<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-anchors-eol-smoke.php	Contract test for two things of a page in the Builder:
 *
 *												A. the links of other pages to the sections of this page: the pages that hold page:<address>#<id> in their template or a text - named, counted, nothing changed, no lock - for the one who
 *												   changes an id; the right and the token; a page of the site only
 *												B. the line ends of a page: a page written with CRLF is saved with CRLF, one with LF with LF, a page of mixed ends with the ends it has most; a save that changes nothing is the same bytes
 *
 *	Usage: php features/Builder/tests/builder-anchors-eol-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-anchors-eol-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;

function aeSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text' ] as $sub )
		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );

	return $list;
}

function aeCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

$site = ninoSandbox( 'anchors-eol' );
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

$both = [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ];
$bytes = static fn( string $name ): string => (string) @file_get_contents( ninoSandboxDir( $GLOBALS['site'] ). '/private/templates/'. $name. '.tpl' );
$section = static fn( string $id ): string => "[section-wrap id=\"". $id. "\"]\n\t[section-row]\n\t[/section-row]\n[/section-wrap]\n";

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The links of other pages to the sections of a page\n";

$put( $site, 'page-home', "[frame /templates/frame-header]\n[section-wrap id=\"x\"]\n\t[section-row]\n\t[/section-row]\n\t[button ~self href=\"page:/home#x\"]\n[/section-wrap]\n[frame /templates/frame-footer]\n" );
$put( $site, 'page-contact', "[frame /templates/frame-header]\n[section-wrap id=\"c\"]\n\t[button ~button href=\"page:/home#x\"]\n\t[button ~other href=\"page:/home#xy\"]\n\t[button ~third href=\"page:/old-page#x\"]\n[/section-wrap]\n[frame /templates/frame-footer]\n" );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/_nino/webpage/name]]' => 'Site', '[[/template/page-contact/c/other]]' => 'page:/home#x', '[[/template/page-contact/c/button]]' => 'Kontakt' ] );
$put( $site, 'page-quiet', "[frame /templates/frame-header]\n[section-wrap id=\"q\"]\n\t[button ~button href=\"page:/home#x\"]\n\t[button ~two href=\"page:/home#x\"]\n[/section-wrap]\n" );
$put( $site, 'page-near', "[section-wrap id=\"n\"]\n\t[button ~button href=\"page:/home#x-1\"]\n\t[button ~two href=\"page:/homework#x\"]\n\t[button ~three href=\"#x\"]\n[/section-wrap]\n" );
$site['/nino/http/routes'] += [ 'GET://contact' => [ 'uri' => '/contact-us', 'body' => '[template /templates/page-contact]' ] ];
$snapshot = aeSnapshot( $site );
[ $linksStatus, $links ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'page-home', 'ids' => [ 'x' ] ] );
$byName = array_column( $links['pages'] ?? [], null, 'template' );

check( 'the pages that link to the old id of a section of this page are named - the name, the address, the number of links - whether they have a route or not: a link in the template or in a text counts, a link to another id, to another page or the jump mark of the page itself does not; nothing is written',
	$linksStatus === 200 && array_keys( $byName ) === [ 'page-contact', 'page-quiet' ] && $byName['page-contact']['links'] === 2 && $byName['page-contact']['path'] === '/contact' && $byName['page-quiet']['links'] === 2 && $byName['page-quiet']['name'] === 'page-quiet.tpl' && ( $links['links'] ?? 0 ) === 4 && aeSnapshot( $site ) === $snapshot );

[ , $none ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'page-home', 'ids' => [ 'nothing' ] ] );
[ , $inner ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'page-home', 'ids' => [ 'x', 'xy' ] ] );
[ $notPage ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'frame-header', 'ids' => [ 'x' ] ] );
[ $bad ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'page-home', 'ids' => [ '../x', 'a b', [ 'x' ], 5 ] ] );

