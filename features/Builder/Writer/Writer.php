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
	 *											order (see sectionClasses() and colClasses()) with the
	 *											custom ones last, an attribute of a call only where it is
	 *											not what the schema says it is without it, a blank line
	 *											between the blocks, one line feed at the end. The columns
	 *											that are 100 wide in all three viewports are written
	 *											nino-grid-100, any other as three classes.
	 *
	 *											The blocks stand in the wrap: the line that opens it
	 *											follows the name and the frame above, the line that closes
	 *											it comes before the frame below, and nothing inside is
	 *											indented for it - a file the builder had not wrapped is
	 *											wrapped with two lines and is otherwise what it was. Its
	 *											classes (see wrapClasses()) are the kind of animation of the
	 *											template; the head line the builder wrote before the wrap
	 *											is not written any more.
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
		 *	One section
		 *
		 *	@param		array			$section
		 *	@param		array			$registry
		 *
		 *	@return 	string
		 */
		private static function _section( array $section, array $registry ): string {

			$settings	= (array) ( $section['settings'] ?? [] ) + Reader::sectionDefaults();
			$lines		= [];

			$attributes = '';

			// The height is always written, at 90 as well: a template says what it is, and nobody has to know the 90 Nino.ui.js takes without it
			if( $settings['cover'] !== null )
				$attributes .= ' data-cover-height="'. (int) $settings['cover']. '"';

			$attributes .= self::_animationAttributes( $settings );

			$lines[] = self::_fill( 'section-open', [ '[[id]]' => self::_escape( (string) ( $section['id'] ?? '' ) ), '[[class]]' => self::_class( self::sectionClasses( $settings ) ), '[[attributes]]' => $attributes ] );

			if( self::_pictured( $section ) === true )
				$lines[] = "\t". self::_background( (array) $section['background'], 'nino-section-bg' );

			$lines[] = "\t". self::_fill( 'div-open', [ '[[class]]' => self::_class( self::rowClasses( $settings ) ) ] );

			foreach( (array) ( $section['cols'] ?? [] ) as $col ) {

				$lines[] = "\t\t". self::_fill( 'div-open', [ '[[class]]' => self::_class( self::colClasses( (array) $col ) ) ] );

				if( self::_pictured( (array) $col ) === true )
					$lines[] = "\t\t\t". self::_background( (array) $col['background'], 'nino-grid-bg' );

				if( is_array( $col['stack'] ?? null ) === true ) {

					$lines[] = "\t\t\t". self::_call( (array) $col['stack'], $registry['stacks'][$col['stack']['name'] ?? ''] ?? [] );

					foreach( (array) ( $col['components'] ?? [] ) as $component )
						array_push( $lines, ...self::_component( (array) $component, $registry, "\t\t\t\t" ) );

					$lines[] = "\t\t\t[/". (string) ( $col['stack']['name'] ?? '' ). ']';
				}
				else
					foreach( (array) ( $col['components'] ?? [] ) as $component )
						array_push( $lines, ...self::_component( (array) $component, $registry, "\t\t\t" ) );

				$lines[] = "\t\t</div>";
			}

			$lines[] = "\t</div>";
			$lines[] = '</section>';

			return implode( "\n", $lines );
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
		 *
		 *	@return 	array										Class names
		 */
		public static function sectionClasses( array $s ): array {

			$s			= $s + Reader::sectionDefaults();
			$class	= [ 'nino-section' ];

			// The full width, then the full height: two switches, either or both
			foreach( Reader::SECTION_SIZE as $size )
				if( $s[$size] === true )
					$class[] = 'nino-section--'. $size;

			if( (string) $s['color'] !== '' )
				$class[] = 'nino-section--'. $s['color'];

			if( (string) $s['border'] !== '' )
				$class[] = 'nino-section--border-'. $s['border'];

			if( $s['borderPrimary'] === true )
				$class[] = 'nino-section--border-primary';

			// The scrim is the cover's where the section is a cover - Nino.css gives that class the ink of a black section as well - else the parallax's
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

			// The place of the content is the row's in a cover or a parallax: on a section that is neither the class would be idle
			if( (string) $s['contentPos'] !== '' && ( $s['cover'] !== null || $s['parallax'] === true ) )
				$class[] = 'nino-cover-'. $s['contentPos'];

			foreach( Reader::SPACING as $space )
				if( (string) $s[$space] !== '' )
					$class[] = 'nino-'. $space. '-'. $s[$space];

			if( (string) $s['text'] !== '' )
				$class[] = 'nino-text-'. $s['text'];

			return array_merge( $class, self::_animationClasses( $s ), self::_custom( (string) $s['custom'] ) );
		}

		/**
		 *	The classes of the row
		 *
		 *	@param		array			$s						The section's settings
		 *
		 *	@return 	array
		 */
		public static function rowClasses( array $s ): array {

			$s			= $s + Reader::sectionDefaults();
			$class	= [ 'nino-grid-row' ];

			if( (string) $s['row'] !== '' )
				$class[] = 'nino-grid-row--'. $s['row'];

			if( (string) $s['rowAlign'] !== '' )
				$class[] = 'nino-grid-'. $s['rowAlign'];

			return array_merge( $class, self::_custom( (string) $s['rowCustom'] ) );
		}

		/**
		 *	The classes of a column
		 *
		 *	@param		array			$col
		 *
		 *	@return 	array
		 */
		public static function colClasses( array $col ): array {

			$col		= $col + Reader::colDefaults();
			$width	= [];

			foreach( Reader::VIEWPORTS as $viewport )
				$width[$viewport] = (int) ( $col['width'][$viewport] ?? 100 );

			$class = $width === [ 's' => 100, 'm' => 100, 'l' => 100 ]
				? [ 'nino-grid-100' ]
				: [ 'nino-grid-s-'. $width['s'], 'nino-grid-m-'. $width['m'], 'nino-grid-l-'. $width['l'] ];

			foreach( Reader::VIEWPORTS as $viewport )
				if( ( $col['hidden'][$viewport] ?? false ) === true )
					$class[] = 'nino-hide-'. $viewport;

			if( (string) $col['color'] !== '' )
				$class[] = 'nino-grid--'. $col['color'];

			// Only a picture is darkened: a column without one has no dimming to write
			if( $col['dim'] === true && self::_pictured( $col ) === true )
				$class[] = 'nino-grid--dim';

			if( (string) $col['stackAlign'] !== '' )
				$class[] = 'nino-stack-'. $col['stackAlign'];

			if( (string) $col['stackGap'] !== '' )
				$class[] = 'nino-stack-gap-'. $col['stackGap'];

			if( (string) $col['text'] !== '' )
				$class[] = 'nino-text-'. $col['text'];

			// The padding, then the margin above and the margin below
			foreach( Reader::COL_SPACING as $space )
				if( (string) $col[$space] !== '' )
					$class[] = 'nino-'. $space. '-'. $col[$space];

			return array_merge( $class, self::_animationClasses( $col ), self::_custom( (string) $col['custom'] ) );
		}

		/**
		 *	@param		array			$node					A section or a column
		 *
		 *	@return 	bool										Whether it has a background that names a slot
		 */
		private static function _pictured( array $node ): bool {
			return is_array( $node['background'] ?? null ) === true && (string) ( $node['background']['slot'] ?? '' ) !== '';
		}

		/**
		 *	The block of a background: the picture of a slot, with an empty alt, and its focus
		 *
		 *	@param		array			$background		slot, focus
		 *	@param		string		$class				nino-section-bg or nino-grid-bg
		 *
		 *	@return 	string
		 */
		private static function _background( array $background, string $class ): string {

			$focus = (int) ( $background['focus'] ?? 0 );

			return self::_fill( 'background', [
				'[[class]]'	=> self::_class( [ $class ] + ( $focus >= 1 && $focus <= 9 ? [ 1 => 'nino-img-focus--'. $focus ] : [] ) ),
				'[[slot]]'	=> self::_escape( (string) $background['slot'] ),
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

			if( (string) ( $node['source'] ?? '' ) !== '' )
				$call .= ' '. self::_safe( (string) $node['source'], $name, 'source' );

			if( is_string( $node['text'] ?? null ) === true )
				$call .= ' text="'. self::_safe( $node['text'], $name, 'text' ). '"';

			// The schema's order, then what it does not know
			foreach( array_merge( array_keys( $defaults ), array_diff( array_keys( $attributes ), array_keys( $defaults ) ) ) as $attribute ) {

				if( array_key_exists( $attribute, $attributes ) === false )
					continue;

				$value = self::_string( $attributes[$attribute] );

				if( $value !== ( $defaults[$attribute] ?? '' ) )
					$call .= ' '. self::_safe( (string) $attribute, $name, 'attribute name' ). '="'. self::_safe( $value, $name, (string) $attribute ). '"';
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
