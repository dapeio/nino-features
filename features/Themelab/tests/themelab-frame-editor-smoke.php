<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-frame-editor-smoke.php	Contract test for the block of a frame in the editor of the blocks of Themelab - opened, changed, saved:
 *
 *												A. opened: the sheet of a block of a header or a footer has the root of that frame, the words of each language, the stamp; a block of a page is no frame and the other way round
 *												B. saved: the words, a text of the site as the source of a component, a component put in - written below the folder of the block, held to the rule of the kernel, and what the block
 *												   reads of the site is said by the kernel; the stamp of a second session stops a save
 *												C. who and what: the right of this panel and the token, a root that is not the block's, html, a source the kernel refuses - and nothing is written
 *												D. the picture: the preview draws the block of a header over a quiet page and one of a footer under it; the texts of the site are looked for only for the block of a frame
 *
 *	Usage: php features/Themelab/tests/themelab-frame-editor-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-frame-editor-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

function feCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function feFile( string $path ): string {
	return is_file( $path ) === true ? (string) file_get_contents( $path ) : '';
}

/** What the project keeps, by name and hash: for "nothing else is written" */
function feSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text', 'private/blocks', 'private/data/builder' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

/** A frame as the kernel reads it, written the one way: the markup and the words under the frame's template, for comparing */
function feShape( array &$appData, string $template ): array {

	$dir = ninoSandboxDir( $appData );
	$nodes = \Nino\Modules\Builder\Tree::read( $appData, fbFile( $dir. '/private/templates/'. $template. '.tpl' ) );
	$words = [];

	foreach( [ 'de_DE', 'en_US' ] as $locale ) {

		$fills = is_file( $dir. '/private/text/'. $locale. '.php' ) === true ? (array) ( include $dir. '/private/text/'. $locale. '.php' ) : [];

		foreach( $fills as $key => $text )
			if( str_starts_with( (string) $key, '[[/template/'. $template. '/' ) === true )
				$words[$locale][substr( (string) $key, strlen( '[[/template/'. $template ) )] = $text;

		ksort( $words[$locale] );
	}

	return [ \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) ), $words ];
}

$site = ninoSandbox( 'themelab-frame-editor' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$header = "[header-wrap id=\"header\" sticky]\n\t[header-logo]\n\t[header-nav menu=\"main\"]\n\t[header-actions]\n\t\t[button ~button href=\"page:/contact\" size=\"s\"]\n\t[/header-actions]\n[/header-wrap]\n";
\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', $header );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-contact.tpl', "<!-- nino:template-name Contact -->\n[template /templates/html-header]\n" );
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns', '[[/project/company/general/name]]' => 'Probe GmbH', '[[/project/mail/address/owner]]' => 'inhaber@example.test', '[[/_nino/webpage/404/description]]' => 'Nicht gefunden', '[[/_nino/locale/de_DE/name]]' => 'Deutsch', '[[/template/page-home/header/button]]' => 'Text der Seite' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us', '[[/project/company/general/name]]' => 'Sample Ltd', '[[/project/mail/address/owner]]' => 'owner@example.test', '[[/_nino/webpage/404/description]]' => 'Not found', '[[/_nino/locale/de_DE/name]]' => 'German', '[[/template/page-home/header/button]]' => 'Page text' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];

ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );
$names = [ 'de_DE' => 'Mein Kopf', 'en_US' => 'My head' ];
$about = [ 'de_DE' => 'Kopf der Website', 'en_US' => 'Head of the site' ];


$both = [ \Nino\Modules\Themelab::PERM ];
$names = [ 'de_DE' => 'Mein Kopf', 'en_US' => 'My head' ];

[ $adopted ] = feCall( $site, 'themelab/frame-adopt', [ 'template' => 'frame-header', 'key' => 'mein-kopf', 'name' => $names, 'description' => $names ] );
unset( $site['./nino/blocks/state'] );
$page = [ 'template' => 'page-home' ];

echo "\nA. Opened\n";

