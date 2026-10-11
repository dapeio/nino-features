<?php
declare(strict_types=1);

/**
 *	Nino
 *	design-smoke.php		Contract test for the Design feature (Modules\Design):
 *											the manifest and activation through \Nino\Features, the
 *											library of five parts and what a template must declare
 *											to be one, the setup's normalisation against a library
 *											that is really there, what the compiler puts in the
 *											sheet and in which order - the font roles, the width and
 *											the animation of the page, the knob positions - the
 *											palette and its mode, the roles base.css assigns against
 *											the kernel's Nino.css, the refusal to overwrite a file
 *											Design did not write, and that a project which applies the
 *											defaults keeps the look of the theme.css the setup wizard
 *											delivers. Travels with the feature and runs against the
 *											checkout three levels up, or the one NINO_ROOT names (see
 *											tests/harness.php there). What the panel's own script
 *											draws is design-js-smoke.js beside this file, which this
 *											suite runs too where node is on the path.
 *
 *	Usage: php features/Design/tests/design-smoke.php
 *	       NINO_ROOT=../nino php features/Design/tests/design-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'design' );
// What a request always carries and a sandbox does not - the preview's own
// public prefix comes off it (see the other feature suites)
$appData['/nino/dir'] = '';
$library = dirname( __DIR__ ). '/library';

use Nino\Modules\Design\Admin;
use Nino\Modules\Design\Colours;
use Nino\Modules\Design\Compiler;
use Nino\Modules\Design\Fonts;
use Nino\Modules\Design\Preview;
use Nino\Modules\Design\Previous;
use Nino\Modules\Design\Setup;

/**
 *	A stylesheet without its comments - the declarations of a file read the
 *	way a browser reads them, and not the way a comment about them says them
 *
 *	@param		string		$css
 *
 *	@return 	string
 */
function designPlain( string $css ): string {
	return Setup::uncomment( $css );
}

/**
 *	The body of the first block that starts with $opener, braces counted, as
 *	the text between them
 *
 *	@param		string		$css					Without comments
 *	@param		string		$opener				eg. ':root {'
 *
 *	@return 	string										'' where there is none
 */
function designBlock( string $css, string $opener ): string {

	$at = strpos( $css, $opener );

	if( $at === false )
		return '';

	$depth	= 0;
	$start	= $at + strlen( $opener );

	for( $i = $at, $length = strlen( $css ); $i < $length; $i++ ) {

		if( $css[$i] === '{' )
			$depth++;

		if( $css[$i] === '}' && --$depth === 0 )
			return substr( $css, $start, $i - $start );
	}

	return '';
}

/**
 *	Every custom property a stretch of css declares, as name => value
 *
 *	@param		string		$css
 *
 *	@return 	array
 */
function designDeclarations( string $css ): array {

	$out = [];

	if( preg_match_all( '/(--[a-z0-9-]+)\s*:\s*([^;]+);/', $css, $found, PREG_SET_ORDER ) > 0 )
		foreach( $found as $declaration )
			$out[$declaration[1]] = trim( $declaration[2] );

	return $out;
}

/**
 *	A numbered section of a stylesheet: from its marker to the next one's
 *
 *	@param		string		$css
 *	@param		int				$number
 *	@param		string		$marker				A sprintf pattern with the number and the title
 *
 *	@return 	string										'' where there is none
 */
function designSection( string $css, int $number, string $marker ): string {

	$regex = static fn( int $at ): string => '#'. str_replace( [ '%d', '%s' ], [ (string) $at, '[^*]*?' ], preg_quote( $marker, '#' ) ). '#';

	if( preg_match( $regex( $number ), $css, $found, PREG_OFFSET_CAPTURE ) !== 1 )
		return '';

	$start = (int) $found[0][1];

	if( preg_match( $regex( $number + 1 ), $css, $after, PREG_OFFSET_CAPTURE, $start ) === 1 )
		return substr( $css, $start, (int) $after[0][1] - $start );

	return substr( $css, $start );
}

/**
 *	The three blocks of a palette in the shape Auto writes: the light values, the
 *	dark ones under the system's media query, and the dark ones under the
 *	attribute the mode switch sets
 *
 *	@param		string		$css					Colours::css() at the mode auto
 *
 *	@return 	array											light, system and chosen, each a name => value map
 */
function designAutoBlocks( string $css ): array {

	$media	= (int) strpos( $css, '@media' );
	$attr		= (int) strpos( $css, ':root[data-nino-mode="dark"]' );

	return [
		'light' 	=> designDeclarations( substr( $css, 0, $media ) ),
		'system' 	=> designDeclarations( substr( $css, $media, $attr - $media ) ),
		'chosen' 	=> designDeclarations( substr( $css, $attr ) ),
	];
}

/**
 *	What is wrong with the knobs of one set of a tab, read the way the panel
 *	and the compiler read it and not any looser: a triple that is missing a
 *	step, a name that is no knob of the tab and no aspect of one, a rule
 *	reading a knob nobody declared, a header that documents other knobs than
 *	the file declares, a face, a weight, a case or a colour written as a
 *	literal - and a triple or a knob declared, or a knob or one of its steps
 *	read, that belongs to another tab
 *
 *	@param		string		$library			The feature's library
 *	@param		string		$part					A tab of Setup::KNOBS
 *	@param		string		$set					A set name
 *
 *	@return 	array											One sentence for each, led by the set's name - [] where there is nothing wrong
 */
function designKnobProblems( string $library, string $part, string $set ): array {

	$style = Setup::file( $library, $part, $set );

	if( $style === '' )
		return [ $set. ': has no stylesheet' ];

	/*	Read the way the panel reads it, or the check is looser than the
		thing it is checking: Setup::triples() looks for the token
		immediately followed by ':' in a stylesheet with its comments taken
		out. A space before the colon is valid css and invisible to that,
		so '--content-corners--less : 0' declared a step the panel never
		finds - and a knob whose three steps are all written that way is
		one nobody can turn, with nothing saying so	*/
	$source		= (string) file_get_contents( $style );
	$css			= designPlain( $source );
	$problems	= [];

	preg_match_all( '/--'. preg_quote( $part, '/' ). '-([a-z0-9]+(?:-[a-z0-9]+)*)--(less|default|more)\s*:/', $css, $found );

	$steps = [];
	foreach( $found[1] as $index => $name )
		$steps[$name][] = $found[2][$index];

	foreach( $steps as $name => $declared ) {

		sort( $declared );
		if( array_values( array_unique( $declared ) ) !== [ 'default', 'less', 'more' ] )
			$problems[] = $set. ': "'. $name. '" is not a triple';

		// A knob of the tab, or an aspect of one - which stands beside the triple of its knob
		$knob = null;
		foreach( Setup::KNOBS[$part] as $candidate )
			if( $name === $candidate || str_starts_with( $name, $candidate. '-' ) === true ) {
				$knob = $candidate;
				break;
			}

		if( $knob === null )
			$problems[] = $set. ': "'. $name. '" is neither a knob of "'. $part. '" nor an aspect of one';
		else if( isset( $steps[$knob] ) === false )
			$problems[] = $set. ': "'. $name. '" is an aspect of "'. $knob. '", which the set does not declare';
	}

	// ...and what it reads. A rule on a token nothing declared is a rule
	// that does nothing, which is invisible until somebody moves the knob
	preg_match_all( '/var\(\s*--'. preg_quote( $part, '/' ). '-([a-z0-9]+(?:-[a-z0-9]+)*)\s*[,)]/', $css, $used );
	foreach( array_unique( $used[1] ) as $token )
		if( isset( $steps[$token] ) === false )
			$problems[] = $set. ': reads --'. $part. '-'. $token. ' without declaring it';

	/*	...and what belongs to a tab that is not this one's. The tabs are
		compiled in the order they are shown in, so a set that declares the triple
		of another tab's knob overrides the steps of the template chosen there - a
		components set declaring --buttons-small--default would replace what the
		buttons template published, with the panel still showing the components
		answering to their own knobs only. So does one that declares the knob
		itself, which the compiler selects on :root for the template chosen there:
		a nested `.nino-form .nino-btn { --buttons-small: 2; }` beats that selection
		for every element it matches. A rule that reads another tab's knob, or one
		of its steps by name, follows a position that tab's template may not
		publish at all. The templates of a tab stay exchangeable only while each of
		them is about its own knobs	*/
	$others = implode( '|', array_diff( array_keys( Setup::KNOBS ), [ $part ] ) );

	preg_match_all( '/--('. $others. ')-([a-z0-9]+(?:-[a-z0-9]+)*)--(?:less|default|more)\s*:/', $css, $foreign, PREG_SET_ORDER );

	$declares = [];
	foreach( $foreign as $hit )
		$declares[ $hit[1]. '-'. $hit[2] ] = $hit[1];

	foreach( $declares as $triple => $tab )
		$problems[] = $set. ': declares --'. $triple. ', a triple of the '. $tab. ' tab';

	// ...and the knob itself, once for each name - and not again for one whose triple was reported just now
	preg_match_all( '/--('. $others. ')-([a-z0-9]+(?:-[a-z0-9]+)*)\s*:/', $css, $foreign, PREG_SET_ORDER );

	$redeclared = [];
	foreach( $foreign as $hit )
		if( isset( $declares[ $hit[1]. '-'. $hit[2] ] ) === false )
			$redeclared[ $hit[1]. '-'. $hit[2] ] = $hit[1];

	foreach( $redeclared as $knob => $tab )
		$problems[] = $set. ': declares --'. $knob. ', a knob of the '. $tab. ' tab';

	// A step is read by its name as well as the knob: var(--buttons-small--default) follows the buttons template just as var(--buttons-small) does
	preg_match_all( '/var\(\s*--('. $others. ')-([a-z0-9]+(?:-[a-z0-9]+)*)(?:--(?:less|default|more))?\s*[,)]/', $css, $foreign, PREG_SET_ORDER );

	$reads = [];
	foreach( $foreign as $hit )
		$reads[ $hit[1]. '-'. $hit[2] ] = $hit[1];

	foreach( $reads as $knob => $tab )
		$problems[] = $set. ': reads --'. $knob. ', a knob of the '. $tab. ' tab';

	// The header's @knob lines are the file's own documentation of what it publishes: no more and no fewer
	preg_match_all( '/@knob\s+([a-z0-9-]+)/', (string) substr( $source, 0, (int) strpos( $source, '*/' ) ), $documented );
	$published = Setup::knobs( $library, $part, $set );
	sort( $documented[1] );
	$sortedPublished = $published;
	sort( $sortedPublished );
	if( $documented[1] !== $sortedPublished )
		$problems[] = $set. ': the @knob lines name ['. implode( ' ', $documented[1] ). '] and the triples publish ['. implode( ' ', $sortedPublished ). ']';

	// A template with no knob has nothing to finetune, and the tab would be a screen of grey
	if( $published === [] )
		$problems[] = $set. ': answers to no knob at all';

	// The face, the weight and the case are the roles of the Fonts tab, and a colour is a role: never a literal
	if( preg_match( '/(?:^|[;{\s])(?:font-family|font-weight|text-transform)\s*:/', $css ) === 1 )
		$problems[] = $set. ': writes the face, the weight or the case of a role';
	if( preg_match( '/#[0-9a-fA-F]{3,8}\b|\b(?:rgb|rgba|hsl|hsla|oklch|lab|lch)\(/', $css ) === 1 )
		$problems[] = $set. ': writes a colour as a literal';

	return $problems;
}

// The two markers: the delivered theme.css numbers its sections with dashes, the compiler with equals signs
const DESIGN_DELIVERED_MARKER = '/* ---- %d. %s ---- */';

// The kernel's own stylesheet and the token layer the setup wizard delivers, where this checkout has them
$deliveredFile	= $root. '/_admin/install/library/base/assets/theme.css';
$ninoCss				= designPlain( (string) @file_get_contents( $root. '/_nino/Nino.css' ) );

echo "The manifest, and what it promises\n";

$feature = \Nino\Features::manifest( dirname( __DIR__ ) );

check( 'the manifest reads, with the key and the class the directory implies', is_array( $feature ) === true
	&& $feature['key'] === 'design' && $feature['module'] === '\\Nino\\Modules\\Design' );
// ^1.6: the hero and the roles. Nino 1.6 renamed the classes of the hero -
// nino-hero, nino-hero--fullscreen, nino-hero-arrowdown, nino-hero-title and
// nino-hero-subtitle - and kept no alias, gave the type its five roles
// (--fontfamily-<role>, --font-<role>-weight, -transform and -size) and the
// page its --grid-gutter, and drew the grounds and the counter ground: the
// content templates, the font roles the compiler writes, the width of the
// page and the specimen are written for them, so on a 1.5 they style
// nothing. The floor before it was 1.4: the frames read words of the
// kernel's - /project/company/..., the words of /template/frame-header and
// /template/frame-footer, the page details after /_nino/webpage - and write
// the project's frame-header.tpl and frame-footer.tpl, which are named so
// from 1.4 on, and 1.6 has them all. A pre-release of 1.6 counts as 1.6
check( 'it names ^1.6 - the kernel whose hero, roles and grounds its templates, its compiler and its specimen are written for', ( $feature['nino'] ?? '' ) === '^1.6' );
check( '...and no 1.5 can run it, a 1.6 pre-release can', \Nino\Features::satisfies( (string) $feature['nino'], '1.5.0' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.2.0-beta' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.6.0-dev' ) === true );
check( 'it is Design 2.0: the model is another one, and the version says so', ( $feature['version'] ?? '' ) === '2.0.0' );
check( 'the setup file is declared under data, so a backup carries it', in_array( Setup::PATH, (array) ( $feature['data'] ?? [] ), true ) === true );
check( 'the manual describes the tabs, the preview and the look the wizard delivers',
	array_diff( [ 'Design', 'Global', 'Fonts', 'Content, Buttons, Components', 'Colours', 'Preview', 'The delivered look' ], array_keys( (array) ( $feature['manual']['panel'] ?? [] ) ) ) === []
	&& str_contains( (string) ( $feature['manual']['panel']['The delivered look']['en_US'] ?? '' ), 'regenerates it' ) === true );
check( '...it names who brings the borders of the grounds - the kernel regenerates the delivered file, an update of this feature assigns them in base.css - and the window the preview jumps in, in both languages',
	str_contains( \Nino\Features::localized( $feature['manual']['panel']['The delivered look'] ?? [], 'en_US' ), 'in base.css (--color-border-<ground>)' ) === true
	&& str_contains( \Nino\Features::localized( $feature['manual']['panel']['The delivered look'] ?? [], 'de_DE' ), 'in der base.css zu (--color-border-<fläche>)' ) === true
	&& str_contains( \Nino\Features::localized( $feature['manual']['panel']['Preview'] ?? [], 'en_US' ), 'own window' ) === true
	&& str_contains( \Nino\Features::localized( $feature['manual']['panel']['Preview'] ?? [], 'de_DE' ), 'eigene Fenster' ) === true );

echo "\nThe library it ships\n";

check( 'five parts: the header and the footer bring markup, the content, the buttons and the components are a stylesheet each',
	Setup::PARTS === [ 'header' => 'frame', 'footer' => 'frame', 'content' => 'set', 'buttons' => 'set', 'components' => 'set' ] );
check( 'the sets of the model before this one are gone - there is no library/sets', is_dir( $library. '/sets' ) === false
	&& is_file( $library. '/content/v1.css' ) === true && is_file( $library. '/buttons/v5.css' ) === true && is_file( $library. '/components/v3.css' ) === true );

foreach( Setup::PARTS as $part => $kind ) {
	$available = Setup::available( $library, $part );
	check( 'the library offers at least one set for "'. $part. '"', $available !== [] );
}

check( 'a frame offers its template and its stylesheet', Setup::file( $library, 'header', 'v1', 'template' ) !== ''
	&& Setup::file( $library, 'header', 'v1' ) !== '' );
check( 'a set name that is not a set name resolves to nothing', Setup::file( $library, 'content', '../../../etc/passwd' ) === ''
	&& Setup::file( $library, 'content', 'v1/../v1' ) === ''
	&& Setup::file( $library, 'content', '' ) === ''
	&& Setup::file( $library, 'sets', 'v1' ) === '' );

/*	The library is written by hand and grows by hand, so what is held here is
	what a new file gets wrong - and gets wrong quietly. A template with no
	@name is offered under its file name, two under one name are two rows
	nobody can tell apart, a knob outside Setup::KNOBS is a handle the panel
	will never show, a triple missing a step is a knob position that compiles
	to nothing, a rule reading a token the file never declared is a
	declaration that silently does not apply, a set that declares a triple or
	the selected knob of another tab overrides the template chosen there, one
	that reads a knob or a step of another tab follows a position that
	template may not publish, and a template that writes the face, the weight
	or the case of the text contradicts the Fonts tab.

	Written against the shipped library rather than a fixture on purpose: a
	fixture would prove the rule and let the files drift. That the rule
	refuses what it names is shown further down, over throwaway sets.	*/
foreach( Setup::PARTS as $part => $kind ) {

	$catalogue	= Setup::catalogue( $library, $part );
	$names			= array_column( $catalogue, 'name' );
	$described	= array_filter( $catalogue, static fn( array $entry ): bool => trim( $entry['description'] ) !== '' );

	check( '"'. $part. '" offers a choice rather than a single template', count( $catalogue ) > 1 );
	check( '...every one of them named, and no two the same', count( array_unique( $names ) ) === count( $names )
		&& count( array_filter( $names, static fn( string $name ): bool => preg_match( '/^v[0-9]+$/', $name ) === 1 ) ) === 0 );
	check( '...and every one of them saying what it is', count( $described ) === count( $catalogue ) );

	$knobProblems	= [];
	$fileProblems	= [];
	$omitted			= 0;

	foreach( array_keys( $catalogue ) as $set ) {

		$style = Setup::file( $library, $part, $set );

		if( $style === '' || ( $kind === 'frame' && Setup::file( $library, $part, $set, 'template' ) === '' ) ) {
			$fileProblems[] = $set;
			continue;
		}

		if( $kind !== 'set' )
			continue;

		$knobProblems = array_merge( $knobProblems, designKnobProblems( $library, $part, $set ) );

		if( count( Setup::knobs( $library, $part, $set ) ) < count( Setup::KNOBS[$part] ) )
			$omitted++;
	}

	// Both lists carry the file and what is wrong with it; a failure that does
	// not say which of five templates it means is a search rather than a report
	check( '...each resolving to the files its kind is made of'. ( $fileProblems === [] ? '' : ' - '. implode( ', ', $fileProblems ) ), $fileProblems === [] );

	if( $kind === 'set' ) {

		check( '...every triple it declares is a triple of a knob of its tab, or an aspect of one, every one it reads is declared, and none it declares or reads is another tab\'s'. ( $knobProblems === [] ? '' : ' - '. implode( ' | ', $knobProblems ) ), $knobProblems === [] );
		check( '...v1 declares every knob of the tab - it is the framework as it is, with something for each knob to reach',
			Setup::knobs( $library, $part, 'v1' ) === Setup::KNOBS[$part] );
		check( '...and another one leaves a knob out, so there is a template to show a knob the panel greys', $omitted > 0 );
	}
}

/*	A page that opens with a hero loses main's top padding - a rule of the
	header's, since the header is what sets that padding. The Builder writes a
	div.nino-wrap around a page's sections, and its hero carries no nino-hero:
	every hero a rule names directly below main it has to name inside the
	wrap as well, in the same rule. Read from the library's own list of
	headers, so a header that is added without the wrapped form fails here
	rather than on a site	*/
$heroProblems = [];

foreach( Setup::available( $library, 'header' ) as $set ) {

	$css		= designPlain( (string) file_get_contents( Setup::file( $library, 'header', $set ) ) );
	$rules	= 0;

	preg_match_all( '/([^{}]+)\{\s*padding-top:\s*0;\s*\}/', $css, $found );

	foreach( $found[1] as $selectors ) {

		// The heroes the rule names directly below main; the wrapped form of each is the same selector with the wrap between
		if( preg_match_all( '/main:has\(> (\.nino-[a-z.-]+):first-child\)/', $selectors, $heroes ) === 0 )
			continue;

		$rules++;

		foreach( $heroes[1] as $hero )
			if( str_contains( $selectors, 'main:has(> .nino-wrap > '. $hero. ':first-child)' ) === false )
				$heroProblems[] = $set. ': '. $hero;
	}

	if( $rules === 0 )
		$heroProblems[] = $set. ': no rule takes main\'s top padding away for a hero';
}

check( 'every header takes main\'s top padding away for a hero that opens the page, directly below main and inside the Builder\'s wrap alike'. ( $heroProblems === [] ? '' : ' - '. implode( ', ', $heroProblems ) ), $heroProblems === [] );

/*	And the one thing reading the files cannot say: that each of them survives
	the compiler. Every template of every part, one at a time, because a single
	setup naming all of them would prove only that the last one landed	*/
$compileProblems = [];

foreach( Setup::PARTS as $part => $kind )
	foreach( Setup::available( $library, $part ) as $set ) {

		$sheet = Compiler::compile( Setup::normalize( [ 'parts' => [ $part => $set ] ], $library ), $library );

		if( str_contains( $sheet, $part. ': '. $set ) === false )
			$compileProblems[] = $part. '/'. $set;
	}

check( 'every template in the library compiles into the sheet under its own name', $compileProblems === [] );

/*	The link to the imprint and the privacy policy is a menu of the project: the
	one the Legal module's unit creates and the base unit's footer frame outputs
	(Nino 1.4). Every footer frame outputs it the same way, and none includes
	html-footer-legal, the template that is no longer delivered - it answers ''
	for a file that is not there, and the frame would show no link at all	*/
$legalNav			= '[navigation nav="legal" id="legal__nav"][/navigation]';
$legalProblems	= [];
$footers			= Setup::available( $library, 'footer' );

foreach( $footers as $set ) {

	$markup = (string) file_get_contents( Setup::file( $library, 'footer', $set, 'template' ) );

	if( substr_count( $markup, $legalNav ) !== 1 )
		$legalProblems[] = $set. ': the legal navigation is not output exactly once';

	if( str_contains( $markup, 'html-footer-legal' ) === true )
		$legalProblems[] = $set. ': includes html-footer-legal';
}

check( 'every footer frame outputs the legal navigation and none includes html-footer-legal'. ( $legalProblems === [] ? '' : ' - '. implode( ' | ', $legalProblems ) ), $footers !== [] && $legalProblems === [] );

$baseFrame = $root. '/_admin/install/library/base/templates/frame-footer.tpl';

