<?php
declare(strict_types=1);

/**
 *	Nino features
 *	blocks-places-smoke.php	Contract test for what a block of the library holds of content: places with an id, and the way a place becomes the page's
 *
 *															A. a block with a title, a text, a button, a picture and a loop is saved - its places have the ids the person gave - and put into a page: the keys of
 *															   the page are made from the page, the id of the block and the id of the place, the same for each block put in, and the sample is the first text
 *															B. what the request may not bring: an id with foreign characters, an id twice, a picture of a size the kernel does not take, markup in a sample
 *															C. the loops: the dialog of the components offers them as loops, and a block that holds one is saved and put in
 *
 *	Usage: php features/Blocks/tests/blocks-places-smoke.php
 *				 NINO_ROOT=../nino php features/Blocks/tests/blocks-places-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Builder\Tree;

/** One action of the API, as the workbench posts it: [ status, body ] */
function placesCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );

	return [ $status, json_decode( (string) json_encode( $body ), true ) ];
}

/** The files a block of the project is, for "nothing is written" */
function placesSnapshot( array &$appData ): array {

	$dir = ninoSandboxDir( $appData );
	$list = [];

	foreach( [ 'private/blocks', 'private/templates', 'private/text' ] as $sub ) {

		if( is_dir( $dir. '/'. $sub ) === false )
			continue;

		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir. '/'. $sub, FilesystemIterator::SKIP_DOTS ) ) as $file )
			if( $file->isFile() === true )
				$list[substr( (string) $file, strlen( $dir ) )] = hash_file( 'sha256', (string) $file );
	}

	return $list;
}

/** The position of the first call a test says yes to, depth first, or null */
function placesFind( array $nodes, callable $test, string $prefix = '' ): ?string {

	foreach( $nodes as $at => $node ) {

		if( isset( $node['n'] ) === false )
			continue;

		if( $test( $node ) === true )
			return $prefix. $at;

		if( is_array( $node['c'] ?? null ) === true && ( $found = placesFind( $node['c'], $test, $prefix. $at. '.' ) ) !== null )
			return $found;
	}

	return null;
}

/** The call at a place of a tree changed by a function */
function placesEdit( array $nodes, string $position, callable $change ): array {

	$parts = explode( '.', $position );
	$index = (int) array_shift( $parts );

	if( $parts === [] )
		$nodes[$index] = $change( $nodes[$index] );
	else
		$nodes[$index]['c'] = placesEdit( $nodes[$index]['c'], implode( '.', $parts ), $change );

	return $nodes;
}

// ---------------------------------------------------------------------------------------------------------------------------------
$site = ninoSandbox( 'blocks-places' );
$sandbox = ninoSandboxDir( $site );

$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Blocks' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );

foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/page-home/hero/title]]' => 'Willkommen' ] );

$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ], '/nino/http/routes' => $site['/nino/http/routes'] ] );
\Nino\Elements::insertElementType( $site, '/services', [ 'title' => [ 'type' => 'string' ] ] );
ninoUser( $site, 'maker@example.com', [ \Nino\Modules\Blocks::PERM, '/_admin/builder/manage', '/_admin/builder/design/*' ] );

echo "\nA. From a block with places to a page\n";

[ $made, $one ] = placesCall( $site, 'blocks/create', [ 'name' => 'Platz Block', 'category' => 'content', 'from' => '' ] );
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $one['key'] ?? 'platz-block' ] );
$key = $one['key'] ?? 'platz-block';
$id = (string) ( $draft['tree'][array_key_first( array_filter( $draft['tree'], static fn( array $node ): bool => isset( $node['n'] ) === true && $node['n'] === 'section-wrap' ) )]['a']['id'] ?? '' );
$col = placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ) ?? '';
$call = static fn( string $name, string $source, array $attributes = [] ): array => [ 'n' => $name, 's' => $source, 'a' => $attributes, 'c' => null ];
$tree = placesEdit( $draft['tree'], $col, static function( array $node ) use ( $call ): array {

	$node['c'] = [ [ 't' => "\n" ], $call( 'section-title', '~titel' ), [ 't' => "\n" ], $call( 'text', '~absatz' ), [ 't' => "\n" ], $call( 'button', '~knopf', [ 'href' => 'page:/home' ] ), [ 't' => "\n" ], $call( 'image', '~bild' ), [ 't' => "\n" ] ];

	return $node;
} );
$base = '/template/'. $draft['template']. '/'. $id. '/';
$sample = [ 'titel' => 'Lorem ipsum dolor', 'absatz' => 'Sit amet consectetur adipiscing elit sed do eiusmod tempor.', 'knopf' => 'Dolor sit' ];
$texts = [];

