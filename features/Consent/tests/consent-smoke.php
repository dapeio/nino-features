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
 *											(the manifest's assets into the site's own bundles,
 *											\Nino\Features::registerAssets()),
 *											the Content-Security-Policy gaining the hosts of the
 *											page's placeholders, the privacy policy of Nino's Legal
 *											module (the banner's link when no address is set, the
 *											settings button in the policy's own section - both only
 *											where the module is there) and deactivation. The browser
 *											half is consent-js-smoke.js beside this file, which
 *											this test runs too where node is on the path.
 *											Travels with the feature and runs against the
 *											checkout three levels up, or the one NINO_ROOT names
 *											(see tests/harness.php there).
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
// ninoSandbox() defaults the current locale to 'de_DE' (see tests/harness.php);
// switched to English here so the rendering assertions below can compare
// against the plain install/text/en_US.php strings
$appData['./nino/locales/current'] = 'en_US';

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

/**
 *	The rules of a stylesheet, a block each, the blocks of a media query read as rules of their own with its condition in 'at'
 *
 *	@param		string		$css
 *	@param		string		$at						The condition the css stands in, '' for none
 *
 *	@return 	array											[ at, selector, body ] for each rule, in the order they stand
 */
function consentRules( string $css, string $at = '' ): array {

	$css		= (string) preg_replace( '#/\*.*?\*/#s', '', $css );
	$rules	= [];
	$offset	= 0;
	$length	= strlen( $css );

	while( ( $open = strpos( $css, '{', $offset ) ) !== false ) {

		$head		= trim( substr( $css, $offset, $open - $offset ) );
		$depth	= 1;
		$i			= $open + 1;

		while( $i < $length && $depth > 0 ) {
			$depth += $css[$i] === '{' ? 1 : ( $css[$i] === '}' ? -1 : 0 );
			$i++;
		}

		$body = trim( substr( $css, $open + 1, $i - $open - 2 ) );

		if( str_starts_with( $head, '@media' ) === true )
			$rules = array_merge( $rules, consentRules( $body, $head ) );
		else
			$rules[] = [ 'at' => $at, 'selector' => $head, 'body' => $body ];

		$offset = $i;
	}

	return $rules;
}

/**
 *	The declarations of the body of a rule
 *
 *	@param		string		$body
 *
 *	@return 	array											property => value, the last of a property wins
 */
function consentDeclarations( string $body ): array {

	preg_match_all( '/(--[a-z0-9-]+|[a-z-]+)\s*:\s*([^;]+?)\s*(?:;|$)/i', $body, $found, PREG_SET_ORDER );

	$declared = [];

	foreach( $found as $one )
		$declared[ $one[1] ] = $one[2];

	return $declared;
}

/**
 *	The colours a stylesheet writes as a literal in the value of a declaration: a hex colour, a colour function or the name of a
 *	colour. What a value reads of the kernel - var(--color-ink) - is the name of a property and none of these; transparent and
 *	color-mix() of the kernel's tokens are none either
 *
 *	@param		string		$css
 *
 *	@return 	array											property: literal, for each
 */
function consentColourLiterals( string $css ): array {

	$names = explode( ' ', 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen' );
	$literal = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(|(?<![\w-])(?:'. implode( '|', $names ). ')(?![\w-])/i';
	$found = [];

	foreach( consentRules( $css ) as $rule )
		foreach( consentDeclarations( $rule['body'] ) as $property => $value )
			if( preg_match( $literal, (string) preg_replace( '/--[a-z0-9-]+/i', '', $value ), $match ) === 1 )
				$found[] = $property. ': '. $match[0];

	return $found;
}

/**
 *	A variant of the Design feature, read by its grammar: the lines of the head by their tag
 *
 *	@param		string		$file
 *
 *	@return 	array											tag (@name, @knob ...) => the texts after it, one for each line
 */
function consentHead( string $file ): array {

	preg_match( '#^\s*/\*(.*?)\*/#s', (string) file_get_contents( $file ), $comment );

	$tags = [];

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 )
			$tags[ $found[1] ][] = $found[2];

	return $tags;
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
check( 'the cookie name defaults to "nino_consent" and is pattern-checked', $manifest['settings']['cookieName']['default'] === 'nino_consent' && $manifest['settings']['cookieName']['pattern'] === '/^[A-Za-z0-9_-]+$/D' );
check( 'the lifetime defaults to 180 days, bounded 1..365', $manifest['settings']['days']['default'] === 180 && $manifest['settings']['days']['min'] === 1 && $manifest['settings']['days']['max'] === 365 );
check( 'it keeps no data of its own - the choice lives in the browser', $manifest['data'] === [] );
check( 'it declares the two callbacks it registers - the output phase, where the finished response is in hand, and the Legal module\'s section',
	array_keys( $manifest['manual']['callbacks'] ) === [ '/nino/http/output', '/nino/legal/section' ] );
check( 'its unit brings the feature\'s section of the privacy policy, and says so', ( include dirname( __DIR__ ). '/install/manifest.php' )['elements'] === [ 'privacy' => 'elements/privacy.php' ]
	&& isset( $manifest['manual']['install']['elements/privacy.php'] ) === true );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'consent' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

// A key the project already has, under one of the very keys the unit is
// about to write - activation must leave it exactly as it is (docs/features.md,
// "The install unit": a unit is applied add-only, on every activation)
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/feature/consent/banner/title]]' => 'Cookie notice (project-edited)' ] );

$result = \Nino\Features::activate( $appData, 'consent' );
check( 'activation succeeds', $result === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Consent', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'consent' )['installed'] === $manifest['version'] );
check( 'the unit merged the new keys into both locales', \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/consent/action/accept-all]]'] === 'Accept all'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/feature/consent/action/accept-all]]'] === 'Alle akzeptieren' );
check( 'add-only: the key the project already had was not overwritten', \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/consent/banner/title]]'] === 'Cookie notice (project-edited)' );
check( 'the settings answer their defaults', \Nino\Features::settings( $appData, 'consent' ) === [
	'statistics' => false, 'marketing' => false, 'external' => false, 'policyUrl' => '', 'cookieName' => 'nino_consent', 'days' => 180,
] );

echo "\n";


// --- init() and the manifest's assets: shortcodes and the asset bundle ----------

echo "Modules\\Consent::init() and the manifest's assets - shortcodes and the asset bundle\n";

// Modules\Assets is what turns [assets ...] into a <link>/<script> tag at
// all - the sandbox starts with no modules (see tests/harness.php), so it is
// added here purely to exercise the real shortcode end to end below; nothing
// about Consent itself depends on it being active. The bundles are filled the
// way \Nino\init() fills them, before the modules' init()
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

