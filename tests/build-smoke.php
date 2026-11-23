<?php
declare(strict_types=1);

/**
 *	Nino features
 *	build-smoke.php	Contract test for bin/build.php, the publishing tool, and for the catalogue of format 2 it builds. A tree of this run's own -
 *									three features (copies of Basis, of Themes and of Collections), a theme (the craft-probe of Themes' fixtures, with a picture, and a part of the group Collections brings), a package of blocks with
 *									a svg and a png, an application - is built into a directory with a key of this run's own, and then what has to hold:
 *
 *										A. the index: the kernel reads every entry; kind, requires, parts and blocks are what the files say, not what anybody
 *										   wrote down; the archive, the picture and the signature are the bytes the entry names; the kernel's reader gives back
 *										   the files the archives were built from
 *										B. a second run gives the same bytes and writes nothing; what no entry names is removed; a catalogue of another format
 *										   is not built over
 *										C. what the build refuses, with the path of the file and before a file is written: a theme with a php file, an @import, a
 *										   token of another group, a group no feature brings, no picture, a folder that is not its key; a svg that loses an
 *										   element or an attribute in the rebuild; a block with [html]; an application that breaks a rule; the usage
 *										D. from the built directory to a project: Catalogue::install() of the theme and of the package of blocks through the stub
 *										   of the client, the svg written as the rebuild of the kernel
 *
 *									Runs against the checkout beside this repository (../nino) or the one NINO_ROOT names, over its tests/harness.php.
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

// A directory of this run's own for everything: keys, trees, output directories - and, defined before the kernel loads, the features directory an
// installation through \Nino\Catalogue lands in, so nothing ever writes into this repository's own features/
$work = sys_get_temp_dir(). '/nino-build-smoke-'. uniqid();
mkdir( $work. '/project-features', 0700, true );

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $work. '/project-features' );
require $root. '/tests/harness.php';
require dirname( __DIR__ ). '/bin/delivered.php';

if( class_exists( '\Nino\Packages' ) === false || class_exists( '\Nino\Archive' ) === false || \Nino\Catalogue::FORMAT !== 2 ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no packages and no catalogue of format 2 - bin/build.php needs the Nino that reads them'. "\n" );
	exit( 2 );
}

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo			= dirname( __DIR__ );
$baseUrl	= 'https://catalogue.test/packages';

// Whether the kernel delivers Basis itself: then the copy of Basis the tree holds rests, the build leaves it out, and the index lists none - the kernel has it
$delivers	= deliveredFeatures() !== [];

// A picture of each kind a block may bring, and a svg that the rebuild of the kernel keeps whole - a comment is no element and no attribute, and the rebuild
// does not keep it
$webp	= (string) base64_decode( 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==' );
$png	= (string) base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );
$svg	= '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><!-- the original --><rect id="a" width="10" height="10" fill="#f00"/></svg>'. "\n";


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
 *	A tree copied, without the names of the top level that are skipped
 */
function copyTree( string $from, string $to, array $skip = [] ): void {

	mkdir( $to, 0755, true );

	foreach( scandir( $from ) ?: [] as $entry ) {

		if( $entry === '.' || $entry === '..' || in_array( $entry, $skip, true ) === true )
			continue;

		if( is_dir( $from. '/'. $entry ) === true )
			copyTree( $from. '/'. $entry, $to. '/'. $entry );
		else
			copy( $from. '/'. $entry, $to. '/'. $entry );
	}
}

/**
 *	The files below a folder: path below it => bytes
 */
function filesOf( string $dir ): array {

	$found = [];

	if( is_dir( $dir ) === false )
		return $found;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		$found[ substr( (string) $file->getPathname(), strlen( $dir ) + 1 ) ] = (string) file_get_contents( (string) $file->getPathname() );

	ksort( $found, SORT_STRING );

	return $found;
}

/**
 *	The sha256 of every file of a directory, by name
 */
function digestsOf( string $dir ): array {

	return array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), filesOf( $dir ) );
}

/**
 *	The block of a package of blocks: the files below its folder, with a slot for a svg and one for a png
 */
