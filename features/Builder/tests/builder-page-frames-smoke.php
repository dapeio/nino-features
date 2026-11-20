<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-page-frames-smoke.php	Contract test for the frames a page chooses - "Frames of this page": the header and the footer of a page are the standard frame of each, the frame of a name the site has, or none:
 *
 *												A. what the page offers: the choice it has now, the frames to choose from, whether the scaffold of the site carries the place for the choice, and a picture of each frame
 *												B. the round trip: header "landing" and no footer, saved - the text of the page has the two calls and nothing else of it is changed, the page that is drawn has that header and no footer -,
 *												   and the last state brings the text back to the byte; a page that calls the scaffold itself is written with the calls of the frames at the first change
 *												C. who and what: the call is the server's - a word of a frame of the site, the standard one or none, and no other template, no path, no capital, no attribute - and the step of the content
 *												   changes none of it; no token, no right, no frame
 *												D. a new page calls its frame where the scaffold carries the place, and the scaffold of an older site is turned by the one call that is in it
 *												E. the head first and the foot last, the choice of a page with one call of the scaffold and one of a frame, the side that cannot be chosen, the frame that is missing, the call that is a call, the scaffold that is kept
 *
 *	Usage: php features/Builder/tests/builder-page-frames-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-page-frames-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;

function pfCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function pfSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

$site = ninoSandbox( 'page-frames' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$put = static function( array &$site, string $name, string $source ): void { \Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source ); };
$file = static fn( string $name ): string => (string) @file_get_contents( ninoSandboxDir( $site ). '/private/templates/'. $name. '.tpl' );

$put( $site, 'html-header', "<!doctype html>\n<html><body>\n[[header]]\n<main>\n" );
$put( $site, 'html-footer', "</main>\n[[footer]]\n</body></html>\n" );
$put( $site, 'frame-header', "[header-wrap id=\"std-head\"]\n[header-logo]\n[/header-wrap]\n" );
$put( $site, 'frame-header-landing', "[header-wrap id=\"landing-head\"]\n[header-logo]\n[/header-wrap]\n" );
$put( $site, 'frame-footer', "[footer-wrap id=\"std-foot\"]\n[/footer-wrap]\n" );
$put( $site, 'frame-footer-klein', "[footer-wrap id=\"small-foot\"]\n[/footer-wrap]\n" );
$home = "[frame /templates/frame-header]\n[section-wrap id=\"x\"]\n\t[section-row]\n\t[/section-row]\n[/section-wrap]\n[frame /templates/frame-footer]\n";
$put( $site, 'page-home', $home );
$put( $site, 'page-old', "[template /templates/html-header]\n[section-wrap id=\"y\"]\n\t[section-row]\n\t[/section-row]\n[/section-wrap]\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $language )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $language. '.php', [ '[[/_nino/webpage/name]]' => 'Site' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://old' => [ 'uri' => '/old-page', 'body' => '[template /templates/page-old]' ] ];

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. What the page offers\n";

$before = pfSnapshot( $site );
[ $status, $offer ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] );
[ $oldStatus, $oldOffer ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-old' ] );

check( 'a page offers, for the header and for the footer, the choice it has now and the frames of the site to choose from - the standard one, the named ones, none - and says that the scaffold carries the place; a page that calls the scaffold itself has the standard one',
	$status === 200 && ( $offer['sides']['header']['current'] ?? '' ) === 'default' && ( $offer['sides']['header']['options'] ?? [] ) === [ 'default', 'landing', 'none' ] && ( $offer['sides']['footer']['options'] ?? [] ) === [ 'default', 'klein', 'none' ]
	&& ( $offer['sides']['footer']['framed'] ?? false ) === true && $oldStatus === 200 && ( $oldOffer['sides']['header']['current'] ?? '' ) === 'default' && pfSnapshot( $site ) === $before );

[ $tileStatus, $tile ] = pfCall( $site, 'builder/pageframetile', [ 'template' => 'page-home', 'side' => 'header', 'word' => 'landing' ] );
[ $tileDefault ] = pfCall( $site, 'builder/pageframetile', [ 'template' => 'page-home', 'side' => 'footer', 'word' => 'default' ] );
$badTiles = array_map( static fn( array $ask ): int => pfCall( $GLOBALS['site'], 'builder/pageframetile', [ 'template' => 'page-home' ] + $ask )[0], [
	[ 'side' => 'header', 'word' => 'none' ], [ 'side' => 'header', 'word' => 'klein' ], [ 'side' => 'footer', 'word' => 'landing' ], [ 'side' => 'header', 'word' => '../x' ], [ 'side' => 'header', 'word' => 'X' ],
	[ 'side' => 'body', 'word' => 'default' ], [ 'side' => '', 'word' => '' ], [ 'side' => 'header', 'word' => str_repeat( 'a', 41 ) ] ] );

check( 'the picture of a frame of the page is its tile - a token, a query and the address - for the standard frame and for a named one, and for no word that is none, that is the frame of another side, a path or a capital',
	$tileStatus === 200 && is_string( $tile['token'] ?? null ) && $tile['token'] !== '' && isset( $tile['query'], $tile['ttl'], $tile['address'] ) && $tileDefault === 200 && array_filter( $badTiles, static fn( int $code ): bool => in_array( $code, [ 400, 404 ], true ) === false ) === [] && pfSnapshot( $site ) === $before );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The round trip\n";

[ , $open ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$call = static function( string $side, string $word ): array { return [ 'n' => 'frame', 's' => '/templates/frame-'. $side. ( $word === 'default' ? '' : '-'. $word ), 'a' => [], 'c' => null ]; };
$choose = static function( array $tree, string $header, string $footer ) use ( $call ): array {

	foreach( $tree as $at => $node )
		if( ( $node['n'] ?? '' ) === 'frame' )
			$tree[$at] = $call( str_contains( (string) $node['s'], 'footer' ) ? 'footer' : 'header', str_contains( (string) $node['s'], 'footer' ) ? $footer : $header );

	return $tree;
};
$was = $file( 'page-home' );
[ $saved ] = pfCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $choose( $open['tree'], 'landing', 'none' ), 'texts' => $open['texts'], 'base' => $open['stamp'] ] );
$now = $file( 'page-home' );
$changed = array_values( array_diff( explode( "\n", $now ), explode( "\n", $was ) ) );
$drawn = \Nino\Html::renderSource( $site, $now );

