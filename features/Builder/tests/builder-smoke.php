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
 *											writes, with a kind of animation) and on a sheet of
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
 *											and the alt texts a new slot is made with - written for an
 *											account that has the Images panel's permission and left out for
 *											one that has not - and how all of it is put back when the save
 *											fails -, the refusals, the wrap and
 *											the animation of the template, a copy of a template with its
 *											keys and slots and what stops it - and last the page the
 *											builder wrote, rendered by the kernel. The panel's script has
 *											a test of its own, builder-js-smoke.js, which runs at the end
 *											where node is there, over the three fixtures this suite
 *											writes (fixtures/page-home.json and page-home-card.json:
 *											the model the Reader makes of the example page and of the
 *											page with a card; fixtures/registry.json: what the registry
 *											of the kernel it runs against says of its components and its
 *											stacks) - written, not compared: the files stay committed so
 *											that node runs the script test alone, and a change of the
 *											registry or of the Reader shows as a change of them in
 *											git status after the next direct run of this suite (the
 *											Usage line below; bin/check.sh runs a copy of the feature
 *											and writes them into that copy, the committed files stay).
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

/**
 *	One fixture of the panel's script test, written as it is read: json, indented, the slashes and the letters of any
 *	language as they are, a line break at the end
 *
 *	@param		string		$name					The file below tests/fixtures/
 *	@param		array 		$data
 *
 *	@return 	bool											Whether the file was written
 */
$fixtureWritten = static function( string $name, array $data ): bool {

	$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	return $json !== false && file_put_contents( __DIR__. '/fixtures/'. $name, $json. "\n" ) !== false;
};

/**
 *	A fixture as the script test reads it back: the file decoded, null where there is none or it is no json
 *
 *	@param		string		$name
 *
 *	@return 	array|null
 */
$fixtureRead = static function( string $name ): ?array {

	$read = json_decode( (string) @file_get_contents( __DIR__. '/fixtures/'. $name ), true );

	return is_array( $read ) === true ? $read : null;
};


// --- 1. The manifest and the panel -------------------------------------------

echo "The manifest\n";

$manifest = \Nino\Features::manifest( $dir );

