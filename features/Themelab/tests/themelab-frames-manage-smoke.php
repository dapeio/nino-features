<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-frames-manage-smoke.php	Contract test for the frames of a site made, named and taken away in Themelab - and for the list of them in the Builder:
 *
 *												A. a frame is made: of the standard frame, of another frame or of a block, with its words in every language and the name it is called by
 *												B. who and what: the right of the panel and the token, a word that is none, taken, too long or that has a capital or a path, a name with markup or format characters, a source that is
 *												   none - another side, a page, both, neither, a block of the other side - are refused and nothing is written
 *												C. the name is kept apart from the word and shown where the frame is listed: in Themelab, in the list of the Builder and in the choice of a page; renaming changes the name only
 *												D. a frame is taken away where no page chooses it - with its template, its words, its name and its last state - and refused, naming the pages, where one does; never the standard one;
 *												   the round trip: made, chosen by a page, refused, the page back on the standard frame, taken away
 *
 *	Usage: php features/Themelab/tests/themelab-frames-manage-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-frames-manage-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;

function fmCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function fmSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text', 'private/data' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

$site = ninoSandbox( 'frames-manage' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$put = static function( array &$site, string $name, string $source ): void { \Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source ); };
$file = static fn( string $name ): string => (string) @file_get_contents( ninoSandboxDir( $GLOBALS['site'] ). '/private/templates/'. $name. '.tpl' );
$header = "[header-wrap id=\"header\"]\n\t[header-logo]\n\t[header-actions]\n\t\t[button ~button href=\"#top\" size=\"s\"]\n\t[/header-actions]\n[/header-wrap]\n";

$put( $site, 'html-header', "<!doctype html>\n<html><body>\n[[header]]\n<main>\n" );
$put( $site, 'html-footer', "</main>\n[[footer]]\n</body></html>\n" );
$put( $site, 'frame-header', $header );
$put( $site, 'frame-footer', "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" );
$put( $site, 'frame-header-b', str_replace( 'id="header"', 'id="b"', $header ) );
$put( $site, 'page-home', "[frame /templates/frame-header]\n[section-wrap id=\"x\"]\n\t[section-row]\n\t[/section-row]\n[/section-wrap]\n[frame /templates/frame-footer]\n" );
$put( $site, 'page-other', "[template /templates/html-header]\n[section-wrap id=\"y\"]\n\t[section-row]\n\t[/section-row]\n[/section-wrap]\n[template /templates/html-footer]\n" );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Kontakt', '[[/template/frame-header-b/b/button]]' => 'B' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Contact', '[[/template/frame-header-b/b/button]]' => 'B' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://other' => [ 'uri' => '/other-page', 'body' => '[template /templates/page-other]' ] ];

$both = [ \Nino\Modules\Themelab::PERM, '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ];
ninoUser( $site, 'lab@example.com', $both );
$names = [ 'de_DE' => 'Landing', 'en_US' => 'Landing page' ];

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. A frame is made\n";

[ $made, $body ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'landing', 'name' => $names, 'from' => 'frame-header' ] );
$words = [ 'de_DE' => \Nino\Text::values( $site, '/template/frame-header-landing' ), 'en_US' => [] ];
$keys = array_keys( $words['de_DE'] );

check( 'a frame is made of the standard frame: its template is the frame\'s and the words of the frame are its words under its own template, in every language, and the name is kept',
	$made === 200 && ( $body['key'] ?? '' ) === 'header-landing' && $file( 'frame-header-landing' ) === $header && $keys === [ '/template/frame-header-landing/header/button' ] && ( $words['de_DE'][$keys[0]]['de_DE'] ?? '' ) === 'Kontakt' && ( $words['de_DE'][$keys[0]]['en_US'] ?? '' ) === 'Contact'
	&& Store::frameName( $site, 'frame-header-landing', 'en_US' ) === 'Landing page' && Store::frameName( $site, 'frame-header-landing', 'de_DE' ) === 'Landing' && isset( Store::frames( $site )['header-landing'] ) === true );

[ $fromFrame, $copy ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'zwei', 'name' => 'Zwei', 'from' => 'frame-header-b' ] );

