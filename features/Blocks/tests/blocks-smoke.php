<?php
declare(strict_types=1);

/**
 *	Nino features
 *	blocks-smoke.php	Contract test for the Blocks: the list of the blocks of a source, a block made empty or as the copy of another, opened as a sheet, saved over
 *										itself and under another name, duplicated, renamed and taken away - and what no request may do:
 *
 *										A. the feature: its files, its manifest with the Builder it needs, its right, its words in both languages and every word the panel asks for
 *										B. the right and the token: no action for an account without /_admin/blocks/manage, none without the token of the session
 *										C. the list: the blocks of the project, the theme tab only where there is a theme in use, the library to begin a block with
 *										D. a new block: empty or the copy of any block, the key made of the name, a key that is taken refused at the name
 *										E. the sheet: a block as the draft of the editor - the frame of the site round it, its words and its pictures in the namespace of a page - and the draft
 *										   that is drawn of it
 *										F. what is written: save over the block, save as, duplicate, rename and remove - held to the rule of the kernel, to the stamp the sheet was opened with,
 *										   and only ever the blocks of the project
 *										G. what the panel is built of: the editor of the Builder and nothing of its own
 *
 *										One sandbox with the kernel's Basis and the collections; the blocks of the project are written below private/blocks/ of it.
 *
 *	Usage: php features/Blocks/tests/blocks-smoke.php
 *				 NINO_ROOT=../nino php features/Blocks/tests/blocks-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Blocks\Sheet;
use Nino\Modules\Builder\Tree;

$feature = dirname( __DIR__ );

/** One action of the API, as the workbench posts it: [ status, body ] */
function blocksCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, $body ];
}

/** What the blocks of the project are, by name: the files below private/blocks/ and the pictures, for "nothing is written" */
function blocksSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/blocks', 'private/templates', 'private/text', 'public/images' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

/** The position of the first node a test says yes to, depth first, or null */
function blocksFind( array $nodes, callable $test, string $prefix = '' ): ?string {

	foreach( $nodes as $at => $node ) {

		if( isset( $node['n'] ) === false )
			continue;

		if( $test( $node ) === true )
			return $prefix. $at;

		if( is_array( $node['c'] ?? null ) === true && ( $found = blocksFind( $node['c'], $test, $prefix. $at. '.' ) ) !== null )
			return $found;
	}

	return null;
}

function blocksEdit( array $nodes, string $position, callable $change ): array {

	$parts = explode( '.', $position );
	$index = (int) array_shift( $parts );

	if( $parts === [] )
		$nodes[$index] = $change( $nodes[$index] );
	else
		$nodes[$index]['c'] = blocksEdit( $nodes[$index]['c'], implode( '.', $parts ), $change );

	return $nodes;
}

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The feature\n";

$missing = array_filter( [ 'Blocks.php', 'feature.php', 'Admin/Admin.php', 'Sheet/Sheet.php', 'assets/admin.js', 'assets/admin.css', 'text/de_DE.php', 'text/en_US.php', 'templates/panel.tpl', 'templates/place.tpl' ], static fn( string $file ): bool => is_file( $feature. '/'. $file ) === false );

check( 'the files of the feature are there: the module, the API, the sheet, the panel with its stylesheet and its words, the template of the panel', $missing === [] );

$manifest = require $feature. '/feature.php';

check( 'the manifest is the one of a feature for the kernel of Nino 2, version 2.0.0, with the six sections of its manual - and it needs the Builder, whose editor it works with',
	$manifest['key'] === 'blocks' && $manifest['nino'] === '^2.0' && $manifest['version'] === '2.0.0' && $manifest['requires'] === [ 'builder' ] && array_keys( $manifest['manual'] ) === [ 'shortcodes', 'markup', 'routes', 'panel', 'callbacks', 'install' ]
	&& str_contains( $manifest['manual']['panel']['Blocks']['en_US'], '/_admin/blocks/manage' ) === true && \Nino\Modules\Blocks::PERM === '/_admin/blocks/manage' );

$words = [ 'de_DE' => require $feature. '/text/de_DE.php', 'en_US' => require $feature. '/text/en_US.php' ];
$script = (string) file_get_contents( $feature. '/assets/admin.js' );

preg_match_all( "/word\\(\\s*'([a-z][a-z\\/-]*)'/", $script, $asked );
$asked = array_unique( $asked[1] );
$dynamic = [ 'cat/intro', 'cat/content', 'cat/dynamic', 'cat/trust', 'cat/offer', 'cat/action', 'cat/contact', 'dup/title', 'dup/title-theme', 'dup/submit', 'dup/submit-theme', 'msg/renamed', 'msg/duplicated', 'error/rename', 'error/duplicate', 'details/ph-landscape', 'details/ph-mark', 'error/load', 'frames/header', 'frames/footer' ];
$lacking = array_filter( array_merge( $asked, $dynamic ), static fn( string $key ): bool => $key !== 'cat/' && $key !== 'frames/' && $key !== 'details/ph-' && isset( $words['de_DE']['[[/_admin/blocks/'. $key. ']]'], $words['en_US']['[[/_admin/blocks/'. $key. ']]'] ) === false );
$sentence = static fn( string $code ): bool => isset( $words['de_DE']['[[/_admin/error/'. $code. ']]'], $words['en_US']['[[/_admin/error/'. $code. ']]'] );
$codes = [ 'blocks_language', 'blocks_shell', 'blocks_unknown', 'blocks_name', 'blocks_category', 'blocks_taken', 'blocks_invalid', 'blocks_refused', 'blocks_tree', 'blocks_place', 'blocks_preview', 'blocks_write', 'blocks_key', 'blocks_block', 'blocks_id', 'blocks_locked', 'blocks_html', 'blocks_foreign', 'blocks_loop', 'blocks_frame', 'blocks_address', 'blocks_picture', 'blocks_empty', 'blocks_long' ];

check( 'every word the script asks for and every failure code the API answers has its sentence in both interface languages, and the two files have the same keys',
	$lacking === [] && array_filter( $codes, static fn( string $code ): bool => $sentence( $code ) === false ) === [] && array_keys( $words['de_DE'] ) === array_keys( $words['en_US'] ) );

$said = static function( array $words ): array { return array_filter( $words, static fn( string $text ): bool => preg_match( '/projekt|project/i', $text ) === 1 ); };

check( 'the blocks of the owner are "Own blocks" - "Eigene Blocks" - in every word of the panel:																																																																																																	no sentence of either language says "project" or "Projekt": the owner has no project, he has blocks of his own',
																																																																																																		$said( $words['de_DE'] ) === [] && $said( $words['en_US'] ) === [] && $words['de_DE']['[[/_admin/blocks/tab/project]]'] === 'Eigene Blocks' && $words['en_US']['[[/_admin/blocks/tab/project]]'] === 'Own blocks'
	&& isset( $words['de_DE']['[[/_admin/blocks/theme/note]]'], $words['de_DE']['[[/_admin/blocks/saveas/message]]'] ) === false );

$style = (string) file_get_contents( $feature. '/assets/admin.css' );

check( 'the picture of a block in the list is as large as the tile of the dialog "Insert block" - 15 rem - a name is broken where it is long, never cut off, and the head of the column of the pictures is set like the heads of the others: small, quiet capitals',
	preg_match( '/\.blocks-tile\s*\{[^}]*width:\s*15rem/', $style ) === 1 && preg_match( '/\.builder-cell-name[^{]*\{[^}]*overflow-wrap:\s*anywhere[^}]*white-space:\s*normal/s', $style ) === 1
	&& preg_match( '/th\[data-key="tile"\]\s*\{[^}]*text-transform:\s*uppercase/', $style ) === 1 );

// ---------------------------------------------------------------------------------------------------------------------------------
$site = ninoSandbox( 'blocks' );
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
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => $locale === 'de_DE' ? 'Willkommen' : 'Welcome' ] );

$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ], '/nino/http/routes' => $site['/nino/http/routes'] ] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The right and the token\n";