if( is_file( $baseFrame ) === true && str_contains( (string) file_get_contents( $baseFrame ), 'nav="legal"' ) === true )
	check( 'it is the shortcode the base unit\'s own footer frame uses', str_contains( (string) file_get_contents( $baseFrame ), $legalNav ) === true );
else
	echo "  --  this checkout's base footer frame has no legal navigation yet, so the shortcode is not compared\n";

/*	A frame is a template of the project the base unit set up, so every fill it
	reads has to be one the unit gives a value - or one the kernel fills while
	it renders. A key the unit stopped shipping stands on the page as itself:
	footer v2 read /website/footer/title/followus, which left the base unit with
	the social links. A fill built out of another one names a key per page and
	is the page's business ([[/_nino/webpage[[/nino/http/response/uri]]/title]]), so
	only its inner half is read	*/
$baseText = $root. '/_admin/install/library/base/text';

if( is_dir( $baseText ) === false )
	echo "  --  this checkout has no installer library, so the frames' fills are not compared\n";
else {
	// What the kernel fills while it renders is the kernel's to list, not a copy of it
	$shipped = array_fill_keys( \Nino\Html::runtimeFillKeys( $appData ), true );
	foreach( glob( $baseText. '/*.php' ) ?: [] as $file )
		foreach( array_keys( (array) include $file ) as $key )
			$shipped[ trim( (string) $key, '[]' ) ] = true;

	$unshipped = [];
	foreach( Setup::PARTS as $part => $kind ) {

		if( $kind !== 'frame' )
			continue;

		foreach( Setup::available( $library, $part ) as $set ) {
			preg_match_all( '/\[\[(\/[^\[\]]+)\]\]/', (string) file_get_contents( Setup::file( $library, $part, $set, 'template' ) ), $fills );
			foreach( array_unique( $fills[1] ) as $key )
				if( isset( $shipped[$key] ) === false )
					$unshipped[] = $part. '/'. $set. ': '. $key;
		}
	}

	check( 'every fill a frame reads is one the base unit ships or the kernel fills'. ( $unshipped === [] ? '' : ' - '. implode( ', ', $unshipped ) ), $unshipped === [] );
}


echo "\nThe setup, held against the library that is there\n";

$notes = [];
$setup = Setup::normalize( [], $library, $notes );

check( 'a setup out of nothing is every part on its first set, the delivered size, the width and the animation Nino.css has, and no note',
	$notes === [] && array_keys( $setup ) === Setup::KEYS
	&& $setup['parts'] === array_fill_keys( array_keys( Setup::PARTS ), 'v1' )
	&& $setup['size'] === 'm' && $setup['width'] === 0
	&& $setup['motion'] === [ 'effect' => '', 'strength' => '', 'speed' => '' ] );
check( '...every named knob of the three tabs in the middle, the fonts the wizard delivers and the palette the framework has',
	$setup['knobs'] === array_map( static fn( array $names ): array => array_fill_keys( $names, 0 ), Setup::KNOBS )
	&& array_keys( $setup['knobs'] ) === [ 'content', 'buttons', 'components' ]
	&& $setup['fonts'] === Fonts::DEFAULTS && $setup['colours'] === Colours::normalize( [] ) && $setup['colours']['mode'] === 'auto' );

$notes = [];
$setup = Setup::normalize( [ 'parts' => [ 'content' => 'v99', 'header' => 'v3', 'buttons' => [ 'set' => 'v2' ], 'atf' => 'v1' ] ], $library, $notes );

check( 'a part naming a set the library does not have falls back to the first one, and says which',
	$setup['parts']['content'] === 'v1' && $setup['parts']['header'] === 'v3'
	&& count( array_filter( $notes, static fn( string $note ): bool => str_contains( $note, '"content"' ) && str_contains( $note, '"v99"' ) ) ) === 1 );
check( '...a part that is none is dropped and named, and a set given in the shape of the model before this one is not mapped: it is the first',
	isset( $setup['parts']['atf'] ) === false && count( array_filter( $notes, static fn( string $note ): bool => str_contains( $note, 'no part "atf"' ) ) ) === 1
	&& $setup['parts']['buttons'] === 'v1' && count( $notes ) === 2 );

$notes = [];
$setup = Setup::normalize( [ 'step' => 'more', 'knobs' => [ 'spacing' => 'more' ], 'future' => true ], $library, $notes );

check( 'what the model before this one stored is dropped and named, and nothing of it is carried: a global position is no tab, a step is no key',
	count( $notes ) === 3 && str_contains( implode( ' ', $notes ), '"step"' ) === true && str_contains( implode( ' ', $notes ), '"future"' ) === true
	&& str_contains( implode( ' ', $notes ), 'no tab "spacing"' ) === true
	&& isset( $setup['step'] ) === false && $setup['knobs'] === Setup::defaults( $library )['knobs'] );

$empty = ninoSandboxDir( $appData ). '/nothing';
mkdir( $empty, 0755, true );
$notes = [];
$setup = Setup::normalize( [], $empty, $notes );

check( 'a library with nothing in it answers every part with none, and says so for each',
	array_values( $setup['parts'] ) === array_fill( 0, count( Setup::PARTS ), '' ) && count( $notes ) === count( Setup::PARTS ) );

/*	A knob is a position of its own tab, and a position is -1, 0 or 1.
	Whatever else arrives - a word, an array, a number beyond the ends - is the
	middle, which is the template as it is	*/
$notes = [];
$setup = Setup::normalize( [ 'knobs' => [
	'content' => [ 'hero-title' => -1, 'hero-text' => '1', 'quiet' => 1.4, 'loud' => 2, 'section-space' => 'more', 'article-space' => [ 1 ], 'corners' => null, 'shadow' => true, 'nonsense' => 1 ],
	'buttons' => 'more',
	'nowhere' => [ 'small' => 1 ],
] ], $library, $notes );

check( 'a knob position is -1, 0 or 1: a string that is a number, or a number that rounds to one, is kept',
	$setup['knobs']['content']['hero-title'] === -1 && $setup['knobs']['content']['hero-text'] === 1 && $setup['knobs']['content']['quiet'] === 1 );
check( '...and a word, an array, a boolean, nothing and a number past the ends are the middle, not an error',
	$setup['knobs']['content']['loud'] === 0 && $setup['knobs']['content']['section-space'] === 0 && $setup['knobs']['content']['article-space'] === 0
	&& $setup['knobs']['content']['corners'] === 0 && $setup['knobs']['content']['shadow'] === 0 && $setup['knobs']['buttons'] === array_fill_keys( Setup::KNOBS['buttons'], 0 ) );
check( '...a knob that is none of its tab\'s and a tab that is none of the three are dropped and named, never carried',
	array_keys( $setup['knobs']['content'] ) === Setup::KNOBS['content'] && isset( $setup['knobs']['nowhere'] ) === false
	&& count( $notes ) === 2 && str_contains( implode( ' ', $notes ), 'no knob "nonsense"' ) === true && str_contains( implode( ' ', $notes ), 'no tab "nowhere"' ) === true );

$positions = [ [ -1, -1 ], [ 0, 0 ], [ 1, 1 ], [ '-1', -1 ], [ '1', 1 ], [ -0.4, 0 ], [ 0.6, 1 ], [ -1.4, -1 ], [ 2, 0 ], [ -2, 0 ], [ 99, 0 ], [ 'x', 0 ], [ '', 0 ], [ null, 0 ], [ true, 0 ], [ [ 1 ], 0 ] ];
$wrong = array_filter( $positions, static fn( array $pair ): bool => Setup::position( $pair[0] ) !== $pair[1] );

check( 'the table of positions: '. count( $positions ). ' values, each the position it should be'. ( $wrong === [] ? '' : ' - wrong: '. json_encode( array_values( $wrong ) ) ), $wrong === [] );
check( 'the model keeps numbers and the library keeps words, and word() is the one place that knows both',
	[ Setup::word( -1 ), Setup::word( 0 ), Setup::word( 1 ), Setup::word( 7 ), Setup::word( -7 ) ] === [ 'less', 'default', 'more', 'more', 'less' ]
	&& Setup::STEPS === [ 'less', 'default', 'more' ] );

$setup = Setup::normalize( [ 'knobs' => [ 'content' => [ 'corners' => 1, 'border' => -1 ], 'components' => [ 'zebra' => 1 ] ] ], $library );

check( 'step() says where a knob stands, as the word its stylesheet declares it under',
	Setup::step( $setup, 'content', 'corners' ) === 'more' && Setup::step( $setup, 'content', 'border' ) === 'less'
	&& Setup::step( $setup, 'content', 'shadow' ) === 'default' && Setup::step( $setup, 'components', 'zebra' ) === 'more'
	&& Setup::step( $setup, 'buttons', 'nothing' ) === 'default' && Setup::step( $setup, 'nowhere', 'x' ) === 'default' );

// The root size, the width and the animation of the page
$setup = Setup::normalize( [ 'size' => 'l', 'width' => '1', 'motion' => [ 'effect' => 'flip', 'strength' => 'hard', 'speed' => 'slow' ] ], $library );

check( 'the size, the width and the animation are kept as they were chosen', $setup['size'] === 'l' && $setup['width'] === 1
	&& $setup['motion'] === [ 'effect' => 'flip', 'strength' => 'hard', 'speed' => 'slow' ] );
check( '...width -1, 0 and 1 are the three widths', array_map( static fn( int $width ): int => Setup::normalize( [ 'width' => $width ], $library )['width'], [ -1, 0, 1 ] ) === [ -1, 0, 1 ] );

$notes = [];
$setup = Setup::normalize( [ 'size' => 'xxl', 'width' => 2, 'motion' => [ 'effect' => 'spin', 'strength' => 'loud', 'speed' => 'warp', 'delay' => 1 ] ], $library, $notes );

check( 'a size, a width, an effect, a strength or a speed that is none of the choices is the default, not an error',
	$setup['size'] === 'm' && $setup['width'] === 0 && $setup['motion'] === [ 'effect' => '', 'strength' => '', 'speed' => '' ] );
check( '...and a key of the animation that is none of its three is dropped and named', count( $notes ) === 1 && str_contains( $notes[0], '"delay"' ) === true );

// None of what comes off the wire may raise: an array cast to a string is a warning the kernel treats as fatal
ninoWarnings();
$setup = Setup::normalize( [ 'size' => [ 'l' ], 'width' => [ 1 ], 'motion' => 'flip', 'parts' => 'v1', 'knobs' => 'more', 'fonts' => 'bold', 'colours' => 'dark', 'compiled' => 'yes' ], $library );

check( 'every key of the model, posted as the wrong type, is its default and raises nothing', ninoWarnings() === []
	&& array_diff_key( $setup, [ 'compiled' => 1 ] ) === array_diff_key( Setup::defaults( $library ), [ 'compiled' => 1 ] ) && $setup['compiled'] === [] );
check( '...and the record of what was compiled is carried when it is one', Setup::normalize( [ 'compiled' => [ 'at' => 'then' ] ], $library )['compiled'] === [ 'at' => 'then' ] );

// The five font roles
$notes = [];
$setup = Setup::normalize( [ 'fonts' => [
	'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'uppercase' => true, 'size' => '2.40' ],
	'subtitle' => [ 'family' => 'nowhere', 'weight' => 'heavy', 'size' => 99, 'colour' => 'red' ],
	'text' => [ 'family' => '', 'size' => 1.125 ],
	'code' => [ 'family' => 'system-mono', 'weight' => 'bold', 'uppercase' => true, 'size' => '0.8125' ],
	'button' => 'bold',
	'heading' => [ 'family' => 'system-sans' ],
] ], $library, $notes );

check( 'a font role keeps a family its select offers, a weight word, the case and a size that is one of its steps - a step spelled 2.40 is the step 2.4',
	$setup['fonts']['title'] === [ 'family' => 'system-serif', 'weight' => 'light', 'uppercase' => true, 'size' => '2.4' ]
	&& $setup['fonts']['text'] === [ 'family' => '', 'weight' => 'normal', 'uppercase' => false, 'size' => '1.125' ] );
check( '...a family, a weight or a size that is none starts from what the role has, and the code has no case to choose',
	$setup['fonts']['subtitle'] === Fonts::DEFAULTS['subtitle'] && $setup['fonts']['button'] === Fonts::DEFAULTS['button']
	&& $setup['fonts']['code']['weight'] === 'bold' && $setup['fonts']['code']['uppercase'] === false && $setup['fonts']['code']['size'] === '0.8125' );
check( '...and a family the role\'s select does not offer is none: the code is given the monospace ones, of which there are none with files yet - the system stack is its empty family - while the other roles keep a system stack',
	$setup['fonts']['code']['family'] === '' && Setup::normalize( [ 'fonts' => [ 'code' => [ 'family' => 'league-spartan' ] ] ], $library )['fonts']['code']['family'] === ''
	&& Setup::normalize( [ 'fonts' => [ 'title' => [ 'family' => 'system-mono' ] ] ], $library )['fonts']['title']['family'] === 'system-mono'
	&& array_filter( Fonts::ROLES, static fn( string $role ): bool => in_array( Setup::normalize( [ 'fonts' => [ $role => [ 'family' => 'source-sans-3' ] ] ], $library )['fonts'][$role]['family'], Fonts::options( $role ), true ) === false ) === [] );
check( '...and a role that is none, or a key of a role that is none of its four, is dropped and named',
	count( $notes ) === 2 && str_contains( implode( ' ', $notes ), 'no font role "heading"' ) === true && str_contains( implode( ' ', $notes ), '"colour"' ) === true
	&& array_keys( $setup['fonts'] ) === Fonts::ROLES );

// The palette is the Colours class's to normalise, the mode with it
$setup = Setup::normalize( [ 'colours' => [ 'primary' => '#8b1d3f', 'mode' => 'dark', 'contrast' => 3 ] ], $library );

check( 'the palette is Colours\' to normalise, and the mode is one of its words', $setup['colours'] === Colours::normalize( [ 'primary' => '#8b1d3f', 'mode' => 'dark', 'contrast' => 3 ] )
	&& $setup['colours']['mode'] === 'dark' && $setup['colours']['contrast'] === 3 );
check( '...a mode that is none of the three, or not a word at all, is Auto',
	Setup::normalize( [ 'colours' => [ 'mode' => 'twilight' ] ], $library )['colours']['mode'] === 'auto'
	&& Setup::normalize( [ 'colours' => [ 'mode' => [ 'dark' ] ] ], $library )['colours']['mode'] === 'auto'
	&& Colours::MODES === [ 'auto', 'light', 'dark' ] );

/*	The first version that was used is this one: a file of another format is
	read as the defaults, and the note says so. Nothing maps what the older
	shape chose onto this one	*/
$setupFile = \Nino\Filesystem::path( $appData, Setup::PATH );

\Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'format' => 1, 'size' => 'l', 'parts' => [ 'header' => [ 'set' => 'v3', 'knobs' => [] ] ], 'knobs' => [ 'spacing' => 'more' ] ] );
$notes = [];
$read = Setup::read( $appData, $library, $notes );

check( 'a file written in another format is read as the defaults, and the note says it is not read and the defaults stand in until the next save', $read === Setup::normalize( [], $library )
	&& count( $notes ) === 1 && str_contains( $notes[0], 'another format' ) === true && str_contains( $notes[0], 'data/design.php' ) === true
	&& str_contains( $notes[0], 'it is not read' ) === true && str_contains( $notes[0], 'until the next save' ) === true );

\Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'size' => 'l' ] );
$notes = [];

check( '...so is one that names no format at all', Setup::read( $appData, $library, $notes ) === Setup::normalize( [], $library ) && count( $notes ) === 1 );

$written = Setup::normalize( [ 'size' => 'l', 'width' => -1, 'parts' => [ 'header' => 'v3' ], 'knobs' => [ 'buttons' => [ 'space' => 1 ] ] ], $library );
$notes = [];

check( 'a file in this format is read as it was written, without a note', Setup::write( $appData, $written ) === true
	&& Setup::read( $appData, $library, $notes ) === $written && $notes === []
	&& ( \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] )['format'] ?? 0 ) === Setup::FORMAT );

@unlink( $setupFile );
$notes = [];

check( 'and with no file at all it is the defaults, without a note', Setup::read( $appData, $library, $notes ) === Setup::normalize( [], $library ) && $notes === [] );

echo "\nThe fonts: what each role can be, and what is written for it\n";

$sans = Fonts::STACK_SANS;
$mono = Fonts::STACK_MONO;

check( 'the five roles, in the order they are shown and written in', Fonts::ROLES === [ 'title', 'subtitle', 'text', 'button', 'code' ]
	&& array_keys( Fonts::DEFAULTS ) === Fonts::ROLES && array_keys( Fonts::SIZES ) === Fonts::ROLES && array_keys( Fonts::INHERITED ) === Fonts::ROLES );
check( 'a family is a name, a category and the files it comes in - the three system stacks have none',
	array_keys( Fonts::families() ) === [ 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ]
	&& array_filter( Fonts::families(), static fn( array $family ): bool => isset( $family['name'], $family['category'], $family['weights'], $family['files'] ) === false ) === []
	&& array_keys( array_filter( Fonts::families(), static fn( array $family ): bool => $family['files'] === [] ) ) === [ 'system-sans', 'system-serif', 'system-mono' ] );
check( 'each role is offered the families that suit it, the empty one first - the code only a monospace that has files, which no system stack has',
	Fonts::options( 'title' ) === [ '', 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ]
	&& Fonts::options( 'text' ) === Fonts::options( 'title' ) && Fonts::options( 'code' ) === [ '' ] );
check( 'a webface is its name in quotes and then the stack of its role, a system family its own stack, and no family nothing',
	Fonts::line( 'title', 'league-spartan' ) === '"League Spartan", '. $sans && Fonts::line( 'code', 'league-spartan' ) === '"League Spartan", '. $mono
	&& Fonts::line( 'text', 'system-mono' ) === $mono && Fonts::line( 'text', 'system-serif' ) === 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif'
	&& Fonts::line( 'text', '' ) === '' && Fonts::line( 'text', 'nowhere' ) === '' );
check( 'a weight word is the weight a family has nearest to it, and as a keyword where it has none to choose from',
	[ Fonts::weight( 'source-sans-3', 'light' ), Fonts::weight( 'source-sans-3', 'normal' ), Fonts::weight( 'source-sans-3', 'bold' ) ] === [ '400', '400', '700' ]
	&& [ Fonts::weight( 'league-spartan', 'light' ), Fonts::weight( 'league-spartan', 'normal' ), Fonts::weight( 'league-spartan', 'bold' ) ] === [ '700', '700', '700' ]
	&& [ Fonts::weight( 'system-sans', 'light' ), Fonts::weight( 'system-sans', 'normal' ), Fonts::weight( '', 'bold' ), Fonts::weight( '', 'heavy' ) ] === [ '300', 'normal', 'bold', 'normal' ] );

// No family has a tie today - a webface that brings 400 and 600 would - so the choice is held where it can be: of two weights as near as each other the lighter wins
$nearest = new ReflectionMethod( Fonts::class, 'nearest' );
check( 'of two weights as near as each other a word gets the lighter - the list is sorted first, and only a nearer weight takes the place of the one held',
	$nearest->invoke( null, [ 400, 600 ], 500 ) === 400 && $nearest->invoke( null, [ 700, 300 ], 500 ) === 300 && $nearest->invoke( null, [ 300, 400, 500, 700, 900 ], 800 ) === 700
	&& $nearest->invoke( null, [ 400, 700 ], 500 ) === 400 && $nearest->invoke( null, [ 400, 700 ], 800 ) === 700 && $nearest->invoke( null, [ 500 ], 300 ) === 500 );
check( 'a size is one of the role\'s steps as a string, and the default for anything else',
	Fonts::size( 'title', '2.4' ) === '2.4' && Fonts::size( 'title', 2.4 ) === '2.4' && Fonts::size( 'title', '2.40' ) === '2.4' && Fonts::size( 'title', 2 ) === '2'
	&& Fonts::size( 'title', '2.45' ) === '' && Fonts::size( 'title', '' ) === '' && Fonts::size( 'title', 'big' ) === '' && Fonts::size( 'title', [ 2 ] ) === ''
	&& Fonts::size( 'code', '0.9375' ) === '0.9375' && Fonts::size( 'nothing', '1' ) === '' );

echo "\nWhat the compiler produces\n";

$notes	= [];
$setup	= Setup::normalize( [], $library );
$css		= Compiler::compile( $setup, $library, $notes );

check( 'compiling a default setup reports nothing missing', $notes === [] );
check( 'the sheet opens with a header naming what made it and forbidding hand edits', str_starts_with( $css, '/*' ) === true
	&& str_contains( $css, 'Generated by the Design feature' ) === true && str_contains( $css, 'Do not edit' ) === true
	&& preg_match( '/'. preg_quote( Compiler::STAMP, '/' ). ': [a-f0-9]{64}/', $css ) === 1 );
check( '...and the header lists the chosen set of every part, the five font roles, the root size, the width and the animation',
	array_filter( array_keys( Setup::PARTS ), static fn( string $part ): bool => preg_match( '/^ \*   '. $part. ' +v1$/m', $css ) !== 1 ) === []
	&& array_filter( Fonts::ROLES, static fn( string $role ): bool => preg_match( '/^ \*   '. $role. ' +\S/m', $css ) !== 1 ) === []
	&& preg_match( '/^ \*\troot size  m$/m', $css ) === 1 && preg_match( '/^ \*\twidth      normal$/m', $css ) === 1
	&& preg_match( '/^ \*\tanimation  as Nino\.css has it$/m', $css ) === 1 );

// The order is the whole contract: the tokens before the palette that replaces them, the type before the roles
// that read it, the page, the frames, the sets, and the knob block last because it reads what the sets declared
$order = [];
if( preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $css, $sections, PREG_SET_ORDER ) > 0 )
	foreach( $sections as $section )
		$order[(int) $section[1]] = trim( $section[2] );

check( 'twelve numbered sections - the tokens, the palette, the type, the roles, the root size, the page', array_keys( $order ) === range( 1, 12 )
	&& array_slice( $order, 0, 6 ) === [ 'the design tokens', 'the palette', 'the type', 'the roles', 'the root size', 'the page' ] );
check( '...then the frames, then the three sets in the order the parts are declared in, each saying where its knobs stand, and the knob positions last', array_slice( $order, 6 ) === [
	'header: v1', 'footer: v1',
	'content: v1 (hero-title default, hero-text default, quiet default, loud default, section-space default, article-space default, corners default, border default, shadow default)',
	'buttons: v1 (small default, big default, space default, corners default)',
	'components: v1 (scale default, space default, corners default, border default, zebra default, labels default)',
	'the knob positions',
] );