[ $openStatus, $open ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$rootAt = static function( array $tree ): ?int { foreach( $tree as $at => $node ) if( isset( $node['n'] ) === true && ( \Nino\Blocks::wraps( $GLOBALS['site'] )[$node['n']] ?? '' ) !== '' ) return (int) $at; return null; };
$at = $rootAt( $open['tree'] ?? [] );

$sharedAtOpen = (array) ( $open['shared'] ?? [ 'x' ] );

check( 'the block of a header is opened in the editor: its root is the header, it is the project\'s - editable -, it has a stamp, the words of each language under the page the sheet is drawn on',
	$adopted === 200 && $openStatus === 200 && ( $open['side'] ?? '' ) === 'header' && ( $open['editable'] ?? false ) === true && ( $open['tree'][$at]['n'] ?? '' ) === 'header-wrap' && ( $open['stamp'] ?? '' ) !== ''
	&& ( $open['texts']['/template/page-home/header/button']['de_DE'] ?? '' ) === 'Schreib uns' && ( $open['texts']['/template/page-home/header/button']['en_US'] ?? '' ) === 'Write to us' && $sharedAtOpen === [] );

// a block of a page is no frame and the other way round: the kernel's word for the root decides, never the request's
[ , $pageBlocks ] = feCall( $site, 'themelab/list', [ 'tab' => 'project' ] );
[ $notFound ] = feCall( $site, 'themelab/open', [ 'key' => 'nothing-here' ] );

echo "\nB. Saved\n";

$walk = static function( array $nodes, callable $visit ) use ( &$walk ): void { foreach( $nodes as $at => $node ) { if( isset( $node['n'] ) === true ) $visit( $node ); if( is_array( $node['c'] ?? null ) === true ) $walk( $node['c'], $visit ); } };
$change = static function( array $tree, callable $edit ): array {

	$go = static function( array $nodes ) use ( &$go, $edit ): array {

		foreach( $nodes as $at => $node ) {

			if( isset( $node['n'] ) === false )
				continue;

			$node = $edit( $node );

			if( is_array( $node['c'] ?? null ) === true )
				$node['c'] = $go( $node['c'] );

			$nodes[$at] = $node;
		}

		return $nodes;
	};

	return $go( $tree );
};

$texts = $open['texts'];
$texts['/template/page-home/header/button'] = [ 'de_DE' => 'Melde Dich', 'en_US' => 'Get in touch' ];
$tree = $change( $open['tree'], static function( array $node ): array { if( $node['n'] === 'header-wrap' ) unset( $node['a']['sticky'] ); return $node; } );
$before = feSnapshot( $site );
[ $saved, $answer ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $tree, 'texts' => $texts, 'base' => $open['stamp'] ] );
$written = array_keys( array_diff_assoc( feSnapshot( $site ), $before ) );
sort( $written );
$block = json_decode( feFile( $sandbox. '/private/blocks/mein-kopf/text/de_DE.json' ), true ) ?: [];
$tpl = feFile( $sandbox. '/private/blocks/mein-kopf/block.tpl' );

check( 'the words of a block of a frame are saved in each language, below the folder of the block and nowhere else: the header is still a header, the block is still a block of the category of its side',
	$saved === 200 && ( $answer['side'] ?? '' ) === 'header' && $written !== [] && array_filter( $written, static fn( string $file ): bool => str_starts_with( $file, '/private/blocks/mein-kopf/' ) === false ) === [] && ( $block['button'] ?? '' ) === 'Melde Dich'
	&& str_starts_with( $tpl, '[header-wrap id="header"' ) === true && str_contains( $tpl, 'sticky' ) === false && ( \Nino\Blocks::all( $site )['mein-kopf']['category'] ?? '' ) === 'header' && ( \Nino\Blocks::all( $site )['mein-kopf']['side'] ?? '' ) === 'header' );

