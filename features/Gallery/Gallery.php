<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Gallery			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Gallery						Any number of image galleries: a grid of thumbnails
	 *										that open full screen, written into a page with
	 *										[gallery album="trip"].
	 *
	 *										Two sizes are made when an image is uploaded and
	 *										neither of them is the upload. The thumbnail is
	 *										cropped to exactly the configured size, because a grid
	 *										of pictures that are all different shapes is not a
	 *										grid; the large view is the whole picture scaled into
	 *										a box and keeping its own proportions, because
	 *										cropping is what somebody opening a thumbnail wanted
	 *										undone. Both go through \Nino\Images, so a project
	 *										that registers on \Nino\Images::RENDER renders this
	 *										feature's images too, without this feature knowing.
	 *
	 *										The original bytes are never stored. What a visitor
	 *										can reach is the largest thing there is - there is no
	 *										full-resolution copy of somebody's camera file
	 *										sitting under /images waiting to be guessed at, and
	 *										no exif riding along with it, because every image is
	 *										re-encoded from pixels.
	 *
	 *										The overlay a thumbnail opens into belongs to the
	 *										Lightbox feature, which this one requires. What is
	 *										rendered here is the markup that feature reads - a
	 *										link with data-lightbox, its group, and a caption -
	 *										so a project could swap the overlay without touching
	 *										the gallery.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Gallery {

		// The albums and their captions. The images are files under
		// /images/gallery/, where every other uploaded image lives
		public const string ALBUMS = '/data/gallery.php';

		// Below \Nino\Images' own upload directory - what process() and fit()
		// take as the deterministic base of a filename
		public const string IMAGE_DIR = 'gallery';

		// An album key, and the id an image is filed under
		public const string KEY_PATTERN = '/^[a-z][a-z0-9-]*$/';
		public const string ID_PATTERN 	= '/^[a-f0-9]{16}$/';

		// What one caption may carry - a line under a picture, not an essay
		public const int MAX_CAPTION = 300;

		/**
		 *	The panel this feature brings along
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Gallery\Admin::class ];
		}

		/**
		 *	Register the shortcode and the stylesheet the grid needs. No
		 *	script: what a thumbnail opens into is the Lightbox feature's
		 *	overlay, and this feature only renders the markup it reads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'gallery', [ self::class, 'doShortcode' ] );

			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Gallery/assets/gallery.css' );
		}

		/**
		 *	Every album this project has, validated, in the order they are
		 *	stored. An album that does not validate is left out rather than
		 *	half-read - the panel writes this file, but a hand edit is a
		 *	project's right
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function albums( array &$appData ): array {

			$albums = [];

			foreach( (array) \Nino\Filesystem::getFileContent( $appData, self::ALBUMS, [] ) as $entry )
				if( is_array( $entry ) === true && ( $album = self::normalize( $entry ) ) !== null )
					$albums[] = $album;

			return $albums;
		}

		/**
		 *	One album by key, or - for an empty key - the first one, which is
		 *	what a bare [gallery] renders
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	array|null
		 */
		public static function album( array &$appData, string $key = '' ): ?array {

			$albums = self::albums( $appData );

			if( $key === '' )
				return $albums[0] ?? null;

			foreach( $albums as $album )
				if( $album['key'] === $key )
					return $album;

			return null;
		}

		/**
		 *	One stored album read into the shape everything here expects, or
		 *	null when it is not one. An image without both of its files is
		 *	dropped: half an image is a broken picture on somebody's page
		 *
		 *	@param		array 		$entry
		 *
		 *	@return 	array|null
		 */
		public static function normalize( array $entry ): ?array {

			$key = is_string( $entry['key'] ?? null ) === true ? $entry['key'] : '';

			if( preg_match( self::KEY_PATTERN, $key ) !== 1 )
				return null;

			$images = [];

			foreach( (array) ( $entry['images'] ?? [] ) as $image ) {

				if( is_array( $image ) === false )
					continue;

				$id			= is_string( $image['id'] ?? null ) === true ? $image['id'] : '';
				$thumb	= is_string( $image['thumb'] ?? null ) === true ? $image['thumb'] : '';
				$large	= is_string( $image['large'] ?? null ) === true ? $image['large'] : '';

				if( preg_match( self::ID_PATTERN, $id ) !== 1 || $thumb === '' || $large === '' )
					continue;

				// The two filenames end up in an src and an href. \Nino\Images
				// only ever hands out names below its own directory, but this
				// file is editable and a name that climbs out of it would be a
				// link to whatever it climbed to
				if( str_contains( $thumb, '..' ) === true || str_contains( $large, '..' ) === true
					|| str_starts_with( $thumb, '/' ) === true || str_starts_with( $large, '/' ) === true )
					continue;

				$images[] = [
					'id'			=> $id,
					'thumb'		=> $thumb,
					'large'		=> $large,
					'caption'	=> substr( trim( (string) ( $image['caption'] ?? '' ) ), 0, self::MAX_CAPTION ),
				];
			}

			return [
				'key'			=> $key,
				'name'		=> substr( trim( (string) ( $entry['name'] ?? $key ) ), 0, 120 ),
				'images'	=> $images,
			];
		}

		/**
		 *	Write the albums back, whole. One short list, and the panel always
		 *	posts what it should be afterwards
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$albums				Normalized albums
		 *
		 *	@return 	bool
		 */
		public static function save( array &$appData, array $albums ): bool {

			return \Nino\Filesystem::putFileContent( $appData, self::ALBUMS, array_values( $albums ) );
		}

		/**
		 *	Make one uploaded image's two sizes and answer the entry that
		 *	describes them.
		 *
		 *	Both go through \Nino\Images, which is what puts them past the
		 *	byte cap, the type check and the pixel cap - and what lets a
		 *	project replace the rendering wholesale by registering on
		 *	\Nino\Images::RENDER. The upload itself is never written anywhere:
		 *	what a visitor can reach is the largest thing that exists.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$albumKey			A validated album key
		 *	@param		string		$bytes				The uploaded bytes
		 *
		 *	@return 	array|null									The image entry, or null when the upload is not one
		 */
		public static function store( array &$appData, string $albumKey, string $bytes ): ?array {

			if( preg_match( self::KEY_PATTERN, $albumKey ) !== 1 )
				return null;

			$id 			= bin2hex( random_bytes( 8 ) );
			$basePath	= self::IMAGE_DIR. '/'. $albumKey. '/'. $id;

			$thumb = \Nino\Images::process( $appData, $bytes,
				self::setting( $appData, 'thumbWidth', 500 ), self::setting( $appData, 'thumbHeight', 500 ), $basePath );

			if( $thumb === false )
				return null;

			$width	= self::setting( $appData, 'largeWidth', 1800 );
			$height	= self::setting( $appData, 'largeHeight', 1800 );

			// The whole picture, or the same crop the thumbnail got. Cropping
			// is what somebody opening a thumbnail wanted undone, so the whole
			// picture is the default - but a project whose gallery is a wall of
			// identical frames may want the frame kept
			$large = \Nino\Features::setting( $appData, 'gallery', 'keepRatio', true ) === true
				? \Nino\Images::fit( $appData, $bytes, $width, $height, $basePath )
				: \Nino\Images::process( $appData, $bytes, $width, $height, $basePath );

			if( $large === false ) {
				\Nino\Images::delete( $appData, $thumb );
				return null;
			}

			return [ 'id' => $id, 'thumb' => $thumb, 'large' => $large, 'caption' => '' ];
		}

		/**
		 *	Take one image's files off disk. Both names came from
		 *	\Nino\Images, and delete() refuses anything that climbs out of its
		 *	own directory regardless
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$image				One normalized image entry
		 *
		 *	@return 	void
		 */
		public static function forget( array &$appData, array $image ): void {

			$seen = [];

			foreach( [ 'thumb', 'large' ] as $size ) {

				$filename = (string) ( $image[$size] ?? '' );

				// Once each: a render hook is free to answer the same file for
				// both sizes, and deleting it twice is one unlink into nothing
				if( $filename === '' || in_array( $filename, $seen, true ) === true )
					continue;

				$seen[] = $filename;
				\Nino\Images::delete( $appData, $filename );
			}
		}

		/**
		 *	One int setting, clamped the way the manifest declares it - a
		 *	hand-edited config.php is not a reason to ask gd for a 40000px
		 *	canvas
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name
		 *	@param		int				$default
		 *
		 *	@return 	int
		 */
		public static function setting( array &$appData, string $name, int $default ): int {

			$value = (int) \Nino\Features::setting( $appData, 'gallery', $name, $default );

			return ( $value >= 40 && $value <= 4000 ) ? $value : $default;
		}

		/**
		 *	Render one album: [gallery] for the first one, [gallery
		 *	album="trip"] for another, [gallery album="trip" columns="3"] to
		 *	override the configured width of the grid.
		 *
		 *	The markup is a list of links the Lightbox feature understands -
		 *	its group is this album, so two galleries on one page stay two
		 *	sets - and nothing else. A caption may be a textfill, and is
		 *	rendered as one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode attributes ( album, columns )
		 *
		 *	@return 	string
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$key		= (string) ( $args['album'] ?? '' );
			$album	= self::album( $appData, preg_match( self::KEY_PATTERN, $key ) === 1 ? $key : '' );

			if( $album === null || $album['images'] === [] )
				return '';

			$columns = (int) ( $args['columns'] ?? \Nino\Features::setting( $appData, 'gallery', 'columns', 4 ) );
			$columns = ( $columns >= 1 && $columns <= 8 ) ? $columns : 4;

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

			// The subdirectory read from the configuration rather than through
			// the '[[/nino/dir]]' fill a template would use: that fill is
			// registered mid-request, and a shortcode's output is not rendered
			// again - so a gallery drawn outside that window would carry the
			// literal in every src
			$html = '<ul class="nino-gallery" style="--nino-gallery-columns:'. $columns. '">';

			foreach( $album['images'] as $image ) {

				// Rendered, then escaped: a caption is editor text that may be a
				// textfill, and what comes out of the fill engine is still text
				$caption = $safe( \Nino\Html::renderHtml( $appData, $image['caption'] ) );

				$html .= '<li class="nino-gallery-item">'
					. '<a class="nino-gallery-link" href="'. $safe( \Nino\Images::getUrl( $appData, $image['large'] ) ). '"'
					. ' data-lightbox="'. $safe( 'gallery-'. $album['key'] ). '"'
					. ( $caption === '' ? '' : ' data-caption="'. $caption. '"' ). '>'
					. '<img class="nino-gallery-thumb" src="'. $safe( \Nino\Images::getUrl( $appData, $image['thumb'] ) ). '"'
					. ' alt="'. $caption. '" loading="lazy" decoding="async">'
					. '</a>'
					. '</li>';
			}

			return $html. '</ul>';
		}
	}

}
