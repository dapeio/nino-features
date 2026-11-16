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
	 *	Modules\Themes		The store: what the project has decided about its look, the state before it, and the copy of every part in use
	 *
	 *										private/data/theme/
	 *											state/brand.json          the brand (M5), per value the project's own or the theme's
	 *											state/active.json         which theme the site shows and what the stylesheet was built from
	 *											state/own/<key>.json      an own theme: a name, the mix and the css of the developer
	 *											state/variants.php        what the register is told of the active mix
	 *											state/theme.css           the stylesheet, the source of private/assets/theme.css
	 *											previous/                 the last state, in the same shape: one generation
	 *											copies/<key>@<version>/   theme.json and the parts of every theme in use, only ever added to
	 *
	 *										A change is made in a copy of state/ (next/), the stylesheet is built in it from what it says,
	 *										and then state/ becomes previous/ and next/ becomes state/ - so a state is always whole, the one
	 *										before it is kept, and restoring is swapping the two (twice is the state it was). A change that
	 *										changes nothing keeps the last state as it is. Everything runs under one lock.
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

		/**
		 *	The state as it stands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											'brand' => as Brand::clean() answers it, 'active' => [ 'theme', 'built', 'feature', 'register' ] or null,
		 *																'own' => key => the own theme, 'previous' => whether there is a last state
		 */
		public static function read( array &$appData ): array {

			$dir = \Nino\Modules\Themes::DIR;

			self::heal( $appData );

			$brand = Brand::clean( $appData, self::_load( $appData, $dir. '/state/brand.json' ) ?? [] );
			$active = self::_load( $appData, $dir. '/state/active.json' );
			$own = [];

			foreach( self::_own( $appData, $dir. '/state' ) as $key ) {

				$mix = self::_load( $appData, $dir. '/state/own/'. $key. '.json' );

				if( is_array( $mix ) === true )
					$own[$key] = $mix;
			}

			return [
				'brand'		=> is_array( $brand ) === true ? $brand : Brand::clean( $appData, [] ),
				'active'	=> is_array( $active ) === true && is_string( $active['theme'] ?? null ) === true ? $active : null,
				'own'			=> $own,
				'previous'	=> is_dir( \Nino\Filesystem::path( $appData, $dir. '/previous' ) ),
			];
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
			$text = is_file( $file ) === true && filesize( $file ) <= self::MAX_FILE ? file_get_contents( $file ) : false;
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

			unset( $appData['./nino/themes/variants'], $appData['./nino/themes/read'] );

			foreach( [ 'state', 'previous' ] as $folder ) {

				$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/'. $folder. '/variants.php' );

				if( function_exists( 'opcache_invalidate' ) === true && is_file( $file ) === true )
					opcache_invalidate( $file, true );
			}
		}

		/**
		 *	The keys of the own themes a state directory holds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$state				The virtual path of the directory
		 *
		 *	@return 	array
		 */
		private static function _own( array &$appData, string $state ): array {

			$keys = [];

			foreach( (array) glob( \Nino\Filesystem::path( $appData, $state. '/own' ). '/*.json' ) as $file )
				if( preg_match( \Nino\Components::NAME, $key = basename( (string) $file, '.json' ) ) === 1 && strlen( $key ) <= \Nino\Components::NAME_LENGTH )
					$keys[] = $key;

			return $keys;
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

				return self::_commit( $appData, $change, $force );
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
		public static function restore( array &$appData, bool $force = false ): ?array {

			if( \Nino\Filesystem::lockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock', false ) === false )
				return [ 409, 'the themes are being changed by another request', 'themes_busy', [], '' ];

			try {

				self::_heal( $appData );

				$state = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/state' );
				$previous = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/previous' );
				$swap = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR. '/swap' );

				if( is_dir( $previous ) === false )
					return [ 409, 'there is no last state', 'themes_nothing', [], '' ];

				$foreign = self::_foreign( $appData, $state. '/theme.css', $force );

				if( is_array( $foreign ) === true )
					return $foreign;

				if( is_dir( $state ) === false )
					@mkdir( $state, 0755, true );

				// Three steps, and a break between any two of them is mended by _heal(): the state is put aside, the last state takes
				// its place, and the stylesheet follows before the one put aside is the last state
				if( rename( $state, $swap ) === false || rename( $previous, $state ) === false )
					return [ 500, 'the states could not be swapped', '', [], '' ];

				$failure = self::_sync( $appData, $state, $swap, $foreign );

				if( rename( $swap, $previous ) === false )
					return [ 500, 'the states could not be swapped', '', [], '' ];

				return $failure;
			}
			finally {
				self::_forget( $appData );
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::DIR. '/state.lock' );
			}
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
			[ $state, $previous, $next, $gone, $swap, $building ] = array_map( static fn( string $name ): string => $base. '/'. $name, [ 'state', 'previous', 'next', 'gone', 'swap', 'building' ] );
			$mended = false;

			if( is_dir( $base ) === false )
				return false;

			if( is_dir( $state ) === false ) {

				// A change that was past its second rename: the state it made is whole, and it is the state
				if( is_dir( $next ) === true )
					$mended = rename( $next, $state );
				// A swap that was begun and not carried on: the state put aside is the state once more
				else if( is_dir( $swap ) === true )
					$mended = rename( $swap, $state );
				// Nothing else of it is left: the last state is the state, and nothing is lost that was not lost already
				else if( is_dir( $previous ) === true )
					$mended = rename( $previous, $state );
			}

			if( is_dir( $state ) === true ) {

				// The swap that was carried on to its second step: what was put aside is the last state
				if( is_dir( $swap ) === true ) {

					if( is_dir( $previous ) === false )
						rename( $swap, $previous );
					else
						\Nino\Filesystem::removeDir( $swap );

					$mended = true;
				}

				// A change that was broken off after the last state was put aside, before the state was: the last state is back
				if( is_dir( $previous ) === false && is_dir( $gone ) === true ) {
					rename( $gone, $previous );
					$mended = true;
				}
			}

			if( is_dir( $state ) === true )
				foreach( [ $gone, $building, $next ] as $left )
					if( is_dir( $left ) === true ) {
						\Nino\Filesystem::removeDir( $left );
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

			$base = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DIR );

			if( is_dir( $base ) === false || ( is_dir( $base. '/state' ) === true && is_dir( $base. '/gone' ) === false && is_dir( $base. '/swap' ) === false )
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
		 *	The commit, with the lock held
		 *
		 *	@return 	array|null
		 */
		private static function _commit( array &$appData, callable $change, bool $force ): ?array {

			$dir = \Nino\Modules\Themes::DIR;
			$state = \Nino\Filesystem::path( $appData, $dir. '/state' );
			$previous = \Nino\Filesystem::path( $appData, $dir. '/previous' );
			$next = \Nino\Filesystem::path( $appData, $dir. '/next' );
			$gone = \Nino\Filesystem::path( $appData, $dir. '/gone' );
			$building = \Nino\Filesystem::path( $appData, $dir. '/building' );

			// What a break left is mended and what is left over is gone: nothing is made on a state that is not whole
			if( is_dir( $building ) === true )
				\Nino\Filesystem::removeDir( $building );

			if( is_dir( $state ) === true )
				\Nino\Filesystem::copyDir( $state, $building );
			else
				@mkdir( $building, 0755, true );

			$failure = $change( $appData, $dir. '/building' );

			if( $failure === null )
				$failure = self::_build( $appData, $dir. '/building' );

			if( $failure !== null ) {
				\Nino\Filesystem::removeDir( $building );
				return $failure;
			}

			// The same state as before: nothing is moved, and the last state stays the last
			if( is_dir( $state ) === true && self::_digest( $state ) === self::_digest( $building ) ) {
				\Nino\Filesystem::removeDir( $building );
				return self::_sync( $appData, $state, $previous, self::_foreign( $appData, $state. '/theme.css', $force ) );
			}

			$foreign = is_file( $building. '/theme.css' ) === true ? self::_foreign( $appData, $building. '/theme.css', $force ) : null;

			if( is_array( $foreign ) === true ) {
				\Nino\Filesystem::removeDir( $building );
				return $foreign;
			}

			// The new state is whole from the day it is called next: that name is what _heal() takes it by
			if( rename( $building, $next ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			if( is_dir( $state ) === false )
				@mkdir( $state, 0755, true );

			// The last state is put aside and not removed: while there is no state, it is what a break would need
			if( is_dir( $previous ) === true && rename( $previous, $gone ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			if( rename( $state, $previous ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			// The stylesheet before the new state has its name, so that a break after it leaves a state and a stylesheet that belong together
			$failure = self::_sync( $appData, $next, $previous, $foreign );

			if( rename( $next, $state ) === false )
				return [ 500, 'the state could not be saved', '', [], '' ];

			if( is_dir( $gone ) === true )
				\Nino\Filesystem::removeDir( $gone );

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

			if( str_starts_with( $active['theme'], 'own:' ) === true ) {

				$key = substr( $active['theme'], 4 );
				$mix = preg_match( \Nino\Components::NAME, $key ) === 1 ? self::_load( $appData, $next. '/own/'. $key. '.json' ) : null;
			}
			else
				$mix = is_array( $active['mix'] ?? null ) === true ? $active['mix'] : Mix::of( $appData, $active['theme'] );

			if( is_array( $mix ) === false )
				return [ 404, 'the theme is not there: '. $active['theme'], 'themes_unknown', [ $active['theme'] ], '' ];

			// A state is files, and what is in files is checked as what is posted is
			$mix = Mix::clean( $appData, $mix, false );

			if( is_string( $mix ) === true )
				return [ 409, 'the mix of the active theme is refused: '. $mix, 'themes_mix', [ $mix ], '' ];

			$brand = Brand::clean( $appData, self::_load( $appData, $next. '/brand.json' ) ?? [] );
			$built = Compiler::compile( $appData, $mix, is_array( $brand ) === true ? $brand : Brand::clean( $appData, [] ) );

			self::keep( $appData, $built['plan'] );

			$active['built'] = Compiler::stamp( $built['css'] );
			$active['feature'] = (string) ( \Nino\Features::get( $appData, 'themes' )['version'] ?? '' );
			$active['register'] = \Nino\Components::version( $appData );

			if( \Nino\Filesystem::putFileContent( $appData, $next. '/theme.css', $built['css'] ) === false
				|| \Nino\Filesystem::putFileContent( $appData, $next. '/variants.php', $built['variants'] ) === false
				|| \Nino\Filesystem::putFileContent( $appData, $next. '/active.json', $active ) === false )
				return [ 500, 'the stylesheet could not be written', '', [], '' ];

			return null;
		}

		/**
		 *	A copy of each part a mix takes from a theme that is installed, where the project has none yet: theme.json of the theme and
		 *	the folder of the part. Only a part the reader read and accepted is copied, and as the text it was read as - written, never
		 *	copied as a file, which would follow a link to what the theme has no business with - so that a part that rests has no copy
		 *	to be taken for a good one at the next build. What is there is never overwritten, so a copy is the version it was made
		 *	from, and a part is copied when a theme of the project - the active one, an own one - names it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$plan					As Compiler::plan() answers it
		 *
		 *	@return 	void
		 */
		public static function keep( array &$appData, array $plan ): void {

			$entries = [ 'page' => $plan['page'] ] + $plan['groups'];

			foreach( $entries as $group => $entry ) {

				if( $entry['origin'] !== 'installed' || $entry['part'] === null || preg_match( \Nino\Components::NAME, (string) $group ) !== 1 )
					continue;

				$theme = Reader::theme( $entry['dir'], '' );
				$raw = is_array( $theme ) === true ? ( $theme['raw'][$group] ?? null ) : null;

				if( $raw === null || Reader::check( $appData, (string) $group, $raw )['ok'] !== true )
					continue;

				$into = \Nino\Modules\Themes::DIR. '/copies/'. $entry['theme']. '@'. $entry['version'];

				if( is_dir( \Nino\Filesystem::path( $appData, $into. '/parts/'. $group ) ) === true )
					continue;

				if( is_file( \Nino\Filesystem::path( $appData, $into. '/theme.json' ) ) === false )
					\Nino\Filesystem::putFileContent( $appData, $into. '/theme.json', [ 'key' => $theme['key'], 'version' => $theme['version'], 'name' => $theme['name'], 'description' => $theme['description'], 'brand' => $theme['brand'] ] );

				\Nino\Filesystem::putFileContent( $appData, $into. '/parts/'. $group. '/part.json', $raw['json'] );

				if( trim( $raw['css'] ) !== '' )
					\Nino\Filesystem::putFileContent( $appData, $into. '/parts/'. $group. '/part.css', $raw['css'] );
			}
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
			$text = is_file( $file ) === true ? file_get_contents( $file ) : false;

			if( $text === false || Compiler::isOwn( $text ) === true || ( is_file( $next ) === true && file_get_contents( $next ) === $text ) )
				return null;

			return $force === true ? $text : [ 409, 'the stylesheet of the project is not the one this feature wrote', 'themes_foreign', [], '' ];
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

			if( is_string( $foreign ) === true && is_dir( $previous ) === true )
				file_put_contents( $previous. '/theme.css', $foreign );

			if( is_file( $state. '/theme.css' ) === true ) {

				if( \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Themes::SHEET, (string) file_get_contents( $state. '/theme.css' ) ) === false )
					return [ 500, 'the stylesheet could not be written', '', [], '' ];
			}
			else if( is_file( $file ) === true && ( $foreign !== null || Compiler::isOwn( (string) file_get_contents( $file ) ) === true ) )
				unlink( $file );

			return null;
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
						$hashes[] = $below. $entry. ':'. sha1_file( $path. '/'. $entry );
				}
			};

			$walk( $dir, '' );
			sort( $hashes );

			return sha1( implode( "\n", $hashes ) );
		}
	}
}
