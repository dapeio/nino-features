<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-frames-named-smoke.php	Contract test for the frames of a site - as many as it has, not two: the standard frame of each side and the named ones, frame-<side>-<word>:
 *
 *												A. the list: one source - Store::frames() - with the standard first, the named ones in the order of their names, and nothing whose name or file is not right;
 *												   side() says which side a name is of by its form alone
 *												B. a named frame is a frame like the standard one: in the list of the Builder and in that of Themelab, opened and saved in the editor, given a block by the
 *												   choice of the Builder - in its own file and no other - and a header block only in a header
 *												C. what is no frame is refused by every action of a frame, of the Builder and of Themelab, like a foreign template: a path, a capital, none, another side, a page, a
 *												   word that is too long, a name of the right form that has no file - and nothing is written
 *
 *	Usage: php features/Themelab/tests/themelab-frames-named-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-frames-named-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;

function namedCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

function namedSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/templates', 'private/text', 'private/data', 'private/blocks' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

$site = ninoSandbox( 'frames-named' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$head = static fn( string $id ): string => "[header-wrap id=\"". $id. "\"]\n\t[header-logo]\n[/header-wrap]\n";
$foot = static fn( string $id ): string => "[footer-wrap id=\"". $id. "\"]\n[/footer-wrap]\n";
$long = 'frame-header-'. str_repeat( 'a', 41 );
$put = static function( array &$site, string $name, string $source ): void { \Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source ); };

foreach( [ 'frame-header' => $head( 'header' ), 'frame-footer' => $foot( 'footer' ), 'frame-header-landing' => str_replace( 'id="landing"', 'id="landing" sticky', $head( 'landing' ) ), 'frame-header-b' => $head( 'b' ), 'frame-footer-alt' => $foot( 'alt' ),
	'frame-header-X' => $head( 'x' ), 'frame-header-none' => $head( 'x' ), 'frame-header-default' => $head( 'x' ), 'frame-body-x' => $head( 'x' ), 'frame-header-a.b' => $head( 'x' ), $long => $head( 'x' ),
	'page-x' => "[template /templates/html-header]\n", 'frame-header-' => $head( 'x' ) ] as $name => $source )
	$put( $site, $name, $source );

@mkdir( $sandbox. '/private/templates/frame-header-folder.tpl', 0755, true );
\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
foreach( [ 'de_DE', 'en_US' ] as $language )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $language. '.php', [ '[[/template/frame-header-landing/landing/x]]' => 'x' ] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The list\n";

$frames = Store::frames( $site );
$sides = [ 'frame-header' => 'header', 'frame-footer' => 'footer', 'frame-header-landing' => 'header', 'frame-header-a' => 'header', 'frame-footer-x1-y2' => 'footer', 'frame-header-X' => null, 'frame-header-none' => null, 'frame-header-default' => null,
	'frame-body-x' => null, 'frame-header-a.b' => null, 'frame-header-../x' => null, $long => null, 'page-x' => null, 'frame-header-' => null, 'frame-header-a--b' => null, 'frame-headerx' => null, '' => null, 'frame-header-a b' => null ];
$wrong = array_filter( $sides, static fn( ?string $side, string $name ): bool => Store::side( $name ) !== $side, ARRAY_FILTER_USE_BOTH );

check( 'the frames of a site are listed from one place: of each side the standard frame first, then the named ones by their names, the header before the footer - and no template whose name is not of the form of a frame, no word that is none or default, none that is too long',
	array_keys( $frames ) === [ 'header', 'header-b', 'header-landing', 'footer', 'footer-alt' ] && $frames['header'] === 'frame-header' && $frames['header-landing'] === 'frame-header-landing' && array_values( $frames )[3] === 'frame-footer' );

check( 'side() says which side a name is of by the form of the name alone - a path, a capital, a dot, a space, a double hyphen, none, default, a word of 41 characters, another side and a page are none - and isFrame() asks for the file of a named one',
	$wrong === [] && Store::isFrame( $site, 'frame-header-landing' ) === true && Store::isFrame( $site, 'frame-header-nofile' ) === false && Store::isFrame( $site, 'frame-header' ) === true );

check( 'the root a block must have for a frame follows its side - a header-wrap for a header, a named one too, a footer-wrap for a footer - and a section-wrap for everything else; a folder that has the name of a frame is none',
	\Nino\Modules\Builder\Library::wrapOf( 'frame-header-landing' ) === 'header-wrap' && \Nino\Modules\Builder\Library::wrapOf( 'frame-footer-alt' ) === 'footer-wrap' && \Nino\Modules\Builder\Library::wrapOf( 'frame-header' ) === 'header-wrap'
	&& \Nino\Modules\Builder\Library::wrapOf( 'page-x' ) === 'section-wrap' && \Nino\Modules\Builder\Library::wrapOf( 'frame-header-X' ) === 'section-wrap' && isset( $frames['header-folder'] ) === false );

