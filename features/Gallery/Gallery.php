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
	 *										cropped to exactly the album's size, because a grid
	 *										of pictures that are all different shapes is not a
	 *										grid; the large view is the whole picture scaled into
	 *										a box and keeping its own proportions, because
	 *										cropping is what somebody opening a thumbnail wanted
	 *										undone. Both go through \Nino\Images, so a project
	 *										that registers on \Nino\Images::RENDER renders this
	 *										feature's images too, without this feature knowing.
	 *
	 *										The sizes belong to the album: its record may carry
	 *										the six settings the feature has (SETTINGS) in
	 *										'settings', and an album that carries none - or one
	 *										of them - is made with the feature's, which are also
	 *										what a new album starts with.
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

		// The albums, with each image's alt text and caption. The images are
		// files under /images/gallery/, where every other uploaded image lives
		public const string ALBUMS = '/data/gallery.php';

		// Below \Nino\Images' own upload directory - what process() and fit()
		// take as the deterministic base of a filename
		public const string IMAGE_DIR = 'gallery';

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Gallery/templates';

		// An album key, and the id an image is filed under. The D modifier makes a
		// key end where the string does: $ alone also matches before a final newline,
		// and a key that ends in one names a directory below /images/gallery
		public const string KEY_PATTERN = '/^[a-z][a-z0-9-]*$/D';
		public const string ID_PATTERN 	= '/^[a-f0-9]{16}$/D';

		// How long the key of a new album may be: it names a directory below
		// /images/gallery, and a name the file system cannot take makes every
		// upload into the album fail. The pattern above is what a key is - an
		// album that has a longer one from before goes on working
		public const int MAX_KEY = 64;

		// What one caption - or one alt text - may carry: a line under a
		// picture, not an essay
		public const int MAX_CAPTION = 300;

		// What an album carries in 'settings', in the order the panel draws it: the
		// settings of the feature, which a new album starts with
		public const array SETTINGS = [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'keepRatio', 'columns' ];

		// The byte cap \Nino\Images::_render() refuses an upload above. The
		// constant there is private, and a feature does not reach past the
		// kernel's public API, so the number is mirrored here - and only ever
		// used to say why a refusal happened (see refusal()), never to refuse
		// one. Switch to the kernel's own once it publishes it
		public const int KERNEL_UPLOAD_BYTES = 8 * 1024 * 1024;

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
		 *	Register the shortcode - on a Nino with the Components module the
		 *	component again, with the albums of the project (see
		 *	_offerAlbums()) - and the stylesheet the grid needs. No script:
		 *	what a thumbnail opens into is the Lightbox feature's overlay, and
		 *	this feature only renders the markup it reads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			/*	Nino 1.6 has registered [gallery] as a component from the manifest before
				this runs (\Nino\Features::registerComponents()), and a plain shortcode
				here would take it back from the wrapper that fills in the defaults -
				what is registered here instead is the component again, with the albums
				of the project (see _offerAlbums()), unless a module of the project has
				registered a [gallery] of its own since. Nino 1.5 ignores the manifest
				key and needs the shortcode	*/
			$components = class_exists( '\\Nino\\Modules\\Components' ) === true ? \Nino\Modules\Components::components( $appData ) : [];

			if( isset( $components['gallery'] ) === false )
				\Nino\Html::addShortcode( $appData, 'gallery', [ self::class, 'doShortcode' ] );
			else if( self::_registeredFromManifest( $appData ) === true )
				self::_offerAlbums( $appData, $components['gallery'] );

			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Gallery/assets/gallery.css' );
		}

		/**
		 *	[gallery] as a component again, the schema its manifest declared
		 *	with one attribute changed: the album is a select of the albums
		 *	this project has, because the Builder draws a select as a list to
		 *	choose from and a string as a field to type in. The manifest is
		 *	read without the project's data and cannot name them;
		 *	addComponent() replaces a registration of the same name, and a
		 *	feature's init() runs after the Components module's, which has
		 *	read every manifest by then - so this is the registration that
		 *	stays. Where a project lists the feature ahead of the module the
		 *	manifest's string stays, and nothing else changes.
		 *
		 *	It is asked for only while [gallery] is still what the kernel
		 *	registered from the manifest (see _registeredFromManifest()).
		 *	A module of the project that comes between the two may have
		 *	registered a [gallery] of its own - that is how a project changes
		 *	the markup of a component - and replacing it here would take its
		 *	renderer and its schema away without a word.
		 *
		 *	The renderer does not say whose schema it is: a module may register
		 *	this feature's own renderer under a schema of its own, which is how
		 *	a project changes the schema and nothing else. So the select goes
		 *	over the album of the manifest alone, a string whose default is
		 *	none; any other - a default album of its own, a select of its own,
		 *	no album at all - is the project's and stays as it registered it.
		 *	And addComponent() is called for a schema the kernel accepts only
		 *	(\Nino\Modules\Components::validate() is the check it refuses by):
		 *	it refuses one with an E_USER_ERROR, which ends the request, and
		 *	init() runs in every request there is - the pages of visitors and
		 *	the workbench alike.
		 *
		 *	'' comes first and is the default: no album named draws the first
		 *	album there is. A key that is not among the options - the album
		 *	was deleted since, or the call misspells it - is read as the
		 *	default too, which is how the wrapper reads a select; the
		 *	shortcode alone (Nino 1.5) draws nothing for it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$schema				The schema [gallery] is registered with, as the registry holds it
		 *
		 *	@return 	void
		 */
		private static function _offerAlbums( array &$appData, array $schema ): void {

			// The album of the manifest is a string and has no default; any other is a project's own
			if( ( $schema['attributes']['album']['type'] ?? null ) !== 'string' || ( $schema['attributes']['album']['default'] ?? null ) !== '' )
				return;

			$schema['attributes']['album']['type']		= 'select';
			$schema['attributes']['album']['options']	= array_merge( [ '' ], self::keys( $appData ) );

			/*	init() asks for this only where its registry has [gallery], which is where the
				Components module is. The guard is for PHPStan: against a checkout of a Nino
				before 1.6 it reports a call on a class that is not there	*/
			if( class_exists( '\\Nino\\Modules\\Components' ) === true && is_array( \Nino\Modules\Components::validate( 'gallery', $schema ) ) === true )
				\Nino\Modules\Components::addComponent( $appData, 'gallery', [ self::class, 'componentGallery' ], $schema );
		}

		/**
		 *	Whether [gallery] is registered the way the kernel registered it
		 *	from this feature's manifest, and as nothing besides. A component's
		 *	callback is wrapped, and the wrapper takes the scope of the class
		 *	the renderer belongs to (see
		 *	\Nino\Modules\Components::addComponent(), and how
		 *	\Nino\Features::shortcodes() reads an owner): one of this class is
		 *	this feature's renderer, one of another class is a module's of the
		 *	project, and a second callback beside it was put there by
		 *	something else - neither of them is ours to replace.
		 *
		 *	That is all the renderer can tell: a module of the project may
		 *	register this feature's own renderer too, under a schema of its own,
		 *	and _offerAlbums() looks at the schema for that
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		private static function _registeredFromManifest( array &$appData ): bool {

			$registered = \Nino\Callbacks::registered( $appData, '/nino/html/shortcode/gallery' );

			return count( $registered ) === 1 && $registered[0] instanceof \Closure
				&& ( new \ReflectionFunction( $registered[0] ) )->getClosureScopeClass()?->getName() === self::class;
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
		 *	The key of every album, in the order they are stored - what the
		 *	Builder's select offers. Read off the file without the images of
		 *	every album being checked, because init() asks on every request,
		 *	the pages of visitors included
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function keys( array &$appData ): array {

			$keys = [];

			foreach( (array) \Nino\Filesystem::getFileContent( $appData, self::ALBUMS, [] ) as $entry )
				if( is_array( $entry ) === true && is_string( $entry['key'] ?? null ) === true && preg_match( self::KEY_PATTERN, $entry['key'] ) === 1 )
					$keys[] = $entry['key'];

			return array_values( array_unique( $keys ) );
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
					'alt'			=> self::text( $image['alt'] ?? '' ),
					'caption'	=> self::text( $image['caption'] ?? '' ),
				];
			}

			$album = [
				'key'			=> $key,
				'name'		=> mb_strcut( trim( (string) ( $entry['name'] ?? $key ) ), 0, 120, 'UTF-8' ),
				'images'	=> $images,
			];

			// Only what the record holds: an album without sizes reads the feature's,
			// and an empty map written into it would be a migration nobody asked for
			$settings = self::_settings( $entry['settings'] ?? null );

			return $settings === [] ? $album : $album + [ 'settings' => $settings ];
		}

		/**
		 *	The sizes a record holds, as far as they are some: the names an album
		 *	carries, with a number or a switch for a value. Whether a number is
		 *	within its bounds is for settingsOf(), which has the manifest
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	array
		 */
		private static function _settings( mixed $value ): array {

			$settings = [];

			foreach( self::SETTINGS as $name )
				if( is_array( $value ) === true && ( is_int( $value[$name] ?? null ) === true || is_bool( $value[$name] ?? null ) === true ) )
					$settings[$name] = $value[$name];

			return $settings;
		}

		/**
		 *	One stored alt text or caption read into either of its two shapes:
		 *	a string, which is every language, or a map of locale => string -
		 *	the shape the images cluster plans for an image slot's alt. Anything
		 *	else is '' rather than a cast: a map must never reach (string),
		 *	which php answers with a fatal 'Array to string conversion'.
		 *	Every text is cut to MAX_CAPTION on a character boundary, and a
		 *	locale without one is not kept
		 *
		 *	@param		mixed			$value
		 *
		 *	@return 	string|array
		 */
		public static function text( mixed $value ): string|array {

			$cut = static fn( mixed $text ): string => is_string( $text ) === true ? mb_strcut( trim( $text ), 0, self::MAX_CAPTION, 'UTF-8' ) : '';

			if( is_array( $value ) === false )
				return $cut( $value );

			$map = [];

			foreach( $value as $locale => $text )
				if( is_string( $locale ) === true && $locale !== '' && ( $text = $cut( $text ) ) !== '' )
					$map[$locale] = $text;

			return $map === [] ? '' : $map;
		}

		/**
		 *	What a stored alt text or caption says in one language: the current
		 *	one, else the site's native one, else the first that has anything
		 *	at all - a picture with one translation is better described in the
		 *	wrong language than not at all. A plain string is every language
		 *
		 *	@param		mixed			$value				A string or a locale => string map (see text())
		 *	@param		string		$locale				The wanted locale
		 *	@param		string		$native				The site's native locale
		 *
		 *	@return 	string
		 */
		public static function localized( mixed $value, string $locale, string $native ): string {

			if( is_string( $value ) === true )
				return $value;

			if( is_array( $value ) === false )
				return '';

			foreach( [ $locale, $native ] as $wanted )
				if( is_string( $value[$wanted] ?? null ) === true && $value[$wanted] !== '' )
					return $value[$wanted];

			foreach( $value as $text )
				if( is_string( $text ) === true && $text !== '' )
					return $text;

			return '';
		}

		/**
		 *	A stored alt text or caption with one language changed. A plain
		 *	string is first made into a map by giving every available locale
		 *	that string - so the first translation does not take the others'
		 *	text away - and an empty $text removes the locale again
		 *
		 *	@param		mixed			$value				What is stored now (see text())
		 *	@param		string		$locale				The locale being edited
		 *	@param		string		$text					Its new text
		 *	@param		array 		$available		Every locale the project has
		 *
		 *	@return 	string|array
		 */
		public static function withLocale( mixed $value, string $locale, string $text, array $available ): string|array {

			$value = self::text( $value );

			if( is_string( $value ) === true )
				$value = $value === '' ? [] : array_fill_keys( $available, $value );

			$value[$locale] = $text;

			return self::text( $value );
		}

		/**
		 *	The most bytes php itself will take in one upload: the smaller of
		 *	upload_max_filesize and post_max_size, where each is a limit at
		 *	all (0 is none). A file over it never reaches the panel's action -
		 *	php answers with an error code, or with nothing, see apiUpload()
		 *
		 *	@return 	int												0 where php sets no limit, or one that reads as none
		 */
		public static function uploadLimit(): int {

			$limits = [];

			foreach( [ 'upload_max_filesize', 'post_max_size' ] as $name ) {

				// ini_parse_quantity() raises a warning for a string it cannot read
				// whole - "abc", "1.5M", a number too big - and answers what php
				// itself would make of it. A warning is a 500 for the panel that
				// asked, so it is let pass here: the answer is the engine's
				set_error_handler( static fn(): bool => true );

				try {
					$bytes = ini_parse_quantity( trim( (string) ini_get( $name ) ) );
				} finally {
					restore_error_handler();
				}

				if( $bytes > 0 )
					$limits[] = $bytes;
			}

			return $limits === [] ? 0 : min( $limits );
		}

		/**
		 *	A byte count the way a person reads one, in whole units: the
		 *	largest of GB, MB and KB that holds it exactly, else KB
		 *
		 *	@param		int				$bytes
		 *
		 *	@return 	string
		 */
		public static function formatBytes( int $bytes ): string {

			foreach( [ 'GB' => 1024 ** 3, 'MB' => 1024 ** 2, 'KB' => 1024 ] as $unit => $size )
				if( $bytes >= $size && $bytes % $size === 0 )
					return ( $bytes / $size ). ' '. $unit;

			return $bytes >= 1024 ? round( $bytes / 1024 ). ' KB' : $bytes. ' B';
		}

		/**
		 *	Why store() answered null for these bytes. \Nino\Images only
		 *	says false, and an upload that is refused without a reason is a
		 *	panel that can only say "that did not work" - so this asks the
		 *	same questions in the same order, for the message only. It
		 *	refuses nothing itself
		 *
		 *	@param		string		$bytes				The uploaded bytes
		 *
		 *	@return 	string										'size', 'type', 'pixels' or - where the bytes pass all three, so
		 *																	something after them (gd, a render callback) said no - 'process'
		 */
		public static function refusal( string $bytes ): string {

			if( strlen( $bytes ) > self::KERNEL_UPLOAD_BYTES )
				return 'size';

			$info = @getimagesizefromstring( $bytes );

			if( $info === false || in_array( $info[2], [ IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP ], true ) === false )
				return 'type';

			return $info[0] * $info[1] > \Nino\Images::MAX_SOURCE_PIXELS ? 'pixels' : 'process';
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
		 *	The sizes are those of the album the image goes into (see
		 *	settingsOf()); a key nobody has made an album of yet is made with the
		 *	feature's
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
			$settings	= self::settingsOf( $appData, self::album( $appData, $albumKey ) ?? [] );

			$thumb = \Nino\Images::process( $appData, $bytes, $settings['thumbWidth'], $settings['thumbHeight'], $basePath );

			if( $thumb === false )
				return null;

			$width	= $settings['largeWidth'];
			$height	= $settings['largeHeight'];

			// The whole picture, or the same crop the thumbnail got. Cropping
			// is what somebody opening a thumbnail wanted undone, so the whole
			// picture is the default - but a project whose gallery is a wall of
			// identical frames may want the frame kept
			$large = $settings['keepRatio'] === true
				? \Nino\Images::fit( $appData, $bytes, $width, $height, $basePath )
				: \Nino\Images::process( $appData, $bytes, $width, $height, $basePath );

			if( $large === false ) {
				\Nino\Images::delete( $appData, $thumb );
				return null;
			}

			return [ 'id' => $id, 'thumb' => $thumb, 'large' => $large, 'alt' => '', 'caption' => '' ];
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
		 *	One int setting of the gallery's. The kernel already holds a stored
		 *	value to the manifest's min and max and answers the manifest's
		 *	default for one outside them, so this is the cast and nothing more
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name
		 *	@param		int				$default
		 *
		 *	@return 	int
		 */
		public static function setting( array &$appData, string $name, int $default ): int {

			return (int) \Nino\Features::setting( $appData, 'gallery', $name, $default );
		}

		/**
		 *	The settings of the feature as the defaults of an album: what a new
		 *	album starts with, and what an album that holds no sizes of its own
		 *	is made with
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											SETTINGS => value
		 */
		public static function defaults( array &$appData ): array {

			return [
				'thumbWidth'	=> self::setting( $appData, 'thumbWidth', 500 ),
				'thumbHeight'	=> self::setting( $appData, 'thumbHeight', 500 ),
				'largeWidth'	=> self::setting( $appData, 'largeWidth', 1800 ),
				'largeHeight'	=> self::setting( $appData, 'largeHeight', 1800 ),
				'keepRatio'		=> \Nino\Features::setting( $appData, 'gallery', 'keepRatio', true ) === true,
				'columns'			=> self::setting( $appData, 'columns', 4 ),
			];
		}

		/**
		 *	The range each number of an album may take: the bounds the manifest
		 *	gives the feature's settings, which are the one place they are
		 *	written - checkSettings() holds a posted value to them, and the panel
		 *	limits its inputs by them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											name => [ min, max ], for the numbers
		 */
		public static function ranges( array &$appData ): array {

			$ranges = [];

			foreach( (array) ( \Nino\Features::get( $appData, 'gallery' )['settings'] ?? [] ) as $name => $schema )
				if( in_array( $name, self::SETTINGS, true ) === true && ( $schema['type'] ?? '' ) === 'int' )
					$ranges[$name] = [ $schema['min'] ?? null, $schema['max'] ?? null ];

			return $ranges;
		}

		/**
		 *	The settings one album is made with: those its record holds where
		 *	they pass the bounds of the manifest, the defaults for every one that
		 *	is not there or does not - the way \Nino\Features::settings() treats a
		 *	stored value that no longer validates
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$album				An album, as albums() has it - or [] for none
		 *
		 *	@return 	array											SETTINGS => value
		 */
		public static function settingsOf( array &$appData, array $album ): array {

			$defaults	= self::defaults( $appData );
			$schema		= (array) ( \Nino\Features::get( $appData, 'gallery' )['settings'] ?? [] );
			$held			= \Nino\Features::validateSettings( $schema, self::_settings( $album['settings'] ?? null ) )['values'];

			return array_replace( $defaults, array_intersect_key( $held, $defaults ) );
		}

		/**
		 *	Sizes posted for an album, held to the bounds of the feature's own
		 *	settings - the check its Features screen makes. All or nothing: one
		 *	value outside its bounds is an error and the album is left as it is.
		 *	What is not posted stays what the album has (see settingsOf())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$posted				name => value as it came in
		 *	@param		array 		$album				The album they are for, as albums() has it - or [] for a new one
		 *
		 *	@return 	array											[ 'values' => SETTINGS => value, 'errors' => name => message ]
		 */
		public static function checkSettings( array &$appData, array $posted, array $album = [] ): array {

			$schema = (array) ( \Nino\Features::get( $appData, 'gallery' )['settings'] ?? [] );
			$result = \Nino\Features::validateSettings( $schema, $posted );

			return [
				'values'	=> array_replace( self::settingsOf( $appData, $album ), array_intersect_key( $result['values'], array_flip( self::SETTINGS ) ) ),
				'errors'	=> $result['errors'],
			];
		}

		/**
		 *	[gallery] as the Components module hands it over, the Builder's way:
		 *	every attribute is there, an empty one where nothing was written,
		 *	and doShortcode() reads them as it reads a hand-written call - the
		 *	count of columns only where one was chosen, which is what the
		 *	album's own count is for. A project may register this renderer under
		 *	a schema of its own that declares fewer attributes (see
		 *	_offerAlbums()), and one that is not there is read as one nobody
		 *	wrote: a key missing from the arguments is a warning, and a warning
		 *	ends the request
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments (see \Nino\Modules\Components::dispatch())
		 *
		 *	@return 	string								What doShortcode() renders for them
		 */
		public static function componentGallery( array &$appData, array $args ): string {

			if( ( $args['columns'] ?? '' ) === '' )
				unset( $args['columns'] );

			return self::doShortcode( $appData, $args );
		}

		/**
		 *	Render one album: [gallery] for the first one, [gallery
		 *	album="trip"] for another, [gallery album="trip" columns="3"] to
		 *	override the count of columns the album has.
		 *
		 *	The markup is a list of links the Lightbox feature understands -
		 *	its group is this album, so two galleries on one page stay two
		 *	sets - and nothing else. An alt text and a caption are each one
		 *	string or one string per language (see text()), either may be a
		 *	textfill, and both are rendered as one
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

			// The call's own count, else the album's - which is the feature's where the
			// album holds none (see settingsOf())
			$columns = (int) ( $args['columns'] ?? self::settingsOf( $appData, $album )['columns'] );
			$columns = ( $columns >= 1 && $columns <= 8 ) ? $columns : 4;

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			// The subdirectory read from the configuration rather than through
			// the '[[/nino/dir]]' fill a template would use: that fill is
			// registered mid-request, and a shortcode's output is not rendered
			// again - so a gallery drawn outside that window would carry the
			// literal in every src
			$items = '';

			$locale = \Nino\Locales::getCurrentLocale( $appData );
			$native = \Nino\Locales::getNativeLocale( $appData );

			foreach( $album['images'] as $image ) {

				// Rendered, then escaped: an alt text and a caption are editor
				// text that may be a textfill, and what comes out of the fill
				// engine is still text
				$alt			= $safe( \Nino\Html::renderHtml( $appData, self::localized( $image['alt'], $locale, $native ) ) );
				$caption	= $safe( \Nino\Html::renderHtml( $appData, self::localized( $image['caption'], $locale, $native ) ) );

				// A thumbnail without an alt text of its own is named by its
				// caption, so the link is never a link without a name. The
				// other way round there is no fallback: a picture given an alt
				// text and no caption says so with an empty data-caption, which
				// the Lightbox reads as 'no caption' rather than showing the alt
				// text under the picture a second time
				$items .= str_replace(
					[ '[[large]]', '[[group]]', '[[caption]]', '[[thumb]]', '[[alt]]' ],
					[
						$safe( \Nino\Images::getUrl( $appData, $image['large'] ) ),
						$safe( 'gallery-'. $album['key'] ),
						( $caption !== '' ? ' data-caption="'. $caption. '"' : ( $alt !== '' ? ' data-caption=""' : '' ) ),
						$safe( \Nino\Images::getUrl( $appData, $image['thumb'] ) ),
						$alt !== '' ? $alt : $caption,
					],
					self::template( $appData, 'gallery-item' )
				);
			}

			// The items last: str_replace() works through its arrays in order, so a
			// token after them would be looked for in the markup they put in as well
			return str_replace( [ '[[class]]', '[[columns]]', '[[items]]' ], [ self::_class( $args ), (string) $columns, $items ], self::template( $appData, 'gallery' ) );
		}

		/**
		 *	The class of one's own a call adds to the grid, with the space in
		 *	front of it that the template leaves out - escaped, and with its
		 *	brackets as character references, because the markup is rendered
		 *	once more
		 *
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	string								'' or ' my-class'
		 */
		private static function _class( array $args ): string {

			$class = trim( (string) ( $args['class'] ?? '' ) );

			return $class === '' ? '' : ' '. str_replace( '[', '&#91;', htmlspecialchars( $class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) );
		}

		/**
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway:
			// the one thing this could otherwise be turned into is a read of
			// something outside the feature
			if( preg_match( '/^[a-z][a-z0-9-]*$/D', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}

}
