<?php
declare(strict_types=1);

/**
 *	Nino
 *	social-smoke.php	Social links: the manifest and the files it ships, the
 *										install unit and what it leaves alone, what [social],
 *										[social-link] and [social-icon] draw, what an editor
 *										can put into an element and what of it reaches a page,
 *										and what deactivation leaves behind.
 *
 *	Usage: php features/Social/tests/social-smoke.php
 *	       NINO_ROOT=../nino php features/Social/tests/social-smoke.php
 */

/*	The checkout this runs against: three levels up when the feature sits in a
	project's features/, else the one NINO_ROOT names. The features root is this
	feature's own parent either way, so the kernel's autoloader serves the class
	- and Filesystem's /features/ its icons and templates - from here	*/
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/**
 *	A throwaway project with the feature switched on, the Elements and
 *	Template modules beside it, ready to render
 *
 *	@param		array					$locales			The project's languages, the first its native one
 *	@param		callable|null	$before				Called with &$appData before the activation
 *
 *	@return 	array
 */
function socialSandbox( array $locales = [ 'de_DE', 'en_US' ], ?callable $before = null ): array {

	$appData = ninoSandbox( 'social' );
	$appData['/nino/dir'] = '';
	$appData['/nino/locales/available'] = $locales;
	$appData['/nino/locales/native'] = $locales[0];
	$appData['./nino/locales/current'] = in_array( 'en_US', $locales, true ) === true ? 'en_US' : $locales[0];
	\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

	if( $before !== null )
		$before( $appData );

	check( 'activation in '. implode( '+', $locales ). ' succeeds', \Nino\Features::activate( $appData, 'social' ) === true );

	$appData['/nino/modules'][] = '\\Nino\\Modules\\Elements';
	$appData['/nino/modules'][] = '\\Nino\\Modules\\Template';

	// The order \Nino\init() takes: the assets of the manifests, then the init() of every module
	\Nino\Features::registerAssets( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

/**
 *	@param		array 		&$appData			(reference) Array with current app data
 *	@param		string		$html
 *
 *	@return 	string
 */
function render( array &$appData, string $html ): string {
	return \Nino\Html::renderHtml( $appData, $html );
}

/**
 *	The attributes of the anchor that leads to an address, as written in a rendering
 *
 *	@param		string		$html
 *	@param		string		$href					The href attribute's value as it is written
 *
 *	@return 	array|null								[ name => value, ... ] of the first such anchor, or null where there is none
 */
function anchorAttributes( string $html, string $href ): ?array {

	preg_match_all( '#<a\s([^>]*)>#', $html, $anchors );

	foreach( $anchors[1] as $attributes ) {

		preg_match_all( '#([a-z-]+)="([^"]*)"#', $attributes, $pairs, PREG_SET_ORDER );
		$found = [];
		foreach( $pairs as $pair )
			$found[$pair[1]] = $pair[2];

		if( ( $found['href'] ?? null ) === $href )
			return $found;
	}

	return null;
}

/**
 *	The rules of a stylesheet, its comments left out: for each the at-rule it stands in ('' for none), its selector with the
 *	whitespace folded and the text of its body. One level of at-rule is read, which is as deep as the stylesheets here go
 *
 *	@param		string		$css
 *
 *	@return 	array<int, array{at: string, selector: string, body: string}>
 */
function socialRules( string $css ): array {

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
function socialDeclarations( string $body ): array {

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
function socialSheet( string $file ): array {

	$css	= (string) file_get_contents( $file );
	$tags	= [];

	preg_match( '#^\s*/\*(.*?)\*/#s', $css, $comment );

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 )
			$tags[ $found[1] ][] = $found[2];

	$sheet = [ 'tags' => $tags, 'rules' => socialRules( $css ), 'declared' => [] ];

	foreach( $sheet['rules'] as $rule )
		foreach( socialDeclarations( $rule['body'] ) as $declaration )
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
function socialColourLiterals( string $css ): array {

	$names = explode( ' ', 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen' );
	$literal = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(|(?<![\w-])(?:'. implode( '|', $names ). ')(?![\w-])/i';
	$found = [];

	foreach( socialRules( $css ) as $rule )
		foreach( socialDeclarations( $rule['body'] ) as $declaration )
			if( preg_match( $literal, (string) preg_replace( '/--[a-z0-9-]+/i', '', $declaration['value'] ), $match ) === 1 )
				$found[] = $declaration['property']. ': '. $match[0];

	return $found;
}

$dir = dirname( __DIR__ );


// --- 1. The manifest and the files ------------------------------------------

echo "The manifest and the files\n";

$manifest = \Nino\Features::manifest( $dir );

check( 'it validates, key "social"', is_array( $manifest ) === true && $manifest['key'] === 'social' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) === true && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest ) === true
	&& \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

check( 'it is filed under content, and has no setting, no data and no requirement', $manifest['category'] === 'content'
	&& $manifest['settings'] === [] && $manifest['data'] === [] && $manifest['requires'] === [] );
check( 'it lists its stylesheet under assets, which is all it asks the kernel for, and has no script', $manifest['assets'] === [ '/features/Social/assets/social.css' ] );

$type			= include $dir. '/install/social.php';
$options	= $type['model']['icon']['options'];
$icons		= array_map( static fn( string $file ): string => basename( $file, '.svg' ), glob( $dir. '/icons/*.svg' ) ?: [] );
sort( $icons );
$offered = $options;
sort( $offered );

check( 'every icon the select offers is a file, and every file is offered', $offered === $icons && count( $icons ) === 23 );
check( 'the icon a link falls back to is one of them', in_array( \Nino\Modules\Social::FALLBACK, $icons, true ) === true );
check( 'the type has the five fields, every one of them global', array_keys( $type['model'] ) === [ 'title', 'icon', 'link', 'order', 'hidden' ]
	&& array_filter( $type['model'], static fn( array $field ): bool => ( $field['locale'] ?? false ) === true ) === [] );
check( 'a link is visible unless it says otherwise', ( $type['*']['*']['hidden'] ?? null ) === false );

$samples = array_diff_key( $type['*'], [ '*' => true ] );
check( 'four links to start from, each named, with an offered icon and a front page, not an account', array_keys( $samples ) === [ 'instagram', 'facebook', 'youtube', 'telegram' ]
	&& array_filter( $samples, static fn( array $sample ): bool => ( $sample['title'] ?? '' ) === ''
		|| in_array( $sample['icon'] ?? '', $options, true ) === false
		|| preg_match( '#^https://[^/]+/$#', $sample['link'] ?? '' ) !== 1 ) === [] );

/*	An icon is inlined into every page that draws a link, as often as the page
	draws it, so it may carry nothing that is only allowed once or that does
	anything: no id, no style, no class, no reference, no script, no handler -
	drawing elements in one line, in currentColor, hidden from screen readers	*/
$clean = [];
foreach( glob( $dir. '/icons/*.svg' ) ?: [] as $file ) {

	$svg = (string) file_get_contents( $file );
	$document = new DOMDocument();

	if( substr_count( $svg, "\n" ) !== 1 || str_ends_with( $svg, "</svg>\n" ) === false || str_contains( $svg, '[' ) === true
		|| @$document->loadXML( $svg ) === false || $document->documentElement?->localName !== 'svg' ) {
		$clean[] = basename( $file );
		continue;
	}

	$svgRoot = $document->documentElement;
	if( $svgRoot->getAttribute( 'fill' ) !== 'none' || $svgRoot->getAttribute( 'stroke' ) !== 'currentColor'
		|| $svgRoot->getAttribute( 'aria-hidden' ) !== 'true' || $svgRoot->getAttribute( 'focusable' ) !== 'false' )
		$clean[] = basename( $file );

	foreach( $document->getElementsByTagName( '*' ) as $node ) {
		if( in_array( $node->localName, [ 'svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse' ], true ) === false )
			$clean[] = basename( $file );
		foreach( $node->attributes ?? [] as $attribute )
			if( in_array( $attribute->name, [ 'id', 'style', 'class', 'href' ], true ) === true || str_starts_with( $attribute->name, 'on' ) === true )
				$clean[] = basename( $file );
	}
}
check( 'every icon is one line of drawing elements in currentColor, aria-hidden, with nothing that runs or must be unique'. ( $clean === [] ? '' : ' - '. implode( ', ', array_unique( $clean ) ) ), $clean === [] );

check( 'the icons carry Lucide\'s license: ISC, and MIT for what came from Feather',
	str_contains( (string) @file_get_contents( $dir. '/icons/LICENSE' ), 'ISC License' ) === true
	&& str_contains( (string) @file_get_contents( $dir. '/icons/LICENSE' ), 'Cole Bemis' ) === true
	&& str_contains( (string) @file_get_contents( $dir. '/icons/LICENSE-0.577.0' ), 'ISC License' ) === true );

$en				= include $dir. '/install/text/en_US.php';
$de				= include $dir. '/install/text/de_DE.php';
$unit			= include $dir. '/install/manifest.php';
$labels		= array_map( static fn( string $field ): string => '[[/_admin/elements/field/social/'. $field. ']]', array_keys( $type['model'] ) );

check( 'a label per field, in both languages, and nothing else', array_keys( $en ) === $labels && array_keys( $de ) === $labels );
check( 'the label of the field Link names a path of the website, in both languages', str_contains( $en['[[/_admin/elements/field/social/link]]'], 'a path of the website like /contact' ) === true
	&& str_contains( $de['[[/_admin/elements/field/social/link]]'], 'ein Pfad der Website wie /kontakt' ) === true );
$manualLines = [];
foreach( (array) ( $manifest['manual']['shortcodes'] ?? [] ) + (array) ( $manifest['manual']['markup'] ?? [] ) as $handle => $line )
	$manualLines[$handle] = $line;
check( 'the manual names the three shortcodes and the path of the website, in both languages', array_filter(
	[ '[social]', '[social-link instagram]', '[social-icon telegram]', 'href="/contact"' ],
	static fn( string $handle ): bool => is_array( $manualLines[$handle] ?? null ) === false || trim( (string) ( $manualLines[$handle]['en_US'] ?? '' ) ) === '' || trim( (string) ( $manualLines[$handle]['de_DE'] ?? '' ) ) === '' ) === [] );
check( 'the unit blacklists every label - workbench words, not the site\'s', $unit['blacklist'] === array_map( static fn( string $key ): string => trim( $key, '[]' ), $labels ) );

$css = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/social.css' ) );
preg_match_all( '/(?<![\w-])fill\s*:\s*([^;]+);/', $css, $fills );
preg_match_all( '/([^{}]+)\{([^}]*)\}/', $css, $rules, PREG_SET_ORDER );
$reset = [];
$weighty = [];
foreach( $rules as $rule ) {
	if( preg_match( '/(?<![\w-])(?:margin|padding)\s*:/', $rule[2] ) !== 1 )
		continue;
	if( preg_match( '/^\s*:where\(\.nino-social\)\s*$/', $rule[1] ) === 1 )
		$reset[] = preg_replace( '/\s+/', ' ', trim( $rule[2] ) );
	elseif( preg_match( '/^\s*\.nino-social\s*$/', $rule[1] ) === 1 )
		$weighty[] = trim( $rule[1] );
}
check( 'the list\'s reset of margin and padding stands in :where(), so the spacing classes of the Builder, which weigh as much as .nino-social, are not cancelled by a stylesheet that comes later', $reset === [ 'margin: 0; padding: 0;' ] && $weighty === [] );
check( 'the stylesheet fills nothing: a fill set from css beats the svg\'s and blots a line icon', $fills[1] === [ 'none' ] );


// --- 2. The activation -------------------------------------------------------

echo "\nThe activation\n";

$appData = socialSandbox();

$typeFile = \Nino\Filesystem::getFileContent( $appData, '/elements/social.php', false );
check( 'the type lands with its four links', is_array( $typeFile ) === true && count( array_diff_key( $typeFile['*'], [ '*' => true ] ) ) === 4 );
check( 'the template social-links lands, holding [social]', trim( (string) \Nino\Filesystem::getFileContent( $appData, '/templates/social-links.tpl', '' ) ) === '[social]' );
check( 'the labels land in both languages', ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/_admin/elements/field/social/hidden]]'] ?? '' ) === 'Auf der Website ausblenden'
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/_admin/elements/field/social/hidden]]'] ?? '' ) === 'Hide on the website' );
check( '...and on the blacklist', in_array( '/_admin/elements/field/social/link', (array) \Nino\Filesystem::getFileContent( $appData, '/text/blacklist.php', [] ), true ) === true );
check( 'the stylesheet joins the bundle', in_array( '/features/Social/assets/social.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );


// --- 3. What the shortcodes draw ---------------------------------------------

echo "\nWhat the shortcodes draw\n";

$html = render( $appData, '[social]' );
check( '[social]: one list, four links, by position', substr_count( $html, '<ul class="nino-social">' ) === 1 && substr_count( $html, '<li class="nino-social-item">' ) === 4
	&& strpos( $html, 'instagram.com' ) < strpos( $html, 'facebook.com' ) && strpos( $html, 'facebook.com' ) < strpos( $html, 'youtube.com' ) && strpos( $html, 'youtube.com' ) < strpos( $html, 't.me' ) );
check( 'every link is named by text for a screen reader, its icon hidden', substr_count( $html, '<span class="nino-social-label nino-sr-only">' ) === 4
	&& substr_count( $html, '<span class="nino-social-icon" aria-hidden="true"><svg' ) === 4 && str_contains( $html, 'aria-label' ) === false );
check( 'a profile on the web opens in a new tab and is rel="me noopener"', substr_count( $html, '<a ' ) === 4
	&& count( array_filter( [ 'instagram.com', 'facebook.com', 'youtube.com', 't.me' ], static fn( string $host ): bool => preg_match( '#<a [^>]*href="https://[^"]*'. preg_quote( $host, '#' ). '[^"]*"[^>]*>#', $html, $anchor ) === 1 && str_contains( $anchor[0], 'target="_blank"' ) === true && str_contains( $anchor[0], 'rel="me noopener"' ) === true ) ) === 4 );
$telegram = trim( (string) file_get_contents( $dir. '/icons/telegram.svg' ) );
check( 'the icon is the one the element names', $telegram !== '' && str_contains( $html, $telegram ) === true );

$html = render( $appData, '[social only="telegram, instagram, nope"]' );
check( 'only=: these, an unknown id ignored, in the order of their position', substr_count( $html, '<li ' ) === 2 && strpos( $html, 'instagram.com' ) < strpos( $html, 't.me' ) );
$html = render( $appData, '[social exclude="facebook,telegram"]' );
check( 'exclude= leaves them out', substr_count( $html, '<li ' ) === 2 && str_contains( $html, 'facebook.com' ) === false && str_contains( $html, 't.me' ) === false );
$html = render( $appData, '[social show="both" size="large"]' );
check( 'show="both" shows the names, size="large" sizes the list', str_contains( $html, '<ul class="nino-social nino-social--large">' ) === true
	&& substr_count( $html, '<span class="nino-social-label">' ) === 4 && substr_count( $html, '<svg' ) === 4 );
$html = render( $appData, '[social show="label" size="small"]' );
check( 'show="label": the names alone', str_contains( $html, '<ul class="nino-social nino-social--small">' ) === true && str_contains( $html, '<svg' ) === false && str_contains( $html, '>Instagram</span>' ) === true );
$html = render( $appData, '[social size="huge" show="all" class="x" onclick="y"]' );
check( 'an unknown size or show is the default, and nothing else is echoed but the class', str_contains( $html, '<ul class="nino-social x">' ) === true
	&& substr_count( $html, 'nino-sr-only' ) === 4 && str_contains( $html, 'onclick' ) === false );

$html = render( $appData, '[social class="my-class mt-2"]' );
check( '[social class=…]: the call\'s class is on the list, behind the feature\'s own, and on no link', str_contains( $html, '<ul class="nino-social my-class mt-2">' ) === true
	&& substr_count( $html, '<a class="nino-social-link" href=' ) === 4 && substr_count( $html, 'my-class' ) === 1 );
$html = render( $appData, '[social size="large" class="my-class"]' );
check( '...after the size', str_contains( $html, '<ul class="nino-social nino-social--large my-class">' ) === true );
$hostileClass = 'a" onclick="x" [[/project/company/general/name]] [social]';
$html = \Nino\Modules\Social::doShortcode( $appData, [ 'class' => $hostileClass ] );
check( '...escaped, its bracket neutralised, so it cannot leave the attribute or render again', str_contains( $html, '<ul class="nino-social a&quot; onclick=&quot;x&quot; &#91;&#91;/project/company/general/name]] &#91;social]">' ) === true );
check( '[social class=""] is the call without one', render( $appData, '[social class=""]' ) === render( $appData, '[social]' ) && render( $appData, '[social class="  "]' ) === render( $appData, '[social]' ) );

$html = render( $appData, 'Follow us on [social-link youtube].' );
check( '[social-link]: one anchor in the text, icon and name by default, no list', substr_count( $html, '<a ' ) === 1 && str_contains( $html, '<ul' ) === false
	&& str_contains( $html, '<span class="nino-social-label">YouTube</span>' ) === true && str_contains( $html, '<svg' ) === true && str_starts_with( $html, 'Follow us on <a class="nino-social-link nino-stretch"' ) === true );
check( '[social-link class=…]: the call\'s class is on the anchor, in both forms of the call', str_contains( render( $appData, '[social-link youtube class="my-class"]' ), '<a class="nino-social-link nino-stretch my-class" href="https://www.youtube.com/" target="_blank" rel="me noopener">' ) === true
	&& render( $appData, '[social-link id="youtube" class="my-class"]' ) === render( $appData, '[social-link youtube class="my-class"]' )
	&& substr_count( render( $appData, '[social-link youtube class="my-class"]' ), 'my-class' ) === 1 );
check( '...escaped, and nowhere when the link is none', str_contains( \Nino\Modules\Social::doLinkShortcode( $appData, [ 'id' => 'youtube', 'class' => $hostileClass ] ), '<a class="nino-social-link nino-stretch a&quot; onclick=&quot;x&quot; &#91;&#91;/project/company/general/name]] &#91;social]" href=' ) === true
	&& render( $appData, '[social-link nope class="my-class"]' ) === '' );
check( '[social-link id="…" show="label"]: the name alone', str_contains( render( $appData, '[social-link id="youtube" show="label"]' ), '<svg' ) === false
	&& str_contains( render( $appData, '[social-link id="youtube" show="label"]' ), 'youtube.com' ) === true );
check( '[social-link] of an unknown id draws nothing', render( $appData, '[social-link nope]' ) === '' && render( $appData, '[social-link]' ) === '' );
check( '[social-icon telegram]: the icon alone', render( $appData, '[social-icon telegram]' ) === '<span class="nino-social-icon" aria-hidden="true">'. $telegram. '</span>' );
check( '[social-icon class=…]: the call\'s class is on the wrapper of the icon, in both forms of the call', render( $appData, '[social-icon telegram class="my-class"]' ) === '<span class="nino-social-icon my-class" aria-hidden="true">'. $telegram. '</span>'
	&& render( $appData, '[social-icon name="telegram" class="my-class"]' ) === render( $appData, '[social-icon telegram class="my-class"]' ) );
check( '...escaped, and the icon of an unknown slug carries it too', str_contains( \Nino\Modules\Social::doIconShortcode( $appData, [ 'name' => 'telegram', 'class' => $hostileClass ] ), '<span class="nino-social-icon a&quot; onclick=&quot;x&quot; &#91;&#91;/project/company/general/name]] &#91;social]" aria-hidden="true">' ) === true
	&& str_starts_with( render( $appData, '[social-icon nope class="my-class"]' ), '<span class="nino-social-icon my-class" aria-hidden="true"><svg' ) === true );
check( '[social-icon name="…"] inside [elements /social] draws each element\'s', substr_count( render( $appData, '[elements /social][social-icon name="[[icon]]"][/elements]' ), '<svg' ) === 4 );
check( 'the template social-links, included, draws the list', substr_count( render( $appData, '[template /templates/social-links]' ), '<li class="nino-social-item">' ) === 4 );

ninoWarnings();
check( 'drawing raised no warning', ninoWarnings() === [] );


// --- The looks of the Design feature ----------------------------------------------

echo "design/ - the looks, the hover and the block that the Design feature reads\n";

/*	What a feature brings the Design feature as files (its manual, under Base, says what a variant, a style and a block are). Read here by their
	grammar - which tags, which fields, which names, which rule where - and what a head says is never compared: the words are the files' to say.
	The base stylesheet owns the row and declares every property a look moves, the looks move them	*/
$design 		= $dir. '/design';
$base 			= socialSheet( $dir. '/assets/social.css' );
$looks 			= [];

foreach( glob( $design. '/*.css' ) ?: [] as $file )
	if( basename( $file ) !== 'styles.css' )
		$looks[ basename( $file, '.css' ) ] = socialSheet( $file );

ksort( $looks );

$kinds			= [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
$tabs				= [ 'type', 'surfaces', 'colours', 'motion' ];
$inkWords		= [ 'ink', 'subtitle', 'title', 'accent', 'primary', 'border', 'focus' ];
$surfaces		= [ 'default', 'alt', 'tint', 'dark', 'black', 'brand', 'brand-safe', 'accent-safe', 'success', 'warning', 'danger' ];
$steps			= [ 'least', 'less', 'default', 'more', 'most' ];
$stepSuffix	= '/--(?:least|less|default|more|most)$/';
$knobTable	= [ 'size' => [ 'scale', 'type' ], 'gap' => [ 'scale', 'surfaces' ], 'stroke' => [ 'scale', 'type' ], 'icon' => [ 'ink', 'colours' ], 'badge' => [ 'ground', 'colours' ] ];
$answers		= [ 'badges' => [ 'size', 'gap', 'stroke', 'icon', 'badge' ], 'outline' => [ 'size', 'gap', 'stroke', 'icon' ], 'regular' => [ 'size', 'gap', 'stroke' ] ];

check( 'the feature brings three looks of the Design feature, a stylesheet each in design/ - and regular, which is where a feature starts from, is the plain one, the links as they have always been drawn - with a block for the preview and the styles of all of them in styles.css and design/styles/',
	array_keys( $looks ) === [ 'badges', 'outline', 'regular' ] && array_map( 'basename', glob( $design. '/*' ) ?: [] ) === [ 'badges.css', 'outline.css', 'preview.tpl', 'regular.css', 'styles', 'styles.css' ] );

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

		// A scale takes no default - its middle is the one it stands at - an ink a tone of the page and a ground the surface of the palette it stands at
		if( $line['kind'] === 'scale' ? $line['default'] !== null : ( $line['kind'] === 'ink' ? in_array( $line['default'], $inkWords, true ) === false : in_array( $line['default'], $surfaces, true ) === false ) )
			$knobProblems[] = $variant. ' default of '. $name;
	}

	foreach( $look['tags']['@knob-de'] ?? [] as $text )
		if( isset( $knobsOf[$variant][ (string) strtok( $text, " \t" ) ] ) === false )
			$knobProblems[] = $variant. ' @knob-de '. strtok( $text, " \t" ). ' names no knob';
}

check( 'each @knob line has its four fields - the name, a kind, a tab, the words - once, a @knob-de beside it, and an ink a tone of the page, a ground a surface of the palette'. ( $knobProblems === [] ? '' : ' - '. implode( ', ', $knobProblems ) ), $knobProblems === [] && $knobsOf !== [] );

$offTable = [];

foreach( $knobsOf as $variant => $declared )
	foreach( $declared as $name => $lines )
		if( ( $knobTable[$name] ?? null ) !== [ $lines[0]['kind'], $lines[0]['tab'] ] )
			$offTable[] = $variant. ' '. $name;

check( 'every knob is one of the five, of the same kind and on the same tab in every look: the size of an icon and the weight of the line on Text, the space between the links on Surface, the colour of the icons and the ground of a badge on Colour'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ), $offTable === [] && $knobsOf !== [] );
check( 'a look declares what it answers to: Badges all five, Outline all but the ground of a badge - it paints none, its line takes the tone of the icon - and Plain the size, the space and the weight of the line alone: it paints nothing, so its icon stays the colour of the link, which is the colour of the name beside it, and a knob a look does not declare is greyed out in the panel',
	array_map( static fn( array $declared ): array => array_keys( $declared ), $knobsOf ) === $answers );

$ladderProblems = [];

foreach( $looks as $variant => $look ) {
	$stems = [];

	foreach( $knobsOf[$variant] ?? [] as $name => $lines )
		if( $lines[0]['kind'] === 'scale' ) {
			$stems[] = '--social-'. $name;

			foreach( $steps as $step )
				if( count( $look['declared'][ '--social-'. $name. '--'. $step ] ?? [] ) !== 1 || $look['declared'][ '--social-'. $name. '--'. $step ][0]['selector'] !== ':root' )
					$ladderProblems[] = $variant. ' lacks --social-'. $name. '--'. $step. ' on :root, once';
		}

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 && in_array( (string) preg_replace( $stepSuffix, '', $property ), $stems, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs - once each, on :root - and every ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ),
	$ladderProblems === [] && count( array_filter( array_keys( $looks['regular']['declared'] ?? [] ), static fn( string $property ): bool => preg_match( $stepSuffix, $property ) === 1 ) ) === 15 );

$baseDeclared	= $base['declared'];
$ruleProblems	= [];

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule ) {

		// The ladders stand on :root, a rule is on the classes of the links and on nothing else - no element, no id, no class of the kernel's
		if( $rule['selector'] === ':root' ) {
			foreach( array_column( socialDeclarations( $rule['body'] ), 'property' ) as $property )
				if( preg_match( '/^--social-[a-z0-9-]+--(?:least|less|default|more|most)$/', $property ) !== 1 )
					$ruleProblems[] = $variant. ' declares '. $property. ' on :root, which is no rung of a ladder';
		}
		elseif( in_array( $rule['at'], [ '', '@media (forced-colors: active)' ], true ) === false || preg_match( '/^[\s>+~,]*$/', (string) preg_replace( [ '/:not\(\.nino-social \*\)/', '/\.nino-social[a-z0-9-]*/' ], '', $rule['selector'] ) ) !== 1 || str_contains( $rule['selector'], '.nino-social' ) === false )
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];

		foreach( socialDeclarations( $rule['body'] ) as $declaration ) {
			preg_match_all( '/var\(\s*(--(?:nino-)?social-[a-z0-9-]+)/', $declaration['value'], $reads );

			foreach( $reads[1] as $read )
				if( isset( $baseDeclared[$read] ) === false )
					$ruleProblems[] = $variant. ' reads '. $read. ', which social.css does not declare';
		}
	}

