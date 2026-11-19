<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-frames-smoke.php	Contract test for the frames of the site in the editor of the blocks - the header and the footer, written there and nowhere else:
 *
 *												A. the list, the open and the save of a frame by the right of the panel alone: what the frame is, the stamp, the state before
 *												B. what only this editor writes: the call of html in a frame - offered, saved, drawn - and never in a block of the library
 *												C. what nobody else writes: a frame by the steps of the panel of the pages, and the mark that opens it, which no request sets
 *												D. the draft of a frame: a token for the right of this panel, a page that is drawn with it, a second session that is told
 *												E. the way up from a page: the right is moved with the upgrade, nobody holds more than before, and the blocks of a page are given to the editor
 *
 *	Usage: php features/Themelab/tests/themelab-frames-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-frames-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;
use Nino\Modules\Builder\Tree;

function framesCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function framesFile( array &$appData, string $template ): string {
	global $sandbox;
	return (string) file_get_contents( $sandbox. '/private/templates/'. $template. '.tpl' );
}

$site = ninoSandbox( 'blocks-frames' );
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
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', "[header-wrap id=\"header\" sticky]\n\t[header-logo]\n\t[header-actions]\n\t[/header-actions]\n[/header-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => $locale === 'de_DE' ? 'Willkommen' : 'Welcome' ] );

$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. A frame by the right of the panel alone\n";

