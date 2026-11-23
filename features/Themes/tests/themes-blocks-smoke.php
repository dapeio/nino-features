<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themes-blocks-smoke.php	Contract test for the blocks that go with a theme package - out of the project into the package of an own theme, and out of the folder of a theme
 *													into the project:
 *
 *													A. the dialog of the export: what the project offers, who is shown it, what the folder carries already, and the limits
 *													B. the export with blocks: they are in the archive and nowhere else, byte for byte, the folder is not written, a key in both is the project's
 *													C. the way back: a package with blocks, copied as an own theme, its blocks taken into the project - the same files; nothing is registered by it
 *													D. what is taken and what is not: a key that the project has is skipped or copied, never replaced; a header or a footer among them is a block to choose
 *													E. who and what may not: the right of the feature that lists the blocks, a folder that is a link, a block the kernel refuses
 *
 *	Usage: php features/Themes/tests/themes-blocks-smoke.php
 *				 NINO_ROOT=../nino php features/Themes/tests/themes-blocks-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Themes\Upload;

const BK_RIGHTS = [ '/_admin/builder/manage', '/_admin/builder/design/*' ];

/** A request of the panel: JSON in, the answer as an array, and what the request made */
function bkCall( array &$appData, string $action, array $data = [], int $before = 200 ): array {

	[ $status, $body, $request ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data + [ 'locale' => 'en_US' ], $action, $before );

	return [ $status, is_string( $body ) === true ? $body : json_decode( (string) json_encode( $body ), true ), $request ];
}

/** A site with the blocks of the Builder, the feature that lists them (where it is asked for) and the themes, and the frames of a site; an account with the rights given */
function bkSite( string $name, array $perms, bool $lab = true ): array {

	global $root;

	$site = ninoSandbox( $name );
	$sandbox = ninoSandboxDir( $site );
	$site['./nino/filesystem/path'] = $root;
	$site['/nino/html/assets'] = [];
	$site['/nino/html/navs'] = [ 'main' ];
	$site['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themes' ], $lab === true ? [ '\\Nino\\Modules\\Themelab' ] : [] );
	\Nino\Html::init( $site );
	\Nino\Features::registerComponents( $site );
	\Nino\Features::registerParts( $site );
	\Nino\Features::registerAssets( $site );
	\Nino\Modules::callModules( $site, 'init' );

	$header = "[header-wrap id=\"header\" sticky]\n\t[header-logo]\n\t[header-nav menu=\"main\"]\n[/header-wrap]\n";
	\Nino\Filesystem::putFileContent( $site, '/templates/html-header.tpl', "<header>head</header>\n" );
	\Nino\Filesystem::putFileContent( $site, '/templates/frame-header.tpl', $header );
	\Nino\Filesystem::putFileContent( $site, '/templates/frame-footer.tpl', "[footer-wrap id=\"footer\"]\n[/footer-wrap]\n" );
	\Nino\Filesystem::putFileContent( $site, '/templates/page-home.tpl', "<!-- nino:template-name Home -->\n[template /templates/html-header]\n" );
	\Nino\Filesystem::putFileContent( $site, '/text/de_DE.php', [ '[[/template/frame-header/header/button]]' => 'Schreib uns' ] );
	\Nino\Filesystem::putFileContent( $site, '/text/en_US.php', [ '[[/template/frame-header/header/button]]' => 'Write to us' ] );
	\Nino\Filesystem::putFileContent( $site, '/config.php', [ '/nino/html/navs' => [ 'main' ] ] );
	$site['/nino/http/routes'] = [ 'GET://home' => [ 'uri' => '/home', 'body' => '[template /templates/page-home]' ] ];
	ninoUser( $site, 'owner@example.com', $perms );

	return $site;
}

/** path below a folder => bytes of every file in it */
function bkFiles( string $dir ): array {

	$out = [];

	if( is_dir( $dir ) === false )
		return $out;

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( $file->isFile() === true )
			$out[substr( (string) $file, strlen( $dir ) + 1 )] = (string) file_get_contents( (string) $file );

	ksort( $out );

	return $out;
}

/** Hash of every file below a folder: for "nothing else is written" */
function bkHashes( string $dir ): array {
	return array_map( static fn( string $bytes ): string => hash( 'sha256', $bytes ), bkFiles( $dir ) );
}

