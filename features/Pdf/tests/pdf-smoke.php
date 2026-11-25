<?php
declare(strict_types=1);

// The Pdf feature's contract test. Runs against the checkout three levels up,
// or the one NINO_ROOT names
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

use Nino\Modules\Pdf as Pdf;
use Nino\Modules\Pdf\Layout\Allow;
use Nino\Modules\Pdf\Layout\Sheet;
use Nino\Modules\Pdf\Layout\Tokens;

$appData = ninoSandbox( 'pdf' );
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
$dir = ninoSandboxDir( $appData );

// --- what the tests draw on --------------------------------------------------

$post = static function( string $action, array $data = [] ) use ( &$appData ): array {
	return ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
};

$picture = static function( string $kind, int $width = 40, int $height = 20, bool $alpha = false, bool $progressive = false ) use ( $dir ): string {

	$image = imagecreatetruecolor( $width, $height );
	imagealphablending( $image, false );
	imagesavealpha( $image, true );
	imagefill( $image, 0, 0, (int) imagecolorallocatealpha( $image, 30, 90, 200, $alpha === true ? 64 : 0 ) );
	imageinterlace( $image, $progressive );
	ob_start();
	$kind === 'png' ? imagepng( $image ) : imagejpeg( $image, null, 80 );

	return (string) ob_get_clean();
};

// A request as the panel gets an upload: the file in $_FILES, the data posted
$upload = static function( string $kind, string $bytes ) use ( $post, $dir ): array {

	$file = $dir. '/upload.bin';
	file_put_contents( $file, $bytes );
	$_FILES['file'] = [ 'name' => 'x', 'type' => '', 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
	$answer = $post( 'pdf/layout/image', [ 'kind' => $kind ] );
	unset( $_FILES['file'] );

	return $answer;
};

$pages = static fn( string $pdf ): int => preg_match_all( '#/Type /Page\b#', $pdf );
$object = static fn( string $pdf, int $id ): string => preg_match( '/\n'. $id. " 0 obj\n(.*?)\nendobj\n/s", $pdf, $found ) === 1 ? $found[1] : '';
$pageObjects = static function( string $pdf ): array {
	preg_match_all( '/\n(\d+) 0 obj\n<< \/Type \/Page \/Parent[^\n]*\n/', $pdf, $found );
	return array_map( 'intval', $found[1] );
};
// The content stream of a page, inflated: the operators a layout wrote
$content = static function( string $pdf, int $page ) use ( $object, $pageObjects ): string {

	$dictionary = $object( $pdf, $pageObjects( $pdf )[$page] );
	preg_match( '#/Contents (\d+) 0 R#', $dictionary, $contents );
	$stream = $object( $pdf, (int) $contents[1] );
	$start = strpos( $stream, "stream\n" );

	return (string) gzuncompress( substr( $stream, $start + 7, (int) strrpos( $stream, "\nendstream" ) - $start - 7 ) );
};
$words = static function( string $pdf, string $options = '-layout' ) use ( $dir ): string {
	file_put_contents( $dir. '/out.pdf', $pdf );
	return (string) shell_exec( 'pdftotext '. $options. ' '. escapeshellarg( $dir. '/out.pdf' ). ' - 2>/dev/null' );
};
$pdftotext = trim( (string) @shell_exec( 'command -v pdftotext 2>/dev/null' ) ) !== '';
$sentence = static function( string $code, string $locale ) use ( &$appData ): string {
	return (string) ( \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' );
};

// --- the feature, its manifest, its activation --------------------------------

$manifest = \Nino\Features::manifest( dirname( __DIR__ ) );
check( 'the manifest validates without a warning', is_array( $manifest ) && ninoWarnings() === [] );
check( '...it names the data it owns, the extensions it needs, the kernel it is written for and a setting of tags that is a list of slugs', $manifest['data'] === [ '/data/pdf', '/data/pdf-fonts' ] && $manifest['php'] === [ 'ext' => [ 'gd', 'dom', 'iconv', 'zlib' ] ] && $manifest['nino'] === '^2.0'
	&& $manifest['requires'] === [] && $manifest['category'] === 'content' && isset( $manifest['components'], $manifest['stacks'], $manifest['assets'] ) === false && $manifest['settings']['tags']['type'] === 'lines' && count( $manifest['settings'] ) === 1
	&& $manifest['settings']['tags']['default'] === [] );
check( '...and the six sections of its manual, in both languages', array_keys( $manifest['manual'] ) === [ 'shortcodes', 'markup', 'routes', 'panel', 'callbacks', 'install' ] && \Nino\Features::manualSections( $manifest['manual'], 'de_DE' ) !== null
	&& count( array_filter( $manifest['manual']['panel'], static fn( array $line ): bool => isset( $line['en_US'], $line['de_DE'] ) ) ) === 18 );

check( '...the manual has the entries Design, Fonts, Font limits and Placeholders, in both languages, and the feature is version 2.0.0', isset( $manifest['manual']['panel']['Design']['en_US'], $manifest['manual']['panel']['Design']['de_DE'], $manifest['manual']['panel']['Placeholders']['en_US'], $manifest['manual']['panel']['Placeholders']['de_DE'] )
	&& isset( $manifest['manual']['panel']['Fonts']['de_DE'], $manifest['manual']['panel']['Font limits']['en_US'] ) === true && $manifest['version'] === '2.0.0' );

// A file system that folds case holds one of two names that differ by case alone: the tree of the feature has no such pair
$folded = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__ ), \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST ) as $entry )
	$folded[ strtolower( $entry->getPathname() ) ][] = $entry->getPathname();
check( 'no two files or folders of the feature have names that differ by the case of their letters alone, and the templates stand in templates/', array_filter( $folded, static fn( array $names ): bool => count( $names ) > 1 ) === []
	&& is_file( dirname( __DIR__ ). '/templates/panel.tpl' ) === false && is_file( dirname( __DIR__ ). '/templates/preview-sample.tpl' ) === true && is_file( dirname( __DIR__ ). '/templates/preview-slot.tpl' ) === true );

check( 'activation lists the class and records 2.0.0', \Nino\Features::activate( $appData, 'pdf' ) === true
	&& in_array( '\\Nino\\Modules\\Pdf', $appData['/nino/modules'], true ) === true && \Nino\Features::get( $appData, 'pdf' )['installed'] === '2.0.0' );
check( '...and copies nothing: the feature has no unit', glob( \Nino\Filesystem::path( $appData, '/templates' ). '/*' ) === [] || is_dir( \Nino\Filesystem::path( $appData, '/templates' ) ) === false );

\Nino\Html::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
ninoUser( $appData, 'dev@example.com' );
$panels = \Nino\Admin\Admin::panels( $appData );
check( 'the panel is in the registry: its own screen in one mount, three tabs of its own after it - pictures, text blocks, templates - and the Settings tab the kernel adds', isset( $panels['pdf'] ) === true
	&& array_keys( $panels['pdf']['tabs'] ) === [ 'pdf-pictures', 'pdf-blocks', 'pdf-templates', 'pdf-settings' ] && $panels['pdf']['panes'] === [ 'pdf-layout' ] && $panels['pdf']['template'] === '' && $panels['pdf']['group'] === 'features'
	&& $panels['pdf']['tabs']['pdf-pictures']['perm'] === $panels['pdf']['perm'] && $panels['pdf']['tabs']['pdf-blocks']['perm'] === $panels['pdf']['perm'] && $panels['pdf']['tabs']['pdf-templates']['perm'] === $panels['pdf']['perm'] && $panels['pdf']['perm'] === Pdf::PERM );
check( '...and a feature that has not been given tags lists none: the tags are the words of a project, and the feature brings no list of its own', Pdf::tags( $appData ) === [] );

\Nino\Features::saveSettings( $appData, 'pdf', [ 'tags' => "offer\nBad Tag\ninvoice\nx\nthis-tag-is-longer-than-forty-characters-by-far" ] );
check( 'tags() lists the lines of the setting that are slugs of 2 to 40 characters: the kernel holds a list of lines to their length alone', Pdf::tags( $appData ) === [ 'offer', 'invoice' ] );
\Nino\Features::saveSettings( $appData, 'pdf', [ 'tags' => "offer\ninvoice\ncontract\nprivacy\ninformation" ] );
check( '...and the setting as the Settings tab saves it is the list the panel offers', Pdf::tags( $appData ) === [ 'offer', 'invoice', 'contract', 'privacy', 'information' ] );

// --- the tile, the log, the permission -----------------------------------------

check( 'the Dashboard tile counts the templates', \Nino\Modules\Pdf\Admin::summary( $appData )['value'] === 0 );

\Nino\Auth::insertUser( $appData, 'viewer@example.com', 'correct horse battery staple', [] );
\Nino\Auth::loginUser( $appData, 'viewer@example.com', 'correct horse battery staple' );
$denied = [];
foreach( array_keys( \Nino\Modules\Pdf\Admin::actions() ) as $action )
	if( $post( $action )[0] !== 403 )
		$denied[] = $action;
check( 'every panel action is 403 for an account without the permission'. ( $denied === [] ? '' : ' - '. implode( ', ', $denied ) ), $denied === [] && count( \Nino\Modules\Pdf\Admin::actions() ) === 14 );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

// --- the layout -----------------------------------------------------------------

check( 'layout() answers the defaults before anything is saved, and layout/get the same with no pictures', Pdf::layout( $appData ) === Pdf::DEFAULTS
	&& $post( 'pdf/layout/get' )[1]['layout'] === Pdf::DEFAULTS && $post( 'pdf/layout/get' )[1]['images'] === [ 'logo' => '', 'signature' => '' ] );

$layout = [ 'format' => 'letter', 'margins' => [ 15, 16, 17, 18 ], 'font' => 'times', 'size' => 11, 'header' => '<p align="right">Head [[page]]</p>', 'footer' => '<p>Page [[page]] / [[pages]]</p>' ];
[ $status, $body ] = $post( 'pdf/layout/save', $layout );
check( 'layout/save persists what it is given, answers the layout, and a fresh read has it', $status === 200 && $body['layout'] === $layout + [ 'design' => '', 'logo' => '', 'signature' => '' ]
	&& Pdf::layout( $appData ) === $layout + [ 'design' => '', 'logo' => '', 'signature' => '' ] && is_file( \Nino\Filesystem::path( $appData, Pdf::LAYOUT ) ) === true );

[ $status, $body ] = $post( 'pdf/layout/save', [ 'header' => '<p align="right" onclick="x()">Head [[page]]</p><script>alert(1)</script>', 'footer' => '<div class="f" style="color:red"><p>Page [[page]] / [[pages]]</p></div><style>p{}</style>' ] + $layout );
check( 'layout/save holds the header and the footer to the list of what a document may hold: the stored texts - and the answer - have no script, no style, no attribute the layout does not read', $status === 200
	&& Pdf::layout( $appData )['header'] === '<p align="right">Head [[page]]</p>' && Pdf::layout( $appData )['footer'] === '<div><p>Page [[page]] / [[pages]]</p></div>'
	&& $body['layout']['header'] === Pdf::layout( $appData )['header'] && $body['layout']['footer'] === Pdf::layout( $appData )['footer'] );
$post( 'pdf/layout/save', $layout );
[ $status, $body ] = $post( 'pdf/layout/save', [ 'header' => str_repeat( '&', 3000 ) ] + $layout );
check( '...a header of 3000 characters that is 15000 once its ampersands are written is refused as too long, as the layout would not read it back: nothing is written', $status === 400 && $body['code'] === 'pdf_band_long' && $body['field'] === 'header'
	&& Pdf::layout( $appData ) === $layout + [ 'design' => '', 'logo' => '', 'signature' => '' ] );

foreach( [
	'a margin of 60'			=> [ [ 'margins' => [ 60, 20, 20, 20 ] ], 'pdf_margin', 'margins', [ 5, 50 ] ],
	'a margin that is no number'	=> [ [ 'margins' => [ 'x', 20, 20, 20 ] ], 'pdf_margin', 'margins', [ 5, 50 ] ],
	'three margins'				=> [ [ 'margins' => [ 20, 20, 20 ] ], 'pdf_margin', 'margins', [ 5, 50 ] ],
	'a size of 15'				=> [ [ 'size' => 15 ], 'pdf_size', 'size', [ 8, 14 ] ],
	'a paper that is none'		=> [ [ 'format' => 'a3' ], 'pdf_format', 'format', [] ],
	'a font that is none'		=> [ [ 'font' => 'comic' ], 'pdf_font', 'font', [] ],
	'a header of 4001 characters'	=> [ [ 'header' => str_repeat( 'x', 4001 ) ], 'pdf_band_long', 'header', [ 4000 ] ],
] as $label => [ $change, $code, $field, $params ] ) {
	[ $status, $body ] = $post( 'pdf/layout/save', $change + $layout );
	check( "layout/save refuses $label with 400, the code, the field and the params", $status === 400 && $body['code'] === $code && $body['field'] === $field && ( $body['params'] ?? [] ) === $params
		&& Pdf::layout( $appData ) === $layout + [ 'design' => '', 'logo' => '', 'signature' => '' ] );
}

$logo = $picture( 'png' );
[ $status, $body ] = $upload( 'logo', $logo );
check( 'layout/image with a PNG stores a file the layout names, and answers it as a picture', $status === 200 && $body['layout']['logo'] === 'logo.png'
	&& is_file( \Nino\Filesystem::path( $appData, Pdf::IMAGES. '/logo.png' ) ) === true && str_starts_with( $body['images']['logo'], 'data:image/png;base64,' ) === true && Pdf::picture( $appData, 'logo' ) === $logo );
[ $status, $body ] = $upload( 'logo', $picture( 'jpeg' ) );
check( '...a second upload replaces it: the layout names the new file and one file is left', $status === 200 && $body['layout']['logo'] === 'logo.jpg'
	&& array_map( 'basename', glob( \Nino\Filesystem::path( $appData, Pdf::IMAGES ). '/logo.*' ) ) === [ 'logo.jpg' ] );
check( '...and the layout form saved after it keeps the picture', $post( 'pdf/layout/save', $layout )[1]['layout']['logo'] === 'logo.jpg' );

[ $status, $body ] = $upload( 'logo', "this is no picture\n" );
check( '...a text file is 400 with the code of the kernel, and the layout stays as it was', $status === 400 && $body['code'] === 'image_type' && Pdf::layout( $appData )['logo'] === 'logo.jpg' );
[ $status, $body ] = $upload( 'logo', $picture( 'png', 4001, 2 ) );
check( '...a picture wider than 4000 pixels is 400 with its own code and the limit', $status === 400 && $body['code'] === 'pdf_image_pixels' && $body['params'] === [ 4000 ] );
[ $status, $body ] = $upload( 'badge', $logo );
check( '...a picture that is neither logo nor signature is 400', $status === 400 && $body['code'] === 'pdf_image_kind' );
$_FILES['file'] = [ 'name' => 'x', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0, 'type' => '' ];
[ $status, $body ] = $post( 'pdf/layout/image', [ 'kind' => 'logo' ] );
unset( $_FILES['file'] );
check( '...and no file at all is 400 with the kernel\'s code', $status === 400 && $body['code'] === 'upload_missing' );

$noise = imagecreatetruecolor( 700, 700 );
for( $y = 0; $y < 700; $y++ )
	for( $x = 0; $x < 700; $x++ )
		imagesetpixel( $noise, $x, $y, crc32( $x. ':'. $y ) & 0xFFFFFF );
ob_start();
imagepng( $noise );
$big = (string) ob_get_clean();
$upload( 'logo', $big );
$answered = $post( 'pdf/layout/get' )[1];
check( 'a picture of more than a megabyte is kept and named by the layout but not sent along with it: the screen shows its name', strlen( $big ) > 1048576 && $answered['layout']['logo'] === 'logo.png' && $answered['images']['logo'] === '' );
$upload( 'logo', $picture( 'jpeg' ) );

$signature = $picture( 'png', 60, 20, true );
$upload( 'signature', $signature );
check( 'the signature is a picture of its own, beside the logo', Pdf::layout( $appData )['signature'] === 'signature.png' && Pdf::layout( $appData )['logo'] === 'logo.jpg' );
[ $status, $body ] = $post( 'pdf/layout/image/delete', [ 'kind' => 'logo' ] );
check( 'layout/image/delete takes the logo away - from the layout and from the disk - and leaves the signature', $status === 200 && $body['layout']['logo'] === '' && $body['layout']['signature'] === 'signature.png'
	&& glob( \Nino\Filesystem::path( $appData, Pdf::IMAGES ). '/logo.*' ) === [] );

// A picture is put in place once the layout names it: a layout that cannot be written leaves the pictures as they were
$images = static fn(): array => array_map( 'basename', glob( \Nino\Filesystem::path( $appData, Pdf::IMAGES ). '/*' ) ?: [] );
$upload( 'logo', $logo );
$layoutFile = \Nino\Filesystem::path( $appData, Pdf::LAYOUT );
$layoutKept = (string) file_get_contents( $layoutFile );
unlink( $layoutFile );
mkdir( $layoutFile );
touch( $layoutFile. '/x' );
ninoWarnings();
$replacement = $picture( 'png', 80, 40 );
$replaced = $upload( 'logo', $replacement );
$added = $upload( 'logo', $picture( 'jpeg' ) );
ninoWarnings();
unlink( $layoutFile. '/x' );
rmdir( $layoutFile );
file_put_contents( $layoutFile, $layoutKept );
check( 'a layout that cannot be written refuses a picture with 500: the picture of the same name is not replaced, a picture of another name is not added, and nothing of either upload is left in the directory', $replaced[0] === 500 && $added[0] === 500
	&& file_get_contents( \Nino\Filesystem::path( $appData, Pdf::IMAGES. '/logo.png' ) ) === $logo && $images() === [ 'logo.png', 'signature.png' ] && Pdf::layout( $appData )['logo'] === 'logo.png' );
$replaced = $upload( 'logo', $replacement );
check( '...and the same upload once the layout can be written puts the new picture in the place of the old, and leaves nothing else', $replaced[0] === 200 && $replaced[1]['layout']['logo'] === 'logo.png' && Pdf::picture( $appData, 'logo' ) === $replacement && $images() === [ 'logo.png', 'signature.png' ] );
$post( 'pdf/layout/image/delete', [ 'kind' => 'logo' ] );

// --- the text blocks --------------------------------------------------------------

[ $status, $body ] = $post( 'pdf/blocks/save', [ 'key' => 'intro', 'isNew' => true, 'title' => 'Intro', 'html' => '<p>Hello [[name]] <strong>bold</strong></p><script>alert(1)</script>' ] );
check( 'blocks/save sanitizes: the script tag is gone after a fresh read, the paragraph and the strong stay', $status === 200 && str_contains( Pdf::blocks( $appData )['intro']['html'], '<script' ) === false
	&& str_contains( Pdf::blocks( $appData )['intro']['html'], '<p>Hello [[name]] <strong>bold</strong></p>' ) === true && $body['blocks'][0]['key'] === 'intro' );
check( '...and blocks/list answers the library', $post( 'pdf/blocks/list' )[1]['blocks'] === [ [ 'key' => 'intro', 'title' => 'Intro', 'html' => Pdf::blocks( $appData )['intro']['html'] ] ] );

foreach( [
	'a key taken'					=> [ [ 'key' => 'intro', 'isNew' => true, 'title' => 'Again', 'html' => '' ], 400, 'pdf_key_taken', 'key' ],
	'a key that is no slug'		=> [ [ 'key' => 'No Slug', 'isNew' => true, 'title' => 'x', 'html' => '' ], 400, 'pdf_key', 'key' ],
	'a title that is empty'		=> [ [ 'key' => 'empty', 'isNew' => true, 'title' => ' ', 'html' => '' ], 400, 'pdf_title', 'title' ],
	'a text of 20001 characters'	=> [ [ 'key' => 'long', 'isNew' => true, 'title' => 'x', 'html' => str_repeat( 'x', 20001 ) ], 400, 'pdf_html_long', 'html' ],
	'a block that is not there'	=> [ [ 'key' => 'gone', 'isNew' => false, 'title' => 'x', 'html' => '' ], 404, 'pdf_block_missing', '' ],
] as $label => [ $data, $expected, $code, $field ] ) {
	[ $status, $body ] = $post( 'pdf/blocks/save', $data );
	check( "blocks/save refuses $label with $expected, the code and the field", $status === $expected && $body['code'] === $code && ( $body['field'] ?? '' ) === $field && array_keys( Pdf::blocks( $appData ) ) === [ 'intro' ] );
}

[ $status, $body ] = $post( 'pdf/blocks/save', [ 'key' => 'intro', 'isNew' => false, 'title' => 'Intro two', 'html' => '<p>Changed</p>' ] );
check( 'blocks/save replaces a block that is there, and keeps the order of the rest', $status === 200 && Pdf::blocks( $appData )['intro'] === [ 'title' => 'Intro two', 'html' => '<p>Changed</p>' ] );
$post( 'pdf/blocks/save', [ 'key' => 'intro', 'isNew' => false, 'title' => 'Intro', 'html' => '<p>Hello [[name]] <strong>bold</strong></p>' ] );
$post( 'pdf/blocks/save', [ 'key' => 'spare', 'isNew' => true, 'title' => 'Spare', 'html' => '<p>Spare</p>' ] );

// --- the HTML a document may hold -------------------------------------------------------

// What the layout understands is kept, in the shape it reads it; the rest is unwrapped or goes
$rich = '<h1 class="big" onclick="x()">Title</h1><table border="1" class="t"><thead><tr><th width="30%" id="a">Item</th><th>Price</th></tr></thead><tbody><tr><td style="color:red;text-align:right" onclick="y()">1</td><td align="Center">2</td></tr></tbody></table>'
	. '<hr class="r"><p><img src="pdf:logo" width="20" alt="x"><img src="https://example.com/a.png"><a href="javascript:alert(1)" target="_blank">link</a> <span class="s">span</span><u>under</u></p><style>p{color:red}</style><script>alert(1)</script>';
$kept = '<h1>Title</h1><table border="1"><thead><tr><th width="30%">Item</th><th>Price</th></tr></thead><tbody><tr><td style="text-align:right">1</td><td align="center">2</td></tr></tbody></table><hr><p><img src="pdf:logo" width="20"><a>link</a> <span>span</span>under</p>';
[ $status, $body ] = $post( 'pdf/blocks/save', [ 'key' => 'rich', 'isNew' => true, 'title' => 'Rich', 'html' => $rich ] );
$stored = Pdf::blocks( $appData )['rich']['html'] ?? '';
check( 'a text block keeps what the layout draws, after the save and in a fresh read: a heading, a table with its border and the width of a th, a rule, the logo with its width, a link as its text underlined by the engine, a span', $status === 200
	&& str_contains( $stored, '<h1>Title</h1>' ) === true && str_contains( $stored, '<table border="1">' ) === true && str_contains( $stored, '<th width="30%">Item</th>' ) === true && str_contains( $stored, '<hr>' ) === true
	&& str_contains( $stored, '<img src="pdf:logo" width="20">' ) === true && str_contains( $stored, '<a>link</a>' ) === true && str_contains( $stored, '<span>span</span>' ) === true );
check( '...a style is reduced to its text-align, an align is written in lower case, and no other attribute stays: onclick, class, id, alt, target and the colour are gone', str_contains( $stored, '<td style="text-align:right">' ) === true && str_contains( $stored, '<td align="center">' ) === true
	&& preg_match( '/onclick|class=|id=|alt=|target=|color/', $stored ) === 0 );
check( '...an img that is not the logo goes, a link is its text with no address, a tag that is not listed is unwrapped to its text, and a script and a style go with what is in them', str_contains( $stored, 'example.com' ) === false && str_contains( $stored, 'javascript' ) === false
	&& str_contains( $stored, '<u>' ) === false && str_contains( $stored, 'under' ) === true && preg_match( '/<script|<style|alert|color:red/', $stored ) === 0 );
check( '...all of it is the text the answer carries, it is written once and for all - a second pass over it changes nothing - and it is what the list in the library holds', $stored === $kept && array_column( $body['blocks'], 'html', 'key' )['rich'] === $kept && Allow::html( $stored ) === $stored
	&& array_column( $post( 'pdf/blocks/list' )[1]['blocks'], 'html', 'key' )['rich'] === $kept );
check( '...the list is the one decided: 24 tags, and the attributes each may keep', array_keys( Allow::TAGS ) === [ 'h1', 'h2', 'h3', 'p', 'div', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'a', 'hr', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'img', 'span' ]
	&& Allow::TAGS['th'] === [ 'width', 'align', 'style' ] && Allow::TAGS['img'] === [ 'src', 'width' ] && Allow::TAGS['table'] === [ 'border' ] && Allow::TAGS['a'] === [] );
check( '...an attribute stays on the tags that read it only, in the shape they read it: a width in percent on a cell and in millimetres on the logo, an align on a text and a cell, a border that is a number', Allow::html( '<p width="5%" border="2" align="right">a</p><td width="25%" border="1" align="left">b</td><td width="25">c</td><img src="PDF:LOGO" width="12.5 MM" align="right"><table border="thick"><tr><td>d</td></tr></table>' )
	=== '<p align="right">a</p><td width="25%" align="left">b</td><td>c</td><img src="pdf:logo" width="12.5mm"><table><tr><td>d</td></tr></table>' );
check( '...the last text-align that is valid is the one, as a cascade has it', Allow::html( '<p style="text-align:left;TEXT-ALIGN : Center !important;text-align:banana;x-text-align:right">a</p><p style="color:red">b</p>' ) === '<p style="text-align:center">a</p><p>b</p>' );
check( '...text is escaped as it is written - an ampersand, the characters of a tag, a no-break space - and an o umlaut and the euro sign stay as they are', Allow::html( "<p>a & b &lt;i&gt; \u{A0}ö€</p>" ) === "<p>a &amp; b &lt;i&gt; &nbsp;ö€</p>"
	&& Allow::html( '   ' ) === '' && Allow::html( '<script>x</script>' ) === '' && Allow::html( "<p>bad \xC3\x28 byte</p>" ) === "<p>bad \u{FFFD}( byte</p>" );

[ $status, $body ] = $post( 'pdf/blocks/save', [ 'key' => 'rich', 'isNew' => false, 'title' => 'Rich', 'html' => str_repeat( '&', 7000 ) ] );
check( '...a text that is 35000 characters once it is written, from 7000 that were typed, is refused with the limit, as the answer for 20001 is, and the block stays as it was', $status === 400 && $body['code'] === 'pdf_html_long' && $body['params'] === [ 20000 ] && $body['field'] === 'html' && Pdf::blocks( $appData )['rich']['html'] === $kept );
$post( 'pdf/blocks/delete', [ 'key' => 'rich' ] );

// --- the templates -----------------------------------------------------------------

$blocks = [
	[ 'type' => 'html', 'block' => 'intro' ],
	[ 'type' => 'html', 'html' => '<p>Own text with ö and €</p><script>x</script>' ],
	[ 'type' => 'slot', 'name' => 'table' ],
	[ 'type' => 'input', 'name' => 'note', 'label' => 'A note', 'multiline' => true, 'required' => true ],
	[ 'type' => 'pagebreak' ],
	[ 'type' => 'signature', 'name' => 'seller', 'party' => 'owner', 'label' => 'The seller', 'page' => 'last', 'x' => 20, 'y' => 200.04, 'w' => 60, 'h' => 20, 'line' => true ],
	[ 'type' => 'signature', 'name' => 'buyer', 'party' => 'member', 'label' => 'The buyer', 'page' => '1', 'x' => 110.5, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => false ],
];
[ $status, $body ] = $post( 'pdf/templates/save', [ 'key' => 'offer-one', 'isNew' => true, 'name' => 'Offer one', 'tag' => 'offer', 'blocks' => $blocks ] );
$read = Pdf::template( $appData, 'offer-one' );
check( 'templates/save with every type of block persists, and a fresh read has the shape of the table: one of block or html, numbers with one decimal, a page as a number or last', $status === 200 && $body['key'] === 'offer-one' && $read !== null
	&& array_column( $read['blocks'], 'type' ) === [ 'html', 'html', 'slot', 'input', 'pagebreak', 'signature', 'signature' ]
	&& $read['blocks'][0] === [ 'type' => 'html', 'block' => 'intro' ] && array_keys( $read['blocks'][1] ) === [ 'type', 'html' ] && str_contains( $read['blocks'][1]['html'], '<script' ) === false
	&& $read['blocks'][2] === [ 'type' => 'slot', 'name' => 'table' ] && $read['blocks'][3] === [ 'type' => 'input', 'name' => 'note', 'label' => 'A note', 'multiline' => true, 'required' => true ]
	&& $read['blocks'][5] === [ 'type' => 'signature', 'name' => 'seller', 'party' => 'owner', 'label' => 'The seller', 'page' => 'last', 'x' => 20.0, 'y' => 200.0, 'w' => 60.0, 'h' => 20.0, 'line' => true ]
	&& $read['blocks'][6]['page'] === 1 && $read['blocks'][6]['x'] === 110.5 && $read['blocks'][6]['line'] === false );