check( 'a look is a head, the rungs of its ladders on :root and rules on the links\' own classes - never an element, an id or a class of the kernel - and it reads only properties that social.css declares'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 3 );

$colourProblems = [];

foreach( array_merge( array_map( static fn( string $variant ): string => $design. '/'. $variant. '.css', array_keys( $looks ) ), [ $design. '/styles.css', $dir. '/assets/social.css' ], glob( $design. '/styles/*.css' ) ?: [] ) as $file ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );

	foreach( socialColourLiterals( $code ) as $literal )
		$colourProblems[] = basename( $file ). ' '. $literal;

	if( stripos( $code, '!important' ) !== false )
		$colourProblems[] = basename( $file ). ' !important';
}

check( '...and no stylesheet of the feature writes a colour as a literal - a hex colour, a function, the name of one: there are no brand colours here - or declares anything !important: the colours are a ground, a tone or what the page says'. ( $colourProblems === [] ? '' : ' - '. implode( ', ', $colourProblems ) ), $colourProblems === [] );

/*	Where the defaults stand: lengths and numbers and the ground with its parts in :where(:root), the tone of the icons in :where(*) - a custom property is resolved where it is
	declared, and :where() has no specificity, so the line of the knob positions a look compiles beats it	*/
$socialSource		= (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/social.css' ) );
$strayDefaults	= [];
$places					= [ '--social-size' => ':where(:root)', '--social-gap' => ':where(:root)', '--social-stroke' => ':where(:root)', '--social-icon' => ':where(*)', '--social-badge' => ':where(:root)' ];

foreach( [ 'on', 'on-muted', 'link', 'border', 'focus' ] as $part )
	$places[ '--social-badge-'. $part ] = ':where(:root)';

foreach( $places as $property => $place )
	if( array_column( $baseDeclared[$property] ?? [], 'selector' ) !== [ $place ] )
		$strayDefaults[] = $property. ' is not declared once in '. $place;

foreach( $base['rules'] as $rule )
	foreach( socialDeclarations( $rule['body'] ) as $declaration )
		if( str_starts_with( $declaration['property'], '--social-' ) === true && in_array( $rule['selector'], [ ':where(:root)', ':where(*)' ], true ) === false )
			$strayDefaults[] = $declaration['property']. ' is declared under '. $rule['selector'];

// Each property it declares is read by a var() - in the row or in a look - since one that nothing reads is a knob that moves nothing
$readAnywhere = $socialSource. implode( "\n", array_map( static fn( string $variant ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $design. '/'. $variant. '.css' ) ), array_keys( $looks ) ) );

