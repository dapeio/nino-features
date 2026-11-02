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
 *										of an upload and the limit each one names - each with
 *										its code and the sentence of that code in both
 *										languages -, the changes of the albums under the lock
 *										of their file, the component the Builder offers with
 *										the albums of the project as the choices of a select,
 *										left alone where a module of the project registered one
 *										of its own - or this feature's renderer under a schema
 *										of its own, with no album or an album that has a
 *										default - and the seam a richer uploader hooks into.
 *										What the panel's own script does with all of that is the
 *										browser's, and gallery-js-smoke.js beside this file
 *										measures it; this test runs that one too where node is
 *										on the path.
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

/** A png header and nothing else - width and height are all getimagesizefromstring() asks of it */
function galleryPng( int $width, int $height ): string {
	$ihdr = pack( 'NN', $width, $height ). "\x08\x02\x00\x00\x00";
	return "\x89PNG\r\n\x1a\n". pack( 'N', 13 ). 'IHDR'. $ihdr. pack( 'N', crc32( 'IHDR'. $ihdr ) );
}

/** The code a refusal carries, '' where the answer has none */
function galleryCode( ?array $body ): string {
	return (string) ( $body['code'] ?? '' );
}

/**
 *	The sentence the panel says for a code in one language, as its text directory
 *	has it - '' for a code without one
 *
 *	@param		array 		&$appData
 *	@param		string		$code
 *	@param		string		$locale
 *
 *	@return 	string
 */
function gallerySentence( array &$appData, string $code, string $locale ): string {
	return (string) ( \Nino\Admin\Admin::textFills( $appData, '/features/Gallery/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' );
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

/**
 *	HTML+ as a page of a request of its own: the groups an earlier render drew are forgotten first,
 *	because a site renders a page once per process. Every render of this test is one page - and
 *	where a pin is about two galleries on one page it renders both in one call
 *
 *	@param		array 		&$appData
 *	@param		string		$content
 *
 *	@return 	string
 */
function galleryPage( array &$appData, string $content ): string {
	unset( $appData[\Nino\Modules\Gallery::GROUPS] );
	return \Nino\Html::renderHtml( $appData, $content );
}

/** The lightbox groups of a page, with the count of links each one has - in the order the page draws them */
function galleryGroups( string $html ): array {
	preg_match_all( '/data-lightbox="([^"]*)"/', $html, $found );
	return array_count_values( $found[1] );
}

/**
 *	The rules of a stylesheet, its comments left out: for each the at-rule it stands in ('' for none), its selector with the
 *	whitespace folded and the text of its body. One level of at-rule is read, which is as deep as the stylesheets here go
 *
 *	@param		string		$css
 *
 *	@return 	array<int, array{at: string, selector: string, body: string}>
 */
function galleryRules( string $css ): array {

	$rules	= [];
	$read		= static function( string $text, string $at ) use ( &$read, &$rules ): void {

		$offset = 0;

		while( ( $open = strpos( $text, '{', $offset ) ) !== false ) {

			$depth	= 1;
			$close	= $open + 1;

			for( ; $close < strlen( $text ) && $depth > 0; $close++ )
				$depth += $text[$close] === '{' ? 1 : ( $text[$close] === '}' ? -1 : 0 );

			$selector	= trim( (string) preg_replace( '/\s+/', ' ', substr( $text, $offset, $open - $offset ) ) );
			$body			= substr( $text, $open + 1, $close - $open - 2 );

			if( str_starts_with( $selector, '@' ) === true )
				$read( $body, $selector );
			else
				$rules[] = [ 'at' => $at, 'selector' => $selector, 'body' => $body ];

			$offset = $close;
		}
	};

	$read( (string) preg_replace( '#/\*.*?\*/#s', '', $css ), '' );

	return $rules;
}

/**
 *	The declarations in the body of a rule, in the order they stand, the whitespace of each value folded
 *
 *	@param		string		$body
 *
 *	@return 	array<int, array{property: string, value: string}>
 */
function galleryDeclarations( string $body ): array {

	preg_match_all( '/(?:^|;)\s*(-{0,2}[a-z][a-z0-9-]*)\s*:\s*([^;]*)/i', $body, $found, PREG_SET_ORDER );

	return array_map( static fn( array $match ): array => [ 'property' => $match[1], 'value' => trim( (string) preg_replace( '/\s+/', ' ', $match[2] ) ) ], $found );
}

/**
 *	A stylesheet of the Design feature's grammar, read as it is written: the tags of its head (tag => the texts after it, one
 *	for each line), the rules after the head, and the property each declaration sets (property => [ selector, value ] for
 *	each). Nothing is compared with what a head says - that is the file's to say
 *
 *	@param		string		$file
 *
 *	@return 	array{tags: array<string, array<int, string>>, rules: array<int, array{at: string, selector: string, body: string}>, declared: array<string, array<int, array{selector: string, value: string}>>}
 */
function gallerySheet( string $file ): array {

	$css	= (string) file_get_contents( $file );
	$tags	= [];

	preg_match( '#^\s*/\*(.*?)\*/#s', $css, $comment );

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 )
			$tags[ $found[1] ][] = $found[2];

	$sheet = [ 'tags' => $tags, 'rules' => galleryRules( $css ), 'declared' => [] ];

	foreach( $sheet['rules'] as $rule )
		foreach( galleryDeclarations( $rule['body'] ) as $declaration )
			$sheet['declared'][ $declaration['property'] ][] = [ 'selector' => $rule['selector'], 'value' => $declaration['value'] ];

	return $sheet;
}

/**
 *	The colours a stylesheet writes as a literal in the value of a declaration: a hex colour, a colour function or the name of a
 *	colour. What a value reads of the kernel - var(--color-ink) - is the name of a property and none of these; transparent,
 *	currentColor and color-mix() of the kernel's tokens are none either
 *
 *	@param		string		$css
 *
 *	@return 	array<int, string>				property: literal, for each
 */
function galleryColourLiterals( string $css ): array {

	$names = explode( ' ', 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen' );
	$literal = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(|(?<![\w-])(?:'. implode( '|', $names ). ')(?![\w-])/i';
	$found = [];

	foreach( galleryRules( $css ) as $rule )
		foreach( galleryDeclarations( $rule['body'] ) as $declaration )
			if( preg_match( $literal, (string) preg_replace( '/--[a-z0-9-]+/i', '', $declaration['value'] ), $match ) === 1 )
				$found[] = $declaration['property']. ': '. $match[0];

	return $found;
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir 			= dirname( __DIR__ );
$manifest	= \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "gallery"', is_array( $manifest ) === true && $manifest['key'] === 'gallery' && ninoWarnings() === [] );
check( 'it is written for this kernel, and names the 1.8 whose vocabulary it uses', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true && $manifest['nino'] === '^1.8' );
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
check( 'the manual says what a key that no album has does, in both languages: the first album, where the Components module reads the albums as a list, a call the Builder does not keep, and nothing with the module switched off',
	str_contains( (string) ( $gone['en_US'] ?? '' ), 'draws the first album' ) === true && str_contains( (string) ( $gone['en_US'] ?? '' ), 'HTML+ block' ) === true
	&& str_contains( (string) ( $gone['en_US'] ?? '' ), 'switched off' ) === true && str_contains( (string) ( $gone['en_US'] ?? '' ), 'Nino 1.' ) === false
	&& str_contains( (string) ( $gone['de_DE'] ?? '' ), 'zeichnet das erste Album' ) === true && str_contains( (string) ( $gone['de_DE'] ?? '' ), 'HTML+-Block' ) === true
	&& str_contains( (string) ( $gone['de_DE'] ?? '' ), 'abgeschaltetem Modul' ) === true && str_contains( (string) ( $gone['de_DE'] ?? '' ), 'Nino 1.' ) === false );

// The manual says what the code does with a count it cannot draw, how a thumbnail is sized and named, and which words the unit brings
$columnsLine = (array) ( $manifest['manual']['shortcodes']['[gallery album="trip" columns="3"]'] ?? [] );
$thumbLine = (array) ( $manifest['manual']['markup']['<img width="500" height="500" alt="Trip, picture 3">'] ?? [] );
$unitLine = (array) ( $manifest['manual']['install']['text/<locale>.php'] ?? [] );
check( 'the manual says, in both languages, that a column count outside 1 to 8 is the album\'s, how a thumbnail keeps its room and is named, and what the unit brings',
	str_contains( (string) ( $columnsLine['en_US'] ?? '' ), '1 to 8' ) === true && str_contains( (string) ( $columnsLine['en_US'] ?? '' ), 'album\'s own' ) === true
	&& str_contains( (string) ( $columnsLine['de_DE'] ?? '' ), '1 bis 8' ) === true && str_contains( (string) ( $columnsLine['de_DE'] ?? '' ), 'des Albums' ) === true
	&& str_contains( (string) ( $thumbLine['en_US'] ?? '' ), '--nino-gallery-thumb-w' ) === true && str_contains( (string) ( $thumbLine['en_US'] ?? '' ), 'the album\'s name and the picture\'s place' ) === true
	&& str_contains( (string) ( $thumbLine['de_DE'] ?? '' ), '--nino-gallery-thumb-w' ) === true && str_contains( (string) ( $thumbLine['de_DE'] ?? '' ), 'der Name des Albums und die Stelle des Bildes' ) === true
	&& str_contains( (string) ( $unitLine['en_US'] ?? '' ), 'Text panel' ) === true && str_contains( (string) ( $unitLine['de_DE'] ?? '' ), 'Panel Texte' ) === true );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

// The requirement is what a requirement is: the Lightbox has to be in the
// directory, and switching this feature on switches that one on first
check( 'with the Lightbox in the directory there is nothing in the way', ( \Nino\Features::get( $appData, 'gallery' )['problems'] ?? null ) === [] );
check( 'activating it activates the Lightbox first - one click, two features on', \Nino\Features::activate( $appData, 'gallery' ) === true
	&& \Nino\Features::get( $appData, 'lightbox' )['active'] === true && \Nino\Features::get( $appData, 'gallery' )['active'] === true );
check( 'and the Lightbox cannot be switched off while this one is on', \Nino\Features::deactivate( $appData, 'lightbox' ) === 'feature "lightbox" is required by "gallery"' );

// init() on its own, on a copy, before the boot has put the manifests' files into the bundles: the sheet is not its to add, and
// Html::addAsset() takes a file once, so only a run without the boot's registration can tell
$alone = $appData;
\Nino\Modules\Gallery::init( $alone );

check( 'init() adds no stylesheet of its own - the grid\'s sheet is the manifest\'s, and nothing is in the style bundle until a boot has put it there',
	in_array( '/features/Gallery/assets/gallery.css', \Nino\Html::getAssets( $alone, '/.cache/style.css' ), true ) === false );

// What a boot does ahead of the init() of every module: the files the manifests name go into the bundles
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

check( 'init adds the [gallery] shortcode', isset( $appData['./nino/html/shortcodes']['gallery'] ) === true );
check( 'the manifest names the grid stylesheet, which a boot puts into the style bundle once',
	$manifest['assets'] === [ '/features/Gallery/assets/gallery.css' ] && array_keys( $manifest['assets'] ) === [ 0 ]
	&& array_count_values( \Nino\Html::getAssets( $appData, '/.cache/style.css' ) )['/features/Gallery/assets/gallery.css'] === 1 );
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

// The reasons store() cannot give - \Nino\Images only answers false - are the kernel's to say, in the codes its words exist for
check( 'the kernel says why it refuses bytes: too many of them', ( \Nino\Images::reject( str_repeat( 'a', \Nino\Images::MAX_UPLOAD_BYTES + 1 ) ) ?? [] ) === [ 'code' => 'image_too_large', 'params' => [ 8 ] ] );
check( '...not an image of a type it takes', ( \Nino\Images::reject( 'not an image' ) ?? [] ) === [ 'code' => 'image_type', 'params' => [] ] && ( \Nino\Images::reject( '' )['code'] ?? '' ) === 'image_type' );
check( '...too many pixels - a png header claiming 6000x4000 is enough, nothing is allocated for it', ( \Nino\Images::reject( galleryPng( 6000, 4000 ) ) ?? [] ) === [ 'code' => 'image_too_many_pixels', 'params' => [ 20 ] ]
	&& \Nino\Modules\Gallery::store( $appData, 'trip', galleryPng( 6000, 4000 ) ) === null );
check( '...and nothing where the bytes pass all three, so that something after them said no is this feature\'s to say', \Nino\Images::reject( $wide ) === null );
check( 'the kernel\'s byte cap is the one a real image gets through up to: that many bytes of it get through, one more does not', ( static function() use ( &$appData ): bool {
	$padded = galleryImage( 40, 40 );
	$padded .= str_repeat( "\0", \Nino\Images::MAX_UPLOAD_BYTES - strlen( $padded ) );
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

ninoUser( $appData, 'dev@example.com', [ \Nino\Modules\Gallery\Admin::MANAGE_PERM ] );
\Nino\Admin\Admin::init( $appData );
$admin = [ \Nino\Admin\Admin::class, 'handlePost' ];

[ $status, $body ] = ninoCall( $appData, $admin, [], 'gallery/list' );
check( 'gallery/list answers no album yet, and what a new album starts with - the settings of the feature - with the range each number may take', $status === 200 && galleryAlbums( $body ) === []
	&& ( $body['defaults'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ]
	&& ( $body['ranges'] ?? null ) === \Nino\Modules\Gallery::ranges( $appData ) && ( $body['ranges']['columns'] ?? null ) === [ 1, 8 ] );
check( 'it carries the languages a text can be written in, the site\'s own, and the one the screen opens in', ( $body['locales'] ?? null ) === [ 'de_DE', 'en_US' ]
	&& ( $body['native'] ?? null ) === 'de_DE' && ( $body['selectedLocale'] ?? null ) === 'de_DE' );
check( 'and how long the key of a new album may be, which is what the field that asks for it is limited by', ( $body['maxKey'] ?? null ) === \Nino\Modules\Gallery::MAX_KEY && \Nino\Modules\Gallery::MAX_KEY === 64 );
check( 'and what php takes in one upload, as a number and in words - the smaller of upload_max_filesize and post_max_size, which is the kernel\'s to say', ( $body['limits']['bytes'] ?? null ) === \Nino\Images::limits()['php']
	&& ( $body['limits']['text'] ?? null ) === \Nino\Modules\Gallery::formatBytes( \Nino\Images::limits()['php'] ) && \Nino\Images::limits()['php'] > 0
	&& \Nino\Images::limits()['php'] <= min( array_filter( [ ini_parse_quantity( (string) ini_get( 'upload_max_filesize' ) ), ini_parse_quantity( (string) ini_get( 'post_max_size' ) ) ] ) ) );

[ $status ] = ninoCall( $appData, $admin, [ 'album' => 'Not A Key', 'name' => 'x' ], 'gallery/album-save' );
check( 'an album key that is not a slug is refused', $status === 400 );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'name' => 'The trip' ], 'gallery/album-save' );
check( 'an album is created', $status === 200 && array_column( galleryAlbums( $body ), 'key' ) === [ 'trip' ] && ( galleryAlbums( $body )[0]['name'] ?? null ) === 'The trip' );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'name' => 'Renamed' ], 'gallery/album-save' );
check( 'saving it again renames rather than duplicates', $status === 200 && count( galleryAlbums( $body ) ) === 1 && ( galleryAlbums( $body )[0]['name'] ?? null ) === 'Renamed' );

ninoWarnings();
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'listy', 'name' => [ 'A', 'B' ] ], 'gallery/album-save' );
check( 'a name that arrives as a list is no name: the album is made and called by its key, and nothing is raised for turning a list into a string',
	$status === 200 && ( galleryAlbumOf( $body, 'listy' )['name'] ?? null ) === 'listy' && ninoWarnings() === [] );
ninoCall( $appData, $admin, [ 'album' => 'listy' ], 'gallery/album-delete' );

$uploaded = [];
foreach( [ 'a', 'b', 'c' ] as $ignored ) {
	[ $status, $body ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => 'trip' ], 'gallery/upload' ) );
	$uploaded[] = $status;
}
check( 'three uploads land in the album, in the order they arrived', $uploaded === [ 200, 200, 200 ] && count( galleryImages( $body ) ) === 3 );
check( 'each image comes back with the urls the panel shows it at', str_contains( (string) ( galleryImages( $body )[0]['thumbUrl'] ?? '' ), '/images/gallery/trip/' ) === true
	&& str_contains( (string) ( galleryImages( $body )[0]['largeUrl'] ?? '' ), '/images/gallery/trip/' ) === true );

