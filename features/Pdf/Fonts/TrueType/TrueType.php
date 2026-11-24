<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Pdf\Fonts\TrueType	Reads what a PDF needs of a TrueType file
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Pdf\Fonts {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	TrueType					The measures of a TrueType file with glyph outlines (glyf): the advance width of every
	 *										WinAnsi code from 32 to 255 in units of 1/1000 em - read through the Unicode cmap (3, 1),
	 *										the hmtx and the head - the ascender and descender, the cap height and x-height, the
	 *										bounding box, the italic angle, the flags and a stem weight of the descriptor a PDF writes.
	 *
	 *										A file that is not such a font - a CFF outline (OpenType), a webfont, a file whose magic
	 *										is wrong, whose tables reach past its end or that lacks one of the tables named below -
	 *										is not read: read() answers a code for the reason. The bytes are never run or
	 *										interpreted beyond the numbers of the tables; every read is checked against the length.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class TrueType {

		// The tables a file needs
		public const array TABLES = [ 'head', 'hhea', 'hmtx', 'cmap', 'OS/2', 'post', 'glyf', 'loca' ];

		// How many tables and cmap records are looked at
		private const int MAX_TABLES = 64;

		// The codes of WinAnsiEncoding from 0x80 to 0x9F as Unicode; 0 is a code the encoding leaves undefined, which a PDF shows as a bullet
		private const array CP1252 = [
			0x20AC, 0, 0x201A, 0x0192, 0x201E, 0x2026, 0x2020, 0x2021, 0x02C6, 0x2030, 0x0160, 0x2039, 0x0152, 0, 0x017D, 0,
			0, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2013, 0x2014, 0x02DC, 0x2122, 0x0161, 0x203A, 0x0153, 0, 0x017E, 0x0178,
		];

		/**
		 *	The numbers of a font
		 *
		 *	@param		string		$bytes				The file
		 *
		 *	@return 	array|string								widths (224 integers, the first for code 32), ascent, descent (negative), cap, xheight, bbox (four integers), angle, flags, stemv, weight; or the code of the refusal: magic, cff, woff, tables, truncated, cmap, license
		 */
		public static function read( string $bytes ): array|string {

			$length = strlen( $bytes );

			if( $length < 12 )
				return 'magic';

			$version = substr( $bytes, 0, 4 );

			if( $version === 'OTTO' )
				return 'cff';

			if( $version === 'wOFF' || $version === 'wOF2' )
				return 'woff';

			if( $version !== "\x00\x01\x00\x00" && $version !== 'true' )
				return 'magic';

			try {
				return self::_read( $bytes );
			}
			catch( \OutOfRangeException ) {
				return 'truncated';
			}
		}

		/**
		 *	@param		string		$bytes
		 *
		 *	@return 	array|string
		 */
		private static function _read( string $bytes ): array|string {

			$length = strlen( $bytes );
			$count = self::_u16( $bytes, 4 );

			if( $count < 1 || $count > self::MAX_TABLES )
				return 'tables';

			$tables = [];

			for( $at = 0; $at < $count; $at++ ) {

				$tag = self::_slice( $bytes, 12 + 16 * $at, 4 );
				$offset = self::_u32( $bytes, 12 + 16 * $at + 8 );
				$size = self::_u32( $bytes, 12 + 16 * $at + 12 );

				if( $offset + $size > $length )
					return 'truncated';

				$tables[$tag] = [ $offset, $size ];
			}

			if( isset( $tables['CFF '] ) === true || isset( $tables['CFF2'] ) === true )
				return 'cff';

			foreach( self::TABLES as $need )
				if( isset( $tables[$need] ) === false )
					return 'tables';

			[ $head, $headSize ] = $tables['head'];
			[ $hhea, $hheaSize ] = $tables['hhea'];
			[ $hmtx, $hmtxSize ] = $tables['hmtx'];
			[ $os2, $os2Size ] = $tables['OS/2'];
			[ $post, $postSize ] = $tables['post'];

			if( $headSize < 54 || $hheaSize < 36 || $os2Size < 78 || $postSize < 32 || self::_u32( $bytes, $head + 12 ) !== 0x5F0F3CF5 )
				return 'tables';

			$em = self::_u16( $bytes, $head + 18 );

			if( $em < 16 || $em > 16384 )
				return 'tables';

			$metrics = self::_u16( $bytes, $hhea + 34 );

			if( $metrics < 1 || $hmtxSize < 4 * $metrics )
				return 'tables';

			// A file whose licence forbids embedding is not embedded
			if( ( self::_u16( $bytes, $os2 + 8 ) & 0x0002 ) !== 0 )
				return 'license';

			$units = static fn( int $value ): int => (int) round( $value * 1000 / $em );
			$glyphs = self::_glyphs( $bytes, $tables['cmap'] );

			if( $glyphs === null )
				return 'cmap';

			$widths = [];

			for( $code = 32; $code < 256; $code++ ) {

				$glyph = $glyphs[ self::_unicode( $code ) ] ?? 0;
				$widths[] = $units( self::_u16( $bytes, $hmtx + 4 * min( $glyph, $metrics - 1 ) ) );
			}

			$weight = self::_u16( $bytes, $os2 + 4 );
			$ascent = $units( self::_s16( $bytes, $hhea + 4 ) );
			$angle = self::_s32( $bytes, $post + 4 ) / 65536.0;
			$class = self::_s16( $bytes, $os2 + 30 ) >> 8;
			$version = self::_u16( $bytes, $os2 );
			$cap = $version >= 2 && $os2Size >= 90 ? $units( self::_s16( $bytes, $os2 + 88 ) ) : 0;
			$xheight = $version >= 2 && $os2Size >= 90 ? $units( self::_s16( $bytes, $os2 + 86 ) ) : 0;
			$italic = $angle !== 0.0 || ( self::_u16( $bytes, $head + 44 ) & 2 ) !== 0 || ( self::_u16( $bytes, $os2 + 62 ) & 1 ) !== 0;

			return [
				'widths'		=> $widths,
				'ascent'		=> $ascent,
				'descent'		=> $units( self::_s16( $bytes, $hhea + 6 ) ),
				'cap'				=> $cap > 0 ? $cap : $ascent,
				'xheight'		=> $xheight > 0 ? $xheight : (int) round( $ascent / 2 ),
				'bbox'			=> [ $units( self::_s16( $bytes, $head + 36 ) ), $units( self::_s16( $bytes, $head + 38 ) ), $units( self::_s16( $bytes, $head + 40 ) ), $units( self::_s16( $bytes, $head + 42 ) ) ],
				'angle'			=> (int) round( $angle ),
				// Nonsymbolic (32); serif (2) for a serif family class; italic (64); forced bold (262144) from a weight of 700
				'flags'			=> 32 | ( ( $class >= 1 && $class <= 5 ) || $class === 7 ? 2 : 0 ) | ( $italic === true ? 64 : 0 ) | ( $weight >= 700 ? 262144 : 0 ),
				// The stem width is not in the file: an estimate from the weight, 95 at 400 and 162 at 700
				'stemv'			=> (int) round( 10 + 220 * ( max( 100, min( 900, $weight ) ) - 50 ) / 900 ),
				'weight'		=> $weight,
			];
		}

		/**
		 *	Unicode => glyph of the cmap (3, 1) for the characters of WinAnsi
		 *
		 *	@param		string		$bytes
		 *	@param		array			$table				[ offset, length ]
		 *
		 *	@return 	array|null								Null without a format 4 subtable for (3, 1)
		 */
		private static function _glyphs( string $bytes, array $table ): ?array {

			[ $cmap, $size ] = $table;
			$records = self::_u16( $bytes, $cmap + 2 );
			$sub = null;

			for( $at = 0; $at < min( $records, self::MAX_TABLES ); $at++ )
				if( self::_u16( $bytes, $cmap + 4 + 8 * $at ) === 3 && self::_u16( $bytes, $cmap + 6 + 8 * $at ) === 1 )
					$sub = $cmap + self::_u32( $bytes, $cmap + 8 + 8 * $at );

			if( $sub === null || $sub >= $cmap + $size || self::_u16( $bytes, $sub ) !== 4 )
				return null;

			$segments = intdiv( self::_u16( $bytes, $sub + 6 ), 2 );

			if( $segments < 1 || $sub + 16 + 8 * $segments > $cmap + $size )
				return null;

			$wanted = [];

			for( $code = 32; $code < 256; $code++ )
				$wanted[ self::_unicode( $code ) ] = true;

			$glyphs = [];
			$ends = $sub + 14;
			$starts = $ends + 2 * $segments + 2;
			$deltas = $starts + 2 * $segments;
			$offsets = $deltas + 2 * $segments;

			for( $at = 0; $at < $segments; $at++ ) {

				$end = self::_u16( $bytes, $ends + 2 * $at );
				$start = self::_u16( $bytes, $starts + 2 * $at );
				$delta = self::_s16( $bytes, $deltas + 2 * $at );
				$range = self::_u16( $bytes, $offsets + 2 * $at );

				foreach( array_keys( $wanted ) as $unicode ) {

					if( $unicode < $start || $unicode > $end )
						continue;

					if( $range === 0 )
						$glyphs[$unicode] = ( $unicode + $delta ) & 0xFFFF;
					else {
						$glyph = self::_u16( $bytes, $offsets + 2 * $at + $range + 2 * ( $unicode - $start ) );
						$glyphs[$unicode] = $glyph === 0 ? 0 : ( $glyph + $delta ) & 0xFFFF;
					}
				}
			}

			return $glyphs;
		}

		/**
		 *	The character of a WinAnsi code; a code the encoding leaves undefined is the bullet, as a viewer shows it
		 *
		 *	@param		int				$code
		 *
		 *	@return 	int
		 */
		private static function _unicode( int $code ): int {

			if( $code >= 0x80 && $code <= 0x9F )
				return self::CP1252[$code - 0x80] ?: 0x2022;

			return $code;
		}

		private static function _slice( string $bytes, int $at, int $size ): string {

			if( $at < 0 || $at + $size > strlen( $bytes ) )
				throw new \OutOfRangeException( 'the file ends' );

			return substr( $bytes, $at, $size );
		}

		private static function _u16( string $bytes, int $at ): int {
			return (int) ( unpack( 'n', self::_slice( $bytes, $at, 2 ) )[1] ?? 0 );
		}

		private static function _s16( string $bytes, int $at ): int {
			return (int) ( unpack( 'n', self::_slice( $bytes, $at, 2 ) )[1] ?? 0 ) - ( ( ( ord( $bytes[$at] ) & 0x80 ) !== 0 ) ? 65536 : 0 );
		}

		private static function _u32( string $bytes, int $at ): int {
			return (int) ( unpack( 'N', self::_slice( $bytes, $at, 4 ) )[1] ?? 0 );
		}

		private static function _s32( string $bytes, int $at ): int {
			$value = self::_u32( $bytes, $at );

			return $value >= 0x80000000 ? $value - 0x100000000 : $value;
		}
	}
}
