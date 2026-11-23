<?php
declare(strict_types=1);

/**
 *	Nino
 *	menu-smoke.php	The test of the feature Menu: structure and behaviour, one pin each. A menu is a map of keys to entries in
 *									/data/menus.php - the file of Nino 1, read as it is -, and the component menu draws one in the header or the
 *									foot. What is pinned is what it adds: the component and its group, what a menu draws (the link, the button
 *									and the panel of an entry, in the order a keyboard walks them), what a stored menu may hold and what
 *									a visitor is never sent from it (an address that is none, markup of a title, HTML+ that the sanitizer
 *									of the kernel has not cleaned), the panel that writes the file with the rights of the panels that own
 *									the texts and the slots, and the names a menu makes, which no other menu or entry takes. Where node is on
 *									the path the public script is run over a stand-in as well (menu-js-smoke.js).
 *
 *	Usage: php features/Menu/tests/menu-smoke.php
 *	       NINO_ROOT=../nino php features/Menu/tests/menu-smoke.php
 */
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );

$appData = ninoSandbox( 'menu' );
$appData['./nino/filesystem/path'] = $root;
$appData['/nino/dir'] = '/shop';
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Menu' ];

\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
\Nino\Admin\Admin::init( $appData );

// Where the words of the menus are written is the kernel's to say for a feature: below /feature/basis/
$basisWords = \Nino\Features::textRoot( $appData, 'basis' ). 'navigation/';

// The words of the site, as the Text panel writes them: a page's name, a menu's titles, links and an entry's name
$words = [
	'en_US' => [
		'/_nino/webpage/about/name' => 'About', '/_nino/webpage/contact/name' => 'Contact', '/_nino/webpage/nowhere/name' => 'Nowhere',
		'/feature/menu/main/services' => 'Services', '/feature/menu/main/services-1' => 'What we do', '/feature/menu/main/services-2' => 'For every size',
		'/feature/menu/main/services-3' => 'Our blog', '/feature/menu/panel/open' => 'Open the submenu', $basisWords. 'primary' => 'Main', $basisWords. 'footer' => 'Foot', $basisWords. 'menu' => 'Menu',
	],
	'de_DE' => [
		'/_nino/webpage/about/name' => 'Über uns', '/_nino/webpage/contact/name' => 'Kontakt',
		'/feature/menu/main/services' => 'Leistungen', '/feature/menu/main/services-1' => 'Was wir tun', '/feature/menu/panel/open' => 'Untermenü öffnen',
	],
];

foreach( $words as $locale => $fills ) {
	$file = (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	foreach( $fills as $key => $text )
		$file['[['. $key. ']]'] = $text;
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $file );
}

foreach( [ 'about', 'contact', 'legal' ] as $page )
	$appData['/nino/http/routes']['GET://'. $page] = [ 'uri' => '/'. $page, 'body' => '[template /templates/page-'. $page. ']' ];

$appData['/nino/html/images']['/feature/menu/main/services-4'] = [ 'label' => 'Services', 'width' => 600, 'height' => 400, 'filename' => 'feature/menu/main/services-4.600x400.webp', 'alt' => [ 'en_US' => 'A workshop', 'de_DE' => 'Eine Werkstatt' ] ];
$appData['/nino/html/images']['/feature/menu/main/services-6'] = [ 'label' => 'Empty', 'width' => 600, 'height' => 400, 'filename' => null ];
$appData['/nino/html/images']['/feature/menu/main/services-7'] = [ 'label' => 'Emptied', 'width' => 600, 'height' => 400, 'filename' => '' ];

/**
 *	A menu of two entries, the second with a panel of two columns - the data as Nino 1 wrote it
 *
 *	@return 	array
 */
function menuFixture(): array {
	return [
		[ 'id' => 'about', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => null ],
		[ 'id' => 'services', 'kind' => 'link', 'href' => '/services', 'target' => '', 'label' => '/feature/menu/main/services', 'panel' => [ 'columns' => 2, 'cols' => [
			[
				[ 'kind' => 'title', 'key' => '/feature/menu/main/services-1' ],
				[ 'kind' => 'subtitle', 'key' => '/feature/menu/main/services-2' ],
				[ 'kind' => 'page', 'uri' => '/contact', 'label' => '' ],
				[ 'kind' => 'image', 'slot' => '/feature/menu/main/services-4' ],
				[ 'kind' => 'image', 'slot' => '/feature/menu/main/services-6' ],
				[ 'kind' => 'image', 'slot' => '/feature/menu/main/services-7' ],
			],
			[
				[ 'kind' => 'link', 'href' => 'https://example.com/blog?a=1&b=[2]', 'target' => '_blank', 'label' => '/feature/menu/main/services-3' ],
				[ 'kind' => 'html', 'html' => '<p class="own">As <strong>typed</strong></p>' ],
			],
		] ] ],
	];
}

/**
 *	A save of the panel as an editor sends it: with the version of the menu it read, which is the file's as it stands unless the test says another
 */
function menuSave( array &$appData, callable $panel, array $data, string $action ): array {

	if( isset( $data['version'] ) === false )
		$data['version'] = \Nino\Modules\Menu\Admin::state( $appData, (string) ( $data['key'] ?? '' ), \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )[ $data['key'] ?? '' ] ?? null );

	return ninoCall( $appData, $panel, $data, $action );
}

/**
 *	The rules of a stylesheet, the at-rules they stand in said with them: [ 'at' => 'the preludes from outside to inside', 'selector' => ..., 'body' => the declarations ]
 *
 *	@param		string		$css
 *
 *	@return 	array
 */
function menuRules( string $css ): array {

	$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
	$rules = [];
	$stack = [];
	$buffer = '';

	for( $at = 0, $length = strlen( $css ); $at < $length; $at++ ) {

		if( $css[$at] === '{' ) {
			$stack[] = [ trim( (string) preg_replace( '/\s+/', ' ', $buffer ) ), $at + 1 ];
			$buffer = '';
		}
		else if( $css[$at] === '}' ) {

			[ $prelude, $from ] = array_pop( $stack );
			$inner = substr( $css, $from, $at - $from );

			if( str_contains( $inner, '{' ) === false )
				$rules[] = [ 'at' => implode( ' ', array_column( $stack, 0 ) ), 'selector' => $prelude, 'body' => trim( (string) preg_replace( '/\s+/', ' ', $inner ) ) ];

			$buffer = '';
		}
		else
			$buffer .= $css[$at];
	}

	return $rules;
}

/**
 *	What the rules that meet an element declare, as property => value with no blanks, the later rule over the earlier: the rules whose selector names every one of the
 *	classes and the attribute, whatever the order or the blanks, and that stand in a media query of width (or in none)
 *
 *	@param		array			$rules
 *	@param		array			$names				Words the selector has to hold, eg. 'menu--in-row'
 *	@param		bool			$wide					Whether the rule stands under min-width
 *
 *	@return 	array
 */
function menuDeclared( array $rules, array $names, bool $wide = false ): array {

	$flat = static fn( string $text ): string => (string) preg_replace( '/\s+/', '', $text );
	$found = [];

	foreach( $rules as $rule ) {

		$selector = $flat( $rule['selector'] );

		if( str_contains( $selector, '::' ) === true || str_contains( $rule['at'], 'min-width' ) !== $wide || array_filter( $names, static fn( string $name ): bool => str_contains( $selector, $name ) === false ) !== [] )
			continue;

		foreach( explode( ';', $rule['body'] ) as $declaration )
			if( str_contains( $declaration, ':' ) === true ) {

				[ $property, $value ] = explode( ':', $declaration, 2 );
				$found[$flat( $property )] = $flat( $value );
			}
	}

	return $found;
}

function menuStore( array &$appData, array $entries, string $key = 'main' ): void {
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ $key => [ 'entries' => $entries ] ] );
}

// What a page is sent for a source: the request is the page that is shown, the ids a menu counts are a request's
$draw = static function( string $source, string $page = '/legal', string $locale = 'en_US' ) use ( &$appData ): string {

	unset( $appData['./menu/count'], $appData[ \Nino\Components::OPTIONS ] );
	$appData['./nino/http/requests'] = [ [ '/nino/http/response' => [ 'uri' => $page ] ] ];
	\Nino\Locales::useLocale( $appData, $locale );

	return trim( \Nino\Html::renderSource( $appData, $source, \Nino\Html::context( $appData ) ) );
};

// The markup of a drawing, asked for by xpath: the order and the attributes are what is pinned, not the words
$dom = static function( string $html ): \DOMXPath {
	$doc = new \DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?><body>'. $html. '</body>', LIBXML_NOERROR | LIBXML_NOWARNING );
	libxml_clear_errors();
	return new \DOMXPath( $doc );
};

echo "\nThe feature - what the folders say of it\n";

$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, owns /data/menus.php and bundles its one script, which is there',
	is_array( $manifest ) === true && $manifest['key'] === 'menu' && $manifest['version'] === '2.0.0' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && $manifest['data'] === [ '/data/menus.php' ]
	&& $manifest['assets'] === [ '/features/Menu/assets/menu.js' ] && is_file( $dir. '/assets/menu.js' ) === true && $manifest['settings'] === [] );
check( 'the folders are read without a warning', ninoWarnings() === [] && \Nino\Features::componentProblems( $appData, 'menu' ) === [] );

$schema = \Nino\Components::components( $appData )['menu'] ?? [];
$group = \Nino\Components::groups( $appData )['menu'] ?? [];
check( 'the component is registered in its group, with a label in both languages, an icon of the table and a standard part, once in a container',
	( $schema['group'] ?? '' ) === 'menu' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true && in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true
	&& ( $group['part'] ?? false ) === true && ( $schema['once'] ?? false ) === true );