/** A package chosen the way php hands a file over; the answer of the check and the key that holds the bytes */
function bkChoose( array &$appData, string $bytes ): array {

	$tmp = ninoSandboxDir( $appData ). '/upload-'. bin2hex( random_bytes( 4 ) ). '.tmp';
	file_put_contents( $tmp, $bytes );
	$_FILES['file'] = [ 'name' => 'theme.tar.gz', 'type' => 'application/gzip', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
	$answer = bkCall( $appData, 'themes/upload-check' );
	unset( $_FILES['file'] );
	@unlink( $tmp );

	return $answer;
}

$limits = \Nino\Packages::LIMITS[\Nino\Packages::THEME];
$all = [ \Nino\Modules\Themes::PERM, \Nino\Modules\Themelab::PERM, ...BK_RIGHTS ];
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"><script>alert(2)</script><rect width="10" height="10" fill="#cccccc" onclick="alert(3)"/></svg>';

echo "\nA. The dialog of the export\n";

$site = bkSite( 'themes-blocks-a', $all );
$sandbox = ninoSandboxDir( $site );
$_SESSION = [];

// three blocks of the project: a copy of a block of the kernel with a picture of its own, an empty one, and a header
[ , $made ] = bkCall( $site, 'themelab/create', [ 'name' => [ 'de_DE' => 'Mein Split', 'en_US' => 'My split' ], 'category' => 'content', 'from' => 'split-image-text' ] );
$split = (string) ( $made['key'] ?? '' );
[ , $made ] = bkCall( $site, 'themelab/create', [ 'name' => [ 'de_DE' => 'Leerer Block', 'en_US' => 'Empty block' ], 'category' => 'intro' ] );
$empty = (string) ( $made['key'] ?? '' );
@mkdir( $sandbox. '/private/blocks/'. $split. '/images', 0755, true );
file_put_contents( $sandbox. '/private/blocks/'. $split. '/images/picture.svg', $svg );
[ $adopted, $made ] = bkCall( $site, 'themelab/frame-adopt', [ 'template' => 'frame-header', 'key' => 'mein-kopf', 'name' => [ 'de_DE' => 'Mein Kopf', 'en_US' => 'My head' ], 'description' => [ 'de_DE' => 'Kopf', 'en_US' => 'Head' ] ] );
\Nino\Blocks::forget( $site );

// an own theme of the project
[ $madeOwn, $own ] = bkCall( $site, 'themes/save-as', [ 'mix' => [ 'name' => 'Boxed', 'css' => '', 'parts' => new stdClass() ] ] );
$ownDir = $sandbox. '/private/themes/boxed';
$ownBefore = bkHashes( $ownDir );

[ $offerStatus, $offer ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed' ] );
$keys = array_column( $offer['blocks'] ?? [], 'key' );
$by = array_column( $offer['blocks'] ?? [], null, 'key' );

check( 'the dialog of the export is given what the project offers: the own blocks of the project - the ones of the pages and the header that is kept as a block - by key, each with its name in the language asked for, whether it is a header or a footer, what a header reads of the site, and the files and the bytes it adds to a package',
	$madeOwn === 200 && $adopted === 200 && $offerStatus === 200 && ( $offer['available'] ?? false ) === true && $keys === [ $empty, 'mein-kopf', $split ] && ( $by[$split]['name'] ?? '' ) === 'My split' && ( $by['mein-kopf']['frame'] ?? false ) === true && ( $by[$split]['frame'] ?? true ) === false
	&& is_array( $by['mein-kopf']['reads'] ?? null ) === true && ( $by[$split]['files'] ?? 0 ) === count( bkFiles( $sandbox. '/private/blocks/'. $split ) ) && ( $by[$split]['bytes'] ?? 0 ) === array_sum( array_map( 'strlen', bkFiles( $sandbox. '/private/blocks/'. $split ) ) ) );

[ , $offerDe ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed', 'locale' => 'de_DE' ] );
$byDe = array_column( $offerDe['blocks'] ?? [], null, 'key' );

check( 'each block of the dialog says what it is in the words of the workbench for its category, in the language asked for - the list of the feature that lists the blocks carries the word, the themes read none of its texts - and a header is a "Header": in English and in German',
	( $by[$split]['kind'] ?? '' ) === 'Static content' && ( $by['mein-kopf']['kind'] ?? '' ) === 'Header' && ( $by[$empty]['kind'] ?? '' ) === 'Titles' && ( $byDe[$split]['kind'] ?? '' ) === 'Statische Inhalte' && ( $byDe['mein-kopf']['kind'] ?? '' ) === 'Kopfbereich' && ( $byDe[$empty]['kind'] ?? '' ) === 'Titel' );

check( 'and the limits of a package, and what the folder of the theme has already: the files and bytes of the folder without blocks, none carried - so that a package that is too large is said before it is packed - and nothing is written by asking',
	( $offer['limits'] ?? [] ) === [ 'files' => $limits['files'], 'bytes' => $limits['bytes'] ] && ( $offer['base']['files'] ?? 0 ) === count( $ownBefore ) && ( $offer['base']['bytes'] ?? 0 ) === array_sum( array_map( 'strlen', bkFiles( $ownDir ) ) ) && ( $offer['carried'] ?? null ) === [] && bkHashes( $ownDir ) === $ownBefore );

// who is shown the dialog: the right of the feature that lists the blocks, and the feature
ninoUser( $site, 'themer@example.com', [ \Nino\Modules\Themes::PERM ] );
[ $plainStatus, $plain ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed' ] );
[ $plainExport ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true ] );
[ $forced, $forcedBody ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => [ $split ] ] );
[ $forcedDirect ] = ninoCall( $site, [ \Nino\Modules\Themes\Admin::class, 'apiExport' ], [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => [ $split ] ], 'themes/export', 200 );
ninoUser( $site, 'owner@example.com', $all );
$noLab = bkSite( 'themes-blocks-nolab', $all, false );
bkCall( $noLab, 'themes/save-as', [ 'mix' => [ 'name' => 'Boxed', 'css' => '', 'parts' => new stdClass() ] ] );
[ $offLabStatus, $offLab ] = bkCall( $noLab, 'themes/export-blocks', [ 'theme' => 'own:boxed' ] );
[ $offLabBlocks ] = bkCall( $noLab, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => [ 'x' ] ] );

check( 'an account that holds the right of the themes and not the right of the blocks is not shown the dialog - the answer is that there is nothing to choose - and exports the folder alone; a request of it that names blocks is refused, 403, not made without them; where the feature is off it is the same',
	$plainStatus === 200 && ( $plain['available'] ?? true ) === false && $plainExport === 200 && $forced === 403 && ( $forcedBody['code'] ?? '' ) === 'themes_blocks_right' && $forcedDirect === 403 && $offLabStatus === 200 && ( $offLab['available'] ?? true ) === false && $offLabBlocks === 403 );

[ $noTheme ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:not-there' ] );
[ $notOwn ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'nino' ] );
[ $leaves ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:../themes/boxed' ] );
[ $noToken ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed' ], 403 );
ninoUser( $site, 'builder@example.com', BK_RIGHTS );
[ $noRight ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed' ] );
[ $noRightDirect ] = ninoCall( $site, [ \Nino\Modules\Themes\Admin::class, 'apiExportBlocks' ], [ 'theme' => 'own:boxed' ], null, 200 );
ninoUser( $site, 'owner@example.com', $all );

check( 'the dialog is asked for an own theme of the project and nothing else: a theme that is not there, a theme of a package and a key that leaves the folder are a 404; a request without its token and an account without the right of the themes are a 403',
	$noTheme === 404 && $notOwn === 404 && $leaves === 404 && $noToken === 403 && $noRight === 403 && $noRightDirect === 403 );

echo "\nB. The export with blocks\n";

[ $exportStatus, $bytes, $request ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'blocks' => [ $split, 'mein-kopf' ] ] );
$files = is_string( $bytes ) === true ? \Nino\Archive::read( $bytes, 'boxed', 3 * $limits['files'], max( \Nino\Blocks::MAX_PICTURE, \Nino\Packages::MAX_PREVIEW ), $limits['bytes'] ) : 'none';
$cleaned = (string) \Nino\Images::cleanSvg( $svg );
$expected = [];

foreach( [ $split, 'mein-kopf' ] as $key )
	foreach( bkFiles( $sandbox. '/private/blocks/'. $key ) as $path => $content )
		$expected['blocks/'. $key. '/'. $path] = str_ends_with( $path, '.svg' ) === true ? $cleaned : $content;

$inside = is_array( $files ) === true ? array_filter( $files, static fn( string $path ): bool => str_starts_with( $path, 'blocks/' ), ARRAY_FILTER_USE_KEY ) : [];
ksort( $expected );
ksort( $inside );

check( 'the blocks that were chosen are in the archive as blocks/<key>/ with the files they have in the project, byte for byte - the picture of a block that has a script in it as the picture the kernel rebuilds of it - and a block that was not chosen is not there',
	$exportStatus === 200 && is_array( $files ) === true && $inside === $expected && count( array_filter( array_keys( $files ), static fn( string $path ): bool => str_contains( $path, $empty ) === true ) ) === 0 && preg_match( '/script|onload|onclick|alert/i', (string) ( $inside['blocks/'. $split. '/images/picture.svg'] ?? '' ) ) === 0 );

check( 'the package is what the kernel takes as a package of a theme, with the blocks and without anything else of the project; the download has the headers of one',
	is_array( $files ) === true && \Nino\Packages::check( $site, \Nino\Packages::THEME, $files ) === null && ( $request['/nino/http/response']['header']['Content-Disposition'] ?? '' ) === 'attachment; filename="boxed-1.0.0.tar.gz"' && ( $request['/nino/http/response']['header']['Content-Type'] ?? '' ) === 'application/gzip' );

check( 'the folder of the own theme is not written by it, and nothing of the project is: the files of the folder and of the blocks of the project are those they were',
	bkHashes( $ownDir ) === $ownBefore && is_dir( $ownDir. '/blocks' ) === false && bkHashes( $sandbox. '/private/blocks/'. $split )['images/picture.svg'] === hash( 'sha256', $svg ) );

[ $dry, $said ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => [ $split ] ] );

check( 'the question whether it can be done is answered with the file, its size and the files, and says that none of the folder was replaced',
	$dry === 200 && ( $said['file'] ?? '' ) === 'boxed-1.0.0.tar.gz' && ( $said['replaced'] ?? null ) === [] && ( $said['files'] ?? 0 ) === count( $ownBefore ) + count( bkFiles( $sandbox. '/private/blocks/'. $split ) ) );

// the folder carries blocks already: a key in both is the project's, and is said; the carried ones that the project has no block of stay
$carried = [ 'blocks/'. $split. '/block.json' => '{"name":{"en_US":"Carried"}}', 'blocks/'. $split. '/block.tpl' => 'x', 'blocks/old-one/block.json' => (string) file_get_contents( $sandbox. '/private/blocks/'. $empty. '/block.json' ), 'blocks/old-one/block.tpl' => (string) file_get_contents( $sandbox. '/private/blocks/'. $empty. '/block.tpl' ), 'blocks/old-one/text/en_US.json' => (string) file_get_contents( $sandbox. '/private/blocks/'. $empty. '/text/en_US.json' ), 'blocks/old-one/text/de_DE.json' => (string) file_get_contents( $sandbox. '/private/blocks/'. $empty. '/text/de_DE.json' ) ];

foreach( $carried as $path => $content ) {
	@mkdir( dirname( $ownDir. '/'. $path ), 0755, true );
	file_put_contents( $ownDir. '/'. $path, $content );
}

$carriedBefore = bkHashes( $ownDir );
[ , $offer2 ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed' ] );
[ $union, $unionBytes ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'blocks' => [ $split ] ] );
[ , $unionSaid ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'blocks' => [ $split ], 'check' => true ] );
$unionFiles = is_string( $unionBytes ) === true ? \Nino\Archive::read( $unionBytes, 'boxed', 3 * $limits['files'], max( \Nino\Blocks::MAX_PICTURE, \Nino\Packages::MAX_PREVIEW ), $limits['bytes'] ) : [];

check( 'what the folder carries in blocks/ is joined with the choice: the block of the project takes the place of the carried one of its key - and the answer says which key it is - the other carried block stays, and the dialog is told what is carried, by key, with its files and bytes',
	$union === 200 && is_array( $unionFiles ) === true && ( $unionFiles['blocks/'. $split. '/block.tpl'] ?? '' ) === (string) file_get_contents( $sandbox. '/private/blocks/'. $split. '/block.tpl' ) && ( $unionFiles['blocks/old-one/block.tpl'] ?? '' ) === $carried['blocks/old-one/block.tpl']
	&& ( $unionSaid['replaced'] ?? [] ) === [ $split ] && array_keys( (array) ( $offer2['carried'] ?? [] ) ) === [ $split, 'old-one' ] && bkHashes( $ownDir ) === $carriedBefore );

// what may not be chosen
$refused = [];

foreach( [ 'a block that is not there' => 'no-such-block', 'a block of the kernel' => 'hero-full', 'a block of the theme that comes with the feature' => 'header-line', 'a key that leaves the folder' => '../blocks', 'a key with a slash' => $split. '/images', 'upper case' => 'Mein-Kopf' ] as $what => $key ) {

	[ $statusOne, $bodyOne ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'blocks' => [ $key ] ] );
	[ $statusTwo ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => [ $key ] ] );
	$refused[$what] = $statusOne === 404 && $statusTwo === 404 && is_array( $bodyOne ) === true && ( $bodyOne['code'] ?? '' ) === 'themes_export_block_unknown';
}