foreach( array_keys( $places ) as $property )
	if( preg_match( '/var\(\s*'. preg_quote( $property, '/' ). '\s*[,)]/', $readAnywhere ) !== 1 )
		$strayDefaults[] = $property. ' is read by nothing';

if( preg_match( '/:root\s*\{/', (string) preg_replace( '/:where\(:root\)/', '', $socialSource ) ) === 1 )
	$strayDefaults[] = 'a bare :root in social.css';

check( 'social.css declares every property a look moves, once, in the place of its kind and in no other: the size, the space, the weight of the line and the ground of a badge with its parts in :where(:root), the tone of the icons in :where(*) - never in a bare :root, on a class or with !important - and each of them is read by a var()'. ( $strayDefaults === [] ? '' : ' - '. implode( ', ', $strayDefaults ) ), $strayDefaults === [] );

// Plain is the row as it is without the Design feature: the middle of every ladder is the default social.css has, it writes no rule, and the tone of the icons the base declares is the colour of the link
$unequal = [];
$plain	 = $looks['regular'] ?? [ 'declared' => [], 'rules' => [] ];

foreach( $plain['declared'] as $property => $stands )
	if( preg_match( '/^(.+)--default$/', $property, $stem ) === 1 && ( $baseDeclared[ $stem[1] ][0]['value'] ?? null ) !== $stands[0]['value'] )
		$unequal[] = $property;

