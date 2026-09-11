<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Search\Shortcodes		see features/Search/Search.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Search {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Shortcodes				The two shortcodes that put a search on a page, and
	 *										nothing else: no route, no page template, no install
	 *										unit. A project that wants a search page writes one and
	 *										puts these in it.
	 *
	 *										  [search]                the form
	 *										  [search-results]…[/…]   the hits, drawn by its own body
	 *
	 *										The body of the second is the row markup, once per hit,
	 *										with [[field]] for anything the type's model has. So the
	 *										feature ships no opinion about what a result looks like -
	 *										which is the only way one search can serve a product
	 *										grid and a list of articles without growing a template
	 *										system of its own.
	 *
	 *										The form is a plain GET form: the query rides in the url,
	 *										so a result page can be linked, bookmarked and gone back
	 *										to. Both shortcodes read the same query variable, and
	 *										they have to agree on its name - `key` on both, default
	 *										"q".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Shortcodes {

		// The query variable both shortcodes read unless `key` says otherwise
		public const string DEFAULT_KEY = 'q';

		// How many hits a result block draws when it names no limit, and the
		// ceiling a named one is held to. Coverage scoring means a multi-word
		// query matches more documents than it used to (see Search::_score()),
		// so a result page that names no number still gets a page rather than
		// a catalogue
		public const int DEFAULT_LIMIT = 20;
		public const int MAX_LIMIT = 200;

		// What the form says when the page does not - resolved from the
		// project's own textfills first, so a project overrides by defining
		// /search/label/submit rather than by passing an attribute everywhere
		private const array DEFAULTS = [
			'submit' 			=> [ 'de' => 'Suchen', 'en' => 'Search' ],
			'placeholder' => [ 'de' => 'Suchbegriff', 'en' => 'Search term' ],
		];

		/**
		 *	Register both shortcodes
		 *
		 *	The longer name first, deliberately: the renderer joins every
		 *	registered name into one alternation, and while PCRE does backtrack
		 *	out of "search" into "search-results", it only has to when the
		 *	shorter one is offered first
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'search-results', [ self::class, 'doResults' ] );
			\Nino\Html::addShortcode( $appData, 'search', [ self::class, 'doForm' ] );
		}

		/**
		 *	What the visitor typed, for one query variable name
		 *
		 *	Read from $_GET rather than from the request array: a shortcode
		 *	callback is handed $appData and its own arguments, never the request
		 *	(see \Nino\Html::_doShortcode()), and $_GET is php's parse of the
		 *	same query string Http::request() parses for itself
		 *
		 *	@param		string		$key					A query variable name
		 *
		 *	@return 	string								Trimmed, never an array
		 */
		public static function query( string $key ): string {

			$value = $_GET[$key] ?? '';

			return is_string( $value ) === true ? trim( $value ) : '';
		}

		/**
		 *	[search] - the form. A plain GET form, so the query lands in the url
		 *	and a result page can be linked and bookmarked
		 *
		 *	  key           the query variable, default "q"
		 *	  action        where it submits, default the page it is on
		 *	  placeholder   the field's placeholder and its accessible name
		 *	  submit        the button
		 *	  label         the accessible name, when it should differ
		 *	  class         replaces the form's classes entirely
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments
		 *
		 *	@return 	string								Html
		 */
		public static function doForm( array &$appData, array $args ): string {

			$key 					= self::_key( $args );
			$placeholder 	= self::_text( $appData, $args, 'placeholder' );
			$submit 			= self::_text( $appData, $args, 'submit' );
			$label 				= self::_attribute( $args, 'label' );
			$class 				= self::_attribute( $args, 'class' );
			$action 			= self::_attribute( $args, 'action' );

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

			return '<form class="'. $safe( $class !== '' ? $class : 'nino-form nino-form--inline nino-search' ). '"'
				. ' role="search" method="get" action="'. $safe( $action ). '">'
				. '<input type="search" name="'. $safe( $key ). '" value="'. $safe( self::query( $key ) ). '"'
				. ' class="nino-form-input nino-search-input"'
				. ( $placeholder !== '' ? ' placeholder="'. $safe( $placeholder ). '"' : '' )
				. ' aria-label="'. $safe( $label !== '' ? $label : $placeholder ). '">'
				. '<button type="submit" class="nino-btn nino-btn--primary nino-form-submit">'. $safe( $submit ). '</button>'
				. '</form>';
		}

		/**
		 *	[search-results …]<row markup>[/search-results] - the hits, each one
		 *	drawn by the block's own body
		 *
		 *	  type          one element type, or several separated by commas
		 *	  key           the query variable, default "q" - must match [search]
		 *	  limit         how many hits to draw, default 20, at most 200
		 *	  empty         what to say when the query found nothing
		 *	  tag / class   the wrapper, default <div class="nino-search-results">;
		 *	                tag="none" leaves the rows unwrapped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments, 'content' is the row markup
		 *
		 *	@return 	string								Html, '' while nothing was searched for
		 */
		public static function doResults( array &$appData, array $args ): string {

			$template = (string) ( $args['content'] ?? '' );
			$types 		= self::_types( $args );
			$key 			= self::_key( $args );
			$query 		= self::query( $key );

			// Nothing was asked, so there is nothing to say - which is a
			// different thing from having asked and found nothing
			if( $query === '' || $types === [] || trim( $template ) === '' )
				return '';

			$limit = (int) ( $args['limit'] ?? self::DEFAULT_LIMIT );
			$limit = $limit > 0 ? min( $limit, self::MAX_LIMIT ) : self::DEFAULT_LIMIT;

			$hits = \Nino\Modules\Search::getElements( $appData, $types, $query, $limit );

			if( $hits === [] )
				return self::_wrap( $args, self::_attribute( $args, 'empty' ) );

			$rows = '';
			$number = 0;

			foreach( $hits as $element )
				$rows .= self::_row( $appData, $template, $element, ++$number );

			return self::_wrap( $args, $rows );
		}

		/**
		 *	One hit, drawn by the block's body: every [[name]] the type's model
		 *	knows, plus the dot-keys an element carries anyway
		 *
		 *	A placeholder the model does not have is left standing rather than
		 *	emptied - that is what an unresolved fill does everywhere else in
		 *	Nino, and a typo nobody can see is a typo nobody fixes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The block's body
		 *	@param		array 		$element			A canonical Element, with .score and .type
		 *	@param		int				$number				Its 1-based place in the list
		 *
		 *	@return 	string								Html
		 */
		private static function _row( array &$appData, string $template, array $element, int $number ): string {

			$type 	= (string) ( $element['.type'] ?? '' );
			$uri 		= (string) ( $element['.uri'] ?? '' );
			$model 	= $type === '' ? [] : \Nino\Elements::getElementModel( $appData, $type );

			$fills = [
				'[[.uri]]' 		=> $uri,
				// The last segment of the uri - what a project's own route builds
				// its link out of, since an element uri is not a public path
				'[[.slug]]' 	=> $uri === '' ? '' : substr( $uri, (int) strrpos( $uri, '/' ) + 1 ),
				'[[.type]]' 	=> $type,
				'[[.locale]]' => (string) ( $element['.locale'] ?? '' ),
				'[[.score]]' 	=> (string) round( (float) ( $element['.score'] ?? 0 ), 3 ),
				'[[.n]]' 			=> (string) $number,
			];

			foreach( $model as $field => $definition )
				if( is_string( $field ) === true )
					$fills['[['. $field. ']]'] = self::_value( $element[$field] ?? null, is_array( $definition ) === true ? $definition : [] );

			return str_replace( array_keys( $fills ), array_values( $fills ), $template );
		}

		/**
		 *	One field value as html. Escaped, unless the model says the field is
		 *	markup - the same 'html' flag the workbench's own table reader honours
		 *
		 *	@param		mixed			$value
		 *	@param		array 		$definition		The model's entry for this field
		 *
		 *	@return 	string
		 */
		private static function _value( mixed $value, array $definition ): string {

			if( is_array( $value ) === true ) {
				$parts = [];
				array_walk_recursive( $value, static function( mixed $part ) use ( &$parts ): void {
					if( is_scalar( $part ) === true && is_bool( $part ) === false )
						$parts[] = (string) $part;
				} );
				$value = implode( ', ', $parts );
			}

			if( is_scalar( $value ) === false || is_bool( $value ) === true )
				return '';

			return ( $definition['type'] ?? '' ) === 'string' && ( $definition['html'] ?? false ) === true
				? (string) $value
				: htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		/**
		 *	The wrapper around the rows
		 *
		 *	@param		array 		$args					Shortcode arguments
		 *	@param		string		$inner				Rows, or the empty message
		 *
		 *	@return 	string
		 */
		private static function _wrap( array $args, string $inner ): string {

			if( $inner === '' )
				return '';

			/*	tag="none" rather than tag="": Nino's shortcode parser cannot tell
				an empty value from a bare flag - `tag=""` arrives as the
				positional argument "tag" and never as the key - so an empty
				string is not a thing an attribute can say here (see
				\Nino\Html::_doShortcode()) */
			$tag = self::_attribute( $args, 'tag' );

			if( $tag === 'none' )
				return $inner;

			if( preg_match( '/^[a-z][a-z0-9]*$/', $tag ) !== 1 )
				$tag = 'div';

			$class = self::_attribute( $args, 'class' );

			return '<'. $tag. ' class="'. htmlspecialchars( $class !== '' ? $class : 'nino-search-results', ENT_QUOTES, 'UTF-8' ). '">'
				. $inner. '</'. $tag. '>';
		}

		/**
		 *	The query variable name, held to what a form field may be called
		 *
		 *	@param		array 		$args					Shortcode arguments
		 *
		 *	@return 	string
		 */
		private static function _key( array $args ): string {

			$key = self::_attribute( $args, 'key' );

			return preg_match( '/^[A-Za-z_][A-Za-z0-9_-]*$/', $key ) === 1 ? $key : self::DEFAULT_KEY;
		}

		/**
		 *	The element types a result block searches: one, or several separated
		 *	by commas. Canonicalised by Search itself, which is also what refuses
		 *	anything that is not a type name
		 *
		 *	@param		array 		$args					Shortcode arguments
		 *
		 *	@return 	array
		 */
		private static function _types( array $args ): array {

			$types = [];

			foreach( explode( ',', self::_attribute( $args, 'type' ) ) as $type )
				if( ( $type = trim( $type ) ) !== '' )
					$types[] = $type;

			return $types;
		}

		/**
		 *	One attribute, with a fill that did not resolve treated as absent
		 *
		 *	Attributes are rendered before shortcodes are (see
		 *	\Nino\Html::renderHtml()), so `submit="[[/page/search/submit]]"`
		 *	arrives here as the text it resolved to. When the project never
		 *	defined that key it arrives as the brackets themselves, and putting
		 *	those on a button in front of a visitor is worse than the default
		 *
		 *	@param		array 		$args					Shortcode arguments
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		private static function _attribute( array $args, string $name ): string {

			$value = trim( (string) ( $args[$name] ?? '' ) );

			return preg_match( '/^\[\[.*\]\]$/s', $value ) === 1 ? '' : $value;
		}

		/**
		 *	A label: the attribute, else the project's own textfill for it, else
		 *	what this feature ships in the interface language
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments
		 *	@param		string		$name					'submit' or 'placeholder'
		 *
		 *	@return 	string
		 */
		private static function _text( array &$appData, array $args, string $name ): string {

			$value = self::_attribute( $args, $name );

			if( $value !== '' )
				return $value;

			$fill = \Nino\Html::renderTextfill( $appData, '/search/label/'. $name );

			if( $fill !== '' )
				return $fill;

			$language = substr( \Nino\Locales::getCurrentLocale( $appData ), 0, 2 );

			return self::DEFAULTS[$name][$language] ?? self::DEFAULTS[$name]['en'];
		}

	}
}
