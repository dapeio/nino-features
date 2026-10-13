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
	 *										There is nothing for PHP to do, and the manifest does
	 *										all of it: its assets put the stylesheet and the script
	 *										into the site's own bundles while the feature is
	 *										listed in /nino/modules - the same targets the base
	 *										install's html-header.tpl/html-footer.tpl already load
	 *										on every page, since a lightbox is opened from
	 *										whatever page has one (\Nino\Features::registerAssets()
	 *										and docs/features.md, "Assets"). The class is the
	 *										module the feature is listed by and has no method; the
	 *										kernel calls init() of the classes that have one.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Lightbox {
	}

}