check( 'every attribute has a label in both languages, and the key is a choice among the menus there are', array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( $schema['attributes']['key']['type'] ?? '' ) === 'choice' );
check( 'the group has its three knobs, and the standard part keeps the rule of mixing', array_keys( $group['knobs'] ?? [] ) === [ 'menu.width', 'menu.gap', 'menu.radius' ]
	&& \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/menu/part/part.css' ), [ 'menu' ] ) === [] );
$offered = static fn( array $path ): bool => ( \Nino\Components::offer( $appData, $path, [] )['menu'] ?? null ) === true;
$found = static fn( string $source ): array => \Nino\Components::check( $appData, \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ) ) );
check( 'a tool offers it in the header, in a column of a row of the header and of the footer, and in a column of the footer - in a column of a page it does not, and the check says so',
	$offered( [ 'header-wrap' ] ) === true && $offered( [ 'footer-wrap', 'footer-col' ] ) === true && $offered( [ 'header-wrap', 'section-row', 'section-col' ] ) === true && $offered( [ 'footer-wrap', 'section-row', 'section-col' ] ) === true
	&& $offered( [ 'section-wrap', 'section-row', 'section-col' ] ) === false && in_array( 'menu', \Nino\Components::allowed( $appData, 'section-col' ), true ) === false
	&& $found( '[header-wrap][section-row][section-col][menu][/section-col][/section-row][/header-wrap][footer-wrap][section-row][section-col][menu][/section-col][/section-row][/footer-wrap]' ) === []
	&& $found( '[header-wrap][menu][/header-wrap][footer-wrap][footer-col][menu][/footer-col][/footer-wrap]' ) === []
	&& array_column( array_merge( ...array_values( $found( '[section-wrap][section-row][section-col][menu][/section-col][/section-row][/section-wrap]' ) ) ), 'detail' ) === [ 'menu' ] );

menuStore( $appData, menuFixture() );
check( 'the key is a choice among the first menu and each menu of the data', array_keys( \Nino\Modules\Menu::options( $appData, 'key' ) ) === [ '', 'main' ] );