[ $many, $manyBody ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => array_map( static fn( int $at ): string => 'block-'. $at, range( 1, 101 ) ) ] );

check( 'only an own block of the project goes into a package: a key that no block has, a block of the kernel, a key that leaves the folder, a key with a slash, one in the wrong case are a 404 and no file; more than a hundred blocks are a 400: '. implode( '; ', array_keys( $refused ) ),
	array_values( array_unique( $refused ) ) === [ true ] && $many === 400 && ( $manyBody['code'] ?? '' ) === 'themes_export_blocks_many' );

// more files than a package may have: said as the check says it, and no file - the folder carries 100 blocks of four files, and one block more is chosen
\Nino\Filesystem::removeDir( $ownDir. '/blocks' );

for( $at = 1; $at <= 100; $at++ )
	foreach( [ 'block.json', 'block.tpl', 'text/en_US.json', 'text/de_DE.json' ] as $name ) {
		@mkdir( dirname( $ownDir. '/blocks/carry-'. $at. '/'. $name ), 0755, true );
		copy( $sandbox. '/private/blocks/'. $empty. '/'. $name, $ownDir. '/blocks/carry-'. $at. '/'. $name );
	}

[ $tooMany, $tooManyBody ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'check' => true, 'blocks' => [ $split ] ] );
[ $heavyStatus, $heavyOffer ] = bkCall( $site, 'themes/export-blocks', [ 'theme' => 'own:boxed' ] );
\Nino\Filesystem::removeDir( $ownDir. '/blocks' );

