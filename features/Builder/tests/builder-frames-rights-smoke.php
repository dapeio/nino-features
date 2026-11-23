<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-frames-rights-smoke.php	Contract test for the rights that go with the frames of the site in the panel of the pages: each right of the matrix is asked with real requests of real accounts, and where a
 *														request is refused nothing is written:
 *
 *														A. the matrix: six accounts - nobody, the content step, the right of Themelab alone, the door of the pages with no right of its own, the design, the design with the
 *														   right of Themelab - against every action of a frame: the list, open, preview, save, restore, make, rename, delete, the choice of a block and what it offers, keeping
 *														   a frame as a block, the texts, a picture, the notation. The files of the project - templates, texts, data of the Builder, blocks, pictures - are as they were after each
 *														   refusal
 *														B. the depth: a frame with the call of html and a text that is free is worked on round them by the design - the attribute of its wrap, a text of its content - and the
 *														   pieces stay as they were, to the byte; each try to change them, to put another in, to move them out of their container or to bring them back by the last state is refused,
 *														   and what a page lets be taken away - the call, the container above it - is taken away from a frame as well: the same requests, the same answers; the account that holds
 *														   the right of Themelab too writes them. The way back of a choice of a block is the unchanged frame for the account that chose; the editor's own way back is held to the step
 * *														C. the step is the account's: nothing a request brings - a parameter, the name of a template, a mark - gives a step, and the draft of a frame is held for what the account holds
 *
 *	Usage: php features/Builder/tests/builder-frames-rights-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-frames-rights-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Store;

function rightsCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

/** What a refusal may not touch, by name and hash: the templates, the texts, the data of the Builder, the own blocks and the pictures */
function rightsSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$path = substr( (string) $file, strlen( $dir ) );

		if( $file->isFile() === true && preg_match( '#/(templates|text|blocks|images)/|/data/builder/#', $path ) === 1 )
			$list[$path] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

/** A tree as the editor sends it: the texts that follow one another are one text, as the page makes them */
function rightsNormal( array $nodes ): array {

	$out = [];

	foreach( $nodes as $node ) {

		if( is_array( $node['c'] ?? null ) === true )
			$node['c'] = rightsNormal( $node['c'] );

		$last = $out === [] ? null : $out[count( $out ) - 1];

		if( isset( $node['t'] ) === true && isset( $node['n'] ) === false && isset( $node['f'] ) === false && $last !== null && isset( $last['t'] ) === true && isset( $last['n'] ) === false && isset( $last['f'] ) === false )
			$out[count( $out ) - 1] = [ 't' => $last['t']. $node['t'] ];
		else
			$out[] = $node;
	}

	return $out;
}

function rightsFile( array &$appData, string $template ): string {
	return (string) file_get_contents( ninoSandboxDir( $appData ). '/private/templates/'. $template. '.tpl' );
}

/** The upload of a small picture, as php hands it to a request */
function rightsUpload(): void {

	$image = imagecreatetruecolor( 64, 48 );
	imagefill( $image, 0, 0, (int) imagecolorallocate( $image, 200, 30, 30 ) );
	ob_start();
	imagepng( $image );
	$bytes = (string) ob_get_clean();
	$path = (string) tempnam( sys_get_temp_dir(), 'nino-up-' );
	file_put_contents( $path, $bytes );
	$_FILES['file'] = [ 'name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
}

$site = ninoSandbox( 'builder-frames-rights' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$head = static fn( string $id ): string => "[header-wrap id=\"". $id. "\" sticky]\n\t[header-logo]\n\t[header-actions]\n\t[/header-actions]\n[/header-wrap]\n";

foreach( [ 'frame-header' => $head( 'header' ), 'frame-footer' => "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n", 'frame-header-keep' => $head( 'keep' ), 'frame-header-door' => $head( 'door' ), 'frame-header-design' => $head( 'design' ), 'frame-header-lab' => $head( 'lab' ) ] as $name => $source )
	\Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/frame-header/header/title]]' => $locale === 'de_DE' ? 'Titel' : 'Title' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ], 'GET://deep' => [ 'uri' => '/deep', 'body' => '[template /templates/page-deep]' ] ];

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nA. The matrix\n";

$LAB = \Nino\Modules\Themelab::PERM;
$accounts = [
	'nobody'		=> [],
	'content'		=> [ '/_admin/builder/manage', '/_admin/builder/content/*' ],
	'lab'				=> [ $LAB ],
	'door'			=> [ '/_admin/builder/manage' ],
	'design'		=> [ '/_admin/builder/manage', '/_admin/builder/design/*' ],
	'designlab'	=> [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ],
];