$blocks = \Nino\Blocks::all( $appData );
check( 'the block is listed with the feature as its origin, a frame, without a missing feature and without a refusal',
	isset( $blocks['menu-header'] ) === true && in_array( 'menu', $blocks['menu-header']['features'] ?? [], true ) === true && ( $blocks['menu-header']['category'] ?? '' ) === 'header' && ( $blocks['menu-header']['side'] ?? '' ) === 'header'
	&& ( $blocks['menu-header']['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );
$demo = $group['demo'] ?? '';
$demoDrawn = $dom( $draw( $demo ) );
check( 'the demo of the group is read without a note and draws the component, in a header of one row: the logo in a column as wide as its content, the menu in the column that fills the rest and stands at its end',
	$demo !== '' && $demoDrawn->query( '//header/div[contains(@class,"section-row")]/div[contains(@class,"section-col--size-auto")]/a[contains(@class,"header-logo")]' )->length === 1
	&& $demoDrawn->query( '//header/div[contains(@class,"section-row")]/div[contains(@class,"section-col--size-fill") and contains(@class,"section-col--align-end")]/nav[contains(concat(" ",@class," ")," menu ")]' )->length === 1 && \Nino\Components::check( $appData, \Nino\Markup::parse( $demo, \Nino\Components::containers( $appData ) ) ) === [] );
$blockTree = \Nino\Markup::parse( (string) file_get_contents( $dir. '/blocks/menu-header/block.tpl' ), \Nino\Components::containers( $appData ) );
$blockDrawn = $dom( $draw( (string) file_get_contents( $dir. '/blocks/menu-header/block.tpl' ) ) );
check( 'the block of the header is the same row - the logo, then the menu - kept at the top, and the register has nothing against it',
	$blockDrawn->query( '//header[contains(@class,"header-wrap--sticky")]/div[contains(@class,"section-row--valign-center")]/div[contains(@class,"section-col--size-auto")]/a[contains(@class,"header-logo")]' )->length === 1
	&& $blockDrawn->query( '//header/div[contains(@class,"section-row")]/div[contains(@class,"section-col--size-fill")]/nav[contains(concat(" ",@class," ")," menu ")]' )->length === 1 && \Nino\Components::check( $appData, $blockTree ) === [] );
check( 'the part is in the style bundle, in the layer of the components', str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.menu-panel' ) === true );

$off = ninoSandbox( 'menu-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[menu]', \Nino\Html::context( $off ) ) === '' );
ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|Modules\\\\Navigation::\$html|nino-menu|nino-sr-only|nino-btn|nino-is-|--color-|--space-[0-9]|--radius-|--fontsize-|Nino\.ui\.|design\/|\/feature\/design/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1: the hook of the Design, the hull of the kernel\'s navigation, the burger of Nino.ui.js, a design folder', $old === [] && is_dir( $dir. '/design' ) === false && method_exists( \Nino\Modules\Menu::class, 'upgrade' ) === false );

echo "\nThe drawing - a menu as Nino 1 wrote it\n";

$header = static fn( string $inner = '[menu key="main"]' ): string => '[header-wrap]'. $inner. '[/header-wrap]';
$html = $draw( $header() );
$x = $dom( $html );
check( 'it is a nav with a details, a button of three bars for the small screen, and a list of the entries that can be drawn',
	$x->query( '//nav[@class="menu"]/details[@class="menu-details"]/summary[@class="menu-burger"]' )->length === 1 && $x->query( '//ul[@class="menu-list"]/li[contains(@class,"menu-item")]' )->length === 2 );

// The names a screen reader reads are the words of Basis, wherever the kernel says Basis keeps them: the nav of the header and the button of three bars, and the nav of the foot
$footing = $dom( $draw( '[footer-wrap][menu key="main"][/footer-wrap]' ) );
check( 'the nav and the button are named by the words of Basis - the main navigation and the menu in the header, the foot navigation in the foot - read below the root the kernel gives them',
	$x->query( '//nav[@class="menu"]' )->item( 0 )?->getAttribute( 'aria-label' ) === 'Main' && $x->query( '//summary[@class="menu-burger"]' )->item( 0 )?->getAttribute( 'aria-label' ) === 'Menu'
	&& $footing->query( '//nav' )->item( 0 )?->getAttribute( 'aria-label' ) === 'Foot' );

// A project that was set up while Basis was a module of the kernel keeps the words of the menus below /module/basis/: where Basis is a feature with none of its own they are read there
$legacy = ninoSandbox( 'menu-legacy' );
$legacy['./nino/filesystem/path'] = $root;
$legacy['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Menu' ];
\Nino\Html::init( $legacy );
\Nino\Features::registerComponents( $legacy );
\Nino\Modules::callModules( $legacy, 'init' );
$legacy['/nino/http/routes']['GET://about'] = [ 'uri' => '/about', 'body' => '' ];
\Nino\Html::addFills( $legacy, [ '/_nino/webpage/about/name' => 'About', '/module/basis/navigation/primary' => 'Mod main', '/module/basis/navigation/menu' => 'Mod menu', '/module/basis/navigation/footer' => 'Mod foot' ], 'en_US' );
menuStore( $legacy, [ [ 'id' => 'about', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => null ] ] );
\Nino\Locales::useLocale( $legacy, 'en_US' );
$old = \Nino\Html::renderSource( $legacy, '[header-wrap][menu key="main"][/header-wrap][footer-wrap][menu key="main"][/footer-wrap]', \Nino\Html::context( $legacy ) );
preg_match_all( '/aria-label="([^"]*)"/', $old, $olds );
check( 'a menu draws the words of Basis from below /module/basis/ where the project keeps them there - the name of the nav in the header and in the foot and of the button - whichever case the kernel is in',
	in_array( 'Mod main', $olds[1], true ) === true && in_array( 'Mod foot', $olds[1], true ) === true && in_array( 'Mod menu', $olds[1], true ) === true );

$buttons = $x->query( '//li[contains(@class,"menu-item--panel")]/button[@class="menu-toggle"]' );
$link = $x->query( '//li[contains(@class,"menu-item--panel")]/a[@class="menu-link"]' )->item( 0 );
$panel = $x->query( '//li[contains(@class,"menu-item--panel")]/div[@class="menu-panel"]' )->item( 0 );
check( 'an entry with a panel is its link, then a button - which says it is closed, what it controls and is named by the entry - then the panel: the order a keyboard walks',
	$buttons->length === 1 && $link !== null && $panel !== null && $buttons->item( 0 )->getAttribute( 'aria-expanded' ) === 'false' && $buttons->item( 0 )->getAttribute( 'type' ) === 'button'
	&& $buttons->item( 0 )->getAttribute( 'aria-controls' ) === $panel->getAttribute( 'id' ) && str_contains( $buttons->item( 0 )->textContent, trim( $link->textContent ) ) === true && trim( $link->textContent ) !== ''
	&& $buttons->item( 0 )->previousSibling === $link && $buttons->item( 0 )->nextSibling === $panel );
check( 'an entry with no panel is its link alone', $x->query( '//li[not(contains(@class,"menu-item--panel"))]/*' )->length === 1 && $x->query( '//li[not(contains(@class,"menu-item--panel"))]/a[@href="/shop/about"]' )->length === 1 );
check( 'the panel has the columns the data says, and the parts in their order: title, subtitle, a page, the picture that has a file - the empty slots draw nothing',
	$x->query( '//div[contains(@class,"menu-cols--2")]/div[@class="menu-col"]' )->length === 2
	&& implode( ' ', array_map( static fn( \DOMNode $n ): string => $n->nodeName. '.'. ( $n instanceof \DOMElement ? $n->getAttribute( 'class' ) : '' ), iterator_to_array( $x->query( '(//div[@class="menu-col"])[1]/*' ) ) ) ) === 'p.menu-title p.menu-subtitle a.menu-link menu-link--sub img.menu-image'
	&& $x->query( '//img[@class="menu-image"][@width="600"][@height="400"]' )->length === 1 && $x->query( '(//div[@class="menu-col"])[2]/*' )->length === 2 );
check( 'the links of the panel are the page\'s own address with the directory of the project in front, and an address as it was typed', $x->query( '//a[@href="/shop/contact"]' )->length === 1
	&& $x->query( '//a[starts-with(@href,"https://example.com/blog?a=1&b=")]' )->length === 1 );
check( 'a link that opens a new window says rel noopener, one that does not says nothing', $x->query( '//a[@target="_blank"][@rel="noopener"]' )->length === 1 && $x->query( '//a[not(@target)][@rel]' )->length === 0 );
check( 'the words are filled and read once: no bracket of a fill is left on the page, and the word of the button is the feature\'s', str_contains( $html, '[[' ) === false && str_contains( $html, 'Open the submenu' ) === true );

check( 'the entry of the page that is shown has aria-current, and the entry whose panel holds that page is marked as the current one',
	$dom( $draw( $header(), '/about' ) )->query( '//a[@href="/shop/about"][@aria-current="page"]' )->length === 1
	&& $dom( $draw( $header(), '/contact' ) )->query( '//li[contains(@class,"menu-item--current")]//a[@aria-current="page"]' )->length === 1 && str_contains( $html, 'aria-current' ) === false );

$german = $draw( $header(), '/legal', 'de_DE' );
check( 'the words are the visitor\'s language, and a text that only another language has is said to be of that language', str_contains( $german, '>Leistungen<' ) === true && str_contains( $german, '>Über uns<' ) === true
	&& str_contains( $german, '>Our blog<' ) === true && preg_match( '/<a class="menu-link menu-link--sub" href="https:[^>]*lang="en-US">Our blog<\/a>/', $german ) === 1 );

$twice = $draw( '[header-wrap][menu][/header-wrap][footer-wrap][footer-col][menu][/footer-col][/footer-wrap]' );
preg_match_all( '/ id="([^"]+)"/', $twice, $ids );
check( 'a second menu of the key on the page - the foot - has ids of its own, and in the foot the list is open and has no burger of its own',
	count( $ids[1] ) === count( array_unique( $ids[1] ) ) && str_contains( $twice, 'id="menu-main--2"' ) === true && str_contains( $twice, '<nav class="menu menu--footer"' ) === true && substr_count( $twice, '<details class="menu-details" open>' ) === 1 );

// Where a menu stands says how it is drawn, and the register says where it stands: the foot is the menu whose outermost component is the footer - or whose place is the column of the markup that has the parts in the wrap -, a menu below a column of the header is in a row, and no call can say either
$form = static function( string $source ) use ( $draw, $dom ): array {

	$nav = $dom( $draw( $source ) )->query( '//nav[contains(concat(" ",@class," ")," menu ")]' );

	return $nav->length !== 1 ? [ 'drawn' => $nav->length ] : [ 'classes' => array_values( array_diff( explode( ' ', $nav->item( 0 )->getAttribute( 'class' ) ), [ 'menu' ] ) ), 'open' => $dom( $draw( $source ) )->query( '//nav//details[@open]' )->length === 1 ];
};
$cell = '[section-row][section-col]%s[/section-col][/section-row]';
check( 'the form follows the outermost component and, for the old markup, the place: the foot is open with no button in a column of a footer of rows, in the column of the old markup and in a box inside a column, a menu in a column of the header of rows or in a box there is in a row, and a menu in the header itself is neither',
	$form( '[header-wrap][menu][/header-wrap]' ) === [ 'classes' => [], 'open' => false ]
	&& $form( '[header-wrap]'. sprintf( $cell, '[menu]' ). '[/header-wrap]' ) === [ 'classes' => [ 'menu--in-row' ], 'open' => false ]
	&& $form( '[header-wrap]'. sprintf( $cell, '[button-group][menu][/button-group]' ). '[/header-wrap]' ) === [ 'classes' => [ 'menu--in-row' ], 'open' => false ]
	&& $form( '[footer-wrap][footer-col][menu][/footer-col][/footer-wrap]' ) === [ 'classes' => [ 'menu--footer' ], 'open' => true ]
	&& $form( '[footer-wrap]'. sprintf( $cell, '[menu]' ). '[/footer-wrap]' ) === [ 'classes' => [ 'menu--footer' ], 'open' => true ]
	&& $form( '[footer-wrap]'. sprintf( $cell, '[button-group][menu][/button-group]' ). '[/footer-wrap]' ) === [ 'classes' => [ 'menu--footer' ], 'open' => true ] );
// The form follows the side of the outermost component, as its schema says it - a header root and a footer root of another feature, named like no root of the kernel's, are drawn as a header and a footer are
foreach( [ 'probe-head' => 'header', 'probe-foot' => 'footer' ] as $rootName => $side )
	\Nino\Components::addComponent( $appData, $rootName, [ 'label' => $rootName, 'in' => [ 'page' ], 'offers' => [ 'flow' ], 'block' => $side, 'attributes' => [], 'template' => '<div class="[[class]]">[[content]]</div>' ] );

check( 'the form follows the side of the outermost component, whatever its name: in the root of a footer of another feature the menu is the foot - open, no button - in a column of the root of a header it is in a row, and in the root of a header itself it is neither',
	$form( '[probe-foot][menu][/probe-foot]' ) === [ 'classes' => [ 'menu--footer' ], 'open' => true ] && $form( '[probe-foot]'. sprintf( $cell, '[menu]' ). '[/probe-foot]' ) === [ 'classes' => [ 'menu--footer' ], 'open' => true ]
	&& $form( '[probe-head]'. sprintf( $cell, '[menu]' ). '[/probe-head]' ) === [ 'classes' => [ 'menu--in-row' ], 'open' => false ] && $form( '[probe-head][menu][/probe-head]' ) === [ 'classes' => [], 'open' => false ] );

check( 'SAFETY: a call cannot make itself the foot or a row - the class, the place and the root it names are no attributes of the component, the words of its key and its name say nothing of it, and a menu that stands in a column of the header stays what the register says it is - and a context that names a place and no root is told by the place',
	$form( '[header-wrap]'. sprintf( $cell, '[menu class="menu--footer" place="footer-col" root="footer-wrap" open label="footer-col" key="footer"]' ). '[/header-wrap]' ) === [ 'classes' => [ 'menu--in-row' ], 'open' => false ]
	&& $form( '[footer-wrap]'. sprintf( $cell, '[menu class="menu--in-row" root="header-wrap"]' ). '[/footer-wrap]' ) === [ 'classes' => [ 'menu--footer' ], 'open' => true ]
	&& str_contains( \Nino\Modules\Menu::componentMenu( $appData, [ 'key' => '', 'label' => '', 'class' => 'menu' ], '', [ 'place' => 'footer-col' ] ), 'menu--footer' ) === true
	&& str_contains( \Nino\Modules\Menu::componentMenu( $appData, [ 'key' => '', 'label' => '', 'class' => 'menu' ], '', [ 'place' => 'section-col' ] ), 'menu--' ) === false );

// The header of the kernel makes a menu an overlay over the window, and holds the page behind it, when its details says data-header-menu: the menu of the header says it, the one of the foot does not
check( 'the details of a menu in the header says data-header-menu - in the old markup, in a column of a row and in a box there - which is how the header of the kernel knows a menu it folds; the one in the foot, which is open and has no button, does not say it',
	$dom( $draw( $header() ) )->query( '//nav/details[@class="menu-details"][@data-header-menu]' )->length === 1
	&& $dom( $draw( $header( sprintf( $cell, '[menu]' ) ) ) )->query( '//nav[contains(@class,"menu--in-row")]/details[@data-header-menu]' )->length === 1
	&& $dom( $draw( $header( sprintf( $cell, '[button-group][menu][/button-group]' ) ) ) )->query( '//nav[contains(@class,"menu--in-row")]/details[@data-header-menu]' )->length === 1
	&& $dom( $draw( '[footer-wrap][footer-col][menu][/footer-col][/footer-wrap]' ) )->query( '//details[@data-header-menu]' )->length === 0
	&& $dom( $draw( '[footer-wrap]'. sprintf( $cell, '[menu]' ). '[/footer-wrap]' ) )->query( '//details[@data-header-menu]' )->length === 0 );
check( 'SAFETY: a call cannot put the mark into the foot or take it out of the header - it is no attribute of the component',
	$dom( $draw( '[footer-wrap][footer-col][menu data-header-menu="1"][/footer-col][/footer-wrap]' ) )->query( '//*[@data-header-menu]' )->length === 0
	&& $dom( $draw( $header( '[menu data-header-menu=""]' ) ) )->query( '//details[@data-header-menu=""]' )->length === 1 );

menuStore( $appData, [ [ 'id' => 'gone', 'kind' => 'page', 'uri' => '/nowhere', 'label' => '', 'panel' => null ], [ 'id' => 'mute', 'kind' => 'link', 'href' => '/x', 'target' => '', 'label' => '/feature/menu/main/mute', 'panel' => null ] ] );
check( 'a page with no route, and a link nobody wrote a word for, are no links: a menu with nothing to draw is not drawn', $draw( $header() ) === '<header class="header-wrap"><div class="header-wrap-inner"></div></header>' || str_contains( $draw( $header() ), 'menu-list' ) === false );

echo "\nWhat a visitor is never sent\n";

// An address: the check of Nino 1, kept and pinned
$bad = [ 'javascript:alert(1)', 'JaVaScRiPt:alert(1)', "java\nscript:alert(1)", ' javascript:alert(1)', 'data:text/html;base64,PHNjcmlwdD4=', 'vbscript:x', '//evil.example/x', '/\\evil.example', '/a/../../b', '/%2e%2e/b', 'https://', 'ftp://example.com/x', 'mailto:', 'tel:abc', "/a b", "/a\0b", '' ];
$good = [ '/contact' => '/shop/contact', '#top' => '#top', 'https://example.com/a?b=1' => 'https://example.com/a?b=1', 'http://example.com' => 'http://example.com', 'mailto:me@example.com' => 'mailto:me@example.com', 'tel:+49 1' => '', 'tel:+491234' => 'tel:+491234' ];
check( 'SAFETY: an address is a path of the site, a fragment, or one with https, http, mailto or tel: no other scheme, no address without a host, no path that climbs, no space and no null byte',
	array_filter( $bad, static fn( string $href ): bool => \Nino\Modules\Menu::address( $href, '/shop' ) !== '' ) === []
	&& array_filter( $good, static fn( string $want, string $href ): bool => \Nino\Modules\Menu::address( $href, '/shop' ) !== $want, ARRAY_FILTER_USE_BOTH ) === [] );

// An address a person wrote by hand into the file is drawn as nothing a browser can follow
$hostile = [ [ 'id' => 'ev', 'kind' => 'link', 'href' => 'javascript:alert(1)', 'target' => '', 'label' => '/feature/menu/main/ev', 'panel' => [ 'columns' => 1, 'cols' => [ [
	[ 'kind' => 'link', 'href' => 'data:text/html;base64,PHNjcmlwdD4=', 'target' => '_blank', 'label' => '/feature/menu/main/ev-1' ],
	[ 'kind' => 'link', 'href' => '//evil.example/x', 'target' => '', 'label' => '/feature/menu/main/ev-2' ],
] ] ] ] ];
\Nino\Html::addFills( $appData, [ '/feature/menu/main/ev' => '[csrf] <b>"x"</b> [[/project/company/general/name]]', '/feature/menu/main/ev-1' => '<img src=x onerror=alert(1)>', '/feature/menu/main/ev-2' => 'Two' ], '*' );
menuStore( $appData, $hostile );
$page = $draw( $header() );
check( 'SAFETY: an address that is none is drawn as # and never as itself, in the entry and in the panel', preg_match( '/javascript:|data:text|\/\/evil/i', $page ) === 0 && substr_count( $page, 'href="#"' ) === 3 );
check( 'SAFETY: the name of an entry and the words of a part are text: a call, a fill and a tag they spell are written as the text they are', str_contains( $page, '<b>' ) === false && str_contains( $page, '<img' ) === false && str_contains( $page, '&lt;b&gt;' ) === true
	&& str_contains( $page, '&#91;csrf&#93;' ) === true && str_contains( $page, '&#91;&#91;/project/company/general/name&#93;&#93;' ) === true && str_contains( $page, '[csrf]' ) === false );

// HTML+ goes through the sanitizer of the kernel when it is drawn, whatever the file holds
$dirty = '<p onclick="x()">Hello <a href="javascript:alert(1)">bad</a> <a href="https://example.com/ok">ok</a> [csrf] [[/project/company/general/name]]<script>alert(1)</script><img src=x onerror=y></p>';
menuStore( $appData, [ [ 'id' => 'h', 'kind' => 'link', 'href' => '/h', 'target' => '', 'label' => '/feature/menu/main/h', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'html', 'html' => $dirty ] ] ] ] ] ] );
\Nino\Html::addFills( $appData, [ '/feature/menu/main/h' => 'H' ], '*' );
$page = $draw( $header() );
check( 'SAFETY: the HTML+ of a part is what the sanitizer of the kernel keeps of it: no script, no handler, no unsafe address, no image - and a call or a fill in it is text',
	str_contains( $page, '<script' ) === false && str_contains( $page, 'onclick' ) === false && str_contains( $page, 'javascript:' ) === false && str_contains( $page, '<img' ) === false && str_contains( $page, 'onerror' ) === false
	&& str_contains( $page, 'href="https://example.com/ok"' ) === true && str_contains( $page, '&#91;csrf]' ) === true && str_contains( $page, '[csrf]' ) === false && str_contains( $page, '[[/project' ) === false );