check( 'it validates, key "builder"', is_array( $manifest ) === true && $manifest['key'] === 'builder' && ninoWarnings() === [] );
check( 'it is written for the kernel that has the Components module, with the card called one, the stretched link, the slug and the options methods', is_array( $manifest ) === true && $manifest['nino'] === '^1.9' && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it is a feature for content, with nothing required, no settings and no data of its own', $manifest['category'] === 'content' && $manifest['requires'] === [] && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'it names and describes itself in both interface languages', \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'its manual says what it adds: the panel, and no shortcode, route or callback', $manifest['manual']['shortcodes'] === [] && $manifest['manual']['routes'] === [] && $manifest['manual']['callbacks'] === [] && isset( $manifest['manual']['panel']['Builder'] ) === true );
check( '...and tells, in both languages and in a line each, of the language picker, the texts of the keys and the pictures of the slots', array_diff( [ 'Language of the texts', 'Texts of the keys', 'Pictures of the slots' ], array_keys( $manifest['manual']['panel'] ) ) === [] && array_reduce( [ 'Language of the texts', 'Texts of the keys', 'Pictures of the slots' ], static fn( bool $carry, string $name ): bool => $carry === true && \Nino\Features::localized( $manifest['manual']['panel'][$name], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['panel'][$name], 'en_US' ), true ) );
check( '...and tells of the loop, of the lightbox, of the components by place and of the card and its form, in both languages and in a line each - the cards of a loop are gone from it, as the kernel has no such thing, and so are their words', array_diff( [ 'Loop', 'The lightbox', 'Components by place', 'The card', 'Properties of a card' ], array_keys( $manifest['manual']['panel'] ) ) === []
	&& isset( $manifest['manual']['panel']['Cards of a loop'] ) === false && array_reduce( [ 'Loop', 'The lightbox', 'Components by place', 'The card', 'Properties of a card' ], static fn( bool $carry, string $name ): bool => $carry === true && \Nino\Features::localized( $manifest['manual']['panel'][$name], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['panel'][$name], 'en_US' ), true )
	&& array_filter( [ 'en_US', 'de_DE' ], static function( string $locale ) use ( $dir ): bool {
		$texts = (array) include $dir. '/text/'. $locale. '.php';
		return array_filter( array_keys( $texts ), static fn( string $key ): bool => str_contains( $key, '/stack/card' ) === true || str_contains( $key, '/option/card-' ) === true ) !== [];
	} ) === [] );
$lightbox = [ 'en_US' => \Nino\Features::localized( $manifest['manual']['panel']['The lightbox'], 'en_US' ), 'de_DE' => \Nino\Features::localized( $manifest['manual']['panel']['The lightbox'], 'de_DE' ) ];
$byPlace = [ 'en_US' => \Nino\Features::localized( $manifest['manual']['panel']['Components by place'], 'en_US' ), 'de_DE' => \Nino\Features::localized( $manifest['manual']['panel']['Components by place'], 'de_DE' ) ];
check( '...and the lines of the lightbox and of the components by place are, in both languages, under a thousand bytes, which the kernel takes for a line and not a paragraph',
	strlen( $lightbox['en_US'] ) < 1000 && strlen( $lightbox['de_DE'] ) < 1000 && strlen( $byPlace['en_US'] ) < 1000 && strlen( $byPlace['de_DE'] ) < 1000 );
check( '...and names the markup it reads and writes besides the wrap and the picture of a column: the call of a component that holds components, and the classes of the six sides of the spacing, in both languages', array_diff( [ '[card padding="3"][card-title /slot][/card]', 'nino-mt-1 nino-mb-1 nino-pt-2 nino-pb-2 nino-pl-2 nino-pr-2' ], array_keys( $manifest['manual']['markup'] ) ) === []
	&& array_reduce( [ '[card padding="3"][card-title /slot][/card]', 'nino-mt-1 nino-mb-1 nino-pt-2 nino-pb-2 nino-pl-2 nino-pr-2' ], static fn( bool $carry, string $name ): bool => $carry === true && \Nino\Features::localized( $manifest['manual']['markup'][$name], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['markup'][$name], 'en_US' ), true ) );

$slugKey = array_values( array_filter( array_keys( $manifest['manual']['markup'] ), static fn( string $name ): bool => str_starts_with( $name, 'href="[[/nino/dir]]' ) === true ) );
check( '...and its example of a link to a page of the site goes on with the slug of the element, and names the slug and the uri in both languages',
	$slugKey === [ 'href="[[/nino/dir]][[/_nino/webpage/<uri>/uri]]/[[.slug]]"' ]
	&& array_reduce( [ 'en_US', 'de_DE' ], static fn( bool $carry, string $locale ): bool => $carry === true && str_contains( \Nino\Features::localized( $manifest['manual']['markup'][$slugKey[0]], $locale ), '[[.slug]]' ) === true
		&& str_contains( \Nino\Features::localized( $manifest['manual']['markup'][$slugKey[0]], $locale ), '[[.uri]]' ) === true, true ) );

$settingsLines = [ 'en_US' => \Nino\Features::localized( $manifest['manual']['panel']['Settings dialogs of columns and components'], 'en_US' ), 'de_DE' => \Nino\Features::localized( $manifest['manual']['panel']['Settings dialogs of columns and components'], 'de_DE' ) ];
check( '...and has a line of its own for the settings dialogs of columns and components, in both languages and under a thousand bytes each',
	$settingsLines['en_US'] !== '' && $settingsLines['de_DE'] !== '' && $settingsLines['en_US'] !== $settingsLines['de_DE'] && strlen( $settingsLines['en_US'] ) < 1000 && strlen( $settingsLines['de_DE'] ) < 1000 );
$hoverLines = array_filter( $manifest['manual']['panel'], static fn( mixed $line ): bool => str_contains( \Nino\Features::localized( $line, 'en_US' ), \Nino\Modules\Builder\Reader::HOVER_CLASS ) === true );
check( '...and names the class a picture takes for the hover of the page in a line that is told in both languages',
	$hoverLines !== [] && array_filter( $hoverLines, static fn( mixed $line ): bool => str_contains( \Nino\Features::localized( $line, 'de_DE' ), \Nino\Modules\Builder\Reader::HOVER_CLASS ) === false || \Nino\Features::localized( $line, 'de_DE' ) === \Nino\Features::localized( $line, 'en_US' ) ) === [] );
check( '...and has a line of its own for the tools of a frame - how the toolbar is named and how large a tool is to hit - in both languages',
	isset( $manifest['manual']['panel']['The tools of a frame'] ) === true && array_diff( [ 'en_US', 'de_DE' ], array_keys( $manifest['manual']['panel']['The tools of a frame'] ) ) === []
	&& \Nino\Features::localized( $manifest['manual']['panel']['The tools of a frame'], 'de_DE' ) !== \Nino\Features::localized( $manifest['manual']['panel']['The tools of a frame'], 'en_US' ) );
check( '...and names the seven blocks of a dialog - the loop is one of them - and the entries this patch rewrites, each in both languages and a line under a thousand bytes, in a manual of at most forty entries of the panel', ( static function() use ( $manifest ): bool {
	$names = [ 'The seven blocks', 'The dialog of a section', 'The spacing and width of a section', 'Properties of a card', 'Loop', 'Placeholders', 'The card', 'Heads of the frames', 'Pictures of the slots', 'Copy and Paste' ];
	foreach( $names as $name )
		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( isset( $manifest['manual']['panel'][$name][$locale] ) === false || strlen( $manifest['manual']['panel'][$name][$locale] ) >= 1000 )
				return false;
	return count( $manifest['manual']['panel'] ) <= 40 && str_contains( $manifest['manual']['panel']['The seven blocks']['en_US'], 'Loop:' ) === true && str_contains( $manifest['manual']['panel']['The seven blocks']['de_DE'], 'Loop:' ) === true
		&& str_contains( $manifest['manual']['panel']['Properties of a card']['en_US'], 'Link:' ) === true && str_contains( $manifest['manual']['panel']['Properties of a card']['de_DE'], 'Link:' ) === true && str_contains( $manifest['manual']['panel']['Pictures of the slots']['en_US'], 'side by side' ) === true
		&& str_contains( $manifest['manual']['panel']['Copy and Paste']['de_DE'], ' Du ' ) === true
		&& str_contains( $manifest['manual']['panel']['Copy and Paste']['en_US'], 'Ctrl+C and Ctrl+V' ) === true && str_contains( $manifest['manual']['panel']['Copy and Paste']['de_DE'], 'Strg+C und Strg+V' ) === true
		&& trim( $manifest['manual']['panel']['Copy and Paste']['en_US'] ) !== '' && trim( $manifest['manual']['panel']['Copy and Paste']['de_DE'] ) !== '' && $manifest['manual']['panel']['Copy and Paste']['en_US'] !== $manifest['manual']['panel']['Copy and Paste']['de_DE'];
} )() );
check( '...and the entry on the spacing of a section names the gap between the columns of its row, by the word the panel gives the select, in both languages', ( static function() use ( $manifest, $dir ): bool {
	foreach( [ 'en_US', 'de_DE' ] as $locale ) {
		$word = (string) ( (array) include $dir. '/text/'. $locale. '.php' )['[[/_admin/builder/section/row-gap]]'];
		if( $word === '' || str_contains( \Nino\Features::localized( $manifest['manual']['panel']['The spacing and width of a section'], $locale ), $word ) === false )
			return false;
	}
	return true;
} )() );
check( 'the words of the patch are in both languages: the block Loop, the group Background image, the three hints of the width, the three lines of the Link block of a linked card - whose link it takes, that only one should be, and what to add where it has none -, the row of a picture in its group, the tools Copy and Paste with what each says and why a paste is refused - and the place of the content of a cover is called Content in the cover, and the gap between the columns of a row has a word and a hint', ( static function() use ( $dir ): bool {
	$words = [ 'block/loop/label', 'group/background-image', 'hint/content-pos', 'hint/text', 'hint/row-align', 'link/no-stretched', 'link/stretched', 'link/two-stretched', 'section/content-pos', 'section/row-gap', 'hint/row-gap', 'source/picture', 'menu/copy', 'menu/paste', 'menu/paste-of', 'reason/paste-empty', 'reason/paste-unknown', 'reason/paste-place' ];
	$texts = [ 'en_US' => (array) include $dir. '/text/en_US.php', 'de_DE' => (array) include $dir. '/text/de_DE.php' ];
	foreach( $texts as $list )
		foreach( $words as $word )
			if( trim( (string) ( $list['[[/_admin/builder/'. $word. ']]'] ?? '' ) ) === '' )
				return false;
	foreach( $texts as $list )
		if( substr_count( (string) $list['[[/_admin/builder/menu/paste-of]]'], '%s' ) !== 1 || substr_count( (string) $list['[[/_admin/builder/reason/paste-unknown]]'], '%s' ) !== 1 || substr_count( (string) $list['[[/_admin/builder/reason/paste-place]]'], '%s' ) !== 1 )
			return false;
	return $texts['en_US']['[[/_admin/builder/section/content-pos]]'] === 'Content in the cover' && $texts['de_DE']['[[/_admin/builder/section/content-pos]]'] === 'Inhalt im Cover'
		&& str_contains( $texts['de_DE']['[[/_admin/builder/hint/content-pos]]'], 'Zeile in einem Cover oder Parallax' ) === true;
} )() );
check( 'the head of the editor is as tall as the heads of the workbench - the rail\'s brand row and the head of a panel, 52px, as a plain value - with the line under it kept', ( static function() use ( $dir ): bool {
	$css = (string) file_get_contents( $dir. '/assets/admin.css' );
	return preg_match( '/\n\.builder-editor-head \{[^}]*\n\tbox-sizing: border-box;\n\tmin-height: 52px;[^}]*\n\tborder-bottom: 1px solid var\(--editor-separator\);\n\}/', $css ) === 1;
} )() );

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
check( 'the script writes no fill of the kernel out: the kernel puts the fills into a script it serves, so that the one a script holds in a string - the directory in front of the address of a link - would be gone from it, and the script puts it together',
	array_filter( explode( "\n", (string) file_get_contents( $dir. '/assets/admin.js' ) ), static fn( string $line ): bool => preg_match( '~^\s*(?://|\*|/\*)~', $line ) !== 1 && str_contains( $line, '[[/' ) === true ) === [] );
check( '...whose words are the same keys in both languages', array_keys( (array) include $dir. '/text/en_US.php' ) === array_keys( (array) include $dir. '/text/de_DE.php' ) );
check( '...and the switch of a picture has its label and its hint in both of them, the words of the two languages differing', array_filter( [ '[[/_admin/builder/common/hover]]', '[[/_admin/builder/hint/hover]]' ], static function( string $key ) use ( $dir ): bool {
	$english = (array) include $dir. '/text/en_US.php';
	$german = (array) include $dir. '/text/de_DE.php';
	return trim( (string) ( $english[$key] ?? '' ) ) === '' || trim( (string) ( $german[$key] ?? '' ) ) === '' || $english[$key] === $german[$key];
} ) === [] );
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

$kernelComponents	= \Nino\Modules\Components::components( $appData );
$kernelStacks			= \Nino\Modules\Components::stacks( $appData );

check( 'the components and the stacks of the kernel are in it, each with the defaults of its attributes the kernel has - in the order the kernel registers them, which is the order a column offers the components',
	$kernelComponents !== [] && $kernelStacks !== [] && array_keys( $registry['components'] ) === array_keys( $kernelComponents ) && array_keys( $registry['stacks'] ) === array_keys( $kernelStacks )
	&& array_filter( $registry['components'], static fn( array $schema, string $name ): bool => $schema['defaults'] !== \Nino\Modules\Components::defaults( $appData, $name ), ARRAY_FILTER_USE_BOTH ) === []
	&& array_filter( $registry['stacks'], static fn( array $schema, string $name ): bool => $schema['defaults'] !== \Nino\Modules\Components::defaults( $appData, $name, true ), ARRAY_FILTER_USE_BOTH ) === []
	&& $registry['components']['title']['defaults'] === [ 'tag' => 'h3', 'style' => '', 'color' => '', 'href' => '', 'target' => '', 'class' => '' ]
	&& $registry['stacks']['stack']['defaults']['gap'] === '2' );
check( '...the card says what it holds, and is the one that does: a component whose schema has holds is a container, and nothing else is - the Builder asks the schema and names none', \Nino\Modules\Builder\Reader::holds( $registry['components']['card'] ) === true && $registry['components']['card']['holds'] === 'card'
	&& array_filter( $registry['components'], static fn( array $schema ): bool => \Nino\Modules\Builder\Reader::holds( $schema ) === true ) === [ 'card' => $registry['components']['card'] ] && \Nino\Modules\Builder\Reader::holds( [ 'holds' => '' ] ) === false && \Nino\Modules\Builder\Reader::holds( [] ) === false );
$reader = \Nino\Modules\Builder\Reader::class;
$marked = array_filter( $registry['components'], static fn( array $schema ): bool => array_key_exists( 'surface', $schema ) === true );
check( '...the registry marks the components that are a surface by what their schemas say and by no name: a container is a card, a picture that no container claims is an image, and the picture of a card is none - it follows the card',
	$marked !== [] && array_values( array_unique( array_column( $marked, 'surface' ) ) ) === [ 'card', 'image' ] && array_filter( $marked, static fn( array $schema ): bool => $schema['surface'] !== $reader::surface( $schema, $registry['components'] ) ) === []
	&& array_filter( $registry['components'], static fn( array $schema ): bool => array_key_exists( 'surface', $schema ) === false && $reader::surface( $schema, $registry['components'] ) !== '' ) === []
	&& array_filter( $marked, static fn( array $schema ): bool => ( $schema['surface'] === 'card' ) !== $reader::holds( $schema ) ) === []
	&& array_filter( $marked, static fn( array $schema ): bool => $schema['surface'] === 'image' && ( $schema['source'] !== 'image' || array_filter( $registry['components'], static fn( array $other ): bool => $reader::holds( $other ) === true && in_array( $other['holds'], (array) ( $schema['in'] ?? [] ), true ) === true ) !== [] ) ) === [] );
$grounds = [ '', 'counter', 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ];
$holder = static fn( array $attributes ): array => [ 'source' => 'content', 'holds' => 'things', 'attributes' => $attributes ];
check( '...and the rule is the registry\'s: a component that holds a place and paints - it declares a ground, a select over the grounds a column may paint, or a picture, an attribute of the type image - is a card whatever it is called and whatever the attributes are called, one that paints nothing is none',
	$reader::surface( $holder( [ 'tint' => [ 'type' => 'select', 'options' => $grounds ] ] ), [] ) === 'card' && $reader::surface( $holder( [ 'behind' => [ 'type' => 'image' ] ] ), [] ) === 'card'
	&& $reader::surface( $holder( [ 'speed' => [ 'type' => 'int', 'default' => 40 ], 'wrap' => [ 'type' => 'bool' ], 'way' => [ 'type' => 'select', 'options' => [ 'left', 'right' ] ] ] ), [] ) === ''
	&& $reader::surface( $holder( [ 'tint' => [ 'type' => 'select', 'options' => array_slice( $grounds, 0, 5 ) ] ] ), [] ) === '' && $reader::surface( $holder( [ 'behind' => [ 'type' => 'string' ] ] ), [] ) === '' && $reader::surface( $holder( [] ), [] ) === ''
	&& $reader::surface( [ 'source' => 'content', 'holds' => 'things' ], [] ) === '' && $reader::paints( $holder( [ 'behind' => [ 'type' => 'image' ] ] ) ) === true && $reader::paints( $holder( [] ) ) === false );
check( '...a picture standing in a place that a card holds is none and one in any other is an image - and so is one standing in the place of a container that paints nothing, which it does not follow - a text and a loop are none',
	$reader::surface( [ 'source' => 'image', 'in' => [ 'things' ] ], [ $holder( [ 'behind' => [ 'type' => 'image' ] ] ) ] ) === '' && $reader::surface( [ 'source' => 'image', 'in' => [ 'things' ] ], [ $holder( [ 'speed' => [ 'type' => 'int' ] ] ) ] ) === 'image'
	&& $reader::surface( [ 'source' => 'image', 'in' => [ 'section' ] ], [ $holder( [ 'behind' => [ 'type' => 'image' ] ] ) ] ) === 'image' && $reader::surface( [ 'source' => 'image' ], [] ) === 'image'
	&& $reader::surface( [ 'source' => 'text' ], [] ) === '' && $reader::surface( [ 'source' => 'none' ], [] ) === '' && $reader::surface( [ 'grid' => true ], [] ) === '' );
check( '...and a loop is a grid and nothing more: its defaults carry no card, the cell is a div', array_filter( $registry['stacks'], static fn( array $schema ): bool => array_key_exists( 'card', $schema['defaults'] ) === true ) === [] );
check( '...the six titles name their tag and no level: one select of the tags their place takes, the default among them, then the style and the colour, which are variants - a hero is an h2, a section an h3, a card an h4, and the subtitles a p, a div and a p', array_filter(
	[ 'hero-title' => 'h2', 'hero-subtitle' => 'p', 'title' => 'h3', 'subtitle' => 'div', 'card-title' => 'h4', 'card-subtitle' => 'p' ],
	static fn( string $default, string $name ): bool => array_keys( $registry['components'][$name]['attributes'] ) !== [ 'tag', 'style', 'color' ] || $registry['components'][$name]['attributes']['tag']['type'] !== 'select'
		|| $registry['components'][$name]['attributes']['style']['type'] !== 'variant' || $registry['components'][$name]['attributes']['color']['type'] !== 'variant'
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

// Every value the registry offers is a value the Reader reads and the Writer writes back - whatever the kernel offers: the tag of a heading among them, the h2 of the title of a
// section where the kernel has one - and a call that has no address, where the component has one to have, reads back the same
[ $offered, $withoutAddress, $offerProblems ] = ( static function() use ( $registry, $read, $write, $page, $section ): array {

$offered = 0;
$withoutAddress = 0;
$offerProblems = [];

foreach( $registry['components'] as $name => $schema ) {

	if( in_array( $schema['source'], [ 'text', 'image', 'none' ], true ) === false || \Nino\Modules\Builder\Reader::holds( $schema ) === true )
		continue;

	$source = $schema['source'] === 'none' ? '' : ' /template/page-x/a/'. $name;

	foreach( (array) $schema['attributes'] as $attribute => $declared )
		foreach( in_array( $declared['type'] ?? '', [ 'select', 'variant' ], true ) === true ? (array) ( $declared['options'] ?? [] ) : [] as $option ) {

			$call = '['. $name. $source. ' '. $attribute. '="'. $option. '"]';
			$model = $read( $page( $section( $call ) ) );
			$component = $model['blocks'][0]['cols'][0]['components'][0] ?? null;

			$offered++;

			if( $component === null || $component['name'] !== $name || $component['attributes'][$attribute] !== (string) $option || $read( $write( $model ) ) !== $model )
				$offerProblems[] = $call;
		}

	// A fixed text and no address: the call has the one and the component the other, which it leaves out
	foreach( (array) $schema['attributes'] as $attribute => $declared )
		if( ( $declared['type'] ?? '' ) === 'href' && $schema['source'] === 'text' ) {

			$call = '['. $name. ' text="Go"]';
			$model = $read( $page( $section( $call ) ) );
			$component = $model['blocks'][0]['cols'][0]['components'][0] ?? null;

			$withoutAddress++;

			if( $component === null || $component['name'] !== $name || $component['text'] !== 'Go' || $component['attributes'][$attribute] !== '' || str_contains( $write( $model ), $attribute. '=' ) === true || $read( $write( $model ) ) !== $model )
				$offerProblems[] = $call;
		}
}

return [ $offered, $withoutAddress, $offerProblems ];
} )();

check( 'every value the registry offers for a select or a variant - each option of each attribute of each component that has a source of its own or none: the tag of a heading among them, whichever tags the kernel offers - is read back from a call that has it, and written as it was read',
	$offered > 0 && $offerProblems === [] );
check( '...and a call of a component that has an address to go to, with a text of its own and none - a button that leads nowhere - is read back as the component with no address, and written without one', $withoutAddress > 0 && $offerProblems === [] );

echo "\n";


// --- 3. The example page: read, write, read again ----------------------------

echo "The example page of the concept\n";

$fixture	= (string) file_get_contents( __DIR__. '/fixtures/page-home.tpl' );
$model		= $read( $fixture );

check( 'the head: its name, its header and its footer', $model['name'] === 'Home' && $model['header'] === 'html-header' && $model['footer'] === 'html-footer' );
check( 'four blocks in the order of the file: a section, a section, a block of html, a section', array_column( $model['blocks'], 'kind' ) === [ 'section', 'section', 'html', 'section' ]
	&& [ $model['blocks'][0]['id'], $model['blocks'][1]['id'], $model['blocks'][3]['id'] ] === [ 'hero', 'services', 'contact' ] );

$hero = $model['blocks'][0];
check( 'the hero: its classes are settings', $hero['settings']['color'] === 'black' && $hero['settings']['cover'] === 100
	&& $hero['settings']['parallax'] === false && $hero['settings']['dim'] === true && $hero['settings']['contentPos'] === '' && $hero['settings']['vpa'] === '' && $hero['settings']['row'] === 'wide' && $hero['settings']['rowAlign'] === 'middle'
	&& $hero['settings']['custom'] === '' );
check( '...its background is a slot with a focus', $hero['background'] === [ 'slot' => '/template/page-home/hero/background', 'focus' => 5 ] );
check( '...its column is 100, 100 and 66 wide, aligned left, and holds three components', $hero['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 66 ] && $hero['cols'][0]['text'] === 'left'
	&& $hero['cols'][0]['stack'] === null && array_column( $hero['cols'][0]['components'], 'name' ) === [ 'title', 'subtitle', 'button' ] );
check( '...a call carries its source and its attributes, every one of the schema, the default where the call is silent - and after them the six sides of its spacing, none where its class says none',
	$hero['cols'][0]['components'][0] === [ 'name' => 'title', 'source' => '/template/page-home/hero/title', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => 'loud', 'color' => '', 'href' => '', 'target' => '', 'class' => '' ] ] + array_fill_keys( \Nino\Modules\Builder\Reader::SPACING, '' ) );

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

check( 'the model the panel\'s script test reads (fixtures/page-home.json) is written from what the Reader makes of the example page: the file is there, is json, and holds the blocks of the page under the name of its file',
	$fixtureWritten( 'page-home.json', array_replace( $model, [ 'file' => 'page-home' ] ) ) === true && is_array( $fixtureRead( 'page-home.json' )['blocks'] ?? null ) === true
	&& ( $fixtureRead( 'page-home.json' )['file'] ?? '' ) === 'page-home' && $fixtureRead( 'page-home.json' ) === json_decode( (string) json_encode( array_replace( $model, [ 'file' => 'page-home' ] ) ), true ) );

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
	[ '[title /template/page-home/hero/title class="" tag="h3" style="loud"]', '[title /template/page-home/contact/title tag="h3" style=""]', "\t\t<div class=\"nino-grid-100 nino-grid-s-100 nino-grid-m-100 nino-grid-l-100\">\n\t\t\t[title" ],
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

// Every switch, effect and speed: the classes are in one order, and what is written is read by its tiers
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

check( 'the switch, the effect with its strength and the speed are classes of the wrap, in that order: all '. $combined. ' combinations of them are written as they are, and read back as they were set'. ( $wrong === [] ? '' : ' - '. implode( ', ', array_slice( $wrong, 0, 5 ) ) ), $wrong === [] && $combined === 2 * ( count( \Nino\Modules\Builder\Reader::EFFECTS ) + 1 ) * ( count( \Nino\Modules\Builder\Reader::SPEEDS ) + 1 ) );

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
check( '...the card is a component node that carries its children, in order, as components, and no content: the title, the text and the button, each a node as it is in a column - and its radius and the offset of its ground at either side, as a surface that paints has them, and the six sides of its spacing',
	array_keys( $cardNode ) === array_merge( [ 'name', 'source', 'text', 'attributes', 'radius' ], array_values( $reader::BG_SPACING ), $reader::SPACING, [ 'components' ] ) && $cardNode['radius'] === '' && array_intersect_key( $cardNode, array_flip( $reader::BG_SPACING ) ) === array_fill_keys( $reader::BG_SPACING, '' )
	&& $cardNode['source'] === '' && $cardNode['text'] === null && array_column( $cardNode['components'], 'name' ) === [ 'card-title', 'text', 'button' ]
	&& array_column( $cardNode['components'], 'source' ) === [ '/template/page-home-card/story/title', '/template/page-home-card/story/text', '/template/page-home-card/story/button' ] && array_key_exists( 'content', $cardNode ) === false );
// The attributes the call of the card in the file sets, which the node holds besides the defaults of the registry
preg_match( '/\[card ([^\]]*)\]/', $cardFixture, $cardCall );
preg_match_all( '/([a-z]+)="([^"]*)"/', $cardCall[1] ?? '', $cardSet );
$cardSetBy = array_combine( $cardSet[1], $cardSet[2] );
check( '...it has every attribute of its schema, in the order of the schema, the default where the call is silent - the ones the file sets are the call\'s - and its children theirs', $cardSetBy !== [] && $cardNode['attributes'] === array_replace( $registry['components']['card']['defaults'], $cardSetBy )
	&& array_keys( $cardNode['attributes'] ) === array_merge( array_keys( $registry['components']['card']['attributes'] ), [ 'class' ] ) && array_keys( $cardNode['attributes'] ) === array_keys( $registry['components']['card']['defaults'] ) && $cardNode['components'][0]['attributes'] === array_replace( $registry['components']['card-title']['defaults'], [ 'tag' => 'h3' ] ) && array_key_exists( 'components', $cardNode['components'][0] ) === false );
check( '...a plain component carries no list of children, whatever it is: only a component that holds components does', array_filter( array_merge( $story['cols'][0]['components'], $cardNode['components'], $cell['components'][0]['components'] ), static fn( array $node ): bool => array_key_exists( 'components', $node ) === true ) === [] );
check( '...in a loop the card stands in the content of the stack, once for each element: its picture is a field of the element, so is its title, and the stack holds the card and nothing else', $cell['components'][0]['attributes']['background'] === 'image' && $cell['components'][0]['attributes']['focus'] === '5' && $cell['components'][0]['attributes']['dim'] === '1' && $cell['components'][0]['attributes']['fill'] === '1'
	&& $cell['components'][0]['components'][0]['source'] === 'title' && $cell['stack']['source'] === '/services' && array_key_exists( 'components', $cell['stack'] ) === false );
check( '...the column beside the picture has a padding and a margin above and below it, the picture\'s column has none: the six sides - the classes nino-mt-1, nino-mb-1 and nino-p-2, which says the four paddings at once - are settings, and are not among the custom ones',
	array_values( array_intersect_key( $story['cols'][1], array_flip( $reader::SPACING ) ) ) === [ '1', '1', '2', '2', '2', '2' ] && array_keys( array_intersect_key( $story['cols'][1], array_flip( $reader::SPACING ) ) ) === $reader::SPACING && $story['cols'][1]['custom'] === ''
	&& array_intersect_key( $story['cols'][0], array_flip( $reader::SPACING ) ) === array_fill_keys( $reader::SPACING, '' ) );
check( 'the model the panel\'s script test reads (fixtures/page-home-card.json) is written from what the Reader makes of the page with a card: the file is there, is json, and holds the blocks of the page under the name of its file',
	$fixtureWritten( 'page-home-card.json', array_replace( $cardModel, [ 'file' => 'page-home-card' ] ) ) === true && is_array( $fixtureRead( 'page-home-card.json' )['blocks'] ?? null ) === true
	&& ( $fixtureRead( 'page-home-card.json' )['file'] ?? '' ) === 'page-home-card' && $fixtureRead( 'page-home-card.json' ) === json_decode( (string) json_encode( array_replace( $cardModel, [ 'file' => 'page-home-card' ] ) ), true ) );
check( 'writing the model gives the page back, byte for byte - the card on a line, its children one tab deeper on lines of their own, its closing tag at the depth of its call, in a loop one tab deeper still - and reading what was written gives the model', $write( $cardModel ) === $cardFixture && $read( $write( $cardModel ) ) === $cardModel && $write( $read( $write( $cardModel ) ) ) === $cardFixture );

// A card written by hand is read all the same, and written in the one form: its attributes in the order of the schema, its children and its closing tag on lines of their own
$handCard = $page( $section( "[card ground=\"counter\" padding=\"3\"][card-title /template/page-x/a/title][text /template/page-x/a/text][/card]" ) );
$handNode = $read( $handCard )['blocks'][0]['cols'][0]['components'][0];
check( 'a card whose attributes are in another order and whose children stand on its line is read as the card it is, and written in the canonical form', $handNode['attributes']['ground'] === 'counter' && $handNode['attributes']['padding'] === '3' && array_column( $handNode['components'], 'name' ) === [ 'card-title', 'text' ]
	&& $write( $read( $handCard ) ) === $inWrap( $page( $section( "[card padding=\"3\" ground=\"counter\"]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t\t[text /template/page-x/a/text]\n\t\t\t[/card]" ) ) ) );

// How far in from either side the picture behind a card starts: split off the classes of its call as the radius of a surface is. The card is the one component that paints a ground, and the two classes are
// taken off its call whatever it holds - they are idle without a picture, as its radius is
$bgCardOf			= static fn( string $class ): string => $page( $section( "[card class=\"". $class. "\"]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t[/card]" ) );
$bgCardWrong	= [];

foreach( $reader::BG_STEPS as $step )
	foreach( $reader::BG_SPACING as $side => $key ) {

		$source	= $bgCardOf( 'nino-bg-'. $side. '-'. $step );
		$node		= $read( $source )['blocks'][0]['cols'][0]['components'][0];

		if( $node[$key] !== $step || $node['attributes']['class'] !== '' || $write( $read( $source ) ) !== $inWrap( $source ) || $read( $write( $read( $source ) ) ) !== $read( $source ) )
			$bgCardWrong[] = $key. ' '. $step;
	}

check( 'how far in from either side the picture behind a card starts is split off the classes of its call: the node has the step for either side after its radius, the class attribute the classes that are left - read from nino-bg-pl-<n> and nino-bg-pr-<n> whatever the card holds, and written back byte for byte: all '
	. count( $reader::BG_STEPS ) * count( $reader::BG_SPACING ). ' classes'. ( $bgCardWrong === [] ? '' : ' - '. implode( ', ', $bgCardWrong ) ), $bgCardWrong === [] );

$bgCardFile		= $bgCardOf( $reader::RADIUS_CLASS['alternative']. ' nino-bg-pl-0 nino-bg-pr-2 my-card' );
$bgCardMix		= $bgCardOf( 'my-card nino-bg-pr-2 '. $reader::RADIUS_CLASS['alternative']. ' nino-bg-pl-0' );
$bgCardNode		= $read( $bgCardMix )['blocks'][0]['cols'][0]['components'][0];
$bgCardTwice	= $read( $bgCardOf( 'nino-bg-pl-1 nino-bg-pl-4 nino-bg-pl-2' ) )['blocks'][0]['cols'][0]['components'][0];
$bgNotCard		= $read( $page( $section( '[title /template/page-x/a/title class="nino-bg-pl-1"]' ) ) )['blocks'][0]['cols'][0]['components'][0];
$bgNotCard2		= $read( $page( $section( '[image /template/page-x/a/photo alt="" class="nino-bg-pl-1"]' ) ) )['blocks'][0]['cols'][0]['components'][0];
check( '...the radius, then the left side, then the right, then the classes of the card, whatever order the file has them in; a second class of a side, or a step the scale has not, is a class of the card, and a component that is no card has the class as it has any other - nothing is split off it',
	$bgCardNode['radius'] === 'alternative' && $bgCardNode['bgPl'] === '0' && $bgCardNode['bgPr'] === '2' && $bgCardNode['attributes']['class'] === 'my-card' && $write( $read( $bgCardMix ) ) === $inWrap( $bgCardFile ) && $write( $read( $bgCardFile ) ) === $inWrap( $bgCardFile )
	&& $bgCardTwice['bgPl'] === '1' && $bgCardTwice['attributes']['class'] === 'nino-bg-pl-4 nino-bg-pl-2' && array_key_exists( 'bgPl', $bgNotCard ) === false && $bgNotCard['attributes']['class'] === 'nino-bg-pl-1' && array_key_exists( 'bgPl', $bgNotCard2 ) === false && $bgNotCard2['attributes']['class'] === 'nino-bg-pl-1' );

// A card with nothing between its tags is a card with no children, and is written with its closing tag on a line of its own
$emptyCard = $page( $section( '[card][/card]' ) );
$emptyNode = $read( $emptyCard )['blocks'][0]['cols'][0]['components'][0];
check( '...a card with nothing between its tags holds nothing: a list of no children, and a card that is written has its closing tag below its call, and reads again as the same card', $emptyNode['components'] === [] && $emptyNode['attributes'] === $registry['components']['card']['defaults']
	&& $write( $read( $emptyCard ) ) === $inWrap( $page( $section( "[card]\n\t\t\t[/card]" ) ) ) && $read( $write( $read( $emptyCard ) ) ) === $read( $emptyCard ) );
check( '...a call without its closing tag is a card with no children as well - the kernel reads to the end of the call', $read( $page( $section( '[card ground="alt"]' ) ) )['blocks'][0]['cols'][0]['components'][0]['components'] === [] );

// A second component that holds components, registered the way a feature registers one: a band that holds the components of a section and paints neither a ground nor a picture. What holds
// components is whatever says "holds", and the Reader and the Writer know no component by name; what only a card has, the radius of its surface and the offset of its ground, a container that
// has no ground has none of
$bandRender		= static fn( array &$appData, array $args ): string => '<div class="band">'. $args['value']. '</div>';
$bandSchema		= [
	'label' => [ 'en_US' => 'Band', 'de_DE' => 'Band' ], 'source' => 'content', 'loop' => false, 'holds' => 'section', 'group' => 'section', 'preview' => 'block', 'draws' => 'div .band', 'in' => [ 'section', 'hero' ],
	'attributes' => [
		'speed' => [ 'type' => 'int', 'min' => 1, 'max' => 100, 'default' => 40, 'label' => 'Speed' ],
		'wrap' => [ 'type' => 'bool', 'default' => true, 'label' => 'Wrap' ],
	],
];
$bandApp = $appData;
\Nino\Modules\Components::addComponent( $bandApp, 'band', $bandRender, $bandSchema );
ninoWarnings();
$bandRegistry	= \Nino\Modules\Builder\Document::schemas( $bandApp );
$readBand			= static fn( string $source ): array => \Nino\Modules\Builder\Reader::read( $source, $bandRegistry );
$writeBand		= static fn( array $model ): string => \Nino\Modules\Builder\Writer::write( $model, $bandRegistry );
$bandPage			= $page( $section( "[band speed=\"60\" wrap=\"0\"]\n\t\t\t\t[title /template/page-x/a/title]\n\t\t\t\t[text /template/page-x/a/text]\n\t\t\t\t[image /template/page-x/a/photo]\n\t\t\t[/band]" ) );
$bandModel		= $readBand( $bandPage );
$bandNode			= $bandModel['blocks'][0]['cols'][0]['components'][0] ?? [];

check( 'a second component that holds components is a container to the Reader by its schema alone: it holds the place of a section, and the card is no more the only one - it paints neither a ground nor a picture, so the registry says no surface of it, as it still does of the card',
	$reader::holds( $bandRegistry['components']['band'] ) === true && $bandRegistry['components']['band']['holds'] === 'section' && array_key_exists( 'surface', $bandRegistry['components']['band'] ) === false
	&& ( $bandRegistry['components']['card']['surface'] ?? '' ) === 'card' && array_keys( array_filter( $bandRegistry['components'], static fn( array $schema ): bool => $reader::holds( $schema ) === true ) ) === [ 'card', 'band' ] );
check( '...its call is read with its children, in the order written, each a node as it is in a column, and the attributes of its schema - the call\'s where it sets them: and the six sides of the spacing - but no radius and no offset of a ground, which a component that is no surface has none of',
	array_keys( $bandNode ) === array_merge( [ 'name', 'source', 'text', 'attributes' ], $reader::SPACING, [ 'components' ] ) && $bandNode['attributes'] === [ 'speed' => '60', 'wrap' => '0', 'class' => '' ]
	&& array_column( $bandNode['components'], 'name' ) === [ 'title', 'text', 'image' ] && array_column( $bandNode['components'], 'source' ) === [ '/template/page-x/a/title', '/template/page-x/a/text', '/template/page-x/a/photo' ]
	&& array_key_exists( 'content', $bandNode ) === false && array_key_exists( 'radius', $bandNode ) === false && array_key_exists( 'bgPl', $bandNode ) === false && array_key_exists( 'bgPr', $bandNode ) === false );
check( '...and it is written as the card is, byte for byte: the call on a line of its own, each child one tab deeper on a line of its own, the closing tag at the depth of its call - and what is written is read as the same model',
	$writeBand( $bandModel ) === $inWrap( $bandPage ) && $readBand( $writeBand( $bandModel ) ) === $bandModel && $writeBand( $readBand( $writeBand( $bandModel ) ) ) === $inWrap( $bandPage ) );
$bandStray = $bandModel;
$bandStray['blocks'][0]['cols'][0]['components'][0]['content'] = '<p>Stray</p>';
check( '...and a content the model carries besides the children is not written: what stands between the tags of a container is its children, whatever the node says',
	$writeBand( $bandStray ) === $inWrap( $bandPage ) );

$bandClasses = 'nino-radius-alt nino-bg-pl-1 nino-hover my-band';
$bandClassed = $page( $section( "[band class=\"". $bandClasses. "\"]\n\t\t\t\t[text /template/page-x/a/text]\n\t\t\t[/band]" ) );
$bandClassNode = $readBand( $bandClassed )['blocks'][0]['cols'][0]['components'][0] ?? [];
check( '...what a card splits off its class - the radius, the offsets of its ground - is a class like any other on a container that paints nothing: it stays in the class of the call, as it was written, and the file is written back as it was',
	$bandClassNode['attributes']['class'] === $bandClasses && array_key_exists( 'radius', $bandClassNode ) === false && array_key_exists( 'bgPl', $bandClassNode ) === false && $writeBand( $readBand( $bandClassed ) ) === $inWrap( $bandClassed ) );

$bandEmpty = $readBand( $page( $section( '[band][/band]' ) ) )['blocks'][0]['cols'][0]['components'][0] ?? [ 'components' => null ];
check( '...with nothing between its tags it holds nothing, and a call that is no container takes no children: the kernel reads a content only of the one that holds',
	$bandEmpty['components'] === [] && $writeBand( $readBand( $page( $section( '[band][/band]' ) ) ) ) === $inWrap( $page( $section( "[band]\n\t\t\t[/band]" ) ) )
	&& $readBand( $page( $section( "[title /template/page-x/a/title][/title]" ) ) )['blocks'][0]['reason']['code'] === 'component-content' );

$bandNot = [
	'a container in it'							=> "[band]\n\t\t\t\t[band][/band]\n\t\t\t[/band]",
	'the card in it'								=> "[band]\n\t\t\t\t[card][/card]\n\t\t\t[/band]",
	'it in a card'									=> "[card]\n\t\t\t\t[band][/band]\n\t\t\t[/card]",
	'a loop in it'									=> "[band]\n\t\t\t\t[stack /services]\n\t\t\t\t\t[title title]\n\t\t\t\t[/stack]\n\t\t\t[/band]",
	'markup in it'									=> "[band]\n\t\t\t\t<p>Hello</p>\n\t\t\t[/band]",
];
$bandWhy = [];

foreach( $bandNot as $label => $calls ) {

	$block = $readBand( $page( $section( $calls ) ) )['blocks'][0];
	$bandWhy[$label] = $block['kind'] === 'html' ? $block['reason']['code'] : 'read';
}

check( 'a container is no page for the Builder where it holds another, in either order, or a loop, or markup next to its calls - the reasons are the card\'s, whichever container holds them',
	$bandWhy === [ 'a container in it' => 'holder-in-holder', 'the card in it' => 'holder-in-holder', 'it in a card' => 'holder-in-holder', 'a loop in it' => 'holder-stack', 'markup in it' => 'holder-content' ] );


echo "\n";


// --- 4. What the Reader leaves as a block of html, and why -------------------

echo "A section that is not read is a foreign block, with its line and its reason\n";

$row = "<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>";
$loopRow = "\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title title]\n\t\t</div>\n\t</div>";

// The call of the scroll hint that the arrow of a section is, and the one attribute it has
$arrowName	= \Nino\Modules\Builder\Reader::ARROW;
$arrowTo		= (string) array_key_first( $registry['components'][$arrowName]['attributes'] );

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
	'a background of a column that has a side of the spacing in its class' => [ $section( "<div class=\"nino-grid-bg\">[image /template/page-x/a/one alt=\"\" class=\"nino-mt-1\"]</div>\n\t\t\t[title /template/page-x/a/title]" ), 'background-image' ],
	'a background of a section that has a side of the spacing in its class' => [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-x/a/one alt=\"\" class=\"nino-pt-2\"]</div>\n\t". $row. "\n</section>", 'background-image' ],
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
	// The picture behind the row: the first thing in it, once, in the shape of the others - and in a section that has nothing of the kind itself
	'a background of the row after a column'	=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t</div>\n</section>", 'row-background' ],
	'a second background of the row'			=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/two alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'row-background' ],
	'a background of the row of a section that has a picture of its own' => [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/two alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'row-background' ],
	'a background of the row of a section that has a colour of its own' => [ "<section id=\"a\" class=\"nino-section nino-section--alt\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'row-background' ],
	'a background of the row of a cover'		=> [ "<section id=\"a\" class=\"nino-section nino-cover\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'row-background' ],
	'a background of the row of a parallax'	=> [ "<section id=\"a\" class=\"nino-section nino-parallex\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'row-background' ],
	'a background of the row that has a class of its own' => [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg my-bg\">[image /template/page-x/a/one alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'background-class', null, 'my-bg' ],
	'a background of the row that is no picture' => [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">text</div>\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'background-image' ],
	// A loop that makes sections is a section: one row, the columns in it, and nothing else but the attributes of the loop
	'a loop of sections without a row'			=> [ "[sections /services id=\"news\"]\n\t<p>Hello</p>\n[/sections]", 'sections-no-row' ],
	'a loop of sections that is never closed'	=> [ "[sections /services id=\"news\"]\n". $loopRow, 'sections-no-row' ],
	'a loop of sections with two rows'			=> [ "[sections /services id=\"news\"]\n". $loopRow. "\n". $loopRow. "\n[/sections]", 'sections-second-row' ],
	'a loop of sections with markup next to its row' => [ "[sections /services id=\"news\"]\n". $loopRow. "\n<p>After</p>\n[/sections]", 'sections-content' ],
	'a loop of sections with markup before its row' => [ "[sections /services id=\"news\"]\n<p>Before</p>\n". $loopRow. "\n[/sections]", 'sections-no-row' ],
	'a loop of sections in a column'			=> [ $section( "[sections /services id=\"inner\"]\n\t\t\t\t". $row. "\n\t\t\t[/sections]" ), 'sections-nested' ],
	'a loop of sections in a loop of sections' => [ "[sections /services id=\"news\"]\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[sections /services id=\"inner\"]\n\t\t\t\t". $row. "\n\t\t\t[/sections]\n\t\t</div>\n\t</div>\n[/sections]", 'sections-nested' ],
	'a loop of sections without the type it loops' => [ "[sections id=\"news\"]\n". $loopRow. "\n[/sections]", 'stack-source' ],
	'a loop of sections without an id'			=> [ "[sections /services]\n". $loopRow. "\n[/sections]", 'section-id' ],
	'a loop of sections with an id that is no slug' => [ "[sections /services id=\"Big News\"]\n". $loopRow. "\n[/sections]", 'section-id' ],
	'a loop of sections with an attribute that only a tag has' => [ "[sections /services id=\"news\" data-cover-height=\"50\"]\n". $loopRow. "\n[/sections]", 'sections-attribute', null, 'data-cover-height' ],
	'a loop of sections with an attribute of the grid' => [ "[sections /services id=\"news\" cols=\"100 50\"]\n". $loopRow. "\n[/sections]", 'sections-attribute', null, 'cols' ],
	'a loop of sections with a class that cannot be kept' => [ "[sections /services id=\"news\" class=\"a&b\"]\n". $loopRow. "\n[/sections]", 'class-token', null, 'a&b' ],
	'a loop in a column of a loop of sections' => [ "[sections /services id=\"news\"]\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]\n\t\t</div>\n\t</div>\n[/sections]", 'stack-in-stack' ],
	'a component that holds none, with a loop of its own, in a column of a loop of sections' => [ "[sections /services id=\"news\"]\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title title]\n\t\t\t[stack /services]\n\t\t\t\t[title title]\n\t\t\t[/stack]\n\t\t</div>\n\t</div>\n[/sections]", 'stack-in-stack' ],
	// The animation of a section is its row's: the delay and the duration are attributes of the row's tag, the tag of the section has none of them, and the row no other
	'the delay of an animation on the tag of a section'		=> [ "<section id=\"a\" class=\"nino-section\" data-vpa-delay=\"200ms\">\n\t". $row. "\n</section>", 'section-attribute', null, 'data-vpa-delay' ],
	'the duration of an animation on the tag of a section'	=> [ "<section id=\"a\" class=\"nino-section\" data-vpa-duration=\"2s\">\n\t". $row. "\n</section>", 'section-attribute', null, 'data-vpa-duration' ],
	'a third attribute on the row of a section'					=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\" data-vpa-delay=\"200ms\" data-vpa-duration=\"2s\" data-aos=\"fade\">\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'row-attribute', null, 'data-aos' ],
	'a delay on the row that is no time'							=> [ "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row nino-vpa\" data-vpa-delay=\"soon\">\n\t\t<div class=\"nino-grid-100\">[title /template/page-x/a/title]</div>\n\t</div>\n</section>", 'attribute-value', null, 'data-vpa-delay' ],
	// The arrow of a section is the call of the scroll hint as it is, on the line after its row - nothing else there is the arrow
	'the arrow of a section with a target'							=> [ "<section id=\"a\" class=\"nino-section\">\n\t". $row. "\n\t[". $arrowName. ' '. $arrowTo. "=\"b\"]\n</section>", 'section-content' ],
	'a second arrow after the row'										=> [ "<section id=\"a\" class=\"nino-section\">\n\t". $row. "\n\t[". $arrowName. "]\n\t[". $arrowName. "]\n</section>", 'section-content' ],
	'markup after the arrow'											=> [ "<section id=\"a\" class=\"nino-section\">\n\t". $row. "\n\t[". $arrowName. "]\n\t<p>After</p>\n</section>", 'section-content' ],
	'the arrow before the row'										=> [ "<section id=\"a\" class=\"nino-section\">\n\t[". $arrowName. "]\n\t". $row. "\n</section>", 'no-row' ],
	'the arrow of a loop of sections with a target'			=> [ "[sections /services id=\"news\"]\n". $loopRow. "\n\t[". $arrowName. ' '. $arrowTo. "=\"b\"]\n[/sections]", 'sections-content' ],
	'a second arrow in a loop of sections'						=> [ "[sections /services id=\"news\"]\n". $loopRow. "\n\t[". $arrowName. "]\n\t[". $arrowName. "]\n[/sections]", 'sections-content' ],
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
// Nino.css starts its first tier at 640px: a column that is not 100 everywhere is full width below it, through the base class that comes before its tiers
$colWritten	= static fn( array $block ): string => $write( [ 'blocks' => [ $block ] ] );
$colTag		= static fn( string $text ): string => (string) preg_replace( '/^.*?<div class="(nino-grid-(?:100|s-)[^"]*)".*$/s', '$1', $text );
$tiered		= $column( 'nino-grid-s-50 nino-grid-m-50 nino-grid-l-33' );
$allHundred	= $column( 'nino-grid-s-100 nino-grid-m-100 nino-grid-l-100' );
$writer		= \Nino\Modules\Builder\Writer::class;

check( 'a column that is not 100 in every viewport is written with the base class first and its three tiers after it, so that it is full width until the first tier applies',
	$colTag( $colWritten( $tiered ) ) === 'nino-grid-100 nino-grid-s-50 nino-grid-m-50 nino-grid-l-33'
	&& array_slice( $writer::colClasses( [ 'width' => [ 's' => 100, 'm' => 100, 'l' => 66 ] ] ), 0, 4 ) === [ 'nino-grid-100', 'nino-grid-s-100', 'nino-grid-m-100', 'nino-grid-l-66' ]
	&& array_slice( $writer::colClasses( [ 'width' => [ 's' => 33, 'm' => 33, 'l' => 33 ], 'hidden' => [ 'm' => true ] ] ), 0, 5 ) === [ 'nino-grid-100', 'nino-grid-s-33', 'nino-grid-m-33', 'nino-grid-l-33', 'nino-hide-m' ] );
check( '...a column that is 100 in every viewport stays the base class alone', $colTag( $colWritten( $allHundred ) ) === 'nino-grid-100'
	&& $writer::colClasses( [ 'width' => [ 's' => 100, 'm' => 100, 'l' => 100 ] ] ) === [ 'nino-grid-100' ] && $writer::colClasses( [] ) === [ 'nino-grid-100' ] );
check( 'the base class with tiers beside it is the column of the tiers: the tiers win, whatever the order, and the base class is not a custom class of the column',
	( static function() use ( $column ): bool {
		foreach( [ 'nino-grid-100 nino-grid-s-50 nino-grid-m-33 nino-grid-l-25', 'nino-grid-l-25 nino-grid-m-33 nino-grid-100 nino-grid-s-50' ] as $class ) {
			$col = $column( $class )['cols'][0];

			if( $col['width'] !== [ 's' => 50, 'm' => 33, 'l' => 25 ] || $col['custom'] !== '' )
				return false;
		}

		return $column( 'nino-grid-100 nino-grid-s-33 nino-grid-l-66' )['cols'][0]['width'] === [ 's' => 33, 'm' => 33, 'l' => 66 ];
	} )() );
check( '...the base class alone is the column that is 100 in every viewport', $column( 'nino-grid-100' )['cols'][0]['width'] === [ 's' => 100, 'm' => 100, 'l' => 100 ] && $column( 'nino-grid-100' )['cols'][0]['custom'] === '' );
check( '...a column with tiers and without the base class is read by its tiers, and is written with the base class - no refusal',
	( static function() use ( $column, $colWritten, $colTag ): bool {
		$bare = $column( 'nino-grid-s-50 nino-grid-m-50 nino-grid-l-33' );

		return $bare['kind'] === 'section' && $bare['cols'][0]['width'] === [ 's' => 50, 'm' => 50, 'l' => 33 ] && $bare['cols'][0]['custom'] === ''
			&& $bare === $column( 'nino-grid-100 nino-grid-s-50 nino-grid-m-50 nino-grid-l-33' ) && $colTag( $colWritten( $bare ) ) === 'nino-grid-100 nino-grid-s-50 nino-grid-m-50 nino-grid-l-33';
	} )() );
check( '...and the round trip holds for each form: what is written is read as the same column, and written again byte for byte',
	( static function() use ( $column, $colWritten, $read, $page, $section ): bool {
		foreach( [ 'nino-grid-100', 'nino-grid-100 nino-grid-s-100 nino-grid-m-100 nino-grid-l-66', 'nino-grid-s-50 nino-grid-m-50 nino-grid-l-33', 'nino-grid-100 nino-grid-m-50 nino-hide-s' ] as $class ) {
			$block	= $column( $class );
			$text		= $colWritten( $block );
			$again	= $read( $text )['blocks'][0];

			if( $again !== $block || $colWritten( $again ) !== $text )
				return false;
		}

		return true;
	} )() );
check( 'the example pages of the fixtures carry the base class before the tiers of every column that has them, and the columns that have none are the base class alone',
	( static function(): bool {
		foreach( [ 'page-home', 'page-home-wrap', 'page-home-concept', 'page-home-card' ] as $name ) {
			$text = (string) file_get_contents( __DIR__. '/fixtures/'. $name. '.tpl' );

			if( preg_match_all( '/nino-grid-s-\d+/', $text ) === 0 || preg_match_all( '/nino-grid-s-\d+/', $text ) !== preg_match_all( '/class="nino-grid-100 nino-grid-s-\d+ nino-grid-m-\d+ nino-grid-l-\d+[ "]/', $text ) )
				return false;
		}

		return true;
	} )() );

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
	'row' => 'narrow', 'rowPlace' => 'right', 'rowAlign' => 'bottom', 'rowPl' => '1', 'rowPr' => '2', 'rowPt' => '4', 'rowPb' => '5', 'color' => 'tint', 'border' => '2', 'borderPrimary' => true, 'cover' => 60, 'parallax' => true, 'dim' => true, 'padded' => true, 'contentPos' => 'bottom', 'whitespace' => 'less',
	'mt' => '1', 'mb' => '0', 'pt' => '3', 'pb' => '6', 'pl' => '2', 'pr' => '4', 'text' => 'right', 'arrow' => true, 'vpa' => 'zoom-soft', 'vpaSpeed' => 'slow', 'vpaMode' => 'repeat', 'vpaDelay' => '200ms', 'vpaDuration' => '1.5s',
	'custom' => 'my-section other', 'rowCustom' => 'my-row',
] + $blank['settings'];
$styled['cols'][0] = [ 'width' => [ 's' => 100, 'm' => 50, 'l' => 33 ], 'hidden' => [ 's' => true ], 'color' => 'dark', 'dim' => true, 'background' => [ 'slot' => '/template/page-x/a/photo', 'focus' => 8 ],
	'text' => 'center', 'stackAlign' => 'end', 'stackGap' => '4', 'vpa' => '', 'custom' => 'my-col' ] + $blank['cols'][0];
$styled['background'] = [ 'slot' => '/template/page-x/a/background', 'focus' => null ];
$once = $write( [ 'blocks' => [ $styled ] ] );

check( 'a section\'s settings are its classes, in one order: the colour, the border and its brand colour, the cover and its scrim, the parallax, the room around a cover, the place of the content, the whitespace, the spacing, the text, the custom ones - and its attribute the height of the cover; its animation is none of them',
	str_contains( $once, '<section id="a" class="nino-section nino-section--tint nino-section--border-2 nino-section--border-primary nino-cover nino-cover--dim nino-parallex nino-cover--padded nino-cover-bottom nino-whitespace--less nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-pl-2 nino-pr-4 nino-text-right my-section other" data-cover-height="60">' ) === true );
check( '...and the row\'s and the column\'s: the width and the place of the row, the alignment of its columns, its padding on each side, the animation of the section and the custom classes - the delay and the duration of the animation the attributes of the row\'s tag; of a column the widths, the hidden ones, the ground and the dimming, the alignment and the gap of the components, the text, the animation, the custom ones',
	str_contains( $once, '<div class="nino-grid-row nino-grid-row--narrow nino-grid-row--right nino-grid-bottom nino-pl-1 nino-pr-2 nino-pt-4 nino-pb-5 nino-vpa nino-vpa--zoom-soft nino-vpa--speed-slow nino-vpa--repeat my-row" data-vpa-delay="200ms" data-vpa-duration="1.5s">' ) === true
	&& str_contains( $once, '<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-50 nino-grid-l-33 nino-hide-s nino-grid--dark nino-grid--dim nino-stack-end nino-stack-gap-4 nino-text-center nino-vpa my-col">' ) === true );
check( '...the animation of a section stands on its row and on nothing else of it: no class and no attribute of it on the tag of the section, whatever the section is - written, read back as it was set, and written again byte for byte',
	preg_match( '/<section [^>]*(?:vpa)/', $once ) === 0 && $write( $read( $once ) ) === $once && substr_count( $once, 'data-vpa-delay' ) === 1 && substr_count( $once, 'data-vpa-duration' ) === 1 );
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
check( '...a scrim over a section that is neither a cover nor a parallax and has no picture of its own darkens nothing: no class is written for it', str_contains( $coverWritten( [ 'dim' => true ] ), '<section id="a" class="nino-section">' ) === true );
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
		'<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-50 nino-grid-l-33 nino-hide-s nino-grid--alt nino-stack-center nino-stack-gap-3 nino-text-left my-col">' ) === true );

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

// A section as a file has it: its classes, and the row's - the picture behind the section before the row, the one behind the row first in it
$fileOf				= static fn( string $sectionClass, string $rowClass = 'nino-grid-row', bool $behind = false, bool $inRow = false, string $rowAttributes = '' ): string => $page( '<section id="a" class="'. $sectionClass. "\">\n"
	. ( $behind === true ? "\t<div class=\"nino-section-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n" : '' ). "\t<div class=\"". $rowClass. "\"". $rowAttributes. ">\n"
	. ( $inRow === true ? "\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n" : '' ). "\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-x/a/title]\n\t\t</div>\n\t</div>\n</section>" );
$fileRead		= static fn( string $sectionClass, string $rowClass = 'nino-grid-row', bool $behind = false, bool $inRow = false, string $rowAttributes = '' ): array => $read( $fileOf( $sectionClass, $rowClass, $behind, $inRow, $rowAttributes ) )['blocks'][0];
$fileSame		= static fn( string $sectionClass, string $rowClass = 'nino-grid-row', bool $behind = false, bool $inRow = false, string $rowAttributes = '' ): bool => $write( $read( $fileOf( $sectionClass, $rowClass, $behind, $inRow, $rowAttributes ) ) ) === $inWrap( $fileOf( $sectionClass, $rowClass, $behind, $inRow, $rowAttributes ) );

// The row of a section: its width and its place are words of Nino.css and its padding a step of the space scale on each side - a class of the row apiece, read in any order and written in one
$rowWrong = [];

foreach( [
	[ 'row', \Nino\Modules\Builder\Reader::ROW, 'nino-grid-row--%s' ],
	[ 'rowPlace', \Nino\Modules\Builder\Reader::ROW_PLACE, 'nino-grid-row--%s' ],
	[ 'rowGap', \Nino\Modules\Builder\Reader::STEPS, 'nino-grid-gap-%s' ],
	[ 'rowPl', \Nino\Modules\Builder\Reader::STEPS, 'nino-pl-%s' ],
	[ 'rowPr', \Nino\Modules\Builder\Reader::STEPS, 'nino-pr-%s' ],
	[ 'rowPt', \Nino\Modules\Builder\Reader::STEPS, 'nino-pt-%s' ],
	[ 'rowPb', \Nino\Modules\Builder\Reader::STEPS, 'nino-pb-%s' ],
] as [ $key, $words, $class ] )
	foreach( $words as $word ) {

		$classes	= 'nino-grid-row '. sprintf( $class, $word );
		$block		= $fileRead( 'nino-section', $classes );
		$made			= $blank;
		$made['settings'][$key] = $word;

		// Read from the class, written back as it was, and written from the setting and read back as set
		if( $block['settings'][$key] !== $word || $block['settings']['rowCustom'] !== '' || $fileSame( 'nino-section', $classes ) === false
			|| $write( [ 'blocks' => [ $made ] ] ) !== $inWrap( $fileOf( 'nino-section', $classes ) ) || $read( $write( [ 'blocks' => [ $made ] ] ) )['blocks'][0] !== $made )
			$rowWrong[] = $key. ' '. $word;
	}

check( 'the row of a section has a width - narrow, wide or full -, a place - left or right, the middle being none -, a gap between its columns and a padding on each side - a step of the space scale from 0 to 6 -, each read from its class and written back byte for byte, and read back as set: '
	. ( count( \Nino\Modules\Builder\Reader::ROW ) + count( \Nino\Modules\Builder\Reader::ROW_PLACE ) + 5 * count( \Nino\Modules\Builder\Reader::STEPS ) ). ' classes'. ( $rowWrong === [] ? '' : ' - '. implode( ', ', $rowWrong ) ),
	$rowWrong === [] && \Nino\Modules\Builder\Reader::ROW === [ 'narrow', 'wide', 'full' ] && \Nino\Modules\Builder\Reader::ROW_PLACE === [ 'left', 'right' ]
	&& \Nino\Modules\Builder\Reader::ROW_SPACING === [ 'pl' => 'rowPl', 'pr' => 'rowPr', 'pt' => 'rowPt', 'pb' => 'rowPb' ] && \Nino\Modules\Builder\Reader::STEPS === [ '0', '1', '2', '3', '4', '5', '6' ] );

$rowAll			= [ 'row' => 'wide', 'rowPlace' => 'right', 'rowAlign' => 'bottom', 'rowGap' => '3', 'rowPl' => '1', 'rowPr' => '2', 'rowPt' => '4', 'rowPb' => '5', 'rowRadius' => '', 'rowCustom' => 'my-row', 'rowBgPl' => '', 'rowBgPr' => '' ];
$rowOrdered	= 'nino-grid-row nino-grid-row--wide nino-grid-row--right nino-grid-bottom nino-grid-gap-3 nino-pl-1 nino-pr-2 nino-pt-4 nino-pb-5 my-row';
$rowMade		= $blank;
$rowMade['settings'] = $rowAll + $blank['settings'];

check( 'the classes of the row stand in one order: the width, the place, the alignment of its columns, the gap between them, the padding on the left, on the right, above and below, then the custom ones - written from the settings, and read back as they were set',
	$write( [ 'blocks' => [ $rowMade ] ] ) === $inWrap( $fileOf( 'nino-section', $rowOrdered ) ) && $read( $write( [ 'blocks' => [ $rowMade ] ] ) )['blocks'][0] === $rowMade
	&& array_slice( array_keys( \Nino\Modules\Builder\Reader::sectionDefaults() ), 0, 10 ) === [ 'row', 'rowPlace', 'rowAlign', 'rowGap', 'rowPl', 'rowPr', 'rowPt', 'rowPb', 'rowRadius', 'rowCustom' ]
	&& array_slice( array_keys( \Nino\Modules\Builder\Reader::sectionDefaults() ), 10, 2 ) === array_values( \Nino\Modules\Builder\Reader::ROW_BG_SPACING ) );

$rowShuffled = 'nino-grid-row nino-pb-5 my-row nino-grid-bottom nino-pt-4 nino-grid-row--right nino-grid-gap-3 nino-pr-2 nino-grid-row--wide nino-pl-1';
check( '...whatever order the file has them in: it is the same row, written in that one order, and what is written reads as it was', $fileRead( 'nino-section', $rowShuffled ) === $read( $write( [ 'blocks' => [ $rowMade ] ] ) )['blocks'][0]
	&& $write( $read( $fileOf( 'nino-section', $rowShuffled ) ) ) === $inWrap( $fileOf( 'nino-section', $rowOrdered ) ) );

// With the background inside the colour and the scrim are the row's, and stand after its place - before the alignment and the padding, which win over what the background gives the row
$rowInner		= 'nino-grid-row nino-grid-row--narrow nino-grid-row--left nino-grid-row--tint nino-grid-row--dim nino-grid-middle nino-pl-0 nino-pr-0 nino-pt-4 nino-pb-4';
$rowInnerSet	= $fileRead( 'nino-section', $rowInner, false, true )['settings'];
check( '...with the background inside the colour and the scrim of the row stand after its place and before its alignment and its padding, and the row is read and written as it is',
	[ $rowInnerSet['inner'], $rowInnerSet['color'], $rowInnerSet['dim'], $rowInnerSet['row'], $rowInnerSet['rowPlace'], $rowInnerSet['rowAlign'], $rowInnerSet['rowPl'], $rowInnerSet['rowPr'], $rowInnerSet['rowPt'], $rowInnerSet['rowPb'], $rowInnerSet['rowCustom'] ] === [ true, 'tint', true, 'narrow', 'left', 'middle', '0', '0', '4', '4', '' ]
	&& $fileSame( 'nino-section', $rowInner, false, true ) === true );

// The first class of a kind is the setting, a second one is a class of its own, kept - as it is on a section; so is a step the scale has not and a class of another utility
$rowSecond = [
	'two widths'												=> [ 'nino-grid-row nino-grid-row--narrow nino-grid-row--wide', [ 'row' => 'narrow', 'rowCustom' => 'nino-grid-row--wide' ] ],
	'two places'												=> [ 'nino-grid-row nino-grid-row--left nino-grid-row--right', [ 'rowPlace' => 'left', 'rowCustom' => 'nino-grid-row--right' ] ],
	'two gaps between the columns'			=> [ 'nino-grid-row nino-grid-gap-1 nino-grid-gap-2', [ 'rowGap' => '1', 'rowCustom' => 'nino-grid-gap-2' ] ],
	'a gap the scale has not'						=> [ 'nino-grid-row nino-grid-gap-7', [ 'rowGap' => '', 'rowCustom' => 'nino-grid-gap-7' ] ],
	'the gap of a stack, which is a column\'s'	=> [ 'nino-grid-row nino-stack-gap-2', [ 'rowGap' => '', 'rowCustom' => 'nino-stack-gap-2' ] ],
	'two steps of one side'							=> [ 'nino-grid-row nino-pl-1 nino-pl-2', [ 'rowPl' => '1', 'rowCustom' => 'nino-pl-2' ] ],
	'a step the scale has not'					=> [ 'nino-grid-row nino-pt-7', [ 'rowPt' => '', 'rowCustom' => 'nino-pt-7' ] ],
	'a step that is a word'							=> [ 'nino-grid-row nino-pb-x', [ 'rowPb' => '', 'rowCustom' => 'nino-pb-x' ] ],
	'the margin, which the row has no setting for'	=> [ 'nino-grid-row nino-ml-1 nino-mr-2', [ 'rowPl' => '', 'rowPr' => '', 'rowCustom' => 'nino-ml-1 nino-mr-2' ] ],
	'a place of the alignment of the columns'		=> [ 'nino-grid-row nino-grid-row--middle', [ 'rowPlace' => '', 'rowAlign' => '', 'rowCustom' => 'nino-grid-row--middle' ] ],
];
$rowSecondWrong = [];

foreach( $rowSecond as $label => [ $classes, $expected ] ) {

	$settings = $fileRead( 'nino-section', $classes )['settings'];

	if( array_intersect_key( $settings, $expected ) !== $expected || $fileSame( 'nino-section', $classes ) === false )
		$rowSecondWrong[] = $label;
}

check( '...a second of a kind - two widths, two places, two gaps, two steps of a side -, a step the scale has not, the gap of a stack and the margins, which the row has no setting for, are custom classes of the row, kept and written back as they were: '. count( $rowSecond ). ' cases'
	. ( $rowSecondWrong === [] ? '' : ' - '. implode( ', ', $rowSecondWrong ) ), $rowSecondWrong === [] );

$padded = $fileRead( 'nino-section nino-pt-3 nino-pb-6', 'nino-grid-row nino-pt-1 nino-pb-2' )['settings'];
check( '...the padding of the section and the padding of its row are two settings: the classes of the section are the section\'s, those of the row the row\'s, whatever they say', [ $padded['pt'], $padded['pb'], $padded['rowPt'], $padded['rowPb'], $padded['custom'], $padded['rowCustom'] ] === [ '3', '6', '1', '2', '', '' ]
	&& $fileSame( 'nino-section nino-pt-3 nino-pb-6', 'nino-grid-row nino-pt-1 nino-pb-2' ) === true );

$rowLeft = $blank;
unset( $rowLeft['settings']['rowPlace'], $rowLeft['settings']['rowGap'], $rowLeft['settings']['rowPl'], $rowLeft['settings']['rowPr'], $rowLeft['settings']['rowPt'], $rowLeft['settings']['rowPb'], $rowLeft['settings']['rowBgPl'], $rowLeft['settings']['rowBgPr'], $rowLeft['settings']['arrow'] );
check( 'a row the model says nothing of has no place, no gap between its columns, no padding, no offset of its picture and no arrow: the Writer takes the defaults, which are none, and writes the row as it always was', str_contains( $write( [ 'blocks' => [ $rowLeft ] ] ), "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n" ) === true
	&& str_contains( $write( [ 'blocks' => [ $rowLeft ] ] ), $reader::ARROW ) === false
	&& array_diff( [ $blank['settings']['rowPlace'], $blank['settings']['rowGap'], $blank['settings']['rowPl'], $blank['settings']['rowPr'], $blank['settings']['rowPt'], $blank['settings']['rowPb'], $blank['settings']['rowBgPl'], $blank['settings']['rowBgPr'] ], [ '' ] ) === [] && $blank['settings']['arrow'] === false );

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
check( '...and on a section that is neither a cover nor a parallax nino-cover-top is no place of the model: a custom class, kept', $coverOf( 'nino-section nino-cover-top' )['contentPos'] === '' && $coverOf( 'nino-section nino-cover-top' )['custom'] === 'nino-cover-top'
	&& $coverOf( 'nino-section nino-parallex nino-cover-top' )['contentPos'] === 'top' );

// The room around a padded cover, the whitespace of a section, and the colour, the picture and the scrim of a background - on the section or, with inner, on its row: classes
// the Reader reads in any order and the Writer writes in one
$picture			= [ 'slot' => '/template/page-x/a/background', 'focus' => null ];
$pictured			= static fn( array $set, ?array $background = [ 'slot' => '/template/page-x/a/background', 'focus' => null ] ): array => array_replace( $blank, [ 'settings' => array_replace( $blank['settings'], $set ), 'background' => $background ] );
$pictureText	= static fn( array $set, ?array $background = [ 'slot' => '/template/page-x/a/background', 'focus' => null ] ): string => $write( [ 'blocks' => [ $pictured( $set, $background ) ] ] );
$pictureBack	= static fn( array $set, ?array $background = [ 'slot' => '/template/page-x/a/background', 'focus' => null ] ): bool => $read( $pictureText( $set, $background ) )['blocks'][0] === $pictured( $set, $background );

check( 'a cover or a parallax that keeps the room of the page around it is nino-cover--padded - after the scrim, before the place of the content - and reads back as it was set',
	str_contains( $coverWritten( [ 'cover' => 90, 'padded' => true ] ), '<section id="a" class="nino-section nino-cover nino-cover--padded" data-cover-height="90">' ) === true
	&& str_contains( $coverWritten( [ 'parallax' => true, 'dim' => true, 'padded' => true, 'contentPos' => 'top' ] ), '<section id="a" class="nino-section nino-parallex nino-parallex--dim nino-cover--padded nino-cover-top">' ) === true
	&& $coverBack( [ 'cover' => 90, 'padded' => true ] ) === true && $coverBack( [ 'parallax' => true, 'padded' => true ] ) === true
	&& $coverBack( [ 'cover' => 60, 'parallax' => true, 'dim' => true, 'padded' => true, 'contentPos' => 'bottom' ] ) === true && \Nino\Modules\Builder\Reader::sectionDefaults()['padded'] === false );
check( '...on a section that is neither it is no class: none is written, and the one a file has is a custom class, kept - as the place of the content is',
	str_contains( $coverWritten( [ 'padded' => true ] ), 'nino-cover--padded' ) === false && $coverOf( 'nino-section nino-cover--padded' )['padded'] === false && $coverOf( 'nino-section nino-cover--padded' )['custom'] === 'nino-cover--padded'
	&& $coverOf( 'nino-section nino-cover nino-cover--padded' )['padded'] === true && $coverOf( 'nino-section nino-cover nino-cover--padded' )['custom'] === ''
	&& $coverOf( 'nino-section nino-parallex nino-cover--padded' )['padded'] === true && $coverOf( 'nino-section nino-parallex nino-cover--padded' )['custom'] === '' );

$whitespaceWrong = [];

foreach( \Nino\Modules\Builder\Reader::WHITESPACE as $word ) {

	$source = $coverSource( 'nino-section nino-whitespace--'. $word. ' nino-mt-1' );

	if( $coverOf( 'nino-section nino-mt-1 nino-whitespace--'. $word )['whitespace'] !== $word || $coverOf( 'nino-section nino-whitespace--'. $word )['custom'] !== '' || $write( $read( $source ) ) !== $inWrap( $source )
		|| str_contains( $coverWritten( [ 'whitespace' => $word, 'cover' => 90, 'contentPos' => 'top', 'mt' => '1' ] ), 'nino-cover-top nino-whitespace--'. $word. ' nino-mt-1' ) === false || $coverBack( [ 'whitespace' => $word ] ) === false )
		$whitespaceWrong[] = $word;
}

check( 'the whitespace of a section is one of four words - least, less, more, most -, read from nino-whitespace--<x> wherever it stands among the classes and written after the place of the content and before the spacing: all '
	. count( \Nino\Modules\Builder\Reader::WHITESPACE ). ' of them, byte for byte'. ( $whitespaceWrong === [] ? '' : ' - '. implode( ', ', $whitespaceWrong ) ),
	$whitespaceWrong === [] && \Nino\Modules\Builder\Reader::WHITESPACE === [ 'least', 'less', 'more', 'most' ] && \Nino\Modules\Builder\Reader::sectionDefaults()['whitespace'] === '' && str_contains( $coverWritten( [] ), 'nino-whitespace' ) === false );
check( '...a word that is none of the four, and a second one, are custom classes, kept',
	$coverOf( 'nino-section nino-whitespace--huge' )['whitespace'] === '' && $coverOf( 'nino-section nino-whitespace--huge' )['custom'] === 'nino-whitespace--huge'
	&& $coverOf( 'nino-section nino-whitespace--less nino-whitespace--more' )['whitespace'] === 'less' && $coverOf( 'nino-section nino-whitespace--less nino-whitespace--more' )['custom'] === 'nino-whitespace--more' );

check( 'a scrim over a section that is neither a cover nor a parallax is nino-section--dim, written where the section has a picture of its own and in no other case, and reads back as it was set',
	str_contains( $pictureText( [ 'dim' => true ] ), '<section id="a" class="nino-section nino-section--dim">' ) === true && $pictureBack( [ 'dim' => true ] ) === true
	&& str_contains( $pictureText( [ 'dim' => true, 'color' => 'black', 'border' => '1' ] ), 'class="nino-section nino-section--black nino-section--border-1 nino-section--dim"' ) === true && $pictureBack( [ 'dim' => true, 'color' => 'black', 'border' => '1' ] ) === true
	&& str_contains( $pictureText( [ 'dim' => false ] ), '--dim' ) === false && str_contains( $coverWritten( [ 'dim' => true ] ), '--dim' ) === false );
check( '...the class is the scrim of a section with a picture of its own and no more: on a section that has none, on a cover and on a parallax, which have their own, it is a custom class, kept',
	$fileRead( 'nino-section nino-section--dim', 'nino-grid-row', true )['settings']['dim'] === true && $fileRead( 'nino-section nino-section--dim', 'nino-grid-row', true )['settings']['custom'] === ''
	&& $fileRead( 'nino-section nino-section--dim' )['settings']['dim'] === false && $fileRead( 'nino-section nino-section--dim' )['settings']['custom'] === 'nino-section--dim'
	&& $fileRead( 'nino-section nino-cover nino-section--dim', 'nino-grid-row', true )['settings']['dim'] === false && $fileRead( 'nino-section nino-cover nino-section--dim', 'nino-grid-row', true )['settings']['custom'] === 'nino-section--dim'
	&& $fileRead( 'nino-section nino-parallex nino-section--dim', 'nino-grid-row', true )['settings']['dim'] === false && $fileRead( 'nino-section nino-parallex nino-section--dim', 'nino-grid-row', true )['settings']['custom'] === 'nino-section--dim'
	&& $fileSame( 'nino-section nino-section--dim', 'nino-grid-row', true ) === true && $fileSame( 'nino-section nino-section--dim' ) === true );

$innerWrong = [];

foreach( \Nino\Modules\Builder\Reader::COLOR as $color ) {

	$set		= [ 'inner' => true, 'color' => $color, 'row' => 'wide', 'rowAlign' => 'middle' ];
	$source	= $fileOf( 'nino-section', 'nino-grid-row nino-grid-row--wide nino-grid-row--'. $color. ' nino-grid-middle' );

	if( str_contains( $coverWritten( $set ), '<section id="a" class="nino-section">' ) === false || str_contains( $coverWritten( $set ), '<div class="nino-grid-row nino-grid-row--wide nino-grid-row--'. $color. ' nino-grid-middle">' ) === false
		|| $coverBack( $set ) === false || $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--wide nino-grid-row--'. $color. ' nino-grid-middle' )['settings']['color'] !== $color || $write( $read( $source ) ) !== $inWrap( $source ) )
		$innerWrong[] = $color;
}

check( 'the colour of a section whose background is inside is the row\'s: nino-grid-row--<color> after the width and before the alignment, none on the section - all '. count( \Nino\Modules\Builder\Reader::COLOR )
	. ' of the section\'s colours, written and read back byte for byte'. ( $innerWrong === [] ? '' : ' - '. implode( ', ', $innerWrong ) ), $innerWrong === [] && \Nino\Modules\Builder\Reader::sectionDefaults()['inner'] === false );
check( '...its picture is the first thing in the row, two tabs in, in the block of the one behind a column - its focus a class of the block - and the section has no block of its own; the scrim over it is nino-grid-row--dim, after the colour',
	str_contains( $pictureText( [ 'inner' => true ] ), "<section id=\"a\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-row-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n\t\t<div class=\"nino-grid-100\">" ) === true
	&& str_contains( $pictureText( [ 'inner' => true ], [ 'slot' => '/template/page-x/a/background', 'focus' => 7 ] ), '<div class="nino-grid-row-bg nino-img-focus--7">' ) === true
	&& str_contains( $pictureText( [ 'inner' => true, 'color' => 'dark', 'dim' => true ] ), '<div class="nino-grid-row nino-grid-row--dark nino-grid-row--dim">' ) === true && str_contains( $pictureText( [ 'inner' => true, 'color' => 'dark', 'dim' => true ] ), 'nino-section-bg' ) === false
	&& str_contains( $pictureText( [ 'inner' => true, 'dim' => true ] ), 'nino-section--dim' ) === false
	&& $pictureBack( [ 'inner' => true ] ) === true && $pictureBack( [ 'inner' => true, 'color' => 'dark', 'dim' => true ] ) === true && $pictureBack( [ 'inner' => true, 'color' => 'alt' ], [ 'slot' => '/template/page-x/a/background', 'focus' => 7 ] ) === true );
check( '...a scrim with no picture behind the row is not written, and with neither a colour nor a picture the row says nothing of it: inner reads as off - the panel keeps the switch off then',
	str_contains( $coverWritten( [ 'inner' => true, 'color' => 'dark', 'dim' => true ] ), 'nino-grid-row--dim' ) === false && $read( $coverWritten( [ 'inner' => true ] ) )['blocks'][0]['settings']['inner'] === false
	&& $read( $coverWritten( [ 'inner' => true, 'color' => 'dark', 'dim' => true ] ) )['blocks'][0]['settings']['inner'] === true && $read( $coverWritten( [ 'inner' => true, 'color' => 'dark', 'dim' => true ] ) )['blocks'][0]['settings']['dim'] === false );
check( '...with a cover or a parallax the background is the section\'s own and inner is none: the colour stays on the section, the picture is in its block, and the switch reads as off',
	str_contains( $pictureText( [ 'inner' => true, 'cover' => 90, 'color' => 'black' ] ), '<section id="a" class="nino-section nino-section--black nino-cover" data-cover-height="90">' ) === true
	&& str_contains( $pictureText( [ 'inner' => true, 'parallax' => true ] ), "\t<div class=\"nino-section-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">" ) === true
	&& str_contains( $pictureText( [ 'inner' => true, 'parallax' => true ] ), 'nino-grid-row-bg' ) === false
	&& $read( $pictureText( [ 'inner' => true, 'cover' => 90 ] ) )['blocks'][0]['settings']['inner'] === false && $read( $pictureText( [ 'inner' => true, 'parallax' => true ] ) )['blocks'][0]['settings']['inner'] === false );

$innerRead = $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--tint', false, true );
check( 'a row with a ground of its own and a picture of its own, in a section that has neither, is a section whose background is inside: the colour and the picture are the section\'s keys all the same, the switch is on, the row has no custom class',
	$innerRead['settings']['inner'] === true && $innerRead['settings']['color'] === 'tint' && $innerRead['settings']['rowCustom'] === '' && $innerRead['background'] === [ 'slot' => '/template/page-x/a/background', 'focus' => null ]
	&& $fileSame( 'nino-section', 'nino-grid-row nino-grid-row--tint', false, true ) === true && $fileSame( 'nino-section', 'nino-grid-row nino-grid-row--tint nino-grid-row--dim', false, true ) === true
	&& $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--tint nino-grid-row--dim', false, true )['settings']['dim'] === true );
check( '...a picture alone, and a ground alone, are as much of it as the row says: the switch is on for each',
	$fileRead( 'nino-section', 'nino-grid-row', false, true )['settings']['inner'] === true && $fileRead( 'nino-section', 'nino-grid-row', false, true )['settings']['color'] === ''
	&& $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--black' )['settings']['inner'] === true && $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--black' )['background'] === null
	&& $fileSame( 'nino-section', 'nino-grid-row', false, true ) === true && $fileSame( 'nino-section', 'nino-grid-row nino-grid-row--black' ) === true );
check( '...where the section has a colour, a picture, a cover or a parallax of its own the ground of the row is a custom class of the row, kept, and nothing is inside: the section is what it says first',
	( static function() use ( $fileRead, $fileSame ): bool {
		foreach( [ [ 'nino-section nino-section--alt', false ], [ 'nino-section', true ], [ 'nino-section nino-cover', false ], [ 'nino-section nino-parallex', false ] ] as [ $classes, $behind ] ) {

			$block = $fileRead( $classes, 'nino-grid-row nino-grid-row--tint', $behind );

			if( $block['settings']['inner'] !== false || $block['settings']['rowCustom'] !== 'nino-grid-row--tint' || $block['settings']['color'] === 'tint' || ( $classes !== 'nino-section nino-cover' && $fileSame( $classes, 'nino-grid-row nino-grid-row--tint', $behind ) === false ) )
				return false;
		}

		return true;
	} )() );
check( '...the ground that sets a column off, a second ground and a scrim over no picture are custom classes of the row as well, where they stand',
	$fileRead( 'nino-section', 'nino-grid-row nino-grid-row--counter' )['settings']['rowCustom'] === 'nino-grid-row--counter' && $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--counter' )['settings']['inner'] === false
	&& $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--alt nino-grid-row--dark' )['settings']['color'] === 'alt' && $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--alt nino-grid-row--dark' )['settings']['rowCustom'] === 'nino-grid-row--dark'
	&& $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--alt nino-grid-row--dim' )['settings']['dim'] === false && $fileRead( 'nino-section', 'nino-grid-row nino-grid-row--alt nino-grid-row--dim' )['settings']['rowCustom'] === 'nino-grid-row--dim'
	&& $fileSame( 'nino-section', 'nino-grid-row nino-grid-row--alt nino-grid-row--dim' ) === true );

// The animation of a section stands on its row: its classes after the radius of the row, its delay and its duration as the attributes of the row's tag. The tag of the section has no part in it - a class of the
// animation there is a class of the section like any other, an attribute of it one the builder does not keep (see the reasons above)
$vpaOn				= [ 'vpa' => 'zoom-soft', 'vpaSpeed' => 'slow', 'vpaMode' => 'repeat', 'vpaDelay' => '200ms', 'vpaDuration' => '1.5s' ];
$vpaClasses		= 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-slow nino-vpa--repeat';
$vpaAttributes	= ' data-vpa-delay="200ms" data-vpa-duration="1.5s"';
$vpaShuffled	= $fileOf( 'nino-section', 'my-row nino-vpa--repeat nino-grid-row nino-vpa--speed-slow nino-pl-1 nino-vpa--zoom-soft nino-vpa', false, false, ' data-vpa-duration="1.5s" data-vpa-delay="200ms"' );
$vpaCanonical	= $fileOf( 'nino-section', 'nino-grid-row nino-pl-1 '. $vpaClasses. ' my-row', false, false, $vpaAttributes );
$vpaRead			= $read( $vpaShuffled )['blocks'][0]['settings'];
$vpaOld				= $read( $fileOf( 'nino-section nino-vpa nino-vpa--zoom-soft', 'nino-grid-row '. $vpaClasses, false, false, $vpaAttributes ) )['blocks'][0]['settings'];

check( 'the animation of a section is read from its row: its classes among the row\'s own, in any order - the bare nino-vpa, the effect, the speed and the mode -, its delay and its duration from the attributes of the row\'s tag, in either order; what else the row has is the row\'s still, and the section keeps none of it',
	array_intersect_key( $vpaRead, $vpaOn ) === $vpaOn && $vpaRead['rowCustom'] === 'my-row' && $vpaRead['rowPl'] === '1' && $vpaRead['custom'] === '' );
check( '...and it is written in one order, byte for byte: after the classes of the row and before its custom ones, the delay then the duration on the tag of the row - the tag of the section holds none of it',
	$write( $read( $vpaShuffled ) ) === $inWrap( $vpaCanonical ) && $write( $read( $vpaCanonical ) ) === $inWrap( $vpaCanonical ) && preg_match( '/<section [^>]*vpa/', $write( $read( $vpaCanonical ) ) ) === 0
	&& substr_count( $write( $read( $vpaCanonical ) ), 'data-vpa-delay' ) === 1 && substr_count( $write( $read( $vpaCanonical ) ), 'data-vpa-duration' ) === 1 );
check( '...on the tag of the section nino-vpa and the classes after it are classes of the section like any other - custom, kept where they stood, the section none the more animated for them - and with the row\'s own they are two things that do not touch',
	$fileRead( 'nino-section nino-vpa nino-vpa--zoom-soft' )['settings']['vpa'] === null && $fileRead( 'nino-section nino-vpa nino-vpa--zoom-soft' )['settings']['custom'] === 'nino-vpa nino-vpa--zoom-soft' && $fileSame( 'nino-section nino-vpa nino-vpa--zoom-soft' ) === true
	&& $vpaOld['custom'] === 'nino-vpa nino-vpa--zoom-soft' && array_intersect_key( $vpaOld, $vpaOn ) === $vpaOn && $fileSame( 'nino-section nino-vpa nino-vpa--zoom-soft', 'nino-grid-row '. $vpaClasses, false, false, $vpaAttributes ) === true );
check( '...a section that is not animated has nothing of it on its row, one that is animated with nothing chosen has the bare class - and the delay alone, on a row, makes it that one',
	str_contains( $coverWritten( [ 'vpa' => null ] ), 'vpa' ) === false && str_contains( $coverWritten( [ 'vpa' => '' ] ), '<div class="nino-grid-row nino-vpa">' ) === true && $fileRead( 'nino-section', 'nino-grid-row', false, false, ' data-vpa-delay="1s"' )['settings']['vpa'] === '' );

// The arrow of a section is the call of the kernel's scroll hint on the line after its row, whitespace between: a setting of the section. In a column the same call is a component like any other
$arrowCall	= '['. $reader::ARROW. ']';
$arrowText	= $coverWritten( [ 'arrow' => true ] );
$arrowNode	= $read( $page( $section( '['. $reader::ARROW. ' '. $arrowTo. '="next"]' ) ) )['blocks'][0];

check( 'the arrow of a section is the call of the scroll hint on a line of its own, one tab in, after the row and before the closing tag - no class, no attribute, nothing else of the section changes -, written from the setting and read back as it was set; a section without it has no call and reads as off',
	str_contains( $arrowText, "\t</div>\n\t". $arrowCall. "\n</section>" ) === true && substr_count( $arrowText, '['. $reader::ARROW ) === 1 && str_replace( "\t". $arrowCall. "\n", '', $arrowText ) === $coverWritten( [] ) && $coverBack( [ 'arrow' => true ] ) === true
	&& str_contains( $coverWritten( [] ), $reader::ARROW ) === false && $read( $coverWritten( [] ) )['blocks'][0]['settings']['arrow'] === false && $reader::sectionDefaults()['arrow'] === false );
check( '...whatever white space lies between the row and the call it is the arrow, and written again it is the one canonical line - with a cover, a picture and a background inside as well',
	$read( str_replace( "\t</div>\n\t". $arrowCall, "\t</div>\n\n\t\t". $arrowCall, $arrowText ) )['blocks'][0] === $read( $arrowText )['blocks'][0] && $write( $read( $arrowText ) ) === $arrowText
	&& $coverBack( [ 'arrow' => true, 'cover' => 60, 'dim' => true, 'row' => 'wide' ] ) === true && $pictureBack( [ 'arrow' => true ] ) === true && $pictureBack( [ 'arrow' => true, 'inner' => true ] ) === true );
check( '...a call with a target, a second call and a call before the row are no arrow - the target is not a setting the builder keeps; each is a foreign block with its reason, see above -, and in a column the call is a component like any other, with its target',
	$arrowNode['settings']['arrow'] === false && array_column( $arrowNode['cols'][0]['components'], 'name' ) === [ $reader::ARROW ] && $arrowNode['cols'][0]['components'][0]['attributes'][$arrowTo] === 'next'
	&& $write( $read( $page( $section( '['. $reader::ARROW. ' '. $arrowTo. '="next"]' ) ) ) ) === $inWrap( $page( $section( '['. $reader::ARROW. ' '. $arrowTo. '="next"]' ) ) ) );

// How far in from either side the picture behind an element starts: nino-bg-pl-<n> and nino-bg-pr-<n>, a step from 0 to 3 for a side, 0 being the edge. A row has them where it holds the picture, a column where it
// has one, a card whatever it holds; on any other they move nothing, and stay custom classes as the radius of such does
$bgRowWrong = [];

foreach( $reader::BG_STEPS as $step )
	foreach( $reader::ROW_BG_SPACING as $side => $key ) {

		$classes	= 'nino-grid-row nino-bg-'. $side. '-'. $step;
		$made			= $pictured( [ 'inner' => true, $key => $step ] );
		$got			= $fileRead( 'nino-section', $classes, false, true )['settings'];

		// Read from the class of a row that holds the picture, written back as it was, and written from the setting and read back as set
		if( $got[$key] !== $step || $got['rowCustom'] !== '' || $fileSame( 'nino-section', $classes, false, true ) === false
			|| $write( [ 'blocks' => [ $made ] ] ) !== $inWrap( $fileOf( 'nino-section', $classes, false, true ) ) || $read( $write( [ 'blocks' => [ $made ] ] ) )['blocks'][0] !== $made )
			$bgRowWrong[] = $key. ' '. $step;
	}

check( 'how far in from either side the picture behind the row starts is a step from 0 to 3 for the left and for the right side - nino-bg-pl-<n> and nino-bg-pr-<n> on the row that holds the picture -, read from the class and written back byte for byte, and written from the setting and read back as set: all '
	. count( $reader::BG_STEPS ) * count( $reader::ROW_BG_SPACING ). ' classes'. ( $bgRowWrong === [] ? '' : ' - '. implode( ', ', $bgRowWrong ) ),
	$bgRowWrong === [] && $reader::BG_STEPS === [ '0', '1', '2', '3' ] && $reader::ROW_BG_SPACING === [ 'pl' => 'rowBgPl', 'pr' => 'rowBgPr' ] && $reader::sectionDefaults()['rowBgPl'] === '' && $reader::sectionDefaults()['rowBgPr'] === '' );

$bgRowOrdered	= 'nino-grid-row nino-grid-row--wide nino-pl-1 nino-pb-2 nino-bg-pl-3 nino-bg-pr-0 '. $reader::RADIUS_CLASS['alternative']. ' my-row';
$bgRowMade		= $pictured( [ 'inner' => true, 'row' => 'wide', 'rowPl' => '1', 'rowPb' => '2', 'rowBgPl' => '3', 'rowBgPr' => '0', 'rowRadius' => 'alternative', 'rowCustom' => 'my-row' ] );
check( '...they stand after the padding of the row and before its radius - the left side, then the right, a step of 0 written like the others -, whatever order the file has them in: the same row, written in that one order, and what is written reads as it was set',
	$write( [ 'blocks' => [ $bgRowMade ] ] ) === $inWrap( $fileOf( 'nino-section', $bgRowOrdered, false, true ) ) && $read( $write( [ 'blocks' => [ $bgRowMade ] ] ) )['blocks'][0] === $bgRowMade
	&& $fileRead( 'nino-section', 'my-row '. $reader::RADIUS_CLASS['alternative']. ' nino-bg-pr-0 nino-pb-2 nino-bg-pl-3 nino-grid-row--wide nino-pl-1 nino-grid-row', false, true ) === $bgRowMade );

$bgRowIdle		= $fileRead( 'nino-section', 'nino-grid-row nino-bg-pl-2 nino-bg-pr-1' )['settings'];
$bgRowBehind	= $fileRead( 'nino-section', 'nino-grid-row nino-bg-pl-2', true )['settings'];
$bgRowTwice		= $fileRead( 'nino-section', 'nino-grid-row nino-bg-pl-1 nino-bg-pr-3 nino-bg-pl-4 nino-bg-pr-x nino-bg-pl-2', false, true )['settings'];
check( '...on a row that holds no picture they move nothing: the classes stay custom classes of the row, and the picture behind the section is not the row\'s; so do a step the scale has not, a word, and a second class of a side, which is a class of its own - all written back where they stood',
	[ $bgRowIdle['rowBgPl'], $bgRowIdle['rowBgPr'], $bgRowIdle['rowCustom'] ] === [ '', '', 'nino-bg-pl-2 nino-bg-pr-1' ] && $fileSame( 'nino-section', 'nino-grid-row nino-bg-pl-2 nino-bg-pr-1' ) === true
	&& [ $bgRowBehind['rowBgPl'], $bgRowBehind['rowCustom'] ] === [ '', 'nino-bg-pl-2' ] && $fileSame( 'nino-section', 'nino-grid-row nino-bg-pl-2', true ) === true
	&& [ $bgRowTwice['rowBgPl'], $bgRowTwice['rowBgPr'], $bgRowTwice['rowCustom'] ] === [ '1', '3', 'nino-bg-pl-4 nino-bg-pr-x nino-bg-pl-2' ]
	&& $fileSame( 'nino-section', 'nino-grid-row nino-bg-pl-1 nino-bg-pr-3 nino-bg-pl-4 nino-bg-pr-x nino-bg-pl-2', false, true ) === true );

check( '...and the Writer leaves them out where the model sets them on a row that holds no picture - the picture behind the section is not the row\'s, and there may be none -: they would move nothing, and a file that has them reads back the settings it had',
	str_contains( $coverWritten( [ 'rowBgPl' => '2', 'rowBgPr' => '1' ] ), 'nino-bg-' ) === false && str_contains( $pictureText( [ 'rowBgPl' => '2' ] ), 'nino-bg-' ) === false
	&& str_contains( $pictureText( [ 'inner' => true, 'rowBgPl' => '2' ], null ), 'nino-bg-' ) === false && str_contains( $pictureText( [ 'inner' => true, 'rowBgPl' => '2' ] ), 'nino-bg-pl-2' ) === true );

// A loop that makes sections: its call stands where the tag of a section does, the id and the classes after nino-section are the call's, and what a section holds is between its tags
$loopFile		= "[sections /services sort=\"title\" limit=\"3\" id=\"news\" class=\"nino-section--alt nino-pt-2\"]\n". $loopRow. "\n[/sections]";
$loopBlock	= $read( $page( $loopFile ) )['blocks'][0];
$loopBlank	= array_replace( $loopBlock, [ 'settings' => $blank['settings'] ] );
$loopMade		= static fn( array $set = [], ?array $background = null ): array => array_replace( $loopBlank, [ 'settings' => array_replace( $loopBlank['settings'], $set ), 'background' => $background ] );
$loopText		= static fn( array $set = [], ?array $background = null ): string => $write( [ 'blocks' => [ $loopMade( $set, $background ) ] ] );
$loopBack		= static fn( array $set = [], ?array $background = null ): bool => $read( $loopText( $set, $background ) )['blocks'][0] === $loopMade( $set, $background );

check( 'a loop that makes sections is read as a section: its id is the call\'s, its classes after nino-section the section\'s - the colour and the padding here - and the loop the stack of it, with the attributes of its schema, the id and the class among them empty',
	$loopBlock['kind'] === 'section' && $loopBlock['id'] === 'news' && $loopBlock['settings']['color'] === 'alt' && $loopBlock['settings']['pt'] === '2' && $loopBlock['settings']['custom'] === ''
	&& $loopBlock['stack']['name'] === 'sections' && $loopBlock['stack']['source'] === '/services' && array_keys( $loopBlock['stack'] ) === [ 'name', 'source', 'attributes' ]
	&& array_keys( $loopBlock['stack']['attributes'] ) === array_keys( $registry['stacks']['sections']['defaults'] ) && $loopBlock['stack']['attributes']['sort'] === 'title' && $loopBlock['stack']['attributes']['limit'] === '3'
	&& $loopBlock['stack']['attributes']['id'] === '' && $loopBlock['stack']['attributes']['class'] === ''
	&& count( $loopBlock['cols'] ) === 1 && array_column( $loopBlock['cols'][0]['components'], 'source' ) === [ 'title' ] && $loopBlock['cols'][0]['stack'] === null && $loopBlock['background'] === null );
check( '...every section has the key, and a section that is a <section> has no loop: null, in the model and in the file',
	array_filter( $model['blocks'], static fn( array $block ): bool => $block['kind'] === 'section' && ( array_key_exists( 'stack', $block ) === false || $block['stack'] !== null ) ) === [] && array_keys( $blank ) === [ 'kind', 'id', 'settings', 'background', 'stack', 'cols' ] && $blank['stack'] === null );
check( '...and it is written back byte for byte: the call at the margin with the attributes of the loop in the order of its schema, then the id, then the class - the row and the columns as they stand in a section, one tab in - and the closing tag where the call is',
	$write( $read( $page( $loopFile ) ) ) === $inWrap( $page( $loopFile ) ) && $read( $write( $read( $page( $loopFile ) ) ) ) === $read( $page( $loopFile ) )
	&& str_contains( $write( $read( $page( $loopFile ) ) ), "\n[sections /services sort=\"title\" limit=\"3\" id=\"news\" class=\"nino-section--alt nino-pt-2\"]\n\t<div class=\"nino-grid-row\">\n" ) === true );
check( '...a call with no class of the section\'s has none: the classes are the section\'s settings in the order of a section\'s, and read back as set',
	str_contains( $loopText(), "\n[sections /services sort=\"title\" limit=\"3\" id=\"news\"]\n" ) === true && $loopBack() === true
	&& str_contains( $loopText( [ 'color' => 'black', 'border' => '2', 'mt' => '1', 'text' => 'center', 'custom' => 'my-own' ] ), ' id="news" class="nino-section--black nino-section--border-2 nino-mt-1 nino-text-center my-own"]' ) === true
	&& $loopBack( [ 'color' => 'black', 'border' => '2', 'mt' => '1', 'text' => 'center', 'custom' => 'my-own', 'whitespace' => 'more', 'row' => 'full', 'rowPlace' => 'left', 'rowGap' => '3', 'rowPl' => '0', 'rowPr' => '0' ] ) === true
	&& str_contains( $loopText( [ 'row' => 'full', 'rowPlace' => 'left', 'rowPl' => '0', 'rowPr' => '0' ] ), "\n\t<div class=\"nino-grid-row nino-grid-row--full nino-grid-row--left nino-pl-0 nino-pr-0\">\n" ) === true
	&& str_contains( $loopText( [ 'rowGap' => '3', 'rowPl' => '1' ] ), "\n\t<div class=\"nino-grid-row nino-grid-gap-3 nino-pl-1\">\n" ) === true );
check( '...a cover has no height to write in a call: it is the 90 of Nino.ui.js, nino-cover with the padding and the place of the content, and no data-cover-height',
	str_contains( $loopText( [ 'cover' => 90, 'padded' => true, 'contentPos' => 'top' ] ), ' id="news" class="nino-cover nino-cover--padded nino-cover-top"]' ) === true && str_contains( $loopText( [ 'cover' => 90 ] ), 'data-cover-height' ) === false
	&& $loopBack( [ 'cover' => 90, 'padded' => true, 'contentPos' => 'top' ] ) === true && $loopBack( [ 'parallax' => true, 'dim' => true ] ) === true );
$loopAnimated = [ 'vpa' => 'slide-up-soft', 'vpaSpeed' => 'fast', 'vpaMode' => 'visible', 'vpaDelay' => '200ms', 'vpaDuration' => '2s' ];
check( '...the animation of a section that is a loop stands on its row inside the call, as it does on any section: its classes and its delay and its duration, which the row has as attributes of its tag - the call keeps none of it - and it reads back as set',
	str_contains( $loopText( $loopAnimated ), "\n[sections /services sort=\"title\" limit=\"3\" id=\"news\"]\n\t<div class=\"nino-grid-row nino-vpa nino-vpa--slide-up-soft nino-vpa--speed-fast nino-vpa--visible\" data-vpa-delay=\"200ms\" data-vpa-duration=\"2s\">\n" ) === true
	&& $loopBack( $loopAnimated ) === true && $loopBack( $loopAnimated + [ 'row' => 'full', 'rowPl' => '2', 'custom' => 'my-own', 'rowCustom' => 'my-row' ] ) === true
	&& $read( str_replace( ' class="nino-section--alt nino-pt-2"', ' class="nino-section--alt nino-pt-2 nino-vpa"', $page( $loopFile ) ) )['blocks'][0]['settings']['vpa'] === null
	&& $read( str_replace( ' class="nino-section--alt nino-pt-2"', ' class="nino-section--alt nino-pt-2 nino-vpa"', $page( $loopFile ) ) )['blocks'][0]['settings']['custom'] === 'nino-vpa'
	&& $read( $page( str_replace( 'id="news"', 'id="news" data-vpa-delay="1s"', $loopFile ) ) )['blocks'][0]['reason']['code'] === 'sections-attribute' );
check( '...the arrow of a loop of sections is the line after its row inside the call, as it is in a section, and reads back as set - with the animation of its row as well',
	str_contains( $loopText( [ 'arrow' => true ] ), "\t</div>\n\t". $arrowCall. "\n[/sections]\n" ) === true && $loopBack( [ 'arrow' => true ] ) === true && $loopBack( [ 'arrow' => true ] + $loopAnimated ) === true
	&& str_contains( $loopText( [] ), $reader::ARROW ) === false );
check( '...the picture behind it is the first thing between the tags, the scrim over it among the classes of the call; with the background inside it is the row\'s: the picture first in the row, the colour and the scrim on the row',
	str_contains( $loopText( [ 'dim' => true ], $picture ), "[sections /services sort=\"title\" limit=\"3\" id=\"news\" class=\"nino-section--dim\"]\n\t<div class=\"nino-section-bg\">[image /template/page-x/a/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row\">" ) === true
	&& $loopBack( [ 'dim' => true ], $picture ) === true
	&& str_contains( $loopText( [ 'inner' => true, 'color' => 'tint', 'dim' => true ], $picture ), " id=\"news\"]\n\t<div class=\"nino-grid-row nino-grid-row--tint nino-grid-row--dim\">\n\t\t<div class=\"nino-grid-row-bg\">" ) === true
	&& $loopBack( [ 'inner' => true, 'color' => 'tint', 'dim' => true ], $picture ) === true && $loopBack( [], $picture ) === true );

$loopWide = "[sections /services id=\"news\"]\n\t<div class=\"nino-grid-row nino-grid-row--wide\">\n\t\t<div class=\"nino-grid-100 nino-grid-s-100 nino-grid-m-50 nino-grid-l-50\">\n\t\t\t<div class=\"nino-grid-bg\">[image /template/page-x/a/photo alt=\"\"]</div>\n"
	. "\t\t\t[title title]\n\t\t\t[card]\n\t\t\t\t[card-title title]\n\t\t\t[/card]\n\t\t</div>\n\t\t<div class=\"nino-grid-100 nino-grid-s-100 nino-grid-m-50 nino-grid-l-50\">\n\t\t\t[button .uri text=\"More\"]\n\t\t</div>\n\t</div>\n[/sections]";
$wide = $read( $page( $loopWide ) )['blocks'][0];
check( '...its columns are columns as any, each with its picture and its components - a field of the element is the source of one, as in a loop of a column - and a card is a container in it as anywhere: written back byte for byte',
	$wide['kind'] === 'section' && count( $wide['cols'] ) === 2 && $wide['cols'][0]['background'] === [ 'slot' => '/template/page-x/a/photo', 'focus' => null ] && array_column( $wide['cols'][0]['components'], 'name' ) === [ 'title', 'card' ]
	&& array_column( $wide['cols'][0]['components'][1]['components'], 'name' ) === [ 'card-title' ] && $wide['cols'][1]['components'][0]['source'] === '.uri' && $wide['cols'][1]['components'][0]['text'] === 'More' && $wide['settings']['row'] === 'wide'
	&& $write( $read( $page( $loopWide ) ) ) === $inWrap( $page( $loopWide ) ) );

$between = $read( $page( $section( '[title /template/page-x/a/title]', 'first' ), $loopFile, $section( '[title /template/page-x/a/title]', 'last' ) ) );
check( '...it stands between the sections of a page as one of them: the blocks in the order of the file, all three sections, and the page is written back byte for byte',
	array_column( $between['blocks'], 'kind' ) === [ 'section', 'section', 'section' ] && array_column( $between['blocks'], 'id' ) === [ 'first', 'news', 'last' ] && array_column( $between['blocks'], 'stack' ) === [ null, $loopBlock['stack'], null ]
	&& $write( $between ) === $inWrap( $page( $section( '[title /template/page-x/a/title]', 'first' ), $loopFile, $section( '[title /template/page-x/a/title]', 'last' ) ) ) && $read( $write( $between ) ) === $between );

$badLoop = "[sections /services id=\"bad\"]\n\t<p>No row</p>\n\t<section id=\"inner\" class=\"nino-section\">\n\t\t". $row. "\n\t</section>\n[/sections]";
$afterBad = $read( $page( $section( '<p>x</p>', 'first' ), $loopFile, $badLoop, $section( '[title /template/page-x/a/title]', 'last' ) ) )['blocks'];
check( '...a loop that fails is one foreign block with what is inside it - a <section> in it is no block of its own - and the reading goes on after its closing tag; so it does after a section that failed, up to the loop that is read',
	array_column( $afterBad, 'kind' ) === [ 'html', 'section', 'html', 'section' ] && $afterBad[1]['id'] === 'news' && $afterBad[2]['source'] === $badLoop && $afterBad[2]['reason']['code'] === 'sections-no-row' && $afterBad[3]['id'] === 'last' );
$sameId = $read( $page( $section( '[title /template/page-x/a/title]', 'news' ), $loopFile ) )['blocks'];
check( '...its id is the id of a section as any is: the id of an earlier section is the loop failing, and so is the id it has itself twice',
	array_column( $sameId, 'kind' ) === [ 'section', 'html' ] && $sameId[1]['reason']['code'] === 'duplicate-id' && array_column( $read( $page( $loopFile, $loopFile ) )['blocks'], 'kind' ) === [ 'section', 'html' ] );
[ $edited, $why ] = \Nino\Modules\Builder\Reader::readSection( $loopFile, $registry );
check( '...a block that was edited is read as a section again, the loop as well - and what stands after it is no part of it',
	$why === null && $edited === $loopBlock && \Nino\Modules\Builder\Reader::readSection( $loopFile. "\n<p>After</p>", $registry )[1]['code'] === 'section-content' && \Nino\Modules\Builder\Reader::readSection( $loopFile, $registry, [ 'news' => true ] )[1]['code'] === 'duplicate-id' );

check( 'the stack that makes sections is known by its schema: the Document marks it - cell is section - and the Reader asks the schema and no name: with the mark taken away the call is no section, and stands as a block of html that nothing reads',
	$registry['stacks']['sections']['cell'] === 'section' && array_keys( array_filter( $registry['stacks'], static fn( array $schema ): bool => ( $schema['cell'] ?? '' ) === 'section' ) ) === [ 'sections' ]
	&& \Nino\Modules\Builder\Reader::loopsSections( $registry ) === 'sections' && \Nino\Modules\Builder\Reader::loopsSections( [] ) === '' && \Nino\Modules\Builder\Reader::loopsSections( [ 'stacks' => [ 'stack' => [ 'grid' => true ] ] ] ) === ''
	&& ( static function() use ( $registry, $page, $loopFile ): bool {
		$bare = $registry;
		unset( $bare['stacks']['sections']['cell'] );
		$blocks = \Nino\Modules\Builder\Reader::read( $page( $loopFile ), $bare )['blocks'];
		return count( $blocks ) === 1 && $blocks[0]['kind'] === 'html' && $blocks[0]['reason']['code'] === 'not-a-section' && $blocks[0]['source'] === $loopFile;
	} )() );

$hostile = $blank;
$hostile['settings']['custom'] = 'a" onmouseover="alert(1)';
check( 'a custom class is escaped where it is written, so it cannot leave the attribute', str_contains( $write( [ 'blocks' => [ $hostile ] ] ), 'onmouseover="' ) === false && str_contains( $write( [ 'blocks' => [ $hostile ] ] ), '&quot;' ) === true );

$stacked = $read( $page( "<section id=\"s\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[slider /services width=\"60%\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]\n\t\t</div>\n\t</div>\n</section>" ) )['blocks'][0];
check( 'a stack other than [stack] is read and written as its own, with the attributes of its schema', $stacked['kind'] === 'section' && $stacked['cols'][0]['stack']['name'] === 'slider' && $stacked['cols'][0]['stack']['attributes']['width'] === '60%'
	&& str_contains( $write( [ 'blocks' => [ $stacked ] ] ), "[slider /services width=\"60%\"]\n\t\t\t\t[title title]\n\t\t\t[/slider]" ) === true );

// A loop is the grid and nothing about what a cell looks like: it has no card attribute, and a card is a component in its content, once for each element
$carded		= $page( $section( "[stack /services sort=\"title\" cols=\"100 50 50\" autoheight=\"1\"]\n\t\t\t\t[card fill=\"1\" ground=\"counter\"]\n\t\t\t\t\t[card-title title]\n\t\t\t\t[/card]\n\t\t\t[/stack]" ) );
$cards		= $read( $carded )['blocks'][0]['cols'][0];
check( 'a loop that holds a card is read and written like any other: the card is the content of the stack, one level deeper - its call at the depth of the components of a loop, its children one tab deeper still, its closing tag where its call is - and written back byte for byte',
	$cards['stack']['name'] === 'stack' && array_keys( $cards['stack']['attributes'] ) === [ 'sort', 'offset', 'limit', 'query', 'callback', 'locale', 'id', 'cols', 'gap', 'autoheight', 'class' ] && array_column( $cards['components'], 'name' ) === [ 'card' ]
	&& array_column( $cards['components'][0]['components'], 'source' ) === [ 'title' ] && $write( $read( $carded ) ) === $inWrap( $carded ) && $read( $write( $read( $carded ) ) ) === $read( $carded ) );

// The spacing of an element: six sides - the margin above and below it, the padding at its top, at its bottom, on its left and on its right -, each a step of the space scale, the same on a section, on a column and on
// any component. A section and a column carry them as classes of the tag, a component in the class attribute of its call
$spaceOf = static function( string $kind, string $classes ) use ( $read, $write, $page, $section, $groundOf, $inWrap ): array {

	$source	= match( $kind ) {
		'section'	=> $page( $section( '[title /template/page-x/a/title]', 'a', trim( 'nino-section '. $classes ) ) ),
		'column'	=> $groundOf( trim( 'nino-grid-100 '. $classes ) ),
		default		=> $page( $section( '[title /template/page-x/a/title'. ( $classes === '' ? '' : ' class="'. $classes. '"' ). ']' ) ),
	};
	$model	= $read( $source );
	$of			= match( $kind ) {
		'section'	=> $model['blocks'][0]['settings'],
		'column'	=> $model['blocks'][0]['cols'][0],
		default		=> $model['blocks'][0]['cols'][0]['components'][0],
	};

	// The six settings, the classes the element keeps besides them, the file as the Writer writes it - and whether that is the file it was read from, and reads back as the same model
	return [
		'sides'		=> array_intersect_key( $of, array_flip( \Nino\Modules\Builder\Reader::SPACING ) ),
		'left'		=> $kind === 'component' ? $of['attributes']['class'] : $of['custom'],
		'written'	=> $write( $model ),
		'same'		=> $write( $model ) === $inWrap( $source ) && $read( $write( $model ) ) === $model,
	];
};
$noSides	= array_fill_keys( $reader::SPACING, '' );
$sidesOf	= static fn( array $set ): array => array_replace( $noSides, $set );
$spaceWrong	= [];

foreach( [ 'section', 'column', 'component' ] as $kind )
	foreach( $reader::SPACING as $side )
		foreach( $reader::STEPS as $step ) {

			$got = $spaceOf( $kind, 'nino-'. $side. '-'. $step );

			if( $got['sides'] !== $sidesOf( [ $side => $step ] ) || $got['left'] !== '' || $got['same'] !== true )
				$spaceWrong[] = $kind. ' '. $side. '-'. $step;
		}

check( 'an element has six sides of spacing - mt, mb, pt, pb, pl and pr, each of the steps 0 to 6 of the space scale - on a section, on a column and on a component alike, read from the class of the side and written back byte for byte: all '
	. 3 * count( $reader::SPACING ) * count( $reader::STEPS ). ' of them'. ( $spaceWrong === [] ? '' : ' - '. implode( ', ', array_slice( $spaceWrong, 0, 5 ) ) ),
	$spaceWrong === [] && $reader::SPACING === [ 'mt', 'mb', 'pt', 'pb', 'pl', 'pr' ] && $reader::STEPS === [ '0', '1', '2', '3', '4', '5', '6' ] );
check( '...the six stand in one order, that of Reader::SPACING: in the defaults of a section and of a column, and in the node of a component - which has no spacing of its own but them - and each is none until a class says so',
	array_keys( $reader::sectionDefaults() ) === [ 'row', 'rowPlace', 'rowAlign', 'rowGap', 'rowPl', 'rowPr', 'rowPt', 'rowPb', 'rowRadius', 'rowCustom', 'rowBgPl', 'rowBgPr', 'color', 'border', 'borderPrimary', 'cover', 'parallax', 'dim', 'padded', 'radius', 'contentPos', 'inner', 'whitespace',
		'mt', 'mb', 'pt', 'pb', 'pl', 'pr', 'text', 'arrow', 'vpa', 'vpaSpeed', 'vpaMode', 'vpaDelay', 'vpaDuration', 'custom' ]
	&& array_keys( $reader::colDefaults() ) === [ 'color', 'dim', 'radius', 'background', 'text', 'stackAlign', 'stackGap', 'mt', 'mb', 'pt', 'pb', 'pl', 'pr', 'bgPl', 'bgPr', 'vpa', 'vpaSpeed', 'vpaMode', 'vpaDelay', 'vpaDuration', 'custom' ]
	&& array_keys( $blank['cols'][0]['components'][0] ) === array_merge( [ 'name', 'source', 'text', 'attributes' ], $reader::SPACING ) && array_intersect_key( $reader::sectionDefaults(), $noSides ) === $noSides && array_intersect_key( $reader::colDefaults(), $noSides ) === $noSides );

$spacedSection	= $spaceOf( 'section', 'nino-pr-4 nino-text-right nino-pl-2 nino-pb-6 nino-mb-0 nino-whitespace--less nino-pt-3 nino-mt-1' );
$spacedColumn		= $spaceOf( 'column', 'nino-pr-4 nino-vpa nino-pl-2 nino-grid--alt nino-pb-6 nino-text-center nino-mb-0 nino-pt-3 nino-mt-1' );
$spacedCardFile	= $bgCardOf( 'my-card nino-pr-4 nino-radius nino-pl-2 nino-bg-pr-1 nino-pb-6 nino-mb-0 nino-pt-3 nino-bg-pl-0 nino-mt-1' );
$spacedCard			= $read( $spacedCardFile )['blocks'][0]['cols'][0]['components'][0];
check( '...the classes are read in any order and written in one: on a section after the whitespace and before the text, on a column after the text and before the animation, on a component after the radius and the offsets of its ground and before the classes of its own - the margin above and below, then the padding at the top, at the bottom, on the left and on the right',
	$spacedSection['sides'] === [ 'mt' => '1', 'mb' => '0', 'pt' => '3', 'pb' => '6', 'pl' => '2', 'pr' => '4' ] && $spacedColumn['sides'] === $spacedSection['sides'] && array_intersect_key( $spacedCard, $spacedSection['sides'] ) === $spacedSection['sides']
	&& $spacedSection['left'] === '' && $spacedColumn['left'] === '' && $spacedCard['attributes']['class'] === 'my-card' && $spacedCard['radius'] === 'standard' && [ $spacedCard['bgPl'], $spacedCard['bgPr'] ] === [ '0', '1' ]
	&& str_contains( $spacedSection['written'], '<section id="a" class="nino-section nino-whitespace--less nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-pl-2 nino-pr-4 nino-text-right">' ) === true
	&& str_contains( $spacedColumn['written'], '<div class="nino-grid-100 nino-grid--alt nino-text-center nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-pl-2 nino-pr-4 nino-vpa">' ) === true
	&& str_contains( $write( $read( $spacedCardFile ) ), '[card class="nino-radius nino-bg-pl-0 nino-bg-pr-1 nino-mt-1 nino-mb-0 nino-pt-3 nino-pb-6 nino-pl-2 nino-pr-4 my-card"]' ) === true );

// The kernel's nino-p-<n> is the four paddings at once, on a tag: the base that a class of one side stands over, whatever the order of the two
$shortWrong = [];

foreach( [ 'section', 'column' ] as $kind )
	foreach( $reader::STEPS as $step ) {

		$got = $spaceOf( $kind, 'nino-p-'. $step );

		if( $got['sides'] !== $sidesOf( array_fill_keys( $reader::INSIDE, $step ) ) || $got['left'] !== '' || $got['same'] !== true )
			$shortWrong[] = $kind. ' p-'. $step;
	}

check( 'the shorthand of the kernel, nino-p-<n>, says the four paddings at once: on a section and on a column it is read as the four, none of the margins, and written back as nino-p-<n> byte for byte - all '. 2 * count( $reader::STEPS ). ' of them'. ( $shortWrong === [] ? '' : ' - '. implode( ', ', $shortWrong ) ), $shortWrong === [] && $reader::INSIDE === [ 'pt', 'pb', 'pl', 'pr' ] );

$shortOver	= [ $spaceOf( 'column', 'nino-pt-3 nino-p-1' ), $spaceOf( 'column', 'nino-p-1 nino-mt-2 nino-pt-3' ), $spaceOf( 'section', 'nino-pr-0 nino-p-4' ) ];
$shortFour	= $spaceOf( 'column', 'nino-pr-2 nino-pt-2 nino-pl-2 nino-pb-2' );
$shortThree	= $spaceOf( 'column', 'nino-pt-2 nino-pb-2 nino-pl-2' );
$shortTwice	= $spaceOf( 'section', 'nino-p-1 my-section nino-p-4' );
check( '...a class of one side stands over it, whichever comes first in the attribute - Nino.css writes the rules of one side after the one of all four -, and the four are written one by one then; a second nino-p-<n> is a class of its own',
	$shortOver[0]['sides'] === $sidesOf( [ 'pt' => '3', 'pb' => '1', 'pl' => '1', 'pr' => '1' ] ) && $shortOver[1]['sides'] === $sidesOf( [ 'mt' => '2', 'pt' => '3', 'pb' => '1', 'pl' => '1', 'pr' => '1' ] ) && $shortOver[2]['sides'] === $sidesOf( [ 'pt' => '4', 'pb' => '4', 'pl' => '4', 'pr' => '0' ] )
	&& array_filter( $shortOver, static fn( array $got ): bool => $got['left'] !== '' ) === [] && str_contains( $shortOver[0]['written'], '<div class="nino-grid-100 nino-pt-3 nino-pb-1 nino-pl-1 nino-pr-1">' ) === true
	&& str_contains( $shortOver[1]['written'], '<div class="nino-grid-100 nino-mt-2 nino-pt-3 nino-pb-1 nino-pl-1 nino-pr-1">' ) === true && str_contains( $shortOver[2]['written'], '<section id="a" class="nino-section nino-pt-4 nino-pb-4 nino-pl-4 nino-pr-0">' ) === true
	&& $shortTwice['sides'] === $sidesOf( array_fill_keys( $reader::INSIDE, '1' ) ) && $shortTwice['left'] === 'my-section nino-p-4' && $shortTwice['same'] === true );
check( '...four sides that are the same are written as the shorthand, whichever way the file said them, and three of them are not four',
	$shortFour['sides'] === $sidesOf( array_fill_keys( $reader::INSIDE, '2' ) ) && str_contains( $shortFour['written'], '<div class="nino-grid-100 nino-p-2">' ) === true
	&& $shortThree['sides'] === $sidesOf( [ 'pt' => '2', 'pb' => '2', 'pl' => '2' ] ) && $shortThree['same'] === true && str_contains( $shortThree['written'], 'nino-p-2' ) === false );

// A second class of a side is a class of its own, and so is one that says no step of the scale: on a section, on a column and in the class of a call
$secondWrong = [];

foreach( [ 'section', 'column', 'component' ] as $kind ) {

	$second	= $spaceOf( $kind, 'nino-mt-1 my-class nino-mt-2' );
	$idle		= $spaceOf( $kind, 'nino-mt-7 nino-pt-x nino-pb- nino-m-2 nino-pl-10 nino-mx-auto' );

	if( $second['sides'] !== $sidesOf( [ 'mt' => '1' ] ) || $second['left'] !== 'my-class nino-mt-2' || $second['same'] !== true )
		$secondWrong[] = $kind. ' second class';

	if( $idle['sides'] !== $noSides || $idle['left'] !== 'nino-mt-7 nino-pt-x nino-pb- nino-m-2 nino-pl-10 nino-mx-auto' || $idle['same'] !== true )
		$secondWrong[] = $kind. ' idle classes';
}

check( '...the first class of a side is the setting and a second is a class of its own, kept and written back where it stood among the others - and so are a step that is none of the scale, a word, a margin on all sides and the centring: on a section, on a column and on a component'. ( $secondWrong === [] ? '' : ' - '. implode( ', ', $secondWrong ) ), $secondWrong === [] );

// On a call nino-p-<n> says nothing of the four: it is a class of the editor's. A card's padding attribute says the same for its four sides, and the card keeps the two apart
$callShort	= $spaceOf( 'component', 'nino-p-2' );
$callMixed	= $spaceOf( 'component', 'nino-p-2 nino-pt-1 my-class' );
$callFour		= $spaceOf( 'component', 'nino-pt-2 nino-pb-2 nino-pl-2 nino-pr-2' );
$cardPadded	= $read( $page( $section( "[card padding=\"3\" class=\"nino-pl-0\"]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t[/card]" ) ) )['blocks'][0]['cols'][0]['components'][0];
check( '...on the call of a component the shorthand is a class of the editor\'s and stays among the classes of its own, the sides are taken out in front of them, and the four paddings that are the same are four classes there, never the shorthand - and the padding attribute of a card is the attribute, its sides the six settings',
	$callShort['sides'] === $noSides && $callShort['left'] === 'nino-p-2' && $callShort['same'] === true && $callMixed['sides'] === $sidesOf( [ 'pt' => '1' ] ) && $callMixed['left'] === 'nino-p-2 my-class'
	&& str_contains( $callMixed['written'], '[title /template/page-x/a/title class="nino-pt-1 nino-p-2 my-class"]' ) === true && $callFour['sides'] === $sidesOf( array_fill_keys( $reader::INSIDE, '2' ) ) && $callFour['left'] === '' && $callFour['same'] === true && $cardPadded['attributes']['padding'] === '3' && $cardPadded['pl'] === '0' && $cardPadded['attributes']['class'] === '' );

// A loop is no component and has no spacing of its own: its class stays as it is, and the components of its content have the six sides like any other. The loop of sections takes the classes of its section
$loopCalls	= $page( $section( "[stack /services class=\"nino-mt-2 nino-p-1\"]\n\t\t\t\t[title title class=\"nino-pb-1\"]\n\t\t\t[/stack]" ) );
$loopCol		= $read( $loopCalls )['blocks'][0]['cols'][0];
$loopFileSpaced	= str_replace( 'class="nino-section--alt nino-pt-2"', 'class="nino-section--alt nino-mt-1 nino-p-3"', $loopFile );
$loopSettings		= $read( $page( $loopFileSpaced ) )['blocks'][0]['settings'];
check( '...a loop is none of them: its class attribute stays as it is, and the components of its content have the six sides like any other; the loop of sections is a section, and its call carries the six sides of its classes, written back with the four paddings as nino-p-<n> where they are the same',
	$loopCol['stack']['attributes']['class'] === 'nino-mt-2 nino-p-1' && array_intersect_key( $loopCol['stack'], array_flip( $reader::SPACING ) ) === [] && $loopCol['components'][0]['pb'] === '1' && $loopCol['components'][0]['attributes']['class'] === '' && $write( $read( $loopCalls ) ) === $inWrap( $loopCalls )
	&& array_intersect_key( $loopSettings, array_flip( $reader::SPACING ) ) === $sidesOf( [ 'mt' => '1', 'pt' => '3', 'pb' => '3', 'pl' => '3', 'pr' => '3' ] ) && $loopSettings['color'] === 'alt' && $loopSettings['custom'] === '' && $write( $read( $page( $loopFileSpaced ) ) ) === $inWrap( $page( $loopFileSpaced ) ) );

// How far in from either side the picture behind a column starts: the same two classes, after the spacing of the column and before its radius, on a column that has a picture - without one they are custom classes
$bgColWrong = [];

foreach( $reader::BG_STEPS as $step )
	foreach( $reader::BG_SPACING as $side => $key ) {

		$source	= $groundOf( 'nino-grid-100 nino-bg-'. $side. '-'. $step, $photo. "\n\t\t\t[title /template/page-x/a/title]" );
		$got		= $columnOf( $source );

		if( $got[$key] !== $step || $got['custom'] !== '' || $got['background'] === null || $write( $read( $source ) ) !== $inWrap( $source ) || $read( $write( $read( $source ) ) ) !== $read( $source ) )
			$bgColWrong[] = $key. ' '. $step;
	}

check( 'how far in from either side the picture behind a column starts is the same step from 0 to 3 for either side - nino-bg-pl-<n> and nino-bg-pr-<n> on a column that has a picture -, read from the class and written back byte for byte: all '
	. count( $reader::BG_STEPS ) * count( $reader::BG_SPACING ). ' classes'. ( $bgColWrong === [] ? '' : ' - '. implode( ', ', $bgColWrong ) ),
	$bgColWrong === [] && $reader::BG_SPACING === [ 'pl' => 'bgPl', 'pr' => 'bgPr' ] && array_intersect_key( $reader::colDefaults(), array_flip( $reader::BG_SPACING ) ) === [ 'bgPl' => '', 'bgPr' => '' ] );

$bgColFile	= $groundOf( 'nino-grid-100 nino-mb-1 nino-p-2 nino-bg-pl-1 nino-bg-pr-3 '. $reader::RADIUS_CLASS['standard']. ' my-col', $photo. "\n\t\t\t[title /template/page-x/a/title]" );
$bgColMix		= $groundOf( 'my-col '. $reader::RADIUS_CLASS['standard']. ' nino-bg-pr-3 nino-mb-1 nino-bg-pl-1 nino-p-2 nino-grid-100', $photo. "\n\t\t\t[title /template/page-x/a/title]" );
$bgColTwice	= $columnOf( $groundOf( 'nino-grid-100 nino-bg-pl-1 nino-bg-pl-4 nino-bg-pr-x nino-bg-pl-2', $photo. "\n\t\t\t[title /template/page-x/a/title]" ) );
$bgColIdle	= $columnOf( $groundOf( 'nino-grid-100 nino-bg-pl-2 nino-bg-pr-1' ) );
check( '...they stand after the spacing of the column and before its radius, left then right, whatever order the file has them in; without a picture they move nothing and are custom classes, kept where they stood - and so are a step the scale has not, a word, and a second class of a side',
	$write( $read( $bgColMix ) ) === $inWrap( $bgColFile ) && $write( $read( $bgColFile ) ) === $inWrap( $bgColFile ) && $columnOf( $bgColFile )['radius'] === 'standard' && $columnOf( $bgColFile )['custom'] === 'my-col'
	&& [ $bgColIdle['bgPl'], $bgColIdle['bgPr'], $bgColIdle['custom'] ] === [ '', '', 'nino-bg-pl-2 nino-bg-pr-1' ] && $write( $read( $groundOf( 'nino-grid-100 nino-bg-pl-2 nino-bg-pr-1' ) ) ) === $inWrap( $groundOf( 'nino-grid-100 nino-bg-pl-2 nino-bg-pr-1' ) )
	&& [ $bgColTwice['bgPl'], $bgColTwice['bgPr'], $bgColTwice['custom'] ] === [ '1', '', 'nino-bg-pl-4 nino-bg-pr-x nino-bg-pl-2' ] );

$colWith = static fn( array $set ): string => $write( [ 'blocks' => [ array_replace( $blank, [ 'cols' => [ array_replace( $blank['cols'][0], $set ) ] ] ) ] ] );
check( '...and the Writer leaves them out where the model sets them on a column that has no picture, whatever ground it paints: they would move nothing',
	str_contains( $colWith( [ 'bgPl' => '2', 'bgPr' => '0' ] ), 'nino-bg-' ) === false && str_contains( $colWith( [ 'bgPl' => '2', 'color' => 'alt' ] ), 'nino-bg-' ) === false
	&& str_contains( $colWith( [ 'bgPl' => '2', 'background' => [ 'slot' => '/template/page-x/a/photo', 'focus' => null ] ] ), 'nino-bg-pl-2' ) === true );

$unspaced = $read( $fixture )['blocks'];
foreach( $unspaced as $b => $block )
	foreach( $block['cols'] ?? [] as $c => $col )
		foreach( array_merge( $reader::SPACING, $reader::BG_SPACING ) as $key )
			unset( $unspaced[$b]['cols'][$c][$key] );
check( 'a column with none of the six sides is written as it always was: a model without them - and without the offsets of its picture - is the example page, byte for byte, and every existing fixture is read and written as it was',
	$write( [ 'name' => 'Home', 'header' => 'html-header', 'footer' => 'html-footer', 'blocks' => $unspaced ] ) === $wrapped( $fixture ) && array_intersect_key( $model['blocks'][0]['cols'][0], array_flip( $reader::SPACING ) ) === array_fill_keys( $reader::SPACING, '' )
	&& $write( $wrapModel ) === $wrapFixture && $write( $read( $concept ) ) === $wrapped( $fixture ) );

// A card as the Writer writes it: every attribute, in the order of the schema and only where it differs from the default. The attributes are the registry's, whichever the kernel declares: each is set to a
// value other than its default - a switch to the other state, a choice to the last option that is not the default, a picture to a slot, any other to a word of its own
$cardAway = [];

foreach( $reader::defaults( $registry['components']['card'] ) as $attribute => $default ) {

	$declared = $registry['components']['card']['attributes'][$attribute] ?? [];

	$cardAway[$attribute] = match( $declared['type'] ?? 'string' ) {
		'bool'								=> $default === '1' ? '0' : '1',
		'select', 'variant'		=> implode( '', array_slice( array_diff( $declared['options'], [ $default ] ), -1 ) ),
		'image'								=> '/template/page-x/a/backdrop',
		default								=> 'my-'. $attribute,
	};
}

$allSet = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $cardAway, 'radius' => '' ] + array_fill_keys( $reader::BG_SPACING, '' ) + array_fill_keys( $reader::SPACING, '' ) + [
	'components' => [
		[ 'name' => 'card-title', 'source' => '/template/page-x/a/title', 'text' => null, 'attributes' => array_replace( $registry['components']['card-title']['defaults'], [ 'tag' => 'h3', 'style' => 'quiet' ] ) ] + array_fill_keys( $reader::SPACING, '' ),
		[ 'name' => 'button', 'source' => '', 'text' => 'Go', 'attributes' => array_replace( $registry['components']['button']['defaults'], [ 'href' => '#top' ] ) ] + array_fill_keys( $reader::SPACING, '' ),
	] ];
$cardBlock = $blank;
$cardBlock['cols'][0]['components'] = [ $allSet ];
$cardText = $write( [ 'blocks' => [ $cardBlock ] ] );
$cardCallAway = '[card'. implode( '', array_map( static fn( string $attribute, string $value ): string => ' '. $attribute. '="'. $value. '"', array_keys( $cardAway ), $cardAway ) ). ']';
check( 'a card with every attribute set is its call with the attributes in the order of the schema, the ones the kernel declares and then the class, each away from its default, its children one tab deeper on a line each and its closing tag at the depth of its call',
	count( $cardAway ) === count( $registry['components']['card']['attributes'] ) + 1 && array_keys( $cardAway ) === array_merge( array_keys( $registry['components']['card']['attributes'] ), [ 'class' ] ) && array_filter( $cardAway, static fn( string $value, string $attribute ): bool => $value === $registry['components']['card']['defaults'][$attribute], ARRAY_FILTER_USE_BOTH ) === []
	&& str_contains( $cardText, "\t\t\t". $cardCallAway. "\n"
		. "\t\t\t\t[card-title /template/page-x/a/title tag=\"h3\" style=\"quiet\"]\n\t\t\t\t[button text=\"Go\" href=\"#top\"]\n\t\t\t[/card]\n\t\t</div>\n" ) === true );
check( '...and what is written reads back as what was meant: the card with its attributes and its children, node for node', $read( $cardText )['blocks'][0]['cols'][0]['components'][0] === $allSet );

// In the content of a loop the card is one level deeper, and so are its children
$looped = $blank;
$looped['cols'][0]['stack'] = [ 'name' => 'stack', 'source' => '/services', 'attributes' => $registry['stacks']['stack']['defaults'] ];
$cardFirst = array_key_first( $cardAway );
$looped['cols'][0]['components'] = [ [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => array_replace( $registry['components']['card']['defaults'], [ $cardFirst => $cardAway[$cardFirst] ] ), 'radius' => '' ] + array_fill_keys( $reader::BG_SPACING, '' ) + array_fill_keys( $reader::SPACING, '' ) + [
	'components' => [ [ 'name' => 'card-title', 'source' => 'title', 'text' => null, 'attributes' => $registry['components']['card-title']['defaults'] ] + array_fill_keys( $reader::SPACING, '' ) ] ] ];
check( '...in the content of a loop the call of the card stands one tab deeper, its children one more and its closing tag with its call - and it reads back as the loop and the card it was', str_contains( $write( [ 'blocks' => [ $looped ] ] ),
	"\t\t\t[stack /services]\n\t\t\t\t[card ". $cardFirst. "=\"". $cardAway[$cardFirst]. "\"]\n\t\t\t\t\t[card-title title]\n\t\t\t\t[/card]\n\t\t\t[/stack]\n" ) === true && $read( $write( [ 'blocks' => [ $looped ] ] ) )['blocks'][0] === $looped );

// The address of a link may lead to a page of the site and go on from it: the fill that holds the address of the page and a sub-path, whose fills are the loop's
$pageKey = '/_nino/webpage/artikel/uri';
// The address a link to a page with a sub-path is written with: the directory the site is served from, then the fill of the key that holds the address of the page - which is
// written without it - and the sub-path
$front = '[[/nino/dir]]';
$aimed = $front. '[['. $pageKey. ']]/[[.uri]]';
// What a call that is refused comes to: its message, so that a pin that fails says so and the suite goes on
$tried = static function( callable $do ): mixed {
	try {
		return $do();
	}
	catch( \Throwable $e ) {
		return $e->getMessage();
	}
};
check( 'the address of a link to a page with a sub-path is read as two things - the key that holds the address of the page and the sub-path, the fills of the element of a loop in it as they stand - and the page alone has a sub-path of nothing',
	$reader::address( $aimed ) === [ 'key' => $pageKey, 'postfix' => '/[[.uri]]' ] && $reader::address( $front. '[['. $pageKey. ']]/a/[[title]]?b=1#c' ) === [ 'key' => $pageKey, 'postfix' => '/a/[[title]]?b=1#c' ]
	&& $reader::address( $front. '[[/_nino/webpage/legal/imprint/uri]]' ) === [ 'key' => '/_nino/webpage/legal/imprint/uri', 'postfix' => '' ] );
check( '...and what is not such an address is none: a sub-path with a quote, a space, a backslash or a bracket that is no fill, a key that is no page, a fill that is left open, the key without its brackets, an address typed in, the directory not in front of the key, twice or after it',
	array_filter( [ $front. '[['. $pageKey. ']]/a"b', $front. '[['. $pageKey. ']]/a b', $front. '[['. $pageKey. ']]/a\\b', $front. '[['. $pageKey. ']]/a[b', $front. '[['. $pageKey. ']]/[[.uri]', $front. '[['. $pageKey. ']]/[[a b]]', $front. '[[/template/page-x/a/title]]/x', $front. '[[/_nino/webpage/artikel]]/x', '[['. $pageKey. ']]/x', $front. $front. '[['. $pageKey. ']]/x', '[['. $pageKey. ']]'. $front. '/x', $pageKey. '/x', $pageKey, '/artikel/[['. $pageKey. ']]', '/contact', '' ],
		static fn( string $value ): bool => $reader::address( $value ) !== null ) === [] );

$linked = $blank;
$linked['cols'][0]['stack'] = [ 'name' => 'stack', 'source' => '/services', 'attributes' => $registry['stacks']['stack']['defaults'] ];
$linked['cols'][0]['components'] = [
	[ 'name' => 'button', 'source' => '', 'text' => 'More', 'attributes' => array_replace( $registry['components']['button']['defaults'], [ 'href' => $aimed ] ) ] + array_fill_keys( $reader::SPACING, '' ),
	[ 'name' => 'title', 'source' => 'title', 'text' => null, 'attributes' => array_replace( $registry['components']['title']['defaults'], [ 'href' => $front. '[['. $pageKey. ']]/[[.id]]' ] ) ] + array_fill_keys( $reader::SPACING, '' ),
];
$linkedText = (string) $tried( static fn(): string => $write( [ 'blocks' => [ $linked ] ] ) );
check( 'a link to a page with a sub-path is written as the call has it: the fill of the key that holds the address of the page and the sub-path after it, in the attribute of a button and in the implicit href of a component that is a link - and nothing of the brackets is escaped or dropped',
	str_contains( $linkedText, "\t\t\t[button text=\"More\" href=\"[[/nino/dir]][[/_nino/webpage/artikel/uri]]/[[.uri]]\"]\n" ) === true && str_contains( $linkedText, "\t\t\t[title title href=\"[[/nino/dir]][[/_nino/webpage/artikel/uri]]/[[.id]]\"]\n" ) === true );
check( '...and it reads back as what was meant, node for node, and is written again byte for byte - the loop around the two calls is the loop it was', $read( $linkedText )['blocks'][0] === $linked && $tried( static fn(): string => $write( $read( $linkedText ) ) ) === $linkedText );

$plainAddress = $linked;
$plainAddress['cols'][0]['components'][0]['attributes']['href'] = $pageKey;
$plainText = (string) $tried( static fn(): string => $write( [ 'blocks' => [ $plainAddress ] ] ) );
check( '...the page alone is the key as it was: the call holds no brackets, and the key reads back as the address of the page', str_contains( $plainText, "[button text=\"More\" href=\"/_nino/webpage/artikel/uri\"]\n" ) === true
	&& $read( $plainText )['blocks'][0] === $plainAddress );

$refusedAddress = static function( string $href ) use ( $linked, $write ): bool {
	$model = $linked;
	$model['cols'][0]['components'][0]['attributes']['href'] = $href;
	try {
		$write( [ 'blocks' => [ $model ] ] );
	}
	catch( \UnexpectedValueException ) {
		return true;
	}
	return false;
};
check( 'a sub-path with a quote or a space, a fill that is left open and the brackets in any other attribute are refused as a value no call can carry: the Writer throws, and nothing of it reaches a file',
	$refusedAddress( $front. '[['. $pageKey. ']]/a"b' ) === true && $refusedAddress( $front. '[['. $pageKey. ']]/a b' ) === true && $refusedAddress( $front. '[['. $pageKey. ']]/[[.uri]' ) === true && $refusedAddress( $front. '[[/template/page-x/a/title]]/x' ) === true
	&& ( static function() use ( $linked, $write, $aimed ): bool {
		$model = $linked;
		$model['cols'][0]['components'][0]['attributes']['class'] = $aimed;
		try {
			$write( [ 'blocks' => [ $model ] ] );
		}
		catch( \UnexpectedValueException ) {
			return true;
		}
		return false;
	} )() === true );

$readAddress = static fn( string $call ): array => $read( $page( $section( $call ) ) )['blocks'][0];
check( 'the fill of a link in a value that is none of an address is no part of a call: the section is a block of html, as it was, with the reason of an argument the kernel would not read as it stands - in a fixed text, in the class, in a sub-path with a space',
	array_filter( [ '[button text="[[/nino/dir]][[/_nino/webpage/artikel/uri]]/x"]', '[button text="Go" class="[[/nino/dir]][[/_nino/webpage/artikel/uri]]"]', '[button text="Go" href="[[/nino/dir]][[/_nino/webpage/artikel/uri]]/a b"]', '[button text="Go" href="[[/nino/dir]][[/template/page-x/a/b]]/x"]' ],
		static fn( string $call ): bool => $readAddress( $call )['kind'] !== 'html' || ( $readAddress( $call )['reason']['code'] ?? '' ) !== 'call-arguments' ) === [] );
$twoCalls = $readAddress( "[button text=\"Go\" href=\"". $front. "[[". $pageKey. "]]/x\"]\n\t\t\t[spacer]" );
check( '...a link with such an address is one call, whose brackets are not its end: the call beside it is the next call, and the address is the attribute as it was written',
	$twoCalls['kind'] === 'section' && count( $twoCalls['cols'][0]['components'] ) === 2 && $twoCalls['cols'][0]['components'][0]['attributes']['href'] === $front. '[['. $pageKey. ']]/x' && $twoCalls['cols'][0]['components'][1]['name'] === 'spacer' );

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
// --- The radius of a surface -----------------------------------------------------------------------------------------------------------------
//
// The page has two strengths of radius, standard and alternative, and a default per kind of surface (the Design's, or the kernel's); an element takes the other strength with a
// class of its own, nino-radius or nino-radius-alt. The model keeps it as a setting where the class means something - the picture of a padded cover, what the row paints with the
// background inside, the ground or the picture of a column - and on a component that is a surface; anywhere else the class is a custom one: kept, not lost

check( 'the radius of a surface has two strengths - standard and alternative - a class for each, nino-radius and nino-radius-alt, and five kinds of surface the page has a default for: the cover, the row, the column, the card and the image - and no section, row or column has a radius until it is said',
	$reader::RADIUS === [ 'standard', 'alternative' ] && $reader::RADIUS_CLASS === [ 'standard' => 'nino-radius', 'alternative' => 'nino-radius-alt' ] && $reader::SURFACES === [ 'cover', 'row', 'column', 'card', 'image' ]
	&& $reader::sectionDefaults()['radius'] === '' && $reader::sectionDefaults()['rowRadius'] === '' && $reader::colDefaults()['radius'] === '' && $blank['settings']['radius'] === '' && $blank['settings']['rowRadius'] === '' && $blank['cols'][0]['radius'] === '' );

// The picture of a padded cover
$coverOf = static function( array $over = [] ) use ( $blank ): array {
	$model = $blank;
	$model['settings'] = array_replace( $blank['settings'], [ 'cover' => 60, 'padded' => true ], $over );
	return $model;
};
$radiusWrong = [];

foreach( $reader::RADIUS as $word ) {

	$made = $coverOf( [ 'radius' => $word ] );
	$text = $write( [ 'blocks' => [ $made ] ] );

	if( str_contains( $text, '<section id="a" class="nino-section nino-cover nino-cover--padded '. $reader::RADIUS_CLASS[$word]. '" data-cover-height="60">' ) === false || $read( $text )['blocks'][0] !== $made || $write( $read( $text ) ) !== $text )
		$radiusWrong[] = 'the padded cover '. $word;
}

$radiusOrdered = $coverOf( [ 'whitespace' => 'more', 'mt' => '1', 'pb' => '2', 'radius' => 'standard', 'text' => 'center', 'custom' => 'my-cover' ] );
$radiusText = $write( [ 'blocks' => [ $radiusOrdered ] ] );
check( 'a padded cover has a radius of its own - the picture, the box of a parallax and the scrim of a dimmed cover take it - read from nino-radius or nino-radius-alt and written back after the spacing, before the text and the custom classes: both words'
	. ( $radiusWrong === [] ? '' : ' - '. implode( ', ', $radiusWrong ) ),
	$radiusWrong === [] && str_contains( $radiusText, 'class="nino-section nino-cover nino-cover--padded nino-whitespace--more nino-mt-1 nino-pb-2 nino-radius nino-text-center my-cover"' ) === true && $read( $radiusText )['blocks'][0] === $radiusOrdered );

$twiceRadius = $fileRead( 'nino-section nino-cover nino-cover--padded nino-radius nino-radius-alt' );
$idleSection = $fileRead( 'nino-section nino-radius-alt' );
$idleCover = $fileRead( 'nino-section nino-cover nino-radius' );
check( '...a second class of the kind is a class of its own, kept - and on a section that is no padded cover, a cover that is not padded among them, both are idle and stay custom classes',
	$twiceRadius['settings']['radius'] === 'standard' && $twiceRadius['settings']['custom'] === 'nino-radius-alt' && $fileSame( 'nino-section nino-cover nino-cover--padded nino-radius nino-radius-alt' ) === false
	&& $idleSection['settings']['radius'] === '' && $idleSection['settings']['custom'] === 'nino-radius-alt' && $fileSame( 'nino-section nino-radius-alt' ) === true
	&& $idleCover['settings']['radius'] === '' && $idleCover['settings']['cover'] === 90 && $idleCover['settings']['custom'] === 'nino-radius' );

check( '...and the Writer writes none for a section that is not a padded cover - the setting waits in the model, and no class of nothing reaches the file',
	str_contains( $write( [ 'blocks' => [ $coverOf( [ 'padded' => false, 'radius' => 'alternative' ] ) ] ] ), 'nino-radius' ) === false
	&& str_contains( $write( [ 'blocks' => [ $coverOf( [ 'cover' => null, 'padded' => false, 'radius' => 'standard' ] ) ] ] ), 'nino-radius' ) === false
	&& str_contains( $write( [ 'blocks' => [ $coverOf( [ 'cover' => null, 'parallax' => true, 'radius' => 'standard' ] ) ] ] ), 'nino-cover--padded nino-radius"' ) === true );

// What the row paints with the background inside
$rowRadiusWrong = [];

foreach( $reader::RADIUS as $word ) {

	$rowClass = 'nino-grid-row nino-grid-row--narrow nino-grid-row--left nino-grid-row--tint nino-grid-middle nino-pl-1 nino-pr-2 nino-pt-3 nino-pb-4 '. $reader::RADIUS_CLASS[$word]. ' my-row';
	$rowSet = $fileRead( 'nino-section', $rowClass )['settings'];

	if( $rowSet['inner'] !== true || $rowSet['rowRadius'] !== $word || $rowSet['color'] !== 'tint' || $rowSet['rowPb'] !== '4' || $rowSet['rowCustom'] !== 'my-row' || $fileSame( 'nino-section', $rowClass ) === false )
		$rowRadiusWrong[] = 'the row '. $word;
}

$rowPicture = $fileRead( 'nino-section', 'nino-grid-row nino-radius', false, true );
$rowIdle = $fileRead( 'nino-section', 'nino-grid-row nino-radius-alt' );
$rowIdleSet = $blank;
$rowIdleSet['settings'] = [ 'inner' => true, 'rowRadius' => 'alternative' ] + $blank['settings'];
$rowSectionGround = $blank;
$rowSectionGround['settings'] = [ 'color' => 'tint', 'rowRadius' => 'alternative' ] + $blank['settings'];
check( 'the row has a radius of its own where it paints - a ground or a picture, which it has with the background inside - read from the class after its padding, before its custom classes, and written back byte for byte: both words'
	. ( $rowRadiusWrong === [] ? '' : ' - '. implode( ', ', $rowRadiusWrong ) ),
	$rowRadiusWrong === [] && $rowPicture['settings']['inner'] === true && $rowPicture['settings']['rowRadius'] === 'standard' && $rowPicture['settings']['rowCustom'] === '' && $rowPicture['background'] !== null && $fileSame( 'nino-section', 'nino-grid-row nino-radius', false, true ) === true );
check( '...on a row that paints nothing both classes are idle and stay custom ones - and the Writer writes none for a row that has no ground and no picture, nor where the colour is the section\'s',
	$rowIdle['settings']['rowRadius'] === '' && $rowIdle['settings']['rowCustom'] === 'nino-radius-alt' && $fileSame( 'nino-section', 'nino-grid-row nino-radius-alt' ) === true
	&& str_contains( $write( [ 'blocks' => [ $rowIdleSet ] ] ), 'nino-radius' ) === false && str_contains( $write( [ 'blocks' => [ $rowSectionGround ] ] ), 'nino-radius' ) === false );

// The ground or the picture of a column
$colRadiusWrong = [];

foreach( $reader::RADIUS as $word ) {

	$colSource = $groundOf( 'nino-grid-100 nino-grid--tint nino-p-2 '. $reader::RADIUS_CLASS[$word]. ' nino-vpa' );
	$colSet = $columnOf( $colSource );

	if( $colSet['radius'] !== $word || $colSet['color'] !== 'tint' || [ $colSet['pt'], $colSet['pb'], $colSet['pl'], $colSet['pr'] ] !== [ '2', '2', '2', '2' ] || $colSet['vpa'] !== '' || $colSet['custom'] !== '' || $write( $read( $colSource ) ) !== $inWrap( $colSource ) )
		$colRadiusWrong[] = 'the column '. $word;
}

$colPicture = $columnOf( $groundOf( 'nino-grid-100 nino-radius-alt', $photo. "\n\t\t\t[title /template/page-x/a/title]" ) );
$colIdle = $columnOf( $groundOf( 'nino-grid-100 nino-radius-alt' ) );
$colIdleSet = $blank;
$colIdleSet['cols'][0]['radius'] = 'standard';
check( 'a column has a radius of its own where it paints - a ground or a picture - read from the class after its spacing, before its animation, and written back byte for byte: both words'
	. ( $colRadiusWrong === [] ? '' : ' - '. implode( ', ', $colRadiusWrong ) ),
	$colRadiusWrong === [] && $colPicture['radius'] === 'alternative' && $colPicture['color'] === '' && $colPicture['background'] !== null && $colPicture['custom'] === '' );
check( '...on a column that paints nothing both classes are idle and stay custom ones - and the Writer writes none for it',
	$colIdle['radius'] === '' && $colIdle['custom'] === 'nino-radius-alt' && $write( $read( $groundOf( 'nino-grid-100 nino-radius-alt' ) ) ) === $inWrap( $groundOf( 'nino-grid-100 nino-radius-alt' ) )
	&& str_contains( $write( [ 'blocks' => [ $colIdleSet ] ] ), 'nino-radius' ) === false );

// A component that is a surface: its radius is apart from its classes, and the first class of its call
$cardCall = static fn( string $class ): string => $section( "[card". $class. "]\n\t\t\t\t[card-title /template/page-x/a/title]\n\t\t\t[/card]" );
$pictureCall = static fn( string $class ): string => $section( '[image /template/page-x/a/photo'. $class. ']' );
$nodeOf = static fn( string $source ): array => $read( $page( $source ) )['blocks'][0]['cols'][0]['components'][0];
$surfaceWrong = [];

foreach( [ 'card' => $cardCall, 'image' => $pictureCall ] as $kind => $call )
	foreach( $reader::RADIUS as $word ) {

		$bare = $call( ' class="'. $reader::RADIUS_CLASS[$word]. '"' );
		$both = $call( ' class="'. $reader::RADIUS_CLASS[$word]. ' my-class"' );

		if( $nodeOf( $bare )['radius'] !== $word || $nodeOf( $bare )['attributes']['class'] !== '' || $nodeOf( $both )['radius'] !== $word || $nodeOf( $both )['attributes']['class'] !== 'my-class'
			|| $write( $read( $page( $bare ) ) ) !== $inWrap( $page( $bare ) ) || $write( $read( $page( $both ) ) ) !== $inWrap( $page( $both ) ) )
			$surfaceWrong[] = $kind. ' '. $word;
	}

$cardNone = $nodeOf( $cardCall( '' ) );
check( 'a card and a picture have a radius of their own: the class of the call, nino-radius or nino-radius-alt, is taken out of the class attribute into the setting radius of the component and put back as the first class, before the editor\'s own, byte for byte - both words of each'
	. ( $surfaceWrong === [] ? '' : ' - '. implode( ', ', $surfaceWrong ) ),
	$surfaceWrong === [] && $cardNone['radius'] === '' && $cardNone['attributes']['class'] === '' && array_key_exists( 'radius', $cardNone['components'][0] ) === false
	&& $write( $read( $page( $cardCall( ' class="my-class nino-radius-alt"' ) ) ) ) === $inWrap( $page( $cardCall( ' class="nino-radius-alt my-class"' ) ) ) );

$twoPicture = $nodeOf( $pictureCall( ' class="nino-radius nino-radius-alt"' ) );
$plainTitle = $nodeOf( $section( '[title /template/page-x/a/title class="nino-radius-alt"]' ) );
check( '...a second class of the kind is a class of its own, and a component that is no surface has none of this: its class stays a class',
	$twoPicture['radius'] === 'standard' && $twoPicture['attributes']['class'] === 'nino-radius-alt'
	&& array_key_exists( 'radius', $plainTitle ) === false && $plainTitle['attributes']['class'] === 'nino-radius-alt' && $write( $read( $page( $section( '[title /template/page-x/a/title class="nino-radius-alt"]' ) ) ) ) === $inWrap( $page( $section( '[title /template/page-x/a/title class="nino-radius-alt"]' ) ) ) );

// The picture behind a section or a column is its background, and has no radius of its own: the section is not read as one
$backgroundRadius = $read( $page( $groundOf( 'nino-grid-100', "<div class=\"nino-grid-bg\">[image /template/page-x/a/photo alt=\"\" class=\"nino-radius\"]</div>" ) ) );
check( '...the picture behind a column is a background and has no radius: the section that gives it one is not read as a section, it is a foreign block with its reason',
	$backgroundRadius['blocks'][0]['kind'] === 'html' && ( $backgroundRadius['blocks'][0]['reason']['code'] ?? '' ) === 'background-image' );

// The hover of a picture: a component whose schema says that its source is an image - the frame of [image] and the picture of a card alike, found by the schema and not by the name - has
// the class nino-hover apart from its classes, the first of them as the radius of a surface is, and the Writer puts it back after the radius and before the sides of the spacing
$pictures		= array_keys( array_filter( $registry['components'], static fn( array $schema ): bool => ( $schema['source'] ?? '' ) === 'image' ) );
$holderOf		= static function( string $name ) use ( $registry, $reader ): string {

	foreach( $registry['components'] as $holder => $schema )
		if( $reader::holds( $schema ) === true && in_array( $schema['holds'], (array) ( $registry['components'][$name]['in'] ?? [] ), true ) === true )
			return (string) $holder;

	return '';
};
$hoverCall	= static function( string $name, string $class ) use ( $holderOf, $section ): string {

	$call		= '['. $name. ' /template/page-x/a/photo'. ( $class === '' ? '' : ' class="'. $class. '"' ). ']';
	$holder	= $holderOf( $name );

	return $section( $holder === '' ? $call : '['. $holder. "]\n\t\t\t\t". $call. "\n\t\t\t[/". $holder. ']' );
};
$hoverNode	= static function( string $name, string $class ) use ( $holderOf, $read, $page, $hoverCall ): array {

	$held = $read( $page( $hoverCall( $name, $class ) ) )['blocks'][0]['cols'][0]['components'][0];

	return $holderOf( $name ) === '' ? $held : $held['components'][0];
};
$hoverWrong	= [];

foreach( $pictures as $hoverName )
	foreach( [ 'nino-hover' => [ 'on', '' ], 'nino-hover my-class' => [ 'on', 'my-class' ], 'my-class nino-hover' => [ 'on', 'my-class' ], 'my-class nino-hover other' => [ 'on', 'my-class other' ], 'my-class' => [ '', 'my-class' ], 'my-class  other' => [ '', 'my-class  other' ], '' => [ '', '' ] ] as $hoverClass => [ $hoverWord, $hoverRest ] ) {

		$hoverRead = $hoverNode( $hoverName, $hoverClass );

		if( ( $hoverRead['hover'] ?? null ) !== $hoverWord || $hoverRead['attributes']['class'] !== $hoverRest )
			$hoverWrong[] = $hoverName. ' "'. $hoverClass. '"';

		// What the file says in the canonical order is written back as it was, a hover after another class is written first
		$hoverCanonical = $hoverWord === 'on' ? $hoverCall( $hoverName, trim( 'nino-hover '. $hoverRest ) ) : $hoverCall( $hoverName, $hoverClass );

		if( $write( $read( $page( $hoverCall( $hoverName, $hoverClass ) ) ) ) !== $inWrap( $page( $hoverCanonical ) ) )
			$hoverWrong[] = $hoverName. ' "'. $hoverClass. '" written';
	}

check( 'the hover of the page is a setting of every component whose schema says that its source is an image - [image] and the picture of a card among them: the first class nino-hover of the call is taken out of the class attribute into the setting hover, on, and put back as the first class of the call; a call without it has the setting empty and keeps its class attribute byte for byte'
	. ( $hoverWrong === [] ? '' : ' - '. implode( ', ', $hoverWrong ) ),
	$hoverWrong === [] && $reader::HOVER === 'on' && $reader::HOVER_CLASS === 'nino-hover' && array_diff( [ 'image', 'card-image' ], $pictures ) === [] );

$hoverText = '';

foreach( $pictures as $hoverName ) {

	$hoverSurface	= $reader::surface( $registry['components'][$hoverName], $registry['components'] ) !== '';
	$hoverMixed		= 'my nino-pr-3 nino-hover'. ( $hoverSurface === true ? ' nino-radius' : '' ). ' nino-mt-1';
	$hoverTwice		= $hoverNode( $hoverName, 'nino-hover nino-hover' );
	$hoverOrder		= $hoverNode( $hoverName, $hoverMixed );

	if( $hoverTwice['hover'] !== 'on' || $hoverTwice['attributes']['class'] !== 'nino-hover' || $write( $read( $page( $hoverCall( $hoverName, 'nino-hover nino-hover' ) ) ) ) !== $inWrap( $page( $hoverCall( $hoverName, 'nino-hover nino-hover' ) ) ) )
		$hoverText .= ' '. $hoverName. ' twice';

	if( $hoverOrder['hover'] !== 'on' || $hoverOrder['mt'] !== '1' || $hoverOrder['pr'] !== '3' || ( $hoverOrder['radius'] ?? '' ) !== ( $hoverSurface === true ? 'standard' : '' ) || $hoverOrder['attributes']['class'] !== 'my'
		|| $write( $read( $page( $hoverCall( $hoverName, $hoverMixed ) ) ) ) !== $inWrap( $page( $hoverCall( $hoverName, ( $hoverSurface === true ? 'nino-radius ' : '' ). 'nino-hover nino-mt-1 nino-pr-3 my' ) ) ) )
		$hoverText .= ' '. $hoverName. ' ordered';
}

check( '...a second nino-hover is a class of the editor\'s and stays, and the call is written with the hover after the radius of the picture and before the sides of its spacing, whatever order the file has them in, then the classes of its own'
	. ( $hoverText === '' ? '' : ' - wrong:'. $hoverText ), $hoverText === '' );

$hoverTitle = $read( $page( $section( '[title /template/page-x/a/title class="nino-hover"]' ) ) )['blocks'][0]['cols'][0]['components'][0];
$hoverCard = $read( $page( $cardCall( ' class="nino-hover"' ) ) )['blocks'][0]['cols'][0]['components'][0];
check( '...a component that is no picture has no setting hover and the class stays a class of its own - the card that holds a picture does not take it either',
	array_key_exists( 'hover', $hoverTitle ) === false && $hoverTitle['attributes']['class'] === 'nino-hover' && array_key_exists( 'hover', $hoverCard ) === false && $hoverCard['attributes']['class'] === 'nino-hover'
	&& $write( $read( $page( $section( '[title /template/page-x/a/title class="nino-hover"]' ) ) ) ) === $inWrap( $page( $section( '[title /template/page-x/a/title class="nino-hover"]' ) ) ) );

$withoutPictures = [ 'components' => array_diff_key( $registry['components'], array_flip( $pictures ) ), 'stacks' => $registry['stacks'] ];
check( '...and a source says its hover behind a class by the calls of the pictures and of no other component: a picture does, a title and a card do not for the same class, nor does a picture for a registry that has none',
	$reader::hoverBehind( $pictureCall( ' class="my-class nino-hover"' ), $registry ) === true && $reader::hoverBehind( $section( '[title /template/page-x/a/title class="my-class nino-hover"]' ), $registry ) === false
	&& $reader::hoverBehind( $cardCall( ' class="my-class nino-hover"' ), $registry ) === false && $reader::hoverBehind( $pictureCall( ' class="my-class nino-hover"' ), $withoutPictures ) === false );

$hoverBackground = $read( $page( $groundOf( 'nino-grid-100', "<div class=\"nino-grid-bg\">[image /template/page-x/a/photo alt=\"\" class=\"nino-hover\"]</div>" ) ) );
check( '...and the picture behind a column is a background with no hover to give: the section that gives it one is not read as a section, it is a foreign block with its reason',
	$hoverBackground['blocks'][0]['kind'] === 'html' && ( $hoverBackground['blocks'][0]['reason']['code'] ?? '' ) === 'background-image' );

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
	&& $registryAnswer['components']['title']['label'] === 'Title (Section)' && $registryAnswer['components']['title']['attributes']['tag']['label'] === 'HTML tag'
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
check( '...a component says where it belongs (in) and what it draws (draws), as the kernel registered it: the answer carries each of the two - and what it holds (holds), whether it is a link (link), who registered it (by) and the group it is offered under (group) - exactly where the kernel\'s entry has it, as it has it, and one that says none of them carries none; the label is the kernel\'s, in the language of the workbench - and a loop says none of the six',
	array_filter( $kernelComponents, static fn( array $schema, string $name ): bool => array_filter( [ 'in', 'draws', 'holds', 'link', 'by', 'group' ], static fn( string $key ): bool => array_key_exists( $key, $registryAnswer['components'][$name] ) !== array_key_exists( $key, $schema )
		|| ( $registryAnswer['components'][$name][$key] ?? null ) !== ( $schema[$key] ?? null ) ) !== [] || $registryAnswer['components'][$name]['label'] !== \Nino\Features::localized( $schema['label'], 'en_US' ), ARRAY_FILTER_USE_BOTH ) === []
	&& array_filter( $kernelComponents, static fn( array $schema ): bool => isset( $schema['in'] ) === true ) !== [] && array_filter( $kernelComponents, static fn( array $schema ): bool => isset( $schema['in'] ) === false ) !== []
	&& array_filter( $registryAnswer['stacks'], static fn( array $schema ): bool => array_key_exists( 'in', $schema ) === true || array_key_exists( 'draws', $schema ) === true || array_key_exists( 'holds', $schema ) === true
		|| array_key_exists( 'link', $schema ) === true || array_key_exists( 'by', $schema ) === true || array_key_exists( 'group', $schema ) === true ) === [] );
check( '...a component says what it holds (holds), the place of the components it takes as its children: the card holds the place card and says where it is offered and what it draws - the three arrive as the kernel has them, which the check above compares, and no other component carries holds, nor does a loop',
	array_key_exists( 'in', $registryAnswer['components']['card'] ) === true && array_key_exists( 'draws', $registryAnswer['components']['card'] ) === true && array_key_exists( 'holds', $registryAnswer['components']['card'] ) === true
	&& $registryAnswer['components']['card']['source'] === 'content' && array_keys( $registryAnswer['components']['card']['attributes'] ) === array_keys( $kernelComponents['card']['attributes'] ) && $kernelComponents['card']['attributes'] !== []
	&& array_keys( array_filter( $registryAnswer['components'], static fn( array $schema ): bool => array_key_exists( 'holds', $schema ) === true ) ) === [ 'card' ] && array_filter( $registryAnswer['stacks'], static fn( array $schema ): bool => array_key_exists( 'holds', $schema ) === true || array_key_exists( 'in', $schema ) === true ) === []
	&& array_key_exists( 'card', $registryAnswer['stacks']['stack']['defaults'] ) === false && array_key_exists( 'card', $registryAnswer['stacks']['filter']['defaults'] ) === false );
check( '...a component says the icon it is shown by (icon), as the kernel registered it: the parts of a 24 by 24 stroke icon, each a tag and a map of its attributes, all of them strings - every component of the kernel has one, a loop has none, and the key stands right after the preview the component is drawn as',
	array_filter( $registryAnswer['components'], static fn( array $schema ): bool => is_array( $schema['icon'] ?? null ) === true && array_is_list( $schema['icon'] ) === true && $schema['icon'] !== []
		&& array_filter( $schema['icon'], static fn( mixed $part ): bool => is_array( $part ) === true && array_is_list( $part ) === true && count( $part ) === 2 && is_string( $part[0] ) === true && is_array( $part[1] ) === true && $part[1] !== [] && array_filter( $part[1], 'is_string' ) === $part[1] ) === $schema['icon'] ) === $registryAnswer['components']
	&& array_filter( $registryAnswer['stacks'], static fn( array $schema ): bool => array_key_exists( 'icon', $schema ) === true ) === []
	&& array_search( 'icon', array_keys( $registryAnswer['components']['title'] ), true ) === array_search( 'preview', array_keys( $registryAnswer['components']['title'] ), true ) + 1
	&& $registryAnswer['components']['title']['icon'] === \Nino\Modules\Builder\Document::schemas( $appData )['components']['title']['icon'] );
check( '...and the entry of the card is the entry of the registry of the Components module but for its words, which are in the language of the workbench: the panel is handed nothing of its own making',
	array_diff_key( $registryAnswer['components']['card'], [ 'label' => 1, 'labels' => 1, 'attributes' => 1 ] ) === array_diff_key( \Nino\Modules\Builder\Document::schemas( $appData )['components']['card'], [ 'label' => 1, 'labels' => 1, 'attributes' => 1 ] ) );
$siteLocales = \Nino\Locales::getAvailableLocales( $appData );
check( '...and it names every component in every language of the site (labels): the label the kernel registered for that language, else the one it has for the language of the workbench - so that the placeholder of a new component can be written in each - and a loop has none',
	count( $siteLocales ) === 2 && array_filter( $registryAnswer['components'], static fn( array $schema, string $name ): bool => array_keys( $schema['labels'] ?? [] ) !== $siteLocales
		|| array_filter( $siteLocales, static fn( string $locale ): bool => $schema['labels'][$locale] !== \Nino\Features::localized( $kernelComponents[$name]['label'], $locale ) ) !== [], ARRAY_FILTER_USE_BOTH ) === []
	&& array_filter( $registryAnswer['components'], static fn( array $schema ): bool => $schema['labels'][\Nino\Admin\Admin::sessionLocale( $appData )] !== $schema['label'] ) === []
	&& array_filter( $registryAnswer['stacks'], static fn( array $schema ): bool => array_key_exists( 'labels', $schema ) === true ) === [] );
check( '...the answer lists the groups a tool offers the kernel\'s and a project\'s components in, in the order the kernel declares them - the words of its own list, which the script reads from the answer and types nowhere',
	$registryAnswer['groups'] === \Nino\Modules\Components::GROUPS && $registryAnswer['groups'] !== [] && array_is_list( $registryAnswer['groups'] ) === true && array_filter( $registryAnswer['groups'], static fn( mixed $word ): bool => is_string( $word ) === false || preg_match( '/^[a-z]+$/', $word ) !== 1 ) === [] );
check( '...the components, the stacks, the groups and the strength each kind of surface takes the panel\'s script test reads (fixtures/registry.json) are written from what the registry says: the file is there, is json, and holds the four keys, each with every component, every stack and every group the registry has',
	$fixtureWritten( 'registry.json', [ 'components' => $registryAnswer['components'], 'stacks' => $registryAnswer['stacks'], 'groups' => $registryAnswer['groups'], 'radii' => $registryAnswer['radii'] ] ) === true && array_keys( $fixtureRead( 'registry.json' ) ?? [] ) === [ 'components', 'stacks', 'groups', 'radii' ]
	&& array_keys( $fixtureRead( 'registry.json' )['components'] ) === array_keys( $registryAnswer['components'] ) && array_keys( $fixtureRead( 'registry.json' )['stacks'] ) === array_keys( $registryAnswer['stacks'] )
	&& $fixtureRead( 'registry.json' ) === json_decode( (string) json_encode( [ 'components' => $registryAnswer['components'], 'stacks' => $registryAnswer['stacks'], 'groups' => $registryAnswer['groups'], 'radii' => $registryAnswer['radii'] ] ), true ) );

/*	The strength each kind of surface takes where an element names none is the site's stylesheets' to say, by a marker at their :root - the workbench loads none of them, so the
	registry reads the bundle's sources and tells the panel: the kernel's own say standard for every kind, and the last declaration in the bundle is the one in force	*/
$radiiSheet = '/assets/radius-test.css';
check( 'the registry tells the panel the strength each kind of surface takes by default: the five kinds, in order, each one of the two strengths - the kernel\'s own stylesheet says standard for all of them',
	array_keys( $registryAnswer['radii'] ) === $reader::SURFACES && array_values( array_unique( array_values( $registryAnswer['radii'] ) ) ) === [ 'standard' ]
	&& \Nino\Modules\Builder\Document::radii( $appData ) === $registryAnswer['radii'] );

\Nino\Filesystem::putFileContent( $appData, $radiiSheet, "/* --radius-image-default: alternative; */\n:root {\n\t--radius-card-default: standard;\n\t--radius-row-default: alternative;\n\t--radius-column-default: sideways;\n\t--radius-nothing-default: alternative;\n}\n:root { --radius-card-default: alternative }\n" );
\Nino\Html::addAsset( $appData, '/.cache/style.css', $radiiSheet );
$radiiProject = \Nino\Modules\Builder\Document::radii( $appData );
$appData['/nino/html/assets']['/.cache/style.css'] = array_values( array_diff( $appData['/nino/html/assets']['/.cache/style.css'], [ $radiiSheet ] ) );
@unlink( \Nino\Filesystem::path( $appData, $radiiSheet ) );

check( '...a stylesheet of the project that declares the markers is heard, and the last declaration in the bundle is the one in force: a marker in a comment is none, a word that is none of the two and a kind the page does not have are ignored',
	$radiiProject === [ 'cover' => 'standard', 'row' => 'alternative', 'column' => 'standard', 'card' => 'alternative', 'image' => 'standard' ]
	&& \Nino\Modules\Builder\Document::radii( $appData ) === $registryAnswer['radii'] );
check( '...the components stand in the order the kernel registers them, which is the order a column offers them, and the answer keeps it the way the panel reads it: through json, as the kernel has them',
	array_keys( (array) json_decode( (string) json_encode( $registryAnswer['components'] ), true ) ) === array_keys( $kernelComponents ) );
$featured = $appData;
foreach( [ 'zulu', 'alpha' ] as $feature )
	\Nino\Modules\Components::addComponent( $featured, $feature, static fn( array &$appData, array $args ): string => '', [ 'label' => [ 'en_US' => ucfirst( $feature ) ], 'source' => 'none', 'by' => 'hello' ] );
check( '...a component a feature registers stands after the kernel\'s, in the order it registered them and not in the alphabet\'s', array_slice( array_keys( (array) json_decode( (string) json_encode( \Nino\Modules\Builder\Document::registry( $featured )['components'] ), true ) ), -3 ) === [ array_key_last( $kernelComponents ), 'zulu', 'alpha' ]
	&& array_keys( $registryAnswer['components'] ) === array_keys( \Nino\Modules\Builder\Document::registry( $appData )['components'] ) );
$byFeature = $appData;
\Nino\Modules\Components::addComponent( $byFeature, 'zulu', static fn( array &$appData, array $args ): string => '', [ 'label' => [ 'en_US' => 'Zulu' ], 'source' => 'none', 'by' => 'no-such-feature' ] );
\Nino\Modules\Components::addComponent( $byFeature, 'alpha', static fn( array &$appData, array $args ): string => '', [ 'label' => [ 'en_US' => 'Alpha' ], 'source' => 'none', 'by' => 'builder' ] );
$byAnswer = \Nino\Modules\Builder\Document::registry( $byFeature );
check( '...the answer names the features that registered a component, by their key, in the language of the workbench - the kernel\'s own components none, a feature this project has by the name of its manifest, one it has not by its key - so that the panel can say whose a component is - and what a feature registered has no group, which is the panel\'s to say: it stands under the feature',
	$registryAnswer['features'] === [] && array_filter( $registryAnswer['components'], static fn( array $schema ): bool => ( $schema['by'] ?? '' ) !== 'nino' ) === [] && $byAnswer['components']['zulu']['by'] === 'no-such-feature' && $byAnswer['components']['alpha']['by'] === 'builder'
	&& $byAnswer['features'] === [ 'no-such-feature' => 'no-such-feature', 'builder' => \Nino\Features::localized( \Nino\Features::get( $byFeature, 'builder' )['name'] ?? '', 'en_US' ) ] && $byAnswer['features']['builder'] !== ''
	&& array_key_exists( 'group', $byAnswer['components']['zulu'] ) === false && array_key_exists( 'group', $byAnswer['components']['alpha'] ) === false );
$iconed = $appData;
\Nino\Modules\Components::addComponent( $iconed, 'zulu', static fn( array &$appData, array $args ): string => '', [ 'label' => [ 'en_US' => 'Zulu' ], 'source' => 'none', 'by' => 'hello', 'icon' => [ [ 'circle', [ 'cx' => '12', 'cy' => '12', 'r' => '9' ] ], [ 'path', [ 'd' => 'M12 7v5l3 3' ] ] ] ] );
\Nino\Modules\Components::addComponent( $iconed, 'alpha', static fn( array &$appData, array $args ): string => '', [ 'label' => [ 'en_US' => 'Alpha' ], 'source' => 'none', 'by' => 'hello' ] );
$iconedAnswer = \Nino\Modules\Builder\Document::registry( $iconed )['components'];
check( '...a component a feature registers has the icon it registered, its parts as the kernel has them, and one that registers none has no icon at all - the panel gives it a puzzle of its own - and the components of the kernel are as they were',
	$iconedAnswer['zulu']['icon'] === [ [ 'circle', [ 'cx' => '12', 'cy' => '12', 'r' => '9' ] ], [ 'path', [ 'd' => 'M12 7v5l3 3' ] ] ] && array_key_exists( 'icon', $iconedAnswer['alpha'] ) === false
	&& array_slice( $iconedAnswer, 0, count( $registryAnswer['components'] ) ) === $registryAnswer['components'] );

// An attribute whose options a method of a feature answers: the registry of the kernel hands out the values as the options and the words beside them, and the Builder
// hands the panel the words in the language of the workbench - whichever component or feature it is, it knows none by name
// Where the kernel has no such attribute (a Nino before the Components module's options from a method) there is nothing for the panel to hand on, and nothing to pin
if( method_exists( \Nino\Modules\Components::class, 'registered' ) === false )
	echo "  note - this Nino's Components module takes no options from a method of a feature: the words of the options and the value a call holds outside them are not run here\n";
else {
	if( class_exists( 'BuilderSmokeChoices' ) === false ) {
		class BuilderSmokeChoices {
			public static function pick( array &$appData, string $attribute ): array {
				return [ '' => [ 'en_US' => 'First', 'de_DE' => 'Erstes' ], 'b' => 'Beta', '2' => [ 'de_DE' => 'Zwei' ], 'z' => [ 'en_US' => 'Zed', 'de_DE' => 'Zett' ] ];
			}
			public static function tones( array &$appData, string $attribute ): array {
				return [ '' => 'Plain', 'warm' => 'Warm' ];
			}
		}
	}
	$chosen = $appData;
	\Nino\Modules\Components::addComponent( $chosen, 'chooser', static fn( array &$appData, array $args ): string => '', [
		'label' => [ 'en_US' => 'Chooser' ], 'source' => 'none', 'by' => 'hello',
		'attributes' => [
			'pick' => [ 'type' => 'select', 'options' => '\BuilderSmokeChoices::pick', 'default' => '', 'label' => 'Pick' ],
			'plain' => [ 'type' => 'select', 'options' => [ '', 'x' ], 'default' => '', 'label' => 'Plain' ],
			'tone' => [ 'type' => 'variant', 'options' => '\BuilderSmokeChoices::tones', 'default' => '', 'prefix' => 'chooser--', 'label' => 'Tone' ],
		],
	] );
	ninoWarnings();
	$choiceIn = static function( string $locale ) use ( $chosen ): array {
		$session = $chosen;
		\Nino\Runtime::setSessionValue( $session, './admin/locale', $locale );
		return \Nino\Modules\Builder\Document::registry( $session )['components']['chooser']['attributes'];
	};
	$choiceEnglish	= $choiceIn( 'en_US' );
	$choiceGerman		= $choiceIn( 'de_DE' );
	check( 'an attribute that takes its options from a method of its feature has them in the answer as the values, and beside them optionLabels, value => words in the language of the workbench: the label of a locale map as the label of the attribute is read: the language, else English, else the first',
		$choiceEnglish['pick']['options'] === [ '', 'b', '2', 'z' ] && $choiceEnglish['pick']['optionLabels'] === [ '' => 'First', 'b' => 'Beta', 2 => 'Zwei', 'z' => 'Zed' ]
		&& $choiceGerman['pick']['options'] === $choiceEnglish['pick']['options'] && $choiceGerman['pick']['optionLabels'] === [ '' => 'Erstes', 'b' => 'Beta', 2 => 'Zwei', 'z' => 'Zett' ] );
	check( '...each label is one string by then - the panel reads no map -, and an attribute with a list of its own has no optionLabels',
		array_filter( $choiceGerman['pick']['optionLabels'], 'is_string' ) === $choiceGerman['pick']['optionLabels'] && array_key_exists( 'optionLabels', $choiceEnglish['plain'] ) === false && $choiceEnglish['plain']['options'] === [ '', 'x' ] );
	check( '...the method is no part of the answer', array_filter( array_merge( $choiceEnglish['pick'], $choiceGerman['pick'] ), static fn( mixed $value ): bool => is_string( $value ) === true && str_contains( $value, '::' ) ) === [] );

	// What the call holds outside the list a method answered - the form that was deleted since - is the project's data, not a mistake of the page: the Reader keeps it, the Writer writes it
	// as it is, and a value outside a list written in the manifest is still refused
	$chosenRegistry	= \Nino\Modules\Builder\Document::schemas( $chosen );
	$staleFile			= $section( '[chooser pick="gone" plain="x"]' );
	[ $staleBlock, $staleWhy ] = \Nino\Modules\Builder\Reader::readSection( $staleFile, $chosenRegistry );
	check( 'a value outside the options a method of the feature answered is read and kept as the call has it, and written again as it was',
		$staleWhy === null && is_array( $staleBlock ) === true && str_contains( \Nino\Modules\Builder\Writer::block( $staleBlock, $chosenRegistry ), '[chooser pick="gone" plain="x"]' ) === true
		&& \Nino\Modules\Builder\Reader::readSection( $section( '[chooser pick="b"]' ), $chosenRegistry )[1] === null );
	check( '...a variant keeps it only where it is a word, since a variant is written as the end of a class: the select keeps any string, the variant lower case letters and digits with hyphens between them',
		\Nino\Modules\Builder\Reader::readSection( $section( '[chooser tone="gone-one"]' ), $chosenRegistry )[1] === null && \Nino\Modules\Builder\Reader::readSection( $section( '[chooser tone="warm"]' ), $chosenRegistry )[1] === null
		&& \Nino\Modules\Builder\Reader::readSection( $section( '[chooser tone="Not a word"]' ), $chosenRegistry )[1]['code'] === 'attribute-value' && \Nino\Modules\Builder\Reader::readSection( $section( '[chooser tone="two--hyphens"]' ), $chosenRegistry )[1]['code'] === 'attribute-value'
		&& \Nino\Modules\Builder\Reader::readSection( $section( '[chooser pick="Not a word"]' ), $chosenRegistry )[1] === null );
	check( '...and a value outside a list the manifest writes is refused as before',
		\Nino\Modules\Builder\Reader::readSection( $section( '[chooser plain="y"]' ), $chosenRegistry )[1]['code'] === 'attribute-value' );
}

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
	'attributes' => [ 'format' => 'blocks', 'style' => '', 'class' => '' ], 'create' => [], 'values' => [ 'en_US' => '<p>Write to us.</p>' ] ];
$edit['blocks'][3]['background'] = [ 'slot' => '/template/page-home/contact/background', 'focus' => 2, 'create' => [ 'label' => 'Contact background', 'width' => 1200, 'height' => 600 ] ];
$edit['blocks'][2]['source'] = "<section id=\"map\" class=\"nino-section nino-section--tint\">\n\t<div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[osm lat=\"48.2\"]</div></div>\n</section>";
$edit['blocks'][] = [ 'kind' => 'html', 'source' => '<p>Imprint note</p>', 'reason' => null ];

$saved = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $edit, $loaded['hash'] );
$written = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) );

