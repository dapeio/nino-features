<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-source-smoke.php	Contract test for what the Builder adds to a page that is opened as blocks: where a content comes from, the pictures, the blocks of the
 *														project and the pages that are made:
 *
 *														A. the source of a content: each kind is taken, what is none is refused and nothing is written, the words of another place are never written,
 *														   the texts are found by name or by text, and a template that is not read says the line
 *														B. the pictures: an upload is made at once and given to its slot by the save, never the file of another slot, what nobody gets is taken away
 *														C. the blocks of the project: made only where the kernel takes them, only below private/blocks/, never over another, taken away only if they are the project's
 *														D. a new page: the template, the routes, the menu entry and the words in one step - and nothing where one of them is refused
 *														E. a link that reads a text, F. what the panel offers and what the content step writes
 *														G. a page copied and taken away: the right and the names the actions hold to, what a page that is taken leaves of the Builder's own, what a new page
 *														   does not write over, the copy and what it is answered with
 *
 *														One sandbox with the kernel's Basis and the collections; the pages are written into it as the starter site has them.
 *
 *	Usage: php features/Builder/tests/builder-source-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-source-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Tree;

$feature = dirname( __DIR__ );
$numbers = [];

/** One action of the API, as the workbench posts it: [ status, body ] */
function sourceCall( array &$appData, string $action, array $data = [] ): array {

	// A call is made in a language: the native one unless it names another - or null, which names none
	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, $body ];
}

/** Everything the Builder may write, by name: what is in the files of the project and the pictures - for "nothing is written" */
function sourceSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text', 'private/blocks', 'private/data/builder', 'public/images' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	$list['config'] = is_file( $dir. '/private/config.php' ) === true ? hash_file( 'sha256', $dir. '/private/config.php' ) : '';

	return $list;
}

/** The position of the first node a test says yes to, depth first - '2.1.1' - or null */
function sourceFind( array $nodes, callable $test, string $prefix = '' ): ?string {

	foreach( $nodes as $at => $node ) {

		$here = $prefix === '' ? (string) $at : $prefix. '.'. $at;

		if( $test( $node ) === true )
			return $here;

		if( is_array( $node['c'] ?? null ) === true && ( $found = sourceFind( $node['c'], $test, $here ) ) !== null )
			return $found;
	}

	return null;
}

/** A tree with the node at a position changed: $change gets the node and answers the one that takes its place */
function sourceEdit( array $nodes, string $position, callable $change ): array {

	$parts = explode( '.', $position );
	$index = (int) array_shift( $parts );

	if( $parts === [] )
		$nodes[$index] = $change( $nodes[$index] );
	else
		$nodes[$index]['c'] = sourceEdit( $nodes[$index]['c'], implode( '.', $parts ), $change );

	return $nodes;
}

/** A call of the tree by what it is: its name and, where given, what it reads */
function sourceAt( array $tree, string $name, ?string $read = null ): string {
	return sourceFind( $tree, static fn( array $node ): bool => ( $node['n'] ?? '' ) === $name && ( $read === null || ( $node['s'] ?? '' ) === $read ) ) ?? '';
}

/** A call with another first word and other attributes, its keys in the order a reading of a template has them */
function sourceWith( array $node, ?string $source = null, array $attributes = [] ): array {
	return array_replace( $node, $source === null ? [] : [ 's' => $source ], $attributes === [] ? [] : [ 'a' => array_replace( (array) $node['a'], $attributes ) ] );
}

/** The page as the same project draws it in another request: what a request builds, built again */
function sourceFresh( array $old ): array {

	$new = [ './nino/uid' => $old['./nino/uid'] ];
	\Nino\AppData::prepare( $new );

	foreach( [ './nino/filesystem/path', './nino/filesystem/configpath', './nino/filesystem/contentpath', './nino/filesystem/publicpath', './nino/locales/current', '/nino/locales/native', '/nino/locales/available', '/nino/dir', '/nino/install/texts', './nino/test/sandbox', '/nino/auth/user', './nino/auth/current' ] as $key )
		if( isset( $old[$key] ) === true )
			$new[$key] = $old[$key];

	$config = \Nino\Filesystem::getFileContent( $old, '/config.php', [] );
	$new['/nino/http/routes'] = $config['/nino/http/routes'] ?? [];
	$new['/nino/html/navs'] = $old['/nino/html/navs'] ?? [];
	$new['/nino/html/assets'] = [];
	$new['/nino/modules'] = $old['/nino/modules'];
	\Nino\Html::init( $new );
	\Nino\Features::registerComponents( $new );
	\Nino\Modules::callModules( $new, 'init' );

	return $new;
}

/** The bytes of a small picture, and the upload of it as php hands it to a request */
function sourceUpload( string $kind = 'png', int $width = 64, int $height = 48 ): void {

	$image = imagecreatetruecolor( $width, $height );
	imagefill( $image, 0, 0, (int) imagecolorallocate( $image, 200, 30, 30 ) );
	ob_start();
	$kind === 'png' ? imagepng( $image ) : imagejpeg( $image );
	$bytes = (string) ob_get_clean();
	$path = (string) tempnam( sys_get_temp_dir(), 'nino-up-' );
	file_put_contents( $path, $kind === 'bytes' ? 'no picture' : $bytes );
	$_FILES['file'] = [ 'name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
}

// ---------------------------------------------------------------------------------------------------------------------------------
$site = ninoSandbox( 'builder-source' );
$sandbox = ninoSandboxDir( $site );

$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

foreach( [ 'page-home', 'page-contact' ] as $name )
	\Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/'. $name. '.tpl' ) );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-loop.tpl', "<!-- nino:template-name Loop -->\n[template /templates/html-header]\n[section-wrap id=\"list\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[image ~picture alt=\"A map\"]\n\t\t\t[collection /_placeholder]\n\t\t\t\t[section-title .title]\n\t\t\t[/collection]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n[template /templates/html-footer]\n" );

$home = [ '/template/page-home/hero/title' => 'Willkommen', '/template/page-home/hero/subtitle' => 'Dein erster Eindruck', '/template/page-home/start/title' => 'Was Du findest', '/template/page-home/start/text' => 'Karten', '/template/page-home/start-1/title' => 'Eins', '/template/page-home/start-1/text' => 'Erstens', '/template/page-home/start-2/title' => 'Zwei', '/template/page-home/start-2/text' => 'Zweitens', '/template/page-home/start-3/title' => 'Drei', '/template/page-home/start-3/text' => 'Drittens', '/project/company/general/name' => 'Muster GmbH' ];

foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', array_combine( array_map( static fn( string $key ): string => '[['. $key. ']]', array_keys( $home ) ), array_map( static fn( string $text ): string => $locale === 'de_DE' ? $text : $text. ' (en)', $home ) ) );

$site['/nino/html/images']['/template/page-home/hero/background'] = [ 'label' => 'Hero', 'width' => 1920, 'height' => 1080, 'filename' => 'template/page-home/hero/background.svg' ];
$site['/nino/html/images']['/template/page-loop/list/picture'] = [ 'label' => 'Picture', 'width' => 640, 'height' => 480, 'filename' => null ];
\Nino\Images::restore( $site, 'template/page-home/hero/background.svg', '<svg xmlns="http://www.w3.org/2000/svg"><!-- the original --></svg>' );
// The pages the links of the blocks go to: a page of the site is one the site has
$site['/nino/http/routes'] = [ 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ], 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://loop' => [ 'uri' => '/loop', 'body' => '[template /templates/page-loop]' ] ];
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/images' => $site['/nino/html/images'], '/nino/html/navs' => [ 'main' ], '/nino/http/routes' => $site['/nino/http/routes'] ] );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

$registry = \Nino\Components::components( $site );
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];
$title = sourceAt( $tree, 'section-title', '~title' );
$button = sourceAt( $tree, 'button' );
$background = sourceAt( $tree, 'section-wrap', '' );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The source of a content\n";

$base = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
$changed = sourceEdit( sourceEdit( sourceEdit( $tree, $title, static fn( array $node ): array => sourceWith( $node, '~claim' ) ), $button, static fn( array $node ): array => sourceWith( $node, '/project/company/general/name', [ 'href' => 'page:/contact' ] ) ), $background, static fn( array $node ): array => $node );
[ $status, $saved ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $changed, 'texts' => [ '/template/page-home/hero/claim' => [ 'de_DE' => 'Brot, das Zeit bekommt' ] ], 'base' => $open['stamp'] ] );
$after = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
$words = \Nino\Text::values( $site, '/template/page-home' )['/template/page-home/hero/claim'] ?? [];

check( 'a new text is a source of the page, a key of the project is one that stays where it is: the template says ~claim and the key of the project, the words are in the language of the view and empty in the other, and nothing else changed', $status === 200 && substr_count( $after, "\n" ) === substr_count( $base, "\n" ) && str_contains( $after, '[section-title ~claim' ) && str_contains( $after, '[button /project/company/general/name' )
	&& ( $words['de_DE'] ?? null ) === 'Brot, das Zeit bekommt' && ( $words['en_US'] ?? '' ) === '' );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];
$title = sourceAt( $tree, 'section-title', '~claim' );
$snapshot = sourceSnapshot( $site );

$tries = [
	'a key of two words'			=> static fn( array $node ): array => sourceWith( $node, '/project/name' ),
	'a key of five words'			=> static fn( array $node ): array => sourceWith( $node, '/a/b/c/d/e' ),
	'a name with capitals'		=> static fn( array $node ): array => sourceWith( $node, '~Claim' ),
	'a field outside a loop'	=> static fn( array $node ): array => sourceWith( $node, '.title' ),
	'a page that is none'			=> static fn( array $node ): array => sourceWith( $node, null, [ 'href' => 'page:/a b' ] ),
	'a script as an address'	=> static fn( array $node ): array => sourceWith( $node, null, [ 'href' => 'javascript:alert(1)' ] ),
	'a script in entities'		=> static fn( array $node ): array => sourceWith( $node, null, [ 'href' => 'java&#115;cript:alert(1)' ] ),
	'a page the site has not'	=> static fn( array $node ): array => sourceWith( $node, null, [ 'href' => 'page:/nope' ] ),
	'a page that climbs'			=> static fn( array $node ): array => sourceWith( $node, null, [ 'href' => 'page:/contact/../home' ] ),
	'a text of the project that is none'	=> static fn( array $node ): array => sourceWith( $node, '/project/no/such/text' ),
	'a text of a page that is none'	=> static fn( array $node ): array => sourceWith( $node, '/template/page-zweite/a/x' ),
];
$refused = [];

foreach( $tries as $name => $change ) {

	$host = $name === 'a page that is none' || str_contains( $name, 'script' ) || str_contains( $name, 'page' ) ? $button : $title;
	$put = sourceEdit( $tree, $host, static fn( array $node ): array => $change( $node ) );

	foreach( [ 'builder/save', 'builder/preview' ] as $action )
		$refused[$name. ' '. $action] = sourceCall( $site, $action, [ 'template' => 'page-home', 'tree' => $put, 'texts' => [], 'base' => $open['stamp'] ] )[0];
}

check( 'a source that is none is refused as a save and as a draft, with 403, and nothing is written: a key of the wrong form, a name with capitals, a field outside a loop, a page of the site that is none, a script as an address, a text the site has not', array_values( array_unique( $refused ) ) === [ 403 ] && sourceSnapshot( $site ) === $snapshot );

[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );

// a picture of another place is a slot the site has: a slot that is none is refused like a text that is none, and one the site has is not
$pictures = [];

foreach( [ 'a slot of another page that is none' => '/template/page-home/hero/nothing', 'a slot of a page that is none' => '/template/page-zweite/a/picture' ] as $label => $slot )
	$pictures[$label] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => sourceEdit( $loop['tree'], sourceAt( $loop['tree'], 'image', '~picture' ), static fn( array $node ): array => sourceWith( $node, $slot ) ), 'texts' => [], 'base' => $loop['stamp'] ] );

$has = sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => sourceEdit( $loop['tree'], sourceAt( $loop['tree'], 'image', '~picture' ), static fn( array $node ): array => sourceWith( $node, '/template/page-home/hero/background' ) ), 'texts' => [], 'base' => $loop['stamp'] ] );

check( 'a picture of another place that the site has not is refused with the code of the source - 403 - and the slot of another page that it has is not refused for that', array_column( $pictures, 0 ) === [ 403, 403 ] && ( $pictures['a slot of another page that is none'][1]['code'] ?? '' ) === 'builder_refused_source' && ( $has[1]['code'] ?? '' ) !== 'builder_refused_source' );

$field = sourceAt( $loop['tree'], 'section-title', '.title' );
$put = static fn( string $written ): int => sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => sourceEdit( $loop['tree'], $field, static fn( array $node ): array => sourceWith( $node, $written ) ), 'texts' => [], 'base' => $loop['stamp'] ] )[0];

check( 'a field of the element is a source in a loop and only a field the type has - a field the type has not, and the field of a picture where a text is read, are refused - and a field outside a loop is refused', $field !== '' && $put( '.date' ) === 200 && $put( '.slug' ) === 200 && $put( '.nope' ) === 403 && $put( '.photo' ) === 403 );

$fills = [ sourceWith( [ 'n' => 'image', 's' => '~picture', 'a' => [ 'alt' => 'A map' ], 'c' => null ], null, [ 'alt' => '[[/project/company/general/name]]' ] ), sourceWith( [ 'n' => 'image', 's' => '~picture', 'a' => [ 'alt' => 'A map' ], 'c' => null ], '~picture', [ 'alt' => '[[x]]' ] ) ];
$image = sourceAt( $loop['tree'], 'image', '~picture' );
$put = static fn( array $with ): int => sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => sourceEdit( $loop['tree'], $image, static fn( array $node ): array => $with ), 'texts' => [], 'base' => $loop['stamp'] ] )[0];

check( 'the start of a fill is no value of a picture and no text of a call: a text that holds one is refused with 403', $put( $fills[0] ) === 403 && sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => sourceEdit( $loop['tree'], sourceAt( $loop['tree'], 'section-title', '.title' ), static fn( array $node ): array => sourceWith( $node, null, [ 'text' => '[[/project/company/general/name]]' ] ) ), 'texts' => [], 'base' => $loop['stamp'] ] )[0] === 403 );

