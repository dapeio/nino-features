<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Sign\Signature	The picture of a signature: the one a member drew, checked and written anew, and the one a name is made into
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Sign {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Sign\Signature		A signature is a PNG. A member either draws it on the canvas of the page, which posts it as a data URL, or
	 *										types a name, which is drawn here with the built-in font 5 of GD, three times as large, on a transparent
	 *										ground. Both are bytes the Pdf feature can place in the box of a signature field.
	 *
	 *										What comes from a browser is untrusted and is never kept as it came: the data URL has to be a PNG, its
	 *										decoded bytes at most 512 KB, its size - read from the header before anything is decoded - a measure the
	 *										pad makes (from 120 by 40 to 1200 by 600 pixels, wider than high, at most six times); only then GD decodes
	 *										it, and something has to be drawn on it - a canvas nobody touched is a transparent picture, and no
	 *										signature. What is kept is what GD writes of the pixels it decoded: no chunk of text, no profile, no byte
	 *										behind the image. A typed name is cleaned of control characters, whitespace is one space, and it is at
	 *										most 60 characters; the font has the letters of ISO 8859-1, and a character it lacks is a question mark.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Signature {

		public const string PREFIX = 'data:image/png;base64,';

		// The measure of the pad: the canvas of the page is 600 by 200
		public const int MIN_WIDTH = 120;
		public const int MIN_HEIGHT = 40;
		public const int MAX_WIDTH = 1200;
		public const int MAX_HEIGHT = 600;
		public const int MAX_BYTES = 524288;
		public const int MAX_NAME = 60;

		// The built-in font GD draws a name with, the pixels one of its letters takes, and how many times larger the picture is made
		private const int FONT = 5;
		private const int LETTER_WIDTH = 9;
		private const int LETTER_HEIGHT = 15;
		private const int SCALE = 3;

		/**
		 *	The picture a member drew
		 *
		 *	@param		string		$url					The data URL the canvas made: data:image/png;base64,...
		 *
		 *	@return		string|null								The bytes of a PNG that GD wrote of the pixels it read; null for anything that is not a PNG of the measure the page makes, that has nothing drawn on it, or is too large
		 */
		public static function drawn( string $url ): ?string {

			// The data URL as base64 is a third longer than the bytes it holds: what is longer than the limit of the bytes, so counted, is not decoded at all
			if( str_starts_with( $url, self::PREFIX ) === false || strlen( $url ) > intdiv( self::MAX_BYTES * 4, 3 ) + strlen( self::PREFIX ) + 4 )
				return null;

			$bytes = base64_decode( substr( $url, strlen( self::PREFIX ) ), true );

			if( $bytes === false || $bytes === '' )
				return null;

			// The header says the type and the measure; nothing is decoded before both are right
			$info = @getimagesizefromstring( $bytes );

			if( $info === false || $info[2] !== IMAGETYPE_PNG || $info[0] < self::MIN_WIDTH || $info[1] < self::MIN_HEIGHT || $info[0] > self::MAX_WIDTH || $info[1] > self::MAX_HEIGHT || $info[0] < $info[1] || $info[0] > $info[1] * 6 )
				return null;

			$image = @imagecreatefromstring( $bytes );

			if( $image === false )
				return null;

			if( imageistruecolor( $image ) === false && @imagepalettetotruecolor( $image ) === false )
				return null;

			if( self::_inked( $image ) === false )
				return null;

			imagealphablending( $image, false );
			imagesavealpha( $image, true );

			ob_start();
			$written = imagepng( $image );
			$kept = (string) ob_get_clean();

			return $written === true && $kept !== '' ? $kept : null;
		}

		/**
		 *	A name as the text of a signature: cleaned, one line of single spaces
		 *
		 *	@param		string		$name					As typed
		 *
		 *	@return		string										'' for a name with nothing in it
		 */
		public static function clean( string $name ): string {

			// A byte that is not UTF-8 is the replacement character, as the engine of the Pdf feature does it
			$substitute = mb_substitute_character();
			mb_substitute_character( 0xFFFD );
			$name = (string) mb_convert_encoding( $name, 'UTF-8', 'UTF-8' );
			mb_substitute_character( $substitute );

			return trim( (string) preg_replace( '/[\s\p{C}]+/u', ' ', $name ) );
		}

		/**
		 *	The picture of a typed name
		 *
		 *	@param		string		$name					As typed
		 *
		 *	@return		string|null								The bytes of the PNG; null for a name with nothing in it or one of more than 60 characters
		 */
		public static function typed( string $name ): ?string {

			$text = self::clean( $name );

			if( $text === '' || mb_strlen( $text, 'UTF-8' ) > self::MAX_NAME )
				return null;

			// Letters the font does not have are a question mark - the substitute character is the default's, and put back
			$substitute = mb_substitute_character();
			mb_substitute_character( 0x3F );
			$latin = (string) mb_convert_encoding( $text, 'ISO-8859-1', 'UTF-8' );
			mb_substitute_character( $substitute );

			$width = self::LETTER_WIDTH * strlen( $latin );
			$small = imagecreatetruecolor( $width, self::LETTER_HEIGHT );
			$large = imagecreatetruecolor( $width * self::SCALE, self::LETTER_HEIGHT * self::SCALE );

			if( $small === false || $large === false )
				return null;

			foreach( [ $small, $large ] as $ground ) {
				imagealphablending( $ground, false );
				imagesavealpha( $ground, true );
				imagefill( $ground, 0, 0, (int) imagecolorallocatealpha( $ground, 0, 0, 0, 127 ) );
			}

			imagestring( $small, self::FONT, 0, 0, $latin, (int) imagecolorallocatealpha( $small, 20, 30, 90, 0 ) );
			imagecopyresampled( $large, $small, 0, 0, 0, 0, $width * self::SCALE, self::LETTER_HEIGHT * self::SCALE, $width, self::LETTER_HEIGHT );

			ob_start();
			$written = imagepng( $large );
			$bytes = (string) ob_get_clean();

			return $written === true && $bytes !== '' ? $bytes : null;
		}

		/**
		 *	Whether anything is drawn on a picture: some pixel of it, seen at a small size, is not fully transparent
		 *
		 *	@param		\GdImage	$image				A true-colour picture
		 *
		 *	@return		bool
		 */
		private static function _inked( \GdImage $image ): bool {

			$small = imagescale( $image, min( imagesx( $image ), 160 ), min( imagesy( $image ), 80 ), IMG_BILINEAR_FIXED );

			if( $small === false )
				return false;

			for( $y = 0; $y < imagesy( $small ); $y++ )
				for( $x = 0; $x < imagesx( $small ); $x++ )
					if( ( ( imagecolorat( $small, $x, $y ) >> 24 ) & 0x7F ) < 127 )
						return true;

			return false;
		}
	}
}
