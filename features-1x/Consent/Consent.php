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
	 *										three a site uses at all. Everything visitor-facing
	 *										(the banner's markup via [consent]/[consent-settings],
	 *										the gating in the browser) is documented in this
	 *										feature's manual; this class only renders the
	 *										markup, answers whether a category is currently
	 *										allowed and names the hosts of the page's gated
	 *										scripts in the Content-Security-Policy (see
	 *										callbackOutput()). The choice itself is a cookie the
	 *										browser writes (consent.js) - PHP only ever reads it,
	 *										see allowed(). Where Nino's Legal module is there, the
	 *										banner links its privacy policy unless the settings
	 *										name another address, and the policy's section on
	 *										consent carries the button that reopens the choice
	 *										(see callbackLegalSection()).
	 *
	 *										consent.css/consent.js reach the browser the way the
	 *										kernel ships its own Nino.css/Nino.js/Nino.ui.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): the
	 *										manifest's assets add them to the SAME site-wide
	 *										bundle targets the base install's html-header.tpl/
	 *										html-footer.tpl already load on every page ([assets
	 *										/.cache/style.css], [assets /.cache/script.js]), not a
	 *										bundle of this feature's own - so the banner's script
	 *										gates consent-tagged scripts on every page, whether or
	 *										not that page renders [consent] itself. See
	 *										\Nino\Features::registerAssets() and
	 *										docs/development.md, "Assets Are Not Templates".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Consent {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Consent/templates';

		// The fixed category list, in the order the banner shows them.
		// "necessary" is always on and never a setting; the other three are
		// switched on per site under /nino/features/consent/settings - see
		// feature.php
		private const array CATEGORIES = [ 'necessary', 'statistics', 'marketing', 'external' ];

		// The categories a site can turn on or off - CATEGORIES without the
		// one that is always on
		private const array OPTIONAL_CATEGORIES = [ 'statistics', 'marketing', 'external' ];

		// The cookie name where the settings have none: the fourth argument
		// of both reads of the setting (doConsentShortcode() and allowed()),
		// the same literal the manifest defaults to. consent.js keeps a
		// constant of its own for a page without a [consent] banner to read
		// the name from
		private const string DEFAULT_COOKIE_NAME = 'nino_consent';

		/**
		 *	Register the shortcodes - [consent-settings] where the Components module
		 *	does not own it, since the manifest declares it as a component - and hook
		 *	the output phase, where the hosts of the page's gated scripts join the
		 *	policy, and the Legal module's section on consent
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// The banner is no component: a shortcode of the page frame
			\Nino\Html::addShortcode( $appData, 'consent', [ self::class, 'doConsentShortcode' ] );

			\Nino\Feature::shortcode( $appData, 'consent-settings', [ self::class, 'doConsentSettingsShortcode' ] );

			// A placeholder's host has to be in the policy's script-src before the
			// browser may load what consent.js releases - see callbackOutput()
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOutput' ] );

			// The name as a string: the Legal module is a kernel module a project
			// may have switched off - nothing fires the hook then, and nothing is lost
			\Nino\Callbacks::registerCallback( $appData, '/nino/legal/section', [ self::class, 'callbackLegalSection' ] );
		}

		/**
		 *	[consent] - the banner markup: title, text, an optional privacy
		 *	link, one checkbox per category the settings enabled ("necessary"
		 *	always, checked and disabled) and the three actions. Hidden by
		 *	default - consent.js unhides it once it finds no stored choice.
		 *	Every word is a textfill the install unit wrote into the
		 *	project's own text/<locale>.php, so editors keep it in the Text
		 *	panel; nothing here is escaped beyond the settings-derived
		 *	attributes, which are not text fills
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments (none used)
		 *
		 *	@return 	string									The banner markup
		 */
		public static function doConsentShortcode( array &$appData, array $args ): string {

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
				$categories .= self::_categoryMarkup( $appData, $category );

			$policy = ( $policyUrl === '' )
				? ''
				// The leading space is the sentence's, not the link's: a file that
				// starts with a space is a space nobody sees in a diff, and a banner
				// with no policy url must not leave one behind either
				: ' '. str_replace( '[[url]]', htmlspecialchars( $policyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), \Nino\Feature::template( $appData, self::TEMPLATES, 'consent-policy-link' ) );

			/*	The two that carry markup last: str_replace() works through its arrays
				in order, so a token after them would be looked for in what they put in
				as well */
			return str_replace(
				[ '[[cookie]]', '[[days]]', '[[policy]]', '[[categories]]' ],
				[ htmlspecialchars( $cookieName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), (string) $days, $policy, $categories ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'consent-banner' )
			);
		}

		/**
		 *	[consent-settings] - a small button that reopens the banner
		 *	(consent.js binds the click), meant for the footer
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments (class only)
		 *
		 *	@return 	string									The button markup
		 */
		public static function doConsentSettingsShortcode( array &$appData, array $args ): string {

			return str_replace( '[[classes]]', \Nino\Feature::classes( $args ), \Nino\Feature::template( $appData, self::TEMPLATES, 'consent-open' ) );
		}

		/**
		 *	[consent-settings] as the Components module hands it over, the
		 *	Builder's way: every attribute is there, an empty one where nothing
		 *	was written, and doConsentSettingsShortcode() reads them as it reads
		 *	a hand-written call
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments (see \Nino\Modules\Components::dispatch())
		 *
		 *	@return 	string									What doConsentSettingsShortcode() renders for them
		 */
		public static function componentConsentSettings( array &$appData, array $args ): string {

			return self::doConsentSettingsShortcode( $appData, $args );
		}

		/**
		 *	The button that reopens the choice, at the end of the privacy
		 *	policy's section on consent - listener of '/nino/legal/section', which
		 *	the Legal module fires for every section it draws. The section is the
		 *	one this feature's own install unit adds ('consent' of the type
		 *	'privacy'), so the withdrawal the text speaks of is on the page it
		 *	speaks on, and a site that does not run this feature shows no button.
		 *	What is appended is the template of [consent-settings], which the
		 *	kernel renders once more with the rest of the page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$section			(reference) [ 'type', 'id', 'html' ] of the section about to be drawn
		 *
		 *	@return 	void
		 */
		public static function callbackLegalSection( array &$appData, array &$section ): void {

			if( ( $section['type'] ?? null ) !== 'privacy' || ( $section['id'] ?? null ) !== 'consent' || is_string( $section['html'] ?? null ) === false )
				return;

			$section['html'] .= str_replace( '[[classes]]', '', \Nino\Feature::template( $appData, self::TEMPLATES, 'consent-open' ) );
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

			// The banner Nino's base install shipped up to 1.3.x wrote
			// 'accepted' or 'declined' into a cookie of this name - read the way
			// consent.js reads it
			if( $raw === 'accepted' )
				return true;
			if( $raw === 'declined' )
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
			foreach( [ $request['/nino/http/request']['uri'] ?? '', $request['/nino/http/response']['uri'] ?? '' ] as $uri )
				if( is_string( $uri ) === true && ( $uri === '/_admin' || str_starts_with( $uri, '/_admin/' ) === true ) )
					return;

			$body = $request['/nino/http/response']['body'] ?? null;

			if( is_string( $body ) === false || stripos( $body, 'text/plain' ) === false )
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
		private static function _categoryMarkup( array &$appData, string $category ): string {

			$isNecessary = $category === 'necessary';

			if( $isNecessary === false && \Nino\Features::setting( $appData, 'consent', $category, false ) !== true )
				return '';

			return str_replace(
				[ '[[category]]', '[[state]]' ],
				[ $category, ( $isNecessary === true ? ' checked disabled' : '' ) ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'consent-category' )
			);
		}
	}

}
