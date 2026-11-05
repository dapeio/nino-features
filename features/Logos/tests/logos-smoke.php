<?php
declare(strict_types=1);

/**
 *	Nino
 *	logos-smoke.php		Contract test for the Logos feature (Modules\Logos): the
 *										manifest, the element type the activation brings -
 *										add-only, a second activation changes nothing and a type
 *										the project has stays - the row the loop draws, a stack of
 *										the cell none that reads the fields its call names: the
 *										logos in the order the kernel's loop gives them, those
 *										without a picture left out, a link only for an address a
 *										link may have and only from a field there is, the second
 *										picture of a logo for a dark ground, the pictures set into
 *										their box and not cut, every value of an element escaped,
 *										a type that is none and the copy of the list a band runs
 *										behind the first one -,
 *										and the looks, the style and the block that the Design
 *										feature reads, as files and compiled through it.
 *
 *										Travels with the feature and runs against the checkout
 *										three levels up, or the one NINO_ROOT names (see
 *										tests/harness.php there).
 *
 *	Usage: php features/Logos/tests/logos-smoke.php
 *	       NINO_ROOT=../nino php features/Logos/tests/logos-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

if( extension_loaded( 'gd' ) === false ) {
	fwrite( STDERR, 'No gd extension here - the pictures of the logos are made with it'. "\n" );
	exit( 2 );
}

/** Raw png bytes for a solid-colour test image */
function logosImage( int $width, int $height ): string {
	$img = imagecreatetruecolor( $width, $height );
	imagefill( $img, 0, 0, imagecolorallocate( $img, 30, 90, 160 ) );
	ob_start();
	imagepng( $img );
	$bytes = ob_get_clean();
	imagedestroy( $img );
	return (string) $bytes;
}

/**
 *	A logo: the element, and the pictures of the fields it is given. The pictures go through the
 *	kernel's own fit, into the box the type declares, as an upload of the Elements panel does for a field that says fit
 *
 *	@param		array 		&$appData
 *	@param		string		$slug
 *	@param		array			$data					The fields of the element
 *	@param		array			$pictures			The image fields it has a picture for
 *	@param		int				$width				The width of the uploaded picture
 *	@param		int				$height			Its height
 *
 *	@return 	void
 */
function logosAdd( array &$appData, string $slug, array $data, array $pictures = [ 'image' ], int $width = 800, int $height = 300 ): void {

	\Nino\Elements::insertElement( $appData, '/logo/'. $slug, $data );

	$files = [];

	foreach( $pictures as $field )
		$files[$field] = \Nino\Images::fit( $appData, logosImage( $width, $height ), 540, 210, 'elements/logo/'. $slug. '-'. $field );

	if( $files !== [] )
		\Nino\Elements::updateElement( $appData, '/logo/'. $slug, $files );
}

/** The html of a page that holds the content, as a visitor's request renders it */
function logosPage( array &$appData, string $content ): string {
	return \Nino\Html::renderHtml( $appData, $content );
}

/** The two lists of a row: the logos, and the copy a band runs behind them - each as its inner html, '' for one that is not there */
function logosLists( string $html ): array {
	preg_match( '~<ul class="nino-logos-strip-list"[^>]*>(.*?)</ul>~s', $html, $list );
	preg_match( '~<ul class="nino-logos-strip-copy"[^>]*>(.*?)</ul>~s', $html, $copy );

	return [ $list[1] ?? '', $copy[1] ?? '' ];
}

/** How many logos a list holds */
function logosCount( string $list ): int {
	return substr_count( $list, '<li class="nino-logos-strip-item">' );
}

/** The names a list draws, as the alt text of its first picture, in the order they stand */
function logosNames( string $list ): array {
	preg_match_all( '~<li class="nino-logos-strip-item">.*?<img class="nino-logo-(?:light|dark) nino-logos-strip-img"[^>]*? alt="([^"]*)"~s', $list, $found );
	return $found[1];
}

/**
 *	The rules of a stylesheet, its comments left out: for each the at-rule it stands in ('' for none), its selector with the
 *	whitespace folded and the text of its body. One level of at-rule is read, which is as deep as the stylesheets here go
 *
 *	@param		string		$css
 *
 *	@return 	array<int, array{at: string, selector: string, body: string}>
 */
function logosRules( string $css ): array {

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
function logosDeclarations( string $body ): array {

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
function logosSheet( string $file ): array {

	$css	= (string) file_get_contents( $file );
	$tags	= [];

	preg_match( '#^\s*/\*(.*?)\*/#s', $css, $comment );

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 )
			$tags[ $found[1] ][] = $found[2];

	$sheet = [ 'tags' => $tags, 'rules' => logosRules( $css ), 'declared' => [] ];

	foreach( $sheet['rules'] as $rule )
		foreach( logosDeclarations( $rule['body'] ) as $declaration )
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
function logosColourLiterals( string $css ): array {

	$names = explode( ' ', 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen' );
	$literal = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(|(?<![\w-])(?:'. implode( '|', $names ). ')(?![\w-])/i';
	$found = [];

	foreach( logosRules( $css ) as $rule )
		foreach( logosDeclarations( $rule['body'] ) as $declaration )
			if( preg_match( $literal, (string) preg_replace( '/--[a-z0-9-]+/i', '', $declaration['value'] ), $match ) === 1 )
				$found[] = $declaration['property']. ': '. $match[0];

	return $found;
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir 			= dirname( __DIR__ );
$manifest	= \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "logos"', is_array( $manifest ) === true && $manifest['key'] === 'logos' && ninoWarnings() === [] );
check( 'it is written for this kernel, and names the 1.10 whose vocabulary it uses', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true && $manifest['nino'] === '^1.10' );
check( 'it names and describes itself in both interface languages', is_array( $manifest ) === true
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) && \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== '' );
check( 'it is filed under marketing, requires nothing, owns no file under data/ and has no setting - the logos are elements, and the look is the Design feature\'s',
	$manifest['category'] === 'marketing' && $manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );
check( 'it declares the row as a stack of the cell none and no component: the kernel brings the loop, the call names the fields',
	array_keys( $manifest['stacks'] ) === [ 'logos' ] && $manifest['components'] === [] && $manifest['stacks']['logos']['cell'] === 'none' && $manifest['stacks']['logos']['grid'] === false );
check( 'its stylesheet and its one script go into the site\'s bundle and there is no panel and no privacy section',
	$manifest['assets'] === [ '/features/Logos/assets/logos.css', '/features/Logos/assets/logos.js' ] && is_file( $dir. '/assets/logos.css' ) === true && is_file( $dir. '/assets/logos.js' ) === true && is_dir( $dir. '/Admin' ) === false
	&& is_file( $dir. '/install/elements/privacy.php' ) === false );
check( 'the manual answers the six sections, the two languages and no line reaches 1000 bytes', array_keys( $manifest['manual'] ) === \Nino\Features::MANUAL_SECTIONS
	&& array_filter( $manifest['manual'], static function( array $section ): bool {
		foreach( $section as $line )
			foreach( [ 'en_US', 'de_DE' ] as $locale )
				if( is_array( $line ) === false || trim( (string) ( $line[$locale] ?? '' ) ) === '' || strlen( (string) $line[$locale] ) >= 1000 )
					return true;

		return false;
	} ) === [] );

$appData = ninoSandbox( 'logos' );
$appData['/nino/dir'] = '';
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Components' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

$typeFile = \Nino\Filesystem::path( $appData, '/elements/logo.php' );

check( 'there is no type before the activation', is_file( $typeFile ) === false );
check( 'activation lists the class, records the version and brings the type', \Nino\Features::activate( $appData, 'logos' ) === true
	&& \Nino\Features::get( $appData, 'logos' )['installed'] === '1.0.0' && in_array( '\\Nino\\Modules\\Logos', (array) $appData['/nino/modules'], true ) === true && is_file( $typeFile ) === true );

$type = \Nino\Filesystem::getFileContent( $appData, '/elements/logo.php', [] );

check( 'the type has the five fields: a name, a picture, an address, a picture for a dark ground and a position, the pictures set into the box a row of logos is made for - fit, not cut - and no element',
	array_keys( (array) ( $type['model'] ?? [] ) ) === [ 'name', 'image', 'url', 'image-dark', 'order' ] && $type['model']['order'] === [ 'type' => 'integer' ] && ( $type['model']['name']['html'] ?? false ) !== true && $type['model']['name']['required'] === true
	&& $type['model']['image'] === [ 'type' => 'image', 'width' => 540, 'height' => 210, 'fit' => true ] && $type['model']['image-dark'] === $type['model']['image']
	&& ( $type['model']['image']['required'] ?? false ) !== true && $type['model']['url']['type'] === 'string' && \Nino\Elements::queryElements( $appData, '/logo', [], '', [] ) === [] );

$before = (string) file_get_contents( $typeFile );
file_put_contents( $typeFile, str_replace( "'title' => 'Logos'", "'title' => 'Customers'", $before ) );
check( 'a second activation changes nothing: the type the project edited stays as it is', \Nino\Features::activate( $appData, 'logos' ) === true
	&& str_contains( (string) file_get_contents( $typeFile ), "'title' => 'Customers'" ) === true );

$labels	= array_map( static fn( string $field ): string => '[[/_admin/elements/field/logo/'. $field. ']]', array_keys( $type['model'] ) );
$words	= [ '[[/feature/logos/action/pause]]' ];
$unit		= require $dir. '/install/manifest.php';
$landed	= [];

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$have = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );

	foreach( array_merge( $labels, $words ) as $key )
		if( trim( (string) ( $have[$key] ?? '' ) ) === '' )
			$landed[] = $locale. ' '. $key;
}

$enLabels = \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
$deLabels = \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] );

check( 'the activation puts a label for every field of the type and the word of the pause button into both languages'. ( $landed === [] ? '' : ' - '. implode( ', ', $landed ) ), $landed === [] && count( $labels ) === 5 );
check( 'the label of the address names the schemes and the path, and says that empty is no link, in both languages; the position says smaller first and empty last',
	str_contains( $enLabels[$labels[2]], 'a path of the website like /contact' ) === true && str_contains( $enLabels[$labels[2]], 'empty: no link' ) === true
	&& str_contains( $deLabels[$labels[2]], 'ein Pfad der Website wie /kontakt' ) === true && str_contains( $deLabels[$labels[2]], 'leer: kein Link' ) === true
	&& str_contains( $enLabels[$labels[4]], '10, 20' ) === true && str_contains( $enLabels[$labels[4]], 'empty goes last' ) === true && str_contains( $deLabels[$labels[4]], 'leer kommt ans Ende' ) === true
	&& str_contains( $enLabels[$labels[4]], '0 goes' ) === false && str_contains( $deLabels[$labels[4]], 'oder 0' ) === false );