$every = [
	'blocks/list'			=> [],
	'blocks/open'			=> [ 'key' => 'hero-full' ],
	'blocks/preview'	=> [ 'key' => 'hero-full', 'tree' => [], 'texts' => [] ],
	'blocks/save'			=> [ 'key' => 'karten', 'tree' => [], 'texts' => [], 'base' => 'x', 'force' => true ],
	'blocks/saveas'		=> [ 'key' => 'hero-full', 'name' => 'Nope', 'tree' => [], 'texts' => [] ],
	'blocks/create'		=> [ 'name' => 'Nope', 'category' => 'content', 'from' => '' ],
	'blocks/duplicate'	=> [ 'key' => 'hero-full', 'name' => 'Nope' ],
	'blocks/rename'		=> [ 'key' => 'karten', 'name' => 'Nope' ],
	'blocks/remove'		=> [ 'key' => 'karten' ],
	'blocks/offer'		=> [ 'tree' => [], 'at' => '0' ],
	'blocks/notation'	=> [ 'tree' => [] ],
	'blocks/keys'			=> [ 'kind' => 'types' ],
	'blocks/tile'			=> [ 'key' => 'hero-full' ],
	'blocks/frames'		=> [],
	'blocks/adopt'		=> [ 'template' => 'page-home', 'tree' => [], 'at' => '0', 'key' => 'neu', 'name' => 'Neu', 'category' => 'content' ],
] + array_combine( array_map( static fn( string $act ): string => 'blocks/frame-'. $act, [ 'open', 'preview', 'save', 'restore', 'blocks', 'block', 'keys', 'variant', 'offer', 'notation', 'upload', 'unstage' ] ), array_fill( 0, 12, [ 'template' => 'frame-header', 'tree' => [], 'texts' => [], 'base' => 'x', 'key' => 'hero-full', 'kind' => 'types', 'uri' => 'x', 'files' => [] ] ) );

ninoUser( $site, 'builder-only@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
$before = blocksSnapshot( $site );
$answers = [];

foreach( $every as $action => $data )
	$answers[$action] = blocksCall( $site, $action, $data )[0];

check( 'an account that has the rights of the Builder and not /_admin/blocks/manage gets 403 on every action of the panel - the thirteen and the frames, the adopting of a block of a page  - and nothing is written', array_unique( array_values( $answers ) ) === [ 403 ] && count( $answers ) === 27 && array_keys( $every ) === array_keys( \Nino\Modules\Blocks\Admin::actions() ) && blocksSnapshot( $site ) === $before );

ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Blocks::PERM ] );
$answers = [];

foreach( $every as $action => $data ) {
	$_POST['data'] = json_encode( $data + [ 'locale' => \Nino\Locales::getNativeLocale( $site ) ] );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$answers[$action] = $request['/nino/http/response']['statusCode'];
}

check( 'every action is reached through the workbench, whose token check comes first: with the right and without the token of the session each is a 403, and nothing is written', array_unique( array_values( $answers ) ) === [ 403 ] && blocksSnapshot( $site ) === $before );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The list\n";

[ $status, $empty ] = blocksCall( $site, 'blocks/list' );
$ownBefore = array_column( $empty['blocks'] ?? [], 'key' );

check( 'the list of the project has no block where there is none, and says that the site has a page a block can be drawn on; the theme tab is not there without a theme in use, and is the project where it is asked for',
	$status === 200 && $ownBefore === [] && $empty['tab'] === 'project' && $empty['theme'] === null && $empty['shell'] === true && blocksCall( $site, 'blocks/list', [ 'tab' => 'theme' ] )[1]['tab'] === 'project' );

