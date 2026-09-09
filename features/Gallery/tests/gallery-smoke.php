<?php
declare(strict_types=1);

/**
 *	Nino
 *	gallery-smoke.php	Contract test for the Gallery feature (Modules\Gallery):
 *										the manifest and its requirement on the Lightbox
 *										feature, the two sizes an upload becomes and the one
 *										thing that is never stored, the shortcode's markup -
 *										which is what the Lightbox reads - the panel with its
 *										albums, captions, order and deletions, and the seam a
 *										richer uploader hooks into.
 *
 *										Travels with the feature and runs against the checkout
 *										three levels up, or the one NINO_ROOT names (see
 *										tests/harness.php there).
 *
 *	Usage: php features/Gallery/tests/gallery-smoke.php
 *	       NINO_ROOT=../nino php features/Gallery/tests/gallery-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

// The two sizes this feature makes are \Nino\Images::process() and its
// counterpart fit(), which arrived together with the render callback. A
// checkout without them cannot run a line of what follows
if( method_exists( '\Nino\Images', 'fit' ) === false ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \\Nino\\Images::fit() - the Gallery feature needs the kernel that brought it'. "\n" );
	exit( 2 );
}

if( extension_loaded( 'gd' ) === false ) {
	fwrite( STDERR, 'No gd extension here - the Gallery feature makes its two sizes with it'. "\n" );
	exit( 2 );
}

$appData = ninoSandbox( 'gallery' );
$appData['/nino/dir'] = '';

/** Raw jpeg bytes for a solid-colour test image */
function galleryImage( int $width, int $height ): string {
	$img = imagecreatetruecolor( $width, $height );
	imagefill( $img, 0, 0, imagecolorallocate( $img, 30, 90, 160 ) );
	ob_start();
	imagejpeg( $img, null, 88 );
	$bytes = ob_get_clean();
	imagedestroy( $img );
	return (string) $bytes;
}

