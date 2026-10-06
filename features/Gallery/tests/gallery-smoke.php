<?php
declare(strict_types=1);

/**
 *	Nino
 *	gallery-smoke.php	Contract test for the Gallery feature (Modules\Gallery):
 *										the manifest and its requirement on the Lightbox
 *										feature, the two sizes an upload becomes and the one
 *										thing that is never stored, the shortcode's markup -
 *										which is what the Lightbox reads, with an alt text and
 *										a caption of their own per language - the panel with
 *										its albums, alt texts, captions, order and deletions,
 *										the refusals of an upload and the limit each one
 *										names, and the seam a richer uploader hooks into. What the panel's own script
 *										does with all of that is the browser's, and
 *										gallery-js-smoke.js beside this file measures it; this
 *										test runs that one too where node is on the path.
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

// The overlay is the Lightbox feature's, so the Lightbox has to sit in the
// same features directory. Without it this feature cannot be activated, its
// panel is never registered, and every gallery/* action below would answer
// 404 - a page of failures for one missing directory, which is worth saying
// rather than demonstrating
if( \Nino\Features::get( $appData, 'lightbox' ) === null ) {
	fwrite( STDERR, 'No Lightbox feature in '. NINO_FEATURES_DIR. ' - Gallery requires it (its manifest says so), so there is nothing to activate'. "\n" );
	exit( 2 );
}

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

/**
 *	The albums, and the first album's images, out of a panel answer - [] where
 *	the answer does not carry them. A step that failed leaves the ones after it
 *	failing too, which is the point; what it must not do is fatal on a shape it
 *	never got, because that hides every check below it
 */
function galleryAlbums( ?array $body ): array {
	return $body['albums'] ?? [];
}

function galleryImages( ?array $body ): array {
	return $body['albums'][0]['images'] ?? [];
}