check( 'init registers [consent]', isset( $appData['./nino/html/shortcodes']['consent'] ) === true );
check( 'init registers [consent-settings]', isset( $appData['./nino/html/shortcodes']['consent-settings'] ) === true );
check( 'init hooks the output phase, where the body is rendered - the response phase runs before it',
	in_array( [ \Nino\Modules\Consent::class, 'callbackOutput' ], array_merge( ...( $appData['./nino/callbacks']['/nino/http/output'] ?? [ [] ] ) ), true ) === true );
check( 'init hooks the Legal module\'s section, which only that module ever fires',
	in_array( [ \Nino\Modules\Consent::class, 'callbackLegalSection' ], array_merge( ...( $appData['./nino/callbacks']['/nino/legal/section'] ?? [ [] ] ) ), true ) === true );
check( 'consent.css joined the project\'s own /.cache/style.css bundle - the same target Nino.css already sits in', in_array( '/features/Consent/assets/consent.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'consent.js joined the project\'s own /.cache/script.js bundle - so it runs on every page that bundle loads on, [consent] or not', in_array( '/features/Consent/assets/consent.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );

// End to end: the real [assets] shortcode bundles and links the file this
// feature shipped - the mechanism docs/development.md calls "Assets Are Not
// Templates" (Modules\Assets, not the fill/shortcode engine)
$styleTag = \Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
// The '?v=' is the bundle's own hash, which \Nino\Modules\Assets puts on the
// url so a regenerated bundle is not served out of a browser's cache
check( 'the style bundle links to .cache/style.css', str_contains( $styleTag, '<link rel="stylesheet" href="' ) === true && preg_match( '#\.cache/style\.css(\?v=[a-f0-9]+)?"#', $styleTag ) === 1 );
check( 'the generated cache file actually carries this feature\'s css', str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-consent {' ) === true );

$scriptTag = \Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
check( 'the script bundle links to .cache/script.js', str_contains( $scriptTag, '<script src="' ) === true && preg_match( '#\.cache/script\.js(\?v=[a-f0-9]+)?"#', $scriptTag ) === 1 );
check( 'the generated cache file actually carries this feature\'s js', str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), "nino:consent" ) === true );

echo "\n";


// --- [consent] and [consent-settings] -------------------------------------------

echo "[consent] / [consent-settings] - the banner markup\n";

$banner = \Nino\Html::renderHtml( $appData, '[consent]' );
check( 'the banner is a hidden .nino-consent div', str_starts_with( $banner, '<div class="nino-consent" hidden' ) === true );
check( 'it is a dialog with a name and a description, and can take the focus without being in the tab order',
	str_contains( $banner, ' role="dialog" aria-labelledby="nino-consent-title" aria-describedby="nino-consent-text" tabindex="-1" ' ) === true
	&& str_contains( $banner, 'class="nino-consent-title" id="nino-consent-title"' ) === true
	&& str_contains( $banner, 'class="nino-consent-text" id="nino-consent-text"' ) === true );
preg_match_all( '/<button type="button" class="([^"]*)" data-consent-action="([a-z-]+)"/', $banner, $actionButtons, PREG_SET_ORDER );
check( 'each of the three actions is a kernel button with the hook of the script - nino-btn and nino-consent-btn - and all three carry the same classes',
	count( $actionButtons ) === 3 && count( array_unique( array_column( $actionButtons, 1 ) ) ) === 1
	&& preg_match( '/^nino-btn(?: nino-btn--[a-z-]+)* nino-consent-btn$/', $actionButtons[0][1] ) === 1 );
check( 'no action is the primary one - accepting everything is no easier to press than refusing it',
	str_contains( $banner, 'nino-btn--primary' ) === false && str_contains( $banner, 'nino-consent-btn--' ) === false );
check( 'it carries the cookie name and lifetime for a page with no other way to know them', str_contains( $banner, 'data-consent-cookie="nino_consent"' ) === true && str_contains( $banner, 'data-consent-days="180"' ) === true );
check( 'the title and text fills are in it', str_contains( $banner, 'Cookie notice (project-edited)' ) === true && str_contains( $banner, '[[/feature/consent/banner/text]]' ) === false ); // fills already resolved by renderHtml()
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

/*	The words of a category are a composed key, /feature/consent/category-[[category]]/name: a template
	that broke the composition would still render every attribute above. The words themselves, in both
	languages, and no fill left standing	*/
check( 'each category carries its own words, from a key the template composes, in English', str_contains( $banner2, '>Statistics</span>' ) === true && str_contains( $banner2, '>Marketing</span>' ) === true
	&& str_contains( $banner2, 'Anonymised usage data' ) === true && str_contains( $banner2, '[[' ) === false );
$appData['./nino/locales/current'] = 'de_DE';
$bannerDe = \Nino\Html::renderHtml( $appData, '[consent]' );
check( '...and in German', str_contains( $bannerDe, '>Statistik</span>' ) === true && str_contains( $bannerDe, '>Marketing</span>' ) === true && str_contains( $bannerDe, '>Statistics</span>' ) === false && str_contains( $bannerDe, '[[' ) === false );
$appData['./nino/locales/current'] = 'en_US';

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

// The banner Nino's base install shipped up to 1.3.x wrote 'accepted' or
// 'declined' under the same cookie name: a choice already made, read the way
// consent.js reads it
$_COOKIE['nino_consent'] = 'accepted';
check( 'the older banner\'s "accepted" counts as every enabled category', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === true && \Nino\Modules\Consent::allowed( $appData, 'necessary' ) === true );
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
/*	A cookie is sent by the client: 'acme_consent[]=x' parses to an array,
	and cast to string that raises "Array to string conversion" - a level
	the kernel treats as fatal, ie. an unauthenticated 500 on every page
	that asks whether something is allowed	*/
ninoWarnings();
$_COOKIE['acme_consent'] = [ 'necessary,statistics' ];
check( 'an array-shaped consent cookie allows nothing, and raises nothing',
	\Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false
	&& \Nino\Modules\Consent::allowed( $appData, 'necessary' ) === true
	&& ninoWarnings() === [] );
unset( $_COOKIE['acme_consent'] );

withConsentCookie( $appData, 'acme_consent', 'necessary,marketing' );
check( 'a cookie under the configured name is read', \Nino\Modules\Consent::allowed( $appData, 'marketing' ) === true );
check( 'and only lists what it actually names', \Nino\Modules\Consent::allowed( $appData, 'statistics' ) === false );
\Nino\Features::saveSettings( $appData, 'consent', [ 'cookieName' => 'nino_consent' ] );
$_COOKIE = [];

echo "\n";


// --- The policy -------------------------------------------------------------------

echo "The Content-Security-Policy - the hosts the page's placeholders load from\n";

/*	A placeholder is released by consent.js into a real <script src>, and the
	shipped policy lets a script come from 'self' alone: without the host in
	script-src a visitor's consent would release a script the browser blocks.
	The hosts are read out of the finished body, so what counts is held to what
	a project wrote on purpose: a category the site offers, https, a plain host	*/
\Nino\Features::saveSettings( $appData, 'consent', [ 'statistics' => 'true', 'marketing' => 'false', 'external' => 'false', 'cookieName' => 'nino_consent' ] );
$request = [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/' ];
\Nino\Http::request( $appData, $request );
$shipped = (string) $request['/nino/http/response']['header']['Content-Security-Policy'];

/**
 *	The policy a page ends up with: the output callbacks run over a response
 *	carrying $policy and $body
 *
 *	@param		array			&$appData
 *	@param		array			$request			A response from \Nino\Http::request()
 *	@param		mixed			$body
 *	@param		string		$policy
 *
 *	@return 	string
 */
function consentPolicy( array &$appData, array $request, mixed $body, string $policy ): string {

	$request['/nino/http/response']['body'] = $body;
	$request['/nino/http/response']['header']['Content-Security-Policy'] = $policy;
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );

	return (string) $request['/nino/http/response']['header']['Content-Security-Policy'];
}

$jstext = "default-src 'self'; script-src 'self' 'nonce-x'; img-src * data:";
$stats = '<script type="text/plain" data-consent="statistics" data-src="https://stats.example/tag.js"></script>';

check( 'the shipped policy names no script host but its own - the case this exists for', str_contains( $shipped, 'script-src' ) === false && str_contains( $shipped, "default-src 'self'" ) === true );
check( 'an offered category\'s placeholder adds its host to the script-src the jstext block already wrote, in place',
	consentPolicy( $appData, $request, '<p>x</p>'. $stats, $jstext ) === "default-src 'self'; script-src 'self' 'nonce-x' https://stats.example; img-src * data:" );
check( '...and builds one from default-src where the policy has none, carrying default-src\'s whole list',
	consentPolicy( $appData, $request, $stats, "default-src 'self' https://cdn.example; img-src *" ) === "default-src 'self' https://cdn.example; img-src *; script-src 'self' https://cdn.example https://stats.example" );
check( 'on the shipped policy that is script-src \'self\' and the host', consentPolicy( $appData, $request, $stats, $shipped ) === $shipped. "; script-src 'self' https://stats.example" );
check( '"necessary" is always offered',
	consentPolicy( $appData, $request, '<script type="text/plain" data-consent="necessary" data-src="https://cdn.example/n.js"></script>', $jstext ) === "default-src 'self'; script-src 'self' 'nonce-x' https://cdn.example; img-src * data:" );
check( 'a category the settings switched off adds nothing - markup cannot open the policy for one nobody offers',
	consentPolicy( $appData, $request, '<script type="text/plain" data-consent="marketing" data-src="https://ads.example/a.js"></script>', $jstext ) === $jstext
	&& consentPolicy( $appData, $request, '<script type="text/plain" data-consent="tracking" data-src="https://ads.example/a.js"></script>', $jstext ) === $jstext );
check( 'a second one in the same page, in another order of attributes and with &amp; in the query, adds its host once',
	consentPolicy( $appData, $request, '<script data-src="https://stats.example/a.js?x=1&amp;y=2" data-consent=\'statistics\' type=text/plain></script>'. $stats, $jstext ) === "default-src 'self'; script-src 'self' 'nonce-x' https://stats.example; img-src * data:" );
check( 'the type is compared without regard to case, as the browser does', str_contains( consentPolicy( $appData, $request, '<SCRIPT TYPE="Text/Plain" DATA-CONSENT="statistics" DATA-SRC="https://stats.example/t.js"></SCRIPT>', $jstext ), 'https://stats.example' ) === true );

foreach( [
	'http:'									=> '<script type="text/plain" data-consent="statistics" data-src="http://stats.example/t.js"></script>',
	'protocol-relative'			=> '<script type="text/plain" data-consent="statistics" data-src="//stats.example/t.js"></script>',
	'relative'							=> '<script type="text/plain" data-consent="statistics" data-src="/assets/t.js"></script>',
	'inline'								=> '<script type="text/plain" data-consent="statistics">console.log( 1 );</script>',
	'userinfo'							=> '<script type="text/plain" data-consent="statistics" data-src="https://stats.example@evil.example/t.js"></script>',
	'userinfo with a password'	=> '<script type="text/plain" data-consent="statistics" data-src="https://u:p@stats.example/t.js"></script>',
	'an ip address'					=> '<script type="text/plain" data-consent="statistics" data-src="https://192.0.2.7/t.js"></script>',
	'an ip in another notation'	=> '<script type="text/plain" data-consent="statistics" data-src="https://2130706433/t.js"></script>',
	'an ipv6 literal'				=> '<script type="text/plain" data-consent="statistics" data-src="https://[2001:db8::1]/t.js"></script>',
	'a non-ascii host'			=> "<script type=\"text/plain\" data-consent=\"statistics\" data-src=\"https://st\u{e4}ts.example/t.js\"></script>",
	'a host with a wildcard'	=> '<script type="text/plain" data-consent="statistics" data-src="https://*.example/t.js"></script>',
	'a source list injected through the host' => '<script type="text/plain" data-consent="statistics" data-src="https://stats.example;script-src *">',
	'a script that is not a placeholder' => '<script data-consent="statistics" data-src="https://stats.example/t.js"></script>',
	'another type'					=> '<script type="module" data-consent="statistics" data-src="https://stats.example/t.js"></script>',
	'no category'						=> '<script type="text/plain" data-src="https://stats.example/t.js"></script>',
	'a data-src inside another attribute\'s value' => '<script type="text/plain" data-consent="statistics" title=\'data-src="https://evil.example/t.js"\'></script>',
	'a placeholder in a text that only says so' => '<p>Use a text/plain script with data-src="https://evil.example/t.js"</p>',
] as $what => $markup )
	check( $what. ' adds nothing', consentPolicy( $appData, $request, $markup, $jstext ) === $jstext );

foreach( [
	'a hex number as its last label'	=> 'https://stats.0x7f000001/t.js',
	'a hex number with nothing after the 0x'	=> 'https://stats.example.0x/t.js',
	'a hex number in capitals'				=> 'https://stats.0X7F000001/t.js',
	'a host with a newline at its end'	=> "https://stats.example\n/t.js",
] as $what => $address )
	check( $what. ' adds nothing - it is an ip address written in a form php does not call one', consentPolicy( $appData, $request, '<script type="text/plain" data-consent="statistics" data-src="'. $address. '"></script>', $jstext ) === $jstext );
check( 'while a hex-looking label that is not the last one is a host like another',
	str_contains( consentPolicy( $appData, $request, '<script type="text/plain" data-consent="statistics" data-src="https://0x7f.stats.example/t.js"></script>', $jstext ), 'https://0x7f.stats.example' ) === true );
check( 'a scheme in capitals is https all the same, as a browser reads it - the policy names the origin in lower case',
	consentPolicy( $appData, $request, '<script type="text/plain" data-consent="statistics" data-src="HTTPS://Stats.Example/t.js"></script>', $jstext ) === "default-src 'self'; script-src 'self' 'nonce-x' https://stats.example; img-src * data:" );

// The workbench is not a page of the site: it sends a policy of its own and nobody's placeholder is its business
$adminRequest = $request;
$adminRequest['/nino/http/request']['uri'] = '/_admin';
$adminRequest['/nino/http/response']['uri'] = '/_admin';
$adminBelow = $request;
$adminBelow['/nino/http/request']['uri'] = '/_admin/panel';
$notAdmin = $request;
$notAdmin['/nino/http/request']['uri'] = '/_administrator';
$notAdmin['/nino/http/response']['uri'] = '/_administrator';
check( 'a response of /_admin or below is left alone, its policy byte for byte', consentPolicy( $appData, $adminRequest, $stats, $jstext ) === $jstext
	&& consentPolicy( $appData, $adminBelow, $stats, $jstext ) === $jstext );
check( '...while a page that only starts with the same letters is a page of the site', str_contains( consentPolicy( $appData, $notAdmin, $stats, $jstext ), 'https://stats.example' ) === true );

// The button the script hides must stay hidden whatever display a rule gives it
check( 'consent.css keeps a hidden [consent-settings] button hidden', preg_match( '/\.nino-consent-open\[hidden\]\s*\{\s*display:\s*none;\s*\}/', (string) file_get_contents( dirname( __DIR__ ). '/assets/consent.css' ) ) === 1 );

check( 'an attribute that comes twice counts once, the first - the way a browser reads it',
	consentPolicy( $appData, $request, '<script type="text/plain" data-consent="statistics" data-src="https://stats.example/t.js" data-src="https://evil.example/t.js"></script>', $jstext ) === "default-src 'self'; script-src 'self' 'nonce-x' https://stats.example; img-src * data:" );
check( 'a ">" inside a quoted value does not end the tag early',
	str_contains( consentPolicy( $appData, $request, '<script type="text/plain" data-consent="statistics" data-x="a>b" data-src="https://stats.example/t.js"></script>', $jstext ), 'https://stats.example' ) === true );
check( 'script-src \'none\' stays, and so does a default-src of \'none\' with no script-src',
	consentPolicy( $appData, $request, $stats, "default-src 'self'; script-src 'none'" ) === "default-src 'self'; script-src 'none'"
	&& consentPolicy( $appData, $request, $stats, "default-src 'none'; img-src *" ) === "default-src 'none'; img-src *" );
check( 'a script-src with no source at all blocks everything just as \'none\' does, and stays - so does a default-src that has none',
	consentPolicy( $appData, $request, $stats, "default-src 'self'; script-src; img-src *" ) === "default-src 'self'; script-src; img-src *"
	&& consentPolicy( $appData, $request, $stats, "default-src 'self'; script-src" ) === "default-src 'self'; script-src"
	&& consentPolicy( $appData, $request, $stats, "default-src; img-src *" ) === "default-src; img-src *" );
check( 'script-src-elem is extended where the policy has one, and not created where it has none',
	consentPolicy( $appData, $request, $stats, "default-src 'self'; script-src 'self'; script-src-elem 'self'" ) === "default-src 'self'; script-src 'self' https://stats.example; script-src-elem 'self' https://stats.example"
	&& str_contains( consentPolicy( $appData, $request, $stats, $jstext ), 'script-src-elem' ) === false );
check( 'a policy with no default-src and no script-src is unrestricted already and stays',
	consentPolicy( $appData, $request, $stats, "img-src *" ) === "img-src *" );
check( 'a body with no placeholders is untouched, byte for byte', consentPolicy( $appData, $request, '<p>Hello</p>', $shipped ) === $shipped );
check( 'an array body and a response with no policy are untouched',
	consentPolicy( $appData, $request, [ 'html' => $stats ], $jstext ) === $jstext && consentPolicy( $appData, $request, $stats, '' ) === '' );

\Nino\Features::saveSettings( $appData, 'consent', [ 'marketing' => 'true' ] );
check( 'switching the category on is what lets its placeholders count',
	str_contains( consentPolicy( $appData, $request, '<script type="text/plain" data-consent="marketing" data-src="https://ads.example/a.js"></script>', $jstext ), 'https://ads.example' ) === true );
\Nino\Features::saveSettings( $appData, 'consent', [ 'statistics' => 'true', 'marketing' => 'true' ] );

echo "\n";


// --- Components ----------------------------------------------------------------

echo "Components - [consent-settings] as the Builder offers it\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[consent-settings]',
		'[consent]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'consent', 'consent-settings' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Consent::init( $probe );
	ninoWarnings();

	check( 'the component is registered with the schema the manifest declares',
		( \Nino\Modules\Components::components( $probe )['consent-settings'] ?? null ) === ( $declared['consent-settings'] ?? false ) );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['consent-settings']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the component by its wrapper',
		\Nino\Features::shortcodes( $probe, 'consent' ) === [ 'consent', 'consent-settings' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/consent-settings' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'consent-settings' ) === [ 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[consent-settings class=""]' ) === $before['[consent-settings]'] );
	check( 'a class of its own comes after the button\'s own, no class leaves no space behind',
		str_contains( \Nino\Html::renderHtml( $probe, '[consent-settings class="footer-link"]' ), '<button type="button" class="nino-consent-open footer-link">' ) === true
		&& str_contains( $before['[consent-settings]'], '<button type="button" class="nino-consent-open">' ) === true );
	check( '[consent] is no component: it is the banner of the page frame, and stays the shortcode it was',
		isset( \Nino\Modules\Components::components( $probe )['consent'] ) === false );

} )( $appData );