$tokensOf	= designPlain( designSection( $css, 1, Compiler::SECTION ) );
$rolesOf	= designPlain( designSection( $css, 4, Compiler::SECTION ) );

check( 'the token layer really is in there, and the roles that name its tokens, each in its own section: base.css fell into its two halves',
	str_contains( $tokensOf, '--nino-default:' ) === true && str_contains( $rolesOf, '--color-title:' ) === true
	&& str_contains( $tokensOf, '--color-title:' ) === false && str_contains( $rolesOf, '--nino-default:' ) === false );
check( 'the root size is the relative pair, never a length', str_contains( $css, '--nino-base-size: 100%;' ) === true
	&& str_contains( $css, '@media (min-width: 768px) { :root { --nino-base-size: 112.5%; } }' ) === true
	&& preg_match( '/--nino-base-size:\s*\d+px/', $css ) !== 1 );

$large = Compiler::compile( Setup::normalize( [ 'size' => 'l' ], $library ), $library );
check( 'the size knob moves the pair, not one half of it: section 5 has the larger pair and the tokens above it the delivered one',
	str_contains( designSection( $large, 5, Compiler::SECTION ), '--nino-base-size: 112.5%;' ) === true && str_contains( designSection( $large, 5, Compiler::SECTION ), '--nino-base-size: 131.25%;' ) === true
	&& str_contains( designSection( $large, 5, Compiler::SECTION ), '--nino-base-size: 100%;' ) === false && str_contains( designSection( $large, 1, Compiler::SECTION ), '--nino-base-size: 100%;' ) === true );

// A library that is not complete is compiled around, and the notes say what is not in the sheet
$notes = [];
$partial = Compiler::compile( Setup::normalize( [], $empty ), $empty, $notes );
check( 'a library with no base.css and no templates compiles to the sections it can, and says what it lacks: the tokens, and each template',
	array_keys( array_map( 'trim', ( static function( string $sheet ): array {
		preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $sheet, $found, PREG_SET_ORDER );
		return array_column( $found, 2, 1 );
	} )( $partial ) ) ) === [ 2, 3, 5, 6 ]
	&& str_contains( implode( ' ', $notes ), 'no base.css' ) === true );

$richRaw = [
	'size' => 'l', 'width' => -1, 'motion' => [ 'effect' => 'flip', 'strength' => 'hard', 'speed' => 'slow' ],
	'parts' => [ 'header' => 'v2', 'footer' => 'v3', 'content' => 'v3', 'buttons' => 'v2', 'components' => 'v5' ],
	'knobs' => [ 'content' => [ 'hero-title' => -1, 'shadow' => 1, 'border' => 1 ], 'buttons' => [ 'small' => 1 ], 'components' => [ 'zebra' => 1, 'scale' => -1 ] ],
	'fonts' => [ 'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'uppercase' => true, 'size' => '2.4' ], 'code' => [ 'weight' => 'bold', 'size' => '0.9375' ] ],
	'colours' => [ 'mode' => 'dark', 'primary' => '#8b1d3f' ],
];
$notes	= [];
$rich		= Compiler::compile( Setup::normalize( $richRaw, $library ), $library, $notes );
$header	= substr( $rich, 0, (int) strpos( $rich, '*/' ) );

check( 'a setup that stands off the defaults compiles all the same, and reports nothing', $notes === [] );
check( 'the header says what stands off the defaults: the size, the width, the animation, each part with the knobs it answers to that are moved, each role with its family, weight, case and size',
	str_contains( $header, " *\troot size  l\n" ) === true && str_contains( $header, " *\twidth      narrow\n" ) === true
	&& str_contains( $header, " *\tanimation  flip, hard, slow\n" ) === true
	&& str_contains( $header, " *   header     v2\n" ) === true && str_contains( $header, " *   footer     v3\n" ) === true
	&& str_contains( $header, " *   content    v3  (hero-title less, shadow more)\n" ) === true
	&& str_contains( $header, " *   buttons    v2  (small more)\n" ) === true && str_contains( $header, " *   components v5  (zebra more)\n" ) === true
	&& str_contains( $header, " *   title      System serif, light, uppercase, 2.4rem\n" ) === true
	&& str_contains( $header, " *   code       the system stack, bold, 0.9375rem\n" ) === true );
check( '...a knob the chosen template does not answer to is not in the sheet at all, whatever position the model keeps for it',
	str_contains( $rich, '--content-border:' ) === false && str_contains( $rich, '--components-scale:' ) === false
	&& str_contains( $rich, '--content-hero-title: var(--content-hero-title--less);' ) === true );

echo "\nThe type: the five font roles, written where they were chosen\n";

$typeOf = static fn( array $raw ): string => designPlain( designSection( Compiler::compile( Setup::normalize( $raw, $library ), $library ), 3, Compiler::SECTION ) );
$rolesIn = static fn( string $type ): array => designDeclarations( designBlock( $type, ':root {' ) );

$type		= $typeOf( [] );
$roles	= $rolesIn( $type );

check( 'the defaults are the faces the wizard delivers: the title in League Spartan, the rest in Source Sans 3, each with its weight',
	( $roles['--fontfamily-title'] ?? '' ) === '"League Spartan", '. $sans && ( $roles['--font-title-weight'] ?? '' ) === '700'
	&& ( $roles['--fontfamily-subtitle'] ?? '' ) === '"Source Sans 3", '. $sans && ( $roles['--font-subtitle-weight'] ?? '' ) === '400'
	&& ( $roles['--fontfamily-text'] ?? '' ) === '"Source Sans 3", '. $sans && ( $roles['--font-text-weight'] ?? '' ) === '400'
	&& ( $roles['--fontfamily-button'] ?? '' ) === '"Source Sans 3", '. $sans && ( $roles['--font-button-weight'] ?? '' ) === '700' );
check( '...a role with no family writes none and keeps the system stack Nino.css has for it, and no case and no size is written where nobody chose one',
	isset( $roles['--fontfamily-code'] ) === false && ( $roles['--font-code-weight'] ?? '' ) === 'normal'
	&& preg_grep( '/-(transform|size)$/', array_keys( $roles ) ) === [] && isset( $roles['--article-title-scale'] ) === false );
check( '...and a family two roles use is declared once, one @font-face for each file it comes in',
	substr_count( $type, "font-family: 'Source Sans 3'" ) === 2 && substr_count( $type, "font-family: 'League Spartan'" ) === 1
	&& substr_count( $type, '@font-face' ) === 3 && str_contains( $type, "url('[[/nino/public]]/fonts/league-spartan.woff2')" ) === true );

$chosen = $rolesIn( $typeOf( $richRaw ) );

check( 'a role that was chosen is written as chosen: the stack of a system family, the weight as a number or a keyword, the case where it is upper, the size in rem',
	( $chosen['--fontfamily-title'] ?? '' ) === 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif' && ( $chosen['--font-title-weight'] ?? '' ) === '300'
	&& ( $chosen['--font-title-transform'] ?? '' ) === 'uppercase' && ( $chosen['--font-title-size'] ?? '' ) === '2.4rem'
	&& isset( $chosen['--fontfamily-code'] ) === false && ( $chosen['--font-code-weight'] ?? '' ) === 'bold' && ( $chosen['--font-code-size'] ?? '' ) === '0.9375rem'
	&& isset( $chosen['--font-code-transform'] ) === false );
check( '...a title with a size of its own fixes the article title as well, because Nino.css multiplies the role by a factor that only gives the size it had on a step of the scale',
	( $chosen['--article-title-scale'] ?? '' ) === '.8' && isset( $rolesIn( $typeOf( [ 'fonts' => [ 'subtitle' => [ 'size' => '1.5' ] ] ] ) )['--article-title-scale'] ) === false );
check( '...and the Source Sans 3 the other three roles still use is declared once, with the League Spartan nobody uses any more gone',
	substr_count( $typeOf( $richRaw ), "font-family: 'Source Sans 3'" ) === 2 && str_contains( $typeOf( $richRaw ), 'League Spartan' ) === false );

// The stacks are the kernel's own, and the step a role starts from is the one Nino.css gives it
if( $ninoCss === '' )
	echo "  --  this checkout has no Nino.css, so the stacks and the sizes of the roles are not compared with the kernel's\n";
else {

	$ninoRootDecls = designDeclarations( designBlock( $ninoCss, ':root {' ) );
	$ninoLarge = designDeclarations( designBlock( designBlock( $ninoCss, '@media (min-width: 768px){' ), ':root{' ) );

	check( 'the stacks a role falls back to are the stacks Nino.css has for its roles', $sans === ( $ninoRootDecls['--fontfamily-text'] ?? '' )
		&& $sans === ( $ninoRootDecls['--fontfamily-title'] ?? '' ) && $mono === ( $ninoRootDecls['--fontfamily-code'] ?? '' ) );
	check( '...and the size each role has until it is fixed is what Nino.css gives it from 768px: the title the fourth step of the scale, the subtitle the second',
		( $ninoRootDecls['--font-title-size'] ?? '' ) === 'var(--text-4)' && ( $ninoLarge['--text-4'] ?? '' ) === 'calc(var(--font-text-size) * '. Fonts::INHERITED['title']. ')'
		&& ( $ninoRootDecls['--font-subtitle-size'] ?? '' ) === 'var(--text-2)' && ( $ninoRootDecls['--text-2'] ?? '' ) === 'calc(var(--font-text-size) * '. Fonts::INHERITED['subtitle']. ')'
		&& ( $ninoRootDecls['--font-text-size'] ?? '' ) === Fonts::INHERITED['text']. 'rem'
		&& ( $ninoRootDecls['--font-button-size'] ?? '' ) === 'var(--fontsize-btn)' && ( $ninoRootDecls['--fontsize-btn'] ?? '' ) === Fonts::INHERITED['button']. 'rem'
		&& ( $ninoRootDecls['--font-code-size'] ?? '' ) === '.875rem' && Fonts::INHERITED['code'] === '0.875' );
	check( '...and the steps of a role run over that size, from below it to above it - "Default" is the position between them, and the subtitle\'s 1.125 stands between two of its tenths',
		array_filter( Fonts::ROLES, static fn( string $role ): bool => (float) Fonts::SIZES[$role][0] > (float) Fonts::INHERITED[$role] || (float) Fonts::SIZES[$role][ array_key_last( Fonts::SIZES[$role] ) ] < (float) Fonts::INHERITED[$role] ) === []
		&& in_array( Fonts::INHERITED['subtitle'], Fonts::SIZES['subtitle'], true ) === false );
}

echo "\nThe page: the width, and how its sections arrive\n";

$pageOf = static fn( array $raw ): string => Compiler::page( Setup::normalize( $raw, $library ) );
$nothing = '/* a row of the width Nino.css gives it, and the animation Nino.css has */';

check( 'a width of 0 and no animation write nothing but a comment saying so, and that is the whole of section 6',
	$pageOf( [] ) === $nothing && designSection( $css, 6, Compiler::SECTION ) === sprintf( Compiler::SECTION, 6, 'the page' ). "\n". $nothing. "\n\n" );
check( 'a narrow page is a row of 96rem with one step less of space to the sides at both sizes, a wide one 144rem with one more',
	$pageOf( [ 'width' => -1 ] ) === ":root { --grid-max-width: 96rem; --grid-gutter: var(--space-1); }\n@media (min-width: 768px) { :root { --grid-gutter: var(--space-2); } }"
	&& $pageOf( [ 'width' => 1 ] ) === ":root { --grid-max-width: 144rem; --grid-gutter: var(--space-3); }\n@media (min-width: 768px) { :root { --grid-gutter: var(--space-4); } }" );

if( $ninoCss === '' )
	echo "  --  this checkout has no Nino.css, so the width and the animation are not compared with the kernel's\n";
else {

	/*	Held to the kernel: 0 is Nino.css as it is, so the two steps are one step
		of the space scale either side of what it has - below 768px and from it	*/
	$gutterAt = static function( array $declarations ): int {
		preg_match( '/var\(--space-([1-6])\)/', (string) ( $declarations['--grid-gutter'] ?? '' ), $found );
		return (int) ( $found[1] ?? 0 );
	};
	$gutterSmall = $gutterAt( $ninoRootDecls );
	$gutterLarge = $gutterAt( $ninoLarge );

	check( 'width -1 and 1 are one step of the space scale either side of Nino.css\'s gutter, at both sizes, and the row 24rem narrower and wider than its 120rem',
		$ninoRootDecls['--grid-max-width'] === '120rem' && $gutterSmall > 1 && $gutterLarge > 1
		&& Setup::WIDTHS[-1] === [ '96rem', 'var(--space-'. ( $gutterSmall - 1 ). ')', 'var(--space-'. ( $gutterLarge - 1 ). ')' ]
		&& Setup::WIDTHS[1] === [ '144rem', 'var(--space-'. ( $gutterSmall + 1 ). ')', 'var(--space-'. ( $gutterLarge + 1 ). ')' ]
		&& array_keys( Setup::WIDTHS ) === [ -1, 1 ] );

	/*	The animation is the properties Nino.css sets for nino-vpa--<effect>-<strength>
		and nino-vpa--speed-<speed>, as one rule on main. The tables are held in
		code, so this is where they are held to the stylesheet they copy	*/
	$vpa = [];
	preg_match_all( '/\.nino-vpa--([a-z-]+)-(soft|medium|hard)\s*\{([^}]*)\}/', $ninoCss, $found, PREG_SET_ORDER );
	foreach( $found as $rule )
		if( $rule[1] !== 'speed' )
			$vpa[$rule[1]][$rule[2]] = designDeclarations( $rule[3] );

	$sorted = static function( array $declarations ): array {
		ksort( $declarations );
		return $declarations;
	};
	$drifted = [];

	foreach( Setup::EFFECTS as $effect => $strengths )
		foreach( Setup::STRENGTHS as $strength ) {

			$wanted = $sorted( $vpa[$effect][$strength] ?? [] );
			$rule		= $sorted( designDeclarations( designBlock( designPlain( $pageOf( [ 'motion' => [ 'effect' => $effect, 'strength' => $strength ] ] ) ), 'main {' ) ) );

			if( $wanted === [] || $sorted( $strengths[$strength] ?? [] ) !== $wanted || $rule !== $wanted )
				$drifted[] = $effect. '-'. $strength;
		}

	check( 'every effect and strength of the animation writes the properties Nino.css sets for nino-vpa--<effect>-<strength> ('. count( Setup::EFFECTS ) * count( Setup::STRENGTHS ). ' of them)'
		. ( $drifted === [] ? '' : ' - drifted: '. implode( ', ', $drifted ) ), $drifted === [] );
	check( '...and they are all the kernel has: no effect of Nino.css is missing from the table, none is made up',
		array_keys( $vpa ) === array_keys( Setup::EFFECTS ) && array_filter( $vpa, static fn( array $strengths ): bool => array_keys( $strengths ) !== Setup::STRENGTHS ) === [] );

	preg_match_all( '/\.nino-vpa--speed-([a-z]+)\s*\{\s*--vpa-duration:\s*([^;]+);/', $ninoCss, $speeds );

	check( 'the three speeds are the durations Nino.css gives nino-vpa--speed-<speed>, and a speed is written beside the effect and never without one',
		array_combine( $speeds[1], array_map( 'trim', $speeds[2] ) ) === Setup::SPEEDS
		&& designDeclarations( designBlock( designPlain( $pageOf( [ 'motion' => [ 'effect' => 'blur', 'strength' => 'soft', 'speed' => 'slow' ] ] ) ), 'main {' ) )['--vpa-duration'] === '2s'
		&& isset( designDeclarations( designBlock( designPlain( $pageOf( [ 'motion' => [ 'effect' => 'blur', 'strength' => 'soft' ] ] ) ), 'main {' ) )['--vpa-duration'] ) === false
		&& $pageOf( [ 'motion' => [ 'effect' => '', 'strength' => 'hard', 'speed' => 'fast' ] ] ) === $nothing );
	check( '...an effect with no strength is the middle one, and the rule is on main - where every animation below it inherits it from',
		$sorted( designDeclarations( designBlock( designPlain( $pageOf( [ 'motion' => [ 'effect' => 'zoom' ] ] ) ), 'main {' ) ) ) === $sorted( $vpa['zoom']['medium'] )
		&& preg_match( '/^main \{$/m', $pageOf( [ 'motion' => [ 'effect' => 'zoom' ] ] ) ) === 1 );
}

// base.css assigns the role --grid-max-width: var(--nino-measure), as the delivered theme.css does: the page comes after it and wins
check( 'the compiled sheet carries the page where it was asked for: the width and the animation in section 6, after the roles - so they are the ones that apply',
	str_contains( designSection( $rich, 6, Compiler::SECTION ), '--grid-max-width: 96rem;' ) === true && str_contains( designSection( $rich, 6, Compiler::SECTION ), '--vpa-duration: 2s;' ) === true
	&& strpos( $rich, '--grid-max-width: 96rem;' ) > strpos( $rich, '--grid-max-width: var(--nino-measure);' )
	&& substr_count( $rich, '--grid-max-width: 96rem;' ) === 1 && substr_count( $rich, "\nmain {\n" ) === 1 );

echo "\nThe knob positions, over a library of throwaway sets\n";

/*	A set declares what its three steps are; the knob picks one. Written into a
	throwaway library so the assertions do not depend on what the shipped sets
	happen to declare today	*/
$sandbox = ninoSandboxDir( $appData ). '/library';
mkdir( $sandbox. '/content', 0755, true );
mkdir( $sandbox. '/buttons', 0755, true );
file_put_contents( $sandbox. '/base.css', "/* ---- 1. the design tokens ---- */\n:root { --x: 1; }\n/* ---- 2. the roles ---- */\n:root { --y: 2; }\n" );
file_put_contents( $sandbox. '/content/v1.css', "/*\t@name Throwaway\n *\t@description A set that exists for this test.\n *\t@knob corners\n *\t@knob shadow\n *\n *\tAn example in a comment is no declaration:\n *\t--content-border--less: 0; --content-border--default: 1px; --content-border--more: 3px;\n */\n:root {\n\t--content-corners--less: 0;\n\t--content-corners--default: .4rem;\n\t--content-corners--more : 2rem;\n\t--content-shadow--less: none;\n\t--content-shadow--default: none;\n\t--content-shadow--more: 0 .1rem .5rem black;\n\t--content-shadow-lift--less: 0;\n\t--content-shadow-lift--default: 0;\n\t--content-shadow-lift--more: -.1rem;\n}\n.nino-article { border-radius: var(--content-corners); box-shadow: var(--content-shadow); }\n" );
file_put_contents( $sandbox. '/content/v2.css', ":root {\n\t--content-shadow-lift--less: 0;\n\t--content-shadow-lift--default: 0;\n\t--content-shadow-lift--more: -.1rem;\n\t--content-nonsense--less: 1;\n\t--content-nonsense--default: 2;\n\t--content-nonsense--more: 3;\n}\n" );
file_put_contents( $sandbox. '/buttons/v1.css', ":root {\n\t--buttons-space--less: .25rem;\n\t--buttons-space--default: .5rem;\n\t--buttons-space--more: 1rem;\n}\n" );

$every = static fn( int $position ): array => array_map( static fn( array $names ): array => array_fill_keys( $names, $position ), Setup::KNOBS );

check( 'a set publishes a knob by declaring its triple, in the order of its tab, and by no other way: not by a comment, not by a name that is none of the tab\'s',
	Setup::knobs( $sandbox, 'content', 'v1' ) === [ 'corners', 'shadow' ] && Setup::knobs( $sandbox, 'buttons', 'v1' ) === [ 'space' ]
	&& Setup::knobs( $sandbox, 'content', 'v2' ) === [] && Setup::knobs( $sandbox, 'components', 'v1' ) === [] );
check( '...a triple spelled with a space before the colon is read the way a browser reads it, and an aspect stands beside the knob it is named after',
	Setup::triples( $sandbox, 'content', 'v1' ) === [ 'corners' => [ 'corners' ], 'shadow' => [ 'shadow', 'shadow-lift' ] ]
	&& Setup::triples( $sandbox, 'buttons', 'v1' ) === [ 'space' => [ 'space' ] ] );
check( '...an aspect without the triple of its knob has published nothing, and a frame or a set that is not there answers to nothing',
	Setup::triples( $sandbox, 'content', 'v2' ) === [] && Setup::knobs( $library, 'header', 'v1' ) === [] && Setup::knobs( $library, 'content', 'nope' ) === []
	&& Setup::knobs( $library, 'nowhere', 'v1' ) === [] );

/*	The library check of the top of this file refuses what it names - shown
	here over sets it can be shown on. The tabs are compiled in the order they
	are shown in, so a components set that declares a triple of the buttons
	would put it in the sheet after the buttons template's own and win, and
	one that declares the knob itself in a nested selector wins for every
	element that selector matches; a rule of it reading a buttons knob, or a
	step of one by name, follows a position the buttons template may not
	publish. Its own tab's triple and read stand beside them, and stay out of
	the report. Each is reported once, however often it is written - and a
	name whose triple is reported is not reported as a knob as well	*/
$stray = ninoSandboxDir( $appData ). '/library-stray';
mkdir( $stray. '/components', 0755, true );
file_put_contents( $stray. '/components/v1.css', "/*\t@name Stray\n *\t@description A set that reaches into the buttons.\n *\t@knob scale\n */\n:root {\n\t--components-scale--less: .9;\n\t--components-scale--default: 1;\n\t--components-scale--more: 1.1;\n\t--buttons-small--less: .5;\n\t--buttons-small--default: .7;\n\t--buttons-small--more: .9;\n}\ninput { font-size: calc(1rem * var(--components-scale)); padding: var(--buttons-space); }\n" );
// ...a step of a buttons knob read by its name, and never the knob itself - twice, the second time with a fallback
file_put_contents( $stray. '/components/v2.css', "/*\t@name Stray read\n *\t@description A set that reads a step of a buttons knob.\n *\t@knob scale\n */\n:root {\n\t--components-scale--less: .9;\n\t--components-scale--default: 1;\n\t--components-scale--more: 1.1;\n}\ninput { padding: var(--buttons-small--default); margin: var(--buttons-small--more, .5rem); }\n" );
// ...the knob a buttons template is selected under, declared again in a nested selector - twice, once with a space before the colon
file_put_contents( $stray. '/components/v3.css', "/*\t@name Stray knob\n *\t@description A set that declares a buttons knob again.\n *\t@knob scale\n */\n:root {\n\t--components-scale--less: .9;\n\t--components-scale--default: 1;\n\t--components-scale--more: 1.1;\n}\n.nino-form .nino-btn { --buttons-small: 2; }\n.nino-form .nino-btn--big { --buttons-small : 3; }\n" );
// ...and the same knob with its triple as well, which is what is reported
file_put_contents( $stray. '/components/v4.css', "/*\t@name Stray both\n *\t@description A set that declares a buttons knob and its triple.\n *\t@knob scale\n */\n:root {\n\t--components-scale--less: .9;\n\t--components-scale--default: 1;\n\t--components-scale--more: 1.1;\n\t--buttons-small--less: .5;\n\t--buttons-small--default: .7;\n\t--buttons-small--more: .9;\n}\n.nino-form .nino-btn { --buttons-small: 2; }\n" );

check( 'the library check finds nothing wrong with a set that keeps to its tab', designKnobProblems( $sandbox, 'content', 'v1' ) === [] );
check( '...and reports a components set that declares the triple of a buttons knob and reads another: once each, named with the tab it belongs to',
	designKnobProblems( $stray, 'components', 'v1' ) === [
		'v1: declares --buttons-small, a triple of the buttons tab',
		'v1: reads --buttons-space, a knob of the buttons tab',
	] );
check( '...and one that reads a step of a buttons knob by name - var(--buttons-small--default) - as reading that knob: once, however often it reads it',
	designKnobProblems( $stray, 'components', 'v2' ) === [ 'v2: reads --buttons-small, a knob of the buttons tab' ] );
check( '...and one that declares a buttons knob again in a nested selector as declaring it, once - and a name whose triple is reported is not reported a second time as a knob',
	designKnobProblems( $stray, 'components', 'v3' ) === [ 'v3: declares --buttons-small, a knob of the buttons tab' ]
	&& designKnobProblems( $stray, 'components', 'v4' ) === [ 'v4: declares --buttons-small, a triple of the buttons tab' ] );
check( '...and a set the library does not have is reported rather than read', designKnobProblems( $stray, 'components', 'v9' ) === [ 'v9: has no stylesheet' ] );

$notes	= [];
$picked	= Compiler::compile( Setup::normalize( [ 'knobs' => $every( 1 ) ], $sandbox, $notes ), $sandbox );

check( 'every knob a set answers to becomes one selection line, the aspects of a knob included', str_contains( $picked, '--content-corners: var(--content-corners--more);' ) === true
	&& str_contains( $picked, '--content-shadow: var(--content-shadow--more);' ) === true && str_contains( $picked, '--content-shadow-lift: var(--content-shadow-lift--more);' ) === true
	&& str_contains( $picked, '--buttons-space: var(--buttons-space--more);' ) === true );
check( '...and a knob no set answers to becomes nothing at all, and neither does a name that is no knob',
	str_contains( $picked, '--content-border:' ) === false && str_contains( $picked, '--content-hero-title:' ) === false
	&& str_contains( $picked, '--buttons-small:' ) === false && str_contains( $picked, '--content-nonsense:' ) === false );
check( '...gathered into one block at the end, not scattered beside the sets', substr_count( $picked, 'the knob positions' ) === 1
	&& strpos( $picked, 'the knob positions' ) > strpos( $picked, '--content-corners--more' ) && strpos( $picked, 'the knob positions' ) > strpos( $picked, '--buttons-space--more' ) );
check( '...and the three steps themselves are still in the sheet, so a project without the feature can move the knob by hand',
	str_contains( $picked, '--content-corners--less: 0;' ) === true && str_contains( $picked, '--content-shadow--more: 0 .1rem .5rem black;' ) === true );

$blockIn = substr( $picked, (int) strpos( $picked, 'the knob positions' ) );

check( '...one line for each, and no more: three for the content - the corners, the shadow and its lift - and one for the buttons',
	preg_match_all( '/^\t--[a-z-]+: var\(--[a-z-]+--more\);$/m', $blockIn ) === 4 && preg_match_all( '/^\t--[a-z-]+:/m', $blockIn ) === 4 );

$titles = [];
preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $picked, $found, PREG_SET_ORDER );
foreach( $found as $row )
	$titles[(int) $row[1]] = trim( $row[2] );

