<?php
declare(strict_types=1);

/**
 *	Nino
 *	seo-smoke.php		Contract test for the Seo feature (\Nino\Modules\Seo): the
 *									manifest and activation through \Nino\Features, a small
 *									fixture of persisted routes (a de_DE/en_US pair, a plain
 *									page whose uri carries a "&", an excluded page, a
 *									/.internal endpoint, a /_admin/x tool uri), sitemap.xml
 *									(well-formed, exactly the site pages as absolute urls,
 *									hreflang alternates for the paired page, "&" escaped, a
 *									lastmod from a template's mtime and none where there is no
 *									template), robots.txt (the fixed lines, the settings, the
 *									sitemap and llms.txt mentions, in order), llms.txt
 *									(heading, description, titled pages grouped by locale,
 *									and a 404 - with no mention in robots.txt - once 'agents'
 *									is off), [seo-alternates] and [seo-jsonld], and
 *									deactivation. Travels with the feature and runs against
 *									the checkout three levels up, or the one NINO_ROOT names
 *									(see tests/harness.php there).
 *
 *	Usage: php features/Seo/tests/seo-smoke.php
 *	       NINO_ROOT=../nino php features/Seo/tests/seo-smoke.php
 */

// Before the kernel loads: the autoloader and Features::dir() read the same
// constant, so the directory this feature lives in is the one searched -
// wherever NINO_ROOT points
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'seo' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Needed for a fill to resolve at all - a bare ninoSandbox() never sets this
// (AppData::init() would; nothing here calls it), and without it neither
// \Nino\Html::getFills() nor this feature's own _fillsForLocale() would ever
// find /text/*.php - same reasoning as ProtectedArea's own test
$appData['/nino/locales/textfiles'] = '/text';

/**
 *	Build a request the way \Nino\Http::request() leaves it, and resolve a
 *	response for it - the same helper and calling convention as
 *	tests/kernel-smoke.php's own fakeRequest()
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *
 *	@return 	array
 */
function fakeRequest( array &$appData, string $uri ): array {
	$request = [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1' ];
	\Nino\Http::request( $appData, $request );
	return $request;
}

/**
 *	Make $uri the "current page" \Nino\Http::getRequest() answers - what
 *	[seo-alternates] reads from inside the shortcode, which never sees a
 *	request array directly (see Modules\Navigation::doShortcode() for the
 *	same technique). Written directly rather than through fakeRequest(),
 *	whose \Nino\Http::cleanUri() would strip the "&" out of '/faq&more' - a
 *	real browser could never request that uri unencoded, but a route's own
 *	'uri' field is plain configuration data, not something that runs through
 *	request-uri cleaning, so the fixture is free to use one.
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *
 *	@return 	void
 */
function seoCurrentUri( array &$appData, string $uri ): void {
	array_unshift( $appData['./nino/http/requests'], [ '/nino/http/request' => [ 'uri' => $uri ] ] );
}

// --- Fixture: routes, texts, a template file --------------------------------

// Two site pages: "about" paired across en_US/en_US and de_DE (sharing the
// internal identity '/about', the same shape as tests/kernel-smoke.php's own
// GET://rechtliches / GET://legal pair), and a locale-agnostic "faq&more"
// whose uri exercises xml escaping. Plus three entries that must never
// appear anywhere this feature generates: an excluded page, a dot-prefixed
// module endpoint, and a workbench tool uri
$appData['/nino/http/routes'] = [
	'GET://about'			=> [ 'uri' => '/about', 'locale' => 'en_US', 'body' => '[template /templates/page-about]' ],
	'GET://ueber-uns'	=> [ 'uri' => '/about', 'locale' => 'de_DE', 'body' => '[template /templates/page-about]' ],
	'GET://faq&more'	=> [ 'uri' => '/faq&more', 'body' => '' ],
	'GET://staging'		=> [ 'uri' => '/staging', 'body' => '[template /templates/page-staging]' ],
	'GET://.internal'	=> [ 'uri' => '/.internal', 'body' => 'internal content' ],
	'GET://_admin/x'	=> [ 'uri' => '/_admin/x', 'body' => 'admin content' ],
];

\Nino\Filesystem::putFileContent( $appData, '/templates/page-about.tpl', '<h1>[[/webpage/about/title]]</h1>' );

\Nino\Filesystem::putFileContent( $appData, '/text/global.php', [
	'[[/website/url]]'		=> 'example.com',
	'[[/company/name]]'	=> 'Acme Inc',
] );
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [
	'[[/webpage/about/title]]'				=> 'About Us',
	'[[/webpage/about/description]]' => 'Who we are',
] );
\Nino\Filesystem::putFileContent( $appData, '/text/de_DE.php', [
	'[[/webpage/about/title]]'						=> 'Über uns',
	'[[/webpage/about/description]]'			=> 'Wer wir sind',
	// No explicit locale on GET://faq&more, so it groups under the site's
	// native locale (de_DE, ninoSandbox()'s own default) - its title/description
	// therefore live here, not in en_US.php
	'[[/webpage/faq&more/title]]'					=> 'FAQ & Mehr',
	'[[/webpage/faq&more/description]]'	=> 'Antworten auf häufige Fragen',
] );

