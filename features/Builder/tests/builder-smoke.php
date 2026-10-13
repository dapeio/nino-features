<?php
declare(strict_types=1);

/**
 *	Nino
 *	builder-smoke.php		The Builder's server half over Nino's harness: the manifest
 *											and the panel, then the Reader and the Writer on the
 *											example page of the concept (tests/fixtures/page-home.tpl,
 *											the example in its canonical form but for the wrap, which a
 *											file the builder did not write has not: the concept's own,
 *											page-home-concept.tpl, says tag="h3" twice and gap="2"
 *											aloud, which are the defaults the Writer leaves out;
 *											page-home-wrap.tpl is the same in the wrap the builder
 *											writes, with a kind of animation, and page-home-vpa.tpl
 *											the same with the animation line an earlier builder wrote
 *											in the head, which reads as that wrap) and on a sheet of
 *											sections that each fail one rule - the picture behind a column
 *											among them -, then on the ground of a column, its dimming and
 *											the picture behind it, the border of a section as a strength and
 *											a brand colour, the cover, the parallax and the place of the
 *											content of a section, the spacing of a column and the card - a
 *											component that holds components: its children, its attributes,
 *											the three reasons that make a section a block of html and the
 *											slots a save makes for its picture - then
 *											the Document - listing, loading, saving with a hash, the keys
 *											and the slots a save makes, the texts it writes to the keys
 *											(new ones and ones that are there, in the language asked for)
 *											and the alt texts a new slot is made with - for which the
 *											account has to have the Images panel's permission - and how all
 *											of it is put back when the save fails -, the refusals, the wrap and
 *											the animation of the template, a copy of a template with its
 *											keys and slots and what stops it - and last the page the
 *											builder wrote, rendered by the kernel. The panel's script has
 *											a test of its own, builder-js-smoke.js, which runs at the end
 *											where node is there; the two fixtures it reads
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
check( 'it is written for the kernel that has the Components module, with the card called one', is_array( $manifest ) === true && $manifest['nino'] === '^1.7' && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it is a feature for content, with nothing required, no settings and no data of its own', $manifest['category'] === 'content' && $manifest['requires'] === [] && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'it names and describes itself in both interface languages', \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'its manual says what it adds: the panel, and no shortcode, route or callback', $manifest['manual']['shortcodes'] === [] && $manifest['manual']['routes'] === [] && $manifest['manual']['callbacks'] === [] && isset( $manifest['manual']['panel']['Builder'] ) === true );
check( '...and tells, in both languages and in a line each, of the language picker, the texts of the keys and the pictures of the slots', array_diff( [ 'Language of the texts', 'Texts of the keys', 'Pictures of the slots' ], array_keys( $manifest['manual']['panel'] ) ) === [] && array_reduce( [ 'Language of the texts', 'Texts of the keys', 'Pictures of the slots' ], static fn( bool $carry, string $name ): bool => $carry === true && \Nino\Features::localized( $manifest['manual']['panel'][$name], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['panel'][$name], 'en_US' ), true ) );
check( '...and tells of the loop, of the components by place and of the card and its form, in both languages and in a line each - the cards of a loop are gone from it, as the kernel has no such thing, and so are their words', array_diff( [ 'Loop', 'Components by place', 'The card', 'Properties of a card' ], array_keys( $manifest['manual']['panel'] ) ) === []
	&& isset( $manifest['manual']['panel']['Cards of a loop'] ) === false && array_reduce( [ 'Loop', 'Components by place', 'The card', 'Properties of a card' ], static fn( bool $carry, string $name ): bool => $carry === true && \Nino\Features::localized( $manifest['manual']['panel'][$name], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['panel'][$name], 'en_US' ), true )
	&& array_filter( [ 'en_US', 'de_DE' ], static function( string $locale ) use ( $dir ): bool {
		$texts = (array) include $dir. '/text/'. $locale. '.php';
		return array_filter( array_keys( $texts ), static fn( string $key ): bool => str_contains( $key, '/stack/card' ) === true || str_contains( $key, '/option/card-' ) === true ) !== [];
	} ) === [] );
check( '...and names the markup it reads and writes besides the wrap and the picture of a column: the call of a component that holds components, and the spacing classes of a column, in both languages', array_diff( [ '[card padding="3"][card-title /slot][/card]', 'nino-p-2 nino-mt-1 nino-mb-1' ], array_keys( $manifest['manual']['markup'] ) ) === []
	&& array_reduce( [ '[card padding="3"][card-title /slot][/card]', 'nino-p-2 nino-mt-1 nino-mb-1' ], static fn( bool $carry, string $name ): bool => $carry === true && \Nino\Features::localized( $manifest['manual']['markup'][$name], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['markup'][$name], 'en_US' ), true ) );

check( 'the feature brings its panel along, and registers nothing on the site', \Nino\Modules\Builder::adminPanels( $appData ) === [ \Nino\Modules\Builder\Admin::class ] && ( static function() use ( &$appData ): bool {
	$before = $appData;
	\Nino\Modules\Builder::init( $appData );
	return $before === $appData;
} )() );

check( 'the panel names every action it answers', array_keys( \Nino\Modules\Builder\Admin::actions() ) === [ 'builder/list', 'builder/create', 'builder/duplicate', 'builder/load', 'builder/save', 'builder/delete', 'builder/source', 'builder/registry' ] );
check( '...asks for its own permission, names no group in its nav() - three entries, since a feature\'s panel sits in the Features group whatever it would name - and is a workspace', \Nino\Modules\Builder\Admin::perm() === '/_admin/builder/manage'
	&& count( \Nino\Modules\Builder\Admin::nav() ) === 3 && \Nino\Modules\Builder\Admin::layout() === 'workspace' );
check( '...and brings the HTML editor of the workbench, its own script and its stylesheet, which are there', count( \Nino\Modules\Builder\Admin::assets() ) === 3 && in_array( '/_admin/assets/html-editor.js', \Nino\Modules\Builder\Admin::assets(), true ) === true
	&& array_filter( [ 'assets/admin.js', 'assets/admin.css', 'templates/panel.tpl', 'text/en_US.php', 'text/de_DE.php' ], static fn( string $file ): bool => is_file( dirname( __DIR__ ). '/'. $file ) === false ) === [] );
check( '...whose words are the same keys in both languages', array_keys( (array) include $dir. '/text/en_US.php' ) === array_keys( (array) include $dir. '/text/de_DE.php' ) );
check( '...and the sentences that name the keys and the slots of a copy in the way have a place for the list, one each, in both', array_filter( [ 'en_US', 'de_DE' ], static function( string $locale ) use ( $dir ): bool {
	$texts = (array) include $dir. '/text/'. $locale. '.php';
	return substr_count( (string) ( $texts['[[/_admin/builder/error/keys-in-the-way]]'] ?? '' ), '%s' ) !== 1 || substr_count( (string) ( $texts['[[/_admin/builder/error/slots-in-the-way]]'] ?? '' ), '%s' ) !== 1;
} ) === [] );
check( '...and the word that names the picture behind a column - the label "Create new" proposes for its slot - starts with a capital as the section\'s does, with the place for the number of the column once, in both', array_filter( [ 'en_US', 'de_DE' ], static function( string $locale ) use ( $dir ): bool {
	$texts = (array) include $dir. '/text/'. $locale. '.php';
	$column = (string) ( $texts['[[/_admin/builder/source/column-background]]'] ?? '' );
	return substr_count( $column, '%s' ) !== 1 || preg_match( '/^\p{Lu}/u', $column ) !== 1 || preg_match( '/^\p{Lu}/u', (string) ( $texts['[[/_admin/builder/source/background]]'] ?? '' ) ) !== 1;
} ) === [] );
check( 'a save, a create and a delete are written to the activity log, a read is not', \Nino\Modules\Builder\Admin::log( 'builder/save', [ 'file' => 'page-home' ] ) !== ''
	&& \Nino\Modules\Builder\Admin::log( 'builder/create', [] ) !== '' && \Nino\Modules\Builder\Admin::log( 'builder/delete', [] ) !== ''
	&& \Nino\Modules\Builder\Admin::log( 'builder/load', [] ) === '' && \Nino\Modules\Builder\Admin::log( 'builder/list', [] ) === '' && \Nino\Modules\Builder\Admin::log( 'builder/source', [] ) === '' );

echo "\n";


// --- 2. The registry the Reader and the Writer take --------------------------

echo "The registry of the Components module\n";

\Nino\Modules\Components::init( $appData );
$registry = \Nino\Modules\Builder\Document::schemas( $appData );

check( 'the components and the stacks of the kernel are in it, each with the defaults of its attributes - in the order a column offers the components, the card, which holds components, between the text and the button', array_keys( $registry['components'] ) === [ 'title', 'subtitle', 'card-title', 'card-subtitle', 'hero-title', 'hero-subtitle', 'text', 'card', 'button', 'image', 'html', 'spacer' ]
	&& array_keys( $registry['stacks'] ) === [ 'stack', 'slider', 'filter', 'list' ]
	&& $registry['components']['title']['defaults'] === [ 'tag' => 'h3', 'style' => '', 'class' => '' ]
	&& $registry['stacks']['stack']['defaults']['gap'] === '2' );
check( '...the card says what it holds, and is the one that does: a component whose schema has holds is a container, and nothing else is - the Builder asks the schema and names none', \Nino\Modules\Builder\Reader::holds( $registry['components']['card'] ) === true && $registry['components']['card']['holds'] === 'card'
	&& array_filter( $registry['components'], static fn( array $schema ): bool => \Nino\Modules\Builder\Reader::holds( $schema ) === true ) === [ 'card' => $registry['components']['card'] ] && \Nino\Modules\Builder\Reader::holds( [ 'holds' => '' ] ) === false && \Nino\Modules\Builder\Reader::holds( [] ) === false );
check( '...and a loop is a grid and nothing more: its defaults carry no card, the cell is a div', array_filter( $registry['stacks'], static fn( array $schema ): bool => array_key_exists( 'card', $schema['defaults'] ) === true ) === [] );
check( '...the six titles name their tag and no level: one select of the tags their place takes, the default among them, then the style - a hero is an h2, a section an h3, a card an h4, and the subtitles a p, a div and a p', array_filter(
	[ 'hero-title' => 'h2', 'hero-subtitle' => 'p', 'title' => 'h3', 'subtitle' => 'div', 'card-title' => 'h4', 'card-subtitle' => 'p' ],
	static fn( string $default, string $name ): bool => array_keys( $registry['components'][$name]['attributes'] ) !== [ 'tag', 'style' ] || $registry['components'][$name]['attributes']['tag']['type'] !== 'select'
		|| in_array( $default, $registry['components'][$name]['attributes']['tag']['options'], true ) === false || $registry['components'][$name]['defaults']['tag'] !== $default || array_key_exists( 'level', $registry['components'][$name]['defaults'] ) === true,
	ARRAY_FILTER_USE_BOTH
) === [] );

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
check( 'the hero: its classes are settings', $hero['settings']['fullwidth'] === true && $hero['settings']['fullheight'] === false && $hero['settings']['color'] === 'black' && $hero['settings']['cover'] === 100
	&& $hero['settings']['parallax'] === false && $hero['settings']['dim'] === true && $hero['settings']['contentPos'] === '' && $hero['settings']['vpa'] === '' && $hero['settings']['row'] === 'wide' && $hero['settings']['rowAlign'] === 'middle'
	&& $hero['settings']['custom'] === '' );
check( '...its background is a slot with a focus', $hero['background'] === [ 'slot' => '/template/page-home/hero/background', 'focus' => 5 ] );
check( '...its column is 100, 100 and 66 wide, aligned left, and holds three components', $hero['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 66 ] && $hero['cols'][0]['text'] === 'left'
	&& $hero['cols'][0]['stack'] === null && array_column( $hero['cols'][0]['components'], 'name' ) === [ 'title', 'subtitle', 'button' ] );
check( '...a call carries its source and its attributes, every one of the schema, the default where the call is silent',
	$hero['cols'][0]['components'][0] === [ 'name' => 'title', 'source' => '/template/page-home/hero/title', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => 'loud', 'class' => '' ] ] );

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
	[ '[title /template/page-home/hero/title style="loud"]', '[title /template/page-home/contact/title]', "\t\t<div class=\"nino-grid-100\">\n\t\t\t[title" ],
	[ '[title /template/page-home/hero/title class="" tag="h3" style="loud"]', '[title /template/page-home/contact/title tag="h3" style=""]', "\t\t<div class=\"nino-grid-s-100 nino-grid-m-100 nino-grid-l-100\">\n\t\t\t[title" ],
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

// A card is a component that holds components: the calls of its children stand between its tags, one tab deeper, and its closing tag stands at the depth of its call
$cardFixture	= (string) file_get_contents( __DIR__. '/fixtures/page-home-card.tpl' );
$cardModel		= $read( $cardFixture );
$story				= $cardModel['blocks'][0];
$cardNode			= $story['cols'][1]['components'][0];
$cell					= $cardModel['blocks'][1]['cols'][0];

check( 'the page with a card is read as it is written: a picture beside a card in one section, a loop in the next', array_column( $cardModel['blocks'], 'kind' ) === [ 'section', 'section' ] && [ $cardModel['blocks'][0]['id'], $cardModel['blocks'][1]['id'] ] === [ 'story', 'services' ]
	&& array_column( $story['cols'][0]['components'], 'name' ) === [ 'image' ] && array_column( $story['cols'][1]['components'], 'name' ) === [ 'card' ] && $cell['stack']['name'] === 'stack' && array_column( $cell['components'], 'name' ) === [ 'card' ] );
check( '...the card is a component node that carries its children, in order, as components, and no content: the title, the text and the button, each a node as it is in a column', array_keys( $cardNode ) === [ 'name', 'source', 'text', 'attributes', 'components' ]
	&& $cardNode['source'] === '' && $cardNode['text'] === null && array_column( $cardNode['components'], 'name' ) === [ 'card-title', 'text', 'button' ]
	&& array_column( $cardNode['components'], 'source' ) === [ '/template/page-home-card/story/title', '/template/page-home-card/story/text', '/template/page-home-card/story/button' ] && array_key_exists( 'content', $cardNode ) === false );
check( '...it has every attribute of its schema, in the order of the schema, the default where the call is silent - the ground and the padding are the two the file sets - and its children theirs', $cardNode['attributes'] === [ 'ground' => 'counter', 'padding' => '3', 'fill' => '0', 'vertical' => '', 'align' => '', 'background' => '', 'focus' => '', 'dim' => '0', 'borderless' => '0', 'class' => '' ]
	&& array_keys( $cardNode['attributes'] ) === array_keys( $registry['components']['card']['defaults'] ) && $cardNode['components'][0]['attributes'] === [ 'tag' => 'h3', 'style' => '', 'class' => '' ] && array_key_exists( 'components', $cardNode['components'][0] ) === false );
check( '...a plain component carries no list of children, whatever it is: only a component that holds components does', array_filter( array_merge( $story['cols'][0]['components'], $cardNode['components'], $cell['components'][0]['components'] ), static fn( array $node ): bool => array_key_exists( 'components', $node ) === true ) === [] );
check( '...in a loop the card stands in the content of the stack, once for each element: its picture is a field of the element, so is its title, and the stack holds the card and nothing else', $cell['components'][0]['attributes']['background'] === 'image' && $cell['components'][0]['attributes']['focus'] === '5' && $cell['components'][0]['attributes']['dim'] === '1' && $cell['components'][0]['attributes']['fill'] === '1'
	&& $cell['components'][0]['components'][0]['source'] === 'title' && $cell['stack']['source'] === '/services' && array_key_exists( 'components', $cell['stack'] ) === false );
check( '...the column beside the picture has a padding and a margin above and below it, the picture\'s column has none: the settings p, mt and mb are the classes nino-p-2, nino-mt-1 and nino-mb-1, and are not among the custom ones', [ $story['cols'][1]['p'], $story['cols'][1]['mt'], $story['cols'][1]['mb'] ] === [ '2', '1', '1' ] && $story['cols'][1]['custom'] === ''
	&& [ $story['cols'][0]['p'], $story['cols'][0]['mt'], $story['cols'][0]['mb'] ] === [ '', '', '' ] );
check( 'the model the panel\'s script test reads (fixtures/page-home-card.json) is what the Reader makes of the page with a card', json_decode( (string) file_get_contents( __DIR__. '/fixtures/page-home-card.json' ), true ) === array_replace( $cardModel, [ 'file' => 'page-home-card' ] ) );
check( 'writing the model gives the page back, byte for byte - the card on a line, its children one tab deeper on lines of their own, its closing tag at the depth of its call, in a loop one tab deeper still - and reading what was written gives the model', $write( $cardModel ) === $cardFixture && $read( $write( $cardModel ) ) === $cardModel && $write( $read( $write( $cardModel ) ) ) === $cardFixture );

// A card written by hand is read all the same, and written in the one form: its attributes in the order of the schema, its children and its closing tag on lines of their own
$handCard = $page( $section( "[card padding=\"3\" ground=\"counter\"][card-title /template/page-x/a/title][text /template/page-x/a/text][/card]" ) );
$handNode = $read( $handCard )['blocks'][0]['cols'][0]['components'][0];
check( 'a card whose attributes are in another order and whose children stand on its line is read as the card it is, and written in the canonical form', $handNode['attributes']['ground'] === 'counter' && $handNode['attributes']['padding'] === '3' && array_column( $handNode['components'], 'name' ) === [ 'card-title', 'text' ]
	&& $write( $read( $handCard ) ) === $inWrap( $page( $section( "[card ground=\"counter\" padding=\"3\"]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\t[text /template/page-x/a/text]\n\t\t\t[/card]" ) ) ) );

// A card with nothing between its tags is a card with no children, and is written with its closing tag on a line of its own
$emptyCard = $page( $section( '[card][/card]' ) );
$emptyNode = $read( $emptyCard )['blocks'][0]['cols'][0]['components'][0];
check( '...a card with nothing between its tags holds nothing: a list of no children, and a card that is written has its closing tag below its call, and reads again as the same card', $emptyNode['components'] === [] && $emptyNode['attributes'] === $registry['components']['card']['defaults']
	&& $write( $read( $emptyCard ) ) === $inWrap( $page( $section( "[card]\n\t\t\t[/card]" ) ) ) && $read( $write( $read( $emptyCard ) ) ) === $read( $emptyCard ) );
check( '...a call without its closing tag is a card with no children as well - the kernel reads to the end of the call', $read( $page( $section( '[card ground="alt"]' ) ) )['blocks'][0]['cols'][0]['components'][0]['components'] === [] );

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
	'a cover height on a section that is no cover' => [ "<section id=\"a\" class=\"nino-section\" data-cover-height=\"50\">\n\t". $row. "\n</section>", 'section-attribute', null, 'data-cover-height' ],
	'a cover height on a parallax that is no cover' => [ "<section id=\"a\" class=\"nino-section nino-parallex\" data-cover-height=\"50\">\n\t". $row. "\n</section>", 'section-attribute', null, 'data-cover-height' ],
	'a background of two pictures'				=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-x/a/one alt=\"\"][image /template/page-x/a/two alt=\"\"]</div>\n\t". $row. "\n</section>", 'background-image' ],
	'a background that has a class of its own' => [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-section-bg my-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t". $row. "\n</section>", 'background-class', null, 'my-bg' ],
	// The picture behind a column: the first thing in it, once, and in the shape of the one behind a section
	'a background of a column after a component' => [ $section( "[title /template/page-x/a/title]\n\t\t\t<div class=\"nino-grid-bg\">[image /template/page-x/a/one alt=\"\"]</div>" ), 'grid-background' ],
	'a background of a column after a loop'	=> [ $section( "[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]\n\t\t\t<div class=\"nino-grid-bg\">[image /template/page-x/a/one alt=\"\"]</div>" ), 'grid-background' ],
	'a second background in a column'			=> [ $section( "<div class=\"nino-grid-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t\t<div class=\"nino-grid-bg\">[image /template/page-x/a/two alt=\"\"]</div>\n\t\t\t[title /template/page-x/a/title]" ), 'grid-background' ],
	'a background of a column that has a class of its own' => [ $section( "<div class=\"nino-grid-bg my-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t\t[title /template/page-x/a/title]" ), 'background-class', null, 'my-bg' ],
	'a background of a column that has an attribute of its own' => [ $section( "<div class=\"nino-grid-bg\" id=\"bg\">[image /template/page-x/a/one alt=\"\"]</div>" ), 'background-class', null, 'id' ],
	'a background of a column of two pictures' => [ $section( "<div class=\"nino-grid-bg\">[image /template/page-x/a/one alt=\"\"][image /template/page-x/a/two alt=\"\"]</div>" ), 'background-image' ],
	'a background of a column with an alt text' => [ $section( "<div class=\"nino-grid-bg\">[image /template/page-x/a/one alt=\"A house\"]</div>" ), 'background-image' ],
	'a background of a column that is no slot' => [ $section( "<div class=\"nino-grid-bg\">[image photo alt=\"\"]</div>" ), 'background-image' ],
	'a background of a column that is no image call' => [ $section( "<div class=\"nino-grid-bg\">text</div>" ), 'background-image' ],
	'the background of a section in a column' => [ $section( "<div class=\"nino-section-bg\">[image /template/page-x/a/one alt=\"\"]</div>" ), 'col-markup' ],
	// What a loop takes: the grid, and no card - the cell is a div, and a card is a component in the content
	'the card attribute a loop had before the card was a component' => [ $section( "[stack /services card=\"alt\"]\n\t\t\t\t[title title]\n\t\t\t[/stack]" ), 'unknown-attribute', null, 'card' ],
	'an attribute of the grid on a loop that has no grid' => [ $section( "[slider /services cols=\"100 50 33\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]" ), 'unknown-attribute', null, 'cols' ],
	// A component that holds components holds the calls of components that hold none, and nothing else
	'a loop in a card'								=> [ $section( "[card]\n\t\t\t\t[stack /services]\n\t\t\t\t\t[title title]\n\t\t\t\t[/stack]\n\t\t\t[/card]" ), 'holder-stack' ],
	'a loop after the calls of a card'		=> [ $section( "[card]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\t[stack /services]\n\t\t\t\t\t[title title]\n\t\t\t\t[/stack]\n\t\t\t[/card]" ), 'holder-stack' ],
	'a card in a card'								=> [ $section( "[card]\n\t\t\t\t[card ground=\"alt\"]\n\t\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\t[/card]\n\t\t\t[/card]" ), 'holder-in-holder' ],
	'a card in a card, the inner one closed first' => [ $section( "[card]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\t[card][/card]\n\t\t\t[/card]" ), 'holder-in-holder' ],
	'a card in a card in a loop'			=> [ $section( "[stack /services]\n\t\t\t\t[card]\n\t\t\t\t\t[card]\n\t\t\t\t\t\t[card-title title]\n\t\t\t\t\t[/card]\n\t\t\t\t[/card]\n\t\t\t[/stack]" ), 'holder-in-holder' ],
	'markup in a card'							=> [ $section( "[card]\n\t\t\t\t<p>Hello</p>\n\t\t\t[/card]" ), 'holder-content', null, 'card' ],
	'text next to the calls of a card'	=> [ $section( "[card]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\tAnd more\n\t\t\t[/card]" ), 'holder-content', null, 'And more' ],
	'a fill in a card'							=> [ $section( "[card]\n\t\t\t\t[[/template/page-x/a/title]]\n\t\t\t[/card]" ), 'holder-content' ],
	'markup in a card in a loop'			=> [ $section( "[stack /services]\n\t\t\t\t[card]\n\t\t\t\t\t<p>Hello</p>\n\t\t\t\t[/card]\n\t\t\t[/stack]" ), 'holder-content', null, 'card' ],
	'a shortcode that is no component in a card' => [ $section( "[card]\n\t\t\t\t[osm lat=\"1\"]\n\t\t\t[/card]" ), 'unknown-shortcode', null, 'osm' ],
	'content on a call in a card'		=> [ $section( "[card]\n\t\t\t\t[title /template/page-x/a/title]text[/title]\n\t\t\t[/card]" ), 'component-content', null, 'title' ],
	'a call inside the content of a call in a card' => [ $section( "[card]\n\t\t\t\t[html]<p>[button /template/page-x/a/button]</p>[/html]\n\t\t\t[/card]" ), 'nested-call', null, 'html' ],
	'an attribute the card has not'		=> [ $section( '[card colour="red"]' ), 'unknown-attribute', null, 'colour' ],
	'a value outside the options of the card' => [ $section( '[card ground="neon"]' ), 'attribute-value', null, 'ground' ],
	'a fixed text on a card'					=> [ $section( '[card text="x"]' ), 'unknown-attribute', null, 'text' ],
	'a source on a card'						=> [ $section( '[card /template/page-x/a/title]' ), 'call-arguments' ],
	'a card next to a loop'					=> [ $section( "[card]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t[/card]\n\t\t\t[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]" ), 'stack-neighbour' ],
	// What a call is made of
	'a value outside its options'					=> [ $section( '[title /template/page-x/a/title tag="h9"]' ), 'attribute-value', null, 'tag' ],
	'an attribute the component has not'	=> [ $section( '[title /template/page-x/a/title colour="red"]' ), 'unknown-attribute', null, 'colour' ],
	'two spaces between the arguments'		=> [ $section( '[title /template/page-x/a/title  tag="h4"]' ), 'call-arguments' ],
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

// Every reason has its words in both languages of the panel, with a place for the detail where the English sentence has one
$reasonWords = [ 'en_US' => (array) include $dir. '/text/en_US.php', 'de_DE' => (array) include $dir. '/text/de_DE.php' ];
$reasonsWrong = [];

foreach( \Nino\Modules\Builder\Reader::REASONS as $code => $sentence )
	foreach( $reasonWords as $locale => $words )
		if( is_string( $words['[[/_admin/builder/reason/'. $code. ']]'] ?? null ) === false || substr_count( $words['[[/_admin/builder/reason/'. $code. ']]'], '%s' ) !== substr_count( $sentence, '%s' ) )
			$reasonsWrong[] = $locale. ' '. $code;

check( 'every reason a section is not read for has its words in both languages of the panel, with a place for the detail where the sentence has one - the background of a column, which is not the first thing in it, among them: '. count( \Nino\Modules\Builder\Reader::REASONS ). ' reasons'. ( $reasonsWrong === [] ? '' : ' - '. implode( ', ', $reasonsWrong ) ),
	$reasonsWrong === [] && isset( \Nino\Modules\Builder\Reader::REASONS['grid-background'] ) === true && $reasonWords['en_US']['[[/_admin/builder/reason/grid-background]]'] !== $reasonWords['de_DE']['[[/_admin/builder/reason/grid-background]]'] );

// The line is the line of the file, where the reading failed
$three = $read( $page( $section( '[title /template/page-x/a/title]', 'good' ), $section( '[osm lat="1"]', 'bad' ) ) );
check( 'the reason names the line of the failure: the call on the twelfth line of a block that starts on the ninth',
	array_column( $three['blocks'], 'kind' ) === [ 'section', 'html' ] && $three['blocks'][1]['reason']['line'] === 12 );
check( '...and the sentence says what the kernel knows no component by', str_contains( $three['blocks'][1]['reason']['text'], 'osm' ) === true );

// Markup in a card: the sentence has two places, the card and the markup, and the detail holds both
$holding = $read( $page( $section( "[card]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\t<p>Hello</p>\n\t\t\t[/card]", 'holding' ) ) )['blocks'][0];
check( 'the reason of markup next to the calls of a card names the line of the markup, and is told with two places - the card and the markup -, which its detail holds with a line feed between',
	$holding['reason']['code'] === 'holder-content' && $holding['reason']['line'] === 6 && $holding['reason']['detail'] === "card\n<p>Hello</p>" && $holding['reason']['text'] === 'the component card holds markup next to its component calls: <p>Hello</p>'
	&& substr_count( \Nino\Modules\Builder\Reader::REASONS['holder-content'], '%s' ) === 2 && substr_count( \Nino\Modules\Builder\Reader::REASONS['holder-stack'], '%s' ) === 0 && substr_count( \Nino\Modules\Builder\Reader::REASONS['holder-in-holder'], '%s' ) === 0 );
$holdingLong = $read( $page( $section( "[card]\n\t\t\t\t<p>Hello\n\t\t\t\t   there, and more of it</p>\n\t\t\t[/card]", 'holding' ) ) )['blocks'][0];
check( '...the markup in it is one line of white space, so that a second place takes it whole, and a sentence with one place takes its detail as it is, whatever it holds', $holdingLong['reason']['detail'] === "card\n<p>Hello there, a" && $holdingLong['reason']['text'] === 'the component card holds markup next to its component calls: <p>Hello there, a'
	&& $read( $page( $section( "<p>Hello\n\t\t\t\tthere</p>", 'holding' ) ) )['blocks'][0]['reason']['text'] === "the column holds markup next to its component calls: <p>Hello\n\t\t\t\tthere</p>" );

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
	[ 'name' => 'title', 'source' => '/template/page-x/a/title', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ] ],
	[ 'name' => 'title', 'source' => '/template/page-x/a/other', 'text' => null, 'attributes' => [ 'tag' => 'h4', 'style' => 'quiet', 'class' => 'big' ] ],
	[ 'name' => 'button', 'source' => '', 'text' => 'Go', 'attributes' => [ 'href' => '#top' ] ],
	[ 'name' => 'html', 'source' => '', 'text' => null, 'attributes' => [], 'content' => '<p>Free</p>' ],
	[ 'name' => 'spacer', 'source' => '', 'text' => null, 'attributes' => [ 'size' => 2 ] ],
];
$written = $write( [ 'blocks' => [ $changed ] ] );

check( 'an attribute is written where it differs from the schema, and only there - not a default, not an empty one, an int or a bool as its string', str_contains( $written, "\t\t\t[title /template/page-x/a/title]\n" ) === true
	&& str_contains( $written, '[title /template/page-x/a/other tag="h4" style="quiet" class="big"]' ) === true
	&& str_contains( $written, '[button text="Go" href="#top"]' ) === true && str_contains( $written, '[spacer]' ) === true && str_contains( $written, 'size=' ) === false );
check( 'a component with a content is its call, the content and its closing call, on one line', str_contains( $written, "\t\t\t[html]<p>Free</p>[/html]\n" ) === true );
check( '...and what is written reads back as what was meant', ( static function() use ( $read, $written ): bool {
	$col = $read( $written )['blocks'][0]['cols'][0]['components'];
	return $col[1]['attributes']['tag'] === 'h4' && $col[2]['text'] === 'Go' && $col[3]['content'] === '<p>Free</p>' && $col[4]['attributes']['size'] === '2';
} )() );

$styled = $blank;
$styled['settings'] = [
	'row' => 'narrow', 'rowAlign' => 'bottom', 'fullwidth' => true, 'fullheight' => true, 'color' => 'tint', 'border' => '2', 'borderPrimary' => true, 'cover' => 60, 'parallax' => true, 'dim' => true, 'contentPos' => 'bottom',
	'mt' => '1', 'mb' => '0', 'pt' => '3', 'pb' => '6', 'text' => 'right', 'vpa' => 'zoom-soft', 'vpaSpeed' => 'slow', 'vpaMode' => 'repeat', 'vpaDelay' => '200ms', 'vpaDuration' => '1.5s',
	'custom' => 'my-section other', 'rowCustom' => 'my-row',
] + $blank['settings'];
$styled['cols'][0] = [ 'width' => [ 's' => 100, 'm' => 50, 'l' => 33 ], 'hidden' => [ 's' => true ], 'color' => 'dark', 'dim' => true, 'background' => [ 'slot' => '/template/page-x/a/photo', 'focus' => 8 ],
	'text' => 'center', 'stackAlign' => 'end', 'stackGap' => '4', 'vpa' => '', 'custom' => 'my-col' ] + $blank['cols'][0];
$styled['background'] = [ 'slot' => '/template/page-x/a/background', 'focus' => null ];
$once = $write( [ 'blocks' => [ $styled ] ] );

check( 'a section\'s settings are its classes, in one order: the full width, the full height, the colour, the border and its brand colour, the cover and its scrim, the parallax, the place of the content, the spacing, the text, the animation, the custom ones - and its attributes the height of the cover, then the animation\'s',
	str_contains( $once, '<section id="a" class="nino-section nino-section--fullwidth nino-section--fullheight nino-section--tint nino-section--border-2 nino-section--border-primary nino-cover nino-cover--dim nino-parallex nino-cover-bottom nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-text-right nino-vpa nino-vpa--zoom-soft nino-vpa--speed-slow nino-vpa--repeat my-section other" data-cover-height="60" data-vpa-delay="200ms" data-vpa-duration="1.5s">' ) === true );
check( '...and the row\'s and the column\'s: the widths, the hidden ones, the ground and the dimming, the alignment and the gap of the components, the text, the animation, the custom ones', str_contains( $once, '<div class="nino-grid-row nino-grid-row--narrow nino-grid-bottom my-row">' ) === true
	&& str_contains( $once, '<div class="nino-grid-s-100 nino-grid-m-50 nino-grid-l-33 nino-hide-s nino-grid--dark nino-grid--dim nino-stack-end nino-stack-gap-4 nino-text-center nino-vpa my-col">' ) === true );
check( '...the picture behind the column is the first thing in it, a block of its own with the focus and the image of its slot with an empty alt', str_contains( $once, "my-col\">\n\t\t\t<div class=\"nino-grid-bg nino-img-focus--8\">[image /template/page-x/a/photo alt=\"\"]</div>\n\t\t\t[title " ) === true );
check( '...a background without a focus is the block and the image, with an empty alt', str_contains( $once, "\t<div class=\"nino-section-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n" ) === true );
$sorted = static function( mixed $value ) use ( &$sorted ): mixed {
	if( is_array( $value ) === false )
		return $value;
	ksort( $value );
	return array_map( $sorted, $value );
};
check( '...and every setting is read back as it was set', $sorted( $read( $once )['blocks'][0] ) === $sorted( $styled ) );

// The cover, the parallax and the scrim over their picture, written from the settings and read back as they were set
$coverWritten	= static fn( array $set ): string => $write( [ 'blocks' => [ array_replace( $blank, [ 'settings' => array_replace( $blank['settings'], $set ) ] ) ] ] );
$coverBack		= static fn( array $set ): bool => $read( $coverWritten( $set ) )['blocks'][0]['settings'] === array_replace( $blank['settings'], $set );

check( 'a cover is written as nino-cover and the height it has, at 90 as well, in an attribute of its own - and reads back as it was set', str_contains( $coverWritten( [ 'cover' => 90 ] ), '<section id="a" class="nino-section nino-cover" data-cover-height="90">' ) === true
	&& $coverBack( [ 'cover' => 90 ] ) === true && str_contains( $coverWritten( [] ), 'data-cover-height' ) === false && str_contains( $coverWritten( [] ), 'nino-cover' ) === false );
check( '...a cover with the scrim over its picture is nino-cover and nino-cover--dim, and reads back as it was set', str_contains( $coverWritten( [ 'cover' => 60, 'dim' => true ] ), '<section id="a" class="nino-section nino-cover nino-cover--dim" data-cover-height="60">' ) === true
	&& $coverBack( [ 'cover' => 60, 'dim' => true ] ) === true );
check( '...a parallax and a scrim without a cover are nino-parallex and nino-parallex--dim, and read back as they were set', str_contains( $coverWritten( [ 'parallax' => true, 'dim' => true ] ), '<section id="a" class="nino-section nino-parallex nino-parallex--dim">' ) === true
	&& $coverBack( [ 'parallax' => true, 'dim' => true ] ) === true && $coverBack( [ 'parallax' => true ] ) === true );
check( '...a section that is a cover and a parallax has the one scrim, the cover\'s: nino-cover nino-cover--dim nino-parallex, and reads back as it was set', str_contains( $coverWritten( [ 'cover' => 90, 'parallax' => true, 'dim' => true ] ), '<section id="a" class="nino-section nino-cover nino-cover--dim nino-parallex" data-cover-height="90">' ) === true
	&& $coverBack( [ 'cover' => 90, 'parallax' => true, 'dim' => true ] ) === true );
check( '...the place of the content is nino-cover-top or nino-cover-bottom, after the parallax, and the middle is no class at all - each reads back as it was set', str_contains( $coverWritten( [ 'cover' => 90, 'parallax' => true, 'contentPos' => 'top' ] ), 'class="nino-section nino-cover nino-parallex nino-cover-top" data-cover-height="90">' ) === true
	&& str_contains( $coverWritten( [ 'parallax' => true, 'contentPos' => 'bottom' ] ), 'class="nino-section nino-parallex nino-cover-bottom">' ) === true && str_contains( $coverWritten( [ 'cover' => 90 ] ), 'nino-cover-' ) === false
	&& $coverBack( [ 'cover' => 90, 'parallax' => true, 'contentPos' => 'top' ] ) === true && $coverBack( [ 'parallax' => true, 'contentPos' => 'bottom' ] ) === true );
check( '...a scrim over a section that is neither a cover nor a parallax darkens nothing: no class is written for it', str_contains( $coverWritten( [ 'dim' => true ] ), '<section id="a" class="nino-section">' ) === true );
check( '...and neither is the place of the content on such a section, where the row stands in the flow: no class, and the middle read back', str_contains( $coverWritten( [ 'contentPos' => 'top' ] ), 'nino-cover-' ) === false
	&& $read( $coverWritten( [ 'contentPos' => 'top' ] ) )['blocks'][0]['settings']['contentPos'] === '' );

// The ground of a column, its dimming and the picture behind it: classes of the column, and a block of its own that is the first thing in it
$groundOf		= static fn( string $classes, string $inside = '[title /template/page-x/a/title]' ): string => $page( $section( $inside, 'a', 'nino-section', $classes ) );
$columnOf		= static fn( string $source ): array => $read( $source )['blocks'][0]['cols'][0];
$photo			= "<div class=\"nino-grid-bg nino-img-focus--3\">[image /template/page-x/a/photo alt=\"\"]</div>";
$groundWrong	= [];

foreach( \Nino\Modules\Builder\Reader::COL_COLOR as $color ) {

	$source	= $groundOf( 'nino-grid-100 nino-grid--'. $color );
	$ground	= $columnOf( $source );

	if( $ground['color'] !== $color || $ground['dim'] !== false || $ground['background'] !== null || $ground['custom'] !== '' || $write( $read( $source ) ) !== $inWrap( $source ) || $read( $write( $read( $source ) ) ) !== $read( $source ) )
		$groundWrong[] = $color;
}

check( 'a column has a ground of its own - the six of a section, and counter, the one that sets it off from its section -, read from nino-grid--<x> and written back byte for byte: all '. count( \Nino\Modules\Builder\Reader::COL_COLOR ). ' of them'. ( $groundWrong === [] ? '' : ' - '. implode( ', ', $groundWrong ) ),
	$groundWrong === [] && \Nino\Modules\Builder\Reader::COL_COLOR === [ 'counter', 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ] );

$mixedUp = $columnOf( $groundOf( 'nino-text-left nino-grid--alt my-col nino-hide-s nino-stack-gap-3 nino-grid-s-100 nino-stack-center nino-grid-m-50 nino-grid-l-33' ) );
check( '...the classes of a column are read in any order and written in one: the widths, the hidden ones, the ground, the dimming, the alignment and the gap of the components, the text, the animation, the custom ones',
	$mixedUp['color'] === 'alt' && $mixedUp['custom'] === 'my-col' && str_contains( $write( [ 'blocks' => [ $read( $groundOf( 'nino-text-left nino-grid--alt my-col nino-hide-s nino-stack-gap-3 nino-grid-s-100 nino-stack-center nino-grid-m-50 nino-grid-l-33' ) )['blocks'][0] ] ] ),
		'<div class="nino-grid-s-100 nino-grid-m-50 nino-grid-l-33 nino-hide-s nino-grid--alt nino-stack-center nino-stack-gap-3 nino-text-left my-col">' ) === true );

$second = $columnOf( $groundOf( 'nino-grid-100 nino-grid--alt nino-grid--dark' ) );
check( '...a second ground is a class of its own: kept, not lost', $second['color'] === 'alt' && $second['custom'] === 'nino-grid--dark' && $write( $read( $groundOf( 'nino-grid-100 nino-grid--alt nino-grid--dark' ) ) ) === $inWrap( $groundOf( 'nino-grid-100 nino-grid--alt nino-grid--dark' ) ) );

$pictureSource	= $groundOf( 'nino-grid-100 nino-grid--dark nino-grid--dim', $photo. "\n\t\t\t[title /template/page-x/a/title]" );
$picture				= $columnOf( $pictureSource );
check( 'a column with a ground and a picture: the picture is the first thing in it, one image call of a slot with an empty alt and its focus, the dimming is a switch that goes with it - and it is written back byte for byte',
	$picture['color'] === 'dark' && $picture['dim'] === true && $picture['background'] === [ 'slot' => '/template/page-x/a/photo', 'focus' => 3 ] && $picture['custom'] === '' && array_column( $picture['components'], 'name' ) === [ 'title' ]
	&& $write( $read( $pictureSource ) ) === $inWrap( $pictureSource ) && $read( $write( $read( $pictureSource ) ) ) === $read( $pictureSource ) );

$bare = $groundOf( 'nino-grid-100', "<div class=\"nino-grid-bg\">[image /template/page-x/a/photo alt=\"\"]</div>" );
check( '...a picture needs no ground, no focus and no component: a column that holds the picture alone is a column', $columnOf( $bare )['background'] === [ 'slot' => '/template/page-x/a/photo', 'focus' => null] && $columnOf( $bare )['color'] === '' && $columnOf( $bare )['dim'] === false
	&& $columnOf( $bare )['components'] === [] && $write( $read( $bare ) ) === $inWrap( $bare ) );

$looped = $groundOf( 'nino-grid-100 nino-grid--tint', $photo. "\n\t\t\t[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]" );
check( '...and stands before the loop of its column, which is still the only thing besides it', $columnOf( $looped )['stack']['name'] === 'stack' && $columnOf( $looped )['background']['slot'] === '/template/page-x/a/photo' && array_column( $columnOf( $looped )['components'], 'name' ) === [ 'title' ]
	&& $write( $read( $looped ) ) === $inWrap( $looped ) );

$anyOrder = $groundOf( 'nino-grid--dim nino-grid--dark nino-grid-100', $photo. "\n\t\t\t[title /template/page-x/a/title]" );
check( '...a dimmed column is dimmed whatever the order of its classes is, and is written in one', $columnOf( $anyOrder ) === $picture && $write( $read( $anyOrder ) ) === $inWrap( $pictureSource ) );

$dimmed = $groundOf( 'nino-grid-100 nino-grid--dim' );
check( 'nino-grid--dim on a column with no picture is a class like any other, to be kept: it stays among the custom ones and is written back where it stood', $columnOf( $dimmed )['dim'] === false && $columnOf( $dimmed )['custom'] === 'nino-grid--dim'
	&& $write( $read( $dimmed ) ) === $inWrap( $dimmed ) );

$dimAmong		= [ 'nino-grid--dim my-col', 'my-col nino-grid--dim', 'my-col nino-grid--dim other-col' ];
$dimAmongWrong	= [];

foreach( $dimAmong as $among ) {

	$source = $groundOf( 'nino-grid-100 '. $among );

	if( $columnOf( $source )['dim'] !== false || $columnOf( $source )['custom'] !== $among || $write( $read( $source ) ) !== $inWrap( $source ) )
		$dimAmongWrong[] = $among;
}

$dimBeside = $groundOf( 'nino-grid-100 nino-grid--dim my-col', $photo. "\n\t\t\t[title /template/page-x/a/title]" );
check( '...and among other custom classes it keeps its place - before them, between them, behind them - written back byte for byte, a class after it stays after it; with a picture it is the switch, and the classes beside it stay'. ( $dimAmongWrong === [] ? '' : ' - '. implode( ', ', $dimAmongWrong ) ),
	$dimAmongWrong === [] && $columnOf( $dimBeside )['dim'] === true && $columnOf( $dimBeside )['custom'] === 'my-col' && $write( $read( $dimBeside ) ) === $inWrap( $dimBeside ) );

$dimless = $read( $pictureSource )['blocks'][0];
$dimless['cols'][0]['background'] = null;
$noSlot = $read( $pictureSource )['blocks'][0];
$noSlot['cols'][0]['background'] = [ 'slot' => '', 'focus' => null ];
check( '...and the model\'s dimming is written only while the column has a picture: without one, or with one that names no slot, the class is not written and the picture is not either', str_contains( $write( [ 'blocks' => [ $dimless ] ] ), 'nino-grid--dim' ) === false
	&& str_contains( $write( [ 'blocks' => [ $dimless ] ] ), 'nino-grid-bg' ) === false && str_contains( $write( [ 'blocks' => [ $noSlot ] ] ), 'nino-grid--dim' ) === false && str_contains( $write( [ 'blocks' => [ $noSlot ] ] ), 'nino-grid-bg' ) === false
	&& str_contains( $write( [ 'blocks' => [ $dimless ] ] ), 'nino-grid--dark' ) === true );

$made = $read( $pictureSource )['blocks'][0];
$made['cols'][0]['background']['create'] = [ 'label' => 'Photo', 'width' => 800, 'height' => 600, 'alt' => [ 'en_US' => 'A house' ] ];
check( '...what only the panel sends with the picture - the mark that makes its slot at save - is not written, and not read', $write( [ 'blocks' => [ $made ] ] ) === $write( [ 'blocks' => [ $read( $pictureSource )['blocks'][0] ] ] ) && isset( $read( $write( [ 'blocks' => [ $made ] ] ) )['blocks'][0]['cols'][0]['background']['create'] ) === false );

$left = $read( $fixture )['blocks'];
foreach( $left as $b => $block )
	foreach( $block['cols'] ?? [] as $c => $col )
		unset( $left[$b]['cols'][$c]['color'], $left[$b]['cols'][$c]['dim'], $left[$b]['cols'][$c]['background'] );
check( 'a column with no ground and no picture is written as it always was: a model without the three keys is the example page, byte for byte', $write( [ 'name' => 'Home', 'header' => 'html-header', 'footer' => 'html-footer', 'blocks' => $left ] ) === $wrapped( $fixture ) && $model['blocks'][0]['cols'][0]['color'] === '' && $model['blocks'][0]['cols'][0]['dim'] === false
	&& $model['blocks'][0]['cols'][0]['background'] === null );

// The border of a section is a strength and a switch: the brand colour, with a strength or without
$borderOf		= static fn( string $classes ): array => $read( $page( $section( '[title /template/page-x/a/title]', 'a', $classes ) ) )['blocks'][0]['settings'];
$borderCases	= [
	'a strength alone'							=> [ 'nino-section nino-section--border-1', '1', false ],
	'the strong one'								=> [ 'nino-section nino-section--border-3', '3', false ],
	'a strength and the brand colour'				=> [ 'nino-section nino-section--border-2 nino-section--border-primary', '2', true ],
	'the brand colour alone'						=> [ 'nino-section nino-section--border-primary', '', true ],
];
$borderWrong	= [];

foreach( $borderCases as $label => [ $classes, $border, $primary ] ) {

	$source = $page( $section( '[title /template/page-x/a/title]', 'a', $classes ) );
	$got		= $borderOf( $classes );

	if( $got['border'] !== $border || $got['borderPrimary'] !== $primary || $got['custom'] !== '' || $write( $read( $source ) ) !== $inWrap( $source ) )
		$borderWrong[] = $label;
}

check( 'the border of a section is a strength - nino-section--border-1 to 3 - and a switch, nino-section--border-primary: each is read and written back byte for byte, the brand colour alone as the one class it is'. ( $borderWrong === [] ? '' : ' - '. implode( ', ', $borderWrong ) ),
	$borderWrong === [] && \Nino\Modules\Builder\Reader::BORDER === [ '1', '2', '3' ] && \Nino\Modules\Builder\Reader::sectionDefaults()['borderPrimary'] === false );

$reversed = $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-section--border-primary nino-section--border-3' ) );
check( '...the two classes are read in either order and written in one: the strength, then the brand colour', $borderOf( 'nino-section nino-section--border-primary nino-section--border-3' )['border'] === '3' && $borderOf( 'nino-section nino-section--border-primary nino-section--border-3' )['borderPrimary'] === true
	&& $write( $read( $reversed ) ) === $inWrap( $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-section--border-3 nino-section--border-primary' ) ) ) );
check( '...a second strength is a class of its own, kept: it cannot be said in a setting that has one place', $borderOf( 'nino-section nino-section--border-1 nino-section--border-3' )['border'] === '1' && $borderOf( 'nino-section nino-section--border-1 nino-section--border-3' )['custom'] === 'nino-section--border-3'
	&& $borderOf( 'nino-section nino-section--border-4' )['border'] === '' && $borderOf( 'nino-section nino-section--border-4' )['custom'] === 'nino-section--border-4' );

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
check( 'a class the builder does not know is a custom one, and so are a second colour and a scrim on a section that is neither a cover nor a parallax - nothing is lost',
	$custom['settings']['color'] === 'black' && $custom['settings']['custom'] === 'my-own nino-section--alt nino-cover--dim' && $custom['cols'][0]['custom'] === 'u-pad' );
check( '...and the custom classes come last when it is written, and the same again when it is read', str_contains( $write( [ 'blocks' => [ $custom ] ] ), 'class="nino-section nino-section--black my-own nino-section--alt nino-cover--dim"' ) === true
	&& $read( $write( [ 'blocks' => [ $custom ] ] ) )['blocks'][0] === $custom );

// A section is a cover by a class of its own and a height in an attribute, a parallax by another class; the scrim over their picture and the place of the content are classes too
$coverSource	= static fn( string $classes, string $attributes = '' ): string => $page( '<section id="a" class="'. $classes. '"'. $attributes. ">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-x/a/title]\n\t\t</div>\n\t</div>\n</section>" );
$coverOf		= static fn( string $classes, string $attributes = '' ): array => $read( $coverSource( $classes, $attributes ) )['blocks'][0]['settings'];

check( 'a section with nino-cover and no data-cover-height is a cover of 90, the height Nino.ui.js gives a cover that names none - a section without the class is no cover', $coverOf( 'nino-section nino-cover' )['cover'] === 90 && $coverOf( 'nino-section nino-cover' )['custom'] === ''
	&& $coverOf( 'nino-section' )['cover'] === null && $coverOf( 'nino-section' )['parallax'] === false );
check( '...with data-cover-height it is a cover of the height the attribute names', $coverOf( 'nino-section nino-cover', ' data-cover-height="50"' )['cover'] === 50 && $coverOf( 'nino-section nino-cover', ' data-cover-height="9999"' )['cover'] === 9999 );
check( 'a section with nino-parallex is a parallax and no cover, and with nino-parallex--dim it has the scrim', $coverOf( 'nino-section nino-parallex' )['parallax'] === true && $coverOf( 'nino-section nino-parallex' )['cover'] === null && $coverOf( 'nino-section nino-parallex' )['dim'] === false
	&& $coverOf( 'nino-section nino-parallex nino-parallex--dim' )['parallax'] === true && $coverOf( 'nino-section nino-parallex nino-parallex--dim' )['dim'] === true && $coverOf( 'nino-section nino-parallex nino-parallex--dim' )['custom'] === '' );

$coverAndParallax = $coverOf( 'nino-section nino-cover nino-parallex nino-parallex--dim' );
check( 'nino-cover and nino-parallex on one section are two settings and both are read - the scrim of a parallax that is a cover is the cover\'s to give, so nino-parallex--dim is a custom class, kept', $coverAndParallax['cover'] === 90 && $coverAndParallax['parallax'] === true
	&& $coverAndParallax['dim'] === false && $coverAndParallax['custom'] === 'nino-parallex--dim' );
check( '...and nino-cover--dim is the scrim of a section that is a cover, a parallax as well or not', $coverOf( 'nino-section nino-cover nino-cover--dim' )['dim'] === true && $coverOf( 'nino-section nino-cover nino-cover--dim nino-parallex' )['dim'] === true
	&& $coverOf( 'nino-section nino-cover nino-cover--dim nino-parallex' )['parallax'] === true && $coverOf( 'nino-section nino-cover nino-cover--dim nino-parallex' )['custom'] === '' );
check( 'a scrim without what it darkens is a custom class, kept: nino-cover--dim on a parallax that is no cover, nino-parallex--dim on a section that is none', $coverOf( 'nino-section nino-parallex nino-cover--dim' )['dim'] === false && $coverOf( 'nino-section nino-parallex nino-cover--dim' )['custom'] === 'nino-cover--dim'
	&& $coverOf( 'nino-section nino-parallex--dim' )['dim'] === false && $coverOf( 'nino-section nino-parallex--dim' )['custom'] === 'nino-parallex--dim' );
check( 'nino-cover-top and nino-cover-bottom are the place of the content in a cover; nino-cover-center is a custom class, since it aligns the text of a .nino-cover-content, which the Builder never writes', $coverOf( 'nino-section nino-cover nino-cover-top' )['contentPos'] === 'top'
	&& $coverOf( 'nino-section nino-cover nino-cover-bottom' )['contentPos'] === 'bottom' && $coverOf( 'nino-section nino-cover' )['contentPos'] === '' && $coverOf( 'nino-section nino-cover nino-cover-center' )['contentPos'] === ''
	&& $coverOf( 'nino-section nino-cover nino-cover-center' )['custom'] === 'nino-cover-center' );
check( '...and on a section that is neither a cover nor a parallax nino-cover-top is idle: a custom class, kept', $coverOf( 'nino-section nino-cover-top' )['contentPos'] === '' && $coverOf( 'nino-section nino-cover-top' )['custom'] === 'nino-cover-top'
	&& $coverOf( 'nino-section nino-parallex nino-cover-top' )['contentPos'] === 'top' );

$hostile = $blank;
$hostile['settings']['custom'] = 'a" onmouseover="alert(1)';
check( 'a custom class is escaped where it is written, so it cannot leave the attribute', str_contains( $write( [ 'blocks' => [ $hostile ] ] ), 'onmouseover="' ) === false && str_contains( $write( [ 'blocks' => [ $hostile ] ] ), '&quot;' ) === true );

$stacked = $read( $page( "<section id=\"s\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[slider /services width=\"60%\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]\n\t\t</div>\n\t</div>\n</section>" ) )['blocks'][0];
check( 'a stack other than [stack] is read and written as its own, with the attributes of its schema', $stacked['kind'] === 'section' && $stacked['cols'][0]['stack']['name'] === 'slider' && $stacked['cols'][0]['stack']['attributes']['width'] === '60%'
	&& str_contains( $write( [ 'blocks' => [ $stacked ] ] ), "[slider /services width=\"60%\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]" ) === true );

// A loop is the grid and nothing about what a cell looks like: it has no card attribute, and a card is a component in its content, once for each element
$carded		= $page( $section( "[stack /services sort=\"title\" cols=\"100 50 50\" autoheight=\"1\"]\n\t\t\t\t[card ground=\"counter\" fill=\"1\"]\n\t\t\t\t\t[card-title title]\n\t\t\t\t[/card]\n\t\t\t[/stack]" ) );
$cards		= $read( $carded )['blocks'][0]['cols'][0];
check( 'a loop that holds a card is read and written like any other: the card is the content of the stack, one level deeper - its call at the depth of the components of a loop, its children one tab deeper still, its closing tag where its call is - and written back byte for byte',
	$cards['stack']['name'] === 'stack' && array_keys( $cards['stack']['attributes'] ) === [ 'sort', 'offset', 'limit', 'query', 'callback', 'locale', 'id', 'cols', 'gap', 'autoheight', 'class' ] && array_column( $cards['components'], 'name' ) === [ 'card' ]
	&& array_column( $cards['components'][0]['components'], 'source' ) === [ 'title' ] && $write( $read( $carded ) ) === $inWrap( $carded ) && $read( $write( $read( $carded ) ) ) === $read( $carded ) );

// The padding of a column and the margin above and below it: p, mt and mb, a step of the space scale each, classes of the column after the text and before the animation
$spaceWrong = [];

foreach( \Nino\Modules\Builder\Reader::COL_SPACING as $space )
	foreach( \Nino\Modules\Builder\Reader::STEPS as $step ) {

		$source	= $groundOf( 'nino-grid-100 nino-'. $space. '-'. $step );
		$got		= $columnOf( $source );
		$others	= array_diff( \Nino\Modules\Builder\Reader::COL_SPACING, [ $space ] );

		if( $got[$space] !== $step || array_filter( $others, static fn( string $other ): bool => $got[$other] !== '' ) !== [] || $got['custom'] !== '' || $write( $read( $source ) ) !== $inWrap( $source ) || $read( $write( $read( $source ) ) ) !== $read( $source ) )
			$spaceWrong[] = $space. '-'. $step;
	}

check( 'a column has a padding and a margin above and below it - p, mt and mb, each of the steps 0 to 6 of the space scale -, read from nino-p-<n>, nino-mt-<n> and nino-mb-<n> and written back byte for byte: all '. ( count( \Nino\Modules\Builder\Reader::COL_SPACING ) * count( \Nino\Modules\Builder\Reader::STEPS ) ). ' of them'. ( $spaceWrong === [] ? '' : ' - '. implode( ', ', $spaceWrong ) ),
	$spaceWrong === [] && \Nino\Modules\Builder\Reader::COL_SPACING === [ 'p', 'mt', 'mb' ] && \Nino\Modules\Builder\Reader::STEPS === [ '0', '1', '2', '3', '4', '5', '6' ]
	&& array_intersect_key( \Nino\Modules\Builder\Reader::colDefaults(), array_flip( [ 'p', 'mt', 'mb' ] ) ) === [ 'p' => '', 'mt' => '', 'mb' => '' ] );

$spaced = $groundOf( 'nino-mb-2 nino-text-center nino-p-1 nino-vpa nino-grid--alt nino-mt-3 nino-stack-gap-4 my-col nino-grid-100' );
check( '...the classes are read in any order and written in one: after the text, before the animation and the custom classes - the padding, the margin above, the margin below', $columnOf( $spaced )['p'] === '1' && $columnOf( $spaced )['mt'] === '3' && $columnOf( $spaced )['mb'] === '2' && $columnOf( $spaced )['custom'] === 'my-col'
	&& str_contains( $write( $read( $spaced ) ), '<div class="nino-grid-100 nino-grid--alt nino-stack-gap-4 nino-text-center nino-p-1 nino-mt-3 nino-mb-2 nino-vpa my-col">' ) === true );

$twiceP = $columnOf( $groundOf( 'nino-grid-100 nino-p-1 my-col nino-p-4' ) );
check( '...a second class of a kind is a class of its own, as it is in a section: kept, not lost - and so are a step that is none, the padding in two directions and the paddings of a section', $twiceP['p'] === '1' && $twiceP['custom'] === 'my-col nino-p-4'
	&& $write( $read( $groundOf( 'nino-grid-100 nino-p-1 my-col nino-p-4' ) ) ) === $inWrap( $groundOf( 'nino-grid-100 nino-p-1 my-col nino-p-4' ) )
	&& $columnOf( $groundOf( 'nino-grid-100 nino-p-7 nino-pt-2 nino-pb-1 nino-m-2 nino-mt-x' ) )['p'] === '' && $columnOf( $groundOf( 'nino-grid-100 nino-p-7 nino-pt-2 nino-pb-1 nino-m-2 nino-mt-x' ) )['custom'] === 'nino-p-7 nino-pt-2 nino-pb-1 nino-m-2 nino-mt-x'
	&& $read( $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-p-2 nino-pt-2' ) ) )['blocks'][0]['settings']['custom'] === 'nino-p-2' && $read( $page( $section( '[title /template/page-x/a/title]', 'a', 'nino-section nino-p-2 nino-pt-2' ) ) )['blocks'][0]['settings']['pt'] === '2' );

$unspaced = $read( $fixture )['blocks'];
foreach( $unspaced as $b => $block )
	foreach( $block['cols'] ?? [] as $c => $col )
		unset( $unspaced[$b]['cols'][$c]['p'], $unspaced[$b]['cols'][$c]['mt'], $unspaced[$b]['cols'][$c]['mb'] );
check( 'a column with none of the three is written as it always was: a model without them is the example page, byte for byte, and every existing fixture is read and written as it was',
	$write( [ 'name' => 'Home', 'header' => 'html-header', 'footer' => 'html-footer', 'blocks' => $unspaced ] ) === $wrapped( $fixture ) && $model['blocks'][0]['cols'][0]['p'] === '' && $model['blocks'][0]['cols'][0]['mt'] === '' && $model['blocks'][0]['cols'][0]['mb'] === ''
	&& $write( $wrapModel ) === $wrapFixture && $write( $vpaModel ) === $wrapFixture && $write( $read( $concept ) ) === $wrapped( $fixture ) );

// A card as the Writer writes it: every attribute, in the order of the schema and only where it differs from the default
$allSet = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => [ 'ground' => 'dark', 'padding' => '0', 'fill' => '1', 'vertical' => 'last-bottom', 'align' => 'center', 'background' => '/template/page-x/a/backdrop', 'focus' => '3', 'dim' => '1', 'borderless' => '1', 'class' => 'my-card' ],
	'components' => [
		[ 'name' => 'card-title', 'source' => '/template/page-x/a/title', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => 'quiet', 'class' => '' ] ],
		[ 'name' => 'button', 'source' => '', 'text' => 'Go', 'attributes' => [ 'href' => '#top', 'style' => '', 'size' => '', 'target' => '', 'class' => '' ] ],
	] ];
$cardBlock = $blank;
$cardBlock['cols'][0]['components'] = [ $allSet ];
$cardText = $write( [ 'blocks' => [ $cardBlock ] ] );
check( 'a card with every attribute set is its call with the attributes in the order of the schema - ground, padding, full height, vertical, horizontal, picture, focus, dimming, border, class -, its children one tab deeper on a line each and its closing tag at the depth of its call',
	str_contains( $cardText, "\t\t\t[card ground=\"dark\" padding=\"0\" fill=\"1\" vertical=\"last-bottom\" align=\"center\" background=\"/template/page-x/a/backdrop\" focus=\"3\" dim=\"1\" borderless=\"1\" class=\"my-card\"]\n"
		. "\t\t\t\t[card-title /template/page-x/a/title tag=\"h3\" style=\"quiet\"]\n\t\t\t\t[button text=\"Go\" href=\"#top\"]\n\t\t\t[/card]\n\t\t</div>\n" ) === true );
check( '...and what is written reads back as what was meant: the card with its attributes and its children, node for node', $read( $cardText )['blocks'][0]['cols'][0]['components'][0] === $allSet );

// In the content of a loop the card is one level deeper, and so are its children
$looped = $blank;
$looped['cols'][0]['stack'] = [ 'name' => 'stack', 'source' => '/services', 'attributes' => $registry['stacks']['stack']['defaults'] ];
$looped['cols'][0]['components'] = [ [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => array_replace( $registry['components']['card']['defaults'], [ 'fill' => '1' ] ),
	'components' => [ [ 'name' => 'card-title', 'source' => 'title', 'text' => null, 'attributes' => $registry['components']['card-title']['defaults'] ] ] ] ];
check( '...in the content of a loop the call of the card stands one tab deeper, its children one more and its closing tag with its call - and it reads back as the loop and the card it was', str_contains( $write( [ 'blocks' => [ $looped ] ] ),
	"\t\t\t[stack /services]\n\t\t\t\t[card fill=\"1\"]\n\t\t\t\t\t[card-title title]\n\t\t\t\t[/card]\n\t\t\t[/stack]\n" ) === true && $read( $write( [ 'blocks' => [ $looped ] ] ) )['blocks'][0] === $looped );

// A card that holds nothing, one the model gives no list of children, and a content that a card has none of
$bare = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => [ 'ground' => 'alt' ] ];
$strayed = $bare;
$strayed['content'] = '<p>Not a card\'s</p>';
$strayed['components'] = [];
$bareBlock = $blank;
$bareBlock['cols'][0]['components'] = [ $bare, $strayed ];
check( 'a card whose model has no list of children, or an empty one, is written with nothing between its tags, its closing tag below its call - and a content is not a card\'s: the Writer leaves it out',
	substr_count( $write( [ 'blocks' => [ $bareBlock ] ] ), "[card ground=\"alt\"]\n\t\t\t[/card]\n" ) === 2 && str_contains( $write( [ 'blocks' => [ $bareBlock ] ] ), 'Not a card' ) === false );

// A component that holds nothing is written as a call, whatever the model hands it
$plain = [ 'name' => 'title', 'source' => '/template/page-x/a/title', 'text' => null, 'attributes' => [ 'tag' => 'h3' ], 'components' => [ [ 'name' => 'text', 'source' => '/template/page-x/a/text', 'text' => null, 'attributes' => [] ] ] ];
$plainBlock = $blank;
$plainBlock['cols'][0]['components'] = [ $plain ];
check( 'a component whose schema does not say that it holds components is one line, a list of children or not: the Writer does not make a container of what is none', str_contains( $write( [ 'blocks' => [ $plainBlock ] ] ), "\t\t\t[title /template/page-x/a/title]\n\t\t</div>\n" ) === true && str_contains( $write( [ 'blocks' => [ $plainBlock ] ] ), '[/title]' ) === false );

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
	&& $registryAnswer['components']['title']['label'] === 'Title (Section)' && $registryAnswer['components']['title']['attributes']['tag']['label'] === 'Tag'
	&& $registryAnswer['components']['title']['defaults']['tag'] === 'h3' && $registryAnswer['stacks']['stack']['label'] === 'Stack' && $registryAnswer['stacks']['stack']['defaults']['cols'] === '100 50 33' );
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
check( '...a component says where it belongs (in) and what it draws (draws), as the kernel registered it: the title for a section, the title of a card for a card, the title of a hero for a hero, the button everywhere - and one that says neither carries neither',
	$registryAnswer['components']['title']['in'] === [ 'section' ] && $registryAnswer['components']['title']['draws'] === '{tag} .nino-section-title' && $registryAnswer['components']['card-title']['in'] === [ 'card' ]
	&& $registryAnswer['components']['hero-title']['in'] === [ 'hero' ] && $registryAnswer['components']['hero-subtitle']['draws'] === '{tag} .nino-hero-subtitle' && $registryAnswer['components']['button']['draws'] === 'a .nino-btn'
	&& array_key_exists( 'in', $registryAnswer['components']['button'] ) === false && array_key_exists( 'in', $registryAnswer['components']['text'] ) === false && array_key_exists( 'draws', $registryAnswer['components']['text'] ) === false
	&& array_key_exists( 'draws', $registryAnswer['stacks']['stack'] ) === false && $registryAnswer['components']['card-title']['label'] === 'Title (Card)' );
check( '...a component says what it holds (holds), the place of the components it takes as its children: the card holds the place card, is offered in a section and in a hero, and draws an article element - all three arrive as the kernel has them, and no other component carries holds, nor does a loop',
	$registryAnswer['components']['card']['in'] === [ 'section', 'hero' ] && $registryAnswer['components']['card']['draws'] === 'article .nino-card' && $registryAnswer['components']['card']['holds'] === 'card' && $registryAnswer['components']['card']['label'] === 'Card'
	&& $registryAnswer['components']['card']['source'] === 'content' && array_keys( $registryAnswer['components']['card']['attributes'] ) === [ 'ground', 'padding', 'fill', 'vertical', 'align', 'background', 'focus', 'dim', 'borderless' ]
	&& array_keys( array_filter( $registryAnswer['components'], static fn( array $schema ): bool => array_key_exists( 'holds', $schema ) === true ) ) === [ 'card' ] && array_filter( $registryAnswer['stacks'], static fn( array $schema ): bool => array_key_exists( 'holds', $schema ) === true || array_key_exists( 'in', $schema ) === true ) === []
	&& array_key_exists( 'card', $registryAnswer['stacks']['stack']['defaults'] ) === false && array_key_exists( 'card', $registryAnswer['stacks']['filter']['defaults'] ) === false );
check( '...and the entry of the card is the entry of the registry of the Components module but for its words, which are in the language of the workbench: the panel is handed nothing of its own making',
	array_diff_key( $registryAnswer['components']['card'], [ 'label' => 1, 'attributes' => 1 ] ) === array_diff_key( \Nino\Modules\Builder\Document::schemas( $appData )['components']['card'], [ 'label' => 1, 'attributes' => 1 ] ) );
check( '...the components and the stacks the panel\'s script test reads (fixtures/registry.json) are what the registry says', json_decode( (string) file_get_contents( __DIR__. '/fixtures/registry.json' ), true ) === json_decode( (string) json_encode( [ 'components' => $registryAnswer['components'], 'stacks' => $registryAnswer['stacks'] ] ), true ) );
check( '...the components stand in the order the kernel registers them, which is the order a column offers them, and the answer keeps it the way the panel reads it: the titles of each place first, then the text, the card, the button, the picture, HTML and the spacer',
	array_keys( (array) json_decode( (string) json_encode( $registryAnswer['components'] ), true ) ) === [ 'title', 'subtitle', 'card-title', 'card-subtitle', 'hero-title', 'hero-subtitle', 'text', 'card', 'button', 'image', 'html', 'spacer' ] );
$featured = $appData;
foreach( [ 'zulu', 'alpha' ] as $feature )
	\Nino\Modules\Components::addComponent( $featured, $feature, static fn( array &$appData, array $args ): string => '', [ 'label' => [ 'en_US' => ucfirst( $feature ) ], 'source' => 'none' ] );
check( '...a component a feature registers stands after the kernel\'s, in the order it registered them and not in the alphabet\'s', array_slice( array_keys( (array) json_decode( (string) json_encode( \Nino\Modules\Builder\Document::registry( $featured )['components'] ), true ) ), -3 ) === [ 'spacer', 'zulu', 'alpha' ]
	&& array_keys( $registryAnswer['components'] ) === array_keys( \Nino\Modules\Builder\Document::registry( $appData )['components'] ) );

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

check( 'renaming a section rewrites the sources of its components and of its background in the file', $renamed['status'] === 200 && str_contains( $source, '[title /template/page-home/start/title style="loud"]' ) === true
	&& str_contains( $source, '[image /template/page-home/start/background alt=""]' ) === true && str_contains( $source, '[title /template/page-home/leistungen/title]' ) === true
	&& str_contains( $source, '/template/page-home/hero/' ) === false && str_contains( $source, '/template/page-home/services/' ) === false );
check( '...and the text keys follow: the value is under the new name, and the old name is gone', ( \Nino\Text::entry( $appData, '/template/page-home/start/title' )['values']['en_US'] ?? null ) === 'Hello'
	&& ( \Nino\Text::entry( $appData, '/template/page-home/leistungen/title' )['values']['en_US'] ?? null ) === 'Services'
	&& \Nino\Text::entry( $appData, '/template/page-home/hero/title' ) === null && \Nino\Text::entry( $appData, '/template/page-home/services/title' ) === null );
check( '...the slot of the background is made again under the new name with the file of the old one, and the old one is kept - without a file', ( \Nino\Images::getSlot( $appData, '/template/page-home/start/background' ) ?: [] )['filename'] === 'hero.1600x900.jpg'
	&& ( \Nino\Images::getSlot( $appData, '/template/page-home/start/background' ) ?: [] )['label'] === 'Hero' && ( \Nino\Images::getSlot( $appData, '/template/page-home/hero/background' ) ?: [ 'filename' => 'none' ] )['filename'] === null );
check( '...and the answer is the file as it reads now, without what only the panel sends', $renamed['model']['blocks'][0]['id'] === 'start' && isset( $renamed['model']['blocks'][0]['renamedFrom'] ) === false );

// The picture behind a column is a slot under the id of the section, and moves with it as the picture behind the section does
$appData['/nino/html/images']['/template/page-grid/one/photo'] = [ 'label' => 'Photo', 'width' => 800, 'height' => 600, 'filename' => 'grid.800x600.jpg' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
$gridSource = $page( "<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100 nino-grid--tint\">\n\t\t\t<div class=\"nino-grid-bg nino-img-focus--2\">[image /template/page-grid/one/photo alt=\"\"]</div>\n\t\t\t[title /template/page-grid/one/title]\n\t\t</div>\n\t</div>\n</section>" );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-grid.tpl', $gridSource );
$grid = \Nino\Modules\Builder\Document::load( $appData, 'page-grid' );
$moved = $grid['model'];
$moved['blocks'][0]['id'] = 'two';
$moved['blocks'][0]['renamedFrom'] = 'one';
$movedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-grid', $moved, $grid['hash'] );
$movedSource = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-grid.tpl' ) );
check( 'the picture behind a column moves with its section: the slot of its block is the new one in the file, made again under the new name with the file of the old one - the old one is kept, without a file - and the ground and the focus are as they were',
	$grid['model']['blocks'][0]['cols'][0]['background'] === [ 'slot' => '/template/page-grid/one/photo', 'focus' => 2 ] && $movedAnswer['status'] === 200
	&& str_contains( $movedSource, "<div class=\"nino-grid-bg nino-img-focus--2\">[image /template/page-grid/two/photo alt=\"\"]</div>" ) === true && str_contains( $movedSource, 'nino-grid--tint' ) === true && str_contains( $movedSource, '/template/page-grid/one/' ) === false
	&& ( \Nino\Images::getSlot( $appData, '/template/page-grid/two/photo' ) ?: [] )['filename'] === 'grid.800x600.jpg' && ( \Nino\Images::getSlot( $appData, '/template/page-grid/one/photo' ) ?: [ 'filename' => 'none' ] )['filename'] === null
	&& $movedAnswer['model']['blocks'][0]['cols'][0]['background'] === [ 'slot' => '/template/page-grid/two/photo', 'focus' => 2 ] );
\Nino\Modules\Builder\Document::delete( $appData, 'page-grid' );

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
	'a file whose foreign block touches the head and the footer'		=> "[template /templates/html-header]\n<section class=\"nino-hero nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<h2>[[/template/page-lib/hero/title]]</h2>\n\t</div>\n</section>\n[template /templates/html-footer]\n",
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
$bad['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/never', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'Never' ] ];
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
	foreach( [ 'a" tag="h4' => 'a double quote', "it's" => 'a single quote', 'a]b' => 'a closing bracket', 'a[b' => 'an opening bracket', "a\nb" => 'a line feed', "a\rb" => 'a carriage return' ] as $value => $name ) {

		$model = $loaded['model'];
		$set( $model, (string) $value );
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'value' || str_contains( implode( ' ', $answer['problems'] ), 'block' ) === false
			|| file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$accepted[] = $label. ' with '. $name;
	}

check( 'a double quote, a single quote, a bracket or a line break in a class, a fixed text, a stack attribute or a custom class is refused with 400 (code value), and the file is as it was: '. count( $unsafe ) * 6 .' cases', $accepted === [] );

$named = $loaded['model'];
$named['blocks'][3]['cols'][0]['components'][0]['attributes']['class'] = 'a" tag="h4';
check( '...the problem names the block, the component and the attribute, and problems() says the same to a caller that is not a save',
	\Nino\Modules\Builder\Document::save( $appData, 'page-home', $named, $loaded['hash'] )['problems'] === \Nino\Modules\Builder\Document::problems( $appData, $named, $registry )
	&& str_contains( \Nino\Modules\Builder\Document::problems( $appData, $named, $registry )[0], 'the section "contact" (block 4)' ) === true
	&& str_contains( \Nino\Modules\Builder\Document::problems( $appData, $named, $registry )[0], 'the attribute "class" of the component "title"' ) === true );

$thrown = 0;

foreach( [ 'class', 'text' ] as $where ) {

	$model = $loaded['model'];

	if( $where === 'class' )
		$model['blocks'][3]['cols'][0]['components'][0]['attributes']['class'] = 'a" tag="h4';
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

// The ground of a column, its dimming and the picture behind it, the border of a section and its brand colour: what is none of them is refused before anything is made, as a switch is
$columnBad = [
	'a word that is no colour'								=> [ 'color', 'bogus', 'the color of column 1 of block %d is none that Nino.css has' ],
	'a colour of a section in capitals'					=> [ 'color', 'Alt', 'the color of column 1 of block %d is none that Nino.css has' ],
	'a number for a colour'									=> [ 'color', 0, 'the color of column 1 of block %d is none that Nino.css has' ],
	'a list for a colour'										=> [ 'color', [ 'alt' ], 'the color of column 1 of block %d is none that Nino.css has' ],
	'a colour with a space'									=> [ 'color', 'alt ', 'the color of column 1 of block %d is none that Nino.css has' ],
	'a dimming that is a text'							=> [ 'dim', 'true', 'the dim of column 1 of block %d is neither on nor off' ],
	'a dimming that is a number'						=> [ 'dim', 1, 'the dim of column 1 of block %d is neither on nor off' ],
	'a dimming that is a list'							=> [ 'dim', [], 'the dim of column 1 of block %d is neither on nor off' ],
	'a background that is a text'						=> [ 'background', 'photo.jpg', 'column 1 of block %d has a background that is no image slot' ],
	'a background whose slot is a list'			=> [ 'background', [ 'slot' => [ '/template/page-home/hero/photo' ], 'focus' => null ], 'column 1 of block %d has a background that is no image slot' ],
	'a background whose alt texts are a text'	=> [ 'background', [ 'slot' => '/template/page-home/hero/photo', 'focus' => null, 'create' => [ 'label' => 'Photo', 'alt' => 'A house' ] ], 'column 1 of block %d has a background with an alt text that is none' ],
];
$columnRefused	= [];

foreach( [ 0, 3 ] as $at )
	foreach( $columnBad as $what => [ $key, $value, $sentence ] ) {

		$model = $loaded['model'];
		$model['blocks'][$at]['cols'][0][$key] = $value;
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ sprintf( $sentence, $at + 1 ) ]
			|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$columnRefused[] = $what. ' of block '. ( $at + 1 );
	}

check( 'a colour of a column that Nino.css has none of, a dimming that is no bool and a background that is no slot - a text, a list for the slot, alt texts that are a text - are refused with 400 (code invalid), the column and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $columnBad ) * 2 . ' cases'. ( $columnRefused === [] ? '' : ' - '. implode( ', ', $columnRefused ) ), $columnRefused === [] );

$slotBad = $loaded['model'];
$slotBad['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-home/hero/1photo', 'focus' => null ];
$slotBadAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $slotBad, $loaded['hash'] );
check( '...so is a background whose slot is no uri of a slot - a segment of one starts with a letter -: the section and the column are named', $slotBadAnswer['status'] === 400 && $slotBadAnswer['problems'] === [ 'the section "'. $slotBad['blocks'][0]['id']. '": the background of column 1 is not an image slot' ] && file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $before );

$groundGood = $loaded['model'];
$groundGood['blocks'][0]['cols'][0]['color'] = 'counter';
$groundGood['blocks'][0]['cols'][0]['dim'] = true;
$groundGood['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-home/hero/photo', 'focus' => 4 ];
$groundSource = \Nino\Modules\Builder\Document::source( $appData, $groundGood );
check( '...and counter, the dimming of a column with a picture and the slot of one are none of that: the source view writes them', $groundSource['status'] === 200 && str_contains( $groundSource['parts'][1]['source'], "<div class=\"nino-grid-s-100 nino-grid-m-100 nino-grid-l-66 nino-grid--counter nino-grid--dim nino-text-left\">\n\t\t\t<div class=\"nino-grid-bg nino-img-focus--4\">[image /template/page-home/hero/photo alt=\"\"]</div>\n\t\t\t[title " ) === true );

// The focus of the picture behind a column or a section is none or a number from 1 to 9: the Writer casts a text or a list to a number, so it is refused before anything is made
$focusBad			= [
	'a text with the number'	=> '5',
	'a text that is no number'	=> 'center',
	'an empty text'				=> '',
	'zero'						=> 0,
	'ten'						=> 10,
	'a negative number'			=> -1,
	'a fraction'				=> 5.5,
	'a whole number as a float'	=> 5.0,
	'true'						=> true,
	'a list'					=> [ 5 ],
	'an empty list'				=> [],
];
$focusRefused	= [ 'column' => [], 'section' => [] ];

foreach( [ 0, 3 ] as $at )
	foreach( [ 'column', 'section' ] as $owner )
		foreach( $focusBad as $what => $value ) {

			$model = $loaded['model'];

			if( $owner === 'column' )
				$model['blocks'][$at]['cols'][0]['background'] = [ 'slot' => '/template/page-home/hero/photo', 'focus' => $value ];
			else
				$model['blocks'][$at]['background'] = [ 'slot' => '/template/page-home/hero/photo', 'focus' => $value ];

			$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );
			$sentence = ( $owner === 'column' ? 'column 1 of ' : '' ). 'block '. ( $at + 1 ). ' has a background with a focus that is no place from 1 to 9';

			if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ $sentence ]
				|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
				$focusRefused[$owner][] = $what. ' of block '. ( $at + 1 );
		}

check( 'the focus of the picture behind a column is none or a number from 1 to 9: a text, a number outside the places, a fraction, a bool and a list are refused with 400 (code invalid), the column and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $focusBad ) * 2 . ' cases'. ( $focusRefused['column'] === [] ? '' : ' - '. implode( ', ', $focusRefused['column'] ) ), $focusRefused['column'] === [] );
check( '...so is the focus of the picture behind a section, with the block named and nothing written: '
	. count( $focusBad ) * 2 . ' cases'. ( $focusRefused['section'] === [] ? '' : ' - '. implode( ', ', $focusRefused['section'] ) ), $focusRefused['section'] === [] );

$focusWrong = [];

foreach( [ 'column', 'section' ] as $owner )
	foreach( [ null, 1, 2, 3, 4, 5, 6, 7, 8, 9 ] as $value ) {

		$model = $loaded['model'];

		if( $owner === 'column' )
			$model['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-home/hero/photo', 'focus' => $value ];
		else
			$model['blocks'][0]['background'] = [ 'slot' => '/template/page-home/hero/photo', 'focus' => $value ];

		$answer = \Nino\Modules\Builder\Document::source( $appData, $model );
		$class = ( $owner === 'column' ? 'nino-grid-bg' : 'nino-section-bg' ). ( $value === null ? '' : ' nino-img-focus--'. $value );

		if( $answer['status'] !== 200 || str_contains( $answer['parts'][1]['source'], '<div class="'. $class. '">[image /template/page-home/hero/photo alt=""]</div>' ) === false )
			$focusWrong[] = $owner. ' with '. ( $value ?? 'none' );
	}

check( '...and none and each of the nine places are none of that: the source view writes the picture of a column and of a section with the class of its place, or with none'. ( $focusWrong === [] ? '' : ' - '. implode( ', ', $focusWrong ) ), $focusWrong === [] );

$borderBad = [
	'the old choice of the border'		=> [ 'border', 'primary', 'the border of block %d is none of the strengths' ],
	'a strength there is none of'			=> [ 'border', '4', 'the border of block %d is none of the strengths' ],
	'a strength that is a number'			=> [ 'border', 2, 'the border of block %d is none of the strengths' ],
	'a strength that is a list'				=> [ 'border', [ '2' ], 'the border of block %d is none of the strengths' ],
	'a brand colour that is a text'			=> [ 'borderPrimary', 'true', 'the borderPrimary of block %d is neither on nor off' ],
	'a brand colour that is a number'		=> [ 'borderPrimary', 1, 'the borderPrimary of block %d is neither on nor off' ],
];
$borderRefused	= [];

foreach( [ 0, 3 ] as $at )
	foreach( $borderBad as $what => [ $key, $value, $sentence ] ) {

		$model = $loaded['model'];
		$model['blocks'][$at]['settings'][$key] = $value;
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ sprintf( $sentence, $at + 1 ) ]
			|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$borderRefused[] = $what. ' of block '. ( $at + 1 );
	}

check( 'a border that is no strength of the three - the old brand colour among them - and a brand colour that is no bool are refused the same way, the block named: '. count( $borderBad ) * 2 . ' cases'. ( $borderRefused === [] ? '' : ' - '. implode( ', ', $borderRefused ) ), $borderRefused === [] );

$borderGood = $loaded['model'];
$borderGood['blocks'][0]['settings']['border'] = '1';
$borderGood['blocks'][0]['settings']['borderPrimary'] = true;
$borderLeft = $loaded['model'];
unset( $borderLeft['blocks'][0]['settings']['borderPrimary'] );
check( '...a strength with the brand colour is written, and a switch left out is off: no class', str_contains( \Nino\Modules\Builder\Document::source( $appData, $borderGood )['parts'][1]['source'], ' nino-section--border-1 nino-section--border-primary ' ) === true
	&& str_contains( \Nino\Modules\Builder\Document::source( $appData, $borderLeft )['parts'][1]['source'], 'nino-section--border-primary' ) === false );

// The width and the alignment of a row, the place of a picture and the alignment of a text or of a stack: a word of Nino.css or none - what is none of them would be a class of nothing
$wordBad = [
	'a row that is no width'										=> [ 'section', 'row', 'narrower', 'the row of block %d is none that Nino.css has' ],
	'a row alignment that is none'							=> [ 'section', 'rowAlign', 'top', 'the rowAlign of block %d is none that Nino.css has' ],
	'a place of the content in capitals'				=> [ 'section', 'contentPos', 'Top', 'the contentPos of block %d is none that Nino.css has' ],
	'the middle, which has no word of its own'	=> [ 'section', 'contentPos', 'center', 'the contentPos of block %d is none that Nino.css has' ],
	'an alignment of the text that is a number'	=> [ 'section', 'text', 0, 'the text of block %d is none that Nino.css has' ],
	'an alignment of a column that is a list'		=> [ 'column', 'text', [ 'left' ], 'the text of column 1 of block %d is none that Nino.css has' ],
	'an alignment of a column with a space'			=> [ 'column', 'text', 'left ', 'the text of column 1 of block %d is none that Nino.css has' ],
	'an alignment of a stack that is none'			=> [ 'column', 'stackAlign', 'middle', 'the stackAlign of column 1 of block %d is none that Nino.css has' ],
];
$wordRefused	= [];

foreach( [ 0, 3 ] as $at )
	foreach( $wordBad as $what => [ $owner, $key, $value, $sentence ] ) {

		$model = $loaded['model'];

		if( $owner === 'section' )
			$model['blocks'][$at]['settings'][$key] = $value;
		else
			$model['blocks'][$at]['cols'][0][$key] = $value;

		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ sprintf( $sentence, $at + 1 ) ]
			|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$wordRefused[] = $what. ' of block '. ( $at + 1 );
	}

check( 'a row that is no width, an alignment of a row, a place of the content and an alignment of a text or of a stack that Nino.css has none of are refused the same way, the setting and the block named: '. count( $wordBad ) * 2 . ' cases'. ( $wordRefused === [] ? '' : ' - '. implode( ', ', $wordRefused ) ), $wordRefused === [] );

$wordWrong = [];

foreach( [
	[ 'section', 'row', [ 'narrow', 'wide' ], 'nino-grid-row--%s' ],
	[ 'section', 'rowAlign', [ 'center', 'middle', 'bottom' ], 'nino-grid-%s' ],
	[ 'section', 'contentPos', [ 'top', 'bottom' ], 'nino-cover-%s' ],
	[ 'section', 'text', [ 'left', 'center', 'right' ], 'nino-text-%s' ],
	[ 'column', 'text', [ 'left', 'center', 'right' ], 'nino-text-%s' ],
	[ 'column', 'stackAlign', [ 'start', 'center', 'end' ], 'nino-stack-%s' ],
] as [ $owner, $key, $words, $class ] )
	foreach( $words as $word ) {

		$model = $loaded['model'];

		if( $owner === 'section' )
			$model['blocks'][0]['settings'][$key] = $word;
		else
			$model['blocks'][0]['cols'][0][$key] = $word;

		$answer = \Nino\Modules\Builder\Document::source( $appData, $model );

		if( $answer['status'] !== 200 || str_contains( $answer['parts'][1]['source'], sprintf( $class, $word ) ) === false )
			$wordWrong[] = $owner. ' '. $key. ' '. $word;
	}

check( 'the five vocabularies the Reader keeps are the words the Builder writes, no more and no fewer - a word dropped from one of them would become a custom class without a sound',
	\Nino\Modules\Builder\Reader::ROW === [ 'narrow', 'wide' ] && \Nino\Modules\Builder\Reader::ROW_ALIGN === [ 'center', 'middle', 'bottom' ]
	&& \Nino\Modules\Builder\Reader::CONTENT_POS === [ 'top', 'bottom' ] && \Nino\Modules\Builder\Reader::TEXT === [ 'left', 'center', 'right' ]
	&& \Nino\Modules\Builder\Reader::STACK_ALIGN === [ 'start', 'center', 'end' ] );
check( '...and every word the Reader keeps in a setting of its own is none of that: the source view writes it as the class it is read from'. ( $wordWrong === [] ? '' : ' - '. implode( ', ', $wordWrong ) ), $wordWrong === [] );

// The cover and the picture behind it: two switches that are on or off like the others, and a height in percent of the window's that is a whole number or none
$pictureSwitchRefused = [];

foreach( [ 0, 3 ] as $at )
	foreach( [ 'parallax', 'dim' ] as $key )
		foreach( $switchBad as $what => $value ) {

			$model = $loaded['model'];
			$model['blocks'][$at]['settings'][$key] = $value;
			$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

			if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ 'the '. $key. ' of block '. ( $at + 1 ). ' is neither on nor off' ]
				|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
				$pictureSwitchRefused[] = $key. ' of block '. ( $at + 1 ). ' as '. $what;
		}

check( 'a parallax or a dimming that is no bool - a text, a number, a list - is refused the way a full width is, the switch and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $switchBad ) * 4 . ' cases'. ( $pictureSwitchRefused === [] ? '' : ' - '. implode( ', ', $pictureSwitchRefused ) ), $pictureSwitchRefused === [] );

$coverBad = [
	'zero'							=> 0,
	'a negative number'				=> -1,
	'five digits'					=> 10000,
	'a word'						=> 'tall',
	'a text with the number'		=> '50',
	'a fraction'					=> 50.5,
	'a whole number as a float'		=> 50.0,
	'true'							=> true,
	'a list'						=> [ 50 ],
	'an empty list'					=> [],
];
$coverRefused = [];

foreach( [ 0, 3 ] as $at )
	foreach( $coverBad as $what => $value ) {

		$model = $loaded['model'];
		$model['blocks'][$at]['settings']['cover'] = $value;
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ 'the cover of block '. ( $at + 1 ). ' is neither none nor a height in percent' ]
			|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$coverRefused[] = $what. ' of block '. ( $at + 1 );
	}

check( 'the cover of a section is none or a whole number from 1 to 9999 - zero, a negative number, five digits, a word, a text with the number, a fraction, a bool and a list are refused with 400 (code invalid), the block named, by a save and by the source view alike, and nothing is written: '
	. count( $coverBad ) * 2 . ' cases'. ( $coverRefused === [] ? '' : ' - '. implode( ', ', $coverRefused ) ), $coverRefused === [] );

$coverWrong = [];

foreach( [ 1, 90, 100, 150, 9999 ] as $height ) {

	$model = $loaded['model'];
	$model['blocks'][0]['settings']['cover'] = $height;
	$answer = \Nino\Modules\Builder\Document::source( $appData, $model );

	if( $answer['status'] !== 200 || str_contains( $answer['parts'][1]['source'], ' nino-cover' ) === false || str_contains( $answer['parts'][1]['source'], ' data-cover-height="'. $height. '"' ) === false )
		$coverWrong[] = (string) $height;
}

$coverNone = $loaded['model'];
$coverNone['blocks'][0]['settings']['cover'] = null;
$coverLeft = $loaded['model'];
unset( $coverLeft['blocks'][0]['settings']['cover'] );
check( '...and a height from 1 to 9999 is written as the cover it is - the class and the attribute -, none and a cover the model leaves out as neither'. ( $coverWrong === [] ? '' : ' - '. implode( ', ', $coverWrong ) ), $coverWrong === []
	&& str_contains( \Nino\Modules\Builder\Document::source( $appData, $coverNone )['parts'][1]['source'], 'nino-cover' ) === false && str_contains( \Nino\Modules\Builder\Document::source( $appData, $coverNone )['parts'][1]['source'], 'data-cover-height' ) === false
	&& str_contains( \Nino\Modules\Builder\Document::source( $appData, $coverLeft )['parts'][1]['source'], 'nino-cover' ) === false && str_contains( \Nino\Modules\Builder\Document::source( $appData, $coverLeft )['parts'][1]['source'], 'data-cover-height' ) === false );

$stackWrong = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$stackWrong['model']['blocks'][3]['cols'][0]['components'] = [ [ 'name' => 'stack', 'source' => '/services', 'text' => null, 'attributes' => [], 'content' => '' ] ];
$stackAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $stackWrong['model'], $stackWrong['hash'] );
check( 'a component called stack in a static column reads back as a stack: it is not the section that was posted, and is a problem', $stackAnswer['status'] === 400 && str_contains( implode( ' ', $stackAnswer['problems'] ), 'does not read back as it was written' ) === true );

// The conflict
$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$mine = $loaded['model'];
$mine['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/late', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'Late' ] ];
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
$limited['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/forbidden', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'No' ] ];
$limitedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $limited, $loaded['hash'] );
check( 'an account that may not make text keys cannot make them through the builder: the builder answers 403 before it makes anything, and the file is not written', $limitedAnswer['status'] === 403
	&& \Nino\Text::entry( $appData, '/template/page-home/contact/forbidden' ) === null && file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $write( $mine ) );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

echo "\n";


// --- 7b. The texts of a key and the alt texts of a slot ----------------------

echo "Document - the texts typed for a key, and the alt texts of a new slot\n";

// The names the site gives its languages, and the alt texts a slot holds, are what the panel's registry says
\Nino\Filesystem::mutate( $appData, '/text/global.php', static function( mixed $texts ): array {
	return (array) $texts + [ '[[/_nino/locale/en_US/name]]' => 'English (US)', '[[/_nino/locale/de_DE/name]]' => 'Deutsch', '[[/project/website/general/slogan]]' => 'Everywhere' ];
} );
\Nino\Images::setSlotAlt( $appData, '/template/page-home/hero/background', [ 'de_DE' => 'Ein Held' ] );
$withLanguages = \Nino\Modules\Builder\Document::registry( $appData );
$names = [ 'en_US' => 'English (US)', 'de_DE' => 'Deutsch' ];
check( 'the registry names the languages of the site, in the order the site has them, by the name the project gives them, and the native one',
	$withLanguages['locales'] === array_map( static fn( string $locale ): array => [ 'locale' => $locale, 'name' => $names[$locale] ], \Nino\Locales::getAvailableLocales( $appData ) ) && count( $withLanguages['locales'] ) === 2 && $withLanguages['native'] === 'en_US' );
$appData['/nino/locales/native'] = 'de_DE';
$nativeGerman = \Nino\Modules\Builder\Document::registry( $appData )['native'];
$appData['/nino/locales/native'] = 'en_US';
check( '...the native one is the language the site has as its own, whichever that is: the registry says what the site says and assumes none', $nativeGerman === 'de_DE' && $withLanguages['native'] === \Nino\Locales::getNativeLocale( $appData ) );
$withSlots = array_column( $withLanguages['slots'], null, 'uri' );
check( '...and the alt texts of each slot by language, a slot without any has none', $withSlots['/template/page-home/hero/background']['alt'] === [ 'de_DE' => 'Ein Held' ] && $withSlots['/template/page-home/contact/background']['alt'] === [] );
unset( $appData['/nino/html/images']['/template/page-home/hero/background']['alt'] );
$appData['/nino/locales/available'][] = 'fr_FR';
check( '...a language with no name is called by its code', end( \Nino\Modules\Builder\Document::registry( $appData )['locales'] ) === [ 'locale' => 'fr_FR', 'name' => 'fr_FR' ] );
array_pop( $appData['/nino/locales/available'] );

foreach( [ 'en_US' => [ 'Old title', '<p>Old intro</p>', 'Old <em>tag</em>' ], 'de_DE' => [ 'Alter Titel', '<p>Alte Einleitung</p>', 'Alte <em>Marke</em>' ] ] as $locale => [ $title, $intro, $tag ] )
	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ) use ( $title, $intro, $tag ): array {
		return (array) $texts + [ '[[/template/page-texts/a/title]]' => $title, '[[/template/page-texts/a/intro]]' => $intro, '[[/template/page-texts/a/tag]]' => $tag ];
	} );
foreach( [ 'title' => 'plain', 'intro' => 'blocks', 'tag' => 'inline' ] as $name => $format )
	\Nino\Text::setMeta( $appData, '/template/page-texts/a/'. $name, $format, null );

$textsFile = $page( $section( "[title /template/page-texts/a/title]\n\t\t\t[text /template/page-texts/a/intro]\n\t\t\t[subtitle /template/page-texts/a/tag]\n\t\t\t[title /project/website/general/slogan]", 'a' ) );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-texts.tpl', $textsFile );
$texted = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$textsPath = \Nino\Filesystem::path( $appData, '/templates/page-texts.tpl' );

// What the keys hold in the languages: key => language => text, and the file
$held = static function( string ...$keys ) use ( &$appData ): array {
	$all = array_column( \Nino\Text::entries( $appData ), null, 'key' );
	$now = [];
	foreach( $keys as $key )
		$now[$key] = isset( $all[$key] ) === false ? null : $all[$key]['values'];
	return $now;
};
$ofTexts = [ '/template/page-texts/a/title', '/template/page-texts/a/intro', '/template/page-texts/a/tag', '/project/website/general/slogan' ];
$untouched = $held( ...$ofTexts );
$cleanly = static fn( string $value, string $format ): string => \Nino\Text::sanitizeValue( $value, $format );
$sorted = static function( array $values ): array {
	ksort( $values );
	return $values;
};

$typed = $texted['model'];
$typed['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'de_DE' => 'Neuer <b>Titel</b>' ];
$typed['blocks'][0]['cols'][0]['components'][1]['values'] = [ 'en_US' => '<p>New <strong>intro</strong></p><script>alert(1)</script>', 'de_DE' => '<p>Neue Einleitung</p>' ];
$typed['blocks'][0]['cols'][0]['components'][2]['values'] = [ 'en_US' => 'New <em>tag</em> <u>and</u> [x]' ];
$saved = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $typed, $texted['hash'] );
$now = $held( ...$ofTexts );

check( 'the texts typed for the keys of existing components are written, each to its language and in the format of the key: the title is plain and loses its tags, the intro keeps its paragraphs and loses the script, the tag keeps the emphasis',
	$saved['status'] === 200 && $now['/template/page-texts/a/title']['de_DE'] === 'Neuer Titel' && $now['/template/page-texts/a/title']['en_US'] === 'Old title'
	&& $now['/template/page-texts/a/intro']['en_US'] === $cleanly( '<p>New <strong>intro</strong></p><script>alert(1)</script>', 'blocks' ) && str_contains( $now['/template/page-texts/a/intro']['en_US'], '<script' ) === false
	&& $now['/template/page-texts/a/intro']['de_DE'] === '<p>Neue Einleitung</p>' && $now['/template/page-texts/a/tag']['en_US'] === $cleanly( 'New <em>tag</em> <u>and</u> [x]', 'inline' )
	&& str_contains( $now['/template/page-texts/a/tag']['en_US'], '<em>tag</em>' ) === true && str_contains( $now['/template/page-texts/a/tag']['en_US'], '<u>' ) === false && $now['/template/page-texts/a/tag']['de_DE'] === $untouched['/template/page-texts/a/tag']['de_DE'] );
check( '...the file is not touched for it: the model is what the file reads as, so the answer is the file and its hash as they were, without the texts', $saved['hash'] === $texted['hash'] && file_get_contents( $textsPath ) === $textsFile
	&& $saved['model'] === $texted['model'] && str_contains( (string) json_encode( $saved['model'] ), '"values"' ) === false );

// A key every language shares has one text
$shared = $texted['model'];
$shared['blocks'][0]['cols'][0]['components'][3]['values'] = [ 'de_DE' => 'Überall', 'en_US' => 'Anywhere' ];
$sharedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $shared, $texted['hash'] );
$slogan = \Nino\Text::entry( $appData, '/project/website/general/slogan' );
check( 'a key every language shares is written once, with the last text given, and stays shared', $sharedAnswer['status'] === 200 && $slogan['global'] === true && $slogan['values'] === [ '*' => 'Anywhere' ] );

// What is refused is refused before anything is written
$refused = static function( callable $break ) use ( &$appData, $texted, $held, $ofTexts, $textsPath, $textsFile ): array {
	$model = $texted['model'];
	$break( $model );
	$before = $held( ...$ofTexts );
	$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $model, $texted['hash'] );
	return $answer + [ 'unchanged' => $held( ...$ofTexts ) === $before && file_get_contents( $textsPath ) === $textsFile ];
};

$unknown = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'de_DE' => 'Fine', 'xx_XX' => 'Not a language of the site' ]; } );
check( 'a text for a language the site does not have is refused with 400 (value), the section and the component named - and not one of the texts is written', $unknown['status'] === 400 && $unknown['code'] === 'value'
	&& str_contains( implode( ' ', $unknown['problems'] ), 'the section "a" (block 1)' ) === true && str_contains( implode( ' ', $unknown['problems'] ), '"xx_XX"' ) === true && $unknown['unchanged'] === true );
$listed = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'Not a language' ]; } );
check( '...so is a text with no language at all, which is a list and not a map', $listed['status'] === 400 && $listed['code'] === 'value' && $listed['unchanged'] === true );

$shapes = [
	'a text that is not a map'								=> static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['values'] = 'Hello'; },
	'a text that is a list of texts'					=> static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => [ 'Hello' ] ]; },
	'a text that is a number'									=> static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => 5 ]; },
	'an alt text that is not a map'						=> static function( array &$model ): void { $model['blocks'][0]['background'] = [ 'slot' => '/template/page-texts/a/background', 'focus' => null, 'create' => [ 'label' => 'B', 'width' => 100, 'height' => 100, 'alt' => 'Hello' ] ]; },
	'an alt text that is a number'						=> static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['create'] = [ 'value' => 'x', 'alt' => [ 'en_US' => 5 ] ]; },
];
$misshapen = [];

foreach( $shapes as $label => $break ) {
	$answer = $refused( $break );
	if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['unchanged'] !== true )
		$misshapen[] = $label;
}

check( 'texts that are not a text for each language - not a map, a text that is not text, an alt text of either kind - are refused with 400 (invalid) before anything is read: '. count( $shapes ). ' cases'. ( $misshapen === [] ? '' : ' - '. implode( ', ', $misshapen ) ), $misshapen === [] );

$fixed = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0] = [ 'name' => 'title', 'source' => '', 'text' => 'Fixed', 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'values' => [ 'en_US' => 'Typed' ] ]; } );
$field = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['source'] = 'title'; $model['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => 'Typed' ]; } );
$picture = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-home/hero/background', 'text' => null, 'attributes' => [ 'alt' => '', 'focus' => '', 'ratio' => '', 'class' => '' ], 'values' => [ 'en_US' => 'Typed' ] ]; } );
check( 'a text for a component that has no text key - a fixed text, a picture - is a problem of the section and the component, and nothing is written', $fixed['status'] === 400 && $fixed['code'] === 'invalid' && $picture['status'] === 400 && $picture['code'] === 'invalid'
	&& str_contains( implode( ' ', $fixed['problems'] ), 'the component "title" has a text, and its source is no text key' ) === true && str_contains( implode( ' ', $picture['problems'] ), 'the component "image" has a text' ) === true && $fixed['unchanged'] === true && $picture['unchanged'] === true );
check( '...and so is one for the field of a loop, which is the element\'s to change: a field name in a column that has no loop is no source', $field['status'] === 400 && $field['unchanged'] === true );

$nokey = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/a/nothing', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'values' => [ 'en_US' => 'Typed' ] ]; } );
check( 'a text for a key that is not there and is not made by the save is refused with 400 (invalid), the key named, and the key is not made', $nokey['status'] === 400 && $nokey['code'] === 'invalid' && str_contains( implode( ' ', $nokey['problems'] ), '"/template/page-texts/a/nothing"' ) === true
	&& $nokey['unchanged'] === true && \Nino\Text::entry( $appData, '/template/page-texts/a/nothing' ) === null );

$odd = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-texts/a/odd', 'text' => null, 'attributes' => [ 'format' => 'nonsense', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'Text' ], 'values' => [ 'en_US' => 'Typed' ] ]; } );
check( 'a component that asks for a format there is none of is not made plain: the section is refused with 400 (invalid), the attribute named, as for any value outside the options of an attribute - and no key is made, no text written', $odd['status'] === 400 && $odd['code'] === 'invalid'
	&& str_contains( implode( ' ', $odd['problems'] ), 'the section "a" is not valid: line ' ) === true && str_contains( implode( ' ', $odd['problems'] ), 'the attribute format has a value the builder does not keep' ) === true
	&& $odd['unchanged'] === true && \Nino\Text::entry( $appData, '/template/page-texts/a/odd' ) === null );

// A key that is new: made, in the format its component asks for, and written
$fresh = $texted['model'];
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/a/title-2', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'Title' ],
	'values' => [ 'en_US' => 'Hello <b>there</b>', 'de_DE' => 'Hallo' ] ];
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-texts/a/body', 'text' => null, 'attributes' => [ 'format' => 'inline', 'style' => '', 'class' => '' ], 'create' => [ 'value' => 'Text' ],
	'values' => [ 'de_DE' => 'Ein <em>Satz</em><p>und ein Absatz</p>' ] ];
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-texts/a/lines', 'text' => null, 'attributes' => $registry['components']['text']['defaults'], 'create' => [ 'value' => 'Text' ], 'values' => [ 'en_US' => "One\nTwo" ] ];
$freshAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $fresh, $texted['hash'] );
$title2 = \Nino\Text::entry( $appData, '/template/page-texts/a/title-2' );
$body = \Nino\Text::entry( $appData, '/template/page-texts/a/body' );
$lines = \Nino\Text::entry( $appData, '/template/page-texts/a/lines' );

check( 'a text typed for a key the save makes is written after the key is made, to the language it is for: the other languages keep the text the key was made with', $freshAnswer['status'] === 200
	&& $sorted( $title2['values'] ) === [ 'de_DE' => 'Hallo', 'en_US' => 'Hello there' ] && $body['values']['en_US'] === 'Text' && $body['values']['de_DE'] === $cleanly( 'Ein <em>Satz</em><p>und ein Absatz</p>', 'inline' )
	&& str_contains( $body['values']['de_DE'], '<p>' ) === false );
check( '...in the format the component asks for - its format attribute, plain where it has none - and chosen, so that the key does not follow what it holds', $title2['format'] === 'plain' && $title2['formatSet'] === true && $body['format'] === 'inline' && $body['formatSet'] === true
	&& $lines['format'] === 'blocks' && $lines['formatSet'] === true && $lines['values']['en_US'] === $cleanly( "One\nTwo", 'blocks' ) );
check( '...and the file has the components', str_contains( (string) file_get_contents( $textsPath ), '[title /template/page-texts/a/title-2]' ) === true && str_contains( (string) file_get_contents( $textsPath ), '[text /template/page-texts/a/body format="inline"]' ) === true
	&& $freshAnswer['model']['blocks'][0]['cols'][0]['components'][5]['source'] === '/template/page-texts/a/body' && isset( $freshAnswer['model']['blocks'][0]['cols'][0]['components'][5]['values'] ) === false );

// ...and the other way round, with the rest of the texts
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$moveModel = $structure['model'];
$moveModel['blocks'][0]['id'] = 'z';
$moveModel['blocks'][0]['renamedFrom'] = 'a';
$moveModel['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => 'Moved and typed' ];
$moved = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $moveModel, $structure['hash'] );
check( 'a section that is renamed has its keys moved first and the texts written to them under their new name, the old name being gone', $moved['status'] === 200 && \Nino\Text::entry( $appData, '/template/page-texts/a/title' ) === null
	&& $sorted( \Nino\Text::entry( $appData, '/template/page-texts/z/title' )['values'] ) === [ 'de_DE' => 'Neuer Titel', 'en_US' => 'Moved and typed' ] && $moved['hash'] !== $structure['hash'] );

// A save that stops puts the texts back as they were, and takes away the keys it made
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$ofMore = [ '/template/page-texts/z/title', '/template/page-texts/z/intro', '/template/page-texts/z/tag', '/template/page-texts/z/new', '/template/page-texts/z/newer' ];
$settled = $held( ...$ofMore );
$fileSettled = file_get_contents( $textsPath );
$stopping = $structure['model'];
$stopping['name'] = 'Texts';
$stopping['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => 'One more title', 'de_DE' => 'Noch ein Titel' ];
$stopping['blocks'][0]['cols'][0]['components'][2]['values'] = [ 'de_DE' => 'Eine Marke' ];
$stopping['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/z/new', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'New' ], 'values' => [ 'en_US' => 'A new title' ] ];
$stopping['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/z/newer', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'Newer' ], 'values' => [ 'de_DE' => 'Ein neuerer Titel' ] ];

$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/templates/page-texts.tpl' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$stopped = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $stopping, $structure['hash'] );
rmdir( $lock );
check( 'a save whose file cannot be written answers 500 (write), puts the texts back as they were read, and takes away the keys it made, every one - so that the project is as it was', $stopped['status'] === 500 && $stopped['code'] === 'write'
	&& $held( ...$ofMore ) === $settled && \Nino\Text::entry( $appData, '/template/page-texts/z/new' ) === null && \Nino\Text::entry( $appData, '/template/page-texts/z/newer' ) === null && file_get_contents( $textsPath ) === $fileSettled );
$retried = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $stopping, $structure['hash'] );
check( '...and the same save, tried again, is none the worse for it: the keys are made again and the texts are written', $retried['status'] === 200 && \Nino\Text::entry( $appData, '/template/page-texts/z/new' )['values']['en_US'] === 'A new title' && \Nino\Text::entry( $appData, '/template/page-texts/z/newer' )['values']['de_DE'] === 'Ein neuerer Titel'
	&& $sorted( \Nino\Text::entry( $appData, '/template/page-texts/z/title' )['values'] ) === [ 'de_DE' => 'Noch ein Titel', 'en_US' => 'One more title' ] && $retried['model']['name'] === 'Texts' );

// A language file that does not take a text is a save that stops, with the texts of the other put back
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$settled = $held( ...$ofMore );
$fileSettled = file_get_contents( $textsPath );
$halfway = $structure['model'];
$halfway['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => 'Written', 'de_DE' => 'Not written' ];
$halfway['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/z/other', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'value' => 'Other' ], 'values' => [ 'en_US' => 'Other title', 'de_DE' => 'Anderer Titel' ] ];
$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/text/de_DE.php' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$half = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $halfway, $structure['hash'] );
rmdir( $lock );
check( 'a language file that cannot be written fails the save (500, key) - the text read back is not the text written -, the language that was written is put back, the key made is taken away, and the file is as it was',
	$half['status'] === 500 && $half['code'] === 'key' && $held( ...$ofMore ) === $settled && \Nino\Text::entry( $appData, '/template/page-texts/z/other' ) === null && file_get_contents( $textsPath ) === $fileSettled );

// Without the permission of the panel that owns the keys, no text is written
\Nino\Auth::insertUser( $appData, 'nokeys@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/slots/manage' ] );
\Nino\Auth::loginUser( $appData, 'nokeys@example.com', 'correct horse battery staple' );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$forbiddenTexts = $structure['model'];
$forbiddenTexts['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'en_US' => 'Not allowed' ];
$forbiddenAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $forbiddenTexts, $structure['hash'] );
check( 'an account that may not use the Text Keys cannot write a text through the builder: 403, the permission named, before anything is written', $forbiddenAnswer['status'] === 403 && $forbiddenAnswer['code'] === 'permission'
	&& $forbiddenAnswer['problems'] === [ '/_admin/keys/manage' ] && $held( ...$ofMore ) === $settled );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

// The alt texts a new slot is made with
$slotsBefore = \Nino\Images::getSlots( $appData );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$pictured = $structure['model'];
$pictured['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-texts/z/photo', 'text' => null, 'attributes' => $registry['components']['image']['defaults'],
	'create' => [ 'label' => 'Photo', 'width' => 800, 'height' => 400, 'alt' => [ 'en_US' => "A house\nby the sea", 'de_DE' => 'Ein Haus am Meer' ] ] ];
$pictured['blocks'][0]['background'] = [ 'slot' => '/template/page-texts/z/background', 'focus' => 5, 'create' => [ 'label' => 'Background', 'width' => 1200, 'height' => 600, 'alt' => [ 'en_US' => 'Waves' ] ] ];
$picturedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $pictured, $structure['hash'] );
check( 'the alt texts a slot is made with end up at the slot, by language and as the Images panel keeps them - a line break is a space -, for a picture and for a background', $picturedAnswer['status'] === 200
	&& ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/photo' ) ?: [] )['alt'] === [ 'en_US' => 'A house by the sea', 'de_DE' => 'Ein Haus am Meer' ]
	&& ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/background' ) ?: [] )['alt'] === [ 'en_US' => 'Waves' ] && ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/photo' ) ?: [] )['width'] === 800 );
check( '...and the registry, the next time it is asked, has them', array_column( \Nino\Modules\Builder\Document::registry( $appData )['slots'], 'alt', 'uri' )['/template/page-texts/z/photo'] === [ 'en_US' => 'A house by the sea', 'de_DE' => 'Ein Haus am Meer' ] );

// The picture behind a column is a slot like the one behind a section: made at the save, with the label and the size of its mark and its alt texts
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$columned = $structure['model'];
$columned['blocks'][0]['cols'][0]['color'] = 'counter';
$columned['blocks'][0]['cols'][0]['dim'] = true;
$columned['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-texts/z/column-background', 'focus' => 7, 'create' => [ 'label' => 'Column background', 'width' => 600, 'height' => 800, 'alt' => [ 'en_US' => 'A wave', 'de_DE' => 'Eine Welle' ] ] ];
$columnedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $columned, $structure['hash'] );
check( 'the picture behind a column is made at the save as the one behind a section is, with the label and the size of its mark and its alt texts by language - and the file has the ground, the dimming and the picture first in the column, which reads back as it was set, without the mark',
	$columnedAnswer['status'] === 200 && ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/column-background' ) ?: [] )['label'] === 'Column background' && ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/column-background' ) ?: [] )['height'] === 800
	&& ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/column-background' ) ?: [] )['alt'] === [ 'en_US' => 'A wave', 'de_DE' => 'Eine Welle' ]
	&& str_contains( (string) file_get_contents( $textsPath ), "<div class=\"nino-grid-100 nino-grid--counter nino-grid--dim\">\n\t\t\t<div class=\"nino-grid-bg nino-img-focus--7\">[image /template/page-texts/z/column-background alt=\"\"]</div>\n" ) === true
	&& $columnedAnswer['model']['blocks'][0]['cols'][0]['background'] === [ 'slot' => '/template/page-texts/z/column-background', 'focus' => 7 ] && $columnedAnswer['model']['blocks'][0]['cols'][0]['color'] === 'counter' && $columnedAnswer['model']['blocks'][0]['cols'][0]['dim'] === true );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$columnedOther = $structure['model'];
$columnedOther['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-texts/z/other-background', 'focus' => null, 'create' => [ 'label' => 'Other', 'width' => 100, 'height' => 100, 'alt' => [ 'xx_XX' => 'Not a language' ] ] ];
$columnedOtherAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $columnedOther, $structure['hash'] );
check( '...an alt text of it for a language the site does not have is refused with 400 (value) as one of a picture is, the column named - and no slot is made', $columnedOtherAnswer['status'] === 400 && $columnedOtherAnswer['code'] === 'value'
	&& str_contains( implode( ' ', $columnedOtherAnswer['problems'] ), 'the alt text of the background of column 1 is for "xx_XX"' ) === true && \Nino\Images::getSlot( $appData, '/template/page-texts/z/other-background' ) === false );

$slotsNow = \Nino\Images::getSlots( $appData );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$altered = static function( array $alt ) use ( $structure ): array {
	$model = $structure['model'];
	$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-texts/z/other-photo', 'text' => null, 'attributes' => [ 'alt' => '', 'focus' => '', 'ratio' => '', 'class' => '' ], 'create' => [ 'label' => 'Other', 'width' => 100, 'height' => 100, 'alt' => $alt ] ];
	return $model;
};
$longAlt		= \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $altered( [ 'en_US' => str_repeat( 'x', 251 ) ] ), $structure['hash'] );
$otherAlt		= \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $altered( [ 'xx_XX' => 'Not a language' ] ), $structure['hash'] );
$brokenAlt	= \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $altered( [ 'en_US' => "\xC3\x28" ] ), $structure['hash'] );
check( 'an alt text of more than 250 characters, or of bytes that are not text, is refused with 400 (invalid) as the Images panel refuses it, one for a language the site does not have with 400 (value) - and no slot is made', $longAlt['status'] === 400 && $longAlt['code'] === 'invalid'
	&& $brokenAlt['status'] === 400 && $brokenAlt['code'] === 'invalid' && $otherAlt['status'] === 400 && $otherAlt['code'] === 'value' && \Nino\Images::getSlots( $appData ) === $slotsNow && $slotsNow !== $slotsBefore );

// A slot another request made in the meantime is left as it is
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$taken = $structure['model'];
$taken['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-texts/z/photo', 'text' => null, 'attributes' => [ 'alt' => '', 'focus' => '', 'ratio' => '', 'class' => '' ], 'create' => [ 'label' => 'Photo', 'width' => 800, 'height' => 400, 'alt' => [ 'en_US' => 'Another alt' ] ] ];
\Nino\Modules\Builder\Document::save( $appData, 'page-texts', $taken, $structure['hash'] );
check( 'a slot that is there already is left alone, its alt texts with it: what a mark says is made only of a slot that is not', ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/photo' ) ?: [] )['alt']['en_US'] === 'A house by the sea' );

// Without the permission of the Images panel no alt text is written: it is that panel's to write, for a slot that is there and for one the save makes
\Nino\Auth::insertUser( $appData, 'noimages@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/slots/manage', '/_admin/keys/manage' ] );
\Nino\Auth::loginUser( $appData, 'noimages@example.com', 'correct horse battery staple' );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$configFile = \Nino\Filesystem::path( $appData, '/config.php' );
// The state as it is when it is asked for: an arrow function, or a use by value, keeps $appData as it was when the closure was made, and would never show a slot the save made
$stateNow = static function() use ( &$appData, $configFile, $textsPath ): array {
	return [ \Nino\Images::getSlots( $appData ), (string) file_get_contents( $configFile ), (string) file_get_contents( $textsPath ) ];
};
$settled = $stateNow();
$marked = static function( array $create ) use ( $structure, $registry ): array {
	$model = $structure['model'];
	$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-texts/z/mood', 'text' => null, 'attributes' => $registry['components']['image']['defaults'], 'create' => $create ];
	return $model;
};
$withAlt = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $marked( [ 'label' => 'Mood', 'width' => 600, 'height' => 300, 'alt' => [ 'en_US' => 'A mood' ] ] ), $structure['hash'] );
$behind = $structure['model'];
$behind['blocks'][0]['background'] = [ 'slot' => '/template/page-texts/z/mood', 'focus' => null, 'create' => [ 'label' => 'Mood', 'width' => 600, 'height' => 300, 'alt' => [ 'de_DE' => 'Eine Stimmung' ] ] ];
$behindAlt = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $behind, $structure['hash'] );
$mixed = $marked( [ 'label' => 'Mood', 'width' => 600, 'height' => 300, 'alt' => [ 'en_US' => 'A mood' ] ] );
$mixed['blocks'][0]['background'] = [ 'slot' => '/template/page-texts/z/calm', 'focus' => null, 'create' => [ 'label' => 'Calm', 'width' => 600, 'height' => 300 ] ];
$mixedAlt = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $mixed, $structure['hash'] );
check( 'an account that may make slots but may not use the Images panel cannot give a new slot alt texts through the builder - for a picture or for a background, whichever slot of the save has them: 403, the permission named, before anything is made - no slot, nothing in the config, the file as it was',
	$withAlt['status'] === 403 && $withAlt['code'] === 'permission' && $withAlt['problems'] === [ '/_admin/images/manage' ] && $behindAlt['status'] === 403 && $behindAlt['code'] === 'permission' && $behindAlt['problems'] === [ '/_admin/images/manage' ]
	&& $mixedAlt['status'] === 403 && $mixedAlt['code'] === 'permission' && $mixedAlt['problems'] === [ '/_admin/images/manage' ] && $stateNow() === $settled );

$inColumn = $structure['model'];
$inColumn['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-texts/z/column-mood', 'focus' => null, 'create' => [ 'label' => 'Mood', 'width' => 600, 'height' => 300, 'alt' => [ 'en_US' => 'A column mood' ] ] ];
$inColumnAlt = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $inColumn, $structure['hash'] );
check( '...nor to the slot of a picture behind a column: 403, the permission named, before anything is made', $inColumnAlt['status'] === 403 && $inColumnAlt['code'] === 'permission' && $inColumnAlt['problems'] === [ '/_admin/images/manage' ] && $stateNow() === $settled );

$without = $marked( [ 'label' => 'Mood', 'width' => 600, 'height' => 300 ] );
$without['blocks'][0]['background'] = [ 'slot' => '/template/page-texts/z/calm', 'focus' => null, 'create' => [ 'label' => 'Calm', 'width' => 600, 'height' => 300, 'alt' => [] ] ];
$withoutAlt = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $without, $structure['hash'] );
check( '...the same account makes slots that have no alt text to write - a mark with none, a mark with an empty list of them: the Images panel is asked for only where there is a text for it', $withoutAlt['status'] === 200
	&& \Nino\Images::getSlot( $appData, '/template/page-texts/z/mood' ) !== false && \Nino\Images::getSlot( $appData, '/template/page-texts/z/calm' ) !== false
	&& ( ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/mood' ) ?: [] )['alt'] ?? [] ) === [] && ( ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/calm' ) ?: [] )['alt'] ?? [] ) === [] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

\Nino\Modules\Builder\Document::delete( $appData, 'page-texts' );

echo "\n";


// --- 7c. The card ------------------------------------------------------------

echo "Document - a component that holds components\n";

\Nino\Filesystem::putFileContent( $appData, '/templates/page-home-card.tpl', $cardFixture );
$cardPath		= \Nino\Filesystem::path( $appData, '/templates/page-home-card.tpl' );
$cardLoad		= \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$cardSame		= \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $cardLoad['model'], $cardLoad['hash'] );
$cardAt			= static fn( array $model ): array => $model['blocks'][0]['cols'][1]['components'][0];

check( 'a page with a card is loaded as the model the Reader makes of it, and a save without a change leaves the bytes as they were: the card with its children, the column with its spacing and the loop with its card are in the model and in the file alike',
	$cardLoad['status'] === 200 && $cardLoad['model'] === array_replace( $cardModel, [ 'file' => 'page-home-card' ] ) && $cardSame['status'] === 200 && file_get_contents( $cardPath ) === $cardFixture && $cardSame['hash'] === $cardLoad['hash'] && $cardSame['model'] === $cardLoad['model'] );

// The children of a card are components like any other: the key a new one names is made at the save, in the format its component asks for
$kidsMade = $cardLoad['model'];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][0]['create'] = [ 'value' => 'Our story' ];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][1]['create'] = [ 'value' => '<p>It began in a garage.</p>' ];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][2]['create'] = [ 'value' => 'Read on' ];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-home-card/story/note', 'text' => null, 'attributes' => $registry['components']['text']['defaults'], 'create' => [ 'value' => '<p>A note.</p>' ] ];
$kidsAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $kidsMade, $cardLoad['hash'] );
$stored = static fn( string $key, string $locale = 'en_US' ): ?string => \Nino\Text::entry( $appData, $key )['values'][$locale] ?? null;

check( 'the keys the children of a card name as new are made at the save, as those of the components of a column are: in every language, with the value they were given - and the file is the page with the fourth child, the card closing below it',
	$kidsAnswer['status'] === 200 && $stored( '/template/page-home-card/story/title' ) === 'Our story' && $stored( '/template/page-home-card/story/title', 'de_DE' ) === 'Our story' && $stored( '/template/page-home-card/story/text' ) === '<p>It began in a garage.</p>'
	&& $stored( '/template/page-home-card/story/button' ) === 'Read on' && $stored( '/template/page-home-card/story/note' ) === '<p>A note.</p>'
	&& str_contains( (string) file_get_contents( $cardPath ), "\t\t\t\t[button /template/page-home-card/story/button href=\"/contact\"]\n\t\t\t\t[text /template/page-home-card/story/note]\n\t\t\t[/card]\n" ) === true );
check( '...the format of a key is the one its component asks for - the text of a card is blocks, the title of a card plain - and the answer is the file as it reads, without the marks',
	( \Nino\Text::entry( $appData, '/template/page-home-card/story/text' )['format'] ?? '' ) === 'blocks' && ( \Nino\Text::entry( $appData, '/template/page-home-card/story/title' )['format'] ?? '' ) === 'plain'
	&& isset( $cardAt( $kidsAnswer['model'] )['components'][0]['create'] ) === false && array_column( $cardAt( $kidsAnswer['model'] )['components'], 'name' ) === [ 'card-title', 'text', 'button', 'text' ] );

// The kernel renders what the builder wrote: the card around its children, the loop with a card in each cell
$cardPage = \Nino\Html::renderHtml( $appData, '[template /templates/page-home-card]' );
check( 'the kernel renders the page with a card: the column with its spacing, the card around its children with the ground and the padding, the loop with a full-height dimmed card in each cell - no call left in the page',
	preg_match( '/\[\/?(?:title|card|card-title|text|image|button|stack)[ \]]/', $cardPage ) === 0 && str_contains( $cardPage, '<div class="nino-grid-s-100 nino-grid-m-66 nino-grid-l-66 nino-p-2 nino-mt-1 nino-mb-1">' ) === true
	&& str_contains( $cardPage, '<article class="nino-card nino-card--counter nino-p-3">' ) === true && str_contains( $cardPage, '<h3 class="nino-card-title">Our story</h3>' ) === true && str_contains( $cardPage, '<a class="nino-btn" href="/contact">Read on</a>' ) === true
	&& substr_count( $cardPage, '<article class="nino-card nino-card--dim nino-card--fill">' ) === 2 && strpos( $cardPage, '<h3 class="nino-card-title">App</h3>' ) < strpos( $cardPage, '<h3 class="nino-card-title">Web</h3>' )
	&& strpos( $cardPage, 'Our story' ) < strpos( $cardPage, 'It began in a garage.' ) && strpos( $cardPage, 'It began in a garage.' ) < strpos( $cardPage, 'Read on' ) && strpos( $cardPage, 'Read on' ) < strpos( $cardPage, 'A note.' ) );

// The texts typed for the key of a child are written to it, and the file is left alone
$cardNow	= \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$typed		= $cardNow['model'];
$typed['blocks'][0]['cols'][1]['components'][0]['components'][0]['values'] = [ 'de_DE' => 'Unsere Geschichte' ];
$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/templates/page-home-card.tpl' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$typedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $typed, $cardNow['hash'] );
rmdir( $lock );
check( 'the text typed for the key of a child of a card is written to the key, in its language, and the model is the file but for it: the file is not written - its lock is held, so that a write would answer 500 -, the answer is the model without the text',
	$typedAnswer['status'] === 200 && $stored( '/template/page-home-card/story/title', 'de_DE' ) === 'Unsere Geschichte' && $stored( '/template/page-home-card/story/title' ) === 'Our story'
	&& file_get_contents( $cardPath ) === $cardNow['source'] && $typedAnswer['hash'] === $cardNow['hash'] && isset( $cardAt( $typedAnswer['model'] )['components'][0]['values'] ) === false );
$typedOther = $cardNow['model'];
$typedOther['blocks'][0]['cols'][1]['components'][0]['components'][0]['values'] = [ 'xx_XX' => 'Nope' ];
$typedPlain = $cardNow['model'];
$typedPlain['blocks'][0]['cols'][1]['components'][0]['values'] = [ 'en_US' => 'A text for a card' ];
check( '...a text for a language the site does not have is refused with 400 (value), the child named - and a text for a card, which has no key, is refused with 400 (invalid) as it is for any component that has none',
	\Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $typedOther, $cardNow['hash'] )['code'] === 'value' && \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $typedPlain, $cardNow['hash'] )['code'] === 'invalid'
	&& str_contains( implode( ' ', \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $typedOther, $cardNow['hash'] )['problems'] ), 'the text of the component "card-title" is for "xx_XX"' ) === true );

// The picture behind a card is a slot an attribute names: made at the save from the mark of the attribute, like the one behind a column
$pictured = $cardNow['model'];
$pictured['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = '/template/page-home-card/story/card-picture';
$pictured['blocks'][0]['cols'][1]['components'][0]['attributes']['focus'] = '3';
$pictured['blocks'][0]['cols'][1]['components'][0]['attributes']['dim'] = '1';
$pictured['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => [ 'label' => 'Card picture', 'width' => 800, 'height' => 600, 'alt' => [ 'en_US' => 'A card' ] ] ];
$slotsBefore = \Nino\Images::getSlots( $appData );

\Nino\Auth::insertUser( $appData, 'cardkeys@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/keys/manage', '/_admin/images/manage' ] );
\Nino\Auth::loginUser( $appData, 'cardkeys@example.com', 'correct horse battery staple' );
$noSlots = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $pictured, $cardNow['hash'] );
check( 'an account that may not make slots cannot make the slot a card\'s picture names: 403, the permission of the Slots tab named, before anything is made - the file as it was, no slot',
	$noSlots['status'] === 403 && $noSlots['code'] === 'permission' && $noSlots['problems'] === [ '/_admin/slots/manage' ] && file_get_contents( $cardPath ) === $cardNow['source'] && \Nino\Images::getSlots( $appData ) === $slotsBefore );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

$picturedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $pictured, $cardNow['hash'] );
$cardSlot = \Nino\Images::getSlot( $appData, '/template/page-home-card/story/card-picture' ) ?: [];
check( 'the slot a card\'s picture names is made at the save with the label and the size of the mark and its alt texts by language, as the one behind a column is - no file yet -, and the card carries the picture, its focus and its dimming in its call',
	$picturedAnswer['status'] === 200 && $sorted( array_intersect_key( $cardSlot, [ 'label' => 1, 'width' => 1, 'height' => 1, 'filename' => 1 ] ) ) === $sorted( [ 'label' => 'Card picture', 'width' => 800, 'height' => 600, 'filename' => null ] ) && ( $cardSlot['alt'] ?? [] ) === [ 'en_US' => 'A card' ]
	&& str_contains( (string) file_get_contents( $cardPath ), '[card ground="counter" padding="3" background="/template/page-home-card/story/card-picture" focus="3" dim="1"]' ) === true );
check( '...the answer is the file as it reads: the attribute is there, the mark is not', ( $cardAt( $picturedAnswer['model'] )['attributes']['background'] ?? '' ) === '/template/page-home-card/story/card-picture' && isset( $cardAt( $picturedAnswer['model'] )['slots'] ) === false );

// A slot that is there already is left alone, with everything it holds
$again = $picturedAnswer['model'];
$again['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => [ 'label' => 'Another', 'width' => 100, 'height' => 100, 'alt' => [ 'en_US' => 'Another alt' ] ] ];
$again['blocks'][0]['cols'][1]['components'][0]['attributes']['focus'] = '4';
\Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $again, $picturedAnswer['hash'] );
check( '...and a mark for a slot that is there is the mark of a slot that is made: the slot another request made is left as it is', ( \Nino\Images::getSlot( $appData, '/template/page-home-card/story/card-picture' ) ?: [] ) === $cardSlot );

$latest = \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$otherLanguage = $latest['model'];
$otherLanguage['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = '/template/page-home-card/story/other-picture';
$otherLanguage['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => [ 'label' => 'Other', 'width' => 100, 'height' => 100, 'alt' => [ 'xx_XX' => 'Not a language' ] ] ];
$otherAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $otherLanguage, $latest['hash'] );
check( '...an alt text of the mark for a language the site does not have is refused with 400 (value), the picture and the card named - and no slot is made', $otherAnswer['status'] === 400 && $otherAnswer['code'] === 'value'
	&& str_contains( implode( ' ', $otherAnswer['problems'] ), 'the alt text of the picture "background" of the component "card" is for "xx_XX"' ) === true && \Nino\Images::getSlot( $appData, '/template/page-home-card/story/other-picture' ) === false );

$strayMark = $latest['model'];
$strayMark['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = '';
$strayMark['blocks'][0]['cols'][1]['components'][0]['attributes']['focus'] = '';
$strayMark['blocks'][0]['cols'][1]['components'][0]['attributes']['dim'] = '0';
$strayMark['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => [ 'label' => 'Stray', 'width' => 100, 'height' => 100 ], 'padding' => [ 'label' => 'Not a picture', 'width' => 1, 'height' => 1 ] ];
$strayAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $strayMark, $latest['hash'] );
check( 'a mark is read for an attribute of the type image that names a slot: the mark of a picture that was taken away again, and one for an attribute that is no picture, make nothing - the picture is gone from the card, the slot it had is not deleted', $strayAnswer['status'] === 200
	&& array_filter( \Nino\Images::getSlots( $appData ), static fn( array $slot ): bool => in_array( $slot['label'] ?? '', [ 'Stray', 'Not a picture' ], true ) ) === [] && \Nino\Images::getSlot( $appData, '/template/page-home-card/story/card-picture' ) !== false && ( $cardAt( $strayAnswer['model'] )['attributes']['background'] ?? 'x' ) === ''
	&& str_contains( (string) file_get_contents( $cardPath ), '[card ground="counter" padding="3"]' ) === true );

// What a card is made of, and what it is not
$bounds = \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$refusedCard = static function( callable $change ) use ( &$bounds, &$appData, $cardPath ): array {
	$model = $bounds['model'];
	$change( $model );
	$before = (string) file_get_contents( $cardPath );
	$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $model, $bounds['hash'] );
	return [ $answer['status'], $answer['code'] ?? '', implode( ' | ', $answer['problems'] ?? [] ), (string) file_get_contents( $cardPath ) === $before ];
};

$spacedWrong = [];
foreach( [ 'p' => '7', 'mt' => '10', 'mb' => 'big' ] as $key => $value ) {
	$answer = $refusedCard( static function( array &$model ) use ( $key, $value ): void { $model['blocks'][0]['cols'][1][$key] = $value; } );
	if( $answer[0] !== 400 || $answer[1] !== 'invalid' || str_contains( $answer[2], 'the '. $key. ' of column 2 of block 1 is none of the steps 0 to 6' ) === false || $answer[3] !== true )
		$spacedWrong[] = $key. '='. $value;
}
foreach( [ 'p' => 3, 'mt' => true, 'mb' => [ '1' ] ] as $key => $value ) {
	$answer = $refusedCard( static function( array &$model ) use ( $key, $value ): void { $model['blocks'][0]['cols'][1][$key] = $value; } );
	if( $answer[0] !== 400 || $answer[1] !== 'invalid' || str_contains( $answer[2], 'the '. $key. ' of column 2 of block 1 is none of the steps 0 to 6' ) === false || $answer[3] !== true )
		$spacedWrong[] = $key. '='. json_encode( $value );
}
check( 'a padding or a margin of a column that is none of the steps 0 to 6 - a step too far, a word, a number where the model has the step as text, a switch, a list - is refused with 400 (invalid), the key, the column and the block named, and the file is as it was'. ( $spacedWrong === [] ? '' : ' - '. implode( ', ', $spacedWrong ) ), $spacedWrong === [] );
$spaceRight = $bounds['model'];
$spaceRight['blocks'][0]['cols'][1]['p'] = '0';
$spaceRight['blocks'][0]['cols'][1]['mt'] = '6';
check( '...and every step of the eight is let through: 0 and 6 and the empty one', \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $spaceRight, $bounds['hash'] )['status'] === 200 && str_contains( (string) file_get_contents( $cardPath ), ' nino-p-0 nino-mt-6 nino-mb-1">' ) === true );
$bounds = \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );

$contented = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['content'] = '<p>Free</p>'; } );
check( 'a card that has a content is refused with 400 (invalid): what it holds is its children, which it has as components - the block and the card named, and nothing is written', $contented[0] === 400 && $contented[1] === 'invalid'
	&& str_contains( $contented[2], 'block 1 has the component "card" with a content, and it holds components' ) === true && $contented[3] === true );
$childless = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['components'] = []; } );
check( '...a component that holds none and has a list of children is refused as well - the list would be written as nothing and read back as nothing', $childless[0] === 400 && str_contains( $childless[2], 'block 1 has the component "image" with components, and it holds none' ) === true && $childless[3] === true );
$deeper = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][0]['components'] = []; } );
check( '...so is a child that has children of its own, a card in a card among them', $deeper[0] === 400 && str_contains( $deeper[2], 'block 1 has the component "card-title" with components, and it holds none' ) === true && $deeper[3] === true );
$cardInCard = $refusedCard( static function( array &$model ) use ( $registry ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'components' => [] ]; } );
check( '...a card in a card is refused with the card named', $cardInCard[0] === 400 && str_contains( $cardInCard[2], 'block 1 has the component "card" inside another that holds components' ) === true && $cardInCard[3] === true );
$stacked = $refusedCard( static function( array &$model ): void { $model['blocks'][1]['cols'][0]['stack']['components'] = []; } );
check( '...a loop that carries a list of components is refused with 400 (invalid) as well, the block and the loop named: its cells are the components of its column, the list would be written as nothing - and the file is as it was', $stacked[0] === 400 && $stacked[1] === 'invalid'
	&& str_contains( $stacked[2], 'block 2 has the loop "stack" with components, and it holds none' ) === true && $stacked[3] === true );
$notList = $refusedCard( static function( array &$model ) use ( $registry ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'] = [ 'first' => $registry['components']['text'] ]; } );
$notArray = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'] = 'title'; } );
$badChild = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][1] = 'a string'; } );
$badMark = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => 'not a mark' ]; } );
$badAlt = $refusedCard( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => [ 'alt' => [ 'en_US' => [ 'a list' ] ] ] ]; } );
check( '...children that are no list, a child that is no call, a mark that is no map and a mark whose alt text is no text are refused with 400 (invalid) as the shape of any call is, none of them an error - and the file is as it was',
	[ $notList[0], $notArray[0], $badChild[0], $badMark[0], $badAlt[0] ] === [ 400, 400, 400, 400, 400 ] && [ $notList[1], $notArray[1], $badChild[1], $badMark[1], $badAlt[1] ] === [ 'invalid', 'invalid', 'invalid', 'invalid', 'invalid' ]
	&& str_contains( $notList[2], 'with children that are no list' ) === true && str_contains( $notArray[2], 'with children that are no list' ) === true && str_contains( $badChild[2], 'has a call that is none' ) === true && str_contains( $badMark[2], 'has a call that is none' ) === true
	&& str_contains( $badAlt[2], 'has a text that is none' ) === true && $notList[3] === true && $notArray[3] === true && $badChild[3] === true && $badMark[3] === true && $badAlt[3] === true );

// What the sources of a card mean where it stands: its children read the fields of a loop like any component, its picture is a slot or a field
$sourced = static function( callable $change ) use ( &$bounds, &$appData, $registry ): string {
	$model = $bounds['model'];
	$change( $model );
	return implode( ' | ', \Nino\Modules\Builder\Document::problems( $appData, $model, $registry ) );
};
check( 'a child of a card in a column without a loop that reads a field of an element means nothing there, as a component of the column does: a problem, the section named', str_contains( $sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][0]['source'] = 'title'; } ),
	'the section "story": the source "title" of card-title means nothing in a column without a loop' ) === true );
check( '...in the cell of a loop it is a field of the type, or it is none', $sourced( static function( array &$model ): void { $model['blocks'][1]['cols'][0]['components'][0]['components'][0]['source'] = 'nope'; } ) === 'the section "services": the source "nope" of card-title is no field of the loop\'s type'
	&& $sourced( static function( array &$model ): void {} ) === '' );
check( '...the picture behind a card is a slot of the grammar or, in a loop, a field of the type; elsewhere it means nothing: a field in a column without a loop, a word that is no slot, a field the type has not',
	$sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = 'photo'; } ) === 'the section "story": the picture "photo" of card means nothing in a column without a loop: it is an image slot'
	&& $sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = '/Not A Slot'; } ) === 'the section "story": the picture "/Not A Slot" of card is no image slot'
	&& $sourced( static function( array &$model ): void { $model['blocks'][1]['cols'][0]['components'][0]['attributes']['background'] = 'photo'; } ) === 'the section "services": the picture "photo" of card is no field of the loop\'s type'
	&& $sourced( static function( array &$model ): void { $model['blocks'][1]['cols'][0]['components'][0]['attributes']['background'] = '/template/page-home-card/services/backdrop'; } ) === '' );
check( '...and a child that does not read back, a component the registry has not, makes the section one that does not read back, and the reason of the Reader is the problem - the card and the child named',
	str_contains( $sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][1]['name'] = 'osm'; } ), 'the section "story" is not valid: line' ) === true
	&& str_contains( $sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][1]['name'] = 'osm'; } ), 'osm' ) === true );
check( '...and a value no call can carry is a problem in the attributes of a card and in those of its children, as it is in a column', str_contains( $sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['attributes']['class'] = 'my"card'; } ), 'the attribute "class" of the component "card" holds a quote' ) === true
	&& str_contains( $sourced( static function( array &$model ): void { $model['blocks'][0]['cols'][1]['components'][0]['components'][2]['attributes']['href'] = '#a]b'; } ), 'the attribute "href" of the component "button" holds a quote' ) === true );

// A section renamed takes the keys of the children of its cards and the slots their pictures name along
$bounds = \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$tale = $bounds['model'];
$tale['blocks'][0]['id'] = 'tale';
$tale['blocks'][0]['renamedFrom'] = 'story';
$tale['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = '/template/page-home-card/story/card-picture';
$taleAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $tale, $bounds['hash'] );
$taleSource = (string) file_get_contents( $cardPath );
$taleSlot = \Nino\Images::getSlot( $appData, '/template/page-home-card/tale/card-picture' ) ?: [];
check( 'renaming a section rewrites the sources of the children of its cards and the picture a card names - in the file, in the keys and in the slots: the keys are under the new name, the slot is made again under it with the alt texts of the old one, which is left',
	$taleAnswer['status'] === 200 && str_contains( $taleSource, '[card-title /template/page-home-card/tale/title tag="h3"]' ) === true && str_contains( $taleSource, 'background="/template/page-home-card/tale/card-picture"' ) === true && str_contains( $taleSource, '/template/page-home-card/story/' ) === false
	&& $stored( '/template/page-home-card/tale/title' ) === 'Our story' && $stored( '/template/page-home-card/tale/note' ) === '<p>A note.</p>' && \Nino\Text::entry( $appData, '/template/page-home-card/story/title' ) === null
	&& ( $taleSlot['label'] ?? '' ) === 'Card picture' && ( $taleSlot['alt'] ?? [] ) === [ 'en_US' => 'A card' ] && \Nino\Images::getSlot( $appData, '/template/page-home-card/story/card-picture' ) !== false );

// A save that makes a key and writes a text and then cannot write the file puts everything back: the text as it was, the key it made taken away - and the slot it made stays, as those of the page's own components do
$bounds = \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$doomed = $bounds['model'];
$doomed['blocks'][0]['cols'][1]['components'][0]['components'][0]['values'] = [ 'en_US' => 'Changed for nothing' ];
$doomed['blocks'][0]['cols'][1]['components'][0]['components'][] = [ 'name' => 'card-subtitle', 'source' => '/template/page-home-card/tale/lead', 'text' => null, 'attributes' => $registry['components']['card-subtitle']['defaults'], 'create' => [ 'value' => 'Never made' ] ];
$doomed['blocks'][0]['cols'][1]['components'][0]['attributes']['ground'] = 'alt';
$doomed['blocks'][0]['cols'][1]['components'][0]['attributes']['background'] = '/template/page-home-card/tale/card-picture-2';
$doomed['blocks'][0]['cols'][1]['components'][0]['slots'] = [ 'background' => [ 'label' => 'Second card picture', 'width' => 640, 'height' => 480, 'alt' => [ 'en_US' => 'Another card' ] ] ];
$lock = \Nino\Filesystem::path( $appData, '/data' ). '/.locks/'. sha1( '/templates/page-home-card.tpl' ). '.lock';
@unlink( $lock );
mkdir( $lock );
$doomedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $doomed, $bounds['hash'] );
$doomedSlot = \Nino\Images::getSlot( $appData, '/template/page-home-card/tale/card-picture-2' ) ?: [];
check( 'a save whose file cannot be written answers 500 and puts back what it did: the text it wrote to the key of a child is as it was, the key it made for a new child is taken away again, the file is as it was - and the slot it made for the picture of the card stays, as the mark made it',
	$doomedAnswer['status'] === 500 && $doomedAnswer['code'] === 'write' && $stored( '/template/page-home-card/tale/title' ) === 'Our story' && \Nino\Text::entry( $appData, '/template/page-home-card/tale/lead' ) === null && file_get_contents( $cardPath ) === $bounds['source']
	&& $sorted( array_intersect_key( $doomedSlot, [ 'label' => 1, 'width' => 1, 'height' => 1, 'filename' => 1 ] ) ) === $sorted( [ 'label' => 'Second card picture', 'width' => 640, 'height' => 480, 'filename' => null ] ) && ( $doomedSlot['alt'] ?? [] ) === [ 'en_US' => 'Another card' ] );
rmdir( $lock );
$retryAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $doomed, $bounds['hash'] );
check( '...and the same save, tried again, is made: 200, the text is written, the key is made, the card has its ground and its picture, and the slot is there', $retryAnswer['status'] === 200 && $stored( '/template/page-home-card/tale/title' ) === 'Changed for nothing' && $stored( '/template/page-home-card/tale/lead' ) === 'Never made'
	&& str_contains( (string) file_get_contents( $cardPath ), '[card ground="alt" padding="3" background="/template/page-home-card/tale/card-picture-2"' ) === true && \Nino\Images::getSlot( $appData, '/template/page-home-card/tale/card-picture-2' ) !== false );

// The panel asks for the file a model would be written as: the card in it, in the lines the Writer makes
$sourceAnswer = \Nino\Modules\Builder\Document::source( $appData, $retryAnswer['model'] );
check( 'source answers the file with the card in it, its children on lines of their own one tab deeper, and the part of the section is the section as a block of the file', $sourceAnswer['status'] === 200 && str_contains( $sourceAnswer['source'], "\t\t\t[/card]\n" ) === true && $sourceAnswer['source'] === (string) file_get_contents( $cardPath )
	&& str_contains( $sourceAnswer['parts'][1]['source'], "\t\t\t\t[card-subtitle /template/page-home-card/tale/lead]\n\t\t\t[/card]\n" ) === true );

\Nino\Modules\Builder\Document::delete( $appData, 'page-home-card' );

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
check( '...the components are the kernel\'s: the heading with its tag and style, the button with its address, the texts of the keys', str_contains( $page, '<h3 class="nino-section-title nino-section-title--loud">Hello</h3>' ) === true
	&& str_contains( $page, '<a class="nino-btn nino-btn--primary" href="/contact">Contact us</a>' ) === true && str_contains( $page, '<h3 class="nino-section-title">Services</h3>' ) === true );
$tagged = $write( $read( $section( "[title /template/page-home/contact/title tag=\"h4\" style=\"loud\"]\n\t\t\t[subtitle /template/page-home/contact/title]\n\t\t\t[subtitle /template/page-home/contact/title tag=\"h5\"]\n\t\t\t[card-title /template/page-home/contact/title]" ). "\n" ) );
$drawn = \Nino\Html::renderHtml( $appData, $tagged );
check( '...the tag the builder writes is the tag the kernel draws: a title is an h4 where the call names h4, a subtitle a div where it names none and an h5 where it names h5, a title of a card an h4 where it names none - and the call of a default says nothing', str_contains( $tagged, '[title /template/page-home/contact/title tag="h4" style="loud"]' ) === true
	&& str_contains( $tagged, "[subtitle /template/page-home/contact/title]\n" ) === true && str_contains( $tagged, '[subtitle /template/page-home/contact/title tag="h5"]' ) === true && str_contains( $tagged, "[card-title /template/page-home/contact/title]\n" ) === true
	&& str_contains( $drawn, '<h4 class="nino-section-title nino-section-title--loud">Contact</h4>' ) === true && str_contains( $drawn, '<div class="nino-section-subtitle">Contact</div>' ) === true
	&& str_contains( $drawn, '<h5 class="nino-section-subtitle">Contact</h5>' ) === true && str_contains( $drawn, '<h4 class="nino-card-title">Contact</h4>' ) === true );
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
check( '...with the languages of the site and the native one, which is where the panel starts', array_column( $body['locales'], 'locale' ) === \Nino\Locales::getAvailableLocales( $appData ) && $body['native'] === 'en_US' && isset( $body['slots'][0]['alt'] ) === true );

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

// The texts of a save go through the same action, and a language the site does not have is answered in the panel's own code
\Nino\Filesystem::putFileContent( $appData, '/templates/page-api.tpl', $section( '[title /template/page-api/a/title]', 'a' ). "\n" );
foreach( [ 'en_US', 'de_DE' ] as $locale )
	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ): array {
		return (array) $texts + [ '[[/template/page-api/a/title]]' => 'Api title' ];
	} );
[ , $apiLoaded ] = callBuilderAction( $appData, 'apiLoad', [ 'file' => 'page-api' ] );
$apiTexts = $apiLoaded['model'];
$apiTexts['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'de_DE' => 'Api-Titel' ];
[ $status, $apiSaved ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-api', 'model' => $apiTexts, 'hash' => $apiLoaded['hash'] ] );
check( 'save writes the texts the model carries: the answer is the model as the file reads it, without them, and the text is the key\'s', $status === 200 && $apiSaved['hash'] === $apiLoaded['hash']
	&& isset( $apiSaved['model']['blocks'][0]['cols'][0]['components'][0]['values'] ) === false && ( \Nino\Text::entry( $appData, '/template/page-api/a/title' )['values']['de_DE'] ?? null ) === 'Api-Titel' );
$apiTexts['blocks'][0]['cols'][0]['components'][0]['values'] = [ 'xx_XX' => 'Not a language' ];
[ $status, $apiRefused ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-api', 'model' => $apiTexts, 'hash' => $apiLoaded['hash'] ] );
check( '...a text for a language the site does not have: 400, builder_value, the problem in the params', $status === 400 && $apiRefused['code'] === 'builder_value' && str_contains( implode( ' ', $apiRefused['params'] ), '"xx_XX"' ) === true
	&& ( \Nino\Text::entry( $appData, '/template/page-api/a/title' )['values']['de_DE'] ?? null ) === 'Api-Titel' );
\Nino\Modules\Builder\Document::delete( $appData, 'page-api' );

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
