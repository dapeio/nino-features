<?php
declare(strict_types=1);

/**
 *	Nino
 *	ticker-smoke.php	Contract test for the Ticker feature (Modules\Ticker): the
 *										manifest, the activation through \Nino\Features, the two
 *										files it puts into the site's own asset bundles
 *										(\Nino\Html::addAsset()), the one text fill its install
 *										unit merges for the pause button, what those two files
 *										promise, and deactivation. Like Typewriter this
 *										feature renders nothing at all - what runs past is
 *										markup the project's own template carries - so there
 *										is no shortcode here to test, and that absence is
 *										itself checked.
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


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "ticker"', is_array( $manifest ) && $manifest['key'] === 'ticker' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

check( 'it is filed under ui', $manifest['category'] === 'ui' );

/*	Like Typewriter: what runs past is the project's own markup, and every
	timing belongs to the row being run rather than to the site - a logo bar in
	a footer and a line of announcements in a header are not one speed	*/
check( 'it brings nothing of its own to write - no shortcode in the manual',
	$manifest['manual']['shortcodes'] === [] && $manifest['manual']['routes'] === [] );
check( '...and names the data attributes a row is timed with instead',
	array_key_exists( 'data-ticker-speed="40"', $manifest['manual']['markup'] ) === true );
check( '...and the track inside the row, the one element the script runs',
	array_key_exists( 'class="nino-ticker-track"', $manifest['manual']['markup'] ) === true );
check( '...and the loop and the pause button, which are opt-in',
	array_key_exists( 'data-ticker-loop="1"', $manifest['manual']['markup'] ) === true
	&& array_key_exists( 'data-ticker-toggle="[[/feature/ticker/pause/label]]"', $manifest['manual']['markup'] ) === true );
check( 'it requires no other feature, keeps no data and carries no settings',
	$manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );
check( 'its install unit brings the pause button\'s label and nothing a project has to write for itself, and the manual lists it',
	array_keys( $manifest['manual']['install'] ) === [ 'text/<locale>.php' ] && is_file( $dir. '/install/manifest.php' ) === true
	&& is_file( $dir. '/install/text/en_US.php' ) === true && is_file( $dir. '/install/text/de_DE.php' ) === true );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'ticker' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Ticker', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'ticker' )['installed'] === $manifest['version'] );
check( 'activation merged the pause button\'s label into the text file of both locales',
	( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' ) === 'Pause animation'
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' ) === 'Animation pausieren' );
check( '...and wrote no template into the project', is_dir( ninoSandboxDir( $appData ). '/templates' ) === false );

/*	Add-only: what a project has written for that key is its own, and an update
	- activating an active feature - leaves it as it is	*/
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/feature/ticker/pause/label]]' => 'Stop it' ] );
check( 'an update keeps the value a project has for that key', \Nino\Features::activate( $appData, 'ticker' ) === true
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' ) === 'Stop it' );

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

/*	One cycle by default: the copy ends where the original stood, so the last
	frame is the first and nothing jumps when it stops. The endless loop is the
	opt-in, and only the class the script writes switches it on - the list of
	values that mean "on" is in one place	*/
check( 'a row runs one cycle by default',
	preg_match( '/\.nino-ticker\.nino-is-running \.nino-ticker-track\s*\{[^}]*animation-iteration-count:\s*1;/s', $css ) === 1 );
check( '...and the endless loop is only for a row the script marked as looping',
	substr_count( $css, 'animation-iteration-count: infinite' ) === 1
	&& preg_match( '/\.nino-ticker\.nino-is-looping\.nino-is-running \.nino-ticker-track\s*\{[^}]*animation-iteration-count:\s*infinite;/s', $css ) === 1
	&& str_contains( $css, '[data-ticker-loop' ) === false );
check( 'a row that has not been seen is held, and one the visitor paused is, whatever the pointer does',
	preg_match( '/\.nino-ticker\.nino-is-waiting \.nino-ticker-track\s*\{[^}]*animation-play-state:\s*paused/s', $css ) === 1
	&& preg_match( '/\.nino-ticker\.nino-is-paused \.nino-ticker-track\s*\{[^}]*animation-play-state:\s*paused/s', $css ) === 1
	&& str_contains( $css, ':not(.nino-is-paused)' ) === true );
check( 'a finished pass has no animation at all, so a resize cannot start it over',
	preg_match( '/\.nino-ticker\.nino-is-done \.nino-ticker-track\s*\{[^}]*animation:\s*none/s', $css ) === 1 );
check( 'the pause button\'s pressed state hangs off the class the script gives it',
	str_contains( $css, '.nino-ticker-toggle[aria-pressed="true"]' ) === true );

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

check( 'the script draws the pause button with createElement() and textContent, never inside the track, and removes it rather than hiding it',
	str_contains( $js, "createElement( 'button' )" ) === true && str_contains( $js, 'row.nextSibling' ) === true
	&& str_contains( $js, 'innerHTML' ) === false && str_contains( $js, "'hidden'" ) === false );
check( 'a single pass is held until the row has been seen, and ends on the animation\'s own end',
	str_contains( $js, 'IntersectionObserver' ) === true && str_contains( $js, "'animationend'" ) === true );

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
