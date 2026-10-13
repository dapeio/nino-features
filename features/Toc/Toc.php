<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Toc					see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Toc								A long page's own headings as a list, and an anchor on
	 *										each of them.
	 *
	 *										Built in the browser rather than on the server, and
	 *										that is the decision worth stating. A Nino page is
	 *										assembled out of a template, sections, shortcodes and
	 *										elements, and what the headings finally are is only
	 *										settled once all of that has run - a [posts] list adds
	 *										some, a [template] include brings its own, and an
	 *										Elements loop makes one per entry. The finished page is
	 *										the only place where the answer is complete, and the
	 *										browser is standing in it.
	 *
	 *										So what this shortcode writes is a <nav> with its
	 *										heading and an empty list, and toc.js fills it from
	 *										the headings that actually came out. A page whose
	 *										script never runs keeps a nav that says nothing, which
	 *										is why the whole thing is written hidden: a table of
	 *										contents with no contents is worse than none.
	 *
	 *										The anchors are the same answer to the same question.
	 *										An id has to be there before a link can point at it,
	 *										and a heading a project wrote by hand usually has
	 *										none - so toc.js gives every heading it lists one made
	 *										from its own words, and leaves an id the page already
	 *										had exactly as it was, because that one may be linked
	 *										to from somewhere else already.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Toc {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Toc/templates';

		// The headings a list may be built from. h1 is the page itself - a
		// page is not a section of itself - and h4 and below are inside a
		// section rather than one of them
		public const array LEVELS = [ 2, 3 ];

		/**
		 *	Register the shortcode where no component is. The stylesheet and the
		 *	script reach the site's bundles through the manifest's assets
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// false: the manifest's component owns the name and renders it through doShortcode()
			\Nino\Feature::shortcode( $appData, 'toc', [ self::class, 'doShortcode' ] );
		}

		/**
		 *	[toc] - the nav the browser fills.
		 *
		 *	What is written here is the frame: a heading and an empty list,
		 *	hidden. toc.js finds the headings, fills the list and unhides it -
		 *	a page whose script never runs keeps a nav that says nothing, and
		 *	a table of contents with no contents is worse than none.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes (see feature.php's manual)
		 *
		 *	@return 	string								The frame
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			/*	The heading over the list is editor text that may be a textfill, so
				it is rendered first and escaped after; where the shortcode says
				nothing, the fill the install unit wrote is left for the kernel	*/
			$title = trim( (string) ( $args['title'] ?? '' ) );
			$title = $title === ''
				? '[[/feature/toc/list/title]]'
				: $safe( \Nino\Html::renderHtml( $appData, $title ) );

			/*	Which element the headings are taken from. A selector rather than
				an id, because "the article" is #content on one project and
				main > .nino-section on the next - and it is only ever read by
				querySelector in the visitor's own browser, on the visitor's own page	*/
			$within = trim( (string) ( $args['within'] ?? '' ) );

			return str_replace(
				[ '[[classes]]', '[[levels]]', '[[within]]', '[[anchors]]', '[[title]]' ],
				[
					\Nino\Feature::classes( $args ),
					implode( ',', self::levels( $args ) ),
					$safe( $within ),
					( self::anchors( $appData ) === true ? '1' : '0' ),
					$title,
				],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'toc' )
			);
		}

		/**
		 *	Which heading levels the list is built from, always in order: a
		 *	list that reads h3 before h2 is not an outline of anything
		 *
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	array									A non-empty subset of LEVELS, ascending
		 */
		public static function levels( array $args ): array {

			$given = trim( (string) ( $args['levels'] ?? '' ) );

			if( $given === '' )
				return self::LEVELS;

			$wanted = array_map( 'intval', array_map( 'trim', explode( ',', $given ) ) );
			$levels = array_values( array_filter( self::LEVELS, static fn( int $level ): bool => in_array( $level, $wanted, true ) ) );

			return $levels === [] ? self::LEVELS : $levels;
		}

		/**
		 *	Whether every heading on a page with a list gets a link of its own,
		 *	as the settings have it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function anchors( array &$appData ): bool {
			return \Nino\Features::setting( $appData, 'toc', 'anchors', true ) === true;
		}
	}

}