$aboutTemplatePath = \Nino\Filesystem::path( $appData, '/templates/page-about.tpl' );


// --- The feature - manifest and activation ----------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates without a warning', is_array( $manifest ) && ninoWarnings() === [] );
check( 'key, class and version are what the directory says', is_array( $manifest ) && $manifest['key'] === 'seo'
	&& $manifest['module'] === '\\Nino\\Modules\\Seo' && $manifest['version'] === '1.0.0' );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it keeps no data of its own', is_array( $manifest ) && $manifest['data'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native', '/nino/http/routes' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'seo' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'seo' ) === true );

$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Seo', $stored['/nino/modules'], true ) === true
	&& $stored['/nino/features']['seo']['version'] === '1.0.0' );

check( 'the settings answer their defaults', \Nino\Features::settings( $appData, 'seo' ) === [
	'exclude'			=> [],
	'disallow'		=> [],
	'robots'			=> [],
	'agents'			=> true,
	'description'	=> '',
	'llms'				=> '',
	'logo'				=> '',
] );

check( 'a posted settings form is validated and stored', \Nino\Features::saveSettings( $appData, 'seo', [
	'exclude'			=> '/staging',
	'disallow'		=> '/private',
	'robots'			=> "User-agent: BadBot\nDisallow: /",
	'description'	=> 'A tiny test site.',
	'logo'				=> 'https://example.com/logo.png',
] ) === [] );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the three technical routes', isset( $appData['/nino/http/routes']['GET://sitemap.xml'] ) === true
	&& isset( $appData['/nino/http/routes']['GET://robots.txt'] ) === true
	&& isset( $appData['/nino/http/routes']['GET://llms.txt'] ) === true );

echo "\n";


// --- /sitemap.xml ------------------------------------------------------------

echo "GET /sitemap.xml\n";

$sitemapRequest = fakeRequest( $appData, '/sitemap.xml' );
\Nino\Http::response( $appData, $sitemapRequest );
$sitemapBody = (string) ( $sitemapRequest['/nino/http/response']['body'] ?? '' );

check( 'the right content type', ( $sitemapRequest['/nino/http/response']['header']['Content-Type'] ?? '' ) === 'application/xml; charset=UTF-8' );
check( 'the "&" in a page\'s uri is escaped in the raw document', str_contains( $sitemapBody, 'faq&amp;more' ) === true
	&& str_contains( $sitemapBody, 'faq&more' ) === false );

$sitemapXml = @simplexml_load_string( $sitemapBody );
check( 'the document is well-formed xml', $sitemapXml !== false );

