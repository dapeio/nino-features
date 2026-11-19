<?php
declare(strict_types=1);

namespace Nino\Modules\Blocks {

	use Nino\Modules\Builder\Library;
	use Nino\Modules\Builder\Pictures;
	use Nino\Modules\Builder\Tree;

	// What a block is as a sheet of the editor: the draft of the Builder for one block - the two calls of the frame of the site round it, so that the page that draws it has
	// its head and its foot, and the words and the picture slots of the block in the namespace of a page of the site, the one the draft is drawn on. Nothing is kept here: a
	// sheet is made from the files of a block and from nothing a request names
	class Sheet {

		// The two calls every page of the site has round its blocks, and what the draft of a block has round it
		public const array FRAMES = [ '/templates/html-header', '/templates/html-footer' ];

		/**
		 *	The page a draft of a block is drawn on: the first page of the site, whose template gives the words of the draft their namespace and whose address is where the
		 *	preview is asked for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale
		 *
		 *	@return 	array|null								[ 'template', 'address' ]; null where the site has no page
		 */
		public static function shell( array &$appData, string $locale ): ?array {

			foreach( \Nino\Modules\Builder\Pages::all( $appData, $locale ) as $page )
				if( preg_match( '/^page-[a-z0-9]+(?:-[a-z0-9]+)*$/D', (string) $page['template'] ) === 1 )
					return [ 'template' => (string) $page['template'], 'address' => (string) $page['path'] ];

			return null;
		}

		/**
		 *	A word of the panel in each language of the site: what the dialogs begin with - the suffix of a copy, the name of a new block - in the language of the row it fills.
		 *	A language the panel has no text file of has the word in English
		 *
		 *	@param		array			$locales
		 *	@param		string		$key					The key of the word below /_admin/blocks/
		 *
		 *	@return 	array											locale => word
		 */
		public static function words( array $locales, string $key ): array {

			$out = [];

			foreach( $locales as $locale ) {

				$file = dirname( __DIR__ ). '/text/'. ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/D', (string) $locale ) === 1 && is_file( dirname( __DIR__ ). '/text/'. $locale. '.php' ) === true ? $locale : \Nino\Blocks::FALLBACK ). '.php';
				$words = is_file( $file ) === true ? (array) require $file : [];
				$out[(string) $locale] = (string) ( $words['[[/_admin/blocks/'. $key. ']]'] ?? '' );
			}

			return $out;
		}

		/**
		 *	The key a name makes: lower case letters, digits and hyphens - the umlauts written out - at most as long as a key may be, starting with a letter
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		public static function keyOf( string $name ): string {

			$key = strtr( mb_strtolower( trim( $name ), 'UTF-8' ), [ 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'à' => 'a', 'ç' => 'c', 'ñ' => 'n' ] );
			$key = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $key ), '-' );

			if( $key === '' || preg_match( '/^[a-z]/', $key ) !== 1 )
				$key = 'block-'. $key;

			return rtrim( substr( $key, 0, \Nino\Components::NAME_LENGTH ), '-' );
		}

		/**
		 *	Whether a key is a key of a block already - of any source: a block the kernel lists, one it refused, a folder of the project that is there
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	bool
		 */
		public static function taken( array &$appData, string $key ): bool {

			if( isset( \Nino\Blocks::all( $appData )[$key] ) === true || array_filter( array_keys( \Nino\Blocks::rejected( $appData ) ), static fn( string $where ): bool => str_ends_with( $where, '/'. $key ) === true ) !== [] )
				return true;

			$folder = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' ). \Nino\Blocks::OWN. '/'. $key;

			return file_exists( $folder ) === true || is_link( $folder ) === true;
		}