check( 'the unit blacklists the labels - the workbench\'s words, not the site\'s - and not the word of the button, which the site keeps in the Text panel, without a quote in it',
	$unit['blacklist'] === array_map( static fn( string $key ): string => trim( $key, '[]' ), $labels )
	&& in_array( '/feature/logos/action/pause', (array) \Nino\Filesystem::getFileContent( $appData, '/text/blacklist.php', [] ), true ) === false
	&& in_array( '/_admin/elements/field/logo/order', (array) \Nino\Filesystem::getFileContent( $appData, '/text/blacklist.php', [] ), true ) === true
	&& str_contains( $enLabels[$words[0]]. $deLabels[$words[0]], '"' ) === false
	&& isset( $enLabels['[[/feature/logos/action/play]]'] ) === false && isset( $deLabels['[[/feature/logos/action/play]]'] ) === false );

$own = ninoSandbox( 'logos-own' );
$own['/nino/modules'] = [ '\\Nino\\Modules\\Components' ];
\Nino\AppData::writeContentData( $own, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
\Nino\Filesystem::forceDir( $own, '/elements' );
file_put_contents( \Nino\Filesystem::path( $own, '/elements/logo.php' ), "<?php return [ 'title' => 'Mine', 'model' => [ 'name' => [ 'type' => 'string' ] ] ];\n" );
check( 'a type the project has is kept, with its own model', \Nino\Features::activate( $own, 'logos' ) === true
	&& array_keys( \Nino\Elements::getElementModel( $own, '/logo' ) ) === [ 'name' ] );

echo "\n";


// --- The row --------------------------------------------------------------------

echo "[logos] - the row the loop draws\n";

\Nino\Html::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
ninoWarnings();

// Not in the order of their names, so that the order of the elements, a sort by name and the position are three different things: Alpha has the
// position 9 and Mid 10, which sort that way as numbers and the other way as text, Zero has 0, and the others have none
logosAdd( $appData, 'zeta', [ 'name' => 'Zeta & Co', 'url' => 'https://zeta.example/?a=1&b=2' ] );
logosAdd( $appData, 'alpha', [ 'name' => 'Alpha', 'url' => '/about', 'order' => 9 ], [ 'image', 'image-dark' ] );
logosAdd( $appData, 'mid', [ 'name' => 'Mid', 'url' => 'mailto:hello@example.com', 'order' => 10 ] );
logosAdd( $appData, 'zero', [ 'name' => 'Zero', 'order' => 0 ] );
logosAdd( $appData, 'bare', [ 'name' => 'Bare' ], [] );
logosAdd( $appData, 'nolink', [ 'name' => 'No link' ] );
logosAdd( $appData, 'script', [ 'name' => 'Script', 'url' => 'javascript:alert(1)' ] );
logosAdd( $appData, 'relative', [ 'name' => 'Relative', 'url' => '//evil.example/x' ] );
logosAdd( $appData, 'dark', [ 'name' => 'Only dark' ], [ 'image-dark' ] );
logosAdd( $appData, 'ill', [ 'name' => '<b>Bold</b> [[/project/company/general/name]] [x] "q"', 'url' => 'https://ill.example/"onmouseover="x' ] );

// The call the manual recommends, and the same with the second picture
$call			= '[logos /logo image="image" link="url" sort="order"]';
$callDark	= '[logos /logo image="image" link="url" name="name" dark="image-dark" sort="order"]';
$plain		= logosPage( $appData, $call );
$all			= logosPage( $appData, $callDark );
[ $list, $copy ] = logosLists( $all );
$illName	= '&lt;b&gt;Bold&lt;/b&gt; &#91;&#91;/project/company/general/name]] &#91;x] &quot;q&quot;';
$inOrderElements = [ 'Zeta &amp; Co', 'Alpha', 'Mid', 'Zero', 'No link', 'Script', 'Relative', 'Only dark', $illName ];
$inOrder	= [ 'Zero', 'Alpha', 'Mid', 'Zeta &amp; Co', 'No link', 'Script', 'Relative', 'Only dark', $illName ];

check( 'the row is a div of the class nino-logos-strip, the height m and the number of logos on its style, with the word of the pause button, the list and the copy of it in a view of their own, and the pause button - hidden, for the script to show',
	preg_match( '~^<div class="nino-logos-strip nino-logos-strip--h-m" style="--nino-logos-count:9" data-logos-pause="[^"[]+">\s*<div class="nino-logos-strip-view">\s*<ul class="nino-logos-strip-list" role="list">.*</ul>\s*<ul class="nino-logos-strip-copy" aria-hidden="true">.*</ul>\s*</div>\s*<button type="button" class="nino-btn nino-btn--outline nino-btn--small nino-logos-strip-pause" aria-pressed="false" hidden></button>\s*</div>$~s', $all ) === 1 && ninoWarnings() === [] );
check( 'a logo is a list item with its picture in the kernel\'s logo set - the picture of the field the call names as image is nino-logo-light - with the size of the file the upload stored, a lazy load and the name as its alt text',
	str_contains( $list, '<li class="nino-logos-strip-item"><a class="nino-logos-strip-link" href="/about" rel="noopener"><span class="nino-logo-set"><img class="nino-logo-light nino-logos-strip-img" src="/public/images/elements/logo/alpha-image.fit540x210.webp" width="540" height="203" alt="Alpha" loading="lazy" decoding="async">' ) === true );
check( 'the picture of the field the call names as dark stands beside it in the same set, as nino-logo-dark, for the ground to choose',
	preg_match( '~<span class="nino-logo-set"><img class="nino-logo-light nino-logos-strip-img"[^>]*alt="Alpha"[^>]*><img class="nino-logo-dark nino-logos-strip-img" src="[^"]*alpha-image-dark\.fit540x210\.webp" width="540" height="203" alt="Alpha" loading="lazy" decoding="async"></span>~', $list ) === 1 );
check( 'a logo with one picture only draws one: nothing for the kernel to choose between', substr_count( $list, '<img' ) === 10 && substr_count( $list, 'nino-logo-dark' ) === 2 );
check( 'a call that leaves dark out draws the second picture from image-dark, where the element has one - and the logo that has only that one, which is seen on every ground',
	logosNames( logosLists( $plain )[0] ) === $inOrder && substr_count( logosLists( $plain )[0], 'nino-logo-dark' ) === 2 && substr_count( logosLists( $plain )[0], 'nino-logo-light' ) === 8
	&& $plain === $all );
$noDark = logosPage( $appData, '[logos /logo image="image" link="url" dark="" sort="order"]' );
check( 'dark="" is no field: no second picture for any logo, no fallback to image-dark, and the logo that has only the picture for a dark ground has no picture and is left out',
	str_contains( $noDark, 'nino-logo-dark' ) === false && substr_count( logosLists( $noDark )[0], '<img' ) === 8 && logosCount( logosLists( $noDark )[0] ) === 8 && str_contains( $noDark, 'Only dark' ) === false
	&& str_contains( $noDark, 'alpha-image-dark' ) === false );
$noName = logosPage( $appData, '[logos /logo image="image" link="url" name="" sort="order"]' );
check( 'name="" is no field: the pictures have an empty alt text and no other field is read in its place',
	substr_count( logosLists( $noName )[0], ' alt=""' ) === 10 && str_contains( $noName, 'alt="Alpha"' ) === false && logosCount( logosLists( $noName )[0] ) === 9 );
check( 'a dark that names the field of image is no second picture: one picture for each logo, not the same one twice',
	substr_count( logosLists( logosPage( $appData, '[logos /logo image="image" dark="image"]' ) )[0], '<img' ) === 8 && str_contains( logosPage( $appData, '[logos /logo image="image" dark="image"]' ), 'nino-logo-dark' ) === false
	&& substr_count( logosLists( logosPage( $appData, '[logos /logo image="image-dark"]' ) )[0], '<img' ) === 2 );

check( 'sort="order" is the kernel\'s sort: by the position, 9 before 10 as numbers, 0 a number before them, and the logos with no position after them in the order of the elements; a logo with no picture at all is left out, but not one that has the picture for a dark ground alone',
	logosNames( $list ) === $inOrder );
check( '...and the limit is applied after that order: a limit of 3 is the three with a position',
	logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="order" limit="3"]' ) )[0] ) === [ 'Zero', 'Alpha', 'Mid' ] && logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="order" limit="3"]' ) )[1] ) === [ 'Zero', 'Alpha', 'Mid' ] );