// what a loop shows is a type of elements the project has: a type it has is taken, a type it has not is refused
\Nino\Elements::insertElementType( $site, '/services', [ 'title' => [ 'type' => 'string' ] ] );
$collection = sourceAt( $loop['tree'], 'collection', '/_placeholder' );
$shows = static fn( string $type ): int => sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => sourceEdit( $loop['tree'], $collection, static fn( array $node ): array => sourceWith( $node, $type ) ), 'texts' => [], 'base' => $loop['stamp'] ] )[0];
check( 'the search of a source in a language the request does not name, or the site does not have, is refused', sourceCall( $site, 'builder/keys', [ 'template' => 'page-home', 'kind' => 'text', 'locale' => null ] )[0] === 400 && sourceCall( $site, 'builder/keys', [ 'template' => 'page-home', 'kind' => 'text', 'locale' => 'xx_XX' ] )[0] === 400 );
[ , $types ] = sourceCall( $site, 'builder/keys', [ 'kind' => 'types' ] );

check( 'a loop shows a type of elements the project has: its types are listed with their titles - the example type of the kernel is none of them - a type it has is taken and a type it has not is refused', $shows( '/services' ) === 200 && $shows( '/nope' ) === 403 && $shows( '/../x' ) === 403 && array_column( $types['types'], 'type' ) === [ '/services' ] );

// the one who designs chooses every source without the development, and a source that is chosen gives no right to write markup in the same request; the one who may only write the
// content chooses none, whatever the source: the type of a loop, the field of an element and a text are refused for it - and nothing is written
$choose = static fn( array $tree, string $type, array $more = [] ): array => sourceEdit( sourceEdit( $tree, $collection, static fn( array $node ): array => sourceWith( $node, $type ) ), $field, static fn( array $node ): array => sourceWith( $node, '.title' ) ) + $more;
$ask = static function( array $tree ) use ( &$site, &$loop ): int {
	return sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => $tree, 'texts' => [], 'base' => $loop['stamp'] ] )[0];
};
$withMarkup = static function( array $tree ) use ( $collection ): array {
	$tree[(int) explode( '.', $collection )[0]]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<b>x</b>' ] ] ];
	return $tree;
};
$designs = [ $ask( $choose( $loop['tree'], '/services' ) ), $ask( $withMarkup( $choose( $loop['tree'], '/services' ) ) ) ];

ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$beforeContent = sourceSnapshot( $site );
$content = [ $ask( $choose( $loop['tree'], '/services' ) ), $ask( sourceEdit( $loop['tree'], $field, static fn( array $node ): array => sourceWith( $node, '.date' ) ) ), $ask( sourceEdit( $loop['tree'], $field, static fn( array $node ): array => sourceWith( $node, '~other' ) ) ) ];
$registryNow = \Nino\Components::components( $site );
$flags = array_map( static fn( array $schema ): bool => $schema['loop'], \Nino\Modules\Builder\Tree::schemas( $site, [], $registryNow, [ 'collection', 'collection-list', 'collection-table', 'collection-filter' ] ) );

check( 'the design chooses the type of a loop and a field of its elements without the development - and a request that chooses a source and writes markup is a 403 - while the content step is refused each source, and nothing is written for it',
	$designs === [ 200, 403 ] && $content === [ 403, 403, 403 ] && sourceSnapshot( $site ) === $beforeContent );
check( 'a loop is a component that holds nodes and says which elements by its sort, its window and its query: the collection, the list and the table are loops and the filter, which holds one, is none',
	$flags === [ 'collection' => true, 'collection-list' => true, 'collection-table' => true, 'collection-filter' => false ] );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
$snapshot = sourceSnapshot( $site );

$text = [ '/template/page-contact/hero/title' => [ 'de_DE' => 'Fremd' ], '/project/company/general/name' => [ 'de_DE' => 'Gekapert' ] ];
$status = [ sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => [ '/project/company/general/name' => [ 'de_DE' => 'Gekapert' ] ], 'base' => $open['stamp'] ] )[0], sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => [ '/template/page-contact/hero/title' => [ 'de_DE' => 'Fremd' ] ], 'base' => $open['stamp'] ] )[0] ];

check( 'the words of a key that is not the page\'s - a key of the project, a key of another page - are never written, whatever the request says: 403 and nothing is written', $status === [ 403, 403 ] && sourceSnapshot( $site ) === $snapshot && ( \Nino\Text::values( $site, '/project/company/general' )['/project/company/general/name']['de_DE'] ?? '' ) === 'Muster GmbH' );

[ $status, $found ] = sourceCall( $site, 'builder/keys', [ 'template' => 'page-home', 'kind' => 'text', 'query' => '' ] );
[ , $byText ] = sourceCall( $site, 'builder/keys', [ 'template' => 'page-home', 'kind' => 'text', 'query' => 'erstens' ] );
[ , $byName ] = sourceCall( $site, 'builder/keys', [ 'template' => 'page-home', 'kind' => 'text', 'query' => 'name' ] );
$keys = array_column( $found['entries'] ?? [], 'key' );

check( 'the texts are found by name or by text, with no search put in: the keys of the page stand first, the keys of other places are in the list, and what a search names is what it finds', $status === 200 && $keys !== [] && str_starts_with( $keys[0], '/template/page-home/' ) && in_array( '/project/company/general/name', $keys, true ) === true
	&& array_column( $byText['entries'], 'key' ) === [ '/template/page-home/start-1/text' ] && in_array( '/project/company/general/name', array_column( $byName['entries'], 'key' ), true ) === true );

ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );

check( 'the choice of a source is for the one who designs: the one who may change the content gets 403 from the search and from the fields of a type', sourceCall( $site, 'builder/keys', [ 'template' => 'page-home', 'kind' => 'text' ] )[0] === 403 && sourceCall( $site, 'builder/keys', [ 'kind' => 'fields', 'type' => '/_placeholder' ] )[0] === 403 );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $fields ] = sourceCall( $site, 'builder/keys', [ 'kind' => 'fields', 'type' => '/_placeholder' ] );

check( 'the fields of the elements of a type are the fields of its model and the three every element has', array_keys( $fields['fields'] ) === [ 'id', 'uri', 'slug', 'title', 'text', 'date', 'price', 'link' ] && sourceCall( $site, 'builder/keys', [ 'kind' => 'fields', 'type' => '/../x' ] )[1]['fields'] === [] );

// the alt of a picture is read as a text of its own - a designer writes it - and an address from a text that runs a script is not written
$image = sourceAt( $loop['tree'], 'image', '~picture' );
$alt = sourceEdit( $loop['tree'], $image, static fn( array $node ): array => sourceWith( $node, null, [ 'alt' => '~alt' ] ) );
[ $okStatus ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $alt, 'texts' => [ '/template/page-loop/list/alt' => [ 'de_DE' => 'Eine Karte', 'en_US' => 'A map' ] ], 'base' => $loop['stamp'] ] );
$altWords = \Nino\Text::values( $site, '/template/page-loop' )['/template/page-loop/list/alt'] ?? [];

check( 'the alt of a picture is a source like a text: a new key of the page, saved as plain text in each language', $okStatus === 200 && $altWords === [ 'de_DE' => 'Eine Karte', 'en_US' => 'A map' ] );

// F9: a template that is not read as it stands says where
\Nino\Filesystem::putFileContent( $site, '/templates/page-broken.tpl', "[template /templates/html-header]\n[section-wrap id=\"x\"]\n\t[section-row]\n[/section-wrap]\n" );
[ $status, $broken ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-broken' ] );

check( 'a template whose reading stops says the line where it does - the block that is not closed stands in line 3 - as a 409 with the name of the page, and nothing is changed', $status === 409 && ( $broken['code'] ?? '' ) === 'builder_unreadable' && ( $broken['params'][0] ?? '' ) === 'page-broken' && ( $broken['params'][1] ?? 0 ) === 3 );

// ...and the page it is said of is called by its name where it has one, and by its frame's name for a frame
$homeSource = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
$homeUri = (string) ( array_values( array_filter( \Nino\Pages::all( $site ), static fn( array $page ): bool => $page['template'] === 'page-home' ) )[0]['uri'] ?? '' );
foreach( [ 'de_DE' => 'Startseite', 'en_US' => 'Start page' ] as $language => $name ) {

	$texts = \Nino\Filesystem::getFileContent( $site, '/text/'. $language. '.php', [] );
	$texts['[[/_nino/webpage'. $homeUri. '/name]]'] = $name;
	\Nino\Filesystem::putFileContent( $site, '/text/'. $language. '.php', $texts );
}
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "[template /templates/html-header]\n[section-wrap id=\"x\"]\n\t[section-row]\n[/section-wrap]\n" );
[ , $named ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$homeNames = array_column( array_filter( \Nino\Pages::all( $site ), static fn( array $page ): bool => $page['template'] === 'page-home' ), 'names' );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', $homeSource );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', "[header-wrap id=\"header\"]\n\t[header-logo]\n" );
ninoUser( $site, 'frame-blocks@example.com', [ '/_admin/themelab/manage' ] );
[ , $frameBroken ] = sourceCall( $site, 'themelab/frame-open', [ 'template' => 'frame-header' ] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

check( 'the sentence of a template that cannot be read names the page by the name it has in the language of the view - and a frame, opened in the editor of the blocks, by its own word - and never by the file',
	$homeNames !== [] && ( $named['params'][0] ?? '' ) === ( $homeNames[0][\Nino\Admin\Admin::contentLocale( $site )] ?? '' ) && ( $named['params'][0] ?? '' ) !== 'page-home' && in_array( $frameBroken['params'][0] ?? '', array_map( static fn( string $locale ): string => (string) ( ( include NINO_FEATURES_DIR. '/Builder/text/'. $locale. '.php' )['[[/_admin/builder/kind/header-wrap]]'] ?? '' ), [ 'de_DE', 'en_US' ] ), true ) === true );


// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The pictures\n";

// The pictures are made with gd, as the pictures of the kernel's own suites are: a php without it leaves this part out and says so
if( function_exists( 'imagecreatetruecolor' ) === false )
	echo "  note  - gd is not there: the part about the pictures is left out\n";
else {


	$uri = '/template/page-loop/list/picture';
	$slotOf = static function( string $at ) use ( &$site ): array {
		$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );
		return (array) ( $config['/nino/html/images'][$at] ?? [] );
	};
	$path = static fn( string $file ): string => \Nino\Filesystem::path( $site, '/images/'. $file );

	[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );
	$snapshot = sourceSnapshot( $site );
	sourceUpload();
	[ $status, $up ] = sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => $uri ] );
	$first = (string) ( $up['file'] ?? '' );
	$after = sourceSnapshot( $site );

	check( 'a picture is made at once under a name of its own - the slot, the mark of an upload, the size the slot has - and nothing else is written: not the slot, not the template, not a word', $status === 200 && \Nino\Modules\Builder\Pictures::isStaged( $uri, $first ) === true && is_file( $path( $first ) ) === true
		&& array_diff_assoc( $snapshot, $after ) === [] && count( array_diff_key( $after, $snapshot ) ) === 1 && $slotOf( $uri )['filename'] === null && str_contains( (string) ( $up['url'] ?? '' ), $first ) );

	[ $status, $draft ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => [], 'pictures' => [ $uri => [ 'file' => $first ] ] ] );
	$request = sourceFresh( $site );
	ninoVisit( $request, '/?'. ( $draft['query'] ?? 'none' ) );
	$drawn = \Nino\Html::renderHtml( $request, '[template /templates/page-loop]' );

	check( 'the draft draws the picture that is chosen and not saved, and the slot of the project has none', $status === 200 && str_contains( $drawn, $first ) === true && $slotOf( $uri )['filename'] === null );

	[ $status ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => [], 'pictures' => [ $uri => [ 'file' => $first ] ], 'base' => $loop['stamp'] ] );

	check( 'a save gives the file to the slot', $status === 200 && $slotOf( $uri )['filename'] === $first && is_file( $path( $first ) ) === true );

	// the next picture replaces it, and the one before stays one save - then it goes; the one that is the slot's is never taken away
	$files = [ $first ];

	foreach( [ 1, 2 ] as $round ) {

		[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );
		sourceUpload( 'jpeg' );
		$next = (string) sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => $uri ] )[1]['file'];
		$files[] = $next;
		sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => [], 'pictures' => [ $uri => [ 'file' => $next ] ], 'base' => $loop['stamp'] ] );

		if( $round === 1 )
			$middle = [ is_file( $path( $files[0] ) ), is_file( $path( $files[1] ) ) ];
	}

	check( 'a picture that is replaced stays one save and is taken away with the next, and the file of the slot is there', $middle === [ true, true ] && is_file( $path( $files[0] ) ) === false && is_file( $path( $files[1] ) ) === true && $slotOf( $uri )['filename'] === $files[2] && is_file( $path( $files[2] ) ) === true );

	// what is not the page's: another page's slot, the file of another slot, a file that is no upload's, bytes that are no picture
	[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );
	$snapshot = sourceSnapshot( $site );
	sourceUpload();
	$strange = [ sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => '/template/page-home/hero/background', 'width' => 100, 'height' => 100 ] )[0], sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => '/project/logo', 'width' => 100, 'height' => 100 ] )[0] ];
	sourceUpload( 'bytes' );
	$strange[] = sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => $uri ] )[0];

	check( 'a picture is made for a slot of the page and for nothing else - a slot of another page and a slot outside the namespaces are 403, bytes that are no picture 400 - and nothing is made', $strange === [ 403, 403, 400 ] && sourceSnapshot( $site ) === $snapshot );

	[ , $home ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
	sourceUpload();
	$other = (string) sourceCall( $site, 'builder/upload', [ 'template' => 'page-home', 'uri' => '/template/page-home/hero/background' ] )[1]['file'];
	$snapshot = sourceSnapshot( $site );
	$give = static fn( string $file ): int => sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => [], 'pictures' => [ $uri => [ 'file' => $file ] ], 'base' => $loop['stamp'] ] )[0];

	check( 'a slot is given the file that was made for it and no other: the file of another slot, a file of the project that no upload made and a name that is none are 403, and nothing is written', [ $give( $other ), $give( 'template/page-home/hero/background.svg' ), $give( '../config.php' ) ] === [ 403, 403, 403 ] && sourceSnapshot( $site ) === $snapshot );

	// what nobody is given is taken away: by the one who gives it up, and after a day - and what a slot reads is never
	$unstaged = sourceCall( $site, 'builder/unstage', [ 'template' => 'page-loop', 'files' => [ $other, $files[2], 'template/page-loop/list/x.s123456789abc.1x1.png' ] ] )[1]['removed'] ?? -1;
	$mine = $path( $other ) !== '' && is_file( $path( $other ) );
	$removed = sourceCall( $site, 'builder/unstage', [ 'template' => 'page-home', 'files' => [ $other ] ] )[1]['removed'] ?? -1;
	sourceUpload();
	$old = (string) sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => $uri ] )[1]['file'];
	sourceUpload();
	$young = (string) sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => $uri ] )[1]['file'];
	touch( $path( $old ), time() - 2 * \Nino\Modules\Builder\Pictures::TTL );
	sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );

	check( 'what nobody is given is taken away - by the one who gives it up, never for another page, never what a slot reads - and when a page is opened after a day', $unstaged === 0 && $mine === true && $removed === 1 && is_file( $path( $other ) ) === false && is_file( $path( $old ) ) === false && is_file( $path( $young ) ) === true && is_file( $path( $files[2] ) ) === true );

	// a slot of another page that the page reads is read and no more: a request that names it with a size and a file would make it the page's - it is 403, and the slot stays as it is
	$strange = '/template/page-home/hero/background';
	$kept = $slotOf( $strange );
	$foreign = sourceEdit( $loop['tree'], sourceAt( $loop['tree'], 'image', '~picture' ), static fn( array $node ): array => sourceWith( $node, $strange ) );
	$snapshot = sourceSnapshot( $site );

	check( 'a slot of another page is read by the page and never written by it: naming it among the pictures of the request is 403, nothing is written, and the slot has its file',
		sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $foreign, 'texts' => [], 'pictures' => [ $strange => [ 'label' => 'Mine', 'width' => 10, 'height' => 10 ] ], 'base' => $loop['stamp'] ] )[0] === 403 && sourceSnapshot( $site ) === $snapshot && $slotOf( $strange ) === $kept && $kept['filename'] !== null );

	// a picture that was made and is gone - taken away after a day, or by hand - is told as it is, and the page is not saved with a slot that has no file
	sourceUpload();
	$lost = (string) sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => $uri ] )[1]['file'];
	unlink( $path( $lost ) );
	$snapshot = sourceSnapshot( $site );
	[ $status, $answer ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => [], 'pictures' => [ $uri => [ 'file' => $lost ] ], 'base' => $loop['stamp'] ] );

	check( 'a file that was made for a slot and is gone is no file to give: the save is a 409 that says the picture is gone, and nothing is written', $status === 409 && ( $answer['code'] ?? '' ) === 'builder_picture_gone' && sourceSnapshot( $site ) === $snapshot );

	// a slot that is new is made by the save, with its label and its size, where a source reads it - and none for what nothing reads
	sourceUpload();
	$photoFile = (string) ( sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => '/template/page-loop/list/photo', 'width' => 800, 'height' => 600 ] )[1]['file'] ?? '' );
	$image = sourceAt( $loop['tree'], 'image', '~picture' );
	$photo = sourceEdit( $loop['tree'], $image, static fn( array $node ): array => sourceWith( $node, '~photo' ) );
	[ $status ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $photo, 'texts' => [], 'pictures' => [ '/template/page-loop/list/photo' => [ 'label' => 'Photo', 'width' => 800, 'height' => 600, 'file' => $photoFile ], '/template/page-loop/list/ghost' => [ 'label' => 'Ghost', 'width' => 10, 'height' => 10 ] ], 'base' => $loop['stamp'] ] );
	$new = $slotOf( '/template/page-loop/list/photo' );

	check( 'a new slot is made by the save with the label, the size and the file of the request where a source of the page reads it - a slot nothing reads is not made - and the slot that is no longer read goes', $status === 200 && ( $new['label'] ?? '' ) === 'Photo' && ( $new['width'] ?? 0 ) === 800 && ( $new['filename'] ?? '' ) === $photoFile
		&& $slotOf( '/template/page-loop/list/ghost' ) === [] && $slotOf( $uri ) === [] );

	// the one who changes the content exchanges a picture and its alt: the way is the same - made at once, given by the save - and needs no step of the design
	ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
	[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );
	sourceUpload();
	[ $made, $up ] = sourceCall( $site, 'builder/upload', [ 'template' => 'page-loop', 'uri' => '/template/page-loop/list/photo' ] );
	[ $saved ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => [ '/template/page-loop/list/alt' => [ 'de_DE' => 'Eine neue Karte' ] ], 'pictures' => [ '/template/page-loop/list/photo' => [ 'file' => (string) ( $up['file'] ?? '' ) ] ], 'base' => $loop['stamp'] ] );

	check( 'the one who changes the content exchanges the picture of a slot of the page and its alt text: the picture is made, given by the save and read back, the alt text is the new one - and the way needs no right of the design', $made === 200 && $saved === 200 && $slotOf( '/template/page-loop/list/photo' )['filename'] === ( $up['file'] ?? '' ) && ( \Nino\Text::values( $site, '/template/page-loop' )['/template/page-loop/list/alt']['de_DE'] ?? '' ) === 'Eine neue Karte' );
	ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
}

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The blocks of the project\n";

