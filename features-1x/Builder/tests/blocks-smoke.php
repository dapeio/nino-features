<?php
declare(strict_types=1);

/**
 *	Nino
 *	blocks-smoke.php		The building blocks the Builder brings: what every directory of
 *											blocks/ has to be - described to the letter, one section in the
 *											grammar of the Builder that the Reader reads whole and the Writer
 *											writes back byte for byte, no class of its own, no colour, no
 *											pixel, the words of both languages under the same keys, every
 *											component the kernel's or named - and how the Library answers:
 *											the order of the dialog, the blocks that are left out with a
 *											warning (a field that is none, a field that is missing, a slug
 *											that is taken, a template that is not one section, a source
 *											outside the keys of the block, words that are not the same in
 *											both languages), a block that needs a feature that is off and so
 *											is not read, and the words each language of a site gets - the
 *											German for a German language, the English for an English one and
 *											for the site's own where that is neither, nothing for the others.
 *											It writes fixtures/blocks.json, the answer of the Library the
 *											panel's script test reads, as builder-smoke.php writes the
 *											others.
 *
 *	Usage: php features/Builder/tests/blocks-smoke.php
 *	       NINO_ROOT=../nino php features/Builder/tests/blocks-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'blocks' );
$appData['/nino/dir'] = '';
$appData['./nino/locales/current'] = 'en_US';
$appData['/nino/locales/native'] = 'en_US';
$appData['/nino/locales/available'] = [ 'en_US', 'de_DE' ];

\Nino\Modules\Components::init( $appData );

$dir			= dirname( __DIR__ ). '/blocks';
$registry	= \Nino\Modules\Builder\Document::schemas( $appData );
$slugs		= array_values( array_filter( scandir( $dir ) ?: [], static fn( string $entry ): bool => $entry[0] !== '.' && is_dir( $dir. '/'. $entry ) === true ) );

/**
 *	A block written into a directory
 *
 *	@param		string		$base					The blocks directory
 *	@param		string		$slug
 *	@param		array			$described		block.php; the defaults are a valid one
 *	@param		string|null	$template		The template; null for the default section
 *	@param		array|null	$words				locale => key => word; null for the default words
 *
 *	@return 	void
 */
function blocksWrite( string $base, string $slug, array $described = [], ?string $template = null, ?array $words = null ): void {

	$described += [
		'name' => [ 'en_US' => 'Name', 'de_DE' => 'Name' ], 'description' => [ 'en_US' => 'One sentence.', 'de_DE' => 'Ein Satz.' ],
		'category' => 'content', 'family' => $slug, 'rank' => 10, 'features' => [],
	];
	$words ??= [ 'en_US' => [ '/template/block-'. $slug. '/one/title' => 'Title' ], 'de_DE' => [ '/template/block-'. $slug. '/one/title' => 'Titel' ] ];

	@mkdir( $base. '/'. $slug. '/text', 0755, true );
	file_put_contents( $base. '/'. $slug. '/block.php', '<?php return '. var_export( $described, true ). ";\n" );
	file_put_contents( $base. '/'. $slug. '/block-'. $slug. '.tpl', $template ?? "<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/block-". $slug. "/one/title]\n\t\t</div>\n\t</div>\n</section>\n" );

	foreach( $words as $locale => $list )
		file_put_contents( $base. '/'. $slug. '/text/'. $locale. '.php', '<?php return '. var_export( $list, true ). ";\n" );
}

// --- 1. What every block of the feature is -----------------------------------

echo "The blocks of the feature\n";

$ignored = ninoWarnings();
$offered = \Nino\Modules\Builder\Library::blocks( $appData, $dir );
$warnings = ninoWarnings();

check( 'the Library offers every directory of blocks/ - there are some, no warning, none left out', $slugs !== [] && count( $offered ) === count( $slugs )
	&& $warnings === [] && array_diff( $slugs, array_column( $offered, 'slug' ) ) === [] );

$bad = [];
$sections = [];