check( 'a package with more files than a package may have is refused with the words of the check of the kernel, 400 - the folder carries 100 blocks and one more is chosen - and the dialog is not given for a folder that is that large by itself, which is not exportable either',
	$tooMany === 400 && ( $tooManyBody['code'] ?? '' ) === 'themes_upload_package_files_many' && $heavyStatus === 400 && ( $heavyOffer['code'] ?? '' ) === 'themes_upload_package_files_many' );

echo "\nC. The way back\n";

// a second project: the package comes in as an own theme, and its blocks are taken
$siteB = bkSite( 'themes-blocks-b', $all );
$sandboxB = ninoSandboxDir( $siteB );
$_SESSION = [];
[ $packStatus, $packBytes ] = bkCall( $site, 'themes/export', [ 'theme' => 'own:boxed', 'blocks' => [ $split, 'mein-kopf' ] ] );
[ $checked, $preview ] = bkChoose( $siteB, (string) $packBytes );
$id = (string) ( $_SESSION[Upload::SESSION]['id'] ?? '' );
[ $copied, $copy ] = bkCall( $siteB, 'themes/upload-own', [ 'id' => $id ] );
unset( $siteB['./nino/themes/own'] );
[ , $list ] = bkCall( $siteB, 'themes/list' );
$card = array_values( array_filter( $list['themes'] ?? [], static fn( array $one ): bool => $one['key'] === 'own:boxed' ) )[0] ?? [];
$blocksBefore = bkHashes( $sandboxB. '/private/blocks' );
$registered = array_keys( array_filter( \Nino\Blocks::all( $siteB ), static fn( array $block ): bool => in_array( (string) $block['origin'], [ 'boxed' ], true ) === true ) );