check( 'a call with no sort, and sort="", keeps the order of the elements',
	logosNames( logosLists( $plain = logosPage( $appData, '[logos /logo image="image" link="url"]' ) )[0] ) === [ 'Zeta &amp; Co', 'Alpha', 'Mid', 'Zero', 'No link', 'Script', 'Relative', 'Only dark', $illName ]
	&& logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" link="url" sort=""]' ) )[0] ) === logosNames( logosLists( $plain )[0] ) );
check( 'a sort the call names is the kernel\'s: -order is descending, with the logos that have none last in either direction',
	array_slice( logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="-order"]' ) )[0] ), 0, 4 ) === [ 'Mid', 'Alpha', 'Zero', 'Zeta &amp; Co' ] );

preg_match_all( '~<a class="nino-logos-strip-link" href="([^"]*)"~', $list, $hrefs );

check( 'a link is drawn for an https address - escaped, an ampersand once, a quote as &quot; - a path of the site and a mailto address, and for no other: javascript: and a protocol-relative address have none',
	$hrefs[1] === [ '/about', 'mailto:hello@example.com', 'https://zeta.example/?a=1&amp;b=2', 'https://ill.example/&quot;onmouseover=&quot;x' ] && substr_count( $list, '<a ' ) === 4 && substr_count( $list, '<li ' ) === 9 );

$linkless = logosPage( $appData, '[logos /logo image="image" link="url" links="0"]' );

check( 'links="0" draws no link, whatever an address says - and with the link field left to its default url as well', logosCount( logosLists( $linkless )[0] ) === 9 && str_contains( $linkless, '<a ' ) === false && str_contains( $linkless, 'href=' ) === false
	&& str_contains( logosPage( $appData, '[logos /logo image="image" links="0"]' ), '<a ' ) === false && str_contains( logosPage( $appData, '[logos /logo image="image"]' ), '<a ' ) === true );
$defaultLink = logosPage( $appData, '[logos /logo image="image" sort="order"]' );
check( 'a call that leaves link out reads the field url, as the schema\'s default says: the same row as with link="url", and so does a value that is no field name; link="" is no field and draws no link',
	$defaultLink === logosPage( $appData, '[logos /logo image="image" link="url" sort="order"]' ) && substr_count( logosLists( $defaultLink )[0], '<a ' ) === 4
	&& logosPage( $appData, '[logos /logo image="image" link="/logo/zeta" sort="order"]' ) === $defaultLink
	&& str_contains( logosPage( $appData, '[logos /logo image="image" link="" sort="order"]' ), '<a ' ) === false
	&& logosCount( logosLists( logosPage( $appData, '[logos /logo image="image" link="" sort="order"]' ) )[0] ) === 9 );
check( 'the link is the field the call names, and none is a link where it names none: a call with link written empty, a field there is none of, a picture field and a field that holds no address draw no link',
	array_filter( [ '[logos /logo image="image" link=""]', '[logos /logo image="image" link="nofield"]', '[logos /logo image="image" link="image-dark"]', '[logos /logo image="image" link="name"]' ],
		static fn( string $call ): bool => str_contains( logosPage( $appData, $call ), '<a ' ) === true || logosCount( logosLists( logosPage( $appData, $call ) )[0] ) !== 9 ) === [] );
check( 'every value of an element is escaped and no bracket of it is left to open a fill or a shortcode on the next pass: the markup of a name is text, "[[" is "&#91;&#91;" and the quote of an address ends nothing',
	str_contains( $all, '<b>' ) === false && str_contains( $all, '[[/project' ) === false && str_contains( $all, '[' ) === false && preg_match( '/\sonmouseover\s*=/', $all ) !== 1 );

$copyNames = logosNames( $copy );

check( 'the copy is the same logos in the same order, each link out of the order of the Tab key and none of it a stop a keyboard can reach that the first list has not',
	logosCount( $copy ) === 9 && $copyNames === logosNames( $list ) && substr_count( $copy, '<a class="nino-logos-strip-link"' ) === 4 && substr_count( $copy, 'tabindex="-1"' ) === 4
	&& str_contains( $list, 'tabindex' ) === false && str_contains( $all, '<ul class="nino-logos-strip-copy" aria-hidden="true">' ) === true );

check( 'the heights are the class of the row: s, m and l, and anything else is m',
	array_map( static fn( string $height ): bool => str_contains( logosPage( $appData, '[logos /logo image="image" height="'. $height. '"]' ), 'nino-logos-strip--h-'. ( in_array( $height, [ 's', 'm', 'l' ], true ) === true ? $height : 'm' ). '"' ), [ 's', 'm', 'l', 'xl', '', 'L' ] ) === array_fill( 0, 6, true ) );
check( 'limit cuts the list, the copy with it, and 0 and a number that is none draw all of them', array_map( static fn( string $limit ): int => logosCount( logosLists( logosPage( $appData, '[logos /logo image="image" dark="image-dark" limit="'. $limit. '"]' ) )[0] ), [ '2', '1', '0', '-4', 'many', '9000' ] ) === [ 2, 1, 9, 9, 9, 9 ]
	&& logosCount( logosLists( logosPage( $appData, '[logos /logo image="image" limit="3"]' ) )[1] ) === 3 && str_contains( logosPage( $appData, '[logos /logo image="image" limit="3"]' ), '--nino-logos-count:3' ) === true );
check( 'sort is the sort of the loop of [elements]: by a field, and descending with a minus',
	( logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="name"]' ) )[0] )[0] === $illName ) === true
	&& logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="-name"]' ) )[0] )[0] === 'Zeta &amp; Co'
	&& logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="-name"]' ) )[0] ) === array_reverse( logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="name"]' ) )[0] ) ) );
check( 'the other arguments of the kernel\'s loop are its own: offset skips, query asks, and the type is the first argument of the call',
	logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" sort="order" offset="1" limit="2"]' ) )[0] ) === [ 'Alpha', 'Mid' ]
	&& logosNames( logosLists( logosPage( $appData, '[logos /logo image="image" query="name=Mid"]' ) )[0] ) === [ 'Mid' ] );
check( 'the pause button\'s word is the text key of the unit, resolved in the language of the page, English and German - and there is no second word for a state: that is aria-pressed',
	( static function( array &$appData ): bool {
		$kept = $appData['./nino/locales/current'];
		$said = [];

		foreach( [ 'en_US' => 'Pause logos', 'de_DE' => 'Logos anhalten' ] as $locale => $expected ) {
			$appData['./nino/locales/current'] = $locale;
			$said[$locale] = str_contains( logosPage( $appData, '[logos /logo image="image" limit="1"]' ), 'data-logos-pause="'. $expected. '">' ) && str_contains( logosPage( $appData, '[logos /logo image="image" limit="1"]' ), 'data-logos-play' ) === false;
		}

		$appData['./nino/locales/current'] = $kept;

		return $said === [ 'en_US' => true, 'de_DE' => true ];
	} )( $appData ) );
check( 'a class of the call is added to the classes of the row, escaped', str_contains( logosPage( $appData, '[logos /logo image="image" class="my-class nino-mb-3"]' ), 'nino-logos-strip--h-m my-class nino-mb-3" style=' ) === true
	&& str_contains( logosPage( $appData, '[logos /logo image="image" class="x&y"]' ), 'nino-logos-strip--h-m x&amp;y" style=' ) === true );

// --- The fields the call names
$names = static fn( string $call ): array => logosNames( logosLists( logosPage( $appData, $call ) )[0] );

check( 'the picture is the field the call names: the one for a dark ground, drawn as the picture of the logo, leaves out the logos that have none in it',
	$names( '[logos /logo image="image-dark" sort="order"]' ) === [ 'Alpha', 'Only dark' ] && substr_count( logosLists( logosPage( $appData, '[logos /logo image="image-dark" sort="order"]' ) )[0], 'alpha-image-dark.fit540x210' ) === 1 );
check( 'an image that is no picture field of the type draws nothing - a field of text, a field there is none of, the name of a slot - and the name of a slot is no field name: it reads as the default, and the slot is not looked up',
	array_filter( [ 'name', 'url', 'nofield', 'order' ], static fn( string $field ): bool => logosPage( $appData, '[logos /logo image="'. $field. '" dark="image-dark" link="url"]' ) !== '' ) === []
	&& logosPage( $appData, '[logos /logo image="/logo" sort="order"]' ) === logosPage( $appData, '[logos /logo sort="order"]' ) && logosPage( $appData, '[logos /logo sort="order"]' ) !== '' );
check( 'a dark that is no picture field of the type is no second picture: a field of text and a field there is none of - and a logo with only that picture is left out with it',
	array_filter( [ 'name', 'nofield', 'url' ], static fn( string $field ): bool => str_contains( logosPage( $appData, '[logos /logo image="image" dark="'. $field. '"]' ), 'nino-logo-dark' ) === true ) === []
	&& $names( '[logos /logo image="image" dark="name"]' ) === array_values( array_diff( $inOrderElements, [ 'Only dark' ] ) ) );
check( 'the alt text is the field the call names for the name: another field, and none where it names a field there is none of or a picture - no field is read in its place',
	str_contains( logosPage( $appData, '[logos /logo image="image" name="url" query="name=Alpha"]' ), ' alt="/about" ' ) === true
	&& str_contains( logosPage( $appData, '[logos /logo image="image" name="nofield" query="name=Alpha"]' ), ' alt="" ' ) === true && str_contains( logosPage( $appData, '[logos /logo image="image" name="nofield" query="name=Alpha"]' ), 'Alpha"' ) === false
	&& str_contains( logosPage( $appData, '[logos /logo image="image" name="image" query="name=Alpha"]' ), ' alt="" ' ) === true
	&& substr_count( logosPage( $appData, '[logos /logo image="image" name="nofield"]' ), ' alt=""' ) === 10 * 2 );

check( 'the call is a single tag: a pair is the same row, what stands between its tags is not read, and what stands around the tag stays',
	logosPage( $appData, $call. 'x[button z][/logos]' ) === logosPage( $appData, $call ) && str_contains( logosPage( $appData, $call. 'x[button z][/logos]' ), 'nino-btn nino-btn--outline nino-btn--small nino-logos-strip-pause' ) === true
	&& substr_count( logosPage( $appData, $call. 'x[button z][/logos]' ), '<button' ) === 1
	&& str_starts_with( logosPage( $appData, '<p>a</p>'. $call. '<p>b</p>' ), '<p>a</p><div class="nino-logos-strip' ) === true && str_ends_with( logosPage( $appData, '<p>a</p>'. $call. '<p>b</p>' ), '</div><p>b</p>' ) === true );

// --- The pictures are set into their box, not cut to it
logosAdd( $appData, 'wide', [ 'name' => 'Wide' ], [ 'image' ], 800, 100 );
logosAdd( $appData, 'tall', [ 'name' => 'Tall' ], [ 'image' ], 100, 400 );
logosAdd( $appData, 'small', [ 'name' => 'Small' ], [ 'image' ], 100, 40 );

$sizes = [];

foreach( [ 'Wide', 'Tall', 'Small' ] as $fitted ) {
	$one = logosPage( $appData, '[logos /logo image="image" query="name='. $fitted. '"]' );
	preg_match( '~<img [^>]*src="/public/images/([^"]+)" width="(\d+)" height="(\d+)"~', $one, $img );
	$stored = isset( $img[1] ) === true ? \Nino\Images::dimensions( $appData, $img[1] ) : false;
	$sizes[$fitted] = $stored !== false && [ (int) $img[2], (int) $img[3] ] === [ $stored['width'], $stored['height'] ] ? $stored['width']. 'x'. $stored['height'] : 'off';
}

check( 'a wide logo, a tall one and a small one keep their proportions and every edge: the width and the height the picture is drawn with are those of the file the upload stored - the longest side takes the box, the small one is not scaled up - and not the box',
	$sizes === [ 'Wide' => '540x68', 'Tall' => '53x210', 'Small' => '100x40' ] );

// A key an element holds that its type has no field for - a type file edited by hand - is no field: the call cannot name it for a link or a name
\Nino\Filesystem::putFileContent( $appData, '/elements/strays.php', [ 'title' => 'Strays', 'model' => [ 'name' => [ 'type' => 'string' ], 'image' => [ 'type' => 'image', 'width' => 540, 'height' => 210, 'fit' => true ] ],
	'*' => [ 'one' => [ 'name' => 'Stray', 'site' => 'https://stray.example/', 'who' => 'Stray who', 'image' => \Nino\Images::fit( $appData, logosImage( 800, 300 ), 540, 210, 'elements/strays/one' ) ] ] ] );