$ids = array_pad( array_column( galleryImages( $body ), 'id' ), 3, '' );

// An upload that is refused says why, with the limit it ran into - not
// 'That image is not in this album.', which is what all of them said. The sandbox has no text files, so the panel's own are what the fills say
$panelText = include dirname( __DIR__ ). '/text/en_US.php';
$german = include dirname( __DIR__ ). '/text/de_DE.php';
\Nino\Html::addFills( $appData, $panelText, '*' );
$refuse = static function( string $bytes, int $error = UPLOAD_ERR_OK ) use ( &$appData, $admin ): array {
	return withUpload( $bytes, static function() use ( &$appData, $admin, $error ): array {
		$_FILES['file']['error'] = $error;
		return ninoCall( $appData, $admin, [ 'album' => 'trip' ], 'gallery/upload' );
	} );
};
$limitText = \Nino\Modules\Gallery::formatBytes( \Nino\Images::limits()['php'] );

check( 'the panel\'s texts are there in both languages, every key of one in the other', array_keys( $panelText ) === array_keys( $german ) );

// How many pictures an album holds is said in two words, the singular and the plural, and the script picks by the number (the JavaScript test
// holds it to that). The words are the project's to change, so what is held here is their shape: both there in both languages, one place for the
// number each, two different words in each language, and the question before an album is deleted takes the count as a phrase of its own
check( 'the count of pictures is two words in each language, the singular and the plural, each with one place for the number - and the question before a delete has one for the name and one for the phrase',
	( static function() use ( $panelText, $german ): bool {
		foreach( [ $panelText, $german ] as $words ) {
			foreach( [ '[[/_admin/gallery/label/count-one]]', '[[/_admin/gallery/label/count]]' ] as $key )
				if( is_string( $words[$key] ?? null ) === false || substr_count( $words[$key], '%s' ) !== 1 )
					return false;
			if( $words['[[/_admin/gallery/label/count-one]]'] === $words['[[/_admin/gallery/label/count]]'] || substr_count( (string) ( $words['[[/_admin/gallery/confirm/album]]'] ?? '' ), '%s' ) !== 2 )
				return false;
		}
		return true;
	} )() );

// A word the script or the class asks for that a language lacks is a screen with a key on it. Keys put together
// from a part and a name - the label of an alt text and a caption - end in a slash and are not read here, and
// the permission of the panel is named the same way and is no word
$asked = [];
foreach( [ '/assets/admin.js', '/Admin/Admin.php' ] as $source )
	if( preg_match_all( '#[\'"](/_admin/gallery/[a-z0-9/-]+)[\'"]#', (string) file_get_contents( dirname( __DIR__ ). $source ), $found ) > 0 )
		$asked = array_merge( $asked, $found[1] );
$lacking = array_values( array_filter( array_unique( $asked ), static fn( string $key ): bool => str_ends_with( $key, '/' ) === false && $key !== \Nino\Modules\Gallery\Admin::MANAGE_PERM
	&& ( isset( $panelText['[['. $key. ']]'] ) === false || isset( $german['[['. $key. ']]'] ) === false ) ) );
check( 'every word of the panel that its script and its class ask for is there, in both languages'. ( $lacking === [] ? '' : ' - '. implode( ' | ', $lacking ) ), $lacking === [] && count( $asked ) > 25 );

/*	The refusals a person can cause carry a code, and the sentence of each is a text of the panel in both
	languages, with a place for each value the code brings. The codes the class answers are these and no
	others, and the ones it takes from the kernel (the reasons an image is refused) are the kernel's	*/
$codes = [ 'gallery_key' => 0, 'gallery_reserved' => 0, 'gallery_long' => 1, 'gallery_taken' => 0, 'gallery_settings' => 1, 'gallery_album' => 0, 'gallery_image' => 0,
	'gallery_order' => 0, 'gallery_locale' => 0, 'gallery_upload' => 0, 'gallery_size' => 1, 'gallery_process' => 0 ];
preg_match_all( '#\'(gallery_[a-z_]+)\'#', (string) file_get_contents( dirname( __DIR__ ). '/Admin/Admin.php' ), $found );
check( 'the panel answers the codes it has words for, and no other', array_values( array_unique( $found[1] ) ) !== [] && array_diff( array_unique( $found[1] ), array_keys( $codes ) ) === []
	&& array_diff( array_keys( $codes ), array_unique( $found[1] ) ) === [] );

foreach( $codes as $code => $places )
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$sentence = gallerySentence( $appData, $code, $locale );
		check( "the code $code has its sentence in $locale, with $places place". ( $places === 1 ? '' : 's' ). ' for a value', $sentence !== '' && substr_count( $sentence, '%s' ) === $places );
	}

foreach( [ 'image_too_large' => 1, 'image_type' => 0, 'image_too_many_pixels' => 1 ] as $code => $places )
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$sentence = (string) ( ( include $root. '/_admin/text/'. $locale. '.php' )['[[/_admin/error/'. $code. ']]'] ?? '' );
		check( "...and the kernel's code $code, which the panel passes on as it is, has its sentence in $locale", $sentence !== '' && substr_count( $sentence, '%s' ) === $places );
	}

[ $status, $body ] = $refuse( $wide, UPLOAD_ERR_INI_SIZE );
check( 'a file over upload_max_filesize is a 400 that names the limit php has: the code, the limit as its value, and the English sentence for a log',
	$status === 400 && $body === [ 'error' => 'the file is larger than php takes in one upload ('. $limitText. ')', 'code' => 'gallery_size', 'params' => [ $limitText ] ] );
[ $status, $body ] = $refuse( $wide, UPLOAD_ERR_FORM_SIZE );
check( '...so is one over the form\'s own', $status === 400 && galleryCode( $body ) === 'gallery_size' && ( $body['params'] ?? null ) === [ $limitText ] );
[ $status, $body ] = $refuse( $wide, UPLOAD_ERR_PARTIAL );
check( 'any other upload error stays "the file could not be read"', $status === 400 && $body === [ 'error' => 'the file could not be read', 'code' => 'gallery_upload' ] );

[ $status, $body ] = $refuse( str_repeat( 'a', \Nino\Images::MAX_UPLOAD_BYTES + 1 ) );
check( 'more than the kernel\'s 8 MiB is refused with the kernel\'s code and that number', $status === 400 && galleryCode( $body ) === 'image_too_large' && ( $body['params'] ?? null ) === [ 8 ] );

[ $status, $body ] = $refuse( 'not an image' );
check( 'bytes that are not an image are refused as the wrong type, with the kernel\'s code', $status === 400 && galleryCode( $body ) === 'image_type' && ( $body['params'] ?? null ) === null );

[ $status, $body ] = $refuse( galleryPng( 6000, 4000 ) );
check( 'a picture of 24 megapixels is refused with the 20 the kernel takes, in the kernel\'s code', $status === 400 && galleryCode( $body ) === 'image_too_many_pixels' && ( $body['params'] ?? null ) === [ 20 ] );

\Nino\Callbacks::registerCallback( $appData, \Nino\Images::RENDER, static function( array &$appData, array &$img ): void {
	$img['filename'] = false;
} );
[ $status, $body ] = $refuse( $wide );
unset( $appData['./nino/callbacks'][ \Nino\Images::RENDER ] );
check( 'an image that passes the checks and is still not made says so, and is none of the above', $status === 400 && $body === [ 'error' => 'the server could not make the two sizes from this image', 'code' => 'gallery_process' ] );
check( 'none of the refusals stored anything', count( galleryImages( ninoCall( $appData, $admin, [], 'gallery/list' )[1] ) ) === 3 );

/*	The two sizes are made before the lock of the albums is taken, and the entry is added under it: an album that is gone by
	then, or a write that fails, leaves two files nothing points at, and they go again. The render callback runs between the
	one and the other, so it is the second request that deletes the album in the meantime	*/
$tripFiles = static fn(): array => glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [];
$filesBefore = $tripFiles();
$albumsBefore = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );

ninoCall( $appData, $admin, [ 'album' => 'doomed', 'name' => 'Doomed' ], 'gallery/album-save' );
$deleted = false;
\Nino\Callbacks::registerCallback( $appData, \Nino\Images::RENDER, static function( array &$appData, array &$img ) use ( &$deleted ): void {
	if( $deleted === true )
		return;
	$deleted = true;
	\Nino\Modules\Gallery::change( $appData, static fn( array $albums ): array => array_values( array_filter( $albums, static fn( array $album ): bool => $album['key'] !== 'doomed' ) ) );
} );
[ $status, $body ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => 'doomed' ], 'gallery/upload' ) );
unset( $appData['./nino/callbacks'][ \Nino\Images::RENDER ] );
check( 'an album deleted while its upload was being made is refused as that, with the code of the album and the English sentence for a log',
	$deleted === true && $status === 400 && $body === [ 'error' => 'there is no album with this key', 'code' => 'gallery_album' ] );
check( '...and the two files it had made are gone again, the albums being as the other request left them',
	( glob( \Nino\Filesystem::path( $appData, '/images/gallery/doomed' ). '/*' ) ?: [] ) === [] && \Nino\Modules\Gallery::album( $appData, 'doomed' ) === null
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $albumsBefore );

/*	A lock that cannot be taken: the lock file of the albums is a directory. The files of the images have locks of their own,
	so the two sizes are made, and the write is the one that fails - a 500 with the English sentence, no code, and no file left	*/
$lockPath = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( \Nino\Modules\Gallery::ALBUMS ). '.lock';
@unlink( $lockPath );
mkdir( $lockPath, 0755, true );
ninoWarnings();
[ $status, $body ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => 'trip' ], 'gallery/upload' ) );
$lockWarnings = ninoWarnings();
rmdir( $lockPath );
check( 'an upload whose entry could not be written is a 500 with the English sentence and no code', $status === 500 && $body === [ 'error' => 'the albums could not be written' ] );
check( '...and the answer is the whole of it: the lock that could not be taken raises no warning of its own', $lockWarnings === [] );
check( '...and the two files it had made are gone again, the albums as they were', $tripFiles() === $filesBefore
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $albumsBefore );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Above the pass' ], 'gallery/image-save' );
check( 'a caption is saved on the image it names, in the language it was written in', $status === 200 && ( galleryImages( $body )[1]['caption'] ?? null ) === [ 'en_US' => 'Above the pass' ]
	&& ( galleryImages( $body )[1]['alt'] ?? null ) === '' && ( galleryImages( $body )[0]['caption'] ?? null ) === '' );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'de_DE', 'caption' => 'Über dem Pass', 'alt' => 'Der Pass im Sommer' ], 'gallery/image-save' );
check( 'each language has its own, and an alt text is saved beside a caption', $status === 200
	&& ( galleryImages( $body )[1]['caption'] ?? null ) === [ 'en_US' => 'Above the pass', 'de_DE' => 'Über dem Pass' ]
	&& ( galleryImages( $body )[1]['alt'] ?? null ) === [ 'de_DE' => 'Der Pass im Sommer' ] );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'alt' => 'The pass in summer' ], 'gallery/image-save' );
check( 'a text that is not posted is left as it is', $status === 200 && ( galleryImages( $body )[1]['alt'] ?? null ) === [ 'de_DE' => 'Der Pass im Sommer', 'en_US' => 'The pass in summer' ]
	&& ( galleryImages( $body )[1]['caption'] ?? null ) === [ 'en_US' => 'Above the pass', 'de_DE' => 'Über dem Pass' ] );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'alt' => '' ], 'gallery/image-save' );
check( 'an empty text takes that language away - the others stay', $status === 200 && ( galleryImages( $body )[1]['alt'] ?? null ) === [ 'de_DE' => 'Der Pass im Sommer' ] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'de_DE', 'alt' => '' ], 'gallery/image-save' );
check( '...and the last one leaves an empty string, not an empty map', $status === 200 && ( galleryImages( $body )[1]['alt'] ?? null ) === '' );

// What an earlier version of this feature wrote: one string, for every language
$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$stored[0]['images'][2]['caption'] = 'Vom Pass';
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[2], 'locale' => 'en_US', 'caption' => 'From the pass' ], 'gallery/image-save' );
check( 'a caption that was one string for every language is every language\'s until the first is written - then each keeps it', $status === 200
	&& ( galleryImages( $body )[2]['caption'] ?? null ) === [ 'de_DE' => 'Vom Pass', 'en_US' => 'From the pass' ] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[2], 'locale' => 'en_US', 'caption' => '' ], 'gallery/image-save' );
check( '...and an empty one takes only its own language off', ( galleryImages( $body )[2]['caption'] ?? null ) === [ 'de_DE' => 'Vom Pass' ] );

// What a request can get wrong, none of it a write
$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'xx_XX', 'caption' => 'x' ], 'gallery/image-save' );
check( 'a language the project does not have is refused - and as that, not as a missing image: the code and the English sentence for a log',
	$status === 400 && $body === [ 'error' => 'that language is not available', 'code' => 'gallery_locale' ] );
