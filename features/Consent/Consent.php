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
	 *										feature's own README.md; this class only renders the
	 *										markup and answers whether a category is currently
	 *										allowed. The choice itself is a cookie the browser
	 *										writes (consent.js) - PHP only ever reads it, see
	 *										allowed().
	 *
	 *										consent.css/consent.js reach the browser the way the
	 *										kernel ships its own Nino.css/Nino.js/Nino.ui.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): added to
	 *										the SAME site-wide bundle targets the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page ([assets /.cache/style.css], [assets
	 *										/.cache/script.js]), not a bundle of this feature's
	 *										own - so the banner's script gates consent-tagged
	 *										scripts on every page, whether or not that page
	 *										renders [consent] itself. See \Nino\Html::addAsset()
	 *										and docs/development.md, "Assets Are Not Templates".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Consent {

		// The fixed category list, in the order the banner shows them.
		// "necessary" is always on and never a setting; the other three are
		// switched on per site under /nino/features/consent/settings - see
		// feature.php
		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Consent/templates';
		private const array CATEGORIES = [ 'necessary', 'statistics', 'marketing', 'external' ];

		// The categories a site can turn on or off - CATEGORIES without the
		// one that is always on
		private const array OPTIONAL_CATEGORIES = [ 'statistics', 'marketing', 'external' ];

		// What consent.js falls back to reading/writing when no [consent]
		// banner is on the current page to carry the project's own
		// cookieName setting (see doConsentShortcode()'s data-consent-cookie
		// attribute) - the same literal the setting itself defaults to, so
		// a project that never touched the setting is covered either way
		private const string DEFAULT_COOKIE_NAME = 'nino_consent';

		/**
		 *	Register the two shortcodes and ship consent.css/consent.js into
		 *	the site's own asset bundles - see this class' own docblock for
		 *	why the site-wide targets and not a bundle of this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'consent', [ self::class, 'doConsentShortcode' ] );
			\Nino\Html::addShortcode( $appData, 'consent-settings', [ self::class, 'doConsentSettingsShortcode' ] );

			// A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
			// resolves against the project root (\Nino\Filesystem::path()'s
			// fallback) - exactly how '/_nino/Nino.css' already does for the
			// kernel's own bundle, so '/features/Consent/assets/...' reaches
			// this feature's own copy as long as features/ sits where it
			// does by default (NINO_FEATURES_DIR unmoved); see the README's
			// "Page cache and asset bundling" note for the relocated case
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Consent/assets/consent.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Consent/assets/consent.js' );
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

			$categories = '';
			foreach( self::CATEGORIES as $category )
				$categories .= self::_categoryMarkup( $appData, $category );

			$policy = ( $policyUrl === '' )
				? ''
				// The leading space is the sentence's, not the link's: a file that
				// starts with a space is a space nobody sees in a diff, and a banner
				// with no policy url must not leave one behind either
				: ' '. str_replace( '[[url]]', htmlspecialchars( $policyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), self::template( $appData, 'consent-policy-link' ) );

			/*	The two that carry markup last: str_replace() works through its arrays
				in order, so a token after them would be looked for in what they put in
				as well */
			return str_replace(
				[ '[[cookie]]', '[[days]]', '[[policy]]', '[[categories]]' ],
				[ htmlspecialchars( $cookieName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), (string) $days, $policy, $categories ],
				self::template( $appData, 'consent-banner' )
			);
		}

		/**
		 *	[consent-settings] - a small button that reopens the banner
		 *	(consent.js binds the click), meant for the footer
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments (none used)
		 *
		 *	@return 	string									The button markup
		 */
		public static function doConsentSettingsShortcode( array &$appData, array $args ): string {

			return self::template( $appData, 'consent-open' );
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

			// is_string() rather than a cast, on both: a cookie is sent by
			// the client, and 'nino_consent[]=x' parses to an array. Cast,
			// that raises "Array to string conversion" - a level
			// \Nino\Runtime treats as fatal, ie. an unauthenticated 500 on
			// every page of the site from a cookie anybody can set. The
			// setting is read the same way, since a hand-edited config.php
			// is no more typed than a request is
			$setting		= \Nino\Features::setting( $appData, 'consent', 'cookieName', self::DEFAULT_COOKIE_NAME );
			$cookieName	= is_string( $setting ) === true ? $setting : self::DEFAULT_COOKIE_NAME;
			$raw				= is_string( $_COOKIE[ $cookieName ] ?? null ) === true ? $_COOKIE[ $cookieName ] : '';

			if( $raw === '' )
				return false;

			// The base install's own banner wrote 'accepted' or 'declined'
			// into a cookie of this name - read the way consent.js reads it
			if( $raw === 'accepted' )
				return true;
			if( $raw === 'declined' )
				return false;

			$allowed = array_map( 'trim', explode( ',', $raw ) );

			return in_array( $category, $allowed, true );
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
				self::template( $appData, 'consent-category' )
			);
		}
		/**
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway:
			// the one thing this could otherwise be turned into is a read of
			// something outside the feature
			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}

}
