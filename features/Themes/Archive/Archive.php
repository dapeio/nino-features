<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Themes\Archive	see _nino/Nino/Modules/Modules.php for the
 *												package-level docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The archive of a family of fonts, read in memory: a .tar.gz of one directory that holds plain files and nothing else
	 *
	 *										Nothing is written to a disk here and no extension but zlib is asked for: the gzip stream is inflated in small
	 *										steps under a limit of its own (a stream that unpacks to more than the limit is stopped, not read to its end),
	 *										and the tar is walked block by block. An entry is a plain file or the one directory; a link, a device, a
	 *										long name, a path with a dot segment, a name outside the one directory or one that is not a plain name is
	 *										refused, and so is a checksum that does not hold, a size that is not octal, a file over its limit, more files
	 *										than the limit, a stream that ends inside an entry and bytes behind the end of the gzip stream. The extensions of pax are skipped, never read: the
	 *										name of an entry is the one of its header, and a name the header cuts short is no name that passes.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Archive {

		private const int BLOCK = 512;

		// The bytes of the compressed stream that are inflated at a time: the most one step can grow is about a thousandfold
		private const int STEP = 4096;

		// A name of a file of the directory: what a font and its two files are called
		private const string NAME = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,80}$/D';

		/**
		 *	Whether the extension the archive is read with is there
		 *
		 *	@return 	bool
		 */
		public static function supported(): bool {
			return function_exists( 'inflate_init' ) === true && function_exists( 'inflate_add' ) === true;
		}

		/**
		 *	The files of an archive
		 *
		 *	@param		string		$bytes				The archive, gzip over tar
		 *	@param		string		$directory		The one directory the archive holds, as it is named in it
		 *	@param		int				$files				The most files it may hold
		 *	@param		int				$each					The most bytes one file may have
		 *	@param		int				$total				The most bytes all files together may have
		 *
		 *	@return 	array|string									name => content, in the order of the archive - or why it is refused
		 */
		public static function read( string $bytes, string $directory, int $files, int $each, int $total ): array|string {

			$tar = self::_inflate( $bytes, $total + ( $files + 2 ) * 2 * self::BLOCK + self::BLOCK * 2 + $each );

			if( is_string( $tar ) === false )
				return 'the archive is no gzip stream, or it unpacks to more than it may';

			$length = strlen( $tar );
			$at = 0;
			$found = [];
			$sum = 0;
			$seen = false;

			while( true ) {

				// A tar that stops after its last entry, with no block of nothing to end it, is as complete as one that has it
				if( $at >= $length )
					break;

				if( $at + self::BLOCK > $length )
					return 'the archive ends inside an entry';

				$head = substr( $tar, $at, self::BLOCK );

				// The end of a tar is a block of nothing
				if( trim( $head, "\0" ) === '' )
					break;

				$at += self::BLOCK;

				$size = self::_size( $head );

				if( $size === null )
					return 'the archive holds an entry with a header that does not hold';

				$type = $head[156];
				$padded = (int) ( ceil( $size / self::BLOCK ) * self::BLOCK );

				if( $at + $size > $length )
					return 'the archive ends inside an entry';

				// The extension of pax is skipped and never read: the entry it speaks of is held to its own header
				if( $type === 'x' || $type === 'g' ) {
					$at += $padded;
					continue;
				}

				if( $type !== '0' && $type !== "\0" && $type !== '5' )
					return 'the archive holds an entry that is neither a file nor a directory';

				$name = self::_name( $head );

				if( $name === null )
					return 'the archive holds an unsafe name';

				if( $type === '5' ) {

					if( $name !== $directory || $seen === true )
						return 'the archive holds the directory "'. self::_shown( $name ). '", which is not the one it may';

					$seen = true;
					$at += $padded;
					continue;
				}

				if( str_starts_with( $name, $directory. '/' ) === false )
					return 'the archive holds "'. self::_shown( $name ). '" outside "'. $directory. '/"';

				$file = substr( $name, strlen( $directory ) + 1 );

				if( preg_match( self::NAME, $file ) !== 1 )
					return 'the archive holds "'. self::_shown( $name ). '", which is no plain file of the directory';

				if( isset( $found[$file] ) === true )
					return 'the archive holds "'. $file. '" twice';

				if( count( $found ) >= $files )
					return 'the archive holds more than '. $files. ' files';

				if( $size > $each )
					return 'the file "'. $file. '" is larger than '. $each. ' bytes';

				$sum += $size;

				if( $sum > $total )
					return 'the files of the archive are larger than '. $total. ' bytes together';

				$found[$file] = substr( $tar, $at, $size );
				$at += $padded;
			}

			return $found;
		}

		/**
		 *	The tar a gzip stream holds, inflated in steps and stopped where it grows past the limit
		 *
		 *	@param		string		$bytes
		 *	@param		int				$limit				The most bytes the tar may have
		 *
		 *	@return 	string|null									null for a stream that is none or too long
		 */
		private static function _inflate( string $bytes, int $limit ): ?string {

			if( strlen( $bytes ) < 18 || str_starts_with( $bytes, "\x1f\x8b" ) === false || self::supported() === false )
				return null;

			// A stream that is cut or damaged is a warning of zlib's and no more
			set_error_handler( static fn(): bool => true );

			try {
				$context = inflate_init( ZLIB_ENCODING_GZIP );
				$out = '';

				if( $context === false )
					return null;

				for( $at = 0, $end = strlen( $bytes ); $at < $end; $at += self::STEP ) {

					$chunk = inflate_add( $context, substr( $bytes, $at, self::STEP ), ZLIB_NO_FLUSH );

					if( $chunk === false )
						return null;

					$out .= $chunk;

					if( strlen( $out ) > $limit )
						return null;
				}

				// A stream that was not ended is a stream that was cut, and one that ended before the last byte has something behind it - a second
				// stream, or anything - that no two readers of an archive agree on
				return inflate_get_status( $context ) === ZLIB_STREAM_END && inflate_get_read_len( $context ) === strlen( $bytes ) ? $out : null;
			}
			finally {
				restore_error_handler();
			}
		}

		/**
		 *	The size of an entry, read from its header - which has to hold: the checksum is the sum of the bytes with the checksum field read as spaces
		 *
		 *	@param		string		$head					One block
		 *
		 *	@return 	int|null
		 */
		private static function _size( string $head ): ?int {

			$field = rtrim( substr( $head, 124, 12 ), "\0 " );
			$check = rtrim( substr( $head, 148, 8 ), "\0 " );

			if( preg_match( '/^[0-7]{1,11}$/D', $field ) !== 1 || preg_match( '/^[0-7]{1,7}$/D', $check ) !== 1 )
				return null;

			$sum = array_sum( unpack( 'C*', substr( $head, 0, 148 ). '        '. substr( $head, 156 ) ) ?: [] );

			return $sum === octdec( $check ) ? (int) octdec( $field ) : null;
		}

		/**
		 *	The name of an entry: the prefix and the name of its header, as the path they spell, with no leading ./ and no trailing slash
		 *
		 *	@param		string		$head					One block
		 *
		 *	@return 	string|null								null for a name that is not a path of plain segments
		 */
		private static function _name( string $head ): ?string {

			// A field ends at its first null byte, as tar reads it: one that begins with a null byte is an empty name, whatever follows
			$name = (string) strstr( substr( $head, 0, 100 ). "\0", "\0", true );
			$prefix = substr( $head, 257, 5 ) === 'ustar' ? (string) strstr( substr( $head, 345, 155 ). "\0", "\0", true ) : '';
			$path = $prefix === '' ? $name : $prefix. '/'. $name;

			if( str_starts_with( $path, './' ) === true )
				$path = substr( $path, 2 );

			$path = rtrim( $path, '/' );

			if( $path === '' || preg_match( '/[\x00-\x1f\x7f\\\\]/', $path ) === 1 )
				return null;

			foreach( explode( '/', $path ) as $segment )
				if( $segment === '' || $segment === '.' || $segment === '..' )
					return null;

			return $path;
		}

		/**
		 *	A name as a sentence may carry it: short, one line
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		private static function _shown( string $name ): string {
			return mb_substr( (string) preg_replace( '/[^\x20-\x7e]/', '?', $name ), 0, 60 );
		}
	}
}