		/**
		 *	The theme in use and the blocks it brings: its key as the kernel names an origin, and its name
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|null								[ 'key', 'name' ]; null without the feature Themes or a theme in use
		 */
		public static function theme( array &$appData ): ?array {

			if( ( \Nino\Features::all( $appData )['themes']['active'] ?? false ) !== true || class_exists( \Nino\Modules\Themes\Store::class ) === false )
				return null;

			$key = \Nino\Modules\Themes\Store::read( $appData )['active']['theme'] ?? null;

			if( is_string( $key ) === false || $key === '' )
				return null;

			$name = \Nino\Modules\Themes\Reader::read( $appData )['themes'][$key]['name'] ?? $key;

			return [ 'key' => $key, 'name' => $name ];
		}

		/**
		 *	The blocks of the editor: the ones of a source that are blocks of a page
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$origin				'project', or the key of a theme; '' for every source
		 *
		 *	@return 	array											key => the block as \Nino\Blocks::all() lists it
		 */
		public static function listed( array &$appData, string $origin ): array {

			$out = [];

			foreach( \Nino\Blocks::all( $appData ) as $key => $block )
				if( ( $block['wrap'] ?? '' ) === Library::PAGE && ( $block['missing'] ?? [] ) === [] && ( $origin === '' || (string) $block['origin'] === $origin ) )
					$out[(string) $key] = $block;

			return $out;
		}

		/**
		 *	What a section, a row and a column begin with in a new block: what the blocks of the basis write there, read from their templates - of six sections four stand on the
		 *	base or the offset ground (the base is written), of seven rows four are narrow, and no column writes more than its width. Padding, gap and the alignments are the schema's
		 *	own in all of them and are written nowhere, so a new block writes them nowhere either
		 */
		public const array BEGIN = [ 'section-wrap' => [ 'ground' => 'base' ], 'section-row' => [ 'width' => 'narrow' ], 'section-col' => [] ];

		/**
		 *	The pictures a block of the project has of its own, by the place they are for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$block				As Library::block() answers it
		 *
		 *	@return 	array|\stdClass							place => 'png', 'jpg', 'webp' or 'svg'
		 */
		public static function own( array &$appData, array $block ): array|\stdClass {

			$list = [];
			$held = \Nino\Blocks::get( $appData, (string) ( $block['key'] ?? '' ) );

			foreach( (array) ( $held['pictures'] ?? [] ) as $relative )
				if( preg_match( '/^([a-z0-9]+(?:-[a-z0-9]+)*)\.(svg|webp|png|jpg)$/D', basename( (string) $relative ), $found ) === 1 )
					$list[$found[1]] = $found[2];

			return $list === [] ? new \stdClass() : $list;
		}

		/**
		 *	A block with a root of a section, a row and a column and nothing in them, as Library::block() answers a block
		 *
		 *	@param		string		$id						The id of the root
		 *
		 *	@return 	array
		 */
		public static function blank( string $id ): array {

			$root = [ 'n' => Library::PAGE, 's' => '', 'a' => [ 'id' => $id ] + self::BEGIN['section-wrap'], 'c' => [ [ 'n' => 'section-row', 's' => '', 'a' => self::BEGIN['section-row'], 'c' => [ [ 'n' => 'section-col', 's' => '', 'a' => self::BEGIN['section-col'], 'c' => [] ] ] ] ] ];

			return [ 'key' => '', 'id' => $id, 'tree' => Tree::strip( \Nino\Markup::layout( [ $root ], 0 ) ), 'known' => [], 'texts' => [ \Nino\Blocks::FALLBACK => [] ], 'slots' => [], 'images' => [] ];
		}

		/**
		 *	A block with the id of its root and every id inside it made new, as a copy that is its own block has them: the blocks of one form are told apart by the id of their root,
		 *	which a block that is put in on a page keeps. The words and the slots of a block are named from the root and follow of themselves. A block whose new ids would not be ids
		 *	is given back as it is
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$block				As Library::block() answers it
		 *	@param		string		$id						The new id of the root
		 *
		 *	@return 	array
		 */
		public static function reid( array &$appData, array $block, string $id ): array {

			$was = (string) $block['id'];

			if( $id === $was || strlen( $id ) > 32 || Tree::isId( $id ) === false )
				return $block;

			$registry = \Nino\Components::components( $appData );
			$fits = true;
			$tree = [];

			foreach( (array) $block['tree'] as $node )
				$tree[] = self::_renamed( $node, $registry, $was, $id, $fits );

			if( $fits === false )
				return $block;

			$block['tree'] = $tree;
			$block['id'] = $id;

			return $block;
		}