foreach( $slugs as $slug ) {

	$folder		= $dir. '/'. $slug;
	$files		= array_values( array_diff( scandir( $folder ) ?: [], [ '.', '..' ] ) );
	sort( $files );

	if( $files !== [ 'block-'. $slug. '.tpl', 'block.php', 'text' ] || array_values( array_diff( scandir( $folder. '/text' ) ?: [], [ '.', '..' ] ) ) !== [ 'de_DE.php', 'en_US.php' ] )
		$bad[] = $slug. ' has other files than block.php, the template and the two languages';

	$source = (string) file_get_contents( $folder. '/block-'. $slug. '.tpl' );
	[ $section, $reason ] = \Nino\Modules\Builder\Reader::readSection( rtrim( $source ), $registry );

	if( $section === null ) {
		$bad[] = $slug. ' is not one readable section';
		continue;
	}

	$sections[$slug] = $section;

	if( \Nino\Modules\Builder\Writer::block( $section, $registry ). "\n" !== $source )
		$bad[] = $slug. ' is not what the Writer writes of what the Reader reads';
}

check( 'every template is one section the Reader reads whole - and the Writer writes it back byte for byte, in the file as it stands (every directory)', $bad === [] && count( $sections ) === count( $slugs ) );
if( $bad !== [] )
	echo '      '. implode( '; ', $bad ). "\n";

$own = [];
$loose = [];
$foreign = [];
$sourced = [];
$wordsOf = static function( string $slug, string $locale ) use ( $dir ): array { return array_keys( (array) include $dir. '/'. $slug. '/text/'. $locale. '.php' ); };
$walk = static function( array $components, callable $each ) use ( &$walk ): void {
	foreach( $components as $component ) {
		$each( $component );
		$walk( (array) ( $component['components'] ?? [] ), $each );
		foreach( (array) ( $component['panels'] ?? [] ) as $panel )
			$walk( (array) ( $panel['components'] ?? [] ), $each );
	}
};

foreach( $sections as $slug => $section ) {

	$source = (string) file_get_contents( $dir. '/'. $slug. '/block-'. $slug. '.tpl' );
	$described = (array) include $dir. '/'. $slug. '/block.php';

	// Nothing of its own: no class the vocabulary does not write, no colour, no pixel, no style, no script, no picture of its own
	if( $section['settings']['custom'] !== '' || $section['settings']['rowCustom'] !== '' || $section['background'] !== null || $section['stack'] !== null || $section['rowLoop'] !== null )
		$own[] = $slug. ' section';

	foreach( $section['cols'] as $col ) {

		if( $col['custom'] !== '' || $col['background'] !== null )
			$own[] = $slug. ' column';

		$walk( $col['components'], static function( array $component ) use ( &$own, &$foreign, &$sourced, $slug, $registry, $described ): void {

			if( ( $component['attributes']['class'] ?? '' ) !== '' )
				$own[] = $slug. ' '. $component['name'];

			// The kernel's, or named by the block as what it needs
			$by = (string) ( $registry['components'][$component['name']]['by'] ?? 'nino' );

			if( $by !== 'nino' && in_array( $by, $described['features'], true ) === false )
				$foreign[] = $slug. ' '. $component['name'];

			if( is_string( $component['source'] ?? null ) === true && $component['source'] !== '' )
				$sourced[$slug][] = $component['source'];
		} );
	}

	if( preg_match( '/#[0-9a-fA-F]{3,8}\b|\d\s*(?:px|rem|em|vh|vw)\b|<[a-z][^>]*\sstyle=|<script|<style|\bon[a-z]+=|nino:html/', $source ) === 1 )
		$loose[] = $slug;
}

check( 'a block has no class, no colour, no pixel value, no script and no picture of its own, and no loop - it is the vocabulary of the model and nothing else', $own === [] && $loose === [] );
check( 'every component of a block is the kernel\'s or comes from a feature the block names', $foreign === [] && count( $sourced ) === count( $slugs ) );

// The headings of a block: the first is a second-level heading - the page has its h1 elsewhere - and none skips a level
$levels = [];