foreach( $sample as $name => $text )
	$texts[$base. $name] = [ 'de_DE' => $text, 'en_US' => $text ];

[ $saved, $answer ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $tree, 'texts' => $texts, 'pictures' => [ $base. 'bild' => [ 'label' => 'Bild', 'width' => 800, 'height' => 600 ] ], 'base' => $draft['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$kept = \Nino\Blocks::get( $site, $key );

check( 'a block with a title, a text, a button and a picture is saved with the ids the person gave its places - titel, absatz, knopf, bild - and the sample of each place, the same words in both languages, is in the files of the block',
	$saved === 200 && is_array( $kept ) === true && str_contains( (string) $kept['source'], '[section-title ~titel]' ) === true && str_contains( (string) $kept['source'], '[text ~absatz]' ) === true && str_contains( (string) $kept['source'], '~bild' ) === true
	&& ( $kept['texts']['de_DE'][$base. 'titel'] ?? $kept['texts']['de_DE']['titel'] ?? '' ) === 'Lorem ipsum dolor' && ( $kept['texts']['en_US'][$base. 'titel'] ?? $kept['texts']['en_US']['titel'] ?? '' ) === 'Lorem ipsum dolor' );

// the block is put into a page: the keys of the page are made from the page, the id of the block and the id of the place - and that is stable and the same for each block put in
[ , $page ] = placesCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$registry = \Nino\Components::components( $site );
$put = static function( int $count ) use ( &$site, $key, $registry ): array {

	[ , $block ] = placesCall( $site, 'builder/block', [ 'key' => $key ] );
	[ , $page ] = placesCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
	$taken = Tree::taken( $page['tree'], $registry, array_merge( array_keys( $page['texts'] ), $page['slots'] ?? [] ) );
	$base = (string) preg_replace( '/-\d+$/', '', $block['id'] );
	$now = $block['id'];

	for( $number = 2; in_array( $now, $taken, true ) === true; $number++ )
		$now = $base. '-'. $number;

	$walk = static function( array $nodes ) use ( &$walk, $registry, $block, $now ): array {

		foreach( $nodes as $index => $node ) {

			$name = isset( $node['n'] ) === true && isset( $registry[$node['n']] ) === true ? Tree::idAttribute( $registry[$node['n']] ) : '';

			if( $name !== '' && isset( $node['a'][$name] ) === true )
				$nodes[$index]['a'][$name] = $node['a'][$name] === $block['id'] ? $now : Tree::inner( $node['a'][$name], $block['id'], $now );

			if( is_array( $node['c'] ?? null ) === true )
				$nodes[$index]['c'] = $walk( $node['c'] );
		}

		return $nodes;
	};

	$at = (int) array_key_last( array_filter( $page['tree'], static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'template' ) );
	$tree = $page['tree'];
	array_splice( $tree, $at, 0, [ $walk( $block['tree'] )[0], [ 't' => "\n" ] ] );
	$words = [];

	foreach( $block['slots'] as $slot )
		if( $slot['kind'] === 'text' ) {

			$was = $slot['scope'] === '' ? $block['id'] : $block['id']. '-'. $slot['scope'];
			$scope = $was === $block['id'] ? $now : Tree::inner( $was, $block['id'], $now );

			foreach( $block['texts'] as $locale => $set )
				$words['/template/page-home/'. $scope. '/'. $slot['word']][$locale] = $set[$slot['name']];
		}

	[ $status, $body ] = placesCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $tree, 'texts' => $words, 'inserted' => [ $now => $key ], 'base' => $page['stamp'] ] );

	return [ $status, $now, $words, $body ];
};