check( 'a package that carries blocks is copied as an own theme, and its card says how many it holds - the two that were chosen - and that they can be taken; nothing is registered by the copy: the project has none of them as blocks',
	$packStatus === 200 && $checked === 200 && $copied === 200 && ( $card['blocks'] ?? 0 ) === 2 && ( $list['blocksTake'] ?? false ) === true && $registered === [] && array_intersect( [ $split, 'mein-kopf' ], array_keys( \Nino\Blocks::all( $siteB ) ) ) === [] && $blocksBefore === [] );

[ $shownStatus, $shown ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed' ] );
[ , $shownDe ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed', 'locale' => 'de_DE' ] );
$shownBy = array_column( $shown['blocks'] ?? [], null, 'key' );

check( 'the blocks of the theme say what they are in the words of the workbench for their category, in the language asked for', array_column( $shown['blocks'] ?? [], 'kind', 'key' ) === [ 'mein-kopf' => 'Header', $split => 'Static content' ] && array_column( $shownDe['blocks'] ?? [], 'kind', 'key' ) === [ 'mein-kopf' => 'Kopfbereich', $split => 'Statische Inhalte' ] );

check( 'the blocks of the theme are shown before anything is written: each with its name, its category and whether the project has the key - all are free here - and the name of the theme',
	$shownStatus === 200 && array_keys( $shownBy ) === [ 'mein-kopf', $split ] && ( $shownBy[$split]['state'] ?? '' ) === 'free' && ( $shownBy[$split]['name'] ?? '' ) === 'My split' && ( $shownBy['mein-kopf']['category'] ?? '' ) === 'header' && ( $shown['theme'] ?? '' ) === 'Boxed' && bkHashes( $sandboxB. '/private/blocks' ) === $blocksBefore );

$items = [ $split => [ 'take' => true, 'how' => 'skip' ], 'mein-kopf' => [ 'take' => true, 'how' => 'skip' ] ];
[ $takenStatus, $taken ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => $items ] );
\Nino\Blocks::forget( $siteB );
$results = array_column( $taken['results'] ?? [], 'result', 'key' );
$same = true;

foreach( [ $split, 'mein-kopf' ] as $key )
	foreach( bkFiles( $sandbox. '/private/blocks/'. $key ) as $path => $content )
		$same = $same && ( bkFiles( $sandboxB. '/private/blocks/'. $key )[$path] ?? null ) === ( str_ends_with( $path, '.svg' ) === true ? $cleaned : $content );

check( 'the round trip: the blocks that were exported with the theme, the theme uploaded and copied, the blocks taken - are blocks of the project, file for file the ones they were in the first project (the picture as it was cleaned)',
	$takenStatus === 200 && ( $results[$split] ?? '' ) === 'written' && ( $results['mein-kopf'] ?? '' ) === 'written' && $same === true
	&& array_keys( bkFiles( $sandboxB. '/private/blocks/'. $split ) ) === array_keys( bkFiles( $sandbox. '/private/blocks/'. $split ) ) );

$projectBlocks = \Nino\Blocks::all( $siteB );

check( 'what was taken is a block of the project, of its origin, a header among them with its category: the project lists them as it lists the blocks it made itself',
	( $projectBlocks[$split]['origin'] ?? '' ) === 'project' && ( $projectBlocks['mein-kopf']['origin'] ?? '' ) === 'project' && ( $projectBlocks['mein-kopf']['category'] ?? '' ) === 'header' && ( $projectBlocks['mein-kopf']['side'] ?? '' ) === 'header' );

echo "\nD. What is taken and what is not\n";

// the frame of the site is the one it was: a header that was taken is a block that can be chosen for the frame, and is not the frame
$frameFile = $sandboxB. '/private/templates/frame-header.tpl';
$frameBytes = (string) file_get_contents( $frameFile );
ninoUser( $siteB, 'designer@example.com', $all );
[ $offerStatus, $offerFrames ] = bkCall( $siteB, 'builder/framechoices', [ 'template' => 'frame-header' ] );
[ $listStatus, $labList ] = bkCall( $siteB, 'themelab/list' );

check( 'a header taken from a package is a block of the project that can be chosen for the header of the site - the choice lists it, and the list of the blocks of the project has it as a block of the headers - and is not the header of the site: its template is as it was',
	$offerStatus === 200 && in_array( 'mein-kopf', array_column( $offerFrames['blocks'] ?? [], 'key' ), true ) === true && $listStatus === 200 && in_array( 'mein-kopf', array_column( $labList['blocks'] ?? [], 'key' ), true ) === true && (string) file_get_contents( $frameFile ) === $frameBytes );

// the same blocks again: the keys are the project's now - skipped, copied under a key of their own, never replaced
$mine = bkHashes( $sandboxB. '/private/blocks' );
[ , $again ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed' ] );
$againBy = array_column( $again['blocks'] ?? [], 'state', 'key' );
[ $skipStatus, $skip ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => [ $split => [ 'take' => true, 'how' => 'skip' ] ] ] );
$skipped = bkHashes( $sandboxB. '/private/blocks' ) === $mine;
[ $replaceStatus, $replace ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => [ $split => [ 'take' => true, 'how' => 'replace' ] ] ] );
$replaced = bkHashes( $sandboxB. '/private/blocks' ) === $mine;
file_put_contents( $sandboxB. '/private/blocks/'. $split. '/block.tpl', (string) file_get_contents( $sandboxB. '/private/blocks/'. $split. '/block.tpl' ). "\n" );
\Nino\Blocks::forget( $siteB );
$edited = bkHashes( $sandboxB. '/private/blocks/'. $split );
[ $copyStatus, $copyTaken ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => [ $split => [ 'take' => true, 'how' => 'copy' ] ] ] );
\Nino\Blocks::forget( $siteB );
$copyKey = (string) ( array_column( $copyTaken['results'] ?? [], 'as', 'key' )[$split] ?? '' );

