<?php
declare(strict_types=1);

/**
 *	Nino
 *	gallery-smoke.php	Contract test for the Gallery feature
 *										(Modules\Gallery): the manifest and its requirement on
 *										the Lightbox feature, the two sizes an upload becomes
 *										and the one thing that is never stored, the sizes an
 *										album carries of its own - what they are held to, what
 *										an album that has none reads, what an upload and the
 *										grid take from them - the shortcode's markup - which is
 *										what the Lightbox reads, with an alt text and a caption
 *										of their own per language - the panel with its albums,
 *										alt texts, captions, order and deletions, the refusals
 *										of an upload and the limit each one names, the
 *										component the Builder offers with the albums of the
 *										project as the choices of a select, left alone where a
 *										module of the project registered one of its own - or
 *										this feature's renderer under a schema of its own, with
 *										no album or an album that has a default - and
 *										the seam a richer uploader hooks into. What the panel's
 *										own script does with all of that is the browser's, and
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

/** One album of a panel answer by its key, [] where it has none */
function galleryAlbumOf( ?array $body, string $key ): array {
	foreach( galleryAlbums( $body ) as $album )
		if( ( $album['key'] ?? null ) === $key )
			return $album;
	return [];
}

/** The width and the height of one stored image file, as '120x90' */
function galleryDims( array &$appData, string $filename ): string {
	$size = @getimagesize( \Nino\Filesystem::path( $appData, '/images/'. $filename ) );
	return $size === false ? '' : $size[0]. 'x'. $size[1];
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

check( 'it is filed under content - it brings something an editor maintains', $manifest['category'] === 'content' );
// The overlay is the Lightbox feature's, not a second copy of one. Installing
// this from the catalogue is what brings it along
check( 'it requires the Lightbox feature rather than carrying an overlay of its own', $manifest['requires'] === [ 'lightbox' ] );
check( 'it owns the album file, and only that - the images live where every uploaded image lives', $manifest['data'] === [ '/data/gallery.php' ] );

// The feature's settings are what a new album starts with, and each says so in its label - in both languages
$defaultsSaid = true;
foreach( [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'keepRatio', 'columns' ] as $name )
	$defaultsSaid = $defaultsSaid && str_contains( \Nino\Features::localized( $manifest['settings'][$name]['label'] ?? '', 'en_US' ), 'default for new albums' ) === true
		&& str_contains( \Nino\Features::localized( $manifest['settings'][$name]['label'] ?? '', 'de_DE' ), 'Vorgabe für neue Alben' ) === true;
check( 'the settings of the feature are the defaults for a new album, and their labels say so in both languages', $defaultsSaid === true );
check( '...and they are the six an album carries, in the order the panel draws them', array_keys( $manifest['settings'] ) === \Nino\Modules\Gallery::SETTINGS );

// The select is read by the wrapper, and a value outside its options is its default: what that makes of a call is the manual's to say
$gone = (array) ( $manifest['manual']['shortcodes']['[gallery album="gone"]'] ?? [] );
check( 'the manual says what a key that no album has does, in both languages: nothing before Nino 1.6, the first album where the Builder offers the albums as a list - from it on - and a call the Builder does not keep',
	str_contains( (string) ( $gone['en_US'] ?? '' ), 'draws nothing before Nino 1.6. From Nino 1.6' ) === true && str_contains( (string) ( $gone['en_US'] ?? '' ), 'draws the first album' ) === true && str_contains( (string) ( $gone['en_US'] ?? '' ), 'HTML+ block' ) === true
	&& str_contains( (string) ( $gone['de_DE'] ?? '' ), 'zeichnet vor Nino 1.6 nichts. Ab Nino 1.6' ) === true && str_contains( (string) ( $gone['de_DE'] ?? '' ), 'zeichnet er das erste Album' ) === true && str_contains( (string) ( $gone['de_DE'] ?? '' ), 'HTML+-Block' ) === true );

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


// --- The sizes of an album ---------------------------------------------------

echo "Modules\\Gallery - the sizes an album carries\n";

$feature = \Nino\Modules\Gallery::defaults( $appData );
check( 'what a new album starts with is the settings of the feature: the six, in the order the panel draws them',
	$feature === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] && array_keys( $feature ) === \Nino\Modules\Gallery::SETTINGS );

// The bounds are written once, in the manifest: the panel limits its inputs by them and the server holds a value to them
$fromManifest = [];
foreach( [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'columns' ] as $name )
	$fromManifest[$name] = [ $manifest['settings'][$name]['min'] ?? null, $manifest['settings'][$name]['max'] ?? null ];
check( 'each number has the range the manifest gives the feature\'s setting - the one place the bounds are written, which the switch has none of',
	\Nino\Modules\Gallery::ranges( $appData ) === $fromManifest && \Nino\Modules\Gallery::ranges( $appData ) === [ 'thumbWidth' => [ 40, 1200 ], 'thumbHeight' => [ 40, 1200 ],
		'largeWidth' => [ 200, 4000 ], 'largeHeight' => [ 200, 4000 ], 'columns' => [ 1, 8 ] ] );

$bare = \Nino\Modules\Gallery::normalize( [ 'key' => 'trip', 'images' => [] ] );
check( 'a record without sizes is read as it is, with no settings in it: nothing is written into a record that nobody asked to', $bare !== null && array_key_exists( 'settings', $bare ) === false );
$held = \Nino\Modules\Gallery::normalize( [ 'key' => 'trip', 'settings' => [ 'thumbWidth' => 100, 'thumbHeight' => '90', 'largeWidth' => [ 1 ], 'keepRatio' => false, 'columns' => 3, 'bogus' => 1 ] ] );
check( '...and a record with some keeps the names an album carries that have a number or a switch for a value, and nothing else', ( $held['settings'] ?? null ) === [ 'thumbWidth' => 100, 'keepRatio' => false, 'columns' => 3 ] );
check( '...a settings entry that is not a map is no sizes', array_key_exists( 'settings', \Nino\Modules\Gallery::normalize( [ 'key' => 'trip', 'settings' => 'big' ] ) ?? [ 'settings' => 1 ] ) === false );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [ [ 'key' => 'one' ], [ 'key' => 'Not A Key' ], 'not an album', [ 'key' => 'two', 'images' => 'broken' ], [ 'key' => 'one' ] ] );
check( 'keys() answers the key of every album there is, in the order they are stored, each once - what the Builder\'s select offers',
	\Nino\Modules\Gallery::keys( $appData ) === [ 'one', 'two' ] );

$ownSizes = [ 'thumbWidth' => 80, 'thumbHeight' => 60, 'largeWidth' => 300, 'largeHeight' => 300, 'keepRatio' => false, 'columns' => 2 ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [
	[ 'key' => 'sized', 'name' => 'Sized', 'images' => [], 'settings' => $ownSizes ],
	[ 'key' => 'fitted', 'name' => 'Fitted', 'images' => [], 'settings' => [ 'thumbWidth' => 100, 'thumbHeight' => 50, 'largeWidth' => 400, 'largeHeight' => 400, 'keepRatio' => true, 'columns' => 3 ] ],
	[ 'key' => 'half', 'name' => 'Half', 'images' => [], 'settings' => [ 'thumbWidth' => 5, 'largeWidth' => 300 ] ],
	[ 'key' => 'plain', 'name' => 'Plain', 'images' => [] ],
] );

