<?php
declare(strict_types=1);

// The Menu feature's contract test. Runs against the checkout three levels up, or the one NINO_ROOT
// names. The feature stores no personal data - a menu is the site's own content, texts and picture
// slots - and so brings no section of the privacy policy.
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'menu' );
$appData['/nino/dir'] = '/shop';
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Components' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

echo "\nThe manifest and the activation\n\n";

check( 'the manifest validates without a warning', is_array( \Nino\Features::manifest( dirname( __DIR__ ) ) ) && ninoWarnings() === [] );
check( 'activation lists the class, records the version and delivers the word of the toggle in both languages', \Nino\Features::activate( $appData, 'menu' ) === true
	&& in_array( '\\Nino\\Modules\\Menu', $appData['/nino/modules'], true ) === true && \Nino\Features::get( $appData, 'menu' )['installed'] === '1.0.1'
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/menu/panel/open]]'] ?? '' ) === 'Open the submenu'
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/feature/menu/panel/open]]'] ?? '' ) === 'Untermenü öffnen' );


$manifest = \Nino\Features::manifest( dirname( __DIR__ ) );
$lineProblems = [];

foreach( $manifest['manual'] as $section => $entries )
	foreach( $entries as $handle => $line )
		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( strlen( (string) $handle ) > 120 || is_string( $line[$locale] ?? null ) === false || trim( $line[$locale] ) === '' || strlen( $line[$locale] ) >= 1000 )
				$lineProblems[] = $section. ' / '. $handle. ' / '. $locale;

check( 'the manifest says what the feature is: a content feature of version 1.0.1 for Nino 1.10, with no requirement and no setting, owning /data/menus.php and bundling its two assets, which are there',
	$manifest['key'] === 'menu' && $manifest['category'] === 'content' && $manifest['version'] === '1.0.1' && $manifest['nino'] === '^1.10' && $manifest['requires'] === [] && $manifest['settings'] === [] && $manifest['data'] === [ '/data/menus.php' ]
	&& $manifest['assets'] === [ '/features/Menu/assets/menu.css', '/features/Menu/assets/menu.js' ] && is_file( dirname( __DIR__ ). '/assets/menu.css' ) === true && is_file( dirname( __DIR__ ). '/assets/menu.js' ) === true
	&& $manifest['name'] === [ 'en_US' => 'Menu', 'de_DE' => 'Menü' ] );
check( '...its manual answers the six questions in both languages, every line under 1000 bytes, the German one without a du of lower case: the two shortcodes, the classes, the panel, the callback of the Design',
	$lineProblems === [] && array_keys( $manifest['manual']['shortcodes'] ) === [ '[menu /main]', '[menu /main burger]' ] && array_keys( $manifest['manual']['callbacks'] ) === [ '/feature/design/navigations' ] && count( $manifest['manual']['panel'] ) === 1
	&& count( $manifest['manual']['markup'] ) === 2 && $manifest['manual']['routes'] === [] && preg_match( '/\bdu\b|\bdein/', json_encode( array_column( array_merge( ...array_values( $manifest['manual'] ) ), 'de_DE' ) ) ) !== 1 );

\Nino\Html::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );

/**
 *	The texts of the site, written the way the Text panel writes them
 */
$words = [
	'en_US' => [
		'/_nino/webpage/about/name' => 'About', '/_nino/webpage/contact/name' => 'Contact', '/_nino/webpage/nowhere/name' => 'Nowhere',
		'/feature/menu/main/services' => 'Services', '/feature/menu/main/services-1' => 'What we do', '/feature/menu/main/services-2' => 'For every size',
		'/feature/menu/main/services-3' => 'Our blog', '/feature/menu/main/services-5' => 'Elsewhere',
	],
	'de_DE' => [
		'/_nino/webpage/about/name' => 'Über uns', '/_nino/webpage/contact/name' => 'Kontakt',
		'/feature/menu/main/services' => 'Leistungen', '/feature/menu/main/services-1' => 'Was wir tun',
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
 *	A menu of two entries, the second with a panel of two columns
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
				[ 'kind' => 'html', 'html' => '<p class="own">As <b>typed</b></p>' ],
			],
		] ] ],
	];
}

/**
 *	The data file as the panel writes it
 *
 *	@param		array 		&$appData
 *	@param		array			$entries
 *	@param		string		$key
 *
 *	@return 	void
 */
function menuStore( array &$appData, array $entries, string $key = 'main' ): void {
	\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ $key => [ 'entries' => $entries ] ] );
}

/**
 *	The shortcode, rendered as a page renders it, for a request on a page
 *
 *	@param		array 		&$appData
 *	@param		string		$shortcode
 *	@param		string		$page					The Element-URI of the page that is shown
 *	@param		string		$locale
 *
 *	@return 	string
 */
function menuRender( array &$appData, string $shortcode = '[menu /main]', string $page = '/home', string $locale = 'en_US' ): string {

	unset( $appData['./menu/count'], $appData['./nino/html/filecache'] );
	$appData['./nino/http/requests'][0] = [ '/nino/http/response' => [ 'uri' => $page ] ];
	\Nino\Locales::useLocale( $appData, $locale );

	return \Nino\Html::renderHtml( $appData, $shortcode );
}

/**
 *	A body as the browser gets it: through json, objects and all
 *
 *	@param		mixed			$body
 *
 *	@return 	mixed
 */
function menuBody( mixed $body ): mixed {
	return json_decode( (string) json_encode( $body ), true );
}

/**
 *	A rendered menu as a document to ask xpath questions of
 *
 *	@param		string		$html
 *
 *	@return 	DOMXPath
 */
function menuDom( string $html ): DOMXPath {
	$document = new DOMDocument();
	@$document->loadHTML( '<?xml encoding="utf-8"?><body>'. $html. '</body>' );
	return new DOMXPath( $document );
}

/**
 *	The values an xpath finds, as strings
 *
 *	@param		DOMXPath	$xpath
 *	@param		string		$query
 *
 *	@return 	array
 */
function menuFind( DOMXPath $xpath, string $query ): array {
	$found = [];
	foreach( $xpath->query( $query ) ?: [] as $node )
		$found[] = $node->nodeValue ?? '';
	return $found;
}

menuStore( $appData, menuFixture() );

echo "\nThe menu, drawn\n\n";

$html = menuRender( $appData );
$dom = menuDom( $html );

check( 'a menu is a list of one item for each entry, in the hull of the kernel\'s regular navigation', menuFind( $dom, '//ul[@class="nino-menu"]/li/@class' ) === [ 'nino-menu-item', 'nino-menu-item nino-menu-item--panel' ]
	&& $dom->query( '//div[contains(@class,"nino-nav-regular") and contains(@class,"nino-menu-wrap")]/div[@class="nino-nav-content"]/ul[@class="nino-menu"]' )->length === 1 );
check( 'an entry is a link of the class nino-menu-link, the address of a page the project\'s directory and its route\'s path, the label the name of the page', menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a/@href' ) === [ '/shop/about' ]
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a[@class="nino-menu-link"]' ) === [ 'About' ]
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]/@href' ) === [ '/shop/services' ] );
check( 'a label that is a key reads its text', menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]' ) === [ 'Services' ] );

$toggle = '//ul[@class="nino-menu"]/li[2]/button[@class="nino-menu-toggle"]';
check( 'an entry with a panel has the button that opens it: a real button, closed, naming the panel by the id made from the menu and the entry', menuFind( $dom, $toggle. '/@type' ) === [ 'button' ]
	&& menuFind( $dom, $toggle. '/@aria-expanded' ) === [ 'false' ] && menuFind( $dom, $toggle. '/@aria-controls' ) === [ 'nino-menu-main--services' ]
	&& menuFind( $dom, '//div[@class="nino-menu-panel"]/@id' ) === [ 'nino-menu-main--services' ] );
check( '...whose word is the feature\'s own, in the language of the page', menuFind( $dom, $toggle. '/span[@class="nino-sr-only"]' ) === [ 'Open the submenu' ]
	&& menuFind( menuDom( menuRender( $appData, '[menu /main]', '/home', 'de_DE' ) ), $toggle. '/span' ) === [ 'Untermenü öffnen' ] );
check( '...followed by the panel, a bar of the columns the entry says, each a column', menuFind( $dom, '//div[@class="nino-menu-panel"]/div/@class' ) === [ 'nino-menu-cols nino-menu-cols-2' ]
	&& $dom->query( '//div[@class="nino-menu-cols nino-menu-cols-2"]/div[@class="nino-menu-col"]' )->length === 2 );

