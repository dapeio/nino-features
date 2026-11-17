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

		/**
		 *	The blocks of the dialog: each that \Nino\Blocks offers for a page - one that lacks the components of a feature that is off with the names of the features, to be shown grey
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											[ 'blocks' => [ key, name, description, category, origin, own, hint, off ], 'categories' => the words of the categories, in the order of the dialog ]
		 */
		public static function all( array &$appData ): array {

			$features = \Nino\Features::all( $appData );
			$list = [];

			foreach( \Nino\Blocks::all( $appData ) as $key => $block ) {

				if( $block['wrap'] !== self::PAGE || ( ( $block['missing'] ?? [] ) === [] && self::block( $appData, (string) $key ) === null ) )
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
					'origin'			=> self::_origin( $features, (string) $block['origin'], $block['label'] ?? null ),
					'own'					=> (string) $block['origin'] === 'project',
					'hint'				=> $missing === [] ? self::hint( $appData, $block, ( $features['themes']['active'] ?? false ) === true ) : null,
					'off'					=> $missing === [] ? null : $missing,
				];
			}

			return [ 'blocks' => $list, 'categories' => array_values( array_diff( \Nino\Blocks::CATEGORIES, [ 'frame' ] ) ) ];
		}

		/**
		 *	One block as it is put into a page: the root of its template laid out the way the kernel writes a template, what the calls of it are, the texts of it in every
		 *	language it has and the places of its sources
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	array|null								[ 'key', 'id', 'tree', 'known', 'texts', 'slots' => [ [ name, scope, word, kind ] ], 'images' => name => [ placeholder, width, height, label ] ]; null for a key that \Nino\Blocks does not offer for a page
		 */
		public static function block( array &$appData, string $key ): ?array {

			$block = \Nino\Blocks::get( $appData, $key );

			if( $block === null || $block['wrap'] !== self::PAGE || ( $block['missing'] ?? [] ) !== [] )
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
