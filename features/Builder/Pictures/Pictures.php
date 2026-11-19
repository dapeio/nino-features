<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// The pictures of a page that are chosen before they are saved. A picture is uploaded at once - through \Nino\Images, which checks and makes it -
	// under a name of its own that no slot has yet: <slot>.s<12 hex>.<W>x<H>.<ext>. A save gives the file to the slot it was made for; until then
	// it is the page's draft and nobody's picture. What nothing has been given is taken away: by the person who throws the draft away, and by every
	// open, upload and save of the page once it is older than TTL, so that no file stays that no slot ever read
	class Pictures {

		// How long a picture that no slot has stays, in seconds
		public const int TTL = 86400;

		// The largest side of a slot a person makes, in pixels: two of them are fewer pixels than the kernel lets a picture have (\Nino\Images::MAX_SOURCE_PIXELS), which a test holds
		public const int MAX_SIDE = 4096;

		/**
		 *	Whether a file is one a picture of this slot was uploaded as: the name of the slot, the mark of an upload, the size and the extension the kernel writes
		 *
		 *	@param		string		$uri					The slot, '/template/page-home/hero/background'
		 *	@param		string		$file					Below /images/
		 *
		 *	@return 	bool
		 */
		public static function isStaged( string $uri, string $file ): bool {
			return preg_match( '#^'. preg_quote( ltrim( $uri, '/' ), '#' ). '\.s[0-9a-f]{12}\.[0-9]+x[0-9]+\.(?:webp|png|jpg|svg)$#D', $file ) === 1;
		}

		/**
		 *	Make the picture of a slot from the bytes of an upload, as the kernel makes the picture of any slot, under a name no slot has
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					A slot of the namespace of the page
		 *	@param		string		$bytes
		 *	@param		int				$width				The size of the slot
		 *	@param		int				$height
		 *
		 *	@return 	string|false							The file below /images/; false where the kernel made none, or made one that is not an upload's name
		 */
		public static function stage( array &$appData, string $uri, string $bytes, int $width, int $height ): string|false {

			$file = \Nino\Images::process( $appData, $bytes, $width, $height, ltrim( $uri, '/' ). '.s'. bin2hex( random_bytes( 6 ) ) );

			if( $file === false )
				return false;

			if( self::isStaged( $uri, $file ) === false ) {
				\Nino\Images::delete( $appData, $file );
				return false;
			}

			return $file;
		}

		/**
		 *	The picture a block has of its own for a slot, made the file of the slot of the page it is put in: a raster picture through the kernel as an upload is made, a svg as
		 *	the picture that was cleaned, under a name that comes of the slot and the bytes - the same picture is the same file, so that a draft that is drawn again makes no more
		 *	files. The bytes are what Library::picture() read from the block, never anything of a request
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					A slot of the namespace of the page
		 *	@param		array			$picture			As Library::picture() answers
		 *	@param		int				$width				The size of the slot
		 *	@param		int				$height
		 *
		 *	@return 	string|false							The file below /images/; false where the kernel made none
		 */
		public static function stageOwn( array &$appData, string $uri, array $picture, int $width, int $height ): string|false {

			if( \Nino\Images::isSlotUri( $uri ) === false || $width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE || is_string( $picture['bytes'] ?? null ) === false )
				return false;

			$base = ltrim( $uri, '/' ). '.s'. substr( hash( 'sha256', $uri. "\0". $picture['bytes'] ), 0, 12 );

			if( ( $picture['type'] ?? '' ) === 'svg' ) {

				$file = $base. '.'. $width. 'x'. $height. '.svg';

				return ( \Nino\Images::read( $appData, $file ) !== false || \Nino\Images::restore( $appData, $file, $picture['bytes'] ) === true ) && self::isStaged( $uri, $file ) === true ? $file : false;
			}

			foreach( [ 'webp', 'png', 'jpg' ] as $type )
				if( \Nino\Images::read( $appData, $base. '.'. $width. 'x'. $height. '.'. $type ) !== false )
					return $base. '.'. $width. 'x'. $height. '.'. $type;

			$file = \Nino\Images::process( $appData, $picture['bytes'], $width, $height, $base );

			if( $file === false )
				return false;

			if( self::isStaged( $uri, $file ) === false ) {
				\Nino\Images::delete( $appData, $file );
				return false;
			}

			return $file;
		}

		/**
		 *	The slots a request names that are the page's: each is a slot of its namespace, made from a label and a size where the page has none, and given the
		 *	file that was uploaded for it where it names one. Only a slot that a source of the tree reads is made
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$state				As Store::state() answers
		 *	@param		mixed			$pictures			uri => [ label, width, height, file ]
		 *	@param		array			$images				The keys a source of the tree reads as a picture, key => true
		 *	@param		array			&$slots				(reference) The slots to write, uri => slot
		 *	@param		array			&$shown				(reference) The slots as the draft shows them
		 *
		 *	@return 	string|null								null where all is as it may be; 'picture' where a request names what it may not, 'picture_gone' where the file that was made for a slot is not there any more
		 */
		public static function accept( array &$appData, string $template, array $state, mixed $pictures, array $images, array &$slots, array &$shown ): ?string {

			if( is_array( $pictures ) === false || count( $pictures ) > Store::MAX_KEYS )
				return 'picture';

			foreach( $pictures as $uri => $spec ) {

				if( is_string( $uri ) === false || is_array( $spec ) === false || Tree::own( $uri, $template ) === false || \Nino\Images::isSlotUri( $uri ) === false )
					return 'picture';

				if( isset( $images[$uri] ) === false )
					continue;

				$slot = $slots[$uri] ?? $state['slots'][$uri] ?? null;

				if( $slot === null ) {

					$label = is_string( $spec['label'] ?? null ) === true ? \Nino\Images::cleanAlt( $spec['label'] ) : false;
					$width = $spec['width'] ?? null;
					$height = $spec['height'] ?? null;

					if( $label === false || $label === '' || mb_strlen( $label ) > 200 || is_int( $width ) === false || is_int( $height ) === false
						|| $width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE )
						return 'picture';

					$slot = [ 'label' => $label, 'width' => $width, 'height' => $height, 'filename' => null ];
				}

				$file = $spec['file'] ?? null;

				if( $file !== null ) {

					if( is_string( $file ) === false || self::isStaged( $uri, $file ) === false )
						return 'picture';

					// What was uploaded a day ago and given to nobody is gone: the person uploads it again
					if( \Nino\Images::read( $appData, $file ) === false )
						return 'picture_gone';

					$slot['filename'] = $file;
				}

				if( ( $state['slots'][$uri] ?? null ) !== $slot ) {
					$slots[$uri] = $slot;
					$shown[$uri] = $slot;
				}
			}

			return null;
		}

		/**
		 *	The files of uploads of a page that no slot reads - neither a slot now nor the one of the last state - and that are older than TTL, or, where $all
		 *	names files, those of them: taken away. Nothing else is looked at, and a file of another slot or of another page is never one of them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array|null								$all					Files below /images/ that are given up now, whatever their age; null for the old ones
		 *
		 *	@return 	int												How many files were taken away
		 */
		public static function sweep( array &$appData, string $template, ?array $all = null ): int {

			$dir = \Nino\Filesystem::path( $appData, '/images/template/'. $template );

			if( $dir === '' || Store::named( $template ) === null || is_dir( $dir ) === false )
				return 0;

			$read = [];

			foreach( array_merge( array_values( (array) ( $appData['/nino/html/images'] ?? [] ) ), array_values( (array) ( Store::previous( $appData, $template )['slots'] ?? [] ) ) ) as $slot )
				if( is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true )
					$read[$slot['filename']] = true;

			$gone = 0;

			foreach( (array) glob( $dir. '/*/*.s*' ) as $path ) {

				$file = 'template/'. $template. '/'. basename( dirname( (string) $path ) ). '/'. basename( (string) $path );

				if( preg_match( '#^template/[a-z0-9-]+/[a-z0-9_-]+/[a-z0-9_-]+\.s[0-9a-f]{12}\.[0-9]+x[0-9]+\.(?:webp|png|jpg|svg)$#D', $file ) !== 1 || isset( $read[$file] ) === true || is_file( (string) $path ) === false )
					continue;

				if( $all === null ? time() - (int) filemtime( (string) $path ) < self::TTL : in_array( $file, $all, true ) === false )
					continue;

				\Nino\Images::delete( $appData, $file );
				$gone++;
			}

			return $gone;
		}

		/**
		 *	The slots of a page for the screen: for each the label, the size and the address of its picture
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$slots				uri => slot, as Store::state() has them
		 *
		 *	@return 	array											uri => [ label, width, height, url ]
		 */
		public static function shown( array &$appData, array $slots ): array {

			$out = [];

			foreach( $slots as $uri => $slot )
				$out[$uri] = [ 'label' => (string) ( $slot['label'] ?? '' ), 'width' => (int) ( $slot['width'] ?? 0 ), 'height' => (int) ( $slot['height'] ?? 0 ), 'url' => is_string( $slot['filename'] ?? null ) === true && $slot['filename'] !== '' ? \Nino\Images::getUrl( $appData, $slot['filename'] ) : '' ];

			return $out;
		}
	}
}
