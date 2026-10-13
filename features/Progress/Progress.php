<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Progress		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Progress					How far through a long text the reader is, as a bar
	 *										across the top of it.
	 *
	 *										What is measured is a choice worth making: the whole
	 *										page counts the footer as part of the article, so
	 *										"finished" arrives after the last paragraph rather
	 *										than at it. data-progress-of="#article" measures that
	 *										one element instead, and the bar is full when the text
	 *										is.
	 *
	 *										The bar is one empty element and the two static files
	 *										the manifest lists under assets do the rest, so what
	 *										PHP writes is that element, with the two data
	 *										attributes progress.js reads: the component the
	 *										Builder places and the shortcode a page writes by
	 *										hand, which are one call and one markup. A project's
	 *										own template may still carry the div by hand. No
	 *										route, no settings, no state: where the bar sits and
	 *										what it measures belong to the one place it is
	 *										written, and a static asset could not read a
	 *										site-wide setting anyway (see feature.php).
	 *
	 *										Every value is escaped, and a bracket in it is written
	 *										as a character reference: the markup is rendered once
	 *										more, and a selector, a label or a class somebody typed
	 *										must not open a fill or a shortcode there.
	 *
	 *										progress.css/progress.js reach the browser the way the
	 *										kernel ships its own Nino.css/Nino.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): the
	 *										kernel adds them, from the manifest's assets
	 *										(\Nino\Features::registerAssets()), to the SAME
	 *										site-wide bundle targets the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Progress {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Progress/templates';

		// What a bar measures where the call names nothing: the element that holds
		// the article, and the default of the component's "of" in feature.php
		public const string OF_DEFAULT = '#article';

		/**
		 *	Register the shortcode where no component is. The stylesheet and the
		 *	script reach the site's bundles through the manifest's assets - see
		 *	this class' own docblock
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// false: the manifest's component owns the name and renders it through doShortcode()
			\Nino\Feature::shortcode( $appData, 'progress', [ self::class, 'doShortcode' ] );
		}

		/**
		 *	[progress of="#article" label="Lesefortschritt"] - the element the
		 *	browser turns into the bar.
		 *
		 *	One empty div with the two attributes progress.js reads. A call without
		 *	"of" measures OF_DEFAULT, which is what the component's default fills in
		 *	for it; an "of" that is there and empty measures the whole page. A
		 *	"label" that is empty hides the bar from a screen reader rather than
		 *	announcing it unnamed.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes (see feature.php's manual)
		 *
		 *	@return 	string								The element
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$of = trim( (string) ( $args['of'] ?? self::OF_DEFAULT ) );
			$label = trim( (string) ( $args['label'] ?? '' ) );

			return str_replace(
				[ '[[classes]]', '[[of]]', '[[label]]' ],
				[ \Nino\Feature::classes( $args ), \Nino\Modules\Components::escape( $of ), \Nino\Modules\Components::escape( $label ) ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'progress' )
			);
		}
	}

}
