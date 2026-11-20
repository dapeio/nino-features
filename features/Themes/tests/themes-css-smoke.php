<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themes-css-smoke.php	Contract test for the own css of the project in the feature Themes - the tab "Own CSS", the file private/assets/style.css
 *												and the right that writes it:
 *
 *													A. the pieces: the tab, its right and its words, the actions, the files of the tab, the file at one place
 *													B. the right: no tab, no read, no write and no preview without it, a request that failed its token, the right of the panel alone
 *													C. the text: what is read, what is written and where, the stamp, a second session, a path that is not asked for
 *													D. what is refused: the size, the bytes that are no text, what could end a style element, braces that are not closed - and what is not
 *													E. the website: the file is a file of the bundle in the layer project after the theme, the stamp of the bundle moves, and a theme
 *													   that is chosen, a last state that is restored and a theme that is made leave it as it is - it is in no theme and in no state
 *													F. the preview: the text as it is typed, for the session and the account that hold the token, and the public bundle untouched
 *													G. the script of the tab, over a stand-in of the page
 *
 *												Runs against an isolated sandbox directory with the kernel's own Basis; the themes are the fixtures of tests/fixtures/themes.
 *
 *	Usage: php features/Themes/tests/themes-css-smoke.php
 *				 NINO_ROOT=../nino php features/Themes/tests/themes-css-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Themes\ProjectCss;

$featureDir	= dirname( __DIR__ );
$sandboxes	= [];

const CSS_BUNDLE = '/.cache/style.css';
const CSS_MARK = '.project-own-mark { color: rgb( 1 2 3 ); }';

/**
 *	Boot a site the way \Nino\init() does: the modules it lists, its components and standard parts, the files its features name for the bundles, then the init()
 *	of every module. The themes of the sandbox are the fixtures
 *
 *	@param		array 		&$appData			(reference) A sandbox's app data
 *
 *	@return 	void
 */
function cssBoot( array &$appData, bool $themes = true ): void {

	// The bundle of a project as the setup leaves it: the kernel's stylesheet and the project's own
	$appData['/nino/html/assets'] = [ CSS_BUNDLE => [ '/_nino/Nino.css', '/assets/style.css' ], '/.cache/script.js' => [ '/_nino/Nino.js' ] ];
	$appData['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Basis' ], $themes === true ? [ '\\Nino\\Modules\\Themes' ] : [] );
	$directory = ninoSandboxDir( $appData ). '/themes';

	\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Themes\Reader::ROOTS, static function( array &$appData, array $roots ) use ( $directory ): array {
		return array_merge( $roots, [ $directory ] );
	} );

	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Features::registerParts( $appData );
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
}

/**
 *	A site as a sandbox of its own, with the fixture themes and an account that holds the right of the panel and the right of the own css
 *
 *	@return 	array											$appData
 */
function cssSite(): array {

	global $root, $sandboxes;

	$appData = ninoSandbox( 'themes-css' );
	$sandboxes[] = ninoSandboxDir( $appData );
	$appData['./nino/filesystem/path'] = $root;
	\Nino\Filesystem::copyDir( __DIR__. '/fixtures/themes', ninoSandboxDir( $appData ). '/themes' );
	cssBoot( $appData );
	ninoUser( $appData, 'writer@example.com', [ \Nino\Modules\Themes::PERM, \Nino\Modules\Themes::CSS_PERM ] );

	// The first request of the workbench of a day writes what the workbench writes of itself - the daily backup, the words of its base unit -, which is nobody's matter here
	cssCall( $appData, 'themes/css' );

	return $appData;
}

/**
 *	The same project as another request: the files stay, everything a request builds is built again
 *
 *	@param		array			$old					The app data of the project
 *
 *	@return 	array											$appData
 */
function cssReboot( array $old, bool $themes = true ): array {

	$new = [ './nino/uid' => $old['./nino/uid'] ];
	\Nino\AppData::prepare( $new );

	foreach( [ './nino/filesystem/path', './nino/filesystem/configpath', './nino/filesystem/contentpath', './nino/filesystem/publicpath', './nino/locales/current', '/nino/locales/native', '/nino/locales/available', '/nino/dir', '/nino/install/texts', './nino/test/sandbox', '/nino/auth/user', './nino/auth/current' ] as $key )
		if( isset( $old[$key] ) === true )
			$new[$key] = $old[$key];

	cssBoot( $new, $themes );

	return $new;
}