check( 'a key that the project has is taken only as the person says: it is shown as taken; skipped, nothing is written; "replace" - which the one way blocks come in has for the project\'s own blocks - is a skip here, nothing is overwritten, not even the block that was changed since; as a copy it is a block under a key of its own with the files of the package, and the block that was there is as it was',
	( $againBy[$split] ?? '' ) === 'taken' && $skipStatus === 200 && $skipped === true && $replaceStatus === 200 && $replaced === true && ( array_column( $replace['results'] ?? [], 'result', 'key' )[$split] ?? '' ) === 'skipped' && $copyStatus === 200 && $copyKey === $split. '-2'
	&& bkHashes( $sandboxB. '/private/blocks/'. $split ) === $edited && is_file( $sandboxB. '/private/blocks/'. $copyKey. '/block.tpl' ) === true && array_keys( bkFiles( $sandboxB. '/private/blocks/'. $copyKey ) ) === array_keys( bkFiles( $sandbox. '/private/blocks/'. $split ) ) );

// the way of an installed theme: its blocks are blocks of the theme, and are taken as copies
[ $installedStatus ] = bkChoose( $siteB, (string) $packBytes );
$installId = (string) ( $_SESSION[Upload::SESSION]['id'] ?? '' );
[ $installed ] = bkCall( $siteB, 'themes/upload', [ 'id' => $installId ] );
[ $installedShown, $installedList ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'boxed' ] );
[ , $cards ] = bkCall( $siteB, 'themes/list' );
$installedCard = array_values( array_filter( $cards['themes'] ?? [], static fn( array $one ): bool => $one['key'] === 'boxed' ) )[0] ?? [];