[ $first, $firstId, $firstWords ] = $put( 1 );
[ $second, $secondId, $secondWords ] = $put( 2 );
$pageSource = (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' );
unset( $site['./nino/text/values'] );

check( 'a block that is put into a page gives its places the keys /template/<page>/<id of the block>/<id of the place> - the id of the block where the page has none, the id with the next number behind it for the next - each with the sample as its first text; the call reads the place by its id',
	$first === 200 && $second === 200 && $firstId === $id && $secondId === $id. '-2'
	&& array_keys( $firstWords ) === [ '/template/page-home/'. $id. '/titel', '/template/page-home/'. $id. '/absatz', '/template/page-home/'. $id. '/knopf' ]
	&& array_keys( $secondWords ) === [ '/template/page-home/'. $id. '-2/titel', '/template/page-home/'. $id. '-2/absatz', '/template/page-home/'. $id. '-2/knopf' ]
	&& ( $firstWords['/template/page-home/'. $id. '/titel']['de_DE'] ?? '' ) === 'Lorem ipsum dolor' && ( $secondWords['/template/page-home/'. $id. '-2/absatz']['en_US'] ?? '' ) === $sample['absatz']
	&& substr_count( $pageSource, '[section-title ~titel]' ) === 2 && str_contains( $pageSource, 'id="'. $id. '-2"' ) === true );

echo "\nB. What a request may not bring\n";

[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$before = placesSnapshot( $site );
$tries = [
	'an id with a capital' => [ placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-title' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~Titel' ] ) ), $draft['texts'] ],
	'an id with a script' => [ placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-title' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~a"><script>' ] ) ), $draft['texts'] ],
];
$sizes = static function( int $width, int $height ) use ( $draft ): array {

	$details = $draft['details'];
	$details['images']['bild']['width'] = $width;
	$details['images']['bild']['height'] = $height;

	return $details;
};
$answers = [];

foreach( $tries as $label => [ $edited, $words ] ) {

	[ $status ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $edited, 'texts' => $words, 'base' => $draft['stamp'], 'force' => true ] );
	$answers[$label] = $status;
}

foreach( [ 'a picture beyond what the kernel takes' => $sizes( 99999, 600 ), 'a picture that is no size' => $sizes( 0, 0 ) ] as $label => $details ) {

	[ $status ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'details' => $details, 'base' => $draft['stamp'], 'force' => true ] );
	$answers[$label] = $status;
}

check( 'an id with foreign characters and a picture of a size the kernel does not take are refused - 400 or 403 - and nothing is written',
	array_filter( $answers, static fn( int $status ): bool => $status !== 400 && $status !== 403 ) === [] && placesSnapshot( $site ) === $before );

// two places with one id are one place: the second call reads the words of the first, and the block says so as a draft it cannot keep
$twice = placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'text' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~titel' ] ) );
$beforeTwice = placesSnapshot( $site );
[ $reading, $readingBody ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $twice, 'texts' => $draft['texts'], 'base' => $draft['stamp'], 'force' => true ] );
$readingCode = (string) ( $readingBody['code'] ?? '' );
$readingParams = (array) ( $readingBody['params'] ?? [] );
unset( $site['./nino/blocks/state'] );

check( 'two components that read one place of the block are refused with a sentence of their own - 400, and nothing is written - and the id of the place is in it',
	$reading === 400 && $readingCode === 'blocks_place_twice' && ( $readingParams[0] ?? '' ) === 'titel' && placesSnapshot( $site ) === $beforeTwice );

// markup in the sample of a place is text: no tag is kept, the words of the block are the words
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$dirty = $draft['texts'];
$dirty[$base. 'titel'] = [ 'de_DE' => 'Lorem <script>alert(1)</script> ipsum', 'en_US' => 'Lorem <b>ipsum</b>' ];
[ $markup ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $dirty, 'base' => $draft['stamp'] ] );
unset( $site['./nino/blocks/state'] );
$words = \Nino\Blocks::get( $site, $key )['texts'] ?? [];
$found = json_encode( $words );

check( 'markup in the sample of a place is no markup in the files of the block: a tag is not kept - a request that carries one is refused or its words are cleaned - and a script never stands in them',
	( $markup === 200 && str_contains( (string) $found, '<script' ) === false && str_contains( (string) $found, '<b>' ) === false ) || $markup === 400 || $markup === 403 );

echo "\nB2. A block that is saved as it is\n";

