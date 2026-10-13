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
	 *	Ticker						A row that runs - one cycle, or round and round
	 *										without a seam: a bar of logos, a line of references,
	 *										a strip of announcements.
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
	 *										By default the row runs exactly one cycle, once it has
	 *										been scrolled into view, and stands still on a frame
	 *										identical to its first. data-ticker-loop asks for the
	 *										endless loop; data-ticker-toggle asks for a pause
	 *										button after the row (WCAG 2.2.2), labelled with the
	 *										text fill the install unit brings.
	 *
	 *										There is nothing for PHP to do here: what runs past is
	 *										markup a project's own template already carries
	 *										(<div class="nino-ticker">) plus the two static files
	 *										the manifest lists under assets, so this class has no
	 *										code at all - no shortcode, no route, no settings, no
	 *										state, not even an init(); the unit's one text fill is
	 *										the label of that button. Every timing belongs to the
	 *										row being run, and a static asset could not read a
	 *										site-wide setting anyway (see feature.php).
	 *
	 *										ticker.css/ticker.js reach the browser the way the
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
	class Ticker {
	}

}
