<?php
declare(strict_types=1);

/**
 *	Nino
 *	gallery-smoke.php	The test of the feature Gallery, structural: the manifest, the folders of the component, its group and
 *										block, what the grid draws and what it reads from the data of a project of Nino 1, the panel's actions with
 *										their rights, what an upload becomes and what it is refused, and the shape of the two scripts. The overlay's
 *										behaviour is gallery-js-smoke.js, which this suite starts where node is on the path.
 *
 *	Usage: php features/Gallery/tests/gallery-smoke.php
 *	       NINO_ROOT=../nino php features/Gallery/tests/gallery-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );

// What an upload needs to be made into two sizes; a PHP without it runs the rest and says so
$gd = extension_loaded( 'gd' ) === true;

/** The bytes of a jpeg of one colour, with an EXIF segment that holds a sentence nobody may read afterwards */
function galleryJpeg( int $width, int $height, string $secret = '' ): string {

	$image = imagecreatetruecolor( $width, $height );
	imagefill( $image, 0, 0, (int) imagecolorallocate( $image, 30, 90, 160 ) );
	ob_start();
	imagejpeg( $image, null, 88 );
	$bytes = (string) ob_get_clean();

	if( $secret === '' )
		return $bytes;

	$exif = "Exif\0\0". $secret;

	return substr( $bytes, 0, 2 ). "\xFF\xE1". pack( 'n', strlen( $exif ) + 2 ). $exif. substr( $bytes, 2 );
}

/** One upload as the workbench's endpoint gets it: the bytes in a temporary file named in $_FILES, the name the browser sent beside it */
function galleryUpload( array &$appData, string $album, string $bytes, string $name = 'photo.jpg' ): array {

	$tmp = (string) tempnam( ninoSandboxDir( $appData ), 'up' );
	file_put_contents( $tmp, $bytes );
	$_FILES = [ 'file' => [ 'name' => $name, 'type' => 'image/jpeg', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ] ];

	$answer = ninoCall( $appData, [ \Nino\Modules\Gallery\Admin::class, 'apiUpload' ], [ 'album' => $album ], 'gallery/upload' );
	$_FILES = [];

	return $answer;
}

function galleryAdmin( array &$appData, string $action, array $data = [] ): array {

	$methods = [ 'gallery/list' => 'apiList', 'gallery/album-save' => 'apiAlbumSave', 'gallery/album-delete' => 'apiAlbumDelete', 'gallery/image-save' => 'apiImageSave', 'gallery/image-delete' => 'apiImageDelete', 'gallery/reorder' => 'apiReorder' ];

	return ninoCall( $appData, [ \Nino\Modules\Gallery\Admin::class, $methods[$action] ], $data, $action );
}

/** The files below the image folder of a sandbox, relative to it */
function galleryFiles( array &$appData ): array {

	$base = \Nino\Filesystem::path( $appData, '/images' );
	$files = [];

	if( is_dir( $base ) === false )
		return [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( $file->isFile() === true )
			$files[] = substr( (string) $file, strlen( $base ) + 1 );

	sort( $files );

	return $files;
}

/** A project with the feature on and the albums in their file, the register read after it: the choices of the album are asked once, at the first call */
function gallerySite( string $root, array $albums, ?array $fills = null ): array {

	$site = ninoSandbox( 'gallery' );
	$site['./nino/filesystem/path'] = $root;
	\Nino\Features::activate( $site, 'basis' );
	\Nino\Features::activate( $site, 'gallery' );
	$site['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/modules'] ?? [] );
	\Nino\Filesystem::putFileContent( $site, \Nino\Modules\Gallery::ALBUMS, $albums );
	\Nino\Html::init( $site );
	\Nino\Features::registerComponents( $site );
	\Nino\Modules::callModules( $site, 'init' );
	\Nino\Features::registerParts( $site );
	\Nino\Html::addFills( $site, $fills ?? [ '/feature/gallery/image/unnamed' => '%s, picture %s' ], '*' );
	ninoWarnings();

	return $site;
}

function galleryDraw( array &$site, string $source ): string {
	return trim( \Nino\Html::renderSource( $site, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $site ) ) );
}

$appData = ninoSandbox( 'gallery' );
$appData['./nino/filesystem/path'] = $root;
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, is written for this kernel, and brings no component and no stylesheet of the old kind, and requires Basis',
	is_array( $manifest ) === true && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false && $manifest['requires'] === [ 'basis' ]
	&& $manifest['assets'] === [ '/features/Gallery/assets/gallery.js' ] && in_array( \Nino\Modules\Gallery::ALBUMS, $manifest['data'], true ) === true );
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'gallery' ) === true );

