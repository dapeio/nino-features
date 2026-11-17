<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Themes\Compiler	see _nino/Nino/Modules/Modules.php for the
 *													package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The compiler: a mix, the brand and the css of the developer become the one stylesheet of the layer
	 *										nino.theme and the variants of the components
	 *
	 *										The stylesheet is written in this order, and a stylesheet is all there is:
	 *
	 *											1. a head with the sha256 of everything after it - the compiler replaces only what it wrote
	 *											2. the @font-face of the fonts of the brand that are folders of public/fonts/
	 *											3. :root with the brand, and only of what the brand names - a colour or a tone that is not neutral makes the colours, the eight pairs of
	 *											   the palette and the colour written on each; a mode and each font are written where they are named
	 *											4. :root with the page: the step of each knob of the page, the density for small screens as a query
	 *											5. :root with the knobs of every group: the step of each ladder, the part's or the group's own
	 *											6. the css of the part of each group
	 *											7. the own css of the developer, as it was written
	 *
	 *										The parts lie on the standard parts (the layer nino.components) and not in their place, so
	 *										every fixed attribute is drawn in every theme. A part that does not hold - it rests - is left
	 *										out and its group has the standard part, with the reason in the plan.
	 *
	 *										The own css is not read: a theme author's text goes to the end of the file as it is. What the
	 *										compiler keeps of it is the shell, which is the bundle's: a source whose braces do not
	 *										balance is repaired where the bundle is made, and stays in its layer.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Compiler {

		// The first line of what the compiler wrote: its mark and the sha256 of the text after that line
		private const string HEAD = '/* nino-themes %s - written by the feature Themes at every build; a rule of your own belongs in the own css of the theme */';
		private const string HEAD_PATTERN = '/^\/\* nino-themes ([0-9a-f]{64}) [^\n]*\*\/\n/D';

		// The pairs of the palette that go into :root, by the token they set
		private const array PAIRS = [
			'--brand-ground-base' => 'base', '--brand-ground-alt' => 'alt', '--brand-ground-tint' => 'tint', '--brand-ink-base' => 'ink',
			'--brand-ink-quiet-base' => 'quiet', '--brand-line-base' => 'line', '--brand-accent-base' => 'accent', '--brand-on-accent-base' => 'on',
		];

		/**
		 *	What a mix and a brand come to, before anything is written: the values of the brand, for the page and for each group
		 *	the part that stands, why it rests where it does and the step of each knob, the variants and the standard of each
		 *	component
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$mix					As Mix::clean() answers it
		 *	@param		array			$brand				As Brand::clean() answers it
		 *
		 *	@return 	array											'brand' => key => value, 'page' => [...], 'groups' => group => [...]
		 */
		public static function plan( array &$appData, array $mix, array $brand ): array {

			$parts = (array) ( $mix['parts'] ?? [] );
			$page = self::_part( $appData, 'page', (array) ( $parts['page'] ?? [] ) );
			$page['knobs'] = [];

			foreach( $page['part']['knobs'] ?? [] as $name => $knob )
				$page['knobs'][$name] = $knob + [ 'step' => self::_step( $page['values'][$name] ?? 0 ) ];

			$plan = [ 'brand' => Brand::effective( $brand, $page['brand'] ), 'named' => Brand::named( $brand, $page['brand'] ), 'notes' => [], 'page' => $page, 'groups' => [] ];
			$plan['palette'] = Brand::colours( $plan['brand'], $plan['named'] );

			foreach( \Nino\Components::groups( $appData ) as $group => $declared )
				$plan['groups'][$group] = self::_group( $appData, (string) $group, $declared, (array) ( $parts[$group] ?? [] ) );

			ksort( $plan['groups'] );

			return $plan;
		}

		/**
		 *	The stylesheet and the variants of a mix
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$mix					As Mix::clean() answers it
		 *	@param		array			$brand				As Brand::clean() answers it
		 *
		 *	@return 	array											'css' => the stylesheet, 'variants' => component => [ the variants a part adds, the standard, the label of each variant it adds: variant => text or locale => text ], 'plan' => see plan(), 'gone' => the fonts the brand names that have no
		 *																folder, by the key of the brand - the stylesheet has the stack of the system for them, 'faults' => what the own css leaves open, as
		 *																balance() names it
		 */
		public static function compile( array &$appData, array $mix, array $brand ): array {

			$plan = self::plan( $appData, $mix, $brand );
			$fonts = Brand::fonts( $appData );
			$values = $plan['brand'];
			$blocks = [];
			$variants = [];
			// What the brand names is written, and what it does not name is the reset's: a theme that says nothing of colour, mode and
			// fonts draws the site as it is drawn with no theme. The fonts: a folder that is not there any more is the stack of the system
			$named = $plan['named'];
			$lines = [];
			$faces = [];
			$gone = [];

			foreach( [ 'title', 'text', 'button' ] as $role ) {

				if( in_array( 'font-'. $role, $named, true ) === false )
					continue;

				$slug = $values['font-'. $role];

				if( isset( $fonts[$slug] ) === false ) {
					$plan['notes'][] = 'the font "'. $slug. '" is no font of this project - the stack of the system stands for the '. $role;
					$gone['font-'. $role] = $slug;
					$slug = 'system-sans';
				}

				$lines['--brand-font-'. $role] = $fonts[$slug]['stack'];

				if( $fonts[$slug]['system'] === false && isset( $faces[$slug] ) === false )
					foreach( $fonts[$slug]['faces'] as [ $weight, $style, $file ] )
						$faces[$slug][] = '@font-face { font-family: "'. $fonts[$slug]['name']. '"; font-style: '. $style. '; font-weight: '. $weight. '; font-display: swap; src: url("[[/nino/public]]/fonts/'. $slug. '/'. $file. '") format("woff2"); }';
			}

			foreach( $faces as $list )
				$blocks[] = implode( "\n", $list );

			if( in_array( 'mode', $named, true ) === true )
				$lines = [ '--brand-scheme' => [ 'auto' => 'light dark', 'light' => 'light', 'dark' => 'dark' ][$values['mode']] ] + $lines;

			if( $plan['palette'] === true ) {

				$palette = Palette::make( $values['color-1'], $values['color-2'], $values['tone'] );
				$lines += [ '--brand-color-1' => $values['color-1'], '--brand-color-2' => $values['color-2'], '--brand-on-color-1' => $palette['on-color-1'], '--brand-on-color-2' => $palette['on-color-2'] ];

				foreach( self::PAIRS as $token => $role )
					$lines[$token] = 'light-dark( '. $palette['light'][$role]. ', '. $palette['dark'][$role]. ' )';
			}

			if( $lines !== [] )
				$blocks[] = self::_root( $lines );

			// The page: the step each knob is on; what a knob for small screens sets is a query of its own. It is written down to a
			// width and not up from one, which is a departure from the line the stylesheets of the catalogue keep (a width from
			// min-width): the middle value of a token such a knob sets belongs to another knob or to Nino.css, and a query up from
			// 768 px would have to restate it - the density of the Nino theme moves --page-text-6, which only the reset has
			$tokens = [];
			$small = [];

			foreach( $plan['page']['knobs'] as $knob )
				foreach( $knob['ladder'][$knob['step'] + 2] as $token => $value )
					if( $knob['small'] === true )
						$small[$token] = $value;
					else
						$tokens[$token] = $value;

			if( $tokens !== [] )
				$blocks[] = self::_root( $tokens );

			// The reset's rule for a visitor who asked for less motion sets --page-motion in the layer of the reset, and this file is a later
			// layer: where a knob of the page sets the duration the rule is written once more after it, so that the request stays what it is. The
			// reset has no other rule of that kind - its @supports sets the roles of a dark ground, which no part of a theme sets
			if( isset( $tokens['--page-motion'] ) === true || isset( $small['--page-motion'] ) === true )
				$blocks[] = "@media (prefers-reduced-motion: reduce) {\n\t:root {\n\t\t--page-motion: 0s;\n\t}\n}";

			if( $small !== [] )
				$blocks[] = "@media (max-width: 767.98px) {\n". str_replace( "\n", "\n\t", "\t". self::_root( $small ) ). "\n}";

			$knobs = [];
			$css = [];

			foreach( $plan['groups'] as $group => $entry ) {

				foreach( $entry['knobs'] as $knob )
					$knobs[$knob['token']] = $knob['ladder'][$knob['step'] + 2];

				if( $entry['part'] !== null && trim( $entry['part']['css'] ) !== '' )
					$css[] = '/* '. $group. ': the part of '. $entry['theme']. ' '. $entry['version']. ' */'. "\n". trim( $entry['part']['css'] );

				foreach( $entry['variants'] as $component => [ $added, $standard, $labels ] )
					if( $added !== [] || $standard !== 'regular' )
						$variants[$component] = [ $added, $standard, $labels ];
			}

			if( $knobs !== [] )
				$blocks[] = self::_root( $knobs );

			$blocks = array_merge( $blocks, $css );

			// The own css goes in as it was written. Where it does not close - a comment, a string, a url(, a block - it is closed here, at its end and
			// as the bundle would close it: the bundle says so in the log at every build, and unfinished css is what a person who types has for a while
			$faults = [];

			if( trim( (string) ( $mix['css'] ?? '' ) ) !== '' ) {
				[ $own, $faults ] = self::balance( trim( (string) $mix['css'] ) );
				$blocks[] = '/* The own css of the theme */'. "\n". $own;
			}

			$body = implode( "\n\n", $blocks ). "\n";

			return [ 'css' => sprintf( self::HEAD, hash( 'sha256', $body ) ). "\n". $body, 'variants' => $variants, 'plan' => $plan, 'gone' => $gone, 'faults' => $faults ];
		}

		/**
		 *	A css closed where it was left open, the way the bundle of the kernel closes it - what is open at the end of the text ends there, and so does
		 *	every block - and a } that closes nothing taken out. Text that is closed is returned as it is
		 *
		 *	@param		string		$css
		 *
		 *	@return 	array											[ the css, what was wrong: open-comment, open-string, open-url, open-block, stray-close - each once ]
		 */
		public static function balance( string $css ): array {

			$length = strlen( $css );
			$body = '';
			$faults = [];
			$copied = 0;
			$depth = 0;
			$open = '';
			$i = 0;

			while( $open === '' && ( $i += strcspn( $css, '/"\'(\\{}', $i ) ) < $length ) {

				$char = $css[$i];

				if( $char === '/' ) {

					if( ( $css[$i + 1] ?? '' ) !== '*' )
						$i++;
					else if( ( $end = strpos( $css, '*/', $i + 2 ) ) === false ) {
						$faults[] = 'open-comment';
						$open = '*/';
					}
					else
						$i = $end + 2;
				}
				else if( $char === '"' || $char === "'" ) {

					$i++;

					while( true ) {

						$i += strcspn( $css, $char. "\\\r\n\f", $i );

						if( $i >= $length ) {
							$faults[] = 'open-string';
							$open = $char;
							break;
						}

						if( $css[$i] === '\\' )
							$i += substr( $css, $i + 1, 2 ) === "\r\n" ? 3 : 2;
						else if( $css[$i] === $char ) {
							$i++;
							break;
						}
						else
							break;
					}
				}
				else if( $char === '(' ) {

					$i++;

					if( $i >= 4 && strcasecmp( substr( $css, $i - 4, 3 ), 'url' ) === 0 && in_array( $css[$i + strspn( $css, " \t\r\n\f", $i )] ?? '', [ '"', "'" ], true ) === false ) {

						if( preg_match( '/\G(?:[^)\\\\]++|\\\\.)*+\)/s', $css, $match, 0, $i ) !== 1 ) {
							$faults[] = 'open-url';
							$open = ')';
						}
						else
							$i += strlen( $match[0] );
					}
				}
				else if( $char === '\\' )
					$i += 2;
				else if( $char === '{' ) {
					$depth++;
					$i++;
				}
				else if( $depth === 0 ) {
					$faults[] = 'stray-close';
					$body .= substr( $css, $copied, $i - $copied );
					$copied = ++$i;
				}
				else {
					$depth--;
					$i++;
				}
			}

			// What closes the text must not be taken for the end of an escape: a text that ends in a backslash has the closing one escaped
			$tail = substr( $css, $copied );
			$body .= $tail. ( $open !== '' && ( strlen( $tail ) - strlen( rtrim( $tail, '\\' ) ) ) % 2 === 1 ? ' ' : '' ). $open;

			if( $depth !== 0 ) {
				$faults[] = 'open-block';
				$body .= "\n". str_repeat( '}', $depth );
			}

			return [ $body, array_values( array_unique( $faults ) ) ];
		}

		/**
		 *	Whether a stylesheet is what the compiler wrote and nobody changed: the head names the sha256 of the text after it
		 *
		 *	@param		string		$css
		 *
		 *	@return 	bool
		 */
		public static function isOwn( string $css ): bool {

			return preg_match( self::HEAD_PATTERN, $css, $found ) === 1 && hash( 'sha256', substr( $css, strlen( $found[0] ) ) ) === $found[1];
		}

		/**
		 *	The sha256 a stylesheet of the compiler names in its head
		 *
		 *	@param		string		$css
		 *
		 *	@return 	string										'' for a stylesheet that is none of the compiler's
		 */
		public static function stamp( string $css ): string {

			return preg_match( self::HEAD_PATTERN, $css, $found ) === 1 && self::isOwn( $css ) === true ? $found[1] : '';
		}

		/**
		 *	A step of a knob, kept within the ladder
		 *
		 *	@param		mixed			$step
		 *
		 *	@return 	int
		 */
		private static function _step( mixed $step ): int {
			return max( -2, min( 2, (int) $step ) );
		}

		/**
		 *	One :root rule
		 *
		 *	@param		array			$lines				token => value
		 *
		 *	@return 	string
		 */
		private static function _root( array $lines ): string {

			$text = '';

			foreach( $lines as $token => $value )
				$text .= "\t". $token. ': '. $value. ";\n";

			return ":root {\n". $text. '}';
		}

		/**
		 *	The part a mix names for the page or a group, resolved
		 *
		 *	@return 	array											'from', 'theme', 'version', 'origin' (standard, copy, installed), 'dir', 'brand' (what the theme suggests), 'part', 'problem', 'missing' (the theme has no part for it), 'values'
		 */
		private static function _part( array &$appData, string $group, array $entry ): array {

			$from = (string) ( $entry['from'] ?? 'standard' );

			if( $from === 'standard' )
				return [ 'from' => 'standard', 'theme' => '', 'version' => '', 'origin' => 'standard', 'dir' => '', 'brand' => [], 'part' => null, 'problem' => '', 'missing' => false, 'values' => (array) ( $entry['values'] ?? [] ) ];

			$resolved = Reader::resolve( $appData, $from, $group ) + [ 'from' => $from ];
			$resolved['missing'] = $resolved['part'] === null && $resolved['problem'] === '' && $resolved['origin'] !== '';
			$resolved['values'] = (array) ( $entry['values'] ?? [] );

			return $resolved;
		}

		/**
		 *	A group with the part a mix gives it: the knobs with their steps and the variants of each component
		 *
		 *	@return 	array											'label', 'from', 'theme', 'version', 'origin', 'dir', 'part', 'problem', 'missing', 'knobs', 'variants'
		 */
		private static function _group( array &$appData, string $group, array $declared, array $entry ): array {

			$resolved = self::_part( $appData, $group, $entry );
			$part = $resolved['part'];
			$knobs = [];

			foreach( $part !== null ? $part['knobs'] : $declared['knobs'] as $key => $knob )
				$knobs[$key] = $knob + [ 'extra' => false, 'step' => self::_step( $resolved['values'][$key] ?? 0 ) ];

			$schemas = \Nino\Components::components( $appData );
			$variants = [];

			foreach( $declared['components'] as $component ) {

				$schema = (array) ( $schemas[$component]['variants'] ?? [ 'regular' ] );
				$added = array_values( array_diff( array_keys( $part['variants'][$component] ?? [] ), $schema ) );
				$standard = (string) ( $entry['standard'][$component] ?? $part['standard'][$component] ?? 'regular' );

				if( in_array( $standard, array_merge( $schema, $added ), true ) === false )
					$standard = 'regular';

				$variants[$component] = [ $added, $standard, array_intersect_key( (array) ( $part['variants'][$component] ?? [] ), array_flip( $added ) ) ];
			}

			return array_diff_key( $resolved, [ 'values' => 1, 'brand' => 1, 'step' => 1 ] ) + [ 'label' => $declared['label'], 'knobs' => $knobs, 'variants' => $variants ];
		}
	}
}