check( 'a template the library does not have is left out and the sections after it take the numbers that are free, each saying where the knobs it answers to stand',
	array_slice( $titles, 6, null, true ) === [ 7 => 'content: v1 (corners more, shadow more)', 8 => 'buttons: v1 (space more)', 9 => 'the knob positions' ]
	&& count( array_filter( $notes, static fn( string $note ): bool => str_contains( $note, 'holds no set for "header"' ) ) ) === 1 );

$moved = Compiler::compile( Setup::normalize( [ 'knobs' => [ 'content' => [ 'corners' => -1, 'shadow' => -1, 'border' => 1 ], 'buttons' => [ 'small' => 1 ] ] ], $sandbox ), $sandbox );

check( 'each knob is asked for its own tab: the content\'s position does not reach the buttons, and the aspect of a knob stands where the knob does',
	str_contains( $moved, '--content-corners: var(--content-corners--less);' ) === true && str_contains( $moved, '--content-shadow-lift: var(--content-shadow-lift--less);' ) === true
	&& str_contains( $moved, '--buttons-space: var(--buttons-space--default);' ) === true && str_contains( $moved, '--content-border:' ) === false );

$withoutKnob = Compiler::compile( Setup::normalize( [ 'parts' => [ 'content' => 'v2' ], 'knobs' => $every( 1 ) ], $sandbox ), $sandbox );
check( 'an aspect declared without its knob is not selected either - nothing was published', str_contains( $withoutKnob, '--content-shadow-lift:' ) === false
	&& str_contains( $withoutKnob, '--content-nonsense:' ) === false && str_contains( $withoutKnob, '--buttons-space: var(--buttons-space--more);' ) === true );

/*	What the panel compares to say whether the stylesheet still answers to the
	screen: the setup's own choices AND the bytes of every library file they
	point at, so a set that changed under a project - a reinstall, an edited
	library - is visible rather than silent. This is the whole of it	*/
$sandboxSetup 	= Setup::normalize( [ 'parts' => [ 'content' => 'v1' ] ], $sandbox );
$beforeSetEdit 	= \Nino\Modules\Design::fingerprint( $sandboxSetup, $sandbox );
$contentSet 		= $sandbox. '/content/v1.css';
$contentSource 	= (string) file_get_contents( $contentSet );
file_put_contents( $contentSet, $contentSource. "\n/* a set the project got from somewhere else */\n" );

check( 'the fingerprint follows the bytes of the sets it names, not only the names', \Nino\Modules\Design::fingerprint( $sandboxSetup, $sandbox ) !== $beforeSetEdit );

file_put_contents( $contentSet, $contentSource );

$recorded = $sandboxSetup;
$recorded['compiled'] = [ 'at' => 'then', 'sha' => 'a', 'input' => 'b', 'setup' => [ 'size' => 'l' ] ];

check( '...and what an earlier apply recorded decides nothing: the record is the answer to the fingerprint, not a question to it',
	\Nino\Modules\Design::fingerprint( $recorded, $sandbox ) === $beforeSetEdit );

$changes = [
	'a knob' 				=> [ 'knobs' => [ 'content' => [ 'corners' => 1 ] ] ],
	'a template' 		=> [ 'parts' => [ 'content' => 'v2' ] ],
	'the size' 			=> [ 'size' => 'l' ],
	'the width' 		=> [ 'width' => 1 ],
	'the animation' => [ 'motion' => [ 'effect' => 'zoom' ] ],
	'a font' 				=> [ 'fonts' => [ 'text' => [ 'family' => 'system-serif' ] ] ],
	'a colour' 			=> [ 'colours' => [ 'primary' => '#8b1d3f' ] ],
	'the mode' 			=> [ 'colours' => [ 'mode' => 'dark' ] ],
];
$unmoved = array_filter( $changes, static fn( array $raw ): bool => \Nino\Modules\Design::fingerprint( Setup::normalize( $raw + [ 'parts' => [ 'content' => 'v1' ] ], $sandbox ), $sandbox ) === $beforeSetEdit );

check( 'and every choice of the six tabs moves it: '. implode( ', ', array_keys( $changes ) ). ( $unmoved === [] ? '' : ' - unmoved: '. implode( ', ', array_keys( $unmoved ) ) ), $unmoved === [] );

/*	The colour tables moved at positions 1 and 3 of three knobs and not at 2,
	so the fingerprint carries their revision for a project with one of those
	knobs off 2 and for no other: a project that never touched them compiles
	the same bytes as before and has no reason to read "not applied"	*/
$beforeRevision = static function( array $setup, string $lib ): string {
	$files = [];
	foreach( array_keys( Setup::PARTS ) as $printPart ) {
		$printSet 					= (string) ( $setup['parts'][$printPart] ?? '' );
		$printFile 					= Setup::file( $lib, $printPart, $printSet );
		$files[$printPart] 	= [ $printSet, ( $printFile === '' || is_file( $printFile ) === false ) ? '' : (string) hash_file( 'sha256', $printFile ) ];
	}
	unset( $setup['compiled'] );
	return hash( 'sha256', serialize( [ $setup, $files ] ) );
};
$moved = $sandboxSetup;
$moved['colours']['contrast'] = 3;

check( 'a setup with every colour knob at 2 fingerprints exactly as it did before the tables moved',
	\Nino\Modules\Design::fingerprint( $sandboxSetup, $sandbox ) === $beforeRevision( $sandboxSetup, $sandbox ) );
check( '...and one with Saturation, Contrast or Depth anywhere else does not',
	\Nino\Modules\Design::fingerprint( $moved, $sandbox ) !== $beforeRevision( $moved, $sandbox ) );

echo "\nThe token layer and the roles, held to the delivered theme.css and to the kernel's Nino.css\n";

/*	The token layer is shipped twice on purpose - the wizard's base unit
	delivers it, and this feature carries its own copy because _admin/install/
	is gone from a project by the time anything here recompiles. The two used
	to be one file, byte for byte. They are not any more: the feature drops
	what nothing reads and lets the compiler write what the model decides, and
	a kernel release will generate the delivered file from this compiler at the
	defaults. Until then they are held to each other where a checkout still has
	the installer: what this feature keeps has the value the wizard delivers,
	and a project that applies the defaults assigns every role the way the
	delivered file does - so it keeps its look.	*/
$baseRaw				= (string) file_get_contents( $library. '/base.css' );
$baseCss				= designPlain( $baseRaw );
$baseTokens			= designDeclarations( designBlock( designPlain( designSection( $baseRaw, 1, DESIGN_DELIVERED_MARKER ) ), ':root {' ) );
$baseRoles			= designDeclarations( designPlain( designSection( $baseRaw, 2, DESIGN_DELIVERED_MARKER ) ) );
$defaultSheet		= Compiler::compile( Setup::normalize( [], $library ), $library );
$compiledRoles	= designDeclarations( designBlock( designPlain( designSection( $defaultSheet, 4, Compiler::SECTION ) ), ':root {' ) );

check( 'base.css has its two sections, the tokens and the roles, and nothing of the type: no font-face, no family, no size, no raster',
	$baseTokens !== [] && $baseRoles !== []
	&& str_contains( $baseCss, '@font-face' ) === false
	&& preg_match( '/--(?:fontfamily|fontsize|font-|nino-text-|text-[1-6])/', $baseCss ) !== 1 );

if( is_file( $deliveredFile ) === false )
	echo "  --  this checkout has no installer library, so the token layer and the roles are not compared\n";
else {

	$theirsRaw		= (string) file_get_contents( $deliveredFile );
	$theirTokens	= designDeclarations( designBlock( designPlain( designSection( $theirsRaw, 1, DESIGN_DELIVERED_MARKER ) ), ':root {' ) );
	$theirRoles		= designDeclarations( designBlock( designPlain( designSection( $theirsRaw, 2, DESIGN_DELIVERED_MARKER ) ), ':root {' ) );

	// What the feature no longer has, by rule: the interaction values nothing reads (a rule derives them),
	// the unsafe second colour (its -safe variant is the ink), and the raster of the text sizes (Nino.css derives its ladder)
	$droppedToken = static fn( string $token ): bool => preg_match( '/^--nino-[a-z-]+-(?:hover|active)$/', $token ) === 1
		|| preg_match( '/^--nino-text-[1-6]$/', $token ) === 1
		|| in_array( $token, [ '--nino-accent', '--nino-on-accent', '--nino-on-accent-muted', '--nino-accent-link', '--nino-accent-border', '--nino-accent-focus', '--nino-accent-disabled', '--nino-accent-shadow' ], true );

	$differing	= array_filter( array_intersect_key( $theirTokens, $baseTokens ), static fn( string $value, string $token ) => $baseTokens[$token] !== $value, ARRAY_FILTER_USE_BOTH );
	$dropped		= array_keys( array_diff_key( $theirTokens, $baseTokens ) );

	check( 'every --nino-* token of the delivered section 1 that base.css still declares has the delivered value ('. count( array_intersect_key( $theirTokens, $baseTokens ) ). ' of them)'
		. ( $differing === [] ? '' : ' - differing: '. implode( ', ', array_keys( $differing ) ) ), $differing === [] && $baseTokens !== [] );
	check( '...base.css declares nothing the delivered file does not', array_diff_key( $baseTokens, $theirTokens ) === [] );
	check( '...and the tokens it dropped are exactly the hover and active values, the unsafe second colour and the raster of the text sizes ('. count( $dropped ). ' of them)',
		$dropped === array_values( array_filter( array_keys( $theirTokens ), $droppedToken ) ) && count( $dropped ) === 38 );
	echo "  --  dropped from the delivered section 1: ". implode( ', ', array_map( static fn( string $token ): string => substr( $token, 7 ), $dropped ) ). "\n";

	// The delivered raster against the kernel's ladder: the ladder derives from the text size, and at the defaults the text size is 1rem
	preg_match_all( '/--nino-text-([1-6])\s*:\s*([0-9.]+)rem/', designPlain( designSection( $theirsRaw, 1, DESIGN_DELIVERED_MARKER ) ), $raster, PREG_SET_ORDER );
	preg_match_all( '/--text-([1-6])\s*:\s*(?:var\(--font-text-size\)|calc\(var\(--font-text-size\) \* ([0-9.]+)\))\s*;/', $ninoCss, $ladder, PREG_SET_ORDER );
	$rasterAt = array_map( static fn( array $row ): float => (float) $row[2], $raster );
	$ladderAt = array_map( static fn( array $row ): float => ( $row[2] ?? '' ) === '' ? 1.0 : (float) $row[2], $ladder );

	check( 'the delivered --nino-text raster is the kernel\'s ladder at the defaults, below 768px and from it: the compiled sheet does not write --text-1 to --text-6',
		count( $rasterAt ) === 9 && $rasterAt === $ladderAt
		&& array_intersect_key( $compiledRoles, array_flip( [ '--text-1', '--text-2', '--text-3', '--text-4', '--text-5', '--text-6' ] ) ) === [] );

	$missing	= array_keys( array_diff_key( $theirRoles, $compiledRoles ) );
	$changed	= array_keys( array_filter( array_intersect_key( $theirRoles, $compiledRoles ), static fn( string $value, string $role ): bool => $compiledRoles[$role] !== $value, ARRAY_FILTER_USE_BOTH ) );
	$added		= array_keys( array_diff_key( $compiledRoles, $theirRoles ) );

	check( 'the compiled sheet at the defaults assigns every role the delivered section 2 assigns to the same token ('. count( array_intersect_key( $theirRoles, $compiledRoles ) ). ' of them)'
		. ( $changed === [] ? '' : ' - assigned differently: '. implode( ', ', $changed ) ), $changed === [] && count( $theirRoles ) > 60 );

	/*	...but for the thirteen it does not assign, and why: --color-footer-text
		is read by nothing; the three font families are the type section's now
		(its names are the families', no longer Text and Title); and the three
		--fontsize-* and the six --text-* are Nino.css's own, with the values the
		delivered file gives them - held below	*/
	check( 'the roles it leaves out are --color-footer-text (nothing reads it), the three families (section 3 writes the roles), the --fontsize-* and the --text ladder (Nino.css has them)',
		$missing === [ '--color-footer-text', '--fontfamily-text', '--fontfamily-title', '--fontfamily-subtitle', '--fontsize-small', '--fontsize-big', '--fontsize-btn', '--text-1', '--text-2', '--text-3', '--text-4', '--text-5', '--text-6' ] );

	$ninoRoot = designDeclarations( designBlock( $ninoCss, ':root {' ) );

	check( '...and the three sizes Nino.css has are the delivered ones', ( $ninoRoot['--fontsize-small'] ?? '' ) === $theirRoles['--fontsize-small']
		&& ( $ninoRoot['--fontsize-big'] ?? '' ) === $theirRoles['--fontsize-big'] && ( $ninoRoot['--fontsize-btn'] ?? '' ) === $theirRoles['--fontsize-btn'] );

	check( 'the compiled roles add nothing to the delivered ones: every role of section 4 is one the delivered section 2 assigns'. ( $added === [] ? '' : ' - added: '. implode( ', ', $added ) ), $added === [] );
}

/*	The roles are held to the kernel as well, wherever the checkout has one:
	every --color-* role Nino.css reads without a fallback is assigned by
	base.css - but for the five Nino.css derives itself, in its own :root and
	per ground (--color-ground and --color-ink are the ground's, --color-accent,
	--color-focus and --color-title-default follow from the roles around them),
	and the border of each of the seven grounds, which it leaves at the border
	of the page as the delivered theme.css does.
	A role the stylesheet reads and nobody assigns is the kernel's default
	wearing the theme's name	*/
if( $ninoCss === '' )
	echo "  --  this checkout has no Nino.css, so the roles are not compared with the kernel's\n";
else {

	preg_match_all( '/var\(\s*(--color-[a-z0-9-]+)\s*\)/', $ninoCss, $read );

	$read				= array_values( array_unique( $read[1] ) );
	$derived		= [ '--color-accent', '--color-focus', '--color-ground', '--color-ink', '--color-title-default' ];
	$grounds		= [ 'default', 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ];
	$followed		= array_map( static fn( string $ground ): string => '--color-border-'. $ground, $grounds );
	$unassigned	= array_values( array_diff( $read, array_keys( $baseRoles ), $derived, $followed ) );
	$ninoDeclared = designDeclarations( designBlock( $ninoCss, ':root {' ) );

	check( 'every --color-* role the kernel\'s Nino.css reads without a fallback is assigned by base.css, but for the five it derives itself and the border of each ground'
		. ( $unassigned === [] ? '' : ' - unassigned: '. implode( ', ', $unassigned ) ), $unassigned === [] && count( $read ) > 50 );
	check( '...and those five are roles its own :root declares', array_diff( $derived, array_keys( $ninoDeclared ) ) === [] );
	check( '...and base.css assigns no role Nino.css does not know', array_diff( array_keys( $baseRoles ), array_keys( $ninoDeclared ) ) === [] );

	/*	The border of every ground is Nino.css's: the seven roles it bends per
		ground default to the border of the page, which is what the delivered
		theme.css leaves them at too. Assigning them from the border of each
		surface would draw a line on an alt, tint, dark, black or brand ground
		in another colour than the delivered file does - a badge fill and a
		timeline marker with it - and the defaults would not look as they did	*/
	$borders = [];

	foreach( $followed as $role )
		if( isset( $baseRoles[$role] ) === true || ( $ninoDeclared[$role] ?? '' ) !== 'var(--color-border)' )
			$borders[] = $role;

	check( 'base.css leaves --color-border-<ground> to Nino.css, which gives each of the seven grounds the border of the page'. ( $borders === [] ? '' : ' - wrong: '. implode( ', ', $borders ) ), $borders === [] );
	check( '...and the roles the model never writes stay out of the compiled roles: the font families and the sizes are the type\'s',
		preg_match( '/--fontfamily|--font-|--text-/', designBlock( designPlain( designSection( $defaultSheet, 4, Compiler::SECTION ) ), ':root {' ) ) !== 1 );
}


echo "\nColours - the surfaces a look bands with, solved rather than written, in the mode that was asked for\n";

$colours		= Colours::css( [] );
$blocks			= designAutoBlocks( $colours );
$genLight		= $blocks['light'];
$genDark		= $blocks['chosen'];

/*	The property that makes the tab adoptable, and the reason the maths was
	lifted unchanged rather than rewritten: with nothing touched, the solver
	lands on the framework's own palette to the byte. A project that never
	opens this tab therefore compiles to the colours the token layer already
	declares, and one that does gets the same tokens one block further down the
	same cascade. The light palette is base.css's own section 1; the dark one is
	held to the delivered theme.css, which is the only file that still has it	*/
$onlyLight = array_intersect_key( $baseTokens, $genLight );

check( 'the untouched palette reproduces the token layer of base.css exactly, light - every value of it', $genLight !== [] && $onlyLight === $genLight );

if( is_file( $deliveredFile ) === false )
	echo "  --  this checkout has no installer library, so the dark palette is not compared with the delivered one\n";
else {
	$deliveredDark = designDeclarations( designBlock( designPlain( (string) file_get_contents( $deliveredFile ) ), ':root[data-nino-mode="dark"] {' ) );
	check( '...and dark, against the delivered theme.css', $genDark !== [] && array_intersect_key( $deliveredDark, $genDark ) === $genDark );
}

check( 'the dark palette is the same set of tokens, under the media query as under the attribute', array_keys( $blocks['system'] ) === array_keys( $genDark ) && $blocks['system'] === $genDark );
check( 'every block publishes the full surface vocabulary: eight values for each of the surfaces, and the scrim - and none for a state nothing reads',
	count( $genLight ) === count( $genDark ) && count( $genLight ) === count( Colours::SURFACES ) * 8 + 1
	&& preg_grep( '/-(hover|active)$/', array_keys( $genLight ) ) === [] && isset( $genLight['--nino-accent'] ) === false && isset( $genLight['--nino-scrim'] ) === true );
check( 'the surfaces are the grounds, the brand as three roles and the three states - the second colour as it was picked is no surface',
	Colours::SURFACES === [ 'default', 'alt', 'tint', 'dark', 'black', 'brand', 'brand-safe', 'accent-safe', 'success', 'warning', 'danger' ] );

/*	Three reader states, not two: an explicit choice stamps data-nino-mode, the
	default "follow the system" setting stamps nothing at all - so the media
	query has to carry the unstamped case while the attribute rule wins in both
	directions once somebody has chosen	*/