ninoUser( $site, 'blocks-only@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ $status, $list ] = framesCall( $site, 'themelab/frames' );
[ $opened, $frame ] = framesCall( $site, 'themelab/frame-open', [ 'template' => 'frame-header' ] );

check( 'the frames are listed by the right of this panel alone - header and footer, as files of templates - and a frame opens as the sheet of the Builder does: its tree, a stamp, the step of the development',
	$status === 200 && array_column( $list['frames'], 'template' ) === [ 'frame-header', 'frame-footer' ] && $opened === 200 && ( $frame['frame'] ?? false ) === true && ( $frame['level'] ?? 0 ) === \Nino\Modules\Builder::LEVEL_DEVELOP && ( $frame['stamp'] ?? '' ) !== '' );

$sticky = ( static function( array $nodes ): array { unset( $nodes[0]['a']['sticky'] ); return $nodes; } )( $frame['tree'] );
[ $saved, $answer ] = framesCall( $site, 'themelab/frame-save', [ 'template' => 'frame-header', 'tree' => $sticky, 'texts' => [], 'base' => $frame['stamp'] ] );

check( 'a frame is saved where the Builder has always saved it - the template of the frame - in one line, the state before kept for the last state, and the stamp moves on',
	$saved === 200 && str_contains( framesFile( $site, 'frame-header' ), 'sticky' ) === false && Store::previous( $site, 'frame-header' ) !== null && ( $answer['stamp'] ?? '' ) !== $frame['stamp'] );

[ $stale ] = framesCall( $site, 'themelab/frame-save', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'] ] );
[ $back, $last ] = framesCall( $site, 'themelab/frame-restore', [ 'template' => 'frame-header', 'base' => $answer['stamp'] ] );

check( 'a second session that opened the frame earlier is told, 409, and nothing is written; the last state brings the frame back as it was',
	$stale === 409 && $back === 200 && str_contains( framesFile( $site, 'frame-header' ), 'sticky' ) === true );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The call of html\n";

[ , $frame ] = framesCall( $site, 'themelab/frame-open', [ 'template' => 'frame-header' ] );
$actions = ( static function( array $nodes ): string { foreach( $nodes[0]['c'] as $at => $inner ) if( ( $inner['n'] ?? '' ) === 'header-actions' ) return '0.'. $at; return ''; } )( $frame['tree'] );
[ $offerStatus, $offer ] = framesCall( $site, 'themelab/frame-offer', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'at' => $actions ] );
$withHtml = ( static function( array $nodes ) use ( $actions ): array { $at = (int) substr( $actions, 2 ); $nodes[0]['c'][$at]['c'] = [ [ 't' => "\n\t\t" ], [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<p>Hi</p>' ] ] ], [ 't' => "\n\t" ] ]; return $nodes; } )( $frame['tree'] );
[ $htmlSaved ] = framesCall( $site, 'themelab/frame-save', [ 'template' => 'frame-header', 'tree' => $withHtml, 'texts' => [], 'base' => $frame['stamp'] ] );

check( 'the call of html is offered in a frame to the editor of the blocks and written there as it is - and drawn cleaned by the component where the frame stands',
	$offerStatus === 200 && in_array( 'html', array_column( $offer['offers'] ?? [], 'name' ), true ) === true && $htmlSaved === 200 && str_contains( framesFile( $site, 'frame-header' ), '[html]' ) === true
	&& str_contains( \Nino\Html::renderSource( $site, framesFile( $site, 'frame-header' ) ), '<p>Hi</p>' ) === true );

[ , $block ] = framesCall( $site, 'themelab/offer', [ 'tree' => [ [ 'n' => 'section-wrap', 's' => '', 'a' => [ 'id' => 'x' ], 'c' => [ [ 't' => "\n" ] ] ] ], 'at' => '0' ] );

check( 'a block of the library has no call of html - its offer and its save refuse it - for the editor that has the right to write it in a frame',
	in_array( 'html', array_column( $block['offers'] ?? [], 'name' ), true ) === false );

// a block of the frame category takes the place of the frame: the editor offers the blocks of the kind of the frame - header blocks for the header, footer blocks for the footer, no section -
// and the preview and the save take what it put in
ninoUser( $site, 'replacer@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ , $headerBlocks ] = framesCall( $site, 'themelab/frame-blocks', [ 'template' => 'frame-header' ] );
[ , $footerBlocks ] = framesCall( $site, 'themelab/frame-blocks', [ 'template' => 'frame-footer' ] );
$headerKeys = array_column( $headerBlocks['blocks'] ?? [], 'key' );
$footerKeys = array_column( $footerBlocks['blocks'] ?? [], 'key' );
$chosen = $headerKeys[0] ?? '';
[ $blockStatus, $block ] = framesCall( $site, 'themelab/frame-block', [ 'template' => 'frame-header', 'key' => $chosen ] );
[ $wrong ] = framesCall( $site, 'themelab/frame-block', [ 'template' => 'frame-header', 'key' => $footerKeys[0] ?? 'x' ] );
[ , $frame ] = framesCall( $site, 'themelab/frame-open', [ 'template' => 'frame-header' ] );
$id = (string) ( $block['id'] ?? '' );
[ $replacedPreview ] = framesCall( $site, 'themelab/frame-preview', [ 'template' => 'frame-header', 'tree' => $block['tree'] ?? [], 'texts' => [], 'inserted' => [ $id => $chosen ], 'token' => '' ] );
[ $replacedSave ] = framesCall( $site, 'themelab/frame-save', [ 'template' => 'frame-header', 'tree' => $block['tree'] ?? [], 'texts' => [], 'inserted' => [ $id => $chosen ], 'base' => $frame['stamp'] ] );
[ $notOffered ] = framesCall( $site, 'themelab/frame-save', [ 'template' => 'frame-header', 'tree' => $block['tree'] ?? [], 'texts' => [], 'inserted' => [ $id => $footerKeys[0] ?? 'x' ], 'base' => $frame['stamp'] ] );

check( 'a frame is replaced by a block of its own kind: the header is offered the header blocks and the footer the footer blocks, never a section; the block that was chosen is taken by the preview and by the save, and a block of the other kind is refused',
	$headerKeys !== [] && $footerKeys !== [] && array_intersect( $headerKeys, $footerKeys ) === [] && $blockStatus === 200 && $wrong === 404 && $replacedPreview === 200 && $replacedSave === 200 && $notOffered === 403 && ( $headerBlocks['categories'] ?? [] ) !== [] );

// the notation of a component - the line the developer copies - is the editor's: asked for a frame and for a block, it is answered to the right of this panel, and the panel of the pages has no such action
[ $notationStatus, $notation ] = framesCall( $site, 'themelab/frame-notation', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'look' => [ 'block' => '0', 'node' => '0' ] ] );
[ $blockNotation, $ofBlock ] = framesCall( $site, 'themelab/notation', [ 'tree' => $block['tree'] ?? [], 'look' => [ 'block' => '0', 'node' => '0' ] ] );

check( 'the notation of a component is answered in the editor of the blocks, for a frame and for a block of the library, and the panel of the pages has no action for it',
	$notationStatus === 200 && str_contains( (string) ( $notation['notation']['node'] ?? '' ), '[header-wrap' ) === true && $blockNotation === 200 && ( $ofBlock['notation']['node'] ?? '' ) !== '' && isset( \Nino\Modules\Builder\Admin::actions()['builder/notation'] ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. What nobody else writes\n";

ninoUser( $site, 'design@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$before = framesFile( $site, 'frame-header' );
$answers = [];

foreach( [ 'builder/open', 'builder/save', 'builder/preview' ] as $action )
	$answers[$action] = framesCall( $site, $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0];

foreach( [ 'themelab/frame-open', 'themelab/frame-save', 'themelab/frame-preview' ] as $action )
	$answers[$action] = framesCall( $site, $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0];

check( 'the steps of the panel of the pages reach no frame, and the panel of the blocks refuses the account that does not hold its right: 403 for each, and the file is as it was',
	array_unique( array_values( $answers ) ) === [ 403 ] && framesFile( $site, 'frame-header' ) === $before );

ninoUser( $site, 'blocks-two@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ $notFrame ] = framesCall( $site, 'themelab/frame-open', [ 'template' => 'page-home' ] );
[ $hostile ] = framesCall( $site, 'themelab/frame-open', [ 'template' => '../config' ] );
$site[\Nino\Modules\Builder::AS] = true;
unset( $site[\Nino\Modules\Builder::AS] );

check( 'the editor of the blocks opens a frame and no page and no path through it - 400 - and the mark that opens the frame to it is memory of a request: the word of a request does not set it',
	$notFrame === 400 && $hostile === 400 && isset( $site[\Nino\Modules\Builder::AS] ) === false && str_contains( (string) file_get_contents( NINO_FEATURES_DIR. '/Builder/Builder.php' ), "AS = './nino/builder/as'" ) === true );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. The draft of a frame\n";

[ $previewStatus, $preview ] = framesCall( $site, 'themelab/frame-preview', [ 'template' => 'frame-header', 'tree' => $withHtml, 'texts' => [], 'token' => '' ] );

check( 'the draft of a frame is a token of this editor: its own kind, held for the right of this panel - the draft of a page of the Builder is of another kind and of another right',
	$previewStatus === 200 && ( $preview['token'] ?? '' ) !== '' && \Nino\Modules\Builder::KIND_FRAME !== \Nino\Modules\Builder::KIND && \Nino\Modules\Builder::BRIDGE === \Nino\Modules\Themelab::PERM && \Nino\Modules\Builder::PERM !== \Nino\Modules\Builder::BRIDGE );

ninoUser( $site, 'framer@example.com', [ \Nino\Modules\Themelab::PERM, \Nino\Modules\Builder::PERM ] );
[ $framed, $token ] = framesCall( $site, 'themelab/frame-preview', [ 'template' => 'frame-header', 'tree' => $withHtml, 'texts' => [], 'token' => '' ] );
$asked = static fn( string $kind, string $perm ): ?array => ( static function( array &$site ) use ( $token, $kind, $perm ): ?array {

	$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $token['token'] ?? '' ) ] ] ];

	return \Nino\Preview::held( $site, $request, $kind, $perm );
} )( $site );
$asFrame = $asked( \Nino\Modules\Builder::KIND_FRAME, \Nino\Modules\Themelab::PERM ) !== null;
$asPage = $asked( \Nino\Modules\Builder::KIND, \Nino\Modules\Builder::PERM ) !== null;

check( 'the preview of a frame is no draft of a page: it is held under the kind of a frame and under no other - an account that holds both rights asks for it as a page and is told nothing',
	$framed === 200 && $asFrame === true && $asPage === false );

// the request of the Builder draws a frame draft for the right of the editor of the blocks and for no other: the account that lost that right keeps the Builder and is shown the site
$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $token['token'] ?? '' ) ] ] ];
\Nino\Modules\Builder::request( $site, $request );
$drawnWith = \Nino\Preview::active( $site );
unset( $site['./nino/preview/held'] );
$site['./nino/auth/current']['perms'] = [ \Nino\Modules\Builder::PERM ];
$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $token['token'] ?? '' ) ] ] ];
\Nino\Modules\Builder::request( $site, $request );
$drawnWithout = \Nino\Preview::active( $site );
unset( $site['./nino/preview/held'] );

