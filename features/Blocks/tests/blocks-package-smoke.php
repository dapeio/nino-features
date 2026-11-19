<?php
declare(strict_types=1);

/**
 *	Nino features
 *	blocks-package-smoke.php	Contract test for the export and the import of own blocks as a package of the catalogue's form:
 *
 *															A. the export: the form of the package, what the kernel takes of it, the file and its headers, what is refused
 *															B. the import: the preview that writes nothing, each of the three choices where a key is taken, the first block that fails
 *															C. what may not come in: every archive the reader refuses, the bytes that are held, the right and the token, what a block may not hold
 *															D. there and back: a package that is imported on a second site is the same blocks, file for file
 *
 *	Usage: php features/Blocks/tests/blocks-package-smoke.php
 *				 NINO_ROOT=../nino php features/Blocks/tests/blocks-package-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Blocks\Package;

function packCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body, $request ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, is_string( $body ) === true ? $body : json_decode( (string) json_encode( $body ), true ), $request ];
}

/** A site with the components of the kernel and the blocks of the Blocks feature, and an account that may use it */
function packSite( string $name ): array {

	global $root;

	$site = ninoSandbox( $name );
	$site['./nino/filesystem/path'] = $root;
	$site['/nino/html/assets'] = [];
	$site['/nino/html/navs'] = [ 'main' ];
	$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Blocks' ];
	\Nino\Html::init( $site );
	\Nino\Features::registerComponents( $site );
	\Nino\Modules::callModules( $site, 'init' );
	\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
	\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
	\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );

	foreach( [ 'de_DE', 'en_US' ] as $locale )
		\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => 'Hallo' ] );

	$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];
	ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Blocks::PERM ] );

	return $site;
}

/** Every file below a folder, path => hash of its bytes, in the order of the paths */
function packFiles( string $dir ): array {

	$out = [];

	if( is_dir( $dir ) === false )
		return $out;

	$walk = static function( string $path, string $below ) use ( &$walk, &$out ): void {

		foreach( (array) scandir( $path ) as $entry ) {

			if( $entry === '.' || $entry === '..' )
				continue;

			if( is_dir( $path. '/'. $entry ) === true )
				$walk( $path. '/'. $entry, $below. $entry. '/' );
			else
				$out[$below. $entry] = hash_file( 'sha256', $path. '/'. $entry );
		}
	};

	$walk( $dir, '' );
	ksort( $out );

	return $out;
}

/** An archive the way a tool that does not care writes it: ustar entries of [ name, bytes, type ], gzip over them */
function packRaw( array $entries ): string {

	$tar = '';

	foreach( $entries as [ $name, $bytes, $type ] ) {

		$head = str_pad( $name, 100, "\0" ). sprintf( '%07o', $type === '5' ? 0755 : 0644 ). "\0". sprintf( '%07o', 0 ). "\0". sprintf( '%07o', 0 ). "\0". sprintf( '%011o', strlen( $bytes ) ). "\0". sprintf( '%011o', 0 ). "\0". '        '. $type
			. str_pad( $type === '2' ? 'target' : '', 100, "\0" ). "ustar\0". '00'. str_pad( '', 32, "\0" ). str_pad( '', 32, "\0" ). str_pad( '', 16, "\0" ). str_pad( '', 155, "\0" );
		$head = str_pad( $head, 512, "\0" );
		$sum = 0;

		foreach( str_split( $head ) as $char )
			$sum += ord( $char );

		$head = substr( $head, 0, 148 ). sprintf( '%06o', $sum ). "\0 ". substr( $head, 156 );
		$tar .= $head. $bytes. str_repeat( "\0", ( 512 - strlen( $bytes ) % 512 ) % 512 );
	}

	return gzencode( $tar. str_repeat( "\0", 1024 ), 9 );
}

/** Upload a file the way php hands it over: a name of its own in the sandbox, and the entry of $_FILES */
function packUpload( array &$appData, string $bytes, string $name = 'blocks.tar.gz' ): array {

	$tmp = ninoSandboxDir( $appData ). '/upload-'. bin2hex( random_bytes( 4 ) ). '.tmp';
	file_put_contents( $tmp, $bytes );
	$_FILES['file'] = [ 'name' => $name, 'type' => 'application/gzip', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];

	$answer = packCall( $appData, 'blocks/import-check' );

	unset( $_FILES['file'] );
	@unlink( $tmp );

	return $answer;
}

$a = packSite( 'blocks-package-a' );
$dirA = ninoSandboxDir( $a ). '/private/blocks';
$_SESSION = [];

// Two blocks of the project: a copy of a block of the kernel that has variants, a picture place and words in two languages - with a picture of its own - and a new one
[ , $made ] = packCall( $a, 'blocks/create', [ 'name' => [ 'de_DE' => 'Mein Split', 'en_US' => 'My split' ], 'category' => 'content', 'from' => 'split-image-text' ] );
$first = (string) ( $made['key'] ?? '' );
[ , $made ] = packCall( $a, 'blocks/create', [ 'name' => [ 'de_DE' => 'Leerer Block', 'en_US' => 'Empty block' ], 'category' => 'intro' ] );
$second = (string) ( $made['key'] ?? '' );
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"><script>alert(2)</script><rect width="10" height="10" fill="#cccccc" onclick="alert(3)"/></svg>';
$cleanSvg = (string) \Nino\Images::cleanSvg( $svg );
@mkdir( $dirA. '/'. $first. '/images', 0755, true );
file_put_contents( $dirA. '/'. $first. '/images/picture.svg', $svg );
\Nino\Blocks::forget( $a );

echo "\nA. The export\n";

$given = [ 'keys' => [ $first, $second ], 'key' => 'my-blocks', 'version' => '1.0.0', 'name' => 'Meine Blocks', 'description' => 'Zwei Blocks.' ];
[ $status, $bytes, $request ] = packCall( $a, 'blocks/export', $given );
$limits = \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS];
$files = is_string( $bytes ) === true ? \Nino\Archive::read( $bytes, 'my-blocks', 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) : 'none';
$paths = is_array( $files ) === true ? array_keys( $files ) : [];
$header = $request['/nino/http/response']['header'] ?? [];

