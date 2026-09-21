<?php
declare(strict_types=1);

/**
 *	Nino
 *	compare-smoke.php		Contract test for the Compare feature
 *											(Modules\Compare): the manifest, the activation
 *											through \Nino\Features and the three words it merges,
 *											the [compare] shortcode over both pictures, both
 *											captions and every way of getting it wrong, the two
 *											files it puts into the site's own asset bundles
 *											(\Nino\Html::addAsset()), and deactivation.
 *
 *											What happens once the script has it is the browser's,
 *											and compare-js-smoke.js beside this file measures it;
 *											this test runs that one too where node is on the path.
 *											Travels with the feature and runs against the checkout
 *											three levels up, or the one NINO_ROOT names (see
 *											tests/harness.php there).
 *
 *	Usage: php features/Compare/tests/compare-smoke.php
 *	       NINO_ROOT=../nino php features/Compare/tests/compare-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'compare' );
$appData['/nino/dir'] = '';
$appData['/nino/locales/textfiles'] = '/text';
$appData['./nino/locales/current'] = 'en_US';

$assetsDir = ninoSandboxDir( $appData ). '/features/Compare/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/compare.css', $assetsDir. '/compare.css' );
copy( dirname( __DIR__ ). '/assets/compare.js', $assetsDir. '/compare.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "compare"', is_array( $manifest ) && $manifest['key'] === 'compare' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';
check( 'it is filed under ui', ( $raw['category'] ?? '' ) === 'ui' );
check( 'it requires no other feature', $manifest['requires'] === [] );
check( 'it keeps no data of its own', $manifest['data'] === [] );

/*	Everything a comparison varies belongs to the one place it is written: a
	before/after of a facade and one of a photo retouch are not the same
	decision, and a setting would have to mean both	*/
check( 'and carries no settings - what varies belongs to the element, not the site', $manifest['settings'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'compare' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Compare', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'compare' )['installed'] === '1.0.0' );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged the pair\'s words into text/'. $locale. '.php',
		array_diff_key( array_flip( [ '[[/compare/before]]', '[[/compare/after]]', '[[/compare/handle]]' ] ), $text ) === [] );
}

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Compare::init() - the shortcode and the bundle\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [compare]', ( $appData['./nino/html/shortcodes']['compare'] ?? null ) !== null );
check( 'compare.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Compare/assets/compare.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'compare.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Compare/assets/compare.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route - nothing about a comparison reaches the server', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- [compare] ---------------------------------------------------------------

echo "[compare] - two pictures and the control between them\n";

$html = \Nino\Html::renderHtml( $appData, '[compare before="haus/roh.jpg" after="haus/fertig.jpg" alt="Die Fassade vor und nach der Sanierung"]' );

check( 'both pictures are served from the project\'s own images',
	substr_count( $html, '<img' ) === 2 && str_contains( $html, 'roh.jpg' ) === true && str_contains( $html, 'fertig.jpg' ) === true );

/*	The divider is an <input type="range">, not a <div> with pointer handlers:
	the browser drags it with a mouse, a finger and the arrow keys, announces it
	and gives it a value - none of which a div gets without being told three
	times	*/
check( 'the divider is a real range control, so mouse, finger and arrow keys all work without being written',
	preg_match( '/<input[^>]+type="range"[^>]+class="nino-compare-range"|<input class="nino-compare-range" type="range"/', $html ) === 1
	&& str_contains( $html, 'min="0"' ) === true && str_contains( $html, 'max="100"' ) === true );
check( '...and it names itself for somebody who reaches it with the keyboard',
	str_contains( $html, 'aria-label="Move the divider between the two pictures"' ) === true );

/*	Without the script the same markup is two captioned pictures under one
	another - so the control is written hidden, the way [mode-switch] writes its
	own, and compare.js unhides it	*/
check( 'the control is hidden until the script has it', preg_match( '/<input[^>]*\shidden/', $html ) === 1 );
check( '...and the layout it is hidden under is the two pictures, both captioned',
	str_contains( $html, 'nino-compare-side--before' ) === true && str_contains( $html, 'nino-compare-side--after' ) === true
	&& str_contains( $html, 'nino-is-ready' ) === false );

check( 'the description is the first picture\'s alt and the caption under the pair',
	substr_count( $html, 'Die Fassade vor und nach der Sanierung' ) === 2
	&& str_contains( $html, 'alt=""' ) === true );
check( '...and the second picture is hidden from a screen reader, because it is the same thing again',
	str_contains( $html, 'aria-hidden="true"' ) === true );

check( 'the starting position is written where the stylesheet reads it and on the control',
	str_contains( $html, '--nino-compare-position:50%' ) === true && str_contains( $html, 'value="50"' ) === true );

$moved = \Nino\Html::renderHtml( $appData, '[compare before="a.jpg" after="b.jpg" start="20"]' );
check( 'a starting position given in the shortcode is taken', str_contains( $moved, '--nino-compare-position:20%' ) === true && str_contains( $moved, 'value="20"' ) === true );
check( '...and one outside the control\'s own range is not - a thumb nobody can see is not a starting point',
	\Nino\Modules\Compare::start( [ 'start' => '140' ] ) === 50
	&& \Nino\Modules\Compare::start( [ 'start' => '-5' ] ) === 50
	&& \Nino\Modules\Compare::start( [ 'start' => 'links' ] ) === 50
	&& \Nino\Modules\Compare::start( [ 'start' => '0' ] ) === 0 );

check( 'the words fall back to the project\'s own fills where the shortcode names no sides',
	str_contains( $html, '>Before</span>' ) === true && str_contains( $html, '>After</span>' ) === true );

$named = \Nino\Html::renderHtml( $appData, '[compare before="a.jpg" after="b.jpg" before-label="Rohbau" after-label="Fertig"]' );
check( '...and to what it says where it names them', str_contains( $named, '>Rohbau</span>' ) === true && str_contains( $named, '>Fertig</span>' ) === true );

$quoted = \Nino\Html::renderHtml( $appData, '[compare before="a.jpg" after="b.jpg" before-label="Ada & Co" alt="<b>x</b>"]' );
check( 'a caption with html in it is escaped, not written through',
	str_contains( $quoted, 'Ada &amp; Co' ) === true && str_contains( $quoted, '<b>x</b>' ) === false );

check( 'the shape is 4:3 unless one of the four is named',
	str_contains( $html, 'nino-compare--4-3' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[compare before="a.jpg" after="b.jpg" ratio="16-9"]' ), 'nino-compare--16-9' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[compare before="a.jpg" after="b.jpg" ratio="7-3"]' ), 'nino-compare--4-3' ) === true );

