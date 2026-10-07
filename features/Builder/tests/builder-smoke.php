<?php
declare(strict_types=1);

/**
 *	Nino
 *	builder-smoke.php		The Builder's server half over Nino's harness: the manifest
 *											and the panel, then the Reader and the Writer on the
 *											example page of the concept (tests/fixtures/page-home.tpl,
 *											the example in its canonical form: the concept's own,
 *											page-home-concept.tpl, says level="2" twice and gap="2"
 *											aloud, which are the defaults the Writer leaves out) and
 *											on a sheet of sections that each fail one rule, then
 *											the Document - listing, loading, saving with a hash, the
 *											keys and the slots a save makes, the refusals - and last
 *											the page the builder wrote, rendered by the kernel. The panel's
 *											script has a test of its own, builder-js-smoke.js, which runs
 *											at the end where node is there; the two fixtures it reads
 *											(fixtures/page-home.json, fixtures/registry.json) are held
 *											here to what the Reader and the registry say.
 *
 *											The Reader and the Writer are pure, so sections 2 to 5 need
 *											no project but the registry of the Components module; the
 *											Document is driven against a sandbox with the keys, the
 *											slots and an element type the example page reads.
 *
 *	Usage: php features/Builder/tests/builder-smoke.php
 *	       NINO_ROOT=../nino php features/Builder/tests/builder-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'builder' );
$appData['/nino/dir'] = '';
$appData['./nino/locales/current'] = 'en_US';
// The workbench speaks the site's language where nobody chose another
$appData['/nino/locales/native'] = 'en_US';

$dir = dirname( __DIR__ );


// --- 1. The manifest and the panel -------------------------------------------

echo "The manifest\n";

$manifest = \Nino\Features::manifest( $dir );

check( 'it validates, key "builder"', is_array( $manifest ) === true && $manifest['key'] === 'builder' && ninoWarnings() === [] );
check( 'it is written for the kernel that has the Components module', is_array( $manifest ) === true && $manifest['nino'] === '^1.6' && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it is a feature for content, with nothing required, no settings and no data of its own', $manifest['category'] === 'content' && $manifest['requires'] === [] && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'it names and describes itself in both interface languages', \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'its manual says what it adds: the panel, and no shortcode, route or callback', $manifest['manual']['shortcodes'] === [] && $manifest['manual']['routes'] === [] && $manifest['manual']['callbacks'] === [] && isset( $manifest['manual']['panel']['Builder'] ) === true );

check( 'the feature brings its panel along, and registers nothing on the site', \Nino\Modules\Builder::adminPanels( $appData ) === [ \Nino\Modules\Builder\Admin::class ] && ( static function() use ( &$appData ): bool {
	$before = $appData;
	\Nino\Modules\Builder::init( $appData );
	return $before === $appData;
} )() );

check( 'the panel names every action it answers', array_keys( \Nino\Modules\Builder\Admin::actions() ) === [ 'builder/list', 'builder/create', 'builder/load', 'builder/save', 'builder/delete', 'builder/source', 'builder/registry' ] );
check( '...asks for its own permission, asks the nav for the structure group (a feature\'s panel lands in the features group whatever it names) and is a workspace', \Nino\Modules\Builder\Admin::perm() === '/_admin/builder/manage'
	&& \Nino\Modules\Builder\Admin::nav()[3] === 'structure' && \Nino\Modules\Builder\Admin::layout() === 'workspace' );
check( '...and brings the HTML editor of the workbench, its own script and its stylesheet, which are there', count( \Nino\Modules\Builder\Admin::assets() ) === 3 && in_array( '/_admin/assets/html-editor.js', \Nino\Modules\Builder\Admin::assets(), true ) === true
	&& array_filter( [ 'assets/admin.js', 'assets/admin.css', 'templates/panel.tpl', 'text/en_US.php', 'text/de_DE.php' ], static fn( string $file ): bool => is_file( dirname( __DIR__ ). '/'. $file ) === false ) === [] );
check( '...whose words are the same keys in both languages', array_keys( (array) include $dir. '/text/en_US.php' ) === array_keys( (array) include $dir. '/text/de_DE.php' ) );
check( 'a save, a create and a delete are written to the activity log, a read is not', \Nino\Modules\Builder\Admin::log( 'builder/save', [ 'file' => 'page-home' ] ) !== ''
	&& \Nino\Modules\Builder\Admin::log( 'builder/create', [] ) !== '' && \Nino\Modules\Builder\Admin::log( 'builder/delete', [] ) !== ''
	&& \Nino\Modules\Builder\Admin::log( 'builder/load', [] ) === '' && \Nino\Modules\Builder\Admin::log( 'builder/list', [] ) === '' && \Nino\Modules\Builder\Admin::log( 'builder/source', [] ) === '' );

echo "\n";


// --- 2. The registry the Reader and the Writer take --------------------------

echo "The registry of the Components module\n";

\Nino\Modules\Components::init( $appData );
$registry = \Nino\Modules\Builder\Document::schemas( $appData );

check( 'the components and the stacks of the kernel are in it, each with the defaults of its attributes', array_keys( $registry['components'] ) === [ 'title', 'subtitle', 'text', 'image', 'button', 'html', 'spacer' ]
	&& array_keys( $registry['stacks'] ) === [ 'stack', 'slider', 'filter', 'list' ]
	&& $registry['components']['title']['defaults'] === [ 'level' => '2', 'style' => '', 'class' => '' ]
	&& $registry['stacks']['stack']['defaults']['gap'] === '2' );

$page = static function( string ...$blocks ) use ( &$page ): string {
	return implode( "\n\n", $blocks ). "\n";
};

// A section with one row and one column, around whatever the column holds
$section = static function( string $calls, string $id = 'a', string $class = 'nino-section', string $col = 'nino-grid-100' ): string {
	return '<section id="'. $id. '" class="'. $class. "\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"". $col. "\">\n\t\t\t". $calls. "\n\t\t</div>\n\t</div>\n</section>";
};

$read		= static fn( string $source ): array => \Nino\Modules\Builder\Reader::read( $source, $registry );
$write	= static fn( array $model ): string => \Nino\Modules\Builder\Writer::write( $model, $registry );

echo "\n";


// --- 3. The example page: read, write, read again ----------------------------

echo "The example page of the concept\n";

$fixture	= (string) file_get_contents( __DIR__. '/fixtures/page-home.tpl' );
$model		= $read( $fixture );

check( 'the head: its name, its header and its footer', $model['name'] === 'Home' && $model['header'] === 'html-header' && $model['footer'] === 'html-footer' );
check( 'four blocks in the order of the file: a section, a section, a block of html, a section', array_column( $model['blocks'], 'kind' ) === [ 'section', 'section', 'html', 'section' ]
	&& [ $model['blocks'][0]['id'], $model['blocks'][1]['id'], $model['blocks'][3]['id'] ] === [ 'hero', 'services', 'contact' ] );

$hero = $model['blocks'][0];
check( 'the hero: its classes are settings', $hero['settings']['width'] === 'fullwidth' && $hero['settings']['color'] === 'black' && $hero['settings']['image'] === 'cover'
	&& $hero['settings']['dim'] === true && $hero['settings']['cover'] === 100 && $hero['settings']['vpa'] === '' && $hero['settings']['row'] === 'wide' && $hero['settings']['rowAlign'] === 'middle'
	&& $hero['settings']['custom'] === '' );
check( '...its background is a slot with a focus', $hero['background'] === [ 'slot' => '/template/page-home/hero/background', 'focus' => 5 ] );
check( '...its column is 100, 100 and 66 wide, aligned left, and holds three components', $hero['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 66 ] && $hero['cols'][0]['text'] === 'left'
	&& $hero['cols'][0]['stack'] === null && array_column( $hero['cols'][0]['components'], 'name' ) === [ 'title', 'subtitle', 'button' ] );
check( '...a call carries its source and its attributes, every one of the schema, the default where the call is silent',
	$hero['cols'][0]['components'][0] === [ 'name' => 'title', 'source' => '/template/page-home/hero/title', 'text' => null, 'attributes' => [ 'level' => '1', 'style' => 'loud', 'class' => '' ] ] );

$services = $model['blocks'][1];
check( 'the services: a column with a stack names its type, its loop and its cells', $services['cols'][1]['stack']['name'] === 'stack' && $services['cols'][1]['stack']['source'] === '/services'
	&& $services['cols'][1]['stack']['attributes']['sort'] === 'title' && $services['cols'][1]['stack']['attributes']['limit'] === '6'
	&& $services['cols'][1]['stack']['attributes']['cols'] === '100 50 50' && $services['cols'][1]['stack']['attributes']['autoheight'] === '1'
	&& $services['cols'][1]['stack']['attributes']['gap'] === '2' );
check( '...its components take a field of the type as their source, and a fixed text beside it',
	array_column( $services['cols'][1]['components'], 'source' ) === [ 'image', 'title', 'summary', '.uri' ] && $services['cols'][1]['components'][3]['text'] === 'Mehr' );

check( 'the block of html in its markers is a block of html without a reason, byte for byte',
	$model['blocks'][2]['reason'] === null && str_starts_with( $model['blocks'][2]['source'], '<section id="map"' ) && str_ends_with( $model['blocks'][2]['source'], '</section>' ) );
check( 'the contact: 100 wide in every viewport is the short class', $model['blocks'][3]['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 100 ] && $model['blocks'][3]['settings']['text'] === 'center' && $model['blocks'][3]['settings']['row'] === 'narrow' );

check( 'the model the panel\'s script test reads (fixtures/page-home.json) is what the Reader makes of the example page', json_decode( (string) file_get_contents( __DIR__. '/fixtures/page-home.json' ), true ) === array_replace( $model, [ 'file' => 'page-home' ] ) );
check( 'writing the model of the example page gives the example page, byte for byte', $write( $model ) === $fixture );
check( '...and reading what was written gives the same model', $read( $write( $model ) ) === $model );
check( 'a load and a save without a change is the same file: Reader( Writer( Reader( x ) ) ) is Reader( x )', $read( $write( $read( $fixture ) ) ) === $read( $fixture ) );

// A page the builder did not write the way it would: the order of the attributes, a default said aloud, the short class, an empty alt
$hand = str_replace(
	[ '[title /template/page-home/hero/title level="1" style="loud"]', '[title /template/page-home/contact/title]', "\t\t<div class=\"nino-grid-100\">\n\t\t\t[title" ],
	[ '[title /template/page-home/hero/title style="loud" class="" level="1"]', '[title /template/page-home/contact/title level="2" style=""]', "\t\t<div class=\"nino-grid-s-100 nino-grid-m-100 nino-grid-l-100\">\n\t\t\t[title" ],
	$fixture
);
check( 'a page written by hand is read all the same, and written back in the canonical form', $hand !== $fixture && $read( $hand ) === $model && $write( $read( $hand ) ) === $fixture );

$concept = (string) file_get_contents( __DIR__. '/fixtures/page-home-concept.tpl' );
check( 'the example as the concept prints it, with the three defaults said aloud, reads as the same model and is written in the canonical form', $concept !== $fixture && $read( $concept ) === $model && $write( $read( $concept ) ) === $fixture );

check( 'a file with no head and no frame is read, and written as it is: nothing is added', $write( $read( $section( '[title /template/page-x/a/title]' )."\n" ) ) === $section( '[title /template/page-x/a/title]' )."\n" );
check( 'an empty file is a page of no blocks, written as an empty file', $read( '' )['blocks'] === [] && $write( $read( '' ) ) === '' );
check( 'a frame the Document would refuse on a save - html-headerx, html-footer- - is no frame to the Reader either: the line stays a block of the page', $read( "[template /templates/html-headerx]\n\n". $section( '[title /template/page-x/a/title]' ). "\n" )['header'] === ''
	&& $read( $section( '[title /template/page-x/a/title]' ). "\n\n[template /templates/html-footer-]\n" )['footer'] === '' );
check( 'a page of a name and two frames and nothing in between', $write( [ 'name' => 'Empty', 'header' => 'html-header', 'footer' => 'html-footer', 'blocks' => [] ] ) === "<!-- nino:template-name Empty -->\n[template /templates/html-header]\n\n[template /templates/html-footer]\n" );

echo "\n";


// --- 4. What the Reader leaves as a block of html, and why -------------------

echo "A section that is not read is a foreign block, with its line and its reason\n";

$row = "<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>";

$failures = [
	// 1.9: the examples of the concept
	'a second row'												=> [ "<section id=\"a\" class=\"nino-section\">\n\t". $row. "\n\t". $row. "\n</section>", 'second-row' ],
	'a column without width classes'			=> [ $section( '[title /template/page-x/a/title]', 'a', 'nino-section', 'nino-text-left' ), 'col-width' ],
	'a column with a width for one viewport only' => [ $section( '[title /template/page-x/a/title]', 'a', 'nino-section', 'nino-grid-l-50' ), 'col-width' ],
	'markup next to a stack'							=> [ $section( "<h2>Our work</h2>\n\t\t\t[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]" ), 'col-markup' ],
	'a component next to a stack'					=> [ $section( "[title /template/page-x/a/title]\n\t\t\t[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]" ), 'stack-neighbour' ],
	'a stack after a component'						=> [ $section( "[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]\n\t\t\t[title /template/page-x/a/title]" ), 'stack-neighbour' ],
	'markup inside a stack'								=> [ $section( "[stack /services]\n\t\t\t\t<p>Hi</p>\n\t\t\t[/stack]" ), 'stack-content' ],
	'a stack in a stack'									=> [ $section( "[stack /services]\n\t\t\t\t[stack /services]\n\t\t\t\t\t[title title]\n\t\t\t\t[/stack]\n\t\t\t[/stack]" ), 'stack-in-stack' ],
	'a stack without a type'							=> [ $section( "[stack]\n\t\t\t\t[title title]\n\t\t\t[/stack]" ), 'stack-source' ],
	'a shortcode that is no component'		=> [ $section( '[osm lat="48.1" lon="11.5"]' ), 'unknown-shortcode', null, 'osm' ],
	'a fill where a call should be'				=> [ $section( '[[/template/page-x/a/title]]' ), 'col-markup' ],
	'a call inside the content of a call'	=> [ $section( '[html]<p>[button /template/page-x/a/button]</p>[/html]' ), 'nested-call', null, 'html' ],
	'content on a call that takes none'		=> [ $section( '[title /template/page-x/a/title]text[/title]' ), 'component-content', null, 'title' ],
	// What the section is made of
	'markup in a column'									=> [ $section( '<p>Hello</p>' ), 'col-markup' ],
	'a section without a row'							=> [ "<section id=\"a\" class=\"nino-section\">\n\t<p>Hello</p>\n</section>", 'no-row' ],
	'a row without columns'								=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t</div>\n</section>", 'no-cols' ],
	'something in a row that is no column'	=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<p>Hello</p>\n\t</div>\n</section>", 'row-content' ],
	'a row in a row'											=> [ $section( '[title /template/page-x/a/title]', 'a', 'nino-section', 'nino-grid-row nino-grid-100' ), 'nested-row' ],
	'markup next to the row'							=> [ "<section id=\"a\" class=\"nino-section\">\n\t". $row. "\n\t<p>After</p>\n</section>", 'section-content' ],
	'a section that is no nino-section'		=> [ $section( '[title /template/page-x/a/title]', 'a', 'something-else' ), 'section-class' ],
	'an id that is no slug'								=> [ $section( '[title /template/page-x/a/title]', 'Big Hero' ), 'section-id' ],
	'an attribute the builder does not keep' => [ "<section id=\"a\" class=\"nino-section\" style=\"color: red\">\n\t". $row. "\n</section>", 'section-attribute', null, 'style' ],
	'attributes that are not name="value"'	=> [ "<section id=a class=\"nino-section\">\n\t". $row. "\n</section>", 'attribute-syntax' ],
	'a class with characters it cannot keep' => [ $section( '[title /template/page-x/a/title]', 'a', 'nino-section a&b' ), 'class-token', null, 'a&b' ],
	'a cover height that is no number'		=> [ "<section id=\"a\" class=\"nino-section nino-cover\" data-cover-height=\"tall\">\n\t". $row. "\n</section>", 'attribute-value', null, 'data-cover-height' ],
	'a background of two pictures'				=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-x/a/one alt=\"\"][image /template/page-x/a/two alt=\"\"]</div>\n\t". $row. "\n</section>", 'background-image' ],
	'a background that has a class of its own' => [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-section-bg my-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t". $row. "\n</section>", 'background-class', null, 'my-bg' ],
	// What a call is made of
	'a value outside its options'					=> [ $section( '[title /template/page-x/a/title level="9"]' ), 'attribute-value', null, 'level' ],
	'an attribute the component has not'	=> [ $section( '[title /template/page-x/a/title colour="red"]' ), 'unknown-attribute', null, 'colour' ],
	'two spaces between the arguments'		=> [ $section( '[title /template/page-x/a/title  level="1"]' ), 'call-arguments' ],
	'a second source'											=> [ $section( '[title /template/page-x/a/title /template/page-x/a/other]' ), 'call-arguments' ],
	'a quote of the other kind'						=> [ $section( '[button /template/page-x/a/button href=\'/x\']' ), 'call-arguments' ],
];

$failed = [];

foreach( $failures as $label => $case ) {

	$given		= $case[0];
	$expected	= $case[1];
	$source		= $case[2] ?? $given;
	$detail		= $case[3] ?? null;
	$blocks		= $read( $page( $given ) )['blocks'];

	check( $label. ': the whole section is one foreign block - '. $expected,
		count( $blocks ) === 1 && $blocks[0]['kind'] === 'html' && is_array( $blocks[0]['reason'] ) === true && $blocks[0]['reason']['code'] === $expected
		&& $blocks[0]['source'] === $source && $blocks[0]['reason']['line'] >= 1
		&& ( $detail === null || str_contains( $blocks[0]['reason']['detail'], $detail ) === true ) && $blocks[0]['reason']['text'] !== '' );
}

// The line is the line of the file, where the reading failed
$three = $read( $page( $section( '[title /template/page-x/a/title]', 'good' ), $section( '[osm lat="1"]', 'bad' ) ) );
check( 'the reason names the line of the failure: the call on the twelfth line of a block that starts on the ninth',
	array_column( $three['blocks'], 'kind' ) === [ 'section', 'html' ] && $three['blocks'][1]['reason']['line'] === 12 );
check( '...and the sentence says what the kernel knows no component by', str_contains( $three['blocks'][1]['reason']['text'], 'osm' ) === true );

check( 'a section the reader does read, after one it does not: the foreign block ends where the next section begins',
	( static function() use ( $read, $page, $section ): bool {
		$blocks = $read( $page( $section( '[osm]', 'one' ), $section( '[title /template/page-x/a/title]', 'two' ), $section( '<p>x</p>', 'three' ), $section( '[title /template/page-x/a/title]', 'four' ) ) )['blocks'];
		return array_column( $blocks, 'kind' ) === [ 'html', 'section', 'html', 'section' ] && $blocks[1]['id'] === 'two' && $blocks[3]['id'] === 'four';
	} )() );
check( 'an id that is the id of an earlier section is the second one failing', ( static function() use ( $read, $page, $section ): bool {
	$blocks = $read( $page( $section( '[title /template/page-x/a/title]' ), $section( '[title /template/page-x/a/title]' ) ) )['blocks'];
	return array_column( $blocks, 'kind' ) === [ 'section', 'html' ] && $blocks[1]['reason']['code'] === 'duplicate-id';
} )() );

// Nino.css is mobile-first: nino-grid-100 holds in every viewport, a prefixed width from its viewport up
$column = static fn( string $class ): array => $read( $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section', $class ) ) )['blocks'][0];

check( 'the most common idiom of the library, nino-grid-100 nino-grid-m-50, is 100 on a phone and 50 from a tablet up - not 100 on a desktop', $column( 'nino-grid-100 nino-grid-m-50' )['cols'][0]['width'] === [ 's' => 100, 'm' => 50, 'l' => 50 ]
	&& $column( 'nino-grid-m-50 nino-grid-100' )['cols'][0]['width'] === [ 's' => 100, 'm' => 50, 'l' => 50 ] );
check( '...a larger viewport that names a width takes over from there: nino-grid-100 nino-grid-l-50 is 100, 100 and 50, in either order',
	$column( 'nino-grid-l-50 nino-grid-100' )['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 50 ] && $column( 'nino-grid-s-33 nino-grid-l-66' )['cols'][0]['width'] === [ 's' => 33, 'm' => 33, 'l' => 66 ] );
check( '...and the column is written with its three widths and read again as the same column, so that nothing is laid out differently', ( static function() use ( $column, $read, $write ): bool {
	$block = $column( 'nino-grid-100 nino-grid-m-50' );
	$text = $write( [ 'blocks' => [ $block ] ] );
	return str_contains( $text, 'nino-grid-s-100 nino-grid-m-50 nino-grid-l-50' ) === true && $read( $text )['blocks'][0] === $block;
} )() );

$twice = $column( 'nino-grid-s-50 nino-grid-s-100' )['cols'][0];
check( 'a second width for the same viewport is not lost: the first one is the width, the other stays a class of the column', $twice['width'] === [ 's' => 50, 'm' => 50, 'l' => 50 ] && $twice['custom'] === 'nino-grid-s-100'
	&& $read( $write( [ 'blocks' => [ $column( 'nino-grid-s-50 nino-grid-s-100' ) ] ] ) )['blocks'][0]['cols'][0] === $twice );
$blocks = $read( $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section', 'nino-grid-50' ) ) )['blocks'];
check( 'an unprefixed width other than 100 cannot be said in three viewports without changing it: the section is a foreign block', count( $blocks ) === 1 && $blocks[0]['reason']['code'] === 'col-width' );

$hand = $page(
	"<div class=\"hero\">\n  <h1>Hello</h1>\n  <p>Written by hand</p>\n</div>",
	$section( '[title /template/page-x/a/title]' ),
	"<!-- a note -->\n<footer>\n\t<p>Not a section either</p>\n</footer>"
);
$blocks = $read( $hand )['blocks'];
check( 'a block without markers that is no section stays: between sections, before and after, as it was written', array_column( $blocks, 'kind' ) === [ 'html', 'section', 'html' ]
	&& $blocks[0]['source'] === "<div class=\"hero\">\n  <h1>Hello</h1>\n  <p>Written by hand</p>\n</div>" && is_array( $blocks[0]['reason'] ) === true
	&& $blocks[2]['source'] === "<!-- a note -->\n<footer>\n\t<p>Not a section either</p>\n</footer>" );
check( '...and is written back byte for byte, without markers added', $write( $read( $hand ) ) === $hand && str_contains( $write( $read( $hand ) ), 'nino:html' ) === false );
check( '...and the whole file is read again as it was', $read( $write( $read( $hand ) ) ) === $read( $hand ) );

$indented = "\t<aside>\n\t\tIndented, by hand\n\t</aside>\n";
check( 'the indentation of the first line of a foreign block is part of it', $read( $indented )['blocks'][0]['source'] === "\t<aside>\n\t\tIndented, by hand\n\t</aside>" );

$unclosed = $read( "<!-- nino:html -->\n<p>No end</p>\n\n". $section( '[title /template/page-x/a/title]' ). "\n" );
check( 'a marker that is never closed is a foreign block with a reason, and the section after it is read', $unclosed['blocks'][0]['reason']['code'] === 'marker-unclosed' && $unclosed['blocks'][1]['kind'] === 'section' );

check( 'a section inside a block that failed is no block of its own', ( static function() use ( $read ): bool {
	$source = "<section id=\"outer\" class=\"nino-section\">\n<p>No row</p>\n<section id=\"inner\" class=\"nino-section\">\n<div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[title /x]</div></div>\n</section>\n</section>\n";
	$blocks = $read( $source )['blocks'];
	return count( $blocks ) === 1 && $blocks[0]['kind'] === 'html' && $blocks[0]['source'] === rtrim( $source );
} )() );

// What the builder wraps itself is read without analysis, and written with its markers
$marked = $page( "<!-- nino:html -->\n<p>Made by the builder</p>\n<!-- /nino:html -->" );
check( 'a block in its markers has no reason, whatever it holds, and keeps them when written', $read( $marked )['blocks'][0] === [ 'kind' => 'html', 'source' => '<p>Made by the builder</p>', 'reason' => null ] && $write( $read( $marked ) ) === $marked );
check( 'a block the panel made - a reason of null - is written with markers', $write( [ 'blocks' => [ [ 'kind' => 'html', 'source' => '<p>New</p>', 'reason' => null ] ] ] ) === "<!-- nino:html -->\n<p>New</p>\n<!-- /nino:html -->\n" );
check( 'a block that failed - a reason - is written without', $write( [ 'blocks' => [ [ 'kind' => 'html', 'source' => '<p>Old</p>', 'reason' => [ 'line' => 1, 'code' => 'not-a-section', 'detail' => '', 'text' => '' ] ] ] ] ) === "<p>Old</p>\n" );

echo "\n";


// --- 5. What the Writer writes -----------------------------------------------

echo "The canonical form\n";

$blank = $read( $page( $section( '[title /template/page-x/a/title]' ) ) )['blocks'][0];

$changed = $blank;
$changed['cols'][0]['components'] = [
	[ 'name' => 'title', 'source' => '/template/page-x/a/title', 'text' => null, 'attributes' => [ 'level' => '2', 'style' => '', 'class' => '' ] ],
	[ 'name' => 'title', 'source' => '/template/page-x/a/other', 'text' => null, 'attributes' => [ 'level' => '3', 'style' => 'quiet', 'class' => 'big' ] ],
	[ 'name' => 'button', 'source' => '', 'text' => 'Go', 'attributes' => [ 'href' => '#top' ] ],
	[ 'name' => 'html', 'source' => '', 'text' => null, 'attributes' => [], 'content' => '<p>Free</p>' ],
	[ 'name' => 'spacer', 'source' => '', 'text' => null, 'attributes' => [ 'size' => 2 ] ],
];
$written = $write( [ 'blocks' => [ $changed ] ] );

check( 'an attribute is written where it differs from the schema, and only there - not a default, not an empty one, an int or a bool as its string', str_contains( $written, "\t\t\t[title /template/page-x/a/title]\n" ) === true
	&& str_contains( $written, '[title /template/page-x/a/other level="3" style="quiet" class="big"]' ) === true
	&& str_contains( $written, '[button text="Go" href="#top"]' ) === true && str_contains( $written, '[spacer]' ) === true && str_contains( $written, 'size=' ) === false );
check( 'a component with a content is its call, the content and its closing call, on one line', str_contains( $written, "\t\t\t[html]<p>Free</p>[/html]\n" ) === true );
check( '...and what is written reads back as what was meant', ( static function() use ( $read, $written ): bool {
	$col = $read( $written )['blocks'][0]['cols'][0]['components'];
	return $col[1]['attributes']['level'] === '3' && $col[2]['text'] === 'Go' && $col[3]['content'] === '<p>Free</p>' && $col[4]['attributes']['size'] === '2';
} )() );

$styled = $blank;
$styled['settings'] = [
	'row' => 'narrow', 'rowAlign' => 'bottom', 'width' => 'fullheight', 'color' => 'tint', 'border' => 'primary', 'image' => 'parallax', 'dim' => true,
	'mt' => '1', 'mb' => '0', 'pt' => '3', 'pb' => '6', 'text' => 'right', 'vpa' => 'zoom-soft', 'vpaSpeed' => 'slow', 'vpaMode' => 'repeat', 'vpaDelay' => '200ms', 'vpaDuration' => '1.5s',
	'custom' => 'my-section other', 'rowCustom' => 'my-row',
] + $blank['settings'];
$styled['cols'][0] = [ 'width' => [ 's' => 100, 'm' => 50, 'l' => 33 ], 'hidden' => [ 's' => true ], 'text' => 'center', 'stackAlign' => 'end', 'stackGap' => '4', 'vpa' => '', 'custom' => 'my-col' ] + $blank['cols'][0];
$styled['background'] = [ 'slot' => '/template/page-x/a/background', 'focus' => null ];
$once = $write( [ 'blocks' => [ $styled ] ] );

check( 'a section\'s settings are its classes, in one order: the width, the colour, the border, the image, the spacing, the text, the animation, the custom ones',
	str_contains( $once, '<section id="a" class="nino-section nino-section--fullheight nino-section--tint nino-section--border-primary nino-parallex nino-parallex--dim nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-text-right nino-vpa nino-vpa--zoom-soft nino-vpa--speed-slow nino-vpa--repeat my-section other" data-vpa-delay="200ms" data-vpa-duration="1.5s">' ) === true );
check( '...and the row\'s and the column\'s', str_contains( $once, '<div class="nino-grid-row nino-grid-row--narrow nino-grid-bottom my-row">' ) === true
	&& str_contains( $once, '<div class="nino-grid-s-100 nino-grid-m-50 nino-grid-l-33 nino-hide-s nino-stack-end nino-stack-gap-4 nino-text-center nino-vpa my-col">' ) === true );
check( '...a background without a focus is the block and the image, with an empty alt', str_contains( $once, "\t<div class=\"nino-section-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n" ) === true );
$sorted = static function( mixed $value ) use ( &$sorted ): mixed {
	if( is_array( $value ) === false )
		return $value;
	ksort( $value );
	return array_map( $sorted, $value );
};
check( '...and every setting is read back as it was set', $sorted( $read( $once )['blocks'][0] ) === $sorted( $styled ) );

$custom = $read( $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-section--black my-own nino-section--alt nino-cover--dim', 'nino-grid-s-100 nino-grid-m-50 nino-grid-l-50 u-pad' ) ) )['blocks'][0];
check( 'a class the builder does not know is a custom one, and so are a second colour and a dim without an image - nothing is lost',
	$custom['settings']['color'] === 'black' && $custom['settings']['custom'] === 'my-own nino-section--alt nino-cover--dim' && $custom['cols'][0]['custom'] === 'u-pad' );
check( '...and the custom classes come last when it is written, and the same again when it is read', str_contains( $write( [ 'blocks' => [ $custom ] ] ), 'class="nino-section nino-section--black my-own nino-section--alt nino-cover--dim"' ) === true
	&& $read( $write( [ 'blocks' => [ $custom ] ] ) )['blocks'][0] === $custom );

$hostile = $blank;
$hostile['settings']['custom'] = 'a" onmouseover="alert(1)';
check( 'a custom class is escaped where it is written, so it cannot leave the attribute', str_contains( $write( [ 'blocks' => [ $hostile ] ] ), 'onmouseover="' ) === false && str_contains( $write( [ 'blocks' => [ $hostile ] ] ), '&quot;' ) === true );

$stacked = $read( $page( "<section id=\"s\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[slider /services width=\"60%\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]\n\t\t</div>\n\t</div>\n</section>" ) )['blocks'][0];
check( 'a stack other than [stack] is read and written as its own, with the attributes of its schema', $stacked['kind'] === 'section' && $stacked['cols'][0]['stack']['name'] === 'slider' && $stacked['cols'][0]['stack']['attributes']['width'] === '60%'
	&& str_contains( $write( [ 'blocks' => [ $stacked ] ] ), "[slider /services width=\"60%\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]" ) === true );

echo "\n";


// --- 6. The Document: a project with the example page ------------------------

echo "Document - the page templates of a project\n";

// What the example page reads: the texts, the slots, the type of the stack, the frames
$texts = [
	'/template/page-home/hero/title' => 'Hello', '/template/page-home/hero/subtitle' => 'Sub', '/template/page-home/services/title' => 'Services',
	'/template/page-home/services/text' => '<p>One</p>', '/template/page-home/contact/title' => 'Contact', '/_nino/webpage/contact/name' => 'Contact us', '/_nino/webpage/contact/uri' => '/contact',
];
foreach( [ 'en_US', 'de_DE' ] as $locale )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', array_combine( array_map( static fn( string $key ): string => '[['. $key. ']]', array_keys( $texts ) ), array_values( $texts ) ) );

\Nino\AppData::writeContentData( $appData, [ '/nino/http/routes' ] );
$appData['/nino/html/images']['/template/page-home/hero/background'] = [ 'label' => 'Hero', 'width' => 1600, 'height' => 900, 'filename' => 'hero.1600x900.jpg' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );

\Nino\Elements::insertElementType( $appData, '/services', [
	'title'		=> [ 'type' => 'string', 'locale' => true ],
	'summary'	=> [ 'type' => 'string', 'locale' => true, 'html' => true, 'blocks' => true ],
	'image'		=> [ 'type' => 'image', 'width' => 400, 'height' => 300 ],
] );
\Nino\Elements::insertElement( $appData, '/services/web', [ 'title' => 'Web', 'summary' => 'Sites', 'image' => '' ], 'en_US' );
\Nino\Elements::insertElement( $appData, '/services/app', [ 'title' => 'App', 'summary' => 'Apps', 'image' => '' ], 'en_US' );
\Nino\Filesystem::putFileContent( $appData, '/templates/html-header.tpl', '<header>Header</header>' );
\Nino\Filesystem::putFileContent( $appData, '/templates/html-footer.tpl', '<footer>Footer</footer>' );
\Nino\Filesystem::putFileContent( $appData, '/templates/html-header-short.tpl', '<header>Short</header>' );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-home.tpl', $fixture );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-legacy.tpl', "<h1>Old</h1>\n[template /templates/html-footer]\n" );

\Nino\Html::addShortcode( $appData, 'osm', static fn( array &$appData, array $args ): string => '<div class="map"></div>' );
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Components' ];
\Nino\Modules::callModules( $appData, 'init' );
ninoWarnings();

$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/', 'body' => '[template /templates/page-home]' ];
$appData['/nino/http/routes']['GET://about'] = [ 'uri' => '/about', 'body' => '[template /templates/frame-header][template /templates/page-about]' ];

$registryAnswer = \Nino\Modules\Builder\Document::registry( $appData );

check( 'the registry hands the panel the components and the stacks with their labels in the language of the workbench, and the defaults of their attributes', $registryAnswer['status'] === 200
	&& $registryAnswer['components']['title']['label'] === 'Title' && $registryAnswer['components']['title']['attributes']['level']['label'] === 'Level'
	&& $registryAnswer['components']['title']['defaults']['level'] === '2' && $registryAnswer['stacks']['stack']['label'] === 'Stack' && $registryAnswer['stacks']['stack']['defaults']['cols'] === '100 50 33' );
check( '...the element types of the project with their fields and the field types there are', array_column( $registryAnswer['types'], 'uri' ) === [ '/services' ]
	&& array_keys( $registryAnswer['types'][0]['fields'] ) === [ 'title', 'summary', 'image' ] && $registryAnswer['types'][0]['fields']['image']['type'] === 'image'
	&& $registryAnswer['fieldTypes'] === \Nino\Elements::FIELD_TYPES );
check( '...the image slots, the headers and the footers', array_column( $registryAnswer['slots'], 'uri' ) === [ '/template/page-home/hero/background' ] && $registryAnswer['slots'][0]['hasImage'] === true
	&& $registryAnswer['headers'] === [ 'html-header', 'html-header-short' ] && $registryAnswer['footers'] === [ 'html-footer' ] );
$appData['/nino/html/images']['/project/logo/header/image'] = [ 'label' => 'Logo', 'width' => 300, 'height' => 100, 'filename' => null ];
$withEmpty = array_column( \Nino\Modules\Builder\Document::registry( $appData )['slots'], null, 'uri' );
unset( $appData['/nino/html/images']['/project/logo/header/image'] );
check( '...a slot with a picture says where it is (the Images panel\'s url) for the thumbnail of the source field, one without says null', $registryAnswer['slots'][0]['url'] === \Nino\Images::getUrl( $appData, 'hero.1600x900.jpg' )
	&& $withEmpty['/project/logo/header/image']['hasImage'] === false && $withEmpty['/project/logo/header/image']['url'] === null );
check( '...the components and the stacks the panel\'s script test reads (fixtures/registry.json) are what the registry says', json_decode( (string) file_get_contents( __DIR__. '/fixtures/registry.json' ), true ) === json_decode( (string) json_encode( [ 'components' => $registryAnswer['components'], 'stacks' => $registryAnswer['stacks'] ] ), true ) );

$listed = \Nino\Modules\Builder\Document::list( $appData );
$byFile = array_column( $listed['templates'], null, 'file' );

check( 'the list has every page-*.tpl of the project, by file', $listed['status'] === 200 && array_keys( $byFile ) === [ 'page-home', 'page-legacy' ] );
check( '...with the name, the frames, the number of sections and of blocks of html, and whether it is read completely', $byFile['page-home']['name'] === 'Home' && $byFile['page-home']['header'] === 'html-header'
	&& $byFile['page-home']['footer'] === 'html-footer' && $byFile['page-home']['sections'] === 3 && $byFile['page-home']['foreign'] === 1 && $byFile['page-home']['readable'] === true );
check( '...a file the builder does not read is one block of html that failed, and listed as not read completely', $byFile['page-legacy']['sections'] === 0 && $byFile['page-legacy']['foreign'] === 1
	&& $byFile['page-legacy']['readable'] === false && $byFile['page-legacy']['footer'] === 'html-footer' );
check( '...and the routes that name it', $byFile['page-home']['usedBy'] === [ [ 'route' => 'GET://', 'uri' => '/' ] ] && $byFile['page-legacy']['usedBy'] === [] );

$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
check( 'load answers the model, the hash of the file and the file', $loaded['status'] === 200 && $loaded['model']['file'] === 'page-home' && $loaded['hash'] === hash( 'sha256', $fixture ) && $loaded['source'] === $fixture
	&& $loaded['model']['blocks'] === $model['blocks'] );
check( 'a page that is not there is 404', \Nino\Modules\Builder\Document::load( $appData, 'page-nothing' )['status'] === 404 );

// The name is a slug of the grammar before any path is built from it
$guarded = [ '../config', '..', 'page-../../config', 'page-', 'page--x', 'page-X', 'page-a_b', 'page-a/b', 'page-a.b', "page-a\0", 'page-a b', '', 'html-header', '/etc/passwd', 'page-home.tpl', 'page-home ' ];
$refused = [];
foreach( $guarded as $name ) {
	$answers = [
		\Nino\Modules\Builder\Document::load( $appData, $name )['status'],
		\Nino\Modules\Builder\Document::save( $appData, $name, $loaded['model'], $loaded['hash'] )['status'],
		\Nino\Modules\Builder\Document::delete( $appData, $name )['status'],
	];
	if( $answers !== [ 400, 400, 400 ] )
		$refused[] = json_encode( $name );
}
check( 'a file name that is no page-<slug> is refused before a path is built - load, save and delete, '. count( $guarded ). ' names, among them traversal, a nul byte and a suffix'. ( $refused === [] ? '' : ' - '. implode( ', ', $refused ) ), $refused === [] );
check( 'the files of the project are all still there', is_file( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === true && is_file( \Nino\Filesystem::path( $appData, '/templates/html-header.tpl' ) ) === true );

echo "\n";


// --- 7. Saving ---------------------------------------------------------------

echo "Document - save\n";

// The accounts the panels of the keys and the slots ask the permission of
\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

$saved = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $loaded['model'], $loaded['hash'] );
check( 'a save without a change writes the same file, byte for byte, and answers the model it reads back', $saved['status'] === 200
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $fixture && $saved['model'] === $loaded['model'] && $saved['hash'] === $loaded['hash'] );

// A new key and a new slot, a new section, a block of html
$edit = $loaded['model'];
$edit['name'] = 'Home page';
$edit['header'] = 'html-header-short';
$edit['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-home/contact/note', 'text' => null,
	'attributes' => [ 'format' => 'blocks', 'style' => '', 'class' => '' ], 'create' => [ 'value' => '<p>Write to us.</p>' ] ];
$edit['blocks'][3]['background'] = [ 'slot' => '/template/page-home/contact/background', 'focus' => 2, 'create' => [ 'label' => 'Contact background', 'width' => 1200, 'height' => 600 ] ];
$edit['blocks'][2]['source'] = "<section id=\"map\" class=\"nino-section nino-section--tint\">\n\t<div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[osm lat=\"48.2\"]</div></div>\n</section>";
$edit['blocks'][] = [ 'kind' => 'html', 'source' => '<p>Imprint note</p>', 'reason' => null ];

$saved = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $edit, $loaded['hash'] );
$written = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) );

check( 'a save with changes answers 200, and the file is what the Writer makes of the model', $saved['status'] === 200 && $written === $write( $edit ) && $saved['hash'] === hash( 'sha256', $written ) );
check( '...the model it answers is the file read again - what was only the panel\'s, the create, is gone', $saved['model']['blocks'][3]['cols'][0]['components'][2]['name'] === 'text' && isset( $saved['model']['blocks'][3]['cols'][0]['components'][2]['create'] ) === false
	&& $saved['model']['name'] === 'Home page' && $saved['model']['header'] === 'html-header-short' && $saved['model'] === \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['model'] );
check( '...the text key the model named as new is made, in every language, with the value it was given', ( \Nino\Text::entry( $appData, '/template/page-home/contact/note' )['values']['en_US'] ?? null ) === '<p>Write to us.</p>'
	&& ( \Nino\Text::entry( $appData, '/template/page-home/contact/note' )['values']['de_DE'] ?? null ) === '<p>Write to us.</p>' );
check( '...the image slot too, with the size it was given and no file', ( \Nino\Images::getSlot( $appData, '/template/page-home/contact/background' ) ?: [] ) === [ 'label' => 'Contact background', 'width' => 1200, 'height' => 600, 'filename' => null ] );
check( '...a key that was there is left as it is', ( \Nino\Text::entry( $appData, '/template/page-home/hero/title' )['values']['en_US'] ?? null ) === 'Hello' );
check( '...and a block of html is kept, the one made in the panel in markers', str_contains( $written, "<!-- nino:html -->\n<p>Imprint note</p>\n<!-- /nino:html -->" ) === true && str_contains( $written, '[osm lat="48.2"]' ) === true );

// A block that was edited in the panel is read as a section again
$again = $saved['model'];
$again['blocks'][2] = [ 'kind' => 'html', 'source' => "<section id=\"map\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-home/map/title]\n\t\t</div>\n\t</div>\n</section>", 'reason' => null, 'edited' => true ];
$again['blocks'][4]['edited'] = true;
$retry = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $again, $saved['hash'] );
check( 'a block that was edited and reads as a section is a section from then on, one that does not stays a block of html - and one that was not edited is not tried',
	$retry['status'] === 200 && array_column( $retry['model']['blocks'], 'kind' ) === [ 'section', 'section', 'section', 'section', 'html' ] && $retry['model']['blocks'][2]['id'] === 'map'
	&& $retry['model']['blocks'][4]['source'] === '<p>Imprint note</p>' && $retry['model']['blocks'][4]['reason'] === null );

$unedited = $retry['model'];
$unedited['blocks'][] = [ 'kind' => 'html', 'source' => $edit['blocks'][2]['source'], 'reason' => null ];
check( '...a marked block that would read as a section but was not edited is not touched', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $unedited, $retry['hash'] )['model']['blocks'][5]['kind'] === 'html' );

// Renaming a section moves its keys
$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$rename = $loaded['model'];
$rename['blocks'][0]['id'] = 'start';
$rename['blocks'][0]['renamedFrom'] = 'hero';
$rename['blocks'][1]['id'] = 'leistungen';
$rename['blocks'][1]['renamedFrom'] = 'services';
$renamed = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $rename, $loaded['hash'] );
$source = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) );

check( 'renaming a section rewrites the sources of its components and of its background in the file', $renamed['status'] === 200 && str_contains( $source, '[title /template/page-home/start/title level="1" style="loud"]' ) === true
	&& str_contains( $source, '[image /template/page-home/start/background alt=""]' ) === true && str_contains( $source, '[title /template/page-home/leistungen/title]' ) === true
	&& str_contains( $source, '/template/page-home/hero/' ) === false && str_contains( $source, '/template/page-home/services/' ) === false );
check( '...and the text keys follow: the value is under the new name, and the old name is gone', ( \Nino\Text::entry( $appData, '/template/page-home/start/title' )['values']['en_US'] ?? null ) === 'Hello'
	&& ( \Nino\Text::entry( $appData, '/template/page-home/leistungen/title' )['values']['en_US'] ?? null ) === 'Services'
	&& \Nino\Text::entry( $appData, '/template/page-home/hero/title' ) === null && \Nino\Text::entry( $appData, '/template/page-home/services/title' ) === null );
check( '...the slot of the background is made again under the new name with the file of the old one, and the old one is kept - without a file', ( \Nino\Images::getSlot( $appData, '/template/page-home/start/background' ) ?: [] )['filename'] === 'hero.1600x900.jpg'
	&& ( \Nino\Images::getSlot( $appData, '/template/page-home/start/background' ) ?: [] )['label'] === 'Hero' && ( \Nino\Images::getSlot( $appData, '/template/page-home/hero/background' ) ?: [ 'filename' => 'none' ] )['filename'] === null );
check( '...and the answer is the file as it reads now, without what only the panel sends', $renamed['model']['blocks'][0]['id'] === 'start' && isset( $renamed['model']['blocks'][0]['renamedFrom'] ) === false );

// What a save refuses for is refused before it changes anything, and what it moves, it moves whole
foreach( [ 'en_US', 'de_DE' ] as $locale )
	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ): array {
		return (array) $texts + [ '[[/template/page-probe/services/title]]' => 'Our services', '[[/template/page-probe/about/title]]' => 'About us (old, orphaned)' ];
	} );
$appData['/nino/html/images']['/template/page-probe/services/background'] = [ 'label' => 'Services', 'width' => 1200, 'height' => 600, 'filename' => 'probe.1200x600.jpg' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
check( 'the alt texts of the slot the probe page owns are stored', \Nino\Images::setSlotAlt( $appData, '/template/page-probe/services/background', [ 'en_US' => 'Our team', 'de_DE' => 'Unser Team' ] ) === true );

$probeSource = $page( "<section id=\"services\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-probe/services/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-probe/services/title]\n\t\t</div>\n\t</div>\n</section>" );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-probe.tpl', $probeSource );
$probe = \Nino\Modules\Builder\Document::load( $appData, 'page-probe' );
$state = static fn(): array => [ file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-probe.tpl' ) ), \Nino\Text::entry( $appData, '/template/page-probe/services/title' ) !== null, \Nino\Text::entry( $appData, '/template/page-probe/leistungen/title' ) !== null,
	\Nino\Images::getSlot( $appData, '/template/page-probe/services/background' ), \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ];
$untouched = $state();

$take = $probe['model'];
$take['blocks'][0]['id'] = 'about';
$take['blocks'][0]['renamedFrom'] = 'services';
$taken = \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $take, $probe['hash'] );
check( 'a section renamed to the id of a section that was deleted - its keys are still there - is refused with 409 before anything is moved, the keys in the way in the problems',
	$taken['status'] === 409 && $taken['code'] === 'key-exists' && $taken['problems'] === [ 'the key "/template/page-probe/about/title" is there already' ] && $state() === $untouched
	&& \Nino\Text::entry( $appData, '/template/page-probe/about/title' )['values']['en_US'] === 'About us (old, orphaned)' && \Nino\Images::getSlot( $appData, '/template/page-probe/about/background' ) === false );

$huge = $probe['model'];
$huge['blocks'][0]['id'] = 'leistungen';
$huge['blocks'][0]['renamedFrom'] = 'services';
$huge['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-probe/leistungen/photo', 'text' => null, 'attributes' => $registry['components']['image']['defaults'], 'create' => [ 'width' => 99999, 'height' => 99999 ] ];
$refused = \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $huge, $probe['hash'] );
check( 'a slot that cannot be made - too large for an upload - refuses the save before a key is moved: the file and the keys are as they were', $refused['status'] === 400 && $refused['code'] === 'invalid'
	&& str_contains( implode( ' ', $refused['problems'] ), 'larger than an upload can produce' ) === true && $state() === $untouched );

$badKey = $probe['model'];
$badKey['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-probe/services/Bad', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'x', 'format' => 'plain' ] ];
$badFormat = $probe['model'];
$badFormat['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-probe/services/other', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'x', 'format' => 'markdown' ] ];
check( '...and so does a key that is none, or one of a format there is none of', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $badKey, $probe['hash'] )['problems'] ), 'means nothing' ) === true
	&& str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $badFormat, $probe['hash'] )['problems'] ), 'format "markdown"' ) === true && $state() === $untouched );

\Nino\Auth::insertUser( $appData, 'keys@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/keys/manage' ] );
\Nino\Auth::loginUser( $appData, 'keys@example.com', 'correct horse battery staple' );
$rename = $probe['model'];
$rename['blocks'][0]['id'] = 'leistungen';
$rename['blocks'][0]['renamedFrom'] = 'services';
$forbidden = \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $rename, $probe['hash'] );
check( 'an account that may move keys but not slots cannot rename a section that has a slot: 403 - and the keys have not moved', $forbidden['status'] === 403 && $forbidden['code'] === 'permission' && $forbidden['problems'] === [ '/_admin/slots/manage' ] && $state() === $untouched );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

$call = static function( string $method, array $arguments ) {
	return ( new \ReflectionMethod( \Nino\Modules\Builder\Document::class, $method ) )->invokeArgs( null, $arguments );
};

$moved = \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $rename, $probe['hash'] );
$new = \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ?: [];
check( 'the rename that is allowed: the key and the file are the new ones', $moved['status'] === 200 && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-probe.tpl' ) ), '[title /template/page-probe/leistungen/title]' ) === true
	&& \Nino\Text::entry( $appData, '/template/page-probe/services/title' ) === null && ( \Nino\Text::entry( $appData, '/template/page-probe/leistungen/title' )['values']['de_DE'] ?? null ) === 'Our services' );
check( '...the new slot has the alt texts of the old one, and the file', ( $new['alt'] ?? null ) === [ 'en_US' => 'Our team', 'de_DE' => 'Unser Team' ] && ( $new['filename'] ?? null ) === 'probe.1200x600.jpg' );
check( '...and the old slot is left without a file: deleting it, or uploading into it, cannot take the image of the renamed section away', ( \Nino\Images::getSlot( $appData, '/template/page-probe/services/background' ) ?: [ 'filename' => 'none' ] )['filename'] === null );

$_POST['data'] = (string) json_encode( [ 'uri' => '/template/page-probe/services/background' ] );
$deleteRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Images\Slots::apiDelete( $appData, $deleteRequest );
unset( $_POST['data'] );
check( '...so that the old slot can be deleted in the Slots tab, and the renamed section keeps its image', $deleteRequest['/nino/http/response']['statusCode'] === 200 && \Nino\Images::getSlot( $appData, '/template/page-probe/services/background' ) === false
	&& ( \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ?: [] )['filename'] === 'probe.1200x600.jpg' );

// A save that moved keys and then could not write the file moves them back
$plan = [ 'keys' => [], 'slots' => [], 'keyMoves' => [ '/template/page-probe/leistungen/title' => '/template/page-probe/diensten/title' ], 'slotMoves' => [ '/template/page-probe/leistungen/background' => '/template/page-probe/diensten/background' ] ];
$made = [];
$call( '_create', [ &$appData, $plan, &$made ] );
[ $failure, $undo ] = $call( '_move', [ &$appData, $plan ] );
check( 'what is moved is a key and the file of a slot', $failure === null && count( $undo ) === 2 && \Nino\Text::entry( $appData, '/template/page-probe/diensten/title' ) !== null && \Nino\Text::entry( $appData, '/template/page-probe/leistungen/title' ) === null
	&& ( \Nino\Images::getSlot( $appData, '/template/page-probe/diensten/background' ) ?: [] )['filename'] === 'probe.1200x600.jpg' && ( \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ?: [ 'filename' => 'none' ] )['filename'] === null );
$call( '_undo', [ &$appData, $undo ] );
check( '...and put back where the file is not written: the key under its name, the file in its slot, the slot that was made empty', \Nino\Text::entry( $appData, '/template/page-probe/leistungen/title' ) !== null && \Nino\Text::entry( $appData, '/template/page-probe/diensten/title' ) === null
	&& ( \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ?: [] )['filename'] === 'probe.1200x600.jpg' && ( \Nino\Images::getSlot( $appData, '/template/page-probe/diensten/background' ) ?: [ 'filename' => 'none' ] )['filename'] === null );

// A save that stops after the slot it moves to was made takes that slot away: the same save can be tried again
$call( '_drop', [ &$appData, [ '/template/page-probe/diensten/background' ] ] );
check( 'the slots that were made for a move are taken away again, by the Slots tab\'s own action', \Nino\Images::getSlot( $appData, '/template/page-probe/diensten/background' ) === false
	&& \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) !== false );

$probe = \Nino\Modules\Builder\Document::load( $appData, 'page-probe' );
$again = $probe['model'];
$again['blocks'][0]['id'] = 'start';
$again['blocks'][0]['renamedFrom'] = 'leistungen';
$untouched = [ file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-probe.tpl' ) ), \Nino\Text::entry( $appData, '/template/page-probe/leistungen/title' ), \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ];
$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/templates/page-probe.tpl' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$failed = \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $again, $probe['hash'] );
check( 'a save whose file cannot be written answers 500, and the file, the keys and the slots are as they were - the slot it was going to move to is not left behind',
	$failed['status'] === 500 && $failed['code'] === 'write' && $untouched === [ file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-probe.tpl' ) ), \Nino\Text::entry( $appData, '/template/page-probe/leistungen/title' ), \Nino\Images::getSlot( $appData, '/template/page-probe/leistungen/background' ) ]
	&& \Nino\Text::entry( $appData, '/template/page-probe/start/title' ) === null && \Nino\Images::getSlot( $appData, '/template/page-probe/start/background' ) === false );
rmdir( $lock );
$retried = \Nino\Modules\Builder\Document::save( $appData, 'page-probe', $again, $probe['hash'] );
check( '...and the same save, tried again, is not refused for a slot nobody made: 200, the key and the file are the new ones', $retried['status'] === 200 && \Nino\Text::entry( $appData, '/template/page-probe/start/title' ) !== null
	&& ( \Nino\Images::getSlot( $appData, '/template/page-probe/start/background' ) ?: [] )['filename'] === 'probe.1200x600.jpg' );

// A key that is made and moved to is made once
foreach( [ 'en_US', 'de_DE' ] as $locale )
	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ): array {
		return (array) $texts + [ '[[/template/page-probe/start/extra]]' => 'Orphaned' ];
	} );
$probe = \Nino\Modules\Builder\Document::load( $appData, 'page-probe' );
$extra = $probe['model'];
$extra['blocks'][0]['id'] = 'drei';
$extra['blocks'][0]['renamedFrom'] = 'start';
$extra['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-probe/start/extra', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'Made twice' ] ];
$moves = $call( '_rename', [ &$extra, 'page-probe', $registry ] );
$planned = $call( '_plan', [ &$appData, $extra, $moves, $registry ] );
check( 'a key that a move takes over is not made by the save as well, where it is a new component\'s: the plan moves it and makes none', $planned[0] === null && $planned[1]['keys'] === []
	&& $planned[1]['keyMoves']['/template/page-probe/start/extra'] === '/template/page-probe/drei/extra' );

// A model that is what the file reads as is not written: the file keeps its bytes, whatever they were
$shapes = [
	'a file whose foreign block touches the head and the footer'		=> "[template /templates/html-header]\n<section class=\"nino-atf nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<h2>[[/template/page-lib/hero/title]]</h2>\n\t</div>\n</section>\n[template /templates/html-footer]\n",
	'a file whose sections are written without blank lines'					=> "<!-- nino:template-name Lib -->\n[template /templates/html-header]\n". $section( '[title /template/page-lib/a/title]', 'a' ). "\n". $section( '[title /template/page-lib/b/title]', 'b' ). "\n[template /templates/html-footer]\n",
	'a file written with other line ends and a last line without one'	=> "[template /templates/html-header]\r\n\r\n\r\n". $section( '[title /template/page-lib/a/title]', 'a' ). "\r\n[template /templates/html-footer]",
];

foreach( $shapes as $label => $text ) {

	\Nino\Filesystem::putFileContent( $appData, '/templates/page-lib.tpl', $text );
	$lib = \Nino\Modules\Builder\Document::load( $appData, 'page-lib' );
	$again = \Nino\Modules\Builder\Document::save( $appData, 'page-lib', $lib['model'], $lib['hash'] );

	check( $label. ': a load and a save without a change leaves the bytes as they were, and answers the same hash', $again['status'] === 200
		&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-lib.tpl' ) ) === $text && $again['hash'] === $lib['hash'] && $again['model'] === $lib['model'] );
}

// A section that says it was renamed from its own id has not been renamed: the hand-written file keeps its bytes
\Nino\Filesystem::putFileContent( $appData, '/templates/page-lib.tpl', $shapes['a file whose sections are written without blank lines'] );
$lib = \Nino\Modules\Builder\Document::load( $appData, 'page-lib' );
$lib['model']['blocks'][0]['renamedFrom'] = $lib['model']['blocks'][0]['id'];
$lib['model']['blocks'][1]['renamedFrom'] = '';
$same = \Nino\Modules\Builder\Document::save( $appData, 'page-lib', $lib['model'], $lib['hash'] );
check( 'a section posted with renamedFrom equal to its own id (or empty) is not renamed and not changed: the file keeps its bytes, hand-written as it is', $same['status'] === 200
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-lib.tpl' ) ) === $shapes['a file whose sections are written without blank lines'] && $same['hash'] === $lib['hash'] );

// A save that stops after it made a slot takes that slot away, and a slot that holds a file is never taken
\Nino\Filesystem::putFileContent( $appData, '/templates/page-rb.tpl', $page(
	"<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-rb/one/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-rb/one/title]\n\t\t</div>\n\t</div>\n</section>",
	"<section id=\"two\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-rb/two/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-rb/two/title]\n\t\t</div>\n\t</div>\n</section>"
) );
$appData['/nino/html/images']['/template/page-rb/one/background'] = [ 'label' => 'One', 'width' => 1200, 'height' => 600, 'filename' => 'one.1200x600.jpg' ];
$appData['/nino/html/images']['/template/page-rb/two/background'] = [ 'label' => '', 'width' => 1200, 'height' => 600, 'filename' => 'two.1200x600.jpg' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
$rb = \Nino\Modules\Builder\Document::load( $appData, 'page-rb' );
$rbBefore = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-rb.tpl' ) );
$rbSlots = \Nino\Images::getSlots( $appData );
$rbModel = $rb['model'];
$rbModel['blocks'][0]['id'] = 'eins';
$rbModel['blocks'][0]['renamedFrom'] = 'one';
$rbModel['blocks'][1]['id'] = 'zwei';
$rbModel['blocks'][1]['renamedFrom'] = 'two';
$rbAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-rb', $rbModel, $rb['hash'] );
check( 'an old slot with no label is renamed with its section: the Slots tab refuses to make the new one (400, code slot), the file is as it was, and the slot made for the first section is taken away again',
	$rbAnswer['status'] === 400 && $rbAnswer['code'] === 'slot' && file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-rb.tpl' ) ) === $rbBefore
	&& \Nino\Images::getSlot( $appData, '/template/page-rb/eins/background' ) === false && \Nino\Images::getSlot( $appData, '/template/page-rb/zwei/background' ) === false
	&& \Nino\Images::getSlots( $appData ) === $rbSlots );

$made = [ '/template/page-rb/eins/background' ];
$appData['/nino/html/images']['/template/page-rb/eins/background'] = [ 'label' => 'Eins', 'width' => 1200, 'height' => 600, 'filename' => 'eins.1200x600.jpg' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
$call( '_drop', [ &$appData, $made ] );
check( '...and a slot that holds a file when the rollback comes to it is left: the image of the old slot is never deleted', ( \Nino\Images::getSlot( $appData, '/template/page-rb/eins/background' ) ?: [] )['filename'] === 'eins.1200x600.jpg' );
\Nino\Modules\Builder\Document::delete( $appData, 'page-rb' );

$delete = \Nino\Modules\Builder\Document::delete( $appData, 'page-lib' );
\Nino\Modules\Builder\Document::delete( $appData, 'page-probe' );
check( 'the files of these probes are gone again', $delete['status'] === 200 && \Nino\Filesystem::fileExists( $appData, '/templates/page-probe.tpl' ) === false );

// What is refused
$before = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) );
$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );

$bad = $loaded['model'];
$bad['blocks'][1]['cols'][0]['components'][0]['name'] = 'osm';
$bad['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/never', 'text' => null, 'attributes' => [ 'level' => '2', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'Never' ] ];
$refusal = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $bad, $loaded['hash'] );
check( 'a section that does not read back is refused with 400, the reason of the Reader in the problems, and nothing is written or made', $refusal['status'] === 400 && $refusal['code'] === 'invalid'
	&& count( $refusal['problems'] ) === 1 && str_contains( $refusal['problems'][0], 'leistungen' ) === true && str_contains( $refusal['problems'][0], 'osm' ) === true
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $before && \Nino\Text::entry( $appData, '/template/page-home/contact/never' ) === null );

$twice = $loaded['model'];
$twice['blocks'][3]['id'] = 'start';
check( 'an id that two sections have is a problem', in_array( 'the id "start" is the id of two sections', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $twice, $loaded['hash'] )['problems'], true ) === true );

$static = $loaded['model'];
$static['blocks'][3]['cols'][0]['components'][0]['source'] = 'title';
$stackless = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $static, $loaded['hash'] );
check( 'a field name where there is no stack means nothing, and is a problem', $stackless['status'] === 400 && str_contains( implode( ' ', $stackless['problems'] ), 'static stack' ) === true );

$typeless = $loaded['model'];
$typeless['blocks'][1]['cols'][1]['stack']['source'] = '/nothing';
check( 'a stack of a type the Elements panel does not know is a problem', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $typeless, $loaded['hash'] )['problems'] ), '"nothing", which is no element type' ) === true );

$fieldless = $loaded['model'];
$fieldless['blocks'][1]['cols'][1]['components'][1]['source'] = 'nope';
check( '...and a field the type does not have', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $fieldless, $loaded['hash'] )['problems'] ), 'is no field of the stack\'s type' ) === true );

$marker = $loaded['model'];
$marker['blocks'][] = [ 'kind' => 'html', 'source' => "<p>x</p>\n<!-- /nino:html -->\n<p>y</p>", 'reason' => null ];
check( 'a block of html that holds the end of its own marker is a problem', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $marker, $loaded['hash'] )['problems'] ), 'end marker' ) === true );

$frames = $loaded['model'];
$frames['header'] = '../config';
check( 'a header that is no html-header template is a problem', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $frames, $loaded['hash'] )['problems'] ), 'header' ) === true );

$quote = $loaded['model'];
$quote['blocks'][3]['cols'][0]['components'][0]['text'] = 'Say "hi"';
check( 'a fixed text with a quote in it, which a call cannot hold, is refused', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $quote, $loaded['hash'] )['status'] === 400 );

// What a call cannot carry is refused before anything is written: a class, a fixed text, a stack's attribute
$unsafe = [
	'a class'						=> static function( array &$model, string $value ): void { $model['blocks'][3]['cols'][0]['components'][0]['attributes']['class'] = $value; },
	'a fixed text'			=> static function( array &$model, string $value ): void { $model['blocks'][1]['cols'][1]['components'][3]['text'] = $value; },
	'a stack attribute'	=> static function( array &$model, string $value ): void { $model['blocks'][1]['cols'][1]['stack']['attributes']['sort'] = $value; },
	'a custom class'		=> static function( array &$model, string $value ): void { $model['blocks'][0]['settings']['custom'] = $value; },
];
$accepted = [];

foreach( $unsafe as $label => $set )
	foreach( [ 'a" level="1' => 'a double quote', "it's" => 'a single quote', 'a]b' => 'a closing bracket', 'a[b' => 'an opening bracket', "a\nb" => 'a line feed', "a\rb" => 'a carriage return' ] as $value => $name ) {

		$model = $loaded['model'];
		$set( $model, (string) $value );
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'value' || str_contains( implode( ' ', $answer['problems'] ), 'block' ) === false
			|| file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$accepted[] = $label. ' with '. $name;
	}

check( 'a double quote, a single quote, a bracket or a line break in a class, a fixed text, a stack attribute or a custom class is refused with 400 (code value), and the file is as it was: '. count( $unsafe ) * 6 .' cases', $accepted === [] );

$named = $loaded['model'];
$named['blocks'][3]['cols'][0]['components'][0]['attributes']['class'] = 'a" level="1';
check( '...the problem names the block, the component and the attribute, and problems() says the same to a caller that is not a save',
	\Nino\Modules\Builder\Document::save( $appData, 'page-home', $named, $loaded['hash'] )['problems'] === \Nino\Modules\Builder\Document::problems( $appData, $named, $registry )
	&& str_contains( \Nino\Modules\Builder\Document::problems( $appData, $named, $registry )[0], 'the section "contact" (block 4)' ) === true
	&& str_contains( \Nino\Modules\Builder\Document::problems( $appData, $named, $registry )[0], 'the attribute "class" of the component "title"' ) === true );

$thrown = 0;

foreach( [ 'class', 'text' ] as $where ) {

	$model = $loaded['model'];

	if( $where === 'class' )
		$model['blocks'][3]['cols'][0]['components'][0]['attributes']['class'] = 'a" level="1';
	else
		$model['blocks'][3]['cols'][0]['components'][0]['text'] = "it's";

	try {
		$write( $model );
	}
	catch( \UnexpectedValueException ) {
		$thrown++;
	}
}

check( 'the Writer asserts the same: a value that holds such a character never reaches the file, it throws', $thrown === 2 );

check( 'nothing the refusals made is in the project, and the file is as it was', file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $before );

// A model that is no model is refused with 400, never a type error
$shaped = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$shape = static function( callable $break ) use ( $appData, $shaped ): array {
	$model = $shaped['model'];
	$break( $model );
	return \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $shaped['hash'] );
};
$answers = [
	'a name that is a list'														=> $shape( static function( array &$model ): void { $model['name'] = [ 'a' ]; } ),
	'a block that is a string'												=> $shape( static function( array &$model ): void { $model['blocks'][2] = 'x'; } ),
	'a foreign block whose reason is a string'				=> $shape( static function( array &$model ): void { $model['blocks'][array_key_first( array_filter( $model['blocks'], static fn( array $block ): bool => $block['kind'] === 'html' ) )]['reason'] = 'because'; } ),
	'a foreign block whose source is a list'					=> $shape( static function( array &$model ): void { $model['blocks'][array_key_first( array_filter( $model['blocks'], static fn( array $block ): bool => $block['kind'] === 'html' ) )]['source'] = []; } ),
	'a section whose id is a list'										=> $shape( static function( array &$model ): void { $model['blocks'][0]['id'] = []; } ),
	'a call whose name is a list'											=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['name'] = []; } ),
	'a column that is a string'												=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0] = 'x'; } ),
];
$typed = array_filter( $answers, static fn( array $answer ): bool => $answer['status'] !== 400 || $answer['code'] !== 'invalid' );
check( 'a model of the wrong shape is refused with 400 and a problem, none of '. count( $answers ). ' ends in an error: '. implode( ', ', array_keys( $answers ) ), $typed === [] && ninoWarnings() === [] );

$stackWrong = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$stackWrong['model']['blocks'][3]['cols'][0]['components'] = [ [ 'name' => 'stack', 'source' => '/services', 'text' => null, 'attributes' => [], 'content' => '' ] ];
$stackAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $stackWrong['model'], $stackWrong['hash'] );
check( 'a component called stack in a static column reads back as a stack: it is not the section that was posted, and is a problem', $stackAnswer['status'] === 400 && str_contains( implode( ' ', $stackAnswer['problems'] ), 'does not read back as it was written' ) === true );

// The conflict
$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$mine = $loaded['model'];
$mine['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/late', 'text' => null, 'attributes' => [ 'level' => '2', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'Late' ] ];
$theirs = $before. "\n<p>Edited elsewhere</p>\n";
\Nino\Filesystem::putFileContent( $appData, '/templates/page-home.tpl', $theirs );

$conflict = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $mine, $loaded['hash'] );
check( 'a file that changed on disk since it was loaded answers 409 and is left as the other window wrote it', $conflict['status'] === 409 && $conflict['code'] === 'conflict'
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $theirs );
check( '...and nothing is made for a save that is refused', \Nino\Text::entry( $appData, '/template/page-home/contact/late' ) === null );

$forced = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $mine, $loaded['hash'], true );
check( 'with force it is written over the other window\'s change', $forced['status'] === 200 && file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $write( $mine )
	&& \Nino\Text::entry( $appData, '/template/page-home/contact/late' ) !== null );

$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
check( 'a hash of nothing is no hash: it is a conflict too', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $loaded['model'], '' )['status'] === 409 );

// Without the permission of the panel that owns a key, no key is made
\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/builder/manage' ] );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );
$limited = $loaded['model'];
$limited['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/forbidden', 'text' => null, 'attributes' => [ 'level' => '2', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'No' ] ];
$limitedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $limited, $loaded['hash'] );
check( 'an account that may not make text keys cannot make them through the builder: the builder answers 403 before it makes anything, and the file is not written', $limitedAnswer['status'] === 403
	&& \Nino\Text::entry( $appData, '/template/page-home/contact/forbidden' ) === null && file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $write( $mine ) );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

echo "\n";


// --- 8. Creating and deleting ------------------------------------------------

echo "Document - create and delete\n";

$created = \Nino\Modules\Builder\Document::create( $appData, 'Über uns' );
check( 'a new template: the file is page-<the name as a slug>, with the frames the project has', $created['status'] === 200 && $created['model']['file'] === 'page-ueber-uns' && $created['model']['name'] === 'Über uns'
	&& $created['model']['header'] === 'html-header' && $created['model']['footer'] === 'html-footer' && $created['model']['blocks'] === [] );
check( '...it is the empty canonical page, written, and read back as the model', file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-ueber-uns.tpl' ) ) === "<!-- nino:template-name Über uns -->\n[template /templates/html-header]\n\n[template /templates/html-footer]\n"
	&& $created['hash'] === hash( 'sha256', (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-ueber-uns.tpl' ) ) )
	&& \Nino\Modules\Builder\Document::load( $appData, 'page-ueber-uns' )['model'] === $created['model'] );
check( '...a name that gives a file that is there is refused with 409, and the file is not touched', \Nino\Modules\Builder\Document::create( $appData, 'ÜBER Uns!' )['status'] === 409
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-ueber-uns.tpl' ) ), 'Über uns' ) === true );

$short = \Nino\Modules\Builder\Document::create( $appData, 'Short', 'html-header-short', '' );
check( 'the frames can be named, or none', $short['status'] === 200 && $short['model']['header'] === 'html-header-short' && $short['model']['footer'] === ''
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-short.tpl' ) ) === "<!-- nino:template-name Short -->\n[template /templates/html-header-short]\n" );
check( '...but only frames the project has, of the right kind', \Nino\Modules\Builder\Document::create( $appData, 'Third', 'html-header-gone' )['status'] === 400
	&& \Nino\Modules\Builder\Document::create( $appData, 'Third', 'html-footer' )['status'] === 400 && \Nino\Modules\Builder\Document::create( $appData, 'Third', '../config' )['status'] === 400
	&& \Nino\Filesystem::fileExists( $appData, '/templates/page-third.tpl' ) === false );
check( '...a frame name that is none is refused before a path is built from it', \Nino\Modules\Builder\Document::create( $appData, 'Third', 'html-headerx' )['status'] === 400 && \Nino\Modules\Builder\Document::create( $appData, 'Third', 'html-header-' )['status'] === 400 );

$long = \Nino\Modules\Builder\Document::create( $appData, 'Leistungen fuer kleine und mittlere Unternehmen in Muenchen 2026 und danach' );
check( 'a long name is cut at 60 characters, and where that is a hyphen the hyphen goes: the file is one the builder can open and delete', $long['status'] === 200
	&& $long['model']['file'] === 'page-leistungen-fuer-kleine-und-mittlere-unternehmen-in-muenchen' && \Nino\Modules\Builder\Document::load( $appData, $long['model']['file'] )['status'] === 200
	&& \Nino\Modules\Builder\Document::delete( $appData, $long['model']['file'] )['status'] === 200 && \Nino\Filesystem::fileExists( $appData, '/templates/'. $long['model']['file']. '.tpl' ) === false );
check( 'a name with brackets, which the kernel would resolve in the comment, is refused', \Nino\Modules\Builder\Document::create( $appData, 'Home [[/x]]' )['status'] === 400 && \Nino\Modules\Builder\Document::create( $appData, 'Home [x]' )['status'] === 400 );

$nameless = [ '', '   ', '---', '<b>x</b>', "two\nlines", 'a --> b', str_repeat( 'x', 161 ) ];
$accepted = array_filter( $nameless, static fn( string $name ): bool => \Nino\Modules\Builder\Document::create( $appData, $name )['status'] !== 400 );
check( 'a name that is empty, no more than hyphens, has angle brackets or a line break or is too long is refused', $accepted === [] );
check( 'a name with a slash or a dot makes a slug and no path', \Nino\Modules\Builder\Document::create( $appData, '../../Evil' )['model']['file'] === 'page-evil'
	&& \Nino\Filesystem::fileExists( $appData, '/templates/page-evil.tpl' ) === true );

$route = \Nino\Modules\Builder\Document::delete( $appData, 'page-home' );
check( 'a template that a route renders is not deleted: 409, with the routes', $route['status'] === 409 && $route['code'] === 'in-use' && $route['usedBy'] === [ [ 'route' => 'GET://', 'uri' => '/' ] ]
	&& is_file( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === true );

$deleted = \Nino\Modules\Builder\Document::delete( $appData, 'page-evil' );
check( 'one that no route renders is deleted - the file, and nothing else: its keys and slots stay', $deleted['status'] === 200 && is_file( \Nino\Filesystem::path( $appData, '/templates/page-evil.tpl' ) ) === false
	&& \Nino\Text::entry( $appData, '/template/page-home/start/title' ) !== null );
check( '...and a second time it is gone: 404', \Nino\Modules\Builder\Document::delete( $appData, 'page-evil' )['status'] === 404 );

echo "\n";


// --- 9. The page the builder wrote, rendered ---------------------------------

echo "The kernel renders what the builder wrote\n";

$final = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$page = \Nino\Html::renderHtml( $appData, '[template /templates/page-home]' );

check( 'the page renders through the kernel with no call of a component, a stack or a template left in it', preg_match( '/\[\/?(?:title|subtitle|text|image|button|html|spacer|stack|slider|filter|list|template|osm)[ \]]/', $page ) === 0
	&& preg_match( '/\[\[/', $page ) === 0 && $page !== '' );
check( '...the head and the foot are the frames it names', str_contains( $page, "\n<header>Short</header>\n" ) === true && str_ends_with( trim( $page ), '<footer>Footer</footer>' ) === true );
check( '...the components are the kernel\'s: the heading with its level and style, the button with its address, the texts of the keys', str_contains( $page, '<h1 class="nino-section-title nino-section-title--loud">Hello</h1>' ) === true
	&& str_contains( $page, '<a class="nino-btn nino-btn--primary" href="/contact">Contact us</a>' ) === true && str_contains( $page, '<h2 class="nino-section-title">Services</h2>' ) === true );
check( '...the stack is a loop of the elements, one cell each, in the order it names', preg_match_all( '/nino-grid-s-100 nino-grid-m-50 nino-grid-l-50 nino-autoheight/', $page ) === 2
	&& strpos( $page, 'App' ) < strpos( $page, 'Web' ) && str_contains( $page, 'Mehr</a>' ) === true );
check( '...and the sections are what Nino.css knows: the cover with its height, the background block, the row and its columns',
	str_contains( $page, '<section id="start" class="nino-section nino-section--fullwidth nino-section--black nino-cover nino-cover--dim nino-vpa" data-cover-height="100">' ) === true
	&& str_contains( $page, '<div class="nino-section-bg nino-img-focus--5">' ) === true && str_contains( $page, '<div class="nino-grid-row nino-grid-row--wide nino-grid-middle">' ) === true );
check( '...a block of html written by hand is in the page as it was written', str_contains( $page, '<p>Imprint note</p>' ) === true && str_contains( $page, '<div class="map"></div>' ) === true );
check( 'an empty page of the builder renders as its frames and nothing between', \Nino\Html::renderHtml( $appData, '[template /templates/page-ueber-uns]' ) === "<!-- nino:template-name Über uns -->\n<header>Header</header>\n\n<footer>Footer</footer>\n" );

echo "\n";


// --- 10. The panel's actions -------------------------------------------------

echo "Modules\\Builder\\Admin - the API\n";

/**
 *	Call one panel action the way the workbench does: a posted request with the
 *	action's json in it, answered into $request
 *
 *	@param		array 		&$appData			(reference) The sandbox's app data
 *	@param		string		$method				The Admin method to call
 *	@param		array			$data					What the screen posted
 *
 *	@return 	array										[ status, decoded body ]
 */
