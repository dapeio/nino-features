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

		/** The part of a template that the last refusal of _node() is about: the name of its call and the id it has, for the words that name it */
		private static string $part = '';

		/** What a name or a description of a block may not hold: markup, control and format characters - the ones that turn a line round or hide in it - and the beginning of a textfill */
		private const string UNSAFE = '/[<>\x00-\x1F\x7F\p{Cf}\p{Zl}\p{Zp}]|\[\[/u';

		/** Whether the block that is made is the block of a frame, and what is refused of it so far: [ code, part, extra ] each - a frame says everything at once */
		private static bool $frame = false;
		private static array $problems = [];

		/**
		 *	A refusal of _node(): the code, and the part it is about noted for the one who asks
		 *
		 *	@param		string		$code
		 *	@param		array			$node
		 *
		 *	@return 	string
		 */
		private static function _bad( string $code, array $node ): string {

			$id = is_array( $node['a'] ?? null ) === true && is_string( $node['a']['id'] ?? null ) === true && $node['a']['id'] !== '' ? ' id="'. $node['a']['id']. '"' : '';
			self::$part = is_string( $node['n'] ?? null ) === true ? '['. $node['n']. $id. ']' : '';

			if( self::$frame === true )
				self::$problems[] = [ 'code' => $code, 'part' => self::$part, 'extra' => [] ];

			return $code;
		}

		/**
		 *	A call as a person reads it in a refusal: its name, the id it has and - where one is written - the source it reads
		 */
		private static function _label( array $node, bool $source = false ): string {

			$id = is_array( $node['a'] ?? null ) === true && is_string( $node['a']['id'] ?? null ) === true && $node['a']['id'] !== '' ? ' id="'. $node['a']['id']. '"' : '';
			$read = $source === true && is_string( $node['s'] ?? null ) === true && $node['s'] !== '' ? ' '. $node['s'] : '';

			return '['. (string) ( $node['n'] ?? '' ). $id. $read. ']';
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
				return self::_bad( $node['n'] === 'html' ? 'html' : 'locked', $node );

			$idName = Tree::idAttribute( $schema );
			$isLoop = ( Tree::schema( $appData, $registry, $node['n'] )['loop'] ?? false ) === true;
			$given = is_array( $node['a'] ?? null ) === true ? $node['a'] : [];

			if( $idName !== '' && is_string( $given[$idName] ?? null ) === true && $given[$idName] !== '' ) {

				$was = $given[$idName];
				$now = $was === $map[0] ? $map[1] : Tree::inner( $was, $map[0], $map[1] );

				if( strlen( $now ) > 32 || Tree::isId( $now ) === false )
					return self::_bad( 'id', $node );

				$given[$idName] = $now;
			}

			// A name in the files: the part of the id behind the id of the root and a hyphen, a hyphen and the name
			$name = static function( string $written, string $kind, bool $link = false ) use ( $was, $now, $map, $template, &$used, $node ): ?string {

				$inner = $now === $map[1] ? '' : substr( $now, strlen( $map[1] ) + 1 );
				$flat = ( $inner === '' ? '' : $inner. '-' ). substr( $written, 1 );
				$kind = $kind === 'image' ? 'image' : 'text';
				$key = '/template/'. $template. '/'. $was. '/'. substr( $written, 1 );

				if( isset( $used[$flat] ) === true && array_slice( $used[$flat], 0, 2 ) !== [ $kind, $key ] )
					return null;

				$used[$flat] = [ $kind, $key, $link === true || ( $used[$flat][2] ?? false ) === true, $used[$flat][3] ?? self::_label( $node ) ];

				return $flat;
			};

			$source = (string) ( $schema['source'] ?? 'none' );
			$out = [ 'n' => $node['n'], 's' => '', 'a' => $given, 'c' => null ];

			if( is_string( $node['s'] ?? null ) === true && $node['s'] !== '' && ( $source === 'none' || $source !== 'text' || is_string( $given['text'] ?? null ) === false ) ) {

				$written = $node['s'];

				if( $written[0] === '~' ) {

					if( $source === 'none' || $name( $written, $source ) === null )
						return self::_bad( 'foreign', $node );
				}
				// A loop of a block of the library shows the placeholder elements: the type is chosen where the block stands on a page
				else if( $isLoop === true && preg_match( '/^\/[a-z_][a-z0-9_-]{0,63}$/D', $written ) === 1 && isset( \Nino\Elements::PLACEHOLDERS[$written] ) === false )
					return self::_bad( 'loop', $node );
				// The type of the elements a loop shows, and a field of the elements below a loop, are sources of the elements and not of the page
				else if( ( $isLoop === true && preg_match( '/^\/[a-z_][a-z0-9_-]{0,63}$/D', $written ) === 1 ) === false && ( $looped === true && preg_match( '/^\.[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $written ) === 1 ) === false
					&& isset( \Nino\Images::PLACEHOLDERS[$written] ) === false && isset( \Nino\Elements::PLACEHOLDERS[$written] ) === false && self::$frame === false )
					return self::_bad( 'foreign', $node );

				// A frame is let carry what it reads of the project: whether a block may is the kernel's to say, and it is asked of the kernel when the block stands
				$out['s'] = $written;
			}

			foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared ) {

				$value = $given[$attribute] ?? null;
				$type = (string) ( $declared['type'] ?? '' );

				if( is_string( $value ) === false || $value === '' || in_array( $type, [ 'text', 'image', 'link' ], true ) === false )
					continue;

				// A picture that a frame names and that is the picture the call has by itself is written as the call has it: nothing - the block draws the same one
				if( self::$frame === true && $type === 'image' && $value === ( $declared['default'] ?? null ) ) {
					unset( $given[$attribute] );
					continue;
				}

				if( $value[0] === '~' && preg_match( '/^[^\s\[\]\x00-\x1F\x7F]+$/D', $value ) === 1 ) {

					if( $name( $value, $type, $type === 'link' ) === null )
						return self::_bad( 'foreign', $node );
				}
				// A field of the element a loop above shows is what it reads, in a text, a picture or a link
				else if( $looped === true && preg_match( '/^\.[A-Za-z][A-Za-z0-9_-]{0,63}$/D', $value ) === 1 )
					continue;
				// A link of a block goes to a page of the site, to a place on the page, to a mail or a telephone or to a secure address - and to a target the one rule takes
				else if( $type === 'link' && ( preg_match( '#^(?:page:/|\#|mailto:|tel:|https://)#i', $value ) !== 1 || \Nino\Modules\Builder\Tree::linkTarget( $appData, $value ) === false ) )
					return self::_bad( 'address', $node );
				else if( $type !== 'link' && strpos( './', $value[0] ) !== false && preg_match( '/^[^\s\[\]\x00-\x1F\x7F]+$/D', $value ) === 1 && isset( \Nino\Images::PLACEHOLDERS[$value] ) === false && self::$frame === false )
					return self::_bad( 'foreign', $node );
			}

			$out['a'] = $given;

			if( is_array( $node['c'] ?? null ) === true ) {

				$out['c'] = [];

				foreach( $node['c'] as $inner ) {

					if( isset( $inner['n'] ) === false ) {

						if( isset( $inner['f'] ) === true || trim( (string) ( $inner['t'] ?? '' ), " \t\r\n" ) !== '' ) {

							self::_bad( 'locked', $node );

							if( self::$frame === true )
								continue;

							return 'locked';
						}

						$out['c'][] = $inner;
						continue;
					}

					$made = self::_node( $appData, $inner, $registry, $map, $template, $was, $now, $used, $looped === true || $isLoop === true );

					if( is_string( $made ) === true ) {

						// A frame goes on to the next part: what is refused of it is said at once
						if( self::$frame === true )
							continue;

						return $made;
					}

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
		public static function parts( array &$appData, string $template, array $state, array $nodes, array $input, ?string $wrap = null ): array {

			// The root is the one the template of a frame has - or the one the caller names, for a block of a frame that is worked on in a page that is no frame
			$wrap ??= Library::wrapOf( $template );
			self::$frame = $wrap !== Library::PAGE;
			self::$problems = [];

			try {
				$made = self::_parts( $appData, $template, $state, $nodes, $input, $wrap );
			}
			finally {
				self::$frame = false;
				self::$problems = [];
			}

			// A frame says what it was refused for in the words of a frame
			if( isset( $made['failure'] ) === true && $wrap !== Library::PAGE )
				$made['failure'][2] = str_replace( 'builder_own_', 'builder_frame_', (string) $made['failure'][2] );

			return $made;
		}

		/**
		 *	parts() for the root of a page or of a frame: the one place where a block of the library and a page or a frame are mapped to one another
		 */
		private static function _parts( array &$appData, string $template, array $state, array $nodes, array $input, string $wrap ): array {

			$registry = \Nino\Components::components( $appData );
			$at = is_string( $input['at'] ?? null ) === true && preg_match( '/^\d+$/D', $input['at'] ) === 1 ? (int) $input['at'] : -1;
			$root = $nodes[$at] ?? null;
			$key = is_string( $input['key'] ?? null ) === true ? $input['key'] : '';
			$name = is_string( $input['name'] ?? null ) === true ? trim( $input['name'] ) : '';
			$description = is_string( $input['description'] ?? null ) === true && trim( $input['description'] ) !== '' ? trim( $input['description'] ) : $name;
			$category = is_string( $input['category'] ?? null ) === true ? $input['category'] : '';

			if( is_array( $root ) === false || Tree::kind( $root, $registry ) !== 'block' || $root['n'] !== $wrap )
				return self::_fail( 404, 'block', 'at' );

			if( $key === '' || strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 )
				return self::_fail( 400, 'key', 'key' );

			if( $name === '' || mb_strlen( $name ) > \Nino\Blocks::MAX_NAME || mb_strlen( $description ) > \Nino\Blocks::MAX_DESCRIPTION || preg_match( self::UNSAFE, $name. $description ) === 1 )
				return self::_fail( 400, 'name', 'name' );

			// A frame is a block of the category of the frames and nothing else; a block of a page is any other
			if( in_array( $category, $wrap === Library::PAGE ? array_diff( \Nino\Blocks::CATEGORIES, [ 'frame' ] ) : [ 'frame' ], true ) === false )
				return self::_fail( 400, 'category', 'category' );

			$rootId = (string) ( $root['a'][Tree::idAttribute( $registry[$root['n']] )] ?? '' );
			// A frame stands on the id it has - there is no copy number to take off of it - so that what the site says of the id is said of the block
			$base = $wrap === Library::PAGE ? (string) preg_replace( '/-\d+$/', '', $rootId ) : $rootId;

			if( Tree::isId( $rootId ) === false || Tree::isId( $base ) === false )
				return self::_fail( 400, 'id', 'at' );

			// The block is the block it is on the page: with the id of its root without the number a copy has, and every id inside it with that in front
			$used = [];
			$tree = self::_node( $appData, $root, $registry, [ $rootId, $base ], $template, $rootId, $base, $used );

			if( is_string( $tree ) === true )
				return self::$frame === true ? self::_frameFailure( $appData, $input ) : self::_fail( 400, $tree, 'at' );

			// The words, in each language of the site and in English: what the page has for the key in the language, else English, else the native language, else any
			$locales = array_values( array_unique( array_merge( [ \Nino\Blocks::FALLBACK ], \Nino\Locales::getAvailableLocales( $appData ) ) ) );
			$native = \Nino\Locales::getNativeLocale( $appData );
			$texts = array_fill_keys( $locales, [] );
			$images = [];
			$words = is_array( $input['texts'] ?? null ) === true ? $input['texts'] : [];
			$chosen = is_array( $input['pictures'] ?? null ) === true ? $input['pictures'] : [];
			$gaps = [];
			$spare = [];
			$filled = [];

			foreach( $used as $flat => [ $kind, $from, $link ] ) {

				if( $kind === 'image' ) {

					$slot = $state['slots'][$from] ?? ( is_array( $chosen[$from] ?? null ) === true ? $chosen[$from] : null );
					$label = is_array( $slot ) === true && is_string( $slot['label'] ?? null ) === true ? trim( (string) preg_replace( '/[<>\x00-\x1F\x7F]|\[\[/u', ' ', $slot['label'] ) ) : '';
					$width = is_array( $slot ) === true ? ( $slot['width'] ?? null ) : null;
					$height = is_array( $slot ) === true ? ( $slot['height'] ?? null ) : null;

					if( is_int( $width ) === false || is_int( $height ) === false ) {

						if( self::$frame === false )
							return self::_fail( 400, 'picture', 'at' );

						self::_note( 'picture', $used[$flat][3] ?? '' );
						continue;
					}

					$width = max( 16, min( 4096, $width ) );
					$height = max( 16, min( 4096, $height ) );
					$images[$flat] = [ 'placeholder' => is_string( $slot['placeholder'] ?? null ) === true && isset( \Nino\Images::PLACEHOLDERS[$slot['placeholder']] ) === true ? $slot['placeholder'] : ( $width >= $height * 1.25 ? '/nino/placeholder/landscape' : '/nino/placeholder/mark' ), 'width' => $width, 'height' => $height, 'label' => self::_labels( $locales, $label === '' ? $flat : $label, is_array( $slot['labels'] ?? null ) === true ? $slot['labels'] : [] ) ];
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

				// A language that was emptied has no text: in a frame it is a language that lacks one - it is not the text of an empty word - and is said so, or filled and said
				if( self::$frame === true )
					$have = array_filter( $have, static fn( string $text ): bool => trim( $text ) !== '' );

				$part = (string) ( $used[$flat][3] ?? '' );

				// A frame has no text made up for a language that has none: a text that is in no language is refused, a language that lacks one is said - and filled from the language
				// that is named, when it is named - and the language the site does not use but the kernel asks for is the only one that takes the text of the native language by itself
				if( self::$frame === true && $have === [] ) {

					self::_note( 'empty', $part );
					continue;
				}

				foreach( $locales as $locale ) {

					$given = $have[$locale] ?? null;

					if( self::$frame === true && $given === null ) {

						$donor = $locale === \Nino\Blocks::FALLBACK && in_array( $locale, \Nino\Locales::getTextLocales( $appData ), true ) === false ? ( $have[$native] ?? reset( $have ) ) : ( $have[(string) ( $input['fill'] ?? '' )] ?? null );

						if( $donor === null ) {

							$gaps[$locale][$part] = $part;
							$spare[$locale][$flat] = self::_plain( (string) ( $have[$native] ?? reset( $have ) ) );
							continue;
						}

						// A language of the site that was given the text of another is said: it is no text anyone wrote in that language
						if( in_array( $locale, \Nino\Locales::getTextLocales( $appData ), true ) === true )
							$filled[$locale][$part] = $part;

						$given = $donor;
					}

					$text = self::_plain( (string) ( $given ?? $have[\Nino\Blocks::FALLBACK] ?? $have[$native] ?? ( $have === [] ? '' : reset( $have ) ) ) );

					if( $text === '' ) {

						if( self::$frame === false )
							return self::_fail( 400, 'empty', 'at' );

						self::_note( 'empty', $part );
						continue;
					}

					// What a link of the block reads is a target, for each language, by the rule of every other link
					if( $link === true && ( preg_match( '#^(?:page:/|\#|mailto:|tel:|https://)#i', $text ) !== 1 || \Nino\Modules\Builder\Tree::linkTarget( $appData, $text, ( $input['lenient'] ?? false ) !== true ) === false ) ) {

						if( self::$frame === false )
							return self::_fail( 400, 'address', 'at' );

						self::_note( 'address', $part );
						continue;
					}

					if( mb_strlen( $text ) > \Nino\Blocks::MAX_TEXT ) {

						if( self::$frame === false )
							return self::_fail( 400, 'long', 'at', [ \Nino\Blocks::MAX_TEXT ] );

						self::_note( 'long', $part, [ (string) \Nino\Blocks::MAX_TEXT ] );
						continue;
					}

					$texts[$locale][$flat] = $text;
				}
			}

			// A name and a description for each language the input gives one for; the others have the one that was given
			$names = array_fill_keys( $locales, $name );
			$about = array_fill_keys( $locales, $description );

			foreach( [ 'names' => &$names, 'descriptions' => &$about ] as $field => &$target )
				foreach( is_array( $input[$field] ?? null ) === true ? $input[$field] : [] as $locale => $line ) {

					if( isset( $target[$locale] ) === false || is_string( $line ) === false || trim( $line ) === '' )
						continue;

					if( mb_strlen( trim( $line ) ) > ( $field === 'names' ? \Nino\Blocks::MAX_NAME : \Nino\Blocks::MAX_DESCRIPTION ) || preg_match( self::UNSAFE, $line ) === 1 )
						return self::_fail( 400, 'name', 'name' );

					$target[$locale] = trim( $line );
				}

			unset( $target );

			$meta = [ 'name' => $names, 'description' => $about, 'category' => $category, 'rank' => self::RANK ];

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

			if( self::$frame === true ) {

				// What the kernel takes as a block of a frame is the kernel's to say: it is asked of the block that stands, and what it refuses - a part after a part - is said at once
				if( self::$problems === [] ) {

					// A language that lacks a text is asked about with the text of another, for the question only: the kernel asks for the same names in each language
					$probe = $parts;

					foreach( $spare as $locale => $list )
						$probe['texts'][$locale] = (string) json_encode( $list + (array) json_decode( (string) ( $parts['texts'][$locale] ?? '{}' ), true ) );

					self::_asked( $appData, $key, $probe, $tree, $used );
				}

				if( self::$problems !== [] )
					return self::_frameFailure( $appData, $input );

				if( $gaps !== [] && ( $input['check'] ?? false ) !== true )
					return self::_frameFailure( $appData, $input, $gaps );

				if( ( $input['check'] ?? false ) === true )
					return [ 'key' => $key, 'parts' => $parts, 'gaps' => self::_gapList( $gaps ) ];
			}

			return [ 'key' => $key, 'parts' => $parts ] + ( $filled === [] ? [] : [ 'filled' => self::_gapList( $filled ) ] );
		}

		/**
		 *	What is refused of a frame, noted once for a part
		 */
		private static function _note( string $code, string $part, array $extra = [] ): void {

			foreach( self::$problems as $one )
				if( $one['code'] === $code && $one['part'] === $part )
					return;

			self::$problems[] = [ 'code' => $code, 'part' => $part, 'extra' => $extra ];
		}

		/**
		 *	The languages that lack a text, as a list: language => the parts, for the answer that tells the dialog
		 */
		private static function _gapList( array $gaps ): array {

			$out = [];

			foreach( $gaps as $locale => $parts )
				$out[] = [ 'locale' => (string) $locale, 'parts' => array_values( $parts ) ];

			return $out;
		}

		/**
		 *	The block that stands put to the kernel, again and again: what it refuses of a source is noted with the part and the call that is the part is left out of the next question,
		 *	so that every part that the kernel refuses is named and none is guessed at. A refusal that is no source is noted as it is
		 */
		private static function _asked( array &$appData, string $key, array $parts, array $tree, array $used ): void {

			$containers = \Nino\Components::containers( $appData );

			for( $round = 0; $round < 24; $round++ ) {

				$parts['tpl'] = \Nino\Markup::write( [ $tree ], $containers ). "\n";
				$why = \Nino\Blocks::validate( $appData, $key, $parts );

				if( is_string( $why ) === false )
					return;

				if( preg_match( '/a source of "([^"]+)" - ([^ ]+) - is neither/', $why, $found ) === 1 ) {

					$hit = self::_take( $tree, $found[1], static fn( array $node ): bool => ( $node['s'] ?? '' ) === $found[2], 'source' );

					if( $hit !== null ) {
						self::_note( str_starts_with( $found[2], '/project/' ) === true ? 'source-project' : 'source-other', self::_label( $hit, false ), [ $found[2] ] );
						continue;
					}
				}
				else if( preg_match( '/the value of "([^"]+)" of "([^"]+)" begins like a source/', $why, $found ) === 1 ) {

					$value = '';
					$hit = self::_take( $tree, $found[2], static function( array $node ) use ( $found, &$value ): bool {

						$have = $node['a'][$found[1]] ?? null;
						$value = is_string( $have ) === true ? $have : '';

						return $value !== '' && strpos( './', $value[0] ) !== false;
					}, $found[1] );

					if( $hit !== null ) {
						self::_note( 'source-other', self::_label( $hit, false ), [ $value ] );
						continue;
					}
				}
				else if( preg_match( '#^text/([A-Za-z_]+)\.json, "([^"]+)":#', $why, $found ) === 1 ) {

					self::_note( 'text', (string) ( $used[$found[2]][3] ?? '' ), [ $found[1] ] );
					return;
				}

				self::_note( 'kernel', '', [ $why ] );

				return;
			}
		}

		/**
		 *	The first call of a name in a tree for which a test holds, with its source - or the attribute that is named - taken out of it
		 *
		 *	@return 	array|null								The call as it was; null where there is none
		 */
		private static function _take( array &$node, string $name, callable $test, string $what ): ?array {

			if( ( $node['n'] ?? '' ) === $name && $test( $node ) === true ) {

				$was = $node;

				if( $what === 'source' )
					$node['s'] = '';
				else
					unset( $node['a'][$what] );

				return $was;
			}

			foreach( $node['c'] ?? [] as $at => $inner )
				if( isset( $inner['n'] ) === true && ( $hit = self::_take( $node['c'][$at], $name, $test, $what ) ) !== null )
					return $hit;

			return null;
		}

		/**
		 *	The words of a refusal of a frame: what is wrong, and the part it is wrong at, in the language of the request - one line for each part
		 */
		private static function _line( string $locale, string $code, string $part, array $extra ): string {

			static $words = [];

			$file = dirname( __DIR__ ). '/text/'. ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/D', $locale ) === 1 && is_file( dirname( __DIR__ ). '/text/'. $locale. '.php' ) === true ? $locale : 'en_US' ). '.php';
			$words[$file] ??= (array) require $file;
			$sentence = (string) ( $words[$file]['[[/_admin/error/builder_frame_part_'. str_replace( '-', '_', $code ). ']]'] ?? $code );

			foreach( $extra as $one ) {

				$at = strpos( $sentence, '%s' );

				if( $at !== false )
					$sentence = substr_replace( $sentence, (string) $one, $at, 2 );
			}

			return ( $part === '' ? '' : $part. ': ' ). $sentence;
		}

		/**
		 *	The refusal of a frame: every part that is refused at once - the code of each and the part in the sentence of the answer, which is in the words of the panel - or, for a language
		 *	that lacks a text, the languages and the parts
		 */
		private static function _frameFailure( array &$appData, array $input, array $gaps = [] ): array {

			$locale = is_string( $input['locale'] ?? null ) === true ? $input['locale'] : 'en_US';

			if( $gaps !== [] ) {

				$lines = [];

				foreach( self::_gapList( $gaps ) as $gap )
					$lines[] = $gap['locale']. ': '. implode( ', ', $gap['parts'] );

				return [ 'failure' => [ 409, 'the block is not made: gaps '. implode( '; ', $lines ), 'builder_frame_gaps', [ implode( '; ', $lines ) ], 'at' ] ];
			}

			$codes = [];
			$lines = [];

			foreach( self::$problems as $one ) {

				$codes[] = $one['code']. ( $one['part'] === '' ? '' : ' '. $one['part'] );
				$lines[] = self::_line( $locale, $one['code'], $one['part'], $one['extra'] );
			}

			return [ 'failure' => [ 400, 'the block is not made: '. implode( '; ', $codes ), 'builder_frame_parts', [ implode( '; ', $lines ) ], 'at' ] ];
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

			$made = $made === true && self::_pictures( $temp, (array) ( $parts['pictures'] ?? [] ) );

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

			$made = $made === true && self::_pictures( $temp, (array) ( $parts['pictures'] ?? [] ) );

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
		 *	The pictures a block has of its own, written into the folder that is made beside its place: images/<slot>.<type>, the names \Nino\Blocks::validate() has read them under; a svg as the picture that \Nino\Images::cleanSvg() rebuilds of it. The
		 *	parts of the editor bring none; a package that is imported does
		 *
		 *	@param		string		$temp					The folder
		 *	@param		array			$pictures			slot.type => bytes
		 *
		 *	@return 	bool											Whether all of them were written
		 */
		private static function _pictures( string $temp, array $pictures ): bool {

			if( $pictures === [] )
				return true;

			if( @mkdir( $temp. '/images', 0755, true ) === false && is_dir( $temp. '/images' ) === false )
				return false;

			foreach( $pictures as $file => $bytes ) {

				if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*\.(?:svg|webp|png|jpg)$/D', (string) $file ) !== 1 || is_string( $bytes ) === false )
					return false;

				// A svg is written as the picture cleanSvg() rebuilds, never as it came
				if( str_ends_with( (string) $file, '.svg' ) === true && ( $bytes = \Nino\Images::cleanSvg( $bytes ) ) === null )
					return false;

				if( file_put_contents( $temp. '/images/'. $file, $bytes ) === false )
					return false;
			}

			return true;
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

			if( isset( $made['failure'] ) === true )
				return $made;

			// A question whether the frame can be a block, and which languages lack a text: nothing is written
			if( isset( $made['gaps'] ) === true )
				return [ 'key' => $made['key'], 'checked' => true, 'gaps' => $made['gaps'] ];

			$written = self::write( $appData, $made['key'], $made['parts'] );

			// What the writing refuses of a frame is said in the words of a frame
			if( isset( $written['failure'] ) === true && Library::wrapOf( $template ) !== Library::PAGE )
				$written['failure'][2] = str_replace( 'builder_own_', 'builder_frame_', (string) $written['failure'][2] );

			return $written;
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
		 *	Take away a folder of the files of a block: its files, text/ and images/ and what they hold, and the folder - nothing of a link, nothing deeper
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

				if( is_link( $path ) === true || ( is_dir( $path ) === true && $entry !== 'text' && $entry !== 'images' ) )
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