// A picture is a slot of the menu, never a file name
$slotless = [ [ 'id' => 'p', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'image', 'slot' => '../../../etc/passwd' ] ] ] ] ] ];
menuStore( $appData, $slotless );
$problems = [];
\Nino\Modules\Menu::entries( 'main', $slotless, $problems );
check( 'SAFETY: the picture of a part is a slot of this menu and this entry, never a file name or a path: the entry is refused when it is written and is not drawn when it is read',
	count( $problems ) === 1 && str_contains( $draw( $header() ), 'passwd' ) === false && str_contains( $draw( $header() ), 'menu-item' ) === false );

// What is hand-written and broken draws nothing and throws nothing
$broken = [ 'a string', 5, null, [ 'entries' => 'x' ], [ 'entries' => [ 'x', 5, [ 'id' => 'Bad Id' ], [ 'id' => 'a', 'kind' => 'page' ], [ 'id' => 'b', 'kind' => 'link', 'href' => [ 'x' ], 'label' => 'l' ], [ 'id' => 'c', 'kind' => 'page', 'uri' => '/about', 'label' => 'x', 'panel' => [ 'columns' => 9, 'cols' => 'x' ] ] ] ] ];
$survived = 0;
foreach( $broken as $one ) {
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ 'main' => $one, 'Not A Key' => [ 'entries' => menuFixture() ], 'panel' => [ 'entries' => menuFixture() ] ] );
	$survived += $draw( $header() ) !== '' && str_contains( $draw( $header() ), 'menu-list' ) === false ? 1 : 0;
	\Nino\Modules\Menu::menus( $appData );
}
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ 'main' => [ 'entries' => 'x' ] ] );
$noMap = [ 'x', 5 ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, $noMap );
$survived += \Nino\Modules\Menu::menus( $appData ) === [] ? 1 : 0;
check( 'a menus.php written by hand and broken - no map, a menu that is none, entries that are no list, an id that is no slug, a panel of nine columns, a key that is no key or is the word of the button - draws nothing and throws nothing',
	$survived === count( $broken ) + 1 && ninoWarnings() === [] );

menuStore( $appData, menuFixture() );
$many = [];
for( $i = 0; $i < \Nino\Modules\Menu::MAX_MENUS + 3; $i++ )
	$many['m'. chr( 97 + $i )] = [ 'entries' => [] ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, $many );
check( 'SAFETY: at most as many menus are read as a menu file may hold', count( \Nino\Modules\Menu::menus( $appData ) ) === \Nino\Modules\Menu::MAX_MENUS );
menuStore( $appData, menuFixture() );

echo "\nThe panel - what it writes\n";

$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoUser( $appData, 'dev@example.com', [ '/_admin/menu/manage', '/_admin/keys/manage', '/_admin/slots/manage', '/_admin/elements/manage' ] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
ninoWarnings();

$register = \Nino\Modules\Menu\Admin::class;
check( 'the panel is the feature\'s: a developer permission, three panes, the rail sign and a nav, and the feature answers for it', \Nino\Modules\Menu::adminPanels( $appData ) === [ $register ] && $register::perm() === '/_admin/menu/manage'
	&& $register::panes() === [ 'menu-list', 'menu-form', 'menu-entry' ] && str_contains( $register::icon(), '<svg' ) === true && $register::nav()[0] === 'menu' && in_array( '/_admin/assets/html-editor.js', $register::assets(), true ) === true );

$texts = [ '/feature/menu/main/home' => [ 'de_DE' => 'Start', 'en_US' => 'Home' ], '/feature/menu/main/shop' => [ 'de_DE' => 'Laden', 'en_US' => 'Shop' ], '/feature/menu/main/shop-1' => [ 'de_DE' => 'Neu', 'en_US' => 'New' ] ];
$entries = [
	[ 'id' => 'home', 'kind' => 'page', 'uri' => '/about', 'label' => '/feature/menu/main/home', 'panel' => null ],
	[ 'id' => 'shop', 'kind' => 'link', 'href' => '/shop-all', 'target' => '', 'label' => '/feature/menu/main/shop', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'title', 'key' => '/feature/menu/main/shop-1' ] ] ] ] ],
];
[ $status ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => $entries, 'texts' => $texts ], 'menu/save' );
$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
check( 'a menu is saved whole: the file holds the entries in the shape of Nino 1, the texts are text keys of both languages, and the menu is drawn from what was written',
	$status === 200 && $stored === [ 'main' => [ 'entries' => $entries ] ] && \Nino\Text::entries( $appData ) !== []
	&& str_contains( $draw( $header() ), '>Home<' ) === true && str_contains( $draw( $header(), '/legal', 'de_DE' ), '>Laden<' ) === true );

[ $status, $loaded ] = ninoCall( $appData, $panel, [ 'key' => 'main' ], 'menu/load' );
check( 'a menu is read with its texts in every language and the names its key has made', $status === 200 && $loaded['entries'] === $entries && array_keys( (array) $loaded['texts'] ) === array_keys( $texts )
	&& in_array( '/feature/menu/main/shop-1', $loaded['taken'], true ) === true );
[ , $list ] = ninoCall( $appData, $panel, [], 'menu/list' );
check( 'the table lists each menu with its entries and its panels, and the pages and languages the editor needs', array_column( $list['menus'], 'entries', 'key' ) === [ 'main' => 2 ] && array_column( $list['menus'], 'panels', 'key' ) === [ 'main' => 1 ]
	&& isset( $list['pages'], $list['locales'], $list['slots'] ) === true && $list['limit'] === \Nino\Modules\Menu::MAX_MENUS );