check( 'an export is a package of the catalogue\'s form: one folder named for its key with a pack.json and, for each block, its folder with block.json, block.tpl, its texts, and the picture of its own - and nothing else; no LICENSE.txt, which the project has no file for',
	$status === 200 && is_array( $files ) === true && in_array( 'pack.json', $paths, true ) === true && in_array( $first. '/block.json', $paths, true ) === true && in_array( $first. '/block.tpl', $paths, true ) === true
	&& in_array( $first. '/images/picture.svg', $paths, true ) === true && in_array( $first. '/text/de_DE.json', $paths, true ) === true && in_array( $second. '/block.json', $paths, true ) === true
	&& in_array( 'LICENSE.txt', $paths, true ) === false && array_filter( $paths, static fn( string $path ): bool => preg_match( '~^(?:pack\.json|'. preg_quote( $first, '~' ). '/|'. preg_quote( $second, '~' ). '/)~', $path ) !== 1 ) === [] );

$pack = is_array( $files ) === true ? (array) json_decode( $files['pack.json'], true ) : [];

check( 'the pack.json says the key, the version, the name and the description - the checked text of the request, and nothing else',
	$pack === [ 'key' => 'my-blocks', 'version' => '1.0.0', 'name' => 'Meine Blocks', 'description' => 'Zwei Blocks.' ] );

check( 'the package is what the kernel takes as a package of blocks, and the bytes of each file are the bytes of the file in the folder of the block',
	is_array( $files ) === true && \Nino\Packages::check( $a, \Nino\Packages::BLOCKS, $files ) === null
	&& array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), array_filter( $files, static fn( string $path ): bool => str_starts_with( $path, $first. '/' ), ARRAY_FILTER_USE_KEY ) ) === array_combine( array_map( static fn( string $path ): string => $first. '/'. $path, array_keys( packFiles( $dirA. '/'. $first ) ) ), array_values( array_replace( packFiles( $dirA. '/'. $first ), [ 'images/picture.svg' => hash( 'sha256', $cleanSvg ) ] ) ) ) );

check( 'a svg leaves in the package as the picture that cleanSvg() rebuilds of it - never as the file lies - with no script, no event attribute: the project\'s file is as it came, the package has the cleaned one',
	$cleanSvg !== '' && $cleanSvg !== $svg && ( $files[$first. '/images/picture.svg'] ?? '' ) === $cleanSvg && preg_match( '/script|onload|onclick|alert/i', (string) ( $files[$first. '/images/picture.svg'] ?? '' ) ) === 0
	&& file_get_contents( $dirA. '/'. $first. '/images/picture.svg' ) === $svg );

check( 'the download is a file the kernel answers as one (Http::download): the type of a gzip, an attachment named for the key and the version, no cache, no sniffing, the length of the bytes, and the bytes are not drawn as a page - they are a gzip stream of a tar',
	( $header['Content-Type'] ?? '' ) === 'application/gzip' && ( $header['Content-Disposition'] ?? '' ) === 'attachment; filename="my-blocks-1.0.0.tar.gz"' && ( $header['Cache-Control'] ?? '' ) === 'private, no-store'
	&& ( $header['X-Content-Type-Options'] ?? '' ) === 'nosniff' && is_string( $bytes ) === true && str_starts_with( $bytes, "\x1f\x8b" ) === true
	&& ( $header['Content-Length'] ?? '' ) === (string) strlen( $bytes ) && ( $request['/nino/http/response']['raw'] ?? false ) === true );

[ $dry, $said ] = packCall( $a, 'blocks/export', $given + [ 'check' => true ] );

check( 'a request that only asks whether the package can be made says so in the words of the panel: the file, its size, the number of blocks - and sends no file',
	$dry === 200 && ( $said['file'] ?? '' ) === 'my-blocks-1.0.0.tar.gz' && ( $said['size'] ?? 0 ) === strlen( (string) $bytes ) && ( $said['blocks'] ?? 0 ) === 2 );

// a licence the project has of its own goes in; none is made up
file_put_contents( $dirA. '/LICENSE.txt', "Meine Lizenz\n" );
[ , $withLicence ] = packCall( $a, 'blocks/export', $given );
$licenced = is_string( $withLicence ) === true ? \Nino\Archive::read( $withLicence, 'my-blocks', 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) : [];
unlink( $dirA. '/LICENSE.txt' );

check( 'the LICENSE.txt of the folder of the own blocks is in the package where the project has one - the one file that is added, and it is the project\'s own',
	is_array( $licenced ) === true && ( $licenced['LICENSE.txt'] ?? '' ) === "Meine Lizenz\n" );

$refused = [];

foreach( [
	'a block of the kernel'					=> [ [ 'keys' => [ 'hero-full' ] ] + $given, 404 ],
	'a key that is no block'				=> [ [ 'keys' => [ 'nothing-here' ] ] + $given, 404 ],
	'a path as a key of a block'		=> [ [ 'keys' => [ '../'. $first ] ] + $given, 404 ],
	'no block'											=> [ [ 'keys' => [] ] + $given, 400 ],
	'a list that is none'						=> [ [ 'keys' => 'x' ] + $given, 400 ],
	'a key with a line break'				=> [ [ 'key' => "a\r\nSet-Cookie: x=1" ] + $given, 400 ],
	'a key that is a path'					=> [ [ 'key' => '../x' ] + $given, 400 ],
	'a key of capitals'							=> [ [ 'key' => 'MyBlocks' ] + $given, 400 ],
	'a version of two numbers'			=> [ [ 'version' => '1.0' ] + $given, 400 ],
	'a version with a quote'				=> [ [ 'version' => '1.0.0"; x=' ] + $given, 400 ],
	'a name with markup'						=> [ [ 'name' => '<b>x</b>' ] + $given, 400 ],
	'an empty name'									=> [ [ 'name' => ' ' ] + $given, 400 ],
	'a description with a key'			=> [ [ 'description' => 'see [[/x]]' ] + $given, 400 ],
] as $why => [ $data, $expected ] ) {

	[ $status, $body, $request ] = packCall( $a, 'blocks/export', $data );
	$refused[$why] = $status === $expected && is_array( $body ) === true && str_starts_with( (string) ( $body['code'] ?? '' ), 'blocks_' ) === true && isset( $request['/nino/http/response']['header']['Content-Disposition'] ) === false;
}

check( 'what may not go into a package is refused with a sentence of its own and no file: a block that is not the project\'s, a key or a version or a name that is none, a line break or a path in them - nothing of the request is in a header',
	array_filter( $refused, static fn( bool $ok ): bool => $ok === false ) === [] );