echo "\n";


// --- The privacy policy ---------------------------------------------------------------

echo "The privacy policy - the banner's link without a policyUrl, and the button in the policy's own section\n";

$legalClass = '\\Nino\\Modules\\Legal';
$openButton = '<button type="button" class="nino-consent-open">Cookie settings</button>';
// What the listener appends is the template as it is - the fill in it is resolved with the rest of the page
$openTemplate = '<button type="button" class="nino-consent-open">[[/feature/consent/action/open]]</button>';

\Nino\Features::saveSettings( $appData, 'consent', [ 'policyUrl' => '' ] );

// What the listener does with a section, whether or not the module is there to fire it
$section = [ 'type' => 'privacy', 'id' => 'consent', 'html' => '<p>Text</p>' ];
\Nino\Modules\Consent::callbackLegalSection( $appData, $section );
check( 'the button is appended to the privacy policy\'s section "consent", and to nothing else of it', $section === [ 'type' => 'privacy', 'id' => 'consent', 'html' => '<p>Text</p>'. $openTemplate ] );

foreach( [ 'another section' => [ 'privacy', 'forms' ], 'the imprint' => [ 'legal', 'consent' ] ] as $what => $of ) {
	$other = [ 'type' => $of[0], 'id' => $of[1], 'html' => '<p>Text</p>' ];
	\Nino\Modules\Consent::callbackLegalSection( $appData, $other );
	check( $what. ' gets no button', $other['html'] === '<p>Text</p>' );
}