// What each action is asked with. An account that may do it is answered 200 and the asking is real - the stamp of the frame as the account has just seen it, a word of its own
$actions = [
	'list'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-list', [] ],
	'open'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-open', [ 'template' => 'frame-header' ] ],
	'preview'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-preview', [ 'template' => 'frame-header', 'tree' => $tree, 'texts' => [], 'token' => '' ] ],
	'save'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $tree, 'texts' => [], 'base' => $stamp ] ],
	'restore'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-restore', [ 'template' => 'frame-header', 'base' => $stamp ] ],
	'make'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-create', [ 'side' => 'header', 'word' => 'neu-'. $who, 'name' => 'Neu '. $who, 'from' => 'frame-header' ] ],
	'rename'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-rename', [ 'template' => 'frame-header-'. ( $who === 'designlab' ? 'lab' : $who ), 'name' => 'Umbenannt '. $who ] ],
	'delete'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-remove', [ 'template' => 'frame-header-'. ( $who === 'designlab' ? 'lab' : $who ) ] ],
	'choices'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/framechoices', [ 'template' => 'frame-header-keep' ] ],
	'replace'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/framechoose', [ 'template' => 'frame-header-keep', 'key' => 'header-line', 'base' => $stamp ] ],
	'block'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'themelab/adopt', [ 'template' => 'frame-header', 'key' => 'block-'. $who, 'name' => [ 'de_DE' => 'Kopf '. $who, 'en_US' => 'Head '. $who ], 'description' => 'x' ] ],
	'texts'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frametexts', [ 'template' => 'frame-header' ] ],
	'write texts'	=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frametextssave', [ 'template' => 'frame-header', 'texts' => [], 'base' => $stamp ] ],
	'picture'			=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-upload', [ 'template' => 'frame-header', 'uri' => '/template/frame-header/header/picture', 'width' => 64, 'height' => 48 ] ],
	'offer'				=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-offer', [ 'template' => 'frame-header', 'tree' => $tree, 'at' => '0' ] ],
	'notation'		=> static fn( array &$s, string $who, string $stamp, array $tree ): array => [ 'builder/frame-notation', [ 'template' => 'frame-header', 'tree' => $tree, 'look' => [ 'block' => '0', 'node' => '0' ] ] ],
];

// What each account is answered: 200 where it may, 403 where it may not
$yes = array_fill_keys( array_keys( $actions ), 200 );
$no = array_fill_keys( array_keys( $actions ), 403 );
$expected = [
	'nobody'		=> $no,
	'content'		=> [ 'texts' => 200, 'write texts' => 200 ] + $no,
	'lab'				=> $no,
	'door'			=> [ 'block' => 403, 'notation' => 403 ] + $yes,
	'design'		=> [ 'block' => 403, 'notation' => 403 ] + $yes,
	'designlab'	=> $yes,
];
$answers = [];
$loose = [];
$touched = [];
$sawNothing = [];