function callGalleryAdmin( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST = [ 'action' => $action, 'data' => json_encode( $data ) ];
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

/** An upload, the way php hands one over */
function withUpload( string $bytes, callable $run ): mixed {
	$tmp = tempnam( sys_get_temp_dir(), 'nino-gallery' );
	file_put_contents( $tmp, $bytes );
	$_FILES = [ 'file' => [ 'name' => 'photo.jpg', 'type' => 'image/jpeg', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ] ];
	$result = $run();
	$_FILES = [];
	@unlink( $tmp );
	return $result;
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir 			= dirname( __DIR__ );
$manifest	= \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "gallery"', is_array( $manifest ) === true && $manifest['key'] === 'gallery' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest ) === true
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';
check( 'it is filed under content - it brings something an editor maintains', ( $raw['category'] ?? '' ) === 'content' );
// The overlay is the Lightbox feature's, not a second copy of one. Installing
// this from the catalogue is what brings it along
check( 'it requires the Lightbox feature rather than carrying an overlay of its own', $manifest['requires'] === [ 'lightbox' ] );
check( 'it owns the album file, and only that - the images live where every uploaded image lives', $manifest['data'] === [ '/data/gallery.php' ] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

// The requirement is what a requirement is: the Lightbox has to be in the
// directory, and switching this feature on switches that one on first
check( 'with the Lightbox in the directory there is nothing in the way', ( \Nino\Features::get( $appData, 'gallery' )['problems'] ?? null ) === [] );
check( 'activating it activates the Lightbox first - one click, two features on', \Nino\Features::activate( $appData, 'gallery' ) === true
	&& \Nino\Features::get( $appData, 'lightbox' )['active'] === true && \Nino\Features::get( $appData, 'gallery' )['active'] === true );
check( 'and the Lightbox cannot be switched off while this one is on', \Nino\Features::deactivate( $appData, 'lightbox' ) === 'feature "lightbox" is required by "gallery"' );

\Nino\Modules::callModules( $appData, 'init' );

check( 'init adds the [gallery] shortcode and the grid stylesheet', isset( $appData['./nino/html/shortcodes']['gallery'] ) === true
	&& in_array( '/features/Gallery/assets/gallery.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'and no script of its own - what a thumbnail opens into is the Lightbox feature\'s', in_array( '/features/Gallery/assets/gallery.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === false );

echo "\n";


// --- The two sizes -----------------------------------------------------------

echo "Modules\\Gallery::store - two sizes, and what is never stored\n";

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );

$wide  = galleryImage( 1200, 600 );
$image = \Nino\Modules\Gallery::store( $appData, 'trip', $wide );

check( 'an upload becomes an entry with an id and two filenames', is_array( $image ) === true
	&& preg_match( '/^[a-f0-9]{16}$/', $image['id'] ) === 1 && $image['thumb'] !== $image['large'] && $image['caption'] === '' );

$thumbSize = getimagesize( \Nino\Filesystem::path( $appData, '/images/'. $image['thumb'] ) );
$largeSize = getimagesize( \Nino\Filesystem::path( $appData, '/images/'. $image['large'] ) );

check( 'the thumbnail is cropped to exactly the configured size - a grid of different shapes is not a grid', $thumbSize[0] === 200 && $thumbSize[1] === 200 );
check( 'the large view keeps the picture\'s own proportions inside the box - cropping is what opening a thumbnail undoes', $largeSize[0] === 600 && $largeSize[1] === 300 );
check( 'both files are below the feature\'s own directory in /images', str_starts_with( $image['thumb'], 'gallery/trip/' ) === true && str_starts_with( $image['large'], 'gallery/trip/' ) === true );

// The point of the whole design: there is no full-resolution copy of
// somebody's camera file sitting under /images waiting to be guessed at
$stored = glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [];
check( 'the upload itself is never written anywhere - two files, and both of them derived', count( $stored ) === 2 );
check( '...and neither is as big as what was uploaded', array_sum( array_map( 'filesize', $stored ) ) < strlen( $wide ) );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => false, 'columns' => 4 ] );
$cropped = \Nino\Modules\Gallery::store( $appData, 'trip', $wide );
$croppedSize = getimagesize( \Nino\Filesystem::path( $appData, '/images/'. $cropped['large'] ) );
check( 'with the ratio switched off the large view is cropped like the thumbnail', $croppedSize[0] === 600 && $croppedSize[1] === 600 );
\Nino\Modules\Gallery::forget( $appData, $cropped );

check( 'an album key that is not a slug stores nothing', \Nino\Modules\Gallery::store( $appData, '../escape', $wide ) === null );
check( 'and bytes that are not an image store nothing', \Nino\Modules\Gallery::store( $appData, 'trip', 'not an image' ) === null );

// Off disk again: nothing in an album points at these two, and the album
// checks further down count what is in the directory
\Nino\Modules\Gallery::forget( $appData, $image );
check( 'forget() takes both files of an image away', is_file( \Nino\Filesystem::path( $appData, '/images/'. $image['thumb'] ) ) === false
	&& is_file( \Nino\Filesystem::path( $appData, '/images/'. $image['large'] ) ) === false );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );

// The seam the kernel opened for a richer uploader: both sizes go through
// \Nino\Images, so a project that registers there renders this feature's
// images too - without this feature knowing anything about it
$seen = [];
\Nino\Callbacks::registerCallback( $appData, \Nino\Images::RENDER, static function( array &$appData, array &$img ) use ( &$seen ): void {
	$seen[] = $img['mode'];
	$img['filename'] = $img['basePath']. '.' . $img['mode']. '.webp';
} );
$hooked = \Nino\Modules\Gallery::store( $appData, 'trip', $wide );
check( 'a project that renders images its own way renders these too, both sizes, through the kernel\'s callback', is_array( $hooked ) === true
	&& $seen === [ 'crop', 'fit' ] && str_ends_with( $hooked['thumb'], '.crop.webp' ) === true && str_ends_with( $hooked['large'], '.fit.webp' ) === true );
unset( $appData['./nino/callbacks'][ \Nino\Images::RENDER ] );

echo "\n";


// --- The panel ---------------------------------------------------------------

echo "Gallery\\Admin - albums, captions, order\n";

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ \Nino\Modules\Gallery\Admin::MANAGE_PERM ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );
\Nino\Admin\Admin::init( $appData );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'gallery/list answers no album yet, and what an upload will be made into', $status === 200 && $body['albums'] === []
	&& $body['thumb'] === [ 200, 200 ] && $body['large'] === [ 600, 600 ] && $body['keepRatio'] === true );

[ $status ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'Not A Key', 'name' => 'x' ] );
check( 'an album key that is not a slug is refused', $status === 400 );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'trip', 'name' => 'The trip' ] );
check( 'an album is created', $status === 200 && array_column( $body['albums'], 'key' ) === [ 'trip' ] && $body['albums'][0]['name'] === 'The trip' );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'trip', 'name' => 'Renamed' ] );
check( 'saving it again renames rather than duplicates', $status === 200 && count( $body['albums'] ) === 1 && $body['albums'][0]['name'] === 'Renamed' );

$uploaded = [];
foreach( [ 'a', 'b', 'c' ] as $ignored ) {
	[ $status, $body ] = withUpload( $wide, static fn(): array => callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => 'trip' ] ) );
	$uploaded[] = $status;
}
check( 'three uploads land in the album, in the order they arrived', $uploaded === [ 200, 200, 200 ] && count( $body['albums'][0]['images'] ) === 3 );
check( 'each image comes back with the urls the panel shows it at', str_contains( $body['albums'][0]['images'][0]['thumbUrl'], '/images/gallery/trip/' ) === true
	&& str_contains( $body['albums'][0]['images'][0]['largeUrl'], '/images/gallery/trip/' ) === true );

