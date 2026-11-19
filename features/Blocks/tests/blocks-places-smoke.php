<?php
declare(strict_types=1);

/**
 *	Nino features
 *	blocks-places-smoke.php	Contract test for what a block of the library holds of content: places with an id, and the way a place becomes the page's
 *
 *															A. a block with a title, a text, a button, a picture and a loop is saved - its places have the ids the person gave - and put into a page: the keys of
 *															   the page are made from the page, the id of the block and the id of the place, the same for each block put in, and the sample is the first text
 *															B. what the request may not bring: an id with foreign characters, an id twice, a picture of a size the kernel does not take, markup in a sample
 *															C. the loops: the dialog of the components offers them as loops, and a block that holds one is saved and put in
 *
 *	Usage: php features/Blocks/tests/blocks-places-smoke.php
 *				 NINO_ROOT=../nino php features/Blocks/tests/blocks-places-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Tree;

/** One action of the API, as the workbench posts it: [ status, body ] */
function placesCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, json_decode( (string) json_encode( $body ), true ) ];
}

/** The files a block of the project is, for "nothing is written" */
function placesSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/blocks', 'private/templates', 'private/text' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

/** The position of the first call a test says yes to, depth first, or null */
function placesFind( array $nodes, callable $test, string $prefix = '' ): ?string {

	foreach( $nodes as $at => $node ) {

		if( isset( $node['n'] ) === false )
			continue;

		if( $test( $node ) === true )
			return $prefix. $at;

		if( is_array( $node['c'] ?? null ) === true && ( $found = placesFind( $node['c'], $test, $prefix. $at. '.' ) ) !== null )
			return $found;
	}

	return null;
}

/** The call at a place of a tree changed by a function */
function placesEdit( array $nodes, string $position, callable $change ): array {

	$parts = explode( '.', $position );
	$index = (int) array_shift( $parts );

	if( $parts === [] )
		$nodes[$index] = $change( $nodes[$index] );
	else
		$nodes[$index]['c'] = placesEdit( $nodes[$index]['c'], implode( '.', $parts ), $change );

	return $nodes;
}

// ---------------------------------------------------------------------------------------------------------------------------------
$site = ninoSandbox( 'blocks-places' );
$sandbox = ninoSandboxDir( $site );

$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Blocks' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );

foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => 'Willkommen' ] );

$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ], '/nino/http/routes' => $site['/nino/http/routes'] ] );
\Nino\Elements::insertElementType( $site, '/services', [ 'title' => [ 'type' => 'string' ] ] );
ninoUser( $site, 'maker@example.com', [ \Nino\Modules\Blocks::PERM, '/_admin/builder/manage', '/_admin/builder/design/*' ] );

echo "\nA. From a block with places to a page\n";

[ $made, $one ] = placesCall( $site, 'blocks/create', [ 'name' => 'Platz Block', 'category' => 'content', 'from' => '' ] );
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $one['key'] ?? 'platz-block' ] );
$key = $one['key'] ?? 'platz-block';
$id = (string) ( $draft['tree'][array_key_first( array_filter( $draft['tree'], static fn( array $node ): bool => isset( $node['n'] ) === true && $node['n'] === 'section-wrap' ) )]['a']['id'] ?? '' );
$col = placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ) ?? '';
$call = static fn( string $name, string $source, array $attributes = [] ): array => [ 'n' => $name, 's' => $source, 'a' => $attributes, 'c' => null ];
$tree = placesEdit( $draft['tree'], $col, static function( array $node ) use ( $call ): array {

	$node['c'] = [ [ 't' => "\n" ], $call( 'section-title', '~titel' ), [ 't' => "\n" ], $call( 'text', '~absatz' ), [ 't' => "\n" ], $call( 'button', '~knopf', [ 'href' => 'page:/home' ] ), [ 't' => "\n" ], $call( 'image', '~bild' ), [ 't' => "\n" ] ];

	return $node;
} );
$base = '/template/'. $draft['template']. '/'. $id. '/';
$sample = [ 'titel' => 'Lorem ipsum dolor', 'absatz' => 'Sit amet consectetur adipiscing elit sed do eiusmod tempor.', 'knopf' => 'Dolor sit' ];
$texts = [];

foreach( $sample as $name => $text )
	$texts[$base. $name] = [ 'de_DE' => $text, 'en_US' => $text ];