function callGalleryAdmin( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST = [ 'action' => $action, 'data' => json_encode( $data ) ];
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

/** A png header and nothing else - width and height are all getimagesizefromstring() asks of it */
function galleryPng( int $width, int $height ): string {
	$ihdr = pack( 'NN', $width, $height ). "\x08\x02\x00\x00\x00";
	return "\x89PNG\r\n\x1a\n". pack( 'N', 13 ). 'IHDR'. $ihdr. pack( 'N', crc32( 'IHDR'. $ihdr ) );
}

/** The error a panel answer carries, '' where it has none */
function galleryError( ?array $body ): string {
	return (string) ( $body['error'] ?? '' );
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

// The reasons store() cannot give: \Nino\Images only answers false
check( 'refusal() says why: too many bytes', \Nino\Modules\Gallery::refusal( str_repeat( 'a', \Nino\Modules\Gallery::KERNEL_UPLOAD_BYTES + 1 ) ) === 'size' );
check( '...not an image of a type the kernel takes', \Nino\Modules\Gallery::refusal( 'not an image' ) === 'type' && \Nino\Modules\Gallery::refusal( '' ) === 'type' );
check( '...too many pixels - a png header claiming 6000x4000 is enough, nothing is allocated for it', \Nino\Modules\Gallery::refusal( galleryPng( 6000, 4000 ) ) === 'pixels'
	&& \Nino\Modules\Gallery::store( $appData, 'trip', galleryPng( 6000, 4000 ) ) === null );
check( '...and where the bytes pass all three, that something after them said no', \Nino\Modules\Gallery::refusal( $wide ) === 'process' );
check( 'the mirrored byte cap is the kernel\'s own: that many bytes of a real image get through, one more does not', ( static function() use ( &$appData ): bool {
	$padded = galleryImage( 40, 40 );
	$padded .= str_repeat( "\0", \Nino\Modules\Gallery::KERNEL_UPLOAD_BYTES - strlen( $padded ) );
	$kept = \Nino\Modules\Gallery::store( $appData, 'trip', $padded );
	$over = \Nino\Modules\Gallery::store( $appData, 'trip', $padded. "\0" );
	if( $kept !== null )
		\Nino\Modules\Gallery::forget( $appData, $kept );
	return $kept !== null && $over === null;
} )() );

// An alt text and a caption are one string for every language, or one string
// per language - and a map must never be cast
check( 'an alt text or a caption is a string or a map, cut to the cap, with no empty language kept', \Nino\Modules\Gallery::text( '  Pass ' ) === 'Pass'
	&& \Nino\Modules\Gallery::text( [ 'de_DE' => ' Pass ', 'en_US' => '', 'fr_FR' => 3 ] ) === [ 'de_DE' => 'Pass' ]
	&& \Nino\Modules\Gallery::text( [ 'en_US' => '' ] ) === '' && \Nino\Modules\Gallery::text( null ) === ''
	&& \Nino\Modules\Gallery::text( [ 'de_DE' => str_repeat( 'ä', \Nino\Modules\Gallery::MAX_CAPTION ) ] )['de_DE'] === str_repeat( 'ä', \Nino\Modules\Gallery::MAX_CAPTION / 2 ) );
check( 'localized(): this language, else the native one, else the first there is, else nothing',
	\Nino\Modules\Gallery::localized( [ 'de_DE' => 'Pass', 'en_US' => 'Pass!' ], 'en_US', 'de_DE' ) === 'Pass!'
	&& \Nino\Modules\Gallery::localized( [ 'de_DE' => 'Pass', 'fr_FR' => 'Col' ], 'en_US', 'de_DE' ) === 'Pass'
	&& \Nino\Modules\Gallery::localized( [ 'fr_FR' => 'Col' ], 'en_US', 'de_DE' ) === 'Col'
	&& \Nino\Modules\Gallery::localized( [], 'en_US', 'de_DE' ) === '' && \Nino\Modules\Gallery::localized( 'Pass', 'en_US', 'de_DE' ) === 'Pass' );
check( 'normalize() takes both shapes of both texts, and a hand-written map does not raise a warning', ( static function(): bool {
	$album = \Nino\Modules\Gallery::normalize( [ 'key' => 'trip', 'images' => [
		[ 'id' => '0123456789abcdef', 'thumb' => 't.webp', 'large' => 'l.webp', 'alt' => [ 'de_DE' => 'Pass' ], 'caption' => 'Col' ],
		[ 'id' => 'fedcba9876543210', 'thumb' => 't.webp', 'large' => 'l.webp' ],
	] ] );
	return $album !== null && $album['images'][0]['alt'] === [ 'de_DE' => 'Pass' ] && $album['images'][0]['caption'] === 'Col'
		&& $album['images'][1]['alt'] === '' && $album['images'][1]['caption'] === '';
} )() && ninoWarnings() === [] );
check( 'withLocale() gives a plain string to every language before it changes one, and an empty text takes the language away again',
	\Nino\Modules\Gallery::withLocale( 'Pass', 'en_US', 'Col', [ 'de_DE', 'en_US' ] ) === [ 'de_DE' => 'Pass', 'en_US' => 'Col' ]
	&& \Nino\Modules\Gallery::withLocale( [ 'de_DE' => 'Pass', 'en_US' => 'Col' ], 'de_DE', '', [ 'de_DE', 'en_US' ] ) === [ 'en_US' => 'Col' ]
	&& \Nino\Modules\Gallery::withLocale( [ 'en_US' => 'Col' ], 'en_US', '', [ 'de_DE', 'en_US' ] ) === ''
	&& \Nino\Modules\Gallery::withLocale( '', 'de_DE', '', [ 'de_DE', 'en_US' ] ) === '' );
check( 'formatBytes() says a limit in whole units', \Nino\Modules\Gallery::formatBytes( 8 * 1024 * 1024 ) === '8 MB' && \Nino\Modules\Gallery::formatBytes( 1024 ** 3 ) === '1 GB'
	&& \Nino\Modules\Gallery::formatBytes( 1536 * 1024 ) === '1536 KB' && \Nino\Modules\Gallery::formatBytes( 512 ) === '512 B' );

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
check( 'gallery/list answers no album yet, and what an upload will be made into', $status === 200 && galleryAlbums( $body ) === []
	&& ( $body['thumb'] ?? null ) === [ 200, 200 ] && ( $body['large'] ?? null ) === [ 600, 600 ] && ( $body['keepRatio'] ?? null ) === true );
check( 'it carries the languages a text can be written in, the site\'s own, and the one the screen opens in', ( $body['locales'] ?? null ) === [ 'de_DE', 'en_US' ]
	&& ( $body['native'] ?? null ) === 'de_DE' && ( $body['selectedLocale'] ?? null ) === 'de_DE' );
check( 'and what php takes in one upload, as a number and in words - the smaller of upload_max_filesize and post_max_size', ( $body['limits']['bytes'] ?? null ) === \Nino\Modules\Gallery::uploadLimit()
	&& ( $body['limits']['text'] ?? null ) === \Nino\Modules\Gallery::formatBytes( \Nino\Modules\Gallery::uploadLimit() ) && \Nino\Modules\Gallery::uploadLimit() > 0
	&& \Nino\Modules\Gallery::uploadLimit() <= min( array_filter( [ ini_parse_quantity( (string) ini_get( 'upload_max_filesize' ) ), ini_parse_quantity( (string) ini_get( 'post_max_size' ) ) ] ) ) );

[ $status ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'Not A Key', 'name' => 'x' ] );
check( 'an album key that is not a slug is refused', $status === 400 );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'trip', 'name' => 'The trip' ] );
check( 'an album is created', $status === 200 && array_column( galleryAlbums( $body ), 'key' ) === [ 'trip' ] && ( galleryAlbums( $body )[0]['name'] ?? null ) === 'The trip' );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'trip', 'name' => 'Renamed' ] );
check( 'saving it again renames rather than duplicates', $status === 200 && count( galleryAlbums( $body ) ) === 1 && ( galleryAlbums( $body )[0]['name'] ?? null ) === 'Renamed' );