// A second menu is added to the file and the first stays
[ $status ] = menuSave( $appData, $panel, [ 'key' => 'foot', 'entries' => [], 'texts' => [] ], 'menu/save' );
check( 'a save changes its menu and no other, and the menus stand in the order they were made', $status === 200 && array_keys( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === [ 'main', 'foot' ] );

echo "\nThe panel - what it refuses\n";

// Every action of the panel needs the permission of the panel: none without an account, 403 for another permission, and nothing is written
$answers = [];
foreach( [ 'menu/list' => [], 'menu/registry' => [], 'menu/load' => [ 'key' => 'main' ], 'menu/save' => [ 'key' => 'evil', 'entries' => [], 'texts' => [] ], 'menu/delete' => [ 'key' => 'main' ] ] as $action => $data ) {
	$loggedOut = $appData;
	$loggedOut['./nino/auth/current'] = null;
	$other = $appData;
	ninoUser( $other, 'nobody@example.com', [ '/_admin/keys/manage', '/_admin/other/manage' ] );
	$answers[$action] = [ ninoCall( $loggedOut, $panel, $data, $action )[0], ninoCall( $other, $panel, $data, $action )[0] ];
}
check( 'SAFETY: every action of the panel needs its permission - 401 without an account, 403 with the rights of another panel - and the file is as it was', array_filter( $answers, static fn( array $a ): bool => $a !== [ 401, 403 ] ) === []
	&& array_keys( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === [ 'main', 'foot' ] );

// The key of a menu is a slug of the grammar, and it is no key a text of the feature has
$keys = [ '../x', 'A', 'a b', 'a--b', 'a-', '1a', 'panel', str_repeat( 'a', 33 ), '', "a\0b", 'a/b' ];
$accepted = array_filter( $keys, static function( string $key ) use ( &$appData, $panel ): bool {
	$one = [ 'key' => $key, 'entries' => [], 'texts' => [] ];
	return in_array( 200, [ menuSave( $appData, $panel, $one, 'menu/save' )[0], menuSave( $appData, $panel, [ 'key' => $key ], 'menu/load' )[0], menuSave( $appData, $panel, [ 'key' => $key ], 'menu/delete' )[0] ], true );
} );
check( 'SAFETY: a menu key is words of lower case letters and digits joined by single hyphens, at most 32, and not "panel": saving, reading and deleting refuse every other', $accepted === [] && count( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === 2 );

// An address, a name, an id, a kind and a slot that the entries must not hold
$mutate = static fn( callable $change ): array => ( static function( array $entries ) use ( $change ): array { $change( $entries ); return $entries; } )( copyOf( $entries ) );
function copyOf( array $value ): array { return json_decode( (string) json_encode( $value ), true ); }
$refused = [];
foreach( [
	'script address'	=> static function( array &$e ): void { $e[1]['href'] = 'javascript:alert(1)'; },
	'host-less path'	=> static function( array &$e ): void { $e[1]['href'] = '//evil.example'; },
	'foreign label'		=> static function( array &$e ): void { $e[1]['label'] = '/template/common/form/submit'; },
	'id with a number'	=> static function( array &$e ): void { $e[1]['id'] = 'shop-2'; },
	'id list'					=> static function( array &$e ): void { $e[1]['id'] = 'list'; },
	'same id twice'		=> static function( array &$e ): void { $e[1]['id'] = 'home'; },
	'kind'						=> static function( array &$e ): void { $e[1]['kind'] = 'script'; },
	'item name'				=> static function( array &$e ): void { $e[1]['panel']['cols'][0][0]['key'] = '/template/common/form/submit'; },
	'slot is a file'	=> static function( array &$e ): void { $e[1]['panel']['cols'][0][] = [ 'kind' => 'image', 'slot' => 'logo.png' ]; },
	'kind of a part'	=> static function( array &$e ): void { $e[1]['panel']['cols'][0][0]['kind'] = 'shortcode'; },
	'five columns'		=> static function( array &$e ): void { $e[1]['panel']['columns'] = 5; $e[1]['panel']['cols'] = [ [], [], [], [], [] ]; },
	'25 parts'				=> static function( array &$e ): void { for( $i = 0; $i < 25; $i++ ) $e[1]['panel']['cols'][0][] = [ 'kind' => 'html', 'html' => 'x' ]; },
	'huge html'				=> static function( array &$e ): void { $e[1]['panel']['cols'][0][] = [ 'kind' => 'html', 'html' => str_repeat( 'a', \Nino\Modules\Menu::MAX_HTML + 1 ) ]; },
	'25 entries'			=> static function( array &$e ): void { for( $i = 0; $i < 25; $i++ ) $e[] = [ 'id' => 'e'. chr( 97 + $i ), 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => null ]; },
] as $name => $change ) {
	$wrong = copyOf( $entries );
	$change( $wrong );
	[ $status ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => $wrong, 'texts' => $texts ], 'menu/save' );
	if( $status !== 400 )
		$refused[] = $name. ' '. $status;
}
check( 'SAFETY: a save is refused (400) for an address that is none, a name that is not the entry\'s own, an id that is a number or taken, a kind and a part that is none, a slot that is a file, too many columns, parts, entries and HTML+ that is too long',
	$refused === [] && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['main']['entries'] === $entries );

// A text for a key that is not the menu's is no text of the menu
[ $status ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => $entries, 'texts' => $texts + [ '/template/common/form/submit' => [ 'en_US' => 'HIJACKED' ] ] ], 'menu/save' );
$known = array_column( \Nino\Text::entries( $appData ), null, 'key' );
check( 'SAFETY: a text that is posted for a key the menu does not name is refused, and what another part of the site says stays as it was', $status === 400 && ( $known['/template/common/form/submit']['values']['en_US'] ?? '' ) !== 'HIJACKED' );

// What HTML+ is written is what the sanitizer keeps, and bringing it asks for the right of the panel where rich text is written
$withHtml = copyOf( $entries );
$withHtml[1]['panel']['cols'][0][] = [ 'kind' => 'html', 'html' => '<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:x">l</a><strong>b</strong></p>' ];
$withoutRight = $appData;
ninoUser( $withoutRight, 'writer@example.com', [ '/_admin/menu/manage', '/_admin/keys/manage', '/_admin/slots/manage' ] );
[ $denied ] = menuSave( $withoutRight, $panel, [ 'key' => 'main', 'entries' => $withHtml, 'texts' => $texts ], 'menu/save' );
check( 'SAFETY: HTML+ that nobody had before asks for the permission of the Elements panel, where the kernel lets a person write rich text: 403 without it, and the file is as it was',
	$denied === 403 && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['main']['entries'] === $entries );
[ $status ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => $withHtml, 'texts' => $texts ], 'menu/save' );
$written = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['main']['entries'][1]['panel']['cols'][0][1]['html'] ?? '';
check( 'SAFETY: the HTML+ that is written is the sanitizer\'s answer - no script, no handler, no address that runs - and is the same when it is saved again',
	$status === 200 && str_contains( $written, '<script' ) === false && str_contains( $written, 'onclick' ) === false && str_contains( $written, 'javascript:' ) === false && str_contains( $written, '<strong>b</strong>' ) === true
	&& $written === \Nino\Html::sanitizeHtml( $written, 'blocks' ) );
[ $again ] = menuSave( $withoutRight, $panel, [ 'key' => 'main', 'entries' => \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['main']['entries'], 'texts' => $texts ], 'menu/save' );
check( 'HTML+ that the menu holds already is no new HTML+: saving it again needs no such permission', $again === 200 );