check( 'the light palette is the bare :root', str_starts_with( $colours, ":root {\n\t--nino-default:" ) === true );
check( 'the system default is carried by the media query', str_contains( $colours, '@media (prefers-color-scheme: dark) {' ) === true
	&& str_contains( $colours, ':root:not([data-nino-mode="light"]) {' ) === true );
check( '...and an explicit choice wins in both directions', str_contains( $colours, ':root[data-nino-mode="dark"] {' ) === true );
check( 'no color-scheme declaration of its own - base.css sets that, this only replaces values', preg_match( '/^\s*color-scheme\s*:/m', $colours ) !== 1 );

/*	The mode says which of the two palettes a visitor sees. Auto is the shape
	above; light and dark write their palette alone, in :root, with no media
	query and no attribute - so the site is that palette whatever the visitor's
	system says, and the switch a visitor may have finds nothing to switch to	*/
foreach( [ 'light', 'dark' ] as $only ) {

	$alone = Colours::css( [ 'mode' => $only ] );

	check( 'the mode "'. $only. '" writes that palette alone, in the bare :root - no media query, no attribute',
		str_starts_with( $alone, ":root {\n\t--nino-default:" ) === true && str_contains( $alone, '@media' ) === false && str_contains( $alone, 'data-nino-mode' ) === false
		&& designDeclarations( $alone ) === ( $only === 'light' ? $genLight : $genDark ) && substr_count( $alone, '--nino-default:' ) === 1 );
}

$inSheet = static fn( string $mode ): string => designPlain( designSection( Compiler::compile( Setup::normalize( [ 'colours' => [ 'mode' => $mode ] ], $library ), $library ), 2, Compiler::SECTION ) );

check( 'the compiled sheet carries the palette in the shape of the mode - three blocks for Auto, one for light and one for dark - and the colour scheme of the page with it',
	substr_count( $inSheet( 'auto' ), '--nino-default:' ) === 3 && preg_match( '/^\s*color-scheme\s*:/m', $inSheet( 'auto' ) ) !== 1
	&& substr_count( $inSheet( 'light' ), '--nino-default:' ) === 1 && str_contains( $inSheet( 'light' ), 'color-scheme: light;' ) === true
	&& substr_count( $inSheet( 'dark' ), '--nino-default:' ) === 1 && str_contains( $inSheet( 'dark' ), 'color-scheme: dark;' ) === true
	&& str_contains( $inSheet( 'dark' ), 'data-nino-mode' ) === false && str_contains( $inSheet( 'light' ), 'prefers-color-scheme' ) === false );

/*	The one line in the engine that is not negotiable. 4.5:1 is WCAG 2.2
	SC 1.4.3 for body copy, and it holds for every solved surface in both
	modes. The brand is the exception and says so: it is the colour the picker
	returned, byte for byte, so there is no lightness left to solve with -
	which is exactly why the two -safe roles exist	*/
$unsafe = [];

foreach( [ 'light', 'dark' ] as $mode )
	foreach( Colours::palette( [], $mode ) as $surface => $values )
		if( $surface !== 'brand' && Colours::contrast( $values['on'], $values['bg'] ) < 4.5 )
			$unsafe[] = $mode. '/'. $surface;

check( 'every solved surface clears 4.5:1 in both modes - but the brand, which is the colour that was picked', $unsafe === [] );

$corporate = [ 'primary' => '#8b1d3f', 'secondary' => '#0f766e' ];
$picked2 = Colours::palette( $corporate, 'light' );

check( 'the brand is the hex that was typed, untouched', $picked2['brand']['bg'] === '#8b1d3f' );
check( '...and the second colour is no surface of its own, but the brand-safe role of it is', isset( $picked2['accent'] ) === false && isset( $picked2['accent-safe'] ) === true );
check( '...while the -safe roles are the same colours solved until text survives',
	Colours::contrast( $picked2['brand-safe']['on'], $picked2['brand-safe']['bg'] ) >= 4.5
	&& Colours::contrast( $picked2['accent-safe']['on'], $picked2['accent-safe']['bg'] ) >= 4.5 );
check( 'a corporate hex still clears the target on every solved surface',
	array_filter( Colours::palette( $corporate, 'dark' ), static fn( array $v, string $k ): bool =>
		$k !== 'brand' && Colours::contrast( $v['on'], $v['bg'] ) < 4.5, ARRAY_FILTER_USE_BOTH ) === [] );

/*	...and the one value the panel needs published on its own: the second
	colour as it will be compiled. A Secondary nobody set is not a missing
	value but a derived one, and the swatch standing for it has to show the
	colour the wheel produced rather than the primary standing in for it	*/
check( 'the second colour is published, as typed where one was typed', Colours::accent( $corporate ) === '#0f766e' );

$wheel = [ 'primary' => '#8b1d3f' ];
$derived = [];

foreach( [ 1, 2, 3, 4 ] as $harmony )
	$derived[$harmony] = Colours::accent( $wheel + [ 'harmony' => $harmony ] );

check( '...and where none was, it is what Harmony carried round the wheel - a different colour at every position but the first',
	$derived[1] === '#8b1d3f' && count( array_unique( $derived ) ) === 4
	&& preg_match( '/^#[0-9a-f]{6}$/', $derived[4] ) === 1 );
$drift = [];

foreach( [ 1, 2, 3, 4 ] as $harmony ) {

	$settings = $wheel + [ 'harmony' => $harmony ];
	$apart		= fmod( abs( rad2deg( Colours::oklch( Colours::accent( $settings ) )[2] - Colours::oklch( Colours::palette( $settings, 'light' )['accent-safe']['bg'] )[2] ) ), 360.0 );

	if( min( $apart, 360.0 - $apart ) > 2.0 )
		$drift[] = $harmony;
}

check( '...which is the hue the palette makes accent-safe from, at every position of Harmony - so the swatch cannot drift from the stylesheet', $drift === [] );

// Status hues are fixed on purpose - no brand knob may turn a danger surface
// into something reassuring
$hot = Colours::palette( [ 'primary' => '#2e7d32' ], 'light' );
check( 'red stays red whatever the brand is', $hot['danger']['bg'] !== $hot['success']['bg']
	&& Colours::oklch( $hot['danger']['bg'] )[2] < 1.0 );

// ...and a knob that moves has to move something - compared light with light, the block it is in
$flat = designAutoBlocks( Colours::css( [ 'depth' => 1 ] ) )['light'];
$neutral = designAutoBlocks( Colours::css( [ 'temperature' => 1 ] ) )['light'];
check( 'Depth moves the alternate ground', $flat['--nino-alt'] !== $genLight['--nino-alt'] );
check( 'Temperature takes the colour out of the greys at Neutral', $neutral['--nino-alt'] !== $genLight['--nino-alt'] );
check( 'an unknown knob position falls back rather than indexing a table with it',
	Colours::normalize( [ 'contrast' => 99, 'primary' => 'nonsense', 'mode' => 'twilight' ] ) === Colours::normalize( [] ) );

$choices = Colours::choices();

check( 'the panel draws what choices() publishes: the five knobs and then the mode, whose steps are the words the setup keeps and whose default is the first of them',
	array_keys( $choices ) === [ 'harmony', 'temperature', 'saturation', 'contrast', 'depth', 'mode' ]
	&& $choices['mode'] === [ 'default' => 'auto', 'steps' => [ 'auto', 'light', 'dark' ] ]
	&& count( $choices['harmony']['steps'] ) === 4 && count( $choices['contrast']['steps'] ) === 3 );

// The compiled sheet carries it, in the section the order test named
check( 'the compiled sheet carries the palette', str_contains( $css, ':root[data-nino-mode="dark"] {' ) === true
	&& substr_count( $css, '--nino-brand-safe:' ) >= 1 );

/*	The three scales that reach furthest at their outer positions - Saturation,
	Contrast and Depth - and what none of those positions may do. Position 2 is
	the framework itself (the base.css identity above); 1 and 3 are free to go
	as far as the floors allow, and the floors are what is held here: for every
	combination of the three, in both modes, on four primaries that are
	nothing alike - a light blue, a dark red, a yellow, a near-black - the
	text clears 4.5:1 (10:1 at Strong), muted text 4.5:1, a link its own
	target (9.98:1 at Strong), a border and the focus ring the 3:1 of
	SC 1.4.11, and a Raised border 7:1 where the ground leaves room for one. The
	brand is the one surface that is the colour that was picked, so as in the
	check above it is not measured for text.	*/
$reach = [];
$reached = 0;

foreach( [ '#4faae8', '#8b1d3f', '#facc15', '#111827' ] as $primary )
	foreach( [ 1, 2, 3 ] as $saturation )
		foreach( [ 1, 2, 3 ] as $contrast )
			foreach( [ 1, 2, 3 ] as $depth )
				foreach( [ 'light', 'dark' ] as $mode ) {

					$settings = [ 'primary' => $primary, 'saturation' => $saturation, 'contrast' => $contrast, 'depth' => $depth ];
					$label		= $primary. ' sat '. $saturation. ' con '. $contrast. ' dep '. $depth. ' '. $mode;

					foreach( Colours::palette( $settings, $mode ) as $surface => $v ) {

						$reached++;

						if( $surface === 'brand' )
							continue;

						$ratio = static fn( string $key ): float => Colours::contrast( $v[$key], $v['bg'] );
						// Text holds the 10:1 at Strong. Only a link has the solver
						// stopping within a hair of its target (9.99:1 on some
						// harmonies), so that one is held to 9.98
						$text	= $contrast === 3 ? 10.0 : 4.5;
						$link	= $contrast === 3 ? 9.98 : 4.5;

						if( $ratio( 'on' ) < $text )
							$reach[] = $label. ' '. $surface. ' on';
						if( $ratio( 'on-muted' ) < 4.5 )
							$reach[] = $label. ' '. $surface. ' on-muted';
						if( $ratio( 'link' ) < $link )
							$reach[] = $label. ' '. $surface. ' link';
						if( $ratio( 'focus' ) < 3.0 )
							$reach[] = $label. ' '. $surface. ' focus';
						if( $ratio( 'border' ) < 3.0 )
							$reach[] = $label. ' '. $surface. ' border';
						if( $depth === 3 && in_array( $surface, [ 'default', 'alt', 'tint', 'dark', 'black' ], true ) === true && $ratio( 'border' ) < 7.0 )
							$reach[] = $label. ' '. $surface. ' border at 7:1';
					}
				}

check( 'no position of Saturation, Contrast or Depth takes a floor away, in either mode, on four unlike primaries ('. $reached. ' surfaces)'
	. ( $reach === [] ? '' : ' - '. implode( ', ', array_slice( $reach, 0, 5 ) ) ), $reach === [] );

$chromaAt = static function( int $saturation ): float {
	return Colours::oklch( Colours::palette( [ 'primary' => '#4faae8', 'saturation' => $saturation ], 'light' )['brand-safe']['bg'] )[1];
};

check( 'Saturation never takes colour away as it goes up, and Muted and Rich are apart from Standard',
	$chromaAt( 1 ) <= $chromaAt( 2 ) && $chromaAt( 2 ) <= $chromaAt( 3 ) && $chromaAt( 1 ) < $chromaAt( 2 ) - 0.02 );

$depthOf = static fn( int $depth, string $mode ): array => Colours::palette( [ 'depth' => $depth ], $mode );

foreach( [ 'light', 'dark' ] as $depthMode ) {
	check( 'Depth 1 is not Depth 2 in '. $depthMode. ': the band is not the same',
		$depthOf( 1, $depthMode )['alt']['bg'] !== $depthOf( 2, $depthMode )['alt']['bg'] );
	// Flat is flatter, not gone: with no band at all the alternate sections
	// vanish into the page
	$band = Colours::contrast( $depthOf( 1, $depthMode )['alt']['bg'], $depthOf( 1, $depthMode )['default']['bg'] );
	check( '...and Flat keeps a trace of the band in '. $depthMode. ' ('. round( $band, 3 ). ':1 against the page)', $band >= 1.015 && $band < 1.05 );
}

/*	The shadow a surface casts is the palette's colour and not its depth: one
	alpha for a mode, whatever the surface and whatever the Depth, denser in the
	dark palette because black at 12% over a dark ground is invisible. How much
	of a shadow a card throws is the content template's to say	*/
$shadows = [];

foreach( [ 'light', 'dark' ] as $shadowMode )
	foreach( [ 1, 2, 3 ] as $depth )
		$shadows[$shadowMode][] = array_values( array_unique( array_column( $depthOf( $depth, $shadowMode ), 'shadow' ) ) );

check( 'the shadow is one colour for every surface at every Depth, per mode: the same value at Flat, Standard and Raised',
	count( array_unique( array_map( 'json_encode', $shadows['light'] ) ) ) === 1 && count( $shadows['light'][0] ) === 1
	&& count( array_unique( array_map( 'json_encode', $shadows['dark'] ) ) ) === 1 && count( $shadows['dark'][0] ) === 1 );

preg_match( '/\/\s*(\d+)%\)/', $shadows['light'][0][0], $lightAlpha );
preg_match( '/\/\s*(\d+)%\)/', $shadows['dark'][0][0], $darkAlpha );

$alphas = ( new ReflectionClassConstant( Colours::class, 'SHADOW' ) )->getValue();

check( '...and it is the delivered one in the light palette, and denser in the dark - '. $shadows['light'][0][0]. ' against '. $shadows['dark'][0][0],
	$shadows['light'][0][0] === $baseTokens['--nino-default-shadow'] && (int) ( $darkAlpha[1] ?? 0 ) > (int) ( $lightAlpha[1] ?? 100 ) && (int) ( $lightAlpha[1] ?? 0 ) > 0
	&& (int) ( $lightAlpha[1] ?? -1 ) === (int) round( $alphas['light'] * 100 ) && (int) ( $darkAlpha[1] ?? -1 ) === (int) round( $alphas['dark'] * 100 ) );

/*	The scrim over a cover photograph has a table of its own. At Strong the
	text asks 10:1, and a scrim solved to that would take a photograph from 80%
	dark to about 91% - nearly black - for a headline that is far above AAA
	already at 7:1	*/
$scrimAt = static function( int $contrast ): array {
	preg_match_all( '/--nino-scrim:\s*rgb\([^\/]+\/\s*(\d+)%\)/', Colours::css( [ 'contrast' => $contrast ] ), $found );
	return array_map( 'intval', $found[1] );
};

check( 'Strong does not darken a cover photograph beyond 7:1 - the scrim stays at or under 80%, in both modes',
	$scrimAt( 3 ) !== [] && max( $scrimAt( 3 ) ) <= 80 );
check( 'the revision of the colour tables is a number that can be raised, and it was when the shadow stopped following Depth', Colours::REVISION >= 2 );

echo "\nWriting it, and what it will not write over\n";

$written = Compiler::write( $appData, $css );
check( 'the first write succeeds - there is no theme.css in a sandbox yet', $written === true );
check( '...and produces the file the css bundle already names', is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true );

$onDisk = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' );
check( 'a sheet Design wrote knows itself', Compiler::stamped( $onDisk ) === true );

$second = Compiler::write( $appData, Compiler::compile( Setup::normalize( [ 'size' => 'l' ], $library ), $library ) );
check( 'compiling again writes over its own file without being asked twice', $second === true
	&& str_contains( (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' ), '131.25%' ) === true );

// The delivered theme.css is explicitly editable by hand, so the file this
// finds on a first run may well be somebody's work
file_put_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css', "/* mine */\nbody { color: red; }\n" );
$refused = Compiler::write( $appData, $css );

check( 'a stylesheet Design did not write is not overwritten, and the refusal says why', $refused !== true
	&& str_contains( (string) $refused, 'not written by Design' ) === true );
check( '...and the file is still exactly what it was', (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === "/* mine */\nbody { color: red; }\n" );

check( 'an edit to a sheet Design did write is caught the same way', ( static function( array $appData, string $css ): bool {
	$path = ninoSandboxDir( $appData ). '/private/assets/theme.css';
	file_put_contents( $path, $css. "\nbody { color: blue; }\n" );
	return Compiler::stamped( (string) file_get_contents( $path ) ) === false;
} )( $appData, $css ) );

check( 'forcing it through overwrites anyway', Compiler::write( $appData, $css, true ) === true );

echo "\nActivation, and the one path applying takes\n";

$appData['/nino/modules'] = [];
$result = \Nino\Features::activate( $appData, 'design' );

check( 'the feature activates through \\Nino\\Features', $result === true );
check( '...and lists its class in /nino/modules', in_array( '\\Nino\\Modules\\Design', (array) $appData['/nino/modules'], true ) === true );
check( '...and brings its panel along', \Nino\Modules\Design::adminPanels( $appData ) === [ \Nino\Modules\Design\Admin::class ] );

// A fresh install has the delivered theme.css in place, and apply() must not
// walk over it without being told to
\Nino\Filesystem::putFileContent( $appData, '/assets/theme.css', "/* delivered */\n" );

$notes 	= [];
$guarded = \Nino\Modules\Design::apply( $appData, $notes );

check( 'applying over the delivered stylesheet is refused, not done quietly', $guarded !== true && str_contains( (string) $guarded, 'not written by Design' ) === true );

$notes 	= [];
$applied = \Nino\Modules\Design::apply( $appData, $notes, true );

check( 'applying for the first time, told to take the file over, succeeds', $applied === true && $notes === [] );

$stored = \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] );

check( 'the setup is written beside it, with the format it is in', is_array( $stored ) === true && ( $stored['format'] ?? 0 ) === Setup::FORMAT );
check( '...recording what was compiled, so the panel can tell the file from the setup', ( $stored['compiled']['sha'] ?? '' ) !== '' && ( $stored['compiled']['at'] ?? '' ) !== '' );
/*	The file holds what was chosen and nothing worked out from it, which is what
	the manual promises of it: the keys of the model and no more, a template
	named by its set and not by a record of it. What a reinstall that changed a
	set has to be told by is 'compiled'['input'], which hashes those same files
	itself	*/
check( '...and it holds what was chosen and nothing derived from it: the keys of the model, a template by its name, a knob by its position',
	array_keys( $stored ) === Setup::KEYS && $stored['parts'] === array_fill_keys( array_keys( Setup::PARTS ), 'v1' )
	&& $stored['knobs'] === Setup::defaults( $library )['knobs'] );

/*	upgrade() reads the setup through normalize() and writes it back, and that
	is the whole of what an upgrade does here - a new version may ship changed
	sets, and moving a site nobody asked to move is not an upgrade. What the
	version stored that this one does not know goes	*/
$legacy = $stored;
$legacy['step'] = 'written by a version before this one';
$legacy['knobs']['spacing'] = 'more';
\Nino\Filesystem::putFileContent( $appData, Setup::PATH, $legacy );

check( 'upgrading drops what an older version stored and leaves the choices alone', \Nino\Modules\Design::upgrade( $appData, '0.1.0' ) === true );
$upgraded = \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] );
check( '...so the file holds the same choices, without it', array_keys( $upgraded ) === Setup::KEYS && isset( $upgraded['knobs']['spacing'] ) === false
	&& $upgraded['parts'] === $stored['parts'] && $upgraded['knobs'] === $stored['knobs']
	&& ( $upgraded['compiled']['input'] ?? '' ) === ( $stored['compiled']['input'] ?? '' ) );

/*	A file of the format before this one is not read, and an upgrade does not
	replace it either: the note the panel gives is all a person has to learn that
	the choices of the version before were not taken over, and an upgrade that
	wrote the defaults over it would have taken the note away with the file	*/
\Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'format' => 1, 'size' => 'l', 'parts' => [ 'header' => [ 'set' => 'v3' ] ] ] );
$former = \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] );

check( 'upgrading leaves a file of the format before this one as it is', \Nino\Modules\Design::upgrade( $appData, '1.9.0' ) === true
	&& \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] ) === $former );

$notes = [];
Setup::read( $appData, $library, $notes );

check( '...so the panel can still say that it was not read - until the first save writes the new one', count( $notes ) === 1 && str_contains( $notes[0], 'another format' ) === true );

\Nino\Filesystem::putFileContent( $appData, Setup::PATH, $upgraded );

/*	A frame is a stylesheet AND the markup it was drawn against. Compiling one
	without writing the other is how a page ends up with v3's css over v1's
	html, which is exactly what this did before the panel went looking	*/
// The two templates the kernel's base unit delivers, which html-header.tpl and html-footer.tpl include
check( 'the frames it writes are the base unit\'s frame-header.tpl and frame-footer.tpl', sprintf( Compiler::FRAME_TARGET, 'header' ) === '/templates/frame-header.tpl'
	&& sprintf( Compiler::FRAME_TARGET, 'footer' ) === '/templates/frame-footer.tpl' );
$headerTemplate = \Nino\Filesystem::path( $appData, sprintf( Compiler::FRAME_TARGET, 'header' ) );
check( 'applying writes the chosen frame\'s markup, not only its stylesheet',
	is_file( $headerTemplate ) === true
	&& str_contains( (string) file_get_contents( $headerTemplate ), 'nino-scroll-header' ) === true
	&& Compiler::stampedFrame( (string) file_get_contents( $headerTemplate ) ) === true );
check( '...and the markup is the variant the setup names', str_contains( (string) file_get_contents( $headerTemplate ), 'header v1' ) === true );

$byHand = "<header>my own</header>\n";
file_put_contents( $headerTemplate, $byHand );
$notes = [];
$stylesheetBefore = (string) file_get_contents( \Nino\Filesystem::path( $appData, Compiler::TARGET ) );
// A setup that would compile to something else, so "nothing was written"
// is a statement about this run rather than about two identical files
$sizedSetup = Setup::read( $appData, \Nino\Modules\Design::libraryDir(), $notes );
$sizedSetup['size'] = $sizedSetup['size'] === 'l' ? 'm' : 'l';
Setup::write( $appData, $sizedSetup );
$refusedFrame = \Nino\Modules\Design::apply( $appData, $notes );
check( 'a frame template somebody edited is not overwritten either', $refusedFrame !== true
	&& str_contains( (string) $refusedFrame, 'frame-header.tpl was not written by Design' ) === true
	&& file_get_contents( $headerTemplate ) === $byHand );
// "Nothing was overwritten" is what the refusal says, and it has to be true:
// the stylesheet used to be written first, so a hand-taken header left the
// project with a new theme.css, an old header and a message saying neither
check( '...and nothing else was written either, which is what the refusal says',
	(string) file_get_contents( \Nino\Filesystem::path( $appData, Compiler::TARGET ) ) === $stylesheetBefore );