check( 'a frame is made of another frame of the site as well - the text of its template is that frame\'s, and the words that point at it point at the copy',
	$fromFrame === 200 && str_contains( $file( 'frame-header-zwei' ), 'id="b"' ) === true && isset( \Nino\Text::values( $site, '/template/frame-header-zwei' )['/template/frame-header-zwei/b/button'] ) === true );

[ $fromBlock ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'aus-block', 'name' => 'Aus Block', 'block' => 'header-line' ] );
[ $footBlock ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'footer', 'word' => 'fuss', 'name' => 'Fuss', 'block' => 'footer-columns' ] );

check( 'a frame is made of a block of its side - the template is the block\'s root and the texts of the block are in it - and a header block makes no footer',
	$fromBlock === 200 && str_starts_with( $file( 'frame-header-aus-block' ), '[header-wrap' ) === true && $footBlock === 200 && str_starts_with( $file( 'frame-footer-fuss' ), '[footer-wrap' ) === true );

[ $wrongBlock ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'footer', 'word' => 'falsch', 'name' => 'Falsch', 'block' => 'header-line' ] );
[ $listStatus, $listed ] = fmCall( $site, 'builder/list' );
$rows = array_column( $listed['frames'] ?? [], null, 'key' );
[ , $lab ] = fmCall( $site, 'themelab/frames' );
$labRows = array_column( $lab['frames'] ?? [], null, 'key' );

check( 'the frame is in the list of the Builder and in that of Themelab: with its name in the language, whether it is the standard one, and on how many pages it is chosen - the standard frame has the pages that choose none of their own',
	$wrongBlock === 404 && ( $rows['header-landing']['name'] ?? '' ) === 'Landing' && ( $labRows['header-landing']['name'] ?? '' ) === 'Landing' && ( $rows['header-landing']['default'] ?? true ) === false && ( $rows['header']['default'] ?? false ) === true
	&& ( $rows['header-landing']['uses'] ?? -1 ) === 0 && ( $rows['header']['uses'] ?? 0 ) === 2 && ( $rows['footer']['uses'] ?? 0 ) === 2 && ( $rows['header-landing']['names']['en_US'] ?? '' ) === 'Landing page' );

[ , $offer ] = fmCall( $site, 'builder/pageframes', [ 'template' => 'page-home' ] );

