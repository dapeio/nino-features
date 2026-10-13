<?php
declare(strict_types=1);

/**
 *	Nino
 *	modeswitch-smoke.php	Contract test for the Modeswitch feature
 *												(Modules\Modeswitch): the manifest, the activation
 *												through \Nino\Features and the four words it merges,
 *												the [mode-switch] shortcode and the markup it
 *												renders, the two files its manifest puts into the
 *												site's own asset bundles
 *												(\Nino\Features::registerAssets()), and
 *												deactivation. What those files then do is the
 *												browser's, and modeswitch-js-smoke.js beside this
 *												file measures it; this test runs that one too where
 *												node is on the path. Travels with the feature and
 *												runs against the checkout three levels up, or the
 *												one NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/Modeswitch/tests/modeswitch-smoke.php
 *	       NINO_ROOT=../nino php features/Modeswitch/tests/modeswitch-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'modeswitch' );
$appData['/nino/dir'] = '';
// ninoSandbox() defaults the current locale to 'de_DE'; switched to English
// here so the assertions below compare against install/text/en_US.php
$appData['./nino/locales/current'] = 'en_US';


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "modeswitch"', is_array( $manifest ) && $manifest['key'] === 'modeswitch' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

/*	The floor is the kernel whose toolset it calls - the helpers of \Nino\Feature,
	the manifest's assets, Nino.events in the script - and every such kernel has
	the dark half of the palette a switch needs: assets/theme.css carries
	:root[data-nino-mode="dark"] and the matching prefers-color-scheme block	*/
check( 'it names ^1.8 - the kernel whose toolset it calls', ( $manifest['nino'] ?? '' ) === '^1.8' );
check( '...and the kernel before it cannot run it', \Nino\Features::satisfies( (string) $manifest['nino'], '1.7.0' ) === false
	&& \Nino\Features::satisfies( (string) $manifest['nino'], '1.8.0-beta' ) === true );

check( 'it is filed under ui', $manifest['category'] === 'ui' );
check( 'it needs no other feature', $manifest['requires'] === [] );
check( 'it keeps no data of its own - the choice belongs to the reader\'s browser', $manifest['data'] === [] );
check( 'and carries no settings: which of the three a reader is on is not the site\'s decision', $manifest['settings'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'modeswitch' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'modeswitch' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Modeswitch', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'modeswitch' )['installed'] === '1.1.0' );

/*	The four words, merged into the project's own text file for every locale it
	has - so an editor changes "Dunkel" in the Text panel and never opens a
	feature directory. Add-only: a project that already had one of these keys
	keeps what it had	*/
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged the switch\'s words into text/'. $locale. '.php',
		array_diff_key( array_flip( [ '[[/feature/modeswitch/switch/label]]', '[[/feature/modeswitch/mode/light]]', '[[/feature/modeswitch/mode/system]]', '[[/feature/modeswitch/mode/dark]]' ] ), $text ) === [] );
}
check( 'it wrote no template and no route of its own - where the switch goes is the project\'s call',
	is_dir( ninoSandboxDir( $appData ). '/templates' ) === false
	&& ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- init() and the manifest's assets ----------------------------------------

echo "Modules\\Modeswitch::init() and the manifest's assets - the shortcode and the bundle\n";

// The bundles are filled the way \Nino\init() fills them, before the modules' init()
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [mode-switch]', ( $appData['./nino/html/shortcodes']['mode-switch'] ?? null ) !== null );
check( 'modeswitch.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Modeswitch/assets/modeswitch.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'modeswitch.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Modeswitch/assets/modeswitch.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route - nothing about this reaches the server', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

check( 'the generated cache file carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-modeswitch' );
} )() );
check( '...and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'data-nino-mode' );
} )() );

echo "\n";


// --- [mode-switch] -----------------------------------------------------------

echo "[mode-switch] - three states, one control\n";

$html = \Nino\Html::renderHtml( $appData, '[mode-switch]' );

check( 'it renders one group with the three buttons in it', substr_count( $html, '<button' ) === 3
	&& str_contains( $html, 'class="nino-modeswitch"' ) === true
	&& str_contains( $html, 'role="group"' ) === true );

/*	light, system, dark - and system in the middle, which is where a reader
	looking for "back to normal" reaches. Measured as positions rather than as
	presence, because the order is the decision	*/
$order = [];
if( preg_match_all( '/data-mode="([a-z]+)"/', $html, $found ) > 0 )
	$order = $found[1];