$stray = logosPage( $appData, '[logos /strays image="image" link="site" name="who"]' );
check( 'a link or a name that is no field of the type is nothing, even where the element holds a value under that name: no link, no alt text, and no other field in its place',
	logosCount( logosLists( $stray )[0] ) === 1 && str_contains( $stray, '<a ' ) === false && str_contains( $stray, ' alt="" ' ) === true && str_contains( $stray, 'stray.example' ) === false && str_contains( $stray, 'Stray' ) === false
	&& str_contains( logosPage( $appData, '[logos /strays image="image" link="name"]' ), '<a ' ) === false && str_contains( logosPage( $appData, '[logos /strays image="image"]' ), ' alt="Stray" ' ) === true );

// A control character anywhere in an address - a tab in a path - is no link: a browser drops it and reads another address
$hrefImage = \Nino\Images::fit( $appData, logosImage( 800, 300 ), 540, 210, 'elements/hrefs/one' );
\Nino\Filesystem::putFileContent( $appData, '/elements/hrefs.php', [ 'title' => 'Hrefs', 'model' => [ 'name' => [ 'type' => 'string' ], 'image' => [ 'type' => 'image', 'width' => 540, 'height' => 210, 'fit' => true ], 'url' => [ 'type' => 'string' ] ],
	'*' => [ 'tab' => [ 'name' => 'Tab', 'url' => "/\t/evil.example", 'image' => $hrefImage ], 'ctl' => [ 'name' => 'Ctl', 'url' => "https://a.example/\x01b", 'image' => $hrefImage ], 'ok' => [ 'name' => 'Ok', 'url' => '/fine', 'image' => $hrefImage ] ] ] );
preg_match_all( '~<a class="nino-logos-strip-link" href="([^"]*)"~', logosLists( logosPage( $appData, '[logos /hrefs image="image" link="url"]' ) )[0], $controlled );
check( 'an address with a control character in it - a tab after the first slash, a byte below the space - draws no link, and the logos are drawn all the same',
	$controlled[1] === [ '/fine' ] && logosCount( logosLists( logosPage( $appData, '[logos /hrefs image="image" link="url"]' ) )[0] ) === 3 );

echo "\n";


// --- What draws nothing -----------------------------------------------------------

echo "[logos] - what draws nothing, and says nothing\n";

ninoWarnings();

\Nino\Filesystem::putFileContent( $appData, '/elements/empty.php', [ 'title' => 'Empty', 'model' => [ 'name' => [ 'type' => 'string' ], 'image' => [ 'type' => 'image', 'width' => 540, 'height' => 210 ] ], '*' => [ '*' => [] ] ] );
\Nino\Filesystem::putFileContent( $appData, '/elements/nopicture.php', [ 'title' => 'No picture', 'model' => [ 'name' => [ 'type' => 'string' ] ], '*' => [ 'one' => [ 'name' => 'One' ] ] ] );

check( 'a call with no type draws nothing', logosPage( $appData, '[logos image="image"]' ) === '' && logosPage( $appData, '[logos]' ) === '' );
check( 'a type with no element draws nothing', logosPage( $appData, '[logos /empty image="image"]' ) === '' );
check( 'a type there is none of draws nothing, and the kernel says so once, as it does for any loop', logosPage( $appData, '[logos /nowhere image="image"]' ) === '' && count( ninoWarnings() ) === 1 );
check( 'a type that is no type name draws nothing: a path, a dot, a capital, a space', array_filter( [ '../logo', 'logo/x', '.logo', 'Logo', 'lo go', '-logo' ], static fn( string $type ): bool => logosPage( $appData, '[logos /'. $type. ' image="image"]' ) !== '' ) === [] && ninoWarnings() !== [] );
check( 'a type with no picture field draws nothing', logosPage( $appData, '[logos /nopicture image="image"]' ) === '' && logosPage( $appData, '[logos /nopicture image="name"]' ) === '' );

\Nino\Filesystem::putFileContent( $appData, '/elements/nameless.php', [ 'title' => 'Nameless', 'model' => [ 'image' => [ 'type' => 'image', 'width' => 540, 'height' => 210, 'fit' => true ] ], '*' => [ '*' => [] ] ] );
\Nino\Elements::insertElement( $appData, '/nameless/one', [] );
\Nino\Elements::updateElement( $appData, '/nameless/one', [ 'image' => \Nino\Images::fit( $appData, logosImage( 800, 300 ), 540, 210, 'elements/nameless/one' ) ] );

ninoWarnings();
check( 'a type with a picture and no field name draws its logo with an empty alt text, rather than a picture whose alt text is the literal [[name]]', substr_count( logosPage( $appData, '[logos /nameless image="image"]' ), '<img ' ) === 2
	&& str_contains( logosPage( $appData, '[logos /nameless image="image"]' ), ' alt="" ' ) === true && str_contains( logosPage( $appData, '[logos /nameless image="image"]' ), '[[' ) === false && ninoWarnings() === [] );

/*	The name is written into the alt attribute, and a field that keeps markup or the breaks of its text leaves the quote in it: the one value in
	the row that is not escaped for an attribute by the field. It is read as a line of text - the tags dropped, the entities the characters they
	stand for - and escaped once, whatever the field keeps	*/
$hostile = 'A <em>x</em> " onmouseover="alert(1) &amp; <b>[[/project/company/general/name]]</b>';

foreach( [ 'htmlname' => [ 'html' => true ], 'blockname' => [ 'html' => true, 'blocks' => true ], 'breakname' => [ 'breaks' => true ], 'plainname' => [], 'numname' => [ 'type' => 'integer' ] ] as $kind => $declared ) {
	\Nino\Filesystem::putFileContent( $appData, '/elements/'. $kind. '.php', [ 'title' => $kind, 'model' => [ 'name' => array_merge( [ 'type' => 'string' ], $declared ), 'image' => [ 'type' => 'image', 'width' => 540, 'height' => 210 ] ], '*' => [ '*' => [] ] ] );
	\Nino\Elements::insertElement( $appData, '/'. $kind. '/one', [ 'name' => $kind === 'numname' ? 7 : ( $kind === 'blockname' ? '<p>Block</p><p>Two <br>lines</p>' : $hostile ) ] );
	\Nino\Elements::updateElement( $appData, '/'. $kind. '/one', [ 'image' => \Nino\Images::process( $appData, logosImage( 800, 300 ), 540, 210, 'elements/'. $kind. '/one' ) ] );
}

$altOf = static function( string $kind ) use ( $appData ): string {
	preg_match( '~<img [^>]* alt="([^"]*)"~', logosPage( $appData, '[logos /'. $kind. ' image="image"]' ), $found );
	return $found[1] ?? 'none';
};

check( 'the name is a line of text whatever the field keeps: html is read as the text it says - tags dropped, the entity the character, a block a space - and every kind of it is escaped once, so that no quote ends the attribute and no bracket opens a fill',
	$altOf( 'htmlname' ) === 'A x &quot; onmouseover=&quot;alert(1) &amp; &#91;&#91;/project/company/general/name]]'
	&& $altOf( 'blockname' ) === 'Block Two lines'
	&& $altOf( 'breakname' ) === 'A &lt;em&gt;x&lt;/em&gt; &quot; onmouseover=&quot;alert(1) &amp;amp; &lt;b&gt;&#91;&#91;/project/company/general/name]]&lt;/b&gt;'
	&& $altOf( 'plainname' ) === $altOf( 'breakname' ) && $altOf( 'numname' ) === '7'
	&& array_filter( array_map( static fn( string $kind ): array => preg_match_all( '~<img\b[^>]*>~', logosPage( $appData, '[logos /'. $kind. ' image="image"]' ), $tags ) === 2 ? $tags[0] : [ 'none' ], [ 'htmlname', 'blockname', 'breakname', 'plainname', 'numname' ] ),
		static fn( array $tags ): bool => array_filter( $tags, static fn( string $tag ): bool => preg_match( '~^<img class="nino-logo-light nino-logos-strip-img" src="[^"]+" width="\d+" height="\d+" alt="[^"]*" loading="lazy" decoding="async">$~', $tag ) !== 1 || str_contains( $tag, '[[' ) === true ) !== [] ) === []
	&& ninoWarnings() === [] );

echo "\n";


// --- The stack, as the Builder offers it ----------------------------------------------

echo "Components - [logos] as a stack the Builder offers\n";

( static function( array $appData ) use ( $all, $callDark, $manifest ): void {

	$declared	= $manifest['stacks']['logos'] ?? [];
	$schema		= \Nino\Modules\Components::stacks( $appData )['logos'] ?? [];

	check( 'the stack is registered from the manifest, as the feature\'s own, and the component registry does not hold it', $schema !== [] && isset( \Nino\Modules\Components::components( $appData )['logos'] ) === false
		&& array_diff_key( $schema, [ 'by' => 1 ] ) === array_diff_key( $declared, [ 'by' => 1 ] ) );
	check( '...a loop with no content (the cell none) and no grid, drawn in the Builder as a picture, with the six attributes of the logos and the fields as the choice: four of them fields of the type, the height a choice of three, the links a flag',
		$schema['cell'] === 'none' && $schema['grid'] === false && $schema['preview'] === 'image'
		&& array_map( static fn( array $attribute ): string => $attribute['type'], array_diff_key( $schema['attributes'], [ 'class' => 1 ] ) ) === [ 'image' => 'field', 'link' => 'field', 'name' => 'field', 'dark' => 'field', 'height' => 'select', 'links' => 'bool' ]
		&& $schema['attributes']['height']['options'] === [ 's', 'm', 'l' ] );
	check( '...with the defaults of its attributes, as strings - the picture, the address from url, the name, the picture for a dark ground from image-dark, the middle height, links on - and the loop of the kernel in front of them',
		\Nino\Modules\Components::defaults( $appData, 'logos', true ) === [ 'sort' => '', 'offset' => '0', 'limit' => '0', 'query' => '', 'callback' => '', 'locale' => '', 'id' => '', 'image' => 'image', 'link' => 'url', 'name' => 'name', 'dark' => 'image-dark', 'height' => 'm', 'links' => '1', 'class' => '' ] );
	check( '...and says which of the fields must be chosen, which a tool reads: the picture always, the link while links is on, the name and the picture for a dark ground never',
		array_map( static fn( array $attribute ): mixed => $attribute['required'], array_intersect_key( $schema['attributes'], array_flip( [ 'image', 'link', 'name', 'dark' ] ) ) ) === [ 'image' => true, 'link' => 'links', 'name' => false, 'dark' => false ]
		&& $declared['attributes']['link']['required'] === 'links' && $declared['attributes']['link']['default'] === 'url' && ( $declared['attributes']['name']['required'] ?? false ) === false && ( $declared['attributes']['dark']['required'] ?? false ) === false );
	check( 'the feature registers the shortcode once, through the manifest', count( \Nino\Callbacks::registered( $appData, '/nino/html/shortcode/logos' ) ) === 1 );

	// What the renderer returns goes through the page's own rendering once more, which resolves the two words of the pause button: that is what a page gets
	$direct = static fn( array $args ): string => logosPage( $appData, \Nino\Modules\Logos::stackLogos( $appData, $args + \Nino\Modules\Components::defaults( $appData, 'logos', true ) + [ 'source' => '/logo', 'content' => '', 'grid' => false ] ) );

	check( 'the renderer is the same whichever way it is called: the arguments the Components module makes ready, and those of a page',
		$direct( [ 'image' => 'image', 'link' => 'url', 'name' => 'name', 'dark' => 'image-dark', 'sort' => 'order' ] ) === logosPage( $appData, $callDark ) && $all !== ''
		&& $direct( [ 'image' => 'image', 'link' => 'url', 'sort' => 'order', 'limit' => '2', 'height' => 's' ] ) === logosPage( $appData, '[logos /logo image="image" link="url" sort="order" limit="2" height="s"]' ) );

	ninoWarnings();
} )( $appData );