function gastroBlock( string $svg, string $png ): array {

	$words = static fn( string $de, string $en ): array => [ 'de_DE' => $de, 'en_US' => $en ];
	$slot = static fn( string $label ): array => [ 'placeholder' => '/nino/placeholder/landscape', 'width' => 800, 'height' => 600, 'label' => $words( $label, $label ) ];

	return [
		'block.json'				=> json_encode( [ 'name' => $words( 'Speisen', 'Menu' ), 'description' => $words( 'Eine Karte', 'A menu' ), 'category' => 'content', 'rank' => 5,
			'images' => [ 'background' => $slot( 'Hintergrund' ), '1-photo' => $slot( 'Foto' ) ] ] ),
		'block.tpl'					=> "[section-wrap id=\"menu\" image=\"~background\"]\n\t[section-row]\n\t\t[section-col id=\"menu-1\"]\n\t\t\t[section-title ~title]\n\t\t\t[image ~photo alt=\"\"]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]",
		'text/de_DE.json'		=> json_encode( [ '1-title' => 'Titel' ] ),
		'text/en_US.json'		=> json_encode( [ '1-title' => 'Title' ] ),
		'images/background.svg'	=> $svg,
		'images/1-photo.png'		=> $png,
	];
}

/**
 *	The tree bin/build.php reads, below a directory: the tool beside it, a feature, a theme, a package of blocks and an application. The tool locates
 *	features/, themes/ and blocks/ beside its own bin/, so the copy is what it reads
 */
function makeTree( string $dest ): void {

	global $repo, $webp, $png, $svg, $delivers;

	copyTree( $repo. '/bin', $dest. '/bin', [ 'check.sh', 'release.sh' ] );
	copyTree( $repo. '/features/Basis', $dest. '/features/Basis', [ 'tests' ] );
	copyTree( $repo. '/features/Themes', $dest. '/features/Themes', [ 'tests' ] );
	copyTree( $repo. '/features/Collections', $dest. '/features/Collections', [ 'tests' ] );
	copyTree( $repo. '/features/Themes/tests/fixtures/themes/craft-probe', $dest. '/themes/craft-probe' );
	file_put_contents( $dest. '/themes/craft-probe/preview.webp', $webp );
	mkdir( $dest. '/themes/craft-probe/parts/collection' );
	file_put_contents( $dest. '/themes/craft-probe/parts/collection/part.json', '{ "contract": "collections@^1.0" }' );

	mkdir( $dest. '/blocks/gastro/menu', 0755, true );
	file_put_contents( $dest. '/blocks/gastro/pack.json', json_encode( [ 'key' => 'gastro', 'version' => '1.0.0', 'name' => [ 'de_DE' => 'Gastro', 'en_US' => 'Gastro' ], 'description' => [ 'de_DE' => 'Karten', 'en_US' => 'Menus' ] ] ) );
	file_put_contents( $dest. '/blocks/gastro/preview.webp', $webp );

	foreach( gastroBlock( $svg, $png ) as $path => $bytes ) {
		@mkdir( dirname( $dest. '/blocks/gastro/menu/'. $path ), 0755, true );
		file_put_contents( $dest. '/blocks/gastro/menu/'. $path, $bytes );
	}

	mkdir( $dest. '/applications', 0755, true );
	file_put_contents( $dest. '/applications/website.php', "<?php\nreturn [ 'key' => 'website', 'name' => [ 'de_DE' => 'Webseite', 'en_US' => 'Website' ], 'description' => [ 'de_DE' => 'Eine Seite', 'en_US' => 'A site' ],\n\t'features' => [ ". ( $delivers === true ? '' : "'basis', " ). "'themes' ], 'next' => '/_admin/#themes', 'start' => null, 'menu' => null ];\n" );
}

/**
 *	Build a tree into a directory, signed
 */
function buildTree( string $tree, string $out ): array {

	global $root, $baseUrl, $work;

	return runScript( $tree. '/bin/build.php', [ $root, $out, '--base-url', $baseUrl, '--key', $work. '/catalogue-key.pem' ] );
}

/**
 *	One entry of the document as the build wrote it, by its kind and key
 */