$col = '//div[@class="nino-menu-col"]';
check( 'a column draws its items in order: title, subtitle, links, the picture of a slot that has one', menuFind( $dom, '('. $col. ')[1]/*/@class' ) === [ 'nino-menu-title', 'nino-menu-subtitle', 'nino-menu-link nino-menu-link--sub', 'nino-menu-image' ]
	&& menuFind( $dom, '('. $col. ')[1]/p[@class="nino-menu-title"]' ) === [ 'What we do' ] && menuFind( $dom, '('. $col. ')[1]/p[@class="nino-menu-subtitle"]' ) === [ 'For every size' ]
	&& menuFind( $dom, '('. $col. ')[1]/a/@href' ) === [ '/shop/contact' ] && menuFind( $dom, '('. $col. ')[1]/a' ) === [ 'Contact' ] );
check( '...the picture is the slot\'s file with the alt text of the slot in the language of the page, and a slot without a picture draws nothing', menuFind( $dom, '('. $col. ')[1]/img/@src' ) === [ '/shop/public/images/feature/menu/main/services-4.600x400.webp' ]
	&& menuFind( $dom, '('. $col. ')[1]/img/@alt' ) === [ 'A workshop' ] && $dom->query( '//img' )->length === 1
	&& menuFind( menuDom( menuRender( $appData, '[menu /main]', '/home', 'de_DE' ) ), '//img/@alt' ) === [ 'Eine Werkstatt' ] );
check( '...an address outside the site opens where the entry says, with noopener, and keeps its query', menuFind( $dom, '('. $col. ')[2]/a/@target' ) === [ '_blank' ] && menuFind( $dom, '('. $col. ')[2]/a/@rel' ) === [ 'noopener' ]
	&& menuFind( $dom, '('. $col. ')[2]/a/@href' ) === [ 'https://example.com/blog?a=1&b=[2]' ] && menuFind( $dom, '('. $col. ')[2]/a' ) === [ 'Our blog' ] && str_contains( $html, 'href="https://example.com/blog?a=1&amp;b=&#91;2]"' ) === true );
check( '...and HTML+ stands as it was typed', str_contains( $html, '<p class="own">As <b>typed</b></p>' ) === true );
check( 'an entry that opens in the same window has no target', menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a/@target' ) === [] && menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]/@rel' ) === [] );
check( 'a page that is not shown has no mark of the current page', str_contains( $html, 'nino-is-active' ) === false && str_contains( $html, 'aria-current' ) === false && str_contains( $html, 'nino-menu-item--current' ) === false );

echo "\nThe page that is shown\n\n";

$dom = menuDom( menuRender( $appData, '[menu /main]', '/about' ) );
check( 'the entry of the page shown carries nino-is-active and aria-current as the kernel\'s menu does, and its item nino-menu-item--current', menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a/@class' ) === [ 'nino-menu-link nino-is-active' ]
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a/@aria-current' ) === [ 'page' ] && menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/@class' ) === [ 'nino-menu-item nino-menu-item--current' ]
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/@class' ) === [ 'nino-menu-item nino-menu-item--panel' ] );

$dom = menuDom( menuRender( $appData, '[menu /main]', '/contact' ) );
check( 'a page of a panel marks its own link and the item of the entry that holds the panel', menuFind( $dom, '//a[@aria-current="page"]/@class' ) === [ 'nino-menu-link nino-menu-link--sub nino-is-active' ]
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/@class' ) === [ 'nino-menu-item nino-menu-item--panel nino-menu-item--current' ] && menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/@class' ) === [ 'nino-menu-item' ] );

echo "\nThe hull\n\n";

$plain = menuRender( $appData, '[menu /main]' );
preg_match( '~<ul class="nino-menu">.*</ul>~s', $plain, $list );
$burger = menuRender( $appData, '[menu /main burger]' );
check( 'burger is the markup of \\Nino\\Modules\\Navigation::$html[\'nav-burger\'] byte for byte, its tokens filled', $burger === str_replace( [ '[[id]]', '[[class]]', '[[list]]', '[[content]]' ], [ 'nino-menu-main', 'nino-menu-wrap', 'nino-menu-main--list', $list[0] ?? '' ], \Nino\Modules\Navigation::$html['nav-burger'] )
	&& $list !== [] );
check( '...and without it the markup of nav-regular, the same way', $plain === str_replace( [ '[[id]]', '[[class]]', '[[content]]' ], [ 'nino-menu-main', 'nino-menu-wrap', $list[0] ?? '' ], \Nino\Modules\Navigation::$html['nav-regular'] ) );
check( 'the second menu of a key on a page - the footer\'s beside the header\'s - gets ids of its own', ( static function( array &$appData ): bool {
	unset( $appData['./menu/count'] );
	$two = \Nino\Html::renderHtml( $appData, '[menu /main burger][menu /main]' );
	return substr_count( $two, 'id="nino-menu-main--services"' ) === 1 && substr_count( $two, 'id="nino-menu-main--2--services"' ) === 1 && substr_count( $two, 'id="nino-menu-main"' ) === 1 && substr_count( $two, 'id="nino-menu-main--2"' ) === 1;
} )( $appData ) );
check( 'a menu that is not there, a key that is none and a menu with nothing to show draw nothing', menuRender( $appData, '[menu /nothing]' ) === '' && menuRender( $appData, '[menu /Main]' ) === '' && menuRender( $appData, '[menu /../main]' ) === '' && menuRender( $appData, '[menu]' ) === ''
	&& ( static function( array &$appData ): bool { menuStore( $appData, [ [ 'id' => 'gone', 'kind' => 'page', 'uri' => '/gone', 'label' => '', 'panel' => null ] ] ); return menuRender( $appData ) === ''; } )( $appData ) );
menuStore( $appData, menuFixture() );

echo "\nWhat is left out\n\n";

$dom = menuDom( menuRender( $appData, '[menu /main]', '/home', 'de_DE' ) );
check( 'a language that has no text of a key shows the text of another language, as the kernel\'s fills do', menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]' ) === [ 'Leistungen' ]
	&& menuFind( $dom, '('. $col. ')[2]/a' ) === [ 'Our blog' ] && menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a' ) === [ 'Über uns' ] );

$sparse = menuFixture();
$sparse[1]['panel']['cols'][0] = [
	[ 'kind' => 'title', 'key' => '/feature/menu/main/services-9' ],
	[ 'kind' => 'subtitle', 'key' => '/feature/menu/main/services-2' ],
	[ 'kind' => 'page', 'uri' => '/nowhere', 'label' => '' ],
	[ 'kind' => 'page', 'uri' => '/legal', 'label' => '' ],
	[ 'kind' => 'image', 'slot' => '/feature/menu/main/services-4' ],
	[ 'kind' => 'image', 'slot' => '/feature/menu/main/services-6' ],
];
menuStore( $appData, $sparse );
$dom = menuDom( menuRender( $appData ) );
check( 'a text nobody wrote, a page without a route and a page without a name draw nothing', menuFind( $dom, '('. $col. ')[1]/*/@class' ) === [ 'nino-menu-subtitle', 'nino-menu-image' ] );

$empty = menuFixture();
$empty[1]['panel']['cols'] = [ [ [ 'kind' => 'title', 'key' => '/feature/menu/main/services-9' ] ], [ [ 'kind' => 'image', 'slot' => '/feature/menu/main/services-6' ] ] ];
menuStore( $appData, $empty );
$dom = menuDom( menuRender( $appData ) );
check( 'a panel with nothing to show is not drawn, and its button goes with it', $dom->query( '//div[@class="nino-menu-panel"]' )->length === 0 && $dom->query( '//button' )->length === 0
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/@class' ) === [ 'nino-menu-item' ] );

echo "\nAddresses and escaping\n\n";

$probe = menuFixture();
$probe[1]['href'] = 'javascript:alert(1)';
$probe[1]['panel']['cols'][1][0]['href'] = "java\tscript:alert(1)";
$probe[1]['panel']['cols'][0][2] = [ 'kind' => 'link', 'href' => '//evil.example/x', 'target' => '', 'label' => '/feature/menu/main/services-5' ];
menuStore( $appData, $probe );
$html = menuRender( $appData );
$dom = menuDom( $html );
check( 'an address with another scheme - javascript:, a tab inside the scheme - renders #', menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]/@href' ) === [ '#' ] && menuFind( $dom, '('. $col. ')[2]/a/@href' ) === [ '#' ]
	&& str_contains( $html, 'javascript' ) === false );