// a text of the site as the source of a component, and a component put in
unset( $site['./nino/blocks/state'] );
[ , $again ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$texts = $again['texts'];
$tree = $change( $again['tree'], static function( array $node ): array { if( $node['n'] === 'header-actions' ) $node['c'][] = [ 'n' => 'button', 's' => '/project/company/general/name', 'a' => [ 'size' => 's' ], 'c' => null ]; return $node; } );
[ $sourced, $sourcedAnswer ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $tree, 'texts' => $texts, 'base' => $again['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$read = \Nino\Blocks::reads( \Nino\Blocks::get( $site, 'mein-kopf' ) ?? [] );

check( 'a component of a block of a frame reads a text of the site - a key of the text store - and a component put in is saved: the block holds both, and the kernel says what the block reads of the site',
	$sourced === 200 && str_contains( feFile( $sandbox. '/private/blocks/mein-kopf/block.tpl' ), '/project/company/general/name' ) === true && $read === [ '/project/company/general/name' ] );

[ , $reopened ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );

check( 'what the text of the site says is given to the editor in the language of the sheet - so that the component shows it - and is no text of the block: the texts of the block are its own',
	( ( (array) $reopened['shared'] )['/project/company/general/name'] ?? '' ) === 'Probe GmbH' && isset( ( (array) $reopened['texts'] )['/project/company/general/name'] ) === false );

// a text that is new has the words of one language: the block takes it for the others from the language the sheet is worked on, and the category of the block is its side's and no other
[ , $fresh ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$extra = $fresh['texts'];
$extra['/template/page-home/header/extra'] = [ 'de_DE' => 'Neu hier' ];
$more = $change( $fresh['tree'], static function( array $node ): array { if( $node['n'] === 'header-actions' ) $node['c'][] = [ 'n' => 'button', 's' => '~extra', 'a' => [], 'c' => null ]; return $node; } );
[ $filled ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $more, 'texts' => $extra, 'base' => $fresh['stamp'] ] );
$english = json_decode( feFile( $sandbox. '/private/blocks/mein-kopf/text/en_US.json' ), true ) ?: [];
unset( $site['./nino/blocks/state'] );
[ , $fresh2 ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
[ $content ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $fresh2['tree'], 'texts' => $fresh2['texts'], 'base' => $fresh2['stamp'], 'details' => [ 'category' => 'content' ] ] );
[ $footerCategory ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $fresh2['tree'], 'texts' => $fresh2['texts'], 'base' => $fresh2['stamp'], 'details' => [ 'category' => 'footer' ] ] );
[ $frames ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $fresh2['tree'], 'texts' => $fresh2['texts'], 'base' => $fresh2['stamp'], 'details' => [ 'category' => 'header' ] ] );

check( 'a text that is new, written in one language, is the block\'s in the others as well - from the language the sheet is worked on - and the block stays of the category of its side: the category "content" and the category of the other side are refused, its own is taken',
	$filled === 200 && ( $english['extra'] ?? '' ) === 'Neu hier' && $content === 400 && $footerCategory === 400 && $frames === 200 );

echo "\nC. Who and what\n";

[ , $now ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$snapshot = feSnapshot( $site );
$send = static function( array $tree, array $more = [] ) use ( &$site, $now ): int { return feCall( $site, 'themelab/save', $more + [ 'key' => 'mein-kopf', 'tree' => $tree, 'texts' => $now['texts'], 'base' => $now['stamp'] ] )[0]; };
$html = $change( $now['tree'], static function( array $node ): array { if( $node['n'] === 'header-actions' ) $node['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => null ]; return $node; } );
$foreign = $change( $now['tree'], static function( array $node ): array { if( $node['n'] === 'button' ) $node['s'] = '/_admin/secret'; return $node; } );
$climbing = $change( $now['tree'], static function( array $node ): array { if( $node['n'] === 'button' ) $node['s'] = '/project/../../etc/passwd'; return $node; } );
$section = $change( $now['tree'], static function( array $node ): array { if( $node['n'] === 'header-wrap' ) $node['n'] = 'section-wrap'; return $node; } );
$footer = $change( $now['tree'], static function( array $node ): array { if( $node['n'] === 'header-wrap' ) $node['n'] = 'footer-wrap'; return $node; } );
$refused = [ 'html' => $send( $html ), 'a source only the site has' => $send( $foreign ), 'a key that climbs' => $send( $climbing ), 'a root that is a section' => $send( $section ), 'a root that is a footer' => $send( $footer ), 'a stamp that is old' => $send( $now['tree'], [ 'base' => str_repeat( 'a', 64 ) ] ), 'a key that is none' => $send( $now['tree'], [ 'key' => '../mein-kopf' ] ), 'a key that is no frame block' => $send( $now['tree'], [ 'key' => 'nothing' ] ) ];

check( 'what a block of a frame may not hold is refused and nothing is written: html, a source that only the site knows, a key that climbs out of the store, another root than the block\'s - a section or a footer for a header -, the stamp of another session, a key that is a path or none',
	array_filter( $refused, static fn( int $status ): bool => in_array( $status, [ 400, 403, 404, 409 ], true ) === false ) === [] && feSnapshot( $site ) === $snapshot );

ninoUser( $site, 'builder@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$noRight = [ feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] )[0], $send( $now['tree'] ), feCall( $site, 'themelab/keys', [ 'key' => 'mein-kopf', 'kind' => 'text' ] )[0], feCall( $site, 'themelab/preview', [ 'key' => 'mein-kopf', 'tree' => $now['tree'] ] )[0] ];
ninoUser( $site, 'lab@example.com', $both );
$tokenless = [];

foreach( [ 'themelab/open' => [ 'key' => 'mein-kopf' ], 'themelab/save' => [ 'key' => 'mein-kopf', 'tree' => $now['tree'], 'texts' => $now['texts'], 'base' => $now['stamp'] ], 'themelab/keys' => [ 'key' => 'mein-kopf', 'kind' => 'text' ] ] as $action => $data ) {

	$_POST['data'] = json_encode( $data + [ 'locale' => 'de_DE' ] );
	$_POST['action'] = $action;
	$request = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
	\Nino\Admin\Admin::handlePost( $site, $request );
	$tokenless[$action] = $request['/nino/http/response']['statusCode'];
}

check( 'the right of this panel and the token of the session are what it takes: an account with the right of the Builder only and a request without the token are 403 on the sheet of a frame, its save and its texts of the site, and nothing is written',
	$noRight === [ 403, 403, 403, 403 ] && array_unique( array_values( $tokenless ) ) === [ 403 ] && feSnapshot( $site ) === $snapshot );

echo "\nD. The texts of the site, and the picture\n";

[ $keysStatus, $keys ] = feCall( $site, 'themelab/keys', [ 'key' => 'mein-kopf', 'kind' => 'text', 'query' => 'name' ] );
[ , $noKey ] = feCall( $site, 'themelab/keys', [ 'kind' => 'text', 'query' => 'name' ] );
[ , $pageKey ] = feCall( $site, 'themelab/keys', [ 'key' => 'karten', 'kind' => 'text', 'query' => 'name' ] );

check( 'the texts of the site are offered for the block of a frame - the keys of the store with the words each has - and for no other: not for a sheet without a block, not for the block of a page',
	$keysStatus === 200 && in_array( '/project/company/general/name', array_column( $keys['entries'] ?? [], 'key' ), true ) === true && ( $noKey['entries'] ?? [ 1 ] ) === [] && ( $pageKey['entries'] ?? [ 1 ] ) === [] );

[ $previewStatus, $preview ] = feCall( $site, 'themelab/preview', [ 'key' => 'mein-kopf', 'tree' => $now['tree'], 'texts' => $now['texts'], 'hint' => 'The page' ] );
$heldRequest = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => $preview['token'] ?? 'x' ] ] ];
$held = \Nino\Preview::held( $site, $heldRequest, \Nino\Modules\Themelab::KIND, \Nino\Modules\Themelab::PERM );

check( 'the picture of the block of a header is drawn for the side the kernel gives the block: the draft says the header, and the preview answers the page the sheet is drawn on',
	$previewStatus === 200 && ( $preview['address'] ?? '' ) !== '' && ( $held['data']['side'] ?? '' ) === 'header' );

// the footer is a block of a frame as well: its root is the footer, and the preview puts the quiet page over it
[ $footerAdopted ] = feCall( $site, 'themelab/frame-adopt', [ 'template' => 'frame-footer', 'key' => 'mein-fuss', 'name' => [ 'de_DE' => 'Mein Fuss', 'en_US' => 'My foot' ], 'description' => [ 'de_DE' => 'Fuss', 'en_US' => 'Foot' ] ] );
unset( $site['./nino/blocks/state'] );
[ $footerOpened, $footerOpen ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-fuss' ] );
[ $footerSaved, $footerAnswer ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-fuss', 'tree' => $footerOpen['tree'] ?? [], 'texts' => $footerOpen['texts'] ?? [], 'base' => $footerOpen['stamp'] ?? '' ] );
[ $swapped ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $footerOpen['tree'] ?? [], 'texts' => $footerOpen['texts'] ?? [], 'base' => $now['stamp'] ] );

check( 'the block of a footer is opened and saved with the root of a footer - a tree of a footer is no tree for a header - ',
	$footerAdopted === 200 && $footerOpened === 200 && ( $footerOpen['side'] ?? '' ) === 'footer' && $footerSaved === 200 && ( $footerAnswer['side'] ?? '' ) === 'footer' && $swapped === 400
 );

echo "\nE. Fixrunde: the root, the page of the picture, the keys the kernel takes, the languages\n";

// the root is the kernel's word for the block: a request that names one changes nothing
[ , $named ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf', 'side' => 'footer' ] );
[ , $named2 ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-fuss', 'side' => 'page' ] );
[ , $plain ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
[ $withWrap ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'side' => 'page', 'tree' => $plain['tree'], 'texts' => $plain['texts'], 'base' => $plain['stamp'] ] );
[ $swapRoot ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'side' => 'footer', 'tree' => $footerOpen['tree'] ?? [], 'texts' => $plain['texts'], 'base' => $plain['stamp'] ] );
[ , $previewNamed ] = feCall( $site, 'themelab/preview', [ 'key' => 'mein-kopf', 'side' => 'footer', 'tree' => $plain['tree'], 'texts' => $plain['texts'] ] );
$heldRequest = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => $previewNamed['token'] ?? 'x' ] ] ];
$heldNamed = \Nino\Preview::held( $site, $heldRequest, \Nino\Modules\Themelab::KIND, \Nino\Modules\Themelab::PERM );

check( 'the side is the kernel\'s word for the block and never the request\'s: a request that names a footer for a header, or a page for a footer, opens the side the block has, saves it and is drawn for it - and a tree of another side is refused whatever the request says',
	( $named['side'] ?? '' ) === 'header' && ( $named2['side'] ?? '' ) === 'footer' && $withWrap === 200 && $swapRoot === 400 && ( $heldNamed['data']['side'] ?? '' ) === 'header' );

// the page of the picture: a header stands over it and a footer under it, by the root of the block; the root of the draft is the one asked for
[ , $headPreview ] = feCall( $site, 'themelab/preview', [ 'key' => 'mein-kopf', 'tree' => $plain['tree'], 'texts' => $plain['texts'] ] );
[ , $footOpenNow ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-fuss' ] );
[ , $footPreview ] = feCall( $site, 'themelab/preview', [ 'key' => 'mein-fuss', 'tree' => $footOpenNow['tree'], 'texts' => $footOpenNow['texts'] ] );
$draftOf = static function( array $preview ) use ( &$site ): array { $request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => $preview['token'] ?? 'x' ] ] ]; return (array) ( \Nino\Preview::held( $site, $request, \Nino\Modules\Themelab::KIND, \Nino\Modules\Themelab::PERM )['data'] ?? [] ); };
$headDraft = $draftOf( $headPreview );
$footDraft = $draftOf( $footPreview );
$headPage = \Nino\Modules\Themelab::sheetPage( $site, $headDraft );
$footPage = \Nino\Modules\Themelab::sheetPage( $site, $footDraft );
$mixed = \Nino\Modules\Themelab::sheetPage( $site, [ 'side' => 'page' ] + $headDraft );
$hintWord = \Nino\Modules\Themelab\Sheet::words( [ 'de_DE' ], 'preview/page' )['de_DE'];