check( 'what a new block may begin with is every block of a page that the library has, from every source, each with the name of its source - and the categories are those of the kernel but the frames',
	count( array_filter( $empty['library'], static fn( array $entry ): bool => $entry['key'] === 'hero-full' && $entry['own'] === false ) ) === 1 && $empty['categories'] === [ 'intro', 'content', 'trust', 'offer', 'action', 'contact' ]
	&& array_keys( Sheet::listed( $site, 'basis' ) ) !== [] && array_diff_key( Sheet::listed( $site, 'basis' ), Sheet::listed( $site, '' ) ) === [] && Sheet::listed( $site, 'project' ) === [] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. A new block\n";

$before = blocksSnapshot( $site );
[ $made, $one ] = blocksCall( $site, 'blocks/create', [ 'name' => 'Mein Block', 'category' => 'content', 'from' => '' ] );
unset( $site['./nino/blocks/state'] );
$kernel = \Nino\Blocks::get( $site, 'mein-block' );
$meta = json_decode( (string) @file_get_contents( $sandbox. '/private/blocks/mein-block/block.json' ), true );
$added = array_keys( array_diff_key( blocksSnapshot( $site ), $before ) );
sort( $added );

check( 'an empty block is a section with one row and one column: its key is made of its name, the kernel lists it as a block of the project with the name in every language and the category, and its three kinds of files are all that is written',
	$made === 200 && $one['key'] === 'mein-block' && ( $kernel['origin'] ?? '' ) === 'project' && ( $kernel['name']['de_DE'] ?? '' ) === 'Mein Block' && ( $kernel['name']['en_US'] ?? '' ) === 'Mein Block' && ( $kernel['category'] ?? '' ) === 'content'
	&& str_contains( (string) $kernel['source'], '[section-row]' ) === true && str_contains( (string) $kernel['source'], '[section-col]' ) === true && ( $meta['category'] ?? '' ) === 'content'
	&& $added === [ '/private/blocks/mein-block/block.json', '/private/blocks/mein-block/block.tpl', '/private/blocks/mein-block/text/de_DE.json', '/private/blocks/mein-block/text/en_US.json' ] );

$before = blocksSnapshot( $site );
$refusals = [
	'a name that is taken'			=> blocksCall( $site, 'blocks/create', [ 'name' => 'Mein  Block', 'category' => 'content', 'from' => '' ] ),
	'the key of another source'	=> blocksCall( $site, 'blocks/create', [ 'name' => 'Hero full', 'category' => 'content', 'from' => '' ] ),
	'no name'										=> blocksCall( $site, 'blocks/create', [ 'name' => '', 'category' => 'content', 'from' => '' ] ),
	'a name with a bracket'			=> blocksCall( $site, 'blocks/create', [ 'name' => 'a <b>', 'category' => 'content', 'from' => '' ] ),
	'a name too long'						=> blocksCall( $site, 'blocks/create', [ 'name' => str_repeat( 'n', 81 ), 'category' => 'content', 'from' => '' ] ),
	'the category of a frame'		=> blocksCall( $site, 'blocks/create', [ 'name' => 'Rahmen', 'category' => 'frame', 'from' => '' ] ),
	'no category'								=> blocksCall( $site, 'blocks/create', [ 'name' => 'Rahmen', 'category' => '', 'from' => '' ] ),
	'a block to copy that is none'	=> blocksCall( $site, 'blocks/create', [ 'name' => 'Kopie', 'category' => 'content', 'from' => 'no-such-block' ] ),
	'a path as the block to copy'	=> blocksCall( $site, 'blocks/create', [ 'name' => 'Kopie', 'category' => 'content', 'from' => '../../config' ] ),
];

check( 'a name that makes a key that is taken - in the project or in another source - is refused at the name with 409, and a name or a category that is none, a block to copy that is none or a path is refused with its own answer: nothing is written',
	array_column( $refusals, 0 ) === [ 409, 409, 400, 400, 400, 400, 400, 404, 404 ] && ( $refusals['a name that is taken'][1]['field'] ?? '' ) === 'name' && ( $refusals['the key of another source'][1]['field'] ?? '' ) === 'name' && ( $refusals['a name that is taken'][1]['code'] ?? '' ) === 'blocks_taken' && blocksSnapshot( $site ) === $before );

[ $copied, $copy ] = blocksCall( $site, 'blocks/create', [ 'name' => 'Held von mir', 'category' => 'intro', 'from' => 'hero-full' ] );
unset( $site['./nino/blocks/state'] );
$mine = \Nino\Blocks::get( $site, 'held-von-mir' );
$basis = \Nino\Blocks::get( $site, 'hero-full' );

check( 'the copy of a block of the library is a block of the project with the components, the texts and the picture places of the original, and the name and the category it was given - the variants of the original go along',
	$copied === 200 && ( $mine['origin'] ?? '' ) === 'project' && ( $mine['category'] ?? '' ) === 'intro' && ( $mine['name']['en_US'] ?? '' ) === 'Held von mir' && $mine['images'] !== [] && array_keys( $mine['images'] ) === array_keys( $basis['images'] ) && $mine['texts']['en_US'] === $basis['texts']['en_US']
	&& array_keys( $mine['alternatives'] ) === array_keys( $basis['alternatives'] ) );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The sheet\n";

[ $status, $open ] = blocksCall( $site, 'blocks/open', [ 'key' => 'mein-block' ] );
$at = Sheet::rootAt( $open['tree'] ?? [] );

check( 'a block is opened as a sheet: the two calls of the frame of the site and the block between them, its stamp, its name, the schemas of the design - and nothing of the pages: no shared text, no variant, no last state',
	$status === 200 && $at === 2 && $open['key'] === 'mein-block' && $open['editable'] === true && preg_match( '/^[0-9a-f]{64}$/D', $open['stamp'] ) === 1 && isset( $open['schemas']['section-wrap'] ) === true && $open['level'] === 2 && $open['template'] === 'page-home'
	&& $open['address'] === '/home' && $open['last'] === 0 && $open['variants'] === [] && ( (array) $open['shared'] ) === [] );

check( 'the words and the picture places of a block are in the namespace of the page it is drawn on, below the id of the scope that holds them - for a block that has some',
	( static function() use ( &$site ): bool {

		[ , $hero ] = blocksCall( $site, 'blocks/open', [ 'key' => 'held-von-mir' ] );
		$keys = array_keys( $hero['texts'] );

		return $keys !== [] && array_filter( $keys, static fn( string $key ): bool => str_starts_with( $key, '/template/page-home/' ) === false ) === [] && count( $hero['images'] ) >= 1 && array_filter( array_keys( $hero['images'] ), static fn( string $uri ): bool => str_starts_with( $uri, '/template/page-home/' ) === false ) === [];
	} )() );

$before = blocksSnapshot( $site );
$foreign = [
	'a block of another source'	=> blocksCall( $site, 'blocks/open', [ 'key' => 'hero-full' ] ),
	'a key that is none'				=> blocksCall( $site, 'blocks/open', [ 'key' => 'no-such-block' ] ),
	'a path'										=> blocksCall( $site, 'blocks/open', [ 'key' => '../../config' ] ),
	'a capital'									=> blocksCall( $site, 'blocks/open', [ 'key' => 'Mein-Block' ] ),
	'a null byte'								=> blocksCall( $site, 'blocks/open', [ 'key' => "mein-block\0" ] ),
	'no key'										=> blocksCall( $site, 'blocks/open', [] ),
];

check( 'a block that is not the project\'s or the theme\'s in use - one of Basis, a key that is none, a path, a capital, a null byte - is no sheet: 404, and nothing is written', array_unique( array_column( $foreign, 0 ) ) === [ 404 ] && blocksSnapshot( $site ) === $before );

[ $previewed, $draft ] = blocksCall( $site, 'blocks/preview', [ 'key' => 'mein-block', 'tree' => $open['tree'], 'texts' => $open['texts'] ] );
$stored = \Nino\Filesystem::getFileContent( $site, \Nino\Preview::DIR. '/'. \Nino\Modules\Blocks::KIND. '/'. ( $draft['token'] ?? 'none' ). '/draft.json', null );

check( 'a draft of the sheet is a token of its own kind and the query that names it, the address of the page it is drawn on and what the register has against it - and the draft that is held is the sheet, written, with the frame of the site and the language of the view',
	$previewed === 200 && preg_match( \Nino\Preview::TOKEN, $draft['token'] ) === 1 && $draft['query'] === \Nino\Preview::QUERY. '='. $draft['token'] && $draft['address'] === '/home' && is_array( $stored ) === true && $stored['template'] === 'page-home' && $stored['locale'] === 'de_DE'
	&& str_starts_with( (string) $stored['source'], '[template /templates/html-header]' ) === true && str_contains( (string) $stored['source'], '[section-wrap id="mein-block"' ) === true );

$request = [];
$appData = $site;
$drawn = \Nino\Modules\Blocks::alone( $appData, $request, $stored ?? [] );
$appData = $site;
$bare = \Nino\Modules\Blocks::alone( $appData, $request, [ 'source' => preg_replace( '/^\[template [^\]]+\]\n|\n\[template [^\]]+\]\n$/', '', (string) ( $stored['source'] ?? '' ) ) ] + (array) $stored );
$appData = $site;
$free = \Nino\Modules\Blocks::alone( $appData, $request, [ 'source' => (string) ( $stored['source'] ?? '' ). '<p>free</p>' ] + (array) $stored );

// a request that asks for the draft with the token is drawn by the module for the account that holds it, and for no other
unset( $site['./nino/template/swapped'] );
ninoVisit( $site, '/home?'. \Nino\Preview::QUERY. '='. $draft['token'] );
$visited = (string) ( $site['./nino/template/swapped']['page-home'] ?? '' );
$opening = (string) ( $site['./nino/template/swapped']['html-header'] ?? '' );
$closing = (string) ( $site['./nino/template/swapped']['html-footer'] ?? '' );
unset( $site['./nino/template/swapped'] );
ninoUser( $site, 'other@example.com', [ '/_admin/builder/manage' ] );
ninoVisit( $site, '/home?'. \Nino\Preview::QUERY. '='. $draft['token'] );
$refused = isset( $site['./nino/template/swapped'] ) === true;
unset( $site['./nino/template/swapped'] );
ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Blocks::PERM ] );

check( 'a request that asks for the draft of a block with its token is drawn by the module for the account that holds the right: the page is the draft - the frame of the site and the block - and for an account that has not, the page is as the visitors have it',
	str_contains( $visited, '[section-wrap id="mein-block"' ) === true && str_starts_with( $visited, '[template /templates/html-header]' ) === true && $refused === false );

check( 'the draft is drawn ALONE: the page is the page of the pictures - the stylesheet of the site and a main - with the block in it, and neither the calls of the frame nor the head and the foot of the site',
	str_contains( $opening, '[assets /.cache/style.css]' ) === true && str_contains( $opening, '<main>' ) === true && str_contains( $closing, '</main>' ) === true && str_contains( $opening. $closing, 'frame-header' ) === false && str_contains( $opening. $closing, 'frame-footer' ) === false
	&& str_contains( $visited, '/templates/frame-header' ) === false );

check( 'the sheet is drawn as the sheet it is - the two calls of the frame and the block between them - so that the marks the drawing leaves have the places of the tree of the sheet, which the editor finds its components by: the block is the third entry of the top level, as in the tree',
	( static function() use ( $visited ): bool { $nodes = Tree::read( $GLOBALS['site'], $visited ); return Sheet::rootAt( $nodes ) === 2; } )() );

check( 'the draft is drawn as a draft of ONE block - the two calls of the frame and the block - and not drawn without the frame or with free markup beside the block', $drawn === true && $bare === false && $free === false );

$bad = [
	'markup the step may not write'	=> blocksEdit( $open['tree'], blocksFind( $open['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ) ?? '2', static function( array $node ): array { $node['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<b>x</b>' ] ] ]; return $node; } ),
	'the frame taken out'						=> array_slice( $open['tree'], 2 ),
	'a second block'								=> array_merge( $open['tree'], [ [ 't' => "\n" ], array_replace_recursive( $open['tree'][2], [ 'a' => [ 'id' => 'other-block' ] ] ) ] ),
	'free markup beside the block'	=> array_merge( $open['tree'], [ [ 't' => '<p>free</p>' ] ] ),
];
$answers = [];

foreach( $bad as $label => $tree )
	$answers[$label] = blocksCall( $site, 'blocks/preview', [ 'key' => 'mein-block', 'tree' => $tree, 'texts' => [] ] )[0];

check( 'a draft that is not a sheet - markup of html, the frame taken out, a second block, free markup beside the block - is refused: 400 or 403, and no token is made', array_values( array_unique( array_map( static fn( int $status ): bool => in_array( $status, [ 400, 403 ], true ), $answers ) ) ) === [ true ] && count( $answers ) === 4 && Sheet::rootAt( $bad['a second block'] ) === null && Sheet::rootAt( $bad['the frame taken out'] ) === null && Sheet::rootAt( $bad['free markup beside the block'] ) === null && Sheet::rootAt( $open['tree'] ) === 2 );

// the draft is held for the account that has the right and for nobody else
ninoUser( $site, 'other@example.com', [ '/_admin/builder/manage' ] );
$visit = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => $draft['token'] ] ], '/nino/http/response' => [] ];
$thief = \Nino\Preview::held( $site, $visit, \Nino\Modules\Blocks::KIND, \Nino\Modules\Blocks::PERM );
ninoUser( $site, 'blocks@example.com', [ \Nino\Modules\Blocks::PERM ] );

