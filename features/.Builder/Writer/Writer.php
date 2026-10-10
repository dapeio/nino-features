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
	 *											calls three - in a stack four, the stack's own tags three),
	 *											one call per line, the classes of every tag in a fixed
	 *											order (see sectionClasses() and colClasses()) with the
	 *											custom ones last, an attribute of a call only where it is
	 *											not what the schema says it is without it, a blank line
	 *											between the blocks, one line feed at the end. The columns
	 *											that are 100 wide in all three viewports are written
	 *											nino-grid-100, any other as three classes.
	 *
	 *											A foreign block is the one thing written as it came: one
	 *											the builder made, or that came in its markers, gets the
	 *											markers (the Reader finds it again without analysing it);
	 *											one that was read as a section and failed has a reason, and
	 *											is written exactly as it was read, without markers, so that
	 *											the file does not change where nobody changed it.
	 *
	 *											The markup of a section is in templates/, one fragment each
	 *											- the tags are the same text wherever a section is written,
	 *											and filled with values that are escaped first (AGENTS.md,
	 *											"Markup belongs in a template"). What is composed here are
	 *											the shortcode calls, which are no markup, and the closing
	 *											tags.
	 *
	 *	@package						Dape/Nino
	 *	@author							David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Writer {

		// The fragments, read once per request: name => text
		private static array $fragments = [];

		/**
		 *	The model written as a page template
		 *
		 *	@param		array			$model				See Reader
		 *	@param		array			$registry			[ 'components' => name => schema, 'stacks' => name => schema ]
		 *
		 *	@return 	string										'' for a page with no name, no frame and no block - but a template animation (vpa)
		 *																	alone produces a head line
		 */
		public static function write( array $model, array $registry ): string {

			$parts	= [];
			$head		= [];

			if( trim( (string) ( $model['name'] ?? '' ) ) !== '' )
				$head[] = '<!-- nino:template-name '. trim( (string) $model['name'] ). ' -->';

			// The classes as they were written, and nothing at all for none
			if( is_string( $model['vpa'] ?? null ) === true ) {

				$vpa = Reader::vpaClasses( $model['vpa'] );

				if( $vpa === false )
					throw new \UnexpectedValueException( 'The animation of the template is none of the classes of nino-vpa.' );

				if( $vpa !== null )
					$head[] = '<!-- nino:template-vpa '. $vpa. ' -->';
			}

			if( (string) ( $model['header'] ?? '' ) !== '' )
				$head[] = '[template /templates/'. $model['header']. ']';

			if( $head !== [] )
				$parts[] = implode( "\n", $head );

			foreach( (array) ( $model['blocks'] ?? [] ) as $block )
				$parts[] = ( $block['kind'] ?? '' ) === 'section' ? self::_section( $block, $registry ) : self::_foreign( $block );

			if( (string) ( $model['footer'] ?? '' ) !== '' )
				$parts[] = '[template /templates/'. $model['footer']. ']';

			return $parts === [] ? '' : implode( "\n\n", $parts ). "\n";
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

			if( $settings['cover'] !== null && (int) $settings['cover'] > 0 )
				$attributes .= ' data-cover-height="'. (int) $settings['cover']. '"';

			$attributes .= self::_animationAttributes( $settings );

			$lines[] = self::_fill( 'section-open', [ '[[id]]' => self::_escape( (string) ( $section['id'] ?? '' ) ), '[[class]]' => self::_class( self::sectionClasses( $settings ) ), '[[attributes]]' => $attributes ] );

			if( is_array( $section['background'] ?? null ) === true && (string) ( $section['background']['slot'] ?? '' ) !== '' ) {

				$focus = (int) ( $section['background']['focus'] ?? 0 );

				$lines[] = "\t". self::_fill( 'section-bg', [
					'[[class]]'	=> self::_class( [ 'nino-section-bg' ] + ( $focus >= 1 && $focus <= 9 ? [ 1 => 'nino-img-focus--'. $focus ] : [] ) ),
					'[[slot]]'	=> self::_escape( (string) $section['background']['slot'] ),
				] );
			}

			$lines[] = "\t". self::_fill( 'div-open', [ '[[class]]' => self::_class( self::rowClasses( $settings ) ) ] );

			foreach( (array) ( $section['cols'] ?? [] ) as $col ) {

				$lines[] = "\t\t". self::_fill( 'div-open', [ '[[class]]' => self::_class( self::colClasses( (array) $col ) ) ] );

				if( is_array( $col['stack'] ?? null ) === true ) {

					$lines[] = "\t\t\t". self::_call( (array) $col['stack'], $registry['stacks'][$col['stack']['name'] ?? ''] ?? [] );

					foreach( (array) ( $col['components'] ?? [] ) as $component )
						$lines[] = "\t\t\t\t". self::_call( (array) $component, $registry['components'][$component['name'] ?? ''] ?? [] );

					$lines[] = "\t\t\t[/". (string) ( $col['stack']['name'] ?? '' ). ']';
				}
				else
					foreach( (array) ( $col['components'] ?? [] ) as $component )
						$lines[] = "\t\t\t". self::_call( (array) $component, $registry['components'][$component['name'] ?? ''] ?? [] );

				$lines[] = "\t\t</div>";
			}

			$lines[] = "\t</div>";
			$lines[] = '</section>';

			return implode( "\n", $lines );
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

			if( (string) $s['width'] !== '' )
				$class[] = 'nino-section--'. $s['width'];

			if( (string) $s['color'] !== '' )
				$class[] = 'nino-section--'. $s['color'];

			if( (string) $s['border'] !== '' )
				$class[] = 'nino-section--border-'. $s['border'];

			if( isset( Reader::IMAGE[(string) $s['image']] ) === true ) {

				$class[] = Reader::IMAGE[$s['image']];

				if( $s['dim'] === true )
					$class[] = Reader::IMAGE[$s['image']]. '--dim';
			}

			if( (string) $s['imagePos'] !== '' )
				$class[] = 'nino-cover-'. $s['imagePos'];

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

			if( (string) $col['stackAlign'] !== '' )
				$class[] = 'nino-stack-'. $col['stackAlign'];

			if( (string) $col['stackGap'] !== '' )
				$class[] = 'nino-stack-gap-'. $col['stackGap'];

			if( (string) $col['text'] !== '' )
				$class[] = 'nino-text-'. $col['text'];

			return array_merge( $class, self::_animationClasses( $col ), self::_custom( (string) $col['custom'] ) );
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

			if( array_key_exists( 'content', $node ) === true )
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
