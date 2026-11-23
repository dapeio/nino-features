<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-frames-smoke.php	Contract test for the choice of a frame in the list of the pages - a header or a footer given a block of its kind, with Themelab off:
 *
 *														A. who may: the design does, the content step and an account with no right of the Builder do not, and a request without the token of the session is refused
 *														B. what is chosen: only a block of the kind of the frame that the kernel offers - a block of a page, a key that is none and a template that is no frame are refused
 *														   and nothing is written - and the frame is written as the editor writes it: the block, its words, one last state to go back to
 *
 *														One sandbox with the kernel's Basis and the Builder - no Themelab: nothing in it names that feature.
 *
 *	Usage: php features/Builder/tests/builder-frames-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-frames-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/** One action of the API, as the workbench posts it: [ status, body ] */
function frameCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

/** The files of the project that the choice may write, by name: for "nothing is written" */
function frameSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text', 'private/data/builder' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

$site = ninoSandbox( 'builder-frames' );

$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', "[header-wrap id=\"header\"]\n\t[header-logo]\n[/header-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "[template /templates/html-header]\n[section-wrap id=\"x\"]\n\t[section-row]\n[/section-wrap]\n" );
foreach( [ 'de_DE', 'en_US' ] as $language )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $language. '.php', [ '[[/_nino/webpage/name]]' => 'Site' ] );

\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );

$before = frameSnapshot( $site );
[ $listStatus, $listed ] = frameCall( $site, 'builder/list' );
$stamp = array_column( $listed['frames'] ?? [], 'stamp', 'key' )['header'] ?? '';
[ $choices, $offer ] = frameCall( $site, 'builder/framechoices', [ 'template' => 'frame-header' ] );
$keys = array_column( $offer['blocks'] ?? [], 'key' );

check( 'the list of the pages has the two frames for the step of the design, and the choice for a header offers the blocks of the kind of a header - of every source, each with its origin - and no block of a page',
	$listStatus === 200 && array_column( $listed['frames'] ?? [], 'key' ) === [ 'header', 'footer' ] && $choices === 200 && in_array( 'header-line', $keys, true ) === true && in_array( 'hero-full', $keys, true ) === false && in_array( 'footer-columns', $keys, true ) === false
	&& array_filter( $offer['blocks'], static fn( array $entry ): bool => is_array( $entry['origin'] ?? null ) === false ) === [] );

// The origin of an entry is the kernel's word for it: a request that names another one changes nothing of it
[ , $lying ] = frameCall( $site, 'builder/framechoices', [ 'template' => 'frame-header', 'source' => 'theme-evil', 'origin' => [ 'kind' => 'theme', 'label' => 'Evil' ], 'applied' => true ] );
$entry = $lying['blocks'][0] ?? [];

check( 'each choice carries what the dialog groups by - the origin as the kernel names it (kind and label), the source key and whether the source is the theme in use - and a request that names an origin of its own does not change it',
	( $entry['origin']['kind'] ?? '' ) === 'basis' && ( $entry['source'] ?? '' ) === 'basis' && ( $entry['applied'] ?? null ) === false && is_array( $entry['origin']['label'] ?? null ) && $entry === ( $offer['blocks'][0] ?? null ) );

// The picture of a block is its tile, drawn the way the page tiles are, from the frame's own wrap
[ $tileStatus, $tile ] = frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'header-line' ] );
$tiles = [
	'a block of a page'				=> frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'hero-full' ] )[0],
	'a footer for a header'		=> frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'footer-columns' ] )[0],
	'a key that is none'			=> frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'nope' ] )[0],
	'a path for a key'				=> frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => '../../x' ] )[0],
	'a page for a frame'			=> frameCall( $site, 'builder/frametile', [ 'template' => 'page-home', 'key' => 'header-line' ] )[0],
	'no template'							=> frameCall( $site, 'builder/frametile', [ 'key' => 'header-line' ] )[0],
];

