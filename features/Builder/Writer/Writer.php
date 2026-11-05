<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Builder\Writer	see features/Builder/Builder.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino								A compact filesystembased php framework
	 *	Writer							The model in, a page template out - the canonical form of
	 *											the grammar the Reader reads, so that the Reader reads what
	 *											the Writer wrote as the model it was given, and the Writer
	 *											writes a file in the canonical form as it was.
	 *
	 *											What canonical means: tabs for the indentation (the section
	 *											at the margin, the row one tab in, the columns two, their
	 *											calls three - in a stack four, the stack's own tags three;
	 *											a component that holds components has its call on a line,
	 *											each of its children one tab deeper on a line of its own, and
	 *											its closing tag on a line of its own at its own depth),
	 *											one call per line, the classes of every tag in a fixed
	 *											order (see sectionClasses(), rowClasses() and colClasses()) with the
	 *											custom ones last, an attribute of a call only where it is
	 *											not what the schema says it is without it, a blank line
	 *											between the blocks, one line feed at the end. The columns
	 *											that are 100 wide in all three viewports are written
	 *											nino-grid-100, any other as nino-grid-100 and its three tiers after it. A section whose
	 *											stack is set is the call of that loop - [sections /type ...] -
	 *											with the section's id and its classes after nino-section as the
	 *											call's, its row and the rest as they stand in a <section>; a
	 *											background that is the row's (inner) is the first thing in
	 *											the row, two tabs in, as the one of a column is in its column.
	 *											A section that is a group has its row as any other has, but for the alignment, the order and the gap of its columns, and in it one column, nino-grid-100 and no other class, that holds
	 *											the call of its stack - [tabs id="<id>-tabs"], three tabs in, with the attributes the group keeps and no class - with a [panel title="..." class="..."]...[/panel] for each column,
	 *											one tab deeper, what the column holds one deeper again, and the closing tag of the stack at the depth of the call (see panelClasses()).
	 *
	 *											The radius of a surface is a class of the page, nino-radius or
	 *											nino-radius-alt, and a setting of the model: it stands after the
	 *											spacing of a section, after the paddings of a row, after the spacing
	 *											of a column and first in the class of a call, and is written only where
	 *											the element paints a surface of its own. Whether it deviates from the
	 *											default the page has for the kind is the panel's to say: the model holds
	 *											no setting where the radius asked for is that default, and the Writer
	 *											writes what the model holds.
	 *
	 *											The hover of a picture is a class of the page as well, nino-hover: it
	 *											stands on the call of a component whose source is an image, the picture
	 *											of a card among them. The classes of a call come in this order: the
	 *											radius, the hover, the two offsets of the ground of a card, the six
	 *											sides of the spacing, then the classes the editor wrote.
	 *
	 *											How far in from its sides the picture behind an element starts is a
	 *											class of the page as well, nino-bg-pl-<n> and nino-bg-pr-<n>: it stands
	 *											before the radius of a row and of a column, after the radius of a card,
	 *											and is written for a row and a column only where they have a picture to
	 *											move, and for a card whenever its node holds a step.
	 *
	 *											The animation of a section stands on its row: its classes after the
	 *											radius of the row, its delay and its duration as the attributes of the
	 *											row's tag - the kernel animates any element that carries nino-vpa, and a
	 *											section that is a cover keeps the tag it has for its height alone. The
	 *											arrow of a section is the one line after its row, the call of the kernel's
	 *											scroll hint.
	 *
	 *											The blocks stand in the wrap: the line that opens it
	 *											follows the name and the frame above, the line that closes
	 *											it comes before the frame below, and nothing inside is
	 *											indented for it - a file the builder had not wrapped is
	 *											wrapped with two lines and is otherwise what it was. Its
	 *											classes (see wrapClasses()) are the kind of animation of the
	 *											template.
	 *
	 *											A foreign block is the one thing written as it came: one
	 *											the builder made, or that came in its markers, gets the
	 *											markers (the Reader finds it again without analysing it);
	 *											one that was read as a section and failed has a reason, and
	 *											is written exactly as it was read, without markers, so that
	 *											the file does not change where nobody changed it.
	 *
	 *											The markup of a section is in templates/, one fragment each
	 *											- the tags are the same text wherever a section is written
	 *											(the block of a background is one, for a section and for a
	 *											column) - and filled with values that are escaped first
	 *											(AGENTS.md, "Markup belongs in a template"). What is
	 *											composed here are the shortcode calls, which are no markup,
	 *											and the closing tags.
	 *
	 *	@package						Dape/Nino
	 *	@author							David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Writer {

		// The fragments, read once per request: name => text
		private static array $fragments = [];

		/**
		 *	The model written as a page template: the head, the blocks and the
		 *	foot, a blank line between them
		 *
		 *	@param		array			$model				See Reader
		 *	@param		array			$registry			[ 'components' => name => schema, 'stacks' => name => schema ]
		 *
		 *	@return 	string										The file; a page with no name, no frame and no block is the wrap and nothing in it
		 *
		 *	@throws		\UnexpectedValueException		Where the effect or the speed of the wrap is none of Nino.css
		 */
		public static function write( array $model, array $registry ): string {

			$parts = [ self::head( $model ) ];

			foreach( (array) ( $model['blocks'] ?? [] ) as $block )
				$parts[] = self::block( (array) $block, $registry );

			$parts[] = self::foot( $model );

			return implode( "\n\n", $parts ). "\n";
		}

		/**
		 *	What stands above the blocks: the name, the frame above them and the
		 *	line that opens the wrap
		 *
		 *	@param		array			$model
		 *
		 *	@return 	string
		 */
		public static function head( array $model ): string {

			$lines = [];

			if( trim( (string) ( $model['name'] ?? '' ) ) !== '' )
				$lines[] = '<!-- nino:template-name '. trim( (string) $model['name'] ). ' -->';

			if( (string) ( $model['header'] ?? '' ) !== '' )
				$lines[] = '[template /templates/'. $model['header']. ']';

			$lines[] = self::_fill( 'div-open', [ '[[class]]' => self::_class( self::wrapClasses( $model ) ) ] );

			return implode( "\n", $lines );
		}

		/**
		 *	What stands below the blocks: the line that closes the wrap and the
		 *	frame below them
		 *
		 *	@param		array			$model
		 *
		 *	@return 	string
		 */
		public static function foot( array $model ): string {
			return '</div>'. ( (string) ( $model['footer'] ?? '' ) !== '' ? "\n[template /templates/". $model['footer']. ']' : '' );
		}

		/**
		 *	One block, a section or a foreign one
		 *
		 *	@param		array			$block
		 *	@param		array			$registry
		 *
		 *	@return 	string
		 */
		public static function block( array $block, array $registry ): string {
			return ( $block['kind'] ?? '' ) === 'section' ? self::_section( $block, $registry ) : self::_foreign( $block );
		}

		/**
		 *	A foreign block: in its markers, or as it came
		 *
		 *	@param		array			$block
		 *
		 *	@return 	string
		 */
		private static function _foreign( array $block ): string {

			$source = (string) ( $block['source'] ?? '' );

			if( is_array( $block['reason'] ?? null ) === true )
				return $source;

			return Reader::MARKER. "\n". $source. "\n". Reader::MARKER_END;
		}

		/**
		 *	One section: a <section>, or the call of a loop of sections where the model says it is
		 *	one, which stands in the place of the tag
		 *
		 *	@param		array			$section
		 *	@param		array			$registry
		 *
		 *	@return 	string
		 */
		private static function _section( array $section, array $registry ): string {

			$settings	= (array) ( $section['settings'] ?? [] ) + Reader::sectionDefaults();
			$stack		= is_array( $section['stack'] ?? null ) === true ? $section['stack'] : null;
			// A section of tiles has the picture behind it as a content section has, and none in its row (the model has none): its tiles hold their own
			$tiles		= $settings['tiles'] === true;
			// A group is the one column of its row. A section of tiles is none
			$group		= $tiles === false && in_array( (string) $settings['group'], Reader::groups( $registry ), true ) === true ? (string) $settings['group'] : '';

			$pictured	= self::_pictured( $section );
			$inner		= self::_inner( $settings );
			$lines		= [];

			if( $stack !== null ) {

				// The id and the classes after nino-section are the call's, and a call has no attributes of a tag: no cover height
				$stack['attributes'] = [ 'id' => (string) ( $section['id'] ?? '' ), 'class' => implode( ' ', array_slice( self::sectionClasses( $settings, $pictured ), 1 ) ) ] + (array) ( $stack['attributes'] ?? [] );

				$lines[] = self::_call( $stack, $registry['stacks'][$stack['name'] ?? ''] ?? [] );
			}
			else {

				$attributes = '';

				// The height is always written, at 90 as well: a template says what it is, and nobody has to know the 90 Nino.ui.js takes without it
				if( $settings['cover'] !== null && $tiles === false )
					$attributes .= ' data-cover-height="'. (int) $settings['cover']. '"';

				$lines[] = self::_fill( 'section-open', [ '[[id]]' => self::_escape( (string) ( $section['id'] ?? '' ) ), '[[class]]' => self::_class( self::sectionClasses( $settings, $pictured ) ), '[[attributes]]' => $attributes ] );
			}

			if( $pictured === true && $inner === false )
				$lines[] = "\t". self::_background( (array) $section['background'], 'nino-section-bg' );

			$lines[] = "\t". self::_fill( 'row-open', [ '[[class]]' => self::_class( self::rowClasses( $settings, $pictured, $group !== '' ) ), '[[attributes]]' => self::_animationAttributes( $settings ) ] );

			// The picture behind the row is the first thing in it, as a column's is in the column
			if( $pictured === true && $inner === true )
				$lines[] = "\t\t". self::_background( (array) $section['background'], 'nino-grid-row-bg' );

			// A group is the one column of the whole width of its row, and holds the call of its stack
			if( $group !== '' ) {

				$panel	= Reader::panel( $registry );
				$schema	= (array) $registry['stacks'][$group];
				$call		= [ 'name' => $group, 'source' => '', 'attributes' => [ 'id' => (string) ( $section['id'] ?? '' ) !== '' && $stack === null ? (string) $section['id']. '-'. $group : '' ]
					+ array_intersect_key( (array) $settings['groupAttributes'], Reader::groupAttributes( $schema ) ) ];

				$lines[] = "\t\t". self::_fill( 'div-open', [ '[[class]]' => 'nino-grid-100' ] );
				$lines[] = "\t\t\t". self::_call( $call, $schema );

				foreach( (array) ( $section['cols'] ?? [] ) as $col ) {

					$classes	= implode( ' ', array_unique( self::panelClasses( (array) $col ) ) );
					$title		= self::_title( (string) ( $col['title'] ?? '' ) );

					// In a loop of sections the fields of the element stand in the title as they are, which the kernel puts in before it reads the call
					$lines[] = "\t\t\t\t[". $panel
						. ( $title !== '' ? ' '. Reader::PANEL_TITLE. '="'. ( $stack !== null && Reader::lineText( $title ) === true ? Reader::lineEncode( $title ) : Reader::entityEncode( $title ) ). '"' : '' )
						. ( $classes !== '' ? ' class="'. self::_safe( $classes, $panel, 'class' ). '"' : '' ). ']';
					array_push( $lines, ...self::_colLines( (array) $col, $registry, "\t\t\t\t\t", true ) );
					$lines[] = "\t\t\t\t[/". $panel. ']';
				}

				$lines[] = "\t\t\t[/". $group. ']';
				$lines[] = "\t\t</div>";
			}
			else
				foreach( (array) ( $section['cols'] ?? [] ) as $col ) {

					$lines[] = "\t\t". self::_fill( 'div-open', [ '[[class]]' => self::_class( self::colClasses( (array) $col, $tiles ) ) ] );
					array_push( $lines, ...self::_colLines( (array) $col, $registry ) );
					$lines[] = "\t\t</div>";
				}

			$lines[] = "\t</div>";

			// The arrow is the last thing in the section, after its row
			if( $settings['arrow'] === true )
				$lines[] = "\t". self::_fill( 'arrow', [] );

			$lines[] = $stack !== null ? '[/'. (string) ( $stack['name'] ?? '' ). ']' : '</section>';

			return implode( "\n", $lines );
		}

		/**
		 *	What a column holds: the picture behind it, then its loop with the components of its cell and the closing tag, or its components - one indent in from $indent for the
		 *	cell. The same in the div of a row and in the panel of a group, which has no picture of its own
		 *
		 *	@param		array			$col
		 *	@param		array			$registry
		 *	@param		string		$indent				The tabs in front of what the column holds
		 *	@param		bool			$panel				Whether the column is the panel of a group
		 *
		 *	@return 	array										The lines
		 */
		private static function _colLines( array $col, array $registry, string $indent = "\t\t\t", bool $panel = false ): array {

			$lines = [];

			if( $panel === false && self::_pictured( $col ) === true )
				$lines[] = $indent. self::_background( (array) $col['background'], 'nino-grid-bg' );

			if( is_array( $col['stack'] ?? null ) === true ) {

				$lines[] = $indent. self::_call( (array) $col['stack'], $registry['stacks'][$col['stack']['name'] ?? ''] ?? [] );

				// A loop of the cell none has no content: its call is a single tag, with nothing after it
				if( ( $registry['stacks'][$col['stack']['name'] ?? '']['cell'] ?? '' ) !== 'none' ) {

					foreach( (array) ( $col['components'] ?? [] ) as $component )
						array_push( $lines, ...self::_component( (array) $component, $registry, $indent. "\t" ) );

					$lines[] = $indent. '[/'. (string) ( $col['stack']['name'] ?? '' ). ']';
				}
			}
			else
				foreach( (array) ( $col['components'] ?? [] ) as $component )
					array_push( $lines, ...self::_component( (array) $component, $registry, $indent ) );

			return $lines;
		}

		/**
		 *	The title of a panel: one line, which the call carries with its five entities (Reader::entityEncode())
		 *
		 *	@param		string		$title
		 *
		 *	@return 	string
		 *
		 *	@throws		\UnexpectedValueException
		 */
		private static function _title( string $title ): string {

			if( preg_match( '/[\r\n]/', $title ) === 1 )
				throw new \UnexpectedValueException( 'The value of "'. Reader::PANEL_TITLE. '" of a panel holds a line break, which a call cannot carry.' );

			return $title;
		}

		/**
		 *	The classes of a panel: those of a column but for its width and the viewports it is hidden in, which only a row has, and the dimming and the offsets of a picture, which a panel has none of
		 *
		 *	@param		array			$col
		 *
		 *	@return 	array										Class names, none for none
		 */
		public static function panelClasses( array $col ): array {

			return self::colClasses( $col, false, true );
		}

		/**
		 *	The classes of the wrap, in the order they are written: what makes it
		 *	one, whether its sections are animated, the effect with its strength,
		 *	the speed, and the rest
		 *
		 *	@param		array			$model				The template's
		 *
		 *	@return 	array										Class names
		 *
		 *	@throws		\UnexpectedValueException		Where the effect or the speed is none of Nino.css, which would be a class that is no wrap's
		 */
		public static function wrapClasses( array $model ): array {

			$model	+= Reader::wrapDefaults();
			$class	= [ Reader::WRAP ];

			if( in_array( (string) $model['vpa'], array_merge( [ '' ], Reader::EFFECTS ), true ) === false )
				throw new \UnexpectedValueException( 'The effect of the animation of the template is none that Nino.css has.' );

			if( in_array( (string) $model['vpaSpeed'], array_merge( [ '' ], Reader::SPEEDS ), true ) === false )
				throw new \UnexpectedValueException( 'The speed of the animation of the template is none that Nino.css has.' );

			if( $model['animate'] === true )
				$class[] = Reader::WRAP_VPA;

			if( (string) $model['vpa'] !== '' )
				$class[] = 'nino-vpa--'. $model['vpa'];

			if( (string) $model['vpaSpeed'] !== '' )
				$class[] = 'nino-vpa--speed-'. $model['vpaSpeed'];

			return array_merge( $class, self::_custom( (string) $model['wrapClass'] ) );
		}

		/**
		 *	The classes of a section, in the order they are written
		 *
		 *	@param		array			$s						The section's settings
		 *	@param		bool			$pictured			Whether it has a picture to darken: a scrim over none is not written on a plain section, and the colour of the row, with the picture, is the row's where inner
		 *
		 *	@return 	array										Class names
		 */
		public static function sectionClasses( array $s, bool $pictured = false ): array {

			$s			= $s + Reader::sectionDefaults();
			$class	= [ 'nino-section' ];
			$tiles	= $s['tiles'] === true;

			// A section of tiles says what it is right after nino-section, then the height of its tiles
			if( $tiles === true ) {

				$class[] = 'nino-tiles';

				if( (string) $s['ratio'] !== '' )
					$class[] = 'nino-tiles--'. $s['ratio'];
			}

			// The colour is the row's where the background stands inside
			if( (string) $s['color'] !== '' && self::_inner( $s ) === false )
				$class[] = 'nino-section--'. $s['color'];

			if( (string) $s['border'] !== '' )
				$class[] = 'nino-section--border-'. $s['border'];

			if( $s['borderPrimary'] === true )
				$class[] = 'nino-section--border-primary';

			// The scrim is the cover's where the section is a cover - Nino.css gives that class the ink of a black section as well - else the parallax's.
			// A cover, a parallax, a scrim, a padded cover and the place of the content are the content kind's: a section of tiles writes none of them
			$s['cover']			= $tiles === true ? null : $s['cover'];
			$s['parallax']	= $tiles === true ? false : $s['parallax'];

			if( $s['cover'] !== null ) {

				$class[] = 'nino-cover';

				if( $s['dim'] === true )
					$class[] = 'nino-cover--dim';
			}

			if( $s['parallax'] === true ) {

				$class[] = 'nino-parallex';

				if( $s['dim'] === true && $s['cover'] === null )
					$class[] = 'nino-parallex--dim';
			}

			// A plain section is darkened by a scrim of its own over its own picture - and one with no picture has nothing to darken, and the light inks of the scrim would sit on a light ground
			if( $s['dim'] === true && $s['cover'] === null && $s['parallax'] === false && $pictured === true && self::_inner( $s ) === false )
				$class[] = 'nino-section--dim';

			// The room of the page around a cover or a parallax, and the place of the content in them: on a section that is neither the Builder writes neither
			if( $s['padded'] === true && ( $s['cover'] !== null || $s['parallax'] === true ) )
				$class[] = 'nino-cover--padded';

			if( (string) $s['contentPos'] !== '' && ( $s['cover'] !== null || $s['parallax'] === true ) )
				$class[] = 'nino-cover-'. $s['contentPos'];

			if( (string) $s['whitespace'] !== '' )
				$class[] = 'nino-whitespace--'. $s['whitespace'];

			// The six sides of the spacing: the margins, then the paddings - the four inside ones as nino-p-<n> where they are the same
			$class = array_merge( $class, self::_spacing( $s, true ) );

			// The radius of the picture of a padded cover - after the spacing, and written for a section that is one and for no other
			if( isset( Reader::RADIUS_CLASS[(string) $s['radius']] ) === true && $s['padded'] === true && ( $s['cover'] !== null || $s['parallax'] === true ) )
				$class[] = Reader::RADIUS_CLASS[$s['radius']];

			if( (string) $s['text'] !== '' )
				$class[] = 'nino-text-'. $s['text'];

			return array_merge( $class, self::_custom( (string) $s['custom'] ) );
		}

		/**
		 *	The classes of the row: its width and its place, then - where the section's background stands inside, on the
		 *	row - its ground and the scrim over its picture, then the alignment of its columns and its padding on each side,
		 *	how far in from either side that picture starts, the radius of what the row paints and the animation of the section
		 *
		 *	@param		array			$s						The section's settings
		 *	@param		bool			$pictured			Whether the section has a picture, which is the row's where inner: a scrim over none is not written
		 *	@param		bool			$group				Whether the row is a group's, which has no alignment, no order and no gap of its columns to write
		 *
		 *	@return 	array
		 */
		public static function rowClasses( array $s, bool $pictured = false, bool $group = false ): array {

			$s			= $s + Reader::sectionDefaults();
			$class	= [ 'nino-grid-row' ];
			$tiles	= $s['tiles'] === true;

			// The row of a section of tiles has the whole width of the section: no maximum width, no place (nor ground or scrim, which are inner)
			if( (string) $s['row'] !== '' && $tiles === false )
				$class[] = 'nino-grid-row--'. $s['row'];

			if( (string) $s['rowPlace'] !== '' && $tiles === false )
				$class[] = 'nino-grid-row--'. $s['rowPlace'];

			if( self::_inner( $s ) === true ) {

				if( (string) $s['color'] !== '' )
					$class[] = 'nino-grid-row--'. $s['color'];

				if( $s['dim'] === true && $pictured === true )
					$class[] = 'nino-grid-row--dim';
			}

			if( (string) $s['rowAlign'] !== '' && $group === false )
				$class[] = 'nino-grid-'. $s['rowAlign'];

			// The order of the columns: the narrowest viewport says it for every width, each wider one only where it differs from the one before
			$before = false;

			foreach( $group === true ? [] : Reader::VIEWPORTS as $viewport ) {

				$turned = ( (array) $s['reverse'] )[$viewport] ?? false;

				if( $viewport === 's' && $turned === true )
					$class[] = 'nino-grid-row--reverse';
				elseif( $viewport !== 's' && $turned !== $before )
					$class[] = 'nino-grid-row--'. $viewport. ( $turned === true ? '-reverse' : '-forward' );

				$before = $turned;
			}

			if( (string) $s['rowGap'] !== '' && $group === false )
				$class[] = 'nino-grid-gap-'. $s['rowGap'];

			foreach( Reader::ROW_SPACING as $side => $key )
				if( (string) $s[$key] !== '' )
					$class[] = 'nino-'. $side. '-'. $s[$key];

			// How far in from either side the picture inside the row starts, left then right - it has one only with the background inside - after the paddings
			if( self::_inner( $s ) === true && $pictured === true )
				foreach( Reader::ROW_BG_SPACING as $side => $key )
					if( (string) $s[$key] !== '' )
						$class[] = 'nino-bg-'. $side. '-'. $s[$key];

			// The radius of what the row paints - its ground or its picture, which it has with the background inside - after them
			if( isset( Reader::RADIUS_CLASS[(string) $s['rowRadius']] ) === true && self::_inner( $s ) === true && ( (string) $s['color'] !== '' || $pictured === true ) )
				$class[] = Reader::RADIUS_CLASS[$s['rowRadius']];

			return array_merge( $class, self::_animationClasses( $s ), self::_custom( (string) $s['rowCustom'] ) );
		}

		/**
		 *	The classes of a column
		 *
		 *	@param		array			$col
		 *	@param		bool			$tiles				Whether it is a tile, a column of a section of tiles: it can be linked, as a whole
		 *	@param		bool			$panel				Whether it is the panel of a group, which has the whole width of the group and is hidden in no viewport: the classes that say a width or a viewport are none of the panel's
		 *
		 *	@return 	array
		 */
		public static function colClasses( array $col, bool $tiles = false, bool $panel = false ): array {

			$col		= $col + Reader::colDefaults();
			$width	= [];
			$class	= [];
			// A panel has no picture of its own, and so nothing to dim and no offsets of one
			$pictured = $panel === false && self::_pictured( $col );

			if( $panel === false ) {

				foreach( Reader::VIEWPORTS as $viewport )
					$width[$viewport] = (int) ( $col['width'][$viewport] ?? 100 );

				// Nino.css is mobile first and its first tier starts at 640px: a column that is not 100 in every viewport has the base class, full width, before its tiers, so that it is full width until the first one applies
				$class = [ 'nino-grid-100' ];

				if( $width !== [ 's' => 100, 'm' => 100, 'l' => 100 ] )
					foreach( Reader::VIEWPORTS as $viewport )
						$class[] = 'nino-grid-'. $viewport. '-'. $width[$viewport];

				foreach( Reader::VIEWPORTS as $viewport )
					if( ( $col['hidden'][$viewport] ?? false ) === true )
						$class[] = 'nino-hide-'. $viewport;
			}

			if( (string) $col['color'] !== '' )
				$class[] = 'nino-grid--'. $col['color'];

			// Only a picture is darkened: a column without one has no dimming to write
			if( $col['dim'] === true && $pictured === true )
				$class[] = 'nino-grid--dim';

			// The title's link covers the whole of a tile
			if( $tiles === true && $col['linked'] === true )
				$class[] = 'nino-grid--linked';

			if( (string) $col['stackAlign'] !== '' )
				$class[] = 'nino-stack-'. $col['stackAlign'];

			if( (string) $col['stackGap'] !== '' )
				$class[] = 'nino-stack-gap-'. $col['stackGap'];

			if( (string) $col['text'] !== '' )
				$class[] = 'nino-text-'. $col['text'];

			// The six sides of the spacing, as a section has them
			$class = array_merge( $class, self::_spacing( $col, true ) );

			// How far in from either side the picture behind the column starts, left then right, after the spacing
			if( $pictured === true && $tiles === false )
				foreach( Reader::BG_SPACING as $side => $key )
					if( (string) $col[$key] !== '' )
						$class[] = 'nino-bg-'. $side. '-'. $col[$key];

			// The radius of what the column paints - a ground or a picture - after them
			if( isset( Reader::RADIUS_CLASS[(string) $col['radius']] ) === true && ( (string) $col['color'] !== '' || $pictured === true ) )
				$class[] = Reader::RADIUS_CLASS[$col['radius']];

			return array_merge( $class, self::_animationClasses( $col ), self::_custom( (string) $col['custom'] ) );
		}

		/**
		 *	The classes of the six sides of the spacing of a section, a column or a component, in the order of Reader::SPACING: one for each side that is set, and -
		 *	where $shorthand is set and the four inside sides are set and the same - the kernel's nino-p-<n> for them, standing where the first of the four would
		 *	stand, which the Reader reads back as the four
		 *
		 *	@param		array			$of						The settings of the section, the column or the component
		 *	@param		bool			$shorthand		Whether the four inside sides may be said at once: on a tag they may, on a call nino-p-<n> is a class of the editor's
		 *
		 *	@return 	array											Class names
		 */
		private static function _spacing( array $of, bool $shorthand ): array {

			$inside	= array_map( static fn( string $side ): string => (string) ( $of[$side] ?? '' ), Reader::INSIDE );
			$whole	= $shorthand === true && $inside[0] !== '' && count( array_unique( $inside ) ) === 1;
			$class	= [];

			foreach( Reader::SPACING as $side ) {

				$step = (string) ( $of[$side] ?? '' );

				if( $step === '' )
					continue;

				if( $whole === true && in_array( $side, Reader::INSIDE, true ) === true ) {

					if( $side === Reader::INSIDE[0] )
						$class[] = 'nino-p-'. $step;

					continue;
				}

				$class[] = 'nino-'. $side. '-'. $step;
			}

			return $class;
		}

		/**
		 *	Whether the colour, the picture and the scrim of a section stand on its row: they do where the
		 *	settings say so and the section is no cover and no parallax, which are the section's own picture
		 *
		 *	@param		array			$s						The section's settings
		 *
		 *	@return 	bool
		 */
		private static function _inner( array $s ): bool {
			return ( $s['inner'] ?? false ) === true && ( $s['cover'] ?? null ) === null && ( $s['parallax'] ?? false ) === false && ( $s['tiles'] ?? false ) === false;
		}

		/**
		 *	@param		array			$node					A section or a column
		 *
		 *	@return 	bool										Whether it has a background that names a slot - or a field of the element, which a column in a loop of sections may
		 */
		private static function _pictured( array $node ): bool {
			return is_array( $node['background'] ?? null ) === true && ( (string) ( $node['background']['slot'] ?? '' ) !== '' || (string) ( $node['background']['field'] ?? '' ) !== '' );
		}

		/**
		 *	The block of a background: the picture of a slot - or the image field of the element, where it names one -, with an empty alt, and its focus
		 *
		 *	@param		array			$background		slot, focus - or field, focus
		 *	@param		string		$class				nino-section-bg or nino-grid-bg
		 *
		 *	@return 	string
		 */
		private static function _background( array $background, string $class ): string {

			$focus = (int) ( $background['focus'] ?? 0 );

			return self::_fill( 'background', [
				'[[class]]'	=> self::_class( [ $class ] + ( $focus >= 1 && $focus <= 9 ? [ 1 => 'nino-img-focus--'. $focus ] : [] ) ),
				'[[slot]]'	=> self::_escape( (string) ( (string) ( $background['field'] ?? '' ) !== '' ? $background['field'] : $background['slot'] ) ),
			] );
		}

		/**
		 *	@param		array			$s
		 *
		 *	@return 	array										The classes of the animation, none for none
		 */
		private static function _animationClasses( array $s ): array {

			if( $s['vpa'] === null )
				return [];

			$class = [ 'nino-vpa' ];

			if( (string) $s['vpa'] !== '' )
				$class[] = 'nino-vpa--'. $s['vpa'];

			if( (string) $s['vpaSpeed'] !== '' )
				$class[] = 'nino-vpa--speed-'. $s['vpaSpeed'];

			if( (string) $s['vpaMode'] !== '' )
				$class[] = 'nino-vpa--'. $s['vpaMode'];

			return $class;
		}

		/**
		 *	@param		array			$s
		 *
		 *	@return 	string									The two attributes of the animation, each with a space in front
		 */
		private static function _animationAttributes( array $s ): string {

			$attributes = '';

			foreach( [ 'data-vpa-delay' => 'vpaDelay', 'data-vpa-duration' => 'vpaDuration' ] as $attribute => $key )
				if( (string) ( $s[$key] ?? '' ) !== '' )
					$attributes .= ' '. $attribute. '="'. self::_escape( (string) $s[$key] ). '"';

			return $attributes;
		}

		/**
		 *	@param		string		$custom				The custom classes, one string
		 *
		 *	@return 	array										Each one
		 */
		private static function _custom( string $custom ): array {
			return preg_split( '/\s+/', trim( $custom ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		}

		/**
		 *	A value that is written into a call: the shortcode
		 *	syntax has no escape for a quote, a bracket or a line break, so
		 *	one that holds any of them never gets as far as the file
		 *	(Document::problems() refuses it first, with the block named)
		 *
		 *	@param		string		$value
		 *	@param		string		$name					The call, for the message
		 *	@param		string		$attribute		The attribute, for the message
		 *
		 *	@return 	string									The value, as it was given
		 *
		 *	@throws		\UnexpectedValueException
		 */
		private static function _safe( string $value, string $name, string $attribute ): string {

			if( preg_match( '/["\'\[\]\r\n]/', $value ) === 1 )
				throw new \UnexpectedValueException( 'The value of "'. $attribute. '" of "'. $name. '" holds a quote, a bracket or a line break, which a call cannot carry.' );

			return $value;
		}

		/**
		 *	A value that is the address of a link, as a call carries it: as _safe() has it, and the address of a page of the site with a sub-path
		 *	([[key]]/sub-path, see Reader::address()) besides, whose brackets are the fill the kernel puts in before it reads the call
		 *
		 *	@param		string		$value
		 *	@param		string		$name					The call, for the message
		 *	@param		string		$attribute		The attribute, for the message
		 *
		 *	@return 	string									The value, as it was given
		 *
		 *	@throws		\UnexpectedValueException
		 */
		private static function _address( string $value, string $name, string $attribute ): string {
			return Reader::address( $value ) !== null ? $value : self::_safe( $value, $name, $attribute );
		}

		/**
		 *	@param		string		$value
		 *
		 *	@return 	string									Escaped for an attribute of a tag
		 */
		private static function _escape( string $value ): string {
			return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
		}

		/**
		 *	A class attribute's value
		 *
		 *	@param		array			$classes
		 *
		 *	@return 	string									Escaped, and without a class twice
		 */
		private static function _class( array $classes ): string {
			return self::_escape( implode( ' ', array_unique( $classes ) ) );
		}

		/**
		 *	One component as the lines it is written in, at an indent: its call on a line of its own
		 *	and - for a component that holds components - each of its children one indent deeper on a
		 *	line of their own and its closing tag on a line of its own, at the indent of the call
		 *
		 *	@param		array			$node					The component of the model
		 *	@param		array			$registry
		 *	@param		string		$indent				The tabs in front of the call
		 *
		 *	@return 	array										The lines
		 */
		private static function _component( array $node, array $registry, string $indent ): array {

			$name		= (string) ( $node['name'] ?? '' );
			$schema	= (array) ( $registry['components'][$name] ?? [] );
			$lines	= [ $indent. self::_call( $node, $schema ) ];

			if( Reader::holds( $schema ) === false )
				return $lines;

			foreach( (array) ( $node['components'] ?? [] ) as $child )
				array_push( $lines, ...self::_component( (array) $child, $registry, $indent. "\t" ) );

			$lines[] = $indent. '[/'. $name. ']';

			return $lines;
		}

		/**
		 *	One call: [name source text="..." attribute="..."], and for a
		 *	component with content its closing tag after the content. An
		 *	attribute is written where it is not the schema's default
		 *
		 *	@param		array			$node					The component or the stack of the model
		 *	@param		array			$schema				Its registry entry; [] for a name the registry does not know,
		 *																	where every attribute that is not empty is written
		 *
		 *	@return 	string
		 */
		private static function _call( array $node, array $schema ): string {

			$name				= (string) ( $node['name'] ?? '' );
			$defaults		= Reader::defaults( $schema );
			$attributes	= (array) ( $node['attributes'] ?? [] );
			$call				= '['. $name;

			// The radius of a surface is the first class of its call, then the hover of a picture, then - on a card - how far in from either side its picture starts, left
			// then right, then the six sides of the spacing of any component, then the classes the editor wrote (the model refuses the radius, the hover and the offsets on a
			// component that is none of the kinds they are for: see Document)
			$first = isset( Reader::RADIUS_CLASS[(string) ( $node['radius'] ?? '' )] ) === true ? [ Reader::RADIUS_CLASS[$node['radius']] ] : [];

			if( (string) ( $node['hover'] ?? '' ) === Reader::HOVER )
				$first[] = Reader::HOVER_CLASS;

			foreach( Reader::BG_SPACING as $side => $key )
				if( (string) ( $node[$key] ?? '' ) !== '' )
					$first[] = 'nino-bg-'. $side. '-'. $node[$key];

			array_push( $first, ...self::_spacing( $node, false ) );

			if( $first !== [] )
				$attributes['class'] = trim( implode( ' ', $first ). ' '. self::_string( $attributes['class'] ?? '' ) );

			// The address of a link may lead to a page of the site and go on from it (see Reader::address()): the one value a call carries the brackets of a fill in
			if( (string) ( $node['source'] ?? '' ) !== '' )
				$call .= ' '. ( ( $schema['source'] ?? '' ) === 'href' ? self::_address( (string) $node['source'], $name, 'source' ) : self::_safe( (string) $node['source'], $name, 'source' ) );

			// A line of free text carries the fills of the element, [[name]], which the kernel puts in before it reads the call
			if( is_string( $node['text'] ?? null ) === true )
				$call .= ' text="'. ( Reader::fielded( $schema ) === true && Reader::lineText( $node['text'] ) === true ? Reader::lineEncode( $node['text'] ) : self::_safe( $node['text'], $name, 'text' ) ). '"';

			// The schema's order, then what it does not know
			foreach( array_merge( array_keys( $defaults ), array_diff( array_keys( $attributes ), array_keys( $defaults ) ) ) as $attribute ) {

				if( array_key_exists( $attribute, $attributes ) === false )
					continue;

				$value = self::_string( $attributes[$attribute] );

				if( $value === ( $defaults[$attribute] ?? '' ) )
					continue;

				// The text of a loop - the schema of a loop says its cell - carries the brackets of its fields as entities, as the call is read up to its first closing bracket
				$text = $attribute === Reader::STACK_TEXT && array_key_exists( 'cell', $schema ) === true && is_array( $schema['attributes'][Reader::STACK_TEXT] ?? null ) === true;

				$call .= ' '. self::_safe( (string) $attribute, $name, 'attribute name' ). '="'. ( $text === true ? ( Reader::lineText( $value ) === true ? Reader::entityEncode( $value ) : self::_safe( $value, $name, (string) $attribute ) ) : ( in_array( (string) $attribute, Reader::addresses( $schema ), true ) === true ? self::_address( $value, $name, (string) $attribute ) : self::_safe( $value, $name, (string) $attribute ) ) ). '"';
			}

			$call .= ']';

			// The content of a component that holds components is its children, which _component() writes
			if( array_key_exists( 'content', $node ) === true && Reader::holds( $schema ) === false )
				$call .= (string) $node['content']. '[/'. $name. ']';

			return $call;
		}

		/**
		 *	An attribute value as a shortcode carries it
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	string									A bool is '1' or '0'
		 */
		private static function _string( mixed $value ): string {
			return is_bool( $value ) === true ? ( $value === true ? '1' : '0' ) : (string) $value;
		}

		/**
		 *	A fragment of markup, filled. The values are escaped by whoever
		 *	hands them over: the classes by _class(), the ids and slots are
		 *	checked as slugs and text keys before they get here (see
		 *	Document::problems()), and the attributes are built escaped
		 *
		 *	@param		string		$name					The file under templates/, without .tpl
		 *	@param		array			$tokens				[[token]] => value
		 *
		 *	@return 	string
		 */
		private static function _fill( string $name, array $tokens ): string {

			if( isset( self::$fragments[$name] ) === false ) {

				$text = @file_get_contents( dirname( __DIR__ ). '/templates/'. $name. '.tpl' );

				if( is_string( $text ) === false || trim( $text ) === '' )
					trigger_error( 'Nino: the template features/Builder/templates/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

				self::$fragments[$name] = is_string( $text ) === true ? rtrim( $text, "\n" ) : '';
			}

			// The tokens that bring built markup last: [[attributes]] is the only one
			uksort( $tokens, static fn( string $a, string $b ): int => ( $a === '[[attributes]]' ) <=> ( $b === '[[attributes]]' ) );

			return str_replace( array_keys( $tokens ), array_values( $tokens ), self::$fragments[$name] );
		}
	}

}
