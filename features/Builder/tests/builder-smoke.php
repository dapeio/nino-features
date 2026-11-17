<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-smoke.php	Contract test for the feature Builder - a page is a row of blocks, moved, copied or left out on a draft, saved in one step:
 *
 *											A. the feature: its files, the right and the words of every failure, and nothing glibc's in the source
 *											B. the pages: the list, what a page opens as, a template that is not read as it stands
 *											C. a save: a move, a copy with the ids of the rule and its words, a block taken out with its words, the last state, a restore
 *											D. the rule: what a person who may change the content cannot do - nothing is written then
 *											E. the stamp: a page changed meanwhile is a 409, force does not lift the rule, a failing step puts the state back
 *											F. the draft: the page drawn from it with the trace, for the session that asked and for nobody else
 *											G. rights: every action wants the right and the token of the session, and a request that asks for nothing is not looked at
 *											H. insert block: the list of the dialog, every block put in with ids by the rule and its words and pictures, the rule that holds a new
 *											   block to the block it is, the tile of the dialog, the blocks that are left out and the hint of a variant
 *											I. what is measured, which is printed and never held to a limit
											   and the suite of the script of the panel, where node is
 *
 *									Runs against one sandbox with the kernel's own Basis; the pages are the three of tests/fixtures/pages, copied in - the start
 *									page, the contact page with a form between its calls and a page that is free markup alone.
 *
 *	Usage: php features/Builder/tests/builder-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;
use Nino\Modules\Builder\Tree;

$feature	= dirname( __DIR__ );
$numbers	= [];

/**
 *	One action of the API, as the workbench posts it
 *
 *	@param		array 		&$appData
 *	@param		string		$action
 *	@param		array			$data
 *
 *	@return 	array											[ status, body ]
 */
function builderCall( array &$appData, string $action, array $data = [] ): array {

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, $body ];
}

/** The file of a template in the sandbox */
function builderFile( array &$appData, string $template ): string {
	return ninoSandboxDir( $appData ). '/private/templates/'. $template. '.tpl';
}

/** The words of a key in every language, as the files have them */
function builderWords( array &$appData, string $key ): array {
	return \Nino\Text::values( $appData, dirname( $key, 2 ) )[$key] ?? [];
}

/** A snapshot of everything the Builder may write: the templates, the text files, config.php, the data folder - bytes and times */
function builderSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData ). '/private';
	$list = [];

	foreach( [ '/templates', '/text', '/data/builder' ] as $sub )
		foreach( (array) glob( $dir. $sub. '/*' ) as $file )
			if( is_file( (string) $file ) === true )
				$list[$sub. '/'. basename( (string) $file )] = hash_file( 'sha256', (string) $file ). filemtime( (string) $file );

	$list['config'] = is_file( $dir. '/config.php' ) === true ? hash_file( 'sha256', $dir. '/config.php' ) : '';

	return $list;
}

/**
 *	A block put in as the panel does it: the tree of the block with its ids made free - its own id first, then the first number behind it that nobody has - laid
 *	out as the server wrote it, and the texts of the block in each language under the keys of the new ids
 *
 *	@param		array 		&$appData
 *	@param		array			$page					[ 'tree', 'texts' => key => language => text, 'slots' => the uris of the slots ] as the page is now
 *	@param		string		$key
 *	@param		int				$at						Where in the top level of the tree
 *	@param		string|null	$id					The id of the root, for one that is not the rule's
 *
 *	@return 	array											'tree', 'texts' (what the block brings), 'inserted' (id => key), 'id', 'block'
 */
function builderInsert( array &$appData, array $page, string $key, int $at, ?string $id = null ): array {

	[ , $block ] = builderCall( $appData, 'builder/block', [ 'key' => $key ] );
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

/**
 *	A block of the page copied as the panel does it: the first free number behind the base of its id, the ids inside begin with it, the words of every id of the block
 *	under the new ids and a slot for each picture made from the original's
 *
 *	@param		array 		&$appData
 *	@param		array			$open					As builder/open answers
 *	@param		string		$id						The id of the block
 *
 *	@return 	array											'tree', 'texts' (what the copy has), 'slots' (new uri => uri), 'id'
 */
function builderCopy( array &$appData, array $open, string $id ): array {

	$registry = \Nino\Components::components( $appData );
	$at = builderAt( $open['tree'], $id );
	$taken = Tree::taken( $open['tree'], $registry, array_merge( array_keys( $open['texts'] ), $open['slots'] ) );
	$base = (string) preg_replace( '/-\d+$/', '', $id );

	for( $count = 2; in_array( $base. '-'. $count, $taken, true ) === true; $count++ );

	$now = $base. '-'. $count;
	$map = [];
	$walk = static function( array $nodes ) use ( &$walk, $registry, $id, $now, &$map ): array {

		foreach( $nodes as $index => $node ) {

			$name = isset( $node['n'] ) === true && isset( $registry[$node['n']] ) === true ? Tree::idAttribute( $registry[$node['n']] ) : '';

			if( $name !== '' && isset( $node['a'][$name] ) === true ) {
				$map[$node['a'][$name]] = $node['a'][$name] === $id ? $now : Tree::inner( $node['a'][$name], $id, $now );
				$nodes[$index]['a'][$name] = $map[$node['a'][$name]];
			}

			if( is_array( $node['c'] ?? null ) === true )
				$nodes[$index]['c'] = $walk( $node['c'] );
		}

		return $nodes;
	};

	$tree = $open['tree'];
	array_splice( $tree, $at + 1, 0, [ [ 't' => "\n" ], $walk( [ $open['tree'][$at] ] )[0] ] );
	$texts = [];
	$slots = [];

	foreach( $open['texts'] as $key => $values )
		if( isset( $map[explode( '/', $key )[3]] ) === true )
			$texts['/template/page-home/'. $map[explode( '/', $key )[3]]. '/'. explode( '/', $key )[4]] = $values;

	foreach( $open['slots'] as $uri )
		if( isset( $map[explode( '/', $uri )[3]] ) === true )
			$slots['/template/page-home/'. $map[explode( '/', $uri )[3]]. '/'. explode( '/', $uri )[4]] = $uri;

	return [ 'tree' => $tree, 'texts' => $texts, 'slots' => $slots, 'id' => $now ];
}

/** The top-level index of the first block with an id, in a tree */
function builderAt( array $tree, string $id ): int {

	foreach( $tree as $at => $node )
		if( ( $node['a']['id'] ?? null ) === $id )
			return $at;

	return -1;
}

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The feature\n";

$classes = [ 'Builder', 'Admin/Admin', 'Tree/Tree', 'Store/Store', 'Pages/Pages' ];
$missing = array_filter( array_merge( $classes, [ 'feature', 'assets/admin', 'text/de_DE', 'text/en_US', 'templates/panel' ] ), static fn( string $file ): bool => glob( $feature. '/'. $file. '.*' ) === [] || glob( $feature. '/'. $file. '.*' ) === false );

check( 'the files of the feature are there: the module, the API, the tree, the store, the pages, the panel and its words', $missing === [] );

$manifest = require $feature. '/feature.php';

check( 'the manifest is the one of a feature that is written for the kernel of Nino 2, with the six sections of its manual', $manifest['key'] === 'builder' && $manifest['nino'] === '^2.0' && array_keys( $manifest['manual'] ) === [ 'shortcodes', 'markup', 'routes', 'panel', 'callbacks', 'install' ] );
check( 'the right is /_admin/builder/manage, and the manual of the feature names the panel', \Nino\Modules\Builder::PERM === '/_admin/builder/manage' && str_contains( $manifest['manual']['panel']['Builder']['en_US'], '/_admin/builder/manage' ) === true );

$words = [ 'de_DE' => require $feature. '/text/de_DE.php', 'en_US' => require $feature. '/text/en_US.php' ];
$codes = [ 'unknown', 'unreadable', 'tree', 'block', 'refused_free', 'refused_block', 'refused_ids', 'refused_copy', 'refused_id', 'refused_frame', 'fixed', 'texts', 'key', 'locale', 'text', 'preview', 'changed', 'write', 'nothing' ];

check( 'every failure code the API answers has its sentence in both interface languages, and the two files have the same keys',
	array_filter( $codes, static fn( string $code ): bool => isset( $words['de_DE']['[[/_admin/error/builder_'. $code. ']]'], $words['en_US']['[[/_admin/error/builder_'. $code. ']]'] ) === false ) === [] && array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) );

// Nino runs on musl on the server of its owner: what only the glibc knows is not used
$source = '';

foreach( [ 'Builder.php', 'Admin/Admin.php', 'Tree/Tree.php', 'Store/Store.php', 'Pages/Pages.php' ] as $file )
	$source .= (string) file_get_contents( $feature. '/'. $file );

check( 'nothing of the glibc alone is in the source: no GLOB_BRACE, no strptime(), no setlocale(), no //TRANSLIT', preg_match( '#GLOB_BRACE|strptime\(|setlocale\(|//TRANSLIT#', $source ) === 0 );
check( 'the panel is small: its script and its stylesheet stay within 150 KB and 15 KB', filesize( $feature. '/assets/admin.js' ) <= 150 * 1024 && filesize( $feature. '/assets/admin.css' ) <= 15 * 1024 );

// ---------------------------------------------------------------------------------------------------------------------------------
// the one site of the suite
$site = ninoSandbox( 'builder' );
$sandbox = ninoSandboxDir( $site );

$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Builder' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

foreach( [ 'page-home', 'page-contact', 'page-legal-imprint' ] as $name )
	\Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/'. $name. '.tpl' ) );