check( 'an album is made with the sizes its record holds', \Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'sized' ) ?? [] ) === $ownSizes );
check( '...and with the feature\'s for every one that is not there or that the bounds of the manifest refuse: 5 is no width for a thumbnail',
	\Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'half' ) ?? [] ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 300, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );
check( '...and an album that holds none - or no album at all - with the feature\'s alone',
	\Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'plain' ) ?? [] ) === $feature && \Nino\Modules\Gallery::settingsOf( $appData, [] ) === $feature );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 90, 'thumbHeight' => 90, 'largeWidth' => 500, 'largeHeight' => 500, 'keepRatio' => false, 'columns' => 7 ] );
check( 'the feature\'s are read when they are asked for, not copied: an album without sizes follows them, one with its own does not',
	\Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'plain' ) ?? [] )['columns'] === 7
	&& \Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'sized' ) ?? [] ) === $ownSizes );
\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );

// What an upload becomes: the album's sizes, in the files and in their names
$made = [];
foreach( [ 'sized', 'fitted', 'half', 'plain' ] as $key )
	$made[$key] = \Nino\Modules\Gallery::store( $appData, $key, $wide );

check( 'an upload into an album is made to the album\'s sizes, not to the feature\'s: a thumbnail of exactly 80x60 and the picture cropped to 300x300 where it keeps no ratio',
	galleryDims( $appData, $made['sized']['thumb'] ?? '' ) === '80x60' && galleryDims( $appData, $made['sized']['large'] ?? '' ) === '300x300' );
check( '...and the file names say which sizes they were made at',
	str_contains( (string) ( $made['sized']['thumb'] ?? '' ), '.80x60.' ) === true && str_contains( (string) ( $made['sized']['large'] ?? '' ), '.300x300.' ) === true );
check( '...the whole picture inside the box where it does: 400x400 around 1200x600 is 400x200, and the name says fit',
	galleryDims( $appData, $made['fitted']['thumb'] ?? '' ) === '100x50' && galleryDims( $appData, $made['fitted']['large'] ?? '' ) === '400x200'
	&& str_contains( (string) ( $made['fitted']['large'] ?? '' ), '.fit400x400.' ) === true );
check( '...an album that holds some of the sizes takes the rest from the feature - 200x200 for a thumbnail, 300 wide and 600 high for the large view',
	galleryDims( $appData, $made['half']['thumb'] ?? '' ) === '200x200' && galleryDims( $appData, $made['half']['large'] ?? '' ) === '300x150' );
check( '...and one that holds none takes them all from the feature', galleryDims( $appData, $made['plain']['thumb'] ?? '' ) === '200x200' && galleryDims( $appData, $made['plain']['large'] ?? '' ) === '600x300' );

foreach( $made as $one )
	if( is_array( $one ) === true )
		\Nino\Modules\Gallery::forget( $appData, $one );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );

// What a panel posts is checked against the same bounds
$posted = \Nino\Modules\Gallery::checkSettings( $appData, [ 'columns' => 5, 'thumbWidth' => '250' ], [ 'settings' => $ownSizes ] );
check( 'checkSettings() takes what was posted over what the album holds - a number written as digits is a number - and leaves the rest as it is',
	$posted['errors'] === [] && $posted['values'] === [ 'thumbWidth' => 250, 'thumbHeight' => 60, 'largeWidth' => 300, 'largeHeight' => 300, 'keepRatio' => false, 'columns' => 5 ] );
$wrong = \Nino\Modules\Gallery::checkSettings( $appData, [ 'thumbWidth' => 39, 'thumbHeight' => 100.5, 'largeWidth' => 200, 'largeHeight' => 4001, 'keepRatio' => 'maybe', 'columns' => 0 ] );
check( '...every value outside the bounds, and every one of the wrong kind, is an error named by its setting - and only those',
	array_keys( $wrong['errors'] ) === [ 'thumbWidth', 'thumbHeight', 'largeHeight', 'keepRatio', 'columns' ] );
$edges = [];
foreach( [ [ 'thumbWidth', 40 ], [ 'thumbWidth', 1200 ], [ 'thumbHeight', 40 ], [ 'thumbHeight', 1200 ], [ 'largeWidth', 200 ], [ 'largeWidth', 4000 ], [ 'largeHeight', 200 ], [ 'largeHeight', 4000 ], [ 'columns', 1 ], [ 'columns', 8 ] ] as [ $name, $value ] )
	if( \Nino\Modules\Gallery::checkSettings( $appData, [ $name => $value ] )['errors'] !== [] )
		$edges[] = $name. '='. $value;
check( '...the bounds themselves are inside them: 40 to 1200, 200 to 4000, 1 to 8'. ( $edges === [] ? '' : ' - '. implode( ' | ', $edges ) ), $edges === [] );
$edgesOut = [];
foreach( [ [ 'thumbWidth', 39 ], [ 'thumbWidth', 1201 ], [ 'thumbHeight', 39 ], [ 'thumbHeight', 1201 ], [ 'largeWidth', 199 ], [ 'largeWidth', 4001 ], [ 'largeHeight', 199 ], [ 'largeHeight', 4001 ], [ 'columns', 0 ], [ 'columns', 9 ] ] as [ $name, $value ] )
	if( \Nino\Modules\Gallery::checkSettings( $appData, [ $name => $value ] )['errors'] === [] )
		$edgesOut[] = $name. '='. $value;
check( '...and the next number outside them is not'. ( $edgesOut === [] ? '' : ' - '. implode( ' | ', $edgesOut ) ), $edgesOut === [] );

echo "\n";


// --- The panel ---------------------------------------------------------------

echo "Gallery\\Admin - albums, captions, order\n";

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ \Nino\Modules\Gallery\Admin::MANAGE_PERM ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );
\Nino\Admin\Admin::init( $appData );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'gallery/list answers no album yet, and what a new album starts with - the settings of the feature - with the range each number may take', $status === 200 && galleryAlbums( $body ) === []
	&& ( $body['defaults'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ]
	&& ( $body['ranges'] ?? null ) === \Nino\Modules\Gallery::ranges( $appData ) && ( $body['ranges']['columns'] ?? null ) === [ 1, 8 ] );
check( 'it carries the languages a text can be written in, the site\'s own, and the one the screen opens in', ( $body['locales'] ?? null ) === [ 'de_DE', 'en_US' ]
	&& ( $body['native'] ?? null ) === 'de_DE' && ( $body['selectedLocale'] ?? null ) === 'de_DE' );
check( 'and how long the key of a new album may be, which is what the field that asks for it is limited by', ( $body['maxKey'] ?? null ) === \Nino\Modules\Gallery::MAX_KEY && \Nino\Modules\Gallery::MAX_KEY === 64 );
check( 'and what php takes in one upload, as a number and in words - the smaller of upload_max_filesize and post_max_size', ( $body['limits']['bytes'] ?? null ) === \Nino\Modules\Gallery::uploadLimit()
	&& ( $body['limits']['text'] ?? null ) === \Nino\Modules\Gallery::formatBytes( \Nino\Modules\Gallery::uploadLimit() ) && \Nino\Modules\Gallery::uploadLimit() > 0
	&& \Nino\Modules\Gallery::uploadLimit() <= min( array_filter( [ ini_parse_quantity( (string) ini_get( 'upload_max_filesize' ) ), ini_parse_quantity( (string) ini_get( 'post_max_size' ) ) ] ) ) );