function entryOf( array $document, string $kind, string $key ): array {

	foreach( $document['packages'] ?? [] as $entry )
		if( $entry['kind'] === $kind && $entry['key'] === $key )
			return $entry;

	return [];
}


// --- This run's key and tree -------------------------------------------------

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privatePem );
$publicPem = openssl_pkey_get_details( $keypair )['key'];

file_put_contents( $work. '/catalogue-key.pem', $privatePem );
chmod( $work. '/catalogue-key.pem', 0600 );

$tree	= $work. '/tree';
$out	= $work. '/out';
makeTree( $tree );

echo "Checkout: $root (Nino ". \Nino\VERSION. ")\nWork:     $work\n\n";


// --- A. The index --------------------------------------------------------------

echo "bin/build.php - a feature, a theme and a package of blocks into a catalogue of format 2\n";

[ $status, $stdout, $stderr ] = buildTree( $tree, $out );

$expected = array_merge( [ 'blocks-gastro-1.0.0.tar.gz', 'blocks-gastro-1.0.0.webp', 'catalogue.json', 'catalogue.json.sig' ], $delivers === true ? [] : [ 'feature-basis-1.0.0.tar.gz' ], [ 'feature-collections-1.0.0.tar.gz', 'feature-themes-1.0.0.tar.gz', 'theme-craft-probe-1.0.0.tar.gz', 'theme-craft-probe-1.0.0.webp' ] );

check( 'the build of the tree succeeds, says nothing on stderr, and writes one archive per package, a picture per theme and per package of blocks, the index and its signature', $status === 0 && $stderr === '' && array_keys( filesOf( $out ) ) === $expected );

$json			= (string) @file_get_contents( $out. '/catalogue.json' );
$document	= (array) json_decode( $json, true );
$parsed		= \Nino\Catalogue::parse( $json );
$parsed		= is_array( $parsed ) === true ? $parsed : [ 'packages' => [], 'unreadable' => [ $parsed ], 'applications' => [] ];
$feature	= entryOf( $document, 'feature', 'themes' );
$theme		= entryOf( $document, 'theme', 'craft-probe' );
$pack			= entryOf( $document, 'blocks', 'gastro' );

check( 'the kernel reads the index whole - every entry, none left out, in the order feature, theme, blocks - and the signature holds over its bytes',
	$parsed['unreadable'] === [] && array_map( static fn( array $entry ): string => $entry['kind']. ':'. $entry['key'], $parsed['packages'] ) === array_merge( $delivers === true ? [] : [ 'feature:basis' ], [ 'feature:collections', 'feature:themes', 'theme:craft-probe', 'blocks:gastro' ] )
	&& array_column( $parsed['applications'], 'key' ) === [ 'website' ] && \Nino\Catalogue::verify( $json, (string) file_get_contents( $out. '/catalogue.json.sig' ), $publicPem ) === true );

$source		= filesOf( $tree. '/themes/craft-probe' );
$groups		= [];

foreach( array_keys( $source ) as $path )
	if( preg_match( '#^parts/([^/]+)/part\.json$#D', $path, $found ) === 1 )
		$groups[$found[1]] = (string) json_decode( $source[$path], true )['contract'];

ksort( $groups );

check( 'what a theme requires is computed from its files - the feature of the theme, the feature of every group of its parts and of the components of its blocks, the fonts of its brand without the stacks of the system - and each part carries the contract of its part.json, the page the one of the kernel',
	$theme['requires'] === [ 'nino' => '^2.0', 'features' => [ 'basis', 'collections', 'themes' ], 'fonts' => [ 'open-sans' ] ] && $theme['blocks'] === [ 'hero-soft' ] && array_keys( $theme['parts'] ) === array_keys( $groups ) && $theme['parts']['page'] === [ 'contract' => $groups['page'] ]
	&& $theme['parts']['section'] === [ 'feature' => 'basis', 'contract' => substr( $groups['section'], strlen( 'basis@' ) ) ] && $theme['parts']['collection'] === [ 'feature' => 'collections', 'contract' => '^1.0' ] && str_starts_with( $groups['page'], 'kernel@' ) === true );

