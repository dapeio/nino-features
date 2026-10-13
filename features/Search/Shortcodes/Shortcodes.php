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
	 *										unit, no form. A project that wants a search page writes
	 *										one, with a GET form of its own - an input and a button
	 *										are written faster than a shortcode's attributes are
	 *										looked up - and puts these under it:
	 *
	 *										  [search-results]…[/…]   the hits, drawn by its own body
	 *										  [search-count]          how many there are, as a number
	 *
	 *										The body of the first is the row markup, once per hit,
	 *										with [[field]] for anything the type's model has. So the
	 *										feature ships no opinion about what a result looks like -
	 *										which is the only way one search can serve a product
	 *										grid and a list of articles without growing a template
	 *										system of its own. The second is the number and nothing
	 *										around it, so the sentence it stands in is the page's.
	 *
	 *										The query rides in the url, so a result page can be
	 *										linked, bookmarked and gone back to. Which query variable
	 *										is read is `key`, default "q" - the name of the input in
	 *										the project's form.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Shortcodes {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Search/templates';

		// The query variable both shortcodes read unless `key` says otherwise
		public const string DEFAULT_KEY = 'q';

		// How many hits a result block draws when it names no limit, and the
		// ceiling a named one is held to. Coverage scoring means a multi-word
		// query matches more documents than it used to (see Search::_score()),
		// so a result page that names no number still gets a page rather than
		// a catalogue
		public const int DEFAULT_LIMIT = 20;
		public const int MAX_LIMIT = 200;

		/**
		 *	Register both shortcodes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'search-results', [ self::class, 'doResults' ] );

			/*	[search-count] is a component of the manifest, and where the
				Components module is on it owns the name and renders it through
				Search::componentSearchCount(), with the defaults of its schema
				filled in: a second registration here would take it back from
				that wrapper, and the helper registers none then. With the module
				off it is a shortcode like the other. The results are a shortcode
				only: the Builder cannot carry a record template	*/
			\Nino\Feature::shortcode( $appData, 'search-count', [ self::class, 'doCount' ] );
		}

		/**
		 *	What the visitor typed, for one query variable name
		 *
		 *	Read from the request that is being answered, which the kernel hands
		 *	to code that has none in hand: a shortcode callback is given $appData
		 *	and its own arguments (see \Nino\Html::_doShortcode()) and reaches the
		 *	request through \Nino\Http::getRequest(). Nothing was searched for
		 *	where there is no request at all - a page rendered by a script
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					A query variable name
		 *
		 *	@return 	string								Trimmed, never an array
		 */
		public static function query( array &$appData, string $key ): string {

			$request	= \Nino\Http::getRequest( $appData );
			$query		= $request === false ? [] : ( $request['/nino/http/request']['query'] ?? [] );
			$value		= is_array( $query ) === true ? ( $query[$key] ?? '' ) : '';

			return is_string( $value ) === true ? trim( $value ) : '';
		}

		/**
		 *	[search-results …]<row markup>[/search-results] - the hits, each one
		 *	drawn by the block's own body
		 *
		 *	  type          one element type, or several separated by commas
		 *	  key           the query variable, default "q" - the name of the
		 *	                input in the project's own form
		 *	  limit         how many hits to draw, default 20, at most 200
		 *	  empty         what to draw when the query found nothing: a
		 *	                template of the project, "search-empty" for
		 *	                /templates/search-empty.tpl
		 *	  tag / class   the wrapper, default <div class="nino-search-results">,
		 *	                with the classes written after it; tag="none" leaves
		 *	                the rows unwrapped
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
			$query 		= self::query( $appData, $key );

			// Nothing was asked, so there is nothing to say - which is a
			// different thing from having asked and found nothing
			if( $query === '' || $types === [] || trim( $template ) === '' )
				return '';

			$limit = (int) ( $args['limit'] ?? self::DEFAULT_LIMIT );
			$limit = $limit > 0 ? min( $limit, self::MAX_LIMIT ) : self::DEFAULT_LIMIT;

			$hits = \Nino\Modules\Search::getElements( $appData, $types, $query, $limit );

			if( $hits === [] )
				return self::_wrap( $appData, $args, self::_empty( $args ) );

			$rows = '';
			$number = 0;

			foreach( $hits as $element )
				$rows .= self::_row( $appData, $template, $element, ++$number );

			return self::_wrap( $appData, $args, $rows );
		}

		/**
		 *	[search-count] - how many hits the running search has, as a plain
		 *	number: "[search-count type="/products"] Treffer" is the page's own
		 *	sentence, so nothing is drawn around it. It counts everything the
		 *	query matched over the named types, not the page a result block's
		 *	`limit` draws - the number a visitor is told is the number there is.
		 *
		 *	  type          one Element type, or several separated by commas
		 *	  key           the query variable, default "q" - the same name the
		 *	                result block reads, so both answer the same search
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments
		 *
		 *	@return 	string								Digits, '' while nothing was searched for
		 */
		public static function doCount( array &$appData, array $args ): string {

			$types = self::_types( $args );
			$query = self::query( $appData, self::_key( $args ) );

			// The same silence as the result block's: nothing asked, nothing
			// said. A query that found nothing is answered - with a 0
			if( $query === '' || $types === [] )
				return '';

			return (string) count( \Nino\Modules\Search::getHits( $appData, $types, $query ) );
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
					$fills['[['. $field. ']]'] = self::_value( $element[$field] ?? null, $definition );

			return str_replace( array_keys( $fills ), array_values( $fills ), $template );
		}

		/**
		 *	One field value as html. Escaped, unless the model says the field is
		 *	markup - the same 'html' flag the workbench's own table reader honours
		 *
		 *	A string field is the kernel's to render by the one rule for it,
		 *	\Nino\Html::fieldValue(): paragraphs and lists of a field that holds
		 *	them, the line breaks of a field that keeps them, the escape for the
		 *	rest. Any other type - a list, a number, a date - is text, escaped the
		 *	way a component escapes a value of its own
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

			/*	The value is editor content, and the row is rendered again after
				this - so a '[' left standing runs whatever the editor typed.
				Both escapes write it as an entity	*/
			if( ( $definition['type'] ?? '' ) === 'string' )
				return \Nino\Html::fieldValue( (string) $value, $definition );

			return \Nino\Modules\Components::escape( (string) $value );
		}

		/**
		 *	The wrapper around the rows
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments
		 *	@param		string		$inner				Rows, or the empty message
		 *
		 *	@return 	string
		 */
		private static function _wrap( array &$appData, array $args, string $inner ): string {

			if( $inner === '' )
				return '';

			/*	tag="none" rather than tag="": an attribute left off and one
				written empty both read as '' here, and '' is the default
				wrapper - so "no wrapper" needs a word of its own */
			$tag = self::_attribute( $args, 'tag' );

			if( $tag === 'none' )
				return $inner;

			if( preg_match( '/^[a-z][a-z0-9]*$/D', $tag ) !== 1 )
				$tag = 'div';

			// The rows last - they are built markup, and str_replace() works through
			// its arrays in order
			return str_replace(
				[ '[[tag]]', '[[classes]]', '[[inner]]' ],
				[ $tag, \Nino\Feature::classes( $args ), $inner ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'search-results' )
			);
		}

		/**
		 *	What stands there when nothing was found: the project's template
		 *	`empty` names, as the [template] the kernel resolves - what a
		 *	shortcode returns is rendered again, which is what lets the template
		 *	hold fills and shortcodes of its own. A sentence in an attribute
		 *	used to stand here; a template holds a sentence as well, and a
		 *	suggestion, a form or a picture beside it.
		 *
		 *	Held to a name below /templates - slugs, with a slash between them -
		 *	because the one thing the value could otherwise do is climb out of
		 *	that directory. A value that is not one renders nothing and says so,
		 *	the way a missing template of this feature's own says so
		 *
		 *	@param		array 		$args					Shortcode arguments
		 *
		 *	@return 	string								'[template …]', or ''
		 */
		private static function _empty( array $args ): string {

			$empty = self::_attribute( $args, 'empty' );

			if( $empty === '' )
				return '';

			if( preg_match( '/^[a-z0-9][a-z0-9_-]*(?:\/[a-z0-9][a-z0-9_-]*)*$/iD', $empty ) !== 1 ) {
				trigger_error( 'Nino: [search-results empty="'. $empty. '"] does not name a template below /templates.', E_USER_WARNING );
				return '';
			}

			return '[template /templates/'. $empty. ']';
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

			return preg_match( '/^[A-Za-z_][A-Za-z0-9_-]*$/D', $key ) === 1 ? $key : self::DEFAULT_KEY;
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
		 *	One attribute, trimmed - absent reads as ''
		 *
		 *	@param		array 		$args					Shortcode arguments
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		private static function _attribute( array $args, string $name ): string {

			return trim( (string) ( $args[$name] ?? '' ) );
		}
	}
}