/** The templates that count as frames by the answer of the kernel - where it has one - and by the folder: the same, whatever the form of each */
$agree = static function( array &$site ) use ( $sandbox ): array {

	$kernel = new ReflectionMethod( Store::class, '_kernelFrames' );
	$scan = new ReflectionMethod( Store::class, '_scanFrames' );
	$known = static function( ?array $names ) use ( &$site ): ?array {

		if( $names === null )
			return null;

		$names = array_values( array_filter( array_unique( $names ), static fn( string $name ): bool => Store::side( $name ) !== null && is_file( ninoSandboxDir( $site ). '/private/templates/'. $name. '.tpl' ) === true ) );
		sort( $names );

		return $names;
	};

	return [ $known( $kernel->invokeArgs( null, [ &$site ] ) ), $known( $scan->invokeArgs( null, [ &$site ] ) ) ];
};
[ $viaKernel, $viaFolder ] = $agree( $site );

check( 'where the kernel lists the frames - side => [ default, the words ] - it is the same list as the folder gives, and a kernel that does not list them gives none here so that the folder is read: never no frames',
	$viaFolder !== [] && ( $viaKernel === null || $viaKernel === $viaFolder ) && Store::frames( $site ) !== [] );

// a site whose standard header is gone, and one that has no named frame
unlink( $sandbox. '/private/templates/frame-header.tpl' );
[ $kernelGone, $folderGone ] = $agree( $site );
$withoutStandard = Store::frames( $site );
$keep = [];

foreach( [ 'frame-header-landing', 'frame-header-b', 'frame-footer-alt' ] as $name ) {
	$keep[$name] = (string) file_get_contents( $sandbox. '/private/templates/'. $name. '.tpl' );
	unlink( $sandbox. '/private/templates/'. $name. '.tpl' );
}

[ $kernelNone, $folderNone ] = $agree( $site );
$plain = Store::frames( $site );

foreach( $keep as $name => $source )
	file_put_contents( $sandbox. '/private/templates/'. $name. '.tpl', $source );

file_put_contents( $sandbox. '/private/templates/frame-header.tpl', $head( 'header' ) );

check( 'the kernel and the folder give the same frames with no standard header file - the standard stays in the list: the list of the pages filters a frame that has no file - and with no named frame at all, where the two standards are what is left',
	( $kernelGone === null || $kernelGone === $folderGone ) && array_keys( $withoutStandard ) === [ 'header', 'header-b', 'header-landing', 'footer', 'footer-alt' ] && ( $kernelNone === null || $kernelNone === $folderNone ) && array_keys( $plain ) === [ 'header', 'footer' ] );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. A named frame is a frame\n";

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $listed ] = namedCall( $site, 'builder/list' );
$rows = array_column( $listed['frames'] ?? [], null, 'key' );

check( 'the list of the pages has a row for each frame of the site - the named ones too, with their side and template - in the order of the list',
	array_keys( $rows ) === [ 'header', 'header-b', 'header-landing', 'footer', 'footer-alt' ] && ( $rows['header-landing']['template'] ?? '' ) === 'frame-header-landing' && ( $rows['header-landing']['side'] ?? '' ) === 'header' && ( $rows['footer-alt']['side'] ?? '' ) === 'footer' );

ninoUser( $site, 'lab@example.com', [ \Nino\Modules\Themelab::PERM ] );
[ , $lab ] = namedCall( $site, 'themelab/frames' );
[ $openStatus, $opened ] = namedCall( $site, 'themelab/frame-open', [ 'template' => 'frame-header-landing' ] );
$tree = $opened['tree'] ?? [];
unset( $tree[0]['a']['sticky'] );
[ $saveStatus ] = namedCall( $site, 'themelab/frame-save', [ 'template' => 'frame-header-landing', 'tree' => $tree, 'texts' => [], 'base' => $opened['stamp'] ?? '' ] );