check( 'a save with changes answers 200, and the file is what the Writer makes of the model', $saved['status'] === 200 && $written === $write( $edit ) && $saved['hash'] === hash( 'sha256', $written ) );
check( '...the model it answers is the file read again - what was only the panel\'s, the create, is gone', $saved['model']['blocks'][3]['cols'][0]['components'][2]['name'] === 'text' && isset( $saved['model']['blocks'][3]['cols'][0]['components'][2]['create'] ) === false
	&& $saved['model']['name'] === 'Home page' && $saved['model']['header'] === 'html-header-short' && $saved['model'] === \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['model'] );
check( '...the text key the model named as new is made with the text typed for it, in the language it was typed in, and with none in the other: not the same words in every language, which one nobody wrote would show as written', ( \Nino\Text::entry( $appData, '/template/page-home/contact/note' )['values']['en_US'] ?? null ) === '<p>Write to us.</p>'
	&& ( \Nino\Text::entry( $appData, '/template/page-home/contact/note' )['values']['de_DE'] ?? null ) === '' );
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
$badKey['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-probe/services/Bad', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'format' => 'plain' ] ];
$badFormat = $probe['model'];
$badFormat['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-probe/services/other', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [ 'format' => 'markdown' ] ];
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

// The creation on its own: the texts are written once more after it, so only a call of its own shows that the key is made with them
$direct = [ 'keys' => [ '/template/page-direct/box/note' => [ 'format' => 'plain' ] ], 'slots' => [], 'keyMoves' => [], 'slotMoves' => [], 'values' => [ '/template/page-direct/box/note' => [ 'de_DE' => 'Nur Deutsch' ] ] ];
$directMade = [];
$directKeys = [];
$directFailure = $call( '_create', [ &$appData, $direct, &$directMade, &$directKeys ] );
$directEntry = \Nino\Text::entry( $appData, '/template/page-direct/box/note' ) ?: [ 'values' => [] ];
check( 'the key a save makes is made with the text typed for it, in the language it was typed in and with none in the other - by the creation itself, before any text is written', $directFailure === null && $directKeys === [ '/template/page-direct/box/note' ]
	&& ( $directEntry['values']['de_DE'] ?? null ) === 'Nur Deutsch' && ( $directEntry['values']['en_US'] ?? null ) === '' );