$bad = static fn( array $change ): array => $post( 'pdf/templates/save', $change + [ 'key' => 'other', 'isNew' => true, 'name' => 'Other', 'tag' => 'invoice', 'blocks' => [] ] );
$box = static fn( array $change ): array => [ [ 'type' => 'signature', 'name' => 'seller', 'party' => 'owner', 'label' => '', 'page' => 'last', 'x' => 20, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => true ] ];
foreach( [
	'a signature box over the right edge of the page'	=> [ [ 'blocks' => [ [ 'x' => 160 ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature box under the foot of the page'			=> [ [ 'blocks' => [ [ 'y' => 290 ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature box that is no number'					=> [ [ 'blocks' => [ [ 'w' => 'wide' ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature box at a negative x'					=> [ [ 'blocks' => [ [ 'x' => -1 ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature box above the page'					=> [ [ 'blocks' => [ [ 'y' => -0.5 ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature box of no width'						=> [ [ 'blocks' => [ [ 'w' => 0 ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature box of no height'						=> [ [ 'blocks' => [ [ 'h' => 0 ] + $box( [] )[0] ] ], 400, 'pdf_signature_box' ],
	'a signature on page 0'								=> [ [ 'blocks' => [ [ 'page' => 0 ] + $box( [] )[0] ] ], 400, 'pdf_signature_page' ],
	'a signature of neither party'						=> [ [ 'blocks' => [ [ 'party' => 'guest' ] + $box( [] )[0] ] ], 400, 'pdf_signature_party' ],
	'a duplicate key'									=> [ [ 'key' => 'offer-one' ], 400, 'pdf_key_taken' ],
	'a key that is no slug'								=> [ [ 'key' => 'Bad_Key' ], 400, 'pdf_key' ],
	'a tag the project does not have'					=> [ [ 'tag' => 'bogus' ], 400, 'pdf_tag' ],
	'a block of the library that is not there'			=> [ [ 'blocks' => [ [ 'type' => 'html', 'block' => 'nowhere' ] ] ], 400, 'pdf_block_ref' ],
	'an html block with both a reference and a text'	=> [ [ 'blocks' => [ [ 'type' => 'html', 'block' => 'intro', 'html' => '<p>x</p>' ] ] ], 400, 'pdf_block_html' ],
	'a block of a type that is none'					=> [ [ 'blocks' => [ [ 'type' => 'image' ] ] ], 400, 'pdf_block_type' ],
	'a slot with a name that is no slug'				=> [ [ 'blocks' => [ [ 'type' => 'slot', 'name' => 'A B' ] ] ], 400, 'pdf_block_name' ],
	'an input label of 121 characters'					=> [ [ 'blocks' => [ [ 'type' => 'input', 'name' => 'note', 'label' => str_repeat( 'x', 121 ) ] ] ], 400, 'pdf_block_label' ],
	'61 blocks'											=> [ [ 'blocks' => array_fill( 0, 61, [ 'type' => 'pagebreak' ] ) ], 400, 'pdf_blocks_many' ],
	'a name that is empty'								=> [ [ 'name' => '' ], 400, 'pdf_title' ],
	'a template that is not there'						=> [ [ 'key' => 'nowhere', 'isNew' => false ], 404, 'pdf_template_missing' ],
] as $label => [ $change, $expected, $code ] ) {
	[ $status, $body ] = $bad( $change );
	check( "templates/save refuses $label with $expected and the code $code, and writes nothing", $status === $expected && $body['code'] === $code && array_keys( Pdf::templates( $appData ) ) === [ 'offer-one' ] );
}

// Every code the sources answer a person with has its sentence in both languages, with a place for each param it carries
$problems = [];
$codes = [];
foreach( [ 'Store/Store.php', 'Admin/Admin.php' ] as $source )
	preg_match_all( "/'(pdf_[a-z_]+)'/", (string) file_get_contents( dirname( __DIR__ ). '/'. $source ), $found ) === false || $codes = array_merge( $codes, $found[1] );
$codes = array_unique( $codes );
foreach( $codes as $code )
	foreach( [ 'en_US', 'de_DE' ] as $locale )
		if( $sentence( $code, $locale ) === '' )
			$problems[] = $locale. ' '. $code;
check( 'every code the panel answers with has its sentence in English and in German ('. count( $codes ). ' codes)'. ( $problems === [] ? '' : ' - '. implode( ', ', $problems ) ), $problems === [] && count( $codes ) > 20 );
$english = array_keys( \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', 'en_US' ) );
$german = array_keys( \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', 'de_DE' ) );
sort( $english );
sort( $german );
check( 'the two text files carry the same keys', $english === $german && count( $english ) > 100 );

// The words of the two directories: each key the script asks for with w('...') is in both files, and the words a card shows - the select of the sort, the box that chooses, the count
// in a question - are said in German too and are not the English ones
$asked = [];
preg_match_all( "/\bw\(\s*'([a-z][a-z0-9\/-]*)'\s*\)/", (string) file_get_contents( dirname( __DIR__ ). '/assets/admin.js' ), $wordsAsked );
$asked = array_unique( $wordsAsked[1] );
$unknown = array_filter( $asked, static fn( string $key ): bool => in_array( '[[/_admin/pdf/'. $key. ']]', $english, true ) === false );
$cardWords = [ 'label/sort-by', 'label/sort-none', 'label/sort-up', 'label/sort-down', 'label/select-blocks', 'label/select-templates', 'empty/nomatch', 'confirm/block-many', 'confirm/block-many-title', 'confirm/template-many', 'confirm/template-many-title' ];
$same = [];
foreach( $cardWords as $key ) {
	$en = \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', 'en_US' )[ '[[/_admin/pdf/'. $key. ']]' ] ?? '';
	$de = \Nino\Admin\Admin::textFills( $appData, '/features/Pdf/text', 'de_DE' )[ '[[/_admin/pdf/'. $key. ']]' ] ?? '';

	if( $en === '' || $de === '' || $en === $de )
		$same[] = $key;
}
check( 'every word the script of the panel asks for is in the text files ('. count( $asked ). ' of them), and the four words of the sort select of a card, the box that chooses, "nothing found" and the questions that name a count are different words in German'. ( $unknown === [] && $same === [] ? '' : ' - missing: '. implode( ', ', $unknown ). ' same: '. implode( ', ', $same ) ),
	$unknown === [] && $same === [] && count( $asked ) > 60 );

$before = array_keys( Pdf::templates( $appData ) );
[ $status, $body ] = $post( 'pdf/templates/duplicate', [ 'key' => 'offer-one', 'name' => 'Offer one (copy)' ] );
check( 'templates/duplicate makes a second file under a free key, with the name it is given and the blocks of the original', $status === 200 && $body['key'] === 'offer-one-copy'
	&& array_keys( Pdf::templates( $appData ) ) === [ 'offer-one', 'offer-one-copy' ] && Pdf::templates( $appData )['offer-one-copy']['name'] === 'Offer one (copy)'
	&& Pdf::template( $appData, 'offer-one-copy' )['blocks'] === Pdf::template( $appData, 'offer-one' )['blocks'] && count( glob( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES ). '/*.php' ) ) === 2 );
foreach( [ 'no name' => [], 'an empty name' => [ 'name' => '' ], 'a name of blanks' => [ 'name' => " \t " ] ] as $label => $extra ) {
	[ $status, $body ] = $post( 'pdf/templates/duplicate', [ 'key' => 'offer-one' ] + $extra );
	check( "...$label is 400 with the code of a name, its limit and its field, and writes nothing: the server has no word of its own to name a copy by", $status === 400 && $body['code'] === 'pdf_title' && $body['field'] === 'name' && $body['params'] === [ 120 ]
		&& array_keys( Pdf::templates( $appData ) ) === [ 'offer-one', 'offer-one-copy' ] && count( glob( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES ). '/*.php' ) ) === 2 );
}
$second = $post( 'pdf/templates/duplicate', [ 'key' => 'offer-one', 'name' => '  Second copy ' ] );
check( '...and a second copy takes the next key, with the name it is given, trimmed', $second[1]['key'] === 'offer-one-copy-2' && Pdf::templates( $appData )['offer-one-copy-2']['name'] === 'Second copy' );
check( '...a duplicate of a template that is not there is 404, whatever name it is given or not', $post( 'pdf/templates/duplicate', [ 'key' => 'nowhere' ] )[0] === 404 && $post( 'pdf/templates/duplicate', [ 'key' => 'nowhere', 'name' => 'Copy' ] )[0] === 404 );
$post( 'pdf/templates/delete', [ 'key' => 'offer-one-copy-2' ] );

$post( 'pdf/templates/save', [ 'key' => 'invoice-a', 'isNew' => true, 'name' => 'Invoice A', 'tag' => 'invoice', 'blocks' => [] ] );
$post( 'pdf/templates/save', [ 'key' => 'invoice-b', 'isNew' => true, 'name' => 'apple invoice', 'tag' => 'invoice', 'blocks' => [] ] );
check( 'templates() lists all, sorted by name; templates( "invoice" ) only those of that tag; a tag nobody has lists none', array_keys( Pdf::templates( $appData ) ) === [ 'invoice-b', 'invoice-a', 'offer-one', 'offer-one-copy' ]
	&& array_keys( Pdf::templates( $appData, 'invoice' ) ) === [ 'invoice-b', 'invoice-a' ] && array_diff_key( Pdf::templates( $appData, 'invoice' )['invoice-a'], [ 'changed' => 0 ] ) === [ 'name' => 'Invoice A', 'tag' => 'invoice' ] && Pdf::templates( $appData, 'contract' ) === [] );
$listed = $post( 'pdf/templates/list' )[1];
check( 'templates/list answers key, name, tag and the number of blocks of each, with the tags, the library and the paper for the editor', array_column( $listed['templates'], 'blocks', 'key' ) === [ 'invoice-b' => 0, 'invoice-a' => 0, 'offer-one' => 7, 'offer-one-copy' => 7 ]
	&& $listed['tags'] === Pdf::tags( $appData ) && array_column( $listed['library'], 'key' ) === [ 'intro', 'spare' ] && $listed['paper'] === [ 215.9, 279.4 ] );
$got = $post( 'pdf/templates/get', [ 'key' => 'offer-one' ] );
check( 'templates/get answers one template as the file holds it; an unknown one is 404 with its code', $got[0] === 200 && $got[1]['template'] === Pdf::template( $appData, 'offer-one' ) && $got[1]['key'] === 'offer-one'
	&& $post( 'pdf/templates/get', [ 'key' => 'nowhere' ] )[0] === 404 && $post( 'pdf/templates/get', [ 'key' => 'nowhere' ] )[1]['code'] === 'pdf_template_missing' && $post( 'pdf/templates/get', [ 'key' => '../x' ] )[0] === 404 );

[ $status, $body ] = $post( 'pdf/blocks/delete', [ 'key' => 'intro' ] );
check( 'blocks/delete of a block a template references is 409 with the code and the names of the templates, and the block stays', $status === 409 && $body['code'] === 'pdf_block_used'
	&& str_contains( $body['params'][0], 'Offer one' ) === true && isset( Pdf::blocks( $appData )['intro'] ) === true );
check( '...a block no template uses is deleted, an unknown one is 404', $post( 'pdf/blocks/delete', [ 'key' => 'spare' ] )[0] === 200 && isset( Pdf::blocks( $appData )['spare'] ) === false && $post( 'pdf/blocks/delete', [ 'key' => 'spare' ] )[0] === 404 );

// --- fields() and inputs() ----------------------------------------------------------

$fields = Pdf::fields( $appData, 'offer-one' );
check( 'fields() answers the signature blocks in order, every key of the API', array_column( $fields, 'name' ) === [ 'seller', 'buyer' ]
	&& array_keys( $fields[0] ) === [ 'name', 'party', 'label', 'page', 'x', 'y', 'w', 'h', 'line' ] && $fields[0]['page'] === 'last' && $fields[1]['page'] === 1 && $fields[1]['x'] === 110.5 );
check( 'inputs() answers the input blocks in order, every key of the API', Pdf::inputs( $appData, 'offer-one' ) === [ [ 'name' => 'note', 'label' => 'A note', 'multiline' => true, 'required' => true ] ]
	&& Pdf::inputs( $appData, 'invoice-a' ) === [] && Pdf::fields( $appData, 'nowhere' ) === [] && Pdf::inputs( $appData, 'nowhere' ) === [] );

// --- render() -----------------------------------------------------------------------

$data = [ 'tokens' => [ 'name' => 'Ada', 'client' => [ 'city' => 'Köln' ] ], 'slots' => [ 'table' => '<table><thead><tr><th>Item</th><th>Price</th></tr></thead><tbody><tr><td>Cell one</td><td>Cell two</td></tr></tbody></table>' ], 'inputs' => [ 'note' => "first line\nsecond line" ] ];
$pdf = Pdf::render( $appData, 'offer-one', $data );
check( 'render() answers bytes that start with %PDF-1.4 and end with %%EOF, with no trailer id and no date', is_string( $pdf ) === true && str_starts_with( $pdf, '%PDF-1.4' ) === true && str_ends_with( $pdf, '%%EOF' ) === true
	&& str_contains( $pdf, '/ID' ) === false && str_contains( $pdf, '/CreationDate' ) === false && str_contains( $pdf, '/ModDate' ) === false );
check( '...the xref table points at every object, and startxref at the table', (function() use ( $pdf ): bool {
	preg_match( '/startxref\n(\d+)\n%%EOF$/', $pdf, $start );
	preg_match( '/^xref\n0 (\d+)\n/', substr( $pdf, (int) $start[1] ), $count );
	$entries = substr( $pdf, (int) $start[1] + strlen( $count[0] ), 20 * (int) $count[1] );
	$ok = strlen( $entries ) === 20 * (int) $count[1];
	for( $id = 1; $id < (int) $count[1]; $id++ )
		$ok = $ok && (int) substr( $entries, 20 * $id, 10 ) === strpos( $pdf, "\n". $id. " 0 obj\n" ) + 1;
	return $ok;
})() );
check( '...the /Producer is the framework and the feature with no version - nothing after the name - and there is no title without the option', str_contains( $pdf, '/Producer (Nino PDF)' ) === true && str_contains( $pdf, '/Title' ) === false );
$before = (string) Pdf::render( $appData, 'invoice-a' );
// An update of the feature is a manifest that says another version: the registry's reading of it - cached for the request - is made to
$manifestVersion = \Nino\Features::get( $appData, 'pdf' )['version'];
$appData['./nino/features/all']['pdf']['version'] = '9.9.9';
check( '...an update of the feature changes no byte: the same render after the manifest of the feature says another version is the same bytes, so that a document kept with its hash can be made again', \Nino\Features::get( $appData, 'pdf' )['version'] === '9.9.9' && $manifestVersion !== '9.9.9' && $before !== '' && (string) Pdf::render( $appData, 'invoice-a' ) === $before );
unset( $appData['./nino/features/all'] );
check( '...the paper is the layout\'s: US Letter in points', str_contains( $pdf, '/MediaBox [ 0 0 612 792 ]' ) === true );
check( '...two pages: the page break adds one', $pages( $pdf ) === 2 && $pages( (string) Pdf::render( $appData, 'invoice-a' ) ) === 1 );
check( '...two renders of one template with the same arguments are the same bytes, with a picture in it as well', $pdf === Pdf::render( $appData, 'offer-one', $data )
	&& Pdf::render( $appData, 'offer-one', $data, [ 'signatures' => [ 'buyer' => $signature ], 'title' => 'T', 'date' => '2026-01-02' ] ) === Pdf::render( $appData, 'offer-one', $data, [ 'signatures' => [ 'buyer' => $signature ], 'title' => 'T', 'date' => '2026-01-02' ] ) );

$dated = (string) Pdf::render( $appData, 'offer-one', $data, [ 'date' => '2026-01-02', 'title' => 'Offer ö' ] );
check( '/CreationDate and /ModDate are there only with the option, as D:YYYYMMDD000000Z; the title is a text string', str_contains( $dated, '/CreationDate (D:20260102000000Z)' ) === true && str_contains( $dated, '/ModDate (D:20260102000000Z)' ) === true
	&& str_contains( $dated, '/Title <FEFF' ) === true && str_contains( (string) Pdf::render( $appData, 'offer-one', $data, [ 'date' => '2026-13-45' ] ), '/CreationDate' ) === false );

ninoWarnings();
check( 'an unknown template is null, with a warning that names it', Pdf::render( $appData, 'nowhere' ) === null && count( array_filter( ninoWarnings(), static fn( string $line ): bool => str_contains( $line, 'nowhere' ) ) ) === 1 );

// A template tagged with a word the project no longer has is a template all the same
$stale = \Nino\Filesystem::getFileContent( $appData, Pdf::TEMPLATES. '/invoice-a.php', [] );
\Nino\Filesystem::putFileContent( $appData, Pdf::TEMPLATES. '/invoice-a.php', [ 'tag' => 'retired' ] + $stale );
check( 'a tag that no longer exists is no reason: the template renders, and the list says the tag is gone', is_string( Pdf::render( $appData, 'invoice-a' ) ) === true && in_array( 'retired', Pdf::tags( $appData ), true ) === false
	&& array_column( $post( 'pdf/templates/list' )[1]['templates'], 'known', 'key' )['invoice-a'] === false && array_column( $post( 'pdf/templates/list' )[1]['templates'], 'known', 'key' )['offer-one'] === true );
\Nino\Filesystem::putFileContent( $appData, Pdf::TEMPLATES. '/invoice-a.php', $stale );

// The text that fills two pages, and the footer's numbers
$post( 'pdf/blocks/save', [ 'key' => 'long', 'isNew' => true, 'title' => 'Long', 'html' => '<p>'. str_repeat( 'word ', 1500 ). '</p>' ] );
$post( 'pdf/templates/save', [ 'key' => 'long', 'isNew' => true, 'name' => 'Long', 'tag' => 'information', 'blocks' => [ [ 'type' => 'html', 'block' => 'long' ] ] ] );
$long = (string) Pdf::render( $appData, 'long' );
check( 'a text long enough for two pages gives two pages, a short one gives one', $pages( $long ) === 2 && $pages( (string) Pdf::render( $appData, 'invoice-b' ) ) === 1 );
check( '...the footer is laid out twice: page 1 of 2 and page 2 of 2 are written in the content of the pages', str_contains( $content( $long, 0 ), '(Page 1 / 2) Tj' ) === true && str_contains( $content( $long, 1 ), '(Page 2 / 2) Tj' ) === true
	&& str_contains( $content( $long, 0 ), '(Head 1) Tj' ) === true );
$post( 'pdf/templates/save', [ 'key' => 'long-break', 'isNew' => true, 'name' => 'Long break', 'tag' => 'information', 'blocks' => [ [ 'type' => 'html', 'block' => 'long' ], [ 'type' => 'pagebreak' ], [ 'type' => 'html', 'block' => 'intro' ] ] ] );
check( '...and a page break after it adds a third', $pages( (string) Pdf::render( $appData, 'long-break' ) ) === 3 );

// --- the words of a document ----------------------------------------------------------

if( $pdftotext === true ) {

	$text = $words( $pdf );
	check( 'a token value is in the text, the dotted one too; an unknown token is gone', str_contains( $text, 'Hello Ada' ) === true && str_contains( $words( (string) Pdf::render( $appData, 'offer-one', [ 'tokens' => [ 'name' => 'Q' ] ] ) ), 'Hello Q' ) === true );
	check( '...the text of a block of its own, with an o umlaut and the euro sign, is in the text', str_contains( $text, 'Own text with ö and €' ) === true );
	check( '...a slot\'s table cell is, and so is the input\'s text, a line to a line', str_contains( $text, 'Cell one' ) === true && str_contains( $text, 'Price' ) === true && preg_match( '/first line\s+second line/', $text ) === 1 );
	check( '...the footer carries 1 / 2 and 2 / 2', str_contains( $words( $pdf, '-f 1 -l 1' ), 'Page 1 / 2' ) === true && str_contains( $words( $pdf, '-f 2 -l 2' ), 'Page 2 / 2' ) === true );

	$escaped = $words( (string) Pdf::render( $appData, 'offer-one', [ 'tokens' => [ 'name' => '<b>&</b>' ] ] ) );
	check( '<b>&</b> handed in as a token arrives as the characters <b>&</b> and not as bold', str_contains( $escaped, 'Hello <b>&</b> bold' ) === true );
	check( '...a token that is not there is removed from the document, not written out', str_contains( $words( (string) Pdf::render( $appData, 'offer-one', [] ) ), '[[name]]' ) === false && str_contains( $words( (string) Pdf::render( $appData, 'offer-one', [] ) ), 'Hello bold' ) === true );
}
else
	echo "note - pdftotext is not on the path: the pins on the words of a document were NOT run\n";

// --- the pictures in a document ---------------------------------------------------------

$withSignature = (string) Pdf::render( $appData, 'offer-one', $data, [ 'signatures' => [ 'seller' => $signature, 'buyer' => $signature ] ] );
$lastPage = $object( $withSignature, $pageObjects( $withSignature )[1] );
preg_match( '#/XObject << /Im1 (\d+) 0 R >>#', $lastPage, $found );
$xobject = $object( $withSignature, (int) ( $found[1] ?? 0 ) );
check( 'a signature PNG with alpha handed in is an image XObject with an /SMask on the resources of the page named: last', $found !== [] && str_contains( $xobject, '/Subtype /Image' ) === true && preg_match( '#/SMask (\d+) 0 R#', $xobject, $mask ) === 1
	&& str_contains( $object( $withSignature, (int) $mask[1] ), '/ColorSpace /DeviceGray' ) === true && str_contains( $content( $withSignature, 1 ), ' Do Q' ) === true );
check( '...and the page of the other field has its own: page 1 holds the buyer\'s picture, the last page the seller\'s', str_contains( $object( $withSignature, $pageObjects( $withSignature )[0] ), '/XObject <<' ) === true );
check( '...an image used twice is one XObject', substr_count( $withSignature, '/Subtype /Image' ) === 2 );

$noPictures = (string) Pdf::render( $appData, 'offer-one', $data );
$lastNone = $object( $noPictures, $pageObjects( $noPictures )[1] );
check( 'the owner field draws the layout\'s signature without being handed one: its image is on the last page', str_contains( $lastNone, '/XObject <<' ) === true && str_contains( $object( $noPictures, $pageObjects( $noPictures )[0] ), '/XObject' ) === false );
check( '...a member field without an image draws its label only, and a field with a line draws the hairline', str_contains( $content( $noPictures, 0 ), '(The buyer) Tj' ) === true && str_contains( $content( $noPictures, 1 ), '(The seller) Tj' ) === true
	&& str_contains( $content( $noPictures, 1 ), ' l S' ) === true );
$post( 'pdf/layout/image/delete', [ 'kind' => 'signature' ] );
$bare = (string) Pdf::render( $appData, 'offer-one', $data );
check( '...and an owner field with no signature at all draws its label too, with no image anywhere', str_contains( $bare, '/Subtype /Image' ) === false && str_contains( $content( $bare, 1 ), '(The seller) Tj' ) === true );
$file = $dir. '/signature.png';
file_put_contents( $file, $signature );
check( 'a signature handed in as the absolute path of a file is drawn - a picture and its mask; a path that is no file draws nothing', substr_count( (string) Pdf::render( $appData, 'offer-one', $data, [ 'signatures' => [ 'buyer' => $file ] ] ), '/Subtype /Image' ) === 2
	&& substr_count( (string) Pdf::render( $appData, 'offer-one', $data, [ 'signatures' => [ 'buyer' => $dir. '/nothing.png' ] ] ), '/Subtype /Image' ) === 0 );
$upload( 'signature', $signature );

$baseline = $picture( 'jpeg', 50, 30 );
$progressive = $picture( 'jpeg', 50, 30, false, true );
$jpegs = [ 'baseline' => Pdf\Images\Image::fromBytes( $baseline ), 'progressive' => Pdf\Images\Image::fromBytes( $progressive ) ];
check( 'a JPEG passes through behind /DCTDecode, baseline or progressive, untouched', $jpegs['baseline'] !== null && $jpegs['progressive'] !== null && $jpegs['baseline']->data() === $baseline && $jpegs['progressive']->data() === $progressive
	&& str_contains( $jpegs['baseline']->entries( 0 ), '/Filter /DCTDecode' ) === true && str_contains( $jpegs['baseline']->entries( 0 ), '/ColorSpace /DeviceRGB' ) === true && str_ends_with( $progressive, "\xFF\xD9" ) === true
	&& str_contains( $progressive, "\xFF\xC2" ) === true );
$cmyk = "\xFF\xD8\xFF\xC0\x00\x14\x08\x00\x10\x00\x10\x04\x01\x11\x00\x02\x11\x00\x03\x11\x00\x04\x11\x00\xFF\xD9";
check( 'a CMYK JPEG is refused, and so is a file that is no picture and one that is too big', Pdf\Images\Image::fromBytes( $cmyk ) === null && Pdf\Images\Image::fromBytes( 'GIF89a nonsense' ) === null && Pdf\Images\Image::fromBytes( '' ) === null
	&& Pdf\Images\Image::fromBytes( $picture( 'png', 4001, 1 ) ) === null );
$flat = Pdf\Images\Image::fromBytes( $picture( 'png', 30, 10 ) );
$alpha = Pdf\Images\Image::fromBytes( $signature );
check( 'a PNG is decoded to Flate-compressed RGB samples, with a soft mask of the alpha channel only where there is alpha', $flat !== null && $alpha !== null && $flat->alpha() === null && $alpha->alpha() !== null
	&& str_contains( $alpha->entries( 9 ), '/Filter /FlateDecode' ) === true && str_contains( $alpha->entries( 9 ), '/SMask 9 0 R' ) === true && strlen( (string) gzuncompress( $alpha->data() ) ) === 60 * 20 * 3
	&& strlen( (string) gzuncompress( (string) $alpha->alpha() ) ) === 60 * 20 && (string) gzuncompress( (string) $alpha->alpha() ) === str_repeat( chr( 255 - (int) round( 64 * 255 / 127 ) ), 60 * 20 ) );

// A JPEG that its EXIF block says to turn is decoded and turned, not passed through
$exif = static function( string $jpeg, int $orientation ): string {
	$block = "Exif\x00\x00MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01". pack( 'n', $orientation ). "\x00\x00\x00\x00\x00\x00";
	return "\xFF\xD8\xFF\xE1". pack( 'n', strlen( $block ) + 2 ). $block. substr( $jpeg, 2 );
};
$turned = Pdf\Images\Image::fromBytes( $exif( $picture( 'jpeg', 40, 20 ), 6 ) );
$upright = Pdf\Images\Image::fromBytes( $exif( $picture( 'jpeg', 40, 20 ), 1 ) );
check( 'a JPEG whose EXIF orientation turns it is decoded and turned upright - the pixels swap their sides; one that is upright passes through', $turned !== null && $turned->width() === 20 && $turned->height() === 40 && str_contains( $turned->entries( 0 ), '/FlateDecode' ) === true
	&& $upright !== null && $upright->width() === 40 && str_contains( $upright->entries( 0 ), '/DCTDecode' ) === true );
$palette = imagecreate( 30, 10 );
$clear = imagecolorallocate( $palette, 255, 255, 255 );
imagecolortransparent( $palette, $clear );
imagefilledrectangle( $palette, 0, 0, 9, 9, (int) imagecolorallocate( $palette, 255, 0, 0 ) );
ob_start();
imagegif( $palette );
$gif = Pdf\Images\Image::fromBytes( (string) ob_get_clean() );
check( 'a GIF with a transparent colour is a picture with a soft mask: the transparent colour is the alpha channel', $gif !== null && $gif->alpha() !== null && strlen( (string) gzuncompress( (string) $gif->alpha() ) ) === 300 && str_contains( (string) gzuncompress( (string) $gif->alpha() ), "\x00" ) === true );

// --- the preview ----------------------------------------------------------------------------

[ $status, $body ] = $post( 'pdf/preview', [ 'name' => 'Draft', 'tag' => 'offer', 'blocks' => $blocks ] );
$previewed = base64_decode( (string) ( $body['pdf'] ?? '' ), true );
check( 'preview answers base64 that decodes to the bytes of a PDF, whatever is saved: an editor that holds a new template renders', $status === 200 && is_string( $previewed ) === true && str_starts_with( $previewed, '%PDF' ) === true && str_ends_with( $previewed, '%%EOF' ) === true );
preg_match( '#<td>(.*?)</td>#', \Nino\Feature::template( $appData, '/features/Pdf/templates', 'preview-slot' ), $slotCell );
preg_match( '#<h1>(.*?)</h1>#', \Nino\Feature::template( $appData, '/features/Pdf/templates', 'preview-sample' ), $sampleHead );
check( '...a slot is drawn as the table of the sample and a token as its name in square brackets, an input as its label, so the page says where the data lands', $pages( $previewed ) === 2
	&& $slotCell !== [] && str_contains( $content( $previewed, 0 ), '('. $slotCell[1]. ')' ) === true && str_contains( $content( $previewed, 0 ), '([A note]) Tj' ) === true && str_contains( $content( $previewed, 0 ), '[name]' ) === true );
check( '...a template with no body shows the layout around a sample text', $pages( (string) base64_decode( $post( 'pdf/preview', [ 'name' => 'Empty', 'tag' => 'offer', 'blocks' => [] ] )[1]['pdf'] ) ) >= 1
	&& str_contains( $content( (string) base64_decode( $post( 'pdf/preview', [ 'name' => 'Empty', 'tag' => 'offer', 'blocks' => [] ] )[1]['pdf'] ), 0 ), '(Page 1 /' ) === true
	&& $sampleHead !== [] && str_contains( $content( (string) base64_decode( $post( 'pdf/preview', [ 'name' => 'Empty', 'tag' => 'offer', 'blocks' => [] ] )[1]['pdf'] ), 0 ), '('. $sampleHead[1]. ') Tj' ) === true );
[ $status, $body ] = $post( 'pdf/preview', [ 'name' => 'Draft', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'signature', 'name' => 'seal', 'party' => 'owner', 'label' => '', 'page' => 'last', 'x' => 200, 'y' => 10, 'w' => 60, 'h' => 20, 'line' => true ] ] ] );
check( '...a preview of a state that a save would refuse is refused the same way', $status === 400 && $body['code'] === 'pdf_signature_box' );

// --- the writer ----------------------------------------------------------------------------------

check( 'the numbers of a PDF are written with three decimals at most, whatever the locale', Pdf\Document\Document::num( 595.2756 ) === '595.276' && Pdf\Document\Document::num( 12.0 ) === '12' && Pdf\Document\Document::num( -0.0001 ) === '0' && Pdf\Document\Document::num( 0.5 ) === '0.5' );
check( '...a literal string escapes the delimiters, the backslash and every byte outside printable ASCII', Pdf\Document\Document::literal( "a(b)\\\xE4\r" ) === '(a\\050b\\051\\134\\344\\015)' );
check( '...a text string is a literal for ASCII and UTF-16BE with its byte order mark for the rest', Pdf\Document\Document::text( 'Plain' ) === '(Plain)' && Pdf\Document\Document::text( 'Ä' ) === '<FEFF00C4>' );
check( 'the widths are the metrics of the AFM files: Helvetica, Times and Courier, 224 numbers for the codes 32 to 255 and one number for Courier', Pdf\Fonts\Metrics::width( 'Helvetica', 'A' ) === 667.0 && Pdf\Fonts\Metrics::width( 'Helvetica-Bold', 'A' ) === 722.0
	&& Pdf\Fonts\Metrics::width( 'Times-Roman', 'A' ) === 722.0 && Pdf\Fonts\Metrics::width( 'Times-Italic', 'i' ) === 278.0 && Pdf\Fonts\Metrics::width( 'Courier-BoldOblique', 'Wi!' ) === 1800.0
	&& Pdf\Fonts\Metrics::width( 'Helvetica', "\xE4" ) === 556.0 && Pdf\Fonts\Metrics::width( 'Helvetica', "\x80" ) === 556.0 && Pdf\Fonts\Metrics::width( 'Helvetica', ' ' ) === 278.0 && Pdf\Fonts\Metrics::width( 'Helvetica', "\x01" ) === 0.0
	&& count( (array) require dirname( __DIR__ ). '/Fonts/Metrics/Helvetica-BoldOblique.php' ) === 224 && ( require dirname( __DIR__ ). '/Fonts/Metrics/Courier-Bold.php' ) === 600 );
check( 'a family and a style name one of twelve base fonts', Pdf\Fonts\Metrics::font( 'times', true, true ) === 'Times-BoldItalic' && Pdf\Fonts\Metrics::font( 'courier', false, true ) === 'Courier-Oblique' && Pdf\Fonts\Metrics::font( 'helvetica', true, false ) === 'Helvetica-Bold'
	&& Pdf\Fonts\Metrics::font( 'nothing', false, false ) === 'Helvetica' );
check( 'text is written in the code page of the fonts: o umlaut, euro sign and typographic quotes are one byte, what the page cannot carry is a question mark', Pdf\Layout\Text::encode( "\u{F6}\u{20AC}\u{2019}" ) === "\xF6\x80\x92" && Pdf\Layout\Text::encode( "\u{65E5}x" ) === '?x' && bin2hex( Pdf\Layout\Text::encode( "a\u{AD}b" ) ) === '6162' );

$type = [ 'f' => 'Helvetica', 'z' => 10.0, 'u' => false ];
$lines = Pdf\Layout\Text::wrap( Pdf\Layout\Text::atoms( "one  two\n three four five six seven", $type ), 70.0, $type );
check( 'text wraps at word boundaries on the measured widths: no line is wider than the room, whitespace collapses, and the words stay whole', count( $lines ) > 2 && array_filter( $lines, static fn( array $line ): bool => $line['w'] > 70.01 ) === []
	&& implode( ' ', array_map( static fn( array $line ): string => implode( '', array_column( $line['a'], 's' ) ), $lines ) ) === 'one two three four five six seven' );
$wide = Pdf\Layout\Text::wrap( Pdf\Layout\Text::atoms( str_repeat( 'W', 40 ), $type ), 50.0, $type );
check( 'a word wider than the line is broken at the width, and not before', count( $wide ) > 3 && array_filter( $wide, static fn( array $line ): bool => $line['w'] > 50.01 ) === [] && implode( '', array_map( static fn( array $line ): string => implode( '', array_column( $line['a'], 's' ) ), $wide ) ) === str_repeat( 'W', 40 )
	&& $wide[0]['w'] > 40.0 );
$glued = Pdf\Layout\Text::wrap( array_merge( Pdf\Layout\Text::atoms( 'x ab', $type ), Pdf\Layout\Text::atoms( 'cd', [ 'f' => 'Helvetica-Bold' ] + $type ) ), 25.0, $type );
check( 'words that touch are not parted: a bold word in a word moves to the next line with the rest of it; and the line is as high as 1.35 times the type', count( $glued ) === 2 && implode( '', array_column( $glued[0]['a'], 's' ) ) === 'x'
	&& implode( '', array_column( $glued[1]['a'], 's' ) ) === 'abcd' && abs( $glued[0]['h'] - 13.5 ) < 0.001 );

$names = [ 'name' => 'Ada <b>', 'client' => [ 'city' => 'Köln', 'tags' => [ 'a' ] ], 'page' => '7', 'number' => 12 ];
check( 'tokens: a plain and a dotted name are filled and escaped as text; an unknown one, an array and a missing key are removed', Pdf\Layout\Tokens::fill( $appData, '[[name]]|[[client.city]]|[[client.tags]]|[[client.none]]|[[nothing]]|[[number]]', $names ) === 'Ada &lt;b&gt;|Köln||||12' );
check( '...in a preview an unknown name stands as itself in square brackets - the opening one as its character reference; page and pages stay for the engine in a header or a footer only', Pdf\Layout\Tokens::fill( $appData, '[[nothing]] [[client.none]]', $names, true ) === '&#91;nothing] &#91;client.none]'
	&& Pdf\Layout\Tokens::fill( $appData, '[[page]] [[pages]]', $names, false, true ) === '[[page]] [[pages]]' && Pdf\Layout\Tokens::fill( $appData, '[[page]] [[pages]]', $names ) === '7 ' );
\Nino\Html::addFills( $appData, [ '[[/project/company/general/name]]' => 'Acme & <Sons>' ] );
check( '...a token with a leading slash is the text of the site for that key, escaped as text; a key without a text is removed', Pdf\Layout\Tokens::fill( $appData, '[[/project/company/general/name]]|[[/project/company/general/none]]', [] ) === 'Acme &amp; &lt;Sons&gt;|' );
check( '...a value that holds a token is not read for tokens again, and its square brackets are character references: what reads the HTML afterwards for page and pages finds no token in it', Pdf\Layout\Tokens::fill( $appData, '[[a]]', [ 'a' => '[[b]]', 'b' => 'x' ] ) === '&#91;&#91;b]]'
	&& Pdf\Layout\Tokens::fill( $appData, '[[a]]', [ 'a' => 'X[[pages]]Y' ], false, true ) === 'X&#91;&#91;pages]]Y' );
\Nino\Html::addFills( $appData, [ '[[/project/company/general/pages]]' => 'See [[pages]]' ] );
check( '...and so are those of a text of the site', Pdf\Layout\Tokens::fill( $appData, '[[/project/company/general/pages]]', [], false, true ) === 'See &#91;&#91;pages]]' );

\Nino\Html::addFills( $appData, [ '[[/project/company/general/nest-a]]' => 'x [[/project/company/general/nest-b]] y [csrf] [[/project/company/general/nest-c]]', '[[/project/company/general/nest-b]]' => 'B&<', '[[/project/company/general/nest-c]]' => '[[/project/company/general/nest-c]]' ] );
check( '...a text of the site that holds the key of another is read once through the kernel\'s fill, the value of the other is text, a call in it is no call, and a key that names itself is the characters of itself',
	Pdf\Layout\Tokens::fill( $appData, '[[/project/company/general/nest-a]]', [] ) === 'x B&amp;&lt; y &#91;csrf] &#91;&#91;/project/company/general/nest-c]]' );

// --- the tables ----------------------------------------------------------------------------------------

$rows = '';
for( $row = 1; $row <= 50; $row++ )
	$rows .= '<tr><td>Row '. $row. '</td><td>Value</td></tr>';
$post( 'pdf/templates/save', [ 'key' => 'table', 'isNew' => true, 'name' => 'Table', 'tag' => 'information', 'blocks' => [ [ 'type' => 'slot', 'name' => 'rows' ] ] ] );
$tabled = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<table><thead><tr><th>Head A</th><th>Head B</th></tr></thead><tbody>'. $rows. '</tbody></table>' ] ] );
check( 'a table that does not fit moves its rows to the next page, and the rows of the thead are drawn there again', $pages( $tabled ) === 2 && substr_count( $content( $tabled, 0 ), '(Head A) Tj' ) === 1 && substr_count( $content( $tabled, 1 ), '(Head A) Tj' ) === 1
	&& str_contains( $content( $tabled, 1 ), '(Row 50) Tj' ) === true && str_contains( $content( $tabled, 0 ), '(Row 50) Tj' ) === false );
check( '...a table with border="0" has no hairline under its rows; with the rule under a th row it has one', substr_count( $content( (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<table border="0"><tr><td>a</td></tr><tr><td>b</td></tr></table>' ] ] ), 0 ), ' l S' ) === 0
	&& substr_count( $content( (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<table><tr><td>a</td></tr><tr><td>b</td></tr></table>' ] ] ), 0 ), ' l S' ) === 2 );
ninoWarnings();
$tall = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<table><tr><td>'. str_repeat( 'tall ', 6000 ). '</td></tr><tr><td>after</td></tr></table>' ] ] );
check( 'a single row taller than a page is drawn clipped on a page of its own, with an E_USER_WARNING; the next row starts another page', $pages( $tall ) === 2 && str_contains( $content( $tall, 0 ), ' re W n' ) === true && str_contains( $content( $tall, 1 ), '(after) Tj' ) === true
	&& count( array_filter( ninoWarnings(), static fn( string $line ): bool => str_contains( $line, 'taller than a page' ) ) ) === 1 );

$grid = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<table><tr><td width="25%">a</td><td>b</td></tr></table>' ] ] );
preg_match_all( '/BT \/F\d+ [\d.]+ Tf ([\d.]+) [\d.]+ Td \((a|b)\) Tj ET/', $content( $grid, 0 ), $columns );
$column = array_combine( $columns[2], array_map( 'floatval', $columns[1] ) );
check( 'the columns take the width the cells of the first row ask for in percent, and share the rest: the second starts a quarter of the column in, plus the padding', abs( ( $column['b'] - $column['a'] ) - 0.25 * ( 612 - ( 16 + 18 ) * 72 / 25.4 ) ) < 0.02 );

// --- the layout around the text ----------------------------------------------------------------------------

$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'courier', 'size' => 10, 'header' => '<h3>Head</h3><ul><li>one</li><li>two</li></ul><p><img src="pdf:logo" width="20"></p>', 'footer' => '<hr><p align="center">[[page]]</p>' ] );
$cour = (string) Pdf::render( $appData, 'invoice-b' );
check( 'the layout is applied: A4 in points, the base font of the family, a rule and a centred footer', str_contains( $cour, '/MediaBox [ 0 0 595.276 841.89 ]' ) === true && str_contains( $cour, '/BaseFont /Courier' ) === true && str_contains( $cour, '/BaseFont /Courier-Bold' ) === true );
check( '...an img with src="pdf:logo" is drawn only when the layout has a logo, and any other src is left out', substr_count( $cour, '/Subtype /Image' ) === 0 );
$upload( 'logo', $picture( 'png', 80, 40 ) );
$withLogo = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<p><img src="https://example.com/x.png" width="20">text</p><p><img src="pdf:logo" width="20"></p>' ] ] );
check( '...with a logo it is: one image XObject, drawn where the header and the text name it; an img of any other src is left out', substr_count( $withLogo, '/Subtype /Image' ) === 1 && substr_count( $content( $withLogo, 0 ), ' Do Q' ) === 2 );
$tallBand = '<p>'. str_repeat( 'band ', 780 ). '</p>';
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => $tallBand, 'footer' => '' ] );
$capped = (string) Pdf::render( $appData, 'long' );
preg_match_all( '/BT \/F\d+ [\d.]+ Tf [\d.]+ ([\d.]+) Td \(word word/', $content( $capped, 0 ), $body );
check( 'a header never takes more than 40 mm: the text starts 20 mm of margin, 40 of header and 4 of gap down from the top - the first baseline 9.3 points below that - and the header is clipped to its band', $body[1] !== []
	&& abs( max( array_map( 'floatval', $body[1] ) ) - ( 841.8898 - 64 * 72 / 25.4 - 9.305 ) ) < 0.05 && str_contains( $content( $capped, 0 ), ' re W n' ) === true );
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );

// A value is text: it cannot take the page numbers of a header or a footer, whatever it holds
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '<p>[[c]] / [[page]] of [[pages]]</p>', 'footer' => '<p>[[c]]</p>' ] );
$injected = (string) Pdf::render( $appData, 'invoice-b', [ 'tokens' => [ 'c' => 'X[[pages]]Y [[page]]' ] ] );
check( 'a value that holds the page tokens is text in a header and in a footer, and the numbers are the engine\'s: the characters of the value are drawn, the page and the pages after them', $pages( $injected ) === 1
	&& str_contains( $content( $injected, 0 ), '(X[[pages]]Y [[page]] / 1 of 1) Tj' ) === true && str_contains( $content( $injected, 0 ), '(X[[pages]]Y [[page]]) Tj' ) === true );
if( $pdftotext === true )
	check( '...and pdftotext reads them as they were handed in, X[[pages]]Y, in both', substr_count( $words( $injected ), 'X[[pages]]Y [[page]]' ) === 2 && str_contains( $words( $injected ), 'X1Y' ) === false );

// A header that no save of the panel wrote - a file put there by hand - is held to the list when a document is laid out
\Nino\Filesystem::putFileContent( $appData, Pdf::LAYOUT, [ 'header' => '<p onclick="x()">Band text</p><script>band_hidden()</script><title>band_title</title>', 'footer' => '' ] + Pdf::layout( $appData ) );
$handmade = (string) Pdf::render( $appData, 'invoice-b' );
check( 'a header in a file of the layout is held to the list as a saved one is: its text is drawn, its script and its title are not', str_contains( $content( $handmade, 0 ), '(Band text) Tj' ) === true && str_contains( $content( $handmade, 0 ), 'band_hidden' ) === false && str_contains( $content( $handmade, 0 ), 'band_title' ) === false );
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );

// --- the geometry of the HTML -------------------------------------------------------------------------------

$fontOf = static function( string $pdf, int $page, string $name ) use ( $object, $pageObjects ): string {
	preg_match( '#/Font <<([^>]*)>>#', $object( $pdf, $pageObjects( $pdf )[$page] ), $fonts );
	preg_match( '#/'. $name. ' (\d+) 0 R#', $fonts[1] ?? '', $found );
	return preg_match( '#/BaseFont /([A-Za-z-]+)#', $object( $pdf, (int) ( $found[1] ?? 0 ) ), $base ) === 1 ? $base[1] : '';
};
// [ text => [ x, font, size, y ] ] of the strings a page shows
$shown = static function( string $pdf, int $page ) use ( $content, $fontOf ): array {
	preg_match_all( '#BT /(F\d+) ([\d.]+) Tf ([\d.]+) ([\d.]+) Td \(([^)]*)\) Tj ET#', $content( $pdf, $page ), $found, PREG_SET_ORDER );
	$out = [];
	foreach( $found as $one )
		$out[ $one[5] ] = [ (float) $one[3], $fontOf( $pdf, $page, $one[1] ), (float) $one[2], (float) $one[4] ];
	return $out;
};

$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '<p>[[/project/company/general/name]] / [[page]]</p>', 'footer' => '' ] );
$html = '<h1>One</h1><h2>Two</h2><h3>Three</h3><p>Plain</p><ul><li>Item</li></ul><ol><li>First</li></ol><p align="right">Right</p><p style="text-align:center">Centre</p>'
	. '<p><b>Bold</b> <i>Italic</i> <b><i>Both</i></b> <a href="#">Link</a></p><table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>'
	. '<p>one<br>two<br><br>three</p><p>Seen</p><script>hidden()</script><style>.quiet{}</style><template>tpl_hidden</template><title>title_hidden</title>';
$geometry = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $html ] ] );
$at = $shown( $geometry, 0 );

check( 'a heading is bold and 1.8, 1.4 or 1.15 times the base size; a paragraph is the base size in the font of the layout', $at['One'][2] === 18.0 && $at['Two'][2] === 14.0 && $at['Three'][2] === 11.5 && $at['Plain'][2] === 10.0
	&& $at['One'][1] === 'Helvetica-Bold' && $at['Two'][1] === 'Helvetica-Bold' && $at['Three'][1] === 'Helvetica-Bold' && $at['Plain'][1] === 'Helvetica' );
check( '...a list item is indented 6 mm, with its bullet - or its number - in front of it', abs( $at['Item'][0] - $at['Plain'][0] - 6 * 72 / 25.4 ) < 0.05 && isset( $at['\\225'] ) === true && $at['\\225'][0] < $at['Item'][0] && isset( $at['1.'] ) === true && $at['1.'][0] < $at['First'][0] );
check( '...a paragraph can be set right or centred: its text ends at the right margin or stands in the middle of the column', abs( $at['Right'][0] - ( 595.2756 - 20 * 72 / 25.4 - 23.34 ) ) < 0.05
	&& abs( $at['Centre'][0] - ( 20 * 72 / 25.4 + ( 170 * 72 / 25.4 - 30.01 ) / 2 ) ) < 0.05 );
check( '...bold, italic and both are the fonts of the family; a th is bold, and the rule under its row is a heavier line than the hairlines', $at['Bold'][1] === 'Helvetica-Bold' && $at['Italic'][1] === 'Helvetica-Oblique' && $at['Both'][1] === 'Helvetica-BoldOblique'
	&& $at['Head'][1] === 'Helvetica-Bold' && $at['Cell'][1] === 'Helvetica' && str_contains( $content( $geometry, 0 ), 'q 0.8 w' ) === true && str_contains( $content( $geometry, 0 ), 'q 0.4 w' ) === true );
check( '...a br ends the line and two make an empty one, at 1.35 times the type; a script and a style are no text of the document', abs( $at['one'][3] - $at['two'][3] - 13.5 ) < 0.001 && abs( $at['two'][3] - $at['three'][3] - 27 ) < 0.001
	&& isset( $at['Seen'] ) === true && str_contains( $content( $geometry, 0 ), 'hidden' ) === false && str_contains( $content( $geometry, 0 ), 'quiet' ) === false );
$unlisted = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<p onclick="alert(1)">Before <font color="red">Unwrapped</font> <u>Under</u> <a href="javascript:alert(1)">Link</a></p><section>Section</section><img src="https://example.com/x.png" width="20"><p>After</p>' ] ] );
check( '...a slot is held to the list of what a document may hold as a saved text is: the text of a tag that is not listed is drawn, the tag and the address of a link and of a picture that is not the logo are not in the file, and there is no picture',
	str_contains( $content( $unlisted, 0 ), '(Before Unwrapped Under ) Tj' ) === true && str_contains( $content( $unlisted, 0 ), '(Link) Tj' ) === true && str_contains( $content( $unlisted, 0 ), '(Section) Tj' ) === true && str_contains( $content( $unlisted, 0 ), '(After) Tj' ) === true
	&& preg_match( '/font|javascript|example\.com|onclick|alert/', $unlisted ) === 0 && str_contains( $unlisted, '/Subtype /Image' ) === false );
if( $pdftotext === true )
	check( '...and pdftotext reads the text of it, not the tags', preg_match( '/Before Unwrapped Under Link\s+Section\s+After/', $words( $unlisted ) ) === 1 && preg_match( '/<font|<section|<u>|<a |javascript|onclick/', $words( $unlisted ) ) === 0 );
check( '...a link is its text underlined, with no annotation; a text key and a token in the header are filled in before the page is laid out', substr_count( $content( (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<p><a href="#">Link</a> and more</p>' ] ] ), 0 ), ' l S' ) === 1
	&& str_contains( $geometry, '/Annots' ) === false && str_contains( $content( $geometry, 0 ), '(Acme & <Sons> / 1) Tj' ) === true );
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );

// --- the paper and the boxes on it ------------------------------------------------------------------------------

// A signature box that lies on the page of A4 may lie off the page of US Letter, and the other way round: the paper is not changed under the templates
$seal = static fn( array $change ): array => [ $change + [ 'type' => 'signature', 'name' => 'seal', 'party' => 'member', 'label' => '', 'page' => 'last', 'x' => 20, 'y' => 270, 'w' => 60, 'h' => 20, 'line' => false ] ];
$onLetter = [ 'format' => 'letter', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 12, 'header' => '', 'footer' => '' ];
$post( 'pdf/templates/save', [ 'key' => 'low-b', 'isNew' => true, 'name' => 'Low B', 'tag' => 'offer', 'blocks' => $seal( [] ) ] );
$post( 'pdf/templates/save', [ 'key' => 'low-a', 'isNew' => true, 'name' => 'Low A', 'tag' => 'offer', 'blocks' => $seal( [] ) ] );
\Nino\Filesystem::putFileContent( $appData, Pdf::TEMPLATES. '/ghost.php', [ 'name' => 'Ghost', 'tag' => 'offer', 'blocks' => $seal( [ 'y' => 295 ] ) ] );
[ $status, $body ] = $post( 'pdf/layout/save', [ 'format' => 'a4' ] + $onLetter );
check( 'layout/save with the paper it has is no change of paper, whatever boxes lie off the page: it is saved', $status === 200 && Pdf::layout( $appData )['size'] === 12 && Pdf::layout( $appData )['format'] === 'a4' );
[ $status, $body ] = $post( 'pdf/layout/save', $onLetter );
check( '...but the other paper is 409 with the code, the names of the templates whose boxes would lie off the page - in the order of their keys, a template the panel did not write as well - and the field; the form is written not at all', $status === 409 && $body['code'] === 'pdf_format_boxes'
	&& $body['params'] === [ 'Ghost, Low A, Low B' ] && $body['field'] === 'format' && Pdf::layout( $appData )['format'] === 'a4' && $post( 'pdf/layout/save', [ 'size' => 9 ] + $onLetter )[0] === 409 && Pdf::layout( $appData )['size'] === 12 );
$post( 'pdf/templates/delete', [ 'key' => 'ghost' ] );
foreach( [ 'low-a' => 'Low A', 'low-b' => 'Low B' ] as $key => $name )
	$post( 'pdf/templates/save', [ 'key' => $key, 'isNew' => false, 'name' => $name, 'tag' => 'offer', 'blocks' => $seal( [ 'y' => 200 ] ) ] );
check( '...once every box lies on both papers the paper changes', $post( 'pdf/layout/save', $onLetter )[0] === 200 && Pdf::layout( $appData )['format'] === 'letter' );
check( '...and a template that is saved on the new paper is held to its page: the box that lies on A4 does not lie on Letter', $post( 'pdf/templates/save', [ 'key' => 'low-a', 'isNew' => false, 'name' => 'Low A', 'tag' => 'offer', 'blocks' => $seal( [] ) ] )[1]['code'] === 'pdf_signature_box' );
$post( 'pdf/templates/save', [ 'key' => 'wide', 'isNew' => true, 'name' => 'Wide', 'tag' => 'offer', 'blocks' => $seal( [ 'x' => 155, 'y' => 200 ] ) ] );
[ $status, $body ] = $post( 'pdf/layout/save', [ 'format' => 'a4' ] + $onLetter );
check( '...and the way back is held to the width: a box that lies on Letter and not on A4 refuses A4, naming that template alone', $status === 409 && $body['params'] === [ 'Wide' ] && Pdf::layout( $appData )['format'] === 'letter' );
foreach( [ 'wide', 'low-a', 'low-b' ] as $key )
	$post( 'pdf/templates/delete', [ 'key' => $key ] );
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );

$post( 'pdf/templates/save', [ 'key' => 'stamp', 'isNew' => true, 'name' => 'Stamp', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'signature', 'name' => 'seal', 'party' => 'member', 'label' => '', 'page' => 'last', 'x' => 20, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => false ] ] ] );
$stamped = (string) Pdf::render( $appData, 'stamp', [], [ 'signatures' => [ 'seal' => $picture( 'png', 20, 40 ) ] ] );
preg_match( '#q ([\d.]+) 0 0 ([\d.]+) ([\d.]+) ([\d.]+) cm /Im1 Do Q#', $content( $stamped, 0 ), $cm );
check( 'a signature picture is fitted into its box keeping its ratio, bottom-aligned at the left edge: a tall picture in a wide box is as high as the box and half as wide, and stands on the bottom edge', $pages( $stamped ) === 1 && count( $cm ) === 5
	&& abs( $cm[1] - 10 * 72 / 25.4 ) < 0.05 && abs( $cm[2] - 20 * 72 / 25.4 ) < 0.05 && abs( $cm[3] - 20 * 72 / 25.4 ) < 0.05 && abs( $cm[4] - ( 841.8898 - 220 * 72 / 25.4 ) ) < 0.05 );
$post( 'pdf/templates/delete', [ 'key' => 'stamp' ] );

// --- the appended part ---------------------------------------------------------------------------------------------

// The option append of render(): an HTML part on a page of its own after the last block, which a signature feature uses for the record of a signing
$record = '<h2>Appended record</h2><p>Appended text of the caller</p>';
$base = (string) Pdf::render( $appData, 'invoice-b' );
$appended = (string) Pdf::render( $appData, 'invoice-b', [], [ 'append' => $record ] );
check( 'append lays the part out on a page of its own after the last block: one page more, its text on the last page and not on the first - and a render without the option is the render it was, byte for byte',
	$pages( $base ) === 1 && $pages( $appended ) === 2 && str_contains( $content( $appended, 1 ), '(Appended text of the caller) Tj' ) === true && str_contains( $content( $appended, 0 ), 'Appended' ) === false
	&& $base === Pdf::render( $appData, 'invoice-b' ) && $appended === Pdf::render( $appData, 'invoice-b', [], [ 'append' => $record ] ) );
check( '...nothing is added where there is nothing to add: an empty option, one of blanks, one that is no text and one that holds nothing to draw leave the bytes as they were',
	Pdf::render( $appData, 'invoice-b', [], [ 'append' => '' ] ) === $base && Pdf::render( $appData, 'invoice-b', [], [ 'append' => " \n\t " ] ) === $base
	&& Pdf::render( $appData, 'invoice-b', [], [ 'append' => [ '<p>x</p>' ] ] ) === $base && Pdf::render( $appData, 'invoice-b', [], [ 'append' => '<script>hidden()</script><style>p{}</style>' ] ) === $base );

$held = (string) Pdf::render( $appData, 'invoice-b', [], [ 'append' => '<p onclick="alert(1)">Held <font color="red">text</font></p><script>hidden()</script><img src="https://example.com/x.png" width="20">' ] );
check( '...it is held to the list of what a document may hold, as a slot is: the text of a tag that is not listed is drawn, and an attribute, a script, an address and a picture that is not the logo are not in the file',
	$pages( $held ) === 2 && str_contains( $content( $held, 1 ), '(Held text) Tj' ) === true && preg_match( '/onclick|alert|hidden|example\.com|<font/', $held ) === 0 && str_contains( $held, '/Subtype /Image' ) === false );

$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '<p>Head [[page]]</p>', 'footer' => '<p>Page [[page]] / [[pages]]</p>' ] );
$framed = (string) Pdf::render( $appData, 'invoice-b', [], [ 'append' => $record ] );
$longer = (string) Pdf::render( $appData, 'invoice-b', [], [ 'append' => '<p>'. str_repeat( 'word ', 1500 ). '</p>' ] );
check( '...the appended page has the header and the footer of the layout and [[pages]] counts it: page 2 of 2; a part longer than a page goes on to the next, and the last of three says 3 of 3',
	str_contains( $content( $framed, 0 ), '(Page 1 / 2) Tj' ) === true && str_contains( $content( $framed, 1 ), '(Page 2 / 2) Tj' ) === true && str_contains( $content( $framed, 1 ), '(Head 2) Tj' ) === true
	&& $pages( $longer ) === 3 && str_contains( $content( $longer, 2 ), '(Page 3 / 3) Tj' ) === true );
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );

// The fields belong to the pages of the template: the last page is the last before the appended one
$field = static fn( string $name, int|string $page, string $label = '' ): array => [ 'type' => 'signature', 'name' => $name, 'party' => 'member', 'label' => $label, 'page' => $page, 'x' => 20, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => false ];
$post( 'pdf/templates/save', [ 'key' => 'append-seal', 'isNew' => true, 'name' => 'Append seal', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'block' => 'intro' ], $field( 'seal', 'last' ), $field( 'later', 2 ) ] ] );
$sealed = (string) Pdf::render( $appData, 'append-seal', [], [ 'signatures' => [ 'seal' => $picture( 'png', 20, 40 ), 'later' => $picture( 'png', 20, 40 ) ], 'append' => $record ] );
check( 'a field on the last page stays on the last page of the template and not on the appended one, and a field on a page past the template is not drawn on the appended page: the picture is on the first of the two pages only',
	$pages( $sealed ) === 2 && str_contains( $object( $sealed, $pageObjects( $sealed )[0] ), '/XObject <<' ) === true && str_contains( $object( $sealed, $pageObjects( $sealed )[1] ), '/XObject' ) === false
	&& str_contains( $content( $sealed, 1 ), ' Do Q' ) === false && str_contains( $content( $sealed, 0 ), ' Do Q' ) === true && substr_count( $content( $sealed, 0 ), ' Do Q' ) === 1 );

check( '...a template with text keeps it on its own page and the part comes on the one after: the text of the template is on the first page and not on the second, the part on the second and not on the first',
	str_contains( $content( $sealed, 0 ), 'Hello' ) === true && str_contains( $content( $sealed, 0 ), 'Appended' ) === false && str_contains( $content( $sealed, 1 ), 'Appended' ) === true && str_contains( $content( $sealed, 1 ), 'Hello' ) === false );

$post( 'pdf/templates/save', [ 'key' => 'append-empty', 'isNew' => true, 'name' => 'Append empty', 'tag' => 'offer', 'blocks' => [ $field( 'seal', 'last', 'The seal' ) ] ] );
$emptied = (string) Pdf::render( $appData, 'append-empty', [], [ 'append' => $record ] );
check( '...a template with no text has its page all the same - the part is the second, not the first', $pages( $emptied ) === 2 && str_contains( $content( $emptied, 0 ), '(The seal) Tj' ) === true
	&& str_contains( $content( $emptied, 1 ), '(Appended text of the caller) Tj' ) === true && $pages( (string) Pdf::render( $appData, 'append-empty' ) ) === 1 );

$post( 'pdf/templates/save', [ 'key' => 'append-end', 'isNew' => true, 'name' => 'Append end', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'block' => 'intro' ], [ 'type' => 'pagebreak' ] ] ] );
check( '...and a template that ends in a page break adds no page of its own before the part: the part is the second page, not the third', $pages( (string) Pdf::render( $appData, 'append-end' ) ) === 1
	&& $pages( (string) Pdf::render( $appData, 'append-end', [], [ 'append' => $record ] ) ) === 2 );
if( $pdftotext === true )
	check( '...and pdftotext reads the part on the last page, after the text of the template', preg_match( '/Appended record\s+Appended text of the caller/', $words( $appended, '-f 2 -l 2' ) ) === 1 && str_contains( $words( $appended, '-f 1 -l 1' ), 'Appended' ) === false );

foreach( [ 'append-seal', 'append-empty', 'append-end' ] as $key )
	$post( 'pdf/templates/delete', [ 'key' => $key ] );

// --- the design sheet ------------------------------------------------------------------------------------------------------