function callBuilderAction( array &$appData, string $method, array $data = [] ): array {

	$_POST['data'] = json_encode( $data );

	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];

	\Nino\Modules\Builder\Admin::$method( $appData, $request );

	$_POST = [];

	return [
		(int) ( $request['/nino/http/response']['statusCode'] ?? 0 ),
		is_array( $request['/nino/http/response']['body'] ?? null )
			? $request['/nino/http/response']['body']
			: json_decode( (string) ( $request['/nino/http/response']['body'] ?? '' ), true ),
	];
}

$actions = [ 'apiList', 'apiCreate', 'apiLoad', 'apiSave', 'apiDelete', 'apiSource', 'apiRegistry' ];

\Nino\Auth::logoutUser( $appData );
check( 'every action refuses a request with no session: 401', array_filter( $actions, static fn( string $action ): bool => callBuilderAction( $appData, $action )[0] !== 401 ) === [] );

\Nino\Auth::insertUser( $appData, 'elements@example.com', 'correct horse battery staple', [ '/_admin/elements/manage' ] );
\Nino\Auth::loginUser( $appData, 'elements@example.com', 'correct horse battery staple' );
check( '...and an account without the permission of the panel: 403', array_filter( $actions, static fn( string $action ): bool => callBuilderAction( $appData, $action )[0] !== 403 ) === [] );