$uploaded = [];
foreach( [ 'a', 'b', 'c' ] as $ignored ) {
	[ $status, $body ] = withUpload( $wide, static fn(): array => callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => 'trip' ] ) );
	$uploaded[] = $status;
}
check( 'three uploads land in the album, in the order they arrived', $uploaded === [ 200, 200, 200 ] && count( galleryImages( $body ) ) === 3 );
check( 'each image comes back with the urls the panel shows it at', str_contains( (string) ( galleryImages( $body )[0]['thumbUrl'] ?? '' ), '/images/gallery/trip/' ) === true
	&& str_contains( (string) ( galleryImages( $body )[0]['largeUrl'] ?? '' ), '/images/gallery/trip/' ) === true );

$ids = array_pad( array_column( galleryImages( $body ), 'id' ), 3, '' );

// An upload that is refused says why, with the limit it ran into - not
// 'That image is not in this album.', which is what all of them said. The sandbox has no text files, so the panel's own are what the fills say
$panelText = include dirname( __DIR__ ). '/text/en_US.php';
\Nino\Html::addFills( $appData, $panelText, '*' );
$refuse = static function( string $bytes, int $error = UPLOAD_ERR_OK ) use ( &$appData ): array {
	[ $status, $body ] = withUpload( $bytes, static function() use ( &$appData, $error ): array {
		$_FILES['file']['error'] = $error;
		return callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => 'trip' ] );
	} );
	return [ $status, galleryError( $body ) ];
};
$limitText = \Nino\Modules\Gallery::formatBytes( \Nino\Modules\Gallery::uploadLimit() );