/*	upload_max_filesize and post_max_size cannot be set from inside a request, so a
	process of its own is started with them: a value php cannot read whole raised a
	warning from ini_parse_quantity(), which is a 500 for the panel that asked	*/
$gallerySource = dirname( __DIR__ ). '/Gallery.php';
$limitWith = static function( string $upload, string $post ) use ( $gallerySource ): ?string {
	if( function_exists( 'shell_exec' ) === false )
		return null;
	// The startup warning php itself gives for such a setting goes to stderr, which is let go;
	// what the class raises while it runs is caught and printed, so it shows in the answer
	$code = 'set_error_handler( static function( int $n, string $m ): bool { echo "WARNING:", $m; return true; } ); require '. var_export( $gallerySource, true ). '; echo \\Nino\\Modules\\Gallery::uploadLimit();';
	return trim( (string) shell_exec( escapeshellarg( PHP_BINARY ). ' -d '. escapeshellarg( 'upload_max_filesize='. $upload ). ' -d '. escapeshellarg( 'post_max_size='. $post ). ' -d display_errors=0 -d log_errors=0 -r '. escapeshellarg( $code ). ' 2>/dev/null' ) );
};
if( $limitWith( '8M', '8M' ) === null )
	echo "  --  shell_exec is not available, so a malformed upload_max_filesize is not tried\n";
else {
	check( 'a limit php can read is read: the smaller of the two', $limitWith( '2M', '8M' ) === (string) ( 2 * 1048576 ) && $limitWith( '8M', '1G' ) === (string) ( 8 * 1048576 ) );
	check( 'a malformed upload_max_filesize raises nothing - it is a limit nobody can tell, and post_max_size still counts',
		$limitWith( 'abc', '8M' ) === (string) ( 8 * 1048576 ) && $limitWith( 'abc', 'xyz' ) === '0' );
	check( '...nor does one with a decimal point, or one that is too big for an integer', $limitWith( '1.5M', '8M' ) === (string) 1048576 && $limitWith( '99999999999999999999G', '8M' ) === (string) ( 8 * 1048576 ) );
}

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

// A word the script or the class asks for that a language lacks is a screen with a key on it. Keys put together
// from a part and a name - the label of an alt text and a caption - end in a slash and are not read here, and
// the permission of the panel is named the same way and is no word
$asked = [];
foreach( [ '/assets/admin.js', '/Admin/Admin.php' ] as $source )
	if( preg_match_all( '#[\'"](/_admin/gallery/[a-z0-9/-]+)[\'"]#', (string) file_get_contents( dirname( __DIR__ ). $source ), $found ) > 0 )
		$asked = array_merge( $asked, $found[1] );
$german = include dirname( __DIR__ ). '/text/de_DE.php';
$lacking = array_values( array_filter( array_unique( $asked ), static fn( string $key ): bool => str_ends_with( $key, '/' ) === false && $key !== \Nino\Modules\Gallery\Admin::MANAGE_PERM
	&& ( isset( $panelText['[['. $key. ']]'] ) === false || isset( $german['[['. $key. ']]'] ) === false ) ) );
check( 'every word of the panel that its script and its class ask for is there, in both languages'. ( $lacking === [] ? '' : ' - '. implode( ' | ', $lacking ) ), $lacking === [] && count( $asked ) > 30 );

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

/*	A list that cannot be locked was never looked at: the album is not missing and
	neither is the image - the albums could not be written, which is what is said	*/
$locks = \Nino\Filesystem::path( $appData, '/data/.locks' );
$locksAside = $locks. '-aside';
$hadLocks = file_exists( $locks );
if( $hadLocks === true )
	rename( $locks, $locksAside );
file_put_contents( $locks, 'not a directory' );
ninoWarnings();
[ $lockedStatus, $lockedBody ] = callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Locked out' ] );
ninoWarnings();
unlink( $locks );
if( $hadLocks === true )
	rename( $locksAside, $locks );
check( 'a list that cannot be locked answers that the albums could not be written - not that the album or the image is missing',
	$lockedStatus === 400 && galleryError( $lockedBody ) === $panelText['[[/_admin/gallery/error/save]]']
	&& galleryError( $lockedBody ) !== $panelText['[[/_admin/gallery/error/album]]'] );
check( '...and wrote nothing', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

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


// --- An album made on a page of its own -------------------------------------

echo "Gallery\\Admin - an album with sizes of its own\n";

$sizes = [ 'thumbWidth' => 80, 'thumbHeight' => 60, 'largeWidth' => 300, 'largeHeight' => 200, 'keepRatio' => true, 'columns' => 2 ];
$storedAlbum = static fn( string $key ): array => array_values( array_filter( (array) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ), static fn( mixed $entry ): bool => is_array( $entry ) === true && ( $entry['key'] ?? null ) === $key ) )[0] ?? [];

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'an album made the old way, with a key and a name, starts with the feature\'s settings - stored as its own six, so the feature changing them later does not change it',
	$status === 200 && ( galleryAlbumOf( $body, 'trip' )['settings'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ]
	&& ( $storedAlbum( 'trip' )['settings'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'name' => 'Sized', 'settings' => $sizes, 'isNew' => true ] );
check( 'the page of a new album makes one with album-save: the key, the name, the six sizes and the word that it is new - and the answer carries the sizes of every album',
	$status === 200 && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Sized' && ( galleryAlbumOf( $body, 'sized' )['settings'] ?? null ) === $sizes
	&& is_array( galleryAlbumOf( $body, 'trip' )['settings'] ?? null ) === true && count( galleryAlbumOf( $body, 'trip' )['settings'] ) === 6 );
check( '...and the record in the file holds them: the six, with their own values', ( $storedAlbum( 'sized' )['settings'] ?? null ) === $sizes && ( $storedAlbum( 'sized' )['images'] ?? null ) === [] );

$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'name' => 'Again', 'settings' => [ 'columns' => 8 ] + $sizes, 'isNew' => true ] );
check( 'a key that is taken is refused for a new album, in the panel\'s words - and neither the name nor the sizes of the album that has it are touched',
	$status === 400 && galleryError( $body ) === $panelText['[[/_admin/gallery/error/taken]]'] && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );
check( 'a key that is not a slug is refused as one, with or without sizes', callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'Sized!', 'name' => 'x', 'settings' => $sizes, 'isNew' => true ] )[0] === 400
	&& galleryError( callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => '', 'settings' => $sizes, 'isNew' => true ] )[1] ) === $panelText['[[/_admin/gallery/error/key]]'] );