$odd = [ 'type' => 'privacy', 'id' => 'consent' ];
ninoWarnings();
\Nino\Modules\Consent::callbackLegalSection( $appData, $odd );
check( 'a section without a text is left as it is, and nothing is raised', $odd === [ 'type' => 'privacy', 'id' => 'consent' ] && ninoWarnings() === [] );

check( 'with the module not active, an empty policyUrl renders no link either - url() gives nothing', str_contains( \Nino\Html::renderHtml( $appData, '[consent]' ), 'nino-consent-link' ) === false );

// The module active, with its routes, as a project that ran the wizard has it
$appData['/nino/modules'][] = $legalClass;
$appData['/nino/http/routes'] = $appData['/nino/http/routes'] ?? [];
\Nino\Modules\Legal::init( $appData );
ninoWarnings();

$bannerPolicy = \Nino\Html::renderHtml( $appData, '[consent]' );
check( 'with no policyUrl the banner links the privacy policy of the module, in the visitor\'s language', str_contains( $bannerPolicy, '<a href="/privacy" class="nino-consent-link">Privacy policy</a>' ) === true );
$appData['./nino/locales/current'] = 'de_DE';
check( '...which is the German path in German', str_contains( \Nino\Html::renderHtml( $appData, '[consent]' ), '<a href="/datenschutz" class="nino-consent-link">Datenschutzerklärung</a>' ) === true );
$appData['./nino/locales/current'] = 'en_US';

