<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-frame-block-smoke.php	Contract test for the blocks of a header and of a footer among the blocks of Themelab - listed like the blocks of a page, worked on where they are the project's:
 *
 *												E. the block among the blocks of the project: listed - the own ones, and the ones of a theme and of the features, which are only looked at -, copied, its details changed,
 *												   taken away, handed out in a package and taken in by a second site; its picture is drawn like the picture of a block of a page
 *
 *	Usage: php features/Themelab/tests/themelab-frame-block-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-frame-block-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

function fbCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function fbFile( string $path ): string {
	return is_file( $path ) === true ? (string) file_get_contents( $path ) : '';
}

/** What the project keeps, by name and hash: for "nothing else is written" */
function fbSnapshot( array &$appData ): array {

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
function fbShape( array &$appData, string $template ): array {

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

$site = ninoSandbox( 'themelab-frame-block' );
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
\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns' ] );
\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];

ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM, '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$names = [ 'de_DE' => 'Mein Kopf', 'en_US' => 'My head' ];
$about = [ 'de_DE' => 'Kopf der Website', 'en_US' => 'Head of the site' ];


// the block of a header the project has of its own: the frame kept as a block
[ $status, $made ] = fbCall( $site, 'themelab/adopt', [ 'template' => 'frame-header', 'key' => 'mein-kopf', 'name' => $names, 'description' => $about ] );
[ , $madeFoot ] = fbCall( $site, 'themelab/adopt', [ 'template' => 'frame-footer', 'key' => 'mein-fuss', 'name' => [ 'de_DE' => 'Mein Fuss', 'en_US' => 'My foot' ], 'description' => $about ] );
unset( $site['./nino/blocks/state'] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nE. The block among the blocks of the project\n";

\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', $header );
$listAs = static function( array &$site ): array {
	[ $status, $body ] = fbCall( $site, 'themelab/list', [ 'tab' => 'blocks' ] );
	return [ $status, array_column( $body['blocks'] ?? [], null, 'key' ), $body['counts'] ?? [] ];
};
[ $listStatus, $rows, $counts ] = $listAs( $site );

check( 'the list of the own blocks has the block of a frame - the category of its side, marked as a frame - and the tab of the theme counts the own blocks of the frames besides the blocks and the frames',
	$listStatus === 200 && ( $rows['mein-kopf']['frame'] ?? false ) === true && ( $rows['mein-kopf']['category'] ?? '' ) === 'header' && ( $rows['mein-kopf']['group'] ?? '' ) === 'header' && ( $counts['frameBlocks'] ?? 0 ) === count( array_filter( $rows, static fn( array $row ): bool => ( $row['frame'] ?? false ) === true && ( $row['own'] ?? false ) === true ) ) && ( $counts['frameBlocks'] ?? 0 ) >= 2 && ( $counts['blocks'] ?? -1 ) === 0 );

[ $copied, $copy ] = fbCall( $site, 'themelab/duplicate', [ 'key' => 'mein-kopf', 'name' => [ 'de_DE' => 'Mein Kopf Kopie', 'en_US' => 'My head copy' ] ] );
unset( $site['./nino/blocks/state'] );
$copyKey = (string) ( $copy['key'] ?? '' );
$copyTpl = fbFile( $sandbox. '/private/blocks/'. $copyKey. '/block.tpl' );

check( 'a block of a frame is copied as the files it is - the root keeps the id the frame stands on, the words and the category are the first block\'s - and the copy has the name it was given',
	$copied === 200 && $copyKey !== '' && str_starts_with( $copyTpl, '[header-wrap id="header"' ) === true && fbFile( $sandbox. '/private/blocks/'. $copyKey. '/text/de_DE.json' ) === fbFile( $sandbox. '/private/blocks/mein-kopf/text/de_DE.json' )
	&& ( \Nino\Blocks::all( $site )[$copyKey]['category'] ?? '' ) === 'header' && ( \Nino\Blocks::all( $site )[$copyKey]['name']['en_US'] ?? '' ) === 'My head copy' );

[ $renamed ] = fbCall( $site, 'themelab/rename', [ 'key' => 'mein-kopf', 'name' => [ 'de_DE' => 'Kopf der Seite', 'en_US' => 'Page head' ], 'description' => [ 'en_US' => 'Page head block' ], 'category' => 'content', 'rank' => 3 ] );
unset( $site['./nino/blocks/state'] );
$after = \Nino\Blocks::all( $site )['mein-kopf'] ?? [];

check( 'the details of the block of a frame are changed in the list - the name, the description, the rank - and its category stays the category of its side, whatever the request says',
	$renamed === 200 && ( $after['name']['en_US'] ?? '' ) === 'Page head' && ( $after['category'] ?? '' ) === 'header' && ( $after['rank'] ?? 0 ) === 3 && ( $after['side'] ?? '' ) === 'header' );

// the package of it, taken in by a second site
[ $exported, $bytes ] = fbCall( $site, 'themelab/export', [ 'keys' => [ 'mein-kopf' ], 'key' => 'koepfe', 'version' => '1.0.0', 'name' => 'Koepfe', 'description' => 'Ein Kopf.' ] );
$files = is_string( $bytes ) === true ? \Nino\Archive::read( $bytes, 'koepfe', 400, \Nino\Blocks::MAX_PICTURE, \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS]['bytes'] ) : 'none';

$other = ninoSandbox( 'themelab-frame-block-b' );
$otherDir = ninoSandboxDir( $other );
$other['./nino/filesystem/path'] = $root;
$other['/nino/html/assets'] = [];
$other['/nino/html/navs'] = [ 'main' ];
$other['/nino/modules'] = $site['/nino/modules'];
\Nino\Html::init( $other );
\Nino\Features::registerComponents( $other );
\Nino\Modules::callModules( $other, 'init' );
\Nino\Filesystem::putFileContent( $other, '/templates/frame-header.tpl', "[header-wrap id=\"header\"]\n[/header-wrap]\n" );
\Nino\Filesystem::putFileContent( $other, '/templates/page-contact.tpl', "<!-- nino:template-name Contact -->\n" );
foreach( [ 'de_DE', 'en_US' ] as $language )
	\Nino\Filesystem::putFileContent( $other, '/text/'. $language. '.php', [ '[[/template/page-contact/x]]' => 'x' ] );
$other['/nino/http/routes'] = [ 'GET://contact' => [ 'uri' => '/contact', 'body' => '[template /templates/page-contact]' ] ];
ninoUser( $other, 'lab@example.com', [ \Nino\Modules\Themelab::PERM, '/_admin/builder/manage', '/_admin/builder/design/*' ] );

$tmp = $otherDir. '/upload.tmp';
file_put_contents( $tmp, (string) $bytes );
$_FILES['file'] = [ 'name' => 'koepfe.tar.gz', 'type' => 'application/gzip', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( (string) $bytes ) ];
[ $checked ] = fbCall( $other, 'themelab/import-check' );
unset( $_FILES['file'] );
$id = (string) ( $_SESSION[\Nino\Modules\Themelab\Package::SESSION]['id'] ?? '' );
[ $imported, $report ] = fbCall( $other, 'themelab/import', [ 'id' => $id, 'items' => [ 'mein-kopf' => [ 'take' => true ] ] ] );
unset( $other['./nino/blocks/state'] );
$taken = \Nino\Blocks::all( $other )['mein-kopf'] ?? [];

check( 'a block of a frame goes out in a package and comes in at a second site as the block it was - in the category of its side, the root with the id of the frame - and is offered there for the header',
	$exported === 200 && is_array( $files ) === true && isset( $files['mein-kopf/block.tpl'] ) === true && $checked === 200 && $imported === 200 && ( $taken['side'] ?? '' ) === 'header' && ( $taken['category'] ?? '' ) === 'header'
	&& str_starts_with( fbFile( $otherDir. '/private/blocks/mein-kopf/block.tpl' ), '[header-wrap id="header"' ) === true && in_array( 'mein-kopf', array_column( fbCall( $other, 'builder/framechoices', [ 'template' => 'frame-header' ] )[1]['blocks'] ?? [], 'key' ), true ) === true );

[ $dropped ] = fbCall( $site, 'themelab/remove', [ 'key' => $copyKey ] );
unset( $site['./nino/blocks/state'] );

check( 'the block of a frame is taken away from the list like any block of the project, and the blocks beside it stay',
	$dropped === 200 && isset( \Nino\Blocks::all( $site )[$copyKey] ) === false && isset( \Nino\Blocks::all( $site )['mein-kopf'] ) === true && is_dir( $sandbox. '/private/blocks/'. $copyKey ) === false );



// the blocks of a header and of a footer that the project does not own are listed as the blocks of a page are: looked at, not worked on, each with where it comes from; and their picture is drawn
[ , $listed ] = fbCall( $site, 'themelab/list', [ 'tab' => 'blocks' ] );
$all = array_column( $listed['blocks'] ?? [], null, 'key' );
$foreign = array_filter( $all, static fn( array $row ): bool => ( $row['frame'] ?? false ) === true && ( $row['own'] ?? true ) === false );
$footers = array_filter( $foreign, static fn( array $row ): bool => ( $row['category'] ?? '' ) === 'footer' );
[ $tileHead, $tile ] = fbCall( $site, 'themelab/tile', [ 'key' => 'header-line' ] );
[ $tileFoot ] = fbCall( $site, 'themelab/tile', [ 'key' => array_key_first( $footers ) ?? 'x' ] );
[ $tileNone ] = fbCall( $site, 'themelab/tile', [ 'key' => 'nope' ] );
[ $tilePath ] = fbCall( $site, 'themelab/tile', [ 'key' => '../../x' ] );
$frameBlockKeys = array_keys( array_filter( $all, static fn( array $row ): bool => ( $row['frame'] ?? false ) === true ) );
[ $openForeign ] = fbCall( $site, 'themelab/open', [ 'key' => 'header-line' ] );

check( 'the blocks of a header and of a footer the project does not own stand in the list beside the own ones, marked as frames, each with its origin and none of them to work on; the picture of one - of a header or of a footer - is drawn as the picture of a block of a page is, and a key that is none or a path has none',
	isset( $all['header-line'] ) === true && ( $all['header-line']['own'] ?? true ) === false && ( $all['header-line']['openable'] ?? true ) === false && ( $all['header-line']['category'] ?? '' ) === 'header' && $footers !== [] && ( $all['mein-kopf']['own'] ?? false ) === true && ( $all['mein-kopf']['openable'] ?? false ) === true
	&& $tileHead === 200 && ( $tile['token'] ?? '' ) !== '' && $tileFoot === 200 && $tileNone === 404 && $tilePath === 404 && $frameBlockKeys !== [] && $openForeign === 404 );

[ , $info ] = fbCall( $site, 'themelab/list', [ 'tab' => 'info' ] );
ninoUser( $site, 'labonly@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ , $infoLab ] = fbCall( $site, 'themelab/list', [ 'tab' => 'info' ] );

check( 'the way from Themelab to the frames of the site is shown to the account that holds the right of the pages and designs - the list says where they are written - and to no other; the tab of the frames is none: asked for, the list is the list of the blocks',
	( $info['framesAt'] ?? false ) === true && ( $infoLab['framesAt'] ?? true ) === false && isset( $info['frames'] ) === false && fbCall( $site, 'themelab/list', [ 'tab' => 'frames' ] )[1]['tab'] === 'blocks' );

ninoDone( $site );