// The page that makes an album is #gallery/new: an album with that key would be opened in its place by a reload
$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new', 'name' => 'New', 'settings' => $sizes, 'isNew' => true ] );
[ $statusPlain, $bodyPlain ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new', 'name' => 'New' ] );
check( 'the key "new" is the address of the page that makes an album, so no album is made with it - with the word that it is new or without it - and the refusal says why; nothing is written',
	$status === 400 && $statusPlain === 400 && galleryError( $body ) === $panelText['[[/_admin/gallery/error/reserved]]'] && galleryError( $bodyPlain ) === $panelText['[[/_admin/gallery/error/reserved]]']
	&& galleryError( $body ) !== $panelText['[[/_admin/gallery/error/key]]'] && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );
check( '...the panel has the words for it in both languages', str_contains( $panelText['[[/_admin/gallery/error/reserved]]'], '"new"' ) === true
	&& str_contains( ( include dirname( __DIR__ ). '/text/de_DE.php' )['[[/_admin/gallery/error/reserved]]'], '„new“' ) === true );

// An album that has the key already - the file may be one from before the panel refused it, or edited by hand - goes on working
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( $before, [ [ 'key' => 'new', 'name' => 'Made before', 'images' => [] ] ] ) );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new', 'name' => 'Still here', 'settings' => [ 'columns' => 3 ] ] );
[ $statusTaken, $bodyTaken ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new', 'name' => 'Again', 'isNew' => true ] );
check( 'an album that has the key already keeps working: it is renamed and its sizes are saved - and as a new album the key is taken, as for any other',
	$status === 200 && ( galleryAlbumOf( $body, 'new' )['name'] ?? null ) === 'Still here' && ( galleryAlbumOf( $body, 'new' )['settings']['columns'] ?? null ) === 3
	&& $statusTaken === 400 && galleryError( $bodyTaken ) === $panelText['[[/_admin/gallery/error/taken]]'] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $before );

// A key names a directory below /images/gallery, and one the file system cannot take makes every upload into the album fail
$longest = 'k'. str_repeat( 'x', \Nino\Modules\Gallery::MAX_KEY - 1 );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => $longest. 'x', 'name' => 'Long', 'settings' => $sizes, 'isNew' => true ] );
[ $statusPlain, $bodyPlain ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => $longest. 'x', 'name' => 'Long' ] );
check( 'a key of more than 64 characters is not the key of a new album - with the word that it is new or without it - and the refusal says how long one may be; nothing is written',
	$status === 400 && $statusPlain === 400 && galleryError( $body ) === str_replace( '%s', (string) \Nino\Modules\Gallery::MAX_KEY, $panelText['[[/_admin/gallery/error/long]]'] ) && galleryError( $bodyPlain ) === galleryError( $body )
	&& galleryError( $body ) !== $panelText['[[/_admin/gallery/error/key]]'] && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );
check( '...the panel has the words for it in both languages, each with the place for the number', str_contains( $panelText['[[/_admin/gallery/error/long]]'], '%s' ) === true
	&& str_contains( ( include dirname( __DIR__ ). '/text/de_DE.php' )['[[/_admin/gallery/error/long]]'], '%s' ) === true );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => $longest, 'name' => 'Longest', 'settings' => $sizes, 'isNew' => true ] );
