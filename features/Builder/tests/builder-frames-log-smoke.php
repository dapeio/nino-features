<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-frames-log-smoke.php	Contract test for the activity log of the frames of the site: each action that writes a frame leaves a line in the log - who did it, what, which frame - and an action that
 *													only reads, and an action that is refused, leaves none. The lines are read back from the log file of the day, as the log of the workbench shows them
 *
 *	Usage: php features/Builder/tests/builder-frames-log-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-frames-log-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

function logCall( array &$appData, string $action, array $data = [] ): array {

	$data += [ 'locale' => \Nino\Locales::getNativeLocale( $appData ) ];

	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
}

/** The lines of the log of the day, as the log file holds them */
function logLines( string $sandbox ): array {

	$path = $sandbox. '/private/.logs/'. date( 'Y-m-d' ). '.php';

	if( is_file( $path ) === false )
		return [];

	$prefix = "<?php http_response_code(403); exit; return '";
	$decoded = base64_decode( substr( (string) file_get_contents( $path ), strlen( $prefix ), -strlen( "';\n" ) ) );

	return $decoded === '' ? [] : explode( "\n", $decoded );
}

$site = ninoSandbox( 'builder-frames-log' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

$head = static fn( string $id ): string => "[header-wrap id=\"". $id. "\" sticky]\n\t[header-logo]\n\t[header-actions]\n\t[/header-actions]\n[/header-wrap]\n";

foreach( [ 'frame-header' => $head( 'header' ), 'frame-footer' => "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n", 'frame-header-shop' => $head( 'shop' ) ] as $name => $source )
	\Nino\Filesystem::putFileContent( $site, '/templates/'. $name. '.tpl', $source );

\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/html-footer.tpl', "<footer>foot</footer>\n" );
\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n[template /templates/html-footer]\n" );
foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::putFileContent( $site, '/text/'. $locale. '.php', [ '[[/template/frame-header/header/title]]' => $locale === 'de_DE' ? 'Titel' : 'Title' ] );
\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];

$LAB = \Nino\Modules\Themelab::PERM;
ninoUser( $site, 'maker@example.com', [ '/_admin/builder/manage', '/_admin/builder/design/*', $LAB ] );

// What was added to the log by one request: the lines after the request that were not there before
$logged = static function( array &$site, string $sandbox, string $action, array $data ): array {

	$before = count( logLines( $sandbox ) );
	[ $status ] = logCall( $site, $action, $data );

	// the login of the account and the backup that the first change of the day makes are lines of their own
	return [ $status, array_values( array_filter( array_slice( logLines( $sandbox ), $before ), static fn( string $line ): bool => preg_match( '/  (Login|Backup created)$/', $line ) !== 1 ) ) ];
};
$stampOf = static function( array &$site, string $template ): string { $state = \Nino\Modules\Builder\Store::state( $site, $template ); return $state === null ? '' : \Nino\Modules\Builder\Store::stamp( $state ); };
$open = static function( array &$site, string $template ): array { [ , $answer ] = logCall( $site, 'builder/frame-open', [ 'template' => $template ] ); return $answer; };

echo "\nA. What writes a frame is in the log\n";

$seen = [];

// make, rename, take away
$seen['create'] = $logged( $site, $sandbox, 'builder/frame-create', [ 'side' => 'header', 'word' => 'neu', 'name' => 'Neu', 'from' => 'frame-header' ] );
$seen['rename'] = $logged( $site, $sandbox, 'builder/frame-rename', [ 'template' => 'frame-header-neu', 'name' => 'Neuer' ] );
$seen['remove'] = $logged( $site, $sandbox, 'builder/frame-remove', [ 'template' => 'frame-header-neu' ] );

// save, and the last state of the editor
$editor = $open( $site, 'frame-header-shop' );
$tree = $editor['tree'];
$tree[0]['a']['id'] = 'shop2';
$seen['save'] = $logged( $site, $sandbox, 'builder/frame-save', [ 'template' => 'frame-header-shop', 'tree' => $tree, 'texts' => [], 'base' => $editor['stamp'] ] );
$seen['restore'] = $logged( $site, $sandbox, 'builder/frame-restore', [ 'template' => 'frame-header-shop', 'base' => $stampOf( $site, 'frame-header-shop' ) ] );

// the choice of a block and its way back
$seen['choose'] = $logged( $site, $sandbox, 'builder/framechoose', [ 'template' => 'frame-header-shop', 'key' => 'header-line', 'base' => $stampOf( $site, 'frame-header-shop' ) ] );
$seen['back'] = $logged( $site, $sandbox, 'builder/framerestore', [ 'template' => 'frame-header-shop', 'base' => $stampOf( $site, 'frame-header-shop' ) ] );