check( '...so does a protocol-relative address', menuFind( $dom, '('. $col. ')[1]/a/@href' ) === [ '#' ] );

foreach( [ 'https://example.com/a', 'http://example.com', 'mailto:me@example.com', 'tel:+49 30 1234' => '#', 'tel:+4930-1234', '/path?x=1#y', '/a b' => '#', '#a b' => '#', '#top', 'ftp://example.com' => '#', 'data:text/html,x' => '#', 'https:///x' => '#', 'https://' => '#' ] as $href => $expected ) {
	if( is_int( $href ) === true ) {
		$href = $expected;
		$expected = $href[0] === '/' ? '/shop'. $href : $href;
	}
	$one = menuFixture();
	$one[1]['href'] = $href;
	menuStore( $appData, $one );
	$seen = menuFind( menuDom( menuRender( $appData ) ), '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]/@href' );
	check( 'the address "'. $href. '" is written '. ( $expected === '#' ? 'as #' : 'as it is' ), $seen === [ $expected ] );
}

\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [
	'[[/feature/menu/main/services]]' => \Nino\Text::sanitizeValue( '<b>Bold</b> [x] & "q"', 'plain' ),
] ) );
menuStore( $appData, menuFixture() );
$html = menuRender( $appData );
$dom = menuDom( $html );
check( 'a label with markup and a bracket, as the Text panel stores it, comes out as text: no tag of its own, and nothing a next pass would read as a fill or a shortcode', $dom->query( '//ul[@class="nino-menu"]/li[2]/a/*' )->length === 0
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a[@class="nino-menu-link"]' ) === [ 'Bold [x] & "q"' ] && str_contains( $html, 'Bold &#91;x&#93;' ) === true );

echo "\nWhat the data may hold\n\n";

/**
 *	The problems of a menu that is written, as a list of the entries they name
 *
 *	@param		array			$entries
 *
 *	@return 	array
 */
function menuProblems( array $entries ): array {
	$problems = [];
	\Nino\Modules\Menu::entries( 'main', $entries, $problems );
	return array_column( $problems, 'entry' );
}

check( 'the menu of the fixture has no problem, and entries() reads it back as it is', menuProblems( menuFixture() ) === [] && ( static function(): bool { $p = []; return \Nino\Modules\Menu::entries( 'main', menuFixture(), $p ) === menuFixture(); } )() );

$bad = static function( callable $change ): array {
	$entries = menuFixture();
	$change( $entries );
	return menuProblems( $entries );
};

check( 'a null byte in a key is refused, the entry named', $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][0]['key'] = "/feature/menu/main/services-1\0x"; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['label'] = "/feature/menu/main/services\0"; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[0]['id'] = "ab\0c"; } ) === [ '#1' ] );
check( 'a path with .. as a uri is refused, as is one that is no path', $bad( static function( array &$e ): void { $e[0]['uri'] = '/a/../b'; } ) === [ 'about' ]
	&& $bad( static function( array &$e ): void { $e[0]['uri'] = '/..'; } ) === [ 'about' ] && $bad( static function( array &$e ): void { $e[0]['uri'] = 'about'; } ) === [ 'about' ]
	&& $bad( static function( array &$e ): void { $e[0]['uri'] = "/a\0"; } ) === [ 'about' ] && $bad( static function( array &$e ): void { $e[0]['uri'] = '/a[[/project/x]]'; } ) === [ 'about' ] && $bad( static function( array &$e ): void { $e[0]['uri'] = '/a"b'; } ) === [ 'about' ] && $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][2]['uri'] = '/x/./y'; } ) === [ 'services' ] );
check( 'a key or a slot of another entry or another menu, a name that is used twice and a number that is none are refused', $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][0]['key'] = '/feature/menu/main/about-1'; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][0]['key'] = '/feature/menu/other/services-1'; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][1]['key'] = '/feature/menu/main/services-1'; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][0]['key'] = '/feature/menu/main/services-0'; } ) === [ 'services' ] );
check( 'an id that is taken, that ends in a number, that is list or that is no slug is refused', $bad( static function( array &$e ): void { $e[1]['id'] = 'about'; } ) === [ 'about' ]
	&& $bad( static function( array &$e ): void { $e[1] = [ 'id' => 'about', 'kind' => 'page', 'uri' => '/contact', 'label' => '', 'panel' => null ]; } ) === [ 'about' ] && $bad( static function( array &$e ): void { $e[0]['id'] = 'about-2'; } ) === [ 'about-2' ] && $bad( static function( array &$e ): void { $e[0]['id'] = 'list'; } ) === [ 'list' ]
	&& $bad( static function( array &$e ): void { $e[0]['id'] = 'About'; } ) === [ '#1' ] && $bad( static function( array &$e ): void { $e[0]['id'] = '1st'; } ) === [ '#1' ] );
check( 'a panel has one to four columns and as many lists of items, of the six kinds and no others', $bad( static function( array &$e ): void { $e[1]['panel']['columns'] = 5; $e[1]['panel']['cols'] = [ [], [], [], [], [] ]; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['panel']['columns'] = 3; } ) === [ 'services' ] && $bad( static function( array &$e ): void { $e[1]['panel']['columns'] = 0; $e[1]['panel']['cols'] = []; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['panel']['cols'][0][0] = [ 'kind' => 'script', 'key' => '/feature/menu/main/services-1', 'html' => 'x' ]; } ) === [ 'services' ] && $bad( static function( array &$e ): void { $e[1]['panel']['cols'][1][1]['html'] = ['x']; } ) === [ 'services' ] );
check( 'an address that would be drawn as # is refused when it is written, a target but _blank is too, and the label of a link is its key', $bad( static function( array &$e ): void { $e[1]['href'] = 'javascript:alert(1)'; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[1]['target'] = '_top'; } ) === [ 'services' ] && $bad( static function( array &$e ): void { $e[1]['label'] = ''; } ) === [ 'services' ]
	&& $bad( static function( array &$e ): void { $e[0]['label'] = '/feature/menu/main/other'; } ) === [ 'about' ] );
check( 'a menu holds 24 entries at the most, 24 items in a column, and every problem is told, one entry or one many', menuProblems( array_map( static fn( int $n ): array => [ 'id' => 'e'. chr( 97 + $n ), 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => null ], range( 0, 24 ) ) ) === [ '#0' ]
	&& $bad( static function( array &$e ): void { $e[0]['uri'] = 'x'; $e[1]['id'] = 'Bad'; } ) === [ 'about', '#2' ] && menuProblems( [ 'x' ] ) === [ '#1' ] && menuProblems( [ 'a' => 1 ] ) === [ '#0' ] );

check( 'a path that climbs is no address: a dot segment, written or percent-encoded, a backslash - while the dots of a query and of a name stay', \Nino\Modules\Menu::address( '/../../etc', '/shop' ) === '' && \Nino\Modules\Menu::address( '/a/./b', '/shop' ) === '' && \Nino\Modules\Menu::address( '/a/%2E%2e/b', '/shop' ) === ''
	&& \Nino\Modules\Menu::address( '/\evil', '/shop' ) === '' && \Nino\Modules\Menu::address( '/a\b', '/shop' ) === '' && \Nino\Modules\Menu::address( '/a?x=../y', '/shop' ) === '/shop/a?x=../y' && \Nino\Modules\Menu::address( '/a.b/c', '/shop' ) === '/shop/a.b/c' );
check( 'a target that is none says so in a sentence of its own, not the one of the address; a problem of the whole list names the list', ( static function() use ( $bad ): bool {
	$entries = menuFixture();
	$entries[1]['target'] = '_top';
	$problems = [];
	\Nino\Modules\Menu::entries( 'main', $entries, $problems );
	$long = [];
	\Nino\Modules\Menu::entries( 'main', array_map( static fn( int $n ): array => [ 'id' => 'e'. chr( 97 + $n ), 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => null ], range( 0, 24 ) ), $long );
	return str_contains( $problems[0]['why'], 'target' ) === true && str_contains( $problems[0]['why'], 'https' ) === false && $long[0]['entry'] === '#0' && str_contains( $long[0]['why'], '24' ) === true;
} )() );
check( 'the key of a menu is a slug that is not panel - /feature/menu/panel/open is the feature\'s own word - and such a menu is no menu', \Nino\Modules\Menu::isMenuKey( 'panel' ) === false && \Nino\Modules\Menu::isMenuKey( 'panels' ) === true && \Nino\Modules\Menu::isMenuKey( 'Panel' ) === false
	&& ( static function( array &$appData ): bool { \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ 'panel' => [ 'entries' => menuFixture() ], 'main' => [ 'entries' => menuFixture() ] ] ); $args = [ 'part' => 'footer', 'options' => [] ]; \Nino\Callbacks::doCallbacks( $appData, '/feature/design/navigations', $args ); return array_keys( $args['options'] ) === [ 'menu-main' ] && menuRender( $appData, '[menu /panel]' ) === ''; } )( $appData ) );