\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

[ $status, $body ] = callBuilderAction( $appData, 'apiList' );
check( 'list answers the templates', $status === 200 && array_column( $body['templates'], 'file' ) === [ 'page-home', 'page-legacy', 'page-short', 'page-ueber-uns' ] && isset( $body['status'] ) === false );

[ $status, $body ] = callBuilderAction( $appData, 'apiRegistry' );
check( 'registry answers the registry', $status === 200 && isset( $body['components']['title'] ) === true && isset( $body['stacks']['stack'] ) === true && $body['types'][0]['uri'] === '/services' );

[ $status, $loadedBody ] = callBuilderAction( $appData, 'apiLoad', [ 'file' => 'page-home' ] );
check( 'load answers the model and the hash', $status === 200 && $loadedBody['model']['file'] === 'page-home' && $loadedBody['hash'] === $final['hash'] );
check( '...a name that is none: 400, a file that is not there: 404', callBuilderAction( $appData, 'apiLoad', [ 'file' => '../x' ] )[0] === 400 && callBuilderAction( $appData, 'apiLoad', [ 'file' => 'page-nope' ] )[0] === 404 );

[ $status, $createdBody ] = callBuilderAction( $appData, 'apiCreate', [ 'name' => 'Team' ] );
check( 'create answers the new model, and refuses a name that makes a file that is there with 409', $status === 200 && $createdBody['model']['file'] === 'page-team' && callBuilderAction( $appData, 'apiCreate', [ 'name' => 'team' ] )[0] === 409
	&& callBuilderAction( $appData, 'apiCreate', [ 'name' => '' ] )[0] === 400 );