check( 'an installed theme says how many blocks it carries and shows them too, read from the folder of the package: the keys are the theme\'s own blocks now, so they are shown as taken',
	$installedStatus === 200 && $installed === 200 && $installedShown === 200 && ( $installedCard['blocks'] ?? 0 ) === 2 && array_values( array_unique( array_column( $installedList['blocks'] ?? [], 'state' ) ) ) === [ 'taken' ] && count( $installedList['blocks'] ?? [] ) === 2 );

echo "\nE. Who and what may not\n";

// without the right of the feature that lists the blocks
ninoUser( $siteB, 'themer@example.com', [ \Nino\Modules\Themes::PERM ] );
[ $noBlocks ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed' ] );
[ $noTake, $noTakeBody ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => [ $split => [ 'take' => true, 'how' => 'skip' ] ] ] );
[ , $plainList ] = bkCall( $siteB, 'themes/list' );
$plainCard = array_values( array_filter( $plainList['themes'] ?? [], static fn( array $one ): bool => $one['key'] === 'own:boxed' ) )[0] ?? [];
$beforeDenied = bkHashes( $sandboxB. '/private/blocks' );
[ $noTokenTake ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => [ $split => [ 'take' => true, 'how' => 'skip' ] ] ], 403 );
$directly = static function( string $method, array $data ) use ( &$siteB ): int {
	return ninoCall( $siteB, [ \Nino\Modules\Themes\Admin::class, $method ], $data, null, 200 )[0];
};
$directDenied = [ $directly( 'apiBlocks', [ 'theme' => 'own:boxed' ] ), $directly( 'apiBlocksTake', [ 'theme' => 'own:boxed', 'items' => [ $split => [ 'take' => true ] ] ] ) ];
ninoUser( $siteB, 'owner2@example.com', $all );

check( 'an account that holds the right of the themes and not the right of the blocks sees how many blocks a theme holds, is told that none can be taken - the list says so - and can neither read the blocks nor take them: 403, also past the dispatcher, and a request without its token; nothing is written',
	$noBlocks === 403 && $noTake === 403 && ( $noTakeBody['code'] ?? '' ) === 'themes_blocks_right' && ( $plainList['blocksTake'] ?? true ) === false && ( $plainCard['blocks'] ?? 0 ) === 2 && $noTokenTake === 403 && $directDenied === [ 403, 403 ] && bkHashes( $sandboxB. '/private/blocks' ) === $beforeDenied );

