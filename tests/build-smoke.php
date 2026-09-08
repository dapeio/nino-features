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
 *									that rebuilds nothing and leaves every archive's bytes
 *									alone, --only touching one entry, the merge keeping every
 *									other entry, and the refusals: a manifest that does not
 *									validate, a symlink, a key nothing has, an archive that is
 *									not what the catalogue names. Where the checkout carries the
 *									kernel side of the catalogue (\Nino\Catalogue), the archives
 *									are installed through it too.
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

if( class_exists( '\Nino\Features' ) === false ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \\Nino\\Features - bin/build.php needs a Nino that carries the feature contract'. "\n" );
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
check( 'the two features of the catalogue are among them', isset( $manifests['newsletter'] ) === true && isset( $manifests['search'] ) === true );
check( 'every feature carries a test under tests/ - the archive is what has to leave it out', array_filter( $manifests, static fn( array $manifest ): bool => ( glob( $manifest['dir']. '/tests/*-smoke.php' ) ?: [] ) === [] ) === [] );

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

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--only', 'nosuch' ] );
check( 'a key no feature has: exit 1, naming the keys there are', $status === 1 && str_contains( $stderr, '"nosuch"' ) === true && str_contains( $stderr, 'newsletter' ) === true && str_contains( $stderr, 'search' ) === true );

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
	check( $name. ' holds the README and the changelog', ( $entries[ $directory. '/README.md' ] ?? '' ) === 'file' && ( $entries[ $directory. '/CHANGELOG.md' ] ?? '' ) === 'file' );
	check( $name. ' holds nothing but files and directories', array_filter( $entries, static fn( string $kind ): bool => $kind !== 'file' && $kind !== 'dir' ) === [] );
	check( $name. ' is listed in sorted order', array_keys( $entries ) === array_keys( $entries ) && filesize( $out. '/'. $name ) < 20 * 1024 * 1024 );
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

	check( $key. ': the entry carries exactly the fields of format 1, in order', is_array( $entry ) === true && array_keys( $entry ) === [ 'key', 'name', 'description', 'version', 'nino', 'php', 'requires', 'directory', 'archive', 'sha256', 'size', 'released' ] );
	check( $key. ': name, description, version, nino, php and requires are the manifest\'s', is_array( $entry ) === true && $entry['name'] === $manifest['name'] && $entry['description'] === $manifest['description'] && $entry['version'] === $manifest['version'] && $entry['nino'] === $manifest['nino'] && $entry['php'] === [ 'ext' => $manifest['php']['ext'] ] && $entry['requires'] === $manifest['requires'] );
	check( $key. ': directory and archive url name the archive under the base url', is_array( $entry ) === true && $entry['directory'] === basename( $manifest['dir'] ) && $entry['archive'] === $baseUrl. '/'. $name );
	check( $key. ': sha256 and size are the archive\'s as written', is_array( $entry ) === true && $entry['sha256'] === hash_file( 'sha256', $out. '/'. $name ) && $entry['size'] === filesize( $out. '/'. $name ) );
	check( $key. ': released is the build day', is_array( $entry ) === true && $entry['released'] === $today );
}