check( 'the draft of a frame is drawn for the right of the editor of the blocks: the account of the same session that holds only the Builder is given the page of the site and no draft',
	$drawnWith === true && $drawnWithout === false );

// the last bolt of each door: the gate of the Builder with the mark of the editor takes a frame and no page, and the opening of a template in the Builder refuses a frame to every step but the one that the editor gives
ninoUser( $site, 'bolts@example.com', [ \Nino\Modules\Themelab::PERM, \Nino\Modules\Builder::PERM ] );
$site[\Nino\Modules\Builder::AS] = true;
[ $pageWithMark ] = ninoCall( $site, [ \Nino\Modules\Builder\Admin::class, 'apiOpen' ], [ 'template' => 'page-home', 'locale' => \Nino\Locales::getNativeLocale( $site ) ], 'builder/open' );
unset( $site[\Nino\Modules\Builder::AS] );
$opener = new ReflectionMethod( \Nino\Modules\Builder\Admin::class, '_open' );
$byStep = [];

foreach( [ \Nino\Modules\Builder::LEVEL_CONTENT, \Nino\Modules\Builder::LEVEL_DESIGN, \Nino\Modules\Builder::LEVEL_DEVELOP ] as $step )
	[ $byStep[$step] ] = ninoCall( $site, static function( array &$appData, array &$request ) use ( $opener, $step ): void {

		$opened = $opener->invokeArgs( null, [ &$appData, &$request, $step ] );

		if( $opened !== null )
			\Nino\Http::ok( $request, [ 'opened' => true ] );
	}, [ 'template' => 'frame-header', 'locale' => \Nino\Locales::getNativeLocale( $site ) ], 'builder/open' );

