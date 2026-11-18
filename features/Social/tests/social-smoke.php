<?php
declare(strict_types=1);

// The test of the feature Social: structure and behaviour, one pin each, and the security boundary named in its labels.
// php features/Social/tests/social-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel', is_array( $manifest ) === true && $manifest['key'] === 'social' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true );

// The elements of a project: the type the unit installs, with the links it ships, and what a test adds to it
$type = require $dir. '/install/social.php';
$setElements = static function( array &$appData, array $links ) use ( $type ): void {

	$data = $type;
	$data['*'] = [ '*' => [ 'hidden' => false ] ] + $links;
	\Nino\Filesystem::putFileContent( $appData, '/elements/social.php', $data );
};
$site = static function( array $links = [], string $locale = 'de_DE' ) use ( $root, $setElements ): array {

	$appData = ninoSandbox( 'social' );
	$appData['./nino/locales/current'] = $locale;
	$appData['./nino/filesystem/path'] = $root;
	$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Social', '\\Nino\\Modules\\Elements' ];
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );
	$setElements( $appData, $links );

	return $appData;
};
$draw = static fn( array &$appData, string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );

$appData = $site( $type['*'] + [] );
$shipped = $site( array_diff_key( $type['*'], [ '*' => 1 ] ) );
check( 'the folders are read without a warning', ninoWarnings() === [] && \Nino\Features::componentProblems( $appData, 'social' ) === [] );