check( 'the panel\'s texts are there in both languages, every key of one in the other', array_keys( $panelText ) === array_keys( include dirname( __DIR__ ). '/text/de_DE.php' )
	&& str_contains( $panelText['[[/_admin/gallery/error/size]]'], '%s' ) === true && str_contains( ( include dirname( __DIR__ ). '/text/de_DE.php' )['[[/_admin/gallery/error/size]]'], '%s' ) === true );

[ $status, $error ] = $refuse( $wide, UPLOAD_ERR_INI_SIZE );
check( 'a file over upload_max_filesize is a 400 that names the limit php has', $status === 400 && $error === str_replace( '%s', $limitText, $panelText['[[/_admin/gallery/error/size]]'] ) );
[ $status, $error ] = $refuse( $wide, UPLOAD_ERR_FORM_SIZE );
check( '...so is one over the form\'s own', $status === 400 && $error === str_replace( '%s', $limitText, $panelText['[[/_admin/gallery/error/size]]'] ) );
[ $status, $error ] = $refuse( $wide, UPLOAD_ERR_PARTIAL );
check( 'any other upload error stays "the file could not be read"', $status === 400 && $error === $panelText['[[/_admin/gallery/error/upload]]'] );

[ $status, $sizeError ] = $refuse( str_repeat( 'a', \Nino\Modules\Gallery::KERNEL_UPLOAD_BYTES + 1 ) );
check( 'more than the kernel\'s 8 MiB is refused with that number', $status === 400 && $sizeError === str_replace( '%s', '8 MB', $panelText['[[/_admin/gallery/error/size]]'] ) );

[ $status, $error ] = $refuse( 'not an image' );
check( 'bytes that are not an image are refused as the wrong type', $status === 400 && $error === $panelText['[[/_admin/gallery/error/type]]'] );

[ $status, $error ] = $refuse( galleryPng( 6000, 4000 ) );
check( 'a picture of 24 megapixels is refused with the 20 the kernel takes', $status === 400 && $error === str_replace( '%s', '20', $panelText['[[/_admin/gallery/error/pixels]]'] ) );

\Nino\Callbacks::registerCallback( $appData, \Nino\Images::RENDER, static function( array &$appData, array &$img ): void {
	$img['filename'] = false;
} );
[ $status, $error ] = $refuse( $wide );
unset( $appData['./nino/callbacks'][ \Nino\Images::RENDER ] );
check( 'an image that passes the checks and is still not made says so, and is none of the above', $status === 400 && $error === $panelText['[[/_admin/gallery/error/process]]'] );
check( 'none of the refusals stored anything', count( galleryImages( callGalleryAdmin( $appData, 'gallery/list' )[1] ) ) === 3 );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Above the pass' ] );
check( 'a caption is saved on the image it names, in the language it was written in', $status === 200 && ( galleryImages( $body )[1]['caption'] ?? null ) === [ 'en_US' => 'Above the pass' ]
	&& ( galleryImages( $body )[1]['alt'] ?? null ) === '' && ( galleryImages( $body )[0]['caption'] ?? null ) === '' );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'de_DE', 'caption' => 'Über dem Pass', 'alt' => 'Der Pass im Sommer' ] );
check( 'each language has its own, and an alt text is saved beside a caption', $status === 200
	&& ( galleryImages( $body )[1]['caption'] ?? null ) === [ 'en_US' => 'Above the pass', 'de_DE' => 'Über dem Pass' ]
	&& ( galleryImages( $body )[1]['alt'] ?? null ) === [ 'de_DE' => 'Der Pass im Sommer' ] );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'alt' => 'The pass in summer' ] );
check( 'a text that is not posted is left as it is', $status === 200 && ( galleryImages( $body )[1]['alt'] ?? null ) === [ 'de_DE' => 'Der Pass im Sommer', 'en_US' => 'The pass in summer' ]
	&& ( galleryImages( $body )[1]['caption'] ?? null ) === [ 'en_US' => 'Above the pass', 'de_DE' => 'Über dem Pass' ] );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'alt' => '' ] );