check( 'the choice is saved with the page: the text has the call of the header with the word and the call of the footer for none, and every other line of it is as it was',
	$saved === 200 && $changed === [ '[frame /templates/frame-header-landing]', '[frame /templates/frame-footer-none]' ] && str_replace( [ '[frame /templates/frame-header-landing]', '[frame /templates/frame-footer-none]' ], [ '[frame /templates/frame-header]', '[frame /templates/frame-footer]' ], $now ) === $was );

check( 'the page that is drawn has the header that was chosen - not the standard one - and no footer, the scaffold stays',
	str_contains( $drawn, 'landing-head' ) === true && str_contains( $drawn, 'std-head' ) === false && str_contains( $drawn, 'std-foot' ) === false && str_contains( $drawn, 'small-foot' ) === false && str_contains( $drawn, '</body>' ) === true );

[ , $chosen ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] );

check( 'what the page has chosen is what it offers as its choice - the word of the header, none for the footer',
	( $chosen['sides']['header']['current'] ?? '' ) === 'landing' && ( $chosen['sides']['footer']['current'] ?? '' ) === 'none' );

[ , $again ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
[ $back ] = pfCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $again['stamp'] ] );

check( 'the last state brings the text of the page back to the byte', $back === 200 && $file( 'page-home' ) === $was );

[ , $oldOpen ] = pfCall( $site, 'builder/open', [ 'template' => 'page-old' ] );
$oldWas = $file( 'page-old' );
$swap = static function( array $tree, string $header ) use ( $call ): array {

	foreach( $tree as $at => $node )
		if( ( $node['n'] ?? '' ) === 'template' && ( $node['s'] ?? '' ) === '/templates/html-header' )
			$tree[$at] = $call( 'header', $header );

	return $tree;
};
[ $oldSaved ] = pfCall( $site, 'builder/save', [ 'template' => 'page-old', 'tree' => $swap( $oldOpen['tree'], 'landing' ), 'texts' => $oldOpen['texts'], 'base' => $oldOpen['stamp'] ] );
$oldNow = $file( 'page-old' );

check( 'a page that calls the scaffold itself is read as the standard frame and is written with the call of the frame at the first change - that call and nothing else of the page',
	$oldSaved === 200 && str_replace( '[frame /templates/frame-header-landing]', '[template /templates/html-header]', $oldNow ) === $oldWas );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. Who and what\n";