check( 'the gate of the Builder with the mark takes no page - 403 - and the opening of a template refuses a frame to the content and the design on its own, and gives it to the step the editor of the blocks has',
	$pageWithMark === 403 && $byStep[\Nino\Modules\Builder::LEVEL_CONTENT] === 403 && $byStep[\Nino\Modules\Builder::LEVEL_DESIGN] === 403 && $byStep[\Nino\Modules\Builder::LEVEL_DEVELOP] === 200 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The way up\n";

$M = '/_admin/builder/manage';
$D = '/_admin/builder/develop/edit';
$B = '/_admin/themelab/manage';
$DS = '/_admin/builder/design/edit';
$site['/nino/auth/roles'] = [
	'dev'			=> [ 'label' => 'Dev', 'perms' => [ $M, $D ] ],
	'devonly'	=> [ 'label' => 'Dev without door', 'perms' => [ $D ] ],
	'door'		=> [ 'label' => 'Door', 'perms' => [ $M ] ],
	'design'	=> [ 'label' => 'Design', 'perms' => [ $M, $DS ] ],
	'content'	=> [ 'label' => 'Content', 'perms' => [ $M, '/_admin/builder/content/edit' ] ],
	'themelab'	=> [ 'label' => 'Themelab', 'perms' => [ $B ] ],
	'all'			=> [ 'label' => 'All', 'perms' => [ '/*' ] ],
	'panel'		=> [ 'label' => 'Panel', 'perms' => [ '/_admin/builder/*' ] ],
];
$site['/nino/auth/user'] = [
	'direct'		=> [ 'perms' => [ $M, $D ], 'role' => '' ],
	'wild'			=> [ 'perms' => [ $M, '/_admin/builder/develop/*' ], 'role' => '' ],
	'nodoor'		=> [ 'perms' => [ $D ], 'role' => '' ],
	'nodoorrole'=> [ 'perms' => [], 'role' => 'devonly' ],
	'doorplus'	=> [ 'perms' => [ $M ], 'role' => 'devonly' ],
	'devplus'		=> [ 'perms' => [ $D ], 'role' => 'door' ],
	'viarole'		=> [ 'perms' => [], 'role' => 'dev' ],
	'plain'			=> [ 'perms' => [ $M, $DS ], 'role' => '' ],
];
$before = $site;
$gone = \Nino\Modules\Builder::upgrade( $site, '1.0.0' );
$r = static fn( string $id ): array => $site['/nino/auth/roles'][$id]['perms'];
$u = static fn( string $mail ): array => $site['/nino/auth/user'][$mail]['perms'];
$can = static fn( string $mail ): bool => \Nino\Auth::holds( \Nino\Auth::permissions( $site, $site['/nino/auth/user'][$mail] ), '/_admin/themelab/manage' );
$again = $site;
\Nino\Modules\Builder::upgrade( $again, '1.0.0' );

check( 'the upgrade gives the right of the editor of the blocks to what could develop - the door and the step together, by an own permission, a blanket, a wildcard or a role - and to nothing else: the step without the door, in an account or in a role, gets nothing and loses the dead permission; and it is the same the second time',
	$gone === true && $r( 'dev' ) === [ $M, $B, $DS ] && $r( 'devonly' ) === [] && $r( 'door' ) === [ $M ] && $r( 'design' ) === [ $M, $DS ] && $r( 'content' ) === $before['/nino/auth/roles']['content']['perms'] && $r( 'themelab' ) === [ $B ] && $r( 'all' ) === [ '/*' ] && $r( 'panel' ) === [ '/_admin/builder/*', $B ]
	&& $u( 'direct' ) === [ $M, $B, $DS ] && $u( 'wild' ) === [ $M, $B, $DS ] && $u( 'nodoor' ) === [] && $can( 'nodoor' ) === false && $can( 'nodoorrole' ) === false && $u( 'nodoorrole' ) === []
	&& $can( 'doorplus' ) === true && in_array( $B, $u( 'doorplus' ), true ) === true && $can( 'devplus' ) === true && in_array( $B, $u( 'devplus' ), true ) === true && $can( 'viarole' ) === true && $u( 'viarole' ) === [] && $u( 'plain' ) === [ $M, $DS ]
	&& $again['/nino/auth/roles'] === $site['/nino/auth/roles'] && $again['/nino/auth/user'] === $site['/nino/auth/user'] );

// the move is made once: an update that comes from the version with the move, or later, gives nobody the right - not back to the account it was taken from, not to one that was made after
// the move with the blanket of the panel - and writes nothing where nothing moved
$config = static fn(): string => (string) @hash_file( 'sha256', $sandbox. '/private/config.php' );
$late = $site;
$late['/nino/auth/roles'] = [ 'panel' => [ 'label' => 'Panel', 'perms' => [ '/_admin/builder/*' ] ], 'dev' => [ 'label' => 'Dev', 'perms' => [ $M, $D ] ] ];
$late['/nino/auth/user'] = [ 'taken' => [ 'perms' => [ '/_admin/builder/*' ], 'role' => '' ], 'made' => [ 'perms' => [ $M, $DS, '/_admin/builder/*' ], 'role' => '' ] ];
$was = $late;
$hash = $config();
$lateDone = \Nino\Modules\Builder::upgrade( $late, '1.1.0' ) === true && \Nino\Modules\Builder::upgrade( $late, '1.2.0' ) === true;
$quiet = $config() === $hash;
$first = $late;
\Nino\Modules\Builder::upgrade( $first, '1.0.0' );
$plain = $site;
$plain['/nino/auth/roles'] = [ 'design' => [ 'label' => 'Design', 'perms' => [ $M, $DS ] ] ];
$plain['/nino/auth/user'] = [];
$hash = $config();
\Nino\Modules\Builder::upgrade( $plain, '1.0.0' );

check( 'the move of the right is made once: an update from the version with the move or from a later one gives nobody the right - the account that had it taken away keeps it taken away, the one made later with the blanket of the panel has none - and an update that moves nothing writes no configuration',
	$lateDone === true && $late['/nino/auth/roles'] === $was['/nino/auth/roles'] && $late['/nino/auth/user'] === $was['/nino/auth/user'] && $quiet === true
	&& in_array( $B, $first['/nino/auth/roles']['panel']['perms'], true ) === true && in_array( $B, $first['/nino/auth/roles']['dev']['perms'], true ) === true && $config() === $hash );

ninoUser( $site, 'old@example.test', [ '/_admin/builder/manage', '/_admin/builder/develop/edit' ] );

check( 'the account of the development that nobody upgraded still designs - the step is gone and what it was is no more than the design here - and has no right of the editor of the blocks it was not given',
	\Nino\Modules\Builder::level( $site ) === \Nino\Modules\Builder::LEVEL_DESIGN && \Nino\Auth::checkPermission( $site, \Nino\Modules\Themelab::PERM ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nF. The move from the feature Blocks\n";

$OLD = \Nino\Modules\Themelab::BEFORE;
$moving = $site;
$moving['/nino/auth/roles'] = [
	'worker'	=> [ 'label' => 'Worker', 'perms' => [ $M, $OLD ] ],
	'wild'		=> [ 'label' => 'Wild', 'perms' => [ '/_admin/blocks/*' ] ],
	'nodoor'	=> [ 'label' => 'Develops without a door', 'perms' => [ $D ] ],
	'panel'		=> [ 'label' => 'Panel', 'perms' => [ '/_admin/builder/*' ] ],
	'all'			=> [ 'label' => 'All', 'perms' => [ '/*' ] ],
	'site'		=> [ 'label' => 'Site', 'perms' => [ '/_admin/*' ] ],
	'develop'	=> [ 'label' => 'Develops', 'perms' => [ $M, $D ] ],
];
$moving['/nino/auth/user'] = [
	'mine'		=> [ 'perms' => [ $OLD, $M ], 'role' => '' ],
	'star'		=> [ 'perms' => [ '/_admin/*' ], 'role' => '' ],
	'devs'		=> [ 'perms' => [ $D ], 'role' => '' ],
	'plain'		=> [ 'perms' => [ $M ], 'role' => '' ],
];
unset( $moving[\Nino\Modules\Themelab::MOVED] );
$moving['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Blocks' ], array_values( (array) ( $moving['/nino/modules'] ?? [] ) ) );
\Nino\Filesystem::mutate( $moving, '/config.php', static function( array $content ) use ( $moving ): array {

	$content['/nino/modules'] = $moving['/nino/modules'];
	unset( $content[\Nino\Modules\Themelab::MOVED] );
	$content['/nino/auth/roles'] = $moving['/nino/auth/roles'];
	$content['/nino/auth/user'] = $moving['/nino/auth/user'];
	$content[\Nino\Features::STATE_KEY]['blocks'] = [ 'version' => '2.0.0', 'settings' => [] ];

	return $content;
} );
$moving['./nino/auth/baseline'] = $moving['/nino/auth/user'];
\Nino\Modules\Themelab::init( $moving );

$rol = static function( string $id ) use ( &$moving ): array { return $moving['/nino/auth/roles'][$id]['perms']; };
$usr = static function( string $mail ) use ( &$moving ): array { return $moving['/nino/auth/user'][$mail]['perms']; };
$stored = (array) \Nino\Filesystem::getFileContent( $moving, '/config.php', [] );

check( 'the first request after Themelab is on gives the right of it to what named the right of Blocks - the entry, or the entry of all of its rights, in a role and in an account - and takes the old one away; a blanket, the step without a door and an account that never had it get nothing, and nothing else is given',
	$rol( 'worker' ) === [ $M, \Nino\Modules\Themelab::PERM ] && $rol( 'wild' ) === [ '/_admin/themelab/*' ] && $rol( 'nodoor' ) === [ $D ] && $rol( 'panel' ) === [ '/_admin/builder/*' ] && $rol( 'all' ) === [ '/*' ] && $rol( 'site' ) === [ '/_admin/*' ] && $rol( 'develop' ) === [ $M, $D ] && $usr( 'star' ) === [ '/_admin/*' ] && $usr( 'devs' ) === [ $D ]
	&& $usr( 'mine' ) === [ \Nino\Modules\Themelab::PERM, $M ] && $usr( 'plain' ) === [ $M ] && in_array( $OLD, $rol( 'worker' ), true ) === false );

check( 'what the old feature left in config.php is taken with it - its class in the list of the modules and the record of its version - and the mark of the move is written, so that the move is made once',
	in_array( '\\Nino\\Modules\\Blocks', array_map( static fn( mixed $class ): string => '\\'. ltrim( (string) $class, '\\' ), (array) ( $stored['/nino/modules'] ?? [] ) ), true ) === false && isset( $stored[\Nino\Features::STATE_KEY]['blocks'] ) === false && ( $stored[\Nino\Modules\Themelab::MOVED] ?? false ) === true && $moving[\Nino\Modules\Themelab::MOVED] === true );

// a right of the old name that is written later - a restored backup, a hand - is not moved again: the mark says it was made
$moving['/nino/auth/roles']['late'] = [ 'label' => 'Late', 'perms' => [ $OLD ] ];
\Nino\Modules\Themelab::init( $moving );

check( 'the move is made once: a role that names the old right after the mark is written is left as it is - Themelab gives nothing a second time',
	$rol( 'late' ) === [ $OLD ] );

// the mark is asked again under the lock config.php is written under: a request that read the roles before another moved them - and so has no mark in memory - finds the mark in the file, writes nothing, and
// takes the mark into memory
$settled = (string) @hash_file( 'sha256', $sandbox. '/private/config.php' );
$stale = $moving;
unset( $stale[\Nino\Modules\Themelab::MOVED] );
$stale['/nino/auth/roles']['late'] = [ 'label' => 'Late', 'perms' => [ $OLD ] ];
\Nino\Modules\Themelab::move( $stale );

check( 'a request that comes with no mark in memory while the file has it moves nothing under the lock: config.php is as it was, the role that names the old right is as it was, the mark is in memory after',
	(string) @hash_file( 'sha256', $sandbox. '/private/config.php' ) === $settled && $stale['/nino/auth/roles']['late']['perms'] === [ $OLD ] && $stale[\Nino\Modules\Themelab::MOVED] === true );

ninoDone( $site );