// A project of its own for what must not touch the one above: it boots the feature the way the first lines of this file do
$bootProject = static function( string $name ): array {

	$project = ninoSandbox( $name );
	\Nino\AppData::writeContentData( $project, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
	\Nino\Features::activate( $project, 'pdf' );
	\Nino\Features::saveSettings( $project, 'pdf', [ 'tags' => "offer\ninvoice\ncontract\nprivacy\ninformation" ] );

	return $project;
};

// The fixture of the byte identity: headings, lists, tables, rules, links, a header and a footer, a signature and an appended part, in three layouts with no override
$fixture = $bootProject( 'pdf-fixture' );
$fixtureLayout = static fn( array $change ): array => $change + [ 'format' => 'a4', 'margins' => [ 20, 22, 18, 24 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ];
Pdf\Store::saveLayout( $fixture, $fixtureLayout( [ 'header' => '<table border="0"><tr><td><h3>Fixture AG</h3></td><td align="right">[[page]] / [[pages]]</td></tr></table><hr>', 'footer' => '<p align="center">Fixture footer <a href="https://example.com">link</a> [[page]]</p>' ] ) );
Pdf\Store::saveBlock( $fixture, [ 'key' => 'fx-text', 'isNew' => true, 'title' => 'Fixture text', 'html' => '<h1>Heading one</h1><p>A paragraph with <strong>bold</strong>, <em>italic</em> and <a href="https://example.com">a link</a>. '. str_repeat( 'Lorem ipsum dolor sit amet. ', 40 ). '</p><h2>Heading two</h2><ul><li>First item</li><li>Second item<ul><li>Nested item</li></ul></li></ul><h3>Heading three</h3><ol><li>One</li><li>Two</li></ol><hr><p align="right">Right</p><p style="text-align:center">Centre</p><table><thead><tr><th width="60%">Item</th><th>Price</th></tr></thead><tbody><tr><td>Cell one</td><td>10,00</td></tr><tr><td>Cell two</td><td>20,00</td></tr></tbody></table><table border="0"><tr><td>No border</td><td>x</td></tr></table>' ] );
Pdf\Store::saveTemplate( $fixture, [ 'key' => 'fx-one', 'isNew' => true, 'name' => 'Fixture one', 'tag' => 'offer', 'blocks' => [
	[ 'type' => 'html', 'block' => 'fx-text' ], [ 'type' => 'html', 'block' => 'fx-text' ], [ 'type' => 'input', 'name' => 'note', 'label' => 'Note', 'multiline' => true, 'required' => false ], [ 'type' => 'pagebreak' ],
	[ 'type' => 'html', 'html' => '<p>[[name]] from [[/nowhere/text]]</p>' ], [ 'type' => 'slot', 'name' => 'rows' ],
	[ 'type' => 'signature', 'name' => 'seller', 'party' => 'owner', 'label' => 'The seller', 'page' => 'last', 'x' => 20, 'y' => 230, 'w' => 60, 'h' => 20, 'line' => true ],
] ] );
$fixtureData = [ 'tokens' => [ 'name' => 'Ada' ], 'slots' => [ 'rows' => '<table><tr><th>A</th><th>B</th></tr><tr><td>1</td><td>2</td></tr></table>' ], 'inputs' => [ 'note' => "one\ntwo" ] ];
$fixtureA = (string) Pdf::render( $fixture, 'fx-one', $fixtureData, [ 'title' => 'Fixture', 'date' => '2026-01-02', 'append' => '<h2>Appended</h2><p>Terms of the appendix.</p>' ] );
Pdf\Store::saveLayout( $fixture, $fixtureLayout( [ 'format' => 'letter', 'margins' => [ 15, 16, 17, 18 ], 'font' => 'times', 'size' => 12, 'footer' => '<p>[[page]]</p>' ] ) );
$fixtureB = (string) Pdf::render( $fixture, 'fx-one', $fixtureData );
Pdf\Store::saveLayout( $fixture, $fixtureLayout( [ 'margins' => [ 20, 20, 20, 20 ], 'font' => 'courier', 'size' => 9 ] ) );
$fixtureC = (string) Pdf::render( $fixture, 'fx-one', $fixtureData );

// The reference of the source: the fixtures of its suite rendered by the source on Nino 1.12 - fixtures/pdf-reference.json holds the SHA-256 and the length of
// each PDF - and by this feature on this kernel. pdf-scenarios.php renders them; the first three are the fixture above
$reference = json_decode( (string) file_get_contents( __DIR__. '/fixtures/pdf-reference.json' ), true );
$compared = $bootProject( 'pdf-reference' );
\Nino\Html::init( $compared );
\Nino\Html::addFills( $compared, [ '[[/project/company/general/name]]' => 'Acme & <Sons>', '[[/project/company/general/pages]]' => 'See [[pages]]' ] );
$made = ( require __DIR__. '/pdf-scenarios.php' )( $compared );
$differ = [];
foreach( $reference as $name => $expected )
	if( isset( $made[$name] ) === false || hash( 'sha256', (string) $made[$name] ) !== $expected['sha256'] || strlen( (string) $made[$name] ) !== $expected['bytes'] )
		$differ[] = $name;
check( 'byte identity with the source: '. count( $reference ). ' fixtures - the three layouts of the fixture, signature fields with the pictures of the layout and handed in, a table over two pages, the HTML of every element, a long text with an appended part, the preview, a design that names every selector, an owner\'s font - are the bytes the source made on Nino 1.12 (SHA-256 and length)'. ( $differ === [] ? '' : ' - differ: '. implode( ', ', $differ ) ),
	$differ === [] && count( $reference ) === 14 && array_keys( $made ) === array_keys( $reference ) && hash( 'sha256', $fixtureA ) === $reference['fixture-A']['sha256'] && hash( 'sha256', $fixtureB ) === $reference['fixture-B']['sha256']
	&& hash( 'sha256', $fixtureC ) === $reference['fixture-C']['sha256'] && strlen( $fixtureA ) === 4065 && strlen( $fixtureB ) === 3154 && strlen( $fixtureC ) === 2962 );

// A stock of the source is read, saved again without a change, and is the same bytes: the data below data/pdf and data/pdf-fonts that the source wrote
// (layout.php, blocks.php, templates/*.php, images/, the numbers of an owner's font) comes from its own suite - fixtures/data - and is not made by this feature
$stock = ninoSandbox( 'pdf-stock' );
\Nino\Features::activate( $stock, 'pdf' );
ninoUser( $stock, 'dev@example.com' );
$kept = static function( string $base ): array {

	$files = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		$files[ substr( (string) $file, strlen( $base ) ) ] = hash( 'sha256', (string) file_get_contents( (string) $file ) );

	ksort( $files );

	return $files;
};
$copyTree = static function( string $from, string $to ) use ( &$copyTree ): void {

	mkdir( $to, 0775, true );

	foreach( array_diff( (array) scandir( $from ), [ '.', '..' ] ) as $name )
		is_dir( $from. '/'. $name ) === true ? $copyTree( $from. '/'. $name, $to. '/'. $name ) : copy( $from. '/'. $name, $to. '/'. $name );
};
$copyTree( __DIR__. '/fixtures/data/pdf', \Nino\Filesystem::path( $stock, '/data/pdf' ) );
$copyTree( __DIR__. '/fixtures/data/pdf-fonts', \Nino\Filesystem::path( $stock, '/data/pdf-fonts' ) );
mkdir( \Nino\Filesystem::path( $stock, Pdf\Fonts\Metrics::DIR ), 0775, true );
foreach( [ 'Fixture-Regular.ttf' => pdfFontFixture(), 'Fixture-Bold.ttf' => pdfFontFixture( [ 'weight' => 700 ] ) ] as $name => $bytes ) {
	file_put_contents( \Nino\Filesystem::path( $stock, Pdf\Fonts\Metrics::DIR ). '/'. $name, $bytes );
	touch( \Nino\Filesystem::path( $stock, Pdf\Fonts\Metrics::DIR ). '/'. $name, 1700000000 );
}
$stockBefore = $kept( \Nino\Filesystem::path( $stock, '/data/pdf' ) ) + $kept( \Nino\Filesystem::path( $stock, '/data/pdf-fonts' ) );
$stockPost = static fn( string $action, array $data = [] ): array => ninoCall( $stock, [ \Nino\Admin\Admin::class, 'handlePost' ], $data, $action );
$read = Pdf::layout( $stock );
check( 'a stock of the source is read as it is: the layout, the library, the templates with their blocks, the pictures, and the numbers of an owner\'s font', $read['margins'] === [ 20, 22, 18, 24 ] && $read['logo'] === 'logo.png' && $read['signature'] === 'signature.png'
	&& array_keys( Pdf::blocks( $stock ) ) === [ 'fx-text', 'long' ] && array_keys( Pdf::templates( $stock ) ) === [ 'fx-one', 'long', 'seals', 'table' ] && count( Pdf::fields( $stock, 'seals' ) ) === 3 && count( Pdf::inputs( $stock, 'fx-one' ) ) === 1
	&& strlen( Pdf::picture( $stock, 'logo' ) ) === 195 && Pdf\Fonts\Metrics::load( $stock ) === null && Pdf\Fonts\Metrics::owned() === [ 'fixture' => 'Fixture' ] && $kept( \Nino\Filesystem::path( $stock, '/data/pdf-fonts' ) ) === array_diff_key( $stockBefore, $kept( \Nino\Filesystem::path( $stock, '/data/pdf' ) ) ) );

$statuses = [ $stockPost( 'pdf/layout/save', [ 'format' => $read['format'], 'margins' => $read['margins'], 'font' => $read['font'], 'size' => $read['size'], 'header' => $read['header'], 'footer' => $read['footer'], 'design' => $read['design'] ] )[0] ];
foreach( Pdf::blocks( $stock ) as $key => $block )
	$statuses[] = $stockPost( 'pdf/blocks/save', [ 'key' => $key, 'isNew' => false ] + $block )[0];
foreach( array_keys( Pdf::templates( $stock ) ) as $key ) {

	$template = Pdf::template( $stock, $key );

	$statuses[] = $stockPost( 'pdf/templates/save', [ 'key' => $key, 'isNew' => false, 'name' => $template['name'], 'tag' => $template['tag'], 'blocks' => $template['blocks'] ] )[0];
}
$stockAfter = $kept( \Nino\Filesystem::path( $stock, '/data/pdf' ) ) + $kept( \Nino\Filesystem::path( $stock, '/data/pdf-fonts' ) );
$changed = array_keys( array_filter( $stockAfter + $stockBefore, static fn( string $hash, string $file ): bool => ( $stockBefore[$file] ?? '' ) !== ( $stockAfter[$file] ?? '' ), ARRAY_FILTER_USE_BOTH ) );
check( 'round trip: the stock saved again through the panel\'s actions without a change - the layout, every block, every template - and the font cache read again is byte for byte what the source wrote, in '. count( $stockBefore ). ' files'. ( $changed === [] ? '' : ' - changed: '. implode( ', ', $changed ) ),
	$changed === [] && count( $stockBefore ) === 10 && $statuses === array_fill( 0, 7, 200 ) );
// The stock has tags the settings do not list: its templates are listed as such, a tag chosen anew is refused, none is allowed, and the one a template has stays valid for it
$template = Pdf::template( $stock, 'fx-one' );
$asSaved = [ 'key' => 'fx-one', 'isNew' => false, 'name' => $template['name'], 'blocks' => $template['blocks'] ];
$stockStatus = [ 'listed' => array_column( $stockPost( 'pdf/templates/list' )[1]['templates'], 'known', 'key' ), 'new' => $stockPost( 'pdf/templates/save', [ 'tag' => 'other' ] + $asSaved ), 'same' => $stockPost( 'pdf/templates/save', [ 'tag' => $template['tag'] ] + $asSaved )[0],
	'none' => $stockPost( 'pdf/templates/save', [ 'tag' => '' ] + $asSaved )[0] ];
check( 'a stock whose templates carry tags the settings do not list: the list marks them, a tag chosen anew is refused at its field, the tag the template has stays valid for it, and no tag is allowed', $stockStatus['listed'] === [ 'fx-one' => false, 'long' => false, 'seals' => false, 'table' => false ]
	&& $stockStatus['new'][0] === 400 && $stockStatus['new'][1]['code'] === 'pdf_tag' && $stockStatus['new'][1]['field'] === 'tag' && $stockStatus['same'] === 200 && $stockStatus['none'] === 200 && Pdf::template( $stock, 'fx-one' )['tag'] === ''
	&& $stockPost( 'pdf/templates/save', [ 'tag' => 'offer' ] + $asSaved )[0] === 400 );
\Nino\Filesystem::removeDir( ninoSandboxDir( $stock ) );
\Nino\Filesystem::removeDir( ninoSandboxDir( $compared ) );

// The parser: every documented property, on a selector it is read for
$all = <<<'CSS'
/* every property */
body { font-family: Courier; font-size: 11pt; font-weight: normal; font-style: italic; color: #112233; line-height: 1.5 }
h1, h2 { font-size: 1.5em; font-weight: bold; margin: 5mm 0 2mm 0; text-align: center }
p { margin: 1mm 0; color: #AABBCC }
ul { margin: 0 0 3mm 8mm }
th { padding: 1mm 2mm 3mm 4mm; background: #eeeeee; border-bottom: 0.5pt solid #000000; border-top: 0 }
td { padding: 2mm; border-bottom: 0 }
hr { border-top: 1pt solid #999999; margin: 3mm 0 }
a { text-decoration: none; color: #0000ff }
.address { margin: 10mm 0 12mm 0 }
.header { font-size: 8pt; border-bottom: 0.3pt solid #cccccc; margin: 0 0 6mm 0 }
.footer { border-top: 0.3pt solid #cccccc }
.signature { font-size: 0.7em; border-top: 0.3pt solid #1f5f8b; margin: 2mm 0 0 0 }
CSS;
$read = Sheet::parse( $all );
check( 'Sheet::parse() reads every documented property: the family, sizes in pt and em, weight, slant, colours (lower case), the line height, the margins of one, two and four values in mm, the paddings, the borders, the backgrounds, the alignment, the decoration',
	is_array( $read ) === true && $read['body'] === [ 'font-family' => 'courier', 'font-size' => [ 'pt', 11.0 ], 'font-weight' => 'normal', 'font-style' => 'italic', 'color' => '112233', 'line-height' => 1.5 ]
	&& $read['h1'] === $read['h2'] && $read['h1']['font-size'] === [ 'em', 1.5 ] && $read['h1']['margin'] === [ 5.0, 0.0, 2.0, 0.0 ] && $read['h1']['text-align'] === 'center'
	&& $read['p'] === [ 'margin' => [ 1.0, 0.0, 1.0, 0.0 ], 'color' => 'aabbcc' ] && $read['ul']['margin'] === [ 0.0, 0.0, 3.0, 8.0 ]
	&& $read['th'] === [ 'padding' => [ 1.0, 2.0, 3.0, 4.0 ], 'background' => 'eeeeee', 'border-bottom' => [ 0.5, '000000' ], 'border-top' => false ] && $read['td'] === [ 'padding' => [ 2.0, 2.0, 2.0, 2.0 ], 'border-bottom' => false ]
	&& $read['hr'] === [ 'border-top' => [ 1.0, '999999' ], 'margin' => [ 3.0, 0.0, 3.0, 0.0 ] ] && $read['a'] === [ 'text-decoration' => 'none', 'color' => '0000ff' ]
	&& $read['.header']['border-bottom'] === [ 0.3, 'cccccc' ] && $read['.footer']['border-top'] === [ 0.3, 'cccccc' ] && $read['.signature']['font-size'] === [ 'em', 0.7 ] && $read['.address']['margin'] === [ 10.0, 0.0, 12.0, 0.0 ] && $read['.signature']['margin'] === [ 2.0, 0.0, 0.0, 0.0 ] );
check( '...and a text of no rule at all is a sheet of no rules', Sheet::parse( '' ) === [] && Sheet::parse( "/* nothing\nhere */\n" ) === [] );

// Three values are top, horizontal, bottom - CSS's own reading - for margin and for padding
$three = Sheet::parse( "p { margin: 1mm 0 3mm }\ntd { padding: 4mm 5mm 6mm }" );
check( 'margin and padding take three values - top, horizontal, bottom: read as top, right, bottom, left with the right twice, written back as three, merged over the feature\'s rules', is_array( $three ) && $three['p']['margin'] === [ 1.0, 0.0, 3.0, 0.0 ] && $three['td']['padding'] === [ 4.0, 5.0, 6.0, 5.0 ]
	&& str_contains( Sheet::text( $three ), 'margin: 1mm 0 3mm }' ) === true && str_contains( Sheet::text( $three ), 'padding: 4mm 5mm 6mm }' ) === true && Sheet::parse( Sheet::text( $three ) ) == $three
	&& Sheet::merge( Sheet::defaults( 'helvetica', 10 ), $three )['p']['margin'] === [ 1.0, 0.0, 3.0, 0.0 ] && Sheet::merge( Sheet::defaults( 'helvetica', 10 ), $three )['td']['border-bottom'] === [ 0.4, '808080' ]
	&& Sheet::check( 'p { margin: 1mm 2mm 3mm 4mm 5mm }' )['code'] === 'value' );

// What it refuses, with the line, the kind and the word
$refusals = [
	'an unknown selector'						=> [ "h1 { color: #112233 }\n\nfooter { color: #112233 }", 3, 'selector', 'footer' ],
	'a selector with an id'					=> [ "#x { color: #112233 }", 1, 'selector', '#x' ],
	'an unknown property'						=> [ "p {\n\tcolor: #112233;\n\tfloat: left;\n}", 3, 'property', 'float' ],
	'a property the selector is not read for'=> [ "h1 { color: #112233 }\nh2 { background: #112233 }", 2, 'applies', 'background' ],
	'a colour that is no six digits'		=> [ "p { color: red }", 1, 'value', 'color: red' ],
	'a font that is none'						=> [ "p {}\n\nbody { font-family: Comic Sans }", 3, 'value', 'font-family: Comic Sans' ],
	'a size in px'								=> [ "p {}\nh1 { font-size: 12px }", 2, 'value', 'font-size: 12px' ],
	'a margin of five values'				=> [ "p { margin: 1mm 2mm 3mm 4mm 5mm }", 1, 'value', 'margin: 1mm 2mm 3mm 4mm 5mm' ],
	'a margin in em'							=> [ "p { margin: 1em }", 1, 'value', 'margin: 1em' ],
	'a border of another style'				=> [ "hr { border-top: 1pt dashed #000000 }", 1, 'value', 'border-top: 1pt dashed #000000' ],
	'a size out of range'						=> [ "p { font-size: 500pt }", 1, 'value', 'font-size: 500pt' ],
	'a declaration without a colon'			=> [ "p { color }", 1, 'declaration', 'color' ],
	'a rule that is not closed'				=> [ "p { color: #112233;\n\nh1 { color: #445566 }", 3, 'brace', '' ],
	'a brace with no rule'					=> [ "p {}\n}", 2, 'brace', '' ],
	'no brace at all'							=> [ "p color: #112233", 1, 'brace', '' ],
	'a line after a comment of several lines'=> [ "/* one\ntwo\nthree */\nh9 {}", 4, 'selector', 'h9' ],
	'a comment that is not closed'			=> [ "p { color: #112233 }\n\n/* the rest\nh1 { color: #445566 }", 3, 'comment', '' ],
	'a selector of a list on the third line'	=> [ "h1,\nh2,\nfoo { color: #112233 }", 3, 'selector', 'foo' ],
	'a missing semicolon before the next declaration'	=> [ "p {\n\tcolor: #112233\n\tmargin: 0\n}", 2, 'semicolon', 'color' ],
	'a declaration that lost its colon across lines'	=> [ "p {\n\tcolor\n\tfont-weight: bold\n}", 2, 'declaration', 'color' ],
	'a value that runs over a line and is none'		=> [ "p {\n\tcolor: red\n\t  bold\n}", 2, 'value', 'color: red' ],
	'a rule with no selector'				=> [ "p {}\n{ color: #112233 }", 2, 'noselector', '' ],
	'an empty selector in a list'			=> [ "h1,\n, h2 { color: #112233 }", 2, 'noselector', '' ],
	'a margin on a side nothing reads (p, left)' => [ "p { margin: 0 0 0 5mm }", 1, 'applies', 'margin-left' ],
];
$problems = [];
foreach( $refusals as $label => [ $css, $line, $code, $detail ] ) {

	$checked = Sheet::check( $css );
	$said = Sheet::parse( $css );

	if( isset( $checked['rules'] ) === true || $checked['line'] !== $line || $checked['code'] !== $code || $checked['detail'] !== $detail || str_contains( $checked['detail'], "\n" ) === true || is_string( $said ) === false || str_starts_with( $said, 'line '. $line. ': ' ) === false )
		$problems[] = $label. ' -> '. json_encode( $checked );
}
check( 'Sheet::parse() refuses by line, and names the kind and the word'. ( $problems === [] ? '' : ' - '. implode( '; ', $problems ) ), $problems === [] && count( $refusals ) === 24 );
check( '...a selector list is a rule for each: one that holds a selector it does not know is refused whole, as is a property one of its selectors is not read for', Sheet::check( 'h1, nope { color: #112233 }' )['code'] === 'selector' && Sheet::check( 'h1, td { background: #112233 }' )['code'] === 'applies'
	&& Sheet::check( 'H1 { COLOR: #ABCDEF }' )['rules'] === [ 'h1' => [ 'color' => 'abcdef' ] ] );

// The defaults are the sheet, and the text is the defaults
$defaults = Sheet::defaults( 'helvetica', 10 );
check( 'the feature\'s own rules are a sheet in the same notation: their text is read back into the same rules, for every size the layout takes', (function() use ( $defaults ): bool {
	foreach( [ 'helvetica', 'times', 'courier' ] as $family )
		for( $size = Pdf::SIZE_MIN; $size <= Pdf::SIZE_MAX; $size++ )
			if( Sheet::parse( Sheet::text( Sheet::defaults( $family, $size ) ) ) != Sheet::defaults( $family, $size ) )
				return false;
	return Sheet::check( Sheet::text( $defaults ) )['rules'] != [];
})() );
check( '...and they hold the numbers the engine sets with: 1.8, 1.4 and 1.15 times the base size for the headings, a rule of 0.5 pt, 1.5 mm of padding, a head rule of 0.8 pt, a hairline of 0.4 pt, a gap of 4 mm around the bands, the label of a signature 0.8 of the base size',
	$defaults['h1']['font-size'] === [ 'em', 1.8 ] && $defaults['h2']['font-size'] === [ 'em', 1.4 ] && $defaults['h3']['font-size'] === [ 'em', 1.15 ] && $defaults['hr']['border-top'][0] === 0.5
	&& $defaults['td']['padding'] === [ 1.5, 1.5, 1.5, 1.5 ] && $defaults['th']['border-bottom'][0] === 0.8 && $defaults['td']['border-bottom'][0] === 0.4 && $defaults['.header']['margin'][2] === 4.0 && $defaults['.footer']['margin'][0] === 4.0
	&& $defaults['.signature']['font-size'] === [ 'em', 0.8 ] && $defaults['body']['font-size'] === [ 'pt', 10.0 ] && $defaults['body']['line-height'] === 1.35 );

// Merged per property
$merged = Sheet::merge( $defaults, [ 'h1' => [ 'color' => '1f5f8b', 'font-size' => [ 'pt', 20.0 ] ], 'td' => [ 'border-bottom' => false ] ] );
check( 'merge() lays the owner\'s rules over the feature\'s property by property: the properties named win, the others of the same selector stay, the selectors not named are untouched',
	$merged['h1']['font-size'] === [ 'pt', 20.0 ] && $merged['h1']['color'] === '1f5f8b' && $merged['h1']['font-weight'] === 'bold' && $merged['h1']['margin'] === $defaults['h1']['margin']
	&& $merged['td']['border-bottom'] === false && $merged['td']['padding'] === [ 1.5, 1.5, 1.5, 1.5 ] && $merged['h2'] === $defaults['h2'] && $defaults['h1']['font-size'] === [ 'em', 1.8 ] );

// The layout holds the design; design() and sheet() answer it
$kept = Pdf::layout( $appData );
$keep = static fn( array $change ): array => $change + [ 'format' => $kept['format'], 'margins' => $kept['margins'], 'font' => $kept['font'], 'size' => $kept['size'], 'header' => $kept['header'], 'footer' => $kept['footer'] ];
$plain = $keep( [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '', 'design' => '' ] );
check( 'a layout with no design answers an empty one, design() the feature\'s rules alone and sheet() their text - for the font and the size of the layout', ( $plain['design'] === '' ) && Pdf::DEFAULTS['design'] === '' && $post( 'pdf/layout/save', $plain )[0] === 200 && Pdf::layout( $appData )['design'] === ''
	&& Pdf::design( $appData ) == Sheet::defaults( 'helvetica', 10 ) && Pdf::sheet( $appData ) === Sheet::text( Sheet::defaults( 'helvetica', 10 ) ) && str_contains( Pdf::sheet( $appData ), 'font-size: 10pt' ) === true && str_contains( Pdf::sheet( $appData ), 'font-family: Helvetica' ) === true );
[ $status, $body ] = $post( 'pdf/layout/save', [ 'design' => "h1 { font-size: 20pt; color: #1F5F8B }\r\n\r\nbody { font-size: 1.5em }\n", 'size' => 12, 'font' => 'times' ] + $plain );
check( 'the design is saved with the layout - trimmed, line breaks as \n -, answered, and design() is the two merged: a body in em is a factor of the size of the layout, in points', $status === 200 && $body['layout']['design'] === "h1 { font-size: 20pt; color: #1F5F8B }\n\nbody { font-size: 1.5em }" && Pdf::layout( $appData )['design'] === $body['layout']['design']
	&& Pdf::design( $appData )['h1']['font-size'] === [ 'pt', 20.0 ] && Pdf::design( $appData )['h1']['color'] === '1f5f8b' && Pdf::design( $appData )['h1']['font-weight'] === 'bold' && Pdf::design( $appData )['body']['font-size'] === [ 'pt', 18.0 ]
	&& Pdf::design( $appData )['body']['font-family'] === 'times' && $body['sheet'] === Pdf::sheet( $appData ) && str_contains( Pdf::sheet( $appData ), 'font-size: 12pt' ) === true && str_contains( Pdf::sheet( $appData ), 'font-family: Times' ) === true );
$stored = Pdf::layout( $appData );
[ $status, $body ] = $post( 'pdf/layout/save', $keep( [ 'size' => 12, 'font' => 'times' ] ) );
check( '...a save that does not name the design leaves it as it is', $status === 200 && Pdf::layout( $appData )['design'] === $stored['design'] );

// A bad sheet is refused with its line, and the layout stays as it was
foreach( [
	'an unknown property'		=> [ "h1 { color: #112233 }\np { float: left }", 'pdf_design_property', [ 2, 'float' ] ],
	'an unknown selector'		=> [ "\n\nfooter { color: #112233 }", 'pdf_design_selector', [ 3, 'footer' ] ],
	'a bad value'				=> [ "h1 { color: red }", 'pdf_design_value', [ 1, 'color: red' ] ],
	'a property not read'		=> [ "p {}\nh1 { background: #112233 }", 'pdf_design_applies', [ 2, 'background', 'h1' ] ],
	'a missing brace'			=> [ "p { color: #112233;", 'pdf_design_brace', [ 1, '' ] ],
	'a declaration'				=> [ "p { color }", 'pdf_design_declaration', [ 1, 'color' ] ],
	'a missing semicolon'		=> [ "p {\n\tcolor: #112233\n\tmargin: 0 0 1mm 0\n}", 'pdf_design_semicolon', [ 2, 'color' ] ],
	'a sheet too long'			=> [ str_repeat( "/* x */\n", 1100 ), 'pdf_design_long', [ Pdf::MAX_DESIGN ] ],
] as $label => [ $css, $code, $params ] ) {
	[ $status, $body ] = $post( 'pdf/layout/save', [ 'design' => $css ] + $keep( [ 'size' => 14, 'font' => 'courier' ] ) );
	check( 'layout/save refuses '. $label. ' with 400, the code, the params - line first - and the field design; nothing is written, not even the other fields', $status === 400 && $body['code'] === $code && $body['params'] === $params && $body['field'] === 'design' && Pdf::layout( $appData ) === $stored );
}
check( '...every code of a sheet\'s refusal has its sentence in both languages, and says the line', (function() use ( $sentence ): bool {
	foreach( [ 'long', 'type', 'brace', 'comment', 'semicolon', 'noselector', 'declaration', 'selector', 'property', 'applies', 'value' ] as $kind )
		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( $sentence( 'pdf_design_'. $kind, $locale ) === '' || ( in_array( $kind, [ 'long', 'type' ], true ) === false && str_contains( $sentence( 'pdf_design_'. $kind, $locale ), '%s' ) === false ) )
				return false;
	return true;
})() );
$stale = \Nino\Filesystem::getFileContent( $appData, Pdf::LAYOUT, [] );
\Nino\Filesystem::putFileContent( $appData, Pdf::LAYOUT, [ 'design' => "h1 { color: #112233 }\np { float: left }" ] + $stale );
$handEdited = $post( 'pdf/layout/get' )[1];
check( 'a design in the file that is no sheet: layout() answers the text as it is stored, design() does not read it - the rules of the feature stand alone - and layout/get says why, with the line; a sheet carries no refusal', Pdf::layout( $appData )['design'] === "h1 { color: #112233 }\np { float: left }"
	&& Pdf::design( $appData ) == Sheet::defaults( (string) Pdf::layout( $appData )['font'], (int) Pdf::layout( $appData )['size'] ) && $handEdited['layout']['design'] === "h1 { color: #112233 }\np { float: left }" && $handEdited['refusal'] === [ 'code' => 'pdf_design_property', 'params' => [ 2, 'float' ] ] );
\Nino\Filesystem::putFileContent( $appData, Pdf::LAYOUT, $stale );
check( '...and the refusal is the code and the params of the sentence a save of the same text says - the words of the panel, none of the parser\'s English - and null for a design that parses and for none', $post( 'pdf/layout/get' )[1]['refusal'] === null && Sheet::parse( '' ) === []
	&& $handEdited['refusal']['code'] === $post( 'pdf/layout/save', [ 'design' => "h1 { color: #112233 }\np { float: left }" ] + $plain )[1]['code'] && $post( 'pdf/layout/save', [ 'design' => "h1 { color: #112233 }\np { float: left }" ] + $plain )[1]['params'] === $handEdited['refusal']['params'] );
$post( 'pdf/layout/get' );

// The engine honours the rules. Each case sets one design on a plain layout and lays a text out without a template of the project
$refusedDesigns = [];
$plainLayout = static function( string $design, array $change = [] ) use ( $post, $plain, &$refusedDesigns ): int {

	$status = $post( 'pdf/layout/save', [ 'design' => $design ] + $change + $plain )[0];

	if( $status !== 200 )
		$refusedDesigns[] = $design;

	return $status;
};
$laid = static function( string $html, array $options = [], array $blocks = [] ) use ( &$appData ): string {
	return Pdf::compose( $appData, [ 'name' => 'T', 'tag' => 'offer', 'blocks' => array_merge( [ [ 'type' => 'html', 'html' => $html ] ], $blocks ) ], [], $options, false );
};
$colour = static fn( string $hex ): string => implode( ' ', array_map( static fn( string $channel ): string => Pdf\Document\Document::num( round( hexdec( $channel ) / 255, 2 ) ), str_split( $hex, 2 ) ) );
// Where a word of the text stands: x and y of its Td
$at = static function( string $pdf, string $word ) use ( $content ): array {
	preg_match( '#([\d.]+) ([\d.]+) Td \('. preg_quote( $word, '#' ). '\) Tj#', $content( $pdf, 0 ), $found );
	return [ (float) ( $found[1] ?? -1 ), (float) ( $found[2] ?? -1 ) ];
};
$mm = Pdf\Document\Document::MM;
$text = '<h1>Heading</h1><p>First</p><p>Second</p>';

$plainLayout( '' );
$plainPdf = $laid( $text );
check( 'the base: a heading of 1.8 times the size, no colour operator in a page that has no colour', str_contains( $content( $plainPdf, 0 ), ' 18 Tf' ) === true && preg_match( '/ (rg|RG)\b/', $content( $plainPdf, 0 ) ) === 0 );

$plainLayout( 'h1 { font-size: 24pt }' );
check( 'an override of the size of h1 sets the heading in it - in points, and the em of the same selector is a factor of the base', str_contains( $content( $laid( $text ), 0 ), ' 24 Tf' ) === true && str_contains( $content( $laid( $text ), 0 ), ' 18 Tf' ) === false
	&& ( $plainLayout( 'h1 { font-size: 2em }' ) === 200 ) && str_contains( $content( $laid( $text ), 0 ), ' 20 Tf' ) === true );

$plainLayout( 'h1 { color: #1f5f8b }' );
$tinted = $content( $laid( $text ), 0 );
check( 'a colour: the content stream carries the fill colour operator - two decimals a channel - for the text of the selector, in a state of its own, and the text of the others is not drawn in it', str_contains( $tinted, 'q '. $colour( '1f5f8b' ). ' rg BT' ) === true && substr_count( $tinted, ' rg ' ) === 1
	&& preg_match( '/\(Heading\) Tj ET\nQ\n/', $tinted ) === 1 && preg_match( '/\n(BT [^\n]+\(First\) Tj ET)\n/', $tinted ) === 1 );
$plainLayout( 'p { color: #808080 }' );
check( '...a grey is written as a grey (g), and a colour that is black is no operator at all', str_contains( $content( $laid( $text ), 0 ), 'q 0.5 g BT' ) === true && ( $plainLayout( 'p { color: #000000 }' ) === 200 ) && preg_match( '/ (rg|g|RG|G) BT/', $content( $laid( $text ), 0 ) ) === 0 );

$apart = static fn( string $pdf, string $from, string $to ): float => $at( $pdf, $from )[1] - $at( $pdf, $to )[1];
$plainLayout( '' );
$base = $apart( $laid( $text ), 'First', 'Second' );
$plainLayout( 'p { margin: 0 0 20mm 0 }' );
$wide = $apart( $laid( $text ), 'First', 'Second' );
$plainLayout( 'p { margin: 10mm 0 20mm }' );
$threeLayout = $laid( $text );
check( 'a margin of three values in the engine: the top and the bottom differ - the space under a paragraph is the larger of its bottom and the next one\'s top (20 mm), above the first one the larger of the heading\'s bottom and its top (10 mm)', abs( ( $apart( $threeLayout, 'First', 'Second' ) - $base ) / $mm - ( 20 - 2.8575 ) ) < 0.01
	&& abs( ( $apart( $threeLayout, 'Heading', 'First' ) - $apart( $plainPdf, 'Heading', 'First' ) ) / $mm - ( 10 - 2.38125 ) ) < 0.01 );
$plainLayout( 'p { margin: 12mm 0 0 0 }' );
$above = $apart( $laid( $text ), 'First', 'Second' );
check( 'the bottom margin of p is the space after a paragraph: a larger one moves the next paragraph down by the difference (in mm); the top margin is the space above, the larger of the two between paragraphs', abs( ( $wide - $base ) / $mm - ( 20 - 2.8575 ) ) < 0.01
	&& abs( ( $above - $base ) / $mm - ( 12 - 2.8575 ) ) < 0.01 );
$plainLayout( 'p { line-height: 2 }' );
$lineGap = static function( string $html ) use ( $laid, $content ): float {
	preg_match_all( '/ ([\d.]+) Td \(word/', $content( $laid( $html ), 0 ), $found );
	return count( $found[1] ) > 3 ? (float) $found[1][0] - (float) $found[1][1] : -1.0;
};
$ownLines = $lineGap( '<p>'. str_repeat( 'word ', 80 ). '</p>' );
$headLines = $plainLayout( 'h1 { line-height: 3 }' ) === 200 ? $lineGap( '<h1>'. str_repeat( 'word ', 40 ). '</h1>' ) : -1.0;
$plainLayout( '' );
check( 'line-height of p and of h1 is a factor of their font size for the lines of that selector alone: p at 2 gives 20 pt lines, h1 at 3 gives 54 pt lines - the plain ones are 13.5 pt and 24.3 pt', abs( $ownLines - 20 ) < 0.01 && abs( $headLines - 54 ) < 0.01
	&& abs( $lineGap( '<p>'. str_repeat( 'word ', 80 ). '</p>' ) - 13.5 ) < 0.01 && abs( $lineGap( '<h1>'. str_repeat( 'word ', 40 ). '</h1>' ) - 24.3 ) < 0.01 );
$plainLayout( 'body { line-height: 2 }' );
check( 'line-height of the body is a factor of the font size for every line: lines of a long paragraph are further apart', (function() use ( $laid, $content, $plainLayout ): bool {
	$long = '<p>'. str_repeat( 'word ', 80 ). '</p>';
	preg_match_all( '/ ([\d.]+) Td \(word/', $content( $laid( $long ), 0 ), $two );
	$plainLayout( '' );
	preg_match_all( '/ ([\d.]+) Td \(word/', $content( $laid( $long ), 0 ), $one );
	return count( $two[1] ) > 3 && count( $one[1] ) > 3 && abs( ( (float) $two[1][0] - (float) $two[1][1] ) - 2 * 10 ) < 0.01 && abs( ( (float) $one[1][0] - (float) $one[1][1] ) - 13.5 ) < 0.01;
})() );

$table = '<table><thead><tr><th>Head</th></tr></thead><tbody><tr><td>Cell</td></tr></tbody></table>';
$plainLayout( 'th { background: #ffeeaa; border-bottom: 2pt solid #112233 }' );
$cells = $content( $laid( $table ), 0 );
check( 'th: a background is a box filled in the colour, as high as the row and drawn before the text; the border is a line of the width and the colour under the row', preg_match( '/q '. preg_quote( $colour( 'ffeeaa' ), '/' ). ' rg [\d.]+ [\d.]+ [\d.]+ [\d.]+ re f Q\n/', $cells, $fill, PREG_OFFSET_CAPTURE ) === 1
	&& strpos( $cells, '(Head) Tj' ) > $fill[0][1] && str_contains( $cells, 'q 2 w '. $colour( '112233' ). ' RG ' ) === true && str_contains( $cells, 'q 0.8 w 0 G ' ) === false );
$plainLayout( 'td { border-bottom: 0 } th { border-bottom: 0 }' );
check( '...a border of 0 draws no line, and a table says the same of itself with border="0"', preg_match( '/ w [\d. ]+ m [\d. ]+ l S Q/', $content( $laid( $table ), 0 ) ) === 0 );
$plainLayout( '' );
check( '...and the rules of the feature draw the head rule of 0.8 pt in black and the hairline of 0.4 pt in grey, the hairline not in a table of border="0"', str_contains( $content( $laid( $table ), 0 ), 'q 0.8 w 0 G ' ) === true && str_contains( $content( $laid( $table ), 0 ), 'q 0.4 w 0.5 G ' ) === true
	&& str_contains( $content( $laid( str_replace( '<table>', '<table border="0">', $table ) ), 0 ), 'q 0.4 w' ) === false );

$cellX = static fn( string $pdf ): float => $at( $pdf, 'Cell' )[0];
$before = $cellX( $laid( $table ) );
$plainLayout( 'td { padding: 5mm 7mm 5mm 9mm }' );
$padded = $laid( $table );
$hairline = static function( string $pdf ) use ( $content ): float {
	preg_match( '/q 0\.4 w 0\.5 G [\d.]+ ([\d.]+) m/', $content( $pdf, 0 ), $found );
	return (float) ( $found[1] ?? -1 );
};
$plainLayout( '' );
$hairlineBase = $hairline( $laid( $table ) );
check( 'td: padding is the room inside the cell - the text stands the left padding from the cell\'s edge and as much lower as the top, and the row is as high as the paddings add up (top and bottom, 10 mm instead of 3)', abs( ( $cellX( $padded ) - $before ) / $mm - ( 9 - 1.5 ) ) < 0.01
	&& $at( $padded, 'Cell' )[1] < $at( $laid( $table ), 'Cell' )[1] && abs( ( $hairlineBase - $hairline( $padded ) ) / $mm - ( 10 - 3 ) ) < 0.01 );

$link = '<p>See <a href="x">this link</a> now</p>';
$underlines = static fn( string $pdf ): int => preg_match_all( '/q [\d.]+ w [\d. ]+ m [\d. ]+ l S Q/', $content( $pdf, 0 ) );
$plainLayout( '' );
$underlined = $underlines( $laid( $link ) );
$plainLayout( 'a { text-decoration: none }' );
$plainLink = $underlines( $laid( $link ) );
$plainLayout( 'a { color: #ff0000 }' );
$redLink = $content( $laid( $link ), 0 );
check( 'a: the link is underlined as it was, text-decoration: none takes the line away, a colour sets the text - and its underline - in it', $underlined === 1 && $plainLink === 0 && str_contains( $redLink, 'q 1 0 0 rg BT' ) === true && str_contains( $redLink, ' w 1 0 0 RG ' ) === true );

$signature = [ [ 'type' => 'signature', 'name' => 'seal', 'party' => 'owner', 'label' => 'Seal', 'page' => 'last', 'x' => 20, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => true ] ];
$plainLayout( '' );
$box = $content( $laid( '<p>Text</p>', [], $signature ), 0 );
$plainLayout( '.signature { border-top: 2pt solid #ff0000; font-size: 12pt; color: #00aa00 }' );
$sealed = $content( $laid( '<p>Text</p>', [], $signature ), 0 );
$plainLayout( '.signature { border-top: 0 }' );
$open = $content( $laid( '<p>Text</p>', [], $signature ), 0 );
check( '.signature: the line of a signature field is 0.5 pt black as it was; an override gives it the width and the colour, the label the size and the colour, border-top: 0 takes the line away and keeps the label',
	str_contains( $box, 'q 0.5 w 0 G ' ) === true && str_contains( $box, ' 8 Tf ' ) === true && str_contains( $sealed, 'q 2 w 1 0 0 RG ' ) === true && str_contains( $sealed, ' 12 Tf ' ) === true && str_contains( $sealed, 'q 0 0.67 0 rg BT' ) === true
	&& str_contains( $open, ' w ' ) === false && str_contains( $open, '(Seal) Tj' ) === true );

$plainLayout( 'hr { border-top: 3pt solid #ff0000; margin: 8mm 0 }' );
check( 'hr: the rule is the width and the colour of border-top, the margin the space around it', str_contains( $content( $laid( '<p>Over</p><hr><p>Under</p>' ), 0 ), 'q 3 w 1 0 0 RG ' ) === true );
$plainLayout( '' );
$plainHr = $apart( $laid( '<p>Over</p><hr><p>Under</p>' ), 'Over', 'Under' );
$plainLayout( 'hr { margin: 8mm 0 }' );
check( '...a margin of 8 mm moves the next paragraph by the difference', abs( ( $apart( $laid( '<p>Over</p><hr><p>Under</p>' ), 'Over', 'Under' ) - $plainHr ) / $mm - ( ( 8 - 2.8575 ) + ( 8 - 1.905 ) ) ) < 0.01 );

$plainLayout( 'ul { margin: 0 0 3mm 15mm } li { margin: 0 0 4mm 0 }' );
$list = $laid( '<ul><li>One</li><li>Two</li></ul>' );
$plainLayout( '' );
check( 'ul: the left margin is the indent of a level, li: the bottom margin the space between items', abs( ( $at( $list, 'One' )[0] - $at( $laid( '<ul><li>One</li><li>Two</li></ul>' ), 'One' )[0] ) / $mm - 9 ) < 0.01
	&& ( $at( $laid( '<ul><li>One</li><li>Two</li></ul>' ), 'One' )[1] - $at( $laid( '<ul><li>One</li><li>Two</li></ul>' ), 'Two' )[1] ) < ( $at( $list, 'One' )[1] - $at( $list, 'Two' )[1] ) );

$plainLayout( 'body { font-family: Courier; font-size: 9pt }' );
check( 'body: the family and the size of the text; they win over the layout\'s own', str_contains( $laid( '<p>Text</p>' ), '/BaseFont /Courier' ) === true && str_contains( $content( $laid( '<p>Text</p>' ), 0 ), ' 9 Tf ' ) === true && str_contains( $laid( '<p>Text</p>' ), '/BaseFont /Helvetica' ) === false );
$plainLayout( 'th { font-weight: normal } td { font-style: italic }' );
check( 'th and td: the weight and the slant of a cell are the rule\'s - a th that is not bold is Helvetica, a td in italic Oblique', str_contains( $laid( $table ), '/BaseFont /Helvetica-Bold' ) === false && str_contains( $laid( $table ), '/BaseFont /Helvetica-Oblique' ) === true );

check( 'Allow keeps one class, address, on a p or a div - in any case, written in lower case - and no other: not another word, not a list, not on a span', Allow::html( '<p class="Address">a</p><div class="address">b</div><p class="address big">c</p><p class="big">d</p><span class="address">e</span><h1 class="address">f</h1>' ) === '<p class="address">a</p><div class="address">b</div><p>c</p><p>d</p><span>e</span><h1>f</h1>' );
$plainLayout( '.address { color: #ff0000; margin: 0 0 9mm 0 }' );
$addressed = $content( $laid( '<p class="address">Street 1</p><p>Plain</p>' ), 0 );
check( '.address: a p or a div with class="address" is set by the rules of p with those of .address over them; the others are not', str_contains( $addressed, 'q 1 0 0 rg BT' ) === true && substr_count( $addressed, ' rg ' ) === 1 && preg_match( '/q 1 0 0 rg BT[^\n]*\(Street 1\) Tj/', $addressed ) === 1 );

$bands = [ 'header' => '<p>Head</p>', 'footer' => '<p>Foot</p>' ];
$plainLayout( '', $bands );
$gapBody = $at( $laid( '<p>Body</p>' ), 'Body' )[1];
$plainLayout( '.header { font-size: 7pt; color: #0000ff; border-bottom: 1pt solid #00ff00; margin: 0 0 10mm 0 } .footer { font-size: 6pt; border-top: 1pt solid #ff00ff; margin: 10mm 0 0 0 }', $bands );
$banded = $content( $laid( '<p>Body</p>' ), 0 );
check( '.header and .footer: the text of the bands has its own size and colour, the border is a line at the foot of the header and at the head of the footer, and the body is set in none of it',
	str_contains( $banded, 'q 0 0 1 rg BT' ) === true && str_contains( $banded, ' 7 Tf ' ) === true && str_contains( $banded, ' 6 Tf ' ) === true && str_contains( $banded, 'q 1 w 0 1 0 RG ' ) === true && str_contains( $banded, 'q 1 w 1 0 1 RG ' ) === true
	&& preg_match( '/ 10 Tf [\d.]+ [\d.]+ Td \(Body\)/', $banded ) === 1 && substr_count( $banded, ' rg ' ) === 1 );
$plainLayout( '.header { margin: 0 0 10mm 0 }', $bands );
check( '...the bottom margin of .header is the gap between it and the text: 6 mm more, the text 6 mm lower (the header is a little shorter in 7 pt only where the size is set)', abs( ( $gapBody - $at( $laid( '<p>Body</p>' ), 'Body' )[1] ) / $mm - 6 ) < 0.01 );
$plainLayout( '' );
check( 'every design the cases above set was accepted by the layout\'s save'. ( $refusedDesigns === [] ? '' : ' - refused: '. implode( ' | ', $refusedDesigns ) ), $refusedDesigns === [] );

// Every property a selector accepts is a property the engine reads: for each pair of the table an override with a value of its own renders other bytes
// than the feature's rules alone, over a text that has every element, a header, a footer and a signature field
$bands = [ 'header' => '<p>Head</p>', 'footer' => '<p>Foot</p>' ];
$everything = '<p>Lead</p><h1>Head one</h1><h2>Head two</h2><h3>Head three</h3><p class="address">Street 1</p><p class="address">'. str_repeat( 'town ', 40 ). '</p><p>A short paragraph</p><ul><li>One</li><li>Two</li></ul><ol><li>Uno</li><li>Dos</li></ol><hr><table><thead><tr><th>Head</th></tr></thead><tbody><tr><th>Row</th><td>Cell</td></tr><tr><th>Row</th><td>Next</td></tr><tr><td>Third</td></tr><tr><td>Fourth</td></tr></tbody></table><p>See <a href="x">link</a> now</p><p>'. str_repeat( 'word ', 1500 ). '</p>';
$plainLayout( '', $bands );
$reference = $laid( $everything, [], $signature );
$unread = [];
$pairs = 0;
foreach( Sheet::APPLIES as $selector => $properties )
	foreach( $properties as $property ) {

		$value = match( $property ) {
			'font-family'		=> 'Courier',
			'font-size'			=> '17pt',
			'font-weight'		=> in_array( $selector, [ 'h1', 'h2', 'h3', 'th', 'tr:nth-child(even) th', 'tr:nth-child(odd) th' ], true ) === true ? 'normal' : 'bold',
			'font-style'		=> 'italic',
			'color'					=> '#ff0000',
			'line-height'		=> '2.6',
			'text-align'		=> 'right',
			'margin'				=> implode( ' ', array_map( static fn( bool $read ): string => $read === true ? '9mm' : '0', Sheet::MARGINS[$selector] ) ),
			'padding'				=> '6mm',
			'border-top', 'border-bottom' => '2pt solid #ff0000',
			'background'		=> '#ffcc00',
			'text-decoration' => 'none',
		};

		$pairs++;

		if( $plainLayout( $selector. ' { '. $property. ': '. $value. ' }', $bands ) !== 200 || $laid( $everything, [], $signature ) === $reference )
			$unread[] = $selector. ' '. $property;
	}
// ... and each side of a margin that a selector reads is read on its own
foreach( Sheet::MARGINS as $selector => $read )
	foreach( [ 'top', 'right', 'bottom', 'left' ] as $index => $name )
		if( $read[$index] === true ) {

			$pairs++;

			// The other sides stay what the feature sets, so that the one side is all that differs
			$margin = Sheet::defaults( 'helvetica', 10 )[$selector]['margin'] ?? Sheet::defaults( 'helvetica', 10 )['p']['margin'];
			$margin[$index] = 9.0;

			if( $plainLayout( $selector. ' { margin: '. implode( ' ', array_map( static fn( float $mm ): string => rtrim( rtrim( number_format( $mm, 7, '.', '' ), '0' ), '.' ). 'mm', $margin ) ). ' }', $bands ) !== 200 || $laid( $everything, [], $signature ) === $reference )
				$unread[] = $selector. ' margin-'. $name;
		}
$plainLayout( '' );
check( 'every property a selector accepts is read by the engine: an override of each ('. $pairs. ' pairs of the table) changes the bytes of a text that has every element, a header, a footer and a signature field'. ( $unread === [] ? '' : ' - not read: '. implode( ', ', $unread ) ), $unread === [] && $pairs === array_sum( array_map( 'count', Sheet::APPLIES ) ) + array_sum( array_map( static fn( array $read ): int => count( array_filter( $read ) ), Sheet::MARGINS ) ) && $pairs > 110 );

// ... and the sides of a margin: a side that is read takes a distance, one that is not takes 0 or the rule is refused with the line and the side
$sides = [];
foreach( Sheet::MARGINS as $selector => $read )
	foreach( [ 'top', 'right', 'bottom', 'left' ] as $index => $name ) {

		$margin = implode( ' ', array_map( static fn( int $side ): string => $side === $index ? '5mm' : '0', [ 0, 1, 2, 3 ] ) );
		$checked = Sheet::check( "\n". $selector. ' { margin: '. $margin. ' }' );

		if( $read[$index] === true ? isset( $checked['rules'] ) === false : ( $checked['code'] ?? '' ) !== 'applies' || $checked['detail'] !== 'margin-'. $name || $checked['selector'] !== $selector || $checked['line'] !== 2 )
			$sides[] = $selector. ' '. $name;
	}
check( '...every selector with a margin has its sides: the sides the engine reads take a distance, any other side is refused with a distance that is not 0 - code applies, the side as margin-left, the line - and accepts 0'. ( $sides === [] ? '' : ' - '. implode( ', ', $sides ) ), $sides === [] && count( Sheet::MARGINS ) === 13
	&& ( static function(): array { $keys = array_keys( Sheet::MARGINS ); sort( $keys ); return $keys; } )() === ( static function(): array { $keys = array_values( array_filter( array_keys( Sheet::APPLIES ), static fn( string $selector ): bool => in_array( 'margin', Sheet::APPLIES[$selector], true ) ) ); sort( $keys ); return $keys; } )()
	&& isset( Sheet::check( 'li { margin: 0 0 4mm 0 } ul { margin: 0 0 3mm 8mm } .header { margin: 0 0 6mm 0 } p { margin: 5mm 0 2mm }' )['rules'] ) === true );

// text-align where it is read: the items of a list, the text of a band
$plainLayout( 'li { text-align: right }' );
$listed = $laid( '<ul><li>One</li></ul>' );
$plainLayout( '' );
check( 'text-align of li sets the items of a list, of .header and .footer the text of the bands: the item stands at the right of the column, a band\'s text at the right of the page', $at( $listed, 'One' )[0] > 400 && $at( $laid( '<ul><li>One</li></ul>' ), 'One' )[0] < 100 );
$plainLayout( '.header { text-align: right } .footer { text-align: center }', $bands );
$aligned = $laid( '<p>Body</p>' );
$plainLayout( '', $bands );
check( '...and the text of a band follows the text-align of its own rules: .header right, .footer centred, and left where none is named', $at( $aligned, 'Head' )[0] > 450 && $at( $aligned, 'Foot' )[0] > 250 && $at( $aligned, 'Foot' )[0] < 320 && $at( $laid( '<p>Body</p>' ), 'Head' )[0] < 100 && $at( $laid( '<p>Body</p>' ), 'Foot' )[0] < 100 );

// The colour of the body is the colour of the signature's label, which has none of its own; a text a person typed is a paragraph of p
$plainLayout( 'body { color: #ff0000 }' );
$redLabel = $content( $laid( '<p>Text</p>', [], $signature ), 0 );
$plainLayout( 'body { color: #ff0000 } .signature { color: #00aa00 }' );
$ownLabel = $content( $laid( '<p>Text</p>', [], $signature ), 0 );
check( 'the label of a signature field follows the colour of the body until .signature names one of its own', preg_match( '/q 1 0 0 rg BT[^\n]*\(Seal\) Tj/', $redLabel ) === 1 && preg_match( '/q 0 0.67 0 rg BT[^\n]*\(Seal\) Tj/', $ownLabel ) === 1 );
$inputTemplate = [ 'name' => 'T', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'input', 'name' => 'note', 'label' => 'Note', 'multiline' => false, 'required' => false ] ] ];
$typed = static fn(): string => Pdf::compose( $appData, $inputTemplate, [ 'inputs' => [ 'note' => 'Typed words' ] ], [], false );
$plainLayout( '' );
$typedBase = $typed();
$plainLayout( 'p { font-size: 15pt; color: #ff0000; font-family: Courier; text-align: right }' );
$typedOwn = $typed();
$plainLayout( 'body { font-size: 13pt }' );
$typedBody = $typed();
$plainLayout( '' );
check( 'a text a person typed in an input is set as a paragraph: the size, the colour, the family and the alignment of p, over the body\'s', str_contains( $content( $typedOwn, 0 ), 'q 1 0 0 rg BT' ) === true && str_contains( $content( $typedOwn, 0 ), ' 15 Tf ' ) === true && str_contains( $typedOwn, '/BaseFont /Courier' ) === true
	&& $at( $typedOwn, 'Typed words' )[0] > 300 && str_contains( $content( $typedBase, 0 ), ' 10 Tf ' ) === true && str_contains( $content( $typedBody, 0 ), ' 13 Tf ' ) === true );

// One gender for the PDF in the German texts: das PDF, ein PDF, jedes PDF
$feminine = [];
foreach( [ 'text/de_DE.php', 'feature.php' ] as $file )
	if( preg_match( '/\b(?:jede|jeder|eine|einer|keine|diese|dieser|die|der) PDF(?!-)/iu', (string) preg_replace( '/\'en_US\' => \'[^\n]*\'/u', '', (string) file_get_contents( dirname( __DIR__ ). '/'. $file ) ) ) === 1 )
		$feminine[] = $file;
check( 'the German texts and manual say das PDF throughout - no jede, eine, die or der PDF'. ( $feminine === [] ? '' : ' - '. implode( ', ', $feminine ) ), $feminine === [] );

// The cap of the design: 8000 characters after the trim, characters and not bytes
$capped = static fn( int $characters, string $pad = '' ): string => $pad. '/* '. str_repeat( 'ä', $characters - 6 ). ' */'. $pad;
$capOk = $post( 'pdf/layout/save', [ 'design' => $capped( Pdf::MAX_DESIGN, "  \n" ) ] + $plain );
$capOver = $post( 'pdf/layout/save', [ 'design' => $capped( Pdf::MAX_DESIGN + 1, "\n" ) ] + $plain );
check( 'a design of 8000 characters - multibyte, and spaces round it that the trim takes - is accepted, one of 8001 is refused as too long, and the refused one writes nothing', $capOk[0] === 200 && mb_strlen( Pdf::layout( $appData )['design'] ) === Pdf::MAX_DESIGN && strlen( Pdf::layout( $appData )['design'] ) > Pdf::MAX_DESIGN
	&& $capOver[0] === 400 && $capOver[1]['code'] === 'pdf_design_long' && mb_strlen( Pdf::layout( $appData )['design'] ) === Pdf::MAX_DESIGN );
$post( 'pdf/layout/save', [ 'design' => '' ] + $plain );

// A design that is no text is refused, and the stored one stays
$post( 'pdf/layout/save', [ 'design' => 'h1 { color: #112233 }' ] + $plain );
$notText = [];
foreach( [ 'an array' => [ 'h1' ], 'a number' => 12, 'null' => null, 'true' => true ] as $label => $given ) {
	[ $status, $body ] = $post( 'pdf/layout/save', [ 'design' => $given ] + $plain );
	if( $status !== 400 || $body['code'] !== 'pdf_design_type' || $body['field'] !== 'design' || Pdf::layout( $appData )['design'] !== 'h1 { color: #112233 }' )
		$notText[] = $label;
}
check( 'a design posted as anything but a text is refused with the code and the field of the design, and the stored one stays'. ( $notText === [] ? '' : ' - '. implode( ', ', $notText ) ), $notText === [] );
$post( 'pdf/layout/save', [ 'design' => '' ] + $plain );

// What the panel does with it
[ $status, $body ] = $post( 'pdf/layout/get' );
check( 'layout/get answers the feature\'s rules as text beside the layout, and the longest a design may be', $status === 200 && $body['sheet'] === Pdf::sheet( $appData ) && $body['layout']['design'] === '' && $body['ranges']['design'] === Pdf::MAX_DESIGN );
$post( 'pdf/layout/save', [ 'design' => 'h1 { color: #ff0000 }' ] + $plain );
[ $status, $body ] = $post( 'pdf/layout/preview' );
$designed = base64_decode( (string) ( $body['pdf'] ?? '' ), true );
check( 'layout/preview answers the sample text as the PDF of the layout and the design as they are saved - the colour of the override is in it - and writes nothing', $status === 200 && is_string( $designed ) === true && str_starts_with( $designed, '%PDF' ) === true && str_contains( $content( $designed, 0 ), 'q 1 0 0 rg BT' ) === true
	&& $body['file'] === 'design.pdf' && Pdf::layout( $appData )['design'] === 'h1 { color: #ff0000 }' );
$post( 'pdf/layout/save', [ 'design' => '' ] + $plain );
check( '...and the same preview of a project without a design has no colour in it', preg_match( '/ (rg|RG)\b/', $content( (string) base64_decode( $post( 'pdf/layout/preview' )[1]['pdf'] ), 0 ) ) === 0 );
$post( 'pdf/layout/save', $keep( [] ) );
$post( 'pdf/layout/save', [ 'design' => $kept['design'] ] + $keep( [] ) );
check( 'the layout of the project is as it was before this section', Pdf::layout( $appData ) === $kept );

// Tokens: the caller's slash keys
$site = Tokens::fill( $appData, '[[/project/company/general/name]]', [] );
check( 'a slash key is looked up in the tokens of the caller first, flat, then in the site\'s texts: the caller\'s wins over a text of the site of the same key, the site\'s text answers where the caller has none',
	$site === 'Acme &amp; &lt;Sons&gt;' && Tokens::fill( $appData, '[[/project/company/general/name]]', [ '/project/company/general/name' => 'Mine & co' ] ) === 'Mine &amp; co'
	&& Tokens::fill( $appData, '[[/project/company/general/name]] [[/customer/name]]', [ '/customer/name' => 'Ada' ] ) === 'Acme &amp; &lt;Sons&gt; Ada' );
check( '...the key is the key as a whole, flat: an array under customer is not read for /customer/name - and a value that is a number is the number, an array or nothing is nothing', Tokens::fill( $appData, '[[/customer/name]]', [ 'customer' => [ 'name' => 'Nested' ] ] ) === ''
	&& Tokens::fill( $appData, '[[/n]]|[[/f]]|[[/a]]|[[/z]]', [ '/n' => 12, '/f' => 1.5, '/a' => [ 'x' ], '/z' => null ] ) === '12|1.5||' );
check( '...a value of the caller is escaped as a text is, a bracket in it as its character reference, and not read for tokens again', Tokens::fill( $appData, '[[/a]]', [ '/a' => '<b>[[/b]]</b>', '/b' => 'x' ] ) === '&lt;b&gt;&#91;&#91;/b]]&lt;/b&gt;' );
check( '...the dotted form and the plain name work as they did: the same tokens, nested and flat, in one text with the slash keys', Tokens::fill( $appData, '[[client.city]]|[[name]]|[[/client/city]]', [ 'client' => [ 'city' => 'Köln' ], 'name' => 'Ada', '/client/city' => 'Bonn' ] ) === 'Köln|Ada|Bonn'
	&& Tokens::fill( $appData, '[[a.b]]', [ 'a.b' => 'flat dotted' ] ) === '' );
check( '...a key nobody has is removed, and in a preview written in square brackets like the names of the caller\'s - a key of the caller\'s that has no value, too', Tokens::fill( $appData, '[[/nobody/has]]|[[/project/company/general/none]]', [] ) === '|'
	&& Tokens::fill( $appData, '[[/nobody/has]]', [], true ) === '&#91;/nobody/has]' && Tokens::fill( $appData, '[[/project/company/general/name]]', [], true ) === 'Acme &amp; &lt;Sons&gt;' );
$slashTemplate = [ 'name' => 'S', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>For [[/customer/name]] at [[/project/company/general/name]] [[/customer/none]]</p>' ] ] ];
$slashPdf = Pdf::compose( $appData, $slashTemplate, [ 'tokens' => [ '/customer/name' => 'Ada Lovelace' ] ], [], false );
check( 'in a document: the slash keys of the caller and the site\'s texts fill one paragraph, the key nobody has leaves nothing; in the preview it stands as the preview marks it', str_contains( $content( $slashPdf, 0 ), '(For Ada Lovelace at Acme & <Sons>) Tj' ) === true
	&& str_contains( $content( Pdf::compose( $appData, $slashTemplate, [], [], true ), 0 ), '[/customer/name]' ) === true && str_contains( $content( Pdf::compose( $appData, $slashTemplate, [], [], true ), 0 ), '[/customer/none]' ) === true );

// install()
$project = $bootProject( 'pdf-install' );
$templatesIn = [ 'quote' => [ 'name' => 'Quote', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'block' => 'terms' ], [ 'type' => 'html', 'html' => '<p>[[/customer/name]]</p>' ] ] ], 'bill' => [ 'name' => 'Bill', 'tag' => 'invoice', 'blocks' => [] ] ];
$blocksIn = [ 'terms' => [ 'title' => 'Terms', 'html' => '<p onclick="x()">Terms</p><script>y()</script>' ] ];
$layoutIn = [ 'header' => '<p>Head</p>', 'footer' => '<p>Foot [[page]]</p>', 'design' => 'h1 { color: #112233 }' ];
$first = Pdf::install( $project, $templatesIn, $blocksIn, $layoutIn );
check( 'install() adds the blocks, the layout texts and the templates and answers what it added: a template may name a block that comes with it, the HTML is held to the list of Allow, the design is the layout\'s', $first['added'] === [ 'blocks' => [ 'terms' ], 'templates' => [ 'quote', 'bill' ], 'layout' => [ 'header', 'footer', 'design' ] ]
	&& $first['existing'] === [ 'blocks' => [], 'templates' => [], 'layout' => [] ] && $first['refused'] === [ 'blocks' => [], 'templates' => [], 'layout' => [] ] && array_keys( Pdf::templates( $project ) ) === [ 'bill', 'quote' ]
	&& Pdf::blocks( $project )['terms']['html'] === '<p>Terms</p>' && Pdf::layout( $project )['header'] === '<p>Head</p>' && Pdf::layout( $project )['design'] === 'h1 { color: #112233 }' && count( Pdf::template( $project, 'quote' )['blocks'] ) === 2 );
Pdf\Store::saveBlock( $project, [ 'key' => 'terms', 'title' => 'My terms', 'html' => '<p>Mine</p>' ] );
Pdf\Store::saveTemplate( $project, [ 'key' => 'quote', 'name' => 'My quote', 'tag' => 'offer', 'blocks' => [] ] );
Pdf\Store::saveLayout( $project, [ 'header' => '<p>My head</p>', 'footer' => '', 'format' => 'a4', 'font' => 'helvetica', 'size' => 10, 'margins' => [ 20, 20, 20, 20 ] ] );
$second = Pdf::install( $project, $templatesIn + [ 'extra' => [ 'name' => 'Extra', 'tag' => 'offer', 'blocks' => [] ] ], $blocksIn + [ 'more' => [ 'title' => 'More', 'html' => '<p>More</p>' ] ], $layoutIn );
check( '...it is add-only: a template and a block the project has stay as they are, so does a layout text that is not empty, and what is empty is filled; the answer lists what was added and what was there', $second['added'] === [ 'blocks' => [ 'more' ], 'templates' => [ 'extra' ], 'layout' => [ 'footer' ] ]
	&& $second['existing'] === [ 'blocks' => [ 'terms' ], 'templates' => [ 'quote', 'bill' ], 'layout' => [ 'header', 'design' ] ] && Pdf::blocks( $project )['terms']['title'] === 'My terms' && Pdf::template( $project, 'quote' )['name'] === 'My quote'
	&& Pdf::layout( $project )['header'] === '<p>My head</p>' && Pdf::layout( $project )['footer'] === '<p>Foot [[page]]</p>' && Pdf::layout( $project )['design'] === 'h1 { color: #112233 }' );
$third = Pdf::install( $project, $templatesIn, $blocksIn, $layoutIn );
check( '...and a second installation of the same adds nothing', $third['added'] === [ 'blocks' => [], 'templates' => [], 'layout' => [] ] && $third['existing'] === [ 'blocks' => [ 'terms' ], 'templates' => [ 'quote', 'bill' ], 'layout' => [ 'header', 'footer', 'design' ] ] );
$forced = Pdf::install( $project, $templatesIn, $blocksIn, $layoutIn, true );
check( '...with $overwrite what the project has is replaced', $forced['added'] === [ 'blocks' => [ 'terms' ], 'templates' => [ 'quote', 'bill' ], 'layout' => [ 'header', 'footer', 'design' ] ] && Pdf::blocks( $project )['terms']['title'] === 'Terms' && Pdf::template( $project, 'quote' )['name'] === 'Quote' && Pdf::layout( $project )['header'] === '<p>Head</p>' );
$refused = Pdf::install( $project, [ 'Bad Key' => [ 'name' => 'x', 'tag' => 'offer', 'blocks' => [] ], 'no-tag' => [ 'name' => 'x', 'tag' => 'nothing', 'blocks' => [] ], 'no-block' => [ 'name' => 'x', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'block' => 'absent' ] ] ] ], [ 'x' => [ 'title' => 'X', 'html' => '' ], 'no-title' => [ 'title' => '', 'html' => '' ], 'no-html' => [ 'title' => 'No html' ], 'fine-one' => [ 'title' => 'Fine', 'html' => '<p>ok</p>' ] ],
	[ 'logo' => 'logo.png', 'design' => "h1 { colour: red }", 'header' => str_repeat( 'x', Pdf::MAX_BAND + 1 ) ] );
check( '...a piece that is wrong is left out and named by its code, the others are written, and the pictures of the layout are no part of it', $refused['added'] === [ 'blocks' => [ 'fine-one' ], 'templates' => [], 'layout' => [] ] && $refused['refused']['templates'] === [ 'Bad Key' => 'pdf_key', 'no-tag' => 'pdf_tag', 'no-block' => 'pdf_block_ref' ]
	&& $refused['refused']['blocks'] === [ 'x' => 'pdf_key', 'no-title' => 'pdf_title', 'no-html' => 'pdf_html_empty' ] && $refused['refused']['layout'] === [ 'logo' => 'pdf_layout_field', 'design' => 'pdf_design_property', 'header' => 'pdf_band_long' ] && Pdf::layout( $project )['logo'] === '' );
check( '...every code it answers with has its sentence in both languages', (function() use ( $sentence, $refused ): bool {
	foreach( array_merge( ...array_values( array_map( 'array_values', $refused['refused'] ) ) ) as $code )
		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( $sentence( $code, $locale ) === '' )
				return false;
	return true;
})() );
$rendered = Pdf::render( $project, 'quote', [ 'tokens' => [ '/customer/name' => 'Ada' ] ] );
check( 'an installed template renders with the installed layout: the caller\'s slash key is in it, and the header', is_string( $rendered ) === true && str_contains( $content( $rendered, 0 ), '(Ada) Tj' ) === true && str_contains( $content( $rendered, 0 ), '(Head) Tj' ) === true );

foreach( [ $fixture, $project ] as $gone )
	\Nino\Filesystem::removeDir( ninoSandboxDir( $gone ) );


// --- the blob frame -----------------------------------------------------------------------------------------------

$request = [ '/nino/http/request' => [ 'uri' => '/_admin' ], '/nino/http/response' => [ 'header' => [ 'Content-Security-Policy' => "default-src 'self'; img-src * data:" ] ] ];
\Nino\Modules\Pdf::callbackOutput( $appData, $request );
$site = [ '/nino/http/request' => [ 'uri' => '/legal' ], '/nino/http/response' => [ 'header' => [ 'Content-Security-Policy' => "default-src 'self'; img-src * data:" ] ] ];
\Nino\Modules\Pdf::callbackOutput( $appData, $site );
check( 'the workbench may frame a blob: address, by one source added to its frame-src; a page of the site keeps its policy as it is', $request['/nino/http/response']['header']['Content-Security-Policy'] === "default-src 'self'; img-src * data:; frame-src 'self' blob:"
	&& $site['/nino/http/response']['header']['Content-Security-Policy'] === "default-src 'self'; img-src * data:"
	&& in_array( [ \Nino\Modules\Pdf::class, 'callbackOutput' ], array_merge( ...( $appData['./nino/callbacks']['/nino/http/output'] ?? [ [] ] ) ), true ) === true );

// --- the fonts of the owner --------------------------------------------------------------------------------------

use Nino\Modules\Pdf\Fonts\Metrics;
use Nino\Modules\Pdf\Fonts\TrueType;

$fontDir = \Nino\Filesystem::path( $appData, Metrics::DIR );
$fontCache = \Nino\Filesystem::path( $appData, Metrics::CACHE );
$fontRows = '<p>First line<br>Second line</p><p>Ä ö ü ß € <b>Bold words</b> <i>Slanted words</i></p>';
$fontTemplate = static fn(): string => (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $fontRows ] ], [ 'date' => '2026-01-02' ] );
$layoutOf = static fn( string $font ): array => $post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => $font, 'size' => 10, 'header' => '', 'footer' => '' ] );

$layoutOf( 'helvetica' );
$plainBytes = $fontTemplate();
$plainPanel = $post( 'pdf/layout/get' );
check( 'without a folder of fonts the panel answers the three standard fonts and nothing else of the kind, and the file has no TrueType font', Metrics::families() === [ 'helvetica', 'times', 'courier' ] && Metrics::ignored() === []
	&& array_keys( $plainPanel[1] ) === [ 'layout', 'sheet', 'refusal', 'images', 'papers', 'fonts', 'ranges' ] && $plainPanel[1]['fonts'] === [ 'helvetica', 'times', 'courier' ] && str_contains( $plainBytes, '/TrueType' ) === false && str_contains( $plainBytes, '/FontFile2' ) === false );

// The parser: the numbers of a font written by hand, with the measures of Satoshi Regular (A 645, ascender 1010, descender -240)
$regular = pdfFontFixture();
$fontRead = TrueType::read( $regular );
check( 'the reader takes 224 widths in 1/1000 em from the cmap and the hmtx: A is 645, the euro sign, the umlaut and a space the ones written, and a character the font has no glyph for has the width of glyph 0', is_array( $fontRead ) === true && count( $fontRead['widths'] ) === 224
	&& $fontRead['widths'][65 - 32] === 645 && $fontRead['widths'][0x80 - 32] === pdfFontWidth( 0x20AC ) && $fontRead['widths'][0xE4 - 32] === pdfFontWidth( 0xE4 ) && $fontRead['widths'][32 - 32] === pdfFontWidth( 32 ) && $fontRead['widths'][0xFF - 32] === 500
	&& $fontRead['widths'][0x81 - 32] === pdfFontWidth( 0x2022 ) );
check( '...the ascender and descender from the hhea, the cap height and x-height from the OS/2, the box from the head, the italic angle from the post, and the flags and a stem weight from the weight and the class',
	$fontRead['ascent'] === 1010 && $fontRead['descent'] === -240 && $fontRead['cap'] === 716 && $fontRead['xheight'] === 484 && $fontRead['bbox'] === [ -203, -224, 1619, 1026 ] && $fontRead['angle'] === 0 && $fontRead['flags'] === 32 && $fontRead['stemv'] === 96
	&& TrueType::read( pdfFontFixture( [ 'weight' => 700, 'angle' => -12, 'class' => 0x0100 ] ) )['flags'] === ( 32 | 2 | 64 | 262144 ) && TrueType::read( pdfFontFixture( [ 'angle' => -12 ] ) )['angle'] === -12
	&& TrueType::read( pdfFontFixture( [ 'em' => 2048, 'asc' => 2048, 'desc' => -512 ] ) )['ascent'] === 1000 && TrueType::read( pdfFontFixture( [ 'em' => 2048, 'asc' => 2048, 'desc' => -512 ] ) )['descent'] === -250 );

// A real font, where the owner of the checkout has one: PDF_TEST_FONTS names a folder with Satoshi-Regular.ttf (the licence of a font is not the repository's to give)
$real = (string) getenv( 'PDF_TEST_FONTS' ). '/Satoshi-Regular.ttf';

if( is_file( $real ) === true ) {
	$satoshi = TrueType::read( (string) file_get_contents( $real ) );
	check( 'Satoshi Regular reads as the converter of the font tables did: A 645, ascender 1010, descender -240', is_array( $satoshi ) === true && $satoshi['widths'][65 - 32] === 645 && $satoshi['ascent'] === 1010 && $satoshi['descent'] === -240 && $satoshi['widths'][0xE4 - 32] === 522 );
}
else
	echo "note - PDF_TEST_FONTS names no Satoshi-Regular.ttf: the numbers of the real font were NOT checked\n";

$baits = [
	'a wrong magic'								=> [ 'magic', pdfFontFixture( [ 'magic' => 'JUNK' ] ) ],
	'an OpenType with CFF outlines'	=> [ 'cff', pdfFontFixture( [ 'magic' => 'OTTO' ] ) ],
	'a webfont'										=> [ 'woff', pdfFontFixture( [ 'magic' => 'wOFF' ] ) ],
	'a table past the end of the file'	=> [ 'truncated', substr( $regular, 0, -300 ) ],
	'a file of eleven bytes'			=> [ 'magic', str_repeat( "\0", 11 ) ],
	'no glyf table'								=> [ 'tables', pdfFontFixture( [ 'drop' => 'glyf' ] ) ],
	'no OS/2 table'								=> [ 'tables', pdfFontFixture( [ 'drop' => 'OS/2' ] ) ],
	'no cmap table'								=> [ 'tables', pdfFontFixture( [ 'drop' => 'cmap' ] ) ],
	'a wrong head magic'					=> [ 'tables', pdfFontFixture( [ 'headmagic' => 1 ] ) ],
	'a number of tables in the thousands'	=> [ 'tables', substr_replace( $regular, "\x0F\xFF", 4, 2 ) ],
	'a cmap without the table (3, 1)'	=> [ 'cmap', pdfFontFixture( [ 'cmapplatform' => 1 ] ) ],
	'a cmap cut off'							=> [ 'cmap', pdfFontFixture( [ 'cmapcut' => true ] ) ],
	'a licence that forbids embedding'	=> [ 'license', pdfFontFixture( [ 'fstype' => 2 ] ) ],
];
$unread = [];
foreach( $baits as $label => [ $code, $bytes ] )
	if( TrueType::read( $bytes ) !== $code )
		$unread[] = $label. ' -> '. json_encode( TrueType::read( $bytes ) );
check( 'the reader refuses what is no TrueType font with outlines by a code, and reads no byte past the end of the file'. ( $unread === [] ? '' : ' - '. implode( '; ', $unread ) ), $unread === [] && count( $baits ) === 13 );

// The folder: a good family and the files that are left out
mkdir( $fontDir, 0775, true );
file_put_contents( $fontDir. '/Fixture-Regular.ttf', $regular );
file_put_contents( $fontDir. '/Fixture-Bold.ttf', pdfFontFixture( [ 'weight' => 700 ] ) );
file_put_contents( $fontDir. '/Torn-Regular.ttf', substr( $regular, 0, -300 ) );
file_put_contents( $fontDir. '/Junk-Regular.ttf', 'not a font at all' );
file_put_contents( $fontDir. '/Open-Regular.ttf', pdfFontFixture( [ 'magic' => 'OTTO' ] ) );
file_put_contents( $fontDir. '/Web-Regular.woff', 'wOFF....' );
file_put_contents( $fontDir. '/Big-Regular.ttf', $regular. str_repeat( "\0", 3 * 1048576 ) );
file_put_contents( $fontDir. '/Closed-Regular.ttf', pdfFontFixture( [ 'fstype' => 2 ] ) );
file_put_contents( $fontDir. '/Heavy-Bold.ttf', $regular );
file_put_contents( $fontDir. '/Helvetica-Regular.ttf', $regular );
file_put_contents( $fontDir. '/a..b-Regular.ttf', $regular );
file_put_contents( $fontDir. '/Odd Name-Regular.ttf', $regular );
file_put_contents( $fontDir. '/Fixture-Regular.TTF', $regular );
file_put_contents( $fontDir. '/'. str_repeat( 'N', 41 ). '-Regular.ttf', $regular );
file_put_contents( $fontDir. '/notes.txt', 'a text file is no font and no complaint' );
mkdir( $fontDir. '/Dir-Regular.ttf' );
symlink( $fontDir. '/Fixture-Regular.ttf', $fontDir. '/Linked-Regular.ttf' );
$post( 'pdf/layout/get' );
$left = Metrics::ignored();
check( 'a family is every file <Family>-<Style>.ttf that reads, and the folder is read for the fonts of the layout: only Fixture, behind the three standard ones', Metrics::families() === [ 'helvetica', 'times', 'courier', 'fixture' ] && Metrics::owned() === [ 'fixture' => 'Fixture' ] && ninoWarnings() === [] );
check( '...the others are named with the reason and not read: a file that is torn, junk, CFF, too big, not to be embedded, a family without Regular or with the name of a standard font, a name with dots, a space, a wrong case or 41 letters, a link and a folder - and a webfont, but not a text file',
	$left === [ 'Big-Regular.ttf' => 'size', 'Closed-Regular.ttf' => 'license', 'Dir-Regular.ttf' => 'name', 'Fixture-Regular.TTF' => 'name', 'Heavy-Bold.ttf' => 'regular', 'Helvetica-Regular.ttf' => 'reserved', 'Junk-Regular.ttf' => 'magic', 'Linked-Regular.ttf' => 'name',
		str_repeat( 'N', 41 ). '-Regular.ttf' => 'name', 'Odd Name-Regular.ttf' => 'name', 'Open-Regular.ttf' => 'cff', 'Torn-Regular.ttf' => 'truncated', 'Web-Regular.woff' => 'name', 'a..b-Regular.ttf' => 'name' ] );
check( '...the panel lists the family after the three, and the files it left out, at most twenty', $post( 'pdf/layout/get' )[1]['fonts'] === [ 'helvetica', 'times', 'courier' ] && $post( 'pdf/layout/get' )[1]['owned'] === [ 'fixture' => 'Fixture' ]
	&& $post( 'pdf/layout/get' )[1]['ignored'] === array_keys( $left ) && count( glob( $fontCache. '/*.php' ) ) === 8 );

// The family is a font of the layout and of the design
[ $status ] = $layoutOf( 'fixture' );
[ $missing ] = $layoutOf( 'comic' );
check( 'the layout takes the family by its word and refuses a font that is none, and the word is the font of the layout', $status === 200 && $missing === 400 && Pdf::layout( $appData )['font'] === 'fixture'
	&& Metrics::font( 'fixture', true, true ) === 'Fixture-Regular' && Metrics::font( 'fixture', true, false ) === 'Fixture-Bold' && Metrics::font( 'fixture', false, false ) === 'Fixture-Regular' && Metrics::font( 'helvetica', true, false ) === 'Helvetica-Bold' && Metrics::family( 'Fixture-Bold' ) === 'fixture' );
$sheet = static fn( string $family ): array|string => Sheet::parse( 'p { font-family: '. $family. ' }' );
check( 'a design names the family in any case, with quotes or without, and the stylesheet of the feature writes it as the file spells it; a name that is no family of the folder is refused', $sheet( 'fixture' ) === [ 'p' => [ 'font-family' => 'fixture' ] ] && $sheet( 'FIXTURE' ) === $sheet( 'fixture' ) && $sheet( '"Fixture"' ) === $sheet( 'fixture' )
	&& $sheet( "'fixture'" ) === $sheet( 'fixture' ) && is_string( $sheet( 'Fixtures' ) ) === true && is_string( $sheet( 'Heavy' ) ) === true && is_string( $sheet( 'Comic Sans' ) ) === true && str_contains( Pdf::sheet( $appData ), 'font-family: Fixture' ) === true
	&& str_contains( Sheet::text( [ 'p' => [ 'font-family' => 'fixture' ] ] ), '{ font-family: Fixture }' ) === true );

// The file
$fixtureBytes = $fontTemplate();
preg_match_all( '#/Subtype /TrueType /BaseFont /([A-Za-z-]+) /FirstChar 32 /LastChar 255 /Widths \[ ([\d ]+) \] /Encoding /WinAnsiEncoding /FontDescriptor (\d+) 0 R#', $fixtureBytes, $fonts, PREG_SET_ORDER );
$embedded = static function( string $pdf, array $font ) use ( $object ): array {
	$descriptor = $object( $pdf, (int) $font[3] );
	preg_match( '#/FontFile2 (\d+) 0 R#', $descriptor, $file );
	$stream = $object( $pdf, (int) ( $file[1] ?? 0 ) );
	$start = strpos( $stream, "stream\n" );
	return [ $descriptor, $stream, $start === false ? '' : (string) gzuncompress( substr( $stream, $start + 7, (int) strrpos( $stream, "\nendstream" ) - $start - 7 ) ) ];
};
[ $descriptor, $stream, $inflated ] = $embedded( $fixtureBytes, $fonts[0] ?? [ 3 => 0 ] );
check( 'the file carries each style it uses as a TrueType font: 224 widths, WinAnsi, a descriptor with the measures and the whole file as the FontFile2, whose Length1 is the size of the file; a style without a file is the Regular', count( $fonts ) === 2
	&& array_column( $fonts, 1 ) === [ 'Fixture-Regular', 'Fixture-Bold' ] && count( explode( ' ', $fonts[0][2] ) ) === 224 && explode( ' ', $fonts[0][2] )[65 - 32] === '645'
	&& str_contains( $descriptor, '/Flags 32 ' ) === true && str_contains( $descriptor, '/Ascent 1010 /Descent -240 /CapHeight 716 ' ) === true && str_contains( $descriptor, '/FontBBox [ -203 -224 1619 1026 ]' ) === true && str_contains( $descriptor, '/FontName /Fixture-Regular' ) === true
	&& str_contains( $stream, '/Filter /FlateDecode /Length1 '. strlen( $regular ). ' ' ) === true && $inflated === $regular && str_contains( $fixtureBytes, '/Helvetica' ) === false );
check( '...the text is the code page of the standard fonts: the umlauts, the sharp s and the euro sign are single bytes', str_contains( $content( $fixtureBytes, 0 ), '(\\304 \\366 \\374 \\337 \\200 ) Tj' ) === true );
if( trim( (string) @shell_exec( 'command -v pdffonts 2>/dev/null' ) ) !== '' ) {
	file_put_contents( $dir. '/fonts.pdf', $fixtureBytes );
	$listed = (string) shell_exec( 'pdffonts '. escapeshellarg( $dir. '/fonts.pdf' ). ' 2>/dev/null' );
	check( '...and pdffonts lists both as embedded TrueType fonts in WinAnsi', preg_match( '/^Fixture-Regular\s+TrueType\s+WinAnsi\s+yes\s/m', $listed ) === 1 && preg_match( '/^Fixture-Bold\s+TrueType\s+WinAnsi\s+yes\s/m', $listed ) === 1 );
}
if( $pdftotext === true )
	check( '...and pdftotext reads the umlauts and the euro sign back', str_contains( $words( $fixtureBytes ), 'Ä ö ü ß €' ) === true && str_contains( $words( $fixtureBytes ), 'Bold words' ) === true );

// The line: the type reaches as far above and below the baseline as the font says
$layoutOf( 'helvetica' );
$helvetica = $fontTemplate();
$yOf = static fn( string $pdf, string $text ): float => $shown( $pdf, 0 )[$text][3];
$lead = $yOf( $fixtureBytes, 'First line' ) - $yOf( $fixtureBytes, 'Second line' );
$rise = static fn( int $up, int $down ): float => ( 13.5 - ( $up - $down ) * 10 / 1000 ) / 2 + $up * 10 / 1000;
check( 'a line in the font sits where its ascender and descender put it: the baseline of the first line differs from the Helvetica one by the difference of the rises, and the lines stay 1.35 times the size apart',
	abs( ( $yOf( $helvetica, 'First line' ) - $yOf( $fixtureBytes, 'First line' ) ) - ( $rise( 1010, -240 ) - $rise( 718, -207 ) ) ) < 0.01 && abs( $lead - 13.5 ) < 0.001 && abs( $yOf( $helvetica, 'First line' ) - $yOf( $helvetica, 'Second line' ) - 13.5 ) < 0.001
	&& abs( $shown( $fixtureBytes, 0 )['Second line'][0] - $shown( $helvetica, 0 )['Second line'][0] ) < 0.001 );
check( '...and a text is as wide as the widths of the file say: Metrics::width() of the font is the sum of the 224 numbers', Metrics::width( 'Fixture-Regular', 'AA' ) === 1290.0 && Metrics::width( 'Fixture-Regular', "A\xFF" ) === 1145.0 && Metrics::width( 'Fixture-Bold', 'A' ) === 645.0 && Metrics::vertical( 'Fixture-Bold' ) === [ 1010, -240 ] );

// A design in the family, over the body in Helvetica
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '', 'design' => "h1 { font-family: \"Fixture\" }\n.signature { font-family: FIXTURE }" ] );
$designed = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<h1>Title</h1><p>Body</p>' ] ], [ 'date' => '2026-01-02' ] );
check( 'a design rule in the family sets that selector in it alone; the Bold of the heading is the Bold file and the body stays in Helvetica', $shown( $designed, 0 )['Title'][1] === 'Fixture-Bold' && $shown( $designed, 0 )['Body'][1] === 'Helvetica' );
$post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '', 'design' => '' ] );

