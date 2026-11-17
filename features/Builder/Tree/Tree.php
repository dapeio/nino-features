<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// A template as the Builder holds it: the tree \Nino\Markup reads, the blocks on its top level, the places where a text is read, and the one rule
	// that says what a person with the right "content" may change in it. Pure over the register: nothing is read from a file or written to one
	//
	// A node is as \Nino\Markup has it - 't', 'f' or 'n' with 's', 'a' and 'c' - without the offset 'o'. A tree that the Builder takes from a person is
	// canonical: it is what \Nino\Markup reads from what it writes, so that the position of a node on a page that is drawn from a draft - the dotted
	// indices of the trace - is the position of the node in the tree the person holds
	class Tree {

		// The most calls a template of the Builder holds, how deep a block may be, how long a fixed text may be and an id
		public const int MAX_CALLS = 2000;
		public const int MAX_TEXT = 1000;
		public const int MAX_ID = 64;
		public const int MAX_BLANKS = 2000;
		public const string ID = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D';

		// What a block that is not a block says it is: free markup, or a call that has no schema
		public const string FREE = 'free';
		public const string CALL = 'call';

		/**
		 *	The nodes of a source without their offsets
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$source
		 *	@param		array			&$notes				(reference) What \Nino\Markup noticed
		 *
		 *	@return 	array
		 */
		public static function read( array &$appData, string $source, array &$notes = [] ): array {
			return self::strip( \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ), $notes ) );
		}

		/**
		 *	The nodes without the offsets of the read
		 *
		 *	@param		array			$nodes
		 *
		 *	@return 	array
		 */
		public static function strip( array $nodes ): array {

			$clean = [];

			foreach( $nodes as $node ) {

				if( is_array( $node ) === false )
					continue;

				unset( $node['o'] );

				if( is_array( $node['c'] ?? null ) === true )
					$node['c'] = self::strip( $node['c'] );

				$clean[] = $node;
			}

			return $clean;
		}

		/**
		 *	What a tree from a person is written as, and the tree that stands for it: the source and the nodes read from it. A tree whose reading is not itself - a
		 *	node the notation cannot write, two texts in a row, a text that ends in a backslash - is none the Builder takes, nor is one with too many calls
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$tree
		 *
		 *	@return 	array|null								[ source, nodes ]
		 */
		public static function canonical( array &$appData, mixed $tree ): ?array {

			if( is_array( $tree ) === false || array_is_list( $tree ) === false )
				return null;

			$containers = \Nino\Components::containers( $appData );
			$source = \Nino\Markup::write( $tree, $containers );

			if( strlen( $source ) > \Nino\Modules\Template::MAX_BYTES )
				return null;

			$notes = [];
			$read = self::read( $appData, $source, $notes );

			if( $notes !== [] || json_encode( $read ) !== json_encode( $tree ) || self::count( $read ) > self::MAX_CALLS || self::blanks( $read ) === false )
				return null;

			return [ $source, $read ];
		}

		/**
		 *	Whether the blanks of a tree are layout: no text of nothing but blanks is longer than MAX_BLANKS, which is what a template written by hand has
		 *	between its calls by a wide margin
		 *
		 *	@param		array			$nodes
		 *
		 *	@return 	bool
		 */
		public static function blanks( array $nodes ): bool {

			foreach( $nodes as $node ) {

				if( isset( $node['t'] ) === true && strlen( $node['t'] ) > self::MAX_BLANKS && trim( $node['t'], " \t\r\n" ) === '' )
					return false;

				if( is_array( $node['c'] ?? null ) === true && self::blanks( $node['c'] ) === false )
					return false;
			}

			return true;
		}

		/**
		 *	How many calls a tree holds
		 *
		 *	@param		array			$nodes
		 *
		 *	@return 	int
		 */
		public static function count( array $nodes ): int {

			$count = 0;

			foreach( $nodes as $node )
				if( isset( $node['n'] ) === true )
					$count += 1 + ( is_array( $node['c'] ?? null ) === true ? self::count( $node['c'] ) : 0 );

			return $count;
		}

		/**
		 *	What a node of the top level is: a gap (text of blanks), free markup (any other text, a fill), a call that has no schema, or a block
		 *
		 *	@param		array			$node
		 *	@param		array			$registry			The schemas
		 *
		 *	@return 	string										'gap', self::FREE, self::CALL or 'block'
		 */
		public static function kind( array $node, array $registry ): string {

			if( isset( $node['n'] ) === true )
				return isset( $registry[$node['n']] ) === true ? 'block' : self::CALL;

			if( isset( $node['t'] ) === true && trim( (string) $node['t'], " \t\r\n" ) === '' )
				return 'gap';

			return self::FREE;
		}

		/**
		 *	The first attribute of a component that is an id: what the sources below a call are read under
		 *
		 *	@param		array			$schema
		 *
		 *	@return 	string										'' for none
		 */
		public static function idAttribute( array $schema ): string {

			foreach( (array) ( $schema['attributes'] ?? [] ) as $name => $declared )
				if( ( $declared['type'] ?? '' ) === 'id' )
					return (string) $name;

			return '';
		}

		/**
		 *	Whether a value is an id: a word, at most MAX_ID long
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	bool
		 */
		public static function isId( mixed $value ): bool {
			return is_string( $value ) === true && strlen( $value ) <= self::MAX_ID && preg_match( self::ID, $value ) === 1;
		}

		/**
		 *	The places where a call reads a value, in the order the register reads them, each with the position of the call in the tree
		 *
		 *	A source written ~name is the key /template/<template>/<id of the nearest call above that has one>/name, one written with a slash is as it is
		 *	written. Only a key of the namespace of the template that holds text is one the Builder writes ('editable'); a fixed text="..." is one too, with no key
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry			The schemas
		 *	@param		string		$template			The name of the template: page-home
		 *
		 *	@return 	array											[ [ path, name, kind, written, key, attribute, format, editable ], ... ] - kind text, image or link; 'fixed' for text="..."
		 */
		public static function sources( array $nodes, array $registry, string $template ): array {

			$found = [];
			self::_sources( $nodes, $registry, $template, '', '', 0, $found );

			return $found;
		}

		private static function _sources( array $nodes, array $registry, string $template, string $scope, string $path, int $depth, array &$found ): void {

			if( $depth > \Nino\Markup::MAX_DEPTH )
				return;

			foreach( $nodes as $at => $node ) {

				if( isset( $node['n'] ) === false )
					continue;

				$here = $path === '' ? (string) $at : $path. '.'. $at;
				$inner = $scope;
				$schema = $registry[$node['n']] ?? null;

				if( $schema !== null ) {

					$given = is_array( $node['a'] ?? null ) === true ? $node['a'] : [];
					$idName = self::idAttribute( $schema );

					if( $idName !== '' && self::isId( $given[$idName] ?? null ) === true )
						$inner = (string) $given[$idName];

					$source = (string) ( $schema['source'] ?? 'none' );
					$format = is_string( $given['format'] ?? null ) === true && in_array( $given['format'], \Nino\Html::FORMATS, true ) === true ? $given['format'] : (string) ( $schema['format'] ?? 'plain' );

					if( $source !== 'none' ) {

						if( $source === 'text' && is_string( $given['text'] ?? null ) === true )
							$found[] = [ 'path' => $here, 'name' => $node['n'], 'kind' => 'fixed', 'written' => 'text', 'key' => '', 'attribute' => '', 'format' => $format, 'editable' => true ];
						else {

							$written = is_string( $node['s'] ?? null ) === true ? $node['s'] : '';
							$key = self::key( $written, $source, $template, $inner );

							if( $key !== null )
								$found[] = [ 'path' => $here, 'name' => $node['n'], 'kind' => $source, 'written' => $written, 'key' => $key, 'attribute' => '', 'format' => $format, 'editable' => $source === 'text' && self::own( $key, $template ) ];
						}
					}

					foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared ) {

						if( in_array( $declared['type'] ?? '', [ 'text', 'image', 'link' ], true ) === false )
							continue;

						$written = is_string( $given[$attribute] ?? null ) === true ? $given[$attribute] : (string) ( $declared['default'] ?? '' );
						$key = self::key( $written, (string) $declared['type'], $template, $inner );

						if( $key !== null )
							$found[] = [ 'path' => $here, 'name' => $node['n'], 'kind' => (string) $declared['type'], 'written' => $written, 'key' => $key, 'attribute' => (string) $attribute, 'format' => 'plain', 'editable' => false ];
					}
				}

				if( is_array( $node['c'] ?? null ) === true )
					self::_sources( $node['c'], $registry, $template, $inner, $here, $depth + 1, $found );
			}
		}

		/**
		 *	The key a written source names
		 *
		 *	@param		string		$written
		 *	@param		string		$kind
		 *	@param		string		$template
		 *	@param		string		$scope
		 *
		 *	@return 	string|null
		 */
		public static function key( string $written, string $kind, string $template, string $scope ): ?string {

			if( $written === '' || ( $written[0] !== '~' && $written[0] !== '/' ) )
				return null;

			if( $written[0] === '/' )
				$key = $kind === 'link' ? '' : $written;
			else
				$key = $scope === '' || $written === '~' ? '' : '/template/'. $template. '/'. $scope. '/'. substr( $written, 1 );

			return $key !== '' && \Nino\Text::isGrammarKey( $key ) === true ? $key : null;
		}

		/**
		 *	Whether a key is in the namespace of a template: the only keys the Builder writes
		 *
		 *	@param		string		$key
		 *	@param		string		$template
		 *
		 *	@return 	bool
		 */
		public static function own( string $key, string $template ): bool {
			return str_starts_with( $key, '/template/'. $template. '/' ) === true && \Nino\Text::isGrammarKey( $key ) === true;
		}

		/**
		 *	The keys a source still reads or writes in its text: the sources, and every key of the namespace that is written out in it - a fill in free markup
		 *	reads one the sources do not tell of
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *	@param		string		$template
		 *	@param		string		$source				The written template
		 *
		 *	@return 	array											The keys
		 */
		public static function readKeys( array $nodes, array $registry, string $template, string $source ): array {

			$keys = [];

			foreach( self::sources( $nodes, $registry, $template ) as $entry )
				if( $entry['key'] !== '' )
					$keys[$entry['key']] = true;

			if( preg_match_all( '#/template/'. preg_quote( $template, '#' ). '/[a-z0-9/-]+#', $source, $found ) > 0 )
				foreach( $found[0] as $key )
					$keys[$key] = true;

			return array_keys( $keys );
		}

		/**
		 *	The names of the calls in a tree
		 *
		 *	@param		array			$nodes
		 *	@param		array			&$names				(reference) name => true
		 *
		 *	@return 	void
		 */
		public static function names( array $nodes, array &$names ): void {

			foreach( $nodes as $node )
				if( isset( $node['n'] ) === true ) {

					$names[$node['n']] = true;

					if( is_array( $node['c'] ?? null ) === true )
						self::names( $node['c'], $names );
				}
		}

		/**
		 *	What the screen knows of the calls of a tree: for each name the register has, its label and the attribute that is its id
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *
		 *	@return 	array											name => [ 'label' => ..., 'id' => attribute or '', 'source' => text, image, link or none ]
		 */
		public static function known( array $nodes, array $registry ): array {

			$names = [];
			self::names( $nodes, $names );
			$known = [];

			foreach( array_keys( $names ) as $name )
				if( isset( $registry[$name] ) === true )
					$known[$name] = [ 'label' => $registry[$name]['label'], 'id' => self::idAttribute( $registry[$name] ), 'source' => (string) ( $registry[$name]['source'] ?? 'none' ) ];

			return $known;
		}

		/**
		 *	What the screen knows of one component to show its settings: its label, group, where it holds calls, what its source is, its attributes as the schema
		 *	declares them - with the options a method answered - and its variants, the schema's and what the callbacks of the theme add, with the standard
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$registry
		 *	@param		string		$name
		 *
		 *	@return 	array											'label', 'group', 'container', 'source', 'format', 'id', 'attributes', 'variants', 'standard'
		 */
		public static function schema( array &$appData, array $registry, string $name ): array {

			$schema = $registry[$name];
			$attributes = [];

			foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared )
				$attributes[(string) $attribute] = [
					'type'				=> (string) $declared['type'],
					'label'				=> $declared['label'] ?? (string) $attribute,
					'hint'				=> $declared['hint'] ?? '',
					'default'			=> $declared['default'] ?? '',
					'options'			=> is_array( $declared['options'] ?? null ) === true ? array_values( $declared['options'] ) : null,
					'optionLabels'	=> is_array( $declared['optionLabels'] ?? null ) === true ? $declared['optionLabels'] : ( is_array( $declared['labels'] ?? null ) === true ? $declared['labels'] : null ),
					'responsive'	=> ( $declared['responsive'] ?? false ) === true,
					'min'					=> $declared['min'] ?? null,
					'max'					=> $declared['max'] ?? null,
				];

			[ $variants, $standard, $variantLabels ] = self::variants( $appData, $name );

			return [
				'label'				=> $schema['label'],
				'group'				=> (string) ( $schema['group'] ?? '' ),
				'container'		=> isset( $schema['offers'] ) === true && $name !== 'html',
				'source'			=> (string) ( $schema['source'] ?? 'none' ),
				'format'			=> (string) ( $schema['format'] ?? '' ),
				'id'					=> self::idAttribute( $schema ),
				'attributes'	=> $attributes,
				'variants'		=> $variants,
				'variantLabels'	=> $variantLabels,
				'standard'		=> $standard,
			];
		}

		/**
		 *	The schemas of the calls of a tree, and of the names that are asked for besides
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *	@param		array			$more					Names to add
		 *
		 *	@return 	array											name => schema()
		 */
		public static function schemas( array &$appData, array $nodes, array $registry, array $more = [] ): array {

			$names = [];
			self::names( $nodes, $names );
			$out = [];

			foreach( array_merge( array_keys( $names ), $more ) as $name )
				if( isset( $registry[$name] ) === true )
					$out[(string) $name] = self::schema( $appData, $registry, (string) $name );

			return $out;
		}

		/**
		 *	The variants of a component, the standard one and the label of each that has one - as the register reads them: the schema's, what the callbacks of the
		 *	variants add, and the labels of both
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name
		 *
		 *	@return 	array											[ the variants, the standard, variant => label (a text or a locale => text map) ]
		 */
		public static function variants( array &$appData, string $name ): array {

			$answer = \Nino\Components::variants( $appData, $name );
			$labels = [];

			foreach( $answer['variants'] as $word => $label )
				if( $label !== null )
					$labels[(string) $word] = $label;

			return [ array_map( 'strval', array_keys( $answer['variants'] ) ), $answer['standard'], $labels ];
		}

		/**
		 *	The node a position names, '0.1.0': the index in each level, the blanks counted
		 *
		 *	@param		array			$nodes
		 *	@param		string		$position
		 *
		 *	@return 	array|null
		 */
		public static function at( array $nodes, string $position ): ?array {

			if( preg_match( '/^\d+(?:\.\d+)*$/D', $position ) !== 1 )
				return null;

			$node = null;

			foreach( explode( '.', $position ) as $index ) {

				$node = $nodes[(int) $index] ?? null;

				if( is_array( $node ) === false )
					return null;

				$nodes = is_array( $node['c'] ?? null ) === true ? $node['c'] : [];
			}

			return $node;
		}

		/**
		 *	The names of the calls from the top of a tree down to the node a position names, the node itself last - the path the register asks for
		 *
		 *	@param		array			$nodes
		 *	@param		string		$position
		 *
		 *	@return 	array|null								null where the position names no call
		 */
		public static function route( array $nodes, string $position ): ?array {

			if( preg_match( '/^\d+(?:\.\d+)*$/D', $position ) !== 1 )
				return null;

			$names = [];

			foreach( explode( '.', $position ) as $index ) {

				$node = $nodes[(int) $index] ?? null;

				if( is_array( $node ) === false || is_string( $node['n'] ?? null ) === false )
					return null;

				$names[] = $node['n'];
				$nodes = is_array( $node['c'] ?? null ) === true ? $node['c'] : [];
			}

			return $names;
		}

		/**
		 *	A call as the first line of its notation: its name and attributes, with no content and no end
		 *
		 *	@param		array			$node
		 *	@param		array			$containers		As \Nino\Components::containers() answers
		 *
		 *	@return 	string
		 */
		public static function opening( array $node, array $containers ): string {

			$name = (string) ( $node['n'] ?? '' );
			$written = \Nino\Markup::write( [ [ 'c' => ( $containers[$name] ?? false ) === true ? [] : null ] + $node ], $containers );

			return ( $containers[$name] ?? false ) === true && str_ends_with( $written, '[/'. $name. ']' ) === true ? substr( $written, 0, -strlen( '[/'. $name. ']' ) ) : $written;
		}

		/**
		 *	What check() finds in a tree, as the screen says it: position => [ [ code, detail ], ... ] - the findings of the pieces that are locked and of the call of html
		 *	are left out, the screen shows those as locked
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes
		 *
		 *	@return 	array
		 */
		public static function findings( array &$appData, array $nodes ): array {

			$out = [];
			$skip = [ \Nino\Components::CODE_BARE, \Nino\Components::CODE_FREE, \Nino\Components::CODE_HTML ];

			foreach( \Nino\Components::check( $appData, $nodes ) as $position => $found )
				foreach( $found as $finding )
					if( in_array( $finding['code'], $skip, true ) === false )
						$out[(string) $position][] = [ $finding['code'], $finding['detail'] ];

			return $out;
		}

		/**
		 *	A tree as a draft draws it: the source of each picture that has no file yet is the picture the kernel brings for it, so that the page shows what it will
		 *	have and nothing is written for it
		 *
		 *	@param		array			$nodes
		 *	@param		array			$sources			As sources() answers them for the nodes
		 *	@param		array			$pictures			key of a picture => the reference of the kernel's picture
		 *
		 *	@return 	array
		 */
		public static function shown( array $nodes, array $sources, array $pictures ): array {

			foreach( $sources as $entry )
				if( $entry['kind'] === 'image' && isset( $pictures[$entry['key']] ) === true )
					$nodes = self::_put( $nodes, array_map( 'intval', explode( '.', (string) $entry['path'] ) ), (string) $entry['attribute'], (string) $pictures[$entry['key']] );

			return $nodes;
		}

		private static function _put( array $nodes, array $path, string $attribute, string $value ): array {

			$at = array_shift( $path );

			if( isset( $nodes[$at] ) === false )
				return $nodes;

			if( $path !== [] )
				$nodes[$at]['c'] = is_array( $nodes[$at]['c'] ?? null ) === true ? self::_put( $nodes[$at]['c'], $path, $attribute, $value ) : ( $nodes[$at]['c'] ?? null );
			else if( $attribute === '' )
				$nodes[$at]['s'] = $value;
			else
				$nodes[$at]['a'][$attribute] = $value;

			return $nodes;
		}

		/**
		 *	The ids of the ids a tree names, the first of them for each call that has any: what must be told apart. A second one is the answer, null where all differ
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *
		 *	@return 	string|null								The first id that stands twice
		 */
		public static function twice( array $nodes, array $registry ): ?string {

			$seen = [];

			return self::_twice( $nodes, $registry, $seen );
		}

		private static function _twice( array $nodes, array $registry, array &$seen ): ?string {

			foreach( $nodes as $node ) {

				if( isset( $node['n'] ) === false )
					continue;

				if( isset( $registry[$node['n']] ) === true ) {

					$name = self::idAttribute( $registry[$node['n']] );
					$id = $name === '' ? '' : (string) ( $node['a'][$name] ?? '' );

					if( $id !== '' ) {

						if( isset( $seen[$id] ) === true )
							return $id;

						$seen[$id] = true;
					}
				}

				if( is_array( $node['c'] ?? null ) === true && ( $again = self::_twice( $node['c'], $registry, $seen ) ) !== null )
					return $again;
			}

			return null;
		}

		/**
		 *	The id the copy of an inner id has: it begins with the id of the new root and a hyphen, and what followed the old root and a hyphen follows it
		 *
		 *	@param		string		$id						The id inside the block
		 *	@param		string		$root					The id of the root it had
		 *	@param		string		$new					The id of the root of the copy
		 *
		 *	@return 	string
		 */
		public static function inner( string $id, string $root, string $new ): string {
			return str_starts_with( $id, $root. '-' ) === true ? $new. substr( $id, strlen( $root ) ) : $new. '-'. $id;
		}

		/**
		 *	Every id a tree names, and with $keys - the keys of the words and the slots of the page - every scope that stands in them: what a new id must not be
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *	@param		array			$keys
		 *
		 *	@return 	array											The ids
		 */
		public static function taken( array $nodes, array $registry, array $keys ): array {

			$taken = [];
			self::_taken( $nodes, $registry, $taken );

			foreach( $keys as $key )
				if( count( $parts = explode( '/', (string) $key ) ) === 5 )
					$taken[$parts[3]] = true;

			return array_keys( $taken );
		}

		private static function _taken( array $nodes, array $registry, array &$taken ): void {

			foreach( $nodes as $node ) {

				if( isset( $node['n'] ) === false )
					continue;

				if( isset( $registry[$node['n']] ) === true ) {

					$name = self::idAttribute( $registry[$node['n']] );

					if( $name !== '' && is_string( $node['a'][$name] ?? null ) === true && $node['a'][$name] !== '' )
						$taken[$node['a'][$name]] = true;
				}

				if( is_array( $node['c'] ?? null ) === true )
					self::_taken( $node['c'], $registry, $taken );
			}
		}

		/**
		 *	What a person who may change the content of a page may do to its template - the one rule, for the draft, the save and the last state
		 *
		 *	The top level of the new tree is the old one with its blocks moved, copied or left out: every piece that is not a block - free markup, a call that has no
		 *	schema - and every block that holds some stands where it stood, in number and order, to the byte (the blanks round it are the layout and may be what
		 *	they like; the fixed texts of its calls may be what they like); it is not moved, copied or taken out, and what is between two of them stays between them.
		 *	Every other block is one of the old ones of its run, with the fixed texts of its calls as they please and nothing else changed, or a copy of one: its
		 *	root has the id of the original with the next number - an id that is no id of the page and has no words or slots - and what is inside has that id in front
		 *	(inner()). No id stands twice
		 *
		 *	A block that is none of the old ones is a new one where $offers has the tree of a block for its id: it is that tree but for the ids and the fixed texts, and
		 *	its root has the id of the block or the first number behind it that is no id of the page and has no words or slots, as a copy has. It stands in a run of the
		 *	top level like any other block
		 *
		 *	@param		array			$old
		 *	@param		array			$new
		 *	@param		array			$registry			The schemas
		 *	@param		array			$taken				What the old page uses as an id, see taken()
		 *	@param		array			$offers				id of the root of a new block => the root of the block it is, laid out as it is put in
		 *
		 *	@return 	string|null								What is refused: 'ids', 'free', 'block', 'copy' or 'id'; null where it is the old page made new
		 */
		public static function refused( array $old, array $new, array $registry, array $taken = [], array $offers = [] ): ?string {

			if( self::twice( $new, $registry ) !== null )
				return 'ids';

			$kept = [ [], [] ];
			$runs = [ [ [] ], [ [] ] ];

			foreach( [ $old, $new ] as $at => $nodes )
				foreach( $nodes as $node ) {

					$kind = self::kind( $node, $registry );

					if( $kind === 'block' && self::_free( $node, $registry ) === false )
						$runs[$at][count( $runs[$at] ) - 1][] = $node;
					else if( $kind !== 'gap' ) {
						$kept[$at][] = isset( $node['t'] ) === true ? [ 't' => trim( $node['t'], " \t\r\n" ) ] : $node;
						$runs[$at][] = [];
					}
				}

			if( count( $kept[0] ) !== count( $kept[1] ) )
				return 'free';

			foreach( $kept[0] as $at => $piece ) {

				$other = $kept[1][$at];

				if( isset( $piece['n'] ) === true && isset( $registry[$piece['n']] ) === true ? ( isset( $other['n'] ) === false || self::_alike( $piece, $other, $registry, [ self::_id( $piece, $registry ), self::_id( $piece, $registry ) ] ) === false ) : json_encode( $piece ) !== json_encode( $other ) )
					return 'free';
			}

			// A new block stands between the calls of the frames of the page - the header and the footer -, and anywhere where the page has none
			$frames = [];

			foreach( $kept[0] as $at => $piece )
				if( isset( $piece['n'] ) === true && isset( $registry[$piece['n']] ) === false && $piece['n'] === 'template' )
					$frames[] = $at;

			foreach( $runs[1] as $run => $news )
				foreach( self::_run( $runs[0][$run] ?? [], $news, $registry, $taken, $offers, $frames === [] || ( $run > $frames[0] && ( count( $frames ) === 1 || $run <= end( $frames ) ) ) ) as $why )
					return $why;

			return null;
		}

		/**
		 *	What a person who may design may do to a template, and the one who may develop: the new tree differs from the old as far as it likes, in what the register
		 *	holds, and in nothing else. The register is asked of both (\Nino\Components::check()) and the new tree may have nothing against it that the old one had not
		 *	by the same name and attributes. Free markup and calls without a schema - and for the one who may not develop the call of html and what it holds - are
		 *	pieces of the old tree to the byte, and the pieces that follow one another are one unit: it stays in its container, whole and in its order, and is moved
		 *	or taken out as a whole, never split, never put in another container, never made twice. No id stands twice, a new id is no id of the page and has no
		 *	words or slots. An address that runs a script or leaves the site and a textfill in a value are the old ones or none, where old means the same attribute of
		 *	a call of the same name. The size, the number of calls and the depth are the canonical reading's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$old					The tree the page has
		 *	@param		array			$new					The tree that is asked for
		 *	@param		array			$registry
		 *	@param		array			$taken				What the old page uses as an id, see taken()
		 *	@param		bool			$develop			Whether the call of html may be new
		 *
		 *	@return 	string|null								What is refused: 'ids', 'html', 'free', 'unit', 'check', 'link', 'value' or 'id'; null where the tree is taken
		 */
		public static function designed( array &$appData, array $old, array $new, array $registry, array $taken, bool $develop ): ?string {

			if( self::twice( $new, $registry ) !== null )
				return 'ids';

			$was = [];
			$now = [];
			$wasUnits = [];
			$nowUnits = [];
			self::_pieces( $old, $registry, $develop, $was, $wasUnits );
			self::_pieces( $new, $registry, $develop, $now, $nowUnits );
			$had = array_count_values( array_column( $was, 0 ) );

			foreach( $now as $piece ) {

				if( ( $had[$piece[0]] ?? 0 ) > 0 )
					$had[$piece[0]]--;
				else
					return $piece[1] === true ? 'html' : 'free';
			}

			$had = array_count_values( $wasUnits );

			foreach( $nowUnits as $unit ) {

				if( ( $had[$unit] ?? 0 ) > 0 )
					$had[$unit]--;
				else
					return 'unit';
			}

			$stood = array_count_values( self::_marks( $appData, $old ) );

			foreach( array_count_values( self::_marks( $appData, $new ) ) as $mark => $count )
				if( $count > ( $stood[$mark] ?? 0 ) )
					return 'check';

			$known = [];
			$given = [];
			self::_values( $old, $registry, $known );
			self::_values( $new, $registry, $given );
			$known = array_count_values( array_column( $known, 0 ) );

			foreach( $given as [ $key, $value, $link ] ) {

				if( ( $known[$key] ?? 0 ) > 0 ) {
					$known[$key]--;
					continue;
				}

				if( str_contains( $value, '[[' ) === true )
					return 'value';

				if( $link === true && preg_match( '#^(?:(?:javascript|data|vbscript):|[/\\\\]{2})#', strtolower( (string) preg_replace( '/[\x00-\x20]+/', '', $value ) ) ) === 1 )
					return 'link';
			}

			$here = self::taken( $old, $registry, [] );

			foreach( self::taken( $new, $registry, [] ) as $id )
				if( in_array( $id, $here, true ) === false && ( self::isId( $id ) === false || in_array( $id, $taken, true ) === true ) )
					return 'id';

			return null;
		}

		/**
		 *	The pieces of a tree that a person who designs does not write: free markup, a fill, a call that has no schema, and - where the call of html is not for the
		 *	person - html with what it holds. A piece is the text without its blanks, or the node as it is written. The pieces that follow one another in a container,
		 *	with nothing but blanks between, are a unit; it is told by the container it stands in - the names of the calls it stands in, from the top of the page down, so that it never gets an ancestor it did not have: no loop, no other kind of container - and what it holds
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *	@param		bool			$develop
		 *	@param		array			&$pieces			(reference) [ [ the piece as it is compared, whether it is the call of html ], ... ]
		 *	@param		array			&$units				(reference) The units, each as a string
		 *	@param		string		$parent				The names of the calls the nodes stand in, from the top down
		 *
		 *	@return 	void
		 */
		private static function _pieces( array $nodes, array $registry, bool $develop, array &$pieces, array &$units, string $parent = '' ): void {

			$run = [];

			foreach( $nodes as $node ) {

				$piece = null;

				if( isset( $node['n'] ) === true ) {

					if( isset( $registry[$node['n']] ) === true && ( $node['n'] !== 'html' || $develop === true ) ) {

						if( $run !== [] )
							$units[] = (string) json_encode( [ $parent, $run ] );

						$run = [];

						if( is_array( $node['c'] ?? null ) === true )
							self::_pieces( $node['c'], $registry, $develop, $pieces, $units, $parent. '/'. $node['n'] );

						continue;
					}

					$piece = [ (string) json_encode( $node ), $node['n'] === 'html' ];
				}
				else if( isset( $node['f'] ) === true )
					$piece = [ (string) json_encode( $node ), false ];
				else if( isset( $node['t'] ) === true && trim( (string) $node['t'], " \t\r\n" ) !== '' )
					$piece = [ (string) json_encode( [ 't' => trim( (string) $node['t'], " \t\r\n" ) ] ), false ];

				if( $piece !== null ) {
					$pieces[] = $piece;
					$run[] = $piece[0];
				}
			}

			if( $run !== [] )
				$units[] = (string) json_encode( [ $parent, $run ] );
		}

		/**
		 *	What the register has against a tree, as marks that do not depend on the place: the code, what it is about and - for a value that is none of its attribute's
		 *	and for an attribute that is none - the value, so that the same wrong value is the same mark wherever it stands and a new one is a new mark
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes
		 *
		 *	@return 	array											A list of marks, one for each finding
		 */
		private static function _marks( array &$appData, array $nodes ): array {

			$marks = [];

			foreach( \Nino\Components::check( $appData, $nodes ) as $position => $found ) {

				$node = self::at( $nodes, (string) $position ) ?? [];

				foreach( $found as $finding ) {

					// What is not in the register - html, a call without a schema, free markup - is the pieces' rule
					if( in_array( $finding['code'], [ \Nino\Components::CODE_HTML, \Nino\Components::CODE_BARE, \Nino\Components::CODE_FREE, \Nino\Components::CODE_UNKNOWN ], true ) === true )
						continue;

					$value = in_array( $finding['code'], [ \Nino\Components::CODE_OUTSIDE, \Nino\Components::CODE_FOREIGN ], true ) === true ? (string) json_encode( $node['a'][$finding['detail']] ?? null ) : '';
					$marks[] = $finding['code']. '|'. $finding['detail']. '|'. $value;
				}
			}

			return $marks;
		}

		/**
		 *	The values of the attributes of the calls of a tree that have a schema, each with the name of the call and of the attribute - which is what makes it
		 *	known - and whether the attribute is a link
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *	@param		array			&$out					(reference) [ [ call, attribute and value as one string, value, link ], ... ]
		 *
		 *	@return 	void
		 */
		private static function _values( array $nodes, array $registry, array &$out ): void {

			foreach( $nodes as $node ) {

				if( isset( $node['n'] ) === false || isset( $registry[$node['n']] ) === false )
					continue;

				foreach( (array) ( $node['a'] ?? [] ) as $name => $value )
					if( is_string( $value ) === true )
						$out[] = [ $node['n']. "\0". $name. "\0". $value, $value, ( $registry[$node['n']]['attributes'][$name]['type'] ?? '' ) === 'link' ];

				if( is_array( $node['c'] ?? null ) === true )
					self::_values( $node['c'], $registry, $out );
			}
		}

		/**
		 *	The fixed texts of a tree that hold the start of a textfill, by the call they are of: what a text may keep that it had
		 *
		 *	@param		array			$nodes
		 *
		 *	@return 	array											call and text as one string => how many
		 */
		public static function fills( array $nodes ): array {

			$out = [];

			foreach( $nodes as $node ) {

				if( isset( $node['n'] ) === false )
					continue;

				if( is_string( $node['a']['text'] ?? null ) === true && str_contains( $node['a']['text'], '[[' ) === true ) {
					$key = $node['n']. "\0". $node['a']['text'];
					$out[$key] = ( $out[$key] ?? 0 ) + 1;
				}

				if( is_array( $node['c'] ?? null ) === true )
					foreach( self::fills( $node['c'] ) as $key => $count )
						$out[$key] = ( $out[$key] ?? 0 ) + $count;
			}

			return $out;
		}

		/**
		 *	Whether the blocks of a run are the old ones of that run
		 *
		 *	@param		array			$olds
		 *	@param		array			$news
		 *	@param		array			$registry
		 *	@param		array			$taken
		 *	@param		array			$offers				See refused()
		 *	@param		bool			$room					Whether a new block may stand in this run
		 *
		 *	@return 	array											[ 'block' ] or [ 'id' ] for what is refused, [] for nothing
		 */
		private static function _run( array $olds, array $news, array $registry, array $taken, array $offers, bool $room ): array {

			// The blocks that are the old ones come first: a copy that stands before its original is not mistaken for it
			$settled = [];

			foreach( $news as $at => $block )
				foreach( $olds as $index => $original ) {

					$ids = [ self::_id( $original, $registry ), self::_id( $block, $registry ) ];

					if( $ids[0] === $ids[1] && isset( $settled[$index] ) === false && self::_alike( $original, $block, $registry, $ids ) === true ) {
						$settled[$index] = $at;
						break;
					}
				}

			$kept = array_flip( $settled );

			foreach( $news as $at => $block ) {

				if( isset( $kept[$at] ) === true )
					continue;

				$pick = null;
				$now = self::_id( $block, $registry );

				foreach( $olds as $index => $original )
					if( self::_alike( $original, $block, $registry, [ self::_id( $original, $registry ), $now ] ) === true ) {
						$pick = $index;
						break;
					}

				if( $pick === null ) {

					if( $room === false || $now === '' || isset( $offers[$now] ) === false )
						return [ 'block' ];

					// A block that is put in: the tree of its block, with an id that nobody uses
					if( self::_alike( $offers[$now], $block, $registry, [ self::_id( $offers[$now], $registry ), $now ] ) === false )
						return [ 'block' ];

					if( self::isId( $now ) === false || in_array( $now, $taken, true ) === true || preg_match( '/^'. preg_quote( (string) preg_replace( '/-\d+$/', '', self::_id( $offers[$now], $registry ) ), '/' ). '(?:-[1-9]\d*)?$/D', $now ) !== 1 )
						return [ 'id' ];

					continue;
				}

				// The id of a copy is the one the rule makes: the base of the original and the next number, one nobody uses
				$was = self::_id( $olds[$pick], $registry );

				if( $was !== '' && ( $now === $was || self::isId( $now ) === false || preg_match( '/^'. preg_quote( (string) preg_replace( '/-\d+$/', '', $was ), '/' ). '-[1-9]\d*$/D', $now ) !== 1 || in_array( $now, $taken, true ) === true ) )
					return [ 'id' ];
			}

			return [];
		}

		/**
		 *	Whether a block is a block of the catalogue that was put in: the tree of the block but for the ids, which are the root's new one and what follows it, and
		 *	the fixed texts
		 *
		 *	@param		array			$block				A block of the page
		 *	@param		array			$expected			The root of the block of the catalogue
		 *	@param		array			$registry
		 *
		 *	@return 	bool
		 */
		public static function likeBlock( array $block, array $expected, array $registry ): bool {
			return self::_alike( $expected, $block, $registry, [ self::_id( $expected, $registry ), self::_id( $block, $registry ) ] );
		}

		/**
		 *	Which id of a copy is which id of the original: a block and its copy have the same calls, so the ids come in pairs
		 *
		 *	@param		array			$old					The original
		 *	@param		array			$new					The copy
		 *	@param		array			$registry
		 *
		 *	@return 	array											id of the copy => id of the original
		 */
		public static function pairs( array $old, array $new, array $registry ): array {

			$pairs = [];
			$one = self::_id( $old, $registry );
			$two = self::_id( $new, $registry );

			if( $one !== '' && $two !== '' )
				$pairs[$two] = $one;

			foreach( (array) ( $old['c'] ?? [] ) as $at => $node )
				if( isset( $node['n'] ) === true && isset( $new['c'][$at]['n'] ) === true )
					$pairs += self::pairs( $node, $new['c'][$at], $registry );

			return $pairs;
		}

		private static function _id( array $block, array $registry ): string {

			$name = isset( $registry[$block['n']] ) === true ? self::idAttribute( $registry[$block['n']] ) : '';

			return $name === '' ? '' : (string) ( $block['a'][$name] ?? '' );
		}

		/**
		 *	Whether a block holds free markup, a fill or a call that has no schema anywhere inside it
		 *
		 *	@param		array			$block
		 *	@param		array			$registry
		 *
		 *	@return 	bool
		 */
		private static function _free( array $block, array $registry ): bool {

			foreach( (array) ( $block['c'] ?? [] ) as $node ) {

				if( isset( $node['f'] ) === true || ( isset( $node['t'] ) === true && trim( $node['t'], " \t\r\n" ) !== '' ) )
					return true;

				if( isset( $node['n'] ) === true && ( isset( $registry[$node['n']] ) === false || self::_free( $node, $registry ) === true ) )
					return true;
			}

			return false;
		}

		/**
		 *	Whether a block is an old one: the same calls with the same sources and attributes in the same order - but for the ids, which are the old ones or the
		 *	copy's, and the fixed texts - and the same text between them to the byte
		 *
		 *	@param		array			$old
		 *	@param		array			$new
		 *	@param		array			$registry
		 *	@param		array			$ids					[ the id of the old root, that of the new ]
		 *
		 *	@return 	bool
		 */
		private static function _alike( array $old, array $new, array $registry, array $ids ): bool {

			if( ( $old['n'] ?? null ) !== ( $new['n'] ?? null ) || ( $old['s'] ?? '' ) !== ( $new['s'] ?? '' ) )
				return false;

			$schema = $registry[$old['n']] ?? null;
			$idName = $schema === null ? '' : self::idAttribute( $schema );
			$was = (array) ( $old['a'] ?? [] );
			$now = (array) ( $new['a'] ?? [] );

			if( array_keys( $was ) !== array_keys( $now ) )
				return false;

			foreach( $was as $name => $value ) {

				if( $name === 'text' && $schema !== null && is_string( $value ) === true && is_string( $now[$name] ) === true )
					continue;

				if( $name === $idName && $idName !== '' && is_string( $value ) === true ) {

					// The root has the id it was given; what is inside has the root's new id in front
					$expected = $ids[0] === $ids[1] ? $value : ( $value === $ids[0] ? $ids[1] : self::inner( $value, $ids[0], $ids[1] ) );

					if( $now[$name] !== $expected )
						return false;

					continue;
				}

				if( $now[$name] !== $value )
					return false;
			}

			if( is_array( $old['c'] ?? null ) !== is_array( $new['c'] ?? null ) )
				return false;

			if( is_array( $old['c'] ?? null ) === false )
				return true;

			if( count( $old['c'] ) !== count( $new['c'] ) )
				return false;

			foreach( $old['c'] as $at => $node ) {

				$other = $new['c'][$at];

				if( isset( $node['n'] ) === true ) {

					if( isset( $other['n'] ) === false || self::_alike( $node, $other, $registry, $ids ) === false )
						return false;
				}
				else if( json_encode( $node ) !== json_encode( $other ) )
					return false;
			}

			return true;
		}
	}
}
