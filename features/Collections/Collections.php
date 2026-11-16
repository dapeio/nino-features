<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Collections	see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules						All optional modules
	 *	Collections				Lists over the elements of a type, as components with a schema: a loop of any components
	 *										(cards in a row, sections), a list, a table and a filter that hides rows in the browser
	 *
	 *										The four containers hold their children as nodes (the register does not draw them) and
	 *										draw them once for each element, with the element in the context as [elements] does it:
	 *										what is asked, in which order and how many is \Nino\Modules\Elements::queryLoop(), the
	 *										fields of an element are \Nino\Modules\Elements::fills(). What this class adds is what
	 *										a loop of components needs and [elements] has no use for: the values it is handed are
	 *										checked once (the type, the sort, the query), the rows can be marked for the filter, and
	 *										a table is drawn column by column. The kernel reads the folders, registers the components
	 *										and puts the standard part into the style bundle, so there is no init() here.
	 *
	 *										A filter is the container of what it filters, and says so to the loops below it by a token
	 *										of the context, FILTER; the browser does the rest (assets/collection-filter.js).
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Collections {

		/**
		 *	What a renderer draws around the rows it was given, and the words of the filter: one line each, the tokens in
		 *	double brackets
		 */
		public static array $html = [
			'item'		=> '<div class="collection-item"[[value]]>[[content]]</div>',
			'row'			=> '<li class="collection-list-row"[[value]]>[[content]]</li>',
			'line'		=> '<tr class="collection-table-row"[[value]]>[[content]]</tr>',
			'value'		=> ' data-collection-value="[[value]]"',
			'empty'		=> '<p class="collection-empty">[[text]]</p>',
			'caption'	=> '<caption id="[[id]]">[[caption]]</caption>',
			'labelled'	=> ' aria-labelledby="[[id]]"',
			'label'		=> ' aria-label="[[label]]"',
			'head'		=> '<thead><tr>[[cells]]</tr></thead>',
			'scope'		=> ' scope="[[scope]]"',
		];

		// The token of the context that tells a loop below a filter which field the filter asks for
		// (a key with a blank in it is no textfill to the reader of the notation, so that no text of a page reads it)
		private const string FILTER = '[[collection filter]]';

		// How many rounds all the loops of this feature draw in one request, and how many notes they may write to the log
		private const int ROUNDS = 20000;
		private const int NOTES = 20;

		// A type of elements as a call writes it, a field, and what a sort and a value of a query may be made of
		private const string TYPE = '/^\/[a-z][a-z0-9_-]{0,63}$/D';
		private const string FIELD = '/^[A-Za-z][A-Za-z0-9_-]{0,63}$/D';
		private const string SORT = '/^[A-Za-z0-9_., -]{0,200}$/D';
		private const int QUERY_LENGTH = 1000;

		// How long a value of the filtered field may be, in characters
		private const int VALUE_LENGTH = 120;

		/**
		 *	The type of elements a call names: '/services' and nothing that climbs, holds a fill or is longer
		 *
		 *	@param		string		$source				The first word of the call as it is written
		 *
		 *	@return 	string										'' where it is none
		 */
		private static function _type( string $source ): string {
			return preg_match( self::TYPE, $source ) === 1 ? $source : '';
		}

		/**
		 *	The query of a call as the kernel reads it, key=value pairs with &: every key is the name of a field and no value
		 *	holds a control character. A query that breaks it is no query that selects less than it was written to - the
		 *	loop is empty
		 *
		 *	@param		string		$query				As the register hands it over, escaped
		 *
		 *	@return 	string|null								null where the query is not to be read
		 */
		private static function _query( string $query ): ?string {

			$query = html_entity_decode( $query, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			if( $query === '' )
				return '';

			if( strlen( $query ) > self::QUERY_LENGTH || preg_match( '/[\x00-\x1F\x7F]/', $query ) === 1 )
				return null;

			foreach( explode( '&', $query ) as $pair ) {

				$parts = explode( '=', $pair, 2 );

				if( count( $parts ) !== 2 || preg_match( self::FIELD, $parts[0] ) !== 1 )
					return null;
			}

			return $query;
		}

		/**
		 *	The sort of a call: fields with a comma and a minus in front of a descending one - what the kernel reads - or
		 *	nothing, where it holds a character a field has none of
		 *
		 *	@param		string		$sort					As the register hands it over, escaped
		 *
		 *	@return 	string
		 */
		private static function _sort( string $sort ): string {

			$sort = html_entity_decode( $sort, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			return preg_match( self::SORT, $sort ) === 1 ? $sort : '';
		}

		/**
		 *	The value of the filtered field of an element, written for an attribute: escaped, no control character, no
		 *	bracket that could start a call, and not longer than a button can hold
		 *
		 *	@param		array			$element			As \Nino\Elements answers it
		 *	@param		string		$field
		 *
		 *	@return 	string
		 */
		private static function _value( array $element, string $field ): string {

			$raw = $element[$field] ?? null;

			if( is_bool( $raw ) === true )
				$raw = $raw === true ? '1' : '0';

			if( is_scalar( $raw ) === false )
				return '';

			return str_replace( '[', '&#91;', \Nino\Components::escape( trim( mb_substr( (string) $raw, 0, self::VALUE_LENGTH ) ) ) );
		}

		/**
		 *	The attribute that marks a row for the filter
		 *
		 *	@param		string|null	$value				null where no filter asks
		 *
		 *	@return 	string
		 */
		private static function _marked( ?string $value ): string {
			return $value === null ? '' : str_replace( '[[value]]', $value, self::$html['value'] );
		}

		/**
		 *	What stands where a loop has no element: the text of the call, if it has one
		 *
		 *	@param		array			$args					What the renderer was handed
		 *
		 *	@return 	string
		 */
		private static function _empty( array $args ): string {

			$text = trim( (string) $args['empty'] );

			return $text === '' ? '' : str_replace( '[[text]]', $text, self::$html['empty'] );
		}

		/**
		 *	One word of the feature, written for an attribute: the text of its key in the language of the page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The part and the name of the key below /feature/collections/
		 *
		 *	@return 	string										'' where nobody has a text for it
		 */
		private static function _word( array &$appData, string $name ): string {

			$text = \Nino\Html::resolveTextfill( $appData, '/feature/collections/'. $name );

			return $text === null ? '' : str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], \Nino\Components::escape( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		}

		/**
		 *	Say once, in the log, why a loop draws less than it was written to: once for each place and at most NOTES in a request
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$what					The place: a word and the thing that was wrong
		 *	@param		string		$message
		 *	@param		int				$level
		 *
		 *	@return 	void
		 */
		private static function _note( array &$appData, string $what, string $message, int $level = E_USER_NOTICE ): void {

			$said = (array) ( $appData['./nino/collections/said'] ?? [] );

			if( isset( $said[$what] ) === true || ( count( $said ) >= self::NOTES && $what !== 'rounds' ) )
				return;

			$said[$what] = true;
			$appData['./nino/collections/said'] = $said;

			trigger_error( 'Collections: '. $message, $level );
		}

		/**
		 *	A written value as the log shows it: printable ascii and short
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function _shown( string $value ): string {
			return (string) preg_replace( '/[^\x20-\x7E]/', '?', substr( $value, 0, 80 ) );
		}

		/**
		 *	The nodes of a loop without the attributes of the type id, in every node below it: in a loop a component carries no
		 *	id, since it would stand once for each element in the page, and a ~name of it reads the section outside the loop.
		 *	Which attribute is an id is the register's to say
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes
		 *
		 *	@return 	array
		 */
		private static function _unid( array &$appData, array $nodes ): array {

			if( isset( $appData['./nino/collections/ids'] ) === false ) {

				$appData['./nino/collections/ids'] = [];

				foreach( \Nino\Components::components( $appData ) as $name => $schema )
					foreach( $schema['attributes'] as $attribute => $declared )
						if( $declared['type'] === 'id' )
							$appData['./nino/collections/ids'][$name][] = $attribute;
			}

			foreach( $nodes as $at => $node ) {

				if( is_array( $node ) === false || isset( $node['n'] ) === false )
					continue;

				foreach( (array) ( $appData['./nino/collections/ids'][(string) $node['n']] ?? [] ) as $attribute )
					unset( $nodes[$at]['a'][$attribute] );

				if( is_array( $node['c'] ?? null ) === true )
					$nodes[$at]['c'] = self::_unid( $appData, $node['c'] );
			}

			return $nodes;
		}

		/**
		 *	The options of the attribute as of a column: the formatters of the kernel, by name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute
		 *
		 *	@return 	array										value => label
		 */
		public static function formats( array &$appData, string $attribute ): array {
			return \Nino\Format::options( $appData, $attribute );
		}

		/**
		 *	The elements of a call, each drawn: the children of the loop once for each element, with the element and its
		 *	fields in the context as each round of [elements] has them. The loop of a filter above it is told by the token
		 *	and passes it on to nobody - a loop inside a loop is no loop of that filter
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the renderer was handed
		 *	@param		array			$nodes				The nodes to draw for each element
		 *	@param		array			$context			What the renderer was handed
		 *
		 *	@return 	array											[ [ the html of the round, the value of the filtered field or null ], ... ]; [] where nothing is asked or nothing is found
		 */
		private static function _drawn( array &$appData, array $args, array $nodes, array $context ): array {

			$uri = self::_type( $args['source'] );
			$query = self::_query( $args['query'] );
			$sort = self::_sort( $args['sort'] );

			if( $uri === '' )
				self::_note( $appData, 'type '. $args['source'], 'the type of elements "'. self::_shown( $args['source'] ). '" is none - a call names it as /services - and the loop is empty' );

			if( $query === null )
				self::_note( $appData, 'query '. $args['query'], 'the query "'. self::_shown( html_entity_decode( $args['query'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ). '" cannot be read - a loop with such a query is empty' );

			if( $sort === '' && $args['sort'] !== '' )
				self::_note( $appData, 'sort '. $args['sort'], 'the sort "'. self::_shown( html_entity_decode( $args['sort'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ). '" holds a character no field has - the elements are not sorted' );

			if( $uri === '' || $query === null )
				return [];

			// The budget belongs to the outermost loop and what it holds: loops that stand beside each other on a
			// page each have their own, since only loops inside each other multiply
			$depth = (int) ( $appData['./nino/collections/depth'] ?? 0 );

			if( $depth === 0 )
				$appData['./nino/collections/rounds'] = 0;

			$left = self::ROUNDS - (int) ( $appData['./nino/collections/rounds'] ?? 0 );

			if( $left <= 0 ) {

				self::_note( $appData, 'rounds', 'a loop and the loops inside it drew '. self::ROUNDS. ' rounds - they draw no further one', E_USER_WARNING );

				return [];
			}

			$elements = \Nino\Modules\Elements::queryLoop( $appData, [ $uri, 'sort' => $sort, 'offset' => (int) $args['offset'], 'limit' => (int) $args['limit'], 'query' => $query ], true );

			if( $elements === [] )
				return [];

			if( count( $elements ) > $left ) {

				self::_note( $appData, 'rounds', 'a loop and the loops inside it drew '. self::ROUNDS. ' rounds - they draw no further one', E_USER_WARNING );

				$elements = array_slice( $elements, 0, $left );
			}

			$appData['./nino/collections/rounds'] = self::ROUNDS - $left + count( $elements );
			$nodes = self::_unid( $appData, $nodes );
			$model = \Nino\Elements::getElementModel( $appData, $uri );
			$fills = is_array( $context['fills'] ?? null ) === true ? $context['fills'] : [];
			$field = isset( $fills[self::FILTER] ) === true ? (string) $fills[self::FILTER] : '';
			unset( $fills[self::FILTER] );

			$round = [ 'fills' => $fills ] + $context;
			$drawn = [];

			foreach( array_values( $elements ) as $index => $element ) {

				$round['element'] = [ 'uri' => $uri, 'id' => $index, 'element' => $element, 'model' => $model ];
				$round['fills'] = array_map( 'strval', \Nino\Modules\Elements::fills( $element, $model, $index ) ) + $fills;

				$appData['./nino/collections/depth'] = $depth + 1;
				$drawn[] = [ \Nino\Components::render( $appData, $nodes, $round ), $field === '' ? null : self::_value( $element, $field ) ];
				$appData['./nino/collections/depth'] = $depth;
			}

			return $drawn;
		}

		/**
		 *	Replace collection: its children once for each element, one after the other where the call stands. Nothing is
		 *	drawn around them, so that a column stands in the row it was written in; where a filter asks, each round is in a
		 *	box of its own that the style takes out of the layout
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentCollection( array &$appData, array $args, string $content, array $context ): string {

			$drawn = self::_drawn( $appData, $args, (array) $context['children'], $context );

			if( $drawn === [] )
				return self::_empty( $args );

			$html = '';

			foreach( $drawn as [ $round, $value ] )
				$html .= $value === null ? $round : strtr( self::$html['item'], [ '[[value]]' => self::_marked( $value ), '[[content]]' => $round ] );

			return $html;
		}

		/**
		 *	Replace collection-list: a list with one row for each element, the children of the call in it. The marker of the
		 *	row is a variant: a check, a number or none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentCollectionList( array &$appData, array $args, string $content, array $context ): string {

			$rows = '';

			foreach( self::_drawn( $appData, $args, (array) $context['children'], $context ) as [ $round, $value ] )
				$rows .= strtr( self::$html['row'], [ '[[value]]' => self::_marked( $value ), '[[content]]' => $round ] );

			if( $rows === '' )
				return self::_empty( $args );

			return \Nino\Components::fill( $appData, 'collection-list', $args, $rows, [ '[[tag]]' => $args['variant'] === 'numbered' ? 'ol' : 'ul' ] );
		}

		/**
		 *	Replace collection-table: a table with a row for each element and a cell for each column the call holds. The
		 *	columns are the calls of collection-col and nothing else; the heading row is there where one of them has a
		 *	title, and the table is in a box that scrolls where the page is too narrow for it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentCollectionTable( array &$appData, array $args, string $content, array $context ): string {

			$columns = array_values( array_filter( (array) $context['children'], static fn( mixed $node ): bool => is_array( $node ) === true && ( $node['n'] ?? null ) === 'collection-col' ) );

			if( $columns === [] )
				return '';

			$rows = '';

			foreach( self::_drawn( $appData, $args, $columns, $context ) as [ $round, $value ] )
				$rows .= strtr( self::$html['line'], [ '[[value]]' => self::_marked( $value ), '[[content]]' => $round ] );

			if( $rows === '' )
				return self::_empty( $args );

			// The headings are the columns drawn once more, with no element: the register reads the title of each as it reads any text
			$head = '';

			if( array_filter( $columns, static fn( array $node ): bool => is_string( $node['a']['title'] ?? null ) === true && $node['a']['title'] !== '' ) !== [] )
				$head = str_replace( '[[cells]]', \Nino\Components::render( $appData, $columns, [ 'element' => null ] + $context ), self::$html['head'] );

			// The box is a region a keyboard stops at, named by the caption; a table with none takes a word of the feature for its name
			$caption = trim( (string) $args['caption'] );
			$id = 'collection-table-'. (string) ( $appData['./nino/collections/tables'] = (int) ( $appData['./nino/collections/tables'] ?? 0 ) + 1 );


			return \Nino\Components::fill( $appData, 'collection-table', $args, $rows, [
				'[[caption]]'	=> $caption === '' ? '' : strtr( self::$html['caption'], [ '[[id]]' => $id, '[[caption]]' => $caption ] ),
				'[[head]]'		=> $head,
				'[[name]]'		=> $caption === '' ? str_replace( '[[label]]', self::_word( $appData, 'table/label' ), self::$html['label'] ) : str_replace( '[[id]]', $id, self::$html['labelled'] ),
			] );
		}

		/**
		 *	Replace collection-col: one cell of a row of its table - a th with a scope for the heading of the column, a td
		 *	for the field of the element it is drawn for. The field is the first word, .title; a field the type has none of
		 *	is an empty cell, so that the cells of a row keep their columns. Written as the fills of [elements] write it, by
		 *	the type of the field, and with no control character in it, as the register writes a field. Anywhere but in a table
		 *	it is nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in
		 *
		 *	@return 	string
		 */
		public static function componentCollectionCol( array &$appData, array $args, string $content, array $context ): string {

			if( ( $context['place'] ?? '' ) !== 'collection-table' )
				return '';

			if( is_array( $context['element'] ?? null ) === false )
				return \Nino\Components::fill( $appData, 'collection-col', $args, '', [ '[[tag]]' => 'th', '[[scope]]' => str_replace( '[[scope]]', 'col', self::$html['scope'] ), '[[value]]' => $args['title'], '[[lang]]' => '' ] );

			$element = $context['element'];
			$name = preg_match( '/^\.(id|uri|slug|[A-Za-z][A-Za-z0-9_-]*)$/D', $args['source'], $found ) === 1 ? $found[1] : '';
			$token = in_array( $name, [ 'id', 'uri', 'slug' ], true ) === true ? '[[.'. $name. ']]' : '[['. $name. ']]';
			$known = $name !== '' && ( $token[2] === '.' || isset( $element['model'][$name] ) === true );
			$head = $args['rowhead'] === '1';
			$value = $known === true ? \Nino\Components::clean( (string) ( $context['fills'][$token] ?? '' ) ) : '';

			// A formatter reads the value as the element stores it and writes it in the language of the page; what it cannot read stays as it is
			if( $value !== '' && $args['as'] !== '' && $token[2] !== '.' && is_scalar( $element['element'][$name] ?? null ) === true ) {

				$formatted = \Nino\Format::apply( $appData, $args['as'], $element['element'][$name] );

				if( $formatted !== null )
					$value = str_replace( '[', '&#91;', \Nino\Components::escape( $formatted ) );
			}

			// The element says it has a text of another language; only a field that is per language can be that text
			$lang = $known === true && $token[2] !== '.' && ( $element['model'][$name]['locale'] ?? false ) === true ? (string) ( $context['fills']['[[.lang]]'] ?? '' ) : '';

			return \Nino\Components::fill( $appData, 'collection-col', $args, '', [
				'[[tag]]'		=> $head === true ? 'th' : 'td',
				'[[scope]]'	=> $head === true ? str_replace( '[[scope]]', 'row', self::$html['scope'] ) : '',
				'[[value]]'	=> $value,
				'[[lang]]'	=> $lang,
			] );
		}

		/**
		 *	Replace collection-filter: what it holds, in a box that the script of the feature turns into a filter. The
		 *	loops below it mark each of their rows with the value of the field and the script makes a button for each
		 *	value. The call is drawn alone - what it holds, nothing around it - where it names no field or where the words
		 *	of the buttons are missing, since a button with no name is worse than none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentCollectionFilter( array &$appData, array $args, string $content, array $context ): string {

			$field = preg_match( '/^\.?([A-Za-z][A-Za-z0-9_-]{0,63})$/D', html_entity_decode( $args['by'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $found ) === 1 ? $found[1] : '';

			// The loops below ask this filter and no other one: what an outer filter asked stays with the loops it holds
			$fills = is_array( $context['fills'] ?? null ) === true ? $context['fills'] : [];
			unset( $fills[self::FILTER] );

			$round = $context;
			$round['fills'] = ( $field === '' ? [] : [ self::FILTER => $field ] ) + $fills;

			$drawn = \Nino\Components::render( $appData, (array) $context['children'], $round );

			// Nothing to filter
			if( $drawn === '' )
				return '';

			$args['all'] = $args['all'] !== '' ? $args['all'] : self::_word( $appData, 'filter/all' );
			$shown = self::_word( $appData, 'filter/shown' );
			$label = self::_word( $appData, 'filter/label' );

			if( $field === '' || $args['all'] === '' || $shown === '' || $label === '' )
				return $drawn;

			return \Nino\Components::fill( $appData, 'collection-filter', $args, $drawn, [ '[[shown]]' => $shown, '[[label]]' => $label ] );
		}
	}

}