\Nino\Features::saveSettings( $appData, 'consent', [ 'policyUrl' => 'https://example.com/privacy' ] );
$bannerOwn = \Nino\Html::renderHtml( $appData, '[consent]' );
check( 'a policyUrl in the settings wins', str_contains( $bannerOwn, 'href="https://example.com/privacy" class="nino-consent-link"' ) === true && str_contains( $bannerOwn, 'href="/privacy"' ) === false );
\Nino\Features::saveSettings( $appData, 'consent', [ 'policyUrl' => '' ] );

// End to end: the module's own sections and this feature's, drawn by [privacy]
$locales = [ 'de_DE', 'en_US' ];
$seeded = \Nino\Elements::seed( $appData, 'privacy', include $root. '/_nino/Nino/Modules/Legal/install/elements/privacy.php', $locales );
$added = \Nino\Elements::seed( $appData, 'privacy', include dirname( __DIR__ ). '/install/elements/privacy.php', $locales );
check( 'the unit\'s file adds the section to the module\'s type', $seeded === true && $added === true && is_array( \Nino\Elements::getElement( $appData, '/privacy/consent', 'en_US', false ) ) === true );

$page = \Nino\Html::renderHtml( $appData, '[privacy]' );
check( 'the page draws the section with the button at its end - the withdrawal the text speaks of is on the page it speaks on',
	preg_match( '#<section class="nino-legal-section" id="privacy-consent">.*?'. preg_quote( $openButton, '#' ). '</div></section>#s', $page ) === 1 );
check( 'and no other section carries it', substr_count( $page, 'nino-consent-open' ) === 1 );

echo "\n";


// --- The looks for the Design feature -------------------------------------------------

echo "The looks - the files in design/, read by their grammar\n";

/*	Three stylesheets in design/, each a variant of the feature for the Design feature (its manual, under Base, says what a variant is),
	and the block it shows on the specimen. A head of lines "@tag words", the ladders of the knobs on :root and - for the two looks that
	move the banner - the rules of the banner's own classes. Read here by their grammar: which tags, which fields, which names. What a
	head says is never compared	*/
$designDir	= $dir. '/design';
$looks			= [];

foreach( glob( $designDir. '/*.css' ) ?: [] as $file ) {
	$rules = consentRules( (string) file_get_contents( $file ) );
	$looks[ basename( $file, '.css' ) ] = [
		'head' => consentHead( $file ), 'rules' => $rules, 'css' => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) ),
		'root' => array_merge( [], ...array_map( static fn( array $rule ): array => consentDeclarations( $rule['body'] ), array_filter( $rules, static fn( array $rule ): bool => $rule['selector'] === ':root' && $rule['at'] === '' ) ) ),
	];
}

ksort( $looks );

$base				= consentRules( (string) file_get_contents( $dir. '/assets/consent.css' ) );
$baseRoot		= array_merge( [], ...array_map( static fn( array $rule ): array => consentDeclarations( $rule['body'] ), array_filter( $base, static fn( array $rule ): bool => $rule['selector'] === ':where(:root)' ) ) );
$knobTable	= [ 'ground' => [ 'ground', 'colours' ], 'space' => [ 'scale', 'surfaces' ], 'corners' => [ 'scale', 'surfaces' ], 'shadow' => [ 'scale', 'surfaces' ] ];
$steps			= [ 'least', 'less', 'default', 'more', 'most' ];

check( 'the feature brings three looks, a stylesheet each in design/, and the block for the specimen beside them - bar, which is the banner as it stands, is one of them',
	array_keys( $looks ) === [ 'bar', 'box', 'dialog' ] && array_map( 'basename', glob( $designDir. '/*' ) ?: [] ) === [ 'bar.css', 'box.css', 'dialog.css', 'preview.tpl' ] );

$headless = [];

foreach( $looks as $variant => $look )
	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $look['head'][$tag] ?? [] ) !== 1 )
			$headless[] = $variant. ' '. $tag;

foreach( [ '@name', '@description' ] as $tag )
	foreach( array_keys( $looks ) as $variant )
		if( ( $variant !== 'dialog' || $tag !== '@name' ) && ( $looks[$variant]['head'][$tag][0] ?? '' ) === ( $looks[$variant]['head'][$tag. '-de'][0] ?? '' ) )
			$headless[] = $variant. ' '. $tag. ' is not translated';

check( 'each head names and describes its look in both languages, once each: @name, @name-de, @description, @description-de'. ( $headless === [] ? '' : ' - '. implode( ', ', $headless ) ), $headless === [] && $looks !== [] );

$knobProblems = [];