[ , $open ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$base = $open['stamp'];
$hand = static function( array $header ) use ( $open ): array {

	$tree = $open['tree'];

	foreach( $tree as $at => $node )
		if( ( $node['n'] ?? '' ) === 'frame' && str_contains( (string) $node['s'], 'header' ) )
			$tree[$at] = $header;

	return $tree;
};
$snapshot = pfSnapshot( $site );
$ask = static fn( array $header ): int => pfCall( $GLOBALS['site'], 'builder/save', [ 'template' => 'page-home', 'tree' => $hand( $header ), 'texts' => $GLOBALS['open']['texts'], 'base' => $GLOBALS['base'] ] )[0];
$refused = [
	'another template'				=> $ask( [ 'n' => 'frame', 's' => '/templates/page-home', 'a' => [], 'c' => null ] ),
	'a path'									=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header-../x', 'a' => [], 'c' => null ] ),
	'another folder'					=> $ask( [ 'n' => 'frame', 's' => '/other/frame-header', 'a' => [], 'c' => null ] ),
	'a capital'								=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header-Landing', 'a' => [], 'c' => null ] ),
	'a word nobody has'				=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header-nobody', 'a' => [], 'c' => null ] ),
	'a word of the footer'		=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header-klein', 'a' => [], 'c' => null ] ),
	'a word that is too long'	=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header-'. str_repeat( 'a', 41 ), 'a' => [], 'c' => null ] ),
	'default as a word'				=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header-default', 'a' => [], 'c' => null ] ),
	'an attribute'						=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header', 'a' => [ 'x' => 'y' ], 'c' => null ] ),
	'a call with content'			=> $ask( [ 'n' => 'frame', 's' => '/templates/frame-header', 'a' => [], 'c' => [ [ 't' => 'x' ] ] ] ),
	'the scaffold as the call'=> $ask( [ 'n' => 'template', 's' => '/templates/page-home', 'a' => [], 'c' => null ] ),
];

check( 'the call is the server\'s to hold to the form: another template, a path, another folder, a capital, a word nobody has or of the other side, a word too long, default, an attribute and a content are refused - 403 - and nothing is written',
	array_filter( $refused, static fn( int $code ): bool => $code !== 403 ) === [] && pfSnapshot( $site ) === $snapshot );

$accepted = [ $ask( $call( 'header', 'none' ) ) ];
[ , $open ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$base = $open['stamp'];
$accepted[] = $ask( $call( 'header', 'landing' ) );
[ , $open ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$base = $open['stamp'];
[ $restoredBack ] = pfCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => $base ] );

check( 'none, the standard frame and a word of the site are taken', $accepted === [ 200, 200 ] && $restoredBack === 200 && $file( 'page-home' ) !== $was );

ninoUser( $site, 'writer@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/edit' ] );
[ , $open ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$base = $open['stamp'];
$snapshot = pfSnapshot( $site );
$beforeContent = $file( 'page-home' );
$content = [
	pfCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $hand( $call( 'header', 'landing' ) ), 'texts' => $open['texts'], 'base' => $base ] )[0],
	pfCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $hand( $call( 'header', 'default' ) ), 'texts' => $open['texts'], 'base' => $base ] )[0],
	pfCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] )[0],
	pfCall( $site, 'builder/pageframetile', [ 'template' => 'page-home', 'side' => 'header', 'word' => 'default' ] )[0],
	pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] )[0],
];
[ $sameSave ] = pfCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $open['tree'], 'texts' => $open['texts'], 'base' => $base ] );

check( 'the step of the content changes no call of the frame - however the request is made - and has no choice, no picture and no scaffold to turn: 403 each, nothing is written; the page as it is saves',
	$content === [ 403, 403, 403, 403, 403 ] && $sameSave === 200 && $file( 'page-home' ) === $beforeContent );

ninoUser( $site, 'themelab@example.com', [ '/_admin/themelab/manage' ] );
$other = [ pfCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] )[0], pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] )[0] ];
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
$snapshot = pfSnapshot( $site );
$untokened = [];

foreach( [ 'builder/pageframes' => [ 'template' => 'page-home' ], 'builder/pageframetile' => [ 'template' => 'page-home', 'side' => 'header', 'word' => 'default' ], 'builder/scaffold' => [ 'side' => 'header' ] ] as $action => $data ) {

	$_POST['data'] = json_encode( $data + [ 'locale' => \Nino\Locales::getNativeLocale( $site ) ] );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$untokened[$action] = $request['/nino/http/response']['statusCode'];
}

