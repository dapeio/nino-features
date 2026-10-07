<?php
declare(strict_types=1);

/**
 *	Nino features
 *	build-smoke.php	Contract test for bin/build.php, the publishing tool: a
 *									keypair of this run's own, a signed build of every feature
 *									into a directory of its own, and then what has to hold -
 *									one archive per feature holding exactly its directory and
 *									no tests/, a catalogue.json with the right digest, size and
 *									version per entry, a signature that verifies, a second run
 *									that rebuilds every archive to the same bytes and leaves
 *									catalogue.json and its signature untouched, a changed
 *									feature giving other bytes under the same name and a new
 *									release day, an archive no entry names removed, one entry
 *									per feature whatever the catalogue held before, and the
 *									refusals: a manifest that does not validate, a category
 *									outside the kernel's vocabulary, a checkout without one, a
 *									symlink - and the archives installed through the kernel's
 *									side of the catalogue, \Nino\Catalogue.
 *
 *									Runs against the checkout beside this repository (../nino)
 *									or the one NINO_ROOT names, over its tests/harness.php.
 *
 *	Usage: php tests/build-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/build-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

// A directory of this run's own for everything: keys, output directories,
// a copy of the repository with a broken feature, and - defined before the
// kernel loads - the features directory an installation through
// \Nino\Catalogue lands in, so nothing ever writes into this repository's
// own features/
$work = sys_get_temp_dir(). '/nino-build-smoke-'. uniqid();
mkdir( $work. '/features', 0700, true );

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $work. '/features' );
require $root. '/tests/harness.php';

if( class_exists( '\Nino\Features' ) === false || defined( '\Nino\Features::CATEGORIES' ) === false ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \\Nino\\Features::CATEGORIES - bin/build.php needs a Nino that carries the feature contract and its categories, 1.2 or later'. "\n" );
	exit( 2 );
}

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo			= dirname( __DIR__ );
$build		= $repo. '/bin/build.php';
$baseUrl	= 'https://catalogue.test/features';
$today		= gmdate( 'Y-m-d' );


// --- Helpers -----------------------------------------------------------------

/**
 *	Run a script with this php - stdout and stderr apart, the exit status first
 */
function runScript( string $script, array $args ): array {

	global $work;

	$command = escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $script );
	foreach( $args as $arg )
		$command .= ' '. escapeshellarg( (string) $arg );

	$errors		= $work. '/stderr-'. bin2hex( random_bytes( 4 ) );
	$process	= proc_open( $command, [ 1 => [ 'pipe', 'w' ], 2 => [ 'file', $errors, 'w' ] ], $pipes );
	if( is_resource( $process ) === false )
		return [ -1, '', 'proc_open failed' ];

	$stdout = (string) stream_get_contents( $pipes[1] );
	fclose( $pipes[1] );
	$status = proc_close( $process );
	$stderr = (string) @file_get_contents( $errors );
	@unlink( $errors );

	return [ $status, $stdout, $stderr ];
}

/**
 *	Every entry of a .tar.gz: relative path => 'dir', 'file', 'link' or 'other'
 */
function archiveEntries( string $path ): array {

	$entries	= [];
	$path			= realpath( $path ) ?: $path;

	try {
		$phar = new \PharData( $path );
		foreach( new \RecursiveIteratorIterator( $phar, \RecursiveIteratorIterator::SELF_FIRST ) as $file )
			$entries[ substr( (string) $file->getPathname(), strlen( 'phar://'. $path. '/' ) ) ] = $file->isLink() === true ? 'link' : ( $file->isFile() === true ? 'file' : ( $file->isDir() === true ? 'dir' : 'other' ) );
	}
	catch( \Throwable ) {
		return [];
	}

	ksort( $entries );

	return $entries;
}

function readCatalogue( string $out ): array {

	$document = json_decode( (string) @file_get_contents( $out. '/catalogue.json' ), true );

	return is_array( $document ) === true ? $document : [];
}

function writeCatalogue( string $out, array $document ): void {

	file_put_contents( $out. '/catalogue.json', json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ). "\n" );
}

function entryOf( array $document, string $key, ?string $version = null ): ?array {

	foreach( $document['features'] ?? [] as $entry )
		if( ( $entry['key'] ?? null ) === $key && ( $version === null || ( $entry['version'] ?? null ) === $version ) )
			return $entry;

	return null;
}

/**
 *	sha256 per archive in a directory, by file name
 */
function archiveHashes( string $out ): array {

	$hashes = [];
	foreach( glob( $out. '/*.tar.gz' ) ?: [] as $file )
		$hashes[ basename( $file ) ] = hash_file( 'sha256', $file );
	ksort( $hashes );

	return $hashes;
}