// the texts of a frame
$seen['texts'] = $logged( $site, $sandbox, 'builder/frametextssave', [ 'template' => 'frame-header', 'texts' => [], 'base' => $stampOf( $site, 'frame-header' ) ] );

// a picture
$image = imagecreatetruecolor( 64, 48 );
ob_start();
imagepng( $image );
$bytes = (string) ob_get_clean();
$path = (string) tempnam( sys_get_temp_dir(), 'nino-up-' );
file_put_contents( $path, $bytes );
$_FILES['file'] = [ 'name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
$seen['upload'] = $logged( $site, $sandbox, 'builder/frame-upload', [ 'template' => 'frame-header', 'uri' => '/template/frame-header/header/picture', 'width' => 64, 'height' => 48 ] );
unset( $_FILES['file'] );

// kept as a block
$seen['block'] = $logged( $site, $sandbox, 'themelab/adopt', [ 'template' => 'frame-header', 'key' => 'block-kopf', 'name' => [ 'de_DE' => 'Kopf', 'en_US' => 'Head' ], 'description' => 'x' ] );

$lineOf = static function( array $entry ): string { return $entry[0] === 200 && count( $entry[1] ) === 1 ? (string) preg_replace( '/^.*?(maker@example\.com)\s*/', '$1 ', $entry[1][0] ) : $entry[0]. ' '. count( $entry[1] ); };

$lines = array_map( $lineOf, $seen );

check( 'making, renaming and taking away a frame leave one line each: the account and what was done to which frame - the side and the word of a new one, the template of the others',
	( $lines['create'] ?? '' ) === 'maker@example.com Create Frame header neu' && ( $lines['rename'] ?? '' ) === 'maker@example.com Rename Frame frame-header-neu' && ( $lines['remove'] ?? '' ) === 'maker@example.com Delete Frame frame-header-neu' );
check( 'saving a frame, taking its last state back, choosing a block for it and taking the choice back leave one line each, with the template',
	( $lines['save'] ?? '' ) === 'maker@example.com Save Frame frame-header-shop' && ( $lines['restore'] ?? '' ) === 'maker@example.com Restore Last Frame State frame-header-shop' && ( $lines['choose'] ?? '' ) === 'maker@example.com Choose Frame Block frame-header-shop header-line'
	&& ( $lines['back'] ?? '' ) === 'maker@example.com Restore Last Frame State frame-header-shop' );
check( 'the texts of a frame, a picture of it and keeping it as a block leave one line each: the template, the address of the picture, and the frame with the key of the block that was made',
	( $lines['texts'] ?? '' ) === 'maker@example.com Save Frame Texts frame-header' && ( $lines['upload'] ?? '' ) === 'maker@example.com Upload Frame Picture /template/frame-header/header/picture' && ( $lines['block'] ?? '' ) === 'maker@example.com Adopt Frame frame-header As block-kopf' );

echo "\nB. What reads or is refused leaves none\n";

$quiet = [
	$logged( $site, $sandbox, 'builder/frame-list', [] ),
	$logged( $site, $sandbox, 'builder/frame-open', [ 'template' => 'frame-header' ] ),
	$logged( $site, $sandbox, 'builder/framechoices', [ 'template' => 'frame-header' ] ),
];
ninoUser( $site, 'reader@example.com', [ '/_admin/builder/manage', '/_admin/builder/content/*' ] );
$refused = [
	$logged( $site, $sandbox, 'builder/frame-create', [ 'side' => 'header', 'word' => 'nein', 'name' => 'Nein', 'from' => 'frame-header' ] ),
	$logged( $site, $sandbox, 'builder/frame-remove', [ 'template' => 'frame-header-shop' ] ),
	$logged( $site, $sandbox, 'builder/frame-rename', [ 'template' => 'frame-header-shop', 'name' => 'Nein' ] ),
];

check( 'a list, an open and the blocks of a choice leave no line; a refused account leaves none either - 403, no line, and the frame is there',
	array_filter( $quiet, static fn( array $entry ): bool => $entry[0] !== 200 || $entry[1] !== [] ) === [] && array_filter( $refused, static fn( array $entry ): bool => $entry[0] !== 403 || $entry[1] !== [] ) === []
	&& is_file( $sandbox. '/private/templates/frame-header-shop.tpl' ) === true );

ninoDone( $site );