check( 'the choice of a page offers the frame by its word and says the name of each frame that has one - the standard frames have none - and a frame of the other side is not offered for a side',
	( $offer['sides']['header']['options'] ?? [] ) === [ 'default', 'aus-block', 'b', 'landing', 'zwei', 'none' ] && ( $offer['sides']['footer']['options'] ?? [] ) === [ 'default', 'fuss', 'none' ] && ( $offer['names']['header']['landing'] ?? '' ) === 'Landing' && ( $offer['names']['footer']['fuss'] ?? '' ) === 'Fuss' && isset( $offer['names']['header']['default'] ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. Who and what\n";

$snapshot = fmSnapshot( $site );
$ask = static fn( array $more ): int => fmCall( $GLOBALS['site'], 'themelab/frame-create', $more + [ 'side' => 'header', 'word' => 'neu', 'name' => 'Neu', 'from' => 'frame-header' ] )[0];
$refused = [
	'a path'							=> $ask( [ 'word' => '../x' ] ),
	'a capital'						=> $ask( [ 'word' => 'Neu' ] ),
	'none'								=> $ask( [ 'word' => 'none' ] ),
	'default'							=> $ask( [ 'word' => 'default' ] ),
	'a blank'							=> $ask( [ 'word' => 'a b' ] ),
	'two hyphens'					=> $ask( [ 'word' => 'a--b' ] ),
	'no word'							=> $ask( [ 'word' => '' ] ),
	'too long'						=> $ask( [ 'word' => str_repeat( 'a', 41 ) ] ),
	'a dot'								=> $ask( [ 'word' => 'a.b' ] ),
	'a side that is none'	=> $ask( [ 'side' => 'body' ] ),
	'markup in the name'	=> $ask( [ 'name' => [ 'de_DE' => '<b>x</b>', 'en_US' => 'x' ] ] ),
	'a reversing character'	=> $ask( [ 'name' => [ 'de_DE' => "A\u{202E}B", 'en_US' => 'x' ] ] ),
	'a page as the source'	=> $ask( [ 'from' => 'page-home' ] ),
	'a frame of the other side' => $ask( [ 'from' => 'frame-footer' ] ),
	'a frame that is not there' => $ask( [ 'from' => 'frame-header-nofile' ] ),
	'a path as the source'	=> $ask( [ 'from' => '../templates/frame-header' ] ),
	'both sources'				=> $ask( [ 'block' => 'header-line' ] ),
	'no source'						=> $ask( [ 'from' => '' ] ),
	'a block that is none'	=> $ask( [ 'from' => '', 'block' => 'nope' ] ),
	'a block of a page'		=> $ask( [ 'from' => '', 'block' => 'hero-full' ] ),
	'a word that is taken'	=> $ask( [ 'word' => 'landing' ] ),
];

check( 'a frame is not made for a word that is none, taken, too long or has a capital, a path, a blank, a dot or two hyphens; for a name with markup or a character that turns a line round; for a source that is a page, another side, no frame, a path, both, none, or a block that is not of the side - 400, 404 or 409 - and nothing is written',
	array_filter( $refused, static fn( int $status ): bool => in_array( $status, [ 400, 404, 409 ], true ) === false ) === [] && $refused['a word that is taken'] === 409 && fmSnapshot( $site ) === $snapshot );

ninoUser( $site, 'builder@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$noRight = [ fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'neu', 'name' => 'Neu', 'from' => 'frame-header' ] )[0], fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header-landing', 'name' => 'X' ] )[0], fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-zwei' ] )[0] ];
ninoUser( $site, 'lab@example.com', $both );
$untokened = [];

foreach( [ 'themelab/frame-create' => [ 'side' => 'header', 'word' => 'neu', 'name' => 'Neu', 'from' => 'frame-header' ], 'themelab/frame-rename' => [ 'template' => 'frame-header-landing', 'name' => 'X' ], 'themelab/frame-remove' => [ 'template' => 'frame-header-zwei' ] ] as $action => $data ) {

	$_POST['data'] = json_encode( $data + [ 'locale' => \Nino\Locales::getNativeLocale( $site ) ] );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$untokened[$action] = $request['/nino/http/response']['statusCode'];
}

check( 'making, renaming and taking away need the right of this panel and the token of the session: an account with the right of the Builder only and a request without the token are 403, and nothing is written',
	$noRight === [ 403, 403, 403 ] && array_unique( array_values( $untokened ) ) === [ 403 ] && fmSnapshot( $site ) === $snapshot );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The name\n";

$before = [ $file( 'frame-header-landing' ), \Nino\Text::values( $site, '/template/frame-header-landing' ) ];
[ $renamed ] = fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header-landing', 'name' => [ 'de_DE' => 'Startseite', 'en_US' => 'Home' ] ] );
[ , $listed ] = fmCall( $site, 'builder/list' );
$rows = array_column( $listed['frames'] ?? [], null, 'key' );
[ $std ] = fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header', 'name' => 'X' ] );
[ $none ] = fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header-nofile', 'name' => 'X' ] );
[ $markup ] = fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header-landing', 'name' => '<i>x</i>' ] );

check( 'renaming changes the name and nothing else: the word, the template and its words stay - the list says the new name in the language - and the standard frame, a frame that is not there and a name with markup are refused',
	$renamed === 200 && ( $rows['header-landing']['name'] ?? '' ) === 'Startseite' && isset( Store::frames( $site )['header-landing'] ) === true && $before === [ $file( 'frame-header-landing' ), \Nino\Text::values( $site, '/template/frame-header-landing' ) ]
	&& $std === 404 && $none === 404 && $markup === 400 && Store::frameName( $site, 'frame-header', 'de_DE' ) === '' );

$names = Store::frameNames( $site );
$script = (string) file_get_contents( $sandbox. '/private/data/builder/frame-names.php' );

check( 'the name is kept in one place - the small store of the Builder - and never in a template',
	isset( $names['frame-header-landing'] ) === true && str_contains( $script, 'Startseite' ) === true && str_contains( $file( 'frame-header-landing' ), 'Startseite' ) === false && array_key_exists( 'frame-header', $names ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nD. A frame is taken away\n";

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $open ] = fmCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];

foreach( $tree as $at => $node )
	if( ( $node['n'] ?? '' ) === 'frame' && str_contains( (string) $node['s'], 'header' ) )
		$tree[$at]['s'] = '/templates/frame-header-landing';

[ $chose ] = fmCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $open['texts'], 'base' => $open['stamp'] ] );
ninoUser( $site, 'lab@example.com', $both );
$snapshot = fmSnapshot( $site );
[ $usedStatus, $used ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-landing' ] );
[ $stdStatus ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header' ] );
[ $footStd ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-footer' ] );
[ $nofile ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-nofile' ] );
[ $path ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-../x' ] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
[ , $listed ] = fmCall( $site, 'builder/list' );
$rows = array_column( $listed['frames'] ?? [], null, 'key' );

check( 'a frame that a page chooses is not taken away - 409, the page is named with its address, the standard frames, a frame that is not there and a path are refused - and nothing is written; the list counts the page on the frame it chose',
	$chose === 200 && $usedStatus === 409 && ( $used['code'] ?? '' ) === 'themelab_frame_used' && str_contains( implode( ' ', (array) ( $used['params'] ?? [] ) ), '/home' ) === true && $stdStatus === 403 && $footStd === 403 && in_array( $nofile, [ 404 ], true ) === true && in_array( $path, [ 404 ], true ) === true
	&& fmSnapshot( $site ) === $snapshot && ( $rows['header-landing']['uses'] ?? 0 ) === 1 && ( $rows['header']['uses'] ?? 0 ) === 1 );

[ , $open ] = fmCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$tree = $open['tree'];

foreach( $tree as $at => $node )
	if( ( $node['n'] ?? '' ) === 'frame' && str_contains( (string) $node['s'], 'header' ) )
		$tree[$at]['s'] = '/templates/frame-header';

[ $back ] = fmCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $open['texts'], 'base' => $open['stamp'] ] );
ninoUser( $site, 'lab@example.com', $both );

// the last state of the page still names the frame: a restore would write it back, so the frame is not taken away either
[ $keptStatus, $keptBody ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-landing' ] );
$keptNamed = $keptStatus === 409 && ( $keptBody['code'] ?? '' ) === 'themelab_frame_used_last' && str_contains( implode( ' ', (array) ( $keptBody['params'] ?? [] ) ), '/home' ) === true && is_file( $sandbox. '/private/templates/frame-header-landing.tpl' ) === true;

// a restore of a state that names a frame the site has not says so at once
$before = Store::previous( $site, 'page-home' );
$ghost = $before === null ? null : [ 'source' => str_replace( 'frame-header-landing', 'frame-header-ghost', $before['source'] ) ] + $before;
$restored = null;

if( $ghost !== null ) {
	\Nino\Filesystem::putFileContent( $site, Store::PREVIOUS. '/page-home.php', $ghost );
	ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
	[ , $restored ] = fmCall( $site, 'builder/restore', [ 'template' => 'page-home', 'base' => Store::stamp( (array) Store::state( $site, 'page-home' ) ) ] );
	\Nino\Filesystem::putFileContent( $site, Store::PREVIOUS. '/page-home.php', $before );
	ninoUser( $site, 'lab@example.com', $both );
}

check( 'the last state of a page counts as a use: the frame is refused - 409, "themelab_frame_used_last" with the page - and stays; a restore of a state that names a frame the site has not answers with the word of that frame',
	$keptNamed === true && $ghost !== null && ( $restored['missingFrames'] ?? [] ) === [ 'ghost' ] );

// the page is saved once more - its last state is then the one before, on the standard frame
[ , $again ] = fmCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/routes/manage' ] );
$retree = $again['tree'];

foreach( $retree as $at => $node )
	if( ( $node['n'] ?? '' ) === 'section-wrap' )
		$retree[$at]['a']['id'] = 'x2';

fmCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $retree, 'texts' => $again['texts'], 'base' => $again['stamp'] ] );
ninoUser( $site, 'lab@example.com', $both );
$keep = $file( 'frame-header' );
\Nino\Filesystem::putFileContent( $site, Store::PREVIOUS. '/frame-header-landing.php', [ 'source' => 'x' ] );
\Nino\Filesystem::putFileContent( $site, Store::FRAME_MARK. '/frame-header-landing.php', [ 'key' => 'x' ] );
$marked = is_file( \Nino\Filesystem::path( $site, Store::PREVIOUS. '/frame-header-landing.php' ) ) === true && is_file( \Nino\Filesystem::path( $site, Store::FRAME_MARK. '/frame-header-landing.php' ) ) === true;
$standard = [ Store::dropFrame( $site, 'frame-header' ), Store::dropFrame( $site, 'frame-footer' ), $file( 'frame-header' ) === $keep ];
[ $gone ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-landing' ] );
$left = fmSnapshot( $site );

check( 'with the page back on the standard frame the frame is taken away: its template, its words in every language, its name and the last state of it are gone, and the standard frame and the other frames are as they were',
	$back === 200 && $gone === 200 && is_file( $sandbox. '/private/templates/frame-header-landing.tpl' ) === false && \Nino\Text::values( $site, '/template/frame-header-landing' ) === [] && isset( Store::frameNames( $site )['frame-header-landing'] ) === false
	&& isset( Store::frames( $site )['header-landing'] ) === false && $marked === true && is_file( \Nino\Filesystem::path( $site, Store::PREVIOUS. '/frame-header-landing.php' ) ) === false && is_file( \Nino\Filesystem::path( $site, Store::FRAME_MARK. '/frame-header-landing.php' ) ) === false && $standard === [ false, false, true ]
	&& $file( 'frame-header' ) === $keep && isset( Store::frames( $site )['header-b'] ) === true && count( array_filter( array_keys( $left ), static fn( string $path ): bool => str_contains( $path, 'frame-header-landing' ) ) ) === 0 );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. What else counts, and what is never read\n";

// the texts: a frame that names its own texts by their key is copied to a frame that names the copy's keys - it never reads the texts of the source
$put( $site, 'frame-header-c', $header. "[text /template/frame-header-c/c/extra]\n" );
[ $copyStatus ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'kopie', 'name' => 'Kopie', 'from' => 'frame-header-c' ] );
$copy = $file( 'frame-header-kopie' );

check( 'a copy of a frame that names its texts by key points at its own keys: no key of the source is left in the template of the copy',
	$copyStatus === 200 && str_contains( $copy, '/template/frame-header-kopie/c/extra' ) === true && str_contains( $copy, '/template/frame-header-c/' ) === false );

// the footer: a frame of the footer is as little taken away as one of the header while a page chooses it; a page that has no route counts, and is named by its file
$home = $file( 'page-home' );
$put( $site, 'page-home', str_replace( '/templates/frame-footer]', '/templates/frame-footer-fuss]', $home ) );
[ $footerUsed, $footerBody ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-footer-fuss' ] );
$put( $site, 'page-home', $home );
$put( $site, 'page-extra', "[frame /templates/frame-header-zwei]\n" );
[ $routeless, $routelessBody ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-zwei' ] );
[ , $routelessList ] = fmCall( $site, 'themelab/frames' );
$routelessRows = array_column( $routelessList['frames'] ?? [], null, 'key' );
$put( $site, 'page-extra', "[template /templates/html-header]\n" );
[ $routelessGone ] = fmCall( $site, 'themelab/frame-remove', [ 'template' => 'frame-header-zwei' ] );

check( 'a footer frame that a page chooses is refused, 409, naming the page; so is a frame that only a template without a route chooses - named by its file, and counted in the list; with that template changed they go',
	$footerUsed === 409 && str_contains( implode( ' ', (array) ( $footerBody['params'] ?? [] ) ), '/home' ) === true && $routeless === 409 && str_contains( implode( ' ', (array) ( $routelessBody['params'] ?? [] ) ), 'page-extra.tpl' ) === true
	&& ( $routelessRows['header-zwei']['uses'] ?? 0 ) === 1 && $routelessGone === 200 );

// the names: two frames of a side are not called alike, in no language; a frame keeps its own name, and the other side may use it
[ $alike ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'gleich', 'name' => [ 'de_DE' => 'aus block', 'en_US' => 'Equal' ], 'from' => 'frame-header' ] );
[ $alikeRename ] = fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header-kopie', 'name' => 'Aus Block' ] );
[ $own ] = fmCall( $site, 'themelab/frame-rename', [ 'template' => 'frame-header-kopie', 'name' => 'KOPIE' ] );
[ $other ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'footer', 'word' => 'gleich', 'name' => 'Aus Block', 'from' => 'frame-footer' ] );

check( 'two frames of one side are not called alike - not in one language, not by a different case - 409 at the name, when made or renamed; a frame may take its own name in another case, and the other side may use a name',
	$alike === 409 && $alikeRename === 409 && $own === 200 && $other === 200 );

// a store of the names that cannot be read is no store: no failure, the frames are called by their words
$names = Store::frameNames( $site );
file_put_contents( $sandbox. '/private/data/builder/frame-names.php', "<?php return [ ;\n" );
unset( $site['./nino/filesystem/cache'][Store::FRAME_NAMES] );
$level = error_reporting( 0 );
[ $brokenLab, $brokenLabBody ] = fmCall( $site, 'themelab/frames' );
[ $brokenList ] = fmCall( $site, 'builder/list' );
error_reporting( $level );
$brokenNames = array_column( $brokenLabBody['frames'] ?? [], 'name' );
\Nino\Filesystem::putFileContent( $site, Store::FRAME_NAMES, $names );

check( 'a store of the names that is broken is no failure: the lists answer 200 and the frames have no name - the word is what they are called by',
	$brokenLab === 200 && $brokenList === 200 && array_filter( $brokenNames, static fn( string $name ): bool => $name !== '' ) === [] );

// A frame is made of a block of its side: the side the schema of the root says - a header root and a footer root of a feature of the test, named like no root of the kernel's
foreach( [ 'probe-head' => 'header', 'probe-foot' => 'footer' ] as $rootName => $side ) {

	\Nino\Components::addComponent( $site, $rootName, [ 'label' => $rootName, 'in' => [ 'page' ], 'offers' => [ 'flow' ], 'block' => $side, 'attributes' => [ 'id' => [ 'type' => 'id', 'default' => '' ] ], 'template' => '<div[[id]] class="[[class]]">[[content]]</div>' ] );
	$folder = $sandbox. '/private/blocks/'. $rootName. '-block';
	mkdir( $folder. '/text', 0755, true );
	file_put_contents( $folder. '/block.json', json_encode( [ 'name' => [ 'en_US' => $rootName ], 'description' => [ 'en_US' => $rootName ], 'category' => $side, 'rank' => 5, 'images' => (object) [] ] ) );
	file_put_contents( $folder. '/block.tpl', '['. $rootName. " id=\"x\"]\n[/". $rootName. "]\n" );
	file_put_contents( $folder. '/text/en_US.json', '{}' );
}

unset( $site['./nino/blocks/state'] );
$templates = static fn(): array => array_map( 'basename', glob( $GLOBALS['sandbox']. '/private/templates/*' ) ?: [] );
$before = $templates();
[ $crossedSide ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'sonde', 'name' => 'Sonde', 'block' => 'probe-foot-block' ] );
$unchanged = $templates() === $before;
[ $rightSide ] = fmCall( $site, 'themelab/frame-create', [ 'side' => 'header', 'word' => 'sonde', 'name' => 'Sonde', 'block' => 'probe-head-block' ] );

check( 'a frame is made of a block of its side whatever the name of the root: the block of a footer makes no header - 404 and nothing is written - and the block of a header does',
	$crossedSide === 404 && $unchanged === true && $rightSide === 200 && str_starts_with( $file( 'frame-header-sonde' ), '[probe-head' ) === true );

ninoDone( $site );
