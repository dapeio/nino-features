<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// The blocks of the project: a block of a page made into a folder of private/blocks/ - block.json, block.tpl, text/<language>.json - and taken away again.
	// What is written is a block of \Nino\Blocks and nothing else: the parts are put together from the tree of the page, held to the rules of the kernel by
	// \Nino\Blocks::validate() before a file is made, and the folder is made beside its place and moved there, so that a block is never seen half-written and
	// never over another. Only a folder below private/blocks/ is written or taken away, and only a block that the kernel lists as the project's own
	class Custom {

		// Where the blocks of the project are ranked: behind the others of their category
		public const int RANK = 900;

		/**
		 *	The failure of an action, in the shape the API answers
		 *
		 *	@param		int				$status
		 *	@param		string		$code
		 *	@param		string		$field
		 *	@param		array			$params
		 *
		 *	@return 	array
		 */
		private static function _fail( int $status, string $code, string $field = '', array $params = [] ): array {
			return [ 'failure' => [ $status, 'the block is not made: '. $code, 'builder_own_'. $code, $params, $field ] ];
		}

		/**
		 *	A text of a page as a text of a block: without markup - a line break stays one, a paragraph is a blank line - and with no character a block text may not have
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		private static function _plain( string $text ): string {

			$text = (string) preg_replace( [ '#<br\s*/?>#i', '#</(?:p|li)>#i' ], [ "\n", "\n\n" ], $text );
			$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			return trim( (string) preg_replace( "/[\\x00-\\x09\\x0B-\\x1F\\x7F]+/", ' ', (string) preg_replace( "/\n{3,}/", "\n\n", str_replace( "\r", '', $text ) ) ) );
		}

		/**
		 *	The tree of a block as a block has it: the ids with the id of its root in front, each ~name left as it is and noted with the name it has in the files of the
		 *	block, and nothing that a block may not hold - free markup, a call without a schema, html, a source that is not the block's own
		 *
		 *	@param		array			$node
		 *	@param		array			$registry
		 *	@param		array			$map					[ the id the root has, the id it is given ]
		 *	@param		string		$template
		 *	@param		string		$was					The id above the node on the page
		 *	@param		string		$now					The id above it in the block
			 *	@param		array			&$used				(reference) The name in the files => [ kind, the key of the page ]
			 *	@param		bool			$looped				Whether a loop is above the node: a field of its elements is a source there
			 *
			 *	@return 	array|string							The node; or why not - html, locked, foreign, address, id
		 */
		private static function _node( array &$appData, array $node, array $registry, array $map, string $template, string $was, string $now, array &$used, bool $looped = false ): array|string {

			$schema = $registry[$node['n']] ?? null;

			if( $schema === null || $node['n'] === 'html' )
				return $node['n'] === 'html' ? 'html' : 'locked';

			$idName = Tree::idAttribute( $schema );
			$isLoop = ( Tree::schema( $appData, $registry, $node['n'] )['loop'] ?? false ) === true;
			$given = is_array( $node['a'] ?? null ) === true ? $node['a'] : [];

			if( $idName !== '' && is_string( $given[$idName] ?? null ) === true && $given[$idName] !== '' ) {

				$was = $given[$idName];
				$now = $was === $map[0] ? $map[1] : Tree::inner( $was, $map[0], $map[1] );

				if( strlen( $now ) > 32 || Tree::isId( $now ) === false )
					return 'id';

				$given[$idName] = $now;
			}

			// A name in the files: the part of the id behind the id of the root and a hyphen, a hyphen and the name
			$name = static function( string $written, string $kind, bool $link = false ) use ( $was, $now, $map, $template, &$used ): ?string {

				$inner = $now === $map[1] ? '' : substr( $now, strlen( $map[1] ) + 1 );
				$flat = ( $inner === '' ? '' : $inner. '-' ). substr( $written, 1 );
				$kind = $kind === 'image' ? 'image' : 'text';
				$key = '/template/'. $template. '/'. $was. '/'. substr( $written, 1 );

				if( isset( $used[$flat] ) === true && array_slice( $used[$flat], 0, 2 ) !== [ $kind, $key ] )
					return null;

				$used[$flat] = [ $kind, $key, $link === true || ( $used[$flat][2] ?? false ) === true ];

				return $flat;
			};

			$source = (string) ( $schema['source'] ?? 'none' );
			$out = [ 'n' => $node['n'], 's' => '', 'a' => $given, 'c' => null ];

			if( is_string( $node['s'] ?? null ) === true && $node['s'] !== '' && ( $source === 'none' || $source !== 'text' || is_string( $given['text'] ?? null ) === false ) ) {

				$written = $node['s'];

				if( $written[0] === '~' ) {

					if( $source === 'none' || $name( $written, $source ) === null )
						return 'foreign';
				}
				// The type of the elements a loop shows, and a field of the elements below a loop, are sources of the elements and not of the page
				else if( ( $isLoop === true && preg_match( '/^\/[a-z_][a-z0-9_-]{0,63}$/D', $written ) === 1 ) === false && ( $looped === true && preg_match( '/^\.[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $written ) === 1 ) === false
					&& isset( \Nino\Images::PLACEHOLDERS[$written] ) === false && isset( \Nino\Elements::PLACEHOLDERS[$written] ) === false )
					return 'foreign';

				$out['s'] = $written;
			}

			foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared ) {

				$value = $given[$attribute] ?? null;
				$type = (string) ( $declared['type'] ?? '' );

				if( is_string( $value ) === false || $value === '' || in_array( $type, [ 'text', 'image', 'link' ], true ) === false )
					continue;

				if( $value[0] === '~' && preg_match( '/^[^\s\[\]\x00-\x1F\x7F]+$/D', $value ) === 1 ) {

					if( $name( $value, $type, $type === 'link' ) === null )
						return 'foreign';
				}
				// A field of the element a loop above shows is what it reads, in a text, a picture or a link
				else if( $looped === true && preg_match( '/^\.[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $value ) === 1 )
					continue;
				// A link of a block goes to a page of the site, to a place on the page, to a mail or a telephone or to a secure address - and to a target the one rule takes
				else if( $type === 'link' && ( preg_match( '#^(?:page:/|\#|mailto:|tel:|https://)#i', $value ) !== 1 || \Nino\Modules\Builder\Tree::linkTarget( $appData, $value ) === false ) )
					return 'address';
				else if( $type !== 'link' && strpos( './', $value[0] ) !== false && preg_match( '/^[^\s\[\]\x00-\x1F\x7F]+$/D', $value ) === 1 && isset( \Nino\Images::PLACEHOLDERS[$value] ) === false )
					return 'foreign';
			}

			$out['a'] = $given;

			if( is_array( $node['c'] ?? null ) === true ) {

				$out['c'] = [];

				foreach( $node['c'] as $inner ) {

					if( isset( $inner['n'] ) === false ) {

						if( isset( $inner['f'] ) === true || trim( (string) ( $inner['t'] ?? '' ), " \t\r\n" ) !== '' )
							return 'locked';

						$out['c'][] = $inner;
						continue;
					}

					$made = self::_node( $appData, $inner, $registry, $map, $template, $was, $now, $used, $looped === true || $isLoop === true );

					if( is_string( $made ) === true )
						return $made;

					$out['c'][] = $made;
				}
			}

			return $out;
		}

		/**
		 *	The label of a picture slot in each language: what the slot has in a language stays - a copy of a block has its labels as the block has them - and the language it
		 *	has none in takes the label of the one given
		 *
		 *	@param		array			$locales
		 *	@param		string		$label				The label that is given
		 *	@param		array			$has					language => label the slot has
		 *
		 *	@return 	array											language => label, each of at most MAX_NAME characters and none empty
		 */
		private static function _labels( array $locales, string $label, array $has ): array {

			$out = [];

			foreach( $locales as $locale ) {

				$own = is_string( $has[$locale] ?? null ) === true ? trim( (string) preg_replace( '/[<>\x00-\x1F\x7F]|\[\[/u', ' ', $has[$locale] ) ) : '';
				$out[$locale] = mb_substr( $own !== '' ? $own : $label, 0, \Nino\Blocks::MAX_NAME );
			}

			return $out;
		}

		/**
		 *	The parts of a block of the library from the tree of a block: the first of the two steps of saving a block of a page as a block of the project, and the one a panel
		 *	that edits blocks takes by itself - the details of block.json, block.tpl, the texts of each language and the places of the pictures, put together from the root of a
		 *	block that stands in a tree. Nothing is written and nothing is looked up that a file holds: the rules that need the files - a key that is taken, the one rule of the
		 *	kernel - are those of write()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The page
		 *	@param		array			$state				The page as it is saved, Store::state()
		 *	@param		array			$nodes				The tree the person is working on
		 *	@param		array			$input				at (the position of the block on the top level), key, name, description, category, texts (key => language => text), pictures (uri => [ label, width, height ]), lenient (true: a link may name a page the site has not yet, as a block of the library may)
		 *
		 *	@return 	array											[ 'key', 'parts' => [ json, tpl, texts => language => json ] ] - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function parts( array &$appData, string $template, array $state, array $nodes, array $input ): array {

			$registry = \Nino\Components::components( $appData );
			$at = is_string( $input['at'] ?? null ) === true && preg_match( '/^\d+$/D', $input['at'] ) === 1 ? (int) $input['at'] : -1;
			$root = $nodes[$at] ?? null;
			$key = is_string( $input['key'] ?? null ) === true ? $input['key'] : '';
			$name = is_string( $input['name'] ?? null ) === true ? trim( $input['name'] ) : '';
			$description = is_string( $input['description'] ?? null ) === true && trim( $input['description'] ) !== '' ? trim( $input['description'] ) : $name;
			$category = is_string( $input['category'] ?? null ) === true ? $input['category'] : '';

			if( is_array( $root ) === false || Tree::kind( $root, $registry ) !== 'block' || $root['n'] !== Library::PAGE )
				return self::_fail( 404, 'block', 'at' );

			if( $key === '' || strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 )
				return self::_fail( 400, 'key', 'key' );

			if( $name === '' || mb_strlen( $name ) > \Nino\Blocks::MAX_NAME || mb_strlen( $description ) > \Nino\Blocks::MAX_DESCRIPTION || preg_match( '/[<>\x00-\x1F\x7F]|\[\[/u', $name. $description ) === 1 )
				return self::_fail( 400, 'name', 'name' );

			if( in_array( $category, array_diff( \Nino\Blocks::CATEGORIES, [ 'frame' ] ), true ) === false )
				return self::_fail( 400, 'category', 'category' );

			$rootId = (string) ( $root['a'][Tree::idAttribute( $registry[$root['n']] )] ?? '' );
			$base = (string) preg_replace( '/-\d+$/', '', $rootId );

			if( Tree::isId( $rootId ) === false || Tree::isId( $base ) === false )
				return self::_fail( 400, 'id', 'at' );

			// The block is the block it is on the page: with the id of its root without the number a copy has, and every id inside it with that in front
			$used = [];
			$tree = self::_node( $appData, $root, $registry, [ $rootId, $base ], $template, $rootId, $base, $used );

			if( is_string( $tree ) === true )
				return self::_fail( 400, $tree, 'at' );

			// The words, in each language of the site and in English: what the page has for the key in the language, else English, else the native language, else any
			$locales = array_values( array_unique( array_merge( [ \Nino\Blocks::FALLBACK ], \Nino\Locales::getAvailableLocales( $appData ) ) ) );
			$native = \Nino\Locales::getNativeLocale( $appData );
			$texts = array_fill_keys( $locales, [] );
			$images = [];
			$words = is_array( $input['texts'] ?? null ) === true ? $input['texts'] : [];
			$chosen = is_array( $input['pictures'] ?? null ) === true ? $input['pictures'] : [];

			foreach( $used as $flat => [ $kind, $from, $link ] ) {

				if( $kind === 'image' ) {

					$slot = $state['slots'][$from] ?? ( is_array( $chosen[$from] ?? null ) === true ? $chosen[$from] : null );
					$label = is_array( $slot ) === true && is_string( $slot['label'] ?? null ) === true ? trim( (string) preg_replace( '/[<>\x00-\x1F\x7F]|\[\[/u', ' ', $slot['label'] ) ) : '';
					$width = is_array( $slot ) === true ? ( $slot['width'] ?? null ) : null;
					$height = is_array( $slot ) === true ? ( $slot['height'] ?? null ) : null;

					if( is_int( $width ) === false || is_int( $height ) === false )
						return self::_fail( 400, 'picture', 'at' );

					$width = max( 16, min( 4096, $width ) );
					$height = max( 16, min( 4096, $height ) );
					$images[$flat] = [ 'placeholder' => $width >= $height * 1.25 ? '/nino/placeholder/landscape' : '/nino/placeholder/mark', 'width' => $width, 'height' => $height, 'label' => self::_labels( $locales, $label === '' ? $flat : $label, is_array( $slot['labels'] ?? null ) === true ? $slot['labels'] : [] ) ];
					continue;
				}

				$have = [];

				foreach( array_merge( (array) ( $state['texts'][$from] ?? [] ), is_array( $words[$from] ?? null ) === true ? $words[$from] : [] ) as $locale => $text )
					if( is_string( $text ) === true && trim( $text ) !== '' )
						$have[(string) $locale] = $text;

				// What the draft says counts over what is saved
				foreach( is_array( $words[$from] ?? null ) === true ? $words[$from] : [] as $locale => $text )
					if( is_string( $text ) === true )
						$have[(string) $locale] = $text;

				foreach( $locales as $locale ) {

					$text = $have[$locale] ?? $have[\Nino\Blocks::FALLBACK] ?? $have[$native] ?? ( $have === [] ? '' : reset( $have ) );
					$text = self::_plain( (string) $text );

					if( $text === '' )
						return self::_fail( 400, 'empty', 'at' );

					// What a link of the block reads is a target, for each language, by the rule of every other link
					if( $link === true && ( preg_match( '#^(?:page:/|\#|mailto:|tel:|https://)#i', $text ) !== 1 || \Nino\Modules\Builder\Tree::linkTarget( $appData, $text, ( $input['lenient'] ?? false ) !== true ) === false ) )
						return self::_fail( 400, 'address', 'at' );

					if( mb_strlen( $text ) > \Nino\Blocks::MAX_TEXT )
						return self::_fail( 400, 'long', 'at', [ \Nino\Blocks::MAX_TEXT ] );

					$texts[$locale][$flat] = $text;
				}
			}

			$meta = [ 'name' => array_fill_keys( $locales, $name ), 'description' => array_fill_keys( $locales, $description ), 'category' => $category, 'rank' => self::RANK ];

			if( $images !== [] )
				$meta['images'] = $images;

			$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
			$parts = [
				'json'	=> json_encode( $meta, $flags ). "\n",
				'tpl'		=> \Nino\Markup::write( [ $tree ], \Nino\Components::containers( $appData ) ). "\n",
				'texts'	=> [],
			];

			foreach( $texts as $locale => $list )
				$parts['texts'][$locale] = ( $list === [] ? '{}' : json_encode( $list, $flags ) ). "\n";

			return [ 'key' => $key, 'parts' => $parts ];
		}

		/**
		 *	The second of the two steps: parts of a block are held to the one rule of the kernel and written as a block of the project. A key that is taken - by any block the kernel
		 *	lists, or one it refused - is no key for a new block, and what \Nino\Blocks::validate() refuses is not written; the folder is made beside its place and moved there, so that
		 *	a block is never seen half-written and never over another
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *	@param		array			$parts				As parts() answers them
		 *
		 *	@return 	array											[ 'key' ] - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function write( array &$appData, string $key, array $parts ): array {

			if( $key === '' || strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 )
				return self::_fail( 400, 'key', 'key' );

			$taken = \Nino\Blocks::all( $appData );
			$rejected = array_filter( array_keys( \Nino\Blocks::rejected( $appData ) ), static fn( string $where ): bool => str_ends_with( $where, '/'. $key ) === true );

			if( isset( $taken[$key] ) === true || $rejected !== [] )
				return self::_fail( 409, 'taken', 'key' );

			$content = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' );
			$folder = $content. \Nino\Blocks::OWN;

			if( $content === '' || is_link( $folder ) === true || file_exists( $folder. '/'. $key ) === true || is_link( $folder. '/'. $key ) === true )
				return self::_fail( $content === '' ? 500 : 409, $content === '' ? 'write' : 'taken', $content === '' ? '' : 'key' );

			// The one rule the kernel holds a block to, asked before a file is made
			if( is_string( \Nino\Blocks::validate( $appData, $key, $parts ) ) === true )
				return self::_fail( 400, 'invalid', 'at' );

			self::sweep( $folder );

			$temp = $folder. '/.new-'. bin2hex( random_bytes( 6 ) );
			$made = ( is_dir( $folder ) === true || @mkdir( $folder, 0755, true ) === true || is_dir( $folder ) === true ) && @mkdir( $temp. '/text', 0755, true ) === true
				&& file_put_contents( $temp. '/block.json', $parts['json'] ) !== false && file_put_contents( $temp. '/block.tpl', $parts['tpl'] ) !== false;

			foreach( $parts['texts'] as $locale => $json )
				$made = $made === true && file_put_contents( $temp. '/text/'. $locale. '.json', $json ) !== false;

			// Moved into its place only where nobody has made one there meanwhile: a folder that is there and holds something is not replaced
			if( $made === true && file_exists( $folder. '/'. $key ) === false && @rename( $temp, $folder. '/'. $key ) === true )
				return [ 'key' => $key ];

			self::_remove( $temp );

			return self::_fail( 500, 'write' );
		}


		/**
		 *	Write parts over a block of the project that is there: the key stays, the files are the new ones. What \Nino\Blocks::validate() refuses is not written; the new folder is
		 *	made beside the old one and swapped for it - the old one is moved aside first and put back where the swap fails, so that a block is never seen half-written or gone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *	@param		array			$parts				As parts() answers them
		 *
		 *	@return 	array											[ 'key' ] - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function replace( array &$appData, string $key, array $parts ): array {

			if( $key === '' || strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 )
				return self::_fail( 400, 'key', 'key' );

			if( ( \Nino\Blocks::all( $appData )[$key]['origin'] ?? '' ) !== 'project' )
				return self::_fail( 404, 'block', 'key' );

			$content = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' );
			$folder = $content. \Nino\Blocks::OWN;
			$real = realpath( $folder );
			$dir = $real === false ? '' : $real. '/'. $key;

			if( $content === '' || $real === false || is_link( $folder ) === true || is_dir( $dir ) === false || realpath( $dir ) !== $dir )
				return self::_fail( 500, 'write' );

			if( is_string( \Nino\Blocks::validate( $appData, $key, $parts ) ) === true )
				return self::_fail( 400, 'invalid', 'at' );

			self::sweep( $folder );

			$temp = $folder. '/.new-'. bin2hex( random_bytes( 6 ) );
			$made = @mkdir( $temp. '/text', 0755, true ) === true && file_put_contents( $temp. '/block.json', (string) $parts['json'] ) !== false && file_put_contents( $temp. '/block.tpl', (string) $parts['tpl'] ) !== false;

			foreach( (array) $parts['texts'] as $locale => $json )
				$made = $made === true && file_put_contents( $temp. '/text/'. $locale. '.json', (string) $json ) !== false;

			$aside = $folder. '/.old-'. bin2hex( random_bytes( 6 ) );

			if( $made === true && @rename( $dir, $aside ) === true ) {

				if( @rename( $temp, $dir ) === true ) {
					self::_remove( $aside );
					return [ 'key' => $key ];
				}

				@rename( $aside, $dir );
			}

			self::_remove( $temp );

			return self::_fail( 500, 'write' );
		}

		/**
		 *	Save a block of the page as a block of the project: its parts, and then the writing of them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The page
		 *	@param		array			$state				The page as it is saved, Store::state()
		 *	@param		array			$nodes				The tree the person is working on
		 *	@param		array			$input				As parts() takes them
		 *
		 *	@return 	array											[ 'key' ] - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function save( array &$appData, string $template, array $state, array $nodes, array $input ): array {

			$made = self::parts( $appData, $template, $state, $nodes, $input );

			return isset( $made['failure'] ) === true ? $made : self::write( $appData, $made['key'], $made['parts'] );
		}

		/**
		 *	What a write that was cut short leaves behind: the folders .new-* and .old-* beside the blocks of the project, which are no block and are never read as one. They are
		 *	taken away at the next write - those a day old, so that the folder another request is writing at this very moment is never touched
		 *
		 *	@param		string		$folder				The folder of the blocks of the project
		 *
		 *	@return 	void
		 */
		public static function sweep( string $folder ): void {

			if( is_dir( $folder ) === false || is_link( $folder ) === true )
				return;

			foreach( (array) scandir( $folder ) as $entry )
				if( preg_match( '/^\.(?:new|old)-[0-9a-f]{12}$/D', $entry ) === 1 && is_link( $folder. '/'. $entry ) === false && is_dir( $folder. '/'. $entry ) === true && (int) filemtime( $folder. '/'. $entry ) < time() - 86400 )
					self::_remove( $folder. '/'. $entry );
		}

		/**
		 *	Take away a folder of the files of a block: its files, text/ and what it holds, and the folder - nothing of a link, nothing deeper
		 *
		 *	@param		string		$dir
		 *
		 *	@return 	bool											Whether it was all taken away
		 */
		private static function _remove( string $dir ): bool {

			if( is_dir( $dir ) === false || is_link( $dir ) === true )
				return false;

			foreach( (array) scandir( $dir ) as $entry ) {

				if( $entry === '.' || $entry === '..' )
					continue;

				$path = $dir. '/'. $entry;

				if( is_link( $path ) === true || ( is_dir( $path ) === true && $entry !== 'text' ) )
					return false;

				if( is_dir( $path ) === true )
					self::_remove( $path );
				else
					@unlink( $path );
			}

			return @rmdir( $dir );
		}

		/**
		 *	Take a block of the project away. A block of the project is a block that \Nino\Blocks lists with the origin 'project': any other - one of Basis, of
		 *	a feature or a theme - is no folder of this to touch. A page that has the block in it keeps its own copy
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	array|null								null where it was taken away, else 'failure' => [ status, message, code, params, field ]
		 */
		public static function drop( array &$appData, string $key ): ?array {

			if( strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 || ( \Nino\Blocks::all( $appData )[$key]['origin'] ?? '' ) !== 'project' )
				return [ 'failure' => [ 404, 'there is no such block of the project', 'builder_block', [], 'key' ] ];

			$folder = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' ). \Nino\Blocks::OWN;
			$real = realpath( $folder );
			$dir = $real === false ? '' : $real. '/'. $key;

			if( $real === false || is_link( $folder ) === true || is_dir( $dir ) === false || realpath( $dir ) !== $dir || self::_remove( $dir ) === false )
				return self::_fail( 500, 'write' );

			return null;
		}
	}
}
