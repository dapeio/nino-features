<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Themes\Palette	see _nino/Nino/Modules/Modules.php for the
 *													package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The palette of the brand: eight light-dark() pairs and the colour written on each colour,
	 *										worked out of two colours, a tone and the mode of the page
	 *
	 *										The colours are searched, not scaled: the ink, the quiet ink and the accent move in lightness
	 *										(OKLCH, after Bjoern Ottosson) until each holds its contrast against every ground of its mode -
	 *										in the dark mode against the lightest ground a picture under the veil of a section leaves as
	 *										well - and the colour written on a colour is white or black, whichever carries better, which
	 *										is never below the square root of 21. The contrast is that of WCAG 2.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Palette {

		// The tones of the grey: the hue and the chroma of the grounds; the hue of neutral is the one of the first colour
		public const array TONES = [ 'warm' => [ 70, 0.012 ], 'neutral' => [ null, 0.004 ], 'cool' => [ 250, 0.012 ] ];

		// 4.5 : 1 with room for the rounding to eight bits per channel; the ink aims higher
		public const float TARGET = 4.6;
		public const float TARGET_INK = 7.0;

		// A white picture under the veil of a section (.75 black in the standard part): the lightest ground the writing of
		// the dark mode stands on
		public const string VEIL = '#404040';

		// What a colour of the brand is: six hexadecimal digits
		private const string HEX = '/^#[0-9a-fA-F]{6}$/D';

		/**
		 *	Whether a text is a colour the palette takes, and the colour as it is written down: six digits, lower case
		 *
		 *	@param		mixed			$color
		 *
		 *	@return 	string|null								null where it is none
		 */
		public static function color( mixed $color ): ?string {
			return is_string( $color ) === true && preg_match( self::HEX, $color ) === 1 ? strtolower( $color ) : null;
		}

		/**
		 *	The palette of two colours and a tone
		 *
		 *	@param		string		$color1				The first colour of the brand, #rrggbb
		 *	@param		string		$color2				The second colour, #rrggbb
		 *	@param		string		$tone					warm, neutral or cool
		 *
		 *	@return 	array											'light' and 'dark' => base, alt, tint, ink, quiet, line, accent, on; 'on-color-1', 'on-color-2'
		 */
		public static function make( string $color1, string $color2, string $tone ): array {

			[ $lightness, $chroma, $hue ] = self::_oklch( $color1 );
			[ $toneHue, $toneChroma ] = self::TONES[$tone] ?? self::TONES['neutral'];
			$toneHue ??= $hue;
			$palette = [];

			foreach( [ 'light' => [ 1.0, 0.965, 0.95, 0.24 ], 'dark' => [ 0.19, 0.23, 0.26, 0.93 ] ] as $mode => [ $base, $alt, $tint, $ink ] ) {

				$grounds = [
					'base'	=> self::_color( $base, $mode === 'light' && $tone === 'neutral' ? 0 : $toneChroma, $toneHue ),
					'alt'		=> self::_color( $alt, $toneChroma * 1.5, $toneHue ),
					'tint'	=> self::_color( $tint, min( 0.035, $chroma ), $hue ),
				];
				$stands = $mode === 'light' ? array_values( $grounds ) : array_merge( array_values( $grounds ), [ self::VEIL ] );

				// The accent is the first colour as it is where it can be read on every ground, and its hue in the lightness it takes otherwise
				$accent = self::_solve( $lightness, $chroma, $hue, $stands, self::TARGET, $mode === 'light' );

				$palette[$mode] = $grounds + [
					'ink'			=> self::_solve( $ink, $toneChroma, $toneHue, $stands, self::TARGET_INK, $mode === 'light' ),
					'quiet'		=> self::_solve( $mode === 'light' ? 0.5 : 0.75, $toneChroma, $toneHue, $stands, self::TARGET, $mode === 'light' ),
					'line'		=> self::_color( $mode === 'light' ? 0.86 : 0.36, $toneChroma, $toneHue ),
					'accent'	=> $accent,
					'on'			=> self::on( $accent ),
				];
			}

			$palette['on-color-1'] = self::on( $color1 );
			$palette['on-color-2'] = self::on( $color2 );

			return $palette;
		}

		/**
		 *	The colour that is written on a colour: white or black, whichever has the better contrast
		 *
		 *	@param		string		$color				#rrggbb
		 *
		 *	@return 	string
		 */
		public static function on( string $color ): string {
			return self::contrast( '#ffffff', $color ) >= self::contrast( '#000000', $color ) ? '#ffffff' : '#000000';
		}

		/**
		 *	The contrast of two colours, WCAG 2: from 1 to 21
		 *
		 *	@param		string		$a						#rrggbb
		 *	@param		string		$b						#rrggbb
		 *
		 *	@return 	float
		 */
		public static function contrast( string $a, string $b ): float {

			$x = self::_luminance( $a );
			$y = self::_luminance( $b );

			return ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 );
		}

		/**
		 *	Every pair a part reads, with its contrast: the ink, the quiet ink and the accent on each ground of each mode (the
		 *	dark mode on the picture under the veil too), the colour on the accent and the colour on the first colour
		 *
		 *	@param		array			$palette			As make() answers it
		 *	@param		string		$color1				The first colour, #rrggbb
		 *
		 *	@return 	array											'<mode> <ink|quiet|accent>/<ground>', '<mode> button', 'primary' => contrast
		 */
		public static function pairs( array $palette, string $color1 ): array {

			$pairs = [];

			foreach( [ 'light', 'dark' ] as $mode ) {

				$grounds = [ 'base' => $palette[$mode]['base'], 'alt' => $palette[$mode]['alt'], 'tint' => $palette[$mode]['tint'] ] + ( $mode === 'dark' ? [ 'picture' => self::VEIL ] : [] );

				foreach( $grounds as $name => $ground )
					foreach( [ 'ink', 'quiet', 'accent' ] as $role )
						$pairs[$mode. ' '. $role. '/'. $name] = self::contrast( $palette[$mode][$role], $ground );

				$pairs[$mode. ' button'] = self::contrast( $palette[$mode]['on'], $palette[$mode]['accent'] );
			}

			$pairs['primary'] = self::contrast( $palette['on-color-1'], $color1 );

			return $pairs;
		}

		private static function _linear( float $c ): float {
			return $c <= 0.04045 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		}

		private static function _gamma( float $c ): float {
			return $c <= 0.0031308 ? 12.92 * $c : 1.055 * $c ** ( 1 / 2.4 ) - 0.055;
		}

		/**
		 *	@return 	array											The three channels of a #rrggbb, 0 to 1
		 */
		private static function _rgb( string $hex ): array {

			$digits = ltrim( $hex, '#' );

			return [ hexdec( substr( $digits, 0, 2 ) ) / 255, hexdec( substr( $digits, 2, 2 ) ) / 255, hexdec( substr( $digits, 4, 2 ) ) / 255 ];
		}

		private static function _hex( array $channels ): string {
			return sprintf( '#%02x%02x%02x', ...array_map( static fn( float $x ): int => (int) round( max( 0, min( 1, $x ) ) * 255 ), $channels ) );
		}

		private static function _luminance( string $hex ): float {

			[ $r, $g, $b ] = array_map( [ self::class, '_linear' ], self::_rgb( $hex ) );

			return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
		}

		/**
		 *	@return 	array											Lightness, chroma and hue (degrees) of a #rrggbb in OKLCH
		 */
		private static function _oklch( string $hex ): array {

			[ $r, $g, $b ] = array_map( [ self::class, '_linear' ], self::_rgb( $hex ) );

			$l = ( 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b ) ** ( 1 / 3 );
			$m = ( 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b ) ** ( 1 / 3 );
			$s = ( 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b ) ** ( 1 / 3 );
			$a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
			$c = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

			return [ 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s, sqrt( $a * $a + $c * $c ), fmod( rad2deg( atan2( $c, $a ) ) + 360, 360 ) ];
		}

		/**
		 *	@return 	array											The linear channels of an OKLCH colour, which may lie outside 0 to 1
		 */
		private static function _linearOf( float $lightness, float $chroma, float $hue ): array {

			$a = $chroma * cos( deg2rad( $hue ) );
			$b = $chroma * sin( deg2rad( $hue ) );
			$l = ( $lightness + 0.3963377774 * $a + 0.2158037573 * $b ) ** 3;
			$m = ( $lightness - 0.1055613458 * $a - 0.0638541728 * $b ) ** 3;
			$s = ( $lightness - 0.0894841775 * $a - 1.2914855480 * $b ) ** 3;

			return [ 4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s, -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s, -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s ];
		}

		/**
		 *	A colour of the screen: the chroma sinks until the colour can be shown, lightness and hue stay
		 */
		private static function _color( float $lightness, float $chroma, float $hue ): string {

			$clamp = static fn( float $x ): float => max( 0, min( 1, $x ) );

			for( $c = $chroma; $c >= 0; $c -= 0.002 ) {

				$linear = self::_linearOf( $lightness, $c, $hue );

				if( min( $linear ) >= -0.0001 && max( $linear ) <= 1.0001 )
					return self::_hex( array_map( [ self::class, '_gamma' ], array_map( $clamp, $linear ) ) );
			}

			return self::_hex( array_map( [ self::class, '_gamma' ], array_map( $clamp, self::_linearOf( $lightness, 0, $hue ) ) ) );
		}

		/**
		 *	The lightness at which a colour holds its target against every ground: the given one if it does, else the nearest in one
		 *	direction, black or white at the end of it
		 *
		 *	@param		array			$grounds			#rrggbb each
		 *	@param		bool			$darker				Search towards black
		 */
		private static function _solve( float $lightness, float $chroma, float $hue, array $grounds, float $target, bool $darker ): string {

			$holds = static function( string $color ) use ( $grounds, $target ): bool {

				foreach( $grounds as $ground )
					if( self::contrast( $color, $ground ) < $target )
						return false;

				return true;
			};

			$first = self::_color( $lightness, $chroma, $hue );

			if( $holds( $first ) === true )
				return $first;

			for( $l = $lightness; $darker === true ? $l >= 0 : $l <= 1; $l += $darker === true ? -0.005 : 0.005 ) {

				$color = self::_color( $l, $chroma, $hue );

				if( $holds( $color ) === true )
					return $color;
			}

			return $darker === true ? '#000000' : '#ffffff';
		}
	}
}