$schemas = \Nino\Components::components( $appData );
$names = [ 'social', 'social-link', 'social-icon' ];
check( 'the three components are registered in their group, with a label in both languages, an icon of the table and a standard part',
	array_filter( $names, static fn( string $name ): bool => ( $schemas[$name]['group'] ?? '' ) !== 'social' || isset( $schemas[$name]['label']['de_DE'], $schemas[$name]['label']['en_US'] ) === false || in_array( $schemas[$name]['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['social']['part'] ?? false ) === true );
check( 'every attribute has a label in both languages', array_filter( $names, static fn( string $name ): bool => array_filter( $schemas[$name]['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) !== [] ) === [] );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/social/part/part.css' ), $names ) === [] );
check( 'a tool offers the list in a column, in the foot and in the actions of the header',
	array_filter( [ 'section-col', 'footer-col', 'header-actions' ], static fn( string $place ): bool => array_diff( $names, \Nino\Components::allowed( $appData, $place ) ) !== [] ) === [] );
check( 'the group has a size and a gap as knobs, and two variants besides the plain one', array_keys( \Nino\Components::groups( $appData )['social']['knobs'] ?? [] ) === [ 'social.size', 'social.gap' ]
	&& ( \Nino\Components::variants( $appData, 'social' )['variants'] ?? [] ) !== [] && array_keys( \Nino\Components::variants( $appData, 'social' )['variants'] ) === [ 'regular', 'badges', 'outline' ] );

// The list
$html = $draw( $appData, '[social show="both"]' );
check( 'the list draws a link for each link that is not hidden, in the order of the position, each with the icon of its own',
	preg_match_all( '#<a class="social-link" href="([^"]+)"#', $html, $found ) === 4 && $found[1] === [ 'https://www.instagram.com/', 'https://www.facebook.com/', 'https://www.youtube.com/', 'https://t.me/' ]
	&& substr_count( $html, '<svg' ) === 4 && str_starts_with( $html, '<ul class="social social--show-both">' ) === true );
$positions = $site( [
	'late'	=> [ 'title' => 'Late', 'icon' => 'rss', 'link' => 'https://example.org/late', 'order' => 0 ],
	'two'		=> [ 'title' => 'Two', 'icon' => 'blog', 'link' => 'https://example.org/two', 'order' => 20 ],
	'one'		=> [ 'title' => 'One', 'icon' => 'blog', 'link' => 'https://example.org/one', 'order' => 5 ],
	'gone'	=> [ 'title' => 'Gone', 'icon' => 'blog', 'link' => 'https://example.org/gone', 'order' => 1, 'hidden' => true ],
] );
check( 'a hidden link is left out, a position that is none goes last', preg_match_all( '#href="https://example.org/([a-z]+)"#', $draw( $positions, '[social]' ), $order ) === 3 && $order[1] === [ 'one', 'two', 'late' ] );
check( 'only picks by id and keeps the order of the positions, exclude leaves some out',
	substr_count( $draw( $appData, '[social only="youtube, instagram, nobody"]' ), '<li class' ) === 2 && str_contains( $draw( $appData, '[social only="youtube,instagram"]' ), 'instagram.com/"' ) === true
	&& substr_count( $draw( $appData, '[social exclude="telegram"]' ), '<li class' ) === 3 && str_contains( $draw( $appData, '[social exclude="telegram"]' ), 't.me' ) === false );
check( 'show is a class, and the icon alone, the name alone and both are all drawn with the name of the link in the page',
	str_contains( $draw( $appData, '[social show="icon"]' ), 'social-link social-link--show-icon' ) === true && substr_count( $draw( $appData, '[social show="icon"]' ), 'class="social-label"' ) === 4
	&& substr_count( $draw( $appData, '[social show="text"]' ), '<svg' ) === 0 && substr_count( $draw( $appData, '[social show="text"]' ), 'class="social-label"' ) === 4
	&& substr_count( $draw( $appData, '[social show="both"]' ), '<svg' ) === 4 && str_contains( $draw( $appData, '[social show="both"]' ), 'social-link--show-' ) === false && str_contains( $draw( $appData, '[social]' ), 'social-link--show-icon' ) === true );
check( 'a size and a variant are classes of the register', str_contains( $draw( $appData, '[social size="large" variant="badges"]' ), 'class="social social--size-large social--variant-badges"' ) === true
	&& str_contains( $draw( $appData, '[social size="huge" variant="neon"]' ), 'class="social"' ) === true );
check( 'with no link left the list draws nothing', $draw( $site( [] ), '[social]' ) === '' && $draw( $appData, '[social only="nobody"]' ) === '' );

// SECURITY: the address of a link is held to a list of schemes
$hostile = $site( [
	'js'		=> [ 'title' => 'Js', 'icon' => 'blog', 'link' => 'javascript:alert(1)', 'order' => 10 ],
	'jsu'		=> [ 'title' => 'JsU', 'icon' => 'blog', 'link' => 'JavaScript:alert(1)', 'order' => 11 ],
	'tab'		=> [ 'title' => 'Tab', 'icon' => 'blog', 'link' => "java\tscript:alert(1)", 'order' => 12 ],
	'data'	=> [ 'title' => 'Data', 'icon' => 'blog', 'link' => 'data:text/html;base64,PHNjcmlwdD4=', 'order' => 13 ],
	'vb'		=> [ 'title' => 'Vb', 'icon' => 'blog', 'link' => 'vbscript:msgbox(1)', 'order' => 14 ],
	'proto'	=> [ 'title' => 'Proto', 'icon' => 'blog', 'link' => '//evil.example/x', 'order' => 15 ],
	'back'	=> [ 'title' => 'Back', 'icon' => 'blog', 'link' => '/\\evil.example/x', 'order' => 16 ],
	'space'	=> [ 'title' => 'Space', 'icon' => 'blog', 'link' => 'https://example.org/a b', 'order' => 17 ],
	'user'	=> [ 'title' => 'User', 'icon' => 'blog', 'link' => 'https://good.example@evil.example/', 'order' => 18 ],
	'bare'	=> [ 'title' => 'Bare', 'icon' => 'blog', 'link' => 'example.org', 'order' => 19 ],
	'ok'		=> [ 'title' => 'Ok', 'icon' => 'blog', 'link' => 'https://example.org/ok', 'order' => 30 ],
] );
$html = $draw( $hostile, '[social]' );
check( 'the address of a link is held to a list of schemes: javascript, data, vbscript, a host without a scheme, a tab, a space and user@host are left out',
	preg_match_all( '#href="([^"]*)"#', $html, $found ) === 1 && $found[1] === [ 'https://example.org/ok' ] && str_contains( strtolower( $html ), 'javascript' ) === false );
check( 'the address check answers the schemes a link may have and nothing else',
	\Nino\Modules\Social::safeUrl( 'https://example.org/' ) !== null && \Nino\Modules\Social::safeUrl( 'mailto:a@example.org' ) !== null && \Nino\Modules\Social::safeUrl( 'tel:+49123' ) !== null && \Nino\Modules\Social::safeUrl( '/contact' ) !== null
	&& \Nino\Modules\Social::safeUrl( 'ftp://example.org/' ) === null && \Nino\Modules\Social::safeUrl( 'javascript:alert(1)' ) === null );

// SECURITY: rel and target
$mixed = $site( [
	'web'		=> [ 'title' => 'Web', 'icon' => 'website', 'link' => 'https://example.org/', 'order' => 10 ],
	'plain'	=> [ 'title' => 'Plain', 'icon' => 'website', 'link' => 'http://example.org/', 'order' => 20 ],
	'mail'	=> [ 'title' => 'Mail', 'icon' => 'mail', 'link' => 'mailto:a@example.org', 'order' => 30 ],
	'tel'		=> [ 'title' => 'Tel', 'icon' => 'phone', 'link' => 'tel:+49123', 'order' => 40 ],
	'path'	=> [ 'title' => 'Path', 'icon' => 'map', 'link' => '/contact', 'order' => 50 ],
] );
preg_match_all( '#<a [^>]*>#', $draw( $mixed, '[social]' ), $anchors );
check( 'an address on the web opens in a new tab with rel="me noopener", a mail, a phone and a path of the site carry neither',
	count( $anchors[0] ) === 5 && array_values( array_map( static fn( string $a ): bool => str_contains( $a, ' target="_blank" rel="me noopener">' ), $anchors[0] ) ) === [ true, true, false, false, false ]
	&& str_contains( $anchors[0][4], 'href="/contact"' ) === true && str_contains( $anchors[0][4], 'rel=' ) === false );
check( 'a path of the site gets the directory the site is served from', ( static function() use ( $mixed, $draw ): bool { $mixed['/nino/dir'] = '/shop'; return str_contains( $draw( $mixed, '[social]' ), 'href="/shop/contact"' ); } )() === true );

// SECURITY: an svg is a file of the feature, never put together from what a project holds
$icons = $site( [
	'a'		=> [ 'title' => 'A', 'icon' => '../../../../etc/passwd', 'link' => 'https://example.org/a', 'order' => 10 ],
	'b'		=> [ 'title' => 'B', 'icon' => '<svg onload=alert(1)>', 'link' => 'https://example.org/b', 'order' => 20 ],
	'c'		=> [ 'title' => 'C', 'icon' => 'LICENSE', 'link' => 'https://example.org/c', 'order' => 30 ],
	'd'		=> [ 'title' => 'D', 'icon' => 'github.svg', 'link' => 'https://example.org/d', 'order' => 40 ],
	'e'		=> [ 'title' => 'E', 'icon' => 'github', 'link' => 'https://example.org/e', 'order' => 50 ],
] );
$html = $draw( $icons, '[social]' );
$files = array_map( static fn( string $file ): string => basename( $file, '.svg' ), glob( $dir. '/icons/*.svg' ) ?: [] );
sort( $files );
$listed = \Nino\Modules\Social::ICONS;
sort( $listed );
check( 'an icon that is none of the list draws the link icon: the file, the markup and the traversal of an element are never read',
	substr_count( $html, '<svg' ) === 5 && substr_count( $html, 'onload' ) === 0 && substr_count( $html, 'passwd' ) === 0 && substr_count( $html, 'Permission' ) === 0
	&& substr_count( $html, trim( (string) \Nino\Filesystem::getFileContent( $icons, '/features/Social/icons/link.svg', '' ) ) ) === 4 && substr_count( $html, trim( (string) \Nino\Filesystem::getFileContent( $icons, '/features/Social/icons/github.svg', '' ) ) ) === 1 );
check( 'the list of icons is the files of the feature, the options of the element type and the options of the component, and each file is an svg that holds no script',
	$listed === $files && array_diff( $type['model']['icon']['options'], $listed ) === []
	&& array_diff( $listed, $type['model']['icon']['options'] ) === [] && array_keys( \Nino\Modules\Social::iconOptions( $appData, 'name' ) ) === \Nino\Modules\Social::ICONS
	&& array_filter( $files, static function( string $name ) use ( $dir ): bool {
		$svg = trim( (string) file_get_contents( $dir. '/icons/'. $name. '.svg' ) );
		return str_starts_with( $svg, '<svg' ) === false || preg_match( '/<script|<foreignObject|\son[a-z]+\s*=|javascript:|<a\s|href\s*=/i', $svg ) === 1;
	} ) === [] );

// An icon has a name for a screen reader: in a link the name of the link, alone the one the call gives it
check( 'each link has its name as text, whatever it shows, and the icon in it is hidden from a screen reader',
	array_filter( [ 'icon', 'text', 'both' ], static function( string $show ) use ( $appData, $draw ): bool {
		$html = $draw( $appData, '[social show="'. $show. '"]' );
		return preg_match_all( '#<a [^>]*>(.*?)</a>#s', $html, $links ) !== 4 || array_filter( $links[1], static fn( string $inner ): bool => preg_match( '#<span class="social-label">[^<]+</span>#', $inner ) !== 1 ) !== []
			|| substr_count( $html, 'aria-hidden="true"><svg' ) !== ( $show === 'text' ? 0 : 4 );
	} ) === [] );
$named = $draw( $appData, '[social-icon name="telegram" label="Telegram"]' );
check( 'an icon alone is named by its label, and hidden where a word stands beside it',
	str_contains( $named, 'role="img" aria-label="Telegram"' ) === true && str_contains( $named, 'aria-hidden="true"><svg' ) === false
	&& str_contains( $draw( $appData, '[social-icon name="telegram"]' ), 'aria-hidden="true"><svg' ) === true && str_contains( $draw( $appData, '[social-icon name="telegram"]' ), 'aria-label' ) === false
	&& str_contains( $draw( $appData, '[social-icon name="nothing" label="x"]' ), 'github' ) === false && substr_count( $draw( $appData, '[social-icon name="nothing"]' ), '<svg' ) === 1 );

// The single link
$one = $draw( $appData, '[social-link profile="youtube"]' );
check( 'one link is drawn with icon and name, and a profile that is none draws nothing',
	str_starts_with( $one, '<a class="social-link" href="https://www.youtube.com/" target="_blank" rel="me noopener">' ) === true && substr_count( $one, '<svg' ) === 1
	&& $draw( $appData, '[social-link profile="nobody"]' ) === '' && $draw( $appData, '[social-link]' ) === '' && $draw( $appData, '[social-link profile="../x"]' ) === '' );
check( 'the options of a profile are the links to draw', array_keys( \Nino\Modules\Social::linkOptions( $appData, 'profile' ) ) === [ 'instagram', 'facebook', 'youtube', 'telegram' ] );

// SECURITY: what an element holds is text
$words = "[csrf] [[/project/company/general/name]] [template /templates/evil] <script>alert(1)</script> \"><img src=x onerror=alert(1)>";
$text = $site( [ 'x' => [ 'title' => $words, 'icon' => 'blog', 'link' => 'https://example.org/?a=1&b="2"', 'order' => 10 ] ] );
$html = $draw( $text, '[social show="both"]' ). $draw( $text, '[social-link profile="x"]' ). $draw( $text, '[social-icon label="\"><script>alert(1)</script> [csrf]"]' );
check( 'the name and the address of a link are text: a call, a fill, a tag and a quote in them stay what they are',
	str_contains( $html, '<script' ) === false && str_contains( $html, '<img' ) === false && str_contains( $html, 'name="csrf"' ) === false && str_contains( $html, 'evil' ) === true
	&& str_contains( $html, '[[/project/company/general/name]]' ) === true && str_contains( $html, 'href="https://example.org/?a=1&amp;b=&quot;2&quot;"' ) === true && substr_count( $html, '&amp;amp;' ) === 0 );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the list three times', ( $groups['social']['demo'] ?? '' ) !== '' && substr_count( $draw( $appData, $groups['social']['demo'] ), '<ul class="social' ) === 3 );
$block = \Nino\Blocks::get( $appData, 'social-row' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'social', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );
check( 'the block reads no key of the project and draws the list', preg_match( '#\[\[|/project/|\s/#', (string) file_get_contents( $dir. '/blocks/social-row/block.tpl' ) ) !== 1 && str_contains( $draw( $appData, (string) file_get_contents( $dir. '/blocks/social-row/block.tpl' ) ), '<ul class="social">' ) === true );
check( 'the part is in the style bundle', str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.social-link' ) === true );

$off = ninoSandbox( 'social-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[social]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && str_contains( (string) $file, '/icons/' ) === false && preg_match( '/Feature::shortcode|Feature::template|Feature::classes|addShortcode|Modules\\\\Components|nino-social|nino-sr-only|nino-icon|--color-|--space-[0-9]|--radius-|--fontsize-|design\//', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] );

ninoDone( $appData );
