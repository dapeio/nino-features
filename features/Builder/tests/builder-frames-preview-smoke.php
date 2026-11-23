<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-frames-preview-smoke.php	Contract test for the preview of a frame in its editor: the page the draft is shown in has the frame in it - the page that chooses the frame, or, where no page chooses it, the
 *															first page of the site with the call of that frame for the draft - and never a page that has the standard frame where the frame that is worked on does not stand
 *
 *	Usage: php features/Builder/tests/builder-frames-preview-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-frames-preview-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

function previewCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

$site = ninoSandbox( 'builder-frames-preview' );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$put = static function( array &$site, string $name, string $source ): void { \Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source ); };
$head = static fn( string $id ): string => "[header-wrap id=\"". $id. "\"]\n\t[header-logo]\n[/header-wrap]\n";
$foot = static fn( string $id ): string => "[footer-wrap id=\"". $id. "\"]\n[/footer-wrap]\n";

$put( $site, 'html-header', "<!doctype html>\n<html><body>\n[[header]]\n<main>\n" );
$put( $site, 'html-footer', "</main>\n[[footer]]\n</body></html>\n" );
$put( $site, 'frame-header', $head( 'std-head' ) );
$put( $site, 'frame-header-landing', $head( 'landing-head' ) );
$put( $site, 'frame-header-shop', $head( 'shop-head' ) );
$put( $site, 'frame-footer', $foot( 'std-foot' ) );
$put( $site, 'frame-footer-klein', $foot( 'small-foot' ) );
$put( $site, 'page-home', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n<p>HOME</p>\n[template /templates/html-footer]\n" );
$put( $site, 'page-contact', "<!-- nino:template-name Contact -->\n[frame /templates/frame-header-landing]\n<p>CONTACT</p>\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => 'Home' ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );

/** What the page at the address of the preview is drawn as with the draft of the frame: the address and the html */
$shown = static function( array &$site, string $template, string $idLeft, array $extra = [] ): array {

	[ , $opened ] = previewCall( $GLOBALS['site'], 'builder/frame-open', [ 'template' => $template ] + $extra );
	$tree = $opened['tree'];
	$tree[0]['a']['id'] = $idLeft;
	[ $status, $preview ] = previewCall( $GLOBALS['site'], 'builder/frame-preview', [ 'template' => $template, 'tree' => $tree, 'texts' => [], 'token' => '' ] + $extra );

	$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => (string) ( $preview['token'] ?? '' ) ] ] ];
	\Nino\Modules\Builder::request( $site, $request );
	$body = '';

	foreach( $site['/nino/http/routes'] as $route )
		if( $route['uri'] === ( $preview['address'] ?? '' ) )
			$body = \Nino\Html::renderSource( $site, $route['body'] );

	unset( $site['./nino/preview/held'], $site['./nino/template/swapped'] );

	return [ $status, (string) ( $preview['address'] ?? '' ), $body ];
};

[ $sStatus, $sAddress, $sBody ] = $shown( $site, 'frame-header', 'std-draft' );
[ $lStatus, $lAddress, $lBody ] = $shown( $site, 'frame-header-landing', 'landing-draft' );
[ $hStatus, $hAddress, $hBody ] = $shown( $site, 'frame-header-shop', 'shop-draft' );
[ $kStatus, $kAddress, $kBody ] = $shown( $site, 'frame-footer-klein', 'small-draft' );
[ $oStatus, $oAddress, $oBody ] = $shown( $site, 'frame-footer', 'foot-draft' );

check( 'the preview of the standard header is drawn on a page that has it - the first page - and shows the draft in it',
	$sStatus === 200 && $sAddress === '/home' && str_contains( $sBody, 'std-draft' ) === true && str_contains( $sBody, 'HOME' ) === true );
check( 'the preview of a named header is drawn on the page that chooses it, and shows the draft in it - not the first page with the standard header',
	$lStatus === 200 && $lAddress === '/contact' && str_contains( $lBody, 'landing-draft' ) === true && str_contains( $lBody, 'CONTACT' ) === true && str_contains( $lBody, 'std-head' ) === false );
check( 'the preview of a header that no page chooses is drawn on the first page with the call of that frame in it for the draft: the draft stands there and the standard header does not',
	$hStatus === 200 && $hAddress === '/home' && str_contains( $hBody, 'shop-draft' ) === true && str_contains( $hBody, 'HOME' ) === true && str_contains( $hBody, 'std-head' ) === false );
check( 'the same for a footer that no page chooses: the draft stands in the foot of the first page, and the standard footer does not',
	$kStatus === 200 && $kAddress === '/home' && str_contains( $kBody, 'small-draft' ) === true && str_contains( $kBody, 'std-foot' ) === false && str_contains( $kBody, 'HOME' ) === true );
check( 'the standard footer is drawn as it is on the page that has it, with the draft in it',
	$oStatus === 200 && str_contains( $oBody, 'foot-draft' ) === true );

// a page that does not choose the frame is shown the frame that is worked on, as a draft in the place of its own head or foot - never its own, where the frame is not in it
[ $pStatus, $pAddress, $pBody ] = $shown( $site, 'frame-header-landing', 'landing-draft', [ 'page' => 'home' ] );
[ $qStatus, $qAddress, $qBody ] = $shown( $site, 'frame-header-shop', 'shop-draft', [ 'page' => 'contact' ] );
[ $rStatus, $rAddress, $rBody ] = $shown( $site, 'frame-header', 'std-draft', [ 'page' => 'contact' ] );
[ $tStatus, $tAddress, $tBody ] = $shown( $site, 'frame-footer-klein', 'small-draft', [ 'page' => 'contact' ] );

check( 'the editor of a frame opened from a page that does not choose it is shown that page with the frame in it as a draft: the named header on the first page, an unused header on the contact page, the standard header on a page that chooses another, a footer on a page - and not the head the page has',
	$pStatus === 200 && $pAddress === '/home' && str_contains( $pBody, 'landing-draft' ) === true && str_contains( $pBody, 'std-head' ) === false && str_contains( $pBody, 'HOME' ) === true
	&& $qStatus === 200 && $qAddress === '/contact' && str_contains( $qBody, 'shop-draft' ) === true && str_contains( $qBody, 'landing-head' ) === false && str_contains( $qBody, 'CONTACT' ) === true
	&& $rStatus === 200 && $rAddress === '/contact' && str_contains( $rBody, 'std-draft' ) === true && str_contains( $rBody, 'landing-head' ) === false
	&& $tStatus === 200 && str_contains( $tBody, 'small-draft' ) === true && str_contains( $tBody, 'std-foot' ) === false );

// the page the editor was opened from is the page of the preview
[ , $opened ] = previewCall( $site, 'builder/frame-open', [ 'template' => 'frame-header-landing', 'page' => 'contact' ] );
check( 'the editor opened from a page is shown that page, and one that has no page is shown the page that chooses the frame',
	( $opened['address'] ?? '' ) === '/contact' && ( previewCall( $site, 'builder/frame-open', [ 'template' => 'frame-header-landing' ] )[1]['address'] ?? '' ) === '/contact' );

ninoDone( $site );