ninoUser( $site, 'designer-blocks@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage', '/_admin/themelab/manage' ] );
[ , $home ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$cards = (string) array_search( 'start', array_map( static fn( array $node ): string => (string) ( $node['a']['id'] ?? '' ), $home['tree'] ), true );
$hero = (string) array_search( 'hero', array_map( static fn( array $node ): string => (string) ( $node['a']['id'] ?? '' ), $home['tree'] ), true );
$mine = static fn( array $with = [] ): array => $with + [ 'template' => 'page-home', 'tree' => $home['tree'], 'at' => $cards, 'key' => 'karten', 'name' => 'Vier Karten', 'description' => 'Eine Zeile über vier Karten', 'category' => 'content', 'texts' => $home['texts'], 'pictures' => [] ];
$before = sourceSnapshot( $site );
[ $status, $made ] = sourceCall( $site, 'themelab/adopt', $mine() );
$after = sourceSnapshot( $site );
unset( $site['./nino/blocks/state'] );
$folder = $sandbox. '/private/blocks/karten';
$meta = json_decode( (string) @file_get_contents( $folder. '/block.json' ), true );
$kernel = \Nino\Blocks::get( $site, 'karten' );
$added = array_keys( array_diff_key( $after, $before ) );
sort( $added );

check( 'a block of the page is a block of the project: its three kinds of files below private/blocks/ and nothing outside it, the kernel lists it as the project\'s own and reads its tree and the words of the page in each language back',
	$status === 200 && ( $made['key'] ?? '' ) === 'karten' && $added === array_map( static fn( string $file ): string => '/private/blocks/karten/'. $file, [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json' ] ) && array_diff_assoc( $before, $after ) === []
	&& ( $meta['name']['de_DE'] ?? '' ) === 'Vier Karten' && $meta['category'] === 'content' && ( $kernel['origin'] ?? '' ) === 'project' && $kernel['id'] === 'start' && $kernel['texts']['de_DE']['title'] === 'Was Du findest' && $kernel['texts']['en_US']['1-text'] === 'Erstens (en)'
	&& scandir( $sandbox. '/private/blocks' ) === [ '.', '..', 'karten' ] );

[ , $listed ] = sourceCall( $site, 'builder/blocks' );
$entries = array_column( $listed['blocks'], null, 'key' );
[ $status, $block ] = sourceCall( $site, 'builder/block', [ 'key' => 'karten' ] );

check( 'it is in the dialog with the origin of the project, may be taken away there and is put in like any block: its tree and its words; a block of Basis is not the project\'s',
	( $entries['karten']['own'] ?? false ) === true && $entries['karten']['origin']['kind'] === 'project' && ( $entries['hero-full']['own'] ?? true ) === false && $status === 200 && $block['id'] === 'start' && count( $block['slots'] ) === 8 );

// the way from a page to the editor of the blocks: the page says which of its blocks is built like a block of the project - to the account that holds the right of that panel and to no other
[ , $now ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
[ , $seen ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $now['tree'], 'texts' => [], 'look' => null, 'page' => '' ] );
ninoUser( $site, 'designer-plain@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $plain ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $now['tree'], 'texts' => [], 'look' => null, 'page' => '' ] );
[ , $list ] = sourceCall( $site, 'builder/list' );
ninoUser( $site, 'designer-blocks@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage', '/_admin/themelab/manage' ] );
[ , $listed ] = sourceCall( $site, 'builder/list' );

check( 'a block of the page that is built like a block of the project is told to the one who holds the right of the editor of the blocks - by its id, with the key of the block - and the list says the way is open to that account; for another account nothing of it is in any answer',
	( $seen['own']['start'] ?? '' ) === 'karten' && isset( $plain['own'] ) === false && ( $listed['bridge'] ?? false ) === true && ( $list['bridge'] ?? true ) === false );


// what is not made: free markup, what is not the block's own, a key that is taken or no key, a name, a category, a place that is no block, the account that may not design
[ , $contact ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-contact' ] );
$details = (string) array_search( 'details', array_map( static fn( array $node ): string => (string) ( $node['a']['id'] ?? '' ), $contact['tree'] ), true );
$before = sourceSnapshot( $site );
$tries = [
	'free markup'				=> [ 'tree' => sourceEdit( $home['tree'], $cards. '.'. sourceAt( $home['tree'][(int) $cards]['c'], 'section-col', '' ), static fn( array $node ): array => array_replace( $node, [ 'c' => array_merge( [ [ 't' => "\n\t\t\t<p>free</p>\n\t\t\t" ] ], array_slice( $node['c'], 1 ) ) ] ) ), 'key' => 'formular' ],
	'a script as the target of a link'	=> [ 'tree' => sourceEdit( $home['tree'], $cards. '.'. sourceFind( $home['tree'][(int) $cards]['c'], static fn( array $node ): bool => ( $node['a']['href'] ?? '' ) === 'page:/contact' ) , static fn( array $node ): array => sourceWith( $node, null, [ 'href' => 'javascript:alert(1)' ] ) ), 'key' => 'ziel' ],
	'a script in entities as a target'	=> [ 'tree' => sourceEdit( $home['tree'], $cards. '.'. sourceFind( $home['tree'][(int) $cards]['c'], static fn( array $node ): bool => ( $node['a']['href'] ?? '' ) === 'page:/contact' ) , static fn( array $node ): array => sourceWith( $node, null, [ 'href' => '&#106;avascript:alert(1)' ] ) ), 'key' => 'ziel' ],
	'a link to a host without a scheme'	=> [ 'tree' => sourceEdit( $home['tree'], $cards. '.'. sourceFind( $home['tree'][(int) $cards]['c'], static fn( array $node ): bool => ( $node['a']['href'] ?? '' ) === 'page:/contact' ) , static fn( array $node ): array => sourceWith( $node, null, [ 'href' => '//evil.example' ] ) ), 'key' => 'ziel' ],
	'a script as the text of a link'	=> [ 'tree' => sourceEdit( $home['tree'], $cards. '.'. sourceFind( $home['tree'][(int) $cards]['c'], static fn( array $node ): bool => ( $node['a']['href'] ?? '' ) === 'page:/contact' ) , static fn( array $node ): array => sourceWith( $node, null, [ 'href' => '~title' ] ) ), 'texts' => [ '/template/page-home/start-3/title' => [ 'de_DE' => 'java&#115;cript:alert(1)', 'en_US' => 'Three' ] ] + $home['texts'], 'key' => 'ziel' ],
	'a value the register refuses'	=> [ 'tree' => sourceEdit( $home['tree'], $cards. '.'. sourceAt( $home['tree'][(int) $cards]['c'], 'section-title', '~title' ), static fn( array $node ): array => sourceWith( $node, null, [ 'tag' => 'h9' ] ) ), 'key' => 'kaputt-neu' ],
	'free markup of a form'		=> [ 'template' => 'page-contact', 'tree' => $contact['tree'], 'at' => $details, 'texts' => $contact['texts'], 'key' => 'formular' ],
	'a foreign source'	=> [ 'at' => $hero, 'key' => 'kopf' ],
	'a key that is taken'	=> [ 'key' => 'karten' ],
	'a key of Basis'		=> [ 'key' => 'hero-full' ],
	'a path'						=> [ 'key' => '../karten' ],
	'a folder'					=> [ 'key' => 'a/b' ],
	'capitals'					=> [ 'key' => 'Karten2' ],
	'no key'						=> [ 'key' => '' ],
	'a name with markup'	=> [ 'key' => 'neu', 'name' => 'Eine <b>Zeile</b>' ],
	'the category of a frame'	=> [ 'key' => 'neu', 'category' => 'frame' ],
	'no block at the place'		=> [ 'key' => 'neu', 'at' => '0' ],
	'a place that is none'		=> [ 'key' => 'neu', 'at' => '99' ],
];
$answers = [];
$private = scandir( $sandbox. '/private' );

foreach( $tries as $name => $with )
	$answers[$name] = sourceCall( $site, 'themelab/adopt', $mine( $with ) );

$statuses = array_map( static fn( array $answer ): int => $answer[0], $answers );

check( 'what is not a block is not made, and nothing is written for it: free markup, a text or a picture of another place, a key that is taken - the project\'s and the kernel\'s - or is no slug, a name with markup, a category that is no one, a place that is no block',
	array_values( array_unique( $statuses ) ) !== [] && array_diff( $statuses, [ 400, 404, 409 ] ) === [] && sourceSnapshot( $site ) === $before && scandir( $sandbox. '/private/blocks' ) === [ '.', '..', 'karten' ] && scandir( $sandbox. '/private' ) === $private && ( $answers['free markup'][1]['code'] ?? '' ) === 'builder_own_locked' && ( $answers['a foreign source'][1]['code'] ?? '' ) === 'builder_own_foreign' && ( $answers['a value the register refuses'][1]['code'] ?? '' ) === 'builder_own_invalid' && array_unique( [ $answers['a script as the target of a link'][1]['code'] ?? '', $answers['a script in entities as a target'][1]['code'] ?? '', $answers['a link to a host without a scheme'][1]['code'] ?? '', $answers['a script as the text of a link'][1]['code'] ?? '' ] ) === [ 'builder_own_address' ] );

ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$before = sourceSnapshot( $site );

check( 'only the one who holds the right of the editor of the blocks makes a block of the project from a page or takes one away: the one who changes the content - and the one who designs without that right - gets 403 and nothing is written', sourceCall( $site, 'themelab/adopt', $mine( [ 'key' => 'neu' ] ) )[0] === 403 && sourceCall( $site, 'themelab/remove', [ 'key' => 'karten' ] )[0] === 403 && ( ninoUser( $site, 'designer0@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] ) || true ) && sourceCall( $site, 'themelab/adopt', $mine( [ 'key' => 'neu' ] ) )[0] === 403 && array_diff_key( sourceSnapshot( $site ), [ 'config' => 1 ] ) === array_diff_key( $before, [ 'config' => 1 ] ) );

// the right of the editor of the blocks alone reads no page: the page is opened by the door and the step of the Builder, and a hand-made tree names no page that the account may not open
ninoUser( $site, 'blocks-alone@example.com', [ '/_admin/themelab/manage' ] );
$before = sourceSnapshot( $site );
[ $alone, $leak ] = sourceCall( $site, 'themelab/adopt', $mine( [ 'key' => 'leck' ] ) );

check( 'a block of a page is not taken over by an account that holds the right of the editor of the blocks and no right of the Builder: 403, no block is written and no text of the page is read',
	$alone === 403 && is_dir( $sandbox. '/private/blocks/leck' ) === false && array_diff_key( sourceSnapshot( $site ), [ 'config' => 1 ] ) === array_diff_key( $before, [ 'config' => 1 ] ) && str_contains( json_encode( $leak ), 'Karten' ) === false );


ninoUser( $site, 'designer-blocks2@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage', '/_admin/themelab/manage' ] );

// a block is taken away only where it is the project's and listed: not one of Basis, not a path, not a folder that holds what is no block
mkdir( $sandbox. '/private/blocks/kaputt', 0755 );
file_put_contents( $sandbox. '/private/blocks/kaputt/block.json', '{' );
$kernelFile = $root. '/_nino/Nino/Modules/Basis/blocks/hero-full/block.json';
$before = sourceSnapshot( $site );
$homeBefore = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
$refused = array_map( static fn( string $key ): int => sourceCall( $site, 'themelab/remove', [ 'key' => $key ] )[0], [ 'hero-full', '../blocks/karten', 'kaputt', 'nothing', '' ] );
[ $status ] = sourceCall( $site, 'themelab/remove', [ 'key' => 'karten' ] );
unset( $site['./nino/blocks/state'] );

check( 'a block of the project is taken away and no other: not one of Basis, not a path, not a folder the kernel does not list; the page that has the block keeps its copy',
	$refused === [ 404, 404, 404, 404, 404 ] && is_file( $kernelFile ) === true && is_file( $sandbox. '/private/blocks/kaputt/block.json' ) === true && $status === 200 && file_exists( $sandbox. '/private/blocks/karten' ) === false
	&& \Nino\Blocks::get( $site, 'karten' ) === null && $homeBefore === (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ) && array_keys( array_diff_key( $before, sourceSnapshot( $site ) ) ) !== [] );


// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. A new page\n";

$page = [ 'names' => [ 'de_DE' => 'Über uns', 'en_US' => 'About us' ], 'addresses' => [ 'de_DE' => '/ueber-uns', 'en_US' => '/about-us' ], 'navs' => [ 'main' ], 'block' => 'hero-full' ];

ninoUser( $site, 'only-design@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$before = sourceSnapshot( $site );
$design = sourceCall( $site, 'builder/create', $page )[0];
ninoUser( $site, 'only-routes@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*', '/_admin/routes/manage' ] );
$before = sourceSnapshot( $site );
$routes = sourceCall( $site, 'builder/create', $page )[0];
[ , $list ] = sourceCall( $site, 'builder/list' );

check( 'a page is made by the one who may design and may write routes, both: the step of the design alone and the right of the routes with the content alone are 403, nothing is written, and the list does not offer it to them', $design === 403 && $routes === 403 && sourceSnapshot( $site ) === $before && $list['create'] === false );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $list ] = sourceCall( $site, 'builder/list' );
$before = sourceSnapshot( $site );
[ $status, $made ] = sourceCall( $site, 'builder/create', $page );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );
$routes = $config['/nino/http/routes'] ?? [];
$words = \Nino\Text::values( $site, '/template/page-ueber-uns' );
$source = (string) @file_get_contents( $sandbox. '/private/templates/page-ueber-uns.tpl' );
$site['/nino/http/routes'] = $routes;
unset( $site['./nino/blocks/state'] );
[ $opened, $open ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-ueber-uns' ] );
[ , $pages ] = sourceCall( $site, 'builder/list' );

check( 'the template, the route of each language with its menu entry, the name in each language and the words and the picture of the first block are made in one call - the page is in the list, opens as a block between the calls of the frame, and is read back by the kernel as the page it is',
	$list['create'] === true && $list['navs'] === [ 'main' ] && $status === 200 && $made === [ 'template' => 'page-ueber-uns', 'slug' => 'ueber-uns' ] && ( $routes['GET://ueber-uns']['locale'] ?? '' ) === 'de_DE' && ( $routes['GET://about-us']['locale'] ?? '' ) === 'en_US' && isset( $routes['GET://about-us']['navs']['main'] )
	&& str_starts_with( $source, "<!-- nino:template-name Über uns -->\n[template /templates/html-header]\n[section-wrap id=\"hero\"" ) && str_ends_with( $source, "[template /templates/html-footer]\n" )
	&& ( $words['/template/page-ueber-uns/hero/title']['de_DE'] ?? '' ) !== '' && ( $words['/template/page-ueber-uns/hero/title']['en_US'] ?? '' ) !== '' && is_file( $sandbox. '/public/images/template/page-ueber-uns/hero/background.svg' ) && ( $config['/nino/html/images']['/template/page-ueber-uns/hero/background']['filename'] ?? '' ) !== ''
	&& $opened === 200 && array_column( $pages['pages'], 'name', 'template' )['page-ueber-uns'] === 'Über uns' && sourceFind( $open['tree'], static fn( array $node ): bool => ( $node['a']['id'] ?? '' ) === 'hero' ) !== null );

check( 'the list says of the page that was made which menus it is in, how many blocks it has, that it has its name and address in every language and when it was written - and offers the menus to an account that may not make a page', ( static function() use ( $pages, &$site ): bool {

	$row = array_column( $pages['pages'], null, 'template' )['page-ueber-uns'] ?? [];
	ninoUser( $site, 'reader@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
	$reader = sourceCall( $site, 'builder/list' )[1];
	ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

	// A page that a module puts in a menu at run time - no route of its own names the menu - is in it as well
	$was = $site['/nino/html/navroutes'] ?? null;
	$site['/nino/html/navroutes'] = [ '/home' => [ 'main' => 1 ] ];
	$home = array_column( sourceCall( $site, 'builder/list' )[1]['pages'], null, 'template' )['page-home'] ?? [];
	$site['/nino/html/navroutes'] = $was ?? [];

	return ( $home['navs'] ?? null ) === [ 'main' ] && ( $row['navs'] ?? null ) === [ 'main' ] && ( $row['blocks'] ?? null ) === 1 && ( $row['complete'] ?? null ) === true && ( $row['saved'] ?? 0 ) > 0 && $reader['create'] === false && $reader['navs'] === [ 'main' ];
} )() );

// what the request cannot say: the source of the template, a name of a template, a block that is not offered
$before = sourceSnapshot( $site );
$second = [ 'names' => [ 'de_DE' => 'Kontakt', 'en_US' => 'Contact' ], 'addresses' => [ 'de_DE' => '/kontakt-neu', 'en_US' => '/contact-new' ], 'navs' => [], 'block' => '' ];
$hostile = [ sourceCall( $site, 'builder/create', [ 'block' => '../hero-full' ] + $second )[0], sourceCall( $site, 'builder/create', [ 'block' => 'nothing' ] + $second )[0] ];
$guarded = sourceSnapshot( $site ) === $before;
[ $status, $made ] = sourceCall( $site, 'builder/create', [ 'source' => '<script>alert(1)</script>', 'template' => 'page-evil', 'tree' => [ [ 't' => '<script>' ] ] ] + [ 'names' => [ 'de_DE' => 'Kon[template /templates/x] takt --> <b>', 'en_US' => 'Contact' ] ] + $second );
$lines = explode( "\n", (string) @file_get_contents( $sandbox. '/private/templates/page-kontakt-neu.tpl' ) );

check( 'the template of a page is made by the server from the calls the other pages have and a block that is offered: a block that is none is 403 and nothing is written, and a source, a name of a template or a tree in the request are not read - a page without a block is the two calls of the frame',
	$hostile === [ 403, 403 ] && $guarded === true && $status === 200 && $made['template'] === 'page-kontakt-neu' && count( $lines ) === 4 && str_starts_with( $lines[0], '<!-- nino:template-name ' ) && str_ends_with( $lines[0], ' -->' ) && str_contains( $lines[0], '[' ) === false
	&& array_slice( $lines, 1 ) === [ '[template /templates/html-header]', '[template /templates/html-footer]', '' ] && file_exists( $sandbox. '/private/templates/page-evil.tpl' ) === false );

// what the kernel refuses is said at the field, and nothing is left: the words and the picture of the block were made first and are taken back
$before = sourceSnapshot( $site );
$wordsBefore = \Nino\Text::values( $site, '/template/page-team' );
$site['/nino/http/routes']['GET://impressum'] = [ 'uri' => '/legal-imprint', 'body' => '[template /templates/page-legal-imprint]' ];
// a block of the project that carries a script as the target of a link is no start of a page - whether the kernel takes it as a block or not
mkdir( $sandbox. '/private/blocks/boese/text', 0755, true );
file_put_contents( $sandbox. '/private/blocks/boese/block.json', json_encode( [ 'name' => [ 'en_US' => 'Evil' ], 'description' => [ 'en_US' => 'Evil' ], 'category' => 'content', 'rank' => 900 ] ) );
file_put_contents( $sandbox. '/private/blocks/boese/block.tpl', "[section-wrap id=\"boese\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[button ~go href=\"~link\"]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n" );
file_put_contents( $sandbox. '/private/blocks/boese/text/en_US.json', json_encode( [ 'go' => 'Go', 'link' => 'java&#115;cript:alert(1)' ] ) );
unset( $site['./nino/blocks/state'] );
$evil = \Nino\Blocks::get( $site, 'boese' ) !== null;
$before = sourceSnapshot( $site );
$start = sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Böse', 'en_US' => 'Evil' ], 'addresses' => [ 'de_DE' => '/boese', 'en_US' => '/evil' ], 'navs' => [], 'block' => 'boese' ] )[0];
$stays = sourceSnapshot( $site ) === $before;
$answers = [
	'an address of a page the module routes' => sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Team', 'en_US' => 'Team' ], 'addresses' => [ 'de_DE' => '/Impressum', 'en_US' => '/team-en' ], 'navs' => [], 'block' => 'hero-full' ] ),
	'an address that is taken'		=> sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Team', 'en_US' => 'Team' ], 'addresses' => [ 'de_DE' => '/team', 'en_US' => '/about-us' ], 'navs' => [], 'block' => 'hero-full' ] ),
	'an address of the system'		=> sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Team', 'en_US' => 'Team' ], 'addresses' => [ 'de_DE' => '/team', 'en_US' => '/_admin/team' ], 'navs' => [], 'block' => 'hero-full' ] ),
	'a name that is missing'			=> sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Team', 'en_US' => ' ' ], 'addresses' => [ 'de_DE' => '/team', 'en_US' => '/team-en' ], 'navs' => [], 'block' => 'hero-full' ] ),
	'a page that is there'				=> sourceCall( $site, 'builder/create', $page ),
];
$told = array_map( static fn( array $answer ): array => [ $answer[0], $answer[1]['code'] ?? '', $answer[1]['field'] ?? '' ], $answers );
$folders = glob( $sandbox. '/public/images/template/page-team*' ) ?: [];

check( 'what the kernel refuses is said at the field it is about - an address that is taken is a 400 at the address of the language, an address of the system a 409, a name that is missing a 400 at the name, a page that is there a 400 - and nothing is left of the page: no word, no picture, no file, no folder of the pictures, no route',
	$told['an address of a page the module routes'] === [ 409, 'builder_page_http_uri_taken', 'httpUri-de_DE' ] && $told['an address that is taken'] === [ 400, 'builder_page_duplicate_http_uri', 'httpUri-en_US' ] && $told['an address of the system'][0] === 409 && $told['an address of the system'][2] === 'httpUri-en_US' && $told['a name that is missing'] === [ 400, 'builder_page_missing_name', 'name-en_US' ] && $told['a page that is there'][0] === 400
	&& sourceSnapshot( $site ) === $before && $folders === [] && $start === 403 && $stays === true && \Nino\Text::values( $site, '/template/page-team' ) === $wordsBefore && ( \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images']['/template/page-team/hero/background'] ?? null ) === null );


// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. A link that reads a text\n";

// the type of example elements of the kernel is a type a loop may name - the blocks of Collections loop it - and a name like it that is none is not
check( 'a loop may name the type of example elements the kernel brings, as a block that is put in does, and no other name that is no type of the project',
	\Nino\Modules\Builder\Tree::sourced( $site, (string) array_key_first( \Nino\Elements::PLACEHOLDERS ), 'type', true, '', null ) === true
	&& \Nino\Modules\Builder\Tree::sourced( $site, '/_nothing', 'type', true, '', null ) === false && \Nino\Modules\Builder\Tree::sourced( $site, '/no-such-type', 'type', true, '', null ) === false );

// what a block brings may name a page the site has not yet - the contact page of the blocks of Basis - and a target a person sets names one that is there
check( 'a page that is not there is a target a block may bring and none a person sets, and a script is neither', \Nino\Modules\Builder\Tree::linkTarget( $site, 'page:/no-such-page', false ) === true
	&& \Nino\Modules\Builder\Tree::linkTarget( $site, 'page:/no-such-page' ) === false && \Nino\Modules\Builder\Tree::linkTarget( $site, 'javascript:alert(1)', false ) === false );

[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$third = sourceFind( $open['tree'], static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'section-title' && ( $node['a']['href'] ?? '' ) === 'page:/contact' );
$linked = sourceEdit( $open['tree'], $third, static fn( array $node ): array => sourceWith( $node, null, [ 'href' => '~title' ] ) );
$key = '/template/page-home/start-3/title';
$before = sourceSnapshot( $site );
$one = [ sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $linked, 'texts' => [ $key => [ 'de_DE' => 'javascript:alert(1)' ] ], 'base' => $open['stamp'] ] )[0], sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $linked, 'texts' => [ $key => [ 'de_DE' => 'java&#115;cript:alert(1)' ] ], 'base' => $open['stamp'] ] )[0] ];

check( 'a link that reads the text of its own call takes no target that runs a script: in one step - the link and the text together, the text with entities - it is 403 and nothing is written', $one === [ 403, 403 ] && sourceSnapshot( $site ) === $before );

[ $status ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $linked, 'texts' => [], 'base' => $open['stamp'] ] );
[ , $linked ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$before = sourceSnapshot( $site );
$two = [ sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $linked['tree'], 'texts' => [ $key => [ 'de_DE' => 'javascript:alert(1)' ] ], 'base' => $linked['stamp'] ] )[0], sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $linked['tree'], 'texts' => [ $key => [ 'de_DE' => '&#x6A;avascript:alert(1)' ] ], 'base' => $linked['stamp'] ] )[0] ];

check( 'and in two steps: the one who designs sets the link on the title - which is taken, the words being no target that runs a script - and the one who changes the content writes the title afterwards: 403, and the title stays', $status === 200 && $two === [ 403, 403 ] && sourceSnapshot( $site ) === $before );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );


// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. What the panel offers, and what the content step writes\n";

// a block that carries a script as the target of a link, in an attribute or in a text, is not offered: not in the dialog, not as a block, not put in, not the start of a page
$bad = static function( string $name, string $tpl, array $words ) use ( $sandbox ): void {

	mkdir( $sandbox. '/private/blocks/'. $name. '/text', 0755, true );
	file_put_contents( $sandbox. '/private/blocks/'. $name. '/block.json', json_encode( [ 'name' => [ 'en_US' => $name ], 'description' => [ 'en_US' => $name ], 'category' => 'content', 'rank' => 901 ] ) );
	file_put_contents( $sandbox. '/private/blocks/'. $name. '/block.tpl', $tpl );
	file_put_contents( $sandbox. '/private/blocks/'. $name. '/text/en_US.json', json_encode( $words ) );
};
$bad( 'alt-evil', "[section-wrap id=\"alt-evil\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[button ~go href=\"javascript:alert(1)\"]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n", [ 'go' => 'Go' ] );
unset( $site['./nino/blocks/state'] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
$before = sourceSnapshot( $site );
[ , $offered ] = sourceCall( $site, 'builder/blocks' );
$asked = [ sourceCall( $site, 'builder/block', [ 'key' => 'alt-evil' ] )[0], sourceCall( $site, 'builder/block', [ 'key' => 'hero-full' ] )[0] ];
$made = sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Alt', 'en_US' => 'Alt' ], 'addresses' => [ 'de_DE' => '/alt-de', 'en_US' => '/alt-en' ], 'navs' => [], 'block' => 'alt-evil' ] )[0];
$kept = sourceSnapshot( $site ) === $before;
ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$before = sourceSnapshot( $site );
$put = sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $open['tree'], 'texts' => [], 'inserted' => [ 'alt-evil' => 'alt-evil' ], 'base' => $open['stamp'] ] )[0];