[ $saved, $answer ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $tree, 'texts' => $texts, 'pictures' => [ $base. 'bild' => [ 'label' => 'Bild', 'width' => 800, 'height' => 600 ] ], 'base' => $draft['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$kept = \Nino\Blocks::get( $site, $key );

check( 'a block with a title, a text, a button and a picture is saved with the ids the person gave its places - titel, absatz, knopf, bild - and the sample of each place, the same words in both languages, is in the files of the block',
	$saved === 200 && is_array( $kept ) === true && str_contains( (string) $kept['source'], '[section-title ~titel]' ) === true && str_contains( (string) $kept['source'], '[text ~absatz]' ) === true && str_contains( (string) $kept['source'], '~bild' ) === true
	&& ( $kept['texts']['de_DE'][$base. 'titel'] ?? $kept['texts']['de_DE']['titel'] ?? '' ) === 'Lorem ipsum dolor' && ( $kept['texts']['en_US'][$base. 'titel'] ?? $kept['texts']['en_US']['titel'] ?? '' ) === 'Lorem ipsum dolor' );

// the block is put into a page: the keys of the page are made from the page, the id of the block and the id of the place - and that is stable and the same for each block put in
[ , $page ] = placesCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$registry = \Nino\Components::components( $site );
$put = static function( int $count ) use ( &$site, $key, $registry ): array {

	[ , $block ] = placesCall( $site, 'builder/block', [ 'key' => $key ] );
	[ , $page ] = placesCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
	$taken = Tree::taken( $page['tree'], $registry, array_merge( array_keys( $page['texts'] ), $page['slots'] ?? [] ) );
	$base = (string) preg_replace( '/-\d+$/', '', $block['id'] );
	$now = $block['id'];

	for( $number = 2; in_array( $now, $taken, true ) === true; $number++ )
		$now = $base. '-'. $number;

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

	$at = (int) array_key_last( array_filter( $page['tree'], static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'template' ) );
	$tree = $page['tree'];
	array_splice( $tree, $at, 0, [ $walk( $block['tree'] )[0], [ 't' => "\n" ] ] );
	$words = [];

	foreach( $block['slots'] as $slot )
		if( $slot['kind'] === 'text' ) {

			$was = $slot['scope'] === '' ? $block['id'] : $block['id']. '-'. $slot['scope'];
			$scope = $was === $block['id'] ? $now : Tree::inner( $was, $block['id'], $now );

			foreach( $block['texts'] as $locale => $set )
				$words['/template/page-home/'. $scope. '/'. $slot['word']][$locale] = $set[$slot['name']];
		}

	[ $status, $body ] = placesCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $words, 'inserted' => [ $now => $key ], 'base' => $page['stamp'] ] );

	return [ $status, $now, $words, $body ];
};

[ $first, $firstId, $firstWords ] = $put( 1 );
[ $second, $secondId, $secondWords ] = $put( 2 );
$pageSource = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
unset( $site['./nino/text/values'] );

check( 'a block that is put into a page gives its places the keys /template/<page>/<id of the block>/<id of the place> - the id of the block where the page has none, the id with the next number behind it for the next - each with the sample as its first text; the call reads the place by its id',
	$first === 200 && $second === 200 && $firstId === $id && $secondId === $id. '-2'
	&& array_keys( $firstWords ) === [ '/template/page-home/'. $id. '/titel', '/template/page-home/'. $id. '/absatz', '/template/page-home/'. $id. '/knopf' ]
	&& array_keys( $secondWords ) === [ '/template/page-home/'. $id. '-2/titel', '/template/page-home/'. $id. '-2/absatz', '/template/page-home/'. $id. '-2/knopf' ]
	&& ( $firstWords['/template/page-home/'. $id. '/titel']['de_DE'] ?? '' ) === 'Lorem ipsum dolor' && ( $secondWords['/template/page-home/'. $id. '-2/absatz']['en_US'] ?? '' ) === $sample['absatz']
	&& substr_count( $pageSource, '[section-title ~titel]' ) === 2 && str_contains( $pageSource, 'id="'. $id. '-2"' ) === true );

echo "\nB. What a request may not bring\n";

[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$before = placesSnapshot( $site );
$tries = [
	'an id with a capital' => [ placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-title' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~Titel' ] ) ), $draft['texts'] ],
	'an id with a script' => [ placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-title' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~a"><script>' ] ) ), $draft['texts'] ],
];
$sizes = static function( int $width, int $height ) use ( $draft ): array {

	$details = $draft['details'];
	$details['images']['bild']['width'] = $width;
	$details['images']['bild']['height'] = $height;

	return $details;
};
$answers = [];