// a folder the reader of the own themes refuses - its name is not the key in its theme.json - is no theme to take blocks from, whatever it carries
$mismatch = $sandboxB. '/private/themes/rj-folder';
\Nino\Filesystem::copyDir( $sandboxB. '/private/themes/boxed', $mismatch );
file_put_contents( $mismatch. '/theme.json', json_encode( array_merge( json_decode( (string) file_get_contents( $mismatch. '/theme.json' ), true ), [ 'key' => 'rj-mismatch' ] ) ) );
unset( $siteB['./nino/themes/own'] );
$beforeRefused = bkHashes( $sandboxB. '/private/blocks' );
[ $refusedList ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:rj-folder' ] );
[ $refusedTake ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:rj-folder', 'items' => [ $split => [ 'take' => true, 'how' => 'copy' ] ] ] );
[ $refusedKey ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:rj-mismatch' ] );

check( 'blocks are taken from a theme that the reader of the own themes knows: a folder it refuses - the name of the folder is not the key in its theme.json - is a 404 for the list and for the taking, so is the key in its theme.json, and nothing is written',
	$refusedList === 404 && $refusedTake === 404 && $refusedKey === 404 && bkHashes( $sandboxB. '/private/blocks' ) === $beforeRefused );
\Nino\Filesystem::removeDir( $mismatch );
unset( $siteB['./nino/themes/own'] );

// a theme that is none, a key that leaves the folder, a theme that carries no blocks
[ $none ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:not-there' ] );
[ $outside ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:../boxed' ] );
[ $ofShipped ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'nino' ] );
bkCall( $siteB, 'themes/save-as', [ 'mix' => [ 'name' => 'Bare', 'css' => '', 'parts' => new stdClass() ] ] );
[ $bare, $bareBody ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:bare' ] );

check( 'what is asked is a theme that the project has and that carries blocks: a theme that is not there and a key that leaves the folder are a 404, a theme without blocks is a 404 that says so, and the theme that comes with the feature carries blocks of its own that are shown like those of any other',
	$none === 404 && $outside === 404 && $ofShipped === 200 && $bare === 404 && ( $bareBody['code'] ?? '' ) === 'themes_blocks_none' );

// a folder that is a link, a block that is a link, and a block the kernel refuses
$boxed = $sandboxB. '/private/themes/boxed';
$secret = $sandboxB. '/outside-blocks';
// what the links point at is a block that is whole, with a word in its name that no answer may have
foreach( bkFiles( $sandboxB. '/private/blocks/mein-kopf' ) as $path => $content ) {
	@mkdir( dirname( $secret. '/leaked/'. $path ), 0755, true );
	file_put_contents( $secret. '/leaked/'. $path, $path === 'block.json' ? str_replace( 'My head', 'secret of the project', $content ) : $content );
}
rename( $boxed. '/blocks', $boxed. '/blocks.away' );
symlink( $secret, $boxed. '/blocks' );
unset( $siteB['./nino/themes/own'] );
[ $linked, $linkedBody ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed' ] );
unlink( $boxed. '/blocks' );
rename( $boxed. '/blocks.away', $boxed. '/blocks' );
symlink( $secret. '/leaked', $boxed. '/blocks/leaked' );
unset( $siteB['./nino/themes/own'] );
[ $linkedBlock, $linkedBlockBody ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed' ] );
unlink( $boxed. '/blocks/leaked' );
file_put_contents( $boxed. '/blocks/'. $split. '/block.json', '{ not json' );
unset( $siteB['./nino/themes/own'] );
[ $broken, $brokenBody ] = bkCall( $siteB, 'themes/blocks', [ 'theme' => 'own:boxed' ] );
$beforeBroken = bkHashes( $sandboxB. '/private/blocks' );
[ $brokenTake ] = bkCall( $siteB, 'themes/blocks-take', [ 'theme' => 'own:boxed', 'items' => [ 'mein-kopf' => [ 'take' => true, 'how' => 'copy' ] ] ] );

check( 'a folder blocks/ that is a link, a block in it that is a link, and a block the kernel refuses are not read: 400 with the reason, nothing that the link points at is in any answer, and nothing of the blocks is taken - not the ones that are whole either',
	$linked === 400 && $linkedBlock === 400 && $broken === 400 && $brokenTake === 400 && str_contains( json_encode( [ $linkedBody, $linkedBlockBody, $brokenBody ] ), 'secret of the project' ) === false && bkHashes( $sandboxB. '/private/blocks' ) === $beforeBroken );

ninoDone( $site );