check( 'a block that carries a script as the target of a link is not offered: it is not in the dialog, it is not a block to put in, it is no start of a page, and naming it in a save is 403 - and nothing is written',
	in_array( 'alt-evil', array_column( (array) ( $offered['blocks'] ?? [] ), 'key' ), true ) === false && in_array( 'hero-full', array_column( (array) ( $offered['blocks'] ?? [] ), 'key' ), true ) === true
	&& $asked === [ 404, 200 ] && $made === 403 && $put === 403 && $kept === true && sourceSnapshot( $site ) === $before );

// the target of a link: with the anchor or the query of a page that is there, as the kernel draws the address, or none
$rule = [ 'page:/contact#anker', 'page:/contact?q=1', 'page:/contact', 'page:/Contact', 'page:/contact/', 'page:/nothing#a', 'page:/contact/../home' ];

check( 'the target of a page is the address as the kernel draws it - lower case, without a slash at the end - with an anchor or a query behind it, and a page that is there',
	array_map( static fn( string $target ): bool => Tree::linkTarget( $site, $target ), $rule ) === [ true, true, true, false, false, false, false ] );

// the last state is a save of its own step: where it brings back an address that runs a script, it is not put back
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$kept = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
\Nino\Modules\Builder\Store::keep( $site, 'page-home', [ 'source' => str_replace( 'page:/contact', 'javascript:alert(1)', $kept ), 'texts' => [], 'slots' => [] ] );
$before = sourceSnapshot( $site );
[ $status, $told ] = sourceCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $open['stamp'] ] );

check( 'the last state is held to the rule for a link like a save of its step: it brings back no address that runs a script - 403, and the page is as it was', $status === 403 && ( $told['code'] ?? '' ) === 'builder_refused_link' && sourceSnapshot( $site ) === $before );

// the content step writes the alt of a picture and no other word of an attribute
[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );
$image = sourceAt( $loop['tree'], 'image' );
$caption = sourceEdit( $loop['tree'], $image, static fn( array $node ): array => sourceWith( $node, null, [ 'alt' => '~alt', 'caption' => '~cap' ] ) );
$both = [ '/template/page-loop/list/alt' => [ 'de_DE' => 'Karte' ], '/template/page-loop/list/cap' => [ 'de_DE' => 'Unterschrift' ] ];
[ $designed ] = sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $caption, 'texts' => $both, 'base' => $loop['stamp'] ] );
ninoUser( $site, 'content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
[ , $loop ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-loop' ] );
$before = sourceSnapshot( $site );
$step = static fn( array $texts ): int => sourceCall( $site, 'builder/save', [ 'template' => 'page-loop', 'tree' => $loop['tree'], 'texts' => $texts, 'base' => $loop['stamp'] ] )[0];
$refused = [ $step( [ '/template/page-loop/list/cap' => [ 'de_DE' => 'Neu' ] ] ), $step( [ '/template/page-loop/list/cap' => [ 'de_DE' => 'Neu' ], '/template/page-loop/list/alt' => [ 'de_DE' => 'Neue Karte' ] ] ) ];
[ , $home ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$target = ( sourceCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $home['tree'], 'texts' => [ '/template/page-home/start-3/title' => [ 'de_DE' => 'page:/home' ] ], 'base' => $home['stamp'] ] )[0] );
$same = sourceSnapshot( $site ) === $before;
$allowed = $step( [ '/template/page-loop/list/alt' => [ 'de_DE' => 'Neue Karte' ] ] );
$words = \Nino\Text::values( $site, '/template/page-loop' );

check( 'the content step writes the alt of a picture of the page and no other attribute: the caption is 403 - alone or beside an alt - and so is the text a link reads, even where it is a good target - and nothing is written, the alt alone is 200 and is the one that changed',
	$designed === 200 && $refused === [ 403, 403 ] && $target === 403 && $same === true && $allowed === 200 && $words['/template/page-loop/list/alt']['de_DE'] === 'Neue Karte' && $words['/template/page-loop/list/cap']['de_DE'] === 'Unterschrift' );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );


// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nG. A page copied and taken away\n";

/** The drafts of one kind as the kernel keeps them: the token of each and the template it is a draft of */
$drafts = static function( string $kind ) use ( $sandbox ): array {

	$list = [];

	foreach( (array) glob( $sandbox. '/private/data/.preview/'. $kind. '/*', GLOB_ONLYDIR ) as $folder ) {

		$draft = json_decode( (string) @file_get_contents( $folder. '/draft.json' ), true );
		$list[basename( (string) $folder )] = is_array( $draft ) === true ? (string) ( $draft['template'] ?? '' ) : '';
	}

	return $list;
};

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

$kept = [ 'names' => [ 'de_DE' => 'Gehalten', 'en_US' => 'Kept' ], 'addresses' => [ 'de_DE' => '/gehalten', 'en_US' => '/kept' ], 'navs' => [ 'main' ], 'block' => 'hero-full' ];
[ $status, $made ] = sourceCall( $site, 'builder/create', $kept );
$template = (string) ( $made['template'] ?? '' );

// a page whose template reads a word of the first one, so that the word stays where the page is taken away
\Nino\Filesystem::putFileContent( $site, '/templates/page-reader.tpl', "<!-- nino:template-name Reader -->\n[section-title text=\"[[/template/". $template. "/hero/title]]\"]\n" );
$site['/nino/http/routes']['GET://reader'] = [ 'uri' => '/reader', 'body' => '[template /templates/page-reader]' ];
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );
$config['/nino/http/routes'] = $site['/nino/http/routes'] + (array) ( $config['/nino/http/routes'] ?? [] );
\Nino\Filesystem::putFileContent( $site, '/config.php', $config );
$site['/nino/http/routes'] = $config['/nino/http/routes'];
unset( $site['./nino/filesystem/cache'] );

// the Builder keeps a last state and the drafts of its previews for the page - and for another page, which stay
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => $template ] );
[ , $homeOpen ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
\Nino\Modules\Builder\Store::keep( $site, $template, \Nino\Modules\Builder\Store::state( $site, $template ) );
\Nino\Modules\Builder\Store::keep( $site, 'page-home', \Nino\Modules\Builder\Store::state( $site, 'page-home' ) );
sourceCall( $site, 'builder/preview', [ 'template' => $template, 'tree' => $open['tree'], 'texts' => [] ] );
sourceCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $homeOpen['tree'], 'texts' => [] ] );
sourceCall( $site, 'builder/tile', [ 'template' => $template, 'key' => 'hero-full' ] );
$previous = $sandbox. '/private/data/builder/previous';

// who may ask, and which names: nothing is read or written for an account that holds the step of the design alone, or the right of the routes with the content, or for a name that
// is no page - a frame, a path, a name with a capital, a byte that is none, a name too long
$every = [
	'builder/copyof'		=> [ 'template' => $template ],
	'builder/duplicate'	=> [ 'from' => $template, 'names' => [ 'de_DE' => 'X', 'en_US' => 'X' ], 'addresses' => [ 'de_DE' => '/x-de', 'en_US' => '/x-en' ], 'navs' => [] ],
	'builder/uses'			=> [ 'template' => $template ],
	'builder/remove'		=> [ 'template' => $template ],
];
$answers = [];
$same = true;