check( 'what a package of blocks requires is the feature of the components its blocks call, and it lists its blocks with their category; a feature requires what its manifest says and has no picture',
	$pack['requires'] === [ 'nino' => '^2.0', 'features' => [ 'basis' ] ] && $pack['blocks'] === [ [ 'key' => 'menu', 'category' => 'content' ] ]
	&& $feature['requires'] === [ 'nino' => '^2.0', 'php' => [], 'features' => [ 'basis' ] ] && $feature['directory'] === 'Themes' && isset( $feature['preview'] ) === false && isset( $feature['parts'] ) === false );

// the bytes each entry names, and what the reader of the kernel makes of the archive
$wrong = [];

foreach( array_merge( $delivers === true ? [] : [ [ entryOf( $document, 'feature', 'basis' ), 'features/Basis' ] ], [ [ $feature, 'features/Themes' ], [ entryOf( $document, 'feature', 'collections' ), 'features/Collections' ], [ $theme, 'themes/craft-probe' ], [ $pack, 'blocks/gastro' ] ] ) as [ $entry, $folder ] ) {

	$name		= $entry['kind']. '-'. $entry['key']. '-'. $entry['version'];
	$bytes	= (string) @file_get_contents( $out. '/'. $name. '.tar.gz' );
	$files	= \Nino\Archive::read( $bytes, $entry['kind'] === 'feature' ? $entry['directory'] : $entry['key'], \Nino\Packages::LIMITS[$entry['kind']]['files'], \Nino\Packages::LIMITS[$entry['kind']]['bytes'], \Nino\Packages::LIMITS[$entry['kind']]['bytes'] );
	$from		= filesOf( $tree. '/'. $folder );

	if( $entry['kind'] === 'feature' )
		$from = array_filter( $from, static fn( string $path ): bool => explode( '/', $path )[0] !== 'tests', ARRAY_FILTER_USE_KEY );

	if( $entry['archive'] !== $baseUrl. '/'. $name. '.tar.gz' || $entry['sha256'] !== hash( 'sha256', $bytes ) || $entry['size'] !== strlen( $bytes ) || is_array( $files ) === false || $files !== $from )
		$wrong[] = $name;

	if( $entry['kind'] !== 'feature' ) {

		$picture = (string) @file_get_contents( $out. '/'. $name. '.webp' );

		if( $entry['preview'] !== [ 'url' => $baseUrl. '/'. $name. '.webp', 'sha256' => hash( 'sha256', $picture ), 'size' => strlen( $picture ) ] || $picture !== $webp )
			$wrong[] = $name. '.webp';
	}
}

check( 'the archive, the picture and the digest an entry names are the bytes in the directory, and the reader of the kernel gives back the files of the folder each archive was built from - the svg as it was, the tests of a feature not among them'. ( $wrong === [] ? '' : ' - NOT: '. implode( ', ', $wrong ) ),
	$wrong === [] );

echo "\n";


// --- B. A second run, what nothing names, another format -----------------------

echo "bin/build.php - a second run, and what lies in the directory\n";

$before		= digestsOf( $out );
$modified	= array_map( 'filemtime', glob( $out. '/*' ) ?: [] );

file_put_contents( $out. '/theme-gone-1.0.0.tar.gz', 'x' );
file_put_contents( $out. '/theme-gone-1.0.0.webp', 'x' );

[ $status, $stdout, $stderr ] = buildTree( $tree, $out );

check( 'a second run builds every archive again to the same bytes and writes nothing - the index and its signature stay as they were, none of them is said to be built, wrote or signed',
	$status === 0 && $stderr === '' && digestsOf( $out ) === $before && preg_match( '/^(built|wrote|signed) /m', $stdout ) === 0 && array_map( 'filemtime', glob( $out. '/*' ) ?: [] ) === $modified );
check( 'an archive and a picture that no entry names are removed: the directory holds what the catalogue lists', array_keys( filesOf( $out ) ) === $expected );

