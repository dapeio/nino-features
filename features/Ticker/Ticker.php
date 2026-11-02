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
	 *										A project writes the row as markup by hand
	 *										(<div class="nino-ticker">) or as the component the
	 *										manifest declares: [ticker]...[/ticker] holds the
	 *										components of a section - a title, a text, a picture -
	 *										and draws the box and the track around them, so that
	 *										what stands between the tags is what runs past. The
	 *										Builder offers it as any component that holds
	 *										components, and knows nothing of it by name.
	 *
	 *										The component writes a data-ticker-* attribute only
	 *										where the call differs from what ticker.js does when
	 *										it finds none: the speed it is given, the other
	 *										direction, the endless loop, the pause button, a row
	 *										that keeps running under the pointer. A call that says
	 *										nothing is the bare box and track, which is what a row
	 *										written by hand is. There are no routes, no settings and
	 *										no state, and not even an init(): the manifest
	 *										declares the component and the Components module
	 *										finds componentTicker() by its name. Every timing
	 *										belongs to the row being run, and a static asset could
	 *										not read a site-wide setting anyway (see feature.php);
	 *										the unit's one text fill is the label of the pause
	 *										button.
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

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Ticker/templates';

		// The speed ticker.js runs a row at where it finds no data-ticker-speed
		// (its SPEED_DEFAULT), which is also the default of the component's
		// attribute: a call at that speed writes no attribute
		public const int SPEED_DEFAULT = 40;

		// The label of the pause button, as the install unit's text fill. A
		// fill stays in the attribute and the kernel resolves it when the result
		// is rendered; it is the same fill the markup of a row written by hand
		// names (see feature.php)
		public const string TOGGLE = '[[/feature/ticker/pause/label]]';

		/**
		 *	The two ways a row runs, as the options of the direction of the
		 *	component: the manifest names this method, and the schema that is
		 *	issued carries the words, so that a tool draws them in the language
		 *	of whoever works with it instead of the two values.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		The attribute that is asked for
		 *
		 *	@return 	array										value => locale => label
		 */
		public static function directions( array &$appData, string $attribute ): array {

			return [
				'left'	=> [ 'en_US' => 'To the left', 'de_DE' => 'Nach links' ],
				'right'	=> [ 'en_US' => 'To the right', 'de_DE' => 'Nach rechts' ],
			];
		}

		/**
		 *	[ticker]...[/ticker]: a component that holds components. What stands
		 *	between its tags is the calls of the components of a section, and this
		 *	draws the row around them: the box ticker.js runs and the track that
		 *	holds what runs past, each call being one item of the track.
		 *
		 *	The content is $args['value'] and it is not rendered here - the engine
		 *	renders the output of a shortcode again (\Nino\Html::_doShortcode()), so
		 *	the components in the row are rendered as soon as this returns, which is
		 *	also why the content goes into the template last: it holds calls, and a
		 *	token after it would be looked for in them as well. dispatch() renders
		 *	nothing for a row with nothing between its tags.
		 *
		 *	A data attribute is written only where the call differs from what
		 *	ticker.js and ticker.css do without it: another speed than the default,
		 *	the direction right, the loop, the pause button, and a row that does not
		 *	stop under the pointer (pause="0").
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by Components::dispatch()
		 *
		 *	@return 	string								The row
		 */
		public static function componentTicker( array &$appData, array $args ): string {

			$data = [
				'speed'			=> ( (int) $args['speed'] === self::SPEED_DEFAULT ? '' : ' data-ticker-speed="'. (int) $args['speed']. '"' ),
				'direction'	=> ( $args['direction'] === 'right' ? ' data-ticker-direction="right"' : '' ),
				'loop'			=> ( $args['loop'] === '1' ? ' data-ticker-loop="1"' : '' ),
				'toggle'		=> ( $args['toggle'] === '1' ? ' data-ticker-toggle="'. self::TOGGLE. '"' : '' ),
				'pause'			=> ( $args['pause'] === '0' ? ' data-ticker-pause="off"' : '' ),
			];

			return str_replace(
				[ '[[classes]]', '[[speed]]', '[[direction]]', '[[loop]]', '[[toggle]]', '[[pause]]', '[[content]]' ],
				[ \Nino\Feature::classes( $args ), $data['speed'], $data['direction'], $data['loop'], $data['toggle'], $data['pause'], $args['value'] ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'ticker' )
			);
		}
	}

}
