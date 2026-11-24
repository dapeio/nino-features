<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Pdf\Layout\Html	The small subset of HTML a layout understands, read into blocks
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Pdf\Layout {

	use Nino\Modules\Pdf\Fonts\Metrics;
	use Nino\Modules\Pdf\Document\Document;
	use Nino\Modules\Pdf\Images\Image;

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Html							A string of HTML parsed with \DOMDocument into blocks the engine
	 *										lays out: paragraphs of measured atoms, rules and tables.
	 *
	 *										Understood: h1, h2 and h3, p and div (a paragraph; with class="address"
	 *										the rules of .address over those of p), br, strong and b, em and i
	 *										(both: bold italic), ul, ol and li (a bullet or the number), a (its
	 *										text), hr, table
	 *										with thead, tbody, tfoot, tr, th and td, span (its text), img with
	 *										the src pdf:logo and a width in mm, and align="right|center" or
	 *										style="text-align:..." on a paragraph, a heading or a cell, and
	 *										style="margin:..." on a p and a div (all four sides, over the design's),
	 *										style="background:#rrggbb" on a cell and on a row (the cell's over the
	 *										row's, both over the design), and style="font-size:..." on any of them, on a list item and on a span:
	 *										the size of what is inside, in pt, px (0.75 pt), mm, or em, rem and
	 *										percent of the size outside (rem: of the base size), between 0.5 and
	 *										72 pt; the line height follows the size.
	 *
	 *										How they look is the sheet's (see Sheet): the sizes, the weights, the
	 *										colours, the line heights, the space above and below, the indent of a
	 *										list, the padding and the rules of a table, the underline of a link are
	 *										read from the rules the reader is handed - the feature's own, with the
	 *										owner's laid over them.
	 *
	 *										That is the list of Allow, and the reader is handed nothing else:
	 *										the text is parsed and held to the list before it is read (see
	 *										Allow::body()), so whatever a caller hands in as a slot, and the
	 *										header and the footer, are held to it as a saved block is. A tag
	 *										that is not listed has been unwrapped to its text, every attribute
	 *										not named there is gone, and a script or a style is not text at
	 *										all.
	 *
	 *										A block is a map. p: atoms, align, indent, marker (the atoms of a
	 *										bullet or a number, or null), before, after, keep (a heading, kept
	 *										with what follows it) and base (the type of an empty line);
	 *										hr: before, after; table: rows - head, and the cells: blocks,
	 *										th, pct (the width the first row asked for), pad (top, right, bottom,
	 *										left) and bg (a colour or null) - border, before and after, and per
	 *										row the rule under it and the rule over it (a width and a colour, or
	 *										false). hr: before, after and rule. All distances are in points.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Html {

		/**
		 *	The type a document is set in, for the rules and the part of the page: the body's, with the
		 *	rules of .header or .footer laid over it in the band
		 *
		 *	@param		array 		$rules				The merged sheet
		 *	@param		string		$scope				.header, .footer or nothing
		 *
		 *	@return 	array											family, size (points), b, i, c (six hex digits), l (the line height as a factor), align (left, center or right: a band's own)
		 */
		public static function base( array $rules, string $scope = '' ): array {

			$body = $rules['body'] ?? [];
			$base = [ 'family' => (string) ( $body['font-family'] ?? 'helvetica' ), 'size' => Sheet::size( $body, 10.0 ), 'b' => ( $body['font-weight'] ?? 'normal' ) === 'bold', 'i' => ( $body['font-style'] ?? 'normal' ) === 'italic', 'c' => (string) ( $body['color'] ?? '000000' ), 'l' => (float) ( $body['line-height'] ?? Text::LEADING ), 'align' => 'left' ];

			if( $scope === '' || isset( $rules[$scope] ) === false )
				return $base;

			$band = $rules[$scope];

			return [
				'family'	=> (string) ( $band['font-family'] ?? $base['family'] ),
				'size'		=> Sheet::size( $band, $base['size'] ),
				'b'				=> isset( $band['font-weight'] ) ? $band['font-weight'] === 'bold' : $base['b'],
				'i'				=> isset( $band['font-style'] ) ? $band['font-style'] === 'italic' : $base['i'],
				'c'				=> (string) ( $band['color'] ?? $base['c'] ),
				'l'				=> (float) ( $band['line-height'] ?? $base['l'] ),
				'align'		=> (string) ( $band['text-align'] ?? $base['align'] ),
			];
		}

		/**
		 *	Parse html into blocks
		 *
		 *	@param		string		$html
		 *	@param		array 		$rules				The merged sheet (see Sheet)
		 *	@param		Image|null	$logo				What src="pdf:logo" is
		 *	@param		float			$width				The width of the column in points, which a picture is held to
		 *	@param		string		$scope				.header or .footer for the text of a band, else nothing
		 *
		 *	@return 	array
		 */
		public static function blocks( string $html, array $rules, ?Image $logo, float $width, string $scope = '' ): array {

			$body = Allow::body( $html );

			if( $body === null )
				return [];

			$base = self::base( $rules, $scope );
			$ctx = [ 'rules' => $rules, 'size' => $base['size'], 'logo' => $logo, 'width' => $width, 'indent' => 0.0, 'inset' => 0.0, 'align' => $base['align'], 'depth' => 0, 'cell' => false ];

			return self::_content( $body, [ 'fam' => $base['family'], 'b' => $base['b'], 'i' => $base['i'], 'u' => false, 'z' => $base['size'], 'c' => $base['c'], 'l' => $base['l'] ], $ctx );
		}

		/**
		 *	The blocks the children of a node make
		 *
		 *	@param		\DOMNode	$node
		 *	@param		array 		$style
		 *	@param		array 		$ctx
		 *
		 *	@return 	array
		 */
		private static function _content( \DOMNode $node, array $style, array $ctx ): array {

			$blocks = [];
			$inline = [];

			foreach( iterator_to_array( $node->childNodes ) as $child )
				self::_node( $child, $style, $ctx, $blocks, $inline );

			self::_flush( $blocks, $inline, $ctx, $style, 0.0, self::_gap( $ctx, [ 'p' ] )[1], false );

			return $blocks;
		}

		/**
		 *	One node: text goes to the inline run, a block closes the run and makes blocks of its own
		 *
		 *	@param		\DOMNode	$node
		 *	@param		array 		$style
		 *	@param		array 		$ctx
		 *	@param		array 		&$blocks
		 *	@param		array 		&$inline
		 *
		 *	@return 	void
		 */
		private static function _node( \DOMNode $node, array $style, array $ctx, array &$blocks, array &$inline ): void {

			if( $node instanceof \DOMText ) {
				array_push( $inline, ...Text::atoms( $node->data, self::_type( $style ) ) );
				return;
			}

			if( $node instanceof \DOMElement === false )
				return;

			$tag = strtolower( $node->nodeName );
			$after = self::_gap( $ctx, [ 'p' ] )[1];

			switch( $tag ) {

				case 'br':
					$inline[] = [ 't' => 'b', 'z' => $style['z'] ];
					return;

				case 'strong':
				case 'b':
					$style['b'] = true;
					break;

				case 'em':
				case 'i':
					$style['i'] = true;
					break;

				case 'span':
					$style['z'] = self::_sized( $node, (float) $style['z'], (float) $ctx['size'] );
					break;

				case 'a':
					$style = self::_style( $style, $ctx, [ 'a' ] );
					$style['u'] = ( $ctx['rules']['a']['text-decoration'] ?? 'underline' ) === 'underline';
					break;

				case 'img':
					$atom = self::_picture( $node, $ctx );
					if( $atom !== null )
						$inline[] = $atom;
					return;

				case 'hr':
					self::_flush( $blocks, $inline, $ctx, $style, 0.0, $after, false );
					[ $above, $below ] = self::_gap( $ctx, [ 'hr' ] );
					$rule = $ctx['rules']['hr']['border-top'] ?? false;
					$blocks[] = [ 'k' => 'hr', 'before' => $above, 'after' => $below, 'rule' => $rule ];
					return;

				case 'h1':
				case 'h2':
				case 'h3':
					[ $above, $below ] = self::_gap( $ctx, [ $tag ] );
					self::_flush( $blocks, $inline, $ctx, $style, 0.0, $after, false );
					$outer = (float) $style['z'];
					$style = self::_style( $style, $ctx, [ $tag ] );
					$style['z'] = self::_sized( $node, $outer, (float) $ctx['size'], (float) $style['z'] );
					$ctx['align'] = self::_align( $node, $ctx['rules'][$tag]['text-align'] ?? $ctx['align'] );
					$inner = [];
					foreach( iterator_to_array( $node->childNodes ) as $child )
						self::_node( $child, $style, $ctx, $blocks, $inner );
					self::_flush( $blocks, $inner, $ctx, $style, $above, $below, true );
					return;

				case 'p':
				case 'div':
					$names = $node->getAttribute( 'class' ) === 'address' ? [ 'p', '.address' ] : [ 'p' ];
					[ $above, $below, $left ] = self::_gap( $ctx, $names );
					$right = (float) self::_rule( $ctx, $names, 'margin', [ 0.0, 0.0, 0.0, 0.0 ] )[1] * Document::MM;
					self::_flush( $blocks, $inline, $ctx, $style, 0.0, $after, false );
					$outer = (float) $style['z'];
					$style = self::_style( $style, $ctx, $names );
					$style['z'] = self::_sized( $node, $outer, (float) $ctx['size'], (float) $style['z'] );

					// A margin of its own style is the margin of the element: all four sides, over the design's
					$own = self::_margin( $node, (float) $style['z'], (float) $ctx['size'] );

					if( $own !== null )
						[ $above, $right, $below, $left ] = $own;

					$ctx['indent'] += $left;
					$ctx['inset'] += $right;
					$ctx['align'] = self::_align( $node, self::_rule( $ctx, $names, 'text-align', $ctx['align'] ) );
					$inner = [];
					foreach( iterator_to_array( $node->childNodes ) as $child )
						self::_node( $child, $style, $ctx, $blocks, $inner );
					self::_flush( $blocks, $inner, $ctx, $style, $above, $below, false );
					return;

				case 'ul':
				case 'ol':
					self::_flush( $blocks, $inline, $ctx, $style, 0.0, $after, false );
					self::_list( $node, $style, $ctx, $blocks );
					return;

				case 'table':
					if( $ctx['cell'] === false ) {
						self::_flush( $blocks, $inline, $ctx, $style, 0.0, $after, false );
						$blocks[] = self::_table( $node, $style, $ctx );
						return;
					}
					break;
			}

			foreach( iterator_to_array( $node->childNodes ) as $child )
				self::_node( $child, $style, $ctx, $blocks, $inline );
		}

		/**
		 *	The value a property has for the first of the selectors that sets it - the last of them, as a cascade has it
		 *
		 *	@param		array 		$ctx
		 *	@param		array 		$selectors		In the order they are laid over each other
		 *	@param		string		$property
		 *	@param		mixed			$fallback
		 *
		 *	@return 	mixed
		 */
		private static function _rule( array $ctx, array $selectors, string $property, mixed $fallback ): mixed {

			$value = $fallback;

			foreach( $selectors as $selector )
				if( array_key_exists( $property, $ctx['rules'][$selector] ?? [] ) === true )
					$value = $ctx['rules'][$selector][$property];

			return $value;
		}

		/**
		 *	The space a block keeps above and below, and the left margin, in points
		 *
		 *	@param		array 		$ctx
		 *	@param		array 		$selectors
		 *
		 *	@return 	array											[ top, bottom, left ]
		 */
		private static function _gap( array $ctx, array $selectors ): array {

			$margin = self::_rule( $ctx, $selectors, 'margin', [ 0.0, 0.0, 0.0, 0.0 ] );

			return [ $margin[0] * Document::MM, $margin[2] * Document::MM, $margin[3] * Document::MM ];
		}

		/**
		 *	A style with the rules of some selectors laid over it: the family, the size, the weight, the slant, the
		 *	colour and the line height they name
		 *
		 *	@param		array 		$style
		 *	@param		array 		$ctx
		 *	@param		array 		$selectors
		 *
		 *	@return 	array
		 */
		private static function _style( array $style, array $ctx, array $selectors ): array {

			foreach( $selectors as $selector ) {

				$rule = $ctx['rules'][$selector] ?? [];

				if( isset( $rule['font-family'] ) === true )
					$style['fam'] = $rule['font-family'];

				if( isset( $rule['font-size'] ) === true )
					$style['z'] = Sheet::size( $rule, (float) $ctx['size'] );

				if( isset( $rule['font-weight'] ) === true )
					$style['b'] = $rule['font-weight'] === 'bold';

				if( isset( $rule['font-style'] ) === true )
					$style['i'] = $rule['font-style'] === 'italic';

				if( isset( $rule['color'] ) === true )
					$style['c'] = $rule['color'];

				if( isset( $rule['line-height'] ) === true )
					$style['l'] = (float) $rule['line-height'];
			}

			return $style;
		}

		/**
		 *	The atoms' type for a style: the font of the family, the size, the underline, the colour, the line height
		 *
		 *	@param		array 		$style
		 *
		 *	@return 	array
		 */
		private static function _type( array $style ): array {

			return [ 'f' => Metrics::font( $style['fam'], $style['b'], $style['i'] ), 'z' => (float) $style['z'], 'u' => $style['u'], 'c' => $style['c'], 'l' => $style['l'] ];
		}

		/**
		 *	The inline run as a paragraph - when there is anything in it
		 *
		 *	@param		array 		&$blocks
		 *	@param		array 		&$inline			Emptied
		 *	@param		array 		$ctx
		 *	@param		array 		$style
		 *	@param		float			$before
		 *	@param		float			$after
		 *	@param		bool			$keep
		 *
		 *	@return 	void
		 */
		private static function _flush( array &$blocks, array &$inline, array $ctx, array $style, float $before, float $after, bool $keep ): void {

			$atoms = $inline;
			$inline = [];

			while( $atoms !== [] && $atoms[0]['t'] === 's' )
				array_shift( $atoms );

			while( $atoms !== [] && $atoms[ count( $atoms ) - 1 ]['t'] === 's' )
				array_pop( $atoms );

			if( $atoms === [] )
				return;

			$type = self::_type( $style );

			$blocks[] = [ 'k' => 'p', 'atoms' => $atoms, 'align' => $ctx['align'], 'indent' => $ctx['indent'], 'inset' => $ctx['inset'], 'marker' => null, 'before' => $before, 'after' => $after, 'keep' => $keep, 'base' => [ 'f' => $type['f'], 'z' => $type['z'], 'l' => $type['l'] ] ];
		}

		/**
		 *	A list: one paragraph for the text of each item, the first of an item with its marker
		 *
		 *	@param		\DOMElement	$list
		 *	@param		array 			$style
		 *	@param		array 			$ctx
		 *	@param		array 			&$blocks
		 *
		 *	@return 	void
		 */
		private static function _list( \DOMElement $list, array $style, array $ctx, array &$blocks ): void {

			$name = strtolower( $list->nodeName );
			[ $above, $below ] = self::_gap( $ctx, [ $name ] );
			$ctx['depth']++;
			$ctx['indent'] = $ctx['depth'] * (float) self::_rule( $ctx, [ $name ], 'margin', [ 0.0, 0.0, 0.0, 0.0 ] )[3] * Document::MM;
			$ordered = $name === 'ol';
			$number = 0;
			$first = count( $blocks );
			$style = self::_style( $style, $ctx, [ 'li' ] );
			$ctx['align'] = (string) ( $ctx['rules']['li']['text-align'] ?? $ctx['align'] );
			$between = self::_gap( $ctx, [ 'li' ] )[1];

			foreach( iterator_to_array( $list->childNodes ) as $item ) {

				if( $item instanceof \DOMElement === false || strtolower( $item->nodeName ) !== 'li' )
					continue;

				$inner = [];
				$inline = [];

				$mine = $style;
				$mine['z'] = self::_sized( $item, (float) $style['z'], (float) $ctx['size'] );

				foreach( iterator_to_array( $item->childNodes ) as $child )
					self::_node( $child, $mine, $ctx, $inner, $inline );

				self::_flush( $inner, $inline, $ctx, $mine, 0.0, $between, false );
				$number++;

				foreach( $inner as $at => $block )
					if( $block['k'] === 'p' ) {
						$inner[$at]['marker'] = Text::atoms( $ordered === true ? $number. '.' : "\u{2022}", self::_type( $mine ) );
						break;
					}

				array_push( $blocks, ...$inner );
			}

			if( $ctx['depth'] === 1 && $blocks !== [] && $blocks[ count( $blocks ) - 1 ]['k'] === 'p' )
				$blocks[ count( $blocks ) - 1 ]['after'] = $below;

			if( $ctx['depth'] === 1 && count( $blocks ) > $first )
				$blocks[$first]['before'] = max( $blocks[$first]['before'], $above );
		}

		/**
		 *	A table: rows of cells, each cell the blocks of its content
		 *
		 *	@param		\DOMElement	$table
		 *	@param		array 			$style
		 *	@param		array 			$ctx
		 *
		 *	@return 	array
		 */
		private static function _table( \DOMElement $table, array $style, array $ctx ): array {

			$rows = [];
			$border = trim( $table->getAttribute( 'border' ) ) !== '0';
			$counted = 0;

			$read = static function( \DOMElement $tr, bool $head ) use ( &$rows, &$counted, $style, $ctx, $border ): void {

				$cells = [];
				$kind = '';
				$heads = true;

				foreach( iterator_to_array( $tr->childNodes ) as $cell )
					if( $cell instanceof \DOMElement && in_array( strtolower( $cell->nodeName ), [ 'td', 'th' ], true ) === true )
						$heads = $heads && strtolower( $cell->nodeName ) === 'th';

				// The rows of the body are counted from 1; a head row, in a thead or of th alone, is none of them
				$parity = $head === true || $heads === true ? '' : ( ++$counted % 2 === 0 ? 'tr:nth-child(even)' : 'tr:nth-child(odd)' );
				$own = self::_ground( $tr );

				foreach( iterator_to_array( $tr->childNodes ) as $cell ) {

					$tag = $cell instanceof \DOMElement ? strtolower( $cell->nodeName ) : '';

					if( $tag !== 'td' && $tag !== 'th' )
						continue;

					$kind = $kind === 'th' ? 'th' : $tag;
					$rule = self::_cells( $ctx, $tag, $parity );
					$pad = $rule['padding'] ?? [ 0.0, 0.0, 0.0, 0.0 ];
					$inner = $ctx;
					$inner['cell'] = true;
					$inner['indent'] = 0.0;
					$inner['inset'] = 0.0;
					$inner['depth'] = 0;
					$inner['align'] = self::_align( $cell, $rule['text-align'] ?? 'left' );

					$cells[] = [
						'blocks'	=> self::_content( $cell, self::_cell( $cell, $style, $ctx, $tag, $parity ), $inner ),
						'th'			=> $tag === 'th',
						'pct'			=> preg_match( '/^\s*(\d+(?:\.\d+)?)\s*%\s*$/', $cell->getAttribute( 'width' ), $found ) === 1 ? (float) $found[1] : null,
						'pad'			=> [ $pad[0] * Document::MM, $pad[1] * Document::MM, $pad[2] * Document::MM, $pad[3] * Document::MM ],
						'bg'			=> self::_ground( $cell ) ?? $own ?? $rule['background'] ?? null,
					];
				}

				if( $cells === [] )
					return;

				// The rules of a row: a row with a th has the th's, a head rule whatever the table says of its border; any other row has the td's, unless the table says border="0"
				$rule = self::_cells( $ctx, $kind, $parity );
				$lines = $kind === 'th' || $border === true;
				$rows[] = [ 'head' => $head, 'cells' => $cells, 'rule' => $lines === true ? ( $rule['border-bottom'] ?? false ) : false, 'top' => $lines === true ? ( $rule['border-top'] ?? false ) : false ];
			};

			foreach( iterator_to_array( $table->childNodes ) as $child ) {

				if( $child instanceof \DOMElement === false )
					continue;

				$tag = strtolower( $child->nodeName );

				if( $tag === 'tr' )
					$read( $child, false );
				else if( $tag === 'thead' || $tag === 'tbody' || $tag === 'tfoot' )
					foreach( iterator_to_array( $child->childNodes ) as $tr )
						if( $tr instanceof \DOMElement && strtolower( $tr->nodeName ) === 'tr' )
							$read( $tr, $tag === 'thead' );
			}

			[ $above, $below ] = self::_gap( $ctx, [ 'table' ] );

			return [ 'k' => 'table', 'rows' => $rows, 'border' => $border, 'before' => $above, 'after' => $below ];
		}

		/**
		 *	The selectors that set a cell, the least specific first: its tag, the rule of its row's parity, and the tag within that
		 *
		 *	@param		string		$tag
		 *	@param		string		$parity				tr:nth-child(even), tr:nth-child(odd), or nothing for a row that is not counted
		 *
		 *	@return 	array
		 */
		private static function _selectors( string $tag, string $parity ): array {

			return $parity === '' ? [ $tag ] : [ $tag, $parity, $parity. ' '. $tag ];
		}

		/**
		 *	The rules that set a cell, laid over one another property by property in the order of its selectors
		 *
		 *	@param		array 		$ctx
		 *	@param		string		$tag
		 *	@param		string		$parity
		 *
		 *	@return 	array
		 */
		private static function _cells( array $ctx, string $tag, string $parity ): array {

			$rule = [];

			foreach( self::_selectors( $tag, $parity ) as $selector )
				$rule = array_replace( $rule, $ctx['rules'][$selector] ?? [] );

			return $rule;
		}

		/**
		 *	The background an element's own style asks for (Allow keeps it as background:#rrggbb), or null
		 *
		 *	@param		\DOMElement	$element
		 *
		 *	@return 	string|null
		 */
		private static function _ground( \DOMElement $element ): ?string {

			return preg_match_all( '/background\s*:\s*#([0-9a-f]{6})\s*(?:;|$)/i', $element->getAttribute( 'style' ), $found ) > 0 ? strtolower( $found[1][ count( $found[1] ) - 1 ] ) : null;
		}

		/**
		 *	The style of a cell: the rules of its tag, then of its row's parity, over the style outside, and the font size its own style asks for
		 *
		 *	@param		\DOMElement	$cell
		 *	@param		array 			$style
		 *	@param		array 			$ctx
		 *	@param		string			$tag
		 *	@param		string			$parity				The rule of the row's parity, or nothing for a row that is not counted
		 *
		 *	@return 	array
		 */
		private static function _cell( \DOMElement $cell, array $style, array $ctx, string $tag, string $parity = '' ): array {

			$mine = self::_style( $style, $ctx, self::_selectors( $tag, $parity ) );
			$mine['z'] = self::_sized( $cell, (float) $style['z'], (float) $ctx['size'], (float) $mine['z'] );

			return $mine;
		}

		/**
		 *	The font size an element's own style asks for, in points: pt, px and mm as they are, em and percent of the size
		 *	outside, rem of the base size - held to 0.5 to 72 points; the size it has without one where there is none
		 *
		 *	@param		\DOMElement	$element
		 *	@param		float				$outer				The size of what the element is in
		 *	@param		float				$base					The base size of the layout
		 *	@param		float|null	$own					The size the rules of the element give it, the outer one where null
		 *
		 *	@return 	float
		 */
		private static function _sized( \DOMElement $element, float $outer, float $base, ?float $own = null ): float {

			$own ??= $outer;

			if( preg_match_all( '/font-size\s*:\s*(\d+(?:\.\d+)?)(pt|px|mm|em|rem|%)/i', $element->getAttribute( 'style' ), $found, PREG_SET_ORDER ) < 1 )
				return $own;

			$last = $found[ count( $found ) - 1 ];
			$number = (float) $last[1];

			$size = match( strtolower( $last[2] ) ) {
				'px'		=> $number * 0.75,
				'mm'		=> $number * Document::MM,
				'em'		=> $number * $outer,
				'rem'		=> $number * $base,
				'%'			=> $number / 100 * $outer,
				default	=> $number,
			};

			return min( max( $size, 0.5 ), 72.0 );
		}

		/**
		 *	The margin an element's own style asks for, in points - top, right, bottom, left - or null where it asks for none.
		 *	One to four values in CSS order; pt, px and mm as they are, em of the size of the element, rem of the base size;
		 *	each held to 0 to 200 mm
		 *
		 *	@param		\DOMElement	$element
		 *	@param		float				$size					The size of the element's text in points
		 *	@param		float				$base					The base size of the layout
		 *
		 *	@return 	array|null
		 */
		private static function _margin( \DOMElement $element, float $size, float $base ): ?array {

			if( preg_match_all( '/margin\s*:\s*([^;]+)/i', $element->getAttribute( 'style' ), $found ) < 1 )
				return null;

			$values = preg_split( '/\s+/', trim( $found[1][ count( $found[1] ) - 1 ] ) ) ?: [];
			$points = [];

			foreach( $values as $value ) {

				if( preg_match( '/^(\d+(?:\.\d+)?)(pt|px|mm|em|rem)?$/i', $value, $one ) !== 1 )
					return null;

				$number = (float) $one[1];
				$points[] = min( max( match( strtolower( $one[2] ?? '' ) ) {
					'px'		=> $number * 0.75,
					'mm'		=> $number * Document::MM,
					'em'		=> $number * $size,
					'rem'		=> $number * $base,
					default	=> $number,
				}, 0.0 ), 200.0 * Document::MM );
			}

			return match( count( $points ) ) {
				1 => [ $points[0], $points[0], $points[0], $points[0] ],
				2 => [ $points[0], $points[1], $points[0], $points[1] ],
				3 => [ $points[0], $points[1], $points[2], $points[1] ],
				4 => $points,
				default => null,
			};
		}

		/**
		 *	The alignment an element asks for, else the one it stands in
		 *
		 *	@param		\DOMElement	$element
		 *	@param		string			$current
		 *
		 *	@return 	string											left, right or center
		 */
		private static function _align( \DOMElement $element, string $current ): string {

			$align = strtolower( trim( $element->getAttribute( 'align' ) ) );

			if( in_array( $align, [ 'left', 'right', 'center' ], true ) === true )
				return $align;

			if( preg_match( '/text-align\s*:\s*(left|right|center)/i', $element->getAttribute( 'style' ), $found ) === 1 )
				return strtolower( $found[1] );

			return $current;
		}

		/**
		 *	The picture atom of an img: the logo, as wide as asked for in mm - 150 dpi where it says
		 *	nothing - and never wider than the column; any other src leaves nothing
		 *
		 *	@param		\DOMElement	$img
		 *	@param		array 			$ctx
		 *
		 *	@return 	array|null
		 */
		private static function _picture( \DOMElement $img, array $ctx ): ?array {

			$logo = $ctx['logo'];

			if( $logo === null || strtolower( trim( $img->getAttribute( 'src' ) ) ) !== 'pdf:logo' )
				return null;

			$wide = preg_match( '/^\s*(\d+(?:\.\d+)?)\s*(?:mm)?\s*$/', $img->getAttribute( 'width' ), $found ) === 1 ? (float) $found[1] * Document::MM : $logo->width() * 72 / 150;
			$wide = min( max( $wide, 1.0 ), max( $ctx['width'] - $ctx['indent'], 1.0 ) );

			return [ 't' => 'i', 'w' => $wide, 'h' => $wide * $logo->height() / $logo->width(), 'i' => $logo ];
		}
	}
}
