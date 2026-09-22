<?php
declare(strict_types=1);

/**
 *	Nino
 *	ticker-smoke.php	Contract test for the Ticker feature (Modules\Ticker): the
 *										manifest, the activation through \Nino\Features, the two
 *										files it puts into the site's own asset bundles
 *										(\Nino\Html::addAsset()), what those two files promise,
 *										and deactivation. Like Typewriter this feature renders
 *										nothing at all - what runs past is markup the project's
 *										own template carries - so there is no shortcode here to
 *										test, and that absence is itself checked.
 *
 *										What the two files then do is the browser's, and
 *										ticker-js-smoke.js beside this file measures it; this
 *										test runs that one too where node is on the path.
 *
 *	Usage: php features/Ticker/tests/ticker-smoke.php
 *	       NINO_ROOT=../nino php features/Ticker/tests/ticker-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'ticker' );
$appData['/nino/dir'] = '';

$assetsDir = ninoSandboxDir( $appData ). '/features/Ticker/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/ticker.css', $assetsDir. '/ticker.css' );
copy( dirname( __DIR__ ). '/assets/ticker.js', $assetsDir. '/ticker.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "ticker"', is_array( $manifest ) && $manifest['key'] === 'ticker' && ninoWarnings() === [] );
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
check( '...and names the data attributes a row is timed with instead',
	array_key_exists( 'data-ticker-speed="40"', $manifest['manual']['markup'] ) === true );
check( 'it requires no other feature, keeps no data and carries no settings',
	$manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );
check( 'and ships no install unit - there is nothing for it to write into a project',
	$manifest['manual']['install'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'ticker' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Ticker', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'ticker' )['installed'] === '1.0.0' );

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Ticker::init() - the bundle, and nothing besides\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'ticker.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Ticker/assets/ticker.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'ticker.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Ticker/assets/ticker.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
/*	Measured against this feature's own name rather than against the whole map:
	Modules\Assets, which the bundle needs, registers [assets] of its own	*/
check( 'and it registered no shortcode and no route at all', ( $appData['./nino/html/shortcodes']['ticker'] ?? null ) === null
	&& ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

check( 'the generated cache file carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-ticker' );
} )() );
check( '...and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'nino-ticker-track' );
} )() );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/ticker.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/ticker.js' );

/*	A browser runs an animation off the main thread and stops paying for it in
	a background tab - neither of which is true of a script that moves something
	every frame	*/
check( 'the movement is an animation, not a script moving something every frame',
	str_contains( $css, 'animation-name: nino-ticker-run' ) === true
	&& str_contains( $js, 'requestAnimationFrame' ) === false && str_contains( $js, 'setInterval' ) === false );
check( '...and what the script sets is the distance and the duration',
	str_contains( $js, '--nino-ticker-distance' ) === true && str_contains( $js, '--nino-ticker-duration' ) === true );

/*	The seam is the whole problem: the animation ends on the copy standing
	where the original stood, so starting again from zero moves nothing	*/
check( 'the row is copied until it covers the box plus one length of itself',
	str_contains( $js, 'row.clientWidth + one' ) === true );
check( '...and there is a ceiling on the copying, so one narrow logo in a wide box does not make a thousand elements',
	str_contains( $js, 'COPIES_MAX' ) === true );
check( 'the copies are hidden from a screen reader - the row is read once, which is how many times it is there',
	str_contains( $js, "setAttribute( 'aria-hidden', 'true' )" ) === true );

check( 'a row stops while it is pointed at, so a logo can be looked at',
	str_contains( $css, ':hover .nino-ticker-track' ) === true && str_contains( $css, 'animation-play-state: paused' ) === true );
check( '...and while something inside it has the keyboard focus, because a row that runs away under a tab stop is unusable',
	str_contains( $css, ':focus-within .nino-ticker-track' ) === true );
check( 'a row that asked to keep running under the pointer does',
	str_contains( $css, '[data-ticker-pause="off"]' ) === true );

/*	Somebody asked for less movement, and a row running across the screen is
	the most literal reading of that there is	*/
check( 'a visitor who asked for less motion gets a row that stands still and scrolls by hand',
	str_contains( $css, 'prefers-reduced-motion' ) === true && str_contains( $css, 'overflow-x: auto' ) === true );

check( 'nothing about a ticker reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so ticker-js-smoke.js is not run here\n\n";
else {
	echo "ticker-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/ticker-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'ticker' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Ticker', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'ticker' )['active'] ?? true ) === false );

ninoDone( $appData );
