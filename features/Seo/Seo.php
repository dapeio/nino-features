<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Seo						see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Seo								What search engines and AI agents ask a site for,
	 *										generated from what Nino already knows - the persisted
	 *										GET routes, their locales, and the /webpage<uri>/title
	 *										and /webpage<uri>/description texts the wizard's Webpages
	 *										step (or a hand-edited /text/<locale>.php) already
	 *										writes. Nothing here is stored under data/ and nothing is
	 *										maintained by hand: every response is built fresh from
	 *										config.php on every request.
	 *
	 *										Three technical endpoints, each registered in init() the
	 *										way Modules\Newsletter owns /.newsletter or the feature
	 *										recipe's Catalog owns /api/catalog - GET://sitemap.xml,
	 *										GET://robots.txt and GET://llms.txt. The base install's
	 *										own units/base/manifest.php persists routes under these
	 *										exact same keys with a static template body the wizard
	 *										fills in once at setup and nobody updates again; the
	 *										plain assignment below overwrites that persisted entry in
	 *										the live array for the lifetime of this request, the same
	 *										"a stale persisted entry cannot shadow its behavior"
	 *										reasoning the Catalog recipe's own docblock spells out.
	 *										Deactivating this feature simply stops overwriting it, so
	 *										whatever a project had before (the static templates, or
	 *										nothing) answers again.
	 *
	 *										A "page" is any persisted GET route whose external path
	 *										(the part of its route key after "GET:/") is not one of
	 *										this feature's own three endpoints, not below /_ (the
	 *										workbench) or /. (a module's own technical endpoint), and
	 *										not matched by the exclude setting. Two routes are locale
	 *										variants of the same page when they share the same 'uri'
	 *										field (the internal identity \Nino\Http::findRouteUri()
	 *										itself pairs a locale switch against) with different
	 *										'locale' values - see tests/kernel-smoke.php's own
	 *										GET://rechtliches / GET://legal pair for exactly this
	 *										shape. <loc> and every href always carry the external
	 *										path (what a crawler can actually fetch), never the
	 *										internal uri, which need not be reachable on its own -
	 *										tests/fixtures/features/Sample/install/manifest.php's
	 *										GET://sample-de -> uri "/beispiel" is exactly such a case.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Seo {

		public const string KEY = 'seo';

		// This feature's own three endpoints - never listed as a "page"
		// themselves, whatever a persisted route happens to say about them
		private const array RESERVED_PATHS = [ '/sitemap.xml', '/robots.txt', '/llms.txt' ];

		/**
		 *	Register the three technical routes and their handlers. GET://llms.txt
		 *	is always registered, even while the 'agents' setting is off -
		 *	callbackLlms() answers the 404 itself, the same shape the feature
		 *	recipe's Catalog uses for its own 'public' setting, so a project's
		 *	persisted, wizard-written llms.txt route (if any) is reliably
		 *	replaced rather than left to answer its stale static template.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'seo-alternates', [ self::class, 'doAlternatesShortcode' ] );
			\Nino\Html::addShortcode( $appData, 'seo-jsonld', [ self::class, 'doJsonLdShortcode' ] );

			$appData['/nino/http/routes']['GET://sitemap.xml']	= [ 'uri' => '/sitemap.xml', 'header' => [ 'Content-Type' => 'application/xml; charset=UTF-8' ] ];
			$appData['/nino/http/routes']['GET://robots.txt']		= [ 'uri' => '/robots.txt', 'header' => [ 'Content-Type' => 'text/plain; charset=UTF-8' ] ];
			$appData['/nino/http/routes']['GET://llms.txt']			= [ 'uri' => '/llms.txt', 'header' => [ 'Content-Type' => 'text/plain; charset=UTF-8' ] ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://sitemap.xml', [ self::class, 'callbackSitemap' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://robots.txt', [ self::class, 'callbackRobots' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://llms.txt', [ self::class, 'callbackLlms' ] );
		}

		/**
		 *	GET /sitemap.xml - every site page, one <url> each, with an
		 *	<xhtml:link rel="alternate"> per locale variant and a <lastmod>
		 *	where the body is a plain template include
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackSitemap( array &$appData, array &$request ): void {
			$request['/nino/http/response']['body'] = self::_buildSitemap( $appData );
		}

		/**
		 *	GET /robots.txt - the always-disallowed paths, the operator's own
		 *	disallows, the sitemap, an llms.txt mention while it is on, and
		 *	the operator's own free-form lines
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackRobots( array &$appData, array &$request ): void {
			$request['/nino/http/response']['body'] = self::_buildRobots( $appData );
		}

		/**
		 *	GET /llms.txt - the llmstxt.org convention: a heading, a one-line
		 *	description, an optional free block, then every site page as a
		 *	markdown link with its own description, grouped by locale once
		 *	the site has more than one. A plain 404 while 'agents' is off,
		 *	rather than leaving that decision to whether a route happens to
		 *	be registered - see init()'s own docblock for why.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLlms( array &$appData, array &$request ): void {

			if( \Nino\Features::setting( $appData, self::KEY, 'agents', true ) !== true ) {

				// Not Http::fail(): that answers a json body, and
				// _finalizeResponse() would then overwrite this route's own
				// text/plain Content-Type with application/json - wrong for
				// a plain-text resource that merely happens to be off
				$request['/nino/http/response']['statusCode']	= 404;
				$request['/nino/http/response']['body']				= '';
				return;
			}

			$request['/nino/http/response']['body'] = self::_buildLlms( $appData );
		}

		/**
		 *	[seo-alternates] - the <link rel="alternate" hreflang="..."> lines
		 *	for the current page's locale variants plus x-default, for a
		 *	project's html-header.tpl to place in <head>. The "current page"
		 *	is read off the live request (\Nino\Http::getRequest(), the same
		 *	way Modules\Navigation finds it from inside a shortcode, which
		 *	never sees $request itself) and matched against the persisted
		 *	routes' external paths, exactly what sitemap.xml is built from.
		 *	Renders '' for a page with no locale variant, or none at all.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments (unused)
		 *
		 *	@return 	string
		 */
		public static function doAlternatesShortcode( array &$appData, array $args ): string {

			$currentPath = (string) ( \Nino\Http::getRequest( $appData )['/nino/http/request']['uri'] ?? '' );
			if( $currentPath === '' )
				return '';

			$pages = self::_pages( $appData );

			$current = null;
			foreach( $pages as $page )
				if( $page['externalPath'] === $currentPath ) {
					$current = $page;
					break;
				}

			if( $current === null )
				return '';

			$alternates = self::_alternatesFor( $pages, $current['uri'] );
			if( count( $alternates ) < 2 )
				return '';

			$base = self::_baseUrl( $appData );
			$html = '';

			foreach( $alternates as $alternate )
				$html .= '<link rel="alternate" hreflang="'. self::_attrEscape( self::_bcp47( (string) $alternate['locale'] ) ). '" href="'. self::_attrEscape( $base. $alternate['externalPath'] ). "\">\n";

			$default = self::_defaultAlternate( $appData, $alternates );
			$html .= '<link rel="alternate" hreflang="x-default" href="'. self::_attrEscape( $base. $default['externalPath'] ). "\">\n";

			return $html;
		}

		/**
		 *	[seo-jsonld] - a minimal Organization + WebSite json-ld block. No
		 *	nonce: a <script type="application/ld+json"> is never executed
		 *	by a browser (the html spec's "prepare the script element" only
		 *	runs a script whose type is empty, a javascript mime type,
		 *	"module" or "importmap" - anything else, this type included, is
		 *	left as an inert data block), so it is not subject to the
		 *	Content-Security-Policy's script-src/default-src at all - there
		 *	is nothing here for that policy to block. html-header.tpl's own
		 *	LocalBusiness block relies on exactly the same fact and carries
		 *	no nonce either.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments (unused)
		 *
		 *	@return 	string
		 */
		public static function doJsonLdShortcode( array &$appData, array $args ): string {

			$name	= \Nino\Html::renderTextfill( $appData, '/company/name' );
			$url	= self::_baseUrl( $appData );

			$organization = [ '@type' => 'Organization', 'name' => $name, 'url' => $url ];

			$logo = (string) \Nino\Features::setting( $appData, self::KEY, 'logo', '' );
			if( $logo !== '' )
				$organization['logo'] = $logo;

			$data = [
				'@context'	=> 'https://schema.org',
				'@graph'		=> [
					$organization,
					[ '@type' => 'WebSite', 'name' => $name, 'url' => $url ],
				],
			];

			// JSON_HEX_TAG turns '<' and '>' into </>, which is what
			// actually keeps a value carrying '</script>' from ending this
			// element early - the same reasoning and the same flag Modules\Jstext
			// and Html::doJsonShortcode() apply to admin-editable text going
			// into an inline <script>
			$json = json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

			return '<script type="application/ld+json">'. ( is_string( $json ) ? $json : '{}' ). '</script>';
		}

		/**
		 *	Every persisted GET route that counts as a site page: not this
		 *	feature's own three endpoints, not below /_ or /. , not matched
		 *	by the exclude setting. Read from config.php directly, the way
		 *	\Nino\Features::activate() reads routes to apply a unit against -
		 *	never the live array, which also carries this request's own
		 *	runtime routes (the workbench's, every active module's own).
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ [ 'externalPath', 'uri', 'locale' (string|null), 'body' ], ... ]
		 */
		private static function _pages( array &$appData ): array {

			$exclude	= (array) \Nino\Features::setting( $appData, self::KEY, 'exclude', [] );
			$pages		= [];

			foreach( self::_persistedRoutes( $appData ) as $routeKey => $route ) {

				if( is_string( $routeKey ) === false || str_starts_with( $routeKey, 'GET:/' ) === false || is_array( $route ) === false )
					continue;

				$externalPath = substr( $routeKey, strlen( 'GET:/' ) );

				if( self::_isPage( $externalPath, $exclude ) === false )
					continue;

				$pages[] = [
					'externalPath'	=> $externalPath,
					'uri'						=> is_string( $route['uri'] ?? null ) ? $route['uri'] : $externalPath,
					'locale'				=> is_string( $route['locale'] ?? null ) ? $route['locale'] : null,
					'body'					=> is_string( $route['body'] ?? null ) ? $route['body'] : '',
				];
			}

			return $pages;
		}

		/**
		 *	Whether $path is a site page worth listing - not this feature's
		 *	own endpoints, not a tool or module uri, not excluded. Plain
		 *	string work against the exclude patterns on purpose, the same
		 *	reasoning as Modules\Stats::_excluded(): an admin-editable
		 *	pattern must never reach a regex engine.
		 *
		 *	@param		string		$path
		 *	@param		array			$exclude			The 'exclude' setting's lines
		 *
		 *	@return 	bool
		 */
		private static function _isPage( string $path, array $exclude ): bool {

			if( $path === '' || in_array( $path, self::RESERVED_PATHS, true ) === true )
				return false;

			if( str_starts_with( $path, '/_' ) === true || str_starts_with( $path, '/.' ) === true )
				return false;

			foreach( $exclude as $pattern ) {

				if( is_string( $pattern ) === false || $pattern === '' )
					continue;

				if( str_ends_with( $pattern, '/*' ) === false ) {
					if( $path === $pattern )
						return false;
					continue;
				}

				$prefix = substr( $pattern, 0, -2 );
				if( $path === $prefix || str_starts_with( $path, $prefix. '/' ) === true )
					return false;
			}

			return true;
		}

		/**
		 *	Every page in $pages that shares $uri (the internal identity) and
		 *	carries an explicit locale - the locale variants
		 *	\Nino\Http::findRouteUri() itself would pair a locale switch
		 *	against. A page sharing the uri without a locale of its own (the
		 *	route answers regardless of locale) is not one of these: there is
		 *	no language to put in its hreflang.
		 *
		 *	@param		array			$pages
		 *	@param		string		$uri
		 *
		 *	@return 	array
		 */
		private static function _alternatesFor( array $pages, string $uri ): array {

			$alternates = [];

			foreach( $pages as $page )
				if( $page['uri'] === $uri && $page['locale'] !== null )
					$alternates[] = $page;

			return $alternates;
		}

		/**
		 *	Which of a page's locale alternates stands in for hreflang
		 *	"x-default" - the site's native locale's variant, else simply the
		 *	first
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$alternates		From _alternatesFor(), never empty
		 *
		 *	@return 	array
		 */
		private static function _defaultAlternate( array &$appData, array $alternates ): array {

			$native = \Nino\Locales::getNativeLocale( $appData );

			foreach( $alternates as $alternate )
				if( $alternate['locale'] === $native )
					return $alternate;

			return $alternates[0];
		}

		/**
		 *	The persisted routes of config.php - never the live array, see
		 *	_pages()'s own docblock. Same read \Nino\Features::activate()
		 *	itself uses.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _persistedRoutes( array &$appData ): array {

			$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
			$stored = is_array( $stored ) ? $stored : [];

			return is_array( $stored['/nino/http/routes'] ?? null ) ? $stored['/nino/http/routes'] : \Nino\AppData::DEFAULTS['/nino/http/routes'];
		}

		/**
		 *	'https://' plus the '/website/url' textfill (the bare domain the
		 *	wizard's PersonalInfos step writes, eg. "www.example.com" -
		 *	html-header.tpl's own canonical link builds the same way), a
		 *	trailing slash trimmed off in case a project's value carries one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _baseUrl( array &$appData ): string {

			return 'https://'. trim( \Nino\Html::renderTextfill( $appData, '/website/url' ), '/' );
		}

		/**
		 *	@param		string		$locale				Eg. 'de_DE'
		 *
		 *	@return 	string									As bcp47, eg. 'de-DE' - Nino's own locale ids already carry
		 *																	the right casing, so replacing the separator is all this needs
		 */
		private static function _bcp47( string $locale ): string {

			return str_replace( '_', '-', $locale );
		}

		/**
		 *	Escape a value for use inside xml element content or an
		 *	attribute, ENT_XML1 rather than the html entity set
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function _xmlEscape( string $value ): string {

			return htmlspecialchars( $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
		}

		/**
		 *	Escape a value for an html attribute - [seo-alternates] renders
		 *	plain html, not xml, so the ordinary entity set applies here
		 *	rather than _xmlEscape()'s
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function _attrEscape( string $value ): string {

			return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
		}

		/**
		 *	A page's <lastmod>, from the mtime of the template file its own
		 *	body includes - null (omitted) for anything else: a body built by
		 *	a route callback, an inline body, an empty one. Only the plain
		 *	"[template /path]" shape is understood, the same one
		 *	Modules\Template::doShortcode() itself resolves to "path.tpl".
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$body
		 *
		 *	@return 	string|null							'Y-m-d', or null
		 */
		private static function _lastmod( array &$appData, string $body ): ?string {

			if( preg_match( '#^\[template\s+(/[^\]\s]+)\]$#', trim( $body ), $matches ) !== 1 )
				return null;

			$path = \Nino\Filesystem::path( $appData, $matches[1]. '.tpl' );
			if( is_file( $path ) === false )
				return null;

			$mtime = filemtime( $path );

			return $mtime === false ? null : date( 'Y-m-d', $mtime );
		}

		/**
		 *	The merged textfills a specific locale would see - what
		 *	\Nino\Html::getFills() answers for the *current* one, parameterized
		 *	instead of read off \Nino\Locales::getCurrentLocale(). llms.txt
		 *	lists every locale's pages in one response, so it needs each
		 *	page's own title/description without switching the visitor's
		 *	actual locale to get them: \Nino\Locales::setCurrentLocale() also
		 *	persists into the session (see its own docblock), which would
		 *	otherwise start a session for every anonymous crawler that asks
		 *	for this file - precisely what \Nino\Locales::init() itself
		 *	takes care never to do for a locale nobody chose.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		private static function _fillsForLocale( array &$appData, string $locale ): array {

			return array_merge(
				(array) \Nino\Filesystem::getFileContent( $appData, $appData['/nino/locales/textfiles']. '/global.php', [] ),
				(array) \Nino\Filesystem::getFileContent( $appData, $appData['/nino/locales/textfiles']. '/'. $locale. '.php', [] ),
				(array) ( $appData['./nino/html/fills'][$locale] ?? [] ),
				(array) ( $appData['./nino/html/fills']['*'] ?? [] )
			);
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									The complete sitemap.xml document
		 */
		private static function _buildSitemap( array &$appData ): string {

			$base	= self::_baseUrl( $appData );
			$pages	= self::_pages( $appData );

			$xml = '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
			$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'. "\n";

			foreach( $pages as $page ) {

				$xml .= "\t<url>\n";
				$xml .= "\t\t<loc>". self::_xmlEscape( $base. $page['externalPath'] ). "</loc>\n";

				foreach( self::_alternatesFor( $pages, $page['uri'] ) as $alternate )
					$xml .= "\t\t<xhtml:link rel=\"alternate\" hreflang=\"". self::_xmlEscape( self::_bcp47( (string) $alternate['locale'] ) ). "\" href=\"". self::_xmlEscape( $base. $alternate['externalPath'] ). "\" />\n";

				$lastmod = self::_lastmod( $appData, $page['body'] );
				if( $lastmod !== null )
					$xml .= "\t\t<lastmod>". $lastmod. "</lastmod>\n";

				$xml .= "\t</url>\n";
			}

			$xml .= '</urlset>';

			return $xml;
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									The complete robots.txt document
		 */
		private static function _buildRobots( array &$appData ): string {

			$lines = [ 'User-agent: *', 'Disallow: /_admin/', 'Disallow: /.' ];

			foreach( (array) \Nino\Features::setting( $appData, self::KEY, 'disallow', [] ) as $path )
				if( is_string( $path ) === true && $path !== '' )
					$lines[] = 'Disallow: '. $path;

			$base = self::_baseUrl( $appData );
			$lines[] = 'Sitemap: '. $base. '/sitemap.xml';

			if( \Nino\Features::setting( $appData, self::KEY, 'agents', true ) === true )
				$lines[] = '# llms.txt: '. $base. '/llms.txt';

			foreach( (array) \Nino\Features::setting( $appData, self::KEY, 'robots', [] ) as $line )
				if( is_string( $line ) === true && $line !== '' )
					$lines[] = $line;

			return implode( "\n", $lines ). "\n";
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									The complete llms.txt document
		 */
		private static function _buildLlms( array &$appData ): string {

			$name					= \Nino\Html::renderTextfill( $appData, '/company/name' );
			$description	= (string) \Nino\Features::setting( $appData, self::KEY, 'description', '' );
			$free					= (string) \Nino\Features::setting( $appData, self::KEY, 'llms', '' );

			$lines = [ '# '. $name ];

			if( $description !== '' ) {
				$lines[] = '';
				$lines[] = '> '. $description;
			}

			if( $free !== '' ) {
				$lines[] = '';
				$lines[] = $free;
			}

			$lines[] = '';
			$lines[] = '## Pages';

			$base				= self::_baseUrl( $appData );
			$native			= \Nino\Locales::getNativeLocale( $appData );
			$locales		= \Nino\Locales::getAvailableLocales( $appData );
			$multiLocale	= count( $locales ) > 1;

			// Grouped by the locale a page actually renders in - its own
			// 'locale' where the route names one, else the site's native
			// locale, the same fallback the base template renders a
			// locale-agnostic route in
			$byLocale = [];
			foreach( self::_pages( $appData ) as $page ) {
				$locale = $page['locale'] ?? $native;
				$byLocale[$locale][] = $page;
			}

			foreach( $byLocale as $locale => $localePages ) {

				$fills = self::_fillsForLocale( $appData, (string) $locale );
				$entries = [];

				foreach( $localePages as $page ) {

					$title = trim( (string) ( $fills['[[/webpage'. $page['uri']. '/title]]'] ?? '' ) );
					if( $title === '' )
						continue;

					$pageDescription = trim( (string) ( $fills['[[/webpage'. $page['uri']. '/description]]'] ?? '' ) );
					$link = '- ['. $title. ']('. $base. $page['externalPath']. ')';

					$entries[] = $pageDescription !== '' ? $link. ': '. $pageDescription : $link;
				}

				if( $entries === [] )
					continue;

				if( $multiLocale === true ) {
					$lines[] = '';
					$lines[] = '### '. self::_bcp47( (string) $locale );
				}

				$lines[] = '';
				array_push( $lines, ...$entries );
			}

			return implode( "\n", $lines ). "\n";
		}
	}
}
