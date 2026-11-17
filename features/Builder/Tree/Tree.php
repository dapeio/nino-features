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
		 *	(inner()). No id stands twice. A frame has the blocks it had - as many, with the names they had, in their order - and no copy
		 *
		 *	A block that is none of the old ones is a new one where $offers has the tree of a block for its id: it is that tree but for the ids and the fixed texts, and
		 *	its root has the id of the block or the first number behind it that is no id of the page and has no words or slots, as a copy has. It stands in a run of the
		 *	top level like any other block; a frame takes none
		 *
		 *	@param		array			$old
		 *	@param		array			$new
		 *	@param		array			$registry			The schemas
		 *	@param		bool			$frame				A frame of the site
		 *	@param		array			$taken				What the old page uses as an id, see taken()
		 *	@param		array			$offers				id of the root of a new block => the root of the block it is, laid out as it is put in
		 *
		 *	@return 	string|null								What is refused: 'ids', 'free', 'block', 'copy', 'id' or 'frame'; null where it is the old page made new
		 */
		public static function refused( array $old, array $new, array $registry, bool $frame, array $taken = [], array $offers = [] ): ?string {

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
				foreach( self::_run( $runs[0][$run] ?? [], $news, $registry, $frame, $taken, $offers, $frames === [] || ( $run > $frames[0] && ( count( $frames ) === 1 || $run <= end( $frames ) ) ) ) as $why )
					return $why;

			// A frame has the blocks it had: as many, with the names they had, in the order they had
			if( $frame === true ) {

				$names = static fn( array $nodes ): array => array_map( static fn( array $node ): string => (string) $node['n'], array_values( array_filter( $nodes, static fn( array $node ): bool => isset( $node['n'] ) === true ) ) );

				if( $names( $old ) !== $names( $new ) )
					return 'frame';
			}

			return null;
		}

		/**
		 *	Whether the blocks of a run are the old ones of that run
		 *
		 *	@param		array			$olds
		 *	@param		array			$news
		 *	@param		array			$registry
		 *	@param		bool			$frame
		 *	@param		array			$taken
		 *	@param		array			$offers				See refused()
		 *	@param		bool			$room					Whether a new block may stand in this run
		 *
		 *	@return 	array											[ 'block' ], [ 'frame' ] or [ 'id' ] for what is refused, [] for nothing
		 */
		private static function _run( array $olds, array $news, array $registry, bool $frame, array $taken, array $offers, bool $room ): array {

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

					if( $frame === true || $room === false || $now === '' || isset( $offers[$now] ) === false )
						return [ 'block' ];

					// A block that is put in: the tree of its block, with an id that nobody uses
					if( self::_alike( $offers[$now], $block, $registry, [ self::_id( $offers[$now], $registry ), $now ] ) === false )
						return [ 'block' ];

					if( self::isId( $now ) === false || in_array( $now, $taken, true ) === true || preg_match( '/^'. preg_quote( (string) preg_replace( '/-\d+$/', '', self::_id( $offers[$now], $registry ) ), '/' ). '(?:-[1-9]\d*)?$/D', $now ) !== 1 )
						return [ 'id' ];

					continue;
				}

				if( $frame === true )
					return [ 'frame' ];

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
