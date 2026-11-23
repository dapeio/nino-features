<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-frames-editor-smoke.php	Contract test for the editor of the frames of the site in the panel of the pages - the header and the footer, written there and nowhere else:
 *
 *												A. the list, the open and the save of a frame by the step of the design: what the frame is, the stamp, the state before; the step the account has for a frame
 *												B. what only the right of Themelab adds: the call of html in a frame - offered, saved, drawn - for the account that designs and holds it, and never in a block of the library
 *												C. what nobody else writes: a frame by the actions of a page, by the step of the content or by the right of Themelab alone, the actions that Themelab had, and a request that
 *												   brings a step of its own
 *												D. the draft of a frame: a token of the kind of a frame, held for the door of the panel and drawn only for an account that still designs, a second session that is told
 *
 *	Usage: php features/Builder/tests/builder-frames-editor-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-frames-editor-smoke.php
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

$site = ninoSandbox( 'builder-frames-editor' );
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
echo "\nA. A frame by the step of the design\n";

$DESIGN = [ '/_admin/builder/manage', '/_admin/builder/design/*' ];
$LAB = \Nino\Modules\Themelab::PERM;

ninoUser( $site, 'designer@example.com', $DESIGN );
[ $status, $list ] = framesCall( $site, 'builder/frame-list' );
[ $opened, $designed ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
ninoUser( $site, 'developer@example.com', array_merge( $DESIGN, [ $LAB ] ) );
[ , $frame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );

check( 'the frames are listed for the step of the design in the panel of the pages - header and footer, as files of templates - and a frame opens as the sheet of a page does: its tree, a stamp, and the step the account has for it: the design, and the development where the account holds the right of Themelab too',
	$status === 200 && array_column( $list['frames'], 'template' ) === [ 'frame-header', 'frame-footer' ] && $opened === 200 && ( $designed['frame'] ?? false ) === true && ( $designed['level'] ?? 0 ) === \Nino\Modules\Builder::LEVEL_DESIGN && ( $designed['stamp'] ?? '' ) !== ''
	&& ( $frame['level'] ?? 0 ) === \Nino\Modules\Builder::LEVEL_DEVELOP );

ninoUser( $site, 'designer@example.com', $DESIGN );
[ , $frame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$sticky = ( static function( array $nodes ): array { unset( $nodes[0]['a']['sticky'] ); return $nodes; } )( $frame['tree'] );
[ $saved, $answer ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $sticky, 'texts' => [], 'base' => $frame['stamp'] ] );

check( 'a frame is saved where the Builder has always saved it - the template of the frame - in one line, the state before kept for the last state, and the stamp moves on',
	$saved === 200 && str_contains( framesFile( $site, 'frame-header' ), 'sticky' ) === false && Store::previous( $site, 'frame-header' ) !== null && ( $answer['stamp'] ?? '' ) !== $frame['stamp'] );

[ $stale ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'] ] );
[ $back, $last ] = framesCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header', 'base' => $answer['stamp'] ] );

check( 'a second session that opened the frame earlier is told, 409, and nothing is written; the last state brings the frame back as it was',
	$stale === 409 && $back === 200 && str_contains( framesFile( $site, 'frame-header' ), 'sticky' ) === true );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The call of html\n";

[ , $frame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$actions = ( static function( array $nodes ): string { foreach( $nodes[0]['c'] as $at => $inner ) if( ( $inner['n'] ?? '' ) === 'header-actions' ) return '0.'. $at; return ''; } )( $frame['tree'] );
$withHtml = ( static function( array $nodes ) use ( $actions ): array { $at = (int) substr( $actions, 2 ); $nodes[0]['c'][$at]['c'] = [ [ 't' => "\n\t\t" ], [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<p>Hi</p>' ] ] ], [ 't' => "\n\t" ] ]; return $nodes; } )( $frame['tree'] );

// the account that designs and holds the right of Themelab is offered the call of html and writes it
ninoUser( $site, 'developer@example.com', array_merge( $DESIGN, [ $LAB ] ) );
[ $offerStatus, $offer ] = framesCall( $site, 'builder/frame-offer', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'at' => $actions ] );
[ $htmlSaved ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $withHtml, 'texts' => [], 'base' => $frame['stamp'] ] );

check( 'the call of html is offered in a frame to the account that designs and holds the right of Themelab and written there as it is - and drawn cleaned by the component where the frame stands',
	$offerStatus === 200 && in_array( 'html', array_column( $offer['offers'] ?? [], 'name' ), true ) === true && $htmlSaved === 200 && str_contains( framesFile( $site, 'frame-header' ), '[html]' ) === true
	&& str_contains( \Nino\Html::renderSource( $site, framesFile( $site, 'frame-header' ) ), '<p>Hi</p>' ) === true );

// the account that only designs is offered none and writes none
ninoUser( $site, 'designer@example.com', $DESIGN );
[ , $plain ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
[ , $plainOffer ] = framesCall( $site, 'builder/frame-offer', [ 'template' => 'frame-header', 'tree' => $plain['tree'], 'at' => $actions ] );
$kept = framesFile( $site, 'frame-header' );
$plainHtml = ( static function( array $nodes ) use ( $actions ): array { $at = (int) substr( $actions, 2 ); $nodes[0]['c'][$at]['c'] = [ [ 't' => "\n\t\t" ], [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<p>Other</p>' ] ] ], [ 't' => "\n\t" ] ]; return $nodes; } )( $plain['tree'] );
[ $plainSave ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $plainHtml, 'texts' => [], 'base' => $plain['stamp'] ] );

check( 'the call of html is no choice for the account that only designs - not offered, and a save that changes it is refused, 403, with the frame as it was',
	in_array( 'html', array_column( $plainOffer['offers'] ?? [], 'name' ), true ) === false && $plainSave === 403 && framesFile( $site, 'frame-header' ) === $kept );

ninoUser( $site, 'developer@example.com', array_merge( $DESIGN, [ $LAB ] ) );
[ , $block ] = framesCall( $site, 'themelab/offer', [ 'tree' => [ [ 'n' => 'section-wrap', 's' => '', 'a' => [ 'id' => 'x' ], 'c' => [ [ 't' => "\n" ] ] ] ], 'at' => '0' ] );

check( 'a block of the library has no call of html - its offer and its save refuse it - for the editor that has the right to write it in a frame',
	in_array( 'html', array_column( $block['offers'] ?? [], 'name' ), true ) === false );

// a block of the frame category takes the place of the frame: the editor offers the blocks of the kind of the frame - header blocks for the header, footer blocks for the footer, no section -
// and the preview and the save take what it put in
[ , $headerBlocks ] = framesCall( $site, 'builder/frame-blocks', [ 'template' => 'frame-header' ] );
[ , $footerBlocks ] = framesCall( $site, 'builder/frame-blocks', [ 'template' => 'frame-footer' ] );
$headerKeys = array_column( $headerBlocks['blocks'] ?? [], 'key' );
$footerKeys = array_column( $footerBlocks['blocks'] ?? [], 'key' );
$chosen = $headerKeys[0] ?? '';
[ $blockStatus, $block ] = framesCall( $site, 'builder/frame-block', [ 'template' => 'frame-header', 'key' => $chosen ] );
[ $wrong ] = framesCall( $site, 'builder/frame-block', [ 'template' => 'frame-header', 'key' => $footerKeys[0] ?? 'x' ] );
[ , $frame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$id = (string) ( $block['id'] ?? '' );
[ $replacedPreview ] = framesCall( $site, 'builder/frame-preview', [ 'template' => 'frame-header', 'tree' => $block['tree'] ?? [], 'texts' => [], 'inserted' => [ $id => $chosen ], 'token' => '' ] );
[ $replacedSave ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $block['tree'] ?? [], 'texts' => [], 'inserted' => [ $id => $chosen ], 'base' => $frame['stamp'] ] );
[ $notOffered ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $block['tree'] ?? [], 'texts' => [], 'inserted' => [ $id => $footerKeys[0] ?? 'x' ], 'base' => $frame['stamp'] ] );

check( 'a frame is replaced by a block of its own kind: the header is offered the header blocks and the footer the footer blocks, never a section; the block that was chosen is taken by the preview and by the save, and a block of the other kind is refused',
	$headerKeys !== [] && $footerKeys !== [] && array_intersect( $headerKeys, $footerKeys ) === [] && $blockStatus === 200 && $wrong === 404 && $replacedPreview === 200 && $replacedSave === 200 && $notOffered === 403 && ( $headerBlocks['categories'] ?? [] ) !== [] );

// the notation of a component - the line the developer copies - is the development's: asked for a frame it is answered to the account that designs and holds the right of Themelab, and for a block of the library by Themelab itself
[ , $frame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
[ $notationStatus, $notation ] = framesCall( $site, 'builder/frame-notation', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'look' => [ 'block' => '0', 'node' => '0' ] ] );
[ $blockNotation, $ofBlock ] = framesCall( $site, 'themelab/notation', [ 'tree' => $block['tree'] ?? [], 'look' => [ 'block' => '0', 'node' => '0' ] ] );
ninoUser( $site, 'designer@example.com', $DESIGN );
[ $plainNotation ] = framesCall( $site, 'builder/frame-notation', [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'look' => [ 'block' => '0', 'node' => '0' ] ] );

check( 'the notation of a component is answered for a frame to the account that designs and holds the right of Themelab and to none other, and for a block of the library by Themelab; the panel of the pages has no action of its own for it',
	$notationStatus === 200 && str_contains( (string) ( $notation['notation']['node'] ?? '' ), '[header-wrap' ) === true && $plainNotation === 403 && $blockNotation === 200 && ( $ofBlock['notation']['node'] ?? '' ) !== '' && isset( \Nino\Modules\Builder\Admin::actions()['builder/notation'] ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. What nobody else writes\n";

$before = framesFile( $site, 'frame-header' );
$answers = [];

foreach( [ 'builder/open', 'builder/save', 'builder/preview', 'builder/restore', 'builder/blocks', 'builder/block', 'builder/tile', 'builder/variant', 'builder/keys', 'builder/upload', 'builder/unstage' ] as $action )
	$answers[$action] = framesCall( $site, $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true, 'key' => 'header-line', 'kind' => 'text', 'uri' => 'x', 'files' => [] ] )[0];

check( 'the actions of a page reach no frame - 403 for the account that designs and for the account that designs and holds the right of Themelab - and the file is as it was',
	array_unique( array_values( $answers ) ) === [ 403 ] && framesFile( $site, 'frame-header' ) === $before );

ninoUser( $site, 'developer@example.com', array_merge( $DESIGN, [ $LAB ] ) );
$answersLab = [];

foreach( [ 'builder/open', 'builder/save', 'builder/preview', 'builder/restore' ] as $action )
	$answersLab[$action] = framesCall( $site, $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0];

check( 'the account that holds the right of Themelab too is given the frame by the actions made for a frame and by none of a page: the same refusals, 403',
	array_unique( array_values( $answersLab ) ) === [ 403 ] && framesFile( $site, 'frame-header' ) === $before );

ninoUser( $site, 'writer@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$content = [];

foreach( [ 'frame-open', 'frame-save', 'frame-preview', 'frame-restore', 'frame-offer', 'frame-notation' ] as $action )
	$content[$action] = framesCall( $site, 'builder/'. $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true, 'at' => '0' ] )[0];

check( 'the step of the content has no frame in the editor - 403 for each action - and the file is as it was',
	array_unique( array_values( $content ) ) === [ 403 ] && framesFile( $site, 'frame-header' ) === $before );

ninoUser( $site, 'blocks-two@example.com', [ $LAB ] );
$labOnly = [];

foreach( [ 'frame-open', 'frame-save', 'frame-preview', 'frame-restore', 'frame-list' ] as $action )
	$labOnly[$action] = framesCall( $site, 'builder/'. $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] )[0];

check( 'the right of Themelab alone opens no frame: it is the right to write what goes beyond a design, and an account that does not hold the pages has no use of it - 403 for each action, and the file is as it was',
	array_unique( array_values( $labOnly ) ) === [ 403 ] && framesFile( $site, 'frame-header' ) === $before );

ninoUser( $site, 'developer@example.com', array_merge( $DESIGN, [ $LAB ] ) );
$gone = [];

foreach( [ 'open', 'preview', 'save', 'restore', 'blocks', 'block', 'tile', 'keys', 'variant', 'offer', 'notation', 'upload', 'unstage', 'create', 'rename', 'remove', 'adopt', 'list' ] as $action )
	$gone['themelab/frame-'. $action] = framesCall( $site, 'themelab/frame-'. $action, [ 'template' => 'frame-header', 'tree' => $frame['tree'], 'texts' => [], 'base' => $frame['stamp'], 'force' => true ] );

[ $nowhere ] = framesCall( $site, 'themelab/nowhere', [ 'template' => 'frame-header' ] );
[ $nowhereFrames ] = framesCall( $site, 'themelab/frames', [ 'locale' => 'en_US' ] );

check( 'the actions Themelab had for a frame are gone: each answers as an action that never was does, and the file is as it was',
	array_unique( array_map( static fn( array $answer ): int => $answer[0], array_values( $gone ) ) ) === [ $nowhere ] && $nowhereFrames === $nowhere && framesFile( $site, 'frame-header' ) === $before );

ninoUser( $site, 'designer@example.com', $DESIGN );
[ $notFrame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'page-home' ] );
[ $hostile ] = framesCall( $site, 'builder/frame-open', [ 'template' => '../config' ] );
[ , $plainFrame ] = framesCall( $site, 'builder/frame-open', [ 'template' => 'frame-header', 'level' => 3, 'develop' => true, 'as' => true, 'step' => 3, './nino/builder/as' => true ] );
[ , $tryOffer ] = framesCall( $site, 'builder/frame-offer', [ 'template' => 'frame-header', 'tree' => $plainFrame['tree'] ?? [], 'at' => $actions, 'level' => 3, 'develop' => true ] );
[ $tryHtml ] = framesCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $plainHtml, 'texts' => [], 'base' => $plainFrame['stamp'] ?? '', 'level' => 3, 'develop' => true, 'as' => true, 'step' => 3 ] );

check( 'the editor opens a frame and no page and no path through it - 400 - and the step is the account\'s: a request that names a step, a mark or a level of its own is given the one the account has - the design - and the call of html stays shut',
	$notFrame === 400 && $hostile === 400 && ( $plainFrame['level'] ?? 0 ) === \Nino\Modules\Builder::LEVEL_DESIGN && in_array( 'html', array_column( $tryOffer['offers'] ?? [], 'name' ), true ) === false && $tryHtml === 403 && framesFile( $site, 'frame-header' ) === $before );

// the last bolt of each door: no page is opened by the door of a frame, and the opening of a template in the Builder refuses a frame to every step but the one that the choice of the list gives it
ninoUser( $site, 'bolts@example.com', array_merge( $DESIGN, [ $LAB ] ) );
$opener = new ReflectionMethod( \Nino\Modules\Builder\Admin::class, '_open' );
$byStep = [];

foreach( [ \Nino\Modules\Builder::LEVEL_CONTENT, \Nino\Modules\Builder::LEVEL_DESIGN, \Nino\Modules\Builder::LEVEL_DEVELOP ] as $step )
	[ $byStep[$step] ] = ninoCall( $site, static function( array &$appData, array &$request ) use ( $opener, $step ): void {

		$opened = $opener->invokeArgs( null, [ &$appData, &$request, $step ] );

		if( $opened !== null )
			\Nino\Http::ok( $request, [ 'opened' => true ] );
	}, [ 'template' => 'frame-header', 'locale' => \Nino\Locales::getNativeLocale( $site ) ], 'builder/open' );

check( 'the opening of a template that is called from outside the editor of a frame refuses a frame to the content and the design on their own, and gives it to the whole of it that the choice of the list asks for',
	$byStep[\Nino\Modules\Builder::LEVEL_CONTENT] === 403 && $byStep[\Nino\Modules\Builder::LEVEL_DESIGN] === 403 && $byStep[\Nino\Modules\Builder::LEVEL_DEVELOP] === 200 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. The draft of a frame\n";

ninoUser( $site, 'framer@example.com', array_merge( $DESIGN, [ $LAB ] ) );
[ $previewStatus, $preview ] = framesCall( $site, 'builder/frame-preview', [ 'template' => 'frame-header', 'tree' => $withHtml, 'texts' => [], 'token' => '' ] );

check( 'the draft of a frame is a token of its own kind, held for the door of the panel - the draft of a page of the Builder is of another kind',
	$previewStatus === 200 && ( $preview['token'] ?? '' ) !== '' && \Nino\Modules\Builder::KIND_FRAME !== \Nino\Modules\Builder::KIND && \Nino\Modules\Builder::BRIDGE === \Nino\Modules\Themelab::PERM && \Nino\Modules\Builder::PERM !== \Nino\Modules\Builder::BRIDGE );

$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $preview['token'] ?? '' ) ] ] ];
$asked = static fn( string $kind ): ?array => ( static function( array &$site ) use ( $request, $kind ): ?array { return \Nino\Preview::held( $site, $request, $kind, \Nino\Modules\Builder::PERM ); } )( $site );
$asFrame = $asked( \Nino\Modules\Builder::KIND_FRAME ) !== null;
$asPage = $asked( \Nino\Modules\Builder::KIND ) !== null;

check( 'the preview of a frame is no draft of a page: it is held under the kind of a frame and under no other - an account that asks for it as a page is told nothing',
	$previewStatus === 200 && $asFrame === true && $asPage === false );

// the request of the Builder draws a frame draft for the account that designs and for no other: the account of the same session that lost the step keeps the door and is shown the site
$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $preview['token'] ?? '' ) ] ] ];
\Nino\Modules\Builder::request( $site, $request );
$drawnWith = \Nino\Preview::active( $site );
unset( $site['./nino/preview/held'] );
$site['./nino/auth/current']['perms'] = [ \Nino\Modules\Builder::PERM, \Nino\Modules\Builder::CONTENT ];
$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $preview['token'] ?? '' ) ] ] ];
\Nino\Modules\Builder::request( $site, $request );
$drawnWithout = \Nino\Preview::active( $site );
unset( $site['./nino/preview/held'] );
$site['./nino/auth/current']['perms'] = [ \Nino\Modules\Themelab::PERM ];
$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $preview['token'] ?? '' ) ] ] ];
\Nino\Modules\Builder::request( $site, $request );
$drawnLab = \Nino\Preview::active( $site );
unset( $site['./nino/preview/held'] );

check( 'the draft of a frame is drawn for the account that designs: an account of the same session that holds the door and the step of the content only, or the right of Themelab only, is given the page of the site and no draft',
	$drawnWith === true && $drawnWithout === false && $drawnLab === false );

ninoDone( $site );