$home = [ '/template/page-home/hero/title' => 'Willkommen', '/template/page-home/hero/subtitle' => 'Dein erster Eindruck', '/template/page-home/start/title' => 'Was Du findest', '/template/page-home/start/text' => 'Karten', '/template/page-home/start-1/title' => 'Eins', '/template/page-home/start-1/text' => 'Erstens', '/template/page-home/start-2/title' => 'Zwei', '/template/page-home/start-2/text' => 'Zweitens', '/template/page-home/start-3/title' => 'Drei', '/template/page-home/start-3/text' => 'Drittens' ];

foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', array_combine( array_map( static fn( string $key ): string => '[['. $key. ']]', array_keys( $home ) ), array_map( static fn( string $text ): string => $locale === 'de_DE' ? $text : $text. ' (en)', $home ) ) );

$site['/nino/html/images']['/template/page-home/hero/background'] = [ 'label' => 'Hero', 'width' => 1920, 'height' => 1080, 'filename' => 'template/page-home/hero/background.svg' ];
\Nino\Images::restore( $site, 'template/page-home/hero/background.svg', '<svg xmlns="http://www.w3.org/2000/svg"><!-- the original --></svg>' );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/images' => $site['/nino/html/images'] ] );

ninoUser( $site, 'builder@example.com' );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The pages\n";

[ $status, $list ] = builderCall( $site, 'builder/list' );
$byTemplate = array_column( (array) ( $list['pages'] ?? [] ), null, 'template' );

check( 'the list names the pages the routes answer with a template of a page - there are none in a sandbox without routes - and the two frames that are files', $status === 200 && $byTemplate === [] && $list['frames'] === [] );

$site['/nino/http/routes'] = [ 'GET://' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];
[ , $list ] = builderCall( $site, 'builder/list' );

check( 'a route that answers with the template of a page is a page, once, with its path and the languages it is for', array_column( $list['pages'], 'template' ) === [ 'page-home', 'page-contact' ] && $list['pages'][0]['path'] === '/' && $list['pages'][1]['locales'] === [ 'de_DE', 'en_US' ] );

[ $status, $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = (array) ( $open['tree'] ?? [] );
$registry = \Nino\Components::components( $site );
$kinds = array_map( static fn( array $node ): string => Tree::kind( $node, $registry ), $tree );

check( 'a page opens as its tree: a note, the call of the head, a block, a block, the call of the foot - the blanks between are gaps', $status === 200 && array_values( array_filter( $kinds, static fn( string $kind ): bool => $kind !== 'gap' ) ) === [ 'free', 'call', 'block', 'block', 'call' ] );
check( 'it says where each call reads its text: the key of a ~name is the id of the nearest call above that has one, and only a key of the page is one that is written',
	count( array_filter( $open['sources'], static fn( array $entry ): bool => $entry['key'] === '/template/page-home/start-1/title' && $entry['editable'] === true ) ) === 1 && count( array_filter( $open['sources'], static fn( array $entry ): bool => $entry['kind'] === 'image' && $entry['editable'] === false ) ) === 1 );
check( 'and the words of the namespace in every language, the stamp, the register\'s label and id attribute of each block, and the slots of the page', $open['texts']['/template/page-home/hero/title'] === [ 'de_DE' => 'Willkommen', 'en_US' => 'Willkommen (en)' ] && strlen( $open['stamp'] ) === 64 && $open['known']['section-wrap']['id'] === 'id' && $open['slots'] === [ '/template/page-home/hero/background' ] );

// B6 and the roundtrip: open and save without a change write nothing at all
$before = builderSnapshot( $site );
[ $status, $same ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $open['texts'], 'base' => $open['stamp'] ] );

check( 'a page that is opened and saved without a change is written nowhere - not one byte, not one modification time, no last state', $status === 200 && $same['changed'] === false && builderSnapshot( $site ) === $before );

[ $status ] = builderCall( $site, 'builder/open', [ 'template' => '../config' ] );
[ $other ] = builderCall( $site, 'builder/open', [ 'template' => 'page-nothere' ] );
\Nino\Filesystem::putFileContent( $site, '/templates/mail-owner.tpl', "[text text=\"private\"]\n" );
$mail = [ builderCall( $site, 'builder/open', [ 'template' => 'mail-owner' ] )[0], builderCall( $site, 'builder/save', [ 'template' => 'mail-owner', 'tree' => [ [ 'n' => 'text', 's' => '', 'a' => [ 'text' => 'changed' ], 'c' => null ], [ 't' => "\n" ] ], 'texts' => [], 'force' => true ] )[0] ];
\Nino\Filesystem::putFileContent( $site, '/templates/page-broken.tpl', "[section-wrap id=\"x\"]\n" );
[ $broken, $why ] = builderCall( $site, 'builder/open', [ 'template' => 'page-broken' ] );

check( 'only a template of a page or a frame is opened or saved - a name that climbs, a page that is not there, a template that is no page - and one that is not written as it is read is left to the one who wrote it', $status === 404 && $other === 404 && $mail === [ 404, 404 ] && str_contains( (string) file_get_contents( builderFile( $site, 'mail-owner' ) ), 'private' ) && $broken === 409 && $why['code'] === 'builder_unreadable' );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. A save\n";

$moved = $tree;
$at = [ builderAt( $tree, 'hero' ), builderAt( $tree, 'start' ) ];
[ $moved[$at[0]], $moved[$at[1]] ] = [ $tree[$at[1]], $tree[$at[0]] ];

[ $status, $saved ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $moved, 'texts' => [ '/template/page-home/hero/title' => [ 'de_DE' => 'Brot <b>braucht</b> Zeit' ] ], 'base' => $open['stamp'] ] );
$file = (string) file_get_contents( builderFile( $site, 'page-home' ) );

check( 'a move and a text are saved in one step: the blocks changed their places, the text of the language of the view is the new one - cleaned by its format - and the other language kept its own',
	$status === 200 && $saved['changed'] === true && strpos( $file, 'id="start"' ) < strpos( $file, 'id="hero"' ) && builderWords( $site, '/template/page-home/hero/title' ) === [ 'de_DE' => 'Brot braucht Zeit', 'en_US' => 'Willkommen (en)' ] );

$last = Store::previous( $site, 'page-home' );

check( 'the state before is kept as the last state: the template as it was, the words of the namespace in every language and the slots of the page', $last !== null && $last['source'] === (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) && $last['texts']['/template/page-home/hero/title']['de_DE'] === 'Willkommen' && array_keys( $last['slots'] ) === [ '/template/page-home/hero/background' ] );

// a copy of the first block: the ids of the rule and the words of the copy
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];
$start = builderAt( $tree, 'start' );
$copy = $tree[$start];
$copy['a']['id'] = 'start-9';
$walk = static function( array $nodes ) use ( &$walk ): array {

	foreach( $nodes as $at => $node ) {

		if( isset( $node['a']['id'] ) === true && $node['a']['id'] !== 'start-9' )
			$nodes[$at]['a']['id'] = 'start-9-'. substr( $node['a']['id'], strlen( 'start-' ) );

		if( is_array( $node['c'] ?? null ) === true )
			$nodes[$at]['c'] = $walk( $node['c'] );
	}

	return $nodes;
};
$copy['c'] = $walk( $copy['c'] );
$with = $tree;
array_splice( $with, $start + 1, 0, [ [ 't' => "\n" ], $copy ] );
$texts = [];

foreach( [ 'start/title', 'start/text', 'start-1/title', 'start-1/text', 'start-2/title', 'start-2/text', 'start-3/title', 'start-3/text' ] as $name )
	$texts['/template/page-home/'. str_replace( 'start', 'start-9', explode( '/', $name )[0] ). '/'. explode( '/', $name )[1]] = $open['texts']['/template/page-home/'. $name];

$texts = array_combine( array_map( static fn( string $key ): string => str_replace( 'start-9-1', 'start-9-1', $key ), array_keys( $texts ) ), $texts );
[ $status, $made ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $with, 'texts' => $texts, 'base' => $open['stamp'] ] );

check( 'a copy whose ids begin with the id of its root - start-9 and start-9-1 for start and start-1 - is saved, and its words stand in every language: German and English', $status === 200 && ( builderWords( $site, '/template/page-home/start-9-1/title' )['en_US'] ?? '' ) === 'Eins (en)' && str_contains( (string) file_get_contents( builderFile( $site, 'page-home' ) ), 'id="start-9-3"' ) );

// a block taken out: its words go, and the rest is left
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$gone = $open['tree'];
$nine = builderAt( $gone, 'start-9' );
array_splice( $gone, $nine - 1, 2 );
[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $gone, 'texts' => [], 'base' => $open['stamp'] ] );

check( 'a block that is taken out takes its words with it - in every language - and the words of the others stay', $status === 200 && builderWords( $site, '/template/page-home/start-9-1/title' ) === [] && builderWords( $site, '/template/page-home/start-1/title' )['de_DE'] === 'Eins' );

// the last state is put back, and the state that was is the last one then
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$beforeRestore = (string) file_get_contents( builderFile( $site, 'page-home' ) );
$kept = Store::previous( $site, 'page-home' )['source'];
[ $status ] = builderCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $open['stamp'] ] );
$afterRestore = (string) file_get_contents( builderFile( $site, 'page-home' ) );

check( 'the last state comes back, and the state it replaced is the last one: a second restore swaps them again', $status === 200 && $afterRestore === $kept && Store::previous( $site, 'page-home' )['source'] === $beforeRestore );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. The rule\n";

\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];
$tries = [];

// what a person who may change the content might try: each is a tree of a kind the rule has a word for
$last = count( $tree ) - 1;
$script = $tree;
$script[2] = [ 't' => "\n<script>alert(1)</script>\n" ];
$tries['a script put between the blocks'] = [ $script, 'free' ];