$call( '_rollback', [ &$appData, [], [], $directMade, $directKeys ] );
check( '...and a save that stops takes the key it made away again', \Nino\Text::entry( $appData, '/template/page-direct/box/note' ) === null );

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
$extra['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-probe/start/extra', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [] ];
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

// The hover of a picture where the Writer writes it - the first class of the call, or the one behind the radius of a picture that has a radius - is the file as it is: a save without a change
// keeps its bytes. A picture that says it behind another class reads as the same setting and so as the same model, and the file is written anew all the same, with the class where the Writer
// puts it (Reader::hoverBehind()). The sections of these files stand without the blank lines the Writer puts between them, so that a file that is written is told from one that is kept
$hoverLib		= static function( string $name, string $class ) use ( $holderOf, $section ): string {

	$call		= '['. $name. ' /template/page-lib/a/photo class="'. $class. '"]';
	$holder	= $holderOf( $name );

	return "<!-- nino:template-name Lib -->\n[template /templates/html-header]\n". $section( $holder === '' ? $call : '['. $holder. "]\n\t\t\t\t". $call. "\n\t\t\t[/". $holder. ']' ). "\n". $section( '[title /template/page-lib/b/title]', 'b' ). "\n[template /templates/html-footer]\n";
};
$hoverShortcut	= [];