foreach( $looks as $variant => $look ) {
	$declared = [];

	foreach( $look['head']['@knob'] ?? [] as $text ) {
		$fields = (array) preg_split( '/\s+/', $text, 4 );
		preg_match( '/^(.*?)(?:\s+=\s+(\S+))?$/', (string) ( $fields[3] ?? '' ), $label );
		$declared[ (string) $fields[0] ] = [ (string) ( $fields[1] ?? '' ), (string) ( $fields[2] ?? '' ), $label[2] ?? null, trim( (string) ( $label[1] ?? '' ) ) ];
	}

	$german = array_map( static fn( string $text ): string => (string) ( preg_split( '/\s+/', $text, 2 )[0] ), $look['head']['@knob-de'] ?? [] );

	if( array_keys( $declared ) !== array_keys( $knobTable ) || count( $look['head']['@knob'] ?? [] ) !== 4 )
		$knobProblems[] = $variant. ' does not declare the four knobs, once each, in order';

	if( $german !== array_keys( $knobTable ) || array_filter( $look['head']['@knob-de'] ?? [], static fn( string $text ): bool => count( (array) preg_split( '/\s+/', $text, 2 ) ) < 2 ) !== [] )
		$knobProblems[] = $variant. ' has no @knob-de for each knob';

	foreach( $declared as $name => [ $kind, $tab, $default, $words ] )
		if( ( $knobTable[$name] ?? null ) !== [ $kind, $tab ] || $words === '' || ( $kind === 'ground' ) !== ( $default !== null ) )
			$knobProblems[] = $variant. ' @knob '. $name;

	if( ( $declared['ground'][2] ?? '' ) !== 'default' )
		$knobProblems[] = $variant. ' ground does not start at the default surface';
}

check( 'each look declares the four knobs, the same in all three: the ground on Colours, with the surface it stands at, and the space inside, the corners and the shadow on Surfaces as scales - each with its words in both languages'. ( $knobProblems === [] ? '' : ' - '. implode( ', ', $knobProblems ) ),
	$knobProblems === [] && count( $looks ) === 3 );

$ladderProblems = [];

foreach( $looks as $variant => $look ) {
	$rungs = [];

	foreach( $knobTable as $name => [ $kind ] )
		if( $kind === 'scale' )
			foreach( $steps as $step ) {
				$property = '--consent-'. $name. '--'. $step;
				$rungs[] = $property;

				if( isset( $look['root'][$property] ) === false || substr_count( $look['css'], $property. ':' ) !== 1 )
					$ladderProblems[] = $variant. ' lacks '. $property;
			}

	foreach( array_keys( $look['root'] ) as $property )
		if( preg_match( '/--(?:least|less|default|more|most)$/', $property ) === 1 && in_array( $property, $rungs, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs on :root, once each, and a ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ), $ladderProblems === [] && count( $looks ) === 3 );

$ruleProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( $look['rules'] as $rule )
		if( $rule['selector'] !== ':root' && preg_match( '/^\.nino-consent(?:-[a-z]+)*$/', $rule['selector'] ) !== 1 )
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];

	foreach( consentColourLiterals( (string) file_get_contents( $designDir. '/'. $variant. '.css' ) ) as $literalColour )
		$ruleProblems[] = $variant. ' writes a colour as a literal: '. $literalColour;

	if( stripos( $look['css'], '!important' ) !== false )
		$ruleProblems[] = $variant. ' !important';
}

check( 'a look styles the banner\'s own classes and :root, writes no colour as a literal and declares nothing !important'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 3 );

$barProblems = [];

foreach( $looks['bar']['rules'] ?? [] as $rule )
	if( $rule['selector'] !== ':root' )
		$barProblems[] = $rule['selector'];

foreach( [ 'space', 'corners', 'shadow' ] as $name )
	if( ( $looks['bar']['root']['--consent-'. $name. '--default'] ?? null ) !== ( $baseRoot['--consent-'. $name] ?? false ) )
		$barProblems[] = $name;

check( 'Bar declares ladders and nothing else, and the middle of each is the default consent.css declares - so the banner under it looks as the banner without the Design feature'. ( $barProblems === [] ? '' : ' - '. implode( ', ', $barProblems ) ),
	$barProblems === [] && count( $looks['bar']['root'] ?? [] ) === 15 );
check( '...the banner is as square as it has always been: the corners are 0 in consent.css and at the three least positions of Bar, then the first step of the radius and the second',
	( $baseRoot['--consent-corners'] ?? null ) === '0rem' && array_map( static fn( string $step ): string => (string) ( $looks['bar']['root']['--consent-corners--'. $step] ?? '' ), $steps ) === [ '0rem', '0rem', '0rem', 'var(--radius-small)', 'var(--radius-large)' ] );

$boxAndDialog = [];

foreach( [ 'box', 'dialog' ] as $variant ) {
	$bannerRule = array_values( array_filter( $looks[$variant]['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-consent' && $rule['at'] === '' ) );
	$place	= consentDeclarations( $bannerRule[0]['body'] ?? '' );

	if( isset( $place['inset'] ) === false || isset( $place['max-width'] ) === false || ( $place['border-radius'] ?? '' ) !== 'var(--consent-corners)' )
		$boxAndDialog[] = $variant;
}

check( 'Box and Dialog replace the layout on the banner\'s class - where it stands, how wide it is at the most, all four corners the knob\'s - and the box is the bar again on a narrow screen',
	$boxAndDialog === [] && array_filter( $looks['box']['rules'], static fn( array $rule ): bool => $rule['at'] === '@media (max-width: 640px)' && $rule['selector'] === '.nino-consent' ) !== []
	&& ( consentDeclarations( array_values( array_filter( $looks['dialog']['rules'], static fn( array $rule ): bool => $rule['selector'] === '.nino-consent' ) )[0]['body'] ?? '' )['box-shadow'] ?? '' ) !== ''
	&& str_contains( (string) ( consentDeclarations( array_values( array_filter( $looks['dialog']['rules'], static fn( array $rule ): bool => $rule['selector'] === '.nino-consent' ) )[0]['body'] ?? '' )['box-shadow'] ?? '' ), 'var(--color-background-dim)' ) === true );

/*	A look beats consent.css by being in :where() there, not by coming after it: the bundle lists consent.css behind the sheet of the Design
	feature, so a property that a look and a plain rule of consent.css both declare on the same selector is the plain rule's	*/
$overlaps = [];
$baseDeclared = [];

foreach( $base as $rule )
	if( str_contains( $rule['selector'], ':where(' ) === false )
		foreach( array_keys( consentDeclarations( $rule['body'] ) ) as $property )
			$baseDeclared[ $rule['selector'] ][$property] = true;

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule )
		foreach( array_keys( consentDeclarations( $rule['body'] ) ) as $property )
			if( isset( $baseDeclared[ $rule['selector'] ][$property] ) === true )
				$overlaps[] = $variant. ' '. $rule['selector']. ' '. $property;

check( 'no look declares a property on a selector that consent.css declares it on in a plain rule, which would win over the look whatever the look says'. ( $overlaps === [] ? '' : ' - '. implode( ', ', $overlaps ) ),
	$overlaps === [] && isset( $baseDeclared['.nino-consent']['position'] ) === true && count( $looks ) === 3 );

// The look that holds the page: --consent-modal is the one property consent.js reads, 0 in consent.css and 1 in Dialog alone
check( 'Dialog declares itself modal, --consent-modal: 1 on :root, which consent.js reads, and consent.css declares 0 for every other look - Bar and Box do not set it',
	( $looks['dialog']['root']['--consent-modal'] ?? null ) === '1' && ( $baseRoot['--consent-modal'] ?? null ) === '0'
	&& isset( $looks['bar']['root']['--consent-modal'] ) === false && isset( $looks['box']['root']['--consent-modal'] ) === false );