foreach( [ [ 'only-design@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] ], [ 'only-routes@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*', '/_admin/routes/manage' ] ] ] as [ $mail, $rights ] ) {

	ninoUser( $site, $mail, $rights );
	$before = sourceSnapshot( $site );

	foreach( $every as $action => $data )
		$answers[$mail. ' '. $action] = sourceCall( $site, $action, $data )[0];

	$same = $same && sourceSnapshot( $site ) === $before;
}

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

check( 'the four actions need the step of the design and the right of the routes, both: with the one alone each is 403 and nothing is written', array_unique( array_values( $answers ) ) === [ 403 ] && count( $answers ) === 8 && $same === true && is_file( $previous. '/'. $template. '.php' ) === true );

$hostile = [ 'frame-header', '../config', 'page-Kept', "page-kept\0", 'page-'. str_repeat( 'a', 120 ), 'html-header', '', 'page-', 'mail-owner' ];
$answers = [];
$before = sourceSnapshot( $site );

foreach( $hostile as $name )
	foreach( [ 'builder/copyof' => 'template', 'builder/uses' => 'template', 'builder/remove' => 'template', 'builder/duplicate' => 'from' ] as $action => $field )
		$answers[] = sourceCall( $site, $action, [ $field => $name ] + $every['builder/duplicate'] )[0];

check( 'a name that is no page - a frame, a path, a capital, a byte that is none, a name too long, a template of another kind - is refused by each of the four with 4xx, and nothing is written: the frame is there, the files are as they were', array_filter( $answers, static fn( int $status ): bool => $status < 400 || $status > 499 ) === [] && sourceSnapshot( $site ) === $before );

// what asking costs: nothing is written, and the answer names who reads what stays and who links
$before = sourceSnapshot( $site );
[ $status, $asked ] = sourceCall( $site, 'builder/uses', [ 'template' => $template ] );
$readers = array_merge( ...array_column( (array) ( $asked['kept'] ?? [] ), 'by' ) );

check( 'what taking a page away would do is asked without writing anything: the page by name and address, the words that stay with who reads them, who links to it - a list each, and the reader here is the template that was written to read it', $status === 200 && $asked['template'] === $template && $asked['name'] === 'Gehalten' && $asked['path'] === '/gehalten' && $asked['remnant'] === false
	&& $asked['linked'] === [] && $readers !== [] && array_filter( $readers, static fn( mixed $by ): bool => str_contains( (string) $by, 'page-reader' ) === true ) !== [] && sourceSnapshot( $site ) === $before );

// the two pages the kernel never takes away are refused with the kernel's own code, in the kernel's words, and are told apart in the list
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );
\Nino\Filesystem::putFileContent( $site, '/templates/page-start.tpl', "<!-- nino:template-name Start -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-missing.tpl', "<!-- nino:template-name Missing -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
$config['/nino/http/routes']['GET://'] = [ 'uri' => '/start', 'body' => '[template /templates/page-start]' ];
$config['/nino/http/routes']['GET://404'] = [ 'uri' => '/404', 'body' => '[template /templates/page-missing]', 'statusCode' => 404 ];
\Nino\Filesystem::putFileContent( $site, '/templates/page-maintenance.tpl', "<!-- nino:template-name Maintenance -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
$config['/nino/http/routes']['GET://maintenance'] = [ 'uri' => '/maintenance', 'body' => '[template /templates/page-maintenance]' ];
\Nino\Filesystem::putFileContent( $site, '/config.php', $config );
$site['/nino/http/routes'] = $config['/nino/http/routes'];
$before = sourceSnapshot( $site );
$refusals = [ 'page-start' => sourceCall( $site, 'builder/remove', [ 'template' => 'page-start' ] ), 'page-missing' => sourceCall( $site, 'builder/uses', [ 'template' => 'page-missing' ] ), 'page-maintenance' => sourceCall( $site, 'builder/remove', [ 'template' => 'page-maintenance' ] ) ];
$locked = array_column( sourceCall( $site, 'builder/list' )[1]['pages'], 'locked', 'template' );

check( 'the start page, the page of the status 404 and a page the kernel draws by itself are refused with 409 and the code of the kernel - for the deletion and for the question - and nothing is written; the list says which they are, and no other page',
	$refusals['page-start'][0] === 409 && $refusals['page-start'][1]['code'] === 'routes_remove_home' && $refusals['page-missing'][0] === 409 && $refusals['page-missing'][1]['code'] === 'routes_remove_not_found' && $refusals['page-maintenance'][0] === 409 && $refusals['page-maintenance'][1]['code'] === 'routes_remove_runtime' && sourceSnapshot( $site ) === $before
	&& $locked['page-start'] === 'home' && $locked['page-missing'] === 'not-found' && $locked['page-maintenance'] === 'runtime' && $locked['page-home'] === '' && $locked[$template] === '' );

// the page at the address /: what the form of its copy starts from is free - its Element-URI is a name that is taken too - and is sent as it stands
[ $status, $home ] = sourceCall( $site, 'builder/copyof', [ 'template' => 'page-start' ] );
[ $made ] = sourceCall( $site, 'builder/duplicate', [ 'from' => 'page-start', 'names' => [ 'de_DE' => 'Start Kopie', 'en_US' => 'Start copy' ], 'addresses' => $home['addresses'], 'navs' => $home['navs'] ] );
$uris = array_map( static fn( array $each ): string => $each['uri'], \Nino\Pages::all( $site ) );

check( 'the copy of the page at the address / is made from the form as the server proposed it: the proposal is no address and no Element-URI that a page has, and sent as it stands it is a page',
	$status === 200 && $made === 200 && in_array( '/start-2', $uris, true ) === true && array_intersect( array_map( 'strtolower', $home['addresses'] ), [ '/start', '/' ] ) === [] );

// what the form of a copy starts from: the names with the word of a copy behind them, an address for each language that no page has and no other language of the form has, the menus
sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Zwei', 'en_US' => 'Two' ], 'addresses' => [ 'de_DE' => '/zwei-de', 'en_US' => '/kept-2' ], 'navs' => [], 'block' => '' ] );
[ $status, $start ] = sourceCall( $site, 'builder/copyof', [ 'template' => $template ] );
$used = [];

foreach( \Nino\Pages::all( $site ) as $each )
	foreach( $each['addresses'] as $address )
		$used[strtolower( rtrim( (string) $address, '/' ) )] = true;

$proposed = array_map( static fn( string $address ): string => strtolower( rtrim( $address, '/' ) ), (array) ( $start['addresses'] ?? [] ) );

check( 'the form of a copy starts from the page: a name in each language that begins with the name of the page and says more, the next address for each language that no page has - the one after the one that is taken - and that is not the address of another language of the form, the menus of the page, and the page named for the view',
	$status === 200 && array_keys( (array) $start['names'] ) === [ 'de_DE', 'en_US' ] && str_starts_with( $start['names']['de_DE'], 'Gehalten' ) && $start['names']['de_DE'] !== 'Gehalten' && str_starts_with( $start['names']['en_US'], 'Kept' ) && $start['names']['en_US'] !== 'Kept'
	&& count( $proposed ) === 2 && count( array_unique( $proposed ) ) === 2 && array_intersect_key( array_flip( $proposed ), $used ) === [] && $start['addresses']['en_US'] === '/kept-3' && $start['navs'] === [ 'main' ] && $start['label'] === 'Gehalten' && $start['template'] === $template );

// the copy: the kernel makes it, the answer names the template of the new page, and the words and the picture of the first are the ones of the copy as well
$copy = [ 'from' => $template, 'names' => [ 'de_DE' => 'Kopie', 'en_US' => 'Copy' ], 'addresses' => [ 'de_DE' => '/kopie', 'en_US' => '/copy' ], 'navs' => [ 'main' ] ];
$words = \Nino\Text::values( $site, '/template/'. $template );
[ $status, $made ] = sourceCall( $site, 'builder/duplicate', $copy );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );
$site['/nino/http/routes'] = $config['/nino/http/routes'];
$copied = \Nino\Text::values( $site, '/template/page-kopie' );
[ , $listing ] = sourceCall( $site, 'builder/list' );

check( 'a page is copied by the kernel: the answer names the template and the word of the new page and counts what was copied, the copy has the words of the first - each key of the first once, in both languages - and a route for each language and the menu, the list has it, and the page it was made from is as it was',
	$status === 200 && $made['template'] === 'page-kopie' && $made['slug'] === 'kopie' && ( $made['copied']['texts'] ?? 0 ) === count( $words ) && count( $words ) > 0 && array_values( array_map( static fn( array $each ): array => array_keys( $each ), $copied ) ) === array_values( array_map( static fn( array $each ): array => array_keys( $each ), $words ) )
	&& isset( $config['/nino/http/routes']['GET://copy']['navs']['main'] ) === true && in_array( 'page-kopie', array_column( $listing['pages'], 'template' ), true ) === true && \Nino\Text::values( $site, '/template/'. $template ) === $words );

// the page that is taken away: the kernel takes its template, routes, words and pictures - what another page reads stays - and the Builder takes its last state and its drafts, and nobody else's
// a page whose name begins with the name of the page that is taken away - and a link that leads out of the folder of the drafts - is not the page: its last state and its
// drafts are not touched
$neighbour = $template. '-x';
\Nino\Modules\Builder\Store::keep( $site, $neighbour, [ 'source' => '', 'texts' => [], 'slots' => [] ] );
$token = str_repeat( 'a', 32 );
mkdir( $sandbox. '/private/data/.preview/builder/'. $token, 0755, true );
file_put_contents( $sandbox. '/private/data/.preview/builder/'. $token. '/draft.json', json_encode( [ 'template' => $neighbour ] ) );
$other = file_get_contents( $previous. '/page-home.php' );
$have = [ $drafts( 'builder' ), $drafts( 'builder-tile' ) ];
[ $status, $gone ] = sourceCall( $site, 'builder/remove', [ 'template' => $template ] );

check( 'a page is taken away whole with its last state and the drafts of its previews - of the page and of its tiles - and the last state and the drafts of the other page stay; the answer counts what the kernel took and what stays because another page reads it',
	$status === 200 && $gone['template'] === $template && $gone['kept'] > 0 && $gone['texts'] > 0 && is_file( $sandbox. '/private/templates/'. $template. '.tpl' ) === false && is_file( $previous. '/'. $template. '.php' ) === false && \Nino\Modules\Builder\Store::previous( $site, $template ) === null
	&& in_array( $template, $have[0], true ) === true && in_array( $template, $have[1], true ) === true && in_array( $template, $drafts( 'builder' ), true ) === false && in_array( $template, $drafts( 'builder-tile' ), true ) === false
	&& array_diff( $have[0], [ $template ] ) === $drafts( 'builder' ) && array_diff( $have[1], [ $template ] ) === $drafts( 'builder-tile' ) && file_get_contents( $previous. '/page-home.php' ) === $other && in_array( 'page-home', $drafts( 'builder' ), true ) === true
	&& is_file( $previous. '/'. $neighbour. '.php' ) === true && ( $drafts( 'builder' )[$token] ?? '' ) === $neighbour );

// the kind of a draft that is a link leads out of the folder of the drafts: the drafts there are not the Builder's to take away
mkdir( $sandbox. '/outside/'. $token, 0755, true );
file_put_contents( $sandbox. '/outside/'. $token. '/draft.json', json_encode( [ 'template' => 'page-linked' ] ) );
rename( $sandbox. '/private/data/.preview/builder-tile', $sandbox. '/private/data/.preview/builder-tile-moved' );
symlink( $sandbox. '/outside', $sandbox. '/private/data/.preview/builder-tile' );
\Nino\Modules\Builder\Store::forget( $site, 'page-linked' );

check( 'the drafts below a kind that is a link are left where they are', is_file( $sandbox. '/outside/'. $token. '/draft.json' ) === true );

// the listener: a name outside the rule for the name of a page touches nothing - not a frame, not a file below data/builder that a path would reach, not a draft of a frame
\Nino\Modules\Builder\Store::keep( $site, 'frame-header', \Nino\Modules\Builder\Store::state( $site, 'frame-header' ) ?? [ 'source' => '', 'texts' => [], 'slots' => [] ] );
file_put_contents( $sandbox. '/private/data/builder/outside.php', '<?php return [];' );
file_put_contents( $previous. '/page-Mixed.php', '<?php return [];' );
$before = [ glob( $sandbox. '/private/data/builder/*' ), glob( $previous. '/*' ), $drafts( 'builder' ), $drafts( 'builder-tile' ) ];

foreach( [ 'frame-header', '../outside', '../previous/page-home', 'page-Mixed', 'page-home/../page-home', "page-home\0", '', 'page-', str_repeat( 'page-a', 40 ) ] as $name ) {
	$event = [ 'template' => $name ];
	\Nino\Modules\Builder::removed( $site, $event );
}

foreach( [ [], [ 'template' => 7 ], [ 'template' => [ 'page-home' ] ] ] as $event )
	\Nino\Modules\Builder::removed( $site, $event );

check( 'the listener of the removal touches nothing for a name outside the rule for the name of a page - a frame, a path, a capital, a byte that is none, a name too long, no name at all: the files and the drafts are what they were',
	$before === [ glob( $sandbox. '/private/data/builder/*' ), glob( $previous. '/*' ), $drafts( 'builder' ), $drafts( 'builder-tile' ) ] && is_file( $sandbox. '/private/data/builder/outside.php' ) === true && is_file( $previous. '/frame-header.php' ) === true && is_file( $previous. '/page-home.php' ) === true );

// a new page of a name that something stands under - the words another page reads stayed - takes the next word, and the words that stayed are not written over; a copy takes the same way
$stayed = \Nino\Text::values( $site, '/template/'. $template );

/** What stays of the page under its name, as the files hold it: the raw words of each language, the formats, the slots and the bytes of the pictures */
$held = static function() use ( &$site, $sandbox, $template ): array {

	unset( $site['./nino/filesystem/cache'] );
	$own = static fn( array $list ): array => array_filter( $list, static fn( mixed $key ): bool => str_contains( (string) $key, '/template/'. $template. '/' ), ARRAY_FILTER_USE_KEY );
	$kept = [];

	foreach( [ 'de_DE', 'en_US' ] as $locale )
		$kept[$locale] = $own( (array) require $sandbox. '/private/text/'. $locale. '.php' );

	$meta = $sandbox. '/private/text/meta.php';
	$kept['meta'] = is_file( $meta ) === true ? $own( (array) require $meta ) : [];
	$kept['slots'] = $own( (array) \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] );
	$kept['files'] = array_map( 'hash_file', array_fill( 0, count( $files = glob( $sandbox. '/public/images/template/'. $template. '/*/*' ) ?: [] ), 'sha256' ), $files );

	return $kept;
};
$snapshot = $held();
[ $status, $again ] = sourceCall( $site, 'builder/create', $kept );
$after = \Nino\Text::values( $site, '/template/'. $template );
$bytes = $held();
$next = (string) ( $again['template'] ?? '' );
sourceCall( $site, 'builder/remove', [ 'template' => $next ] );
[ $copyStatus, $second ] = sourceCall( $site, 'builder/duplicate', [ 'from' => 'page-kopie', 'addresses' => [ 'de_DE' => '/gehalten', 'en_US' => '/kept' ] ] + $copy );

check( 'a page that is made under the name of words that stayed takes the next free word - the template, the word and the Element-URI with it - and the words that stayed are the same to the byte; the copy of a page onto the same name takes the one after that',
	$stayed !== [] && $status === 200 && $next === $template. '-2' && ( $again['slug'] ?? '' ) === substr( $next, 5 ) && $after === $stayed && $bytes === $snapshot && $bytes['de_DE'] !== [] && is_file( $sandbox. '/private/templates/'. $next. '.tpl' ) === true
	&& $copyStatus === 200 && ( $second['template'] ?? '' ) === $template. '-2' && $held() === $snapshot );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nH. Writers that run beside a save and beside a page that is made\n";

/** A second process on this sandbox, in the background: what it does is named by the first word (see fixtures/second.php) */
$beside = static function( string $what, string ...$arguments ) use ( $sandbox, $root ): void {
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( __DIR__. '/fixtures/second.php' ). ' '. escapeshellarg( $sandbox ). ' '. $what. ' '. implode( ' ', array_map( 'escapeshellarg', $arguments ) ). ' > /dev/null 2>&1 &' );
};

/** Wait for a file, a fixed number of rounds and no longer: whether it is there is what is asked, not how long it took */
$await = static function( callable $there, int $rounds ): bool {

	for( $round = 0; $round < $rounds && $there() === false; $round++ )
		usleep( 10000 );

	return $there();
};

/** Arm a file that a request reads: the first line of it runs the hook of that name, which is the test's and runs where the request is */
$plant = static function( string $file, string $name ) use ( &$site ): void {
	file_put_contents( $file, (string) preg_replace( '/^<\?php/', '<?php ( $GLOBALS[\'ninoHook\'][\''. $name. '\'] ?? static fn() => null )();', (string) file_get_contents( $file ), 1 ) );
	unset( $site['./nino/filesystem/cache'] );
};

/** Whether the request that runs the hook is inside a function of the Builder or the kernel */
$inside = static fn( string $function ): bool => in_array( $function, array_map( static fn( array $frame ): string => ( $frame['class'] ?? '' ). '::'. $frame['function'], debug_backtrace() ), true );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Wettlauf', 'en_US' => 'Race' ], 'addresses' => [ 'de_DE' => '/wettlauf', 'en_US' => '/race' ], 'navs' => [], 'block' => 'hero-full' ] );
$site['/nino/http/routes'] = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/http/routes'];
$t = 'page-wettlauf';
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => $t ] );
$title = '/template/'. $t. '/hero/title';
$edit = [ $title => [ 'de_DE' => 'Geändert' ] ];
$state = \Nino\Modules\Builder\Store::state( $site, $t );
\Nino\Modules\Builder\Store::keep( $site, $t, $state );

// the page is taken away by another request while a save reads its last state: the removal waits for the save - it is one of the writers of config.php - and is done
// when the save is, so nothing of the page is written back after it: no word, no last state, no slot, no picture
$fired = false;
$GLOBALS['ninoHook']['commit'] = static function() use ( &$fired, $inside, $beside, $await, $sandbox, $t ): void {

	if( $fired === true || $inside( 'Nino\\Modules\\Builder\\Store::commit' ) === false )
		return;

	$fired = true;
	$beside( 'remove', $t );
	$await( static fn(): bool => is_file( $sandbox. '/second.done' ), 60 );
};
$plant( $sandbox. '/private/data/builder/previous/'. $t. '.php', 'commit' );
[ $status ] = sourceCall( $site, 'builder/save', [ 'template' => $t, 'tree' => $open['tree'], 'texts' => $edit, 'base' => $open['stamp'] ] );
$done = $await( static fn(): bool => is_file( $sandbox. '/second.done' ), 600 );
unset( $site['./nino/filesystem/cache'] );
$routes = array_filter( (array) \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/http/routes'], static fn( mixed $route ): bool => str_contains( (string) ( $route['body'] ?? '' ), $t. ']' ) );

check( 'a page that another request takes away while it is saved stays away: the save is 200, the removal is done after it, and nothing of the page is written back - no template, no route, no word, no last state, no slot, no picture',
	$fired === true && $status === 200 && $done === true && is_file( $sandbox. '/private/templates/'. $t. '.tpl' ) === false && $routes === [] && \Nino\Text::values( $site, '/template/'. $t ) === []
	&& is_file( $sandbox. '/private/data/builder/previous/'. $t. '.php' ) === false && array_filter( array_keys( (array) \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] ), static fn( string $uri ): bool => str_contains( $uri, $t ) ) === []
	&& ( glob( $sandbox. '/public/images/template/'. $t. '/*/*' ) ?: [] ) === [] );

// a save of a page that was taken away before it began, from a state it read before, writes nothing and says why
$site['/nino/http/routes'] = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/http/routes'];
$before = sourceSnapshot( $site );
$target = $state;
$target['texts'][$title]['de_DE'] = 'Zu spät';
$answer = \Nino\Modules\Builder\Store::commit( $site, $t, $state, $target, null );

check( 'a save of a page that is gone writes nothing: the answer says the page is gone and the files are what they were - no word, no last state', $answer === 'gone' && sourceSnapshot( $site ) === $before && \Nino\Text::values( $site, '/template/'. $t ) === [] );