$sayings = [ 'de_DE' => require dirname( __DIR__ ). '/text/de_DE.php', 'en_US' => require dirname( __DIR__ ). '/text/en_US.php' ];
$codes = array_merge(
	array_map( static fn( string $code ): string => 'blocks_import_archive_'. $code, [ 'gzip', 'bomb', 'trailing', 'truncated', 'header', 'type', 'pax', 'path', 'depth', 'folder', 'duplicate', 'name', 'count', 'file-size', 'total-size', 'unsupported', 'refused' ] ),
	array_map( static fn( string $code ): string => 'blocks_package_'. $code, [ 'empty', 'files-many', 'bytes-many', 'path', 'twice', 'not-allowed', 'missing', 'long', 'text', 'json', 'file-key', 'file-slug', 'file-version', 'file-words', 'file-map', 'block', 'preview', 'refused', 'loop' ] ),
	array_map( static fn( string $code ): string => 'blocks_export_'. $code, [ 'key', 'version', 'name', 'description', 'none', 'unknown', 'read', 'archive', 'large' ] ),
	[ 'blocks_import_large', 'blocks_import_empty', 'blocks_import_unknown', 'blocks_import_nofile', 'blocks_import_words', 'blocks_package_block_place', 'blocks_package_block_text', 'blocks_package_block_source' ]
);
$unsaid = array_filter( $codes, static fn( string $code ): bool => isset( $sayings['de_DE']['[[/_admin/error/'. $code. ']]'], $sayings['en_US']['[[/_admin/error/'. $code. ']]'] ) === false );

check( 'every refusal of the export and the import has its sentence in both languages: each code the reader of archives has, each of the rule of the packages, each of the export',
	$unsaid === [] );

echo "\nB. The import\n";

$b = packSite( 'blocks-package-b' );
$dirB = ninoSandboxDir( $b ). '/private/blocks';
$sandboxB = ninoSandboxDir( $b );
$exported = (string) $bytes;

[ $status, $preview ] = packUpload( $b, $exported );
$held = $_SESSION[Package::SESSION] ?? [];
$byKey = [];

foreach( $preview['blocks'] ?? [] as $one )
	$byKey[$one['key']] = $one;

check( 'a package that was chosen is read, checked and shown before anything is written: the key of the preview, the name of the package, and for each block its name, category, variants and picture places - and the folder of the blocks of the project is as it was',
	$status === 200 && preg_match( '/^[0-9a-f]{32}$/D', (string) ( $preview['id'] ?? '' ) ) === 1 && ( $preview['pack']['key'] ?? '' ) === 'my-blocks' && ( $preview['pack']['version'] ?? '' ) === '1.0.0' && count( $byKey ) === 2
	&& ( $byKey[$first]['name'] ?? '' ) === 'Mein Split' && ( $byKey[$first]['category'] ?? '' ) === 'content' && count( $byKey[$first]['variants'] ?? [] ) === 2 && ( $byKey[$first]['images'] ?? 0 ) === 1 && ( $byKey[$first]['state'] ?? '' ) === 'free'
	&& ( $byKey[$second]['state'] ?? '' ) === 'free' && packFiles( $dirB ) === [] && ( $held['id'] ?? '' ) === ( $preview['id'] ?? 'x' ) );

// the same keys are taken on the second site: a block of the project of that key, a block of the kernel of that key, and one that is free
[ , $made ] = packCall( $b, 'blocks/create', [ 'name' => [ 'de_DE' => 'Mein Split', 'en_US' => 'My split' ], 'category' => 'trust' ] );
$takenKey = (string) ( $made['key'] ?? '' );
$board = static function( array $names ) use ( $files ): array {

	$out = [];

	foreach( $names as $to => $from )
		foreach( $files as $path => $content )
			if( str_starts_with( $path, $from. '/' ) === true )
				$out[$to. substr( $path, strlen( $from ) )] = $content;

	return $out;
};
$mix = $board( [ $takenKey => $first, 'hero-full' => $second, 'fresh-one' => $second ] ) + [ 'pack.json' => json_encode( [ 'key' => 'mix', 'version' => '1.2.3', 'name' => 'Mix', 'description' => 'Mix' ] ). "\n" ];
$mixBytes = \Nino\Archive::write( 'mix', $mix, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] );
$atTaken = hash( 'sha256', (string) file_get_contents( $dirB. '/'. $takenKey. '/block.json' ) );

[ , $shown ] = packUpload( $b, (string) $mixBytes );
$states = [];

foreach( $shown['blocks'] ?? [] as $one )
	$states[$one['key']] = [ $one['state'], $one['replace'] ];

check( 'where a key is taken the preview says so, in every source of the library: of an own block - which may be replaced - of a block of the kernel or a theme, which may not',
	( $states[$takenKey] ?? [] ) === [ 'taken', true ] && ( $states['hero-full'] ?? [] ) === [ 'taken', false ] && ( $states['fresh-one'] ?? [] ) === [ 'free', false ] );

$choose = static function( array $items ) use ( &$b, $mixBytes ): array {

	packUpload( $b, (string) $mixBytes );
	$id = (string) ( $_SESSION[Package::SESSION]['id'] ?? '' );
	[ $status, $body ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => $items ] );

	return [ $status, array_column( $body['results'] ?? [], 'result', 'key' ), $body ];
};

// the choice "skip": the default of a key that is taken
[ $one, $results ] = $choose( [ $takenKey => [ 'take' => true, 'how' => 'skip' ], 'hero-full' => [ 'take' => true, 'how' => 'skip' ], 'fresh-one' => [ 'take' => true ] ] );

$freshWant = array_filter( $mix, static fn( string $path ): bool => str_starts_with( $path, 'fresh-one/' ) && $path !== 'fresh-one/block.tpl', ARRAY_FILTER_USE_KEY );
$freshGot = packFiles( $dirB. '/fresh-one' );
$freshTpl = (string) file_get_contents( $dirB. '/fresh-one/block.tpl' );