// The numbers are kept until the file changes
$firstWidth = Metrics::width( 'Fixture-Regular', 'A' );
$stamp = (int) filemtime( $fontDir. '/Fixture-Regular.ttf' );
file_put_contents( $fontDir. '/Fixture-Regular.ttf', pdfFontFixture( [ 'wide' => 700 ] ) );
touch( $fontDir. '/Fixture-Regular.ttf', $stamp );
clearstatcache();
Metrics::load( $appData );
$kept = Metrics::width( 'Fixture-Regular', 'A' );
touch( $fontDir. '/Fixture-Regular.ttf', $stamp + 30 );
clearstatcache();
Metrics::load( $appData );
check( 'the numbers of a file are read once and kept in the data folder: a file with its time and size is not read again, one whose time moved is', $firstWidth === 645.0 && $kept === 645.0 && Metrics::width( 'Fixture-Regular', 'A' ) === 700.0 && count( glob( $fontCache. '/*.php' ) ) === 8 );
file_put_contents( $fontDir. '/Fixture-Regular.ttf', $regular. 'x' );
Metrics::load( $appData );
check( '...and one whose size moved is read again too - here as a file that is no longer what the table of the font says it is, and left out', Metrics::width( 'Fixture-Regular', 'A' ) === 600.0 || isset( Metrics::ignored()['Fixture-Regular.ttf'] ) === false );
file_put_contents( $fontDir. '/Fixture-Regular.ttf', $regular );
Metrics::load( $appData );