foreach( $pictures as $hoverName ) {

	$hoverRadius	= $reader::surface( $registry['components'][$hoverName], $registry['components'] ) !== '';

	// The class of the call as the file has it => the class it has once the file has been saved without a change
	foreach( [
		'nino-hover'													=> 'nino-hover',
		'nino-hover my-class'									=> 'nino-hover my-class',
		'nino-radius-alt nino-hover my-class'	=> $hoverRadius === true ? 'nino-radius-alt nino-hover my-class' : 'nino-hover nino-radius-alt my-class',
		'my-class nino-hover'									=> 'nino-hover my-class',
		'nino-mt-1 nino-hover'								=> 'nino-hover nino-mt-1',
		'my-class nino-radius-alt nino-hover'	=> $hoverRadius === true ? 'nino-radius-alt nino-hover my-class' : 'nino-hover my-class nino-radius-alt',
		'my-class'														=> 'my-class',
	] as $hoverClass => $hoverWritten ) {

		$hoverText	= $hoverLib( $hoverName, $hoverClass );
		\Nino\Filesystem::putFileContent( $appData, '/templates/page-lib.tpl', $hoverText );
		$hoverLoaded	= \Nino\Modules\Builder\Document::load( $appData, 'page-lib' );
		$hoverHeld		= $hoverLoaded['model']['blocks'][0]['cols'][0]['components'][0];
		$hoverNodeLib	= $holderOf( $hoverName ) === '' ? $hoverHeld : $hoverHeld['components'][0];
		$hoverSaved		= \Nino\Modules\Builder\Document::save( $appData, 'page-lib', $hoverLoaded['model'], $hoverLoaded['hash'] );
		$hoverFile		= (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-lib.tpl' ) );

		// The setting is read from the file as it is, and the model that comes back is the model that went in
		$hoverRight		= $hoverNodeLib['hover'] === ( str_contains( $hoverClass, 'nino-hover' ) === true ? 'on' : '' ) && $hoverSaved['status'] === 200 && $hoverSaved['model'] === $hoverLoaded['model'];

		if( $hoverClass === $hoverWritten )
			$hoverRight = $hoverRight === true && $hoverFile === $hoverText && $hoverSaved['hash'] === $hoverLoaded['hash'];
		else
			$hoverRight = $hoverRight === true && str_contains( $hoverFile, '['. $hoverName. ' /template/page-lib/a/photo class="'. $hoverWritten. '"]' ) === true && str_contains( $hoverFile, 'class="'. $hoverClass. '"' ) === false
				&& $hoverSaved['hash'] === hash( 'sha256', $hoverFile ) && $hoverSaved['hash'] !== $hoverLoaded['hash'];

		if( $hoverRight === false )
			$hoverShortcut[] = $hoverName. ' "'. $hoverClass. '"';
	}
}

check( 'a file whose picture says nino-hover first, or behind its radius, keeps its bytes and its hash in a save without a change; one that says it behind another class - an editor\'s, a side of the spacing, a second radius or the radius of a picture that has none - reads as the same model all the same, and is written anew with the class where the Writer puts it'
	. ( $hoverShortcut === [] ? '' : ' - wrong: '. implode( ', ', $hoverShortcut ) ), $hoverShortcut === [] && count( $pictures ) >= 2 );

// The hover of a page that changes as well: the page is written whole, the class stands first - and switching the setting off takes it away, on takes it up again
\Nino\Filesystem::putFileContent( $appData, '/templates/page-lib.tpl', "<!-- nino:template-name Lib -->\n[template /templates/html-header]\n". $section( '[image /template/page-lib/a/photo class="my-class nino-hover"]', 'a' ). "\n[template /templates/html-footer]\n" );
$libHover = \Nino\Modules\Builder\Document::load( $appData, 'page-lib' );
$libHoverModel = $libHover['model'];
$libHoverModel['blocks'][0]['settings']['mt'] = '2';
$libHoverSaved = \Nino\Modules\Builder\Document::save( $appData, 'page-lib', $libHoverModel, $libHover['hash'] );
$libHoverFile = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-lib.tpl' ) );
$libHoverModel = $libHoverSaved['model'];
$libHoverModel['blocks'][0]['cols'][0]['components'][0]['hover'] = '';
$libHoverOff = \Nino\Modules\Builder\Document::save( $appData, 'page-lib', $libHoverModel, $libHoverSaved['hash'] );
$libHoverOffFile = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-lib.tpl' ) );
$libHoverModel = $libHoverOff['model'];
$libHoverModel['blocks'][0]['cols'][0]['components'][0]['hover'] = 'on';
$libHoverOn = \Nino\Modules\Builder\Document::save( $appData, 'page-lib', $libHoverModel, $libHoverOff['hash'] );
check( 'a picture that says nino-hover after a class of the editor\'s is read as hover on with the class kept, and once the page changes it is written with the class first, as the Writer writes it; switched off the call has the class of the editor\'s alone, switched on again the hover is first',
	$libHover['model']['blocks'][0]['cols'][0]['components'][0]['hover'] === 'on' && $libHover['model']['blocks'][0]['cols'][0]['components'][0]['attributes']['class'] === 'my-class'
	&& $libHoverSaved['status'] === 200 && str_contains( $libHoverFile, '[image /template/page-lib/a/photo class="nino-hover my-class"]' ) === true && str_contains( $libHoverFile, 'my-class nino-hover' ) === false
	&& $libHoverOff['status'] === 200 && str_contains( $libHoverOffFile, '[image /template/page-lib/a/photo class="my-class"]' ) === true && $libHoverOff['model']['blocks'][0]['cols'][0]['components'][0]['hover'] === ''
	&& $libHoverOn['status'] === 200 && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-lib.tpl' ) ), '[image /template/page-lib/a/photo class="nino-hover my-class"]' ) === true );

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
$bad['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/never', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'create' => [] ];
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

// The address of a link may hold the fill of a page of the site and a sub-path, and nothing else may hold a bracket
$aimedModel = static function( string $href ) use ( $loaded ): array {
	$model = $loaded['model'];
	$model['blocks'][1]['cols'][1]['components'][3]['attributes']['href'] = $href;
	return $model;
};
check( 'a link to a page of the site with a sub-path - the fill of the loop\'s element in it - is no problem in the button of a loop, and the page alone is none: problems() has nothing to say of either',
	\Nino\Modules\Builder\Document::problems( $appData, $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[.uri]]' ), $registry ) === [] && \Nino\Modules\Builder\Document::problems( $appData, $aimedModel( '/_nino/webpage/contact/uri' ), $registry ) === [] );
$quoted = (array) $tried( static fn(): array => \Nino\Modules\Builder\Document::save( $appData, 'page-home', $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/a"b' ), $loaded['hash'] ) );
$bracketed = (array) $tried( static fn(): array => \Nino\Modules\Builder\Document::save( $appData, 'page-home', $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[.uri]' ), $loaded['hash'] ) );
check( '...a sub-path with a quote, or a fill that is left open, is refused with 400 (code value) as any value a call cannot carry is - the attribute named - and the file is as it was',
	( $quoted['status'] ?? 0 ) === 400 && ( $quoted['code'] ?? '' ) === 'value' && str_contains( implode( ' ', (array) ( $quoted['problems'] ?? [] ) ), 'the attribute "href" of the component "button"' ) === true && ( $bracketed['status'] ?? 0 ) === 400 && ( $bracketed['code'] ?? '' ) === 'value'
	&& file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $before );

// The fills of the element in the sub-path are the loop's: where there is none, or the type has no such field, they mean nothing
$outside = static function( string $href ) use ( $loaded ): array {
	$model = $loaded['model'];
	$model['blocks'][3]['cols'][0]['components'][1]['attributes']['href'] = $href;
	return $model;
};
$fillProblems = static fn( array $model ): string => implode( ' | ', array_filter( \Nino\Modules\Builder\Document::problems( $appData, $model, $registry ), static fn( string $problem ): bool => str_contains( $problem, 'sub-path' ) ) );
check( 'a fill of the element in the sub-path of a link is a problem where there is no loop - the section, the attribute and the fill named, as for a source that means nothing there - and the page alone, or a sub-path with none, is none',
	str_contains( $fillProblems( $outside( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[.uri]]' ) ), 'the sub-path of the attribute "href" of button holds the fill [[.uri]], which means nothing in a column without a loop' ) === true
	&& $fillProblems( $outside( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/more?x=1' ) ) === '' && $fillProblems( $outside( '/_nino/webpage/contact/uri' ) ) === '' );
check( '...in a loop it is .slug, .uri, .id or a field of the type - and a field the type has not is a problem, as a source that is no field is, whatever else the sub-path holds',
	$fillProblems( $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[.slug]]/[[.uri]]/[[.id]]/[[title]]' ) ) === '' && str_contains( $fillProblems( $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[title]]/[[nope]]' ) ), 'holds the fill [[nope]], which is no field of the loop\'s type' ) === true
	&& str_contains( $fillProblems( $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[.lang]]' ) ), '[[.lang]]' ) === true );
// The slug is a source of a loop as the id and the uri are, and means nothing in a column without one
$slugSource = static function( int $block, int $col, int $component ) use ( $loaded ): array {
	$model = $loaded['model'];
	$model['blocks'][$block]['cols'][$col]['components'][$component]['source'] = '.slug';
	return $model;
};
check( 'the source .slug of a component is no problem in a loop, as .id and .uri are none - and it is one in a column without a loop, the source named',
	\Nino\Modules\Builder\Document::problems( $appData, $slugSource( 1, 1, 1 ), $registry ) === []
	&& str_contains( implode( ' | ', \Nino\Modules\Builder\Document::problems( $appData, $slugSource( 3, 0, 0 ), $registry ) ), 'the source ".slug" of title means nothing in a column without a loop' ) === true );

// The Document accepts in the sub-path exactly the fills the dialog of the Builder offers (_fillsOf() in admin.js): .slug, .uri, .id and the fields of the type that are a line of text or a number and no markup
\Nino\Elements::insertElementType( $appData, '/slugkinds', [
	'title'		=> [ 'type' => 'string', 'locale' => true ],
	'count'		=> [ 'type' => 'integer' ],
	'price'		=> [ 'type' => 'double' ],
	'my-field'	=> [ 'type' => 'string' ],
	'body'		=> [ 'type' => 'string', 'html' => true, 'blocks' => true ],
	'photo'		=> [ 'type' => 'image', 'width' => 400, 'height' => 300 ],
	'when'		=> [ 'type' => 'date' ],
	'stamp'		=> [ 'type' => 'datetime' ],
	'tags'		=> [ 'type' => 'array' ],
	'flag'		=> [ 'type' => 'boolean' ],
	'a.b'			=> [ 'type' => 'string' ],
] );
$kindsModel = static function( string $fill ) use ( $aimedModel ): array {
	$model = $aimedModel( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/'. $fill );
	$model['blocks'][1]['cols'][1]['stack']['source'] = '/slugkinds';
	return $model;
};
// What the loop's cells say of the fields of the other type stays the same whatever the sub-path holds: a fill is offered where it adds no problem to it
$kindsBase = \Nino\Modules\Builder\Document::problems( $appData, $kindsModel( '[[.id]]' ), $registry );
$kindsOffered = array_values( array_filter( [ '.slug', '.uri', '.id', 'title', 'count', 'price', 'my-field', 'body', 'photo', 'when', 'stamp', 'tags', 'flag', 'a.b', '.lang', 'nope' ],
	static fn( string $fill ): bool => \Nino\Modules\Builder\Document::problems( $appData, $kindsModel( '[['. $fill. ']]' ), $registry ) === $kindsBase ) );
check( 'the Document accepts the fills of the element that the dialog offers, and no others: .slug, .uri, .id and the fields of the type that are a line of text, an integer or a double and hold no markup - not an image, a body, a date, a time, a list of tags, a flag, a name no address can hold, nor the language of a text',
	$kindsOffered === [ '.slug', '.uri', '.id', 'title', 'count', 'price', 'my-field' ] );
check( '...a field of the type that is none of those is told apart from one the type has not: the sentence names the fill, and the field it is not',
	str_contains( $fillProblems( $kindsModel( '[[photo]]' ) ), 'holds the fill [[photo]], which is a field of the loop\'s type that is no line of text or number' ) === true
	&& str_contains( $fillProblems( $kindsModel( '[[nope]]' ) ), 'holds the fill [[nope]], which is no field of the loop\'s type' ) === true );
check( '...the Reader reads such a link all the same: the problem is the Document\'s, the page is a page', $reader::address( '[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[nope]]' ) !== null
	&& $read( $page( $section( '[button text="Go" href="[[/nino/dir]][[/_nino/webpage/contact/uri]]/[[nope]]"]' ) ) )['blocks'][0]['kind'] === 'section' );

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
	'a brand colour of the border that is a text'					=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['borderPrimary'] = 'true'; } ),
	'a padding of the row that is a number'							=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['rowPl'] = 1; } ),
	'a column that is a string'												=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0] = 'x'; } ),
	'a radius of a section that is a list'							=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['radius'] = [ 'standard' ]; } ),
	'a radius of the row that is none of the two strengths'	=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['rowRadius'] = 'sideways'; } ),
	'a radius of a column that is a number'						=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['radius'] = 1; } ),
	'a radius on a component that is no surface'			=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['radius'] = 'alternative'; } ),
	'a radius of a card that is a list'								=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => [ 'alternative' ], 'components' => [] ];
	} ),
	'a radius on the child of a card'									=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'components' => [
			[ 'name' => 'card-title', 'source' => '/template/page-home/hero/title', 'text' => null, 'attributes' => $registry['components']['card-title']['defaults'], 'radius' => 'standard' ],
		] ];
	} ),
	'a hover on a component that is no picture'				=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['hover'] = 'on'; } ),
	'a hover of a picture that is none of the two words'	=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '', 'text' => null, 'attributes' => $registry['components']['image']['defaults'], 'radius' => '', 'hover' => 'sideways' ];
	} ),
	'a hover of a picture that is a list'							=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '', 'text' => null, 'attributes' => $registry['components']['image']['defaults'], 'radius' => '', 'hover' => [ 'on' ] ];
	} ),
	'a hover on the child of a card that is no picture'	=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'components' => [
			[ 'name' => 'card-title', 'source' => '/template/page-home/hero/title', 'text' => null, 'attributes' => $registry['components']['card-title']['defaults'], 'hover' => 'on' ],
		] ];
	} ),
	'a step of the picture behind the row that is a number'	=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['rowBgPl'] = 1; } ),
	'a step of the picture behind the row outside the scale'	=> $shape( static function( array &$model ): void { $model['blocks'][0]['settings']['rowBgPr'] = '4'; } ),
	'a step of the picture behind a column outside the scale'	=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['bgPl'] = '4'; } ),
	'a step of the picture behind a card outside the scale'		=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'bgPr' => '4', 'components' => [] ];
	} ),
	'a step of the picture behind a card that is a list'		=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'bgPl' => [ '1' ], 'components' => [] ];
	} ),
	'a step of the picture on a component that is no card'	=> $shape( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['bgPr'] = '1'; } ),
	'a step of the picture on the child of a card'				=> $shape( static function( array &$model ) use ( $registry ): void {
		$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'components' => [
			[ 'name' => 'card-title', 'source' => '/template/page-home/hero/title', 'text' => null, 'attributes' => $registry['components']['card-title']['defaults'], 'bgPl' => '1' ],
		] ];
	} ),
];
$typed = array_filter( $answers, static fn( array $answer ): bool => $answer['status'] !== 400 || $answer['code'] !== 'invalid' );
check( 'a model of the wrong shape is refused with 400 and a problem, none of '. count( $answers ). ' ends in an error: '. implode( ', ', array_keys( $answers ) ), $typed === [] && ninoWarnings() === [] );

