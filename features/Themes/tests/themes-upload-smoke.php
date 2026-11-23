<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themes-upload-smoke.php	Contract test for the theme package that is chosen in the panel of the Themes - read in memory, shown, put in place, taken away:
 *
 *														A. the preview: what a package holds is told before anything is written, and the bytes are held
 *														B. in place: the theme is in the list as an uploaded one and not applied, its blocks are the kernel's; the rule of a key that is taken
 *														C. what may not come in: every archive, path, kind of package and text the rule refuses, and what is left out of what comes in is said
 *														D. the bytes that are held, the right and the token, and the picture of a block that a script is in
 *														E. away: not while the website shows it or an own theme takes parts from it; there and back through the existing way of applying
 *														L. an own theme as a package, to save - after the whole check - and a package as an own theme: a copy of its folder, nothing installed, nothing applied
 *
 *	Usage: php features/Themes/tests/themes-upload-smoke.php
 *				 NINO_ROOT=../nino php features/Themes/tests/themes-upload-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

// What the feature reads from the disk is watched from the first request on: a function of the namespace of the feature that is called first is the one every later call finds
$spy = sys_get_temp_dir(). '/themes-upload-spy-'. getmypid(). '.php';
file_put_contents( $spy, "<?php\nnamespace Nino\\Modules\\Themes {\n\tfunction file_get_contents( string \$filename, bool \$use_include_path = false, \$context = null, int \$offset = 0, ?int \$length = null ): string|false {\n\t\t\$GLOBALS['spied'][] = \$filename;\n\t\treturn \\file_get_contents( \$filename, \$use_include_path, \$context, \$offset, \$length );\n\t}\n}\n" );
require $spy;
unlink( $spy );
$spied = [];

use Nino\Modules\Themes\Mix;
use Nino\Modules\Themes\Upload;

const UP_BUNDLE = '/.cache/style.css';

