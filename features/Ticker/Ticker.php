<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Ticker			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Ticker						A row that runs and starts again without a seam: a bar
	 *										of logos, a line of references, a strip of
	 *										announcements.
	 *
	 *										The seam is the whole problem. A row that simply
	 *										scrolls runs out and jumps back, and the jump is what
	 *										everybody sees. So ticker.js copies the row's own
	 *										children until they are wider than the box plus one
	 *										length of the row and moves the whole of it by exactly
	 *										one original width - at which point the copy is
	 *										standing where the original stood and the animation can
	 *										start again with nothing moving. The copies are
	 *										aria-hidden: to a screen reader the row is read once,
	 *										which is how many times it is there.
	 *
	 *										There is nothing for PHP to do here: what runs past is
	 *										markup a project's own template already carries
	 *										(<div class="nino-ticker">) plus the two static files
	 *										below, so this class is the two lines that put them
	 *										into the site's bundles - no shortcode, no route, no
	 *										settings, no state. Every timing belongs to the row
	 *										being run, and a static asset could not read a
	 *										site-wide setting anyway (see feature.php).
	 *
	 *										ticker.css/ticker.js reach the browser the way the
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
	class Ticker {

		/**
		 *	Ship ticker.css/ticker.js into the site's own asset bundles - see
		 *	this class' own docblock for why the site-wide targets and not a
		 *	bundle of this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			/*	The virtual '/features/...' prefix resolves against
				\Nino\Features::dir() (\Nino\Filesystem::FEATURES_DIR), so
				'/features/Ticker/assets/...' reaches this feature's own copy
				wherever NINO_FEATURES_DIR put the features directory, and a
				project that moved it has nothing to say in '/nino/html/assets'	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Ticker/assets/ticker.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Ticker/assets/ticker.js' );
		}
	}

}