check( 'an empty text takes that language away - the others stay', $status === 200 && ( galleryImages( $body )[1]['alt'] ?? null ) === [ 'de_DE' => 'Der Pass im Sommer' ] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'de_DE', 'alt' => '' ] );
check( '...and the last one leaves an empty string, not an empty map', $status === 200 && ( galleryImages( $body )[1]['alt'] ?? null ) === '' );

// What an earlier version of this feature wrote: one string, for every language
$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$stored[0]['images'][2]['caption'] = 'Vom Pass';
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[2], 'locale' => 'en_US', 'caption' => 'From the pass' ] );
check( 'a caption that was one string for every language is every language\'s until the first is written - then each keeps it', $status === 200
	&& ( galleryImages( $body )[2]['caption'] ?? null ) === [ 'de_DE' => 'Vom Pass', 'en_US' => 'From the pass' ] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[2], 'locale' => 'en_US', 'caption' => '' ] );
check( '...and an empty one takes only its own language off', ( galleryImages( $body )[2]['caption'] ?? null ) === [ 'de_DE' => 'Vom Pass' ] );

// What a request can get wrong, none of it a write
$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'xx_XX', 'caption' => 'x' ] );
check( 'a language the project does not have is refused - and as that, not as a missing image', $status === 400 && galleryError( $body ) === $panelText['[[/_admin/gallery/error/locale]]'] );
check( '...so is none at all', callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'caption' => 'x' ] )[0] === 400 );
check( '...and an image the album does not have, or an album nobody has', callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => 'ffffffffffffffff', 'locale' => 'en_US', 'caption' => 'x' ] )[0] === 400
	&& callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'nowhere', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'x' ] )[0] === 400 );
check( 'a text that is not a string is not one', callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => [ 'x' ], 'alt' => 5 ] )[0] === 200 );
check( 'none of those wrote anything', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Above the pass' ] );

// The cap is in bytes, and a cut through the middle of a multibyte character
// leaves a byte sequence that is not utf-8 - which json_encode() answers with
// false, so the panel's whole answer came back empty and every screen of it
// stopped working until somebody found the caption by hand
$longCaption = str_repeat( 'a', \Nino\Modules\Gallery::MAX_CAPTION - 1 ). 'ä und weiter';
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => $longCaption, 'alt' => $longCaption ] );
check( 'a caption or an alt text longer than the cap is cut on a character boundary, and the panel still answers', $status === 200
	&& ( galleryImages( $body )[1]['caption']['en_US'] ?? null ) === str_repeat( 'a', \Nino\Modules\Gallery::MAX_CAPTION - 1 )
	&& ( galleryImages( $body )[1]['alt']['en_US'] ?? null ) === str_repeat( 'a', \Nino\Modules\Gallery::MAX_CAPTION - 1 )
	&& json_encode( $body ) !== false );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Above the pass', 'alt' => '' ] );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'trip', 'order' => [ $ids[2], $ids[0], $ids[1] ] ] );
check( 'the order is the whole list, posted and stored', $status === 200 && array_column( galleryImages( $body ), 'id' ) === [ $ids[2], $ids[0], $ids[1] ] );