function copyDir( string $from, string $to ): void {

	mkdir( $to, 0755, true );
	foreach( scandir( $from ) ?: [] as $entry ) {
		if( $entry === '.' || $entry === '..' )
			continue;
		if( is_dir( $from. '/'. $entry ) === true )
			copyDir( $from. '/'. $entry, $to. '/'. $entry );
		else
			copy( $from. '/'. $entry, $to. '/'. $entry );
	}
}


// --- This run's key and features ----------------------------------------------

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privatePem );
$publicPem = openssl_pkey_get_details( $keypair )['key'];

$keyFile = $work. '/catalogue-key.pem';
file_put_contents( $keyFile, $privatePem );
chmod( $keyFile, 0600 );

$otherKeypair		= openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
$otherPublicPem	= openssl_pkey_get_details( $otherKeypair )['key'];

// What the tool has to publish: every directory below features/, through
// the same kernel the tool reads them with
$manifests = [];
foreach( scandir( $repo. '/features' ) ?: [] as $entry ) {
	if( $entry[0] === '.' || is_dir( $repo. '/features/'. $entry ) === false )
		continue;
	$manifest = \Nino\Features::manifest( $repo. '/features/'. $entry );
	if( is_array( $manifest ) === true )
		$manifests[ $manifest['key'] ] = $manifest;
}
ksort( $manifests );

echo "Checkout: $root (Nino ". \Nino\VERSION. ")\nWork:     $work\n\n";
echo "The repository - what there is to publish\n";

check( 'every feature directory has a manifest this kernel validates', count( $manifests ) >= 2 && ninoWarnings() === [] );
check( 'the two features the checks below name by key, newsletter and search, are among them', isset( $manifests['newsletter'] ) === true && isset( $manifests['search'] ) === true );
// At least one, not every one: a test is optional here (see AGENTS.md), and
// this check exists to give the archive check below something to leave out. A
// feature that carries none would have made this fail for following the rule
check( 'a feature carries its test under tests/, and the archive is what has to leave it out', array_filter( $manifests, static fn( array $manifest ): bool => ( glob( $manifest['dir']. '/tests/*-smoke.php' ) ?: [] ) !== [] ) !== [] );

check( 'every feature of this repository is filed under one of the kernel\'s categories', array_filter( $manifests, static fn( array $manifest ): bool => in_array( $manifest['category'], \Nino\Features::CATEGORIES, true ) === false ) === [] );

echo "\n";


// --- Refusals before anything is built ------------------------------------------

echo "bin/build.php - usage and what is refused before anything is written\n";

$out = $work. '/refused';

[ $status, , $stderr ] = runScript( $build, [] );
check( 'no arguments: usage, exit 2', $status === 2 && str_contains( $stderr, 'Usage: php bin/build.php' ) === true );

[ $status, , $stderr ] = runScript( $build, [ $work. '/nowhere', $out ] );
check( 'a path that is no Nino checkout: exit 2', $status === 2 && str_contains( $stderr, 'No Nino checkout' ) === true );

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--bogus', '1' ] );
check( 'an unknown option: exit 2', $status === 2 && str_contains( $stderr, '--bogus' ) === true );

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--base-url', 'http://catalogue.test/features' ] );
check( 'a base url that is not https: exit 2, the kernel fetches nothing else', $status === 2 && str_contains( $stderr, 'https' ) === true );

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--key', $work. '/missing.pem' ] );
check( 'a key file that does not exist: exit 1', $status === 1 && str_contains( $stderr, 'cannot be read' ) === true );

