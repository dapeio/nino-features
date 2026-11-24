<?php
declare(strict_types=1);

// The fixtures of the byte comparison: what the suite of the source made of them on Nino 1.12 is in fixtures/pdf-reference.json, as the SHA-256 and the
// length of each PDF. A project is handed in - the feature is on, the tags are set - and the bytes of every fixture come back by name. Headings, lists,
// tables across pages, rules, links, a header and a footer with page numbers and a logo, signature fields with a picture of the layout, a handed in PNG
// with alpha, a JPEG and a progressive one, a long text with an appended part and tokens, the preview, a sheet of the design that names every selector,
// and an owner's font of two styles. It needs pdfFontFixture() of pdf-smoke.php

use Nino\Modules\Pdf as Pdf;
use Nino\Modules\Pdf\Store;

return static function( array &$appData ): array {

	$picture = static function( string $kind, int $width = 40, int $height = 20, bool $alpha = false, bool $progressive = false ): string {

		$image = imagecreatetruecolor( $width, $height );
		imagealphablending( $image, false );
		imagesavealpha( $image, true );
		imagefill( $image, 0, 0, (int) imagecolorallocatealpha( $image, 30, 90, 200, $alpha === true ? 64 : 0 ) );
		imageinterlace( $image, $progressive );
		ob_start();
		$kind === 'png' ? imagepng( $image ) : imagejpeg( $image, null, 80 );

		return (string) ob_get_clean();
	};

	$dir = ninoSandboxDir( $appData );
	$out = [];
	$emit = static function( string $name, ?string $pdf ) use ( &$out ): void {
		$out[$name] = $pdf;
	};
	$layout = static fn( array $change ): array => Store::saveLayout( $appData, $change + [ 'format' => 'a4', 'margins' => [ 20, 22, 18, 24 ], 'font' => 'helvetica', 'size' => 10, 'header' => '', 'footer' => '' ] );
	$say = static function( array $answer ): void { if( isset( $answer['fail'] ) === true ) throw new \RuntimeException( 'refused: '. json_encode( $answer['fail'] ) ); };

	// The fixture of the suite: headings, lists, tables, rules, links, a header and a footer, a signature and an appended part, in three layouts
	$say( $layout( [ 'header' => '<table border="0"><tr><td><h3>Fixture AG</h3></td><td align="right">[[page]] / [[pages]]</td></tr></table><hr>', 'footer' => '<p align="center">Fixture footer <a href="https://example.com">link</a> [[page]]</p>' ] ) );
	$say( Store::saveBlock( $appData, [ 'key' => 'fx-text', 'isNew' => true, 'title' => 'Fixture text', 'html' => '<h1>Heading one</h1><p>A paragraph with <strong>bold</strong>, <em>italic</em> and <a href="https://example.com">a link</a>. '. str_repeat( 'Lorem ipsum dolor sit amet. ', 40 ). '</p><h2>Heading two</h2><ul><li>First item</li><li>Second item<ul><li>Nested item</li></ul></li></ul><h3>Heading three</h3><ol><li>One</li><li>Two</li></ol><hr><p align="right">Right</p><p style="text-align:center">Centre</p><table><thead><tr><th width="60%">Item</th><th>Price</th></tr></thead><tbody><tr><td>Cell one</td><td>10,00</td></tr><tr><td>Cell two</td><td>20,00</td></tr></tbody></table><table border="0"><tr><td>No border</td><td>x</td></tr></table>' ] ) );
	$say( Store::saveTemplate( $appData, [ 'key' => 'fx-one', 'isNew' => true, 'name' => 'Fixture one', 'tag' => 'offer', 'blocks' => [
		[ 'type' => 'html', 'block' => 'fx-text' ], [ 'type' => 'html', 'block' => 'fx-text' ], [ 'type' => 'input', 'name' => 'note', 'label' => 'Note', 'multiline' => true, 'required' => false ], [ 'type' => 'pagebreak' ],
		[ 'type' => 'html', 'html' => '<p>[[name]] from [[/nowhere/text]]</p>' ], [ 'type' => 'slot', 'name' => 'rows' ],
		[ 'type' => 'signature', 'name' => 'seller', 'party' => 'owner', 'label' => 'The seller', 'page' => 'last', 'x' => 20, 'y' => 230, 'w' => 60, 'h' => 20, 'line' => true ],
	] ] ) );
	$data = [ 'tokens' => [ 'name' => 'Ada' ], 'slots' => [ 'rows' => '<table><tr><th>A</th><th>B</th></tr><tr><td>1</td><td>2</td></tr></table>' ], 'inputs' => [ 'note' => "one\ntwo" ] ];
	$emit( 'fixture-A', Pdf::render( $appData, 'fx-one', $data, [ 'title' => 'Fixture', 'date' => '2026-01-02', 'append' => '<h2>Appended</h2><p>Terms of the appendix.</p>' ] ) );
	$say( $layout( [ 'format' => 'letter', 'margins' => [ 15, 16, 17, 18 ], 'font' => 'times', 'size' => 12, 'footer' => '<p>[[page]]</p>' ] ) );
	$emit( 'fixture-B', Pdf::render( $appData, 'fx-one', $data ) );
	$say( $layout( [ 'margins' => [ 20, 20, 20, 20 ], 'font' => 'courier', 'size' => 9 ] ) );
	$emit( 'fixture-C', Pdf::render( $appData, 'fx-one', $data ) );

	// Signatures: a picture with alpha handed in, the owner's signature of the layout, a logo in the header, a JPEG, a progressive JPEG, a path
	$say( $layout( [ 'header' => '<p><img src="pdf:logo" width="20"> Head [[page]]</p>', 'footer' => '<p>[[page]] / [[pages]]</p>' ] ) );
	$png = $picture( 'png', 60, 20, true );
	$say( Store::saveImage( $appData, 'logo', $picture( 'png', 80, 40 ) ) );
	$say( Store::saveImage( $appData, 'signature', $png ) );
	$say( Store::saveTemplate( $appData, [ 'key' => 'seals', 'isNew' => true, 'name' => 'Seals', 'tag' => 'contract', 'blocks' => [
		[ 'type' => 'html', 'html' => '<p>Text of the contract. [[/project/company/general/name]]</p>' ],
		[ 'type' => 'signature', 'name' => 'seller', 'party' => 'owner', 'label' => 'The seller', 'page' => 'last', 'x' => 20, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => true ],
		[ 'type' => 'signature', 'name' => 'buyer', 'party' => 'member', 'label' => 'The buyer', 'page' => 1, 'x' => 110.5, 'y' => 200, 'w' => 60, 'h' => 20, 'line' => false ],
		[ 'type' => 'signature', 'name' => 'third', 'party' => 'member', 'label' => '', 'page' => 'last', 'x' => 20, 'y' => 240, 'w' => 30, 'h' => 30, 'line' => false ],
	] ] ) );
	file_put_contents( $dir. '/sig.png', $png );
	$emit( 'seals-owner', Pdf::render( $appData, 'seals' ) );
	$emit( 'seals-given', Pdf::render( $appData, 'seals', [], [ 'signatures' => [ 'buyer' => $png, 'third' => $picture( 'jpeg', 50, 30 ) ] ] ) );
	$emit( 'seals-progressive', Pdf::render( $appData, 'seals', [], [ 'signatures' => [ 'buyer' => $picture( 'jpeg', 50, 30, false, true ), 'seller' => $dir. '/sig.png' ] ] ) );

	// A long text, a table across pages, a slot with HTML that is cleaned, the headings and lists of the geometry case
	$say( $layout( [ 'header' => '<p>[[/project/company/general/name]] / [[page]]</p>', 'footer' => '<p>[[/project/company/general/pages]]</p>' ] ) );
	$rows = '';
	for( $row = 1; $row <= 50; $row++ )
		$rows .= '<tr><td>Row '. $row. '</td><td>Value</td></tr>';
	$say( Store::saveTemplate( $appData, [ 'key' => 'table', 'isNew' => true, 'name' => 'Table', 'tag' => 'information', 'blocks' => [ [ 'type' => 'slot', 'name' => 'rows' ] ] ] ) );
	$emit( 'table-pages', Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<table><thead><tr><th>Head A</th><th>Head B</th></tr></thead><tbody>'. $rows. '</tbody></table>' ] ] ) );
	$html = '<h1>One</h1><h2>Two</h2><h3>Three</h3><p>Plain</p><ul><li>Item</li></ul><ol><li>First</li></ol><p align="right">Right</p><p style="text-align:center">Centre</p><p><b>Bold</b> <i>Italic</i> <b><i>Both</i></b> <a href="#">Link</a></p><table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table><p>one<br>two<br><br>three</p><p>Seen</p><script>hidden()</script><style>.quiet{}</style><p onclick="alert(1)">Before <font color="red">Unwrapped</font> <u>Under</u> <a href="javascript:alert(1)">Link</a></p><section>Section</section><p style="font-size:14pt">Big <span style="font-size:70%">and small</span></p><table><tr style="background:#fff3cd"><td style="background:#d4edda">x</td></tr></table><p class="address" style="margin: 10mm 0 0 20mm">Street 1</p>';
	$emit( 'geometry', Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $html ] ] ) );
	$say( Store::saveBlock( $appData, [ 'key' => 'long', 'isNew' => true, 'title' => 'Long', 'html' => '<p>'. str_repeat( 'word ', 1500 ). '</p>' ] ) );
	$say( Store::saveTemplate( $appData, [ 'key' => 'long', 'isNew' => true, 'name' => 'Long', 'tag' => 'information', 'blocks' => [ [ 'type' => 'html', 'block' => 'long' ], [ 'type' => 'pagebreak' ], [ 'type' => 'html', 'html' => '<p>[[c]] [[a.b]]</p>' ] ] ] ) );
	$emit( 'long', Pdf::render( $appData, 'long', [ 'tokens' => [ 'c' => 'X[[pages]]Y [[page]]', 'a' => [ 'b' => 'Köln' ] ] ], [ 'append' => '<p>'. str_repeat( 'appended ', 900 ). '</p>', 'title' => 'Lång', 'date' => '2026-02-03' ] ) );
	$emit( 'preview', Pdf::compose( $appData, Pdf::template( $appData, 'seals' ), [], [ 'title' => 'x' ], true ) );
	$emit( 'preview-empty', Pdf::compose( $appData, [ 'name' => 'Design', 'tag' => '', 'blocks' => [] ], [], [ 'title' => 'Design' ], true ) );

	// The design: a sheet that names every selector and property, merged over the feature's
	$design = <<<'CSS'
	body { font-family: Courier; font-size: 11pt; font-weight: normal; font-style: italic; color: #112233; line-height: 1.5 }
	h1, h2 { font-size: 1.5em; font-weight: bold; margin: 5mm 0 2mm 0; text-align: center; color: #1f5f8b }
	p { margin: 1mm 0; color: #AABBCC }
	ul { margin: 0 0 3mm 8mm }
	li { margin: 0 0 2mm 0; text-align: right }
	th { padding: 1mm 2mm 3mm 4mm; background: #eeeeee; border-bottom: 0.5pt solid #000000; border-top: 0 }
	td { padding: 2mm; border-bottom: 0 }
	tr:nth-child(even) td { background: #f3f5f7 }
	hr { border-top: 1pt solid #999999; margin: 3mm 0 }
	a { text-decoration: none; color: #0000ff }
	.address { margin: 10mm 0 12mm 0; color: #ff0000 }
	.header { font-size: 8pt; border-bottom: 0.3pt solid #cccccc; margin: 0 0 6mm 0; text-align: right }
	.footer { border-top: 0.3pt solid #cccccc; text-align: center }
	.signature { font-size: 0.7em; border-top: 0.3pt solid #1f5f8b; margin: 2mm 0 0 0; color: #00aa00 }
	CSS;
	$say( $layout( [ 'header' => '<p>Head [[page]]</p>', 'footer' => '<p>Foot</p>', 'design' => $design, 'font' => 'times', 'size' => 12 ] ) );
	$emit( 'design-seals', Pdf::render( $appData, 'seals', [], [ 'signatures' => [ 'buyer' => $png ] ] ) );
	$emit( 'design-geometry', Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => $html. '<table><thead><tr><th>H</th></tr></thead><tbody><tr><td>1</td></tr><tr><td>2</td></tr><tr><td>3</td></tr></tbody></table>' ] ] ) );
	$say( $layout( [ 'design' => '' ] ) );

	// An owner's font: the fixture TrueType files of the suite, in the folder of the owner
	$fonts = \Nino\Filesystem::path( $appData, \Nino\Modules\Pdf\Fonts\Metrics::DIR );
	mkdir( $fonts, 0775, true );
	file_put_contents( $fonts. '/Fixture-Regular.ttf', pdfFontFixture() );
	file_put_contents( $fonts. '/Fixture-Bold.ttf', pdfFontFixture( [ 'weight' => 700 ] ) );
	$say( $layout( [ 'font' => 'fixture', 'size' => 10 ] ) );
	$emit( 'font-fixture', Pdf::render( $appData, 'table', [ 'slots' => [ 'rows' => '<p>First line<br>Second line</p><p>Ä ö ü ß € <b>Bold words</b> <i>Slanted words</i></p>' ] ], [ 'date' => '2026-01-02' ] ) );

	return $out;
};