check( 'in the order the switch offers them, with system between the two', $order === \Nino\Modules\Modeswitch::MODES
	&& $order === [ 'light', 'system', 'dark' ] );

/*	Which one is on is a question only the browser can answer - the server
	renders none of them pressed, and the control hidden, so a reader without
	JavaScript is not left with three buttons that do nothing	*/
check( 'nothing is rendered pressed', substr_count( $html, 'aria-pressed="false"' ) === 3
	&& str_contains( $html, 'aria-pressed="true"' ) === false );
check( '...and the whole switch is hidden until the script has it', preg_match( '/<div class="nino-modeswitch"[^>]*\shidden/', $html ) === 1 );

check( 'every word is a text fill the project owns, resolved by the time it renders',
	str_contains( $html, '[[/feature/modeswitch/' ) === false
	&& substr_count( $html, 'System' ) >= 1 && substr_count( $html, 'Dark' ) >= 1 );
check( 'the group says what it is, for a reader who cannot see the three together',
	str_contains( $html, 'aria-label="Appearance"' ) === true );
check( 'each button carries its icon inline - a switch that paints its icons a request late moves under the pointer',
	substr_count( $html, '<svg' ) === 3 && str_contains( $html, 'stroke="currentColor"' ) === true
	&& str_contains( $html, 'aria-hidden="true"' ) === true );

$icons = \Nino\Html::renderHtml( $appData, '[mode-switch icons]' );

check( 'icons="1" is the flag, icons="0" is not, and neither changes the plain switch',
	\Nino\Html::renderHtml( $appData, '[mode-switch icons="1"]' ) === $icons
	&& \Nino\Html::renderHtml( $appData, '[mode-switch icons="0"]' ) === \Nino\Html::renderHtml( $appData, '[mode-switch]' ) );
check( 'the icons flag drops the words from the layout and keeps them in the markup',
	str_contains( $icons, 'nino-modeswitch--icons' ) === true
	&& substr_count( $icons, 'nino-modeswitch-name nino-sr-only' ) === 3
	&& substr_count( $icons, 'Dark' ) >= 1 );
check( '...and an argument that is not it changes nothing',
	str_contains( \Nino\Html::renderHtml( $appData, '[mode-switch words]' ), 'nino-modeswitch--icons' ) === false );
check( '...under the plain registration the words of the flag are the kernel\'s: any case, a space around them does no harm, anything else is no flag (a 1.8 site renders the component - see Components below)',
	\Nino\Html::renderHtml( $appData, '[mode-switch icons="YES"]' ) === $icons
	&& \Nino\Html::renderHtml( $appData, '[mode-switch icons=" on "]' ) === $icons
	&& \Nino\Html::renderHtml( $appData, '[mode-switch icons="no"]' ) === \Nino\Html::renderHtml( $appData, '[mode-switch]' )
	&& \Nino\Html::renderHtml( $appData, '[mode-switch icons="maybe"]' ) === \Nino\Html::renderHtml( $appData, '[mode-switch]' ) );

echo "\n";


// --- What the two files promise ----------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/modeswitch.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/modeswitch.js' );

/*	The half of a forced mode that is not a colour: :root carries
	"color-scheme: light dark", so a reader who forces the other one would get
	the browser's own furniture - scrollbars, form controls - from the system
	setting they just overrode	*/
check( 'a forced mode also tells the browser which furniture to paint',
	str_contains( $css, ':root[data-nino-mode="light"]' ) === true
	&& str_contains( $css, ':root[data-nino-mode="dark"]' ) === true
	&& preg_match_all( '/^\s*color-scheme\s*:/m', $css ) === 2 );
check( '...and says nothing about the third state, which is the one with no attribute',
	str_contains( $css, 'data-nino-mode="system"' ) === false );

/*	The switch is rendered with the hidden attribute and unhidden by the
	script, so a reader without JavaScript is not left with three buttons that
	do nothing. hidden is display:none out of the browser's own stylesheet,
	and this file gives the same element a display of its own - which beats it	*/
check( 'the stylesheet keeps the hidden switch hidden, which its own display rule would otherwise undo',
	str_contains( $css, '.nino-modeswitch[hidden]' ) === true );

/*	Nothing about this reaches the server or outlives the browser it was
	chosen in: no cookie to declare, no consent to ask for, and a cached page
	is as switchable as a fresh one	*/
