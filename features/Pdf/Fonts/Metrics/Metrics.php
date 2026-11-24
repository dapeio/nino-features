<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Pdf\Fonts\Metrics	The widths of the standard fonts
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Pdf\Fonts {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Metrics						What a layout has to know about the twelve standard fonts it
	 *										can set text in - the Helvetica, Times and Courier families in
	 *										regular, bold, italic and bold italic - to measure a string:
	 *										the base font names a PDF is told, the advance width of every
	 *										WinAnsi code from 32 to 255, and the ascender and descender
	 *										of a family.
	 *
	 *										The widths are data, one file for each font beside this class
	 *										(Metrics/<Font>.php): a list of 224 numbers in units of 1/1000
	 *										em, the first for code 32, or the one number a monospaced font
	 *										has for every code. They are the character metrics of Adobe's
	 *										Core 14 AFM files; the head of each file carries Adobe's
	 *										notice and the terms they are distributed under, and the
	 *										catalogue's THIRD-PARTY-NOTICES.md quotes them. A font's
	 *										kerning pairs are not used: a viewer does not kern a simple
	 *										font either, so the measure and the page agree.
	 *
	 *										Beside the twelve there are the families the owner puts in a
	 *										folder of the project (private/pdf-fonts/<Family>-<Style>.ttf,
	 *										style Regular, Bold, Italic or BoldItalic): load() reads the
	 *										folder, TrueType the numbers of each file, once - the numbers
	 *										are kept in data/pdf-fonts/ until the file changes. A family
	 *										without a Regular file, a file that is no TrueType font with
	 *										outlines, one over 2 MB, one that does not allow embedding
	 *										and one with a name that does not fit are left out and named
	 *										by ignored(). A style the family has no file for is set in
	 *										its Regular.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Metrics {

		// The families a layout offers, by the word its panel stores
		public const array FAMILIES = [ 'helvetica', 'times', 'courier' ];

		// family => regular, bold, italic, bold italic
		private const array NAMES = [
			'helvetica'	=> [ 'Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique' ],
			'times'			=> [ 'Times-Roman', 'Times-Bold', 'Times-Italic', 'Times-BoldItalic' ],
			'courier'		=> [ 'Courier', 'Courier-Bold', 'Courier-Oblique', 'Courier-BoldOblique' ],
		];

		// The Ascender and Descender of the AFM files, which are the same for the four fonts of a family
		private const array VERTICAL = [ 'helvetica' => [ 718, -207 ], 'times' => [ 683, -217 ], 'courier' => [ 629, -157 ] ];

		// Where the owner puts the fonts, and where the numbers read from them are kept
		public const string DIR = '/private/pdf-fonts';
		public const string CACHE = '/data/pdf-fonts';

		// <Family>-<Style>.ttf: letters and digits, up to 40, and one of the four styles
		public const string FILE = '/^([A-Za-z0-9]{1,40})-(Regular|Bold|Italic|BoldItalic)\.ttf$/D';
		public const int MAX_BYTES = 2097152;
		public const int MAX_FILES = 64;

		// What the numbers kept in a file of the cache are read with: a change of the reader changes it
		private const int READER = 1;

		private const array STYLES = [ 'Regular' => 0, 'Bold' => 1, 'Italic' => 2, 'BoldItalic' => 3 ];

		// font => the widths, read once
		private static array $_widths = [];

		// family (lower case) => [ name, styles: [ 0..3 => [ base, file, widths, ... ] ] ], base font name => the same style, and file => code of the files left out
		private static array $_custom = [];
		private static array $_byFont = [];
		private static array $_ignored = [];

		/**
		 *	Read the folder of the owner's fonts of a project; what font(), family(), width(), vertical() and families()
		 *	answer for a family of it holds until the next call
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function load( array &$appData ): void {

			self::$_custom = [];
			self::$_byFont = [];
			self::$_ignored = [];
			$dir = \Nino\Filesystem::path( $appData, self::DIR );

			if( is_dir( $dir ) === false || is_link( $dir ) === true )
				return;

			$names = scandir( $dir );
			$found = [];
			$seen = 0;

			foreach( ( $names === false ? [] : $names ) as $name ) {

				if( $name[0] === '.' || preg_match( '/\.(ttf|otf|ttc|woff2?|eot)$/Di', $name ) !== 1 )
					continue;

				if( ++$seen > self::MAX_FILES )
					break;

				if( preg_match( self::FILE, $name, $parts ) !== 1 ) {
					self::$_ignored[$name] = 'name';
					continue;
				}

				$path = $dir. '/'. $name;

				if( is_link( $path ) === true || is_file( $path ) === false ) {
					self::$_ignored[$name] = 'name';
					continue;
				}

				$size = (int) filesize( $path );

				if( $size < 1 || $size > self::MAX_BYTES ) {
					self::$_ignored[$name] = 'size';
					continue;
				}

				$read = self::_read( $appData, $name, $path, $size );

				if( is_string( $read ) === true ) {
					self::$_ignored[$name] = $read;
					continue;
				}

				$found[ strtolower( $parts[1] ) ][ self::STYLES[ $parts[2] ] ] = [ 'name' => $parts[1], 'file' => $name, 'path' => $path, 'style' => $parts[2], 'size' => $size ] + $read;
			}

			ksort( $found );

			foreach( $found as $key => $styles ) {

				if( in_array( $key, self::FAMILIES, true ) === true || isset( $styles[0] ) === false ) {

					foreach( $styles as $style )
						self::$_ignored[ $style['file'] ] = isset( $styles[0] ) === false ? 'regular' : 'reserved';

					continue;
				}

				// Every style is named by the spelling of the Regular file
				foreach( $styles as $at => $style ) {
					$styles[$at]['base'] = $styles[0]['name']. '-'. $style['style'];
					self::$_byFont[ $styles[$at]['base'] ] = $styles[$at] + [ 'family' => $key ];
				}

				self::$_custom[$key] = [ 'name' => $styles[0]['name'], 'styles' => $styles ];
			}

			ksort( self::$_ignored );
		}

		/**
		 *	The numbers of one font file: from the cache while the file keeps its time and size, else read and kept
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		$path
		 *	@param		int				$size
		 *
		 *	@return 	array|string								The numbers, see TrueType::read(), or the code of the refusal
		 */
		private static function _read( array &$appData, string $name, string $path, int $size ): array|string {

			$kept = self::CACHE. '/'. sha1( $name ). '.php';
			$time = (int) filemtime( $path );
			$cache = \Nino\Filesystem::getFileContent( $appData, $kept, [] );

			if( is_array( $cache ) === true && ( $cache['reader'] ?? 0 ) === self::READER && ( $cache['time'] ?? 0 ) === $time && ( $cache['size'] ?? 0 ) === $size ) {

				if( is_string( $cache['read'] ?? null ) === true )
					return $cache['read'];

				if( is_array( $cache['read'] ?? null ) === true && count( $cache['read']['widths'] ?? [] ) === 224 )
					return $cache['read'];
			}

			$bytes = (string) file_get_contents( $path );
			$read = strlen( $bytes ) === $size ? TrueType::read( $bytes ) : 'truncated';

			\Nino\Filesystem::putFileContent( $appData, $kept, [ 'reader' => self::READER, 'time' => $time, 'size' => $size, 'read' => $read ] );

			return $read;
		}

		/**
		 *	The families a layout can be set in: the three standard ones, then the owner's
		 *
		 *	@return 	array											The words a layout stores, lower case
		 */
		public static function families(): array {
			return array_merge( self::FAMILIES, array_keys( self::$_custom ) );
		}

		/**
		 *	The owner's families by the word a layout stores
		 *
		 *	@return 	array											word => the name as the file spells it
		 */
		public static function owned(): array {
			return array_map( static fn( array $family ): string => $family['name'], self::$_custom );
		}

		/**
		 *	The font files load() left out
		 *
		 *	@return 	array											file name => name, size, tables, truncated, cff, woff, magic, cmap, license, regular or reserved
		 */
		public static function ignored(): array {
			return self::$_ignored;
		}

		/**
		 *	The name of a family as a stylesheet is written: the standard ones capitalised, the owner's as the file spells it
		 *
		 *	@param		string		$family				The word a layout stores
		 *
		 *	@return 	string
		 */
		public static function label( string $family ): string {
			return self::$_custom[$family]['name'] ?? ucfirst( $family );
		}

		/**
		 *	What a base font of the owner's is embedded with
		 *
		 *	@param		string		$font					A base font name
		 *
		 *	@return 	array|null								base, file, path, size, widths, ascent, descent, cap, xheight, bbox, angle, flags, stemv; null for a standard font
		 */
		public static function embedded( string $font ): ?array {
			return self::$_byFont[$font] ?? null;
		}

		/**
		 *	The base font name of a family in a style
		 *
		 *	@param		string		$family				helvetica, times, courier or a family of the owner (see load()); anything else is helvetica
		 *	@param		bool			$bold
		 *	@param		bool			$italic
		 *
		 *	@return 	string										eg. Times-BoldItalic
		 */
		public static function font( string $family, bool $bold, bool $italic ): string {

			if( isset( self::$_custom[$family] ) === true ) {

				$styles = self::$_custom[$family]['styles'];

				return $styles[ ( $bold === true ? 1 : 0 ) + ( $italic === true ? 2 : 0 ) ]['base'] ?? $styles[0]['base'];
			}

			return self::NAMES[ isset( self::NAMES[$family] ) === true ? $family : 'helvetica' ][ ( $bold === true ? 1 : 0 ) + ( $italic === true ? 2 : 0 ) ];
		}

		/**
		 *	The family a base font name belongs to
		 *
		 *	@param		string		$font
		 *
		 *	@return 	string
		 */
		public static function family( string $font ): string {

			if( isset( self::$_byFont[$font] ) === true )
				return self::$_byFont[$font]['family'];

			return str_starts_with( $font, 'Times' ) === true ? 'times' : ( str_starts_with( $font, 'Courier' ) === true ? 'courier' : 'helvetica' );
		}

		/**
		 *	The width of a string in units of 1/1000 em
		 *
		 *	@param		string		$font					A base font name, see font()
		 *	@param		string		$ansi					WinAnsi bytes
		 *
		 *	@return 	float
		 */
		public static function width( string $font, string $ansi ): float {

			if( $ansi === '' )
				return 0.0;

			if( isset( self::$_byFont[$font] ) === true )
				return self::_sum( self::$_byFont[$font]['widths'], $ansi );

			if( isset( self::$_widths[$font] ) === false ) {

				$file = __DIR__. '/'. $font. '.php';
				self::$_widths[$font] = in_array( $font, array_merge( ...array_values( self::NAMES ) ), true ) === true && is_file( $file ) === true ? require $file : 600;
			}

			$widths = self::$_widths[$font];

			if( is_int( $widths ) === true )
				return (float) ( strlen( $ansi ) * $widths );

			return self::_sum( $widths, $ansi );
		}

		/**
		 *	@param		array			$widths				224 numbers, the first for code 32
		 *	@param		string		$ansi
		 *
		 *	@return 	float
		 */
		private static function _sum( array $widths, string $ansi ): float {

			$sum = 0;

			foreach( (array) unpack( 'C*', $ansi ) as $code )
				$sum += $code >= 32 ? $widths[$code - 32] : 0;

			return (float) $sum;
		}

		/**
		 *	How far a family's glyphs reach above and below the baseline, in units of 1/1000 em
		 *
		 *	@param		string		$font					A base font name
		 *
		 *	@return 	array										[ ascender, descender ], the descender negative
		 */
		public static function vertical( string $font ): array {

			if( isset( self::$_byFont[$font] ) === true )
				return [ self::$_byFont[$font]['ascent'], self::$_byFont[$font]['descent'] ];

			return self::VERTICAL[ self::family( $font ) ];
		}
	}
}