foreach( $plain['rules'] as $rule )
	if( $rule['selector'] !== ':root' )
		$unequal[] = 'a rule on '. $rule['selector'];

foreach( $knobsOf['regular'] ?? [] as $name => $lines )
	if( in_array( $lines[0]['kind'], [ 'ink', 'ground', 'colour' ], true ) === true )
		$unequal[] = 'a colour knob '. $name;

if( ( $baseDeclared['--social-icon'][0]['value'] ?? null ) !== 'currentColor' )
	$unequal[] = 'the icons are not the colour of the link';

check( 'Plain stands where social.css stands: the middle of each of its ladders is the default the base declares, it writes no rule and has no colour knob, and the tone of the icons the base declares is the colour of the link - so the links under it are drawn as they are without the Design feature'. ( $unequal === [] ? '' : ' - '. implode( ', ', $unequal ) ),
	$unequal === [] && count( array_filter( array_keys( $plain['declared'] ), static fn( string $property ): bool => str_ends_with( $property, '--default' ) ) ) === 3 );

/*	The knob and the attribute are on one thing: the call's size is the exception of one list, the knob the site's rule. The class of the call is worked out from the property of
	the knob - it is a factor of it - and the icon reads the result, so that moving the knob moves a small and a large list with it and neither is a size of its own	*/
$sizeRules = [];

foreach( $base['rules'] as $rule )
	foreach( socialDeclarations( $rule['body'] ) as $declaration )
		if( $declaration['property'] === '--nino-social-size' )
			$sizeRules[ $rule['selector'] ] = $declaration['value'];

check( 'the sizes of a call are factors of the knob: the list reads --social-size, .nino-social--small is three quarters of it and .nino-social--large four thirds, and none of the three is a length of its own',
	( $sizeRules['.nino-social'] ?? '' ) === 'var(--social-size)' && ( $sizeRules['.nino-social--small'] ?? '' ) === 'calc( var(--social-size) * .75 )' && ( $sizeRules['.nino-social--large'] ?? '' ) === 'calc( var(--social-size) * 4 / 3 )' && count( $sizeRules ) === 3 );
check( '...the shortcode writes those classes, so a call with a size is a factor of what the site draws',
	str_contains( render( $appData, '[social size="small"]' ), '<ul class="nino-social nino-social--small">' ) === true && str_contains( render( $appData, '[social size="large"]' ), '<ul class="nino-social nino-social--large">' ) === true
	&& str_contains( $socialSource, 'var(--social-size)' ) === true );
check( '...and the icon, in a list or alone, takes its side from that property - a lone one five sixths of the knob, the 1.25em it was - and nothing else gives it a width',
	array_column( $baseDeclared['--nino-social-icon-size'] ?? [], 'value' ) === [ 'var(--nino-social-size, calc( var(--social-size) * 5 / 6 ))' ]
	&& array_column( $baseDeclared['width'] ?? [], 'value', 'selector' )['.nino-social-icon'] === 'var(--nino-social-icon-size)' && array_column( $baseDeclared['height'] ?? [], 'value', 'selector' )['.nino-social-icon'] === 'var(--nino-social-icon-size)' );

// The weight of the line is the property on the svg, which beats the attribute of the file; the icon is drawn in the tone the property says
$iconRule = [];

foreach( $base['rules'] as $rule )
	if( $rule['selector'] === '.nino-social-icon > svg' )
		$iconRule = array_column( socialDeclarations( $rule['body'] ), 'value', 'property' );

check( 'the icon\'s line is the weight of the knob - stroke-width on the svg reads --social-stroke - and the wrapper has the tone of the knob as its colour, which the svg draws in',
	( $iconRule['stroke-width'] ?? '' ) === 'var(--social-stroke)' && ( $iconRule['stroke'] ?? '' ) === 'currentColor'
	&& array_column( $baseDeclared['color'] ?? [], 'value', 'selector' ) === [ '.nino-social-icon' => 'var(--social-icon)' ] );