check( 'the picture of a block of the kind of the frame is its tile - a token, a query, a time and the address of the preview - and nothing is written; a block of a page, a footer for a header, a key that is none, a path and a template that is no frame are refused - 400 or 404',
	$tileStatus === 200 && is_string( $tile['token'] ?? null ) && $tile['token'] !== '' && is_string( $tile['address'] ?? null ) && $tile['address'] !== '' && isset( $tile['query'], $tile['ttl'] )
	&& array_filter( $tiles, static fn( int $status ): bool => in_array( $status, [ 400, 404 ], true ) === false ) === [] && frameSnapshot( $site ) === $before );

ninoUser( $site, 'writer@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/edit' ] );
$content = [ frameCall( $site, 'builder/framechoices', [ 'template' => 'frame-header' ] )[0], frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line' ] )[0], frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'force' => true ] )[0], frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'header-line' ] )[0] ];
[ , $contentList ] = frameCall( $site, 'builder/list' );
ninoUser( $site, 'themelab@example.com', [ '/_admin/themelab/manage' ] );
$other = [ frameCall( $site, 'builder/framechoices', [ 'template' => 'frame-header' ] )[0], frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line' ] )[0], frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'header-line' ] )[0] ];

check( 'the step of the content - the list gives it no frame and the four actions - the picture of a block as well - are 403 - and an account with the right of Themelab and no right of the Builder is refused: nothing is written',
	$content === [ 403, 403, 403, 403 ] && ( $contentList['frames'] ?? null ) === [] && $other === [ 403, 403, 403 ] && frameSnapshot( $site ) === $before );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$wrong = [
	'a block of a page for a frame'	=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'hero-full', 'base' => $stamp ] )[0],
	'a footer for a header'					=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'footer-columns', 'base' => $stamp ] )[0],
	'a key that is none'						=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'nope', 'base' => $stamp ] )[0],
	'a path for a key'							=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => '../../x', 'base' => $stamp ] )[0],
	'a page for a frame'						=> frameCall( $site, 'builder/framechoose', [ 'template' => 'page-home', 'key' => 'header-line', 'base' => $stamp ] )[0],
	'no template'										=> frameCall( $site, 'builder/framechoose', [ 'key' => 'header-line', 'base' => $stamp ] )[0],
];

check( 'what is chosen is the kernel\'s to say: a block of a page, a footer for a header, a key that is none or a path, and a template that is no frame are refused - 404 or 400 - and nothing is written',
	array_filter( $wrong, static fn( int $status ): bool => in_array( $status, [ 400, 404 ], true ) === false ) === [] && frameSnapshot( $site ) === $before );

[ $status, $done ] = frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line', 'base' => $stamp ] );
$written = (string) @file_get_contents( ninoSandboxDir( $site ). '/private/templates/frame-header.tpl' );
$block = \Nino\Blocks::get( $site, 'header-line' );
[ , $after ] = frameCall( $site, 'builder/list' );

check( 'a header given a block is that block in its file, with the id of the block, and the words of the block are the frame\'s under that id; the list says there is a last state to go back to',
	$status === 200 && ( $done['saved'] ?? false ) === true && str_contains( $written, '[header-wrap id="'. $block['id']. '"' ) === true && $written !== "[header-wrap id=\"header\"]\n\t[header-logo]\n[/header-wrap]\n"
	&& array_column( $after['frames'] ?? [], 'last', 'key' )['header'] > 0 );

[ $back ] = frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'base' => array_column( $after['frames'] ?? [], 'stamp', 'key' )['header'] ?? '' ] );
$restored = (string) @file_get_contents( ninoSandboxDir( $site ). '/private/templates/frame-header.tpl' );

check( 'the way back puts the frame back as it was: the file is the one before the choice',
	$back === 200 && $restored === "[header-wrap id=\"header\"]\n\t[header-logo]\n[/header-wrap]\n" );