check( 'a block of a free key is written as it is in the package - every file the bytes of the file in it but the template, whose root has the id of the key it is written under - and a key that is taken is skipped by the choice that is the default: the block that has it is not touched',
	$one === 200 && $results === [ 'fresh-one' => 'written', 'hero-full' => 'skipped', $takenKey => 'skipped' ]
	&& array_diff_key( $freshGot, [ 'block.tpl' => 1 ] ) === array_combine( array_map( static fn( string $path ): string => substr( $path, strlen( 'fresh-one/' ) ), array_keys( $freshWant ) ), array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), array_values( $freshWant ) ) )
	&& str_contains( $freshTpl, 'id="fresh-one"' ) === true && str_contains( $freshTpl, 'id="'. $second. '"' ) === false
	&& hash( 'sha256', (string) file_get_contents( $dirB. '/'. $takenKey. '/block.json' ) ) === $atTaken );

// the choice "as a copy": a key of its own, a name that is no other's
[ $two, $copied ] = $choose( [ $takenKey => [ 'take' => true, 'how' => 'copy' ] ] );
$copyKey = $takenKey. '-2';
$was = array_filter( $mix, static fn( string $path ): bool => str_starts_with( $path, $takenKey. '/' ), ARRAY_FILTER_USE_KEY );
$now = packFiles( $dirB. '/'. $copyKey );
$unchanged = 0;

foreach( $was as $path => $content )
	if( ( $now[substr( $path, strlen( $takenKey ) + 1 )] ?? '' ) === hash( 'sha256', $content ) )
		$unchanged++;

$copyNames = (array) ( \Nino\Blocks::all( $b )[$copyKey]['name'] ?? [] );

check( 'the choice of a copy writes the block under a key of its own - the key with a number - and the changes it makes are the name - with the word for a copy, so that it is no other block\'s - and the id of the root, which follows the key: the files but block.json and block.tpl are the bytes of the package',
	$two === 200 && ( $copied[$takenKey] ?? '' ) === 'copied' && $unchanged === count( $was ) - 2 && str_contains( (string) file_get_contents( $dirB. '/'. $copyKey. '/block.tpl' ), 'id="'. $copyKey. '"' ) === true && count( $now ) === count( $was ) && ( $copyNames['en_US'] ?? '' ) === 'My split copy' && ( $copyNames['de_DE'] ?? '' ) === 'Mein Split Kopie'
	&& hash( 'sha256', (string) file_get_contents( $dirB. '/'. $takenKey. '/block.json' ) ) === $atTaken );

// the choice "replace": only the block of the project, and then it is the package's block
[ $three, $replaced ] = $choose( [ $takenKey => [ 'take' => true, 'how' => 'replace' ] ] );
$after = packFiles( $dirB. '/'. $takenKey );
$replacedLike = $after === array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), array_combine( array_map( static fn( string $path ): string => substr( $path, strlen( $takenKey ) + 1 ), array_keys( $was ) ), array_values( $was ) ) );

check( 'the choice to replace takes the package\'s block over the block of the project that has the key: every file is the one of the package afterwards, and what the old block had that the package has not is gone',
	$three === 200 && ( $replaced[$takenKey] ?? '' ) === 'replaced' && $replacedLike === true && hash( 'sha256', (string) file_get_contents( $dirB. '/'. $takenKey. '/block.json' ) ) !== $atTaken );

// the first block that cannot be written ends it: what was written stays, and the message says which one was missing and why
$partial = $board( [ 'aa-new' => $second, 'hero-full' => $second, 'zz-new' => $second ] ) + [ 'pack.json' => $mix['pack.json'] ];
$partialBytes = \Nino\Archive::write( 'mix', $partial, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] );
packUpload( $b, (string) $partialBytes );
$id = (string) ( $_SESSION[Package::SESSION]['id'] ?? '' );
[ $stopped, $report ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => [ 'aa-new' => [ 'take' => true ], 'hero-full' => [ 'take' => true, 'how' => 'replace' ], 'zz-new' => [ 'take' => true ] ] ] );
$by = array_column( $report['results'] ?? [], null, 'key' );

check( 'the first block that cannot be written ends the import: the ones before it stay, the one that failed is named with its reason in the words of the panel - a block of the kernel is no block to replace - and the ones behind it are said to be untried and are not written',
	$stopped === 200 && ( $by['aa-new']['result'] ?? '' ) === 'written' && ( $by['hero-full']['result'] ?? '' ) === 'failed' && ( $by['hero-full']['code'] ?? '' ) === 'block' && ( $by['hero-full']['message'] ?? '' ) !== '' && ( $by['zz-new']['result'] ?? '' ) === 'untried'
	&& is_dir( $dirB. '/aa-new' ) === true && file_exists( $dirB. '/zz-new' ) === false && file_exists( $dirB. '/hero-full' ) === false );

echo "\nC. What may not come in\n";

$whole = static fn( array &$site ): array => packFiles( ninoSandboxDir( $site ) );
$base = packFiles( $dirB );
$entry = static fn( string $name, string $content = '', string $type = '0' ): array => [ $name, $content, $type ];
$pack = $entry( 'evil/pack.json', json_encode( [ 'key' => 'evil', 'version' => '1.0.0', 'name' => 'Evil', 'description' => 'Evil' ] ). "\n" );
$tpl = $entry( 'evil/b/block.tpl', (string) ( $files[$second. '/block.tpl'] ?? '' ) );
$json = $entry( 'evil/b/block.json', (string) ( $files[$second. '/block.json'] ?? '' ) );
$folder = $entry( 'evil/', '', '5' );
$many = [];

for( $at = 1; $at <= 1700; $at++ )
	$many[] = $entry( 'evil/b/images/p'. $at. '.svg', 'x' );

$bomb = gzencode( packRaw( [ $folder, $pack ] ) );

$archives = [
	'a path that climbs'								=> packRaw( [ $folder, $pack, $entry( 'evil/../x.json', '{}' ) ] ),
	'a link'														=> packRaw( [ $folder, $pack, $entry( 'evil/link', '', '2' ) ] ),
	'a file that is too large'					=> packRaw( [ $folder, $pack, $json, $tpl, $entry( 'evil/b/images/picture.svg', str_repeat( 'x', \Nino\Blocks::MAX_PICTURE + 1 ) ) ] ),
	'too many entries'									=> packRaw( array_merge( [ $folder, $pack ], $many ) ),
	'a php file'												=> packRaw( [ $folder, $pack, $json, $tpl, $entry( 'evil/b/block.php', '<?php echo 1;' ) ] ),
	'an .htaccess'											=> packRaw( [ $folder, $pack, $json, $tpl, $entry( 'evil/.htaccess', 'Options +ExecCGI' ) ] ),
	'a hidden file'											=> packRaw( [ $folder, $pack, $json, $tpl, $entry( 'evil/b/.hidden', 'x' ) ] ),
	'a stream that unpacks to more than it may' => gzencode( packRaw( [ $folder, $pack, $entry( 'evil/b/images/big.svg', str_repeat( "\0", 400000 ) ) ] ). str_repeat( str_repeat( "\0", 1048576 ), 30 ), 9 ),
	'an archive that is cut off'				=> substr( $exported, 0, intdiv( strlen( $exported ), 2 ) ),
	'no gzip at all'										=> 'this is not an archive',
	'a file beside the folder'					=> packRaw( [ $entry( 'pack.json', '{}' ) ] ),
];