echo "\nThe ids of the markup\n\n";

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [
	'main'		=> [ 'entries' => [ [ 'id' => 'x', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'link', 'href' => '/a', 'target' => '', 'label' => '/feature/menu/main/x-1' ] ] ] ] ] ] ],
	'main-x'	=> [ 'entries' => [ [ 'id' => 'about', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'link', 'href' => '/a', 'target' => '', 'label' => '/feature/menu/main-x/about-1' ] ] ] ] ] ] ],
] );
$appData['./menu/count'] = [];
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [ '[[/feature/menu/main-x/about-1]]' => 'One', '[[/feature/menu/main/x-1]]' => 'Uno' ] ) );
unset( $appData['./nino/html/filecache'] );
$both = menuRender( $appData, '[menu /main burger][menu /main][menu /main-x burger][menu /main-x]' );
preg_match_all( '/\sid="([^"]+)"/', $both, $ids );
$dom = menuDom( $both );
check( 'no id of the markup is another\'s: the ids of two menus of keys that run into each other (main and main-x) and of the second render of a key are joined by a double hyphen, which no slug has',
	count( $ids[1] ) === count( array_unique( $ids[1] ) ) && count( $ids[1] ) === 14 && in_array( 'nino-menu-main--x', $ids[1], true ) === true && in_array( 'nino-menu-main-x--about', $ids[1], true ) === true && in_array( 'nino-menu-main--2--x', $ids[1], true ) === true );
check( 'the button of a panel is described by the link of its entry - every button has the same name else - and the id of that link is the id of the panel and --link',
	menuFind( $dom, '(//button[@class="nino-menu-toggle"])[1]/@aria-describedby' ) === [ 'nino-menu-main--x--link' ] && menuFind( $dom, '//a[@id="nino-menu-main--x--link"]/@class' ) === [ 'nino-menu-link' ]
	&& $dom->query( '//a[@class="nino-menu-link nino-menu-link--sub"][@id]' )->length === 0 );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ 'main' => [ 'entries' => menuFixture() ], 'footer' => [ 'entries' => [] ], 'Bad Key' => [ 'entries' => [] ] ] );

echo "\nThe callback\n\n";

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [ 'main' => [ 'entries' => menuFixture() ], 'footer' => [ 'entries' => [] ], 'Bad Key' => [ 'entries' => [] ] ] );

foreach( [ 'header' => '[menu /%s burger]', 'footer' => '[menu /%s]' ] as $part => $shortcode ) {

	$args = [ 'part' => $part, 'options' => [ 'none' => [ 'shortcode' => '', 'title' => [ 'en_US' => 'None' ] ] ], 'extra' => 7 ];
	\Nino\Callbacks::doCallbacks( $appData, '/feature/design/navigations', $args );

	check( 'the Design\'s question is answered for the '. $part. ': one option for each menu of the data, its shortcode and its title in both languages, and nothing else changed', $args === [
		'part'		=> $part,
		'options'	=> [
			'none'				=> [ 'shortcode' => '', 'title' => [ 'en_US' => 'None' ] ],
			'menu-main'		=> [ 'shortcode' => sprintf( $shortcode, 'main' ), 'title' => [ 'de_DE' => 'Menü „main“', 'en_US' => 'Menu "main"' ] ],
			'menu-footer'	=> [ 'shortcode' => sprintf( $shortcode, 'footer' ), 'title' => [ 'de_DE' => 'Menü „footer“', 'en_US' => 'Menu "footer"' ] ],
		],
		'extra'		=> 7,
	] );
}

echo "\nThe data file\n\n";

$before = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
check( 'the data file survives upgrade() as it is', \Nino\Modules\Menu::upgrade( $appData, '1.0.0' ) === true && \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] ) === $before );


echo "\nThe look for the Design feature\n\n";

$dir = dirname( __DIR__ );
$designDir = $dir. '/design';
$variant = (string) file_get_contents( $designDir. '/regular.css' );
$base = (string) file_get_contents( $dir. '/assets/menu.css' );
$knobTable = [ 'ground' => [ 'ground', 'colours' ], 'space' => [ 'scale', 'surfaces' ], 'gap' => [ 'scale', 'surfaces' ], 'radius' => [ 'scale', 'surfaces' ], 'shadow' => [ 'scale', 'surfaces' ], 'title' => [ 'scale', 'type' ] ];
$steps = [ 'least', 'less', 'default', 'more', 'most' ];

/**
 *	The custom properties a rule of a stylesheet declares
 *
 *	@param		string		$css
 *	@param		string		$selector			The selector of the rule, as it is written
 *
 *	@return 	array											property => value, the white space of the value as one space
 */
function menuDeclarations( string $css, string $selector ): array {

	$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );

	if( preg_match( '~(?:^|\})\s*'. preg_quote( $selector, '~' ). '\s*\{([^}]*)\}~s', $css, $rule ) !== 1 )
		return [];

	preg_match_all( '/(--[a-z-]+)\s*:\s*([^;]+);/', $rule[1], $found, PREG_SET_ORDER );

	return array_column( array_map( static fn( array $one ): array => [ $one[1], (string) preg_replace( '/\s+/', ' ', trim( $one[2] ) ) ], $found ), 1, 0 );
}

$head = [];
foreach( [ 'name', 'name-de', 'description', 'description-de' ] as $tag )
	$head[$tag] = preg_match_all( '/^(?:\/| )\*\t@'. $tag. '\t+(\S.*)$/m', $variant, $found ) === 1 ? $found[1][0] : null;

check( 'the feature brings one look, regular - a stylesheet in design/ - and the block for the specimen beside it', array_map( 'basename', glob( $designDir. '/*' ) ?: [] ) === [ 'preview.tpl', 'regular.css' ] );
check( '...its head names and describes it in both languages, once each', count( array_filter( $head, 'is_string' ) ) === 4 && $head['name'] !== $head['name-de'] && $head['description'] !== $head['description-de'] );

preg_match_all( '/^ \*\t@knob\t+(\S+)\t+(\S+)\t+(\S+)\t+(.+?)(?:\s+=\s+(\S+))?$/m', $variant, $knobs, PREG_SET_ORDER );
$declared = [];
foreach( $knobs as $knob )
	$declared[$knob[1]] = [ $knob[2], $knob[3], $knob[5] ?? null ];

check( 'it declares the six knobs in order - the ground on Colours at the page\'s own surface, the space, the gap between the columns, the corners and the shadow on Surfaces, the size of the title on Type - each with its words in German',
	array_keys( $declared ) === array_keys( $knobTable ) && $declared['ground'] === [ 'ground', 'colours', 'default' ] && array_filter( $knobTable, static fn( array $want, string $name ): bool => $declared[$name][0] !== $want[0] || $declared[$name][1] !== $want[1], ARRAY_FILTER_USE_BOTH ) === []
	&& preg_match_all( '/^ \*\t@knob-de\t+(\S+)\t+\S/m', $variant, $german ) === 6 && $german[1] === array_keys( $knobTable ) );

$root = menuDeclarations( $variant, ':root' );
$defaults = menuDeclarations( $base, ':where(:root)' );
$rungs = [];
$offLadder = [];
$middle = [];

foreach( $knobTable as $name => [ $kind ] )
	if( $kind === 'scale' )
		foreach( $steps as $step ) {
			$rungs[] = '--menu-'. $name. '--'. $step;

			if( isset( $root['--menu-'. $name. '--'. $step] ) === false || substr_count( (string) preg_replace( '#/\*.*?\*/#s', '', $variant ), '--menu-'. $name. '--'. $step. ':' ) !== 1 )
				$offLadder[] = $name. ' '. $step;
		}

foreach( array_keys( $root ) as $property )
	if( in_array( $property, $rungs, true ) === false )
		$offLadder[] = $property;

foreach( [ 'space', 'gap', 'radius', 'shadow', 'title' ] as $name )
	if( ( $root['--menu-'. $name. '--default'] ?? 'x' ) !== ( $defaults['--menu-'. $name] ?? 'y' ) )
		$middle[] = $name;