check( '...so is none at all', ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'caption' => 'x' ], 'gallery/image-save' )[0] === 400 );
ninoWarnings();
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => [ 'en_US' ], 'caption' => 'x' ], 'gallery/image-save' );
check( '...and so is a list where a language is meant: refused with the code of a language, not a 500 for turning a list into a string', $status === 400 && galleryCode( $body ) === 'gallery_locale' && ninoWarnings() === [] );
check( '...and an image the album does not have, or an album nobody has, each as what it is',
	galleryCode( ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => 'ffffffffffffffff', 'locale' => 'en_US', 'caption' => 'x' ], 'gallery/image-save' )[1] ) === 'gallery_image'
	&& galleryCode( ninoCall( $appData, $admin, [ 'album' => 'nowhere', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'x' ], 'gallery/image-save' )[1] ) === 'gallery_album' );
check( 'a text that is not a string is not one', ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => [ 'x' ], 'alt' => 5 ], 'gallery/image-save' )[0] === 200 );
check( 'none of those wrote anything', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

/*	A list that cannot be locked was never looked at: the album is not missing and
	neither is the image - the albums could not be written, which is what is said:
	a 500 with the English sentence of the server and no code, since nobody caused it	*/
$locks = \Nino\Filesystem::path( $appData, '/data/.locks' );
$locksAside = $locks. '-aside';
$hadLocks = file_exists( $locks );
if( $hadLocks === true )
	rename( $locks, $locksAside );
file_put_contents( $locks, 'not a directory' );
ninoWarnings();
[ $lockedStatus, $lockedBody ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Locked out' ], 'gallery/image-save' );
ninoWarnings();
unlink( $locks );
if( $hadLocks === true )
	rename( $locksAside, $locks );
check( 'a list that cannot be locked answers that the albums could not be written - not that the album or the image is missing',
	$lockedStatus === 500 && $lockedBody === [ 'error' => 'the albums could not be written' ] );
check( '...and wrote nothing', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Above the pass' ], 'gallery/image-save' );

// The cap is in bytes, and a cut through the middle of a multibyte character
// leaves a byte sequence that is not utf-8 - which json_encode() answers with
// false, so the panel's whole answer came back empty and every screen of it
// stopped working until somebody found the caption by hand
$longCaption = str_repeat( 'a', \Nino\Modules\Gallery::MAX_CAPTION - 1 ). 'ä und weiter';
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => $longCaption, 'alt' => $longCaption ], 'gallery/image-save' );
check( 'a caption or an alt text longer than the cap is cut on a character boundary, and the panel still answers', $status === 200
	&& ( galleryImages( $body )[1]['caption']['en_US'] ?? null ) === str_repeat( 'a', \Nino\Modules\Gallery::MAX_CAPTION - 1 )
	&& ( galleryImages( $body )[1]['alt']['en_US'] ?? null ) === str_repeat( 'a', \Nino\Modules\Gallery::MAX_CAPTION - 1 )
	&& json_encode( $body ) !== false );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'en_US', 'caption' => 'Above the pass', 'alt' => '' ], 'gallery/image-save' );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'order' => [ $ids[2], $ids[0], $ids[1] ] ], 'gallery/reorder' );
check( 'the order is the whole list, posted and stored', $status === 200 && array_column( galleryImages( $body ), 'id' ) === [ $ids[2], $ids[0], $ids[1] ] );

[ $status ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'order' => [ $ids[0], $ids[1] ] ], 'gallery/reorder' );
check( 'an order that leaves an image out is refused whole - a partial order would drop it silently', $status === 400
	&& count( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'] ?? [] ) === 3 );

$goneFiles = array_map( static fn( string $key ): string => \Nino\Filesystem::path( $appData, '/images/'. \Nino\Modules\Gallery::album( $appData, 'trip' )['images'][0][$key] ?? '' ), [ 'thumb', 'large' ] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[2] ], 'gallery/image-delete' );
check( 'deleting an image takes both of its files with it', $status === 200 && count( galleryImages( $body ) ) === 2
	&& is_file( $goneFiles[0] ) === false && is_file( $goneFiles[1] ) === false );

[ $status ] = ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[2] ], 'gallery/image-delete' );
check( 'deleting it twice is a refusal, not a second delete', $status === 400 );

echo "\n";


// --- An album made on a page of its own -------------------------------------

echo "Gallery\\Admin - an album with sizes of its own\n";

$sizes = [ 'thumbWidth' => 80, 'thumbHeight' => 60, 'largeWidth' => 300, 'largeHeight' => 200, 'keepRatio' => true, 'columns' => 2 ];
$storedAlbum = static fn( string $key ): array => array_values( array_filter( (array) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ), static fn( mixed $entry ): bool => is_array( $entry ) === true && ( $entry['key'] ?? null ) === $key ) )[0] ?? [];

[ $status, $body ] = ninoCall( $appData, $admin, [], 'gallery/list' );
check( 'an album made the old way, with a key and a name, starts with the feature\'s settings - stored as its own six, so the feature changing them later does not change it',
	$status === 200 && ( galleryAlbumOf( $body, 'trip' )['settings'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ]
	&& ( $storedAlbum( 'trip' )['settings'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'name' => 'Sized', 'settings' => $sizes, 'isNew' => true ], 'gallery/album-save' );
check( 'the page of a new album makes one with album-save: the key, the name, the six sizes and the word that it is new - and the answer carries the sizes of every album',
	$status === 200 && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Sized' && ( galleryAlbumOf( $body, 'sized' )['settings'] ?? null ) === $sizes
	&& is_array( galleryAlbumOf( $body, 'trip' )['settings'] ?? null ) === true && count( galleryAlbumOf( $body, 'trip' )['settings'] ) === 6 );
check( '...and the record in the file holds them: the six, with their own values', ( $storedAlbum( 'sized' )['settings'] ?? null ) === $sizes && ( $storedAlbum( 'sized' )['images'] ?? null ) === [] );

$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'name' => 'Again', 'settings' => [ 'columns' => 8 ] + $sizes, 'isNew' => true ], 'gallery/album-save' );
check( 'a key that is taken is refused for a new album, with its code - and neither the name nor the sizes of the album that has it are touched',
	$status === 400 && $body === [ 'error' => 'there is an album with this key already', 'code' => 'gallery_taken' ] && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );
check( 'a key that is not a slug is refused as one, with or without sizes', galleryCode( ninoCall( $appData, $admin, [ 'album' => 'Sized!', 'name' => 'x', 'settings' => $sizes, 'isNew' => true ], 'gallery/album-save' )[1] ) === 'gallery_key'
	&& ninoCall( $appData, $admin, [ 'album' => '', 'settings' => $sizes, 'isNew' => true ], 'gallery/album-save' )[1] === [ 'error' => 'an album key is a slug: lower case, starting with a letter', 'code' => 'gallery_key' ] );

// The page that makes an album is #gallery/new: an album with that key would be opened in its place by a reload
$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'new', 'name' => 'New', 'settings' => $sizes, 'isNew' => true ], 'gallery/album-save' );
[ $statusPlain, $bodyPlain ] = ninoCall( $appData, $admin, [ 'album' => 'new', 'name' => 'New' ], 'gallery/album-save' );
check( 'the key "new" is the address of the page that makes an album, so no album is made with it - with the word that it is new or without it - and the refusal says why, as a code of its own and not as a key that is no slug; nothing is written',
	$status === 400 && $statusPlain === 400 && galleryCode( $body ) === 'gallery_reserved' && $bodyPlain === $body
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );
check( '...the panel has the words for it in both languages, naming the key', str_contains( gallerySentence( $appData, 'gallery_reserved', 'en_US' ), '"new"' ) === true
	&& str_contains( gallerySentence( $appData, 'gallery_reserved', 'de_DE' ), '„new“' ) === true );

// An album that has the key already - the file may be one from before the panel refused it, or edited by hand - goes on working
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( $before, [ [ 'key' => 'new', 'name' => 'Made before', 'images' => [] ] ] ) );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'new', 'name' => 'Still here', 'settings' => [ 'columns' => 3 ] ], 'gallery/album-save' );
[ $statusTaken, $bodyTaken ] = ninoCall( $appData, $admin, [ 'album' => 'new', 'name' => 'Again', 'isNew' => true ], 'gallery/album-save' );
check( 'an album that has the key already keeps working: it is renamed and its sizes are saved - and as a new album the key is taken, as for any other',
	$status === 200 && ( galleryAlbumOf( $body, 'new' )['name'] ?? null ) === 'Still here' && ( galleryAlbumOf( $body, 'new' )['settings']['columns'] ?? null ) === 3
	&& $statusTaken === 400 && galleryCode( $bodyTaken ) === 'gallery_taken' );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $before );