$other = $work. '/other';
mkdir( $other );
file_put_contents( $other. '/catalogue.json', json_encode( [ 'format' => 1, 'generated' => '2026-01-01T00:00:00Z', 'packages' => [] ] ) );
[ $status, , $stderr ] = buildTree( $tree, $other );
check( 'a catalogue of another format in the directory is not built over, and nothing is written beside it', $status === 1 && str_contains( $stderr, 'is not a format 2 catalogue' ) === true && array_keys( filesOf( $other ) ) === [ 'catalogue.json' ] );

echo "\n";


// --- C. What the build refuses -------------------------------------------------

echo "bin/build.php - what stops the build, with the path of the file\n";

/**
 *	A copy of the tree with one change made to it, built into a directory of its own
 *
 *	@param		callable	$change				( string $tree ): void
 *
 *	@return 	array											[ status, stderr, what was written ]
 */
$refusal = static function( callable $change ) use ( $tree, $work ): array {

	static $count = 0;

	$copy	= $work. '/case-'. ++$count;
	$into	= $copy. '-out';

	copyTree( $tree, $copy );
	$change( $copy );

	[ $status, , $stderr ] = buildTree( $copy, $into );

	\Nino\Filesystem::removeDir( $copy );

	return [ $status, $stderr, filesOf( $into ) ];
};

$craft = '/themes/craft-probe';
$pack = '/blocks/gastro';

$cases = [
	// the three of a theme, and what the kernel says of its neighbours
	[ 'a theme with a php file'							, 'themes/craft-probe/parts/section/x.php'						, static function( string $tree ) use ( $craft ): void { file_put_contents( $tree. $craft. '/parts/section/x.php', '<?php echo 1;' ); } ],
	[ 'a theme with an @import'							, 'themes/craft-probe/parts/section/part.css'					, static function( string $tree ) use ( $craft ): void { file_put_contents( $tree. $craft. '/parts/section/part.css', "@import 'other.css';\n". file_get_contents( $tree. $craft. '/parts/section/part.css' ) ); } ],
	[ 'a theme that reads a token of another group'	, 'themes/craft-probe/parts/section/part.css'	, static function( string $tree ) use ( $craft ): void { file_put_contents( $tree. $craft. '/parts/section/part.css', ".section-wrap { color: var( --header-wrap-ink ); }\n" ); } ],
	[ 'a theme that sets a token of the page'		, 'themes/craft-probe/parts/section/part.css'	, static function( string $tree ) use ( $craft ): void { file_put_contents( $tree. $craft. '/parts/section/part.css', ".section-wrap { --page-size: 20px; }\n" ); } ],
	// the rules of the build itself
	[ 'a theme that names a group no feature brings'	, 'themes/craft-probe/parts/cards'							, static function( string $tree ) use ( $craft ): void { mkdir( $tree. $craft. '/parts/cards' ); file_put_contents( $tree. $craft. '/parts/cards/part.json', '{ "contract": "cards@^1.0" }' ); } ],
	[ 'a theme without a picture'						, 'themes/craft-probe'														, static function( string $tree ) use ( $craft ): void { unlink( $tree. $craft. '/preview.webp' ); } ],
	[ 'a folder that is not the key of its package'	, 'themes/craft/theme.json'													, static function( string $tree ) use ( $craft ): void { rename( $tree. $craft, $tree. '/themes/craft' ); } ],
	[ 'a package of blocks with a block with [html]'	, 'blocks/gastro/menu'													, static function( string $tree ) use ( $pack ): void { file_put_contents( $tree. $pack. '/menu/block.tpl', "[section-wrap id=\"menu\" image=\"~background\"]\n\t[html]<b>x</b>[/html]\n\t[image ~photo alt=\"\"]\n[/section-wrap]" ); } ],
	[ 'a svg that loses an attribute in the rebuild'	, 'blocks/gastro/menu/images/background.svg'						, static function( string $tree ) use ( $pack ): void { file_put_contents( $tree. $pack. '/menu/images/background.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="#f00" onclick="x()"/></svg>' ); } ],
	[ 'a svg that loses an element in the rebuild'	, 'blocks/gastro/menu/images/background.svg'						, static function( string $tree ) use ( $pack ): void { file_put_contents( $tree. $pack. '/menu/images/background.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="#f00"/><script>alert(1)</script></svg>' ); } ],
	[ 'a file that is no file of a package of blocks'	, 'blocks/gastro/menu/.htaccess'										, static function( string $tree ) use ( $pack ): void { file_put_contents( $tree. $pack. '/menu/.htaccess', 'Options +ExecCGI' ); } ],
	[ 'an application that names a feature nobody has'	, 'website'																, static function( string $tree ): void { file_put_contents( $tree. '/applications/website.php', str_replace( "'themes'", "'nobody'", (string) file_get_contents( $tree. '/applications/website.php' ) ) ); } ],
	[ 'an application that lists a feature before the feature it requires'	, 'applications/website.php'		, static function( string $tree ): void {
		file_put_contents( $tree. '/features/Collections/feature.php', str_replace( "'requires'		=> [ 'basis' ],", "'requires'		=> [ 'basis', 'themes' ],", (string) file_get_contents( $tree. '/features/Collections/feature.php' ) ) );
		file_put_contents( $tree. '/applications/website.php', str_replace( "'themes'", "'collections', 'themes'", (string) file_get_contents( $tree. '/applications/website.php' ) ) );
	} ],
	[ 'a feature with a file the reader of the kernel refuses'	, 'features/Themes/.hidden.php'							, static function( string $tree ): void { file_put_contents( $tree. '/features/Themes/.hidden.php', '<?php' ); } ],
	[ 'a feature that does not validate'			, 'features/Broken'														, static function( string $tree ): void { mkdir( $tree. '/features/Broken' ); file_put_contents( $tree. '/features/Broken/feature.php', "<?php\nreturn 'not a manifest';\n" ); file_put_contents( $tree. '/features/Broken/Broken.php', "<?php\nnamespace Nino\\Modules { class Broken {} }\n" ); } ],
];