check( 'each scale has its five rungs on :root, once each, and nothing else stands there'. ( $offLadder === [] ? '' : ' - '. implode( ', ', $offLadder ) ), $offLadder === [] && count( $rungs ) === 25 );
check( '...the middle of every ladder is the value menu.css has as its default, so that a menu under the look is drawn as it is without the Design feature'. ( $middle === [] ? '' : ' - '. implode( ', ', $middle ) ), $middle === [] );
check( '...the look is the ladders and nothing else: no rule but :root, no colour literal, no !important',
	preg_match_all( '/\}\s*([^{}\s][^{}]*)\{/', (string) preg_replace( '#/\*.*?\*/#s', '', $variant ), $selectors ) === 0 && preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(/i', (string) preg_replace( '#/\*.*?\*/#s', '', $variant ) ) !== 1
	&& stripos( $variant, '!important' ) === false );
check( 'menu.css declares the defaults of everything a knob moves in :where(), which has no specificity, so that the compile of the Design wins whatever the order of the sheets',
	array_keys( $defaults ) === [ '--menu-ground', '--menu-ground-on', '--menu-ground-on-muted', '--menu-ground-link', '--menu-ground-border', '--menu-ground-focus', '--menu-ground-shadow', '--menu-space', '--menu-gap', '--menu-radius', '--menu-shadow', '--menu-title' ]
	&& menuDeclarations( $base, ':root' ) === [] );

$flat = '~:is\(\.nino-frame-header-rail, \.nino-nav-burger:has\(> \.nino-nav-toggle\[aria-expanded="true"\]\)\) \.nino-menu-panel \{([^}]*)\}~';
check( 'menu.css leaves the scroll to the rail - a panel open in it makes it scroll, the bar of other headers is what is not clipped - and draws the panel in the flow, as an accordion, wherever the list stands in a rail or in an open burger overlay - at every width, the script reading the shape from the style - and a dropdown only from 768px; a link of a panel is reset to the last of the background and the corners the header sets give an active link',
	preg_match( $flat, $base, $rule ) === 1 && str_contains( $rule[1], 'position: static;' ) === true && str_contains( $rule[1], 'background: none;' ) === true && preg_match( '~@media \(min-width: 768px\) \{.*:is\(\.nino-frame-header-rail~s', $base ) === 1
	&& str_contains( $base, '.nino-scroll-header:not(.nino-frame-header-rail):has(.nino-menu-panel) {' ) === true && str_contains( $base, 'body.nino-scroll-down .nino-scroll-header:not(.nino-frame-header-rail):has(' ) === true
	&& preg_match( '~\.nino-menu-panel \.nino-menu-cols a \{[^}]*border-radius: 0;[^}]*background: none;~s', $base ) === 1 && preg_match( '~\n\t\.nino-menu-panel \{[^}]*position: absolute;~', $base ) === 1 );

$block = (string) file_get_contents( $designDir. '/preview.tpl' );
check( 'the block for the specimen has no word of its own: every text in it is a token of the specimen or a word the kernel delivers', preg_match( '/>[^<>]*[A-Za-z][^<>]*</', (string) preg_replace( '/\[\[[^\]]+\]\]/', '', $block ) ) !== 1 && preg_match_all( '/>\s*\[\[[^\]]+\]\]\s*</', $block ) >= 10 );
$blockIds = [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

check( 'the block for the specimen: one grid row, every id in it menu-something and none twice, no section of its own and no inline handler, the bar with one entry\'s panel open in the markup the shortcode writes',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1 && $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'menu-' ) === false ) === []
	&& count( $blockIds ) === count( array_unique( $blockIds ) ) && preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1
	&& substr_count( $block, 'aria-expanded="true"' ) === 1 && substr_count( $block, 'class="nino-menu-panel"' ) === 1 && substr_count( $block, 'class="nino-menu-col"' ) === 3
	&& array_filter( [ 'nino-menu-title', 'nino-menu-subtitle', 'nino-menu-link--sub', 'nino-menu-image', 'nino-menu-toggle', 'nino-menu-item--current' ], static fn( string $class ): bool => str_contains( $block, $class ) === false ) === [] );

$designSetup = '\\Nino\\Modules\\Design\\Setup';
$designPreview = '\\Nino\\Modules\\Design\\Preview';
$designCompiler = '\\Nino\\Modules\\Design\\Compiler';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the look is not run through it here, bin/check.sh runs it with it in the checkout\n";
else {
	$library = \Nino\Features::dir(). '/Design/library';
	$modulesKept = (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept = $appData['./nino/locales/current'];
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Menu', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		return $designCompiler::compile( $appData, $designSetup::normalize( $appData, $raw, $library, $notes ), $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Menu', 'Design' ] );

	$scanNotes = [];
	$scan = $designSetup::features( $appData, $scanNotes );
	$feature = $scan['menu'] ?? [];
	$catalogue = $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists the feature by its one look, which is the one it has before anything is chosen, and has nothing to say of it',
		isset( $scan['menu'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === [ 'regular' ] && $feature['default'] === 'regular' && $scanNotes === [] );

	$appData['./nino/locales/current'] = 'de_DE';
	$german = $designSetup::features( $appData )['menu']['catalogue']['regular'] ?? [];
	$offTable = [];

	foreach( $catalogue['regular']['knobs'] ?? [] as $name => $knob )
		if( ( $knobTable[$name] ?? null ) !== [ $knob['kind'], $knob['tab'] ] || ( $knob['kind'] === 'scale' && ( $knob['ladder'] ?? [] ) !== [ $name ] ) )
			$offTable[] = $name;

	check( '...described in the language of the workbench by the lines of its head, each of the six knobs answering with the kind and the tab its head names and each scale published by its ladder'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ),
		$offTable === [] && array_keys( $catalogue['regular']['knobs'] ?? [] ) === array_keys( $knobTable ) && ( $german['name'] ?? null ) === $head['name-de'] && ( $german['description'] ?? null ) === $head['description-de'] && ( $catalogue['regular']['name'] ?? null ) === $head['name'] );

	$blockTold = [];
	$blocks = $designPreview::blocks( $appData );
	check( 'the block stands the check Design holds it to, the scan publishes its anchor, feature-menu, and the preview makes the section of it',
		$designSetup::previewOf( (string) $feature['dir'], 'menu', $blockTold ) === trim( $block ) && $blockTold === [] && ( $feature['anchor'] ?? null ) === 'feature-menu' && array_keys( $blocks ) === [ 'menu' ]
		&& preg_match( '~^<section id="feature-menu" class="nino-section" aria-label="[^"]+">\s*(.*?)\s*</section>$~s', $blocks['menu'], $section ) === 1 && str_contains( $section[1] ?? '', 'id="menu-panel"' ) === true );

	$featuresOn( [ 'Menu', 'Design' ] );
	$sheetNotes = [];
	$sheet = $compile( [ 'features' => [ 'menu' => [ 'set' => 'regular' ] ] ], $sheetNotes );
	$sheetProblems = [];

	foreach( [ 'space', 'gap', 'radius', 'shadow', 'title' ] as $ladder )
		if( str_contains( $sheet, "\n\t--menu-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'menu', $ladder, 'default' ). ");\n" ) === false )
			$sheetProblems[] = 'no line for '. $ladder;

	foreach( \Nino\Modules\Design\Colours::parts() as $part )
		if( str_contains( $sheet, "\n\t--menu-ground". ( $part === 'bg' ? '' : '-'. $part ). ': var('. \Nino\Modules\Design\Colours::token( 'default', $part ). ");\n" ) === false )
			$sheetProblems[] = 'no ground part '. $part;

	check( 'with the look chosen the sheet carries its section, the stylesheet as the file has it, a line for the ladder of every scale and every part of the ground the palette solved'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ),
		$sheetProblems === [] && $sheetNotes === [] && preg_match( '~^/\* ==== \d+\. feature menu: regular \(space default, gap default, radius default, shadow default, title default\) ==== \*/$~m', $sheet ) === 1 && str_contains( $sheet, rtrim( $variant, "\n" ) ) === true );

	$moved = $compile( [ 'features' => [ 'menu' => [ 'set' => 'regular', 'knobs' => [ 'space' => -2, 'gap' => 1, 'radius' => 2, 'shadow' => 1, 'title' => -1, 'ground' => 'dark' ] ] ] ] );
	$selects = static fn( string $ladder, string $step ): bool => str_contains( $moved, "\n\t--menu-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'menu', $ladder, $step ). ");\n" );

	check( 'a position moved reaches the sheet as the rung it stands for, and a ground chosen as the palette\'s token of that surface and of every part of it',
		$selects( 'space', 'least' ) && $selects( 'gap', 'more' ) && $selects( 'radius', 'most' ) && $selects( 'shadow', 'more' ) && $selects( 'title', 'less' )
		&& str_contains( $moved, "\n\t--menu-ground: var(". \Nino\Modules\Design\Colours::token( 'dark', 'bg' ). ");\n" ) === true && str_contains( $moved, "\n\t--menu-ground-on: var(". \Nino\Modules\Design\Colours::token( 'dark', 'on' ). ");\n" ) === true );

	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];
	$kernelSheets = (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] );

	check( 'in a booted project the bundle of the website lists menu.css once, behind the kernel\'s own sheets',
		$kernelSheets !== [] && array_keys( $bundle, '/features/Menu/assets/menu.css', true ) === [ count( $kernelSheets ) ] && array_slice( $bundle, 0, count( $kernelSheets ) ) === $kernelSheets );

	$appData['/nino/modules'] = $modulesKept;
	$appData['./nino/locales/current'] = $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
}