$example = \Nino\Modules\Builder\Reader::read( (string) file_get_contents( __DIR__. '/fixtures/page-home.tpl' ), $registry );

[ $status, $sourceBody ] = callBuilderAction( $appData, 'apiSource', [ 'model' => $example ] );
check( 'source answers the file the model would be written as, and what the file is made of: the head, each block, the foot', $status === 200 && $sourceBody['source'] === (string) file_get_contents( __DIR__. '/fixtures/page-home.tpl' )
	&& array_column( $sourceBody['parts'], 'kind' ) === [ 'head', 'section', 'section', 'html', 'section', 'foot' ] && array_column( $sourceBody['parts'], 'block' ) === [ null, 0, 1, 2, 3, null ]
	&& implode( "\n\n", array_column( $sourceBody['parts'], 'source' ) ). "\n" === $sourceBody['source'] );
check( '...a block of html in its markers carries them, a section does not, and nothing was written', str_starts_with( $sourceBody['parts'][3]['source'], '<!-- nino:html -->' ) === true && str_starts_with( $sourceBody['parts'][1]['source'], '<section id="hero"' ) === true
	&& \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['hash'] === $loadedBody['hash'] );

$edited = $example;
$edited['blocks'][2] = [ 'kind' => 'html', 'source' => "<section id=\"map\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-home/map/title]\n\t\t</div>\n\t</div>\n</section>", 'reason' => null, 'edited' => true ];
[ $status, $editedBody ] = callBuilderAction( $appData, 'apiSource', [ 'model' => $edited ] );
check( '...an edited block that reads as a section is one in the model that comes back', $status === 200 && $editedBody['model']['blocks'][2]['kind'] === 'section' && $editedBody['model']['blocks'][2]['id'] === 'map'
	&& $editedBody['parts'][3]['kind'] === 'section' && str_starts_with( $editedBody['parts'][3]['source'], '<section id="map"' ) === true );