// a block.json that a hand or an older version wrote - the languages the other way round, an empty set as a list - is the same to the byte after a save that changes nothing
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$file = $sandbox. '/private/blocks/'. $key. '/block.json';
$meta = json_decode( (string) file_get_contents( $file ), true );
$meta['name'] = array_reverse( $meta['name'] );
$meta['description'] = array_reverse( (array) $meta['description'] );
$meta['variants'] = [ 'standard' => [ 'label' => [ 'en_US' => 'Standard', 'de_DE' => 'Standard' ], 'set' => [] ], 'quiet' => [ 'label' => [ 'en_US' => 'Quiet', 'de_DE' => 'Ruhig' ], 'set' => [ 'root' => [ 'ground' => 'alt' ] ] ] ];
file_put_contents( $file, json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n" );
unset( $site['./nino/blocks/state'] );
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$was = (string) file_get_contents( $file );
$variants = array_map( static fn( array $variant ): array => [ 'name' => $variant['name'], 'label' => $variant['label'], 'set' => $variant['set'] ], $draft['alternatives'] ?? [] );
[ $unchanged ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'variants' => $variants, 'details' => $draft['details'], 'base' => $draft['stamp'] ] );

check( 'a block that is saved without a change keeps its block.json to the byte - the file of an older version, with the languages the other way round and an empty set as a list, is not made over - and a change is written',
	$unchanged === 200 && count( $variants ) === 2 && hash( 'sha256', (string) file_get_contents( $file ) ) === hash( 'sha256', $was ) );

[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$details = $draft['details'];
$details['rank'] = 7;
[ $changed ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'variants' => $variants, 'details' => $details, 'base' => $draft['stamp'] ] );

check( 'and a change of the block is written: the rank is the new one', $changed === 200 && ( json_decode( (string) file_get_contents( $file ), true )['rank'] ?? 0 ) === 7 );

echo "\nB3. The picture of a place\n";
$key = $one['key'] ?? 'platz-block';

// The kernel writes a picture anew with gd: a php without it leaves this part out and says so
if( function_exists( 'imagecreatetruecolor' ) === false )
	echo "  note  - gd is not there: the part about the picture of a place is left out\n";
else {


// a picture for a place: looked at, written anew by the kernel and kept apart for this session and this block until the block is saved; a svg is cleaned, any other kind is refused at once
$pngBytes = (string) base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );
$gifBytes = (string) base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
$uploadAs = static function( array &$appData, string $bytes, string $name, string $block = 'platz-block' ) use ( $sandbox ): array {

	$path = $sandbox. '/upload-'. bin2hex( random_bytes( 4 ) );
	file_put_contents( $path, $bytes );
	$_FILES['file'] = [ 'name' => $name, 'type' => 'application/octet-stream', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
	$answer = placesCall( $appData, 'blocks/picture', [ 'key' => $block, 'name' => 'bild' ] );
	unset( $_FILES['file'] );

	return $answer;
};
$staging = $sandbox. '/private/data/.preview/blocks-picture/';
$ownFiles = static fn( string $block ): array => array_map( 'basename', (array) glob( $sandbox. '/private/blocks/'. $block. '/images/bild.*' ) );
[ $sent, $staged ] = $uploadAs( $site, $pngBytes. '<?php echo 1; ?>', 'a.png', $key );
[ $hostile, $hostileStaged ] = $uploadAs( $site, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script><rect width="9" height="9"/></svg>', 'b.svg', $key );
[ $nothing ] = $uploadAs( $site, 'this is no picture', 'c.txt', $key );
[ $gif, $gifBody ] = $uploadAs( $site, $gifBytes, 'd.gif', $key );
[ $empty, $emptyBody ] = $uploadAs( $site, '', 'e.png', $key );
[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );

check( 'a picture for a place is looked at and written anew - a png with a script appended to it is kept without it -, a svg with a script comes out cleaned, and a file that is no picture, a gif and an empty file are refused with a sentence of their own at once; nothing of the block is written',
	$sent === 200 && in_array( $staged['type'] ?? '', [ 'png', 'webp', 'jpg' ], true ) === true && str_contains( (string) file_get_contents( $staging. $staged['file'] ), '<?php' ) === false && $hostile === 200 && ( $hostileStaged['type'] ?? '' ) === 'svg'
	&& $nothing >= 400 && $gif === 400 && ( $gifBody['code'] ?? '' ) === 'blocks_picture_type' && $empty === 400 && ( $emptyBody['code'] ?? '' ) === 'blocks_picture_empty' && $ownFiles( $key ) === []
	&& str_contains( (string) file_get_contents( $staging. $hostileStaged['file'] ), 'script' ) === false );

// the file is the session's and the block's: another block, or another session, puts nothing of it in
[ , $other ] = placesCall( $site, 'blocks/create', [ 'name' => 'Anderer Block', 'category' => 'content', 'from' => '' ] );
[ , $otherDraft ] = placesCall( $site, 'blocks/open', [ 'key' => $other['key'] ] );
$otherBase = '/template/'. $otherDraft['template']. '/'. (string) ( $otherDraft['tree'][array_key_first( array_filter( $otherDraft['tree'], static fn( array $node ): bool => ( $node['n'] ?? '' ) === 'section-wrap' ) )]['a']['id'] ?? '' ). '/';
$otherCol = placesFind( $otherDraft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ) ?? '';
$otherTree = placesEdit( $otherDraft['tree'], $otherCol, static function( array $node ): array { $node['c'] = [ [ 'n' => 'image', 's' => '~bild', 'a' => [], 'c' => null ] ]; return $node; } );
$beforeOther = placesSnapshot( $site );
[ $elsewhere ] = placesCall( $site, 'blocks/save', [ 'key' => $other['key'], 'tree' => $otherTree, 'texts' => $otherDraft['texts'], 'pictures' => [ $otherBase. 'bild' => [ 'label' => 'Bild', 'width' => 800, 'height' => 600 ] ], 'own' => [ 'bild' => $staged['file'] ], 'base' => $otherDraft['stamp'] ] );
$sessionWas = $_SESSION;
$_SESSION = [];
[ $strange ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'own' => [ 'bild' => $staged['file'] ], 'base' => $draft['stamp'] ] );
$_SESSION = $sessionWas;

check( 'a picture that was put aside for one block is put in no other block, and a session that did not put it aside puts it in none: 403, and nothing is written',
	$elsewhere === 403 && $strange === 403 && placesSnapshot( $site ) === $beforeOther && is_file( $staging. $staged['file'] ) === true );

// a save that fails keeps the file for the next save
$beforeFailing = placesSnapshot( $site );
$broken = placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-title' ), static fn( array $node ): array => array_replace( $node, [ 's' => '~Titel' ] ) );
[ $failed ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $broken, 'texts' => $draft['texts'], 'own' => [ 'bild' => $staged['file'] ], 'base' => $draft['stamp'], 'force' => true ] );