// The permissions of the panels that own the keys and the slots
$noKeys = $appData;
ninoUser( $noKeys, 'nokeys@example.com', [ '/_admin/menu/manage', '/_admin/slots/manage' ] );
$fresh = [ [ 'id' => 'new', 'kind' => 'link', 'href' => '/n', 'target' => '', 'label' => '/feature/menu/news/new', 'panel' => null ] ];
[ $keyless ] = menuSave( $noKeys, $panel, [ 'key' => 'news', 'entries' => $fresh, 'texts' => [ '/feature/menu/news/new' => [ 'en_US' => 'New' ] ] ], 'menu/save' );
$noSlots = $appData;
ninoUser( $noSlots, 'noslots@example.com', [ '/_admin/menu/manage', '/_admin/keys/manage' ] );
$pictured = [ [ 'id' => 'pic', 'kind' => 'link', 'href' => '/n', 'target' => '', 'label' => '/feature/menu/news/pic', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'image', 'slot' => '/feature/menu/news/pic-1' ] ] ] ] ] ];
[ $slotless ] = menuSave( $noSlots, $panel, [ 'key' => 'news', 'entries' => $pictured, 'texts' => [ '/feature/menu/news/pic' => [ 'en_US' => 'P' ] ] ], 'menu/save' );
check( 'SAFETY: a save that makes a text key asks for the permission of the Text Keys, one that makes a slot for that of the Image Slots: 403, nothing is made and nothing is written',
	$keyless === 403 && $slotless === 403 && array_key_exists( 'news', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === false
	&& array_filter( \Nino\Text::entries( $appData ), static fn( array $e ): bool => str_starts_with( (string) $e['key'], '/feature/menu/news/' ) ) === [] );

[ $status, $made ] = menuSave( $appData, $panel, [ 'key' => 'news', 'entries' => $pictured, 'texts' => [ '/feature/menu/news/pic' => [ 'en_US' => 'P', 'de_DE' => 'P' ] ] ], 'menu/save' );
check( 'a picture is a slot the menu makes with the save, of the size of its place, and its text key is made with it', $status === 200 && $made['made'] === [ 'keys' => [ '/feature/menu/news/pic' ], 'slots' => [ '/feature/menu/news/pic-1' ] ]
	&& ( \Nino\Images::getSlot( $appData, '/feature/menu/news/pic-1' )['width'] ?? 0 ) === \Nino\Modules\Menu\Admin::SLOT_WIDTH );

echo "\nThe names - no menu and no entry takes another's\n";

// Delete the menu and make one of the same key: the texts and the slots of the first stay in the project, and the second must not take them
[ $status ] = ninoCall( $appData, $panel, [ 'key' => 'news' ], 'menu/delete' );
$stays = array_filter( \Nino\Text::entries( $appData ), static fn( array $e ): bool => $e['key'] === '/feature/menu/news/pic' ) !== [] && \Nino\Images::getSlot( $appData, '/feature/menu/news/pic-1' ) !== false;
[ $again, $body ] = menuSave( $appData, $panel, [ 'key' => 'news', 'entries' => $fresh === [] ? [] : [ [ 'id' => 'pic', 'kind' => 'link', 'href' => '/n', 'target' => '', 'label' => '/feature/menu/news/pic', 'panel' => null ] ], 'texts' => [ '/feature/menu/news/pic' => [ 'en_US' => 'Evil' ] ] ], 'menu/save' );
[ , $fresher ] = ninoCall( $appData, $panel, [ 'key' => 'news', 'fresh' => true ], 'menu/load' );
$own = ( $known = array_column( \Nino\Text::entries( $appData ), null, 'key' ) )['/feature/menu/news/pic']['values']['en_US'] ?? '';
check( 'SAFETY: a menu that is deleted keeps its texts and its slots, and a menu made again under its key is refused the names the first made (409) - and is told them, so that it chooses others',
	$status === 200 && $stays === true && $again === 409 && $own === 'P' && in_array( '/feature/menu/news/pic', $fresher['taken'], true ) === true && in_array( '/feature/menu/news/pic-1', $fresher['taken'], true ) === true && $fresher['exists'] === false );

// An entry that is taken out of a menu and one that is made under its id
$base = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['main']['entries'];
$without = array_values( array_filter( $base, static fn( array $e ): bool => $e['id'] !== 'shop' ) );
menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => $without, 'texts' => [ '/feature/menu/main/home' => $texts['/feature/menu/main/home'] ] ], 'menu/save' );
$remade = [ [ 'id' => 'shop', 'kind' => 'link', 'href' => '/other', 'target' => '', 'label' => '/feature/menu/main/shop', 'panel' => null ] ];
[ $second ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => array_merge( $without, $remade ), 'texts' => [ '/feature/menu/main/shop' => [ 'en_US' => 'Impostor' ] ] ], 'menu/save' );
$known = array_column( \Nino\Text::entries( $appData ), null, 'key' );
check( 'SAFETY: an entry that is taken out of a menu and made again under its id is refused the name it made (409): the words of the first are never the second\'s and are not overwritten', $second === 409 && ( $known['/feature/menu/main/shop']['values']['en_US'] ?? '' ) === 'Shop'
	&& count( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['main']['entries'] ) === 1 );
[ $kept ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => $without, 'texts' => [ '/feature/menu/main/home' => [ 'de_DE' => 'Anfang', 'en_US' => 'Home' ] ] ], 'menu/save' );
$known = array_column( \Nino\Text::entries( $appData ), null, 'key' );
check( 'the names a menu has are its own to write: saving it again with another text changes the text', $kept === 200 && ( $known['/feature/menu/main/home']['values']['de_DE'] ?? '' ) === 'Anfang' );

// A name another part of the project made - a text key somebody wrote by hand - is not taken by a new entry either
$other = [ [ 'id' => 'mine', 'kind' => 'link', 'href' => '/m', 'target' => '', 'label' => '/feature/menu/main/mine', 'panel' => null ] ];
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', ( (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ) ) + [ '[[/feature/menu/main/mine]]' => 'Written by hand' ] );
[ $handmade ] = menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => array_merge( $without, $other ), 'texts' => [ '/feature/menu/main/mine' => [ 'en_US' => 'Overwritten' ] ] ], 'menu/save' );
check( 'SAFETY: a text key that exists already and is no part of the stored menu is not taken by a new entry, whoever made it', $handmade === 409 );

// The menu is deleted, and only it
[ $status ] = ninoCall( $appData, $panel, [ 'key' => 'main' ], 'menu/delete' );
[ $gone ] = ninoCall( $appData, $panel, [ 'key' => 'main' ], 'menu/delete' );
check( 'a menu is taken out of the file and only it: 404 for one that is not there, the other menu stays', $status === 200 && $gone === 404 && array_keys( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === [ 'foot' ] );

$many = [];
for( $i = 0; $i < \Nino\Modules\Menu::MAX_MENUS; $i++ )
	$many['n'. chr( 97 + $i )] = [ 'entries' => [] ];
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, $many );
[ $limit ] = menuSave( $appData, $panel, [ 'key' => 'one-more', 'entries' => [], 'texts' => [] ], 'menu/save' );
[ $freshLimit ] = ninoCall( $appData, $panel, [ 'key' => 'one-more', 'fresh' => true ], 'menu/load' );
[ $resave ] = menuSave( $appData, $panel, [ 'key' => 'na', 'entries' => [], 'texts' => [] ], 'menu/save' );
check( 'SAFETY: a menu file holds at most as many menus as the limit says: the next is refused (400) when it is made and when it is asked for, and a menu that is there is saved still', $limit === 400 && $freshLimit === 400 && $resave === 200
	&& count( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === \Nino\Modules\Menu::MAX_MENUS );

echo "\nThe panel - versions, words, the first menu\n";

// Two sessions: what an editor read is what it writes over
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$mk = static fn( string $id, string $word ): array => [ [ 'id' => $id, 'kind' => 'link', 'href' => '/x', 'target' => '', 'label' => '/feature/menu/race/'. $id, 'panel' => null ] ];
[ , $freshA ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'fresh' => true ], 'menu/load' );
[ , $freshB ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'fresh' => true ], 'menu/load' );
[ $first ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => $mk( 'about', 'A' ), 'texts' => [ '/feature/menu/race/about' => [ 'en_US' => 'A' ] ], 'version' => $freshA['version'] ], 'menu/save' );
[ $second, $conflict ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => $mk( 'team', 'B' ), 'texts' => [ '/feature/menu/race/team' => [ 'en_US' => 'B' ] ], 'version' => $freshB['version'] ], 'menu/save' );
[ $forced ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => $mk( 'team', 'B' ), 'texts' => [ '/feature/menu/race/team' => [ 'en_US' => 'B' ] ], 'version' => $freshB['version'], 'force' => true ], 'menu/save' );
check( 'SAFETY: a menu that two sessions made is the first one\'s: the second save is a conflict (409, menu_changed) and is not written over it, not even when the editor says to overwrite',
	$first === 200 && $second === 409 && ( $conflict['code'] ?? '' ) === 'menu_changed' && $forced === 409 && array_column( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] )['race']['entries'], 'id' ) === [ 'about' ] );
[ , $seen ] = ninoCall( $appData, $panel, [ 'key' => 'race' ], 'menu/load' );
[ $stale ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => $mk( 'news', 'N' ), 'texts' => [ '/feature/menu/race/news' => [ 'en_US' => 'N' ] ], 'version' => $freshA['version'] ], 'menu/save' );
[ $current ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => array_merge( $mk( 'about', 'A' ), $mk( 'news', 'N' ) ), 'texts' => [ '/feature/menu/race/about' => [ 'en_US' => 'A' ], '/feature/menu/race/news' => [ 'en_US' => 'N' ] ], 'version' => $seen['version'] ], 'menu/save' );
check( 'SAFETY: a session that read a menu long ago cannot write over what another saved since (409), and one that read the menu as it is can; a save with no version is refused',
	$stale === 409 && $current === 200 && ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => [], 'texts' => [] ], 'menu/save' )[0] === 409 );
[ $forcedOld ] = ninoCall( $appData, $panel, [ 'key' => 'race', 'entries' => array_merge( $mk( 'about', 'A' ), $mk( 'news', 'N' ) ), 'texts' => [ '/feature/menu/race/about' => [ 'en_US' => 'A2' ] ], 'version' => $seen['version'], 'force' => true ], 'menu/save' );
check( 'a menu that was there may be written over once the editor has been asked and says so', $forcedOld === 200 );

// A word typed in the panel goes the way of the Text panel - plain text kept with its quotes as entities - and is shown once escaped
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$hard = 'Write "us" & it\'s';
$word = [ [ 'id' => 'say', 'kind' => 'link', 'href' => '/say', 'target' => '', 'label' => '/feature/menu/words/say', 'panel' => null ] ];
[ $status ] = menuSave( $appData, $panel, [ 'key' => 'words', 'entries' => $word, 'texts' => [ '/feature/menu/words/say' => [ 'en_US' => $hard, 'de_DE' => $hard ] ] ], 'menu/save' );
[ , $back ] = ninoCall( $appData, $panel, [ 'key' => 'words' ], 'menu/load' );
$page = $draw( $header( '[menu key="words"]' ) );
check( 'a word with a quote and an ampersand, written through the panel, is shown once escaped on the page and comes back as typed into the field',
	$status === 200 && str_contains( $page, '>Write &quot;us&quot; &amp; it&#039;s</a>' ) === true && str_contains( $page, '&amp;quot;' ) === false && str_contains( $page, '&amp;amp;' ) === false
	&& ( (array) ( (array) $back['texts'] )['/feature/menu/words/say'] )['en_US'] === $hard );