echo "\nThe panel\n\n";

$english = (array) include dirname( __DIR__ ). '/text/en_US.php';
$german = (array) include dirname( __DIR__ ). '/text/de_DE.php';
check( 'the words of the panel are the same keys in both languages, each with a text, and every one is a fill of the workbench', array_keys( $english ) === array_keys( $german ) && count( $english ) > 60 && array_filter( [ ...array_values( $english ), ...array_values( $german ) ], static fn( mixed $text ): bool => is_string( $text ) === false || trim( $text ) === '' ) === []
	&& array_filter( array_keys( $english ), static fn( string $key ): bool => preg_match( '~^\[\[/(?:_admin/(?:menu/[a-z-]+(?:/[a-z-]+)?|error/menu_[a-z_]+))\]\]$~', $key ) !== 1 ) === [] );

\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
$post = [ \Nino\Admin\Admin::class, 'handlePost' ];
$actions = [ 'menu/list', 'menu/registry', 'menu/load', 'menu/save', 'menu/delete' ];

$panel = \Nino\Admin\Admin::panels( $appData )['menu'] ?? null;
check( 'the panel is in the workbench while the feature is active, with the permission of a developer and the actions it names', $panel !== null && \Nino\Modules\Menu\Admin::perm() === '/_admin/menu/manage'
	&& array_keys( \Nino\Modules\Menu\Admin::actions() ) === $actions );

$template = (string) file_get_contents( dirname( __DIR__ ). '/templates/panel.tpl' );
check( '...a workspace that renders its own template: the two screens the script draws into, the editor hidden to begin with, and the icon of the rail an svg that runs nothing', \Nino\Modules\Menu\Admin::layout() === 'workspace'
	&& str_ends_with( \Nino\Modules\Menu\Admin::template(), '/features/Menu/templates/panel' ) === true && preg_match( '/<section id="menu-list" class="menu-screen"><\/section>/', $template ) === 1
	&& preg_match( '/<section id="menu-editor" class="menu-screen admin-hidden" tabindex="-1"><\/section>/', $template ) === 1 && str_contains( $template, 'id="menu-root"' ) === true
	&& preg_match( '/^<svg [^>]*>(<(line|rect|path|circle)[^>]*>)+<\/svg>$/', \Nino\Modules\Menu\Admin::icon() ) === 1 && preg_match( '/\son[a-z]+=|<script/i', \Nino\Modules\Menu\Admin::icon(). $template ) !== 1 );

$outcomes = [];
foreach( $actions as $action )
	$outcomes[$action] = ninoCall( $appData, $post, [ 'key' => 'main' ], $action )[0];
check( 'no action answers without an account: 401 for every one', array_values( array_unique( $outcomes ) ) === [ 401 ] );

ninoUser( $appData, 'nobody@example.com', [] );
$outcomes = [];
foreach( $actions as $action )
	$outcomes[$action] = ninoCall( $appData, $post, [ 'key' => 'main' ], $action )[0];
check( '...nor to an account without the permission: 403 for every one, the reads among them', array_values( array_unique( $outcomes ) ) === [ 403 ] );

ninoUser( $appData, 'menu@example.com', [ '/_admin/menu/manage' ] );
$appData['/nino/html/navs'] = [ 'main', 'footer' ];

[ $status, $body ] = ninoCall( $appData, $post, [], 'menu/registry' );
$body = menuBody( $body );
check( 'the registry names the pages that have a name, each once', $status === 200 && $body['pages'] === [ [ 'uri' => '/about', 'name' => 'Über uns', 'names' => [ 'de_DE' => 'Über uns', 'en_US' => 'About' ] ], [ 'uri' => '/contact', 'name' => 'Kontakt', 'names' => [ 'de_DE' => 'Kontakt', 'en_US' => 'Contact' ] ] ] );
check( '...the navigations of the kernel with the pages each lists, the languages, the working language, the slots of the menus and the size a slot is made in',
	$body['navs'] === [ 'main', 'footer' ] && array_keys( $body['navPages'] ) === $body['navs'] && $body['locales'] === [ 'de_DE', 'en_US' ] && $body['locale'] === 'de_DE' && $body['native'] === \Nino\Locales::getNativeLocale( $appData )
	&& array_column( $body['slots'], 'uri' ) === [ '/feature/menu/main/services-4', '/feature/menu/main/services-6', '/feature/menu/main/services-7' ] && $body['slots'][0]['url'] === '/shop/public/images/feature/menu/main/services-4.600x400.webp' && $body['slots'][1]['url'] === null
	&& $body['size'] === [ 'width' => 600, 'height' => 400 ] && $body['names']['de_DE'] !== '' );

$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => '[template /templates/page-home]', 'navs' => [ 'main' => 1 ] ];
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [ '[[/_nino/webpage/home/name]]' => 'Home' ] ) );
unset( $appData['./nino/html/filecache'] );
$body = menuBody( ninoCall( $appData, $post, [], 'menu/registry' )[1] );
check( 'a navigation lists the Element-URIs of its pages, in its order: the route of the root is the page /home', $body['navPages']['main'] === [ '/home' ] && in_array( '/home', array_column( $body['pages'], 'uri' ), true ) === true );

$appData['/nino/http/routes']['GET://missing'] = [ 'uri' => '/missing', 'body' => '[template /templates/page-missing]', 'statusCode' => 404 ];
$appData['/nino/http/routes']['GET://robots.txt'] = [ 'uri' => '/robots', 'body' => '[template /templates/robots]', 'header' => [ 'Content-Type' => 'text/plain' ] ];
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', array_merge( (array) \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] ), [ '[[/_nino/webpage/missing/name]]' => 'Missing', '[[/_nino/webpage/robots/name]]' => 'Robots' ] ) );
unset( $appData['./nino/html/filecache'] );
$appData['/nino/html/navs'] = [ 'main', 'Bad Key', 'panel', 'footer', 'a/b' ];
$body = menuBody( ninoCall( $appData, $post, [], 'menu/registry' )[1] );
check( 'a page that answers an error, and one that answers no page, are not offered; the navigations of the kernel are those that can be a key of a menu', in_array( '/missing', array_column( $body['pages'], 'uri' ), true ) === false && in_array( '/robots', array_column( $body['pages'], 'uri' ), true ) === false
	&& $body['navs'] === [ 'main', 'footer' ] && menuBody( ninoCall( $appData, $post, [], 'menu/list' )[1] )['navs'] === [ 'main', 'footer' ] );
$appData['/nino/html/navs'] = [ 'main', 'footer' ];

[ $status, $body ] = ninoCall( $appData, $post, [], 'menu/list' );
check( 'no menu yet: the list is empty and offers the keys of the kernel\'s navigations', $status === 200 && $body['menus'] === [] && $body['navs'] === menuBody( ninoCall( $appData, $post, [], 'menu/registry' )[1] )['navs'] && in_array( 'main', $body['navs'], true ) === true );

echo "\nSaving a menu\n\n";