// A key names a directory below /images/gallery, and one the file system cannot take makes every upload into the album fail
$longest = 'k'. str_repeat( 'x', \Nino\Modules\Gallery::MAX_KEY - 1 );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => $longest. 'x', 'name' => 'Long', 'settings' => $sizes, 'isNew' => true ], 'gallery/album-save' );
[ $statusPlain, $bodyPlain ] = ninoCall( $appData, $admin, [ 'album' => $longest. 'x', 'name' => 'Long' ], 'gallery/album-save' );
check( 'a key of more than 64 characters is not the key of a new album - with the word that it is new or without it - and the refusal says how long one may be, as a value of its code; nothing is written',
	$status === 400 && $statusPlain === 400 && $body === [ 'error' => 'the key of a new album has at most 64 characters', 'code' => 'gallery_long', 'params' => [ \Nino\Modules\Gallery::MAX_KEY ] ] && $bodyPlain === $body
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => $longest, 'name' => 'Longest', 'settings' => $sizes, 'isNew' => true ], 'gallery/album-save' );
[ $statusUpload, $bodyUpload ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => $longest ], 'gallery/upload' ) );
check( '...64 are the longest: such a key makes an album, and an upload into it is made',
	$status === 200 && ( galleryAlbumOf( $body, $longest )['name'] ?? null ) === 'Longest' && $statusUpload === 200 && count( galleryAlbumOf( $bodyUpload, $longest )['images'] ?? [] ) === 1
	&& count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/'. $longest ). '/*' ) ?: [] ) === 2 );
ninoCall( $appData, $admin, [ 'album' => $longest ], 'gallery/album-delete' );

// An album that has a longer key from before - a file edited by hand, or written by the panel as it was - goes on working: only a new one is held to the number
$older = 'k'. str_repeat( 'x', 99 );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( $before, [ [ 'key' => $older, 'name' => 'Made before', 'images' => [] ] ] ) );
[ $statusRename, $bodyRename ] = ninoCall( $appData, $admin, [ 'album' => $older, 'name' => 'Still here', 'settings' => [ 'columns' => 3 ] ], 'gallery/album-save' );
[ $statusDelete ] = ninoCall( $appData, $admin, [ 'album' => $older ], 'gallery/album-delete' );
check( 'an album that has a longer key from before is renamed, has its sizes saved and is deleted all the same',
	$statusRename === 200 && ( galleryAlbumOf( $bodyRename, $older )['name'] ?? null ) === 'Still here' && ( galleryAlbumOf( $bodyRename, $older )['settings']['columns'] ?? null ) === 3 && $statusDelete === 200
	&& array_column( \Nino\Modules\Gallery::albums( $appData ), 'key' ) === array_column( $before, 'key' ) );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $before );

$refused = [];
foreach( [ 'thumbWidth' => [ 39, 1201, 'wide', 100.5, [ 1 ], null ], 'thumbHeight' => [ 39, 1201 ], 'largeWidth' => [ 199, 4001 ], 'largeHeight' => [ 199, 4001 ], 'columns' => [ 0, 9 ], 'keepRatio' => [ 'yes', 2, [] ] ] as $name => $values )
	foreach( $values as $value )
		if( ninoCall( $appData, $admin, [ 'album' => 'sized', 'settings' => [ $name => $value ] ], 'gallery/album-save' )[0] !== 400 )
			$refused[] = $name. '='. json_encode( $value );
check( 'a size outside the range of the feature\'s setting, or one that is no number, is refused: below it and above it, for each of the six'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ), $refused === [] );
check( '...and nothing was written by any of the refusals', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'name' => 'Not written', 'settings' => [ 'thumbWidth' => 39, 'columns' => 9, 'keepRatio' => true, 'largeWidth' => 300 ] ], 'gallery/album-save' );
check( 'the refusal names what is wrong - as the panel names it, with the range - and not what is right, and is all or nothing: the name that came with it was not kept either',
	$status === 400 && $body === [ 'error' => 'these sizes are outside what is allowed: thumbWidth, columns', 'code' => 'gallery_settings', 'params' => [ 'Thumbnail width (40–1200), Columns (1–8)' ] ]
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ) === $before );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'settings' => [ 'columns' => 5 ] ], 'gallery/album-save' );
check( 'sizes posted in part change those and leave the rest - the name is not in the request and is not touched either',
	$status === 200 && ( galleryAlbumOf( $body, 'sized' )['settings'] ?? null ) === array_replace( $sizes, [ 'columns' => 5 ] ) && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Sized' );

[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'settings' => [ 'thumbWidth' => '90', 'columns' => 8 ] ], 'gallery/album-save' );
check( 'a number written as digits is stored as a number, and 8 is the last column there is', $status === 200 && ( $storedAlbum( 'sized' )['settings']['thumbWidth'] ?? null ) === 90 && ( $storedAlbum( 'sized' )['settings']['columns'] ?? null ) === 8 );

ninoCall( $appData, $admin, [ 'album' => 'sized', 'settings' => $sizes ], 'gallery/album-save' );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'name' => 'Renamed' ], 'gallery/album-save' );
check( 'a rename is the name alone: the sizes of the album are the ones it had', $status === 200 && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Renamed' && ( galleryAlbumOf( $body, 'sized' )['settings'] ?? null ) === $sizes );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'sized', 'name' => '', 'settings' => $sizes ], 'gallery/album-save' );
check( '...and a name that is empty leaves the name as it was, as it did before', $status === 200 && ( galleryAlbumOf( $body, 'sized' )['name'] ?? null ) === 'Renamed' );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'made-before', 'name' => 'Made before' ], 'gallery/album-save' );
check( 'an album saved without the word that it is new is made if there is none and renamed if there is, as it always was: the page of a new album is the one that says it', $status === 200
	&& ( galleryAlbumOf( $body, 'made-before' )['name'] ?? null ) === 'Made before' && count( galleryAlbumOf( $body, 'made-before' )['settings'] ?? [] ) === 6 );
ninoCall( $appData, $admin, [ 'album' => 'made-before' ], 'gallery/album-delete' );

// What an upload becomes
[ $status, $body ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => 'sized' ], 'gallery/upload' ) );
$first = galleryAlbumOf( $body, 'sized' )['images'][0] ?? [];
check( 'an upload into the album is made to the album\'s sizes and not to the feature\'s: a thumbnail of exactly 80x60 and the whole picture inside 300x200 - and the file names say so',
	$status === 200 && galleryDims( $appData, $first['thumb'] ?? '' ) === '80x60' && galleryDims( $appData, $first['large'] ?? '' ) === '300x150'
	&& str_contains( (string) ( $first['thumb'] ?? '' ), '.80x60.' ) === true && str_contains( (string) ( $first['large'] ?? '' ), '.fit300x200.' ) === true );

ninoCall( $appData, $admin, [ 'album' => 'sized', 'settings' => [ 'thumbWidth' => 100, 'thumbHeight' => 100, 'largeWidth' => 250, 'largeHeight' => 250, 'keepRatio' => false ] ], 'gallery/album-save' );
[ $status, $body ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => 'sized' ], 'gallery/upload' ) );
$second = galleryAlbumOf( $body, 'sized' )['images'][1] ?? [];
check( 'changed sizes are the ones of the next upload: the picture is cropped to 250x250 now - and the one that was there keeps the size it was made at',
	galleryDims( $appData, $second['thumb'] ?? '' ) === '100x100' && galleryDims( $appData, $second['large'] ?? '' ) === '250x250' && str_contains( (string) ( $second['large'] ?? '' ), '.250x250.' ) === true
	&& galleryDims( $appData, $first['thumb'] ?? '' ) === '80x60' && galleryDims( $appData, $first['large'] ?? '' ) === '300x150' );

// A record from before the sizes were the album's: it holds none, and reads the feature's
$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$stored[] = [ 'key' => 'old', 'name' => 'Old', 'images' => [] ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
[ $status, $body ] = ninoCall( $appData, $admin, [], 'gallery/list' );
check( 'a record without sizes - which is what every album was before they were its own - reads the feature\'s settings, so the panel has all six to show',
	( galleryAlbumOf( $body, 'old' )['settings'] ?? null ) === [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );
[ $status, $body ] = withUpload( $wide, static fn(): array => ninoCall( $appData, $admin, [ 'album' => 'old' ], 'gallery/upload' ) );
$oldImage = galleryAlbumOf( $body, 'old' )['images'][0] ?? [];
check( '...and an upload into it is made to those', galleryDims( $appData, $oldImage['thumb'] ?? '' ) === '200x200' && galleryDims( $appData, $oldImage['large'] ?? '' ) === '600x300' );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 90, 'thumbHeight' => 90, 'largeWidth' => 500, 'largeHeight' => 500, 'keepRatio' => true, 'columns' => 7 ] );
[ $status, $body ] = ninoCall( $appData, $admin, [], 'gallery/list' );
check( 'the feature\'s settings are what such an album reads as they are now - a default that was copied would have gone stale - while an album with sizes of its own keeps them',
	( galleryAlbumOf( $body, 'old' )['settings']['columns'] ?? null ) === 7 && ( galleryAlbumOf( $body, 'old' )['settings']['thumbWidth'] ?? null ) === 90
	&& ( galleryAlbumOf( $body, 'sized' )['settings']['thumbWidth'] ?? null ) === 100 && ( $body['defaults']['columns'] ?? null ) === 7 );

$oldImageId = (string) ( $oldImage['id'] ?? '' );
ninoCall( $appData, $admin, [ 'album' => 'old', 'id' => $oldImageId, 'locale' => 'en_US', 'caption' => 'Rewritten' ], 'gallery/image-save' );
ninoCall( $appData, $admin, [ 'album' => 'sized', 'order' => array_reverse( array_column( galleryAlbumOf( ninoCall( $appData, $admin, [], 'gallery/list' )[1], 'sized' )['images'] ?? [], 'id' ) ) ], 'gallery/reorder' );
check( 'no other action copies the defaults into such a record: it is a record without sizes after the file was written again, by a caption and by an order',
	array_key_exists( 'settings', $storedAlbum( 'old' ) ) === false && ( $storedAlbum( 'old' )['images'][0]['caption'] ?? null ) === [ 'en_US' => 'Rewritten' ] );

\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 4 ] );
ninoCall( $appData, $admin, [ 'album' => 'old' ], 'gallery/album-delete' );
ninoCall( $appData, $admin, [ 'album' => 'sized' ], 'gallery/album-delete' );
check( 'those albums go with their pictures, and the one the rest of the test is about is as it was',
	count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/sized' ). '/*' ) ?: [] ) === 0 && count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/old' ). '/*' ) ?: [] ) === 0
	&& array_column( \Nino\Modules\Gallery::albums( $appData ), 'key' ) === [ 'trip' ] && count( \Nino\Modules\Gallery::albums( $appData )[0]['images'] ) === 2 );

echo "\n";


// --- The shortcode -----------------------------------------------------------

echo "[gallery] - the markup the Lightbox reads\n";

// The sandbox is in German; the captions the panel part above wrote for the
// second image are in both languages, and this part reads them in English first
\Nino\Locales::useLocale( $appData, 'en_US' );

$html = galleryPage( $appData, '[gallery album="trip"]' );

check( 'it renders one item per image, in the stored order', substr_count( $html, '<li class="nino-gallery-cell">' ) === 2 );
check( 'every thumbnail is a link to the large view', substr_count( $html, 'class="nino-gallery-link"' ) === 2 && str_contains( $html, '/images/gallery/trip/' ) === true );
// The group is the album, so two galleries on one page stay two sets - and
// this is the whole of what the Lightbox feature needs from here
check( 'each link carries the lightbox group of its own album', substr_count( $html, 'data-lightbox="gallery-trip"' ) === 2 );
check( 'a caption travels as the caption and - where there is no alt text - as the alt', str_contains( $html, 'data-caption="Above the pass"' ) === true && str_contains( $html, 'alt="Above the pass"' ) === true );
check( 'an image without one says it has no caption: the name it is given below is for whoever cannot see it, and not a caption the Lightbox would show under the picture',
	substr_count( $html, 'data-caption=' ) === 2 && substr_count( $html, 'data-caption=""' ) === 1 && substr_count( $html, 'data-caption="Above the pass"' ) === 1 );
check( 'the thumbnails are lazy', substr_count( $html, 'loading="lazy"' ) === 2 );
check( 'the column count travels as a custom property, so the stylesheet needs no rule per number', str_contains( $html, '--nino-gallery-columns:4' ) === true );
/*	The room a thumbnail takes before it has loaded: the size the album's thumbnails are cropped to - the album's, 200 x 200 here -
	on the picture, where the browser makes a ratio of it before a byte has come, and on the list as two properties for the
	stylesheet, which gives the link the same ratio. Without them a cell is as high as nothing until its picture is there, every
	lazy picture is in view at once and none of them waits	*/
check( 'every thumbnail carries the width and the height it is cropped to, so the browser knows its ratio before it has loaded',
	substr_count( $html, 'width="200" height="200"' ) === 2 && preg_match_all( '/<img [^>]*\bwidth="(\d+)" height="(\d+)"/', $html ) === 2 );
check( '...and the list carries the same two numbers as properties, for the stylesheet',
	str_contains( $html, '--nino-gallery-thumb-w:200;--nino-gallery-thumb-h:200' ) === true );
check( '[gallery columns="2"] overrides it for one gallery', str_contains( galleryPage( $appData, '[gallery album="trip" columns="2"]' ), '--nino-gallery-columns:2' ) === true );
check( 'a bare [gallery] renders the first album', str_contains( galleryPage( $appData, '[gallery]' ), 'data-lightbox="gallery-trip"' ) === true );
check( 'an album key nothing has renders nothing at all', galleryPage( $appData, '[gallery album="nowhere"]' ) === '' );

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
	[ 'key' => 'wide', 'name' => 'Wide', 'images' => [ $picture ], 'settings' => [ 'thumbWidth' => 120, 'thumbHeight' => 80 ] ],
	[ 'key' => 'trip-2', 'name' => 'Trip, the second', 'images' => [ $picture ] ],
	[ 'key' => 'tour', 'name' => 'Tour', 'images' => [ $picture ] ],
	[ 'key' => 'tour-2', 'name' => 'Tour, the second', 'images' => [] ],
] ) );
\Nino\Features::saveSettings( $appData, 'gallery', [ 'thumbWidth' => 200, 'thumbHeight' => 200, 'largeWidth' => 600, 'largeHeight' => 600, 'keepRatio' => true, 'columns' => 6 ] );
$columnsOf = static function( string $call ) use ( &$appData ): string {
	return preg_match( '/--nino-gallery-columns:(\d+)/', galleryPage( $appData, $call ), $found ) === 1 ? $found[1] : '';
};
check( 'the grid is drawn in the columns of its album: an album with three draws three', $columnsOf( '[gallery album="three"]' ) === '3' );
check( '...a call that sets a count of its own has the last word', $columnsOf( '[gallery album="three" columns="2"]' ) === '2' && $columnsOf( '[gallery album="three" columns="8"]' ) === '8' && $columnsOf( '[gallery album="three" columns="1"]' ) === '1' );
check( '...and a count that is none - 9, 0, below nought, a word, nothing at all - is the album\'s: 3, not the 4 the code once made of it',
	array_unique( array_map( $columnsOf, array_map( static fn( string $count ): string => '[gallery album="three" columns="'. $count. '"]', [ '9', '0', '-2', '100', 'x', '', 'many', '3x', '4.5', '4px', '1e0' ] ) ) ) === [ '3' ] );
check( '...and the feature\'s where the album has no count of its own either: 6',
	$columnsOf( '[gallery album="none" columns="9"]' ) === '6' && $columnsOf( '[gallery album="none" columns="x"]' ) === '6' && $columnsOf( '[gallery album="broken" columns="0"]' ) === '6' );
check( '...an album that holds none, or one that is no count, or none that is a map, draws the feature\'s: six', $columnsOf( '[gallery album="none"]' ) === '6'
	&& $columnsOf( '[gallery album="nine"]' ) === '6' && $columnsOf( '[gallery album="broken"]' ) === '6' );
// The size comes from the album, and from the feature where the album holds none: 120 x 80 here, 200 x 200 there
$wideGrid = galleryPage( $appData, '[gallery album="wide"]' );
check( 'the thumbnail of an album with a size of its own carries that size, on the picture and on the list - and an album with none the feature\'s',
	str_contains( $wideGrid, 'width="120" height="80"' ) === true && str_contains( $wideGrid, '--nino-gallery-thumb-w:120;--nino-gallery-thumb-h:80' ) === true
	&& str_contains( galleryPage( $appData, '[gallery album="none"]' ), 'width="200" height="200"' ) === true
	&& str_contains( galleryPage( $appData, '[gallery album="none"]' ), '--nino-gallery-thumb-w:200;--nino-gallery-thumb-h:200' ) === true );

/*	The same album twice on one page: the Lightbox makes a set of every link that carries one group, so two
	galleries of one group were one set of both - '1 / 4' where each holds two. The first gallery of an album
	keeps the group it always had, and each one after it has a group of its own, counted over the request	*/
check( 'a gallery on its own carries the group of its album, and each of its pictures is in it',
	galleryGroups( galleryPage( $appData, '[gallery album="trip"]' ) ) === [ 'gallery-trip' => 2 ] );
check( 'the same album twice on one page is two sets, each with all of the album\'s pictures: the second has a group of its own',
	galleryGroups( galleryPage( $appData, '[gallery album="trip"] [gallery album="trip"]' ) ) === [ 'gallery-trip' => 2, 'gallery-trip-2' => 2 ] );
check( '...a third is a third set',
	galleryGroups( galleryPage( $appData, '[gallery album="trip"][gallery album="trip"][gallery album="trip"]' ) ) === [ 'gallery-trip' => 2, 'gallery-trip-2' => 2, 'gallery-trip-3' => 2 ] );
check( 'galleries of different albums are counted apart: the second of an album is the only one with a suffix',
	galleryGroups( galleryPage( $appData, '[gallery album="trip"][gallery album="wide"][gallery album="trip"]' ) ) === [ 'gallery-trip' => 2, 'gallery-wide' => 1, 'gallery-trip-2' => 2 ] );
check( '...and a call without a key is the first album, counted with the calls that name it',
	galleryGroups( galleryPage( $appData, '[gallery][gallery album="trip"]' ) ) === [ 'gallery-trip' => 2, 'gallery-trip-2' => 2 ] );
check( 'a call that draws nothing takes no group: an album without pictures and a key no album has leave the numbers where they were',
	galleryGroups( galleryPage( $appData, '[gallery album="tour-2"][gallery album="nowhere"][gallery album="tour"][gallery album="tour"]' ) ) === [ 'gallery-tour' => 1, 'gallery-tour-2' => 1 ] );
check( 'a group is never shared: the second \'trip\' is not the set of an album whose key is \'trip-2\', whichever is drawn first',
	galleryGroups( galleryPage( $appData, '[gallery album="trip"][gallery album="trip"][gallery album="trip-2"]' ) ) === [ 'gallery-trip' => 2, 'gallery-trip-2' => 2, 'gallery-trip-2-2' => 1 ]
	&& galleryGroups( galleryPage( $appData, '[gallery album="trip-2"][gallery album="trip"][gallery album="trip"]' ) ) === [ 'gallery-trip-2' => 1, 'gallery-trip' => 2, 'gallery-trip-3' => 2 ] );

