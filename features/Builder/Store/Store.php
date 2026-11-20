<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// What the Builder keeps and writes of a page: its template, the words of its namespace in every language and its picture slots - read together as one
	// state, stamped, written in a fixed order and kept one step back as the last state. The files are the project's own; nothing is written but the template
	// of the page or of the frame, the keys below /template/<template>/ and the slots below the same
	//
	//	the order of a save   the last state, the words, the slots, the template - and where one of the later steps fails, the state that was is put back: of each file
	//												only what this save wrote
	//	forget()							what is kept of a page that the kernel removed - its last state and its drafts
	class Store {

		// Where the last state of a template is kept: private/data/builder/previous/<template>.php
		public const string PREVIOUS = '/data/builder/previous';

		// Where the scaffold of a site is kept as it was before a turn of it: private/data/builder/scaffold/html-header.tpl and html-footer.tpl, the way back by hand
		public const string SCAFFOLD = '/data/builder/scaffold';

		// The lock that the writers of the Builder of a page - a save, a page that is made, a page that is copied - take one after the other: nothing else holds it, so
		// no write of a file lets it go
		public const string LOCK = '/data/builder/pages.lock';

		// The file of the formats and limits of the words
		private const string META = '/text/meta.php';

		// The most keys and texts a request names, and the longest text of one
		public const int MAX_KEYS = 600;
		public const int MAX_LENGTH = 20000;

		// The standard frame of each side of the site, and what the Builder calls it: a site has more of them - frame-<side>-<word> - which frames() lists
		public const array FRAMES = [ 'header' => 'frame-header', 'footer' => 'frame-footer' ];

		// The word of a named frame, its longest, and the two it may not be: they stand for no frame at a call of the frames
		public const string FRAME_WORD = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
		public const int FRAME_WORD_MAX = 40;
		public const array FRAME_RESERVED = [ 'none', 'default' ];

		/**
		 *	Which side of the page a template is a frame of - 'header' or 'footer' - by its NAME alone: the standard frame, or frame-<side>-<word> with a word of the form a word has.
		 *	Nothing of a path, a dot, a capital or a space gets through; null for every other name. Whether the file is there is frames()'s to say
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string|null
		 */
		public static function side( string $name ): ?string {

			foreach( self::FRAMES as $side => $standard ) {

				if( $name === $standard )
					return $side;

				if( str_starts_with( $name, $standard. '-' ) === true ) {

					$word = substr( $name, strlen( $standard ) + 1 );

					if( $word !== '' && strlen( $word ) <= self::FRAME_WORD_MAX && preg_match( self::FRAME_WORD, $word ) === 1 && in_array( $word, self::FRAME_RESERVED, true ) === false )
						return $side;
				}
			}

			return null;
		}

		/**
		 *	The frames the site has, each by its key - the template without frame-: header, footer, header-landing - and the template: of each side the standard first, then the named ones in
		 *	the order of their names. The templates are read from the folder of the templates - or, where the kernel lists them, as it does - and every name is held to side() and to a file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											key => template
		 */
		public static function frames( array &$appData ): array {

			$found = self::_kernelFrames( $appData ) ?? self::_scanFrames( $appData );

			$out = [];

			foreach( self::FRAMES as $side => $standard ) {

				$out[$side] = $standard;
				$named = array_filter( array_unique( $found ), static fn( string $name ): bool => $name !== $standard && self::side( $name ) === $side && is_file( \Nino\Filesystem::path( $appData, '/templates/'. $name. '.tpl' ) ) === true && is_link( \Nino\Filesystem::path( $appData, '/templates/'. $name. '.tpl' ) ) === false );

				sort( $named, SORT_STRING );

				foreach( $named as $name )
					$out[substr( $name, 6 )] = $name;
			}

			return $out;
		}

		/**
		 *	Whether the scaffolds of the site hold the places of their frames - \Nino\Modules\Template::framed(), asked through a callable, since a kernel before the call of
		 *	the frames has no such method
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											'header' => bool, 'footer' => bool - or [] where the kernel does not say
		 */
		public static function framed( array &$appData ): array {

			$call = self::_kernelCall( 'framed' );

			if( is_callable( $call ) === false )
				return [];

			/** @var mixed $answer */
			$answer = call_user_func_array( $call, [ &$appData ] );

			return is_array( $answer ) === true ? $answer : [];
		}

		/**
		 *	The call of the kernel that lists the frames, as class and method
		 *
		 *	@return 	array{string, string}
		 */
		private static function _kernelCall( string $method = 'frames' ): array {
			return [ '\\Nino\\Modules\\Template', $method ];
		}

		/**
		 *	The templates the kernel says are frames - \Nino\Modules\Template::frames(), which answers side => [ 'default', the words ] - as names of templates; null where the kernel has no such
		 *	call or answers in another form, so that the folder is read instead and never "no frames"
		 */
		private static function _kernelFrames( array &$appData ): ?array {

			// Asked through a callable, since a kernel before the call of the frames has no such method: the answer is of no known type here and is held to its form
			$call = self::_kernelCall();

			if( is_callable( $call ) === false )
				return null;

			/** @var mixed $answer */
			$answer = call_user_func_array( $call, [ &$appData ] );

			if( is_array( $answer ) === false || array_keys( $answer ) !== array_keys( self::FRAMES ) )
				return null;

			$names = [];

			foreach( self::FRAMES as $side => $standard ) {

				if( is_array( $answer[$side] ) === false || array_filter( $answer[$side], 'is_string' ) !== $answer[$side] )
					return null;

				foreach( $answer[$side] as $word )
					$names[] = $word === 'default' ? $standard : $standard. '-'. $word;
			}

			return $names;
		}

		/**
		 *	The templates of the folder of the templates, by name
		 */
		private static function _scanFrames( array &$appData ): array {

			$dir = \Nino\Filesystem::path( $appData, '/templates' );
			$found = [];

			foreach( is_dir( $dir ) === true ? (array) scandir( $dir ) : [] as $file )
				if( is_string( $file ) === true && str_ends_with( $file, '.tpl' ) === true )
					$found[] = substr( $file, 0, -4 );

			return $found;
		}

		// What a person calls a named frame, apart from its word: the word is in the address of the template and in every page that chooses the frame, so the name is kept here and nowhere in a
		// template. template => language => name
		public const string FRAME_NAMES = '/data/builder/frame-names.php';

		// Where the Builder keeps the stamp of the frame that a choice wrote - the mark the way back asks for - one file for each frame
		public const string FRAME_MARK = '/data/builder/frame-choice';

		/**
		 *	The names of the named frames, as the project keeps them: only for a template that has the form of a named frame, only a text for a language
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											template => language => name
		 */
		public static function frameNames( array &$appData ): array {

			// A store that cannot be read - a file that was edited by hand - is no store: the frames are called by their words, and the log says so once
			try {
				$kept = \Nino\Filesystem::getFileContent( $appData, self::FRAME_NAMES, [] );
			}
			catch( \Throwable $failure ) {

				static $said = false;

				if( $said === false ) {
					$said = true;
					error_log( 'Builder: the names of the frames could not be read from '. self::FRAME_NAMES. ' ('. $failure->getMessage(). ')' );
				}

				$kept = [];
			}

			$out = [];

			foreach( is_array( $kept ) === true ? $kept : [] as $template => $names )
				if( is_string( $template ) === true && self::side( $template ) !== null && in_array( $template, self::FRAMES, true ) === false && is_array( $names ) === true )
					foreach( $names as $locale => $name )
						if( is_string( $locale ) === true && is_string( $name ) === true && $name !== '' )
							$out[$template][$locale] = $name;

			return $out;
		}

		/**
		 *	The name, in a language, that another frame of the same side already has: two frames of one side are not called alike - the tiles of the choice would show two equal names
		 *
		 *	@param		string		$side
		 *	@param		string		$except				The frame that is named - it may keep its name
		 *	@param		array			$names				language => name
		 *
		 *	@return 	string|null								The name that is taken, null where none is
		 */
		public static function frameNameTaken( array &$appData, string $side, string $except, array $names ): ?string {

			foreach( self::frameNames( $appData ) as $template => $others ) {

				if( $template === $except || self::side( $template ) !== $side )
					continue;

				foreach( $names as $locale => $name )
					if( isset( $others[$locale] ) === true && mb_strtolower( trim( $others[$locale] ) ) === mb_strtolower( trim( (string) $name ) ) )
						return (string) $name;
			}

			return null;
		}

		/**
		 *	What a frame is called for a person, in a language: the name it has in that language, else in another; the standard frames and a frame with no name have none - the word is what they
		 *	are called then
		 */
		public static function frameName( array &$appData, string $template, string $locale ): string {

			$names = self::frameNames( $appData )[$template] ?? [];

			return (string) ( $names[$locale] ?? ( $names === [] ? '' : reset( $names ) ) );
		}

		/**
		 *	Keep the names of a named frame, or take them away with an empty list
		 */
		public static function setFrameName( array &$appData, string $template, array $names ): bool {

			if( self::side( $template ) === null || in_array( $template, self::FRAMES, true ) === true )
				return false;

			$all = self::frameNames( $appData );

			if( $names === [] )
				unset( $all[$template] );
			else
				$all[$template] = $names;

			return \Nino\Filesystem::putFileContent( $appData, self::FRAME_NAMES, $all ) === true;
		}

		/**
		 *	The pages that choose each frame: the template of the frame => the pages, each [ template, name, path ]. A page that calls the scaffold itself, or a frame with no word, has the standard
		 *	frame of the side; a page that has neither call is none of them. The templates are read as they stand - the call [frame /templates/frame-header-landing] is read by the form of
		 *	the call and nothing else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The language the names and the addresses are told in
		 *
		 *	@return 	array
		 */
		public static function frameUsage( array &$appData, string $locale ): array {

			$usage = [];
			$known = [];

			foreach( Pages::all( $appData, $locale ) as $page )
				$known[(string) $page['template']] = [ 'name' => (string) ( $page['name'] ?? $page['template'] ), 'path' => (string) ( $page['path'] ?? '' ) ];

			$dir = \Nino\Filesystem::path( $appData, '/templates' );
			$templates = $dir === '' ? [] : (array) glob( $dir. '/page-*.tpl' );
			sort( $templates );

			foreach( $templates as $file ) {

				$template = basename( (string) $file, '.tpl' );

				if( ( self::named( $template )['frame'] ?? true ) !== false || is_link( (string) $file ) === true )
					continue;

				// A page that has no route is named by its file; the last state of a page counts as well - a restore would write the frame back
				$who = $known[$template] ?? [ 'name' => $template. '.tpl', 'path' => '' ];
				$live = \Nino\Filesystem::getFileContent( $appData, '/templates/'. $template. '.tpl', null );
				$last = self::previous( $appData, $template );

				foreach( [ [ $live, false ], [ $last['source'] ?? null, true ] ] as [ $source, $kept ] ) {

					if( is_string( $source ) === false )
						continue;

					$seen = [];

					foreach( \Nino\Modules\Builder\Tree::read( $appData, $source ) as $node ) {

						$choice = \Nino\Modules\Builder\Tree::frameChoice( $node );

						if( $choice !== null )
							$frame = $choice[1] === 'default' ? self::FRAMES[$choice[0]] : ( $choice[1] === 'none' ? '' : self::FRAMES[$choice[0]]. '-'. $choice[1] );
						else if( ( $node['n'] ?? '' ) === 'template' && in_array( $node['s'] ?? '', \Nino\Modules\Builder\Tree::FRAME_CALLS, true ) === true )
							$frame = self::FRAMES[$node['s'] === \Nino\Modules\Builder\Tree::FRAME_CALLS[0] ? 'header' : 'footer'];
						else
							continue;

						if( $frame !== '' && isset( $seen[$frame] ) === false ) {
							$seen[$frame] = true;
							$usage[$frame][] = [ 'template' => $template, 'name' => $who['name'], 'path' => $who['path'], 'last' => $kept ];
						}
					}
				}
			}

			return $usage;
		}

		/**
		 *	The frames of the site for a list - the Builder's and Themelab's -: each with its key, its side, whether it is the standard one of its side, the names it was given and the one for the
		 *	language, and on how many pages it is chosen (the standard frame counts the pages that choose none of their own)
		 *
		 *	@return 	array											[ [ key, side, template, default, names, name, uses ] ]
		 */
		public static function frameList( array &$appData, string $locale ): array {

			$names = self::frameNames( $appData );
			$usage = self::frameUsage( $appData, $locale );
			$out = [];

			foreach( self::frames( $appData ) as $key => $template ) {

				$live = array_values( array_filter( $usage[$template] ?? [], static fn( array $use ): bool => $use['last'] === false ) );
				$out[] = [ 'key' => $key, 'side' => (string) self::side( $template ), 'template' => $template, 'default' => $key === self::side( $template ), 'names' => $names[$template] ?? [], 'name' => self::frameName( $appData, $template, $locale ), 'uses' => count( $live ), 'pages' => array_slice( array_column( $live, 'name' ), 0, 3 ) ];
			}

			return $out;
		}

		/**
		 *	Take a named frame away with everything that belongs to it: its template, its words in every language, its names, the last state of it and the mark of the choice that wrote it.
		 *	Only a named frame of the form, never a standard one; whether a page chooses it is asked before, by the one who calls this
		 */
		public static function dropFrame( array &$appData, string $template ): bool {

			if( self::side( $template ) === null || in_array( $template, self::FRAMES, true ) === true )
				return false;

			$real = \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' );

			if( $real === '' || is_file( $real ) === false || is_link( $real ) === true || @unlink( $real ) === false )
				return false;

			$keys = array_keys( \Nino\Text::values( $appData, '/template/'. $template ) );

			if( $keys !== [] )
				\Nino\Text::put( $appData, [], $keys );

			self::setFrameName( $appData, $template, [] );

			foreach( [ self::PREVIOUS. '/'. $template. '.php', self::FRAME_MARK. '/'. $template. '.php' ] as $path ) {

				$file = \Nino\Filesystem::path( $appData, $path );

				if( $file !== '' && ( is_file( $file ) === true || is_link( $file ) === true ) )
					@unlink( $file );

				unset( $appData['./nino/filesystem/cache'][$path] );
			}

			return true;
		}

		/**
		 *	Whether a name is a frame of this site: a standard frame, or a named one whose file is there - the form is side()'s, nothing of a request is taken unchecked
		 */
		public static function isFrame( array &$appData, string $name ): bool {
			return self::side( $name ) !== null && in_array( $name, self::frames( $appData ), true ) === true;
		}

		/**
		 *	What a name is, as the Builder may open it: a template of a page - page-<word> - or a frame of the site, by its name (the file is the state's to find)
		 *
		 *	@param		string		$name
		 *
		 *	@return 	array|null								[ 'name', 'frame' ]
		 */
		public static function named( string $name ): ?array {

			if( self::side( $name ) !== null )
				return [ 'name' => $name, 'frame' => true ];

			return preg_match( '/^page-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $name ) === 1 && strlen( $name ) <= 80 ? [ 'name' => $name, 'frame' => false ] : null;
		}

		/**
		 *	The state of a page as the project holds it now
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *
		 *	@return 	array|null								[ 'source', 'texts', 'slots' ]; null where there is no such template
		 */
		public static function state( array &$appData, string $template ): ?array {

			$source = \Nino\Filesystem::getFileContent( $appData, '/templates/'. $template. '.tpl', null );

			if( is_string( $source ) === false )
				return null;

			$slots = [];

			foreach( (array) ( $appData['/nino/html/images'] ?? [] ) as $uri => $slot )
				if( is_string( $uri ) === true && str_starts_with( $uri, '/template/'. $template. '/' ) === true && is_array( $slot ) === true )
					$slots[$uri] = array_intersect_key( $slot, [ 'label' => 1, 'width' => 1, 'height' => 1, 'filename' => 1 ] );

			$texts = \Nino\Text::values( $appData, '/template/'. $template );
			ksort( $texts );
			ksort( $slots );

			return [ 'source' => $source, 'texts' => $texts, 'slots' => $slots ];
		}

		/**
		 *	What tells a page that it was changed since it was opened: its template, its words in every language and its slots
		 *
		 *	@param		array			$state
		 *
		 *	@return 	string										A sha256
		 */
		public static function stamp( array $state ): string {

			$sort = static function( array $list ): array {
				ksort( $list );
				return $list;
			};

			return hash( 'sha256', (string) json_encode( [ $state['source'], array_map( $sort, $sort( $state['texts'] ) ), $sort( $state['slots'] ) ] ) );
		}

		/**
		 *	A page that is written is written in the line ends it has: the ends that most of its lines of the old text have - CRLF where there are more of them than of LF, else LF. What is new in
		 *	it takes them; a text that differs from the old one in nothing but the ends is the old text, to the byte, so that a save that changes nothing changes nothing
		 *
		 *	@param		string		$written			What the tree is written as
		 *	@param		string		$old					The text of the template as it stands
		 *
		 *	@return 	string
		 */
		public static function eol( string $written, string $old ): string {

			$plain = static fn( string $text ): string => str_replace( "\r\n", "\n", $text );

			if( $plain( $written ) === $plain( $old ) )
				return $old;

			$crlf = substr_count( $old, "\r\n" );

			return $crlf > substr_count( $old, "\n" ) - $crlf ? str_replace( "\n", "\r\n", $plain( $written ) ) : $plain( $written );
		}

		/**
		 *	What is checked of a draft or a save, before anything is drawn or written: the tree is one the Builder takes, it is the old page with its blocks
		 *	moved, copied or left out, its fixed texts are texts, and the words are for keys of the namespace, in languages of the site, and kept for the keys a call still reads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$state				As state() answers it
		 *	@param		mixed			$tree					What the person sent
		 *	@param		mixed			$texts				key => language => text
		 *	@param		mixed			$copies				slot of a copy => the slot of the original it is made from
		 *	@param		mixed			$inserted			id of the root of a block that was put in => the key of the block, which \Nino\Blocks is asked for
		 *	@param		int				$level				The step the account holds
		 *	@param		mixed			$chosen				uri of a slot of the page => [ label, width, height, file ]: the pictures a person made and has not saved
		 *
		 *	@return 	array											'source', 'draft' (the source as a draft draws it), 'tree', 'sources', 'formats', 'texts' (key => language => text as it is kept), 'slots' (the slots of the copies and of the blocks put in, uri => slot), 'files', 'findings' (what the register has against the tree, for the screen of a person who designs) - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function prepare( array &$appData, string $template, array $state, mixed $tree, mixed $texts, mixed $copies = [], mixed $inserted = [], int $level = \Nino\Modules\Builder::LEVEL_CONTENT, mixed $chosen = [] ): array {

			$registry = \Nino\Components::components( $appData );
			$canonical = \Nino\Modules\Builder\Tree::canonical( $appData, $tree );

			if( $canonical === null )
				return [ 'failure' => [ 400, 'the tree is none the Builder takes', 'builder_tree', [], 'tree' ] ];

			[ $source, $nodes ] = $canonical;
			$old = \Nino\Modules\Builder\Tree::read( $appData, $state['source'] );

			$taken = \Nino\Modules\Builder\Tree::taken( $old, $registry, array_merge( array_keys( $state['texts'] ), array_keys( $state['slots'] ) ) );

			// The blocks that are put in: each is a block that \Nino\Blocks offers now, and the rule holds the tree to it
			$blocks = [];
			$offers = [];

			if( is_array( $inserted ) === false || count( $inserted ) > self::MAX_KEYS )
				return [ 'failure' => [ 400, 'the blocks are none', 'builder_block', [], 'blocks' ] ];

			// The content of the blocks of a page is the content step's, the build of the page is not: no block is put in, and none is copied
			if( $level < \Nino\Modules\Builder::LEVEL_DESIGN && ( $inserted !== [] || empty( $copies ) === false ) )
				return [ 'failure' => [ 403, 'the page may not be changed like that: block', 'builder_refused_block', [], 'tree' ] ];

			foreach( $inserted as $id => $key ) {

				$block = is_string( $id ) === true && \Nino\Modules\Builder\Tree::isId( $id ) === true && is_string( $key ) === true ? \Nino\Modules\Builder\Library::block( $appData, $key, \Nino\Modules\Builder\Library::wrapOf( $template ) ) : null;

				if( $block === null )
					return [ 'failure' => [ 403, 'the block is none that is offered', 'builder_block', [], 'blocks' ] ];

				$blocks[$id] = $block;
				$offers[$id] = $block['tree'][0];
			}

			$why = $level >= \Nino\Modules\Builder::LEVEL_DESIGN
				? \Nino\Modules\Builder\Tree::designed( $appData, $old, $nodes, $registry, $taken, $level >= \Nino\Modules\Builder::LEVEL_DEVELOP )
				: \Nino\Modules\Builder\Tree::refused( $old, $nodes, $registry );

			if( $why !== null )
				return [ 'failure' => [ 403, 'the page may not be changed like that: '. $why, 'builder_refused_'. $why, [], 'tree' ] ];

			$fills = \Nino\Modules\Builder\Tree::fills( $old );
			$nodes = self::_fixed( $nodes, $registry, $fills );

			if( $nodes === 'fill' )
				return [ 'failure' => [ 403, 'a fixed text may not hold the start of a fill', 'builder_refused_value', [], 'tree' ] ];

			if( $nodes === null )
				return [ 'failure' => [ 400, 'a fixed text is not one', 'builder_fixed', [ \Nino\Modules\Builder\Tree::MAX_TEXT ], 'tree' ] ];

			$source = self::eol( \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) ), $state['source'] );

			if( is_array( $texts ) === false || count( $texts ) > self::MAX_KEYS )
				return [ 'failure' => [ 400, 'the texts are none', 'builder_texts', [], 'texts' ] ];

			$sources = \Nino\Modules\Builder\Tree::sources( $nodes, $registry, $template );

			// Where a link goes may be written in a text: the text of every key of the page that a link reads - the words in the request, and the words that stand in the file where
			// the link is new - is held to the one rule for a target, whoever writes it, in whatever step, and whatever else the key is read as
			$before = [];

			foreach( \Nino\Modules\Builder\Tree::sources( $old, $registry, $template ) as $entry )
				if( $entry['kind'] === 'link' && $entry['key'] !== '' )
					$before[$entry['key']] = true;

			// A text or a picture of another place that a call reads is one the site has: a source that is chosen is a source that is there - as a type of elements is - and no
			// key typed into a request, whose words would be none
			$stood = [];

			foreach( \Nino\Modules\Builder\Tree::sources( $old, $registry, $template ) as $entry )
				$stood[$entry['kind']. "\0". $entry['key']] = true;

			foreach( $sources as $entry )
				if( in_array( $entry['kind'], [ 'text', 'image' ], true ) === true && $entry['key'] !== '' && \Nino\Modules\Builder\Tree::own( $entry['key'], $template ) === false && isset( $stood[$entry['kind']. "\0". $entry['key']] ) === false ) {

					$there = $entry['kind'] === 'image' ? \Nino\Images::getSlot( $appData, $entry['key'] ) !== false : isset( \Nino\Text::values( $appData, dirname( $entry['key'], 2 ) )[$entry['key']] ) === true;

					if( $there === false )
						return [ 'failure' => [ 403, 'the source is none the site has', 'builder_refused_source', [], 'tree' ] ];
				}

			foreach( $sources as $entry ) {

				if( $entry['kind'] !== 'link' || $entry['key'] === '' || \Nino\Modules\Builder\Tree::own( $entry['key'], $template ) === false )
					continue;

				$words = is_array( $texts[$entry['key']] ?? null ) === true ? $texts[$entry['key']] : [];

				// What a link reads is the target of the link: the step that changes the content writes the alt of a picture and no word a link reads
				if( $level < \Nino\Modules\Builder::LEVEL_DESIGN && isset( $state['texts'][$entry['key']] ) === true && array_diff_assoc( $words, (array) ( $state['texts'][$entry['key']] ?? [] ) ) !== [] )
					return [ 'failure' => [ 403, 'the key is not one of a text', 'builder_key', [], 'texts' ] ];

				if( isset( $before[$entry['key']] ) === false )
					$words += (array) ( $state['texts'][$entry['key']] ?? [] );

				foreach( $words as $word )
					if( is_string( $word ) === true && \Nino\Modules\Builder\Tree::linkTarget( $appData, $word, false ) === false )
						return [ 'failure' => [ 403, 'a text that a link reads is no target', 'builder_refused_link', [], 'texts' ] ];
			}
			$formats = [];

			// The blocks that were put in and are on the top level with an id the page did not have: the words they read in an attribute - the text of a link, an alt - are
			// words of the block as well, as plain text
			$fresh = [];

			foreach( $nodes as $node )
				if( isset( $node['n'] ) === true && ( $idName = \Nino\Modules\Builder\Tree::idAttribute( $registry[$node['n']] ?? [] ) ) !== '' && is_string( $node['a'][$idName] ?? null ) === true
					&& isset( $blocks[$node['a'][$idName]] ) === true && in_array( $node['a'][$idName], $taken, true ) === false )
					$fresh[$node['a'][$idName]] = $blocks[$node['a'][$idName]];

			// What they bring for those words, by language; and which id of a copy is which id of the block it was made from, whose words it takes
			$brought = [];
			$copyOf = [];

			foreach( $fresh as $id => $block )
				foreach( \Nino\Modules\Builder\Library::places( $block, $id, $template ) as $place )
					if( $place['kind'] === 'text' )
						$brought[$place['key']] = array_map( static fn( array $words ): mixed => $words[$place['name']] ?? null, $block['texts'] );

			foreach( $nodes as $node )
				if( isset( $node['n'] ) === true && ( $idName = \Nino\Modules\Builder\Tree::idAttribute( $registry[$node['n']] ?? [] ) ) !== '' && is_string( $node['a'][$idName] ?? null ) === true && in_array( $node['a'][$idName], $taken, true ) === false )
					foreach( $old as $original )
						if( isset( $original['n'] ) === true && \Nino\Modules\Builder\Tree::likeBlock( $node, $original, $registry ) === true ) {
							$copyOf += \Nino\Modules\Builder\Tree::pairs( $original, $node, $registry );
							break;
						}

			foreach( $sources as $entry )
				if( $entry['editable'] === true && $entry['key'] !== '' )
					$formats[$entry['key']] ??= $entry['format'];

			$reads = array_flip( \Nino\Modules\Builder\Tree::readKeys( $nodes, $registry, $template, $source ) );
			$images = [];
			$attributes = [];
			$named = [];

			foreach( $sources as $entry ) {

				if( $entry['kind'] === 'image' && $entry['key'] !== '' )
					$images[$entry['key']] = true;

				// The words a call reads in an attribute - an alt, the text of a link - and which of them an address is
				if( $entry['attribute'] !== '' && $entry['key'] !== '' && in_array( $entry['kind'], [ 'text', 'link' ], true ) === true && \Nino\Modules\Builder\Tree::own( $entry['key'], $template ) === true ) {

					$attributes[$entry['key']] = ( $attributes[$entry['key']] ?? '' ) === 'link' ? 'link' : $entry['kind'];
					$named[$entry['key']][$entry['attribute']] = true;
				}
			}
			$locales = \Nino\Locales::getTextLocales( $appData );
			$meta = \Nino\Text::meta( $appData );
			$kept = [];
			$raw = [];

			foreach( $texts as $key => $values ) {

				if( is_string( $key ) === false || \Nino\Modules\Builder\Tree::own( $key, $template ) === false || is_array( $values ) === false )
					return [ 'failure' => [ 403, 'the key is not one of the page', 'builder_key', [], 'texts' ] ];

				// A key of the page that no call reads any more - a block that was copied and then taken out - has no words to keep; the key of a picture is no text
				if( isset( $reads[$key] ) === false )
					continue;

				// A word that a call reads in an attribute - the text of a link, an alt - is no word the page is written in: it is kept as it is saved, and it is new only as the
				// block that was put in brings it or as the original of the copy has it, never as a person wrote it, and never as an address that runs a script or leaves the site
				if( isset( $formats[$key] ) === false ) {

					if( $values === array_intersect_key( (array) ( $state['texts'][$key] ?? [] ), $values ) )
						continue;

					$parts = explode( '/', $key );
					$origin = count( $parts ) === 5 && isset( $copyOf[$parts[3]] ) === true ? '/template/'. $template. '/'. $copyOf[$parts[3]]. '/'. $parts[4] : '';
					$may = isset( $brought[$key] ) === true || $origin !== '';

					foreach( $values as $locale => $text )
						$may = $may && is_string( $text ) === true && preg_match( '#^\s*(?:(?:javascript|data|vbscript):|//)#i', $text ) !== 1
							&& ( $text === ( $brought[$key][$locale] ?? null ) || $text === ( $brought[$key][\Nino\Blocks::FALLBACK] ?? null ) || $text === ( $state['texts'][$origin][$locale] ?? null ) );

					// A person who designs writes the word of an attribute of a call of the page as a text of its own - the alt of a picture, the text of a link - and a person who may
					// change the content writes the alt of a picture: it is a plain text; what a link reads was held to the rule for a target before
					if( $may === false && isset( $attributes[$key] ) === true && ( $level >= \Nino\Modules\Builder::LEVEL_DESIGN || ( $attributes[$key] === 'text' && array_keys( $named[$key] ) === [ 'alt' ] ) ) )
						$may = array_filter( $values, static fn( mixed $text ): bool => is_string( $text ) === false ) === [];

					if( $may === false )
						return [ 'failure' => [ 403, 'the key is not one of a text', 'builder_key', [], 'texts' ] ];

					$formats[$key] = 'plain';
				}

				foreach( $values as $locale => $text ) {

					if( is_string( $locale ) === false || in_array( $locale, $locales, true ) === false )
						return [ 'failure' => [ 403, 'the language is none of the site', 'builder_locale', [], 'texts' ] ];

					if( is_string( $text ) === false || strlen( $text ) > self::MAX_LENGTH || str_contains( $text, "\0" ) === true )
						return [ 'failure' => [ 400, 'the text is too long or has a character it may not have', 'builder_text', [ self::MAX_LENGTH ], 'texts' ] ];

					$raw[$key][$locale] = $text;
					$format = $meta[$key]['format'] ?? $formats[$key] ?? \Nino\Html::detectFormat( $text );
					$kept[$key][$locale] = \Nino\Text::sanitizeValue( $text, in_array( $format, \Nino\Html::FORMATS, true ) === true ? $format : 'plain' );
				}
			}

			// The slot of a copy: a picture the copy reads and the page has no slot for is made from the slot of the original, its file and its size - never from anything else
			$slots = [];
			$shown = [];
			$files = [];

			if( is_array( $copies ) === false || count( $copies ) > self::MAX_KEYS )
				return [ 'failure' => [ 400, 'the pictures are none', 'builder_texts', [], 'slots' ] ];

			foreach( $copies as $new => $was ) {

				if( is_string( $new ) === false || is_string( $was ) === false || \Nino\Modules\Builder\Tree::own( $new, $template ) === false || \Nino\Modules\Builder\Tree::own( $was, $template ) === false || isset( $state['slots'][$was] ) === false )
					return [ 'failure' => [ 403, 'the picture is not one of the page', 'builder_key', [], 'slots' ] ];

				if( isset( $images[$new] ) === true && isset( $state['slots'][$new] ) === false ) {

					$slots[$new] = $state['slots'][$was];
					$shown[$new] = $slots[$new];
					$file = $slots[$new]['filename'] ?? null;

					// The file of the copy has the name of its own slot - the name of the original's with the path of the original's slot replaced - and is made from
					// the bytes of the original's when the page is saved; the two slots never share a file
					if( is_string( $file ) === true && $file !== '' ) {

						$from = ltrim( $was, '/' );
						$to = ltrim( $new, '/' );
						$rest = str_starts_with( $file, $from. '.' ) === true ? substr( $file, strlen( $from ) ) : '.'. preg_replace( '/[^A-Za-z0-9]/', '', pathinfo( $file, PATHINFO_EXTENSION ) );
						$slots[$new]['filename'] = $to. $rest;
						$files[$to. $rest] = $file;
					}
				}
			}

			// The slots of the blocks that were put in: each picture of the block is a slot of the namespace of its new id, with the size and the name the block says and
			// the picture of the kernel as its file. Where the page has the slot already - it was copied above, or the id was the page's - nothing is made
			$pictures = [];
			$staged = [];
			$locale = \Nino\Locales::getNativeLocale( $appData );

			foreach( $fresh as $id => $block ) {

				foreach( \Nino\Modules\Builder\Library::places( $block, $id, $template ) as $place ) {

					$spec = $block['images'][$place['name']] ?? null;

					if( $place['kind'] !== 'image' || $spec === null || isset( $images[$place['key']] ) === false || isset( $slots[$place['key']] ) === true || isset( $state['slots'][$place['key']] ) === true )
						continue;

					$file = ltrim( $place['key'], '/' ). '.svg';
					$label = (string) ( $spec['label'][$locale] ?? $spec['label'][\Nino\Blocks::FALLBACK] ?? reset( $spec['label'] ) );

					// A slot that the block has a picture of its own for has that picture to begin with, for the one who designs: made from what the server reads of the block, as a
					// picture of the slot is made of an upload. The page owns it from then on - the block that changes later changes nothing of it. Any other slot has the placeholder
					$own = $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? \Nino\Modules\Builder\Library::picture( $appData, (string) $block['key'], (string) $place['name'] ) : null;
					$made = $own === null ? false : \Nino\Modules\Builder\Pictures::stageOwn( $appData, $place['key'], $own, (int) $spec['width'], (int) $spec['height'] );

					if( $made !== false ) {

						$slots[$place['key']] = [ 'label' => $label, 'width' => $spec['width'], 'height' => $spec['height'], 'filename' => $made ];
						$shown[$place['key']] = $slots[$place['key']];
						$staged[] = $made;
						continue;
					}

					$slots[$place['key']] = [ 'label' => $label, 'width' => $spec['width'], 'height' => $spec['height'], 'filename' => $file ];
					$files[$file] = $spec['placeholder'];
					$pictures[$place['key']] = $spec['placeholder'];
				}
			}

			// The pictures a person chose and has not saved: a slot of the page that is made, a file that was uploaded for it - the draft shows them
			$wrong = \Nino\Modules\Builder\Pictures::accept( $appData, $template, $state, $chosen, $images, $slots, $shown );

			if( $wrong !== null )
				return [ 'failure' => [ $wrong === 'picture' ? 403 : 409, $wrong === 'picture' ? 'the picture is not one of the page' : 'the picture that was made is not there any more', 'builder_'. $wrong, [], 'pictures' ] ];

			$draft = $pictures === [] ? $source : \Nino\Markup::write( \Nino\Modules\Builder\Tree::shown( $nodes, $sources, $pictures ), \Nino\Components::containers( $appData ) );

			return [ 'source' => $source, 'draft' => $draft, 'tree' => $nodes, 'sources' => $sources, 'formats' => $formats, 'texts' => $kept, 'raw' => $raw, 'reads' => array_keys( $reads ), 'images' => array_keys( $images ), 'slots' => $slots, 'shown' => $shown, 'staged' => $staged, 'files' => $files, 'registry' => $registry, 'findings' => $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? \Nino\Modules\Builder\Tree::findings( $appData, $nodes ) : [] ];
		}

		/**
		 *	The fixed texts of a tree, each one a line of at most Tree::MAX_TEXT characters without a control character
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *
		 *	@return 	array|null								The nodes; null where a fixed text is none
		 */
		private static function _fixed( array $nodes, array $registry, array &$fills ): array|string|null {

			foreach( $nodes as $at => $node ) {

				if( isset( $node['n'] ) === false )
					continue;

				if( isset( $registry[$node['n']] ) === true && array_key_exists( 'text', (array) ( $node['a'] ?? [] ) ) === true ) {

					$text = $node['a']['text'];

					if( is_string( $text ) === false || mb_strlen( $text ) > \Nino\Modules\Builder\Tree::MAX_TEXT || preg_match( '/[\x00-\x1F\x7F]/', $text ) === 1 )
						return null;

					// The start of a textfill is a text of the template that was there, in that call, and no text a person writes
					if( str_contains( $text, '[[' ) === true ) {

						$key = $node['n']. "\0". $text;

						if( ( $fills[$key] ?? 0 ) < 1 )
							return 'fill';

						$fills[$key]--;
					}
				}

				if( is_array( $node['c'] ?? null ) === true ) {

					$inner = self::_fixed( $node['c'], $registry, $fills );

					if( $inner === null || $inner === 'fill' )
						return $inner;
				}
			}

			return $nodes;
		}

		/**
		 *	The keys and slots of the namespace that nothing reads any more, and the words that changed, of a save
		 *
		 *	@param		array			$state				The state now
		 *	@param		array			$prepared			As prepare() answers it
		 *	@param		string		$template
		 *
		 *	@return 	array											'set' (key => language => text), 'drop' (keys), 'dropSlots' (uris)
		 */
		public static function changes( array $state, array $prepared, string $template ): array {

			$set = [];

			foreach( $prepared['texts'] as $key => $values )
				foreach( $values as $locale => $text )
					// A text that is posted as it stands is not touched: what was written by hand is not cleaned by being looked at
					if( isset( $state['texts'][$key] ) === false || ( ( $state['texts'][$key][$locale] ?? null ) !== $text && ( $state['texts'][$key][$locale] ?? null ) !== ( $prepared['raw'][$key][$locale] ?? null ) ) )
						$set[$key][$locale] = $text;

			$reads = array_flip( $prepared['reads'] );
			$drop = array_values( array_filter( array_keys( $state['texts'] ), static fn( string $key ): bool => isset( $reads[$key] ) === false ) );
			$kinds = array_flip( $prepared['images'] );
			$dropSlots = array_values( array_filter( array_keys( $state['slots'] ), static fn( string $uri ): bool => isset( $kinds[$uri] ) === false && isset( $reads[$uri] ) === false ) );

			return [ 'set' => $set, 'drop' => $drop, 'dropSlots' => $dropSlots ];
		}

		/**
		 *	The last state of a template as it was kept
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *
		 *	@return 	array|null
		 */
		public static function previous( array &$appData, string $template ): ?array {

			$kept = \Nino\Filesystem::getFileContent( $appData, self::PREVIOUS. '/'. $template. '.php', null );

			return is_array( $kept ) === true && is_string( $kept['source'] ?? null ) === true && is_array( $kept['texts'] ?? null ) === true && is_array( $kept['slots'] ?? null ) === true ? $kept : null;
		}

		/**
		 *	Keep a state as the last one
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		array			$state
		 *
		 *	@return 	bool
		 */
		public static function keep( array &$appData, string $template, array $state, int $level = \Nino\Modules\Builder::LEVEL_CONTENT ): bool {
			return \Nino\Filesystem::putFileContent( $appData, self::PREVIOUS. '/'. $template. '.php', [ 'saved' => time(), 'level' => $level ] + $state ) === true;
		}

		/**
		 *	Take away what the Builder keeps of a page that is gone: its last state and the drafts of its previews, the page's and the small pictures of its blocks. The
		 *	name is held to the rule of the kernel for the name of a page - nothing is looked at for any other, a frame among them - before a path is made of it. The
		 *	pictures of the page are the kernel's to remove, with the rest of the page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The template of the page that was removed
		 *
		 *	@return 	void
		 */
		public static function forget( array &$appData, string $template ): void {

			if( strlen( $template ) > \Nino\Pages::MAX_NAME || preg_match( \Nino\Pages::TEMPLATE, $template ) !== 1 )
				return;

			$kept = self::PREVIOUS. '/'. $template. '.php';
			$real = \Nino\Filesystem::path( $appData, $kept );

			if( $real !== '' && ( is_file( $real ) === true || is_link( $real ) === true ) && \Nino\Filesystem::lockFile( $appData, $kept ) === true ) {

				@unlink( $real );
				\Nino\Filesystem::unlockFile( $appData, $kept );
				unset( $appData['./nino/filesystem/cache'][$kept] );
			}

			foreach( [ \Nino\Modules\Builder::KIND, \Nino\Modules\Builder\Library::KIND ] as $kind ) {

				$base = \Nino\Filesystem::path( $appData, \Nino\Preview::DIR. '/'. $kind );

				// A kind that is a link leads out of the folder of the drafts: the kernel leaves it alone when it tidies, and so does this
				foreach( $base === '' || is_link( $base ) === true ? [] : (array) glob( $base. '/*', GLOB_ONLYDIR ) as $folder ) {

					$token = basename( (string) $folder );

					if( preg_match( \Nino\Preview::TOKEN, $token ) !== 1 || is_link( (string) $folder ) === true )
						continue;

					$draft = \Nino\Filesystem::getFileContent( $appData, \Nino\Preview::DIR. '/'. $kind. '/'. $token. '/draft.json', null );

					if( is_array( $draft ) === true && ( $draft['template'] ?? null ) === $template )
						\Nino\Filesystem::removeDir( (string) $folder );
				}
			}
		}

		/**
		 *	Make the project hold a state: its words in every language it names, the keys of the namespace it does not have removed, its slots, its template. The
		 *	template is written last and only where it still is what the caller saw ($seen, a sha256 of its text, '' for no check). Each step is noted as the bytes
		 *	the files had right before it and the bytes it left in them (see _note()), so that a failure later puts back what this save wrote and nothing that was
		 *	written by somebody else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$target				A state
		 *	@param		array			$now					The state that is replaced
		 *	@param		array|null								$changes			As changes() answers it, or null for every difference of the two states
		 *	@param		string		$seen
		 *	@param		array			$formats			key => format for the keys that are new
		 *	@param		array			&$wrote				(reference) path => the bytes this save left in it
		 *	@param		array			&$before			(reference) path => the bytes the file had right before this save wrote it, null for no file
		 *
		 *	@return 	string|null								null where it was done, else 'texts', 'slots' or 'template' - the step that failed - or 'changed' (the template was written by somebody else meanwhile)
		 */
		public static function apply( array &$appData, string $template, array $target, array $now, ?array $changes, string $seen, array $formats = [], array &$wrote = [], array &$before = [] ): ?string {

			if( $changes === null ) {

				$set = [];

				foreach( $target['texts'] as $key => $values )
					foreach( $values as $locale => $text )
						if( ( $now['texts'][$key][$locale] ?? null ) !== $text || isset( $now['texts'][$key] ) === false )
							$set[$key][$locale] = $text;

				$changes = [ 'set' => $set, 'drop' => array_values( array_diff( array_keys( $now['texts'] ), array_keys( $target['texts'] ) ) ), 'dropSlots' => array_values( array_diff( array_keys( $now['slots'] ), array_keys( $target['slots'] ) ) ) ];
			}

			if( $changes['set'] !== [] || $changes['drop'] !== [] ) {

				$paths = [ self::META ];

				foreach( $changes['set'] as $values )
					foreach( array_keys( $values ) as $locale )
						$paths[] = '/text/'. $locale. '.php';

				// A key that is taken away is taken from every file of the words
				if( $changes['drop'] !== [] )
					$paths = array_merge( $paths, self::_texts( $appData ) );

				$paths = array_values( array_unique( $paths ) );

				if( self::_hold( $appData, $template ) !== null )
					return 'gone';

				$was = self::_files( $appData, $template, $paths );
				$put = \Nino\Text::put( $appData, $changes['set'], $changes['drop'], $formats );
				self::_note( $appData, $template, $was, [], $wrote, $before );

				if( $put === null )
					return 'texts';
			}

			$was = self::_files( $appData, $template, [ '/config.php' ] );
			$failed = false;
			$stopped = null;

			// Each write of a slot lets the lock of config.php go: the next one takes it again, and finds out whether the page is still there
			foreach( $target['slots'] as $uri => $slot )
				if( $failed === false && $stopped === null && ( $now['slots'][$uri] ?? null ) !== $slot ) {

					$stopped = self::_hold( $appData, $template );
					$failed = $stopped === null && \Nino\Images::putSlot( $appData, (string) $uri, $slot ) === false;
				}

			foreach( $changes['dropSlots'] as $uri )
				if( $failed === false && $stopped === null && isset( $target['slots'][$uri] ) === false ) {

					$stopped = self::_hold( $appData, $template );
					$failed = $stopped === null && \Nino\Images::dropSlot( $appData, $uri ) === false;
				}

			self::_note( $appData, $template, $was, [ '/config.php' ], $wrote, $before );

			if( $stopped !== null )
				return 'gone';

			if( $failed === true )
				return 'slots';

			$file = '/templates/'. $template. '.tpl';

			// A save that leaves the template as it is has seen it as well: where it was changed or taken away since, the words that were written are put back
			if( $target['source'] === $now['source'] )
				return $seen === '' || hash_equals( $seen, hash( 'sha256', (string) ( self::_files( $appData, $template, [ $file ] )[$file] ?? '' ) ) ) === true ? null : 'changed';

			if( self::_hold( $appData, $template ) !== null )
				return 'gone';

			$gone = false;
			$was = self::_files( $appData, $template, [ $file ] );
			$ok = \Nino\Filesystem::mutate( $appData, $file, static function( mixed $text ) use ( $target, $seen, &$gone ): ?string {

				if( $seen !== '' && hash_equals( $seen, hash( 'sha256', (string) $text ) ) === false ) {
					$gone = true;
					return null;
				}

				return $target['source'];
			}, '' );

			if( $ok === true )
				self::_note( $appData, $template, $was, [ $file ], $wrote, $before );

			return $ok === true ? null : ( $gone === true ? 'changed' : 'template' );
		}

		/**
		 *	What one step of a save wrote: each file of the step that holds other bytes than it did right before the step is the step's, with the bytes it had and the ones
		 *	it left. Only the files that the step writes are looked at, so that what somebody else writes to any other file while the step runs is never noted as the
		 *	save's. A file that the save wrote in an earlier step and that still holds what that step left stays one file with the bytes it had at the start; one that was
		 *	changed by somebody else between two steps is theirs from then on and is not noted again
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		array			$was					As _files() answered it for $paths right before the step
		 *	@param		array			$paths				The files the step writes, by their virtual path; where none is named, those $was holds
		 *	@param		array			&$wrote				(reference) path => the bytes this save left in it
		 *	@param		array			&$before			(reference) path => the bytes the file had before this save wrote it
		 *
		 *	@return 	void
		 */
		private static function _note( array &$appData, string $template, array $was, array $paths, array &$wrote, array &$before ): void {

			foreach( self::_files( $appData, $template, $paths === [] ? array_keys( $was ) : $paths ) as $path => $bytes ) {

				$had = $was[$path] ?? null;

				if( $bytes === $had )
					continue;

				if( array_key_exists( $path, $wrote ) === false ) {
					$before[$path] = $had;
					$wrote[$path] = $bytes;
				}
				else if( $wrote[$path] === $had )
					$wrote[$path] = $bytes;
			}
		}

		/**
		 *	The files of the words, by their virtual path: one for each language that has one, the file of the words of every language and the file of the formats
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		private static function _texts( array &$appData ): array {

			$paths = [];

			foreach( (array) glob( \Nino\Filesystem::path( $appData, '/text' ). '/*.php' ) as $file )
				$paths[] = '/text/'. basename( (string) $file );

			return $paths;
		}

		/**
		 *	The files a save may touch, by their virtual path: the template, config.php (the slots), every file of the words and their meta, and the last state
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		array|null								$only					The files to read, by their virtual path; null for every one of them
		 *
		 *	@return 	array											virtual path => the bytes the file has, null for a file that is not there
		 */
		private static function _files( array &$appData, string $template, ?array $only = null ): array {

			$paths = $only ?? array_merge( [ '/templates/'. $template. '.tpl', '/config.php', self::PREVIOUS. '/'. $template. '.php' ], self::_texts( $appData ) );

			$files = [];

			foreach( $paths as $path ) {

				$real = \Nino\Filesystem::path( $appData, $path );
				$bytes = $real !== '' && is_file( $real ) === true ? file_get_contents( $real ) : false;
				$files[$path] = $bytes === false ? null : $bytes;
			}

			return $files;
		}

		/**
		 *	Put back what a save wrote, where a step of it failed: each file this save wrote - and only those - is written as it was before, byte for byte, while its
		 *	lock is held and only if it still holds the bytes this save left. A file that somebody else wrote since is theirs and stays; nothing is written through
		 *	the writers that clean or sort what they write
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$before				As _files() answered it before the save
		 *	@param		array			$wrote				path => the bytes this save left in it
		 *	@param		array			$made					The files of pictures this save made below /images/
		 *
		 *	@return 	void
		 */
		private static function _back( array &$appData, array $before, array $wrote, array $made ): void {

			foreach( $wrote as $path => $mine ) {

				$real = \Nino\Filesystem::path( $appData, $path );

				if( $real === '' || \Nino\Filesystem::lockFile( $appData, $path ) === false )
					continue;

				$now = is_file( $real ) === true ? file_get_contents( $real ) : null;

				if( $now === $mine ) {

					if( $before[$path] === null )
						@unlink( $real );
					else
						file_put_contents( $real, $before[$path] );
				}

				\Nino\Filesystem::unlockFile( $appData, $path );
				unset( $appData['./nino/filesystem/cache'][$path] );
			}

			foreach( $made as $file )
				\Nino\Images::delete( $appData, $file );

			$config = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
			$appData['/nino/html/images'] = is_array( $config ) === true && is_array( $config['/nino/html/images'] ?? null ) === true ? $config['/nino/html/images'] : [];
		}

		/**
		 *	Make the files of the pictures of the copies: each from the bytes of the original's, under the name of its own slot. A file that cannot be read has no
		 *	copy, and its slot has no file
		 *
		 *	@param		array 		&$appData
		 *	@param		array			&$target			(reference) The state to write, whose slots get the name of their file
		 *	@param		array			$files				new file => the file it is made from, or the reference of the picture of the kernel it is
		 *	@param		array			&$made				(reference) What was made
		 *
		 *	@return 	void
		 */
		private static function _copies( array &$appData, array &$target, array $files, array &$made ): void {

			foreach( $files as $new => $old ) {

				$bytes = isset( \Nino\Images::PLACEHOLDERS[(string) $old] ) === true ? \Nino\Images::PLACEHOLDERS[(string) $old]['svg'] : \Nino\Images::read( $appData, (string) $old );

				if( $bytes !== false && \Nino\Images::restore( $appData, (string) $new, $bytes ) === true ) {
					$made[] = (string) $new;
					continue;
				}

				foreach( $target['slots'] as $uri => $slot )
					if( ( $slot['filename'] ?? null ) === $new )
						$target['slots'][$uri]['filename'] = null;
			}
		}

		/**
		 *	The picture files that stay below /images/ only as long as a slot of the current or of the last state has them: the file of a slot of the page that was kept
		 *	for one save and is in neither state now - the slot is gone, or it has another picture - is taken away, when it is a file of the slot's own name and no slot reads it
		 *
		 *	@param		array 		&$appData
		 *	@param		array|null								$older				The last state before this save
		 *	@param		array			$target				The state that is current now
		 *	@param		array			$state				The state that is the last now
		 *
		 *	@return 	void
		 */
		private static function _sweep( array &$appData, ?array $older, array $target, array $state ): void {

			$read = [];

			foreach( (array) ( $appData['/nino/html/images'] ?? [] ) as $slot )
				if( is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true )
					$read[$slot['filename']] = true;

			foreach( (array) ( $older['slots'] ?? [] ) as $uri => $slot ) {

				$file = $slot['filename'] ?? null;

				if( is_string( $file ) === true && ( $target['slots'][$uri]['filename'] ?? null ) !== $file && ( $state['slots'][$uri]['filename'] ?? null ) !== $file && str_starts_with( $file, ltrim( (string) $uri, '/' ). '.' ) === true && isset( $read[$file] ) === false )
					\Nino\Images::delete( $appData, $file );
			}
		}

		/**
		 *	Write a state in the order of a save - the last state first, then the files of the pictures of copies, then apply() - and put back what this save wrote
		 *	where a later step fails: the last state is the one that was still
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$state				The state the project holds, which is kept as the last one
		 *	@param		array			$target				The state to write
		 *	@param		array|null								$changes			As changes() answers it, or null for every difference
		 *	@param		array			$formats
		 *	@param		string|null							$seen					A sha256 of the template as it must still be, null for the one of $state
		 *	@param		array			$files				The files of the pictures of the copies, new file => the file it is made from
		 *
		 *	@return 	string|null								null where it was written, else 'keep', 'texts', 'slots', 'template' or 'changed'
		 */
		public static function commit( array &$appData, string $template, array $state, array $target, ?array $changes, array $formats = [], ?string $seen = null, array $files = [], int $level = \Nino\Modules\Builder::LEVEL_CONTENT ): ?string {

			// The writers of the Builder follow one another. The lock of config.php is what the routes of a page are written and taken away under (\Nino\Pages), and every write
			// of config.php lets it go: each step takes it again before it writes, and looks at the page then
			if( \Nino\Filesystem::lockFile( $appData, self::LOCK ) === false )
				return 'busy';

			try {
				return self::_hold( $appData, $template ) ?? self::_commit( $appData, $template, $state, $target, $changes, $formats, $seen, $files, $level );
			} finally {
				\Nino\Filesystem::unlockFile( $appData, self::LOCK );
				\Nino\Filesystem::unlockFile( $appData, '/config.php' );
			}
		}

		/**
		 *	commit() under the locks: the page is looked at before each write - a template that is not there or a page that no route answers is a page that was taken away -
		 *	and nothing is written for it
		 *
		 *	@return 	string|null								See commit(); 'gone' for a page that was taken away
		 */
		private static function _commit( array &$appData, string $template, array $state, array $target, ?array $changes, array $formats, ?string $seen, array $files, int $level ): ?string {

			$older = self::previous( $appData, $template );
			$was = self::_files( $appData, $template, [ self::PREVIOUS. '/'. $template. '.php' ] );
			$before = [];
			$wrote = [];
			$made = [];

			if( self::keep( $appData, $template, $state, $level ) === false ) {
				self::_back( $appData, $before, $wrote, $made );
				return 'keep';
			}

			self::_note( $appData, $template, $was, [], $wrote, $before );

			self::_copies( $appData, $target, $files, $made );

			$failed = self::apply( $appData, $template, $target, $state, $changes, $seen ?? hash( 'sha256', $state['source'] ), $formats, $wrote, $before );

			if( $failed !== null ) {
				self::_back( $appData, $before, $wrote, $made );
				return $failed;
			}

			self::_sweep( $appData, $older, $target, $state );
			\Nino\Modules\Builder\Pictures::sweep( $appData, $template );

			return null;
		}

		/**
		 *	Take the lock of config.php - the routes of a page are written and taken away under it - and look at the page: a write of a save is made under the lock, and only for
		 *	a page that is there
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *
		 *	@return 	string|null								null where the page is there and the lock is held; 'busy' where the lock cannot be had, 'gone' for a page that was taken away
		 */
		private static function _hold( array &$appData, string $template ): ?string {

			if( \Nino\Filesystem::lockFile( $appData, '/config.php' ) === false )
				return 'busy';

			return self::alive( $appData, $template ) === true ? null : 'gone';
		}

		/**
		 *	Whether a page is still there as the Builder opens it: the file of its template, and for a page a route that draws it - a frame has none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *
		 *	@return 	bool
		 */
		public static function alive( array &$appData, string $template ): bool {

			if( \Nino\Filesystem::fileExists( $appData, '/templates/'. $template. '.tpl' ) === false )
				return false;

			if( ( self::named( $template )['frame'] ?? true ) === true )
				return true;

			foreach( \Nino\Pages::all( $appData ) as $page )
				if( $page['template'] === $template )
					return true;

			return false;
		}


		/**
		 *	The line a place of a text is on, counted from 1
		 *
		 *	@param		string		$text
		 *	@param		int				$offset				In bytes
		 *
		 *	@return 	int
		 */
		public static function line( string $text, int $offset ): int {
			return 1 + substr_count( substr( $text, 0, max( 0, $offset ) ), "\n" );
		}

		/**
		 *	A word of a request as it may stand in a sentence or the log
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		public static function shown( string $text ): string {
			return mb_substr( (string) preg_replace( '/[\x00-\x1F\x7F]/', '?', $text ), 0, 60 );
		}
	}
}