check( 'a save that fails - 400 or 403 - keeps the picture that was put aside: it is there for the next save, and nothing is written',
	in_array( $failed, [ 400, 403 ], true ) && is_file( $staging. $staged['file'] ) === true && placesSnapshot( $site ) === $beforeFailing );

// and so does one that fails when the kernel looks at the picture - the file is there, a bad picture the kernel refuses, and the one that is good takes its place
$token = str_repeat( 'c', 32 ). '.png';
file_put_contents( $staging. $token, 'this is no png' );
$_SESSION['nino_blocks_pictures'][$token] = [ 'key' => $key, 'until' => time() + 3600 ];
$beforeBad = placesSnapshot( $site );
[ $bad ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'own' => [ 'bild' => $token ], 'base' => $draft['stamp'], 'force' => true ] );

check( 'a save that the kernel refuses for the picture - 400 - keeps the file that was put aside for the next save, and nothing is written',
	$bad === 400 && is_file( $staging. $token ) === true && placesSnapshot( $site ) === $beforeBad );

@unlink( $staging. $token );
unset( $_SESSION['nino_blocks_pictures'][$token] );

[ $made ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $draft['tree'], 'texts' => $draft['texts'], 'own' => [ 'bild' => $staged['file'] ], 'base' => $draft['stamp'] ] );
[ , $after ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$kind = (string) substr( (string) $staged['file'], 33 );
$pictureFile = $sandbox. '/private/blocks/'. $key. '/images/bild.'. $kind;

check( 'the save makes the picture the own picture of the place - the file, which is the kernel\'s writing of it and holds nothing that was appended, stands in the folder of the block, the draft says which places have one, and the file that was put aside is gone',
	$made === 200 && is_file( $pictureFile ) === true && str_contains( (string) file_get_contents( $pictureFile ), '<?php' ) === false && ( (array) ( $after['own'] ?? [] ) ) === [ 'bild' => $kind ] && is_file( $staging. $staged['file'] ) === false );

$refusals = [];

foreach( [ 'a place that is none' => [ [ 'nope' => $hostileStaged['file'] ], 'blocks_picture_own' ], 'a name that is no name' => [ [ '../x' => $hostileStaged['file'] ], 'blocks_picture_name' ], 'a name that is a path to a picture' => [ [ '../../../blocks/'. $key. '/images/bild' => '' ], 'blocks_picture_name' ], 'a token that is a path' => [ [ 'bild' => '../../x.png' ], 'blocks_picture_own' ], 'a token that is gone' => [ [ 'bild' => str_repeat( 'a', 32 ). '.png' ], 'blocks_picture_own' ] ] as $label => [ $own, $code ] ) {

	$before = placesSnapshot( $site );
	[ $status, $body ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $after['tree'], 'texts' => $after['texts'], 'own' => $own, 'base' => $after['stamp'], 'force' => true ] );
	$refusals[$label] = in_array( $status, [ 400, 403 ], true ) && ( $body['code'] ?? '' ) === $code && placesSnapshot( $site ) === $before;
}