/**
 *	One action of the API, as the workbench posts it
 *
 *	@param		array 		&$appData			(reference) A project
 *	@param		string		$action				eg. 'themes/css-save'
 *	@param		array			$data
 *	@param		int				$before				The status the request has when the action is reached: 403 for one that failed its token
 *
 *	@return 	array											[ status, body ]
 */
function cssCall( array &$appData, string $action, array $data = [], int $before = 200 ): array {

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data + [ 'locale' => 'en_US' ], $action, $before );

	return [ $status, is_array( $body ) === true ? json_decode( (string) json_encode( $body ), true ) : $body ];
}

/** Every file of the sandbox, path => hash of its bytes, but what a request writes whatever it does: the log, the locks, the cache, the backups, the drafts */
function cssFiles( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$out = [];

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( $file->isFile() === true && preg_match( '~^/(?:private/data/(?:\.locks|cache|logs|backups|theme/\.preview)|private/\.?logs)|/logs\.[0-9-]+\.php$~', substr( (string) $file, strlen( $dir ) ) ) !== 1 )
			$out[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );

	ksort( $out );

	return $out;
}

/** The text of the block of a layer in a bundle: from its @layer line to the next one */
function cssBlock( string $bundle, string $layer ): string {

	$at = strpos( $bundle, '@layer '. $layer. ' {' );

	if( $at === false )
		return '';

	$end = strpos( $bundle, '@layer ', $at + 1 );

	return substr( $bundle, $at, $end === false ? null : $end - $at );
}

$site = cssSite();
$sheet = ninoSandboxDir( $site ). '/private/assets/style.css';
$save = static function( string $css, ?string $base = null, bool $force = false ) use ( &$site ): array {

	$base ??= hash( 'sha256', (string) @file_get_contents( ninoSandboxDir( $site ). '/private/assets/style.css' ) );

	return cssCall( $site, 'themes/css-save', [ 'css' => $css, 'base' => $base, 'force' => $force ] );
};

// ---------------------------------------------------------------------------------------------------------------------------------
echo "A. The pieces\n";

$tabs = \Nino\Admin\Admin::panels( $site )['themes']['tabs'] ?? [];
$words = [ 'en_US' => require $featureDir. '/text/en_US.php', 'de_DE' => require $featureDir. '/text/de_DE.php' ];
$script = (string) file_get_contents( $featureDir. '/assets/admin-css.js' );
preg_match_all( "/\\bword\\(\\s*'([a-z0-9\\/-]+)'/", $script, $asked );
$keys = array_merge( array_map( static fn( string $key ): string => '/_admin/themes/css/'. $key, array_filter( array_unique( $asked[1] ), static fn( string $key ): bool => str_ends_with( $key, '/' ) === false ) ), [ ProjectCss::nav()[1], ProjectCss::tab() ] );
$missing = array_filter( $keys, static fn( string $key ): bool => ( $words['en_US']['[['. $key. ']]'] ?? '' ) === '' || ( $words['de_DE']['[['. $key. ']]'] ?? '' ) === '' );

check( 'the pane of the themes has the tab of the own css after the fonts and before the settings, under the right /_admin/themes/css - which is not the right of the panel -, with the label of the right and the name of the tab as words of both languages',
	array_keys( $tabs ) === [ 'themes-fonts', 'themes-css', 'themes-settings' ] && ( $tabs['themes-css']['perm'] ?? '' ) === '/_admin/themes/css' && \Nino\Modules\Themes::CSS_PERM !== \Nino\Modules\Themes::PERM
	&& $tabs['themes-css']['class'] === ProjectCss::class && $tabs['themes-css']['panes'] === [ 'themes-css-list' ] && $tabs['themes-css']['parent'] === 'themes'
	&& ( $words['en_US']['[['. ProjectCss::nav()[1]. ']]'] ?? '' ) !== ( $words['en_US']['[['. ProjectCss::tab(). ']]'] ?? '' ) && $missing === [] );

$assets = ProjectCss::assets();