// Away with the folder: a layout that names the family is the default again, and the file is the one it was
$layoutOf( 'fixture' );
unlink( $fontDir. '/Linked-Regular.ttf' );
\Nino\Filesystem::removeDir( $fontDir );
$gone = $post( 'pdf/layout/get' );
$layoutOf( 'helvetica' );
check( 'without the folder the family is none: the stored font of the layout is the default, the panel is the one of before the folder, and the file is byte for byte the first', Pdf::layout( $appData )['font'] === 'helvetica' && $gone[1]['layout']['font'] === 'helvetica' && $gone[1]['fonts'] === [ 'helvetica', 'times', 'courier' ]
	&& array_keys( $gone[1] ) === array_keys( $plainPanel[1] ) && $layoutOf( 'fixture' )[0] === 400 && $sheet( 'fixture' ) !== [ 'p' => [ 'font-family' => 'fixture' ] ] && $fontTemplate() === $plainBytes );
\Nino\Filesystem::removeDir( $fontCache );

// --- what a request can do with a key, a file and a right it has not --------------------------------------------------------------

$tree = static function( string $base ): array {

	$files = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		$files[ substr( (string) $file, strlen( $base ) ) ] = hash( 'sha256', (string) file_get_contents( (string) $file ) );

	ksort( $files );

	return $files;
};
$dataBefore = $tree( \Nino\Filesystem::path( $appData, '/data' ) );
$outside = dirname( \Nino\Filesystem::path( $appData, Pdf::DIR ) );
$outsideBefore = array_map( 'basename', glob( $outside. '/*' ) ?: [] );

// A tag that is no text - a list, an object, true, null - is refused at its field with the code of the tag, for a new template and for one that has a tag, and the file stays as it is
$tagFile = \Nino\Filesystem::path( $appData, Pdf::TEMPLATES. '/offer-one.php' );
$tagHash = hash_file( 'sha256', $tagFile );
$tagBad = [];
foreach( [ [ 'offer' ], [ 'a' => 'offer' ], true, false, null, 5, [] ] as $kind => $tag )
	foreach( [ [ 'key' => 'offer-one', 'isNew' => false ], [ 'key' => 'tag-new', 'isNew' => true ] ] as $which ) {

		$answer = $post( 'pdf/templates/save', $which + [ 'name' => 'Tag', 'tag' => $tag, 'blocks' => [] ] );

		if( $answer[0] !== 400 || $answer[1]['code'] !== 'pdf_tag' || $answer[1]['field'] !== 'tag' )
			$tagBad[] = $kind. ' '. json_encode( $which );
	}
check( 'a tag that is a list, an object, true, false, null, a number or an empty list is 400 with the code of the tag and its field, for a template that is new and for one that has a tag; the file of the template is byte for byte what it was and nothing new is written'. ( $tagBad === [] ? '' : ' - '. implode( ', ', $tagBad ) ),
	$tagBad === [] && hash_file( 'sha256', $tagFile ) === $tagHash && Pdf::template( $appData, 'tag-new' ) === null );

// The stylesheet of the panel is for the places the script draws into: every id it names is a mount of the panel or of a tab, and every class it names is one the script makes
$css = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( dirname( __DIR__ ). '/assets/admin.css' ) );
$script = (string) file_get_contents( dirname( __DIR__ ). '/assets/admin.js' );
$mounts = array_merge( \Nino\Modules\Pdf\Admin::panes(), ...array_map( static fn( string $tab ): array => $tab::panes(), \Nino\Modules\Pdf\Admin::tabs() ) );
preg_match_all( '/#([a-z][a-z0-9-]*)/', $css, $ids );
preg_match_all( '/\.(pdf-[a-z-]+)/', $css, $classes );
$strays = array_merge( array_diff( array_unique( $ids[1] ), $mounts ), array_filter( array_unique( $classes[1] ), static fn( string $class ): bool => str_contains( $script, $class ) === false ) );
check( 'the stylesheet of the panel names only mounts the panel draws into and classes the script makes, and the picture of the Pictures tab and the name of a row of the two directories are held by rules of their own'. ( $strays === [] ? '' : ' - strays: '. implode( ', ', $strays ) ),
	$strays === [] && in_array( 'pdf-pictures', $ids[1], true ) === true && preg_match( '/#pdf-pictures \.pdf-picture-image\s*\{[^}]*max-width:\s*100%[^}]*max-height:/s', $css ) === 1
	&& preg_match( '/@media \(min-width: 40rem\)\s*\{\s*#pdf-blocks-list td\[data-card="primary"\] \.nino-admin-table-clamp,\s*#pdf-templates-list td\[data-card="primary"\] \.nino-admin-table-clamp\s*\{[^}]*display:\s*inline-block/s', $css ) === 1 );

