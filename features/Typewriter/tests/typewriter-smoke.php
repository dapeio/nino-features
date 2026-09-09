<?php
declare(strict_types=1);

/**
 *	Nino
 *	typewriter-smoke.php	Contract test for the Typewriter feature
 *												(Modules\Typewriter): the manifest, the activation
 *												through \Nino\Features, the one thing the class
 *												does - putting typewriter.css/typewriter.js into
 *												the site's own asset bundles
 *												(\Nino\Html::addAsset()) - and deactivation. The
 *												behaviour those two files carry is the browser's,
 *												and typewriter-js-smoke.js beside this file
 *												measures it; this test runs that one too where
 *												node is on the path. Travels with the feature and
 *												runs against the checkout three levels up, or the
 *												one NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/Typewriter/tests/typewriter-smoke.php
 *	       NINO_ROOT=../nino php features/Typewriter/tests/typewriter-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'typewriter' );
$appData['/nino/dir'] = '';

// \Nino\Filesystem::path()'s fallback resolves a virtual path outside
// PRIVATE_DIRS/PUBLIC_DIRS against the project root - exactly how
// '/_nino/Nino.css' reaches the kernel's own file (see Typewriter::init()'s
// own docblock). The sandbox's project root is a fresh temp directory, not
// this feature's real parent, so the two files the asset bundler actually
// has to read are mirrored into it here, the way a real project's features/
// directory holds them
$assetsDir = ninoSandboxDir( $appData ). '/features/Typewriter/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/typewriter.css', $assetsDir. '/typewriter.css' );
copy( dirname( __DIR__ ). '/assets/typewriter.js', $assetsDir. '/typewriter.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "typewriter"', is_array( $manifest ) && $manifest['key'] === 'typewriter' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
// The name is the feature's alone - what it is for lives in 'category', which
// is what the panel groups and filters by. A category in the name would sort
// the list by category instead of by name and say the same thing twice, once
// per language
// Read from the file rather than from $manifest: this repository's CI runs
// every feature's test against Nino's main and against its latest tag, and a
// kernel released before categories existed drops the field on the way
// through - which would leave nothing to check on exactly the run where the
// manifest is what is being checked
$raw = include $dir. '/feature.php';
check( 'it is filed under ui, and says so in the field rather than in its name', ( $raw['category'] ?? '' ) === 'ui'
	&& str_contains( \Nino\Features::localized( $manifest['name'], 'en_US' ), ':' ) === false
	&& str_contains( \Nino\Features::localized( $manifest['name'], 'de_DE' ), ':' ) === false );
check( 'it needs no other feature', $manifest['requires'] === [] );
check( 'it keeps no data of its own - it animates the markup a project already has', $manifest['data'] === [] );
check( 'and carries no settings: what a typewriter is timed with belongs to the element being typed', $manifest['settings'] === [] );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'typewriter' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'typewriter' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Typewriter', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'typewriter' )['installed'] === '1.0.0' );

// It has no install unit, and that is the point: a typewriter is written
// into a template by whoever wants one, so there is no file to copy in
check( 'activation wrote no template and no text of its own into the project', is_dir( ninoSandboxDir( $appData ). '/templates' ) === false
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) === [] );

echo "\n";


// --- init(): the asset bundle --------------------------------------------------

echo "Modules\\Typewriter::init() - the asset bundle\n";

// Modules\Assets is what turns [assets ...] into a <link>/<script> tag at
// all - the sandbox starts with no modules (see tests/harness.php), so it is
// added here purely to exercise the real shortcode end to end below; nothing
// about Typewriter itself depends on it being active
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'typewriter.css joined the project\'s own /.cache/style.css bundle - the same target Nino.css already sits in', in_array( '/features/Typewriter/assets/typewriter.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'typewriter.js joined the project\'s own /.cache/script.js bundle - so it runs on every page that bundle loads on', in_array( '/features/Typewriter/assets/typewriter.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers nothing else - no shortcode, no route', ( $appData['./nino/html/shortcodes']['typewriter'] ?? null ) === null
	&& ( $appData['/nino/http/routes']['GET://typewriter'] ?? null ) === null );

// End to end: the real [assets] shortcode bundles and links the files this
// feature shipped - the mechanism docs/development.md calls "Assets Are Not
// Templates" (Modules\Assets, not the fill/shortcode engine)
$styleTag = \Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
check( 'the style bundle links to .cache/style.css', str_contains( $styleTag, '<link rel="stylesheet" href="' ) === true && str_contains( $styleTag, '.cache/style.css"' ) === true );
check( 'the generated cache file actually carries this feature\'s css', str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-typewriter-rest {' ) === true );

$scriptTag = \Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
check( 'the script bundle links to .cache/script.js', str_contains( $scriptTag, '<script src="' ) === true && str_contains( $scriptTag, '.cache/script.js"' ) === true );
check( 'the generated cache file actually carries this feature\'s js', str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'nino-typewriter-cursor' ) === true );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/typewriter.css' );
$js	= (string) file_get_contents( dirname( __DIR__ ). '/assets/typewriter.js' );

// Every rule in the stylesheet hangs off .nino-typewriter-line, the class the
// script writes - that is what leaves the markup alone where the script does
// not run (no JavaScript, reduced motion). A rule on .nino-typewriter alone
// would stack the lines on top of each other for a visitor who never gets the
// script, and hide all but one of them
check( 'the container itself is only ever styled through the class the script writes', preg_match( '/^\.nino-typewriter\s*\{/m', $css ) !== 1 );
check( 'the stylesheet keeps the untyped rest laid out, so nothing below a typewriter moves', str_contains( $css, '.nino-typewriter-rest {' ) === true && str_contains( $css, 'visibility: hidden;' ) === true );
check( 'and the cursor out of the line box, so nothing inside one moves either', preg_match( '/\.nino-typewriter-cursor\s*\{[^}]*width:\s*0;/s', $css ) === 1 );
check( 'the script asks for the reduced-motion preference before it changes anything', str_contains( $js, "prefers-reduced-motion: reduce" ) === true );
check( 'and writes the lines with textContent, never as markup', str_contains( $js, 'innerHTML' ) === false );

echo "\n";


// --- The browser half ------------------------------------------------------------

echo "typewriter.js - its own behaviour test\n";

$jsTest	= __DIR__. '/typewriter-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	// Not a failure and not a pass: say which, rather than counting a check
	// that never ran (Nino's AGENTS.md, section 10)
	echo "  --  - node is not available here: typewriter-js-smoke.js was NOT run\n";
} else {
	$output	= [];
	$status	= 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = trim( (string) ( $output === [] ? '' : end( $output ) ) );
	check( 'typewriter-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

echo "\n";


// --- Deactivation -----------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'typewriter' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Typewriter', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'typewriter' )['active'] ?? true ) === false );

ninoDone( $appData );