$notes = [];
check( 'and forcing it through takes that one over too', \Nino\Modules\Design::apply( $appData, $notes, true ) === true
	&& Compiler::stampedFrame( (string) file_get_contents( $headerTemplate ) ) === true );

check( 'applying again needs no force - the file is now one of ours', \Nino\Modules\Design::apply( $appData ) === true );

check( 'the compiled sheet is what the bundle already points at', in_array( Compiler::TARGET,
	(array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), true ) === false
	&& Compiler::TARGET === '/assets/theme.css' );

check( 'deactivating leaves the compiled sheet and the setup where they are', \Nino\Features::deactivate( $appData, 'design' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/data/design.php' ) === true );

echo "\n";

echo "\nThe panel: what it lists, what it saves, and what it refuses to overwrite\n";

function callDesignAction( array &$appData, string $method, array $post = [] ): array {
	$_POST['data'] = json_encode( $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	Admin::$method( $appData, $request );
	$_POST = [];
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ];
}

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

[ $status, $listed ] = callDesignAction( $appData, 'apiList' );
$onFile = Setup::read( $appData, $library );
check( 'the panel lists one row per part, in the order the cascade wants them', $status === 200
	&& array_column( (array) ( $listed['parts'] ?? [] ), 'part' ) === array_keys( Setup::PARTS ) );
check( 'a row carries the catalogue it can be given, and what is chosen',
	isset( $listed['parts'][0]['catalogue']['v1'] ) === true
	&& ( $listed['parts'][0]['set'] ?? null ) === 'v1'
	&& ( $listed['parts'][0]['kind'] ?? null ) === 'frame' && ( $listed['parts'][2]['kind'] ?? null ) === 'set' );
check( 'every variant comes with the name and description its own file carries',
	( $listed['parts'][0]['catalogue']['v1']['name'] ?? '' ) === 'Plain bar'
	&& str_contains( (string) ( $listed['parts'][0]['catalogue']['v1']['description'] ?? '' ), 'rule under it' ) === true
	&& ( $listed['parts'][2]['catalogue']['v2']['name'] ?? '' ) === 'Poster' );
check( 'a template of the tabs names the knobs it answers to, each of them - and a frame names none, it answers to none',
	( $listed['parts'][2]['catalogue']['v1']['knobs'] ?? null ) === Setup::KNOBS['content']
	&& ( $listed['parts'][2]['catalogue']['v4']['knobs'] ?? null ) === Setup::knobs( $library, 'content', 'v4' )
	&& count( $listed['parts'][2]['catalogue']['v4']['knobs'] ) < count( Setup::KNOBS['content'] )
	&& ( $listed['parts'][3]['catalogue']['v3']['knobs'] ?? null ) === Setup::knobs( $library, 'buttons', 'v3' )
	&& array_key_exists( 'knobs', $listed['parts'][0]['catalogue']['v1'] ) === false );
check( 'and the screen is handed the vocabulary it draws: the tabs with their knobs, the steps, the sizes, the widths\' words, the effects with their strengths and speeds',
	( $listed['tabs'] ?? null ) === Setup::KNOBS && ( $listed['steps'] ?? null ) === Setup::STEPS
	&& ( $listed['sizes'] ?? null ) === array_keys( Setup::SIZES ) && ( $listed['size'] ?? null ) === $onFile['size'] && ( $listed['width'] ?? null ) === 0
	&& ( $listed['motion'] ?? null ) === [ 'effect' => '', 'strength' => '', 'speed' => '' ]
	&& ( $listed['effects'] ?? null ) === array_keys( Setup::EFFECTS ) && ( $listed['strengths'] ?? null ) === Setup::STRENGTHS
	&& ( $listed['speeds'] ?? null ) === array_keys( Setup::SPEEDS ) );
check( '...where every knob stands, the five font roles with what each has and what each can be given, and the sizes with the one Nino.css gives a role',
	( $listed['knobs'] ?? null ) === $onFile['knobs'] && $onFile['knobs'] === Setup::defaults( $library )['knobs']
	&& ( $listed['roles'] ?? null ) === Fonts::ROLES && ( $listed['fonts'] ?? null ) === Fonts::DEFAULTS
	&& ( $listed['weights'] ?? null ) === Fonts::WEIGHTS && ( $listed['fontsizes'] ?? null ) === Fonts::SIZES && ( $listed['inherited'] ?? null ) === Fonts::INHERITED
	&& ( $listed['options']['title'] ?? null ) === Fonts::options( 'title' ) && ( $listed['options']['code'] ?? null ) === [ '' ] );
check( '...and the families with their names, their category and whether they are a system stack - and not one file name or url',
	array_keys( (array) ( $listed['families'] ?? [] ) ) === array_keys( Fonts::families() )
	&& ( $listed['families']['league-spartan'] ?? null ) === [ 'name' => 'League Spartan', 'category' => 'sans', 'system' => false ]
	&& ( $listed['families']['system-mono']['system'] ?? null ) === true && str_contains( json_encode( $listed['families'] ), 'woff2' ) === false );
check( 'the file on disk is ours and answers to the setup', ( $listed['exists'] ?? null ) === true
	&& ( $listed['ours'] ?? null ) === true && ( $listed['current'] ?? null ) === true );

// Saving is not compiling: the decision lands, the stylesheet does not move
[ $status, $saved ] = callDesignAction( $appData, 'apiSave', [
	'parts' => [ 'content' => 'v2', 'buttons' => 'v3', 'components' => 'v3' ],
	'knobs' => [ 'content' => [ 'hero-title' => 1, 'shadow' => 1, 'corners' => -1 ], 'buttons' => [ 'corners' => 1, 'small' => -1 ], 'components' => [ 'zebra' => 1, 'labels' => 1 ] ],
	'size' => 'l', 'width' => 1,
	'motion' => [ 'effect' => 'slide-left', 'strength' => 'soft', 'speed' => 'fast' ],
	'fonts' => [ 'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'uppercase' => true, 'size' => '2.4' ] ],
	'colours' => [ 'mode' => 'dark' ],
] );
$stored = Setup::read( $appData, \Nino\Modules\Design::libraryDir() );

check( 'saving stores the selection: the templates, the size, the width, the animation, the fonts and the mode', $status === 200
	&& $stored['parts']['content'] === 'v2' && $stored['parts']['buttons'] === 'v3' && $stored['size'] === 'l' && $stored['width'] === 1
	&& $stored['motion'] === [ 'effect' => 'slide-left', 'strength' => 'soft', 'speed' => 'fast' ]
	&& $stored['fonts']['title'] === [ 'family' => 'system-serif', 'weight' => 'light', 'uppercase' => true, 'size' => '2.4' ]
	&& $stored['colours']['mode'] === 'dark' );
check( '...and says the file no longer answers to it, rather than moving it', ( $saved['current'] ?? null ) === false
	&& ( $saved['ours'] ?? null ) === true );
check( 'a knob stands where it was put, for the knobs the chosen template answers to',
	$stored['knobs']['content']['hero-title'] === 1 && $stored['knobs']['content']['corners'] === -1
	&& $stored['knobs']['buttons']['small'] === -1 && $stored['knobs']['components']['labels'] === 1 );
check( '...and a position for a knob the chosen template does not answer to is not kept: Poster has no shadow, Square no corners, Airy no zebra',
	$stored['knobs']['content']['shadow'] === 0 && $stored['knobs']['buttons']['corners'] === 0 && $stored['knobs']['components']['zebra'] === 0 );

/*	What the server answers for a project with this selection - another template
	for each of the three tabs, a font, the width, the animation and the mode moved -
	is written down for design-js-smoke.js, which draws the screen from it: the
	stand-in listing of that test is only as good as it is like this one	*/
[ , $forScreen ] = callDesignAction( $appData, 'apiList' );
$listingFile = ninoSandboxDir( $appData ). '/design-listing.json';
file_put_contents( $listingFile, json_encode( $forScreen, JSON_THROW_ON_ERROR ) );

[ $status, $applied ] = callDesignAction( $appData, 'apiApply' );
$appliedSheet = (string) \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' );

check( 'applying compiles it and the file answers again', $status === 200 && ( $applied['current'] ?? null ) === true );
check( '...and the root size, the width and the animation the save asked for are in the stylesheet', str_contains( $appliedSheet, '131.25%' ) === true
	&& str_contains( $appliedSheet, '--grid-max-width: 144rem;' ) === true && str_contains( $appliedSheet, '--vpa-duration: .25s;' ) === true
	&& str_contains( $appliedSheet, '--font-title-transform: uppercase;' ) === true && str_contains( $appliedSheet, 'color-scheme: dark;' ) === true );

// A hand edit makes the file somebody else's again, and the panel says so
\Nino\Filesystem::putFileContent( $appData, Compiler::TARGET, "/* edited by hand */\n" );
[ , $foreign ] = callDesignAction( $appData, 'apiList' );
check( 'a file somebody edited reads as not ours', ( $foreign['ours'] ?? null ) === false && ( $foreign['exists'] ?? null ) === true );
check( 'and applying over it is a 409 with the reason, not a silent overwrite',
	callDesignAction( $appData, 'apiApply' ) === [ 409, [ 'error' => 'assets/theme.css was not written by Design - it is the delivered file, or somebody edited it. Nothing was overwritten.' ] ] );
check( '...the file is still exactly what it was',
	\Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' ) === "/* edited by hand */\n" );
check( 'forcing it through takes the file over', callDesignAction( $appData, 'apiApply', [ 'force' => true ] )[0] === 200
	&& Compiler::stamped( (string) \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' ) ) === true );

// A set that is not in the library is not written, and the answer says why
[ $status, $refused ] = callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'content' => 'nope' ], 'size' => 'm' ] );
check( 'a variant that is not in the library falls back and is named', $status === 200
	&& count( (array) ( $refused['notes'] ?? [] ) ) === 1
	&& str_contains( (string) ( $refused['notes'][0] ?? '' ), '"nope"' ) === true );
check( 'a set name that could climb out of the library never becomes a path',
	callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'content' => '../../../etc/passwd' ], 'size' => 'm' ] )[0] === 200
	&& Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['parts']['content'] === 'v1' );

// ...and neither does a size that is not a string, or any other key of the
// model posted as the wrong type. Cast, an array raises "Array to string
// conversion", a level the kernel treats as fatal - a 500 on the panel's own
// save where a fallback is the answer
ninoWarnings();
check( 'a size posted as an array falls back rather than raising',
	callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'size' => [ 'l' ] ] )[0] === 200
	&& ninoWarnings() === []
	&& Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['size'] === 'm' );
check( '...and so does every other key of it',
	callDesignAction( $appData, 'apiSave', [ 'parts' => 'v1', 'knobs' => 'more', 'size' => 5, 'width' => [ 1 ], 'motion' => 'flip', 'fonts' => 5, 'colours' => 'dark' ] )[0] === 200
	&& ninoWarnings() === [] && Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['width'] === 0 );
echo "\nApplying asks first, keeps the version before, and restores it\n";

$slotPath	= \Nino\Filesystem::path( $appData, Previous::PATH );
$targets	= Previous::targets();
$pathOf		= static fn( string $target ): string => \Nino\Filesystem::path( $appData, $target );

/** Every file Design writes as it is on disk right now: bytes, or null where there is none */
$onDisk = static function() use ( $targets, $pathOf ): array {
	$files = [];
	foreach( $targets as $target )
		$files[$target] = is_file( $pathOf( $target ) ) === true ? (string) file_get_contents( $pathOf( $target ) ) : null;
	return $files;
};

/** A plan, by target */
$planOf = static function() use ( &$appData ): array {
	return array_column( (array) ( callDesignAction( $appData, 'apiPlan' )[1]['files'] ?? [] ), null, 'target' );
};

check( 'the files Design writes are the stylesheet and the two frames, in that order',
	$targets === [ '/assets/theme.css', '/templates/frame-header.tpl', '/templates/frame-footer.tpl' ] );
check( 'the previous version is declared under data, so a backup carries it too',
	in_array( Previous::PATH, (array) ( $feature['data'] ?? [] ), true ) === true );

// A project the way the wizard leaves it: a delivered stylesheet and two
// frames with no stamp - and a shortcode put into the footer by hand
$delivered = [
	'/assets/theme.css'							=> "/* delivered */\nbody { margin: 0; }\n",
	'/templates/frame-header.tpl'		=> "<header>[template /templates/html-header-nav]</header>\n",
	'/templates/frame-footer.tpl'		=> "<footer>[template /templates/html-footer-nav]\n[consent-settings]</footer>\n",
];

foreach( $delivered as $target => $bytes )
	file_put_contents( $pathOf( $target ), $bytes );

@unlink( $slotPath );
$setupBefore = \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] );

[ $status, $listing ] = callDesignAction( $appData, 'apiPlan' );
$plan = $planOf();

check( 'plan answers one entry per file Design writes, in the order it writes them', $status === 200 && array_keys( $plan ) === $targets );
check( '...all three delivered, so all three not ours, existing, and different from what applying writes',
	count( array_filter( $plan, static fn( array $file ): bool => $file['state'] === 'foreign' && $file['exists'] === true && $file['changes'] === true ) ) === 3 );
check( '...and the footer is told apart by the shortcode somebody put in it that the variant replacing it does not have',
	$plan['/templates/frame-footer.tpl']['lost'] === [ '[consent-settings]' ]
	&& $plan['/templates/frame-header.tpl']['lost'] === [] && $plan['/assets/theme.css']['lost'] === [] );
$shortcodes = new ReflectionMethod( \Nino\Modules\Design::class, '_shortcodes' );
check( 'a shortcode is the whole token, a [[fill]] in one of its arguments included, and a bare [[fill]] is none',
	$shortcodes->invoke( null, '<p>[[/project/company/general/name]] [image /x alt="[[/project/company/general/name]]"] [consent-settings]</p>' ) === [ '[image /x alt="[[/project/company/general/name]]"]', '[consent-settings]' ] );
check( 'planning writes nothing: the files are the delivered ones and there is no previous version',
	$onDisk() === $delivered && is_file( $slotPath ) === false );

check( 'applying over them without force is refused, and still keeps nothing',
	callDesignAction( $appData, 'apiApply' )[0] === 409 && is_file( $slotPath ) === false && $onDisk() === $delivered );
check( 'applying with force goes through', callDesignAction( $appData, 'apiApply', [ 'force' => true ] )[0] === 200 );

$slot 		= Previous::read( $appData );
$applied 	= $onDisk();
$setupApplied = Setup::read( $appData, $library );

check( 'the previous version holds the old bytes of all three files', $slot !== null && $slot['files'] === $delivered );
check( '...and when it was kept - with no setup, because the delivered files were written under none of Design\'s: the record in data/design.php names another stylesheet', $slot !== null && $slot['setup'] === null
	&& ( $setupBefore['compiled']['sha'] ?? '' ) !== hash( 'sha256', $delivered['/assets/theme.css'] )
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T/', $slot['at'] ) === 1 );
check( '...while the files on disk are now Design\'s own',
	count( array_filter( $targets, static fn( string $target ): bool => Compiler::ownership( (string) $applied[$target], $target !== Compiler::TARGET ) === 'ours' ) ) === 3 );
check( 'plan afterwards finds every file ours and nothing to change',
	array_column( $planOf(), 'state' ) === [ 'ours', 'ours', 'ours' ] && array_filter( array_column( $planOf(), 'changes' ) ) === [] );

// Applying the same thing again must not replace the one version there is
// with a copy of the present. The date is fixed first, so "untouched" is a
// statement about the whole slot and not about a clock
$fixed = $slot;
$fixed['at'] = '2026-01-01T00:00:00+00:00';
Previous::write( $appData, $fixed );

check( 'applying again with nothing changed leaves the previous version exactly as it was',
	callDesignAction( $appData, 'apiApply' )[0] === 200 && Previous::read( $appData ) === $fixed
	&& $onDisk() === $applied );

// A restore is a swap
[ $status, $restored ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring puts the three files back byte for byte', $status === 200 && $onDisk() === $delivered );
check( '...and the slot now holds what was applied, so the swap can be undone',
	( Previous::read( $appData )['files'] ?? [] ) === $applied );
$afterApply = $setupApplied;
unset( $afterApply['compiled'] );
check( '...and with no setup in the slot only the record of what was compiled goes: the choices stay, and nothing claims the files answer to them',
	Setup::read( $appData, $library ) === $afterApply + [ 'compiled' => [] ] );
check( '...and the answer says what the files are now: not Design\'s, so not current',
	array_column( (array) ( $restored['files'] ?? [] ), 'state' ) === [ 'foreign', 'foreign', 'foreign' ] && ( $restored['current'] ?? null ) === false
	&& is_array( $restored['previous'] ?? null ) === true && ( $restored['previous']['files'] ?? [] ) === $targets );

// A request already failed by Csrf, which runs before an action, writes nothing
$_POST['data'] = '[]';
$failed = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
$slotNow = Previous::read( $appData );
Admin::apiRestore( $appData, $failed );
$_POST = [];

check( 'a restore the Csrf check already failed writes nothing',
	$failed['/nino/http/response']['statusCode'] === 403 && $onDisk() === $delivered && Previous::read( $appData ) === $slotNow );

check( 'restoring again returns to what was applied',
	callDesignAction( $appData, 'apiRestore' )[0] === 200 && $onDisk() === $applied
	&& ( Previous::read( $appData )['files'] ?? [] ) === $delivered );

// What came back is an older Design output with its own setup, which is a
// file that answers to the setup beside it. Without a setup in the slot
// there is nothing saying what the files were compiled from - and the panel
// does not say they are current
[ , $listed ] = callDesignAction( $appData, 'apiList' );
check( 'an older Design output that comes back with its setup is current, because the two belong together', ( $listed['current'] ?? null ) === true );

$slotNoSetup = Previous::read( $appData );
$slotNoSetup['setup'] = null;
Previous::write( $appData, $slotNoSetup );
callDesignAction( $appData, 'apiRestore' );
[ , $listed ] = callDesignAction( $appData, 'apiList' );

check( 'one that comes back with no setup to match it is not current - there is no record of what it was compiled from',
	( $listed['current'] ?? null ) === false && ( $listed['compiled'] ?? 'x' ) === '' );

// ...a restore the disk refuses is not half done
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );
$before 	= $onDisk();
$slotHeld = Previous::read( $appData );

unlink( $pathOf( '/templates/frame-footer.tpl' ) );
mkdir( $pathOf( '/templates/frame-footer.tpl' ) );
ninoWarnings();
[ $status, $failedRestore ] = callDesignAction( $appData, 'apiRestore' );
ninoWarnings();
rmdir( $pathOf( '/templates/frame-footer.tpl' ) );

check( 'a restore whose last write fails answers 500 and says so', $status === 500 && str_contains( (string) ( $failedRestore['error'] ?? '' ), 'frame-footer.tpl' ) === true );
check( '...puts the files it had replaced back, and leaves the previous version where it was',
	$slotHeld['files']['/assets/theme.css'] !== $before['/assets/theme.css'] && $slotHeld['files']['/templates/frame-header.tpl'] !== $before['/templates/frame-header.tpl']
	&& file_get_contents( $pathOf( '/assets/theme.css' ) ) === $before['/assets/theme.css']
	&& file_get_contents( $pathOf( '/templates/frame-header.tpl' ) ) === $before['/templates/frame-header.tpl']
	&& Previous::read( $appData ) === $slotHeld );

file_put_contents( $pathOf( '/templates/frame-footer.tpl' ), (string) $before['/templates/frame-footer.tpl'] );

unlink( $slotPath );
check( 'with no previous version a restore is a 404 and writes nothing',
	callDesignAction( $appData, 'apiRestore' )[0] === 404 && $onDisk() === $before );

// A snapshot that cannot be written stops the apply before it writes a file
callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'knobs' => [], 'size' => 's' ] );
mkdir( $slotPath );
ninoWarnings();
[ $status, $noSnapshot ] = callDesignAction( $appData, 'apiApply' );
ninoWarnings();
rmdir( $slotPath );

check( 'an apply that cannot keep the previous version is refused with that reason',
	$status === 500 && str_contains( (string) ( $noSnapshot['error'] ?? '' ), 'could not keep the previous version in data/design-previous.php - nothing was overwritten' ) === true );
check( '...and not one of the three files changed', $onDisk() === $before );

// A stylesheet that is Design's beside a frame somebody edited used to be a
// dead end: the refusal had nothing on screen to answer yes to
callDesignAction( $appData, 'apiApply' );
$footerPath = $pathOf( '/templates/frame-footer.tpl' );
file_put_contents( $footerPath, (string) file_get_contents( $footerPath ). "\n<p>[consent-settings]</p>\n" );
$plan = $planOf();

check( 'plan tells a stylesheet that is ours from a frame somebody edited', $plan['/assets/theme.css']['state'] === 'ours'
	&& $plan['/templates/frame-footer.tpl']['state'] === 'edited' && $plan['/templates/frame-header.tpl']['state'] === 'ours' );
check( '...applying over it needs force, and force is what gets it through',
	callDesignAction( $appData, 'apiApply' )[0] === 409 && callDesignAction( $appData, 'apiApply', [ 'force' => true ] )[0] === 200
	&& Compiler::ownership( (string) file_get_contents( $footerPath ), true ) === 'ours' );
check( '...and the edited footer is in the previous version',
	str_contains( (string) ( Previous::read( $appData )['files']['/templates/frame-footer.tpl'] ?? '' ), '<p>[consent-settings]</p>' ) === true );

/*	The panel's own sequence, which is not apply on its own: _save(true) posts
	the draft, asks for the plan and only then applies. By the time the apply
	takes the snapshot, data/design.php already holds the NEW choices - and the
	version before is the choices the files were written under, not those	*/
$parts = static fn( string $header, string $footer ): array => [ 'header' => $header, 'footer' => $footer ];
$sans = static function( array $setup ): array {
	unset( $setup['compiled'] );
	return $setup;
};

callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v1', 'v1' ), 'knobs' => [], 'size' => 'm', 'colours' => [] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );

$setupA = $sans( Setup::read( $appData, $library ) );
$filesA = $onDisk();

callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v3', 'v2' ), 'knobs' => [], 'size' => 'l', 'colours' => [ 'contrast' => 3 ] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply' );

$setupB = $sans( Setup::read( $appData, $library ) );
$filesB = $onDisk();

check( 'the two versions of the sequence differ in files, choices, size and colours', $filesA !== $filesB && $setupA !== $setupB
	&& $setupA['size'] === 'm' && $setupB['size'] === 'l' && $setupB['parts']['header'] === 'v3' && $setupB['colours']['contrast'] === 3 );