check( 'the picture of the block of a header has the quiet page after the block and one of a footer before it; a draft whose side is not the one of its source is no picture, and the sentence on the page is the panel\'s own word for the language',
	$headPage !== null && $footPage !== null && str_contains( $headPage[1], 'themelab-place' ) === true && str_contains( $headPage[0], 'themelab-place' ) === false && str_contains( $footPage[0], 'themelab-place' ) === true && str_contains( $footPage[1], 'themelab-place' ) === false
	&& $mixed === null && $hintWord !== '' && str_contains( $headPage[1], $hintWord ) === true );

// the sentence is the panel's: a sentence in the request is not read; one that reaches the page is cleaned of tags and of markup characters - and what is left is written as text
[ , $hostile ] = feCall( $site, 'themelab/preview', [ 'key' => 'mein-kopf', 'hint' => '</main><script>alert(1)</script>"[[x]]', 'tree' => $plain['tree'], 'texts' => $plain['texts'] ] );
$fromRequest = $draftOf( $hostile );
$cleaned = \Nino\Modules\Themelab::sheetPage( $site, [ 'hint' => '<script>alert(1)</script>' ] + $headDraft );
$escaped = \Nino\Modules\Themelab::sheetPage( $site, [ 'hint' => "it's" ] + $headDraft );

