<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Typewriter		see _nino/Nino/Modules/Modules.php for the
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
	 *										away again, then the next one - stopping on the last
	 *										line, or looping where the container asks for it. The
	 *										lines are the container's own <p>s; every timing is a
	 *										data attribute on that same container, and the
	 *										manual lists them.
	 *										A container can also ask for a pause button after it
	 *										(data-typewriter-toggle, WCAG 2.2.2), labelled with
	 *										the text fill the install unit brings.
	 *
	 *										There is nothing for PHP to do here: the effect is
	 *										markup a project's own template already carries
	 *										(<div class="nino-typewriter">) plus the two static
	 *										files the manifest lists under assets, so this class
	 *										has no code at all - no shortcode, no route, no
	 *										settings, no state, not even an init(); the unit's one
	 *										text fill is the label of that button. What a
	 *										typewriter is timed with belongs to the element being
	 *										typed, and a static asset could not read a site-wide
	 *										setting anyway (see feature.php).
	 *
	 *										typewriter.css/typewriter.js reach the browser the way
	 *										the kernel ships its own Nino.css/Nino.js/Nino.ui.js
	 *										(see \Nino\AppData::DEFAULTS, '/nino/html/assets'):
	 *										the kernel adds them, from the manifest's assets
	 *										(\Nino\Features::registerAssets()), to the SAME
	 *										site-wide bundle targets the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page ([assets /.cache/style.css], [assets
	 *										/.cache/script.js]), not a bundle of this feature's
	 *										own - a typewriter is written into whatever page wants
	 *										one, and every one of them already loads those two.
	 *										See docs/development.md, "Assets Are Not Templates".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Typewriter {
	}

}