$edited['blocks'][2]['source'] = '<section id="map" class="nino-section"><div class="nino-grid-row"><div class="nino-grid-100">[title /template/page-home/map/title]</div></div><div class="nino-grid-row"></div></section>';
[ $status, $keptBody ] = callBuilderAction( $appData, 'apiSource', [ 'model' => $edited ] );
$edited['blocks'][2]['reason'] = [ 'line' => 0, 'code' => '', 'detail' => '', 'text' => '' ];
[ , $failedBody ] = callBuilderAction( $appData, 'apiSource', [ 'model' => $edited ] );
check( '...one that does not stays in its markers, and one that had a reason gets the reason it fails with now', $keptBody['model']['blocks'][2]['kind'] === 'html' && $keptBody['model']['blocks'][2]['reason'] === null
	&& $failedBody['model']['blocks'][2]['kind'] === 'html' && $failedBody['model']['blocks'][2]['reason']['code'] === 'second-row' && str_starts_with( $failedBody['parts'][3]['source'], '<section' ) === true );

$edited['blocks'][2] = [ 'kind' => 'html', 'source' => "<section id=\"map\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[button text=\"it's\" href=\"/x\"]\n\t\t</div>\n\t</div>\n</section>", 'reason' => null, 'edited' => true ];
[ $status, $valueBody ] = callBuilderAction( $appData, 'apiSource', [ 'model' => $edited ] );
check( '...an edited block that reads back as a section with a value the writer cannot write: 400, builder_value - the values are looked at after the blocks are read again, so it does not throw', $status === 400 && $valueBody['code'] === 'builder_value' );