check( 'the sentence of the quiet page is not read from the request - it is the panel\'s own word -, and a sentence that reaches the page is cleaned of its tags, so that no script name is left, and written as text, so that a quote is an entity',
	( $fromRequest['hint'] ?? '' ) === $hintWord && $cleaned !== null && str_contains( $cleaned[1], 'script' ) === false && str_contains( $cleaned[1], 'alert(1)' ) === true && $escaped !== null && str_contains( $escaped[1], 'it&#039;s' ) === true );

// the keys of the site that the kernel takes as the source of a call in a block of a frame - and only those - are offered, each of them passes the rule of the kernel
[ , $offered ] = feCall( $site, 'themelab/keys', [ 'key' => 'mein-kopf', 'kind' => 'text', 'query' => '' ] );
$listed = array_column( $offered['entries'] ?? [], null, 'key' );
$passes = static function( string $key ) use ( &$site ): bool {

	$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
	$name = [ 'en_US' => 'Probe' ];
	$parts = [ 'json' => json_encode( [ 'name' => $name, 'description' => $name, 'category' => 'header', 'rank' => 0 ], $flags ). "\n", 'tpl' => "[header-wrap id=\"probe\"]\n\t[header-actions]\n\t\t[button ". $key. "]\n\t[/header-actions]\n[/header-wrap]\n", 'texts' => [ 'en_US' => "{}\n" ] ];

	return is_array( \Nino\Blocks::validate( $site, 'probe', $parts ) );
};

