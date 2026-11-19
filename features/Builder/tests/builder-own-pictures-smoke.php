<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-own-pictures-smoke.php	Contract test for the picture a block has of its own: where a block is put into a page, a slot that the block has a picture for begins with it
 *
 *															A. the tile of a block draws its own picture, for the one who may put the block in
 *															B. a block that is put in has the picture in the draft, owns it after the save, and the block of the library may go
 *															C. what may not come of it: bytes of a request, a picture of a kind the kernel does not take, a step that does not design, a file left behind
 *
 *	Usage: php features/Builder/tests/builder-own-pictures-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-own-pictures-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Library;
use Nino\Modules\Builder\Store;

function ownCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, json_decode( (string) json_encode( $body ), true ) ];
}

use Nino\Modules\Builder\Tree;

/** A block put in as the panel does it - the ids, the words - as builder-smoke.php does: the test of the picture holds the same tree */
function ownInsert( array &$appData, array $page, string $key, int $at, ?string $id = null ): array {

	[ , $block ] = ownCall( $appData, 'builder/block', [ 'key' => $key ] );
	$registry = \Nino\Components::components( $appData );
	$taken = Tree::taken( $page['tree'], $registry, array_merge( array_keys( $page['texts'] ), $page['slots'] ) );
	$base = (string) preg_replace( '/-\d+$/', '', $block['id'] );
	$now = $id;

	for( $count = 1; $now === null; $count++ ) {

		$candidate = $count === 1 ? $block['id'] : $base. '-'. $count;
		$now = in_array( $candidate, $taken, true ) === true || array_filter( array_keys( $page['texts'] ), static fn( string $name ): bool => str_starts_with( $name, '/template/page-home/'. $candidate. '/' ) ) !== [] ? null : $candidate;
	}

	$walk = static function( array $nodes ) use ( &$walk, $registry, $block, $now ): array {

		foreach( $nodes as $index => $node ) {

			$name = isset( $node['n'] ) === true && isset( $registry[$node['n']] ) === true ? Tree::idAttribute( $registry[$node['n']] ) : '';

			if( $name !== '' && isset( $node['a'][$name] ) === true )
				$nodes[$index]['a'][$name] = $node['a'][$name] === $block['id'] ? $now : Tree::inner( $node['a'][$name], $block['id'], $now );

			if( is_array( $node['c'] ?? null ) === true )
				$nodes[$index]['c'] = $walk( $node['c'] );
		}

		return $nodes;
	};

	$tree = $page['tree'];
	array_splice( $tree, $at, 0, [ $walk( $block['tree'] )[0], [ 't' => "\n" ] ] );
	$texts = [];

	foreach( $block['slots'] as $slot ) {

		if( $slot['kind'] !== 'text' )
			continue;

		$was = $slot['scope'] === '' ? $block['id'] : $block['id']. '-'. $slot['scope'];
		$scope = $was === $block['id'] ? $now : Tree::inner( $was, $block['id'], $now );

		foreach( $block['texts'] as $locale => $words )
			$texts['/template/page-home/'. $scope. '/'. $slot['word']][$locale] = $words[$slot['name']];
	}

	return [ 'tree' => $tree, 'texts' => $texts, 'inserted' => [ $now => $key ], 'id' => $now, 'block' => $block ];
}

$site = ninoSandbox( 'builder-own-pictures' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );

foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => 'Hallo' ] );

$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];
ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Themelab::PERM ] );

// Two blocks of the project that have a picture of their own for their slot - a png, and a svg with a script in it - and one that has none
$names = [ 'png' => [ 'Mit Bild', 'With picture' ], 'svg' => [ 'Mit Vektor', 'With vector' ], 'plain' => [ 'Ohne Bild', 'Without picture' ] ];
$keys = [];

foreach( $names as $at => [ $de, $en ] ) {

	[ , $made ] = ownCall( $site, 'themelab/create', [ 'name' => [ 'de_DE' => $de, 'en_US' => $en ], 'category' => 'content', 'from' => 'split-image-text' ] );
	$keys[$at] = (string) ( $made['key'] ?? '' );
}

// The pictures are made with gd, as the pictures of the kernel's own suites are: a php without it leaves everything that needs a picture out and says so
if( function_exists( 'imagecreatetruecolor' ) === false ) {

	echo "  note  - gd is not there: the checks about the pictures of a block are left out\n";
	ninoDone( $site );
}

