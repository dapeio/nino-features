<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Consent				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Consent						A cookie/consent banner without a third party, and
	 *										consent-gated scripts: a site embeds an analytics or
	 *										map script only after the visitor allowed that
	 *										category. Categories are fixed - "necessary" (always
	 *										on), "statistics", "marketing", "external" (maps,
	 *										videos) - and the settings say which of the optional
	 *										three a site uses at all.
	 *
	 *										The banner is the component consent. No page names it:
	 *										on every html page of the site the callback of the
	 *										output phase draws it at the start of the body, so
	 *										that a project cannot leave it out of a frame it
	 *										wrote, and a page that is a draft preview has none.
	 *										Its look is the variant the theme chose for the
	 *										component. The button that opens the choice again is
	 *										the component consent-settings, which a page, a block
	 *										and the section of the privacy policy put where they
	 *										like. What the schemas and the templates cannot say
	 *										is here: the words, the policy address and the
	 *										categories the settings switched on, whether a
	 *										category is currently allowed, and the hosts of the
	 *										page's gated scripts in the Content-Security-Policy
	 *										(see callbackOutput()).
	 *
	 *										The choice itself is a cookie the browser writes
	 *										(consent.js) - PHP only ever reads it, see allowed().
	 *										consent.js reaches the browser in the site's script
	 *										bundle, so that it gates the scripts of every page,
	 *										banner or not.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Consent {

		// What the renderers put into the templates of the components where there is something to put: one line each,
		// the tokens in double brackets. A word is filled in by the renderer, the register reads nothing again
		public static array $html = [
			'category'	=> '<label class="consent-category"><input type="checkbox" data-consent-category="[[category]]"[[state]]><span class="consent-category-name">[[name]]</span><span class="consent-category-hint">[[hint]]</span></label>',
			'link'			=> ' <a href="[[url]]" class="consent-link">[[name]]</a>',
			'state'			=> ' checked disabled',
		];

		// The fixed category list, in the order the banner shows them.
		// "necessary" is always on and never a setting; the other three are
		// switched on per site under /nino/features/consent/settings - see
		// feature.php
		private const array CATEGORIES = [ 'necessary', 'statistics', 'marketing', 'external' ];

		// The categories a site can turn on or off - CATEGORIES without the
		// one that is always on
		private const array OPTIONAL_CATEGORIES = [ 'statistics', 'marketing', 'external' ];

		// The cookie name where the settings have none: the fourth argument
		// of both reads of the setting (componentConsent() and allowed()),
		// the same literal the manifest defaults to. consent.js keeps a
		// constant of its own for a page without a banner to read the name from
		private const string DEFAULT_COOKIE_NAME = 'nino_consent';

		/**
		 *	Hook the output phase, where the banner joins the finished page and the hosts of the page's
		 *	gated scripts join the policy, and the Legal module's section on consent
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// The banner is drawn into the finished page and the policy is widened from what that page holds:
			// both need the body, and only this hook has it. Below priority 9, where the page cache stores the page
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackBanner' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOffered' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOutput' ] );

			// The name as a string: the Legal module is a kernel module a project
			// may have switched off - nothing fires the hook then, and nothing is lost
			\Nino\Callbacks::registerCallback( $appData, '/nino/legal/section', [ self::class, 'callbackLegalSection' ] );
		}

		/**
		 *	The banner: title, text, an optional privacy link, one checkbox per category the settings enabled
		 *	("necessary" always, checked and disabled) and the three actions. Hidden by default - consent.js
		 *	unhides it once it finds no stored choice. The words are fills of the template, which the register
		 *	puts in; the words of the categories are looked up here, since a token is finished markup
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the register resolved: the attributes as strings
		 *	@param		string		$content			Nothing: the component holds nothing
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string									The banner
		 */
		public static function componentConsent( array &$appData, array $args, string $content, array $context ): string {

			$cookieName	= (string) \Nino\Features::setting( $appData, 'consent', 'cookieName', self::DEFAULT_COOKIE_NAME );
			$days				= (int) \Nino\Features::setting( $appData, 'consent', 'days', 180 );
			$policyUrl	= (string) \Nino\Features::setting( $appData, 'consent', 'policyUrl', '' );

			/*	No address in the settings: the privacy policy of the Legal module, in
				the visitor's language, where the module is there and its page has a
				route. The setting wins whenever it is set. A feature does not depend
				on a module (the catalogue's AGENTS.md, section 4c), so what it asks of
				Legal sits behind class_exists() and not method_exists(): PHPStan reads
				the second as always false where the class is not part of the checkout
				and as always true where it is	*/
			if( $policyUrl === '' && class_exists( '\\Nino\\Modules\\Legal' ) === true )
				$policyUrl = \Nino\Modules\Legal::url( $appData, 'privacy' );

			$categories = '';
			foreach( self::CATEGORIES as $category )
				$categories .= self::_category( $appData, $category );

			$policy = $policyUrl === ''
				? ''
				: strtr( self::$html['link'], [ '[[url]]' => htmlspecialchars( $policyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), '[[name]]' => self::_word( $appData, 'banner/link' ) ] );

			return \Nino\Components::fill( $appData, 'consent', $args, $content, [
				// A banner in the flow of a preview is shown; the one of a page waits for the script
				'[[hidden]]'		=> $args['inline'] === '1' ? '' : ' hidden',
				// The dialog holds the page and is a dialog; the bar and the box are a notice that leaves the page as it is
				'[[role]]'			=> ( $args['variant'] ?? '' ) === 'dialog' ? 'dialog' : 'region',
				'[[cookie]]'		=> \Nino\Components::escape( $cookieName ),
				'[[days]]'			=> (string) $days,
				'[[policy]]'		=> $policy,
				'[[categories]]'	=> $categories,
			] );
		}

		/**
		 *	The banner, drawn into the finished page of the site: right after the start tag of the body of every html
		 *	response - it is the first stop of the Tab key and the first thing a screen reader meets, and the part keeps
		 *	it fixed at the edge of the window - but not of the workbench, not of a draft that is previewed and not of
		 *	a page that has no body to put it in. The start tag is looked for after the end of the head, so that a
		 *	script or a comment of the head that spells one is no place for it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackBanner( array &$appData, array &$request ): void {

			if( self::_workbench( $request ) === true || \Nino\Preview::active( $appData ) === true || self::_page( $request ) === false )
				return;

			$body = (string) $request['/nino/http/response']['body'];
			$head = stripos( $body, '</head>' );

			if( preg_match( '/<body(?=[\s>])(?:[^>"\']|"[^"]*"|\'[^\']*\')*+>/i', $body, $found, PREG_OFFSET_CAPTURE, $head === false ? 0 : $head ) !== 1 )
				return;

			$banner = trim( \Nino\Html::renderSource( $appData, '[consent]', \Nino\Html::context( $appData ) ) );
			$at = $found[0][1] + strlen( $found[0][0] );

			if( $banner !== '' )
				$request['/nino/http/response']['body'] = substr( $body, 0, $at ). "\n". $banner. substr( $body, $at );
		}

		/**
		 *	The categories the site offers, on the root element of every html page of the site, the preview of a draft
		 *	too: data-consent-offered="necessary,statistics". The script gives out what the cookie lists and this names,
		 *	and nothing else - on a page with no banner as on one with. The attribute goes into the start tag of <html>
		 *	where the page opens with it, after a doctype or a comment and nothing else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackOffered( array &$appData, array &$request ): void {

			if( self::_workbench( $request ) === true || self::_page( $request ) === false )
				return;

			$offered = [ 'necessary' ];
			foreach( self::OPTIONAL_CATEGORIES as $category )
				if( \Nino\Features::setting( $appData, 'consent', $category, false ) === true )
					$offered[] = $category;

			$count = 0;
			$tagged = preg_replace( '/^(\s*(?:<!--.*?-->\s*|<!doctype[^>]*>\s*)*<html)(?=[\s>])/is', '$1 data-consent-offered="'. implode( ',', $offered ). '"', (string) $request['/nino/http/response']['body'], 1, $count );

			if( $count === 1 && is_string( $tagged ) === true )
				$request['/nino/http/response']['body'] = $tagged;
		}

		/**
		 *	The button that reopens the choice (consent.js binds the click): a call of the component, which
		 *	draws the button from its template
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the register resolved
		 *	@param		string		$content			Nothing
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string									The button
		 */
		public static function componentConsentSettings( array &$appData, array $args, string $content, array $context ): string {
			return \Nino\Components::fill( $appData, 'consent-settings', $args, $content, [] );
		}

		/**
		 *	The button that reopens the choice, at the end of the privacy
		 *	policy's section on consent - listener of '/nino/legal/section', which
		 *	the Legal module fires for every section it draws. The section is the
		 *	one this feature's own install unit adds ('consent' of the type
		 *	'privacy'), so the withdrawal the text speaks of is on the page it
		 *	speaks on, and a site that does not run this feature shows no button.
		 *	The module takes what is added as finished html, so the button is drawn here
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$section			(reference) [ 'type', 'id', 'html' ] of the section about to be drawn
		 *
		 *	@return 	void
		 */
		public static function callbackLegalSection( array &$appData, array &$section ): void {

			if( ( $section['type'] ?? null ) !== 'privacy' || ( $section['id'] ?? null ) !== 'consent' || is_string( $section['html'] ?? null ) === false )
				return;

			$section['html'] .= \Nino\Html::renderSource( $appData, '[consent-settings]', \Nino\Html::context( $appData ) );
		}

		/**
		 *	Whether a category is currently allowed - reads the cookie
		 *	consent.js wrote, never writes one itself. "necessary" is always
		 *	true; a category the settings did not switch on for this site is
		 *	never allowed, whatever an old cookie might still say; an unknown
		 *	category name is refused the same way
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$category			One of "necessary", "statistics", "marketing", "external"
		 *
		 *	@return 	bool
		 */
		public static function allowed( array &$appData, string $category ): bool {

			if( $category === 'necessary' )
				return true;

			if( in_array( $category, self::OPTIONAL_CATEGORIES, true ) === false )
				return false;

			if( \Nino\Features::setting( $appData, 'consent', $category, false ) !== true )
				return false;

			$cookieName	= (string) \Nino\Features::setting( $appData, 'consent', 'cookieName', self::DEFAULT_COOKIE_NAME );

			// is_string() rather than a cast: a cookie is sent by the client,
			// and 'nino_consent[]=x' parses to an array. Cast, that raises
			// "Array to string conversion" - a level \Nino\Runtime treats as
			// fatal, ie. an unauthenticated 500 on every page of the site
			// from a cookie anybody can set
			$raw				= is_string( $_COOKIE[ $cookieName ] ?? null ) === true ? $_COOKIE[ $cookieName ] : '';

			if( $raw === '' )
				return false;

			$allowed = array_map( 'trim', explode( ',', $raw ) );

			return in_array( $category, $allowed, true );
		}

		/**
		 *	Name the hosts of this page's consent-gated scripts in the
		 *	Content-Security-Policy's script-src. consent.js releases a
		 *	<script type="text/plain" data-consent="..." data-src="https://...">
		 *	by cloning it into a real script, and the shipped policy (script-src
		 *	'self' and the jstext nonce) refuses the host - so without this a
		 *	placeholder is consent for a script the browser then blocks.
		 *
		 *	The hosts are read out of the finished body, which is why this is on
		 *	/nino/http/output and not on /nino/http/response, and which is also the
		 *	trust question: markup that reaches the page can name a host. Three
		 *	things hold that to what a project wrote on purpose. Only a placeholder
		 *	of a category this site offers counts ('necessary' and the optional
		 *	ones its settings switched on - the rule allowed() keeps), so a
		 *	category nobody offers cannot be used to open the policy. Only an
		 *	https host that is a plain ascii name counts, with no credentials and
		 *	no ip address. And an inline, relative, http: or protocol-relative
		 *	placeholder adds nothing. What is left is a script that redirects, a
		 *	hosted script's own requests and a cached page.
		 *
		 *	script-src is extended where it exists and built from default-src where
		 *	it does not; script-src-elem only where the policy has one; 'none' is
		 *	left as the project decided it - see \Nino\Http::allowSource().
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackOutput( array &$appData, array &$request ): void {

			// The workbench sends a policy of its own and is not a page of the website:
			// a placeholder in a page it previews is none of its business
			if( self::_workbench( $request ) === true )
				return;

			$body = $request['/nino/http/response']['body'] ?? null;

			if( is_string( $body ) === false || stripos( $body, 'text/plain' ) === false )
				return;

			// A placeholder in a comment is none: the browser draws nothing of it
			$body = preg_replace( '/<!--.*?-->/s', '', $body );

			if( is_string( $body ) === false )
				return;

			$policy = $request['/nino/http/response']['header']['Content-Security-Policy'] ?? '';

			if( is_string( $policy ) === false || $policy === '' )
				return;

			$offered = [ 'necessary' ];
			foreach( self::OPTIONAL_CATEGORIES as $category )
				if( \Nino\Features::setting( $appData, 'consent', $category, false ) === true )
					$offered[] = $category;

			/*	A start tag, with a quoted value allowed to hold a '>' - the same
				way a browser reads one. Possessive, so an unterminated quote costs
				one pass over the rest of the body and not one per backtrack	*/
			if( preg_match_all( '/<script\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*+>/i', $body, $tags ) === false )
				return;

			$origins = [];

			foreach( $tags[0] as $tag ) {

				$attributes = self::_attributes( $tag );

				if( strtolower( $attributes['type'] ?? '' ) !== 'text/plain' || in_array( $attributes['data-consent'] ?? '', $offered, true ) === false )
					continue;

				$origin = \Nino\Feature::origin( $attributes['data-src'] ?? '' );

				if( $origin !== '' )
					$origins[$origin] = true;
			}

			// A script-src-elem is widened where the policy has one. Where it has
			// none the call changes nothing, and it is not created: the browser
			// answers script elements from script-src, which has the origin by now
			foreach( array_keys( $origins ) as $origin ) {
				\Nino\Feature::allowOrigin( $request, 'script-src', (string) $origin );
				\Nino\Feature::allowOrigin( $request, 'script-src-elem', (string) $origin );
			}
		}

		/**
		 *	Whether the response is an html page with a body that is a string
		 *
		 *	@param		array			$request			Current request and its response
		 *
		 *	@return 	bool
		 */
		private static function _page( array $request ): bool {

			$type = $request['/nino/http/response']['header']['Content-Type'] ?? 'text/html';

			return is_string( $request['/nino/http/response']['body'] ?? null ) === true && is_string( $type ) === true && stripos( $type, 'text/html' ) === 0;
		}

		/**
		 *	Whether the response is the workbench's: a page of /_admin, which sends a policy of its own
		 *
		 *	@param		array			$request			Current request and its response
		 *
		 *	@return 	bool
		 */
		private static function _workbench( array $request ): bool {

			foreach( [ $request['/nino/http/request']['uri'] ?? '', $request['/nino/http/response']['uri'] ?? '' ] as $uri )
				if( is_string( $uri ) === true && ( $uri === '/_admin' || str_starts_with( $uri, '/_admin/' ) === true ) )
					return true;

			return false;
		}

		/**
		 *	The attributes of one start tag, name lower-cased and value decoded
		 *	the way a browser decodes it. The first of a name wins, as in a
		 *	browser; a name without a value is ''
		 *
		 *	@param		string		$tag					A start tag, '<script ...>'
		 *
		 *	@return 	array									name => value
		 */
		private static function _attributes( string $tag ): array {

			$inner = (string) preg_replace( '/^<script\b|>$/iD', '', $tag );

			preg_match_all( '/([^\s"\'<>\/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/', $inner, $found, PREG_SET_ORDER );

			$attributes = [];

			foreach( $found as $set ) {

				$name = strtolower( $set[1] );

				if( isset( $attributes[$name] ) === false )
					$attributes[$name] = html_entity_decode( ( $set[2] ?? '' ). ( $set[3] ?? '' ). ( $set[4] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}

			return $attributes;
		}

		/**
		 *	One category's checkbox row, or '' when it is "off" for this
		 *	site (a category the settings did not enable is neither shown
		 *	nor storable)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$category			One of self::CATEGORIES
		 *
		 *	@return 	string
		 */
		private static function _category( array &$appData, string $category ): string {

			$isNecessary = $category === 'necessary';

			if( $isNecessary === false && \Nino\Features::setting( $appData, 'consent', $category, false ) !== true )
				return '';

			return strtr( self::$html['category'], [
				'[[category]]'	=> $category,
				'[[state]]'			=> $isNecessary === true ? self::$html['state'] : '',
				'[[name]]'			=> self::_word( $appData, 'category-'. $category. '/name' ),
				'[[hint]]'			=> self::_word( $appData, 'category-'. $category. '/hint' ),
			] );
		}

		/**
		 *	A word of the banner as it is written into html: the text of the key in the language of the page,
		 *	escaped, every bracket an entity. A key nobody has a text for is nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The part and the name below /feature/consent/
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $name ): string {
			return str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], \Nino\Components::escape( (string) ( \Nino\Html::resolveTextfill( $appData, '/feature/consent/'. $name ) ?? '' ) ) );
		}
	}

}