// The list says the way back is there only while the frame is the one the choice wrote, and a frame that is another by now - a save in the editor of the frames came between - is neither
// chosen over nor restored: the stamp the list saw is asked, nothing is written, and the answer says why
$undoOf = static function() use ( &$site ): bool { return array_column( frameCall( $site, 'builder/list' )[1]['frames'] ?? [], 'undo', 'key' )['header'] ?? true; };
$stampOf = static function() use ( &$site ): string { return array_column( frameCall( $site, 'builder/list' )[1]['frames'] ?? [], 'stamp', 'key' )['header'] ?? ''; };
$offeredBefore = $undoOf();
frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line', 'base' => $stampOf() ] );
$offeredAfter = $undoOf();
$seen = $stampOf();
[ $wrongBase ] = frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'base' => 'not-a-stamp' ] );

// somebody saves the frame in the editor of the frames: its words change
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', (string) file_get_contents( ninoSandboxDir( $site ). '/private/templates/frame-header.tpl' ). "\n" );
$snapshot = frameSnapshot( $site );
[ $choseStale, $choseBody ] = frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line', 'base' => $seen ] );
[ $noBase ] = frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line' ] );
[ $backStale, $backBody ] = frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'base' => $seen ] );
[ $backForced ] = frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'base' => $seen, 'force' => true ] );
[ $backNow ] = frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'base' => $stampOf() ] );

check( 'the way back is offered after a choice and for the frame that choice wrote only; a frame that was changed since - its stamp is another - is not chosen over, not restored, not even with force: 409 with a code of its own and nothing is written',
	$offeredBefore === false && $offeredAfter === true && $wrongBase === 409 && $undoOf() === false && $choseStale === 409 && ( $choseBody['code'] ?? '' ) === 'builder_frame_changed' && $noBase === 409 && $backStale === 409 && ( $backBody['code'] ?? '' ) === 'builder_frame_changed' && $backForced === 409 && $backNow === 409 && frameSnapshot( $site ) === $snapshot );

// Reached through the workbench the way the browser reaches it - without the token of the session - each of the three is a 403 and nothing is written
$snapshot = frameSnapshot( $site );
$untokened = [];

foreach( [ 'builder/framechoices', 'builder/framechoose', 'builder/framerestore', 'builder/frametile' ] as $action ) {

	$_POST['data'] = json_encode( [ 'template' => 'frame-header', 'key' => 'header-line', 'force' => true, 'locale' => \Nino\Locales::getNativeLocale( $site ) ] );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$untokened[$action] = $request['/nino/http/response']['statusCode'];
}

check( 'a request for the choice, the choices or the way back that comes without the token of the session is refused, 403, and nothing is written',
	array_unique( array_values( $untokened ) ) === [ 403 ] && frameSnapshot( $site ) === $snapshot );

echo "\nC. The side a block is offered for is the one the schema of its root says - no name of a root decides\n";

// A feature of the test with a root for each side, none of them named like a root of the kernel's, and a block of each in the project
foreach( [ 'probe-top' => 'page', 'probe-head' => 'header', 'probe-foot' => 'footer' ] as $root => $side )
	\Nino\Components::addComponent( $site, $root, [ 'label' => $root, 'in' => [ 'page' ], 'offers' => [ 'flow' ], 'block' => $side, 'attributes' => [ 'id' => [ 'type' => 'id', 'default' => '' ] ], 'template' => '<div[[id]] class="[[class]]">[[content]]</div>' ] );

foreach( [ 'probe-top' => 'content', 'probe-head' => 'header', 'probe-foot' => 'footer' ] as $root => $category ) {

	$folder = ninoSandboxDir( $site ). '/private/blocks/'. $root. '-block';
	mkdir( $folder. '/text', 0755, true );
	file_put_contents( $folder. '/block.json', json_encode( [ 'name' => [ 'en_US' => $root ], 'description' => [ 'en_US' => $root ], 'category' => $category, 'rank' => 5, 'images' => (object) [] ] ) );
	file_put_contents( $folder. '/block.tpl', '['. $root. " id=\"x\"]\n[/". $root. "]\n" );
	file_put_contents( $folder. '/text/en_US.json', '{}' );
}