[ $notAPage ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'frame-header' ] );
[ $noPage ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-nothing' ] );

check( 'an account with the right of another panel only, a request without the token of the session, a frame for a page and a page that is not there are refused - and nothing is written',
	$other === [ 403, 403 ] && array_unique( array_values( $untokened ) ) === [ 403 ] && in_array( $notAPage, [ 400, 403, 404 ], true ) === true && in_array( $noPage, [ 404, 400 ], true ) === true && pfSnapshot( $site ) === $snapshot );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. A new page and an older scaffold\n";

$page = [ 'names' => [ 'de_DE' => 'Neu', 'en_US' => 'New' ], 'addresses' => [ 'de_DE' => '/neu', 'en_US' => '/new' ], 'navs' => [ 'main' ], 'block' => 'hero-full' ];
[ $made ] = pfCall( $site, 'builder/create', $page );
$new = $file( 'page-neu' );

check( 'a new page calls its frame - the standard header and the standard footer - where the scaffold carries the place for it',
	$made === 200 && str_contains( $new, '[frame /templates/frame-header]' ) === true && str_contains( $new, '[frame /templates/frame-footer]' ) === true && str_contains( $new, '[template /templates/html-header]' ) === false );

$put( $site, 'html-header', "<!doctype html>\n<html><body>\n[template /templates/frame-header]\n<main>\n" );
$put( $site, 'html-footer', "</main>\n[template /templates/frame-footer]\n[template /templates/frame-footer]\n</body></html>\n" );
unset( $site['./nino/html/cache'] );
[ , $older ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] );
[ $made2 ] = pfCall( $site, 'builder/create', [ 'addresses' => [ 'de_DE' => '/alt', 'en_US' => '/old' ], 'names' => [ 'de_DE' => 'Alt', 'en_US' => 'Old' ] ] + $page );
$oldPage = $file( 'page-alt' );
$twice = pfCall( $site, 'builder/scaffold', [ 'side' => 'footer' ] );
$snapshot = pfSnapshot( $site );
[ $turned ] = pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] );
$header = $file( 'html-header' );
[ $again ] = pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] );
unset( $site['./nino/html/cache'] );
[ , $newer ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] );
[ $badSide ] = pfCall( $site, 'builder/scaffold', [ 'side' => 'body' ] );

check( 'the scaffold of an older site says that the choice has no place in it, and a new page calls the scaffold as before; the call of the scaffold that is in it exactly once is turned to [[header]] - that call and nothing else - and a scaffold that has the call twice, one that is turned and a side that is none are refused and left as they are',
	( $older['sides']['header']['framed'] ?? true ) === false && $made2 === 200 && str_contains( $oldPage, '[template /templates/html-header]' ) === true && $twice[0] === 409 && $turned === 200 && $header === "<!doctype html>\n<html><body>\n[[header]]\n<main>\n"
	&& $again === 409 && $badSide === 400 && ( $newer['sides']['header']['framed'] ?? false ) === true && ( $newer['sides']['footer']['framed'] ?? true ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. Head and foot, the choice of an older page, the scaffold turned with a way back, what the page cannot choose\n";

$body = "[section-wrap id=\"z\"]\n\t[section-row]\n\t[/section-row]\n[/section-wrap]\n";
$put( $site, 'page-bare', $body );
$site['/nino/http/routes']['GET://bare'] = [ 'uri' => '/bare', 'body' => '[template /templates/page-bare]' ];
$put( $site, 'page-mixed', "[template /templates/html-header]\n". $body. "[frame /templates/frame-footer-klein]\n" );
$put( $site, 'page-mixed2', "[frame /templates/frame-header-landing]\n". $body. "[template /templates/html-footer]\n" );
$put( $site, 'page-attr', "[template /templates/html-header class=\"x\"]\n". $body. "[frame /templates/frame-footer]\n" );
$put( $site, 'page-gone', "[frame /templates/frame-header-gone]\n". $body. "[frame /templates/frame-footer-gone]\n" );
[ , $bare ] = pfCall( $site, 'builder/open', [ 'template' => 'page-bare' ] );
$bareSave = static fn( array $tree ): int => pfCall( $GLOBALS['site'], 'builder/save', [ 'template' => 'page-bare', 'tree' => $tree, 'texts' => $GLOBALS['bare']['texts'], 'base' => $GLOBALS['bare']['stamp'] ] )[0];
$upside = $bareSave( array_merge( [ $call( 'footer', 'klein' ) ], $bare['tree'], [ $call( 'header', 'landing' ) ] ) );
$upsideText = $file( 'page-bare' );
[ , $home ] = pfCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$homeText = $file( 'page-home' );
$plain = array_values( array_filter( $home['tree'], static fn( array $node ): bool => ( $node['n'] ?? '' ) !== 'frame' ) );
$swapped = pfCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => array_merge( [ $call( 'footer', 'default' ) ], $plain, [ $call( 'header', 'default' ) ] ), 'texts' => $home['texts'], 'base' => $home['stamp'] ] )[0];
$inPlace = $bareSave( array_merge( [ $call( 'header', 'landing' ) ], $bare['tree'], [ $call( 'footer', 'klein' ) ] ) );
$bareText = $file( 'page-bare' );