// other writers while a save is in the step of the words: a word of another page, the template from another tab - which is why the save fails - and a route. What they wrote
// is theirs: the save puts back what it wrote and nothing of theirs, and the route waits for the lock and is written when the save is done
sourceCall( $site, 'builder/create', [ 'names' => [ 'de_DE' => 'Fremdlauf', 'en_US' => 'Foreign' ], 'addresses' => [ 'de_DE' => '/fremdlauf', 'en_US' => '/foreign-page' ], 'navs' => [], 'block' => 'hero-full' ] );
$site['/nino/http/routes'] = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/http/routes'];
$t = 'page-fremdlauf';
[ , $open ] = sourceCall( $site, 'builder/open', [ 'template' => $t ] );
$title = '/template/'. $t. '/hero/title';
$was = \Nino\Text::values( $site, '/template/'. $t )[$title];
$marker = $sandbox. '/foreign.marker';
$fired = false;
$GLOBALS['ninoHook']['words'] = static function() use ( &$fired, $inside, $beside, $await, $marker, $t ): void {

	if( $fired === true || $inside( 'Nino\\Text::put' ) === false )
		return;

	$fired = true;
	$beside( 'foreign', $t, $marker );
	$await( static fn(): bool => is_file( $marker ), 300 );
};
$plant( $sandbox. '/private/text/de_DE.php', 'words' );
$state = \Nino\Modules\Builder\Store::state( $site, $t );
$target = $state;
$target['texts'][$title]['de_DE'] = 'Nur deutsch';
$target['source'] .= "<!-- ours -->\n";
$status = \Nino\Modules\Builder\Store::commit( $site, $t, $state, $target, null );
$await( static fn(): bool => str_contains( (string) file_get_contents( $sandbox. '/private/config.php' ), '/foreign\'' ), 600 );
unset( $site['./nino/filesystem/cache'] );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );

check( 'what another writer wrote while a save was under way stays theirs when the save fails: their word of another page, their line in the template and their route are there, and the word the save wrote is the one it was',
	$fired === true && $status === 'changed' && ( \Nino\Text::values( $site, '/template/page-home' )['/template/page-home/hero/title']['en_US'] ?? '' ) === 'Written by somebody else'
	&& str_contains( (string) file_get_contents( $sandbox. '/private/templates/'. $t. '.tpl' ), 'other tab' ) === true && isset( $config['/nino/http/routes']['GET://foreign'] ) === true && \Nino\Text::values( $site, '/template/'. $t )[$title] === $was );

// two pages made at the same moment under the same address, round after round: one of them is made - whole: template, words, slot, picture and route - and the other
// is refused at the address, whichever is first
$rounds = 6;
$start = microtime( true ) + 1.0;
$procs = [];

foreach( [ 'A', 'B' ] as $who )
	$procs[$who] = proc_open( [ PHP_BINARY, __DIR__. '/fixtures/second.php', $sandbox, 'racer', (string) $start, (string) $rounds, $who, 'hero-full' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes[$who], null, [ 'NINO_ROOT' => $root ] + $_ENV );

$told = [];

foreach( $procs as $who => $proc ) {
	$told[$who] = (array) json_decode( (string) stream_get_contents( $pipes[$who][1] ), true );
	proc_close( $proc );
}

unset( $site['./nino/filesystem/cache'] );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] );
$whole = [];

for( $round = 1; $round <= $rounds; $round++ ) {

	$made = array_values( array_filter( [ $told['A'][$round] ?? '', $told['B'][$round] ?? '' ], static fn( mixed $answer ): bool => str_starts_with( (string) $answer, 'page-' ) ) );
	$lost = array_values( array_filter( [ $told['A'][$round] ?? '', $told['B'][$round] ?? '' ], static fn( mixed $answer ): bool => str_starts_with( (string) $answer, 'page-' ) === false ) );
	$name = 'page-race-'. $round;
	$file = (string) ( $config['/nino/html/images']['/template/'. $name. '/hero/background']['filename'] ?? '' );
	$whole[] = $made === [ $name ] && count( $lost ) === 1 && is_file( $sandbox. '/private/templates/'. $name. '.tpl' ) === true && \Nino\Text::values( $site, '/template/'. $name ) !== [] && $file !== '' && is_file( $sandbox. '/public/images/'. $file ) === true
		&& count( array_filter( (array) $config['/nino/http/routes'], static fn( mixed $route ): bool => str_contains( (string) ( $route['body'] ?? '' ), $name. ']' ) ) ) === 2;
}

check( 'two pages made at the same moment under one address, '. $rounds. ' rounds with two processes: in each round one is made whole and the other is refused', array_filter( $whole, static fn( bool $ok ): bool => $ok === false ) === [] && count( $whole ) === $rounds );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nH. The variants of a block\n";

// A page with three blocks of the catalogue that have variants - as the dialog puts them in: the template of the block, whatever its first line is
$basis = $root. '/_nino/Nino/Modules/Basis/blocks';
$blockTpl = static function( string $path ) use ( &$site ): string {

	// The block as it is put into a page: its root laid out the way the kernel writes a template, each call on a line of its own
	foreach( Tree::read( $site, (string) file_get_contents( $path. '/block.tpl' ) ) as $node )
		if( isset( $node['n'] ) === true )
			return rtrim( \Nino\Markup::write( \Nino\Markup::layout( [ $node ], 0 ), \Nino\Components::containers( $site ) ) );

	return '';
};
\Nino\Filesystem::putFileContent( $site, '/templates/page-variants.tpl', "<!-- nino:template-name Variants -->\n[template /templates/html-header]\n". $blockTpl( $basis. '/split-image-text' ). "\n". $blockTpl( $basis. '/hero-full' ). "\n". $blockTpl( NINO_FEATURES_DIR. '/Collections/blocks/list-over-elements' ). "\n[template /templates/html-footer]\n" );
$site['/nino/html/images']['/template/page-variants/split/picture'] = [ 'label' => 'Picture', 'width' => 1200, 'height' => 900, 'filename' => null ];
$site['/nino/html/images']['/template/page-variants/hero/background'] = [ 'label' => 'Background', 'width' => 1920, 'height' => 1080, 'filename' => null ];

ninoUser( $site, 'variants-design@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ $status, $variantsOpen ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-variants' ] );
$vTree = $variantsOpen['tree'] ?? [];
$atOf = static function( array $tree, string $id ): string {
	foreach( $tree as $at => $node )
		if( ( $node['a']['id'] ?? '' ) === $id )
			return (string) $at;
	return '';
};
$splitAt = $atOf( $vTree, 'split' );
$heroAt = $atOf( $vTree, 'hero' );
$listAt = $atOf( $vTree, 'list' );
$told = [];

foreach( $variantsOpen['variants'] ?? [] as $entry )
	$told[$entry['key']] = [ (string) $entry['at'], $entry['current'], array_column( $entry['items'], 0 ) ];

check( 'the open answer of the one who designs tells which blocks of the page have variants - by their place, the block of the catalogue they are, the variant they have now and the names in the order of the block, one block once - and no other block',
	$status === 200 && $splitAt !== '' && $heroAt !== '' && $listAt !== '' && ( $told['split-image-text'] ?? null ) === [ $splitAt, 'image-left', [ 'image-left', 'image-right' ] ] && ( $told['hero-full'][1] ?? '' ) === 'image-full' && ( $told['hero-full'][2] ?? [] )[0] === 'image-full'
	&& ( $told['list-over-elements'] ?? null ) === [ $listAt, 'plain', [ 'plain', 'offset', 'tinted' ] ] && count( $variantsOpen['variants'] ) === 3 && isset( $variantsOpen['variants'][0]['items'][0][1]['de_DE'], $variantsOpen['variants'][0]['items'][0][1]['en_US'] ) === true );

