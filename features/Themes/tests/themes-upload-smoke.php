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
 *
 *	Usage: php features/Themes/tests/themes-upload-smoke.php
 *				 NINO_ROOT=../nino php features/Themes/tests/themes-upload-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

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

/** What a package could change of a project: the packages, the blocks, the stylesheet and the state of the themes - and not the log, the locks and the cache a request writes whatever it does */
function upWritten( array &$appData ): array {

	return array_filter( upFiles( ninoSandboxDir( $appData ) ), static fn( string $path ): bool => preg_match( '~^/(?:private/packages|private/blocks|private/assets|assets|private/data/theme/(?:state|copies|uploads\.json))~', $path ) === 1, ARRAY_FILTER_USE_KEY );
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
	( $blocks['second-split']['origin'] ?? '' ) === 'second-look' && ( $blocks['second-foot']['origin'] ?? '' ) === 'second-look' && ( $blocks['second-foot']['category'] ?? '' ) === 'frame'
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
[ $own ] = upCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Mine', 'parts' => [ 'button' => [ 'from' => 'second-look' ] ], 'css' => '' ] ] );
[ $other ] = upCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
[ $refusedOwn, $bodyOwn ] = upCall( $site, 'themes/remove', [ 'theme' => 'second-look' ] );

check( 'a theme is not taken away while the website shows it - 409, and the folder is there - nor while an own theme takes a part from it: the answer names the own theme',
	$applied === 200 && $refusedActive === 409 && ( $bodyActive['code'] ?? '' ) === 'themes_upload_in_use_active' && $stays === true && $own === 200 && $other === 200 && $refusedOwn === 409 && ( $bodyOwn['code'] ?? '' ) === 'themes_upload_in_use_own' && in_array( 'Mine', $bodyOwn['params'] ?? [], true ) === true
	&& is_file( $dir. '/second-look/theme.json' ) === true );

[ , $listed ] = upCall( $site, 'themes/list' );
$ownKey = (string) ( array_values( array_filter( $listed['themes'], static fn( array $one ): bool => $one['kind'] === 'own' ) )[0]['key'] ?? '' );
upCall( $site, 'themes/delete', [ 'theme' => $ownKey, 'base' => (string) ( upCall( $site, 'themes/open', [ 'theme' => $ownKey ] )[1]['stamp'] ?? '' ) ] );
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

// the own theme of the last state holds the theme as well, and so does an own theme of the state as it is - each by its name
$site = upSite();
$_SESSION = [];
upChoose( $site, upPack( upLight( 'light-one', '1.0.0' ) ) );
upCall( $site, 'themes/upload', [ 'id' => upHeld() ] );
upSpare( $site );
upCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Mine', 'parts' => [ 'button' => [ 'from' => 'light-one' ] ], 'css' => '' ] ] );
[ $heldNow, $bodyNow ] = upCall( $site, 'themes/remove', [ 'theme' => 'light-one' ] );
upCall( $site, 'themes/apply', [ 'theme' => 'nino' ] );
upCall( $site, 'themes/delete', [ 'theme' => 'own:mine', 'base' => (string) ( upCall( $site, 'themes/open', [ 'theme' => 'own:mine' ] )[1]['stamp'] ?? '' ) ] );
[ $heldBefore, $bodyBefore ] = upCall( $site, 'themes/remove', [ 'theme' => 'light-one' ] );
upCall( $site, 'themes/apply', [ 'theme' => 'spare' ] );
[ $freed ] = upCall( $site, 'themes/remove', [ 'theme' => 'light-one' ] );

check( 'an own theme that takes a part from a theme holds it - in the state as it is and in the last state - and each answer names the own theme; once neither has it, the theme is taken away',
	$heldNow === 409 && ( $bodyNow['code'] ?? '' ) === 'themes_upload_in_use_own' && ( $bodyNow['params'][0] ?? '' ) === 'Mine' && $heldBefore === 409 && ( $bodyBefore['code'] ?? '' ) === 'themes_upload_in_use_last_own' && ( $bodyBefore['params'][0] ?? '' ) === 'Mine' && $freed === 200 );

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

foreach( [ 'project', 'basis', 'own', 'themes', 'pack' ] as $reserved ) {

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
	( $badRead['css'] ?? 'x' ) === '' && ( \Nino\Modules\Themes\Reader::explain( (string) ( $badRead['cssProblem'] ?? '' ) )['code'] ?? '' ) === 'theme-css-any' && isset( $badMix['css'] ) === false && str_contains( $badSheet, 'color: red' ) === false && str_contains( $badSheet, 'color: blue' ) === false
	&& str_contains( $badOwnSheet, 'color: red' ) === false && ( $badOwn['css'] ?? '' ) === '' && ( $badCard['css']['why']['code'] ?? '' ) === 'theme-css-any' );

// a package whose css breaks the rule is not put in place at all
$nastyCss = upRich( 'nasty-css', '1.0.0' );
$nastyCss['theme.css'] = "body { color: red }\n";
$before = upWritten( $site );
[ $nastyStatus, $nastyBody ] = upChoose( $site, upPack( $nastyCss ) );

check( 'a package whose theme.css breaks the rule of a theme is refused before it is held, with the file named, and nothing is written',
	$nastyStatus === 400 && ( $nastyBody['code'] ?? '' ) === 'themes_upload_package_theme_css_any' && isset( $_SESSION[Upload::SESSION] ) === false && upWritten( $site ) === $before );

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
	array_map( static fn( string $code ): string => 'themes_upload_'. $code, [ 'large', 'nofile', 'unknown', 'exists', 'taken_shipped', 'taken_catalogue', 'taken_project', 'not_uploaded', 'in_use_active', 'in_use_own', 'in_use_last', 'in_use_last_own', 'busy', 'key_reserved', 'key_like', 'not_newer_same', 'not_newer_older', 'replace_held_active', 'replace_held_own', 'replace_held_last', 'replace_held_last_own', 'too_many', 'block_displaces' ] )
);
$unsaid = array_filter( $codes, static fn( string $code ): bool => isset( $sayings['de_DE']['[[/_admin/error/'. $code. ']]'], $sayings['en_US']['[[/_admin/error/'. $code. ']]'] ) === false );

check( 'every refusal of the upload has its sentence in both languages - each code of the reader of archives, each of the rule of the packages, each of the rules of the panel - and each code is one the panel looks a sentence up by: letters, digits and underscores', $unsaid === [] && array_filter( $codes, static fn( string $code ): bool => preg_match( '/^[a-z][a-z0-9_]*$/D', $code ) !== 1 ) === [] );

ninoDone( $site );