/** A site as a sandbox of its own: the kernel's components and Basis, the feature, and an account that holds the right of the panel */
function upSite(): array {

	global $root;

	$appData = ninoSandbox( 'themes-upload' );
	$appData['./nino/filesystem/path'] = $root;
	$appData['/nino/html/assets'] = [ UP_BUNDLE => [ '/_nino/Nino.css' ], '/.cache/script.js' => [ '/_nino/Nino.js' ] ];
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Themes' ];
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Features::registerParts( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	ninoUser( $appData, 'designer@example.com', [ \Nino\Modules\Themes::PERM ] );

	return $appData;
}

function upCall( array &$appData, string $action, array $data = [], int $before = 200 ): array {

	[ $status, $body, $request ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data + [ 'locale' => 'en_US' ], $action, $before );

	return [ $status, is_string( $body ) === true ? $body : json_decode( (string) json_encode( $body ), true ), $request ];
}

/** Choose a file the way php hands it over: a file of its own in the sandbox, and the entry of $_FILES */
function upChoose( array &$appData, string $bytes, string $name = 'theme.tar.gz' ): array {

	$tmp = ninoSandboxDir( $appData ). '/upload-'. bin2hex( random_bytes( 4 ) ). '.tmp';
	file_put_contents( $tmp, $bytes );
	$_FILES['file'] = [ 'name' => $name, 'type' => 'application/gzip', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];

	$answer = upCall( $appData, 'themes/upload-check' );

	unset( $_FILES['file'] );
	@unlink( $tmp );

	return $answer;
}

/** The key of what is held in the session */
function upHeld(): string {
	return (string) ( $_SESSION[Upload::SESSION]['id'] ?? '' );
}

/** Every file below a folder, path => hash of its bytes */
function upFiles( string $dir ): array {

	$out = [];

	if( is_dir( $dir ) === false )
		return $out;

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( $file->isFile() === true )
			$out[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );

	ksort( $out );

	return $out;
}

/** What a package could change of a project: the packages, the blocks, the stylesheet, the own themes and the state of the themes - and not the log, the locks and the cache a request writes whatever it does */
function upWritten( array &$appData ): array {

	return array_filter( upFiles( ninoSandboxDir( $appData ) ), static fn( string $path ): bool => preg_match( '~^/(?:private/packages|private/blocks|private/assets|assets|private/themes|private/data/theme/(?:current|previous|state|copies|uploads\.json))~', $path ) === 1, ARRAY_FILTER_USE_KEY );
}

/** An archive the way a tool that does not care writes it: ustar entries of [ name, bytes, type ], gzip over them */
function upRaw( array $entries ): string {

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

$limits = \Nino\Packages::LIMITS[\Nino\Packages::THEME];

/** The package: a theme of its own colours, a part for the button, a block with a picture place and a frame, and the licence - made of the files of the fixtures and of the kernel */
function upFiles2( string $key = 'second-look', string $version = '1.2.0' ): array {

	global $root;

	$fixture = __DIR__. '/fixtures/themes/craft-probe';
	$basis = $root. '/_nino/Nino/Modules/Basis/blocks';
	$files = [
		'theme.json'							=> json_encode( [ 'key' => $key, 'version' => $version, 'name' => [ 'de_DE' => 'Zweiter Blick', 'en_US' => 'Second look' ], 'description' => [ 'de_DE' => 'Warm und grün.', 'en_US' => 'Warm and green.' ],
			'brand' => [ 'color-1' => '#8a2b0d', 'color-2' => '#1d3b2f', 'tone' => 'warm', 'font-title' => 'lato-demo' ] ], JSON_UNESCAPED_UNICODE ),
		'parts/button/part.json'	=> (string) file_get_contents( $fixture. '/parts/button/part.json' ),
		'parts/button/part.css'		=> (string) file_get_contents( $fixture. '/parts/button/part.css' ),
		'LICENSE.txt'							=> "Free to use <b>as is</b>\n",
		'README.md'								=> "# Second look\n",
		'preview.webp'						=> (string) base64_decode( 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==' ),
	];

	foreach( [ 'split-image-text' => 'second-split', 'footer-columns' => 'second-foot' ] as $from => $to )
		foreach( [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json' ] as $name )
			$files['blocks/'. $to. '/'. $name] = (string) file_get_contents( $basis. '/'. $from. '/'. $name );

	return $files;
}

/** A theme of one part and no block: the version and the shadow the part gives the buttons say which package it is */
function upLight( string $key, string $version, string $shadow = '.15' ): array {

	$fixture = __DIR__. '/fixtures/themes/craft-probe';

	return [
		'theme.json'							=> json_encode( [ 'key' => $key, 'version' => $version, 'name' => [ 'de_DE' => 'Zweiter Blick', 'en_US' => 'Second look' ], 'description' => 'Light.' ] ),
		'parts/button/part.json'	=> (string) file_get_contents( $fixture. '/parts/button/part.json' ),
		'parts/button/part.css'		=> str_replace( '.15', $shadow, (string) file_get_contents( $fixture. '/parts/button/part.css' ) ),
	];
}

/** A theme that is put in place and is not wanted for itself: applying it moves the state on, so that the last state is no longer the one before */
function upSpare( array &$site ): void {

	upChoose( $site, upPack( upLight( 'spare', '1.0.0' ) ) );
	upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
}

/** A theme that is delivered at steps of its knobs, with a stylesheet of its own and a frame that reads a text of the site - and a block of the pages that reads none */
function upRich( string $key, string $version ): array {

	global $root;

	$fixture = __DIR__. '/fixtures/themes/craft-probe';
	$basis = $root. '/_nino/Nino/Modules/Basis/blocks';
	$page = (array) json_decode( (string) file_get_contents( $fixture. '/parts/page/part.json' ), true );
	$page['values'] = [ 'size' => 1, 'width' => 0 ];
	$files = [
		'theme.json'							=> json_encode( [ 'key' => $key, 'version' => $version, 'name' => [ 'de_DE' => 'Reich', 'en_US' => 'Rich' ], 'description' => 'Steps, css and a frame.' ] ),
		'theme.css'								=> ".theme-probe { letter-spacing: .01em; }\n.theme-probe .theme-probe-inner { margin: 0; }\n",
		'parts/button/part.json'	=> json_encode( [ 'contract' => 'basis@^1.0', 'ladders' => [ 'button.radius' => [ '1px', '2px', '3px', '4px', '5px' ] ], 'values' => [ 'button.radius' => 2, 'button.space' => -1, 'button.size' => 0 ] ] ),
		'parts/page/part.json'		=> json_encode( $page ),
	];

	foreach( [ 'footer-columns' => 'rich-foot', 'split-image-text' => 'rich-split' ] as $from => $to )
		foreach( [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json' ] as $name )
			$files['blocks/'. $to. '/'. $name] = (string) file_get_contents( $basis. '/'. $from. '/'. $name );

	// The frame reads the name of the company, which is a text of the site and not of its own
	$files['blocks/rich-foot/block.tpl'] = str_replace( '[text ~about format="inline" size="s"]', '[text /project/company/general/name size="s"]', $files['blocks/rich-foot/block.tpl'] );

	foreach( [ 'de_DE', 'en_US' ] as $locale ) {

		$words = (array) json_decode( $files['blocks/rich-foot/text/'. $locale. '.json'], true );
		unset( $words['about'] );
		$files['blocks/rich-foot/text/'. $locale. '.json'] = (string) json_encode( $words, JSON_UNESCAPED_UNICODE );
	}

	return $files;
}

/** A theme as a folder of the hook of the project: what is in it is read as it lies, and no rule of the packages has looked at it */
function upHook( array &$site, string $key, array $files ): void {

	static $registered = [];

	$root = ninoSandboxDir( $site ). '/hook-themes';

	foreach( $files as $path => $bytes ) {
		@mkdir( dirname( $root. '/'. $key. '/'. $path ), 0755, true );
		file_put_contents( $root. '/'. $key. '/'. $path, $bytes );
	}

	if( isset( $registered[$root] ) === false ) {

		\Nino\Callbacks::registerCallback( $site, \Nino\Modules\Themes\Reader::ROOTS, static function( array &$appData, array $roots ) use ( $root ): array { return array_merge( $roots, [ $root ] ); } );
		$registered[$root] = true;
	}

	unset( $site['./nino/themes/read'] );
}

function upPack( array $files, string $folder = 'second-look' ): string {

	global $limits;

	$packed = \Nino\Archive::write( $folder, $files, 3 * $limits['files'], \Nino\Blocks::MAX_PICTURE, $limits['bytes'] );

	return is_string( $packed ) === true ? $packed : '';
}

echo "\nA. The preview\n";

$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
$files = upFiles2();
$bytes = upPack( $files );
$before = upWritten( $site );
[ $status, $preview ] = upChoose( $site, $bytes );
$theme = $preview['theme'] ?? [];
$after = upWritten( $site );

check( 'the package that was chosen is shown before anything is written: key, version, name and description in the language asked for, the picture, the licence as it is, the number of parts, of blocks and of frames, and the font the brand names that the project has not - and the files of the project are as they were',
	$status === 200 && ( $theme['key'] ?? '' ) === 'second-look' && ( $theme['version'] ?? '' ) === '1.2.0' && ( $theme['name'] ?? '' ) === 'Second look' && ( $theme['description'] ?? '' ) === 'Warm and green.'
	&& str_starts_with( (string) ( $theme['picture'] ?? '' ), 'data:image/webp;base64,' ) === true && ( $theme['licence'] ?? '' ) === $files['LICENSE.txt'] && count( $theme['parts'] ?? [] ) === 1 && count( $theme['blocks'] ?? [] ) === 2 && ( $theme['frames'] ?? 0 ) === 1
	&& ( $theme['fonts'] ?? [] ) === [ 'lato-demo' ] && array_key_exists( 'installed', $theme ) === true && $theme['installed'] === null && $after === $before && is_dir( $dir ) === false );

check( 'the bytes are held for the session that sent them, under a key nobody can guess, for a quarter of an hour - and the preview says for how long',
	preg_match( '/^[0-9a-f]{32}$/D', (string) ( $preview['id'] ?? '' ) ) === 1 && upHeld() === ( $preview['id'] ?? 'x' ) && ( $preview['seconds'] ?? 0 ) === Upload::HOLD && ( $_SESSION[Upload::SESSION]['until'] ?? 0 ) > time() );

echo "\nB. In place\n";

[ $status, $done ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
[ , $list ] = upCall( $site, 'themes/list' );
$card = array_values( array_filter( $list['themes'] ?? [], static fn( array $one ): bool => $one['key'] === 'second-look' ) )[0] ?? [];
$blocks = \Nino\Blocks::all( $site );

check( 'the package is put in place, and the theme is in the list as one that was uploaded, with its parts, and it is not the theme of the website - applying stays the step it is; the bytes are let go of',
	$status === 200 && ( $done['installed']['key'] ?? '' ) === 'second-look' && is_file( $dir. '/second-look/theme.json' ) === true && ( $card['origin'] ?? '' ) === 'uploaded' && ( $card['active'] ?? true ) === false
	&& array_keys( $card['parts'] ?? [] ) === [ 'button' ] && ( $card['parts']['button']['ok'] ?? false ) === true && array_key_exists( 'active', $list ) === true && $list['active'] === null && isset( $_SESSION[Upload::SESSION] ) === false );

check( 'the blocks of the package are blocks of the kernel with the key of the theme as their origin - the one with a frame among them - and the list of the shipped theme is the one it was',
	( $blocks['second-split']['origin'] ?? '' ) === 'second-look' && ( $blocks['second-foot']['origin'] ?? '' ) === 'second-look' && ( $blocks['second-foot']['category'] ?? '' ) === 'footer'
	&& array_column( array_filter( $list['themes'], static fn( array $one ): bool => $one['kind'] === 'theme' ), 'origin', 'key' ) === [ 'nino' => 'shipped', 'second-look' => 'uploaded' ] );

// the same key again: replaced only where it is asked for
$digest = upFiles( $dir );
$newer = upFiles2( 'second-look', '1.3.0' );
[ , $second ] = upChoose( $site, upPack( $newer ) );
[ $quiet, $no ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$unchanged = upFiles( $dir ) === $digest;
upChoose( $site, upPack( $newer ) );
[ $replaced, $yes ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );

check( 'a theme of that key that was uploaded is shown with its version, and it is replaced only where the person said so: without it the answer is a 409 and the folder is as it was, with it the folder is the new version and the answer says it replaced one',
	( $second['theme']['installed'] ?? [] ) === [ 'origin' => 'uploaded', 'version' => '1.2.0', 'replace' => true ] && $quiet === 409 && ( $no['code'] ?? '' ) === 'themes_upload_exists' && $unchanged === true
	&& $replaced === 200 && ( $yes['installed']['replaced'] ?? false ) === true && ( $yes['installed']['version'] ?? '' ) === '1.3.0' && json_decode( (string) file_get_contents( $dir. '/second-look/theme.json' ), true )['version'] === '1.3.0' );

// a key of the theme that comes with the feature, and a key of a package the catalogue put in place
$shipped = upFiles2( 'nino' );
[ , $shownShipped ] = upChoose( $site, upPack( $shipped ) );
[ $noShipped, $bodyShipped ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
$catalogue = upFiles2( 'from-catalogue' );
\Nino\Packages::place( $site, \Nino\Packages::THEME, 'from-catalogue', $catalogue );
$digest = upFiles( $dir );
[ , $shownCatalogue ] = upChoose( $site, upPack( $catalogue ) );
[ $noCatalogue, $bodyCatalogue ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );

check( 'a theme that comes with the feature and a theme of the catalogue are never replaced by an upload, whatever the person says: the preview says which one is there and a 409 names the reason - and the folders are as they were',
	( $shownShipped['theme']['installed']['origin'] ?? '' ) === 'shipped' && ( $shownShipped['theme']['installed']['replace'] ?? true ) === false && $noShipped === 409 && ( $bodyShipped['code'] ?? '' ) === 'themes_upload_taken_shipped'
	&& ( $shownCatalogue['theme']['installed']['origin'] ?? '' ) === 'catalogue' && $noCatalogue === 409 && ( $bodyCatalogue['code'] ?? '' ) === 'themes_upload_taken_catalogue' && upFiles( $dir ) === $digest );

echo "\nC. What may not come in\n";

$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
$whole = static fn(): array => upWritten( $site );
$base = $whole();
$entry = static fn( string $name, string $content = '', string $type = '0' ): array => [ $name, $content, $type ];
$identity = $entry( 'evil/theme.json', json_encode( [ 'key' => 'evil', 'version' => '1.0.0', 'name' => 'Evil', 'description' => 'Evil' ] ) );
$folder = $entry( 'evil/', '', '5' );
$many = [];

for( $at = 1; $at <= 1300; $at++ )
	$many[] = $entry( 'evil/blocks/b/images/p'. $at. '.svg', 'x' );

$archives = [
	'a file that is no archive'					=> 'this is a text and no archive',
	'an archive that is cut off'				=> substr( $bytes, 0, intdiv( strlen( $bytes ), 2 ) ),
	'a path that climbs'								=> upRaw( [ $folder, $identity, $entry( 'evil/../x.json', '{}' ) ] ),
	'a link'														=> upRaw( [ $folder, $identity, $entry( 'evil/link', '', '2' ) ] ),
	'a file that is too large'					=> upRaw( [ $folder, $identity, $entry( 'evil/blocks/b/images/picture.svg', str_repeat( 'x', \Nino\Blocks::MAX_PICTURE + 1 ) ) ] ),
	'too many entries'									=> upRaw( array_merge( [ $folder, $identity ], $many ) ),
	'a php file'												=> upRaw( [ $folder, $identity, $entry( 'evil/parts/button/part.php', '<?php echo 1;' ) ] ),
	'an .htaccess'											=> upRaw( [ $folder, $identity, $entry( 'evil/.htaccess', 'Options +ExecCGI' ) ] ),
	'a hidden file'											=> upRaw( [ $folder, $identity, $entry( 'evil/parts/.hidden', 'x' ) ] ),
	'a stream that unpacks to more than it may' => gzencode( upRaw( [ $folder, $identity ] ). str_repeat( str_repeat( "\0", 1048576 ), 30 ), 9 ),
	'a file beside the folder'					=> upRaw( [ $entry( 'theme.json', '{}' ) ] ),
	'a second folder'										=> upRaw( [ $folder, $identity, $entry( 'other/theme.json', '{}' ) ] ),
	'a file too large for the upload'		=> str_repeat( 'x', \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME] + 1 ),
];
$why = [];

foreach( $archives as $name => $data ) {

	unset( $_SESSION[Upload::SESSION] );
	[ $status, $body ] = upChoose( $site, $data );
	$why[$name] = $status === 400 && preg_match( '/^themes_upload_(?:archive_|large|package_)[a-z0-9_]*$/D', (string) ( $body['code'] ?? '' ) ) === 1 && isset( $_SESSION[Upload::SESSION] ) === false && $whole() === $base;
}

check( 'what is no theme package - a text, an archive that is cut off, a link, a php file, an .htaccess, a hidden file, a file that is too large, too many entries, a bomb, a file beside the folder, a second folder, an upload over the size - is refused with a code of its own, nothing is held and nothing is written',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

// the same keys in a folder of the hook - a theme that is put there by hand - are refused by the reader, with the reason: a theme that is read has no such key
upHook( $site, 'uploaded', [ 'theme.json' => upLight( 'uploaded', '1.0.0' )['theme.json'] ] );
unset( $site['./nino/themes/read'] );
$handRead = \Nino\Modules\Themes\Reader::read( $site );
check( 'a folder of the hook with a key that a list of the panel uses as a choice is refused with the reason that the key is reserved, and it is no theme of the list',
	isset( $handRead['themes']['uploaded'] ) === false && array_values( array_filter( $handRead['rejected'], static fn( string $reason, string $folder ): bool => basename( $folder ) === 'uploaded' && ( \Nino\Modules\Themes\Reader::explain( $reason )['code'] ?? '' ) === 'key-reserved', ARRAY_FILTER_USE_BOTH ) ) !== [] );

$paths = [
	'a folder that climbs'							=> [ 'parts/../../escape/part.json', '{}' ],
	'a path with a backslash'						=> [ 'parts\\button\\part.json', '{}' ],
	'a path from the root'							=> [ '/etc/part.json', '{}' ],
	'a folder in another spelling'			=> [ 'Parts/button/part.json', '{}' ],
	'a segment of dots'									=> [ 'parts/./button/part.json', '{}' ],
	'a null byte in a name'							=> [ "parts/button/part.json\0.txt", '{}' ],
	'a file in a folder of a block'			=> [ 'blocks/second-split/../../theme.json', '{}' ],
];
$why = [];

foreach( $paths as $name => [ $path, $content ] ) {

	$tricked = $files;
	$tricked[$path] = $content;
	$packed = \Nino\Archive::write( 'second-look', $tricked, 400, \Nino\Blocks::MAX_PICTURE, $limits['bytes'] );
	unset( $_SESSION[Upload::SESSION] );
	$data = is_string( $packed ) === true ? $packed : upRaw( array_merge( [ $entry( 'second-look/', '', '5' ) ], array_map( static fn( string $name ): array => $entry( 'second-look/'. $name, $files[$name] ), array_keys( $files ) ), [ $entry( 'second-look/'. $path, $content ) ] ) );
	[ $status, $body ] = upChoose( $site, $data );
	$why[$name] = $status === 400 && preg_match( '/^themes_upload_(?:archive_|package_)/', (string) ( $body['code'] ?? '' ) ) === 1 && isset( $_SESSION[Upload::SESSION] ) === false && $whole() === $base;
}

check( 'a path that is not a path of plain names - one that climbs, a backslash, one from the root, a folder in another spelling, a segment of dots, a null byte - is refused, nothing is held and nothing is written',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

$blocksPack = upPack( [ 'pack.json' => json_encode( [ 'key' => 'only-blocks', 'version' => '1.0.0', 'name' => 'Blocks', 'description' => 'Blocks' ] ), 'second-split/block.json' => $files['blocks/second-split/block.json'], 'second-split/block.tpl' => $files['blocks/second-split/block.tpl'] ], 'only-blocks' );
$featurePack = upPack( [ 'feature.php' => "<?php\nreturn [ 'key' => 'evil' ];\n", 'Evil.php' => "<?php\necho 'x';\n" ], 'evil' );
$why = [];

foreach( [ 'a package of blocks' => $blocksPack, 'a feature' => $featurePack ] as $name => $data ) {

	unset( $_SESSION[Upload::SESSION] );
	[ $status, $body ] = upChoose( $site, $data );
	$why[$name] = $status === 400 && preg_match( '/^themes_upload_(?:archive_|package_)/', (string) ( $body['code'] ?? '' ) ) === 1 && isset( $_SESSION[Upload::SESSION] ) === false && $whole() === $base;
}

check( 'a package of another kind - the blocks of the catalogue, a feature with php in it - is no theme: it is refused at the check, nothing is held, nothing is written, and no php of it is read',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

$why = [];

foreach( [
	'a tag in the name of the theme'			=> [ 'theme.json', static fn( array $json ): array => [ 'name' => '<script>alert(1)</script>' ] + $json ],
	'a call in the description of the theme'	=> [ 'theme.json', static fn( array $json ): array => [ 'description' => 'see [[/x/y]]' ] + $json ],
	'a tag in the name of a block'				=> [ 'blocks/second-split/block.json', static fn( array $json ): array => [ 'name' => [ 'de_DE' => 'x', 'en_US' => '<img src=x onerror=alert(1)>' ] ] + $json ],
] as $name => [ $path, $change ] ) {

	$changed = $files;
	$changed[$path] = (string) json_encode( $change( (array) json_decode( $files[$path], true ) ) );
	unset( $_SESSION[Upload::SESSION] );
	[ $status, $body ] = upChoose( $site, upPack( $changed ) );
	$why[$name] = $status === 400 && preg_match( '/^themes_upload_package_[a-z0-9_]+$/D', (string) ( $body['code'] ?? '' ) ) === 1 && isset( $_SESSION[Upload::SESSION] ) === false && $whole() === $base;
}

check( 'markup in what is drawn as a name - the name and the description of the theme, the name of a block - is refused before anything is held; and the licence and the readme, which are drawn as text by the panel, come through the preview as the text they are',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] && ( upChoose( $site, $bytes )[1]['theme']['licence'] ?? '' ) === $files['LICENSE.txt'] );

// what the kernel leaves out, and what rests, is said: a part for a later version of its feature rests, a block whose key a block of the kernel has is not the theme's
$left = $files;
$left['parts/button/part.json'] = (string) json_encode( [ 'contract' => 'basis@^9.0' ] + (array) json_decode( $files['parts/button/part.json'], true ) );

foreach( [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json' ] as $name )
	$left['blocks/hero-full/'. $name] = $files['blocks/second-split/'. $name];

$left['theme.json'] = (string) json_encode( [ 'key' => 'leaves-out' ] + (array) json_decode( $files['theme.json'], true ) );
unset( $_SESSION[Upload::SESSION] );
[ , $shown ] = upChoose( $site, upPack( $left ) );
$taken = array_values( array_filter( $shown['theme']['blocks'] ?? [], static fn( array $block ): bool => $block['state'] === 'out' ) );
[ $status, $done ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );

check( 'what the theme brings and the kernel does not take is said, in the preview and after the write - a block whose key another origin has, a part that rests with its reason - and the rest of the theme is in place',
	count( $taken ) === 1 && $taken[0]['key'] === 'hero-full' && $taken[0]['origin'] !== '' && $status === 200 && array_column( $done['installed']['left'] ?? [], 'key' ) === [ 'hero-full' ] && array_column( $done['installed']['parts'] ?? [], 'group' ) === [ 'button' ]
	&& is_array( $done['installed']['parts'][0]['why'] ?? null ) === true && in_array( 'second-split', $done['installed']['blocks'] ?? [], true ) === true && is_file( $dir. '/leaves-out/theme.json' ) === true );

echo "\nD. Held bytes, the right, the token and the picture\n";

$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
$whole = static fn(): array => upWritten( $site );
upChoose( $site, $bytes );
$id = upHeld();
$base = $whole();
$mine = $_SESSION;
$_SESSION = [];
[ $otherSession ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );
$_SESSION = $mine;
ninoUser( $site, 'other@example.com', [ \Nino\Modules\Themes::PERM ] );
[ $otherAccount ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );
[ $otherDrop ] = upCall( $site, 'themes/upload-drop', [ 'id' => $id ] );
$stillHeld = isset( $_SESSION[Upload::SESSION] ) === true;
ninoUser( $site, 'designer@example.com', [ \Nino\Modules\Themes::PERM ] );
[ $wrong ] = upCall( $site, 'themes/upload', [ 'id' => str_repeat( '0', 32 ) ] );
$_SESSION[Upload::SESSION]['until'] = time() - 1;
[ $expired ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );

check( 'the held bytes belong to the session and the account that sent them: another session, another account (which cannot let them go either), a key that was not given and a key that has run out are each a 403, and nothing is written',
	$otherSession === 403 && $otherAccount === 403 && $stillHeld === true && $wrong === 403 && $expired === 403 && isset( $_SESSION[Upload::SESSION] ) === false && $whole() === $base );

upChoose( $site, $bytes );
$id = upHeld();
$_SESSION[Upload::SESSION]['bytes'] = $archives['a php file'];
[ $altered ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );
upChoose( $site, $bytes );
$id = upHeld();
$_SESSION[Upload::SESSION]['bytes'] = $archives['a php file'];
$_SESSION[Upload::SESSION]['sum'] = hash( 'sha256', $archives['a php file'] );
[ $again, $body ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );

check( 'what is written is the package that is read and checked again from the held bytes: bytes that are not the ones that were held are a 403, and held bytes the rule refuses are refused at the write - nothing is written, nothing is held',
	$altered === 403 && $again === 400 && str_starts_with( (string) ( $body['code'] ?? '' ), 'themes_upload_' ) === true && $whole() === $base && isset( $_SESSION[Upload::SESSION] ) === false );

upChoose( $site, $bytes );
$id = upHeld();
[ $noToken ] = upCall( $site, 'themes/upload', [ 'id' => $id ], 403 );
[ $noTokenCheck ] = upCall( $site, 'themes/upload-check', [], 403 );
[ $noTokenRemove ] = upCall( $site, 'themes/remove', [ 'theme' => 'nino' ], 403 );

// the action itself, past the dispatcher of the workbench: each guards itself
$direct = static function( string $method, array $data, int $before ) use ( &$site ): int {
	return ninoCall( $site, [ \Nino\Modules\Themes\Admin::class, $method ], $data, null, $before )[0];
};
$noTokenDirect = [ $direct( 'apiUpload', [ 'id' => $id ], 403 ), $direct( 'apiUploadCheck', [], 403 ), $direct( 'apiUploadDrop', [ 'id' => $id ], 403 ), $direct( 'apiRemove', [ 'theme' => 'nino' ], 403 ) ];

check( 'a request that failed its token - for the check, for the write, for the removal - is refused, as every writing action is, and nothing is written; the held bytes are still there',
	$noToken === 403 && $noTokenCheck === 403 && $noTokenRemove === 403 && $noTokenDirect === [ 403, 403, 403, 403 ] && $whole() === $base && upHeld() === $id );

ninoUser( $site, 'builder@example.com', [ '/_admin/builder/manage', '/_admin/features/manage' ] );
[ $noRight ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );
[ $noCheck ] = upChoose( $site, $bytes );
[ $noRemove ] = upCall( $site, 'themes/remove', [ 'theme' => 'nino' ] );
$noRightDirect = [ $direct( 'apiUpload', [ 'id' => $id ], 200 ), $direct( 'apiUploadCheck', [], 200 ), $direct( 'apiUploadDrop', [ 'id' => $id ], 200 ), $direct( 'apiRemove', [ 'theme' => 'nino' ], 200 ) ];

check( 'an account that has other rights and not the right of the panel is refused, 403, at the check, at the write and at the removal - the right of the panel is the one that is asked, and nothing is written',
	$noRight === 403 && $noCheck === 403 && $noRemove === 403 && $noRightDirect === [ 403, 403, 403, 403 ] && $whole() === $base );

ninoUser( $site, 'designer@example.com', [ \Nino\Modules\Themes::PERM ] );

// the picture of a block that has a script in it: the kernel builds it again when it places it
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"><script>alert(2)</script><rect width="10" height="10" fill="#cccccc" onclick="alert(3)"/></svg>';
$clean = (string) \Nino\Images::cleanSvg( $svg );
$nasty = $files;
$nasty['blocks/second-split/images/picture.svg'] = $svg;
upChoose( $site, upPack( $nasty ) );
[ $status ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$written = (string) @file_get_contents( $dir. '/second-look/blocks/second-split/images/picture.svg' );

check( 'a svg in the picture of a block is written as the picture that cleanSvg() rebuilds of it: on the disk there is no script and no event attribute, whatever the package had in it',
	$status === 200 && $clean !== '' && $clean !== $svg && $written === $clean && preg_match( '/script|onload|onclick|alert/i', $written ) === 0 );

echo "\nE. Away, and there and back\n";

$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
upChoose( $site, $bytes );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
[ $applied ] = upCall( $site, 'themes/apply', [ 'theme' => 'second-look' ] );
[ $refusedActive, $bodyActive ] = upCall( $site, 'themes/remove', [ 'theme' => 'second-look' ] );
$stays = is_file( $dir. '/second-look/theme.json' ) === true;

[ $other ] = upCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
check( 'a theme is not taken away while the website shows it - 409, and the folder is there',
	$applied === 200 && $refusedActive === 409 && ( $bodyActive['code'] ?? '' ) === 'themes_upload_in_use_active' && $stays === true && $other === 200 );

upSpare( $site );
upCall( $site, 'themes/apply', [ 'theme' => 'spare' ] );
[ $removed, $gone ] = upCall( $site, 'themes/remove', [ 'theme' => 'second-look' ] );
$markGone = isset( Upload::marks( $site )['second-look'] ) === false;
[ , $after ] = upCall( $site, 'themes/list' );
$marks = \Nino\Filesystem::getFileContent( $site, Upload::MARKS, [] );

check( 'the way there and back: uploaded, shown, installed, applied through the way that was there, refused while applied, another theme applied, removed - the folder, the blocks, the card and the mark of it are gone, and the theme that comes with the feature is as it was',
	$removed === 200 && ( $gone['removed'] ?? false ) === true && is_dir( $dir. '/second-look' ) === false && isset( \Nino\Blocks::all( $site )['second-split'] ) === false && in_array( 'second-look', array_column( $after['themes'], 'key' ), true ) === false
	&& $markGone === true && array_keys( $marks ) === [ 'spare' ] && ( $after['active']['theme'] ?? '' ) === 'spare' );

// a theme the catalogue put in place and the one that comes with the feature are not taken away here, and what is no theme is a 404
\Nino\Packages::place( $site, \Nino\Packages::THEME, 'from-catalogue', upFiles2( 'from-catalogue' ) );
[ $noCatalogue, $bodyCatalogue ] = upCall( $site, 'themes/remove', [ 'theme' => 'from-catalogue' ] );
[ $noShipped, $bodyShipped ] = upCall( $site, 'themes/remove', [ 'theme' => 'nino' ] );
[ $noSuch, $bodySuch ] = upCall( $site, 'themes/remove', [ 'theme' => '../nino' ] );

check( 'only a theme that was uploaded here is removed here: one of the catalogue and the one that comes with the feature are 409, a key that is no key is a 404 - and their folders are there',
	$noCatalogue === 409 && $noShipped === 409 && ( $bodyCatalogue['code'] ?? '' ) === 'themes_upload_not_uploaded' && ( $bodyShipped['code'] ?? '' ) === 'themes_upload_not_uploaded' && $noSuch === 404 && ( $bodySuch['code'] ?? '' ) === 'themes_upload_unknown'
	&& is_dir( $dir. '/from-catalogue' ) === true && is_dir( \Nino\Features::dir(). '/Themes/themes/nino' ) === true );


// the last state that is kept - what Restore brings back - shows the theme
$site = upSite();
$_SESSION = [];
upChoose( $site, upPack( upLight( 'light-last', '1.0.0' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
upSpare( $site );
upCall( $site, 'themes/apply', [ 'theme' => 'light-last' ] );
upCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
[ $heldLast, $bodyLast ] = upCall( $site, 'themes/remove', [ 'theme' => 'light-last' ] );
[ , $shownLast ] = upChoose( $site, upPack( upLight( 'light-last', '1.1.0' ) ) );
[ $replaceLast, $bodyReplaceLast ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
upCall( $site, 'themes/apply', [ 'theme' => 'spare' ] );
[ $removedLast ] = upCall( $site, 'themes/remove', [ 'theme' => 'light-last' ] );

check( 'the last state that is kept - what Restore brings back - holds a theme too: it is not taken away and not replaced while that state shows it, the answers say so and the preview has no button; once another state has taken its place, the theme is taken away',
	$heldLast === 409 && ( $bodyLast['code'] ?? '' ) === 'themes_upload_in_use_last' && $replaceLast === 409 && ( $bodyReplaceLast['code'] ?? '' ) === 'themes_upload_replace_held_last' && ( $shownLast['theme']['canInstall'] ?? true ) === false && $removedLast === 200 );

// an own theme that is made of a part of the theme does not hold it: it has the part in its own folder
$site = upSite();
$_SESSION = [];
upChoose( $site, upPack( upLight( 'light-one', '1.0.0' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
upSpare( $site );
upCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Mine', 'parts' => [ 'button' => [ 'from' => 'light-one' ] ], 'css' => '' ] ] );
$partBefore = (string) file_get_contents( ninoSandboxDir( $site ). '/private/themes/mine/parts/button/part.json' );
[ , $openedBefore ] = upCall( $site, 'themes/open', [ 'theme' => 'own:mine' ] );
[ $heldNow ] = upCall( $site, 'themes/remove', [ 'theme' => 'light-one' ] );
[ $openedStatus, $opened ] = upCall( $site, 'themes/open', [ 'theme' => 'own:mine' ] );
[ $appliedStatus ] = upCall( $site, 'themes/apply', [ 'theme' => 'own:mine', 'base' => (string) ( $opened['stamp'] ?? '' ) ] );

check( 'an own theme that is made of a part of an uploaded theme holds that theme in place no more than a copy does: the part is in its folder, so the theme is taken away - and the own theme stands on it: its part is told from the theme while it is there, and from another installed theme that has the same part when it is gone - the comparison is of what is in the files -, and it is applied',
	$openedBefore['groups']['button']['from'] === 'light-one@1.0.0' && $heldNow === 200 && is_dir( ninoSandboxDir( $site ). '/private/packages/themes/light-one' ) === false && $openedStatus === 200 && $opened['groups']['button']['from'] === 'spare@1.0.0' && $opened['groups']['button']['problem'] === ''
	&& $appliedStatus === 200 && (string) file_get_contents( ninoSandboxDir( $site ). '/private/themes/mine/parts/button/part.json' ) === $partBefore );

echo "\nF. Replacing\n";

$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
upChoose( $site, upPack( upLight( 'rep', '1.3.0' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$digest = upFiles( $dir );
$answers = [];

foreach( [ '1.3.0' => 'themes_upload_not_newer_same', '1.2.0' => 'themes_upload_not_newer_older' ] as $version => $code ) {

	[ , $shown ] = upChoose( $site, upPack( upLight( 'rep', $version ) ) );
	[ $status, $body ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
	$answers[$version] = $status === 409 && ( $body['code'] ?? '' ) === $code && ( $body['params'] ?? [] ) === [ 'Second look', '1.3.0', $version ] && ( $shown['theme']['canInstall'] ?? true ) === false
		&& in_array( str_replace( 'themes_upload_', '', $code ), array_column( $shown['theme']['refused'] ?? [], 'code' ), true ) === true && ( $shown['theme']['installed']['replace'] ?? true ) === false;
}

check( 'a theme that was uploaded is replaced by a higher version only: the same version and an older one are a 409 that names both versions, and the preview says it before the button - there is none - and the folder is as it was',
	array_filter( $answers, static fn( bool $ok ): bool => $ok === false ) === [] && upFiles( $dir ) === $digest );

upCall( $site, 'themes/apply', [ 'theme' => 'rep' ] );
[ , $shown ] = upChoose( $site, upPack( upLight( 'rep', '1.4.0' ) ) );
[ $held, $body ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );

check( 'a theme is not replaced while the website shows it, whatever the version: the preview says to apply another theme first and has no button, and a request that asks is a 409 - and the folder is as it was',
	$held === 409 && ( $body['code'] ?? '' ) === 'themes_upload_replace_held_active' && ( $shown['theme']['canInstall'] ?? true ) === false && in_array( 'replace_held_active', array_column( $shown['theme']['refused'] ?? [], 'code' ), true ) === true && upFiles( $dir ) === $digest );

// the same second, the same size: what the reader of the themes kept of the folder before is not what is read after, and the website draws the version that was put in place
upCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
upSpare( $site );
upCall( $site, 'themes/apply', [ 'theme' => 'spare' ] );
upChoose( $site, upPack( upLight( 'rep', '1.3.1', '.25' ) ) );
[ $replacedAt ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
upCall( $site, 'themes/apply', [ 'theme' => 'rep' ] );
$sheet = (string) file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' );
[ , $listed ] = upCall( $site, 'themes/list' );
$card = array_values( array_filter( $listed['themes'], static fn( array $one ): bool => $one['key'] === 'rep' ) )[0] ?? [];

check( 'after a replacement the website draws the version that was put in place: applied at once, in the same second and with files of the same size, the stylesheet has the new part and the card says the new version',
	$replacedAt === 200 && str_contains( $sheet, 'rgb( 0 0 0 / .25 )' ) === true && str_contains( $sheet, 'rgb( 0 0 0 / .15 )' ) === false && ( $card['version'] ?? '' ) === '1.3.1' );

echo "\nG. Keys and blocks\n";

$site = upSite();
$_SESSION = [];
$base = upWritten( $site );
$why = [];

foreach( \Nino\Modules\Themes\Store::RESERVED as $reserved ) {

	unset( $_SESSION[Upload::SESSION] );
	[ , $shown ] = upChoose( $site, upPack( upLight( $reserved, '1.0.0' ) ) );
	[ $status, $body ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
	$why[$reserved] = $status === 409 && ( $body['code'] ?? '' ) === 'themes_upload_key_reserved' && ( $shown['theme']['canInstall'] ?? true ) === false && in_array( 'key_reserved', array_column( $shown['theme']['refused'] ?? [], 'code' ), true ) === true && upWritten( $site ) === $base;
}

check( 'a key that is the origin of blocks of the kernel, a feature or the project - the blocks of a theme are told by its key - is refused: in the preview, with no button, and at the write, a 409 - and nothing is written, so no block of a theme is ever a block of the project or of Basis',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

// a block whose key another theme has: the theme that is read first keeps it; a theme that would put out the block of another is refused, one that is read later is told that the block stays outside
$block = static function( array $files, string $key ) {

	foreach( [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json' ] as $name )
		$files['blocks/'. $key. '/'. $name] = $files['blocks/second-split/'. $name];

	foreach( array_keys( $files ) as $path )
		if( str_starts_with( $path, 'blocks/second-' ) === true )
			unset( $files[$path] );

	return $files;
};
$late = $block( upFiles2( 'zz-late' ), 'shared-block' );
upChoose( $site, upPack( $late ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$digest = upFiles( ninoSandboxDir( $site ). '/private/packages/themes' );
[ , $early ] = upChoose( $site, upPack( $block( upFiles2( 'aa-early' ), 'shared-block' ) ) );
[ $displaces, $body ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
[ , $later ] = upChoose( $site, upPack( $block( upFiles2( 'zz-later' ), 'shared-block' ) ) );
$laterBlocks = array_column( $later['theme']['blocks'] ?? [], null, 'key' );
[ $keeps ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );

check( 'a block of a package that would put out the block of another theme - the one that is read first keeps the key - is refused with the block and the theme named, in the preview and at the write, and nothing is written; one that would stay outside is told so in the preview, with the theme that has it, and is taken',
	$displaces === 409 && ( $body['code'] ?? '' ) === 'themes_upload_block_displaces' && ( $early['theme']['blocks'][0]['state'] ?? '' ) === 'displaces' && ( $early['theme']['canInstall'] ?? true ) === false && upFiles( ninoSandboxDir( $site ). '/private/packages/themes' ) !== [] && is_dir( ninoSandboxDir( $site ). '/private/packages/themes/aa-early' ) === false
	&& ( $laterBlocks['shared-block']['state'] ?? '' ) === 'out' && ( $laterBlocks['shared-block']['origin'] ?? '' ) !== '' && $keeps === 200 && ( \Nino\Blocks::all( $site )['shared-block']['origin'] ?? '' ) === 'zz-late' );

echo "\nH. What a theme of the panel is\n";

$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
upChoose( $site, upPack( upLight( 'mark-a', '1.0.0' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
[ , $listed ] = upCall( $site, 'themes/list' );
$origins = array_column( array_filter( $listed['themes'], static fn( array $one ): bool => $one['kind'] === 'theme' ), 'origin', 'key' );

// the folder is not the one that was written: a file changed by hand makes it another theme, which an upload does not replace and the panel does not remove
file_put_contents( $dir. '/mark-a/parts/button/part.css', "/* by hand */\n". (string) file_get_contents( $dir. '/mark-a/parts/button/part.css' ) );
upChoose( $site, upPack( upLight( 'mark-a', '1.1.0' ) ) );
[ $replaceHand, $bodyHand ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
[ $removeHand, $bodyRemoveHand ] = upCall( $site, 'themes/remove', [ 'theme' => 'mark-a' ] );
[ , $listed ] = upCall( $site, 'themes/list' );

check( 'a theme counts as uploaded while every file of its folder is the one that was written: a file changed by hand makes it a theme the panel did not write - an upload does not replace it, the panel does not remove it, the list says it is of the catalogue - and the mark of it is let go of',
	( $origins['mark-a'] ?? '' ) === 'uploaded' && $replaceHand === 409 && ( $bodyHand['code'] ?? '' ) === 'themes_upload_taken_catalogue' && $removeHand === 409 && ( $bodyRemoveHand['code'] ?? '' ) === 'themes_upload_not_uploaded'
	&& array_column( array_filter( $listed['themes'], static fn( array $one ): bool => $one['kind'] === 'theme' ), 'origin', 'key' )['mark-a'] === 'catalogue' && isset( Upload::marks( $site )['mark-a'] ) === false );

// a theme that was taken away where the packages are managed takes its mark with it: the same files that come from the catalogue are the catalogue's
upChoose( $site, upPack( upLight( 'mark-b', '1.0.0' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$markB = Upload::marks( $site )['mark-b'] ?? '';
\Nino\Packages::remove( $site, \Nino\Packages::THEME, 'mark-b' );
upCall( $site, 'themes/list' );
$gone = isset( Upload::marks( $site )['mark-b'] ) === false;
\Nino\Packages::place( $site, \Nino\Packages::THEME, 'mark-b', upLight( 'mark-b', '1.0.0' ) );
upChoose( $site, upPack( upLight( 'mark-b', '1.1.0' ) ) );
[ $replaceBack, $bodyBack ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
[ $removeBack ] = upCall( $site, 'themes/remove', [ 'theme' => 'mark-b' ] );

check( 'a mark whose folder is gone is let go of at the next request of the panel, and the same files that come from the catalogue after it are the catalogue\'s: an upload does not replace them and the panel does not remove them',
	$markB !== '' && $gone === true && $replaceBack === 409 && ( $bodyBack['code'] ?? '' ) === 'themes_upload_taken_catalogue' && $removeBack === 409 );

// two writers over a state that one of them read before the other wrote: nothing the other wrote is lost
upChoose( $site, upPack( upLight( 'mark-c', '1.0.0' ) ) );
$stale = Upload::marks( $site );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
Upload::changeMarks( $site, static function( array $marks ) use ( $stale ): array { return $marks + [ 'writer-two' => str_repeat( 'a', 64 ) ]; } );

check( 'the marks are read, changed and written under a lock: a writer that read before another wrote does not write over what the other wrote - the mark of the theme that was put in is still there beside the one that came after it',
	isset( Upload::marks( $site )['mark-c'] ) === true && isset( Upload::marks( $site )['writer-two'] ) === true && isset( $stale['mark-c'] ) === false );

// a folder of the hook of the project: a theme that is no package at all
$hooked = ninoSandboxDir( $site ). '/hooked-themes';
mkdir( $hooked. '/hooked', 0755, true );
file_put_contents( $hooked. '/hooked/theme.json', json_encode( [ 'key' => 'hooked', 'version' => '1.0.0', 'name' => 'Hooked', 'description' => 'From a folder of the project.' ] ) );
\Nino\Callbacks::registerCallback( $site, \Nino\Modules\Themes\Reader::ROOTS, static function( array &$appData, array $roots ) use ( $hooked ): array { return array_merge( $roots, [ $hooked ] ); } );
unset( $site['./nino/themes/read'] );
[ , $shown ] = upChoose( $site, upPack( upLight( 'hooked', '2.0.0' ) ) );
[ $projectStatus, $projectBody ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
[ $projectRemove ] = upCall( $site, 'themes/remove', [ 'theme' => 'hooked' ] );

check( 'a theme of a folder of the hook of the project is neither replaced by an upload nor removed by the panel: the preview names where it comes from, the write is a 409, the removal is a 409',
	( $shown['theme']['installed']['origin'] ?? '' ) === 'project' && $projectStatus === 409 && ( $projectBody['code'] ?? '' ) === 'themes_upload_taken_project' && $projectRemove === 409 && is_dir( $hooked. '/hooked' ) === true );

// the number of themes that were uploaded
$site = upSite();
$_SESSION = [];

for( $at = 1; $at <= Upload::MAX_UPLOADED; $at++ ) {
	upChoose( $site, upPack( upLight( 'many-'. $at, '1.0.0' ) ) );
	upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
}

[ , $shown ] = upChoose( $site, upPack( upLight( 'many-over', '1.0.0' ) ) );
[ $over, $bodyOver ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );

check( 'only so many themes are uploaded: the one after the last is refused, in the preview and at the write, and the folder of it is not there',
	count( Upload::marks( $site ) ) === Upload::MAX_UPLOADED && $over === 409 && ( $bodyOver['code'] ?? '' ) === 'themes_upload_too_many' && ( $shown['theme']['canInstall'] ?? true ) === false && is_dir( ninoSandboxDir( $site ). '/private/packages/themes/many-over' ) === false );

echo "\nI. Words and the brand\n";

$site = upSite();
$_SESSION = [];
$base = upWritten( $site );
$why = [];

foreach( [
	'a name of 81 characters'					=> [ 'name', str_repeat( 'n', 81 ) ],
	'a description of 241 characters'	=> [ 'description', str_repeat( 'd', 241 ) ],
	'a character that turns the line'	=> [ 'name', "Second \u{202E}look" ],
	'a zero width joiner'							=> [ 'name', "Sec\u{200D}ond" ],
	'a line break in a name'					=> [ 'name', "Second\nlook" ],
] as $name => [ $field, $text ] ) {

	$changed = upFiles2();
	$json = (array) json_decode( $changed['theme.json'], true );
	$json[$field] = $text;
	$changed['theme.json'] = (string) json_encode( $json, JSON_UNESCAPED_UNICODE );
	unset( $_SESSION[Upload::SESSION] );
	[ $status, $body ] = upChoose( $site, upPack( $changed ) );
	$why[$name] = $status === 400 && ( $body['code'] ?? '' ) === 'themes_upload_package_words' && isset( $_SESSION[Upload::SESSION] ) === false && upWritten( $site ) === $base;
}

check( 'a name or a description has the length the names of a block have and no control character and no character that turns a line round - a name of 81 characters, a description of 241, a right-to-left override, a joiner and a line break are refused before anything is held',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

$why = [];

foreach( [
	'a colour that closes the style'	=> [ 'color-1' => '</style><script>alert(1)</script>' ],
	'a tone that is none'							=> [ 'tone' => 'loud' ],
	'a key that is none'							=> [ 'color-9' => '#ffffff' ],
	'a font that is a tag'						=> [ 'font-title' => '<img src=x>' ],
] as $name => $brand ) {

	$changed = upFiles2();
	$json = (array) json_decode( $changed['theme.json'], true );
	$json['brand'] = $brand;
	$changed['theme.json'] = (string) json_encode( $json );
	unset( $_SESSION[Upload::SESSION] );
	[ $status, $body ] = upChoose( $site, upPack( $changed ) );
	$why[$name] = $status === 400 && ( $body['code'] ?? '' ) === 'themes_upload_package_brand' && isset( $_SESSION[Upload::SESSION] ) === false && upWritten( $site ) === $base;
}

check( 'what the theme suggests for the brand is held to the rule of the brand before anything is held: a colour that closes the style element, a tone that is none, a key that is none and a font that is a tag are refused',
	array_filter( $why, static fn( bool $ok ): bool => $ok === false ) === [] );

// what a key says today: beside the key in the preview, in the working language, cut at the end of a word
$site = upSite();
$_SESSION = [];
$long = 'Omega Alpha beta gamma delta epsilon zeta eta theta iota kappa lambda mu nu xi omicron pi rho sigma tau upsilon phi chi psi omega';
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/project/company/general/name]]' => $long. ' <b>Name</b>' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/project/company/general/name]]' => 'Name of the company' ] );
ninoUser( $site, 'only-themes@example.com', [ \Nino\Modules\Themes::PERM ] );
[ , $shownText ] = upChoose( $site, upPack( upRich( 'rich-text', '1.0.0' ) ) );
$frameText = array_values( array_filter( $shownText['theme']['blocks'] ?? [], static fn( array $block ): bool => $block['frame'] === true ) )[0] ?? [];
$read = $frameText['reads'][0] ?? [];
$text = (string) ( $read['text'] ?? '' );
$cut = rtrim( $text, '…' );

check( 'beside each key a frame reads the text the site has at it today stands, in the working language of the workbench, as a line of text without markup and cut at the end of a word with a mark that it goes on',
	( $read['key'] ?? '' ) === '/project/company/general/name' && $text !== '' && str_ends_with( $text, '…' ) === true && mb_strlen( $cut ) <= 80 && str_starts_with( $long, $cut ) === true && $long[mb_strlen( $cut )] === ' ' && str_contains( $text, '<' ) === false
	&& ( $read['text'] ?? '' ) !== 'Name of the company' );

// what a block of a package may read is the right of the side of its root, as the kernel's schema says it, never of the name of the root: a header root and a page root of a feature of
// the test, each with a block that names a text of the site
foreach( [ 'probe-head' => 'header', 'probe-top' => 'page' ] as $rootName => $side )
	\Nino\Components::addComponent( $site, $rootName, [ 'label' => $rootName, 'in' => [ 'page' ], 'offers' => [ 'flow' ], 'block' => $side, 'attributes' => [ 'id' => [ 'type' => 'id', 'default' => '' ] ], 'template' => '<div[[id]] class="[[class]]">[[content]]</div>' ] );

$withRoot = static function( string $rootName, string $category ): array {

	$files = upRich( 'rich-sides', '1.0.0' );

	foreach( array_keys( $files ) as $path )
		if( str_starts_with( $path, 'blocks/' ) === true )
			unset( $files[$path] );

	$files['blocks/'. $rootName. '-block/block.json'] = (string) json_encode( [ 'name' => [ 'en_US' => $rootName ], 'description' => [ 'en_US' => $rootName ], 'category' => $category, 'rank' => 5, 'images' => (object) [] ] );
	$files['blocks/'. $rootName. '-block/block.tpl'] = '['. $rootName. ' id="x"]'. "\n\t[text /project/company/general/name]\n[/". $rootName. "]\n";
	$files['blocks/'. $rootName. '-block/text/en_US.json'] = '{}';

	return $files;
};
[ $headStatus, $shownHead ] = upChoose( $site, upPack( $withRoot( 'probe-head', 'header' ) ) );
[ $topStatus, $refusedTop ] = upChoose( $site, upPack( $withRoot( 'probe-top', 'content' ) ) );
$headBlock = array_column( $shownHead['theme']['blocks'] ?? [], null, 'key' )['probe-head-block'] ?? [];

check( 'a block of a package reads the text of the site by the side its root says: the header root of another feature is a frame that reads it, its page root is refused with the block - 400 - for the same text',
	$headStatus === 200 && ( $headBlock['frame'] ?? null ) === true && ( $headBlock['side'] ?? '' ) === 'header' && in_array( '/project/company/general/name', array_column( $headBlock['reads'] ?? [], 'key' ), true ) === true
	&& $topStatus === 400 && ( $refusedTop['code'] ?? '' ) === 'themes_upload_package_block_source' );

echo "\nJ. The right of the panel, and the bytes that stay\n";

// an account that has the right of the panel and no other puts in, replaces and takes away a package with blocks and frames
$site = upSite();
$_SESSION = [];
$dir = ninoSandboxDir( $site ). '/private/packages/themes';
ninoUser( $site, 'only-themes@example.com', [ \Nino\Modules\Themes::PERM ] );
[ , $shown ] = upChoose( $site, $bytes );
[ $installed ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
upChoose( $site, upPack( upFiles2( 'second-look', '1.9.0' ) ) );
[ $replaced ] = upCall( $site, 'themes/upload', [ 'id' => upHeld(), 'replace' => true ] );
[ $gone ] = upCall( $site, 'themes/remove', [ 'theme' => 'second-look' ] );

check( 'an account that has the right of the panel and no other puts in a package with blocks and frames - the preview has the button - replaces it by a higher version and takes it away: the right of the panel is enough for each of the three',
	( $shown['theme']['canInstall'] ?? false ) === true && ( $shown['theme']['frames'] ?? 0 ) === 1 && $installed === 200 && $replaced === 200 && $gone === 200 && is_dir( $dir. '/second-look' ) === false );

// the bytes stay while another change of the packages is at work: the person asks again, and does not choose the file again
upChoose( $site, upPack( upLight( 'wait-for', '1.0.0' ) ) );
$id = upHeld();
$other = $site;
unset( $other['./nino/filesystem/locks'], $other['./nino/features/lock'] );
$locked = \Nino\Features::lock( $other );
[ $busyStatus, $busyBody ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );
$stays = upHeld() === $id;
\Nino\Features::unlock( $other );
[ $retried ] = upCall( $site, 'themes/upload', [ 'id' => $id ] );

check( 'while another change of the packages is at work the answer is that it is busy and the bytes are still held: asking again with the same key writes the package',
	$locked === true && $busyStatus === 400 && ( $busyBody['code'] ?? '' ) === 'themes_upload_package_busy' && $stays === true && $retried === 200 && is_file( $dir. '/wait-for/theme.json' ) === true );

// the bytes of a preview that was left are let go of at any action of the panel, and not only at the ones of the upload
upChoose( $site, upPack( upLight( 'left-over', '1.0.0' ) ) );
$_SESSION[Upload::SESSION]['until'] = time() - 1;
upCall( $site, 'themes/list' );

check( 'the bytes of a preview that ran out are let go of by the next action of the panel, whichever it is',
	isset( $_SESSION[Upload::SESSION] ) === false );


echo "\nK. The steps of the knobs and the css of a theme\n";

$site = upSite();
$_SESSION = [];
ninoUser( $site, 'only-themes@example.com', [ \Nino\Modules\Themes::PERM ] );
$sheetOf = static fn(): string => (string) @file_get_contents( ninoSandboxDir( $site ). '/private/assets/theme.css' );
[ , $shown ] = upChoose( $site, upPack( upRich( 'rich', '1.0.0' ) ) );
$frame = array_values( array_filter( $shown['theme']['blocks'] ?? [], static fn( array $block ): bool => $block['frame'] === true ) )[0] ?? [];
$pages = array_values( array_filter( $shown['theme']['blocks'] ?? [], static fn( array $block ): bool => $block['frame'] === false ) )[0] ?? [];
[ $installed ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );

check( 'the preview of a package that brings css says how many lines it has and has the text, and names for each frame the keys of the texts of the site it reads - a block of the pages reads none - all as text',
	( $shown['theme']['css']['lines'] ?? 0 ) === 2 && str_contains( (string) ( $shown['theme']['css']['text'] ?? '' ), '.theme-probe-inner' ) === true && in_array( '/project/company/general/name', array_column( $frame['reads'] ?? [], 'key' ), true ) === true && ( $pages['reads'] ?? [ 'x' ] ) === [] && $installed === 200 );

$read = \Nino\Modules\Themes\Reader::read( $site )['themes']['rich'] ?? [];

check( 'a part that carries values is read with them - the steps of its knobs, from -2 to 2, in the order of the knobs, the middle one left out - for a group and for the page',
	( $read['parts']['button']['ok'] ?? false ) === true && ( $read['parts']['button']['part']['values'] ?? [] ) === [ 'button.radius' => 2, 'button.space' => -1 ] && ( $read['parts']['page']['part']['values'] ?? [] ) === [ 'size' => 1 ] );

$bad = [
	'a step of 3'						=> [ 'button.radius' => 3 ],
	'a knob the part has not'	=> [ 'button.nothing' => 1 ],
	'a step that is a string'	=> [ 'button.radius' => '1' ],
	'a list'								=> [ 1, 2 ],
];
$rests = [];
$files = upRich( 'bad-values', '1.0.0' );

foreach( $bad as $name => $values ) {

	$json = (array) json_decode( $files['parts/button/part.json'], true );
	$json['values'] = $values;
	upHook( $site, 'bad-values', [ 'theme.json' => $files['theme.json'], 'parts/button/part.json' => json_encode( $json ) ] );
	$part = \Nino\Modules\Themes\Reader::read( $site )['themes']['bad-values']['parts']['button'] ?? [];
	$why = \Nino\Modules\Themes\Reader::explain( (string) $part['problem'] );
	$rests[$name] = ( $part['ok'] ?? true ) === false && ( $why['code'] ?? '' ) === 'part-values' && ( $why['params'][0] ?? 'x' ) === ( array_is_list( $values ) === true ? '' : (string) array_key_first( $values ) );
}

check( 'values that are no steps of knobs of the part - a step out of -2 to 2, a knob the part has not, a step that is no whole number, a list - let the part rest with the reason, as any other mistake of a part does, and the theme is read all the same',
	array_filter( $rests, static fn( bool $ok ): bool => $ok === false ) === [] && isset( \Nino\Modules\Themes\Reader::read( $site )['themes']['bad-values'] ) === true );

upCall( $site, 'themes/apply', [ 'theme' => 'rich' ] );
$sheet = $sheetOf();
[ , $opened ] = upCall( $site, 'themes/open', [ 'theme' => 'rich' ] );
$knob = array_values( array_filter( $opened['groups']['button']['knobs'] ?? [], static fn( array $one ): bool => $one['key'] === 'button.radius' ) )[0] ?? [];

check( 'a theme of a package is drawn and applied at the steps it is delivered at, and the editor shows them as the stand of the knobs: the stylesheet has the fifth value of the ladder and the first of the other, the page at its step - and the middle of the scale is the middle, a step moves no ladder',
	str_contains( $sheet, '--button-radius: 5px;' ) === true && str_contains( $sheet, '--page-size: 106.25%;' ) === true && str_contains( $sheet, '--button-space: calc( var(--page-space-1) * 1.5 );' ) === true
	&& ( $knob['step'] ?? 9 ) === 2 && ( $knob['ladder'][2] ?? '' ) === '3px' && ( $knob['default'] ?? '' ) === '3px' && str_contains( $sheet, '--page-width: 64rem;' ) === true );

$offer = array_values( array_filter( $opened['groups']['button']['offers'] ?? [], static fn( array $one ): bool => $one['theme'] === 'rich' ) )[0] ?? [];
$pageOffer = array_values( array_filter( $opened['groups']['page']['offers'] ?? [], static fn( array $one ): bool => $one['theme'] === 'rich' ) )[0] ?? [];

check( 'the editor is told the step each knob is delivered at - beside the step it stands at - and the steps each part that is offered is delivered at, so that a part chosen for a group begins where it does in its theme: the knob of the group, the offer for the group and the offer for the page',
	( $knob['delivered'] ?? 9 ) === 2 && ( $offer['values'] ?? [] ) === [ 'button.radius' => 2, 'button.space' => -1 ] && ( $pageOffer['values'] ?? [] ) === [ 'size' => 1 ]
	&& array_filter( $opened['groups']['section']['knobs'] ?? [], static fn( array $one ): bool => ( $one['delivered'] ?? 9 ) !== 0 ) === [] );

$part = strpos( $sheet, '--button-radius: 5px;' );
$own = strpos( $sheet, '.theme-probe { letter-spacing' );

check( 'the css of the theme stands after its parts in the stylesheet and as it is in the package, once',
	$part !== false && $own !== false && $part < $own && substr_count( $sheet, '.theme-probe-inner' ) === 1 );

// an own theme made of it begins at those steps and has the css to change, and stays regulable
$mix = (array) $opened['mix'];
$mix['name'] = 'Mine from rich';
[ $saved, $made ] = upCall( $site, 'themes/save-as', [ 'mix' => $mix ] );
[ , $mine ] = upCall( $site, 'themes/open', [ 'theme' => 'own:mine-from-rich' ] );
$mineKnob = array_values( array_filter( $mine['groups']['button']['knobs'] ?? [], static fn( array $one ): bool => $one['key'] === 'button.radius' ) )[0] ?? [];
$moved = (array) $mine['mix'];
$moved['parts']['button']['values']['button.radius'] = -1;
$moved['css'] = (string) $moved['css']. "\n.theme-probe { margin: 1px; }";
$stamp = (string) ( $mine['stamp'] ?? '' );
[ $moving ] = upCall( $site, 'themes/save', [ 'theme' => 'own:mine-from-rich', 'mix' => $moved, 'base' => $stamp ] );
[ , $again ] = upCall( $site, 'themes/open', [ 'theme' => 'own:mine-from-rich' ] );
upCall( $site, 'themes/apply', [ 'theme' => 'own:mine-from-rich', 'base' => (string) ( $again['stamp'] ?? '' ) ] );
$after = $sheetOf();

check( 'an own theme made of a theme with steps and css begins at the steps and has the css as the start of its own - and is as free to be set as any: a knob moved from the step it was delivered at and a line of css added are in the stylesheet, the css once',
	$saved === 200 && ( $mineKnob['step'] ?? 9 ) === 2 && str_contains( (string) ( $mine['mix']['css'] ?? '' ), '.theme-probe-inner' ) === true && $moving === 200 && str_contains( $after, '--button-radius: 2px;' ) === true && str_contains( $after, '--button-radius: 5px;' ) === false
	&& str_contains( $after, '.theme-probe { margin: 1px; }' ) === true && substr_count( $after, '.theme-probe-inner' ) === 1 );

// a css of a theme that does not keep the rule is not drawn: not by the theme, not by a theme made of it
$filesBad = upRich( 'bad-css', '1.0.0' );
upHook( $site, 'bad-css', [ 'theme.json' => $filesBad['theme.json'], 'theme.css' => "body { color: red }\n.ok { color: blue }\n", 'parts/button/part.json' => $filesBad['parts/button/part.json'] ] );
// a kernel that says the line of a finding on a selector gives the reason the line (the rule is at line 1 here); one that does not gives it without: what is expected is what this kernel does
$kernelLine = preg_match( '/\(line 1\)/', implode( '; ', \Nino\Components::themeProblems( "body { color: red }\n.ok { color: blue }\n" ) ) ) === 1;
$expectedWhy = $kernelLine === true ? [ 'theme-css', [ '1' ] ] : [ 'theme-css-any', [] ];
$badRead = \Nino\Modules\Themes\Reader::read( $site )['themes']['bad-css'] ?? [];
$badMix = \Nino\Modules\Themes\Mix::of( $site, 'bad-css' ) ?? [];
upCall( $site, 'themes/apply', [ 'theme' => 'bad-css' ] );
$badSheet = $sheetOf();
[ , $badOpen ] = upCall( $site, 'themes/open', [ 'theme' => 'bad-css' ] );
$badOwn = (array) $badOpen['mix'];
$badOwn['name'] = 'Mine from bad';
upCall( $site, 'themes/save-as', [ 'mix' => $badOwn ] );
upCall( $site, 'themes/apply', [ 'theme' => 'own:mine-from-bad' ] );
$badOwnSheet = $sheetOf();
[ , $listed ] = upCall( $site, 'themes/list' );
$badCard = array_values( array_filter( $listed['themes'], static fn( array $one ): bool => $one['key'] === 'bad-css' ) )[0] ?? [];

check( 'a css of a theme that does not keep the rule is left out and named: the theme is read and used without it, it is not in the stylesheet - not of the theme, not of an own theme made of it, whose css begins empty - and the list says why, with what it found',
	( $badRead['css'] ?? 'x' ) === '' && ( ( $badExplained = \Nino\Modules\Themes\Reader::explain( (string) ( $badRead['cssProblem'] ?? '' ) ) )['code'] ?? '' ) === $expectedWhy[0] && ( $badExplained['params'] ?? [] ) === $expectedWhy[1] && isset( $badMix['css'] ) === false && str_contains( $badSheet, 'color: red' ) === false && str_contains( $badSheet, 'color: blue' ) === false
	&& str_contains( $badOwnSheet, 'color: red' ) === false && ( $badOwn['css'] ?? '' ) === '' && [ $badCard['css']['why']['code'] ?? '', $badCard['css']['why']['params'] ?? [] ] === $expectedWhy );

// a package whose css breaks the rule is not put in place at all
$nastyCss = upRich( 'nasty-css', '1.0.0' );
$nastyCss['theme.css'] = "body { color: red }\n";
$before = upWritten( $site );
[ $nastyStatus, $nastyBody ] = upChoose( $site, upPack( $nastyCss ) );
$expectedBody = $kernelLine === true ? [ 'themes_upload_package_theme_css', [ 'theme.css', '1' ] ] : [ 'themes_upload_package_theme_css_any', null ];

check( 'a package whose theme.css breaks the rule of a theme is refused before it is held, with the file named, and nothing is written',
	$nastyStatus === 400 && ( $nastyBody['code'] ?? '' ) === $expectedBody[0] && ( $expectedBody[1] === null || ( $nastyBody['params'] ?? [] ) === $expectedBody[1] ) && isset( $_SESSION[Upload::SESSION] ) === false && upWritten( $site ) === $before );

// a package whose values are no steps of knobs is refused as any package with a part that is wrong
$nastyValues = upRich( 'nasty-values', '1.0.0' );
$json = (array) json_decode( $nastyValues['parts/button/part.json'], true );
$json['values'] = [ 'button.radius' => 3 ];
$nastyValues['parts/button/part.json'] = (string) json_encode( $json );
[ $valuesStatus, $valuesBody ] = upChoose( $site, upPack( $nastyValues ) );

check( 'a package whose values are no steps of knobs - a step of 3 - is refused before it is held, with the part named, and nothing is written',
	$valuesStatus === 400 && ( $valuesBody['code'] ?? '' ) === 'themes_upload_package_part_values' && isset( $_SESSION[Upload::SESSION] ) === false && upWritten( $site ) === $before );

// what is said of the css and of the steps is said with what the kernel gives: the line, the knob
$lined = upRich( 'lined-css', '1.0.0' );
$lined['theme.css'] = ".a { margin: 0; }\n.b { margin: 0; }\n/* </style> */\n";
[ $linedStatus, $linedBody ] = upChoose( $site, upPack( $lined ) );
upHook( $site, 'lined-hand', [ 'theme.json' => upRich( 'lined-hand', '1.0.0' )['theme.json'], 'theme.css' => $lined['theme.css'] ] );
$linedWhy = \Nino\Modules\Themes\Reader::explain( (string) ( \Nino\Modules\Themes\Reader::read( $site )['themes']['lined-hand']['cssProblem'] ?? '' ) );
$nastyValues['parts/button/part.json'] = (string) json_encode( [ 'values' => [ 'button.nothing' => 1 ] ] + (array) json_decode( $nastyValues['parts/button/part.json'], true ) );
[ , $knobBody ] = upChoose( $site, upPack( $nastyValues ) );

check( 'the css that breaks the rule is told with its line and a part with steps that are none with its knob, in the codes of the panel and with the parameters the kernel gives - no sentence of the kernel is in what is said: in the preview and for a theme read from a folder',
	$linedStatus === 400 && ( $linedBody['code'] ?? '' ) === 'themes_upload_package_theme_css' && ( $linedBody['params'] ?? [] ) === [ 'theme.css', '3' ] && ( $linedWhy['code'] ?? '' ) === 'theme-css' && ( $linedWhy['params'] ?? [] ) === [ '3' ]
	&& ( $knobBody['code'] ?? '' ) === 'themes_upload_package_part_values' && ( $knobBody['params'][1] ?? '' ) === 'button.nothing' );

// the css of a theme that is changed after it was read, to as many bytes, in the same second: the stamp of the css is what it says, so the next read is of the file that is there
upHook( $site, 'stamp-css', [ 'theme.json' => upRich( 'stamp-css', '1.0.0' )['theme.json'], 'theme.css' => ".stamp-probe { margin: 1px; }\n" ] );
$first = \Nino\Modules\Themes\Reader::read( $site )['themes']['stamp-css']['css'] ?? '';
upHook( $site, 'stamp-css', [ 'theme.css' => ".stamp-probe { margin: 2px; }\n" ] );
$second = \Nino\Modules\Themes\Reader::read( $site )['themes']['stamp-css']['css'] ?? '';

check( 'what the reader kept of the themes is stamped by the css of a theme too: the css that is changed to as many bytes - in the same second, as a file that is put in by hand can be - is the css that is read next',
	str_contains( $first, '1px' ) === true && str_contains( $second, 'margin: 2px' ) === true && $first !== $second );

[ , $listed ] = upCall( $site, 'themes/list' );
$richCard = array_values( array_filter( $listed['themes'], static fn( array $one ): bool => $one['key'] === 'rich' ) )[0] ?? [];

check( 'the card of an installed theme says what its frames read, as the preview does, and how long its css is - the keys as text of a list, once for each frame',
	( $richCard['css']['lines'] ?? 0 ) === 2 && array_key_exists( 'why', $richCard['css'] ?? [] ) === true && $richCard['css']['why'] === null && array_column( $richCard['frames'] ?? [], 'key' ) === [ 'rich-foot' ] && in_array( '/project/company/general/name', array_column( $richCard['frames'][0]['reads'] ?? [], 'key' ), true ) === true && array_key_exists( 'text', $richCard['frames'][0]['reads'][0] ?? [] ) === true && $richCard['frames'][0]['reads'][0]['text'] === null );

// the own css of the project is the project's and not a theme's: a package that is put in place, applied and taken away leaves it as it is
\Nino\Filesystem::putFileContent( $site, '/assets/style.css', ".project-own-mark { color: rgb( 1 2 3 ); }\n" );
$projectSheet = ninoSandboxDir( $site ). '/private/assets/style.css';
$projectHash = hash_file( 'sha256', $projectSheet );
$projectTime = filemtime( $projectSheet );
upChoose( $site, upPack( upLight( 'own-css-probe', '1.0.0' ) ) );
[ $putStatus ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
upCall( $site, 'themes/apply', [ 'theme' => 'own-css-probe' ] );
[ $goneStatus ] = upCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
[ $restoreStatus ] = upCall( $site, 'themes/restore', [ 'base' => \Nino\Modules\Themes\Store::stamp( $site ) ] );

check( 'a theme package that is uploaded, put in place, put on, taken off and a last state that is restored leave the own css of the project as it is - its bytes and its time - and does not carry it',
	$putStatus === 200 && $goneStatus === 200 && $restoreStatus === 200 && hash_file( 'sha256', $projectSheet ) === $projectHash && filemtime( $projectSheet ) === $projectTime
	&& array_filter( upFiles( ninoSandboxDir( $site ). '/private/packages' ), static fn( string $hash, string $path ): bool => str_contains( $path, 'style.css' ), ARRAY_FILTER_USE_BOTH ) === [] );

// every refusal has its sentence in both languages
$sayings = [ 'de_DE' => require dirname( __DIR__ ). '/text/de_DE.php', 'en_US' => require dirname( __DIR__ ). '/text/en_US.php' ];
$codes = array_merge(
	array_map( static fn( string $code ): string => 'themes_upload_archive_'. str_replace( '-', '_', $code ), [ 'gzip', 'bomb', 'trailing', 'truncated', 'header', 'type', 'pax', 'path', 'depth', 'folder', 'duplicate', 'name', 'count', 'file-size', 'total-size', 'unsupported', 'refused' ] ),
	array_map( static fn( string $code ): string => 'themes_upload_package_'. str_replace( '-', '_', $code ), [ 'empty', 'files-many', 'bytes-many', 'path', 'twice', 'not-allowed', 'missing', 'long', 'text', 'json', 'file-key', 'file-slug', 'file-version', 'file-words', 'file-markup', 'file-map', 'parts-many', 'part', 'part_values', 'css', 'theme_css', 'theme_css_any', 'block', 'preview', 'brand', 'words', 'block_loop', 'block_place', 'block_text', 'block_source', 'busy', 'unlockable', 'stage', 'swap', 'refused' ] ),
	array_map( static fn( string $code ): string => 'themes_upload_'. $code, [ 'large', 'nofile', 'unknown', 'exists', 'taken_shipped', 'taken_catalogue', 'taken_project', 'not_uploaded', 'in_use_active', 'in_use_last', 'busy', 'key_reserved', 'key_like', 'not_newer_same', 'not_newer_older', 'replace_held_active', 'replace_held_last', 'too_many', 'block_displaces' ] ),
	[ 'themes_export_unknown', 'themes_export_rejected', 'themes_export_archive', 'themes_export_large', 'themes_import_name' ]
);
$unsaid = array_filter( $codes, static fn( string $code ): bool => isset( $sayings['de_DE']['[[/_admin/error/'. $code. ']]'], $sayings['en_US']['[[/_admin/error/'. $code. ']]'] ) === false );

check( 'every refusal of the upload and of the export has its sentence in both languages - each code of the reader of archives, each of the rule of the packages, each of the rules of the panel, each of the export and of the copy as an own theme - and each code is one the panel looks a sentence up by: letters, digits and underscores', $unsaid === [] && array_filter( $codes, static fn( string $code ): bool => preg_match( '/^[a-z][a-z0-9_]*$/D', $code ) !== 1 ) === [] );

echo "\nL. An own theme as a package, and a package as an own theme\n";

$site = upSite();
$_SESSION = [];
$own = ninoSandboxDir( $site ). '/private/themes';
$packages = ninoSandboxDir( $site ). '/private/packages';
$each = max( \Nino\Blocks::MAX_PICTURE, \Nino\Packages::MAX_PREVIEW );

/** A folder of an own theme written by hand: the files of a package, as they are */
$put = static function( string $key, array $files ) use ( $own, &$site ): string {

	$folder = $own. '/'. $key;
	\Nino\Filesystem::removeDir( $folder );

	foreach( $files as $path => $bytes ) {
		@mkdir( dirname( $folder. '/'. $path ), 0755, true );
		file_put_contents( $folder. '/'. $path, $bytes );
	}

	unset( $site['./nino/themes/own'] );

	return $folder;
};
$read = static fn( string $bytes, string $folder ): array|string => \Nino\Archive::read( $bytes, $folder, 3 * $limits['files'], $each, $limits['bytes'] );

// the folder of an own theme, packed
$files = upFiles2( 'hand-made', '1.2.0' );
$folder = $put( 'hand-made', $files );
$folderBefore = upFiles( $folder );
[ $status, $bytes, $request ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ] );
$back = is_string( $bytes ) === true ? $read( $bytes, 'hand-made' ) : 'none';
$header = $request['/nino/http/response']['header'] ?? [];

check( 'an own theme is exported as a package of the form the catalogue holds a theme to: one folder named for its key, and in it every file of the folder of the theme - theme.json, the parts, the blocks, the picture, the licence and the readme - byte for byte, and nothing else',
	$status === 200 && is_array( $back ) === true && $back == $files && array_diff( array_keys( $files ), array_keys( $back ) ) === [] );

check( 'what is exported is what the kernel takes as a theme package, and nothing of the project is written by the export: the folder is as it was, and no file is made in the packages, in the state of the website or in the own themes',
	is_array( $back ) === true && \Nino\Packages::check( $site, \Nino\Packages::THEME, $back ) === null && upFiles( $folder ) === $folderBefore && is_dir( $packages ) === false && upWritten( $site ) === array_filter( upWritten( $site ), static fn( string $path ): bool => str_starts_with( $path, '/private/themes/hand-made/' ), ARRAY_FILTER_USE_KEY ) );

check( 'the download is a file the kernel answers as one (Http::download): the type of a gzip, an attachment named for the key and the version of the theme, no cache, no sniffing, the length of the bytes, and the bytes are a gzip stream',
	( $header['Content-Type'] ?? '' ) === 'application/gzip' && ( $header['Content-Disposition'] ?? '' ) === 'attachment; filename="hand-made-1.2.0.tar.gz"' && ( $header['Cache-Control'] ?? '' ) === 'private, no-store'
	&& ( $header['X-Content-Type-Options'] ?? '' ) === 'nosniff' && is_string( $bytes ) === true && str_starts_with( $bytes, "\x1f\x8b" ) === true && ( $header['Content-Length'] ?? '' ) === (string) strlen( $bytes )
	&& ( $request['/nino/http/response']['raw'] ?? false ) === true );

[ $came ] = upChoose( $site, (string) $bytes );
upCall( $site, 'themes/upload-drop', [ 'id' => upHeld() ] );

check( 'what is exported is what comes in: the package of an export is read by the check of the upload - the same rules of the words and of the brand - and shown, 200', $came === 200 );

// The size that the question names is the bytes of the files together - the archive's own length depends on the second it was made in, as gzip packs the time with the rest - and the file that
// follows is those files: it is unpacked and its files are those of the folder, and their bytes are the size that was said. A pause between the two (PACK_PAUSE seconds, none by default) makes
// no difference to either
[ $dry, $said ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made', 'check' => true ] );
usleep( (int) ( 1000000 * (float) ( getenv( 'PACK_PAUSE' ) ?: 0 ) ) );
[ , $fetched ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ] );
$got = is_string( $fetched ) === true ? $read( $fetched, 'hand-made' ) : 'none';

check( 'a request that asks only whether it can be exported is answered with the name of the file, its size and the number of files - JSON, no file - and the size is the bytes of the files, which are the files of the file that is sent: unpacked, they are the files of the folder, as many as were said, and add up to it',
	$dry === 200 && is_array( $said ) === true && ( $said['file'] ?? '' ) === 'hand-made-1.2.0.tar.gz' && is_array( $got ) === true && $got == $files && ( $said['size'] ?? 0 ) === array_sum( array_map( 'strlen', $got ) ) && ( $said['files'] ?? 0 ) === count( $files ) );

// a theme that cannot be exported says why - each reason is a sentence of the panel - and no file is made of it
$bad = [
	'a part whose css the rule refuses'							=> [ [ 'parts/button/part.css' => "@import 'x.css';\n.nino-button { color: red; }\n" ], 'themes_upload_package_css' ],
	'a theme.json that is no JSON'									=> [ [ 'theme.json' => '{ not json' ], 'themes_export_rejected' ],
	'a file a package does not have'								=> [ [ 'notes.txt' => 'my notes' ], 'themes_upload_package_not_allowed' ],
	'a file too large'															=> [ [ 'README.md' => str_repeat( 'x', 70000 ) ], 'themes_upload_package_long' ],
	'a picture that is no webp'											=> [ [ 'preview.webp' => 'not a picture' ], 'themes_upload_package_preview' ],
	'no theme.json'																	=> [ [ 'theme.json' => null ], 'themes_export_rejected' ],
	'a description longer than a description may be'	=> [ [ 'theme.json' => json_encode( array_merge( json_decode( $files['theme.json'], true ), [ 'description' => str_repeat( 'd', \Nino\Blocks::MAX_DESCRIPTION + 1 ) ] ) ) ], 'themes_upload_package_words' ],
	'a name with a control character'								=> [ [ 'theme.json' => json_encode( array_merge( json_decode( $files['theme.json'], true ), [ 'name' => "Two\nlines" ] ) ) ], 'themes_upload_package_words' ],
	'a folder too deep'															=> [ [ 'blocks/a-block/images/deep/x.png' => 'x' ], 'themes_upload_package_path' ],
];
$told = [];

foreach( $bad as $what => [ $change, $code ] ) {

	$made = $files;

	foreach( $change as $path => $bytes )
		if( $bytes === null )
			unset( $made[$path] );
		else
			$made[$path] = $bytes;

	$folder = $put( 'hand-made', $made );
	$before = upFiles( $folder );
	[ $statusDry, $bodyDry ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made', 'check' => true ] );
	[ $statusFile, $bodyFile ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ] );
	$sentence = $sayings['en_US']['[[/_admin/error/'. ( $bodyDry['code'] ?? '' ). ']]'] ?? '';
	$told[$what] = $statusDry === 400 && ( $bodyDry['code'] ?? '' ) === $code && $statusFile === 400 && is_array( $bodyFile ) === true && ( $bodyFile['code'] ?? '' ) === $code && $sentence !== '' && upFiles( $folder ) === $before;
}

check( 'a theme that the kernel would refuse as a package is not exported - the answer is a 400 with the reason, for the question and for the file, and no file is sent: '. implode( '; ', array_keys( $bad ) ), array_values( array_unique( $told ) ) === [ true ] && count( $told ) === count( $bad ) );

// the limits of a package are those of the whole of it: what is more is said at once, with the words of the check, before another file is read
$bigRead = static function( array $names ): int {

	global $spied;

	return count( array_filter( (array) $spied, static fn( string $path ): bool => in_array( basename( $path ), $names, true ) === true && str_contains( $path, '/themes/hand-made/' ) === true && filesize( $path ) >= 1000000 ) );
};
$sized = [];

foreach( [
	'a file of more than a package may be'	=> [ [ 'README.md' => str_repeat( 'x', 5000000 ) ], 'themes_upload_package_long', 0 ],
	'files that are more together'					=> [ [ 'LICENSE.txt' => str_repeat( 'x', 2000000 ), 'README.md' => str_repeat( 'x', 2000000 ), 'preview.webp' => str_repeat( 'x', 2000000 ) ], 'themes_upload_package_bytes_many', 2 ],
	'more files than a package may have'		=> [ array_combine( array_map( static fn( int $at ): string => 'notes-'. $at. '.txt', range( 1, 405 ) ), array_fill( 0, 405, 'x' ) ), 'themes_upload_package_files_many', 400 ],
] as $what => [ $change, $code, $most ] ) {

	$made = array_merge( $files, $change );
	$folder = $put( 'hand-made', $made );
	$spied = [];
	[ $statusDry, $bodyDry ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made', 'check' => true ] );
	[ $statusFile, $bodyFile ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ] );
	$reads = $code === 'themes_upload_package_files_many' ? count( array_filter( $spied, static fn( string $path ): bool => str_contains( $path, '/themes/hand-made/notes-' ) === true ) ) / 2 : $bigRead( [ 'LICENSE.txt', 'README.md', 'preview.webp' ] ) / 2;
	$sized[$what] = $statusDry === 400 && ( $bodyDry['code'] ?? '' ) === $code && $statusFile === 400 && ( $bodyFile['code'] ?? '' ) === $code && $reads <= $most && is_array( $bodyDry ) === true && preg_match( '/(?:more|longer) than \d+/', (string) ( $bodyDry['error'] ?? '' ) ) === 1;
}

check( 'a package is held to its limits as a whole while the folder is read: a file of more than a package may be is "too long" with its limit, files that are more together than a package may be are "more than" it - the third of three files of two megabytes is not read - and more files than a package may have end the reading at the limit; each an answer of 400 with the reason, never a 500: '. implode( '; ', array_keys( $sized ) ), array_values( array_unique( $sized ) ) === [ true ] );

// a folder the reader refuses is no theme to export: the name of the folder and the key in its theme.json disagree
$mismatch = $files;
$mismatch['theme.json'] = json_encode( array_merge( json_decode( $files['theme.json'], true ), [ 'key' => 'ex-mismatch' ] ) );
$put( 'ex-folder', $mismatch );
[ $rejectedDry, $bodyRejected ] = upCall( $site, 'themes/export', [ 'theme' => 'own:ex-folder', 'check' => true ] );
[ $rejectedFile, $bodyRejectedFile ] = upCall( $site, 'themes/export', [ 'theme' => 'own:ex-folder' ] );
[ $mismatchKey ] = upCall( $site, 'themes/export', [ 'theme' => 'own:ex-mismatch' ] );

check( 'a folder that the reader of the own themes refuses - its name is not the key in its theme.json - is not exported: 400 with the reason, for the question and for the file, no archive with a root that is not its key; the key in its theme.json is no own theme either: 404',
	$rejectedDry === 400 && ( $bodyRejected['code'] ?? '' ) === 'themes_export_rejected' && $rejectedFile === 400 && is_array( $bodyRejectedFile ) === true && ( $bodyRejectedFile['code'] ?? '' ) === 'themes_export_rejected' && $mismatchKey === 404 );
\Nino\Filesystem::removeDir( $own. '/ex-folder' );
$folder = $put( 'hand-made', $files );

// what a package may weigh as an archive: noise in the pictures of blocks does not pack smaller, and the archive that is more than a package may be is refused
$noise = '';

if( function_exists( 'imagecreatetruecolor' ) === true ) {

	$picture = imagecreatetruecolor( 380, 380 );
	mt_srand( 7 );

	for( $y = 0; $y < 380; $y++ )
		for( $x = 0; $x < 380; $x++ )
			imagesetpixel( $picture, $x, $y, mt_rand( 0, 16777215 ) );

	ob_start();
	imagepng( $picture, null, 0 );
	$noise = (string) ob_get_clean();
}

$heavy = $files;

foreach( range( 1, 6 ) as $at ) {

	foreach( [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json' ] as $name )
		$heavy['blocks/heavy-'. $at. '/'. $name] = (string) file_get_contents( $root. '/_nino/Nino/Modules/Basis/blocks/split-image-text/'. $name );

	$heavy['blocks/heavy-'. $at. '/images/picture.png'] = $noise;
}

$put( 'hand-made', $heavy );
[ $largeStatus, $largeBody ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made', 'check' => true ] );

check( 'an archive that is more than a package may weigh is refused after it is packed: six pictures of noise in the blocks of the folder - each one a file the check takes - give an archive of more than two megabytes, 400 with the limit, no file',
	$noise !== '' && strlen( $noise ) < \Nino\Blocks::MAX_PICTURE && $largeStatus === 400 && ( $largeBody['code'] ?? '' ) === 'themes_export_large' );
$folder = $put( 'hand-made', $files );

// a link in the folder is not followed and not packed
$folder = $put( 'hand-made', $files );
$secret = ninoSandboxDir( $site ). '/outside-secret.txt';
file_put_contents( $secret, 'the secret of the project' );
symlink( $secret, $folder. '/README.md.link' );
unlink( $folder. '/README.md' );
symlink( $secret, $folder. '/README.md' );
[ $linked, $bodyLinked ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ] );

check( 'a link in the folder of an own theme is not followed and not packed: the theme is not exported, with the reason of a path that is none, and what the link points at is not in any answer',
	$linked === 400 && is_array( $bodyLinked ) === true && ( $bodyLinked['code'] ?? '' ) === 'themes_upload_package_path' && str_contains( json_encode( $bodyLinked ), 'the secret' ) === false && is_link( $folder. '/README.md' ) === true );

unlink( $folder. '/README.md' );
unlink( $folder. '/README.md.link' );
$folder = $put( 'hand-made', $files );

// what can be exported is an own theme: an installed theme, a key that is no key, a folder outside, and a theme that is not there
upChoose( $site, upPack( upFiles2( 'second-look' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$refused = [];

foreach( [ 'an installed theme' => 'second-look', 'the theme of the shipped ones' => 'nino', 'an own theme that is not there' => 'own:not-there', 'a key that leaves the folder' => 'own:../themes/hand-made', 'a key of a slash' => 'own:hand-made/..', 'no key' => 'own:', 'nothing' => '' ] as $what => $theme ) {

	[ $statusOne, $bodyOne ] = upCall( $site, 'themes/export', [ 'theme' => $theme ] );
	[ $statusTwo ] = upCall( $site, 'themes/export', [ 'theme' => $theme, 'check' => true ] );
	$refused[$what] = $statusOne === 404 && $statusTwo === 404 && is_array( $bodyOne ) === true && ( $bodyOne['code'] ?? '' ) === 'themes_export_unknown';
}

check( 'only an own theme is exported: an installed theme, the shipped one, an own theme that is not there, a key that leaves the folder and a key that is none are each a 404 and no file: '. implode( '; ', array_keys( $refused ) ), array_values( array_unique( $refused ) ) === [ true ] );

// the token and the right
$mine = upFiles( $folder );
[ $noToken ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ], 403 );
ninoUser( $site, 'builder@example.com', [ '/_admin/builder/manage', '/_admin/features/manage' ] );
[ $noRight ] = upCall( $site, 'themes/export', [ 'theme' => 'own:hand-made' ] );
[ $noRightUpload ] = upCall( $site, 'themes/upload-own', [ 'id' => str_repeat( '0', 32 ) ] );
[ $noRightDirect ] = ninoCall( $site, [ \Nino\Modules\Themes\Admin::class, 'apiExport' ], [ 'theme' => 'own:hand-made' ], null, 200 );
ninoUser( $site, 'designer@example.com', [ \Nino\Modules\Themes::PERM ] );

check( 'the export and the copy of a package are actions of the panel: a request that failed its token and an account that has other rights and not the right of the panel are refused, 403, and no file is sent',
	$noToken === 403 && $noRight === 403 && $noRightUpload === 403 && $noRightDirect === 403 && upFiles( $folder ) === $mine );

// any installed theme's part may be in a package: the parts of an installed theme under a licence that forbids copying are bundled as they are
$licenced = upFiles2( 'under-licence', '2.0.0' );
$licenced['LICENSE.txt'] = "All rights reserved. This theme may not be copied or handed on.\n";
upChoose( $site, upPack( $licenced, 'under-licence' ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
[ $copied, $copy ] = upCall( $site, 'themes/duplicate', [ 'theme' => 'under-licence', 'name' => 'My copy' ] );
unset( $site['./nino/themes/own'] );
[ $copyStatus, $copyBytes ] = upCall( $site, 'themes/export', [ 'theme' => 'own:my-copy' ] );
$copyFiles = is_string( $copyBytes ) === true ? $read( $copyBytes, 'my-copy' ) : [];

check( 'the parts of an installed theme are bundled freely: an own theme made of a theme that has a licence of its own is exported with the parts of that theme, byte for byte, whatever the licence says - no restriction is made on what is packed',
	$copied === 200 && $copyStatus === 200 && is_array( $copyFiles ) === true && ( $copyFiles['parts/button/part.json'] ?? 'x' ) === $licenced['parts/button/part.json'] && ( $copyFiles['parts/button/part.css'] ?? 'x' ) === $licenced['parts/button/part.css']
	&& \Nino\Packages::check( $site, \Nino\Packages::THEME, $copyFiles ) === null );

// a package copied as an own theme
$site = upSite();
$_SESSION = [];
$own = ninoSandboxDir( $site ). '/private/themes';
$packages = ninoSandboxDir( $site ). '/private/packages';
$files = upFiles2( 'second-look', '1.2.0' );
$packed = upPack( $files );
upChoose( $site, $packed );
$id = upHeld();
$before = upWritten( $site );
[ $status, $done ] = upCall( $site, 'themes/upload-own', [ 'id' => $id ] );
[ , $list ] = upCall( $site, 'themes/list' );
$card = array_values( array_filter( $list['themes'] ?? [], static fn( array $one ): bool => $one['key'] === 'own:zweiter-blick' ) )[0] ?? [];
$copyFolder = $own. '/zweiter-blick';
$now = upFiles( $copyFolder );
$wanted = array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), array_diff_key( $files, [ 'theme.json' => 1 ] ) );
$wanted = array_combine( array_map( static fn( string $path ): string => '/'. $path, array_keys( $wanted ) ), array_values( $wanted ) );
ksort( $wanted );
$theme = (array) json_decode( (string) @file_get_contents( $copyFolder. '/theme.json' ), true );
$first = (array) json_decode( $files['theme.json'], true );

check( 'a package that was shown is copied as an own theme: its folder is made in private/themes/ with every file of the package byte for byte, and the theme.json is the package\'s with nothing made over but the key of the folder, which is the one its name in the language of the site makes - the answer says which own theme it is, and it is in the list as one',
	$status === 200 && ( $done['theme'] ?? '' ) === 'own:zweiter-blick' && array_diff_key( $now, [ '/theme.json' => 1 ] ) === $wanted && $theme === array_merge( $first, [ 'key' => 'zweiter-blick' ] ) && ( $card['kind'] ?? '' ) === 'own' && ( $card['active'] ?? true ) === false );

$after = upWritten( $site );
$changed = array_keys( array_diff_assoc( $after, $before ) + array_diff_assoc( $before, $after ) );

check( 'only the folder of the own theme is written, and the website is not touched: nothing is installed - there is no package folder, no uploaded theme, no block of the project - nothing is applied, and the state of the website has no theme - only its record of the change is written; the bytes that were held are let go of',
	$changed !== [] && array_filter( $changed, static fn( string $path ): bool => str_starts_with( $path, '/private/themes/zweiter-blick/' ) === false && $path !== '/private/data/theme/current/saved.json' ) === [] && is_dir( $packages ) === false
	&& array_key_exists( 'active', $list ) === true && $list['active'] === null && ( \Nino\Modules\Themes\Store::read( $site )['active']['theme'] ?? '' ) === '' && isset( $_SESSION[Upload::SESSION] ) === false
	&& array_filter( array_column( $list['themes'] ?? [], 'origin', 'key' ), static fn( string $origin ): bool => $origin === 'uploaded' ) === [] && is_file( ninoSandboxDir( $site ). '/private/data/theme/current/theme/theme.json' ) === false );

// the folder is a theme the reader takes and the check takes, and it is exported as the package it came from
[ $exportStatus, $exportBytes ] = upCall( $site, 'themes/export', [ 'theme' => 'own:zweiter-blick' ] );
$again = is_string( $exportBytes ) === true ? $read( $exportBytes, 'zweiter-blick' ) : [];

check( 'the copy is whole: the reader of the own themes takes it - with its parts - and exported it gives the package again, file for file',
	$exportStatus === 200 && is_array( $again ) === true && array_diff_key( $again, [ 'theme.json' => 1 ] ) == array_diff_key( $files, [ 'theme.json' => 1 ] ) && ( (array) json_decode( $again['theme.json'] ?? '', true ) )['key'] === 'zweiter-blick' );
check( '...and an own theme whose parts are the package\'s is one the panel can open: its card has the part of the package',
	array_keys( $card['parts'] ?? [] ) === [ 'button' ] && ( $card['parts']['button']['ok'] ?? false ) === true );

// the same package once more: its name is taken, and that is said at the name; the bytes stay held and nothing is written
upChoose( $site, $packed );
$id = upHeld();
$before = upWritten( $site );
[ $taken, $bodyTaken ] = upCall( $site, 'themes/upload-own', [ 'id' => $id ] );
$stays = upHeld() === $id && upWritten( $site ) === $before;
[ $other, $bodyOther ] = upCall( $site, 'themes/upload-own', [ 'id' => $id, 'name' => 'Another look' ] );
$otherTheme = (array) json_decode( (string) @file_get_contents( $own. '/another-look/theme.json' ), true );

check( 'a name that an own theme has is a 409 at the field "name", the bytes stay held and nothing is written; asked again under another name the folder has the key of that name, its theme.json has that key and that name, and the rest of the package is as it was',
	$taken === 409 && ( $bodyTaken['code'] ?? '' ) === 'themes_name_taken' && ( $bodyTaken['field'] ?? '' ) === 'name' && $stays === true && $other === 200 && ( $bodyOther['theme'] ?? '' ) === 'own:another-look'
	&& ( $otherTheme['key'] ?? '' ) === 'another-look' && ( $otherTheme['name'] ?? '' ) === 'Another look' && ( $otherTheme['description'] ?? '' ) === $first['description'] && ( $otherTheme['version'] ?? '' ) === '1.2.0'
	&& array_diff_key( upFiles( $own. '/another-look' ), [ '/theme.json' => 1 ] ) === $wanted && isset( $_SESSION[Upload::SESSION] ) === false );

// names that an own theme may not have, and a package that is not the one that was shown
upChoose( $site, $packed );
$id = upHeld();
$before = upWritten( $site );
$names = [];

foreach( [ 'markup' => 'A <b>look</b>', 'a line break' => "Two\nlines", 'too long' => str_repeat( 'n', Mix::NAME_LENGTH + 1 ) ] as $what => $name ) {

	[ $statusName, $bodyName ] = upCall( $site, 'themes/upload-own', [ 'id' => $id, 'name' => $name ] );
	$names[$what] = $statusName === 400 && ( $bodyName['code'] ?? '' ) === 'themes_import_name' && ( $bodyName['field'] ?? '' ) === 'name';
}

[ $unknown ] = upCall( $site, 'themes/upload-own', [ 'id' => str_repeat( '0', 32 ), 'name' => 'Nothing' ] );
[ $noTokenOwn ] = upCall( $site, 'themes/upload-own', [ 'id' => $id, 'name' => 'No token' ], 403 );
// the action itself, past the dispatcher of the workbench: each guards itself
$directly = static function( string $method, array $data, int $before ) use ( &$site ): int {
	return ninoCall( $site, [ \Nino\Modules\Themes\Admin::class, $method ], $data, null, $before )[0];
};
$noTokenDirectOwn = $directly( 'apiUploadOwn', [ 'id' => $id, 'name' => 'No token' ], 403 );
ninoUser( $site, 'builder@example.com', [ '/_admin/builder/manage', '/_admin/features/manage' ] );
[ $noRightOwn ] = upCall( $site, 'themes/upload-own', [ 'id' => $id, 'name' => 'No right' ] );
$noRightDirectOwn = $directly( 'apiUploadOwn', [ 'id' => $id, 'name' => 'No right' ], 200 );
ninoUser( $site, 'designer@example.com', [ \Nino\Modules\Themes::PERM ] );
$_SESSION[Upload::SESSION]['bytes'] = $archives['a php file'] ?? 'x';
[ $altered ] = upCall( $site, 'themes/upload-own', [ 'id' => $id, 'name' => 'Altered' ] );

check( 'a name that has markup, a line break or more characters than a name may have is a 400 at the field "name" with its sentence, a key that is not held is a 403, held bytes that are not the ones that were held are a 403, a request without its token or without the right of the panel is a 403 - and nothing is written: '. implode( '; ', array_keys( $names ) ),
	array_values( array_unique( $names ) ) === [ true ] && $unknown === 403 && $altered === 403 && $noTokenOwn === 403 && $noRightOwn === 403 && $noTokenDirectOwn === 403 && $noRightDirectOwn === 403 && upWritten( $site ) === $before );

// where the key of the package is the key of an installed theme, the copy is still possible: the own theme has a key of its own
$site = upSite();
$_SESSION = [];
$own = ninoSandboxDir( $site ). '/private/themes';
upChoose( $site, $packed );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
upChoose( $site, $packed );
[ $both ] = upCall( $site, 'themes/upload-own', [ 'id' => upHeld() ] );
[ , $list ] = upCall( $site, 'themes/list' );

check( 'a package whose key is the key of an installed theme is copied all the same - the own theme has its own key in its own folder, and the installed theme is as it was: both are in the list',
	$both === 200 && is_dir( $own. '/zweiter-blick' ) === true && array_column( $list['themes'] ?? [], 'kind', 'key' ) === [ 'nino' => 'theme', 'second-look' => 'theme', 'own:zweiter-blick' => 'own' ] );

// a theme that is written by hand: the files that are not of a package never get into the folder of an own theme
$stray = \Nino\Modules\Themes\Mix::place( $site, 'stray', [ 'theme.json' => '{}', '../outside.php' => '<?php' ] );
$strays = \Nino\Modules\Themes\Mix::place( $site, 'stray', [ 'theme.json' => '{}', 'parts/button/part.php' => '<?php' ] );

check( 'a folder is written whole or not at all: files that a package does not have - a path that leaves the folder, a script - are refused and nothing is made',
	is_string( $stray ) === true && is_string( $strays ) === true && file_exists( $own. '/stray' ) === false && file_exists( ninoSandboxDir( $site ). '/private/outside.php' ) === false && array_filter( (array) scandir( $own ), static fn( string $entry ): bool => str_starts_with( $entry, '.stray' ) ) === [] );

// there is a limit to the own themes: where the folders are as many as there may be, the copy is refused and nothing is made
for( $count = 1; $count <= \Nino\Modules\Themes\Store::MAX_OWN; $count++ )
	@mkdir( $own. '/filler-'. $count, 0755, true );

unset( $site['./nino/themes/own'] );
$full = array_values( array_filter( (array) scandir( $own ), static fn( string $entry ): bool => $entry[0] !== '.' ) );
upChoose( $site, $packed );
$id = upHeld();
[ $limited, $bodyLimited ] = upCall( $site, 'themes/upload-own', [ 'id' => $id, 'name' => 'One more' ] );

check( 'where the own themes are as many as there may be, a package is not copied: a 409 with the number, the bytes stay held, and no folder is made',
	$limited === 409 && ( $bodyLimited['code'] ?? '' ) === 'themes_limit' && upHeld() === $id && is_dir( $own. '/one-more' ) === false && array_values( array_filter( (array) scandir( $own ), static fn( string $entry ): bool => $entry[0] !== '.' ) ) === $full );


echo "\nH. The export of an own theme whose key an installed theme has\n";

// an own theme and an installed one may have the same key - the own one is told by "own:" - and the package that is made of the own one meets the installed one when it is uploaded on this site:
// it replaces nothing unasked, and the list tells the own theme that its key is an installed theme's
$site = upSite();
upChoose( $site, upPack( upLight( 'dupe', '1.0.0' ), 'dupe' ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
$ownRoot = ninoSandboxDir( $site ). '/private/themes';

foreach( [ 'dupe', 'nino' ] as $same ) {

	@mkdir( $ownRoot. '/'. $same. '/parts', 0755, true );
	file_put_contents( $ownRoot. '/'. $same. '/theme.json', json_encode( [ 'key' => $same, 'version' => '1.0.0', 'name' => ucfirst( $same ). ' own', 'description' => 'Own' ] ) );
}

unset( $site['./nino/themes/own'], $site['./nino/themes/read'] );
[ , $listed ] = upCall( $site, 'themes/list' );
$sameKeys = [];

foreach( $listed['themes'] as $one )
	if( $one['kind'] === 'own' )
		$sameKeys[$one['key']] = $one['sameKey'] ?? 'missing';

ksort( $sameKeys );
$installedDir = \Nino\Packages::dir( $site, \Nino\Packages::THEME, 'dupe' );
$installedBefore = upFiles( $installedDir );
$outcome = [];

foreach( [ 'dupe' => 'themes_upload_exists', 'nino' => 'themes_upload_taken_shipped' ] as $same => $code ) {

	$export = \Nino\Modules\Themes\Package::files( $site, $same );
	unset( $_SESSION[Upload::SESSION] );
	upChoose( $site, upPack( (array) ( $export['files'] ?? [] ), $same ) );
	[ $status, $body ] = upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
	$outcome[$same] = [ $status, $body['code'] ?? '' ];
}

check( 'the list tells an own theme whose key an installed theme has what that theme is - by the key, "uploaded" for a package put in place here, "shipped" for the one of the feature - and the upload of the package of the own theme replaces nothing unasked: the one that was uploaded is not replaced and the shipped one never, a 409 each and the installed folder is as it was',
	$sameKeys === [ 'own:dupe' => 'uploaded', 'own:nino' => 'shipped' ] && $outcome === [ 'dupe' => [ 409, 'themes_upload_exists' ], 'nino' => [ 409, 'themes_upload_taken_shipped' ] ] && upFiles( $installedDir ) === $installedBefore && $installedBefore !== [] );


ninoDone( $site );