		/**
		 *	The parts of a block with the id of its root and every id inside it made new, as reid() does it for a block as the library has it: the template is read, renamed and written
		 *	back - the words and the slots are named from the root and need nothing. Parts whose new ids would not be ids are given back as they are
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$parts				json, tpl, texts, pictures
		 *	@param		string		$id						The new id of the root
		 *
		 *	@return 	array
		 */
		public static function reidParts( array &$appData, array $parts, string $id ): array {

			if( strlen( $id ) > 32 || Tree::isId( $id ) === false )
				return $parts;

			$nodes = Tree::read( $appData, (string) $parts['tpl'] );
			$at = null;

			foreach( $nodes as $index => $node )
				if( isset( $node['n'] ) === true && $at === null )
					$at = $index;

			if( $at === null )
				return $parts;

			$registry = \Nino\Components::components( $appData );
			$name = isset( $registry[$nodes[$at]['n']] ) === true ? Tree::idAttribute( $registry[$nodes[$at]['n']] ) : '';
			$was = $name === '' ? '' : (string) ( $nodes[$at]['a'][$name] ?? '' );

			if( $was === '' || $was === $id )
				return $parts;

			$fits = true;
			$root = self::_renamed( $nodes[$at], $registry, $was, $id, $fits );

			if( $fits === false )
				return $parts;

			$nodes[$at] = $root;
			$parts['tpl'] = rtrim( \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) ) ). "\n";