// Not through galleryPage(): the request in between is the one that forgets
\Nino\Html::renderHtml( $appData, '[gallery album="trip"][gallery album="trip"]' );
ninoVisit( $appData, '/' );
check( 'the next page, a request of its own, starts again with the group of the album and no suffix',
	galleryGroups( \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' ) ) === [ 'gallery-trip' => 2 ] );
check( '...while a second gallery within the request goes on counting',
	galleryGroups( \Nino\Html::renderHtml( $appData, '[gallery album="trip"]' ) ) === [ 'gallery-trip-2' => 2 ] );

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
/*	The two classes of the kernel's hover - the frame says nino-image and nino-hover, and Nino.css draws what the pointer does to it - are the
	templates' to write and the stylesheet's never to style: every other class the templates write, and every class the stylesheet selects, is
	this feature's own	*/
$hoverClasses = [ 'nino-image', 'nino-hover' ];
$written = [];
foreach( [ 'templates/gallery.tpl', 'templates/gallery-item.tpl' ] as $file ) {
	preg_match_all( '/class="([^"]*)"/', (string) file_get_contents( dirname( __DIR__ ). '/'. $file ), $found );
	foreach( $found[1] as $list )
		$written = array_merge( $written, array_diff( (array) preg_split( '/\s+/', trim( $list ) ), $hoverClasses ) );
}
preg_match_all( '/\.(nino-[a-z0-9-]+)/', $uncommented( dirname( __DIR__ ). '/assets/gallery.css' ), $found );
$written = array_values( array_unique( array_merge( $written, $found[1] ) ) );
$styledByKernel = array_values( array_filter( $written, static fn( string $class ): bool => preg_match( '/\.'. preg_quote( $class, '/' ). '(?![\w-])/', $ninoCss ) === 1 ) );
check( 'no class the templates or the stylesheet write is one Nino.css styles - the bundle is shared, and the kernel\'s .nino-gallery is another grid - but the two of the hover, which the template says and the stylesheet never selects', $written !== [] && $styledByKernel === [] );

// The attribute of a caption is the template's: the class fills in its text, escaped, and none when there is none
check( 'the caption attribute is written in the template of an item - an empty caption is the empty attribute',
	str_contains( (string) file_get_contents( dirname( __DIR__ ). '/templates/gallery-item.tpl' ), 'data-caption="[[caption]]"' ) === true );

// The link is the box, its ratio the album's thumbnail size; a thumbnail made before that size was changed keeps its old shape, and fills the
// box all the same - cut to it - rather than leaving a gap or being clipped by the link
$galleryCss = $uncommented( dirname( __DIR__ ). '/assets/gallery.css' );
preg_match( '/\.nino-gallery-thumb\s*\{([^}]*)\}/', $galleryCss, $thumbRule );
check( 'the thumbnail fills the box of its link, cut to it: width and height 100% and object-fit: cover',
	preg_match( '/\bwidth:\s*100%\s*;/', $thumbRule[1] ?? '' ) === 1 && preg_match( '/\bheight:\s*100%\s*;/', $thumbRule[1] ?? '' ) === 1 && preg_match( '/\bobject-fit:\s*cover\s*;/', $thumbRule[1] ?? '' ) === 1
	&& preg_match( '/\.nino-gallery-link\s*\{[^}]*\baspect-ratio:\s*var\(--nino-gallery-thumb-w[^;]*--nino-gallery-thumb-h/', $galleryCss ) === 1 );

// A caption is editor text and may be a textfill, which is how one caption
// serves every language - and what comes out of the fill engine is escaped
// like anything else that reaches an attribute
\Nino\Html::addFills( $appData, [ '[[/project/gallery/one/caption]]' => 'Am Pass "oben"' ], '*' );
ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'en_US', 'caption' => '[[/project/gallery/one/caption]]' ], 'gallery/image-save' );
$filled = galleryPage( $appData, '[gallery album="trip"]' );
check( 'a caption written as a fill is resolved, and what comes out is escaped', str_contains( $filled, 'data-caption="Am Pass &quot;oben&quot;"' ) === true
	&& str_contains( $filled, '[[/project/gallery/' ) === false );

// The alt text is a text of its own, and so is each language's. What the
// thumbnail says is the alt text; what the Lightbox shows under the picture is
// the caption - and where there is an alt text and no caption, an empty
// data-caption tells it there is none, instead of showing the alt text there
ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'en_US', 'caption' => '', 'alt' => 'Front of the "house"' ], 'gallery/image-save' );
ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[0], 'locale' => 'de_DE', 'alt' => 'Vorderseite des Hauses' ], 'gallery/image-save' );
$both = galleryPage( $appData, '[gallery album="trip"]' );
check( 'an alt text and no caption: the thumbnail is named by the alt text, and the link says it has no caption', str_contains( $both, 'alt="Front of the &quot;house&quot;"' ) === true
	&& str_contains( $both, 'data-caption=""' ) === true && substr_count( $both, 'data-caption=' ) === 2 );
check( 'the alt text is escaped like anything that reaches an attribute', str_contains( $both, 'alt="Front of the "house""' ) === false );
check( 'an image with only a caption keeps it as both, and one with neither carries no caption attribute at all', substr_count( $both, 'data-caption="Above the pass"' ) === 1
	&& substr_count( galleryPage( $appData, '[gallery album="trip" columns="2"]' ), 'alt=""' ) === 0 );

\Nino\Locales::useLocale( $appData, 'de_DE' );
$german = galleryPage( $appData, '[gallery album="trip"]' );
check( 'the same page in German reads the German alt text and the German caption', str_contains( $german, 'alt="Vorderseite des Hauses"' ) === true
	&& str_contains( $german, 'data-caption="Über dem Pass"' ) === true && str_contains( $german, 'Above the pass' ) === false );

// Only English written for the second image: German is the site's own language
// and has nothing, so the first there is is better than a picture with no words
ninoCall( $appData, $admin, [ 'album' => 'trip', 'id' => $ids[1], 'locale' => 'de_DE', 'caption' => '' ], 'gallery/image-save' );
check( 'a language that has no text falls back to the site\'s own, then to the first there is', str_contains( galleryPage( $appData, '[gallery album="trip"]' ), 'data-caption="Above the pass"' ) === true );

/*	A picture with neither an alt text nor a caption is a link with no name: a screen reader reads out its address.
	It is named by its album and its place in it - the words are /feature/gallery/image/unnamed, which the unit delivers
	in both languages and the code renders through the fill engine, with no sentence of its own. The album has four
	pictures: an alt text, nothing, a caption, nothing - places 2 and 4 are unnamed, so that a number is the place of the
	picture and not the count of those without a name	*/
$unitWords = [];
foreach( [ 'en_US', 'de_DE' ] as $locale )
	$unitWords[$locale] = (array) include dirname( __DIR__ ). '/install/text/'. $locale. '.php';
$unnamedKey = '[[/feature/gallery/image/unnamed]]';
// What the words say for an album and a place: the %s filled in the order they stand, the name escaped as an attribute has it
$unnamedAlt = static fn( string $locale, string $album, int $place ): string => sprintf( $unitWords[$locale][$unnamedKey], htmlspecialchars( $album, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ), $place );
$albumsNow = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
$picture = $albumsNow[0]['images'][0];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( $albumsNow, [
	[ 'key' => 'bare', 'name' => 'Lake "House" & <Sauna>', 'images' => [
		[ 'alt' => 'The jetty', 'caption' => '' ] + $picture,
		[ 'alt' => '', 'caption' => '' ] + $picture,
		[ 'alt' => '', 'caption' => 'At dusk' ] + $picture,
		[ 'alt' => '', 'caption' => '' ] + $picture,
	] ],
] ) );
$altsOf = static function( string $html ): array {
	preg_match_all( '/<img [^>]*\balt="([^"]*)"/', $html, $found );
	return $found[1];
};

\Nino\Locales::useLocale( $appData, 'en_US' );
check( 'a picture with neither is named by its album and its place in it, in the words of the unit, escaped like anything that reaches an attribute - the others keep what they have',
	$altsOf( galleryPage( $appData, '[gallery album="bare"]' ) ) === [ 'The jetty', $unnamedAlt( 'en_US', 'Lake "House" & <Sauna>', 2 ), 'At dusk', $unnamedAlt( 'en_US', 'Lake "House" & <Sauna>', 4 ) ]
	&& str_contains( $unnamedAlt( 'en_US', 'Lake "House" & <Sauna>', 2 ), '&quot;House&quot; &amp; &lt;Sauna&gt;' ) === true );
\Nino\Locales::useLocale( $appData, 'de_DE' );
check( '...in German in a German page, in the German words', $altsOf( galleryPage( $appData, '[gallery album="bare"]' ) )[1] === $unnamedAlt( 'de_DE', 'Lake "House" & <Sauna>', 2 )
	&& $unnamedAlt( 'de_DE', 'x', 2 ) !== $unnamedAlt( 'en_US', 'x', 2 ) );
\Nino\Locales::useLocale( $appData, 'en_US' );
check( '...and none of them is without a name any more, the link of the first image of "trip" included',
	in_array( '', $altsOf( galleryPage( $appData, '[gallery album="bare"]' ) ), true ) === false
	&& in_array( '', $altsOf( galleryPage( $appData, '[gallery album="trip"]' ) ), true ) === false );
check( '...and the name is not a caption: a link says it has none, which the Lightbox reads as no caption',
	substr_count( galleryPage( $appData, '[gallery album="bare"]' ), 'data-caption=""' ) === 3 && substr_count( galleryPage( $appData, '[gallery album="bare"]' ), 'data-caption="At dusk"' ) === 1 );

// The unit: the sentence in both languages with a place for the name and one for the number, a word of the project like its others
check( 'the unit delivers /feature/gallery/image/unnamed in both languages, with a %s for the album and one for the picture',
	array_keys( $unitWords['en_US'] ) === [ '[[/feature/gallery/image/unnamed]]' ] && array_keys( $unitWords['de_DE'] ) === array_keys( $unitWords['en_US'] )
	&& substr_count( $unitWords['en_US']['[[/feature/gallery/image/unnamed]]'], '%s' ) === 2 && substr_count( $unitWords['de_DE']['[[/feature/gallery/image/unnamed]]'], '%s' ) === 2
	&& $unitWords['en_US']['[[/feature/gallery/image/unnamed]]'] !== $unitWords['de_DE']['[[/feature/gallery/image/unnamed]]'] );
check( '...and activation puts it into the text files of the project, in each language',
	( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/gallery/image/unnamed]]'] ?? null ) === $unitWords['en_US']['[[/feature/gallery/image/unnamed]]']
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/feature/gallery/image/unnamed]]'] ?? null ) === $unitWords['de_DE']['[[/feature/gallery/image/unnamed]]'] );

// The words are the project's once they are in its text files, and a sentence with too few or too many places never ends the request
$bareOf = static function( string $words ) use ( &$appData, $altsOf ): string {
	\Nino\Html::addFills( $appData, [ '[[/feature/gallery/image/unnamed]]' => $words ], '*' );
	return $altsOf( galleryPage( $appData, '[gallery album="bare"]' ) )[3];
};
ninoWarnings();
check( 'the words of the project are the ones used: the name where %s stands first and the place where it stands second',
	$bareOf( 'Foto %s, Nr. %s' ) === 'Foto Lake &quot;House&quot; &amp; &lt;Sauna&gt;, Nr. 4' );
check( '...and words with one place take the name, with none take neither, with three leave the third empty - and none of it is an error',
	$bareOf( 'Foto von %s' ) === 'Foto von Lake &quot;House&quot; &amp; &lt;Sauna&gt;' && $bareOf( 'Ein Foto' ) === 'Ein Foto' && $bareOf( '%s/%s/%s' ) === 'Lake &quot;House&quot; &amp; &lt;Sauna&gt;/4/' && ninoWarnings() === [] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, array_merge( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] ), [ [ 'key' => 'pct', 'name' => 'Sale %s', 'images' => [ [ 'alt' => '', 'caption' => '' ] + $picture ] ] ] ) );
check( '...and a %s in the name of the album is the name, not another place', $bareOf( '%s, picture %s' ) !== '' && $altsOf( galleryPage( $appData, '[gallery album="pct"]' ) ) === [ 'Sale %s, picture 1' ] );

// A project that has none of the words - the unit is applied when the feature is activated: the code has no sentence of its own, so the key
// shows as every key without a word does, in either language. The text files without the key, and as they were again afterwards
$textFiles = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$textFiles[$locale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	$without = $textFiles[$locale];
	unset( $without[$unnamedKey] );
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $without );
}
\Nino\Html::removeFill( $appData, '/feature/gallery/image/unnamed', '*' );
$withoutWords = $altsOf( galleryPage( $appData, '[gallery album="pct"]' ) );
\Nino\Locales::useLocale( $appData, 'de_DE' );
$withoutWordsGerman = $altsOf( galleryPage( $appData, '[gallery album="pct"]' ) );
\Nino\Locales::useLocale( $appData, 'en_US' );
foreach( $textFiles as $locale => $content )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );
check( '...and a project without the word has the key where the name would be, in either language - no sentence in the code stands in for it',
	$withoutWords === [ $unnamedKey ] && $withoutWordsGerman === [ $unnamedKey ] );
// What was there before the words of the project were tried: the fills of each language from its text file, none in the bucket of no language
$restored = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	\Nino\Locales::useLocale( $appData, $locale );
	$restored[$locale] = $altsOf( galleryPage( $appData, '[gallery album="bare"]' ) )[3];
}
\Nino\Locales::useLocale( $appData, 'en_US' );
check( '...and the words of each language are the ones of its own text file again after the words of a project were tried',
	$restored === [ 'en_US' => $unnamedAlt( 'en_US', 'Lake "House" & <Sauna>', 4 ), 'de_DE' => $unnamedAlt( 'de_DE', 'Lake "House" & <Sauna>', 4 ) ] );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $albumsNow );

// Not ninoWarnings() === []: the sandbox has no text files, and every fill the
// panel renders says so. What a map in a text must never do is be cast
check( 'none of it cast a map to a string', array_filter( ninoWarnings(), static fn( string $warning ): bool => str_contains( $warning, 'Array to string' ) === true ) === [] );

echo "\n";


// --- The looks of the Design feature ----------------------------------------------

echo "design/ - the looks, the knobs and the block that the Design feature reads\n";

/*	What a feature brings the Design feature as files (its manual, under Base, says what a variant and a block are). Read here by their
	grammar - which tags, which fields, which names, which rule where - and what a head says is never compared: the words are the
	files' to say. The base stylesheet owns the grid and declares every property a look moves, the looks move them	*/
$design 		= $dir. '/design';
$base 			= gallerySheet( $dir. '/assets/gallery.css' );
$looks 			= [];

foreach( glob( $design. '/*.css' ) ?: [] as $file )
	$looks[ basename( $file, '.css' ) ] = gallerySheet( $file );

ksort( $looks );

$kinds			= [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
$tabs				= [ 'type', 'surfaces', 'colours', 'motion' ];
$surfaces		= [ 'default', 'alt', 'tint', 'dark', 'black', 'brand', 'brand-safe', 'accent-safe', 'success', 'warning', 'danger' ];
$steps			= [ 'least', 'less', 'default', 'more', 'most' ];
$stepSuffix	= '/--(?:least|less|default|more|most)$/';
$knobTable	= [ 'gap' => [ 'scale', 'surfaces' ], 'corners' => [ 'scale', 'surfaces' ], 'ground' => [ 'ground', 'colours' ] ];
$answers		= [ 'mat' => [ 'gap', 'corners', 'ground' ], 'regular' => [ 'gap', 'corners', 'ground' ], 'seamless' => [ 'ground' ] ];

check( 'the feature brings three looks of the Design feature, a stylesheet each in design/ - and regular, which is where a feature starts from, is the grid as it has always been - and the block for the preview beside them',
	array_keys( $looks ) === [ 'mat', 'regular', 'seamless' ] && array_map( 'basename', glob( $design. '/*' ) ?: [] ) === [ 'mat.css', 'preview.tpl', 'regular.css', 'seamless.css' ] );

$headless = [];

foreach( $looks as $variant => $look )
	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $look['tags'][$tag] ?? [] ) !== 1 || trim( $look['tags'][$tag][0] ) === '' )
			$headless[] = $variant. ' '. $tag;

check( 'each head names and describes its look in both languages, once each: @name, @name-de, @description, @description-de'. ( $headless === [] ? '' : ' - '. implode( ', ', $headless ) ), $headless === [] && $looks !== [] );

/** The knobs a head declares: name => kind, tab, label, default (null where the line has none), and how often it was declared */
$knobsOf = [];