check( 'the texts of the site that are offered are those the kernel takes in a block of a frame: each offered key passes the rule of the kernel, and the keys of the workbench itself - /_nino/... - are not offered',
	count( $listed ) >= 2 && array_filter( array_keys( $listed ), static fn( string $key ): bool => $passes( $key ) === false ) === [] && isset( $listed['/_nino/webpage/404/description'] ) === false && isset( $listed['/_nino/locale/de_DE/name'] ) === false && isset( $listed['/project/company/general/name'] ) === true );

check( 'a text the website does not offer in its lists - the mailbox of the forms - is offered and marked as one that is not shown openly otherwise; the name of the company is not marked',
	( $listed['/project/mail/address/owner']['withheld'] ?? false ) === true && ( $listed['/project/company/general/name']['withheld'] ?? true ) === false );

// the languages: a new text is given to the other languages from the working language and the answer says for which; a text that is there is never written over
[ , $base ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$words = $base['texts'];
$words['/template/page-home/header/third'] = [ 'de_DE' => 'Drittens' ];
$words['/template/page-home/header/extra'] = [ 'de_DE' => 'Neu hier geaendert', 'en_US' => 'English own' ];
$grown = $change( $base['tree'], static function( array $node ): array { if( $node['n'] === 'header-actions' && count( array_filter( $node['c'], static fn( array $one ): bool => ( $one['s'] ?? '' ) === '~third' ) ) === 0 ) $node['c'][] = [ 'n' => 'button', 's' => '~third', 'a' => [], 'c' => null ]; return $node; } );
[ $grownStatus, $grownAnswer ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $grown, 'texts' => $words, 'base' => $base['stamp'] ] );
$en = json_decode( feFile( $sandbox. '/private/blocks/mein-kopf/text/en_US.json' ), true ) ?: [];
$filledLocales = array_column( (array) ( $grownAnswer['filled'] ?? [] ), 'locale' );
$filledParts = array_merge( ...array_column( (array) ( $grownAnswer['filled'] ?? [] ), 'parts' ) ?: [ [] ] );