$html = $tree;
array_splice( $html, $last - 1, 0, [ [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<img src=x onerror=alert(1)>' ] ] ], [ 't' => "\n" ] ] );
$tries['a new block of raw html'] = [ $html, 'block' ];

$attribute = $tree;
$attribute[builderAt( $tree, 'hero' )]['a']['ground'] = 'primary';
$tries['an attribute that is not a text'] = [ $attribute, 'block' ];

$source = $tree;
$source[builderAt( $tree, 'hero' )]['c'][1]['c'][1]['c'][1]['s'] = '/project/company/general/name';
$tries['a block that reads another source'] = [ $source, 'block' ];

$call = $tree;
array_splice( $call, $last - 1, 0, [ [ 'n' => 'template', 's' => '/templates/mail-owner', 'a' => [], 'c' => null ], [ 't' => "\n" ] ] );
$tries['a call without a schema'] = [ $call, 'free' ];

$twice = $tree;
$twice[builderAt( $tree, 'start' )]['a']['id'] = 'hero';
$tries['an id that stands twice'] = [ $twice, 'ids' ];

$across = $tree;
[ $across[1], $across[builderAt( $tree, 'hero' )] ] = [ $tree[builderAt( $tree, 'hero' )], $tree[1] ];
$tries['a block moved above the call of the head'] = [ $across, 'free' ];

$bad = $tree;
$bad[builderAt( $tree, 'hero' )]['c'][1]['c'][1]['c'][1]['a']['id'] = 'sneaky';
$tries['an id in a block that is none of the rule'] = [ $bad, 'block' ];

$snapshot = builderSnapshot( $site );
$answers = [];

foreach( $tries as $label => [ $draft, $why ] ) {

	$draft = Tree::strip( $draft );
	[ $status, $body ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $draft, 'texts' => [], 'base' => $open['stamp'], 'force' => true ] );
	$answers[$label] = [ $status, str_replace( 'builder_refused_', '', (string) ( $body['code'] ?? '' ) ) ];
}

check( 'the rule: '. count( $tries ). ' trees a person who may change the content would try - a script, a raw html block, an attribute, another source, a call, a doubled id, a block above the head, an id of its own - are each refused, with the force that skips the stamp, and nothing is written',
	array_filter( $answers, static fn( array $answer ): bool => $answer[0] !== 403 && $answer[0] !== 400 ) === [] && builderSnapshot( $site ) === $snapshot );
check( 'and each is refused for what it is: the free piece the rule keeps in its place, the block that is none of the old ones - a new one, an attribute, another source, another place, an id of its own - and the id that stands twice',
	array_map( static fn( array $answer ): string => (string) $answer[1], $answers ) === [ 'a script put between the blocks' => 'free', 'a new block of raw html' => 'free', 'an attribute that is not a text' => 'block', 'a block that reads another source' => 'block', 'a call without a schema' => 'free', 'an id that stands twice' => 'ids', 'a block moved above the call of the head' => 'block', 'an id in a block that is none of the rule' => 'block' ] );

// what a person is told is a sentence of the language, and neither a key nor a code stands in it
$sentence = static function( string $code ) use ( $words ): bool {

	foreach( $words as $file )
		if( preg_match( '#/template/|builder_|\(block\)#', (string) $file['[[/_admin/error/builder_'. $code. ']]'] ) === 1 )
			return false;

	return true;
};

check( 'what a person is told of a refusal is a sentence in both languages and no key, no code and no word of the rule stands in it', array_filter( $codes, static fn( string $code ): bool => $sentence( $code ) === false ) === [] );

// words: only the namespace of the page, only keys the page reads, only the languages of the site, nothing with a null byte
$bad = [
	'a key of another page'	=> [ '/template/page-contact/hero/title' => [ 'de_DE' => 'x' ] ],
	'a key of the project'	=> [ '/project/company/general/name' => [ 'de_DE' => 'x' ] ],
	'a key of a picture'		=> [ '/template/page-home/hero/background' => [ 'de_DE' => 'x' ] ],
	'a language of no site'	=> [ '/template/page-home/hero/title' => [ 'xx_XX' => 'x' ] ],
	'a null byte'						=> [ '/template/page-home/hero/title' => [ 'de_DE' => "a\0b" ] ],
	'a text that is none'		=> [ '/template/page-home/hero/title' => [ 'de_DE' => [ 'x' ] ] ],
];
$answers = [];

foreach( $bad as $label => $texts )
	$answers[$label] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $texts, 'base' => $open['stamp'], 'force' => true ] )[0];

check( 'the words: a key of another page or of the project, a key of a picture, a language the site does not have, a null byte and a text that is none are refused - and nothing is written', array_filter( $answers, static fn( int $status ): bool => $status !== 403 && $status !== 400 ) === [] && builderSnapshot( $site ) === $snapshot );

// text that is typed is text: markup and calls in it are made harmless by the format of the key, a fill of the page's own stays one
[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => [ '/template/page-home/hero/title' => [ 'de_DE' => '<script>x</script>[template /templates/mail-owner] [[/template/page-home/hero/subtitle]]' ] ], 'base' => $open['stamp'] ] );
$kept = builderWords( $site, '/template/page-home/hero/title' )['de_DE'] ?? '';

check( 'a text with a script, a call and a fill in it is kept as text: no tag, no bracket of a call, the fill of its own key a fill', $status === 200 && str_contains( $kept, '<' ) === false && str_contains( $kept, '[template' ) === false && str_contains( $kept, '[[/template/page-home/hero/subtitle]]' ) === true );

// the page of free markup and the page with a form between its calls
[ , $legal ] = builderCall( $site, 'builder/open', [ 'template' => 'page-legal-imprint' ] );
[ , $contact ] = builderCall( $site, 'builder/open', [ 'template' => 'page-contact' ] );
$formBlock = builderAt( $contact['tree'], 'details' );
$twin = $contact['tree'];
array_splice( $twin, $formBlock + 1, 0, [ [ 't' => "\n" ], $contact['tree'][$formBlock] ] );
$twin[$formBlock + 2]['a']['id'] = 'details-2';
$snapshot = builderSnapshot( $site );

check( 'a page that is free markup alone opens, and has nothing a person may move; a block that holds a form is moved and taken out and not copied - and the page is the same to the byte where nothing changed',
	count( array_filter( $legal['tree'], static fn( array $node ): bool => in_array( Tree::kind( $node, $registry ), [ 'block' ], true ) ) ) === 0
	&& builderCall( $site, 'builder/save', [ 'template' => 'page-contact', 'tree' => Tree::strip( $twin ), 'texts' => [], 'base' => $contact['stamp'], 'force' => true ] )[0] === 403
	&& builderCall( $site, 'builder/save', [ 'template' => 'page-contact', 'tree' => $contact['tree'], 'texts' => [], 'base' => $contact['stamp'] ] )[1]['changed'] === false && builderSnapshot( $site ) === $snapshot );

// the ids of a copy are the ones the rule makes: of the id of the original, with the next number, that nobody uses - and in a frame there is no copy
$base = $tree;
$ids = static function( string $root ) use ( $base ): array {

	$at = builderAt( $base, 'start' );
	$copy = $base[$at];
	$walk = static function( array $nodes ) use ( &$walk, $root ): array {

		foreach( $nodes as $index => $node ) {

			if( isset( $node['a']['id'] ) === true && $node['a']['id'] !== 'start' )
				$nodes[$index]['a']['id'] = $root. substr( $node['a']['id'], strlen( 'start' ) );

			if( is_array( $node['c'] ?? null ) === true )
				$nodes[$index]['c'] = $walk( $node['c'] );
		}

		return $nodes;
	};
	$copy['a']['id'] = $root;
	$copy['c'] = $walk( $copy['c'] );
	array_splice( $base, $at + 1, 0, [ [ 't' => "\n" ], $copy ] );

	return $base;
};
$answers = [];

foreach( [ 'a root that is an attribute' => 'x" onmouseover="alert(1)', 'a root of capitals' => 'Hero-Big', 'a renamed root' => 'neu', 'a root that is the id of the page' => 'hero', 'a root that is the id of another block' => 'start' ] as $label => $newRoot )
	$answers[$label] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => Tree::strip( $ids( $newRoot ) ), 'texts' => [], 'base' => $open['stamp'], 'force' => true ] )[0];

check( 'the id of a copy is the rule\'s: an id with quotes, one of capitals, a new name for the copy and an id the page has are each refused - nothing is written', array_unique( array_values( $answers ) ) === [ 403 ] || array_diff( array_unique( array_values( $answers ) ), [ 400, 403 ] ) === [] );

[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
check( 'and a copy that is made by the rule - start-9 for start - is taken', builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => Tree::strip( $ids( 'start-9' ) ), 'texts' => [], 'base' => $open['stamp'] ] )[0] === 200 );

\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', "[header-wrap id=\"header\"]\n\t[header-logo]\n[/header-wrap]\n" );
[ , $frame ] = builderCall( $site, 'builder/open', [ 'template' => 'frame-header' ] );
$renamed = $frame['tree'];
$renamed[0]['a']['id'] = 'header-2';
$doubled = $frame['tree'];
array_splice( $doubled, 1, 0, [ [ 't' => "\n" ], $frame['tree'][0] ] );
$snapshot = builderSnapshot( $site );