$locs = is_object( $sitemapXml ) ? array_map( 'strval', $sitemapXml->xpath( '//*[local-name()="url"]/*[local-name()="loc"]' ) ?: [] ) : [];
sort( $locs );
check( 'it lists exactly the site pages, as absolute urls - not the excluded, dot or admin ones', $locs === [
	'https://example.com/about',
	'https://example.com/faq&more',
	'https://example.com/ueber-uns',
] );

$hreflangs = [];
if( is_object( $sitemapXml ) )
	foreach( $sitemapXml->xpath( '//*[local-name()="url"][*[local-name()="loc"]="https://example.com/about"]/*[local-name()="link"]' ) ?: [] as $link )
		$hreflangs[(string) $link['hreflang']] = (string) $link['href'];
ksort( $hreflangs );
check( 'the about page carries hreflang alternates for both locale variants', $hreflangs === [
	'de-DE' => 'https://example.com/ueber-uns',
	'en-US' => 'https://example.com/about',
] );

$aboutLastmod = is_object( $sitemapXml )
	? (string) ( $sitemapXml->xpath( '//*[local-name()="url"][*[local-name()="loc"]="https://example.com/about"]/*[local-name()="lastmod"]' )[0] ?? '' )
	: '';
check( 'the about page carries a lastmod from its template\'s mtime', $aboutLastmod === date( 'Y-m-d', (int) filemtime( $aboutTemplatePath ) ) );

$faqLastmod = is_object( $sitemapXml )
	? ( $sitemapXml->xpath( '//*[local-name()="url"][*[local-name()="loc"]="https://example.com/faq&more"]/*[local-name()="lastmod"]' ) ?: [] )
	: [];
check( 'a page with no template body has no lastmod at all', $faqLastmod === [] );

echo "\n";


// --- /robots.txt ---------------------------------------------------------------

echo "GET /robots.txt\n";

$robotsRequest = fakeRequest( $appData, '/robots.txt' );
\Nino\Http::response( $appData, $robotsRequest );
$robotsBody = (string) ( $robotsRequest['/nino/http/response']['body'] ?? '' );

check( 'the right content type', ( $robotsRequest['/nino/http/response']['header']['Content-Type'] ?? '' ) === 'text/plain; charset=UTF-8' );
check( 'the lines are exactly the fixed ones, the settings and the sitemap, in order', $robotsBody === implode( "\n", [
	'User-agent: *',
	'Disallow: /_admin/',
	'Disallow: /.',
	'Disallow: /private',
	'Sitemap: https://example.com/sitemap.xml',
	'# llms.txt: https://example.com/llms.txt',
	'User-agent: BadBot',
	'Disallow: /',
] ). "\n" );

echo "\n";


// --- /llms.txt -------------------------------------------------------------

echo "GET /llms.txt\n";

$llmsRequest = fakeRequest( $appData, '/llms.txt' );
\Nino\Http::response( $appData, $llmsRequest );
$llmsBody = (string) ( $llmsRequest['/nino/http/response']['body'] ?? '' );

check( 'the right content type', ( $llmsRequest['/nino/http/response']['header']['Content-Type'] ?? '' ) === 'text/plain; charset=UTF-8' );
check( 'it opens with the heading and the description', str_starts_with( $llmsBody, "# Acme Inc\n\n> A tiny test site.\n" ) === true );
check( 'it lists the paired page under both locale headings, and the unpaired one under the native locale', str_contains( $llmsBody, "## Pages" ) === true
	&& str_contains( $llmsBody, "### en-US" ) === true && str_contains( $llmsBody, '- [About Us](https://example.com/about): Who we are' ) === true
	&& str_contains( $llmsBody, "### de-DE" ) === true && str_contains( $llmsBody, '- [Über uns](https://example.com/ueber-uns): Wer wir sind' ) === true
	&& str_contains( $llmsBody, '- [FAQ & Mehr](https://example.com/faq&more): Antworten auf häufige Fragen' ) === true );
check( 'the excluded page never appears', str_contains( $llmsBody, 'staging' ) === false );