// Badges: the ground of the knob behind the icon, round, and what stands in it read on it
$badge = array_column( array_filter( $looks['badges']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-social-icon' ), 'body' );
$badgeMade = array_column( socialDeclarations( $badge[0] ?? '' ), 'value', 'property' );
$roles = [
	'--color-ink' => 'var(--social-badge-on)', '--color-title' => 'var(--social-badge-on)', '--color-subtitle' => 'var(--social-badge-on-muted)',
	'--color-accent' => 'var(--social-badge-link)', '--color-border' => 'var(--social-badge-border)', '--color-focus' => 'var(--social-badge-focus)',
];

check( 'Badges paint the wrapper of the icon: the ground of the knob, round, with a line in its border colour - and pass the roles of that ground on to the icon, so that the tone chosen for the icon is read on the badge and not on the section: the ink and the title as its text colour, the subtitle as the quieter one, the accent as its link, the border and the focus',
	count( $badge ) === 1 && ( $badgeMade['background-color'] ?? '' ) === 'var(--social-badge)' && ( $badgeMade['border-radius'] ?? '' ) === '50%' && str_contains( (string) ( $badgeMade['border'] ?? '' ), 'var(--social-badge-border)' ) === true
	&& array_intersect_key( $badgeMade, $roles ) === $roles && array_filter( array_diff_key( $looks['badges']['declared'] ?? [], array_flip( array_keys( $roles ) ) ), static fn( array $stands, string $property ): bool => str_starts_with( $property, '--color-' ), ARRAY_FILTER_USE_BOTH ) === []
	&& array_filter( [ 'regular', 'outline' ], static fn( string $variant ): bool => array_filter( array_keys( $looks[$variant]['declared'] ?? [] ), static fn( string $property ): bool => str_starts_with( $property, '--color-' ) ) !== [] ) === [] );

// Outline: a ring of line in the tone of the icon, as thick as the icon's line - no ground, no roles. A shadow inside the edge, because a border is rounded down to a pixel of the screen; forced colours take shadows away and get a border
$ringRule	= array_column( array_filter( $looks['outline']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-social-icon' && $rule['at'] === '' ), 'body' );
$ringMade	= array_column( socialDeclarations( $ringRule[0] ?? '' ), 'value', 'property' );
$ringForced	= array_values( array_filter( $looks['outline']['rules'] ?? [], static fn( array $rule ): bool => $rule['at'] === '@media (forced-colors: active)' ) );
$outlineCss	= (string) file_get_contents( $design. '/outline.css' );

check( 'Outline draws a circle of line round the icon in the tone of the icon (currentColor, which is the wrapper\'s colour) as thick as the line of the icon - a shadow inside the edge whose width reads --social-stroke, which is not rounded to a pixel as a border is - and has no ground: nothing in it reads --social-badge or sets a background',
	count( $ringRule ) === 1 && ( $ringMade['box-shadow'] ?? '' ) === 'inset 0 0 0 calc( var(--nino-social-icon-size) / 24 * var(--social-stroke) ) currentColor' && ( $ringMade['border-radius'] ?? '' ) === '50%' && isset( $ringMade['border'] ) === false
	&& str_contains( (string) preg_replace( '#/\*.*?\*/#s', '', $outlineCss ), '--social-badge' ) === false && array_filter( array_keys( $looks['outline']['declared'] ?? [] ), static fn( string $property ): bool => str_starts_with( $property, 'background' ) ) === [] );
check( '...and where the visitor has forced colours, which take shadows away, the circle is a border of a pixel in the colour of the icon',
	count( $ringForced ) === 1 && $ringForced[0]['selector'] === '.nino-social-icon' && array_column( socialDeclarations( $ringForced[0]['body'] ), 'value', 'property' ) === [ 'border' => '1px solid currentColor' ] );

// A lone icon or link in running text, outside a list, would grow the line by what the badge or the circle takes round the icon, upward from the baseline: the margin takes it off again, by the same two fifths as the padding and by the width of the line
$loneMargin = [];

foreach( [ 'badges' => 'calc( var(--nino-social-icon-size) * -.4 - 1px )', 'outline' => 'calc( var(--nino-social-icon-size) * -.4 )' ] as $variant => $expected ) {
	$lone		= array_values( array_filter( $looks[$variant]['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-social-icon:not(.nino-social *)' ) );
	$wrap		= array_column( array_filter( $looks[$variant]['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-social-icon' && $rule['at'] === '' ), 'body' );

	if( count( $lone ) !== 1 || array_column( socialDeclarations( $lone[0]['body'] ), 'value', 'property' ) !== [ 'margin-block' => $expected ] || $lone[0]['at'] !== ''
		|| ( array_column( socialDeclarations( $wrap[0] ?? '' ), 'value', 'property' )['padding'] ?? '' ) !== 'calc( var(--nino-social-icon-size) * .4 )' )
		$loneMargin[] = $variant;
}

check( 'Badges and Outline take the room round the icon off the margin of a lone icon - outside a list, by the two fifths of the padding - and of the line a badge has - so that a line of text is as high as it is with a plain icon; Plain has no such rule'. ( $loneMargin === [] ? '' : ' - '. implode( ', ', $loneMargin ) ),
	$loneMargin === [] && array_filter( $looks['regular']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] !== ':root' ) === [] );

// --- The hover: a style of all the looks
$styles 			= socialSheet( $design. '/styles.css' );
$styleLines		= $styles['tags']['@style'] ?? [];
$styleName		= (string) strtok( $styleLines[0] ?? '', " \t" );
$optionFiles	= glob( $design. '/styles/*.css' ) ?: [];
$styleProblems = [];

if( count( $styleLines ) !== 1 || count( $styles['tags']['@style-de'] ?? [] ) !== 1 || strtok( $styles['tags']['@style-de'][0], " \t" ) !== $styleName || count( (array) preg_split( '/\s+/', $styleLines[0], 2 ) ) !== 2 )
	$styleProblems[] = 'styles.css declares one style with its @style and its @style-de line';

if( isset( $styles['tags']['@name'] ) === true )
	$styleProblems[] = 'styles.css has a @name - it is no look';

$optionSheets = [];

foreach( $optionFiles as $file ) {
	$optionSheets[ basename( $file, '.css' ) ] = $option = socialSheet( $file );

	if( preg_match( '/^'. preg_quote( $styleName, '/' ). '-[a-z0-9]+(?:-[a-z0-9]+)*$/', basename( $file, '.css' ) ) !== 1 )
		$styleProblems[] = basename( $file ). ' is named for no option of '. $styleName;

	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $option['tags'][$tag] ?? [] ) !== 1 || trim( $option['tags'][$tag][0] ) === '' )
			$styleProblems[] = basename( $file ). ' '. $tag;
}

check( 'design/styles.css declares one style, the hover, with a @style and a @style-de line, and the files of design/styles/ are its options - each named <style>-<option>.css and headed in both languages'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ),
	$styleProblems === [] && $styleName === 'hover' && count( $optionFiles ) === 2 && array_keys( $optionSheets ) === [ 'hover-lift', 'hover-tint' ] );

/*	The body of styles.css is what the options share, compiled before the option chosen and with none chosen not at all: how fast a link changes, and not at all for a visitor who asks for less motion	*/
$sharedRules = array_values( array_filter( $styles['rules'], static fn( array $rule ): bool => $rule['at'] === '' ) );
$sharedMade	 = array_column( socialDeclarations( $sharedRules[0]['body'] ?? '' ), 'value', 'property' );
$calmRules	 = array_values( array_filter( $styles['rules'], static fn( array $rule ): bool => $rule['at'] === '@media (prefers-reduced-motion: reduce)' ) );

check( 'the body of styles.css is the one transition the options share - on the link and on the wrapper of the icon, of its transform and its colour - and, for a visitor who asks for less motion, none',
	count( $sharedRules ) === 1 && $sharedRules[0]['selector'] === '.nino-social-link, .nino-social-icon' && preg_match( '/^transform [.\d]+s [a-z-]+, color [.\d]+s [a-z-]+$/', (string) ( $sharedMade['transition'] ?? '' ) ) === 1
	&& count( $calmRules ) === 1 && $calmRules[0]['selector'] === '.nino-social-link, .nino-social-icon' && array_column( socialDeclarations( $calmRules[0]['body'] ), 'value', 'property' ) === [ 'transition' => 'none' ] );

$lift		= $optionSheets['hover-lift']['rules'] ?? [];
$tint		= $optionSheets['hover-tint']['rules'] ?? [];
$liftOn	= array_column( socialDeclarations( $lift[0]['body'] ?? '' ), 'value', 'property' );
$tintOn	= array_column( socialDeclarations( $tint[0]['body'] ?? '' ), 'value', 'property' );

check( 'Lift raises the link by two pixels under the pointer and the keyboard focus - in the list, where a transform moves the box, and only where the visitor has not asked for less motion',
	count( $lift ) === 1 && $lift[0]['at'] === '@media (prefers-reduced-motion: no-preference)' && $lift[0]['selector'] === '.nino-social .nino-social-link:is(:hover, :focus-visible)' && $liftOn === [ 'transform' => 'translateY(-2px)' ] );
check( 'Tint gives the icon of the link the accent of the ground it stands on - the wrapper\'s colour, which the line and a circle of Outline are drawn in, and which a badge has solved for its own ground - under the pointer and the keyboard focus',
	count( $tint ) === 1 && $tint[0]['at'] === '' && $tint[0]['selector'] === '.nino-social-link:is(:hover, :focus-visible) .nino-social-icon' && $tintOn === [ 'color' => 'var(--color-accent)' ] );
check( 'social.css moves and tints nothing: no transform, no transition, no :hover and no reduced-motion block - the hover is the option\'s, and with None nothing happens',
	preg_match( '/\btransform\s*:|\btransition\s*:|:hover|prefers-reduced-motion/', $socialSource ) !== 1
	&& preg_match( '/\btransform\b|\btransition\b|:hover/', (string) preg_replace( '#/\*.*?\*/#s', '', implode( "\n", array_map( static fn( string $variant ): string => (string) file_get_contents( $design. '/'. $variant. '.css' ), array_keys( $looks ) ) ) ) ) !== 1 );

// --- The block: four links, the icons by the shortcode so that it stands without links of the project
$block 			= (string) @file_get_contents( $design. '/preview.tpl' );
$blockIds 	= [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

preg_match_all( '/<li class="nino-social-item">(.*?)<\/li>/s', $block, $blockItems );
preg_match_all( '/\[social-icon ([a-z0-9-]+)\]/', $block, $blockSlugs );

check( 'the feature brings a block to the specimen, design/preview.tpl: one grid row, every id in it social-something and each once, no section of its own and no inline handler',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $block ), '</div>' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'social-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1 );
check( '...it is one list of the links\' own classes with four links by hand - each to nothing, #, with its name for a screen reader - whose icons are the shortcode\'s, four different ones this feature has, so that the block needs no link of the project and no [social]',
	substr_count( $block, '<ul class="nino-social"' ) === 1 && count( $blockItems[1] ) === 4 && substr_count( $block, 'href="#"' ) === 4 && substr_count( $block, 'nino-sr-only' ) === 4
	&& count( array_unique( $blockSlugs[1] ) ) === 4 && array_filter( $blockSlugs[1], static fn( string $slug ): bool => is_file( $dir. '/icons/'. $slug. '.svg' ) === false ) === []
	&& preg_match( '/\[social[\s\]]|\[\[|<svg/', $block ) !== 1 );

// The block is the markup of the templates: an item of it, the icon and the ids taken out, is an item of the shortcode
$itemShape		= static fn( string $markup ): string => (string) preg_replace( [ '/ id="[^"]*"/', '/\[social-icon [a-z0-9-]+\]|<span class="nino-social-icon".*?<\/svg><\/span>/s', '/<span class="nino-social-label[^"]*">[^<]*<\/span>/', '/ href="[^"]*"| target="[^"]*"| rel="[^"]*"/' ], '', trim( $markup ) );
$socialItem		= preg_match( '/<li class="nino-social-item">.*?<\/li>/s', render( $appData, '[social]' ), $first ) === 1 ? $first[0] : '';
$blockRendered	= render( $appData, $block );

check( '...each item of it has the shape the shortcode draws - the list item, the link, the icon, the name - and, rendered, it holds four icons, each one of the feature\'s own wrapped aria-hidden, as the shortcode draws them',
	$socialItem !== '' && array_filter( $blockItems[0], static fn( string $item ): bool => $itemShape( $item ) !== $itemShape( $socialItem ) ) === []
	&& substr_count( $blockRendered, '<span class="nino-social-icon" aria-hidden="true"><svg' ) === 4 && str_contains( $blockRendered, '[social-icon' ) === false );

// --- The manual
$manualProblems = [];

foreach( [ 'panel' => 'Design: the looks of the social links', 'markup' => '--social-size, --social-gap, --social-stroke, --social-icon, --social-badge' ] as $manualSection => $manualHandle )
	foreach( [ 'en_US', 'de_DE' ] as $locale )
		if( trim( (string) ( $manifest['manual'][$manualSection][$manualHandle][$locale] ?? '' ) ) === '' )
			$manualProblems[] = $locale. ' '. $manualSection;

check( 'the manual names the looks under panel and the properties under markup, in both languages'. ( $manualProblems === [] ? '' : ' - '. implode( ', ', $manualProblems ) ), $manualProblems === [] );

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
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Social', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Social', 'Design' ] );
	$appData['./nino/locales/current'] = 'en_US';

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['social'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its three looks, regular the one it has before anything is chosen, the anchor of its block - and has nothing to say of any file: no line dropped, no ladder missing, no style without a file',
		isset( $scan['social'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === array_keys( $looks ) && $feature['default'] === 'regular'
		&& $feature['anchor'] === 'feature-social' && $scanNotes === [] );

	$publishes = [];

	foreach( $catalogue as $variant => $look )
		$publishes[$variant] = array_map( static fn( array $knob ): array => [ $knob['kind'], $knob['tab'] ], $look['knobs'] );

	check( 'each look publishes the knobs it declares and no other, with the kind and the tab of its head, a scale by the ladder of its own name: five for Badges, four for Outline, three for Plain',
		$publishes['badges'] === $knobTable && $publishes['regular'] === array_diff_key( $knobTable, [ 'badge' => 1, 'icon' => 1 ] ) && $publishes['outline'] === array_diff_key( $knobTable, [ 'badge' => 1 ] )
		&& array_filter( $catalogue, static fn( array $look ): bool => ( $look['knobs']['size']['ladder'] ?? [] ) !== [ 'size' ] || ( $look['knobs']['gap']['ladder'] ?? [] ) !== [ 'gap' ] || ( $look['knobs']['stroke']['ladder'] ?? [] ) !== [ 'stroke' ] ) === [] );

	$styled = $feature['styles'] ?? [];

	check( 'the styles the scan finds are the one design/styles.css declares, the hover, with the two options its files give, the file of each there',
		array_keys( $styled ) === [ $styleName ] && array_keys( $styled[$styleName]['options'] ?? [] ) === [ 'lift', 'tint' ] && array_filter( $styled[$styleName]['options'] ?? [], static fn( array $option ): bool => is_file( (string) $option['file'] ) === false ) === [] );

	// The block is the feature's, not a look's: Design makes the section round it, with the feature's anchor
	$blockTold		= [];
	$blockInside	= $designSetup::previewOf( (string) $feature['dir'], 'social', $blockTold );
	$blocks				= $designPreview::blocks( $appData );

	check( 'the block stands the check Design holds it to and the preview makes the section of it, with the anchor the scan names, before the footer',
		$blockInside === trim( $block ) && $blockInside !== '' && $blockTold === [] && array_keys( $blocks ) === [ 'social' ]
		&& preg_match( '~^<section id="feature-social" class="nino-section" aria-label="[^"]+">\s*(.*?)\s*</section>$~s', $blocks['social'] ?? '', $blockSection ) === 1 && str_contains( $blockSection[1] ?? '', 'id="social-list"' ) === true );

	$withNone	= $compile( [] );
	$featuresOn( [ 'Design' ] );
	$withOff	= $compile( [] );
	$featuresOn( [ 'Social', 'Design' ] );

	check( 'with no look chosen the feature compiles nothing: the sheet of a project that has the feature on is the sheet of one that has it off, byte for byte', $withNone === $withOff && str_contains( $withNone, 'feature social' ) === false );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'social' => [ 'set' => $variant ] ] ], $sheetNotes );
		$ladders		= [];

		foreach( $look['knobs'] as $name => $knob )
			if( $knob['kind'] === 'scale' ) {
				$ladders[] = $name. ' default';

				foreach( $knob['ladder'] ?? [] as $ladder )
					if( str_contains( $sheet, "\n\t--social-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'social', $ladder, 'default' ). ");\n" ) === false )
						$sheetProblems[] = $variant. ' selects no '. $ladder;
			}
			elseif( $knob['kind'] === 'ink' && preg_match( '~\n:root, :root \* \{\n(?:\t[^\n]*\n)*?\t--social-'. preg_quote( $name, '~' ). ': var\('. preg_quote( $designSetup::INKS[ (string) $knob['default'] ] ?? '', '~' ). '\);\n~', $sheet ) !== 1 )
				$sheetProblems[] = $variant. ' writes no ink '. $name;
			elseif( $knob['kind'] === 'ground' && str_contains( $sheet, "\n\t--social-". $name. ': var(--nino-'. $knob['default']. ");\n" ) === false )
				$sheetProblems[] = $variant. ' writes no ground '. $name;

		if( preg_match( '~^/\* ==== \d+\. feature social: '. preg_quote( $variant, '~' ). ' \((.*)\) ==== \*/$~m', $sheet, $title ) !== 1 || $title[1] !== implode( ', ', $ladders ) )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] || str_contains( $sheet, $designCompiler::SHARED ) === true )
			$sheetProblems[] = $variant. ' compiled with a note, or with the shared rules of a style that was not chosen';
	}

	check( 'with a look chosen the sheet carries its section, its stylesheet as the file has it, a line for every ladder of every knob it declares, the tone of its icons and the ground of its badge where it has them - and none of the shared rules of the styles, which stay out where no style is chosen'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ),
		$sheetProblems === [] && $catalogue !== [] );

	// Under Plain nothing is written for the tone: the icon stays the colour of the link, which is the colour of its name; Badges and Outline write the ink of their knob on every element
	$plainSheet = $compile( [ 'features' => [ 'social' => [ 'set' => 'regular' ] ] ] );
	$inkProblems = [];

	if( str_contains( $plainSheet, '--social-icon' ) === true || str_contains( $plainSheet, '--social-badge' ) === true )
		$inkProblems[] = 'regular writes a tone or a ground';

	foreach( [ 'badges', 'outline' ] as $variant )
		if( str_contains( $compile( [ 'features' => [ 'social' => [ 'set' => $variant ] ] ] ), "\n\t--social-icon: var(--color-ink);\n" ) === false )
			$inkProblems[] = $variant. ' writes no tone';

	check( 'Plain compiles no tone for the icons and no ground - the icon is the colour of the link, as its name is - where Badges and Outline write the ink of their knob'. ( $inkProblems === [] ? '' : ' - '. implode( ', ', $inkProblems ) ), $inkProblems === [] );

	$groundSheet = $compile( [ 'features' => [ 'social' => [ 'set' => 'badges', 'knobs' => [ 'badge' => 'dark' ] ] ] ] );

	check( 'a badge moved to another surface is written as the surface and every part of it the stylesheet reads - the colour, the text on it, the quieter one, the link, the line, the focus',
		str_contains( $groundSheet, "\n\t--social-badge: var(--nino-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--social-badge-on: var(--nino-on-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--social-badge-on-muted: var(--nino-on-dark-muted);\n" ) === true
		&& str_contains( $groundSheet, "\n\t--social-badge-link: var(--nino-dark-link);\n" ) === true && str_contains( $groundSheet, "\n\t--social-badge-border: var(--nino-dark-border);\n" ) === true && str_contains( $groundSheet, "\n\t--social-badge-focus: var(--nino-dark-focus);\n" ) === true );

	$styleProblems = [];

	foreach( array_keys( $catalogue ) as $variant )
		foreach( $styled[$styleName]['options'] ?? [] as $option => $declared ) {
			$sheet	= $compile( [ 'features' => [ 'social' => [ 'set' => $variant, 'styles' => [ $styleName => $option ] ] ] ] );
			$shared	= $designSetup::featureShared( $appData, 'social' );
			$at 		= (int) strpos( $sheet, $designCompiler::SHARED );
			$line 	= sprintf( $designCompiler::STYLE, $styleName, $option );

			if( $shared === '' || substr_count( $sheet, $shared ) !== 1 || $at === 0 || strpos( $sheet, $shared ) < $at || strpos( $sheet, $line ) < strpos( $sheet, $shared ) )
				$styleProblems[] = $variant. ' '. $option. ': the shared rules stand once, under their line, before the option';

			if( str_contains( $sheet, rtrim( (string) file_get_contents( (string) $declared['file'] ), "\n" ) ) === false || substr_count( $sheet, $line ) !== 1 )
				$styleProblems[] = $variant. ' '. $option. ': the option is not in the sheet once, as the file has it';
		}

	check( 'a hover chosen puts the transition the options share once into the section of the look, whichever it is, and then the file of the option under its own line - the same for Plain, Badges and Outline'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ), $styleProblems === [] && count( $catalogue ) === 3 );

	// The bundle of a project the wizard has set up lists the sheet of the feature after the theme the Design feature writes
	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ $designCompiler::TARGET, '/assets/style.css' ] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];

	check( 'in a booted project the bundle lists social.css after the theme.css the Design feature writes its sheet to - the order the :where() of its defaults is for',
		in_array( '/features/Social/assets/social.css', $bundle, true ) === true && array_search( '/features/Social/assets/social.css', $bundle, true ) > array_search( $designCompiler::TARGET, $bundle, true ) );

	$appData['/nino/modules']					= $modulesKept;
	$appData['./nino/locales/current']	= $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );

	ninoWarnings();
}

