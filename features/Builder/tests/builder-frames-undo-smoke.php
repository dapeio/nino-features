<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-frames-undo-smoke.php	Contract test for the way back of the choice of a block for a frame: it brings back exactly the frame the server kept before that choice, or nothing. The mark of
 *													the choice holds the stamp of the frame the choice wrote and the stamp of the frame it replaced; the way back needs the frame to be the first and the last state to be the second,
 *													and every later write of the frame takes the mark away - two guards for one right. The list offers the way back exactly where it goes
 *
 *	Usage: php features/Builder/tests/builder-frames-undo-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-frames-undo-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;

function undoCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

$site = ninoSandbox( 'builder-frames-undo' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$head = static fn( string $id, bool $html = false ): string => "[header-wrap id=\"". $id. "\"]\n\t[header-logo]\n\t[header-actions]\n". ( $html === true ? "\t\t[html]\n\t\t\t<p>Mark ". $id. "</p>\n\t\t[/html]\n" : '' ). "\t[/header-actions]\n[/header-wrap]\n";
$files = [ 'frame-header' => $head( 'header' ), 'frame-footer' => "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" ];

foreach( [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm' ] as $word )
	$files['frame-header-'. $word] = $head( $word, in_array( $word, [ 'a', 'c', 'e', 'g', 'i', 'k' ], true ) );

foreach( $files as $name => $source )
	\Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/frame-header-a/a/title]]' => 'Titel' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
\Nino\Filesystem::putFileContent( $site, '/templates/page-contact.tpl', "<!-- nino:template-name Contact -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];

$LAB = \Nino\Modules\Themelab::PERM;
$DESIGN = [ '/_admin/builder/manage', '/_admin/builder/design/*' ];
$as = static function( array &$site, string $who ) use ( $DESIGN, $LAB ): void { ninoUser( $site, $who. '@example.com', $who === 'designlab' ? array_merge( $DESIGN, [ $LAB ] ) : $DESIGN ); };
$file = static fn( string $template ): string => (string) file_get_contents( ninoSandboxDir( $GLOBALS['site'] ). '/private/templates/'. $template. '.tpl' );
$stampOf = static function( array &$site, string $template ): string { $state = Store::state( $site, $template ); return $state === null ? '' : Store::stamp( $state ); };
$row = static function( array &$site, string $key ): array { [ , $list ] = undoCall( $site, 'builder/frame-list' ); return array_column( $list['frames'] ?? [], null, 'key' )[$key] ?? []; };
$choose = static fn( array &$site, string $template ): int => undoCall( $site, 'builder/framechoose', [ 'template' => $template, 'key' => 'header-line', 'base' => $GLOBALS['stampOf']( $site, $template ) ] )[0];
$back = static fn( array &$site, string $template, array $extra = [] ): array => undoCall( $site, 'builder/framerestore', [ 'template' => $template, 'base' => $GLOBALS['stampOf']( $site, $template ) ] + $extra );
$save = static function( array &$site, string $template, callable $edit ): int {

	[ , $open ] = undoCall( $site, 'builder/frame-open', [ 'template' => $template ] );
	$tree = $edit( $open['tree'] );

	return undoCall( $site, 'builder/frame-save', [ 'template' => $template, 'tree' => $tree, 'texts' => $open['texts'], 'base' => $open['stamp'] ] )[0];
};
$other = static fn( array $tree ): array => ( static function( array $tree ): array { $tree[0]['a']['id'] = ( $tree[0]['a']['id'] ?? 'x' ). 'z'; return $tree; } )( $tree );
$mark = static fn( string $template ): string => $sandbox . '/private/data/builder/frame-choice/'. $template. '.php';

echo "\nA. The way back of a choice and the frame it brings back\n";

// a choice and at once its way back, for a frame with the call of html and for one without: the frame is as it was, to the byte
$as( $site, 'designer' );
$plain = [ $file( 'frame-header-b' ), $choose( $site, 'frame-header-b' ), ( $row( $site, 'header-b' )['undo'] ?? false ) ];
[ $plainBack ] = $back( $site, 'frame-header-b' );
$withHtml = [ $file( 'frame-header-a' ), $choose( $site, 'frame-header-a' ), ( $row( $site, 'header-a' )['undo'] ?? false ) ];
[ $htmlBack ] = $back( $site, 'frame-header-a' );

check( 'a choice taken back at once by the account that designs brings the frame back to the byte - for a frame without the call of html and for one with it - and the list offers the way back exactly then, and offers none after it',
	$plain[1] === 200 && $plain[2] === true && $plainBack === 200 && $file( 'frame-header-b' ) === $plain[0] && $withHtml[1] === 200 && $withHtml[2] === true && $htmlBack === 200 && $file( 'frame-header-a' ) === $withHtml[0]
	&& str_contains( $file( 'frame-header-a' ), 'Mark a' ) === true && ( $row( $site, 'header-a' )['undo'] ?? true ) === false && ( $row( $site, 'header-b' )['undo'] ?? true ) === false );

// the sequence that brings the stamp of the choice back without the frame being what it was: the account that designs chooses, the account that develops puts the call of html in and
// takes it out by its last state - the frame is the frame of the choice again, and its last state is the frame with the call of html
$as( $site, 'designer' );
$choose( $site, 'frame-header-c' );
$chosenFrame = $file( 'frame-header-c' );
$as( $site, 'designlab' );
$savedHtml = $save( $site, 'frame-header-c', static function( array $tree ): array {
	$put = static function( array &$nodes ) use ( &$put ): bool {
		foreach( $nodes as &$node ) {
			if( ( $node['n'] ?? '' ) === 'section-col' ) {
				$node['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => "\n\t\t\t<script>SECRET</script>\n\t\t" ] ] ];
				return true;
			}
			if( is_array( $node['c'] ?? null ) === true && $put( $node['c'] ) === true )
				return true;
		}
		return false;
	};
	$put( $tree );
	return $tree;
} );
[ $editorBack ] = undoCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header-c', 'base' => $stampOf( $site, 'frame-header-c' ) ] );
$sameAsChoice = $file( 'frame-header-c' ) === $chosenFrame;
$as( $site, 'designer' );
$offered = $row( $site, 'header-c' )['undo'] ?? true;
$before = $file( 'frame-header-c' );
[ $refusedBack ] = $back( $site, 'frame-header-c' );

check( 'a frame that goes back to the frame of a choice by a write of another account has no way back for the account that chose: the list offers none, the request is refused - 409 - and the frame is as it was, without the call of html that the other account took out',
	$savedHtml === 200 && $editorBack === 200 && $sameAsChoice === true && $offered === false && $refusedBack === 409 && $file( 'frame-header-c' ) === $before && str_contains( $file( 'frame-header-c' ), 'SECRET' ) === false );

// the account that develops may take the same choice back as it may take any last state back; and what another account saved after the choice takes the mark away
$as( $site, 'designer' );
$choose( $site, 'frame-header-d' );
$as( $site, 'designlab' );
$save( $site, 'frame-header-d', $other );
$offeredAfterSave = $row( $site, 'header-d' )['undo'] ?? true;
[ $afterSave ] = $back( $site, 'frame-header-d' );

check( 'a choice is no more to be taken back after the frame was saved by another account: the list offers none, the request is refused - 409',
	$offeredAfterSave === false && $afterSave === 409 );

// the mark belongs to its frame: a choice for one frame is no way back for another
$as( $site, 'designer' );
$choose( $site, 'frame-header-e' );
$beforeOther = $file( 'frame-header-f' );
[ $acrossStatus ] = undoCall( $site, 'builder/framerestore', [ 'template' => 'frame-header-f', 'base' => $stampOf( $site, 'frame-header-f' ) ] );

check( 'the way back of a choice for one frame is asked for another: refused - 409 - and the other is as it was',
	$acrossStatus === 409 && $file( 'frame-header-f' ) === $beforeOther && ( $row( $site, 'header-f' )['undo'] ?? true ) === false && ( $row( $site, 'header-e' )['undo'] ?? false ) === true );

// force is no word of this action: the frame the list saw is the frame that is restored
[ $forced ] = undoCall( $site, 'builder/framerestore', [ 'template' => 'frame-header-e', 'base' => 'not-the-stamp', 'force' => true ] );

check( 'a request that says force and names a stamp that is not the frame\'s is refused as one that does not - 409 - and the frame is as the choice left it',
	$forced === 409 && ( $row( $site, 'header-e' )['undo'] ?? false ) === true && str_contains( $file( 'frame-header-e' ), 'Mark e' ) === false );

echo "\nB. What takes the mark away\n";

// every write of the frame takes the mark away: a save, the editor's own way back, the texts, a rename - and a write that brings the very same frame back
$sequences = [];

foreach( [ 'save' => 'g', 'editor restore' => 'h', 'texts' => 'i', 'rename' => 'j' ] as $kind => $word ) {

	$template = 'frame-header-'. $word;
	$as( $site, 'designer' );
	$choose( $site, $template );
	$lived = $row( $site, 'header-'. $word )['undo'] ?? false;

	if( $kind === 'save' )
		$save( $site, $template, $other );
	else if( $kind === 'editor restore' )
		undoCall( $site, 'builder/frame-restore', [ 'template' => $template, 'base' => $stampOf( $site, $template ) ] );
	else if( $kind === 'texts' ) {
		[ , $texts ] = undoCall( $site, 'builder/frametexts', [ 'template' => $template ] );
		undoCall( $site, 'builder/frametextssave', [ 'template' => $template, 'texts' => [ '[[/template/'. $template. '/'. $word. '/title]]' => [ 'de_DE' => 'Neu' ] ], 'base' => $stampOf( $site, $template ) ] );
	}
	else
		undoCall( $site, 'builder/frame-rename', [ 'template' => $template, 'name' => [ 'de_DE' => 'Neu', 'en_US' => 'New' ] ] );

	$sequences[$kind] = [ $lived, $row( $site, 'header-'. $word )['undo'] ?? true ];
}

check( 'a choice offers its way back until the frame is written in any way - a save, the way back of the editor, the texts, a rename - and offers none after it',
	$sequences['save'] === [ true, false ] && $sequences['editor restore'] === [ true, false ] && $sequences['texts'][0] === true && $sequences['rename'] === [ true, false ] );

// a frame that is written and is written again to be what it was is no frame a choice wrote: the mark went with the first write
$as( $site, 'designer' );
$choose( $site, 'frame-header-l' );
$start = $file( 'frame-header-l' );
$as( $site, 'designlab' );
undoCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header-l', 'base' => $stampOf( $site, 'frame-header-l' ) ] );
undoCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header-l', 'base' => $stampOf( $site, 'frame-header-l' ) ] );
$as( $site, 'designer' );

check( 'a frame that was written twice and is the frame of the choice again has no way back for the account that chose: the list offers none and the request is refused - 409',
	$file( 'frame-header-l' ) === $start && ( $row( $site, 'header-l' )['undo'] ?? true ) === false && $back( $site, 'frame-header-l' )[0] === 409 );

echo "\nC. A mark that does not hold what the guards need\n";

// a mark with the stamp of the frame and a last state that is not the one the choice kept: refused, though the frame is the frame of the choice
$as( $site, 'designer' );
$choose( $site, 'frame-header-f' );
$forged = \Nino\Filesystem::getFileContent( $site, Store::FRAME_MARK. '/frame-header-f.php', [] );
\Nino\Filesystem::putFileContent( $site, Store::FRAME_MARK. '/frame-header-f.php', [ 'stamp' => $forged['stamp'], 'before' => hash( 'sha256', 'another last state' ) ] );
$offeredForged = $row( $site, 'header-f' )['undo'] ?? true;
[ $forgedBack ] = $back( $site, 'frame-header-f' );

check( 'a mark whose last state is not the one it kept is no way back: the list offers none and the request is refused - 409 - though the frame is the frame the choice wrote',
	$offeredForged === false && $forgedBack === 409 );

// a mark that an earlier version wrote has the stamp of the frame and none of the last state: it goes for the account that could take the last state back anyway, and for no other
$as( $site, 'designlab' );
$choose( $site, 'frame-header-k' );
$oldMark = \Nino\Filesystem::getFileContent( $site, Store::FRAME_MARK. '/frame-header-k.php', [] );
\Nino\Filesystem::putFileContent( $site, Store::FRAME_MARK. '/frame-header-k.php', [ 'stamp' => $oldMark['stamp'] ] );
$labOffered = $row( $site, 'header-k' )['undo'] ?? false;
$as( $site, 'designer' );
$designOffered = $row( $site, 'header-k' )['undo'] ?? true;
$chosenG = $file( 'frame-header-k' );
[ $designBack ] = $back( $site, 'frame-header-k' );
$designAfter = $file( 'frame-header-k' );
$as( $site, 'designlab' );
[ $labBack ] = $back( $site, 'frame-header-k' );

check( 'a mark without the stamp of the last state - a choice from before this version - is taken back by the account that holds the step that wrote the last state, and by no other: the list offers it to the first only, the request of the second is refused - 409 - and nothing is written',
	$labOffered === true && $designOffered === false && $designBack === 409 && $designAfter === $chosenG && $labBack === 200 && str_contains( $file( 'frame-header-k' ), 'Mark k' ) === true );

// the write is held under the lock to the frame and the last state that were looked at: the same look at a frame that was written between - by a request that came in after the look - is
// refused by the write itself, and nothing is written; the look that still holds lets the write go
$as( $site, 'designer' );
$choose( $site, 'frame-header-m' );
$afterChoiceJ = $file( 'frame-header-m' );
$stateJ = Store::state( $site, 'frame-header-m' );
$previousJ = Store::previous( $site, 'frame-header-m' );
$markJ = Store::choice( $site, 'frame-header-m' );
$targetJ = [ 'source' => $previousJ['source'], 'texts' => [], 'slots' => [] ];
$stale = Store::commit( $site, 'frame-header-m', $stateJ, $targetJ, null, [], null, [], 3, [ 'state' => Store::stamp( $stateJ ), 'previous' => hash( 'sha256', 'a last state that was written since' ), 'choice' => $markJ ] );
$staleMark = Store::commit( $site, 'frame-header-m', $stateJ, $targetJ, null, [], null, [], 3, [ 'state' => Store::stamp( $stateJ ), 'previous' => Store::stamp( $previousJ ), 'choice' => null ] );
$untouched = $file( 'frame-header-m' );
$held = Store::commit( $site, 'frame-header-m', $stateJ, $targetJ, null, [], null, [], 3, [ 'state' => Store::stamp( $stateJ ), 'previous' => Store::stamp( $previousJ ), 'choice' => $markJ ] );

check( 'the write of a way back looks at the frame, its last state and the mark of the choice under the lock: a look that no longer holds is refused by the write - nothing is written - and one that holds is written',
	$stale === 'changed' && $staleMark === 'changed' && $untouched === $afterChoiceJ && $held === null && $file( 'frame-header-m' ) === $previousJ['source'] && Store::choice( $site, 'frame-header-m' ) === null );

ninoDone( $site );