$none = [ [ 't' => "\n" ] ];
$two = Tree::read( $site, "[header-wrap id=\"header\"]\n[/header-wrap]\n[footer-wrap id=\"foot\"]\n[/footer-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', "[header-wrap id=\"header\"]\n[/header-wrap]\n[footer-wrap id=\"foot\"]\n[/footer-wrap]\n" );
[ , $two ] = builderCall( $site, 'builder/open', [ 'template' => 'frame-footer' ] );
$swap = array_values( array_filter( $two['tree'], static fn( array $node ): bool => isset( $node['n'] ) === true ) );
$order = $two['tree'];
[ $order[0], $order[2] ] = [ $two['tree'][2], $two['tree'][0] ];
$fewer = array_slice( $two['tree'], 0, 2 );
$snapshot = builderSnapshot( $site );
$answers = [ builderCall( $site, 'builder/save', [ 'template' => 'frame-header', 'tree' => $none, 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0], builderCall( $site, 'builder/save', [ 'template' => 'frame-footer', 'tree' => Tree::strip( $order ), 'texts' => [], 'base' => $two['stamp'], 'force' => true ] )[0], builderCall( $site, 'builder/save', [ 'template' => 'frame-footer', 'tree' => Tree::strip( $fewer ), 'texts' => [], 'base' => $two['stamp'], 'force' => true ] )[0] ];

check( 'a frame keeps its blocks: none, one fewer or in another order is refused, and nothing is written', $answers === [ 403, 403, 403 ] && count( $swap ) === 2 && builderSnapshot( $site ) === $snapshot );

check( 'a frame keeps its id and its blocks: a new id and a copy are refused, and nothing is written', builderCall( $site, 'builder/save', [ 'template' => 'frame-header', 'tree' => Tree::strip( $renamed ), 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0] === 403 && builderCall( $site, 'builder/save', [ 'template' => 'frame-header', 'tree' => Tree::strip( $doubled ), 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0] === 403 && builderSnapshot( $site ) === $snapshot );

// what holds free markup is not moved, copied or taken out in this version: the form of the contact page stays
[ , $contact ] = builderCall( $site, 'builder/open', [ 'template' => 'page-contact' ] );
$form = builderAt( $contact['tree'], 'details' );
$out = $contact['tree'];
array_splice( $out, $form - 1, 2 );
$swapped = $contact['tree'];
[ $swapped[builderAt( $contact['tree'], 'hero' )], $swapped[$form] ] = [ $contact['tree'][$form], $contact['tree'][builderAt( $contact['tree'], 'hero' )] ];
$snapshot = builderSnapshot( $site );
$answers = [];

foreach( [ 'taken out' => $out, 'moved' => $swapped ] as $label => $draft )
	$answers[$label] = builderCall( $site, 'builder/save', [ 'template' => 'page-contact', 'tree' => Tree::strip( $draft ), 'texts' => [], 'base' => $contact['stamp'], 'force' => true ] )[0];

check( 'a block that holds free markup - the form of the contact page - is not taken out and not moved, and a draft of it is refused as a save is: nothing is written', $answers === [ 'taken out' => 403, 'moved' => 403 ] && builderCall( $site, 'builder/preview', [ 'template' => 'page-contact', 'tree' => Tree::strip( $out ), 'texts' => [] ] )[0] === 403 && builderSnapshot( $site ) === $snapshot );

// a text that is too much of blanks is none: layout is a few lines
$blank = $tree;
$blank[2] = [ 't' => str_repeat( ' ', 900000 ) ];

check( 'blanks between the blocks are layout: 900 KB of them are no page', builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $blank, 'texts' => [], 'base' => $open['stamp'], 'force' => true ] )[0] === 400 );

// the slots of a copy: a copy of a block with a picture has a slot of its own, made from the original's, and nothing else is made
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];
$hero = builderAt( $tree, 'hero' );
$twin = $tree[$hero];
$twin['a']['id'] = 'hero-2';
$with = $tree;
array_splice( $with, $hero + 1, 0, [ [ 't' => "\n" ], $twin ] );
$was = $site['/nino/html/images']['/template/page-home/hero/background'];
[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $with, 'texts' => [], 'slots' => [ '/template/page-home/hero-2/background' => '/template/page-home/hero/background', '/template/page-contact/hero/background' => '/template/page-home/hero/background' ], 'base' => $open['stamp'] ] );
$denied = $status;
[ $status, $saved ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $with, 'texts' => [], 'slots' => [ '/template/page-home/hero-2/background' => '/template/page-home/hero/background' ], 'base' => $open['stamp'] ] );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] ?? [];

$copy = $config['/template/page-home/hero-2/background'] ?? [];
$bytesOf = static fn( string $file ): string|false => \Nino\Images::read( $site, $file );

check( 'a copy of a block with a picture gets a slot of its own with the size of the original, in the namespace of the copy and nowhere else; a slot of another page is refused',
	$denied === 403 && $status === 200 && array_diff_key( $copy, [ 'filename' => 1 ] ) === array_diff_key( $was, [ 'filename' => 1 ] ) && isset( $config['/template/page-contact/hero/background'] ) === false );
check( 'and a file of its own, made from the bytes of the original under the name of its slot: two files, one content - a new picture for the copy leaves the original\'s file where it is',
	( $copy['filename'] ?? '' ) === 'template/page-home/hero-2/background.svg' && $copy['filename'] !== $was['filename'] && $bytesOf( $copy['filename'] ) === $bytesOf( $was['filename'] ) && $bytesOf( $was['filename'] ) !== false
	&& ( \Nino\Images::delete( $site, $copy['filename'] ) || true ) && $bytesOf( $was['filename'] ) !== false );
\Nino\Images::restore( $site, 'template/page-home/hero-2/background.svg', (string) $bytesOf( $was['filename'] ) );

[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$without = $open['tree'];
array_splice( $without, builderAt( $without, 'hero-2' ) - 1, 2 );
builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $without, 'texts' => [], 'base' => $open['stamp'] ] );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] ?? [];

check( 'a copy that is taken out takes its slot with it, the original keeps its slot and its file, and the file of the copy stays while the last state holds its slot', isset( $config['/template/page-home/hero-2/background'] ) === false && isset( $config['/template/page-home/hero/background'] ) === true && $bytesOf( 'template/page-home/hero-2/background.svg' ) !== false && $bytesOf( $was['filename'] ) !== false );

// one save later the last state no longer has the slot: the file of the copy goes, the file of the original never
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $open['tree'], 'texts' => [ '/template/page-home/hero/title' => [ 'de_DE' => 'Noch ein Stand' ] ], 'base' => $open['stamp'] ] );

check( 'the file of a copy lives as long as its slot is in the page or in the last state, and the file of the original is never taken away by it', $bytesOf( 'template/page-home/hero-2/background.svg' ) === false && $bytesOf( $was['filename'] ) !== false );

// an id of a copy that the page has words for is no id: the words would be shown in the copy
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
\Nino\Text::put( $site, [ '/template/page-home/hero-5/title' => [ 'de_DE' => 'Alt' ] ] );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$heroAt = builderAt( $open['tree'], 'hero' );
$taken = $open['tree'];
$twin = $taken[$heroAt];
$twin['a']['id'] = 'hero-5';
array_splice( $taken, $heroAt + 1, 0, [ [ 't' => "\n" ], $twin ] );
$snapshot = builderSnapshot( $site );

check( 'an id of a copy that has words on the page - hero-5 - is refused as one that is taken: nothing is written', builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => Tree::strip( $taken ), 'texts' => [], 'base' => $open['stamp'] ] )[0] === 403 && builderSnapshot( $site ) === $snapshot );
\Nino\Text::put( $site, [], [ '/template/page-home/hero-5/title' ] );

// a slot of the page is not made new from another by a request: the slot that is there keeps what it has
\Nino\Images::putSlot( $site, '/template/page-home/start/background', [ 'label' => 'Other', 'width' => 10, 'height' => 10, 'filename' => null ] );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $open['tree'], 'texts' => [], 'slots' => [ '/template/page-home/hero/background' => '/template/page-home/start/background' ], 'base' => $open['stamp'] ] );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] ?? [];

check( 'a slot that is there is not written over by a request that names it as a copy', ( $config['/template/page-home/hero/background']['label'] ?? '' ) === 'Hero' );
\Nino\Images::dropSlot( $site, '/template/page-home/start/background' );

// the last state after a block was taken out: the block and its words in every language are back
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$gone = $open['tree'];
$start = builderAt( $gone, 'start' );
array_splice( $gone, $start - 1, 2 );
$wordsBefore = \Nino\Text::values( $site, '/template/page-home' );
[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $gone, 'texts' => [], 'base' => $open['stamp'] ] );
$empty = array_filter( \Nino\Text::values( $site, '/template/page-home' ), static fn( string $key ): bool => str_starts_with( $key, '/template/page-home/start' ), ARRAY_FILTER_USE_KEY ) === [];
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
[ $restored ] = builderCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $open['stamp'] ] );

check( 'a block of the page - not a copy - that was taken out and saved comes back with the last state: the template, and its words in German and English', $status === 200 && $empty === true && $restored === 200
	&& (string) file_get_contents( builderFile( $site, 'page-home' ) ) === (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) && \Nino\Text::values( $site, '/template/page-home' ) === $wordsBefore );

// the last state brings back the words and the slots of the page and no others, whatever the file of it says
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$moved = $open['tree'];
$pair = [ builderAt( $moved, 'hero' ), builderAt( $moved, 'start' ) ];
[ $moved[$pair[0]], $moved[$pair[1]] ] = [ $open['tree'][$pair[1]], $open['tree'][$pair[0]] ];
builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $moved, 'texts' => [], 'base' => $open['stamp'] ] );
$kept = Store::previous( $site, 'page-home' );
$kept['texts']['/template/page-contact/hero/title'] = [ 'de_DE' => 'FREMD' ];
$kept['texts']['/template/common/form/name'] = [ 'de_DE' => 'GEKAPERT' ];
$kept['slots']['/template/page-contact/hero/image'] = [ 'label' => 'x', 'width' => 10, 'height' => 10, 'filename' => null ];
\Nino\Filesystem::putFileContent( $site, Store::PREVIOUS. '/page-home.php', $kept );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
[ $restored ] = builderCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $open['stamp'] ] );
$after = \Nino\Text::values( $site, '/template/page-contact' ) + \Nino\Text::values( $site, '/template/common' );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] ?? [];