foreach( $looks as $variant => $look )
	foreach( $look['tags']['@knob'] ?? [] as $text ) {
		$fields = (array) preg_split( '/\s+/', $text, 4 );
		preg_match( '/^(.*?)(?:\s+=\s+(\S+))?$/', (string) ( $fields[3] ?? '' ), $label );
		$knobsOf[$variant][ (string) $fields[0] ][] = [ 'fields' => count( $fields ), 'kind' => (string) ( $fields[1] ?? '' ), 'tab' => (string) ( $fields[2] ?? '' ), 'label' => (string) ( $label[1] ?? '' ), 'default' => $label[2] ?? null ];
	}

$knobProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( $knobsOf[$variant] ?? [] as $name => $lines ) {
		$line = $lines[0];

		if( count( $lines ) !== 1 || $line['fields'] !== 4 || in_array( $line['kind'], $kinds, true ) === false || in_array( $line['tab'], $tabs, true ) === false || $line['label'] === '' )
			$knobProblems[] = $variant. ' @knob '. $name;

		$twins = array_filter( $look['tags']['@knob-de'] ?? [], static fn( string $text ): bool => preg_match( '/^'. preg_quote( $name, '/' ). '\s+\S/', $text ) === 1 );

		if( count( $twins ) !== 1 )
			$knobProblems[] = $variant. ' @knob-de '. $name;

		// A scale takes no default - its middle is the one it stands at - and a ground the surface of the palette it stands at
		if( $line['kind'] === 'scale' ? $line['default'] !== null : in_array( $line['default'], $surfaces, true ) === false )
			$knobProblems[] = $variant. ' default of '. $name;
	}

	foreach( $look['tags']['@knob-de'] ?? [] as $text )
		if( isset( $knobsOf[$variant][ (string) strtok( $text, " \t" ) ] ) === false )
			$knobProblems[] = $variant. ' @knob-de '. strtok( $text, " \t" ). ' names no knob';
}

check( 'each @knob line has its four fields - the name, a kind, a tab, the words - once, a @knob-de beside it, and a ground the surface of the palette it stands at'. ( $knobProblems === [] ? '' : ' - '. implode( ', ', $knobProblems ) ), $knobProblems === [] && $knobsOf !== [] );

$offTable = [];

foreach( $knobsOf as $variant => $declared )
	foreach( $declared as $name => $lines )
		if( ( $knobTable[$name] ?? null ) !== [ $lines[0]['kind'], $lines[0]['tab'] ] )
			$offTable[] = $variant. ' '. $name;

check( 'every knob is one of the three, of the same kind and on the same tab in every look: the space between the pictures and the corners on Surface, the ground on Colour - and there is none for the columns, which are the album\'s and the shortcode\'s'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ),
	$offTable === [] && array_diff( array_keys( $knobsOf ), array_keys( $answers ) ) === [] );
check( 'a look declares only what it answers to: Grid and Mat all three, Seamless the ground alone - it has no space and no corners to move, and the panel greys the two out under it with its line',
	array_map( static fn( array $declared ): array => array_keys( $declared ), $knobsOf ) === $answers );

$ladderProblems = [];

foreach( $looks as $variant => $look ) {
	$stems = [];

	foreach( $knobsOf[$variant] ?? [] as $name => $lines )
		if( $lines[0]['kind'] === 'scale' ) {
			$stems[] = '--gallery-'. $name;

			foreach( $steps as $step )
				if( count( $look['declared'][ '--gallery-'. $name. '--'. $step ] ?? [] ) !== 1 || $look['declared'][ '--gallery-'. $name. '--'. $step ][0]['selector'] !== ':root' )
					$ladderProblems[] = $variant. ' lacks --gallery-'. $name. '--'. $step. ' on :root, once';
		}

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 && in_array( (string) preg_replace( $stepSuffix, '', $property ), $stems, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs - once each, on :root - and every ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ),
	$ladderProblems === [] && count( array_filter( array_keys( $looks['regular']['declared'] ?? [] ), static fn( string $property ): bool => preg_match( $stepSuffix, $property ) === 1 ) ) === 10 && count( array_filter( array_keys( $looks['mat']['declared'] ?? [] ), static fn( string $property ): bool => preg_match( $stepSuffix, $property ) === 1 ) ) === 10
	&& array_filter( array_keys( $looks['seamless']['declared'] ?? [] ), static fn( string $property ): bool => preg_match( $stepSuffix, $property ) === 1 ) === [] );

/*	Seamless has no space and no corners, and says so by setting the two properties to nothing: on :root, where a look moves a default, and to a
	length that is none - the knobs it does not declare are not moved by anything else	*/
$seamless = $looks['seamless']['declared'] ?? [];

check( 'Seamless sets the space and the corners to nothing, on :root and nowhere else - a length of zero, which no knob of its own moves',
	array_column( $seamless['--gallery-gap'] ?? [], 'selector' ) === [ ':root' ] && array_column( $seamless['--gallery-corners'] ?? [], 'selector' ) === [ ':root' ]
	&& preg_match( '/^0(?:\.0+)?[a-z%]*$/', (string) ( $seamless['--gallery-gap'][0]['value'] ?? '' ) ) === 1 && preg_match( '/^0(?:\.0+)?[a-z%]*$/', (string) ( $seamless['--gallery-corners'][0]['value'] ?? '' ) ) === 1
	&& array_column( $looks['seamless']['rules'] ?? [], 'selector' ) === [ ':root', '.nino-gallery-cell > .nino-gallery-link:focus-visible' ] );

/*	With no space between the pictures the ring of a focused link would run into its neighbours, which are positioned and paint over it: the focused link is raised
	over them, and the ring is two lines - the outline in the text colour of the ground and a shadow in the ground itself in the gap between the picture and the outline
	- so that one of them shows on whatever the picture is	*/
$seamlessRing = array_values( array_filter( $looks['seamless']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-gallery-cell > .nino-gallery-link:focus-visible' ) );
$ringMade			= array_column( galleryDeclarations( $seamlessRing[0]['body'] ?? '' ), 'value', 'property' );

check( 'Seamless raises the focused link over its neighbours and gives the ring a second line: a z-index above the cells, the outline in the text colour of the ground, a shadow in the ground itself',
	count( $seamlessRing ) === 1 && preg_match( '/^[1-9]\d*$/', (string) ( $ringMade['z-index'] ?? '' ) ) === 1 && ( $ringMade['outline-color'] ?? '' ) === 'var(--gallery-ground-on)'
	&& preg_match( '/^0 0 0 \d+(?:\.\d+)?[a-z]+ var\(--gallery-ground\)$/', (string) ( $ringMade['box-shadow'] ?? '' ) ) === 1 );

$baseDeclared	= $base['declared'];
$ruleProblems	= [];

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule ) {
		$properties = array_column( galleryDeclarations( $rule['body'] ), 'property' );

		// The ladders and the properties Seamless moves stand on :root; a rule is on the grid's own classes and on nothing else - no element, no id, no class of the kernel's
		if( $rule['selector'] === ':root' ) {
			foreach( $properties as $property )
				if( preg_match( '/^--gallery-[a-z0-9-]+--(?:least|less|default|more|most)$/', $property ) !== 1 && in_array( $property, [ '--gallery-gap', '--gallery-corners' ], true ) === false )
					$ruleProblems[] = $variant. ' declares '. $property. ' on :root, which is no rung of a ladder';
		}
		elseif( $rule['at'] !== '' || preg_match( '/^[\s>+~,]*$/', (string) preg_replace( [ '/\.nino-gallery[a-z0-9-]*/', '/:focus-visible/' ], '', $rule['selector'] ) ) !== 1 || str_contains( $rule['selector'], '.nino-gallery' ) === false )
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];

		foreach( galleryDeclarations( $rule['body'] ) as $declaration ) {
			preg_match_all( '/var\(\s*(--gallery-[a-z0-9-]+)/', $declaration['value'], $reads );

			foreach( $reads[1] as $read )
				if( isset( $baseDeclared[$read] ) === false )
					$ruleProblems[] = $variant. ' reads '. $read. ', which gallery.css does not declare';
		}
	}

check( 'a look is a head, the rungs of its ladders on :root and rules on the grid\'s own classes - never an element, an id or a class of the kernel - and it reads only properties that gallery.css declares'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 3 );

$colourProblems = [];

foreach( array_merge( array_map( static fn( string $variant ): string => $design. '/'. $variant. '.css', array_keys( $looks ) ), [ $dir. '/assets/gallery.css' ] ) as $file ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );

	foreach( galleryColourLiterals( $code ) as $literal )
		$colourProblems[] = basename( $file ). ' '. $literal;

	if( stripos( $code, '!important' ) !== false )
		$colourProblems[] = basename( $file ). ' !important';
}

check( '...and no stylesheet of the feature writes a colour as a literal - a hex colour, a function, the name of one - or declares anything !important: the colours are a ground and what the page says'. ( $colourProblems === [] ? '' : ' - '. implode( ', ', $colourProblems ) ), $colourProblems === [] );

/*	Where the defaults stand: lengths, numbers and the ground with its parts in :where(:root), and nowhere else - a custom property is resolved where it is
	declared, and :where() has no specificity, so the line of the knob positions a look compiles beats it. A ground in :where(*) would be declared on every
	element and beat the compiled one on all of them	*/