$model = [
	[ 'id' => 'docs', 'kind' => 'page', 'uri' => '/about', 'label' => '/feature/menu/side/docs', 'panel' => [ 'columns' => 2, 'cols' => [
		[ [ 'kind' => 'title', 'key' => '/feature/menu/side/docs-1' ], [ 'kind' => 'image', 'slot' => '/feature/menu/side/docs-2' ] ],
		[ [ 'kind' => 'link', 'href' => 'https://example.com/b', 'target' => '_blank', 'label' => '/feature/menu/side/docs-3' ], [ 'kind' => 'html', 'html' => '<p>Own</p>' ] ],
	] ] ],
	[ 'id' => 'blog', 'kind' => 'link', 'href' => '/blog', 'target' => '', 'label' => '/feature/menu/side/blog', 'panel' => null ],
];
$texts = [
	'/feature/menu/side/docs' => [ 'en_US' => 'Docs', 'de_DE' => 'Doku' ], '/feature/menu/side/docs-1' => [ 'en_US' => 'Read <b>on</b>' ],
	'/feature/menu/side/docs-3' => [ 'en_US' => 'The blog' ], '/feature/menu/side/blog' => [ 'en_US' => 'Blog', 'de_DE' => 'Blog' ],
];
$save = [ 'key' => 'side', 'entries' => $model, 'texts' => $texts ];
$file = static fn(): mixed => \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );

[ $status, $body ] = ninoCall( $appData, $post, $save, 'menu/save' );
check( 'a save that has to make keys is refused for an account without the Text Keys permission: 403, the code and the permission', $status === 403 && $body['code'] === 'menu_permission' && $body['params'] === [ '/_admin/keys/manage' ] );
check( '...and has written nothing: no key, no slot, no data', \Nino\Text::entry( $appData, '/feature/menu/side/docs' ) === null && \Nino\Images::getSlot( $appData, '/feature/menu/side/docs-2' ) === false && $file() === [] );

ninoUser( $appData, 'keys@example.com', [ '/_admin/menu/manage', '/_admin/keys/manage' ] );
[ $status, $body ] = ninoCall( $appData, $post, $save, 'menu/save' );
check( 'a save that has to make a slot is refused for an account without the Image Slots permission, the permission named', $status === 403 && $body['code'] === 'menu_permission' && $body['params'] === [ '/_admin/slots/manage' ]
	&& \Nino\Text::entry( $appData, '/feature/menu/side/docs' ) === null && $file() === [] );

$noPicture = $save;
$noPicture['entries'][0]['panel']['cols'][0] = [ [ 'kind' => 'title', 'key' => '/feature/menu/side/docs-1' ] ];
[ $status, $body ] = ninoCall( $appData, $post, $noPicture, 'menu/save' );
check( 'with the Text Keys permission a menu without a new slot is saved: 200, the keys it made named', $status === 200 && $body['key'] === 'side' && $body['entries'] === 2
	&& $body['made'] === [ 'keys' => [ '/feature/menu/side/docs', '/feature/menu/side/docs-1', '/feature/menu/side/docs-3', '/feature/menu/side/blog' ], 'slots' => [] ] );

unset( $appData['./nino/html/filecache'] );
$stored = \Nino\Text::entry( $appData, '/feature/menu/side/docs-1' );
check( '...each key is made for every language with the text typed for it, plain text, and none where none was typed', \Nino\Text::entry( $appData, '/feature/menu/side/docs' )['values'] === [ 'de_DE' => 'Doku', 'en_US' => 'Docs' ]
	&& $stored['values']['en_US'] === 'Read on' && ( $stored['values']['de_DE'] ?? '' ) === '' && $stored['format'] === 'plain' && $stored['formatSet'] === true && $stored['global'] === false );
check( '...and the data holds the menu as it was sent, the other menus as they were', $file() === [ 'side' => [ 'entries' => $noPicture['entries'] ] ] );

$html = menuRender( $appData, '[menu /side]', '/about' );
$dom = menuDom( $html );
check( 'the page draws what was saved: the label from the text, the title with its markup taken out', menuFind( $dom, '//ul[@class="nino-menu"]/li[1]/a[@class="nino-menu-link nino-is-active"]' ) === [ 'Docs' ] && menuFind( $dom, '//p[@class="nino-menu-title"]' ) === [ 'Read on' ]
	&& menuFind( $dom, '//ul[@class="nino-menu"]/li[2]/a/@href' ) === [ '/shop/blog' ] );

[ $status, $body ] = ninoCall( $appData, $post, $noPicture, 'menu/save' );
check( 'the same menu saved again changes nothing and makes nothing', $status === 200 && $body['made'] === [ 'keys' => [], 'slots' => [] ] && $file() === [ 'side' => [ 'entries' => $noPicture['entries'] ] ] );

ninoUser( $appData, 'menu2@example.com', [ '/_admin/menu/manage' ] );
[ $status, $body ] = ninoCall( $appData, $post, $noPicture, 'menu/save' );
check( 'an account with the permission of the panel alone may save a menu that makes and writes nothing', $status === 200 );

$changed = $noPicture;
$changed['texts']['/feature/menu/side/blog']['en_US'] = 'Our blog';
[ $status, $body ] = ninoCall( $appData, $post, $changed, 'menu/save' );
check( '...but not one that writes a text: 403 again, the text as it was', $status === 403 && $body['params'] === [ '/_admin/keys/manage' ] && \Nino\Text::entry( $appData, '/feature/menu/side/blog' )['values']['en_US'] === 'Blog' );

ninoUser( $appData, 'dev@example.com', [ '/*' ] );
[ $status, $body ] = ninoCall( $appData, $post, $changed, 'menu/save' );
unset( $appData['./nino/html/filecache'] );
check( 'with the permission a text that differs is written, in the language it was sent for and in no other', $status === 200 && $body['made']['keys'] === [] && \Nino\Text::entry( $appData, '/feature/menu/side/blog' )['values'] === [ 'de_DE' => 'Blog', 'en_US' => 'Our blog' ] );

$cleared = $noPicture;
$cleared['texts']['/feature/menu/side/blog']['en_US'] = '';
$_POST['keep'] = 'mine';
[ $status ] = ninoCall( $appData, $post, $cleared, 'menu/save' );
$afterSave = $_POST;
unset( $appData['./nino/html/filecache'] );
check( 'a text that is cleared is cleared: the empty text is written, the Text panel and the load of the menu answer it empty, the other language is as it was, and a page falls back to the other language', $status === 200
	&& \Nino\Text::entry( $appData, '/feature/menu/side/blog' )['values'] === [ 'de_DE' => 'Blog', 'en_US' => '' ] && menuBody( ninoCall( $appData, $post, [ 'key' => 'side' ], 'menu/load' )[1]['texts'] )['/feature/menu/side/blog'] === [ 'de_DE' => 'Blog', 'en_US' => '' ] );
check( 'the posted data of the request is as it was after the calls the panel makes to the panels of the others - the keys it wrote - and what else was posted is left alone', $afterSave['data'] === json_encode( $cleared ) && $afterSave['keep'] === 'mine' );
unset( $_POST['keep'] );
ninoCall( $appData, $post, $changed, 'menu/save' );

[ $status, $body ] = ninoCall( $appData, $post, $save, 'menu/save' );
$slot = \Nino\Images::getSlot( $appData, '/feature/menu/side/docs-2' );
check( 'an account that may do everything also makes the slot of a picture, in the size the panel makes them in', $status === 200 && $body['made'] === [ 'keys' => [], 'slots' => [ '/feature/menu/side/docs-2' ] ]
	&& $slot !== false && $slot['width'] === 600 && $slot['height'] === 400 && ( $slot['filename'] ?? null ) === null );
check( '...a slot without a picture draws nothing, and the menu has the item all the same', substr_count( menuRender( $appData, '[menu /side]' ), '<img' ) === 0 && $file()['side']['entries'][0]['panel']['cols'][0][1] === [ 'kind' => 'image', 'slot' => '/feature/menu/side/docs-2' ] );

echo "\nWhat a save refuses\n\n";

$refused = static function( array $change ) use ( $post, &$appData, $file ): array {
	$before = $file();
	$payload = array_replace_recursive( [ 'key' => 'side', 'entries' => [], 'texts' => [] ], $change );
	[ $status, $body ] = ninoCall( $appData, $post, $payload, 'menu/save' );
	return [ $status, $body['code'] ?? '', $body['params'] ?? [], $file() === $before ];
};

$bad = static function( callable $change ) use ( $model ): array {
	$entries = $model;
	$change( $entries );
	return $entries;
};

check( 'a key that is no slug is refused with its code, the field and no write', ( static function() use ( &$appData, $post, $model ): bool {
	[ $status, $body ] = ninoCall( $appData, $post, [ 'key' => 'Side Bar', 'entries' => $model ], 'menu/save' );
	return $status === 400 && $body['code'] === 'menu_key' && $body['field'] === 'key' && ninoCall( $appData, $post, [ 'key' => "a\0b" ], 'menu/load' )[0] === 400 && ninoCall( $appData, $post, [ 'key' => '../x' ], 'menu/delete' )[0] === 400;
} )() );