file_put_contents( $work. '/not-a-key.pem', "this is not a key\n" );
[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--key', $work. '/not-a-key.pem' ] );
check( 'a key file that holds no private key: exit 1', $status === 1 && str_contains( $stderr, 'not a PEM private key' ) === true );

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--only', 'search' ] );
check( '--only is gone: exit 2, an unknown option', $status === 2 && str_contains( $stderr, '--only' ) === true );

// A checkout whose \Nino\Features has no vocabulary of categories - what
// Nino was before 1.2 - is refused before anything is read, with a reason
$bare = $work. '/bare';
mkdir( $bare. '/_nino', 0755, true );
file_put_contents( $bare. '/_nino/Nino.php', "<?php\nnamespace Nino { const VERSION = '1.1.0'; final class Features {} }\n" );
[ $status, , $stderr ] = runScript( $build, [ $bare, $out ] );
check( 'a checkout without the kernel\'s categories: exit 2, naming what is missing', $status === 2 && str_contains( $stderr, 'CATEGORIES' ) === true );
[ $status, , $stderr ] = runScript( $repo. '/bin/catalogue.php', [ $bare ] );
check( '...and the preview refuses it the same way', $status === 2 && str_contains( $stderr, 'CATEGORIES' ) === true );

check( 'none of that created the output directory', is_dir( $out ) === false );

// A copy of the repository with a feature Nino would skip: the tool locates
// features/ beside its own bin/, so the copy is what it reads
$broken = $work. '/broken';
mkdir( $broken. '/bin', 0755, true );
copy( $build, $broken. '/bin/build.php' );
copyDir( $repo. '/features', $broken. '/features' );
mkdir( $broken. '/features/Broken', 0755, true );
file_put_contents( $broken. '/features/Broken/feature.php', "<?php\nreturn 'not a manifest';\n" );
file_put_contents( $broken. '/features/Broken/Broken.php', "<?php\nnamespace Nino\\Modules { class Broken {} }\n" );

[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
check( 'a manifest that does not validate fails the run, naming the directory', $status === 1 && str_contains( $stderr, 'features/Broken' ) === true && str_contains( $stderr, 'must return an array' ) === true );
check( 'and nothing was built', ( glob( $out. '/*.tar.gz' ) ?: [] ) === [] );

\Nino\Filesystem::removeDir( $broken. '/features/Broken' );

// The Features panel shows a feature by its name and never by its key, so two
// features carrying one name are two rows nobody can tell apart. Written into
// the copy rather than asserted over the real manifests: what is checked is
// the refusal, not that today's names happen not to collide
$seo			= $broken. '/features/Seo/feature.php';
$original	= (string) file_get_contents( $seo );

$collide = static function( string $name ) use ( $seo, $original ): void {
	file_put_contents( $seo, str_replace( "'en_US' => 'SEO', 'de_DE' => 'SEO'", $name, $original ) );
};

$collide( "'en_US' => 'SEO', 'de_DE' => 'Formulare'" );
[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
check( 'two features named the same in one locale fail the run, naming both directories', $status === 1 && str_contains( $stderr, 'features/Forms' ) === true && str_contains( $stderr, 'features/Seo' ) === true && str_contains( $stderr, 'are both named' ) === true );

$collide( "'en_US' => 'fORMS', 'de_DE' => 'SEO'" );
[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
check( 'and a name that differs only in case is the same name', $status === 1 && str_contains( $stderr, 'are both named' ) === true );

// A plain string is the name in every locale, so it meets a map's German one
file_put_contents( $seo, str_replace( "[ 'en_US' => 'SEO', 'de_DE' => 'SEO' ]", "'Galerie'", $original ) );
[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
check( 'a name given as a plain string is that name in every locale', $status === 1 && str_contains( $stderr, 'features/Gallery' ) === true && str_contains( $stderr, 'are both named' ) === true );

file_put_contents( $seo, $original );

// The kernel takes any slug as a category; the build publishes only its six
file_put_contents( $seo, str_replace( "'category'		=> 'marketing',", "'category'		=> 'seo',", $original ) );
[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
check( 'a category outside the kernel\'s vocabulary fails the run, naming the directory and the six', $status === 1 && str_contains( $stderr, 'features/Seo is filed under "seo"' ) === true
	&& str_contains( $stderr, implode( ', ', \Nino\Features::CATEGORIES ) ) === true );
file_put_contents( $seo, str_replace( "	'category'		=> 'marketing',\n", '', $original ) );
[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
check( '...and so does a feature filed under none', $status === 1 && str_contains( $stderr, 'features/Seo is filed under no category' ) === true );
file_put_contents( $seo, $original );
check( 'and nothing was built by either', ( glob( $out. '/*.tar.gz' ) ?: [] ) === [] );


if( @symlink( $broken. '/features/Search/feature.php', $broken. '/features/Search/link.php' ) === true ) {
	[ $status, , $stderr ] = runScript( $broken. '/bin/build.php', [ $root, $out ] );
	check( 'a symlink inside a feature fails the build - the kernel refuses it on the other end', $status === 1 && str_contains( $stderr, 'Search/link.php' ) === true && str_contains( $stderr, 'neither a file nor a directory' ) === true );
	unlink( $broken. '/features/Search/link.php' );
}
else
	echo "  note - symlinks cannot be created here, the symlink refusal is not exercised\n";

echo "\n";


// --- A signed build ---------------------------------------------------------------

echo "bin/build.php - a signed build of every feature\n";

$out = $work. '/dist';

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--key', $keyFile ] );
check( 'the build succeeds', $status === 0 && $stderr === '' );
check( 'it reports every feature as built, the catalogue as written and signed', substr_count( $stdout, "\nbuilt    " ) + (int) str_starts_with( $stdout, 'built    ' ) === count( $manifests ) && str_contains( $stdout, 'wrote    ' ) === true && str_contains( $stdout, 'signed   ' ) === true );

foreach( $manifests as $key => $manifest ) {

	$directory	= basename( $manifest['dir'] );
	$name				= $key. '-'. $manifest['version']. '.tar.gz';
	$entries		= archiveEntries( $out. '/'. $name );
	$top				= array_unique( array_map( static fn( string $path ): string => explode( '/', $path )[0], array_keys( $entries ) ) );

	check( $name. ' exists', is_file( $out. '/'. $name ) === true && $entries !== [] );
	check( $name. ' holds exactly one top-level directory, '. $directory, array_values( $top ) === [ $directory ] && ( $entries[$directory] ?? '' ) === 'dir' );
	check( $name. ' holds the manifest and the class file', ( $entries[ $directory. '/feature.php' ] ?? '' ) === 'file' && ( $entries[ $directory. '/'. $directory. '.php' ] ?? '' ) === 'file' );
	check( $name. ' holds no tests/', array_filter( array_keys( $entries ), static fn( string $path ): bool => str_starts_with( $path, $directory. '/tests' ) === true ) === [] );
	check( $name. ' holds nothing but files and directories', array_filter( $entries, static fn( string $kind ): bool => $kind !== 'file' && $kind !== 'dir' ) === [] );
	check( $name. ' is under the 20 MB the kernel takes', filesize( $out. '/'. $name ) < 20 * 1024 * 1024 );
}

check( 'no other archive was written', count( archiveHashes( $out ) ) === count( $manifests ) );

$json			= (string) file_get_contents( $out. '/catalogue.json' );
$document	= readCatalogue( $out );

check( 'catalogue.json is format 1 with a generated time in ISO 8601 UTC', ( $document['format'] ?? null ) === 1 && preg_match( '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/', (string) ( $document['generated'] ?? '' ) ) === 1 );
check( 'it is pretty-printed with unescaped slashes and unicode and ends in a newline', str_contains( $json, "\n    \"format\": 1" ) === true && str_contains( $json, '\/' ) === false && str_contains( $json, 'über' ) === true && str_ends_with( $json, "}\n" ) === true );
check( 'one entry per feature, sorted by key', array_column( $document['features'] ?? [], 'key' ) === array_keys( $manifests ) );

foreach( $manifests as $key => $manifest ) {

	$name		= $key. '-'. $manifest['version']. '.tar.gz';
	$entry	= entryOf( $document, $key );

	$fields = [ 'key', 'name', 'description', 'category', 'version', 'nino', 'php', 'requires', 'directory', 'archive', 'sha256', 'size', 'released' ];

	// The badge is optional: an entry carries it only where the manifest this
	// kernel read does, right after the category - a kernel from before the
	// field drops it from the manifest, and then the entry has none either
	if( ( $manifest['maturity'] ?? '' ) !== '' )
		array_splice( $fields, array_search( 'category', $fields, true ) + 1, 0, 'maturity' );

	check( $key. ': the entry carries exactly the fields of format 1, in order', is_array( $entry ) === true && array_keys( $entry ) === $fields );
	check( $key. ': name, description, version, nino, php and requires are the manifest\'s', is_array( $entry ) === true && $entry['name'] === $manifest['name'] && $entry['description'] === $manifest['description'] && $entry['version'] === $manifest['version'] && $entry['nino'] === $manifest['nino'] && $entry['php'] === [ 'ext' => $manifest['php']['ext'] ] && $entry['requires'] === $manifest['requires'] );
	check( $key. ': the category is the manifest\'s, and one the catalogue publishes', is_array( $entry ) === true && $entry['category'] === $manifest['category'] && in_array( $entry['category'], \Nino\Features::CATEGORIES, true ) === true );
	check( $key. ': the maturity is the manifest\'s where there is one, and left out where there is none', is_array( $entry ) === true
		&& ( ( $manifest['maturity'] ?? '' ) === '' ? array_key_exists( 'maturity', $entry ) === false : $entry['maturity'] === $manifest['maturity'] ) );
	check( $key. ': directory and archive url name the archive under the base url', is_array( $entry ) === true && $entry['directory'] === basename( $manifest['dir'] ) && $entry['archive'] === $baseUrl. '/'. $name );
	check( $key. ': sha256 and size are the archive\'s as written', is_array( $entry ) === true && $entry['sha256'] === hash_file( 'sha256', $out. '/'. $name ) && $entry['size'] === filesize( $out. '/'. $name ) );
	check( $key. ': released is the build day', is_array( $entry ) === true && $entry['released'] === $today );
}

$signature = (string) file_get_contents( $out. '/catalogue.json.sig' );
check( 'catalogue.json.sig is one line of base64 with a trailing newline', preg_match( '/^[A-Za-z0-9+\/]+=*\n$/', $signature ) === 1 );
check( 'it is an ECDSA signature over SHA-256 of the exact bytes, DER, that the public key accepts', openssl_verify( $json, (string) base64_decode( trim( $signature ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) === 1 );
check( 'another key does not accept it', openssl_verify( $json, (string) base64_decode( trim( $signature ), true ), $otherPublicPem, OPENSSL_ALGO_SHA256 ) !== 1 );
check( 'one changed byte and it does not hold', openssl_verify( $json. ' ', (string) base64_decode( trim( $signature ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) !== 1 );

$parsed = \Nino\Catalogue::parse( $json );

check( 'the kernel parses the catalogue', is_array( $parsed ) === true );
check( 'and reads every entry as written', is_array( $parsed ) === true && count( $parsed['features'] ) === count( $manifests ) && array_map( static function( array $entry ): array {

	// A kernel that knows the badge hands back '' for an entry without one
	// where the document leaves the key out - an empty badge is no badge
	if( ( $entry['maturity'] ?? null ) === '' )
		unset( $entry['maturity'] );

	return $entry;
}, $parsed['features'] ) === $document['features'] );
check( 'the kernel verifies the signature with the public key', \Nino\Catalogue::verify( $json, $signature, $publicPem ) === true );
check( 'and refuses it with another key', \Nino\Catalogue::verify( $json, $signature, $otherPublicPem ) === false );
check( 'the signature verifies the way `openssl dgst -sha256 -sign | base64 -w0` is read: whitespace tolerated', \Nino\Catalogue::verify( $json, "  ". trim( $signature ). "\n\n", $publicPem ) === true );

$firstHashes = archiveHashes( $out );

echo "\n";


// --- A second run ------------------------------------------------------------------

echo "bin/build.php - a second run changes nothing\n";

$firstJson			= (string) file_get_contents( $out. '/catalogue.json' );
$firstSignature	= (string) file_get_contents( $out. '/catalogue.json.sig' );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--key', $keyFile ] );
check( 'the second run succeeds', $status === 0 && $stderr === '' );
check( 'it reads the catalogue, builds every archive again and finds every one the same', str_contains( $stdout, 'reading  ' ) === true && substr_count( "\n". $stdout, "\nsame     " ) === count( $manifests ) + 2 && str_contains( $stdout, 'built    ' ) === false && str_contains( $stdout, 'wrote    ' ) === false );
check( 'every archive is byte for byte what the first run wrote', archiveHashes( $out ) === $firstHashes );
check( 'catalogue.json is not rewritten - generated stays, every byte stays', (string) file_get_contents( $out. '/catalogue.json' ) === $firstJson );
check( 'and neither is its signature - an ECDSA signature made again would differ', (string) file_get_contents( $out. '/catalogue.json.sig' ) === $firstSignature );

// A released date from an earlier day is what the published catalogue would
// carry - it has to survive, and the signature of a catalogue edited by hand
// is made again
$document = readCatalogue( $out );
foreach( $document['features'] as &$entry )
	$entry['released'] = $entry['key'] === 'newsletter' ? '2001-01-01' : '2002-02-02';
unset( $entry );
writeCatalogue( $out, $document );
$editedJson = (string) file_get_contents( $out. '/catalogue.json' );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--key', $keyFile ] );
check( 'a catalogue whose entries are the same, with older release days, is read and left as it is', $status === 0 && $stderr === '' && (string) file_get_contents( $out. '/catalogue.json' ) === $editedJson && str_contains( $stdout, 'wrote    ' ) === false );
check( 'the signature that no longer holds for it is made again', str_contains( $stdout, 'signed   ' ) === true && openssl_verify( $editedJson, (string) base64_decode( trim( (string) file_get_contents( $out. '/catalogue.json.sig' ) ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) === 1 );
$secondDocument = readCatalogue( $out );

echo "\n";


// --- A changed feature ---------------------------------------------------------------

echo "bin/build.php - a feature that changed: other bytes under the same name, released today\n";

// A copy of the repository in which Search carries one more file, built into
// the directory the unchanged build filled - what the server holds is that
$changed = $work. '/changed';
mkdir( $changed. '/bin', 0755, true );
copy( $build, $changed. '/bin/build.php' );
copyDir( $repo. '/features', $changed. '/features' );
file_put_contents( $changed. '/features/Search/note.txt', "one more file\n" );

$searchName = 'search-'. $manifests['search']['version']. '.tar.gz';

[ $status, $stdout, $stderr ] = runScript( $changed. '/bin/build.php', [ $root, $out, '--base-url', $baseUrl, '--key', $keyFile ] );
$document = readCatalogue( $out );
$hashes		= archiveHashes( $out );

check( 'the build succeeds, builds the changed archive and nothing else', $status === 0 && $stderr === '' && substr_count( "\n". $stdout, "\nbuilt    " ) === 1 && str_contains( $stdout, 'built    search' ) === true );
check( 'search keeps its name and has other bytes', isset( $hashes[$searchName] ) === true && $hashes[$searchName] !== $firstHashes[$searchName] && count( $hashes ) === count( $firstHashes ) );
check( 'every other archive is what it was', array_diff_key( $hashes, [ $searchName => 1 ] ) === array_diff_key( $firstHashes, [ $searchName => 1 ] ) );
check( 'its entry names the new digest and size, released today', entryOf( $document, 'search' )['sha256'] === $hashes[$searchName] && entryOf( $document, 'search' )['size'] === filesize( $out. '/'. $searchName ) && entryOf( $document, 'search' )['released'] === $today );
check( 'the entries of the others are untouched, their release days kept', entryOf( $document, 'newsletter' ) === entryOf( $secondDocument, 'newsletter' ) && entryOf( $document, 'newsletter' )['released'] === '2001-01-01' );
check( 'the catalogue is written and signed again', str_contains( $stdout, 'wrote    ' ) === true && \Nino\Catalogue::verify( (string) file_get_contents( $out. '/catalogue.json' ), (string) file_get_contents( $out. '/catalogue.json.sig' ), $publicPem ) === true );
// A copy under another name: PharData keeps what it read from a path
copy( $out. '/'. $searchName, $work. '/changed-search.tar.gz' );
check( 'and the archive holds the new file', ( archiveEntries( $work. '/changed-search.tar.gz' )['Search/note.txt'] ?? '' ) === 'file' );

echo "\n";


// --- An archive no entry names ---------------------------------------------------------

echo "bin/build.php - what no entry names is removed\n";

// Search was built from the copy above; the repository's own tree is what
// the next run builds, and gives back the first bytes under the same name
file_put_contents( $out. '/search-0.0.1.tar.gz', 'an old version' );
file_put_contents( $out. '/gone-1.0.0.tar.gz', 'a feature that is gone' );
file_put_contents( $out. '/notes.txt', 'not an archive' );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--key', $keyFile ] );
check( 'the build succeeds', $status === 0 && $stderr === '' );
check( 'the two archives no entry names are removed, and said so', is_file( $out. '/search-0.0.1.tar.gz' ) === false && is_file( $out. '/gone-1.0.0.tar.gz' ) === false && str_contains( $stdout, 'removed  search-0.0.1.tar.gz' ) === true && str_contains( $stdout, 'removed  gone-1.0.0.tar.gz' ) === true );
check( 'a file that is no archive stays', is_file( $out. '/notes.txt' ) === true );
unlink( $out. '/notes.txt' );
check( 'every archive is the repository\'s again, byte for byte the first build', archiveHashes( $out ) === $firstHashes );
check( 'search is released today again, since its bytes changed back', entryOf( readCatalogue( $out ), 'search' )['released'] === $today && entryOf( readCatalogue( $out ), 'search' )['sha256'] === $firstHashes[$searchName] );

echo "\n";


// --- Unsigned ----------------------------------------------------------------------

echo "bin/build.php without --key - unsigned, the one-liner to sign by hand\n";

// An entry that is not what the build gives: the catalogue changes, and the
// signature it had is no longer one
$document = readCatalogue( $out );
foreach( $document['features'] as &$entry )
	if( $entry['key'] === 'newsletter' )
		$entry['sha256'] = str_repeat( '0', 64 );
unset( $entry );
writeCatalogue( $out, $document );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl ] );
check( 'an unsigned run succeeds', $status === 0 && $stderr === '' && archiveHashes( $out ) === $firstHashes );
check( 'it prints the openssl one-liner', str_contains( $stdout, 'openssl dgst -sha256 -sign' ) === true && str_contains( $stdout, '| base64 -w0 > '. $out. '/catalogue.json.sig' ) === true );
check( 'and removes the signature of the earlier catalogue instead of leaving a stale one', is_file( $out. '/catalogue.json.sig' ) === false && str_contains( $stdout, 'removed  ' ) === true );

// Signed by hand the way the one-liner does it: DER from openssl_sign(), base64
$json = (string) file_get_contents( $out. '/catalogue.json' );
openssl_sign( $json, $der, $privatePem, OPENSSL_ALGO_SHA256 );
file_put_contents( $out. '/catalogue.json.sig', base64_encode( $der ) );
check( 'a signature made by hand over the written bytes verifies', openssl_verify( $json, (string) base64_decode( (string) file_get_contents( $out. '/catalogue.json.sig' ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) === 1
	&& \Nino\Catalogue::verify( $json, (string) file_get_contents( $out. '/catalogue.json.sig' ), $publicPem ) === true );

echo "\n";


// --- What the catalogue held before ----------------------------------------------------

echo "bin/build.php - one entry per feature, whatever the catalogue held before\n";

$merge = $work. '/merge';
mkdir( $merge, 0755, true );

$foreign = static fn( string $key, string $version ): array => [
	'key'					=> $key,
	'name'				=> ucfirst( $key ),
	'description'	=> [ 'en_US' => 'A '. $key, 'de_DE' => 'Ein '. $key ],
	'version'			=> $version,
	'nino'				=> '^1.0',
	'php'					=> [ 'ext' => [] ],
	'requires'		=> [],
	'directory'		=> ucfirst( $key ),
	'archive'			=> $baseUrl. '/'. $key. '-'. $version. '.tar.gz',
	'sha256'			=> hash( 'sha256', $key. $version ),
	'size'				=> 1234,
	'released'		=> '2020-01-01',
];

writeCatalogue( $merge, [ 'format' => 1, 'generated' => '2020-01-01T00:00:00Z', 'features' => [ $foreign( 'other', '1.0.0' ), $foreign( 'search', '0.9.0' ), $foreign( 'other', '2.0.0' ) ] ] );
foreach( [ 'other-1.0.0', 'other-2.0.0', 'search-0.9.0' ] as $old )
	file_put_contents( $merge. '/'. $old. '.tar.gz', $old );

[ $status, , $stderr ] = runScript( $build, [ $root, $merge, '--base-url', $baseUrl ] );
$document = readCatalogue( $merge );
check( 'a build into a catalogue with other keys and versions succeeds', $status === 0 && $stderr === '' );
check( 'the catalogue lists the features of the repository, one entry each, and nothing it held before', array_map( static fn( array $entry ): string => $entry['key']. '@'. $entry['version'], $document['features'] ) === array_map( static fn( string $key ): string => $key. '@'. $manifests[$key]['version'], array_keys( $manifests ) ) );
check( 'a feature that is gone, and an older version of one that is not, are no longer there', entryOf( $document, 'other' ) === null && entryOf( $document, 'search', '0.9.0' ) === null );
check( 'their archives are removed', ( glob( $merge. '/*.tar.gz' ) ?: [] ) !== [] && archiveHashes( $merge ) === $firstHashes );
check( 'the release days of the entries were not theirs to keep: the digests differ, so it is today', array_filter( $document['features'], static fn( array $entry ): bool => $entry['released'] !== gmdate( 'Y-m-d' ) ) === [] );
check( 'the kernel parses the catalogue', is_array( \Nino\Catalogue::parse( (string) file_get_contents( $merge. '/catalogue.json' ) ) ) === true );

$garbage = $work. '/garbage';
mkdir( $garbage, 0755, true );
file_put_contents( $garbage. '/catalogue.json', '{ "format": 2, "features": [] }'. "\n" );

[ $status, , $stderr ] = runScript( $build, [ $root, $garbage, '--base-url', $baseUrl ] );
check( 'a catalogue.json that is not format 1 is not built over', $status === 1 && str_contains( $stderr, 'not building over' ) === true );
check( 'and stays as it was', file_get_contents( $garbage. '/catalogue.json' ) === '{ "format": 2, "features": [] }'. "\n" && ( glob( $garbage. '/*.tar.gz' ) ?: [] ) === [] );

echo "\n";


// --- Through the kernel --------------------------------------------------------

echo "\\Nino\\Catalogue::install - the archives install through the kernel\n";

$appData = ninoSandbox( 'build' );

// The output directory, served from the stub the kernel's own test uses
$remote = [];
foreach( scandir( $out ) ?: [] as $file )
	if( is_file( $out. '/'. $file ) === true )
		$remote[ $baseUrl. '/'. $file ] = (string) file_get_contents( $out. '/'. $file );

$appData['./nino/fetch/stub'] = static function( string $url, array $options ) use ( &$remote ): array {
	if( isset( $remote[$url] ) === false )
		return [ 'ok' => false, 'status' => 404, 'error' => 'http 404' ];
	if( strlen( $remote[$url] ) > $options['maxBytes'] )
		return [ 'ok' => false, 'status' => 200, 'error' => 'the answer exceeds '. $options['maxBytes']. ' bytes' ];
	return [ 'ok' => true, 'status' => 200, 'body' => $remote[$url] ];
};

$appData['/nino/catalogue/url'] = $baseUrl. '/catalogue.json';
$appData['/nino/catalogue/key'] = $publicPem;

$catalogue = \Nino\Catalogue::fetch( $appData );
check( 'the kernel fetches and verifies the catalogue as published', is_array( $catalogue ) === true && count( $catalogue['features'] ) === count( $manifests ) );

// Which of them this kernel can run is bin/applicable.php's to say, the
// same answer the CI copies by: a feature written for a newer Nino than
// this one is offered, as incompatible, and cannot be installed
[ $applicableStatus, $applicableOut ] = runScript( $repo. '/bin/applicable.php', [ $root ] );
$runnable = array_values( array_filter( explode( "\n", $applicableOut ) ) );
check( 'bin/applicable.php names the directories of the features this kernel can run', $applicableStatus === 0 && $runnable !== []
	&& array_diff( $runnable, array_map( static fn( array $manifest ): string => basename( $manifest['dir'] ), $manifests ) ) === [] );

$offers = is_array( $catalogue ) === true ? \Nino\Catalogue::offers( $appData, $catalogue ) : [];
$states = [];
foreach( $offers as $key => $offer )
	$states[(string) $key] = $offer['state'];
$expectedStates = [];
foreach( $manifests as $key => $manifest )
	$expectedStates[$key] = in_array( basename( $manifest['dir'] ), $runnable, true ) === true ? 'available' : 'incompatible';
ksort( $states );
ksort( $expectedStates );
check( 'every feature this kernel can run is offered to it as available, every other one as incompatible', count( $offers ) === count( $manifests ) && $states === $expectedStates );

foreach( $manifests as $key => $manifest ) {
	if( $expectedStates[$key] === 'incompatible' ) {
		$refusal = \Nino\Catalogue::install( $appData, $key, $manifest['version'] );
		check( $key. ' needs Nino '. $manifest['nino']. ', and this kernel refuses it: '. ( is_string( $refusal ) === true ? $refusal : '' ), is_string( $refusal ) === true
			&& str_contains( $refusal, 'requires Nino '. $manifest['nino'] ) === true && is_dir( NINO_FEATURES_DIR. '/'. basename( $manifest['dir'] ) ) === false );
		continue;
	}

	$directory = basename( $manifest['dir'] );
	check( $key. ' '. $manifest['version']. ' installs from the archive', \Nino\Catalogue::install( $appData, $key, $manifest['version'] ) === true );
	check( $key. ': the directory is in place, without tests/', is_file( NINO_FEATURES_DIR. '/'. $directory. '/feature.php' ) === true && is_file( NINO_FEATURES_DIR. '/'. $directory. '/'. $directory. '.php' ) === true && is_dir( NINO_FEATURES_DIR. '/'. $directory. '/tests' ) === false );
	check( $key. ': the registry sees the version the catalogue promised', ( \Nino\Features::get( $appData, $key )['version'] ?? null ) === $manifest['version'] && ninoWarnings() === [] );
}

echo "\n";

// --- an empty file where an archive should be ----------------------------

echo "bin/build.php - a zero-byte file is no archive, it is replaced\n";

$empty = $work. '/empty';
mkdir( $empty );
$firstKey = array_key_first( $manifests );
$emptyName = $firstKey. '-'. $manifests[$firstKey]['version']. '.tar.gz';
file_put_contents( $empty. '/'. $emptyName, '' );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $empty ] );
check( 'a build over an empty archive file succeeds', $status === 0 && $stderr === '' );
check( 'the archive is built in its place', filesize( $empty. '/'. $emptyName ) > 0 && hash_file( 'sha256', $empty. '/'. $emptyName ) === $firstHashes[$emptyName] );
$emptyDocument = readCatalogue( $empty );
check( 'the entry names the real size', ( entryOf( $emptyDocument, $firstKey, $manifests[$firstKey]['version'] )['size'] ?? 0 ) === filesize( $empty. '/'. $emptyName ) );

echo "\n";

ninoDone( $appData );