$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'gallery' ) === [] );
$schema = \Nino\Components::components( $appData )['gallery'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table', ( $schema['group'] ?? '' ) === 'gallery' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true && in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true );
$unlabelled = [];
foreach( $schema['attributes'] ?? [] as $name => $attribute )
	if( isset( $attribute['label']['de_DE'], $attribute['label']['en_US'] ) === false )
		$unlabelled[] = $name;
check( 'every attribute is labelled in both languages, and the album is a choice of the albums there are', $unlabelled === [] && ( (array) include $dir. '/components/gallery/component.php' )['attributes']['album']['options'] === '\Nino\Modules\Gallery::albumOptions' );
$group = \Nino\Components::groups( $appData )['gallery'] ?? [];
check( 'the group has a standard part, which keeps the rule of mixing, and two knobs', ( $group['part'] ?? false ) === true && \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/gallery/part/part.css' ), [ 'gallery' ] ) === []
	&& array_keys( $group['knobs'] ?? [] ) === [ 'gallery.gap', 'gallery.radius' ] );
check( 'a tool offers it in a column, with the two looks as variants', in_array( 'gallery', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true && $schema['variants'] === [ 'regular', 'mat', 'seamless' ] );
check( 'the part is in the style bundle, and so is the overlay', str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.gallery-view' ) === true );
check( 'the demo is read without a note', ( $group['demo'] ?? '' ) !== '' );
$block = \Nino\Blocks::get( $appData, 'gallery-album' );
check( 'the block is valid, names the feature and misses nothing', is_array( $block ) === true && in_array( 'gallery', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );

$empty = gallerySite( $root, [] );
check( 'with no album the call draws nothing', galleryDraw( $empty, '[gallery]' ) === '' && galleryDraw( $empty, '[gallery album="nothing"]' ) === '' && \Nino\Modules\Gallery::albumOptions( $empty, 'album' ) === [ '' => [ 'de_DE' => 'Erstes Album', 'en_US' => 'First album' ] ] );

if( $gd === false )
	echo "  --  - gd is not here: what an upload becomes, and what the panel does with it, was NOT run\n";

// --- a project of Nino 1: the file as that version wrote it, read as it is ----------------------------------------------------

$old = [
	[ 'key' => 'trip', 'name' => 'The trip', 'settings' => [ 'thumbWidth' => 400, 'thumbHeight' => 300, 'largeWidth' => 1600, 'largeHeight' => 1200, 'keepRatio' => true, 'columns' => 3 ], 'images' => [
		[ 'id' => '0123456789abcdef', 'thumb' => 'gallery/trip/0123456789abcdef.400x300.webp', 'large' => 'gallery/trip/0123456789abcdef.fit1600x1200.webp', 'alt' => 'The pass', 'caption' => [ 'de_DE' => 'Der Pass', 'en_US' => 'The pass' ] ],
		[ 'id' => 'fedcba9876543210', 'thumb' => 'gallery/trip/fedcba9876543210.400x300.webp', 'large' => 'gallery/trip/fedcba9876543210.fit1600x1200.webp', 'alt' => '', 'caption' => '' ],
		// Not pictures of an album: a name that climbs out, an absolute one, one in another folder of the images, one with a control character
		[ 'id' => 'aaaaaaaaaaaaaaaa', 'thumb' => '../private/config.php', 'large' => 'gallery/trip/aaaaaaaaaaaaaaaa.webp' ],
		[ 'id' => 'bbbbbbbbbbbbbbbb', 'thumb' => '/etc/passwd', 'large' => 'gallery/trip/bbbbbbbbbbbbbbbb.webp' ],
		[ 'id' => 'cccccccccccccccc', 'thumb' => 'elements/news/launch.800x600.webp', 'large' => 'gallery/trip/cccccccccccccccc.webp' ],
		[ 'id' => 'dddddddddddddddd', 'thumb' => "gallery/trip/a\x01.webp", 'large' => 'gallery/trip/dddddddddddddddd.webp' ],
	] ],
	[ 'key' => 'Not A Key', 'name' => 'Refused', 'images' => [] ],
	[ 'key' => 'empty', 'name' => 'Nothing yet', 'images' => [] ],
];
$site = gallerySite( $root, $old );
$url = static fn( string $name ): string => \Nino\Images::getUrl( $site, $name );
$trip = galleryDraw( $site, '[gallery album="trip"]' );
check( 'a file of Nino 1 is read as it is: three pictures of the six - a name of another folder of the images is read, as the kernel reads it - the albums of the pattern, the album\'s own sizes and columns',
	array_column( \Nino\Modules\Gallery::albums( $site ), 'key' ) === [ 'trip', 'empty' ] && count( \Nino\Modules\Gallery::album( $site, 'trip' )['images'] ) === 3
	&& str_contains( $trip, 'src="'. $url( 'elements/news/launch.800x600.webp' ). '"' ) === true
	&& str_contains( $trip, '--gallery-columns:3;--gallery-thumb-width:400;--gallery-thumb-height:300' ) === true && substr_count( $trip, '<li class="gallery-cell">' ) === 3 );
check( 'every link carries what the overlay reads: the large picture, its group, the caption - and the thumbnail its size and an alt text, in the language of the page',
	str_contains( $trip, 'href="'. $url( 'gallery/trip/0123456789abcdef.fit1600x1200.webp' ). '" data-lightbox="gallery-trip" data-caption="Der Pass"' ) === true
	&& str_contains( $trip, 'width="400" height="300" alt="The pass"' ) === true && str_contains( $trip, 'alt="The trip, picture 2"' ) === true
	&& str_contains( $trip, 'data-caption=""' ) === true );
check( 'the second gallery of the same album on a page is a group of its own', str_contains( galleryDraw( $site, '[gallery album="trip"][gallery album="trip"]' ), 'data-lightbox="gallery-trip-2"' ) === true
	&& str_contains( galleryDraw( $site, '[gallery album="trip"]' ), 'data-lightbox="gallery-trip-2"' ) === false );
check( 'a count of columns of the choices wins, anything else is the album\'s own; a variant is a class', str_contains( galleryDraw( $site, '[gallery album="trip" columns="5"]' ), '--gallery-columns:5' ) === true
	&& str_contains( galleryDraw( $site, '[gallery album="trip" columns="9"]' ), '--gallery-columns:3' ) === true && str_contains( galleryDraw( $site, '[gallery album="trip" variant="mat"]' ), 'class="gallery gallery--variant-mat"' ) === true
	&& str_contains( galleryDraw( $site, '[gallery album="trip" variant="loud"]' ), 'class="gallery"' ) === true );
check( 'no album named is the first one, one that is none the same, and an album with no picture draws nothing', str_contains( galleryDraw( $site, '[gallery]' ), 'gallery-trip' ) === true
	&& str_contains( galleryDraw( $site, '[gallery album="../x"]' ), 'gallery-trip' ) === true && str_contains( galleryDraw( $site, '[gallery album="wild\' onclick=\'x"]' ), 'onclick' ) === false );
$none = gallerySite( $root, [ [ 'key' => 'empty', 'name' => 'Nothing yet', 'images' => [] ] ] );
check( 'an album with no picture draws nothing, and takes no group', galleryDraw( $none, '[gallery album="empty"]' ) === '' && ( $none[\Nino\Modules\Gallery::GROUPS] ?? [] ) === [] );

// What is stored is text: whatever a picture's words or an album's name spell stays text where it is written
$wildAlbums = [
	[ 'key' => 'wild', 'name' => '<b onclick="x">[csrf]</b>', 'images' => [
		[ 'id' => '0123456789abcdef', 'thumb' => 'gallery/wild/0123456789abcdef.webp', 'large' => 'gallery/wild/0123456789abcdef.webp', 'alt' => '"><script>alert(1)</script> [template /templates/html-header]', 'caption' => '<img src=x onerror=alert(1)> [[content]] "q" [csrf]' ],
		[ 'id' => 'fedcba9876543210', 'thumb' => 'gallery/wild/fedcba9876543210.webp', 'large' => 'gallery/wild/fedcba9876543210.webp', 'alt' => '', 'caption' => '' ],
	] ],
];
$wildSite = gallerySite( $root, $wildAlbums );
$wild = galleryDraw( $wildSite, '[gallery album="wild"]' );
check( 'a caption, an alt text and the name of an album that spell markup, a call or a quote stay text, once escaped',
	str_contains( $wild, '<script' ) === false && str_contains( $wild, '<img src=x' ) === false && str_contains( $wild, '<b onclick' ) === false && str_contains( $wild, 'onerror=alert(1)&gt;' ) === true
	&& str_contains( $wild, 'alt="&quot;&gt;&lt;script&gt;' ) === true && str_contains( $wild, '&lt;b onclick=&quot;x&quot;&gt;[csrf]&lt;/b&gt;, picture 2' ) === true && substr_count( $wild, '[csrf]' ) === 2 && str_contains( $wild, '&amp;' ) === false
	&& str_contains( $wild, '[template /templates/html-header]' ) === true );
check( 'the demo of the group draws the grid where there is an album', str_contains( galleryDraw( $site, (string) ( \Nino\Components::groups( $site )['gallery']['demo'] ?? '' ) ), 'class="gallery' ) === true );

// The pictures a record names are the pictures of the folder of this feature, and nothing else is ever taken off the disk
check( 'a name that is absolute, climbs out, is empty or holds a control character or a backslash is no picture name', \Nino\Modules\Gallery::named( 'elements/a.webp' ) === true && \Nino\Modules\Gallery::named( 'gallery/trip/a.webp' ) === true
	&& array_filter( [ '../x', '/x', 'gallery/../elements/a.webp', "gallery/a\0.webp", 'gallery\\a.webp', '' ], static fn( string $name ): bool => \Nino\Modules\Gallery::named( $name ) === true ) === [] );
check( 'a deletion for an album reaches the files of that album\'s folder and nothing else', \Nino\Modules\Gallery::owned( 'gallery/trip/a.webp', 'trip' ) === true
	&& array_filter( [ [ 'gallery/other/a.webp', 'trip' ], [ 'elements/a.webp', 'trip' ], [ 'gallery/trip/../other/a.webp', 'trip' ], [ 'gallery/trip', 'trip' ], [ 'gallery/trip2/a.webp', 'trip' ], [ 'gallery/trip/a.webp', '../x' ], [ 'gallery/trip/a.webp', '' ] ], static fn( array $case ): bool => \Nino\Modules\Gallery::owned( ...$case ) === true ) === [] );
$keep = \Nino\Filesystem::path( $appData, '/images/elements/keep.webp' );
$other = \Nino\Filesystem::path( $appData, '/images/gallery/other/keep.webp' );
mkdir( dirname( $keep ), 0755, true );
mkdir( dirname( $other ), 0755, true );
file_put_contents( $keep, 'x' );
file_put_contents( $other, 'x' );
\Nino\Modules\Gallery::forget( $appData, [ 'thumb' => 'elements/keep.webp', 'large' => 'gallery/other/keep.webp' ], 'trip' );
\Nino\Modules\Gallery::forget( $appData, [ 'thumb' => '../keep.webp', 'large' => 'gallery/trip/../other/keep.webp' ], 'trip' );
check( 'a hand-edited record cannot take a file of another folder, or of another album, off the disk', is_file( $keep ) === true && is_file( $other ) === true );

// --- the panel: the right of every action -----------------------------------------------------------------------------------------

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $wildAlbums );
ninoUser( $appData, 'editor@example.org', [] );
$before = (string) file_get_contents( \Nino\Filesystem::path( $appData, \Nino\Modules\Gallery::ALBUMS ) );
$denied = [];
foreach( [ 'gallery/list' => [], 'gallery/album-save' => [ 'album' => 'wild', 'name' => 'x' ], 'gallery/album-delete' => [ 'album' => 'wild' ], 'gallery/image-save' => [ 'album' => 'wild', 'id' => '0123456789abcdef', 'locale' => 'de_DE', 'alt' => 'x' ],
	'gallery/image-delete' => [ 'album' => 'wild', 'id' => '0123456789abcdef' ], 'gallery/reorder' => [ 'album' => 'wild', 'order' => [ 'fedcba9876543210', '0123456789abcdef' ] ] ] as $action => $data )
	$denied[$action] = galleryAdmin( $appData, $action, $data )[0];
$uploaded = $gd === true ? galleryUpload( $appData, 'wild', galleryJpeg( 800, 600 ) ) : [ 403 ];
check( 'an account without the right may do none of the seven actions, and nothing is written or made', array_unique( array_values( $denied ) ) === [ 403 ] && $uploaded[0] === 403
	&& (string) file_get_contents( \Nino\Filesystem::path( $appData, \Nino\Modules\Gallery::ALBUMS ) ) === $before && galleryFiles( $appData ) === [ 'elements/keep.webp', 'gallery/other/keep.webp' ] );

ninoUser( $appData, 'admin@example.org', [ \Nino\Modules\Gallery\Admin::MANAGE_PERM ] );
$listed = galleryAdmin( $appData, 'gallery/list' );
check( 'with the right the list answers the albums with the url of each picture, the sizes of each album and the bounds the screen needs', $listed[0] === 200 && count( $listed[1]['albums'] ) === 1 && isset( $listed[1]['albums'][0]['images'][0]['thumbUrl'], $listed[1]['albums'][0]['settings']['columns'] ) === true
	&& $listed[1]['maxImages'] === \Nino\Modules\Gallery::MAX_IMAGES && isset( $listed[1]['ranges']['thumbWidth'], $listed[1]['native'] ) === true );

// What a request names is held to a pattern before anything is looked up, written or made
$refused = [];
foreach( [ 'Trip', '../trip', 'a/b', '', 'trip ', "trip\n", "tr\0ip", '9lives', [ 'trip' ], 'a--' . str_repeat( 'b', 80 ) ] as $key )
	$refused[] = galleryAdmin( $appData, 'gallery/album-save', [ 'album' => $key, 'name' => 'x', 'isNew' => true ] )[0];
foreach( [ 'ffffffffffffffff', '../x', '0123456789ABCDEF', '0123456789abcde', "0123456789abcdef\n", [ 'x' ] ] as $id )
	$refused[] = galleryAdmin( $appData, 'gallery/image-delete', [ 'album' => 'wild', 'id' => $id ] )[0];
check( 'an album key and an image id that do not fit their pattern are refused, and nothing is written', array_unique( $refused ) === [ 400 ] && (string) file_get_contents( \Nino\Filesystem::path( $appData, \Nino\Modules\Gallery::ALBUMS ) ) === $before );

// --- albums: made, held to their sizes and to the most there may be ---------------------------------------------------
$made = galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new-one', 'name' => '<i>Made</i>', 'isNew' => true ] );
$again = galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new-one', 'name' => 'Again', 'isNew' => true ] );
check( 'an album is made from a key and a name; the name is kept as the text it is; a key that is taken is refused', $made[0] === 200 && $again[0] === 400 && ( $again[1]['code'] ?? '' ) === 'gallery_taken'
	&& \Nino\Modules\Gallery::album( $appData, 'new-one' )['name'] === '<i>Made</i>' && \Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'new-one' ) )['thumbWidth'] === 500 );
$tooBig = galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new-one', 'settings' => [ 'thumbWidth' => 39, 'columns' => 9 ] ] );
$fine = galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'new-one', 'settings' => [ 'columns' => 5, 'keepRatio' => false ] ] );
check( 'the sizes are held to the bounds of the settings, all of them or none, and the answer names the first wrong field', $tooBig[0] === 400 && ( $tooBig[1]['code'] ?? '' ) === 'gallery_settings' && ( $tooBig[1]['field'] ?? '' ) === 'thumbWidth' && $fine[0] === 200
	&& \Nino\Modules\Gallery::settingsOf( $appData, \Nino\Modules\Gallery::album( $appData, 'new-one' ) )['columns'] === 5 );