// The frame the matrix works on: the standard header as the files have it - it is read once by the account that may, so that a refused account asks with a real tree and a real stamp
ninoUser( $site, 'reader@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $seen ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$tree = $seen['tree'];

foreach( $accounts as $who => $perms ) {

	ninoUser( $site, $who. '@example.com', $perms );
	[ , $own ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
	[ , $reader ] = [ 0, $own ];
	$stamp = (string) ( $own['stamp'] ?? '' );

	if( $stamp === '' ) {

		// An account that may not open a frame is asked with the stamp of the frame as the files have it now
		$state = Store::state( $site, 'frame-header' );
		$stamp = $state === null ? '' : Store::stamp( $state );
	}

	foreach( $actions as $name => $make ) {

		// The two actions that write the frame ask with what it is when they are asked: a change that is real - the wrap is not sticky any more, or is again
		$withTree = $tree;

		if( $name === 'save' || $name === 'preview' ) {

			$state = Store::state( $site, 'frame-header' );
			$stamp = $state === null ? $stamp : Store::stamp( $state );
			$withTree = \Nino\Modules\Builder\Tree::read( $site, (string) ( $state['source'] ?? '' ) );

			if( isset( $withTree[0]['a']['sticky'] ) === true )
				unset( $withTree[0]['a']['sticky'] );
			else
				$withTree[0]['a']['sticky'] = true;
		}
		else if( $name !== 'restore' && $name !== 'write texts' && $name !== 'replace' ) {

			$state = Store::state( $site, 'frame-header' );
			$stamp = $state === null ? $stamp : Store::stamp( $state );
		}

		if( $name === 'restore' || $name === 'write texts' ) {

			$state = Store::state( $site, 'frame-header' );
			$stamp = $state === null ? $stamp : Store::stamp( $state );
		}

		if( $name === 'replace' ) {

			$state = Store::state( $site, 'frame-header-keep' );
			$stamp = $state === null ? $stamp : Store::stamp( $state );
		}

		[ $action, $data ] = $make( $site, $who, $stamp, $withTree );
		$before = rightsSnapshot( $site );

		if( $name === 'picture' )
			rightsUpload();

		[ $status ] = rightsCall( $site, $action, $data );
		unset( $_FILES['file'] );
		$answers[$who][$name] = $status;

		if( $status === 403 && rightsSnapshot( $site ) !== $before )
			$touched[] = $who. ' '. $name;

		if( $status === 200 && in_array( $name, [ 'save', 'make', 'rename', 'delete', 'block' ], true ) === true && rightsSnapshot( $site ) === $before )
			$sawNothing[] = $who. ' '. $name;
	}
}

foreach( $expected as $who => $row )
	foreach( $row as $name => $status )
		if( ( $answers[$who][$name] ?? 0 ) !== $status )
			$loose[] = $who. ' '. $name. ' '. ( $answers[$who][$name] ?? 'none' ). '!='. $status;

check( 'each account is answered as the matrix says - nobody and the right of Themelab alone: 403 for each action; the content step: the texts of a frame and no more; the door of the pages with no right of its own and the design: every action but the keeping as a block and the notation; the design with the right of Themelab: every action',
	$loose === [] );

check( 'where a request of the matrix is refused nothing is written: the templates, the texts, the data of the Builder, the own blocks and the pictures are as they were after each of the 403 - and what is allowed has written what it does',
	$touched === [] && $sawNothing === [] );

[ $keepsStatus ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header-keep' ] );

check( 'the frames the accounts left are what the matrix says: the standard header is still a header, the frame of the right of Themelab alone and of nobody is there as it was, and each allowed account made its own',
	$keepsStatus === 200 && is_file( $sandbox. '/private/templates/frame-header-neu-door.tpl' ) === true && is_file( $sandbox. '/private/templates/frame-header-neu-designlab.tpl' ) === true && is_file( $sandbox. '/private/templates/frame-header-neu-nobody.tpl' ) === false
	&& is_file( $sandbox. '/private/templates/frame-header-neu-lab.tpl' ) === false && is_file( $sandbox. '/private/templates/frame-header-neu-content.tpl' ) === false && is_file( $sandbox. '/private/templates/frame-header-door.tpl' ) === false
	&& is_file( $sandbox. '/private/templates/frame-header-lab.tpl' ) === false && is_file( $sandbox. '/private/blocks/block-designlab/block.json' ) === true && is_file( $sandbox. '/private/blocks/block-design/block.json' ) === false );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nB. The depth\n";

$deep = "[header-wrap id=\"header\" sticky]\n\t[header-logo]\n\t[header-actions]\n\t\t[html]\n\t\t\t<p>Hi</p>\n\t\t[/html]\n\t[/header-actions]\n[/header-wrap]\n";
ninoUser( $site, 'dev@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ] );
[ , $frame ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$withHtml = $frame['tree'];
$at = 0;

foreach( $withHtml[0]['c'] as $index => $inner )
	if( ( $inner['n'] ?? '' ) === 'header-actions' )
		$at = $index;

$withHtml[0]['c'][$at]['c'] = [ [ 't' => "\n\t\t" ], [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => "\n\t\t\t<p>Hi</p>\n\t\t" ] ] ], [ 't' => "\n\t" ] ];
[ $wrote, $wroteAnswer ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $withHtml, 'texts' => [], 'base' => $frame['stamp'] ] );
$locked = rightsFile( $site, 'frame-header' );
$piece = static fn( string $source ): string => (string) preg_replace( '/^.*(\[html\].*?\[\/html\]).*$/s', '$1', $source );

check( 'the account that designs and holds the right of Themelab writes the call of html into a frame: the frame has it, and draws it',
	$wrote === 200 && str_contains( $locked, '[html]' ) === true && str_contains( \Nino\Html::renderSource( $site, $locked ), '<p>Hi</p>' ) === true );

// the design works round the piece: the attribute of the wrap goes and the piece is as it was, to the byte
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $plain ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$around = $plain['tree'];
unset( $around[0]['a']['sticky'] );
[ $round, $roundAnswer ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $around, 'texts' => [], 'base' => $plain['stamp'] ] );
$worked = rightsFile( $site, 'frame-header' );

check( 'the account that only designs works round a call of html in a frame - the attribute of the wrap is changed, 200 - and the piece is as it was, to the byte; the frame draws it as before',
	$round === 200 && str_contains( $worked, 'sticky' ) === false && $piece( $worked ) === $piece( $locked ) && $piece( $worked ) !== '' && str_contains( \Nino\Html::renderSource( $site, $worked ), '<p>Hi</p>' ) === true );

[ , $now ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$tries = [];
$mutate = static function( array $tree, callable $edit ): array { return rightsNormal( $edit( $tree ) ); };
$htmlAt = static function( array $tree ): array { foreach( $tree[0]['c'] as $i => $inner ) if( ( $inner['n'] ?? '' ) === 'header-actions' ) foreach( $inner['c'] as $j => $leaf ) if( ( $leaf['n'] ?? '' ) === 'html' ) return [ $i, $j ]; return [ 0, 0 ]; };
[ $hi, $hj ] = $htmlAt( $now['tree'] );

$tries['changed'] = $mutate( $now['tree'], static function( array $tree ) use ( $hi, $hj ): array { $tree[0]['c'][$hi]['c'][$hj]['c'] = [ [ 't' => '<script>x</script>' ] ]; return $tree; } );
$tries['a second one'] = $mutate( $now['tree'], static function( array $tree ) use ( $hi ): array { $tree[0]['c'][$hi]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<b>new</b>' ] ] ]; return $tree; } );
$tries['moved out'] = $mutate( $now['tree'], static function( array $tree ) use ( $hi, $hj ): array { $moved = $tree[0]['c'][$hi]['c'][$hj]; unset( $tree[0]['c'][$hi]['c'][$hj] ); $tree[0]['c'][] = $moved; return $tree; } );
$tries['free text'] = $mutate( $now['tree'], static function( array $tree ) use ( $hi ): array { $tree[0]['c'][$hi]['c'][] = [ 't' => '<p>free</p>' ]; return $tree; } );
$tries['free markup'] = $mutate( $now['tree'], static function( array $tree ) use ( $hi ): array { $tree[0]['c'][$hi]['c'][] = [ 'f' => '<?php echo 1; ?>' ]; return $tree; } );
$before = rightsSnapshot( $site );
$refusedTries = [];

foreach( $tries as $name => $tree )
	$refusedTries[$name] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $tree, 'texts' => [], 'base' => $now['stamp'] ] );

[ $previewStatus ] = rightsCall( $site, 'builder/frame-preview', [ 'template' => 'frame-header', 'tree' => $tries['a second one'], 'texts' => [], 'token' => '' ] );

check( 'a try to change the call of html, to put a second one in, to move it out of its place or to put free text in is refused - 403, builder_refused_html, builder_refused_unit or builder_refused_free - and one that is no tree is 400; the frame is as it was, to the byte, and the draft of such a tree is no draft',
	array_map( static fn( array $answer ): string => $answer[0]. ' '. ( $answer[1]['code'] ?? '' ), $refusedTries ) === [ 'changed' => '403 builder_refused_html', 'a second one' => '403 builder_refused_html', 'moved out' => '403 builder_refused_unit', 'free text' => '403 builder_refused_free', 'free markup' => '400 builder_tree' ]
	&& $previewStatus === 403 && rightsSnapshot( $site ) === $before && rightsFile( $site, 'frame-header' ) === $worked );

// One rule for a page and for a frame: the same tree, the same account, the same requests are answered with the same statuses and codes. What is taken away - the call of html, or the container
// above it - is taken, as it is from a page; what is put in, changed, copied or moved out of its container is refused as it is on a page
$pageSource = static fn( string $head ): string => "[frame /templates/frame-header]\n". $head. "[frame /templates/frame-footer]\n";
$deepHead = "[header-wrap id=\"deep\" sticky]\n\t[header-logo]\n\t[header-actions]\n\t\t[html]\n\t\t\t<p>Hi</p>\n\t\t[/html]\n\t[/header-actions]\n[/header-wrap]\n";
$sequence = [
	'text in the call'	=> static function( array $tree, array $at ): array { $tree[$at[0]]['c'][$at[1]]['c'][$at[2]]['c'] = [ [ 't' => '<script>x</script>' ] ]; return $tree; },
	'a second one'		=> static function( array $tree, array $at ): array { $tree[$at[0]]['c'][$at[1]]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<b>new</b>' ] ] ]; return $tree; },
	'moved out'			=> static function( array $tree, array $at ): array { $moved = $tree[$at[0]]['c'][$at[1]]['c'][$at[2]]; unset( $tree[$at[0]]['c'][$at[1]]['c'][$at[2]] ); $tree[$at[0]]['c'][] = $moved; return $tree; },
	'free text'			=> static function( array $tree, array $at ): array { $tree[$at[0]]['c'][$at[1]]['c'][] = [ 't' => '<p>free</p>' ]; return $tree; },
	'attribute round'	=> static function( array $tree, array $at ): array { unset( $tree[$at[0]]['a']['sticky'] ); return $tree; },
	'the call taken away'	=> static function( array $tree, array $at ): array { unset( $tree[$at[0]]['c'][$at[1]]['c'][$at[2]] ); $tree[$at[0]]['c'][$at[1]]['c'] = array_values( $tree[$at[0]]['c'][$at[1]]['c'] ); return $tree; },
	'the container taken away'	=> static function( array $tree, array $at ): array { unset( $tree[$at[0]]['c'][$at[1]] ); $tree[$at[0]]['c'] = array_values( $tree[$at[0]]['c'] ); return $tree; },
];
$same = [ 'page' => [], 'frame' => [] ];

foreach( [ 'page' => [ 'page-deep', 'builder/open', 'builder/save' ], 'frame' => [ 'frame-header-deep', 'builder/frame-open', 'builder/frame-save' ] ] as $kind => [ $template, $openAction, $saveAction ] ) {

	ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );

	foreach( $sequence as $step => $change ) {

		// each step starts from the tree as it was made
		\Nino\Filesystem::putFileContent( $site, '/templates/'. $template. '.tpl', $kind === 'page' ? $pageSource( $deepHead ) : $deepHead );
		[ , $opened ] = rightsCall( $site, $openAction, [ 'template' => $template ] );
		$tree = $opened['tree'];
		$wrap = 0;

		foreach( $tree as $index => $node )
			if( ( $node['n'] ?? '' ) === 'header-wrap' )
				$wrap = $index;

		$holder = 0;
		$callAt = 0;

		foreach( $tree[$wrap]['c'] as $index => $inner )
			if( ( $inner['n'] ?? '' ) === 'header-actions' ) {
				$holder = $index;

				foreach( $inner['c'] as $position => $leaf )
					if( ( $leaf['n'] ?? '' ) === 'html' )
						$callAt = $position;
			}

		[ $status, $body ] = rightsCall( $site, $saveAction, [ 'template' => $template, 'tree' => rightsNormal( $change( $tree, [ $wrap, $holder, $callAt ] ) ), 'texts' => $opened['texts'], 'base' => $opened['stamp'] ] );
		$same[$kind][$step] = $status. ' '. ( $body['code'] ?? '' ). ' '. ( $status === 200 ? ( str_contains( rightsFile( $site, $template ), '[html]' ) === true ? 'html' : 'no html' ) : '' );
	}
}

check( 'a page and a frame are held to one rule: the same changes of the same tree by the same account are answered with the same status and code - the text of the call, a second call, a call moved out of its container, free text: refused, as on a page - the attribute round the call, the call taken away and the container above it taken away: 200, and the call is gone, as from a page',
	$same['page'] === $same['frame'] && count( $same['page'] ) === count( $sequence ) && $same['page']['the call taken away'] === '200  no html' && $same['page']['the container taken away'] === '200  no html' && $same['page']['attribute round'] === '200  html'
	&& str_starts_with( $same['page']['text in the call'], '403 builder_refused_html' ) && str_starts_with( $same['page']['moved out'], '403 builder_refused_unit' ) && str_starts_with( $same['page']['free text'], '403 builder_refused_free' ) );

// the last state: the frame the account that develops left before the call of html is not brought back over it, and the call of html is not brought back over the frame that has none
ninoUser( $site, 'dev@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ] );
[ , $dev ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$without = $dev['tree'];
unset( $without[0]['c'][$hi]['c'][$hj] );
$without = rightsNormal( $without );
[ $devSaved ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $without, 'texts' => [], 'base' => $dev['stamp'] ] );
$bare = rightsFile( $site, 'frame-header' );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $bareOpen ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$before = rightsSnapshot( $site );
[ $bringBack, $bringBody ] = rightsCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header', 'base' => $bareOpen['stamp'] ] );

check( 'the last state that has the call of html is not brought back by the account that only designs - 403, builder_restore_develop - and the button says so beforehand: the answer of the open has the reason; the frame is as it was',
	$devSaved === 200 && str_contains( $bare, '[html]' ) === false && $bringBack === 403 && ( $bringBody['code'] ?? '' ) === 'builder_restore_develop' && ( $bareOpen['unback'] ?? '' ) === 'builder_restore_develop' && rightsSnapshot( $site ) === $before && rightsFile( $site, 'frame-header' ) === $bare );

// and the other way: the account that develops puts it in and the account that designs takes it away by the last state, as it may by a save
ninoUser( $site, 'dev@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ] );
[ , $dev ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
$again = $dev['tree'];
$again[0]['c'][$hi]['c'] = [ [ 't' => "\n\t\t" ], [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => "\n\t\t\t<p>Again</p>\n\t\t" ] ] ], [ 't' => "\n\t" ] ];
[ $devAgain ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $again, 'texts' => [], 'base' => $dev['stamp'] ] );
$withAgain = rightsFile( $site, 'frame-header' );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $againOpen ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
[ $takeAway ] = rightsCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header', 'base' => $againOpen['stamp'] ] );

check( 'the call of html is taken away by the last state as it is by a save, as it is from a page: the account that only designs is answered 200 and the frame has no call of html',
	$devAgain === 200 && str_contains( $withAgain, 'Again' ) === true && $takeAway === 200 && str_contains( rightsFile( $site, 'frame-header' ), '[html]' ) === false );

// the choice of a block for the whole frame is the design's, and so is taking it back: the frame is the one the choice wrote, and the last state is the frame as it was before - with the call of html
// that the development had put in, which the choice took away. The way back is the unchanged state: 200 and the file as it was, to the byte. The editor's own way back (a last state after a save) is
// held to what the account may write, and no request turns the one into the other
$withMark = static fn( string $source ): string => str_replace( "\t[/header-actions]", "\t\t[html]\n\t\t\t<p>Mark</p>\n\t\t[/html]\n\t[/header-actions]", $source );
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header-undo.tpl', $withMark( $head( 'undo' ) ) );
$undoPrepared = rightsFile( $site, 'frame-header-undo' );
$undoRows = static function( array &$site ): array { [ , $listing ] = rightsCall( $site, 'builder/frame-list' ); return array_column( $listing['frames'] ?? [], null, 'key' )['header-undo'] ?? []; };

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
$undoBefore = $undoRows( $site );
$undoPlain = $undoRows( $site );
ninoUser( $site, 'maker@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ] );
$undoDeveloper = $undoRows( $site );
ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ $chose ] = rightsCall( $site, 'builder/framechoose', [ 'template' => 'frame-header-undo', 'key' => 'header-line', 'base' => $undoBefore['stamp'] ?? '' ] );
$undoAfter = $undoRows( $site );
$chosenFile = rightsFile( $site, 'frame-header-undo' );
[ $editorBack, $editorBody ] = rightsCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header-undo', 'base' => $undoAfter['stamp'] ?? '', 'choice' => true, 'force' => true, 'level' => 3, 'develop' => true, 'as' => true ] );
$stillChosen = rightsFile( $site, 'frame-header-undo' );
[ $back, $backBody ] = rightsCall( $site, 'builder/framerestore', [ 'template' => 'frame-header-undo', 'base' => $undoAfter['stamp'] ?? '' ] );
$backFile = rightsFile( $site, 'frame-header-undo' );

check( 'the choice of a block takes the call of html out of the frame - 200, the list offers the way back - and the way back of the choice brings it back for the account that only designs: 200, the frame is as it was before the choice, to the byte, and the list offers no way back after it',
	str_contains( $undoPrepared, '[html]' ) === true && $chose === 200 && str_contains( $chosenFile, '[html]' ) === false && ( $undoAfter['undo'] ?? false ) === true && $back === 200 && $backFile === $undoPrepared && ( $undoRows( $site )['undo'] ?? true ) === false );
check( 'the list says of a frame that holds the call of html that a choice takes parts away from the account that only designs - and says it of no frame to the account that develops, nor of a frame that has none',
	( $undoBefore['locked'] ?? null ) === true && ( $undoDeveloper['locked'] ?? null ) === false && ( $undoAfter['locked'] ?? null ) === false );
check( 'the editor\'s own way back is held to what the account may write: after the same choice it is refused - 403, builder_restore_develop - whatever the request says - a flag, a step, a force -, and the frame is as the choice left it',
	$editorBack === 403 && ( $editorBody['code'] ?? '' ) === 'builder_restore_develop' && $stillChosen === $chosenFile );

// a choice that was made before the update: its last state was kept by the step of the development, whoever chose - the way back of the choice is the same
\Nino\Filesystem::putFileContent( $site, '/templates/frame-header-undo.tpl', $undoPrepared );
$before = Store::state( $site, 'frame-header-undo' );
[ $chosenAgain ] = rightsCall( $site, 'builder/framechoose', [ 'template' => 'frame-header-undo', 'key' => 'header-line', 'base' => Store::stamp( $before ) ] );
$kept = \Nino\Filesystem::getFileContent( $site, Store::PREVIOUS. '/frame-header-undo.php', [] );
$kept['level'] = \Nino\Modules\Builder::LEVEL_DEVELOP;
\Nino\Filesystem::putFileContent( $site, Store::PREVIOUS. '/frame-header-undo.php', $kept );
[ $oldBack ] = rightsCall( $site, 'builder/framerestore', [ 'template' => 'frame-header-undo', 'base' => Store::stamp( Store::state( $site, 'frame-header-undo' ) ) ] );

check( 'a choice whose last state was kept by the step of the development - how a choice made before this version left it - is taken back by the account that only designs as well: 200, and the frame is as it was',
	$chosenAgain === 200 && ( (int) $kept['level'] ) === 3 && $oldBack === 200 && rightsFile( $site, 'frame-header-undo' ) === $undoPrepared );

// the mark of a choice that was taken back is gone with the request: the editor's own way back after another choice is held to the step as ever - nothing of the first stays set
[ $chosenFourth ] = rightsCall( $site, 'builder/framechoose', [ 'template' => 'frame-header-undo', 'key' => 'header-line', 'base' => Store::stamp( Store::state( $site, 'frame-header-undo' ) ) ] );
[ $strictAfter, $strictBody ] = rightsCall( $site, 'builder/frame-restore', [ 'template' => 'frame-header-undo', 'base' => Store::stamp( Store::state( $site, 'frame-header-undo' ) ) ] );

check( 'the way back of a choice leaves nothing behind for the next request: the editor\'s own way back that follows it, in the same process, is refused as it is on its own - 403, builder_restore_develop',
	$chosenFourth === 200 && $strictAfter === 403 && ( $strictBody['code'] ?? '' ) === 'builder_restore_develop' );

// a frame that was changed since the choice is not brought back: the stamps say so - 409 - and nothing is written
$state = Store::state( $site, 'frame-header-undo' );
[ $chosenThird ] = rightsCall( $site, 'builder/framechoose', [ 'template' => 'frame-header-undo', 'key' => 'header-line', 'base' => Store::stamp( $state ) ] );
[ , $opened ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header-undo' ] );
$changed = $opened['tree'];
$changed[0]['a']['id'] = 'undo2';
[ $savedBetween ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header-undo', 'tree' => $changed, 'texts' => [], 'base' => $opened['stamp'] ] );
$before = rightsSnapshot( $site );
$offeredLate = ( $undoRows( $site )['undo'] ?? true );
[ $late ] = rightsCall( $site, 'builder/framerestore', [ 'template' => 'frame-header-undo', 'base' => Store::stamp( Store::state( $site, 'frame-header-undo' ) ) ] );

check( 'a frame that was changed after the choice is no frame the choice wrote: the list offers no way back and the way back is refused - 409 - and nothing is written',
	$chosenThird === 200 && $savedBetween === 200 && $offeredLate === false && $late === 409 && rightsSnapshot( $site ) === $before );

// ---------------------------------------------------------------------------------------------------------------------------------
echo "\nC. The step is the account's\n";

ninoUser( $site, 'designer@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $ours ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header', 'level' => 3, 'step' => 3, 'develop' => true, 'bridge' => true, 'as' => true, 'themelab' => true, 'perm' => '/_admin/themelab/manage' ] );
[ , $offered ] = rightsCall( $site, 'builder/frame-offer', [ 'template' => 'frame-header', 'tree' => $ours['tree'], 'at' => '0', 'level' => 3, 'develop' => true ] );
$markup = $ours['tree'];
$markup[0]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<i>x</i>' ] ] ];
$before = rightsSnapshot( $site );
[ $asked ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $markup, 'texts' => [], 'base' => $ours['stamp'], 'level' => 3, 'step' => 3, 'develop' => true, 'as' => true ] );
[ $named ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header-develop', 'tree' => $markup, 'texts' => [], 'base' => $ours['stamp'] ] );
[ $notationAsked ] = rightsCall( $site, 'builder/frame-notation', [ 'template' => 'frame-header', 'tree' => $ours['tree'], 'look' => [ 'block' => '0', 'node' => '0' ], 'level' => 3, 'develop' => true ] );

check( 'a request that names a step, a level, a mark, a right or a template of its own is given what the account holds: the design - the step it is answered with, the offer of the register without the call of html, the save and the notation refused - and nothing is written',
	( $ours['level'] ?? 0 ) === \Nino\Modules\Builder::LEVEL_DESIGN && in_array( 'html', array_column( $offered['offers'] ?? [], 'name' ), true ) === false && $asked === 403 && $named === 400 && $notationAsked === 403 && rightsSnapshot( $site ) === $before );

// the draft of a frame is held for what the account holds when it asks for it to be drawn: a token that was given to an account that designs is no draft for the one that no longer does
ninoUser( $site, 'drafter@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*' ] );
[ , $draft ] = rightsCall( $site, 'builder/frame-preview', [ 'template' => 'frame-header', 'tree' => $ours['tree'], 'texts' => [], 'token' => '' ] );
$drawn = static function( array &$site, string $token ): bool {

	$request = [ '/nino/http/request' => [ 'query' => [ \Nino\Preview::QUERY => $token ] ] ];
	\Nino\Modules\Builder::request( $site, $request );
	$active = \Nino\Preview::active( $site );
	unset( $site['./nino/preview/held'] );

	return $active;
};
$token = (string) ( $draft['token'] ?? '' );
$designing = $drawn( $site, $token );
$site['./nino/auth/current']['perms'] = [ '/_admin/builder/manage', '/_admin/builder/content/*' ];
$content = $drawn( $site, $token );
$site['./nino/auth/current']['perms'] = [ $LAB ];
$labOnly = $drawn( $site, $token );
$site['./nino/auth/current']['perms'] = [];
$nobody = $drawn( $site, $token );

check( 'the draft a frame made is drawn for the account that designs and for no other: the same session with the step of the content, with the right of Themelab alone or with nothing is given the page of the site',
	$token !== '' && $designing === true && $content === false && $labOnly === false && $nobody === false );

// the step of an account is read from the account alone, so that a request of the site - which has no class of the workbench - may ask for it: it is the reading the workbench has
$mismatch = [];

foreach( [ [], [ '/_admin/builder/manage' ], [ '/_admin/builder/manage', '/_admin/builder/content/edit' ], [ '/_admin/builder/manage', '/_admin/builder/design/edit' ], [ '/_admin/builder/manage', '/_admin/builder/design/*' ], [ '/_admin/builder/manage', '/_admin/builder/content/*', '/_admin/builder/design/edit' ],
	[ '/_admin/builder/*' ], [ '/_admin/*' ], [ '/*' ], [ '/_admin/builder/manage', '/_admin/builder/develop/edit' ], [ '/_admin/builder/manage', '/_admin/builder/other/thing' ], [ '/_admin/themelab/manage' ], [ '/_admin/builder/manage', '/_admin/builder/*' ] ] as $at => $perms ) {

	ninoUser( $site, 'step'. $at. '@example.com', $perms );
	$workbench = \Nino\Admin\Admin::scoped( $site, \Nino\Modules\Builder::SCOPE, \Nino\Modules\Builder::DESIGN ) === true || \Nino\Auth::checkPermission( $site, \Nino\Modules\Builder::DEVELOPED. 'edit' ) === true ? 2 : ( \Nino\Admin\Admin::scoped( $site, \Nino\Modules\Builder::SCOPE, \Nino\Modules\Builder::CONTENT ) === true ? 1 : 0 );

	if( \Nino\Modules\Builder::level( $site ) !== $workbench )
		$mismatch[] = json_encode( $perms );
}

check( 'the step an account has is the one the workbench reads - for no right, the door, the door with a step of the content, with a step of the design, with both, a blanket of the panel, of the workbench and of the site, the step of an older version, a permission of the panel that is no step, and the right of Themelab alone',
	$mismatch === [] );

// the right of a feature that is switched off gives nothing: the account that designs and holds the right of Themelab is a designer while Themelab is off - the step of the design, no call of html, no
// notation, the list that says it is not the development - and the account that holds the right of Themelab alone is as little a designer
ninoUser( $site, 'both@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ] );
$onBridge = \Nino\Modules\Builder::bridge( $site );
$onLevel = \Nino\Modules\Builder::frameLevel( $site );
$modules = $site['/nino/modules'];
$site['/nino/modules'] = array_values( array_filter( $modules, static fn( string $class ): bool => $class !== '\\Nino\\Modules\\Themelab' ) );
unset( $site['./nino/features/all'] );
[ , $offOpen ] = rightsCall( $site, 'builder/frame-open', [ 'template' => 'frame-header' ] );
[ , $offList ] = rightsCall( $site, 'builder/frame-list' );
$withHtmlTree = $offOpen['tree'];
$withHtmlTree[0]['c'][] = [ 'n' => 'html', 's' => '', 'a' => [], 'c' => [ [ 't' => '<i>x</i>' ] ] ];
$before = rightsSnapshot( $site );
[ $offSave, $offBody ] = rightsCall( $site, 'builder/frame-save', [ 'template' => 'frame-header', 'tree' => $withHtmlTree, 'texts' => [], 'base' => $offOpen['stamp'] ?? '' ] );
[ $offNotation ] = rightsCall( $site, 'builder/frame-notation', [ 'template' => 'frame-header', 'tree' => $offOpen['tree'], 'look' => [ 'block' => '0', 'node' => '0' ] ] );
$offBridge = \Nino\Modules\Builder::bridge( $site );
$offLevel = \Nino\Modules\Builder::frameLevel( $site );
$site['/nino/modules'] = $modules;
unset( $site['./nino/features/all'] );

check( 'the right of Themelab gives nothing while Themelab is off: the bridge is shut, the step of a frame is the design - the call of html is refused, 403, the notation is refused, 403, and the list says it is not the development - where it was the development while Themelab is on',
	$onBridge === true && $onLevel === \Nino\Modules\Builder::LEVEL_DEVELOP && $offBridge === false && $offLevel === \Nino\Modules\Builder::LEVEL_DESIGN && ( $offOpen['level'] ?? 0 ) === \Nino\Modules\Builder::LEVEL_DESIGN
	&& ( $offList['develop'] ?? true ) === false && $offSave === 403 && ( $offBody['code'] ?? '' ) === 'builder_refused_html' && $offNotation === 403 && rightsSnapshot( $site ) === $before );

ninoDone( $site );