check( 'the head put in goes first and the foot put in goes last, each as its own side: a page that has none takes both in their place and is refused the foot at the top and the head at the bottom - 403 where the page had none, 400 where it had them - with nothing written',
	$inPlace === 200 && $swapped === 400 && $upside === 403 && $upsideText === $body && $file( 'page-home' ) === $homeText && str_starts_with( $bareText, '[frame /templates/frame-header-landing]' ) === true && str_ends_with( $bareText, '[frame /templates/frame-footer-klein]' ) === true );

[ , $mixed ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-mixed' ] );
[ , $mixed2 ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-mixed2' ] );

check( 'a page with the call of the scaffold on one side and the call of a frame on the other reports the choice of each side as it is - the standard one for the scaffold, the word for the frame',
	[ $mixed['sides']['header']['current'] ?? null, $mixed['sides']['footer']['current'] ?? null ] === [ 'default', 'klein' ] && [ $mixed2['sides']['header']['current'] ?? null, $mixed2['sides']['footer']['current'] ?? null ] === [ 'landing', 'default' ] );

[ , $attr ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-attr' ] );
[ , $gone ] = pfCall( $site, 'builder/pageframes', [ 'template' => 'page-gone' ] );

check( 'a page that calls the scaffold with an attribute has that side locked - the choice is not offered for it - and the other side is not; a frame of the page that is not there is reported as missing and is not offered as a choice',
	( $attr['sides']['header']['locked'] ?? false ) === true && ( $attr['sides']['footer']['locked'] ?? true ) === false && ( $mixed['sides']['header']['locked'] ?? true ) === false
	&& ( $gone['sides']['header']['missing'] ?? false ) === true && ( $gone['sides']['footer']['missing'] ?? false ) === true && ( $gone['sides']['header']['current'] ?? '' ) === 'gone'
	&& in_array( 'gone', $gone['sides']['header']['options'] ?? [ 'gone' ], true ) === false && ( $mixed['sides']['footer']['missing'] ?? true ) === false && ( $offer['sides']['header']['missing'] ?? true ) === false );

$old = "<!doctype html>\n<html><body>\n[template /templates/frame-header]\n<main>\n";
$put( $site, 'html-header', "<!-- [template /templates/frame-header] -->\n<!doctype html>\n<html><body>\n<main>\n" );
unset( $site['./nino/html/cache'] );
$onlyComment = pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] )[0];
$put( $site, 'html-header', "<!doctype html>\n<html><body>\n[template /templates/frame-header x=\"1\"]\n<main>\n" );
unset( $site['./nino/html/cache'] );
$otherSpelling = pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] )[0];
$put( $site, 'html-header', "<!doctype html>\n<html><body>\n\\[template /templates/frame-header]\n<main>\n" );
unset( $site['./nino/html/cache'] );
$escaped = pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] )[0];
$source = "<!-- [template /templates/frame-header] -->\n<!doctype html>\n<html><body>\n<!-- [template /templates/frame-header] -->\n[template /templates/frame-header]\n<main>\n";
$put( $site, 'html-header', $source );
unset( $site['./nino/html/cache'] );
[ $turned, $answer ] = pfCall( $site, 'builder/scaffold', [ 'side' => 'header' ] );
$kept = ninoSandboxDir( $site ). '/private/data/builder/scaffold/html-header.tpl';

check( 'the scaffold is turned at the one call that is a call - a call in a comment or written as text is none and a call in another spelling is refused with 409 - and the text that was turned away from is kept in a file that the answer names',
	$onlyComment === 409 && $otherSpelling === 409 && $escaped === 409 && $turned === 200 && $file( 'html-header' ) === str_replace( "\n[template /templates/frame-header]\n<main>", "\n[[header]]\n<main>", $source )
	&& ( $answer['kept'] ?? '' ) === 'private/data/builder/scaffold/html-header.tpl' && is_file( $kept ) === true && file_get_contents( $kept ) === $source );

ninoDone( $site );