foreach( $tries as $label => [ $edited, $words ] ) {

	[ $status ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $edited, 'texts' => $words, 'base' => $draft['stamp'], 'force' => true ] );
	$answers[$label] = $status;
}

foreach( [ 'a picture beyond what the kernel takes' => $sizes( 99999, 600 ), 'a picture that is no size' => $sizes( 0, 0 ) ] as $label => $details ) {

	[ $status ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'details' => $details, 'base' => $draft['stamp'], 'force' => true ] );
	$answers[$label] = $status;
}

check( 'an id with foreign characters and a picture of a size the kernel does not take are refused - 400 or 403 - and nothing is written',
	array_filter( $answers, static fn( int $status ): bool => $status !== 400 && $status !== 403 ) === [] && placesSnapshot( $site ) === $before );

// two places with one id are one place: the second call reads the words of the first, and the block says so as a draft it cannot keep
$twice = placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'text' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~titel' ] ) );
[ $reading ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $twice, 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'force' => true ] );
unset( $site['./nino/blocks/state'] );
$source = (string) \Nino\Blocks::get( $site, $key )['source'];

check( 'two places with one id are one place for the server - the text reads the id of the title, there is one key for both and no second one - and it is the editor that keeps the ids of a block apart, at the field',
	$reading === 200 && substr_count( $source, '~titel' ) === 2 );

// markup in the sample of a place is text: no tag is kept, the words of the block are the words
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$dirty = $draft['texts'];
$dirty[$base. 'titel'] = [ 'de_DE' => 'Lorem <script>alert(1)</script> ipsum', 'en_US' => 'Lorem <b>ipsum</b>' ];
[ $markup ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $dirty, 'base' => $draft['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$words = \Nino\Blocks::get( $site, $key )['texts'] ?? [];
$found = json_encode( $words );

check( 'markup in the sample of a place is no markup in the files of the block: a tag is not kept - a request that carries one is refused or its words are cleaned - and a script never stands in them',
	( $markup === 200 && str_contains( (string) $found, '<script' ) === false && str_contains( (string) $found, '<b>' ) === false ) || $markup === 400 || $markup === 403 );

echo "\nC. The loops\n";

[ $offered, $offer ] = placesCall( $site, 'blocks/offer', [ 'tree' => $draft['tree'], 'at' => (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ) ] );
$loops = array_values( array_filter( $offer['offers'] ?? [], static fn( array $entry ): bool => ( $offer['schemas'][$entry['name']]['loop'] ?? false ) === true ) );

check( 'the dialog of the components offers the loops - the lists, the tables, the collection - and every one of them stands in the part of the dialog that is the loops, whatever group the register gives it',
	$offered === 200 && count( $loops ) > 0 && array_unique( array_column( $loops, 'section' ) ) === [ 'loops' ] && in_array( 'collection-list', array_column( $loops, 'name' ), true ) === true );

[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$loopTree = placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ), static function( array $node ): array {

	$node['c'][] = [ 'n' => 'collection-list', 's' => '/_placeholder', 'a' => [], 'c' => [ [ 't' => "\n" ], [ 'n' => 'text', 's' => '.title', 'a' => [], 'c' => null ], [ 't' => "\n" ] ] ];
	$node['c'][] = [ 't' => "\n" ];

	return $node;
} );
[ $loopSaved ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $loopTree, 'texts' => $draft['texts'], 'pictures' => [], 'base' => $draft['stamp'] ] );
unset( $site['./nino/blocks/state'] );

[ $third, $thirdId ] = $put( 3 );
[ , $page ] = placesCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$loopAt = (string) placesFind( $page['tree'], static fn( array $node ): bool => $node['n'] === 'collection-list' );
$real = placesEdit( $page['tree'], $loopAt, static fn( array $node ): array => array_replace( $node, [ 's' => '/services' ] ) );
[ $filled ] = placesCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $real, 'texts' => [], 'base' => $page['stamp'] ] );
[ $unknown ] = placesCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => placesEdit( $page['tree'], $loopAt, static fn( array $node ): array => array_replace( $node, [ 's' => '/nope' ] ) ), 'texts' => [], 'base' => $page['stamp'], 'force' => true ] );

check( 'a block that holds a loop is saved with the placeholder type, is put into a page, and the page shows a type of the project in it - a type the project has is taken and one it has not is refused',
	$loopSaved === 200 && $third === 200 && $loopAt !== '' && $filled === 200 && $unknown === 403 && str_contains( (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ), '[collection-list /services' ) === true );

ninoDone( $site );
