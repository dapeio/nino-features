<?php
declare(strict_types=1);

/**
 *	Nino
 *	consent-smoke.php	Contract test for the Consent feature (Modules\Consent):
 *											the manifest and the activation through \Nino\Features
 *											with the unit's texts merged add-only, the shortcodes
 *											registering in init(), [consent] rendering only the
 *											enabled categories and the policy link when set,
 *											[consent-settings], allowed() reading the cookie the
 *											settings name, the asset bundling mechanism
 *											(\Nino\Html::addAsset() into the site's own bundles),
 *											and deactivation. Travels with the feature and runs
 *											against the checkout three levels up, or the one
 *											NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/Consent/tests/consent-smoke.php
 *	       NINO_ROOT=../nino php features/Consent/tests/consent-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'consent' );
$appData['/nino/dir'] = '';
// \Nino\AppData::prepare() (what ninoSandbox() calls) only seeds the handful
// of keys needed before config.php loads - everything else in ::DEFAULTS,
// textfiles' own directory included, arrives through the real ::init() a
// sandboxed test never runs. Rendering the banner's fills needs it, the way
// tests/design-smoke.php sets the same key for the same reason
$appData['/nino/locales/textfiles'] = '/text';
// ninoSandbox() defaults the current locale to 'de_DE' (see tests/harness.php);
// switched to English here so the rendering assertions below can compare
// against the plain install/text/en_US.php strings
$appData['./nino/locales/current'] = 'en_US';

// \Nino\Filesystem::path()'s fallback resolves a virtual path outside
// PRIVATE_DIRS/PUBLIC_DIRS against the project root - exactly how
// '/_nino/Nino.css' reaches the kernel's own file (see Consent::init()'s own
// docblock). The sandbox's project root is a fresh temp directory, not this
// feature's real parent, so the one file the asset bundler actually has to
// read - assets/consent.css and consent.js - is mirrored into it here, the
// way a real project's features/ directory holds it
$assetsDir = ninoSandboxDir( $appData ). '/features/Consent/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/consent.css', $assetsDir. '/consent.css' );
copy( dirname( __DIR__ ). '/assets/consent.js', $assetsDir. '/consent.js' );

/**
 *	@param		array 		&$appData			(reference) A sandbox's app data
 *	@param		string		$cookieName
 *	@param		string		$value
 *
 *	@return 	void
 */
function withConsentCookie( array &$appData, string $cookieName, string $value ): void {
	$_COOKIE = [ $cookieName => $value ];
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest, settings and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "consent"', is_array( $manifest ) && $manifest['key'] === 'consent' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it declares the six settings, every optional category off by default', is_array( $manifest ) && array_keys( $manifest['settings'] ) === [ 'statistics', 'marketing', 'external', 'policyUrl', 'cookieName', 'days' ]
	&& $manifest['settings']['statistics']['default'] === false && $manifest['settings']['marketing']['default'] === false && $manifest['settings']['external']['default'] === false );
check( 'the cookie name defaults to "nino_consent" and is pattern-checked', $manifest['settings']['cookieName']['default'] === 'nino_consent' && $manifest['settings']['cookieName']['pattern'] === '/^[A-Za-z0-9_-]+$/' );
check( 'the lifetime defaults to 180 days, bounded 1..365', $manifest['settings']['days']['default'] === 180 && $manifest['settings']['days']['min'] === 1 && $manifest['settings']['days']['max'] === 365 );
check( 'it keeps no data of its own - the choice lives in the browser', $manifest['data'] === [] );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'consent' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

// A key the project already has, under one of the very keys the unit is
// about to write - activation must leave it exactly as it is (add-only,
// docs/features.md: "Activating", step 3)
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/consent/title]]' => 'Cookie notice (project-edited)' ] );