[ $statusUpload, $bodyUpload ] = withUpload( $wide, static fn(): array => callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => $longest ] ) );
check( '...64 are the longest: such a key makes an album, and an upload into it is made',
	$status === 200 && ( galleryAlbumOf( $body, $longest )['name'] ?? null ) === 'Longest' && $statusUpload === 200 && count( galleryAlbumOf( $bodyUpload, $longest )['images'] ?? [] ) === 1
	&& count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/'. $longest ). '/*' ) ?: [] ) === 2 );
callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => $longest ] );

// An album that has a longer key from before - a file edited by hand, or written by the panel as it was - goes on working: only a new one is held to the number
$older = 'k'. str_repeat( 'x', 99 );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( $before, [ [ 'key' => $older, 'name' => 'Made before', 'images' => [] ] ] ) );
[ $statusRename, $bodyRename ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => $older, 'name' => 'Still here', 'settings' => [ 'columns' => 3 ] ] );
[ $statusDelete ] = callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => $older ] );
check( 'an album that has a longer key from before is renamed, has its sizes saved and is deleted all the same',
	$statusRename === 200 && ( galleryAlbumOf( $bodyRename, $older )['name'] ?? null ) === 'Still here' && ( galleryAlbumOf( $bodyRename, $older )['settings']['columns'] ?? null ) === 3 && $statusDelete === 200
	&& array_column( \Nino\Modules\Gallery::albums( $appData ), 'key' ) === array_column( $before, 'key' ) );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $before );

$refused = [];
foreach( [ 'thumbWidth' => [ 39, 1201, 'wide', 100.5, [ 1 ], null ], 'thumbHeight' => [ 39, 1201 ], 'largeWidth' => [ 199, 4001 ], 'largeHeight' => [ 199, 4001 ], 'columns' => [ 0, 9 ], 'keepRatio' => [ 'yes', 2, [] ] ] as $name => $values )
	foreach( $values as $value )
		if( callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'settings' => [ $name => $value ] ] )[0] !== 400 )
			$refused[] = $name. '='. json_encode( $value );
check( 'a size outside the range of the feature\'s setting, or one that is no number, is refused: below it and above it, for each of the six'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ), $refused === [] );
check( '...and nothing was written by any of the refusals', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'name' => 'Not written', 'settings' => [ 'thumbWidth' => 39, 'columns' => 9, 'keepRatio' => true, 'largeWidth' => 300 ] ] );
check( 'the refusal names what is wrong - as the panel names it, with the range - and not what is right, and is all or nothing: the name that came with it was not kept either',
	$status === 400 && galleryError( $body ) === str_replace( '%s', 'Thumbnail width (40–1200), Columns (1–8)', $panelText['[[/_admin/gallery/error/settings]]'] )
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'settings' => [ 'columns' => 5 ] ] );
check( 'sizes posted in part change those and leave the rest - the name is not in the request and is not touched either',
	$status === 200 && ( galleryAlbumOf( $body, 'sized' )['settings'] ?? null ) === array_replace( $sizes, [ 'columns' => 5 ] ) && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Sized' );

[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'settings' => [ 'thumbWidth' => '90', 'columns' => 8 ] ] );
check( 'a number written as digits is stored as a number, and 8 is the last column there is', $status === 200 && ( $storedAlbum( 'sized' )['settings']['thumbWidth'] ?? null ) === 90 && ( $storedAlbum( 'sized' )['settings']['columns'] ?? null ) === 8 );

callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'settings' => $sizes ] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'name' => 'Renamed' ] );
check( 'a rename is the name alone: the sizes of the album are the ones it had', $status === 200 && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Renamed' && ( galleryAlbumOf( $body, 'sized' )['settings'] ?? null ) === $sizes );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'name' => '', 'settings' => $sizes ] );
check( '...and a name that is empty leaves the name as it was, as it did before', $status === 200 && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Renamed' );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'made-before', 'name' => 'Made before' ] );
check( 'an album saved without the word that it is new is made if there is none and renamed if there is, as it always was: the page of a new album is the one that says it', $status === 200
	&& ( galleryAlbumOf( $body, 'made-before' )['name'] ?? null ) === 'Made before' && count( galleryAlbumOf( $body, 'made-before' )['settings'] ?? [] ) === 6 );
callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'made-before' ] );

// What an upload becomes
[ $status, $body ] = withUpload( $wide, static fn(): array => callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => 'sized' ] ) );
$first = galleryAlbumOf( $body, 'sized' )['images'][0] ?? [];
check( 'an upload into the album is made to the album\'s sizes and not to the feature\'s: a thumbnail of exactly 80x60 and the whole picture inside 300x200 - and the file names say so',
	$status === 200 && galleryDims( $appData, $first['thumb'] ?? '' ) === '80x60' && galleryDims( $appData, $first['large'] ?? '' ) === '300x150'
	&& str_contains( (string) ( $first['thumb'] ?? '' ), '.80x60.' ) === true && str_contains( (string) ( $first['large'] ?? '' ), '.fit300x200.' ) === true );

callGalleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'sized', 'settings' => [ 'thumbWidth' => 100, 'thumbHeight' => 100, 'largeWidth' => 250, 'largeHeight' => 250, 'keepRatio' => false ] ] );
[ $status, $body ] = withUpload( $wide, static fn(): array => callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => 'sized' ] ) );
$second = galleryAlbumOf( $body, 'sized' )['images'][1] ?? [];
check( 'changed sizes are the ones of the next upload: the picture is cropped to 250x250 now - and the one that was there keeps the size it was made at',
	galleryDims( $appData, $second['thumb'] ?? '' ) === '100x100' && galleryDims( $appData, $second['large'] ?? '' ) === '250x250' && str_contains( (string) ( $second['large'] ?? '' ), '.250x250.' ) === true
	&& galleryDims( $appData, $first['thumb'] ?? '' ) === '80x60' && galleryDims( $appData, $first['large'] ?? '' ) === '300x150' );

// A record from before the sizes were the album's: it holds none, and reads the feature's
$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$stored[] = [ 'key' => 'old', 'name' => 'Old', 'images' => [] ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'a record without sizes - which is what every album was before they were its own - reads the feature\'s settings, so the panel has all six to show',
	( galleryAlbumOf( $body, 'old' )['settings'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );
[ $status, $body ] = withUpload( $wide, static fn(): array => callGalleryAdmin( $appData, 'gallery/upload', [ 'album' => 'old' ] ) );
$oldImage = galleryAlbumOf( $body, 'old' )['images'][0] ?? [];
check( '...and an upload into it is made to those', galleryDims( $appData, $oldImage['thumb'] ?? '' ) === '200x200' && galleryDims( $appData, $oldImage['large'] ?? '' ) === '600x300' );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 90, 'thumbHeight' => 90, 'largeWidth' => 500, 'largeHeight' => 500, 'keepRatio' => true, 'columns' => 7 ] );
[ $status, $body ] = callGalleryAdmin( $appData, 'gallery/list' );
check( 'the feature\'s settings are what such an album reads as they are now - a default that was copied would have gone stale - while an album with sizes of its own keeps them',
	( galleryAlbumOf( $body, 'old' )['settings']['columns'] ?? null ) === 7 && ( galleryAlbumOf( $body, 'old' )['settings']['thumbWidth'] ?? null ) === 90
	&& ( galleryAlbumOf( $body, 'sized' )['settings']['thumbWidth'] ?? null ) === 100 && ( $body['defaults']['columns'] ?? null ) === 7 );

$oldImageId = (string) ( $oldImage['id'] ?? '' );
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'old', 'id' => $oldImageId, 'locale' => 'en_US', 'caption' => 'Rewritten' ] );
callGalleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'sized', 'order' => array_reverse( array_column( galleryAlbumOf( callGalleryAdmin( $appData, 'gallery/list' )[1], 'sized' )['images'] ?? [], 'id' ) ) ] );
check( 'no other action copies the defaults into such a record: it is a record without sizes after the file was written again, by a caption and by an order',
	array_key_exists( 'settings', $storedAlbum( 'old' ) ) === false && ( $storedAlbum( 'old' )['images'][0]['caption'] ?? null ) === [ 'en_US' => 'Rewritten' ] );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );
callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'old' ] );
callGalleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'sized' ] );
check( 'those albums go with their pictures, and the one the rest of the test is about is as it was',
	count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/sized' ). '/*' ) ?: [] ) === 0 && count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/old' ). '/*' ) ?: [] ) === 0
	&& array_column( \Nino\Modules\Gallery::albums( $appData ), 'key' ) === [ 'trip' ] && count( \Nino\Modules\Gallery::albums( $appData )[0]['images'] ) === 2 );

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

/*	The count of columns: the call's own, else the album's, else the feature's. Four albums that draw the same
	picture - the first image of the album above - with a count of 3, a count that is no count, an invalid
	entry that is not a map, and none; the feature's is 6 while they are drawn	*/
$albumsNow = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$picture = $albumsNow[0]['images'][0];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( $albumsNow, [
	[ 'key' => 'three', 'name' => 'Three', 'images' => [ $picture ], 'settings' => [ 'columns' => 3 ] ],
	[ 'key' => 'nine', 'name' => 'Nine', 'images' => [ $picture ], 'settings' => [ 'columns' => 9 ] ],
	[ 'key' => 'broken', 'name' => 'Broken', 'images' => [ $picture ], 'settings' => 'wide' ],
	[ 'key' => 'none', 'name' => 'None', 'images' => [ $picture ] ],
] ) );
\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 6 ] );
$columnsOf = static function( string $call ) use ( &$appData ): string {
	return preg_match( '/--nino-gallery-columns:(\d+)/', \Nino\Html::renderHtml( $appData, $call ), $found ) === 1 ? $found[1] : '';
};
check( 'the grid is drawn in the columns of its album: an album with three draws three', $columnsOf( '[gallery album="three"]' ) === '3' );
check( '...a call that sets a count of its own has the last word, and a count that is none is 4 as it always was - not the album\'s, not the feature\'s',
	$columnsOf( '[gallery album="three" columns="2"]' ) === '2' && $columnsOf( '[gallery album="three" columns="9"]' ) === '4' );
check( '...an album that holds none, or one that is no count, or none that is a map, draws the feature\'s: six', $columnsOf( '[gallery album="none"]' ) === '6'
	&& $columnsOf( '[gallery album="nine"]' ) === '6' && $columnsOf( '[gallery album="broken"]' ) === '6' );
\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $albumsNow );

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
\Nino\Html::addFills( $appData, [ '[[/project/gallery/one/caption]]' => 'Am Pass "oben"' ], '*' );
callGalleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'en_US', 'caption' => '[[/project/gallery/one/caption]]' ] );
$filled = \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' );
check( 'a caption written as a fill is resolved, and what comes out is escaped', str_contains( $filled, 'data-caption="Am Pass &quot;oben&quot;"' ) === true
	&& str_contains( $filled, '[[/project/gallery/' ) === false );

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


/**
 *	A module of a project that changes the markup of the gallery the way a project changes a component's: it
 *	registers one of its own under the same name from its init(), with the schema it found, and so replaces the
 *	one the kernel made from the manifest. A project's module is listed ahead of the features - a feature is
 *	appended to the list - so its init() runs after the Components module's and before the Gallery's
 */
class GalleryProjectModule {

	public static function init( array &$appData ): void {

		$schema = \Nino\Modules\Components::components( $appData )['gallery'] ?? null;

		if( $schema === null )
			return;

		$schema['label'] = 'Project gallery';

		\Nino\Modules\Components::addComponent( $appData, 'gallery', [ self::class, 'componentGallery' ], $schema );
	}