check( 'the slot holds the choices the files were written under, not the draft the panel saved a moment before the apply',
	( Previous::read( $appData )['files'] ?? [] ) === $filesA
	&& $sans( (array) ( Previous::read( $appData )['setup'] ?? [] ) ) === $setupA );

[ $status, $back ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring after save, plan and apply brings back the files AND the setup: the choices, the size and the colours of the older version',
	$status === 200 && $onDisk() === $filesA && $sans( Setup::read( $appData, $library ) ) === $setupA );
check( '...and the panel reads that version as applied, with nothing "saved, not applied"', ( $back['current'] ?? null ) === true
	&& ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );

[ $status, $forth ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring again gives the newer version back, files and setup, and current',
	$status === 200 && $onDisk() === $filesB && $sans( Setup::read( $appData, $library ) ) === $setupB
	&& ( $forth['current'] ?? null ) === true );

// A draft saved over what was applied is not what the files answer to, and
// the next apply must not keep it as the version before
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v5', 'v3' ), 'knobs' => [], 'size' => 's', 'colours' => [] ] );
callDesignAction( $appData, 'apiApply' );

check( 'a third apply keeps what the second one wrote, with the choices that wrote it',
	( Previous::read( $appData )['files'] ?? [] ) === $filesB
	&& $sans( (array) ( Previous::read( $appData )['setup'] ?? [] ) ) === $setupB );

// A project that applied with the release before this one has a record
// without 'setup'. The draft saved over it cannot be told from the choices, so
// the slot keeps data/design.php whole with its record, and a restore reads
// "saved, not applied" - not "never applied", and not foreign
$wrote = Setup::read( $appData, $library );
unset( $wrote['compiled']['setup'] );
\Nino\Filesystem::putFileContent( $appData, Setup::PATH, $wrote );
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v3', 'v2' ), 'knobs' => [], 'size' => 'l', 'colours' => [] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply' );
[ $status, $legacyBack ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring over a record written before it carried its setup reads saved, not applied: the files are ours, the record is there, the draft is not current',
	$status === 200 && ( $legacyBack['exists'] ?? null ) === true && ( $legacyBack['ours'] ?? null ) === true
	&& ( $legacyBack['current'] ?? null ) === false && ( $legacyBack['compiled'] ?? '' ) !== ''
	&& array_column( (array) ( $legacyBack['files'] ?? [] ), 'state' ) === [ 'ours', 'ours', 'ours' ] );

// A project compiled before the tables moved has a fingerprint without the
// revision. With a colour knob off 2 that is "saved, not applied" once; with
// all three at 2 the bytes did not change and neither does the answer
$legacyOff = Setup::read( $appData, $library );
$legacyOff['colours']['contrast'] = 3;
Setup::write( $appData, $legacyOff );
callDesignAction( $appData, 'apiApply' );
$legacyOff = Setup::read( $appData, $library );
check( 'a project applied with Contrast at Strong reads current', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );
$legacyOff['compiled']['input'] = $beforeRevision( $legacyOff, $library );
Setup::write( $appData, $legacyOff );
check( '...and once it was applied before the revision existed it reads saved, not applied', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === false );

$legacyDefault = Setup::read( $appData, $library );
$legacyDefault['colours']['contrast'] = 2;
Setup::write( $appData, $legacyDefault );
callDesignAction( $appData, 'apiApply' );
$legacyDefault = Setup::read( $appData, $library );
$legacyDefault['compiled']['input'] = $beforeRevision( $legacyDefault, $library );
Setup::write( $appData, $legacyDefault );
check( 'with every colour knob at 2 the same record still reads current', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );

/*	A write the disk refuses half way. The stylesheet goes out first and the
	frames after it, so a frame that failed used to leave the new stylesheet over
	the old markup - and the version kept for a change that then did not happen
	stayed behind as the version before, where there had been none. A directory
	where a file belongs is how a test makes the disk say no. What the answer has
	to leave is the site as the call found it	*/
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v1', 'v1' ), 'knobs' => [], 'size' => 'm', 'colours' => [] ] );
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v2', 'v2' ), 'knobs' => [], 'size' => 'l', 'colours' => [] ] );
callDesignAction( $appData, 'apiApply' );

$old 			= $onDisk();
$oldSlot	= Previous::read( $appData );

// The draft that changes all three files: what the apply writes, and so what it can fail at
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v3', 'v3' ), 'knobs' => [], 'size' => 's', 'colours' => [] ] );
$draft = (string) file_get_contents( $pathOf( Setup::PATH ) );

check( 'the arrangement: three files of Design\'s own, a version before them, and a draft that would change every one',
	count( array_filter( $old, static fn( ?string $bytes ): bool => $bytes !== null ) ) === 3 && $oldSlot !== null
	&& count( array_filter( array_column( $planOf(), 'changes' ) ) ) === 3 );

/** The three files as that left them and the slot as it was - or with no slot, and a frame missing where one is named */
$reset = static function( bool $withSlot, array $missing = [] ) use ( $old, $oldSlot, $pathOf, $slotPath, &$appData ): void {

	foreach( $old as $target => $bytes ) {

		if( in_array( $target, $missing, true ) === true )
			@unlink( $pathOf( $target ) );
		else
			file_put_contents( $pathOf( $target ), (string) $bytes );
	}

	if( $withSlot === true )
		Previous::write( $appData, $oldSlot );
	else
		@unlink( $slotPath );
};

/** One apply with a directory where $dir belongs, and what it left - read before the directory goes again */
$halfway = static function( string $dir, bool $withSlot, array $missing = [] ) use ( $reset, $onDisk, $pathOf, $slotPath, &$appData ): array {

	$reset( $withSlot, $missing );

	if( is_file( $pathOf( $dir ) ) === true )
		unlink( $pathOf( $dir ) );

	mkdir( $pathOf( $dir ) );
	ninoWarnings();
	[ $status, $body ] = callDesignAction( $appData, 'apiApply' );
	ninoWarnings();

	$left = [
		'status' 		=> $status,
		'error' 		=> (string) ( $body['error'] ?? '' ),
		'files' 		=> $onDisk(),
		'directory'	=> is_dir( $pathOf( $dir ) ),
		'slotFile' 	=> is_file( $slotPath ),
		'slot' 			=> Previous::read( $appData ),
		'setup' 		=> (string) file_get_contents( $pathOf( Setup::PATH ) ),
	];

	rmdir( $pathOf( $dir ) );

	return $left;
};

// The files as they are with the directory in the place of one of them
$with = static fn( string ...$targets ): array => array_merge( $old, array_fill_keys( $targets, null ) );

$footerLast = $halfway( '/templates/frame-footer.tpl', true );
check( 'an apply whose last write fails - a directory where the footer belongs, with the stylesheet and the header already out - answers 500 and names the file',
	$footerLast['status'] === 500 && str_contains( $footerLast['error'], 'could not write /templates/frame-footer.tpl' ) === true && $footerLast['directory'] === true );
check( '...puts the stylesheet and the header back byte for byte, so the new stylesheet is not left over the old markup',
	$footerLast['files'] === $with( '/templates/frame-footer.tpl' ) );
check( '...leaves the previous version exactly as it was, and no record of a compile that did not happen',
	$footerLast['slot'] === $oldSlot && $footerLast['setup'] === $draft );

$headerFirst = $halfway( '/templates/frame-header.tpl', true );
check( 'the header refused, with only the stylesheet out: it goes back too, the footer was never touched, and the slot stays',
	$headerFirst['status'] === 500 && str_contains( $headerFirst['error'], 'could not write /templates/frame-header.tpl' ) === true
	&& $headerFirst['files'] === $with( '/templates/frame-header.tpl' ) && $headerFirst['slot'] === $oldSlot && $headerFirst['setup'] === $draft );

$noSlotFrame = $halfway( '/templates/frame-footer.tpl', false );
check( 'with no previous version a failure half way leaves none behind - the one kept for the change that did not happen is taken away again',
	$noSlotFrame['status'] === 500 && $noSlotFrame['slotFile'] === false && $noSlotFrame['files'] === $with( '/templates/frame-footer.tpl' )
	&& $noSlotFrame['setup'] === $draft );

$noSlotCss = $halfway( '/assets/theme.css', false );
check( 'a stylesheet that cannot be written, with no previous version, leaves no slot file behind either',
	$noSlotCss['status'] === 500 && str_contains( $noSlotCss['error'], 'could not write /assets/theme.css' ) === true
	&& $noSlotCss['slotFile'] === false && $noSlotCss['files'] === $with( '/assets/theme.css' ) );

$slotCss = $halfway( '/assets/theme.css', true );
check( '...and where there is one it stays exactly as it was',
	$slotCss['status'] === 500 && $slotCss['slot'] === $oldSlot && $slotCss['files'] === $with( '/assets/theme.css' ) );

$madeHeader = $halfway( '/templates/frame-footer.tpl', false, [ '/templates/frame-header.tpl' ] );
check( 'a frame the apply had only just made is taken away again: the project had no header, and has none',
	$madeHeader['status'] === 500 && $madeHeader['files'] === $with( '/templates/frame-header.tpl', '/templates/frame-footer.tpl' )
	&& $madeHeader['slotFile'] === false );

$reset( true );
check( 'the same apply goes through once the disk allows it, and keeps the version it found',
	callDesignAction( $appData, 'apiApply' )[0] === 200 && $onDisk() !== $old
	&& ( Previous::read( $appData )['files'] ?? [] ) === $old );

// What the preview tests further down take for the stored setup
callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'knobs' => [], 'size' => 'm' ] );

echo "\nThe knobs: each tab's own vocabulary, and what the shipped templates answer to\n";

check( 'a set publishes a knob by declaring its triple: v1 of each tab answers to every knob of the tab, in the order of the tab',
	Setup::knobs( $library, 'content', 'v1' ) === Setup::KNOBS['content']
	&& Setup::knobs( $library, 'buttons', 'v1' ) === Setup::KNOBS['buttons']
	&& Setup::knobs( $library, 'components', 'v1' ) === Setup::KNOBS['components'] );
check( 'a frame answers to none - a knob is about a set\'s own triples - and neither does a set that is not there',
	Setup::knobs( $library, 'header', 'v1' ) === [] && Setup::knobs( $library, 'footer', 'v3' ) === []
	&& Setup::knobs( $library, 'content', 'nope' ) === [] );
check( 'the stripes of a table have a ground and an ink, the labels of a form ten triples in all, the shadow of a card its lift - and the aspects stand beside the triple of their knob',
	Setup::triples( $library, 'components', 'v1' )['zebra'] === [ 'zebra', 'zebra-ink' ]
	&& Setup::triples( $library, 'components', 'v1' )['labels'] === [ 'labels', 'labels-form', 'labels-cols', 'labels-align', 'labels-wide', 'labels-start', 'labels-block', 'labels-pos', 'labels-lift', 'labels-cut' ]
	&& Setup::triples( $library, 'content', 'v3' )['shadow'] === [ 'shadow', 'shadow-lift' ]
	&& array_filter( Setup::triples( $library, 'buttons', 'v1' ), static fn( array $names ): bool => count( $names ) > 1 ) === [] );
check( 'the templates differ in what they answer to - Poster has no shadow, Square no corners, Airy no zebra, Filled neither border nor labels',
	Setup::knobs( $library, 'content', 'v2' ) === array_values( array_diff( Setup::KNOBS['content'], [ 'shadow' ] ) )
	&& Setup::knobs( $library, 'buttons', 'v3' ) === array_values( array_diff( Setup::KNOBS['buttons'], [ 'corners' ] ) )
	&& Setup::knobs( $library, 'components', 'v3' ) === array_values( array_diff( Setup::KNOBS['components'], [ 'zebra' ] ) )
	&& Setup::knobs( $library, 'components', 'v4' ) === array_values( array_diff( Setup::KNOBS['components'], [ 'border', 'labels' ] ) ) );

/*	Every template at every position of its knobs: the sheet it compiles to
	selects exactly the triples the template declares and not one more, every
	selection points at a step the sheet declares, and the middle position is
	the template's own value for each	*/
$selectProblems = [];
$compiled = 0;

foreach( Setup::PARTS as $part => $kind ) {

	if( $kind !== 'set' )
		continue;

	foreach( Setup::available( $library, $part ) as $set )
		foreach( [ -1, 0, 1 ] as $position ) {

			$sheet = Compiler::compile( Setup::normalize( [ 'parts' => [ $part => $set ], 'knobs' => [ $part => array_fill_keys( Setup::KNOBS[$part], $position ) ] ], $library ), $library );
			$block = designPlain( substr( $sheet, (int) strrpos( $sheet, 'the knob positions' ) ) );
			$plain = designPlain( $sheet );

			$expected = [];
			foreach( Setup::triples( $library, $part, $set ) as $names )
				foreach( $names as $name )
					$expected[ '--'. $part. '-'. $name ] = 'var(--'. $part. '-'. $name. '--'. Setup::word( $position ). ')';

			$selected = array_filter( designDeclarations( $block ), static fn( string $value, string $token ): bool => str_starts_with( $token, '--'. $part. '-' ), ARRAY_FILTER_USE_BOTH );
			ksort( $expected );
			ksort( $selected );
			$compiled++;

			if( $selected !== $expected )
				$selectProblems[] = $part. '/'. $set. ' at '. $position. ': selects ['. implode( ' ', array_keys( $selected ) ). '], declares ['. implode( ' ', array_keys( $expected ) ). ']';

			foreach( $selected as $value )
				if( preg_match( '/^var\((--[a-z0-9-]+)\)$/', $value, $step ) !== 1 || preg_match( '/'. preg_quote( $step[1], '/' ). '\s*:/', $plain ) !== 1 )
					$selectProblems[] = $part. '/'. $set. ' at '. $position. ': '. $value. ' is a step the sheet does not declare';
		}
}

check( 'every template at every position of its knobs ('. $compiled. ' sheets) selects the triples it declares and no others, each a step the sheet declares'
	. ( $selectProblems === [] ? '' : ' - '. implode( ' | ', array_slice( $selectProblems, 0, 3 ) ) ), $selectProblems === [] && $compiled === 45 );
echo "\nThe preview: a selection, before it is one\n";

/*	The framework's own halves are read out of the checkout and bundled, and a
	sandbox has no kernel in it at all - so it gets one, the same way a project
	has one. Modules\Assets is what writes the bundle, Modules\Template what a
	frame's [template] includes resolve through */
$root = realpath( $root ) ?: $root;	// a relative NINO_ROOT would make a dangling link
symlink( $root. '/_nino', ninoSandboxDir( $appData ). '/_nino' );
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template' ];
\Nino\Modules::callModules( $appData, 'init' );

/*	The logo is the kernel's slot, as the base unit's own frames ask for it - never
	a file name of a library template, which a project that uploaded its logo
	through the Images panel does not have. One slot serves every variant: a frame
	that was drawn for a dark ground says so with nino-logo--invert and applies no
	filter of its own	*/
// [image] is the Components module's from Nino 1.6 and the Images module's
// before; whichever this checkout carries registers it
if( class_exists( '\\Nino\\Modules\\Components' ) === true )
	\Nino\Modules\Components::init( $appData );
else
	\Nino\Modules\Images::init( $appData );
$logoNamed		= [];
$logoRender		= [ 'filled' => [], 'empty' => [] ];
$logoUrl			= \Nino\Images::getUrl( $appData, 'logo.webp' );

foreach( [ 'header', 'footer' ] as $part )
	foreach( Setup::available( $library, $part ) as $set ) {

		$source = (string) file_get_contents( Setup::file( $library, $part, $set, 'template' ) );

		if( preg_match( '#logo(?:-invert)?\.png#', $source ) === 1 || str_contains( $source, 'images/logo' ) === true )
			$logoNamed[] = $part. '/'. $set;

		unset( $appData['/nino/html/images']['/logo'] );
		$logoRender['empty'][$part. '/'. $set] = \Nino\Html::renderHtml( $appData, $source );
		$appData['/nino/html/images']['/logo'] = [ 'label' => 'Logo', 'filename' => 'logo.webp', 'width' => 500, 'height' => 100 ];
		$logoRender['filled'][$part. '/'. $set] = \Nino\Html::renderHtml( $appData, $source );
	}

unset( $appData['/nino/html/images']['/logo'] );

check( 'no library template names logo.png or logo-invert.png'. ( $logoNamed === [] ? '' : ' - '. implode( ', ', $logoNamed ) ), $logoNamed === [] && count( $logoRender['empty'] ) >= 17 );
check( 'every frame asks the logo slot', count( array_filter( $logoRender['filled'], static fn( string $html ): bool
	=> str_contains( $html, 'src="'. $logoUrl. '"' ) === true && str_contains( $html, 'width="500" height="100"' ) === true ) ) === 17 );
check( '...and with no logo uploaded none leaves an <img>, a slot tag or a fill of the shortcode behind',
	array_filter( $logoRender['empty'], static fn( string $html ): bool
		=> preg_match( '#<img[^>]*logo#i', $html ) === 1 || str_contains( $html, '[image' ) === true
			|| str_contains( $html, '[[src]]' ) === true || str_contains( $html, '[[width]]' ) === true || str_contains( $html, '[[alt]]' ) === true
			|| str_contains( $html, $logoUrl ) === true || str_contains( $html, '/images/logo' ) === true ) === [] );
check( 'a frame drawn for a dark ground carries nino-logo--invert, one slot for both variants',
	str_contains( $logoRender['filled']['header/v4'], 'nino-logo--invert' ) === true && str_contains( $logoRender['filled']['footer/v2'], 'nino-logo--invert' ) === true
	&& str_contains( $logoRender['filled']['header/v1'], 'nino-logo--invert' ) === false );
check( '...and no stylesheet of the library filters it', array_filter( glob( $library. '/*/*/style.css' ) ?: [], static fn( string $file ): bool
	=> preg_match( '#nino-logo--invert[^{]*\{[^}]*filter#', (string) file_get_contents( $file ) ) === 1 ) === [] );

$specimen = Preview::specimen( $appData );

check( 'the specimen brings no frame of its own - markup() puts the chosen ones around it',
	str_contains( $specimen, 'frame-header' ) === false && str_contains( $specimen, 'frame-footer' ) === false );
/*	One page, and an anchor on it for each tab of the panel: an element with
	the id of the tab, which the frame scrolls to when the tab is opened and
	after every change made in it. The ids are the script's (ANCHORS in
	admin.js, which design-js-smoke.js reads them back from)	*/
preg_match_all( '/\sid="(global|type|content|buttons|components|colours)"/', $specimen, $anchors );

$anchored = array_count_values( $anchors[1] );
ksort( $anchored );

check( 'it is one page with an anchor for each tab: global, type, content, buttons, components and colours, each exactly once',
	$anchored === [ 'buttons' => 1, 'colours' => 1, 'components' => 1, 'content' => 1, 'global' => 1, 'type' => 1 ] );

$oldWords = [];
foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( dirname( __DIR__ ), FilesystemIterator::SKIP_DOTS ) ) as $file ) {

	$path = substr( $file->getPathname(), strlen( dirname( __DIR__ ) ) + 1 );

	// The headers and footers carry the kernel's own body.nino-scroll-atf, the state its scroll script sets: that class is not this feature's to rename
	if( $file->isFile() === false || str_starts_with( $path, 'tests/' ) === true || preg_match( '#^library/(?:header|footer)/#', $path ) === 1 )
		continue;

	if( preg_match( '/\batf\b|nino-atf|above the fold|library\/sets|\bATF\b/i', (string) file_get_contents( $file->getPathname() ) ) === 1 )
		$oldWords[] = $path;
}

check( 'and it, the library, the panel and its texts have no word of the model before this one: no ATF, no above the fold, no library/sets'. ( $oldWords === [] ? '' : ' - in '. implode( ', ', $oldWords ) ), $oldWords === [] );
check( 'its one picture is a data uri, so it needs no route wherever it is shown',
	str_starts_with( Preview::PLACEHOLDER, 'data:image/svg+xml' ) === true
	&& str_contains( $specimen, Preview::PLACEHOLDER ) === true
	&& str_contains( $specimen, 'src="/images/' ) === false );

/*	The specimen is what a set is judged on, so it has to be built the way a page
	this framework produces is built: one .nino-section, one .nino-grid-row inside
	it, and every child of that row a grid cell. Not a rule of taste - the row is
	the only thing carrying the horizontal padding and the max-width (Nino.css,
	"02 Grid"), and it carries no vertical margin, so a second row stacked under
	the first sits flush against it. Every page the wizard installs and every
	section the Builder writes (one row to a section, Reader and Writer hold it)
	is this shape	*/

$document = new DOMDocument();
$previous = libxml_use_internal_errors( true );
$document->loadHTML( '<?xml encoding="utf-8"?><div id="specimen-root">'. $specimen. '</div>', LIBXML_NOWARNING | LIBXML_NOERROR );
libxml_clear_errors();
libxml_use_internal_errors( $previous );
$xpath = new DOMXPath( $document );

/** Elements below $context carrying $class, as an array - and with $top only those that are not inside another one of the class, which is a row of the section and not a row in a cell of it */
$byClass = static function( DOMXPath $xpath, string $class, ?DOMNode $context = null, bool $top = false ): array {
	$has = static fn( string $name ): string => 'contains(concat(" ",normalize-space(@class)," ")," '. $name. ' ")';
	$query = './/*['. $has( $class ). ( $top === true ? ' and not(ancestor::*['. $has( $class ). '])' : '' ). ']';
	$found = [];
	foreach( $xpath->query( $query, $context ) as $node )
		$found[] = $node;
	return $found;
};

$sections = $byClass( $xpath, 'nino-section' );
$rowCounts = [];
$strayCells = [];

foreach( $sections as $section ) {

	$rows = $byClass( $xpath, 'nino-grid-row', $section, true );
	$rowCounts[] = count( $rows );

	// A row inside a cell is a stack of cards or a pair of columns: its cells are grid cells as well
	foreach( $byClass( $xpath, 'nino-grid-row', $section ) as $row )
		foreach( $row->childNodes as $child ) {
			if( $child instanceof DOMElement === false )
				continue;
			if( preg_match( '/(?:^| )nino-grid-(?:s-|m-|l-|xl-)?(?:25|33|50|66|75|100)(?: |$)/', $child->getAttribute( 'class' ) ) !== 1 )
				$strayCells[] = $child->tagName. '.'. $child->getAttribute( 'class' );
		}
}

check( 'every section of the specimen holds exactly one grid row of its own - a second one under it has no margin and would sit flush',
	$sections !== [] && array_unique( $rowCounts ) === [ 1 ] );