check( 'a last state that was changed by hand brings back the words and the slots of the namespace of its page and no other: no key of another page, none of the project, no slot of another page', $restored === 200 && isset( $after['/template/page-contact/hero/title'] ) === false && isset( $after['/template/common/form/name'] ) === false && isset( $config['/template/page-contact/hero/image'] ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The stamp\n";

\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];
$edit = [ '/template/page-home/hero/title' => [ 'de_DE' => 'Von diesem Tab' ] ];

// another tab, or the panel of the texts, writes a key of the page meanwhile
\Nino\Text::put( $site, [ '/template/page-home/start/title' => [ 'de_DE' => 'Von anderswo' ] ] );
$snapshot = builderSnapshot( $site );
[ $status, $body ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $edit, 'base' => $open['stamp'] ] );

check( 'a page whose words were changed meanwhile - in the panel of the texts - is a 409 with its code, and nothing is written', $status === 409 && $body['code'] === 'builder_changed' && builderSnapshot( $site ) === $snapshot );
check( 'a save that names no stamp has seen no page: it is a 409 as well', builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $edit ] )[0] === 409 );

[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $edit, 'base' => $open['stamp'], 'force' => true ] );

check( 'force writes over it - and the words of the other tab that the page did not change are as they are now', $status === 200 && builderWords( $site, '/template/page-home/hero/title' )['de_DE'] === 'Von diesem Tab' && builderWords( $site, '/template/page-home/start/title' )['de_DE'] === 'Von anderswo' );

// a step that fails puts the state back: the words are written, the template is not what the save saw
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$state = Store::state( $site, 'page-home' );
$target = $state;
$target['source'] = (string) str_replace( 'id="start"', 'id="begin"', $state['source'] );
$target['texts']['/template/page-home/hero/title']['de_DE'] = 'Nie gesehen';
$snapshot = builderSnapshot( $site );
$failed = Store::commit( $site, 'page-home', $state, $target, null, [], str_repeat( '0', 64 ) );
$now = Store::state( $site, 'page-home' );

check( 'a save whose last step fails - the template was written by somebody else after it was looked at - says which step, and the words, the slots and the template are what they were',
	$failed === 'changed' && $now === $state && Store::previous( $site, 'page-home' )['source'] === $state['source'] );

// the bytes: no file of the words is sorted or cleaned by being put back, and the last state is the one it was
$bytes = static function() use ( &$site ): array {

	$list = [];

	foreach( [ '/private/text/de_DE.php', '/private/text/en_US.php', '/private/config.php', '/private/templates/page-home.tpl', '/private/data/builder/previous/page-home.php' ] as $file )
		$list[$file] = (string) @file_get_contents( ninoSandboxDir( $site ). $file );

	return $list;
};
file_put_contents( ninoSandboxDir( $site ). '/private/text/en_US.php', str_replace( "'[[/template/page-home/start/text]]' => 'Karten (en)'", "'[[/template/page-home/start/text]]' => 'Karten roh'", (string) file_get_contents( ninoSandboxDir( $site ). '/private/text/en_US.php' ) ) );
\Nino\Text::put( $site, [ '/template/page-home/start/title' => [ 'de_DE' => 'Neu' ] ] );
file_put_contents( ninoSandboxDir( $site ). '/private/text/en_US.php', "<?php return array (\n  '[[/template/page-home/start/text]]' => 'Karten roh',\n  '[[/template/page-home/hero/title]]' => 'Title',\n);\n" );
$state = Store::state( $site, 'page-home' );
$target = $state;
$target['source'] = (string) str_replace( 'id="start"', 'id="begin"', $state['source'] );
$target['texts']['/template/page-home/hero/title']['en_US'] = 'Other';
$target['texts']['/template/page-home/start/text']['en_US'] = 'Karten neu';
$beforeBytes = $bytes();
$failed = Store::commit( $site, 'page-home', $state, $target, null, [], str_repeat( '0', 64 ) );

// a template that somebody else wrote after the save looked at it is theirs: the save did not write it and does not put anything over it
$state = Store::state( $site, 'page-home' );
$target = $state;
$target['source'] = (string) str_replace( 'id="start"', 'id="begin"', $state['source'] );
$theirs = $state['source']. "\n<!-- somebody else -->\n";
file_put_contents( builderFile( $site, 'page-home' ), $theirs );
$failed = Store::commit( $site, 'page-home', $state, $target, null, [], hash( 'sha256', $state['source'] ) );
$sameTheirs = (string) file_get_contents( builderFile( $site, 'page-home' ) ) === $theirs;
file_put_contents( builderFile( $site, 'page-home' ), $state['source'] );

check( 'a save that finds the template written by somebody else puts nothing over it: their template is what stands', $failed === 'changed' && $sameTheirs === true );

// the put-back writes a file only where it still holds what this save left in it: the lock is held, and what somebody wrote since stays
$put = new ReflectionMethod( Store::class, '_back' );
$file = '/templates/page-home.tpl';
$real = builderFile( $site, 'page-home' );
file_put_contents( $real, 'theirs' );
$put->invokeArgs( null, [ &$site, [ $file => 'old' ], [], [] ] );
$untouched = (string) file_get_contents( $real ) === 'theirs';
$put->invokeArgs( null, [ &$site, [ $file => 'old' ], [ $file => 'other' ], [] ] );
$theirs = (string) file_get_contents( $real ) === 'theirs';
$put->invokeArgs( null, [ &$site, [ $file => 'old' ], [ $file => 'theirs' ], [] ] );
$mine = (string) file_get_contents( $real ) === 'old';
file_put_contents( $real, $state['source'] );

check( 'the put-back leaves a file that this save did not write, and one that somebody else wrote after it did, and puts back one that still holds what it left', $untouched === true && $theirs === true && $mine === true );

check( 'and the files are the same to the byte afterwards - the words that were not changed are not sorted or cleaned, the last state is the one that was before the save', $failed === 'changed' && $bytes() === $beforeBytes );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. The draft\n";

[ $status, $draft ] = builderCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => [ '/template/page-home/hero/title' => [ 'de_DE' => 'Nur im Entwurf' ] ] ] );
$snapshot = builderSnapshot( $site );

check( 'a draft is a token and the query that names it, for how long it holds, and where each call of it reads - and the template and the words are not written', $status === 200 && preg_match( \Nino\Preview::TOKEN, $draft['token'] ) === 1 && $draft['query'] === \Nino\Preview::QUERY. '='. $draft['token'] && $draft['ttl'] === \Nino\Preview::TTL && $draft['sources'] !== [] && str_contains( (string) file_get_contents( builderFile( $site, 'page-home' ) ), 'Nur im Entwurf' ) === false );
check( 'a draft is held to the same rule as a save: a tree that is not the old page is refused before a token is given', builderCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => Tree::strip( $script ), 'texts' => [] ] )[0] === 403 );

/**
 *	The same project as another request: the files and the account stay, everything a request builds is built again
 *
 *	@param		array			$old
 *
 *	@return 	array
 */
function builderReboot( array $old ): array {

	$new = [ './nino/uid' => $old['./nino/uid'] ];
	\Nino\AppData::prepare( $new );

	foreach( [ './nino/filesystem/path', './nino/filesystem/configpath', './nino/filesystem/contentpath', './nino/filesystem/publicpath', './nino/locales/current', '/nino/locales/native', '/nino/locales/available', '/nino/dir', '/nino/install/texts', './nino/test/sandbox', '/nino/auth/user', './nino/auth/current', '/nino/http/routes' ] as $key )
		if( isset( $old[$key] ) === true )
			$new[$key] = $old[$key];

	$new['/nino/html/assets'] = [];
	$new['/nino/modules'] = $old['/nino/modules'];
	\Nino\Html::init( $new );
	\Nino\Features::registerComponents( $new );
	\Nino\Modules::callModules( $new, 'init' );

	return $new;
}

/** The page of a route as one request draws it: a request of its own - the module\'s first stage - and the template of the route */
$draw = static function( array &$appData, string $query ): string {

	$request = builderReboot( $appData );
	ninoVisit( $request, '/?'. $query );

	return \Nino\Html::renderHtml( $request, '[template /templates/page-home]' ). ( \Nino\Preview::active( $request ) === true ? '<!--active-->' : '' );
};

$drawn = $draw( $site, $draft['query'] );

check( 'the session that holds the token has the page drawn from the draft: its words, the trace round each call and round the value of a source, and no stored tree or file touched', str_contains( $drawn, 'Nur im Entwurf' ) && str_contains( $drawn, '<!--nino:n page-home:' ) && str_contains( $drawn, '<!--nino:s ~' ) && builderSnapshot( $site ) === $snapshot );

// a text that is emptied draws what the website would draw for it: the text of the next language - and a key none has a text for draws nothing
$words = \Nino\Text::values( $site, '/template/page-home' )['/template/page-home/hero/title'];
[ , $emptied ] = builderCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => [ '/template/page-home/hero/title' => [ 'de_DE' => '', 'en_US' => 'Fallback text' ] ] ] );
$shown = $draw( $site, $emptied['query'] );

check( 'a text that is emptied in the draft is drawn as the next language has it - and is not drawn as nothing', str_contains( $shown, 'Fallback text' ) === true && str_contains( $shown, '<!--nino:s ~title-->Fallback text' ) === true && $words !== [] );