// The first menu is the first one made, whatever the names say
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$zedEntry = [ [ 'id' => 'home', 'kind' => 'link', 'href' => '/', 'target' => '', 'label' => '/feature/menu/zed/home', 'panel' => null ] ];
$aaaEntry = [ [ 'id' => 'home', 'kind' => 'link', 'href' => '/', 'target' => '', 'label' => '/feature/menu/aaa/home', 'panel' => null ] ];
menuSave( $appData, $panel, [ 'key' => 'zed', 'entries' => $zedEntry, 'texts' => [ '/feature/menu/zed/home' => [ 'en_US' => 'Zed home' ] ] ], 'menu/save' );
menuSave( $appData, $panel, [ 'key' => 'aaa', 'entries' => $aaaEntry, 'texts' => [ '/feature/menu/aaa/home' => [ 'en_US' => 'Aaa home' ] ] ], 'menu/save' );
$page = $draw( $header( '[menu]' ) );
check( 'a menu made later - one whose name sorts first - does not take over a call that names no menu: it draws the first menu made, and the choice lists them in that order',
	str_contains( $page, 'id="menu-zed"' ) === true && str_contains( $page, 'Aaa home' ) === false && array_keys( \Nino\Modules\Menu::options( $appData, 'key' ) ) === [ '', 'zed', 'aaa' ] );

// What is written into an attribute is escaped once: an address and the alt text of a picture are an attribute each
$tricky = [ [ 'id' => 'a', 'kind' => 'link', 'href' => 'https://e.example/?x=&quot;y&amp;z', 'target' => '', 'label' => '/feature/menu/main/a', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'image', 'slot' => '/feature/menu/main/a-1' ] ] ] ] ] ];
$appData['/nino/html/images']['/feature/menu/main/a-1'] = [ 'label' => 'A', 'width' => 60, 'height' => 40, 'filename' => 'feature/menu/main/a-1.60x40.webp', 'alt' => [ 'en_US' => 'Say "hi" & <b>x</b> \' onload=\'y' ] ];
\Nino\Html::addFills( $appData, [ '/feature/menu/main/a' => 'A' ], '*' );
menuStore( $appData, $tricky );
$x = $dom( $draw( $header() ) );
$a = $x->query( '//a[@class="menu-link"]' )->item( 0 );
$img = $x->query( '//img[@class="menu-image"]' )->item( 0 );
check( 'SAFETY: an address and an alt text are one attribute each and are what was written: no entity of them is read, no quote ends them, no script comes of them',
	$a !== null && $a->getAttribute( 'href' ) === 'https://e.example/?x=&quot;y&amp;z' && $img !== null && $img->getAttribute( 'alt' ) === 'Say "hi" & <b>x</b> \' onload=\'y' && $img->attributes->length === 7 && $x->query( '//script' )->length === 0 );
$quoted = [ 'https://e.example/"><script>alert(1)</script>', 'https://e.example/a<b', 'https://e.example/a>b', "https://e.example/a\x01b" ];
$refusedHref = 0;
foreach( $quoted as $href )
	$refusedHref += \Nino\Modules\Menu::address( $href, '' ) === '' && menuSave( $appData, $panel, [ 'key' => 'main', 'entries' => [ [ 'id' => 'a', 'kind' => 'link', 'href' => $href, 'target' => '', 'label' => '/feature/menu/main/a', 'panel' => null ] ], 'texts' => [] ], 'menu/save' )[0] === 400 ? 1 : 0;
check( 'SAFETY: saving refuses a target that holds a double quote, a less-than or greater-than sign or a control character', $refusedHref === count( $quoted ) );

// A session that only changed a word is a session that wrote something: the words of a menu are part of its version
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$wr = [ [ 'id' => 'about', 'kind' => 'link', 'href' => '/x', 'target' => '', 'label' => '/feature/menu/wr/about', 'panel' => null ] ];
menuSave( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [ '/feature/menu/wr/about' => [ 'en_US' => 'First' ] ] ], 'menu/save' );
[ , $readA ] = ninoCall( $appData, $panel, [ 'key' => 'wr' ], 'menu/load' );
menuSave( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [ '/feature/menu/wr/about' => [ 'en_US' => 'Changed by B' ] ] ], 'menu/save' );
[ $lateA, $lateBody ] = ninoCall( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [ '/feature/menu/wr/about' => [ 'en_US' => 'Stale from A' ] ], 'version' => $readA['version'] ], 'menu/save' );
$kept = ( array_column( \Nino\Text::entries( $appData ), null, 'key' )['/feature/menu/wr/about']['values']['en_US'] ?? '' );
check( 'SAFETY: a session that read a menu and then only had a word changed under it is a conflict (409): the word of the session that wrote it is not set back', $lateA === 409 && ( $lateBody['code'] ?? '' ) === 'menu_changed' && $kept === 'Changed by B' );
[ , $readNow ] = ninoCall( $appData, $panel, [ 'key' => 'wr' ], 'menu/load' );
$file = (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] );
$file['[[/feature/menu/wr/about]]'] = 'Written in the Text panel';
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', $file );
check( 'a word changed in the Text panel moves the version as well', menuSave( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [], 'version' => $readNow['version'] ], 'menu/save' )[0] === 409 );
[ $emptyForce ] = ninoCall( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [], 'version' => '', 'force' => true ], 'menu/save' );
[ $junkForce ] = ninoCall( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [], 'version' => 'x', 'force' => true ], 'menu/save' );
[ $realForce ] = ninoCall( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [], 'version' => $readA['version'], 'force' => true ], 'menu/save' );
check( 'SAFETY: overwriting asks for a real version of the menu: none or a made-up one is a conflict (409) whatever force says, the version that was read is let through', $emptyForce === 409 && $junkForce === 409 && $realForce === 200 );
[ , $readGone ] = ninoCall( $appData, $panel, [ 'key' => 'wr' ], 'menu/load' );
ninoCall( $appData, $panel, [ 'key' => 'wr' ], 'menu/delete' );
[ $goneStatus, $goneBody ] = ninoCall( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [], 'version' => $readGone['version'] ], 'menu/save' );
[ $goneForce, $goneForceBody ] = ninoCall( $appData, $panel, [ 'key' => 'wr', 'entries' => $wr, 'texts' => [], 'version' => $readGone['version'], 'force' => true ], 'menu/save' );
check( 'a menu another session deleted is said to be gone (409, menu_gone) - to a save and to an overwrite - and is not made again',
	$goneStatus === 409 && ( $goneBody['code'] ?? '' ) === 'menu_gone' && $goneForce === 409 && ( $goneForceBody['code'] ?? '' ) === 'menu_gone' && array_key_exists( 'wr', \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) ) === false );
check( 'SAFETY: the check under the lock names a menu that another save changed since the first check, and one that is the same, and a file that is no map',
	\Nino\Modules\Menu\Admin::changed( [ 'a' => [ 'entries' => [] ] ], 'a', \Nino\Modules\Menu\Admin::version( [ 'entries' => [ 1 ] ] ) ) === true
	&& \Nino\Modules\Menu\Admin::changed( [ 'a' => [ 'entries' => [] ] ], 'a', \Nino\Modules\Menu\Admin::version( [ 'entries' => [] ] ) ) === false && \Nino\Modules\Menu\Admin::changed( 'x', 'a', \Nino\Modules\Menu\Admin::version( null ) ) === false );

// An entity somebody typed stays what they typed; only the four the Text panel's sanitizer writes are read back
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$typed = 'a &lt;i&gt; "q" it\'s [x]';
$ent = [ [ 'id' => 'ent', 'kind' => 'link', 'href' => '/e', 'target' => '', 'label' => '/feature/menu/ent/ent', 'panel' => null ] ];
menuSave( $appData, $panel, [ 'key' => 'ent', 'entries' => $ent, 'texts' => [ '/feature/menu/ent/ent' => [ 'en_US' => $typed ] ] ], 'menu/save' );
[ , $entBack ] = ninoCall( $appData, $panel, [ 'key' => 'ent' ], 'menu/load' );
check( 'an entity that was typed stays one: the field gets what was typed and the page shows it once escaped (&lt; is read as &lt;), where a quote and a bracket are the characters', ( (array) ( (array) $entBack['texts'] )['/feature/menu/ent/ent'] )['en_US'] === $typed
	&& str_contains( $draw( $header( '[menu key="ent"]' ) ), '>a &amp;lt;i&amp;gt; &quot;q&quot; it&#039;s &#91;x&#93;</a>' ) === true );

// The longest text, and the right for the slots by its own code
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$long = [ [ 'id' => 'lg', 'kind' => 'link', 'href' => '/l', 'target' => '', 'label' => '/feature/menu/long/lg', 'panel' => null ] ];
[ $tooLong ] = menuSave( $appData, $panel, [ 'key' => 'long', 'entries' => $long, 'texts' => [ '/feature/menu/long/lg' => [ 'en_US' => str_repeat( 'a', \Nino\Modules\Menu\Admin::MAX_TEXT + 1 ) ] ] ], 'menu/save' );
[ $justRight ] = menuSave( $appData, $panel, [ 'key' => 'long', 'entries' => $long, 'texts' => [ '/feature/menu/long/lg' => [ 'en_US' => str_repeat( 'a', \Nino\Modules\Menu\Admin::MAX_TEXT ) ] ] ], 'menu/save' );
check( 'SAFETY: a text of a menu is one line of at most 300 characters: one more is refused (400), 300 are written', $tooLong === 400 && $justRight === 200 );
$noSlots2 = $appData;
ninoUser( $noSlots2, 'noslots2@example.com', [ '/_admin/menu/manage', '/_admin/keys/manage' ] );
[ $code403, $why ] = menuSave( $noSlots2, $panel, [ 'key' => 'pics', 'entries' => [ [ 'id' => 'p', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'image', 'slot' => '/feature/menu/pics/p-1' ] ] ] ] ] ], 'texts' => [] ], 'menu/save' );
check( 'SAFETY: the permission of the Image Slots is the panel\'s own question, answered with its own code (menu_permission) before anything is made', $code403 === 403 && ( $why['code'] ?? '' ) === 'menu_permission' );

