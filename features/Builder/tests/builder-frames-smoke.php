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

ninoUser( $site, 'writer@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/edit' ] );
$content = [ frameCall( $site, 'builder/framechoices', [ 'template' => 'frame-header' ] )[0], frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line' ] )[0], frameCall( $site, 'builder/framerestore', [ 'template' => 'frame-header', 'force' => true ] )[0] ];
[ , $contentList ] = frameCall( $site, 'builder/list' );
ninoUser( $site, 'themelab@example.com', [ '/_admin/themelab/manage' ] );
$other = [ frameCall( $site, 'builder/framechoices', [ 'template' => 'frame-header' ] )[0], frameCall( $site, 'builder/framechoose', [ 'template' => 'frame-header', 'key' => 'header-line' ] )[0] ];

check( 'the step of the content - the list gives it no frame and the three actions are 403 - and an account with the right of Themelab and no right of the Builder is refused: nothing is written',
	$content === [ 403, 403, 403 ] && ( $contentList['frames'] ?? null ) === [] && $other === [ 403, 403 ] && frameSnapshot( $site ) === $before );

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

foreach( [ 'builder/framechoices', 'builder/framechoose', 'builder/framerestore' ] as $action ) {

	$_POST['data'] = json_encode( [ 'template' => 'frame-header', 'key' => 'header-line', 'force' => true, 'locale' => \Nino\Locales::getNativeLocale( $site ) ] );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$untokened[$action] = $request['/nino/http/response']['statusCode'];
}

check( 'a request for the choice, the choices or the way back that comes without the token of the session is refused, 403, and nothing is written',
	array_unique( array_values( $untokened ) ) === [ 403 ] && frameSnapshot( $site ) === $snapshot );

check( 'the feature Themelab is not in this sandbox and the choice, the way back and the list have worked without it',
	in_array( '\\Nino\\Modules\\Themelab', (array) $site['/nino/modules'], true ) === false );

ninoDone( $site );