// consent.css: the defaults of the knobs, the layout in :where(), the buttons without a look of their own
$baseProblems = [];

foreach( [ '--consent-ground', '--consent-ground-on', '--consent-ground-link', '--consent-ground-border', '--consent-ground-focus', '--consent-ground-shadow', '--consent-space', '--consent-corners', '--consent-shadow' ] as $property )
	if( isset( $baseRoot[$property] ) === false )
		$baseProblems[] = $property;

$baseCss = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/consent.css' ) );

foreach( consentColourLiterals( $baseCss ) as $literalColour )
	$baseProblems[] = 'a literal colour: '. $literalColour;

if( preg_match( '/:root\s*\{/', (string) preg_replace( '/:where\(:root\)/', '', $baseCss ) ) === 1 || stripos( $baseCss, '!important' ) !== false )
	$baseProblems[] = 'a default outside :where(:root), or !important';

check( 'consent.css declares the default of each property the knobs move - the ground and its parts, the space, the corners and the shadow - in :where(:root), writes no colour as a literal'. ( $baseProblems === [] ? '' : ' - '. implode( ', ', $baseProblems ) ), $baseProblems === [] );

$layout = array_values( array_filter( $base, static fn( array $rule ): bool => $rule['selector'] === ':where(.nino-consent)' && $rule['at'] === '' ) );
check( 'the layout of the bar stands in :where(.nino-consent), which has no specificity, so a rule of a look beats it whatever the order of the sheets - and the rule of the banner itself keeps what no look replaces',
	count( $layout ) === 1 && array_keys( consentDeclarations( $layout[0]['body'] ) ) === [ 'inset', 'flex-flow', 'align-items', 'justify-content', 'border-width', 'border-radius', 'box-shadow' ]
	&& array_filter( $base, static fn( array $rule ): bool => $rule['selector'] === '.nino-consent' && $rule['at'] === '' && array_intersect( array_keys( consentDeclarations( $rule['body'] ) ), [ 'inset', 'left', 'right', 'bottom', 'width', 'max-width', 'margin', 'border-width', 'border-radius', 'box-shadow' ] ) !== [] ) === [] );

$staticRule = array_values( array_filter( $base, static fn( array $rule ): bool => $rule['selector'] === '.nino-consent.nino-consent--static' ) );
check( 'consent.css carries nino-consent--static for the preview: two classes, so it beats the rules of the looks, and the position is static',
	count( $staticRule ) === 1 && ( consentDeclarations( $staticRule[0]['body'] )['position'] ?? '' ) === 'static' );

$buttonLook = [];

foreach( $base as $rule )
	if( str_contains( $rule['selector'], '.nino-consent-btn' ) === true )
		foreach( array_keys( consentDeclarations( $rule['body'] ) ) as $property )
			if( in_array( $property, [ 'white-space', 'flex' ], true ) === false )
				$buttonLook[] = $property;

check( 'consent.css draws no look of the buttons - they are the kernel\'s, and it keeps for the hook only that a label is not broken and, on a narrow screen, that the buttons share the width'. ( $buttonLook === [] ? '' : ' - '. implode( ', ', $buttonLook ) ),
	$buttonLook === [] && array_filter( $base, static fn( array $rule ): bool => str_contains( $rule['selector'], '.nino-consent-btn' ) === true ) !== [] );

