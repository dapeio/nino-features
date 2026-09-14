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
	 *										There is nothing for PHP to do here: the bar is one
	 *										empty element a project's own template carries plus
	 *										the two static files below, so this class is the two
	 *										lines that put them into the site's bundles - no
	 *										shortcode, no route, no settings, no state. Where the
	 *										bar sits and what it measures belong to the template it
	 *										is written into, and a static asset could not read a
	 *										site-wide setting anyway (see feature.php).
	 *
	 *										progress.css/progress.js reach the browser the way the
	 *										kernel ships its own Nino.css/Nino.js (see
	 *										\Nino\AppData::DEFAULTS, '/nino/html/assets'): added
	 *										to the SAME site-wide bundle targets the base
	 *										install's html-header.tpl/html-footer.tpl already load
	 *										on every page.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Progress {

		/**
		 *	Ship progress.css/progress.js into the site's own asset bundles -
		 *	see this class' own docblock for why the site-wide targets and not a
		 *	bundle of this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			/*	A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
				resolves against the project root (\Nino\Filesystem::path()'s
				fallback) - exactly how '/_nino/Nino.css' already does for the
				kernel's own bundle; see the README's "Asset bundling" note for a
				project that moved features/ with NINO_FEATURES_DIR	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Progress/assets/progress.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Progress/assets/progress.js' );
		}
	}

}