[ $status ] = callGalleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'trip', 'order' => [ $ids[0], $ids[1] ] ] );
check( 'an order that leaves an image out is refused whole - a partial order would drop it silently', $status === 400
	&& count( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'] ?? [] ) === 3 );

$goneFiles = array_map( static fn( string $key ): string => \Nino\Filesystem::path( $appData, '/images/'. \Nino\Modules\Gallery::album( $appData, 'trip' )['images'][0][$key] ?? '' ), [ 'thumb', 'large' ] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/image-delete', [ 'album' => 'trip', 'id' => $ids[2] ] );
check( 'deleting an image takes both of its files with it', $status === 200 && count( galleryImages( $body ) ) === 2
	&& is_file( $goneFiles[0] ) === false && is_file( $goneFiles[1] ) === false );

[ $status ] = callGalleryAdmin( $appData, 'gallery/image-delete', [ 'album' => 'trip', 'id' => $ids[2] ] );
check( 'deleting it twice is a refusal, not a second delete', $status === 400 );

echo "\n";


// --- The shortcode -----------------------------------------------------------

echo "[gallery] - the markup the Lightbox reads\n";

// The sandbox is in German; the captions the panel part above wrote for the
// second image are in both languages, and this part reads them in English first
\Nino\Locales::useLocale( $appData, 'en_US' );

$html = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );

check( 'it renders one item per image, in the stored order', substr_count( $html, '<li class="nino-gallery-cell">' ) === 2 );
check( 'every thumbnail is a link to the large view', substr_count( $html, 'class="nino-gallery-link"' ) === 2 && str_contains( $html, '/images/gallery/trip/' ) === true );
// The group is the album, so two galleries on one page stay two sets - and
// this is the whole of what the Lightbox feature needs from here
check( 'each link carries the lightbox group of its own album', substr_count( $html, 'data-lightbox="gallery-trip"' ) === 2 );
check( 'a caption travels as the caption and - where there is no alt text - as the alt', str_contains( $html, 'data-caption="Above the pass"' ) === true && str_contains( $html, 'alt="Above the pass"' ) === true );
check( 'an image without one carries no empty caption attribute', substr_count( $html, 'data-caption=' ) === 1 );
check( 'the thumbnails are lazy', substr_count( $html, 'loading="lazy"' ) === 2 );
check( 'the column count travels as a custom property, so the stylesheet needs no rule per number', str_contains( $html, '--nino-gallery-columns:4' ) === true );
check( '[gallery columns="2"] overrides it for one gallery', str_contains( \Nino\Html::renderHtml( $appData, '[gallery album="trip" columns="2"]' ), '--nino-gallery-columns:2' ) === true );
check( 'a bare [gallery] renders the first album', str_contains( \Nino\Html::renderHtml( $appData, '[gallery]' ), 'data-lightbox="gallery-trip"' ) === true );
check( 'an album key nothing has renders nothing at all', \Nino\Html::renderHtml( $appData, '[gallery album="nowhere"]' ) === '' );

/*	The classes the two templates and the stylesheet write are this feature's
	own. Nino.css lands in the same /.cache/style.css bundle and has a public
	.nino-gallery mosaic - fixed rows of 160 and 200 px, an item that clips -
	and the list carried its two names once: every thumbnail was cut off at
	the bottom of a 200 px cell, and this stylesheet's grid rules restyled
	every mosaic a project drew with the kernel's class	*/
// Selectors, not comments: both stylesheets talk about the other's classes
$uncommented = static fn( string $file ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );
$ninoCss = $uncommented( $root. '/_nino/Nino.css' );
$written = [];
foreach( [ 'templates/gallery.tpl', 'templates/gallery-item.tpl' ] as $file ) {
	preg_match_all( '/class="([^"]*)"/', (string) file_get_contents( dirname( __DIR__ ). '/'. $file ), $found );
	foreach( $found[1] as $list )
		$written = array_merge( $written, (array) preg_split( '/\s+/', trim( $list ) ) );
}
preg_match_all( '/\.(nino-[a-z0-9-]+)/', $uncommented( dirname( __DIR__ ). '/assets/gallery.css' ), $found );
$written = array_values( array_unique( array_merge( $written, $found[1] ) ) );
$styledByKernel = array_values( array_filter( $written, static fn( string $class ): bool => preg_match( '/\.'. preg_quote( $class, '/' ). '(?![\w-])/', $ninoCss ) === 1 ) );
check( 'no class the templates or the stylesheet write is one Nino.css styles - the bundle is shared, and the kernel\'s .nino-gallery is another grid', $written !== [] && $styledByKernel === [] );

// A caption is editor text and may be a textfill, which is how one caption
// serves every language - and what comes out of the fill engine is escaped
// like anything else that reaches an attribute
\Nino\Html::addFills( $appData, [ '[[/gallery/caption/one]]' => 'Am Pass "oben"' ], '*' );
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'en_US', 'caption' => '[[/gallery/caption/one]]' ] );
$filled = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );
check( 'a caption written as a fill is resolved, and what comes out is escaped', str_contains( $filled, 'data-caption="Am Pass &quot;oben&quot;"' ) === true
	&& str_contains( $filled, '[[/gallery/caption/' ) === false );