// The radius is one of the two strengths or none, in a section, its row, a column and a component that is a surface - and a component that is none has no radius to give
$radiusSaid = static fn( string $what ): string => implode( ' | ', $answers[$what]['problems'] ?? [] );
$radiusModel = $shaped['model'];
$radiusModel['blocks'][0]['settings']['radius'] = 'alternative';
$radiusModel['blocks'][0]['settings']['rowRadius'] = 'standard';
$radiusModel['blocks'][0]['cols'][0]['radius'] = 'alternative';
$radiusModel['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => 'alternative', 'components' => [] ];
check( '...the radius is named where it is wrong: the setting of the section, of its row, of a column and of a component - a radius on a component that is no surface too, where it stands in the children of a card as well - and where it is one of the two strengths or none in all four places, nothing is said of it',
	str_contains( $radiusSaid('a radius of a section that is a list'), 'the radius of block 1' ) === true && str_contains( $radiusSaid('a radius of the row that is none of the two strengths'), 'the rowRadius of block 1' ) === true
	&& str_contains( $radiusSaid('a radius of a column that is a number'), 'the radius of column 1 of block 1' ) === true
	&& str_contains( $radiusSaid('a radius on a component that is no surface'), 'block 1 has the component "title" with a radius, and it is no surface' ) === true
	&& str_contains( $radiusSaid('a radius of a card that is a list'), 'block 1 has the component "card" with a radius that is none of the two strengths' ) === true
	&& str_contains( $radiusSaid('a radius on the child of a card'), 'block 1 has the component "card-title" with a radius, and it is no surface' ) === true
	&& array_filter( \Nino\Modules\Builder\Document::problems( $appData, $radiusModel, $registry ), static fn( string $sentence ): bool => str_contains( $sentence, 'radius' ) === true ) === [] && ninoWarnings() === [] );

// The hover is empty or on, on a component whose schema says its source is an image - the picture of a card among them - and a component that is none has no hover to give
$hoverSaid = static fn( string $what ): string => implode( ' | ', $answers[$what]['problems'] ?? [] );
$hoverModel = $shaped['model'];
$hoverModel['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '', 'text' => null, 'attributes' => $registry['components']['image']['defaults'], 'radius' => '', 'hover' => 'on' ];
$hoverModel['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'components' => [
	[ 'name' => 'card-image', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card-image']['defaults'], 'hover' => 'on' ],
	[ 'name' => 'card-image', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card-image']['defaults'], 'hover' => '' ],
] ];
check( '...the hover is named where it is wrong: a hover on a component that is no picture - a child of a card too - and a word that is neither empty nor on, or a list - and where it is empty or on, on the frame of a picture and on the picture of a card, nothing is said of it',
	str_contains( $hoverSaid('a hover on a component that is no picture'), 'block 1 has the component "title" with a hover, and it is no picture' ) === true
	&& str_contains( $hoverSaid('a hover of a picture that is none of the two words'), 'block 1 has the component "image" with a hover that is neither empty nor on' ) === true
	&& str_contains( $hoverSaid('a hover of a picture that is a list'), 'block 1 has the component "image" with a hover that is neither empty nor on' ) === true
	&& str_contains( $hoverSaid('a hover on the child of a card that is no picture'), 'block 1 has the component "card-title" with a hover, and it is no picture' ) === true
	&& array_filter( \Nino\Modules\Builder\Document::problems( $appData, $hoverModel, $registry ), static fn( string $sentence ): bool => str_contains( $sentence, 'hover' ) === true ) === [] && ninoWarnings() === [] );

// How far in from its sides the picture behind a row, a column and a card starts is a step from 0 to 3 or none - and the card is the one component to give a step
$bgSaid = static fn( string $what ): string => implode( ' | ', $answers[$what]['problems'] ?? [] );
$bgModel = $shaped['model'];
$bgModel['blocks'][0]['settings']['rowBgPl'] = '0';
$bgModel['blocks'][0]['settings']['rowBgPr'] = '3';
$bgModel['blocks'][0]['cols'][0]['bgPl'] = '2';
$bgModel['blocks'][0]['cols'][0]['bgPr'] = '';
$bgModel['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => $registry['components']['card']['defaults'], 'radius' => '', 'bgPl' => '0', 'bgPr' => '3', 'components' => [] ];
check( '...how far in the picture starts is named where it is wrong - the setting of the row, of a column, of a card, and a step on a component that is no card, on the child of a card as well - and where it is a step from 0 to 3 or none in all three places, nothing is said of it',
	str_contains( $bgSaid('a step of the picture behind the row that is a number'), 'the rowBgPl of block 1 is none of the steps 0 to 3' ) === true && str_contains( $bgSaid('a step of the picture behind the row outside the scale'), 'the rowBgPr of block 1 is none of the steps 0 to 3' ) === true
	&& str_contains( $bgSaid('a step of the picture behind a column outside the scale'), 'the bgPl of column 1 of block 1 is none of the steps 0 to 3' ) === true
	&& str_contains( $bgSaid('a step of the picture behind a card that is a list'), 'block 1 has the component "card" with a bgPl that is none of the steps 0 to 3' ) === true
	&& str_contains( $bgSaid('a step of the picture on a component that is no card'), 'block 1 has the component "title" with a bgPr, and it is no card' ) === true
	&& str_contains( $bgSaid('a step of the picture on the child of a card'), 'block 1 has the component "card-title" with a bgPl, and it is no card' ) === true
	&& array_filter( \Nino\Modules\Builder\Document::problems( $appData, $bgModel, $registry ), static fn( string $sentence ): bool => str_contains( $sentence, 'steps 0 to 3' ) === true || str_contains( $sentence, 'no card' ) === true ) === [] && ninoWarnings() === [] );

// A switch of a section is on or off - the brand colour of its border is one: anything else would be left out of the classes and read back as off, so it is refused before anything is made
$switchBad			= [ 'a text' => 'true', 'an empty text' => '', 'a number' => 1, 'zero' => 0, 'a list' => [ true ], 'an empty list' => [] ];
$switchRefused	= [];

foreach( [ 0, 3 ] as $at )
	foreach( [ 'borderPrimary' ] as $key )
		foreach( $switchBad as $what => $value ) {

			$model = $loaded['model'];
			$model['blocks'][$at]['settings'][$key] = $value;
			$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

			if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ 'the '. $key. ' of block '. ( $at + 1 ). ' is neither on nor off' ]
				|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
				$switchRefused[] = $key. ' of block '. ( $at + 1 ). ' as '. $what;
		}

check( 'the brand colour of the border of a section that is no bool - a text, a number, a list - is refused with 400 (code invalid), the switch and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $switchBad ) * 2 . ' cases'. ( $switchRefused === [] ? '' : ' - '. implode( ', ', $switchRefused ) ), $switchRefused === [] );

$switchLeft = $loaded['model'];
$switchLeft['blocks'][0]['settings']['borderPrimary'] = true;
$switchWith = \Nino\Modules\Builder\Document::source( $appData, $switchLeft );
unset( $switchLeft['blocks'][0]['settings']['borderPrimary'] );
$switchSource = \Nino\Modules\Builder\Document::source( $appData, $switchLeft );
check( '...and a switch left out is off, no problem: the section is written without the class, and with it where the switch is on', $switchSource['status'] === 200 && str_contains( $switchSource['parts'][1]['source'], '<section id="start" class="nino-section nino-section--black ' ) === true
	&& str_contains( $switchSource['parts'][1]['source'], 'nino-section--border-primary' ) === false && $switchWith['status'] === 200 && str_contains( $switchWith['parts'][1]['source'], 'nino-section--border-primary' ) === true );

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
check( '...and counter, the dimming of a column with a picture and the slot of one are none of that: the source view writes them', $groundSource['status'] === 200 && str_contains( $groundSource['parts'][1]['source'], "<div class=\"nino-grid-100 nino-grid-s-100 nino-grid-m-100 nino-grid-l-66 nino-grid--counter nino-grid--dim nino-text-left\">\n\t\t\t<div class=\"nino-grid-bg nino-img-focus--4\">[image /template/page-home/hero/photo alt=\"\"]</div>\n\t\t\t[title " ) === true );

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

// The width, the place and the alignment of a row, the place of a picture and the alignment of a text or of a stack: a word of Nino.css or none - what is none of them would be a class of nothing
$wordBad = [
	'a row that is no width'										=> [ 'section', 'row', 'narrower', 'the row of block %d is none that Nino.css has' ],
	'a place in capitals'											=> [ 'section', 'rowPlace', 'Left', 'the rowPlace of block %d is none that Nino.css has' ],
	'a fourth place of the row, a word of the alignment'	=> [ 'section', 'rowPlace', 'center', 'the rowPlace of block %d is none that Nino.css has' ],
	'the middle, which is none and has no word of its own'	=> [ 'section', 'rowPlace', 'middle', 'the rowPlace of block %d is none that Nino.css has' ],
	'a place of the row that is a number'					=> [ 'section', 'rowPlace', 0, 'the rowPlace of block %d is none that Nino.css has' ],
	'a row alignment that is none'							=> [ 'section', 'rowAlign', 'top', 'the rowAlign of block %d is none that Nino.css has' ],
	'a place of the content in capitals'				=> [ 'section', 'contentPos', 'Top', 'the contentPos of block %d is none that Nino.css has' ],
	'the middle, which has no word of its own'	=> [ 'section', 'contentPos', 'center', 'the contentPos of block %d is none that Nino.css has' ],
	'a whitespace that is none of the four'		=> [ 'section', 'whitespace', 'wider', 'the whitespace of block %d is none that Nino.css has' ],
	'a whitespace in capitals'					=> [ 'section', 'whitespace', 'More', 'the whitespace of block %d is none that Nino.css has' ],
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

check( 'a row that is no width, a place of a row, an alignment of a row, a place of the content and an alignment of a text or of a stack that Nino.css has none of are refused the same way, the setting and the block named: '. count( $wordBad ) * 2 . ' cases'. ( $wordRefused === [] ? '' : ' - '. implode( ', ', $wordRefused ) ), $wordRefused === [] );

$wordWrong = [];

foreach( [
	[ 'section', 'row', [ 'narrow', 'wide', 'full' ], 'nino-grid-row--%s' ],
	[ 'section', 'rowPlace', [ 'left', 'right' ], 'nino-grid-row--%s' ],
	[ 'section', 'rowAlign', [ 'center', 'middle', 'bottom' ], 'nino-grid-%s' ],
	[ 'section', 'rowGap', [ '0', '1', '2', '3', '4', '5', '6' ], 'nino-grid-gap-%s' ],
	[ 'section', 'rowPl', [ '0', '1', '2', '3', '4', '5', '6' ], 'nino-pl-%s' ],
	[ 'section', 'rowPr', [ '0', '1', '2', '3', '4', '5', '6' ], 'nino-pr-%s' ],
	[ 'section', 'rowPt', [ '0', '1', '2', '3', '4', '5', '6' ], 'nino-pt-%s' ],
	[ 'section', 'rowPb', [ '0', '1', '2', '3', '4', '5', '6' ], 'nino-pb-%s' ],
	[ 'section', 'contentPos', [ 'top', 'bottom' ], 'nino-cover-%s' ],
	[ 'section', 'whitespace', [ 'least', 'less', 'more', 'most' ], 'nino-whitespace--%s' ],
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

check( 'the vocabularies the Reader keeps are the words the Builder writes, no more and no fewer - a word dropped from one of them would become a custom class without a sound',
	\Nino\Modules\Builder\Reader::ROW === [ 'narrow', 'wide', 'full' ] && \Nino\Modules\Builder\Reader::ROW_PLACE === [ 'left', 'right' ] && \Nino\Modules\Builder\Reader::ROW_ALIGN === [ 'center', 'middle', 'bottom' ]
	&& \Nino\Modules\Builder\Reader::CONTENT_POS === [ 'top', 'bottom' ] && \Nino\Modules\Builder\Reader::WHITESPACE === [ 'least', 'less', 'more', 'most' ] && \Nino\Modules\Builder\Reader::TEXT === [ 'left', 'center', 'right' ]
	&& \Nino\Modules\Builder\Reader::STACK_ALIGN === [ 'start', 'center', 'end' ] );
check( '...and every word the Reader keeps in a setting of its own is none of that: the source view writes it as the class it is read from'. ( $wordWrong === [] ? '' : ' - '. implode( ', ', $wordWrong ) ), $wordWrong === [] );

// The padding of a row on each side is a step of the space scale or none, as the padding and the margins of a column are: a step too far, a word, a number where the model has the step as text, a switch, a list
$stepBad		= [ 'a step too far' => '7', 'two digits' => '10', 'a word' => 'big', 'a step with a space' => '1 ', 'a number' => 3, 'a switch' => true, 'a list' => [ '1' ], 'an empty list' => [] ];
$stepRefused	= [];

foreach( [ 0, 3 ] as $at )
	foreach( \Nino\Modules\Builder\Reader::ROW_SPACING as $key )
		foreach( $stepBad as $what => $value ) {

			$model = $loaded['model'];
			$model['blocks'][$at]['settings'][$key] = $value;
			$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

			if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ 'the '. $key. ' of block '. ( $at + 1 ). ' is none of the steps 0 to 6' ]
				|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
				$stepRefused[] = $key. ' of block '. ( $at + 1 ). ' as '. $what;
		}

check( 'a padding of the row that is none of the steps 0 to 6 - a step too far, a word, a number where the model has the step as text, a switch, a list - is refused with 400 (code invalid), the setting and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $stepBad ) * 8 . ' cases'. ( $stepRefused === [] ? '' : ' - '. implode( ', ', $stepRefused ) ), $stepRefused === [] );

$stepRight = $loaded['model'];

foreach( \Nino\Modules\Builder\Reader::ROW_SPACING as $key )
	$stepRight['blocks'][0]['settings'][$key] = '6';

$stepRightSource = \Nino\Modules\Builder\Document::source( $appData, $stepRight );
$stepRight['blocks'][0]['settings']['rowPl'] = '0';
$stepRight['blocks'][0]['settings']['rowPb'] = '';
check( '...and every step of the eight is let through, the empty one and 0 and 6 among them: the row is written with the classes it says', $stepRightSource['status'] === 200 && str_contains( $stepRightSource['parts'][1]['source'], ' nino-pl-6 nino-pr-6 nino-pt-6 nino-pb-6' ) === true
	&& \Nino\Modules\Builder\Document::source( $appData, $stepRight )['status'] === 200 && str_contains( \Nino\Modules\Builder\Document::source( $appData, $stepRight )['parts'][1]['source'], ' nino-pl-0 nino-pr-6 nino-pt-6 nino-vpa">' ) === true );

// The gap between the columns of a row and the gap between the components of a column are steps of the space scale as well: the same eight words, the setting and the element named
$gapRefused = [];

foreach( [ 'rowGap' => [ [ 'blocks', 0, 'settings' ], 'the rowGap of block 1 is none of the steps 0 to 6' ], 'stackGap' => [ [ 'blocks', 0, 'cols', 0 ], 'the stackGap of column 1 of block 1 is none of the steps 0 to 6' ] ] as $key => [ $path, $sentence ] )
	foreach( $stepBad as $what => $value ) {

		$model = $loaded['model'];
		$node = &$model;
		foreach( $path as $step )
			$node = &$node[$step];
		$node[$key] = $value;
		unset( $node );
		$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

		if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ $sentence ]
			|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
			$gapRefused[] = $key. ' as '. $what;
	}

check( 'a gap between the columns of a row or between the components of a column that is none of the steps 0 to 6 - a step too far, a word, a number where the model has the step as text, a switch, a list - is refused with 400 (code invalid), the setting and the element named, by a save and by the source view alike, and nothing is written: '
	. count( $stepBad ) * 2 . ' cases'. ( $gapRefused === [] ? '' : ' - '. implode( ', ', $gapRefused ) ), $gapRefused === [] );

$gapRight = $loaded['model'];
$gapRight['blocks'][0]['settings']['rowGap'] = '6';
$gapRight['blocks'][0]['cols'][0]['stackGap'] = '0';
$gapRightSource = \Nino\Modules\Builder\Document::source( $appData, $gapRight );
$gapRight['blocks'][0]['settings']['rowGap'] = '';
$gapRight['blocks'][0]['cols'][0]['stackGap'] = '';
check( '...and the empty one, 0 and 6 are let through: the row and the column are written with the classes they say',
	$gapRightSource['status'] === 200 && str_contains( $gapRightSource['parts'][1]['source'], 'nino-grid-gap-6' ) === true && str_contains( $gapRightSource['parts'][1]['source'], 'nino-stack-gap-0' ) === true
	&& \Nino\Modules\Builder\Document::source( $appData, $gapRight )['status'] === 200 );

// The cover and the picture behind it: two switches that are on or off like the others, and a height in percent of the window's that is a whole number or none
$pictureSwitchRefused = [];

foreach( [ 0, 3 ] as $at )
	foreach( [ 'parallax', 'dim', 'padded', 'inner', 'arrow' ] as $key )
		foreach( $switchBad as $what => $value ) {

			$model = $loaded['model'];
			$model['blocks'][$at]['settings'][$key] = $value;
			$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $model, $loaded['hash'] );

			if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || $answer['problems'] !== [ 'the '. $key. ' of block '. ( $at + 1 ). ' is neither on nor off' ]
				|| \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400 || file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) !== $before )
				$pictureSwitchRefused[] = $key. ' of block '. ( $at + 1 ). ' as '. $what;
		}

check( 'a parallax, a dimming, the room around a cover, a background inside or the arrow that is no bool - a text, a number, a list - is refused the way the brand colour of a border is, the switch and the block named, by a save and by the source view alike, and nothing is written: '
	. count( $switchBad ) * 10 . ' cases'. ( $pictureSwitchRefused === [] ? '' : ' - '. implode( ', ', $pictureSwitchRefused ) ), $pictureSwitchRefused === [] );

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

// A section that is a loop of sections is the call of the one stack that makes them: saved as that call, with the id of the section for the id of the call and its classes after nino-section for the call's
\Nino\Filesystem::putFileContent( $appData, '/templates/page-loop.tpl', $page( $loopFile ) );
$loopLoaded	= \Nino\Modules\Builder\Document::load( $appData, 'page-loop' );
$loopEdit		= $loopLoaded['model'];
$loopEdit['blocks'][0]['settings']['whitespace']	= 'most';
$loopEdit['blocks'][0]['settings']['text']				= 'center';
$loopAnswer	= \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $loopEdit, $loopLoaded['hash'] );
$loopOnDisk	= (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-loop.tpl' ) );
check( 'a section that is a loop of sections is saved as the call of its stack: the id of the section is the id of the call, its classes after nino-section are the call\'s, the loop\'s own attributes stay - and the answer is the model that reads back, loop and all',
	$loopLoaded['status'] === 200 && $loopLoaded['model']['blocks'][0]['stack']['name'] === 'sections' && $loopAnswer['status'] === 200
	&& str_contains( $loopOnDisk, "\n[sections /services sort=\"title\" limit=\"3\" id=\"news\" class=\"nino-section--alt nino-whitespace--most nino-pt-2 nino-text-center\"]\n\t<div class=\"nino-grid-row\">\n" ) === true && str_ends_with( $loopOnDisk, "\t</div>\n[/sections]\n\n</div>\n" ) === true
	&& $loopAnswer['model']['blocks'][0]['stack'] === $loopLoaded['model']['blocks'][0]['stack'] && $loopAnswer['model']['blocks'][0]['settings']['whitespace'] === 'most' && $loopAnswer['model']['blocks'][0]['id'] === 'news' );
check( '...the source view writes the same call, and a save that changes nothing leaves the file as it is',
	\Nino\Modules\Builder\Document::source( $appData, $loopAnswer['model'] )['status'] === 200 && str_contains( \Nino\Modules\Builder\Document::source( $appData, $loopAnswer['model'] )['parts'][1]['source'], '[sections /services sort="title" limit="3" id="news" class="nino-section--alt nino-whitespace--most nino-pt-2 nino-text-center"]' ) === true
	&& \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $loopAnswer['model'], $loopAnswer['hash'] )['hash'] === $loopAnswer['hash'] && (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-loop.tpl' ) ) === $loopOnDisk );

$loopTimed = $loopLoaded['model'];
$loopTimed['blocks'][0]['settings']['vpa'] = 'zoom-soft';
$loopTimed['blocks'][0]['settings']['vpaDelay'] = '200ms';
$loopTimed['blocks'][0]['settings']['vpaDuration'] = '2s';
$loopTimedSource = \Nino\Modules\Builder\Document::source( $appData, $loopTimed );
check( '...the delay and the duration of the animation of a loop of sections are let through as any section\'s are: they are the attributes of the tag of its row, inside the call, and the call keeps none of them',
	$loopTimedSource['status'] === 200 && str_contains( $loopTimedSource['parts'][1]['source'], ' nino-vpa nino-vpa--zoom-soft" data-vpa-delay="200ms" data-vpa-duration="2s">' ) === true
	&& preg_match( '/^\[sections [^\]]*data-vpa/m', $loopTimedSource['parts'][1]['source'] ) === 0 && \Nino\Modules\Builder\Document::problems( $appData, $loopTimed, $registry ) === [] );

$loopBad = [
	'a loop that makes no sections'								=> [ static function( array &$block ): void { $block['stack']['name'] = 'stack'; }, 'block 1 has the loop "stack", which makes no sections' ],
	'a loop with components of its own'						=> [ static function( array &$block ): void { $block['stack']['components'] = []; }, 'block 1 has the loop "sections" with components, and it holds none' ],
	'a loop that is no call'									=> [ static function( array &$block ): void { $block['stack'] = 'sections'; }, 'block 1 has a call that is none' ],
	'a cover with a height'										=> [ static function( array &$block ): void { $block['settings']['cover'] = 60; }, 'the cover of block 1 has a height, and the call of a loop of sections has no attribute for one: a cover is as high as the page makes it' ],
	'a loop in a column of it'									=> [ static function( array &$block ): void { $block['cols'][0]['stack'] = [ 'name' => 'stack', 'source' => '/services', 'attributes' => [] ]; }, 'column 1 of block 1 has a loop, and it is a column of a loop of sections' ],
];
$loopRefused = [];

foreach( $loopBad as $what => [ $change, $sentence ] ) {

	$model = $loopAnswer['model'];
	$change( $model['blocks'][0] );
	$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $model, $loopAnswer['hash'] );

	if( $answer['status'] !== 400 || $answer['code'] !== 'invalid' || in_array( $sentence, $answer['problems'], true ) === false || \Nino\Modules\Builder\Document::source( $appData, $model )['status'] !== 400
		|| (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-loop.tpl' ) ) !== $loopOnDisk )
		$loopRefused[] = $what;
}

check( 'what a loop of sections cannot carry is refused with 400 (code invalid), the block named, by a save and by the source view alike, and nothing is written - a loop that makes none, one with components, one that is no call, a cover with a height, a loop in one of its columns: '
	. count( $loopBad ). ' cases'. ( $loopRefused === [] ? '' : ' - '. implode( ', ', $loopRefused ) ), $loopRefused === [] );

$loopSources = $loopAnswer['model'];
$loopSources['blocks'][0]['stack']['source'] = '/nothing';
$loopSourcesAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $loopSources, $loopAnswer['hash'] );
$loopFields = $loopAnswer['model'];
$loopFields['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => 'nothing', 'text' => null, 'attributes' => $registry['components']['text']['defaults'] ];
$loopFieldsAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $loopFields, $loopAnswer['hash'] );
$loopWell = $loopAnswer['model'];
$loopWell['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => 'summary', 'text' => null, 'attributes' => $registry['components']['text']['defaults'] ];
check( 'the sources in a loop of sections mean what they mean in a loop of a column: the type it runs over is an element type, and a component names a field of it - or a key, or .id and .uri - and no other word',
	$loopSourcesAnswer['status'] === 400 && in_array( 'the section "news": the loop runs over "nothing", which is no element type', $loopSourcesAnswer['problems'], true ) === true
	&& $loopFieldsAnswer['status'] === 400 && in_array( 'the section "news": the source "nothing" of text is no field of the loop\'s type', $loopFieldsAnswer['problems'], true ) === true
	&& \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $loopWell, $loopAnswer['hash'] )['status'] === 200 && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-loop.tpl' ) ), "\t\t\t[text summary]\n" ) === true );
\Nino\Modules\Builder\Document::delete( $appData, 'page-loop' );

$stackWrong = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$stackWrong['model']['blocks'][3]['cols'][0]['components'] = [ [ 'name' => 'stack', 'source' => '/services', 'text' => null, 'attributes' => [], 'content' => '' ] ];
$stackAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $stackWrong['model'], $stackWrong['hash'] );
check( 'a component called stack in a static column reads back as a stack: it is not the section that was posted, and is a problem', $stackAnswer['status'] === 400 && str_contains( implode( ' ', $stackAnswer['problems'] ), 'does not read back as it was written' ) === true );

// The conflict
$loaded = \Nino\Modules\Builder\Document::load( $appData, 'page-home' );
$mine = $loaded['model'];
$mine['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/late', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'create' => [] ];
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
$limited['blocks'][3]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-home/contact/forbidden', 'text' => null, 'attributes' => [ 'tag' => 'h3', 'style' => '', 'class' => '' ], 'create' => [] ];
$limitedAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home', $limited, $loaded['hash'] );
check( 'an account that may not make text keys cannot make them through the builder: the builder answers 403 before it makes anything, and the file is not written', $limitedAnswer['status'] === 403
	&& \Nino\Text::entry( $appData, '/template/page-home/contact/forbidden' ) === null && file_get_contents( \Nino\Filesystem::path( $appData, '/templates/page-home.tpl' ) ) === $write( $mine ) );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

// A loop of sections makes the ids of its sections of its own id and a number - news-1, news-2 - and a section of the page that is none of a loop and has such an id is the id of two
$loopName = \Nino\Modules\Builder\Reader::loopsSections( $registry );
$loopOf = static fn( string $id ): string => '['. $loopName. ' /services id="'. $id. "\"]\n". $loopRow. "\n[/". $loopName. ']';
$collides = $page( $loopOf( 'news' ), $section( '[title /template/page-loop/news-2/title]', 'news-2' ) );
$collidingRead = $read( $collides );
check( 'a file that has a section by the id a loop of sections makes of its own is read as it always was: the loop and the section, both of them sections, none held back as a block it cannot read - and written, it reads back as it was',
	array_column( $collidingRead['blocks'], 'kind' ) === [ 'section', 'section' ] && array_column( $collidingRead['blocks'], 'id' ) === [ 'news', 'news-2' ] && $collidingRead['blocks'][0]['stack'] !== null && $collidingRead['blocks'][1]['stack'] === null
	&& $read( $write( $collidingRead ) ) === $collidingRead );

$loopedFile = $page( $loopOf( 'news' ), $section( '[title /template/page-loop/extra/title]', 'extra' ) );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-loop.tpl', $loopedFile );
$loopPath = \Nino\Filesystem::path( $appData, '/templates/page-loop.tpl' );
$loopedPage = \Nino\Modules\Builder\Document::load( $appData, 'page-loop' );
$renamedTo = static function( string $id ) use ( $loopedPage ): array {
	$model = $loopedPage['model'];
	$model['blocks'][1]['id'] = $id;
	$model['blocks'][1]['renamedFrom'] = 'extra';
	return $model;
};
$idTaken = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $renamedTo( 'news-2' ), $loopedPage['hash'] );
$idTakenLonger = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $renamedTo( 'news-12' ), $loopedPage['hash'] );
check( 'a save that names a section by the id a loop of sections makes for one of its own is refused with 409 (section-id-taken), the ids named, before anything is made or written - the file is as it was',
	$idTaken['status'] === 409 && $idTaken['code'] === 'section-id-taken' && $idTaken['problems'] === [ 'news-2' ] && $idTakenLonger['status'] === 409 && $idTakenLonger['problems'] === [ 'news-12' ] && file_get_contents( $loopPath ) === $loopedFile );

$statuses = [];

foreach( [ 'news-two', 'news2', 'xnews-2', 'news-2x', 'news' ] as $id ) {

	\Nino\Filesystem::putFileContent( $appData, '/templates/page-loop.tpl', $loopedFile );

	$statuses[$id] = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $renamedTo( $id ), $loopedPage['hash'] )['status'];
}

check( '...an id that only looks like one - another word after the hyphen, a number before it, the number with something after it - is no such id, and the section is saved; the id of the loop itself is the duplicate it was always refused as',
	$statuses === [ 'news-two' => 200, 'news2' => 200, 'xnews-2' => 200, 'news-2x' => 200, 'news' => 400 ] );

$handWritten = $page( $loopOf( 'news' ), $section( '[title /template/page-loop/news-3/title]', 'news-3' ) );
\Nino\Filesystem::putFileContent( $appData, '/templates/page-loop.tpl', $handWritten );
$handLoaded = \Nino\Modules\Builder\Document::load( $appData, 'page-loop' );
$handSaved = \Nino\Modules\Builder\Document::save( $appData, 'page-loop', $handLoaded['model'], $handLoaded['hash'] );
check( '...a file that was written so - by hand, or before - opens as it is, and saving it asks for another id, whatever else is changed: the page would have two sections of one id', array_column( $handLoaded['model']['blocks'], 'kind' ) === [ 'section', 'section' ]
	&& $handSaved['status'] === 409 && $handSaved['code'] === 'section-id-taken' && $handSaved['problems'] === [ 'news-3' ] && file_get_contents( $loopPath ) === $handWritten );
unlink( $loopPath );

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
	'an alt text that is a number'						=> static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][0]['create'] = [ 'alt' => [ 'en_US' => 5 ] ]; },
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