check( '...a request with no model: 400, a model that is none: 400, a value no call can carry: 400',
	callBuilderAction( $appData, 'apiSource', [] )[0] === 400 && callBuilderAction( $appData, 'apiSource', [ 'model' => [ 'blocks' => 'x' ] ] )[0] === 400
	&& callBuilderAction( $appData, 'apiSource', [ 'model' => array_replace_recursive( $example, [ 'blocks' => [ 0 => [ 'cols' => [ 0 => [ 'components' => [ 0 => [ 'text' => 'a"b' ] ] ] ] ] ] ] ) ] )[0] === 400 );

$next = $loadedBody['model'];
$next['name'] = 'Start';
[ $status, $savedBody ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'model' => $next, 'hash' => $loadedBody['hash'] ] );
check( 'save answers the model as it reads back and the new hash', $status === 200 && $savedBody['model']['name'] === 'Start' && $savedBody['hash'] !== $loadedBody['hash'] );

[ $status, $conflictBody ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'model' => $next, 'hash' => $loadedBody['hash'] ] );
check( '...a hash that is not the file\'s any more: 409, with the code the panel looks at', $status === 409 && $conflictBody['code'] === 'builder_conflict' );
check( '...and with force it is written', callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'model' => $next, 'hash' => $loadedBody['hash'], 'force' => true ] )[0] === 200 );
check( '...a model that is no model: 400', callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'hash' => 'x' ] )[0] === 400 );