// The alt text is a text of its own, and so is each language's. What the
// thumbnail says is the alt text; what the Lightbox shows under the picture is
// the caption - and where there is an alt text and no caption, an empty
// data-caption tells it there is none, instead of showing the alt text there
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'en_US', 'caption' => '', 'alt' => 'Front of the "house"' ] );
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'de_DE', 'alt' => 'Vorderseite des Hauses' ] );
$both = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );
check( 'an alt text and no caption: the thumbnail is named by the alt text, and the link says it has no caption', str_contains( $both, 'alt="Front of the &quot;house&quot;"' ) === true
	&& str_contains( $both, 'data-caption=""' ) === true && substr_count( $both, 'data-caption=' ) === 2 );
check( 'the alt text is escaped like anything that reaches an attribute', str_contains( $both, 'alt="Front of the "house""' ) === false );
check( 'an image with only a caption keeps it as both, and one with neither carries no caption attribute at all', substr_count( $both, 'data-caption="Above the pass"' ) === 1
	&& substr_count( \Nino\Html::renderHtml( $appData, '[gallery album="trip" columns="2"]' ), 'alt=""' ) === 0 );

\Nino\Locales::useLocale( $appData, 'de_DE' );
$german = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );
check( 'the same page in German reads the German alt text and the German caption', str_contains( $german, 'alt="Vorderseite des Hauses"' ) === true
	&& str_contains( $german, 'data-caption="Über dem Pass"' ) === true && str_contains( $german, 'Above the pass' ) === false );

// Only English written for the second image: German is the site's own language
// and has nothing, so the first there is is better than a picture with no words
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'de_DE', 'caption' => '' ] );
check( 'a language that has no text falls back to the site\'s own, then to the first there is', str_contains( \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' ), 'data-caption="Above the pass"' ) === true );

// Not ninoWarnings() === []: the sandbox has no text files, and every fill the
// panel renders says so. What a map in a text must never do is be cast
check( 'none of it cast a map to a string', array_filter( ninoWarnings(), static fn( string $warning ): bool => str_contains( $warning, 'Array to string' ) === true ) === [] );

echo "\n";


// --- Deleting an album -------------------------------------------------------

echo "Gallery\\Admin - deleting an album\n";

$before = count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'trip' ] );
$after = count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [] );

// Unlike a form's submissions, an album's images are the album: nothing else
// points at them, and leaving them would leave files nobody can reach again
check( 'the album goes, and every picture in it', $status === 200 && galleryAlbums( $body ) === [] && $before > 0 && $after === 0 );

[ $status ] = callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'trip' ] );
check( 'deleting it twice is a refusal', $status === 400 );

$appData['./nino/auth/current'] = null;
[ $status ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'every action needs the panel\'s permission', $status === 401 );

echo "\n";


// --- The browser half --------------------------------------------------------

echo "assets/admin.js - its own behaviour test\n";

$jsTest	= __DIR__. '/gallery-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	// Not a failure and not a pass: say which, rather than counting a check
	// that never ran (Nino's AGENTS.md, section 10)
	echo "  --  - node is not available here: gallery-js-smoke.js was NOT run\n";
} else {
	$output	= [];
	$status	= 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = trim( (string) ( $output === [] ? '' : end( $output ) ) );
	check( 'gallery-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoWarnings();
ninoDone( $appData );