$odd = $refused( static function( array &$model ): void { $model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-texts/a/odd', 'text' => null, 'attributes' => [ 'format' => 'nonsense', 'style' => '', 'class' => '' ], 'create' => [], 'values' => [ 'en_US' => 'Typed' ] ]; } );
check( 'a component that asks for a format there is none of is not made plain: the section is refused with 400 (invalid), the attribute named, as for any value outside the options of an attribute - and no key is made, no text written', $odd['status'] === 400 && $odd['code'] === 'invalid'
	&& str_contains( implode( ' ', $odd['problems'] ), 'the section "a" is not valid: line ' ) === true && str_contains( implode( ' ', $odd['problems'] ), 'the attribute format has a value the builder does not keep' ) === true
	&& $odd['unchanged'] === true && \Nino\Text::entry( $appData, '/template/page-texts/a/odd' ) === null );

// A key that is new: made, in the format its component asks for, and written
$fresh = $texted['model'];
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/a/title-2', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [],
	'values' => [ 'en_US' => 'Hello <b>there</b>', 'de_DE' => 'Hallo' ] ];
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-texts/a/body', 'text' => null, 'attributes' => [ 'format' => 'inline', 'style' => '', 'class' => '' ], 'create' => [],
	'values' => [ 'de_DE' => 'Ein <em>Satz</em><p>und ein Absatz</p>' ] ];
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-texts/a/lines', 'text' => null, 'attributes' => $registry['components']['text']['defaults'], 'create' => [], 'values' => [ 'en_US' => "One\nTwo" ] ];
// Nothing typed for it: the key is made, and holds nothing in any language
$fresh['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/a/untyped', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [] ];
$freshAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $fresh, $texted['hash'] );
$title2 = \Nino\Text::entry( $appData, '/template/page-texts/a/title-2' );
$body = \Nino\Text::entry( $appData, '/template/page-texts/a/body' );
$lines = \Nino\Text::entry( $appData, '/template/page-texts/a/lines' );
$untyped = \Nino\Text::entry( $appData, '/template/page-texts/a/untyped' );

check( 'a text typed for a key the save makes is written to the key, to the language it is for: the other languages hold none - nothing is made up for them, a page shows the native text there - and a key that nothing was typed for is empty in every language', $freshAnswer['status'] === 200
	&& $sorted( $title2['values'] ) === [ 'de_DE' => 'Hallo', 'en_US' => 'Hello there' ] && $body['values']['en_US'] === '' && $body['values']['de_DE'] === $cleanly( 'Ein <em>Satz</em><p>und ein Absatz</p>', 'inline' )
	&& str_contains( $body['values']['de_DE'], '<p>' ) === false && $untyped !== null && $sorted( $untyped['values'] ) === [ 'de_DE' => '', 'en_US' => '' ] );
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
$stopping['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/z/new', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [], 'values' => [ 'en_US' => 'A new title' ] ];
$stopping['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/z/newer', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [], 'values' => [ 'de_DE' => 'Ein neuerer Titel' ] ];

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
$halfway['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'title', 'source' => '/template/page-texts/z/other', 'text' => null, 'attributes' => $registry['components']['title']['defaults'], 'create' => [], 'values' => [ 'en_US' => 'Other title', 'de_DE' => 'Anderer Titel' ] ];
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

// A slot that is made as the copy of another takes what the other has: the alt texts of the languages the site has, and the picture
$copyFrom = 'template/page-texts/src/photo.800x400.jpg';
\Nino\Images::restore( $appData, $copyFrom, 'the bytes of a card picture' );
$appData['/nino/html/images']['/template/page-texts/src/photo'] = [ 'label' => 'Source photo', 'width' => 800, 'height' => 400, 'filename' => $copyFrom, 'alt' => [ 'en_US' => 'A card', 'de_DE' => 'Eine Karte', 'xx_XX' => 'A language that is gone' ] ];
$appData['/nino/html/images']['/template/page-texts/src/bare'] = [ 'label' => 'Source bare', 'width' => 800, 'height' => 400, 'filename' => null ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );

// Without the permission of the Images panel no alt text is written and no picture is handed on: they are that panel's to write, for a slot that is there and for one the save makes. An account that has
// not its permission is not refused for them - the save goes through, and its slots are made without them - and one that has it has them written
\Nino\Auth::insertUser( $appData, 'noimages@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/slots/manage', '/_admin/keys/manage' ] );
\Nino\Auth::insertUser( $appData, 'images@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/slots/manage', '/_admin/keys/manage', '/_admin/images/manage' ] );
\Nino\Auth::insertUser( $appData, 'noslots@example.com', 'correct horse battery staple', [ '/_admin/builder/manage', '/_admin/keys/manage' ] );
$configFile = \Nino\Filesystem::path( $appData, '/config.php' );
// The state as it is when it is asked for: an arrow function, or a use by value, keeps $appData as it was when the closure was made, and would never show a slot the save made
$stateNow = static function() use ( &$appData, $configFile, $textsPath ): array {
	return [ \Nino\Images::getSlots( $appData ), (string) file_get_contents( $configFile ), (string) file_get_contents( $textsPath ) ];
};
$slotOf = static function( string $uri ) use ( &$appData ): array {
	return \Nino\Images::getSlot( $appData, $uri ) ?: [];
};
// What the panel sends for a save that makes pictures: a new picture with the placeholder alt text of each language of the site, the picture behind a section and the one behind a column with
// alt texts typed for them, and the copy of a picture, which says which slot it is a copy of
$pictures = static function( array $model, string $name ) use ( $registry ): array {
	$image = $registry['components']['image']['defaults'];
	$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-texts/z/'. $name, 'text' => null, 'attributes' => $image, 'create' => [ 'label' => 'Image', 'width' => 600, 'height' => 300, 'alt' => [ 'en_US' => 'Image', 'de_DE' => 'Bild' ] ] ];
	$model['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'image', 'source' => '/template/page-texts/z/'. $name. '-copy', 'text' => null, 'attributes' => $image, 'create' => [ 'label' => 'Copy', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/photo' ] ];
	$model['blocks'][0]['background'] = [ 'slot' => '/template/page-texts/z/'. $name. '-behind', 'focus' => null, 'create' => [ 'label' => 'Behind', 'width' => 600, 'height' => 300, 'alt' => [ 'de_DE' => 'Eine Stimmung' ] ] ];
	$model['blocks'][0]['cols'][0]['background'] = [ 'slot' => '/template/page-texts/z/'. $name. '-column', 'focus' => null, 'create' => [ 'label' => 'Column', 'width' => 600, 'height' => 300, 'alt' => [ 'en_US' => 'A column mood' ] ] ];
	return $model;
};

\Nino\Auth::loginUser( $appData, 'images@example.com', 'correct horse battery staple' );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$granted = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $pictures( $structure['model'], 'granted' ), $structure['hash'] );
check( 'an account that may manage images has the alt texts of its new slots written - the placeholder of each language for a picture, the texts typed for the picture behind a section and for the one behind a column - and a copy takes over the alt texts and the picture of the slot it is a copy of',
	$granted['status'] === 200 && $slotOf( '/template/page-texts/z/granted' )['alt'] === [ 'en_US' => 'Image', 'de_DE' => 'Bild' ] && $slotOf( '/template/page-texts/z/granted-behind' )['alt'] === [ 'de_DE' => 'Eine Stimmung' ]
	&& $slotOf( '/template/page-texts/z/granted-column' )['alt'] === [ 'en_US' => 'A column mood' ] && $slotOf( '/template/page-texts/z/granted-copy' )['alt'] === [ 'en_US' => 'A card', 'de_DE' => 'Eine Karte' ]
	&& \Nino\Images::read( $appData, 'template/page-texts/z/granted-copy.800x400.jpg' ) === 'the bytes of a card picture' );

\Nino\Auth::loginUser( $appData, 'noimages@example.com', 'correct horse battery staple' );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$denied = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $pictures( $structure['model'], 'denied' ), $structure['hash'] );
$emptied = array_filter( [ 'denied', 'denied-behind', 'denied-column', 'denied-copy' ], static function( string $name ) use ( $slotOf ): bool {
	$slot = $slotOf( '/template/page-texts/z/'. $name );
	return $slot !== [] && ( $slot['alt'] ?? [] ) === [] && ( $slot['filename'] ?? null ) === null;
} );
check( 'the same save by an account that may make slots but may not manage images is not refused for it: 200 - every slot is made, with its label and its size, and none of them has an alt text',
	$denied['status'] === 200 && count( $emptied ) === 4 && $slotOf( '/template/page-texts/z/denied' )['label'] === 'Image' && $slotOf( '/template/page-texts/z/denied' )['width'] === 600 && $slotOf( '/template/page-texts/z/denied-copy' )['height'] === 400
	&& str_contains( (string) file_get_contents( $textsPath ), '[image /template/page-texts/z/denied' ) === true && str_contains( (string) file_get_contents( $textsPath ), '[image /template/page-texts/z/denied-copy' ) === true );
check( '...the slot that is a copy takes over neither the alt texts nor the picture of the slot it is a copy of: it is made empty - no file is stored for it - and the slot it is a copy of is as it was',
	\Nino\Images::read( $appData, 'template/page-texts/z/denied-copy.800x400.jpg' ) === false && $slotOf( '/template/page-texts/src/photo' )['filename'] === $copyFrom
	&& $slotOf( '/template/page-texts/src/photo' )['alt'] === [ 'en_US' => 'A card', 'de_DE' => 'Eine Karte', 'xx_XX' => 'A language that is gone' ] );