$invalid = $next;
$invalid['blocks'][0]['cols'][0]['components'][0]['name'] = 'nothing';
[ $status, $invalidBody ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'model' => $invalid, 'hash' => \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['hash'] ] );
check( '...an invalid one: 400, the problems in the params of the answer', $status === 400 && $invalidBody['code'] === 'builder_invalid' && isset( $invalidBody['params'][0] ) === true && str_contains( $invalidBody['params'][0], 'nothing' ) === true );

[ $status, $inUse ] = callBuilderAction( $appData, 'apiDelete', [ 'file' => 'page-home' ] );
check( 'delete refuses a template a route renders with 409, the key of the route in the params, as the list shows the address', $status === 409 && $inUse['code'] === 'builder_in_use' && $inUse['params'] === [ 'GET://' ] );
check( '...and deletes one that none does', callBuilderAction( $appData, 'apiDelete', [ 'file' => 'page-team' ] )[0] === 200 && callBuilderAction( $appData, 'apiDelete', [ 'file' => 'page-team' ] )[0] === 404 );

echo "\n";

// --- The browser half --------------------------------------------------------

echo "assets/admin.js - its own behaviour test\n";

$jsTest	= __DIR__. '/builder-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	// Not a failure and not a pass: say which, rather than counting a check
	// that never ran (Nino's AGENTS.md, section 10)
	echo "  --  - node is not available here: builder-js-smoke.js was NOT run\n";
} else {
	$output	= [];
	$status	= 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = trim( (string) ( $output === [] ? '' : end( $output ) ) );
	check( 'builder-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