$full = [];
for( $at = 0; $at < \Nino\Modules\Gallery::MAX_ALBUMS; $at++ )
	$full[] = [ 'key' => 'album-'. $at, 'name' => 'A', 'images' => [] ];
$filled = [ 'key' => 'filled', 'name' => 'Filled', 'images' => [] ];
for( $at = 0; $at < \Nino\Modules\Gallery::MAX_IMAGES; $at++ )
	$filled['images'][] = [ 'id' => sprintf( '%016x', $at ), 'thumb' => 'gallery/filled/t'. $at. '.webp', 'large' => 'gallery/filled/l'. $at. '.webp', 'alt' => '', 'caption' => '' ];
$full[0] = $filled;
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $full );
$more = galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'one-more', 'name' => 'x', 'isNew' => true ] );
check( 'there may be no more albums than the most, and a refused one is not made', $more[0] === 400 && ( $more[1]['code'] ?? '' ) === 'gallery_albums_full' && \Nino\Modules\Gallery::album( $appData, 'one-more' ) === null );
$filesBefore = galleryFiles( $appData );
$over = $gd === true ? galleryUpload( $appData, 'filled', galleryJpeg( 400, 300 ) ) : [ 400, [ 'code' => 'gallery_images_full' ] ];
check( 'an album that holds the most pictures takes no more, and nothing is made for the refused one', $over[0] === 400 && ( $over[1]['code'] ?? '' ) === 'gallery_images_full' && galleryFiles( $appData ) === $filesBefore );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$deleted = galleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'nothing' ] );
check( 'a delete of an album that is none is refused', $deleted[0] === 400 && ( $deleted[1]['code'] ?? '' ) === 'gallery_album' );