// The stylesheet: what the page shows with no script, and what folds the panels
$css = (string) file_get_contents( $dir. '/groups/menu/part/part.css' );
preg_match( '/^\.menu-link \{[^}]*\}/m', $css, $linkRule );
check( 'the name of an entry keeps its width with no script (its basis is auto, not 0), and the panels fold only where the script has started: the rule that hides one is under menu--ready and no media query of scripting hides a panel',
	str_contains( $linkRule[0] ?? '', 'flex: 1 1 auto' ) === true && str_contains( $css, '.menu--ready .menu-toggle[aria-expanded="false"] + .menu-panel { display: none; }' ) === true
	&& preg_match( '/@media \(scripting: enabled\)\s*\{[^}]*display: none/', $css ) === 0 );
check( 'the wide form (a row, a panel that hangs from its entry) stands under the media query of scripting and does not wait for the script, so that the header does not jump; a panel then waits hidden and opens on :hover and :focus-within until the script has started',
	preg_match( '/@media \(scripting: enabled\)\s*\{\s*@media \(min-width: 768px\)\s*\{\s*@supports[^@]*flex-direction: row/', $css ) === 1 && str_contains( $css, '.menu--ready:not( .menu--footer ) .menu-list' ) === false
	&& str_contains( $css, '.menu-panel { visibility: hidden; }' ) === true && str_contains( $css, ':focus-within > .menu-panel' ) === true && str_contains( $css, 'menu-wait' ) === false );


// The menu in a row: what the part says of the class the register writes - the list that is folded lies under the row over its width and above what follows, and a wide screen shows it in the column
$rules = menuRules( $css );
$lies = menuDeclared( $rules, [ 'menu--in-row', 'menu-list', '[open]' ] );
$ink = $lies['color'] ?? '';
$ground = $lies['background-color'] ?? '';
check( 'the list of a menu in a row that is open and folded lies under the row, over its width and above the section that follows - absolute, from edge to edge of the row, one step above the page - and is drawn in its own ink on its own ground, so that it reads on a bar of any colour',
	( $lies['position'] ?? '' ) === 'absolute' && ( $lies['inset-inline'] ?? '' ) === '0' && (int) ( $lies['z-index'] ?? 0 ) >= 1 && str_starts_with( $lies['top'] ?? '', 'calc(100%+' ) === true
	&& str_starts_with( $ink, 'var(--brand-' ) === true && str_starts_with( $ground, 'var(--brand-' ) === true && $ink !== $ground );
check( 'on a wide screen the list is a row in the column, in the flow: not absolute',
	( menuDeclared( $rules, [ 'menu--in-row', 'menu-list', '[open]' ], true )['position'] ?? '' ) === 'static' );

// The open list that is folded is drawn in the overlay the header makes of itself (the part of the header, the script of the header): the header scrolls, so the list has no height of its own and nothing to scroll in itself,
// it does not stand as a card (no shadow), and its entries are as large as those of the menu of the header, by the tokens of the page; from 768px the entries are those of the line
$folded = menuDeclared( $rules, [ 'menu', 'menu-list' ] );
$entry = menuDeclared( $rules, [ 'menu-item', '>', 'menu-link' ] );
$entryWide = menuDeclared( $rules, [ 'menu-item', '>', 'menu-link' ], true );
check( 'the open list of a menu that is folded has no height of its own and no scrolling of its own, and a gap of the page\'s below the line: the header, which is the overlay, scrolls',
	isset( $folded['max-height'] ) === false && isset( $folded['overflow-y'] ) === false && isset( $folded['overflow'] ) === false && ( $folded['margin-top'] ?? '' ) === 'var(--page-space-5)'
	&& count( array_filter( $rules, static fn( array $rule ): bool => str_contains( $rule['at'], 'min-width' ) === true && str_contains( $rule['body'], 'max-height: 40dvh' ) === true && str_contains( $rule['body'], 'overflow-y: auto' ) === true && str_contains( $rule['body'], 'margin-top: 0' ) === true ) ) === 1 );
check( 'the open list of a menu in a row is flat, as the list of the menu of the header: no shadow, on its own ground and a gap below the row - and no shadow comes back on a wide screen',
	isset( $lies['box-shadow'] ) === false && isset( menuDeclared( $rules, [ 'menu--in-row', 'menu-list', '[open]' ], true )['box-shadow'] ) === false && str_contains( $css, 'box-shadow: 0 .5rem 1.5rem color-mix( in srgb, var(--brand-ink) 16%, transparent ); translate' ) === true
	&& ( $lies['top'] ?? '' ) === 'calc(100%+var(--page-space-4))' && ( $lies['margin-top'] ?? 'none' ) === 'none' );
check( 'the entries of a folded menu are large by the tokens of the page, as those of the menu of the header, and on a wide screen they are those of the line',
	( $entry['font-size'] ?? '' ) === 'var(--page-text-5)' && ( $entry['padding-block'] ?? '' ) === 'var(--page-space-3)' && ( $entryWide['font-size'] ?? '' ) === 'inherit' && ( $entryWide['padding-block'] ?? '' ) === 'var(--page-space-2)' );
check( 'where the browser does not know ::details-content the nav is a box, and the one that is open takes the whole line, as the nav of the menu of the header does - the list is not squeezed beside the logo',
	( menuDeclared( $rules, [ '.menu:not(.menu--footer):has(', 'menu-details', '[open]' ] )['flex'] ?? '' ) === '10100%' );
check( 'the part names nothing of the header - it cannot, the rule of mixing says every class is its own - and it keeps that rule',
	str_contains( $css, 'header-wrap' ) === false && \Nino\Components::partProblems( $css, [ 'menu' ] ) === [] );
$blockDrawn2 = $dom( $draw( (string) file_get_contents( $dir. '/blocks/menu-header/block.tpl' ) ) );
check( 'the block of the header keeps the menu in the column that fills the rest of the row and stands at its end',
	$blockDrawn2->query( '//header//div[contains(@class,"section-col--size-fill") and contains(@class,"section-col--align-end")]/nav[contains(concat(" ",@class," ")," menu ")]' )->length === 1 );

echo "\nThe panel script - what it is built from\n";

$script = (string) file_get_contents( $dir. '/assets/admin.js' );
check( 'the panel script is built from the blocks of the workbench - a table, an ordered list with the cluster to move, a dialog for a part, the picker of the kind, the languages one under the other, the file field, the editor of the kernel, one main button in the bar - and builds nothing itself',
	preg_match( '/Nino\.adminUi\.table\(/', $script ) === 1 && preg_match( '/Nino\.adminUi\.list\(/', $script ) === 1 && str_contains( $script, 'nino-admin-move' ) === true && preg_match( '/Nino\.adminUi\.formDialog\(/', $script ) === 1
	&& preg_match( '/Nino\.adminUi\.pickDialog\(/', $script ) === 1 && preg_match( '/Nino\.adminUi\.localeRows\(/', $script ) === 1 && str_contains( $script, 'Nino.adminUi.fileField(' ) === true && str_contains( $script, 'Nino.admin.htmlEditor.create(' ) === true
	&& preg_match( '/actionBar\(\s*\{/', $script ) === 1 && str_contains( $script, 'noValidate = true' ) === true && str_contains( $script, 'Nino.adminUi.say(' ) === true && str_contains( $script, 'dirty' ) === true && str_contains( $script, 'workLocale' ) === true && str_contains( $script, "field.addEventListener( 'keydown'" ) === true && str_contains( $script, "nino-admin-richtext-linkbar button').click()" ) === true && str_contains( $script, 'firstWords(' ) === true
	&& preg_match( '/createElement\(\s*[\'"]button|(?<![.\w])confirm\(|window\.confirm|(?<![.\w])alert\(|type\s*=\s*[\'"]file|nino-admin-btn-|innerHTML\s*=\s*+[^\'"\s]/', $script ) === 0 );
preg_match_all( '/\bw\(\s*\'([^\']+)\'\s*\)/', $script, $wanted );
$panelWords = [ 'en_US' => include $dir. '/text/en_US.php', 'de_DE' => include $dir. '/text/de_DE.php' ];
$missing = [];
foreach( array_unique( $wanted[1] ) as $word )
	foreach( $panelWords as $locale => $table )
		if( isset( $table[ '[[/_admin/menu/'. $word. ']]' ] ) === false )
			$missing[] = $locale. ' '. $word;
foreach( [ 'title', 'subtitle', 'image', 'page', 'link', 'html' ] as $kind )
	foreach( $panelWords as $locale => $table )
		foreach( [ 'kind/', 'kind-hint/', 'dialog/add-', 'dialog/edit-' ] as $prefix )
			if( isset( $table[ '[[/_admin/menu/'. $prefix. $kind. ']]' ] ) === false )
				$missing[] = $locale. ' '. $prefix. $kind;
check( 'every word the script asks for is said in both languages of the panel, both say the same keys, and each code the panel answers with has a sentence', $missing === [] && array_keys( $panelWords['en_US'] ) === array_keys( $panelWords['de_DE'] )
	&& array_filter( [ 'menu_key', 'menu_invalid', 'menu_list', 'menu_texts', 'menu_missing', 'menu_limit', 'menu_taken', 'menu_permission', 'menu_key_made', 'menu_slot_made' ], static fn( string $c ): bool => isset( $panelWords['en_US'][ '[[/_admin/error/'. $c. ']]' ] ) === false ) === [] );

$public = (string) file_get_contents( $dir. '/assets/menu.js' );
check( 'the public script writes no markup: it sets aria-expanded and one custom property, and reads nothing from data', preg_match( '/createElement|innerHTML|insertAdjacent|outerHTML|document\.write|\.append\(|appendChild|eval\(|new Function/', $public ) === 0
	&& str_contains( $public, 'aria-expanded' ) === true && str_contains( $public, '--menu-shift' ) === true && str_contains( $public, "'Escape'" ) === true );

// The script of the public site, and the more of the panel, run over a stand-in of a page (menu-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: menu-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/menu-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'menu-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
