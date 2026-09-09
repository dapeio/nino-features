<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Typewriter		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Typewriter				A container types its lines one after the other: fade
	 *										one in, write it out character by character with the
	 *										cursor riding at the writing head, hold it, take it
	 *										away again, then the next one - looping, or stopping
	 *										on the last line. The lines are the container's own
	 *										<p>s; every timing is a data attribute on that same
	 *										container, and this feature's README.md lists them.
	 *
	 *										There is nothing for PHP to do here: the effect is
	 *										markup a project's own template already carries
	 *										(<div class="nino-typewriter">) plus the two static
	 *										files below, so this class is the two lines that put
	 *										them into the site's bundles - no shortcode, no route,
	 *										no settings, no state. What a typewriter is timed with
	 *										belongs to the element being typed, and a static asset
	 *										could not read a site-wide setting anyway (see
	 *										feature.php).
	 *
	 *										typewriter.css/typewriter.js reach the browser the way
	 *										the kernel ships its own Nino.css/Nino.js/Nino.ui.js
	 *										(see \Nino\AppData::DEFAULTS, '/nino/html/assets'):
	 *										added to the SAME site-wide bundle targets the base
	 *										install's html-header.tpl/html-footer.tpl already load
	 *										on every page ([assets /.cache/style.css], [assets
	 *										/.cache/script.js]), not a bundle of this feature's
	 *										own - a typewriter is written into whatever page wants
	 *										one, and every one of them already loads those two.
	 *										See \Nino\Html::addAsset() and docs/development.md,
	 *										"Assets Are Not Templates".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Typewriter {

		/**
		 *	Ship typewriter.css/typewriter.js into the site's own asset
		 *	bundles - see this class' own docblock for why the site-wide
		 *	targets and not a bundle of this feature's own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
			// resolves against the project root (\Nino\Filesystem::path()'s
			// fallback) - exactly how '/_nino/Nino.css' already does for the
			// kernel's own bundle, so '/features/Typewriter/assets/...' reaches
			// this feature's own copy as long as features/ sits where it does
			// by default (NINO_FEATURES_DIR unmoved); see the README's "Asset
			// bundling" note for the relocated case
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Typewriter/assets/typewriter.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Typewriter/assets/typewriter.js' );
		}
	}

}