check( 'a text that is new, in one language, is given to the other languages and the answer says which languages and which parts; a text that is there in the other language is not written over, and is not said',
	$grownStatus === 200 && ( $en['third'] ?? '' ) === 'Drittens' && ( $en['extra'] ?? '' ) === 'English own' && $filledLocales === [ 'en_US' ] && count( $filledParts ) === 1 );

// a language that was emptied lacks the text - it has not the text of an empty word: it is given the text of the working language, or, with no such text, named with the part
unset( $site['./nino/blocks/state'] );
[ , $again2 ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$emptied = $again2['texts'];
$emptied['/template/page-home/header/third'] = [ 'de_DE' => 'Drittens', 'en_US' => '' ];
[ $emptiedStatus, $emptiedAnswer ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $again2['tree'], 'texts' => $emptied, 'base' => $again2['stamp'] ] );
unset( $site['./nino/blocks/state'] );
[ , $again3 ] = feCall( $site, 'themelab/open', [ 'key' => 'mein-kopf' ] );
$noDonor = $again3['texts'];
$noDonor['/template/page-home/header/third'] = [ 'de_DE' => '', 'en_US' => '' ];
[ $noDonorStatus, $noDonorAnswer ] = feCall( $site, 'themelab/save', [ 'key' => 'mein-kopf', 'tree' => $again3['tree'], 'texts' => $noDonor, 'base' => $again3['stamp'] ] );

check( 'a language that was emptied lacks the text and is told so: with the working language\'s text it is filled and said, and where no language has one the part is named as the one with no text in any language',
	$emptiedStatus === 200 && in_array( 'en_US', array_column( (array) ( $emptiedAnswer['filled'] ?? [] ), 'locale' ), true ) === true && $noDonorStatus === 400 && str_contains( implode( ' ', (array) ( $noDonorAnswer['params'] ?? [] ) ), '[button' ) === true );

// the picture of a block of a frame in the list: the account that holds the right of this panel and none of the Builder is given it
ninoUser( $site, 'only@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ $tileStatus, $tile ] = feCall( $site, 'themelab/frame-tile', [ 'template' => 'frame-header', 'key' => 'header-line' ] );
$tileRequest = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => $tile['token'] ?? 'x' ] ] ];
$tileHeld = \Nino\Preview::held( $site, $tileRequest, \Nino\Modules\Builder\Library::KIND, \Nino\Modules\Builder::BRIDGE );
ninoUser( $site, 'lab@example.com', $both );

check( 'an account with the right of this panel and none of the Builder is given the picture of a block of a frame: the answer is 200 with a token the right of this panel holds - the draft is held for that right',
	$tileStatus === 200 && ( $tile['token'] ?? '' ) !== '' && is_array( $tileHeld ) === true );

ninoDone( $site );
