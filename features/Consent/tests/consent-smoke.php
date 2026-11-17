<?php
declare(strict_types=1);

// The test of the feature Consent: structure and behaviour, one pin each. php features/Consent/tests/consent-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form', is_array( $manifest ) === true && $manifest['key'] === 'consent' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && isset( $manifest['stacks'] ) === false );
check( 'the six settings: every optional category off, the cookie name and the lifetime bounded, no data of its own', array_keys( $manifest['settings'] ) === [ 'statistics', 'marketing', 'external', 'policyUrl', 'cookieName', 'days' ]
	&& array_filter( [ 'statistics', 'marketing', 'external' ], static fn( string $key ): bool => $manifest['settings'][$key]['default'] !== false ) === []
	&& $manifest['settings']['cookieName']['pattern'] === '/^[A-Za-z0-9_-]+$/D' && $manifest['settings']['days']['min'] === 1 && $manifest['settings']['days']['max'] === 365 && $manifest['data'] === [] );
check( 'the script is in the bundle and the look is the part: no stylesheet among the assets', $manifest['assets'] === [ '/features/Consent/assets/consent.js' ] );

/**
 *	A project with Basis and the feature switched on, the register read as \Nino\init() reads it, and the settings saved before anything is drawn:
 *	the register keeps what a source drew for the length of a request, so a setting is changed in a project of its own
 *
 *	@param		string		$name					The sandbox
 *	@param		array			$settings			What the settings panel would save
 *	@param		array			$fills				Words of the project: key => text
 *
 *	@return 	array										The project's app data
 */
function consentSite( string $name, array $settings = [], array $fills = [] ): array {

	global $root;

	$appData = ninoSandbox( $name );
	$appData['./nino/filesystem/path'] = $root;
	check( 'Basis and the feature are switched on in '. $name, \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'consent' ) === true
		&& ( $settings === [] || \Nino\Features::saveSettings( $appData, 'consent', $settings ) === [] ) );
	$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );
	\Nino\Html::addFills( $appData, $fills, '*' );
	ninoWarnings();

	return $appData;
}