			return $parts;
		}

		/**
		 *	A node and what is below it with the ids made new: the id of the root is the new one, an id inside it follows it, and a link to a place of the block goes to the new id
		 */
		private static function _renamed( array $node, array $registry, string $was, string $id, bool &$fits ): array {

			$name = isset( $node['n'] ) === true && isset( $registry[$node['n']] ) === true ? Tree::idAttribute( $registry[$node['n']] ) : '';

			if( $name !== '' && is_string( $node['a'][$name] ?? null ) === true && $node['a'][$name] !== '' ) {

				$node['a'][$name] = $node['a'][$name] === $was ? $id : Tree::inner( $node['a'][$name], $was, $id );

				if( strlen( $node['a'][$name] ) > 32 || Tree::isId( $node['a'][$name] ) === false )
					$fits = false;
			}

			foreach( (array) ( $node['a'] ?? [] ) as $attribute => $value )
				if( is_string( $value ) === true && ( $value === '#'. $was || str_starts_with( $value, '#'. $was. '-' ) ) )
					$node['a'][$attribute] = '#'. ( $value === '#'. $was ? $id : Tree::inner( substr( $value, 1 ), $was, $id ) );

			if( is_array( $node['c'] ?? null ) === true )
				foreach( $node['c'] as $at => $child )
					$node['c'][$at] = self::_renamed( $child, $registry, $was, $id, $fits );

			return $node;
		}

		/**
		 *	The words and the slots of a block as the page they are drawn on has them: the key of each text and picture is below the namespace of the page and the id of its scope
		 *
		 *	@param		array			$block				As Library::block() answers it
		 *	@param		string		$template			The page
		 *	@param		string		$locale
		 *
		 *	@return 	array											'texts' => key => language => text, 'slots' => uri => [ label, labels (language => label), width, height, filename ]
		 */
		public static function state( array $block, string $template, string $locale ): array {

			$texts = [];
			$slots = [];

			foreach( Library::places( $block, (string) $block['id'], $template ) as $place ) {

				if( $place['kind'] === 'image' ) {

					$image = (array) ( $block['images'][$place['name']] ?? [] );
					$labels = is_array( $image['label'] ?? null ) === true ? array_map( 'strval', $image['label'] ) : [];
					$label = $labels !== [] ? (string) ( $labels[$locale] ?? $labels[\Nino\Blocks::FALLBACK] ?? reset( $labels ) ) : (string) ( $image['label'] ?? $place['word'] );
					$slots[$place['key']] = [ 'label' => $label, 'labels' => $labels, 'width' => (int) ( $image['width'] ?? 0 ), 'height' => (int) ( $image['height'] ?? 0 ), 'filename' => null ];
					continue;
				}

				foreach( (array) $block['texts'] as $language => $words )
					if( is_string( $words[$place['name']] ?? null ) === true )
						$texts[$place['key']][(string) $language] = $words[$place['name']];
			}

			ksort( $texts );
			ksort( $slots );

			return [ 'texts' => $texts, 'slots' => $slots ];
		}

		/**
		 *	The draft of a block as it is drawn for the person who works on it: a column with nothing in it holds a quiet place with a sentence - where a component goes - and is
		 *	no empty hole. What is drawn is for the preview alone; the tree of the sheet and what is saved have no such place
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$nodes				A list of nodes of the tree
		 *	@param		string		$hint					The sentence; none leaves the nodes as they are
		 *
		 *	@return 	array
		 */
		public static function hinted( array &$appData, array $nodes, string $hint ): array {

			$hint = trim( mb_substr( (string) preg_replace( '/[\x00-\x1F\x7F<>&"\[\]]+/u', ' ', strip_tags( $hint ) ), 0, 80, 'UTF-8' ) );

			if( $hint === '' )
				return $nodes;

			$place = str_replace( '[[hint]]', htmlspecialchars( $hint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), rtrim( \Nino\Feature::template( $appData, '/features/Blocks/templates', 'place' ) ) );

			return self::_placed( $nodes, $place );
		}

		/**
		 *	The nodes with the place put in each column that has nothing in it
		 *
		 *	@param		array			$nodes
		 *	@param		string		$place				The markup of the place
		 *
		 *	@return 	array
		 */
		private static function _placed( array $nodes, string $place ): array {

			foreach( $nodes as $index => $node ) {

				if( isset( $node['n'] ) === false || is_array( $node['c'] ?? null ) === false )
					continue;

				$node['c'] = self::_placed( $node['c'], $place );

				$pieces = array_filter( $node['c'], static fn( array $inner ): bool => isset( $inner['n'] ) === true || isset( $inner['f'] ) === true || trim( (string) ( $inner['t'] ?? '' ), " \t\r\n" ) !== '' );

				if( $node['n'] === 'section-col' && $pieces === [] )
					$node['c'] = [ [ 't' => "\n" ], [ 't' => $place ], [ 't' => "\n" ] ];

				$nodes[$index] = $node;
			}

			return $nodes;
		}

		/**
		 *	The template a draft of a block is: the two calls of the frame and the block between them, each on a line of its own, as the kernel writes a template
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$tree					The tree of the block, laid out and stripped
		 *
		 *	@return 	string
		 */
		public static function source( array &$appData, array $tree ): string {
			return '['. 'template '. self::FRAMES[0]. "]\n". rtrim( \Nino\Markup::write( $tree, \Nino\Components::containers( $appData ) ) ). "\n[template ". self::FRAMES[1]. "]\n";
		}

		/**
		 *	The sheet of a block, in the form of the answer of an opened page: what the editor of the Builder takes to draw it and work on it
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$block				As Library::block() answers it, or blank()
		 *	@param		array			$shell				As shell() answers it
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		public static function draft( array &$appData, array $block, array $shell, string $locale ): array {

			$registry = \Nino\Components::components( $appData );
			$template = $shell['template'];
			$nodes = Tree::read( $appData, self::source( $appData, $block['tree'] ) );
			$state = self::state( $block, $template, $locale );
			$sources = Tree::sources( $nodes, $registry, $template );

			return [
				'template'		=> $template,
				'frame'				=> false,
				'tree'				=> $nodes,
				'sources'			=> $sources,
				'shared'			=> new \stdClass(),
				'uses'				=> new \stdClass(),
				// No text at all is an empty map, not the empty list a PHP array of nothing is in JSON: the editor adds a key to it
				'texts'				=> $state['texts'] === [] ? new \stdClass() : $state['texts'],
				'known'				=> Tree::known( $nodes, $registry ),
				'slots'				=> array_keys( $state['slots'] ),
				'images'			=> Pictures::shown( $appData, $state['slots'] ),
				'own'					=> self::own( $appData, $block ),
				'address'			=> $shell['address'],
				'last'				=> 0,
				'unback'			=> '',
				'schemas'			=> Tree::schemas( $appData, $nodes, $registry ),
				'findings'		=> Tree::findings( $appData, $nodes ),
				'links'				=> [],
				'variants'		=> [],
				'details'			=> self::details( $appData, (string) ( $block['key'] ?? '' ), $locale, $block ),
				'alternatives'	=> self::alternatives( $appData, (string) ( $block['key'] ?? '' ), $locale ),
				'titles'			=> new \stdClass(),
				'contentLocale'	=> $locale,
				'native'			=> \Nino\Locales::getNativeLocale( $appData ),
				'locales'			=> \Nino\Admin\Admin::textLocales( $appData ),
				'dir'					=> (string) ( $appData['/nino/dir'] ?? '' ),
				'level'				=> \Nino\Modules\Builder::LEVEL_DESIGN,
				'dim'					=> (string) \Nino\Features::setting( $appData, 'builder', 'dim', 'frame' ),
			];
		}

		/**
		 *	The details of a block as the editor shows them: the name and the description in each language, the category, the rank and the places of the pictures, each with the
		 *	words of its label, its kind of placeholder and its size, and whether a call of the block still reads it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					'' for a draft of a block that is not there yet
		 *	@param		string		$locale
		 *	@param		array			$block				As Library::block() answers it
		 *
		 *	@return 	array
		 */
		public static function details( array &$appData, string $key, string $locale, array $block ): array {

			$listed = $key === '' ? null : \Nino\Blocks::get( $appData, $key );

			if( $listed === null )
				return [ 'names' => new \stdClass(), 'descriptions' => new \stdClass(), 'category' => 'content', 'rank' => 0, 'images' => new \stdClass(), 'locales' => array_keys( (array) ( $block['texts'] ?? [] ) ), 'placeholders' => array_keys( \Nino\Images::PLACEHOLDERS ) ];

			$read = [];

			foreach( (array) ( $block['slots'] ?? [] ) as $slot )
				if( ( $slot['kind'] ?? '' ) === 'image' )
					$read[(string) $slot['name']] = true;

			$images = [];

			foreach( (array) ( $listed['images'] ?? [] ) as $name => $image )
				$images[(string) $name] = [ 'placeholder' => (string) $image['placeholder'], 'width' => (int) $image['width'], 'height' => (int) $image['height'], 'label' => (object) array_map( 'strval', (array) $image['label'] ), 'used' => isset( $read[(string) $name] ) ];

			return [
				'names'					=> (object) array_map( 'strval', (array) $listed['name'] ),
				'descriptions'	=> (object) array_map( 'strval', (array) $listed['description'] ),
				'category'			=> (string) $listed['category'],
				'rank'					=> (int) $listed['rank'],
				'images'				=> $images === [] ? new \stdClass() : $images,
				'locales'				=> array_map( 'strval', (array) $listed['locales'] ),
				'placeholders'	=> array_keys( \Nino\Images::PLACEHOLDERS ),
			];
		}

		/**
		 *	The variants of a block for the editor: see Variants::listed()
		 *
		 *	@return 	array
		 */
		public static function alternatives( array &$appData, string $key, string $locale ): array
		{
			$listed = $key === '' ? null : \Nino\Blocks::get( $appData, $key );

			return Variants::listed( $listed ?? [], $listed === null ? [ \Nino\Blocks::FALLBACK ] : array_map( 'strval', (array) $listed['locales'] ), $locale );
		}

		/**
		 *	The index of the block in the top level of a tree that is a sheet - the two calls of the frame, the block and blanks and nothing else - null for any other tree
		 *
		 *	@param		array			$nodes
		 *
		 *	@return 	int|null
		 */
		public static function rootAt( array $nodes ): ?int {

			$at = null;
			$frames = [];

			foreach( $nodes as $index => $node ) {

				if( isset( $node['n'] ) === false ) {

					if( isset( $node['f'] ) === true || trim( (string) ( $node['t'] ?? '.' ), " \t\r\n" ) !== '' )
						return null;

					continue;
				}

				if( $node['n'] === Library::PAGE && $at === null )
					$at = (int) $index;
				else if( $node['n'] === 'template' && in_array( (string) ( $node['s'] ?? '' ), self::FRAMES, true ) === true && ( $node['a'] ?? [] ) === [] && isset( $frames[$node['s']] ) === false )
					$frames[$node['s']] = true;
				else
					return null;
			}

			return $at !== null && count( $frames ) === count( self::FRAMES ) ? $at : null;
		}

		/**
		 *	The parts of a block of any source as the files of a block hold them - what is written again where the block is changed, copied or renamed: block.json with the
		 *	details of each language, the variants and the places of the pictures, block.tpl as it is written, and the words of each language
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	array|null								[ 'json', 'tpl', 'texts' => locale => json, 'pictures' => file => bytes ]; null for a key that is no block with a template
		 */
		public static function partsOf( array &$appData, string $key ): ?array {

			$block = \Nino\Blocks::get( $appData, $key );

			if( $block === null || ( $block['missing'] ?? [] ) !== [] || is_string( $block['source'] ?? null ) === false )
				return null;

			$meta = [ 'name' => $block['name'], 'description' => $block['description'], 'category' => $block['category'], 'rank' => $block['rank'] ];

			if( ( $block['images'] ?? [] ) !== [] )
				$meta['images'] = $block['images'];

			if( ( $block['alternatives'] ?? [] ) !== [] ) {

				$meta['variants'] = [];

				foreach( $block['alternatives'] as $name => $variant )
					$meta['variants'][(string) $name] = [ 'label' => $variant['label'], 'set' => (object) array_map( static fn( array $attributes ): object => (object) $attributes, $variant['set'] ) ];
			}

			$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
			$parts = [ 'json' => json_encode( $meta, $flags ). "\n", 'tpl' => rtrim( $block['source'] ). "\n", 'texts' => [], 'pictures' => [] ];

			// The pictures a block of the project has of its own - one that came in with a package - are part of it: what writes the block anew writes them with it
			if( ( $block['pictures'] ?? [] ) !== [] && (string) ( $block['origin'] ?? '' ) === 'project' ) {

				$dir = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' ). \Nino\Blocks::OWN. '/'. $key. '/images/';

				foreach( (array) $block['pictures'] as $relative ) {

					$file = basename( (string) $relative );

					if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*\.(?:svg|webp|png|jpg)$/D', $file ) === 1 && is_file( $dir. $file ) === true && is_link( $dir. $file ) === false && is_string( $bytes = @file_get_contents( $dir. $file ) ) === true )
						$parts['pictures'][$file] = $bytes;
				}
			}

			foreach( (array) $block['texts'] as $locale => $words )
				$parts['texts'][(string) $locale] = ( (array) $words === [] ? '{}' : json_encode( $words, $flags ) ). "\n";

			return $parts;
		}

		/**
		 *	What tells a block that it was changed since it was opened: the parts of it as the files hold them
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	string										A sha256; '' for a key that is no block
		 */
		public static function stamp( array &$appData, string $key ): string {

			$parts = self::partsOf( $appData, $key );

			if( $parts === null )
				return '';

			// The pictures are bytes: they are told by their hashes, which a json text can hold
			$parts['pictures'] = array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), (array) $parts['pictures'] );

			return hash( 'sha256', (string) json_encode( $parts ) );
		}
	}
}