/*	One half of a comparison is a picture nobody asked for, and a divider with
	nothing on one side of it is a control that cannot mean anything	*/
check( 'half a pair renders nothing at all',
	\Nino\Html::renderHtml( $appData, '[compare before="a.jpg"]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[compare after="b.jpg"]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[compare]' ) === '' );
check( '...and a name that climbs out of the project\'s images is not a picture',
	\Nino\Modules\Compare::image( '../../config.php' ) === ''
	&& \Nino\Modules\Compare::image( '/etc/passwd' ) === ''
	&& \Nino\Modules\Compare::image( 'haus/roh.jpg' ) === 'haus/roh.jpg'
	&& \Nino\Html::renderHtml( $appData, '[compare before="../x.jpg" after="b.jpg"]' ) === '' );

check( 'every word is a text fill the project owns, resolved by the time it renders',
	str_contains( $html, '[[/compare/' ) === false );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/compare.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/compare.js' );

/*	The clipping is the stylesheet's, so a drag is a paint rather than a call
	into JavaScript on every frame - the script only ever writes the value it
	was given into a custom property	*/
check( 'the clipping is the stylesheet\'s, driven by one custom property',
	str_contains( $css, 'clip-path: inset(0 0 0 var(--nino-compare-position' ) === true
	&& str_contains( $js, 'setProperty( \'--nino-compare-position\'' ) === true );
check( 'the script drags nothing itself - the control is what the browser already drags',
	str_contains( $js, 'mousemove' ) === false && str_contains( $js, 'touchmove' ) === false
	&& str_contains( $js, 'pointermove' ) === false && str_contains( $js, "'input'" ) === true );
check( 'nothing about a comparison reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );
check( 'the value it is handed is held to the control\'s own range before it is written',
	str_contains( $js, 'Math.max( 0, Math.min( 100' ) === true );

/*	The hidden attribute is display:none out of the browser's own stylesheet,
	and this file gives the same element a display of its own - which beats it	*/
check( 'the stylesheet keeps the hidden control hidden, which its own display rule would otherwise undo',
	str_contains( $css, '.nino-compare-range[hidden]' ) === true );
check( 'and the control stays a real control rather than being replaced by a painted one',
	str_contains( $css, 'focus-visible' ) === true && str_contains( $css, 'slider-thumb' ) === true );

/*	The figure is the frame plus the caption under it. A control laid over all
	of that is one whose thumb rides below the middle of the picture and whose
	own box is over the caption - text nobody can select, and a caption a
	pointer press drags the comparison with	*/
preg_match( '/\.nino-compare\.nino-is-ready \.nino-compare-range \{([^}]*)\}/', $css, $laid );
$control = $laid[1] ?? '';

check( 'the control is laid over the frame rather than over the whole figure, so the caption under it stays text',
	$control !== '' && str_contains( $control, 'aspect-ratio: var(--nino-compare-ratio' ) === true
	&& str_contains( $control, 'inset: 0' ) === false && str_contains( $control, 'height: 100%' ) === false );
check( '...taking its ratio from the same property the frame reads, so a pair says its ratio once',
	substr_count( $css, 'aspect-ratio: var(--nino-compare-ratio' ) === 2
	&& str_contains( $css, '.nino-compare--16-9 { --nino-compare-ratio: 16 / 9; }' ) === true );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so compare-js-smoke.js is not run here\n\n";
else {
	echo "compare-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/compare-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'compare' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Compare', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/compare/before]]'] ) === true );

ninoDone( $appData );