$why = [];

foreach( $archives as $name => $data ) {

	unset( $_SESSION[Package::SESSION] );
	$before = $whole( $b );
	[ $status, $body ] = packUpload( $b, $data );
	$why[$name] = $status === 400 && is_array( $body ) === true && preg_match( '/^blocks_(?:import|package)_/', (string) ( $body['code'] ?? '' ) ) === 1 && isset( $_SESSION[Package::SESSION] ) === false && $whole( $b ) === $before && packFiles( $dirB ) === $base;
}

check( 'what the reader of archives and the rule of the packages refuse is refused with a sentence of its own, nothing is written and nothing is held - a path that climbs, a link, a file that is too large, too many entries, a php file, an .htaccess, a hidden file, a bomb, an archive that is cut off, no gzip, a file beside the folder - and no rest of the uploaded file is left',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

$why = [];

foreach( [
	'a free html'												=> [ $second, static fn( string $tpl ): string => "[section-wrap id=\"x\"]\n[html]<b>x</b>[/html]\n[/section-wrap]\n", 'tpl' ],
	'a script in a name'								=> [ $second, static fn( string $json ): string => (string) str_replace( 'Empty block', '<script>alert(1)</script>', $json ), 'json' ],
	'a text that is foreign'						=> [ $second, static fn( string $tpl ): string => "[section-wrap id=\"x\"]\n[text ~foreign]\n[/section-wrap]\n", 'tpl' ],
] as $name => [ $block, $change, $part ] ) {

	$changed = $files;
	$changed[$block. '/block.'. $part] = $change( $files[$block. '/block.'. $part] );
	$data = \Nino\Archive::write( 'my-blocks', $changed, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] );
	[ $status, $body ] = packUpload( $b, (string) $data );
	$why[$name] = $status === 400 && in_array( $body['code'] ?? '', [ 'blocks_package_block', 'blocks_package_block_place', 'blocks_package_block_text', 'blocks_package_block_source' ], true ) && isset( $_SESSION[Package::SESSION] ) === false && packFiles( $dirB ) === $base;
}

$changed = $files;
$changed[$second. '/block.tpl'] = "[section-wrap id=\"x\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[collection /team]\n\t\t\t[/collection]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n";
[ $status, $body ] = packUpload( $b, (string) \Nino\Archive::write( 'my-blocks', $changed, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) );
$why['a real type of elements'] = $status === 400 && ( $body['code'] ?? '' ) === 'blocks_package_loop' && packFiles( $dirB ) === $base;

check( 'a block the editor would not save is not imported: free html, a script in a name, a text that is not its own - refused as the kernel refuses a block - and a block of a real type of elements with the sentence of the editor; nothing is written',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

// the bytes that are held: for the session that asked, for a quarter of an hour, and read and checked again when they are written
packUpload( $b, $exported );
$id = (string) $_SESSION[Package::SESSION]['id'];
$mine = $_SESSION;
$_SESSION = [];
[ $foreign ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => [ $first => [ 'take' => true ] ] ] );
$_SESSION = $mine;
[ $wrong ] = packCall( $b, 'blocks/import', [ 'id' => str_repeat( '0', 32 ), 'items' => [ $first => [ 'take' => true ] ] ] );
$_SESSION[Package::SESSION]['until'] = time() - 1;
[ $expired ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => [ $first => [ 'take' => true ] ] ] );
$gone = isset( $_SESSION[Package::SESSION] ) === false;

check( 'the bytes of a package that was shown are held for the session that sent them and for a quarter of an hour: another session, a key that was not given and a key that has run out are each a 403, and nothing is written',
	$foreign === 403 && $wrong === 403 && $expired === 403 && $gone === true && packFiles( $dirB ) === $base );

// the right of the panel is the right of the import: an account of the Builder without it is refused - for the upload and for the write, whatever it holds
packUpload( $b, $exported );
$id = (string) $_SESSION[Package::SESSION]['id'];
ninoUser( $b, 'builder-only@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ $noWrite ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => [ $first => [ 'take' => true ] ] ] );
unset( $_SESSION[Package::SESSION] );
[ $noUpload ] = packUpload( $b, $exported );
ninoUser( $b, 'blocks@example.com', [ \Nino\Modules\Blocks::PERM ] );

check( 'an account that has the Builder and not the right of the Blocks is refused, 403, at the check of a package and at the writing of one - and nothing is written and nothing is held',
	$noWrite === 403 && $noUpload === 403 && packFiles( $dirB ) === $base && isset( $_SESSION[Package::SESSION] ) === false );

packUpload( $b, $exported );
$id = (string) $_SESSION[Package::SESSION]['id'];
$_SESSION[Package::SESSION]['bytes'] = (string) $archives['a php file'];
[ $altered ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => [ $first => [ 'take' => true ] ] ] );
packUpload( $b, $exported );
$id = (string) $_SESSION[Package::SESSION]['id'];
$_SESSION[Package::SESSION]['bytes'] = (string) $archives['a php file'];
$_SESSION[Package::SESSION]['sum'] = hash( 'sha256', (string) $archives['a php file'] );
[ $again, $body ] = packCall( $b, 'blocks/import', [ 'id' => $id, 'items' => [ $first => [ 'take' => true ] ] ] );

check( 'what is written is the package that is read and checked again from the held bytes - no trust in what was shown: bytes that are no longer the ones that were held are a 403, and held bytes that the reader refuses are refused at the write, with nothing written and nothing held',
	$altered === 403 && $again === 400 && preg_match( '/^blocks_(?:import|package)_/', (string) ( $body['code'] ?? '' ) ) === 1 && packFiles( $dirB ) === $base && isset( $_SESSION[Package::SESSION] ) === false );

