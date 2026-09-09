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
	 *										screen of its own: upload, caption, order, delete.
	 *										Two levels, two panes, the workbench's own back link
	 *										between them.
	 *
	 *										\Nino\Modules\Gallery (Gallery.php beside this) owns
	 *										the shape of the file and the making of the two sizes;
	 *										this reads and writes through that class, so what the
	 *										panel stores is what the shortcode renders. Every word
	 *										a person reads is resolved here, in the session
	 *										language, so the script lays out what it gets.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/gallery/manage';

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

		// The group is named the way every catalogue feature names one, and
		// the workbench overrides it: a panel below \Nino\Features::dir()
		// always lands in the rail's own "features" group
		public static function nav(): array {
			return [ 'gallery', '/_admin/nav/gallery', 55, 'content' ];
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
		 *	Every album with its images, and the urls the panel shows them at
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [
				'albums'	=> self::_albums( $appData ),
				// What a thumbnail will be cropped to, so the panel can say so
				// rather than let somebody find out after twenty uploads
				'thumb'		=> [ \Nino\Modules\Gallery::setting( $appData, 'thumbWidth', 500 ), \Nino\Modules\Gallery::setting( $appData, 'thumbHeight', 500 ) ],
				'large'		=> [ \Nino\Modules\Gallery::setting( $appData, 'largeWidth', 1800 ), \Nino\Modules\Gallery::setting( $appData, 'largeHeight', 1800 ) ],
				'keepRatio'	=> \Nino\Features::setting( $appData, 'gallery', 'keepRatio', true ) === true,
			] );
		}

		/**
		 *	Create or rename one album. The images stay with it: a rename is
		 *	the album's name, not its key, and the key is what the files are
		 *	filed under
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
			$name		= substr( trim( (string) ( is_string( $data['name'] ?? null ) ? $data['name'] : '' ) ), 0, 120 );
			$albums	= \Nino\Modules\Gallery::albums( $appData );

			if( $key === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/key' ) );
				return;
			}

			$found = false;

			foreach( $albums as $index => $album )
				if( $album['key'] === $key ) {
					$albums[$index]['name'] = $name !== '' ? $name : $album['name'];
					$found = true;
				}

			if( $found === false )
				$albums[] = [ 'key' => $key, 'name' => $name !== '' ? $name : $key, 'images' => [] ];

			if( \Nino\Modules\Gallery::save( $appData, $albums ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/save' ) );
				return;
			}

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
			$albums	= \Nino\Modules\Gallery::albums( $appData );
			$kept		= [];
			$gone		= null;

			foreach( $albums as $album )
				$album['key'] === $key ? $gone = $album : $kept[] = $album;

			if( $gone === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/album' ) );
				return;
			}

			if( \Nino\Modules\Gallery::save( $appData, $kept ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/save' ) );
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
		 *	itself never written anywhere
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiUpload( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key		= self::_key( \Nino\Admin\Admin::postData()['album'] ?? null );
			$albums	= \Nino\Modules\Gallery::albums( $appData );
			$index	= self::_indexOf( $albums, $key );

			if( $index === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/album' ) );
				return;
			}

			if( isset( $_FILES['file'] ) === false || ( $_FILES['file']['error'] ?? 1 ) !== UPLOAD_ERR_OK ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/upload' ) );
				return;
			}

			$bytes = @file_get_contents( (string) $_FILES['file']['tmp_name'] );

			if( is_string( $bytes ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/upload' ) );
				return;
			}

			$image = \Nino\Modules\Gallery::store( $appData, $key, $bytes );

			if( $image === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/image' ) );
				return;
			}

			$albums[$index]['images'][] = $image;

			if( \Nino\Modules\Gallery::save( $appData, $albums ) === false ) {
				// The two files are already on disk and nothing points at them,
				// so they go again rather than sit there unreachable
				\Nino\Modules\Gallery::forget( $appData, $image );
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	One image's caption. It may be a textfill, which is how one
		 *	caption serves every language
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiImageSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data			= \Nino\Admin\Admin::postData();
			$key			= self::_key( $data['album'] ?? null );
			$id				= self::_id( $data['id'] ?? null );
			$caption	= substr( trim( (string) ( is_string( $data['caption'] ?? null ) ? $data['caption'] : '' ) ), 0, \Nino\Modules\Gallery::MAX_CAPTION );
			$albums		= \Nino\Modules\Gallery::albums( $appData );
			$index		= self::_indexOf( $albums, $key );

			if( $index === null || $id === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/album' ) );
				return;
			}

			$found = false;

			foreach( $albums[$index]['images'] as $position => $image )
				if( $image['id'] === $id ) {
					$albums[$index]['images'][$position]['caption'] = $caption;
					$found = true;
				}

			if( $found === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/image' ) );
				return;
			}

			if( \Nino\Modules\Gallery::save( $appData, $albums ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/save' ) );
				return;
			}

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
			$albums	= \Nino\Modules\Gallery::albums( $appData );
			$index	= self::_indexOf( $albums, $key );

			if( $index === null || $id === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/album' ) );
				return;
			}

			$kept = [];
			$gone = null;

			foreach( $albums[$index]['images'] as $image )
				$image['id'] === $id ? $gone = $image : $kept[] = $image;

			if( $gone === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/image' ) );
				return;
			}

			$albums[$index]['images'] = $kept;

			if( \Nino\Modules\Gallery::save( $appData, $albums ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/save' ) );
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
			$albums	= \Nino\Modules\Gallery::albums( $appData );
			$index	= self::_indexOf( $albums, $key );

			if( $index === null ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/album' ) );
				return;
			}

			$byId		= array_column( $albums[$index]['images'], null, 'id' );
			$order	= [];

			foreach( (array) ( $data['order'] ?? [] ) as $id ) {

				$id = self::_id( $id );

				if( $id === '' || isset( $byId[$id] ) === false ) {
					\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/image' ) );
					return;
				}

				$order[] = $byId[$id];
				unset( $byId[$id] );
			}

			if( $byId !== [] ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/order' ) );
				return;
			}

			$albums[$index]['images'] = $order;

			if( \Nino\Modules\Gallery::save( $appData, $albums ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/gallery/error/save' ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'albums' => self::_albums( $appData ) ] );
		}

		/**
		 *	The albums as the panel needs them: the stored shape, plus the url
		 *	of each image, so the script builds no path of its own
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _albums( array &$appData ): array {

			$albums = [];

			foreach( \Nino\Modules\Gallery::albums( $appData ) as $album ) {

				foreach( $album['images'] as $position => $image ) {
					$album['images'][$position]['thumbUrl'] = \Nino\Images::getUrl( $appData, $image['thumb'] );
					$album['images'][$position]['largeUrl'] = \Nino\Images::getUrl( $appData, $image['large'] );
				}

				$albums[] = $album;
			}

			return $albums;
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

		/**
		 *	One of this panel's own fills, in the language of whoever is
		 *	looking - the panel phrases its refusals, the script shows them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	string
		 */
		private static function _say( array &$appData, string $key ): string {

			return \Nino\Html::renderHtml( $appData, '[['. $key. ']]' );
		}
	}
}