echo "\n";


// --- The looks of the Design feature ----------------------------------------------

echo "design/ - the looks, the style and the block that the Design feature reads\n";

/*	What a feature brings the Design feature as files (its manual, under Base, says what a variant, a style and a block are). Read
	here by their grammar - which tags, which fields, which names, which rule where - and what a head says is never compared: the
	words are the files' to say. The base stylesheet owns the row and declares every property a look moves, the looks move them	*/
$design 		= $dir. '/design';
$base 			= logosSheet( $dir. '/assets/logos.css' );
$looks 			= [];

foreach( glob( $design. '/*.css' ) ?: [] as $file )
	if( basename( $file ) !== 'styles.css' )
		$looks[ basename( $file, '.css' ) ] = logosSheet( $file );

ksort( $looks );

$kinds			= [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
$tabs				= [ 'type', 'surfaces', 'colours', 'motion' ];
$surfaces		= [ 'default', 'alt', 'tint', 'dark', 'black', 'brand', 'brand-safe', 'accent-safe', 'success', 'warning', 'danger' ];
$steps			= [ 'least', 'less', 'default', 'more', 'most' ];
$stepSuffix	= '/--(?:least|less|default|more|most)$/';
$knobTable	= [ 'height' => [ 'scale', 'type' ], 'gap' => [ 'scale', 'surfaces' ], 'corners' => [ 'scale', 'surfaces' ], 'ground' => [ 'ground', 'colours' ], 'speed' => [ 'scale', 'motion' ] ];
$answers		= [ 'band' => [ 'height', 'gap', 'speed' ], 'cards' => [ 'height', 'gap', 'corners', 'ground' ], 'grid' => [ 'height', 'gap', 'corners', 'ground' ], 'regular' => [ 'height', 'gap' ] ];
$grounds		= [ 'cards' => 'alt', 'grid' => 'default' ];

check( 'the feature brings four looks of the Design feature, a stylesheet each in design/ - and regular, which is where a feature starts from, is the row as the kernel drew it - with a block for the preview, and the style of all of them in styles.css and design/styles/',
	array_keys( $looks ) === [ 'band', 'cards', 'grid', 'regular' ] && is_file( $design. '/preview.tpl' ) === true && is_file( $design. '/styles.css' ) === true && is_dir( $design. '/styles' ) === true );

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

check( 'every knob is one of the five, of the same kind and on the same tab in every look: the height of a logo on Type; the space, the corners on Surface; the ground on Colour; the speed on Motion'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ), $offTable === [] && array_diff( array_keys( array_merge( ...array_values( $knobsOf ) ) ), array_keys( $knobTable ) ) === [] );
check( 'a look declares only what it answers to - Row the height and the space, Grid and Cards those and the corners and the ground, Band those and the speed - and the grounds are the surfaces they were made for: Grid on the first, Cards on the second',
	array_map( static fn( array $declared ): array => array_keys( $declared ), $knobsOf ) === $answers && ( $knobsOf['grid']['ground'][0]['default'] ?? '' ) === $grounds['grid'] && ( $knobsOf['cards']['ground'][0]['default'] ?? '' ) === $grounds['cards'] );

$ladderProblems = [];

foreach( $looks as $variant => $look ) {
	$stems = [];

	foreach( $knobsOf[$variant] ?? [] as $name => $lines )
		if( $lines[0]['kind'] === 'scale' ) {
			$stems[] = '--logos-'. $name;

			foreach( $steps as $step )
				if( count( $look['declared'][ '--logos-'. $name. '--'. $step ] ?? [] ) !== 1 || $look['declared'][ '--logos-'. $name. '--'. $step ][0]['selector'] !== ':root' )
					$ladderProblems[] = $variant. ' lacks --logos-'. $name. '--'. $step. ' on :root, once';
		}

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 && in_array( (string) preg_replace( $stepSuffix, '', $property ), $stems, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs - once each, on :root - and every ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ),
	$ladderProblems === [] && array_map( static fn( array $look ): int => count( array_filter( array_keys( $look['declared'] ), static fn( string $property ): bool => preg_match( $stepSuffix, $property ) === 1 ) ), $looks ) === [ 'band' => 15, 'cards' => 15, 'grid' => 15, 'regular' => 10 ] );

$baseDeclared	= $base['declared'];
$ruleProblems	= [];

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule ) {

		// The rungs of a ladder stand on :root, the steps of a run in the keyframes of the band are no rule, and every other rule is on the row's own classes
		// and the states a pointer and a keyboard give them - never an element, an id or a class of the kernel's
		if( $rule['selector'] === ':root' ) {
			foreach( array_column( logosDeclarations( $rule['body'] ), 'property' ) as $property )
				if( preg_match( '/^--logos-[a-z0-9-]+--(?:least|less|default|more|most)$/', $property ) !== 1 )
					$ruleProblems[] = $variant. ' declares '. $property. ' on :root, which is no rung of a ladder';
		}
		elseif( str_starts_with( $rule['at'], '@keyframes ' ) === true ) {
			if( $variant !== 'band' || in_array( $rule['selector'], [ 'from', 'to' ], true ) === false )
				$ruleProblems[] = $variant. ' has a step '. $rule['selector']. ' of keyframes';
		}
		elseif( ( $rule['at'] !== '' && $rule['at'] !== '@media (prefers-reduced-motion: reduce)' )
			|| preg_match( '/^[\s>+~,]*$/', (string) preg_replace( [ '/\.nino-logos-strip[a-z0-9-]*/', '/:(?:is|hover|focus-within)(?:\([^)]*\))?/' ], '', $rule['selector'] ) ) !== 1 || str_contains( $rule['selector'], '.nino-logos-strip' ) === false )
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];

		foreach( logosDeclarations( $rule['body'] ) as $declaration ) {
			preg_match_all( '/var\(\s*(--(?:nino-)?logos-[a-z0-9-]+)/', $declaration['value'], $reads );

			foreach( $reads[1] as $read )
				if( isset( $baseDeclared[$read] ) === false && in_array( $read, [ '--nino-logos-size', '--nino-logos-count' ], true ) === false )
					$ruleProblems[] = $variant. ' reads '. $read. ', which logos.css does not declare';
		}
	}

check( 'a look is a head, the rungs of its ladders on :root and rules on the row\'s own classes - never an element, an id or a class of the kernel - and it reads only properties that logos.css declares, or the two the row sets on itself'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 4 );

$colourProblems = [];

foreach( array_merge( array_map( static fn( string $variant ): string => $design. '/'. $variant. '.css', array_keys( $looks ) ), [ $design. '/styles.css', $dir. '/assets/logos.css' ], glob( $design. '/styles/*.css' ) ?: [] ) as $file ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );

	foreach( logosColourLiterals( $code ) as $literal )
		$colourProblems[] = basename( $file ). ' '. $literal;

	if( stripos( $code, '!important' ) !== false )
		$colourProblems[] = basename( $file ). ' !important';
}

check( '...and no stylesheet of the feature writes a colour as a literal - a hex colour, a function, the name of one - or declares anything !important: the colours are a ground, a mix of the page\'s own and what the page says'. ( $colourProblems === [] ? '' : ' - '. implode( ', ', $colourProblems ) ), $colourProblems === [] );

/*	Where the defaults stand: lengths and the ground with its parts in :where(:root), and nowhere else - a custom property is resolved where it is
	declared, and :where() has no specificity, so the line of the knob positions a look compiles beats it. The size and the count are the row's own,
	set on its classes, not defaults a look moves	*/
$logosSource	= (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/logos.css' ) );
$strayDefaults	= [];
$owned					= [ '--logos-height', '--logos-gap', '--logos-speed', '--logos-corners', '--logos-ground', '--logos-ground-on', '--logos-ground-border', '--logos-ground-shadow', '--logos-ground-logo-light', '--logos-ground-logo-dark' ];

foreach( $owned as $property )
	if( array_column( $baseDeclared[$property] ?? [], 'selector' ) !== [ ':where(:root)' ] )
		$strayDefaults[] = $property. ' is not declared once in :where(:root)';

foreach( $base['rules'] as $rule )
	foreach( logosDeclarations( $rule['body'] ) as $declaration )
		if( preg_match( '/^--logos-/', $declaration['property'] ) === 1 && $rule['selector'] !== ':where(:root)' )
			$strayDefaults[] = $declaration['property']. ' is declared under '. $rule['selector'];

// Each property it declares is read by a var() - in the row or in a look - since one that nothing reads is a knob that moves nothing
$readAnywhere = $logosSource. implode( "\n", array_map( static fn( string $variant ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $design. '/'. $variant. '.css' ) ), array_keys( $looks ) ) );

foreach( array_keys( $baseDeclared ) as $property )
	if( str_starts_with( $property, '--logos-' ) === true && preg_match( '/var\(\s*'. preg_quote( $property, '/' ). '\s*[,)]/', $readAnywhere ) !== 1 )
		$strayDefaults[] = $property. ' is read by nothing';

if( preg_match( '/:root\s*\{/', (string) preg_replace( '/:where\(:root\)/', '', $logosSource ) ) === 1 )
	$strayDefaults[] = 'a bare :root in logos.css';

check( 'logos.css declares every property a look moves, once, in :where(:root) and nowhere else - never in a bare :root, on a class or with !important - and each of them is read by a var()'. ( $strayDefaults === [] ? '' : ' - '. implode( ', ', $strayDefaults ) ),
	$strayDefaults === [] && str_contains( $logosSource, 'var(--logos-gap)' ) === true );

// Row is the row as it is without the Design feature: the middle of each ladder is the default logos.css has, and it writes no rule
$unequal = [];

foreach( $looks['regular']['declared'] ?? [] as $property => $places )
	if( preg_match( '/^(.+)--default$/', $property, $stem ) === 1 && ( $baseDeclared[ $stem[1] ][0]['value'] ?? null ) !== $places[0]['value'] )
		$unequal[] = $property;