// A key is a slug of the feature's own and nothing else: a path, a null byte or a name of another file goes nowhere, whichever action carries it
$keys = [ '../layout', '../../config', 'a/b', "good\0evil", "good\0", '..', '.', '', 'UPPER', "offer-one\n", 'offer-one ', '%2e%2e%2flayout', '..\\layout', str_repeat( 'a', 41 ) ];
$wrong = [];
foreach( $keys as $key ) {

	foreach( [ 'pdf/templates/get', 'pdf/templates/delete', 'pdf/templates/duplicate' ] as $action )
		if( in_array( $post( $action, [ 'key' => $key, 'name' => 'Copy' ] )[0], [ 404, 400 ], true ) === false )
			$wrong[] = $action. ' '. json_encode( $key );

	foreach( [ 'pdf/templates/save', 'pdf/blocks/save' ] as $action )
		if( $post( $action, [ 'key' => $key, 'isNew' => true, 'name' => 'x', 'title' => 'x', 'tag' => 'offer', 'html' => '', 'blocks' => [] ] )[0] !== 400 )
			$wrong[] = $action. ' '. json_encode( $key );

	if( $post( 'pdf/blocks/delete', [ 'key' => $key ] )[0] !== 404 )
		$wrong[] = 'pdf/blocks/delete '. json_encode( $key );

	if( Pdf::template( $appData, $key ) !== null || Pdf::fields( $appData, $key ) !== [] || Pdf::inputs( $appData, $key ) !== [] )
		$wrong[] = 'template() '. json_encode( $key );

	ninoWarnings();

	if( Pdf::render( $appData, $key ) !== null )
		$wrong[] = 'render() '. json_encode( $key );

	ninoWarnings();
}
check( 'a key with a path jump, a slash, a null byte, a new line, upper case, a dot or 41 characters is refused by every action that takes one - 400 or 404 - and read as no template by template(), fields(), inputs() and render() (null, with a warning)'. ( $wrong === [] ? '' : ' - '. implode( '; ', array_slice( $wrong, 0, 6 ) ) ), $wrong === [] && count( $keys ) === 14 );
check( '...and nothing was written or taken away by any of them: the files below data/ are the files that were there, the layout among them, and nothing lies beside data/pdf', $tree( \Nino\Filesystem::path( $appData, '/data' ) ) === $dataBefore && is_file( \Nino\Filesystem::path( $appData, Pdf::LAYOUT ) ) === true
	&& array_map( 'basename', glob( $outside. '/*' ) ?: [] ) === $outsideBefore );

// A template file that is a link, or holds what is no template, is none: reading a .php file through the filesystem is an include, which is why the file is looked at first
$link = \Nino\Filesystem::path( $appData, Pdf::TEMPLATES. '/linked.php' );
file_put_contents( $dir. '/elsewhere.php', "<?php return [ 'name' => 'Elsewhere', 'tag' => 'offer', 'blocks' => [] ];" );
symlink( $dir. '/elsewhere.php', $link );
file_put_contents( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES. '/broken.php' ), "<?php return 'not a template';" );
check( 'a template that is a link to a file, and a file that holds no template, are no templates: not listed, not read, not rendered', Pdf::template( $appData, 'linked' ) === null && Pdf::template( $appData, 'broken' ) === null
	&& isset( Pdf::templates( $appData )['linked'] ) === false && isset( Pdf::templates( $appData )['broken'] ) === false && in_array( 'linked', Pdf::keys( $appData ), true ) === false );
unlink( $link );
unlink( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES. '/broken.php' ) );

// A picture: the file name the browser sends is never the name it is stored under, and what is no picture or too big is refused before anything is written
$imagesBefore = $tree( \Nino\Filesystem::path( $appData, Pdf::IMAGES ) );
$named = static function( string $name, string $bytes, string $kind = 'logo' ) use ( $dir, $post ): array {

	$file = $dir. '/upload.bin';
	file_put_contents( $file, $bytes );
	$_FILES['file'] = [ 'name' => $name, 'type' => 'image/png', 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => strlen( $bytes ) ];
	$answer = $post( 'pdf/layout/image', [ 'kind' => $kind ] );
	unset( $_FILES['file'] );

	return $answer;
};
$pathName = $named( '../../../../etc/cron.d/x.png', $picture( 'png', 30, 10 ) );
check( 'a file named like a path - parent folders, a drive, a null byte - is stored as logo.png or logo.jpg and nowhere else: the name the browser sends is read by nothing', $pathName[0] === 200 && $pathName[1]['layout']['logo'] === 'logo.png'
	&& $named( "..\\..\\x.png\0.php", $picture( 'jpeg', 30, 10 ) )[1]['layout']['logo'] === 'logo.jpg' && array_keys( $tree( \Nino\Filesystem::path( $appData, Pdf::IMAGES ) ) ) === [ '/logo.jpg', '/signature.png' ] && is_file( $dir. '/etc/cron.d/x.png' ) === false );
foreach( [ '../x', "logo\0", 'LOGO', 'logo.png', ' logo', '', 'signature/../logo' ] as $kind )
	if( $named( 'x.png', $picture( 'png' ), $kind )[1]['code'] !== 'pdf_image_kind' )
		$wrong[] = 'kind '. json_encode( $kind );
check( '...a kind that is not exactly logo or signature is refused with its code, whatever it spells', $wrong === [] || count( array_filter( $wrong, static fn( string $line ): bool => str_starts_with( $line, 'kind ' ) ) ) === 0 );

$tooBig = $named( 'big.png', "\x89PNG\r\n\x1a\n". str_repeat( "\0", \Nino\Images::MAX_UPLOAD_BYTES ) );
check( '...a file over the kernel\'s limit of bytes is 400 with the kernel\'s code and its field, and a file that is no picture - a script, an SVG, a GIF with a PNG name, an empty file - is 400 with the code of its kind', $tooBig[0] === 400 && $tooBig[1]['code'] === 'image_too_large' && $tooBig[1]['field'] === 'file'
	&& $named( 'x.png', '<?php system( $_GET["c"] );' )[1]['code'] === 'image_type' && $named( 'x.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>' )[1]['code'] === 'image_type' && $named( 'x.png', '' )[1]['code'] === 'image_type' );
$_FILES['file'] = [ 'name' => 'x.png', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0 ];
$iniSize = $post( 'pdf/layout/image', [ 'kind' => 'logo' ] );
unset( $_FILES['file'] );
check( '...a file the server cut off by its own limit is 413 or 400 with a code of the kernel, and after all of it the pictures are the two files that were there, unchanged but for the logo that was replaced', in_array( $iniSize[0], [ 400, 413 ], true ) === true && is_string( $iniSize[1]['code'] ?? null ) === true
	&& array_keys( $tree( \Nino\Filesystem::path( $appData, Pdf::IMAGES ) ) ) === [ '/logo.jpg', '/signature.png' ] && $tree( \Nino\Filesystem::path( $appData, Pdf::IMAGES ) )['/signature.png'] === $imagesBefore['/signature.png'] );
$upload( 'logo', $logo );

// A text that is HTML is held to the list, a name that is markup is text: what a person writes into a title, a name or a label never becomes an element of the answer's reading
$evil = '<img src=x onerror=alert(1)>';
$post( 'pdf/blocks/save', [ 'key' => 'evil-one', 'isNew' => true, 'title' => $evil, 'html' => '<p onclick="x()">a</p><iframe src="javascript:alert(1)"></iframe><img src="pdf:logo" onerror="y()" width="9999999999999999999999">' ] );
$post( 'pdf/templates/save', [ 'key' => 'evil-one', 'isNew' => true, 'name' => $evil, 'tag' => 'offer', 'blocks' => [ [ 'type' => 'input', 'name' => 'note', 'label' => $evil, 'multiline' => false, 'required' => false ], [ 'type' => 'html', 'html' => '<a href="javascript:alert(1)">x</a><p style="background:url(javascript:alert(1))">y</p>' ] ] ] );
$kept = Pdf::template( $appData, 'evil-one' );
check( 'a title, a name and a label that spell an element are stored as the characters they are, and the HTML of a block and of a template is the list\'s rewrite: no iframe, no script address, no event, no url() - and an absurd width of the logo is dropped', Pdf::blocks( $appData )['evil-one']['title'] === $evil && $kept['name'] === $evil && $kept['blocks'][0]['label'] === $evil
	&& preg_match( '/iframe|javascript|onclick|onerror|url\(/i', Pdf::blocks( $appData )['evil-one']['html']. $kept['blocks'][1]['html'] ) === 0 );
$post( 'pdf/templates/delete', [ 'key' => 'evil-one' ] );
$post( 'pdf/blocks/delete', [ 'key' => 'evil-one' ] );

// A right and a token: every action asks for the right first, and a request the token guard refused (a status of 403 that is there before the action runs) is not carried out
$names = [ 'pdf/layout/get' => [], 'pdf/layout/save' => [ 'format' => 'letter', 'margins' => [ 10, 10, 10, 10 ], 'font' => 'courier', 'size' => 9, 'header' => '<p>x</p>', 'footer' => '' ], 'pdf/layout/preview' => [], 'pdf/layout/image/delete' => [ 'kind' => 'signature' ],
	'pdf/blocks/list' => [], 'pdf/blocks/save' => [ 'key' => 'late', 'isNew' => true, 'title' => 'Late', 'html' => '' ], 'pdf/blocks/delete' => [ 'key' => 'long' ], 'pdf/templates/list' => [], 'pdf/templates/get' => [ 'key' => 'long' ],
	'pdf/templates/save' => [ 'key' => 'late', 'isNew' => true, 'name' => 'Late', 'tag' => 'offer', 'blocks' => [] ], 'pdf/templates/delete' => [ 'key' => 'long' ], 'pdf/templates/duplicate' => [ 'key' => 'long', 'name' => 'Late' ],
	'pdf/preview' => [ 'name' => 'x', 'tag' => 'offer', 'blocks' => [] ] ];
$beforeAll = $tree( \Nino\Filesystem::path( $appData, '/data' ) );
$untouched = [];
foreach( $names as $action => $data ) {

	$callable = \Nino\Modules\Pdf\Admin::actions()[$action];

	if( ninoCall( $appData, $callable, $data, null, 403 )[0] !== 403 )
		$untouched[] = $action;
}
$upload = static function() use ( $dir, $picture ): array {

	$file = $dir. '/upload.bin';
	file_put_contents( $file, $picture( 'png' ) );
	$_FILES['file'] = [ 'name' => 'x', 'type' => '', 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => filesize( $file ) ];
	$answer = ninoCall( $GLOBALS['appData'], \Nino\Modules\Pdf\Admin::actions()['pdf/layout/image'], [ 'kind' => 'logo' ], null, 403 );
	unset( $_FILES['file'] );

	return $answer;
};
check( 'a request the token guard refused - the status is 403 before the action runs - is answered 403 by every one of the '. count( $names ). ' actions but the upload, which is tried below, and nothing is written'. ( $untouched === [] ? '' : ' - carried out: '. implode( ', ', $untouched ) ),
	$untouched === [] && count( $names ) === 13 && $tree( \Nino\Filesystem::path( $appData, '/data' ) ) === $beforeAll && $upload()[0] === 403 && $tree( \Nino\Filesystem::path( $appData, '/data' ) ) === $beforeAll );
check( '...and the actions the panel answers are the fourteen of its contract, each under the slug of the panel: pdf/ and the name of the thing', count( \Nino\Modules\Pdf\Admin::actions() ) === 14 && count( array_filter( array_keys( \Nino\Modules\Pdf\Admin::actions() ), static fn( string $action ): bool => preg_match( '#^pdf(/[a-z]+){1,3}$#D', $action ) === 1 ) ) === 14 );

// A fresh installation has no tags and needs none: a template is saved and previewed with no tag, a tag chosen anew has to be one of the project's
$bare = ninoSandbox( 'pdf-bare' );
\Nino\Features::activate( $bare, 'pdf' );
ninoUser( $bare, 'dev@example.com' );
$barePost = static fn( string $method, array $data ): array => ninoCall( $bare, [ \Nino\Modules\Pdf\Admin::class, $method ], $data );
$bareSaved = $barePost( 'apiTemplatesSave', [ 'key' => 'first', 'isNew' => true, 'name' => 'First', 'tag' => '', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>Hello</p>' ] ] ] );
$barePreview = $barePost( 'apiPreview', [ 'name' => 'Draft', 'tag' => 'whatever', 'blocks' => [ [ 'type' => 'html', 'html' => '<p>Hello</p>' ] ] ] );
$bareRefused = $barePost( 'apiTemplatesSave', [ 'key' => 'second', 'isNew' => true, 'name' => 'Second', 'tag' => 'offer', 'blocks' => [] ] );
$bareInstalled = Pdf::install( $bare, [ 'first' => [ 'name' => 'First', 'tag' => '', 'blocks' => [] ], 'later' => [ 'name' => 'Later', 'tag' => 'offer', 'blocks' => [] ] ], [], [] );
check( 'a fresh installation with no tags: tags() is empty, a template with no tag is saved and listed, the preview asks no tag, a tag chosen anew is refused with the code of the tag and its field, install() says the same',
	Pdf::tags( $bare ) === [] && $bareSaved[0] === 200 && Pdf::template( $bare, 'first' )['tag'] === '' && $barePreview[0] === 200 && str_starts_with( (string) base64_decode( $barePreview[1]['pdf'] ), '%PDF' )
	&& $bareRefused[0] === 400 && $bareRefused[1]['code'] === 'pdf_tag' && $bareRefused[1]['field'] === 'tag' && $bareInstalled['added']['templates'] === [] && $bareInstalled['existing']['templates'] === [ 'first' ] && $bareInstalled['refused']['templates'] === [ 'later' => 'pdf_tag' ]
	&& $barePost( 'apiTemplatesList', [] )[1]['tags'] === [] && array_column( $barePost( 'apiTemplatesList', [] )[1]['templates'], 'known', 'key' ) === [ 'first' => true ]
	&& array_keys( Pdf::templates( $bare, '' ) ) === [ 'first' ] && Pdf::render( $bare, 'first' ) !== null );
\Nino\Filesystem::removeDir( ninoSandboxDir( $bare ) );
$appData = $GLOBALS['appData'];

// A copy has the name it is given or none: a name over the limit is refused at its field, and nothing is cut or written
$countBefore = count( glob( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES ). '/*.php' ) );
$longName = $post( 'pdf/templates/duplicate', [ 'key' => 'offer-one', 'name' => str_repeat( 'x', 121 ) ] );
$sameCount = count( glob( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES ). '/*.php' ) );
$whole = $post( 'pdf/templates/duplicate', [ 'key' => 'offer-one', 'name' => str_repeat( 'y', 120 ) ] );
check( 'a copy named with 121 characters is refused with the code of a name, its limit and its field, and is not cut to 120: nothing is written; 120 characters are kept whole', $longName[0] === 400 && $longName[1]['code'] === 'pdf_title' && $longName[1]['field'] === 'name' && $longName[1]['params'] === [ 120 ]
	&& $sameCount === $countBefore && $whole[0] === 200 && Pdf::templates( $appData )[ $whole[1]['key'] ]['name'] === str_repeat( 'y', 120 ) );
$post( 'pdf/templates/delete', [ 'key' => $whole[1]['key'] ] );

// A picture that the types and the size let through but a PDF cannot place - a JPEG in CMYK colours - is refused with its code, and nothing of it is kept
$imagesNow = $tree( \Nino\Filesystem::path( $appData, Pdf::IMAGES ) );
$placed = $named( 'cmyk.jpg', $cmyk );
check( 'a picture that cannot be placed in a PDF (CMYK) is 400 with the code of the picture, and the pictures stay as they were', $placed[0] === 400 && $placed[1]['code'] === 'pdf_image_place' && $tree( \Nino\Filesystem::path( $appData, Pdf::IMAGES ) ) === $imagesNow );

// --- the log, deactivation ------------------------------------------------------------------------------------------

$log = static fn( string $action, array $data = [] ): string => \Nino\Modules\Pdf\Admin::log( $action, $data );
$writes = [ 'pdf/layout/save', 'pdf/layout/image', 'pdf/layout/image/delete', 'pdf/blocks/save', 'pdf/blocks/delete', 'pdf/templates/save', 'pdf/templates/delete', 'pdf/templates/duplicate' ];
$reads = [ 'pdf/layout/get', 'pdf/blocks/list', 'pdf/templates/list', 'pdf/templates/get', 'pdf/preview' ];
check( 'the activity log has a line for each action that writes - it names the key of the template or the block, or the kind of the picture, and what was done differs from one action to the next - and none for an action that reads', array_sum( array_map( static fn( string $action ): int => $log( $action, [ 'key' => 'offer-one', 'kind' => 'logo' ] ) !== '' ? 1 : 0, $writes ) ) === count( $writes )
	&& count( array_unique( array_map( static fn( string $action ): string => $log( $action, [ 'key' => 'offer-one', 'kind' => 'logo' ] ), $writes ) ) ) === count( $writes )
	&& str_contains( $log( 'pdf/templates/save', [ 'key' => 'offer-one' ] ), 'offer-one' ) === true && str_contains( $log( 'pdf/templates/delete', [ 'key' => 'offer-one' ] ), 'offer-one' ) === true && str_contains( $log( 'pdf/blocks/delete', [ 'key' => 'intro' ] ), 'intro' ) === true
	&& str_contains( $log( 'pdf/layout/image', [ 'kind' => 'logo' ] ), 'logo' ) === true && str_contains( $log( 'pdf/layout/image/delete', [ 'kind' => 'signature' ] ), 'signature' ) === true
	&& array_filter( array_map( static fn( string $action ): string => $log( $action, [ 'key' => 'offer-one', 'kind' => 'logo' ] ), $reads ) ) === [] );
check( 'the Dashboard tile counts the templates there are now', \Nino\Modules\Pdf\Admin::summary( $appData )['value'] === count( Pdf::keys( $appData ) ) && count( Pdf::keys( $appData ) ) === 7 );

// --- the font size of an element's own style -----------------------------------------------------------------------
pinFontSizeAllow();
pinFontSizeDrawn( $appData, $post, $content );

// --- the rows of a table by their parity, and the ground of a cell by its own style ------------------------------------
pinStripeSheet( $appData, $post );
pinStripeDrawn( $appData, $post, $content );
pinStripeInline( $appData, $post, $content );

// --- the margin of an address, in the design and in the element ------------------------------------------------------------
pinAddressMargin( $appData, $post, $content );

$files = glob( \Nino\Filesystem::path( $appData, Pdf::DIR ). '/*' );
check( 'deactivation takes the panel away and keeps /data/pdf', \Nino\Features::deactivate( $appData, 'pdf' ) === true && isset( \Nino\Admin\Admin::panels( $appData )['pdf'] ) === false
	&& glob( \Nino\Filesystem::path( $appData, Pdf::DIR ). '/*' ) === $files && is_file( \Nino\Filesystem::path( $appData, Pdf::TEMPLATES. '/offer-one.php' ) ) === true && count( $files ) === 4 );

// --- the panel's script, where node is on the path -------------------------------------------------------------------

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: pdf-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/pdf-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'pdf-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );

// --- pins: the font size of an element's own style (named, so that each can be broken on a copy) --------------------

/**
 *	What Allow keeps of a font-size: the elements that read it, the units, the range, the baits
 */
function pinFontSizeAllow(): void {

	$kept = static fn( string $tag, string $style ): string => Allow::html( '<'. $tag. ' style="'. $style. '">x</'. $tag. '>' );

	$every = true;

	foreach( [ 'p', 'div', 'h1', 'h2', 'h3', 'td', 'th', 'li', 'span' ] as $tag )
		$every = $every && str_contains( $kept( $tag, 'font-size: 12PT' ), 'style="font-size:12pt"' );

	check( 'a font-size of an inline style stays on a p, a div, h1 to h3, a td, a th, a li and a span, in the shape the engine reads it (lower case, no space)', $every === true );
	check( '...in pt, px, mm, em, rem and percent, with a fraction, and !important is no part of it', array_reduce( [ '12pt', '16px', '10mm', '1.5em', '2rem', '150%', '8.5pt' ], static fn( bool $all, string $size ): bool => $all && str_contains( $kept( 'p', 'font-size:'. $size. ' !important' ), 'style="font-size:'. $size. '"' ), true ) );
	check( '...and the range: 0.5 to 72 pt, whatever the unit - a value outside it, a negative one and a unit that is none are dropped with the whole style', array_reduce( [ '500pt', '0.2pt', '73pt', '-5pt', '1000px', '9em', '0.01em', '5%', '900%', '12', '12 pt', '12ptx', 'large', '' ], static fn( bool $all, string $size ): bool => $all && $kept( 'p', 'font-size:'. $size ) === '<p>x</p>', true ) );
	check( '...a baited value is dropped: calc, expression, url, var, a second value, a comment, an escape', array_reduce( [ 'calc(10pt + 2pt)', 'expression(alert(1))', 'url(javascript:alert(1))', 'var(--x)', '12pt 14pt', '12pt/20pt', '1\\32pt', '12pt/*x*/', 'inherit' ], static fn( bool $all, string $size ): bool => $all && $kept( 'p', 'font-size:'. $size ) === '<p>x</p>', true )
		&& $kept( 'p', "font-size:12pt\0;font-size:evil" ) !== '<p style="font-size:evil">x</p>' );
	check( '...the last valid declaration wins, as a cascade has it, and what else a style says is dropped', $kept( 'p', 'font-size:20pt;font-size:evil' ) === '<p style="font-size:20pt">x</p>' && $kept( 'p', 'font-size:10pt;font-size:14pt' ) === '<p style="font-size:14pt">x</p>'
		&& $kept( 'p', 'color:red;position:absolute;font-size:11pt;background:url(x)' ) === '<p style="font-size:11pt">x</p>' );
	check( '...with the alignment where an element reads one (a p, a div, a heading, a cell), and with the size alone on a li and a span', $kept( 'td', 'text-align:right; font-size:9pt' ) === '<td style="text-align:right;font-size:9pt">x</td>' && $kept( 'p', 'font-size:9pt;text-align:center' ) === '<p style="text-align:center;font-size:9pt">x</p>'
		&& $kept( 'span', 'text-align:right;font-size:9pt' ) === '<span style="font-size:9pt">x</span>' && $kept( 'li', 'text-align:right' ) === '<li>x</li>' );
	check( '...and a tag the list does not know keeps none of it: a h4 is its text', Allow::html( '<h4 style="font-size:30pt">x</h4>' ) === 'x' && Allow::html( '<font style="font-size:30pt">x</font>' ) === 'x' );
}

/**
 *	What is drawn: the size of the glyphs and the height of the lines, in the body, a header, a footer, a list, a cell
 */
function pinFontSizeDrawn( array &$appData, callable $post, callable $content ): void {

	$layout = static fn( string $header, string $footer ): array => $post( 'pdf/layout/save', [ 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => $header, 'footer' => $footer ] );
	$layout( '', '' );
	$drawn = static function( string $html ) use ( &$appData, $content ): array {

		$pdf = (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $html ] ] );
		preg_match_all( '/BT \/F\d+ ([\d.]+) Tf ([\d.]+) ([\d.]+) Td \(([^)]*)\) Tj ET/', $content( $pdf, 0 ), $found, PREG_SET_ORDER );
		$out = [];

		foreach( $found as $run )
			$out[ trim( $run[4] ) ][] = [ (float) $run[1], (float) $run[3] ];

		return $out;
	};
	$size = static fn( string $html, string $word = 'A' ): float => $drawn( $html )[$word][0][0] ?? -1.0;
	$near = static fn( float $a, float $b ): bool => abs( $a - $b ) < 0.01;

	check( 'a p with a font-size is drawn in that size: 20pt is 20, 16px is 12, 10mm is 28.346, 2em and 2rem are twice the base of 10, 150% is 15', $near( $size( '<p style="font-size:20pt">A</p>' ), 20.0 ) && $near( $size( '<p style="font-size:16px">A</p>' ), 12.0 )
		&& $near( $size( '<p style="font-size:10mm">A</p>' ), 10 * 72 / 25.4 ) && $near( $size( '<p style="font-size:2em">A</p>' ), 20.0 ) && $near( $size( '<p style="font-size:2rem">A</p>' ), 20.0 ) && $near( $size( '<p style="font-size:150%">A</p>' ), 15.0 ) );
	check( '...a div and a heading as well, a heading in the size it is asked for and not the one the design gives it', $near( $size( '<div style="font-size:20pt">A</div>' ), 20.0 ) && $near( $size( '<h1 style="font-size:12pt">A</h1>' ), 12.0 ) && $near( $size( '<h2 style="font-size:50%">A</h2>' ), 5.0 ) && $near( $size( '<h3 style="font-size:20pt">A</h3>' ), 20.0 ) );
	check( '...the paragraph after it is set in the size of the layout again, and so is one that says nothing', $near( $size( '<p style="font-size:20pt">A</p><p>B</p>', 'B' ), 10.0 ) && $near( $size( '<p>A</p>' ), 10.0 ) );

	// The line height follows the size: 1.35 times it, in a div with a break between two lines
	$lines = $drawn( '<div style="font-size:20pt">A<br>B</div><div style="font-size:8pt">C<br>D</div>' );
	check( '...the height of a line follows the size as it does for the rules of the design: 1.35 times it', $near( $lines['A'][0][1] - $lines['B'][0][1], 27.0 ) && $near( $lines['C'][0][1] - $lines['D'][0][1], 10.8 ) );

	// What is inside takes it: an inline child, a span with its own size, relative to what it stands in
	$inner = $drawn( '<p style="font-size:20pt">A <b>B</b> <span style="font-size:50%">C</span> <span style="font-size:2em">D</span> <a>E</a> <span style="font-size:8em">F</span></p>' );
	check( '...and what is inside takes the size: bold, a link, a span with no size of its own; a span with one is relative to what it is in - 50% of 20 is 10, 2em is 40 - and never more than 72 pt', $near( $inner['B'][0][0], 20.0 ) && $near( $inner['E'][0][0], 20.0 ) && $near( $inner['C'][0][0], 10.0 ) && $near( $inner['D'][0][0], 40.0 ) && $near( $inner['F'][0][0], 72.0 )
		&& $near( $size( '<span style="font-size:14pt">A</span> B' ), 14.0 ) && $near( $size( '<span style="font-size:14pt">A</span> B', 'B' ), 10.0 ) );
	check( '...a line holds the tallest of its sizes: a 20 pt word with a 10 pt one beside it is set as high as a line of 20 pt', $near( $drawn( '<p><span style="font-size:20pt">A</span> B</p><p>C</p>' )['C'][0][1], $drawn( '<p style="font-size:20pt">A B</p><p>C</p>' )['C'][0][1] ) );

	$list = $drawn( '<ul><li style="font-size:20pt">A</li><li>B</li></ul><ol><li style="font-size:20pt">C</li></ol>' );
	check( '...a li takes it, with its bullet and its number: the marker of the item is as big as the text of the item', $near( $list['A'][0][0], 20.0 ) && $near( $list['B'][0][0], 10.0 ) && $near( $list['C'][0][0], 20.0 ) && $near( $list["\\225"][0][0], 20.0 ) && $near( $list["\\225"][1][0], 10.0 ) && $near( $list['1.'][0][0], 20.0 ) );
	$cells = $drawn( '<table><tr><td style="font-size:20pt">A</td><th style="font-size:14px">B</th><td>C</td></tr></table>' );
	check( '...a td and a th take it, the cell next to them does not, and a cell has its rules\' size under it: a th in bold at 14px is 10.5', $near( $cells['A'][0][0], 20.0 ) && $near( $cells['B'][0][0], 10.5 ) && $near( $cells['C'][0][0], 10.0 ) );

	// The header and the footer: the size of a text there, relative to the size of the band
	$layout( '<p style="font-size:18pt">Head</p><p>Under</p>', '<div style="font-size:7pt">Foot [[page]]</div>' );
	$page = $drawn( '<p>A</p>' );
	check( '...in the header and in the footer: a size of its own in the band, the rest in the size of the layout', $near( $page['Head'][0][0], 18.0 ) && $near( $page['Under'][0][0], 10.0 ) && $near( $page['Foot 1'][0][0], 7.0 ) && $near( $page['A'][0][0], 10.0 ) );
	$layout( '', '' );

	// The same bytes without it: a style that says no size of its own is no change at all
	$plain = '<h1>Title</h1><p>Some <b>text</b> and <a>a link</a>.</p><ul><li>one</li><li>two</li></ul><table><tr><th>Item</th><td>Cell</td></tr></table><p style="text-align:right">right</p>';
	$kept = str_replace( [ '<p>', '<li>', '<td>' ], [ '<p style="font-size:large">', '<li style="font-size:calc(1pt + 2pt)">', '<td style="font-size:500pt">' ], $plain );
	check( '...and a document that gives no size of its own is the same bytes with the values this list drops, and the same bytes as the source made of it: a hash held for it', Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $plain ] ] ) === Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $kept ] ] )
		&& hash( 'sha256', (string) Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $plain ] ] ) ) === '2719be35d3e95742c87769a6f563ba4b62870c0edfc0a605d81b7ce5f0792cfe' );
}

// --- pins: the rows of a table by their parity (named, so that each can be broken on a copy) -----------------------------

/**
 *	What the sheet makes of the selectors of even and odd rows: read, refused when they are none of the six, the properties of a cell, written back
 */
function pinStripeSheet( array &$appData, callable $post ): void {

	$rows = [ 'tr:nth-child(even)', 'tr:nth-child(odd)', 'tr:nth-child(even) td', 'tr:nth-child(odd) td', 'tr:nth-child(even) th', 'tr:nth-child(odd) th' ];
	$read = Sheet::check( implode( " { background: #f3f5f7 }\n", $rows ). " { background: #f3f5f7 }" );
	check( 'the sheet reads the selectors of even and odd rows - alone, and followed by td or th - with the properties of a cell', array_keys( $read['rules'] ?? [] ) === $rows && array_reduce( $rows, static fn( bool $all, string $row ): bool => $all && Sheet::APPLIES[$row] === Sheet::APPLIES['td'], true )
		&& Sheet::check( 'tr:nth-child(even) td { padding: 2mm; border-bottom: 1pt solid #112233; font-weight: bold; text-align: right }' )['rules']['tr:nth-child(even) td']['padding'] === [ 2.0, 2.0, 2.0, 2.0 ] );
	check( '...case and the space between the two words are no matter, and a list of them is a rule for each', array_keys( Sheet::check( "TR:NTH-CHILD(EVEN)   TD,\n tr:nth-child(odd)\tth { color: #112233 }" )['rules'] ?? [] ) === [ 'tr:nth-child(even) td', 'tr:nth-child(odd) th' ] );
	$refused = true;

	foreach( [ 'tr:nth-child(2n+1)', 'tr:nth-child(3)', 'tr:nth-child(evil)', 'tr:nth-child(even', 'tr:nth-child(even) p', 'tr:nth-child(even) tr', 'tr:nth-child(even) > td', 'tr:nth-child(even) td:first-child', 'td:nth-child(even)', 'tr:nth-child()', ':nth-child(even)', 'tr:nth-child(even) td td', 'tr:nth-last-child(odd)', 'tr:nth-child(odd) td, evil' ] as $bait ) {

		$one = Sheet::check( $bait. ' { background: #f3f5f7 }' );
		$refused = $refused && ( $one['code'] ?? '' ) !== '' && isset( $one['rules'] ) === false;
	}

	check( '...and what is not one of the six is refused with its line: a formula, a number, a word, a child that is none, an element the row has no rule for', $refused === true && Sheet::check( "p {}\ntr:nth-child(2n+1) { background: #f3f5f7 }" ) === [ 'line' => 2, 'code' => 'selector', 'detail' => 'tr:nth-child(2n+1)', 'selector' => '' ] );
	check( '...a colour that is no six hex digits is refused for them as for a cell', array_reduce( [ 'rgb(1,2,3)', 'url(x)', '#fff', '#12345', '#1234567', 'red', '#12345g' ], static fn( bool $all, string $value ): bool => $all && Sheet::check( 'tr:nth-child(even) td { background: '. $value. ' }' )['code'] === 'value', true ) );
	$merged = Sheet::merge( Sheet::defaults( 'helvetica', 10 ), $read['rules'] );
	check( '...written as text it reads back as the same rules, and a sheet that names none of them writes none', Sheet::check( Sheet::text( $merged ) )['rules'] == $merged && str_contains( Sheet::text( $merged ), 'tr:nth-child(odd) th { background: #f3f5f7 }' ) === true
		&& str_contains( Sheet::text( Sheet::defaults( 'helvetica', 10 ) ), 'nth-child' ) === false );
	check( '...the panel saves a layout with them, and a layout with a bait is refused whole', $post( 'pdf/layout/save', [ 'design' => 'tr:nth-child(even) td { background: #f3f5f7 }', 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] )[0] === 200
		&& $post( 'pdf/layout/save', [ 'design' => "tr:nth-child(even) td { background: #f3f5f7 }\ntr:nth-child(2n+1) td { background: #f3f5f7 }", 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] )[0] === 400
		&& str_contains( (string) Pdf::layout( $appData )['design'], 'nth-child(2n' ) === false );
}