echo "\nD. There and back\n";

$c = packSite( 'blocks-package-c' );
$dirC = ninoSandboxDir( $c ). '/private/blocks';
packUpload( $c, $exported );
$id = (string) $_SESSION[Package::SESSION]['id'];
[ $backStatus, $back ] = packCall( $c, 'blocks/import', [ 'id' => $id, 'items' => [ $first => [ 'take' => true ], $second => [ 'take' => true ] ] ] );

check( 'a package that is imported on a second, fresh site is the same blocks: every file of each block - the block, its template, its texts in both languages, its variants, its picture place - is the same file, byte for byte, and the picture of its own is the cleaned svg; nothing else is changed where no key and no name is taken',
	$backStatus === 200 && array_column( $back['results'] ?? [], 'result' ) === [ 'written', 'written' ] && packFiles( $dirC. '/'. $first ) === array_replace( packFiles( $dirA. '/'. $first ), [ 'images/picture.svg' => hash( 'sha256', $cleanSvg ) ] ) && packFiles( $dirC. '/'. $second ) === packFiles( $dirA. '/'. $second ) && count( packFiles( $dirC. '/'. $first ) ) >= 5 );

$again = \Nino\Blocks::get( $c, $first );
$source = \Nino\Blocks::get( $a, $first );

check( 'the blocks that came in are the kernel\'s blocks of the project: the variants, the picture place and the picture of its own are read from the files - the same names, labels and places as on the first site',
	is_array( $again ) === true && array_keys( (array) $again['alternatives'] ) === array_keys( (array) $source['alternatives'] ) && (array) $again['images'] === (array) $source['images'] && (array) ( $again['pictures'] ?? [] ) === (array) ( $source['pictures'] ?? [] ) && (array) $again['pictures'] !== []
	&& ( \Nino\Blocks::all( $c )[$first]['origin'] ?? '' ) === 'project' );

[ , $reexport ] = packCall( $c, 'blocks/export', [ 'keys' => [ $first, $second ], 'key' => 'my-blocks', 'version' => '1.0.0', 'name' => 'Meine Blocks', 'description' => 'Zwei Blocks.' ] );
$roundtrip = is_string( $reexport ) === true ? \Nino\Archive::read( $reexport, 'my-blocks', 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) : 'none';

check( 'and back again: the package made on the second site holds the same files as the one made on the first',
	$roundtrip == $files );

// the picture of its own belongs to the block: the details and the save of the editor write the block anew and take it along
$onDisk = (string) file_get_contents( $dirC. '/'. $first. '/images/picture.svg' );

// a package that holds a svg as it came - the script and the event attribute in it - is written with the cleaned picture, never the bytes that came
$nasty = $files;
$nasty[$first. '/images/picture.svg'] = $svg;
[ $nastyStatus ] = packUpload( $c, (string) \Nino\Archive::write( 'my-blocks', $nasty, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) );
$nastyId = (string) ( $_SESSION[Package::SESSION]['id'] ?? '' );
[ $nastyDone ] = packCall( $c, 'blocks/import', [ 'id' => $nastyId, 'items' => [ $first => [ 'take' => true, 'how' => 'replace' ] ] ] );
$written = (string) file_get_contents( $dirC. '/'. $first. '/images/picture.svg' );

check( 'a svg that a package holds as it came is written as the picture that cleanSvg() rebuilds of it: on the disk of the block there is no script and no event attribute, whatever the package had in it',
	$nastyStatus === 200 && $nastyDone === 200 && $written === $cleanSvg && $written === $onDisk && preg_match( '/script|onload|onclick|alert/i', $written ) === 0 );

// what the project wrote before the rule was held is cleaned at the next save: the raw file on the first site is replaced when the block is written anew
[ $cleaned ] = packCall( $a, 'blocks/rename', [ 'key' => $first, 'name' => [ 'de_DE' => 'Mein Split', 'en_US' => 'My split' ] ] );

check( 'a svg that lies in a block of the project as it came is cleaned when the block is written anew: the details of the block replace it by the cleaned picture',
	$cleaned === 200 && file_get_contents( $dirA. '/'. $first. '/images/picture.svg' ) === $cleanSvg );

$picture = packFiles( $dirC. '/'. $first )['images/picture.svg'] ?? '';
[ $renamed ] = packCall( $c, 'blocks/rename', [ 'key' => $first, 'name' => [ 'de_DE' => 'Mein Split', 'en_US' => 'My split' ], 'rank' => 7 ] );
$afterRename = packFiles( $dirC. '/'. $first )['images/picture.svg'] ?? 'gone';
[ , $draft ] = packCall( $c, 'blocks/open', [ 'key' => $first ] );
[ $savedAgain ] = packCall( $c, 'blocks/save', [ 'key' => $first, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => $draft['stamp'] ] );
$afterSave = packFiles( $dirC. '/'. $first )['images/picture.svg'] ?? 'gone';

check( 'the picture that a block has of its own stays where the block is written anew: a change of its details and a save of the editor - the slot is read still - leave images/picture.svg as it was, byte for byte',
	$picture !== '' && $renamed === 200 && $savedAgain === 200 && $afterRename === $picture && $afterSave === $picture );

echo "\nE. Blocks of one form on a page\n";

$laid = static fn( array &$site, string $key ): string => rtrim( \Nino\Markup::write( \Nino\Markup::layout( [ \Nino\Modules\Builder\Library::block( $site, $key )['tree'][0] ], 0 ), \Nino\Components::containers( $site ) ) );
$label = static fn( string $de, string $en ): array => [ 'de_DE' => $de, 'en_US' => $en ];
$varied = [
	[ 'name' => 'image-left', 'label' => $label( 'Bild links', 'Picture left' ), 'set' => [] ],
	[ 'name' => 'image-right', 'label' => $label( 'Bild rechts', 'Picture right' ), 'set' => [ '1' => [ 'reverse' => 'on' ] ] ],
	[ 'name' => 'quiet', 'label' => $label( 'Leise', 'Quiet' ), 'set' => [ 'root' => [ 'ground' => 'tint' ] ] ],
];