$result = \Nino\Features::activate( $appData, 'consent' );
check( 'activation succeeds', $result === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Consent', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'consent' )['installed'] === '1.0.0' );
check( 'the unit merged the new keys into both locales', \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/consent/accept-all]]'] === 'Accept all'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/consent/accept-all]]'] === 'Alle akzeptieren' );
check( 'add-only: the key the project already had was not overwritten', \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/consent/title]]'] === 'Cookie notice (project-edited)' );
check( 'the settings answer their defaults', \Nino\Features::settings( $appData, 'consent' ) === [
	'statistics' => false, 'marketing' => false, 'external' => false, 'policyUrl' => '', 'cookieName' => 'nino_consent', 'days' => 180,
] );

echo "\n";


// --- init(): shortcodes and the asset bundle -----------------------------------

echo "Modules\\Consent::init() - shortcodes and the asset bundle\n";

// Modules\Assets is what turns [assets ...] into a <link>/<script> tag at
// all - the sandbox starts with no modules (see tests/harness.php), so it is
// added here purely to exercise the real shortcode end to end below; nothing
// about Consent itself depends on it being active
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'init registers [consent]', isset( $appData['./nino/html/shortcodes']['consent'] ) === true );
check( 'init registers [consent-settings]', isset( $appData['./nino/html/shortcodes']['consent-settings'] ) === true );
check( 'consent.css joined the project\'s own /.cache/style.css bundle - the same target Nino.css already sits in', in_array( '/features/Consent/assets/consent.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'consent.js joined the project\'s own /.cache/script.js bundle - so it runs on every page that bundle loads on, [consent] or not', in_array( '/features/Consent/assets/consent.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );

// End to end: the real [assets] shortcode bundles and links the file this
// feature shipped - the mechanism docs/development.md calls "Assets Are Not
// Templates" (Modules\Assets, not the fill/shortcode engine)
$styleTag = \Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
check( 'the style bundle links to .cache/style.css', str_contains( $styleTag, '<link rel="stylesheet" href="' ) === true && str_contains( $styleTag, '.cache/style.css"' ) === true );
check( 'the generated cache file actually carries this feature\'s css', str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-consent {' ) === true );

$scriptTag = \Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
check( 'the script bundle links to .cache/script.js', str_contains( $scriptTag, '<script src="' ) === true && str_contains( $scriptTag, '.cache/script.js"' ) === true );
check( 'the generated cache file actually carries this feature\'s js', str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), "nino:consent" ) === true );

echo "\n";


// --- [consent] and [consent-settings] -------------------------------------------

echo "[consent] / [consent-settings] - the banner markup\n";

$banner = \Nino\Html::renderHtml( $appData, '[consent]' );
check( 'the banner is a hidden .nino-consent div', str_starts_with( $banner, '<div class="nino-consent" hidden' ) === true );
check( 'it carries the cookie name and lifetime for a page with no other way to know them', str_contains( $banner, 'data-consent-cookie="nino_consent"' ) === true && str_contains( $banner, 'data-consent-days="180"' ) === true );
check( 'the title and text fills are in it', str_contains( $banner, 'Cookie notice (project-edited)' ) === true && str_contains( $banner, '[[/consent/text]]' ) === false ); // fills already resolved by renderHtml()
check( 'necessary is checked and disabled', str_contains( $banner, 'data-consent-category="necessary" checked disabled' ) === true );
check( 'with every optional category off, none of their checkboxes are rendered', str_contains( $banner, 'data-consent-category="statistics"' ) === false
	&& str_contains( $banner, 'data-consent-category="marketing"' ) === false && str_contains( $banner, 'data-consent-category="external"' ) === false );
check( 'with no policyUrl, no privacy link is rendered', str_contains( $banner, 'nino-consent-link' ) === false );
check( 'the three actions are all there, in order: accept all, necessary only, save', strpos( $banner, 'data-consent-action="accept-all"' ) < strpos( $banner, 'data-consent-action="necessary-only"' )
	&& strpos( $banner, 'data-consent-action="necessary-only"' ) < strpos( $banner, 'data-consent-action="save"' ) );

$settingsButton = \Nino\Html::renderHtml( $appData, '[consent-settings]' );
check( '[consent-settings] renders the reopen button', $settingsButton === '<button type="button" class="nino-consent-open">Cookie settings</button>' );

check( 'turning statistics and marketing on and setting a policy url is saved', \Nino\Features::saveSettings( $appData, 'consent', [
	'statistics' => 'true', 'marketing' => 'true', 'external' => 'false', 'policyUrl' => 'https://example.com/privacy', 'cookieName' => 'nino_consent', 'days' => '90',
] ) === [] );

$banner2 = \Nino\Html::renderHtml( $appData, '[consent]' );
check( 'statistics and marketing now have a checkbox each', str_contains( $banner2, 'data-consent-category="statistics"' ) === true && str_contains( $banner2, 'data-consent-category="marketing"' ) === true );
check( 'external stays off - a category the settings did not enable is neither shown nor storable', str_contains( $banner2, 'data-consent-category="external"' ) === false );
check( 'the optional categories are not checked/disabled the way necessary is', str_contains( $banner2, 'data-consent-category="statistics" checked disabled' ) === false );
check( 'the privacy link now renders, escaped, pointing at policyUrl', str_contains( $banner2, '<a href="https://example.com/privacy" class="nino-consent-link">Privacy policy</a>' ) === true );
check( 'the lifetime setting reaches the markup too', str_contains( $banner2, 'data-consent-days="90"' ) === true );

echo "\n";


// --- allowed() -------------------------------------------------------------------

echo "Modules\\Consent::allowed() - reads the cookie, never writes one\n";

$_COOKIE = [];
check( 'necessary is always allowed, cookie or not', \Nino\Modules\Consent::allowed( $appData, 'necessary' ) === true );
check( 'an enabled category is not allowed without a cookie', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false );
check( 'an unknown category name is never allowed', \Nino\Modules\Consent::allowed( $appData, 'tracking' ) === false );

withConsentCookie( $appData, 'nino_consent', 'necessary,statistics' );
check( 'an enabled category the cookie lists is allowed', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === true );
check( 'an enabled category the cookie does not list is not allowed', \Nino\Modules\Consent::allowed( $appData, 'marketing' ) === false );

// The base install's plain banner wrote 'accepted' or 'declined' under the
// same cookie name: a choice already made, read the way consent.js reads it
$_COOKIE['nino_consent'] = 'accepted';
check( 'the old banner\'s "accepted" counts as every enabled category', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === true && \Nino\Modules\Consent::allowed( $appData, 'necessary' ) === true );
$_COOKIE['nino_consent'] = 'declined';
check( 'its "declined" as the necessary one alone', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false && \Nino\Modules\Consent::allowed( $appData, 'necessary' ) === true );
$_COOKIE['nino_consent'] = 'necessary,statistics';

check( 'turning statistics back off is saved', \Nino\Features::saveSettings( $appData, 'consent', [ 'statistics' => 'false' ] ) === [] );
check( 'a category the site switched off is never allowed, whatever an old cookie says', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false );
\Nino\Features::saveSettings( $appData, 'consent', [ 'statistics' => 'true' ] );

check( 'the cookie name from the settings is respected', \Nino\Features::saveSettings( $appData, 'consent', [ 'cookieName' => 'acme_consent' ] ) === [] );
$_COOKIE = [];
withConsentCookie( $appData, 'nino_consent', 'necessary,statistics' ); // the old name - must no longer be read
check( 'a cookie under the old name is ignored once the setting changed', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false );
withConsentCookie( $appData, 'acme_consent', 'necessary,marketing' );
check( 'a cookie under the configured name is read', \Nino\Modules\Consent::allowed( $appData, 'marketing' ) === true );
check( 'and only lists what it actually names', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false );
\Nino\Features::saveSettings( $appData, 'consent', [ 'cookieName' => 'nino_consent' ] );
$_COOKIE = [];

echo "\n";


// --- Deactivation -----------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'consent' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Consent', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the settings survive deactivation', \Nino\Features::setting( $appData, 'consent', 'marketing', false ) === true );
check( 'the merged texts survive deactivation', \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/consent/accept-all]]'] === 'Accept all' );

ninoDone( $appData );