// another account - with the right, and without - and nobody: the page as it is
ninoUser( $site, 'second@example.com' );
$public = [ $draw( $site, $draft['query'] ) ];
ninoUser( $site, 'plain@example.com', [ '/_admin/text/manage' ] );
$public[] = $draw( $site, $draft['query'] );
\Nino\Auth::logoutUser( $site );
$public[] = $draw( $site, $draft['query'] );
\Nino\Auth::loginUser( $site, 'builder@example.com', 'correct horse battery staple' );
$public[] = $draw( $site, 'nino-preview=0123456789abcdef0123456789abcdef' );
$plain = $draw( $site, '' );

check( 'the token of the draft shows it to nobody else: another account that has the right, one that has not, nobody, and a token that was never given each get the page as it is - no trace, no draft',
	array_filter( $public, static fn( string $page ): bool => str_contains( $page, 'Nur im Entwurf' ) === true || str_contains( $page, '<!--nino:' ) === true ) === [] && str_contains( $plain, '<!--nino:' ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nG. Rights\n";

$every = [
	'builder/list'		=> [],
	'builder/open'		=> [ 'template' => 'page-home' ],
	'builder/preview'	=> [ 'template' => 'page-home', 'tree' => $tree, 'texts' => [] ],
	'builder/save'		=> [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $edit, 'base' => 'x', 'force' => true ],
	'builder/restore'	=> [ 'template' => 'page-home', 'base' => 'x', 'force' => true ],
	'builder/blocks'	=> [],
	'builder/block'		=> [ 'key' => 'hero-full' ],
	'builder/tile'		=> [ 'template' => 'page-home', 'key' => 'hero-full' ],
];

ninoUser( $site, 'editor@example.com', [ '/_admin/elements/manage', '/_admin/text/manage' ] );
$snapshot = builderSnapshot( $site );
$answers = [];

foreach( $every as $action => $data )
	$answers[$action] = builderCall( $site, $action, $data )[0];

check( 'an account without the right gets 403 on every one of the eight actions - the Editor of the starter site has not - and nothing is read or written', array_unique( array_values( $answers ) ) === [ 403 ] && builderSnapshot( $site ) === $snapshot );

\Nino\Auth::logoutUser( $site );
$answers = [];

foreach( $every as $action => $data )
	$answers[$action] = builderCall( $site, $action, $data )[0];

check( 'and nobody signed in gets none of them: 401 or 403, and nothing is written', array_filter( $answers, static fn( int $status ): bool => $status !== 401 && $status !== 403 ) === [] && builderSnapshot( $site ) === $snapshot );

ninoUser( $site, 'scoped@example.com', [ '/_admin/builder/manage' ] );
check( 'the right is /_admin/builder/manage and nothing wider has to be held', builderCall( $site, 'builder/list' )[0] === 200 && builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] )[0] === 200 );

$snapshot = builderSnapshot( $site );

// the token of the session: a request the csrf guard refused is answered with its 403 and does nothing
$answers = [];

foreach( $every as $action => $data ) {
	$_POST['data'] = json_encode( $data );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$answers[$action] = $request['/nino/http/response']['statusCode'];
}

check( 'every action is reached through the workbench, whose token check comes first: a request it refused is not answered by any of them, and nothing is written', array_unique( array_values( $answers ) ) === [ 403 ] && builderSnapshot( $site ) === $snapshot && array_diff( array_keys( $every ), array_keys( \Nino\Modules\Builder\Admin::actions() ) ) === [] );

$cheap = builderReboot( $site );
ninoVisit( $cheap, '/?other=1' );

check( 'a request that carries no query of a preview is not looked at: no draft held, no trace', \Nino\Preview::active( $cheap ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nH. Insert block\n";

\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
\Nino\Auth::loginUser( $site, 'builder@example.com', 'correct horse battery staple' );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$registry = \Nino\Components::components( $site );
$page = [ 'tree' => $open['tree'], 'texts' => $open['texts'], 'slots' => $open['slots'] ];
$offered = array_filter( \Nino\Blocks::all( $site ), static fn( array $block ): bool => $block['wrap'] === 'section-wrap' );

[ $status, $dialog ] = builderCall( $site, 'builder/blocks' );

check( 'the dialog lists each block that the kernel offers for a page, in its order - the frames are none of them -, each with its origin, and every category of it is one the dialog has',
	$status === 200 && array_column( $dialog['blocks'], 'key' ) === array_keys( $offered ) && in_array( 'frame', $dialog['categories'], true ) === false
	&& array_diff( array_column( $dialog['blocks'], 'category' ), $dialog['categories'] ) === [] && array_unique( array_column( array_column( $dialog['blocks'], 'origin' ), 'kind' ) ) === [ 'basis' ] );

// every block of the dialog is put in, one after the other, into one page: the ids of the rule, the words in each language, a slot of its own for each picture
$at = builderAt( $page['tree'], 'start' ) + 2;
$inserted = [];
$words = [];

foreach( array_keys( $offered ) as $key ) {

	$put = builderInsert( $site, $page + [ 'texts' => $page['texts'] + $words ], $key, $at );
	$page['tree'] = $put['tree'];
	$words += $put['texts'];
	$inserted += $put['inserted'];
	$at += 2;
}

$ids = array_keys( $inserted );
$snapshot = builderSnapshot( $site );
[ $status, $drafted ] = builderCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $page['tree'], 'texts' => $words, 'inserted' => $inserted ] );
$shown = $status === 200 ? $draw( $site, $drafted['query'] ) : '';
$heroTitle = '/template/page-home/'. $ids[0]. '/title';

check( 'a block that is put in is drawn in the draft before anything is saved - its words, the trace round it, the pictures of the kernel in the place of the files it has not yet - and no file is written for it',
	$status === 200 && str_contains( $shown, $words[$heroTitle]['de_DE'] ) === true && str_contains( $shown, '<!--nino:n page-home:' ) === true && str_contains( $shown, 'data:image/svg+xml' ) === true && builderSnapshot( $site ) === $snapshot );

[ $status, $saved ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $page['tree'], 'texts' => $words, 'inserted' => $inserted, 'base' => $open['stamp'] ] );
$file = (string) file_get_contents( builderFile( $site, 'page-home' ) );
$config = \Nino\Filesystem::getFileContent( $site, '/config.php', [] )['/nino/html/images'] ?? [];
$values = \Nino\Text::values( $site, '/template/page-home' );
$missing = [];

foreach( $words as $key => $byLocale )
	foreach( $byLocale as $locale => $text )
		// a text of a block that has paragraphs is kept in the form of its source, the words are the same
		if( trim( strip_tags( (string) ( $values[$key][$locale] ?? '' ) ) ) !== $text )
			$missing[] = $key. ' '. $locale;

$slots = [];

foreach( $inserted as $id => $key ) {

	$block = \Nino\Blocks::get( $site, $key );

	foreach( $block['slots'] as $slot )
		if( $slot['kind'] === 'image' ) {

			$uri = '/template/page-home/'. ( $slot['scope'] === '' ? $id : $id. '-'. $slot['scope'] ). '/'. substr( $slot['source'], 1 );
			$spec = $block['images'][$slot['name']];
			$made = $config[$uri] ?? [];
			$slots[$uri] = ( $made['width'] ?? 0 ) === $spec['width'] && ( $made['height'] ?? 0 ) === $spec['height'] && ( $made['filename'] ?? '' ) === ltrim( $uri, '/' ). '.svg' && \Nino\Images::read( $site, (string) ( $made['filename'] ?? '' ) ) === \Nino\Images::PLACEHOLDERS[$spec['placeholder']]['svg'];
		}
}

check( 'saved in one step, the blocks are in the template with the ids of the rule - the id of the block where the page has none, hero-2 behind it - and each has its words in German and in English',
	$status === 200 && $saved['changed'] === true && count( $ids ) === count( $offered ) && count( array_unique( $ids ) ) === count( $ids ) && $missing === []
	&& array_filter( $ids, static fn( string $id ): bool => str_contains( $file, 'id="'. $id. '"' ) === false ) === []
	&& preg_match( '/^[a-z]+(?:-\d+)?$/D', $ids[0] ) === 1 && str_contains( $file, 'id="hero-2"' ) === true && str_contains( $file, 'id="hero-2-' ) === false );
check( 'and each picture of them is a slot of the namespace of its id - the size and the file the block says, the file the picture of the kernel - and the page is the one that was kept as the last state',
	$slots !== [] && array_filter( $slots, static fn( bool $ok ): bool => $ok === false ) === [] && Store::previous( $site, 'page-home' )['source'] === (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );

// a block that was saved is the page's: naming its id as a block that is put in makes none of its words a thing to write, and the last state comes back to a page that holds blocks
$link = '/template/page-home/'. $ids[0]. '/link';
\Nino\Text::put( $site, [ $link => [ 'de_DE' => 'page:/elsewhere' ] ] );
[ , $held ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$snapshot = builderSnapshot( $site );
[ $named ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $held['tree'], 'texts' => [ $link => [ 'de_DE' => $words[$link]['de_DE'] ] ], 'inserted' => [ $ids[0] => 'hero-full' ], 'base' => $held['stamp'], 'force' => true ] );
$linked = ( $held['texts'][$link]['de_DE'] ?? '' ) === 'page:/elsewhere';
[ $back ] = builderCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $held['stamp'] ] );

