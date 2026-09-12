<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Modeswitch	see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modeswitch				Three states, one control: read the site light, read it
	 *										dark, or read it the way the system asks - which is
	 *										where it starts, and the only one of the three that
	 *										is not a decision.
	 *
	 *										Dark is not invented here. Every Nino project's
	 *										assets/theme.css already publishes a dark palette
	 *										beside its light one, under two selectors: a
	 *										prefers-color-scheme media query for the reader who has
	 *										said nothing, and :root[data-nino-mode="dark"] for the
	 *										one who has. This feature is the control that
	 *										writes that attribute, and that is the whole of it -
	 *										which is why the switch works on a project that never
	 *										installed the Design feature.
	 *
	 *										The three states are therefore two values and an
	 *										absence: data-nino-mode="light", ="dark", and no
	 *										attribute at all. The stylesheet is written so the
	 *										absence means "follow the system", so "system" has
	 *										nothing to write and cannot go stale when the reader
	 *										changes their system setting with the page open.
	 *
	 *										Nothing reaches the server. The choice lives in the
	 *										reader's own browser (localStorage), so there is no
	 *										cookie to declare, no consent to ask for, and a cached
	 *										page is as switchable as a fresh one - the attribute is
	 *										written after the html arrives, not into it.
	 *
	 *										modeswitch.css/modeswitch.js reach the browser the way
	 *										the kernel ships its own Nino.css/Nino.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): added to
	 *										the SAME site-wide bundles the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page. A switch is written into one template and read on
	 *										all of them, so a bundle of its own would be a second
	 *										request for two small files every page needs anyway.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Modeswitch {

		/*	The three the switch offers, in the order it draws them. "system" in
			the middle on purpose: it is the one between the two, both as a
			meaning and on screen, and a reader looking for "back to normal"
			finds it where the middle of a three-way switch is.

			The value is what goes into data-nino-mode - and "system" has none,
			because the stylesheet's own rule for a reader who chose nothing is
			the @media query, which only applies while no attribute is set. So
			the middle position removes the attribute rather than writing a third
			value, and there is no state that has to be kept in step with the
			system setting	*/
		public const array MODES = [ 'light', 'system', 'dark' ];

		/*	Inline rather than files. Three icons of a dozen paths each are
			smaller than the request that would fetch them, and a switch that
			paints its icons one request later than its buttons is a switch that
			moves under the pointer. currentColor throughout, so they take the
			colour of whatever surface the switch was written onto	*/
		private const array ICONS = [
			'light'		=> '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.6v2.6M12 18.8v2.6M2.6 12h2.6M18.8 12h2.6M5.4 5.4l1.8 1.8M16.8 16.8l1.8 1.8M18.6 5.4l-1.8 1.8M7.2 16.8l-1.8 1.8"/>',
			'system'	=> '<rect x="2.8" y="4.2" width="18.4" height="12.4" rx="1.8"/><path d="M8.4 20.2h7.2M12 16.6v3.6"/>',
			'dark'		=> '<path d="M20.4 14.6A8.6 8.6 0 0 1 9.4 3.6a8.6 8.6 0 1 0 11 11Z"/>',
		];

		/**
		 *	Register the shortcode and ship the two static files - see this
		 *	class' own docblock for why the site-wide bundles and not one of
		 *	this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'mode-switch', [ self::class, 'doShortcode' ] );

			/*	A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
				resolves against the project root (\Nino\Filesystem::path()'s
				fallback) - the same way '/_nino/Nino.css' already does for the
				kernel's own bundle, so '/features/Modeswitch/assets/...' reaches
				this feature's own copy as long as features/ sits where it does by
				default (NINO_FEATURES_DIR unmoved); see the README's "Asset
				bundling" note for the relocated case	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Modeswitch/assets/modeswitch.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Modeswitch/assets/modeswitch.js' );
		}

		/**
		 *	[mode-switch] - the control itself.
		 *
		 *	Three real <button>s in a group rather than a select or a single
		 *	toggle: a toggle can hold two states and there are three, and a
		 *	select hides the one thing this control is for - which of them is
		 *	on. aria-pressed rather than a tablist, because these switch how
		 *	the same page is painted, not which page is shown.
		 *
		 *	Every word is a text fill the install unit wrote into the project's
		 *	own text/<locale>.php, so an editor changes it in the Text panel and
		 *	no locale has to be added here.
		 *
		 *	It renders hidden, and pressed on nothing: which one is on is a
		 *	question only the browser can answer, and a server that guessed would
		 *	guess wrong for every reader whose choice is not the default.
		 *	modeswitch.js unhides and paints it the moment it runs, which is
		 *	before the reader can reach it - and where it never runs, a control
		 *	that cannot work is better not shown than shown dead.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments: "icons" for icons only
		 *
		 *	@return 	string									The switch markup
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			/*	The one thing a project varies, and it belongs to the place the
				switch is written rather than to the site: a header bar with no room
				for three words is not the same decision as a footer.

				A bare flag rather than key="value", because that is the form
				\Nino\Html's shortcode parser reads without quotes - [mode-switch
				icons] is what somebody types into a template, and
				[mode-switch labels="off"] is what they get wrong	*/
			$labels = in_array( 'icons', $args, true ) === false;

			$buttons = '';

			foreach( self::MODES as $mode )
				$buttons .= self::_button( $mode, $labels );

			/*	Hidden until modeswitch.js unhides it, the way [consent] is. A
				switch nothing wires up is three identical buttons that do nothing -
				worse than no switch at all, because it looks like the site is broken
				rather than like it has no such control. Without JavaScript the reader
				keeps the mode their system asks for, which is what they had before
				this feature existed	*/
			return '<div class="nino-modeswitch'. ( $labels === true ? '' : ' nino-modeswitch--icons' ). '" hidden'
				. ' role="group" aria-label="[[/modeswitch/label]]">'
				. $buttons
				. '</div>';
		}

		/**
		 *	One of the three
		 *
		 *	@param		string		$mode					A member of MODES
		 *	@param		bool			$labels				Whether the word goes beside the icon
		 *
		 *	@return 	string
		 */
		private static function _button( string $mode, bool $labels ): string {

			$fill = '[[/modeswitch/'. $mode. ']]';

			/*	The word is in the markup either way, and hidden with a class
				rather than left out: a switch whose buttons are three unlabelled
				icons is one a screen reader cannot read at all, and
				aria-label would then have to repeat what the fill already says	*/
			$name = '<span class="nino-modeswitch-name'. ( $labels === true ? '' : ' nino-modeswitch-name--hidden' ). '">'. $fill. '</span>';

			return '<button type="button" class="nino-modeswitch-btn" data-mode="'. $mode. '"'
				. ' aria-pressed="false" title="'. $fill. '">'
				. '<svg class="nino-modeswitch-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
				. ' fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"'
				. ' stroke-linejoin="round" aria-hidden="true" focusable="false">'
				. self::ICONS[$mode]
				. '</svg>'
				. $name
				. '</button>';
		}
	}

}