check( 'a place that is none, a name that is no name - never a path -, a token that is a path and one that is gone are refused with the sentence for each - and nothing is written',
	array_filter( $refusals, static fn( bool $ok ): bool => $ok === false ) === [] );

[ $gone ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $after['tree'], 'texts' => $after['texts'], 'own' => [ 'bild' => '' ], 'base' => $after['stamp'], 'force' => true ] );
[ , $last ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );

check( 'a place that is told "none" has no picture of its own any more: the file is gone and the draft says so', $gone === 200 && $ownFiles( $key ) === [] && ( (array) ( $last['own'] ?? [ 'x' => 1 ] ) ) === [] );

// what was put aside and is not wanted goes: the draft that is given up lets go of it - of this block, of this session - and whatever is old goes at the next action of the panel, whichever it is
[ , $again ] = $uploadAs( $site, $pngBytes, 'f.png', $key );
[ $dropElsewhere ] = placesCall( $site, 'blocks/picture-drop', [ 'key' => $other['key'], 'files' => [ $again['file'] ] ] );
$keptElsewhere = is_file( $staging. $again['file'] ) === true;
[ $dropped, $droppedBody ] = placesCall( $site, 'blocks/picture-drop', [ 'key' => $key, 'files' => [ $again['file'], '../x', 7 ] ] );
$old = $staging. str_repeat( 'b', 32 ). '.png';
file_put_contents( $old, 'old' );
touch( $old, time() - 4 * 3600 );
placesCall( $site, 'blocks/list' );

[ , $shown ] = $uploadAs( $site, $pngBytes, 'g.png', $key );
[ $show, $showBody ] = placesCall( $site, 'blocks/picture-show', [ 'key' => $key, 'file' => $shown['file'] ] );
[ $showElsewhere ] = placesCall( $site, 'blocks/picture-show', [ 'key' => $other['key'], 'file' => $shown['file'] ] );
[ $showNone ] = placesCall( $site, 'blocks/picture-show', [ 'key' => $key, 'file' => '../x' ] );
[ $showGone ] = placesCall( $site, 'blocks/picture-show', [ 'key' => $key, 'file' => str_repeat( 'c', 32 ). '.png' ] );
placesCall( $site, 'blocks/picture-drop', [ 'key' => $key, 'files' => [ $shown['file'] ] ] );

check( 'the picture that was put aside is shown to the preview of the editor as an address of data - for the session and the block it was for only: another block 403, a name that is none 400, a file nobody put aside 403 - and the site has no address for it',
	$show === 200 && str_starts_with( (string) ( $showBody['url'] ?? '' ), 'data:image/' ) === true && base64_decode( substr( (string) $showBody['url'], strpos( (string) $showBody['url'], ',' ) + 1 ) ) !== '' && $showElsewhere === 403 && $showNone === 400 && $showGone === 403 && is_dir( \Nino\Filesystem::path( $site, '/images/blocks-picture' ) ) === false );

check( 'a picture that was put aside and is not wanted is let go of by the draft that is given up - only by the block and the session it was for - and a file that is old goes at the next action of the panel, whichever it is',
	$dropElsewhere === 200 && $keptElsewhere === true && $dropped === 200 && ( $droppedBody['dropped'] ?? 0 ) === 1 && is_file( $staging. $again['file'] ) === false && is_file( $old ) === false );

}

echo "\nC. The loops\n";

[ $offered, $offer ] = placesCall( $site, 'blocks/offer', [ 'tree' => $draft['tree'], 'at' => (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ) ] );
$loops = array_values( array_filter( $offer['offers'] ?? [], static fn( array $entry ): bool => ( $offer['schemas'][$entry['name']]['loop'] ?? false ) === true ) );