/** The blocks on one page, saved and opened anew: for each the block it is told to be, and the names of its variants - and what the Builder does when it is asked to set one */
$alongside = static function( array &$site, string $template, array $keys, array $ask ) use ( $laid ): array {

	$html = '';

	foreach( $keys as $key ) {

		$html .= $laid( $site, $key ). "\n";
		$site['/nino/html/images']['/template/'. $template. '/'. \Nino\Modules\Builder\Library::block( $site, $key )['id']. '/picture'] = [ 'label' => 'Picture', 'width' => 800, 'height' => 600, 'filename' => null ];
	}

	\Nino\Filesystem::putFileContent( $site, '/templates/'. $template. '.tpl', "<!-- nino:template-name Mixed -->\n[template /templates/html-header]\n". $html. "[template /templates/html-footer]\n" );
	$site['/nino/http/routes']['GET://'. $template] = [ 'uri' => '/'. $template, 'body' => '[template /templates/'. $template. ']' ];
	ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );

	[ , $opened ] = packCall( $site, 'builder/open', [ 'template' => $template ] );
	$out = [ 'variants' => array_map( static fn( array $one ): array => [ $one['key'], array_column( $one['items'], 0 ) ], (array) ( $opened['variants'] ?? [] ) ), 'asked' => [] ];

	foreach( $ask as $label => [ $at, $name ] ) {

		$tree = $opened['tree'];
		$places = array_keys( array_filter( $tree, static fn( array $one ): bool => ( $one['n'] ?? '' ) === 'section-wrap' ) );
		[ $status ] = packCall( $site, 'builder/variant', [ 'template' => $template, 'tree' => $tree, 'at' => (string) $places[$at], 'name' => $name ] );
		$out['asked'][$label] = $status;
	}

	ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Blocks::PERM ] );

	return $out;
};

// the block of the kernel and a copy of it that has a variant of its own, side by side on a page that was saved and is opened anew: each is told to be its own block
[ , $open ] = packCall( $a, 'blocks/open', [ 'key' => $first ] );
packCall( $a, 'blocks/save', [ 'key' => $first, 'tree' => $open['tree'], 'texts' => $open['texts'], 'base' => $open['stamp'], 'variants' => $varied ] );
$both = $alongside( $a, 'page-both', [ 'split-image-text', $first ], [ 'the copy\'s variant at the block of the kernel' => [ 0, 'quiet' ], 'the copy\'s variant at the copy' => [ 1, 'quiet' ], 'a variant of the kernel at the block of the kernel' => [ 0, 'image-right' ] ] );

check( 'a page that has the block of the kernel and a copy of it that has a variant of its own, saved and opened anew, tells each block to be its own: the one offers its two variants, the copy its three - and the Builder sets the variant of the block that stands there and of no other',
	$both['variants'] === [ [ 'split-image-text', [ 'image-left', 'image-right' ] ], [ $first, [ 'image-left', 'image-right', 'quiet' ] ] ]
	&& $both['asked'] === [ 'the copy\'s variant at the block of the kernel' => 403, 'the copy\'s variant at the copy' => 200, 'a variant of the kernel at the block of the kernel' => 200 ] );

// a copy that is saved under a name from the editor has the id of its own key, too - and stands beside the block of the kernel and the first copy, each told to be its own
[ , $open ] = packCall( $a, 'blocks/open', [ 'key' => $first ] );
[ $savedAs, $savedBody ] = packCall( $a, 'blocks/saveas', [ 'key' => $first, 'name' => $label( 'Gesichert', 'Saved' ), 'tree' => $open['tree'], 'texts' => $open['texts'] ] );
$asKey = (string) ( $savedBody['key'] ?? '' );
$asId = $asKey === '' ? '' : \Nino\Modules\Builder\Library::block( $a, $asKey )['id'];
$three = $asKey === '' ? [] : $alongside( $a, 'page-three', [ 'split-image-text', $first, $asKey ], [ 'the variant of the first copy at the second' => [ 2, 'quiet' ] ] );

check( 'a block that is saved under a name from the editor has the id of its own key at its root; on a page with the block of the kernel and two copies - saved and opened anew - each is told to be its own block',
	$savedAs === 200 && $asId === $asKey && $asKey !== $first && count( $three['variants'] ?? [] ) === 3 && array_column( $three['variants'], 0 ) === [ 'split-image-text', $first, $asKey ] );

// a copy that came in by a package has the id of its own key, so that it can be told from the block it is a copy of
packCall( $c, 'blocks/save', [ 'key' => $first, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'base' => packCall( $c, 'blocks/open', [ 'key' => $first ] )[1]['stamp'], 'variants' => $varied ] );
packUpload( $c, $exported );
[ $copyStatus, $copied ] = packCall( $c, 'blocks/import', [ 'id' => (string) $_SESSION[Package::SESSION]['id'], 'items' => [ $first => [ 'take' => true, 'how' => 'copy' ], $second => [ 'take' => false ] ] ] );
$copyKey = (string) ( array_column( $copied['results'] ?? [], 'as', 'key' )[$first] ?? '' );
$copyId = $copyKey === '' ? '' : \Nino\Modules\Builder\Library::block( $c, $copyKey )['id'];
$pair = $copyKey === '' ? [] : $alongside( $c, 'page-pair', [ $first, $copyKey ], [ 'the variant of the original at the copy' => [ 1, 'quiet' ], 'the variant of the original at the original' => [ 0, 'quiet' ] ] );

check( 'a copy that is imported has the id of its own key at its root, the original keeps its own, and on a page that has both - saved and opened anew - each offers its own variants and the Builder sets the variant of the block that stands there',
	$copyStatus === 200 && $copyKey === $first. '-2' && $copyId === $copyKey && ( $pair['variants'] ?? [] ) === [ [ $first, [ 'image-left', 'image-right', 'quiet' ] ], [ $copyKey, [ 'image-left', 'image-right' ] ] ]
	&& ( $pair['asked'] ?? [] ) === [ 'the variant of the original at the copy' => 403, 'the variant of the original at the original' => 200 ] );

echo "\nF. What is held, what is said\n";

// the bytes that are held are let go of: by a request that comes when their time is out - whatever it is - by a newer preview, by the one who leaves the preview, and by the import
packUpload( $b, $exported );
$one = (string) $_SESSION[Package::SESSION]['id'];
packUpload( $b, $exported );
$two = (string) $_SESSION[Package::SESSION]['id'];
[ $replacedOne ] = packCall( $b, 'blocks/import', [ 'id' => $one, 'items' => [ $first => [ 'take' => true ] ] ] );