echo "\n";


// --- Components ----------------------------------------------------------------

echo "Components - [social], [social-link], [social-icon] as the Builder offers them\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[social]',
		'[social only="telegram, instagram, nope"]',
		'[social exclude="facebook,telegram"]',
		'[social show="both" size="large"]',
		'[social show="label" size="small"]',
		'[social size="huge" show="all" class="x" onclick="y"]',
		'Follow us on [social-link youtube].',
		'[social-link id="youtube" show="label"]',
		'[social-link nope]',
		'[social-link]',
		'[social-icon telegram]',
		'[social-icon name="telegram"]',
		'[social-icon ../../config]',
		'[social-icon name="../LICENSE"]',
		'[elements /social][social-icon name="[[icon]]"][/elements]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'social', 'social-icon', 'social-link' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Social::init( $probe );
	ninoWarnings();

	check( 'the components are registered with the schema the manifest declares',
		( \Nino\Modules\Components::components( $probe )['social'] ?? null ) === ( $declared['social'] ?? false )
		&& ( \Nino\Modules\Components::components( $probe )['social-link'] ?? null ) === ( $declared['social-link'] ?? false )
		&& ( \Nino\Modules\Components::components( $probe )['social-icon'] ?? null ) === ( $declared['social-icon'] ?? false ) );
	check( '...and what the feature registers is answered once - the components by its wrapper',
		\Nino\Features::shortcodes( $probe, 'social' ) === [ 'social', 'social-icon', 'social-link' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/social' ) ) === 1 && $registered[0] instanceof \Closure
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/social-link' ) ) === 1 && $registered[0] instanceof \Closure
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/social-icon' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'social' ) === [ 'only' => '', 'exclude' => '', 'show' => 'icon', 'size' => '', 'class' => '' ]
		&& \Nino\Modules\Components::defaults( $probe, 'social-link' ) === [ 'id' => '', 'show' => 'both', 'class' => '' ]
		&& \Nino\Modules\Components::defaults( $probe, 'social-icon' ) === [ 'name' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	// The size is a modifier of the list's class, so it is a variant: a tool draws it as a row of buttons, and each word is
	// the class the list is written with
	$size = $declared['social']['attributes']['size'] ?? [];
	check( 'the size is a variant, the empty word and the modifiers the list is written with, and the empty one is the default',
		( $size['type'] ?? null ) === 'variant' && ( $size['options'] ?? null ) === [ '', 'small', 'large' ] && ( $size['default'] ?? null ) === '' );
	check( '...and each of its words is the class nino-social-- and the word, which is what a variant is',
		array_filter( (array) ( $size['options'] ?? [] ), static fn( string $word ): bool => $word !== '' && str_contains( \Nino\Html::renderHtml( $probe, '[social size="'. $word. '"]' ), '<ul class="nino-social nino-social--'. $word. '">' ) === false ) === [] );

	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[social only="" exclude="" show="icon" size=""]' ) === $before['[social]']
		&& \Nino\Html::renderHtml( $probe, '[social-link id="youtube" show="both"]' ) === \Nino\Html::renderHtml( $probe, '[social-link youtube]' ) );
	check( 'the link of a call written with id= and the link of the bare word are one',
		\Nino\Html::renderHtml( $probe, '[social-link id="youtube"]' ) === \Nino\Html::renderHtml( $probe, '[social-link youtube]' )
		&& \Nino\Html::renderHtml( $probe, '[social-link id="youtube"]' ) !== '' );
	check( 'a web link opens in a new tab with rel="me noopener", the Builder\'s component as the shortcode by hand',
		( anchorAttributes( \Nino\Html::renderHtml( $probe, '[social-link id="youtube"]' ), 'https://www.youtube.com/' )['target'] ?? null ) === '_blank'
		&& ( anchorAttributes( \Nino\Html::renderHtml( $probe, '[social-link id="youtube"]' ), 'https://www.youtube.com/' )['rel'] ?? null ) === 'me noopener'
		&& ( anchorAttributes( \Nino\Html::renderHtml( $probe, '[social-link youtube]' ), 'https://www.youtube.com/' )['target'] ?? null ) === '_blank'
		&& ( anchorAttributes( \Nino\Html::renderHtml( $probe, '[social-link youtube]' ), 'https://www.youtube.com/' )['rel'] ?? null ) === 'me noopener' );
	check( 'a class is the call\'s own, on the list, the anchor and the wrapper of the icon, the wrapper\'s way as well',
		str_contains( \Nino\Html::renderHtml( $probe, '[social class="my-class"]' ), '<ul class="nino-social my-class">' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[social size="large" class="my-class"]' ), '<ul class="nino-social nino-social--large my-class">' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[social-link youtube class="my-class"]' ), '<a class="nino-social-link nino-stretch my-class" href=' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[social-link id="youtube" class="my-class"]' ), '<a class="nino-social-link nino-stretch my-class" href=' ) === true
		&& \Nino\Html::renderHtml( $probe, '[social-icon telegram class="my-class"]' ) === '<span class="nino-social-icon my-class" aria-hidden="true">'. trim( (string) file_get_contents( dirname( __DIR__ ). '/icons/telegram.svg' ) ). '</span>'
		&& \Nino\Html::renderHtml( $probe, '[social-icon name="telegram" class="my-class"]' ) === \Nino\Html::renderHtml( $probe, '[social-icon telegram class="my-class"]' ) );
	check( '...and the same through the Components module with the class written out empty',
		\Nino\Html::renderHtml( $probe, '[social class="" only="" ]' ) === $before['[social]'] );

	// The lone link may be the link of a linked card: the schema says so, and the anchor carries the class Nino.css stretches - first after the feature's own, before the call's.
	// The links of the list do not: a list is no candidate, and two links in one card would both cover it
	check( 'the lone link says stretched in the schema the manifest declares, and registers with it; it draws its own anchor, so it says no link - the list and the icon say neither',
		( $declared['social-link']['stretched'] ?? null ) === true && ( $declared['social']['stretched'] ?? null ) === false && ( $declared['social-icon']['stretched'] ?? null ) === false
		&& ( \Nino\Modules\Components::components( $probe )['social-link']['stretched'] ?? null ) === true && ( \Nino\Modules\Components::components( $probe )['social-link']['link'] ?? null ) === false
		&& array_keys( array_filter( \Nino\Modules\Components::components( $probe ), static fn( array $schema ): bool => ( $schema['by'] ?? '' ) === 'social' && $schema['stretched'] === true ) ) === [ 'social-link' ] );
	check( 'the anchor of a lone link carries nino-stretch right after its own class, before the class of the call - as the shortcode by hand and as the component',
		str_contains( \Nino\Html::renderHtml( $probe, '[social-link youtube]' ), '<a class="nino-social-link nino-stretch" href=' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[social-link id="youtube" show="icon" class="my-class"]' ), '<a class="nino-social-link nino-stretch my-class" href=' ) === true
		&& \Nino\Modules\Social::doLinkShortcode( $probe, [ 'id' => 'youtube', 'class' => 'my-class' ] ) === \Nino\Html::renderHtml( $probe, '[social-link id="youtube" class="my-class"]' )
		&& substr_count( \Nino\Html::renderHtml( $probe, '[social-link youtube]' ), 'nino-stretch' ) === 1 );
	check( '...and the links of the list carry none, however the list is called',
		substr_count( \Nino\Html::renderHtml( $probe, '[social show="both" size="large" class="my-class"]' ), '<a class="nino-social-link" href=' ) === 4
		&& str_contains( \Nino\Html::renderHtml( $probe, '[social]' ), 'nino-stretch' ) === false && str_contains( \Nino\Html::renderHtml( $probe, '[social only="youtube"]' ), 'nino-stretch' ) === false
		&& str_contains( \Nino\Html::renderHtml( $probe, '[elements /social][social-icon name="[[icon]]"][/elements]' ), 'nino-stretch' ) === false );
	check( 'a linked card with a lone link and a text holds exactly one anchor of the class, the link of the card; with the list in its place it holds none',
		substr_count( \Nino\Html::renderHtml( $probe, '[card linked="1"][social-link instagram][card-text text="Follow us"][/card]' ), '<a class="nino-social-link nino-stretch" href="https://www.instagram.com/' ) === 1
		&& substr_count( \Nino\Html::renderHtml( $probe, '[card linked="1"][social-link instagram][card-text text="Follow us"][/card]' ), 'nino-stretch' ) === 1
		&& str_contains( \Nino\Html::renderHtml( $probe, '[card linked="1"][social-link instagram][card-text text="Follow us"][/card]' ), '<article class="nino-card nino-card--linked">' ) === true
		&& str_contains( \Nino\Html::renderHtml( $probe, '[card linked="1"][social show="both"][card-text text="Follow us"][/card]' ), 'nino-stretch' ) === false
		&& substr_count( \Nino\Html::renderHtml( $probe, '[card linked="1"][card-title text="Follow us" href="/contact"][social-link instagram][/card]' ), 'nino-stretch' ) === 2 );

} )( $appData );

echo "\n";


// --- 4. Positions ------------------------------------------------------------

echo "\nPositions\n";

\Nino\Elements::insertElement( $appData, '/social/mastodon', [ 'title' => 'Mastodon', 'icon' => 'mastodon', 'link' => 'https://mastodon.social/@example', 'order' => 15 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/website', [ 'title' => 'Website', 'icon' => 'website', 'link' => 'https://first.example/', 'order' => 0 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/mail', [ 'title' => 'Mail', 'icon' => 'mail', 'link' => 'mailto:hello@example.com' ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/phone', [ 'title' => 'Phone', 'icon' => 'phone', 'link' => 'tel:+4930123456' ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/contact', [ 'title' => 'Contact', 'icon' => 'link', 'link' => '/contact', 'order' => 20 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/scheme', [ 'title' => 'Scheme', 'icon' => 'link', 'link' => 'HTTPS://Upper-Scheme.example/', 'order' => 21 ], 'en_US' );

$html = render( $appData, '[social]' );
check( 'a position between two puts a link between them', strpos( $html, 'instagram.com' ) < strpos( $html, 'mastodon.social' ) && strpos( $html, 'mastodon.social' ) < strpos( $html, 'facebook.com' ) );
check( '0 - what the panel saves for an empty field - and none go last, in the order of the type file', strpos( $html, 't.me' ) < strpos( $html, 'first.example' ) && strpos( $html, 'first.example' ) < strpos( $html, 'mailto:' ) );
check( 'mail is a link, not a profile: no rel="me"', str_contains( $html, 'href="mailto:hello@example.com">' ) === true );
foreach( [ [ 'a link on the web', 'mastodon', 'https://mastodon.social/@example', true ], [ 'a path of the website', 'contact', '/contact', false ], [ 'a mailto: link', 'mail', 'mailto:hello@example.com', false ], [ 'a tel: link', 'phone', 'tel:+4930123456', false ], [ 'a link on the web with its scheme in capitals', 'scheme', 'HTTPS://Upper-Scheme.example/', true ] ] as [ $what, $id, $href, $web ] ) {
	$attributes = anchorAttributes( $html, $href );
	check( $what. ( $web === true ? ' opens in a new tab and is rel="me noopener"' : ' is a plain link: no target, no rel' ),
		$attributes !== null && ( $attributes['target'] ?? null ) === ( $web === true ? '_blank' : null ) && ( $attributes['rel'] ?? null ) === ( $web === true ? 'me noopener' : null ) );
	$lone = anchorAttributes( render( $appData, '[social-link '. $id. ']' ), $href );
	check( $what. ' is the same through [social-link], but for the class that makes the lone link the link of a linked card', $attributes !== null && $lone !== null
		&& array_diff_key( $lone, [ 'class' => 1 ] ) === array_diff_key( $attributes, [ 'class' => 1 ] )
		&& ( $lone['class'] ?? null ) === 'nino-social-link nino-stretch' && ( $attributes['class'] ?? null ) === 'nino-social-link' );
}

foreach( [ 'mastodon', 'website', 'mail', 'phone', 'contact', 'scheme' ] as $id )
	\Nino\Elements::deleteElement( $appData, '/social/'. $id, '*' );


// --- 5. What an editor can put in --------------------------------------------

echo "\nWhat an editor can put in\n";

$hostile = [
	'js'			=> 'javascript:alert(1)',
	'jsuc'		=> ' JavaScript:alert(1)',
	'jstab'		=> "java\tscript:alert(1)",
	'data'		=> 'data:text/html,<script>x</script>',
	'vbs'			=> 'vbscript:x',
	'proto'		=> '//evil.example/',
	'slash'		=> "/\t/evil.example/",
	'back'		=> '/\\evil.example/',
	'user'		=> 'https://instagram.com@evil.example/',
	'nohost'	=> 'https:///path',
];
$order = 1;
foreach( $hostile as $id => $url )
	\Nino\Elements::insertElement( $appData, '/social/'. $id, [ 'title' => 'X'. $id, 'icon' => 'mail', 'link' => $url, 'order' => $order++ ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/name', [ 'title' => '"><b>x</b>[social]', 'icon' => '../../config', 'link' => 'https://ok.example/"onmouseover="x', 'order' => 1 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/noname', [ 'title' => '  ', 'icon' => 'website', 'link' => 'https://www.example.org/', 'order' => 2 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/here', [ 'title' => 'Here', 'icon' => 'link', 'link' => '/contact', 'order' => 3 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/query', [ 'title' => 'Query', 'icon' => 'website', 'link' => '/contact?a=1#b', 'order' => 6 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/home', [ 'title' => 'Home', 'icon' => 'website', 'link' => '/', 'order' => 7 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/images', [ 'title' => 'Images', 'icon' => 'website', 'link' => '/images', 'order' => 8 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/Upper', [ 'title' => 'Upper', 'icon' => 'NotASlug', 'link' => 'https://upper.example/', 'order' => 5 ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/social/off', [ 'title' => 'Off', 'icon' => 'link', 'link' => 'https://off.example/', 'order' => 4, 'hidden' => true ], 'en_US' );

$html = render( $appData, '[social]' );
check( 'an address that is not http(s), mailto:, tel: or a path here is dropped', preg_match( '/>X(?:js|jsuc|jstab|data|vbs|proto|slash|back|user|nohost)</', $html ) !== 1
	&& stripos( $html, 'script:' ) === false && str_contains( $html, 'evil.example' ) === false );
check( 'a name is escaped, its bracket neutralised, and nothing renders twice', str_contains( $html, '<b>' ) === false && str_contains( $html, '&#91;social]' ) === true && substr_count( $html, '<ul' ) === 1 );
check( 'a quote in an address stays inside href', str_contains( $html, 'href="https://ok.example/&quot;onmouseover=&quot;x"' ) === true );
check( 'an icon that is no slug of this feature\'s draws the link icon', substr_count( $html, trim( (string) file_get_contents( $dir. '/icons/link.svg' ) ) ) === 3 );
check( 'a link without a name is named by its host', str_contains( $html, '<span class="nino-social-label nino-sr-only">example.org</span>' ) === true );
check( 'a path on this site is kept, without rel="me"', str_contains( $html, 'href="/contact">' ) === true );

// The website at a directory: the path of the website is put behind it, an
// address of another host is not
$appData['/nino/dir'] = '/sub';
$html = render( $appData, '[social]' );
check( 'under a directory a path of the website gets it: /contact is /sub/contact', str_contains( $html, 'href="/sub/contact">' ) === true && str_contains( $html, 'href="/contact">' ) === false );
check( '...a path of a page that is named like a public directory is a page: /images is /sub/images, not a file under /sub/public', str_contains( $html, 'href="/sub/images">' ) === true && str_contains( $html, '/public' ) === false );
check( '...with its query and fragment, and the front page as /sub/', str_contains( $html, 'href="/sub/contact?a=1#b">' ) === true && str_contains( $html, 'href="/sub/">' ) === true );
check( '...in every shortcode that draws the link, and not twice', str_contains( render( $appData, '[social-link here]' ), 'href="/sub/contact">' ) === true
	&& str_contains( render( $appData, '[social only="here" show="label"]' ), 'href="/sub/contact">' ) === true && str_contains( $html, '/sub/sub' ) === false );
check( '...but not an address of another host, mailto: or tel:, which no directory is put in front of', str_contains( $html, 'href="https://upper.example/"' ) === true && str_contains( $html, 'href="/sub' ) === true
	&& substr_count( $html, 'href="/sub' ) === 4 && anchorAttributes( $html, '/sub/contact' ) !== null && isset( anchorAttributes( $html, '/sub/contact' )['target'] ) === false && isset( anchorAttributes( $html, '/sub/contact' )['rel'] ) === false );
$appData['/nino/dir'] = '';
$html = render( $appData, '[social]' );
check( 'at the root nothing is put in front: /contact stays /contact, and /images stays /images', str_contains( $html, 'href="/contact">' ) === true && str_contains( $html, 'href="/images">' ) === true && str_contains( $html, '/public' ) === false );
check( 'a hidden link is left out, wherever it is asked for', str_contains( $html, 'off.example' ) === false
	&& render( $appData, '[social-link off]' ) === '' && render( $appData, '[social only="off"]' ) === '' );
check( 'an id with capitals, which the Elements panel accepts, is drawn and can be asked for', str_contains( $html, 'upper.example' ) === true
	&& str_contains( render( $appData, '[social-link Upper]' ), 'upper.example' ) === true && str_contains( render( $appData, '[social only="Upper"]' ), 'upper.example' ) === true );
check( 'an icon slug cannot leave the icons', render( $appData, '[social-icon ../../config]' ) === \Nino\Modules\Social::icon( $appData, 'link' )
	&& render( $appData, '[social-icon name="../LICENSE"]' ) === \Nino\Modules\Social::icon( $appData, 'link' ) );

// A type file edited by hand: '1' for hidden is hidden too. The element
// cache is dropped as the next request would start without it
$typeFile = (array) \Nino\Filesystem::getFileContent( $appData, '/elements/social.php', [] );
$typeFile['*']['facebook']['hidden'] = '1';
\Nino\Filesystem::putFileContent( $appData, '/elements/social.php', $typeFile );
unset( $appData['./nino/elements/cache'] );
check( 'a hidden written by hand as "1" hides the link as well', str_contains( render( $appData, '[social]' ), 'facebook.com' ) === false );
$typeFile['*']['facebook']['hidden'] = false;
\Nino\Filesystem::putFileContent( $appData, '/elements/social.php', $typeFile );
unset( $appData['./nino/elements/cache'] );

foreach( array_merge( array_keys( $hostile ), [ 'name', 'noname', 'here', 'query', 'home', 'images', 'Upper', 'off' ] ) as $id )
	\Nino\Elements::deleteElement( $appData, '/social/'. $id, '*' );
check( 'with those gone, the four are what is left', substr_count( render( $appData, '[social]' ), '<li ' ) === 4 );


// --- 6. Add-only, an update, other languages ---------------------------------

echo "\nAdd-only, an update, other languages\n";

\Nino\Elements::deleteElement( $appData, '/social/youtube', '*' );
$appData['/nino/features']['social']['version'] = '0.9.0';
\Nino\AppData::writeContentData( $appData, [ '/nino/features' ] );
unset( $appData['./nino/features/all'] );
check( 'an update activates', \Nino\Features::activate( $appData, 'social' ) === true );
check( 'a link the editors deleted stays deleted', \Nino\Elements::getElement( $appData, '/social/youtube', 'en_US' ) === false );

// A project with a type /social and a template of the same name of its own:
// both are kept as they were, and [social] draws nothing it does not understand
$foreign	= "<?php\nreturn [ 'title' => 'Mine', 'model' => [ 'name' => [ 'type' => 'string' ], 'url' => [ 'type' => 'string' ] ], '*' => [ '*' => [], 'a' => [ 'name' => 'A', 'url' => 'https://a.example/' ] ] ];\n";
$ownTpl		= "<p>Our links: [[/project/company/general/name]]</p>\n";
$own = socialSandbox( [ 'de_DE', 'en_US' ], static function( array &$appData ) use ( $foreign, $ownTpl ): void {
	\Nino\Filesystem::forceDir( $appData, '/elements' );
	\Nino\Filesystem::forceDir( $appData, '/templates' );
	file_put_contents( \Nino\Filesystem::path( $appData, '/elements/social.php' ), $foreign );
	file_put_contents( \Nino\Filesystem::path( $appData, '/templates/social-links.tpl' ), $ownTpl );
} );
check( 'a type /social of the project\'s own is kept byte for byte', file_get_contents( \Nino\Filesystem::path( $own, '/elements/social.php' ) ) === $foreign );
check( 'so is a template social-links of its own', file_get_contents( \Nino\Filesystem::path( $own, '/templates/social-links.tpl' ) ) === $ownTpl );
ninoWarnings();
check( '[social] over a model of another shape draws nothing, and warns about nothing', render( $own, '[social]' ) === '' && ninoWarnings() === [] );
\Nino\Filesystem::removeDir( ninoSandboxDir( $own ) );

// A project in French alone: the type file is not tied to a language, so the
// links are named; the unit has no French labels, so none is copied
$fr = socialSandbox( [ 'fr_FR' ] );
$html = render( $fr, '[social show="both"]' );
check( 'fr_FR alone: four named links', substr_count( $html, '<li ' ) === 4 && str_contains( $html, '>Instagram</span>' ) === true );
check( 'fr_FR alone: no [[key]] reaches the page', str_contains( $html, '[[' ) === false && str_contains( $html, '&#91;&#91;' ) === false );
\Nino\Filesystem::removeDir( ninoSandboxDir( $fr ) );


// --- 7. Deactivation -----------------------------------------------------------

echo "\nDeactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'social' ) === true );
$persisted = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
check( 'the class leaves /nino/modules', in_array( '\\Nino\\Modules\\Social', $persisted, true ) === false );
check( 'the type, the template and the labels stay', is_file( \Nino\Filesystem::path( $appData, '/elements/social.php' ) ) === true
	&& is_file( \Nino\Filesystem::path( $appData, '/templates/social-links.tpl' ) ) === true
	&& isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/_admin/elements/field/social/link]]'] ) === true );

// The next request, which no longer loads the class
$next = $appData;
unset( $next['./nino/html/shortcodes'], $next['./nino/callbacks'] );
$next['./nino/html/cache'] = false;
$next['/nino/modules'] = [ '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Template' ];
\Nino\Modules::callModules( $next, 'init' );
check( 'afterwards a [social] left in a template is text - the contract every feature has', render( $next, 'A[social]B' ) === 'A[social]B' );
check( '...the template social-links among them, which is why it has to be emptied first', trim( render( $next, '[template /templates/social-links]' ) ) === '[social]' );

ninoDone( $appData );