check( 'and every child of a row is a grid cell, so nothing sits in the row without a width',
	$strayCells === [] );

/*	The animation of the page is a choice of the Global tab, and a page that
	carries no viewport animation shows none of it: the cell of the hero, the
	cards at the head of the page, the pricing items and the figures carry
	nino-vpa, and repeat, so that each time the frame scrolls to them they play
	again. Never a section, and never an anchor: a transform on one would move
	the very element the frame measures its scroll by	*/
$animated	= $byClass( $xpath, 'nino-vpa' );
$repeating	= array_filter( $animated, static fn( DOMElement $node ): bool => preg_match( '/(?:^| )nino-vpa--repeat(?: |$)/', $node->getAttribute( 'class' ) ) === 1 );
$sectioned	= array_filter( $animated, static fn( DOMElement $node ): bool => $node->hasAttribute( 'id' ) === true || preg_match( '/(?:^| )nino-(?:section|hero)(?: |$)/', $node->getAttribute( 'class' ) ) === 1 );

check( 'it carries viewport animations on the hero\'s cell, cards, pricing items and figures, each playing again when the frame scrolls to it - and on no section and no anchor ('. count( $animated ). ' elements)',
	count( $animated ) === 10 && count( $repeating ) === count( $animated ) && $sectioned === [] );

/*	The rhythm between the blocks inside a row is a spacing utility on the cell:
	a heading cell is 'nino-grid-100 nino-mb-3', an action cell
	'nino-grid-100 nino-mt-3', a card 'nino-article ... nino-mb-3'. Without it
	the cells of a wrapping row meet at the .5rem an element brings of its own	*/
$multi = 0;
$spaced = 0;

foreach( $sections as $section )
	foreach( $byClass( $xpath, 'nino-grid-row', $section ) as $row ) {

		$cells = [];
		foreach( $row->childNodes as $child )
			if( $child instanceof DOMElement )
				$cells[] = $child;

		// A stack spaces its cells by a gap of its own (nino-stack-gap-n), which is the point of having one
		if( count( $cells ) < 2 || preg_match( '/(?:^| )nino-stack(?: |$)/', $row->getAttribute( 'class' ) ) === 1 )
			continue;

		$multi++;

		// Every cell but the last one, which is the section's own bottom padding
		array_pop( $cells );
		$missing = array_filter( $cells, static fn( DOMElement $cell ): bool
			=> preg_match( '/(?:^| )nino-(?:m|mt|mb|my|p|pt|pb)-[0-6](?: |$)/', $cell->getAttribute( 'class' ) ) !== 1 );

		if( $missing === [] )
			$spaced++;
	}

check( 'a row with more than one cell spaces them - every cell but the last carries a spacing utility, or the row is a stack with a gap of its own',
	$multi > 0 && $spaced === $multi );

$notes 		= [];
$chosen 	= Setup::normalize( [ 'parts' => [ 'header' => 'v3', 'footer' => 'v5' ] ], $library );
$markup 	= Preview::markup( $appData, $library, $chosen, $notes );

check( 'markup() reads the chosen frames out of the library rather than off disk', $notes === []
	&& str_starts_with( trim( $markup ), trim( (string) file_get_contents( Setup::file( $library, 'header', 'v3', 'template' ) ) ) ) === true
	&& str_ends_with( trim( $markup ), trim( (string) file_get_contents( Setup::file( $library, 'footer', 'v5', 'template' ) ) ) ) === true );

$notes = [];
check( 'a frame with no template is named rather than quietly left out',
	Preview::frame( $library, [ 'parts' => [ 'header' => 'nope' ] ], 'header', $notes ) === ''
	&& count( $notes ) === 1 && str_contains( $notes[0], '"header"' ) === true );

$notes = [];
$shown = Preview::css( $chosen, $library, '/somewhere/public', $notes );
check( 'the sheet a preview is shown under resolves the public prefix, or the webfaces never load',
	str_contains( $shown, "url('/somewhere/public/fonts/" ) === true && str_contains( $shown, '[[/nino/public]]' ) === false );

$document = Preview::document( $appData, 'de_DE', '<style>a{}</style>', '<main>b</main>', '<script></script>' );
check( 'the document says which language it is in and says no to crawlers',
	str_starts_with( $document, '<!doctype html>' ) === true
	&& str_contains( $document, '<html lang="de">' ) === true
	&& str_contains( $document, 'name="robots" content="noindex, nofollow"' ) === true );

// What the panel answers with. The setup on disk is size 'm' here (the fallback
// save above wrote it), and the post below asks for 's' - so the preview showing
// 's' is the whole point: it is the selection on screen, not the one stored
$stored 	= Setup::read( $appData, $library );
$sheet 		= (string) \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' );

[ $status, $preview ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [ 'header' => 'v3' ], 'size' => 's', 'full' => true,
] );

check( 'the preview answers with a whole document and the id of the sheet inside it', $status === 200
	&& str_starts_with( (string) ( $preview['document'] ?? '' ), '<!doctype html>' ) === true
	&& ( $preview['style'] ?? '' ) === Admin::PREVIEW_STYLE
	&& str_contains( (string) $preview['document'], 'id="'. Admin::PREVIEW_STYLE. '"' ) === true );
check( 'it shows what was posted and not what is stored - looking is what you do while deciding',
	str_contains( (string) ( $preview['css'] ?? '' ), '87.5%' ) === true
	&& ( $stored['size'] ?? '' ) === 'm'
	&& str_contains( (string) $preview['document'], 'nino-grid-row nino-grid-row--wide' ) === true );
// The '?v=' is the bundle's own hash, which \Nino\Modules\Assets puts on the
// url so a regenerated bundle is not served from a browser's cache
check( 'the framework is linked, not inlined - the workbench sends a csp that refuses an inline script',
	preg_match( '#design-preview\.js(\?v=[a-f0-9]+)?"></script>#', (string) $preview['document'] ) === 1
	&& preg_match( '#design-preview\.css(\?v=[a-f0-9]+)?"#', (string) $preview['document'] ) === 1
	&& str_contains( (string) $preview['document'], '<script>' ) === false );
check( '...and the bundle it points at really carries the framework',
	str_contains( (string) \Nino\Filesystem::getFileContent( $appData, Admin::FRAMEWORK_CSS, '' ), '.nino-section' ) === true
	&& str_contains( (string) \Nino\Filesystem::getFileContent( $appData, Admin::FRAMEWORK_JS, '' ), 'Nino.ui' ) === true );

[ $status, $partial ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 'l', 'full' => false,
] );
check( 'a change that is only a stylesheet sends only that - the frame on screen keeps its page', $status === 200
	&& ( $partial['document'] ?? null ) === '' && str_contains( (string) ( $partial['css'] ?? '' ), '131.25%' ) === true );

/*	Both of these move with every colour knob and are drawn beside the controls
	that moved them, so they ride the preview rather than a request of their own	*/
[ $status, $wheeled ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 'm', 'full' => false,
	'colours' => [ 'primary' => '#8b1d3f', 'harmony' => 4 ],
] );
check( 'a preview also answers the derived second colour and what the brand measures - the swatch follows a knob without a request of its own',
	$status === 200 && ( $wheeled['accent'] ?? '' ) === Colours::accent( [ 'primary' => '#8b1d3f', 'harmony' => 4 ] )
	&& ( $wheeled['accent'] ?? '' ) !== '#8b1d3f'
	&& isset( $wheeled['brand']['light']['ratio'] ) === true );

check( 'previewing writes neither the setup nor the stylesheet',
	Setup::read( $appData, $library ) === $stored
	&& \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' ) === $sheet );

// Every key of the model travels the way it does in a save, and is shown without being written
[ $status, $everything ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [ 'content' => 'v4', 'buttons' => 'v2', 'components' => 'nope' ],
	'knobs' => [ 'content' => [ 'corners' => 1 ], 'buttons' => [ 'space' => -1 ] ],
	'size' => 'm', 'width' => -1,
	'motion' => [ 'effect' => 'blur', 'strength' => 'hard', 'speed' => 'fast' ],
	'fonts' => [ 'text' => [ 'family' => 'system-serif', 'size' => '1.125' ] ],
	'colours' => [ 'mode' => 'dark' ], 'full' => false,
] );
$shownCss = (string) ( $everything['css'] ?? '' );

check( 'a preview carries every key of the model the way a save does: the templates, the knobs, the width, the animation, the fonts and the mode', $status === 200
	&& str_contains( $shownCss, 'content: v4' ) === true && str_contains( $shownCss, 'buttons: v2' ) === true
	&& str_contains( $shownCss, '--content-corners: var(--content-corners--more);' ) === true && str_contains( $shownCss, '--buttons-space: var(--buttons-space--less);' ) === true
	&& str_contains( $shownCss, '--grid-max-width: 96rem;' ) === true && str_contains( $shownCss, '--vpa-f-hidden: blur(24px);' ) === true && str_contains( $shownCss, '--vpa-duration: .25s;' ) === true
	&& str_contains( $shownCss, '--fontfamily-text: ui-serif' ) === true && str_contains( $shownCss, '--font-text-size: 1.125rem;' ) === true
	&& str_contains( $shownCss, 'color-scheme: dark;' ) === true );
check( '...a template the library does not have is named in the answer and the first one is shown, and the stored setup and the stylesheet are still what they were',
	count( (array) ( $everything['notes'] ?? [] ) ) === 1 && str_contains( (string) ( $everything['notes'][0] ?? '' ), '"nope"' ) === true && str_contains( $shownCss, 'components: v1' ) === true
	&& Setup::read( $appData, $library ) === $stored && \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' ) === $sheet );

/*	The palette travels the same three ways the structure does: out in the
	list, back in on a save, and through a preview without being written. The
	panel draws whatever choices() publishes, so a knob added in Colours
	appears on screen without the script or the panel gaining a line - what it
	does need is its words, and those are text keys rather than strings in the
	table. The table used to carry a 'label', a 'note' and a 'hint' that
	nothing ever drew, and they had drifted: 'harmony' read "Harmony" there
	while the screen said "Second colour" */
[ $status, $listed ] = callDesignAction( $appData, 'apiList' );

check( 'the list hands over where the palette stands, and the words to draw it with', $status === 200
	&& is_array( $listed['colours'] ?? null ) === true
	&& ( $listed['colours']['primary'] ?? '' ) !== ''
	&& array_keys( (array) ( $listed['palette'] ?? [] ) ) === [ 'harmony', 'temperature', 'saturation', 'contrast', 'depth', 'mode' ] );
check( '...each knob with its own positions, three for a scale and four for a choice - and the mode, whose positions are the words the setup keeps',
	count( (array) ( $listed['palette']['harmony']['steps'] ?? [] ) ) === 4
	&& count( (array) ( $listed['palette']['contrast']['steps'] ?? [] ) ) === 3
	&& ( $listed['palette']['mode'] ?? null ) === [ 'default' => 'auto', 'steps' => [ 'auto', 'light', 'dark' ] ]
	&& ( $listed['colours']['mode'] ?? '' ) === 'auto' );
/*	Measured once: brand and its ink are the same in both modes, so a dark
	entry would repeat the light one - and if a mode ever moves them, the second
	half of this fails and says the measurement has to be per mode again	*/
$brandIn = static fn( string $mode ): array => array_intersect_key( Colours::palette( (array) ( $listed['colours'] ?? [] ), $mode )['brand'], [ 'bg' => true, 'on' => true ] );
check( '...and whether the colour that was picked is one text survives on, measured once since no mode moves it',
	array_keys( (array) ( $listed['brand'] ?? [] ) ) === [ 'light' ]
	&& isset( $listed['brand']['light']['safe'], $listed['brand']['light']['ratio'], $listed['brand']['light']['target'] ) === true
	&& $brandIn( 'light' ) === $brandIn( 'dark' ) );
check( '...and the second colour as it will be compiled, for the swatch that stands for it',
	preg_match( '/^#[0-9a-f]{6}$/', (string) ( $listed['accent'] ?? '' ) ) === 1 );

/*	So a published knob has to have its words, in every locale this feature
	ships - that is the half the table cannot carry, and the half nothing
	checked. A knob added without them draws as a blank row	*/
$knobWords = [];
foreach( [ 'en_US', 'de_DE' ] as $knobLocale ) {

	$knobText = (array) @include __DIR__. '/../text/'. $knobLocale. '.php';

	foreach( (array) ( $listed['palette'] ?? [] ) as $knobKey => $knobMeta ) {

		// A scale's positions are numbered and the mode's are its words
		$wanted = [ 'label', 'note' ];
		foreach( (array) ( $knobMeta['steps'] ?? [] ) as $position => $step )
			$wanted[] = $knobKey === 'mode' ? (string) $step : (string) ( $position + 1 );

		foreach( $wanted as $suffix )
			if( ( $knobText[ '[[/_admin/design/colour/'. $knobKey. '/'. $suffix. ']]' ] ?? '' ) === '' )
				$knobWords[] = $knobLocale. ' '. $knobKey. '/'. $suffix;
	}
}
check( 'every knob the panel is handed has its name, its note and a word per position, in both locales'. ( $knobWords === [] ? '' : ' - missing: '. implode( ', ', $knobWords ) ), $knobWords === [] );
check( '...and the table itself carries no second copy of those words', ( $listed['palette']['harmony']['label'] ?? null ) === null
	&& ( $listed['palette']['harmony']['note'] ?? null ) === null
	&& ( $listed['palette']['harmony']['hint'] ?? null ) === null );

[ $status, ] = callDesignAction( $appData, 'apiSave', [
	'parts' => [], 'knobs' => [], 'size' => 'm',
	'colours' => [ 'primary' => '#8b1d3f', 'secondary' => '#0f766e', 'temperature' => 1, 'mode' => 'light' ],
] );
$saved = Setup::read( $appData, $library );

check( 'a save keeps the palette, normalized', $status === 200
	&& ( $saved['colours']['primary'] ?? '' ) === '#8b1d3f'
	&& ( $saved['colours']['secondary'] ?? '' ) === '#0f766e'
	&& ( $saved['colours']['temperature'] ?? 0 ) === 1
	&& ( $saved['colours']['contrast'] ?? 0 ) === 2
	&& ( $saved['colours']['mode'] ?? '' ) === 'light' );
check( '...and the compiled sheet carries that colour, exactly as it was typed',
	str_contains( Compiler::compile( $saved, $library ), '--nino-brand: #8b1d3f;' ) === true );

[ $status, $coloured ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 'm', 'colours' => [ 'primary' => '#2e7d32' ], 'full' => false,
] );
check( 'a preview shows the colour that was posted rather than the one on disk', $status === 200
	&& str_contains( (string) ( $coloured['css'] ?? '' ), '--nino-brand: #2e7d32;' ) === true
	&& ( Setup::read( $appData, $library )['colours']['primary'] ?? '' ) === '#8b1d3f' );

\Nino\Auth::logoutUser( $appData );
check( 'every action of the panel refuses a request with no session',
	callDesignAction( $appData, 'apiList' )[0] === 401
	&& callDesignAction( $appData, 'apiSave', [] )[0] === 401
	&& callDesignAction( $appData, 'apiApply' )[0] === 401
	&& callDesignAction( $appData, 'apiPlan' )[0] === 401
	&& callDesignAction( $appData, 'apiRestore' )[0] === 401
	&& callDesignAction( $appData, 'apiPreview', [] )[0] === 401 );

\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/elements/manage' ] );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );
check( '...and an account without /_admin/design/manage with a 403',
	callDesignAction( $appData, 'apiList' )[0] === 403 && callDesignAction( $appData, 'apiApply' )[0] === 403
	&& callDesignAction( $appData, 'apiPlan' )[0] === 403 && callDesignAction( $appData, 'apiRestore' )[0] === 403
	&& callDesignAction( $appData, 'apiPreview', [] )[0] === 403
	&& callDesignAction( $appData, 'apiSave', [] )[0] === 403 );

check( 'the panel names every action it answers', array_keys( Admin::actions() ) === [ 'design/list', 'design/save', 'design/apply', 'design/plan', 'design/restore', 'design/preview' ] );
check( 'and previewing is not written to the activity log - it happens on every select and changes nothing',
	Admin::log( 'design/preview', [] ) === '' );
check( 'and ships the pane and the two assets it is drawn with',
	Admin::panes() === [ 'design-form' ] && count( Admin::assets() ) === 2 );
check( 'taking over files Design had not written is written to the activity log as that, and a restore as one',
	str_contains( Admin::log( 'design/apply', [ 'force' => true ] ), 'took over files Design had not written' ) === true
	&& Admin::log( 'design/restore', [] ) === 'Restore previous Design version' );
check( 'planning only reads, so it is not written to the log either', Admin::log( 'design/plan', [] ) === '' );

/*	Both files hold the same keys. Nothing but this checks it: a key one
	language lacks draws as the fill itself in that language's panel, and the
	script reads a dozen of them by name	*/
$textEn = array_keys( (array) include __DIR__. '/../text/en_US.php' );
$textDe = array_keys( (array) include __DIR__. '/../text/de_DE.php' );
check( 'the panel\'s two text files carry the same keys, in the same order', $textEn === $textDe );

preg_match_all( "#'/_admin/design/([a-z0-9/-]*[a-z0-9])'#", (string) file_get_contents( __DIR__. '/../assets/admin.js' ), $scriptKeys );
$unwritten = array_filter( array_unique( $scriptKeys[1] ), static fn( string $key ): bool => in_array( '[[/_admin/design/'. $key. ']]', $textEn, true ) === false );
check( '...and every text the script names literally is one of them'. ( $unwritten === [] ? '' : ' - missing: '. implode( ', ', $unwritten ) ), $unwritten === [] );

/*	The words the model needs, one for each thing the script draws by name: a
	tab and its group, a template and a size and a width, an effect with its
	strength and speed, a font role with its columns and its families, and
	three words and a note for every knob of every tab. The model is the list,
	so a tab, a knob or a family added to it without its words fails here and
	not as a blank row on a screen - and a word that no longer belongs to
	anything fails as well	*/
$wanted = [];
foreach( [ 'global', 'fonts', 'content', 'buttons', 'components', 'colours' ] as $tab ) {
	$wanted[] = 'tab/'. $tab;
	$wanted[] = 'group/'. $tab;
}
foreach( array_keys( Setup::PARTS ) as $part )
	$wanted[] = 'part/'. $part;
foreach( array_keys( Setup::SIZES ) as $size )
	$wanted[] = 'size/'. $size;
foreach( [ 'narrow', 'normal', 'wide' ] as $word )
	$wanted[] = 'pagewidth/'. $word;
foreach( [ 'phone', 'tablet', 'desktop' ] as $word )
	$wanted[] = 'width/'. $word;
foreach( [ 'current', 'drifted', 'missing', 'foreign' ] as $word ) {
	$wanted[] = 'state/'. $word;
	$wanted[] = 'state/short/'. $word;
}
array_push( $wanted, 'motion/default', 'motion/effect/label', 'motion/strength/label', 'motion/speed/label' );
foreach( array_keys( Setup::EFFECTS ) as $effect )
	$wanted[] = 'motion/effect/'. $effect;
foreach( Setup::STRENGTHS as $strength )
	$wanted[] = 'motion/strength/'. $strength;
foreach( array_keys( Setup::SPEEDS ) as $speed )
	$wanted[] = 'motion/speed/'. $speed;
foreach( Fonts::ROLES as $role )
	$wanted[] = 'font/role/'. $role;
foreach( [ 'family', 'weight', 'uppercase', 'size' ] as $head )
	$wanted[] = 'font/head/'. $head;
foreach( Fonts::families() as $slug => $family )
	if( $family['files'] === [] )
		$wanted[] = 'family/'. $slug;
array_push( $wanted, 'family/default', 'family/default-code', 'fontsize/default', 'fontsize/decimal', 'fontsize/unit' );
foreach( Fonts::WEIGHTS as $weight )
	$wanted[] = 'weight/'. $weight;
foreach( Setup::KNOBS as $tab => $names )
	foreach( $names as $knob )
		foreach( [ 'label', 'note', 'less', 'default', 'more' ] as $word )
			$wanted[] = 'knob/'. $tab. '/'. $knob. '/'. $word;
foreach( Colours::choices() as $knob => $meta ) {
	array_push( $wanted, 'colour/'. $knob. '/label', 'colour/'. $knob. '/note' );
	foreach( $meta['steps'] as $position => $step )
		$wanted[] = 'colour/'. $knob. '/'. ( $knob === 'mode' ? $step : $position + 1 );
}

$missingWords = [];
foreach( [ 'en_US' => $textEn, 'de_DE' => $textDe ] as $wordLocale => $wordKeys )
	foreach( $wanted as $word )
		if( in_array( '[[/_admin/design/'. $word. ']]', $wordKeys, true ) === false )
			$missingWords[] = $wordLocale. ' '. $word;

check( 'the texts have a word for everything the model draws by name ('. count( $wanted ). ' of them, in two languages)'
	. ( $missingWords === [] ? '' : ' - missing: '. implode( ', ', array_slice( $missingWords, 0, 6 ) ) ), $missingWords === [] );

// ...and nothing else: a key is either one of those, or a literal the script names, or the one page title the server renders
$accounted = array_merge( $wanted, array_values( array_unique( $scriptKeys[1] ) ), [ 'preview/page' ] );
$leftover = array_filter( $textEn, static fn( string $key ): bool => str_starts_with( $key, '[[/_admin/design/' ) === true
	&& in_array( substr( $key, strlen( '[[/_admin/design/' ), -2 ), $accounted, true ) === false );

check( '...and no word is left over that nothing draws: every key is one of those, a literal the script names, or the page title of the preview'
	. ( $leftover === [] ? '' : ' - left over: '. implode( ', ', array_slice( $leftover, 0, 6 ) ) ), $leftover === [] );

// --- The panel's script, where node is on the path ------------------------------
//
// design-js-smoke.js beside this file draws the screen over a dom stand-in and
// reads the summary back off it; this suite runs it too where node is on the
// path, the way gallery-smoke.php and stats-smoke.php run theirs, so
// bin/check.sh and CI cover both halves in one go
$jsTest	= __DIR__. '/design-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: design-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( 'DESIGN_LISTING='. escapeshellarg( $listingFile ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'design-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

// ninoDone() removes the sandbox with Filesystem::removeDir(), which neither follows a link nor removes one: the link to the kernel made above kept the directory from being emptied, and it stayed behind in the temp directory after every run
unlink( ninoSandboxDir( $appData ). '/_nino' );

ninoDone( $appData );