$gallerySource	= (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/gallery.css' ) );
$strayDefaults	= [];
$owned					= [ '--gallery-gap', '--gallery-corners', '--gallery-ground', '--gallery-ground-on', '--gallery-ground-border' ];

foreach( $owned as $property )
	if( array_column( $baseDeclared[$property] ?? [], 'selector' ) !== [ ':where(:root)' ] )
		$strayDefaults[] = $property. ' is not declared once in :where(:root)';

foreach( $base['rules'] as $rule )
	foreach( galleryDeclarations( $rule['body'] ) as $declaration )
		if( str_starts_with( $declaration['property'], '--gallery-' ) === true && $rule['selector'] !== ':where(:root)' )
			$strayDefaults[] = $declaration['property']. ' is declared under '. $rule['selector'];

// Each property it declares is read by a var() - in the grid or in a look - since one that nothing reads is a knob that moves nothing
$readAnywhere = $gallerySource. implode( "\n", array_map( static fn( string $variant ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/design/'. $variant. '.css' ) ), array_keys( $looks ) ) );

foreach( array_unique( array_merge( $owned, array_filter( array_keys( $baseDeclared ), static fn( string $property ): bool => str_starts_with( $property, '--gallery-' ) ) ) ) as $property )
	if( preg_match( '/var\(\s*'. preg_quote( $property, '/' ). '\s*[,)]/', $readAnywhere ) !== 1 )
		$strayDefaults[] = $property. ' is read by nothing';

if( preg_match( '/:root\s*\{/', (string) preg_replace( '/:where\(:root\)/', '', $gallerySource ) ) === 1 )
	$strayDefaults[] = 'a bare :root in gallery.css';

check( 'gallery.css declares every property a look moves, once, in :where(:root) and nowhere else - never in a bare :root, on a class, in :where(*) or with !important - and each of them is read by a var()'. ( $strayDefaults === [] ? '' : ' - '. implode( ', ', $strayDefaults ) ),
	$strayDefaults === [] && str_contains( $gallerySource, 'var(--gallery-gap)' ) === true );

// Grid is the gallery as it is without the Design feature: the middle of each ladder is the default gallery.css has, it writes no rule, and the ground it stands at is the one gallery.css has
$unequal = [];

foreach( $looks['regular']['declared'] ?? [] as $property => $places )
	if( preg_match( '/^(.+)--default$/', $property, $stem ) === 1 && ( $baseDeclared[ $stem[1] ][0]['value'] ?? null ) !== $places[0]['value'] )
		$unequal[] = $property;

foreach( $looks['regular']['rules'] ?? [] as $rule )
	if( $rule['selector'] !== ':root' )
		$unequal[] = 'a rule on '. $rule['selector'];

if( ( $baseDeclared['--gallery-ground'][0]['value'] ?? null ) !== 'var(--color-section-'. ( $knobsOf['regular']['ground'][0]['default'] ?? '' ). '-bg)' )
	$unequal[] = 'the ground it stands at is not the one gallery.css has';

check( 'Grid stands where gallery.css stands: the middle of the space and of the corners is the default the base declares, it writes no rule, and the ground it stands at is the surface gallery.css reads - so the gallery under it is drawn as it is without the Design feature'. ( $unequal === [] ? '' : ' - '. implode( ', ', $unequal ) ),
	$unequal === [] && count( array_filter( array_keys( $looks['regular']['declared'] ?? [] ), static fn( string $property ): bool => str_ends_with( $property, '--default' ) ) ) === 2 );

// The hover is the kernel's: nothing in gallery.css moves a picture - no transform, no rule under :hover, no transition of its own, and no reduced-motion block to take the zoom back
check( 'gallery.css moves no picture: no transform, no :hover, no transition and no reduced-motion block - the hover is the kernel\'s, set for every picture under Page, Motion',
	preg_match( '/\btransform\s*:|:hover|\btransition\s*:|prefers-reduced-motion/', $gallerySource ) !== 1 && preg_match( '/\btransform\b|:hover|\btransition\b/', (string) preg_replace( '#/\*.*?\*/#s', '', implode( "\n", array_map( static fn( string $variant ): string => (string) file_get_contents( $dir. '/design/'. $variant. '.css' ), array_keys( $looks ) ) ) ) ) !== 1 );

// The mat is a ground of its own: what is drawn on it takes its text colour, and its line - the roles of the ground - are the ground's, not the section's
$matCell = array_values( array_filter( $looks['mat']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-gallery-grid > .nino-gallery-cell' ) );
$matMade = array_column( galleryDeclarations( $matCell[0]['body'] ?? '' ), 'value', 'property' );

check( 'Mat paints the cell: the ground of the knob, its line, the corners of the knob and room round the picture, the colour of the text the ground brings - on the ring of the focused link, which stands on the mat - and the picture inside has the small corners of the page',
	count( $matCell ) === 1 && ( $matMade['background-color'] ?? '' ) === 'var(--gallery-ground)' && ( $matMade['color'] ?? '' ) === 'var(--gallery-ground-on)' && str_contains( (string) ( $matMade['border'] ?? '' ), 'var(--gallery-ground-border)' ) === true
	&& ( $matMade['border-radius'] ?? '' ) === 'var(--gallery-corners)' && preg_match( '/^var\(--space-[1-6]\)$/', (string) ( $matMade['padding'] ?? '' ) ) === 1
	&& array_filter( $looks['mat']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-gallery-cell > .nino-gallery-link' && ( array_column( galleryDeclarations( $rule['body'] ), 'value', 'property' )['border-radius'] ?? '' ) === 'var(--radius-small)' ) !== [] );

// --- The block: six pictures as the template writes them, in three columns
$block 			= (string) @file_get_contents( $design. '/preview.tpl' );
$blockIds 	= [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

preg_match_all( '/<img\b[^>]*>/', $block, $blockImages );
preg_match_all( '/<li class="nino-gallery-cell">(.*?)<\/li>/s', $block, $blockCells );
preg_match( '/<ul class="nino-gallery-grid"[^>]*style="([^"]*)"/', $block, $blockList );

// The shape of one item as the template has it, the fills made static: the preview's cell is that, so that the specimen draws what the shortcode draws
$itemOfBlock = static fn( string $cell ): string => (string) preg_replace( '/ id="[^"]*"/', '', $cell );
$cellShape	 = static fn( string $markup ): string => (string) preg_replace( [ '/ (?:src|alt|width|height|href|data-lightbox|data-caption)="[^"]*"/', '/ loading="lazy" decoding="async"/' ], '', $markup );
$templateItem = (string) file_get_contents( $dir. '/templates/gallery-item.tpl' );

check( 'the feature brings a block to the specimen, design/preview.tpl: one grid row, every id in it gallery-something and each once, no section of its own and no inline handler',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $block ), '</div>' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'gallery-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1 );
check( '...the grid is the shortcode\'s: one list with three columns and the size the thumbnails are cropped to as the properties the stylesheet reads, and six cells, each of them a link to nothing - # - round the frame of the hover and a picture of the preview\'s placeholder, which has nothing to say and so an empty alt, with the width and the height the template writes - the lightbox does not load there, so no link carries a group',
	substr_count( $block, '<ul class="nino-gallery-grid"' ) === 1 && count( $blockCells[0] ) === 6 && count( $blockImages[0] ) === 6
	&& str_contains( (string) ( $blockList[1] ?? '' ), '--nino-gallery-columns:3' ) === true && preg_match( '/--nino-gallery-thumb-w:\d+;--nino-gallery-thumb-h:\d+/', (string) ( $blockList[1] ?? '' ) ) === 1
	&& array_filter( $blockImages[0], static fn( string $image ): bool => str_contains( $image, 'src="[[placeholder]]"' ) === false || preg_match( '/\swidth="\d+" height="\d+"/', $image ) !== 1 ) === []
	&& substr_count( $block, 'href="#"' ) === 6 && str_contains( $block, 'data-lightbox' ) === false && substr_count( $block, ' alt=""' ) === 6
	&& array_unique( array_map( static fn( string $cell ): string => $cellShape( $itemOfBlock( $cell ) ), $blockCells[0] ) ) === [ $cellShape( trim( $templateItem ) ) ] );

// The hover is asked for in the item the shortcode renders: the frame says the two classes of the kernel, and the picture is its child - which is what the kernel's rule is written for
$itemHtml = galleryPage( $appData, '[gallery album="trip"]' );
preg_match_all( '/<a class="nino-gallery-link"[^>]*>(<span class="([^"]*)">(<img [^>]*>)<\/span>)<\/a>/', $itemHtml, $frames, PREG_SET_ORDER );

check( 'the item the shortcode renders has the kernel\'s frame round the picture, inside its link: nino-image and nino-hover on one element - the rule of Nino.css reads both and the picture as its direct child - and the picture is the one with the width and the height',
	count( $frames ) === 2 && array_filter( $frames, static fn( array $frame ): bool => array_diff( [ 'nino-image', 'nino-hover' ], (array) preg_split( '/\s+/', $frame[2] ) ) !== [] || str_contains( $frame[3], 'class="nino-gallery-thumb"' ) === false || str_contains( $frame[3], 'width="' ) === false ) === []
	&& preg_match( '/\.nino-image\.nino-hover\s*>\s*img\s*\{/', $ninoCss ) === 1 && preg_match( '/a:focus-visible\s*>\s*\.nino-image\.nino-hover\b/', $ninoCss ) === 1 );

// --- The manual
$manualProblems = [];

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = ( (array) ( $manifest['manual']['panel'] ?? [] ) )['Design: the looks of the gallery'][$locale] ?? null;

	if( is_string( $text ) === false || trim( $text ) === '' || max( array_map( 'strlen', explode( "\n", $text ) ) ) >= 1000 || str_contains( $text, 'nino-hover' ) === true )
		$manualProblems[] = $locale;
}

$hoverWords = [ 'en_US' => [ 'Page, Motion', 'kernel' ], 'de_DE' => [ 'Seite, Bewegung', 'Kernels' ] ];

foreach( $hoverWords as $locale => $words )
	foreach( $words as $word )
		if( str_contains( (string) ( ( (array) ( $manifest['manual']['panel'] ?? [] ) )['Design: the looks of the gallery'][$locale] ?? '' ), $word ) === false )
			$manualProblems[] = $locale. ' '. $word;

$frameLine = (array) ( $manifest['manual']['markup']['<span class="nino-gallery-frame nino-image nino-hover">'] ?? [] );
$propertyLine = (array) ( $manifest['manual']['markup']['--gallery-gap, --gallery-corners, --gallery-ground'] ?? [] );

foreach( [ 'en_US', 'de_DE' ] as $locale )
	foreach( [ '--gallery-gap', '--gallery-corners', '--gallery-ground', ':root' ] as $word )
		if( str_contains( (string) ( $propertyLine[$locale] ?? '' ), $word ) === false || strlen( (string) ( $propertyLine[$locale] ?? '' ) ) >= 1000 )
			$manualProblems[] = $locale. ' markup line '. $word;

check( 'the manual names the looks, the knobs and the hover under panel - the hover as the kernel\'s, set under Page, Motion - and the frame of the picture under markup, in both languages, every line under 1000 bytes'. ( $manualProblems === [] ? '' : ' - '. implode( ', ', $manualProblems ) ),
	$manualProblems === [] && trim( (string) ( $frameLine['en_US'] ?? '' ) ) !== '' && trim( (string) ( $frameLine['de_DE'] ?? '' ) ) !== '' && strlen( (string) $frameLine['en_US'] ) < 1000 && strlen( (string) $frameLine['de_DE'] ) < 1000 );

echo "\n";


// --- ...run through the Design feature ---------------------------------------------

echo "design/ - as the Design feature reads and compiles it\n";

$designSetup			= '\\Nino\\Modules\\Design\\Setup';
$designCompiler		= '\\Nino\\Modules\\Design\\Compiler';
$designPreview		= '\\Nino\\Modules\\Design\\Preview';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the looks are not run through it here, bin/check.sh runs them with it in the checkout\n";
else {
	$library			= \Nino\Features::dir(). '/Design/library';
	$modulesKept	= (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept		= $appData['./nino/locales/current'];

	/** Which of the features are on: their classes are listed in /nino/modules, and what the kernel and the Design feature have read of the features is read again */
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Gallery', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Gallery', 'Design' ] );
	$appData['./nino/locales/current'] = 'en_US';

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['gallery'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its three looks, regular the one it has before anything is chosen, the anchor of its block - and has nothing to say of any file: no line dropped, no ladder missing',
		isset( $scan['gallery'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === array_keys( $looks ) && $feature['default'] === 'regular'
		&& $feature['anchor'] === 'feature-gallery' && $scanNotes === [] );

	$publishes = [];

	foreach( $catalogue as $variant => $look )
		$publishes[$variant] = array_map( static fn( array $knob ): array => [ $knob['kind'], $knob['tab'] ], $look['knobs'] );

	check( 'each look publishes the knobs it declares and no other, with the kind and the tab of its head: all three for Grid and for Mat, the ground alone for Seamless - and a scale by the ladder of its own name',
		$publishes['regular'] === $knobTable && $publishes['mat'] === $knobTable && $publishes['seamless'] === array_intersect_key( $knobTable, [ 'ground' => 1 ] )
		&& array_filter( [ 'regular', 'mat' ], static fn( string $variant ): bool => ( $catalogue[$variant]['knobs']['gap']['ladder'] ?? [] ) !== [ 'gap' ] || ( $catalogue[$variant]['knobs']['corners']['ladder'] ?? [] ) !== [ 'corners' ] ) === [] );

	// The block is the feature's, not a look's: Design makes the section round it, with the feature's anchor
	$blockTold		= [];
	$blockInside	= $designSetup::previewOf( (string) $feature['dir'], 'gallery', $blockTold );
	$blocks				= $designPreview::blocks( $appData );

	check( 'the block stands the check Design holds it to and the preview makes the section of it, with the anchor the scan names, before the footer',
		$blockInside === trim( $block ) && $blockInside !== '' && $blockTold === [] && array_keys( $blocks ) === [ 'gallery' ]
		&& preg_match( '~^<section id="feature-gallery" class="nino-section" aria-label="[^"]+">\s*(.*?)\s*</section>$~s', $blocks['gallery'] ?? '', $blockSection ) === 1 && str_contains( $blockSection[1] ?? '', 'id="gallery-grid"' ) === true
		&& substr_count( $blocks['gallery'], 'nino-gallery-cell' ) === 6 && str_contains( $blocks['gallery'], '[[placeholder]]' ) === false );

	$withNone	= $compile( [] );
	$featuresOn( [ 'Design' ] );
	$withOff	= $compile( [] );
	$featuresOn( [ 'Gallery', 'Design' ] );

	check( 'with no look chosen the feature compiles nothing: the sheet of a project that has the feature on is the sheet of one that has it off, byte for byte', $withNone === $withOff && str_contains( $withNone, 'feature gallery' ) === false );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'gallery' => [ 'set' => $variant ] ] ], $sheetNotes );
		$ladders		= [];

		foreach( $look['knobs'] as $name => $knob )
			if( $knob['kind'] === 'scale' ) {
				$ladders[] = $name. ' default';

				foreach( $knob['ladder'] ?? [] as $ladder )
					if( str_contains( $sheet, "\n\t--gallery-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'gallery', $ladder, 'default' ). ");\n" ) === false )
						$sheetProblems[] = $variant. ' selects no '. $ladder;
			}
			elseif( $knob['kind'] === 'ground' && str_contains( $sheet, "\n\t--gallery-". $name. ': var(--nino-'. $knob['default']. ");\n" ) === false )
				$sheetProblems[] = $variant. ' writes no ground '. $name;

		if( preg_match( '~^/\* ==== \d+\. feature gallery: '. preg_quote( $variant, '~' ). '(?: \((.*)\))? ==== \*/$~m', $sheet, $title ) !== 1 || ( $title[1] ?? '' ) !== implode( ', ', $ladders ) )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] )
			$sheetProblems[] = $variant. ' compiled with a note';
	}

	check( 'with a look chosen the sheet carries its section, its stylesheet as the file has it, and a line for every ladder of every knob it declares and for the ground, with every part the palette solved for it'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ),
		$sheetProblems === [] && $catalogue !== [] );

	$groundSheet = $compile( [ 'features' => [ 'gallery' => [ 'set' => 'mat', 'knobs' => [ 'ground' => 'dark' ] ] ] ] );

	check( 'a ground moved is written as the surface and every part of it - the colour, the text on it, the line - the properties the stylesheet reads',
		str_contains( $groundSheet, "\n\t--gallery-ground: var(--nino-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--gallery-ground-on: var(--nino-on-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--gallery-ground-border: var(--nino-dark-border);\n" ) === true );

	$appData['/nino/modules'] = $modulesKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	$appData['./nino/locales/current'] = $localeKept;
	ninoWarnings();
}

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

( static function( array $appData ): void {

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
		$before[$call] = galleryPage( $appData, $call );
	$nowhere = galleryPage( $appData, '[gallery album="nowhere"]' );

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

	/*	The registry as registered is what the feature reads when it runs, with no list asked for: another component of the project takes
		the options of an attribute from a method (the forms, the albums of another feature), and a boot that asked it here would ask it at
		the start of every request, for an answer that then stays the request's. The schema it registers again is the registered one	*/
	if( method_exists( \Nino\Modules\Components::class, 'registered' ) === false )
		echo "  note - this Nino's Components module has no registered(), and no attribute whose options are a method: the schema the feature reads is the registry as registered, which components() is there\n";
	else {
		if( class_exists( 'GalleryAskedProbe' ) === false ) {
			class GalleryAskedProbe {
				public static int $asked = 0;
				public static function pick( array &$appData, string $attribute ): array {
					self::$asked++;
					return [ '' => 'None', 'a' => 'A' ];
				}
			}
		}
		$asking = $appData;
		\Nino\Callbacks::removeCallbacks( $asking, '/nino/html/shortcode/gallery' );
		\Nino\Modules\Components::init( $asking );
		\Nino\Modules\Components::addComponent( $asking, 'asking', static fn( array &$appData, array $args ): string => '', [
			'label' => 'Asking', 'source' => 'none', 'by' => 'hello',
			'attributes' => [ 'pick' => [ 'type' => 'select', 'options' => '\GalleryAskedProbe::pick', 'default' => '' ] ],
		] );
		$askingRegistered = \Nino\Modules\Components::registered( $asking )['gallery'] ?? [];
		\Nino\Modules\Gallery::init( $asking );
		ninoWarnings();
		check( 'the feature reads the schema as registered: a component of another feature that takes its options from a method is not asked while it runs, and stays registered with the name of the method',
			GalleryAskedProbe::$asked === 0 && ( \Nino\Modules\Components::registered( $asking )['asking']['attributes']['pick']['options'] ?? null ) === '\GalleryAskedProbe::pick' );
		check( '...and the schema it registers again is the registered one but for the album: the select of the albums there are',
			$withoutAlbum( \Nino\Modules\Components::registered( $asking )['gallery'] ?? [] ) === $withoutAlbum( $askingRegistered ) && $askingRegistered !== []
			&& ( \Nino\Modules\Components::registered( $asking )['gallery']['attributes']['album']['type'] ?? null ) === 'select' && ( $askingRegistered['attributes']['album']['type'] ?? null ) === 'string' );
	}

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, galleryPage( $probe, $call ) === $before[$call] );

	check( 'the component counts as the shortcode does: the same album twice through the wrapper is two sets, a bare call the first album',
		galleryGroups( galleryPage( $probe, '[gallery album="trip"][gallery]' ) ) === [ 'gallery-trip' => 2, 'gallery-trip-2' => 2 ] );

	/*	The one call form that is not rendered as it was. A select reads a value that is not among its options
		as its default - which is how the wrapper reads every select - so a key no album has is no key at all
		to the select, and no key is the first album there is. The manifest's string, and the shortcode
		of a Nino without the Components module, draw nothing for it	*/
	check( 'a key that no album has draws nothing as the shortcode has it - and as the kernel registers the component from the manifest alone',
		$nowhere === '' && galleryPage( $manifestOnly, '[gallery album="nowhere"]' ) === '' );
	check( '...but the select the feature registers on top of it reads it for no key at all: the first album there is. The one form of the manual that does not render as it did',
		$before['[gallery]'] !== '' && galleryPage( $probe, '[gallery album="nowhere"]' ) === $before['[gallery]'] );

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
		&& count( \Nino\Callbacks::registered( $late, '/nino/html/shortcode/gallery' ) ) === 1 && galleryPage( $late, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] );

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
		galleryPage( $mine, '[gallery album="trip"]' ) === '<div class="project-gallery">trip</div>' );
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
		$before['[gallery]'] !== '' && galleryPage( $bare, '[gallery]' ) === $before['[gallery]'] && galleryPage( $bare, '[gallery album="trip"]' ) === $before['[gallery album="trip"]']
		&& galleryPage( $bare, '[gallery album="nowhere"]' ) === $before['[gallery]'] );
	check( '...registered once, by the module and with the feature\'s renderer: the feature owns the shortcode', count( \Nino\Callbacks::registered( $bare, '/nino/html/shortcode/gallery' ) ) === 1 && \Nino\Features::shortcodes( $bare, 'gallery' ) === [ 'gallery' ] );

	// A schema that declares no attribute at all: the renderer reads what is not there as what nobody wrote - a warning would end the request of a page that has the gallery
	[ $plain, $refused ] = $bootWith( 'without-attributes' );
	ninoWarnings();
	$plainHtml = galleryPage( $plain, '[gallery]' );
	check( 'a module that registers the feature\'s renderer under a schema without any attribute does not end the boot, and its gallery draws the first album in the columns the album has - with no warning about the arguments that are not there'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ),
		$refused === [] && $plainHtml === $before['[gallery]'] && $plainHtml !== '' && ninoWarnings() === [] && ( \Nino\Modules\Components::components( $plain )['gallery']['attributes'] ?? null ) === [] );

	[ $gone, $refused ] = $bootWith( 'default-album' );
	$goneAlbum = \Nino\Modules\Components::components( $gone )['gallery']['attributes']['album'] ?? [];
	check( 'a module whose album has a default of its own, a key that no album has, does not end the boot either'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ), $refused === [] );
	check( '...its album stays a string with that default, and the feature puts no select of the albums over it', ( $goneAlbum['type'] ?? null ) === 'string' && ( $goneAlbum['default'] ?? null ) === 'gone' && isset( $goneAlbum['options'] ) === false );
	check( '...a call that names an album draws it, and one that names none draws what the module made of it: the album with that key, which no album has, so nothing',
		galleryPage( $gone, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] && galleryPage( $gone, '[gallery]' ) === '' && $before['[gallery]'] !== '' );

	/*	The default of an album that exists is among the options a select of the albums would have, so the kernel would accept
		it: that the schema is valid does not make the album the manifest's. It stays the module's string - and does not turn
		into a select while the album is there and back into a string once it is deleted. The same goes for a select of the
		module's own, whose choices are its own	*/
	[ $trip, $refused ] = $bootWith( 'existing-album' );
	$tripAlbum = \Nino\Modules\Components::components( $trip )['gallery']['attributes']['album'] ?? [];
	check( 'a module whose album has the key of an album for its default keeps its string: the select of the albums would be valid, and is not offered over it'. ( $refused === [] ? '' : ' - '. implode( ' | ', $refused ) ),
		$refused === [] && ( $tripAlbum['type'] ?? null ) === 'string' && ( $tripAlbum['default'] ?? null ) === 'trip' && isset( $tripAlbum['options'] ) === false
		&& galleryPage( $trip, '[gallery]' ) === $before['[gallery album="trip"]'] && galleryPage( $trip, '[gallery album="nowhere"]' ) === '' );

	[ $curated, $refused ] = $bootWith( 'select-album' );
	check( 'a module whose album is a select of its own keeps its choices: the albums of the project are not put in their place',
		$refused === [] && ( \Nino\Modules\Components::components( $curated )['gallery']['attributes']['album']['options'] ?? null ) === [ '', 'curated', 'trip' ]
		&& galleryPage( $curated, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] );

	// A change to anything else of the schema is the project's too, and the albums are offered all the same
	[ $labelled, $refused ] = $bootWith( 'label' );
	$labelledSchema = \Nino\Modules\Components::components( $labelled )['gallery'] ?? [];
	check( 'a module that changes something else of the schema, its label, and keeps the album gets the select of the albums over what it registered: its label stays',
		$refused === [] && ( $labelledSchema['label'] ?? null ) === 'Project gallery' && ( $labelledSchema['attributes']['album']['type'] ?? null ) === 'select'
		&& ( $labelledSchema['attributes']['album']['options'] ?? null ) === [ '', 'trip' ] && galleryPage( $labelled, '[gallery album="trip"]' ) === $before['[gallery album="trip"]'] );

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
		check( 'renders as it did with the setting at 6: '. $call, galleryPage( $probe, $call ) === galleryPage( $plain, $call ) );
	check( 'without a count of columns the setting\'s is taken by an album that holds none of its own, which a default of 4 in the schema would have overridden - and a count of its own still wins',
		str_contains( galleryPage( $probe, '[gallery album="trip"]' ), '--nino-gallery-columns:6' ) === true
		&& str_contains( galleryPage( $probe, '[gallery album="trip" columns="3"]' ), '--nino-gallery-columns:3' ) === true
		&& str_contains( galleryPage( $probe, '[gallery album="trip" columns="9"]' ), '--nino-gallery-columns:6' ) === true );
	// The one form the wrapper cannot tell from an omitted attribute
	check( '...and an explicit columns="" is that omitted attribute, as the wrapper hands it on',
		str_contains( galleryPage( $probe, '[gallery album="trip" columns=""]' ), '--nino-gallery-columns:6' ) === true );

	// An album that has a count of its own: the call that sets none takes it - the wrapper hands the empty
	// attribute on as none - and not the setting of the feature, which is 6 here
	$stored[0]['settings']['columns'] = 3;
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
	check( 'an album with a count of columns of its own draws it when the call sets none - through the wrapper and as a plain call - and not the feature\'s 6',
		str_contains( galleryPage( $probe, '[gallery album="trip"]' ), '--nino-gallery-columns:3' ) === true && str_contains( galleryPage( $probe, '[gallery album="trip" columns=""]' ), '--nino-gallery-columns:3' ) === true
		&& str_contains( galleryPage( $plain, '[gallery album="trip"]' ), '--nino-gallery-columns:3' ) === true );
	check( '...the call\'s own count has the last word, and a count that is none - 9 here - is the album\'s, through the wrapper and as a plain call: 3, and not the 4 the code once made of it',
		str_contains( galleryPage( $probe, '[gallery album="trip" columns="2"]' ), '--nino-gallery-columns:2' ) === true && str_contains( galleryPage( $probe, '[gallery album="trip" columns="9"]' ), '--nino-gallery-columns:3' ) === true
		&& str_contains( galleryPage( $plain, '[gallery album="trip" columns="x"]' ), '--nino-gallery-columns:3' ) === true );
	// The album as it was. The copies of the project's data read a file by its time and its size, and a file
	// written twice within a second to the same size is the one they hold - so they are told to read it again
	$stored[0]['settings']['columns'] = 4;
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $stored );
	unset( $probe['./nino/filesystem/cache'][ \Nino\Modules\Gallery::ALBUMS ], $plain['./nino/filesystem/cache'][ \Nino\Modules\Gallery::ALBUMS ] );
	unset( $probe[ \Nino\Features::STATE_KEY ]['gallery']['settings'] );
	check( 'every attribute written out at its default is the call without it',
		galleryPage( $probe, '[gallery album="" columns="" class=""]' ) === $before['[gallery]'] );
	check( 'a class of its own comes after the grid\'s own, no class leaves no space behind',
		str_contains( galleryPage( $probe, '[gallery album="trip" class="trip"]' ), '<ul class="nino-gallery-grid trip" style="--nino-gallery-columns:4;--nino-gallery-thumb-w:200;--nino-gallery-thumb-h:200">' ) === true
		&& str_contains( $before['[gallery album="trip"]'], '<ul class="nino-gallery-grid" style="--nino-gallery-columns:4;--nino-gallery-thumb-w:200;--nino-gallery-thumb-h:200">' ) === true );

} )( $appData );