// The block for the specimen: the banner in the flow, finished
$block			= (string) file_get_contents( $designDir. '/preview.tpl' );
$blockIds		= [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

$liveClasses = [];
preg_match_all( '/class="([^"\[]*)/', (string) file_get_contents( $dir. '/templates/consent-banner.tpl' ). (string) file_get_contents( $dir. '/templates/consent-category.tpl' ), $liveFound );

foreach( $liveFound[1] as $classes )
	foreach( preg_split( '/\s+/', trim( $classes ) ) ?: [] as $class )
		if( $class !== '' )
			$liveClasses[$class] = true;

$blockClasses = [];
preg_match_all( '/class="([^"]*)"/', $block, $blockFound );

foreach( $blockFound[1] as $classes )
	foreach( preg_split( '/\s+/', trim( $classes ) ) ?: [] as $class )
		$blockClasses[$class] = true;

$blockMissing = array_filter( array_keys( $liveClasses ), static fn( string $class ): bool => isset( $blockClasses[$class] ) === false );

check( 'the block for the specimen, design/preview.tpl: one grid row, every id in it consent-something and none twice, no section of its own, and no inline handler',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $block ), '</div>' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'consent-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1 );
check( '...it is the banner finished and in the flow - not hidden, nino-consent--static on it, the three kernel buttons - with every class of the banner\'s templates in it, and its words the feature\'s own text keys, so that the specimen speaks as the page does'. ( $blockMissing === [] ? '' : ' - missing '. implode( ', ', $blockMissing ) ),
	$blockMissing === [] && preg_match( '/<div class="nino-consent nino-consent--static"/', $block ) === 1 && preg_match( '/\shidden\b/', $block ) !== 1 && substr_count( $block, 'class="nino-btn nino-btn--outline nino-consent-btn"' ) === 3
	&& str_contains( $block, '[[/feature/consent/banner/title]]' ) === true && str_contains( $block, '[[/feature/consent/action/accept-all]]' ) === true && str_contains( $block, 'data-consent' ) === false );

// The manual: one entry for the looks and the knobs, in both languages
$lookManual = (array) ( $manifest['manual']['panel'] ?? [] );
$lookProblems = [];

$lookManual['markup / Custom properties'] = $manifest['manual']['markup']['Custom properties'] ?? [];

foreach( $lookManual as $title => $lines )
	foreach( [ 'en_US', 'de_DE' ] as $locale )
		if( is_string( $lines[$locale] ?? null ) === false || trim( $lines[$locale] ) === '' || strlen( $lines[$locale] ) >= 1000 )
			$lookProblems[] = $title. ' / '. $locale;

check( 'the manual tells of the looks and the knobs of the Design feature under panel and of the custom properties under markup, in both languages, every line under 1000 bytes and the German one without a du of lower case'. ( $lookProblems === [] ? '' : ' - '. implode( ', ', $lookProblems ) ),
	count( $lookManual ) === 2 && $lookProblems === [] && preg_match( '/\bdu\b|\bdein/', implode( ' ', array_column( $lookManual, 'de_DE' ) ) ) !== 1);

echo "\n";


// --- The looks, run through the Design feature --------------------------------------------

echo "The looks - as the Design feature scans and compiles them\n";

$designSetup		= '\\Nino\\Modules\\Design\\Setup';
$designPreview	= '\\Nino\\Modules\\Design\\Preview';
$designCompiler	= '\\Nino\\Modules\\Design\\Compiler';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the looks are not run through it here, bin/check.sh runs them with it in the checkout\n";
else {
	$library			= \Nino\Features::dir(). '/Design/library';
	$modulesKept	= (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept		= $appData['./nino/locales/current'];

	/** Which of the two features are on: their classes are listed in /nino/modules, and what the kernel and the Design feature have read of the features is read again */
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Consent', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Consent', 'Design' ] );

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['consent'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its three looks - bar the one it has before anything is chosen - and has nothing to say of any of them',
		isset( $scan['consent'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === [ 'bar', 'box', 'dialog' ] && $feature['default'] === 'bar' && $scanNotes === [] );

	$appData['./nino/locales/current'] = 'en_US';
	$english = $designSetup::features( $appData )['consent']['catalogue'] ?? [];
	$appData['./nino/locales/current'] = 'de_DE';
	$german = $designSetup::features( $appData )['consent']['catalogue'] ?? [];

	check( '...each described in the language of the workbench by the lines of its head, the name and the description - the English ones or the ones with -de',
		count( $english ) === 3 && count( $german ) === 3 && array_filter( array_keys( $looks ), static fn( string $variant ): bool => ( $english[$variant]['name'] ?? null ) !== $looks[$variant]['head']['@name'][0] || ( $english[$variant]['description'] ?? null ) !== $looks[$variant]['head']['@description'][0]
			|| ( $german[$variant]['name'] ?? null ) !== $looks[$variant]['head']['@name-de'][0] || ( $german[$variant]['description'] ?? null ) !== $looks[$variant]['head']['@description-de'][0] ) === [] );

	$offTable = [];

	foreach( $catalogue as $variant => $look ) {
		if( array_keys( $look['knobs'] ) !== array_keys( $knobTable ) )
			$offTable[] = $variant. ' answers to '. implode( ', ', array_keys( $look['knobs'] ) );

		foreach( $look['knobs'] as $name => $knob )
			if( ( $knobTable[$name] ?? null ) !== [ $knob['kind'], $knob['tab'] ] || ( $knob['kind'] === 'scale' && ( $knob['ladder'] ?? [] ) !== [ $name ] ) )
				$offTable[] = $variant. ' '. $name;
	}

	check( 'every look answers to the four knobs with the kind and the tab its head names, and each scale is published by its ladder'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ), $offTable === [] && $catalogue !== [] );

	// The block is the feature's, not a look's: the scan names its anchor once, with the feature, and Design makes the section round it
	$blockTold	= [];
	$inside			= $designSetup::previewOf( (string) $feature['dir'], 'consent', $blockTold );
	$blocks			= $designPreview::blocks( $appData );

	check( 'the block stands the check Design holds it to and the scan publishes its anchor, feature-consent, with the feature and with none of its looks - and the preview makes the section of it, named like the feature in the language of the workbench',
		$inside === trim( $block ) && $inside !== '' && $blockTold === [] && ( $feature['anchor'] ?? null ) === 'feature-consent' && array_keys( $blocks ) === [ 'consent' ]
		&& preg_match( '~^<section id="feature-consent" class="nino-section" aria-label="Cookie-Einwilligung">\s*(.*?)\s*</section>$~s', $blocks['consent'], $section ) === 1 && str_contains( $section[1] ?? '', 'id="consent-banner"' ) === true );

	// The sheet: the section of the look after the seven parts, the line of every ladder, the ground with its parts
	$featuresOn( [ 'Consent', 'Design' ] );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'consent' => [ 'set' => $variant ] ] ], $sheetNotes );

		foreach( [ 'space', 'corners', 'shadow' ] as $ladder )
			if( str_contains( $sheet, "\n\t--consent-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'consent', $ladder, 'default' ). ");\n" ) === false )
				$sheetProblems[] = $variant. ' selects no '. $ladder;

		foreach( \Nino\Modules\Design\Colours::parts() as $part )
			if( str_contains( $sheet, "\n\t--consent-ground". ( $part === 'bg' ? '' : '-'. $part ). ': var('. \Nino\Modules\Design\Colours::token( 'default', $part ). ");\n" ) === false )
				$sheetProblems[] = $variant. ' writes no ground part '. $part;

		if( preg_match( '~^/\* ==== \d+\. feature consent: '. preg_quote( $variant, '~' ). ' \(space default, corners default, shadow default\) ==== \*/$~m', $sheet ) !== 1 )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $dir. '/design/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] )
			$sheetProblems[] = $variant. ' compiled with a note';
	}

	check( 'with a look chosen the sheet carries its section, its stylesheet as the file has it, a line for the ladder of every scale and every part of the ground the palette solved'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ), $sheetProblems === [] && $catalogue !== [] );

	$selects	= static fn( string $sheet, string $ladder, string $step ): bool => str_contains( $sheet, "\n\t--consent-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'consent', $ladder, $step ). ");\n" );
	$moved		= $compile( [ 'features' => [ 'consent' => [ 'set' => 'box', 'knobs' => [ 'space' => -2, 'corners' => 2, 'shadow' => 1, 'ground' => 'dark' ] ] ] ] );

	check( 'a position moved reaches the sheet as the rung it stands for, and a ground chosen as the palette\'s token of that surface and of every part of it',
		$selects( $moved, 'space', 'least' ) && $selects( $moved, 'corners', 'most' ) && $selects( $moved, 'shadow', 'more' )
		&& str_contains( $moved, "\n\t--consent-ground: var(". \Nino\Modules\Design\Colours::token( 'dark', 'bg' ). ");\n" ) === true && str_contains( $moved, "\n\t--consent-ground-on: var(". \Nino\Modules\Design\Colours::token( 'dark', 'on' ). ");\n" ) === true );

	// The bundle of a project the wizard has set up lists the sheet of the feature after the theme the Design feature writes
	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ $designCompiler::TARGET, '/assets/style.css' ] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];

	check( 'in a booted project the bundle lists consent.css after the theme.css the Design feature writes its sheet to - the order the :where() of its defaults and of its layout is for',
		in_array( '/features/Consent/assets/consent.css', $bundle, true ) === true && array_search( '/features/Consent/assets/consent.css', $bundle, true ) > array_search( $designCompiler::TARGET, $bundle, true ) );

	$appData['/nino/modules']					= $modulesKept;
	$appData['./nino/locales/current']	= $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
}

echo "\n";


// --- The browser half ----------------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so consent-js-smoke.js is not run here\n\n";
else {
	echo "consent-js-smoke.js\n";
	// Over the same checkout: the script is started, and reads its banner, through the kernel's own Nino.js and Nino.ui.js
	$out = (string) @shell_exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/consent-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation -----------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'consent' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Consent', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the settings survive deactivation', \Nino\Features::setting( $appData, 'consent', 'marketing', false ) === true );
check( 'the merged texts survive deactivation', \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/consent/action/accept-all]]'] === 'Accept all' );

ninoDone( $appData );