if( $gd === true ) {

	// --- an upload: what is made, what is not kept, what is refused -----------------------------------------------------------------
	galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'trip', 'name' => 'Trip', 'isNew' => true ] );
	$secret = 'SECRET-GPS-48.1371N';
	$before = galleryFiles( $appData );
	$bytes = galleryJpeg( 1200, 900, $secret );
	$one = galleryUpload( $appData, 'trip', $bytes, '../../public/evil.php.jpg' );
	$images = \Nino\Modules\Gallery::album( $appData, 'trip' )['images'] ?? [];
	$files = array_values( array_diff( galleryFiles( $appData ), $before ) );
	$stored = $files === [] ? '' : implode( '', array_map( static fn( string $file ): string => (string) file_get_contents( \Nino\Filesystem::path( $appData, '/images/'. $file ) ), $files ) );
	check( 'an upload becomes two pictures of the album and nothing else is kept: two files, no original, no exif, no name of the browser anywhere', $one[0] === 200 && count( $images ) === 1 && count( $files ) === 2
		&& str_contains( $stored, $secret ) === false && str_contains( $stored, 'Exif' ) === false && in_array( $bytes, array_map( static fn( string $file ): string => (string) file_get_contents( \Nino\Filesystem::path( $appData, '/images/'. $file ) ), $files ), true ) === false
		&& str_contains( implode( ' ', $files ), 'evil' ) === false );
	check( 'both names are of the shape the kernel makes below the folder of the album: a random id, the size, one extension - never a double one',
		count( array_filter( $files, static fn( string $file ): bool => preg_match( '~^gallery/trip/[a-f0-9]{16}\.(fit)?\d+x\d+\.(webp|png|jpg)$~D', $file ) === 1 ) ) === 2 );
	$size = getimagesize( \Nino\Filesystem::path( $appData, '/images/'. $images[0]['large'] ) );
	check( 'the large view is the whole picture in its proportions, not larger than the box, the thumbnail the album\'s size', is_array( $size ) === true && $size[0] <= 1800 && abs( $size[0] / $size[1] - 4 / 3 ) < 0.02
		&& ( getimagesize( \Nino\Filesystem::path( $appData, '/images/'. $images[0]['thumb'] ) )[0] ?? 0 ) === 500 );

	// Only what the kernel takes as a picture is taken: a script in an svg, a page in a picture's clothes, a text, nothing at all
	$svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="9" height="9"/></svg>';
	$refusals = [];
	foreach( [ [ $svg, 'x.svg' ], [ "GIF89a<?php system('id'); ?>", 'x.gif' ], [ 'plain text', 'x.jpg.txt' ], [ '', 'x.png' ] ] as [ $content, $name ] )
		$refusals[] = galleryUpload( $appData, 'trip', $content, $name )[0];
	$_FILES = [ 'file' => [ 'name' => [ 'a.jpg' ], 'tmp_name' => [ '/etc/passwd' ], 'error' => [ UPLOAD_ERR_OK ], 'size' => [ 5 ] ] ];
	$refusals[] = ninoCall( $appData, [ \Nino\Modules\Gallery\Admin::class, 'apiUpload' ], [ 'album' => 'trip' ], 'gallery/upload' )[0];
	$_FILES = [];
	$refusals[] = galleryUpload( $appData, 'nowhere', galleryJpeg( 100, 100 ) )[0];
	check( 'an svg with a script, a script in a gif, a text, an empty file, a file list that is no file and an album that is none are refused, and nothing is stored for them',
		array_unique( $refusals ) === [ 400 ] && count( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'] ) === 1 && array_diff( galleryFiles( $appData ), $before, $files ) === [] );

	// The words and the order of a picture, in one language each
	galleryUpload( $appData, 'trip', galleryJpeg( 300, 300 ) );
	galleryUpload( $appData, 'trip', galleryJpeg( 300, 200 ) );
	$ids = array_column( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'], 'id' );
	galleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'de_DE', 'caption' => 'Der Pass', 'alt' => str_repeat( 'ä', 400 ) ] );
	galleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'en_US', 'caption' => 'The pass' ] );
	$first = \Nino\Modules\Gallery::album( $appData, 'trip' )['images'][0];
	check( 'a caption and an alt text are kept for one language each, cut to the most, and a language the site does not have is refused',
		$first['caption'] === [ 'de_DE' => 'Der Pass', 'en_US' => 'The pass' ] && strlen( $first['alt']['de_DE'] ) <= \Nino\Modules\Gallery::MAX_CAPTION && mb_check_encoding( $first['alt']['de_DE'], 'UTF-8' ) === true
		&& galleryAdmin( $appData, 'gallery/image-save', [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'xx_XX', 'caption' => 'x' ] )[0] === 400 );
	$moved = galleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'trip', 'order' => [ $ids[2], $ids[0], $ids[1] ] ] );
	$partial = galleryAdmin( $appData, 'gallery/reorder', [ 'album' => 'trip', 'order' => [ $ids[0], $ids[1] ] ] );
	check( 'the order is kept as posted, and an order that names not every picture is refused whole', $moved[0] === 200 && array_column( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'], 'id' ) === [ $ids[2], $ids[0], $ids[1] ] && $partial[0] === 400 );
	$gone = \Nino\Modules\Gallery::album( $appData, 'trip' )['images'][0];
	$count = count( galleryFiles( $appData ) );
	galleryAdmin( $appData, 'gallery/image-delete', [ 'album' => 'trip', 'id' => $ids[2] ] );
	check( 'a picture deleted takes both of its files off the disk', count( galleryFiles( $appData ) ) === $count - 2 && is_file( \Nino\Filesystem::path( $appData, '/images/'. $gone['thumb'] ) ) === false );
	galleryAdmin( $appData, 'gallery/album-delete', [ 'album' => 'trip' ] );
	check( 'an album deleted takes every picture of it with it, and not the file of another folder', array_diff( galleryFiles( $appData ), [ 'elements/keep.webp', 'gallery/other/keep.webp' ] ) === [] );

	// The most is held a second time under the lock of the file: an album that is filled between the first look and the write takes no more, and what was made for the refused upload is taken off the disk again
	galleryAdmin( $appData, 'gallery/album-save', [ 'album' => 'race', 'name' => 'Race', 'isNew' => true ] );
	$armed = true;
	\Nino\Callbacks::registerCallback( $appData, \Nino\Images::RENDER, static function( array &$appData, array &$image ) use ( &$armed ): void {

		if( $armed === false )
			return;

		$armed = false;
		\Nino\Modules\Gallery::change( $appData, static function( array $albums ): array {

			foreach( $albums as $at => $album )
				if( $album['key'] === 'race' )
					for( $n = 0; $n < \Nino\Modules\Gallery::MAX_IMAGES; $n++ )
						$albums[$at]['images'][] = [ 'id' => sprintf( '%016x', $n ), 'thumb' => 'gallery/race/t'. $n. '.webp', 'large' => 'gallery/race/l'. $n. '.webp', 'alt' => '', 'caption' => '' ];

			return $albums;
		} );
	} );
	$filesBefore = galleryFiles( $appData );
	$raced = galleryUpload( $appData, 'race', galleryJpeg( 300, 300 ) );
	check( 'an album that is filled while a picture is made takes no more under the lock, and the two files made for it are taken off the disk', $armed === false && $raced[0] === 400 && ( $raced[1]['code'] ?? '' ) === 'gallery_images_full'
		&& count( \Nino\Modules\Gallery::album( $appData, 'race' )['images'] ) === \Nino\Modules\Gallery::MAX_IMAGES && galleryFiles( $appData ) === $filesBefore );
}

// --- the scripts and the words ----------------------------------------------------------------------------------------------------
$panel = (string) file_get_contents( $dir. '/assets/admin.js' );
check( 'the rail entry has a sign of its own: an svg that draws, no script in it', preg_match( '/^<svg [^>]*>(<(path|circle|rect) [^>]+\/>)+<\/svg>$/', \Nino\Modules\Gallery\Admin::icon() ) === 1 );
check( 'the panel script takes its buttons, dialogs, bars, cards, table and file field from the workbench and builds none of them',
	preg_match( '/createElement\(\s*.button|window\.confirm|\balert\(|\bprompt\(|nino-admin-btn-|type\s*[:=]\s*.file|<dialog|\.innerHTML\s*=\s*[^\'"\s]/', $panel ) === 0
	&& str_contains( $panel, 'Nino.adminUi.cards(' ) === true && str_contains( $panel, 'Nino.adminUi.fileField(' ) === true && str_contains( $panel, 'Nino.adminUi.table(' ) === true && str_contains( $panel, 'Nino.adminUi.formDialog(' ) === true );
check( 'every bar has exactly one main button, the form is the shape of the guide, success is a toast and the language is the workbench\'s',
	substr_count( $panel, 'actionBar( {' ) === 2 && substr_count( $panel, 'primary :' ) === 2 && str_contains( $panel, 'noValidate = true' ) === true && str_contains( $panel, "'fieldset'" ) === true && str_contains( $panel, 'nino-admin-fieldgrid' ) === true
	&& str_contains( $panel, 'Nino.adminUi.say(' ) === true && str_contains( $panel, 'dirty' ) === true && str_contains( $panel, 'Nino.admin.workLocale' ) === true && str_contains( $panel, 'localeSelect' ) === false && str_contains( $panel, 'selectedLocale' ) === false );
check( 'the page says text where it takes text: every string of a picture or an album goes in as text', str_contains( $panel, 'innerHTML' ) === true && preg_match( '/innerHTML\s*=\s*\'\'/', $panel ) === 1 && preg_match( '/innerHTML\s*+[+]?=\s*+(?!\'\')|outerHTML|insertAdjacentHTML|document\.write|\beval\(|new Function/', $panel ) === 0 );
$part = (string) file_get_contents( $dir. '/groups/gallery/part/part.css' );
check( 'a visitor who asked for less motion gets none of the grid\'s zoom and none of the overlay\'s spinner', preg_match( '/@media \(prefers-reduced-motion: reduce\)\s*\{[^@]*\.gallery-link:hover \.gallery-thumb \{ transform: none;[^@]*animation: none;/s', $part ) === 1 && substr_count( $part, 'var(--page-motion)' ) >= 2 );
$words = [ 'de_DE' => include $dir. '/text/de_DE.php', 'en_US' => include $dir. '/text/en_US.php' ];
check( 'a count of one picture has a sentence of its own in both languages, and the script chooses between the two', isset( $words['de_DE']['[[/_admin/gallery/msg/uploaded-one]]'], $words['en_US']['[[/_admin/gallery/msg/uploaded-one]]'] ) === true && str_contains( $panel, 'msg/uploaded-one' ) === true );
check( 'both languages of the panel have the same keys, and none is empty', array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) && array_filter( $words['de_DE'] + $words['en_US'], static fn( string $text ): bool => $text === '' ) === [] );
preg_match_all( "~'(/_admin/(?!common/)[a-z/_-]+)'~", $panel, $read );
$own = array_map( static fn( string $key ): string => trim( $key, '[]' ), array_keys( $words['en_US'] ) );
preg_match_all( "~'(gallery_[a-z_]+)'~", (string) file_get_contents( $dir. '/Admin/Admin.php' ), $answered );
check( 'every word the script reads is one the panel brings in both languages, and so is the sentence of every code the panel answers',
	array_diff( array_filter( array_unique( $read[1] ), static fn( string $key ): bool => str_ends_with( $key, '/' ) === false ), $own ) === [] && count( array_unique( $answered[1] ) ) >= 10 && array_diff( array_map( static fn( string $code ): string => '/_admin/error/'. $code, array_unique( $answered[1] ) ), $own ) === [] );