$appData = consentSite( 'consent' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'consent' ) === [] );
$schemas = \Nino\Components::components( $appData );
check( 'both components are in their group, labelled in both languages, with an icon of the table and every attribute labelled', array_filter( [ 'consent', 'consent-settings' ], static fn( string $name ): bool => ( $schemas[$name]['group'] ?? '' ) !== 'consent'
	|| isset( $schemas[$name]['label']['de_DE'], $schemas[$name]['label']['en_US'] ) === false || in_array( $schemas[$name]['icon'] ?? '', \Nino\Components::ICONS, true ) === false
	|| array_filter( $schemas[$name]['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) !== [] ) === []
	&& ( \Nino\Components::groups( $appData )['consent']['part'] ?? false ) === true );
$variants = \Nino\Components::variants( $appData, 'consent' );
check( 'the banner has the bar as its standard and the box and the dialog as variants', $variants['standard'] === 'regular' && array_keys( $variants['variants'] ) === [ 'regular', 'box', 'dialog' ] );
check( 'a tool offers the button in a column and in the footer, and offers the banner nowhere (the output phase draws it)', in_array( 'consent-settings', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& in_array( 'consent-settings', \Nino\Components::allowed( $appData, 'footer-col' ), true ) === true && array_filter( [ '', 'page', 'section-col', 'footer-col', 'header-actions', 'section-row' ], static fn( string $place ): bool => in_array( 'consent', \Nino\Components::allowed( $appData, $place ), true ) === true ) === [] );
$part = (string) file_get_contents( $dir. '/groups/consent/part/part.css' );
check( 'the standard part keeps the rule of mixing, sets the modal token only for the dialog and is in the style bundle', \Nino\Components::partProblems( $part, [ 'consent', 'consent-settings' ] ) === []
	&& preg_match( '/\.consent--variant-dialog\s*\{[^}]*--consent-modal:\s*1/', $part ) === 1 && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.consent-button' ) === true );

// The banner: what the settings switch on is in it, what they do not is neither shown nor storable
$banner = $draw( '[consent]' );
check( 'the banner is hidden until the script shows it, is a region with a name and a description, carries the cookie and the lifetime and has the three actions in order', preg_match( '/^<div class="consent" hidden role="region" aria-labelledby="consent-title" aria-describedby="consent-text" tabindex="-1" data-consent-cookie="nino_consent" data-consent-days="180">/', $banner ) === 1
	&& preg_match( '/data-consent-action="accept-all".*data-consent-action="necessary-only".*data-consent-action="save"/s', $banner ) === 1 );
check( 'the three actions look alike: accepting everything is no easier to press than refusing it', substr_count( $banner, 'class="consent-button" data-consent-action' ) === 3 && str_contains( $banner, 'consent-button--' ) === false );
check( 'necessary is checked and disabled, and with every optional category off none of theirs is drawn', str_contains( $banner, 'data-consent-category="necessary" checked disabled' ) === true
	&& preg_match( '/data-consent-category="(statistics|marketing|external)"/', $banner ) === 0 && str_contains( $banner, 'consent-link' ) === false );
check( 'a text of the banner is the text of its key, in the language of the page', str_contains( $draw( '[consent]' ), '>[[' ) === false && str_contains( $banner, 'consent-title' ) === true );

// A project of its own that offers two categories, names the policy, the cookie and the lifetime, and has words that spell a tag or a call
$configured = consentSite( 'consent-configured', [ 'statistics' => 'true', 'marketing' => 'true', 'policyUrl' => 'https://example.com/privacy?a=1&b=2', 'days' => '90', 'cookieName' => 'acme_consent' ],
	[ '/feature/consent/banner/title' => 'Cookie notice <b>', '/feature/consent/category-statistics/name' => 'Statistics <i>x</i> [csrf]', '/feature/consent/banner/link' => 'Privacy' ] );
$banner = trim( \Nino\Html::renderSource( $configured, '[consent]', \Nino\Html::context( $configured ) ) );
check( 'the offered categories have a box each, the others none, in the order of the banner', preg_match( '/category="necessary".*category="statistics".*category="marketing"/s', $banner ) === 1 && str_contains( $banner, 'data-consent-category="external"' ) === false
	&& str_contains( $banner, 'data-consent-category="statistics" checked' ) === false );
check( 'the cookie, the lifetime and the policy address are escaped into the banner, once', str_contains( $banner, 'data-consent-cookie="acme_consent" data-consent-days="90"' ) === true
	&& str_contains( $banner, '<a href="https://example.com/privacy?a=1&amp;b=2" class="consent-link">Privacy</a>' ) === true );
check( 'a word that spells a tag or a call is text: nothing of it is a tag, a bracket is an entity', str_contains( $banner, '<b>' ) === false && str_contains( $banner, '<i>' ) === false && str_contains( $banner, 'Cookie notice &lt;b&gt;' ) === true && str_contains( $banner, 'Statistics &lt;i&gt;x&lt;/i&gt; &#91;csrf&#93;' ) === true );
$configuredPage = static function() use ( &$configured ): string {
	$request = [ '/nino/http/request' => [ 'uri' => '/' ], '/nino/http/response' => [ 'uri' => '/', 'body' => '<html><body></body></html>', 'header' => [ 'Content-Type' => 'text/html' ] ] ];
	\Nino\Callbacks::doCallbacks( $configured, '/nino/http/output', $request );
	return $request['/nino/http/response']['body'];
};
check( 'the root element lists the offered categories in the order of the banner and not the one that is off', str_contains( $configuredPage(), '<html data-consent-offered="necessary,statistics,marketing">' ) === true );
check( 'a banner in the flow of a preview is shown and says so', str_contains( $draw( '[consent inline]' ), ' hidden' ) === false && str_contains( $draw( '[consent inline]' ), 'class="consent consent--inline"' ) === true );
check( 'the bar and the box are a region with a name and leave the page as it is; the dialog is a dialog', str_contains( $draw( '[consent variant="box"]' ), 'role="region" aria-labelledby="consent-title"' ) === true
	&& str_contains( $draw( '[consent variant="dialog"]' ), 'role="dialog" aria-labelledby="consent-title"' ) === true && str_contains( $draw( '[consent inline]' ), 'role="region"' ) === true );
check( 'a variant is a class of the banner', str_contains( $draw( '[consent variant="dialog"]' ), 'class="consent consent--variant-dialog"' ) === true && str_contains( $draw( '[consent variant="nonsense"]' ), 'class="consent"' ) === true );
check( 'the button that opens the choice is a button with its word - a filled one, never the key', preg_match( '/^<button type="button" class="consent-settings">[^<\[]+<\/button>$/', $draw( '[consent-settings]' ) ) === 1 );

// The output phase draws the banner: once, into the pages of the site and nowhere else
$page = static fn( string $body, string $uri = '/', string $type = 'text/html; charset=UTF-8' ): array => [ '/nino/http/request' => [ 'uri' => $uri ], '/nino/http/response' => [ 'uri' => $uri, 'body' => $body, 'header' => [ 'Content-Type' => $type ] ] ];
$run = static function( array $request ) use ( &$appData ): array {
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );
	return $request;
};
$bodyOf = static fn( array $request ): string => $request['/nino/http/response']['body'];
$out = $bodyOf( $run( $page( '<html><head><title>x</title></head><body class="a"><main>x</main></body></html>' ) ) );
check( 'a page of the site gets the banner once, right after the start tag of its body: it is the first stop of the Tab key and of a screen reader', substr_count( $out, 'class="consent"' ) === 1
	&& str_contains( $out, '<body class="a">'. "\n". '<div class="consent"' ) === true && strpos( $out, 'class="consent"' ) < strpos( $out, '<main>' ) );
$tricky = '<html><head><script>var a = "<body><div>"; var b = "</body>";</script><!-- <body> --></head><body data-x=">"><p>"</body>"</p></body></html>';
$out = $bodyOf( $run( $page( $tricky ) ) );
check( 'where <body and </body> stand in a script, a comment or a text, the banner is put after the start tag of the real body and nowhere else', substr_count( $out, 'class="consent"' ) === 1 && str_contains( $out, '<body data-x=">">'. "\n". '<div class="consent"' ) === true
	&& str_contains( $out, 'var a = "<body><div>"; var b = "</body>";' ) === true );
$closed = '<body>x</body>';
check( 'the workbench, a response that is no page, a page with no body and a body that is none get no banner',
	$bodyOf( $run( $page( $closed, '/_admin/' ) ) ) === $closed && $bodyOf( $run( $page( $closed, '/_admin' ) ) ) === $closed
	&& $bodyOf( $run( $page( '<body>', '/x', 'application/json' ) ) ) === '<body>' && $bodyOf( $run( $page( '<p>x</p>' ) ) ) === '<p>x</p>' && $bodyOf( $run( $page( '' ) ) ) === '' );
check( 'a page that merely starts with the same letters as the workbench is a page of the site', str_contains( $bodyOf( $run( $page( $closed, '/_adminx' ) ) ), 'class="consent"' ) === true );
$appData['./nino/preview/held'] = [ 'kind' => 'builder', 'token' => 'probe' ];
check( 'a draft that is previewed gets no banner, but names the offered categories like every page', $bodyOf( $run( $page( $closed ) ) ) === $closed
	&& str_contains( $bodyOf( $run( $page( '<html><body>x</body></html>' ) ) ), '<html data-consent-offered="necessary">' ) === true );
unset( $appData['./nino/preview/held'] );

// The categories a site offers are on the root element of its pages: the script gives out nothing else, banner or not
$doctype = "<!-- nino:template-name Home -->\n<!doctype html>\n<html lang=\"en\"><head></head><body>x</body></html>";
$tagged = $bodyOf( $run( $page( $doctype ) ) );
check( 'the root element of a page names the categories the site offers: only necessary where the settings switched none on, one tag and no other', str_contains( $tagged, '<html data-consent-offered="necessary" lang="en">' ) === true && substr_count( $tagged, 'data-consent-offered' ) === 1 );
check( 'the attribute is written into the start tag of <html> and into no text that spells one, nor into a page of the workbench or of another type',
	str_contains( $bodyOf( $run( $page( '<head><script>"<html>"</script></head><body>x</body>' ) ) ), 'data-consent-offered' ) === false && $bodyOf( $run( $page( $doctype, '/_admin/' ) ) ) === $doctype && $bodyOf( $run( $page( '<html></html>', '/x', 'text/plain' ) ) ) === '<html></html>' );

// The Content-Security-Policy: the hosts of the placeholders of a category the site offers, https and a plain host - security, held and made red
$statsSite = consentSite( 'consent-statistics', [ 'statistics' => 'true' ] );
$policy = static function( mixed $body, string $csp, string $uri = '/' ) use ( $page, &$statsSite ): string {
	$request = $page( '' , $uri );
	$request['/nino/http/response']['body'] = $body;
	$request['/nino/http/response']['header']['Content-Security-Policy'] = $csp;
	\Nino\Callbacks::doCallbacks( $statsSite, '/nino/http/output', $request );
	return (string) $request['/nino/http/response']['header']['Content-Security-Policy'];
};
$jstext = "default-src 'self'; script-src 'self' 'nonce-x'; img-src * data:";
$stats = '<script type="text/plain" data-consent="statistics" data-src="https://stats.example/tag.js"></script>';
check( 'a placeholder of an offered category adds its host to script-src, in place', $policy( '<p>x</p>'. $stats, $jstext ) === "default-src 'self'; script-src 'self' 'nonce-x' https://stats.example; img-src * data:" );
check( 'where the policy has no script-src one is built from default-src, whole', $policy( $stats, "default-src 'self' https://cdn.example; img-src *" ) === "default-src 'self' https://cdn.example; img-src *; script-src 'self' https://cdn.example https://stats.example" );
check( 'a category the settings switched off or nobody knows adds nothing: markup cannot open the policy for one nobody offers', $policy( '<script type="text/plain" data-consent="marketing" data-src="https://ads.example/a.js"></script>', $jstext ) === $jstext
	&& $policy( '<script type="text/plain" data-consent="tracking" data-src="https://ads.example/a.js"></script>', $jstext ) === $jstext );
check( 'a placeholder in a comment adds nothing: the browser draws none', $policy( '<!-- '. $stats. ' -->', $jstext ) === $jstext && $policy( '<!--x-->'. $stats, $jstext ) !== $jstext );
check( 'a host that is not a plain https name adds nothing: http, a relative address, credentials, an ip address, a script of no placeholder', array_filter( [
	'<script type="text/plain" data-consent="statistics" data-src="http://stats.example/a.js"></script>',
	'<script type="text/plain" data-consent="statistics" data-src="//stats.example/a.js"></script>',
	'<script type="text/plain" data-consent="statistics" data-src="/local.js"></script>',
	'<script type="text/plain" data-consent="statistics" data-src="https://user:pw@stats.example/a.js"></script>',
	'<script type="text/plain" data-consent="statistics" data-src="https://127.0.0.1/a.js"></script>',
	'<script type="text/plain" data-consent="statistics" data-src="https://0x7f.1/a.js"></script>',
	'<script data-consent="statistics" data-src="https://stats.example/a.js"></script>',
], static fn( string $markup ): bool => $policy( $markup, $jstext ) !== $jstext ) === [] );
check( 'the workbench keeps its policy, and a body that is no string or a response with no policy is left as it is', $policy( $stats, $jstext, '/_admin/x' ) === $jstext && $policy( [ 'a' => 1 ], $jstext ) === $jstext && $policy( $stats, '' ) === '' );

// What the visitor chose: only what the site offers, and never from a cookie that is not a string
check( 'necessary is always allowed, an optional category only where the site offers it and the cookie lists it, an unknown one never', ( static function() use ( &$statsSite ): bool {
	$_COOKIE = [];
	$none = \Nino\Modules\Consent::allowed( $statsSite, 'necessary' ) === true && \Nino\Modules\Consent::allowed( $statsSite, 'statistics' ) === false;
	$_COOKIE = [ 'nino_consent' => 'necessary,statistics,marketing,tracking' ];
	$listed = \Nino\Modules\Consent::allowed( $statsSite, 'statistics' ) === true && \Nino\Modules\Consent::allowed( $statsSite, 'marketing' ) === false && \Nino\Modules\Consent::allowed( $statsSite, 'tracking' ) === false;
	$_COOKIE = [];
	return $none && $listed;
} )() );
check( 'a cookie that is an array allows nothing and raises nothing', ( static function() use ( &$statsSite ): bool {
	$_COOKIE = [ 'nino_consent' => [ 'statistics' ] ];
	$ignored = ninoWarnings();
	$allowed = \Nino\Modules\Consent::allowed( $statsSite, 'statistics' );
	$_COOKIE = [];
	return $allowed === false && ninoWarnings() === [];
} )() );
check( 'the cookie is the one the settings name', ( static function() use ( &$configured ): bool {
	$_COOKIE = [ 'nino_consent' => 'necessary,statistics' ];
	$old = \Nino\Modules\Consent::allowed( $configured, 'statistics' );
	$_COOKIE = [ 'acme_consent' => 'necessary,statistics' ];
	$new = \Nino\Modules\Consent::allowed( $configured, 'statistics' );
	$_COOKIE = [];
	return $old === false && $new === true;
} )() );

// The privacy policy: the section brings the button, only its own
$section = [ 'type' => 'privacy', 'id' => 'consent', 'html' => '<p>x</p>' ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/legal/section', $section );
$other = [ 'type' => 'privacy', 'id' => 'cookies', 'html' => '<p>y</p>' ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/legal/section', $other );
$legal = [ 'type' => 'legal', 'id' => 'consent', 'html' => '<p>z</p>' ];
\Nino\Callbacks::doCallbacks( $appData, '/nino/legal/section', $legal );
check( 'the section on consent ends with the button, as finished html; no other section does', preg_match( '/^<p>x<\/p><button type="button" class="consent-settings">[^<]+<\/button>\s*$/', $section['html'] ) === 1 && $other['html'] === '<p>y</p>' && $legal['html'] === '<p>z</p>' );
$unit = (array) include $dir. '/install/manifest.php';
$privacy = (array) include $dir. '/install/elements/privacy.php';
check( 'the unit brings the section of the privacy policy in both languages, under the id the callback reads', ( $unit['elements']['privacy'] ?? '' ) === 'elements/privacy.php'
	&& isset( $privacy['*']['consent'], $privacy['de_DE']['consent']['text'], $privacy['en_US']['consent']['text'] ) === true );

// The block and the demo
$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the banner in the flow and the button', ( $groups['consent']['demo'] ?? '' ) !== '' && str_contains( $draw( $groups['consent']['demo'] ), 'consent--inline' ) === true && str_contains( $draw( $groups['consent']['demo'] ), 'class="consent-settings"' ) === true );
$block = \Nino\Blocks::get( $appData, 'consent-note' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'consent', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );

// Words: the same keys in both languages
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
check( 'both languages bring the same words, and every word the templates and the renderer read is among them', $words['de_DE'] === $words['en_US'] && ( static function() use ( $dir, $words ): bool {
	$read = [];
	foreach( [ '/components/consent/template.tpl', '/components/consent-settings/template.tpl' ] as $file )
		if( preg_match_all( '/\[\[(\/feature\/consent\/[a-z-]+\/[a-z-]+)\]\]/', (string) file_get_contents( $dir. $file ), $found ) > 0 )
			$read = array_merge( $read, $found[1] );
	foreach( [ 'necessary', 'statistics', 'marketing', 'external' ] as $category )
		array_push( $read, '/feature/consent/category-'. $category. '/name', '/feature/consent/category-'. $category. '/hint' );
	$read[] = '/feature/consent/banner/link';
	return count( $read ) > 10 && array_diff( array_map( static fn( string $key ): string => '[['. $key. ']]', array_unique( $read ) ), $words['en_US'] ) === [];
} )() );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'consent-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[consent][consent-settings]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-consent|nino-btn|Nino\.ui|--color-|--space-[0-9]|--radius-|--fontsize-|data-nino-mode/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/design' ) === false && is_dir( $dir. '/templates' ) === false );

// The script runs over the kernel's stand-in of a page (consent-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: consent-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/consent-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'consent-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
