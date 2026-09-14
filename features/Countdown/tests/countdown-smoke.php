<?php
declare(strict_types=1);

/**
 *	Nino
 *	countdown-smoke.php		Contract test for the Countdown feature
 *												(Modules\Countdown): the manifest, the activation
 *												through \Nino\Features and the nine words it merges,
 *												the [countdown] shortcode over its units, its
 *												formats and every way of getting the moment wrong,
 *												the two files it puts into the site's own asset
 *												bundles (\Nino\Html::addAsset()), and deactivation.
 *
 *												The arithmetic is the browser's, and
 *												countdown-js-smoke.js beside this file measures it;
 *												this test runs that one too where node is on the
 *												path. Travels with the feature and runs against the
 *												checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Countdown/tests/countdown-smoke.php
 *	       NINO_ROOT=../nino php features/Countdown/tests/countdown-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'countdown' );
$appData['/nino/dir'] = '';
$appData['/nino/locales/textfiles'] = '/text';
$appData['./nino/locales/current'] = 'en_US';

$assetsDir = ninoSandboxDir( $appData ). '/features/Countdown/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/countdown.css', $assetsDir. '/countdown.css' );
copy( dirname( __DIR__ ). '/assets/countdown.js', $assetsDir. '/countdown.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "countdown"', is_array( $manifest ) && $manifest['key'] === 'countdown' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';
check( 'it is filed under ui', ( $raw['category'] ?? '' ) === 'ui' );
check( 'it requires no other feature', $manifest['requires'] === [] );
check( 'it keeps no data and carries no settings - a sale ending and a conference opening are not one countdown',
	$manifest['data'] === [] && $manifest['settings'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'countdown' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Countdown', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'countdown' )['installed'] === '1.0.0' );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged both forms of every unit into text/'. $locale. '.php',
		array_diff_key( array_flip( [
			'[[/countdown/day]]', '[[/countdown/days]]', '[[/countdown/hour]]', '[[/countdown/hours]]',
			'[[/countdown/minute]]', '[[/countdown/minutes]]', '[[/countdown/second]]', '[[/countdown/seconds]]',
			'[[/countdown/done]]',
		] ), $text ) === [] );
}

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Countdown::init() - the shortcode and the bundle\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [countdown]', ( $appData['./nino/html/shortcodes']['countdown'] ?? null ) !== null );
check( 'countdown.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Countdown/assets/countdown.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'countdown.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Countdown/assets/countdown.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route - nothing about a countdown reaches the server', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- [countdown] -------------------------------------------------------------

echo "[countdown] - the moment, and what stands there without a script\n";

$html = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00"]' );

/*	The moment goes out once, as an ISO-8601 string with the site's own offset
	in it, so a reader in another timezone counts down to the same instant
	rather than to the same wall clock	*/
check( 'the moment is written with the site\'s own offset, so it means the same instant everywhere',
	preg_match( '/data-countdown-to="2026-12-24T18:00:00[+-]\d{2}:\d{2}"/', $html ) === 1 );
check( '...and the same string is the machine-readable half of a <time>',
	preg_match( '/<time class="nino-countdown-date" datetime="2026-12-24T18:00:00[+-]\d{2}:\d{2}">/', $html ) === 1 );

/*	Without JavaScript what stands there is the date. That is the markup; the
	counter is what is hidden until countdown.js takes over, the way
	[mode-switch]'s switch is - a row of em dashes is worse than a date	*/
check( 'the date is written out for a reader too', str_contains( $html, '>2026-12-24 18:00</time>' ) === true );
check( '...and the counter is hidden until the script has it', preg_match( '/<div class="nino-countdown-parts" hidden/', $html ) === 1 );