foreach( $sections as $slug => $section ) {

	$found = [];
	$walk( array_merge( ...array_map( static fn( array $col ): array => $col['components'], $section['cols'] ) ), static function( array $component ) use ( &$found, $registry ): void {

		$tag = (string) ( $component['attributes']['tag'] ?? $registry['components'][$component['name']]['attributes']['tag']['default'] ?? '' );

		if( preg_match( '/^h([1-6])$/', $tag, $level ) === 1 )
			$found[] = (int) $level[1];
	} );

	if( $found !== [] && $found[0] !== 2 )
		$levels[] = $slug. ' starts at h'. $found[0];

	for( $at = 1; $at < count( $found ); $at++ )
		if( $found[$at] > max( array_slice( $found, 0, $at ) ) + 1 )
			$levels[] = $slug. ' skips from h'. max( array_slice( $found, 0, $at ) ). ' to h'. $found[$at];
}

check( 'the headings of a block start at h2 and skip no level', $levels === [] );
if( $levels !== [] )
	echo '      '. implode( '; ', $levels ). "\n";

$keys = [];

foreach( $sections as $slug => $section ) {

	$prefix = '/template/block-'. $slug. '/'. $section['id']. '/';
	$named = array_values( array_unique( $sourced[$slug] ) );
	$en = $wordsOf( $slug, 'en_US' );
	$de = $wordsOf( $slug, 'de_DE' );
	sort( $en );
	sort( $de );
	sort( $named );

	if( $en !== $de || $en !== $named )
		$keys[] = $slug. ': the keys of the template and of the two languages are not the same';

	foreach( array_unique( $sourced[$slug] ) as $source )
		if( str_starts_with( $source, $prefix ) === false )
			$keys[] = $slug. ': '. $source. ' is not under '. $prefix;

	foreach( [ 'en_US', 'de_DE' ] as $locale )
		foreach( (array) include $dir. '/'. $slug. '/text/'. $locale. '.php' as $word )
			if( is_string( $word ) === false || trim( $word ) === '' || preg_match( '/[\[\]<>]/', $word ) === 1 )
				$keys[] = $slug. ': a word of '. $locale. ' is empty or holds a bracket or a tag';
}

check( 'the keys of a block are the same in the template and in both languages, all under /template/block-<slug>/<section id>/, and every word is text', $keys === [] );
if( $keys !== [] )
	echo '      '. implode( '; ', $keys ). "\n";

$names = [];

foreach( $offered as $entry ) {

	$described = (array) include $dir. '/'. $entry['slug']. '/block.php';
	$names[] = array_keys( $described['name'] ) === [ 'en_US', 'de_DE' ] && array_keys( $described['description'] ) === [ 'en_US', 'de_DE' ]
		&& $described['name']['en_US'] !== $described['name']['de_DE'] && $described['description']['en_US'] !== $described['description']['de_DE'] ? 1 : 0;
}

check( 'a block has its name and its description in English and in German, which differ', array_sum( $names ) === count( $offered ) );
check( 'no slug and no name is there twice, and a family stands in one category', count( array_unique( array_column( $offered, 'slug' ) ) ) === count( $offered ) && count( array_unique( array_column( $offered, 'name' ) ) ) === count( $offered )
	&& count( array_unique( array_map( static fn( array $entry ): string => $entry['category']. '/'. $entry['family'], $offered ) ) ) === count( array_unique( array_column( $offered, 'family' ) ) ) );
check( 'the blocks come in the order of the categories, and by rank within a family: intro, content, offer, action', array_values( array_unique( array_column( $offered, 'category' ) ) ) === [ 'intro', 'content', 'offer', 'action' ]
	&& array_map( static fn( string $category ): int => (int) array_search( $category, \Nino\Modules\Builder\Library::CATEGORIES, true ), array_column( $offered, 'category' ) ) === ( static function( array $at ): array { sort( $at ); return $at; } )( array_map( static fn( string $category ): int => (int) array_search( $category, \Nino\Modules\Builder\Library::CATEGORIES, true ), array_column( $offered, 'category' ) ) ) );