check( 'the draft of a block is held only for the account that has the right /_admin/blocks/manage, and a token the session was given by another account is none', $thief === null );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. What is written\n";

$title = blocksFind( $open['tree'], static fn( array $node ): bool => $node['n'] === 'section-wrap' );
$column = blocksFind( $open['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' );
$grown = blocksEdit( $open['tree'], $column, static function( array $node ): array {
	$node['c'][] = [ 't' => "\n\t\t\t" ];
	$node['c'][] = [ 'n' => 'section-title', 's' => '~claim', 'a' => [ 'tag' => 'h2' ], 'c' => null ];
	$node['c'][] = [ 't' => "\n\t\t" ];
	return $node;
} );
$words = [ '/template/page-home/mein-block/claim' => [ 'de_DE' => 'Ein Anspruch', 'en_US' => 'A claim' ] ];
$wasFiles = blocksSnapshot( $site );

[ $saved, $answer ] = blocksCall( $site, 'blocks/save', [ 'key' => 'mein-block', 'tree' => $grown, 'texts' => $words, 'base' => $open['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$now = \Nino\Blocks::get( $site, 'mein-block' );
$changedFiles = array_keys( array_diff_assoc( blocksSnapshot( $site ), $wasFiles ) );
sort( $changedFiles );

check( 'a block is saved over itself: the key stays, the new component and its words are in the files of the block in each language, the name and the category are as they were, the answer is the sheet as it is saved - with the stamp of the new files - and only the files of that block changed',
	$saved === 200 && $answer['key'] === 'mein-block' && $answer['stamp'] !== $open['stamp'] && str_contains( (string) $now['source'], '[section-title ~claim' ) === true && ( $now['texts']['de_DE']['claim'] ?? '' ) === 'Ein Anspruch' && ( $now['texts']['en_US']['claim'] ?? '' ) === 'A claim'
	&& ( $now['name']['de_DE'] ?? '' ) === 'Mein Block' && ( $now['category'] ?? '' ) === 'content' && array_filter( $changedFiles, static fn( string $file ): bool => str_starts_with( $file, '/private/blocks/mein-block/' ) === false ) === [] && Sheet::rootAt( $answer['tree'] ) === 2 );

// another session saved since this one opened the block: not written over unless force says so
$beforeConflict = blocksSnapshot( $site );
[ $conflict, $conflicted ] = blocksCall( $site, 'blocks/save', [ 'key' => 'mein-block', 'tree' => $open['tree'], 'texts' => [], 'base' => $open['stamp'] ] );
[ $forced ] = blocksCall( $site, 'blocks/save', [ 'key' => 'mein-block', 'tree' => $open['tree'], 'texts' => [], 'base' => $open['stamp'], 'force' => true ] );
unset( $site['./nino/blocks/state'] );

check( 'a block that was changed since the sheet was opened - by another session - is not written over: 409 with the code of the conflict the workbench asks about, and nothing is written; with force it is written',
	$conflict === 409 && ( $conflicted['code'] ?? '' ) === 'builder_changed' && $beforeConflict === $beforeConflict && $forced === 200 && str_contains( (string) \Nino\Blocks::get( $site, 'mein-block' )['source'], '~claim' ) === false );

// what the rule of the kernel refuses is not written, and the reason is the answer
[ , $reopened ] = blocksCall( $site, 'blocks/open', [ 'key' => 'mein-block' ] );
$long = blocksEdit( $reopened['tree'], $column, static function( array $node ): array {
	$node['c'][] = [ 'n' => 'text', 's' => '~long', 'a' => [], 'c' => null ];
	return $node;
} );
$beforeInvalid = blocksSnapshot( $site );
[ $invalid, $why ] = blocksCall( $site, 'blocks/save', [ 'key' => 'mein-block', 'tree' => $long, 'texts' => [ '/template/page-home/mein-block/long' => [ 'de_DE' => str_repeat( 'w', 601 ), 'en_US' => str_repeat( 'w', 601 ) ] ], 'base' => $reopened['stamp'] ] );

check( 'a block that the rule of the kernel takes for none - a text of more than the most - is not written: 400 with the sentence of the reason, and no file changed', $invalid === 400 && in_array( $why['code'] ?? '', [ 'blocks_long', 'blocks_invalid' ], true ) === true && blocksSnapshot( $site ) === $beforeInvalid );

// what the rule of the kernel refuses and the parts do not: more texts than a block may have - the reason of the kernel is the sentence
$many = blocksEdit( $reopened['tree'], $column, static function( array $node ): array {

	for( $at = 1; $at <= 61; $at++ ) {
		$node['c'][] = [ 't' => "\n\t\t\t" ];
		$node['c'][] = [ 'n' => 'text', 's' => '~line-'. $at, 'a' => [], 'c' => null ];
	}

	return $node;
} );
$crowd = [];

for( $at = 1; $at <= 61; $at++ )
	$crowd['/template/page-home/mein-block/line-'. $at] = [ 'de_DE' => 'Zeile '. $at, 'en_US' => 'Line '. $at ];

$beforeCrowd = blocksSnapshot( $site );
[ $crowded, $crowdWhy ] = blocksCall( $site, 'blocks/save', [ 'key' => 'mein-block', 'tree' => $many, 'texts' => $crowd, 'base' => $reopened['stamp'] ] );

check( 'a block that the rule of the kernel takes for none - more texts than a block may have - is not written: 400 with the sentence that carries the reason of the kernel, and no file changed', $crowded === 400 && ( $crowdWhy['code'] ?? '' ) === 'blocks_invalid' && str_contains( (string) ( $crowdWhy['error'] ?? '' ), 'texts' ) === true && blocksSnapshot( $site ) === $beforeCrowd );

// the key of a block that is not the project's: no save, no name, no removal
$before = blocksSnapshot( $site );
$alien = [];

foreach( [ 'hero-full', '../../config', 'Mein-Block', "mein-block\0x", 'no-such-block', '' ] as $key ) {

	$alien[] = blocksCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $reopened['tree'], 'texts' => [], 'force' => true ] )[0];
	$alien[] = blocksCall( $site, 'blocks/rename', [ 'key' => $key, 'name' => 'Nope' ] )[0];
	$alien[] = blocksCall( $site, 'blocks/remove', [ 'key' => $key ] )[0];
}

check( 'a key that is not a block of the project - one of Basis, a path, a capital, a null byte, a key that is none, no key - is refused by save, rename and remove with 404, and not a file changes or goes', array_unique( $alien ) === [ 404 ] && blocksSnapshot( $site ) === $before );

[ $as, $new ] = blocksCall( $site, 'blocks/saveas', [ 'key' => 'mein-block', 'name' => 'Mein anderer Block', 'tree' => $grown, 'texts' => $words ] );
unset( $site['./nino/blocks/state'] );
$other = \Nino\Blocks::get( $site, 'mein-anderer-block' );
$still = \Nino\Blocks::get( $site, 'mein-block' );

check( 'save as makes a new block of the project under the key its name makes - with what the sheet holds - the answer is the sheet of the new block, and the block it was saved from is as it was',
	$as === 200 && $new['key'] === 'mein-anderer-block' && ( $other['origin'] ?? '' ) === 'project' && str_contains( (string) $other['source'], '~claim' ) === true && ( $other['name']['en_US'] ?? '' ) === 'Mein anderer Block' && str_contains( (string) $still['source'], '~claim' ) === false && $new['stamp'] !== '' );

$before = blocksSnapshot( $site );
$answers = [
	'a name that is taken'		=> blocksCall( $site, 'blocks/saveas', [ 'key' => 'mein-block', 'name' => 'Mein Block', 'tree' => $grown, 'texts' => $words ] ),
	'a name of another source'	=> blocksCall( $site, 'blocks/saveas', [ 'key' => 'mein-block', 'name' => 'Hero full', 'tree' => $grown, 'texts' => $words ] ),
	'a block of another source'	=> blocksCall( $site, 'blocks/saveas', [ 'key' => 'hero-full', 'name' => 'Kopie 2', 'tree' => $grown, 'texts' => $words ] ),
	'no name'									=> blocksCall( $site, 'blocks/saveas', [ 'key' => 'mein-block', 'name' => '', 'tree' => $grown, 'texts' => $words ] ),
];

check( 'save as under a name that makes a key that is taken - of the project or of another source - or under no name, and from a block that is not the project\'s, is refused - 409, 400, 404 - and nothing is written', array_column( $answers, 0 ) === [ 409, 409, 404, 400 ] && blocksSnapshot( $site ) === $before );

[ $duplicated ] = blocksCall( $site, 'blocks/duplicate', [ 'key' => 'held-von-mir', 'name' => 'Held Zwei' ] );
unset( $site['./nino/blocks/state'] );
$two = \Nino\Blocks::get( $site, 'held-zwei' );

check( 'a block of the project is duplicated under a name of its own: the copy is a block of the project with the components, the words and the variants of the original, and the original is untouched',
	$duplicated === 200 && ( $two['origin'] ?? '' ) === 'project' && $two['texts'] === \Nino\Blocks::get( $site, 'held-von-mir' )['texts'] && array_keys( $two['alternatives'] ) === array_keys( \Nino\Blocks::get( $site, 'held-von-mir' )['alternatives'] ) && ( $two['name']['de_DE'] ?? '' ) === 'Held Zwei' );

$before = blocksSnapshot( $site );
[ $renamed ] = blocksCall( $site, 'blocks/rename', [ 'key' => 'held-zwei', 'name' => [ 'de_DE' => 'Zweiter Held', 'en_US' => 'Second hero' ] ] );
unset( $site['./nino/blocks/state'] );
$named = \Nino\Blocks::get( $site, 'held-zwei' );
$touched = array_keys( array_diff_assoc( blocksSnapshot( $site ), $before ) );

check( 'a block is renamed in each language and the key stays: only block.json of that block changes, the template, the words and the variants are as they were',
	$renamed === 200 && ( $named['name']['de_DE'] ?? '' ) === 'Zweiter Held' && ( $named['name']['en_US'] ?? '' ) === 'Second hero' && $touched === [ '/private/blocks/held-zwei/block.json' ] && $named['source'] === $two['source'] && $named['alternatives'] === $two['alternatives'] );

$before = blocksSnapshot( $site );
$answers = [ blocksCall( $site, 'blocks/rename', [ 'key' => 'held-zwei', 'name' => '<b>x</b>' ] )[0], blocksCall( $site, 'blocks/rename', [ 'key' => 'held-zwei', 'name' => '' ] )[0], blocksCall( $site, 'blocks/rename', [ 'key' => 'held-zwei', 'name' => str_repeat( 'n', 81 ) ] )[0] ];

check( 'a name with a bracket, no name and a name too long are no new name of a block: 400, and nothing is written', $answers === [ 400, 400, 400 ] && blocksSnapshot( $site ) === $before );

[ $removed ] = blocksCall( $site, 'blocks/remove', [ 'key' => 'held-zwei' ] );
unset( $site['./nino/blocks/state'] );

check( 'a block of the project is taken away with its files, and the blocks of the others stay', $removed === 200 && \Nino\Blocks::get( $site, 'held-zwei' ) === null && is_dir( $sandbox. '/private/blocks/held-zwei' ) === false && \Nino\Blocks::get( $site, 'held-von-mir' ) !== null && \Nino\Blocks::get( $site, 'hero-full' ) !== null );

// the keys the panel lists are the ones of the blocks of the project, and only of those
[ , $listed ] = blocksCall( $site, 'blocks/list' );

check( 'the list shows the blocks of the project that there are now, with their number of variants, and no block of another source', array_column( $listed['blocks'], 'key' ) !== [] && array_diff( array_column( $listed['blocks'], 'key' ), [ 'mein-block', 'mein-anderer-block', 'held-von-mir' ] ) === [] && array_column( $listed['blocks'], 'variants', 'key' )['held-von-mir'] === count( $mine['alternatives'] ) );

// the tile of the list is the small picture of a block of any source
[ $tiled, $tile ] = blocksCall( $site, 'blocks/tile', [ 'key' => 'hero-full' ] );
$noTile = blocksCall( $site, 'blocks/tile', [ 'key' => 'no-such-block' ] )[0];

check( 'the picture of a block in the list is a token of its own kind for any block the library has, and a block that is none has none: 404', $tiled === 200 && preg_match( \Nino\Preview::TOKEN, $tile['token'] ?? '' ) === 1 && $noTile === 404 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF2. Languages, names, a draft that is new and what a cut-short write leaves\n";

// a block with no text at all is opened with its texts as a MAP: an empty list in JSON would be given the key of a text that is typed and lose it
[ , $bare ] = blocksCall( $site, 'blocks/open', [ 'key' => 'mein-block' ] );

check( 'a sheet with no text is opened with the texts as an empty map and not as an empty list: the words that are typed into the draft are sent as a map and kept', json_encode( $bare['texts'] ) === '{}' && json_encode( $bare ) !== false && str_contains( (string) json_encode( $bare ), '"texts":{}' ) === true );

// a copy of a block has its languages as the block has them: the description, the labels of the picture places and the texts of each language stay in theirs
$basis = \Nino\Blocks::get( $site, 'hero-full' );
[ $copied, ] = blocksCall( $site, 'blocks/create', [ 'name' => [ 'de_DE' => 'Kopie Held', 'en_US' => 'Copy hero' ], 'category' => 'intro', 'from' => 'hero-full' ] );
unset( $site['./nino/blocks/state'] );
$copy = \Nino\Blocks::get( $site, 'kopie-held' );
$norm = static function( array $value ) use ( &$norm ): array { ksort( $value ); return array_map( static fn( mixed $each ): mixed => is_array( $each ) === true ? $norm( $each ) : $each, $value ); };
$labels = static fn( array $block ): array => array_values( array_map( static fn( array $image ): array => $image['label'], $block['images'] ) );


check( 'the copy of hero-full has the description, the labels of the picture places and the texts in German as the original has them in German and in English as the original has them in English - and the original has them different',
	$copied === 200 && $basis['description']['de_DE'] !== $basis['description']['en_US'] && $norm( $copy['description'] ) === $norm( $basis['description'] ) && $norm( $labels( $copy ) ) === $norm( $labels( $basis ) ) && array_values( $labels( $basis )[0] ) !== [ reset( $labels( $basis )[0] ), reset( $labels( $basis )[0] ) ]
	&& $copy['texts']['de_DE'] === $basis['texts']['de_DE'] && $copy['texts']['en_US'] === $basis['texts']['en_US'] && $copy['texts']['de_DE'] !== $copy['texts']['en_US'] && $norm( $copy['name'] ) === $norm( [ 'de_DE' => 'Kopie Held', 'en_US' => 'Copy hero' ] ) );

// ...and a duplicate of a block of the project, a save under another name and a save over itself keep them as well
[ $duplicated, ] = blocksCall( $site, 'blocks/duplicate', [ 'key' => 'kopie-held', 'name' => [ 'de_DE' => 'Held Drei', 'en_US' => 'Hero three' ] ] );
[ , $kept ] = blocksCall( $site, 'blocks/open', [ 'key' => 'kopie-held' ] );
[ $resaved, ] = blocksCall( $site, 'blocks/save', [ 'key' => 'kopie-held', 'tree' => $kept['tree'], 'texts' => $kept['texts'], 'pictures' => $kept['images'], 'base' => $kept['stamp'] ] );
[ $savedAs, ] = blocksCall( $site, 'blocks/saveas', [ 'key' => 'kopie-held', 'name' => [ 'de_DE' => 'Held Vier', 'en_US' => 'Hero four' ], 'tree' => $kept['tree'], 'texts' => $kept['texts'] ] );
unset( $site['./nino/blocks/state'] );
$three = \Nino\Blocks::get( $site, 'held-drei' );
$again = \Nino\Blocks::get( $site, 'kopie-held' );
$four = \Nino\Blocks::get( $site, 'held-vier' );

check( 'a duplicate, a save over the block and a save under another name keep the description, the labels of the picture places and the texts of each language as they are: nothing of one language is made into another',
	$duplicated === 200 && $resaved === 200 && $savedAs === 200 && $norm( $three['description'] ) === $norm( $basis['description'] ) && $norm( $labels( $three ) ) === $norm( $labels( $basis ) ) && $norm( $three['texts'] ) === $norm( $copy['texts'] ) && $norm( $again['description'] ) === $norm( $basis['description'] ) && $norm( $labels( $again ) ) === $norm( $labels( $basis ) ) && $norm( $again['texts'] ) === $norm( $copy['texts'] )
	&& $norm( $four['description'] ) === $norm( $basis['description'] ) && $norm( $labels( $four ) ) === $norm( $labels( $basis ) ) && $norm( $four['texts'] ) === $norm( $copy['texts'] ) );

// an empty block has a description in each language - the name in it - and a name that is given in the working language alone is the name in the others, where the kernel wants one
[ $empty, ] = blocksCall( $site, 'blocks/create', [ 'name' => [ 'de_DE' => 'Leerer Block', 'en_US' => 'Empty block' ], 'category' => 'content', 'from' => '' ] );
[ $single, ] = blocksCall( $site, 'blocks/create', [ 'name' => [ 'de_DE' => 'Nur Deutsch', 'en_US' => '' ], 'category' => 'content', 'from' => '' ] );
unset( $site['./nino/blocks/state'] );
$leer = \Nino\Blocks::get( $site, 'leerer-block' );
$only = \Nino\Blocks::get( $site, 'nur-deutsch' );
$beforeNone = blocksSnapshot( $site );
$work = blocksCall( $site, 'blocks/create', [ 'name' => [ 'de_DE' => '', 'en_US' => 'Only English' ], 'category' => 'content', 'from' => '' ] );

check( 'a new empty block has its name as its description in each language; a name in the working language alone is the name in the other as well; a name that the working language lacks is refused at the name: 400, nothing is written',
	$empty === 200 && $norm( $leer['description'] ) === $norm( [ 'de_DE' => 'Leerer Block', 'en_US' => 'Empty block' ] ) && $norm( $leer['name'] ) === $norm( $leer['description'] ) && $single === 200 && $norm( $only['name'] ) === $norm( [ 'de_DE' => 'Nur Deutsch', 'en_US' => 'Nur Deutsch' ] ) && $work[0] === 400 && ( $work[1]['field'] ?? '' ) === 'name' && blocksSnapshot( $site ) === $beforeNone );

// a text that is typed in one language is the text of the other where the kernel wants one in each - and one typed in both is two texts
[ , $leerOpen ] = blocksCall( $site, 'blocks/open', [ 'key' => 'leerer-block' ] );
$colLeer = blocksFind( $leerOpen['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' );
$typed = blocksEdit( $leerOpen['tree'], $colLeer, static function( array $node ): array {
	$node['c'][] = [ 't' => "\n\t\t\t" ];
	$node['c'][] = [ 'n' => 'section-title', 's' => '~titel', 'a' => [ 'tag' => 'h2' ], 'c' => null ];
	$node['c'][] = [ 't' => "\n\t\t\t" ];
	$node['c'][] = [ 'n' => 'section-title', 's' => '~zwei', 'a' => [ 'tag' => 'h3' ], 'c' => null ];
	$node['c'][] = [ 't' => "\n\t\t" ];
	return $node;
} );
$words = [ '/template/page-home/leerer-block/titel' => [ 'de_DE' => 'Nur hier getippt' ], '/template/page-home/leerer-block/zwei' => [ 'de_DE' => 'Zwei', 'en_US' => 'Two' ] ];
[ $wrote, ] = blocksCall( $site, 'blocks/save', [ 'key' => 'leerer-block', 'tree' => $typed, 'texts' => $words, 'base' => $leerOpen['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$leer = \Nino\Blocks::get( $site, 'leerer-block' );

check( 'a text typed in German alone is saved in both files - the kernel wants each text in each language - and a text typed in both languages stays two texts',
	$wrote === 200 && $leer['texts']['de_DE']['titel'] === 'Nur hier getippt' && $leer['texts']['en_US']['titel'] === 'Nur hier getippt' && $leer['texts']['de_DE']['zwei'] === 'Zwei' && $leer['texts']['en_US']['zwei'] === 'Two' );

// the name of a block is the name of one block of the library: another block - of any source - with that name is a refusal at the name
$beforeName = blocksSnapshot( $site );
$taken = [
	'the name of a block of another source'	=> blocksCall( $site, 'blocks/create', [ 'name' => [ 'de_DE' => $basis['name']['de_DE'], 'en_US' => 'Another' ], 'category' => 'content', 'from' => '' ] ),
	'the same in capitals'									=> blocksCall( $site, 'blocks/create', [ 'name' => [ 'de_DE' => mb_strtoupper( $basis['name']['de_DE'] ), 'en_US' => 'Another' ], 'category' => 'content', 'from' => '' ] ),
	'a duplicate under it'									=> blocksCall( $site, 'blocks/duplicate', [ 'key' => 'leerer-block', 'name' => [ 'de_DE' => $basis['name']['de_DE'], 'en_US' => 'Another' ] ] ),
	'a save under it'												=> blocksCall( $site, 'blocks/saveas', [ 'key' => 'leerer-block', 'name' => [ 'de_DE' => $basis['name']['de_DE'], 'en_US' => 'Another' ], 'tree' => $typed, 'texts' => $words ] ),
	'a new name of another block'						=> blocksCall( $site, 'blocks/rename', [ 'key' => 'leerer-block', 'name' => [ 'de_DE' => 'Nur Deutsch', 'en_US' => 'Nur Deutsch' ] ] ),
];

check( 'a name that another block of the library has - of any source, in any case - is refused at the field of the name with 409 by create, duplicate, save as and rename, and nothing is written', array_values( array_unique( array_column( $taken, 0 ) ) ) === [ 409 ] && array_values( array_unique( array_map( static fn( array $answer ): string => (string) ( $answer[1]['field'] ?? '' ) . ( $answer[1]['code'] ?? '' ), $taken ) ) ) === [ 'nameblocks_name_taken' ] && blocksSnapshot( $site ) === $beforeName );
[ $keeps, ] = blocksCall( $site, 'blocks/rename', [ 'key' => 'leerer-block', 'name' => [ 'de_DE' => 'Leerer Block', 'en_US' => 'Empty block, new' ] ] );

check( 'a block may keep its own name in a rename - in one language, with the other new', $keeps === 200 );

// the draft of a block that is not there yet is held to the rule of a draft as well: nothing a block may not hold, for no key either
[ $nameless, ] = blocksCall( $site, 'blocks/preview', [ 'key' => '', 'tree' => $bad['markup the step may not write'], 'texts' => [] ] );
[ $plain, ] = blocksCall( $site, 'blocks/preview', [ 'key' => '', 'tree' => $open['tree'], 'texts' => [] ] );

check( 'a draft with no key is held to the rule of a draft like one with a key: markup of html is refused (403) and the tree of an empty block is drawn', $nameless === 403 && $plain === 200 );

// a picture that was just put in has no size: it is made with the size of a picture of a page, and the block is saved
[ , $reopenedLeer ] = blocksCall( $site, 'blocks/open', [ 'key' => 'leerer-block' ] );
$imageTree = blocksEdit( $reopenedLeer['tree'], blocksFind( $reopenedLeer['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ), static function( array $node ): array {
	$node['c'][] = [ 'n' => 'image', 's' => '~bild', 'a' => [], 'c' => null ];
	$node['c'][] = [ 't' => "\n\t\t" ];
	return $node;
} );
[ $pictured, $pic ] = blocksCall( $site, 'blocks/save', [ 'key' => 'leerer-block', 'tree' => $imageTree, 'texts' => $reopenedLeer['texts'], 'base' => $reopenedLeer['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$leer = \Nino\Blocks::get( $site, 'leerer-block' );

check( 'a picture place that was put in and has no size gets the size of a picture of a page - 1200 by 800 - and the block is saved with it', $pictured === 200 && ( $leer['images']['bild']['width'] ?? 0 ) === 1200 && ( $leer['images']['bild']['height'] ?? 0 ) === 800 );

// what a write that was cut short leaves is taken away at the next write, a day old - and is no block, whatever it holds
$blocks = $sandbox. '/private/blocks';
$old = $blocks. '/.new-aaaaaaaaaaaa';
$young = $blocks. '/.old-bbbbbbbbbbbb';

foreach( [ $old, $young ] as $left ) {
	mkdir( $left. '/text', 0755, true );
	copy( $blocks. '/mein-block/block.json', $left. '/block.json' );
	copy( $blocks. '/mein-block/block.tpl', $left. '/block.tpl' );
	copy( $blocks. '/mein-block/text/de_DE.json', $left. '/text/de_DE.json' );
	copy( $blocks. '/mein-block/text/en_US.json', $left. '/text/en_US.json' );
}

touch( $old, time() - 2 * 86400 );
unset( $site['./nino/blocks/state'] );
$listedLeft = array_keys( \Nino\Blocks::all( $site ) );
$sawLeft = array_filter( $listedLeft, static fn( string $key ): bool => str_contains( $key, 'aaaaaaaaaaaa' ) === true || str_contains( $key, 'bbbbbbbbbbbb' ) === true ) === [] && array_filter( array_keys( \Nino\Blocks::rejected( $site ) ), static fn( string $where ): bool => str_contains( $where, '.new-' ) === true || str_contains( $where, '.old-' ) === true ) === [];
blocksCall( $site, 'blocks/create', [ 'name' => 'Nach dem Abbruch', 'category' => 'content', 'from' => '' ] );
unset( $site['./nino/blocks/state'] );

check( 'a folder .new-* or .old-* that a cut-short write left is no block, listed or refused, and is taken away at the next write when it is a day old - a younger one is left, it may be another request\'s',
	$sawLeft === true && file_exists( $old ) === false && is_dir( $young ) === true && \Nino\Blocks::get( $site, 'nach-dem-abbruch' ) !== null );

// a block with a loop - the list and the table over elements of the Collections - is copied and saved: the type of its elements and the fields it reads of them are sources of the elements, no foreign ones
$loops = [];

foreach( [ 'list-over-elements' => 'collection-list', 'table-over-elements' => 'collection-table' ] as $from => $call ) {

	[ $made, $answer ] = blocksCall( $site, 'blocks/create', [ 'name' => 'Schleife '. $from, 'category' => 'content', 'from' => $from ] );
	unset( $site['./nino/blocks/state'] );
	$key = (string) ( $answer['key'] ?? '' );
	$copy = \Nino\Blocks::get( $site, $key );
	[ , $again ] = blocksCall( $site, 'blocks/open', [ 'key' => $key ] );
	[ $resaved ] = blocksCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $again['tree'] ?? [], 'texts' => $again['texts'] ?? [], 'base' => $again['stamp'] ?? '' ] );
	unset( $site['./nino/blocks/state'] );
	$loops[$from] = $made === 200 && str_contains( (string) ( $copy['source'] ?? '' ), '['. $call. ' /' ) === true && str_contains( (string) $copy['source'], ' .title' ) === true && ( $copy['missing'] ?? [ 'x' ] ) === [] && $resaved === 200 && str_contains( (string) ( \Nino\Blocks::get( $site, $key )['source'] ?? '' ), ' .title' ) === true;
}

check( 'a block with a loop is copied and saved over itself: the type of the elements the loop shows and the fields it reads of them are kept - the list and the table over elements of the Collections', array_values( $loops ) === [ true, true ] );

// the groups of the dialog "Insert block": the categories of the kernel in the order the owner chose - titles, static content, dynamic content, social proof, offer, action, contact -
// with the frames none of them, and a block whose template has a loop in the dynamic content, whatever category block.json says; what is stored is not touched
$catalog = \Nino\Modules\Builder\Library::all( $site );
$groups = array_column( $catalog['blocks'], 'group', 'key' );
$shown = array_filter( $catalog['blocks'], static fn( array $entry ): bool => in_array( $entry['group'], $catalog['categories'], true ) === false );
[ , $listing ] = blocksCall( $site, 'blocks/list' );
$listed = array_column( $listing['blocks'] ?? [], 'group', 'key' );
$frames = array_filter( \Nino\Blocks::all( $site ), static fn( array $block ): bool => $block['wrap'] !== 'section-wrap' );

check( 'the dialog groups the blocks as titles, static content, dynamic content, social proof, offer, action and contact - in that order, and no frame is a group of the page: the frame blocks of the kernel are no entry of it',
	$catalog['categories'] === [ 'intro', 'content', 'dynamic', 'trust', 'offer', 'action', 'contact' ] && in_array( 'frame', $catalog['categories'], true ) === false && $shown === [] && $frames !== [] && array_intersect( array_keys( $frames ), array_keys( $groups ) ) === [] );

check( 'a block whose template has a loop - the list and the table over elements - is dynamic content whatever category it has, a block without a loop keeps its category, and the category in block.json is not touched',
	( $groups['list-over-elements'] ?? '' ) === 'dynamic' && ( $groups['table-over-elements'] ?? '' ) === 'dynamic' && ( $groups['hero-full'] ?? '' ) === \Nino\Blocks::get( $site, 'hero-full' )['category'] && ( $groups['three-columns'] ?? '' ) === \Nino\Blocks::get( $site, 'three-columns' )['category']
	&& \Nino\Blocks::get( $site, 'list-over-elements' )['category'] !== 'dynamic' && in_array( 'dynamic', \Nino\Blocks::CATEGORIES, true ) === false );

check( 'the list of the panel Blocks says the same group of each block as the dialog does - the column of the category is the word of the group',																																																																																																																																																		count( array_filter( $listed, static fn( string $group ): bool => $group === 'dynamic' ) ) === 2 && count( array_filter( $listed, static fn( string $group ): bool => $group !== 'dynamic' ) ) > 0 );

// the empty column of the draft is a quiet place with a sentence - in the preview alone: what is saved has none, and what is sent is made plain text
[ $hinted, $held ] = blocksCall( $site, 'blocks/preview', [ 'key' => 'nach-dem-abbruch', 'tree' => blocksCall( $site, 'blocks/open', [ 'key' => 'nach-dem-abbruch' ] )[1]['tree'], 'texts' => [], 'hint' => 'Komponente <b>einfügen</b> [[x]]' ] );
$kept = \Nino\Filesystem::getFileContent( $site, \Nino\Preview::DIR. '/'. \Nino\Modules\Blocks::KIND. '/'. ( $held['token'] ?? 'none' ). '/draft.json', null );
[ , $unhinted ] = blocksCall( $site, 'blocks/preview', [ 'key' => 'nach-dem-abbruch', 'tree' => blocksCall( $site, 'blocks/open', [ 'key' => 'nach-dem-abbruch' ] )[1]['tree'], 'texts' => [] ] );
$kept2 = \Nino\Filesystem::getFileContent( $site, \Nino\Preview::DIR. '/'. \Nino\Modules\Blocks::KIND. '/'. ( $unhinted['token'] ?? 'none' ). '/draft.json', null );

check( 'a column with nothing in it is drawn in the preview as a place with the sentence the panel gives - as plain text, no markup and no fill of a request - and where there is no sentence it is left as it is; the files of the block have no such place',
	$hinted === 200 && str_contains( (string) ( $kept['source'] ?? '' ), 'blocks-place' ) === true && str_contains( (string) $kept['source'], 'Komponente einfügen' ) === true && str_contains( (string) $kept['source'], '<b>' ) === false && str_contains( (string) $kept['source'], '[[x]]' ) === false
	&& str_contains( (string) ( $kept2['source'] ?? '' ), 'blocks-place' ) === false && str_contains( (string) \Nino\Blocks::get( $site, 'nach-dem-abbruch' )['source'], 'blocks-place' ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF3. Each bar that is the only one\n";

$request = [];
$own = new ReflectionMethod( \Nino\Modules\Blocks\Admin::class, '_own' );
$names = new ReflectionMethod( \Nino\Modules\Blocks\Admin::class, '_names' );
$appData = $site;
$kept = [ $own->invokeArgs( null, [ &$appData, &$request, 'hero-full' ] ), $own->invokeArgs( null, [ &$appData, &$request, 'no-such-block' ] ), $own->invokeArgs( null, [ &$appData, &$request, '../mein-block' ] ), $own->invokeArgs( null, [ &$appData, &$request, 'mein-block' ] ) ];

check( 'the guard of the origin on its own: only the key of a block of the project is one - a block of Basis, a key that is none and a path are not', $kept === [ null, null, null, 'mein-block' ] );

$appData = $site;
$wrote = \Nino\Modules\Builder\Custom::replace( $appData, 'hero-full', [ 'json' => '{}', 'tpl' => '', 'texts' => [] ] );

check( 'the rule of the origin of Custom::replace on its own: a block of another source is no block of the project to write over - 404 - whatever the caller checked', ( $wrote['failure'][0] ?? 0 ) === 404 );

$marked = [];

foreach( [ '<b>x</b>', 'a <script>', 'a [[fill]]', "a\x07bell" ] as $name ) {
	$request = [];
	$appData = $site;
	$marked[] = $names->invokeArgs( null, [ &$appData, &$request, $name, 'name', 'de_DE' ] );
}

$request = [];
$appData = $site;
$clean = $names->invokeArgs( null, [ &$appData, &$request, [ 'de_DE' => 'Ein Name', 'en_US' => '' ], 'name', 'de_DE' ] );

check( 'the rule of the name on its own: markup, a bracket pair of a fill and a control character are no name of a block, and a name of the working language is the name in each language', $marked === [ null, null, null, null ] && $norm( (array) $clean ) === $norm( [ 'de_DE' => 'Ein Name', 'en_US' => 'Ein Name' ] ) );

// the way of the pages - "Save as my own block" - stays strict: a link that names a page the site has not is refused, and nothing of a request turns that off
$registry = \Nino\Components::components( $site );
$linked = [ 'n' => 'section-wrap', 's' => '', 'a' => [ 'id' => 'strikt', 'ground' => 'base' ], 'c' => [ [ 't' => "\n" ], [ 'n' => 'button', 's' => '~go', 'a' => [ 'href' => '~ziel' ], 'c' => null ], [ 't' => "\n" ] ] ];
$input = [ 'at' => '0', 'key' => 'strikt', 'name' => 'Strikt', 'description' => 'Strikt', 'category' => 'content', 'texts' => [ '/template/page-home/strikt/go' => [ 'de_DE' => 'Los' ], '/template/page-home/strikt/ziel' => [ 'de_DE' => 'page:/gibt-es-nicht' ] ], 'pictures' => [] ];
$appData = $site;
$strict = \Nino\Modules\Builder\Custom::parts( $appData, 'page-home', [ 'source' => '', 'texts' => [], 'slots' => [] ], [ $linked ], $input );
$loose = \Nino\Modules\Builder\Custom::parts( $appData, 'page-home', [ 'source' => '', 'texts' => [], 'slots' => [] ], [ $linked ], $input + [ 'lenient' => true ] );
$builderSource = (string) file_get_contents( dirname( __DIR__, 2 ). '/Builder/Admin/Admin.php' );

// a loop in a block shows the elements of a type of the project and reads fields of them: the type and the fields are sources of the elements - not of the page
$looping = [ 'n' => 'section-wrap', 's' => '', 'a' => [ 'id' => 'schleife', 'ground' => 'base' ], 'c' => [ [ 't' => "\n" ], [ 'n' => 'collection-list', 's' => '/_placeholder', 'a' => [], 'c' => [ [ 't' => "\n" ], [ 'n' => 'text', 's' => '.title', 'a' => [], 'c' => null ], [ 't' => "\n" ] ] ], [ 't' => "\n" ] ] ];
$appData = $site;
$looped = \Nino\Modules\Builder\Custom::parts( $appData, 'page-home', [ 'source' => '', 'texts' => [], 'slots' => [] ], [ $looping ], [ 'at' => '0', 'key' => 'schleife', 'name' => 'Schleife', 'description' => 'Schleife', 'category' => 'content', 'texts' => [], 'pictures' => [] ] );
$outside = [ 'n' => 'section-wrap', 's' => '', 'a' => [ 'id' => 'draussen', 'ground' => 'base' ], 'c' => [ [ 't' => "\n" ], [ 'n' => 'text', 's' => '.title', 'a' => [], 'c' => null ], [ 't' => "\n" ] ] ];
$apart = \Nino\Modules\Builder\Custom::parts( $appData, 'page-home', [ 'source' => '', 'texts' => [], 'slots' => [] ], [ $outside ], [ 'at' => '0', 'key' => 'draussen', 'name' => 'Draussen', 'description' => 'Draussen', 'category' => 'content', 'texts' => [], 'pictures' => [] ] );

$real = \Nino\Modules\Builder\Custom::parts( $appData, 'page-home', [ 'source' => '', 'texts' => [], 'slots' => [] ], [ array_replace_recursive( $looping, [ 'c' => [ 1 => [ 's' => '/services' ] ] ] ) ], [ 'at' => '0', 'key' => 'echt', 'name' => 'Echt', 'description' => 'Echt', 'category' => 'content', 'texts' => [], 'pictures' => [] ] );

check( 'a loop of a block of the library shows the placeholder elements and reads fields of them, and a loop over a real type is refused with the sentence that the type is chosen after putting it in - 400 - and a field of the elements where no loop is above is no source of a block: refused as foreign',
	isset( $looped['failure'] ) === false && ( $real['failure'][2] ?? '' ) === 'builder_own_loop' && str_contains( (string) ( $looped['parts']['tpl'] ?? '' ), '[collection-list /_placeholder' ) === true && str_contains( (string) $looped['parts']['tpl'], '[text .title]' ) === true && ( $apart['failure'][2] ?? '' ) === 'builder_own_foreign' );

[ , $typesAsked ] = blocksCall( $site, 'blocks/keys', [ 'kind' => 'types' ] );

check( 'the editor of the blocks offers one type for the loop of a block of the library - the placeholder elements - whatever types the project has, and the sentence that says why is in both languages',
	array_column( $typesAsked['types'] ?? [], 'type' ) === [ '/_placeholder' ] && str_contains( (string) ( ( require $feature. '/text/de_DE.php' )['[[/_admin/error/blocks_loop]]'] ?? '' ), 'Platzhalter-Elemente' ) === true && isset( ( require $feature. '/text/en_US.php' )['[[/_admin/error/blocks_loop]]'] ) === true );

check( 'the way of the pages stays strict: a link to a page the site has not is refused by Custom::parts - 400 - unless the caller says lenient, and no action reads that word from a request - the one that adopts a block of a page throws it away, and the word stands nowhere else in the Builder\'s API',
	( $strict['failure'][0] ?? 0 ) === 400 && ( $strict['failure'][2] ?? '' ) === 'builder_own_address' && isset( $loose['failure'] ) === false && substr_count( $builderSource, 'lenient' ) === 1 && str_contains( $builderSource, "unset( \$data['lenient'] )" ) === true && str_contains( (string) file_get_contents( dirname( __DIR__ ). '/Admin/Admin.php' ), "'lenient' => true" ) === true );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nG. What the panel is built of\n";

$ownFunctions = [ 'Nino.adminUi.tree(', 'Nino.adminUi.previewFrame(', 'Nino.adminUi.hoverSync(', 'Nino.adminUi.split(', 'Nino.adminUi.saveBar(', 'Nino.adminUi.contextBar(', 'Nino.adminUi.previewTools(' ];

check( 'the panel draws the editor with the editor of the Builder and with nothing of its own: no tree, no frame, no hover switchboard, no bar - it opens a sheet through Nino.admin.builder.editor, names its adapter and draws the tiles through it',
	array_filter( $ownFunctions, static fn( string $call ): bool => str_contains( $script, $call ) === true ) === [] && str_contains( $script, 'editor().open( adapterOf(' ) === true && str_contains( $script, 'editor().tile( box, tiles() )' ) === true && str_contains( $script, 'Nino.admin.builder.editor' ) === true
	&& str_contains( $script, 'panel		: \'blocks\'' ) === true && str_contains( $script, 'sources	: \'block\'' ) === true
	&& preg_match( '/workLocale\.follow\( \'blocks\', function\(\) \{\s*\/\/[^\n]*\n\s*if\( here\(\)\.panel !== \'blocks\' \)\s*return;/', $script ) === 1
	&& str_contains( $script, 'Nino.adminUi.notice( Nino.adminUi.api.errorText(' ) === true && str_contains( $script, "'nino-admin-notice'" ) === false );

// The script of the panel has a suite of its own, which runs where node is
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' )
	echo "  --  - node is not available here: blocks-js-smoke.js was NOT run\n";
else {

	$output = [];
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/blocks-js-smoke.js' ). ' 2>&1', $output, $exit );

	if( $exit !== 0 )
		echo implode( "\n", array_slice( $output, 0, 12 ) ), "\n";

	check( 'blocks-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $site );