check( 'the tab brings three actions and two files of the feature, a script that every word of is a fill of both languages and a stylesheet scoped to the mount of the tab',
	array_keys( ProjectCss::actions() ) === [ 'themes/css', 'themes/css-save', 'themes/css-preview' ] && count( $assets ) === 2 && str_ends_with( $assets[0], '/features/Themes/assets/admin-css.js' ) === true && str_ends_with( $assets[1], '/features/Themes/assets/admin-css.css' ) === true
	&& is_file( $featureDir. '/assets/admin-css.js' ) === true && is_file( $featureDir. '/assets/admin-css.css' ) === true && count( $asked[1] ) > 4 && str_contains( $script, "const MOUNT = 'themes-css-list';" ) === true
	&& preg_match_all( '/^(?:#themes-css-list[^{]*)\{/m', (string) file_get_contents( $featureDir. '/assets/admin-css.css' ) ) === preg_match_all( '/^[^\s@\/*}][^{]*\{/m', (string) file_get_contents( $featureDir. '/assets/admin-css.css' ) ) );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The right\n";

ninoUser( $site, 'panel@example.com', [ \Nino\Modules\Themes::PERM ] );
$panels = \Nino\Admin\Admin::visiblePanels( $site );
$shown = array_keys( $panels['themes']['tabs'] ?? [] );
$before = cssFiles( $site );
$denied = [ cssCall( $site, 'themes/css' )[0], cssCall( $site, 'themes/css-save', [ 'css' => CSS_MARK, 'base' => hash( 'sha256', '' ) ] )[0], cssCall( $site, 'themes/css-save', [ 'css' => CSS_MARK, 'base' => '', 'force' => true ] )[0], cssCall( $site, 'themes/css-preview', [ 'css' => CSS_MARK ] )[0] ];

check( 'an account with the right of the panel and not the right of the own css has no tab of it, and every action of it is refused - 403 - with nothing written; the tab of the fonts is there',
	$shown === [ 'themes-fonts' ] && $denied === [ 403, 403, 403, 403 ] && cssFiles( $site ) === $before && is_file( $sheet ) === false );

$offered = array_values( array_filter( \Nino\Modules\Users\Admin::permOptions( $site ), static fn( array $option ): bool => $option['perm'] === \Nino\Modules\Themes::CSS_PERM ) );

check( 'the right is offered to the roles with its own label - write the own css of the project - apart from the right of the panel, which it is not part of and which no role has by itself: the editor role has neither',
	count( $offered ) === 1 && $offered[0]['label'] === ProjectCss::nav()[1] && $offered[0]['offered'] === true && in_array( \Nino\Modules\Themes::CSS_PERM, \Nino\Modules\Users\Roles::defaults( $site )['editor']['perms'], true ) === false
	&& in_array( \Nino\Modules\Themes::PERM, \Nino\Modules\Users\Roles::defaults( $site )['editor']['perms'], true ) === false );

ninoUser( $site, 'full@example.com' );

check( 'full access has the right as it has every right: the tab is in the pane and the actions answer',
	in_array( 'themes-css', array_keys( \Nino\Admin\Admin::visiblePanels( $site )['themes']['tabs'] ?? [] ), true ) === true && cssCall( $site, 'themes/css' )[0] === 200 );

ninoUser( $site, 'css-only@example.com', [ \Nino\Modules\Themes::CSS_PERM ] );

check( 'the right of the own css is a right of its own: an account that has it and not the right of the panel reads and writes, and the panel of the themes is not its own',
	cssCall( $site, 'themes/css' )[0] === 200 && cssCall( $site, 'themes/themes' )[0] !== 200 && cssCall( $site, 'themes/list' )[0] === 403 );

ninoUser( $site, 'writer2@example.com', [ \Nino\Modules\Themes::PERM, \Nino\Modules\Themes::CSS_PERM ] );
$before = cssFiles( $site );
$untoken = [ cssCall( $site, 'themes/css', [], 403 )[0], cssCall( $site, 'themes/css-save', [ 'css' => CSS_MARK, 'base' => hash( 'sha256', '' ) ], 403 )[0], cssCall( $site, 'themes/css-preview', [ 'css' => CSS_MARK ], 403 )[0] ];
$direct = static fn( string $method, array $data ): int => ninoCall( $GLOBALS['site'], [ ProjectCss::class, $method ], $data, null, 403 )[0];