check( 'the dialog of the components offers the loops - the lists, the tables, the collection - and every one of them stands in the part of the dialog that is the loops, whatever group the register gives it',
	$offered === 200 && count( $loops ) > 0 && array_unique( array_column( $loops, 'section' ) ) === [ 'loops' ] && in_array( 'collection-list', array_column( $loops, 'name' ), true ) === true );

// the filter holds loops and is none: it is the last of the loops - and the two columns the register has, of a section and of a table, are told apart by what they are a part of
$inLoops = array_values( array_filter( $offer['offers'] ?? [], static fn( array $entry ): bool => $entry['section'] === 'loops' ) );
$columns = array_filter( array_column( $offer['offers'] ?? [], 'label', 'name' ), static fn( mixed $label, string $name ): bool => in_array( $name, [ 'section-col', 'collection-col' ], true ), ARRAY_FILTER_USE_BOTH );

check( 'the filter is a tile of the loops and the last of them, and the column of a table is not named like the column of a section: it says "(Table)" - the dialog never has two tiles of one name',
	end( $inLoops )['name'] === 'collection-filter' && count( $columns ) === 2 && ( $columns['section-col']['en_US'] ?? '' ) !== ( $columns['collection-col']['en_US'] ?? '' ) && str_ends_with( (string) ( $columns['collection-col']['en_US'] ?? '' ), '(Table)' ) === true
	&& count( array_unique( array_map( static fn( array $entry ): string => json_encode( $entry['label'] ), $offer['offers'] ) ) ) === count( $offer['offers'] ) );

[ , $draft ] = placesCall( $site, 'blocks/open', [ 'key' => $key ] );
$loopTree = placesEdit( $draft['tree'], (string) placesFind( $draft['tree'], static fn( array $node ): bool => $node['n'] === 'section-col' ), static function( array $node ): array {

	$node['c'][] = [ 'n' => 'collection-list', 's' => '/_placeholder', 'a' => [], 'c' => [ [ 't' => "\n" ], [ 'n' => 'text', 's' => '.title', 'a' => [], 'c' => null ], [ 't' => "\n" ] ] ];
	$node['c'][] = [ 't' => "\n" ];
	$node['c'][] = [ 'n' => 'collection-table', 's' => '/_placeholder', 'a' => [], 'c' => [ [ 'n' => 'collection-col', 's' => '.title', 'a' => [ 'title' => '~heading-title', 'rowhead' => true ], 'c' => null ], [ 'n' => 'collection-col', 's' => '.text', 'a' => [ 'title' => '~heading-text' ], 'c' => null ] ] ];
	$node['c'][] = [ 't' => "\n" ];

	return $node;
} );
[ $loopSaved ] = placesCall( $site, 'blocks/save', [ 'key' => $key, 'tree' => $loopTree, 'texts' => $draft['texts'] + [ $base. 'heading-title' => [ 'de_DE' => 'Titel', 'en_US' => 'Titel' ], $base. 'heading-text' => [ 'de_DE' => 'Text', 'en_US' => 'Text' ] ], 'pictures' => [], 'base' => $draft['stamp'] ] );
unset( $site['./nino/blocks/state'] );

[ $third, $thirdId ] = $put( 3 );
[ , $page ] = placesCall( $site, 'builder/open', [ 'template' => 'page-home' ] );
$loopAt = (string) placesFind( $page['tree'], static fn( array $node ): bool => $node['n'] === 'collection-list' );
$real = placesEdit( $page['tree'], $loopAt, static fn( array $node ): array => array_replace( $node, [ 's' => '/services' ] ) );
[ $filled ] = placesCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => $real, 'texts' => [], 'base' => $page['stamp'] ] );
[ $unknown ] = placesCall( $site, 'builder/save', [ 'template' => 'page-home', 'tree' => placesEdit( $page['tree'], $loopAt, static fn( array $node ): array => array_replace( $node, [ 's' => '/nope' ] ) ), 'texts' => [], 'base' => $page['stamp'], 'force' => true ] );

check( 'a block that holds a loop is saved with the placeholder type, is put into a page, and the page shows a type of the project in it - a type the project has is taken and one it has not is refused',
	$loopSaved === 200 && $third === 200 && $loopAt !== '' && $filled === 200 && $unknown === 403 && str_contains( (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ), '[collection-list /services' ) === true && str_contains( (string) file_get_contents( $sandbox. '/private/templates/page-home.tpl' ), '[collection-table /_placeholder' ) === true );

ninoDone( $site );