\Nino\Filesystem::putFileContent( $site, '/templates/page-side.tpl', "[template /templates/html-header]\n[section-wrap id=\"x\"]\n[/section-wrap]\n[template /templates/html-footer]\n" );
// Two blocks whose root is a component of a feature that is off - Menu is not on in this sandbox - one filed as a header and one as a block of the page: each is listed, grey, for its own side only
foreach( [ 'probe-off-head' => 'header', 'probe-off-page' => 'content' ] as $key => $category ) {

	$folder = ninoSandboxDir( $site ). '/private/blocks/'. $key;
	mkdir( $folder. '/text', 0755, true );
	file_put_contents( $folder. '/block.json', json_encode( [ 'name' => [ 'en_US' => $key ], 'description' => [ 'en_US' => $key ], 'category' => $category, 'rank' => 5, 'images' => (object) [] ] ) );
	file_put_contents( $folder. '/block.tpl', "[menu id=\"x\"]\n[/menu]\n" );
	file_put_contents( $folder. '/text/en_US.json', '{}' );
}

unset( $site['./nino/blocks/state'] );
$probes = [ 'probe-top-block', 'probe-head-block', 'probe-foot-block', 'probe-off-head', 'probe-off-page' ];
$offeredFor = static function( string $template ) use ( &$site, $probes ): array { return array_values( array_intersect( $probes, array_column( frameCall( $site, 'builder/framechoices', [ 'template' => $template ] )[1]['blocks'] ?? [], 'key' ) ) ); };
$forPage = array_values( array_intersect( $probes, array_column( frameCall( $site, 'builder/blocks', [ 'template' => 'page-side' ] )[1]['blocks'] ?? [], 'key' ) ) );

check( 'the blocks a header is offered are those of the side header, a footer those of the side footer, a page those of the side page - by the side the schema of the root says, for roots with names of their own; a block whose root a feature that is off brings stands, grey, in the list of its own side only',
	$offeredFor( 'frame-header' ) === [ 'probe-head-block', 'probe-off-head' ] && $offeredFor( 'frame-footer' ) === [ 'probe-foot-block' ] && $forPage === [ 'probe-top-block', 'probe-off-page' ] );

$stamp = $stampOf();
$footStamp = array_column( frameCall( $site, 'builder/list' )[1]['frames'] ?? [], 'stamp', 'key' )['footer'] ?? '';
$snapshot = frameSnapshot( $site );
$crossed = [
	'a block of the page for a header'		=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'probe-top-block', 'base' => $stamp ] )[0],
	'a block of a footer for a header'		=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'probe-foot-block', 'base' => $stamp ] )[0],
	'a block of a header for a footer'		=> frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-footer', 'key' => 'probe-head-block', 'base' => $footStamp ] )[0],
	'the picture of a footer for a header'	=> frameCall( $site, 'builder/frametile', [ 'template' => 'frame-header', 'key' => 'probe-foot-block' ] )[0],
	'a block of a header for a page'			=> frameCall( $site, 'builder/block', [ 'template' => 'page-side', 'key' => 'probe-head-block' ] )[0],
	'the picture of a footer for a page'	=> frameCall( $site, 'builder/tile', [ 'template' => 'page-side', 'key' => 'probe-foot-block' ] )[0],
];

check( 'a request that names a block of another side is refused, whatever the name of its root: a block of the page or of a footer for a header, one of a header for a footer, one of a header or a footer for a page - 400 or 404 - and nothing is written',
	array_filter( $crossed, static fn( int $status ): bool => in_array( $status, [ 400, 404 ], true ) === false ) === [] && frameSnapshot( $site ) === $snapshot );

[ $chosen ] = frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'probe-head-block', 'base' => $stamp ] );

check( 'the block of the side of the frame is chosen: the header is the block of the root the schema says is a header',
	$chosen === 200 && str_starts_with( (string) @file_get_contents( ninoSandboxDir( $site ). '/private/templates/frame-header.tpl' ), '[probe-head id="x"' ) === true );

check( 'the feature Themelab is not in this sandbox and the choice, the way back and the list have worked without it',
	in_array( '\\Nino\\Modules\\Themelab', (array) $site['/nino/modules'], true ) === false );

ninoDone( $site );