check( 'a request that failed its token is refused by each of the three actions - by the dispatcher of the workbench and by the action itself - and nothing is written',
	$untoken === [ 403, 403, 403 ] && [ $direct( 'apiCss', [] ), $direct( 'apiSave', [ 'css' => CSS_MARK, 'base' => '' ] ), $direct( 'apiPreview', [ 'css' => CSS_MARK ] ) ] === [ 403, 403, 403 ] && cssFiles( $site ) === $before && is_file( $sheet ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The text\n";

$before = cssFiles( $site );
[ $status, $read ] = cssCall( $site, 'themes/css' );

check( 'the text of a project that has none is empty with the stamp of the empty text, the limit and the pages of the preview; asking writes nothing - no file, no lock that stays, no state',
	$status === 200 && $read['css'] === '' && $read['stamp'] === hash( 'sha256', '' ) && $read['max'] === 262144 && $read['exists'] === false && $read['problem'] === '' && is_array( $read['pages'] ) === true && cssFiles( $site ) === $before && is_file( $sheet ) === false
	&& array_filter( array_keys( cssFiles( $site ) ), static fn( string $path ): bool => str_starts_with( $path, '/private/assets/' ) === true ) === [] );

// A file that is there already - what the setup leaves, or what somebody wrote by hand - is what the tab shows, whatever the rule says of it
$hand = static function( string $css ) use ( &$site ): array {
	\Nino\Filesystem::putFileContent( $site, '/assets/style.css', $css );

	return cssCall( $site, 'themes/css' )[1];
};
$markup = $hand( "<!-- old -->\na { color: red; }\n" );
$open = $hand( "a { color: red;\n" );
$setup = $hand( (string) file_get_contents( $root. '/_admin/install/library/base/assets/style.css' ) );
$hand( "<!-- old -->\na { color: red; }\n" );
[ $keepsMarkup ] = $save( "<!-- old -->\na { color: red; }\n", hash( 'sha256', "<!-- old -->\na { color: red; }\n" ) );
$onDisk = (string) file_get_contents( $sheet );
$hand( "<!-- old -->\na { color: red; }\n" );
[ $fixedStatus ] = $save( "a { color: red; }\n", hash( 'sha256', "<!-- old -->\na { color: red; }\n" ) );

check( 'a file that breaks the rule - a comment mark of html, braces that are not closed - is shown as it is, and it is saved only when the text keeps the rule; what the setup leaves in the file is shown as well; nothing is replaced that was not shown',
	$markup['css'] === "<!-- old -->\na { color: red; }\n" && $markup['problem'] === '' && $open['css'] === "a { color: red;\n" && $open['problem'] === '' && $setup['css'] !== '' && $setup['problem'] === '' && str_contains( $setup['css'], 'This site\'s own stylesheet' ) === true
	&& $keepsMarkup === 400 && $onDisk === "<!-- old -->\na { color: red; }\n" && $fixedStatus === 200 && file_get_contents( $sheet ) === "a { color: red; }\n" );

$big = $hand( str_repeat( 'a', 262145 ) );
$nul = $hand( "a { content: \"\0\"; }" );
$utf = $hand( "a { content: \"\xff\"; }" );

check( 'a file that cannot be shown - longer than the limit, with a NUL byte, with bytes that are no utf-8 - is not shown: the answer names the rule and has no text, and the stamp is the one of the bytes that are there, so that a save is made over what was not seen only by one who says so with force',
	$big['css'] === '' && $big['problem'] === 'themes_css_big' && $nul['css'] === '' && $nul['problem'] === 'themes_css_nul' && $utf['css'] === '' && $utf['problem'] === 'themes_css_utf8'
	&& $big['stamp'] === hash( 'sha256', str_repeat( 'a', 262145 ) ) && $big['exists'] === true );
unlink( $sheet );


$text = "/* Own css */\n". CSS_MARK. "\nbody { margin: 0; }\n";
[ $status, $saved ] = $save( $text );

check( 'a text that is saved is the file, byte for byte: private/assets/style.css, and the answer gives the stamp of those bytes - which is what the next read gives',
	$status === 200 && $saved['saved'] === true && file_get_contents( $sheet ) === $text && $saved['stamp'] === hash( 'sha256', $text ) && cssCall( $site, 'themes/css' )[1]['stamp'] === $saved['stamp'] && cssCall( $site, 'themes/css' )[1]['css'] === $text );

$after = cssFiles( $site );

check( 'and the file is the one place it is written to: nothing else of the project changed but that file - not config.php, not a template, not the state of the themes',
	array_keys( array_diff_assoc( $after, $before ) ) === [ '/private/assets/style.css' ] && array_diff_key( $before, $after ) === [] );

$before = cssFiles( $site );
[ $status ] = cssCall( $site, 'themes/css-save', [ 'css' => "a { color: red; }\n", 'base' => hash( 'sha256', $text ), 'file' => '../../config.php', 'path' => '/config.php', 'target' => '/templates/page-home.tpl', 'sheet' => '/assets/theme.css' ] );
$after = cssFiles( $site );

check( 'no path, name or file of the request is read: a request that names others writes the one file and nothing else',
	$status === 200 && array_keys( array_diff_assoc( $after, $before ) ) === [ '/private/assets/style.css' ] && file_get_contents( $sheet ) === "a { color: red; }\n" );

[ $status, $second ] = $save( "b { color: blue; }\n", hash( 'sha256', "a { color: red; }\n" ) );
[ $stale, $changed ] = $save( "c { color: green; }\n", hash( 'sha256', "a { color: red; }\n" ) );
[ $noBase ] = cssCall( $site, 'themes/css-save', [ 'css' => "d { color: gray; }\n" ] );
$onFile = (string) file_get_contents( $sheet );
[ $forced ] = $save( "e { color: black; }\n", hash( 'sha256', 'something else' ), true );

check( 'a save is made on the stamp the text was opened with: the first session that saves wins, the second is 409 and its text is not written - the answer says so with its code - and a save that names no stamp is the same; force writes over what is there',
	$status === 200 && $stale === 409 && $changed['code'] === 'themes_css_changed' && $noBase === 409 && $onFile === "b { color: blue; }\n" && $forced === 200 && file_get_contents( $sheet ) === "e { color: black; }\n" );

$forceFixed = "g { color: teal; }\n";
$save( $forceFixed, hash( 'sha256', "e { color: black; }\n" ), true );
$wrongBase = hash( 'sha256', 'not the file' );
$notForce = array_map( static fn( mixed $value ): int => cssCall( $GLOBALS['site'], 'themes/css-save', [ 'css' => "h { color: gold; }\n", 'base' => $wrongBase, 'force' => $value ] )[0], [ 'true', 1, 'yes', [ true ], null, false ] );

check( 'force is the boolean true and nothing that only looks like it: the string "true", 1, "yes", a list, null and false are no force - 409 each, the file is as it was',
	$notForce === array_fill( 0, 6, 409 ) && file_get_contents( $sheet ) === $forceFixed );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. What is refused\n";

$fixed = "f { color: #123; }\n";
$save( $fixed );
$stands = static fn(): bool => file_get_contents( $GLOBALS['sheet'] ) === $GLOBALS['fixed'];
$limit = \Nino\Modules\Themes\ProjectCss::MAX_BYTES;
$exact = '/*'. str_repeat( 'a', $limit - 4 ). '*/';
[ $atLimit ] = $save( $exact );
$over = '/*'. str_repeat( 'a', $limit - 3 ). '*/';
$save( $fixed );
[ $overStatus, $overBody ] = $save( $over );
[ $overPreview ] = cssCall( $site, 'themes/css-preview', [ 'css' => $over ] );

check( 'the limit is 256 KB in bytes: a text of exactly that many is written and one byte more is refused - 413, by the save and by the preview - and the file stays; an answer of a longer file that was put there by hand is shown empty and says why',
	$atLimit === 200 && strlen( $exact ) === 262144 && $overStatus === 413 && $overBody['code'] === 'themes_css_big' && $overPreview === 413 && $stands() === true && strlen( $over ) === 262145 );

$save( $fixed );
$nul = $save( "a { content: \"\0\"; }" );
[ $wire ] = cssCall( $site, 'themes/css-save', [ 'css' => "a { content: \"\xff\xfe\"; }", 'base' => hash( 'sha256', $fixed ) ] );
$direct = [ ProjectCss::refusal( "a { content: \"\xff\xfe\"; }" )[2] ?? '', ProjectCss::refusal( "a { content: \"\xc3\"; }" )[2] ?? '', ProjectCss::refusal( "a { content: \"\u{e4}\u{1F600}\"; }" )[2] ?? 'none' ];

check( 'bytes that are no text are refused with their own code - a NUL byte, bytes that are not utf-8, half of a character - and nothing is written; a character that is utf-8, of one byte or of four, is text. Json carries no byte that is not utf-8: such a request does not reach the text and is refused as a text that is missing',
	$nul[0] === 400 && $nul[1]['code'] === 'themes_css_nul' && $wire === 400 && $direct === [ 'themes_css_utf8', 'themes_css_utf8', 'none' ] && $stands() === true );

$markup = [ "a { }\n</style><script>x</script>", "a { }\n</STYLE >", "<!-- a { } -->", "a { } -->", "a { content: '<!--'; }", "/* </style */" ];
$refused = array_map( static fn( string $css ): array => [ $GLOBALS['save']( $css )[0], $GLOBALS['save']( $css )[1]['code'] ?? '' ], $markup );

check( 'a text that could end a style element or a comment of html - </style in any case of letters, <!--, -->, in a string and in a comment as well - is refused whatever else it holds, and nothing is written',
	$refused === array_fill( 0, 6, [ 400, 'themes_css_markup' ] ) && $stands() === true );

$open = [ 'a { color: red;', "a { color: red; }\n}", "/* a comment that is not closed\na { }", "a { content: \"open; }", "a { background: url(open.png; }", "a { b { c: d; }" ];
$refused = array_map( static fn( string $css ): array => [ $GLOBALS['save']( $css )[0], $GLOBALS['save']( $css )[1]['code'] ?? '' ], $open );

check( 'braces, comments, strings and url() that are not closed - or a } that closes nothing - are refused with the code of the faults, and nothing is written: what follows such a text in the bundle would be taken into the wrong layer',
	$refused === array_fill( 0, 6, [ 400, 'themes_css_faults' ] ) && $stands() === true );

[ , $preview ] = cssCall( $site, 'themes/css-preview', [ 'css' => "a { color: red;\n/* open" ] );

check( 'the preview takes a text that is not closed - the bundle repairs it - and says what is wrong with it: each fault once, by its name; a text that is closed has none',
	array_diff( [ 'open-comment', 'open-block' ], $preview['faults'] ) === [] && count( $preview['faults'] ) === 2 && cssCall( $site, 'themes/css-preview', [ 'css' => $fixed ] )[1]['faults'] === [] );

$free = "@import url( \"https://fonts.example.test/x.css\" );\nbody > header #logo, .a:hover { background: url( data/x.png ) !important; }\n@media (min-width: 40em) { h1 { color: red !important; } }\n@font-face { font-family: X; src: url( x.woff2 ); }\n";
[ $freeStatus ] = $save( $free );

check( 'what is not checked is free: elements, ids, url(), @import, @font-face and !important are the css of the one who wrote it, and are written as they are',
	$freeStatus === 200 && file_get_contents( $sheet ) === $free );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The website\n";

$mine = "/* mine */\n". CSS_MARK. "\n";
$save( $mine );
$site = cssReboot( $site );
cssCall( $site, 'themes/apply', [ 'theme' => 'craft-probe' ] );
$site = cssReboot( $site );
$bundle = \Nino\Modules\Assets::stylesheet( $site, CSS_BUNDLE );
$themeSheet = ninoSandboxDir( $site ). '/private/assets/theme.css';

check( 'the file is the stylesheet of the project in the bundle - the layer project, which the bundle declares after the layer of the theme, so it rules over the theme whichever it is - once, and not in the layer of the theme',
	is_file( $themeSheet ) === true && str_contains( $bundle, '@layer nino.reset, nino.components, nino.theme, project;' ) === true && substr_count( $bundle, CSS_MARK ) === 1
	&& str_contains( cssBlock( $bundle, 'project' ), CSS_MARK ) === true && cssBlock( $bundle, 'nino.theme' ) !== '' && str_contains( cssBlock( $bundle, 'nino.theme' ), CSS_MARK ) === false );

$off = cssReboot( $site, false );
$offBundle = \Nino\Modules\Assets::stylesheet( $off, CSS_BUNDLE );

check( 'it is the kernel that loads it: with the feature off the bundle has the file as it has it with the feature on, in the same layer',
	str_contains( cssBlock( $offBundle, 'project' ), CSS_MARK ) === true && in_array( '\\Nino\\Modules\\Themes', $off['/nino/modules'], true ) === false );

$link = static fn(): string => \Nino\Html::renderHtml( $GLOBALS['site'], '[assets '. CSS_BUNDLE. ']' );
$was = $link();
$save( "/* mine */\n". CSS_MARK, hash( 'sha256', $mine ) );
$site = cssReboot( $site );
$now = $link();

check( 'a save moves the stamp of the bundle - the address of the stylesheet is another - and the same text saved again in the same second and the same size is a new stamp as well',
	$was !== $now && preg_match( '/style\.css\?v=([0-9a-f]+)/', $was ) === 1 && ( static function() use ( &$site, $sheet, $link ): bool {

		$one = $link();
		$text = (string) file_get_contents( $sheet );
		$GLOBALS['save']( $text, hash( 'sha256', $text ), true );
		$GLOBALS['site'] = cssReboot( $GLOBALS['site'] );

		return $link() !== $one;
	} )() );

$kept = hash_file( 'sha256', $sheet );
$time = filemtime( $sheet );
$state = static fn(): string => implode( '|', array_map( static fn( string $path ): string => $path, array_keys( array_filter( cssFiles( $GLOBALS['site'] ), static fn( string $path ): bool => str_starts_with( $path, '/private/data/theme/' ) === true, ARRAY_FILTER_USE_KEY ) ) ) );

cssCall( $site, 'themes/apply', [ 'theme' => 'band-probe' ] );
[ $restore ] = cssCall( $site, 'themes/restore', [ 'base' => \Nino\Modules\Themes\Store::stamp( $site ) ] );
[ $made ] = cssCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Mine', 'parts' => [], 'css' => '.theme-own { color: red; }' ] ] );
cssCall( $site, 'themes/apply', [ 'theme' => 'own:mine', 'base' => (string) ( cssCall( $site, 'themes/open', [ 'theme' => 'own:mine' ] )[1]['stamp'] ?? '' ) ] );
[ $copy ] = cssCall( $site, 'themes/duplicate', [ 'theme' => 'own:mine', 'name' => 'Mine two', 'base' => (string) ( cssCall( $site, 'themes/open', [ 'theme' => 'own:mine' ] )[1]['stamp'] ?? '' ) ] );
[ $removed ] = cssCall( $site, 'themes/delete', [ 'theme' => 'own:mine-two', 'base' => (string) ( cssCall( $site, 'themes/open', [ 'theme' => 'own:mine-two' ] )[1]['stamp'] ?? '' ) ] );

check( 'a theme that is chosen, a last state that is restored, a theme that is made, put on, copied and deleted leave the file as it is - its bytes and its time: it is in no theme and in no state',
	$made === 200 && $restore === 200 && hash_file( 'sha256', $sheet ) === $kept && filemtime( $sheet ) === $time && is_file( $sheet ) === true );

$all = '';

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( ninoSandboxDir( $site ). '/private/data/theme', FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( $file->isFile() === true )
		$all .= (string) file_get_contents( (string) $file );

check( 'the text is in no export and no package: not in the state of the themes, not in their last state, not in a copy of a theme, not in the stylesheet of the theme - the one place it is is its file',
	$all !== '' && str_contains( $all, CSS_MARK ) === false && str_contains( (string) file_get_contents( $themeSheet ), CSS_MARK ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. The preview\n";

$visit = static function( string $query ) use ( &$site ): string {

	ninoVisit( $site, '/?'. $query );

	return \Nino\Html::renderHtml( $site, '[assets '. CSS_BUNDLE. ']' );
};
$inside = static function( string $html ): string {

	preg_match( '#<style data-nino-theme-preview>(.*?)</style>#s', $html, $found );

	return str_replace( '<\\/', '</', $found[1] ?? '' );
};

$drafted = ".typed-in-the-field { color: rgb( 9 8 7 ); }\n";
[ $status, $draft ] = cssCall( $site, 'themes/css-preview', [ 'css' => $drafted ] );
$token = (string) ( $draft['token'] ?? '' );
$publicFile = ninoSandboxDir( $site ). '/public/.cache/style.css';
$visit( 'x=1' );
$publicBefore = (string) file_get_contents( $publicFile );
$page = $visit( 'nino-preview='. $token );
$styled = $inside( $page );

check( 'a preview of the text is a draft of the kernel of its own kind: a token and the query that asks for it; a request that holds it has the bundle in memory with the typed text in the layer project, after the theme, and not the saved text',
	$status === 200 && preg_match( \Nino\Preview::TOKEN, $token ) === 1 && $draft['query'] === \Nino\Preview::QUERY. '='. $token && $draft['faults'] === []
	&& str_contains( cssBlock( $styled, 'project' ), $drafted ) === true && str_contains( $styled, CSS_MARK ) === false && cssBlock( $styled, 'nino.theme' ) !== '' );

check( 'the public bundle stays as it is: the file is the one it was, byte for byte, and a request without the token has the link and none of the typed text',
	(string) file_get_contents( $publicFile ) === $publicBefore && str_contains( $visit( 'x=1' ), '<link' ) === true && str_contains( $visit( 'x=1' ), 'typed-in-the-field' ) === false && str_contains( $publicBefore, 'typed-in-the-field' ) === false );

$shows = static fn( string $query ): bool => str_contains( $visit( $query ), 'typed-in-the-field' ) === true;
$held = $_SESSION[$site['./nino/uid']]['/nino/preview/'. \Nino\Modules\Themes::CSS_KIND] ?? [];
$_SESSION[$site['./nino/uid']]['/nino/preview/'. \Nino\Modules\Themes::CSS_KIND] = [];
$otherSession = $shows( 'nino-preview='. $token );
$_SESSION[$site['./nino/uid']]['/nino/preview/'. \Nino\Modules\Themes::CSS_KIND] = $held;
$sameAgain = $shows( 'nino-preview='. $token );
$perms = $site['./nino/auth/current']['perms'] ?? null;
$site['./nino/auth/current']['perms'] = [ \Nino\Modules\Themes::PERM ];
$withoutRight = $shows( 'nino-preview='. $token );
$site['./nino/auth/current']['perms'] = $perms;
$again = $shows( 'nino-preview='. $token );
$session = $_SESSION[$site['./nino/uid']];
ninoUser( $site, 'writer3@example.com', [ \Nino\Modules\Themes::PERM, \Nino\Modules\Themes::CSS_PERM ] );
$otherAccount = $shows( 'nino-preview='. $token );
$_SESSION[$site['./nino/uid']] = $session;

check( 'the token is the session\'s and the account\'s, and the right is asked of the request and not only of the one that made the draft: another session, another account, and the same account with the right taken from it get the page as it is - and the one that holds the token has the draft',
	$sameAgain === true && $otherSession === false && $withoutRight === false && $again === true && $otherAccount === false );

[ , $theme ] = cssCall( $site, 'themes/preview', [ 'mix' => [ 'name' => 'Draft', 'parts' => [], 'css' => '.theme-draft { color: red; }' ] ] );
$themePage = $inside( $visit( 'nino-preview='. ( $theme['token'] ?? '' ) ) );

check( 'a draft of a theme is no draft of the own css and the other way round: the draft of the theme has the saved text of the project in the layer project, and the draft of the text has the saved theme',
	str_contains( $themePage, '.theme-draft' ) === true && str_contains( $themePage, 'typed-in-the-field' ) === false && str_contains( cssBlock( $themePage, 'project' ), CSS_MARK ) === true
	&& str_contains( $styled, '.theme-draft' ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nG. The script of the tab\n";

$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path: the script of the tab is not run\n";
else {
	$exit = 1;
	$output = [];

	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/themes-css-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'G. themes-css-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

foreach( $sandboxes as $sandbox )
	if( is_dir( $sandbox ) === true )
		\Nino\Filesystem::removeDir( $sandbox );

ninoDone( $site );