$image = imagecreatetruecolor( 120, 90 );
imagefill( $image, 0, 0, (int) imagecolorallocate( $image, 200, 30, 30 ) );
ob_start();
imagepng( $image );
$png = (string) ob_get_clean();
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"><script>alert(2)</script><rect width="10" height="10" fill="#cc2222"/></svg>';
$cleanSvg = (string) \Nino\Images::cleanSvg( $svg );

foreach( [ 'png' => [ 'picture.png', $png ], 'svg' => [ 'picture.svg', $svg ] ] as $at => [ $file, $bytes ] ) {

	@mkdir( $sandbox. '/private/blocks/'. $keys[$at]. '/images', 0755, true );
	file_put_contents( $sandbox. '/private/blocks/'. $keys[$at]. '/images/'. $file, $bytes );
}

\Nino\Blocks::forget( $site );

$block = static fn( string $at ): array => (array) Library::block( $site, $keys[$at] );
$uriOf = static fn( string $template, string $at ): string => '/template/'. $template. '/'. $block( $at )['id']. '/picture';
$images = static fn(): array => array_keys( ( static function( string $dir ): array {

	$out = [];

	foreach( (array) glob( $dir. '/images/template/*/*/*' ) as $path )
		$out[$path] = 1;

	return $out;
} )( $sandbox ) );

echo "\nA. The tile\n";

$tile = Library::tile( $site, 'page-home', $keys['png'], 'de_DE', true );
$slot = $tile['slots'][$uriOf( 'page-home', 'png' )] ?? [];
$file = (string) ( $slot['filename'] ?? '' );
$plain = Library::tile( $site, 'page-home', $keys['plain'], 'de_DE', true );
$without = Library::tile( $site, 'page-home', $keys['png'], 'de_DE', false );

check( 'the tile of a block that has a picture of its own for a slot draws it: the slot of the tile has the file that was made of the picture, in the size of the slot - the picture is no placeholder',
	preg_match( '#\.s[0-9a-f]{12}\.[0-9]+x[0-9]+\.(?:webp|png|jpg)$#D', $file ) === 1 && \Nino\Images::read( $site, $file ) !== false && ( $slot['width'] ?? 0 ) === $block( 'png' )['images']['picture']['width'] && str_contains( (string) $tile['source'], '/nino/placeholder/' ) === false );

check( 'a block that has no picture of its own has the placeholder in its tile, as it did; and the tile for one who may not put the block in has the placeholder, too',
	( $plain['slots'] ?? [ 'x' ] ) === [] && str_contains( (string) $plain['source'], '/nino/placeholder/' ) === true && ( $without['slots'] ?? [ 'x' ] ) === [] && str_contains( (string) $without['source'], '/nino/placeholder/' ) === true );

$again = Library::tile( $site, 'page-home', $keys['png'], 'de_DE', true );
$files = $images();
Library::tile( $site, 'page-home', $keys['png'], 'de_DE', true );

check( 'a tile that is drawn again makes no more files: the same picture is the same file', ( $again['slots'][$uriOf( 'page-home', 'png' )]['filename'] ?? '' ) === $file && $images() === $files );

// a block of a package of the catalogue has its pictures beside it, and is drawn with them
$pack = $sandbox. '/private/packages/blocks/pictured';
@mkdir( $pack. '/pic-block/text', 0755, true );
@mkdir( $pack. '/pic-block/images', 0755, true );
file_put_contents( $pack. '/pack.json', json_encode( [ 'key' => 'pictured', 'version' => '1.0.0', 'name' => 'Pictured', 'description' => 'Pictured' ] ). "\n" );

foreach( [ 'block.json', 'block.tpl', 'text/de_DE.json', 'text/en_US.json', 'images/picture.png' ] as $part )
	file_put_contents( $pack. '/pic-block/'. $part, (string) file_get_contents( $sandbox. '/private/blocks/'. $keys['png']. '/'. $part ) );

\Nino\Blocks::forget( $site );
$packed = Library::picture( $site, 'pic-block', 'picture' );
$packedTile = Library::tile( $site, 'page-home', 'pic-block', 'de_DE', true );

check( 'a block of a package has its picture beside it, and the picture is its tile\'s: the bytes are read from the folder of the package - the origin of the block says where - and a block of the kernel that has none has none',
	is_array( $packed ) === true && $packed['type'] === 'png' && $packed['bytes'] === $png && count( (array) ( $packedTile['slots'] ?? [] ) ) === 1 && Library::picture( $site, 'hero-full', 'picture' ) === null );