check( 'a format given in the shortcode is used for the readable half',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" format="d.m.Y H:i"]' ), '>24.12.2026 18:00</time>' ) === true );

check( 'the four parts are drawn by default, largest first',
	preg_match_all( '/data-countdown-unit="([a-z]+)"/', $html, $found ) === 4
	&& $found[1] === [ 'days', 'hours', 'minutes', 'seconds' ] );

/*	Both forms of every unit reach the browser on the part they belong to: a
	static asset cannot read a text fill, so the script is handed the two words
	rather than the key, and only chooses between them	*/
check( 'every part carries both forms of its name, resolved before the page was sent',
	str_contains( $html, 'data-countdown-one="day" data-countdown-many="days"' ) === true
	&& str_contains( $html, 'data-countdown-one="second" data-countdown-many="seconds"' ) === true
	&& str_contains( $html, '[[/countdown/' ) === false );

$some = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" units="hours,days"]' );
check( 'a shorter list of parts is taken, and always drawn largest first whatever order it was written in',
	preg_match_all( '/data-countdown-unit="([a-z]+)"/', $some, $found ) === 2 && $found[1] === [ 'days', 'hours' ] );
check( '...and a list naming nothing this knows falls back to all four rather than to an empty row',
	\Nino\Modules\Countdown::units( [ 'units' => 'fortnights' ] ) === \Nino\Modules\Countdown::UNITS_DEFAULT
	&& \Nino\Modules\Countdown::units( [ 'units' => '' ] ) === \Nino\Modules\Countdown::UNITS_DEFAULT
	&& \Nino\Modules\Countdown::units( [] ) === \Nino\Modules\Countdown::UNITS_DEFAULT );

check( 'the sentence for afterwards falls back to the project\'s own fill',
	str_contains( $html, 'data-countdown-done="The time has come"' ) === true );

$done = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" done="Der Verkauf läuft"]' );
check( '...and to what the shortcode says where it says one', str_contains( $done, 'data-countdown-done="Der Verkauf läuft"' ) === true );

$quoted = \Nino\Html::renderHtml( $appData, '[countdown to="2026-12-24 18:00" done="Ada & Co"]' );
check( 'a sentence with html in it is escaped, not written through',
	str_contains( $quoted, 'Ada &amp; Co' ) === true && str_contains( $quoted, '"Ada & Co"' ) === false );

echo "\n";


// --- A moment that is not one --------------------------------------------------

echo "A moment that is not one\n";

check( 'a date this cannot read is no countdown at all', \Nino\Modules\Countdown::moment( 'irgendwann' ) === null );
check( '...and it is said out loud rather than shown as a wrong date',
	count( array_filter( ninoWarnings(), static fn( string $w ): bool => str_contains( $w, '[countdown to="irgendwann"]' ) ) ) === 1 );

check( 'an [countdown] with no moment renders nothing',
	\Nino\Html::renderHtml( $appData, '[countdown]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[countdown to=""]' ) === '' );
ninoWarnings();

check( 'a moment already past is still rendered - what has run out is what the script says so about',
	str_contains( \Nino\Html::renderHtml( $appData, '[countdown to="2001-01-01 00:00"]' ), 'data-countdown-to="2001-01-01T00:00:00' ) === true );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/countdown.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/countdown.js' );

check( 'the arithmetic is the browser\'s - a page cached for an hour would otherwise be an hour wrong',
	str_contains( $js, 'Date.now()' ) === true && str_contains( $js, 'Date.parse(' ) === true );
check( 'one timer serves every countdown on the page, and stops when the last one has run out',
	substr_count( $js, 'setInterval' ) === 1 && str_contains( $js, 'clearInterval' ) === true );
check( 'nothing about a countdown reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

/*	data-countdown-done is where the sentence for afterwards is kept. A flag
	written into it would destroy the sentence one line before it is read	*/
check( 'the "already finished" flag is the class, not the attribute the sentence is kept in',
	str_contains( $js, "classList.contains( 'nino-is-done' )" ) === true
	&& str_contains( $js, 'dataset.countdownDone' ) === false );

check( 'the numbers are tabular, so the row does not breathe once a second',
	str_contains( $css, 'tabular-nums' ) === true );
check( 'the stylesheet keeps the hidden counter hidden, which its own display rule would otherwise undo',
	str_contains( $css, '.nino-countdown-parts[hidden]' ) === true );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so countdown-js-smoke.js is not run here\n\n";
else {
	echo "countdown-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/countdown-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'countdown' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Countdown', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/countdown/days]]'] ) === true );

ninoDone( $appData );
