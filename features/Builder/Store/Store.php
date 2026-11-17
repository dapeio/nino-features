<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// What the Builder keeps and writes of a page: its template, the words of its namespace in every language and its picture slots - read together as one
	// state, stamped, written in a fixed order and kept one step back as the last state. The files are the project's own; nothing is written but the template
	// of the page or of the frame, the keys below /template/<template>/ and the slots below the same
	//
	//	the order of a save   the last state, the words, the slots, the template - and where one of the later steps fails, the state that was is put back
	class Store {

		// Where the last state of a template is kept: private/data/builder/previous/<template>.php
		public const string PREVIOUS = '/data/builder/previous';

		// The most keys and texts a request names, and the longest text of one
		public const int MAX_KEYS = 600;
		public const int MAX_LENGTH = 20000;

		// The frames of the site and what the Builder calls them
		public const array FRAMES = [ 'header' => 'frame-header', 'footer' => 'frame-footer' ];

		/**
		 *	What a name is, as the Builder may open it: a template of a page - page-<word> - or one of the two frames
		 *
		 *	@param		string		$name
		 *
		 *	@return 	array|null								[ 'name', 'frame' ]
		 */
		public static function named( string $name ): ?array {

			if( in_array( $name, self::FRAMES, true ) === true )
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
		 *	What is checked of a draft or a save, before anything is drawn or written: the tree is one the Builder takes, it is the old page with its blocks
		 *	moved, copied or left out, its fixed texts are texts, and the words are for keys of the namespace, in languages of the site, and kept for the keys a call still reads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$state				As state() answers it
		 *	@param		mixed			$tree					What the person sent
		 *	@param		mixed			$texts				key => language => text
		 *	@param		mixed			$copies				slot of a copy => the slot of the original it is made from
		 *
		 *	@return 	array											'source', 'tree', 'sources', 'formats', 'texts' (key => language => text as it is kept), 'slots' (the slots of the copies, uri => slot) - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function prepare( array &$appData, string $template, array $state, mixed $tree, mixed $texts, mixed $copies = [] ): array {

			$registry = \Nino\Components::components( $appData );
			$canonical = \Nino\Modules\Builder\Tree::canonical( $appData, $tree );

			if( $canonical === null )
				return [ 'failure' => [ 400, 'the tree is none the Builder takes', 'builder_tree', [], 'tree' ] ];

			[ $source, $nodes ] = $canonical;
			$old = \Nino\Modules\Builder\Tree::read( $appData, $state['source'] );
			$frame = self::named( $template )['frame'] ?? false;

			$taken = \Nino\Modules\Builder\Tree::taken( $old, $registry, array_merge( array_keys( $state['texts'] ), array_keys( $state['slots'] ) ) );

			if( ( $why = \Nino\Modules\Builder\Tree::refused( $old, $nodes, $registry, $frame, $taken ) ) !== null )
				return [ 'failure' => [ 403, 'the page may not be changed like that: '. $why, 'builder_refused_'. $why, [], 'tree' ] ];

			$nodes = self::_fixed( $nodes, $registry );

			if( $nodes === null )
				return [ 'failure' => [ 400, 'a fixed text is not one', 'builder_fixed', [ \Nino\Modules\Builder\Tree::MAX_TEXT ], 'tree' ] ];

			$source = \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) );

			if( is_array( $texts ) === false || count( $texts ) > self::MAX_KEYS )
				return [ 'failure' => [ 400, 'the texts are none', 'builder_texts', [], 'texts' ] ];

			$sources = \Nino\Modules\Builder\Tree::sources( $nodes, $registry, $template );
			$formats = [];

			foreach( $sources as $entry )
				if( $entry['editable'] === true && $entry['key'] !== '' )
					$formats[$entry['key']] ??= $entry['format'];

			$reads = array_flip( \Nino\Modules\Builder\Tree::readKeys( $nodes, $registry, $template, $source ) );
			$images = [];

			foreach( $sources as $entry )
				if( $entry['kind'] === 'image' && $entry['key'] !== '' )
					$images[$entry['key']] = true;
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

				if( isset( $formats[$key] ) === false )
					return [ 'failure' => [ 403, 'the key is not one of a text', 'builder_key', [], 'texts' ] ];

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

			return [ 'source' => $source, 'tree' => $nodes, 'sources' => $sources, 'formats' => $formats, 'texts' => $kept, 'raw' => $raw, 'reads' => array_keys( $reads ), 'images' => array_keys( $images ), 'slots' => $slots, 'shown' => $shown, 'files' => $files, 'registry' => $registry ];
		}

		/**
		 *	The fixed texts of a tree, each one a line of at most Tree::MAX_TEXT characters without a control character
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *
		 *	@return 	array|null								The nodes; null where a fixed text is none
		 */
		private static function _fixed( array $nodes, array $registry ): ?array {

			foreach( $nodes as $at => $node ) {

				if( isset( $node['n'] ) === false )
					continue;

				if( isset( $registry[$node['n']] ) === true && array_key_exists( 'text', (array) ( $node['a'] ?? [] ) ) === true ) {

					$text = $node['a']['text'];

					if( is_string( $text ) === false || mb_strlen( $text ) > \Nino\Modules\Builder\Tree::MAX_TEXT || preg_match( '/[\x00-\x1F\x7F]/', $text ) === 1 )
						return null;
				}

				if( is_array( $node['c'] ?? null ) === true && ( $inner = self::_fixed( $node['c'], $registry ) ) === null )
					return null;
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
		public static function keep( array &$appData, string $template, array $state ): bool {
			return \Nino\Filesystem::putFileContent( $appData, self::PREVIOUS. '/'. $template. '.php', [ 'saved' => time() ] + $state ) === true;
		}

		/**
		 *	Make the project hold a state: its words in every language it names, the keys of the namespace it does not have removed, its slots, its template. The
		 *	template is written last and only where it still is what the caller saw ($seen, a sha256 of its text, '' for no check)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$target				A state
		 *	@param		array			$now					The state that is replaced
		 *	@param		array|null								$changes			As changes() answers it, or null for every difference of the two states
		 *	@param		string		$seen
		 *	@param		array			$formats			key => format for the keys that are new
		 *
		 *	@return 	string|null								null where it was done, else 'texts', 'slots' or 'template' - the step that failed - or 'changed' (the template was written by somebody else meanwhile)
		 */
		public static function apply( array &$appData, string $template, array $target, array $now, ?array $changes, string $seen, array $formats = [], array &$wrote = [], array $before = [] ): ?string {

			if( $changes === null ) {

				$set = [];

				foreach( $target['texts'] as $key => $values )
					foreach( $values as $locale => $text )
						if( ( $now['texts'][$key][$locale] ?? null ) !== $text || isset( $now['texts'][$key] ) === false )
							$set[$key][$locale] = $text;

				$changes = [ 'set' => $set, 'drop' => array_values( array_diff( array_keys( $now['texts'] ), array_keys( $target['texts'] ) ) ), 'dropSlots' => array_values( array_diff( array_keys( $now['slots'] ), array_keys( $target['slots'] ) ) ) ];
			}

			// What a step wrote is noted as the bytes it left, so that a failure later puts back what this save wrote and nothing that was written by somebody else
			$note = function() use ( &$appData, $template, &$wrote, $before ): void {

				foreach( self::_files( $appData, $template ) as $path => $bytes )
					if( array_key_exists( $path, $before ) === true && $bytes !== $before[$path] && array_key_exists( $path, $wrote ) === false )
						$wrote[$path] = $bytes;
			};

			if( $changes['set'] !== [] || $changes['drop'] !== [] ) {

				if( \Nino\Text::put( $appData, $changes['set'], $changes['drop'], $formats ) === null ) {
					$note();
					return 'texts';
				}

				$note();
			}

			foreach( $target['slots'] as $uri => $slot )
				if( ( $now['slots'][$uri] ?? null ) !== $slot && \Nino\Images::putSlot( $appData, (string) $uri, $slot ) === false ) {
					$note();
					return 'slots';
				}

			foreach( $changes['dropSlots'] as $uri )
				if( isset( $target['slots'][$uri] ) === false && \Nino\Images::dropSlot( $appData, $uri ) === false ) {
					$note();
					return 'slots';
				}

			$note();

			if( $target['source'] === $now['source'] )
				return null;

			$gone = false;
			$ok = \Nino\Filesystem::mutate( $appData, '/templates/'. $template. '.tpl', static function( mixed $text ) use ( $target, $seen, &$gone ): ?string {

				if( $seen !== '' && hash_equals( $seen, hash( 'sha256', (string) $text ) ) === false ) {
					$gone = true;
					return null;
				}

				return $target['source'];
			}, '' );

			if( $ok === true )
				$note();

			return $ok === true ? null : ( $gone === true ? 'changed' : 'template' );
		}

		/**
		 *	The files a save may touch, by their virtual path: the template, config.php (the slots), every file of the words and their meta, and the last state
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *
		 *	@return 	array											virtual path => the bytes the file has, null for a file that is not there
		 */
		private static function _files( array &$appData, string $template ): array {

			$paths = [ '/templates/'. $template. '.tpl', '/config.php', self::PREVIOUS. '/'. $template. '.php' ];

			foreach( (array) glob( \Nino\Filesystem::path( $appData, '/text' ). '/*.php' ) as $file )
				$paths[] = '/text/'. basename( (string) $file );

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
		 *	@param		array			$files				new file => the file it is made from
		 *	@param		array			&$made				(reference) What was made
		 *
		 *	@return 	void
		 */
		private static function _copies( array &$appData, array &$target, array $files, array &$made ): void {

			foreach( $files as $new => $old ) {

				$bytes = \Nino\Images::read( $appData, (string) $old );

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
		 *	for one save and is in neither state now is taken away - when it is a file of the slot's own name and no slot reads it
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

				if( is_string( $file ) === true && isset( $target['slots'][$uri] ) === false && isset( $state['slots'][$uri] ) === false && str_starts_with( $file, ltrim( (string) $uri, '/' ). '.' ) === true && isset( $read[$file] ) === false )
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
		public static function commit( array &$appData, string $template, array $state, array $target, ?array $changes, array $formats = [], ?string $seen = null, array $files = [] ): ?string {

			$before = self::_files( $appData, $template );
			$older = self::previous( $appData, $template );
			$wrote = [];
			$made = [];

			if( self::keep( $appData, $template, $state ) === false ) {
				self::_back( $appData, $before, $wrote, $made );
				return 'keep';
			}

			$kept = self::PREVIOUS. '/'. $template. '.php';
			$real = \Nino\Filesystem::path( $appData, $kept );
			$wrote[$kept] = is_file( $real ) === true ? (string) file_get_contents( $real ) : '';

			self::_copies( $appData, $target, $files, $made );

			$failed = self::apply( $appData, $template, $target, $state, $changes, $seen ?? hash( 'sha256', $state['source'] ), $formats, $wrote, $before );

			if( $failed !== null ) {
				self::_back( $appData, $before, $wrote, $made );
				return $failed;
			}

			self::_sweep( $appData, $older, $target, $state );

			return null;
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
