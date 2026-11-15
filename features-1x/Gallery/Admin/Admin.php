<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Nino\Modules\Gallery\Admin	The /_admin panel of the Gallery feature
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Gallery {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Gallery\Admin			The albums a project has, and one album's images on a
	 *										screen of its own: upload, alt text and caption per
	 *										language, order, delete - and the sizes the album's
	 *										pictures are made with. A new album is made on a page
	 *										of its own, drawn into the same pane: a key, a name
	 *										and those sizes.
	 *										Three levels, two panes - the page of a new album and
	 *										an album's page share one - with the workbench's
	 *										own back link between them.
	 *
	 *										\Nino\Modules\Gallery (Gallery.php beside this) owns
	 *										the shape of the file and the making of the two sizes;
	 *										this reads and writes through that class, so what the
	 *										panel stores is what the shortcode renders. A refusal
	 *										a person can cause answers with a code, and the script
	 *										says it in the interface language with the sentence
	 *										the panel's text has for it.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/gallery/manage';

		// What the page that makes an album is addressed by (#gallery/new): an album with
		// that key would be opened in its place by a reload, so none is made with it
		private const string RESERVED_KEY = 'new';

		// The panel's word for each of an album's settings, for the sentence that
		// names the ones a request got wrong (see _named())
		private const array LABELS = [
			'thumbWidth'	=> '/_admin/gallery/label/thumb-width',
			'thumbHeight'	=> '/_admin/gallery/label/thumb-height',
			'largeWidth'	=> '/_admin/gallery/label/large-width',
			'largeHeight'	=> '/_admin/gallery/label/large-height',
			'keepRatio'		=> '/_admin/gallery/label/keep-ratio',
			'columns'			=> '/_admin/gallery/label/columns',
		];

		public static function actions(): array {
			return [
				'gallery/list'				=> [ self::class, 'apiList' ],
				'gallery/album-save'	=> [ self::class, 'apiAlbumSave' ],
				'gallery/album-delete'=> [ self::class, 'apiAlbumDelete' ],
				'gallery/upload'			=> [ self::class, 'apiUpload' ],
				'gallery/image-save'	=> [ self::class, 'apiImageSave' ],
				'gallery/image-delete'=> [ self::class, 'apiImageDelete' ],
				'gallery/reorder'			=> [ self::class, 'apiReorder' ],
			];
		}

		// No group is named here: a panel a feature brought always lands in
		// the rail's own "features" group (see \Nino\Admin\Panels)
		public static function nav(): array {
			return [ 'gallery', '/_admin/nav/gallery', 55 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-images-icon lucide-images"><path d="M18 22H4a2 2 0 0 1-2-2V6"/><path d="m22 13-1.296-1.296a2.41 2.41 0 0 0-3.408 0L11 18"/><circle cx="12" cy="8" r="2"/><rect width="16" height="16" x="6" y="2" rx="2"/></svg>';
		}

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function panes(): array {
			return [ 'gallery-list', 'gallery-album' ];
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function summary( array &$appData ): array {

			$images = 0;
			foreach( \Nino\Modules\Gallery::albums( $appData ) as $album )
				$images += count( $album['images'] );

			return [ 'value' => $images, 'label' => '/_admin/gallery/label/images' ];
		}

		public static function log( string $action, array $data ): string {

			$key = is_string( $data['album'] ?? null ) === true ? $data['album'] : '';

			return match( $action ) {
				'gallery/album-save'		=> 'Save gallery album "'. $key. '"',
				'gallery/album-delete'	=> 'Delete gallery album "'. $key. '" and its images',
				'gallery/upload'				=> 'Add an image to gallery album "'. $key. '"',
				'gallery/image-delete'	=> 'Delete an image of gallery album "'. $key. '"',
				default									=> '',
			};
		}

		/**
		 *	Every album with its images, and the urls the panel shows them at -
		 *	plus what the screen needs to edit them: the languages an alt text
		 *	and a caption can be written in, how big a file php takes, and what a
		 *	new album starts with. Each album carries the sizes it is made with
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$limit = \Nino\Images::limits()['php'];

			\Nino\Http::ok( $request, [
				'albums'	=> self::_albums( $appData ),
				// What a new album starts with, and the range each number may take:
				// the form of a new album is filled from the one, and its inputs are
				// limited by the other the way the server will hold them to it
				'defaults'	=> \Nino\Modules\Gallery::defaults( $appData ),
				'ranges'	=> \Nino\Modules\Gallery::ranges( $appData ),
				'locales'	=> \Nino\Locales::getAvailableLocales( $appData ),
				'native'	=> \Nino\Locales::getNativeLocale( $appData ),
				// The one the screen opens in: what was last chosen in any panel
				// that has a language switch, so it does not start over
				'selectedLocale'	=> \Nino\Admin\Admin::sessionLocale( $appData ),
				// What php takes in one upload, so the panel can say which file is
				// too big before it is sent - a file over post_max_size is not
				// refused by this panel at all, php drops the whole request and
				// what comes back is the csrf check's 403 (see apiUpload())
				'limits'	=> [ 'bytes' => $limit, 'text' => \Nino\Modules\Gallery::formatBytes( $limit ) ],
				// How long an alt text or a caption may be: what the server keeps of
				// one is cut to it, and the panel compares with what it keeps
				'maxText'	=> \Nino\Modules\Gallery::MAX_CAPTION,
				// How long the key of a new album may be: the field that asks for it is
				// limited by it, and the server holds a key to it the same way
				'maxKey'	=> \Nino\Modules\Gallery::MAX_KEY,
			] );
		}

		/**
		 *	Create one album, rename it, or change the sizes it is made with -
		 *	each only where the request carries it. The images stay with the
		 *	album: a rename is the album's name, not its key, and the key is what
		 *	the files are filed under.
		 *
		 *	A request that says it is new is a creation, and a key that is taken
		 *	is refused rather than renamed: the page of a new album has no say
		 *	over the sizes of one that exists. 'new' is the address of that page
		 *	and is refused as the key of an album that is not there yet, while
		 *	one that has it keeps working - and so is a key longer than
		 *	\Nino\Modules\Gallery::MAX_KEY: only a new album is held to it. The
		 *	sizes are held to the bounds of the feature's own settings (see
		 *	\Nino\Modules\Gallery::checkSettings()), all of them or none; a new
		 *	album without any starts with the feature's.
		 *
		 *	What depends on the albums there are - whether the key is taken, what
		 *	the sizes of an album are now - is decided under the lock of the file
		 *	(see \Nino\Modules\Gallery::change()), where the albums are read
		 *	again: two people creating the album of one key are two requests one
		 *	after the other, and the second is refused as taken
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiAlbumSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data		= \Nino\Admin\Admin::postData();
			$key		= self::_key( $data['album'] ?? null );
			// mb_strcut, not substr: the cap is in bytes, and a cut through the
			// middle of a multibyte character leaves a sequence that is not
			// utf-8 - which json_encode() answers with false, so the panel's
			// whole answer came back empty and every screen of it stopped
			$name		= mb_strcut( trim( \Nino\Admin\Admin::postString( $data, 'name' ) ), 0, 120, 'UTF-8' );
			$isNew	= ( $data['isNew'] ?? false ) === true;
			$posted	= is_array( $data['settings'] ?? null ) === true ? $data['settings'] : null;

			if( $key === '' ) {
				\Nino\Http::fail( $request, 400, 'an album key is a slug: lower case, starting with a letter', 'gallery_key' );
				return;
			}

			$refusal = null;

			$written = \Nino\Modules\Gallery::change( $appData, static function( array $albums, array &$appData ) use ( $key, $name, $isNew, $posted, &$refusal ): ?array {

				$index = self::_indexOf( $albums, $key );

				if( $key === self::RESERVED_KEY && $index === null ) {
					$refusal = [ 400, 'the key "new" is the address of the page that makes an album', 'gallery_reserved' ];
					return null;
				}

				if( $index === null && strlen( $key ) > \Nino\Modules\Gallery::MAX_KEY ) {
					$refusal = [ 400, 'the key of a new album has at most '. \Nino\Modules\Gallery::MAX_KEY. ' characters', 'gallery_long', [ \Nino\Modules\Gallery::MAX_KEY ] ];
					return null;
				}

				if( $index !== null && $isNew === true ) {
					$refusal = [ 400, 'there is an album with this key already', 'gallery_taken' ];
					return null;
				}

				$settings = null;

				if( $posted !== null ) {

					$checked = \Nino\Modules\Gallery::checkSettings( $appData, $posted, $index === null ? [] : $albums[$index] );

					if( $checked['errors'] !== [] ) {
						$refusal = [ 400, 'these sizes are outside what is allowed: '. implode( ', ', array_keys( $checked['errors'] ) ), 'gallery_settings',
							[ self::_named( $appData, array_keys( $checked['errors'] ) ) ] ];
						return null;
					}

					$settings = $checked['values'];
				}

				if( $index === null )
					$albums[] = [ 'key' => $key, 'name' => $name !== '' ? $name : $key, 'images' => [], 'settings' => $settings ?? \Nino\Modules\Gallery::defaults( $appData ) ];
				else {
					$albums[$index]['name'] = $name !== '' ? $name : $albums[$index]['name'];

					if( $settings !== null )
						$albums[$index]['settings'] = $settings;
				}

				return $albums;
			} );

			if( $refusal !== null )
				\Nino\Http::fail( $request, ...$refusal );
			else if( $written === false )
				\Nino\Http::fail( $request, 500, 'the albums could not be written' );
			else
				\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	Delete one album, and every image in it. Unlike a form's
		 *	submissions, an album's images are the album: nothing else points
		 *	at them, and leaving them behind is leaving files nobody can reach
		 *	from the workbench again
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiAlbumDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key		= self::_key( \Nino\Admin\Admin::postData()['album'] ?? null );
			$gone		= null;
			$refusal	= null;

			$written = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( $key, &$gone, &$refusal ): ?array {

				$kept = [];

				foreach( $albums as $album )
					$album['key'] === $key ? $gone = $album : $kept[] = $album;

				if( $gone === null ) {
					$refusal = [ 400, 'there is no album with this key', 'gallery_album' ];
					return null;
				}

				return $kept;
			} );

			if( $refusal !== null ) {
				\Nino\Http::fail( $request, ...$refusal );
				return;
			}

			if( $written === false ) {
				\Nino\Http::fail( $request, 500, 'the albums could not be written' );
				return;
			}

			// After the write, never before: a file removed for a record that
			// then failed to save is a gallery of broken pictures
			foreach( $gone['images'] as $image )
				\Nino\Modules\Gallery::forget( $appData, $image );

			\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	Add one uploaded image to an album - two sizes made, the upload
		 *	itself never written anywhere. The sizes are made before the file of
		 *	the albums is locked, which is for the short time it takes to add the
		 *	entry: an album that is gone by then, or a write that fails, takes
		 *	the two files off the disk again
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiUpload( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key = self::_key( \Nino\Admin\Admin::postData()['album'] ?? null );

			if( $key === '' || self::_indexOf( \Nino\Modules\Gallery::albums( $appData ), $key ) === null ) {
				\Nino\Http::fail( $request, 400, 'there is no album with this key', 'gallery_album' );
				return;
			}

			$error = isset( $_FILES['file'] ) === true ? ( $_FILES['file']['error'] ?? 1 ) : 1;

			// php's own ceiling, with the number. A file over post_max_size never
			// gets here: php answers with an empty $_POST, so the csrf check has
			// nothing to compare and refuses first - which is why the panel checks
			// the size itself before it sends anything
			$limit = \Nino\Images::limits()['php'];

			if( ( $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE ) && $limit > 0 ) {
				$text = \Nino\Modules\Gallery::formatBytes( $limit );
				\Nino\Http::fail( $request, 400, 'the file is larger than php takes in one upload ('. $text. ')', 'gallery_size', [ $text ] );
				return;
			}

			if( $error !== UPLOAD_ERR_OK ) {
				\Nino\Http::fail( $request, 400, 'the file could not be read', 'gallery_upload' );
				return;
			}

			$bytes = @file_get_contents( (string) $_FILES['file']['tmp_name'] );

			if( is_string( $bytes ) === false ) {
				\Nino\Http::fail( $request, 400, 'the file could not be read', 'gallery_upload' );
				return;
			}

			$image = \Nino\Modules\Gallery::store( $appData, $key, $bytes );

			if( $image === null ) {

				/*	\Nino\Images only says false, and an upload that is refused
					without a reason is a panel that can only say "that did not
					work": the kernel says why in a code its words exist for. What
					passes all three checks and is refused after them - gd, a render
					callback - is this feature's	*/
				$rejected = \Nino\Images::reject( $bytes );

				if( $rejected === null )
					\Nino\Http::fail( $request, 400, 'the server could not make the two sizes from this image', 'gallery_process' );
				else
					\Nino\Http::fail( $request, 400, match( $rejected['code'] ) {
						'image_too_large'	=> 'the file is larger than the kernel takes ('. $rejected['params'][0]. ' MB)',
						'image_type'			=> 'the file is not a JPEG, PNG, GIF or WebP image',
						default						=> 'the image has more than '. $rejected['params'][0]. ' megapixels',
					}, $rejected['code'], $rejected['params'] );

				return;
			}

			$refusal = null;

			$written = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( $key, $image, &$refusal ): ?array {

				$index = self::_indexOf( $albums, $key );

				if( $index === null ) {
					$refusal = [ 400, 'there is no album with this key', 'gallery_album' ];
					return null;
				}

				$albums[$index]['images'][] = $image;

				return $albums;
			} );

			if( $refusal !== null || $written === false ) {

				// The two files are already on disk and nothing points at them,
				// so they go again rather than sit there unreachable
				\Nino\Modules\Gallery::forget( $appData, $image );

				if( $refusal !== null )
					\Nino\Http::fail( $request, ...$refusal );
				else
					\Nino\Http::fail( $request, 500, 'the albums could not be written' );

				return;
			}

			\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	One image's alt text and caption in one language. Either may be a
		 *	textfill, which is how one text serves every language - and a
		 *	field that was not posted is left as it is, so the screen sends the
		 *	one that was changed.
		 *
		 *	Stored as one string for every language until the first translation
		 *	is saved; then as a locale => string map (see
		 *	\Nino\Modules\Gallery::withLocale()). Written under the lock of the
		 *	file, like every change of the albums: two people on two screens
		 *	saving two images are two writes of one file, and the second must
		 *	start from what the first left
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiImageSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data		= \Nino\Admin\Admin::postData();
			$key		= self::_key( $data['album'] ?? null );
			$id			= self::_id( $data['id'] ?? null );
			$locale	= \Nino\Admin\Admin::postString( $data, 'locale' );

			if( \Nino\Locales::verifyLocale( $appData, $locale ) === false ) {
				\Nino\Http::fail( $request, 400, 'that language is not available', 'gallery_locale' );
				return;
			}

			// text() is what cuts them to MAX_CAPTION - on a character
			// boundary, see apiAlbumSave() for why that is not substr(). A field
			// that is not a string is not posted: the other one may be
			$posted	= [];

			foreach( [ 'alt', 'caption' ] as $field )
				if( is_string( $data[$field] ?? null ) === true )
					$posted[$field] = \Nino\Modules\Gallery::text( $data[$field] );

			$available	= \Nino\Locales::getAvailableLocales( $appData );
			$refusal		= null;

			$written = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( $key, $id, $locale, $posted, $available, &$refusal ): ?array {

				$index = self::_indexOf( $albums, $key );

				if( $index === null ) {
					$refusal = [ 400, 'there is no album with this key', 'gallery_album' ];
					return null;
				}

				foreach( $albums[$index]['images'] as $position => $image ) {

					if( $image['id'] !== $id )
						continue;

					foreach( $posted as $field => $text )
						$albums[$index]['images'][$position][$field] = \Nino\Modules\Gallery::withLocale( $image[$field], $locale, (string) $text, $available );

					return $albums;
				}

				$refusal = [ 400, 'that image is not in this album', 'gallery_image' ];
				return null;
			} );

			if( $refusal !== null )
				\Nino\Http::fail( $request, ...$refusal );
			else if( $written === false )
				\Nino\Http::fail( $request, 500, 'the albums could not be written' );
			else
				\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	Take one image out of an album, files and all
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiImageDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data		= \Nino\Admin\Admin::postData();
			$key		= self::_key( $data['album'] ?? null );
			$id			= self::_id( $data['id'] ?? null );
			$gone		= null;
			$refusal	= null;

			$written = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( $key, $id, &$gone, &$refusal ): ?array {

				$index = self::_indexOf( $albums, $key );

				if( $index === null || $id === '' ) {
					$refusal = [ 400, 'there is no album with this key', 'gallery_album' ];
					return null;
				}

				$kept = [];

				foreach( $albums[$index]['images'] as $image )
					$image['id'] === $id ? $gone = $image : $kept[] = $image;

				if( $gone === null ) {
					$refusal = [ 400, 'that image is not in this album', 'gallery_image' ];
					return null;
				}

				$albums[$index]['images'] = $kept;

				return $albums;
			} );

			if( $refusal !== null ) {
				\Nino\Http::fail( $request, ...$refusal );
				return;
			}

			if( $written === false ) {
				\Nino\Http::fail( $request, 500, 'the albums could not be written' );
				return;
			}

			\Nino\Modules\Gallery::forget( $appData, $gone );

			\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	The order of one album's images, posted whole as a list of ids -
		 *	the order the grid renders in. An id the album does not have is
		 *	the whole request refused: a partial order would silently drop
		 *	whatever the browser and the disk disagreed about
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiReorder( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data		= \Nino\Admin\Admin::postData();
			$key		= self::_key( $data['album'] ?? null );
			$posted	= (array) ( $data['order'] ?? [] );
			$refusal	= null;

			$written = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( $key, $posted, &$refusal ): ?array {

				$index = self::_indexOf( $albums, $key );

				if( $index === null ) {
					$refusal = [ 400, 'there is no album with this key', 'gallery_album' ];
					return null;
				}

				$byId		= array_column( $albums[$index]['images'], null, 'id' );
				$order	= [];

				foreach( $posted as $id ) {

					$id = self::_id( $id );

					if( $id === '' || isset( $byId[$id] ) === false ) {
						$refusal = [ 400, 'that image is not in this album', 'gallery_image' ];
						return null;
					}

					$order[] = $byId[$id];
					unset( $byId[$id] );
				}

				if( $byId !== [] ) {
					$refusal = [ 400, 'the order did not name every image of the album', 'gallery_order' ];
					return null;
				}

				$albums[$index]['images'] = $order;

				return $albums;
			} );

			if( $refusal !== null )
				\Nino\Http::fail( $request, ...$refusal );
			else if( $written === false )
				\Nino\Http::fail( $request, 500, 'the albums could not be written' );
			else
				\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	The albums as the panel needs them: the stored shape, plus the url
		 *	of each image, so the script builds no path of its own, and the six
		 *	settings the album is made with - the feature's where it holds none
		 *	of its own, so the script knows no defaults either
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _albums( array &$appData ): array {

			$albums = [];

			foreach( \Nino\Modules\Gallery::albums( $appData ) as $album ) {

				$album['settings'] = \Nino\Modules\Gallery::settingsOf( $appData, $album );

				foreach( $album['images'] as $position => $image ) {
					$album['images'][$position]['thumbUrl'] = \Nino\Images::getUrl( $appData, $image['thumb'] );
					$album['images'][$position]['largeUrl'] = \Nino\Images::getUrl( $appData, $image['large'] );
				}

				$albums[] = $album;
			}

			return $albums;
		}

		/**
		 *	The settings a request got wrong, as the panel names them: each by
		 *	its label, in the language of whoever is looking, and, for a number,
		 *	the range it may take. It is a value of the sentence of the code
		 *	(gallery_settings), which only this side can phrase
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$names				Setting names
		 *
		 *	@return 	string											Eg. 'Thumbnail width (40–1200), Columns (1–8)'
		 */
		private static function _named( array &$appData, array $names ): string {

			$ranges	= \Nino\Modules\Gallery::ranges( $appData );
			$named	= [];

			foreach( $names as $name ) {

				$label = isset( self::LABELS[$name] ) === true ? \Nino\Html::renderHtml( $appData, '[['. self::LABELS[$name]. ']]' ) : (string) $name;

				$named[] = isset( $ranges[$name] ) === true ? $label. ' ('. $ranges[$name][0]. '–'. $ranges[$name][1]. ')' : $label;
			}

			return implode( ', ', $named );
		}

		/**
		 *	@param		array 		$albums
		 *	@param		string		$key
		 *
		 *	@return 	int|null										Where that album is, or null
		 */
		private static function _indexOf( array $albums, string $key ): ?int {

			foreach( $albums as $index => $album )
				if( $album['key'] === $key )
					return (int) $index;

			return null;
		}

		/**
		 *	@param		mixed			$value
		 *
		 *	@return 	string										A validated album key, '' when it is not one
		 */
		private static function _key( mixed $value ): string {

			return is_string( $value ) === true && preg_match( \Nino\Modules\Gallery::KEY_PATTERN, $value ) === 1 ? $value : '';
		}

		/**
		 *	@param		mixed			$value
		 *
		 *	@return 	string										A validated image id, '' when it is not one
		 */
		private static function _id( mixed $value ): string {

			return is_string( $value ) === true && preg_match( \Nino\Modules\Gallery::ID_PATTERN, $value ) === 1 ? $value : '';
		}
	}
}
