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
	 *	Shortcodes				The one shortcode that puts a search on a page, and
	 *										nothing else: no route, no page template, no install
	 *										unit, no form. A project that wants a search page writes
	 *										one, with a GET form of its own - an input and a button
	 *										are written faster than a shortcode's attributes are
	 *										looked up - and puts this under it:
	 *
	 *										  [search-results]…[/…]   the hits, drawn by its own body
	 *
	 *										The body is the row markup, once per hit, with [[field]]
	 *										for anything the type's model has. So the feature ships
	 *										no opinion about what a result looks like - which is the
	 *										only way one search can serve a product grid and a list
	 *										of articles without growing a template system of its
	 *										own.
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

		// The query variable the shortcode reads unless `key` says otherwise
		public const string DEFAULT_KEY = 'q';

		// How many hits a result block draws when it names no limit, and the
		// ceiling a named one is held to. Coverage scoring means a multi-word
		// query matches more documents than it used to (see Search::_score()),
		// so a result page that names no number still gets a page rather than
		// a catalogue
		public const int DEFAULT_LIMIT = 20;
		public const int MAX_LIMIT = 200;

		/**
		 *	Register the shortcode
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'search-results', [ self::class, 'doResults' ] );
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
				return self::_wrap( $appData, $args, self::_empty( $args ) );

			$rows = '';
			$number = 0;

			foreach( $hits as $element )
				$rows .= self::_row( $appData, $template, $element, ++$number );

			return self::_wrap( $appData, $args, $rows );
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

			// The same two steps Modules\Elements takes on a field value, and
			// for the same reason: the value is editor content, and the row is
			// rendered again after this - so a '[' left standing runs whatever
			// the editor typed
			$safe = ( $definition['type'] ?? '' ) === 'string' && ( $definition['html'] ?? false ) === true
				? \Nino\Html::sanitizeHtml( (string) $value )
				: htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			return str_replace( '[', '&#91;', $safe );
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

			// The rows last - they are built markup, and str_replace() works through
			// its arrays in order
			return str_replace(
				[ '[[tag]]', '[[class]]', '[[inner]]' ],
				[ $tag, htmlspecialchars( $class !== '' ? $class : 'nino-search-results', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), $inner ],
				self::template( $appData, 'search-results' )
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

			if( preg_match( '/^[a-z0-9][a-z0-9_-]*(?:\/[a-z0-9][a-z0-9_-]*)*$/i', $empty ) !== 1 ) {
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
		 *	\Nino\Html::renderHtml()), so `empty="[[/page/search/empty]]"`
		 *	arrives here as the text it resolved to. When the project never
		 *	defined that key it arrives as the brackets themselves, and reading
		 *	those as a value is worse than the default
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
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway:
			// the one thing this could otherwise be turned into is a read of
			// something outside the feature
			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}
}
