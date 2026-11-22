<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Themes\Package	see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		An own theme as a package, to save: its folder - which has the form of a package - packed as a .tar.gz, after the whole check of the kernel
	 *										that the catalogue holds a theme to, and after what is packed is read back as the same files
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Package {

		// How deep a file of a package lies below the folder: blocks/<slug>/images/<file>
		private const int DEPTH = 4;

		/**
		 *	What the folder of an own theme holds, as the files of a package: every file of it, by path, as it is. Nothing is made good: a link, a file that is none or one that is too
		 *	large is said as what the check of a package says of it, and the files that are too many are handed on to be counted by the check
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the own theme
		 *
		 *	@return 	array											'files' => path => bytes; or 'problem' => [ 'code', 'path', 'params' ] as \Nino\Packages::check() says it; or 'missing' => true; or 'rejected' => true for a folder the reader refuses
		 */
		public static function files( array &$appData, string $key ): array {

			if( preg_match( \Nino\Components::NAME, $key ) !== 1 || strlen( $key ) > \Nino\Components::NAME_LENGTH )
				return [ 'missing' => true ];

			$dir = Reader::ownDir( $appData ). '/'. $key;

			if( is_dir( $dir ) === false || is_link( $dir ) === true )
				return [ 'missing' => true ];

			// Only a folder the reader of the own themes takes for one: a folder it refuses - the name of the folder is not the key in its theme.json - is no theme of the list, and
			// what leaves is never a package whose root is not its key
			if( isset( Reader::own( $appData, true )['themes'][$key] ) === false )
				return [ 'rejected' => true ];

			$limits = \Nino\Packages::LIMITS[\Nino\Packages::THEME];
			$files = [];
			$sum = 0;
			$found = self::_walk( $dir, '', 1, $limits, $files, $sum );

			return $found === null ? [ 'files' => $files ] : [ 'problem' => $found ];
		}

		/**
		 *	What a theme carries in its folder blocks/, as the files of a package: for an installed theme or an own one, read as files only - a link, a file that is none or one that is too large
		 *	is the problem the check of a package says of it, and nothing is read through a link. A folder that has no blocks/ carries none
		 *
		 *	@param		string		$dir					The real path of the folder of the theme
		 *
		 *	@return 	array											'files' => path (blocks/...) => bytes; or 'problem' => [ 'code', 'path', 'params' ]
		 */
		public static function carried( string $dir ): array {

			if( is_link( $dir. '/blocks' ) === true )
				return [ 'problem' => [ 'code' => 'path', 'path' => 'blocks', 'params' => [] ] ];

			if( is_dir( $dir. '/blocks' ) === false )
				return [ 'files' => [] ];

			$files = [];
			$sum = 0;
			$found = self::_walk( $dir. '/blocks', 'blocks', 2, \Nino\Packages::LIMITS[\Nino\Packages::THEME], $files, $sum );

			return $found === null ? [ 'files' => $files ] : [ 'problem' => $found ];
		}

		/**
		 *	How many blocks the folder of a theme carries in blocks/: the folders that are named as a block is and are no links, each with the block.json and the block.tpl of a block
		 *
		 *	@param		string		$dir					The real path of the folder of the theme
		 *
		 *	@return 	int
		 */
		public static function counted( string $dir ): int {

			$count = 0;

			if( is_link( $dir. '/blocks' ) === true || is_dir( $dir. '/blocks' ) === false )
				return 0;

			foreach( (array) scandir( $dir. '/blocks' ) as $entry ) {

				$path = $dir. '/blocks/'. $entry;

				if( preg_match( \Nino\Components::NAME, (string) $entry ) === 1 && is_link( $path ) === false && is_dir( $path ) === true && is_file( $path. '/block.json' ) === true && is_link( $path. '/block.json' ) === false && is_file( $path. '/block.tpl' ) === true && is_link( $path. '/block.tpl' ) === false )
					$count++;
			}

			return $count;
		}

		/**
		 *	Whether blocks of the project may be put into a package: the feature that lists them is on, and the account holds the right of it - for an account without the right a package
		 *	is the folder of the theme and nothing else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function blocksAvailable( array &$appData ): bool {

			$active = array_map( static fn( mixed $class ): string => '\\'. ltrim( (string) $class, '\\' ), (array) ( $appData['/nino/modules'] ?? [] ) );

			return in_array( '\\Nino\\Modules\\Themelab', $active, true ) === true && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Themelab::PERM ) === true;
		}

		/**
		 *	@return 	array|null								The problem, or null
		 */
		private static function _walk( string $dir, string $below, int $depth, array $limits, array &$files, int &$sum ): ?array {

			$entries = @scandir( $dir );

			if( $entries === false )
				return [ 'code' => 'path', 'path' => $below === '' ? '.' : $below, 'params' => [] ];

			foreach( $entries as $entry ) {

				if( $entry === '.' || $entry === '..' )
					continue;

				$path = $below === '' ? $entry : $below. '/'. $entry;
				$full = $dir. '/'. $entry;

				if( is_link( $full ) === true )
					return [ 'code' => 'path', 'path' => $path, 'params' => [] ];

				if( is_dir( $full ) === true ) {

					if( $depth >= self::DEPTH )
						return [ 'code' => 'path', 'path' => $path, 'params' => [] ];

					$found = self::_walk( $full, $path, $depth + 1, $limits, $files, $sum );

					if( $found !== null )
						return $found;

					continue;
				}

				if( is_file( $full ) === false )
					return [ 'code' => 'path', 'path' => $path, 'params' => [] ];

				// The limits of a package are those of the whole of it: what is more is said at once, with the words of the check, before another file is read
				if( count( $files ) >= $limits['files'] )
					return [ 'code' => 'files-many', 'path' => '', 'params' => [ $limits['files'] ] ];

				$size = (int) @filesize( $full );

				if( $size > $limits['bytes'] )
					return [ 'code' => 'long', 'path' => $path, 'params' => [ $limits['bytes'] ] ];

				if( $sum + $size > $limits['bytes'] )
					return [ 'code' => 'bytes-many', 'path' => '', 'params' => [ $limits['bytes'] ] ];

				$sum += $size;

				$bytes = @file_get_contents( $full );

				if( $bytes === false )
					return [ 'code' => 'path', 'path' => $path, 'params' => [] ];

				$files[$path] = $bytes;
			}

			return null;
		}

		/**
		 *	Whether an own theme can be exported, and the package if it can: the files of its folder are held to the whole check of the kernel - what the catalogue would not take is not
		 *	handed out - and what is packed is what a reader takes back as the same files. Any theme's part may be in it, as it is: the licence is the theme's own LICENSE.txt, which is
		 *	in the folder when it was in the theme it was made of
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the own theme
		 *	@param		array			$blocks				The keys of blocks of the project that go into the package as well, none for a package of the folder alone
		 *
		 *	@return 	array											'bytes', 'file' (key and version of the theme), 'files' (how many) and 'replaced' (the keys of the folder's own blocks/ that the project's blocks took the place of); or 'failure' => [ status, message, code, params, field ]
		 */
		public static function export( array &$appData, string $key, array $blocks = [] ): array {

			$read = self::files( $appData, $key );

			if( isset( $read['missing'] ) === true )
				return [ 'failure' => [ 404, 'there is no such own theme', 'themes_export_unknown', [], 'theme' ] ];

			if( isset( $read['rejected'] ) === true )
				return [ 'failure' => [ 400, 'the folder is no own theme, the reader refuses it', 'themes_export_rejected', [], 'theme' ] ];

			if( isset( $read['problem'] ) === true )
				return Upload::problem( $read['problem'] );

			$files = $read['files'];
			$replaced = [];

			// Blocks of the project go into the archive and nowhere else: the folder of the theme is not written. What the folder carries in blocks/ and the project has a block of is
			// replaced by the project's block, and the answer says which
			if( $blocks !== [] ) {

				$carried = array_filter( $files, static fn( string $path ): bool => str_starts_with( $path, 'blocks/' ), ARRAY_FILTER_USE_KEY );
				$bundle = \Nino\Modules\Themelab\Package::bundle( $appData, $blocks, $carried );

				if( isset( $bundle['failure'] ) === true )
					return $bundle;

				$files = array_diff_key( $files, $carried ) + $bundle['files'];
				$replaced = $bundle['replaced'];
			}

			$problem = \Nino\Packages::check( $appData, \Nino\Packages::THEME, $files );

			if( $problem !== null )
				return Upload::problem( $problem );

			$theme = (array) json_decode( (string) $files['theme.json'], true );

			// What leaves is what comes in: the words and the brand are held to what the upload holds a package to
			$refused = Upload::refuse( $theme );

			if( $refused !== null )
				return $refused;

			$limits = \Nino\Packages::LIMITS[\Nino\Packages::THEME];
			$each = max( \Nino\Blocks::MAX_PICTURE, \Nino\Packages::MAX_PREVIEW );
			$entries = 3 * $limits['files'];
			$bytes = \Nino\Archive::write( $key, $files, $entries, $each, $limits['bytes'], time(), Upload::ALLOWED );

			if( is_array( $bytes ) === true )
				return [ 'failure' => [ 400, 'the archive is not made: '. (string) $bytes['code'], 'themes_export_archive', [ (string) $bytes['path'], (string) $bytes['code'] ], 'theme' ] ];

			if( strlen( $bytes ) > \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME] )
				return [ 'failure' => [ 400, 'the archive is larger than a package may be', 'themes_export_large', [ \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME] ], 'theme' ] ];

			// What leaves is what a reader takes back as the same files
			$back = \Nino\Archive::read( $bytes, $key, $entries, $each, $limits['bytes'], Upload::ALLOWED );

			if( is_array( $back ) === false || $back != $files )
				return [ 'failure' => [ 500, 'the archive is not the files', 'themes_export_archive', [ '', 'different' ], 'theme' ] ];

			return [ 'bytes' => $bytes, 'file' => $key. '-'. (string) ( $theme['version'] ?? '' ). '.tar.gz', 'files' => count( $files ), 'replaced' => $replaced ];
		}
	}
}