$install = [ include $dir. '/install/text/de_DE.php', include $dir. '/install/text/en_US.php' ];
check( 'the sentence for a picture with no words is in both languages of the unit', array_keys( $install[0] ) === array_keys( $install[1] ) && array_keys( $install[0] ) === [ '[[/feature/gallery/image/unnamed]]' ] );

$overlay = (string) file_get_contents( $dir. '/assets/gallery.js' );
check( 'the overlay writes what a page says as text: no markup is ever put together in it, and it opens nothing but a link that names an image', preg_match( '/innerHTML|outerHTML|insertAdjacentHTML|document\.write|\beval\(|new Function/', $overlay ) === 0
	&& str_contains( $overlay, 'isImage( link.getAttribute' ) === true && str_contains( $overlay, 'caption.textContent' ) === true );

// The kernel's own check and the other half of the test, in node
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: gallery-js-smoke.js was NOT run\n";
else {
	$output = [];
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/gallery-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'the overlay\'s own suite passes ('. trim( (string) end( $output ) ). ')', $exit === 0 );
}

check( 'the call draws nothing where the feature is off, and the albums stay', \Nino\Features::deactivate( $appData, 'gallery' ) === true && \Nino\Filesystem::fileExists( $appData, \Nino\Modules\Gallery::ALBUMS ) === true );
ninoWarnings();

// Nothing of the old components API, the old stylesheet vocabulary or the old ways of ending a request is left in the folder
$oldWorld = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|Modules\\\\Components|\bnino-(?!admin-)[a-z]|--(?:space|color|radius|fontsize)-|--text-\d|Nino\.ui\.|E_USER_ERROR|GLOB_BRACE/', (string) file_get_contents( (string) $file ) ) === 1 )
		$oldWorld[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $oldWorld === [] );

ninoDone( $appData );
