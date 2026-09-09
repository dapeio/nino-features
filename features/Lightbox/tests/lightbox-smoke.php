<?php
declare(strict_types=1);

/**
 *	Nino
 *	lightbox-smoke.php	Contract test for the Lightbox feature
 *											(Modules\Lightbox): the manifest, the activation
 *											through \Nino\Features, the one thing the class does -
 *											putting lightbox.css/lightbox.js into the site's own
 *											asset bundles (\Nino\Html::addAsset()) - and
 *											deactivation. The behaviour those two files carry is
 *											the browser's, and lightbox-js-smoke.js beside this
 *											file measures it; this test runs that one too where
 *											node is on the path. Travels with the feature and
 *											runs against the checkout three levels up, or the one
 *											NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/Lightbox/tests/lightbox-smoke.php
 *	       NINO_ROOT=../nino php features/Lightbox/tests/lightbox-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'lightbox' );
$appData['/nino/dir'] = '';

// \Nino\Filesystem::path()'s fallback resolves a virtual path outside
// PRIVATE_DIRS/PUBLIC_DIRS against the project root - exactly how
// '/_nino/Nino.css' reaches the kernel's own file. The sandbox's project root
// is a fresh temp directory, not this feature's real parent, so the two files
// the asset bundler actually has to read are mirrored into it here, the way a
// real project's features/ directory holds them
$assetsDir = ninoSandboxDir( $appData ). '/features/Lightbox/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/lightbox.css', $assetsDir. '/lightbox.css' );
copy( dirname( __DIR__ ). '/assets/lightbox.js', $assetsDir. '/lightbox.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "lightbox"', is_array( $manifest ) && $manifest['key'] === 'lightbox' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

// Read from the file rather than from $manifest: this repository's CI runs
// every feature's test against Nino's main and against its latest tag, and a
// kernel released before categories existed drops the field on the way through
$raw = include $dir. '/feature.php';
check( 'it is filed under ui', ( $raw['category'] ?? '' ) === 'ui' );
check( 'it needs no other feature - it is the one others depend on', $manifest['requires'] === [] );
check( 'it keeps no data of its own - what it opens is markup the page already has', $manifest['data'] === [] );
check( 'and carries no settings: a static asset cannot read one, so the link carries what differs', $manifest['settings'] === [] );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'lightbox' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'lightbox' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Lightbox', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'lightbox' )['installed'] === '1.0.0' );
check( 'activation wrote no template and no text of its own into the project', is_dir( ninoSandboxDir( $appData ). '/templates' ) === false
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) === [] );

echo "\n";


// --- init(): the asset bundle --------------------------------------------------

echo "Modules\\Lightbox::init() - the asset bundle\n";

// Modules\Assets is what turns [assets ...] into a <link>/<script> tag at
// all - the sandbox starts with no modules, so it is added here purely to
// exercise the real shortcode end to end below
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'lightbox.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Lightbox/assets/lightbox.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'lightbox.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Lightbox/assets/lightbox.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers nothing else - no shortcode, no route', ( $appData['./nino/html/shortcodes']['lightbox'] ?? null ) === null
	&& ( $appData['/nino/http/routes']['GET://lightbox'] ?? null ) === null );

check( 'the generated cache file actually carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-lightbox {' );
} )() );
check( 'and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'nino-lightbox-stage' );
} )() );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/lightbox.css' );
$js	= (string) file_get_contents( dirname( __DIR__ ). '/assets/lightbox.js' );

// Nothing in the stylesheet matches before the script opens something: a
// visitor without JavaScript gets a thumbnail that is a link to a bigger
// picture, which works
check( 'every rule hangs off .nino-lightbox, which only exists once one is open', preg_match( '/^(?!\.nino-lightbox|:root|@|\s|\}|\/\*|\*)\S.*\{/m', $css ) !== 1 );
check( 'the page behind it is locked through the root element, not the body', str_contains( $css, '.nino-lightbox-lock {' ) === true && str_contains( $js, "documentElement.classList.add('nino-lightbox-lock')" ) === true );
check( 'a reduced-motion preference stops the animation without stopping the lightbox', str_contains( $css, 'prefers-reduced-motion: reduce' ) === true
	&& str_contains( $css, '.nino-lightbox.is-swapping .nino-lightbox-image {' ) === true );

// A caption and an alt are editor text. Written with textContent, never as
// markup - the same rule every panel of the workbench follows
check( 'the script writes every word as text, never as markup', str_contains( $js, 'innerHTML' ) === false );
check( 'it listens once, on the document, so a gallery rendered later needs no second call', preg_match( '/dc\.addEventListener\(\s*\'click\'/', $js ) === 1
	&& str_contains( $js, 'querySelectorAll( \'a[\' + ATTR + \']\' )' ) === true );
check( 'a link only opens where its href really is an image - the overlay puts that value into an <img src>', str_contains( $js, 'IMAGE.test' ) === true );
check( 'a modified click is left to the browser: that is the visitor asking for a tab of their own', str_contains( $js, 'ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey' ) === true );
check( 'the caption is what the page already says the picture is, never a filename', str_contains( $js, "getAttribute('data-caption')" ) === true
	&& str_contains( $js, "getAttribute('alt')" ) === true && str_contains( $js, 'basename' ) === false );

echo "\n";


// --- The behaviour, measured over a dom stand-in ---------------------------------

echo "lightbox.js - its own behaviour test\n";

$jsTest	= __DIR__. '/lightbox-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	// Not a failure and not a pass: say which, rather than counting a check
	// that never ran (Nino's AGENTS.md, section 10)
	echo "  --  - node is not available here: lightbox-js-smoke.js was NOT run\n";
} else {
	$output	= [];
	$status	= 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = trim( (string) ( $output === [] ? '' : end( $output ) ) );
	check( 'lightbox-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

echo "\n";


// --- Deactivation -----------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'lightbox' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Lightbox', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'lightbox' )['active'] ?? true ) === false );

ninoDone( $appData );