echo "\nB. A block that is put in\n";

// a page that has a block already - a block put in joins the blocks of the page - the block of the project that has no picture of its own, as the dialog puts it in
$laid = '';

foreach( \Nino\Modules\Builder\Tree::read( $site, (string) file_get_contents( $sandbox. '/private/blocks/'. $keys['plain']. '/block.tpl' ) ) as $one )
	if( isset( $one['n'] ) === true )
		$laid = rtrim( \Nino\Markup::write( \Nino\Markup::layout( [ $one ], 0 ), \Nino\Components::containers( $site ) ) );

\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n". $laid. "\n[template /templates/html-footer]\n" );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $page ] = ownCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$state = Store::state( $site, 'page-home' );

$page += [ 'slots' => (array) ( $page['slots'] ?? [] ) ];
$put = static fn( string $at ): array => ownInsert( $site, $page, $keys[$at], (int) array_key_last( array_filter( $page['tree'], static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'template' ) ) );
$with = static fn( string $at ): array => $put( $at )['tree'];
$words = static fn( string $at ): array => $put( $at )['texts'];

$prepared = Store::prepare( $site, 'page-home', $state, $with( 'png' ), $words( 'png' ), [], [ $block( 'png' )['id'] => $keys['png'] ], \Nino\Modules\Builder::LEVEL_DESIGN, [] );
$uri = $uriOf( 'page-home', 'png' );
$got = $prepared['shown'][$uri]['filename'] ?? '';

check( 'a block that is put in has its own picture in the draft: the slot of the page that the block makes has the file that was made of the picture of the block, the draft is told so, and the file is among those the draft gives up',
	isset( $prepared['failure'] ) === false && preg_match( '#^template/page-home/'. preg_quote( $block( 'png' )['id'], '#' ). '/picture\.s[0-9a-f]{12}\.[0-9]+x[0-9]+\.(?:webp|png|jpg)$#D', $got ) === 1 && ( $prepared['slots'][$uri]['filename'] ?? '' ) === $got
	&& \Nino\Images::read( $site, $got ) !== false && $prepared['staged'] === [ $got ] && isset( $prepared['files'][$got] ) === false );

$plainPrepared = Store::prepare( $site, 'page-home', $state, $with( 'plain' ), $words( 'plain' ), [], $put( 'plain' )['inserted'], \Nino\Modules\Builder::LEVEL_DESIGN, [] );
$plainUri = '/template/page-home/'. $put( 'plain' )['id']. '/picture';

check( 'a slot that the block has no picture of its own for has the placeholder of the kernel as it did: its file is the one of the slot, made from the placeholder when the page is saved',
	( $plainPrepared['slots'][$plainUri]['filename'] ?? '' ) === ltrim( $plainUri, '/' ). '.svg' && $plainPrepared['staged'] === [] && isset( $plainPrepared['files'][ltrim( $plainUri, '/' ). '.svg'] ) === true );

[ $previewStatus, $preview ] = ownCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $with( 'png' ), 'texts' => $words( 'png' ), 'inserted' => [ $block( 'png' )['id'] => $keys['png'] ] ] );
$saved = ownCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $with( 'png' ), 'texts' => $words( 'png' ), 'inserted' => [ $block( 'png' )['id'] => $keys['png'] ], 'base' => $page['stamp'] ] )[0];
$after = Store::state( $site, 'page-home' )['slots'][$uri]['filename'] ?? '';

check( 'the preview says which files were made for the draft, and the save gives the slot of the page the picture: after it the file is the picture of the page',
	$previewStatus === 200 && ( $preview['staged'] ?? [] ) === [ $got ] && $saved === 200 && $after === $got && \Nino\Images::read( $site, $got ) !== false );