echo "\n";


// --- [seo-alternates] -----------------------------------------------------------

echo "[seo-alternates]\n";

seoCurrentUri( $appData, '/about' );
$alternatesHtml = \Nino\Modules\Seo::doAlternatesShortcode( $appData, [] );
check( 'on a paired page it links both locale variants plus x-default', str_contains( $alternatesHtml, 'hreflang="en-US" href="https://example.com/about">' ) === true
	&& str_contains( $alternatesHtml, 'hreflang="de-DE" href="https://example.com/ueber-uns">' ) === true
	&& str_contains( $alternatesHtml, 'hreflang="x-default" href="https://example.com/ueber-uns">' ) === true );

seoCurrentUri( $appData, '/faq&more' );
check( 'on a page without variants it renders nothing', \Nino\Modules\Seo::doAlternatesShortcode( $appData, [] ) === '' );

seoCurrentUri( $appData, '/nowhere' );
check( 'on a uri that is not a known page it renders nothing either', \Nino\Modules\Seo::doAlternatesShortcode( $appData, [] ) === '' );

echo "\n";


// --- [seo-jsonld] ----------------------------------------------------------------

echo "[seo-jsonld]\n";

$jsonLdHtml = \Nino\Modules\Seo::doJsonLdShortcode( $appData, [] );
check( 'it wraps a single application/ld+json script tag', str_starts_with( $jsonLdHtml, '<script type="application/ld+json">' ) === true
	&& str_ends_with( $jsonLdHtml, '</script>' ) === true );

$jsonLdInner = substr( $jsonLdHtml, strlen( '<script type="application/ld+json">' ), -strlen( '</script>' ) );
$jsonLdData = json_decode( $jsonLdInner, true );
check( 'the decoded json is a valid Organization + WebSite graph carrying the company name, url and logo', is_array( $jsonLdData )
	&& ( $jsonLdData['@graph'][0]['@type'] ?? null ) === 'Organization'
	&& ( $jsonLdData['@graph'][0]['name'] ?? null ) === 'Acme Inc'
	&& ( $jsonLdData['@graph'][0]['url'] ?? null ) === 'https://example.com'
	&& ( $jsonLdData['@graph'][0]['logo'] ?? null ) === 'https://example.com/logo.png'
	&& ( $jsonLdData['@graph'][1]['@type'] ?? null ) === 'WebSite' );

echo "\n";


// --- agents off: llms.txt 404s, robots.txt drops the mention --------------------

echo "The 'agents' setting off - llms.txt 404s, robots.txt drops the mention\n";

check( 'agents is switched off', \Nino\Features::saveSettings( $appData, 'seo', [ 'agents' => 'false' ] ) === [] );

$llmsOffRequest = fakeRequest( $appData, '/llms.txt' );
\Nino\Http::response( $appData, $llmsOffRequest );
check( 'llms.txt now answers 404', $llmsOffRequest['/nino/http/response']['statusCode'] === 404 );

$robotsOffRequest = fakeRequest( $appData, '/robots.txt' );
\Nino\Http::response( $appData, $robotsOffRequest );
check( 'robots.txt no longer mentions llms.txt', str_contains( (string) ( $robotsOffRequest['/nino/http/response']['body'] ?? '' ), 'llms.txt' ) === false );
check( '...while the sitemap line is still there', str_contains( (string) ( $robotsOffRequest['/nino/http/response']['body'] ?? '' ), 'Sitemap: https://example.com/sitemap.xml' ) === true );

check( 'agents is switched back on', \Nino\Features::saveSettings( $appData, 'seo', [ 'agents' => 'true' ] ) === [] );

echo "\n";


// --- Deactivation ------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'seo' ) === true );
check( 'the class is removed from /nino/modules', in_array( '\\Nino\\Modules\\Seo', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the settings survive deactivation', \Nino\Features::setting( $appData, 'seo', 'description' ) === 'A tiny test site.' );

ninoDone( $appData );
