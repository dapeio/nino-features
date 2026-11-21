<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Themes\Store	see _nino/Nino/Modules/Modules.php for the
 *												package-level docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The store: what the project has decided about its look, and the state before it
	 *
	 *										private/data/theme/
	 *											current/theme/            the theme the website shows, copied in the form of a package: theme.json, parts/<group>/part.json and part.css, theme.css
	 *											current/brand.json        the brand (M5), per value the project's own or the theme's
	 *											current/active.json       which theme the site shows (the one it was copied from) and what the stylesheet was built from
	 *											current/origin.json       where each part of the copy comes from - theme and version - while the state is not saved as a theme
	 *											current/variants.php      what the register is told of the theme
	 *											current/theme.css         the stylesheet, the source of private/assets/theme.css
	 *											previous/                 the last state, in the same shape: one generation
	 *
	 *										A change is made in a copy of current/ (building/), the stylesheet is built in it from the copy of the theme it holds - and from nothing
	 *										else: the website reads no folder of an own theme and no package - and then current/ becomes previous/ and the copy becomes current/ - so a
	 *										state is always whole, the one before it is kept, and restoring is swapping the two (twice is the state it was). A change that changes nothing
	 *										keeps the last state as it is. Everything runs under one lock.
	 *
	 *										The own themes are no part of a state: each is a folder in the form of a theme package, private/themes/<key>/ (see Mix::write()), which the
	 *										reader reads as it is. Changing one takes the lock of the state, so that a theme is not changed under another change; a folder that was
	 *										replaced is put aside until the change of the state holds and put back where it does not. A theme that is on the website is a copy in the state:
	 *										what is done to the folder of its own is seen by the website when the theme is applied - or saved, where it is the one that is on.
	 *
	 *										The compiler overwrites only what it wrote: a private/assets/theme.css that is not what the
	 *										compiler left - no head, or a head that does not match the text - is somebody's own file, and
	 *										a change that would replace it is refused until the caller says it may; the file is then kept
	 *										as the stylesheet of the last state.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Store {

		// The most own themes there are, and the keys of the files of the state
		public const int MAX_OWN = 50;

		// The longest file of the state that is read
		public const int MAX_FILE = 1048576;

		// What a last state says of itself in saved.json: the form of the states that hold the copy of the theme they were built from
		private const int FORMAT = 2;

		// The longest file of a state that is copied, and the files of a state beside the copy of its theme
		private const int STATE_FILE = 8388608;
		private const array FILES = [ 'active.json', 'brand.json', 'origin.json', 'saved.json', 'theme.css', 'variants.php' ];

		/**
		 *	The state as it stands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											'brand' => as Brand::read() answers it, 'active' => [ 'theme', 'built', 'feature', 'register', 'gone' ] or null,
		 *																'own' => key => the own theme, 'previous' => whether there is a last state, 'origin' => group => where the part of the copy comes from ([] where it is saved as a theme),
		 *																'held' => the theme.json of the copy of the theme the website shows, null where there is none
		 */
		public static function read( array &$appData ): array {

			$dir = \Nino\Modules\Themes::DIR;

			self::heal( $appData );

			$brand = Brand::read( $appData, self::_load( $appData, $dir. '/current/brand.json' ) );
			$active = self::_load( $appData, $dir. '/current/active.json' );
			$own = [];

			// The own themes are folders of the project, read as they are: what a person sees of one is what its parts are told from
			foreach( array_keys( Reader::own( $appData )['themes'] ) as $key )
				if( is_array( $mix = Mix::ofOwn( $appData, (string) $key, true ) ) === true )
					$own[(string) $key] = $mix;

			$origin = self::_origin( self::_load( $appData, $dir. '/current/origin.json' ) );

			return [
				'brand'		=> $brand,
				'active'	=> is_array( $active ) === true && is_string( $active['theme'] ?? null ) === true ? $active : null,
				'own'			=> $own,
				'previous'	=> self::_isState( $appData, $dir. '/previous' ),
				'origin'	=> $origin,
				'held'		=> self::_load( $appData, $dir. '/current/theme/theme.json' ),
			];
		}

		/**
		 *	The note of where the parts of the copy come from, as far as it is whole: a group (a slug) with the theme and the version it comes from, or the own theme. An entry that is
		 *	anything else - a null, a text, a name that is no slug, more than it says - is left out as if the note did not have it: the note is only shown, and is read from a file
		 *
		 *	@param		array|null								$note					What the file holds
		 *
		 *	@return 	array											group => [ 'theme' => key, 'version' => ... ] or [ 'own' => key ]
		 */
		private static function _origin( ?array $note ): array {

			$origin = [];
			$slug = static fn( mixed $value ): bool => is_string( $value ) === true && strlen( $value ) <= \Nino\Components::NAME_LENGTH && preg_match( \Nino\Components::NAME, $value ) === 1;

			foreach( $note ?? [] as $group => $entry ) {

				if( $slug( $group ) === false || is_array( $entry ) === false )
					continue;

				if( array_keys( $entry ) === [ 'own' ] && $slug( $entry['own'] ) === true )
					$origin[$group] = [ 'own' => $entry['own'] ];
				else if( array_keys( $entry ) === [ 'theme', 'version' ] && $slug( $entry['theme'] ) === true && is_string( $entry['version'] ) === true && ( $entry['version'] === '' || preg_match( '/^\d{1,4}\.\d{1,4}\.\d{1,4}(?:-[A-Za-z0-9.]{1,20})?$/D', $entry['version'] ) === 1 ) )
					$origin[$group] = [ 'theme' => $entry['theme'], 'version' => $entry['version'] ];
			}

			return $origin;
		}

		/**
		 *	What the state is, as one stamp: a request that is about the state as a whole - a restore - says which one it was made on, so that a
		 *	save that came between is not put aside unseen
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										'' where there is no state
		 */
		public static function stamp( array &$appData ): string {

			$state = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/current' );

			return is_dir( $state ) === true ? self::_digest( $state ) : '';
		}

		/**
		 *	What the last state is, for a person to read: when it was saved and which theme the website showed in it. A state that was saved before
		 *	the time was kept says nothing of it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								[ 'at' => unix time, 'theme' => key or '', 'name' => the name of the theme, a string or a locale => string map, '' for none ], null where there is none to tell of
		 */
		public static function last( array &$appData ): ?array {

			$dir = \Nino\Modules\Themes::DIR. '/previous';
			$saved = self::_load( $appData, $dir. '/saved.json' );

			if( $saved === null || is_int( $saved['at'] ?? null ) === false || is_string( $saved['theme'] ?? null ) === false )
				return null;

			$key = $saved['theme'];
			$name = '';

			// The name of the theme is the one in the copy that state holds: the theme may be gone or have another name since
			if( $key !== '' )
				$name = self::_load( $appData, $dir. '/theme/theme.json' )['name'] ?? ( str_starts_with( $key, 'own:' ) === true ? substr( $key, 4 ) : $key );

			return [ 'at' => $saved['at'], 'theme' => $key, 'name' => $name ];
		}

		/**
		 *	Whether a directory is a state of the website: it is there, and is not one of the form the states had before they held the theme they were built from - which
		 *	has an own/ folder, or a saved.json that says no format - and is left as it is, unread
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$virtual			The virtual path of the directory
		 *
		 *	@return 	bool
		 */
		private static function _isState( array &$appData, string $virtual ): bool {

			$path = \Nino\Filesystem::path( $appData, $virtual );

			if( is_dir( $path ) === false )
				return false;

			if( is_dir( $path. '/own' ) === true || is_dir( $path. '/copies' ) === true )
				return false;

			$saved = self::_load( $appData, $virtual. '/saved.json' );

			return $saved === null || ( $saved['format'] ?? 0 ) === self::FORMAT;
		}

		/**
		 *	The stylesheet of the project as a file of the disk: the web server may not be allowed to read what somebody else put there, and the
		 *	kernel makes a warning of a read that fails
		 *
		 *	@param		string		$file					The real path
		 *
		 *	@return 	string|false|null					The text, null where there is no file, false where it cannot be read
		 */
		public static function sheet( string $file ): string|false|null {

			if( is_file( $file ) === false )
				return null;

			// Whether the file may be read is the answer of the system to the read: a failed read is caught here, where the kernel would make a 500
			// of its warning
			return self::quietRead( $file );
		}

		/**
		 *	Whether a file can be opened for reading by the one who runs this request: the answer of the system to the open, which is_readable() does not
		 *	always give where the real and the effective user are two
		 *
		 *	@param		string		$file					The real path
		 *
		 *	@return 	bool
		 */
		public static function readable( string $file ): bool {

			set_error_handler( static fn(): bool => true );

			try {
				$handle = is_file( $file ) === true ? fopen( $file, 'rb' ) : false;
			}
			finally {
				restore_error_handler();
			}

			if( $handle === false )
				return false;

			fclose( $handle );

			return true;
		}

		/**
		 *	A file read with no warning: false where the read fails
		 *
		 *	@param		string		$file					The real path
		 *
		 *	@return 	string|false
		 */
		public static function quietRead( string $file ): string|false {

			set_error_handler( static fn(): bool => true );

			try {
				return file_get_contents( $file );
			}
			finally {
				restore_error_handler();
			}
		}

		/**
		 *	A JSON file of the state, read from the disk as it stands: the state is swapped by renaming directories, which the
		 *	cache of the filesystem keyed by name and modification time cannot tell
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$virtual			The virtual path
		 *
		 *	@return 	array|null								null where there is no file or it is no JSON map
		 */
		private static function _load( array &$appData, string $virtual ): ?array {

			$file = \Nino\Filesystem::path( $appData, $virtual );
			$text = is_file( $file ) === true && filesize( $file ) <= self::MAX_FILE ? self::quietRead( $file ) : false;
			$json = $text === false ? null : json_decode( $text, true, 32 );

			return is_array( $json ) === true ? $json : null;
		}

		/**
		 *	What is held of the state for the rest of this request is let go, and the files that php may have compiled are told to
		 *	read anew: the directories were swapped under their names
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		private static function _forget( array &$appData ): void {

			unset( $appData['./nino/themes/variants'], $appData['./nino/themes/read'], $appData['./nino/themes/own'] );

			foreach( [ 'current', 'previous' ] as $folder ) {

				$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. $folder. '/variants.php' );

				if( function_exists( 'opcache_invalidate' ) === true && is_file( $file ) === true )
					opcache_invalidate( $file, true );
			}
		}

		/**
		 *	Change the state: $change writes into a copy of it, the stylesheet is built from the copy, and the copy becomes the state
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		callable	$change				( array &$appData, string $next ): ?array - $next is the virtual path of the copy; answers null, or the failure as Http::fail() takes it
		 *	@param		bool			$force				Whether a stylesheet that is not the compiler's may be replaced
		 *
		 *	@return 	array|null								null where it is done, else [ status, message, code, params, field ]
		 */
		public static function commit( array &$appData, callable $change, bool $force = false ): ?array {

			if( \Nino\Filesystem::lockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock', false ) === false )
				return [ 409, 'the themes are being changed by another request', 'themes_busy', [], '' ];

			try {
				self::_heal( $appData );
				Mix::heal( $appData );

				$failure = self::_commit( $appData, $change, $force );

				// What the change did to the folder of an own theme is settled here, with the lock held: no request that is made whole in between sees the folder half way
				// A break after the new state was put in place - its stylesheet could not be written - leaves a state that holds the change: the folder goes with it
				$id = Mix::pending( $appData );
				Mix::settle( $appData, $failure === null || ( $id !== null && self::holds( $appData, $id ) === true ) );

				return $failure;
			}
			finally {
				self::_forget( $appData );
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock' );
			}
		}

		/**
		 *	Swap the state and the last state, and the stylesheet with them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		bool			$force				Whether a stylesheet that is not the compiler's may be replaced
		 *
		 *	@return 	array|null								null where it is done, else [ status, message, code, params, field ]
		 */
		public static function restore( array &$appData, bool $force = false, ?string $base = null ): ?array {

			if( \Nino\Filesystem::lockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock', false ) === false )
				return [ 409, 'the themes are being changed by another request', 'themes_busy', [], '' ];

			try {

				self::_heal( $appData );

				// The state the one who asks has seen, looked at with the lock held: a save that came between is not put aside unseen
				if( $base !== null && hash_equals( self::stamp( $appData ), $base ) === false )
					return [ 409, 'the themes were changed since they were opened', 'themes_state', [], '' ];

				$dir = \Nino\Modules\Themes::DIR;
				$state = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/current' );
				$previous = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/previous' );
				$swap = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/swap' );

				if( self::_isState( $appData, $dir. '/previous' ) === false )
					return [ 409, 'there is no last state', 'themes_nothing', [], '' ];

				if( ( $stuck = self::_leftover( $appData, [ 'swap' ] ) ) !== null )
					return $stuck;

				// The stylesheet of the state that comes back is built anew from the theme it holds, as every change builds it: a stylesheet that was made
				// before a rule was and says what the rule refuses is not put on the website
				$failure = self::_renew( $appData );

				if( $failure !== null )
					return $failure;

				$foreign = self::_foreign( $appData, $state. '/theme.css', $force );

				if( is_array( $foreign ) === true )
					return $foreign;

				if( self::_replaceable( $appData, $previous, $foreign ) === false )
					return [ 409, 'the stylesheet of the project cannot be replaced', 'themes_locked', [], '' ];

				if( is_dir( $state ) === false )
					@mkdir( $state, 0755, true );

				// Three steps, and a break between any two of them is mended by _heal(): the state is put aside, the last state takes
				// its place, and the stylesheet follows before the one put aside is the last state
				if( @rename( $state, $swap ) === false || @rename( $previous, $state ) === false )
					return [ 500, 'the states could not be swapped', '', [], '' ];

				$failure = self::_sync( $appData, $state, $swap, $foreign );

				if( @rename( $swap, $previous ) === false )
					return [ 500, 'the states could not be swapped', '', [], '' ];

				return $failure;
			}
			finally {
				self::_forget( $appData );
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock' );
			}
		}

		/**
		 *	The last state, built anew with the lock held: a copy of it is built from the theme it holds, and where that is not what the state holds - another
		 *	stylesheet, other variants, the stamps of the register - the copy is the last state
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								null where it is done, else [ status, message, code, params, field ]
		 */
		private static function _renew( array &$appData ): ?array {

			$dir = \Nino\Modules\Themes::DIR;
			$previous = \Nino\Filesystem::path( $appData, $dir. '/previous' );
			$building = \Nino\Filesystem::path( $appData, $dir. '/building' );
			$gone = \Nino\Filesystem::path( $appData, $dir. '/gone' );

			// A state that shows no theme has nothing to build - and its theme.css, where it has one, is the stylesheet that somebody else wrote, kept as it was
			if( is_string( self::_load( $appData, $dir. '/previous/active.json' )['theme'] ?? null ) === false )
				return null;

			if( ( $stuck = self::_leftover( $appData, [ 'building', 'gone' ] ) ) !== null )
				return $stuck;

			if( self::_copy( $previous, $building ) === false ) {
				self::_clear( $building );
				return [ 500, 'the last state could not be copied', '', [], '' ];
			}

			$failure = self::_build( $appData, $dir. '/building' );

			if( $failure !== null ) {
				self::_clear( $building );
				return $failure;
			}

			if( self::_digest( $previous ) === self::_digest( $building ) ) {
				self::_clear( $building );
				return null;
			}

			// The last state is put aside and not removed: a break between the two steps is mended by _heal()
			if( @rename( $previous, $gone ) === false || @rename( $building, $previous ) === false )
				return [ 500, 'the states could not be swapped', '', [], '' ];

			self::_clear( $gone );

			return null;
		}

		/**
		 *	Make whole what a change that was broken off left: a request that was killed between the renames of the directories leaves
		 *	a state that is not there, or two that are. With the lock held, here, and for a request that reads and finds no state, in
		 *	heal(). The new state is complete where the directory next is there - it is only given that name when it is - and is taken;
		 *	a swap that was begun is finished or, where the state is not yet in its place, taken back; the last state is never removed
		 *	while there is no state, and what is left over is removed. A state that was healed gets its stylesheet written
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool											Whether something was found to mend
		 */
		private static function _heal( array &$appData ): bool {

			$base = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR );
			[ $state, $previous, $next, $gone, $swap, $building ] = array_map( static fn( string $name ): string => $base. '/'. $name, [ 'current', 'previous', 'next', 'gone', 'swap', 'building' ] );
			$mended = false;

			if( is_dir( $base ) === false )
				return false;

			if( is_dir( $state ) === false ) {

				// A change that was past its second rename: the state it made is whole, and it is the state
				if( is_dir( $next ) === true )
					$mended = @rename( $next, $state );
				// A swap that was begun and not carried on: the state put aside is the state once more
				else if( is_dir( $swap ) === true )
					$mended = @rename( $swap, $state );
				// Nothing else of it is left: the last state is the state, and nothing is lost that was not lost already
				else if( self::_isState( $appData, \Nino\Modules\Themes::DIR. '/previous' ) === true )
					$mended = @rename( $previous, $state );
			}

			if( is_dir( $state ) === true ) {

				// The swap that was carried on to its second step: what was put aside is the last state
				if( is_dir( $swap ) === true ) {

					if( is_dir( $previous ) === false )
						@rename( $swap, $previous );
					else
						self::_clear( $swap );

					$mended = true;
				}

				// A change that was broken off after the last state was put aside, before the state was: the last state is back
				if( is_dir( $previous ) === false && is_dir( $gone ) === true ) {
					@rename( $gone, $previous );
					$mended = true;
				}
			}

			if( is_dir( $state ) === true )
				foreach( [ $gone, $building, $next ] as $left )
					if( is_dir( $left ) === true ) {
						self::_clear( $left );
						$mended = true;
					}

			if( $mended === true && is_dir( $state ) === true && is_array( $foreign = self::_foreign( $appData, $state. '/theme.css', false ) ) === false )
				self::_sync( $appData, $state, $previous, $foreign );

			return $mended;
		}

		/**
		 *	_heal() for a request that is not a change: where the state is not there and the themes are - or the last state is put aside and not in its place - it takes the lock - not waiting,
		 *	whoever holds it is making the change - and mends. The request that finds it is the first one after the break
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool											Whether it mended something
		 */
		public static function heal( array &$appData ): bool {

			// The states first: whether a change of a folder holds is told by the state that is in place
			$mended = self::_healStates( $appData );

			return self::_healOwn( $appData ) || $mended;
		}

		/**
		 *	The states of heal(): where the state is not there and the themes are - or the last state is put aside and not in its place - they are made whole, with the lock taken
		 *	and not waited for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool											Whether it mended something
		 */
		private static function _healStates( array &$appData ): bool {

			$base = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR );

			if( is_dir( $base ) === false || ( is_dir( $base. '/current' ) === true && is_dir( $base. '/gone' ) === false && is_dir( $base. '/swap' ) === false )
				|| \Nino\Filesystem::lockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock', false ) === false )
				return false;

			try {
				$mended = self::_heal( $appData );
				self::_forget( $appData );

				return $mended;
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock' );
			}
		}

		/**
		 *	The own themes: where a change that was broken off left a folder that was being written, a folder that was put aside or a folder that was being taken away, it is made whole - not
		 *	waiting for the lock, which whoever makes the change holds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool											Whether there was something to mend
		 */
		private static function _healOwn( array &$appData ): bool {


			if( Mix::leftovers( $appData ) === [] || \Nino\Filesystem::lockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock', false ) === false )
				return false;

			try {
				Mix::heal( $appData );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock' );
			}

			return true;
		}

		/**
		 *	The commit, with the lock held
		 *
		 *	@return 	array|null
		 */
		private static function _commit( array &$appData, callable $change, bool $force ): ?array {

			$dir = \Nino\Modules\Themes::DIR;
			$state = \Nino\Filesystem::path( $appData, $dir. '/current' );
			$previous = \Nino\Filesystem::path( $appData, $dir. '/previous' );
			$next = \Nino\Filesystem::path( $appData, $dir. '/next' );
			$gone = \Nino\Filesystem::path( $appData, $dir. '/gone' );
			$building = \Nino\Filesystem::path( $appData, $dir. '/building' );

			// What a break left is mended and what is left over is gone: nothing is made on a state that is not whole
			if( ( $stuck = self::_leftover( $appData, [ 'building', 'next', 'gone' ] ) ) !== null )
				return $stuck;

			if( is_dir( $state ) === true ) {

				if( self::_copy( $state, $building ) === false ) {
					self::_clear( $building );
					return [ 500, 'the state could not be copied', '', [], '' ];
				}
			}
			else
				@mkdir( $building, 0755, true );

			$failure = $change( $appData, $dir. '/building' );

			if( $failure === null )
				$failure = self::_build( $appData, $dir. '/building' );

			if( $failure !== null ) {
				self::_clear( $building );
				return $failure;
			}

			// The same state as before: nothing is moved, and the last state stays the last
			if( is_dir( $state ) === true && self::_digest( $state ) === self::_digest( $building ) ) {
				self::_clear( $building );
				return self::_sync( $appData, $state, $previous, self::_foreign( $appData, $state. '/theme.css', $force ) );
			}

			$foreign = is_file( $building. '/theme.css' ) === true ? self::_foreign( $appData, $building. '/theme.css', $force ) : null;

			if( is_array( $foreign ) === true ) {
				self::_clear( $building );
				return $foreign;
			}

			// A stylesheet that cannot be put in place is found out before anything is moved: a state that is written and said not to be saved is a
			// state the person does not know they have
			if( self::_replaceable( $appData, $building, $foreign ) === false ) {
				self::_clear( $building );
				return [ 409, 'the stylesheet of the project cannot be replaced', 'themes_locked', [], '' ];
			}

			// When it was saved and what the website showed, for the one who reads what the last state is; it is no part of what the state says, so it
			// is written after the states were compared
			// The change of a folder of an own theme that goes with this state - the number of its journal - is written into the state itself: the state that has it is the state
			// that holds the change, and that is how a request that is broken off is told, by the next one, which way the folder goes (Mix::heal())
			$saved = [ 'at' => time(), 'theme' => (string) ( self::_load( $appData, $dir. '/building/active.json' )['theme'] ?? '' ), 'format' => self::FORMAT ];

			if( ( $change = Mix::pending( $appData ) ) !== null )
				$saved['change'] = $change;

			\Nino\Filesystem::putFileContent( $appData, $dir. '/building/saved.json', $saved );

			// The new state is whole from the day it is called next: that name is what _heal() takes it by
			if( @rename( $building, $next ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			if( is_dir( $state ) === false )
				@mkdir( $state, 0755, true );

			// A last state of the days before the states held their theme is not one: it is put aside as it is, unread, and is none of the states
			if( is_dir( $previous ) === true && self::_isState( $appData, $dir. '/previous' ) === false ) {

				$aside = \Nino\Filesystem::path( $appData, $dir. '/legacy-previous' );

				if( file_exists( $aside ) === true )
					$aside .= '-'. time();

				if( @rename( $previous, $aside ) === false )
					return [ 500, 'the state could not be saved', '', [], '' ];
			}

			// The last state is put aside and not removed: while there is no state, it is what a break would need
			if( is_dir( $previous ) === true && @rename( $previous, $gone ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			if( @rename( $state, $previous ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			// The stylesheet before the new state has its name, so that a break after it leaves a state and a stylesheet that belong together
			$failure = self::_sync( $appData, $next, $previous, $foreign );

			if( @rename( $next, $state ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			if( is_dir( $gone ) === true )
				self::_clear( $gone );

			return $failure;
		}

		/**
		 *	Build the stylesheet and the variants of the active theme in a copy of the state, and keep a copy of each part it uses
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the copy
		 *
		 *	@return 	array|null								null where it is built or nothing is active
		 */
		private static function _build( array &$appData, string $next ): ?array {

			$active = self::_load( $appData, $next. '/active.json' );

			if( is_array( $active ) === false || is_string( $active['theme'] ?? null ) === false ) {

				foreach( [ 'theme.css', 'variants.php' ] as $file )
					@unlink( \Nino\Filesystem::path( $appData, $next. '/'. $file ) );

				return null;
			}

			// The website is built from the copy of its theme that the state holds, and from nothing else: no folder of an own theme, no package. A state is files, and
			// what is in files is checked as what is posted is
			$folder = \Nino\Filesystem::path( $appData, $next. '/theme' );
			$brand = Brand::read( $appData, self::_load( $appData, $next. '/brand.json' ) );
			$built = Reader::using( $appData, $folder, static function( array|string $theme ) use ( &$appData, $active, $brand ): array {

				if( is_string( $theme ) === true )
					return [ 'failure' => [ 409, 'the state holds no theme: '. $theme, 'themes_unknown', [ (string) $active['theme'] ], '' ] ];

				$mix = Mix::clean( $appData, Mix::ofCurrent( $appData, $theme ), false );

				if( is_string( $mix ) === true )
					return [ 'failure' => [ 409, 'the mix of the active theme is refused: '. $mix, 'themes_mix', [ $mix, Mix::where( $mix ) ], '' ] ];

				return [ 'built' => Compiler::compile( $appData, $mix, $brand ) ];
			} );

			if( isset( $built['failure'] ) === true )
				return $built['failure'];

			$built = $built['built'];

			$active['built'] = Compiler::stamp( $built['css'] );
			$active['gone'] = $built['gone'];
			$active['feature'] = (string) ( \Nino\Features::get( $appData, 'themes' )['version'] ?? '' );
			$active['register'] = \Nino\Components::version( $appData );

			if( \Nino\Filesystem::putFileContent( $appData, $next. '/theme.css', $built['css'] ) === false
				|| \Nino\Filesystem::putFileContent( $appData, $next. '/variants.php', $built['variants'] ) === false
				|| \Nino\Filesystem::putFileContent( $appData, $next. '/active.json', $active ) === false )
				return [ 500, 'the stylesheet could not be written', '', [], '' ];

			return null;
		}

		/**
		 *	Whether the stylesheet of the project may be replaced: it is not there, the compiler wrote it, or it is what the new state
		 *	says anyway - or the caller said it may be
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The real path of the stylesheet the state is going to have, or of the state that is
		 *	@param		bool			$force
		 *
		 *	@return 	array|string|null					The failure; the text of the file that is replaced against the caller's word; null where it is the compiler's or there is none
		 */
		private static function _foreign( array &$appData, string $next, bool $force ): array|string|null {

			$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::SHEET );
			$text = self::sheet( $file );

			if( $text === null || ( $text !== false && ( Compiler::isOwn( $text ) === true || ( self::sheet( $next ) === $text ) ) ) )
				return null;

			// A file that cannot be read is somebody else's, and what is in it cannot be kept as the last state's: the text of it is none
			if( $force === true )
				return $text === false ? '' : $text;

			return $text === false ? [ 409, 'the stylesheet of the project cannot be read, and what it holds cannot be kept as the last state', 'themes_unreadable', [], '' ] : [ 409, 'the stylesheet of the project is not the one this feature wrote', 'themes_foreign', [], '' ];
		}

		/**
		 *	Make the stylesheet of the project what the state says: its theme.css, or none. A stylesheet that was not the compiler's
		 *	and is replaced is kept as the last state's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$state				The real path of the state
		 *	@param		string		$previous			The real path of the last state
		 *	@param		array|string|null	$foreign		What _foreign() answered: the text of the file that goes, or null
		 *
		 *	@return 	array|null
		 */
		private static function _sync( array &$appData, string $state, string $previous, array|string|null $foreign ): ?array {

			if( is_array( $foreign ) === true )
				return $foreign;

			$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::SHEET );

			if( is_string( $foreign ) === true && $foreign !== '' && is_dir( $previous ) === true )
				file_put_contents( $previous. '/theme.css', $foreign );

			if( is_file( $state. '/theme.css' ) === true ) {

				$text = (string) file_get_contents( $state. '/theme.css' );

				// A file that already holds the text is left as it is: its time is the stamp of the bundle, and the browsers of all visitors fetch
				// the stylesheet anew when the stamp moves
				if( self::sheet( $file ) === $text )
					return null;

				// A file that is put in place keeps the rights of the one it replaces - a file that could not be read stays one - so it is opened for reading, and
				// one that is not the text, or still cannot be read, is not what the website is to have: said, and not taken for done
				if( \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Themes::SHEET, $text ) === false )
					return [ 409, 'the stylesheet of the project cannot be replaced', 'themes_locked', [], '' ];

				if( self::sheet( $file ) !== $text ) {
					@chmod( $file, 0666 & ~umask() );

					if( self::sheet( $file ) !== $text )
						return [ 409, 'the stylesheet of the project cannot be replaced', 'themes_locked', [], '' ];
				}
			}
			else if( is_file( $file ) === true && ( $foreign !== null || Compiler::isOwn( (string) self::sheet( $file ) ) === true ) )
				unlink( $file );

			return null;
		}

		/**
		 *	Whether _sync() can do what the state asks of the stylesheet of the project: a file is put in place by a temporary file written next to it
		 *	and renamed over it, so what decides is the directory - and the same is asked where the file is to go. Nothing is changed by asking
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$state				The real path of the state that is to be the state
		 *	@param		array|string|null	$foreign		What _foreign() answered
		 *
		 *	@return 	bool
		 */
		private static function _replaceable( array &$appData, string $state, array|string|null $foreign ): bool {

			$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::SHEET );

			// Nothing is written and nothing removed: the file is left as it is
			if( is_file( $state. '/theme.css' ) === false && ( is_file( $file ) === false || ( $foreign === null && Compiler::isOwn( (string) self::sheet( $file ) ) === false ) ) )
				return true;

			$dir = dirname( $file );

			// A folder that is not there yet is made by the one who writes: the nearest one that is there is what has to be written in
			while( is_dir( $dir ) === false && dirname( $dir ) !== $dir )
				$dir = dirname( $dir );

			// By writing a file there and taking it away, which is what the replacement does: is_writable() asks the real user and not the one the process
			// runs as, and the one place that says it for certain is the open
			$probe = $dir. '/.themes-'. bin2hex( random_bytes( 6 ) ). '.tmp';
			$handle = @fopen( $probe, 'xb' );

			if( $handle === false )
				return false;

			fclose( $handle );
			@unlink( $probe );

			return true;
		}

		/**
		 *	A directory of the states taken away, links and all: a link - to a file or to a folder, at any depth - is removed as the link it is, with unlink(), and is never entered, so that what it
		 *	points at is not touched; what is left is removed after. The directories of the states are the store's, but a link can be put into them by hand, and removeDir() of the kernel leaves a link
		 *	to a folder where it is - which a directory that is then not empty keeps from being renamed over
		 *
		 *	@param		string		$path					The real path
		 *	@param		int				$depth				How deep it has gone: the states are shallow, and a directory that is deeper than any of them is left
		 *
		 *	@return 	bool											Whether nothing of it is left
		 */
		private static function _clear( string $path, int $depth = 0 ): bool {

			if( is_link( $path ) === true || is_file( $path ) === true )
				return @unlink( $path );

			if( is_dir( $path ) === false )
				return true;

			if( $depth > 16 )
				return false;

			$done = true;

			foreach( (array) @scandir( $path ) as $entry )
				if( $entry !== '.' && $entry !== '..' )
					$done = self::_clear( $path. '/'. $entry, $depth + 1 ) && $done;

			return @rmdir( $path ) && $done;
		}

		/**
		 *	What an earlier change left in the directories a change is made in - the one it builds in, the one it is called when it is whole, the one the last state is put aside in - taken away
		 *	before a change goes on: a change is never made over what is left of another
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$names				The names of the directories
		 *
		 *	@return 	array|null								null where nothing is left; else the failure: a directory that cannot be taken away is said, and the change is not made
		 */
		private static function _leftover( array &$appData, array $names ): ?array {

			foreach( $names as $name ) {

				$path = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. $name );

				if( file_exists( $path ) === true || is_link( $path ) === true )
					self::_clear( $path );

				if( file_exists( $path ) === true || is_link( $path ) === true )
					return [ 409, 'a folder of an earlier change is left and cannot be taken away: '. $name, 'themes_stuck', [ $name ], '' ];
			}

			return null;
		}

		/**
		 *	Whether the state that is in place holds the change of a folder: its saved.json has the number of the change. The change is what the state was made with - a request that
		 *	is broken off before the state is in place leaves the state that was, which has no such number
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id					The number of the change, as the journal has it
		 *
		 *	@return 	bool
		 */
		public static function holds( array &$appData, string $id ): bool {

			$dir = \Nino\Modules\Themes::DIR;

			// The state that was made is in place, or - the request was broken off between the two renames - is the one that is called next while none is in place
			$where = is_dir( \Nino\Filesystem::path( $appData, $dir. '/current' ) ) === true ? '/current' : '/next';

			return ( self::_load( $appData, $dir. $where. '/saved.json' )['change'] ?? null ) === $id;
		}

		/**
		 *	A state copied: the files a state has, and the copy of the theme it holds as a package has its files - one source for both, the positive list of Mix::copy(). Files only: a
		 *	link is not followed and is not copied, a file that a state does not have is not taken, whatever it is called; a state whose copy of the theme is a link has no copy
		 *	of the theme - it is a state that holds no theme to read, which a change that needs it refuses (themes_unknown) as it refuses a state whose copy is gone
		 *
		 *	@param		string		$from					The real path of the state
		 *	@param		string		$to						The real path of the copy, which is made
		 *
		 *	@return 	bool											Whether it was copied
		 */
		private static function _copy( string $from, string $to ): bool {

			if( is_link( $from ) === true || is_dir( $from ) === false || ( is_dir( $to ) === false && @mkdir( $to, 0755, true ) === false ) )
				return false;

			foreach( self::FILES as $file ) {

				if( is_link( $from. '/'. $file ) === true || is_file( $from. '/'. $file ) === false || filesize( $from. '/'. $file ) > self::STATE_FILE )
					continue;

				$text = self::readable( $from. '/'. $file ) === true ? self::quietRead( $from. '/'. $file ) : false;

				if( $text === false || @file_put_contents( $to. '/'. $file, $text ) === false )
					return false;
			}

			return is_link( $from. '/theme' ) === true || is_dir( $from. '/theme' ) === false || Mix::copy( $from. '/theme', $to. '/theme' );
		}

		/**
		 *	What a directory holds, as one hash: every file, by its path in it and its bytes
		 *
		 *	@param		string		$dir
		 *
		 *	@return 	string
		 */
		private static function _digest( string $dir ): string {

			$hashes = [];
			$walk = static function( string $path, string $below ) use ( &$walk, &$hashes ): void {

				foreach( (array) scandir( $path ) as $entry ) {

					if( $entry === '.' || $entry === '..' )
						continue;

					if( is_dir( $path. '/'. $entry ) === true )
						$walk( $path. '/'. $entry, $below. $entry. '/' );
					else
						$hashes[] = $below. $entry. ':'. ( self::readable( $path. '/'. $entry ) === true ? sha1_file( $path. '/'. $entry ) : '-' );
				}
			};

			$walk( $dir, '' );
			sort( $hashes );

			return sha1( implode( "\n", $hashes ) );
		}
	}
}