$clip = $offered[0]['clip'];
check( 'a block comes as what Copy puts into the clipboard of the editor: the kind section, the file and the section its keys stand under, the section as a model, the words of each key by language',
	$clip['kind'] === 'section' && $clip['file'] === 'block-'. $offered[0]['slug'] && $clip['section'] === $clip['node']['id'] && $clip['node']['kind'] === 'section'
	&& array_keys( $clip ) === [ 'kind', 'file', 'section', 'node', 'values' ] && array_keys( reset( $clip['values'] ) ) === [ 'en_US', 'de_DE' ] && $offered[0]['needs'] === [] );

// The panel's script reads the answer of the Library as it is
$fixture = json_encode( \Nino\Modules\Builder\Library::blocks( $appData, $dir ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
check( 'fixtures/blocks.json holds the answer of the Library for the script test', $fixture !== false && file_put_contents( __DIR__. '/fixtures/blocks.json', $fixture. "\n" ) !== false );

echo "\n";

// --- 2. How the Library answers ----------------------------------------------

echo "The Library\n";

$temp = ninoSandboxDir( $appData ). '/blocks';
mkdir( $temp );

blocksWrite( $temp, 'second', [ 'rank' => 30, 'family' => 'alpha' ] );
blocksWrite( $temp, 'first', [ 'rank' => 10, 'family' => 'alpha' ] );
blocksWrite( $temp, 'aside', [ 'rank' => 20, 'family' => 'beta' ] );
blocksWrite( $temp, 'early', [ 'rank' => 1, 'family' => 'gamma', 'category' => 'intro' ] );
ninoWarnings();
$listed = \Nino\Modules\Builder\Library::blocks( $appData, $temp );

check( 'blocks are listed by category, then by family where its first block stands, then by rank: a family is together - a block of rank 20 of another family does not come between two of one - and the family of the lowest rank leads', array_column( $listed, 'slug' ) === [ 'early', 'first', 'second', 'aside' ] && ninoWarnings() === [] );

$bad = static function( string $slug, array $described, ?string $template = null, ?array $words = null ) use ( &$appData ): array {

	$base = ninoSandboxDir( $appData ). '/bad-'. $slug. '-'. uniqid();
	mkdir( $base );
	blocksWrite( $base, $slug, $described, $template, $words );
	$answer = \Nino\Modules\Builder\Library::blocks( $appData, $base );

	return [ $answer, ninoWarnings() ];
};

$ok = [ 'name' => [ 'en_US' => 'N', 'de_DE' => 'N' ], 'description' => [ 'en_US' => 'D', 'de_DE' => 'D' ], 'category' => 'content', 'family' => 'f', 'rank' => 1, 'features' => [] ];

[ $answer, $said ] = $bad( 'aaa', $ok + [ 'extra' => 1 ] );
check( 'a field that is not one of the six leaves the block out, with one warning that names it', $answer === [] && count( $said ) === 1 && str_contains( $said[0], '"aaa"' ) === true && str_contains( $said[0], 'extra' ) === true );
$base = ninoSandboxDir( $appData ). '/missing';
mkdir( $base );
blocksWrite( $base, 'bbb' );
file_put_contents( $base. '/bbb/block.php', '<?php return '. var_export( array_diff_key( $ok, [ 'rank' => 1 ] ), true ). ";\n" );
$answer = \Nino\Modules\Builder\Library::blocks( $appData, $base );
$said = ninoWarnings();
check( 'a field that is missing leaves it out', $answer === [] && count( $said ) === 1 );

$shapes = [
	'category' => [ 'nowhere' ], 'family' => [ 'Not A Slug' ], 'rank' => [ '5', -1, 1000, 1.5 ], 'features' => [ 'forms', [ 'Forms' ], [ 5 ], [ 'a' => 'forms' ] ],
	'name' => [ 'Name', [ 'en_US' => 'N' ], [ 'de_DE' => 'N', 'en_US' => 'N' ], [ 'en_US' => 'N', 'de_DE' => '' ], [ 'en_US' => str_repeat( 'x', 61 ), 'de_DE' => 'N' ], [ 'en_US' => "a\nb", 'de_DE' => 'N' ], [ 'en_US' => '[x]', 'de_DE' => 'N' ] ],
	'description' => [ [ 'en_US' => str_repeat( 'x', 201 ), 'de_DE' => 'D' ], [ 'en_US' => 'D', 'de_DE' => 'D', 'fr_FR' => 'D' ] ],
];
$left = 0;
$total = 0;

foreach( $shapes as $field => $values )
	foreach( $values as $value ) {
		$total++;
		[ $answer, $said ] = $bad( 'shape', array_replace( $ok, [ $field => $value ] ) );
		$left += $answer === [] && count( $said ) === 1 ? 1 : 0;
	}

check( 'each field is of its kind - a category of the four, a family a slug, a rank from 0 to 999, features a list of keys, name and description one line of either language, not too long - and one that is not leaves the block out ('. $total. ' variations)', $left === $total );

[ $answer, $said ] = $bad( 'broken', $ok, "<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[nothing /template/block-broken/one/x]</div></div>\n</section>\n" );
check( 'a template with a shortcode that is no component is left out with a warning - and the Reader\'s code in it', $answer === [] && count( $said ) === 1 && str_contains( $said[0], 'unknown-shortcode' ) === true );
[ $answer, $said ] = $bad( 'two', $ok, "<section id=\"one\" class=\"nino-section\"><div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[title /template/block-two/one/title]</div></div></section>\n<section id=\"more\" class=\"nino-section\"><div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[title /template/block-two/more/title]</div></div></section>\n" );
check( 'a template of two sections is left out: a block is one', $answer === [] && count( $said ) === 1 );
[ $answer, $said ] = $bad( 'html', $ok, "<!-- nino:html -->\n<section id=\"one\" class=\"nino-section\"><div class=\"nino-grid-row\"><div class=\"nino-grid-100\">[title /template/block-html/one/title]</div></div></section>\n<!-- /nino:html -->\n" );
check( 'a block of HTML+ is no block', $answer === [] && count( $said ) === 1 );
[ $answer, $said ] = $bad( 'stray', $ok, "<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[title /template/page-home/one/title]\n\t\t</div>\n\t</div>\n</section>\n", [ 'en_US' => [ '/template/block-stray/one/title' => 'T' ], 'de_DE' => [ '/template/block-stray/one/title' => 'T' ] ] );
check( 'a source outside the keys of the block - a key of another page - leaves it out: nothing in a block points into the project', $answer === [] && count( $said ) === 1 && str_contains( $said[0], '/template/page-home/one/title' ) === true );
[ $answer, $said ] = $bad( 'words', $ok, null, [ 'en_US' => [ '/template/block-words/one/title' => 'T' ], 'de_DE' => [ '/template/block-words/one/title' => 'T', '/template/block-words/one/extra' => 'E' ] ] );
check( 'words in one language that the other has not leave the block out', $answer === [] && count( $said ) === 1 );
[ $answer, $said ] = $bad( 'morewords', $ok, null, [ 'en_US' => [ '/template/block-morewords/one/title' => 'T', '/template/block-morewords/one/extra' => 'E' ], 'de_DE' => [ '/template/block-morewords/one/title' => 'T' ] ] );
check( '...and so do words in the other that the first has not', $answer === [] && count( $said ) === 1 );
[ $answer, $said ] = $bad( 'nowords', $ok, null, [ 'en_US' => [ '/template/block-nowords/one/title' => 'T' ] ] );
check( 'a language without a file leaves it out', $answer === [] && count( $said ) === 1 );
[ $answer, $said ] = $bad( 'Upper', $ok );
check( 'a directory that is no slug is no block', $answer === [] && count( $said ) === 1 );

check( 'a directory that is not there offers nothing and warns of nothing', \Nino\Modules\Builder\Library::blocks( $appData, ninoSandboxDir( $appData ). '/nowhere' ) === [] && ninoWarnings() === [] );

// A file that prints anything is no description: a BOM, a blank before the tag, an echo would spoil the answer of the panel
foreach( [ 'a byte order mark' => "\xEF\xBB\xBF", 'a blank' => ' ', 'an echo' => '<?php echo "x"; ?>' ] as $what => $printed ) {

	$base = ninoSandboxDir( $appData ). '/prints-'. md5( $what );
	mkdir( $base );
	blocksWrite( $base, 'prints' );
	$described = (string) file_get_contents( $base. '/prints/block.php' );
	file_put_contents( $base. '/prints/block.php', $printed. $described );
	ob_start();
	$answer = \Nino\Modules\Builder\Library::blocks( $appData, $base );
	$output = (string) ob_get_clean();
	$said = ninoWarnings();
	check( 'a block.php that prints - '. $what. ' - leaves the block out with a warning, and nothing of it reaches the output of the answer', $answer === [] && count( $said ) === 1 && $output === '' );
}

// A block that needs a feature that is off: offered with the name of what it needs, not read. The features are the test's own, put where the Library asks for them
$appData['./nino/features/all'] = [
	'fixture-off'	=> [ 'active' => false, 'name' => [ 'en_US' => 'Fixture feature', 'de_DE' => 'Fixture-Feature' ] ],
	'fixture-on'	=> [ 'active' => true, 'name' => [ 'en_US' => 'Feature that is on', 'de_DE' => 'Feature, das an ist' ] ],
];
$needing = ninoSandboxDir( $appData ). '/needing';
mkdir( $needing );
blocksWrite( $needing, 'wants', [ 'features' => [ 'fixture-off', 'fixture-on', 'nowhere' ] ], "<section id=\"one\" class=\"nino-section\">\n\t<div class=\"nino-grid-row\">\n\t\t<div class=\"nino-grid-100\">\n\t\t\t[fixture /template/block-wants/one/title]\n\t\t</div>\n\t</div>\n</section>\n" );
blocksWrite( $needing, 'ready', [ 'features' => [ 'fixture-on' ] ] );
$answer = array_column( \Nino\Modules\Builder\Library::blocks( $appData, $needing ), null, 'slug' );
$said = ninoWarnings();
check( 'a block that needs features that are off - one that is installed, one that is not - is offered with the names of what it needs (the key for the one that has no manifest), and no section: its components are not there to read it with',
	$answer['wants']['clip'] === null && $answer['wants']['needs'] === [ 'Fixture feature', 'nowhere' ] && $said === [] );
check( 'a block that needs a feature that is on needs nothing, and is read', $answer['ready']['needs'] === [] && $answer['ready']['clip'] !== null );
unset( $appData['./nino/features/all'] );

// The words each language of a site gets
$words = ninoSandboxDir( $appData ). '/words';
mkdir( $words );
blocksWrite( $words, 'say', [], null, [ 'en_US' => [ '/template/block-say/one/title' => 'English' ], 'de_DE' => [ '/template/block-say/one/title' => 'Deutsch' ] ] );
$key = '/template/block-say/one/title';
$valuesFor = static function( array $available, string $native ) use ( &$appData, $words, $key ): array {

	$appData['/nino/locales/available'] = $available;
	$appData['/nino/locales/native'] = $native;

	return \Nino\Modules\Builder\Library::blocks( $appData, $words )[0]['clip']['values'][$key] ?? [];
};

check( 'a site in English and German gets each its own words', $valuesFor( [ 'en_US', 'de_DE' ], 'en_US' ) === [ 'en_US' => 'English', 'de_DE' => 'Deutsch' ] );
check( 'a site in German alone gets the German - no language it has not, which a save would refuse', $valuesFor( [ 'de_DE' ], 'de_DE' ) === [ 'de_DE' => 'Deutsch' ] && $valuesFor( [ 'en_US' ], 'en_US' ) === [ 'en_US' => 'English' ] );
check( 'a language that is neither gets the English where it is the site\'s own - and nothing where it is another', $valuesFor( [ 'fr_FR', 'en_US' ], 'fr_FR' ) === [ 'fr_FR' => 'English', 'en_US' => 'English' ] && $valuesFor( [ 'fr_FR', 'de_DE' ], 'de_DE' ) === [ 'de_DE' => 'Deutsch' ]
	&& $valuesFor( [ 'fr_FR' ], 'fr_FR' ) === [ 'fr_FR' => 'English' ] );
check( 'a variant of a language gets the words of the language', $valuesFor( [ 'de_AT', 'en_GB' ], 'de_AT' ) === [ 'de_AT' => 'Deutsch', 'en_GB' => 'English' ] );

ninoDone( $appData );