$one = $refused( [ 'entries' => $bad( static function( array &$e ): void { $e[0]['panel']['cols'][0][0]['key'] = "/feature/menu/side/docs-1\0"; } ) ] );
check( 'a null byte in a key: 400 menu_invalid, the entry named in the params, nothing written', $one === [ 400, 'menu_invalid', [ 'docs' ], true ] );

$two = $refused( [ 'entries' => $bad( static function( array &$e ): void { $e[0]['uri'] = '/a/../b'; $e[1]['href'] = 'javascript:alert(1)'; } ) ] );
check( 'a path with .. as a uri and an address that would be drawn as #: both entries named', $two === [ 400, 'menu_invalid', [ 'docs, blog' ], true ] );

check( 'texts for a key the menu does not have, in a language it does not have, that are not text or longer than 300 characters or hold a line break are refused', ( static function() use ( $refused, $model ): bool {
	foreach( [
		[ '/feature/menu/side/other' => [ 'en_US' => 'x' ] ], [ '/feature/menu/side/docs' => [ 'xx_XX' => 'x' ] ], [ '/feature/menu/side/docs' => [ 'en_US' => [ 'x' ] ] ],
		[ '/feature/menu/side/docs' => [ 'en_US' => str_repeat( 'x', 301 ) ] ], [ '/feature/menu/side/docs' => [ 'en_US' => "a\nb" ] ], [ '/feature/menu/side/docs' => [ 'en_US' => "a\0b" ] ],
	] as $texts )
		if( array_slice( $refused( [ 'entries' => $model, 'texts' => $texts ] ), 0, 2 ) !== [ 400, 'menu_texts' ] )
			return false;
	return true;
} )() );

check( 'entries that are none and texts that are none are refused', array_slice( $refused( [ 'entries' => 'x' ] ), 0, 2 ) === [ 400, 'menu_list' ] );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$sentences = \Nino\Admin\Admin::textFills( $appData, '/features/Menu/text', $locale );
	$missing = array_filter( [ 'menu_key', 'menu_invalid', 'menu_texts', 'menu_missing', 'menu_permission', 'menu_key_made', 'menu_slot_made', 'menu_list' ], static fn( string $code ): bool => ( $sentences['[[/_admin/error/'. $code. ']]'] ?? '' ) === '' );
	check( 'every code the panel answers has its sentence in '. $locale. ( $missing === [] ? '' : ' - '. implode( ', ', $missing ) ), $missing === [] );
}

echo "\nLoading, listing and deleting\n\n";

[ $status, $body ] = ninoCall( $appData, $post, [ 'key' => 'side' ], 'menu/load' );
check( 'a menu loads as it was saved, with the text of every key it names in every language it has and the names of its keys and slots', $status === 200 && $body['entries'] === $save['entries'] && $body['kernel'] === false && $body['problems'] === []
	&& menuBody( $body['texts'] )['/feature/menu/side/docs'] === [ 'de_DE' => 'Doku', 'en_US' => 'Docs' ]
	&& $body['taken'] === [ '/feature/menu/side/blog', '/feature/menu/side/docs', '/feature/menu/side/docs-1', '/feature/menu/side/docs-3', '/feature/menu/side/docs-2' ] );
check( '...one that is not there is a 404 with its code', ( static function() use ( &$appData, $post ): bool { [ $status, $body ] = ninoCall( $appData, $post, [ 'key' => 'nothing' ], 'menu/load' ); return $status === 404 && $body['code'] === 'menu_missing'; } )() );

$kept = $file();
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, $kept + [ 'edited' => [ 'entries' => [
	[ 'id' => 'ok', 'kind' => 'page', 'uri' => '/about', 'label' => '', 'panel' => null ], [ 'id' => 'up', 'kind' => 'page', 'uri' => '/a/../b', 'label' => '', 'panel' => null ],
	[ 'id' => 'null', 'kind' => 'link', 'href' => "/a\0b", 'target' => '', 'label' => '/feature/menu/edited/null' ], 'text', [ 'id' => 'ok-b', 'kind' => 'link', 'href' => 'javascript:alert(1)', 'target' => '', 'label' => '/feature/menu/edited/ok-b' ],
] ] ] );
$loaded = ninoCall( $appData, $post, [ 'key' => 'edited' ], 'menu/load' )[1];
check( 'a menu a hand edited the file of loads only what is valid, and names what is not: the climbing path, the null byte, what is no entry - the address that is drawn as # stays, for the person to see and the save to refuse', array_column( $loaded['entries'], 'id' ) === [ 'ok', 'ok-b' ] && $loaded['problems'] === [ 'up', 'null', '#4' ] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Menu::STORAGE, $kept );

ninoCall( $appData, $post, [ 'key' => 'main', 'entries' => [ [ 'id' => 'home', 'kind' => 'page', 'uri' => '/home', 'label' => '', 'panel' => null ] ], 'texts' => [] ], 'menu/save' );
[ $status, $body ] = ninoCall( $appData, $post, [], 'menu/list' );
check( 'the list has a row for each menu - its key, its entries and whether it stands in for a navigation of the kernel', $status === 200 && $body['menus'] === [
	[ 'key' => 'main', 'entries' => 1, 'kernel' => true ], [ 'key' => 'side', 'entries' => 2, 'kernel' => false ],
] );
check( '...and a menu of a kernel key loads as one', ninoCall( $appData, $post, [ 'key' => 'main' ], 'menu/load' )[1]['kernel'] === true );

[ $status, $body ] = ninoCall( $appData, $post, [ 'key' => 'side' ], 'menu/delete' );
check( 'delete takes the menu out of the data and leaves the other, and the texts and the slots it made', $status === 200 && array_keys( $file() ) === [ 'main' ] && \Nino\Text::entry( $appData, '/feature/menu/side/docs' ) !== null
	&& \Nino\Images::getSlot( $appData, '/feature/menu/side/docs-2' ) !== false );
check( '...a menu that is not there is a 404, and a draw of it nothing', ninoCall( $appData, $post, [ 'key' => 'side' ], 'menu/delete' )[0] === 404 && menuRender( $appData, '[menu /side]' ) === '' );

echo "\nWhen the write fails\n\n";

$path = \Nino\Filesystem::path( $appData, \Nino\Modules\Menu::STORAGE );
$saved = file_get_contents( $path );
unlink( $path );
mkdir( $path );
$failing = [ 'key' => 'lost', 'entries' => [ [ 'id' => 'only', 'kind' => 'link', 'href' => '/x', 'target' => '', 'label' => '/feature/menu/lost/only', 'panel' => [ 'columns' => 1, 'cols' => [ [ [ 'kind' => 'image', 'slot' => '/feature/menu/lost/only-1' ] ] ] ] ] ], 'texts' => [ '/feature/menu/lost/only' => [ 'en_US' => 'Only' ] ] ];
ninoErrors( static function() use ( &$appData, $post, $failing, &$status, &$body ): void { [ $status, $body ] = ninoCall( $appData, $post, $failing, 'menu/save' ); } );
check( 'a data file that cannot be written ends the save with 500, and the key and the slot it made are taken away again', $status === 500 && \Nino\Text::entry( $appData, '/feature/menu/lost/only' ) === null && \Nino\Images::getSlot( $appData, '/feature/menu/lost/only-1' ) === false );
rmdir( $path );
file_put_contents( $path, $saved );
ninoWarnings();

echo "\nThe log and the end\n\n";

check( 'the log records a save and a delete by the key, and says nothing of a read', \Nino\Modules\Menu\Admin::log( 'menu/save', [ 'key' => 'side' ] ) === 'Save Menu "side"' && \Nino\Modules\Menu\Admin::log( 'menu/delete', [ 'key' => 'side' ] ) === 'Delete Menu "side"'
	&& \Nino\Modules\Menu\Admin::log( 'menu/load', [ 'key' => 'side' ] ) === '' && \Nino\Modules\Menu\Admin::log( 'menu/save', [ 'key' => "x\ny" ] ) === 'Save Menu "?"' );

$before = $file();
check( 'deactivation takes the panel away and keeps the data', \Nino\Features::deactivate( $appData, 'menu' ) === true && isset( \Nino\Admin\Admin::panels( $appData )['menu'] ) === false && $file() === $before );

// The scripts, where node is on the path: menu-js-smoke.js beside this file runs menu.js and the panel's script, so that bin/check.sh and CI cover both halves in one go
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: menu-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/menu-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'menu-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