ninoUser( $site, 'writer@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/edit' ] );
[ $content ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'page-home', 'ids' => [ 'x' ] ] );
ninoUser( $site, 'nobody@example.com', [] );
[ $nobody ] = aeCall( $site, 'builder/anchorlinks', [ 'template' => 'page-home', 'ids' => [ 'x' ] ] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
$_POST['data'] = json_encode( [ 'template' => 'page-home', 'ids' => [ 'x' ], 'locale' => 'de_DE' ] );
$_POST['action'] = 'builder/anchorlinks';
$tokenless = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
\Nino\Admin\Admin::handlePost( $site, $tokenless );

check( 'no id that is none finds anything, an id of the inner part is asked as well, a frame is no page (refused), and the step of the content, a request without a right and one without the token are 403',
	( $none['links'] ?? -1 ) === 0 && ( $inner['links'] ?? 0 ) === 5 && in_array( $notPage, [ 403, 404 ], true ) === true && $bad === 200 && $content === 403 && $nobody === 403 && $tokenless['/nino/http/response']['statusCode'] === 403 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The line ends of a page\n";

$cases = [ 'crlf' => [ "\r\n", str_replace( "\n", "\r\n", "[frame /templates/frame-header]\n". $section( 'a' ). $section( 'b' ). "[frame /templates/frame-footer]\n" ) ],
	'lf' => [ "\n", "[frame /templates/frame-header]\n". $section( 'a' ). $section( 'b' ). "[frame /templates/frame-footer]\n" ],
	'mostly-crlf' => [ "\r\n", "[frame /templates/frame-header]\r\n". str_replace( "\n", "\r\n", $section( 'a' ) ). $section( 'b' ). "[frame /templates/frame-footer]\r\n" ],
	'mostly-lf' => [ "\n", "[frame /templates/frame-header]\n". $section( 'a' ). str_replace( "\n", "\r\n", $section( 'b' ) ). "[frame /templates/frame-footer]\n" ] ];
$routes = [];

foreach( $cases as $name => [ , $source ] ) {
	$put( $site, 'page-'. $name, $source );
	$routes['GET://'. $name] = [ 'uri' => '/'. $name, 'body' => '[template /templates/page-'. $name. ']' ];
}

$site['/nino/http/routes'] += $routes;

// a save: the page is opened, changed by $edit and saved; what is written is answered
$save = static function( string $name, ?callable $edit ) use ( &$site, $bytes ): array {

	[ , $open ] = aeCall( $site, 'builder/open', [ 'template' => 'page-'. $name ] );
	$tree = $open['tree'] ?? [];

	if( $edit !== null )
		$tree = $edit( $tree );

	[ $status ] = aeCall( $site, 'builder/save', [ 'template' => 'page-'. $name, 'tree' => $tree, 'texts' => $open['texts'] ?? [], 'base' => $open['stamp'] ?? '' ] );

	return [ $status, $bytes( 'page-'. $name ) ];
};
$ends = static fn( string $text ): array => [ substr_count( $text, "\r\n" ), substr_count( $text, "\n" ) ];
$edits = [
	'an id' => static function( array $tree ): array { foreach( $tree as $at => $node ) if( ( $node['n'] ?? '' ) === 'section-wrap' && ( $node['a']['id'] ?? '' ) === 'a' ) $tree[$at]['a']['id'] = 'a2'; return $tree; },
	'a frame' => static function( array $tree ): array { foreach( $tree as $at => $node ) if( ( $node['n'] ?? '' ) === 'frame' && str_contains( (string) $node['s'], 'frame-header' ) ) $tree[$at]['s'] = '/templates/frame-header-landing'; return $tree; },
	'blocks moved' => static function( array $tree ): array { $at = array_keys( array_filter( $tree, static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'section-wrap' ) ); [ $tree[$at[0]], $tree[$at[1]] ] = [ $tree[$at[1]], $tree[$at[0]] ]; return $tree; },
	'text made in LF' => static function( array $tree ): array { $lf = static function( array $nodes ) use ( &$lf ): array { foreach( $nodes as $at => $node ) { if( isset( $node['t'] ) === true ) $nodes[$at]['t'] = str_replace( "\r\n", "\n", $node['t'] ) ; else if( is_array( $node['c'] ?? null ) === true ) $nodes[$at]['c'] = $lf( $node['c'] ); } return $nodes; }; foreach( $tree as $at => $node ) if( ( $node['n'] ?? '' ) === 'section-wrap' && ( $node['a']['id'] ?? '' ) === 'a' ) { $tree[$at]['c'] = $lf( $node['c'] ); $tree[$at]['a']['id'] = 'a3'; } return $tree; },
];
$fine = [];

foreach( $cases as $name => [ $eol, $source ] ) {

	[ $same, $kept ] = $save( $name, null );
	$fine[$name] = $same === 200 && $kept === $source;

	foreach( $edits as $what => $edit ) {

		$put( $site, 'page-'. $name, $source );
		[ $status, $written ] = $save( $name, $edit );
		[ $crlf, $all ] = $ends( $written );
		if( getenv('AEDEBUG') ) fwrite( STDERR, $name. ' '. $what. ' '. $status. ' '. json_encode( $ends( $written ) ). "\n" );
		$fine[$name. ' '. $what] = $status === 200 && $written !== $source && ( $eol === "\r\n" ? $crlf === $all : $crlf === 0 );
	}

	$put( $site, 'page-'. $name, $source );
}

check( 'a page written with CRLF is saved with CRLF - a save that changes nothing is the same bytes, and a change of an id, of the frame, blocks moved, or a text that was made with LF in a page of CRLF keeps every line end the same; a page with LF stays LF; a page of both is saved with the ends it has most',
	array_filter( $fine, static fn( bool $one ): bool => $one === false ) === [] );

ninoDone( $site );