foreach( $looks['regular']['rules'] ?? [] as $rule )
	if( $rule['selector'] !== ':root' )
		$unequal[] = 'a rule on '. $rule['selector'];

check( 'Row stands where logos.css stands: the middle of the height and of the space is the default the base declares and it writes no rule - so the row under it is drawn as it is without the Design feature'. ( $unequal === [] ? '' : ' - '. implode( ', ', $unequal ) ),
	$unequal === [] && count( array_filter( array_keys( $looks['regular']['declared'] ?? [] ), static fn( string $property ): bool => str_ends_with( $property, '--default' ) ) ) === 2 );

// The band is the band as it is without the Design feature's choice of a speed: the middle of its ladder is the default the base declares
check( 'the middle of the speed of Band is the default logos.css declares, as the middle of the other ladders is',
	( $looks['band']['declared']['--logos-speed--default'][0]['value'] ?? '' ) === ( $baseDeclared['--logos-speed'][0]['value'] ?? '-' ) && ( $baseDeclared['--logos-speed'][0]['value'] ?? '' ) === '5s' );

// The height of a logo is the class of the row's, relative to the height of the look
$sizes = [];

foreach( $base['rules'] as $rule )
	if( preg_match( '/^\.nino-logos-strip(--h-[sml])?$/', $rule['selector'], $size ) === 1 )
		$sizes[ $size[1] ?? '' ] = array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['--nino-logos-size'] ?? '';

check( 'the height is the class of the row: m is the height of the look, s three quarters of it and l one and a half times, and a row with no class has the height of the look',
	$sizes === [ '' => 'var(--logos-height)', '--h-s' => 'calc(var(--logos-height) * .75)', '--h-m' => 'var(--logos-height)', '--h-l' => 'calc(var(--logos-height) * 1.5)' ] );

// The copy belongs to the band: hidden everywhere else, shown by the band alone
$hidden = array_values( array_filter( $base['rules'], static fn( array $rule ): bool => $rule['selector'] === '.nino-logos-strip-copy' ) );

check( 'the copy is hidden by the base, so that every look but the band is one list; the band shows it with a rule of a heavier selector than the base\'s and hides it again for reduced motion',
	count( $hidden ) === 1 && ( array_column( logosDeclarations( $hidden[0]['body'] ), 'value', 'property' )['display'] ?? '' ) === 'none'
	&& array_filter( $looks['grid']['rules'], static fn( array $rule ): bool => str_contains( $rule['selector'], 'copy' ) ) === [] && array_filter( $looks['cards']['rules'], static fn( array $rule ): bool => str_contains( $rule['selector'], 'copy' ) ) === []
	&& array_filter( $looks['band']['rules'], static fn( array $rule ): bool => $rule['at'] === '' && str_contains( $rule['selector'], '.nino-logos-strip .nino-logos-strip-copy' ) && ( array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['display'] ?? '' ) === 'flex' ) !== [] );

// --- The band: a run, a pause, and none for a visitor who asked for less
$band			= $looks['band'];
$run			= array_values( array_filter( $band['rules'], static fn( array $rule ): bool => $rule['at'] === '' && str_contains( $rule['selector'], '.nino-logos-strip-list' ) && ( array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['animation'] ?? 'none' ) !== 'none' ) );
$runMade	= array_column( logosDeclarations( $run[0]['body'] ?? '' ), 'value', 'property' );
$paused		= array_values( array_filter( $band['rules'], static fn( array $rule ): bool => ( array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['animation-play-state'] ?? '' ) === 'paused' ) );
$focused	= array_values( array_filter( $band['rules'], static fn( array $rule ): bool => str_contains( $rule['selector'], ':focus-within' ) ) );
$stilled	= array_values( array_filter( $band['rules'], static fn( array $rule ): bool => $rule['at'] === '@media (prefers-reduced-motion: reduce)' ) );
$stillMade = [];

foreach( $stilled as $rule )
	foreach( logosDeclarations( $rule['body'] ) as $declaration )
		$stillMade[ $rule['selector'] ][ $declaration['property'] ] = $declaration['value'];

check( 'Band runs the list and its copy by their own width, over and over, in a time that is the number of logos times the time one takes - so the speed is the same for four logos and for forty - the script does not move it',
	count( $run ) === 1 && str_contains( $run[0]['selector'], '.nino-logos-strip-copy' ) === true && preg_match( '/^nino-logos-strip-run calc\(var\(--nino-logos-count\) \* var\(--logos-speed\)\) linear infinite$/', $runMade['animation'] ?? '' ) === 1
	&& preg_match( '/@keyframes nino-logos-strip-run\s*\{[^}]*\}[^}]*\}/s', (string) file_get_contents( $design. '/band.css' ) ) === 1 && array_filter( $band['rules'], static fn( array $rule ): bool => $rule['at'] === '@keyframes nino-logos-strip-run' && $rule['selector'] === 'to' && str_contains( $rule['body'], 'translateX(-100%)' ) ) !== []
	&& preg_match( '/\.nino-logos-strip\s+\.nino-logos-strip-view\s*\{[^}]*overflow:\s*hidden/', (string) file_get_contents( $design. '/band.css' ) ) === 1 );
check( 'Band stops under the pointer on its logos - not on its button, which stands outside the view - and where the button of the band has been pressed - the class nino-logos-strip--paused - for the list and the copy, and for nothing else: not under the keyboard focus',
	count( $paused ) === 1 && $paused[0]['at'] === '' && substr_count( $paused[0]['selector'], '.nino-logos-strip-view:hover' ) === 2 && substr_count( $paused[0]['selector'], '.nino-logos-strip--paused' ) === 2
	&& str_contains( $paused[0]['selector'], '.nino-logos-strip-list' ) === true && str_contains( $paused[0]['selector'], '.nino-logos-strip-copy' ) === true && str_contains( $paused[0]['selector'], ':focus-within' ) === false );
$focusMade = [];

foreach( $focused as $rule )
	$focusMade[ $rule['selector'] ] = array_column( logosDeclarations( $rule['body'] ), 'value', 'property' );

check( 'Band under the keyboard focus does not stay where it stands - a paused band leaves the link in focus out of sight - but stands at its start, with no animation and no fade at the edges, so that every stop is at least partly in view - and the focus of the button outside the view is none of this, which is what lets a press of it start the band again',
	count( $focused ) === 2 && ( $focusMade['.nino-logos-strip .nino-logos-strip-view:focus-within .nino-logos-strip-list, .nino-logos-strip .nino-logos-strip-view:focus-within .nino-logos-strip-copy'] ?? [] ) === [ 'animation' => 'none' ]
	&& ( $focusMade['.nino-logos-strip .nino-logos-strip-view:focus-within'] ?? [] ) === [ 'mask-image' => 'none' ] && str_contains( implode( ' ', array_keys( $focusMade ) ), '.nino-logos-strip:focus-within' ) === false );
check( 'Band under reduced motion stands still: no run, the list wraps and is centred like a row, the copy is not there and the fade at the edges and the clipping are gone',
	array_filter( $stillMade, static fn( array $made, string $selector ): bool => str_contains( $selector, '.nino-logos-strip-list' ) && ( $made['animation'] ?? '' ) === 'none' && ( $made['flex-wrap'] ?? '' ) === 'wrap' , ARRAY_FILTER_USE_BOTH ) !== []
	&& array_filter( $stillMade, static fn( array $made, string $selector ): bool => str_contains( $selector, '.nino-logos-strip-copy' ) && ( $made['display'] ?? '' ) === 'none', ARRAY_FILTER_USE_BOTH ) !== []
	&& array_filter( $stillMade, static fn( array $made, string $selector ): bool => $selector === '.nino-logos-strip .nino-logos-strip-view' && ( $made['mask-image'] ?? '' ) === 'none' && ( $made['overflow'] ?? '' ) === 'visible', ARRAY_FILTER_USE_BOTH ) !== [] );
check( 'the speed is a time in seconds, the longer the slower: five rungs from the slowest at the sharp end to the fastest',
	array_map( static fn( string $step ): string => $band['declared']['--logos-speed--'. $step][0]['value'], $steps ) === [ '10s', '7s', '5s', '3.5s', '2.5s' ] );

// The cells that paint a ground: the roles of the ground and the pair of the logo of it
$pair = [];

foreach( [ 'grid', 'cards' ] as $variant )
	foreach( $looks[$variant]['rules'] as $rule )
		if( $rule['selector'] === '.nino-logos-strip .nino-logos-strip-item' ) {
			$made = array_column( logosDeclarations( $rule['body'] ), 'value', 'property' );
			$pair[$variant] = [ $made['--logo-light'] ?? '', $made['--logo-dark'] ?? '', $made['background-color'] ?? '', $made['color'] ?? '', $made['border-radius'] ?? '' ];
		}

check( 'Grid and Cards paint a cell with the ground of the knob, the text colour of that ground and the corners of the knob, and give it the pair of the logo of that ground - a logo with a second picture shows the one made for the cell, whatever the section behind it is',
	$pair === [ 'grid' => [ 'var(--logos-ground-logo-light)', 'var(--logos-ground-logo-dark)', 'var(--logos-ground)', 'var(--logos-ground-on)', 'var(--logos-corners)' ],
		'cards' => [ 'var(--logos-ground-logo-light)', 'var(--logos-ground-logo-dark)', 'var(--logos-ground)', 'var(--logos-ground-on)', 'var(--logos-corners)' ] ] );
check( 'Grid has a line round the cell in the border the palette solved for its ground, Cards the shadow of that ground instead',
	str_contains( (string) file_get_contents( $design. '/grid.css' ), 'border: 1px solid var(--logos-ground-border)' ) === true && str_contains( (string) file_get_contents( $design. '/cards.css' ), 'box-shadow: 0 .25rem 1rem var(--logos-ground-shadow)' ) === true
	&& str_contains( (string) file_get_contents( $design. '/cards.css' ), 'border:' ) === false );

/*	Both stand in equal columns, none narrower than its logo - the width of the box the picture was cropped to at the height of the row, 18 to 7 - with
	the room at the sides of the cell that the cell takes, and the line of Grid: a column narrower than that makes the cell shrink the logo, which is
	what a minimum that left the padding out did at the height s on a phone	*/
$columns = [];

foreach( [ 'grid' => 'var(--space-1) * 2 + 2px', 'cards' => 'var(--space-1) * 2' ] as $variant => $room ) {
	foreach( $looks[$variant]['rules'] as $rule )
		if( $rule['selector'] === '.nino-logos-strip .nino-logos-strip-list' )
			$columns[$variant] = array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['grid-template-columns'] ?? '';

	$columns[$variant] = ( $columns[$variant] ?? '' ) === 'repeat(auto-fit, minmax(min(100%, calc(var(--nino-logos-size) * 18 / 7 + '. $room. ')), 1fr))';
}

