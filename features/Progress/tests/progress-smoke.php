<?php
declare(strict_types=1);

/**
 *	Nino
 *	progress-smoke.php	Contract test for the Progress feature (Modules\Progress): the
 *										manifest, the activation through \Nino\Features, the two
 *										files it puts into the site's own asset bundles
 *										(\Nino\Html::addAsset()), what those two files promise,
 *										and deactivation. Like Typewriter this feature renders
 *										nothing at all - the bar is one empty element the
 *										project's own template carries - so there is no
 *										shortcode here to test, and that absence is itself
 *										checked.
 *
 *										What the two files then do is the browser's, and
 *										progress-js-smoke.js beside this file measures it; this
 *										test runs that one too where node is on the path.
 *
 *	Usage: php features/Progress/tests/progress-smoke.php
 *	       NINO_ROOT=../nino php features/Progress/tests/progress-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'progress' );
$appData['/nino/dir'] = '';

$assetsDir = ninoSandboxDir( $appData ). '/features/Progress/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/progress.css', $assetsDir. '/progress.css' );
copy( dirname( __DIR__ ). '/assets/progress.js', $assetsDir. '/progress.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "progress"', is_array( $manifest ) && $manifest['key'] === 'progress' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';
check( 'it is filed under ui', ( $raw['category'] ?? '' ) === 'ui' );

/*	Like Typewriter: what runs past is the project's own markup, and every
	timing belongs to the row being run rather than to the site - a logo bar in
	a footer and a line of announcements in a header are not one speed	*/
check( 'it brings nothing of its own to write - no shortcode in the manual',
	$manifest['manual']['shortcodes'] === [] && $manifest['manual']['routes'] === [] );
check( '...and names the data attributes a bar is pointed with instead',
	array_key_exists( 'data-progress-of="#article"', $manifest['manual']['markup'] ) === true );
check( 'it requires no other feature, keeps no data and carries no settings',
	$manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );
check( 'and ships no install unit - there is nothing for it to write into a project',
	$manifest['manual']['install'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'progress' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Progress', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'progress' )['installed'] === '1.0.0' );

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Progress::init() - the bundle, and nothing besides\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'progress.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Progress/assets/progress.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'progress.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Progress/assets/progress.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
/*	Measured against this feature's own name rather than against the whole map:
	Modules\Assets, which the bundle needs, registers [assets] of its own	*/
check( 'and it registered no shortcode and no route at all', ( $appData['./nino/html/shortcodes']['progress'] ?? null ) === null
	&& ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

check( 'the generated cache file carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-progress' );
} )() );
check( '...and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'data-progress-of' );
} )() );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/progress.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/progress.js' );

/*	The whole page counts the footer as part of the article, so "finished"
	arrives after the last paragraph rather than at it	*/
check( 'a bar can be pointed at the one element that is the text, rather than at the whole page',
	str_contains( $js, "getAttribute( 'data-progress-of' )" ) === true
	&& str_contains( $js, 'box.height - seen' ) === true );
check( '...and an element shorter than the screen is finished the moment it is on it, not never',
	str_contains( $js, 'if( travel <= 0 )' ) === true );

check( 'the width is a custom property the stylesheet uses, so a scroll costs a paint',
	str_contains( $js, "setProperty( '--nino-progress'" ) === true
	&& str_contains( $css, 'width: var(--nino-progress' ) === true );
check( 'scrolling costs one frame at most, not one call per event',
	str_contains( $js, 'requestAnimationFrame' ) === true && str_contains( $js, 'passive : true' ) === true );

/*	A progressbar with no name is announced as a number nobody asked for, which
	is worse than a decoration a screen reader never mentions	*/
check( 'a bar that was given a name is a named progressbar',
	str_contains( $js, "setAttribute( 'role', 'progressbar' )" ) === true
	&& str_contains( $js, "setAttribute( 'aria-valuemax', '100' )" ) === true );
check( '...and one that was not is hidden from a screen reader rather than announced unnamed',
	str_contains( $js, "setAttribute( 'aria-hidden', 'true' )" ) === true );

check( 'the bar is not on the page at all until something has been measured',
	str_contains( $css, '.nino-progress:not(.nino-is-ready)' ) === true );
check( 'it paints from the project\'s own accent rather than a palette of its own',
	str_contains( $css, 'var(--color-accent' ) === true && preg_match( '/#[0-9a-f]{6}/i', $css ) !== 1 );
check( 'and a visitor who asked for less motion gets the value itself, with nothing in between',
	str_contains( $css, 'prefers-reduced-motion: no-preference' ) === true );

check( 'nothing about a bar reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so progress-js-smoke.js is not run here\n\n";
else {
	echo "progress-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/progress-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'progress' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Progress', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'progress' )['active'] ?? true ) === false );

ninoDone( $appData );