$notRefused = [];

foreach( $cases as [ $name, $path, $change ] ) {

	[ $status, $stderr, $written ] = $refusal( $change );

	if( $status !== 1 || str_contains( $stderr, $path ) === false || $written !== [] )
		$notRefused[] = $name. ' ('. $status. ( str_contains( $stderr, $path ) === true ? '' : ', no path' ). ( $written === [] ? '' : ', wrote '. implode( ' ', array_keys( $written ) ) ). ')';
}

check( 'each of '. count( $cases ). ' cases stops the build with exit 1 and a sentence that names the file - '. implode( '; ', array_column( $cases, 0 ) ). ' - and no file is written, not an archive and not the index'. ( $notRefused === [] ? '' : ' - NOT: '. implode( ', ', $notRefused ) ), $notRefused === [] );

[ $status, , $stderr ] = runScript( $tree. '/bin/build.php', [] );
$usage = $status === 2 && str_contains( $stderr, 'Usage: php bin/build.php' ) === true;
[ $status, , $stderr ] = runScript( $tree. '/bin/build.php', [ $work. '/nowhere', $work. '/usage-out' ] );
$usage = $usage && $status === 2 && str_contains( $stderr, 'No Nino checkout' ) === true;
[ $status, , $stderr ] = runScript( $tree. '/bin/build.php', [ $root, $work. '/usage-out', '--base-url', 'http://catalogue.test/packages' ] );
$usage = $usage && $status === 2 && str_contains( $stderr, 'https' ) === true;

$bare = $work. '/bare';
mkdir( $bare. '/_nino', 0755, true );
file_put_contents( $bare. '/_nino/Nino.php', "<?php\nnamespace Nino { const VERSION = '1.1.0'; final class Features {} }\n" );
[ $status, , $stderr ] = runScript( $tree. '/bin/build.php', [ $bare, $work. '/usage-out' ] );
[ $previewStatus, , $previewError ] = runScript( $tree. '/bin/catalogue.php', [ $bare ] );

check( 'what is not a build is a usage error, exit 2, before anything is created: no arguments, no Nino checkout, a base url that is not https, and a checkout that predates the packages - for the build and for the preview',
	$usage === true && $status === 2 && $previewStatus === 2 && str_contains( $stderr, 'catalogue format 2' ) === true && str_contains( $previewError, 'catalogue format 2' ) === true && is_dir( $work. '/usage-out' ) === false );