check( 'a block that is on the page is not a block that is put in whatever the request names: its link is not a word to write - nothing is written - and the last state comes back to a page that holds blocks of the kernel',
	$linked === true && $named === 403 && $back === 200 && (string) file_get_contents( builderFile( $site, 'page-home' ) ) === (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );

// a block with a link and paragraphs is put in, saved, copied and saved: the copy has the link of the original, the paragraphs stay
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$both = builderInsert( $site, [ 'tree' => $open['tree'], 'texts' => $open['texts'], 'slots' => $open['slots'] ], 'hero-full', builderAt( $open['tree'], 'start' ) + 2 );
$long = builderInsert( $site, [ 'tree' => $both['tree'], 'texts' => $open['texts'] + $both['texts'], 'slots' => $open['slots'] ], 'text-narrow', builderAt( $both['tree'], $both['id'] ) + 2 );
$words = $both['texts'] + $long['texts'];
$words['/template/page-home/'. $long['id']. '/text'] = [ 'de_DE' => '<p>Erster Absatz</p><p>Zweiter <strong>Absatz</strong></p>' ];
[ $first ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $long['tree'], 'texts' => $words, 'inserted' => $both['inserted'] + $long['inserted'], 'base' => $open['stamp'] ] );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$copy = builderCopy( $site, $open, $both['id'] );
[ $second ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $copy['tree'], 'texts' => $copy['texts'], 'slots' => $copy['slots'], 'base' => $open['stamp'] ] );
$kept = \Nino\Text::values( $site, '/template/page-home' );
$told = $kept['/template/page-home/'. $long['id']. '/text']['de_DE'] ?? '';

check( 'a block that was put in, saved and copied is saved again: its copy has the link and the words of the original, and the paragraphs a text was written in are kept the way its source keeps them',
	$first === 200 && $second === 200 && ( $kept['/template/page-home/'. $copy['id']. '/link'] ?? [] ) === $kept['/template/page-home/'. $both['id']. '/link'] && substr_count( $told, '<p>' ) === 2 && str_contains( $told, '<strong>' ) === true );

// the rule: a new block is the block of its key and nothing else - each way round it is refused, and nothing is written
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
\Nino\Text::put( $site, [ '/template/page-home/hero-5/title' => [ 'de_DE' => 'Alt' ] ] );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$page = [ 'tree' => $open['tree'], 'texts' => $open['texts'], 'slots' => $open['slots'] ];
$at = builderAt( $page['tree'], 'start' ) + 2;
$good = builderInsert( $site, $page, 'hero-full', $at );
$send = static fn( array $put, array $more = [] ): array => $more + [ 'template' => 'page-home', 'tree' => $put['tree'], 'texts' => $put['texts'], 'inserted' => $put['inserted'], 'base' => $open['stamp'], 'force' => true ];
$tries = [];

$smuggled = $good;
$smuggled['tree'][$at]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<script>alert(1)</script>' ] ] ];
$tries['another tree under the key of a block'] = [ $send( $smuggled ), 403, 'refused_free' ];

$changed = $good;
$changed['tree'][$at]['a']['ground'] = 'light';
$tries['an attribute of the block that is not the block\'s'] = [ $send( $changed ), 403, 'refused_block' ];

$edited = $send( $good );
$edited['texts']['/template/page-home/'. $good['id']. '/link'] = [ 'de_DE' => 'https://evil.example/phish' ];
$tries['a link target that the block does not bring'] = [ $edited, 403, 'key' ];

$leading = builderInsert( $site, $page, 'hero-full', builderAt( $page['tree'], 'hero' ) - 2 );
$tries['a block before the call of the header'] = [ $send( $leading ), 403, 'refused_block' ];

$trailing = builderInsert( $site, $page, 'hero-full', count( $page['tree'] ) );
$tries['a block after the call of the footer'] = [ $send( $trailing ), 403, 'refused_block' ];

$padded = builderInsert( $site, $page, 'hero-full', $at, 'hero-007' );
$tries['a number with a zero in front of it'] = [ $send( $padded ), 403, 'refused_id' ];

$tries['a key of no block'] = [ $send( $good, [ 'inserted' => [ $good['id'] => 'no-such-block' ] ] ), 403, 'block' ];
$tries['the key of a frame, which is no block of a page'] = [ $send( $good, [ 'inserted' => [ $good['id'] => 'header-line' ] ] ), 403, 'block' ];
$tries['a block that nobody said is a block'] = [ $send( $good, [ 'inserted' => [] ] ), 403, 'refused_block' ];

$renamed = builderInsert( $site, $page, 'hero-full', $at, 'neu' );
$tries['an id of its own for the block'] = [ $send( $renamed ), 403, 'refused_id' ];

$taken = builderInsert( $site, $page, 'hero-full', $at, 'hero-5' );
$tries['an id that has words on the page'] = [ $send( $taken ), 403, 'refused_id' ];

$old = builderInsert( $site, $page, 'hero-full', $at, 'hero' );
$tries['the id of a block that is on the page'] = [ $send( $old ), 403, 'refused_ids' ];

$inside = $page;
$inside['tree'][builderAt( $page['tree'], 'start' )]['c'][] = $good['tree'][$at];
$tries['a block inside a block'] = [ $send( [ 'tree' => $inside['tree'], 'texts' => [], 'inserted' => $good['inserted'] ] ), 403, 'refused_block' ];

$foreign = $send( $good );
$foreign['texts']['/template/page-contact/'. $good['id']. '/title'] = [ 'de_DE' => 'Fremd' ];
$tries['words in the namespace of another page'] = [ $foreign, 403, 'key' ];

$slotsOf = $send( $good, [ 'slots' => [ '/template/page-contact/'. $good['id']. '/background' => '/template/page-home/hero/background' ] ] );
$tries['a picture slot in the namespace of another page'] = [ $slotsOf, 403, 'key' ];

[ $frameStatus, $frameOpen ] = builderCall( $site, 'builder/open', [ 'template' => 'frame-footer' ] );
$snapshot = builderSnapshot( $site );
$answers = [];

foreach( $tries as $label => [ $data, $want, $why ] ) {

	[ $status, $body ] = builderCall( $site, 'builder/save', $data );
	$answers[$label] = $status === $want && str_ends_with( (string) ( $body['code'] ?? '' ), $why ) === true;
}

check( 'a new block is the block of its key and nothing else: another tree under a valid key, a key that is none or is a frame\'s, no word of it at all, an id that is not the rule\'s or has words or is a block\'s, a block inside a block and what is not of the namespace are each refused with their word - and nothing is written',
	array_filter( $answers, static fn( bool $ok ): bool => $ok === false ) === [] && builderSnapshot( $site ) === $snapshot );

// a block of the catalogue put in before a block of the same shape is the new one, and the one that was there stays what it was: the old ones are settled first
$twinAt = builderAt( $page['tree'], 'hero' );
$twin = builderInsert( $site, $page, 'hero-full', $twinAt, 'hero-7' );

check( 'a new block that stands before an old block of its shape is the new one - the old block is not taken for the copy - and it is saved',
	builderCall( $site, 'builder/save', $send( $twin, [ 'force' => false ] ) )[0] === 200 && preg_match( '/id="hero-7".*id="hero"/s', (string) file_get_contents( builderFile( $site, 'page-home' ) ) ) === 1 );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$snapshot = builderSnapshot( $site );

// in a frame no block is put in, and a frame is a page that has none to give
$frameTree = $frameOpen['tree'];
$frameBlock = builderInsert( $site, [ 'tree' => $frameTree, 'texts' => [], 'slots' => [] ], 'hero-full', 0, 'hero' );
[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'frame-footer', 'tree' => $frameBlock['tree'], 'texts' => $frameBlock['texts'], 'inserted' => $frameBlock['inserted'], 'base' => $frameOpen['stamp'], 'force' => true ] );

[ $frameKey ] = builderCall( $site, 'builder/block', [ 'key' => 'header-line' ] );

check( 'a frame takes no block, whatever the request says of it, and the block of a frame is none that is put in anywhere', $frameStatus === 200 && $frameKey === 404 && $status === 403 && builderSnapshot( $site ) === $snapshot );

// a word that no call of the page reads is not kept, and one block that is put in leaves the others as they are
\Nino\Text::put( $site, [], [ '/template/page-home/hero-5/title' ] );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$good = builderInsert( $site, [ 'tree' => $open['tree'], 'texts' => $open['texts'], 'slots' => $open['slots'] ], 'hero-full', builderAt( $open['tree'], 'start' ) + 2 );
$extra = $good['texts'] + [ '/template/page-home/'. $good['id']. '/nothing-reads-this' => [ 'de_DE' => 'x' ] ];
builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $good['tree'], 'texts' => $extra, 'inserted' => $good['inserted'], 'base' => $open['stamp'] ] );

check( 'only what the block reads is written under its id: a word that no call reads is left out', \Nino\Text::values( $site, '/template/page-home/'. $good['id'] )['/template/page-home/'. $good['id']. '/nothing-reads-this'] ?? null === null && isset( \Nino\Text::values( $site, '/template/page-home' )['/template/page-home/'. $good['id']. '/title'] ) === true );

// the tile: the page with the block in it, as a draft of its own that holds nothing the person sent, and no more of them than the kernel keeps
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$before = builderSnapshot( $site );
[ $status, $tile ] = builderCall( $site, 'builder/tile', [ 'template' => 'page-home', 'key' => 'text-narrow', 'tree' => [ [ 't' => '<script>alert(1)</script>' ] ], 'source' => '<script>alert(2)</script>', 'texts' => [ 'x' => 'y' ] ] );
$page = $status === 200 ? $draw( $site, $tile['query'] ) : '';
$narrow = \Nino\Blocks::get( $site, 'text-narrow' );