$ids = array_column( $body['albums'][0]['images'], 'id' );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'caption' => 'Above the pass' ] );
check( 'a caption is saved on the image it names', $status === 200 && $body['albums'][0]['images'][1]['caption'] === 'Above the pass' );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'trip', 'order' => [ $ids[2], $ids[0], $ids[1] ] ] );
check( 'the order is the whole list, posted and stored', $status === 200 && array_column( $body['albums'][0]['images'], 'id' ) === [ $ids[2], $ids[0], $ids[1] ] );

[ $status ] = callGalleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'trip', 'order' => [ $ids[0], $ids[1] ] ] );
check( 'an order that leaves an image out is refused whole - a partial order would drop it silently', $status === 400
	&& count( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'] ) === 3 );

$goneFiles = array_map( static fn( string $key ): string => \Nino\Filesystem::path( $appData, '/images/'. \Nino\Modules\Gallery::album( $appData, 'trip' )['images'][0][$key] ), [ 'thumb', 'large' ] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-delete', [ 'album' => 'trip', 'id' => $ids[2] ] );
check( 'deleting an image takes both of its files with it', $status === 200 && count( $body['albums'][0]['images'] ) === 2
	&& is_file( $goneFiles[0] ) === false && is_file( $goneFiles[1] ) === false );

[ $status ] = callGalleryAdmin( $appData, 'gallery/image-delete', [ 'album' => 'trip', 'id' => $ids[2] ] );
check( 'deleting it twice is a refusal, not a second delete', $status === 400 );

echo "\n";


// --- The shortcode -----------------------------------------------------------

echo "[gallery] - the markup the Lightbox reads\n";

$html = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );

check( 'it renders one item per image, in the stored order', substr_count( $html, '<li class="nino-gallery-item">' ) === 2 );
check( 'every thumbnail is a link to the large view', substr_count( $html, 'class="nino-gallery-link"' ) === 2 && str_contains( $html, '/images/gallery/trip/' ) === true );
// The group is the album, so two galleries on one page stay two sets - and
// this is the whole of what the Lightbox feature needs from here
check( 'each link carries the lightbox group of its own album', substr_count( $html, 'data-lightbox="gallery-trip"' ) === 2 );
check( 'a caption travels as the caption and as the alt', str_contains( $html, 'data-caption="Above the pass"' ) === true && str_contains( $html, 'alt="Above the pass"' ) === true );
check( 'an image without one carries no empty caption attribute', substr_count( $html, 'data-caption=' ) === 1 );
check( 'the thumbnails are lazy', substr_count( $html, 'loading="lazy"' ) === 2 );
check( 'the column count travels as a custom property, so the stylesheet needs no rule per number', str_contains( $html, '--nino-gallery-columns:4' ) === true );
check( '[gallery columns="2"] overrides it for one gallery', str_contains( \Nino\Html::renderHtml( $appData, '[gallery album="trip" columns="2"]' ), '--nino-gallery-columns:2' ) === true );
check( 'a bare [gallery] renders the first album', str_contains( \Nino\Html::renderHtml( $appData, '[gallery]' ), 'data-lightbox="gallery-trip"' ) === true );
check( 'an album key nothing has renders nothing at all', \Nino\Html::renderHtml( $appData, '[gallery album="nowhere"]' ) === '' );

// A caption is editor text and may be a textfill, which is how one caption
// serves every language - and what comes out of the fill engine is escaped
// like anything else that reaches an attribute
\Nino\Html::addFills( $appData, [ '[[/gallery/caption/one]]' => 'Am Pass "oben"' ], '*' );
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'caption' => '[[/gallery/caption/one]]' ] );
$filled = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );
check( 'a caption written as a fill is resolved, and what comes out is escaped', str_contains( $filled, 'data-caption="Am Pass &quot;oben&quot;"' ) === true
	&& str_contains( $filled, '[[/gallery/caption/' ) === false );

echo "\n";


// --- Deleting an album -------------------------------------------------------

echo "Gallery\\Admin - deleting an album\n";

$before = count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'trip' ] );
$after = count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [] );

// Unlike a form's submissions, an album's images are the album: nothing else
// points at them, and leaving them would leave files nobody can reach again
check( 'the album goes, and every picture in it', $status === 200 && $body['albums'] === [] && $before > 0 && $after === 0 );

[ $status ] = callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'trip' ] );
check( 'deleting it twice is a refusal', $status === 400 );

$appData['./nino/auth/current'] = null;
[ $status ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'every action needs the panel\'s permission', $status === 401 );

ninoWarnings();
ninoDone( $appData );