// Nothing else is loosened: the permission of the Slots tab is asked for as it was, whether or not the account may manage images
\Nino\Auth::loginUser( $appData, 'noslots@example.com', 'correct horse battery staple' );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$beforeRefusal = $stateNow();
$noSlotsAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $pictures( $structure['model'], 'refused' ), $structure['hash'] );
check( 'an account that may not make slots is refused as it was - 403, the permission of the Slots tab named and no other, before anything is made: no slot, nothing in the config, the template as it was',
	$noSlotsAnswer['status'] === 403 && $noSlotsAnswer['code'] === 'permission' && $noSlotsAnswer['problems'] === [ '/_admin/slots/manage' ] && $stateNow() === $beforeRefusal && $slotOf( '/template/page-texts/z/refused' ) === [] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$copying = static function( string $slot, array $create ) use ( $structure ): array {
	$model = $structure['model'];
	$model['blocks'][0]['background'] = [ 'slot' => $slot, 'focus' => null, 'create' => $create ];
	return $model;
};
$copied = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copied', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/photo' ] ), $structure['hash'] );
$copiedSlot = \Nino\Images::getSlot( $appData, '/template/page-texts/z/copied' ) ?: [];
check( 'a slot that is made as the copy of another - create.from, which the panel sends for each picture a copy of a column, a card or a section has - is made with the alt texts of the old one, in the languages the site has, and with its picture: the same bytes, stored again under the name of the new slot, so that taking the one away leaves the other its picture',
	$copied['status'] === 200 && ( $copiedSlot['alt'] ?? [] ) === [ 'en_US' => 'A card', 'de_DE' => 'Eine Karte' ] && is_string( $copiedSlot['filename'] ?? null ) === true && $copiedSlot['filename'] !== $copyFrom
	&& str_starts_with( (string) $copiedSlot['filename'], 'template/page-texts/z/copied' ) === true && \Nino\Images::read( $appData, (string) $copiedSlot['filename'] ) === 'the bytes of a card picture'
	&& \Nino\Images::read( $appData, $copyFrom ) === 'the bytes of a card picture' && ( \Nino\Images::getSlot( $appData, '/template/page-texts/src/photo' ) ?: [] )['filename'] === $copyFrom
	&& ( \Nino\Images::getSlot( $appData, '/template/page-texts/src/photo' ) ?: [] )['alt']['xx_XX'] === 'A language that is gone' && ( $copiedSlot['width'] ?? 0 ) === 800 );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$typedOver = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copied-typed', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/photo', 'alt' => [ 'de_DE' => 'Meine Karte' ] ] ), $structure['hash'] );
check( '...what was typed for the new slot is written over what it was copied with, language by language: the language that was not typed has the text of the old slot', $typedOver['status'] === 200
	&& ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copied-typed' ) ?: [] )['alt'] === [ 'en_US' => 'A card', 'de_DE' => 'Meine Karte' ] && is_string( ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copied-typed' ) ?: [] )['filename'] ?? null ) === true );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$cardCopy = $structure['model'];
$cardCopy['blocks'][0]['cols'][0]['components'][] = [ 'name' => 'card', 'source' => '', 'text' => null, 'attributes' => array_replace( $registry['components']['card']['defaults'], [ 'background' => '/template/page-texts/z/card-copy' ] ), 'components' => [],
	'slots' => [ 'background' => [ 'label' => 'Card - Background picture', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/photo' ] ] ];
$cardCopied = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $cardCopy, $structure['hash'] );
$copiedCardSlot = \Nino\Images::getSlot( $appData, '/template/page-texts/z/card-copy' ) ?: [];
check( '...a card that is a copy has the picture behind it as the original has it: the slot of its background attribute is made with the file and the alt texts of the old one', $cardCopied['status'] === 200 && ( $copiedCardSlot['alt'] ?? [] ) === [ 'en_US' => 'A card', 'de_DE' => 'Eine Karte' ]
	&& is_string( $copiedCardSlot['filename'] ?? null ) === true && \Nino\Images::read( $appData, (string) $copiedCardSlot['filename'] ) === 'the bytes of a card picture' && str_contains( (string) file_get_contents( $textsPath ), 'background="/template/page-texts/z/card-copy"' ) === true );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$bareCopy = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copy-of-bare', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/bare' ] ), $structure['hash'] );
$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$goneCopy = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copy-of-gone', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/gone' ] ), $structure['hash'] );
check( '...a copy of a slot that has none of it - no picture, no alt texts - or of one that is not there, which this save may be making itself, is a slot like any new one: the save asks for nothing and hands nothing on', $bareCopy['status'] === 200 && $goneCopy['status'] === 200
	&& ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copy-of-bare' ) ?: [] )['filename'] === null && ( ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copy-of-bare' ) ?: [] )['alt'] ?? [] ) === []
	&& \Nino\Images::getSlot( $appData, '/template/page-texts/z/copy-of-gone' ) !== false && ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copy-of-gone' ) ?: [] )['filename'] === null );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$beforeBad = $stateNow();
$badFrom = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copy-bad', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => 'not a slot' ] ), $structure['hash'] );
$arrayFrom = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copy-bad', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => [ '/template/page-texts/src/photo' ] ] ), $structure['hash'] );
check( '...what it says it is a copy of is a slot uri: anything else is refused with 400 (invalid) before anything is made', $badFrom['status'] === 400 && $badFrom['code'] === 'invalid' && $arrayFrom['status'] === 400 && $arrayFrom['code'] === 'invalid' && \Nino\Images::getSlot( $appData, '/template/page-texts/z/copy-bad' ) === false
	&& $stateNow() === $beforeBad );

$structure = \Nino\Modules\Builder\Document::load( $appData, 'page-texts' );
$copiedAgain = \Nino\Modules\Builder\Document::save( $appData, 'page-texts', $copying( '/template/page-texts/z/copied', [ 'label' => 'Copied', 'width' => 800, 'height' => 400, 'from' => '/template/page-texts/src/bare' ] ), $structure['hash'] );
check( '...a slot that is there already is left as it is, the picture it has and the alt texts: what a mark says is made only of a slot that is not', $copiedAgain['status'] === 200 && ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copied' ) ?: [] )['alt'] === [ 'en_US' => 'A card', 'de_DE' => 'Eine Karte' ]
	&& is_string( ( \Nino\Images::getSlot( $appData, '/template/page-texts/z/copied' ) ?: [] )['filename'] ?? null ) === true );

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
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][0] += [ 'create' => [], 'values' => [ 'en_US' => 'Our story' ] ];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][1] += [ 'create' => [], 'values' => [ 'en_US' => '<p>It began in a garage.</p>' ] ];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][2] += [ 'create' => [], 'values' => [ 'en_US' => 'Read on' ] ];
$kidsMade['blocks'][0]['cols'][1]['components'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-home-card/story/note', 'text' => null, 'attributes' => $registry['components']['text']['defaults'], 'create' => [], 'values' => [ 'en_US' => '<p>A note.</p>' ] ];
$kidsAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $kidsMade, $cardLoad['hash'] );
$stored = static fn( string $key, string $locale = 'en_US' ): ?string => \Nino\Text::entry( $appData, $key )['values'][$locale] ?? null;

check( 'the keys the children of a card name as new are made at the save, as those of the components of a column are: with the text typed for them, in the language it was typed in, and none in the other - and the file is the page with the fourth child, the card closing below it',
	$kidsAnswer['status'] === 200 && $stored( '/template/page-home-card/story/title' ) === 'Our story' && $stored( '/template/page-home-card/story/title', 'de_DE' ) === '' && $stored( '/template/page-home-card/story/text' ) === '<p>It began in a garage.</p>'
	&& $stored( '/template/page-home-card/story/button' ) === 'Read on' && $stored( '/template/page-home-card/story/note' ) === '<p>A note.</p>'
	&& str_contains( (string) file_get_contents( $cardPath ), "\t\t\t\t[button /template/page-home-card/story/button href=\"/contact\"]\n\t\t\t\t[text /template/page-home-card/story/note]\n\t\t\t[/card]\n" ) === true );
check( '...the format of a key is the one its component asks for - the text of a card is blocks, the title of a card plain - and the answer is the file as it reads, without the marks',
	( \Nino\Text::entry( $appData, '/template/page-home-card/story/text' )['format'] ?? '' ) === 'blocks' && ( \Nino\Text::entry( $appData, '/template/page-home-card/story/title' )['format'] ?? '' ) === 'plain'
	&& isset( $cardAt( $kidsAnswer['model'] )['components'][0]['create'] ) === false && array_column( $cardAt( $kidsAnswer['model'] )['components'], 'name' ) === [ 'card-title', 'text', 'button', 'text' ] );

// The kernel renders what the builder wrote: the card around its children, the loop with a card in each cell - in the language the texts were typed in
$localeWas = \Nino\Locales::getCurrentLocale( $appData );
\Nino\Locales::useLocale( $appData, 'en_US' );
$cardPage = \Nino\Html::renderHtml( $appData, '[template /templates/page-home-card]' );
\Nino\Locales::useLocale( $appData, $localeWas );
check( 'the kernel renders the page with a card: the column with its spacing, the card around its children with the ground and the padding, the loop with a full-height dimmed card in each cell - no call left in the page',
	preg_match( '/\[\/?(?:title|card|card-title|text|image|button|stack)[ \]]/', $cardPage ) === 0 && str_contains( $cardPage, '<div class="nino-grid-100 nino-grid-s-100 nino-grid-m-66 nino-grid-l-66 nino-mt-1 nino-mb-1 nino-p-2">' ) === true
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
	&& str_contains( (string) file_get_contents( $cardPath ), '[card padding="3" ground="counter" background="/template/page-home-card/story/card-picture" focus="3" dim="1"]' ) === true );
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
	&& str_contains( (string) file_get_contents( $cardPath ), '[card padding="3" ground="counter"]' ) === true );

// What a card is made of, and what it is not
$bounds = \Nino\Modules\Builder\Document::load( $appData, 'page-home-card' );
$refusedCard = static function( callable $change ) use ( &$bounds, &$appData, $cardPath ): array {
	$model = $bounds['model'];
	$change( $model );
	$before = (string) file_get_contents( $cardPath );
	$answer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $model, $bounds['hash'] );
	return [ $answer['status'], $answer['code'] ?? '', implode( ' | ', $answer['problems'] ?? [] ), (string) file_get_contents( $cardPath ) === $before ];
};

// The six sides of the spacing are steps of the space scale on every element that has them: a section, a column, a component and a child of one that holds components
$spacedWhere = [
	'section'		=> [ [ 'blocks', 0, 'settings' ], static fn( string $key ): string => 'the '. $key. ' of block 1 is none of the steps 0 to 6' ],
	'column'		=> [ [ 'blocks', 0, 'cols', 1 ], static fn( string $key ): string => 'the '. $key. ' of column 2 of block 1 is none of the steps 0 to 6' ],
	'card'			=> [ [ 'blocks', 0, 'cols', 1, 'components', 0 ], static fn( string $key ): string => 'block 1 has the component "card" with a '. $key. ' that is none of the steps 0 to 6' ],
	'child'			=> [ [ 'blocks', 0, 'cols', 1, 'components', 0, 'components', 0 ], static fn( string $key ): string => 'block 1 has the component "card-title" with a '. $key. ' that is none of the steps 0 to 6' ],
	'picture'		=> [ [ 'blocks', 0, 'cols', 0, 'components', 0 ], static fn( string $key ): string => 'block 1 has the component "image" with a '. $key. ' that is none of the steps 0 to 6' ],
];
$spacedBad		= [ '7', '10', 'big', 3, true, [ '1' ] ];
$spacedWrong	= [];

foreach( $spacedWhere as $where => [ $path, $sentence ] )
	foreach( $reader::SPACING as $at => $key ) {

		$value	= $spacedBad[$at];
		$answer	= $refusedCard( static function( array &$model ) use ( $path, $key, $value ): void {
			$node = &$model;
			foreach( $path as $step )
				$node = &$node[$step];
			$node[$key] = $value;
		} );

		if( $answer[0] !== 400 || $answer[1] !== 'invalid' || str_contains( $answer[2], $sentence( $key ) ) === false || $answer[3] !== true )
			$spacedWrong[] = $where. ' '. $key. '='. json_encode( $value );
	}

check( 'a side of the spacing that is none of the steps 0 to 6 - a step too far, a word, a number where the model has the step as text, a switch, a list - is refused with 400 (invalid) on a section, a column, a component and a child of one, the key, the element and the block named, and the file is as it was: '
	. count( $spacedWhere ) * count( $reader::SPACING ). ' refusals'. ( $spacedWrong === [] ? '' : ' - '. implode( ', ', $spacedWrong ) ), $spacedWrong === [] );
$spaceRight = $bounds['model'];
foreach( $reader::INSIDE as $key )
	$spaceRight['blocks'][0]['cols'][1][$key] = '0';
$spaceRight['blocks'][0]['cols'][1]['mt'] = '6';
$spaceRight['blocks'][0]['settings']['pr'] = '5';
$spaceRight['blocks'][0]['cols'][1]['components'][0]['pl'] = '6';
$spaceRight['blocks'][0]['cols'][0]['components'][0]['mb'] = '0';
$spaceRightAnswer = \Nino\Modules\Builder\Document::save( $appData, 'page-home-card', $spaceRight, $bounds['hash'] );
check( '...and every step of the eight is let through, on every element - a component that is no surface as well, a picture and a card alike: 0 and 6 and the empty one - and written as the classes they say: the four paddings of the column as nino-p-0, the margin of the picture and the padding of the card in their calls',
	$spaceRightAnswer['status'] === 200 && str_contains( (string) file_get_contents( $cardPath ), ' nino-mt-6 nino-mb-1 nino-p-0">' ) === true && str_contains( (string) file_get_contents( $cardPath ), '<section id="story" class="nino-section nino-pr-5">' ) === true
	&& str_contains( (string) file_get_contents( $cardPath ), '[card padding="3" ground="counter" class="nino-pl-6"]' ) === true && str_contains( (string) file_get_contents( $cardPath ), '[image /template/page-home-card/story/photo ratio="4-3" class="nino-mb-0"]' ) === true
	&& $spaceRightAnswer['model']['blocks'][0]['cols'][1]['components'][0]['pl'] === '6' && $spaceRightAnswer['model']['blocks'][0]['cols'][0]['components'][0]['mb'] === '0' );
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
$doomed['blocks'][0]['cols'][1]['components'][0]['components'][] = [ 'name' => 'card-subtitle', 'source' => '/template/page-home-card/tale/lead', 'text' => null, 'attributes' => $registry['components']['card-subtitle']['defaults'], 'create' => [], 'values' => [ 'en_US' => 'Never made' ] ];
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
	&& str_contains( (string) file_get_contents( $cardPath ), '[card padding="3" ground="alt" background="/template/page-home-card/tale/card-picture-2"' ) === true && \Nino\Images::getSlot( $appData, '/template/page-home-card/tale/card-picture-2' ) !== false );

// The panel asks for the file a model would be written as: the card in it, in the lines the Writer makes
$sourceAnswer = \Nino\Modules\Builder\Document::source( $appData, $retryAnswer['model'] );
check( 'source answers the file with the card in it, its children on lines of their own one tab deeper, and the part of the section is the section as a block of the file', $sourceAnswer['status'] === 200 && str_contains( $sourceAnswer['source'], "\t\t\t[/card]\n" ) === true && $sourceAnswer['source'] === (string) file_get_contents( $cardPath )
	&& str_contains( $sourceAnswer['parts'][1]['source'], "\t\t\t\t[card-subtitle /template/page-home-card/tale/lead]\n\t\t\t[/card]\n" ) === true );

\Nino\Modules\Builder\Document::delete( $appData, 'page-home-card' );

echo "\n";



// A second container through the Document: loaded, saved with a child more, and rendered by the kernel - as the page with a card is, with nothing of the card's in it
$bandDoc = $appData;
\Nino\Modules\Components::addComponent( $bandDoc, 'band', $bandRender, $bandSchema );
ninoWarnings();
$bandFile = "<!-- nino:template-name Band -->\n[template /templates/html-header]\n<div class=\"nino-wrap\">\n\n". $section( "[band speed=\"60\"]\n\t\t\t\t[title /template/page-home-band/story/title]\n\t\t\t\t[text /template/page-home-band/story/text]\n\t\t\t[/band]", 'story' ). "\n\n</div>\n[template /templates/html-footer]\n";
\Nino\Filesystem::putFileContent( $appData, '/templates/page-home-band.tpl', $bandFile );
$bandPath	= \Nino\Filesystem::path( $appData, '/templates/page-home-band.tpl' );
$bandLoad	= \Nino\Modules\Builder\Document::load( $bandDoc, 'page-home-band' );
$bandSave	= \Nino\Modules\Builder\Document::save( $bandDoc, 'page-home-band', $bandLoad['model'], $bandLoad['hash'] );
$bandIn		= static fn( array $model ): array => $model['blocks'][0]['cols'][0]['components'][0] ?? [ 'components' => [], 'attributes' => [] ];

check( 'a page with a second container is loaded as the model the Reader makes of it, the band with its two children, and a save without a change leaves the bytes as they were',
	$bandLoad['status'] === 200 && array_column( $bandIn( $bandLoad['model'] )['components'], 'name' ) === [ 'title', 'text' ] && ( $bandIn( $bandLoad['model'] )['attributes']['speed'] ?? '' ) === '60'
	&& $bandSave['status'] === 200 && file_get_contents( $bandPath ) === $bandFile && $bandSave['hash'] === $bandLoad['hash'] && $bandSave['model'] === $bandLoad['model'] );

$bandMade = $bandLoad['model'];
$bandMade['blocks'][0]['cols'][0]['components'][0]['components'][0] += [ 'create' => [], 'values' => [ 'en_US' => 'Our friends' ] ];
$bandMade['blocks'][0]['cols'][0]['components'][0]['components'][1] += [ 'create' => [], 'values' => [ 'en_US' => '<p>They run with us.</p>' ] ];
$bandMade['blocks'][0]['cols'][0]['components'][0]['components'][] = [ 'name' => 'text', 'source' => '/template/page-home-band/story/note', 'text' => null, 'attributes' => $bandRegistry['components']['text']['defaults'], 'create' => [], 'values' => [ 'en_US' => '<p>A note.</p>' ] ];
$bandAnswer = \Nino\Modules\Builder\Document::save( $bandDoc, 'page-home-band', $bandMade, $bandLoad['hash'] );

check( '...a save with a child more writes it inside the band, below the others and above the closing tag, makes the keys the children name as new with the text typed for them - and answers the file as it reads',
	$bandAnswer['status'] === 200 && str_contains( (string) file_get_contents( $bandPath ), "\t\t\t\t[text /template/page-home-band/story/text]\n\t\t\t\t[text /template/page-home-band/story/note]\n\t\t\t[/band]\n" ) === true
	&& ( \Nino\Text::entry( $appData, '/template/page-home-band/story/title' )['values']['en_US'] ?? null ) === 'Our friends' && ( \Nino\Text::entry( $appData, '/template/page-home-band/story/note' )['values']['en_US'] ?? null ) === '<p>A note.</p>'
	&& array_column( $bandIn( $bandAnswer['model'] )['components'], 'name' ) === [ 'title', 'text', 'text' ] && isset( $bandIn( $bandAnswer['model'] )['components'][2]['create'] ) === false );

$bandLocale = \Nino\Locales::getCurrentLocale( $appData );
\Nino\Locales::useLocale( $bandDoc, 'en_US' );
$bandHtml = \Nino\Html::renderHtml( $bandDoc, '[template /templates/page-home-band]' );
\Nino\Locales::useLocale( $bandDoc, $bandLocale );

check( '...and the kernel renders it: the band around its three children in the order they were written, with no call left in the page',
	preg_match( '/\[\/?(?:band|title|text)[ \]]/', $bandHtml ) === 0 && substr_count( $bandHtml, '<div class="band">' ) === 1 && strpos( $bandHtml, '<div class="band">' ) < strpos( $bandHtml, 'Our friends' )
	&& strpos( $bandHtml, 'Our friends' ) < strpos( $bandHtml, 'They run with us.' ) && strpos( $bandHtml, 'They run with us.' ) < strpos( $bandHtml, 'A note.' ) && strpos( $bandHtml, 'A note.' ) < strpos( $bandHtml, '</div>', strpos( $bandHtml, 'A note.' ) ) );

$bandRadius = $bandAnswer['model'];
$bandRadius['blocks'][0]['cols'][0]['components'][0]['radius'] = 'alternative';
$bandOffset = $bandAnswer['model'];
$bandOffset['blocks'][0]['cols'][0]['components'][0]['bgPl'] = '1';
$bandRefused = [ \Nino\Modules\Builder\Document::save( $bandDoc, 'page-home-band', $bandRadius, $bandAnswer['hash'] ), \Nino\Modules\Builder\Document::save( $bandDoc, 'page-home-band', $bandOffset, $bandAnswer['hash'] ) ];
check( 'a container that paints nothing takes no radius and no offset of a ground: a save that says one is refused with 400, the component named, and the file is not written',
	$bandRefused[0]['status'] === 400 && str_contains( implode( ' ', $bandRefused[0]['problems'] ), 'the component "band" with a radius, and it is no surface' ) === true
	&& $bandRefused[1]['status'] === 400 && str_contains( implode( ' ', $bandRefused[1]['problems'] ), 'the component "band" with a bgPl, and it is no card' ) === true
	&& file_get_contents( $bandPath ) === \Nino\Modules\Builder\Document::load( $bandDoc, 'page-home-band' )['source'] );
unlink( $bandPath );

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

// The file of a name is made with the kernel's table, every letter of it: a letter is written out the way the table says, and not taken for a hyphen
$unwritten = [];
$unslugged = [];

foreach( \Nino\Feature::TRANSLITERATIONS as $letter => $plain ) {

	$made = \Nino\Modules\Builder\Document::create( $appData, 'Q'. $letter. 'Q' );

	if( ( $made['model']['file'] ?? '' ) !== 'page-q'. $plain. 'q' )
		$unwritten[] = $letter;

	if( ( $made['model']['file'] ?? '' ) !== 'page-'. \Nino\Feature::slug( 'Q'. $letter. 'Q' ) )
		$unslugged[] = $letter;

	\Nino\Modules\Builder\Document::delete( $appData, (string) ( $made['model']['file'] ?? 'page-none' ) );
}

check( 'the file of a new template is the kernel\'s slug of its name, for every letter the table of the kernel has - '. count( \Nino\Feature::TRANSLITERATIONS ). ' of them, not the few a table of its own had', $unwritten === [] && $unslugged === [] && count( \Nino\Feature::TRANSLITERATIONS ) > 17 );

$odd = array_key_last( \Nino\Feature::TRANSLITERATIONS );
$source = \Nino\Modules\Builder\Document::create( $appData, 'Slug Source' );
$sluggedCopy = \Nino\Modules\Builder\Document::duplicate( $appData, 'page-slug-source', 'Copy Q'. $odd. 'Q' );
check( '...and so is the file of a copy, in the same way', $source['status'] === 200 && $sluggedCopy['status'] === 200 && $sluggedCopy['model']['file'] === 'page-copy-q'. \Nino\Feature::TRANSLITERATIONS[$odd]. 'q' );

foreach( [ 'page-slug-source', (string) ( $sluggedCopy['model']['file'] ?? 'page-none' ) ] as $file )
	\Nino\Modules\Builder\Document::delete( $appData, $file );

check( '...and a name that is the one that was already made stays the file it was: the umlauts and the accents of the first table give the files they gave', \Nino\Modules\Builder\Document::create( $appData, 'Über Äpfel & Öl, ça va' )['model']['file'] === 'page-ueber-aepfel-oel-ca-va'
	&& \Nino\Modules\Builder\Document::delete( $appData, 'page-ueber-aepfel-oel-ca-va' )['status'] === 200 );

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
check( 'the sections that follow the switch lose their bare nino-vpa where it is turned off, the one with a kind of its own does not - on the row, where the animation of a section stands - and the wrap loses nino-wrap--vpa, and keeps its kind', $wrapSave['status'] === 200 && str_contains( $wrapText, '<div class="nino-wrap nino-vpa--zoom-soft nino-vpa--speed-medium">' ) === true
	&& str_contains( $wrapText, "<section id=\"hero\" class=\"nino-section nino-section--black nino-cover nino-cover--dim\" data-cover-height=\"100\">\n\t<div class=\"nino-section-bg nino-img-focus--5\">[image /template/page-home/hero/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row nino-grid-row--wide nino-grid-middle\">\n" ) === true
	&& str_contains( $wrapText, "<section id=\"services\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n" ) === true
	&& str_contains( $wrapText, "<section id=\"contact\" class=\"nino-section nino-section--primary nino-text-center\">\n\t<div class=\"nino-grid-row nino-grid-row--narrow nino-vpa nino-vpa--flip-hard nino-vpa--speed-fast\">\n" ) === true
	&& $wrapSave['model']['animate'] === false && $wrapSave['model']['blocks'][3]['settings']['vpa'] === 'flip-hard' );

$wrapEdit = $follow( $wrapSave['model'], true );
$wrapSave = \Nino\Modules\Builder\Document::save( $appData, 'page-wrap', $wrapEdit, $wrapSave['hash'] );
$wrapText = (string) file_get_contents( $wrapPath );
check( '...and where it is turned on again every section without an animation is given the bare nino-vpa on its row, and the one with its own is left as it was', $wrapSave['status'] === 200 && str_contains( $wrapText, '<div class="nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium">' ) === true
	&& str_contains( $wrapText, "\t<div class=\"nino-grid-row nino-grid-row--wide nino-grid-middle nino-vpa\">\n" ) === true && str_contains( $wrapText, "<section id=\"services\" class=\"nino-section\">\n\t<div class=\"nino-grid-row nino-vpa\">\n" ) === true
	&& str_contains( $wrapText, "\t<div class=\"nino-grid-row nino-grid-row--narrow nino-vpa nino-vpa--flip-hard nino-vpa--speed-fast\">\n" ) === true && $wrapSave['model'] === $wrapEdit );

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

// A template with texts in two languages, a global one, a limit, a slot with a picture and alt texts, a block of html that names a key
$srcFile = "<!-- nino:template-name Dup -->\n[template /templates/html-header]\n<div class=\"nino-wrap nino-wrap--vpa nino-vpa--blur-soft\">\n\n"
	. "<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-section-bg\">[image /template/page-dup/one/background alt=\"\"]</div>\n\t<div class=\"nino-grid-row nino-vpa\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-dup/one/title]\n\t\t\t[text /template/page-dup/one/text]\n\t\t</div>\n\t</div>\n</section>\n\n"
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

check( 'the page renders through the kernel with no call of a component, a stack or a template left in it, and no fill but the label of a control, which the texts of the kernel\'s base unit make', preg_match( '/\[\/?(?:title|subtitle|text|image|button|html|spacer|stack|slider|filter|list|template|osm|'. preg_quote( \Nino\Modules\Builder\Reader::ARROW, '/' ). ')[ \]]/', $page ) === 0
	&& preg_match( '/\[\[/', (string) preg_replace( '/ aria-label="\[\[[^"]*\]\]"/', '', $page ) ) === 0 && $page !== '' );
check( '...the head and the foot are the frames it names', str_contains( $page, "\n<header>Short</header>\n" ) === true && str_ends_with( trim( $page ), '<footer>Footer</footer>' ) === true );
check( '...the components are the kernel\'s: the heading with its tag and style, the button with its address, the texts of the keys', str_contains( $page, '<h3 class="nino-section-title nino-section-title--loud">Hello</h3>' ) === true
	&& str_contains( $page, '<a class="nino-btn nino-btn--primary" href="/contact">Contact us</a>' ) === true && str_contains( $page, '<h3 class="nino-section-title">Services</h3>' ) === true );
$tagged = $write( $read( $section( "[title /template/page-home/contact/title tag=\"h4\" style=\"loud\"]\n\t\t\t[subtitle /template/page-home/contact/title]\n\t\t\t[subtitle /template/page-home/contact/title tag=\"h5\"]\n\t\t\t[card-title /template/page-home/contact/title]" ). "\n" ) );
$drawn = \Nino\Html::renderHtml( $appData, $tagged );
check( '...the tag the builder writes is the tag the kernel draws: a title is an h4 where the call names h4, a subtitle a div where it names none and an h5 where it names h5, a title of a card an h4 where it names none - and the call of a default says nothing', str_contains( $tagged, '[title /template/page-home/contact/title tag="h4" style="loud"]' ) === true
	&& str_contains( $tagged, "[subtitle /template/page-home/contact/title]\n" ) === true && str_contains( $tagged, '[subtitle /template/page-home/contact/title tag="h5"]' ) === true && str_contains( $tagged, "[card-title /template/page-home/contact/title]\n" ) === true
	&& str_contains( $drawn, '<h4 class="nino-section-title nino-section-title--loud">Contact</h4>' ) === true && str_contains( $drawn, '<div class="nino-section-subtitle">Contact</div>' ) === true
	&& str_contains( $drawn, '<h5 class="nino-section-subtitle">Contact</h5>' ) === true && str_contains( $drawn, '<h4 class="nino-card-title">Contact</h4>' ) === true );

// A link to a page with a sub-path leads to the page where the site is served from: the directory is in front of it, as the kernel puts it in front of the key of a page alone
$subLink = $write( $read( $section( '[button text="Go" href="[[/nino/dir]][[/_nino/webpage/contact/uri]]/sub?q=1"]' ) ) );
$dirWas = $appData['/nino/dir'];
$rendered = [];

foreach( [ '', '/shop' ] as $served ) {
	$appData['/nino/dir'] = $served;
	\Nino\Html::addFills( $appData, \Nino\Html::bootFills( $appData ), '*' );
	$rendered[$served] = [ \Nino\Html::renderHtml( $appData, $subLink ), \Nino\Html::renderHtml( $appData, '[button text="Go" href="/_nino/webpage/contact/uri"]' ) ];
}

$appData['/nino/dir'] = $dirWas;
\Nino\Html::addFills( $appData, \Nino\Html::bootFills( $appData ), '*' );
check( 'a link to a page with a sub-path is written as the call has it and renders as the address of the page with the sub-path behind it - /contact/sub?q=1 at the root of a host, /shop/contact/sub?q=1 where the site is served from /shop, the directory the page alone has in front of it as well',
	str_contains( $subLink, 'href="[[/nino/dir]][[/_nino/webpage/contact/uri]]/sub?q=1"' ) === true && str_contains( $rendered[''][0], 'href="/contact/sub?q=1"' ) === true && str_contains( $rendered['/shop'][0], 'href="/shop/contact/sub?q=1"' ) === true
	&& str_contains( $rendered['/shop'][1], 'href="/shop/contact"' ) === true );
$appData['/nino/html/images']['/template/page-x/a/hover'] = [ 'label' => 'Hover', 'width' => 1600, 'height' => 900, 'filename' => 'hover.1600x900.jpg' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
$hoverMade = $read( $section( "[image /template/page-x/a/hover]\n\t\t\t[card]\n\t\t\t\t[card-image /template/page-x/a/hover]\n\t\t\t[/card]" ). "\n" );
$hoverMade['blocks'][0]['cols'][0]['components'][0]['hover'] = 'on';
$hoverMade['blocks'][0]['cols'][0]['components'][1]['components'][0]['hover'] = 'on';
$hovered = $write( $hoverMade );
$hoveredDrawn = \Nino\Html::renderHtml( $appData, $hovered );
unset( $appData['/nino/html/images']['/template/page-x/a/hover'] );
\Nino\AppData::writeContentData( $appData, [ '/nino/html/images' ] );
check( '...the hover the builder writes is the class the kernel frames a picture for: switched on, the picture of [image] and the picture of a card, which has neither a ratio nor a focus, are in a frame that carries it, once each',
	substr_count( $hovered, 'class="nino-hover"' ) === 2 && preg_match_all( '~<div class="nino-image[^"]*\bnino-hover\b[^"]*"><img ~', $hoveredDrawn ) === 2 && substr_count( $hoveredDrawn, 'nino-card-img nino-hover' ) === 1 );
check( '...the stack is a loop of the elements, one cell each, in the order it names', preg_match_all( '/nino-grid-s-100 nino-grid-m-50 nino-grid-l-50 nino-autoheight/', $page ) === 2
	&& strpos( $page, 'App' ) < strpos( $page, 'Web' ) && str_contains( $page, 'Mehr</a>' ) === true );
check( '...and the sections are what Nino.css knows: the cover with its height, the background block, the row and its columns',
	str_contains( $page, '<section id="start" class="nino-section nino-section--black nino-cover nino-cover--dim" data-cover-height="100">' ) === true
	&& str_contains( $page, '<div class="nino-section-bg nino-img-focus--5">' ) === true && str_contains( $page, '<div class="nino-grid-row nino-grid-row--wide nino-grid-middle nino-vpa">' ) === true );
[ $arrowTag, $arrowClass ] = explode( ' .', (string) $registry['components'][\Nino\Modules\Builder\Reader::ARROW]['draws'], 2 );
check( '...the arrow of a section is the last thing in it, after its row: the element the registry says the scroll hint draws, once - for the one section of the page that has the setting',
	preg_match_all( '~<'. preg_quote( $arrowTag, '~' ). ' [^>]*class="[^"]*\b'. preg_quote( $arrowClass, '~' ). '\b~', $page ) === 1
	&& preg_match( '~\t</div>\n\t<'. preg_quote( $arrowTag, '~' ). ' [^>]*\b'. preg_quote( $arrowClass, '~' ). '\b[^>]*></'. preg_quote( $arrowTag, '~' ). '>\n</section>~', $page ) === 1 );
check( '...a block of html written by hand is in the page as it was written', str_contains( $page, '<p>Imprint note</p>' ) === true && str_contains( $page, '<div class="map"></div>' ) === true );
$loopDrawn = \Nino\Html::renderHtml( $appData, $inWrap( $loopFile ) );
check( '...a loop of sections is a section for each element, one after the other where the call stood: the id and the number of the element, the classes after nino-section, the row and its columns once for each, the element the context of what is in them',
	preg_match_all( '/<section id="news-(\d)" class="nino-section nino-section--alt nino-pt-2">/', $loopDrawn, $drawnIds ) === 2 && $drawnIds[1] === [ '1', '2' ] && strpos( $loopDrawn, '>App<' ) < strpos( $loopDrawn, '>Web<' )
	&& substr_count( $loopDrawn, '<div class="nino-grid-row">' ) === 2 && str_contains( $loopDrawn, '[sections' ) === false && str_contains( $loopDrawn, '[/sections]' ) === false && str_starts_with( $loopDrawn, "<div class=\"nino-wrap\">\n\n<section id=\"news-1\"" ) === true );
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
$notASwitch['blocks'][0]['settings']['parallax'] = 'yes';
$switchHash = \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['hash'];
[ $status, $switchBody ] = callBuilderAction( $appData, 'apiSave', [ 'file' => 'page-home', 'model' => $notASwitch, 'hash' => $switchHash ] );
check( '...a parallax that is no bool: 400, builder_invalid, the switch and its block in the params - and the file is as it was', $status === 400 && $switchBody['code'] === 'builder_invalid'
	&& $switchBody['params'] === [ 'the parallax of block 1 is neither on nor off' ] && \Nino\Modules\Builder\Document::load( $appData, 'page-home' )['hash'] === $switchHash );

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