check( 'one package is held for a session, and a newer preview takes the place of the older: the key of the older is a 403 and nothing is written; and the keys are made of chance - the same bytes sent twice have two keys, and the key is no hash of them',
	$replacedOne === 403 && $one !== $two && $one !== substr( hash( 'sha256', $exported ), 0, 32 ) && $two !== substr( hash( 'sha256', $exported ), 0, 32 ) && preg_match( '/^[0-9a-f]{32}$/D', $two ) === 1 && $two !== str_repeat( '0', 32 ) && packFiles( $dirB ) === $base );

[ $wrongDrop ] = packCall( $b, 'blocks/import-drop', [ 'id' => str_repeat( 'a', 32 ) ] );
$stillHeld = isset( $_SESSION[Package::SESSION] ) === true;
[ $drop ] = packCall( $b, 'blocks/import-drop', [ 'id' => $two ] );

check( 'a preview that is left is let go of: the key that was held lets go of the bytes, a key that is not held lets go of nothing',
	$wrongDrop === 200 && $stillHeld === true && $drop === 200 && isset( $_SESSION[Package::SESSION] ) === false );

packUpload( $b, $exported );
$_SESSION[Package::SESSION]['until'] = time() - 1;
packCall( $b, 'blocks/list', [ 'tab' => 'project' ] );
$swept = isset( $_SESSION[Package::SESSION] ) === false;
packUpload( $b, $exported );
packCall( $b, 'blocks/list', [ 'tab' => 'project' ] );

check( 'bytes whose time is out are let go of by the next request of the session, whatever it asks for - and bytes that are still held are not',
	$swept === true && isset( $_SESSION[Package::SESSION] ) === true );

packCall( $b, 'blocks/import', [ 'id' => (string) $_SESSION[Package::SESSION]['id'], 'items' => [ $second => [ 'take' => true ] ] ] );

check( 'what was written lets go of what was held for it: the import ends the hold, whatever it wrote', isset( $_SESSION[Package::SESSION] ) === false );

// the package is read back as the files it was made of
check( 'an archive is the files it was made of only where a reader takes back exactly them: the same bytes, another folder, another file and a missing file are told apart',
	Package::same( $exported, 'my-blocks', $files, 2 ) === true && Package::same( $exported, 'other', $files, 2 ) === false && Package::same( $exported, 'my-blocks', array_replace( $files, [ 'pack.json' => '{}' ] ), 2 ) === false
	&& Package::same( $exported, 'my-blocks', array_diff_key( $files, [ 'pack.json' => 1 ] ), 2 ) === false );

// a package that the rule of the catalogue's packages would refuse is not handed out: more files than a package of blocks may have
$crowd = packSite( 'blocks-package-crowd' );
$dirCrowd = ninoSandboxDir( $crowd ). '/private/blocks';
$more = [];

for( $at = 1; $at <= 100; $at++ ) {

	$name = 'crowd'. $at;
	@mkdir( $dirCrowd. '/'. $name. '/text', 0755, true );

	foreach( packFiles( $dirA. '/'. $second ) as $path => $hash )
		file_put_contents( $dirCrowd. '/'. $name. '/'. $path, (string) file_get_contents( $dirA. '/'. $second. '/'. $path ) );

	$more[] = $name;
}

\Nino\Blocks::forget( $crowd );
[ $crowded, $why ] = packCall( $crowd, 'blocks/export', [ 'keys' => $more, 'key' => 'crowd', 'version' => '1.0.0', 'name' => 'Crowd' ] );

check( 'a package that holds more files than a package of blocks may have is not handed out: a hundred blocks of four files are refused by the rule of the catalogue\'s packages, with its sentence and no file',
	count( packFiles( $dirCrowd ) ) >= 400 && $crowded === 400 && ( $why['code'] ?? '' ) === 'blocks_package_files-many' );

echo "\nG. What is said of a refusal\n";

[ $emptyStatus, $empty ] = packUpload( $b, '' );

check( 'an empty file says that it is empty - and not that it is too large', $emptyStatus === 400 && ( $empty['code'] ?? '' ) === 'blocks_import_nofile' );

$reasons = [];

foreach( [
	'a call where the kernel does not allow it'	=> [ $second. '/block.tpl', "[section-wrap id=\"x\"]\n[text ~foreign]\n[/section-wrap]\n", 'blocks_package_block_place' ],
	'words with markup'													=> [ $second. '/block.json', str_replace( 'Empty block', '<b>x</b>', (string) $files[$second. '/block.json'] ), 'blocks_package_block_text' ],
] as $why => [ $path, $content, $code ] ) {

	$changed = $files;
	$changed[$path] = $content;
	[ $status, $body ] = packUpload( $b, (string) \Nino\Archive::write( 'my-blocks', $changed, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) );
	$reasons[$why] = $status === 400 && ( $body['code'] ?? '' ) === $code && ( $body['params'][0] ?? '' ) === $second && ( $body['params'][1] ?? 'text' ) !== 'text' && ( $body['params'][1] ?? '' ) !== '' && str_contains( (string) ( $body['params'][1] ?? '' ), 'register' ) === false;
}

check( 'a block that the kernel refuses is refused in words: which block, and what - the call that stands where it may not, or the words that hold what they may not - not in the sentence of the kernel',
	array_filter( $reasons, static fn( bool $ok ): bool => $ok === false ) === [] );

$words = [];

foreach( [ 'name' => 'Pack <b>x</b>', 'description' => 'see [[/x]]' ] as $field => $text ) {

	$changed = $files;
	$changed['pack.json'] = json_encode( array_replace( (array) json_decode( $files['pack.json'], true ), [ $field => $text ] ) ). "\n";
	[ $status, $body ] = packUpload( $b, (string) \Nino\Archive::write( 'my-blocks', $changed, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] ) );
	$words[$field] = $status === 400 && ( $body['code'] ?? '' ) === 'blocks_import_words' && isset( $_SESSION[Package::SESSION] ) === false;
}

check( 'the import holds the name and the description of a package to the words the export holds them to: markup and a key of the project are refused, nothing is held',
	array_filter( $words, static fn( bool $ok ): bool => $ok === false ) === [] );

ninoDone( $a );
ninoDone( $b );
ninoDone( $c );