[ $status, $stdout, $stderr ] = runScript( $tree. '/bin/catalogue.php', [ $root ] );
$preview = (array) json_decode( $stdout, true );

check( 'the preview lists what the build would: the same entries without archive, digest and picture, the applications above them',
	$status === 0 && ( $delivers === true ? str_contains( $stderr, 'features/Basis: delivered by the kernel' ) === true : $stderr === '' ) && array_map( static fn( array $entry ): array => array_diff_key( $entry, [ 'archive' => 1, 'sha256' => 1, 'size' => 1, 'released' => 1, 'preview' => 1 ] ), $document['packages'] ) === $preview['packages'] && $preview['applications'] === $document['applications'] );

echo "\n";

// --- D. From the built directory to a project -------------------------------------

echo "\\Nino\\Catalogue - the built theme and package of blocks installed through the client's stub\n";

$appData = ninoSandbox( 'build' );
$appData['/nino/modules']					= [ '\\Nino\\Modules\\Basis' ];

// The groups of the parts of the theme are read against the register as it stands when the theme comes, and the features it brings are placed in that same step: Basis - where the kernel
// does not deliver it, the folder below the features/ of the project - is there before the theme
$delivers === true || copyTree( $repo. '/features/Basis', NINO_FEATURES_DIR. '/Basis', [ 'tests' ] );
$appData['/nino/catalogue/url']		= $baseUrl. '/catalogue.json';
$appData['/nino/catalogue/key']		= $publicPem;

\Nino\Features::registerComponents( $appData );

$served = [];

$appData['./nino/fetch/stub'] = static function( string $url, array $options ) use ( $baseUrl, $out, &$served ): array {

	$served[] = $url;
	$file = $out. '/'. substr( $url, strlen( $baseUrl ) + 1 );

	if( str_starts_with( $url, $baseUrl. '/' ) === false || is_file( $file ) === false )
		return [ 'ok' => false, 'status' => 404, 'error' => 'http 404' ];

	return [ 'ok' => true, 'status' => 200, 'body' => (string) file_get_contents( $file ) ];
};

$themed		= \Nino\Catalogue::install( $appData, 'theme', 'craft-probe', '1.0.0' );
$placed		= array_map( static fn( array $one ): string => $one['kind']. ':'. $one['key']. '@'. $one['version'], (array) ( $appData['./nino/catalogue/placed'] ?? [] ) );
$blocked	= \Nino\Catalogue::install( $appData, 'blocks', 'gastro', '1.0.0' );

check( 'the theme installs, with the features it requires that the project does not have - the features first, as the entry says - and the package of blocks with nothing else to bring',
	$themed === true && end( $placed ) === 'theme:craft-probe@1.0.0' && array_slice( $placed, 0, -1 ) === [ 'feature:collections@1.0.0', 'feature:themes@1.0.0' ] && $blocked === true && is_file( NINO_FEATURES_DIR. '/Themes/feature.php' ) === true
	&& \Nino\Packages::installed( $appData, 'theme' ) === [ 'craft-probe' => '1.0.0' ] && \Nino\Packages::installed( $appData, 'blocks' ) === [ 'gastro' => '1.0.0' ] );

$installed = filesOf( \Nino\Packages::dir( $appData, 'blocks', 'gastro' ) );

check( 'what lies in the project is the package the build read: the files of the folder, but for the svg, which is the rebuild of the kernel and not the file it was given; the theme as the folder it was built from, down to the last file',
	array_keys( $installed ) === array_keys( filesOf( $tree. '/blocks/gastro' ) ) && $installed['menu/images/background.svg'] === \Nino\Images::cleanSvg( $svg ) && $installed['menu/images/background.svg'] !== $svg && $installed['menu/images/1-photo.png'] === $png
	&& filesOf( \Nino\Packages::dir( $appData, 'theme', 'craft-probe' ) ) === filesOf( $tree. '/themes/craft-probe' ) );

echo "\n";

ninoDone( $appData );