$sides = [];

foreach( [ 'grid', 'cards' ] as $variant )
	foreach( $looks[$variant]['rules'] as $rule )
		if( $rule['selector'] === '.nino-logos-strip .nino-logos-strip-item' )
			$sides[$variant] = array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['padding'] ?? '';

check( 'Grid and Cards are equal columns of at least the width of a logo at the height of the row, 18 to 7, and the room at the sides of the cell - a space-1 on each side, and the line of the grid - so two stand side by side on a phone, and no cell shrinks its logo',
	$columns === [ 'grid' => true, 'cards' => true ] && $sides === [ 'grid' => 'var(--space-2) var(--space-1)', 'cards' => 'var(--space-2) var(--space-1)' ] );

// The picture: its height, the tone until a style says another, no motion for a visitor who asked for less
$picture = array_values( array_filter( $base['rules'], static fn( array $rule ): bool => $rule['selector'] === '.nino-logos-strip-img' && $rule['at'] === '' ) );
$pictureMade = array_column( logosDeclarations( $picture[0]['body'] ?? '' ), 'value', 'property' );
$lit = array_values( array_filter( $base['rules'], static fn( array $rule ): bool => str_starts_with( $rule['selector'], ':where(' ) && str_contains( $rule['selector'], ':hover' ) ) );

check( 'the picture has the height of the row and the width that gives it, inside the room it has, fitted into its box; until a style says another it is grey and faded and takes its colour under the pointer and under the keyboard focus, by a rule that has no weight of its own',
	count( $picture ) === 1 && $pictureMade['height'] === 'var(--nino-logos-size)' && $pictureMade['width'] === 'auto' && $pictureMade['max-width'] === '100%' && $pictureMade['object-fit'] === 'contain'
	&& $pictureMade['opacity'] === '.5' && $pictureMade['filter'] === 'grayscale(1)' && count( $lit ) === 1 && $lit[0]['selector'] === ':where(.nino-logos-strip-item:hover, .nino-logos-strip-item:focus-within) .nino-logos-strip-img'
	&& array_column( logosDeclarations( $lit[0]['body'] ), 'value', 'property' ) === [ 'opacity' => '1', 'filter' => 'grayscale(0)' ] );
check( 'logos.css moves nothing for a visitor who asked for less motion: its one transition is switched off under prefers-reduced-motion, and the band has no other motion',
	( $pictureMade['transition'] ?? '' ) !== '' && array_filter( $base['rules'], static fn( array $rule ): bool => $rule['at'] === '@media (prefers-reduced-motion: reduce)' && $rule['selector'] === '.nino-logos-strip-img'
		&& ( array_column( logosDeclarations( $rule['body'] ), 'value', 'property' )['transition'] ?? '' ) === 'none' ) !== []
	&& preg_match( '/\banimation\s*:|@keyframes|\btransform\s*:/', $logosSource ) !== 1 );

/*	The classes. Nino.css lands in the same bundle: it may carry a public .nino-logos and .nino-logos-item, and two rule sets for one name both apply. The three
	classes of the kernel's logo set and the three of its small outline button, the pause button's, are the templates' to write and the stylesheets' never to select; every other class the templates write, and every class a
	stylesheet selects, is this feature's own, and none of them is one Nino.css styles	*/
$uncommented		= static fn( string $file ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );
$ninoCss				= $uncommented( $root. '/_nino/Nino.css' );
$logoSet				= [ 'nino-logo-set', 'nino-logo-light', 'nino-logo-dark', 'nino-btn', 'nino-btn--outline', 'nino-btn--small' ];
$written				= [];
$selected				= [];

foreach( glob( $dir. '/templates/*.tpl' ) ?: [] as $file ) {
	preg_match_all( '/class="([^"]*)"/', (string) file_get_contents( $file ), $found );

	foreach( $found[1] as $words )
		$written = array_merge( $written, array_diff( (array) preg_split( '/\s+/', trim( $words ) ), $logoSet ) );
}

foreach( array_merge( [ $dir. '/assets/logos.css' ], glob( $design. '/*.css' ) ?: [], glob( $design. '/styles/*.css' ) ?: [] ) as $file ) {
	preg_match_all( '/\.(nino-[a-z0-9-]+)/', $uncommented( $file ), $found );
	$selected = array_merge( $selected, $found[1] );
}

$classes					= array_values( array_unique( array_merge( $written, $selected ) ) );
$styledByKernel		= array_values( array_filter( $classes, static fn( string $class ): bool => preg_match( '/\.'. preg_quote( $class, '/' ). '(?![\w-])/', $ninoCss ) === 1 ) );

check( 'no class the templates write or a stylesheet selects is one Nino.css styles - the bundle is shared - but the three of the kernel\'s logo set and the three of its small outline button, which the templates say and no stylesheet selects, and the classes are all this feature\'s: nino-logos-strip, and what follows it',
	$classes !== [] && $styledByKernel === [] && array_filter( $classes, static fn( string $class ): bool => str_starts_with( $class, 'nino-logos-strip' ) === false ) === [] && array_intersect( $selected, $logoSet ) === []
	&& preg_match( '/\.nino-logo-set\s+\.nino-logo-light\b/', $ninoCss ) === 1 && preg_match( '/\.nino-logo-set\s*>\s*img:only-child/', $ninoCss ) === 1 );

// --- The style: once for all the looks
$styles 			= logosSheet( $design. '/styles.css' );
$styleLines		= $styles['tags']['@style'] ?? [];
$styleName		= (string) strtok( $styleLines[0] ?? '', " \t" );
$optionFiles	= glob( $design. '/styles/*.css' ) ?: [];
$styleProblems = [];

if( count( $styleLines ) !== 1 || count( $styles['tags']['@style-de'] ?? [] ) !== 1 || strtok( $styles['tags']['@style-de'][0], " \t" ) !== $styleName || count( (array) preg_split( '/\s+/', $styleLines[0], 2 ) ) !== 2 )
	$styleProblems[] = 'styles.css declares one style with its @style and its @style-de line';

if( isset( $styles['tags']['@name'] ) === true || $styles['rules'] !== [] )
	$styleProblems[] = 'styles.css has a @name or a rule - it is no look, and the options share no rule';

$options = [];

foreach( $optionFiles as $file ) {
	$option = logosSheet( $file );
	$name		= basename( $file, '.css' );

	if( preg_match( '/^'. preg_quote( $styleName, '/' ). '-[a-z0-9]+(?:-[a-z0-9]+)*$/', $name ) !== 1 )
		$styleProblems[] = $name. ' is named for no option of '. $styleName;

	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $option['tags'][$tag] ?? [] ) !== 1 || trim( $option['tags'][$tag][0] ) === '' )
			$styleProblems[] = $name. ' '. $tag;

	$options[ substr( $name, strlen( $styleName ) + 1 ) ] = $option;
}

ksort( $options );

check( 'design/styles.css declares one style, tone, the files of design/styles/ are its options - each named <style>-<option>.css and headed in both languages - and it shares no rule among them'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ),
	$styleProblems === [] && $styleName === 'tone' && array_keys( $options ) === [ 'colour', 'grey', 'hover' ] );

$toneMade = [];

foreach( $options as $option => $sheet )
	foreach( $sheet['rules'] as $rule )
		$toneMade[$option][ $rule['selector'] ] = array_column( logosDeclarations( $rule['body'] ), 'value', 'property' );

check( 'each option moves the tone of the picture and nothing else - its opacity and its filter - on a selector that names the row, which is a weight of its own: in colour, grey, or grey until the pointer or the focus is on the logo, as logos.css has it until a style is chosen',
	$toneMade === [
		'colour' => [ '.nino-logos-strip .nino-logos-strip-img' => [ 'opacity' => '1', 'filter' => 'none' ] ],
		'grey' => [ '.nino-logos-strip .nino-logos-strip-img' => [ 'opacity' => '1', 'filter' => 'grayscale(1)' ] ],
		'hover' => [ '.nino-logos-strip .nino-logos-strip-img' => [ 'opacity' => $pictureMade['opacity'], 'filter' => $pictureMade['filter'] ],
			'.nino-logos-strip .nino-logos-strip-item:is(:hover, :focus-within) .nino-logos-strip-img' => [ 'opacity' => '1', 'filter' => 'none' ] ],
	] );

// --- The block: the row as the shortcode draws it, six logos in the list and in its copy
$block 			= (string) @file_get_contents( $design. '/preview.tpl' );
$blockIds 	= [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

[ $blockList, $blockCopy ] = logosLists( $block );
preg_match_all( '~<li class="nino-logos-strip-item">.*?</li>~s', $blockList, $blockCells );
preg_match_all( '~<img\b[^>]*>~', $block, $blockImages );

// The shape of one item as the shortcode draws it, the values made static: the block's cell is that, so that the specimen draws what the shortcode draws
$shape = static fn( string $markup ): string => (string) preg_replace( [ '/ (?:src|alt|width|height|href)="[^"]*"/', '/ loading="lazy" decoding="async"/', '/ rel="noopener"/', '/ tabindex="-1"/' ], '', $markup );

check( 'the feature brings a block to the specimen, design/preview.tpl: one grid row, every id in it logos-something and each once, no section of its own and no inline handler',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $block ), '</div>' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'logos-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1 );
check( '...the row is the shortcode\'s: the list and its copy of six logos each, as the count on its style says, a link to nothing - # - round a picture of the preview\'s placeholder, which has nothing to say and so an empty alt, with the size of the type; the links of the copy are out of the order of the Tab key',
	preg_match( '~<div class="nino-logos-strip nino-logos-strip--h-m" id="logos-strip" style="--nino-logos-count:6">\s*<div class="nino-logos-strip-view">~', $block ) === 1 && logosCount( $blockList ) === 6 && logosCount( $blockCopy ) === 6 && count( $blockImages[0] ) === 12
	&& array_filter( $blockImages[0], static fn( string $image ): bool => str_contains( $image, 'src="[[placeholder]]"' ) === false || str_contains( $image, ' width="540" height="210"' ) === false || str_contains( $image, ' alt=""' ) === false ) === []
	&& substr_count( $blockList, 'href="#"' ) === 6 && substr_count( $blockCopy, 'href="#"' ) === 6 && substr_count( $blockCopy, 'tabindex="-1"' ) === 6 && str_contains( $blockList, 'tabindex' ) === false );
check( '...and each cell of it is the item the shortcode draws, link, set and picture: the same markup but for the values',
	array_unique( array_map( $shape, $blockCells[0] ) ) === [ $shape( (string) preg_replace( '~^.*?(<li class="nino-logos-strip-item"><a [^>]*><span class="nino-logo-set"><img [^>]*zeta-image[^>]*></span></a></li>).*$~s', '$1', $list ) ) ] );

// --- The manual
$manualProblems = [];

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	foreach( [ 'Design: the looks of the logos', 'Builder: the presentation Logos' ] as $entry ) {
		$text = ( (array) ( $manifest['manual']['panel'] ?? [] ) )[$entry][$locale] ?? null;

		if( is_string( $text ) === false || trim( $text ) === '' || max( array_map( 'strlen', explode( "\n", $text ) ) ) >= 1000 )
			$manualProblems[] = $locale. ' '. $entry;
	}

	if( str_contains( (string) ( $manifest['manual']['panel']['Builder: the presentation Logos'][$locale] ?? '' ), 'order' ) === false )
		$manualProblems[] = $locale. ' the Builder line names the sort by order';

	foreach( [ '[logos /logo image="image" link="url" sort="order"]', '[logos /logo image="image" link="url" name="name" dark="image-dark"]', '[logos /logo sort="name" limit="8"]', '[logos /logo links="0"]', '[logos /logo height="l"]' ] as $handle )
		if( trim( (string) ( $manifest['manual']['shortcodes'][$handle][$locale] ?? '' ) ) === '' )
			$manualProblems[] = $locale. ' '. $handle;

	foreach( [ '--logos-height, --logos-gap, --logos-corners, --logos-ground, --logos-speed', '<ul class="nino-logos-strip-copy" aria-hidden="true">', '<div class="nino-logos-strip nino-logos-strip--h-m">' ] as $handle )
		if( trim( (string) ( $manifest['manual']['markup'][$handle][$locale] ?? '' ) ) === '' )
			$manualProblems[] = $locale. ' markup '. $handle;
}