echo "\n";


// --- A change another request made meanwhile ---------------------------------

echo "Gallery\\Admin - the albums are changed under the lock of their file\n";

/*	The site and a second screen write the file the albums are in, and an action
	that read it, changed what it had read and wrote that whole took back what was
	written in between. Another request is a file written behind this one's back,
	of the same size and the same time, so that the copy Filesystem keeps of it
	for this request cannot tell: what an action reads before it locks the file is
	the old state, what it reads under the lock is the file	*/
$stage = static function( array &$appData, string $from, string $to ): bool {

	$path = \Nino\Filesystem::path( $appData, \Nino\Modules\Gallery::ALBUMS );

	// This request reads the albums...
	\Nino\Modules\Gallery::albums( $appData );
	$time = (int) filemtime( $path );

	// ...another one renames the album, to a name of the same length, and puts the time back
	file_put_contents( $path, str_replace( "'name' => '$from'", "'name' => '$to'", (string) file_get_contents( $path ) ) );
	touch( $path, $time );
	clearstatcache( true, $path );

	if( function_exists( 'opcache_invalidate' ) === true )
		opcache_invalidate( $path, true );

	return ( \Nino\Modules\Gallery::album( $appData, 'trip' )['name'] ?? '' ) === $from;
};
$nameInFile = static function( array &$appData ): string {
	$stored = @include \Nino\Filesystem::path( $appData, \Nino\Modules\Gallery::ALBUMS );
	return (string) ( array_column( is_array( $stored ) === true ? $stored : [], 'name', 'key' )['trip'] ?? '' );
};

ninoCall( $appData, $admin, [ 'album' => 'trip', 'name' => 'name-a' ], 'gallery/album-save' );
ninoCall( $appData, $admin, [ 'album' => 'other', 'name' => 'Other', 'isNew' => true ], 'gallery/album-save' );
$tripIds = array_column( \Nino\Modules\Gallery::album( $appData, 'trip' )['images'] ?? [], 'id' );

// Each of the five actions that read the albums, changed and wrote them - and the one that already did it under the lock
$meanwhile = [
	'gallery/album-save'	=> [ 'album' => 'trip', 'settings' => [ 'columns' => 5 ] ],
	'gallery/image-save'	=> [ 'album' => 'trip', 'id' => $tripIds[0], 'locale' => 'en_US', 'caption' => 'Meanwhile' ],
	'gallery/reorder'		=> [ 'album' => 'trip', 'order' => array_reverse( $tripIds ) ],
	'gallery/upload'		=> [ 'album' => 'trip' ],
	'gallery/image-delete'	=> [ 'album' => 'trip', 'id' => $tripIds[1] ],
	'gallery/album-delete'	=> [ 'album' => 'other' ],
];

$name = 'name-a';

foreach( $meanwhile as $action => $post ) {

	$next		= 'name-'. chr( ord( $name[5] ) + 1 );
	$staged	= $stage( $appData, $name, $next );

	// The upload is the one with a file: the same appData runs it, so the copy of the albums it holds is the old one
	if( $action === 'gallery/upload' )
		[ $status ] = withUpload( $wide, static function() use ( &$appData, $admin, $post, $action ): array {
			return ninoCall( $appData, $admin, $post, $action );
		} );
	else
		[ $status ] = ninoCall( $appData, $admin, $post, $action );

	$name = $next;

	check( "$action keeps the change another request made after this one had read the albums: the file holds that request's name", $staged === true && $status === 200 && $nameInFile( $appData ) === $name );
}

// The same without an action: what change() is handed is the albums as the file holds them, and a callback that answers null leaves the file as it is
$staged = $stage( $appData, $name, 'name-z' );
$seen = null;
$left = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( &$seen ): ?array {
	$seen = array_column( $albums, 'name', 'key' );
	return null;
} );
check( 'change() hands the callback the albums as the file holds them under the lock, not as this request read them - and writes nothing for null',
	$staged === true && $left === false && ( $seen['trip'] ?? '' ) === 'name-z' && $nameInFile( $appData ) === 'name-z' );

$kept = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, [ [ 'key' => 'one', 'name' => 'One', 'images' => [] ], [ 'key' => 'Not A Key' ], 'not an album', [ 'key' => 'two', 'name' => 'Two' ] ] );
check( 'it is handed valid albums only, and writes the ones it answers - the list renumbered, nothing else of the file kept',
	\Nino\Modules\Gallery::change( $appData, static fn( array $albums ): array => array_reverse( $albums ) ) === true
	&& array_column( (array) @include \Nino\Filesystem::path( $appData, \Nino\Modules\Gallery::ALBUMS ), 'key' ) === [ 'two', 'one' ] );
check( '...and a file that cannot be locked is not written: change() says so', ( static function() use ( &$appData ): bool {
	$locks = \Nino\Filesystem::path( $appData, '/data/.locks' );
	$aside = $locks. '-aside';
	$had = file_exists( $locks );
	if( $had === true )
		rename( $locks, $aside );
	file_put_contents( $locks, 'not a directory' );
	ninoWarnings();
	$ran = false;
	$wrote = \Nino\Modules\Gallery::change( $appData, static function( array $albums ) use ( &$ran ): array {
		$ran = true;
		return $albums;
	} );
	ninoWarnings();
	unlink( $locks );
	if( $had === true )
		rename( $aside, $locks );
	return $wrote === false && $ran === false;
} )() );

// Back to the albums the rest of the test is about
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Gallery::ALBUMS, $kept );

echo "\n";


// --- Deleting an album -------------------------------------------------------

echo "Gallery\\Admin - deleting an album\n";

$before = count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [] );
[ $status, $body ] = ninoCall( $appData, $admin, [ 'album' => 'trip' ], 'gallery/album-delete' );
$after = count( glob( \Nino\Filesystem::path( $appData, '/images/gallery/trip' ). '/*' ) ?: [] );

// Unlike a form's submissions, an album's images are the album: nothing else
// points at them, and leaving them would leave files nobody can reach again
check( 'the album goes, and every picture in it', $status === 200 && galleryAlbums( $body ) === [] && $before > 0 && $after === 0 );

[ $status ] = ninoCall( $appData, $admin, [ 'album' => 'trip' ], 'gallery/album-delete' );
check( 'deleting it twice is a refusal', $status === 400 );

// No album: the select has the empty choice alone, which is the default - and a page that names the component draws nothing
$empty = $appData;
foreach( [ 'gallery' ] as $shortcode )
	\Nino\Callbacks::removeCallbacks( $empty, '/nino/html/shortcode/'. $shortcode );
\Nino\Modules\Components::init( $empty );
\Nino\Modules\Gallery::init( $empty );
ninoWarnings();
check( 'with no album the select of the component has the empty choice and nothing else, and the page draws nothing for it',
	( \Nino\Modules\Components::components( $empty )['gallery']['attributes']['album']['options'] ?? null ) === [ '' ] && ( \Nino\Modules\Components::components( $empty )['gallery']['attributes']['album']['default'] ?? null ) === ''
	&& \Nino\Modules\Gallery::keys( $appData ) === [] && galleryPage( $empty, '[gallery]' ) === '' && galleryPage( $empty, '[gallery album="trip"]' ) === '' );

$appData['./nino/auth/current'] = null;
[ $status ] = ninoCall( $appData, $admin, [], 'gallery/list' );
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