check( 'the tab of the frames of Themelab lists them all, a named frame opens in the editor as the standard one does and is saved in its own file - the other frames are as they were',
	array_column( $lab['frames'] ?? [], 'key' ) === [ 'header', 'header-b', 'header-landing', 'footer', 'footer-alt' ] && $openStatus === 200 && ( $opened['frame'] ?? false ) === true && $saveStatus === 200 && ( $opened['tree'][0]['a']['id'] ?? '' ) === 'landing'
	&& str_contains( (string) file_get_contents( $sandbox. '/private/templates/frame-header-landing.tpl' ), 'sticky' ) === false && (string) file_get_contents( $sandbox. '/private/templates/frame-header.tpl' ) === $head( 'header' ) );

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $offer ] = namedCall( $site, 'builder/framechoices', [ 'template' => 'frame-header-landing' ] );
[ , $footOffer ] = namedCall( $site, 'builder/framechoices', [ 'template' => 'frame-footer-alt' ] );
$headerKeys = array_column( $offer['blocks'] ?? [], 'key' );
$footerKeys = array_column( $footOffer['blocks'] ?? [], 'key' );
[ , $listed ] = namedCall( $site, 'builder/list' );
$stamp = (string) ( array_column( $listed['frames'] ?? [], 'stamp', 'key' )['header-landing'] ?? '' );
$beforeChoice = [ (string) file_get_contents( $sandbox. '/private/templates/frame-header.tpl' ), (string) file_get_contents( $sandbox. '/private/templates/frame-header-b.tpl' ), (string) file_get_contents( $sandbox. '/private/templates/frame-footer-alt.tpl' ) ];
[ $wrongSide ] = namedCall( $site, 'builder/framechoose', [ 'template' => 'frame-header-landing', 'key' => $footerKeys[0] ?? 'footer-columns', 'base' => $stamp ] );
[ $chose ] = namedCall( $site, 'builder/framechoose', [ 'template' => 'frame-header-landing', 'key' => $headerKeys[0] ?? 'header-line', 'base' => $stamp ] );
$landing = (string) file_get_contents( $sandbox. '/private/templates/frame-header-landing.tpl' );

check( 'the choice of the Builder for a named frame offers the blocks of its side, a block of the other side is refused, and the block that is chosen is written in the file of that frame - and in no other',
	$headerKeys !== [] && $footerKeys !== [] && array_intersect( $headerKeys, $footerKeys ) === [] && $wrongSide === 404 && $chose === 200 && str_contains( $landing, 'id="landing"' ) === false && str_contains( $landing, '[header-wrap' ) === true
	&& $beforeChoice === [ (string) file_get_contents( $sandbox. '/private/templates/frame-header.tpl' ), (string) file_get_contents( $sandbox. '/private/templates/frame-header-b.tpl' ), (string) file_get_contents( $sandbox. '/private/templates/frame-footer-alt.tpl' ) ]
	&& is_file( $sandbox. '/private/data/builder/frame-choice/frame-header-landing.php' ) === true && is_file( $sandbox. '/private/data/builder/frame-choice/frame-header.php' ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. What is no frame\n";

$names = [ 'frame-header-../x', 'frame-header-X', 'frame-header-none', 'frame-header-default', 'frame-body-x', 'page-x', $long, 'frame-header-nofile', 'frame-footer-nofile', '/templates/frame-header', 'frame-header.tpl', '' ];
$builderActions = [ 'builder/framechoices' => [ 'key' => 'header-line' ], 'builder/frametile' => [ 'key' => 'header-line' ], 'builder/framechoose' => [ 'key' => 'header-line', 'base' => 'x' ], 'builder/framerestore' => [ 'base' => 'x', 'force' => true ] ];
$labActions = [];

foreach( [ 'open', 'preview', 'save', 'restore', 'blocks', 'block', 'tile', 'keys', 'variant', 'offer', 'notation', 'upload', 'unstage', 'adopt' ] as $action )
	$labActions['themelab/frame-'. $action] = [ 'tree' => [], 'texts' => [], 'base' => 'x', 'key' => 'header-line', 'name' => [ 'de_DE' => 'N', 'en_US' => 'N' ], 'at' => '0', 'uri' => 'x', 'files' => [] ];

ninoUser( $site, 'both@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', '/_admin/builder/content/*', \Nino\Modules\Themelab::PERM ] );
$snapshot = namedSnapshot( $site );
$answers = [];

foreach( $names as $name )
	foreach( $builderActions + $labActions as $action => $data )
		$answers[$action. ' '. $name] = namedCall( $site, $action, $data + [ 'template' => $name ] )[0];

$loose = array_filter( $answers, static fn( int $status ): bool => in_array( $status, [ 400, 403, 404, 409 ], true ) === false );

check( 'every action of a frame - the four of the Builder and the fourteen of Themelab - refuses what is no frame of the site as it refuses a foreign template: a path, a capital, none, default, another side, a page, a word that is too long, and a name of the right form that has no file; nothing is written',
	$loose === [] && count( $answers ) === count( $names ) * ( count( $builderActions ) + count( $labActions ) ) && namedSnapshot( $site ) === $snapshot );

ninoDone( $site );