$signature = (string) file_get_contents( $out. '/catalogue.json.sig' );
check( 'catalogue.json.sig is one line of base64 with a trailing newline', preg_match( '/^[A-Za-z0-9+\/]+=*\n$/', $signature ) === 1 );
check( 'it is an ECDSA signature over SHA-256 of the exact bytes, DER, that the public key accepts', openssl_verify( $json, (string) base64_decode( trim( $signature ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) === 1 );
check( 'another key does not accept it', openssl_verify( $json, (string) base64_decode( trim( $signature ), true ), $otherPublicPem, OPENSSL_ALGO_SHA256 ) !== 1 );
check( 'one changed byte and it does not hold', openssl_verify( $json. ' ', (string) base64_decode( trim( $signature ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) !== 1 );

if( class_exists( '\Nino\Catalogue' ) === true ) {

	$parsed = \Nino\Catalogue::parse( $json );

	check( 'the kernel parses the catalogue', is_array( $parsed ) === true );
	check( 'and reads every entry as written', is_array( $parsed ) === true && count( $parsed['features'] ) === count( $manifests ) && array_map( static fn( array $entry ): array => $entry, $parsed['features'] ) === $document['features'] );
	check( 'the kernel verifies the signature with the public key', \Nino\Catalogue::verify( $json, $signature, $publicPem ) === true );
	check( 'and refuses it with another key', \Nino\Catalogue::verify( $json, $signature, $otherPublicPem ) === false );
	check( 'the signature verifies the way `openssl dgst -sha256 -sign | base64 -w0` is read: whitespace tolerated', \Nino\Catalogue::verify( $json, "  ". trim( $signature ). "\n\n", $publicPem ) === true );
}
else
	echo "  note - this Nino has no \\Nino\\Catalogue yet: parse() and verify() are not exercised\n";

$firstHashes		= archiveHashes( $out );
$firstDocument	= $document;

echo "\n";


// --- A second run ------------------------------------------------------------------

echo "bin/build.php - a second run rebuilds nothing\n";

// A released date from an earlier day is what the published catalogue would
// carry - it has to survive
$document = readCatalogue( $out );
foreach( $document['features'] as &$entry )
	$entry['released'] = $entry['key'] === 'newsletter' ? '2001-01-01' : '2002-02-02';
unset( $entry );
writeCatalogue( $out, $document );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--key', $keyFile ] );
check( 'the second run succeeds', $status === 0 && $stderr === '' );
check( 'it merges into the existing catalogue and keeps every archive', str_contains( $stdout, 'merging into' ) === true && substr_count( $stdout, "\nkept     " ) + (int) str_starts_with( $stdout, 'kept     ' ) === count( $manifests ) && str_contains( $stdout, 'built    ' ) === false );
check( 'every archive is byte for byte what the first run wrote', archiveHashes( $out ) === $firstHashes );

$document = readCatalogue( $out );
check( 'the entries are unchanged, their released dates kept', entryOf( $document, 'newsletter' )['released'] === '2001-01-01' && entryOf( $document, 'search' )['released'] === '2002-02-02'
	&& array_map( static fn( array $entry ): array => array_diff_key( $entry, [ 'released' => 1 ] ), $document['features'] ) === array_map( static fn( array $entry ): array => array_diff_key( $entry, [ 'released' => 1 ] ), $firstDocument['features'] ) );
check( 'generated moved on', ( $document['generated'] ?? '' ) >= ( $firstDocument['generated'] ?? '' ) );
$json = (string) file_get_contents( $out. '/catalogue.json' );
check( 'the catalogue was signed again, over its new bytes', openssl_verify( $json, (string) base64_decode( trim( (string) file_get_contents( $out. '/catalogue.json.sig' ) ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) === 1 );

echo "\n";


// --- --only ------------------------------------------------------------------------

echo "bin/build.php --only - one feature, every other entry untouched\n";

$searchName = 'search-'. $manifests['search']['version']. '.tar.gz';
unlink( $out. '/'. $searchName );

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--only', 'search', '--key', $keyFile ] );
check( '--only search with its archive gone rebuilds it', $status === 0 && $stderr === '' && str_contains( $stdout, 'built    search' ) === true );
check( 'and mentions no other feature', str_contains( $stdout, 'newsletter' ) === false );
check( 'the rebuilt archive is the very bytes of the first build - a build is reproducible', archiveHashes( $out ) === $firstHashes );

$document = readCatalogue( $out );
check( 'the search entry is back as it was, its released date kept from the entry', entryOf( $document, 'search' ) === array_replace( entryOf( $firstDocument, 'search' ), [ 'released' => '2002-02-02' ] ) );
check( 'the newsletter entry was not touched', entryOf( $document, 'newsletter' ) === array_replace( entryOf( $firstDocument, 'newsletter' ), [ 'released' => '2001-01-01' ] ) );
check( 'still one entry per feature', count( $document['features'] ) === count( $manifests ) );

echo "\n";


// --- Immutability ------------------------------------------------------------------

echo "bin/build.php - a published version is immutable\n";

// The archive on disk is not what the catalogue names
$document = readCatalogue( $out );
foreach( $document['features'] as &$entry )
	if( $entry['key'] === 'search' )
		$entry['sha256'] = str_repeat( '0', 64 );
unset( $entry );
writeCatalogue( $out, $document );

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--only', 'search' ] );
check( 'an archive that is not the one its entry names fails the run', $status === 1 && str_contains( $stderr, $searchName ) === true && str_contains( $stderr, 'immutable' ) === true );
check( 'the archive stays as it is', archiveHashes( $out ) === $firstHashes );

// The archive is gone and a rebuild would not give what the entry names
unlink( $out. '/'. $searchName );

[ $status, , $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl, '--only', 'search' ] );
check( 'a rebuild that does not give the published bytes fails the run', $status === 1 && str_contains( $stderr, 'search@'. $manifests['search']['version'] ) === true && str_contains( $stderr, 'immutable' ) === true );
check( 'and leaves no archive behind that the catalogue would not name', is_file( $out. '/'. $searchName ) === false );
check( 'the catalogue was not rewritten', entryOf( readCatalogue( $out ), 'search' )['sha256'] === str_repeat( '0', 64 ) );

writeCatalogue( $out, $firstDocument );

echo "\n";


// --- Unsigned ----------------------------------------------------------------------

echo "bin/build.php without --key - unsigned, the one-liner to sign by hand\n";

[ $status, $stdout, $stderr ] = runScript( $build, [ $root, $out, '--base-url', $baseUrl ] );
check( 'an unsigned run succeeds', $status === 0 && $stderr === '' && archiveHashes( $out ) === $firstHashes );
check( 'it prints the openssl one-liner', str_contains( $stdout, 'openssl dgst -sha256 -sign' ) === true && str_contains( $stdout, '| base64 -w0 > '. $out. '/catalogue.json.sig' ) === true );
check( 'and removes the signature of the earlier catalogue instead of leaving a stale one', is_file( $out. '/catalogue.json.sig' ) === false && str_contains( $stdout, 'removed  ' ) === true );

// Signed by hand the way the one-liner does it: DER from openssl_sign(), base64
$json = (string) file_get_contents( $out. '/catalogue.json' );
openssl_sign( $json, $der, $privatePem, OPENSSL_ALGO_SHA256 );
file_put_contents( $out. '/catalogue.json.sig', base64_encode( $der ) );
check( 'a signature made by hand over the written bytes verifies', openssl_verify( $json, (string) base64_decode( (string) file_get_contents( $out. '/catalogue.json.sig' ), true ), $publicPem, OPENSSL_ALGO_SHA256 ) === 1
	&& ( class_exists( '\Nino\Catalogue' ) === false || \Nino\Catalogue::verify( $json, (string) file_get_contents( $out. '/catalogue.json.sig' ), $publicPem ) === true ) );

echo "\n";


// --- Merging -----------------------------------------------------------------------

echo "bin/build.php - the catalogue is merged, never replaced\n";

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

[ $status, , $stderr ] = runScript( $build, [ $root, $merge, '--base-url', $baseUrl ] );
$document = readCatalogue( $merge );
check( 'a build into a catalogue with other keys and versions succeeds', $status === 0 && $stderr === '' );
check( 'every foreign entry survives as it was', entryOf( $document, 'other', '1.0.0' ) === $foreign( 'other', '1.0.0' ) && entryOf( $document, 'other', '2.0.0' ) === $foreign( 'other', '2.0.0' ) && entryOf( $document, 'search', '0.9.0' ) === $foreign( 'search', '0.9.0' ) );
check( 'sorted by key, then version descending', array_map( static fn( array $entry ): string => $entry['key']. '@'. $entry['version'], $document['features'] ) === [ 'newsletter@'. $manifests['newsletter']['version'], 'other@2.0.0', 'other@1.0.0', 'search@'. $manifests['search']['version'], 'search@0.9.0' ] );

if( class_exists( '\Nino\Catalogue' ) === true )
	check( 'the kernel parses the merged catalogue', is_array( \Nino\Catalogue::parse( (string) file_get_contents( $merge. '/catalogue.json' ) ) ) === true );

$garbage = $work. '/garbage';
mkdir( $garbage, 0755, true );
file_put_contents( $garbage. '/catalogue.json', '{ "format": 2, "features": [] }'. "\n" );

[ $status, , $stderr ] = runScript( $build, [ $root, $garbage, '--base-url', $baseUrl ] );
check( 'a catalogue.json that is not format 1 is not merged into', $status === 1 && str_contains( $stderr, 'not merging' ) === true );
check( 'and stays as it was', file_get_contents( $garbage. '/catalogue.json' ) === '{ "format": 2, "features": [] }'. "\n" && ( glob( $garbage. '/*.tar.gz' ) ?: [] ) === [] );

echo "\n";


// --- Through the kernel --------------------------------------------------------

$appData = [];

if( class_exists( '\Nino\Catalogue' ) === true && class_exists( '\Nino\Fetch' ) === true ) {

	echo "\\Nino\\Catalogue::install - the archives install through the kernel\n";

	$appData = ninoSandbox( 'build' );

	// The output directory, served from the stub the kernel's own test uses
	$remote = [ 'https://catalogue.test/ping' => 'pong' ];
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

	if( ( \Nino\Fetch::get( $appData, 'https://catalogue.test/ping' )['body'] ?? '' ) !== 'pong' )
		echo "  note - this Nino's \\Nino\\Fetch honours no stub, the installation is not exercised\n";
	else {

		$appData['/nino/catalogue/url'] = $baseUrl. '/catalogue.json';
		$appData['/nino/catalogue/key'] = $publicPem;

		$catalogue = \Nino\Catalogue::fetch( $appData );
		check( 'the kernel fetches and verifies the catalogue as published', is_array( $catalogue ) === true && count( $catalogue['features'] ) === count( $manifests ) );

		$offers = is_array( $catalogue ) === true ? \Nino\Catalogue::offers( $appData, $catalogue ) : [];
		check( 'every feature is offered to this kernel as available', array_filter( $offers, static fn( array $offer ): bool => $offer['state'] !== 'available' ) === [] && count( $offers ) === count( $manifests ) );

		foreach( $manifests as $key => $manifest ) {
			$directory = basename( $manifest['dir'] );
			check( $key. ' '. $manifest['version']. ' installs from the archive', \Nino\Catalogue::install( $appData, $key, $manifest['version'] ) === true );
			check( $key. ': the directory is in place, without tests/', is_file( NINO_FEATURES_DIR. '/'. $directory. '/feature.php' ) === true && is_file( NINO_FEATURES_DIR. '/'. $directory. '/'. $directory. '.php' ) === true && is_dir( NINO_FEATURES_DIR. '/'. $directory. '/tests' ) === false );
			check( $key. ': the registry sees the version the catalogue promised', ( \Nino\Features::get( $appData, $key )['version'] ?? null ) === $manifest['version'] && ninoWarnings() === [] );
		}
	}

	echo "\n";
}
else
	echo "note - this Nino has no \\Nino\\Catalogue yet: the installation through the kernel is not exercised\n\n";

ninoDone( $appData );
