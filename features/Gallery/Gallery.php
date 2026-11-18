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
	 *										[gallery album="trip"] (components/gallery/).
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
	 *										The overlay a thumbnail opens into is this feature's
	 *										own script (assets/gallery.js). What is rendered here
	 *										is the markup it reads - a link with data-lightbox,
	 *										its group, and a caption - which stays readable for
	 *										a link a project writes itself.
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

		// What the panel takes: the albums there may be and the images in one album. An album that holds
		// more from before goes on working - only what is added is held to them
		public const int MAX_ALBUMS = 50;
		public const int MAX_IMAGES = 200;

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

		// The overlay's groups this request has drawn, as group => true. A key of the request
		// alone ('./'): it is not stored, and a new request starts with none (see
		// callbackRequest())
		public const string GROUPS = './gallery/groups';

		// What an album carries in 'settings', in the order the panel draws it: the
		// settings of the feature, which a new album starts with
		public const array SETTINGS = [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'keepRatio', 'columns' ];

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
		 *	The module has one thing to do at the start of a request: forget the
		 *	groups of the one before (see callbackRequest()). The component is in
		 *	components/gallery/ and the kernel registers it; the grid's part and
		 *	the overlay's script and stylesheet are the manifest's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'callbackRequest' ] );
		}

		/**
		 *	A new request: the groups the last one drew are forgotten, so that the
		 *	first gallery of an album is 'gallery-<album>' again (see group()).
		 *	A request is one process where a site is served, and then there is
		 *	nothing to forget; a run that serves several from one $appData - a
		 *	test is one - would carry the count of the first into the second
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request that has just come in
		 *
		 *	@return 	void
		 */
		public static function callbackRequest( array &$appData, array &$request ): void {
			unset( $appData[self::GROUPS] );
		}

		/**
		 *	The lightbox group of one gallery on the page: 'gallery-<album>' for the
		 *	first of an album in this request and 'gallery-<album>-2', '-3'... for
		 *	each of the same album after it. The overlay makes a set of
		 *	every link that carries the same group, so a group that two galleries
		 *	share is one set of both - 'picture 1 / 36' where each holds 18 - and
		 *	the same album twice on a page is two galleries, each a set of its own.
		 *	A suffix is taken only when the group is already drawn: an album whose
		 *	key is 'trip-2' draws 'gallery-trip-2' itself, and the second 'trip'
		 *	before or after it moves on to '-3' rather than join its set
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the album drawn
		 *
		 *	@return 	string											Not yet escaped
		 */
		public static function group( array &$appData, string $key ): string {

			$group	= 'gallery-'. $key;
			$number	= 1;

			while( isset( $appData[self::GROUPS][$group] ) === true )
				$group = 'gallery-'. $key. '-'. ++$number;

			$appData[self::GROUPS][$group] = true;

			return $group;
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

			return self::fromState( \Nino\Filesystem::getFileContent( $appData, self::ALBUMS, [] ) );
		}

		/**
		 *	The albums a stored state holds, validated, in the order they are
		 *	stored. What albums() makes of the file, and what a change made under
		 *	the lock (see change()) starts from
		 *
		 *	@param		mixed			$state				The content of the albums file
		 *
		 *	@return 	array
		 */
		public static function fromState( mixed $state ): array {

			$albums = [];

			foreach( (array) $state as $entry )
				if( is_array( $entry ) === true && ( $album = self::normalize( $entry ) ) !== null )
					$albums[] = $album;

			return $albums;
		}

		/**
		 *	Change the albums, and write them back whole, under the lock of
		 *	their file: the lock is taken, the file read again and handed to
		 *	$change as albums, and what it answers is written - so two people
		 *	on two screens saving two things are two changes one after the other,
		 *	the second starting from what the first left, where a read before
		 *	the lock and a write after it lost one of them. One short list, and
		 *	the panel always posts what it should be afterwards
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		callable	$change				function( array $albums, array &$appData ): ?array - the albums as albums() has them; answers the albums to keep, or null to leave the file as it is
		 *
		 *	@return 	bool											Whether the albums were written; false where the file could not be locked, where $change answered null and where the write failed
		 */
		public static function change( array &$appData, callable $change ): bool {

			return \Nino\Filesystem::mutate( $appData, self::ALBUMS, static function( mixed $state, array &$appData ) use ( $change ): ?array {

				$albums = $change( self::fromState( $state ), $appData );

				return $albums === null ? null : array_values( $albums );
			}, [] );
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

				// The two filenames end up in an src and an href. They are read by the rule of the kernel, a name of
				// \Nino\Images: a project whose render hook names its files elsewhere keeps its pictures. A name
				// that is absolute, climbs out or holds a control character is no picture of an album
				if( self::named( $thumb ) === false || self::named( $large ) === false )
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
		 *	Whether a stored filename can be the name of a picture below /images/: not empty, not absolute, no '..',
		 *	no control character, no backslash. What the page links to is held to it
		 *
		 *	@param		string		$filename			A filename below /images/
		 *
		 *	@return 	bool
		 */
		public static function named( string $filename ): bool {

			return $filename !== '' && str_starts_with( $filename, '/' ) === false && str_contains( $filename, '..' ) === false
				&& preg_match( '/[\x00-\x1F\x7F\\\\]/', $filename ) !== 1;
		}

		/**
		 *	Whether a stored filename lies in the folder of one album, gallery/<key>/: what a deletion for that album
		 *	may take off the disk, and nothing else - a record that was edited by hand names no file of another album
		 *
		 *	@param		string		$filename			A filename below /images/
		 *	@param		string		$albumKey			The album the deletion is for
		 *
		 *	@return 	bool
		 */
		public static function owned( string $filename, string $albumKey ): bool {

			return preg_match( self::KEY_PATTERN, $albumKey ) === 1 && str_starts_with( $filename, self::IMAGE_DIR. '/'. $albumKey. '/' ) === true && self::named( $filename ) === true;
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
		 *	@param		string		$albumKey			The album it is taken out of: only files in its own folder are deleted
		 *
		 *	@return 	void
		 */
		public static function forget( array &$appData, array $image, string $albumKey ): void {

			$seen = [];

			foreach( [ 'thumb', 'large' ] as $size ) {

				$filename = (string) ( $image[$size] ?? '' );

				// Once each: a render hook is free to answer the same file for
				// both sizes, and deleting it twice is one unlink into nothing
				if( self::owned( $filename, $albumKey ) === false || in_array( $filename, $seen, true ) === true )
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
		 *	The albums a project has, as the choices of the attribute album: the first album that there is first
		 *	and as the default, then each album by its key with its name. The register asks for it when the
		 *	first drawing or the first tool needs it (the schema names this method in 'options')
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		The attribute that asks, album
		 *
		 *	@return 	array											key => label, '' first
		 */
		public static function albumOptions( array &$appData, string $attribute ): array {

			$options = [ '' => [ 'de_DE' => 'Erstes Album', 'en_US' => 'First album' ] ];

			foreach( self::albums( $appData ) as $album )
				$options[$album['key']] = $album['name'] !== '' ? $album['name'] : $album['key'];

			return $options;
		}

		/**
		 *	The renderer of the component gallery, [gallery album="trip" columns="3"]: one album's grid of links,
		 *	each with the group and the caption the overlay reads. The first album where none is named, nothing
		 *	for an album that is none or holds no picture.
		 *
		 *	The register has made the attributes ready, so they arrive escaped; the key of the album is read
		 *	back to look it up and only a key of the pattern is looked up. The count of columns is the call's
		 *	where it is one of the choices, else the album's - which is the feature's where the album holds none
		 *	(see settingsOf()). An alt text and a caption are each one string or one string per language (see
		 *	text()), either may be a textfill, and both are written once, escaped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call
		 *	@param		string		$content			Nothing: the component holds nothing
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string											The grid, or '' where there is none to draw
		 */
		public static function componentGallery( array &$appData, array $args, string $content, array $context ): string {

			$key		= html_entity_decode( (string) ( $args['album'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$album	= self::album( $appData, preg_match( self::KEY_PATTERN, $key ) === 1 ? $key : '' );

			if( $album === null || $album['images'] === [] )
				return '';

			// Only a gallery that is drawn takes a group: an album with no pictures or a key
			// nothing has leaves the count where it was
			$group		= self::group( $appData, $album['key'] );
			$settings	= self::settingsOf( $appData, $album );
			$columns	= filter_var( $args['columns'] ?? '', FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 2, 'max_range' => 6 ] ] );
			$columns	= is_int( $columns ) === true ? $columns : $settings['columns'];

			$safe	= static fn( string $value ): string => \Nino\Components::escape( $value );

			$locale	= \Nino\Locales::getCurrentLocale( $appData );
			$native	= \Nino\Locales::getNativeLocale( $appData );

			// The room a thumbnail takes before it has loaded: the size the album's thumbnails are
			// cropped to, which the browser turns into the ratio of the box (see part.css)
			$width	= (string) $settings['thumbWidth'];
			$height	= (string) $settings['thumbHeight'];

			// The words that name a picture with neither an alt text nor a caption, read once
			// for the gallery and only when there is such a picture (see _named())
			$unnamed = null;
			$items = '';

			foreach( $album['images'] as $index => $image ) {

				// An alt text and a caption are editor text that may be a textfill: the
				// fills are put in and the rest is text, so no shortcode of it is run
				$alt			= self::_words( $appData, self::localized( $image['alt'], $locale, $native ) );
				$caption	= self::_words( $appData, self::localized( $image['caption'], $locale, $native ) );

				// A thumbnail without an alt text of its own is named by its caption, so the link is
				// never a link without a name. The other way round there is no fallback: a picture
				// given an alt text and no caption says so with an empty data-caption, which the
				// overlay reads as 'no caption'. A picture with neither is named by its album and its
				// place in it (see _named()), and says it has no caption in the same way
				$name = $alt !== '' ? $alt : $caption;

				if( $name === '' ) {
					$unnamed ??= \Nino\Html::renderHtml( $appData, '[[/feature/gallery/image/unnamed]]' );
					$name = self::_named( \Nino\Components::escape( $unnamed ), $safe( $album['name'] ), $index + 1 );
				}

				$items .= str_replace(
					[ '[[large]]', '[[group]]', '[[caption]]', '[[thumb]]', '[[width]]', '[[height]]', '[[alt]]' ],
					[
						$safe( \Nino\Images::getUrl( $appData, $image['large'] ) ),
						$safe( $group ),
						$caption,
						$safe( \Nino\Images::getUrl( $appData, $image['thumb'] ) ),
						$width,
						$height,
						$name,
					],
					\Nino\Feature::template( $appData, self::TEMPLATES, 'gallery-item' )
				);
			}

			// The items go in as a token of the renderer: the register fills the template in one pass and reads nothing it put in
			return \Nino\Components::fill( $appData, 'gallery', $args, $content, [
				'[[columns]]'			=> (string) $columns,
				'[[thumbWidth]]'	=> $width,
				'[[thumbHeight]]'	=> $height,
				'[[items]]'				=> $items,
			] );
		}

		/**
		 *	An editor's text as the page shows it: the fills in it put in as the fill
		 *	pass puts them - a fill is a word of the project's - and nothing else
		 *	read, so a shortcode in the text is characters and calls nothing. Then
		 *	escaped for text and for an attribute, with every '[' written as a
		 *	character reference, because what this feature returns is rendered once
		 *	more
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$text					What the editor wrote
		 *
		 *	@return 	string									Ready for markup
		 */
		private static function _words( array &$appData, string $text ): string {

			$put = static function( array $match ) use ( &$appData ): string {
				return \Nino\Html::resolveTextfill( $appData, $match[1] ) ?? $match[0];
			};

			return \Nino\Components::escape( (string) preg_replace_callback( '#\[\[(/[a-zA-Z0-9/_.-]*)\]\]#', $put, $text ) );
		}

		/**
		 *	The name of a picture that has neither an alt text nor a caption: the
		 *	name of its album and its place in it, 'Trip, picture 3', made of the
		 *	words /feature/gallery/image/unnamed - which the feature's unit
		 *	delivers in both languages with a %s each for the name and the number,
		 *	and which componentGallery() renders once through the fill engine. Where a
		 *	project has no word for the key the key shows as every key without a
		 *	word does. The words are the project's to change in the Text panel and
		 *	so may carry too few or too many %s: each is filled once, in order, and
		 *	one that is left over is nothing, where sprintf() would end the request
		 *	on a missing one
		 *
		 *	@param		string		$words				The rendered words
		 *	@param		string		$album				The name of the album
		 *	@param		int				$number				Its place in the album, counted from 1
		 *
		 *	@return 	string											Ready for markup: both words are escaped when they come in
		 */
		private static function _named( string $words, string $album, int $number ): string {

			$values = [ $album, (string) $number ];

			// By reference: an arrow function would take the list by value and hand every %s the first
			return (string) preg_replace_callback( '/%s/', static function() use ( &$values ): string {
				return (string) array_shift( $values );
			}, $words );
		}
	}

}