/**
 *	Which rows of a table the design fills: the boxes behind the words of a PDF, row by row, over any number of pages
 */
function pinStripeDrawn( array &$appData, callable $post, callable $content ): void {

	$layout = static fn( string $design ): int => $post( 'pdf/layout/save', [ 'design' => $design, 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] )[0];
	$colour = static fn( string $hex ): string => implode( ' ', array_map( static fn( string $channel ): string => Pdf\Document\Document::num( round( hexdec( $channel ) / 255, 2 ) ), str_split( $hex, 2 ) ) );
	$laid = static function( string $html ) use ( &$appData ): string {
		return Pdf::compose( $appData, [ 'name' => 'T', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'html' => $html ] ] ], [], [], false );
	};

	// The words that stand on a box of a colour, in the order of the pages: a word is on the box when its baseline lies between the box's foot and head
	$filled = static function( string $pdf, string $hex ) use ( $content, $colour ): array {

		$words = [];

		for( $page = 0; $page < preg_match_all( '#/Type /Page\b#', $pdf ); $page++ ) {

			$stream = $content( $pdf, $page );
			preg_match_all( '/q '. preg_quote( $colour( $hex ), '/' ). ' rg ([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+) re f Q/', $stream, $boxes, PREG_SET_ORDER );
			preg_match_all( '/([\d.]+) ([\d.]+) Td \(([^)]*)\) Tj/', $stream, $runs, PREG_SET_ORDER );

			foreach( $runs as $run )
				foreach( $boxes as $box )
					if( (float) $run[1] >= (float) $box[1] && (float) $run[1] < (float) $box[1] + (float) $box[3] && (float) $run[2] > (float) $box[2] && (float) $run[2] < (float) $box[2] + (float) $box[4] ) {
						$words[] = $run[3];
						continue 2;
					}
		}

		return $words;
	};
	$body = static fn( int $count, string $first = '<td>%s</td>' ): string => implode( '', array_map( static fn( int $row ): string => '<tr>'. sprintf( $first, 'R'. $row ). '<td>x'. $row. '</td></tr>', range( 1, $count ) ) );
	$head = '<thead><tr><th>Head</th><th>Two</th></tr></thead>';

	$layout( 'tr:nth-child(even) td { background: #f3f5f7 }' );
	$four = '<table>'. $head. '<tbody>'. $body( 4 ). '</tbody></table>';
	check( 'tr:nth-child(even) td fills the cells of the second and the fourth row of the body, and no other - the head row is not counted', $filled( $laid( $four ), 'f3f5f7' ) === [ 'R2', 'x2', 'R4', 'x4' ] );
	$layout( 'tr:nth-child(odd) td { background: #f3f5f7 }' );
	check( '...tr:nth-child(odd) td the first and the third', $filled( $laid( $four ), 'f3f5f7' ) === [ 'R1', 'x1', 'R3', 'x3' ] );
	check( '...a table with no head row counts from its first row, a head row of th inside the body is not counted, and the rows of a tbody and a tfoot go on from one another',
		$filled( $laid( '<table>'. $body( 3 ). '</table>' ), 'f3f5f7' ) === [ 'R1', 'x1', 'R3', 'x3' ]
		&& $filled( $laid( '<table><tr><th>Head</th><th>Two</th></tr>'. $body( 3 ). '</table>' ), 'f3f5f7' ) === [ 'R1', 'x1', 'R3', 'x3' ]
		&& $filled( $laid( '<table><tbody>'. $body( 2 ). '</tbody><tfoot><tr><td>Sum</td><td>y</td></tr></tfoot></table>' ), 'f3f5f7' ) === [ 'R1', 'x1', 'Sum', 'y' ] );

	$layout( 'tr:nth-child(even) { background: #f3f5f7 }' );
	$mixed = '<table>'. $head. '<tbody>'. $body( 4, '<th>%s</th>' ). '</tbody></table>';
	check( 'tr:nth-child(even) alone fills every cell of the row, a th and a td alike; a row that starts with a th among tds is a row of the body and is counted', $filled( $laid( $mixed ), 'f3f5f7' ) === [ 'R2', 'x2', 'R4', 'x4' ] );
	$layout( 'tr:nth-child(even) th { background: #f3f5f7 }' );
	check( '...tr:nth-child(even) th the th of the even rows only - the th of the head is not among them - and no td', $filled( $laid( $mixed ), 'f3f5f7' ) === [ 'R2', 'R4' ] );
	$layout( 'tr:nth-child(odd) td { background: #f3f5f7 }' );
	check( '...tr:nth-child(odd) td leaves the th of a row alone', $filled( $laid( $mixed ), 'f3f5f7' ) === [ 'x1', 'x3' ] );

	// The count runs on over a break of the page, and the head row that is drawn again there is none of the rows
	$layout( 'tr:nth-child(even) td { background: #f3f5f7 }' );
	$long = '<table>'. $head. '<tbody>'. $body( 90 ). '</tbody></table>';
	$pdf = $laid( $long );
	$even = [];

	for( $row = 2; $row <= 90; $row += 2 )
		array_push( $even, 'R'. $row, 'x'. $row );

	check( 'over a break of the page the count runs on: 90 rows on '. preg_match_all( '#/Type /Page\b#', $pdf ). ' pages, the rows filled are the even ones and all of them, and the head drawn again at the top of a page is not filled', preg_match_all( '#/Type /Page\b#', $pdf ) >= 2 && $filled( $pdf, 'f3f5f7' ) === $even
		&& substr_count( implode( ' ', array_map( static fn( int $page ): string => $content( $pdf, $page ), range( 0, preg_match_all( '#/Type /Page\b#', $pdf ) - 1 ) ) ), '(Head) Tj' ) === preg_match_all( '#/Type /Page\b#', $pdf ) );

	// The order of the rules: the tag, the row, the tag within the row - each over the one before, property by property
	$layout( 'td { background: #a0c0e0; color: #00ff00 } tr:nth-child(even) { background: #b0d0f0 } tr:nth-child(even) td { color: #ff0000 }' );
	$order = $laid( $four );
	check( 'the rule of a row is over the rule of td, and the rule of the tag within the row over both - a property by property: the even rows are on the row\'s ground and in the red of td within it, the odd ones on the ground of td', $filled( $order, 'b0d0f0' ) === [ 'R2', 'x2', 'R4', 'x4' ] && $filled( $order, 'a0c0e0' ) === [ 'R1', 'x1', 'R3', 'x3' ]

		&& preg_match_all( '/q '. preg_quote( $colour( 'ff0000' ), '/' ). ' rg BT [^\n]*\((?:R|x)[24]\) Tj ET/', $content( $order, 0 ) ) === 4 );
	$layout( 'td { background: #a0c0e0 } tr:nth-child(even) td { background: #c0e0f0 } tr:nth-child(even) { background: #b0d0f0 }' );
	check( '...and the order of the lines in the text of the design is no matter', $filled( $laid( $four ), 'c0e0f0' ) === [ 'R2', 'x2', 'R4', 'x4' ] && $filled( $laid( $four ), 'b0d0f0' ) === [] );

	// The same bytes without the rules, and with rules that name no row
	$layout( '' );
	$plain = $laid( $four );
	$layout( 'tr:nth-child(odd) th { background: #f3f5f7 } tr:nth-child(even) td { color: #000000 }' );
	check( 'a table is laid out in the same bytes where the design names no row, or names a row it does not have - an odd th in a table whose th stand in the head alone', $laid( $four ) === $plain );
	$layout( '' );
}

/**
 *	The ground of a cell by its own style: kept in one shape, drawn over the row and over the design, and nothing else of a style is
 */
function pinStripeInline( array &$appData, callable $post, callable $content ): void {

	$kept = static fn( string $tag, string $style ): string => Allow::html( '<table><tr><'. $tag. ' style="'. $style. '">x</'. $tag. '></tr></table>' );
	$only = static fn( string $tag, string $style ): string => Allow::html( '<'. $tag. ' style="'. $style. '">x</'. $tag. '>' );

	check( 'a background of an inline style stays on a td, a th and a tr, in the one shape the engine reads: six hex digits, lower case, no space', $kept( 'td', 'background: #AABBCC' ) === '<table><tr><td style="background:#aabbcc">x</td></tr></table>' && $kept( 'th', 'background:#aabbcc ;' ) === '<table><tr><th style="background:#aabbcc">x</th></tr></table>'
		&& Allow::html( '<table><tr style="BACKGROUND : #AABBCC !important"><td>x</td></tr></table>' ) === '<table><tr style="background:#aabbcc"><td>x</td></tr></table>' );
	check( '...with the alignment and the size on a cell, in the order of the list, and on a tr the background alone', $kept( 'td', 'background:#aabbcc;font-size:9pt;text-align:right' ) === '<table><tr><td style="text-align:right;font-size:9pt;background:#aabbcc">x</td></tr></table>'
		&& Allow::html( '<table><tr style="text-align:right;font-size:9pt;background:#aabbcc"><td>x</td></tr></table>' ) === '<table><tr style="background:#aabbcc"><td>x</td></tr></table>' );
	check( '...and the last valid declaration wins, as a cascade has it', $kept( 'td', 'background:#111111;background:#222222' ) === '<table><tr><td style="background:#222222">x</td></tr></table>' && $kept( 'td', 'background:#111111;background:evil' ) === '<table><tr><td style="background:#111111">x</td></tr></table>' );

	$baits = [ 'url(javascript:alert(1))', 'url(x)', 'rgb(1,2,3)', 'rgba(1,2,3,.5)', 'hsl(0,0%,0%)', '#fff', '#ffff', '#12345', '#1234567', '#12345g', '#12345678', 'red', 'transparent', 'var(--x)', 'expression(alert(1))', '#aabbcc url(x)', '#aabbcc #ddeeff', '#aabbcc/*x*/', '\\23aabbcc', '#aabbcc\0', 'inherit', '' ];
	$dropped = true;

	foreach( $baits as $bait )
		foreach( [ 'td', 'th' ] as $tag )
			$dropped = $dropped && $kept( $tag, 'background:'. $bait ) === '<table><tr><'. $tag. '>x</'. $tag. '></tr></table>';

	check( '...a value that is no six hex digits - a url, rgb(), hsl(), three or eight digits, a word, a var, a second value, a comment, an escape, a null byte - is dropped with the declaration', $dropped === true
		&& Allow::html( '<table><tr style="background:url(x)"><td>x</td></tr></table>' ) === '<table><tr><td>x</td></tr></table>' && Allow::html( '<table><tr style="background:rgb(1,2,3)"><td>x</td></tr></table>' ) === '<table><tr><td>x</td></tr></table>' );
	check( '...and no other element keeps a background: a p, a div, a heading, a li, a span, a table, a thead, a tbody and a tfoot are their text or their tag alone', array_reduce( [ 'p', 'div', 'h1', 'li', 'span', 'table', 'thead', 'tbody', 'tfoot' ], static fn( bool $all, string $tag ): bool => $all && str_contains( Allow::html( '<'. $tag. ' style="background:#aabbcc">x</'. $tag. '>' ), 'background' ) === false, true )
		&& str_contains( $only( 'td', 'background-color:#aabbcc' ), 'style' ) === false && str_contains( Allow::html( '<table><tr><td style="background:#aabbcc" bgcolor="#112233" onclick="x()">x</td></tr></table>' ), 'bgcolor' ) === false );

	$post( 'pdf/layout/save', [ 'design' => 'tr:nth-child(even) td { background: #f3f5f7 }', 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );
	$laid = static function( string $html ) use ( &$appData ): string {
		return Pdf::compose( $appData, [ 'name' => 'T', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'html' => $html ] ] ], [], [], false );
	};
	$boxes = static function( string $pdf ) use ( $content ): array {

		preg_match_all( '/q ([\d.]+ [\d.]+ [\d.]+) rg [\d.]+ [\d.]+ [\d.]+ [\d.]+ re f Q/', $content( $pdf, 0 ), $found );

		return $found[1];
	};
	$colour = static fn( string $hex ): string => implode( ' ', array_map( static fn( string $channel ): string => Pdf\Document\Document::num( round( hexdec( $channel ) / 255, 2 ) ), str_split( $hex, 2 ) ) );
	$table = '<table><tr><td>A</td><td>B</td></tr><tr style="background:#112233"><td>C</td><td style="background:#445566">D</td></tr><tr><td style="background:#778899">E</td><td>F</td></tr></table>';
	$drawn = $boxes( $laid( $table ) );

	check( 'drawn: the box of a cell is its own colour over the colour of its row, and the colour of a row over the design - D on its own, C on the row\'s, E on its own, F on the design of the even row it is not in', $drawn === [ $colour( '112233' ), $colour( '445566' ), $colour( '778899' ) ] );
	$even = $boxes( $laid( '<table><tr><td>A</td></tr><tr><td>B</td></tr><tr><td style="background:#778899">C</td></tr><tr><td style="background:#778899">D</td></tr></table>' ) );
	check( '...a cell with a colour of its own on an even row is on its own and not on the design\'s, and the cell of an even row with none is on the design\'s', $even === [ $colour( 'f3f5f7' ), $colour( '778899' ), $colour( '778899' ) ] );
	$headed = $boxes( $laid( '<table><thead><tr style="background:#112233"><th style="background:#445566">H</th><th>I</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>' ) );
	check( '...a head row takes it too: the th of its own and the th of the row', $headed === [ $colour( '445566' ), $colour( '112233' ) ] );

	$plain = '<table><tr><th>Head</th><td>Cell</td></tr><tr><td>A</td><td>B</td></tr></table>';
	$baited = '<table><tr style="background:url(x)"><th style="background:rgb(1,2,3)">Head</th><td style="background:#fff">Cell</td></tr><tr style="background:#12345g"><td style="background:#aabbcc url(x)">A</td><td style="background:red">B</td></tr></table>';
	check( 'a table with the baits in its styles is laid out in the same bytes as the table without them', $laid( $baited ) === $laid( $plain ) && $boxes( $laid( $baited ) ) === $boxes( $laid( $plain ) ) );
	$post( 'pdf/layout/save', [ 'design' => '', 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );
	check( '...and a table with no style at all is the same bytes with a style that says nothing of a ground', $laid( $plain ) === $laid( str_replace( [ '<th>', '<td>' ], [ '<th style="color:red;position:absolute">', '<td style="float:left">' ], $plain ) ) );
}

// --- pins: the margin of an address block and of a paragraph (named, so that each can be broken on a copy) --------------

/**
 *	Where the text of an address stands: the margin of .address in the design and the margin of a p and a div in their own style, on all four sides
 */
function pinAddressMargin( array &$appData, callable $post, callable $content ): void {

	$layout = static fn( string $design ): int => $post( 'pdf/layout/save', [ 'design' => $design, 'format' => 'a4', 'margins' => [ 20, 20, 20, 20 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] )[0];
	$laid = static function( string $html ) use ( &$appData ): string {
		return Pdf::compose( $appData, [ 'name' => 'T', 'tag' => 'offer', 'blocks' => [ [ 'type' => 'html', 'html' => $html ] ] ], [], [], false );
	};
	$at = static function( string $pdf, string $word ) use ( $content ): array {
		preg_match( '#([\d.]+) ([\d.]+) Td \('. preg_quote( $word, '#' ). '\) Tj#', $content( $pdf, 0 ), $found );
		return [ (float) ( $found[1] ?? -1 ), (float) ( $found[2] ?? -1 ) ];
	};
	$mm = Pdf\Document\Document::MM;
	$near = static fn( float $a, float $b ): bool => abs( $a - $b ) < 0.01;
	$text = '<p>Lead</p><p class="address">Street</p><p>Next</p>';

	$layout( '' );
	$base = $laid( $text );
	$layout( '.address { margin: 30mm 0 30mm 20mm }' );
	$far = $laid( $text );
	$layout( '.address { margin: 40mm 0 50mm 20mm }' );
	$farther = $laid( $text );
	check( 'the margin of .address in the design: the left is the indent of the block (20 mm to the right), the top and the bottom the space above and below (10 mm more above and 20 mm more below for a margin that is 10 and 20 larger)',
		$near( ( $at( $far, 'Street' )[0] - $at( $base, 'Street' )[0] ) / $mm, 20.0 ) && $near( $at( $far, 'Lead' )[0], $at( $base, 'Lead' )[0] ) && $near( ( $at( $far, 'Street' )[1] - $at( $farther, 'Street' )[1] ) / $mm, 10.0 )
		&& $near( ( $at( $far, 'Next' )[1] - $at( $farther, 'Next' )[1] ) / $mm, 10.0 + 20.0 ) && $near( $at( $far, 'Next' )[0], $at( $base, 'Next' )[0] ) );

	$right = '<p class="address" align="right">Street</p>';
	$layout( '' );
	$flush = $at( $laid( $right ), 'Street' )[0];
	$layout( '.address { margin: 0 40mm 0 0 }' );
	$narrow = $laid( $right );
	$long = '<p class="address">'. str_repeat( 'town ', 60 ). '</p>';
	preg_match_all( '/\(town[^)]*\) Tj/', $content( $laid( $long ), 0 ), $narrowLines );
	$layout( '' );
	preg_match_all( '/\(town[^)]*\) Tj/', $content( $laid( $long ), 0 ), $wideLines );
	check( '...the right is the room kept free on that side: text set to the right ends 40 mm earlier, and a long text breaks into more lines', $near( ( $flush - $at( $narrow, 'Street' )[0] ) / $mm, 40.0 ) && count( $narrowLines[0] ) > count( $wideLines[0] ) );

	$layout( '.address { margin: 0 5mm 0 5mm }' );
	$inline = static fn( string $tag, string $style, string $class = ' class="address"' ): string => '<p>Lead</p><'. $tag. $class. ' style="'. $style. '">Street</'. $tag. '><p>Next</p>';
	$ownLeft = ( $at( $laid( $inline( 'p', 'margin:10mm 0 0 20mm' ) ), 'Street' )[0] - $at( $base, 'Street' )[0] ) / $mm;
	check( 'the margin of the element\'s own style, over the design: 10mm 0 0 20mm on a p with class address indents it by 20 mm - the 5 mm of the design is no part of it - in a div as well, and in a p and a div that are no address', $near( $ownLeft, 20.0 )
		&& $near( ( $at( $laid( $inline( 'div', 'margin:10mm 0 0 20mm' ) ), 'Street' )[0] - $at( $base, 'Street' )[0] ) / $mm, 20.0 )
		&& $near( ( $at( $laid( $inline( 'p', 'margin:10mm 0 0 20mm', '' ) ), 'Street' )[0] - $at( $base, 'Street' )[0] ) / $mm, 20.0 ) && $near( ( $at( $laid( $inline( 'div', 'margin:10mm 0 0 20mm', '' ) ), 'Street' )[0] - $at( $base, 'Street' )[0] ) / $mm, 20.0 ) );

	$layout( '' );
	$spaced = static fn( string $style ): array => $at( $laid( '<p>Lead</p><p style="'. $style. '">Street</p><p>Next</p>' ), 'Street' );
	$below = static fn( string $style ): float => $at( $laid( '<p>Lead</p><p style="'. $style. '">Street</p><p>Next</p>' ), 'Next' )[1];
	check( '...the top and the bottom are the space above and below: 10 mm more above and 20 mm more below for the same difference in the style', $near( ( $spaced( 'margin:30mm 0 0 0' )[1] - $spaced( 'margin:40mm 0 0 0' )[1] ) / $mm, 10.0 ) && $near( ( $below( 'margin:0 0 30mm 0' ) - $below( 'margin:0 0 50mm 0' ) ) / $mm, 20.0 ) );
	check( '...the units: pt as they are, px at 0.75 pt, mm, em of the size of the element (2em of 10 pt is 20 pt), rem of the base size, a bare 0 - and one to four values in the order of CSS (two: vertical horizontal; three: top horizontal bottom)',
		$near( $spaced( 'margin:0 0 0 20pt' )[0] - $spaced( '' )[0], 20.0 ) && $near( $spaced( 'margin:0 0 0 20px' )[0] - $spaced( '' )[0], 15.0 ) && $near( $spaced( 'margin:0 0 0 2em' )[0] - $spaced( '' )[0], 20.0 ) && $near( $spaced( 'font-size:20pt;margin:0 0 0 1em' )[0] - $spaced( 'font-size:20pt' )[0], 20.0 )
		&& $near( $spaced( 'margin:0 0 0 2rem' )[0] - $spaced( '' )[0], 20.0 ) && $near( $spaced( 'margin:0 10mm' )[0], $spaced( 'margin:0 0 0 10mm' )[0] ) && $near( $spaced( 'margin:0 0 0 10mm' )[0] - $spaced( '' )[0], 10 * $mm )
		&& $near( $spaced( 'margin:5mm 3mm 7mm' )[0], $spaced( 'margin:5mm 3mm 7mm 3mm' )[0] ) && $near( $spaced( 'margin:5mm 3mm 7mm' )[1], $spaced( 'margin:5mm 9mm 7mm 3mm' )[1] ) && $near( $spaced( 'margin:10mm' )[0], $spaced( 'margin:10mm 10mm 10mm 10mm' )[0] ) );

	$kept = static fn( string $tag, string $style ): string => Allow::html( '<'. $tag. ' style="'. $style. '">x</'. $tag. '>' );
	check( 'Allow keeps a margin on a p and a div in the one shape, after the size, and no other element keeps one - a span, a li, a heading, a td, a th, a tr', $kept( 'p', 'MARGIN : 10MM  0 0 20mm !important' ) === '<p style="margin:10mm 0 0 20mm">x</p>' && $kept( 'div', 'margin:0' ) === '<div style="margin:0">x</div>'
		&& $kept( 'p', 'margin:1em;font-size:9pt;text-align:right' ) === '<p style="text-align:right;font-size:9pt;margin:1em">x</p>' && $kept( 'p', 'margin:1mm;margin:2mm' ) === '<p style="margin:2mm">x</p>'
		&& array_reduce( [ 'span', 'li', 'h1', 'td', 'th', 'tr' ], static fn( bool $all, string $tag ): bool => $all && str_contains( Allow::html( '<table><tr><td><'. $tag. ' style="margin:5mm">x</'. $tag. '></td></tr></table>' ), 'margin' ) === false, true ) );
	$baits = [ 'auto', '-5mm', '5mm -5mm', 'calc(1mm + 2mm)', 'var(--x)', 'url(x)', 'inherit', '10', '5 5mm', '201mm', '20.1em', '1e3mm', '5mm 5mm 5mm 5mm 5mm', '5mm/2', '5mm !', '5mm\0', '5mm /*x*/', '\\35mm', '+5mm', '', '5 mm', '9999pt', '1000px' ];
	$dropped = true;

	foreach( $baits as $bait )
		$dropped = $dropped && $kept( 'p', 'margin:'. $bait ) === '<p>x</p>';

	check( '...a value that is no length of one to four - auto, a negative one, a calc, a var, a number with no unit, more than 200 mm, a fifth value, an escape, a comment - is dropped with the declaration', $dropped === true );

	$plain = '<p>Lead</p><p class="address">Street</p><div>Box</div><p>Next</p>';
	$baited = '<p style="margin:auto">Lead</p><p class="address" style="margin:-5mm 0 0 calc(1mm + 2mm)">Street</p><div style="margin:201mm">Box</div><p style="margin:10">Next</p>';
	check( '...and a document with those in its styles is the same bytes as the document without them', $laid( $baited ) === $laid( $plain ) );
	check( 'the design takes the four sides for .address alone: a left or a right on p is refused, and all four sides of .address are taken', $layout( 'p { margin: 0 5mm 0 0 }' ) === 400 && $layout( '.address { margin: 1mm 2mm 3mm 4mm }' ) === 200 && Sheet::check( '.address { margin: 1mm 2mm 3mm 4mm }' )['rules']['.address']['margin'] === [ 1.0, 2.0, 3.0, 4.0 ] );
	$layout( '' );
}

// --- a TrueType font written by hand ------------------------------------------------------------------------------

// The width of the glyph of a character in the font pdfFontFixture() writes: A is 645, the others 500 to 580
function pdfFontWidth( int $unicode ): int {
	return $unicode === 65 ? 645 : 500 + ( $unicode % 9 ) * 10;
}

/**
 *	The bytes of a TrueType font with the numbers of Satoshi Regular (1000 units, ascender 1010, descender -240, cap height 716, x-height 484, A 645) and
 *	empty outlines: a glyph for every character of WinAnsi but the y diaeresis. $change: weight, angle (degrees), class (OS/2 family class), em, asc, desc,
 *	fstype, wide (the width of A), magic (the first four bytes), drop (a table left out), headmagic, cmapplatform, cmapcut
 */
function pdfFontFixture( array $change = [] ): string {

	$o = $change + [ 'weight' => 400, 'angle' => 0, 'class' => 0, 'em' => 1000, 'asc' => 1010, 'desc' => -240, 'fstype' => 0, 'wide' => 645, 'magic' => "\x00\x01\x00\x00", 'drop' => '', 'headmagic' => 0, 'cmapplatform' => 3, 'cmapcut' => false ];
	$scale = $o['em'] / 1000;
	$chars = [];

	for( $code = 32; $code < 256; $code++ ) {

		$unicode = $code >= 0x80 && $code <= 0x9F ? [ 0x20AC, 0, 0x201A, 0x0192, 0x201E, 0x2026, 0x2020, 0x2021, 0x02C6, 0x2030, 0x0160, 0x2039, 0x0152, 0, 0x017D, 0, 0, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2013, 0x2014, 0x02DC, 0x2122, 0x0161, 0x203A, 0x0153, 0, 0x017E, 0x0178 ][$code - 0x80] : $code;

		if( $unicode === 0 )
			$unicode = 0x2022;

		if( $code !== 0xFF )
			$chars[$unicode] = true;
	}

	ksort( $chars );
	$glyphs = array_keys( $chars );
	$hmtx = pack( 'nn', (int) ( 500 * $scale ), 0 );
	$segments = '';
	$ends = $starts = $deltas = $ranges = '';

	foreach( $glyphs as $at => $unicode ) {

		$width = $unicode === 65 ? $o['wide'] : pdfFontWidth( $unicode );
		$hmtx .= pack( 'nn', (int) round( $width * $scale ), 0 );
		$ends .= pack( 'n', $unicode );
		$starts .= pack( 'n', $unicode );
		$deltas .= pack( 'n', ( $at + 1 - $unicode ) & 0xFFFF );
		$ranges .= pack( 'n', 0 );
	}

	$count = count( $glyphs ) + 1;
	$ends .= pack( 'n', 0xFFFF );
	$starts .= pack( 'n', 0xFFFF );
	$deltas .= pack( 'n', 1 );
	$ranges .= pack( 'n', 0 );
	$format4 = pack( 'nnnnnnn', 4, 16 + 8 * $count, 0, 2 * $count, 0, 0, 0 ). $ends. pack( 'n', 0 ). $starts. $deltas. $ranges;
	$cmap = pack( 'nn', 0, 1 ). pack( 'nnN', $o['cmapplatform'], 1, 12 ). $format4;

	if( $o['cmapcut'] === true )
		$cmap = substr( $cmap, 0, 40 );

	$head = pack( 'NNNN', 0x00010000, 0x00010000, 0, $o['headmagic'] === 1 ? 0x12345678 : 0x5F0F3CF5 ). pack( 'nn', 0, $o['em'] ). str_repeat( "\0", 16 )
		. pack( 'nnnn', (int) ( -203 * $scale ) & 0xFFFF, (int) ( -224 * $scale ) & 0xFFFF, (int) ( 1619 * $scale ), (int) ( 1026 * $scale ) ). pack( 'nnnnnnn', 0, 8, 2, 0, 0, 0, 0 );
	$hhea = pack( 'Nnnnn', 0x00010000, $o['asc'] & 0xFFFF, $o['desc'] & 0xFFFF, 0, 2000 ). str_repeat( "\0", 20 ). pack( 'nn', 0, $count );
	$os2 = pack( 'nnnnn', 2, 600, $o['weight'], 5, $o['fstype'] ). str_repeat( "\0", 20 ). pack( 'n', $o['class'] ). str_repeat( "\0", 30 ). pack( 'n', 0x40 ). str_repeat( "\0", 22 )
		. pack( 'nn', (int) ( 484 * $scale ), (int) ( 716 * $scale ) ). str_repeat( "\0", 6 );
	$post = pack( 'NN', 0x00030000, ( (int) ( $o['angle'] * 65536 ) ) & 0xFFFFFFFF ). str_repeat( "\0", 24 );
	$tables = [ 'OS/2' => $os2, 'cmap' => $cmap, 'glyf' => str_repeat( "\0", 4 ), 'head' => $head, 'hhea' => $hhea, 'hmtx' => $hmtx, 'loca' => str_repeat( "\0", 2 * ( $count + 1 ) ), 'post' => $post ];
	unset( $tables[ $o['drop'] ] );
	$directory = '';
	$body = '';
	$offset = 12 + 16 * count( $tables );

	foreach( $tables as $tag => $bytes ) {

		$directory .= $tag. ( $tag === 'OS/2' ? '' : '' ). pack( 'NNN', 0, $offset + strlen( $body ), strlen( $bytes ) );
		$body .= $bytes. str_repeat( "\0", ( 4 - strlen( $bytes ) % 4 ) % 4 );
	}

	return $o['magic']. pack( 'nnnn', count( $tables ), 0, 0, 0 ). $directory. $body;
}