check( 'the switch stores the choice in the browser and nowhere else',
	str_contains( $js, 'localStorage' ) === true
	&& str_contains( $js, 'document.cookie' ) === false
	&& str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false );
check( '...and survives a browser that refuses storage rather than failing with it',
	substr_count( $js, 'try {' ) >= 2 && substr_count( $js, 'catch' ) >= 2 );
check( 'the middle position removes the attribute rather than writing a third value',
	str_contains( $js, 'removeAttribute' ) === true );
check( 'it takes what it finds stored only if it is one of the three',
	str_contains( $js, 'MODES.indexOf' ) === true );

echo "\n";


// --- The browser half --------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so modeswitch-js-smoke.js is not run here\n\n";
else {
	echo "modeswitch-js-smoke.js\n";
	// Over the same checkout: the script is started through the kernel's own Nino.js
	$out = (string) @shell_exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/modeswitch-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Components ----------------------------------------------------------------

echo "Components - [mode-switch] as the Builder offers it\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[mode-switch]',
		'[mode-switch icons]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'mode-switch' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Modeswitch::init( $probe );
	ninoWarnings();

	check( 'the component is registered with the schema the manifest declares',
		( \Nino\Modules\Components::components( $probe )['mode-switch'] ?? null ) === ( $declared['mode-switch'] ?? false ) );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['mode-switch']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the component by its wrapper',
		\Nino\Features::shortcodes( $probe, 'modeswitch' ) === [ 'mode-switch' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/mode-switch' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'mode-switch' ) === [ 'icons' => '0', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	check( 'icons="true" and icons="yes" are the flag as icons="1" is, through the wrapper as in a hand-written file',
		\Nino\Html::renderHtml( $probe, '[mode-switch icons="true"]' ) === \Nino\Html::renderHtml( $probe, '[mode-switch icons="1"]' )
		&& \Nino\Html::renderHtml( $probe, '[mode-switch icons="yes"]' ) === \Nino\Html::renderHtml( $probe, '[mode-switch icons="1"]' )
		&& \Nino\Html::renderHtml( $probe, '[mode-switch icons="1"]' ) !== \Nino\Html::renderHtml( $probe, '[mode-switch]' ) );
	check( 'through the wrapper the words are read in any case but not with a space around them: icons="YES" is the flag, icons=" on " is not - the module reads a bool with strtolower() and no trim, and a 1.8 site always renders the component',
		\Nino\Html::renderHtml( $probe, '[mode-switch icons="YES"]' ) === \Nino\Html::renderHtml( $probe, '[mode-switch icons="1"]' )
		&& \Nino\Html::renderHtml( $probe, '[mode-switch icons=" on "]' ) === \Nino\Html::renderHtml( $probe, '[mode-switch]' )
		&& \Nino\Html::renderHtml( $probe, '[mode-switch icons=" on "]' ) !== \Nino\Html::renderHtml( $probe, '[mode-switch icons="1"]' ) );
	check( 'icons="1", which the Builder writes, is the flag',
		\Nino\Html::renderHtml( $probe, '[mode-switch icons="1"]' ) === $before['[mode-switch icons]']
		&& \Nino\Html::renderHtml( $probe, '[mode-switch icons="0" class=""]' ) === $before['[mode-switch]'] );
	check( 'a class of its own comes after the switch\'s own, no class leaves no space behind',
		str_contains( \Nino\Html::renderHtml( $probe, '[mode-switch class="header-mode"]' ), '<div class="nino-modeswitch header-mode" ' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[mode-switch icons class="header-mode"]' ), '<div class="nino-modeswitch nino-modeswitch--icons header-mode" ' ) === true
		&& str_contains( $before['[mode-switch]'], '<div class="nino-modeswitch" ' ) === true );
	check( 'a value that says "icons" is not the flag - only a bare word is, which is how the switch is written',
		\Nino\Html::renderHtml( $probe, '[mode-switch class="icons"]' ) !== $before['[mode-switch icons]']
		&& str_contains( \Nino\Html::renderHtml( $probe, '[mode-switch class="icons"]' ), 'nino-modeswitch--icons' ) === false );

} )( $appData );

echo "\n";


// --- Deactivation ------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'modeswitch' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Modeswitch', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/modeswitch/mode/dark]]'] ) === true );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'modeswitch' )['active'] ?? true ) === false );

ninoDone( $appData );
