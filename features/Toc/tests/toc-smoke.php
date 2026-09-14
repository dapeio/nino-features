<?php
declare(strict_types=1);

/**
 *	Nino
 *	toc-smoke.php			Contract test for the Toc feature (Modules\Toc): the
 *										manifest, the activation through \Nino\Features and the
 *										two words it merges, what [toc] writes and what it
 *										deliberately does not, the two files it puts into the
 *										site's own asset bundles (\Nino\Html::addAsset()), and
 *										deactivation.
 *
 *										The list itself is the browser's, and toc-js-smoke.js
 *										beside this file measures it; this test runs that one
 *										too where node is on the path. Travels with the feature
 *										and runs against the checkout three levels up, or the
 *										one NINO_ROOT names.
 *
 *	Usage: php features/Toc/tests/toc-smoke.php
 *	       NINO_ROOT=../nino php features/Toc/tests/toc-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'toc' );
$appData['/nino/dir'] = '';
$appData['/nino/locales/textfiles'] = '/text';
$appData['./nino/locales/current'] = 'en_US';

$assetsDir = ninoSandboxDir( $appData ). '/features/Toc/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/toc.css', $assetsDir. '/toc.css' );
copy( dirname( __DIR__ ). '/assets/toc.js', $assetsDir. '/toc.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "toc"', is_array( $manifest ) && $manifest['key'] === 'toc' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';

/*	It is a way through what is already written on a page rather than a control
	that changes how it looks - which is the line the Features panel draws
	between content and ui	*/
check( 'it is filed under content', ( $raw['category'] ?? '' ) === 'content' );
check( 'it requires no other feature', $manifest['requires'] === [] );
check( 'it keeps no data of its own - the list is the page, read as it stands', $manifest['data'] === [] );
check( 'and carries one setting: whether every heading gets a link of its own',
	array_keys( $manifest['settings'] ) === [ 'anchors' ] && $manifest['settings']['anchors']['default'] === true );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'toc' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Toc', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'toc' )['installed'] === '1.0.0' );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged the list\'s words into text/'. $locale. '.php',
		array_diff_key( array_flip( [ '[[/toc/title]]', '[[/toc/anchor]]' ] ), $text ) === [] );
}

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Toc::init() - the shortcode and the bundle\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [toc]', ( $appData['./nino/html/shortcodes']['toc'] ?? null ) !== null );
check( 'toc.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Toc/assets/toc.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'toc.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Toc/assets/toc.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- [toc] -------------------------------------------------------------------

echo "[toc] - the frame the browser fills\n";

$html = \Nino\Html::renderHtml( $appData, '[toc]' );

/*	A Nino page is assembled out of a template, sections, shortcodes and
	elements - what the headings finally are is only settled once all of that
	has run. So the server writes the frame and the browser fills it	*/
check( 'what the server writes is a nav with an empty list in it',
	str_contains( $html, '<nav class="nino-toc"' ) === true
	&& str_contains( $html, '<ol class="nino-toc-list"></ol>' ) === true );
check( '...hidden, because a table of contents with no contents is worse than none',
	preg_match( '/<nav class="nino-toc"[^>]*\shidden/', $html ) === 1 );
check( 'the list names itself with its own heading', str_contains( $html, 'aria-labelledby="nino-toc-title"' ) === true
	&& str_contains( $html, 'id="nino-toc-title"' ) === true );

check( 'the levels it is built from are written for the script to read, in order',
	str_contains( $html, 'data-toc-levels="2,3"' ) === true );
check( '...and a shorter list is taken, always in order - a list that reads h3 before h2 is not an outline',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc levels="2"]' ), 'data-toc-levels="2"' ) === true
	&& \Nino\Modules\Toc::levels( [ 'levels' => '3,2' ] ) === [ 2, 3 ]
	&& \Nino\Modules\Toc::levels( [ 'levels' => '7' ] ) === \Nino\Modules\Toc::LEVELS
	&& \Nino\Modules\Toc::levels( [] ) === \Nino\Modules\Toc::LEVELS );

check( 'a scope given in the shortcode is passed on for the browser to look in',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc within="#content"]' ), 'data-toc-within="#content"' ) === true );
check( '...and it is escaped on the way, because it goes into an attribute',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc within="a\" onx=\"1"]' ), 'onx=' ) === false );

check( 'the heading over the list falls back to the project\'s own fill',
	str_contains( $html, '>On this page</h2>' ) === true && str_contains( $html, '[[/toc/' ) === false );
check( '...and to what the shortcode says where it says one',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc title="Auf dieser Seite"]' ), '>Auf dieser Seite</h2>' ) === true );

check( 'the word for an anchor is handed over too - a static asset cannot read a fill',
	str_contains( $html, 'data-toc-label="Link to this section"' ) === true );
check( 'the anchors setting reaches the markup as the script\'s own flag',
	str_contains( $html, 'data-toc-anchors="1"' ) === true && \Nino\Modules\Toc::anchors( $appData ) === true );

$off = $appData;
$off[ \Nino\Features::STATE_KEY ]['toc']['settings'] = [ 'anchors' => false ];
check( '...and says so when a project switched them off',
	str_contains( \Nino\Html::renderHtml( $off, '[toc]' ), 'data-toc-anchors="0"' ) === true );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/toc.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/toc.js' );

/*	An id a page already had may be linked to from somewhere else, and taking
	it away would break a link nobody here can see	*/
check( 'a heading that already has an id keeps it', str_contains( $js, "if( heading.id !== '' )" ) === true );
check( '...and a made-up one is made unique against the whole page, not just the list',
	str_contains( $js, 'document.getElementById( id ) !== null' ) === true );
check( 'a list never lists its own title', str_contains( $js, 'closest( SELECTOR )' ) === true );
check( 'nothing about a list reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );
check( 'scrolling costs one frame at most, not one call per event',
	str_contains( $js, 'requestAnimationFrame' ) === true && str_contains( $js, 'passive : true' ) === true );

check( 'the section being read is said with aria-current, so the mark and the announcement are one fact',
	str_contains( $js, "setAttribute( 'aria-current', 'true' )" ) === true
	&& str_contains( $css, '[aria-current="true"]' ) === true );
check( 'the anchor on a heading stays reachable by keyboard rather than being a hover-only affordance',
	str_contains( $css, '.nino-toc-anchor:focus-visible' ) === true );
check( 'and the stylesheet keeps the hidden nav hidden', str_contains( $css, '.nino-toc[hidden]' ) === true );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so toc-js-smoke.js is not run here\n\n";
else {
	echo "toc-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/toc-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'toc' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Toc', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/toc/title]]'] ) === true );

ninoDone( $appData );
