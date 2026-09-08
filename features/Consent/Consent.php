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

			$policyLink = ( $policyUrl === '' )
				? ''
				: ' <a href="'. htmlspecialchars( $policyUrl, ENT_QUOTES ). '" class="nino-consent-link">[[/consent/policy-label]]</a>';

			return '<div class="nino-consent" hidden'
				. ' data-consent-cookie="'. htmlspecialchars( $cookieName, ENT_QUOTES ). '"'
				. ' data-consent-days="'. $days. '">'
				. '<div class="nino-consent-content">'
				. '<p class="nino-consent-title">[[/consent/title]]</p>'
				. '<p class="nino-consent-text">[[/consent/text]]'. $policyLink. '</p>'
				. '<div class="nino-consent-categories">'. $categories. '</div>'
				. '</div>'
				. '<div class="nino-consent-actions">'
				. '<button type="button" class="nino-consent-btn nino-consent-btn--primary" data-consent-action="accept-all">[[/consent/accept-all]]</button>'
				. '<button type="button" class="nino-consent-btn" data-consent-action="necessary-only">[[/consent/necessary-only]]</button>'
				. '<button type="button" class="nino-consent-btn" data-consent-action="save">[[/consent/save]]</button>'
				. '</div>'
				. '</div>';
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

			return '<button type="button" class="nino-consent-open">[[/consent/open]]</button>';
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
			$raw				= (string) ( $_COOKIE[ $cookieName ] ?? '' );

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

			return '<label class="nino-consent-category">'
				. '<input type="checkbox" data-consent-category="'. $category. '"'. ( $isNecessary === true ? ' checked disabled' : '' ). '>'
				. '<span class="nino-consent-category-name">[[/consent/category/'. $category. ']]</span>'
				. '<span class="nino-consent-category-hint">[[/consent/category/'. $category. '/hint]]</span>'
				. '</label>';
		}
	}

}
