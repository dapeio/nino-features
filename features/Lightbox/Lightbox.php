<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Lightbox		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Lightbox					A link that points at an image opens it full screen
	 *										instead of navigating away, with every other link of
	 *										its group as the rest of the set: arrows and swipe
	 *										between them, the caption underneath, Escape and the
	 *										backdrop to close, and the focus put back on the link
	 *										that opened it.
	 *
	 *										What it opens is markup the page already carries - a
	 *										gallery's thumbnails, a figure in an article, a link
	 *										an editor wrote by hand. A link joins in by carrying
	 *										data-lightbox; the value is the group, so two
	 *										galleries on one page stay two sets. Nothing else is
	 *										required of the page, and nothing here knows where
	 *										the images came from.
	 *
	 *										There is nothing for PHP to do, so this class is the
	 *										two lines that put the stylesheet and the script into
	 *										the site's own bundles - the same targets the base
	 *										install's html-header.tpl/html-footer.tpl already load
	 *										on every page, since a lightbox is opened from
	 *										whatever page has one. See \Nino\Html::addAsset() and
	 *										docs/development.md, "Assets Are Not Templates".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Lightbox {

		/**
		 *	Ship lightbox.css/lightbox.js into the site's own asset bundles
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
			// resolves against the project root, so '/features/Lightbox/...'
			// reaches this feature's own copy as long as features/ sits where
			// it does by default (NINO_FEATURES_DIR unmoved)
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Lightbox/assets/lightbox.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Lightbox/assets/lightbox.js' );
		}
	}

}