$asked = builderReboot( $site );
ninoVisit( $asked, '/?'. ( $status === 200 ? $tile['query'] : '' ) );
$silenced = in_array( [ \Nino\Modules\Builder::class, 'noScript' ], \Nino\Callbacks::registered( $asked, '/nino/shortcodes/assets/output/js' ), true ) === true;

check( 'the tile of a block is drawn by the website with the words of the block and nothing the request carried, without the trace and without the scripts of the site, and nothing is written for it but the draft',
	$status === 200 && str_contains( $page, $narrow['texts']['de_DE']['title'] ) === true && str_contains( $page, 'alert(' ) === false && str_contains( $page, '<!--nino:' ) === false && $silenced === true && builderSnapshot( $site ) === $before );

$request = builderReboot( $site );
$request['./nino/test/tile'] = true;
$mine = \Nino\Filesystem::path( $site, \Nino\Preview::DIR. '/'. \Nino\Modules\Builder\Library::KIND );

for( $count = 0; $count < \Nino\Modules\Builder\Library::TILES + 4; $count++ )
	builderCall( $site, 'builder/tile', [ 'template' => 'page-home', 'key' => array_keys( $offered )[$count % count( $offered )] ] );

[ $nothing ] = builderCall( $site, 'builder/tile', [ 'template' => 'page-home', 'key' => 'no-such-block' ] );
[ $aFrame ] = builderCall( $site, 'builder/tile', [ 'template' => 'frame-footer', 'key' => 'hero-full' ] );

check( 'a session holds at most the number of tiles the kernel is told, whatever was asked, and a block that is not offered or a frame has no tile',
	count( glob( $mine. '/*', GLOB_ONLYDIR ) ?: [] ) === \Nino\Modules\Builder\Library::TILES && $nothing === 404 && $aFrame === 404 );

ninoUser( $site, 'other@example.com', [ '/_admin/builder/manage' ] );
$others = $draw( $site, $tile['query'] );
\Nino\Auth::loginUser( $site, 'builder@example.com', 'correct horse battery staple' );

check( 'the tile of one session is the page as it is for another', str_contains( $others, $narrow['texts']['de_DE']['title'] ) === false );

// what the tile draws is what a save takes: the root of the block is the block of the kernel - laid out, with the pictures of the kernel in the place of the slots
$same = true;

foreach( array_keys( $offered ) as $key ) {

	$block = \Nino\Modules\Builder\Library::block( $site, $key );
	$draft = \Nino\Modules\Builder\Library::tile( $site, 'page-home', $key, 'de_DE' );
	$nodes = Tree::read( $site, (string) $draft['source'] );
	$pictures = [];

	foreach( \Nino\Modules\Builder\Library::places( $block, (string) $block['id'], 'page-home' ) as $place )
		if( $place['kind'] === 'image' )
			$pictures[$place['key']] = $block['images'][$place['name']]['placeholder'];

	$found = array_values( array_filter( $nodes, static fn( array $node ): bool => ( $node['a']['id'] ?? null ) === $block['id'] ) )[0] ?? null;
	$want = Tree::shown( $block['tree'], Tree::sources( $block['tree'], $registry, 'page-home' ), $pictures )[0];
	$same = $same && $found !== null && json_encode( $found ) === json_encode( $want ) && \Nino\Components::check( $site, [ $found ] ) === [] && str_contains( (string) $draft['source'], '[template' ) === false;
}

check( 'the tile of every block that is offered draws its block as it is put in - the same tree, laid out, with nothing against it by the register - so that it draws nothing a save would refuse, and nothing of the header and the footer of the site', $same === true );

// a block that is left out: a feature it needs is off - it is told with the name of the feature - and any other block that is refused is not told at all
$dir = ninoSandboxDir( $site ). '/private/blocks';
$write = static function( string $name, string $tpl, array $words = [ 'title' => 'Word' ] ) use ( $dir ): void {

	@mkdir( $dir. '/'. $name. '/text', 0777, true );
	file_put_contents( $dir. '/'. $name. '/block.json', json_encode( [ 'name' => [ 'de_DE' => 'Ein '. $name, 'en_US' => 'A '. $name ], 'description' => [ 'de_DE' => 'Beschreibung', 'en_US' => 'Description' ], 'category' => 'content', 'rank' => 5, 'images' => (object) [] ] ) );
	file_put_contents( $dir. '/'. $name. '/block.tpl', $tpl );

	foreach( [ 'de_DE', 'en_US' ] as $locale )
		file_put_contents( $dir. '/'. $name. '/text/'. $locale. '.json', json_encode( $words ) );
};
$write( 'needs-lists', "[section-wrap id=\"lists\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[section-title ~title]\n\t\t\t[collection-list]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]" );
$write( 'evil-link', "[section-wrap id=\"evil\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[button ~label href=\"~href\"]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]", [ 'label' => 'Go', 'href' => 'javascript:alert(1)' ] );
$write( 'sneaky', "[section-wrap id=\"sneaky\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[section-title ~title]\n\t\t\t<script>alert(1)</script>\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]" );
$write( 'has-band', "[section-wrap id=\"band\" variant=\"band\"]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[section-title ~title]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]" );
$ignored = ninoWarnings();
\Nino\Blocks::all( $site );
$site['./nino/blocks/state'] = null;
unset( $site['./nino/blocks/state'], $site['./nino/blocks/said'] );
[ , $dialog ] = builderCall( $site, 'builder/blocks' );
$entries = array_column( $dialog['blocks'], null, 'key' );
$collections = \Nino\Features::all( $site )['collections']['name'] ?? '';

check( 'a block that needs a feature that is off is in the dialog with the name of the feature and cannot be put in, and a block that was left out for any other reason is not in it at all',
	( $entries['needs-lists']['off'] ?? null ) === [ $collections ] && isset( $entries['sneaky'] ) === false
	&& builderCall( $site, 'builder/block', [ 'key' => 'needs-lists' ] )[0] === 404 && builderCall( $site, 'builder/tile', [ 'template' => 'page-home', 'key' => 'needs-lists' ] )[0] === 404 );

$put = builderInsert( $site, [ 'tree' => $open['tree'], 'texts' => [], 'slots' => [] ], 'hero-full', 1, 'lists' );
[ $status ] = builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $put['tree'], 'texts' => [], 'inserted' => [ 'lists' => 'needs-lists' ], 'base' => $open['stamp'], 'force' => true ] );

check( 'and it is not put in by naming its key either', $status === 403 );

// an address that runs a script or leaves the site is never written, even where the block brings it
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', (string) file_get_contents( __DIR__. '/fixtures/pages/page-home.tpl' ) );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$evil = builderInsert( $site, [ 'tree' => $open['tree'], 'texts' => $open['texts'], 'slots' => $open['slots'] ], 'evil-link', builderAt( $open['tree'], 'start' ) + 2 );
$snapshot = builderSnapshot( $site );

check( 'an address that runs a script is not written even where the block brings it - the block is listed, put in and refused with its words - and nothing is written',
	isset( $entries['evil-link'] ) === true && builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $evil['tree'], 'texts' => $evil['texts'], 'inserted' => $evil['inserted'], 'base' => $open['stamp'] ] )[0] === 403 && builderSnapshot( $site ) === $snapshot );

// the hint of a variant: the one the mix has not got, with the one it appears as - and none where the Themes are off
$band = \Nino\Blocks::all( $site )['has-band'] ?? [];
\Nino\Callbacks::registerCallback( $site, \Nino\Components::VARIANTS, static function( array &$appData, array $asked ): array {
	return $asked['name'] === 'section-wrap' ? [ 'name' => 'section-wrap', 'variants' => array_merge( $asked['variants'], [ 'soft' ] ), 'standard' => 'soft' ] : $asked;
} );
$said = \Nino\Modules\Builder\Library::hint( $site, $band, true );

check( 'a block that names a variant the active mix has not got says which one it appears as, and with the Themes off it says nothing', $band !== [] && $said === [ 'variant' => 'band', 'standard' => 'soft' ] && \Nino\Modules\Builder\Library::hint( $site, $band, false ) === null && array_key_exists( 'hint', $entries['has-band'] ) === true && $entries['has-band']['hint'] === null );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nI. What is measured\n";

\Nino\Auth::loginUser( $site, 'builder@example.com', 'correct horse battery staple' );
[ , $open ] = builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$t0 = hrtime( true );
builderCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$numbers['open'] = ( hrtime( true ) - $t0 ) / 1e6;
$t0 = hrtime( true );
$p = builderCall( $site, 'builder/preview', [ 'template' => 'page-home', 'tree' => $open['tree'], 'texts' => $open['texts'] ] )[1];
$numbers['preview'] = ( hrtime( true ) - $t0 ) / 1e6;
$t0 = hrtime( true );
$draw( $site, $p['query'] );
$numbers['draw'] = ( hrtime( true ) - $t0 ) / 1e6;
$t0 = hrtime( true );
builderCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $open['tree'], 'texts' => [ '/template/page-home/hero/title' => [ 'de_DE' => 'Zeit' ] ], 'base' => $open['stamp'] ] );
$numbers['save'] = ( hrtime( true ) - $t0 ) / 1e6;

foreach( $numbers as $name => $ms )
	echo sprintf( "  %-8s %.2f ms\n", $name, $ms );

check( 'the times are told, none is held to a limit: the machines the suite runs on differ', count( $numbers ) === 4 );

// The script of the panel has a suite of its own, which runs where node is
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' )
	echo "  --  - node is not available here: builder-js-smoke.js was NOT run\n";
else {

	$output = [];
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/builder-js-smoke.js' ). ' 2>&1', $output, $exit );
	if( $exit !== 0 )
		echo implode( "\n", array_slice( $output, 0, 12 ) ), "\n";

	check( 'builder-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $site );