check( 'the manual names the looks, the knobs and the style and the loop of the Builder under panel, the shortcodes with their attributes and the properties and the copy under markup, in both languages, every line under 1000 bytes'. ( $manualProblems === [] ? '' : ' - '. implode( ', ', $manualProblems ) ), $manualProblems === [] );

echo "\n";


// --- ...run through the Design feature ---------------------------------------------

echo "design/ - as the Design feature reads and compiles it\n";

$designSetup			= '\\Nino\\Modules\\Design\\Setup';
$designPreview		= '\\Nino\\Modules\\Design\\Preview';
$designCompiler		= '\\Nino\\Modules\\Design\\Compiler';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the looks are not run through it here, bin/check.sh runs them with it in the checkout\n";
else {
	$library			= \Nino\Features::dir(). '/Design/library';
	$modulesKept	= (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept		= $appData['./nino/locales/current'];

	/** Which of the features are on: their classes are listed in /nino/modules, and what the kernel and the Design feature have read of the features is read again */
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Logos', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Logos', 'Design' ] );
	$appData['./nino/locales/current'] = 'en_US';

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['logos'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its four looks, regular the one it has before anything is chosen, the anchor of its block - and has nothing to say of any file: no line dropped, no ladder missing, no style without a file',
		isset( $scan['logos'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === array_keys( $looks ) && $feature['default'] === 'regular'
		&& $feature['anchor'] === 'feature-logos' && $scanNotes === [] );

	$publishes = [];

	foreach( $catalogue as $variant => $look )
		$publishes[$variant] = array_map( static fn( array $knob ): array => [ $knob['kind'], $knob['tab'] ], $look['knobs'] );

	check( 'each look publishes the knobs it declares and no other, with the kind and the tab of its head - and a scale by the ladder of its own name',
		array_map( static fn( array $each ): array => array_keys( $each ), $publishes ) === $answers && array_filter( $publishes, static fn( array $each ): bool => array_diff_assoc( $each, array_intersect_key( $knobTable, $each ) ) !== [] ) === []
		&& array_filter( $catalogue, static function( array $look ): bool {
			foreach( $look['knobs'] as $name => $knob )
				if( $knob['kind'] === 'scale' && ( $knob['ladder'] ?? [] ) !== [ $name ] )
					return true;

			return false;
		} ) === [] );

	$styled = $feature['styles'] ?? [];

	check( 'the styles the scan finds are the one design/styles.css declares, with the options its files give, the file of each there',
		array_keys( $styled ) === [ $styleName ] && array_keys( $styled[$styleName]['options'] ?? [] ) === array_keys( $options )
		&& array_filter( $styled[$styleName]['options'] ?? [], static fn( array $option ): bool => is_file( (string) $option['file'] ) === false ) === [] );

	// The block is the feature's, not a look's: Design makes the section round it, with the feature's anchor
	$blockTold		= [];
	$blockInside	= $designSetup::previewOf( (string) $feature['dir'], 'logos', $blockTold );
	$blocks				= $designPreview::blocks( $appData );

	check( 'the block stands the check Design holds it to and the preview makes the section of it, with the anchor the scan names, before the footer',
		$blockInside === trim( $block ) && $blockInside !== '' && $blockTold === [] && array_keys( $blocks ) === [ 'logos' ]
		&& preg_match( '~^<section id="feature-logos" class="nino-section" aria-label="[^"]+">\s*(.*?)\s*</section>$~s', $blocks['logos'] ?? '', $blockSection ) === 1 && str_contains( $blockSection[1] ?? '', 'id="logos-strip"' ) === true
		&& substr_count( $blocks['logos'], 'nino-logos-strip-item' ) === 12 && str_contains( $blocks['logos'], '[[placeholder]]' ) === false );

	$withNone	= $compile( [] );
	$featuresOn( [ 'Design' ] );
	$withOff	= $compile( [] );
	$featuresOn( [ 'Logos', 'Design' ] );

	check( 'with no look chosen the feature compiles nothing: the sheet of a project that has the feature on is the sheet of one that has it off, byte for byte', $withNone === $withOff && str_contains( $withNone, 'feature logos' ) === false );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'logos' => [ 'set' => $variant ] ] ], $sheetNotes );
		$ladders		= [];

		foreach( $look['knobs'] as $name => $knob )
			if( $knob['kind'] === 'scale' ) {
				$ladders[] = $name. ' default';

				foreach( $knob['ladder'] ?? [] as $ladder )
					if( str_contains( $sheet, "\n\t--logos-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'logos', $ladder, 'default' ). ");\n" ) === false )
						$sheetProblems[] = $variant. ' selects no '. $ladder;
			}
			elseif( $knob['kind'] === 'ground' ) {
				if( str_contains( $sheet, "\n\t--logos-". $name. ': var(--nino-'. $knob['default']. ");\n" ) === false )
					$sheetProblems[] = $variant. ' writes no ground '. $name;

				// The pair of the logo of the ground is a part of it: what a cell hands the logo set inside it
				foreach( [ 'logo-light', 'logo-dark' ] as $part )
					if( str_contains( $sheet, "\n\t--logos-". $name. '-'. $part. ': var(--nino-'. $knob['default']. '-'. $part. ");\n" ) === false )
						$sheetProblems[] = $variant. ' writes no '. $part. ' of the ground '. $name;
			}

		if( preg_match( '~^/\* ==== \d+\. feature logos: '. preg_quote( $variant, '~' ). '(?: \((.*)\))? ==== \*/$~m', $sheet, $title ) !== 1 || ( $title[1] ?? '' ) !== implode( ', ', $ladders ) )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] || str_contains( $sheet, $designCompiler::SHARED ) === true )
			$sheetProblems[] = $variant. ' compiled with a note, or with the shared rules of a style that was not chosen';
	}

	check( 'with a look chosen the sheet carries its section, its stylesheet as the file has it, a line for every ladder of every knob it declares and, for a ground, every part the palette solved for it - the pair of the logo among them'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ),
		$sheetProblems === [] && $catalogue !== [] );

	$groundSheet = $compile( [ 'features' => [ 'logos' => [ 'set' => 'grid', 'knobs' => [ 'ground' => 'dark' ] ] ] ] );

	check( 'a ground moved is written as the surface and every part of it - the colour, the text on it, the line, the shadow and the pair of the logo - the properties the stylesheets read',
		str_contains( $groundSheet, "\n\t--logos-ground: var(--nino-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--logos-ground-on: var(--nino-on-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--logos-ground-border: var(--nino-dark-border);\n" ) === true
		&& str_contains( $groundSheet, "\n\t--logos-ground-shadow: var(--nino-dark-shadow);\n" ) === true && str_contains( $groundSheet, "\n\t--logos-ground-logo-dark: var(--nino-dark-logo-dark);\n" ) === true );

	$styleProblems = [];

	foreach( array_keys( $catalogue ) as $variant )
		foreach( $styled[$styleName]['options'] ?? [] as $option => $declared ) {
			$sheet	= $compile( [ 'features' => [ 'logos' => [ 'set' => $variant, 'styles' => [ $styleName => $option ] ] ] ] );
			$line 	= sprintf( $designCompiler::STYLE, $styleName, $option );

			if( str_contains( $sheet, rtrim( (string) file_get_contents( (string) $declared['file'] ), "\n" ) ) === false || substr_count( $sheet, $line ) !== 1 )
				$styleProblems[] = $variant. ' '. $option. ': the option is not in the sheet once, as the file has it';

			if( strpos( $sheet, $line ) < strpos( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) )
				$styleProblems[] = $variant. ' '. $option. ': the option stands before the look, which it has to come behind';

			if( str_contains( $sheet, $designCompiler::SHARED ) === true )
				$styleProblems[] = $variant. ' '. $option. ': the styles share nothing and the sheet has a line for it';
		}

	check( 'a style chosen puts the file of the option into the section of the look, whichever it is, under its own line and behind the look - the same for Row, Grid, Band and Cards - and writes nothing of what the options share, for they share nothing'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ),
		$styleProblems === [] && count( $catalogue ) === 4 );

	// The bundle of a project the wizard has set up lists the sheet of the feature after the theme the Design feature writes
	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ $designCompiler::TARGET, '/assets/style.css' ] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];

	check( 'in a booted project the bundle lists logos.css after the theme.css the Design feature writes its sheet to - the order the :where() of its defaults is for',
		in_array( '/features/Logos/assets/logos.css', $bundle, true ) === true && array_search( '/features/Logos/assets/logos.css', $bundle, true ) > array_search( $designCompiler::TARGET, $bundle, true ) );

	$appData['/nino/modules']					= $modulesKept;
	$appData['./nino/locales/current']	= $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );

	ninoWarnings();
}

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so logos-js-smoke.js is not run here\n\n";
else {
	echo "logos-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/logos-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}

ninoDone( $appData );