$setVariant = static function( array $tree, string $at, string $name, array $more = [] ) use ( &$site ): array {
	return sourceCall( $site, 'builder/variant', [ 'template' => 'page-variants', 'tree' => $tree, 'at' => $at, 'name' => $name ] + $more );
};
$flat = static function( array $nodes ) use ( &$flat ): array {

	$out = [];

	foreach( $nodes as $node ) {
		$out[] = isset( $node['n'] ) === true ? [ $node['n'], $node['s'] ?? '', $node['a'] ?? [], is_array( $node['c'] ?? null ) === true ? $flat( $node['c'] ) : null ] : [ 't', $node['t'] ?? '' ];
	}

	return $out;
};
$before = sourceSnapshot( $site );
[ $setStatus, $set ] = $setVariant( $vTree, $splitAt, 'image-right' );
$row = $set['node']['c'][1]['a'] ?? [];
$treeRight = $vTree;
$treeRight[(int) $splitAt] = $set['node'] ?? [];
$withoutReverse = $set['node'] ?? [];
unset( $withoutReverse['c'][1]['a']['reverse'] );
[ , $backSet ] = $setVariant( $treeRight, $splitAt, 'image-left' );
[ , $previewRight ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-variants', 'tree' => $treeRight, 'texts' => [] ] );
$rightNow = array_column( array_filter( $previewRight['variants'] ?? [], static fn( array $entry ): bool => $entry['key'] === 'split-image-text' ), 'current' );

check( 'a variant is set by the server from the block it finds in the tree: the row of the picture gets reverse - and nothing else of the page changes, the texts, the sources and the children stay to the letter - the draft that comes of it is told as the variant, and the first variant gives back the tree it began with; nothing is written',
	$setStatus === 200 && ( $row['reverse'] ?? '' ) === 'on' && $flat( [ $withoutReverse ] ) === $flat( [ $vTree[(int) $splitAt] ] ) && ( $set['variant'] ?? '' ) === 'image-right' && $rightNow === [ 'image-right' ] && ( $backSet['node'] ?? null ) === $vTree[(int) $splitAt] && sourceSnapshot( $site ) === $before );

// the draft that comes of a variant is held to the rule of the step: a tree that holds markup the one who designs may not write is refused with the markup's own code, whatever the variant
$withMarkup = $vTree;
$withMarkup[(int) $listAt]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<b>x</b>' ] ] ];
[ $markupStatus, $markupBody ] = $setVariant( $withMarkup, $splitAt, 'image-right' );

check( 'a variant that is asked for in a draft that holds markup the step may not write is refused by the rule of the draft - 403, the code of the markup - and gives no tree', $markupStatus === 403 && ( $markupBody['code'] ?? '' ) === 'builder_refused_html' && isset( $markupBody['node'] ) === false );

// every variant of every block of the page that has some can be set and is known again - the choice is the same as a person makes by hand
$all = [];

foreach( $variantsOpen['variants'] ?? [] as $entry )
	foreach( $entry['items'] as [ $name ] ) {
		[ $one, $made ] = $setVariant( $vTree, (string) $entry['at'], $name );
		$made = $made['node'] ?? [];
		$copy = $vTree;
		$copy[(int) $entry['at']] = $made;
		[ , $answer ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-variants', 'tree' => $copy, 'texts' => [] ] );
		$now = array_values( array_filter( $answer['variants'] ?? [], static fn( array $other ): bool => $other['at'] === $entry['at'] ) );
		$all[$entry['key']. ' '. $name] = $one === 200 && ( $now[0]['current'] ?? null ) === $name && ( $made['a']['id'] ?? '' ) === ( $vTree[(int) $entry['at']]['a']['id'] ?? '' );
	}

check( 'each variant of each block of the page - the picture at either side, the hero in three, the list on three grounds - is set and is the variant that is told for the draft afterwards: '. count( $all ). ' of them', count( $all ) === 8 && array_filter( $all, static fn( bool $ok ): bool => $ok === false ) === [] );

// what a request names is the place of the block and the name of the variant, and nothing else: another block, another set or a key of the catalogue it brings is not read
[ $other ] = $setVariant( $vTree, $splitAt, 'image-right', [ 'key' => 'hero-full', 'block' => [ 'alternatives' => [ 'image-right' => [ 'set' => [ 'root' => [ 'ground' => 'dark' ] ] ] ] ], 'set' => [ 'root' => [ 'ground' => 'dark' ] ], 'origin' => 'hero-full' ] );
[ , $same ] = $setVariant( $vTree, $splitAt, 'image-right', [ 'key' => 'hero-full', 'set' => [ 'root' => [ 'ground' => 'dark' ] ] ] );

check( 'a request that brings the key of a block, a set of attributes or a block of its own is answered as one that does not: the block and its variants are the catalogue\'s, told from the tree', $other === 200 && ( $same['node'] ?? null ) === ( $set['node'] ?? 0 ) );

$tries = [
	'a name no block has'							=> [ $splitAt, 'upside-down' ],
	'a variant of another block'		=> [ $splitAt, 'image-compact' ],
	'no name'												=> [ $splitAt, '' ],
	'a name that is no word'				=> [ $splitAt, 'image-right"] [html]' ],
	'a block that has no variants'	=> [ (string) array_key_first( array_filter( $vTree, static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'template' ) ), 'image-right' ],
	'a place that is none'					=> [ '99', 'image-right' ],
	'a place that is no number'			=> [ 'root', 'image-right' ],
	'a place with a sign'						=> [ '-1', 'image-right' ],
];
$answers = [];

foreach( $tries as $label => [ $place, $name ] )
	$answers[$label] = $setVariant( $vTree, $place, $name )[0];

check( 'a name no block has, the variant of another block, no name, a name that is no word, a block that has no variants and a place that is none are refused - 403 for the name and the block, 400 for the place - and nothing is written',
	array_values( $answers ) === [ 403, 403, 403, 403, 403, 400, 400, 400 ] && sourceSnapshot( $site ) === $before );

// a block that was rebuilt is none whose variants are told: a row put in, a call taken out and a component of another name each end the choice - at the server, whatever the screen shows
$rebuilt = [
	'a row put in'					=> static function( array $node ): array { $node['c'][] = [ 'n' => 'section-row', 's' => '', 'a' => [], 'c' => [] ]; return $node; },
	'the row taken out'			=> static function( array $node ): array { $node['c'] = [ $node['c'][0] ]; return $node; },
	'another component'			=> static function( array $node ): array { $node['c'][1]['n'] = 'section-wrap'; return $node; },
	'a source changed'			=> static function( array $node ): array {

		$walk = static function( array $list ) use ( &$walk ): array {

			foreach( $list as $at => $inner )
				if( isset( $inner['n'] ) === true ) {

					if( $inner['n'] === 'section-title' ) {
						$list[$at]['s'] = '~other';
						return $list;
					}

					if( is_array( $inner['c'] ?? null ) === true && ( $changed = $walk( $inner['c'] ) ) !== $inner['c'] ) {
						$list[$at]['c'] = $changed;
						return $list;
					}
				}

			return $list;
		};
		$node['c'] = $walk( $node['c'] );

		return $node;
	},
	'a column taken out'		=> static function( array $node ): array {

		$cols = array_keys( array_filter( $node['c'][1]['c'], static fn( array $inner ): bool => ( $inner['n'] ?? '' ) === 'section-col' ) );
		unset( $node['c'][1]['c'][end( $cols )], $node['c'][1]['c'][end( $cols ) - 1] );
		$node['c'][1]['c'] = array_values( $node['c'][1]['c'] );

		return $node;
	},
];
$refused = [];

foreach( $rebuilt as $label => $make ) {
	$tree = $vTree;
	$tree[(int) $splitAt] = $make( $tree[(int) $splitAt] );
	$refused[$label] = [ $setVariant( $tree, $splitAt, 'image-right' )[0], array_column( sourceCall( $site, 'builder/preview', [ 'template' => 'page-variants', 'tree' => $tree, 'texts' => [] ] )[1]['variants'] ?? [], 'key' ) ];
}

check( 'a block that was rebuilt - a row put in, a call taken out, a component of another name, a source changed, a column taken out - has no choice of variants and cannot be set: 403, and the preview does not list it',
	array_column( $refused, 0 ) === [ 403, 403, 403, 403, 403 ] && array_filter( $refused, static fn( array $pair ): bool => in_array( 'split-image-text', $pair[1], true ) === true ) === [] && sourceSnapshot( $site ) === $before );

// a change by hand that is none of the variants is its own: the block keeps its choice - the names are told - and says so with null, until a variant is set, which takes the change back
$handmade = $vTree;
$handmade[(int) $splitAt]['c'][1]['a']['reverse'] = 'off m:on';
[ , $handAnswer ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-variants', 'tree' => $handmade, 'texts' => [] ] );
$hand = array_values( array_filter( $handAnswer['variants'] ?? [], static fn( array $entry ): bool => $entry['key'] === 'split-image-text' ) );
[ $handStatus, $handSet ] = $setVariant( $handmade, $splitAt, 'image-left' );

$ground = $vTree;
$ground[(int) $splitAt]['a']['ground'] = 'dark';
[ , $groundAnswer ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-variants', 'tree' => $ground, 'texts' => [] ] );
$kept = array_values( array_filter( $groundAnswer['variants'] ?? [], static fn( array $entry ): bool => $entry['key'] === 'split-image-text' ) );

check( 'a block that was changed by hand into none of its variants is told with no variant, its choices are still told, and a variant that is set takes the change back to the template - and a look that no variant names, the ground of the section, keeps the choice and the variant it has',
	count( $kept ) === 1 && $kept[0]['current'] === 'image-left' && count( $hand ) === 1 && $hand[0]['current'] === null && array_column( $hand[0]['items'], 0 ) === [ 'image-left', 'image-right' ] && $handStatus === 200 && ( $handSet['node'] ?? null ) === $vTree[(int) $splitAt] );

// the rights: the content step has no choice of variants, in no answer, and every action that sets one is a 403 for it
ninoUser( $site, 'variants-content@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$before = sourceSnapshot( $site );
[ , $contentOpen ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-variants' ] );
[ , $contentPreview ] = sourceCall( $site, 'builder/preview', [ 'template' => 'page-variants', 'tree' => $vTree, 'texts' => [] ] );
[ $contentStatus, $contentBody ] = $setVariant( $vTree, $splitAt, 'image-right' );

check( 'the one who may only write the content is told of no variants - not in the open answer, not in a preview - and is refused by the action that sets one: 403, the level, and nothing is written',
	isset( $contentOpen['variants'] ) === false && isset( $contentPreview['variants'] ) === false && $contentStatus === 403 && ( $contentBody['code'] ?? '' ) === 'builder_level' && sourceSnapshot( $site ) === $before );

// the title of a type of elements that a loop of the page shows is told to every step, and no other title: the one who only writes the content reads "Services" where the loop says /services
\Nino\Elements::insertElementType( $site, '/unused-type', [ 'title' => [ 'type' => 'string' ] ] );
\Nino\Filesystem::putFileContent( $site, '/templates/page-typed.tpl', "<!-- nino:template-name Typed -->\n[template /templates/html-header]\n[section-wrap id=\"typed\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[collection /services]\n\t\t\t\t[section-title .title]\n\t\t\t[/collection]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n[template /templates/html-footer]\n" );
$summary = array_column( \Nino\Modules\Elements\Types::summaries( $site ), 'title', 'uri' );
[ , $typedContent ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-typed' ] );
[ , $plainContent ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-home' ] );

check( 'the answer of an opened page tells the one who may only write the content the title of the type its loop shows - and the title of no type the page does not show',
	( $typedContent['titles'] ?? null ) === [ '/services' => (string) $summary['services'] ] && $summary['services'] !== '' && ( $plainContent['titles'] ?? null ) === [] );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

// the tile of the dialog is one for each block and says how many variants it has
[ , $listed ] = sourceCall( $site, 'builder/blocks' );
$tiles = array_column( $listed['blocks'] ?? [], 'variants', 'key' );
$keys = array_column( $listed['blocks'] ?? [], 'key' );

check( 'the dialog lists one tile for each block - a block with variants is not listed once for each - and the tile has the number of its variants, none where it has none',
	count( $keys ) === count( array_unique( $keys ) ) && ( $tiles['split-image-text'] ?? -1 ) === 2 && ( $tiles['hero-full'] ?? -1 ) === 3 && ( $tiles['list-over-elements'] ?? -1 ) === 3 && ( $tiles['hero-simple'] ?? $tiles[array_key_first( array_diff_key( $tiles, [ 'split-image-text' => 1, 'hero-full' => 1, 'list-over-elements' => 1 ] ) ) ?? ''] ?? 0 ) === 0 );

// a block that is put in is the template, and the template is the first variant
$firsts = [];

foreach( [ 'hero-full', 'split-image-text', 'list-over-elements' ] as $key ) {

	$catalogue = \Nino\Blocks::get( $site, $key );
	$put = \Nino\Modules\Builder\Library::block( $site, $key );
	$firsts[$key] = $catalogue !== null && $put !== null && \Nino\Blocks::variantOf( $catalogue, $put['tree'] ) === array_key_first( $catalogue['alternatives'] );
}

check( 'a block that is put in has the first of its variants: the template as the dialog puts it in is the variant that comes first in the file, for each block that has some', array_values( $firsts ) === [ true, true, true ] );

// the blocks of the catalogue that name variants: valid, with labels in both languages, only attributes the register writes as classes, and a first variant that is the template as it is
$catalogue = [ 'Forms/blocks/contact-form', 'Collections/blocks/list-over-elements' ];
$registryNow = \Nino\Components::components( $site );
$sound = [];

foreach( $catalogue as $dir ) {

	$json = json_decode( (string) file_get_contents( NINO_FEATURES_DIR. '/'. $dir. '/block.json' ), true );
	$tpl = (string) file_get_contents( NINO_FEATURES_DIR. '/'. $dir. '/block.tpl' );
	$variants = is_array( $json['variants'] ?? null ) === true ? $json['variants'] : [];
	$ok = count( $variants ) >= 2 && count( $variants ) <= \Nino\Blocks::MAX_VARIANTS && ( reset( $variants )['set'] ?? null ) === [];

	foreach( $variants as $name => $variant ) {

		$ok = $ok && is_string( $name ) && preg_match( '/^[a-z][a-z0-9-]*$/D', $name ) === 1 && ( $variant['label']['de_DE'] ?? '' ) !== '' && ( $variant['label']['en_US'] ?? '' ) !== '';

		foreach( $variant['set'] ?? [] as $place => $attributes ) {

			$component = $place === 'root' ? 'section-wrap' : 'section-row';

			foreach( $attributes as $attribute => $value )
				$ok = $ok && in_array( $value, (array) ( $registryNow[$component]['attributes'][$attribute]['options'] ?? [] ), true ) === true && ( $registryNow[$component]['attributes'][$attribute]['class'] ?? true ) !== false && str_contains( $tpl, '['. $component ) === true;
		}
	}

	$sound[$dir] = $ok;
}

check( 'the blocks of the catalogue that name variants - the contact form and the list over elements - have two or three, the first of them the template as it is, labels in both languages, and set only the looks that the register takes as classes', array_values( $sound ) === [ true, true ] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nI. The seam for a second panel\n";

// the two steps of saving a block as a block of the library are public and each is its own: the parts from the tree of a block - nothing written -, and the parts held to the one rule of the
// kernel and written - nothing written for what it refuses
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
\Nino\Filesystem::putFileContent( $site, '/templates/page-seam.tpl', "<!-- nino:template-name Seam -->\n[template /templates/html-header]\n[section-wrap id=\"seam\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[section-title ~title tag=\"h2\"]\n\t\t\t[text ~text]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n[template /templates/html-footer]\n" );
\Nino\Text::put( $site, [ '/template/page-seam/seam/title' => [ 'de_DE' => 'Eine Naht', 'en_US' => 'A seam' ], '/template/page-seam/seam/text' => [ 'de_DE' => 'Zwei Seiten', 'en_US' => 'Two sides' ] ] );
[ , $seamOpen ] = sourceCall( $site, 'builder/open', [ 'template' => 'page-seam' ] );
$seamState = \Nino\Modules\Builder\Store::state( $site, 'page-seam' );
$seamAt = (string) array_search( 'seam', array_map( static fn( array $node ): string => (string) ( $node['a']['id'] ?? '' ), $seamOpen['tree'] ), true );
$seamInput = [ 'at' => $seamAt, 'key' => 'seam-karten', 'name' => 'Naht', 'description' => 'Eine Naht', 'category' => 'content', 'texts' => [], 'pictures' => [] ];
$beforeSeam = sourceSnapshot( $site );
$seamParts = \Nino\Modules\Builder\Custom::parts( $site, 'page-seam', $seamState, $seamOpen['tree'], $seamInput );
$meta = json_decode( (string) ( $seamParts['parts']['json'] ?? '' ), true );
unset( $site['./nino/blocks/state'] );

check( 'the parts of a block come from the tree of a block and nothing is written: the details of block.json in each language, the template with the id of the root, the words of each language - and a key, a name or a category that is none, or a place that is no block, is refused with its own code',
	( $seamParts['key'] ?? '' ) === 'seam-karten' && ( $meta['name']['de_DE'] ?? '' ) === 'Naht' && ( $meta['category'] ?? '' ) === 'content' && str_starts_with( (string) $seamParts['parts']['tpl'], '[section-wrap id="seam"' ) && isset( $seamParts['parts']['texts']['de_DE'], $seamParts['parts']['texts']['en_US'] ) === true
	&& sourceSnapshot( $site ) === $beforeSeam && isset( \Nino\Blocks::all( $site )['seam-karten'] ) === false
	&& ( \Nino\Modules\Builder\Custom::parts( $site, 'page-seam', $seamState, $seamOpen['tree'], [ 'key' => 'Bad Key' ] + $seamInput )['failure'][2] ?? '' ) === 'builder_own_key'
	&& ( \Nino\Modules\Builder\Custom::parts( $site, 'page-seam', $seamState, $seamOpen['tree'], [ 'category' => 'frame' ] + $seamInput )['failure'][2] ?? '' ) === 'builder_own_category'
	&& ( \Nino\Modules\Builder\Custom::parts( $site, 'page-seam', $seamState, $seamOpen['tree'], [ 'at' => '999' ] + $seamInput )['failure'][2] ?? '' ) === 'builder_own_block' );

$bad = $seamParts['parts'];
$bad['tpl'] = "[nothing-like-this-call]\n";
$beforeWrite = sourceSnapshot( $site );
$refusedWrite = \Nino\Modules\Builder\Custom::write( $site, 'seam-bad', $bad );
$seamWritten = \Nino\Modules\Builder\Custom::write( $site, 'seam-karten', $seamParts['parts'] );
unset( $site['./nino/blocks/state'] );
$again = \Nino\Modules\Builder\Custom::write( $site, 'seam-karten', $seamParts['parts'] );

check( 'the parts are held to the rule of the kernel and written as a block of the project in the second step: what Blocks::validate() refuses is not written and nothing is left of it, a good block is listed by the kernel as the project\'s own, a key that is taken is refused with 409 and a key that is none with 400',
	( $refusedWrite['failure'][2] ?? '' ) === 'builder_own_invalid' && is_dir( $sandbox. '/private/blocks/seam-bad' ) === false && $seamWritten === [ 'key' => 'seam-karten' ] && ( \Nino\Blocks::all( $site )['seam-karten']['origin'] ?? '' ) === 'project'
	&& ( $again['failure'][0] ?? 0 ) === 409 && ( \Nino\Modules\Builder\Custom::write( $site, 'Bad Key', $seamParts['parts'] )['failure'][0] ?? 0 ) === 400 && array_keys( array_diff_key( sourceSnapshot( $site ), $beforeWrite ) ) !== [] );

// the public drawer: the right is the caller's and has no default - a call that says no draws nothing - and a draft of one block is a source with one call
$source = "[section-wrap id=\"seam\"]\n\t[section-title text=\"Naht\"]\n[/section-wrap]\n";
$draft = [ 'template' => 'block-draft', 'source' => $source, 'fills' => [], 'locale' => 'en_US', 'slots' => [] ];
$drawn = [];

foreach( [ 'no right' => [ $draft, false ], 'two blocks' => [ [ 'source' => $source. $source ] + $draft, true ], 'free markup' => [ [ 'source' => $source. "<b>x</b>\n" ] + $draft, true ], 'no call' => [ [ 'source' => "\n" ] + $draft, true ], 'one block' => [ $draft, true ] ] as $label => [ $given, $allowed ] ) {

	$appData = $site;
	unset( $appData['./nino/preview/trace'] );
	$request = [];
	$answer = \Nino\Modules\Builder::drawBlock( $appData, $request, $given, $allowed );
	$drawn[$label] = [ $answer, isset( $request['/nino/http/response']['locale'] ), ( $appData['./nino/preview/trace'] ?? false ) === true ];
}

// the general drawer, which the draft of a page is drawn by, has the same right as a parameter
$appData = $site;
unset( $appData['./nino/preview/trace'] );
$request = [];
$drawn['general, no right'] = [ \Nino\Modules\Builder::draw( $appData, $request, $draft, false, true ), isset( $request['/nino/http/response']['locale'] ), ( $appData['./nino/preview/trace'] ?? false ) === true ];

check( 'the drawer of the draft of one block draws it with the trace only where the caller says the account may have it - and a source of two blocks, of free markup or of no call is not drawn: nothing of the language or the trace is set for any of them',
	$drawn['one block'] === [ true, true, true ] && array_filter( $drawn, static fn( array $result, string $label ): bool => $label !== 'one block' && $result !== [ false, false, false ], ARRAY_FILTER_USE_BOTH ) === [] );

ninoDone( $site );
