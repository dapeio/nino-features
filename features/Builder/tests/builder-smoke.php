<?php
declare(strict_types=1);

/**
 *	Nino
 *	builder-smoke.php		The Builder's server half over Nino's harness: the manifest
 *											and the panel, then the Reader and the Writer on the
 *											example page of the concept (tests/fixtures/page-home.tpl,
 *											the example in its canonical form but for the wrap, which a
 *											file the builder did not write has not: the concept's own,
 *											page-home-concept.tpl, says level="2" twice and gap="2"
 *											aloud, which are the defaults the Writer leaves out;
 *											page-home-wrap.tpl is the same in the wrap the builder
 *											writes, with a kind of animation, and page-home-vpa.tpl
 *											the same with the animation line an earlier builder wrote
 *											in the head, which reads as that wrap) and on a sheet of
 *											sections that each fail one rule, then
 *											the Document - listing, loading, saving with a hash, the
 *											keys and the slots a save makes, the refusals, the wrap
 *											and the animation of the template, a copy of a template
 *											with its keys and slots and what stops it - and last the
 *											page the builder wrote, rendered by the kernel. The panel's
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

check( 'the panel names every action it answers', array_keys( \Nino\Modules\Builder\Admin::actions() ) === [ 'builder/list', 'builder/create', 'builder/duplicate', 'builder/load', 'builder/save', 'builder/delete', 'builder/source', 'builder/registry' ] );
check( '...asks for its own permission, names the structure group in its nav() (a feature\'s panel sits in the group its nav() names) and is a workspace', \Nino\Modules\Builder\Admin::perm() === '/_admin/builder/manage'
	&& \Nino\Modules\Builder\Admin::nav()[3] === 'structure' && \Nino\Modules\Builder\Admin::layout() === 'workspace' );
check( '...and brings the HTML editor of the workbench, its own script and its stylesheet, which are there', count( \Nino\Modules\Builder\Admin::assets() ) === 3 && in_array( '/_admin/assets/html-editor.js', \Nino\Modules\Builder\Admin::assets(), true ) === true
	&& array_filter( [ 'assets/admin.js', 'assets/admin.css', 'templates/panel.tpl', 'text/en_US.php', 'text/de_DE.php' ], static fn( string $file ): bool => is_file( dirname( __DIR__ ). '/'. $file ) === false ) === [] );
check( '...whose words are the same keys in both languages', array_keys( (array) include $dir. '/text/en_US.php' ) === array_keys( (array) include $dir. '/text/de_DE.php' ) );
check( '...and the sentences that name the keys and the slots of a copy in the way have a place for the list, one each, in both', array_filter( [ 'en_US', 'de_DE' ], static function( string $locale ) use ( $dir ): bool {
	$texts = (array) include $dir. '/text/'. $locale. '.php';
	return substr_count( (string) ( $texts['[[/_admin/builder/error/keys-in-the-way]]'] ?? '' ), '%s' ) !== 1 || substr_count( (string) ( $texts['[[/_admin/builder/error/slots-in-the-way]]'] ?? '' ), '%s' ) !== 1;
} ) === [] );
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
check( 'the hero: its classes are settings', $hero['settings']['fullwidth'] === true && $hero['settings']['fullheight'] === false && $hero['settings']['color'] === 'black' && $hero['settings']['image'] === 'cover'
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

var_dump($services);
exit;
check( '...its components take a field of the type as their source, and a fixed text beside it',
	array_column( $services['cols'][1]['components'], 'source' ) === [ 'image', 'title', 'summary', '.uri' ] && $services['cols'][1]['components'][3]['text'] === 'Mehr' );

check( 'the block of html in its markers is a block of html without a reason, byte for byte',
	$model['blocks'][2]['reason'] === null && str_starts_with( $model['blocks'][2]['source'], '<section id="map"' ) && str_ends_with( $model['blocks'][2]['source'], '</section>' ) );
check( 'the contact: 100 wide in every viewport is the short class', $model['blocks'][3]['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 100 ] && $model['blocks'][3]['settings']['text'] === 'center' && $model['blocks'][3]['settings']['row'] === 'narrow' );

check( 'the model the panel\'s script test reads (fixtures/page-home.json) is what the Reader makes of the example page', json_decode( (string) file_get_contents( __DIR__. '/fixtures/page-home.json' ), true ) === array_replace( $model, [ 'file' => 'page-home' ] ) );

// The blocks stand in a wrap, between the head and the foot. A file the builder did not write has none, and is given it with the first change - two lines
$wrapped = static fn( string $source, string $class = 'nino-wrap' ): string => str_replace(
	[ "[template /templates/html-header]\n\n", "\n[template /templates/html-footer]\n" ],
	[ "[template /templates/html-header]\n<div class=\"". $class. "\">\n\n", "\n</div>\n[template /templates/html-footer]\n" ],
	$source
);

// The wrap of a page that has blocks and nothing else
$inWrap = static fn( string $blocks ): string => "<div class=\"nino-wrap\">\n\n". rtrim( $blocks ). "\n\n</div>\n";

// The line a reason names is a line of the file: read from the file the Writer wrapped, a model names each one further down by the lines the wrap put before the blocks
$shifted = static function( array $model, int $by ): array {

	foreach( $model['blocks'] as $index => $block )
		if( is_array( $block['reason'] ?? null ) === true )
			$model['blocks'][$index]['reason']['line'] += $by;

	return $model;
};

check( 'writing the model of the example page gives the example page in its wrap: two lines more, which the file had none of, and not a byte else', $write( $model ) === $wrapped( $fixture ) && $fixture !== $wrapped( $fixture )
	&& substr_count( $write( $model ), "\n" ) - substr_count( $fixture, "\n" ) === 2 );
check( '...and reading what was written gives the same model: a wrap with no class of its own says nothing that a file without one did not', $read( $write( $model ) ) === $model && $model['animate'] === false && $model['vpa'] === '' && $model['vpaSpeed'] === '' && $model['wrapClass'] === '' );
check( 'a load and a save without a change is the same file: Reader( Writer( Reader( x ) ) ) is Reader( x )', $read( $write( $read( $fixture ) ) ) === $read( $fixture ) );

// A page the builder did not write the way it would: the order of the attributes, a default said aloud, the short class, an empty alt
$hand = str_replace(
	[ '[title /template/page-home/hero/title level="1" style="loud"]', '[title /template/page-home/contact/title]', "\t\t<div class=\"nino-grid-100\">\n\t\t\t[title" ],
	[ '[title /template/page-home/hero/title style="loud" class="" level="1"]', '[title /template/page-home/contact/title level="2" style=""]', "\t\t<div class=\"nino-grid-s-100 nino-grid-m-100 nino-grid-l-100\">\n\t\t\t[title" ],
	$fixture
);
check( 'a page written by hand is read all the same, and written back in the canonical form, wrapped', $hand !== $fixture && $read( $hand ) === $model && $write( $read( $hand ) ) === $wrapped( $fixture ) );

// The wrap carries the animation of the template: whether its sections are animated, and what kind of animation it is - which every .nino-vpa below it inherits
$wrapFixture	= (string) file_get_contents( __DIR__. '/fixtures/page-home-wrap.tpl' );
$wrapModel		= $read( $wrapFixture );
$exampleBlocks	= $model['blocks'];

check( 'the wrap is read from its place - the line after the head, the line before the foot: its classes are the animation of the template, animated, an effect with its strength, a speed - and the page is what it was', $wrapModel['animate'] === true && $wrapModel['vpa'] === 'zoom-soft'
	&& $wrapModel['vpaSpeed'] === 'medium' && $wrapModel['wrapClass'] === '' && $wrapModel['name'] === 'Home' && $wrapModel['header'] === 'html-header' && $wrapModel['footer'] === 'html-footer' && $wrapModel['blocks'] === $model['blocks']
	&& array_keys( $wrapModel ) === [ 'file', 'name', 'animate', 'vpa', 'vpaSpeed', 'wrapClass', 'header', 'footer', 'blocks' ] && array_keys( $model ) === array_keys( $wrapModel ) );
check( '...written back byte for byte, the wrap hard at the frames, and Reader( Writer( Reader( x ) ) ) is Reader( x )', $write( $wrapModel ) === $wrapFixture && $read( $write( $wrapModel ) ) === $wrapModel );
check( '...and the file that is wrapped and the file that is not hold the same blocks, so that nothing but the two lines and their classes is the wrap\'s', $wrapFixture === $wrapped( $fixture, 'nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium' ) );

// Every switch, effect and speed: the classes are in one order, and what is written is read as it was
$wrong		= [];
$combined	= 0;

foreach( [ false, true ] as $animate )
	foreach( array_merge( [ '' ], \Nino\Modules\Builder\Reader::EFFECTS ) as $effect )
		foreach( array_merge( [ '' ], \Nino\Modules\Builder\Reader::SPEEDS ) as $speed ) {

			$combined++;
			$expected	= '<div class="nino-wrap'. ( $animate === true ? ' nino-wrap--vpa' : '' ). ( $effect === '' ? '' : ' nino-vpa--'. $effect ). ( $speed === '' ? '' : ' nino-vpa--speed-'. $speed ). "\">\n\n</div>\n";
			$written	= $write( [ 'animate' => $animate, 'vpa' => $effect, 'vpaSpeed' => $speed, 'blocks' => [] ] );
			$back			= $read( $written );

			if( $written !== $expected || [ $back['animate'], $back['vpa'], $back['vpaSpeed'], $back['wrapClass'] ] !== [ $animate, $effect, $speed, '' ] || $write( $back ) !== $written )
				$wrong[] = ( $animate === true ? 'on' : 'off' ). ' '. $effect. ' '. $speed;
		}

check( 'the switch, the effect with its strength and the speed are classes of the wrap, in that order: all '. $combined. ' combinations of them are written as they are, and read back as they were set'. ( $wrong === [] ? '' : ' - '. implode( ', ', array_slice( $wrong, 0, 5 ) ) ), $wrong === [] && $combined === 152 );

$foreign = $read( str_replace( 'class="nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium"', 'class="my-wrap nino-vpa--speed-slow nino-wrap nino-vpa--blur-hard u-x nino-wrap--vpa nino-vpa--flip-soft nino-vpa--repeat nino-vpa"', $wrapFixture ) );
check( 'a class of the wrap that the builder does not know is carried in wrapClass - a second effect, the mode and a bare nino-vpa among them - and written behind the known ones', $foreign['animate'] === true && $foreign['vpa'] === 'blur-hard' && $foreign['vpaSpeed'] === 'slow'
	&& $foreign['wrapClass'] === 'my-wrap u-x nino-vpa--flip-soft nino-vpa--repeat nino-vpa' && $foreign['blocks'] === $model['blocks'] );
check( '...they survive a round trip: written in the order of the known ones and then theirs, read as they were', str_contains( $write( $foreign ), '<div class="nino-wrap nino-wrap--vpa nino-vpa--blur-hard nino-vpa--speed-slow my-wrap u-x nino-vpa--flip-soft nino-vpa--repeat nino-vpa">' ) === true
	&& $read( $write( $foreign ) ) === $foreign && $write( $read( $write( $foreign ) ) ) === $write( $foreign ) );

// A wrap is known by its place alone: a line that opens it and one that closes it, each of nothing else
$open		= '<div class="nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium">';
$noClose	= $read( str_replace( "\n</div>\n[template /templates/html-footer]\n", "\n[template /templates/html-footer]\n", $wrapFixture ) );
$noOpen		= $read( str_replace( $open. "\n\n", '', $wrapFixture ) );

check( 'a line that opens a wrap and none that closes it is no wrap: the file is read as one that has none, the line is a block of html of its own with the reason it is no section, and the sections after it are read',
	$noClose['animate'] === false && $noClose['vpa'] === '' && $noClose['wrapClass'] === '' && $noClose['blocks'][0]['kind'] === 'html' && $noClose['blocks'][0]['source'] === $open && is_array( $noClose['blocks'][0]['reason'] ) === true && $noClose['blocks'][0]['reason']['line'] === 3
	&& array_column( array_slice( $noClose['blocks'], 1 ), 'kind' ) === [ 'section', 'section', 'html', 'section' ] );
check( '...so is a line that closes it and none that opens it: a block of html at the end', $noOpen['animate'] === false && array_column( $noOpen['blocks'], 'kind' ) === [ 'section', 'section', 'html', 'section', 'html' ] && $noOpen['blocks'][4]['source'] === '</div>' && $noOpen['footer'] === 'html-footer' );
check( '...and each is written where it stands, among the blocks of html, byte for byte - what the file has it keeps until the wrap is meant to', str_contains( $write( $noClose ), "[template /templates/html-header]\n<div class=\"nino-wrap\">\n\n". $open. "\n\n<section id=\"hero\"" ) === true
	&& str_ends_with( $write( $noOpen ), "\n\n</div>\n\n</div>\n[template /templates/html-footer]\n" ) === true );

$apart = static fn( string $head, string $first ): array => $read( "[template /templates/html-header]\n". $head. $first. "\n". $section( '[title /template/page-x/a/title]' ). "\n</div>\n[template /templates/html-footer]\n" );
check( 'a line between the head and the opening line that is none of the wrap\'s - a section, a comment - or an opening tag that has an attribute besides its class is no wrap either, whatever closes the file',
	$apart( "<!-- hello -->\n", '<div class="nino-wrap">' )['wrapClass'] === '' && $apart( '', '<div class="nino-wrap" id="page">' )['blocks'][0]['source'] === '<div class="nino-wrap" id="page">' && $apart( '', '<div class="nino-wrap" id="page">' )['animate'] === false
	&& $apart( '', '<div class="nino-wrap nino-wrap--vpa">' )['animate'] === true && $apart( '', '<div class="my-wrap">' )['blocks'][0]['source'] === '<div class="my-wrap">' && $apart( '', '<div class="nino-wrap nino-wrap--vpa">x' )['animate'] === false );

$handWrapped = $read( "[template /templates/html-header]\n\n\n\t<div class=\"nino-wrap  nino-wrap--vpa \">  \n". $section( '[title /template/page-x/a/title]' ). "\n\n\t</div>\n\n[template /templates/html-footer]\n" );
check( 'the lines of a wrap written by hand may be indented, have blank lines around them and spaces behind them - and a class the builder cannot keep, a quote or an ampersand, makes it no wrap', $handWrapped['animate'] === true && array_column( $handWrapped['blocks'], 'kind' ) === [ 'section' ]
	&& $read( "<div class=\"nino-wrap a&b\">\n". $section( '[title /template/page-x/a/title]' ). "\n</div>\n" )['blocks'][0]['kind'] === 'html' && $read( "<div class=\"nino-wrap\">\r\n". $section( '[title /template/page-x/a/title]' ). "\r\n</div>\r\n" )['blocks'][0]['kind'] === 'section' );

// What an earlier builder wrote in the head of a page: its animation, as a line of classes - read in the place of a wrap, and never written again
$vpaFixture	= (string) file_get_contents( __DIR__. '/fixtures/page-home-vpa.tpl' );
$vpaModel		= $read( $vpaFixture );

check( 'the head line of an earlier builder is read as the wrap says it - animated, the effect with its strength, the speed - so the page is the page of the wrap', $vpaModel === $wrapModel && $vpaModel['vpa'] === 'zoom-soft' && $vpaModel['vpaSpeed'] === 'medium' );
check( '...and it is not written again: the page is written in the wrap that says the same, a head line is not made, and what is written is read as it was', $write( $vpaModel ) === $wrapFixture && str_contains( $write( $vpaModel ), 'nino:template-vpa' ) === false && $read( $write( $vpaModel ) ) === $vpaModel );

$headLine = static fn( string $classes ): array => $read( str_replace( 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium', $classes, $vpaFixture ) );
$cases		= [ 'off' => [ false, '', '', '' ], 'nino-vpa' => [ true, '', '', '' ], 'nino-vpa nino-vpa--blur-hard nino-vpa--repeat' => [ true, 'blur-hard', '', '' ], 'nino-vpa--speed-fast nino-vpa--slide-left-soft' => [ true, 'slide-left-soft', 'fast', '' ],
	'nino-vpa--visible' => [ true, '', '', '' ], 'nino-vpa--visible-once nino-vpa--zoom-out-hard nino-vpa--speed-slow' => [ true, 'zoom-out-hard', 'slow', '' ], 'nino-vpa nino-vpa--zoom-soft nino-vpa--flip-soft nino-vpa--foo' => [ true, 'zoom-soft', '', 'nino-vpa--flip-soft nino-vpa--foo' ] ];
$unread		= [];

foreach( $cases as $classes => $expected ) {

	$got = $headLine( $classes );

	if( [ $got['animate'], $got['vpa'], $got['vpaSpeed'], $got['wrapClass'] ] !== $expected || $got['blocks'] !== $model['blocks'] )
		$unread[] = $classes;
}

check( 'a head line says off, or names classes: off is no animation, a class is - the effect, the strength and the speed are the wrap\'s, a mode is dropped for it is not inherited, and a class of nino-vpa-- that Nino.css has none of stays among the other classes of the wrap'
	. ( $unread === [] ? '' : ' - '. implode( ', ', $unread ) ), $unread === [] );
check( 'a file that has the line and a wrap is read by its wrap: the line says nothing then, and is gone with the next write', ( static function() use ( $read, $write, $wrapFixture, $wrapped ): bool {
	$both = $read( str_replace( "<!-- nino:template-name Home -->\n", "<!-- nino:template-name Home -->\n<!-- nino:template-vpa nino-vpa nino-vpa--blur-hard -->\n", $wrapFixture ) );
	return $both['vpa'] === 'zoom-soft' && $both['vpaSpeed'] === 'medium' && $write( $both ) === $wrapFixture;
} )() );

$foreignVpa = $read( str_replace( 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium', 'zoom-soft', $vpaFixture ) );
check( 'a line that names no class of the animation is no head line: it is left where it is, in the block that follows, and nothing is lost', $foreignVpa['animate'] === false && $foreignVpa['blocks'][0]['kind'] === 'html'
	&& str_contains( $foreignVpa['blocks'][0]['source'], '<!-- nino:template-vpa zoom-soft -->' ) === true );
check( 'the line is read without a name before it, and with the frames after it', $read( "<!-- nino:template-vpa nino-vpa--blur-soft -->\n[template /templates/html-header]\n" )['vpa'] === 'blur-soft'
	&& $read( "<!-- nino:template-vpa nino-vpa--blur-soft -->\n[template /templates/html-header]\n" )['header'] === 'html-header' && $read( "<!-- nino:template-vpa nino-vpa--blur-soft -->\n[template /templates/html-header]\n" )['animate'] === true );

$thrownVpa = 0;
foreach( [ 'zoom', 'nino-vpa--zoom-soft', 'zoom-soft nino-vpa', "zoom-soft\n", 'ZOOM-SOFT', 'x" onclick="y', 'repeat' ] as $bad ) {
	try {
		$write( [ 'vpa' => $bad, 'blocks' => [] ] );
	}
	catch( \UnexpectedValueException ) {
		$thrownVpa++;
	}
}
foreach( [ 'quick', 'speed-fast', 'FAST', "slow\n" ] as $bad ) {
	try {
		$write( [ 'vpaSpeed' => $bad, 'blocks' => [] ] );
	}
	catch( \UnexpectedValueException ) {
		$thrownVpa++;
	}
}
check( 'the Writer takes no effect and no speed of the wrap that Nino.css has none of - it throws, so no class is made that is none of the wrap\'s', $thrownVpa === 11 );

$concept = (string) file_get_contents( __DIR__. '/fixtures/page-home-concept.tpl' );
check( 'the example as the concept prints it, with the three defaults said aloud, reads as the same model and is written in the canonical form', $concept !== $fixture && $read( $concept ) === $model && $write( $read( $concept ) ) === $wrapped( $fixture ) );

check( 'a file with no head and no frame is read, and written as it is in its wrap: nothing else is added', $write( $read( $section( '[title /template/page-x/a/title]' )."\n" ) ) === "<div class=\"nino-wrap\">\n\n". $section( '[title /template/page-x/a/title]' ). "\n\n</div>\n" );
check( 'an empty file is a page of no blocks, written as its wrap and nothing in it', $read( '' )['blocks'] === [] && $write( $read( '' ) ) === "<div class=\"nino-wrap\">\n\n</div>\n" && $read( $write( $read( '' ) ) ) === $read( '' ) );
check( 'a frame the Document would refuse on a save - html-headerx, html-footer- - is no frame to the Reader either: the line stays a block of the page', $read( "[template /templates/html-headerx]\n\n". $section( '[title /template/page-x/a/title]' ). "\n" )['header'] === ''
	&& $read( $section( '[title /template/page-x/a/title]' ). "\n\n[template /templates/html-footer-]\n" )['footer'] === '' );
check( 'a page of a name and two frames and nothing in between', $write( [ 'name' => 'Empty', 'header' => 'html-header', 'footer' => 'html-footer', 'blocks' => [] ] ) === "<!-- nino:template-name Empty -->\n[template /templates/html-header]\n<div class=\"nino-wrap\">\n\n</div>\n[template /templates/html-footer]\n" );

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
check( '...and is written back byte for byte, in the wrap, without markers added', $write( $read( $hand ) ) === $inWrap( $hand ) && str_contains( $write( $read( $hand ) ), 'nino:html' ) === false );
check( '...and the whole file is read again as it was, but for the lines its reasons name: each two further down, for the line that opens the wrap and the blank line after it, which a file without a head had not - and the page the builder wrote is the same again, whole',
	$read( $write( $read( $hand ) ) ) === $shifted( $read( $hand ), 2 ) && $read( $write( $read( $write( $read( $hand ) ) ) ) ) === $read( $write( $read( $hand ) ) ) );

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
check( 'a block in its markers has no reason, whatever it holds, and keeps them when written', $read( $marked )['blocks'][0] === [ 'kind' => 'html', 'source' => '<p>Made by the builder</p>', 'reason' => null ] && $write( $read( $marked ) ) === $inWrap( $marked ) );
check( 'a block the panel made - a reason of null - is written with markers', \Nino\Modules\Builder\Writer::block( [ 'kind' => 'html', 'source' => '<p>New</p>', 'reason' => null ], $registry ) === "<!-- nino:html -->\n<p>New</p>\n<!-- /nino:html -->"
	&& $write( [ 'blocks' => [ [ 'kind' => 'html', 'source' => '<p>New</p>', 'reason' => null ] ] ] ) === $inWrap( "<!-- nino:html -->\n<p>New</p>\n<!-- /nino:html -->" ) );
check( 'a block that failed - a reason - is written without', \Nino\Modules\Builder\Writer::block( [ 'kind' => 'html', 'source' => '<p>Old</p>', 'reason' => [ 'line' => 1, 'code' => 'not-a-section', 'detail' => '', 'text' => '' ] ], $registry ) === '<p>Old</p>'
	&& $write( [ 'blocks' => [ [ 'kind' => 'html', 'source' => '<p>Old</p>', 'reason' => [ 'line' => 1, 'code' => 'not-a-section', 'detail' => '', 'text' => '' ] ] ] ] ) === $inWrap( '<p>Old</p>' ) );

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
	'row' => 'narrow', 'rowAlign' => 'bottom', 'fullwidth' => true, 'fullheight' => true, 'color' => 'tint', 'border' => 'primary', 'image' => 'parallax', 'dim' => true,
	'mt' => '1', 'mb' => '0', 'pt' => '3', 'pb' => '6', 'text' => 'right', 'vpa' => 'zoom-soft', 'vpaSpeed' => 'slow', 'vpaMode' => 'repeat', 'vpaDelay' => '200ms', 'vpaDuration' => '1.5s',
	'custom' => 'my-section other', 'rowCustom' => 'my-row',
] + $blank['settings'];
$styled['cols'][0] = [ 'width' => [ 's' => 100, 'm' => 50, 'l' => 33 ], 'hidden' => [ 's' => true ], 'text' => 'center', 'stackAlign' => 'end', 'stackGap' => '4', 'vpa' => '', 'custom' => 'my-col' ] + $blank['cols'][0];
$styled['background'] = [ 'slot' => '/template/page-x/a/background', 'focus' => null ];
$once = $write( [ 'blocks' => [ $styled ] ] );

check( 'a section\'s settings are its classes, in one order: the full width, the full height, the colour, the border, the image, the spacing, the text, the animation, the custom ones',
	str_contains( $once, '<section id="a" class="nino-section nino-section--fullwidth nino-section--fullheight nino-section--tint nino-section--border-primary nino-parallex nino-parallex--dim nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-text-right nino-vpa nino-vpa--zoom-soft nino-vpa--speed-slow nino-vpa--repeat my-section other" data-vpa-delay="200ms" data-vpa-duration="1.5s">' ) === true );
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

// Full width and full height are two switches of a section, not one choice: a class each, side by side, and neither is a custom class
$fullOf			= static fn( array $block ): array => [ $block['settings']['fullwidth'], $block['settings']['fullheight'], $block['settings']['custom'] ];
$fullBoth		= $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-section--fullwidth nino-section--fullheight nino-section--tint' ) );
$fullModel		= $read( $fullBoth )['blocks'][0];

check( 'a section with nino-section--fullwidth and nino-section--fullheight reads with both switches on, and neither class falls into the custom ones - the width of the model is gone', $fullOf( $fullModel ) === [ true, true, '' ]
	&& $fullModel['settings']['color'] === 'tint' && array_key_exists( 'width', $fullModel['settings'] ) === false
	&& array_slice( array_keys( $fullModel['settings'] ), 0, 6 ) === [ 'row', 'rowAlign', 'rowCustom', 'fullwidth', 'fullheight', 'color' ] );
check( '...and is written back byte for byte: the full width, then the full height, in the place the width had - behind nino-section, before the colour', $write( $read( $fullBoth ) ) === $inWrap( $fullBoth ) );

$fullSwapped = $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-section--tint nino-section--fullheight nino-section--fullwidth' ) );
check( '...whatever order the file has them in: it is the same section, written in the one order of the Writer, and what is written reads as it was', $read( $fullSwapped )['blocks'][0] === $fullModel
	&& $write( $read( $fullSwapped ) ) === $inWrap( $fullBoth ) && $read( $write( $read( $fullSwapped ) ) ) === $read( $fullSwapped ) );

$fullCases = [
	'neither'				=> [ false, false, 'nino-section nino-section--tint' ],
	'the full width alone'	=> [ true, false, 'nino-section nino-section--fullwidth nino-section--tint' ],
	'the full height alone'	=> [ false, true, 'nino-section nino-section--fullheight nino-section--tint' ],
	'both'					=> [ true, true, 'nino-section nino-section--fullwidth nino-section--fullheight nino-section--tint' ],
];
$fullWrong = [];

foreach( $fullCases as $label => [ $fullwidth, $fullheight, $classes ] ) {

	$source		= $page( $section( '[title /template/page-x/a/title]', 'a', $classes ) );
	$fullMade	= $blank;
	$fullMade['settings']['fullwidth']	= $fullwidth;
	$fullMade['settings']['fullheight']	= $fullheight;
	$fullMade['settings']['color']		= 'tint';

	// Read from the classes, and written back as they were; written from the switches, and read back as they were set
	if( $fullOf( $read( $source )['blocks'][0] ) !== [ $fullwidth, $fullheight, '' ] || $write( $read( $source ) ) !== $inWrap( $source )
		|| $write( [ 'blocks' => [ $fullMade ] ] ) !== $inWrap( $source ) || $read( $write( [ 'blocks' => [ $fullMade ] ] ) )['blocks'][0] !== $fullMade )
		$fullWrong[] = $label;
}

check( 'each switch alone, neither and both: '. count( $fullCases ). ' combinations are read from their classes, written back byte for byte, and written from the switches and read back as set'. ( $fullWrong === [] ? '' : ' - '. implode( ', ', $fullWrong ) ), $fullWrong === [] );

$fullLeft = $blank;
unset( $fullLeft['settings']['fullwidth'], $fullLeft['settings']['fullheight'] );
check( 'a switch the model leaves out is off: the Writer takes its default and writes neither class', str_contains( $write( [ 'blocks' => [ $fullLeft ] ] ), '<section id="a" class="nino-section">' ) === true
	&& $blank['settings']['fullwidth'] === false && $blank['settings']['fullheight'] === false && \Nino\Modules\Builder\Reader::sectionDefaults()['fullwidth'] === false && \Nino\Modules\Builder\Reader::sectionDefaults()['fullheight'] === false );

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
check( '...and the animation of the template as its wrap says it, which a file that has no wrap has off, with no kind and no class of its own', $byFile['page-home']['animate'] === false && $byFile['page-home']['vpa'] === '' && $byFile['page-home']['vpaSpeed'] === '' && $byFile['page-home']['wrapClass'] === ''
	&& array_keys( $byFile['page-home'] ) === [ 'file', 'name', 'animate', 'vpa', 'vpaSpeed', 'wrapClass', 'header', 'footer', 'sections', 'foreign', 'readable', 'reason', 'editable', 'usedBy' ] );
check( '...a file the builder does not read is one block of html that failed, and listed as not read completely', $byFile['page-legacy']['sections'] === 0 && $byFile['page-legacy']['foreign'] === 1
	&& $byFile['page-legacy']['readable'] === false && $byFile['page-legacy']['footer'] === 'html-footer' );
check( '...and the routes that name it', $byFile['page-home']['usedBy'] === [ [ 'route' => 'GET://', 'uri' => '/' ] ] && $byFile['page-legacy']['usedBy'] === [] );

check( '...a template the Reader does not read completely says why in the list - the reason of the first block it failed at - and one it reads says none', $byFile['page-legacy']['reason']['code'] === 'not-a-section'
	&& $byFile['page-legacy']['reason']['line'] === 1 && $byFile['page-home']['reason'] === null );

\Nino\Filesystem::putFileContent( $appData, '/templates/page-zzz.tpl', "<!-- nino:template-name Last -->\n" );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-aaa.tpl', "<!-- nino:template-name First -->\n<div class=\"nino-wrap nino-wrap--vpa nino-vpa--blur-soft nino-vpa--speed-fast my-wrap\">\n\n</div>\n" );
$more = array_column( \Nino\Modules\Builder\Document::list( $appData )['templates'], null, 'file' );
unlink( \Nino\Filesystem::path( $appData, '/templates/page-zzz.tpl' ) );
unlink( \Nino\Filesystem::path( $appData, '/templates/page-aaa.tpl' ) );
check( '...and the list is sorted by file, whatever order the files were made in', array_keys( $more ) === [ 'page-aaa', 'page-home', 'page-legacy', 'page-zzz' ] );
check( '...a template in a wrap is listed with the animation the classes of the wrap say: on, the effect with its strength, the speed, and the classes of its own', $more['page-aaa']['animate'] === true && $more['page-aaa']['vpa'] === 'blur-soft'
	&& $more['page-aaa']['vpaSpeed'] === 'fast' && $more['page-aaa']['wrapClass'] === 'my-wrap' && $more['page-aaa']['sections'] === 0 && $more['page-aaa']['foreign'] === 0 && $more['page-aaa']['readable'] === true && $more['page-zzz']['animate'] === false );

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
check( 'a field name where there is no stack means nothing, and is a problem', $stackless['status'] === 400 && str_contains( implode( ' ', $stackless['problems'] ), 'a column without a loop' ) === true );

$typeless = $loaded['model'];
$typeless['blocks'][1]['cols'][1]['stack']['source'] = '/nothing';
check( 'a stack of a type the Elements panel does not know is a problem', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $typeless, $loaded['hash'] )['problems'] ), '"nothing", which is no element type' ) === true );

$fieldless = $loaded['model'];
$fieldless['blocks'][1]['cols'][1]['components'][1]['source'] = 'nope';
check( '...and a field the type does not have', str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home', $fieldless, $loaded['hash'] )['problems'] ), 'is no field of the loop\'s type' ) === true );

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
	'settings that are a text'												=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings'] = 'x'; } ),
	'a full width that is a text'											=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['fullwidth'] = 'true'; } ),
	'a full height that is a number'										=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['fullheight'] = 1; } ),
	'a column that is a string'												=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0] = 'x'; } ),
];
$typed = array_filter( $answers, static fn( array $answer ): bool => $answer['status'] !== 400 || $answer['code'] !== 'invalid' );
check( 'a model of the wrong shape is refused with 400 and a problem, none of '. count( $answers ). ' ends in an error: '. implode( ', ', array_keys( $answers ) ), $typed === [] && ninoWarnings() === [] );

// A switch of a section is on or off: anything else would be left out of the classes and read back as off, so it is refused before anything is made
$switchBad			= [ 'a text' => 'true', 'the old choice' => 'fullwidth', 'an empty text' => '', 'a number' => 1, 'zero' => 0, 'a list' => [ true ], 'an empty list' => [] ];
$switchRefused	= [];

foreach( [ 0, 3 ] as $at )
	foreach( [ 'fullwidth', 'fullheight' ] as $key )
		foreach( $switchBad as $what => $value ) {

			$model = $loaded['model'];
			$model['blocks'][$at]['settings'][$key] = $value;
			$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

			if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ 'the '. $key. ' of block '. ( $at + 1 ). ' is neither on nor off' ]
				|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
				$switchRefused[] = $key. ' of block '. ( $at + 1 ). ' as '. $what;
		}

check( 'a full width or a full height that is no bool - a text, a number, a list - is refused with 400 (code invalid), the switch and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $switchBad ) * 4 . ' cases'. ( $switchRefused === [] ? '' : ' - '. implode( ', ', $switchRefused ) ), $switchRefused === [] );

$switchLeft = $loaded['model'];
unset( $switchLeft['blocks'][0]['settings']['fullwidth'], $switchLeft['blocks'][0]['settings']['fullheight'] );
$switchSource = \Nino\Modules\Builder\Document::source( $appData, $switchLeft );
check( '...and a switch left out is off, no problem: the section is written without either class', $switchSource['status'] === 200 && str_contains( $switchSource['parts'][1]['source'], '<section id="start" class="nino-section nino-section--black ' ) === true
	&& str_contains( $switchSource['parts'][1]['source'], 'nino-section--full' ) === false );

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
check( '...its sections are not animated, with no kind of animation and no class of the wrap of its own', $created['model']['animate'] === false && $created['model']['vpa'] === '' && $created['model']['vpaSpeed'] === '' && $created['model']['wrapClass'] === '' );
check( '...it is the empty canonical page, in its wrap, written, and read back as the model', file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-ueber-uns.tpl' ) ) === "<!-- nino:template-name Über uns -->\n[template /templates/html-header]\n<div class=\"nino-wrap\">\n\n</div>\n[template /templates/html-footer]\n"
	&& $created['hash'] === hash( 'sha256', (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-ueber-uns.tpl' ) ) )
	&& \Nino\Modules\Builder\Document::load( $appData, 'page-ueber-uns' )['model'] === $created['model'] );
check( '...a name that gives a file that is there is refused with 409, and the file is not touched', \Nino\Modules\Builder\Document::create( $appData, 'ÜBER Uns!' )['status'] === 409
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-ueber-uns.tpl' ) ), 'Über uns' ) === true );

$short = \Nino\Modules\Builder\Document::create( $appData, 'Short', 'html-header-short', '' );
check( 'the frames can be named, or none', $short['status'] === 200 && $short['model']['header'] === 'html-header-short' && $short['model']['footer'] === ''
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-short.tpl' ) ) === "<!-- nino:template-name Short -->\n[template /templates/html-header-short]\n<div class=\"nino-wrap\">\n\n</div>\n" );
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


// --- 9. The wrap and the animation of the template, and a copy --------------

echo "Document - the wrap and the animation of the template, and duplicate\n";

$wrapPath = \Nino\Filesystem::path( $appData, '/templates/page-wrap.tpl' );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-wrap.tpl', $wrapFixture );
\Nino\Filesystem::putFileContent( $appData, '/templates/html-header.tpl', '<header>Header</header>' );
$wrapLoad = \Nino\Modules\Builder\Document::load( $appData, 'page-wrap' );
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapLoad['model'], $wrapLoad['hash'] );
check( 'a template with a wrap is loaded and saved without a change byte for byte: the wrap is the model\'s animate, vpa and vpaSpeed', $wrapLoad['model']['animate'] === true && $wrapLoad['model']['vpa'] === 'zoom-soft' && $wrapLoad['model']['vpaSpeed'] === 'medium'
	&& $wrapSave['status'] === 200 && file_get_contents( $wrapPath ) === $wrapFixture && $wrapSave['model'] === $wrapLoad['model'] && $wrapSave['hash'] === $wrapLoad['hash'] );

$wrapEdit = $wrapLoad['model'];
$wrapEdit['vpa'] = 'blur-hard';
$wrapEdit['vpaSpeed'] = 'slow';
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapLoad['hash'] );
check( 'a new kind of animation is a new set of classes on the wrap, in the one place - the effect with its strength, then the speed - and the answer is the file read again', $wrapSave['status'] === 200
	&& file_get_contents( $wrapPath ) === str_replace( 'nino-vpa--zoom-soft nino-vpa--speed-medium', 'nino-vpa--blur-hard nino-vpa--speed-slow', $wrapFixture ) && $wrapSave['model']['vpa'] === 'blur-hard' && $wrapSave['model']['vpaSpeed'] === 'slow' && $wrapSave['model']['blocks'] === $exampleBlocks );

$wrapEdit = $wrapSave['model'];
$wrapEdit['animate'] = false;
$wrapEdit['vpa'] = '';
$wrapEdit['vpaSpeed'] = '';
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
check( '...the switch off and no kind is the wrap with nothing on it, and the rest of the file is what it was: nothing of the animation is left in the head, the sections keep their own', $wrapSave['status'] === 200 && (string) file_get_contents( $wrapPath ) === $wrapped( $fixture )
	&& $wrapSave['model'] === [ 'file' => 'page-wrap', 'name' => 'Home' ] + \Nino\Modules\Builder\Reader::wrapDefaults() + [ 'header' => 'html-header', 'footer' => 'html-footer', 'blocks' => $exampleBlocks ] );

// What the panel does where the switch is turned: the sections that follow it are changed in the model, the ones with a kind of their own are not
$follow = static function( array $model, bool $animate ): array {

	$model['animate'] = $animate;

	foreach( $model['blocks'] as $index => $block )
		if( ( $block['kind'] ?? '' ) === 'section' && $block['settings']['vpa'] === ( $animate === true ? null : '' ) && $block['settings']['vpaSpeed'] === '' && $block['settings']['vpaMode'] === '' && $block['settings']['vpaDelay'] === '' && $block['settings']['vpaDuration'] === '' )
			$model['blocks'][$index]['settings']['vpa'] = $animate === true ? '' : null;

	return $model;
};

$own = $wrapLoad['model'];
$own['blocks'][3]['settings']['vpa'] = 'flip-hard';
$own['blocks'][3]['settings']['vpaSpeed'] = 'fast';
$wrapEdit = $follow( $own, false );
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
$wrapText = (string) file_get_contents( $wrapPath );
check( 'the sections that follow the switch lose their bare nino-vpa where it is turned off, the one with a kind of its own does not - and the wrap loses nino-wrap--vpa, and keeps its kind', $wrapSave['status'] === 200 && str_contains( $wrapText, '<div class="nino-wrap nino-vpa--zoom-soft nino-vpa--speed-medium">' ) === true
	&& str_contains( $wrapText, '<section id="hero" class="nino-section nino-section--fullwidth nino-section--black nino-cover nino-cover--dim" data-cover-height="100">' ) === true && str_contains( $wrapText, '<section id="services" class="nino-section">' ) === true
	&& str_contains( $wrapText, '<section id="contact" class="nino-section nino-section--primary nino-text-center nino-vpa nino-vpa--flip-hard nino-vpa--speed-fast">' ) === true && $wrapSave['model']['animate'] === false && $wrapSave['model']['blocks'][3]['settings']['vpa'] === 'flip-hard' );

$wrapEdit = $follow( $wrapSave['model'], true );
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
$wrapText = (string) file_get_contents( $wrapPath );
check( '...and where it is turned on again every section without an animation is given the bare nino-vpa, and the one with its own is left as it was', $wrapSave['status'] === 200 && str_contains( $wrapText, '<div class="nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium">' ) === true
	&& str_contains( $wrapText, 'nino-cover nino-cover--dim nino-vpa" data-cover-height="100">' ) === true && str_contains( $wrapText, '<section id="services" class="nino-section nino-vpa">' ) === true
	&& str_contains( $wrapText, 'nino-text-center nino-vpa nino-vpa--flip-hard nino-vpa--speed-fast">' ) === true && $wrapSave['model'] === $wrapEdit );

$wrapBefore	= (string) file_get_contents( $wrapPath );
$wrapRefused	= [];
$wrapBad		= [
	'vpa'					=> [ 'zoom', 'nino-vpa--zoom-soft', 'x" onclick="y', 'ZOOM-SOFT', 'zoom-soft nino-vpa--speed-fast', "zoom-soft\n" ],
	'vpaSpeed'		=> [ 'quick', 'nino-vpa--speed-fast', 'speed-fast', 'FAST' ],
	'wrapClass'		=> [ 'a" onmouseover="b', "it's", 'a]b', 'a[b', "a\nb", "a\rb" ],
];

foreach( $wrapBad as $key => $values )
	foreach( $values as $value ) {
		$wrapEdit = $wrapSave['model'];
		$wrapEdit[$key] = $value;
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
		if( $answer['status'] !== 400 || $answer['problems'] === [] || file_get_contents( $wrapPath ) !== $wrapBefore )
			$wrapRefused[] = $key. ' '. json_encode( $value );
	}

check( 'an effect or a speed that Nino.css has none of, and a class of the wrap with a quote, a bracket or a line break in it, are refused with 400 and nothing is written: '. count( $wrapRefused ). ' of '. array_sum( array_map( 'count', $wrapBad ) ). ' were accepted'. ( $wrapRefused === [] ? '' : ' - '. implode( ', ', $wrapRefused ) ), $wrapRefused === [] );

$wrapEdit = $wrapSave['model'];
$wrapEdit['vpa'] = [ 'zoom-soft' ];
$wrapAnswers = [ \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] ) ];
$wrapEdit['vpa'] = 'zoom-soft';
$wrapEdit['animate'] = 'yes';
$wrapAnswers[] = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
$wrapEdit['animate'] = true;
$wrapEdit['wrapClass'] = 0;
$wrapAnswers[] = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
check( '...so is a model whose switch is no switch and whose effect or classes are no text: 400, a problem of the shape, and nothing is written', array_column( $wrapAnswers, 'status' ) === [ 400, 400, 400 ] && array_column( $wrapAnswers, 'code' ) === [ 'invalid', 'invalid', 'invalid' ] && file_get_contents( $wrapPath ) === $wrapBefore );

$wrapEdit = $wrapSave['model'];
$wrapEdit['wrapClass'] = 'my-wrap u-x:y nino-vpa--blur-soft';
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
check( 'a class of the wrap that is the template\'s own is kept after the others and read back as it was - a second effect among them, which is a class like any other where the first is the effect', $wrapSave['status'] === 200
	&& str_contains( (string) file_get_contents( $wrapPath ), '<div class="nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium my-wrap u-x:y nino-vpa--blur-soft">' ) === true && $wrapSave['model']['wrapClass'] === 'my-wrap u-x:y nino-vpa--blur-soft' );

$wrapBefore	= (string) file_get_contents( $wrapPath );
$wrapRefused	= [];

foreach( [ [ 'nino-wrap--vpa', false, '', '' ], [ 'nino-vpa--blur-soft', true, '', '' ], [ 'nino-vpa--speed-slow', true, 'zoom-soft', '' ], [ 'nino-wrap', true, '', '' ], [ 'a&b', true, '', '' ], [ 'a<b', true, '', '' ] ] as [ $value, $animate, $effect, $speed ] ) {
	$wrapEdit = $wrapSave['model'];
	$wrapEdit['wrapClass'] = $value;
	$wrapEdit['animate'] = $animate;
	$wrapEdit['vpa'] = $effect;
	$wrapEdit['vpaSpeed'] = $speed;
	$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
	if( $answer['status'] !== 400 || str_contains( implode( ' ', $answer['problems'] ), 'classes of the wrap do not read back' ) === false || file_get_contents( $wrapPath ) !== $wrapBefore )
		$wrapRefused[] = $value;
}

check( 'a class of the wrap that is one of its own and is not the wrap\'s to say, or that the builder cannot keep, would be read as another: it is refused, with the wrap named, and nothing is written: '. count( $wrapRefused ). ' of 6 were accepted'. ( $wrapRefused === [] ? '' : ' - '. implode( ', ', $wrapRefused ) ), $wrapRefused === [] );

$wrapSource = \Nino\Modules\Builder\Document::source( $appData, $wrapLoad['model'] );
check( 'the source view has the wrap in the head and in the foot, and the parts joined by a blank line are the file', $wrapSource['status'] === 200 && $wrapSource['parts'][0]['kind'] === 'head' && $wrapSource['parts'][0]['source'] === "<!-- nino:template-name Home -->\n[template /templates/html-header]\n". $open
	&& $wrapSource['parts'][count( $wrapSource['parts'] ) - 1]['kind'] === 'foot' && $wrapSource['parts'][count( $wrapSource['parts'] ) - 1]['source'] === "</div>\n[template /templates/html-footer]"
	&& implode( "\n\n", array_column( $wrapSource['parts'], 'source' ) ). "\n" === $wrapSource['source'] && $wrapSource['source'] === $wrapFixture );
check( '...and a page with no frame has the wrap alone in its head and its foot', \Nino\Modules\Builder\Document::source( $appData, [ 'blocks' => [] ] )['parts'] === [ [ 'kind' => 'head', 'source' => '<div class="nino-wrap">', 'block' => null ], [ 'kind' => 'foot', 'source' => '</div>', 'block' => null ] ] );

unlink( $wrapPath );

// A page that has the head line an earlier builder wrote has no wrap: it is read as the wrap says it, kept as it is until it is changed, and then written in the wrap
$vpaPath = \Nino\Filesystem::path( $appData, '/templates/page-vpa.tpl' );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-vpa.tpl', $vpaFixture );
$vpaLoad = \Nino\Modules\Builder\Document::load( $appData, 'page-vpa' );
$vpaSave = \Nino\Modules\Builder\Document::save( $appData, 'page-vpa', $vpaLoad['model'], $vpaLoad['hash'] );
check( 'a template with the head line is loaded and saved without a change byte for byte: it is not wrapped by being opened', $vpaLoad['model']['animate'] === true && $vpaLoad['model']['vpa'] === 'zoom-soft' && $vpaLoad['model']['vpaSpeed'] === 'medium'
	&& $vpaSave['status'] === 200 && file_get_contents( $vpaPath ) === $vpaFixture && $vpaSave['hash'] === $vpaLoad['hash'] );

$vpaEdit = $vpaLoad['model'];
$vpaEdit['vpaSpeed'] = 'fast';
$vpaSave = \Nino\Modules\Builder\Document::save( $appData, 'page-vpa', $vpaEdit, $vpaLoad['hash'] );
check( '...and a change writes it in the wrap, with the kind the line said and the change, and no head line: the line is gone', $vpaSave['status'] === 200 && file_get_contents( $vpaPath ) === str_replace( 'speed-medium', 'speed-fast', $wrapFixture )
	&& str_contains( (string) file_get_contents( $vpaPath ), 'nino:template-vpa' ) === false && $vpaSave['model']['vpaSpeed'] === 'fast' );

$vpaEdit = $vpaLoad['model'];
$vpaEdit['vpa'] = 'nino-vpa nino-vpa--blur-hard';
$vpaAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-vpa', $vpaEdit, $vpaSave['hash'] );
check( 'the animation as the head line had it is no animation of the model any more: the classes of it are refused where the effect is looked for', $vpaAnswer['status'] === 400 && str_contains( implode( ' ', $vpaAnswer['problems'] ), 'effect of the animation' ) === true );

unlink( $vpaPath );

// A template with texts in two languages, a global one, a limit, a slot with a picture and alt texts, a block of html that names a key
$srcFile = "<!-- nino:template-name Dup -->\n[template /templates/html-header]\n<div class=\"nino-wrap nino-wrap--vpa nino-vpa--blur-soft\">\n\n"
	. "<section id=\"one\" class=\"nino-section nino-vpa\">\n\t<div class=\"nino-section-bg\">[image /template/page-dup/one/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-dup/one/title]\n\t\t\t[text /template/page-dup/one/text]\n\t\t</div>\n\t</div>\n</section>\n\n"
	. "<!-- nino:html -->\n<p>[[/template/page-dup/one/title]] stays a sentence, [[/template/page-dupx/one/title]] another</p>\n<!-- /nino:html -->\n\n</div>\n[template /templates/html-footer]\n";
\Nino\Filesystem::putFileContent( $appData, '/templates/page-dup.tpl', $srcFile );

foreach( [ 'en_US' => [ 'Hello', 'Dup text' ], 'de_DE' => [ 'Hallo', 'Dup Text' ] ] as $locale => [ $title, $text ] )
	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ) use ( $title, $text ): array {
		return (array) $texts + [ '[[/template/page-dup/one/title]]' => $title, '[[/template/page-dup/one/text]]' => '<p>'. $text. '</p>', '[[/template/page-dupx/one/title]]' => 'Not mine', '[[/template/page-dup/not-a-key]]' => 'No grammar' ];
	} );

\Nino\Filesystem::mutate( $appData, '/text/global.php', static fn( mixed $texts ): array => (array) $texts + [ '[[/template/page-dup/one/note]]' => 'Everywhere' ] );
\Nino\Text::setMeta( $appData, '/template/page-dup/one/title', 'plain', 120 );

$dupImage = 'template/page-dup/one/background.1200x600.jpg';
\Nino\Images::restore( $appData, $dupImage, 'the bytes of a picture' );
$appData['/nino/html/images']['/template/page-dup/one/background'] = [ 'label' => 'Dup background', 'width' => 1200, 'height' => 600, 'filename' => $dupImage, 'alt' => [ 'en_US' => 'A team', 'de_DE' => 'Ein Team' ] ];
$appData['/nino/html/images']['/template/page-dupx/one/background'] = [ 'label' => 'Not mine', 'width' => 100, 'height' => 100, 'filename' => null ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );

$state = static fn(): array => [
	\Nino\Text::entries( $appData ),
	\Nino\Images::getSlots( $appData ),
	array_map( static fn( string $path ): string => basename( $path ), glob( \Nino\Filesystem::path( $appData, '/templates' ). '/*.tpl' ) ?: [] ),
	is_file( \Nino\Filesystem::path( $appData, '/images/'. $dupImage ) ) ?: glob( \Nino\Filesystem::path( $appData, '/images' ). '/template/*/*/*' ),
];
$untouched = $state();

// What is refused is refused before anything is made
$refusals = [
	'a file name that is none'	=> [ '../x', 'Copy', 400 ],
	'a template that is not there'	=> [ 'page-nope', 'Copy', 404 ],
	'no name'							=> [ 'page-dup', '', 400 ],
	'a name of hyphens'		=> [ 'page-dup', '---', 400 ],
	'a name with a comment end'	=> [ 'page-dup', 'a --> b', 400 ],
	'a name with angle brackets'	=> [ 'page-dup', '<b>x</b>', 400 ],
	'a name with a line break'	=> [ 'page-dup', "two\nlines", 400 ],
	'a name with a bracket'	=> [ 'page-dup', 'Copy [[/x]]', 400 ],
	'a name that is too long'	=> [ 'page-dup', str_repeat( 'x', 161 ), 400 ],
	'a name that makes the file it is'	=> [ 'page-dup', 'Dup', 409 ],
	'a name that makes a file that is there'	=> [ 'page-dup', 'Home', 409 ],
];
$accepted = [];

foreach( $refusals as $label => [ $file, $name, $status ] )
	if( \Nino\Modules\Builder\Document::duplicate( $appData, $file, $name )['status'] !== $status )
		$accepted[] = $label;

check( 'a copy that cannot be made is refused as it is, 400 for a name or a file that is none, 404 for a template that is not there, 409 for a file that is there: '. count( $refusals ). ' cases'
	. ( $accepted === [] ? '' : ' - '. implode( ', ', $accepted ) ), $accepted === [] && $state() === $untouched );
check( '...and the name of an existing file answers the code the panel looks at', \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Home' )['code'] === 'exists'
	&& \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', '' )['code'] === 'name' );

$dup = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Copy' );
$copyPath = \Nino\Filesystem::path( $appData, '/templates/page-dup-copy.tpl' );
$copyText = (string) file_get_contents( $copyPath );

check( 'a copy answers 200 with the model of the new file, and its hash', $dup['status'] === 200 && $dup['model']['file'] === 'page-dup-copy' && $dup['hash'] === hash( 'sha256', $copyText )
	&& \Nino\Modules\Builder\Document::load( $appData, 'page-dup-copy' )['model'] === $dup['model'] );
check( '...the file is the old one under its new name: the name line, the wrap with its animation, the frames, and every source made the new file\'s - in the sections and in the blocks of html',
	$copyText === str_replace( [ '/template/page-dup/', 'template-name Dup -->' ], [ '/template/page-dup-copy/', 'template-name Dup Copy -->' ], $srcFile ) && $dup['model']['name'] === 'Dup Copy'
	&& $dup['model']['animate'] === true && $dup['model']['vpa'] === 'blur-soft' && $dup['model']['blocks'][0]['background']['slot'] === '/template/page-dup-copy/one/background'
	&& $dup['model']['blocks'][0]['cols'][0]['components'][0]['source'] === '/template/page-dup-copy/one/title' && $dup['model']['blocks'][1]['reason'] === null
	&& str_contains( $dup['model']['blocks'][1]['source'], '[[/template/page-dup-copy/one/title]] stays a sentence, [[/template/page-dupx/one/title]] another' ) === true );
check( '...and the old file is as it was', file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-dup.tpl' ) ) === $srcFile );

$title = \Nino\Text::entry( $appData, '/template/page-dup-copy/one/title' ) ?? [];
$text = \Nino\Text::entry( $appData, '/template/page-dup-copy/one/text' ) ?? [];
$note = \Nino\Text::entry( $appData, '/template/page-dup-copy/one/note' ) ?? [];
check( 'every text key under the old name is made under the new one with the values of every language: a key of two languages, one that is global', ( $title['values']['en_US'] ?? null ) === 'Hello' && ( $title['values']['de_DE'] ?? null ) === 'Hallo'
	&& ( $text['values']['en_US'] ?? null ) === '<p>Dup text</p>' && ( $text['values']['de_DE'] ?? null ) === '<p>Dup Text</p>' && ( $note['global'] ?? null ) === true && ( $note['values'] ?? null ) === [ '*' => 'Everywhere' ] );
check( '...with the format and the limit that were set', ( $title['formatSet'] ?? null ) === true && ( $title['format'] ?? null ) === 'plain' && ( $title['maxlengthSet'] ?? null ) === true && ( $title['maxlength'] ?? null ) === 120
	&& ( $text['maxlengthSet'] ?? null ) === false );
check( '...and the old ones are as they were, and what is no key of the old name is not copied: another template\'s key, and a text that is no key of the grammar', ( \Nino\Text::entry( $appData, '/template/page-dup/one/title' )['values']['de_DE'] ?? null ) === 'Hallo'
	&& \Nino\Text::entry( $appData, '/template/page-dup-copy/not-a-key' ) === null && \Nino\Text::entry( $appData, '/template/page-dup-copy-x/one/title' ) === null
	&& \Nino\Text::entry( $appData, '/template/page-dup/one/note' ) !== null && count( array_filter( \Nino\Text::entries( $appData ), static fn( array $entry ): bool => str_starts_with( $entry['key'], '/template/page-dup-copy/' ) ) ) === 3 );

$slot = \Nino\Images::getSlot( $appData, '/template/page-dup-copy/one/background' ) ?: [];
$copyImage = 'template/page-dup-copy/one/background.1200x600.jpg';
check( 'every image slot is made again with its label, its size and its alt texts', ( $slot['label'] ?? null ) === 'Dup background' && ( $slot['width'] ?? null ) === 1200 && ( $slot['height'] ?? null ) === 600
	&& ( $slot['alt'] ?? null ) === [ 'en_US' => 'A team', 'de_DE' => 'Ein Team' ] && \Nino\Images::getSlot( $appData, '/template/page-dup-copy/one/background' ) !== false && \Nino\Images::getSlot( $appData, '/template/page-dup-copy-x/one/background' ) === false );
check( '...and with a picture of its own: the same bytes under the new name, so that deleting the one slot leaves the other its picture', ( $slot['filename'] ?? null ) === $copyImage && \Nino\Images::read( $appData, $copyImage ) === 'the bytes of a picture'
	&& ( \Nino\Images::getSlot( $appData, '/template/page-dup/one/background' ) ?: [] )['filename'] === $dupImage && \Nino\Images::read( $appData, $dupImage ) === 'the bytes of a picture' );

$_POST['data'] = (string) json_encode( [ 'uri' => '/template/page-dup-copy/one/background' ] );
$slotRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Images\Slots::apiDelete( $appData, $slotRequest );
unset( $_POST['data'] );
check( '...so the slot of the copy is deleted in the Slots tab and the old one keeps its picture', $slotRequest['/nino/http/response']['statusCode'] === 200 && \Nino\Images::read( $appData, $copyImage ) === false
	&& \Nino\Images::read( $appData, $dupImage ) === 'the bytes of a picture' );

$once = $state();
check( 'a second copy under the same name is refused with 409, and nothing is made', \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Copy' )['status'] === 409 && $state() === $once );

// A key or a slot of the copy that is there already: a copy of a template whose keys were left behind
\Nino\Filesystem::mutate( $appData, '/text/en_US.php', static fn( mixed $texts ): array => (array) $texts + [ '[[/template/page-dup-orphan/one/title]]' => 'Left behind' ] );
$orphans = $state();
$orphan = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Orphan' );
check( 'a key of the copy that is there already - the keys of a template that was deleted stay - is refused with 409 (key-exists), named by its bare uri in the keys of the answer, and nothing is made', $orphan['status'] === 409 && $orphan['code'] === 'key-exists'
	&& $orphan['keys'] === [ '/template/page-dup-orphan/one/title' ] && $orphan['slots'] === [] && $orphan['problems'] === [] && $state() === $orphans );
$appData['/nino/html/images']['/template/page-dup-slotted/one/background'] = [ 'label' => 'Left', 'width' => 10, 'height' => 10, 'filename' => null ];
$slotted = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Slotted' );
unset( $appData['/nino/html/images']['/template/page-dup-slotted/one/background'] );
check( '...so is a slot, named in the slots of the answer', $slotted['status'] === 409 && $slotted['code'] === 'key-exists' && $slotted['keys'] === [] && $slotted['slots'] === [ '/template/page-dup-slotted/one/background' ]
	&& $slotted['problems'] === [] && $state() === $orphans );

// The permissions of the panels that own the keys and the slots
\Nino\Auth::insertUser( $appData, 'nobody@example.com', 'correct horse battery staple', [ '/_admin/builder/manage' ] );
\Nino\Auth::loginUser( $appData, 'nobody@example.com', 'correct horse battery staple' );
$noKeys = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Forbidden' );
\Nino\Auth::loginUser( $appData, 'keys@example.com', 'correct horse battery staple' );
$noSlots = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Forbidden' );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );
check( 'an account that may not make keys, or not slots, cannot copy a template that has them: 403, the permission named - before anything is made', $noKeys['status'] === 403 && $noKeys['problems'] === [ '/_admin/keys/manage' ]
	&& $noSlots['status'] === 403 && $noSlots['problems'] === [ '/_admin/slots/manage' ] && $state() === $orphans );

// A copy that stops takes everything it made away again
$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/templates/page-dup-lock.tpl' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$locked = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Lock' );
rmdir( $lock );
check( 'a copy whose file cannot be written answers 500, and the keys and the slots and the pictures it made are taken away again', $locked['status'] === 500 && $locked['code'] === 'write' && $state() === $orphans
	&& \Nino\Images::read( $appData, 'template/page-dup-lock/one/background.1200x600.jpg' ) === false );

$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/text/de_DE.php' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$unwritten = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Key' );
rmdir( $lock );
check( 'a copy whose key cannot be written in every language is refused with 500 (key) - the panel makes it without saying - and what was made is taken away again', $unwritten['status'] === 500 && $unwritten['code'] === 'key'
	&& $state() === $orphans && \Nino\Filesystem::fileExists( $appData, '/templates/page-dup-key.tpl' ) === false );

// A slot the Slots tab will not make, after the keys were: the label is none and the name of the slot is made of its last segment
$appData['/nino/html/images']['/template/page-dup/two/image'] = [ 'label' => '', 'width' => 100, 'height' => 100, 'filename' => null ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
$unlabelled = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Unlabelled' );
check( 'a slot with no label is made with its name as the label, so that it can be copied: the Slots tab asks for one', $unlabelled['status'] === 200
	&& ( \Nino\Images::getSlot( $appData, '/template/page-dup-unlabelled/two/image' ) ?: [] )['label'] === 'Image' );

// A slot that another request makes after the copy has looked and before it makes its own: the Slots tab finds it in config.php (409), which this
// request's copy of the slots does not have, so the look did not see it. The other request's slot is not the copy's to use
$beforeRace = $state();
$theirSlot = [ 'label' => 'Theirs', 'width' => 10, 'height' => 10, 'filename' => null ];
\Nino\Filesystem::mutate( $appData, '/config.php', static function( mixed $content ) use ( $theirSlot ): array {
	$content = (array) $content;
	$content['/nino/html/images']['/template/page-dup-race/two/image'] = $theirSlot;
	return $content;
} );
$race = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-dup', 'Dup Race' );
$stored = (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
check( 'a slot that is there when the copy makes it, though it was not when it looked, is a failure of the copy: 409 (slot), the slot named', $race['status'] === 409 && $race['code'] === 'slot'
	&& $race['problems'] === [ '/template/page-dup-race/two/image' ] && \Nino\Filesystem::fileExists( $appData, '/templates/page-dup-race.tpl' ) === false );
check( '...everything the copy made before is taken away again - the keys, the slot with its picture - and the slot of the other request is as it was made', $state() === $beforeRace
	&& \Nino\Images::read( $appData, 'template/page-dup-race/one/background.1200x600.jpg' ) === false && ( $stored['/nino/html/images']['/template/page-dup-race/one/background'] ?? null ) === null
	&& ( $stored['/nino/html/images']['/template/page-dup-race/two/image'] ?? null ) === $theirSlot );
\Nino\Filesystem::mutate( $appData, '/config.php', static function( mixed $content ): array {
	$content = (array) $content;
	unset( $content['/nino/html/images']['/template/page-dup-race/two/image'] );
	return $content;
} );

// Values of different formats, none of them chosen: every one of them comes through, the first language being plain
\Nino\Filesystem::putFileContent( $appData, '/templates/page-fmt.tpl', "[template /templates/html-header]\n\n". $section( "[title /template/page-fmt/a/title]\n\t\t\t[text /template/page-fmt/a/text]", 'a' ). "\n" );
\Nino\Filesystem::mutate( $appData, '/text/de_DE.php', static fn( mixed $texts ): array => (array) $texts + [ '[[/template/page-fmt/a/title]]' => 'Hallo', '[[/template/page-fmt/a/text]]' => 'Nur deutsch' ] );
\Nino\Filesystem::mutate( $appData, '/text/en_US.php', static fn( mixed $texts ): array => (array) $texts + [ '[[/template/page-fmt/a/title]]' => 'Hello <strong>world</strong>' ] );
\Nino\Filesystem::mutate( $appData, '/text/global.php', static fn( mixed $texts ): array => (array) $texts + [ '[[/template/page-fmt/a/note]]' => '0' ] );
$formats = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-fmt', 'Fmt Copy' );
$fmtOld = \Nino\Text::entry( $appData, '/template/page-fmt/a/title' ) ?? [];
$fmtNew = \Nino\Text::entry( $appData, '/template/page-fmt-copy/a/title' ) ?? [];
check( 'a key whose languages hold different formats is copied with every value as it is, though the format was never chosen: the markup of the second language stays', $formats['status'] === 200
	&& ( $fmtNew['values']['en_US'] ?? null ) === 'Hello <strong>world</strong>' && ( $fmtNew['values']['de_DE'] ?? null ) === 'Hallo' && ( $fmtNew['format'] ?? null ) === ( $fmtOld['format'] ?? '' ) );
check( '...the format stays unchosen, as it was, so that it follows the values', ( $fmtOld['formatSet'] ?? null ) === false && ( $fmtNew['formatSet'] ?? null ) === false );
check( '...a global value of 0 is a value, not nothing', ( \Nino\Text::entry( $appData, '/template/page-fmt-copy/a/note' )['values'] ?? null ) === [ '*' => '0' ] );

// A file with no name line is given one, first
\Nino\Filesystem::putFileContent( $appData, '/templates/page-plain.tpl', "[template /templates/html-header]\n\n". $section( '[title /template/page-plain/a/title]', 'a' ). "\n" );
$plain = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-plain', 'Plain Copy' );
check( 'a template with no name line is copied with one - first, where the Reader looks for it - and its sections are what they were', $plain['status'] === 200 && $plain['model']['name'] === 'Plain Copy' && $plain['model']['header'] === 'html-header'
	&& str_starts_with( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-plain-copy.tpl' ) ), "<!-- nino:template-name Plain Copy -->\n[template /templates/html-header]\n" ) === true
	&& $plain['model']['blocks'][0]['cols'][0]['components'][0]['source'] === '/template/page-plain-copy/a/title' );

check( 'the copies of these probes are listed with the rest, by file', array_slice( array_column( \Nino\Modules\Builder\Document::list( $appData )['templates'], 'file' ), 0, 3 ) === [ 'page-dup', 'page-dup-copy', 'page-dup-unlabelled' ] );

foreach( [ 'page-dup', 'page-dup-copy', 'page-dup-unlabelled', 'page-plain', 'page-plain-copy', 'page-fmt', 'page-fmt-copy' ] as $probe )
	\Nino\Modules\Builder\Document::delete( $appData, $probe );

echo "\n";


// --- 10. The page the builder wrote, rendered --------------------------------

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
check( 'an empty page of the builder renders as its frames and its wrap, a div the kernel leaves as it is, with nothing in it', \Nino\Html::renderHtml( $appData, '[template /templates/page-ueber-uns]' ) === "<!-- nino:template-name Über uns -->\n<header>Header</header>\n<div class=\"nino-wrap\">\n\n</div>\n<footer>Footer</footer>\n" );

echo "\n";


// --- 11. The panel's actions -------------------------------------------------

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

$actions = [ 'apiList', 'apiCreate', 'apiDuplicate', 'apiLoad', 'apiSave', 'apiDelete', 'apiSource', 'apiRegistry' ];

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

[ $status, $copiedBody ] = callBuilderAction( $appData, 'apiDuplicate', [ 'file' => 'page-home', 'name' => 'Home copy' ] );
check( 'duplicate answers the model of the copy; a name that makes a file that is there is 409, a name that is none 400, a template that is not there 404', $status === 200 && $copiedBody['model']['file'] === 'page-home-copy'
	&& $copiedBody['model']['blocks'][0]['id'] === 'start' && \Nino\Text::entry( $appData, '/template/page-home-copy/start/title' ) !== null
	&& callBuilderAction( $appData, 'apiDuplicate', [ 'file' => 'page-home', 'name' => 'home copy' ] )[0] === 409 && callBuilderAction( $appData, 'apiDuplicate', [ 'file' => 'page-home', 'name' => '' ] )[0] === 400
	&& callBuilderAction( $appData, 'apiDuplicate', [ 'file' => 'page-nope', 'name' => 'Nope' ] )[0] === 404 && callBuilderAction( $appData, 'apiDuplicate', [ 'name' => 'Nope' ] )[0] === 400 );
check( '...a copy is written to the activity log with the template and the name, as a create is', \Nino\Modules\Builder\Admin::log( 'builder/duplicate', [ 'file' => 'page-home', 'name' => 'Home copy' ] ) === 'Duplicate Builder Template page-home as "Home copy"' );
callBuilderAction( $appData, 'apiDelete', [ 'file' => 'page-home-copy' ] );

// The file is gone and its keys and slots stay: a copy under the same name finds them in its way
[ $status, $inTheWay ] = callBuilderAction( $appData, 'apiDuplicate', [ 'file' => 'page-home', 'name' => 'Home copy' ] );
$wanted = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-home', 'Home copy' );
check( 'duplicate refuses a copy whose keys or slots are there already with 409 (builder_key_exists), the bare uris in the params - two lists, the keys and then the slots - and no sentence of the server\'s',
	$status === 409 && $inTheWay['code'] === 'builder_key_exists' && $wanted['code'] === 'key-exists' && $inTheWay['params'] === [ $wanted['keys'], $wanted['slots'] ] && count( $inTheWay['params'] ) === 2
	&& in_array( '/template/page-home-copy/start/title', $inTheWay['params'][0], true ) === true && in_array( '/template/page-home-copy/start/background', $inTheWay['params'][1], true ) === true
	&& array_filter( array_merge( ...$inTheWay['params'] ), static fn( mixed $uri ): bool => is_string( $uri ) === false || str_starts_with( $uri, '/template/page-home-copy/' ) === false ) === [] );

$example = \Nino\Modules\Builder\Reader::read( (string) file_get_contents( __DIR__. '/fixtures/page-home.tpl' ), $registry );

[ $status, $sourceBody ] = callBuilderAction( $appData, 'apiSource', [ 'model' => $example ] );
check( 'source answers the file the model would be written as - the example page in its wrap - and what the file is made of: the head, each block, the foot', $status === 200 && $sourceBody['source'] === $wrapped( (string) file_get_contents( __DIR__. '/fixtures/page-home.tpl' ) )
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

$notASwitch = $next;
$notASwitch['blocks'][0]['settings']['fullheight'] = 'yes';
$switchHash = \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['hash'];
[ $status, $switchBody ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'model' => $notASwitch, 'hash' => $switchHash ] );
check( '...a full height that is no bool: 400, builder_invalid, the switch and its block in the params - and the file is as it was', $status === 400 && $switchBody['code'] === 'builder_invalid'
	&& $switchBody['params'] === [ 'the fullheight of block 1 is neither on nor off' ] && \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['hash'] === $switchHash );

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
