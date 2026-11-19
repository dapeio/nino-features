<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// The blocks the Builder puts into a page: what \Nino\Blocks offers now, as the dialog lists them, as one block is put in and as a tile of the dialog draws it.
	// Nothing is written here and nothing is trusted that a person sent: a block is a key of \Nino\Blocks, whatever else a request says of it
	class Library {

		// What the drafts of the tiles are called in \Nino\Preview, and how many of them a session holds: a dialog shows the blocks of one category
		public const string KIND = 'builder-tile';
		public const int TILES = 12;

		// The root of a block that stands on a page: the frames are blocks too, and are no part of a page
		public const string PAGE = 'section-wrap';

		// The groups the dialog "Insert block" shows, in its order: the categories of the kernel but the frames, and the dynamic content - a block with a loop - between the static and the proof
		public const array GROUPS = [ 'intro', 'content', 'dynamic', 'trust', 'offer', 'action', 'contact' ];

		/**
		 *	The blocks of the dialog: each that \Nino\Blocks offers for a page - one that lacks the components of a feature that is off with the names of the features, to be shown grey
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											[ 'blocks' => [ key, name, description, category, origin, own, variants, hint, off ], 'categories' => the words of the categories, in the order of the dialog ]
		 */
		public static function all( array &$appData, string $wrap = self::PAGE ): array {

			$features = \Nino\Features::all( $appData );
			$list = [];

			foreach( \Nino\Blocks::all( $appData ) as $key => $block ) {

				if( $block['wrap'] !== $wrap || ( ( $block['missing'] ?? [] ) === [] && self::block( $appData, (string) $key, $wrap ) === null ) )
					continue;

				// A block that lacks the components of a feature that is off says which features: the entry is grey and is not put in
				$missing = [];

				foreach( (array) ( $block['missing'] ?? [] ) as $feature )
					$missing[] = isset( $features[$feature]['name'] ) === true ? $features[$feature]['name'] : (string) $feature;

				$list[] = [
					'key'					=> (string) $key,
					'name'				=> $block['name'],
					'description'	=> $block['description'],
					'category'		=> $block['category'],
					'group'				=> self::group( $appData, (string) $key, $block ),
					'origin'			=> self::_origin( $features, (string) $block['origin'], $block['label'] ?? null ),
					'own'					=> (string) $block['origin'] === 'project',
					'variants'		=> count( (array) ( $block['alternatives'] ?? [] ) ),
					'hint'				=> $missing === [] ? self::hint( $appData, $block, ( $features['themes']['active'] ?? false ) === true ) : null,
					'off'					=> $missing === [] ? null : $missing,
				];
			}

			return [ 'blocks' => $list, 'categories' => self::GROUPS ];
		}

		/**
		 *	The root a block must have to stand in a template: a section for a page, the header for the frame of the head, the footer for the frame of the foot
		 *
		 *	@param		string		$template
		 *
		 *	@return 	string
		 */
		public static function wrapOf( string $template ): string {

			if( $template === Store::FRAMES['header'] )
				return 'header-wrap';

			return $template === Store::FRAMES['footer'] ? 'footer-wrap' : self::PAGE;
		}

		/**
		 *	The group a block is shown in: 'dynamic' where its template has a loop - the same recognition as the register's schema makes - whatever category block.json says, else the
		 *	category. Nothing is written: the category in block.json is the one the block has
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *	@param		array			$block				As \Nino\Blocks::all() has it
		 *
		 *	@return 	string
		 */
		public static function group( array &$appData, string $key, array $block ): string {

			$registry = \Nino\Components::components( $appData );
			$looped = static function( array $nodes ) use ( &$looped, &$appData, $registry ): bool {

				foreach( $nodes as $node ) {

					if( isset( $node['n'] ) === false )
						continue;

					if( isset( $registry[$node['n']] ) === true && ( Tree::schema( $appData, $registry, (string) $node['n'] )['loop'] ?? false ) === true )
						return true;

					if( is_array( $node['c'] ?? null ) === true && $looped( $node['c'] ) === true )
						return true;
				}

				return false;
			};

			return $looped( Tree::read( $appData, (string) ( \Nino\Blocks::get( $appData, $key )['source'] ?? '' ) ) ) === true ? 'dynamic' : (string) $block['category'];
		}

		/**
		 *	One block as it is put into a page: the root of its template laid out the way the kernel writes a template, what the calls of it are, the texts of it in every
		 *	language it has and the places of its sources
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *	@param		string		$wrap				The root the block has: a section for a page, a header or a footer for a frame
		 *
		 *	@return 	array|null								[ 'key', 'id', 'tree', 'known', 'texts', 'slots' => [ [ name, scope, word, kind ] ], 'images' => name => [ placeholder, width, height, label ] ]; null for a key that \Nino\Blocks does not offer for a page
		 */
		public static function block( array &$appData, string $key, string $wrap = self::PAGE ): ?array {

			$block = \Nino\Blocks::get( $appData, $key );

			if( $block === null || $block['wrap'] !== $wrap || ( $block['missing'] ?? [] ) !== [] )
				return null;

			$root = null;

			foreach( Tree::read( $appData, (string) $block['source'] ) as $node )
				if( isset( $node['n'] ) === true && $root === null )
					$root = $node;

			if( $root === null )
				return null;

			$tree = Tree::strip( \Nino\Markup::layout( [ $root ], 0 ) );
			$slots = [];

			// A block is offered by one rule for where a link goes, whoever made it: a target in its tree or in a text a link of it reads that the rule does not take is no block to offer
			$registry = \Nino\Components::components( $appData );

			if( Tree::linksOk( $appData, $tree, $registry, false ) === false )
				return null;

			foreach( $block['slots'] as $slot )
				if( $slot['kind'] === 'link' )
					foreach( $block['texts'] as $words )
						if( is_string( $words[$slot['name']] ?? null ) === true && Tree::linkTarget( $appData, $words[$slot['name']], false ) === false )
							return null;

			foreach( $block['slots'] as $slot )
				$slots[] = [ 'name' => $slot['name'], 'scope' => $slot['scope'], 'word' => substr( (string) $slot['source'], 1 ), 'kind' => $slot['kind'] === 'image' ? 'image' : 'text' ];

			return [ 'key' => $key, 'id' => $block['id'], 'tree' => $tree, 'known' => Tree::known( $tree, \Nino\Components::components( $appData ) ), 'texts' => $block['texts'], 'slots' => $slots, 'images' => $block['images'] ];
		}

		/**
		 *	The blocks of a page that are blocks of the catalogue that were put in: for each top-level block whose id is not in $taken the first block that \Nino\Blocks
		 *	offers for a page that it is the tree of, as the rule of the Tree holds a new block to it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes				The tree of the page
		 *	@param		array			$taken				The ids the page it came from has
		 *
		 *	@return 	array											id of the root => the root of the block, laid out
		 */
		public static function offers( array &$appData, array $nodes, array $taken ): array {

			$registry = \Nino\Components::components( $appData );
			$offers = [];

			foreach( $nodes as $node ) {

				$id = isset( $node['n'] ) === true && isset( $registry[$node['n']] ) === true ? (string) ( $node['a'][Tree::idAttribute( $registry[$node['n']] )] ?? '' ) : '';

				if( $id === '' || in_array( $id, $taken, true ) === true )
					continue;

				foreach( array_keys( \Nino\Blocks::all( $appData ) ) as $key )
					if( ( $block = self::block( $appData, (string) $key ) ) !== null && Tree::likeBlock( $node, $block['tree'][0], $registry ) === true ) {
						$offers[$id] = $block['tree'][0];
						break;
					}
			}

			return $offers;
		}

		/**
		 *	Which block of the catalogue a block of a page is, for a block that has variants: a block that \Nino\Blocks offers for a page that has variants, whose places the tree has and
		 *	whose calls it is - the rule of the Tree for a block that was put in, with the attributes left out. It is told from the tree; a request may only name which of the blocks of
		 *	that form it put in, and only one of them is taken. Where several blocks have the form - the ids inside them are then the same - the one put in this session is it, else the one whose variant the
		 *	calls are in; where that does not decide and the blocks have variants of their own there is no
		 *	answer, never the variants of a block the page does not have. A block with a call put in, taken out or named another, or another source, is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$node					The root of a block of the page
		 *	@param		array			$registry
		 *	@param		array			$inserted			id of the root of a block put in this session => its key
		 *
		 *	@return 	array|null								[ 'key', 'block' (as \Nino\Blocks::get() answers it), 'current' (the name of the variant, null where it is its own), 'items' => [ [ name, label ] ] ] - null where no block of the catalogue with variants is it
		 */
		public static function origin( array &$appData, array $node, array $registry, array $inserted = [] ): ?array {

			if( ( $node['n'] ?? '' ) !== self::PAGE || isset( $node['a'] ) === false )
				return null;

			$name = isset( $registry[$node['n']] ) === true ? Tree::idAttribute( $registry[$node['n']] ) : '';
			$page = $name === '' ? '' : (string) ( $node['a'][$name] ?? '' );
			$found = [];

			foreach( \Nino\Blocks::all( $appData ) as $key => $listed ) {

				if( ( $listed['alternatives'] ?? [] ) === [] || ( $listed['wrap'] ?? '' ) !== self::PAGE )
					continue;

				$block = \Nino\Blocks::get( $appData, (string) $key );
				$own = self::block( $appData, (string) $key );

				if( $block === null || $own === null || \Nino\Blocks::variantsFit( $block, [ $node ] ) === false )
					continue;

				// The block is the block of the catalogue when its calls are the calls of the template with the same sources - what the attributes say, a look a person gave it
				// among them, is no part of that: it is what the variants are about
				if( Tree::likeBlock( self::_shape( $node, $registry ), self::_shape( $own['tree'][0], $registry ), $registry ) === false )
					continue;

				$found[(string) $key] = [ 'block' => $block ];
			}

			if( $found === [] )
				return null;

			// Blocks of one form are told apart by their ids first - the ids inside a block put in are the id of its root and what follows it, so a block is only the block whose ids the calls
			// have - then by the block that was put in this very session, then by the one whose variant the calls are in. Where that does not decide and the blocks have variants of
			// their own, no block is chosen: the variants of a block that the page does not have are worse than none
			if( count( $found ) > 1 ) {

				$hinted = $page !== '' && is_string( $inserted[$page] ?? null ) === true ? (string) $inserted[$page] : '';

				if( $hinted !== '' && isset( $found[$hinted] ) === true )
					$found = [ $hinted => $found[$hinted] ];
			}

			if( count( $found ) > 1 ) {

				$named = array_filter( $found, static fn( array $one ): bool => \Nino\Blocks::variantOf( $one['block'], [ $node ] ) !== null );
				$pool = count( $named ) === 1 ? $named : ( $named === [] ? $found : $named );
				$sets = array_unique( array_map( static fn( array $one ): string => (string) json_encode( $one['block']['alternatives'] ), $pool ) );

				if( count( $pool ) > 1 && count( $sets ) > 1 )
					return null;

				$found = [ array_key_first( $pool ) => reset( $pool ) ];
			}

			$key = (string) array_key_first( $found );
			$block = $found[$key]['block'];
			$items = [];

			foreach( $block['alternatives'] as $one => $variant )
				$items[] = [ (string) $one, $variant['label'] ];

			return [ 'key' => $key, 'block' => $block, 'current' => \Nino\Blocks::variantOf( $block, [ $node ] ), 'items' => $items ];
		}

		/**
		 *	Which block of the project a block of a page is built like: the first block of the project, by key, whose call is the call of the block - the attributes left out, what a
		 *	look a person gave it is. It is told from the tree and from nothing a request names; null where no block of the project is it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$node					The root of a block of the page
		 *	@param		array			$registry
		 *
		 *	@return 	string|null
		 */
		public static function sameAs( array &$appData, array $node, array $registry ): ?string {

			if( ( $node['n'] ?? '' ) !== self::PAGE )
				return null;

			foreach( \Nino\Blocks::all( $appData ) as $key => $listed ) {

				if( ( $listed['origin'] ?? '' ) !== 'project' || ( $listed['wrap'] ?? '' ) !== self::PAGE )
					continue;

				$own = self::block( $appData, (string) $key );

				if( $own !== null && Tree::likeBlock( self::_shape( $node, $registry ), self::_shape( $own['tree'][0], $registry ), $registry ) === true )
					return (string) $key;
			}

			return null;
		}

		/**
		 *	The blocks of a page that are built like a block of the project, for the one who may go to the editor of the blocks: the id of each top-level block and the key of
		 *	that block - what the bridge "open in the editor of the blocks" has
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes				The tree of the page
		 *	@param		array			$registry
		 *
		 *	@return 	array											id => key
		 */
		public static function owned( array &$appData, array $nodes, array $registry ): array {

			$owned = [];

			foreach( $nodes as $node ) {

				$id = isset( $node['n'] ) === true && isset( $registry[$node['n']] ) === true ? (string) ( $node['a'][Tree::idAttribute( $registry[$node['n']] )] ?? '' ) : '';
				$key = $id === '' ? null : self::sameAs( $appData, $node, $registry );

				if( $key !== null )
					$owned[$id] = $key;
			}

			return $owned;
		}

		/**
		 *	The blocks of a page that have a choice of variants, for the screen of the one who designs: where the block stands, which catalogue block it is, which variant it has
		 *	and the names of the variants with their labels, in the order of the block
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes				The tree of the page
		 *	@param		array			$registry
		 *
		 *	@return 	array											[ [ at, key, current, items => [ [ name, label ] ] ] ]
		 */
		public static function variants( array &$appData, array $nodes, array $registry, array $inserted = [] ): array {

			$out = [];

			foreach( $nodes as $at => $node )
				if( isset( $node['n'] ) === true && ( $origin = self::origin( $appData, $node, $registry, $inserted ) ) !== null )
					$out[] = [ 'at' => (int) $at, 'key' => $origin['key'], 'current' => $origin['current'], 'items' => $origin['items'] ];

			return $out;
		}

		/**
		 *	A block as the calls it is made of: every call with its source and nothing of its attributes but the id, which the rule for a block that was put in holds to its own form
		 *
		 *	@param		array			$node
		 *	@param		array			$registry
		 *
		 *	@return 	array
		 */
		private static function _shape( array $node, array $registry ): array {

			$name = isset( $registry[$node['n'] ?? ''] ) === true ? Tree::idAttribute( $registry[$node['n']] ) : '';

			$node['a'] = $name !== '' && isset( $node['a'][$name] ) === true ? [ $name => $node['a'][$name] ] : [];

			if( is_array( $node['c'] ?? null ) === true )
				foreach( $node['c'] as $at => $inner )
					if( isset( $inner['n'] ) === true )
						$node['c'][$at] = self::_shape( $inner, $registry );

			return $node;
		}

		/**
		 *	The keys and the pictures a block has when its root has an id: below the namespace of the template, each in the scope of the id that holds it
		 *
		 *	@param		array			$block				As block() answers it
		 *	@param		string		$id						The id the root has
		 *	@param		string		$template
		 *
		 *	@return 	array											[ [ key, kind, name, word ], ... ]
		 */
		public static function places( array $block, string $id, string $template ): array {

			$places = [];

			foreach( $block['slots'] as $slot )
				$places[] = [ 'key' => '/template/'. $template. '/'. ( $slot['scope'] === '' ? $id : $id. '-'. $slot['scope'] ). '/'. $slot['word'], 'kind' => $slot['kind'], 'name' => $slot['name'], 'word' => $slot['word'] ];

			return $places;
		}

		/**
		 *	A tile of the dialog: the block alone, on a bare page that has the stylesheet of the site and nothing of the header or the footer, for a draft that is drawn
		 *	small. The texts are those of the language of the view - or the English ones, where the block has none in it -, the pictures are the kernel's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The page the dialog is on, whose place in the site the draft takes
		 *	@param		string		$key
		 *	@param		string		$locale
		 *
		 *	@return 	array|null								[ 'template', 'source', 'fills', 'locale' ], the draft; null for a key that \Nino\Blocks does not offer for a page
		 */
		public static function tile( array &$appData, string $template, string $key, string $locale ): ?array {

			$block = self::block( $appData, $key );

			if( $block === null )
				return null;

			$registry = \Nino\Components::components( $appData );
			$fills = [];
			$pictures = [];

			foreach( self::places( $block, (string) $block['id'], $template ) as $place ) {

				if( $place['kind'] === 'image' ) {
					$pictures[$place['key']] = (string) ( $block['images'][$place['name']]['placeholder'] ?? '' );
					continue;
				}

				$text = $block['texts'][$locale][$place['name']] ?? $block['texts'][\Nino\Blocks::FALLBACK][$place['name']] ?? '';

				if( is_string( $text ) === true && $text !== '' )
					$fills[$place['key']] = $text;
			}

			$root = Tree::shown( $block['tree'], Tree::sources( $block['tree'], $registry, $template ), array_filter( $pictures ) );
			$page = \Nino\Feature::template( $appData, '/features/Builder/templates', 'tile' );

			return [ 'template' => $template, 'source' => str_replace( '[[block]]', \Nino\Markup::write( $root, \Nino\Components::containers( $appData ) ), $page ), 'fills' => $fills, 'locale' => $locale ];
		}

		/**
		 *	Where a block comes from, as the kernel labels it: the kind - basis, project, feature or theme - and the label the origin has, a text or a locale => text map
		 *
		 *	@param		array			$features			\Nino\Features::all()
		 *	@param		string		$origin				What \Nino\Blocks says
		 *	@param		mixed			$label				What \Nino\Blocks says a person reads for the origin
		 *
		 *	@return 	array											[ 'kind' => basis, project, feature or theme, 'label' => the label ]
		 */
		private static function _origin( array $features, string $origin, mixed $label ): array {

			$kind = $origin === 'project' ? 'project' : ( $origin === 'basis' ? 'basis' : ( isset( $features[$origin] ) === true ? 'feature' : 'theme' ) );

			return [ 'kind' => $kind, 'label' => is_array( $label ) === true || is_string( $label ) === true ? $label : $origin ];
		}

		/**
		 *	What the tile says of a block that names a variant the active mix does not have: the word of the variant and the one it is drawn as. Only a feature that
		 *	tells the register of variants - the Themes - gives it a thing to say, so that with it off there is none
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$block				As \Nino\Blocks::all() has it
		 *	@param		bool			$themes				Whether the Themes are on
		 *
		 *	@return 	array|null								[ 'variant', 'standard', 'labels' => [ 'variant', 'standard' ] - the label the register has for each, a text or a locale => text map, or null ]
		 */
		public static function hint( array &$appData, array $block, bool $themes ): ?array {

			if( $themes === false )
				return null;

			$registry = \Nino\Components::components( $appData );

			foreach( (array) $block['variants'] as $name => $words ) {

				$schema = $registry[$name] ?? null;

				if( $schema === null )
					continue;

				[ $list, $standard, $labels ] = Tree::variants( $appData, (string) $name );

				foreach( (array) $words as $word )
					if( in_array( $word, $list, true ) === false )
						return [ 'variant' => (string) $word, 'standard' => $standard, 'labels' => [ 'variant' => $labels[(string) $word] ?? null, 'standard' => $labels[$standard] ?? null ] ];
			}

			return null;
		}
	}
}
