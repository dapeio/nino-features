<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Builder\Reader	see features/Builder/Builder.php for the feature's own
 *												docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino								A compact filesystembased php framework
	 *	Reader							A page template in, the model out: the head, the blocks in
	 *											their order, the foot. A pure function of the source and
	 *											the registry of components - no $appData, no file, no
	 *											state - so the tests drive it directly and the panel can
	 *											rely on one answer for one file.
	 *
	 *											The grammar it reads is the one the Writer writes: a block
	 *											is a section (a <section>, an optional background, exactly
	 *											one row, columns, and in each column either component
	 *											calls or one stack call) or a foreign block. A section is
	 *											read whole or not at all - whatever it holds that the model
	 *											cannot keep (a second row, a column without a width, a
	 *											shortcode that is no component, markup next to a stack,
	 *											a nested call, an attribute the schema does not know) makes
	 *											the whole section a foreign block, with the line and the
	 *											reason it failed at. A foreign block runs from where the
	 *											reading failed to the next section that is read, and is
	 *											written back byte for byte; one wrapped in the markers
	 *											<!-- nino:html --> ... <!-- /nino:html --> is recognised
	 *											without being analysed, and carries no reason.
	 *
	 *											The shortcode calls are read the way \Nino\Html reads them:
	 *											the same expression for the call and its content, the same
	 *											one for the arguments - a first positional argument, named
	 *											arguments in double quotes. A call whose arguments that
	 *											expression would read differently than they were written (a
	 *											quote of the other kind, two spaces, a stray '=') is not
	 *											kept, because writing it back would change it.
	 *
	 *											THE MODEL, as it runs between the panel and the server
	 *											(every key is always present in what read() answers; the
	 *											Writer takes a missing one as its default):
	 *
	 *											document
	 *											  file      string  page-home; read() leaves it '', Document sets it
	 *											  name      string  the nino:template-name comment, '' where none
	 *											  header    string  html-header, or any html-header* template; '' for none
	 *											  footer    string  html-footer, ditto
	 *											  blocks    list    section and html, in the order of the file
	 *
	 *											html (a foreign block)
	 *											  kind      'html'
	 *											  source    string  byte for byte, without the blank lines around it
	 *											  reason    null    wrapped in the markers - written back with them -
	 *											            array   read as a section and failed: line (int, the first
	 *											                    line of the attempt), code (see REASONS), detail
	 *											                    (a name where the text has one, else ''), text
	 *											                    (the sentence, in English) - written back as it was
	 *											  edited    bool    only ever sent by the panel, never read: the source
	 *											                    was changed, so a save reads it as a section again
	 *
	 *											section
	 *											  kind      'section'
	 *											  id        string  slug, unique in the page; the third segment of the
	 *											                    text keys of its components
	 *											  renamedFrom string only ever sent by the panel: the id it had, so a
	 *											                    save moves the keys of the section
	 *											  settings  map     of the section and its row, defaults in sectionDefaults()
	 *											    row         ''|narrow|wide        nino-grid-row--<x>
	 *											    rowAlign    ''|center|middle|bottom   nino-grid-<x>
	 *											    rowCustom   string                classes of the row that are none of these
	 *											    width       ''|fullwidth|fullheight   nino-section--<x>
	 *											    color       ''|alt|tint|dark|black|primary|brand-alt
	 *											    border      ''|1|2|3|primary      nino-section--border-<x>
	 *											    image       ''|cover|parallax     nino-cover, nino-parallex
	 *											    dim         bool                  nino-cover--dim, nino-parallex--dim
	 *											    imagePos    ''|top|center|bottom  nino-cover-<x>
	 *											    cover       int|null              data-cover-height
	 *											    mt mb pt pb ''|0..6               nino-mt-<n> ...
	 *											    text        ''|left|center|right  nino-text-<x>
	 *											    vpa         null|string           null: none; '': nino-vpa; else the
	 *											                                      effect, nino-vpa--<x>
	 *											    vpaSpeed    ''|fast|medium|slow   nino-vpa--speed-<x>
	 *											    vpaMode     ''|repeat|visible|visible-once
	 *											    vpaDelay    string                data-vpa-delay, a css time
	 *											    vpaDuration string                data-vpa-duration
	 *											    custom      string                every other class of the section
	 *											  background null|map  slot (string, a text key grammar uri), focus
	 *											            (int 1..9|null); an image call with alt=""
	 *											  cols      list    the columns of the one row
	 *
	 *											column
	 *											  width     map     s, m, l: 25 33 50 66 75 100 (ints), what each
	 *											                    viewport ends up with, mobile first: nino-grid-100
	 *											                    is all three, nino-grid-m-50 holds from m up
	 *											  hidden    map     s, m, l => true, only the hidden ones (none
	 *											                    hidden is [] in php, and in the json)
	 *											  text      ''|left|center|right
	 *											  stackAlign ''|start|center|end   nino-stack-<x>
	 *											  stackGap  ''|0..6                nino-stack-gap-<n>
	 *											  vpa, vpaSpeed, vpaMode, vpaDelay, vpaDuration   as in settings
	 *											  custom    string
	 *											  stack     null    the column is a static stack of components
	 *											            map     name, source (the type's uri), attributes - the
	 *											                    registered stack that loops its components
	 *											  components list  of component, in order; inside the stack's content
	 *											                    where the column has one
	 *
	 *											component
	 *											  name      string  a registered component
	 *											  source    string  the first argument: a text key, an image slot, a
	 *											                    field of the type inside a stack, '' for none
	 *											  text      null|string   the fixed value, text="..."
	 *											  attributes map    every attribute of the schema, a string each - the
	 *											                    default where the call does not set it
	 *											  content   string  only for a component whose source is 'content'
	 *											  create    map     only ever sent by the panel: what the key or the slot
	 *											                    this source names is made with at save
	 *
	 *	@package						Dape/Nino
	 *	@author							David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Reader {

		// The vocabulary of Nino.css the model keeps in settings of their own,
		// the order the Writer writes them in - everything else is 'custom'
		public const array ROW = [ 'narrow', 'wide' ];
		public const array ROW_ALIGN = [ 'center', 'middle', 'bottom' ];
		public const array SECTION_WIDTH = [ 'fullwidth', 'fullheight' ];
		public const array COLOR = [ 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ];
		public const array BORDER = [ '1', '2', '3', 'primary' ];
		public const array IMAGE = [ 'cover' => 'nino-cover', 'parallax' => 'nino-parallex' ];
		public const array IMAGE_POS = [ 'top', 'center', 'bottom' ];
		public const array SPACING = [ 'mt', 'mb', 'pt', 'pb' ];
		public const array TEXT = [ 'left', 'center', 'right' ];
		public const array EFFECTS = [
			'blur-soft', 'blur-medium', 'blur-hard', 'flip-soft', 'flip-medium', 'flip-hard',
			'slide-left-soft', 'slide-left-medium', 'slide-left-hard', 'slide-right-soft', 'slide-right-medium', 'slide-right-hard',
			'zoom-soft', 'zoom-medium', 'zoom-hard', 'zoom-out-soft', 'zoom-out-medium', 'zoom-out-hard',
		];
		public const array SPEEDS = [ 'fast', 'medium', 'slow' ];
		public const array MODES = [ 'repeat', 'visible', 'visible-once' ];
		public const array WIDTHS = [ '25', '33', '50', '66', '75', '100' ];
		public const array VIEWPORTS = [ 's', 'm', 'l' ];
		public const array STACK_ALIGN = [ 'start', 'center', 'end' ];

		// Why a section is not read: the code of a failure, and the sentence
		// that says it. %s is the detail, where the failure has one
		public const array REASONS = [
			'not-a-section'				=> 'a block that is no section',
			'marker-unclosed'			=> 'a <!-- nino:html --> marker that is never closed',
			'attribute-syntax'		=> 'a tag whose attributes are not written as name="value"',
			'section-attribute'		=> 'the section has the attribute %s, which the builder does not keep',
			'section-id'					=> 'the section has no id that is a slug',
			'duplicate-id'				=> 'the id %s is the id of an earlier section',
			'section-class'				=> 'the section has no class nino-section',
			'class-token'					=> 'the class %s has characters the builder does not keep',
			'attribute-value'			=> 'the attribute %s has a value the builder does not keep',
			'background-class'		=> 'the background block has the class %s, which the builder does not keep',
			'background-image'		=> 'the background block is not one image call with an empty alt',
			'no-row'							=> 'the section has no row',
			'second-row'					=> 'the section has more than one row',
			'section-content'			=> 'the section holds markup next to its row',
			'row-attribute'				=> 'the row has the attribute %s, which the builder does not keep',
			'row-content'					=> 'the row holds something that is no column',
			'no-cols'							=> 'the row has no column',
			'nested-row'					=> 'a row inside a row',
			'col-attribute'				=> 'the column has the attribute %s, which the builder does not keep',
			'col-width'						=> 'the column has no width class for every viewport',
			'col-markup'					=> 'the column holds markup next to its component calls: %s',
			'unknown-shortcode'		=> 'the shortcode %s is no registered component or stack',
			'call-arguments'			=> 'a call whose arguments are not written the way the kernel reads them: %s',
			'unknown-attribute'		=> 'the attribute %s is none of the component\'s',
			'component-content'		=> 'the component %s takes no content',
			'nested-call'					=> 'a call inside the content of %s',
			'stack-source'				=> 'a stack without the type it loops',
			'stack-neighbour'			=> 'a stack with other components in its column',
			'stack-in-stack'			=> 'a stack inside a stack',
			'stack-content'				=> 'the stack holds markup next to its component calls: %s',
		];

		// The marker the builder wraps a foreign block in, and its end
		public const string MARKER = '<!-- nino:html -->';
		public const string MARKER_END = '<!-- /nino:html -->';

		/**
		 *	The settings of a section and its row, as a section without a
		 *	single modifier has them
		 *
		 *	@return 	array
		 */
		public static function sectionDefaults(): array {
			return [
				'row' => '', 'rowAlign' => '', 'rowCustom' => '', 'width' => '', 'color' => '', 'border' => '',
				'image' => '', 'dim' => false, 'imagePos' => '', 'cover' => null,
				'mt' => '', 'mb' => '', 'pt' => '', 'pb' => '', 'text' => '',
			] + self::animationDefaults() + [ 'custom' => '' ];
		}

		/**
		 *	A column's own settings, as a column without a modifier has them -
		 *	its width and hidden, which have no default, left out
		 *
		 *	@return 	array
		 */
		public static function colDefaults(): array {
			return [ 'text' => '', 'stackAlign' => '', 'stackGap' => '' ] + self::animationDefaults() + [ 'custom' => '' ];
		}

		/**
		 *	@return 	array										The animation settings of a section or a column, off
		 */
		public static function animationDefaults(): array {
			return [ 'vpa' => null, 'vpaSpeed' => '', 'vpaMode' => '', 'vpaDelay' => '', 'vpaDuration' => '' ];
		}

		/**
		 *	The attributes of a component or a stack and what each is worth
		 *	where a call does not set it: the 'defaults' the Document puts in
		 *	the registry (the kernel's Components::defaults(), so a stack has
		 *	the loop's and the grid's too), else those the schema declares
		 *
		 *	@param		array			$schema				One entry of the registry
		 *
		 *	@return 	array										attribute => default, strings
		 */
		public static function defaults( array $schema ): array {

			if( is_array( $schema['defaults'] ?? null ) === true )
				return $schema['defaults'];

			$defaults = [];

			foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared ) {
				$default = is_array( $declared ) === true ? ( $declared['default'] ?? '' ) : '';
				$defaults[$attribute] = is_bool( $default ) === true ? ( $default === true ? '1' : '0' ) : (string) $default;
			}

			return $defaults + [ 'class' => '' ];
		}

		/**
		 *	A page template read into its model
		 *
		 *	@param		string		$source				The file
		 *	@param		array			$registry			[ 'components' => name => schema, 'stacks' => name => schema ]
		 *
		 *	@return 	array										The model, see above
		 */
		public static function read( string $source, array $registry ): array {

			$model	= [ 'file' => '', 'name' => '', 'header' => '', 'footer' => '', 'blocks' => [] ];
			$pos		= 0;

			if( preg_match( '~\G\s*<!--[\t ]*nino:template-name[\t ]+([^\r\n<>]+?)[\t ]*-->[\t ]*(?:\r?\n|$)~', $source, $match, 0, $pos ) === 1 ) {
				$model['name'] = $match[1];
				$pos += strlen( $match[0] );
			}

			if( preg_match( '~\G\s*\[template /templates/(html-header(?:-[a-z0-9]+)*)\][\t ]*(?:\r?\n|$)~', $source, $match, 0, $pos ) === 1 ) {
				$model['header'] = $match[1];
				$pos += strlen( $match[0] );
			}

			$end = strlen( $source );

			if( preg_match( '~(?<![^\n])[\t ]*\[template /templates/(html-footer(?:-[a-z0-9]+)*)\]\s*\z~', $source, $match, PREG_OFFSET_CAPTURE, $pos ) === 1 ) {
				$model['footer'] = $match[1][0];
				$end = $match[0][1];
			}

			$body		= substr( $source, 0, $end );
			$ids		= [];
			$pending = null;

			while( true ) {

				$start = self::_lineStart( $body, $pos );

				if( $start >= strlen( $body ) )
					break;

				$pos = $start;

				try {

					// A block the builder wrapped itself: recognised, not analysed
					if( preg_match( '~\G[\t ]*<!--[\t ]*nino:html[\t ]*-->~', $body, $open, 0, $pos ) === 1 ) {

						if( preg_match( '~<!--[\t ]*/nino:html[\t ]*-->~', $body, $closing, PREG_OFFSET_CAPTURE, $pos + strlen( $open[0] ) ) !== 1 )
							throw self::_fail( 'marker-unclosed', '', $pos );

						self::_flush( $model, $pending, $body, $pos );

						$inner = substr( $body, $pos + strlen( $open[0] ), $closing[0][1] - $pos - strlen( $open[0] ) );
						$inner = (string) preg_replace( '~\A\r?\n~', '', $inner );
						$inner = (string) preg_replace( '~\r?\n[\t ]*\z~', '', $inner );

						$model['blocks'][] = [ 'kind' => 'html', 'source' => $inner, 'reason' => null ];
						$pos = $closing[0][1] + strlen( $closing[0][0] );

						continue;
					}

					[ $section, $after ] = self::_section( $body, $pos, $registry, $ids );

					self::_flush( $model, $pending, $body, $pos );

					$ids[$section['id']] = true;
					$model['blocks'][] = $section;
					$pos = $after;

					continue;
				}
				catch( \RuntimeException $e ) {

					if( $pending === null )
						$pending = [ 'start' => $pos, 'reason' => self::_reason( $body, $e ) ];
				}

				$candidate = self::_candidate( $body, $pos );

				if( $candidate === null ) {
					self::_flush( $model, $pending, $body, strlen( $body ) );
					break;
				}

				$pos = $candidate;
			}

			self::_flush( $model, $pending, $body, strlen( $body ) );

			return $model;
		}

		/**
		 *	One block as a section, or why it is none - the way the Document
		 *	reads a block again after it was edited
		 *
		 *	@param		string		$source				The block
		 *	@param		array			$registry			See read()
		 *	@param		array			$taken				Ids the page has already: id => true
		 *
		 *	@return 	array										[ the section, null ] or [ null, the reason ]
		 */
		public static function readSection( string $source, array $registry, array $taken = [] ): array {

			try {
				[ $section, $end ] = self::_section( $source, self::_lineStart( $source, 0 ), $registry, $taken );
			}
			catch( \RuntimeException $e ) {
				return [ null, self::_reason( $source, $e ) ];
			}

			if( trim( substr( $source, $end ) ) !== '' )
				return [ null, self::_reason( $source, new \RuntimeException( 'section-content', $end ) ) ];

			return [ $section, null ];
		}

		/**
		 *	Close the foreign block that is being collected, if there is one:
		 *	from where the reading failed to where it is now
		 *
		 *	@param		array 		&$model				The model so far
		 *	@param		array|null &$pending			[ start, reason ]
		 *	@param		string		$body					The source
		 *	@param		int				$until				Where the next block starts
		 *
		 *	@param-out		null							$pending
		 *
		 *	@return 	void
		 */
		private static function _flush( array &$model, ?array &$pending, string $body, int $until ): void {

			if( $pending === null )
				return;

			$source = rtrim( substr( $body, $pending['start'], $until - $pending['start'] ), " \t\r\n" );

			if( $source !== '' )
				$model['blocks'][] = [ 'kind' => 'html', 'source' => $source, 'reason' => $pending['reason'] ];

			$pending = null;
		}

		/**
		 *	The start of the line that holds the first character that is no
		 *	whitespace at or after an offset - so a block keeps the
		 *	indentation of its first line
		 *
		 *	@param		string		$text
		 *	@param		int				$pos
		 *
		 *	@return 	int											strlen() where only whitespace is left
		 */
		private static function _lineStart( string $text, int $pos ): int {

			if( preg_match( '~\S~', $text, $match, PREG_OFFSET_CAPTURE, $pos ) !== 1 )
				return strlen( $text );

			$newline = strrpos( substr( $text, 0, $match[0][1] ), "\n" );

			return max( $pos, $newline === false ? 0 : $newline + 1 );
		}

		/**
		 *	Where the next block may start after a failed one: a line that
		 *	opens a <section> or a marker, and that is not inside the section
		 *	that failed - the sections opened since and not yet closed are
		 *	counted
		 *
		 *	@param		string		$body
		 *	@param		int				$from					The start of the failed block
		 *
		 *	@return 	int|null								The start of the line, null where there is none
		 */
		private static function _candidate( string $body, int $from ): ?int {

			$newline = strpos( $body, "\n", $from );

			if( $newline === false )
				return null;

			$search = $newline + 1;

			while( preg_match( '~(?<![^\n])[\t ]*<(?:section\b|!--[\t ]*nino:html[\t ]*-->)~', $body, $match, PREG_OFFSET_CAPTURE, $search ) === 1 ) {

				$at			= (int) $match[0][1];
				$passed	= substr( $body, $from, $at - $from );
				$depth	= preg_match_all( '~<section\b~', $passed ) - preg_match_all( '~</section\s*>~', $passed );

				if( $depth <= 0 )
					return $at;

				$search = $at + strlen( $match[0][0] );
			}

			return null;
		}

		/**
		 *	The reason of a failure, as the model carries it
		 *
		 *	@param		string		$text					What was read
		 *	@param		\RuntimeException	$e		The failure: its message is code and detail, its code the offset
		 *
		 *	@return 	array
		 */
		private static function _reason( string $text, \RuntimeException $e ): array {

			[ $code, $detail ] = array_pad( explode( ':', $e->getMessage(), 2 ), 2, '' );

			$offset = min( strlen( $text ), $e->getCode() );

			return [
				'line'		=> substr_count( $text, "\n", 0, $offset ) + 1,
				'code'		=> $code,
				'detail'	=> $detail,
				'text'		=> sprintf( self::REASONS[$code] ?? '%s', $detail ),
			];
		}

		/**
		 *	@param		string		$code
		 *	@param		string		$detail
		 *	@param		int				$offset
		 *
		 *	@return 	\RuntimeException
		 */
		private static function _fail( string $code, string $detail, int $offset ): \RuntimeException {
			return new \RuntimeException( $detail === '' ? $code : $code. ':'. $detail, $offset );
		}

		/**
		 *	One section, from the line it starts at
		 *
		 *	@param		string		$body					The source
		 *	@param		int				$pos					Where the block starts
		 *	@param		array			$registry
		 *	@param		array			$ids					The ids of the sections before it: id => true
		 *
		 *	@return 	array										[ the section, where it ends ]
		 *
		 *	@throws 	\RuntimeException					The failure, see _fail()
		 */
		private static function _section( string $body, int $pos, array $registry, array $ids ): array {

			$pos = self::_skip( $body, $pos );

			[ $attributes, $pos ] = self::_open( $body, $pos, 'section', 'not-a-section' );

			foreach( array_keys( $attributes ) as $name )
				if( in_array( $name, [ 'id', 'class', 'data-cover-height', 'data-vpa-delay', 'data-vpa-duration' ], true ) === false )
					throw self::_fail( 'section-attribute', $name, $pos );

			$id = $attributes['id'] ?? '';

			if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id ) !== 1 )
				throw self::_fail( 'section-id', '', $pos );

			if( isset( $ids[$id] ) === true )
				throw self::_fail( 'duplicate-id', $id, $pos );

			$tokens = self::_tokens( $attributes['class'] ?? '', $pos );

			if( in_array( 'nino-section', $tokens, true ) === false )
				throw self::_fail( 'section-class', '', $pos );

			$settings = self::_sectionSettings( $tokens, $attributes, $pos );

			$section = [ 'kind' => 'section', 'id' => $id, 'settings' => $settings, 'background' => null, 'cols' => [] ];

			$pos = self::_skip( $body, $pos );

			[ $tag, $tagAttributes, $after ] = self::_peek( $body, $pos );

			if( $tag === 'div' && in_array( 'nino-section-bg', self::_tokens( $tagAttributes['class'] ?? '', $pos ), true ) === true ) {

				[ $section['background'], $pos ] = self::_background( $body, $pos, $registry );
				$pos = self::_skip( $body, $pos );
				[ $tag, $tagAttributes, $after ] = self::_peek( $body, $pos );
			}

			if( $tag !== 'div' || in_array( 'nino-grid-row', self::_tokens( $tagAttributes['class'] ?? '', $pos ), true ) === false )
				throw self::_fail( 'no-row', '', $pos );

			foreach( array_keys( $tagAttributes ) as $name )
				if( $name !== 'class' )
					throw self::_fail( 'row-attribute', $name, $pos );

			$rowTokens = self::_tokens( $tagAttributes['class'], $pos );

			self::_rowSettings( $rowTokens, $section['settings'] );

			$pos = $after;

			while( true ) {

				$pos = self::_skip( $body, $pos );

				if( preg_match( '~\G</div\s*>~', $body, $close, 0, $pos ) === 1 ) {
					$pos += strlen( $close[0] );
					break;
				}

				[ $col, $pos ] = self::_col( $body, $pos, $registry );
				$section['cols'][] = $col;
			}

			if( $section['cols'] === [] )
				throw self::_fail( 'no-cols', '', $pos );

			$pos = self::_skip( $body, $pos );

			if( preg_match( '~\G</section\s*>~', $body, $close, 0, $pos ) !== 1 ) {

				[ $tag, $tagAttributes ] = self::_peek( $body, $pos );

				throw self::_fail( $tag === 'div' && in_array( 'nino-grid-row', self::_tokens( $tagAttributes['class'] ?? '', $pos ), true ) === true ? 'second-row' : 'section-content', '', $pos );
			}

			return [ $section, $pos + strlen( $close[0] ) ];
		}

		/**
		 *	@param		string		$body
		 *	@param		int				$pos
		 *
		 *	@return 	int											The first position at or after $pos that is no whitespace
		 */
		private static function _skip( string $body, int $pos ): int {
			return $pos + strspn( $body, " \t\r\n", $pos );
		}

		/**
		 *	The opening tag at a position: its name and attributes - without
		 *	consuming it
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *
		 *	@return 	array										[ tag name or '', attributes, position after the tag ]
		 */
		private static function _peek( string $body, int $pos ): array {

			try {
				if( preg_match( '~\G<([a-zA-Z][a-zA-Z0-9]*)~', $body, $match, 0, $pos ) !== 1 )
					return [ '', [], $pos ];

				[ $attributes, $after ] = self::_open( $body, $pos, strtolower( $match[1] ), 'not-a-section' );

				return [ strtolower( $match[1] ), $attributes, $after ];
			}
			catch( \RuntimeException ) {
				return [ '', [], $pos ];
			}
		}

		/**
		 *	An opening tag, with attributes written as name="value"
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		string		$tag					The tag it has to be
		 *	@param		string		$code					What fails where it is another one
		 *
		 *	@return 	array										[ attributes, the position after the tag ]
		 */
		private static function _open( string $body, int $pos, string $tag, string $code ): array {

			if( preg_match( '~\G<'. $tag. '((?:\s+[a-zA-Z][-a-zA-Z0-9_:.]*(?:="[^"]*")?)*)\s*>~', $body, $match, 0, $pos ) !== 1 ) {

				if( preg_match( '~\G<'. $tag. '\b~', $body, $loose, 0, $pos ) === 1 )
					throw self::_fail( 'attribute-syntax', '', $pos );

				throw self::_fail( $code, '', $pos );
			}

			$attributes = [];

			if( preg_match_all( '~\s+([a-zA-Z][-a-zA-Z0-9_:.]*)(?:="([^"]*)")?~', $match[1], $found, PREG_SET_ORDER ) > 0 )
				foreach( $found as $attribute ) {

					if( isset( $attribute[2] ) === false )
						throw self::_fail( 'attribute-syntax', '', $pos );

					$attributes[strtolower( $attribute[1] )] = $attribute[2];
				}

			return [ $attributes, $pos + strlen( $match[0] ) ];
		}

		/**
		 *	The classes of a class attribute, each one a token the builder can
		 *	write back
		 *
		 *	@param		string		$class
		 *	@param		int				$pos
		 *
		 *	@return 	array
		 */
		private static function _tokens( string $class, int $pos ): array {

			$tokens = preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY ) ?: [];

			foreach( $tokens as $token )
				if( preg_match( '/^[A-Za-z0-9_:.\/-]+$/', $token ) !== 1 )
					throw self::_fail( 'class-token', $token, $pos );

			return array_values( array_unique( $tokens ) );
		}

		/**
		 *	The settings of a section: its classes and the attributes the
		 *	modifiers have
		 *
		 *	@param		array			$tokens				The classes
		 *	@param		array			$attributes		The attributes of the tag
		 *	@param		int				$pos
		 *
		 *	@return 	array
		 */
		private static function _sectionSettings( array $tokens, array $attributes, int $pos ): array {

			$s				= self::sectionDefaults();
			$custom		= [];
			$dim			= [];
			$default	= $s;

			// A modifier a second time, or a second of a kind that has one
			// place, is a class of its own: kept, not lost
			$set = static function( string $key, mixed $value, string $token ) use ( &$s, &$custom, $default ): void {

				if( $s[$key] !== $default[$key] )
					$custom[] = $token;
				else
					$s[$key] = $value;
			};

			foreach( $tokens as $token ) {

				if( $token === 'nino-section' )
					continue;

				if( preg_match( '/^nino-section--(fullwidth|fullheight)$/', $token, $m ) === 1 )
					$set( 'width', $m[1], $token );
				elseif( preg_match( '/^nino-section--(alt|tint|dark|black|primary|brand-alt)$/', $token, $m ) === 1 )
					$set( 'color', $m[1], $token );
				elseif( preg_match( '/^nino-section--border-(1|2|3|primary)$/', $token, $m ) === 1 )
					$set( 'border', $m[1], $token );
				elseif( $token === 'nino-cover' || $token === 'nino-parallex' )
					$set( 'image', $token === 'nino-cover' ? 'cover' : 'parallax', $token );
				elseif( $token === 'nino-cover--dim' || $token === 'nino-parallex--dim' )
					$dim[] = $token;
				elseif( preg_match( '/^nino-cover-(top|center|bottom)$/', $token, $m ) === 1 )
					$set( 'imagePos', $m[1], $token );
				elseif( preg_match( '/^nino-(mt|mb|pt|pb)-([0-6])$/', $token, $m ) === 1 )
					$set( $m[1], $m[2], $token );
				elseif( preg_match( '/^nino-text-(left|center|right)$/', $token, $m ) === 1 )
					$set( 'text', $m[1], $token );
				elseif( self::_animation( $token, $s ) === false )
					$custom[] = $token;
			}

			foreach( $dim as $token )
				if( ( $token === 'nino-cover--dim' && $s['image'] === 'cover' ) || ( $token === 'nino-parallex--dim' && $s['image'] === 'parallax' ) )
					$s['dim'] = true;
				else
					$custom[] = $token;

			if( isset( $attributes['data-cover-height'] ) === true ) {

				if( preg_match( '/^[1-9][0-9]{0,3}$/', $attributes['data-cover-height'] ) !== 1 )
					throw self::_fail( 'attribute-value', 'data-cover-height', $pos );

				$s['cover'] = (int) $attributes['data-cover-height'];
			}

			self::_animationAttributes( $attributes, $s, $pos );

			$s['custom'] = implode( ' ', $custom );

			return $s;
		}

		/**
		 *	The classes of the row: its width, the alignment of its columns,
		 *	and the rest
		 *
		 *	@param		array			$tokens
		 *	@param		array 		&$settings		The section's, the row's keys are set in it
		 *
		 *	@return 	void
		 */
		private static function _rowSettings( array $tokens, array &$settings ): void {

			$custom = [];

			foreach( $tokens as $token ) {

				if( $token === 'nino-grid-row' )
					continue;

				if( preg_match( '/^nino-grid-row--(narrow|wide)$/', $token, $m ) === 1 && $settings['row'] === '' )
					$settings['row'] = $m[1];
				elseif( preg_match( '/^nino-grid-(center|middle|bottom)$/', $token, $m ) === 1 && $settings['rowAlign'] === '' )
					$settings['rowAlign'] = $m[1];
				else
					$custom[] = $token;
			}

			$settings['rowCustom'] = implode( ' ', $custom );
		}

		/**
		 *	One class that belongs to the animation, set into the settings of
		 *	a section or a column
		 *
		 *	@param		string		$token
		 *	@param		array 		&$s
		 *
		 *	@return 	bool										Whether it was one
		 */
		private static function _animation( string $token, array &$s ): bool {

			if( $token === 'nino-vpa' ) {
				$s['vpa'] ??= '';
				return true;
			}

			if( preg_match( '/^nino-vpa--(.+)$/', $token, $m ) !== 1 )
				return false;

			if( in_array( $m[1], self::EFFECTS, true ) === true && ( $s['vpa'] ?? '' ) === '' ) {
				$s['vpa'] = $m[1];
				return true;
			}

			if( preg_match( '/^speed-(fast|medium|slow)$/', $m[1], $speed ) === 1 && $s['vpaSpeed'] === '' ) {
				$s['vpa'] ??= '';
				$s['vpaSpeed'] = $speed[1];
				return true;
			}

			if( in_array( $m[1], self::MODES, true ) === true && $s['vpaMode'] === '' ) {
				$s['vpa'] ??= '';
				$s['vpaMode'] = $m[1];
				return true;
			}

			return false;
		}

		/**
		 *	The two attributes of the animation
		 *
		 *	@param		array			$attributes
		 *	@param		array 		&$s
		 *	@param		int				$pos
		 *
		 *	@return 	void
		 */
		private static function _animationAttributes( array $attributes, array &$s, int $pos ): void {

			foreach( [ 'data-vpa-delay' => 'vpaDelay', 'data-vpa-duration' => 'vpaDuration' ] as $attribute => $key ) {

				if( isset( $attributes[$attribute] ) === false )
					continue;

				if( preg_match( '/^[0-9]+(?:\.[0-9]+)?(?:ms|s)$/', $attributes[$attribute] ) !== 1 )
					throw self::_fail( 'attribute-value', $attribute, $pos );

				$s[$key] = $attributes[$attribute];
				$s['vpa'] ??= '';
			}
		}

		/**
		 *	The background block of a section
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		array			$registry
		 *
		 *	@return 	array										[ [ slot, focus ], the position after it ]
		 */
		private static function _background( string $body, int $pos, array $registry ): array {

			[ $attributes, $pos ] = self::_open( $body, $pos, 'div', 'background-image' );

			$focus = null;

			foreach( array_keys( $attributes ) as $name )
				if( $name !== 'class' )
					throw self::_fail( 'background-class', $name, $pos );

			foreach( self::_tokens( $attributes['class'] ?? '', $pos ) as $token )
				if( $token === 'nino-section-bg' )
					continue;
				elseif( preg_match( '/^nino-img-focus--([1-9])$/', $token, $m ) === 1 && $focus === null )
					$focus = (int) $m[1];
				else
					throw self::_fail( 'background-class', $token, $pos );

			$pos = self::_skip( $body, $pos );

			[ $call, $pos ] = self::_call( $body, $pos, 0, $registry );

			if( $call['stack'] === true || $call['node']['name'] !== 'image' || $call['node']['source'] === '' || $call['node']['source'][0] !== '/'
				|| $call['node']['text'] !== null || ( $call['node']['attributes']['alt'] ?? '' ) !== '' )
				throw self::_fail( 'background-image', '', $pos );

			// Only the slot and the empty alt: any other attribute set would be lost in writing
			foreach( $call['node']['attributes'] as $attribute => $value )
				if( $attribute !== 'alt' && $value !== ( Reader::defaults( $registry['components']['image'] ?? [] )[$attribute] ?? '' ) )
					throw self::_fail( 'background-image', '', $pos );

			$pos = self::_skip( $body, $pos );

			if( preg_match( '~\G</div\s*>~', $body, $close, 0, $pos ) !== 1 )
				throw self::_fail( 'background-image', '', $pos );

			return [ [ 'slot' => $call['node']['source'], 'focus' => $focus ], $pos + strlen( $close[0] ) ];
		}

		/**
		 *	One column and what is in it
		 *
		 *	@param		string		$body
		 *	@param		int				$pos
		 *	@param		array			$registry
		 *
		 *	@return 	array										[ the column, the position after its closing tag ]
		 */
		private static function _col( string $body, int $pos, array $registry ): array {

			[ $attributes, $after ] = self::_open( $body, $pos, 'div', 'row-content' );

			foreach( array_keys( $attributes ) as $name )
				if( $name !== 'class' )
					throw self::_fail( 'col-attribute', $name, $pos );

			$tokens = self::_tokens( $attributes['class'] ?? '', $pos );

			if( in_array( 'nino-grid-row', $tokens, true ) === true )
				throw self::_fail( 'nested-row', '', $pos );

			$col = [ 'width' => [], 'hidden' => [] ] + self::colDefaults() + [ 'stack' => null, 'components' => [] ];
			$custom = [];

			// Nino.css is mobile-first: nino-grid-100 holds in every viewport, a
			// prefixed width from its viewport up until a larger one takes over.
			// The model keeps the width each viewport ends up with. A width that
			// cannot be said that way - another unprefixed one than 100 - fails
			// the column, a second one for a viewport is kept as a class of its own
			$base		= null;
			$widths	= [];

			foreach( $tokens as $token ) {

				if( preg_match( '/^nino-grid-(?:(s|m|l)-)?(25|33|50|66|75|100)$/', $token, $m ) === 1 ) {

					if( $m[1] === '' && $m[2] !== '100' )
						throw self::_fail( 'col-width', '', $pos );

					if( $m[1] === '' )
						$base = 100;
					elseif( isset( $widths[$m[1]] ) === false )
						$widths[$m[1]] = (int) $m[2];
					else
						$custom[] = $token;
				}
				elseif( preg_match( '/^nino-hide-(s|m|l)$/', $token, $m ) === 1 )
					$col['hidden'][$m[1]] = true;
				elseif( preg_match( '/^nino-stack-(start|center|end)$/', $token, $m ) === 1 && $col['stackAlign'] === '' )
					$col['stackAlign'] = $m[1];
				elseif( preg_match( '/^nino-stack-gap-([0-6])$/', $token, $m ) === 1 && $col['stackGap'] === '' )
					$col['stackGap'] = $m[1];
				elseif( preg_match( '/^nino-text-(left|center|right)$/', $token, $m ) === 1 && $col['text'] === '' )
					$col['text'] = $m[1];
				elseif( self::_animation( $token, $col ) === false )
					$custom[] = $token;
			}

			$width = [ 's' => $widths['s'] ?? $base ];
			$width['m'] = $widths['m'] ?? $width['s'];
			$width['l'] = $widths['l'] ?? $width['m'];

			if( $width['s'] === null )
				throw self::_fail( 'col-width', '', $pos );

			$col['width'] = $width;
			$col['custom'] = implode( ' ', $custom );

			self::_animationAttributes( $attributes, $col, $pos );

			$pos = $after;
			$calls = [];

			while( true ) {

				$pos = self::_skip( $body, $pos );

				if( preg_match( '~\G</div\s*>~', $body, $close, 0, $pos ) === 1 ) {
					$pos += strlen( $close[0] );
					break;
				}

				$at = $pos;
				[ $call, $pos ] = self::_call( $body, $pos, 0, $registry, 'col-markup' );
				$call['at'] = $at;

				if( $call['stack'] === true )
					$call['components'] = self::_sequence( $call['content'], $call['contentAt'], $registry );

				$calls[] = $call;
			}

			$stacks = array_filter( $calls, static fn( array $call ): bool => $call['stack'] === true );

			if( $stacks !== [] ) {

				if( count( $calls ) > 1 )
					throw self::_fail( 'stack-neighbour', '', $calls[1]['at'] );

				$stack = $calls[0];
				$col['stack'] = $stack['node'];
				$col['components'] = $stack['components'];

				return [ $col, $pos ];
			}

			foreach( $calls as $call )
				$col['components'][] = $call['node'];

			return [ $col, $pos ];
		}

		/**
		 *	The calls of a stack's content, and nothing else
		 *
		 *	@param		string		$content			The text between [stack] and [/stack]
		 *	@param		int				$base					Where it starts in the source, for the line of a failure
		 *	@param		array			$registry
		 *
		 *	@return 	array										The components
		 */
		private static function _sequence( string $content, int $base, array $registry ): array {

			$components	= [];
			$pos				= 0;

			while( true ) {

				$pos = self::_skip( $content, $pos );

				if( $pos >= strlen( $content ) )
					return $components;

				[ $call, $pos ] = self::_call( $content, $pos, $base, $registry, 'stack-content' );

				if( $call['stack'] === true )
					throw self::_fail( 'stack-in-stack', '', $base + $pos );

				$components[] = $call['node'];
			}
		}

		/**
		 *	One shortcode call, read the way the kernel reads it
		 *
		 *	@param		string		$text					The text the call is in
		 *	@param		int				$pos					Where it starts
		 *	@param		int				$base					What to add to a position to get one of the source
		 *	@param		array			$registry
		 *	@param		string		$other				What fails where the text at $pos is no call
		 *
		 *	@return 	array										[ [ stack, node, content, contentAt ], the position after the call ]
		 */
		private static function _call( string $text, int $pos, int $base, array $registry, string $other = 'background-image' ): array {

			if( preg_match( '~\G\[([a-z][a-z0-9-]*)(?: ([^\]]*))?\]~', $text, $head, 0, $pos ) !== 1 )
				throw self::_fail( $other, trim( substr( $text, $pos, 24 ) ), $base + $pos );

			$name			= $head[1];
			$isStack	= isset( $registry['stacks'][$name] ) === true;

			if( $isStack === false && isset( $registry['components'][$name] ) === false )
				throw self::_fail( 'unknown-shortcode', $name, $base + $pos );

			$schema = $isStack === true ? $registry['stacks'][$name] : $registry['components'][$name];

			// The expression the kernel finds a call and its content with
			preg_match( '~\G\[('. preg_quote( $name, '~' ). ')(?: ([^\]]*))?\](?:([^\[]*+(?:\[(?!\/\1\])[^\[]*+)*+)(?:\[\/\1\]))?~', $text, $match, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $pos );

			$raw				= $match[2][0] ?? '';
			$content		= $match[3][0] ?? null;
			$contentAt	= $match[3][1] ?? 0;
			$end				= $pos + strlen( $match[0][0] );

			[ $positional, $named ] = self::_arguments( $raw, $name, $base + $pos );

			$kind = $isStack === true ? 'stack' : (string) ( $schema['source'] ?? 'text' );

			if( count( $positional ) > ( $kind === 'none' || $kind === 'content' ? 0 : 1 ) )
				throw self::_fail( 'call-arguments', $name. ' takes no more than its source', $base + $pos );

			if( $isStack === true && ( $positional[0] ?? '' ) === '' )
				throw self::_fail( 'stack-source', '', $base + $pos );

			$fixed = null;

			if( array_key_exists( 'text', $named ) === true ) {

				if( in_array( $kind, [ 'text', 'href', 'image' ], true ) === false )
					throw self::_fail( 'unknown-attribute', 'text', $base + $pos );

				$fixed = $named['text'];
				unset( $named['text'] );
			}

			$node = [
				'name'				=> $name,
				'source'			=> $positional[0] ?? '',
				'text'				=> $fixed,
				'attributes'	=> self::_attributes( $named, $schema, $base + $pos ),
			];

			if( $isStack === true ) {

				unset( $node['text'] );

				return [ [ 'stack' => true, 'node' => $node, 'content' => (string) $content, 'contentAt' => $base + $contentAt ], $end ];
			}

			if( $kind === 'content' ) {

				$names = array_merge( array_keys( (array) ( $registry['components'] ?? [] ) ), array_keys( (array) ( $registry['stacks'] ?? [] ) ) );

				if( $names !== [] && preg_match( '~\[(?:'. implode( '|', array_map( static fn( string $n ): string => preg_quote( $n, '~' ), $names ) ). ')(?: [^\]]*)?\]~', (string) $content ) === 1 )
					throw self::_fail( 'nested-call', $name, $base + $contentAt );

				$node['content'] = (string) $content;
			}
			elseif( $content !== null )
				throw self::_fail( 'component-content', $name, $base + $pos );

			return [ [ 'stack' => false, 'node' => $node, 'content' => '', 'contentAt' => 0 ], $end ];
		}

		/**
		 *	The arguments of a call as \Nino\Html splits them - and only where
		 *	reading what that makes back gives the text that was written
		 *
		 *	@param		string		$raw
		 *	@param		string		$name
		 *	@param		int				$offset
		 *
		 *	@return 	array										[ the positional arguments, the named ones ]
		 */
		private static function _arguments( string $raw, string $name, int $offset ): array {

			$positional	= [];
			$named			= [];
			$again			= [];

			if( $raw === '' )
				return [ $positional, $named ];

			preg_match_all( '/\ ([^\ \=]*)(\=[\"]([^\"]*)[\"])?/i', ' '. $raw. ' ', $found );

			// The expression ends on an empty match for the space it was given
			array_pop( $found[1] );

			foreach( $found[1] as $id => $key ) {

				if( $key === '' )
					throw self::_fail( 'call-arguments', $name. ' has an empty argument', $offset );

				if( $found[2][$id] !== '' ) {
					$named[$key] = $found[3][$id];
					$again[] = $key. '="'. $found[3][$id]. '"';
					continue;
				}

				$positional[] = $key;
				$again[] = $key;
			}

			if( implode( ' ', $again ) !== $raw )
				throw self::_fail( 'call-arguments', $name. ' '. $raw, $offset );

			return [ $positional, $named ];
		}

		/**
		 *	The attributes of a call against its schema: every one of the
		 *	schema, a string each, with the default where the call is silent
		 *
		 *	@param		array			$named
		 *	@param		array			$schema
		 *	@param		int				$offset
		 *
		 *	@return 	array
		 */
		private static function _attributes( array $named, array $schema, int $offset ): array {

			$attributes = self::defaults( $schema );

			foreach( $named as $key => $value ) {

				if( array_key_exists( $key, $attributes ) === false )
					throw self::_fail( 'unknown-attribute', (string) $key, $offset );

				$declared = $schema['attributes'][$key] ?? [];
				$type = (string) ( $declared['type'] ?? 'string' );

				$valid = match( $type ) {
					'select'	=> in_array( $value, (array) ( $declared['options'] ?? [] ), true ),
					'int'			=> preg_match( '/^-?\d{1,9}$/', $value ) === 1 && (int) $value >= ( $declared['min'] ?? PHP_INT_MIN ) && (int) $value <= ( $declared['max'] ?? PHP_INT_MAX ),
					'bool'		=> $value === '0' || $value === '1',
					default		=> true,
				};

				if( $valid === false )
					throw self::_fail( 'attribute-value', (string) $key, $offset );

				$attributes[$key] = $value;
			}

			return $attributes;
		}
	}

}