	public static function componentGallery( array &$appData, array $args ): string {
		return '<div class="project-gallery">'. htmlspecialchars( $args['album'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ). '</div>';
	}
}

/**
 *	A module of a project that changes the schema of the gallery and nothing else: it registers [gallery] again, under
 *	the renderer of the feature itself - there is no markup of its own to write - with the schema it found, changed as
 *	$appData['./nino/test/gallery-schema'] says. 'without-album' takes the album away, 'without-attributes' takes
 *	every attribute away, 'default-album' gives the album a default of its own that no album has, 'existing-album'
 *	one that an album has, 'select-album' makes it a select of choices of its own, and anything else changes only
 *	the label
 */
class GalleryProjectSchemaModule {

	public static function init( array &$appData ): void {

		$schema = \Nino\Modules\Components::components( $appData )['gallery'] ?? null;

		if( $schema === null )
			return;

		$change = (string) ( $appData['./nino/test/gallery-schema'] ?? '' );

		if( $change === 'without-album' )
			unset( $schema['attributes']['album'] );
		else if( $change === 'without-attributes' )
			$schema['attributes'] = [];
		else if( $change === 'default-album' )
			$schema['attributes']['album']['default'] = 'gone';
		else if( $change === 'existing-album' )
			$schema['attributes']['album']['default'] = 'trip';
		else if( $change === 'select-album' )
			$schema['attributes']['album'] = [ 'type' => 'select', 'options' => [ '', 'curated', 'trip' ], 'default' => '', 'label' => 'Album', 'hint' => '' ];
		else
			$schema['label'] = 'Project gallery';

		\Nino\Modules\Components::addComponent( $appData, 'gallery', [ \Nino\Modules\Gallery::class, 'componentGallery' ], $schema );
	}
}


// --- Components ----------------------------------------------------------------

echo "Components - [gallery] as the Builder offers it\n";

if( class_exists( '\\Nino\\Modules\\Components' ) === false )
	echo "  --  this Nino has no \\Nino\\Modules\\Components: the manifest's components are not read here, and the shortcode registers itself\n";
else ( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there - but for an album that
	// nobody has: what the select makes of that one is a check of its own, below
	$calls = [
		'[gallery]',
		'[gallery album="trip"]',
		'[gallery album="trip" columns="3"]',
		'[gallery album="trip" columns="2"]',
		'[gallery album="trip" columns="9"]',
		'[gallery album="trip" columns="0"]',
		'[gallery album="trip" columns="3.5"]',
		'[gallery album="trip" columns="03"]',
		'[gallery album="trip" columns="x"]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );
	$nowhere = \Nino\Html::renderHtml( $appData, '[gallery album="nowhere"]' );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once. What
		the kernel has registered from the manifest alone is kept, to be compared	*/
	$probe = $appData;
	foreach( [ 'gallery' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	$manifestOnly = $probe;
	\Nino\Modules\Gallery::init( $probe );
	ninoWarnings();

	$registered	= \Nino\Modules\Components::components( $probe )['gallery'] ?? [];
	$album			= $registered['attributes']['album'] ?? [];
	$withoutAlbum	= static function( array $schema ): array {
		unset( $schema['attributes']['album'] );
		return $schema;
	};

	check( 'the kernel registers the component from the manifest alone, with the schema the manifest declares - the album a string, because a manifest is read without the data of a project',
		( \Nino\Modules\Components::components( $manifestOnly )['gallery'] ?? null ) === ( $declared['gallery'] ?? false ) && ( $declared['gallery']['attributes']['album']['type'] ?? null ) === 'string' );
	check( 'the feature registers it again when it runs: the schema of the manifest, but for the album',
		$registered !== [] && $withoutAlbum( $registered ) === $withoutAlbum( $declared['gallery'] ?? [] ) );
	check( '...the album a select of the albums there are - the empty choice first, which is the first album there is and the default, then the key of each album - with the label and the hint of the manifest',
		( $album['type'] ?? null ) === 'select' && ( $album['options'] ?? null ) === [ '', 'trip' ] && ( $album['default'] ?? null ) === ''
		&& ( $album['label'] ?? null ) === ( $declared['gallery']['attributes']['album']['label'] ?? false ) && ( $album['hint'] ?? null ) === ( $declared['gallery']['attributes']['album']['hint'] ?? false ) );
	check( '...whose hint says, in both languages, what none is: the first album there is', str_contains( \Nino\Features::localized( $album['hint'] ?? '', 'en_US' ), 'first album there is' ) === true
		&& str_contains( \Nino\Features::localized( $album['hint'] ?? '', 'de_DE' ), 'erste Album' ) === true );
	check( '...and keeps the place the first registration gave the component: the Builder lists them in the order they were registered',
		array_keys( \Nino\Modules\Components::components( $probe ) ) === array_keys( \Nino\Modules\Components::components( $manifestOnly ) ) );
	check( '...and what the feature registers is answered once - the component by its wrapper',
		\Nino\Features::shortcodes( $probe, 'gallery' ) === [ 'gallery' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/gallery' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'gallery' ) === [ 'album' => '', 'columns' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	/*	The one call form that is not rendered as it was. A select reads a value that is not among its options
		as its default - which is how the wrapper reads every select - so a key no album has is no key at all
		to the select, and no key is the first album there is. The manifest's string, and the shortcode
		of a Nino without the Components module, draw nothing for it	*/
	check( 'a key that no album has draws nothing as the shortcode has it - and as the kernel registers the component from the manifest alone',
		$nowhere === '' && \Nino\Html::renderHtml( $manifestOnly, '[gallery album="nowhere"]' ) === '' );
	check( '...but the select the feature registers on top of it reads it for no key at all: the first album there is. The one form of the manual that does not render as it did',
		$before['[gallery]'] !== '' && \Nino\Html::renderHtml( $probe, '[gallery album="nowhere"]' ) === $before['[gallery]'] );

	ninoWarnings();

	/*	The boot the kernel does: every module's init(), in the order the project lists them. The
		Components module is listed ahead of the features - AppData::init() puts it before the first
		module that is no default - which is what makes the feature's registration the one that stays	*/
	$boot = $appData;
	foreach( [ 'gallery' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $boot, '/nino/html/shortcode/'. $shortcode );
	$boot['/nino/modules'] = [ '\\Nino\\Modules\\Components', '\\Nino\\Modules\\Lightbox', '\\Nino\\Modules\\Gallery' ];
	\Nino\Modules::callModules( $boot, 'init' );
	ninoWarnings();
	check( 'a boot that lists the Components module ahead of the feature ends with the select - the registration of the feature is the one after the manifest\'s',
		( \Nino\Modules\Components::components( $boot )['gallery']['attributes']['album']['type'] ?? null ) === 'select'
		&& ( \Nino\Modules\Components::components( $boot )['gallery']['attributes']['album']['options'] ?? null ) === [ '', 'trip' ]
		&& count( \Nino\Callbacks::registered( $boot, '/nino/html/shortcode/gallery' ) ) === 1 );

	$late = $appData;
	foreach( [ 'gallery' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $late, '/nino/html/shortcode/'. $shortcode );
	$late['/nino/modules'] = [ '\\Nino\\Modules\\Lightbox', '\\Nino\\Modules\\Gallery', '\\Nino\\Modules\\Components' ];
	\Nino\Modules::callModules( $late, 'init' );
	ninoWarnings();
	check( 'a project that lists the feature ahead of the module has the manifest\'s string - and a gallery that works all the same, answered once',
		( \Nino\Modules\Components::components( $late )['gallery']['attributes']['album']['type'] ?? null ) === 'string' && \Nino\Features::shortcodes( $late, 'gallery' ) === [ 'gallery' ]
		&& count( \Nino\Callbacks::registered( $late, '/nino/html/shortcode/gallery' ) ) === 1 && \Nino\Html::renderHtml( $late, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] );

	/*	A project that changes the markup of the gallery: its module registers a [gallery] of its own, between the
		Components module and the feature, and replaces the one the kernel made from the manifest. The feature does
		not register the albums' select over it - what it registered is its renderer, and its schema is the one the
		Builder is offered - and a callback that something else put beside the component is left where it is too	*/
	$mine = $appData;
	foreach( [ 'gallery' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $mine, '/nino/html/shortcode/'. $shortcode );
	$mine['/nino/modules'] = [ '\\Nino\\Modules\\Components', 'GalleryProjectModule', '\\Nino\\Modules\\Lightbox', '\\Nino\\Modules\\Gallery' ];
	\Nino\Modules::callModules( $mine, 'init' );
	ninoWarnings();
	$project = \Nino\Modules\Components::components( $mine )['gallery'] ?? [];
	check( 'a module of the project that registers a [gallery] of its own ahead of the feature keeps it: its renderer draws the call and not the grid of the feature',
		\Nino\Html::renderHtml( $mine, '[gallery album="trip"]' ) === '<div class="project-gallery">trip</div>' );
	check( '...with the schema it registered - its label, and the album the manifest\'s string - and not the select of the albums the feature registers otherwise',
		( $project['label'] ?? null ) === 'Project gallery' && ( $project['attributes']['album']['type'] ?? null ) === 'string' && isset( $project['attributes']['album']['options'] ) === false );
	check( '...registered once, and by the project: the feature owns no shortcode in that boot',
		count( \Nino\Callbacks::registered( $mine, '/nino/html/shortcode/gallery' ) ) === 1 && \Nino\Features::shortcodes( $mine, 'gallery' ) === [] );

	/*	A project that changes the schema of the gallery and keeps the renderer of the feature: its module registers [gallery]
		again under [Gallery, componentGallery], between the Components module and the feature. That the renderer is the
		feature's does not make the registration the manifest's, so the album does: the select of the albums goes over the
		manifest's own, a string with no default, and over no other. Over a schema without the album it would be an attribute
		without a default, and over an album with a default of its own - a key that is among the options while the album is
		there, and no longer once it is deleted - one whose default is not among them: schemas the kernel refuses with an
		E_USER_ERROR, which ends the request of a visitor as much as the workbench's. The boot records what it raised
		instead of ending with it, so a check says so	*/
	$bootWith = static function( string $change ) use ( $appData ): array {

		$boot = $appData;
		foreach( [ 'gallery' ] as $shortcode )
			\Nino\Callbacks::removeCallbacks( $boot, '/nino/html/shortcode/'. $shortcode );
		$boot['/nino/modules'] = [ '\\Nino\\Modules\\Components', 'GalleryProjectSchemaModule', '\\Nino\\Modules\\Lightbox', '\\Nino\\Modules\\Gallery' ];
		$boot['./nino/test/gallery-schema'] = $change;

		$refused = [];
		/*	Every level but E_DEPRECATED is recorded, with the level in front of the message. The handler answers true so
			that the boot goes on, and what it does not keep is lost - and a warning must not be: Nino's runtime does not
			survive one (only E_USER_NOTICE, E_USER_WARNING, E_USER_DEPRECATED and E_DEPRECATED are non-fatal there, see
			\Nino\Runtime::NON_FATAL_LEVELS). E_DEPRECATED is left out because php 8.4 adds one to every E_USER_ERROR,
			which is not what is asked about here	*/
		set_error_handler( static function( int $level, string $message ) use ( &$refused ): bool {
			if( $level !== E_DEPRECATED )
				$refused[] = $level. ': '. $message;
			return true;
		} );
		\Nino\Modules::callModules( $boot, 'init' );
		restore_error_handler();
		ninoWarnings();

		return [ $boot, $refused ];
	};

	[ $bare, $refused ] = $bootWith( 'without-album' );
	$bareSchema = \Nino\Modules\Components::components( $bare )['gallery'] ?? [];
	check( 'a module that registers the feature\'s own renderer under a schema without the album does not end the boot: the feature registers nothing the kernel refuses'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ), $refused === [] );
	check( '...the schema stays as the module registered it - no album to choose from, and no select put in the place of one', $bareSchema === $withoutAlbum( $declared['gallery'] ?? [] ) && isset( $bareSchema['attributes']['album'] ) === false );
	check( '...and the gallery draws: the first album there is, whichever album a call names, because that schema has none to read',
		$before['[gallery]'] !== '' && \Nino\Html::renderHtml( $bare, '[gallery]' ) === $before['[gallery]'] && \Nino\Html::renderHtml( $bare, '[gallery album="trip"]' ) === $before['[gallery album="trip"]']
		&& \Nino\Html::renderHtml( $bare, '[gallery album="nowhere"]' ) === $before['[gallery]'] );
	check( '...registered once, by the module and with the feature\'s renderer: the feature owns the shortcode', count( \Nino\Callbacks::registered( $bare, '/nino/html/shortcode/gallery' ) ) === 1 && \Nino\Features::shortcodes( $bare, 'gallery' ) === [ 'gallery' ] );

	// A schema that declares no attribute at all: the renderer reads what is not there as what nobody wrote - a warning would end the request of a page that has the gallery
	[ $plain, $refused ] = $bootWith( 'without-attributes' );
	ninoWarnings();
	$plainHtml = \Nino\Html::renderHtml( $plain, '[gallery]' );
	check( 'a module that registers the feature\'s renderer under a schema without any attribute does not end the boot, and its gallery draws the first album in the columns the album has - with no warning about the arguments that are not there'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ),
		$refused === [] && $plainHtml === $before['[gallery]'] && $plainHtml !== '' && ninoWarnings() === [] && ( \Nino\Modules\Components::components( $plain )['gallery']['attributes'] ?? null ) === [] );

	[ $gone, $refused ] = $bootWith( 'default-album' );
	$goneAlbum = \Nino\Modules\Components::components( $gone )['gallery']['attributes']['album'] ?? [];
	check( 'a module whose album has a default of its own, a key that no album has, does not end the boot either'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ), $refused === [] );
	check( '...its album stays a string with that default, and the feature puts no select of the albums over it', ( $goneAlbum['type'] ?? null ) === 'string' && ( $goneAlbum['default'] ?? null ) === 'gone' && isset( $goneAlbum['options'] ) === false );
	check( '...a call that names an album draws it, and one that names none draws what the module made of it: the album with that key, which no album has, so nothing',
		\Nino\Html::renderHtml( $gone, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] && \Nino\Html::renderHtml( $gone, '[gallery]' ) === '' && $before['[gallery]'] !== '' );

	/*	The default of an album that exists is among the options a select of the albums would have, so the kernel would accept
		it: that the schema is valid does not make the album the manifest's. It stays the module's string - and does not turn
		into a select while the album is there and back into a string once it is deleted. The same goes for a select of the
		module's own, whose choices are its own	*/
	[ $trip, $refused ] = $bootWith( 'existing-album' );
	$tripAlbum = \Nino\Modules\Components::components( $trip )['gallery']['attributes']['album'] ?? [];
	check( 'a module whose album has the key of an album for its default keeps its string: the select of the albums would be valid, and is not offered over it'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ),
		$refused === [] && ( $tripAlbum['type'] ?? null ) === 'string' && ( $tripAlbum['default'] ?? null ) === 'trip' && isset( $tripAlbum['options'] ) === false
		&& \Nino\Html::renderHtml( $trip, '[gallery]' ) === $before['[gallery album="trip"]'] && \Nino\Html::renderHtml( $trip, '[gallery album="nowhere"]' ) === '' );

	[ $curated, $refused ] = $bootWith( 'select-album' );
	check( 'a module whose album is a select of its own keeps its choices: the albums of the project are not put in their place',
		$refused === [] && ( \Nino\Modules\Components::components( $curated )['gallery']['attributes']['album']['options'] ?? null ) === [ '', 'curated', 'trip' ]
		&& \Nino\Html::renderHtml( $curated, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] );

	// A change to anything else of the schema is the project's too, and the albums are offered all the same
	[ $labelled, $refused ] = $bootWith( 'label' );
	$labelledSchema = \Nino\Modules\Components::components( $labelled )['gallery'] ?? [];
	check( 'a module that changes something else of the schema, its label, and keeps the album gets the select of the albums over what it registered: its label stays',
		$refused === [] && ( $labelledSchema['label'] ?? null ) === 'Project gallery' && ( $labelledSchema['attributes']['album']['type'] ?? null ) === 'select'
		&& ( $labelledSchema['attributes']['album']['options'] ?? null ) === [ '', 'trip' ] && \Nino\Html::renderHtml( $labelled, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] );

	$beside = $appData;
	foreach( [ 'gallery' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $beside, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $beside );
	\Nino\Html::addShortcode( $beside, 'gallery', static fn( array &$appData, mixed &$args ): string => 'beside' );
	\Nino\Modules\Gallery::init( $beside );
	ninoWarnings();
	check( 'a second callback beside the component is left where it is, and the feature registers nothing over the two - what is registered is no longer the manifest\'s alone',
		count( \Nino\Callbacks::registered( $beside, '/nino/html/shortcode/gallery' ) ) === 2
		&& ( \Nino\Modules\Components::components( $beside )['gallery']['attributes']['album']['type'] ?? null ) === 'string' );

	// The albums of an earlier version hold no sizes of their own and read the feature's:
	// the file as it was written then
	$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_map( static function( array $entry ): array {
		unset( $entry['settings'] );
		return $entry;
	}, $stored ) );

	// The forms that rendered four columns above are rendered again with the setting at 6:
	// a count the control does not offer is no count, and what is no count was four
	// before the wrapper as well - not the setting
	$probe[ \Nino\Features::STATE_KEY ]['gallery']['settings'] = [ 'columns' => 6 ];
	$plain = $appData;
	$plain[ \Nino\Features::STATE_KEY ]['gallery']['settings'] = [ 'columns' => 6 ];
	foreach( [ '[gallery album="trip" columns="9"]', '[gallery album="trip" columns="0"]', '[gallery album="trip" columns="3.5"]', '[gallery album="trip" columns="03"]', '[gallery album="trip" columns="x"]', '[gallery album="trip" columns="3"]', '[gallery album="trip"]' ] as $call )
		check( 'renders as it did with the setting at 6: '. $call, \Nino\Html::renderHtml( $probe, $call ) === \Nino\Html::renderHtml( $plain, $call ) );
	check( 'without a count of columns the setting\'s is taken by an album that holds none of its own, which a default of 4 in the schema would have overridden - and a count of its own still wins',
		str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip"]' ), '--nino-gallery-columns:6' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" columns="3"]' ), '--nino-gallery-columns:3' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" columns="9"]' ), '--nino-gallery-columns:4' ) === true );
	// The one form the wrapper cannot tell from an omitted attribute
	check( '...and an explicit columns="" is that omitted attribute, as the wrapper hands it on',
		str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" columns=""]' ), '--nino-gallery-columns:6' ) === true );

	// An album that has a count of its own: the call that sets none takes it - the wrapper hands the empty
	// attribute on as none - and not the setting of the feature, which is 6 here
	$stored[0]['settings']['columns'] = 3;
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
	check( 'an album with a count of columns of its own draws it when the call sets none - through the wrapper and as a plain call - and not the feature\'s 6',
		str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip"]' ), '--nino-gallery-columns:3' ) === true && str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" columns=""]' ), '--nino-gallery-columns:3' ) === true
		&& str_contains( \Nino\Html::renderHtml( $plain, '[gallery album="trip"]' ), '--nino-gallery-columns:3' ) === true );
	check( '...the call\'s own count has the last word, and a count that is none is 4, as it was: not the album\'s and not the feature\'s',
		str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" columns="2"]' ), '--nino-gallery-columns:2' ) === true && str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" columns="9"]' ), '--nino-gallery-columns:4' ) === true );
	// The album as it was. The copies of the project's data read a file by its time and its size, and a file
	// written twice within a second to the same size is the one they hold - so they are told to read it again
	$stored[0]['settings']['columns'] = 4;
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
	unset( $probe['./nino/filesystem/cache'][ \Nino\Modules\Gallery::ALBUMS ], $plain['./nino/filesystem/cache'][ \Nino\Modules\Gallery::ALBUMS ] );
	unset( $probe[ \Nino\Features::STATE_KEY ]['gallery']['settings'] );
	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[gallery album="" columns="" class=""]' ) === $before['[gallery]'] );
	check( 'a class of its own comes after the grid\'s own, no class leaves no space behind',
		str_contains( \Nino\Html::renderHtml( $probe, '[gallery album="trip" class="trip"]' ), '<ul class="nino-gallery-grid trip" style="--nino-gallery-columns:4">' ) === true
		&& str_contains( $before['[gallery album="trip"]'], '<ul class="nino-gallery-grid" style="--nino-gallery-columns:4">' ) === true );

} )( $appData );

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

// No album: the select has the empty choice alone, which is the default - and a page that names the component draws nothing
if( class_exists( '\\Nino\\Modules\\Components' ) === true ) {
	$empty = $appData;
	foreach( [ 'gallery' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $empty, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $empty );
	\Nino\Modules\Gallery::init( $empty );
	ninoWarnings();
	check( 'with no album the select of the component has the empty choice and nothing else, and the page draws nothing for it',
		( \Nino\Modules\Components::components( $empty )['gallery']['attributes']['album']['options'] ?? null ) === [ '' ] && ( \Nino\Modules\Components::components( $empty )['gallery']['attributes']['album']['default'] ?? null ) === ''
		&& \Nino\Modules\Gallery::keys( $appData ) === [] && \Nino\Html::renderHtml( $empty, '[gallery]' ) === '' && \Nino\Html::renderHtml( $empty, '[gallery album="trip"]' ) === '' );
}

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