// the block of the library may go: the page keeps the picture
ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ $removed ] = ownCall( $site, 'themelab/remove', [ 'key' => $keys['png'] ] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
\Nino\Blocks::forget( $site );
unset( $site['./nino/blocks/state'] );

check( 'the picture is the page\'s own from the save on: the block of the library is taken away, and the slot of the page has its file still, whose bytes are the picture',
	$removed === 200 && ( Store::state( $site, 'page-home' )['slots'][$uri]['filename'] ?? '' ) === $got && \Nino\Images::read( $site, $got ) !== false && isset( \Nino\Blocks::all( $site )[$keys['png']] ) === false );

// a svg is the picture that is cleaned, in a file of the page as well
$vector = Store::prepare( $site, 'page-home', Store::state( $site, 'page-home' ), $with( 'svg' ), $words( 'svg' ), [], [ $block( 'svg' )['id'] => $keys['svg'] ], \Nino\Modules\Builder::LEVEL_DESIGN, [] );
$vectorFile = (string) ( $vector['shown'][$uriOf( 'page-home', 'svg' )]['filename'] ?? '' );

check( 'a svg that a block has for a slot is the page\'s as the picture that was cleaned - no script, no event attribute - in a file of its own',
	str_ends_with( $vectorFile, '.svg' ) === true && \Nino\Images::read( $site, $vectorFile ) === $cleanSvg && preg_match( '/script|onload|alert/i', (string) \Nino\Images::read( $site, $vectorFile ) ) === 0 );

echo "\nC. What may not come of it\n";

// the page as it is now: the picture of the first block is its own, and the tree has the block
[ , $page ] = ownCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$page += [ 'slots' => (array) ( $page['slots'] ?? [] ) ];


// the bytes are the block's, read by the server: nothing a request says is a picture - a file named by the request for a slot of a block is no file of it
$foreign = Store::prepare( $site, 'page-home', Store::state( $site, 'page-home' ), $with( 'svg' ), $words( 'svg' ), [], [ $block( 'svg' )['id'] => $keys['svg'] ], \Nino\Modules\Builder::LEVEL_DESIGN, [ $uriOf( 'page-home', 'svg' ) => [ 'file' => 'template/page-home/other/picture.s000000000000.10x10.png' ] ] );

check( 'a request that names a file for the slot of a block that is put in is refused, and nothing it says is a picture of the block: the bytes come from the block that the server reads',
	isset( $foreign['failure'] ) === true && Library::picture( $site, $keys['svg'], '../picture' ) === null && Library::picture( $site, $keys['svg'], 'other' ) === null && Library::picture( $site, '../x', 'picture' ) === null && Library::picture( $site, 'hero-full', 'nothing' ) === null );

// the step: an account that does not design puts no block in, and no file is made for it - where the one who designs gets the picture of the same block
@mkdir( $sandbox. '/private/blocks/'. $keys['plain']. '/images', 0755, true );
file_put_contents( $sandbox. '/private/blocks/'. $keys['plain']. '/images/picture.png', $png );
\Nino\Blocks::forget( $site );

$before = $images();
$plainUriNow = '/template/page-home/'. $put( 'plain' )['id']. '/picture';
$content = Store::prepare( $site, 'page-home', Store::state( $site, 'page-home' ), $with( 'plain' ), $words( 'plain' ), [], $put( 'plain' )['inserted'], \Nino\Modules\Builder::LEVEL_CONTENT, [] );
$contentTile = Library::tile( $site, 'page-home', $keys['plain'], 'de_DE', false );
$untouched = $images() === $before;
$design = Store::prepare( $site, 'page-home', Store::state( $site, 'page-home' ), $with( 'plain' ), $words( 'plain' ), [], $put( 'plain' )['inserted'], \Nino\Modules\Builder::LEVEL_DESIGN, [] );

check( 'what an account that does not design gets of this is nothing: the block is not put in - the build of a page is not its - no file is made, nothing to give up, and the tile shows the placeholder - where the account that designs gets the picture of the same block',
	( $content['failure'][2] ?? '' ) === 'builder_refused_block' && ( $contentTile['slots'] ?? [ 'x' ] ) === [] && $untouched === true
	&& count( $design['staged'] ?? [] ) === 1 );

// the draft that is thrown away gives its pictures up: the files that nobody was given are taken away, those of a slot of the page are not
$thrown = Store::prepare( $site, 'page-home', Store::state( $site, 'page-home' ), $with( 'svg' ), $words( 'svg' ), [], [ $block( 'svg' )['id'] => $keys['svg'] ], \Nino\Modules\Builder::LEVEL_DESIGN, [] );
$staged = $thrown['staged'];
[ $unstaged ] = ownCall( $site, 'builder/unstage', [ 'template' => 'page-home', 'files' => array_merge( $staged, [ $got ] ) ] );

check( 'a draft that is thrown away leaves no file of its own pictures: the files of the draft are gone after it, and the picture that the page has been given is not',
	$unstaged === 200 && $staged !== [] && array_filter( $staged, static fn( string $file ): bool => \Nino\Images::read( $site, $file ) !== false ) === [] && \Nino\Images::read( $site, $got ) !== false );

ninoDone( $site );
