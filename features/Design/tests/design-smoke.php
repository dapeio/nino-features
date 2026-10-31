<?php
declare(strict_types=1);

/**
 *	Nino
 *	design-smoke.php		Contract test for the Design feature (Modules\Design):
 *											the manifest and activation through \Nino\Features, the
 *											library of seven parts and what a template must declare
 *											to be one - every set of it held to what a set is, and none
 *											to what it happens to be: the suite names no set of the
 *											library, no theme, no sentence of the manual and no word of the
 *											panel - a word is read from the text files by its key -, and where
 *											it needs names it makes a library of its own -, the
 *											setup's normalisation against a library that is really
 *											there, what the compiler puts in the
 *											sheet and in which order - the five root sizes, the font
 *											roles, the five widths, the five positions of the whitespace
 *											and the animation of the page, the ladders of five and of
 *											three the knob positions select -
 *											the palette and its mode, the roles base.css assigns
 *											against the kernel's Nino.css, the words of the knobs and
 *											of the selects in both languages, the refusal to
 *											overwrite a file Design did not write, and that the theme.css
 *											the setup wizard delivers is the body the compiler writes at
 *											the defaults, byte for byte - and the typefaces: the signed
 *											list of a repository read, checked against a key pair this
 *											run makes and kept, a family installed whole from an archive
 *											the test builds byte by byte (and each way an archive is
 *											refused, by name), listed by Fonts, written into the sheet
 *											with its files, removed while no role of the saved setup
 *											uses it, the panel's three actions over all of it, and
 *											the font-src the workbench's policy gets, for the
 *											previews of the list - and the themes: the two keys the
 *											setup gained, the model of a theme file and the css it is
 *											refused for, the themes the feature ships held to what a
 *											theme is (the first of them the defaults), the fifteenth
 *											section of the compile, the seven actions of the themes
 *											with the codes they answer, the pane in three tabs - the
 *											order the registry lists them in, the class of the one the
 *											panel brings and the words of the strip - over a setup in
 *											six views, the manual, which has an entry for each tab, each
 *											view and each part of the themes and is a line in both
 *											languages, the answers the panel's script is drawn from, the
 *											stylesheets of the active features that the preview links after
 *											the compiled sheet, and the anchor a variant's head names - the
 *											place of the specimen the variant is looked at in.
 *											Travels with the feature and
 *											runs against the checkout three levels up, or the one
 *											NINO_ROOT names (see tests/harness.php there). What the
 *											panel's own script draws is design-js-smoke.js beside this
 *											file, which this suite runs too where node is on the path.
 *
 *	Usage: php features/Design/tests/design-smoke.php
 *	       NINO_ROOT=../nino php features/Design/tests/design-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );

/**
 *	A directory and everything below it, removed: a link in it is removed and not followed
 *
 *	@param		string		$path
 *
 *	@return 	void
 */
function designRemoveTree( string $path ): void {

	if( is_link( $path ) === true || is_file( $path ) === true )
		@unlink( $path );
	else if( is_dir( $path ) === true ) {

		foreach( scandir( $path ) ?: [] as $entry )
			if( $entry !== '.' && $entry !== '..' )
				designRemoveTree( $path. '/'. $entry );

		@rmdir( $path );
	}
}

/**
 *	A directory and everything below it, copied
 *
 *	@param		string		$from
 *	@param		string		$to
 *
 *	@return 	void
 */
function designCopyTree( string $from, string $to ): void {

	mkdir( $to, 0755, true );

	foreach( scandir( $from ) ?: [] as $entry ) {

		if( $entry === '.' || $entry === '..' )
			continue;

		if( is_dir( $from. '/'. $entry ) === true )
			designCopyTree( $from. '/'. $entry, $to. '/'. $entry );
		else
			copy( $from. '/'. $entry, $to. '/'. $entry );
	}
}

/**
 *	A feature of the kind the suite makes for itself where the fixture is not the thing to show: a manifest, a class of the name and
 *	the stylesheets of its variants - none for a feature that brings no design/ directory at all
 *
 *	@param		string		$features			The directory the features live in
 *	@param		string		$name					The directory of the feature, and its class
 *	@param		string		$key					The key of the feature
 *	@param		array 		$variants			variant => the text of its stylesheet
 *	@param		string		$title				The name its manifest gives it; the directory's where none is given
 *
 *	@return 	void
 */
function designMakeFeature( string $features, string $name, string $key, array $variants, string $title = '' ): void {

	mkdir( $features. '/'. $name. ( $variants === [] ? '' : '/design' ), 0755, true );

	$manifest = [
		'key' => $key, 'name' => [ 'en_US' => $title !== '' ? $title : $name, 'de_DE' => $title !== '' ? $title : $name ], 'description' => [ 'en_US' => 'Made by the suite.', 'de_DE' => 'Von der Suite gemacht.' ],
		'manual' => [ 'shortcodes' => [], 'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [], 'install' => [] ],
		'category' => 'ui', 'maturity' => 'Fixture', 'version' => '1.0.0', 'nino' => '^1.8', 'requires' => [], 'data' => [], 'assets' => [], 'settings' => [],
	];

	file_put_contents( $features. '/'. $name. '/feature.php', "<?php\nreturn ". var_export( $manifest, true ). ";\n" );
	file_put_contents( $features. '/'. $name. '/'. $name. ".php", "<?php\ndeclare(strict_types=1);\nnamespace Nino\\Modules {\n\tclass ". $name. " {\n\t}\n}\n" );

	foreach( $variants as $variant => $css )
		file_put_contents( $features. '/'. $name. '/design/'. $variant. '.css', $css );
}

/*	The features directory of this run, which is the one the feature lives in with five things more: the fixture feature the pins of the
	variants a feature brings run on (fixtures/features/Probe, copied), and four features the suite makes for itself where a head is to be
	wrong - one line of it, the anchor, wrong in the way a line can be and a second one beside it -, a key is a part's, a feature has one variant only and the variants of a feature declare knobs of their own (see designMakeFeature()). A directory of its own, so that nothing is written
	to the checkout, named before the kernel loads - the harness reads the constant when it does - and removed when the run ends	*/
if( defined( 'NINO_FEATURES_DIR' ) === false ) {

	$designTree = sys_get_temp_dir(). '/nino-design-features-'. uniqid();
	mkdir( $designTree, 0755, true );

	foreach( glob( dirname( __DIR__, 2 ). '/*', GLOB_ONLYDIR ) ?: [] as $designSibling )
		symlink( $designSibling, $designTree. '/'. basename( $designSibling ) );

	// Design itself is a copy and no link: the registry tells the panel of a feature by where its class file really is (see \Nino\Admin\Panels), and only the panel of a feature gets the Settings tab of that feature
	unlink( $designTree. '/'. basename( dirname( __DIR__ ) ) );
	designCopyTree( dirname( __DIR__ ), $designTree. '/'. basename( dirname( __DIR__ ) ) );

	designCopyTree( __DIR__. '/fixtures/features/Probe', $designTree. '/Probe' );
	designMakeFeature( $designTree, 'Broken', 'broken', [
		'regular' => "/*\t@name\tBroken\n *\t@description\tA head with a line wrong for each thing a line can be wrong in.\n *\t@anchor\tForm Section\n *\n"
			. " *\t@knob\tsize\tscale\ttype\tSize\n"
			. " *\t@knob\tpad\tscale\ttype\tPad, whose ladder is not there\n"
			. " *\t@knob\ttone\tchoice\tsurfaces\tTone, with two of its three words\n *\t@option\ttone\tless\tLess\n *\t@option\ttone\tmore\tMore\n"
			. " *\t@knob\tback\tground\tcolours\tNodefault\n"
			. " *\t@knob\tfront\tground\tcolours\tBadsurface\t= nowhere\n"
			. " *\t@knob\tink\tink\tcolours\tInk\t= subtitle\n"
			. " *\t@knob\tdye\tink\tcolours\tBadtone\t= purple\n"
			. " *\t@knob\tbase\tground\tcolours\tBase\t= brand\n"
			. " *\t@knob\tbase-border\tscale\ttype\tClash, named for a part of the ground\n"
			. " *\t@knob\tmark\tcolour\tcolours\tOnehalf\t= #112233\n"
			. " *\t@knob\tsize\tscale\ttype\tTwice\n"
			. " *\t@knob\tfixed\tscale\ttype\tFixed\t= 2\n"
			. " *\t@knob\todd\twiggle\ttype\tNokind\n"
			. " *\t@knob\toff\tscale\tnotab\tNotab\n"
			. " *\t@knob\tstyle\tscale\tcss\tNocss\n"
			. " *\t@knob\tparts\tscale\ttemplates\tNotemplates\n"
			. " *\t@knob\tBad_Name\tscale\ttype\tBadname\n"
			. " *\t@knob\tbare\tscale\ttype\n"
			. " *\t@knob-de\tghostlabel\tEin Geist\n *\t@option\tghostoption\tless\tNo knob of this name\n"
			. " *\tnot a line of the grammar @knob\n */\n"
			. ":root {\n\t--broken-size--least: 1;\n\t--broken-size--less: 1;\n\t--broken-size--default: 1;\n\t--broken-size--more: 1;\n\t--broken-size--most: 1;\n"
			. "\t--broken-stray--least: 1;\n\t--broken-stray--less: 1;\n\t--broken-stray--default: 1;\n\t--broken-stray--more: 1;\n\t--broken-stray--most: 1;\n"
			. "\t--broken-tone--less: 1;\n\t--broken-tone--default: 1;\n\t--broken-tone--more: 1;\n"
			. "\t--broken-style--least: 1;\n\t--broken-style--less: 1;\n\t--broken-style--default: 1;\n\t--broken-style--more: 1;\n\t--broken-style--most: 1;\n"
			. "\t--broken-parts--least: 1;\n\t--broken-parts--less: 1;\n\t--broken-parts--default: 1;\n\t--broken-parts--more: 1;\n\t--broken-parts--most: 1;\n"
			. "\t--broken-base-border--least: 1;\n\t--broken-base-border--less: 1;\n\t--broken-base-border--default: 1;\n\t--broken-base-border--more: 1;\n\t--broken-base-border--most: 1;\n}\n",
	] );
	designMakeFeature( $designTree, 'Cardish', 'card', [ 'regular' => "/*\t@name\tRegular\n *\t@knob\tsize\tscale\ttype\tSize\n */\n:root { --card-size--least: 1; --card-size--less: 1; --card-size--default: 1; --card-size--more: 1; --card-size--most: 1; }\n" ] );
	designMakeFeature( $designTree, 'Single', 'single', [ 'plain' => "/*\t@name\tPlain\n *\t@description\tThe one look this feature has.\n *\n *\t@knob\tsize\tscale\ttype\tSize\n */\n:root { --single-size--least: 1; --single-size--less: 1; --single-size--default: 1; --single-size--more: 1; --single-size--most: 1; }\n" ], 'Alone' );
	designMakeFeature( $designTree, 'Plain', 'plain', [] );
	$pairStray = "/*\t@name\tStray\n *\t@knob\tgamma\tscale\ttype\tGamma\n */\n:root { --pair-gamma--least: 1; --pair-gamma--less: 1; --pair-gamma--default: 1; --pair-gamma--more: 1; --pair-gamma--most: 1; }\n";
	designMakeFeature( $designTree, 'Pair', 'pair', [
		'first' => "/*\t@name\tFirst\n *\t@anchor\tfirst-place\n *\t@anchor\tsecond-place\n *\t@knob\talpha\tscale\ttype\tAlpha\n */\n:root { --pair-alpha--least: 1; --pair-alpha--less: 1; --pair-alpha--default: 1; --pair-alpha--more: 1; --pair-alpha--most: 1; }\n",
		'second' => "/*\t@name\tSecond\n *\t@anchor-de\tgerman-place\n *\t@knob\talpha\tscale\tmotion\tAlpha\n *\t@knob\tbeta\tscale\ttype\tBeta\n */\n:root { --pair-alpha--least: 1; --pair-alpha--less: 1; --pair-alpha--default: 1; --pair-alpha--more: 1; --pair-alpha--most: 1;"
			. " --pair-beta--least: 1; --pair-beta--less: 1; --pair-beta--default: 1; --pair-beta--more: 1; --pair-beta--most: 1; }\n",
		'two words' => $pairStray,
		'a..b' => $pairStray,
	] );

	define( 'NINO_FEATURES_DIR', $designTree );
	register_shutdown_function( static function() use ( $designTree ): void { designRemoveTree( $designTree ); } );
}

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
use Nino\Modules\Design\Themes;
use Nino\Modules\Design\Webfonts;

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
 *	The style rules of a stylesheet, in the order they stand, each with the block it stands in: its selector, the text
 *	between its braces and - for a rule inside an @media or another block that holds rules - the prelude of that block,
 *	'' for a rule at the top
 *
 *	@param		string		$css					Without comments
 *	@param		string		$within				The prelude of the block $css is the inside of
 *
 *	@return 	array											[ selector, body, within ] for each rule
 */
function designRules( string $css, string $within = '' ): array {

	$rules	= [];
	$at			= 0;

	while( $at < strlen( $css ) && ( $open = strpos( $css, '{', $at ) ) !== false ) {

		$prelude	= trim( (string) preg_replace( '/\s+/', ' ', substr( $css, $at, $open - $at ) ) );
		$depth		= 0;

		for( $close = $open, $length = strlen( $css ); $close < $length; $close++ ) {

			if( $css[$close] === '{' )
				$depth++;

			if( $css[$close] === '}' && --$depth === 0 )
				break;
		}

		$body = substr( $css, $open + 1, $close - $open - 1 );

		if( str_starts_with( $prelude, '@' ) === true && str_contains( $body, '{' ) === true )
			$rules = array_merge( $rules, designRules( $body, trim( $within. ' '. $prelude ) ) );
		else
			$rules[] = [ 'selector' => $prelude, 'body' => $body, 'within' => $within ];

		$at = $close + 1;
	}

	return $rules;
}

/**
 *	What a custom property comes to in a stylesheet, the way a browser takes it:
 *	its value, and where that is the var() of another property, the value of that
 *	one - whitespace folded
 *
 *	@param		string		$css					A stylesheet, with its comments
 *	@param		string		$name					eg. '--shadow-card'
 *
 *	@return 	string										'' where the property is not declared
 */
function designResolved( string $css, string $name ): string {

	$declared	= designDeclarations( designPlain( $css ) );
	$value		= $declared[$name] ?? '';

	for( $depth = 0; $depth < 8 && preg_match( '/^var\(\s*(--[a-z0-9-]+)\s*\)$/', $value, $step ) === 1; $depth++ )
		$value = $declared[ $step[1] ] ?? '';

	return (string) preg_replace( '/\s+/', ' ', $value );
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
 *	A stylesheet without its header: everything after the first closing mark
 *	of a comment and the line break that follows it, the way
 *	Compiler::stamped() reads the body a header's digest is taken of
 *
 *	@param		string		$css
 *
 *	@return 	string										'' where there is no header
 */
function designBody( string $css ): string {

	$at = strpos( $css, "*/\n" );

	return $at === false ? '' : substr( $css, $at + 3 );
}

/**
 *	The opening comment of a library file: the first comment block, which says what
 *	the file is - its name, its description in both languages, the knobs it answers to
 *
 *	@param		string		$file					A path
 *
 *	@return 	string										Without the comment marks' own line; '' where the file has none
 */
function designOpening( string $file ): string {

	$head = (string) @file_get_contents( $file, false, null, 0, 4096 );

	return preg_match( '~/\*(.*?)\*/~s', $head, $block ) === 1 ? $block[1] : '';
}

/**
 *	A compiled sheet without the opening comment of the seven parts' sets in it: the comment
 *	under the marker of a section of a frame or of a set, which describes the file - its
 *	name, its description in both languages, the knobs it answers to - and is no part of
 *	what a browser draws, and which the owner of a library rewrites as he pleases.
 *	Everything else of the sheet stays
 *
 *	@param		string		$css					A compiled sheet, or its body
 *
 *	@return 	string
 */
function designWithoutOpenings( string $css ): string {
	return (string) preg_replace( '~(/\* ==== \d+\. [a-z]+: [^\n]*==== \*/\n)/\*.*?\*/\n~s', '$1', $css );
}

/**
 *	The values a stylesheet declares for the ladder of each knob of a part, below
 *	the breakpoint of 768px and from it, whitespace folded - the ladder of the
 *	knob itself and not the aspects that stand beside it
 *
 *	@param		string		$css					A stylesheet
 *	@param		string		$part					A key of Setup::KNOBS
 *
 *	@return 	array											[ 'base' | 'wide' => [ knob => [ word => value ] ] ], the words in the order of Setup::STEPS
 */
function designLadderValues( string $css, string $part ): array {

	$plain 	= designPlain( $css );
	$opener = '@media (min-width: 768px) {';
	$wide 	= designBlock( $plain, $opener );
	$read 	= [ 'base' => $wide === '' ? $plain : str_replace( $opener. $wide. '}', '', $plain ), 'wide' => $wide ];
	$out 		= [ 'base' => [], 'wide' => [] ];

	foreach( $read as $where => $text ) {

		preg_match_all( '/--'. preg_quote( $part, '/' ). '-([a-z0-9]+(?:-[a-z0-9]+)*)--('. implode( '|', Setup::STEPS ). ')\s*:\s*([^;]+);/', $text, $found, PREG_SET_ORDER );

		foreach( $found as $hit )
			if( isset( Setup::KNOBS[$part][ $hit[1] ] ) === true )
				$out[$where][ $hit[1] ][ $hit[2] ] = (string) preg_replace( '/\s+/', ' ', trim( $hit[3] ) );
	}

	foreach( $out as $where => $knobs )
		foreach( array_keys( $knobs ) as $knob )
			uksort( $out[$where][$knob], static fn( string $a, string $b ): int => array_search( $a, Setup::STEPS, true ) <=> array_search( $b, Setup::STEPS, true ) );

	return $out;
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
 *	A stylesheet without what a template sets as what it is built as and not as a role of the Type
 *	tab: the declarations of one property in the rules whose selectors all end in one kind of
 *	element - the case of a table head in a tables template, the weight of the mark before an item
 *	in a lists template. What is written anywhere else stays in it
 *
 *	@param		string		$css					Without its comments
 *	@param		string		$ending				A pattern for the last compound of a selector - th, li::before
 *	@param		string		$property			The property taken out of such a rule - text-transform, font-weight
 *
 *	@return 	string
 */
function designWithoutConstruction( string $css, string $ending, string $property ): string {

	return (string) preg_replace_callback( '/([^{}]+)\{([^{}]*)\}/', static function( array $rule ) use ( $ending, $property ): string {

		// The selectors of the rule, cut where a comma stands outside of brackets: :where( :not( a, b ) ) is one
		$selectors	= [];
		$current		= '';
		$depth			= 0;

		foreach( str_split( $rule[1] ) as $character ) {

			$depth += $character === '(' ? 1 : ( $character === ')' ? -1 : 0 );

			if( $character === ',' && $depth === 0 ) {
				$selectors[] = $current;
				$current = '';
			}
			else
				$current .= $character;
		}

		$selectors[] = $current;

		foreach( $selectors as $selector ) {

			// What the selector ends in is the last compound of it, with the brackets of its pseudo-classes taken out - and the names of those that took them
			while( preg_match( '/\([^()]*\)/', $selector ) === 1 )
				$selector = (string) preg_replace( '/\([^()]*\)/', '', $selector );

			$selector = (string) preg_replace( '/:(?:where|not|is|has)(?![\w-])/i', '', $selector );

			$compounds = (array) preg_split( '/[\s>+~]+/', trim( $selector ), -1, PREG_SPLIT_NO_EMPTY );

			if( preg_match( $ending, (string) end( $compounds ) ) !== 1 )
				return $rule[0];
		}

		return $rule[1]. '{'. (string) preg_replace( '/'. preg_quote( $property, '/' ). '\s*:[^;]*;?/', '', $rule[2] ). '}';
	}, $css );
}

/**
 *	What is wrong with the knobs of one set of a part, read the way the panel
 *	and the compiler read it and not any looser: a ladder that is missing a
 *	step or has the length of another kind, a name that is no knob of the part
 *	and no aspect of one, an aspect of another length than its knob, a rule
 *	reading a knob nobody declared, a header that documents other knobs than
 *	the file declares, a set that writes a face, a weight or a case - but for the
 *	case of a table head in a tables template, the weight of the mark before an
 *	item in a lists template, which are what those are built as and no role, and
 *	the case of a thing that the part has a toggle for, read from the toggle - or a
 *	colour written as a literal, and a ladder or a knob declared, or a knob or one
 *	of its steps read, that belongs to another part - but for the one family a
 *	section set and a card set may read of the other, by a token its opening
 *	comment names. A frame is held to the ladders and not to the roles: it is the
 *	construction of a bar or of a footer, with the ink and the type a bar has
 *
 *	@param		string		$library			The feature's library
 *	@param		string		$part					A key of Setup::KNOBS
 *	@param		string		$set					A set name
 *
 *	@return 	array											One sentence for each, led by the set's name - [] where there is nothing wrong
 */
function designKnobProblems( string $library, string $part, string $set ): array {

	$style = Setup::file( $library, $part, $set );

	if( $style === '' )
		return [ $set. ': has no stylesheet' ];

	/*	Read the way the panel reads it, or the check is looser than the
		thing it is checking: Setup::ladders() looks for the token
		immediately followed by ':' in a stylesheet with its comments taken
		out. A space before the colon is valid css and invisible to that,
		so '--card-border--less : 0' declared a step the panel never
		finds - and a knob whose five steps are all written that way is
		one nobody can turn, with nothing saying so	*/
	$source		= (string) file_get_contents( $style );
	$css			= designPlain( $source );
	$problems	= [];
	$words		= implode( '|', Setup::STEPS );

	preg_match_all( '/--'. preg_quote( $part, '/' ). '-([a-z0-9]+(?:-[a-z0-9]+)*)--('. $words. ')\s*:/', $css, $found );

	$steps = [];
	foreach( $found[1] as $index => $name )
		$steps[$name][] = $found[2][$index];

	foreach( $steps as $name => $declared ) {

		// A knob of the part, or an aspect of one - which stands beside the ladder of its knob. A name that is a knob of its own is that knob's: title-case is the toggle and no aspect of title
		$knob = isset( Setup::KNOBS[$part][$name] ) === true ? $name : null;
		foreach( $knob === null ? array_keys( Setup::KNOBS[$part] ) : [] as $candidate )
			if( str_starts_with( $name, $candidate. '-' ) === true ) {
				$knob = $candidate;
				break;
			}

		if( $knob === null ) {
			$problems[] = $set. ': "'. $name. '" is neither a knob of "'. $part. '" nor an aspect of one';
			continue;
		}

		// The ladder of a scale is five words, the ladder of a choice the three inner ones, the ladder of a toggle the middle and the one after it - in the order of Setup::STEPS, once each
		$ladder		= Setup::words( Setup::kind( $part, $knob ) );
		$declared	= array_values( array_intersect( Setup::STEPS, $declared ) );
		$length		= [ 2 => 'two', 3 => 'three', 5 => 'five' ][ count( $ladder ) ];

		if( $name === $knob && $declared !== $ladder )
			$problems[] = $set. ': "'. $name. '" is not a ladder of '. $length;
		else if( $name !== $knob && count( $declared ) !== count( $ladder ) )
			$problems[] = $set. ': "'. $name. '" is a ladder of another length than "'. $knob. '"';
		else if( $name !== $knob && $declared !== $ladder )
			$problems[] = $set. ': "'. $name. '" is not a ladder of '. $length;

		if( $name !== $knob && isset( $steps[$knob] ) === false )
			$problems[] = $set. ': "'. $name. '" is an aspect of "'. $knob. '", which the set does not declare';
	}

	// ...and what it reads. A rule on a token nothing declared is a rule
	// that does nothing, which is invisible until somebody moves the knob
	preg_match_all( '/var\(\s*--'. preg_quote( $part, '/' ). '-([a-z0-9]+(?:-[a-z0-9]+)*)\s*[,)]/', $css, $used );
	foreach( array_unique( $used[1] ) as $token )
		if( isset( $steps[$token] ) === false )
			$problems[] = $set. ': reads --'. $part. '-'. $token. ' without declaring it';

	/*	...and what belongs to a part that is not this one's. The parts are
		compiled in the order they are shown in, so a set that declares the ladder
		of another part's knob overrides the steps of the template chosen there - a
		tables set declaring --buttons-size--default would replace what the
		buttons template published, with the panel still showing the tables
		answering to their own knobs only. So does one that declares the knob
		itself, which the compiler selects on :root for the template chosen there:
		a nested `.nino-form .nino-btn { --buttons-size: 2; }` beats that selection
		for every element it matches. A rule that reads another part's knob, or one
		of its steps by name, follows a position that part's template may not
		publish at all. The templates of a part stay exchangeable only while each of
		them is about its own knobs	*/
	$others = implode( '|', array_diff( array_keys( Setup::KNOBS ), [ $part ] ) );

	preg_match_all( '/--('. $others. ')-([a-z0-9]+(?:-[a-z0-9]+)*)--(?:'. $words. ')\s*:/', $css, $foreign, PREG_SET_ORDER );

	$declares = [];
	foreach( $foreign as $hit )
		$declares[ $hit[1]. '-'. $hit[2] ] = $hit[1];

	foreach( $declares as $ladder => $other )
		$problems[] = $set. ': declares --'. $ladder. ', a ladder of the '. $other. ' part';

	// ...and the knob itself, once for each name - and not again for one whose ladder was reported just now
	preg_match_all( '/--('. $others. ')-([a-z0-9]+(?:-[a-z0-9]+)*)\s*:/', $css, $foreign, PREG_SET_ORDER );

	$redeclared = [];
	foreach( $foreign as $hit )
		if( isset( $declares[ $hit[1]. '-'. $hit[2] ] ) === false )
			$redeclared[ $hit[1]. '-'. $hit[2] ] = $hit[1];

	foreach( $redeclared as $knob => $other )
		$problems[] = $set. ': declares --'. $knob. ', a knob of the '. $other. ' part';

	// A step is read by its name as well as the knob: var(--buttons-size--default) follows the buttons template just as var(--buttons-size) does
	preg_match_all( '/var\(\s*--('. $others. ')-([a-z0-9]+(?:-[a-z0-9]+)*)(?:--(?:'. $words. '))?\s*[,)]/', $css, $foreign, PREG_SET_ORDER );

	/*	The section family and the card family are one design in two halves - a panel of a section casts the shadow of a card - and where a set of the one has to read
		a value of the other it reads it by its token and says so in its opening comment: a read that the comment names is no dependency nobody can see. Declaring one never is	*/
	$family		= [ 'section' => 'card', 'card' => 'section' ];
	$opening	= designOpening( $style );
	$reads		= [];

	foreach( $foreign as $hit )
		if( ( $family[$part] ?? '' ) !== $hit[1] || str_contains( $opening, '--'. $hit[1]. '-'. $hit[2] ) === false )
			$reads[ $hit[1]. '-'. $hit[2] ] = $hit[1];

	foreach( $reads as $knob => $other )
		$problems[] = $set. ': reads --'. $knob. ', a knob of the '. $other. ' part';

	// The header's @knob lines are the file's own documentation of what it publishes: no more and no fewer
	preg_match_all( '/@knob\s+([a-z0-9-]+)/', (string) substr( $source, 0, (int) strpos( $source, '*/' ) ), $documented );
	$published = Setup::knobs( $library, $part, $set );
	sort( $documented[1] );
	$sortedPublished = $published;
	sort( $sortedPublished );
	if( $documented[1] !== $sortedPublished )
		$problems[] = $set. ': the @knob lines name ['. implode( ' ', $documented[1] ). '] and the ladders publish ['. implode( ' ', $sortedPublished ). ']';

	// A template with no knob has nothing to finetune, and its controls would be a screen of grey
	if( $published === [] )
		$problems[] = $set. ': answers to no knob at all';

	/*	The face and the weight are the roles of the Type view, the case a switch of the objects, and a colour is a role: never a literal. A
		tables template may set the case of a table head, and a lists template the weight of a mark - that is what they
		are built as, and the roles are the title's and the text's, not these - so the rules whose selectors all end in
		one are read without it, and one set anywhere else in it is reported like the others. A frame is no set of
		the Type view's: the bar and the footer set the ink and the type a bar has, and are held to their ladders alone	*/
	if( ( Setup::PARTS[$part] ?? 'set' ) === 'frame' )
		return $problems;

	$allowed	= [ 'tables' => [ '/^th(?![\w-])/i', 'text-transform' ], 'lists' => [ '/^li::before$/i', 'font-weight' ] ][$part] ?? null;
	$read			= $allowed === null ? $css : designWithoutConstruction( $css, $allowed[0], $allowed[1] );

	// ...and the case of a thing the part has a toggle for, which a set writes by reading the toggle and by no other value: text-transform: var(--<part>-<toggle>)
	foreach( array_keys( array_filter( Setup::KNOBS[$part], static fn( string $kind ): bool => $kind === 'toggle' ) ) as $toggle )
		$read = (string) preg_replace( '/text-transform\s*:\s*var\(\s*--'. preg_quote( $part. '-'. $toggle, '/' ). '\s*\)\s*;?/', '', $read );

	if( preg_match( '/(?:^|[;{\s])(?:font-family|font-weight|text-transform)\s*:/', $read ) === 1 )
		$problems[] = $set. ': writes the face, the weight or the case of a role';
	if( preg_match( '/#[0-9a-fA-F]{3,8}\b|\b(?:rgb|rgba|hsl|hsla|oklch|lab|lch)\(/', $css ) === 1 )
		$problems[] = $set. ': writes a colour as a literal';

	return $problems;
}

/**
 *	One ustar header - enough tar to build an archive PharData reads, and enough
 *	to name what a real build never would: a "../", a link
 *
 *	@param		string		$name
 *	@param		int				$size
 *	@param		string		$type					'0' a file, '5' a directory, '2' a symbolic link, '1' a hard link
 *	@param		string		$link					Where a link points
 *
 *	@return 	string
 */
function designTarHeader( string $name, int $size, string $type, string $link = '' ): string {

	$header  = str_pad( $name, 100, "\0" );
	$header .= "0000644\0". "0000000\0". "0000000\0";
	$header .= str_pad( decoct( $size ), 11, '0', STR_PAD_LEFT ). "\0";
	$header .= str_pad( decoct( 1767225600 ), 11, '0', STR_PAD_LEFT ). "\0";
	$header .= '        '. $type. str_pad( $link, 100, "\0" ). "ustar\0". '00';
	$header  = str_pad( $header, 512, "\0" );

	$sum = 0;

	for( $i = 0; $i < 512; $i++ )
		$sum += ord( $header[$i] );

	return substr( $header, 0, 148 ). str_pad( decoct( $sum ), 6, '0', STR_PAD_LEFT ). "\0 ". substr( $header, 156 );
}

/**
 *	A .tar.gz out of name => content: a string is a file, null a directory,
 *	[ 'link' => target ] a symbolic link, [ 'hardlink' => target ] a hard link
 *
 *	@param		array 		$entries
 *
 *	@return 	string
 */
function designTarGz( array $entries ): string {

	$out = '';

	foreach( $entries as $name => $content ) {

		if( $content === null )
			$out .= designTarHeader( (string) $name, 0, '5' );
		else if( is_array( $content ) === true )
			$out .= designTarHeader( (string) $name, 0, isset( $content['hardlink'] ) === true ? '1' : '2', (string) ( $content['link'] ?? $content['hardlink'] ) );
		else
			$out .= designTarHeader( (string) $name, strlen( $content ), '0' ). str_pad( $content, (int) ( ceil( strlen( $content ) / 512 ) * 512 ), "\0" );
	}

	return (string) gzencode( $out. str_repeat( "\0", 1024 ), 6 );
}

/**
 *	The manifest of a family out of the files it has - a weight and a style each
 *  (a weight is a number, or '100 900' for an axis) -, with the file names W1 gives
 *	them: 400.woff2, 700-italic.woff2, 100-900.woff2
 *
 *	@param		string		$slug
 *	@param		string		$family
 *	@param		array 		$files				[ [ weight, style ], ... ]
 *	@param		array 		$more					Fields that are not these (the version, the category, ...)
 *
 *	@return 	array
 */
function designManifest( string $slug, string $family, array $files, array $more = [] ): array {

	$list 		= [];
	$weights 	= [];
	$styles 	= [];

	foreach( $files as [ $weight, $style ] ) {
		$list[] 									= [ 'weight' => $weight, 'style' => $style, 'file' => str_replace( ' ', '-', (string) $weight ). ( $style === 'italic' ? '-italic' : '' ). '.woff2' ];
		$weights[(string) $weight] = $weight;
		$styles[$style] 					= $style;
	}

	return array_replace( [
		'slug' 			=> $slug,
		'family' 		=> $family,
		'category' 	=> 'sans',
		'version' 	=> '1.0',
		'license' 	=> 'OFL-1.1',
		'source' 		=> 'https://fonts.test/'. $slug,
		'weights' 	=> array_values( $weights ),
		'styles' 		=> array_values( array_intersect( [ 'normal', 'italic' ], $styles ) ),
		'files' 		=> $list,
	], $more );
}

/**
 *	What an archive of a family holds: the directory <slug>/, its font.json and LICENSE, and a woff2 file for
 *	each file the manifest names - bytes of this run's own, beginning the way a woff2 file does
 *
 *	@param		array 		$manifest
 *
 *	@return 	array											name => content, for designTarGz()
 */
function designArchiveFiles( array $manifest ): array {

	$slug = $manifest['slug'];

	$entries = [
		$slug. '/' 						=> null,
		$slug. '/font.json' 	=> json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ). "\n",
		$slug. '/LICENSE' 		=> 'The licence text of '. $manifest['family']. "\n",
	];

	foreach( $manifest['files'] as $file )
		$entries[ $slug. '/'. $file['file'] ] = 'wOF2'. $slug. '/'. $file['file'];

	return $entries;
}

/**
 *	The entry of the list for an archive: the manifest, then where the archive and its preview are and
 *	what the archive is
 *
 *	@param		array 		$manifest
 *	@param		string		$archive			The bytes
 *	@param		array 		$more					What is not this
 *
 *	@return 	array
 */
function designListEntry( array $manifest, string $archive, array $more = [] ): array {

	return array_replace( $manifest + [
		'archive' 	=> 'https://webfonts.test/'. $manifest['slug']. '-'. $manifest['version']. '.tar.gz',
		'sha256' 		=> hash( 'sha256', $archive ),
		'size' 			=> strlen( $archive ),
		'preview' 	=> 'https://webfonts.test/'. $manifest['slug']. '-'. $manifest['version']. '-preview.woff2',
		'released' 	=> '2026-10-12',
	], $more );
}

/**
 *	The list document, as webfonts.json is written
 *
 *	@param		array 		$entries
 *	@param		array 		$over					Keys of the document
 *
 *	@return 	string
 */
function designList( array $entries, array $over = [] ): string {

	return (string) json_encode( $over + [ 'format' => 1, 'generated' => '2026-10-12T09:30:00Z', 'families' => $entries ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
}

/**
 *	A signature the way bin/build.php of the repository and `openssl dgst -sha256 -sign` write it: DER, base64
 *
 *	@param		string		$data
 *	@param		mixed			$privateKey		A key, or PEM
 *
 *	@return 	string
 */
function designSign( string $data, mixed $privateKey ): string {

	openssl_sign( $data, $signature, $privateKey, OPENSSL_ALGO_SHA256 );

	return base64_encode( $signature );
}

/**
 *	Whether the staging directory of an install holds nothing
 *
 *	@param		string		$staging
 *
 *	@return 	bool
 */
function designStagingClean( string $staging ): bool {

	return is_dir( $staging ) === false || ( scandir( $staging ) ?: [] ) === [ '.', '..' ];
}

// The two markers: base.css numbers its two halves with dashes, the compiled sheet its sections with equals signs
const DESIGN_BASE_MARKER = '/* ---- %d. %s ---- */';

// The kernel's own stylesheet and the theme.css the setup wizard delivers, where this checkout has them
$deliveredFile	= $root. '/_admin/install/library/base/assets/theme.css';
$ninoCss				= designPlain( (string) @file_get_contents( $root. '/_nino/Nino.css' ) );

echo "The manifest, and what it promises\n";

$feature = \Nino\Features::manifest( dirname( __DIR__ ) );

check( 'the manifest reads, with the key and the class the directory implies', is_array( $feature ) === true
	&& $feature['key'] === 'design' && $feature['module'] === '\\Nino\\Modules\\Design' );
// ^1.7: the card. Nino 1.7 called it one - nino-card with every part and
// modifier, --card-title-scale, --card-img-max-width and --card-img-max-height
// - and kept no alias for the old names: the content sets, base.css, the type
// the compiler writes and the specimen are written for the new ones, and the
// theme.css the kernel delivers is the body this compiler writes of them, so
// on a 1.6 they style nothing and the compile is not the delivered file.
// The floor before it was 1.6, set when the hero's rename, the five roles of the
// type and the grounds were expected in that release; they came after its tag,
// with the card, so 1.7 is the first kernel that has all of it (the kernel's
// CHANGELOG lists them under 1.7). The floor before that was 1.4: the frames read words of the
// kernel's - /project/company/..., the words of /template/frame-header and
// /template/frame-footer, the page details after /_nino/webpage - and write
// the project's frame-header.tpl and frame-footer.tpl, which are named so
// from 1.4 on, and 1.6 has them all. A pre-release of 1.7 counts as 1.7
check( 'it names ^1.7 - the kernel whose card, hero, roles and grounds its templates, its compiler and its specimen are written for', ( $feature['nino'] ?? '' ) === '^1.7' );
check( '...and no 1.6 can run it, a 1.7 pre-release can', \Nino\Features::satisfies( (string) $feature['nino'], '1.5.0' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.2.0-beta' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.6.0' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.7.0-dev' ) === true );
check( 'it is Design 2.1: the model of 2.0 with the typefaces and the five knobs of round 4, and the version says so', ( $feature['version'] ?? '' ) === '2.1.0' );
check( 'the setup file is declared under data, so a backup carries it', in_array( Setup::PATH, (array) ( $feature['data'] ?? [] ), true ) === true );
$says = static fn( string $entry, string $locale ): string => \Nino\Features::localized( $feature['manual']['panel'][$entry] ?? [], $locale );
// The line as the file has it, with no fallback to another language: what a check of both languages reads
$said = static fn( string $entry, string $locale ): string => trim( (string) ( $feature['manual']['panel'][$entry][$locale] ?? '' ) );

/*	The words of the panel, taken from the text files themselves: the views the manual has to
	have an entry for are the views the model has, by the word the panel draws them with	*/
$panelWord = static fn( string $key, string $locale ): string => (string) ( ( (array) include __DIR__. '/../text/'. $locale. '.php' )['[[/_admin/design/'. $key. ']]'] ?? '' );

/*	A sentence of the panel's texts with its %s filled in turn, as the panel's own _text() does it: what a check says of a sentence the server answers with
	is read from its key, and never typed here	*/
$panelText = static function( string $key, string $locale, string ...$values ) use ( $panelWord ): string {

	$text = $panelWord( $key, $locale );

	foreach( $values as $value )
		$text = (string) preg_replace_callback( '/%s/', static fn(): string => $value, $text, 1 );

	return $text;
};


/*	What the strip on top of the pane is drawn from: the panel's own screen by what tab() answers - the library of the themes, which the strip calls Themes where the rail calls the
	panel Design - and each tab the panel brings by the label its nav() names. All three are fills of the kernel's own list (/_admin/nav/...), the text files have a word for each in
	both languages, and what is held here is that there is one: the words are the owner's, and they are read by their keys	*/
$stripFills = array_merge( [ Admin::tab() ], array_map( static fn( string $tab ): string => (string) ( $tab::nav()[1] ?? '' ), Admin::tabs() ) );
$stripWords = static fn( string $locale ): array => array_map( static fn( string $fill ): string => (string) ( ( (array) include __DIR__. '/../text/'. $locale. '.php' )['[['. $fill. ']]'] ?? '' ), $stripFills );
$panelTabs = array_values( array_map(
	static fn( string $key ): string => (string) preg_replace( '~^\[\[/_admin/design/tab/([a-z0-9-]+)/label\]\]$~', '$1', $key ),
	array_filter( array_keys( (array) include __DIR__. '/../text/en_US.php' ), static fn( string $key ): bool => preg_match( '~^\[\[/_admin/design/tab/[a-z0-9-]+/label\]\]$~', $key ) === 1 )
) );

/*	The views the model names are the views the panel draws - the script holds none of them, it takes them from the table - and the script is read for what it names literally,
	which is where the panel has it: a test that typed it again would hold the script to nothing (the chips of the loader are read the same way, further down)	*/
$panelScript = (string) file_get_contents( __DIR__. '/../assets/admin.js' );

/*	The manual is the feature's only documentation, and what is held of it is its shape and not
	its words: that every tab of the pane, every view of the setup and every part of the themes has an entry, in both
	languages, and that an entry is a line - what a sentence of it says is for whoever writes it.
	An entry is found by its handle, which names the tab, the view or the button by the word the panel
	draws it with	*/
$handles = array_map( 'mb_strtolower', array_keys( (array) ( $feature['manual']['panel'] ?? [] ) ) );
$handled = static fn( string $word ): bool => $word !== '' && array_filter( $handles, static fn( string $handle ): bool => str_contains( $handle, mb_strtolower( $word ) ) ) !== [];
$themeParts = array_map( static fn( string $key ): string => strtok( $panelWord( $key, 'en_US' ), ' ' ), [ 'theme/load', 'theme/edit', 'theme/duplicate', 'theme/save', 'theme/delete', 'theme/export', 'theme/css/title' ] );
$themeParts[] = 'import';

/*	The words the panel borrows from the workbench for the rename and the way back (Rename, Save, Cancel, Back to list) are the kernel's: a checkout that carries its text files is asked for them,
	in both languages, and the manual names the rename by the kernel's word	*/
$kernelWords = static fn( string $locale ): array => is_file( $root. '/_admin/text/'. $locale. '.php' ) === true ? (array) include $root. '/_admin/text/'. $locale. '.php' : [];
$borrowed = [ 'rename', 'save', 'cancel', 'back' ];

check( 'the pane has three tabs on top - the strip of the kernel reads the panel\'s own screen, the one tab the panel brings and the Settings tab the registry attaches - and the manual has an entry for each of the two the feature draws, found by the word the strip draws it with: both text files have that word, by key',
	count( $stripFills ) === 2 && array_filter( $stripFills, static fn( string $fill ): bool => preg_match( '~^/_admin/nav/[a-z-]+$~', $fill ) !== 1 ) === []
	&& array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => in_array( '', $stripWords( $locale ), true ) === true ) === []
	&& array_filter( $stripWords( 'en_US' ), static fn( string $word ): bool => in_array( mb_strtolower( $word ), $handles, true ) === false ) === [] );
check( 'the setup has six views - the ones the model names, in its order, are the ones its text files name - and the manual has an entry for each, found in the handles of the manual by the word the panel draws them with',
	count( Setup::TABS ) === 6 && $panelTabs === Setup::TABS
	&& array_filter( Setup::TABS, static fn( string $tab ): bool => $handled( $panelWord( 'tab/'. $tab. '/label', 'en_US' ) ) === false ) === [] );
check( '...the panel\'s own screen is the one place of the setup: Admin::tabs() brings the loader of the typefaces and nothing else, and the manual\'s entry for the panel names the three tabs and the way back to the list by the words the panel draws them with',
	Admin::tabs() === [ Webfonts\Tab::class ]
	&& array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => str_contains( $says( 'Design', $locale ), (string) ( $kernelWords( $locale )['[[/_admin/common/label/back]]'] ?? "\0" ) ) === false ) === [] );

// The two axes of the setup are told in one entry: the overview names the six views and the blocks of the views name the eight objects, so only the entry on the axes names both - each by the word the panel draws it with, and in both languages by the same entry
$namedIn = static function( array $words, string $locale ) use ( $feature, $says ): array {
	return array_values( array_filter( array_keys( (array) ( $feature['manual']['panel'] ?? [] ) ), static fn( string $entry ): bool => array_filter( $words, static fn( string $word ): bool => $word === '' || str_contains( $says( $entry, $locale ), $word ) === false ) === [] ) );
};
$axisNamed = [];

foreach( [ 'en_US', 'de_DE' ] as $axisLocale )
	$axisNamed[$axisLocale] = $namedIn( array_merge(
		array_map( static fn( string $tab ): string => $panelWord( 'tab/'. $tab. '/label', $axisLocale ), Setup::TABS ),
		array_map( static fn( string $object ): string => $panelWord( 'object/'. $object. '/label', $axisLocale ), Setup::OBJECTS )
	), $axisLocale );

$axisEntries = array_values( array_intersect( $axisNamed['en_US'], $axisNamed['de_DE'] ) );

check( '...the two axes are told in one entry of the manual: the same entry names all six views and all eight objects in both languages, by the words the panel draws them with'. ( $axisEntries === [] ? ' - no entry does in both: '. count( $axisNamed['en_US'] ). ' in English, '. count( $axisNamed['de_DE'] ). ' in German' : '' ),
	$axisEntries !== [] && count( Setup::TABS ) === 6 && count( Setup::OBJECTS ) === 8 );

// The hover of the pictures is told in an entry of its own, which names the class that makes a picture take part and the two controls by the words the panel draws them with - the same entry in both languages
$hoverNamed = [];

foreach( [ 'en_US', 'de_DE' ] as $hoverLocale )
	$hoverNamed[$hoverLocale] = $namedIn( [ 'nino-hover', $panelWord( 'motion/hover/label', $hoverLocale ), $panelWord( 'motion/hover-strength/label', $hoverLocale ) ], $hoverLocale );

$hoverEntries = array_values( array_intersect( $hoverNamed['en_US'], $hoverNamed['de_DE'] ) );

check( '...and so is the hover of the pictures: one entry names the class a picture says to take part and the two controls of the page\'s block, by the words the panel draws them with, in both languages'. ( $hoverEntries === [] ? ' - no entry does in both: '. count( $hoverNamed['en_US'] ). ' in English, '. count( $hoverNamed['de_DE'] ). ' in German' : '' ),
	count( $hoverEntries ) === 1 && $panelWord( 'motion/hover/label', 'en_US' ) !== '' && $panelWord( 'motion/hover-strength/label', 'de_DE' ) !== '' );

// The description is the line the feature is listed by: it names the six views of the setup and the two tabs of its own the strip on top of the pane draws, each by the word the panel draws it with and as a word of its own - Type is no part of Typefaces
$describedGaps = [];

foreach( [ 'en_US', 'de_DE' ] as $describedLocale )
	foreach( array_merge( array_map( static fn( string $tab ): string => $panelWord( 'tab/'. $tab. '/label', $describedLocale ), Setup::TABS ), $stripWords( $describedLocale ) ) as $describedWord )
		if( preg_match( '/(?<!\p{L})'. preg_quote( $describedWord, '/' ). '(?!\p{L})/u', \Nino\Features::localized( (array) ( $feature['description'] ?? [] ), $describedLocale ) ) !== 1 )
			$describedGaps[] = $describedLocale. ' '. $describedWord;

check( '...and the description of the feature names the six views and the two tabs in both languages, each by the word the panel draws it with'. ( $describedGaps === [] ? '' : ' - missing: '. implode( ', ', $describedGaps ) ),
	$describedGaps === [] && count( Setup::TABS ) === 6 && count( $stripFills ) === 2 );

check( '...and one for each part of the themes - what its buttons do (load, edit, duplicate, save, delete, export), the import, the rename by the kernel\'s word for it - and the css of a theme, which is the view CSS',
	count( $themeParts ) === 8 && array_filter( $themeParts, static fn( string $part ): bool => $handled( $part ) === false ) === []
	&& $handled( (string) ( $kernelWords('en_US')['[[/_admin/common/label/rename]]'] ?? '' ) ) === true );
check( '...and the four words the panel borrows from the workbench - Rename, Save, Cancel and Back to list - are in the workbench\'s text files, in both languages',
	array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => array_filter( $borrowed, static fn( string $word ): bool => ( $kernelWords( $locale )['[[/_admin/common/label/'. $word. ']]'] ?? '' ) === '' ) !== [] ) === [] );
check( '...every entry in both languages',
	array_filter( array_keys( (array) ( $feature['manual']['panel'] ?? [] ) ), static fn( string $entry ): bool => $said( $entry, 'en_US' ) === '' || $said( $entry, 'de_DE' ) === '' ) === [] );
// Features::manifest() holds a line of the manual to 1000 characters; it would refuse the whole file, and the first check of this suite says so
check( '...every line of it a line and not a paragraph: at most 1000 bytes in either language, as the kernel reads a manifest',
	array_filter( (array) ( $feature['manual']['panel'] ?? [] ), static fn( array $lines ): bool => array_filter( $lines, static fn( string $line ): bool => strlen( $line ) > 1000 ) !== [] ) === [] );
/*	The features that bring variants have two entries, found by what they name and not by their handles: the author's names the five kinds, the tags of a head - the German line of a knob among them -, the folder and the two
	places a default may stand; the editor's names the folder, the view the variant is chosen on - by the word the panel draws it with - and the ratio under which the panel says a colour
	falls short. Each in both languages, one of each	*/
$variantEntries = [];

foreach( [ 'en_US', 'de_DE' ] as $variantLocale ) {

	$texts = array_map( static fn( string $entry ): string => $says( $entry, $variantLocale ), array_keys( (array) ( $feature['manual']['panel'] ?? [] ) ) );
	$author = array_merge( Setup::FEATURE_KINDS, [ '@name', '@description', '@knob', '@knob-de', '@option', 'design/', ':where(:root)', ':where(*)' ] );
	$editor = [ 'design/', $panelWord( 'tab/templates/label', $variantLocale ), (int) Colours::TARGET_UI. ':1' ];

	$variantEntries[$variantLocale] = [
		count( array_filter( $texts, static fn( string $text ): bool => array_filter( $author, static fn( string $needle ): bool => str_contains( $text, $needle ) === false ) === [] ) ),
		count( array_filter( $texts, static fn( string $text ): bool => array_filter( $editor, static fn( string $needle ): bool => str_contains( $text, $needle ) === false ) === [] ) ),
	];
}

check( 'the manual tells the features that bring variants twice, in both languages: once for the person who chooses - the folder, the view the variant is chosen on, the ratio below which a colour is said to fall short - and once for the person who writes a variant - the five kinds, the tags of a head with the German line of a knob among them, the two places a default may stand',
	$variantEntries === [ 'en_US' => [ 1, 1 ], 'de_DE' => [ 1, 1 ] ] );
check( 'the manual names the one callback the feature registers, on /nino/http/output, in both languages, and the setting that callback reads',
	array_keys( (array) ( $feature['manual']['callbacks'] ?? [] ) ) === [ '/nino/http/output' ]
	&& trim( \Nino\Features::localized( $feature['manual']['callbacks']['/nino/http/output'], 'en_US' ) ) !== '' && trim( \Nino\Features::localized( $feature['manual']['callbacks']['/nino/http/output'], 'de_DE' ) ) !== ''
	&& isset( $feature['settings']['webfontsUrl']['label']['en_US'], $feature['settings']['webfontsUrl']['label']['de_DE'] ) === true );

// The themes: where the themes of the project are kept
check( 'the themes of the project are in the backup: /data/themes is declared under data, and a theme the class writes - one file below it - is in the manifest of what a backup carries, as data/themes/<slug>.php',
	in_array( Themes::DIR, (array) ( $feature['data'] ?? [] ), true ) === true
	&& Themes::write( $appData, 'backup-probe', Themes::record( 'Backup probe', '', Setup::defaults( $library ), '' ) ) === true
	&& in_array( ltrim( Themes::DIR, '/' ). '/backup-probe.php', \Nino\Backup::manifest( $appData ), true ) === true );
\Nino\Filesystem::removeDir( \Nino\Filesystem::path( $appData, Themes::DIR ) );

echo "\nThe library it ships\n";

check( 'seven parts, in the order they are compiled in: the header and the footer bring markup, the section, the card, the buttons, the lists and the tables are a stylesheet each',
	Setup::PARTS === [ 'header' => 'frame', 'footer' => 'frame', 'section' => 'set', 'card' => 'set', 'buttons' => 'set', 'lists' => 'set', 'tables' => 'set' ] );
check( 'the sets of the model before this one are gone - there is no library/sets', is_dir( $library. '/sets' ) === false );

foreach( Setup::PARTS as $part => $kind ) {
	$available = Setup::available( $library, $part );
	check( 'the library offers at least one set for "'. $part. '"', $available !== [] );
}

/*	The families are the sets of the content and of the components the model before this one had, cut by the object each is a design for: the section and the card
	are one design for two objects and list the same names, so that a theme names a sensible pair - the four the content had - and the tables are the five the
	components had. A count and a likeness, and no name typed here	*/
check( 'the section and the card families list the same names, four of them, as the sets of the content they come from did - and the tables family the five the components sets had: a family is one design for each of its objects',
	Setup::available( $library, 'section' ) === Setup::available( $library, 'card' ) && count( Setup::available( $library, 'section' ) ) === 4
	&& count( Setup::available( $library, 'tables' ) ) === 5 && Setup::available( $library, 'lists' ) !== [] );

/*	The set a fresh install has for each part is the one the first shipped theme names (see Setup::defaults()): what
	the checks below call the default set of a part, whichever set that is	*/
$defaultSets = Setup::defaults( $library )['parts'];

check( 'a frame offers its template and its stylesheet', Setup::file( $library, 'header', $defaultSets['header'], 'template' ) !== ''
	&& Setup::file( $library, 'header', $defaultSets['header'] ) !== '' );
check( 'a set name that is not a set name resolves to nothing', Setup::file( $library, 'section', '../../../etc/passwd' ) === ''
	&& Setup::file( $library, 'section', $defaultSets['section']. '/../'. $defaultSets['section'] ) === ''
	&& Setup::file( $library, 'section', '' ) === ''
	&& Setup::file( $library, 'sets', $defaultSets['section'] ) === '' );

/*	The library is written by hand and grows by hand, so what is held here is
	what a new file gets wrong - and gets wrong quietly. A set is named by a
	slug and carries its name and its description in both languages, a name is
	not translated, two under one name are two rows nobody can tell apart, a
	knob outside Setup::KNOBS is a handle the panel will never show, a
	ladder missing a step is a knob position that compiles
	to nothing - and one of five for a choice, or of three for a scale, is
	not published at all - a rule reading a token the file never declared is a
	declaration that silently does not apply, a set that declares a ladder or
	the selected knob of another part overrides the template chosen there, one
	that reads a knob or a step of another part follows a position that
	template may not publish, and a template that writes the face, the weight
	or the case of the text contradicts the Type view. A frame is held to the
	same but for that last rule - it is the construction of a bar or of a
	footer, with the ink and the type a bar has - and it answers to knobs of its
	part and to none of another; the default set answers to all of its part's,
	a template that leaves one out has it greyed out in the panel.

	Written against the shipped library rather than a fixture on purpose: a
	fixture would prove the rule and let the files drift - and over every file
	of a kind, so that no name of a set stands in it, and a set the owner adds
	is held to the same. That the rule refuses what it names is shown further
	down, over throwaway sets.	*/
foreach( Setup::PARTS as $part => $kind ) {

	$catalogue	= Setup::catalogue( $library, $part );
	$names			= array_column( $catalogue, 'name' );
	$slugs			= array_filter( array_keys( $catalogue ), static fn( string $set ): bool => preg_match( Themes::SLUG, $set ) === 1 );
	$unsaid			= [];

	// Every file says what it is, in the lines of its opening comment: @name, @description and @description-de - and no @name-de, for a name is a name
	foreach( array_keys( $catalogue ) as $set ) {

		$opening = designOpening( Setup::file( $library, $part, $set ) );

		foreach( [ 'name', 'description', 'description-de' ] as $tag )
			if( preg_match( '/@'. $tag. '[ \t]+\S/', $opening ) !== 1 )
				$unsaid[] = $set. ': no @'. $tag;

		if( preg_match( '/@name-de\b/', $opening ) === 1 )
			$unsaid[] = $set. ': has a @name-de';

		if( Setup::describe( $library, $part, $set, 'de_DE' )['name'] !== Setup::describe( $library, $part, $set, 'en_US' )['name'] )
			$unsaid[] = $set. ': is named differently in the two languages';
	}

	check( '"'. $part. '" offers a choice rather than a single template', count( $catalogue ) > 1 );
	check( '...every one of them a slug - lower-case words joined by hyphens, the pattern a theme\'s slug has', count( $slugs ) === count( $catalogue ) );
	check( '...every one of them named, and no two the same', count( array_unique( $names ) ) === count( $names ) && array_filter( $names, static fn( string $name ): bool => trim( $name ) === '' ) === [] );
	check( '...and every one of them saying what it is: its name, its description and its description in German in the opening comment of its file, no name in German'
		. ( $unsaid === [] ? '' : ' - '. implode( ', ', $unsaid ) ), $unsaid === [] );

	$knobProblems	= [];
	$fileProblems	= [];

	foreach( array_keys( $catalogue ) as $set ) {

		$style = Setup::file( $library, $part, $set );

		if( $style === '' || ( $kind === 'frame' && Setup::file( $library, $part, $set, 'template' ) === '' ) ) {
			$fileProblems[] = $set;
			continue;
		}

		$knobProblems = array_merge( $knobProblems, designKnobProblems( $library, $part, $set ) );
	}

	// Both lists carry the file and what is wrong with it; a failure that does
	// not say which of the templates it means is a search rather than a report
	check( '...each resolving to the files its kind is made of'. ( $fileProblems === [] ? '' : ' - '. implode( ', ', $fileProblems ) ), $fileProblems === [] );

	check( '...every ladder it declares is a ladder of a knob of its part, or an aspect of one, of the length of the kind of the knob - five for a scale, three for a choice - every one it reads is declared, and none it declares or reads is another part\'s'. ( $knobProblems === [] ? '' : ' - '. implode( ' | ', $knobProblems ) ), $knobProblems === [] );
	check( '...the default set of the part declares every knob of it - it is the framework as it is, with something for each knob to reach',
		Setup::knobs( $library, $part, $defaultSets[$part] ) === array_keys( Setup::KNOBS[$part] ) );
}

/*	The ladders are Nino's in every template of a stylesheet. A knob is a measure and the template owns its ladder, but it is the
	same ladder in every template of a part - the default set's, value for value, below the breakpoint of 768px and from it
	- so that a switch of template moves no size. What a template keeps is its construction: how a hero is composed, how a
	band, a card, a table or a button is built. A template that answers to a knob declares the five values of the default
	set for it, or the three of a choice, and the aspects that stand beside a ladder - the lift of a shadow, the ink of the
	stripes - are its own, and left out. The frames are left out of this one: the middle of a frame's ladder is the bar or the
	footer as that set has it, so a header of 4.5rem and one of 5rem do not answer to one height, and the ladders of a frame are
	its own, held to what a set is above	*/
$ladderProblems	= [];
$compared				= 0;
$levels					= [];

foreach( array_keys( array_filter( Setup::PARTS, static fn( string $kind ): bool => $kind === 'set' ) ) as $part ) {

	$nino = designLadderValues( (string) file_get_contents( Setup::file( $library, $part, $defaultSets[$part] ) ), $part );

	foreach( $nino as $where => $knobs )
		foreach( $knobs as $knob => $words )
			$levels[$part. '/'. $knob. ( $where === 'wide' ? ' from 768px' : '' )] = array_unique( array_values( $words ) );

	foreach( Setup::available( $library, $part ) as $set ) {

		if( $set === $defaultSets[$part] )
			continue;

		$own = designLadderValues( (string) file_get_contents( Setup::file( $library, $part, $set ) ), $part );

		foreach( Setup::knobs( $library, $part, $set ) as $knob )
			foreach( [ 'base', 'wide' ] as $where ) {

				$compared++;

				if( ( $own[$where][$knob] ?? [] ) !== ( $nino[$where][$knob] ?? [] ) )
					$ladderProblems[] = $part. '/'. $set. ' '. $knob. ( $where === 'wide' ? ' from 768px' : '' );
			}
	}
}

check( 'every template of a part declares for every knob it answers to the ladder of the default set, value for value - the five values of a scale or the three of a choice, below 768px and from it, and no value from 768px that the default set has not - so that a switch of template moves no size ('. $compared. ' ladders compared)'
	. ( $ladderProblems === [] ? '' : ' - not the default\'s: '. implode( ', ', array_slice( $ladderProblems, 0, 6 ) ) ), $ladderProblems === [] && $compared > 0 );
check( '...and the ladders of the default set are not all one value - most of them have values that differ from step to step - so what is compared is something',
	$levels !== [] && count( array_filter( $levels, static fn( array $values ): bool => count( $values ) > 1 ) ) > count( $levels ) / 2 );

/*	The knobs of a section are the model's, and every section set answers to all of them and to no other - but for the toggles, the case of the title and of the subtitle, which the default
	set answers to and another may leave: the @knob lines of its opening comment and the ladders it publishes are the
	knobs of Setup::KNOBS['section'], and a ladder is the ladder of one of them or an aspect beside it. A knob the model dropped leaves no line and no ladder behind in any set. The
	sets point the numbers Nino.css reads by name at the knobs - the size of a section's title and of its subtitle, the thickness of the rule under a title, the two variants - and each
	of those is a custom property the kernel's Nino.css declares, the same ones in every set: a number the kernel does not read is a line that does nothing, and a set that pointed
	another would make a switch of template move the page	*/
$sectionKnobs		= array_keys( Setup::KNOBS['section'] );
$sectionSets		= Setup::available( $library, 'section' );
$sectionDrift		= [];
$pointedAt			= [];
$ninoReads			= [];

preg_match_all( '/(?<![\w-])(--[a-z0-9-]+)\s*:/', $ninoCss, $ninoNames );

foreach( $sectionSets as $set ) {

	$source = (string) file_get_contents( Setup::file( $library, 'section', $set ) );

	preg_match_all( '/@knob\s+([a-z0-9-]+)/', designOpening( Setup::file( $library, 'section', $set ) ), $named );
	preg_match_all( '/--section-([a-z0-9]+(?:-[a-z0-9]+)*)--(?:'. implode( '|', Setup::STEPS ). ')\s*:/', designPlain( $source ), $laddered );
	preg_match_all( '/(?<![\w-])(--[a-z0-9-]+)\s*:\s*var\(\s*--section-([a-z0-9-]+)\s*\)\s*;/', designPlain( $source ), $assigned, PREG_SET_ORDER );

	$strays = array_filter( array_unique( $laddered[1] ), static fn( string $name ): bool => array_filter( $sectionKnobs, static fn( string $knob ): bool => $name === $knob || str_starts_with( $name, $knob. '-' ) ) === [] );

	// The default set answers to all of them, the toggles too; another set to every knob but the toggles, which are the default set's alone
	$answers = $set === $defaultSets['section'] ? $sectionKnobs : array_keys( array_filter( Setup::KNOBS['section'], static fn( string $kind ): bool => $kind !== 'toggle' ) );

	if( $named[1] !== $answers || Setup::knobs( $library, 'section', $set ) !== $answers || $strays !== [] )
		$sectionDrift[] = $set;

	foreach( $assigned as $assignment )
		if( isset( Setup::KNOBS['section'][ $assignment[2] ] ) === true ) {

			$pointedAt[$set][ $assignment[1] ] = $assignment[2];

			if( in_array( $assignment[1], $ninoNames[1], true ) === false )
				$ninoReads[] = $set. ' '. $assignment[1];
		}
}

check( 'every section template answers to every knob of its part and to no other - the toggles, the case of the title and of the subtitle, are the default set\'s alone: the @knob lines of its opening comment and the ladders it publishes are the knobs of Setup::KNOBS, in its order, and no ladder is the ladder of another name'
	. ( $sectionDrift === [] ? '' : ' - not so for '. implode( ', ', $sectionDrift ) ), count( $sectionSets ) >= 2 && $sectionDrift === [] );
check( '...and points the numbers Nino.css reads by name at its knobs - the same ones in every set, the loud and the quiet variant among them, each a custom property the kernel\'s Nino.css declares'
	. ( $ninoReads === [] ? '' : ' - not declared by Nino.css: '. implode( ', ', $ninoReads ) ),
	count( $pointedAt ) === count( $sectionSets ) && count( array_unique( array_map( 'serialize', $pointedAt ) ) ) === 1 && $ninoReads === []
	&& ( reset( $pointedAt )['--scale-loud'] ?? '' ) === 'loud' && ( reset( $pointedAt )['--scale-quiet'] ?? '' ) === 'quiet' );

/*	The hero is the loud variant: the kernel sizes the title and the subtitle of a hero by their role times --scale-loud and by no number of its own - below 768px and from it, where the
	hero states its size again - so the loud knob of a section, which every section set points that number at, sizes the hero as it sizes the loud variants. The first check below is the
	coupling pin: the kernel's own suite holds that formula, this one holds the library to the Nino.css it runs against - it is red against a kernel that still sizes the hero by numbers
	of its own, on which the loud knob would not move the hero. And the middle of the loud ladder and of the quiet one is the number Nino.css declares for it - the middle rung of a
	ladder is Nino's value - held against the Nino.css this suite runs against, so that it holds on any kernel whose numbers move together with the library's	*/
$heroSizes		= array_values( array_filter( designRules( $ninoCss ), static fn( array $rule ): bool => in_array( $rule['selector'], [ '.nino-hero-title', '.nino-hero-subtitle' ], true ) === true && preg_match( '/(?<![\w-])font-size\s*:/', $rule['body'] ) === 1 ) );
$heroReads		= [];

foreach( $heroSizes as $heroSize ) {

	preg_match( '/(?<![\w-])font-size\s*:\s*([^;]+);/', $heroSize['body'], $heroValue );
	preg_match_all( '/var\(\s*(--[a-z0-9-]+)/', $heroValue[1] ?? '', $heroProperties );

	if( preg_match( '/^--font-(?:title|subtitle)-size$/', $heroProperties[1][0] ?? '' ) !== 1 || array_slice( $heroProperties[1], 1 ) !== [ '--scale-loud' ] )
		$heroReads[] = $heroSize['selector']. ( $heroSize['within'] === '' ? '' : ' in '. $heroSize['within'] );
}

$ninoRoot	= designDeclarations( designBlock( $ninoCss, ':root {' ) );
$middles	= [];

foreach( $sectionSets as $set ) {

	$ladder = designLadderValues( (string) file_get_contents( Setup::file( $library, 'section', $set ) ), 'section' );

	foreach( [ 'loud' => '--scale-loud', 'quiet' => '--scale-quiet' ] as $knob => $number )
		if( (float) ( $ladder['base'][$knob]['default'] ?? -1 ) !== (float) ( $ninoRoot[$number] ?? -2 ) )
			$middles[] = $set. ' '. $knob;
}

check( 'the hero is the loud variant: wherever Nino.css states the size of a hero title and of a hero subtitle - below 768px and from it - it is the size of the role times --scale-loud, and nothing else'
	. ( $heroReads === [] ? '' : ' - not so for '. implode( ', ', $heroReads ) ), count( $heroSizes ) >= 4 && $heroReads === [] );
check( '...and the middle of the loud ladder and of the quiet ladder is the number the kernel declares for --scale-loud and --scale-quiet, in every section template'
	. ( $middles === [] ? '' : ' - not so for '. implode( ', ', $middles ) ), $middles === [] && isset( $ninoRoot['--scale-loud'], $ninoRoot['--scale-quiet'] ) === true );

/*	The type of a table and of a list keeps the scale as it is: calc( 1em times the scale ), in every template of the two, so
	that the ladder is a factor on the size the page has, and not a size of its own. The fields of a form are the kernel's and
	have no rule here, so there is no floor of 1rem to hold them to	*/
$scaleKept = [];

foreach( [ 'tables' => '.nino-table', 'lists' => '.nino-list' ] as $scalePart => $scaleSelector )
	foreach( Setup::available( $library, $scalePart ) as $set ) {

		$folded = (string) preg_replace( '/\s+/', ' ', designPlain( (string) file_get_contents( Setup::file( $library, $scalePart, $set ) ) ) );

		if( str_contains( $folded, $scaleSelector. ' { font-size: calc( 1em * var(--'. $scalePart. '-scale) );' ) === false )
			$scaleKept[] = $scalePart. '/'. $set;
	}

check( 'the type of a table and of a list stays calc( 1em times the scale ) in every template of the two'
	. ( $scaleKept === [] ? '' : ' - not: '. implode( ', ', $scaleKept ) ), $scaleKept === [] );

/*	The frames the fresh install has are the ones the kernel delivers, to the byte: the header and the footer of the first
	shipped theme are frame-header.tpl and frame-footer.tpl of the base unit, so that a project that applies the defaults
	keeps the markup it started from	*/
foreach( [ 'header', 'footer' ] as $frame ) {

	$delivered = $root. '/_admin/install/library/base/templates/frame-'. $frame. '.tpl';

	if( is_file( $delivered ) === false )
		echo "  --  this checkout has no installer library, so the ". $frame. " of the defaults is not compared with the kernel's\n";
	else
		check( 'the '. $frame. ' the fresh install has is the kernel\'s own frame-'. $frame. '.tpl, byte for byte',
			(string) file_get_contents( Setup::file( $library, $frame, $defaultSets[$frame], 'template' ) ) === (string) file_get_contents( $delivered ) );
}

/*	A template is named by its construction, in both languages: @name and @description in English and
	@description-de beside them, in the opening comment of its file. describe() reads the lines of the language it is
	asked for - the first two letters of the locale - and the English ones where a file has none in that language. Held
	over a throwaway library, so that the files of the shipped one stay what their owner writes them as	*/
$bilingual = ninoSandboxDir( $appData ). '/library-names';
mkdir( $bilingual. '/section', 0755, true );
mkdir( $bilingual. '/header/first', 0755, true );
file_put_contents( $bilingual. '/section/first.css', "/*\t@name Both\n *\t@description Both languages.\n *\t@name-de Beide\n *\t@description-de Beide Sprachen.\n */\n:root {}\n" );
// A template somebody else wrote: English lines only - and one with the name in German and the description not
file_put_contents( $bilingual. '/section/second.css', "/*\t@name English only\n *\t@description Written by somebody else.\n */\n:root {}\n" );
file_put_contents( $bilingual. '/section/third.css', "/*\t@name Half\n *\t@description Both here.\n *\t@name-de Halb\n */\n:root {}\n" );
// ...and one whose German line stands below the first comment, which is none of the file's
file_put_contents( $bilingual. '/section/fourth.css', "/*\t@name Opening\n *\t@description The opening comment.\n */\n.nino-section { /* @name-de Darunter */ }\n/*\t@name-de Auch darunter\n *\t@description-de Und das auch.\n */\n" );
file_put_contents( $bilingual. '/header/first/template.tpl', '<header></header>' );
file_put_contents( $bilingual. '/header/first/style.css', "/*\t@name Frame\n *\t@description A frame with English lines.\n */\n" );

check( 'a file that carries the German lines gives them under de_DE and the English ones under en_US, and under a locale of no language of its own - English has no lines but the plain ones',
	Setup::describe( $bilingual, 'section', 'first', 'de_DE' ) === [ 'name' => 'Beide', 'description' => 'Beide Sprachen.' ]
	&& Setup::describe( $bilingual, 'section', 'first', 'en_US' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ]
	&& Setup::describe( $bilingual, 'section', 'first' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ]
	&& Setup::describe( $bilingual, 'section', 'first', 'de' ) === [ 'name' => 'Beide', 'description' => 'Beide Sprachen.' ]
	&& Setup::describe( $bilingual, 'section', 'first', 'fr_FR' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ]
	&& Setup::describe( $bilingual, 'section', 'first', 'en_GB' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ]
	&& Setup::describe( $bilingual, 'section', 'first', '' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ] );
check( '...a locale is no pattern: what is not two letters of the alphabet at its start names no line, whatever it holds',
	Setup::describe( $bilingual, 'section', 'first', 'd.*' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ]
	&& Setup::describe( $bilingual, 'section', 'first', '../../x' ) === [ 'name' => 'Both', 'description' => 'Both languages.' ]
	&& Setup::describe( $bilingual, 'section', 'first', 'DE_de' ) === [ 'name' => 'Beide', 'description' => 'Beide Sprachen.' ] );
check( '...a file with no German line gives the English one under de_DE - a template somebody else wrote is listed, in the words it was written in - and a file that has the one of the two gives that one and the English for the other',
	Setup::describe( $bilingual, 'section', 'second', 'de_DE' ) === [ 'name' => 'English only', 'description' => 'Written by somebody else.' ]
	&& Setup::describe( $bilingual, 'section', 'third', 'de_DE' ) === [ 'name' => 'Halb', 'description' => 'Both here.' ]
	&& Setup::describe( $bilingual, 'section', 'fourth', 'de_DE' ) === [ 'name' => 'Opening', 'description' => 'The opening comment.' ]
	&& Setup::describe( $bilingual, 'header', 'first', 'de_DE' ) === Setup::describe( $bilingual, 'header', 'first', 'en_US' ) && Setup::describe( $bilingual, 'header', 'first', 'de_DE' )['name'] === 'Frame' );
check( '...and catalogue() hands the language through: every set of a part under the name of the language asked for, and English where it is not asked for one',
	Setup::catalogue( $bilingual, 'section', 'de_DE' )['first']['name'] === 'Beide' && Setup::catalogue( $bilingual, 'section', 'de_DE' )['second']['name'] === 'English only'
	&& Setup::catalogue( $bilingual, 'section', 'en_US' )['first']['name'] === 'Both' && Setup::catalogue( $bilingual, 'section' )['first']['name'] === 'Both'
	&& array_column( Setup::catalogue( $bilingual, 'header', 'de_DE' ), 'name' ) === [ 'Frame' ] );

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

/*	The colours of a header are roles. --nino-default-focus is the one solved for a ring - 3:1, which is all a ring has to clear, and not what a text
	has to: a header that colours its text with it, the link that is current for one, is a text under 4.5:1 - so no set does, and the role a link is
	coloured with is the link role. And a bar that paints a surface of its own that is see-through - a mix with transparent, or the page's dim - lies over
	whatever the page opens with, and a ring solved against the plain ground of the page falls under 3:1 on it: such a bar declares its own --color-focus,
	on the element that is the bar (the roles of the surface it lies on, as a section declares the roles of its ground). Read from the library's own list of
	headers, so that a header that is added with either fails here rather than on a site	*/
$roleProblems = [];

foreach( Setup::available( $library, 'header' ) as $set ) {

	$css = designPlain( (string) file_get_contents( Setup::file( $library, 'header', $set ) ) );

	if( preg_match( '/(?<![a-z-])color\s*:\s*var\(--nino-default-focus/', $css ) === 1 )
		$roleProblems[] = $set. ': a text coloured with --nino-default-focus';

	$paints = false;
	$declares = false;

	preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER );

	foreach( $rules as $rule ) {

		// The bar is the element a rule ending in .nino-scroll-header paints; the roles of its surface stand on it or on the header that holds it, which they reach it from
		$isBar		= preg_match( '/\.nino-scroll-header\s*$/', trim( $rule[1] ) ) === 1;
		$isHeader	= preg_match( '/\.nino-frame-header\s*$/', trim( $rule[1] ) ) === 1;

		if( $isBar === false && $isHeader === false )
			continue;

		if( $isBar === true && preg_match( '/background(?:-color)?\s*:[^;]*(?:transparent\)|-dim)/', $rule[2] ) === 1 )
			$paints = true;

		if( preg_match( '/--color-focus\s*:/', $rule[2] ) === 1 )
			$declares = true;
	}

	if( $paints === true && $declares === false )
		$roleProblems[] = $set. ': a see-through bar with no --color-focus of its own';
}

check( 'no header colours a text with --nino-default-focus, and a header whose bar is see-through declares the focus role of its own surface, on the bar or on the header that holds it'. ( $roleProblems === [] ? '' : ' - '. implode( ', ', $roleProblems ) ), $roleProblems === [] );

/*	The burger of the kernel is a button: .nino-nav-burger > button with aria-expanded and aria-controls, the icon in it, the list beside it, the open
	state on the button (and nino-is-open on the wrapper). A header that styles what the burger was - a label under it, the checkbox in it, input:checked
	and input:focus-visible with the icon beside them - styles nothing, and its menu opens as the kernel's own and looks as no header meant it. The old
	forms are looked for wherever a header says them - under .nino-nav-burger, or as input:checked and input[type="checkbox"] on their own. Read from
	the library's own list of headers, so that a header that is added in the old form fails here rather than on a site	*/
$burgerProblems = [];

foreach( Setup::available( $library, 'header' ) as $set )
	if( preg_match( '/\.nino-nav-burger[^{,]*\b(?:label|input)\b|\binput\s*(?::checked|\[\s*type\s*=\s*["\']?checkbox)/', designPlain( (string) file_get_contents( Setup::file( $library, 'header', $set ) ) ) ) === 1 )
		$burgerProblems[] = $set;

check( 'no header styles the burger as it was - a label or an input under .nino-nav-burger, or input:checked or input[type="checkbox"] wherever it stands'. ( $burgerProblems === [] ? '' : ' - '. implode( ', ', $burgerProblems ) ), $burgerProblems === [] );

/*	The logo link says the name of the company until there is a logo: the base frame of the kernel writes <span aria-hidden="true">[[/project/company/general/name]]</span>
	after [image /logo] - or after its [/image] where a header writes the enclosing form -, and Nino.css takes it away again once the link holds a picture. A header that draws the link with nothing but the shortcode in it
	leaves a link with nothing in it to see wherever no logo is uploaded, which is a stop of the Tab key that nobody can find. Read from the library's own
	list of headers - those whose template asks for the slot, [image /logo, whatever class the link has - so that a header that is added without the
	span in the link fails here rather than on a site	*/
$nameProblems = [];
$namedBy = 0;

foreach( Setup::available( $library, 'header' ) as $set ) {

	$source = (string) file_get_contents( Setup::file( $library, 'header', $set, 'template' ) );

	if( str_contains( $source, '[image /logo' ) === false )
		continue;

	$namedBy++;

	if( preg_match( '#<a\b[^>]*>(?:(?!</a>).)*?\[image /logo[^\]]*\](?:(?:(?!</a>).)*?\[/image\])?\s*<span aria-hidden="true">\[\[/project/company/general/name\]\]</span>#s', $source ) !== 1 )
		$nameProblems[] = $set;
}

check( 'every header whose logo link asks for the slot writes the name of the company after it, in the span of the kernel\'s own frame, so that a link with no logo in it has a name to read'. ( $nameProblems === [] ? '' : ' - '. implode( ', ', $nameProblems ) ), $namedBy > 0 && $nameProblems === [] );

/*	The brand link of a footer is the logo link of a header over again - a picture in a link, which is a link with nothing in it to see where no logo is
	uploaded - and says the name of the company the same way, in the span of the frame after the logo, which a footer writes as a pair and so after its
	[/image]. Nino.css takes that span away for the link of its own logo and for no other class, so the stylesheet of the footer does, behind a picture.
	And with a picture and an empty alt text, as without one, the link has no name for whoever listens: the header's is named by the heading that stands
	in it, a footer has none, so the name is written once more in the class that hides a text from the eye and from nobody else, as the kernel names its
	burger. Read from the library's own list of footers - those whose template draws the logo in a link, whatever class the link has - so that a footer
	that is added without them fails here rather than on a site	*/
$footerProblems = [];
$footerLinks = 0;

foreach( Setup::available( $library, 'footer' ) as $set ) {

	$source = (string) file_get_contents( Setup::file( $library, 'footer', $set, 'template' ) );

	// The link the logo stands in - a logo that stands in no link leaves no link with nothing in it
	if( preg_match( '#<a\b([^>]*)>((?:(?!</a>).)*?\[image /logo(?:(?!</a>).)*)</a>#s', $source, $link ) !== 1 )
		continue;

	$footerLinks++;

	if( preg_match( '#\[image /logo[^\]]*\](?:(?:(?!</a>).)*?\[/image\])?\s*<span aria-hidden="true">\[\[/project/company/general/name\]\]</span>#s', $link[2] ) !== 1 )
		$footerProblems[] = $set. ': no name after the logo';

	// The name for whoever listens stands in the link and outside the pair of the logo: inside the pair it is drawn with the picture alone
	$outside = (string) preg_replace( '#\[image /logo[^\]]*\](?:(?:(?!</a>).)*?\[/image\])?#s', '', $link[2] );

	if( str_contains( $outside, '<span class="nino-sr-only">[[/project/company/general/name]]</span>' ) === false )
		$footerProblems[] = $set. ': the link has no name for whoever listens';

	// The stylesheet takes the span away behind a picture for the class of that link, in a rule that holds on every medium - a rule
	// under an @media is read as none, as a rule for another class is
	$classes = preg_match( '/\bclass="([^"]*)"/', $link[1], $found ) === 1 ? preg_split( '/\s+/', trim( $found[1] ) ) : [];
	$sheet = (string) preg_replace( '/@media[^{]*\{(?:[^{}]*+\{[^{}]*+\})*+[^{}]*+\}/', '', designPlain( (string) file_get_contents( Setup::file( $library, 'footer', $set ) ) ) );
	$hidden = false;

	foreach( (array) $classes as $class )
		if( $class !== '' && preg_match( '/(?:^|[\s,}])[^{},]*\.'. preg_quote( (string) $class, '/' ). ':has\(\s*img\s*\)\s*>\s*span\[aria-hidden=["\']?true["\']?\]\s*\{[^{}]*\bdisplay\s*:\s*none/', $sheet ) === 1 )
			$hidden = true;

	if( $hidden === false )
		$footerProblems[] = $set. ': the name stays beside a picture';
}

check( 'every footer whose logo stands in a link writes the name of the company after it, in the span of the frame of the kernel, and the same name for whoever listens beside it, and its stylesheet takes the span away where the link holds a picture - so that the link has a name to read with no logo in it and a name to hear with or without one'. ( $footerProblems === [] ? '' : ' - '. implode( ', ', $footerProblems ) ), $footerLinks > 0 && $footerProblems === [] );

/*	The whitespace position of the Surfaces view is the kernel's token, and Nino.css scales what it spaces with it: the padding of a section and of
	a card by --whitespace-scale, the room under a text by --whitespace-text. A section set and a card set say that spacing again, after Nino.css, so each has to
	say it with the same factors - a section's padding with the first, a card's padding with the first and the room under its texts with the second -, or the
	position stops at the set: a section as high at +2 as at -2. Read from the library's own list of the two families, so that a set that is added without them
	fails here rather than on a site	*/
$whitespaceProblems = [];

foreach( [ 'section' => [ '--whitespace-scale' ], 'card' => [ '--whitespace-scale', '--whitespace-text' ] ] as $whitespacePart => $whitespaceTokens )
	foreach( Setup::available( $library, $whitespacePart ) as $set ) {

		$css = designPlain( (string) file_get_contents( Setup::file( $library, $whitespacePart, $set ) ) );

		foreach( $whitespaceTokens as $token )
			if( str_contains( $css, 'var('. $token ) === false )
				$whitespaceProblems[] = $whitespacePart. '/'. $set. ': '. $token;
	}

check( 'every section set scales the space of a section with --whitespace-scale, and every card set the space of a card with it and the room under a text with --whitespace-text, as Nino.css does, so that the whitespace position of the Surfaces view does not stop at the set'. ( $whitespaceProblems === [] ? '' : ' - '. implode( ', ', $whitespaceProblems ) ), $whitespaceProblems === [] );

/*	And the one thing reading the files cannot say: that each of them survives
	the compiler. Every template of every part, one at a time, because a single
	setup naming all of them would prove only that the last one landed	*/
$compileProblems = [];

foreach( Setup::PARTS as $part => $kind )
	foreach( Setup::available( $library, $part ) as $set ) {

		$sheet = Compiler::compile( $appData, Setup::normalize( $appData, [ 'parts' => [ $part => $set ] ], $library ), $library );

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
	a footer once read /website/footer/title/followus, which left the base unit with
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

/*	The bands of the Panel set, and the hover of the card sets and the button sets: two things a stylesheet of the library says in a rule and a person sees only in a browser, so they are
	read as rules - the way a browser reads them, with no comment between - against the kernel's own Nino.css where the library is to agree with it	*/

// The simple selectors of the :where(:not(...)) list a rule ends in, split where a comma stands outside of every bracket - the conditions the rule is made on
$splitCommas = static function( string $list ): array {

	$items 	= [];
	$depth 	= 0;
	$item 	= '';

	foreach( str_split( $list ) as $character ) {

		$depth += $character === '(' || $character === '[' ? 1 : ( $character === ')' || $character === ']' ? -1 : 0 );

		if( $character === ',' && $depth === 0 ) {
			$items[] = trim( $item );
			$item = '';
			continue;
		}

		$item .= $character;
	}

	$items[] = trim( $item );

	return array_values( array_filter( $items, static fn( string $entry ): bool => $entry !== '' ) );
};
$notList = static fn( string $selector ): array => preg_match( '/:where\(:not\((.*)\)\)$/', $selector, $found ) === 1 ? $splitCommas( $found[1] ) : [];

$panelRules 	= designRules( designPlain( (string) file_get_contents( Setup::file( $library, 'section', 'panel' ) ) ) );
$panelBand 		= array_values( array_filter( $panelRules, static fn( array $rule ): bool => str_contains( $rule['selector'], '+' ) === false && preg_match( '/(?<![\w-])border-radius\s*:\s*var\(--radius-surface\)/', $rule['body'] ) === 1 ) );
$stacking 		= array_filter( $panelRules, static fn( array $rule ): bool => str_contains( $rule['selector'], '+' ) === true && preg_match( '/(?<![\w-])border-radius\s*:\s*0(?:px)?\s*(?:;|$)/', $rule['body'] ) === 1 );
$gapRules 		= array_values( array_filter( $panelRules, static fn( array $rule ): bool => str_contains( $rule['selector'], '+' ) === true && preg_match( '/(?<![\w-])margin-top\s*:\s*var\(--grid-gutter\)\s*(?:;|$)/', $rule['body'] ) === 1 ) );
$gapSelector 	= $gapRules[0]['selector'] ?? '';
$sameBand 		= preg_match( '/^(\.[\w-]+) \+ \1:where\(/', $gapSelector, $bandClass ) === 1 && str_starts_with( $panelBand[0]['selector'] ?? '', $bandClass[1]. ':where(' );

check( 'the bands of the Panel set are two panels with room between them, whatever ground each has: no rule with a + in its selector takes the radius away from the second - there is no stack - and one rule gives a band that follows a section a margin of one gutter above it',
	count( $panelBand ) === 1 && $stacking === [] && count( $gapRules ) === 1 && $sameBand === true );
check( '...on the conditions of the rule that makes a band a panel - the opening and the bands that run the full width are left as the page has them - and one more: a margin the page gave the band itself, a nino-mt-<n> or a nino-m-<n>, is the page\'s',
	$notList( $panelBand[0]['selector'] ?? '' ) !== [] && array_diff( $notList( $panelBand[0]['selector'] ?? '' ), $notList( $gapSelector ) ) === []
	&& in_array( '[class*="nino-mt-"]', $notList( $gapSelector ), true ) === true && in_array( '[class*="nino-m-"]', $notList( $gapSelector ), true ) === true );

/*	A hover keeps the transition of the viewport animation. Nino.css makes the animation a transition - the rule of .nino-vpa names its properties, its time and its easing - and a hover
	rule of a set writes the list of a transition for itself, which takes the animation's away from the element for as long as it is hovered: a card that is still arriving stops where it
	is. So the list of every hover rule of the card sets and the button sets, the default of each part among them, names the properties of the animation, and gives the ones the hover does not change its
	time and its easing. The opacity of a button is the framework's own: the kernel dims the brand buttons under the pointer, and a dimming is no arrival	*/
$kernelRules = designRules( designPlain( (string) file_get_contents( $root. '/_nino/Nino.css' ) ) );
$vpaRule 		= array_values( array_filter( $kernelRules, static fn( array $rule ): bool => str_contains( $rule['selector'], '.nino-vpa' ) === true && preg_match( '/(?<![\w-])transition-property\s*:/', $rule['body'] ) === 1 ) )[0]['body'] ?? '';
$vpaValue 		= static fn( string $property ): string => preg_match( '/(?<![\w-])'. $property. '\s*:\s*([^;]+);/', $vpaRule, $found ) === 1 ? trim( (string) preg_replace( '/\s+/', ' ', $found[1] ) ) : '';
$vpaProperties 	= array_map( 'trim', explode( ',', $vpaValue( 'transition-property' ) ) );
$vpaDuration 	= $vpaValue( 'transition-duration' );
$vpaEasing 		= $vpaValue( 'transition-timing-function' );
$buttonsDim 	= array_filter( $kernelRules, static fn( array $rule ): bool => str_contains( $rule['selector'], '.nino-btn' ) === true && str_contains( $rule['selector'], ':hover' ) === true && preg_match( '/(?<![\w-])opacity\s*:/', $rule['body'] ) === 1 ) !== [];

$hoverLists = [];

foreach( [ 'card', 'buttons' ] as $part )
	foreach( Setup::available( $library, $part ) as $set )
		foreach( designRules( designPlain( (string) file_get_contents( Setup::file( $library, $part, $set ) ) ) ) as $rule )
			if( str_contains( $rule['selector'], ':hover' ) === true && preg_match( '/(?<![\w-])transition\s*:\s*([^;]+);/', $rule['body'], $found ) === 1 )
				$hoverLists[$part][$set][] = array_map( static fn( string $entry ): string => trim( (string) preg_replace( '/\s+/', ' ', $entry ) ), $splitCommas( $found[1] ) );

$leftOut 			= [];
$ownTime 			= [];
$entriesChecked = 0;

foreach( $hoverLists as $part => $sets )
	foreach( $sets as $set => $lists )
		foreach( $lists as $entries ) {

			foreach( array_diff( $vpaProperties, array_map( static fn( string $entry ): string => explode( ' ', $entry )[0], $entries ) ) as $property )
				$leftOut[] = $part. '/'. $set. ' '. $property;

			foreach( $entries as $entry )
				if( in_array( explode( ' ', $entry )[0], $vpaProperties, true ) === true && ( $part === 'buttons' && $buttonsDim === true && str_starts_with( $entry, 'opacity ' ) === true ) === false ) {

					$entriesChecked++;

					if( str_contains( $entry, $vpaDuration ) === false || str_contains( $entry, $vpaEasing ) === false )
						$ownTime[] = $part. '/'. $set. ' '. $entry;
				}
		}

check( 'a hover keeps the transition of the viewport animation: the list of each hover rule of the card sets and of the button sets - the default of each part among them - names every property the animation transitions'
	. ( $leftOut === [] ? '' : ' - not so for '. implode( ', ', $leftOut ) ),
	count( $vpaProperties ) >= 2 && $leftOut === [] && isset( $hoverLists['card'], $hoverLists['buttons'] ) === true && array_sum( array_map( 'count', $hoverLists['card'] ) ) >= 1 && array_sum( array_map( 'count', $hoverLists['buttons'] ) ) >= 1 );
check( '...and gives each of them the time and the easing the animation has - its custom property for the duration and the easing as Nino.css writes it - but for the opacity of a button, which the kernel dims under the pointer'
	. ( $ownTime === [] ? '' : ' - not so for '. implode( ' | ', $ownTime ) ),
	$vpaDuration !== '' && $vpaEasing !== '' && $entriesChecked >= 3 && $ownTime === [] );


echo "\nThe setup, held against the library that is there\n";

$notes = [];
$setup = Setup::normalize( $appData, [], $library, $notes );

$firstTheme = Themes::read( $appData, $library, Themes::SHIPPED[0] ) ?? [];

check( 'a setup out of nothing is every part on the set the first shipped theme names, the delivered size, the width, the whitespace, the radius and the animation and the hover of the pictures Nino.css has, and no note',
	$notes === [] && array_keys( $setup ) === Setup::KEYS
	&& $setup['parts'] === ( Themes::setup( $firstTheme )['parts'] ?? [] ) && array_keys( $setup['parts'] ) === array_keys( Setup::PARTS )
	&& $setup['size'] === 0 && $setup['width'] === 0 && $setup['whitespace'] === 0
	&& $setup['radius'] === 0 && $setup['radius-alt'] === 1 && $setup['radii'] === array_fill_keys( Setup::SURFACES, 'standard' )
	&& $setup['motion'] === [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => '' ] );

// Where the first shipped theme puts each knob: the position its file names, the middle for one it does not
$themeKnobs = [];

foreach( Setup::KNOBS as $knobPart => $kinds )
	foreach( $kinds as $knobName => $kind )
		$themeKnobs[$knobPart][$knobName] = Setup::position( $firstTheme['setup']['knobs'][$knobPart][$knobName] ?? 0, $kind );

check( '...every named knob of the seven parts where the first shipped theme puts it, the font roles in the face the wizard delivers and the palette the framework has',
	$setup['knobs'] === $themeKnobs && array_keys( $setup['knobs'] ) === array_keys( Setup::PARTS )
	&& $setup['fonts'] === Fonts::DEFAULTS && $setup['colours'] === Colours::normalize( [] ) && $setup['colours']['mode'] === 'auto' );

// A set of a header that is not the default one, whichever it is
$anotherHeader = array_values( array_diff( Setup::available( $library, 'header' ), [ $defaultSets['header'] ] ) )[0] ?? '';
$notes = [];
$setup = Setup::normalize( $appData, [ 'parts' => [ 'section' => 'nothing-like-it', 'header' => $anotherHeader, 'buttons' => [ 'set' => $defaultSets['buttons'] ], 'atf' => 'first' ] ], $library, $notes );

check( 'a part naming a set the library does not have falls back to the set a fresh install has, and says which - in a note that carries a code and its params, as a refused request answers, besides the English sentence for a log',
	$setup['parts']['section'] === $defaultSets['section'] && $setup['parts']['header'] === $anotherHeader && $anotherHeader !== ''
	&& count( array_filter( $notes, static fn( string|array $note ): bool => is_array( $note ) === true && array_keys( $note ) === [ 'error', 'code', 'params' ] && $note['code'] === 'design_set_missing'
		&& $note['params'] === [ 'section', 'nothing-like-it', $defaultSets['section'] ] && str_contains( $note['error'], '"nothing-like-it"' ) && str_contains( $note['error'], 'using "'. $defaultSets['section']. '"' ) ) ) === 1 );
check( '...a part that is none is dropped and named - in a sentence: only the two notes that the screen says in words carry a code - and a set given in the shape of the model before this one is not mapped: it is the default',
	isset( $setup['parts']['atf'] ) === false && count( array_filter( $notes, static fn( string|array $note ): bool => is_string( $note ) === true && str_contains( $note, 'no part "atf"' ) ) ) === 1
	&& $setup['parts']['buttons'] === $defaultSets['buttons'] && count( $notes ) === 2 );

$notes = [];
$setup = Setup::normalize( $appData, [ 'step' => 'more', 'knobs' => [ 'spacing' => 'more' ], 'future' => true ], $library, $notes );

check( 'what the model before this one stored is dropped and named, and nothing of it is carried: a global position is no part, a step is no key',
	count( $notes ) === 3 && str_contains( implode( ' ', $notes ), '"step"' ) === true && str_contains( implode( ' ', $notes ), '"future"' ) === true
	&& str_contains( implode( ' ', $notes ), 'no part "spacing"' ) === true
	&& isset( $setup['step'] ) === false && $setup['knobs'] === Setup::defaults( $library )['knobs'] );

// A knob the setup leaves out stands where the shipped theme puts it - the whole part left out, or the one knob of a part that names the others
check( 'a knob the setup does not carry stands where the first shipped theme puts it, and not at 0: the knobs left out altogether, a part left out, and one knob left out of a part that names the rest',
	Setup::normalize( $appData, [ 'knobs' => [] ], $library )['knobs'] === $themeKnobs
	&& Setup::normalize( $appData, [ 'knobs' => [ 'card' => [ 'title' => 1 ] ] ], $library )['knobs']['header'] === $themeKnobs['header']
	&& Setup::normalize( $appData, [ 'knobs' => [ 'header' => [ 'nav' => 1 ] ] ], $library )['knobs']['header'] === array_merge( $themeKnobs['header'], [ 'nav' => 1 ] ) );

$empty = ninoSandboxDir( $appData ). '/nothing';
mkdir( $empty, 0755, true );
$notes = [];
$setup = Setup::normalize( $appData, [], $empty, $notes );

check( 'a library with nothing in it answers every part with none, and says so for each',
	array_values( $setup['parts'] ) === array_fill( 0, count( Setup::PARTS ), '' ) && count( $notes ) === count( Setup::PARTS ) );

/*	The knobs of the seven parts, each a scale - five positions, -2 to +2 - but the ground of a frame, the tone and the hover of a card, the hover of the buttons, the marker of a
	list and the zebra of a table, which are choices of three, -1 to +1. The kind is what says how far a knob reaches and by which words it is declared	*/
$scales		= 0;
$choices	= [];
$toggles	= [];

foreach( Setup::KNOBS as $knobPart => $kinds ) {

	$scales += count( array_filter( $kinds, static fn( string $kind ): bool => $kind === 'scale' ) );

	foreach( $kinds as $knobName => $kind ) {

		if( $kind === 'choice' )
			$choices[] = $knobPart. '/'. $knobName;

		if( $kind === 'toggle' )
			$toggles[] = $knobPart. '/'. $knobName;
	}
}

check( 'the knobs of the seven parts, in the order of the model: forty-six, twenty-nine scales, ten choices and seven toggles - the ground, the colour of the line, the position and the button of the header, the ground of the footer, the tone and the hover of a card, the hover of the buttons, the marker of a list and the zebra of a table are the choices; the case of the navigation of the header and of the footer, of the title and the subtitle of a section and of a card and of the buttons the toggles, each after the knobs of its part - the line under the title of a section its rule, and no corners but the buttons\': the radius of the other surfaces is the page\'s, set under Surfaces',
	array_map( 'array_keys', Setup::KNOBS ) === [
		'header' => [ 'nav', 'height', 'space', 'ground', 'border-size', 'border-color', 'position', 'cta', 'nav-case' ],
		'footer' => [ 'scale', 'height', 'space', 'ground', 'nav-case' ],
		'section' => [ 'quiet', 'loud', 'title', 'subtitle', 'space', 'rule', 'dim', 'title-case', 'subtitle-case' ],
		'card' => [ 'title', 'subtitle', 'space', 'border', 'shadow', 'tone', 'hover', 'title-case', 'subtitle-case' ],
		'buttons' => [ 'size', 'spread', 'space', 'corners', 'hover', 'case' ],
		'lists' => [ 'scale', 'space', 'marker' ],
		'tables' => [ 'scale', 'space', 'border', 'zebra', 'stripes' ],
	]
	&& array_keys( Setup::KNOBS ) === array_keys( Setup::PARTS )
	&& $choices === [ 'header/ground', 'header/border-color', 'header/position', 'header/cta', 'footer/ground', 'card/tone', 'card/hover', 'buttons/hover', 'lists/marker', 'tables/zebra' ]
	&& $toggles === [ 'header/nav-case', 'footer/nav-case', 'section/title-case', 'section/subtitle-case', 'card/title-case', 'card/subtitle-case', 'buttons/case' ]
	&& $scales === 29 && array_sum( array_map( 'count', Setup::KNOBS ) ) === 46
	&& Setup::KNOBS['buttons']['corners'] === 'scale' && isset( Setup::KNOBS['section']['corners'], Setup::KNOBS['card']['corners'], Setup::KNOBS['tables']['corners'] ) === false );

/*	A knob is selected on :root by a custom property of its own, --<part>-<knob>, which the rules of the templates read, and the compiler writes one line for each. A custom property
	that the kernel's Nino.css or the library's base.css declares by the same name would be the same property - a selection line over a value of theirs, or a value of theirs that
	the line is written under - so none of the forty-six selection tokens is a name either of them declares. Read from both stylesheets, for every custom property they declare	*/
$declaredByTheirs = [];

foreach( [ $ninoCss, designPlain( (string) file_get_contents( $library. '/base.css' ) ) ] as $theirCss ) {

	preg_match_all( '/(?<![\w-])(--[a-z0-9-]+)\s*:/', $theirCss, $theirDeclared );

	$declaredByTheirs += array_flip( $theirDeclared[1] );
}

$colliding = [];

foreach( Setup::KNOBS as $knobPart => $kinds )
	foreach( array_keys( $kinds ) as $knobName )
		if( isset( $declaredByTheirs[ '--'. $knobPart. '-'. $knobName ] ) === true )
			$colliding[] = '--'. $knobPart. '-'. $knobName;

check( 'no selection token is a custom property that the kernel\'s Nino.css or the library\'s base.css declares - the forty-six names --<part>-<knob> are the knobs\' alone ('. count( $declaredByTheirs ). ' declared by the two)'
	. ( $colliding === [] ? '' : ' - theirs: '. implode( ', ', $colliding ) ), $colliding === [] && count( $declaredByTheirs ) > 100 );

/*	The panel in two axes is drawn from one table. A tab is a property of the design and an object is what the property is set on; Setup::LAYOUT says, for every control of
	the setup, the tab it stands on, the object it is set on and its place in the row of that object, and the screen takes all it knows of where a control stands from the
	table - the list hands it over, and design-js-smoke.js holds the screen to what the list answers. Nothing of it is stored: a setup and a theme file keep every choice by
	its key, as they did. What the table is held to here is the model - it names every key of the setup that a person chooses, once, and no other; each entry names a tab
	and an object the model has; a row counts from 1 without a gap; and the keys that share a place are the ones that are one control	*/
check( 'the setup has six views - Templates, Type, Surfaces, Colours, Motion and CSS - and names neither the themes nor the typefaces, which the pane draws as a screen of its own and as a tab of its own - and its blocks are of eight objects - the page, the header, the footer, a section, a card, the buttons, the lists and the tables - in the order they are shown in',
	Setup::TABS === [ 'templates', 'type', 'surfaces', 'colours', 'motion', 'css' ] && Setup::OBJECTS === [ 'page', 'header', 'footer', 'section', 'card', 'buttons', 'lists', 'tables' ]
	&& array_intersect( [ 'theme', 'typefaces' ], Setup::TABS ) === [] );

// ...and the two text files have a word for each of them: by key, which is what the panel reads, and never by what the word says
$unworded = [];

foreach( [ 'en_US', 'de_DE' ] as $wordedLocale ) {

	$wordedKeys = array_keys( (array) include __DIR__. '/../text/'. $wordedLocale. '.php' );

	foreach( array_merge( array_map( static fn( string $tab ): string => 'tab/'. $tab. '/label', Setup::TABS ), array_map( static fn( string $object ): string => 'object/'. $object. '/label', Setup::OBJECTS ) ) as $wordedKey )
		if( in_array( '[[/_admin/design/'. $wordedKey. ']]', $wordedKeys, true ) === false )
			$unworded[] = $wordedLocale. ' '. $wordedKey;
}

check( '...and both text files have a word for each of the six views and the eight objects, by key'. ( $unworded === [] ? '' : ' - missing: '. implode( ', ', array_slice( $unworded, 0, 4 ) ) ), $unworded === [] && count( Setup::TABS ) + count( Setup::OBJECTS ) === 14 );

$chosen = [];

// The keys of a fresh setup that are a choice of the panel: a key by itself, the entries of a list under the key of the list, the knobs under their part as well. The version of the model, the theme it was taken from, the custom css (the view CSS) and the record of the compile are none
foreach( Setup::defaults( $library ) as $key => $value ) {

	if( in_array( $key, [ 'format', 'theme', 'css', 'compiled' ], true ) === true )
		continue;

	if( is_array( $value ) === false )
		$chosen[] = $key;
	else if( $key === 'knobs' )
		foreach( $value as $part => $knobs )
			foreach( array_keys( $knobs ) as $knob )
				$chosen[] = $key. '/'. $part. '/'. $knob;
	else
		foreach( array_keys( $value ) as $name )
			$chosen[] = $key. '/'. $name;
}

$unplaced = array_values( array_diff( $chosen, array_keys( Setup::LAYOUT ) ) );
$unknown = array_values( array_diff( array_keys( Setup::LAYOUT ), $chosen ) );

check( 'the panel is drawn from a table - Setup::LAYOUT has an entry for every key of the setup that a person chooses, and for no other: the seven templates, the root size, the width, the whitespace, the two radii and the corners of each kind of surface, every knob, the five font roles, the colours, the animation and the hover of the pictures'
	. ( $unplaced === [] && $unknown === [] ? '' : ' - without an entry: '. implode( ', ', array_slice( $unplaced, 0, 4 ) ). ' - naming no key: '. implode( ', ', array_slice( $unknown, 0, 4 ) ) ),
	$unplaced === [] && $unknown === [] && $chosen !== [] && count( $chosen ) === count( array_unique( $chosen ) ) );

$entryProblems = [];
$rows = [];

foreach( Setup::LAYOUT as $key => $place ) {

	if( is_array( $place ) === false || array_keys( $place ) !== [ 0, 1, 2 ] ) {
		$entryProblems[] = $key. ' is no [ tab, object, order ]';
		continue;
	}

	[ $tab, $object, $order ] = $place;

	// The screens of the themes, the view CSS and the loader of the typefaces have no control in the table: the first and the last are no views of the setup, and the view CSS is the one field of the css. Every other entry stands under one of the objects - the templates too, each under the object it is the template of
	if( in_array( $tab, Setup::TABS, true ) === false || $tab === 'css' )
		$entryProblems[] = $key. ' stands on "'. ( is_string( $tab ) === true ? $tab : '?' ). '"';
	else if( in_array( $object, Setup::OBJECTS, true ) === false )
		$entryProblems[] = $key. ' stands under "'. ( is_string( $object ) === true ? $object : '?' ). '"';
	else if( is_int( $order ) === false || $order < 1 )
		$entryProblems[] = $key. ' has the place '. json_encode( $order );
	else
		$rows[ $tab. '/'. $object ][$order][] = $key;
}

// The rows in the order the screen draws them in: the tabs in the order of TABS and under each the objects in the order of OBJECTS, the controls of a row in the order of their places
$laidOut = [];
$gaps = [];

foreach( Setup::TABS as $tab )
	foreach( Setup::OBJECTS as $object ) {

		if( isset( $rows[ $tab. '/'. $object ] ) === false )
			continue;

		ksort( $rows[ $tab. '/'. $object ] );

		if( array_keys( $rows[ $tab. '/'. $object ] ) !== range( 1, count( $rows[ $tab. '/'. $object ] ) ) )
			$gaps[] = $tab. '/'. $object;

		$laidOut[ $tab. '/'. $object ] = array_map( static fn( array $keys ): string => implode( ' ', $keys ), array_values( $rows[ $tab. '/'. $object ] ) );
	}

check( 'every entry of the table names one of the five views that have objects - the sixth, CSS, is the one field of the css and has no control in the table - and one of the eight objects, the templates too, and a row counts its controls from 1 without a gap'
	. ( $entryProblems === [] && $gaps === [] ? '' : ' - '. implode( ', ', array_slice( array_merge( $entryProblems, $gaps ), 0, 4 ) ) ),
	$entryProblems === [] && $gaps === [] && count( $laidOut ) === count( $rows ) );

$shared = [];

foreach( $rows as $row => $orders )
	foreach( $orders as $order => $keys )
		if( count( $keys ) > 1 )
			$shared[ $row. '/'. $order ] = $keys;

check( 'the keys that share a place are the controls that are one: the cover, the row and the column of a section take their corners from one choice, and the second colour is the swatch at the end of the row of the harmony - no other two keys stand together',
	$shared === [ 'surfaces/section/3' => [ 'radii/cover', 'radii/row', 'radii/column' ], 'colours/page/2' => [ 'colours/secondary', 'colours/harmony' ] ]
	&& array_slice( Setup::SURFACES, 0, 3 ) === [ 'cover', 'row', 'column' ] );
check( '...and the table puts every control in the row of its object: each template under the object it is the template of - the header, the footer, the section, the card, the buttons, the lists and the tables, in the order of the objects -; the type and the surfaces row by row - page, header, footer, section, card, buttons, lists and tables - with the sizes of the titles under the object whose titles they are; the colours and the motion with the page, and with the object whose dimming, shadow, stripes or hover they are',
	$laidOut === [
		'templates/header' 			=> [ 'parts/header', 'knobs/header/position', 'knobs/header/cta' ],
		'templates/footer' 			=> [ 'parts/footer' ],
		'templates/section' 		=> [ 'parts/section' ],
		'templates/card' 				=> [ 'parts/card' ],
		'templates/buttons' 		=> [ 'parts/buttons' ],
		'templates/lists' 			=> [ 'parts/lists' ],
		'templates/tables' 			=> [ 'parts/tables' ],
		'type/page' 						=> [ 'size', 'fonts/title', 'fonts/subtitle', 'fonts/text', 'fonts/button', 'fonts/code' ],
		'type/header' 					=> [ 'knobs/header/nav', 'knobs/header/nav-case' ],
		'type/footer' 					=> [ 'knobs/footer/scale', 'knobs/footer/nav-case' ],
		'type/section' 					=> [ 'knobs/section/title', 'knobs/section/subtitle', 'knobs/section/quiet', 'knobs/section/loud', 'knobs/section/title-case', 'knobs/section/subtitle-case' ],
		'type/card' 						=> [ 'knobs/card/title', 'knobs/card/subtitle', 'knobs/card/title-case', 'knobs/card/subtitle-case' ],
		'type/buttons' 					=> [ 'knobs/buttons/size', 'knobs/buttons/spread', 'knobs/buttons/case' ],
		'type/lists' 						=> [ 'knobs/lists/scale' ],
		'type/tables' 					=> [ 'knobs/tables/scale' ],
		'surfaces/page' 				=> [ 'width', 'whitespace', 'radius', 'radius-alt', 'radii/image' ],
		'surfaces/header' 			=> [ 'knobs/header/height', 'knobs/header/space', 'knobs/header/border-size' ],
		'surfaces/footer' 			=> [ 'knobs/footer/height', 'knobs/footer/space' ],
		'surfaces/section' 			=> [ 'knobs/section/space', 'knobs/section/rule', 'radii/cover radii/row radii/column' ],
		'surfaces/card' 				=> [ 'knobs/card/space', 'knobs/card/border', 'knobs/card/shadow', 'radii/card' ],
		'surfaces/buttons' 			=> [ 'knobs/buttons/space', 'knobs/buttons/corners' ],
		'surfaces/lists' 				=> [ 'knobs/lists/space', 'knobs/lists/marker' ],
		'surfaces/tables' 			=> [ 'knobs/tables/space', 'knobs/tables/border', 'knobs/tables/zebra' ],
		'colours/page' 					=> [ 'colours/primary', 'colours/secondary colours/harmony', 'colours/temperature', 'colours/saturation', 'colours/contrast', 'colours/depth', 'colours/mode' ],
		'colours/header' 				=> [ 'knobs/header/ground', 'knobs/header/border-color' ],
		'colours/footer' 				=> [ 'knobs/footer/ground' ],
		'colours/section' 			=> [ 'knobs/section/dim' ],
		'colours/card' 					=> [ 'knobs/card/tone' ],
		'colours/tables' 				=> [ 'knobs/tables/stripes' ],
		'motion/page' 					=> [ 'motion/effect', 'motion/strength', 'motion/speed', 'motion/hover', 'motion/hover-strength' ],
		'motion/card' 					=> [ 'knobs/card/hover' ],
		'motion/buttons' 				=> [ 'knobs/buttons/hover' ],
	] );
check( '...kind() says which - the quiet and the loud variant of a section are a scale each, as the sizes are, the zebra of a table a choice and the case of the buttons a toggle -, and a knob there is none of is of no kind; positions() says where a kind can stand, and words() the words it is declared under - all five for a scale, the inner three for a choice, the middle and the one after it for a toggle',
	Setup::kind( 'section', 'quiet' ) === 'scale' && Setup::kind( 'section', 'loud' ) === 'scale' && Setup::kind( 'buttons', 'corners' ) === 'scale' && Setup::kind( 'buttons', 'size' ) === 'scale' && Setup::kind( 'tables', 'zebra' ) === 'choice'
	&& Setup::kind( 'header', 'ground' ) === 'choice' && Setup::kind( 'footer', 'height' ) === 'scale'
	&& Setup::kind( 'section', 'corners' ) === '' && Setup::kind( 'section', 'nonsense' ) === '' && Setup::kind( 'nowhere', 'corners' ) === ''
	&& Setup::kind( 'buttons', 'case' ) === 'toggle' && Setup::kind( 'header', 'nav-case' ) === 'toggle' && Setup::kind( 'section', 'title' ) === 'scale'
	&& Setup::positions( 'scale' ) === [ -2, -1, 0, 1, 2 ] && Setup::positions( 'choice' ) === [ -1, 0, 1 ] && Setup::positions( 'toggle' ) === [ 0, 1 ] && Setup::positions( 'nothing' ) === [ 0 ]
	&& Setup::words( 'scale' ) === [ 'least', 'less', 'default', 'more', 'most' ] && Setup::words( 'choice' ) === [ 'less', 'default', 'more' ] && Setup::words( 'toggle' ) === [ 'default', 'more' ]
	&& Setup::LADDERS === [ 'scale', 'choice', 'toggle' ] && Setup::FEATURE_KINDS === [ 'scale', 'choice', 'ground', 'ink', 'colour' ] );

/*	Every view that the table draws is drawn as an accordion, the ones with one block as much as the ones with several - design-js-smoke.js holds the screen to that - and the manual says so
	where it is drawn: in the entry of each such view, found by the word the panel draws the view with, a sentence that the entries of all of them have in common, in both languages. What is held
	of it is its shape and not its words, as of every entry: a sentence is what stands between two full stops	*/
$accordionTabs = array_values( array_filter( Setup::TABS, static fn( string $tab ): bool => count( array_filter( array_keys( $laidOut ), static fn( string $row ): bool => str_starts_with( $row, $tab. '/' ) ) ) > 0 ) );
$sentencesOf = static fn( string $entry, string $locale ): array => array_values( array_filter( array_map( 'trim', (array) preg_split( '/(?<=\.)\s+(?=\p{Lu})/u', $says( $entry, $locale ) ) ) ) );
$accordionSaid = [];

foreach( [ 'en_US', 'de_DE' ] as $accordionLocale ) {

	$accordionLists = array_map( static fn( string $tab ): array => $sentencesOf( $panelWord( 'tab/'. $tab. '/label', 'en_US' ), $accordionLocale ), $accordionTabs );
	$accordionSaid[$accordionLocale] = $accordionLists === [] ? [] : array_values( array_intersect( ...$accordionLists ) );
}

check( 'every view the table draws - Templates, Type, Surfaces, Colours and Motion, and no other: CSS is the one field - is told as an accordion in the manual: the entries of all five have a sentence in common, in English and in German',
	$accordionTabs === [ 'templates', 'type', 'surfaces', 'colours', 'motion' ] && array_values( array_diff( Setup::TABS, $accordionTabs ) ) === [ 'css' ] && $accordionSaid['en_US'] !== [] && $accordionSaid['de_DE'] !== [] );

/*	A knob is a position of its own part, held to the range of its kind: -2 to +2 for a scale, -1 to +1 for a choice.
	Whatever else arrives - a word, an array, a number beyond the ends - is the middle, which is the template as it is	*/
$notes = [];
$setup = Setup::normalize( $appData, [ 'knobs' => [
	'section' => [ 'title' => -2, 'subtitle' => '1', 'quiet' => 1.4, 'loud' => 2, 'space' => 'more', 'rule' => null, 'nonsense' => 1 ],
	'card' => [ 'space' => [ 1 ], 'shadow' => true, 'border' => 3 ],
	'tables' => [ 'scale' => -2, 'zebra' => 2, 'stripes' => -1 ],
	'buttons' => 'more',
	'nowhere' => [ 'small' => 1 ],
] ], $library, $notes );

check( 'a knob position of a scale is -2 to +2 and one of a choice -1 to +1: a string that is a number, or a number that rounds to one, is kept - the ends of a scale included',
	$setup['knobs']['section']['title'] === -2 && $setup['knobs']['section']['subtitle'] === 1 && $setup['knobs']['section']['quiet'] === 1
	&& $setup['knobs']['section']['loud'] === 2 && $setup['knobs']['tables']['scale'] === -2 && $setup['knobs']['tables']['stripes'] === -1 );
check( '...and a word, an array, a boolean, nothing and a number past the ends of the kind - 3 for a scale, 2 for a choice - are the middle, not an error',
	$setup['knobs']['section']['space'] === 0 && $setup['knobs']['card']['space'] === 0
	&& $setup['knobs']['section']['rule'] === 0 && $setup['knobs']['card']['shadow'] === 0 && $setup['knobs']['card']['border'] === 0
	&& $setup['knobs']['tables']['zebra'] === 0 && $setup['knobs']['buttons'] === array_fill_keys( array_keys( Setup::KNOBS['buttons'] ), 0 ) );
// The seven toggles: 1 is on, and 0, 2, a word, a boolean and nothing are off - a toggle is normalised through position() like every knob, and one that is not posted stands where the defaults put it, off
$casedNotes = [];
$cased = Setup::normalize( $appData, [ 'knobs' => [
	'header' => [ 'nav-case' => 1 ], 'footer' => [ 'nav-case' => '1' ], 'section' => [ 'title-case' => 1.2, 'subtitle-case' => true ],
	'card' => [ 'title-case' => 2, 'subtitle-case' => 'on' ], 'buttons' => [ 'case' => -1 ],
] ], $library, $casedNotes );

check( 'a toggle posted as 1, as "1" or as 1.2 is on; as 2, -1, true or "on" it is off, and one that is not posted is off - the seven stand at 0 in a setup that names none, and the shipped theme names none of them',
	$cased['knobs']['header']['nav-case'] === 1 && $cased['knobs']['footer']['nav-case'] === 1 && $cased['knobs']['section']['title-case'] === 1
	&& $cased['knobs']['section']['subtitle-case'] === 0 && $cased['knobs']['card']['title-case'] === 0 && $cased['knobs']['card']['subtitle-case'] === 0 && $cased['knobs']['buttons']['case'] === 0
	&& $casedNotes === [] && array_filter( $toggles, static fn( string $at ): bool => Setup::normalize( $appData, [], $library )['knobs'][ explode( '/', $at )[0] ][ explode( '/', $at )[1] ] !== 0
		|| Setup::defaults( $library )['knobs'][ explode( '/', $at )[0] ][ explode( '/', $at )[1] ] !== 0
		|| array_key_exists( explode( '/', $at )[1], $firstTheme['setup']['knobs'][ explode( '/', $at )[0] ] ) === true ) === [] );

check( '...a knob that is none of its part\'s and a part that is none of the seven are dropped and named, never carried',
	array_keys( $setup['knobs']['section'] ) === array_keys( Setup::KNOBS['section'] ) && isset( $setup['knobs']['nowhere'] ) === false
	&& count( $notes ) === 2 && str_contains( implode( ' ', $notes ), 'no knob "nonsense"' ) === true && str_contains( implode( ' ', $notes ), 'no part "nowhere"' ) === true );

/*	The parts of the model before this one - the content, which is the section and the card now, and the components, which are the tables - are no parts of it, and no
	setup or theme file that names them is read as naming another: the part is dropped and named, nothing of it is carried to a part that has the knob by the same name, and what the file
	says of the parts it still has is kept. And the knob of a part it has none of - the corners of a section went to the page, see the radius below - is read the same way	*/
$gone		= [ 'content', 'components' ];
$notes	= [];
$old		= Setup::normalize( $appData, [ 'knobs' => [ 'content' => [ 'subtitle' => 2, 'border' => 1 ], 'components' => [ 'zebra' => 1 ], 'section' => [ 'corners' => 2, 'title' => 1 ] ] ], $library, $notes );

check( 'a setup that names a part of the model before this one is read as it reads any part it has none of: the part is dropped and named, no knob of it reaches a part of this one - the border of a card and the zebra of a table stay in the middle - and the knob of a part that it has none of is dropped and named, with the others of the part kept',
	array_intersect( $gone, array_keys( Setup::PARTS ) ) === [] && isset( $old['knobs'][ $gone[0] ] ) === false && isset( $old['knobs'][ $gone[1] ] ) === false
	&& $old['knobs']['card']['border'] === 0 && $old['knobs']['tables']['zebra'] === 0 && $old['knobs']['section']['subtitle'] === 0
	&& isset( $old['knobs']['section']['corners'] ) === false && $old['knobs']['section']['title'] === 1
	&& count( $notes ) === 3 && str_contains( implode( ' ', $notes ), 'no part "'. $gone[0]. '"' ) === true && str_contains( implode( ' ', $notes ), 'no part "'. $gone[1]. '"' ) === true
	&& str_contains( implode( ' ', $notes ), 'the part "section" has no knob "corners"' ) === true );

/*	Setup::position( $value, $kind ): a number inside the range of the kind, rounded - and anything else, a number past the
	ends included, the middle. The default kind is a scale, which is what the root size and the width of the page are as well	*/
$positions = [
	[ -2, 'scale', -2 ], [ -1, 'scale', -1 ], [ 0, 'scale', 0 ], [ 1, 'scale', 1 ], [ 2, 'scale', 2 ], [ '-2', 'scale', -2 ], [ '2', 'scale', 2 ],
	[ -0.4, 'scale', 0 ], [ 0.6, 'scale', 1 ], [ 1.6, 'scale', 2 ], [ -1.4, 'scale', -1 ], [ -2.4, 'scale', -2 ],
	[ 3, 'scale', 0 ], [ -3, 'scale', 0 ], [ 99, 'scale', 0 ], [ 2.6, 'scale', 0 ],
	[ 'x', 'scale', 0 ], [ 'more', 'scale', 0 ], [ '', 'scale', 0 ], [ null, 'scale', 0 ], [ true, 'scale', 0 ], [ [ 1 ], 'scale', 0 ],
	[ -1, 'choice', -1 ], [ 0, 'choice', 0 ], [ 1, 'choice', 1 ], [ '1', 'choice', 1 ], [ 0.6, 'choice', 1 ],
	[ 2, 'choice', 0 ], [ -2, 'choice', 0 ], [ 3, 'choice', 0 ], [ 1.6, 'choice', 0 ], [ 'more', 'choice', 0 ], [ null, 'choice', 0 ],
	// A toggle is off at 0 and on at 1, whatever else it is handed: a switch is a number like every knob, and neither true nor 'on' is read
	[ 0, 'toggle', 0 ], [ 1, 'toggle', 1 ], [ '0', 'toggle', 0 ], [ '1', 'toggle', 1 ], [ 1.0, 'toggle', 1 ], [ 0.4, 'toggle', 0 ], [ 0.6, 'toggle', 1 ], [ -0.4, 'toggle', 0 ],
	[ -1, 'toggle', 0 ], [ 2, 'toggle', 0 ], [ 1.6, 'toggle', 0 ], [ 99, 'toggle', 0 ], [ '2', 'toggle', 0 ],
	[ true, 'toggle', 0 ], [ false, 'toggle', 0 ], [ 'on', 'toggle', 0 ], [ 'more', 'toggle', 0 ], [ 'off', 'toggle', 0 ], [ '', 'toggle', 0 ], [ null, 'toggle', 0 ], [ [ 1 ], 'toggle', 0 ], [ NAN, 'toggle', 0 ],
];
$wrong = array_filter( $positions, static fn( array $row ): bool => Setup::position( $row[0], $row[1] ) !== $row[2] );

check( 'the table of positions: '. count( $positions ). ' values, each the position it should be'. ( $wrong === [] ? '' : ' - wrong: '. json_encode( array_values( $wrong ) ) ), $wrong === [] );
check( '...a toggle is 0 or 1 for any input - never a third thing, and the fallback a caller names is the answer for what is none: 1 for a toggle that stands on where nothing is chosen',
	array_filter( [ 0, 1, 2, -1, 0.5, 1.5, 'on', true, null, [ 1 ], 'x', 1e9, NAN, '1', ' 1' ], static fn( mixed $value ): bool => in_array( Setup::position( $value, 'toggle' ), [ 0, 1 ], true ) === false ) === []
	&& Setup::position( 5, 'toggle', 1 ) === 1 && Setup::position( 'on', 'toggle', 1 ) === 1 && Setup::position( 0, 'toggle', 1 ) === 0 && Setup::position( 1, 'toggle', 0 ) === 1 );
check( '...and with no kind it is a scale: 2 and -2 are positions, 3 is none',
	Setup::position( 2 ) === 2 && Setup::position( -2 ) === -2 && Setup::position( 3 ) === 0 && Setup::position( 'more' ) === 0 );
check( 'the model keeps numbers and the library keeps words, and word() is the one place that knows both - five words, the middle one at 0, and the end for a number past it',
	[ Setup::word( -2 ), Setup::word( -1 ), Setup::word( 0 ), Setup::word( 1 ), Setup::word( 2 ), Setup::word( 7 ), Setup::word( -7 ) ] === [ 'least', 'less', 'default', 'more', 'most', 'most', 'least' ]
	&& Setup::STEPS === [ 'least', 'less', 'default', 'more', 'most' ] );

$setup = Setup::normalize( $appData, [ 'knobs' => [ 'buttons' => [ 'corners' => 2, 'case' => 1 ], 'card' => [ 'border' => -2, 'shadow' => 1, 'title-case' => 2 ], 'tables' => [ 'zebra' => 1, 'stripes' => -1 ] ] ], $library );

check( 'step() says where a knob stands, as the word its stylesheet declares it under - one of five for a scale, one of the inner three for a choice',
	Setup::step( $setup, 'buttons', 'corners' ) === 'most' && Setup::step( $setup, 'card', 'border' ) === 'least'
	&& Setup::step( $setup, 'card', 'shadow' ) === 'more' && Setup::step( $setup, 'section', 'loud' ) === 'default'
	&& Setup::step( $setup, 'tables', 'zebra' ) === 'more' && Setup::step( $setup, 'tables', 'stripes' ) === 'less'
	&& Setup::step( $setup, 'buttons', 'nothing' ) === 'default' && Setup::step( $setup, 'nowhere', 'x' ) === 'default'
	&& Setup::step( $setup, 'buttons', 'case' ) === 'more' && Setup::step( $setup, 'card', 'title-case' ) === 'default' && Setup::step( $setup, 'card', 'subtitle-case' ) === 'default'
	&& Setup::step( [ 'knobs' => [ 'buttons' => [ 'case' => 1 ] ] ], 'buttons', 'case' ) === 'more' && Setup::step( [ 'knobs' => [ 'buttons' => [ 'case' => 2 ] ] ], 'buttons', 'case' ) === 'default' );
check( '...and passes the kind of the knob on, so that a choice at 2, which a setup that was not normalised may hold, is the middle and not the fifth word',
	Setup::step( [ 'knobs' => [ 'tables' => [ 'zebra' => 2, 'scale' => 2 ] ] ], 'tables', 'zebra' ) === 'default'
	&& Setup::step( [ 'knobs' => [ 'tables' => [ 'zebra' => 2, 'scale' => 2 ] ] ], 'tables', 'scale' ) === 'most' );

// The root size, the width, the whitespace and the animation of the page
$setup = Setup::normalize( $appData, [ 'size' => 1, 'width' => '2', 'whitespace' => '-2', 'motion' => [ 'effect' => 'flip', 'strength' => 'hard', 'speed' => 'slow', 'hover' => 'zoom-bright', 'hover-strength' => 'soft' ] ], $library );

check( 'the size, the width, the whitespace, the animation and the hover of the pictures are kept as they were chosen', $setup['size'] === 1 && $setup['width'] === 2 && $setup['whitespace'] === -2
	&& $setup['motion'] === [ 'effect' => 'flip', 'strength' => 'hard', 'speed' => 'slow', 'hover' => 'zoom-bright', 'hover-strength' => 'soft' ] );
check( '...every kind of hover and every strength of it is kept, one by one, and the strength is kept beside no kind as the strength of an effect is kept beside no effect - it waits in the setup; a kind with no strength has none',
	array_filter( array_keys( Setup::HOVERS ), static fn( string $kind ): bool => Setup::normalize( $appData, [ 'motion' => [ 'hover' => $kind ] ], $library )['motion']['hover'] !== $kind ) === []
	&& array_filter( Setup::STRENGTHS, static fn( string $strength ): bool => Setup::normalize( $appData, [ 'motion' => [ 'hover' => 'bright', 'hover-strength' => $strength ] ], $library )['motion']['hover-strength'] !== $strength ) === []
	&& Setup::normalize( $appData, [ 'motion' => [ 'hover-strength' => 'hard' ] ], $library )['motion'] === [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => 'hard' ]
	&& Setup::normalize( $appData, [ 'motion' => [ 'hover' => 'none' ] ], $library )['motion']['hover-strength'] === '' );
check( '...width -2, -1, 0, 1 and 2 are the five widths', array_map( static fn( int $width ): int => Setup::normalize( $appData, [ 'width' => $width ], $library )['width'], [ -2, -1, 0, 1, 2 ] ) === [ -2, -1, 0, 1, 2 ] );
check( '...whitespace -2, -1, 0, 1 and 2 are the five positions of the air, Setup::WHITESPACE is keyed by them with the number the kernel\'s --whitespace-scale is set to, and the middle one is what a setup out of nothing has',
	array_map( static fn( int $air ): int => Setup::normalize( $appData, [ 'whitespace' => $air ], $library )['whitespace'], [ -2, -1, 0, 1, 2 ] ) === [ -2, -1, 0, 1, 2 ]
	&& Setup::WHITESPACE === [ -2 => '.75', -1 => '.9', 0 => '1', 1 => '1.5', 2 => '2' ] && Setup::WHITESPACE[0] === '1'
	&& Setup::normalize( $appData, [], $library )['whitespace'] === 0 );
check( '...the size is one of five, -2 to +2, each kept as it was chosen - and the middle one is what a setup out of nothing has; Setup::SIZES is keyed by them, with the five pairs',
	Setup::SIZES === [ -2 => [ '85%', '95.625%' ], -1 => [ '92.5%', '104.0625%' ], 0 => [ '100%', '112.5%' ], 1 => [ '115%', '129.375%' ], 2 => [ '145%', '163.125%' ] ]
	&& array_map( static fn( int $size ): int => Setup::normalize( $appData, [ 'size' => $size ], $library )['size'], array_keys( Setup::SIZES ) ) === [ -2, -1, 0, 1, 2 ]
	&& Setup::normalize( $appData, [], $library )['size'] === 0 );

$notes = [];
$setup = Setup::normalize( $appData, [ 'size' => 3, 'width' => 3, 'whitespace' => 3, 'motion' => [ 'effect' => 'spin', 'strength' => 'loud', 'speed' => 'warp', 'hover' => 'lift', 'hover-strength' => 'loud', 'delay' => 1 ] ], $library, $notes );

check( 'a size, a width, a whitespace, an effect, a strength, a speed, a kind of hover or a strength of it that is none of the choices is the default, not an error - 3 is no size, width or whitespace, and neither is a word: the xs to xl of the model before are mapped to nothing',
	$setup['size'] === 0 && $setup['width'] === 0 && $setup['whitespace'] === 0 && $setup['motion'] === [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => '' ]
	&& array_filter( [ 'XL', 'xxl', 'xxs', 'xs', 's', 'm', 'l', 'xl', 'medium', '', ' s' ], static fn( string $size ): bool => Setup::normalize( $appData, [ 'size' => $size, 'width' => $size, 'whitespace' => $size ], $library )['size'] !== 0 ) === []
	&& array_filter( [ 3, -3, 2.6, -2.6, 99 ], static fn( int|float $size ): bool => Setup::normalize( $appData, [ 'size' => $size, 'width' => $size, 'whitespace' => $size ], $library )['size'] !== 0 || Setup::normalize( $appData, [ 'width' => $size ], $library )['width'] !== 0 || Setup::normalize( $appData, [ 'whitespace' => $size ], $library )['whitespace'] !== 0 ) === [] );
check( '...and a key of the motion that is none of its five is dropped and named - the value that is none of the choices is not', count( $notes ) === 1 && str_contains( $notes[0], '"delay"' ) === true );

/*	The radius of the surfaces: two strengths, the standard one and the alternative one, each a position of the one table Setup::RADIUS, and for each kind of
	surface the word of the strength it takes. A setup out of nothing stands where Nino.css does - the standard strength at the kernel's small radius, the alternative
	at its large one, every kind on the standard - and what is none of the positions is where the key starts from: the alternative strength is not the middle of its own	*/
$setup = Setup::normalize( $appData, [ 'radius' => '-2', 'radius-alt' => 2, 'radii' => [ 'cover' => 'alternative', 'card' => 'alternative' ] ], $library );
$fresh = Setup::normalize( $appData, [], $library );

check( 'the radius is two positions of one table, the standard strength and the alternative one: -2 to +2 each, kept as they were chosen, and Setup::RADIUS is keyed by them with the length at each - 0 at the sharp end, .1rem in the middle, .4rem at +1',
	array_map( static fn( int $step ): int => Setup::normalize( $appData, [ 'radius' => $step ], $library )['radius'], [ -2, -1, 0, 1, 2 ] ) === [ -2, -1, 0, 1, 2 ]
	&& array_map( static fn( int $step ): int => Setup::normalize( $appData, [ 'radius-alt' => $step ], $library )['radius-alt'], [ -2, -1, 0, 1, 2 ] ) === [ -2, -1, 0, 1, 2 ]
	&& Setup::RADIUS === [ -2 => '0rem', -1 => '.05rem', 0 => '.1rem', 1 => '.4rem', 2 => '1rem' ]
	&& $setup['radius'] === -2 && $setup['radius-alt'] === 2 );
check( '...a setup out of nothing stands at 0 and +1, and a position that is none - 3, a word, an array, nothing - is where its key starts from: 0 for the standard strength and +1 for the alternative one, never the middle of that',
	$fresh['radius'] === 0 && $fresh['radius-alt'] === 1 && Setup::defaults( $library )['radius'] === 0 && Setup::defaults( $library )['radius-alt'] === 1
	&& array_filter( [ 3, -3, 99, 2.6, 'more', '', [ 1 ], null, true ], static fn( mixed $bad ): bool => Setup::normalize( $appData, [ 'radius' => $bad, 'radius-alt' => $bad ], $library )['radius'] !== 0
		|| Setup::normalize( $appData, [ 'radius' => $bad, 'radius-alt' => $bad ], $library )['radius-alt'] !== 1 ) === [] );

$notes = [];
$kinds = Setup::normalize( $appData, [ 'radii' => [ 'image' => 'alternative', 'card' => 'alternative', 'row' => 'third', 'column' => 1, 'cover' => [ 'alternative' ], 'sixth' => 'alternative' ] ], $library, $notes );

check( 'the kinds of surface are the five of Setup::SURFACES - the cover, the row, the column, the card and the image - each the word standard or alternative, standard until it is told otherwise, and always all five, in that order, whatever came in',
	Setup::SURFACES === [ 'cover', 'row', 'column', 'card', 'image' ] && Setup::RADIUS_WORDS === [ 'standard', 'alternative' ]
	&& array_keys( $kinds['radii'] ) === Setup::SURFACES && array_keys( $setup['radii'] ) === Setup::SURFACES
	&& $setup['radii'] === [ 'cover' => 'alternative', 'row' => 'standard', 'column' => 'standard', 'card' => 'alternative', 'image' => 'standard' ]
	&& $fresh['radii'] === array_fill_keys( Setup::SURFACES, 'standard' ) && Setup::defaults( $library )['radii'] === $fresh['radii'] );
check( '...a third word, a number and an array are the standard one, a sixth kind is dropped and named, and posting the kinds as anything but a list is all of them standard',
	$kinds['radii'] === [ 'cover' => 'standard', 'row' => 'standard', 'column' => 'standard', 'card' => 'alternative', 'image' => 'alternative' ]
	&& count( $notes ) === 1 && str_contains( $notes[0], '"sixth"' ) === true
	&& array_filter( [ 'card', 1, null, true ], static fn( mixed $bad ): bool => Setup::normalize( $appData, [ 'radii' => $bad ], $library )['radii'] !== $fresh['radii'] ) === []
	&& Setup::radiusOf( $kinds, 'card' ) === 'alternative' && Setup::radiusOf( $kinds, 'row' ) === 'standard' && Setup::radiusOf( [], 'card' ) === 'standard' );

// None of what comes off the wire may raise: an array cast to a string is a warning the kernel treats as fatal
ninoWarnings();
$setup = Setup::normalize( $appData, [ 'theme' => [ Themes::SHIPPED[0] ], 'size' => [ 'l' ], 'width' => [ 1 ], 'whitespace' => [ 1 ], 'radius' => [ 1 ], 'radius-alt' => [ 1 ], 'radii' => 'card', 'motion' => 'flip', 'parts' => 'first', 'knobs' => 'more', 'fonts' => 'bold', 'colours' => 'dark', 'css' => [ 'a {}' ], 'compiled' => 'yes' ], $library );

check( 'every key of the model, posted as the wrong type, is its default and raises nothing', ninoWarnings() === []
	&& array_diff_key( $setup, [ 'compiled' => 1 ] ) === array_diff_key( Setup::defaults( $library ), [ 'compiled' => 1 ] ) && $setup['compiled'] === [] );
check( '...and the record of what was compiled is carried when it is one', Setup::normalize( $appData, [ 'compiled' => [ 'at' => 'then' ] ], $library )['compiled'] === [ 'at' => 'then' ] );

// The five font roles
$notes = [];
$setup = Setup::normalize( $appData, [ 'fonts' => [
	'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'uppercase' => true, 'size' => '2.40' ],
	'subtitle' => [ 'family' => 'nowhere', 'weight' => 'heavy', 'size' => 'big', 'colour' => 'red' ],
	'text' => [ 'family' => '', 'size' => 1.125 ],
	'code' => [ 'family' => 'system-mono', 'weight' => 'bold', 'size' => '0.9' ],
	'button' => 'bold',
	'heading' => [ 'family' => 'system-sans' ],
] ], $library, $notes );

check( 'a font role keeps a family its select offers, a weight word and a size that is a number inside its bounds - spelled 2.40 it is 2.4 - and nothing else: its three keys, in this order',
	$setup['fonts']['title'] === [ 'family' => 'system-serif', 'weight' => 'light', 'size' => '2.4' ]
	&& $setup['fonts']['text'] === [ 'family' => '', 'weight' => 'normal', 'size' => '1.125' ]
	&& array_filter( $setup['fonts'], static fn( array $role ): bool => array_keys( $role ) !== [ 'family', 'weight', 'size' ] ) === []
	&& array_filter( Fonts::DEFAULTS, static fn( array $role ): bool => array_keys( $role ) !== [ 'family', 'weight', 'size' ] ) === [] );
check( '...a family, a weight or a size that is none starts from what the role has',
	$setup['fonts']['subtitle'] === Fonts::DEFAULTS['subtitle'] && $setup['fonts']['button'] === Fonts::DEFAULTS['button']
	&& $setup['fonts']['code'] === [ 'family' => '', 'weight' => 'bold', 'size' => '0.9' ] );
check( '...a size outside the bounds of its role is held at the nearer bound, and one with more than three decimals is rounded to three: the setup keeps what the compiler can write',
	array_map( static fn( string $role ): string => Setup::normalize( $appData, [ 'fonts' => [ $role => [ 'size' => 99 ] ] ], $library )['fonts'][$role]['size'], Fonts::ROLES ) === [ '4', '2.5', '1.5', '1.5', '1.3' ]
	&& array_map( static fn( string $role ): string => Setup::normalize( $appData, [ 'fonts' => [ $role => [ 'size' => '0.1' ] ] ], $library )['fonts'][$role]['size'], Fonts::ROLES ) === [ '1', '0.8', '0.8', '0.6', '0.6' ]
	&& Setup::normalize( $appData, [ 'fonts' => [ 'text' => [ 'size' => '1.23456' ] ] ], $library )['fonts']['text']['size'] === '1.235'
	&& Setup::normalize( $appData, [ 'fonts' => [ 'text' => [ 'size' => [ 1 ] ] ] ], $library )['fonts']['text']['size'] === '' );
check( '...and a family the role\'s select does not offer is none: the code is given the monospace ones, of which there are none with files yet - the system stack is its empty family - while the other roles keep a system stack',
	$setup['fonts']['code']['family'] === '' && Setup::normalize( $appData, [ 'fonts' => [ 'code' => [ 'family' => 'open-sans' ] ] ], $library )['fonts']['code']['family'] === ''
	&& Setup::normalize( $appData, [ 'fonts' => [ 'title' => [ 'family' => 'system-mono' ] ] ], $library )['fonts']['title']['family'] === 'system-mono'
	&& array_filter( Fonts::ROLES, static fn( string $role ): bool => in_array( Setup::normalize( $appData, [ 'fonts' => [ $role => [ 'family' => 'open-sans' ] ] ], $library )['fonts'][$role]['family'], Fonts::options( $appData, $role ), true ) === false ) === [] );
check( '...and a role that is none, or a key of a role that is none of its three - the colour, and the uppercase a stored setup of the time the roles had a case still carries - is dropped and named',
	count( $notes ) === 3 && str_contains( implode( ' ', $notes ), 'no font role "heading"' ) === true && str_contains( implode( ' ', $notes ), '"colour"' ) === true
	&& in_array( 'the font role "title" has no "uppercase" - dropped', $notes, true ) === true
	&& array_keys( $setup['fonts'] ) === Fonts::ROLES );

// The palette is the Colours class's to normalise, the mode with it
$setup = Setup::normalize( $appData, [ 'colours' => [ 'primary' => '#8b1d3f', 'mode' => 'dark', 'contrast' => 5 ] ], $library );

check( 'the palette is Colours\' to normalise, and the mode is one of its words', $setup['colours'] === Colours::normalize( [ 'primary' => '#8b1d3f', 'mode' => 'dark', 'contrast' => 5 ] )
	&& $setup['colours']['mode'] === 'dark' && $setup['colours']['contrast'] === 5 );
check( '...a scale of the palette has five positions, 1 to 5, the third the framework\'s own, and a choice as many as it has names; a position past them is the default of the knob',
	Colours::normalize( [ 'saturation' => 5, 'contrast' => 1, 'depth' => 4 ] )['saturation'] === 5 && Colours::normalize( [ 'saturation' => 5, 'contrast' => 1, 'depth' => 4 ] )['contrast'] === 1
	&& Colours::normalize( [ 'depth' => 4 ] )['depth'] === 4 && Colours::normalize( [ 'harmony' => 4, 'temperature' => 4 ] )['harmony'] === 4
	&& Colours::normalize( [ 'saturation' => 6, 'contrast' => 0, 'depth' => -1, 'harmony' => 5, 'temperature' => 5 ] ) === Colours::normalize( [] )
	&& Colours::normalize( [] )['saturation'] === 3 && Colours::normalize( [] )['contrast'] === 3 && Colours::normalize( [] )['depth'] === 3 );
check( '...a mode that is none of the three, or not a word at all, is Auto',
	Setup::normalize( $appData, [ 'colours' => [ 'mode' => 'twilight' ] ], $library )['colours']['mode'] === 'auto'
	&& Setup::normalize( $appData, [ 'colours' => [ 'mode' => [ 'dark' ] ] ], $library )['colours']['mode'] === 'auto'
	&& Colours::MODES === [ 'auto', 'light', 'dark' ] );

/*	The first version that was used is this one: a file of another format is
	read as the defaults, and the note says so. Nothing maps what the older
	shape chose onto this one - and nothing maps what Design 2.0.0 kept in the same
	format either, since there are no stored setups of it anywhere: the words of its
	size are no numbers, a knob it had is dropped with the note, and a position of the
	palette is read as the position of five it now is	*/
$notes = [];
$former = Setup::normalize( $appData, [ 'size' => 'm', 'knobs' => [ 'buttons' => [ 'small' => 1 ] ], 'colours' => [ 'contrast' => 2 ] ], $library, $notes );

check( 'a setup written by Design 2.0.0 is read by the rules of this version and mapped to nothing - no stored setup of it exists: its size "m" is the middle, its buttons knob "small" is dropped and named, and its contrast 2 is position 2 of 5 now, kept as that',
	$former['size'] === 0 && isset( $former['knobs']['buttons']['small'] ) === false && $former['knobs']['buttons'] === array_fill_keys( array_keys( Setup::KNOBS['buttons'] ), 0 )
	&& count( $notes ) === 1 && str_contains( $notes[0], 'the part "buttons" has no knob "small"' ) === true && $former['colours']['contrast'] === 2 );

$setupFile = \Nino\Filesystem::path( $appData, Setup::PATH );

\Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'format' => 1, 'size' => 'l', 'parts' => [ 'header' => [ 'set' => 'first', 'knobs' => [] ] ], 'knobs' => [ 'spacing' => 'more' ] ] );
$notes = [];
$read = Setup::read( $appData, $library, $notes );

check( 'a file written in another format is read as the defaults, and the note says it is not read and the defaults stand in until the next save', $read === Setup::normalize( $appData, [], $library )
	&& count( $notes ) === 1 && str_contains( $notes[0], 'another format' ) === true && str_contains( $notes[0], 'data/design.php' ) === true
	&& str_contains( $notes[0], 'it is not read' ) === true && str_contains( $notes[0], 'until the next save' ) === true );

\Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'size' => 'l' ] );
$notes = [];

check( '...so is one that names no format at all', Setup::read( $appData, $library, $notes ) === Setup::normalize( $appData, [], $library ) && count( $notes ) === 1 );

$written = Setup::normalize( $appData, [ 'size' => 1, 'width' => -2, 'whitespace' => 1, 'radius' => -1, 'radius-alt' => 2, 'radii' => [ 'image' => 'alternative' ],
	'parts' => [ 'header' => $anotherHeader ], 'knobs' => [ 'buttons' => [ 'space' => 2 ] ] ], $library );
$notes = [];

check( 'a file in this format is read as it was written, without a note', Setup::write( $appData, $written ) === true
	&& Setup::read( $appData, $library, $notes ) === $written && $notes === []
	&& ( \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] )['format'] ?? 0 ) === Setup::FORMAT );

@unlink( $setupFile );
$notes = [];

check( 'and with no file at all it is the defaults, without a note', Setup::read( $appData, $library, $notes ) === Setup::normalize( $appData, [], $library ) && $notes === [] );

echo "\nThe fonts: what each role can be, and what is written for it\n";

$sans = Fonts::STACK_SANS;
$mono = Fonts::STACK_MONO;

check( 'the five roles, in the order they are shown and written in', Fonts::ROLES === [ 'title', 'subtitle', 'text', 'button', 'code' ]
	&& array_keys( Fonts::DEFAULTS ) === Fonts::ROLES && array_keys( Fonts::BOUNDS ) === Fonts::ROLES && array_keys( Fonts::INHERITED ) === Fonts::ROLES );
check( 'a family is a name, a category and the files it comes in - the three system stacks have none',
	array_keys( Fonts::families( $appData ) ) === [ 'open-sans', 'system-sans', 'system-serif', 'system-mono' ]
	&& array_filter( Fonts::families( $appData ), static fn( array $family ): bool => isset( $family['name'], $family['category'], $family['weights'], $family['files'] ) === false ) === []
	&& array_keys( array_filter( Fonts::families( $appData ), static fn( array $family ): bool => $family['files'] === [] ) ) === [ 'system-sans', 'system-serif', 'system-mono' ] );
check( 'each role is offered the families that suit it, the empty one first - the code only a monospace that has files, which no system stack has',
	Fonts::options( $appData, 'title' ) === [ '', 'open-sans', 'system-sans', 'system-serif', 'system-mono' ]
	&& Fonts::options( $appData, 'text' ) === Fonts::options( $appData, 'title' ) && Fonts::options( $appData, 'code' ) === [ '' ] );
check( 'a webface is its name in quotes and then the stack of its role, a system family its own stack, and no family nothing',
	Fonts::line( $appData, 'title', 'open-sans' ) === '"Open Sans", '. $sans && Fonts::line( $appData, 'code', 'open-sans' ) === '"Open Sans", '. $mono
	&& Fonts::line( $appData, 'text', 'system-mono' ) === $mono && Fonts::line( $appData, 'text', 'system-serif' ) === 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif'
	&& Fonts::line( $appData, 'text', '' ) === '' && Fonts::line( $appData, 'text', 'nowhere' ) === '' );
check( 'a weight word is the weight a family has nearest to it - the Open Sans, which has 300, 400 and 700, gives light 300, normal 400 and bold 700 - and as a keyword where it has none to choose from',
	[ Fonts::weight( $appData, 'open-sans', 'light' ), Fonts::weight( $appData, 'open-sans', 'normal' ), Fonts::weight( $appData, 'open-sans', 'bold' ) ] === [ '300', '400', '700' ]
	&& [ Fonts::weight( $appData, 'system-sans', 'light' ), Fonts::weight( $appData, 'system-sans', 'normal' ), Fonts::weight( $appData, '', 'bold' ), Fonts::weight( $appData, '', 'heavy' ) ] === [ '300', 'normal', 'bold', 'normal' ] );

// No family has a tie today - a webface that brings 400 and 600 would - so the choice is held where it can be: of two weights as near as each other the lighter wins
$nearest = new ReflectionMethod( Fonts::class, 'nearest' );
check( 'of two weights as near as each other a word gets the lighter - the list is sorted first, and only a nearer weight takes the place of the one held',
	$nearest->invoke( null, [ 400, 600 ], 500 ) === 400 && $nearest->invoke( null, [ 700, 300 ], 500 ) === 300 && $nearest->invoke( null, [ 300, 400, 500, 700, 900 ], 800 ) === 700
	&& $nearest->invoke( null, [ 400, 700 ], 500 ) === 400 && $nearest->invoke( null, [ 400, 700 ], 800 ) === 700 && $nearest->invoke( null, [ 500 ], 300 ) === 500 );
check( 'every role has bounds in rem, from the least to the most: the five of them, in the order of the roles',
	array_keys( Fonts::BOUNDS ) === Fonts::ROLES && array_filter( Fonts::BOUNDS, static fn( array $bounds ): bool => count( $bounds ) !== 2 || $bounds[0] <= 0 || $bounds[0] >= $bounds[1] ) === []
	&& Fonts::BOUNDS === [ 'title' => [ 1, 4 ], 'subtitle' => [ 0.8, 2.5 ], 'text' => [ 0.8, 1.5 ], 'button' => [ 0.6, 1.5 ], 'code' => [ 0.6, 1.3 ] ] );
/*	The field a size is typed in steps by a tenth from its least, so a bound that is not
	a number of tenths is a size the spinner cannot reach and the browser calls invalid:
	every bound is on the step, and the model keeps no less than that	*/
check( '...and every bound is a number of tenths, the step of the field the size is typed in - the least and the most of each role',
	array_filter( Fonts::BOUNDS, static fn( array $bounds ): bool => array_filter( $bounds, static fn( int|float $bound ): bool => abs( $bound * 10 - round( $bound * 10 ) ) > 1e-9 ) !== [] ) === [] );
check( 'a size is a number inside the bounds of its role as the shortest string it can be written as: 2.4 however it is spelled, 1.1250 as 1.125, 2.0 as 2',
	Fonts::size( 'title', '2.4' ) === '2.4' && Fonts::size( 'title', 2.4 ) === '2.4' && Fonts::size( 'title', '2.40' ) === '2.4' && Fonts::size( 'title', 2 ) === '2'
	&& Fonts::size( 'title', '1.1250' ) === '1.125' && Fonts::size( 'title', '2.0' ) === '2' && Fonts::size( 'text', 0.875 ) === '0.875' && Fonts::size( 'title', '  1.5 ' ) === '1.5' );
check( '...a value is held at the nearer bound - below the least it is the least, above the most the most - and rounded to three decimals, which is what a number that is no step of any list is',
	Fonts::size( 'title', 0.5 ) === '1' && Fonts::size( 'title', '9' ) === '4' && Fonts::size( 'title', -3 ) === '1' && Fonts::size( 'title', 1e9 ) === '4'
	&& Fonts::size( 'subtitle', 0.7 ) === '0.8' && Fonts::size( 'subtitle', 2.51 ) === '2.5' && Fonts::size( 'button', 0.59 ) === '0.6' && Fonts::size( 'code', 1.31 ) === '1.3'
	&& Fonts::size( 'title', '2.45' ) === '2.45' && Fonts::size( 'code', '0.9375' ) === '0.938' && Fonts::size( 'title', 1.00049 ) === '1' );
check( '...the bounds themselves are sizes: .8 and 2.5 for the subtitle, .8 and 1.5 for the text, .6 and 1.5 for the button, .6 and 1.3 for the code - and .59 is held at .6, 1.31 at 1.3',
	Fonts::size( 'subtitle', .8 ) === '0.8' && Fonts::size( 'subtitle', 2.5 ) === '2.5' && Fonts::size( 'text', '.8' ) === '0.8' && Fonts::size( 'text', 1.5 ) === '1.5'
	&& Fonts::size( 'button', .6 ) === '0.6' && Fonts::size( 'button', '1.5' ) === '1.5' && Fonts::size( 'code', .6 ) === '0.6' && Fonts::size( 'code', 1.3 ) === '1.3'
	&& Fonts::size( 'button', .59 ) === '0.6' && Fonts::size( 'code', 1.31 ) === '1.3' && Fonts::size( 'text', .79 ) === '0.8' );
check( '...and a size between two steps of the field is kept as it was typed, to three decimals: 1.25 stays 1.25, 1.125 stays 1.125, 0.875 stays 0.875 - the step is the spinner\'s and not the model\'s',
	Fonts::size( 'text', '1.25' ) === '1.25' && Fonts::size( 'subtitle', 1.125 ) === '1.125' && Fonts::size( 'code', '0.875' ) === '0.875' && Fonts::size( 'title', '1.55' ) === '1.55' );
check( '...whatever is no number is the default - nothing, null, text, an array, a bool, a comma for the point - and so is a size for a role there is none of',
	Fonts::size( 'title', '' ) === '' && Fonts::size( 'title', null ) === '' && Fonts::size( 'title', 'big' ) === '' && Fonts::size( 'title', [ 2 ] ) === ''
	&& Fonts::size( 'title', true ) === '' && Fonts::size( 'title', '2,4' ) === '' && Fonts::size( 'nothing', '1' ) === '' );
// NAN is a float, and neither below a bound nor above one: held between them it was the most, a title of 4 rem that nobody typed
check( '...and so is a float that is not a number: NAN in, the default out, for every role - not the most of the role, which is what holding it between the bounds made of it',
	Fonts::size( 'title', NAN ) === '' && array_filter( Fonts::ROLES, static fn( string $role ): bool => Fonts::size( $role, NAN ) !== '' ) === [] );
check( '...and it answers a size it answers to again: the bounds are sizes of their own, and so is the one Nino.css gives each role',
	array_map( static fn( string $role ): string => Fonts::size( $role, Fonts::BOUNDS[$role][0] ), Fonts::ROLES ) === [ '1', '0.8', '0.8', '0.6', '0.6' ]
	&& array_map( static fn( string $role ): string => Fonts::size( $role, Fonts::BOUNDS[$role][1] ), Fonts::ROLES ) === [ '4', '2.5', '1.5', '1.5', '1.3' ]
	&& array_filter( Fonts::ROLES, static fn( string $role ): bool => Fonts::size( $role, Fonts::INHERITED[$role] ) !== Fonts::INHERITED[$role]
		|| Fonts::size( $role, Fonts::size( $role, 1.23456 ) ) !== Fonts::size( $role, 1.23456 ) ) === [] );

echo "\nWhat the compiler produces\n";

$notes	= [];
$setup	= Setup::normalize( $appData, [], $library );
$css		= Compiler::compile( $appData, $setup, $library, $notes );

check( 'compiling a default setup reports nothing missing', $notes === [] );
check( 'the sheet opens with a header naming what made it and forbidding hand edits', str_starts_with( $css, '/*' ) === true
	&& str_contains( $css, 'Generated by the Design feature' ) === true && str_contains( $css, 'Do not edit' ) === true
	&& preg_match( '/'. preg_quote( Compiler::STAMP, '/' ). ': [a-f0-9]{64}/', $css ) === 1 );
check( '...and the header lists the chosen set of every part, the five font roles, the root size, the width, the whitespace, the radius and the animation',
	array_filter( array_keys( Setup::PARTS ), static fn( string $part ): bool => preg_match( '/^ \*   '. $part. ' +'. preg_quote( $defaultSets[$part], '/' ). '(?:  \([^)]*\))?$/m', $css ) !== 1 ) === []
	&& array_filter( Fonts::ROLES, static fn( string $role ): bool => preg_match( '/^ \*   '. $role. ' +\S/m', $css ) !== 1 ) === []
	&& preg_match( '/^ \*\troot size  default$/m', $css ) === 1 && preg_match( '/^ \*\twidth      default$/m', $css ) === 1
	&& preg_match( '/^ \*\twhitespace default$/m', $css ) === 1
	&& preg_match( '/^ \*\tradius     default$/m', $css ) === 1 && preg_match( '/^ \*\tradius alt '. Setup::word( 1 ). '$/m', $css ) === 1
	&& preg_match( '/^ \*\tradii      '. preg_quote( implode( ', ', array_map( static fn( string $kind ): string => $kind. ' standard', Setup::SURFACES ) ), '/' ). '$/m', $css ) === 1
	&& preg_match( '/^ \*\tanimation  as Nino\.css has it$/m', $css ) === 1 );

// The order is the whole contract: the tokens before the palette that replaces them, the type before the roles
// that read it, the page, the frames, the sets, and the knob block last because it reads what the sets declared
$order = [];
if( preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $css, $sections, PREG_SET_ORDER ) > 0 )
	foreach( $sections as $section )
		$order[(int) $section[1]] = trim( $section[2] );

check( 'fourteen numbered sections - the first six the tokens, the palette, the type, the roles, the root size and the page, then one for each of the seven parts and the knob positions', array_keys( $order ) === range( 1, 14 )
	&& count( Setup::PARTS ) === 7 && array_slice( $order, 0, 6 ) === [ 'the design tokens', 'the palette', 'the type', 'the roles', 'the root size', 'the page' ] );
/*	Each set saying where the knobs it answers to stand: at the position the defaults put each, for the default set, which answers to every knob of its part	*/
$standing = static fn( string $part ): string => $defaultSets[$part]. ' ('. implode( ', ', array_map( static fn( string $knob ): string => $knob. ' '. Setup::word( $setup['knobs'][$part][$knob] ), Setup::knobs( $library, $part, $defaultSets[$part] ) ) ). ')';

$partsInOrder = [];

foreach( Setup::PARTS as $part => $kind )
	$partsInOrder[] = $part. ': '. $standing( $part );

$partsInOrder[] = 'the knob positions';

check( '...then one section for each part in the order the parts are declared in, each saying which set it is and where the knobs it answers to stand, and the knob positions last', array_slice( $order, 6 ) === $partsInOrder );

$tokensOf	= designPlain( designSection( $css, 1, Compiler::SECTION ) );
$rolesOf	= designPlain( designSection( $css, 4, Compiler::SECTION ) );

check( 'the token layer really is in there, and the roles that name its tokens, each in its own section: base.css fell into its two halves',
	str_contains( $tokensOf, '--nino-default:' ) === true && str_contains( $rolesOf, '--color-title:' ) === true
	&& str_contains( $tokensOf, '--color-title:' ) === false && str_contains( $rolesOf, '--nino-default:' ) === false );
check( 'the root size is the relative pair, never a length', str_contains( $css, '--nino-base-size: 100%;' ) === true
	&& str_contains( $css, '@media (min-width: 768px) { :root { --nino-base-size: 112.5%; } }' ) === true
	&& preg_match( '/--nino-base-size:\s*\d+px/', $css ) !== 1 );

/*	Five sizes, -2 to +2, each a pair, and section 5 writes exactly the pair of the one that was chosen:
	both halves of it, the rule below the breakpoint and the one from it. The tokens above
	it keep the delivered 100%, which is what section 5 moves	*/
$pairProblems = [];

foreach( Setup::SIZES as $size => $pair ) {

	$sized = Compiler::compile( $appData, Setup::normalize( $appData, [ 'size' => $size ], $library ), $library );

	if( designSection( $sized, 5, Compiler::SECTION ) !== sprintf( Compiler::SECTION, 5, 'the root size' ). "\n:root { --nino-base-size: ". $pair[0]. "; }\n@media (min-width: 768px) { :root { --nino-base-size: ". $pair[1]. "; } }\n\n"
		|| str_contains( designSection( $sized, 1, Compiler::SECTION ), '--nino-base-size: 100%;' ) === false )
		$pairProblems[] = $size;
}

check( 'the size knob moves the pair, not one half of it: for each of the five, section 5 has its pair and the tokens above it the delivered one'
	. ( $pairProblems === [] ? '' : ' - not so for '. implode( ', ', $pairProblems ) ), $pairProblems === [] );

$xsSheet = designSection( Compiler::compile( $appData, Setup::normalize( $appData, [ 'size' => -2 ], $library ), $library ), 5, Compiler::SECTION );
$xlSheet = designSection( Compiler::compile( $appData, Setup::normalize( $appData, [ 'size' => 2 ], $library ), $library ), 5, Compiler::SECTION );

check( '...-2 is 85% below the breakpoint and 95.625% from it, +2 145% and 163.125% - and nothing of the delivered 100% and 112.5% stays in section 5',
	str_contains( $xsSheet, '--nino-base-size: 85%;' ) === true && str_contains( $xsSheet, '--nino-base-size: 95.625%;' ) === true
	&& str_contains( $xlSheet, '--nino-base-size: 145%;' ) === true && str_contains( $xlSheet, '--nino-base-size: 163.125%;' ) === true
	&& str_contains( $xsSheet. $xlSheet, '--nino-base-size: 100%;' ) === false && str_contains( $xsSheet. $xlSheet, '--nino-base-size: 112.5%;' ) === false );

/*	What a person sees: at a browser default of 16px the five are 13.6, 14.8, 16, 18.4 and 23.2px below the breakpoint - the two under the
	middle 7.5% of it apart, the two over it 15% and 30% - and the kernel's own ratio of 1.125 more from it: 15.3, 16.65, 18, 20.7 and 26.1px	*/
$pixels = static fn( string $percent ): float => round( (float) $percent * 16 / 100, 3 );

check( '...the steps are 7.5% of the default apart under the middle and 15% and 30% over it below the breakpoint, and 1.125 times that from it: 13.6, 14.8, 16, 18.4, 23.2px and 15.3, 16.65, 18, 20.7, 26.1px at 16px',
	array_map( static fn( array $pair ): float => $pixels( $pair[0] ), array_values( Setup::SIZES ) ) === [ 13.6, 14.8, 16.0, 18.4, 23.2 ]
	&& array_map( static fn( array $pair ): float => $pixels( $pair[1] ), array_values( Setup::SIZES ) ) === [ 15.3, 16.65, 18.0, 20.7, 26.1 ]
	&& array_filter( Setup::SIZES, static fn( array $pair ): bool => abs( (float) $pair[1] / (float) $pair[0] - 1.125 ) > 1e-9 ) === []
	&& Setup::SIZES[0] === [ '100%', '112.5%' ] );

// A library that is not complete is compiled around, and the notes say what is not in the sheet
$notes = [];
$partial = Compiler::compile( $appData, Setup::normalize( $appData, [], $empty ), $empty, $notes );
check( 'a library with no base.css and no templates compiles to the sections it can, and says what it lacks: the tokens, and each template',
	array_keys( array_map( 'trim', ( static function( string $sheet ): array {
		preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $sheet, $found, PREG_SET_ORDER );
		return array_column( $found, 2, 1 );
	} )( $partial ) ) ) === [ 2, 3, 5, 6 ]
	&& str_contains( implode( ' ', $notes ), 'no base.css' ) === true );

// A setup that stands off the defaults everywhere: another set for each part - whichever the library has besides the default one - and, on each part, the first two knobs that set answers to, moved
$richParts = [];
$richKnobs = [];

foreach( Setup::PARTS as $part => $kind )
	$richParts[$part] = array_values( array_diff( Setup::available( $library, $part ), [ $defaultSets[$part] ] ) )[0] ?? $defaultSets[$part];

foreach( Setup::KNOBS as $part => $kinds )
	foreach( array_slice( Setup::knobs( $library, $part, $richParts[$part] ), 0, 2 ) as $at => $knob )
		$richKnobs[$part][$knob] = $kinds[$knob] === 'toggle' ? 1 : ( $kinds[$knob] === 'choice' ? ( $at === 0 ? 1 : -1 ) : ( $at === 0 ? 2 : -1 ) );

$richRaw = [
	'size' => 1, 'width' => -2, 'whitespace' => 2, 'radius' => -1, 'radius-alt' => 2, 'radii' => [ 'card' => 'alternative', 'image' => 'alternative' ],
	'motion' => [ 'effect' => 'flip', 'strength' => 'hard', 'speed' => 'slow' ],
	'parts' => $richParts, 'knobs' => $richKnobs,
	'fonts' => [ 'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'size' => '2.4' ], 'code' => [ 'weight' => 'bold', 'size' => '0.9' ] ],
	'colours' => [ 'mode' => 'dark', 'primary' => '#8b1d3f' ],
];
$notes	= [];
$richSetup = Setup::normalize( $appData, $richRaw, $library );
$rich		= Compiler::compile( $appData, $richSetup, $library, $notes );
$header	= substr( $rich, 0, (int) strpos( $rich, '*/' ) );

check( 'a setup that stands off the defaults compiles all the same, and reports nothing', $notes === [] );
// What the header says of each part: the set, and the knobs it answers to that are off the middle, in the order of the part
$partLines = [];
$stepLines = [];

foreach( Setup::PARTS as $part => $kind ) {

	$offMiddle = [];

	foreach( Setup::knobs( $library, $part, $richParts[$part] ) as $knob )
		if( $richSetup['knobs'][$part][$knob] !== 0 ) {
			$offMiddle[] = $knob. ' '. Setup::word( $richSetup['knobs'][$part][$knob] );
			$stepLines[] = '--'. $part. '-'. $knob. ': var(--'. $part. '-'. $knob. '--'. Setup::word( $richSetup['knobs'][$part][$knob] ). ');';
		}

	$partLines[] = ' *   '. str_pad( $part, 11 ). $richParts[$part]. ( $offMiddle === [] ? '' : '  ('. implode( ', ', $offMiddle ). ')' ). "\n";
}

check( 'the header says what stands off the defaults: the size, the width, the whitespace, the radius with its two strengths and the strength of each kind of surface, the animation, each part with the knobs it answers to that are moved, each role with its family, weight and size - and none with a case',
	str_contains( $header, " *\troot size  more\n" ) === true && str_contains( $header, " *\twidth      least\n" ) === true
	&& str_contains( $header, " *\twhitespace most\n" ) === true && str_contains( $header, " *\tanimation  flip, hard, slow\n" ) === true
	&& str_contains( $header, " *\tradius     less\n" ) === true && str_contains( $header, " *\tradius alt most\n" ) === true
	&& str_contains( $header, " *\tradii      ". implode( ', ', array_map( static fn( string $kind ): string => $kind. ' '. ( $richRaw['radii'][$kind] ?? 'standard' ), Setup::SURFACES ) ). "\n" ) === true
	&& array_filter( $partLines, static fn( string $line ): bool => str_contains( $header, $line ) === false ) === [] && count( $partLines ) === count( Setup::PARTS )
	&& str_contains( $header, " *   title      System serif, light, 2.4rem\n" ) === true
	&& str_contains( $header, " *   code       the system stack, bold, 0.9rem\n" ) === true );
check( '...and the knobs that were moved are in the sheet, each at the step of its position, as one selection line - however many of them the chosen sets answer to',
	$stepLines !== [] && array_filter( $stepLines, static fn( string $line ): bool => str_contains( $rich, $line ) === false ) === [] );

echo "\nThe type: the five font roles, written where they were chosen\n";

$typeOf = static fn( array $raw ): string => designPlain( designSection( Compiler::compile( $appData, Setup::normalize( $appData, $raw, $library ), $library ), 3, Compiler::SECTION ) );
$rolesIn = static fn( string $type ): array => designDeclarations( designBlock( $type, ':root {' ) );

$type		= $typeOf( [] );
$roles	= $rolesIn( $type );

check( 'the defaults are the face the wizard delivers: the four roles of running text in Open Sans, the title and the buttons in its 700, the subtitle and the text in its 400',
	( $roles['--fontfamily-title'] ?? '' ) === '"Open Sans", '. $sans && ( $roles['--font-title-weight'] ?? '' ) === '700'
	&& ( $roles['--fontfamily-subtitle'] ?? '' ) === '"Open Sans", '. $sans && ( $roles['--font-subtitle-weight'] ?? '' ) === '400'
	&& ( $roles['--fontfamily-text'] ?? '' ) === '"Open Sans", '. $sans && ( $roles['--font-text-weight'] ?? '' ) === '400'
	&& ( $roles['--fontfamily-button'] ?? '' ) === '"Open Sans", '. $sans && ( $roles['--font-button-weight'] ?? '' ) === '700' );
check( '...a role with no family writes none and keeps the system stack Nino.css has for it, and no size is written where nobody chose one',
	isset( $roles['--fontfamily-code'] ) === false && ( $roles['--font-code-weight'] ?? '' ) === 'normal'
	&& preg_grep( '/-(transform|size)$/', array_keys( $roles ) ) === [] && isset( $roles['--card-title-scale'] ) === false );
check( '...and the family the four roles share is declared once, one @font-face for each file it comes in - the 300, the 400 and the 700',
	substr_count( $type, "font-family: 'Open Sans'" ) === 3 && substr_count( $type, '@font-face' ) === 3
	&& str_contains( $type, "font-weight: 300;\n\tfont-display: swap;\n\tsrc: url('[[/nino/public]]/fonts/open-sans-light.woff2') format('woff2');" ) === true
	&& str_contains( $type, "font-weight: 400;\n\tfont-display: swap;\n\tsrc: url('[[/nino/public]]/fonts/open-sans-regular.woff2') format('woff2');" ) === true
	&& str_contains( $type, "font-weight: 700;\n\tfont-display: swap;\n\tsrc: url('[[/nino/public]]/fonts/open-sans-bold.woff2') format('woff2');" ) === true );

$chosen = $rolesIn( $typeOf( $richRaw ) );

check( 'a role that was chosen is written as chosen: the stack of a system family, the weight as a number or a keyword, the size in rem',
	( $chosen['--fontfamily-title'] ?? '' ) === 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif' && ( $chosen['--font-title-weight'] ?? '' ) === '300'
	&& ( $chosen['--font-title-size'] ?? '' ) === '2.4rem'
	&& isset( $chosen['--fontfamily-code'] ) === false && ( $chosen['--font-code-weight'] ?? '' ) === 'bold' && ( $chosen['--font-code-size'] ?? '' ) === '0.9rem' );
// The roles carry no case: not a line for any of the five - the case is a switch of the objects, which a set reads
check( '...and no role is written with a case: the compiler writes no transform line for any role',
	preg_grep( '/-transform$/', array_keys( $chosen ) ) === [] && preg_grep( '/-transform$/', array_keys( $roles ) ) === [] );
check( '...a title with a size of its own fixes the card title as well, because Nino.css multiplies the role by a factor that only gives the size it had on a step of the scale',
	( $chosen['--card-title-scale'] ?? '' ) === '.8' && isset( $rolesIn( $typeOf( [ 'fonts' => [ 'subtitle' => [ 'size' => '1.5' ] ] ] ) )['--card-title-scale'] ) === false );
$typed = $rolesIn( $typeOf( [ 'fonts' => [ 'title' => [ 'size' => '2.45' ], 'text' => [ 'size' => 1.35 ], 'subtitle' => [ 'size' => 9 ], 'code' => [ 'size' => '0.1' ] ] ] ) );
check( '...a size that was typed, and is no step of any list, is written in rem as it is - and one outside the bounds at the bound: 2.45 for the title, 1.35 for the text, 9 for the subtitle, 0.1 for the code',
	( $typed['--font-title-size'] ?? '' ) === '2.45rem' && ( $typed['--font-text-size'] ?? '' ) === '1.35rem'
	&& ( $typed['--font-subtitle-size'] ?? '' ) === '2.5rem' && ( $typed['--font-code-size'] ?? '' ) === '0.6rem' && isset( $typed['--font-button-size'] ) === false
	&& ( $typed['--card-title-scale'] ?? '' ) === '.8' );
check( '...and an empty size, or one that is no number, writes no size line at all',
	preg_grep( '/-size$/', array_keys( $rolesIn( $typeOf( [ 'fonts' => [ 'title' => [ 'size' => 'big' ], 'text' => [ 'size' => '' ], 'code' => [ 'size' => null ] ] ] ) ) ) ) === [] );
check( '...and the Open Sans the other three roles still use is declared once, a rule for each of its three files - and not at all once no role uses it any more',
	substr_count( $typeOf( $richRaw ), "font-family: 'Open Sans'" ) === 3 && substr_count( $typeOf( $richRaw ), '@font-face' ) === 3
	&& str_contains( $typeOf( [ 'fonts' => [ 'title' => [ 'family' => 'system-serif' ], 'subtitle' => [ 'family' => 'system-sans' ], 'text' => [ 'family' => '' ], 'button' => [ 'family' => 'system-mono' ] ] ] ), '@font-face' ) === false );

// The stacks are the kernel's own, and the step a role starts from is the one Nino.css gives it
$ninoRootDecls = designDeclarations( designBlock( $ninoCss, ':root {' ) );
$ninoLarge = designDeclarations( designBlock( designBlock( $ninoCss, '@media (min-width: 768px){' ), ':root{' ) );

if( $ninoCss === '' )
	echo "  --  this checkout has no Nino.css, so the stacks and the sizes of the roles are not compared with the kernel's\n";
else {

	check( 'the stacks a role falls back to are the stacks Nino.css has for its roles', $sans === ( $ninoRootDecls['--fontfamily-text'] ?? '' )
		&& $sans === ( $ninoRootDecls['--fontfamily-title'] ?? '' ) && $mono === ( $ninoRootDecls['--fontfamily-code'] ?? '' ) );
	/*	The size each role has is its own rule's - --font-<role>-size, which stands on a step of the scale (var(--text-N), whose
		factor from 768px is the media block's where it has one) or on a token of its own (var(--fontsize-btn)) or is a length - read
		from the kernel at the time of the test, so that this holds on any kernel whose numbers move together with the table's	*/
	$kernelSize = static function( string $role ) use ( $ninoRootDecls, $ninoLarge ): string {

		$value = (string) ( $ninoRootDecls['--font-'. $role. '-size'] ?? '' );

		for( $depth = 0; $depth < 3 && preg_match( '/^var\((--[a-z0-9-]+)\)$/', $value, $reference ) === 1; $depth++ )
			$value = (string) ( $ninoLarge[$reference[1]] ?? $ninoRootDecls[$reference[1]] ?? '' );

		if( preg_match( '/^calc\(var\(--font-text-size\) \* ([0-9.]+)\)$/', $value, $factor ) === 1 )
			return (string) (float) $factor[1];

		return preg_match( '/^([0-9.]+)rem$/', $value, $length ) === 1 ? (string) (float) $length[1] : '';
	};

	check( '...and the size each role has until it is fixed is what Nino.css gives it from 768px: the rule of each role and the step of the scale it stands on, read from the kernel - '. implode( ', ', array_map( static fn( string $role ): string => $role. ' '. $kernelSize( $role ), Fonts::ROLES ) ),
		array_map( $kernelSize, Fonts::ROLES ) === array_values( Fonts::INHERITED ) && array_keys( Fonts::INHERITED ) === Fonts::ROLES );
	check( '...and the bounds of a role run over that size, from below it to above it - what is typed can be smaller than the default and larger, and the default is a size the role may be given',
		array_filter( Fonts::ROLES, static fn( string $role ): bool => Fonts::BOUNDS[$role][0] >= (float) Fonts::INHERITED[$role] || Fonts::BOUNDS[$role][1] <= (float) Fonts::INHERITED[$role] ) === [] );
}

echo "\nThe typefaces: the signed list, a family installed whole, and what lists it\n";

/*	No network, no key of the owner's, no real fonts: the stub of \Nino\Fetch answers from a map of
	urls, a key pair is made for this run, and the archives are built here byte by byte - so that a
	hostile one can be built as well. Everything lands in the public root of the sandbox	*/
$webKeys = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $webKeys, $webPrivate );
$webPublic = openssl_pkey_get_details( $webKeys )['key'];

$strangerKeys = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $strangerKeys, $strangerPrivate );

$remote 		= [];
$requests 	= [];

$appData['./nino/fetch/stub'] = static function( string $url, array $options ) use ( &$remote, &$requests ): array {

	$requests[] = [ 'url' => $url, 'options' => $options ];

	if( isset( $remote[$url] ) === false )
		return [ 'ok' => false, 'status' => 404, 'error' => 'http 404' ];

	if( strlen( $remote[$url] ) > $options['maxBytes'] )
		return [ 'ok' => false, 'status' => 200, 'error' => 'the answer exceeds '. $options['maxBytes']. ' bytes' ];

	return [ 'ok' => true, 'status' => 200, 'body' => $remote[$url] ];
};

/*	A list under the default url, signed with the key of this run unless it is told otherwise */
$publish = static function( array $entries, ?string $signWith = null ) use ( &$remote, $webPrivate ): string {

	$json 												= designList( $entries );
	$remote[Webfonts::DEFAULT_URL] 				= $json;
	$remote[Webfonts::DEFAULT_URL. '.sig'] = $signWith ?? designSign( $json, $webPrivate );

	return $json;
};

$inter 		= designManifest( 'inter', 'Inter', [ [ 400, 'normal' ], [ 700, 'normal' ], [ 400, 'italic' ], [ 700, 'italic' ] ], [ 'version' => '4.1', 'source' => 'https://github.com/rsms/inter' ] );
$flex 		= designManifest( 'roboto-flex', 'Roboto Flex', [ [ '100 900', 'normal' ], [ '100 900', 'italic' ] ], [ 'version' => '3.0' ] );
$firaCode = designManifest( 'fira-code', 'Fira Code', [ [ 400, 'normal' ] ], [ 'category' => 'mono', 'license' => 'Apache-2.0' ] );

$archiveInter 	= designTarGz( designArchiveFiles( $inter ) );
$archiveFlex 		= designTarGz( designArchiveFiles( $flex ) );
$archiveFira 		= designTarGz( designArchiveFiles( $firaCode ) );

$entryInter 	= designListEntry( $inter, $archiveInter );
$entryFlex 		= designListEntry( $flex, $archiveFlex );
$entryFira 		= designListEntry( $firaCode, $archiveFira );

$remote[$entryInter['archive']] = $archiveInter;
$remote[$entryFlex['archive']] 	= $archiveFlex;
$remote[$entryFira['archive']] 	= $archiveFira;

$staging 	= \Nino\Filesystem::path( $appData, Webfonts::STAGING );
$cacheFile = \Nino\Filesystem::path( $appData, Webfonts::CACHE );
$fontsDir = Fonts::dir( $appData );

// --- The settings -----------------------------------------------------------------

check( 'the manifest declares the two settings of the repository - its address, a url, which is Nino\'s own by default, and the public key that signs it, a text, empty by default',
	( $feature['settings']['webfontsUrl']['type'] ?? '' ) === 'url' && ( $feature['settings']['webfontsUrl']['default'] ?? '' ) === 'https://webfonts.getnino.dev/webfonts.json'
	&& ( $feature['settings']['webfontsKey']['type'] ?? '' ) === 'text' && ( $feature['settings']['webfontsKey']['default'] ?? null ) === ''
	&& array_keys( $feature['settings'] ) === [ 'webfontsUrl', 'webfontsKey' ] );
check( '...Features::setting() answers them with those defaults, and Webfonts reads the address from the first',
	\Nino\Features::setting( $appData, 'design', 'webfontsUrl' ) === 'https://webfonts.getnino.dev/webfonts.json' && \Nino\Features::setting( $appData, 'design', 'webfontsKey' ) === ''
	&& Webfonts::DEFAULT_URL === 'https://webfonts.getnino.dev/webfonts.json' && Webfonts::url( $appData ) === Webfonts::DEFAULT_URL );
check( '...an address that is not https is none that can be fetched - the setting takes it, the repository answers with none - and an empty one switches the repository off',
	\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'http://webfonts.test/webfonts.json' ] ) === [] && Webfonts::url( $appData ) === ''
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => '' ] ) === [] && Webfonts::url( $appData ) === ''
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'https://elsewhere.test/list.json' ] ) === [] && Webfonts::url( $appData ) === 'https://elsewhere.test/list.json'
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL ] ) === [] );
check( '...and the key is the setting\'s, trimmed, else the one in the class', \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsKey' => "\n". $webPublic. "\n  " ] ) === []
	&& Webfonts::key( $appData ) === trim( $webPublic ) && \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsKey' => '' ] ) === [] && Webfonts::key( $appData ) === Webfonts::PUBLIC_KEY );

// The key the owner pastes in is theirs to make: while there is none, nothing is believed, and the refusal says where to put it
if( Webfonts::PUBLIC_KEY === '' ) {

	$requests = [];

	check( 'with no key in the class and none in the setting the list is refused before a request is made, and the reason says where the key goes - the refusal is about the key',
		Webfonts::fetch( $appData, $kind ) === 'no public key for the repository - set it in the feature\'s settings or in Webfonts::PUBLIC_KEY' && $requests === [] && $kind === 'nokey' );
}
else
	echo "  --  the class carries a public key, so the refusal for a missing key is not tried\n";

check( 'a repository switched off is not asked, and the refusal is about that',
	\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => '', 'webfontsKey' => $webPublic ] ) === [] && Webfonts::fetch( $appData, $kind ) === 'the typeface repository is switched off' && $requests === [] && $kind === 'off'
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL ] ) === [] );

// --- The workbench's policy: the previews of the list ------------------------------------

/*	The Typefaces tab sets each name of the list in the preview its family publishes, loaded by the browser from the
	repository - and the policy the workbench is sent has no font-src, so default-src 'self' refuses it. The feature
	widens font-src by the origin of the list's address, in the output phase, for the workbench's own responses	*/
$workbench = [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/_admin' ];
\Nino\Http::request( $appData, $workbench );
$sentPolicy = (string) $workbench['/nino/http/response']['header']['Content-Security-Policy'];

$appData['/nino/modules'] = [ '\\Nino\\Modules\\Design' ];
\Nino\Modules::callModules( $appData, 'init' );

/**
 *	The policy a response of $uri ends up with: the output callbacks run over a response that carries $policy
 *
 *	@param		string		$uri
 *	@param		mixed			$policy				The header value the response carries before the callbacks
 *
 *	@return 	mixed											The header value after them
 */
$policyOf = static function( string $uri, mixed $policy ) use ( &$appData, $workbench ): mixed {

	$request = $workbench;
	$request['/nino/http/request']['uri'] = $uri;
	$request['/nino/http/response']['uri'] = $uri;
	$request['/nino/http/response']['header']['Content-Security-Policy'] = $policy;
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );

	return $request['/nino/http/response']['header']['Content-Security-Policy'];
};

check( 'the feature hooks the output phase, where the policy is final: init() registers one callback, on /nino/http/output',
	in_array( [ \Nino\Modules\Design::class, 'callbackOutput' ], array_merge( ...( $appData['./nino/callbacks']['/nino/http/output'] ?? [ [] ] ) ), true ) === true
	&& count( array_merge( ...( $appData['./nino/callbacks']['/nino/http/output'] ?? [ [] ] ) ) ) === 1 );
check( 'the policy the workbench is sent has a default-src and no font-src - the case this exists for',
	str_contains( $sentPolicy, "default-src 'self'" ) === true && str_contains( $sentPolicy, 'font-src' ) === false );
check( 'a response of /_admin, and of what is below it, names the origin of the list\'s address in a font-src of its own, built from the default-src - and nothing else of the policy is touched',
	$policyOf( '/_admin', $sentPolicy ) === $sentPolicy. "; font-src 'self' https://webfonts.getnino.dev" && $policyOf( '/_admin/design/webfonts/list', $sentPolicy ) === $sentPolicy. "; font-src 'self' https://webfonts.getnino.dev"
	&& $policyOf( '/_admin/', $sentPolicy ) === $sentPolicy. "; font-src 'self' https://webfonts.getnino.dev" );
check( '...a page of the website keeps its policy byte for byte - and so does one that only begins with the same letters',
	$policyOf( '/', $sentPolicy ) === $sentPolicy && $policyOf( '/about', $sentPolicy ) === $sentPolicy && $policyOf( '/_administrator', $sentPolicy ) === $sentPolicy && $policyOf( '/blog/_admin', $sentPolicy ) === $sentPolicy );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => '' ] );
$off = $policyOf( '/_admin', $sentPolicy );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'http://webfonts.test/webfonts.json' ] );
$notHttps = $policyOf( '/_admin', $sentPolicy );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'https://203.0.113.5/webfonts.json' ] );
$byNumber = $policyOf( '/_admin', $sentPolicy );

check( 'a repository switched off adds nothing - no address, an address that is not https, an address that is a number and no name',
	$off === $sentPolicy && $notHttps === $sentPolicy && $byNumber === $sentPolicy );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'HTTPS://Fonts.Example.org:8443/lists/webfonts.json?token=abc' ] );

check( '...the repository of a project\'s own is the origin that is named, as the setting says it: the scheme and the host in lower case, the port kept, no path and no query',
	$policyOf( '/_admin', $sentPolicy ) === $sentPolicy. "; font-src 'self' https://fonts.example.org:8443" );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL ] );

$withFontSrc = "default-src 'self'; font-src https://cdn.example; img-src *";

check( 'an existing font-src is extended in place, never given a second directive - and an origin it lists already is not added again, in whatever case',
	$policyOf( '/_admin', $withFontSrc ) === "default-src 'self'; font-src https://cdn.example https://webfonts.getnino.dev; img-src *"
	&& $policyOf( '/_admin', "default-src 'self'; font-src HTTPS://WEBFONTS.GETNINO.DEV" ) === "default-src 'self'; font-src HTTPS://WEBFONTS.GETNINO.DEV" );
check( '...a default-src that lists more than \'self\' is carried over rather than narrowed to \'self\', and one that has the origin already gives a policy that stays',
	$policyOf( '/_admin', "default-src 'self' https://cdn.example" ) === "default-src 'self' https://cdn.example; font-src 'self' https://cdn.example https://webfonts.getnino.dev"
	&& $policyOf( '/_admin', "default-src 'self' https://webfonts.getnino.dev" ) === "default-src 'self' https://webfonts.getnino.dev" );
check( '...a font-src of \'none\' or with no source at all, and a default-src of \'none\' or with none, is the project\'s decision and stays - and a policy with no default-src is unrestricted already, and creating a font-src would restrict it',
	$policyOf( '/_admin', "default-src 'self'; font-src 'none'" ) === "default-src 'self'; font-src 'none'" && $policyOf( '/_admin', "default-src 'self'; font-src; img-src *" ) === "default-src 'self'; font-src; img-src *"
	&& $policyOf( '/_admin', "default-src 'none'; img-src *" ) === "default-src 'none'; img-src *" && $policyOf( '/_admin', "default-src; img-src *" ) === "default-src; img-src *"
	&& $policyOf( '/_admin', "img-src *; style-src 'self'" ) === "img-src *; style-src 'self'" );
check( '...a response with no policy, or one that is not a string, is left alone',
	$policyOf( '/_admin', '' ) === '' && $policyOf( '/_admin', [ 'not' => 'a policy' ] ) === [ 'not' => 'a policy' ] );

unset( $appData['./nino/callbacks']['/nino/http/output'], $appData['/nino/modules'] );

// --- The list: what it is made of ---------------------------------------------------

$parsed = Webfonts::parse( designList( [ $entryInter, $entryFlex, $entryFira ] ) );

check( 'a list in the shape the repository writes is read: format 1, when it was made, and every family with its fourteen fields - the nine of the manifest, the archive with its digest and size, the preview and the day',
	is_array( $parsed ) === true && $parsed['format'] === 1 && $parsed['generated'] === '2026-10-12T09:30:00Z' && $parsed['families'] === [ $entryInter, $entryFlex, $entryFira ]
	&& array_keys( $entryInter ) === [ 'slug', 'family', 'category', 'version', 'license', 'source', 'weights', 'styles', 'files', 'archive', 'sha256', 'size', 'preview', 'released' ]
	&& array_slice( array_keys( $entryInter ), 0, 9 ) === Webfonts::MANIFEST );
check( '...a key it does not know is ignored, in an entry and in the document, so that a feature written before a field reads a list that has it - and a digest in capitals is the digest',
	Webfonts::parse( designList( [ $entryInter + [ 'tags' => [ 'ui' ] ], array_replace( $entryFlex, [ 'sha256' => strtoupper( $entryFlex['sha256'] ) ] ) ], [ 'next' => true ] ) )['families'] === [ $entryInter, $entryFlex ] );
check( '...a list with no family in it is a list', Webfonts::parse( designList( [] ) )['families'] === [] );

$cases = [
	'a licence that is none of the three'												=> [ 'license' => 'GPL-3.0' ],
	'a licence in lower case'																		=> [ 'license' => 'ofl-1.1' ],
	'a name with a "<" in it'																		=> [ 'family' => 'In<ter' ],
	'a name with a quote in it'																	=> [ 'family' => 'Inter\' }' ],
	'a name with a space at its end'														=> [ 'family' => 'Inter ' ],
	'a name that begins with a dot'															=> [ 'family' => '.Inter' ],
	'a name that begins with a hyphen'														=> [ 'family' => '-Inter' ],
	'a name that begins with a space'														=> [ 'family' => ' Inter' ],
	'a name that is nothing'																		=> [ 'family' => '' ],
	'a name of 65 characters'																		=> [ 'family' => str_repeat( 'n', 65 ) ],
	'a name that is no string'																	=> [ 'family' => [ 'Inter' ] ],
	'a slug with a capital'																			=> [ 'slug' => 'Inter' ],
	'a slug with two hyphens in a row'													=> [ 'slug' => 'in--ter' ],
	'a slug of 65 characters'																		=> [ 'slug' => str_repeat( 's', 65 ) ],
	'a slug that climbs'																				=> [ 'slug' => '../inter' ],
	'a size over the limit'																			=> [ 'size' => Webfonts::MAX_ARCHIVE_BYTES + 1 ],
	'a size of nothing'																					=> [ 'size' => 0 ],
	'a size that is a string'																		=> [ 'size' => '1000' ],
	'a category that is none of the six'												=> [ 'category' => 'script' ],
	'a version with a slash'																		=> [ 'version' => '4/1' ],
	'a version of 33 characters'																=> [ 'version' => str_repeat( '1', 33 ) ],
	'a source that is not https'																=> [ 'source' => 'http://github.com/rsms/inter' ],
	'an archive that is not https'															=> [ 'archive' => 'http://webfonts.test/inter-4.1.tar.gz' ],
	'an archive with credentials in its address'								=> [ 'archive' => 'https://user:pw@webfonts.test/inter-4.1.tar.gz' ],
	'a preview that is not an address'													=> [ 'preview' => 'inter-4.1-preview.woff2' ],
	'a digest that is no digest'																=> [ 'sha256' => 'abc' ],
	'a digest of 64 characters that are not hex'								=> [ 'sha256' => str_repeat( 'g', 64 ) ],
	'a day that is none'																				=> [ 'released' => '2026-13-45' ],
	'a day that is not a day'																		=> [ 'released' => 'yesterday' ],
	'a weight of 950'																						=> [ 'files' => [ [ 'weight' => 950, 'style' => 'normal', 'file' => '950.woff2' ] ], 'weights' => [ 950 ], 'styles' => [ 'normal' ] ],
	'a weight of 50'																						=> [ 'files' => [ [ 'weight' => 50, 'style' => 'normal', 'file' => '50.woff2' ] ], 'weights' => [ 50 ], 'styles' => [ 'normal' ] ],
	'an axis that runs backwards'																=> [ 'files' => [ [ 'weight' => '900 100', 'style' => 'normal', 'file' => '900-100.woff2' ] ], 'weights' => [ '900 100' ], 'styles' => [ 'normal' ] ],
	'an axis that is no range'																	=> [ 'files' => [ [ 'weight' => '400 400', 'style' => 'normal', 'file' => '400-400.woff2' ] ], 'weights' => [ '400 400' ], 'styles' => [ 'normal' ] ],
	'a weight that is a string of one number'										=> [ 'files' => [ [ 'weight' => '400', 'style' => 'normal', 'file' => '400.woff2' ] ], 'weights' => [ '400' ], 'styles' => [ 'normal' ] ],
	'a file named after another weight'													=> [ 'files' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => '700.woff2' ] ], 'weights' => [ 400 ], 'styles' => [ 'normal' ] ],
	'a file that is no name W1 gives'														=> [ 'files' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => 'regular.woff2' ] ], 'weights' => [ 400 ], 'styles' => [ 'normal' ] ],
	'a file that climbs'																				=> [ 'files' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => '../400.woff2' ] ], 'weights' => [ 400 ], 'styles' => [ 'normal' ] ],
	'a style that is none'																			=> [ 'files' => [ [ 'weight' => 400, 'style' => 'oblique', 'file' => '400-oblique.woff2' ] ], 'weights' => [ 400 ], 'styles' => [ 'oblique' ] ],
	'one file twice'																						=> [ 'files' => [ [ 'weight' => 400, 'style' => 'normal', 'file' => '400.woff2' ], [ 'weight' => 400, 'style' => 'normal', 'file' => '400.woff2' ] ], 'weights' => [ 400 ], 'styles' => [ 'normal' ] ],
	'weights that are not those of the files'										=> [ 'weights' => [ 400 ] ],
	'a weight twice in the weights'															=> [ 'weights' => [ 400, 400, 700 ] ],
	'styles that are not those of the files'										=> [ 'styles' => [ 'normal' ] ],
	'no files'																									=> [ 'files' => [] ],
	'files that are an object'																	=> [ 'files' => [ 'a' => [ 'weight' => 400, 'style' => 'normal', 'file' => '400.woff2' ] ] ],
	'weights that are no list'																	=> [ 'weights' => 400 ],
];

$unread = [];

foreach( $cases as $why => $change )
	if( is_string( Webfonts::parse( designList( [ array_replace( $entryInter, $change ) ] ) ) ) === false )
		$unread[] = $why;

check( 'a list with one bad entry is refused whole, whatever is wrong with it - each of these '. count( $cases ). ' ways an entry can be wrong'. ( $unread === [] ? '' : ' - not so for: '. implode( ', ', $unread ) ), $unread === [] );

$unnamed = [];

foreach( array_keys( $entryInter ) as $field ) {

	$bad = Webfonts::parse( designList( [ array_diff_key( $entryInter, [ $field => 1 ] ) ] ) );

	if( is_string( $bad ) === false )
		$unnamed[] = $field;
}

check( '...a missing field is one of them, for each of the fourteen'. ( $unnamed === [] ? '' : ' - not so for: '. implode( ', ', $unnamed ) ), $unnamed === [] );

$twice = Webfonts::parse( designList( [ $entryInter, $entryFlex, array_replace( $entryFira, [ 'slug' => 'inter', 'files' => $entryInter['files'], 'weights' => $entryInter['weights'], 'styles' => $entryInter['styles'] ] ) ] ) );

check( '...so are two entries of one slug, and the reason names the entry and the slug', is_string( $twice ) === true && str_contains( $twice, 'entry 2' ) === true && str_contains( $twice, '"inter"' ) === true );
check( '...a reason names the entry and the field, so that the owner who made the list can find it',
	str_contains( (string) Webfonts::parse( designList( [ $entryFlex, array_replace( $entryInter, [ 'license' => 'MIT' ] ) ] ) ), 'typeface list entry 1: "license" must be one of OFL-1.1, Apache-2.0, UFL-1.0' ) === true );
check( '...a document of another format, one that is no json, one with no list of families and one whose families are an object are none',
	Webfonts::parse( designList( [ $entryInter ], [ 'format' => 2 ] ) ) === 'the typeface list has format 2, this feature reads format 1'
	&& Webfonts::parse( 'families: none' ) === 'the typeface list is not valid json'
	&& Webfonts::parse( (string) json_encode( [ 'format' => 1 ] ) ) === 'the typeface list names no families'
	&& is_string( Webfonts::parse( (string) json_encode( [ 'format' => 1, 'families' => [ 'inter' => $entryInter ] ] ) ) ) === true
	&& is_string( Webfonts::parse( '[]' ) ) === true && is_string( Webfonts::parse( '"inter"' ) ) === true );

// --- The list: fetched, verified, kept ---------------------------------------------------

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsKey' => $webPublic ] );

$json 			= $publish( [ $entryInter, $entryFlex, $entryFira ] );
$requests 	= [];
$kind 			= 'left over from a refusal';
$fetched 		= Webfonts::fetch( $appData, $kind );

check( 'fetch() makes two requests - the list, and its signature at the same address with .sig on the end - and answers the families, and a refusal of none',
	is_array( $fetched ) === true && $fetched === [ $entryInter, $entryFlex, $entryFira ] && $kind === ''
	&& array_column( $requests, 'url' ) === [ Webfonts::DEFAULT_URL, Webfonts::DEFAULT_URL. '.sig' ] );
check( '...the list may be a megabyte, the signature a few bytes, and nobody waits the default for either longer than it asks for',
	$requests[0]['options']['maxBytes'] === 1024 * 1024 && $requests[1]['options']['maxBytes'] === 4096 && Webfonts::MAX_LIST_BYTES === 1024 * 1024 );
check( '...the limits of the catalogue are the limits here: 20 MB packed, 50 MB unpacked, 5000 entries',
	Webfonts::MAX_ARCHIVE_BYTES === 20 * 1024 * 1024 && Webfonts::MAX_UNPACKED_BYTES === 50 * 1024 * 1024 && Webfonts::MAX_ENTRIES === 5000 );

$cached = Webfonts::cached( $appData );

check( '...and keeps what it read in data/design-webfonts.php, which cached() reads back without a request: when it was fetched, under which address, the families',
	is_file( $cacheFile ) === true && str_ends_with( $cacheFile, '/data/design-webfonts.php' ) === true && is_array( $cached ) === true && $cached['families'] === $fetched
	&& $cached['url'] === Webfonts::DEFAULT_URL && abs( $cached['fetched'] - time() ) < 5 && array_keys( $cached ) === [ 'fetched', 'url', 'families' ] );

$requests = [];
Webfonts::cached( $appData );

check( '...reading the cache asks nobody for anything, and with no cache there is nothing: null', $requests === [] && ( static function( array $appData ): bool {
	@unlink( \Nino\Filesystem::path( $appData, Webfonts::CACHE ) );
	unset( $appData['./nino/filesystem/cache'] );
	return Webfonts::cached( $appData ) === null;
} )( $appData ) );

// A byte that is not the signed one
Webfonts::fetch( $appData );
$remote[Webfonts::DEFAULT_URL] = str_replace( '"version": "4.1"', '"version": "9.9"', $json );

check( 'a byte of the list that is not the one that was signed is refused, the refusal is about the signature, and the list that was kept stays',
	Webfonts::fetch( $appData, $kind ) === 'the typeface list signature does not verify' && $kind === 'signature' && ( Webfonts::cached( $appData )['families'] ?? [] ) === $fetched );

$publish( [ $entryInter ], designSign( $json, $strangerPrivate ) );

check( '...a list signed with another key is refused, and so is a signature that is none - both about the signature', Webfonts::fetch( $appData, $kind ) === 'the typeface list signature does not verify' && $kind === 'signature'
	&& ( $publish( [ $entryInter ], '!!not a signature!!' ) !== '' && Webfonts::fetch( $appData, $kind ) === 'the typeface list signature does not verify' && $kind === 'signature' ) );

$publish( [ $entryInter, $entryFlex, $entryFira ] );
unset( $remote[Webfonts::DEFAULT_URL. '.sig'] );

check( '...a signature that cannot be fetched is the reason when it is not there, and the repository could not be reached', Webfonts::fetch( $appData, $kind ) === 'the typeface list signature could not be fetched: http 404' && $kind === 'unreachable' );

$publish( [ $entryInter, $entryFlex, $entryFira ] );
unset( $remote[Webfonts::DEFAULT_URL] );

check( '...and so is a list that cannot be', Webfonts::fetch( $appData, $kind ) === 'the typeface list could not be fetched: http 404' && $kind === 'unreachable' );

$remote[Webfonts::DEFAULT_URL] 					= str_repeat( ' ', 1024 * 1024 ). $json;
$remote[Webfonts::DEFAULT_URL. '.sig'] 	= designSign( $remote[Webfonts::DEFAULT_URL], $webPrivate );

check( '...a list over a megabyte is not read', str_contains( (string) Webfonts::fetch( $appData, $kind ), 'the typeface list could not be fetched: the answer exceeds 1048576 bytes' ) === true && $kind === 'unreachable' );

$remote[Webfonts::DEFAULT_URL] 					= designList( [ $entryInter, array_replace( $entryFlex, [ 'license' => 'MIT' ] ) ] );
$remote[Webfonts::DEFAULT_URL. '.sig'] 	= designSign( $remote[Webfonts::DEFAULT_URL], $webPrivate );

check( '...a signed list that is not what format 1 says is refused with the reason, about its format, and the list that was kept stays', str_contains( (string) Webfonts::fetch( $appData, $kind ), 'typeface list entry 1: "license"' ) === true && $kind === 'parse'
	&& ( Webfonts::cached( $appData )['families'] ?? [] ) === $fetched );
check( '...the kinds of refusal are the five the panel has words for',
	Webfonts::REFUSALS === [ 'off', 'nokey', 'unreachable', 'signature', 'parse' ] );

// An address with a token in it keeps its query: the signature is at the end of the path
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'https://webfonts.test/list.json?token=abc' ] );
$remote['https://webfonts.test/list.json?token=abc'] 				= $json;
$remote['https://webfonts.test/list.json.sig?token=abc'] 		= designSign( $json, $webPrivate );
$requests = [];

check( 'a list behind a token is fetched, and its signature is looked for at the end of the path and not of the address',
	is_array( Webfonts::fetch( $appData ) ) === true && array_column( $requests, 'url' ) === [ 'https://webfonts.test/list.json?token=abc', 'https://webfonts.test/list.json.sig?token=abc' ] );
check( '...what was fetched under one address is not the list of another: the cache reads null once the address is changed, and again when it is back',
	Webfonts::cached( $appData ) !== null && \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL ] ) === [] && Webfonts::cached( $appData ) === null
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => 'https://webfonts.test/list.json?token=abc' ] ) === [] && Webfonts::cached( $appData ) !== null
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => '' ] ) === [] && Webfonts::cached( $appData ) === null
	&& \Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL ] ) === [] );

$publish( [ $entryInter, $entryFlex, $entryFira ] );
Webfonts::fetch( $appData );
$stored = \Nino\Filesystem::getFileContent( $appData, Webfonts::CACHE, [] );
$stored['families'][1]['slug'] = '../roboto-flex';
\Nino\Filesystem::putFileContent( $appData, Webfonts::CACHE, $stored );

check( 'a cache somebody wrote by hand is held to the rules of the list as well: an entry with a slug that climbs is no cache', Webfonts::cached( $appData ) === null );

Webfonts::fetch( $appData );

// --- A family installed whole ------------------------------------------------------------

check( 'before anything is installed the families are the four the feature brings, and nothing is listed as installed or as broken',
	array_keys( Fonts::families( $appData ) ) === [ 'open-sans', 'system-sans', 'system-serif', 'system-mono' ] && Fonts::installed( $appData ) === [] && Fonts::broken( $appData ) === []
	&& is_dir( $fontsDir ) === false && str_ends_with( $fontsDir, '/public/assets/fonts' ) === true );

$requests 	= [];
$done 			= Webfonts::install( $appData, 'inter' );

check( 'install() downloads the archive of the entry - with a cap of one byte more than the entry says, so that a larger body is seen - and puts the family below assets/fonts/ of the public root', $done === true
	&& array_column( $requests, 'url' ) === [ $entryInter['archive'] ] && $requests[0]['options']['maxBytes'] === $entryInter['size'] + 1 && is_dir( $fontsDir. '/inter' ) === true );

$found = array_values( array_diff( scandir( $fontsDir. '/inter' ) ?: [], [ '.', '..' ] ) );
sort( $found );

check( '...exactly the files of the archive and no others: its font.json, its LICENSE and the four woff2 files, every one as the archive had it',
	$found === [ '400-italic.woff2', '400.woff2', '700-italic.woff2', '700.woff2', 'LICENSE', 'font.json' ]
	&& file_get_contents( $fontsDir. '/inter/400.woff2' ) === 'wOF2inter/400.woff2' && file_get_contents( $fontsDir. '/inter/700-italic.woff2' ) === 'wOF2inter/700-italic.woff2'
	&& json_decode( (string) file_get_contents( $fontsDir. '/inter/font.json' ), true ) === $inter && trim( (string) file_get_contents( $fontsDir. '/inter/LICENSE' ) ) === 'The licence text of Inter'
	&& decoct( fileperms( $fontsDir. '/inter/400.woff2' ) & 0777 ) === '644' && decoct( fileperms( $fontsDir. '/inter' ) & 0777 ) === '755' );
check( '...the staging directory below data/ is empty again, and nothing of the archive stays in the private root', designStagingClean( $staging ) === true && is_dir( $staging. '/inter' ) === false );

$families = Fonts::families( $appData );

check( 'Fonts::families() lists it after the four it brings, with the name, the category, the weights, the files with their urls, and where it came from',
	array_keys( $families ) === [ 'open-sans', 'system-sans', 'system-serif', 'system-mono', 'inter' ]
	&& $families['inter'] === [
		'name' => 'Inter', 'category' => 'sans', 'weights' => [ 400, 700 ], 'axis' => [],
		'files' => [
			[ 'weight' => 400, 'style' => 'normal', 'url' => '[[/nino/public]]/assets/fonts/inter/400.woff2' ],
			[ 'weight' => 700, 'style' => 'normal', 'url' => '[[/nino/public]]/assets/fonts/inter/700.woff2' ],
			[ 'weight' => 400, 'style' => 'italic', 'url' => '[[/nino/public]]/assets/fonts/inter/400-italic.woff2' ],
			[ 'weight' => 700, 'style' => 'italic', 'url' => '[[/nino/public]]/assets/fonts/inter/700-italic.woff2' ],
		],
		'installed' => [ 'version' => '4.1', 'license' => 'OFL-1.1', 'source' => 'https://github.com/rsms/inter' ],
	] && array_keys( Fonts::installed( $appData ) ) === [ 'inter' ] );

// The fill is what every stylesheet of a project carries for its public directory, and the bundle writes the directory in for it
$urlOf = str_replace( '[[/nino/public]]', \Nino\Filesystem::getPublicDir( $appData ), $families['inter']['files'][1]['url'] );

check( '...and the url it writes into the stylesheet, once the bundle has put the public directory for the fill - as it does for the family the wizard delivers - is a file of the public root',
	$urlOf === '/public/assets/fonts/inter/700.woff2' && is_file( \Nino\Filesystem::getPublicPath( $appData ). substr( $urlOf, strlen( '/public' ) ) ) === true
	&& $families['open-sans']['weights'] === [ 300, 400, 700 ]
	&& array_map( static fn( array $file ): array => [ $file['weight'], $file['style'], str_replace( '[[/nino/public]]', '/public', $file['url'] ) ], $families['open-sans']['files'] ) === [
		[ 300, 'normal', '/public/fonts/open-sans-light.woff2' ], [ 400, 'normal', '/public/fonts/open-sans-regular.woff2' ], [ 700, 'normal', '/public/fonts/open-sans-bold.woff2' ],
	] );
check( '...every role may be given it but the code, which is given the monospace ones; a role that is given it keeps it',
	in_array( 'inter', Fonts::options( $appData, 'title' ), true ) === true && in_array( 'inter', Fonts::options( $appData, 'button' ), true ) === true
	&& in_array( 'inter', Fonts::options( $appData, 'code' ), true ) === false
	&& Setup::normalize( $appData, [ 'fonts' => [ 'title' => [ 'family' => 'inter' ], 'code' => [ 'family' => 'inter' ] ] ], $library )['fonts']['title']['family'] === 'inter'
	&& Setup::normalize( $appData, [ 'fonts' => [ 'code' => [ 'family' => 'inter' ] ] ], $library )['fonts']['code']['family'] === '' );

$rolesOfInter = static fn( array $raw ): array => designDeclarations( designBlock( designPlain( Fonts::css( $appData, Setup::normalize( $appData, $raw, $library )['fonts'] ) ), ':root {' ) );

$typeOfInter = designPlain( Fonts::css( $appData, Setup::normalize( $appData, [ 'fonts' => [ 'title' => [ 'family' => 'inter', 'weight' => 'bold' ], 'text' => [ 'family' => 'inter', 'weight' => 'light' ] ] ], $library )['fonts'] ) );

check( 'Fonts::css() writes a rule for each of its files - four, once, for the two roles that use it, beside the three of the Open Sans the others still have - and names it between quotes with the stack of the role behind it',
	substr_count( $typeOfInter, "font-family: 'Inter'" ) === 4 && substr_count( $typeOfInter, '@font-face' ) === 7
	&& str_contains( $typeOfInter, "font-style: italic;\n\tfont-weight: 700;\n\tfont-display: swap;\n\tsrc: url('[[/nino/public]]/assets/fonts/inter/700-italic.woff2') format('woff2');" ) === true
	&& ( $rolesOfInter( [ 'fonts' => [ 'title' => [ 'family' => 'inter' ] ] ] )['--fontfamily-title'] ?? '' ) === '"Inter", '. $sans
	&& Fonts::line( $appData, 'title', 'inter' ) === '"Inter", '. $sans && Fonts::line( $appData, 'code', 'inter' ) === '"Inter", '. $mono );
check( '...and the weight of a word is the weight it has nearest: light the 400, normal the 400, bold the 700',
	[ Fonts::weight( $appData, 'inter', 'light' ), Fonts::weight( $appData, 'inter', 'normal' ), Fonts::weight( $appData, 'inter', 'bold' ) ] === [ '400', '400', '700' ]
	&& ( $rolesOfInter( [ 'fonts' => [ 'title' => [ 'family' => 'inter', 'weight' => 'bold' ], 'text' => [ 'family' => 'inter', 'weight' => 'light' ] ] ] )['--font-title-weight'] ?? '' ) === '700' );

$sheetWithInter = Compiler::compile( $appData, Setup::normalize( $appData, [ 'fonts' => [ 'title' => [ 'family' => 'inter' ] ] ], $library ), $library );

check( 'a compiled sheet that has a role set in it carries its rules in the type section, and names it in the header - the sheet at the defaults is not changed by any of this',
	str_contains( designSection( $sheetWithInter, 3, Compiler::SECTION ), "src: url('[[/nino/public]]/assets/fonts/inter/400.woff2') format('woff2');" ) === true
	&& preg_match( '/^ \*   title      Inter, bold$/m', $sheetWithInter ) === 1
	&& str_contains( Compiler::compile( $appData, Setup::normalize( $appData, [], $library ), $library ), 'assets/fonts' ) === false );

// A family with a weight axis: one file for all the weights
$requests = [];

check( 'a family whose files carry a weight axis installs the same way', Webfonts::install( $appData, 'roboto-flex' ) === true && is_file( $fontsDir. '/roboto-flex/100-900.woff2' ) === true
	&& is_file( $fontsDir. '/roboto-flex/100-900-italic.woff2' ) === true );

$flexFamily = Fonts::families( $appData )['roboto-flex'];
$typeOfFlex = designPlain( Fonts::css( $appData, Setup::normalize( $appData, [ 'fonts' => [ 'title' => [ 'family' => 'roboto-flex', 'weight' => 'bold' ] ] ], $library )['fonts'] ) );

check( '...lists no weight of its own and remembers the axis, and its files carry the two numbers as their weight', $flexFamily['weights'] === [] && $flexFamily['axis'] === [ 100, 900 ]
	&& $flexFamily['files'][0]['weight'] === '100 900' && $flexFamily['files'][0]['url'] === '[[/nino/public]]/assets/fonts/roboto-flex/100-900.woff2' );
check( '...and the rule of a file that carries the axis writes the two numbers, font-weight: 100 900, for the normal style and the italic alike - and no rule has a weight of a single number',
	substr_count( $typeOfFlex, "font-family: 'Roboto Flex'" ) === 2 && substr_count( $typeOfFlex, "font-weight: 100 900;" ) === 2 && str_contains( $typeOfFlex, "font-style: italic;\n\tfont-weight: 100 900;" ) === true );
check( '...a word is the weight it aims at - 300, 500, 800 - because the axis has it; and a word the axis does not reach is the nearer end of it',
	[ Fonts::weight( $appData, 'roboto-flex', 'light' ), Fonts::weight( $appData, 'roboto-flex', 'normal' ), Fonts::weight( $appData, 'roboto-flex', 'bold' ) ] === [ '300', '500', '800' ]
	&& ( designDeclarations( designBlock( $typeOfFlex, ':root {' ) )['--font-title-weight'] ?? '' ) === '800' );

// Directories as a hand leaves them: an axis that stops short of the words, and one that begins after them
foreach( [ 'axis-narrow' => '400 700', 'axis-heavy' => '600 900', 'axis-light' => '100 250' ] as $slug => $axis ) {
	mkdir( $fontsDir. '/'. $slug, 0755, true );
	file_put_contents( $fontsDir. '/'. $slug. '/font.json', json_encode( designManifest( $slug, ucwords( str_replace( '-', ' ', $slug ) ), [ [ $axis, 'normal' ] ] ) ) );
}

check( '...400 to 700 gives light 400 and bold 700; 600 to 900 gives light and normal the 600; 100 to 250 gives every word the 250',
	[ Fonts::weight( $appData, 'axis-narrow', 'light' ), Fonts::weight( $appData, 'axis-narrow', 'normal' ), Fonts::weight( $appData, 'axis-narrow', 'bold' ) ] === [ '400', '500', '700' ]
	&& [ Fonts::weight( $appData, 'axis-heavy', 'light' ), Fonts::weight( $appData, 'axis-heavy', 'normal' ), Fonts::weight( $appData, 'axis-heavy', 'bold' ) ] === [ '600', '600', '800' ]
	&& [ Fonts::weight( $appData, 'axis-light', 'light' ), Fonts::weight( $appData, 'axis-light', 'normal' ), Fonts::weight( $appData, 'axis-light', 'bold' ) ] === [ '250', '250', '250' ] );

Webfonts::remove( $appData, 'axis-narrow' );
Webfonts::remove( $appData, 'axis-heavy' );
Webfonts::remove( $appData, 'axis-light' );

check( 'a monospace family is given to the code, and only a monospace one', Webfonts::install( $appData, 'fira-code' ) === true
	&& in_array( 'fira-code', Fonts::options( $appData, 'code' ), true ) === true && in_array( 'fira-code', Fonts::options( $appData, 'title' ), true ) === true
	&& Setup::normalize( $appData, [ 'fonts' => [ 'code' => [ 'family' => 'fira-code' ] ] ], $library )['fonts']['code']['family'] === 'fira-code'
	&& Fonts::line( $appData, 'code', 'fira-code' ) === '"Fira Code", '. $mono );

// --- What an archive is refused for ----------------------------------------------------------------

/**
 *	Publish a list of one entry for an archive that is not what it says, and install it: the answer, and whether
 *	anything of it is where a family goes
 */
$attempt = static function( string $slug, string $archive, array $entryMore = [], ?array $manifest = null ) use ( &$remote, $publish, &$appData, $staging, $fontsDir ): array {

	$manifest = $manifest ?? designManifest( $slug, ucfirst( $slug ), [ [ 400, 'normal' ] ] );
	$entry 		= designListEntry( $manifest, $archive, $entryMore );

	$remote[$entry['archive']] = $archive;
	$publish( [ $entry ] );
	Webfonts::fetch( $appData );

	$answer = Webfonts::install( $appData, $slug );

	return [ $answer, is_dir( $fontsDir. '/'. $slug ) === true, designStagingClean( $staging ) ];
};

$good 		= designManifest( 'sample', 'Sample', [ [ 400, 'normal' ], [ 700, 'normal' ] ] );
$goodFiles = designArchiveFiles( $good );
$refusals = [];

ninoWarnings();

$digest = designTarGz( $goodFiles );
[ $answer, $there, $clean ] = $attempt( 'sample', $digest, [ 'sha256' => str_repeat( 'a', 64 ) ], $good );
$refusals['a digest that is not the archive\'s'] = [ $answer === 'the archive does not match what the list promised', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', $digest, [ 'size' => strlen( $digest ) - 1 ], $good );
$refusals['a size that is not the archive\'s'] = [ $answer === 'the archive does not match what the list promised', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', $digest . 'x', [ 'sha256' => hash( 'sha256', $digest ), 'size' => strlen( $digest ) ], $good );
$refusals['a body one byte longer than the size says'] = [ $answer === 'the archive does not match what the list promised', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', 'this is not an archive', [], $good );
$refusals['bytes that are no archive'] = [ str_starts_with( $answer, 'the archive could not be read' ), $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'evil/' => null, 'evil/x.woff2' => 'wOF2' ] ), [], $good );
$refusals['a second directory beside the family'] = [ str_contains( (string) $answer, 'outside "sample/"' ), $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/../evil.txt' => 'x' ] ), [], $good );
$refusals['a path that climbs'] = [ $answer === 'the archive unpacked to more than the directory "sample"', $there, $clean && is_file( $fontsDir. '/evil.txt' ) === false && is_file( dirname( $fontsDir ). '/evil.txt' ) === false ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/a\\b.woff2' => 'wOF2' ] ), [], $good );
$refusals['a name with a backslash'] = [ str_starts_with( (string) $answer, 'the archive could not be read' ), $there, $clean ];

// PharData folds a dot into the directory it is in, and the file that is then there is one font.json does not name
[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/./extra.woff2' => 'wOF2' ] ), [], $good );
$refusals['a dot in a path'] = [ $answer === 'the archive holds "extra.woff2", which font.json does not name', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/link.woff2' => [ 'link' => '/etc/passwd' ] ] ), [], $good );
$refusals['a link'] = [ str_contains( (string) $answer, 'which is neither a file nor a directory' ), $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/hard.woff2' => [ 'hardlink' => 'sample/400.woff2' ] ] ), [], $good );
$refusals['a hard link'] = [ str_starts_with( (string) $answer, 'the archive could not be read' ), $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( [ 'sample' => 'a file where the directory should be' ] ), [], $good );
$refusals['a file where the directory should be'] = [ $answer === 'the archive holds "sample" as a file, not a directory', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( [ 'sample/font.json' => '{}' ] ), [], $good );
$refusals['a family with no directory entry and no files - a font.json that says nothing'] = [ str_contains( (string) $answer, 'the font.json of the archive:' ), $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_replace( $goodFiles, [ 'sample/400.woff2' => 'RIFF not a font' ] ) ), [], $good );
$refusals['a file that is not a woff2 file'] = [ $answer === '"400.woff2" is no woff2 file - it does not begin with wOF2', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_diff_key( $goodFiles, [ 'sample/700.woff2' => 1 ] ) ), [], $good );
$refusals['a font.json that names a file that is not there'] = [ $answer === 'font.json names "700.woff2", which the archive does not hold', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/extra.txt' => 'more' ] ), [], $good );
$refusals['a file font.json does not name'] = [ $answer === 'the archive holds "extra.txt", which font.json does not name', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( $goodFiles + [ 'sample/sub/' => null, 'sample/sub/x.txt' => 'x' ] ), [], $good );
$refusals['a directory inside the family'] = [ $answer === 'the archive holds "sub", which font.json does not name', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_diff_key( $goodFiles, [ 'sample/LICENSE' => 1 ] ) ), [], $good );
$refusals['no LICENSE'] = [ $answer === 'the archive holds no LICENSE', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_replace( $goodFiles, [ 'sample/LICENSE' => "  \n\t" ] ) ), [], $good );
$refusals['a LICENSE that says nothing'] = [ $answer === 'the LICENSE of the archive is empty', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_replace( $goodFiles, [ 'sample/font.json' => json_encode( array_replace( $good, [ 'version' => '9.9' ] ) ) ] ) ), [], $good );
$refusals['a font.json that is another version than the list promised'] = [ $answer === 'the font.json of the archive says another "version" than the list promised', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_replace( $goodFiles, [ 'sample/font.json' => json_encode( array_replace( $good, [ 'license' => 'MIT' ] ) ) ] ) ), [], $good );
$refusals['a font.json with a licence the list does not know'] = [ str_starts_with( (string) $answer, 'the font.json of the archive: "license"' ), $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_replace( $goodFiles, [ 'sample/font.json' => 'not json' ] ) ), [], $good );
$refusals['a font.json that is no json'] = [ $answer === 'the font.json of the archive is not valid json', $there, $clean ];

[ $answer, $there, $clean ] = $attempt( 'sample', designTarGz( array_diff_key( $goodFiles, [ 'sample/font.json' => 1 ] ) ), [], $good );
$refusals['no font.json'] = [ $answer === 'the archive holds no font.json', $there, $clean ];

$notRefused = array_keys( array_filter( $refusals, static fn( array $result ): bool => $result[0] !== true ) );
$left 			= array_keys( array_filter( $refusals, static fn( array $result ): bool => $result[1] !== false ) );
$unclean 		= array_keys( array_filter( $refusals, static fn( array $result ): bool => $result[2] !== true ) );

check( 'an archive is refused for each of these, and the answer says which: '. count( $refusals ). ' of them'. ( $notRefused === [] ? '' : ' - not so for: '. implode( ', ', $notRefused ) ), $notRefused === [] );
check( '...and nothing of any of them is where a family goes'. ( $left === [] ? '' : ' - there is something for: '. implode( ', ', $left ) ), $left === [] );
check( '...and the staging directory is empty after every one of them'. ( $unclean === [] ? '' : ' - not so for: '. implode( ', ', $unclean ) ), $unclean === [] );
check( '...none of them raised a warning, and none of them touched the families that were there', ninoWarnings() === [] && is_file( $fontsDir. '/inter/400.woff2' ) === true && is_file( $fontsDir. '/fira-code/400.woff2' ) === true );

$publish( [ $entryInter, $entryFlex, $entryFira ] );
Webfonts::fetch( $appData );

check( 'a slug the list does not have is not installed, and neither is one that is none, or one of the families the feature brings',
	Webfonts::install( $appData, 'nowhere' ) === 'the typeface "nowhere" is not in the list - load the list first'
	&& Webfonts::install( $appData, '../inter' ) === 'that is no slug of a typeface' && Webfonts::install( $appData, '' ) === 'that is no slug of a typeface'
	&& Webfonts::install( $appData, 'Inter' ) === 'that is no slug of a typeface' && is_dir( $fontsDir. '/nowhere' ) === false );

$bringing = designManifest( 'open-sans', 'Open Sans', [ [ 700, 'normal' ] ] );
$bringingArchive = designTarGz( designArchiveFiles( $bringing ) );
$remote['https://webfonts.test/open-sans-1.0.tar.gz'] = $bringingArchive;
$publish( [ designListEntry( $bringing, $bringingArchive ) ] );
Webfonts::fetch( $appData );

check( '...a family of the slug of one the feature brings is not installed: it would stand in the select under the one that is there already',
	Webfonts::install( $appData, 'open-sans' ) === 'the slug "open-sans" is one of the families the feature brings' && is_dir( $fontsDir. '/open-sans' ) === false );

// An update replaces the whole family
$inter2 		= designManifest( 'inter', 'Inter', [ [ 400, 'normal' ], [ 700, 'normal' ] ], [ 'version' => '4.2', 'source' => 'https://github.com/rsms/inter' ] );
$archive2 	= designTarGz( designArchiveFiles( $inter2 ) );
$entry2 		= designListEntry( $inter2, $archive2 );

$remote[$entry2['archive']] = $archive2;
$publish( [ $entry2, $entryFlex, $entryFira ] );
Webfonts::fetch( $appData );

check( 'an update replaces the whole family: the version of the list is installed, and the files of the version before that it does not have - the italics - are gone with its directory', Webfonts::install( $appData, 'inter' ) === true
	&& array_values( array_diff( scandir( $fontsDir. '/inter' ) ?: [], [ '.', '..' ] ) ) === [ '400.woff2', '700.woff2', 'LICENSE', 'font.json' ]
	&& Fonts::installed( $appData )['inter']['installed']['version'] === '4.2' && count( Fonts::installed( $appData )['inter']['files'] ) === 2
	&& designStagingClean( $staging ) === true );

// Another run of the same family, at work in a staging directory: an install has one of its own and leaves that one alone
mkdir( $staging. '/inter', 0755, true );
file_put_contents( $staging. '/inter/archive-0123456789ab.tar.gz', 'the archive of a run that is not over' );

check( 'an install leaves the staging directory of another run of the same family as it is - its own has a name of its own, and is gone again',
	Webfonts::install( $appData, 'inter' ) === true && is_file( $staging. '/inter/archive-0123456789ab.tar.gz' ) === true && ( scandir( $staging ) ?: [] ) === [ '.', '..', 'inter' ] );

\Nino\Filesystem::removeDir( $staging. '/inter' );

// An update that does not hold leaves the version that was installed
$broken2 = designTarGz( array_replace( designArchiveFiles( designManifest( 'inter', 'Inter', [ [ 400, 'normal' ] ], [ 'version' => '4.3' ] ) ), [ 'inter/400.woff2' => 'not a font' ] ) );
$entry3 	= designListEntry( designManifest( 'inter', 'Inter', [ [ 400, 'normal' ] ], [ 'version' => '4.3' ] ), $broken2 );

$remote[$entry3['archive']] = $broken2;
$publish( [ $entry3, $entryFlex, $entryFira ] );
Webfonts::fetch( $appData );

check( '...and one that is refused leaves the family as it was: the version before still there, whole', is_string( Webfonts::install( $appData, 'inter' ) ) === true
	&& Fonts::installed( $appData )['inter']['installed']['version'] === '4.2' && is_file( $fontsDir. '/inter/700.woff2' ) === true && designStagingClean( $staging ) === true );

$publish( [ $entry2, $entryFlex, $entryFira ] );
Webfonts::fetch( $appData );

// --- A family nothing uses may go, one a role uses may not ----------------------------------------

$usedBy = static function( array $roles ) use ( &$appData, $library ): void {

	$fonts = [];

	foreach( $roles as $role )
		$fonts[$role] = [ 'family' => 'inter' ];

	Setup::write( $appData, Setup::normalize( $appData, [ 'fonts' => $fonts ], $library ) );
};

check( 'nothing uses it, so far: no role has it, and uses() says so', Webfonts::uses( $appData, 'inter' ) === [] && Webfonts::usage( $appData ) === [ 'open-sans' => [ 'title', 'subtitle', 'text', 'button' ] ] );

$usedBy( [ 'title' ] );

check( 'remove() refuses a family while a role of the saved setup uses it, and names the role', Webfonts::uses( $appData, 'inter' ) === [ 'title' ] && Webfonts::remove( $appData, 'inter' ) === 'used by title'
	&& is_file( $fontsDir. '/inter/400.woff2' ) === true );

$usedBy( [ 'text', 'title', 'button' ] );

check( '...every role that does, in the order of the roles', Webfonts::uses( $appData, 'inter' ) === [ 'title', 'text', 'button' ] && Webfonts::remove( $appData, 'inter' ) === 'used by title, text, button' );

$usedBy( [] );

check( '...and not a role that has another family, or a family that is not one - the setup is read the way the panel reads it',
	Webfonts::uses( $appData, 'inter' ) === [] && Webfonts::uses( $appData, 'nowhere' ) === [] && Webfonts::uses( $appData, '' ) === [] );

check( 'an update is allowed for a family a role uses: the files follow - the role keeps the family', ( static function() use ( $usedBy, &$appData, $entryFlex, $entryFira, $publish, &$remote ): bool {

	$usedBy( [ 'title' ] );

	$next 		= designManifest( 'inter', 'Inter', [ [ 400, 'normal' ], [ 600, 'normal' ], [ 700, 'normal' ] ], [ 'version' => '4.4', 'source' => 'https://github.com/rsms/inter' ] );
	$archive 	= designTarGz( designArchiveFiles( $next ) );
	$entry 		= designListEntry( $next, $archive );

	$remote[$entry['archive']] = $archive;
	$publish( [ $entry, $entryFlex, $entryFira ] );
	Webfonts::fetch( $appData );

	$done = Webfonts::install( $appData, 'inter' );
	$kept = Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['fonts']['title']['family'] === 'inter';

	return $done === true && $kept === true && Fonts::installed( $appData )['inter']['installed']['version'] === '4.4' && Fonts::installed( $appData )['inter']['weights'] === [ 400, 600, 700 ];
} )() );

$usedBy( [] );

check( 'a family no role uses is removed, with its directory and everything in it; the families that are installed are what is left', Webfonts::remove( $appData, 'inter' ) === true
	&& is_dir( $fontsDir. '/inter' ) === false && array_keys( Fonts::installed( $appData ) ) === [ 'fira-code', 'roboto-flex' ]
	&& in_array( 'inter', Fonts::options( $appData, 'title' ), true ) === false );
check( '...a second time there is nothing to remove, and neither for a name that is none, or one that climbs',
	Webfonts::remove( $appData, 'inter' ) === 'the typeface "inter" is not installed' && Webfonts::remove( $appData, 'nowhere' ) === 'the typeface "nowhere" is not installed'
	&& Webfonts::remove( $appData, '../fonts' ) === 'that is no slug of a typeface' && Webfonts::remove( $appData, '' ) === 'that is no slug of a typeface'
	&& is_dir( $fontsDir ) === true );
check( '...a role whose family is gone is its default again - the setup is normalised against what is installed', ( static function() use ( $usedBy, &$appData, $library ): bool {

	Webfonts::install( $appData, 'inter' );
	$usedBy( [ 'subtitle' ] );
	$was = Setup::read( $appData, $library )['fonts']['subtitle']['family'];
	\Nino\Filesystem::removeDir( Fonts::dir( $appData ). '/inter' );
	$now = Setup::read( $appData, $library )['fonts']['subtitle']['family'];
	$usedBy( [] );

	return $was === 'inter' && $now === Fonts::DEFAULTS['subtitle']['family'];
} )() );

// --- A directory that is none ----------------------------------------------------------------

foreach( [ 'empty-one', 'no-json', 'strange', 'other-name', 'open-sans', 'wrong-shape' ] as $slug )
	mkdir( $fontsDir. '/'. $slug, 0755, true );

file_put_contents( $fontsDir. '/no-json/font.json', 'not json' );
file_put_contents( $fontsDir. '/strange/font.json', json_encode( designManifest( 'strange', 'Str<ange', [ [ 400, 'normal' ] ] ) ) );
file_put_contents( $fontsDir. '/other-name/font.json', json_encode( designManifest( 'another-name', 'Other', [ [ 400, 'normal' ] ] ) ) );
file_put_contents( $fontsDir. '/open-sans/font.json', json_encode( designManifest( 'open-sans', 'Open Sans', [ [ 400, 'normal' ] ] ) ) );
file_put_contents( $fontsDir. '/wrong-shape/font.json', json_encode( [ 'family' => 'Wrong' ] ) );
mkdir( $fontsDir. '/Not A Slug', 0755, true );
file_put_contents( $fontsDir. '/a-file', 'not a directory' );

check( 'a directory that is no family is not listed, and broken() says why - for each one with the name of a slug: no font.json, one that is no json, a name that could reach a stylesheet, a slug that is not the directory\'s, one of the four, a manifest of no shape',
	array_keys( Fonts::installed( $appData ) ) === [ 'fira-code', 'roboto-flex' ] && array_keys( Fonts::families( $appData ) ) === [ 'open-sans', 'system-sans', 'system-serif', 'system-mono', 'fira-code', 'roboto-flex' ]
	&& Fonts::broken( $appData ) === [
		'empty-one' => 'there is no font.json', 'no-json' => 'font.json is not valid json', 'open-sans' => 'the slug is one of the families the feature brings',
		'other-name' => 'font.json names the slug "another-name", the directory is "other-name"', 'strange' => 'font.json: "family" must be a name of 1 to 64 letters, digits, spaces, dots and hyphens, beginning with a letter or a digit and with no space at its end',
		'wrong-shape' => 'font.json: "slug" must be lower-case ascii letters, digits and single hyphens, at most 64 characters',
	] );
check( '...what else lies there is nobody\'s business: a name that is no slug, a file, a link - not listed as a family, not listed as broken, not removed',
	array_keys( Fonts::broken( $appData ) ) === [ 'empty-one', 'no-json', 'open-sans', 'other-name', 'strange', 'wrong-shape' ] && Webfonts::remove( $appData, 'a-file' ) === 'the typeface "a-file" is not installed'
	&& is_dir( $fontsDir. '/Not A Slug' ) === true && is_file( $fontsDir. '/a-file' ) === true );

$outside = ninoSandboxDir( $appData ). '/outside-of-fonts';
mkdir( $outside, 0755, true );
file_put_contents( $outside. '/keep.txt', 'mine' );
symlink( $outside, $fontsDir. '/a-link' );

check( '...and a link to a directory is neither, and removing it by name removes nothing: the directory it points to is not followed', isset( Fonts::broken( $appData )['a-link'] ) === false && isset( Fonts::installed( $appData )['a-link'] ) === false
	&& Webfonts::remove( $appData, 'a-link' ) === 'the typeface "a-link" is not installed' && is_file( $outside. '/keep.txt' ) === true );

check( 'a directory that is none can be removed all the same - it uses nothing - and so can the one with a slug of the four', Webfonts::remove( $appData, 'empty-one' ) === true && Webfonts::remove( $appData, 'open-sans' ) === true
	&& is_dir( $fontsDir. '/empty-one' ) === false && is_dir( $fontsDir. '/open-sans' ) === false && is_file( $fontsDir. '/fira-code/400.woff2' ) === true );

foreach( [ 'no-json', 'strange', 'other-name', 'wrong-shape' ] as $slug )
	Webfonts::remove( $appData, $slug );

unlink( $fontsDir. '/a-link' );
unlink( $fontsDir. '/a-file' );
\Nino\Filesystem::removeDir( $fontsDir. '/Not A Slug' );

// --- The fingerprint ----------------------------------------------------------------

/*	What the fingerprint was before an installed family joined it: the setup and the bytes of the sets it names - the setup as it was written before the
	features, which had no key for what is chosen for them	*/
$beforeFamilies = static function( array $setup ) use ( $library ): string {

	$files = [];

	foreach( array_keys( Setup::PARTS ) as $printPart ) {
		$printFile 					= Setup::file( $library, $printPart, (string) ( $setup['parts'][$printPart] ?? '' ) );
		$files[$printPart] 	= [ (string) ( $setup['parts'][$printPart] ?? '' ), ( $printFile === '' || is_file( $printFile ) === false ) ? '' : (string) hash_file( 'sha256', $printFile ) ];
	}

	// ...and before the features had a key of their own in it
	unset( $setup['compiled'], $setup['features'] );

	return hash( 'sha256', serialize( [ $setup, $files ] ) );
};

$sandboxPlain = Setup::normalize( $appData, [], $library );
$usesFira = Setup::normalize( $appData, [ 'fonts' => [ 'code' => [ 'family' => 'fira-code' ] ] ], $library );
$usesFlex = Setup::normalize( $appData, [ 'fonts' => [ 'title' => [ 'family' => 'roboto-flex' ] ] ], $library );
$before = [ \Nino\Modules\Design::fingerprint( $appData, $sandboxPlain, $library ), \Nino\Modules\Design::fingerprint( $appData, $usesFira, $library ), \Nino\Modules\Design::fingerprint( $appData, $usesFlex, $library ) ];

check( 'a project that uses no family it installed fingerprints as it did - the installed families do not join the input of one that uses none', $before[0] === $beforeFamilies( $sandboxPlain )
	&& $before[1] !== $beforeFamilies( $usesFira ) && $before[2] !== $beforeFamilies( $usesFlex ) && $before[1] !== $before[2] );

// A family a role uses is updated: what the type section writes of it changes, and so does the fingerprint; a version alone does not
$firaNext 		= designManifest( 'fira-code', 'Fira Code', [ [ 400, 'normal' ], [ 700, 'normal' ] ], [ 'category' => 'mono', 'license' => 'Apache-2.0', 'version' => '2.0' ] );
$firaArchive 	= designTarGz( designArchiveFiles( $firaNext ) );
$firaEntry 		= designListEntry( $firaNext, $firaArchive );

$remote[$firaEntry['archive']] = $firaArchive;
$publish( [ $entry2, $entryFlex, $firaEntry ] );
Webfonts::fetch( $appData );
Webfonts::install( $appData, 'fira-code' );

check( '...and an update of a family a role uses that changes its files changes the fingerprint, so that the panel reads "saved, not applied" for it - the one of a family that is not used does not',
	\Nino\Modules\Design::fingerprint( $appData, $usesFira, $library ) !== $before[1] && \Nino\Modules\Design::fingerprint( $appData, $usesFlex, $library ) === $before[2]
	&& \Nino\Modules\Design::fingerprint( $appData, $sandboxPlain, $library ) === $before[0] );

$firaSame 		= designManifest( 'fira-code', 'Fira Code', [ [ 400, 'normal' ], [ 700, 'normal' ] ], [ 'category' => 'mono', 'license' => 'Apache-2.0', 'version' => '2.1' ] );
$firaArchive 	= designTarGz( designArchiveFiles( $firaSame ) );
$firaEntry2 	= designListEntry( $firaSame, $firaArchive );
$sheetBefore 	= \Nino\Modules\Design::fingerprint( $appData, $usesFira, $library );

$remote[$firaEntry2['archive']] = $firaArchive;
$publish( [ $entry2, $entryFlex, $firaEntry2 ] );
Webfonts::fetch( $appData );
Webfonts::install( $appData, 'fira-code' );

check( '...a version alone changes nothing a stylesheet says, and so nothing the fingerprint hashes', \Nino\Modules\Design::fingerprint( $appData, $usesFira, $library ) === $sheetBefore
	&& Fonts::installed( $appData )['fira-code']['installed']['version'] === '2.1' );

// Put the sandbox back as the sections after this one found it
foreach( array_keys( Fonts::installed( $appData ) ) as $slug )
	Webfonts::remove( $appData, $slug );

\Nino\Filesystem::removeDir( dirname( $fontsDir ) );
@unlink( $cacheFile );
@unlink( \Nino\Filesystem::path( $appData, Setup::PATH ) );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => '' ] );
unset( $appData['./nino/filesystem/cache'] );

check( 'and the sandbox is as it was: no family, no directory for them, no cache, no staging - and the settings are the defaults again',
	Fonts::installed( $appData ) === [] && Fonts::broken( $appData ) === [] && is_dir( dirname( $fontsDir ) ) === false && is_file( $cacheFile ) === false && designStagingClean( $staging ) === true
	&& Webfonts::url( $appData ) === Webfonts::DEFAULT_URL && Webfonts::key( $appData ) === Webfonts::PUBLIC_KEY );

echo "\nThe page: the width, the radius, and how its sections arrive\n";

$pageOf = static fn( array $raw ): string => Compiler::page( Setup::normalize( $appData, $raw, $library ) );

/*	The radius rule of a setup as the model says it, written out from the tables and the setup alone: the length of each of the two strengths, and
	for each kind of surface its token and its marker - one rule on :root of twelve declarations, in the order of Setup::SURFACES	*/
$radiusRule = static function( array $raw ) use ( $appData, $library ): string {

	$setup = Setup::normalize( $appData, $raw, $library );
	$lines = [ '--radius-surface: '. Setup::RADIUS[$setup['radius']], '--radius-surface-alt: '. Setup::RADIUS[$setup['radius-alt']] ];

	foreach( Setup::SURFACES as $kind ) {
		$lines[] = '--radius-'. $kind. ': var(--radius-surface'. ( $setup['radii'][$kind] === 'alternative' ? '-alt' : '' ). ')';
		$lines[] = '--radius-'. $kind. '-default: '. $setup['radii'][$kind];
	}

	return ":root {\n\t". implode( ";\n\t", $lines ). ";\n}";
};
$radiusOfNothing = $radiusRule( [] );

check( 'a width of 0, a whitespace of 0 and no animation write the radius and nothing else - one rule on :root, written at the kernel\'s own values as well - and that is the whole of section 6',
	$pageOf( [] ) === $radiusOfNothing && designSection( $css, 6, Compiler::SECTION ) === sprintf( Compiler::SECTION, 6, 'the page' ). "\n". $radiusOfNothing. "\n\n" );
check( 'the four widths around the kernel\'s own are rules exactly: a reading column of at most 48rem and a contained page of 64rem with the kernel\'s own space to the sides at both sizes, and then no maximum - with that space at both sizes, and with none from 768px',
	$pageOf( [ 'width' => -2 ] ) === ":root { --grid-max-width: 48rem; --grid-gutter: var(--space-2); }\n@media (min-width: 768px) { :root { --grid-gutter: var(--space-3); } }\n". $radiusOfNothing
	&& $pageOf( [ 'width' => -1 ] ) === ":root { --grid-max-width: 64rem; --grid-gutter: var(--space-2); }\n@media (min-width: 768px) { :root { --grid-gutter: var(--space-3); } }\n". $radiusOfNothing
	&& $pageOf( [ 'width' => 1 ] ) === ":root { --grid-max-width: none; --grid-gutter: var(--space-2); }\n@media (min-width: 768px) { :root { --grid-gutter: var(--space-2); } }\n". $radiusOfNothing
	&& $pageOf( [ 'width' => 2 ] ) === ":root { --grid-max-width: none; --grid-gutter: var(--space-2); }\n@media (min-width: 768px) { :root { --grid-gutter: 0; } }\n". $radiusOfNothing );
check( '...and they are all of section 6, in the sheet: the page of the width, under its marker, and nothing after it but the next section',
	array_filter( [ -2, -1, 1, 2 ], static fn( int $width ): bool => designSection( Compiler::compile( $appData, Setup::normalize( $appData, [ 'width' => $width ], $library ), $library ), 6, Compiler::SECTION )
		!== sprintf( Compiler::SECTION, 6, 'the page' ). "\n". $pageOf( [ 'width' => $width ] ). "\n\n" ) === [] );
check( 'a whitespace of 0 is the kernel\'s own and writes nothing; the four around it write the number of Setup::WHITESPACE as one rule on :root, in section 6, beside the width and before the animation',
	$pageOf( [ 'whitespace' => 0 ] ) === $radiusOfNothing
	&& array_filter( [ -2, -1, 1, 2 ], static fn( int $air ): bool => $pageOf( [ 'whitespace' => $air ] ) !== ':root { --whitespace-scale: '. Setup::WHITESPACE[$air]. "; }\n". $radiusOfNothing ) === []
	&& array_filter( [ -2, -1, 1, 2 ], static fn( int $air ): bool => designSection( Compiler::compile( $appData, Setup::normalize( $appData, [ 'whitespace' => $air ], $library ), $library ), 6, Compiler::SECTION )
		!== sprintf( Compiler::SECTION, 6, 'the page' ). "\n". $pageOf( [ 'whitespace' => $air ] ). "\n\n" ) === []
	&& $pageOf( [ 'width' => -2, 'whitespace' => 2 ] ) === substr( $pageOf( [ 'width' => -2 ] ), 0, -strlen( $radiusOfNothing ) ). ":root { --whitespace-scale: 2; }\n". $radiusOfNothing
	&& strpos( $pageOf( [ 'whitespace' => 2, 'motion' => [ 'effect' => 'zoom' ] ] ), '--whitespace-scale' ) < strpos( $pageOf( [ 'whitespace' => 2, 'motion' => [ 'effect' => 'zoom' ] ] ), 'main {' ) );

/*	The radius, every position of the two strengths and every kind on either of them: the declarations of the page that start with --radius-, in the order
	the model says them, are the table's length for each strength and for each kind the token that points at its strength with the marker, the plain word, beside it	*/
$radiusOf = static fn( array $raw ): array => array_filter( designDeclarations( $pageOf( $raw ) ), static fn( string $name ): bool => str_starts_with( $name, '--radius-' ), ARRAY_FILTER_USE_KEY );
$radiusDrift = [];

foreach( array_keys( Setup::RADIUS ) as $step )
	foreach( [ [ 'radius' => $step ], [ 'radius-alt' => $step ], [ 'radius' => $step, 'radius-alt' => -$step ] ] as $raw ) {

		$at = $radiusOf( $raw );
		$radiusDrift[] = $at['--radius-surface'] === Setup::RADIUS[ $raw['radius'] ?? 0 ] && $at['--radius-surface-alt'] === Setup::RADIUS[ $raw['radius-alt'] ?? 1 ] ? null : json_encode( $raw );
	}

foreach( array_merge( [ [] ], array_map( static fn( string $kind ): array => [ $kind => 'alternative' ], Setup::SURFACES ), [ array_fill_keys( Setup::SURFACES, 'alternative' ) ] ) as $kinds ) {

	$at = $radiusOf( [ 'radii' => $kinds ] );

	foreach( Setup::SURFACES as $kind ) {
		$word = $kinds[$kind] ?? 'standard';
		$radiusDrift[] = ( $at['--radius-'. $kind] ?? '' ) === 'var(--radius-surface'. ( $word === 'alternative' ? '-alt' : '' ). ')' && ( $at['--radius-'. $kind. '-default'] ?? '' ) === $word ? null : json_encode( $kinds ). ' '. $kind;
	}
}

check( 'the compiled page carries the radius for every setup: the length of both strengths from Setup::RADIUS at the positions the setup holds - all five of each - and for each of the five kinds the token that points at the strength it takes, with the marker beside it, which says the same in a plain word'
	. ( array_filter( $radiusDrift ) === [] ? '' : ' - drifted: '. implode( ', ', array_filter( $radiusDrift ) ) ),
	array_filter( $radiusDrift ) === [] && count( $radiusOf( [] ) ) === 2 + 2 * count( Setup::SURFACES )
	&& array_keys( $radiusOf( [] ) ) === array_merge( [ '--radius-surface', '--radius-surface-alt' ], ...array_map( static fn( string $kind ): array => [ '--radius-'. $kind, '--radius-'. $kind. '-default' ], Setup::SURFACES ) ) );
check( '...as the one rule on :root, in section 6, after the whitespace and before the animation, and the same rule at every setup but for its values: the rule of a setup is its twelve lines and no other',
	$pageOf( [ 'radius' => 2, 'radius-alt' => -2, 'radii' => [ 'row' => 'alternative' ] ] ) === $radiusRule( [ 'radius' => 2, 'radius-alt' => -2, 'radii' => [ 'row' => 'alternative' ] ] )
	&& substr_count( $pageOf( [ 'width' => -2, 'whitespace' => 2, 'radius' => 2, 'motion' => [ 'effect' => 'zoom' ] ] ), '--radius-surface:' ) === 1
	&& strpos( $pageOf( [ 'whitespace' => 2, 'motion' => [ 'effect' => 'zoom' ] ] ), '--whitespace-scale' ) < strpos( $pageOf( [ 'whitespace' => 2, 'motion' => [ 'effect' => 'zoom' ] ] ), '--radius-surface:' )
	&& strpos( $pageOf( [ 'motion' => [ 'effect' => 'zoom' ] ] ), '--radius-surface:' ) < strpos( $pageOf( [ 'motion' => [ 'effect' => 'zoom' ] ] ), 'main {' )
	&& designSection( Compiler::compile( $appData, Setup::normalize( $appData, [ 'radius' => 1 ], $library ), $library ), 6, Compiler::SECTION )
		=== sprintf( Compiler::SECTION, 6, 'the page' ). "\n". $radiusRule( [ 'radius' => 1 ] ). "\n\n" );

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

	// The root of the frame at its desktop width of 1280px: the middle size's pair from 768px, 112.5% of the browser's 16px, which is 18px and not 16
	$frameRoot = 16 * (float) Setup::SIZES[0][1] / 100;

	check( 'the four widths around the kernel\'s own keep the gutters Nino.css has at both sizes - -2 and -1 under a row of 48rem and of 64rem, both narrower than a preview of 1280px, where the 120rem of the kernel is not - and +1 has no maximum and the small gutter at both sizes, +2 no maximum and no gutter from 768px, the kernel\'s below it, where a section has no padding to its sides',
		$ninoRootDecls['--grid-max-width'] === '120rem' && $gutterSmall > 0 && $gutterLarge > 0 && $gutterSmall < $gutterLarge
		&& array_keys( Setup::WIDTHS ) === [ -2, -1, 1, 2 ]
		&& Setup::WIDTHS[-2] === [ '48rem', 'var(--space-'. $gutterSmall. ')', 'var(--space-'. $gutterLarge. ')' ]
		&& Setup::WIDTHS[-1] === [ '64rem', 'var(--space-'. $gutterSmall. ')', 'var(--space-'. $gutterLarge. ')' ]
		&& Setup::WIDTHS[1] === [ 'none', 'var(--space-'. $gutterSmall. ')', 'var(--space-'. $gutterSmall. ')' ]
		&& Setup::WIDTHS[2] === [ 'none', 'var(--space-'. $gutterSmall. ')', '0' ]
		&& $frameRoot === 18.0 && (float) Setup::WIDTHS[-2][0] * $frameRoot < (float) Setup::WIDTHS[-1][0] * $frameRoot
		&& (float) Setup::WIDTHS[-1][0] * $frameRoot < 1280 && (float) $ninoRootDecls['--grid-max-width'] * $frameRoot > 1280 );

	/*	Every one of the five is another row in a preview of 1280px, which is what the panel shows. At 18px to the rem the
		section keeps what Nino.css gives it from 768px to each side, so a row has at most 1280px less that twice, and its
		text the row less its gutter twice - read here out of the kernel's own numbers and the rules above, and quoted by the
		manual and by the docblock of Setup::WIDTHS	*/
	preg_match( '/\.nino-section\s*\{\s*padding:\s*calc\(\s*var\(--space-5\)\s*\*\s*var\(--whitespace-scale\)\s*\)\s*calc\(\s*var\(--space-([1-6])\)\s*\*\s*var\(--whitespace-scale\)\s*\);\s*\}/', $ninoCss, $sectionPadding );

	$sectionSide	= (int) ( $sectionPadding[1] ?? 0 );
	$lengthPx			= static function( string $length ) use ( $frameRoot, $ninoRootDecls ): float {

		if( preg_match( '/^var\(--space-([1-6])\)$/', $length, $found ) === 1 )
			$length = (string) ( $ninoRootDecls['--space-'. $found[1]] ?? '0' );

		return (float) $length * $frameRoot;
	};
	$room					= 1280.0 - 2 * $lengthPx( 'var(--space-'. $sectionSide. ')' );
	$rows					= [];
	$texts				= [];

	foreach( [ -2 => Setup::WIDTHS[-2], -1 => Setup::WIDTHS[-1], 0 => [ $ninoRootDecls['--grid-max-width'], 'var(--space-'. $gutterSmall. ')', 'var(--space-'. $gutterLarge. ')' ], 1 => Setup::WIDTHS[1], 2 => Setup::WIDTHS[2] ] as $position => $width ) {
		$rows[$position]	= $width[0] === 'none' ? $room : min( $room, (float) $width[0] * $frameRoot );
		$texts[$position]	= $rows[$position] - 2 * $lengthPx( $width[2] );
	}

	check( 'in a preview of 1280px every one of the five widths is another row: 864, 1152, 1208, 1208 and 1208px of row, and 792, 1080, 1136, 1172 and 1208px of text - the section keeps '. $lengthPx( 'var(--space-'. $sectionSide. ')' ). 'px of its own to each side',
		$sectionSide === 3 && array_values( $rows ) === [ 864.0, 1152.0, 1208.0, 1208.0, 1208.0 ] && array_values( $texts ) === [ 792.0, 1080.0, 1136.0, 1172.0, 1208.0 ]
		&& count( array_unique( $texts ) ) === 5 );
	check( '...which Nino.css reads as the max-width of a row (none is a value of it) and as the padding of one (0 is a value of that) - and as the max-width of the gallery too',
		preg_match( '/\.nino-grid-row\s*\{[^}]*padding:\s*0 var\(--grid-gutter\);[^}]*max-width:\s*var\(--grid-max-width\);/', $ninoCss ) === 1
		&& preg_match( '/\.nino-gallery\s*\{[^}]*max-width:\s*var\(--grid-max-width\);/', $ninoCss ) === 1 );
	check( '...and the middle size is the pair Nino.css has: --base-size is 100% and, from 768px, 112.5% - the delivered size, which the compiler writes beside the other four',
		( $ninoRootDecls['--base-size'] ?? '' ) === Setup::SIZES[0][0] && ( $ninoLarge['--base-size'] ?? '' ) === Setup::SIZES[0][1] );

	/*	The whitespace is the kernel's token, which is 1 where the page asks for nothing, and the two strengths the kernel derives from
		it are 1 there as well: the middle position writes nothing because Nino.css has it already. The strengths are said by the kernel, and
		the Design feature writes the number alone	*/
	check( '...and the middle whitespace is the number Nino.css has: --whitespace-scale is '. Setup::WHITESPACE[0]. ', and the two strengths it derives - for the gaps between pieces and for the room under text - are worked out from it, so that writing the number is all there is to do',
		( $ninoRootDecls['--whitespace-scale'] ?? '' ) === Setup::WHITESPACE[0]
		&& preg_match( '/^calc\( 1 \+ \( var\(--whitespace-scale\) - 1 \) \* [0-9.]+ \)$/', (string) ( $ninoRootDecls['--whitespace-gap'] ?? '' ) ) === 1
		&& preg_match( '/^calc\( 1 \+ \( var\(--whitespace-scale\) - 1 \) \* [0-9.]+ \)$/', (string) ( $ninoRootDecls['--whitespace-text'] ?? '' ) ) === 1
		&& array_filter( Setup::WHITESPACE, static fn( string $number ): bool => is_numeric( $number ) === false || (float) $number <= 0 ) === [] );

	/*	The four outer positions of the ladder are classes of the kernel as well - nino-whitespace--least, --less, --more and --most, on a section or a
		card, which the Builder's whitespace select writes -, and each sets the number of its position: a contract of the kernel, read at test time, so
		that a section the Builder gives a whitespace has the air the ladder of the Design names for it	*/
	$airRules = [];
	preg_match_all( '/\.nino-whitespace--(least|less|more|most)\s*\{\s*--whitespace-scale:\s*([0-9.]+);\s*\}/', $ninoCss, $airFound, PREG_SET_ORDER );
	foreach( $airFound as $airRule )
		$airRules[$airRule[1]] = $airRule[2];

	check( '...and the four outer positions are classes the kernel gives any element: .nino-whitespace--least, --less, --more and --most set --whitespace-scale to the number of the position -2, -1, +1 and +2 of Setup::WHITESPACE - the middle one is the page\'s own, and no class',
		$airRules === [ 'least' => Setup::WHITESPACE[-2], 'less' => Setup::WHITESPACE[-1], 'more' => Setup::WHITESPACE[1], 'most' => Setup::WHITESPACE[2] ] && preg_match( '/\.nino-whitespace--(?:middle|default|normal)\b/', $ninoCss ) !== 1 );

	/*	The radius is the kernel's as well, and this is the coupling to it: the two strengths and the five kinds of surface are tokens of Nino.css's :root
		(--radius-surface, --radius-surface-alt, and --radius-<kind> with its marker --radius-<kind>-default), the middle of the table is its small radius and
		+1 its large one, and the two classes that take the other strength of one element are its own. Against a kernel that does not have them these fail	*/
	$kernelKinds = [];

	foreach( $ninoRootDecls as $property => $value )
		if( preg_match( '/^--radius-([a-z]+)-default$/', $property, $found ) === 1 )
			$kernelKinds[$found[1]] = $value;

	check( 'the radius is the kernel\'s own: the middle of Setup::RADIUS is its small radius and +1 its large one, and the two strengths it declares at its :root - --radius-surface and --radius-surface-alt - are those two, so that a setup out of nothing stands at 0 and +1 and is Nino.css as it is',
		( $ninoRootDecls['--radius-small'] ?? '' ) === Setup::RADIUS[0] && ( $ninoRootDecls['--radius-large'] ?? '' ) === Setup::RADIUS[1]
		&& ( $ninoRootDecls['--radius-surface'] ?? '' ) === 'var(--radius-small)' && ( $ninoRootDecls['--radius-surface-alt'] ?? '' ) === 'var(--radius-large)' );
	check( '...and the kinds of surface are the kernel\'s: one marker --radius-<kind>-default at its :root for each of Setup::SURFACES, in that order, and for no other kind - every one the word standard - and --radius-<kind> pointing at the standard strength: what the compiler writes over them is the same twelve declarations',
		array_keys( $kernelKinds ) === Setup::SURFACES && array_values( array_unique( $kernelKinds ) ) === [ Setup::RADIUS_WORDS[0] ]
		&& array_filter( Setup::SURFACES, static fn( string $kind ): bool => ( $ninoRootDecls['--radius-'. $kind] ?? '' ) !== 'var(--radius-surface)' ) === []
		&& array_keys( array_filter( designDeclarations( $radiusOfNothing ), static fn( string $value, string $name ): bool => str_ends_with( $name, '-default' ) === false && str_starts_with( $name, '--radius-surface' ) === false, ARRAY_FILTER_USE_BOTH ) )
			=== array_map( static fn( string $kind ): string => '--radius-'. $kind, Setup::SURFACES ) );

	$radiusClasses = [];
	preg_match_all( '/(\.nino-radius(?:-alt)?)\s*\{\s*--radius-([a-z]+):\s*var\(--radius-surface(-alt)?\);\s*\}/', $ninoCss, $radiusRules, PREG_SET_ORDER );

	foreach( $radiusRules as $radiusRule )
		$radiusClasses[$radiusRule[2]][ $radiusRule[1] === '.nino-radius-alt' ? Setup::RADIUS_WORDS[1] : Setup::RADIUS_WORDS[0] ] = ( $radiusRule[3] ?? '' ) === '-alt' ? Setup::RADIUS_WORDS[1] : Setup::RADIUS_WORDS[0];

	check( '...and the class that puts one element on the other strength is the kernel\'s for each kind: nino-radius sets --radius-<kind> to the standard strength and nino-radius-alt to the alternative one, a rule of Nino.css for every one of the kinds and for none that is not - which is what the Builder writes, and the Design only reads',
		array_keys( $radiusClasses ) === Setup::SURFACES && array_filter( $radiusClasses, static fn( array $pair ): bool => $pair !== [ 'standard' => 'standard', 'alternative' => 'alternative' ] ) === [] );

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
		&& $pageOf( [ 'motion' => [ 'effect' => '', 'strength' => 'hard', 'speed' => 'fast' ] ] ) === $radiusOfNothing );
	check( '...an effect with no strength is the middle one, and the rule is on main - where every animation below it inherits it from',
		$sorted( designDeclarations( designBlock( designPlain( $pageOf( [ 'motion' => [ 'effect' => 'zoom' ] ] ) ), 'main {' ) ) ) === $sorted( $vpa['zoom']['medium'] )
		&& preg_match( '/^main \{$/m', $pageOf( [ 'motion' => [ 'effect' => 'zoom' ] ] ) ) === 1 );

	/*	The hover of the pictures is the handles Nino.css declares for the rules of nino-hover - the custom properties --image-hover-* on :root, in a block of their own - and the compiler writes
		some of them on main, after the animation's. HOVERS is held in code, so this is where it is held to the stylesheet: every property a kind writes is one the kernel declares, and the only one it
		declares and no kind writes is the time the change takes, which stays the kernel's. A handle the kernel gains is one the table has to decide about	*/
	preg_match_all( '/:root\s*\{([^}]*)\}/', designPlain( $ninoCss ), $rootBlocks );

	$kernelHover = [];

	foreach( $rootBlocks[1] as $rootBlock )
		$kernelHover += array_filter( designDeclarations( $rootBlock ), static fn( string $property ): bool => str_starts_with( $property, '--image-hover-' ), ARRAY_FILTER_USE_KEY );

	$hoverWritten = [];

	foreach( Setup::HOVERS as $hoverStrengths )
		foreach( $hoverStrengths as $hoverDeclarations )
			$hoverWritten = array_merge( $hoverWritten, array_keys( $hoverDeclarations ) );

	$hoverWritten = array_values( array_unique( $hoverWritten ) );

	check( 'the hover of the pictures names only handles Nino.css declares on :root, and all of them but the time the change takes - which stays the kernel\'s and is never written',
		$hoverWritten !== [] && array_diff( $hoverWritten, array_keys( $kernelHover ) ) === [] && array_values( array_diff( array_keys( $kernelHover ), $hoverWritten ) ) === [ '--image-hover-duration' ] );
}

/*	The hover of the pictures is written into the one rule on main that the animation writes, after the animation's: the properties of Setup::HOVERS for the kind and the strength - the middle one
	where there is none -, nothing for no kind, the rule by itself where there is no effect, and no rule on main where neither is set	*/
$mainOf = static function( array $motion ) use ( $pageOf ): array {

	$rule = designDeclarations( designBlock( designPlain( $pageOf( [ 'motion' => $motion ] ) ), 'main {' ) );

	ksort( $rule );

	return $rule;
};
$hoverDrift = [];

foreach( Setup::HOVERS as $hoverKind => $hoverStrengths )
	foreach( Setup::STRENGTHS as $hoverStrength ) {

		$wantedRule = Setup::HOVERS[$hoverKind][$hoverStrength];

		ksort( $wantedRule );

		if( $wantedRule === [] || $mainOf( [ 'hover' => $hoverKind, 'hover-strength' => $hoverStrength ] ) !== $wantedRule )
			$hoverDrift[] = $hoverKind. '-'. $hoverStrength;
	}

check( 'every kind of hover and every strength of it writes the properties of Setup::HOVERS, as the rule on main and nothing else ('. count( Setup::HOVERS ) * count( Setup::STRENGTHS ). ' of them)'
	. ( $hoverDrift === [] ? '' : ' - drifted: '. implode( ', ', $hoverDrift ) ), $hoverDrift === [] && array_keys( Setup::HOVERS ) !== [] );
check( '...a kind with no strength is the middle one, and no kind writes nothing whatever strength stands beside it - the page is then what it is with no motion at all',
	array_filter( array_keys( Setup::HOVERS ), static function( string $kind ) use ( $mainOf ): bool {

		$middle = Setup::HOVERS[$kind]['medium'];

		ksort( $middle );

		return $mainOf( [ 'hover' => $kind ] ) !== $middle;
	} ) === [] && Setup::STRENGTHS[1] === 'medium'
	&& array_filter( Setup::STRENGTHS, static fn( string $strength ): bool => $pageOf( [ 'motion' => [ 'hover' => '', 'hover-strength' => $strength ] ] ) !== $radiusOfNothing ) === []
	&& $pageOf( [ 'motion' => [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => '' ] ] ) === $radiusOfNothing && str_contains( $radiusOfNothing, 'main' ) === false );
check( '...it comes after the animation in the one rule on main, which is alone in the page - and is the rule by itself where there is no effect',
	( function() use ( $pageOf ): bool {

		$both = $pageOf( [ 'motion' => [ 'effect' => 'zoom', 'strength' => 'soft', 'speed' => 'slow', 'hover' => 'bright', 'hover-strength' => 'hard' ] ] );
		$order = array_keys( designDeclarations( designBlock( designPlain( $both ), 'main {' ) ) );
		$alone = $pageOf( [ 'motion' => [ 'hover' => 'none' ] ] );

		return substr_count( $both, 'main {' ) === 1 && $order === array_merge( array_keys( Setup::EFFECTS['zoom']['soft'] ), [ '--vpa-duration' ], array_keys( Setup::HOVERS['bright']['hard'] ) )
			&& substr_count( $alone, 'main {' ) === 1 && array_keys( designDeclarations( designBlock( designPlain( $alone ), 'main {' ) ) ) === array_keys( Setup::HOVERS['none']['medium'] );
	} )() );

/*	The kinds are described by what they do, and the strengths by how much: zoom grows the picture and brighten brightens it, each more with every strength and from more than the factor 1 - brighten
	keeps the size, which is the factor 1, or the zoom Nino.css has by itself would come with it -, zoom and brighten do both with the values of the other two, and none is every handle at the value that
	does nothing, the same for the three strengths	*/
$hoverNumbers = static fn( string $kind, string $property ): array => array_map( static fn( string $strength ): float => (float) ( Setup::HOVERS[$kind][$strength][$property] ?? 0 ), Setup::STRENGTHS );
$hoverGrows = static fn( array $values ): bool => $values[0] > 1 && $values[0] < $values[1] && $values[1] < $values[2];
$hoverNeutral = [];

foreach( Setup::STRENGTHS as $hoverStrength ) {

	$neutral = Setup::HOVERS['none'][$hoverStrength];

	$hoverNeutral[] = (float) ( $neutral['--image-hover-scale'] ?? 0 ) === 1.0 && (float) ( $neutral['--image-hover-lift'] ?? 1 ) === 0.0 && (float) ( $neutral['--image-hover-brightness'] ?? 0 ) === 1.0
		&& ( $neutral['--image-hover-shadow'] ?? '' ) === 'none' && count( $neutral ) === 4 && $neutral === Setup::HOVERS['none']['medium'];
}

check( 'the table of the hover says what each kind is: zoom grows the picture and brighten brightens it - more with every strength, from more than 1 -, brighten keeps the size, zoom and brighten is both, and none leaves everything as it is',
	$hoverGrows( $hoverNumbers( 'zoom', '--image-hover-scale' ) ) === true && $hoverGrows( $hoverNumbers( 'bright', '--image-hover-brightness' ) ) === true && $hoverNumbers( 'bright', '--image-hover-scale' ) === [ 1.0, 1.0, 1.0 ]
	&& array_filter( Setup::STRENGTHS, static fn( string $strength ): bool => array_keys( Setup::HOVERS['zoom'][$strength] ) !== [ '--image-hover-scale' ] ) === []
	&& array_filter( Setup::STRENGTHS, static fn( string $strength ): bool => Setup::HOVERS['zoom-bright'][$strength] !== array_merge( Setup::HOVERS['bright'][$strength], Setup::HOVERS['zoom'][$strength] ) ) === []
	&& $hoverNeutral === [ true, true, true ] && array_keys( Setup::HOVERS ) === [ 'zoom', 'bright', 'zoom-bright', 'none' ] );

// base.css assigns the role --grid-max-width: var(--nino-measure), as the delivered theme.css does: the page comes after it and wins
check( 'the compiled sheet carries the page where it was asked for: the width and the animation in section 6, after the roles - so they are the ones that apply',
	str_contains( designSection( $rich, 6, Compiler::SECTION ), '--grid-max-width: 48rem;' ) === true && str_contains( designSection( $rich, 6, Compiler::SECTION ), '--vpa-duration: 2s;' ) === true
	&& strpos( $rich, '--grid-max-width: 48rem;' ) > strpos( $rich, '--grid-max-width: var(--nino-measure);' )
	&& substr_count( $rich, '--grid-max-width: 48rem;' ) === 1 && substr_count( $rich, "\nmain {\n" ) === 1 );
$wideSheet = Compiler::compile( $appData, Setup::normalize( $appData, [ 'width' => 2 ], $library ), $library );
$openSheet = Compiler::compile( $appData, Setup::normalize( $appData, [ 'width' => 1 ], $library ), $library );
check( '...and a wide page the same way: no maximum and no gutter, written after the role base.css assigns the maximum to, at both sizes - and one with no maximum and the small gutter at both',
	str_contains( designSection( $wideSheet, 6, Compiler::SECTION ), ":root { --grid-max-width: none; --grid-gutter: var(--space-2); }\n@media (min-width: 768px) { :root { --grid-gutter: 0; } }" ) === true
	&& strpos( $wideSheet, '--grid-max-width: none;' ) > strpos( $wideSheet, '--grid-max-width: var(--nino-measure);' )
	&& str_contains( designSection( $openSheet, 6, Compiler::SECTION ), ":root { --grid-max-width: none; --grid-gutter: var(--space-2); }\n@media (min-width: 768px) { :root { --grid-gutter: var(--space-2); } }" ) === true
	&& strpos( $openSheet, '--grid-max-width: none;' ) > strpos( $openSheet, '--grid-max-width: var(--nino-measure);' ) );

echo "\nThe knob positions, over a library of throwaway sets\n";

/*	A set declares what its steps are - five words for a scale, the three inner ones for a choice - and the knob picks
	one. Written into a throwaway library so the assertions do not depend on what the shipped sets happen to declare today	*/
$sandbox = ninoSandboxDir( $appData ). '/library';
mkdir( $sandbox. '/card', 0755, true );
mkdir( $sandbox. '/buttons', 0755, true );
// ...and the theme a library's defaults are taken from: the first of the shipped ones, which names the set of each part it has sets for
mkdir( $sandbox. '/themes', 0755, true );
file_put_contents( $sandbox. '/themes/'. Themes::SHIPPED[0]. '.php', "<?php\nreturn [ 'setup' => [ 'parts' => [ 'card' => 'first', 'buttons' => 'first' ] ] ];\n" );
file_put_contents( $sandbox. '/base.css', "/* ---- 1. the design tokens ---- */\n:root { --x: 1; }\n/* ---- 2. the roles ---- */\n:root { --y: 2; }\n" );
file_put_contents( $sandbox. '/card/first.css', "/*\t@name Throwaway\n *\t@description A set that exists for this test.\n *\t@knob border\n *\t@knob shadow\n *\n *\tAn example in a comment is no declaration:\n *\t--card-card-space--least: 0; --card-card-space--less: 0; --card-card-space--default: 1px; --card-card-space--more: 2px; --card-card-space--most: 3px;\n */\n:root {\n\t--card-border--least: 0;\n\t--card-border--less: .1rem;\n\t--card-border--default: .4rem;\n\t--card-border--more: 1rem;\n\t--card-border--most : 2rem;\n\t--card-shadow--least: none;\n\t--card-shadow--less: none;\n\t--card-shadow--default: none;\n\t--card-shadow--more: 0 .1rem .5rem black;\n\t--card-shadow--most: 0 .2rem 1rem black;\n\t--card-shadow-lift--least: 0;\n\t--card-shadow-lift--less: 0;\n\t--card-shadow-lift--default: 0;\n\t--card-shadow-lift--more: -.1rem;\n\t--card-shadow-lift--most: -.2rem;\n}\n.nino-card { border-width: var(--card-border); box-shadow: var(--card-shadow); }\n" );
file_put_contents( $sandbox. '/card/second.css', ":root {\n\t--card-shadow-lift--least: 0;\n\t--card-shadow-lift--less: 0;\n\t--card-shadow-lift--default: 0;\n\t--card-shadow-lift--more: -.1rem;\n\t--card-shadow-lift--most: -.2rem;\n\t--card-nonsense--least: 0;\n\t--card-nonsense--less: 1;\n\t--card-nonsense--default: 2;\n\t--card-nonsense--more: 3;\n\t--card-nonsense--most: 4;\n}\n" );
file_put_contents( $sandbox. '/buttons/first.css', ":root {\n\t--buttons-space--least: .125rem;\n\t--buttons-space--less: .25rem;\n\t--buttons-space--default: .5rem;\n\t--buttons-space--more: 1rem;\n\t--buttons-space--most: 2rem;\n}\n" );

/*	...and one for the other kind, and for what is published by no one: a choice is declared under the three inner
	words and a scale under all five, and a scale with three of its five, a choice with five, a choice under the outer
	words and an aspect of another length than its knob publish nothing - the panel greys the knob out	*/
$kindsLibrary = ninoSandboxDir( $appData ). '/library-kinds';
mkdir( $kindsLibrary. '/tables', 0755, true );
mkdir( $kindsLibrary. '/lists', 0755, true );

/** A ladder as a stylesheet declares it: one custom property for each of the words, from the values given - of the part of the tables unless another is named */
$ladder = static function( string $name, array $words, string $part = 'tables' ): string {

	$lines = '';

	foreach( $words as $at => $word )
		$lines .= "\t--". $part. '-'. $name. '--'. $word. ': '. ( $at + 1 ). ";\n";

	return $lines;
};
$five		= [ 'least', 'less', 'default', 'more', 'most' ];
$three	= [ 'less', 'default', 'more' ];
$head		= static fn( string $knobs ): string => "/*\t@name Kinds\n *\t@description A set that exists for this test.\n". $knobs. " */\n:root {\n";

// scale and zebra, published: five words, and three words with an aspect of three
file_put_contents( $kindsLibrary. '/tables/first.css', $head( " *\t@knob scale\n *\t@knob zebra\n" ). $ladder( 'scale', $five ). $ladder( 'zebra', $three ). $ladder( 'zebra-ink', $three ). "}\n" );
// a scale with three of its five, a choice with five of its three - and a scale that is whole, which is the one knob published
file_put_contents( $kindsLibrary. '/tables/second.css', $head( " *\t@knob border\n" ). $ladder( 'border', $five ). $ladder( 'space', $three ). $ladder( 'zebra', $five ). "}\n" );
// an aspect of another length than its knob: the knob is not published, the other knob is
file_put_contents( $kindsLibrary. '/tables/third.css', $head( " *\t@knob stripes\n" ). $ladder( 'zebra', $three ). $ladder( 'zebra-ink', $five ). $ladder( 'stripes', $five ). "}\n" );
// a choice declared under the outer words
file_put_contents( $kindsLibrary. '/tables/fourth.css', $head( '' ). $ladder( 'zebra', [ 'least', 'default', 'most' ] ). "}\n" );
// ...and an aspect of the right length under the wrong words, with the ladder of its choice under the right ones, which publishes nothing either
file_put_contents( $kindsLibrary. '/lists/first.css', $head( '' ). $ladder( 'marker', $three, 'lists' ). $ladder( 'marker-lift', [ 'least', 'default', 'most' ], 'lists' ). "}\n" );

$every = static fn( int $position ): array => array_map( static fn( array $kinds ): array => array_fill_keys( array_keys( $kinds ), $position ), Setup::KNOBS );

check( 'a library\'s defaults take their sets from the file of its first shipped theme: the parts it names, and an empty part for each it does not - the library of this section has sets for two of the seven',
	Setup::defaults( $sandbox )['parts'] === array_replace( array_fill_keys( array_keys( Setup::PARTS ), '' ), [ 'card' => 'first', 'buttons' => 'first' ] )
	&& Setup::defaults( $kindsLibrary )['parts'] === array_fill_keys( array_keys( Setup::PARTS ), '' ) );
check( 'a set publishes a knob by declaring its ladder, in the order of its part, and by no other way: not by a comment, not by a name that is none of the part\'s',
	Setup::knobs( $sandbox, 'card', 'first' ) === [ 'border', 'shadow' ] && Setup::knobs( $sandbox, 'buttons', 'first' ) === [ 'space' ]
	&& Setup::knobs( $sandbox, 'card', 'second' ) === [] && Setup::knobs( $kindsLibrary, 'tables', 'fourth' ) === [] );
check( '...a ladder spelled with a space before the colon is read the way a browser reads it, and an aspect stands beside the knob it is named after',
	Setup::ladders( $sandbox, 'card', 'first' ) === [ 'border' => [ 'border' ], 'shadow' => [ 'shadow', 'shadow-lift' ] ]
	&& Setup::ladders( $sandbox, 'buttons', 'first' ) === [ 'space' => [ 'space' ] ] );
check( '...an aspect without the ladder of its knob has published nothing, and a set that is not there answers to nothing - a frame that is there answers to what it declares, as a set does',
	Setup::ladders( $sandbox, 'card', 'second' ) === [] && Setup::knobs( $library, 'header', 'nope' ) === [] && Setup::knobs( $library, 'card', 'nope' ) === []
	&& Setup::knobs( $library, 'nowhere', 'first' ) === [] && Setup::knobs( $library, 'header', $defaultSets['header'] ) === array_keys( Setup::KNOBS['header'] ) );
check( '...a scale is published by all five words and a choice by its three, aspects included: a scale with three of its five, a choice with five, a choice under the outer words and an aspect of another length than its knob publish nothing',
	Setup::ladders( $kindsLibrary, 'tables', 'first' ) === [ 'scale' => [ 'scale' ], 'zebra' => [ 'zebra', 'zebra-ink' ] ]
	&& Setup::knobs( $kindsLibrary, 'tables', 'second' ) === [ 'border' ] && Setup::knobs( $kindsLibrary, 'tables', 'third' ) === [ 'stripes' ]
	&& Setup::knobs( $kindsLibrary, 'tables', 'fourth' ) === [] && Setup::knobs( $kindsLibrary, 'lists', 'first' ) === [] );

/*	A toggle is a ladder of two rungs, under default and more. Published by those two and no others - not by one, not by three, not by the outer words - and with an aspect of its own length, like any knob.
	And its name may start with the name of another knob and a hyphen - title-case beside title - which does not make it an aspect of that knob: a name that is a knob of the part is that knob's, and an
	incomplete toggle does not take the size beside it down with it	*/
$caseLibrary = ninoSandboxDir( $appData ). '/library-case';
mkdir( $caseLibrary. '/buttons', 0755, true );
mkdir( $caseLibrary. '/section', 0755, true );

$two = [ 'default', 'more' ];

// published: the two rungs, and with an aspect of two rungs beside it
file_put_contents( $caseLibrary. '/buttons/first.css', $head( " *\t@knob case\n" ). $ladder( 'case', $two, 'buttons' ). $ladder( 'case-ink', $two, 'buttons' ). "}\n" );
// one rung, three rungs, the outer words - and a rung of two with an aspect of three: none of them is a toggle
file_put_contents( $caseLibrary. '/buttons/second.css', $head( '' ). $ladder( 'case', [ 'default' ], 'buttons' ). "}\n" );
file_put_contents( $caseLibrary. '/buttons/third.css', $head( '' ). $ladder( 'case', $three, 'buttons' ). "}\n" );
file_put_contents( $caseLibrary. '/buttons/fourth.css', $head( '' ). $ladder( 'case', [ 'less', 'more' ], 'buttons' ). "}\n" );
file_put_contents( $caseLibrary. '/buttons/fifth.css', $head( '' ). $ladder( 'case', $two, 'buttons' ). $ladder( 'case-ink', $three, 'buttons' ). "}\n" );
// reads its toggle, writes capitals of its own, and a toggle that is on a scale's name
file_put_contents( $caseLibrary. '/buttons/read.css', $head( " *\t@knob case\n" ). $ladder( 'case', $two, 'buttons' ). "}\n.nino-btn { text-transform: var(--buttons-case); }\n" );
file_put_contents( $caseLibrary. '/buttons/own.css', $head( " *\t@knob case\n" ). $ladder( 'case', $two, 'buttons' ). "}\n.nino-btn { text-transform: uppercase; }\n" );
file_put_contents( $caseLibrary. '/buttons/other.css', $head( " *\t@knob case\n" ). $ladder( 'case', $two, 'buttons' ). "}\n.nino-btn { text-transform: var(--section-title-case); }\n" );
// title-case beside title: each its own knob, complete or not
file_put_contents( $caseLibrary. '/section/first.css', $head( " *\t@knob title\n *\t@knob subtitle\n *\t@knob title-case\n *\t@knob subtitle-case\n" ). $ladder( 'title', $five, 'section' ). $ladder( 'subtitle', $five, 'section' ). $ladder( 'title-case', $two, 'section' ). $ladder( 'subtitle-case', $two, 'section' ). "}\n" );
file_put_contents( $caseLibrary. '/section/second.css', $head( '' ). $ladder( 'title-case', $two, 'section' ). "}\n" );
file_put_contents( $caseLibrary. '/section/third.css', $head( " *\t@knob title\n" ). $ladder( 'title', $five, 'section' ). $ladder( 'title-case', [ 'more' ], 'section' ). "}\n" );
file_put_contents( $caseLibrary. '/section/fourth.css', $head( '' ). $ladder( 'title', $five, 'section' ). $ladder( 'title-ink', $three, 'section' ). $ladder( 'title-case', $two, 'section' ). "}\n" );

check( 'a toggle is published by the two rungs default and more, and by no other count or words: one rung, three, and the outer pair publish nothing, and an aspect of another length takes the toggle with it',
	Setup::ladders( $caseLibrary, 'buttons', 'first' ) === [ 'case' => [ 'case', 'case-ink' ] ]
	&& Setup::knobs( $caseLibrary, 'buttons', 'second' ) === [] && Setup::knobs( $caseLibrary, 'buttons', 'third' ) === [] && Setup::knobs( $caseLibrary, 'buttons', 'fourth' ) === []
	&& Setup::knobs( $caseLibrary, 'buttons', 'fifth' ) === [] );
check( '...the name of a toggle that starts with the name of another knob and a hyphen is a knob of its own and no aspect of that one: title-case stands beside title, whole or not - the size is published either way, and an aspect of the size is still its aspect and takes the size down alone, not the toggle',
	Setup::ladders( $caseLibrary, 'section', 'first' ) === [ 'title' => [ 'title' ], 'subtitle' => [ 'subtitle' ], 'title-case' => [ 'title-case' ], 'subtitle-case' => [ 'subtitle-case' ] ]
	&& Setup::ladders( $caseLibrary, 'section', 'second' ) === [ 'title-case' => [ 'title-case' ] ]
	&& Setup::ladders( $caseLibrary, 'section', 'third' ) === [ 'title' => [ 'title' ] ]
	&& Setup::knobs( $caseLibrary, 'section', 'fourth' ) === [ 'title-case' ] );
check( 'the library check lets a set read its toggle where it styles the thing - text-transform: var(--buttons-case) - and nothing else: capitals of its own are the case of a role as ever, and another part\'s toggle is a read of another part\'s knob',
	designKnobProblems( $caseLibrary, 'buttons', 'read' ) === [] && designKnobProblems( $caseLibrary, 'buttons', 'first' ) === []
	&& designKnobProblems( $caseLibrary, 'buttons', 'own' ) === [ 'own: writes the face, the weight or the case of a role' ]
	&& in_array( 'other: reads --section-title-case, a knob of the section part', designKnobProblems( $caseLibrary, 'buttons', 'other' ), true ) === true
	&& in_array( 'other: writes the face, the weight or the case of a role', designKnobProblems( $caseLibrary, 'buttons', 'other' ), true ) === true
	&& designKnobProblems( $caseLibrary, 'section', 'first' ) === []
	&& designKnobProblems( $caseLibrary, 'buttons', 'second' ) === [ 'second: "case" is not a ladder of two', 'second: answers to no knob at all' ]
	&& designKnobProblems( $caseLibrary, 'buttons', 'third' ) === [ 'third: "case" is not a ladder of two', 'third: answers to no knob at all' ] );

/*	The library check of the top of this file refuses what it names - shown
	here over sets it can be shown on. The parts are compiled in the order they
	are shown in, so a tables set that declares a ladder of the buttons
	would put it in the sheet after the buttons template's own and win, and
	one that declares the knob itself in a nested selector wins for every
	element that selector matches; a rule of it reading a buttons knob, or a
	step of one by name, follows a position the buttons template may not
	publish. Its own part's ladder and read stand beside them, and stay out of
	the report. Each is reported once, however often it is written - and a
	name whose ladder is reported is not reported as a knob as well	*/
$stray = ninoSandboxDir( $appData ). '/library-stray';
mkdir( $stray. '/tables', 0755, true );
$ownScale = "\t--tables-scale--least: .8;\n\t--tables-scale--less: .9;\n\t--tables-scale--default: 1;\n\t--tables-scale--more: 1.1;\n\t--tables-scale--most: 1.2;\n";
$foreignSize = "\t--buttons-size--least: .8;\n\t--buttons-size--less: .9;\n\t--buttons-size--default: 1;\n\t--buttons-size--more: 1.12;\n\t--buttons-size--most: 1.25;\n";
file_put_contents( $stray. '/tables/first.css', "/*\t@name Stray\n *\t@description A set that reaches into the buttons.\n *\t@knob scale\n */\n:root {\n". $ownScale. $foreignSize. "}\n.nino-table { font-size: calc(1rem * var(--tables-scale)); padding: var(--buttons-space); }\n" );
// ...a step of a buttons knob read by its name, and never the knob itself - twice, the second time with a fallback
file_put_contents( $stray. '/tables/second.css', "/*\t@name Stray read\n *\t@description A set that reads a step of a buttons knob.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-table { padding: var(--buttons-size--default); margin: var(--buttons-size--more, .5rem); }\n" );
// ...the knob a buttons template is selected under, declared again in a nested selector - twice, once with a space before the colon
file_put_contents( $stray. '/tables/third.css', "/*\t@name Stray knob\n *\t@description A set that declares a buttons knob again.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-table .nino-btn { --buttons-size: 2; }\n.nino-table .nino-btn--big { --buttons-size : 3; }\n" );
// ...and the same knob with its ladder as well, which is what is reported
file_put_contents( $stray. '/tables/fourth.css', "/*\t@name Stray both\n *\t@description A set that declares a buttons knob and its ladder.\n *\t@knob scale\n */\n:root {\n". $ownScale. $foreignSize. "}\n.nino-table .nino-btn { --buttons-size: 2; }\n" );

check( 'the library check finds nothing wrong with a set that keeps to its part', designKnobProblems( $sandbox, 'card', 'first' ) === [] && designKnobProblems( $kindsLibrary, 'tables', 'first' ) === [] );
check( '...and reports a ladder that is not one of its kind: a scale with three of its five, and a choice with five of its three - once each, with the length it should have',
	designKnobProblems( $kindsLibrary, 'tables', 'second' ) === [ 'second: "space" is not a ladder of five', 'second: "zebra" is not a ladder of three' ] );
check( '...an aspect of another length than its knob, a ladder under the wrong words, and an aspect of the right length under the wrong ones - with a set that publishes nothing saying so',
	designKnobProblems( $kindsLibrary, 'tables', 'third' ) === [ 'third: "zebra-ink" is a ladder of another length than "zebra"' ]
	&& designKnobProblems( $kindsLibrary, 'tables', 'fourth' ) === [ 'fourth: "zebra" is not a ladder of three', 'fourth: answers to no knob at all' ]
	&& designKnobProblems( $kindsLibrary, 'lists', 'first' ) === [ 'first: "marker-lift" is not a ladder of three', 'first: answers to no knob at all' ] );
check( '...and reports a tables set that declares the ladder of a buttons knob and reads another: once each, named with the part it belongs to',
	designKnobProblems( $stray, 'tables', 'first' ) === [
		'first: declares --buttons-size, a ladder of the buttons part',
		'first: reads --buttons-space, a knob of the buttons part',
	] );
check( '...and one that reads a step of a buttons knob by name - var(--buttons-size--default) - as reading that knob: once, however often it reads it',
	designKnobProblems( $stray, 'tables', 'second' ) === [ 'second: reads --buttons-size, a knob of the buttons part' ] );
check( '...and one that declares a buttons knob again in a nested selector as declaring it, once - and a name whose ladder is reported is not reported a second time as a knob',
	designKnobProblems( $stray, 'tables', 'third' ) === [ 'third: declares --buttons-size, a knob of the buttons part' ]
	&& designKnobProblems( $stray, 'tables', 'fourth' ) === [ 'fourth: declares --buttons-size, a ladder of the buttons part' ] );
check( '...and a set the library does not have is reported rather than read', designKnobProblems( $stray, 'tables', 'absent' ) === [ 'absent: has no stylesheet' ] );

/*	...and the roles of the Type view and the colours of the palette: a template that writes a face, a weight or a case of
	its own is reported, and so is one that writes a colour as a literal - a hex, an rgb() or an hsl(). A tables template
	may set the case of a table head, which is what it is built as and no role of the type - but of that
	and of nothing else	*/
$roles = ninoSandboxDir( $appData ). '/library-roles';
mkdir( $roles. '/buttons', 0755, true );
mkdir( $roles. '/tables', 0755, true );
mkdir( $roles. '/lists', 0755, true );
$ownSize = "\t--buttons-size--least: .8;\n\t--buttons-size--less: .9;\n\t--buttons-size--default: 1;\n\t--buttons-size--more: 1.12;\n\t--buttons-size--most: 1.25;\n";
file_put_contents( $roles. '/buttons/first.css', "/*\t@name Cased\n *\t@description A buttons template that sets a case and a colour.\n *\t@knob size\n */\n:root {\n". $ownSize. "}\n.nino-btn { text-transform: uppercase; color: #fff; }\n" );
file_put_contents( $roles. '/buttons/second.css', "/*\t@name Weighed\n *\t@description A buttons template that sets a face, a weight and a colour.\n *\t@knob size\n */\n:root {\n". $ownSize. "}\n.nino-btn { font-family: serif; font-weight: 700; background: rgb(0 0 0 / 50%); }\n" );
file_put_contents( $roles. '/tables/first.css', "/*\t@name Capitals\n *\t@description A tables template that sets the case of a table head.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-table th { text-transform: uppercase; letter-spacing: .08em; background: color-mix( in srgb, var(--color-ink) 7%, var(--color-ground) ); }\n" );
file_put_contents( $roles. '/tables/second.css', "/*\t@name Heavy\n *\t@description A tables template that sets a weight and a colour.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-table th { font-weight: bold; background: hsl(0 0% 0%); }\n" );
// ...the case of a table head in a rule whose selector is full of brackets and commas, of table heads in a list, and in a media query
file_put_contents( $roles. '/tables/third.css', "/*\t@name Heads\n *\t@description A tables template that sets the case of table heads.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-table:where(:not(.nino-table--bordered, .nino-table--small)) > thead > tr > th:where(:not(.nino-sr-only)) { font-size: .8em; text-transform: uppercase; }\n.nino-table th, .nino-table--bordered th { text-transform: uppercase; }\n@media (min-width: 768px) {\n\t.nino-table th { text-transform: uppercase; }\n}\n" );
// ...and the case of anything else: a badge, and a button in a rule that names a table head too
file_put_contents( $roles. '/tables/fourth.css', "/*\t@name Badges\n *\t@description A tables template that sets the case of a badge.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-badge { text-transform: uppercase; }\n" );
$ownMarker = "\t--lists-marker--less: normal;\n\t--lists-marker--default: bold;\n\t--lists-marker--more: bold;\n";
// ...the weight of the mark before an item, which the marker of a list is, and the weight of the item itself
file_put_contents( $roles. '/lists/first.css', "/*\t@name Marks\n *\t@description A lists template that sets the weight of a mark.\n *\t@knob marker\n */\n:root {\n". $ownMarker. "}\n.nino-list--check li::before, .nino-list--numbered li::before { font-weight: var(--lists-marker); }\n@media (min-width: 768px) {\n\t.nino-list--numbered li:where(:not(.nino-is-last))::before { font-weight: bold; }\n}\n" );
file_put_contents( $roles. '/lists/second.css', "/*\t@name Items\n *\t@description A lists template that sets the weight of an item and the case of a mark.\n *\t@knob marker\n */\n:root {\n". $ownMarker. "}\n.nino-list li { font-weight: bold; }\n.nino-list li::before { text-transform: uppercase; }\n" );
file_put_contents( $roles. '/tables/fifth.css', "/*\t@name Both\n *\t@description A tables template that sets the case of a table head and of a button.\n *\t@knob scale\n */\n:root {\n". $ownScale. "}\n.nino-table th, .nino-btn { text-transform: uppercase; }\n" );

check( 'the library check reports a buttons template that sets a case or a colour of its own, and one that sets a face, a weight or a colour - the roles of the type are the Type view\'s and the colours the palette\'s',
	designKnobProblems( $roles, 'buttons', 'first' ) === [ 'first: writes the face, the weight or the case of a role', 'first: writes a colour as a literal' ]
	&& designKnobProblems( $roles, 'buttons', 'second' ) === [ 'second: writes the face, the weight or the case of a role', 'second: writes a colour as a literal' ] );
check( '...and a tables template that sets the case of a table head - in a rule of its own, in a list of selectors that all end in one, in a selector full of brackets, or in a media query - is none of them, nor is a colour that is mixed out of the roles, while one that sets a weight or writes an hsl() is',
	designKnobProblems( $roles, 'tables', 'first' ) === [] && designKnobProblems( $roles, 'tables', 'third' ) === []
	&& designKnobProblems( $roles, 'tables', 'second' ) === [ 'second: writes the face, the weight or the case of a role', 'second: writes a colour as a literal' ] );
check( '...and so is one that sets a case on a badge, or on a button in the same rule as a table head - the allowance is for a table head, not for a tables template',
	designKnobProblems( $roles, 'tables', 'fourth' ) === [ 'fourth: writes the face, the weight or the case of a role' ]
	&& designKnobProblems( $roles, 'tables', 'fifth' ) === [ 'fifth: writes the face, the weight or the case of a role' ] );
check( '...and a lists template may set the weight of the mark before an item, on its own or in a media query - the weight of a mark is its marker\'s - and nothing else: the weight of an item, or the case of a mark, is a role\'s',
	designKnobProblems( $roles, 'lists', 'first' ) === [] && designKnobProblems( $roles, 'lists', 'second' ) === [ 'second: writes the face, the weight or the case of a role' ] );
check( '...and a frame is held to its ladders and to what it reads, and not to the roles: a bar that writes a case, a weight or a colour is a bar',
	designKnobProblems( $library, 'header', $defaultSets['header'] ) === [] && array_filter( Setup::available( $library, 'header' ), static fn( string $set ): bool => in_array( $set. ': writes a colour as a literal', designKnobProblems( $library, 'header', $set ), true ) ) === []
	&& designKnobProblems( $library, 'footer', $defaultSets['footer'] ) === [] );

$notes	= [];
$picked	= Compiler::compile( $appData, Setup::normalize( $appData, [ 'knobs' => $every( 1 ) ], $sandbox, $notes ), $sandbox );

check( 'every knob a set answers to becomes one selection line, the aspects of a knob included', str_contains( $picked, '--card-border: var(--card-border--more);' ) === true
	&& str_contains( $picked, '--card-shadow: var(--card-shadow--more);' ) === true && str_contains( $picked, '--card-shadow-lift: var(--card-shadow-lift--more);' ) === true
	&& str_contains( $picked, '--buttons-space: var(--buttons-space--more);' ) === true );
check( '...and a knob no set answers to becomes nothing at all, and neither does a name that is no knob',
	str_contains( $picked, '--card-space:' ) === false && str_contains( $picked, '--card-title:' ) === false
	&& str_contains( $picked, '--buttons-size:' ) === false && str_contains( $picked, '--card-nonsense:' ) === false );
check( '...gathered into one block at the end, not scattered beside the sets', substr_count( $picked, 'the knob positions' ) === 1
	&& strpos( $picked, 'the knob positions' ) > strpos( $picked, '--card-border--more' ) && strpos( $picked, 'the knob positions' ) > strpos( $picked, '--buttons-space--more' ) );
check( '...and the five steps themselves are still in the sheet, so a project without the feature can move the knob by hand',
	str_contains( $picked, '--card-border--least: 0;' ) === true && str_contains( $picked, '--card-border--most : 2rem;' ) === true && str_contains( $picked, '--card-shadow--most: 0 .2rem 1rem black;' ) === true );

$blockIn = substr( $picked, (int) strpos( $picked, 'the knob positions' ) );

check( '...one line for each, and no more: three for the card - the border, the shadow and its lift - and one for the buttons',
	preg_match_all( '/^\t--[a-z-]+: var\(--[a-z-]+--more\);$/m', $blockIn ) === 4 && preg_match_all( '/^\t--[a-z-]+:/m', $blockIn ) === 4 );

$titles = [];
preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $picked, $found, PREG_SET_ORDER );
foreach( $found as $row )
	$titles[(int) $row[1]] = trim( $row[2] );

check( 'a template the library does not have is left out and the sections after it take the numbers that are free, each saying where the knobs it answers to stand',
	array_slice( $titles, 6, null, true ) === [ 7 => 'card: first (border more, shadow more)', 8 => 'buttons: first (space more)', 9 => 'the knob positions' ]
	&& count( array_filter( $notes, static fn( string $note ): bool => str_contains( $note, 'holds no set for "header"' ) ) ) === 1 );

$moved = Compiler::compile( $appData, Setup::normalize( $appData, [ 'knobs' => [ 'card' => [ 'border' => -1, 'shadow' => -1, 'space' => 1 ], 'buttons' => [ 'size' => 1 ] ] ], $sandbox ), $sandbox );

check( 'each knob is asked for its own part: the card\'s position does not reach the buttons, and the aspect of a knob stands where the knob does',
	str_contains( $moved, '--card-border: var(--card-border--less);' ) === true && str_contains( $moved, '--card-shadow-lift: var(--card-shadow-lift--less);' ) === true
	&& str_contains( $moved, '--buttons-space: var(--buttons-space--default);' ) === true && str_contains( $moved, '--card-space:' ) === false );

/*	The two outer positions of a scale select the outer words - and a choice, which has three, does not reach them:
	a position past its ends is the middle, never the fifth word	*/
$ends = Compiler::compile( $appData, Setup::normalize( $appData, [ 'knobs' => [ 'card' => [ 'border' => 2, 'shadow' => -2 ], 'buttons' => [ 'space' => -1 ] ] ], $sandbox ), $sandbox );

check( 'a knob at +2 selects --most and at -2 --least, the aspects of a knob with it, and the positions between them --more and --less',
	str_contains( $ends, '--card-border: var(--card-border--most);' ) === true && str_contains( $ends, '--card-shadow: var(--card-shadow--least);' ) === true
	&& str_contains( $ends, '--card-shadow-lift: var(--card-shadow-lift--least);' ) === true && str_contains( $ends, '--buttons-space: var(--buttons-space--less);' ) === true );

$choiceSheet	= Compiler::compile( $appData, Setup::normalize( $appData, [ 'parts' => [ 'tables' => 'first' ], 'knobs' => [ 'tables' => [ 'scale' => 2, 'zebra' => -1 ] ] ], $kindsLibrary ), $kindsLibrary );
$pastTheEnds	= Compiler::compile( $appData, Setup::normalize( $appData, [ 'parts' => [ 'tables' => 'first' ], 'knobs' => [ 'tables' => [ 'scale' => -2, 'zebra' => 2 ] ] ], $kindsLibrary ), $kindsLibrary );

check( 'a choice is selected under its three words - -1 is --less, with its aspect - and a position past its ends is the middle, not the fifth word, while the scale beside it goes to the end',
	str_contains( $choiceSheet, '--tables-zebra: var(--tables-zebra--less);' ) === true && str_contains( $choiceSheet, '--tables-zebra-ink: var(--tables-zebra-ink--less);' ) === true
	&& str_contains( $choiceSheet, '--tables-scale: var(--tables-scale--most);' ) === true
	&& str_contains( $pastTheEnds, '--tables-zebra: var(--tables-zebra--default);' ) === true && str_contains( $pastTheEnds, '--tables-zebra-ink: var(--tables-zebra-ink--default);' ) === true
	&& str_contains( $pastTheEnds, '--tables-scale: var(--tables-scale--least);' ) === true );

$withoutKnob = Compiler::compile( $appData, Setup::normalize( $appData, [ 'parts' => [ 'card' => 'second' ], 'knobs' => $every( 1 ) ], $sandbox ), $sandbox );
check( 'an aspect declared without its knob is not selected either - nothing was published', str_contains( $withoutKnob, '--card-shadow-lift:' ) === false
	&& str_contains( $withoutKnob, '--card-nonsense:' ) === false && str_contains( $withoutKnob, '--buttons-space: var(--buttons-space--more);' ) === true );

/*	What the panel compares to say whether the stylesheet still answers to the
	screen: the setup's own choices AND the bytes of every library file they
	point at, so a set that changed under a project - a reinstall, an edited
	library - is visible rather than silent. This is the whole of it	*/
$sandboxSetup 	= Setup::normalize( $appData, [ 'parts' => [ 'card' => 'first' ] ], $sandbox );
$beforeSetEdit 	= \Nino\Modules\Design::fingerprint( $appData, $sandboxSetup, $sandbox );
$cardSet 			= $sandbox. '/card/first.css';
$cardSource 	= (string) file_get_contents( $cardSet );
file_put_contents( $cardSet, $cardSource. "\n/* a set the project got from somewhere else */\n" );

check( 'the fingerprint follows the bytes of the sets it names, not only the names', \Nino\Modules\Design::fingerprint( $appData, $sandboxSetup, $sandbox ) !== $beforeSetEdit );

file_put_contents( $cardSet, $cardSource );

$recorded = $sandboxSetup;
$recorded['compiled'] = [ 'at' => 'then', 'sha' => 'a', 'input' => 'b', 'setup' => [ 'size' => 1 ] ];

check( '...and what an earlier apply recorded decides nothing: the record is the answer to the fingerprint, not a question to it',
	\Nino\Modules\Design::fingerprint( $appData, $recorded, $sandbox ) === $beforeSetEdit );

$changes = [
	'a knob' 				=> [ 'knobs' => [ 'card' => [ 'border' => 1 ] ] ],
	'a template' 		=> [ 'parts' => [ 'card' => 'second' ] ],
	'the size' 			=> [ 'size' => 1 ],
	'the width' 		=> [ 'width' => 1 ],
	'the radius' 		=> [ 'radius' => 1 ],
	'the alternative radius' => [ 'radius-alt' => 2 ],
	'the radius of a surface' => [ 'radii' => [ 'card' => 'alternative' ] ],
	'the animation' => [ 'motion' => [ 'effect' => 'zoom' ] ],
	'a font' 				=> [ 'fonts' => [ 'text' => [ 'family' => 'system-serif' ] ] ],
	'a colour' 			=> [ 'colours' => [ 'primary' => '#8b1d3f' ] ],
	'the mode' 			=> [ 'colours' => [ 'mode' => 'dark' ] ],
];
$unmoved = array_filter( $changes, static fn( array $raw ): bool => \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, $raw + [ 'parts' => [ 'card' => 'first' ] ], $sandbox ), $sandbox ) === $beforeSetEdit );

check( 'and every choice of the panel moves it, and the radius of the page with them: '. implode( ', ', array_keys( $changes ) ). ( $unmoved === [] ? '' : ' - unmoved: '. implode( ', ', array_keys( $unmoved ) ) ), $unmoved === [] );

/*	The colour tables moved at every position of three knobs but the framework's
	own, the third of five, so the fingerprint carries their revision for a
	project with one of those knobs off 3 and for no other: a project that never
	touched them compiles the same bytes as before and has no reason to read
	"not applied"	*/
$beforeRevision = static function( array $setup, string $lib ): string {
	$files = [];
	foreach( array_keys( Setup::PARTS ) as $printPart ) {
		$printSet 					= (string) ( $setup['parts'][$printPart] ?? '' );
		$printFile 					= Setup::file( $lib, $printPart, $printSet );
		$files[$printPart] 	= [ $printSet, ( $printFile === '' || is_file( $printFile ) === false ) ? '' : (string) hash_file( 'sha256', $printFile ) ];
	}
	// ...as it was written before the features had a key of their own in it
	unset( $setup['compiled'], $setup['features'] );
	return hash( 'sha256', serialize( [ $setup, $files ] ) );
};
$moved = $sandboxSetup;
$moved['colours']['contrast'] = 5;
$movedDown = $sandboxSetup;
$movedDown['colours']['depth'] = 2;

check( 'a setup with every colour knob at 3 fingerprints exactly as it did before the tables moved',
	\Nino\Modules\Design::fingerprint( $appData, $sandboxSetup, $sandbox ) === $beforeRevision( $sandboxSetup, $sandbox ) && $sandboxSetup['colours']['saturation'] === 3
	&& $sandboxSetup['colours']['contrast'] === 3 && $sandboxSetup['colours']['depth'] === 3 );
check( '...and one with Saturation, Contrast or Depth anywhere else does not, on either side of the middle',
	\Nino\Modules\Design::fingerprint( $appData, $moved, $sandbox ) !== $beforeRevision( $moved, $sandbox )
	&& \Nino\Modules\Design::fingerprint( $appData, $movedDown, $sandbox ) !== $beforeRevision( $movedDown, $sandbox ) );

echo "\nThe token layer and the roles, held to the delivered theme.css and to the kernel's Nino.css\n";

/*	The token layer and the roles are shipped twice on purpose - the wizard's
	base unit delivers them, and this feature carries its own copy because
	_admin/install/ is gone from a project by the time anything here
	recompiles. They are one source since Nino 1.6: the kernel generates the
	delivered theme.css from this feature's library at the defaults with each
	release, under a header of its own. Where a checkout still has the
	installer the two are held to each other as a whole - everything after the
	header, byte for byte - and no longer token by token and role by role. A
	project that applies the defaults keeps its look because the file it is
	given is the file this compiler writes.	*/
$baseRaw				= (string) file_get_contents( $library. '/base.css' );
$baseCss				= designPlain( $baseRaw );
$baseTokens			= designDeclarations( designBlock( designPlain( designSection( $baseRaw, 1, DESIGN_BASE_MARKER ) ), ':root {' ) );
$baseRoles			= designDeclarations( designPlain( designSection( $baseRaw, 2, DESIGN_BASE_MARKER ) ) );
$defaultSheet		= Compiler::compile( $appData, Setup::normalize( $appData, [], $library ), $library );

check( 'base.css has its two sections, the tokens and the roles, and nothing of the type: no font-face, no family, no size, no raster',
	$baseTokens !== [] && $baseRoles !== []
	&& str_contains( $baseCss, '@font-face' ) === false
	&& preg_match( '/--(?:fontfamily|fontsize|font-|nino-text-|text-[1-6])/', $baseCss ) !== 1 );

if( is_file( $deliveredFile ) === false )
	echo "  --  this checkout has no installer library, so the delivered theme.css is not compared with the compiler's\n";
else {

	/*	The compiler writes a library file whole, and its opening comment with it: the description of the file, which its owner
		rewrites as he pleases and which is no rule. The kernel regenerates the delivered file from the library with each release, so
		the opening comment of each of the seven parts' sets is left out on both sides - they are not what a browser is given to draw - and the rest
		of the body, every rule and every ladder, the frames' and the sets' alike, is held to the delivered file
		byte for byte	*/
	$theirsRaw	= (string) file_get_contents( $deliveredFile );
	$theirBody	= designWithoutOpenings( designBody( $theirsRaw ) );
	$ownBody	= designWithoutOpenings( designBody( $defaultSheet ) );
	$limit		= min( strlen( $theirBody ), strlen( $ownBody ) );
	$differAt	= 0;

	// Where the two bodies part, for a failure that says where
	while( $differAt < $limit && $theirBody[$differAt] === $ownBody[$differAt] )
		$differAt++;

	/*	The first six sections are the base's and no part of the library of the seven parts: the tokens, the palette, the type, the roles, the root size and the
		page of the delivered file are the same bytes as those of the compile - which holds against a delivered file older than the library as well, the
		one thing that turns the check after it red	*/
	$firstSix = static function( string $body ): string {
		return preg_match( '~/\* ==== 7\. ~', $body, $found, PREG_OFFSET_CAPTURE ) === 1 ? substr( $body, 0, $found[0][1] ) : '';
	};

	check( 'the first six sections of the delivered theme.css - the tokens, the palette, the type, the roles, the root size and the page - are the first six of the compile at the defaults, byte for byte: they do not move with the library of the seven parts ('. strlen( $firstSix( $ownBody ) ). ' bytes)',
		$firstSix( $ownBody ) !== '' && $firstSix( $theirBody ) === $firstSix( $ownBody ) && preg_match_all( '~/\* ==== [1-6]\. ~', $firstSix( $ownBody ) ) === 6 );

	check( 'the body of the delivered theme.css - everything after its header - is the body of the sheet this compiler writes at the defaults, byte for byte but for the opening comment of the seven sets, which describes the file and is no rule ('. strlen( $ownBody ). ' bytes)'
		. ( $theirBody === $ownBody ? '' : ' - they part at byte '. $differAt. ', the delivered body has '. strlen( $theirBody ). ' bytes' ), $ownBody !== '' && $theirBody === $ownBody );

	$openingMarker = '~/\* ==== \d+\. (?:'. implode( '|', array_keys( Setup::PARTS ) ). '): [^\n]*==== \*/\n/\*~';

	check( '...and what is cut is the opening comment of those seven and nothing of a rule: the compiled sheet has a section for each of the seven parts and each opens with its comment, cutting them leaves the sections without one, and the declarations of the default sets are in it - the frames\' included',
		preg_match_all( $openingMarker, $defaultSheet ) === count( Setup::PARTS ) && preg_match_all( $openingMarker, $ownBody ) === 0
		&& array_filter( [ '--section-title--default', '--buttons-spread--default', '--tables-zebra--more', '--header-height--default', '--footer-ground--more' ], static fn( string $token ): bool => preg_match( '/'. preg_quote( $token, '/' ). ':\s/', $ownBody ) !== 1 ) === [] );

	/*	The header is the kernel's own, so the file carries no stamp of the
		compiler's: ownership() calls it foreign, the panel's word for a file
		Design never wrote, and an apply asks before it writes over it. The
		file stays one that may be edited by hand or replaced wholesale	*/
	check( '...and the delivered file carries no stamp of the compiler\'s, since its header is the kernel\'s own - so it is foreign, and Design asks before it writes over it',
		Compiler::stamped( $theirsRaw ) === false && Compiler::ownership( $theirsRaw, false ) === 'foreign' );
}

/*	The roles are held to the kernel as well, wherever the checkout has one:
	every --color-* role Nino.css reads without a fallback is assigned by
	base.css - but for the five Nino.css derives itself, in its own :root and
	per ground (--color-ground and --color-ink are the ground's, --color-accent,
	--color-focus and --color-title-default follow from the roles around them).
	A role the stylesheet reads and nobody assigns is the kernel's default
	wearing the theme's name	*/
if( $ninoCss === '' )
	echo "  --  this checkout has no Nino.css, so the roles are not compared with the kernel's\n";
else {

	preg_match_all( '/var\(\s*(--color-[a-z0-9-]+)\s*\)/', $ninoCss, $read );

	$read				= array_values( array_unique( $read[1] ) );
	$derived		= [ '--color-accent', '--color-focus', '--color-ground', '--color-ink', '--color-title-default' ];
	$unassigned	= array_values( array_diff( $read, array_keys( $baseRoles ), $derived ) );
	$ninoDeclared = designDeclarations( designBlock( $ninoCss, ':root {' ) );

	check( 'every --color-* role the kernel\'s Nino.css reads without a fallback is assigned by base.css, but for the five it derives itself'
		. ( $unassigned === [] ? '' : ' - unassigned: '. implode( ', ', $unassigned ) ), $unassigned === [] && count( $read ) > 50 );
	check( '...and those five are roles its own :root declares', array_diff( $derived, array_keys( $ninoDeclared ) ) === [] );
	check( '...and base.css assigns no role Nino.css does not know', array_diff( array_keys( $baseRoles ), array_keys( $ninoDeclared ) ) === [] );

	/*	The border of every ground is base.css's, and so the delivered file's:
		each of the seven roles is the border solved for the surface the
		ground is drawn on, so a line, a badge fill and a marker on an alt,
		tint, dark, black or brand ground are in the colour that was solved
		against it. The two brand grounds are filled with the safe colours
		(--color-primary is --nino-brand-safe, --color-brand-alt is
		--nino-accent-safe), so their line is the border solved for those.
		Nino.css declares the same seven as the border of the page, which is
		what a page without the theme draws	*/
	$borders	= [ 'default' => 'default', 'alt' => 'alt', 'tint' => 'tint', 'dark' => 'dark', 'black' => 'black', 'primary' => 'brand-safe', 'brand-alt' => 'accent-safe' ];
	$assigned	= [];
	$ofThePage	= [];

	foreach( $borders as $ground => $surface ) {

		if( ( $baseRoles['--color-border-'. $ground] ?? '' ) !== 'var(--nino-'. $surface. '-border)' || isset( $baseTokens['--nino-'. $surface. '-border'] ) === false )
			$assigned[] = '--color-border-'. $ground;

		if( ( $ninoDeclared['--color-border-'. $ground] ?? '' ) !== 'var(--color-border)' )
			$ofThePage[] = '--color-border-'. $ground;
	}

	check( 'base.css assigns the border of each of the seven grounds from the border of its surface: --color-border-default from --nino-default-border ... --color-border-primary from --nino-brand-safe-border, --color-border-brand-alt from --nino-accent-safe-border'
		. ( $assigned === [] ? '' : ' - wrong: '. implode( ', ', $assigned ) ), $assigned === [] );
	check( '...and Nino.css still declares the seven as var(--color-border), the border of the page, for a page without the theme'
		. ( $ofThePage === [] ? '' : ' - wrong: '. implode( ', ', $ofThePage ) ), $ofThePage === [] );
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
	same cascade. The light palette is base.css's own section 1; the dark one has
	no section of its own, it is written into the compiled sheet, which the
	delivered theme.css is held to byte for byte above	*/
$onlyLight = array_intersect_key( $baseTokens, $genLight );

check( 'the untouched palette reproduces the token layer of base.css exactly, light - every value of it', $genLight !== [] && $onlyLight === $genLight );

/*	The compiled palette at the defaults - saturation, contrast and depth on the third of five, temperature on the third of four, harmony on the first -
	is the palette the framework has, and it did not move when the three knobs went from three positions to five: section 2 of the compiled
	defaults is the same 8872 bytes. Compiling the defaults before and after is not possible in one tree, so this is what is kept of "before":
	the sha256 of that section, computed on the tip of F34 (commit f78f272) with the knobs on the second of three, before the tables were
	widened. A table that moves its middle row fails here before it moves a project	*/
$palettePinned = 'c9bab03eebb71e8c02c50980616fafaf50161cf02e2368e40248dd7f9bf3a259';
$paletteSection = designSection( $defaultSheet, 2, Compiler::SECTION );

check( 'section 2 of the compiled defaults, the palette, is byte for byte what it was with three positions on the second: '. strlen( $paletteSection ). ' bytes, sha256 '. substr( $palettePinned, 0, 12 ). '... as computed on the F34 tip',
	strlen( $paletteSection ) === 8872 && hash( 'sha256', $paletteSection ) === $palettePinned && Colours::normalize( [] )['saturation'] === 3
	&& Colours::normalize( [] )['contrast'] === 3 && Colours::normalize( [] )['depth'] === 3 && Colours::normalize( [] )['temperature'] === 3 && Colours::normalize( [] )['harmony'] === 1 );
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

$inSheet = static fn( string $mode ): string => designPlain( designSection( Compiler::compile( $appData, Setup::normalize( $appData, [ 'colours' => [ 'mode' => $mode ] ], $library ), $library ), 2, Compiler::SECTION ) );

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

check( 'the panel draws what choices() publishes: the five knobs and then the mode, each with its kind - harmony and temperature are choices with the names of their four alternatives, saturation, contrast and depth scales of five positions that start on the third - and the mode, whose steps are the words the setup keeps and whose default is the first of them',
	array_keys( $choices ) === [ 'harmony', 'temperature', 'saturation', 'contrast', 'depth', 'mode' ]
	&& $choices['mode'] === [ 'kind' => 'choice', 'default' => 'auto', 'steps' => [ 'auto', 'light', 'dark' ] ]
	&& $choices['harmony']['kind'] === 'choice' && count( $choices['harmony']['steps'] ) === 4 && $choices['harmony']['default'] === 1
	&& $choices['temperature']['kind'] === 'choice' && count( $choices['temperature']['steps'] ) === 4 && $choices['temperature']['default'] === 3
	&& array_filter( [ 'saturation', 'contrast', 'depth' ], static fn( string $knob ): bool => $choices[$knob] !== [ 'kind' => 'scale', 'default' => 3, 'steps' => 5 ] ) === [] );

// The compiled sheet carries it, in the section the order test named
check( 'the compiled sheet carries the palette', str_contains( $css, ':root[data-nino-mode="dark"] {' ) === true
	&& substr_count( $css, '--nino-brand-safe:' ) >= 1 );

/*	The three scales that reach furthest at their outer positions - Saturation,
	Contrast and Depth - and what none of those positions may do. Position 3 is
	the framework itself (the base.css identity above); 1 and 5 are free to go
	as far as the floors allow, 2 and 4 stand between them and it, and the floors
	are what is held here: on four primaries that are nothing alike - a light blue,
	a dark red, a yellow, a near-black - the text clears 4.5:1 (7:1 at the fourth
	position, 10:1 at the fifth), muted text 4.5:1, a link its own target (6.98:1 at
	the fourth, 9.98:1 at the fifth), a border and the focus ring the 3:1 of SC 1.4.11,
	and a border 5:1 at the fourth Depth and 7:1 at the fifth where the ground leaves
	room for one. The brand is the one surface that is the colour that was picked,
	so as in the check above it is not measured for text.

	Every combination of the five positions of three knobs on four primaries in two
	modes is 1000 palettes of 25ms each. So the ends and the middle are held in every
	combination (1, 3 and 5 of each knob), and the two positions between are held with
	the other two knobs at the ends, in the middle and at themselves	*/
$grid = [];

foreach( [ 1, 3, 5 ] as $saturation )
	foreach( [ 1, 3, 5 ] as $contrast )
		foreach( [ 1, 3, 5 ] as $depth )
			$grid[] = [ $saturation, $contrast, $depth ];

foreach( [ 2, 4 ] as $between ) {

	$grid[] = [ $between, $between, $between ];

	foreach( [ 1, 3, 5 ] as $others ) {
		$grid[] = [ $between, $others, $others ];
		$grid[] = [ $others, $between, $others ];
		$grid[] = [ $others, $others, $between ];
	}
}

$textFloor		= [ 1 => 4.5, 2 => 4.5, 3 => 4.5, 4 => 7.0, 5 => 10.0 ];
// Only a link has the solver stopping within a hair of its target (9.99:1 on some harmonies), so that one is held a hair under
$linkFloor		= [ 1 => 4.5, 2 => 4.5, 3 => 4.5, 4 => 6.98, 5 => 9.98 ];
$borderFloor	= [ 1 => 3.0, 2 => 3.0, 3 => 3.0, 4 => 5.0, 5 => 7.0 ];
$reach				= [];
$reached			= 0;

foreach( [ '#4faae8', '#8b1d3f', '#facc15', '#111827' ] as $primary )
	foreach( $grid as [ $saturation, $contrast, $depth ] )
		foreach( [ 'light', 'dark' ] as $mode ) {

			$settings = [ 'primary' => $primary, 'saturation' => $saturation, 'contrast' => $contrast, 'depth' => $depth ];
			$label		= $primary. ' sat '. $saturation. ' con '. $contrast. ' dep '. $depth. ' '. $mode;

			foreach( Colours::palette( $settings, $mode ) as $surface => $v ) {

				$reached++;

				if( $surface === 'brand' )
					continue;

				$ratio = static fn( string $key ): float => Colours::contrast( $v[$key], $v['bg'] );

				if( $ratio( 'on' ) < $textFloor[$contrast] )
					$reach[] = $label. ' '. $surface. ' on';
				if( $ratio( 'on-muted' ) < 4.5 )
					$reach[] = $label. ' '. $surface. ' on-muted';
				if( $ratio( 'link' ) < $linkFloor[$contrast] )
					$reach[] = $label. ' '. $surface. ' link';
				if( $ratio( 'focus' ) < 3.0 )
					$reach[] = $label. ' '. $surface. ' focus';
				if( $ratio( 'border' ) < 3.0 )
					$reach[] = $label. ' '. $surface. ' border';
				if( in_array( $surface, [ 'default', 'alt', 'tint', 'dark', 'black' ], true ) === true && $ratio( 'border' ) < $borderFloor[$depth] )
					$reach[] = $label. ' '. $surface. ' border at '. $borderFloor[$depth]. ':1';
			}
		}

check( 'no position of Saturation, Contrast or Depth takes a floor away, in either mode, on four unlike primaries ('. count( $grid ). ' combinations, '. $reached. ' surfaces)'
	. ( $reach === [] ? '' : ' - '. implode( ', ', array_slice( $reach, 0, 5 ) ) ), $reach === [] && count( $grid ) === 47 );

$chromaAt = static function( int $saturation ): float {
	return Colours::oklch( Colours::palette( [ 'primary' => '#4faae8', 'saturation' => $saturation ], 'light' )['brand-safe']['bg'] )[1];
};
// The alternate ground is a grey the brand tints, so it takes every step of the knob, where a surface solved against its ink soon meets the gamut
$tintAt = static function( int $saturation, string $primary ): float {
	return Colours::oklch( Colours::palette( [ 'primary' => $primary, 'saturation' => $saturation ], 'light' )['alt']['bg'] )[1];
};

check( 'Saturation never takes colour away as it goes up, and Muted and Rich are apart from Default',
	$chromaAt( 1 ) <= $chromaAt( 2 ) && $chromaAt( 2 ) <= $chromaAt( 3 ) && $chromaAt( 3 ) <= $chromaAt( 4 ) && $chromaAt( 4 ) <= $chromaAt( 5 )
	&& $chromaAt( 1 ) < $chromaAt( 3 ) - 0.02 && $chromaAt( 3 ) < $chromaAt( 5 ) );
check( '...and its positions are apart where the knob can be seen: the tint of the alternate ground rises at every step to the fourth and is not less at the fifth - where the gamut ends it on some hues - on a light blue and on a dark red',
	array_filter( [ '#4faae8', '#8b1d3f' ], static fn( string $primary ): bool => array_filter( [ 1, 2, 3 ], static fn( int $at ): bool => $tintAt( $at, $primary ) >= $tintAt( $at + 1, $primary ) ) !== []
		|| $tintAt( 4, $primary ) > $tintAt( 5, $primary ) || $tintAt( 3, $primary ) >= $tintAt( 5, $primary ) ) === [] );

$depthOf = static fn( int $depth, string $mode ): array => Colours::palette( [ 'depth' => $depth ], $mode );

foreach( [ 'light', 'dark' ] as $depthMode ) {
	$bands = [];

	foreach( [ 1, 2, 3, 4, 5 ] as $depth )
		$bands[$depth] = Colours::contrast( $depthOf( $depth, $depthMode )['alt']['bg'], $depthOf( $depth, $depthMode )['default']['bg'] );

	check( 'Depth takes five steps in '. $depthMode. ': the band against the page grows at every one ('. implode( ', ', array_map( static fn( float $band ): string => (string) round( $band, 3 ), $bands ) ). ':1)',
		$bands[1] < $bands[2] && $bands[2] < $bands[3] && $bands[3] < $bands[4] && $bands[4] < $bands[5] );
	// Flat is flatter, not gone: with no band at all the alternate sections
	// vanish into the page
	check( '...and Flat keeps a trace of the band in '. $depthMode. ' ('. round( $bands[1], 3 ). ':1 against the page)', $bands[1] >= 1.015 && $bands[1] < 1.05 );
}

/*	The shadow a surface casts is the palette's colour and not its depth: one
	alpha for a mode, whatever the surface and whatever the Depth, denser in the
	dark palette because black at 12% over a dark ground is invisible. How much
	of a shadow a card throws is the content template's to say	*/
$shadows = [];

foreach( [ 'light', 'dark' ] as $shadowMode )
	foreach( [ 1, 2, 3, 4, 5 ] as $depth )
		$shadows[$shadowMode][] = array_values( array_unique( array_column( $depthOf( $depth, $shadowMode ), 'shadow' ) ) );

check( 'the shadow is one colour for every surface at every Depth, per mode: the same value at all five positions',
	count( array_unique( array_map( 'json_encode', $shadows['light'] ) ) ) === 1 && count( $shadows['light'][0] ) === 1
	&& count( array_unique( array_map( 'json_encode', $shadows['dark'] ) ) ) === 1 && count( $shadows['dark'][0] ) === 1 );

preg_match( '/\/\s*(\d+)%\)/', $shadows['light'][0][0], $lightAlpha );
preg_match( '/\/\s*(\d+)%\)/', $shadows['dark'][0][0], $darkAlpha );

$alphas = ( new ReflectionClassConstant( Colours::class, 'SHADOW' ) )->getValue();

check( '...and it is the delivered one in the light palette, and denser in the dark - '. $shadows['light'][0][0]. ' against '. $shadows['dark'][0][0],
	$shadows['light'][0][0] === $baseTokens['--nino-default-shadow'] && (int) ( $darkAlpha[1] ?? 0 ) > (int) ( $lightAlpha[1] ?? 100 ) && (int) ( $lightAlpha[1] ?? 0 ) > 0
	&& (int) ( $lightAlpha[1] ?? -1 ) === (int) round( $alphas['light'] * 100 ) && (int) ( $darkAlpha[1] ?? -1 ) === (int) round( $alphas['dark'] * 100 ) );

/*	The scrim over a cover photograph has a table of its own. At the fifth
	position the text asks 10:1, and a scrim solved to that would take a photograph
	from 80% dark to about 91% - nearly black - for a headline that is far above AAA
	already at 7:1	*/
$scrimAt = static function( int $contrast ): array {
	preg_match_all( '/--nino-scrim:\s*rgb\([^\/]+\/\s*(\d+)%\)/', Colours::css( [ 'contrast' => $contrast ] ), $found );
	return array_map( 'intval', $found[1] );
};

check( 'the strongest Contrast does not darken a cover photograph beyond 7:1 - the scrim stays at or under 80%, in both modes, and so does the fourth position',
	$scrimAt( 5 ) !== [] && max( $scrimAt( 5 ) ) <= 80 && $scrimAt( 4 ) !== [] && max( $scrimAt( 4 ) ) <= 80 );
check( 'the revision of the colour tables is a number that can be raised, and it was when the shadow stopped following Depth and when the three knobs got five positions', Colours::REVISION >= 3 );

echo "\nWriting it, and what it will not write over\n";

$written = Compiler::write( $appData, $css );
check( 'the first write succeeds - there is no theme.css in a sandbox yet', $written === true );
check( '...and produces the file the css bundle already names', is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true );

$onDisk = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' );
check( 'a sheet Design wrote knows itself', Compiler::stamped( $onDisk ) === true );

$second = Compiler::write( $appData, Compiler::compile( $appData, Setup::normalize( $appData, [ 'size' => 1 ], $library ), $library ) );
check( 'compiling again writes over its own file without being asked twice', $second === true
	&& str_contains( (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' ), '--nino-base-size: 129.375%;' ) === true );

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

/*	The pane is three tabs on top. The kernel draws the strip - the panel's own screen first, the tabs the panel brings by their weights, and the Settings tab of a feature that has settings last
	(see \Nino\Admin\Panels::collect()) - so what the feature holds is the tab it brings and what it is: a panel of the kernel's own kind, with its uri, its words, its mount
	and the permission of the panel, and no action of its own - every design/ action stays on the panel, whichever tab asks for it. Read from the registry of this copy, where the feature is active;
	the uris are the classes' own, never typed here	*/
$paneRegistry	= \Nino\Admin\Admin::panels( $appData );
$paneEntry		= $paneRegistry[ Admin::nav()[0] ] ?? [ 'tabs' => [] ];
$paneClasses	= Admin::tabs();
$paneUris			= array_merge( [ Admin::nav()[0] ], array_map( static fn( string $tab ): string => (string) $tab::nav()[0], $paneClasses ) );
$paneListed		= array_keys( array_filter( \Nino\Admin\Admin::allPanels( $appData ), static fn( array $panel ): bool => $panel['uri'] === $paneUris[0] || $panel['parent'] === $paneUris[0] ) );
$paneWeights	= array_column( $paneEntry['tabs'], 'weight' );
$paneSorted		= $paneWeights;
sort( $paneSorted );

check( 'the registry lists the pane as the strip on top reads - the panel\'s own screen, the loader and, last, the Settings tab the registry attaches - the tabs the panel brings by their weights',
	count( $paneClasses ) === 1 && count( array_unique( $paneUris ) ) === 2
	&& $paneListed === array_merge( $paneUris, [ $paneUris[0]. '-settings' ] ) && array_keys( $paneEntry['tabs'] ) === array_slice( $paneListed, 1 )
	&& $paneWeights === $paneSorted && count( array_unique( $paneWeights ) ) === count( $paneWeights )
	&& ( $paneEntry['tabs'][ $paneUris[0]. '-settings' ]['feature'] ?? '' ) === $feature['key'] );
check( '...the panel\'s own screen is called in the strip by a word other than the one the rail has for the panel - tab() answers a fill, which nav() does not - and it keeps the panel\'s permission and its own mount',
	( $paneEntry['tab'] ?? '' ) === Admin::tab() && Admin::tab() !== ( $paneEntry['label'] ?? '' ) && preg_match( '~^/_admin/nav/[a-z-]+$~', Admin::tab() ) === 1
	&& ( $paneEntry['own'] ?? false ) === true && ( $paneEntry['perm'] ?? '?' ) === Admin::MANAGE_PERM && ( $paneEntry['panes'] ?? [] ) !== [] );

$paneMounts		= $paneEntry['panes'] ?? [];
$paneProblems	= [];

foreach( $paneClasses as $paneClass ) {

	$paneTab = $paneEntry['tabs'][ $paneClass::nav()[0] ] ?? [];

	foreach( [ 'actions', 'nav', 'perm', 'panes', 'assets', 'text' ] as $paneMethod )
		if( method_exists( $paneClass, $paneMethod ) === false )
			$paneProblems[] = $paneClass. ' has no '. $paneMethod. '()';

	if( ( $paneTab['class'] ?? '' ) !== $paneClass )
		$paneProblems[] = $paneClass. ' is not on the pane';
	else {

		if( $paneTab['parent'] !== $paneUris[0] || $paneTab['group'] !== $paneEntry['group'] )
			$paneProblems[] = $paneClass. ' does not sit with its panel';

		if( $paneTab['perm'] !== $paneEntry['perm'] )
			$paneProblems[] = $paneClass. ' has a permission of its own';

		if( $paneTab['assets'] !== $paneEntry['assets'] || $paneTab['text'] !== $paneEntry['text'] || $paneTab['text'] === '' || $paneTab['assets'] === [] )
			$paneProblems[] = $paneClass. ' does not share the panel\'s script, stylesheet and words';

		if( $paneTab['panes'] === [] || array_intersect( $paneTab['panes'], $paneMounts ) !== [] )
			$paneProblems[] = $paneClass. ' has no mount of its own';

		if( preg_match( '~^/_admin/nav/[a-z-]+$~', $paneTab['label'] ) !== 1 )
			$paneProblems[] = $paneClass. ' is labelled by no fill';

		$paneMounts = array_merge( $paneMounts, $paneTab['panes'] );
	}

	if( $paneClass::actions() !== [] )
		$paneProblems[] = $paneClass. ' brings an action of its own';
}

$paneActions = \Nino\Admin\Admin::actions( $appData );

check( '...the tab is a panel of the kernel\'s own kind - actions(), nav(), perm(), panes(), assets() and text() - that sits with its panel, has its permission, shares its script, its stylesheet and its words, and draws into a mount of its own, no two the same'
	. ( $paneProblems === [] ? '' : ' - '. implode( ', ', array_slice( $paneProblems, 0, 3 ) ) ),
	$paneProblems === [] && count( $paneMounts ) === count( array_unique( $paneMounts ) ) && count( $paneMounts ) === 2 );
check( '...and brings no action: every design/ action is the panel\'s, whichever tab asks for it - the registry maps the actions of the pane to the panel\'s class and to nothing else',
	array_filter( $paneActions, static fn( array $handler ): bool => in_array( $handler[0], $paneClasses, true ) ) === []
	&& array_filter( array_keys( Admin::actions() ), static fn( string $action ): bool => ( $paneActions[$action][0] ?? '' ) !== Admin::class ) === [] );

// ...and the script keeps one namespace for each of the two, by the uri of its tab - the shell asks Nino.admin[<uri>] for the screen of a tab - and, beside the panel's, the one of the views of its setup, which the shell does not ask for; every mount is a literal of it
preg_match_all( '/^\tNino\.admin(?:\.([a-z]+(?:\.[a-z]+)?)|\[\'([a-z-]+)\'\])\s*=\s*\{/m', $panelScript, $scriptSpaces, PREG_SET_ORDER );

check( 'the script keeps one namespace for the panel, one for the views of its setup and one for its tab - the uris the registry lists, the views after the panel\'s - and every mount the pane names is a literal of it',
	array_map( static fn( array $found ): string => ( $found[2] ?? '' ) !== '' ? $found[2] : $found[1], $scriptSpaces ) === [ $paneUris[0], $paneUris[0]. '.views', $paneUris[1] ]
	&& array_filter( $paneMounts, static fn( string $mount ): bool => str_contains( $panelScript, "'". $mount. "'" ) === false ) === [] );

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
	array_keys( $stored ) === Setup::KEYS && $stored['parts'] === $defaultSets
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
\Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'format' => 1, 'size' => 'l', 'parts' => [ 'header' => [ 'set' => 'first' ] ] ] );
$former = \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] );

check( 'upgrading leaves a file of the format before this one as it is', \Nino\Modules\Design::upgrade( $appData, '1.9.0' ) === true
	&& \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] ) === $former );

$notes = [];
Setup::read( $appData, $library, $notes );

check( '...so the panel can still say that it was not read - until the first save writes the new one', count( $notes ) === 1 && str_contains( $notes[0], 'another format' ) === true );

\Nino\Filesystem::putFileContent( $appData, Setup::PATH, $upgraded );

/*	A frame is a stylesheet AND the markup it was drawn against. Compiling one
	without writing the other is how a page ends up with the css of one header
	over the markup of another, which is exactly what this did before the panel went looking	*/
// The two templates the kernel's base unit delivers, which html-header.tpl and html-footer.tpl include
check( 'the frames it writes are the base unit\'s frame-header.tpl and frame-footer.tpl', sprintf( Compiler::FRAME_TARGET, 'header' ) === '/templates/frame-header.tpl'
	&& sprintf( Compiler::FRAME_TARGET, 'footer' ) === '/templates/frame-footer.tpl' );
$headerTemplate = \Nino\Filesystem::path( $appData, sprintf( Compiler::FRAME_TARGET, 'header' ) );
check( 'applying writes the chosen frame\'s markup, not only its stylesheet',
	is_file( $headerTemplate ) === true
	&& str_contains( (string) file_get_contents( $headerTemplate ), 'nino-scroll-header' ) === true
	&& Compiler::stampedFrame( (string) file_get_contents( $headerTemplate ) ) === true );
check( '...and the markup is the variant the setup names', str_contains( (string) file_get_contents( $headerTemplate ), 'header '. $defaultSets['header']. '.' ) === true );

$byHand = "<header>my own</header>\n";
file_put_contents( $headerTemplate, $byHand );
$notes = [];
$stylesheetBefore = (string) file_get_contents( \Nino\Filesystem::path( $appData, Compiler::TARGET ) );
// A setup that would compile to something else, so "nothing was written"
// is a statement about this run rather than about two identical files
$sizedSetup = Setup::read( $appData, \Nino\Modules\Design::libraryDir(), $notes );
$sizedSetup['size'] = $sizedSetup['size'] === 1 ? 0 : 1;
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
	isset( $listed['parts'][0]['catalogue'][ $defaultSets['header'] ] ) === true
	&& ( $listed['parts'][0]['set'] ?? null ) === $defaultSets['header'] && ( $listed['parts'][2]['set'] ?? null ) === $defaultSets['section']
	&& ( $listed['parts'][0]['kind'] ?? null ) === 'frame' && ( $listed['parts'][2]['kind'] ?? null ) === 'set' );
// A sandbox is German - its current locale is the one the workbench's texts are chosen by - and the list names a template in it: the name and the
// description its own file gives in that language, whichever sets the library has - what Setup::describe() says of each of them
$listedWords = static function( array $answer, string $locale ) use ( $library ): array {

	$problems = [];

	foreach( $answer['parts'] as $row )
		foreach( $row['catalogue'] as $set => $entry ) {

			$described = Setup::describe( $library, $row['part'], (string) $set, $locale );

			if( ( $entry['name'] ?? null ) !== $described['name'] || ( $entry['description'] ?? null ) !== $described['description'] || $described['description'] === '' )
				$problems[] = $row['part']. '/'. $set;
		}

	return $problems;
};

check( 'every variant comes with the name and description its own file carries, in the language of the workbench - here the German one - for every set of every part, as Setup::describe() reads them'
	. ( $listedWords( $listed, 'de_DE' ) === [] ? '' : ' - not so for '. implode( ', ', $listedWords( $listed, 'de_DE' ) ) ),
	\Nino\Locales::getCurrentLocale( $appData ) === 'de_DE' && $listedWords( $listed, 'de_DE' ) === []
	&& array_keys( $listed['parts'][2]['catalogue'] ) === Setup::available( $library, 'section' ) );

\Nino\Locales::useLocale( $appData, 'en_US' );
[ , $listedEnglish ] = callDesignAction( $appData, 'apiList' );
\Nino\Locales::useLocale( $appData, 'de_DE' );

check( '...and in English where the workbench speaks English: the same sets under the lines of the other language, and the same knobs',
	$listedWords( $listedEnglish, 'en_US' ) === []
	&& array_column( $listedEnglish['parts'][2]['catalogue'], 'knobs' ) === array_column( $listed['parts'][2]['catalogue'], 'knobs' ) );
$knobProblems = [];

foreach( $listed['parts'] as $row )
	foreach( $row['catalogue'] as $set => $entry )
		if( ( $entry['knobs'] ?? null ) !== Setup::knobs( $library, $row['part'], (string) $set ) )
			$knobProblems[] = $row['part']. '/'. $set;

check( 'a template of any of the seven parts names the knobs it answers to, each of them, as the stylesheet declares them - a frame\'s too, which answers to all four of its part\'s'
	. ( $knobProblems === [] ? '' : ' - not so for '. implode( ', ', $knobProblems ) ), $knobProblems === []
	&& ( $listed['parts'][2]['catalogue'][ $defaultSets['section'] ]['knobs'] ?? null ) === array_keys( Setup::KNOBS['section'] )
	&& ( $listed['parts'][0]['catalogue'][ $defaultSets['header'] ]['knobs'] ?? null ) === array_keys( Setup::KNOBS['header'] )
	&& ( $listed['parts'][1]['catalogue'][ $defaultSets['footer'] ]['knobs'] ?? null ) === array_keys( Setup::KNOBS['footer'] ) );
check( 'and the screen is handed the vocabulary it draws: the parts with the kind of each knob, the five steps, the size, the width, the whitespace and the two radii as numbers, the strength of each kind of surface and the two words there are, the effects and the kinds of hover with their strengths and speeds - and no list of sizes: a strip numbers them, and none of the surfaces: the strengths name the kinds',
	( $listed['kinds'] ?? null ) === Setup::KNOBS && ( $listed['steps'] ?? null ) === Setup::STEPS && count( $listed['steps'] ) === 5
	&& array_key_exists( 'sizes', $listed ) === false && ( $listed['size'] ?? null ) === $onFile['size'] && is_int( $listed['size'] ) === true && ( $listed['width'] ?? null ) === 0 && is_int( $listed['width'] ) === true
	&& ( $listed['whitespace'] ?? null ) === 0 && is_int( $listed['whitespace'] ) === true
	&& ( $listed['radius'] ?? null ) === 0 && is_int( $listed['radius'] ) === true && ( $listed['radius-alt'] ?? null ) === 1 && is_int( $listed['radius-alt'] ) === true
	&& ( $listed['radii'] ?? null ) === array_fill_keys( Setup::SURFACES, 'standard' ) && array_key_exists( 'surfaces', $listed ) === false && ( $listed['radiusWords'] ?? null ) === Setup::RADIUS_WORDS
	&& ( $listed['motion'] ?? null ) === [ 'effect' => '', 'strength' => '', 'speed' => '', 'hover' => '', 'hover-strength' => '' ]
	&& ( $listed['effects'] ?? null ) === array_keys( Setup::EFFECTS ) && ( $listed['hovers'] ?? null ) === array_keys( Setup::HOVERS ) && ( $listed['strengths'] ?? null ) === Setup::STRENGTHS
	&& ( $listed['speeds'] ?? null ) === array_keys( Setup::SPEEDS ) );
check( '...and where every control of the panel stands: the table of the model with the six views and the eight objects it is made of - the screen is drawn from it and from nothing else',
	( $listed['layout'] ?? null ) === [ 'tabs' => Setup::TABS, 'objects' => Setup::OBJECTS, 'controls' => Setup::LAYOUT ] );
check( '...where every knob stands, the five font roles with what each has and what each can be given, and the bounds of a typed size with the one Nino.css gives a role - no list of steps',
	( $listed['knobs'] ?? null ) === $onFile['knobs'] && $onFile['knobs'] === Setup::defaults( $library )['knobs']
	&& ( $listed['roles'] ?? null ) === Fonts::ROLES && ( $listed['fonts'] ?? null ) === Fonts::DEFAULTS
	&& ( $listed['weights'] ?? null ) === Fonts::WEIGHTS && ( $listed['bounds'] ?? null ) === Fonts::BOUNDS && array_key_exists( 'fontsizes', $listed ) === false
	&& ( $listed['inherited'] ?? null ) === Fonts::INHERITED
	&& ( $listed['options']['title'] ?? null ) === Fonts::options( $appData, 'title' ) && ( $listed['options']['code'] ?? null ) === [ '' ] );
check( '...and the families with their names, their category and whether they are a system stack - and not one file name or url',
	array_keys( (array) ( $listed['families'] ?? [] ) ) === array_keys( Fonts::families( $appData ) )
	&& ( $listed['families']['open-sans'] ?? null ) === [ 'name' => 'Open Sans', 'category' => 'sans', 'system' => false ]
	&& ( $listed['families']['system-mono']['system'] ?? null ) === true && str_contains( json_encode( $listed['families'] ), 'woff2' ) === false );
check( 'the file on disk is ours and answers to the setup', ( $listed['exists'] ?? null ) === true
	&& ( $listed['ours'] ?? null ) === true && ( $listed['current'] ?? null ) === true );

// A header template that does not answer to the position of the navigation: whatever was posted for the knob, the setup keeps it where the defaults put it - and not at 0
$noPosition = array_values( array_filter( Setup::available( $library, 'header' ), static fn( string $set ): bool => in_array( 'position', Setup::knobs( $library, 'header', $set ), true ) === false ) )[0] ?? '';
[ $status, ] = callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'header' => $noPosition ], 'knobs' => [ 'header' => [ 'position' => -1 ] ] ] );
$kept = Setup::read( $appData, \Nino\Modules\Design::libraryDir() );

check( 'a header template that leaves the position out keeps no position posted for it: the setup holds the one the defaults put it at, which is not the middle',
	$noPosition !== '' && $status === 200 && $kept['parts']['header'] === $noPosition
	&& $kept['knobs']['header']['position'] === Setup::defaults( $library )['knobs']['header']['position'] && Setup::defaults( $library )['knobs']['header']['position'] !== 0 );

// A header template that does not answer to the case of the navigation (a toggle): a switch posted on for it is off again, and for the template that does answer it stays on - and so for the footer
$noCase = array_values( array_filter( Setup::available( $library, 'header' ), static fn( string $set ): bool => in_array( 'nav-case', Setup::knobs( $library, 'header', $set ), true ) === false ) )[0] ?? '';
$noFooterCase = array_values( array_filter( Setup::available( $library, 'footer' ), static fn( string $set ): bool => in_array( 'nav-case', Setup::knobs( $library, 'footer', $set ), true ) === false ) )[0] ?? '';
[ $statusOff, ] = callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'header' => $noCase, 'footer' => $noFooterCase ], 'knobs' => [ 'header' => [ 'nav-case' => 1 ], 'footer' => [ 'nav-case' => 1 ] ] ] );
$keptOff = Setup::read( $appData, \Nino\Modules\Design::libraryDir() );
[ $statusOn, ] = callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'header' => $defaultSets['header'], 'footer' => $defaultSets['footer'] ], 'knobs' => [ 'header' => [ 'nav-case' => 1 ], 'footer' => [ 'nav-case' => 1 ] ] ] );
$keptOn = Setup::read( $appData, \Nino\Modules\Design::libraryDir() );

check( 'a header and a footer template that leave the case of the navigation out keep no switch posted for it - the setup holds it off - while the default templates keep it on',
	$noCase !== '' && $noFooterCase !== '' && $statusOff === 200 && $statusOn === 200
	&& $keptOff['parts']['header'] === $noCase && $keptOff['knobs']['header']['nav-case'] === 0 && $keptOff['parts']['footer'] === $noFooterCase && $keptOff['knobs']['footer']['nav-case'] === 0
	&& $keptOn['knobs']['header']['nav-case'] === 1 && $keptOn['knobs']['footer']['nav-case'] === 1 );
callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'header' => $defaultSets['header'], 'footer' => $defaultSets['footer'] ], 'knobs' => [] ] );

// Saving is not compiling: the decision lands, the stylesheet does not move
// Every knob of every part is moved - each to +1, which a scale and a choice both reach - on another set for each part, whichever the library has: what is kept is what the chosen set answers to
[ $status, $saved ] = callDesignAction( $appData, 'apiSave', [
	'parts' => array_intersect_key( $richParts, array_flip( [ 'section', 'card', 'buttons', 'lists', 'tables' ] ) ),
	'knobs' => $every( 1 ),
	'size' => 1, 'width' => 2, 'whitespace' => -1, 'radius' => 2, 'radius-alt' => -1, 'radii' => [ 'column' => 'alternative' ],
	'motion' => [ 'effect' => 'slide-left', 'strength' => 'soft', 'speed' => 'fast', 'hover' => 'bright', 'hover-strength' => 'hard' ],
	'fonts' => [ 'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'size' => '2.4' ] ],
	'colours' => [ 'mode' => 'dark' ],
] );
$stored = Setup::read( $appData, \Nino\Modules\Design::libraryDir() );

check( 'saving stores the selection: the templates, the size, the width, the whitespace, the radius, the animation, the hover of the pictures, the fonts and the mode', $status === 200
	&& $stored['parts']['section'] === $richParts['section'] && $stored['parts']['card'] === $richParts['card'] && $stored['parts']['tables'] === $richParts['tables'] && $stored['parts']['buttons'] === $richParts['buttons'] && $stored['size'] === 1 && $stored['width'] === 2 && $stored['whitespace'] === -1
	&& $stored['radius'] === 2 && $stored['radius-alt'] === -1 && $stored['radii'] === array_replace( array_fill_keys( Setup::SURFACES, 'standard' ), [ 'column' => 'alternative' ] )
	&& $stored['motion'] === [ 'effect' => 'slide-left', 'strength' => 'soft', 'speed' => 'fast', 'hover' => 'bright', 'hover-strength' => 'hard' ]
	&& $stored['fonts']['title'] === [ 'family' => 'system-serif', 'weight' => 'light', 'size' => '2.4' ]
	&& $stored['colours']['mode'] === 'dark' );
check( '...and says the file no longer answers to it, rather than moving it', ( $saved['current'] ?? null ) === false
	&& ( $saved['ours'] ?? null ) === true );
$answered = [];
$kept = [];

foreach( Setup::KNOBS as $part => $kinds )
	foreach( array_keys( $kinds ) as $knob ) {

		if( in_array( $knob, Setup::knobs( $library, $part, $stored['parts'][$part] ), true ) === true )
			$answered[] = $part. '/'. $knob;

		$kept[$part. '/'. $knob] = $stored['knobs'][$part][$knob];
	}

check( 'a knob stands where it was put, for the knobs the chosen template answers to - a scale or a choice, +1 being a position of both',
	$answered !== [] && array_filter( $answered, static fn( string $knob ): bool => $kept[$knob] !== 1 ) === [] );
check( '...and a position for a knob the chosen template does not answer to is not kept: every one of them is where the defaults put it again, whichever knobs the sets leave out',
	array_filter( array_diff( array_keys( $kept ), $answered ), static fn( string $knob ): bool => $kept[$knob] !== $themeKnobs[ explode( '/', $knob )[0] ][ explode( '/', $knob )[1] ] ) === [] );

/*	What the server answers for a project with this selection - another template
	for each of the three parts, a font, the width, the animation and the mode moved -
	is written down for design-js-smoke.js, which draws the screen from it: the
	stand-in listing of that test is only as good as it is like this one	*/
[ , $forScreen ] = callDesignAction( $appData, 'apiList' );
$listingFile = ninoSandboxDir( $appData ). '/design-listing.json';
file_put_contents( $listingFile, json_encode( $forScreen, JSON_THROW_ON_ERROR ) );

check( 'the answer carries the setup a fresh install starts from, beside the one the project has - what the head of a closed row is compared with - cut to the keys that choose something: it does not follow the project, whose knobs have moved, and holds nothing that is the project\'s own',
	( $forScreen['defaults'] ?? null ) === Themes::snapshot( Setup::defaults( $library ) ) && ( $forScreen['defaults'] ?? null ) === ( $listed['defaults'] ?? 'x' )
	&& ( $forScreen['knobs'] ?? null ) !== ( $forScreen['defaults']['knobs'] ?? null ) && ( $forScreen['size'] ?? null ) !== ( $forScreen['defaults']['size'] ?? null )
	&& array_diff( array_keys( (array) ( $forScreen['defaults'] ?? [] ) ), Setup::KEYS ) === []
	&& array_intersect( [ 'format', 'theme', 'css', 'compiled' ], array_keys( (array) ( $forScreen['defaults'] ?? [] ) ) ) === [] );

[ $status, $applied ] = callDesignAction( $appData, 'apiApply' );
$appliedSheet = (string) \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' );

check( 'applying compiles it and the file answers again', $status === 200 && ( $applied['current'] ?? null ) === true );
check( '...and the root size, the width, the whitespace, the radius and the animation the save asked for are in the stylesheet: the larger size, a wide page with no maximum and no gutter, a little less air, the two strengths at their lengths and the column on the second, a fast animation',
	str_contains( $appliedSheet, '--nino-base-size: 115%;' ) === true && str_contains( $appliedSheet, '--nino-base-size: 129.375%;' ) === true
	&& str_contains( $appliedSheet, ':root { --grid-max-width: none; --grid-gutter: var(--space-2); }' ) === true && str_contains( $appliedSheet, ':root { --whitespace-scale: '. Setup::WHITESPACE[-1]. '; }' ) === true
	&& str_contains( $appliedSheet, "\t--radius-surface: ". Setup::RADIUS[2]. ";\n\t--radius-surface-alt: ". Setup::RADIUS[-1]. ";\n" ) === true
	&& str_contains( $appliedSheet, "\t--radius-column: var(--radius-surface-alt);\n\t--radius-column-default: alternative;\n" ) === true
	&& str_contains( $appliedSheet, '--vpa-duration: '. Setup::SPEEDS['fast']. ';' ) === true
	&& str_contains( $appliedSheet, '--font-title-size: 2.4rem;' ) === true && preg_match( '/--font-[a-z]+-transform/', $appliedSheet ) !== 1 && str_contains( $appliedSheet, 'color-scheme: dark;' ) === true );

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
[ $status, $refused ] = callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'section' => 'nope' ], 'size' => 0 ] );
check( 'a variant that is not in the library falls back and is named - by its code and its params, in the answer a person\'s screen says in words',
	$status === 200 && count( (array) ( $refused['notes'] ?? [] ) ) === 1
	&& ( $refused['notes'][0]['code'] ?? '' ) === 'design_set_missing' && ( $refused['notes'][0]['params'] ?? [] ) === [ 'section', 'nope', $defaultSets['section'] ] );
check( 'a set name that could climb out of the library never becomes a path',
	callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'section' => '../../../etc/passwd' ], 'size' => 0 ] )[0] === 200
	&& Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['parts']['section'] === $defaultSets['section'] );

// ...and neither does a size that is not a string, or any other key of the
// model posted as the wrong type. Cast, an array raises "Array to string
// conversion", a level the kernel treats as fatal - a 500 on the panel's own
// save where a fallback is the answer
ninoWarnings();
check( 'a size posted as an array falls back rather than raising',
	callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'size' => [ 1 ] ] )[0] === 200
	&& ninoWarnings() === []
	&& Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['size'] === 0 );
check( '...and so does every other key of it',
	callDesignAction( $appData, 'apiSave', [ 'parts' => 'first', 'knobs' => 'more', 'size' => 5, 'width' => [ 1 ], 'whitespace' => [ 1 ], 'radius' => [ 1 ], 'radius-alt' => 'more', 'radii' => 'card', 'motion' => 'flip', 'fonts' => 5, 'colours' => 'dark' ] )[0] === 200
	&& ninoWarnings() === [] && Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['width'] === 0
	&& Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['radius'] === 0 && Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['radius-alt'] === 1
	&& Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['radii'] === array_fill_keys( Setup::SURFACES, 'standard' ) );
echo "\nApplying asks first, keeps the version before, and restores it\n";

$slotPath	= \Nino\Filesystem::path( $appData, Previous::PATH );
$targets	= Previous::targets();
$pathOf		= static fn( string $target ): string => \Nino\Filesystem::path( $appData, $target );

/*	The frames the sequences below write, taken from the library by what they write and not by name: one set of a part for each
	markup it has - two sets with the same template.tpl would leave a file unchanged - in the order the library lists them. A pair is
	a header and a footer; three pairs that differ in both are what a version, a version before it and a draft over both need	*/
$distinctFrames = static function( string $part ) use ( $library ): array {

	$byMarkup = [];

	foreach( Setup::available( $library, $part ) as $set )
		$byMarkup[ (string) hash_file( 'sha256', Setup::file( $library, $part, $set, 'template' ) ) ] ??= $set;

	return array_values( $byMarkup );
};
$headersApplied = $distinctFrames( 'header' );
$footersApplied = $distinctFrames( 'footer' );
$pairs = [];

foreach( [ 0, 1, 2 ] as $at )
	$pairs[$at] = [ 'header' => $headersApplied[$at] ?? '', 'footer' => $footersApplied[$at] ?? '' ];

check( 'the library has the frames these sequences need: three headers and three footers that write different markup',
	count( $headersApplied ) >= 3 && count( $footersApplied ) >= 3 );

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
check( '...each by the part it is for - none for the stylesheet, the part of the frame for a frame: what the screen names a file by, in words, and not by its path',
	array_column( $plan, 'part', 'target' ) === [ Compiler::TARGET => '', sprintf( Compiler::FRAME_TARGET, 'header' ) => 'header', sprintf( Compiler::FRAME_TARGET, 'footer' ) => 'footer' ]
	&& array_keys( reset( $plan ) ) === [ 'target', 'part', 'exists', 'state', 'changes', 'lost' ] );
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
callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'knobs' => [], 'size' => -1 ] );
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
$sans = static function( array $setup ): array {
	unset( $setup['compiled'] );
	return $setup;
};

callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[0], 'knobs' => [], 'size' => 0, 'colours' => [] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );

$setupA = $sans( Setup::read( $appData, $library ) );
$filesA = $onDisk();

callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[1], 'knobs' => [], 'size' => 1, 'colours' => [ 'contrast' => 5 ] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply' );

$setupB = $sans( Setup::read( $appData, $library ) );
$filesB = $onDisk();

check( 'the two versions of the sequence differ in files, choices, size and colours', $filesA !== $filesB && $setupA !== $setupB
	&& $setupA['size'] === 0 && $setupB['size'] === 1 && $setupB['parts']['header'] === $pairs[1]['header'] && $setupB['colours']['contrast'] === 5 );
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
callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[2], 'knobs' => [], 'size' => -1, 'colours' => [] ] );
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
callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[1], 'knobs' => [], 'size' => 1, 'colours' => [] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply' );
[ $status, $legacyBack ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring over a record written before it carried its setup reads saved, not applied: the files are ours, the record is there, the draft is not current',
	$status === 200 && ( $legacyBack['exists'] ?? null ) === true && ( $legacyBack['ours'] ?? null ) === true
	&& ( $legacyBack['current'] ?? null ) === false && ( $legacyBack['compiled'] ?? '' ) !== ''
	&& array_column( (array) ( $legacyBack['files'] ?? [] ), 'state' ) === [ 'ours', 'ours', 'ours' ] );

// A project compiled before the tables moved has a fingerprint without the
// revision. With a colour knob off 3 that is "saved, not applied" once; with
// all three at 3 the bytes did not change and neither does the answer
$legacyOff = Setup::read( $appData, $library );
$legacyOff['colours']['contrast'] = 5;
Setup::write( $appData, $legacyOff );
callDesignAction( $appData, 'apiApply' );
$legacyOff = Setup::read( $appData, $library );
check( 'a project applied with Contrast at the fifth position reads current', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );
$legacyOff['compiled']['input'] = $beforeRevision( $legacyOff, $library );
Setup::write( $appData, $legacyOff );
check( '...and once it was applied before the revision existed it reads saved, not applied', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === false );

$legacyDefault = Setup::read( $appData, $library );
$legacyDefault['colours']['contrast'] = 3;
Setup::write( $appData, $legacyDefault );
callDesignAction( $appData, 'apiApply' );
$legacyDefault = Setup::read( $appData, $library );
$legacyDefault['compiled']['input'] = $beforeRevision( $legacyDefault, $library );
Setup::write( $appData, $legacyDefault );
check( 'with every colour knob at 3 the same record still reads current', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );

/*	A write the disk refuses half way. The stylesheet goes out first and the
	frames after it, so a frame that failed used to leave the new stylesheet over
	the old markup - and the version kept for a change that then did not happen
	stayed behind as the version before, where there had been none. A directory
	where a file belongs is how a test makes the disk say no. What the answer has
	to leave is the site as the call found it	*/
callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[0], 'knobs' => [], 'size' => 0, 'colours' => [] ] );
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );
callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[1], 'knobs' => [], 'size' => 1, 'colours' => [] ] );
callDesignAction( $appData, 'apiApply' );

$old 			= $onDisk();
$oldSlot	= Previous::read( $appData );

// The draft that changes all three files: what the apply writes, and so what it can fail at
callDesignAction( $appData, 'apiSave', [ 'parts' => $pairs[2], 'knobs' => [], 'size' => -1, 'colours' => [] ] );
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
callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'knobs' => [], 'size' => 0 ] );

echo "\nThe knobs: each part's own vocabulary, and what the shipped templates answer to\n";

$knobNames = array_map( 'array_keys', Setup::KNOBS );

check( 'a set publishes a knob by declaring its ladder: the default set of each part answers to every knob of the part, in the order of the part - it is the framework as it is, with something for each knob to reach',
	array_filter( array_keys( Setup::KNOBS ), static fn( string $part ): bool => Setup::knobs( $library, $part, $defaultSets[$part] ) !== $knobNames[$part] ) === [] );
check( 'a set that is not there answers to nothing, and neither does a part that is not there',
	Setup::knobs( $library, 'section', 'nope' ) === [] && Setup::knobs( $library, 'nowhere', $defaultSets['section'] ) === [] );
check( '...and every set of every part answers to knobs of its own part, in the order of the part, with the ladder of the knob itself first and the aspects after it, named after the knob',
	array_filter( array_keys( Setup::KNOBS ), static function( string $part ) use ( $library, $knobNames ): bool {

		foreach( Setup::available( $library, $part ) as $set ) {

			$ladders = Setup::ladders( $library, $part, $set );

			if( array_keys( $ladders ) !== array_values( array_intersect( $knobNames[$part], array_keys( $ladders ) ) ) )
				return true;

			foreach( $ladders as $knob => $names )
				if( ( $names[0] ?? '' ) !== $knob || array_filter( array_slice( $names, 1 ), static fn( string $aspect ): bool => str_starts_with( $aspect, $knob. '-' ) === false ) !== [] )
					return true;
		}

		return false;
	} ) === [] );

/*	Every template at every position of its knobs: the sheet it compiles to
	selects exactly the ladders the template declares and not one more, every
	selection points at a step the sheet declares, and the middle position is
	the template's own value for each. A scale is taken to its two ends, -2 and
	+2 (the words least and most); a choice has only the three inner positions,
	so what is asked of it past them is the middle	*/
$selectProblems = [];
$compiled = 0;

foreach( Setup::PARTS as $part => $kind ) {

	foreach( Setup::available( $library, $part ) as $set )
		foreach( [ -2, -1, 0, 1, 2 ] as $position ) {

			$sheet = Compiler::compile( $appData, Setup::normalize( $appData, [ 'parts' => [ $part => $set ], 'knobs' => [ $part => array_fill_keys( $knobNames[$part], $position ) ] ], $library ), $library );
			$block = designPlain( substr( $sheet, (int) strrpos( $sheet, 'the knob positions' ) ) );
			$plain = designPlain( $sheet );

			$expected = [];
			foreach( Setup::ladders( $library, $part, $set ) as $knob => $names )
				foreach( $names as $name )
					$expected[ '--'. $part. '-'. $name ] = 'var(--'. $part. '-'. $name. '--'. Setup::word( Setup::position( $position, Setup::kind( $part, $knob ) ) ). ')';

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

check( 'every template at every position of its knobs ('. $compiled. ' sheets, the frames\' among them) selects the ladders it declares and no others, each a step the sheet declares - a scale from least to most, a choice from less to more'
	. ( $selectProblems === [] ? '' : ' - '. implode( ' | ', array_slice( $selectProblems, 0, 3 ) ) ), $selectProblems === [] && $compiled > 0 );

/*	A ladder that has all the words it should is not yet one that says something: a set declares five values for a
	scale and the middle one is what the set was before it had five - the framework as it is. That the middle is the
	value the template always declared is the pixel measurement's to say (the Playwright pass); what is held here is
	that the ladder of a knob that is a size or a space does not stand still - its ends are not its middle	*/
$flatLadders = [];

foreach( Setup::PARTS as $part => $kind ) {

	foreach( Setup::available( $library, $part ) as $set ) {

		$css = designPlain( (string) file_get_contents( Setup::file( $library, $part, $set ) ) );

		foreach( Setup::ladders( $library, $part, $set ) as $knob => $names ) {

			if( Setup::kind( $part, $knob ) !== 'scale' )
				continue;

			preg_match_all( '/--'. preg_quote( $part. '-'. $knob, '/' ). '--('. implode( '|', Setup::STEPS ). ')\s*:\s*([^;]+);/', $css, $values, PREG_SET_ORDER );

			$byWord = [];
			foreach( $values as $row )
				$byWord[$row[1]][] = trim( $row[2] );

			if( isset( $byWord['least'][0], $byWord['most'][0], $byWord['default'][0] ) === false || $byWord['least'][0] === $byWord['most'][0] )
				$flatLadders[] = $part. '/'. $set. ': '. $knob;
		}
	}
}

check( 'no scale of a shipped template stands still, a frame\'s included: the least and the most of every ladder differ'. ( $flatLadders === [] ? '' : ' - '. implode( ', ', array_slice( $flatLadders, 0, 4 ) ) ), $flatLadders === [] );

/*	The five knobs of the section and the card: the text size of the title and of the subtitle of a section and of a card, and the rule under the title of
	a section. Every section and every card set declares its own as a ladder of five, answers to it, and points the name Nino.css reads at the ladder. The middle
	is what the page was: the number the kernel has, read from its Nino.css here - and the same steps of the number it has from 768px where it
	changes there, as it does for a card title - and for the rule none. A scale rises from step to step; the rule never falls. The measure of
	what the page does is the Playwright pass's	*/
$groupKnobs = [
	'section'	=> [ 'title' => '--section-title-scale', 'subtitle' => '--section-subtitle-scale', 'rule' => '--section-line' ],
	'card'		=> [ 'title' => '--card-title-scale', 'subtitle' => '--card-subtitle-scale' ],
];

// What a value of a ladder comes to: a number, a length, or a factor times a fraction - calc(.8 * 5 / 6)
$evaluate = static function( string $value ): float {

	if( preg_match( '/^calc\(\s*(?:([\d.]+)\s*\*\s*)?(\d+)\s*\/\s*(\d+)\s*\)$/', $value, $found ) === 1 )
		return ( $found[1] === '' ? 1.0 : (float) $found[1] ) * (int) $found[2] / (int) $found[3];

	return (float) $value;
};

// The number the kernel has for each token below 768px and from it, where Nino.css is to be read
$kernelNumber = static fn( string $token, bool $wide ): float => $evaluate( $wide === true ? (string) ( $ninoLarge[$token] ?? $ninoRootDecls[$token] ?? '' ) : (string) ( $ninoRootDecls[$token] ?? '' ) );

if( $ninoCss === '' )
	echo "  --  this checkout has no Nino.css, so the middles of the ladders of the five are not compared with the kernel's\n";

$newLadderProblems = [];

foreach( $groupKnobs as $groupPart => $groupNames )
	foreach( Setup::available( $library, $groupPart ) as $set ) {

		$css 		= designPlain( (string) file_get_contents( Setup::file( $library, $groupPart, $set ) ) );
		$ladders = Setup::ladders( $library, $groupPart, $set );
		$base 		= designDeclarations( designBlock( $css, ':root {' ) );
		$wide 		= designDeclarations( designBlock( designBlock( $css, '@media (min-width: 768px) {' ), ':root {' ) );

		foreach( $groupNames as $knob => $name ) {

			$label	= $groupPart. '/'. $set. ': '. $knob;
			$values = array_map( static fn( string $word ): ?string => $base['--'. $groupPart. '-'. $knob. '--'. $word] ?? null, Setup::STEPS );

			if( ( $ladders[$knob][0] ?? null ) !== $knob || in_array( null, $values, true ) === true ) {
				$newLadderProblems[] = $label. ' is not a ladder of five';
				continue;
			}

			$numbers = array_map( $evaluate, $values );

			if( $ninoCss !== '' && abs( $numbers[2] - $kernelNumber( $name, false ) ) > 1e-9 )
				$newLadderProblems[] = $label. ': the middle is '. $values[2]. ', not the kernel\'s '. ( $ninoRootDecls[$name] ?? '' );

			for( $at = 1; $at < 5; $at++ )
				if( $numbers[$at] < $numbers[$at - 1] - 1e-9 || ( $knob !== 'rule' && $numbers[$at] < $numbers[$at - 1] + 1e-9 ) )
					$newLadderProblems[] = $label. ' does not rise at '. Setup::STEPS[$at];

			if( $numbers[4] <= $numbers[0] )
				$newLadderProblems[] = $label. ' ends where it starts';

			// ...at the ladder of the knob, or at an aspect of it that the set declares beside it and reads in its place
			if( preg_match( '/'. preg_quote( $name, '/' ). '\s*:\s*var\(--'. $groupPart. '-([a-z0-9-]+)\)\s*;/', $css, $pointed ) !== 1 || in_array( $pointed[1], $ladders[$knob], true ) === false )
				$newLadderProblems[] = $label. ': '. $name. ' is not pointed at the ladder';

			// Nino.css changes the number at 768px, and so does the ladder: the same steps of the number it has from there
			if( $ninoCss !== '' && abs( $kernelNumber( $name, true ) - $kernelNumber( $name, false ) ) > 1e-9 )
				foreach( Setup::STEPS as $at => $word ) {

					$large = $evaluate( (string) ( $wide['--'. $groupPart. '-'. $knob. '--'. $word] ?? '0' ) );

					if( $numbers[$at] <= 0 || abs( $large / $numbers[$at] - $kernelNumber( $name, true ) / $kernelNumber( $name, false ) ) > 1e-9 )
						$newLadderProblems[] = $label. ' is not the same step of the kernel\'s number from 768px at '. $word;
				}
		}

		// The rule is drawn by Nino.css as thick as --section-line says and by nothing else: a set that gives the title a border of its own would draw it twice, or hold the thickness to a floor the ladder does not know
		if( $groupPart === 'section' && preg_match( '/\.nino-section-title\b[^{}]*\{[^}]*border/', $css ) === 1 )
			$newLadderProblems[] = $groupPart. '/'. $set. ': draws a border on the section title itself';
	}

check( 'every section set and every card set declares the five knobs of the two as ladders of five it answers to and points the names Nino.css reads at - the text size of the title and of the subtitle of a section and of a card, the rule under a section title - the middle the number the kernel has, a scale rising, the rule never falling, the same steps of the kernel\'s number from 768px where it changes there, and no border of the title\'s own'
	. ( $newLadderProblems === [] ? '' : ' - '. implode( ' | ', array_slice( $newLadderProblems, 0, 4 ) ) ), $newLadderProblems === [] );

$defaultSheet 	= Compiler::compile( $appData, Setup::normalize( $appData, [], $library ), $library );
$defaultLines 	= designPlain( substr( $defaultSheet, (int) strrpos( $defaultSheet, 'the knob positions' ) ) );
$ladderCount		= 0;

foreach( Setup::PARTS as $part => $kind )
	$ladderCount += array_sum( array_map( 'count', Setup::ladders( $library, $part, $defaultSets[$part] ) ) );

$partsPattern = implode( '|', array_keys( Setup::PARTS ) );

// The ladders of the knobs the defaults leave off the middle, aspects included: they point at the step the defaults put them at
$offMiddleLadders = 0;

foreach( Setup::PARTS as $part => $kind )
	foreach( Setup::ladders( $library, $part, $defaultSets[$part] ) as $knob => $names )
		if( $themeKnobs[$part][$knob] !== 0 )
			$offMiddleLadders += count( $names );

check( 'at the defaults the sheet selects the step the defaults put every ladder at: a line for each ladder the default sets of the seven parts publish - the forty-six knobs and the aspects beside some of them - each pointing at its own step, the middle but for the knobs the shipped theme moves, the five of the section and the card among them',
	preg_match_all( '/^\t--((?:'. $partsPattern. ')-[a-z0-9-]+): var\(--\1--default\);$/m', $defaultLines ) === $ladderCount - $offMiddleLadders
	&& preg_match_all( '/^\t--((?:'. $partsPattern. ')-[a-z0-9-]+): var\(--\1--(?:least|less|default|more|most)\);$/m', $defaultLines ) === $ladderCount && $ladderCount >= 46
	&& count( array_filter( $groupKnobs, static function( array $names, string $groupPart ) use ( $defaultLines ): bool {
		return array_filter( array_keys( $names ), static fn( string $knob ): bool => str_contains( $defaultLines, "\t--". $groupPart. '-'. $knob. ': var(--'. $groupPart. '-'. $knob. "--default);\n" ) === false ) === [];
	}, ARRAY_FILTER_USE_BOTH ) ) === count( $groupKnobs ) );
echo "\nThe case of a thing: seven toggles, a switch each\n";

/*	The manual tells the switches once, in both languages: one entry names the four labels the seven switches are drawn with - the navigation of the header and of the footer share one - by the words
	the panel draws them with, says what a toggle is by its two rungs, and is the one entry that does. Found by those words and not by its handle	*/
$caseTold		= [];

foreach( [ 'en_US', 'de_DE' ] as $manualLocale )
	$caseTold[$manualLocale] = array_keys( array_filter( $feature['manual']['panel'], static function( mixed $entry ) use ( $toggles, $panelWord, $manualLocale ): bool {

		$text = \Nino\Features::localized( $entry, $manualLocale );

		return array_filter( $toggles, static fn( string $at ): bool => str_contains( $text, $panelWord( 'knob/'. $at. '/label', $manualLocale ) ) === false ) === []
			&& str_contains( $text, '--default' ) === true && str_contains( $text, '--more' ) === true;
	} ) );

check( 'the manual tells the seven switches in one entry of its own, in both languages: it names all seven by the label the panel draws them with, and the two rungs a template declares to answer one - the same entry in English and in German',
	count( $caseTold['en_US'] ) === 1 && $caseTold['en_US'] === $caseTold['de_DE'] && count( $toggles ) === 7 );

/*	A toggle is a ladder of two rungs, none and uppercase, under the words default and more, and the set that answers it reads the rung the knob selects where it styles the thing: the default set of the
	header, the footer, the section, the card and the buttons does, any other does not (a set of the library that sets capitals of its own is none of this). The selectors are what the case is of: the links of the
	navigation of the bar and of the footer, the title and the subtitle of a section and of the hero, the title and the subtitle of a card, the label of a button. Read from the files the way the panel and the
	compiler read them - the ladders with Setup::ladders() - and not from a list of the sets	*/
$toggleSelectors = [
	'header/nav-case' => [ '.nino-frame-header .nino-nav-content li a' ],
	'footer/nav-case' => [ '.nino-footer-nav .nino-nav-content li a' ],
	'section/title-case' => [ '.nino-section-title', '.nino-hero-title' ],
	'section/subtitle-case' => [ '.nino-section-subtitle', '.nino-hero-subtitle' ],
	'card/title-case' => [ '.nino-card-title' ],
	'card/subtitle-case' => [ '.nino-card-subtitle' ],
	'buttons/case' => [ '.nino-btn' ],
];
$toggleProblems	= [];
$toggleReads		= 0;

foreach( $toggles as $at ) {

	[ $togglePart, $toggleKnob ] = explode( '/', $at );

	$token = '--'. $togglePart. '-'. $toggleKnob;

	foreach( Setup::available( $library, $togglePart ) as $set ) {

		$answers = in_array( $toggleKnob, Setup::knobs( $library, $togglePart, $set ), true );

		if( $set !== $defaultSets[$togglePart] ) {

			if( $answers === true )
				$toggleProblems[] = $at. ': the set "'. $set. '" answers it, and it is the default set\'s alone';

			continue;
		}

		$css = designPlain( (string) file_get_contents( Setup::file( $library, $togglePart, $set ) ) );

		if( $answers === false || ( Setup::ladders( $library, $togglePart, $set )[$toggleKnob] ?? null ) !== [ $toggleKnob ] )
			$toggleProblems[] = $at. ': the default set "'. $set. '" does not publish it as a ladder of its own';

		foreach( [ 'default' => 'none', 'more' => 'uppercase' ] as $word => $value )
			if( preg_match( '/'. preg_quote( $token. '--'. $word, '/' ). '\s*:\s*'. $value. '\s*;/', $css ) !== 1 )
				$toggleProblems[] = $at. ': the rung '. $word. ' is not '. $value;

		// The rules that read the token: one rule for the things the case is of, and no other
		$reading = array_values( array_filter( designRules( $css ), static fn( array $rule ): bool
			=> preg_match( '/text-transform\s*:\s*var\(\s*'. preg_quote( $token, '/' ). '\s*\)/', $rule['body'] ) === 1 ) );

		$toggleReads += count( $reading );

		if( count( $reading ) !== 1 || array_map( 'trim', explode( ',', $reading[0]['selector'] ) ) !== $toggleSelectors[$at] || $reading[0]['within'] !== '' )
			$toggleProblems[] = $at. ': read by '. implode( ' | ', array_map( static fn( array $rule ): string => $rule['selector'], $reading ) ). ' instead of '. implode( ', ', $toggleSelectors[$at] );
	}
}

check( 'the seven toggles are answered by the default set of their part alone, as a ladder of two rungs of their own - none under default, uppercase under more - which one rule reads, on what the case is of: the links of the navigation of the bar and of the footer, a title and a subtitle of a section and of the hero, of a card, and the buttons'
	. ( $toggleProblems === [] ? '' : ' - '. implode( ' | ', array_slice( $toggleProblems, 0, 4 ) ) ), $toggleProblems === [] && $toggleReads === 7 && array_keys( $toggleSelectors ) === $toggles );

/*	The panel greys what a template does not answer to, and says so of a toggle as of any knob: the sets that leave the case out - the other header, footer, section, card and buttons sets - are listed with the
	other knobs and without these, so that the answer to design/list has the toggles in the catalogue of the default sets alone	*/
[ , $caseListing ] = callDesignAction( $appData, 'apiList' );
$caseAnswered = [];

foreach( (array) ( $caseListing['parts'] ?? [] ) as $row )
	foreach( $row['catalogue'] as $set => $entry )
		$caseAnswered[$row['part']. '/'. $set] = array_values( array_intersect( array_keys( array_filter( Setup::KNOBS[$row['part']], static fn( string $kind ): bool => $kind === 'toggle' ) ), $entry['knobs'] ) );

check( 'the answer of the list has the toggles in the knobs of the default set of each of the five parts that have them, and in no other set\'s - and says, by the kinds, that they are toggles',
	array_filter( $caseAnswered, static fn( array $answered, string $where ): bool
		=> $answered !== ( explode( '/', $where )[1] === $defaultSets[ explode( '/', $where )[0] ] ? array_keys( array_filter( Setup::KNOBS[ explode( '/', $where )[0] ], static fn( string $kind ): bool => $kind === 'toggle' ) ) : [] ), ARRAY_FILTER_USE_BOTH ) === []
	&& count( $caseAnswered ) === array_sum( array_map( static fn( array $row ): int => count( $row['catalogue'] ), (array) ( $caseListing['parts'] ?? [] ) ) )
	&& ( $caseListing['kinds']['buttons']['case'] ?? '' ) === 'toggle' && ( $caseListing['kinds']['header']['nav-case'] ?? '' ) === 'toggle' && ( $caseListing['kinds']['section']['title'] ?? '' ) === 'scale' );

// A toggle at 1 selects the rung more, and at 0 the rung default - in the line of the knob positions, in the head of the section of its set, and in the header of the sheet
$caseOn		= Setup::defaults( $library );

foreach( $toggles as $at )
	$caseOn['knobs'][ explode( '/', $at )[0] ][ explode( '/', $at )[1] ] = 1;

$caseSheet	= Compiler::compile( $appData, Setup::normalize( $appData, $caseOn, $library ), $library );
$caseLines	= designPlain( substr( $caseSheet, (int) strrpos( $caseSheet, 'the knob positions' ) ) );
$caseHeader	= substr( $caseSheet, 0, (int) strpos( $caseSheet, '*/' ) );
$caseOff		= designPlain( substr( $defaultSheet, (int) strrpos( $defaultSheet, 'the knob positions' ) ) );

check( 'a toggle at 0 selects its rung default and at 1 its rung more, one line each in the knob positions - the seven new lines of the forty-six',
	array_filter( $toggles, static fn( string $at ): bool
		=> str_contains( $caseOff, "\t--". str_replace( '/', '-', $at ). ': var(--'. str_replace( '/', '-', $at ). "--default);\n" ) === false
			|| str_contains( $caseLines, "\t--". str_replace( '/', '-', $at ). ': var(--'. str_replace( '/', '-', $at ). "--more);\n" ) === false
			|| str_contains( $caseLines, "\t--". str_replace( '/', '-', $at ). ': var(--'. str_replace( '/', '-', $at ). "--default);\n" ) === true ) === []
	&& preg_match_all( '/^\t--(?:header-nav-case|footer-nav-case|section-title-case|section-subtitle-case|card-title-case|card-subtitle-case|buttons-case): var\(--[a-z-]+--default\);$/m', $caseOff ) === 7
	&& preg_match_all( '/^\t--(?:header-nav-case|footer-nav-case|section-title-case|section-subtitle-case|card-title-case|card-subtitle-case|buttons-case): var\(--[a-z-]+--more\);$/m', $caseLines ) === 7 );

// ...and the section of its set says the step in its title, as it does of every knob, and the header of the sheet names the ones that are on and none that is off
check( '...the title of the section of its set says the step of the toggle, and the header of the sheet names the toggles that stand off the middle and only those',
	array_filter( $toggles, static fn( string $at ): bool
		=> preg_match( '/^\/\* ==== \d+\. '. preg_quote( explode( '/', $at )[0], '/' ). ': [a-z-]+ \([^)]*\b'. preg_quote( explode( '/', $at )[1], '/' ). ' more[,)][^\n]*==== \*\/$/m', $caseSheet ) !== 1
			|| preg_match( '/^\/\* ==== \d+\. '. preg_quote( explode( '/', $at )[0], '/' ). ': [a-z-]+ \([^)]*\b'. preg_quote( explode( '/', $at )[1], '/' ). ' default[,)][^\n]*==== \*\/$/m', $defaultSheet ) !== 1
			|| preg_match( '/^ \*   '. preg_quote( str_pad( explode( '/', $at )[0], 11 ), '/' ). '[a-z-]+  \([^)]*\b'. preg_quote( explode( '/', $at )[1], '/' ). ' more\b/m', $caseHeader ) !== 1 ) === []
	&& preg_match( '/-case (?:default|more)/', substr( $defaultSheet, 0, (int) strpos( $defaultSheet, '*/' ) ) ) !== 1
	&& preg_match( '/ \*   buttons +regular +\(case more\)/', $caseHeader ) === 1 );

// ...and the rung it selects is what the set's rule reads: the token the rule names is, through the selection line, the value uppercase at 1 and none at 0
$caseValues	= static function( string $lines, string $css, string $token ): string {

	if( preg_match( '/^\t'. preg_quote( $token, '/' ). ': var\((--[a-z-]+)\);$/m', $lines, $selected ) !== 1 )
		return '';

	return preg_match( '/(?<![\w-])'. preg_quote( $selected[1], '/' ). '\s*:\s*([a-z]+)\s*;/', $css, $value ) === 1 ? $value[1] : '';
};

check( '...so the value a rule of the set reads is uppercase for a toggle that is on and none for one that is off, for each of the seven',
	array_filter( $toggles, static fn( string $at ): bool
		=> $caseValues( $caseLines, designPlain( $caseSheet ), '--'. str_replace( '/', '-', $at ) ) !== 'uppercase' || $caseValues( $caseOff, designPlain( $defaultSheet ), '--'. str_replace( '/', '-', $at ) ) !== 'none' ) === [] );

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
$logoAsks			= [];
$logoRender		= [ 'filled' => [], 'empty' => [] ];
$logoUrl			= \Nino\Images::getUrl( $appData, 'logo.webp' );

foreach( [ 'header', 'footer' ] as $part )
	foreach( Setup::available( $library, $part ) as $set ) {

		$source = (string) file_get_contents( Setup::file( $library, $part, $set, 'template' ) );

		if( preg_match( '#logo(?:-invert)?\.png#', $source ) === 1 || str_contains( $source, 'images/logo' ) === true )
			$logoNamed[] = $part. '/'. $set;

		$logoAsks[$part. '/'. $set] = str_contains( $source, '[image /logo' );

		unset( $appData['/nino/html/images']['/logo'] );
		$logoRender['empty'][$part. '/'. $set] = \Nino\Html::renderHtml( $appData, $source );
		$appData['/nino/html/images']['/logo'] = [ 'label' => 'Logo', 'filename' => 'logo.webp', 'width' => 500, 'height' => 100 ];
		$logoRender['filled'][$part. '/'. $set] = \Nino\Html::renderHtml( $appData, $source );
	}

unset( $appData['/nino/html/images']['/logo'] );

check( 'no library template names logo.png or logo-invert.png'. ( $logoNamed === [] ? '' : ' - '. implode( ', ', $logoNamed ) ),
	$logoNamed === [] && $logoRender['empty'] !== [] && count( $logoRender['empty'] ) === count( Setup::available( $library, 'header' ) ) + count( Setup::available( $library, 'footer' ) ) );
check( 'every frame that asks the logo slot is given the logo that was uploaded: its url and its size',
	array_filter( $logoAsks ) !== [] && array_filter( array_keys( array_filter( $logoAsks ) ), static fn( string $frame ): bool
		=> str_contains( $logoRender['filled'][$frame], 'src="'. $logoUrl. '"' ) === false || str_contains( $logoRender['filled'][$frame], 'width="500" height="100"' ) === false ) === [] );
check( '...and with no logo uploaded none leaves an <img>, a slot tag or a fill of the shortcode behind',
	array_filter( $logoRender['empty'], static fn( string $html ): bool
		=> preg_match( '#<img[^>]*logo#i', $html ) === 1 || str_contains( $html, '[image' ) === true
			|| str_contains( $html, '[[src]]' ) === true || str_contains( $html, '[[width]]' ) === true || str_contains( $html, '[[alt]]' ) === true
			|| str_contains( $html, $logoUrl ) === true || str_contains( $html, '/images/logo' ) === true ) === [] );
check( 'one slot serves every variant: no stylesheet of the library filters the logo, a frame that was drawn for a dark ground says so with nino-logo--invert in its markup and applies no filter of its own', array_filter( glob( $library. '/*/*/style.css' ) ?: [], static fn( string $file ): bool
	=> preg_match( '#nino-logo--invert[^{]*\{[^}]*filter#', (string) file_get_contents( $file ) ) === 1 ) === [] );

$specimen = Preview::specimen( $appData );

check( 'the specimen brings no frame of its own - render() puts the chosen ones around it',
	str_contains( $specimen, 'frame-header' ) === false && str_contains( $specimen, 'frame-footer' ) === false );
/*	One page, and an anchor on it for each place the panel looks at: an element
	with an id, which the frame scrolls to when a tab is opened and after every change
	made in it - the top of the page, the type, the first section, the buttons, the
	tables, the lists, the colours. Which tab or object looks at which is the script's
	(ANCHORS and OBJECT_ANCHORS in admin.js, which design-js-smoke.js reads back and
	holds to the ids of this page)	*/
preg_match_all( '/\sid="(global|type|content|buttons|tables|lists|colours)"/', $specimen, $anchors );

$anchored = array_count_values( $anchors[1] );
ksort( $anchored );

check( 'it is one page with an anchor for each of those places: global, type, content, buttons, tables, lists and colours, each exactly once',
	$anchored === [ 'buttons' => 1, 'colours' => 1, 'content' => 1, 'global' => 1, 'lists' => 1, 'tables' => 1, 'type' => 1 ] );

/*	...and one for every place the script names, whatever table it names it in: the anchor of a tab (ANCHORS), of an object (OBJECT_ANCHORS) and of a control that
	stands elsewhere than its object (CONTROL_ANCHORS), read out of the script itself - a table that sent the frame to an id the page does not have would scroll nowhere, and say nothing	*/
$scriptAnchors = [];

foreach( [ 'ANCHORS', 'OBJECT_ANCHORS', 'CONTROL_ANCHORS' ] as $anchorTable )
	if( preg_match( '/\b'. $anchorTable. '\s*:\s*\{([^}]*)\}/', $panelScript, $tableFound ) === 1 ) {

		preg_match_all( "/:\s*'([a-z]+)'/", $tableFound[1], $tableTargets );

		$scriptAnchors = array_merge( $scriptAnchors, $tableTargets[1] );
	}

// ...and the one the list of the themes looks at, which is no table of its own: a theme is all of the page (THEMES_ANCHOR)
if( preg_match( "/\bTHEMES_ANCHOR\s*:\s*'([a-z]+)'/", $panelScript, $themesFound ) === 1 )
	$scriptAnchors[] = $themesFound[1];

$scriptAnchors	= array_values( array_unique( $scriptAnchors ) );
$anchorless			= array_values( array_filter( $scriptAnchors, static fn( string $id ): bool => substr_count( $specimen, ' id="'. $id. '"' ) !== 1 ) );

check( 'the specimen has an element for every anchor the script of the panel looks at, once each - the tabs\', the objects\' and the controls\''. ( $anchorless === [] ? '' : ' - none for '. implode( ', ', $anchorless ) ),
	$anchorless === [] && count( $scriptAnchors ) >= 8 && in_array( 'lists', $scriptAnchors, true ) === true && in_array( 'tables', $scriptAnchors, true ) === true && in_array( 'footer', $scriptAnchors, true ) === true );
/*	...and one more, which no tab has: where a change of a card is looked at (OBJECT_ANCHORS in
	admin.js, which design-js-smoke.js holds to the ids of this page)	*/
check( 'the Cards section carries id="cards", once, with an accessible name of its own - and it comes after the hero, which is #content, and before the grounds, which are #colours',
	substr_count( $specimen, ' id="cards"' ) === 1 && preg_match( '/<section id="cards" class="nino-section" aria-label="[^"]+">/', $specimen ) === 1
	&& (int) strpos( $specimen, 'id="content"' ) < (int) strpos( $specimen, 'id="cards"' ) && (int) strpos( $specimen, 'id="cards"' ) < (int) strpos( $specimen, 'id="colours"' ) );

/*	...and one at its end, as the first is at its beginning: where the footer starts, which the frame scrolls to when a footer is chosen on Templates (the
	footer of CONTROL_ANCHORS and _jump() in admin.js, which design-js-smoke.js holds to the ids of this page). An empty block after the last section, so that it
	takes no room, in a page whose body is a column of the header, the main part and the footer	*/
check( 'the page ends in an anchor of its own, id="footer", once: an empty block after the last section - it takes no room - and no tab has it',
	substr_count( $specimen, ' id="footer"' ) === 1 && preg_match( '#</section>\s*<div id="footer"></div>\s*$#', $specimen ) === 1
	&& (int) strpos( $specimen, ' id="footer"' ) > (int) strrpos( $specimen, '<section ' ) );

/*	...and one that no tab, no object and no control of the script looks at: the form, which a variant of a feature may name in the line of its head that says where it is looked at
	(see Setup::head()) - the section around the form and the fields a feature's look is seen on, after the buttons and before the tables	*/
check( 'the Form section carries id="form", once, with an accessible name of its own, around the form and its field - after the buttons and before the tables - and the script of the panel looks at it nowhere: a place of the specimen for a feature that brings markup, named by a variant and by no table',
	substr_count( $specimen, ' id="form"' ) === 1 && preg_match( '/<section id="form" class="nino-section" aria-label="[^"]+">(.*?)<\/section>/s', $specimen, $formSection ) === 1
	&& str_contains( $formSection[1], 'class="nino-form"' ) === true && str_contains( $formSection[1], 'class="nino-form-input"' ) === true
	&& (int) strpos( $specimen, 'id="buttons"' ) < (int) strpos( $specimen, 'id="form"' ) && (int) strpos( $specimen, 'id="form"' ) < (int) strpos( $specimen, 'id="tables"' )
	&& in_array( 'form', $scriptAnchors, true ) === false );

// The places of the specimen are the elements with an id that a block of it starts at; the manual tells a variant where it is looked at in one entry of each language, found by the line of the head it names, which names each of them
preg_match_all( '/<(?:div|section) id="([a-z]+)"/', $specimen, $placeIds );

$placeEntries = [];

foreach( [ 'en_US', 'de_DE' ] as $placeLocale )
	$placeEntries[$placeLocale] = array_values( array_filter( array_keys( (array) ( $feature['manual']['panel'] ?? [] ) ), static fn( string $entry ): bool => str_contains( $says( $entry, $placeLocale ), '@anchor' ) === true ) );

check( 'the manual tells where a variant is looked at in one entry, the same one in both languages, found by the line of the head that names it - and the entry names every place of the specimen by its id, the ids the page has, read from the template',
	count( $placeIds[1] ) >= 9 && in_array( 'form', $placeIds[1], true ) === true && count( $placeEntries['en_US'] ) === 1 && $placeEntries['en_US'] === $placeEntries['de_DE']
	&& array_filter( $placeIds[1], static fn( string $id ): bool => str_contains( $said( $placeEntries['en_US'][0], 'en_US' ), $id ) === false || str_contains( $said( $placeEntries['de_DE'][0], 'de_DE' ), $id ) === false ) === [] );

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
check( '...and the second of them is the one with the cards: #cards, the section the hero is followed by, which holds the cards',
	isset( $sections[1] ) === true && $sections[1]->getAttribute( 'id' ) === 'cards' && $sections[1]->getAttribute( 'aria-label' ) !== ''
	&& count( $byClass( $xpath, 'nino-card', $sections[1] ) ) > 10 && $sections[0]->getAttribute( 'id' ) === 'content' );
check( 'and every child of a row is a grid cell, so nothing sits in the row without a width',
	$strayCells === [] );

/*	A stack of cards is drawn the way Nino 1.6 draws one: a row of cells, each
	of them a div with the classes of the grid, and the card in it. The card is
	no longer the cell - it would keep the margins of a card that stands in a
	column on top of its slot, so the row overflows its column: at 1280px the
	third card wraps under the first two, and on a phone the cards stand
	outside the column (measured)	*/
$stacks = $byClass( $xpath, 'nino-stack' );
$bareCells = [];

foreach( $stacks as $stack )
	foreach( $stack->childNodes as $child )
		if( $child instanceof DOMElement && ( $child->tagName !== 'div' || count( $byClass( $xpath, 'nino-card', $child ) ) !== 1 ) )
			$bareCells[] = $child->tagName. '.'. $child->getAttribute( 'class' );

check( 'the stack of cards is drawn as Nino 1.6 draws one: every cell of a stack is a div that holds one card, and no card is a cell'. ( $bareCells === [] ? '' : ' - '. implode( ', ', $bareCells ) ),
	$stacks !== [] && $bareCells === [] );

/*	The first row of cards frames its pictures for the hover of the pictures, as the kernel frames the picture of a card for a class of its own: a div that carries the classes of that frame and nino-hover,
	round one picture. The two controls of the hover look at the cards (CONTROL_ANCHORS in admin.js), so the pictures they change are the ones the frame scrolls to; the cards after the first row keep the
	bare picture, and the specimen shows both	*/
$framedPictures = $byClass( $xpath, 'nino-hover', $sections[1] );
$firstCards = array_slice( $byClass( $xpath, 'nino-card', $sections[1] ), 0, 3 );
$framesWrong = array_filter( $framedPictures, static function( DOMNode $frame ): bool {

	if( $frame instanceof DOMElement === false )
		return true;

	$tokens = array_values( array_filter( explode( ' ', $frame->getAttribute( 'class' ) ) ) );
	$inside = $frame->getElementsByTagName( 'img' );

	sort( $tokens );

	return $frame->tagName !== 'div' || $tokens !== [ 'nino-card-img', 'nino-hover', 'nino-image' ] || $inside->length !== 1 || ( $inside->item( 0 )?->getAttribute( 'class' ) ?? 'x' ) !== '';
} );

check( 'the first row of cards frames its pictures for the hover of the pictures: a div with the classes of the frame the kernel gives the picture of a card and nino-hover, round one picture of no class of its own - in each of the first three cards, once, and in no other',
	count( $framedPictures ) === 3 && $framesWrong === [] && count( $firstCards ) === 3 && array_filter( $firstCards, static fn( DOMNode $card ): bool => count( $byClass( $xpath, 'nino-hover', $card ) ) !== 1 ) === [] );
check( '...and a card after them keeps the picture bare, an img with the class nino-card-img, so that the specimen shows a picture with the hover and one without',
	array_filter( $byClass( $xpath, 'nino-card-img', $sections[1] ), static fn( DOMNode $node ): bool => $node->nodeName === 'img' ) !== [] );

/*	The animation of the page is a choice of the Motion view, and a page that
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
	'nino-grid-100 nino-mt-3', a card 'nino-card ... nino-mb-3'. Without it
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
$chosen 	= Setup::normalize( $appData, [ 'parts' => [ 'header' => $richParts['header'], 'footer' => $richParts['footer'] ] ], $library );

/*	The preview renders its header, its specimen and its footer one by one, as a page renders the two frames it includes. The
	kernel's parser pairs an opening shortcode with the first closing tag of its name after it, however far away: a header
	carries its logo as [image /logo alt=""] on its own and a footer may carry it as a pair, [image /logo alt=""]...[/image],
	so as one string the header's tag takes the footer's closing tag for its own and the page between the two is its content -
	gone where no logo is uploaded, and a fresh install has none; where one is, the header's picture is drawn in the footer.
	The two frames are found by what their templates carry, whatever the library calls them: the first header of the one
	kind and the first footer of the other	*/

/** The sets of a part whose template asks for the logo slot and closes the tag ($closed) or leaves it open, as set => template */
$logoFrames = static function( string $part, bool $closed ) use ( $library ): array {

	$found = [];

	foreach( Setup::available( $library, $part ) as $set ) {

		$source = (string) file_get_contents( Setup::file( $library, $part, $set, 'template' ) );

		if( str_contains( $source, '[image /logo' ) === true && str_contains( $source, '[/image]' ) === $closed )
			$found[$set] = $source;
	}

	return $found;
};

/** Whether every chunk is in the page, each after the one before it - none of them empty */
$inOrder = static function( string $page, array $chunks ): bool {

	$from = 0;

	foreach( $chunks as $chunk ) {

		$at = $chunk === '' ? false : strpos( $page, $chunk, $from );

		if( $at === false )
			return false;

		$from = $at + strlen( $chunk );
	}

	return true;
};

$lonely 		= $logoFrames( 'header', false );
$paired 		= $logoFrames( 'footer', true );
$logoPair 		= [ 'header' => (string) array_key_first( $lonely ), 'footer' => (string) array_key_first( $paired ) ];
$shortfall 		= array_filter( [ $lonely === [] ? 'header whose logo shortcode stands alone' : '', $paired === [] ? 'footer whose logo shortcode is a pair' : '' ] );
$pairSetup 		= Setup::normalize( $appData, [ 'parts' => $logoPair ], $library, $notes );
$pairSources 	= [ $lonely[$logoPair['header']] ?? '', $paired[$logoPair['footer']] ?? '' ];

unset( $appData['/nino/html/images']['/logo'] );

$pairHeader 	= trim( \Nino\Html::renderHtml( $appData, $pairSources[0] ) );
$pairBody 		= trim( \Nino\Html::renderHtml( $appData, Preview::specimen( $appData ) ) );
$pairFooter 	= trim( \Nino\Html::renderHtml( $appData, $pairSources[1] ) );
$pageApart 		= trim( Preview::render( $appData, $library, $pairSetup, $notes ) );
$pairOpening 	= [ explode( "\n", $pairHeader )[0], explode( "\n", $pairFooter )[0] ];
$oneString 		= trim( \Nino\Html::renderHtml( $appData, $pairSources[0]. "\n". Preview::specimen( $appData ). "\n". $pairSources[1] ) );

check( 'render() reads the chosen frames out of the library and renders each on its own: with no logo uploaded, a header whose logo shortcode stands alone and a footer whose logo shortcode is a pair - found by what their templates carry - come out as the header, the whole specimen and the footer, in that order, with no tag of the logo left'
	. ( $shortfall === [] ? '' : ' - the library has no '. implode( ' and no ', $shortfall ) ),
	$shortfall === [] && $notes === [] && $pairBody !== ''
	&& str_starts_with( $pageApart, $pairHeader ) === true && str_ends_with( $pageApart, $pairFooter ) === true
	&& $inOrder( $pageApart, [ $pairHeader, $pairBody, $pairFooter ] ) === true
	&& str_contains( $pageApart, '[image' ) === false && str_contains( $pageApart, '[/image]' ) === false );
check( '...the anchor of the footer is the end of the specimen: in the page the preview renders it stands once, after the last section of the specimen and before the footer\'s own markup - the frames bring none of their own - so that the frame can scroll to where the footer starts',
	$shortfall === [] && substr_count( $pageApart, ' id="footer"' ) === 1 && str_contains( $pairBody, ' id="footer"' ) === true && $inOrder( $pageApart, [ ' id="colours"', ' id="footer"', $pairOpening[1] ] ) === true );
check( '...and the same three rendered as one string lose the specimen - the parser gives the header\'s tag the footer\'s closing tag, however far away - which is why render() does not',
	$shortfall === [] && $pairBody !== '' && str_contains( $oneString, $pairBody ) === false );

[ $status, $shownPair ] = callDesignAction( $appData, 'apiPreview', [ 'parts' => $logoPair, 'full' => true ] );
$pairDocument = (string) ( $shownPair['document'] ?? '' );

check( '...and the preview the panel is sent is that page: with the same two frames chosen and no logo uploaded, the document has the header, the specimen and the footer, in that order',
	$shortfall === [] && $status === 200 && ( $shownPair['notes'] ?? null ) === []
	&& $inOrder( $pairDocument, [ $pairOpening[0], $pairBody, $pairOpening[1] ] ) === true );

// The slot of the logo, as the Images panel leaves it once a logo is uploaded - the specimen asks for none, so the one rendered above is the middle of this page as well
$appData['/nino/html/images']['/logo'] = [ 'label' => 'Logo', 'filename' => 'logo.webp', 'width' => 500, 'height' => 100 ];

$pairHeader 	= trim( \Nino\Html::renderHtml( $appData, $pairSources[0] ) );
$pairFooter 	= trim( \Nino\Html::renderHtml( $appData, $pairSources[1] ) );
$pageLogo 		= trim( Preview::render( $appData, $library, $pairSetup, $notes ) );

unset( $appData['/nino/html/images']['/logo'] );

check( '...and with a logo uploaded they come out as the whole page too, each frame with the picture its own logo shortcode asks for',
	$shortfall === [] && $notes === []
	&& str_starts_with( $pageLogo, $pairHeader ) === true && str_ends_with( $pageLogo, $pairFooter ) === true
	&& $inOrder( $pageLogo, [ $pairHeader, $pairBody, $pairFooter ] ) === true
	&& substr_count( $pageLogo, 'src="'. $logoUrl. '"' ) === substr_count( $pairSources[0]. $pairSources[1], '[image /logo' ) );

$notes = [];
check( 'a frame with no template is named rather than quietly left out',
	Preview::frame( $library, [ 'parts' => [ 'header' => 'nope' ] ], 'header', $notes ) === ''
	&& count( $notes ) === 1 && str_contains( $notes[0], '"header"' ) === true );

$notes = [];
$shown = Preview::css( $appData, $chosen, $library, '/somewhere/public', $notes );
check( 'the sheet a preview is shown under resolves the public prefix, or the webfaces never load',
	str_contains( $shown, "url('/somewhere/public/fonts/" ) === true && str_contains( $shown, '[[/nino/public]]' ) === false );

$document = Preview::document( $appData, 'de_DE', '<style>a{}</style>', '<main>b</main>', '<script></script>' );
check( 'the document says which language it is in and says no to crawlers',
	str_starts_with( $document, '<!doctype html>' ) === true
	&& str_contains( $document, '<html lang="de">' ) === true
	&& str_contains( $document, 'name="robots" content="noindex, nofollow"' ) === true );

// What the panel answers with. The setup on disk is size 0 here (the fallback
// save above wrote it), and the post below asks for -1 - so the preview showing
// -1 is the whole point: it is the selection on screen, not the one stored
$stored 	= Setup::read( $appData, $library );
$sheet 		= (string) \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' );

[ $status, $preview ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [ 'header' => $richParts['header'] ], 'size' => -1, 'full' => true,
] );

check( 'the preview answers with a whole document and the id of the sheet inside it', $status === 200
	&& str_starts_with( (string) ( $preview['document'] ?? '' ), '<!doctype html>' ) === true
	&& ( $preview['style'] ?? '' ) === Admin::PREVIEW_STYLE
	&& str_contains( (string) $preview['document'], 'id="'. Admin::PREVIEW_STYLE. '"' ) === true );
check( 'it shows what was posted and not what is stored - looking is what you do while deciding',
	str_contains( (string) ( $preview['css'] ?? '' ), '--nino-base-size: 92.5%;' ) === true && str_contains( (string) ( $preview['css'] ?? '' ), '--nino-base-size: 104.0625%;' ) === true
	&& ( $stored['size'] ?? '' ) === 0
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

// The preview is the site as a visitor sees it: drawn in the language a visitor of this browser is served - the one the site's own switch picked, else the native one -
// and not in the language the workbench speaks, which the request keeps. The name of the company, a word of its own in each language, is what the frames say
$nameKey = '[[/project/company/general/name]]';
$salt = bin2hex( random_bytes( 4 ) );
$visitorWords = [ 'de_DE' => 'zq'. $salt. 'de', 'en_US' => 'zq'. $salt. 'en' ];
$nameBefore = [];

foreach( $visitorWords as $locale => $word ) {

	$nameBefore[$locale] = ( (array) \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] ) )[$nameKey] ?? null;

	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ) use ( $nameKey, $word ): array {
		$texts = (array) $texts;
		$texts[$nameKey] = $word;
		return $texts;
	} );
}

$previewIn = static function( string $interface, ?string $visitor ) use ( &$appData ): array {

	\Nino\Locales::useLocale( $appData, $interface );

	if( $visitor === null )
		\Nino\Runtime::unsetSessionValue( $appData, './nino/locales/current' );
	else
		\Nino\Runtime::setSessionValue( $appData, './nino/locales/current', $visitor );

	[ $status, $answer ] = callDesignAction( $appData, 'apiPreview', [ 'parts' => [], 'full' => true ] );
	$stayed = \Nino\Locales::getCurrentLocale( $appData ) === $interface;

	\Nino\Locales::useLocale( $appData, 'de_DE' );

	return [ $status === 200 ? (string) ( $answer['document'] ?? '' ) : '', $stayed ];
};

[ $drawn, $stayed ] = $previewIn( 'en_US', 'de_DE' );
check( 'the preview draws the site in the language the visitor is served and not the workbench\'s: the workbench in English, the visitor served German - the frames say the German name of the company and not the English one, the document says it is German, and the request is still in English afterwards',
	str_contains( $drawn, $visitorWords['de_DE'] ) === true && str_contains( $drawn, $visitorWords['en_US'] ) === false && str_contains( $drawn, '<html lang="de">' ) === true && $stayed === true );
[ $drawn, $stayed ] = $previewIn( 'de_DE', 'en_US' );
check( '...and the other way round: the workbench in German, the visitor served English - the frames and the document are English, the request is German afterwards',
	str_contains( $drawn, $visitorWords['en_US'] ) === true && str_contains( $drawn, $visitorWords['de_DE'] ) === false && str_contains( $drawn, '<html lang="en">' ) === true && $stayed === true );
[ $drawn ] = $previewIn( 'en_US', null );
[ $unknown ] = $previewIn( 'en_US', 'xx_XX' );
check( '...a visitor who picked no language is served the native one, whatever the workbench speaks - and so is one whose pick the site does not have',
	str_contains( $drawn, $visitorWords['de_DE'] ) === true && str_contains( $drawn, $visitorWords['en_US'] ) === false && str_contains( $unknown, $visitorWords['de_DE'] ) === true && str_contains( $unknown, $visitorWords['en_US'] ) === false );

\Nino\Locales::useLocale( $appData, 'en_US' );
\Nino\Runtime::setSessionValue( $appData, './nino/locales/current', 'de_DE' );
[ , $listedMixed ] = callDesignAction( $appData, 'apiList' );
\Nino\Locales::useLocale( $appData, 'de_DE' );
\Nino\Runtime::unsetSessionValue( $appData, './nino/locales/current' );
check( '...what is for the person at the workbench stays in the language of the workbench: the names and the descriptions of the sets are English where the workbench speaks English, whatever language the visitor is served', $listedWords( $listedMixed, 'en_US' ) === [] );

foreach( $nameBefore as $locale => $was )
	\Nino\Filesystem::mutate( $appData, '/text/'. $locale. '.php', static function( mixed $texts ) use ( $nameKey, $was ): array {
		$texts = (array) $texts;

		if( $was === null )
			unset( $texts[$nameKey] );
		else
			$texts[$nameKey] = $was;

		return $texts;
	} );

[ $status, $partial ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 1, 'full' => false,
] );
check( 'a change that is only a stylesheet sends only that - the frame on screen keeps its page', $status === 200
	&& ( $partial['document'] ?? null ) === '' && str_contains( (string) ( $partial['css'] ?? '' ), '--nino-base-size: 115%;' ) === true && str_contains( (string) ( $partial['css'] ?? '' ), '--nino-base-size: 129.375%;' ) === true );

/*	Both of these move with every colour knob and are drawn beside the controls
	that moved them, so they ride the preview rather than a request of their own	*/
[ $status, $wheeled ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 0, 'full' => false,
	'colours' => [ 'primary' => '#8b1d3f', 'harmony' => 4 ],
] );
check( 'a preview also answers the derived second colour and what the brand measures - the swatch follows a knob without a request of its own',
	$status === 200 && ( $wheeled['accent'] ?? '' ) === Colours::accent( [ 'primary' => '#8b1d3f', 'harmony' => 4 ] )
	&& ( $wheeled['accent'] ?? '' ) !== '#8b1d3f'
	&& isset( $wheeled['brand']['light']['ratio'] ) === true );

check( 'previewing writes neither the setup nor the stylesheet',
	Setup::read( $appData, $library ) === $stored
	&& \Nino\Filesystem::getFileContent( $appData, Compiler::TARGET, '' ) === $sheet );

// Every key of the model travels the way it does in a save, and is shown without being written: the sets stand off the defaults, and a knob of each of two of them - the first one it answers to that is a scale, which has the two ends - at an end
$endKnob = static fn( string $part ): string => array_values( array_filter( Setup::knobs( $library, $part, $richParts[$part] ), static fn( string $knob ): bool => Setup::kind( $part, $knob ) === 'scale' ) )[0] ?? '';
$endKnobs = [ 'section' => $endKnob( 'section' ), 'buttons' => $endKnob( 'buttons' ), 'header' => $endKnob( 'header' ) ];

[ $status, $everything ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [ 'section' => $richParts['section'], 'buttons' => $richParts['buttons'], 'header' => $richParts['header'], 'tables' => 'nope' ],
	'knobs' => [ 'section' => [ $endKnobs['section'] => 2 ], 'buttons' => [ $endKnobs['buttons'] => -2 ], 'header' => [ $endKnobs['header'] => 2 ] ],
	'size' => 0, 'width' => -2,
	'motion' => [ 'effect' => 'blur', 'strength' => 'hard', 'speed' => 'fast' ],
	'fonts' => [ 'text' => [ 'family' => 'system-serif', 'size' => '1.125' ] ],
	'colours' => [ 'mode' => 'dark' ], 'full' => false,
] );
$shownCss = (string) ( $everything['css'] ?? '' );

check( 'a preview carries every key of the model the way a save does: the templates, the knobs - a frame\'s among them -, the width, the animation, the fonts and the mode', $status === 200
	&& $endKnobs['section'] !== '' && $endKnobs['buttons'] !== '' && $endKnobs['header'] !== ''
	&& str_contains( $shownCss, 'section: '. $richParts['section'] ) === true && str_contains( $shownCss, 'buttons: '. $richParts['buttons'] ) === true && str_contains( $shownCss, 'header: '. $richParts['header'] ) === true
	&& str_contains( $shownCss, '--section-'. $endKnobs['section']. ': var(--section-'. $endKnobs['section']. '--most);' ) === true
	&& str_contains( $shownCss, '--buttons-'. $endKnobs['buttons']. ': var(--buttons-'. $endKnobs['buttons']. '--least);' ) === true
	&& str_contains( $shownCss, '--header-'. $endKnobs['header']. ': var(--header-'. $endKnobs['header']. '--most);' ) === true
	&& str_contains( $shownCss, '--grid-max-width: '. Setup::WIDTHS[-2][0]. ';' ) === true && str_contains( $shownCss, '--vpa-f-hidden: '. Setup::EFFECTS['blur']['hard']['--vpa-f-hidden']. ';' ) === true
	&& str_contains( $shownCss, '--vpa-duration: '. Setup::SPEEDS['fast']. ';' ) === true
	&& str_contains( $shownCss, '--fontfamily-text: ui-serif' ) === true && str_contains( $shownCss, '--font-text-size: 1.125rem;' ) === true
	&& str_contains( $shownCss, 'color-scheme: dark;' ) === true );
check( '...a template the library does not have is named in the answer and the first one is shown, and the stored setup and the stylesheet are still what they were',
	count( (array) ( $everything['notes'] ?? [] ) ) === 1 && ( $everything['notes'][0]['code'] ?? '' ) === 'design_set_missing' && ( $everything['notes'][0]['params'][1] ?? '' ) === 'nope' && str_contains( $shownCss, 'tables: '. $defaultSets['tables'] ) === true
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
check( '...each knob with its kind and its own positions - the names of four alternatives for a choice, the number five for a scale that starts on the third - and the mode, whose positions are the words the setup keeps',
	count( (array) ( $listed['palette']['harmony']['steps'] ?? [] ) ) === 4 && ( $listed['palette']['harmony']['kind'] ?? '' ) === 'choice'
	&& ( $listed['palette']['contrast'] ?? null ) === [ 'kind' => 'scale', 'default' => 3, 'steps' => 5 ]
	&& ( $listed['palette']['mode'] ?? null ) === [ 'kind' => 'choice', 'default' => 'auto', 'steps' => [ 'auto', 'light', 'dark' ] ]
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

		// A scale's positions are numbers and have no word, a choice has one for each of its alternatives and the mode's are its words - and the mode, a select under a heading, has no note
		$wanted = $knobKey === 'mode' ? [ 'label' ] : [ 'label', 'note' ];
		foreach( ( $knobMeta['kind'] ?? '' ) === 'choice' ? (array) ( $knobMeta['steps'] ?? [] ) : [] as $position => $step )
			$wanted[] = $knobKey === 'mode' ? (string) $step : (string) ( $position + 1 );

		foreach( $wanted as $suffix )
			if( ( $knobText[ '[[/_admin/design/colour/'. $knobKey. '/'. $suffix. ']]' ] ?? '' ) === '' )
				$knobWords[] = $knobLocale. ' '. $knobKey. '/'. $suffix;
	}
}
check( 'every knob the panel is handed has its name, its note - the mode has none - and, for a choice, a word per position, in both locales'. ( $knobWords === [] ? '' : ' - missing: '. implode( ', ', $knobWords ) ), $knobWords === [] );
check( '...and the table itself carries no second copy of those words', ( $listed['palette']['harmony']['label'] ?? null ) === null
	&& ( $listed['palette']['harmony']['note'] ?? null ) === null
	&& ( $listed['palette']['harmony']['hint'] ?? null ) === null );

[ $status, ] = callDesignAction( $appData, 'apiSave', [
	'parts' => [], 'knobs' => [], 'size' => 0,
	'colours' => [ 'primary' => '#8b1d3f', 'secondary' => '#0f766e', 'temperature' => 1, 'mode' => 'light' ],
] );
$saved = Setup::read( $appData, $library );

check( 'a save keeps the palette, normalized', $status === 200
	&& ( $saved['colours']['primary'] ?? '' ) === '#8b1d3f'
	&& ( $saved['colours']['secondary'] ?? '' ) === '#0f766e'
	&& ( $saved['colours']['temperature'] ?? 0 ) === 1
	&& ( $saved['colours']['contrast'] ?? 0 ) === 3
	&& ( $saved['colours']['mode'] ?? '' ) === 'light' );
check( '...and the compiled sheet carries that colour, exactly as it was typed',
	str_contains( Compiler::compile( $appData, $saved, $library ), '--nino-brand: #8b1d3f;' ) === true );

[ $status, $coloured ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 0, 'colours' => [ 'primary' => '#2e7d32' ], 'full' => false,
] );
check( 'a preview shows the colour that was posted rather than the one on disk', $status === 200
	&& str_contains( (string) ( $coloured['css'] ?? '' ), '--nino-brand: #2e7d32;' ) === true
	&& ( Setup::read( $appData, $library )['colours']['primary'] ?? '' ) === '#8b1d3f' );

echo "\nThe panel: the typefaces - the list, an install, a removal\n";

/*	The panel's own words are what a request has loaded when it gets here (see \Nino\Admin\Admin::init()), and the
	sentence an install or a removal answers with is made of them. The setup file is put back as it was at the end:
	the roles below are given a family to see it refused for removal	*/
$fillsBefore 	= $appData['./nino/html/fills'] ?? null;
$setupFile 		= \Nino\Filesystem::path( $appData, Setup::PATH );
$setupKept 		= is_file( $setupFile ) === true ? (string) file_get_contents( $setupFile ) : null;

\Nino\Html::addFills( $appData, (array) include __DIR__. '/../text/en_US.php', '*' );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => $webPublic ] );
$publish( [ $entryInter, $entryFlex, $entryFira ] );
@unlink( $cacheFile );

$shelfKeys = [ 'families', 'fetched', 'url', 'key', 'builtin', 'installed', 'broken', 'uses', 'refusal', 'refusalKind' ];
$requests = [];

[ $status, $shelf ] = callDesignAction( $appData, 'apiWebfontsList', [] );

check( 'the list of typefaces is asked for with nothing kept: what the screen draws both halves from - the families on offer and when they were loaded, the address, whether there is a key, the families the feature brings, what is installed, what lies in the fonts directory that is none, who uses what, and why the list is missing and what that is about',
	$status === 200 && array_keys( $shelf ) === $shelfKeys && $shelf['families'] === [] && $shelf['fetched'] === 0 && $shelf['url'] === Webfonts::DEFAULT_URL && $shelf['key'] === true
	&& $shelf['builtin'] === array_keys( Fonts::builtin() ) && $shelf['installed'] === [] && $shelf['broken'] === [] && $shelf['uses'] === [] && $shelf['refusal'] === '' && $shelf['refusalKind'] === '' );
check( '...and nothing was fetched for it: the panel reads what it kept, and the repository is asked when the button says so', $requests === [] );

[ $status, $shelf ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => 'yes' ] );

check( '...a refresh that is not the boolean true is no refresh', $status === 200 && $shelf['families'] === [] && $requests === [] );

[ $status, $shelf ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

check( 'with refresh it asks the repository - the list and its signature, two requests - and answers what the list names, when it was loaded, and no refusal',
	$status === 200 && $shelf['families'] === [ $entryInter, $entryFlex, $entryFira ] && $shelf['refusal'] === '' && abs( $shelf['fetched'] - time() ) < 30
	&& array_column( $requests, 'url' ) === [ Webfonts::DEFAULT_URL, Webfonts::DEFAULT_URL. '.sig' ] );

$requests = [];
[ $status, $again ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => false ] );

check( '...and the next time it answers from what it kept, the same, without a request', $status === 200 && $again === $shelf && $requests === [] );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsKey' => '' ] );

if( Webfonts::PUBLIC_KEY === '' ) {

	$requests = [];
	[ $status, $refused ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

	check( 'a list that cannot be believed is an answer and not an error: the reason where the list was, and what it is about, none of the list, nothing fetched - with no key the sentence that says where to put one',
		$status === 200 && array_keys( $refused ) === $shelfKeys && $refused['families'] === [] && $refused['fetched'] === 0 && $requests === []
		&& $refused['refusal'] === 'no public key for the repository - set it in the feature\'s settings or in Webfonts::PUBLIC_KEY' && $refused['refusalKind'] === 'nokey' );
}
else
	echo "  --  the class carries a public key, so the refusal for a missing key is not tried here\n";

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsKey' => $webPublic, 'webfontsUrl' => '' ] );

check( '...and a repository that is switched off says that, in the refusal and in the address that is none - the screen is told what the settings are now, and not only when the panel was opened',
	callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] )[1]['refusal'] === 'the typeface repository is switched off'
	&& callDesignAction( $appData, 'apiWebfontsList', [] )[1]['url'] === '' );
check( '...the refusal of a repository that is switched off is about that, and a read that refuses nothing is about nothing',
	callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] )[1]['refusalKind'] === 'off' && callDesignAction( $appData, 'apiWebfontsList', [] )[1]['refusalKind'] === '' );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => $webPublic ] );

$served = $remote;
unset( $remote[Webfonts::DEFAULT_URL] );
[ , $unreachable ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

$remote = $served;
$remote[Webfonts::DEFAULT_URL] = str_replace( '"version": "4.1"', '"version": "9.9"', $remote[Webfonts::DEFAULT_URL] );
[ , $forged ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

$remote = $served;
$remote[Webfonts::DEFAULT_URL] = designList( [ array_replace( $entryInter, [ 'license' => 'MIT' ] ) ] );
$remote[Webfonts::DEFAULT_URL. '.sig'] = designSign( $remote[Webfonts::DEFAULT_URL], $webPrivate );
[ , $unreadable ] = callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

$remote = $served;

check( 'the other things a refusal can be about are told as well, each with its reason: a repository that cannot be reached, a list that was changed after it was signed, a signed list that is no list this reads',
	$unreachable['refusalKind'] === 'unreachable' && $unreachable['refusal'] === 'the typeface list could not be fetched: http 404'
	&& $forged['refusalKind'] === 'signature' && $forged['refusal'] === 'the typeface list signature does not verify'
	&& $unreadable['refusalKind'] === 'parse' && str_contains( $unreadable['refusal'], 'typeface list entry 0: "license"' ) === true
	&& array_keys( $unreachable ) === $shelfKeys && array_keys( $forged ) === $shelfKeys && array_keys( $unreadable ) === $shelfKeys
	&& in_array( $unreachable['refusalKind'], Webfonts::REFUSALS, true ) === true && in_array( $forged['refusalKind'], Webfonts::REFUSALS, true ) === true && in_array( $unreadable['refusalKind'], Webfonts::REFUSALS, true ) === true );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => '' ] );
check( '...and so is a key that is missing: the list answers that there is none',
	callDesignAction( $appData, 'apiWebfontsList', [] )[1]['key'] === ( Webfonts::PUBLIC_KEY !== '' ) );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => '', 'webfontsKey' => $webPublic ] );

[ , $offList ] = callDesignAction( $appData, 'apiList' );

check( 'design/list says whether the second half of the tab can be offered: an address, a key - one without the other is none',
	( $offList['webfonts'] ?? null ) === [ 'url' => false, 'key' => true ] );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => '' ] );
[ , $keyless ] = callDesignAction( $appData, 'apiList' );
\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsKey' => $webPublic ] );
[ , $both ] = callDesignAction( $appData, 'apiList' );

check( '...the key being the class\'s own where the setting is empty',
	( $keyless['webfonts'] ?? null ) === [ 'url' => true, 'key' => Webfonts::PUBLIC_KEY !== '' ] && ( $both['webfonts'] ?? null ) === [ 'url' => true, 'key' => true ] );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => $webPublic ] );
callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

// --- An install

[ $status, $done ] = callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'inter' ] );
$face = $done['installed']['inter']['face'] ?? '';

check( 'an install answers with the sentence for the bar and the state after it - the same shape the list has, the sentence in front, the families the feature brings among them',
	$status === 200 && array_keys( $done ) === array_merge( [ 'msg' ], $shelfKeys ) && $done['msg'] === $panelText( 'typefaces/msg/installed', 'en_US', 'Inter', '4.1' ) && $done['refusal'] === '' && $done['refusalKind'] === ''
	&& $done['builtin'] === array_keys( Fonts::builtin() ) && $done['families'] === [ $entryInter, $entryFlex, $entryFira ] && $done['fetched'] === ( Webfonts::cached( $appData )['fetched'] ?? 0 ) );
check( '...the family is installed whole, with what the screen draws of it: the name, its category, version, licence and where the files came from, the weights, and the file its name is set in - a url of the project\'s own, the first normal file',
	is_dir( $fontsDir. '/inter' ) === true && is_file( $fontsDir. '/inter/700-italic.woff2' ) === true
	&& ( $done['installed']['inter'] ?? null ) === [
		'name' => 'Inter', 'category' => 'sans', 'version' => '4.1', 'license' => 'OFL-1.1', 'source' => 'https://github.com/rsms/inter',
		'weights' => [ 400, 700 ], 'axis' => [], 'face' => $face,
	] && str_ends_with( $face, '/assets/fonts/inter/400.woff2' ) === true && str_contains( $face, '[[' ) === false && str_starts_with( $face, 'http' ) === false );
check( '...and nobody uses it yet', $done['uses'] === [ 'inter' => [] ] && $done['broken'] === [] );

[ , $relisted ] = callDesignAction( $appData, 'apiList' );

check( 'design/list has it after an install: among the families the Type view chooses from, by its name, and in the options of the roles - the code is offered the monospace ones only',
	( $relisted['families']['inter'] ?? null ) === [ 'name' => 'Inter', 'category' => 'sans', 'system' => false ] && in_array( 'inter', $relisted['options']['title'], true ) === true
	&& in_array( 'inter', $relisted['options']['code'], true ) === false );

[ $status, $flexDone ] = callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'roboto-flex' ] );

check( 'a family with a weight axis is told as one: the range, and the file with the axis as the file its name is set in',
	$status === 200 && $flexDone['msg'] === $panelText( 'typefaces/msg/installed', 'en_US', 'Roboto Flex', '3.0' ) && ( $flexDone['installed']['roboto-flex']['axis'] ?? null ) === [ 100, 900 ]
	&& str_ends_with( (string) ( $flexDone['installed']['roboto-flex']['face'] ?? '' ), '/assets/fonts/roboto-flex/100-900.woff2' ) === true
	&& array_keys( $flexDone['installed'] ) === [ 'inter', 'roboto-flex' ] );

$next 		= designManifest( 'inter', 'Inter', [ [ 400, 'normal' ], [ 600, 'normal' ], [ 700, 'normal' ] ], [ 'version' => '4.2', 'source' => 'https://github.com/rsms/inter' ] );
$archive 	= designTarGz( designArchiveFiles( $next ) );
$entryNext = designListEntry( $next, $archive );

$remote[$entryNext['archive']] = $archive;
$publish( [ $entryNext, $entryFlex, $entryFira ] );
callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );
[ $status, $updated ] = callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'inter' ] );

check( 'an update says so: the family that was there is the version of the list now, with the weights it has, and the sentence is the one for an update',
	$status === 200 && $updated['msg'] === $panelText( 'typefaces/msg/updated', 'en_US', 'Inter', '4.2' ) && ( $updated['installed']['inter']['version'] ?? null ) === '4.2' && ( $updated['installed']['inter']['weights'] ?? null ) === [ 400, 600, 700 ]
	&& is_file( $fontsDir. '/inter/600.woff2' ) === true && is_file( $fontsDir. '/inter/700-italic.woff2' ) === false );

// ...refused, with the reason, and a conflict rather than a failure of the server: the request was understood and the answer is no
$wrongSum = designListEntry( $firaCode, $archiveFira, [ 'sha256' => str_repeat( 'b', 64 ) ] );

$publish( [ $entryNext, $entryFlex, $wrongSum ] );
callDesignAction( $appData, 'apiWebfontsList', [ 'refresh' => true ] );

check( 'an archive the list does not vouch for is refused as a conflict, with the reason, and nothing is installed or left behind',
	callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'fira-code' ] ) === [ 409, [ 'error' => 'the archive does not match what the list promised' ] ]
	&& is_dir( $fontsDir. '/fira-code' ) === false && designStagingClean( $staging ) === true );
check( '...so is a family that is not in the list, and so is a slug that is none - which never reaches a path, a message or a line of the log as posted',
	callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'nowhere' ] ) === [ 409, [ 'error' => 'the typeface "nowhere" is not in the list - load the list first' ] ]
	&& callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => '../inter' ] ) === [ 409, [ 'error' => 'that is no slug of a typeface' ] ]
	&& callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => [ 'inter' ] ] ) === [ 409, [ 'error' => 'that is no slug of a typeface' ] ]
	&& callDesignAction( $appData, 'apiWebfontsInstall', [] ) === [ 409, [ 'error' => 'that is no slug of a typeface' ] ]
	&& callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'open-sans' ] )[0] === 409 );

// --- A removal

$usedBy( [ 'title', 'text' ] );
[ , $used ] = callDesignAction( $appData, 'apiWebfontsList', [] );

check( 'the list says who uses a family: the roles of the saved setup, in the order of the roles', $used['uses'] === [ 'inter' => [ 'title', 'text' ], 'roboto-flex' => [] ] );
check( 'a removal of a family a role uses is refused as a conflict, and says which roles, and the family stays',
	callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => 'inter' ] ) === [ 409, [ 'error' => 'used by title, text', 'code' => 'used', 'params' => [ 'title', 'text' ] ] ] && is_file( $fontsDir. '/inter/400.woff2' ) === true );

$usedBy( [] );
[ $status, $removed ] = callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => 'inter' ] );

check( 'one that nothing uses is removed: the sentence for the bar and the state after it - the family is gone from the installed ones, with its directory, and from what the Type view chooses from',
	$status === 200 && array_keys( $removed ) === array_merge( [ 'msg' ], $shelfKeys ) && $removed['msg'] === $panelText( 'typefaces/msg/removed', 'en_US', 'Inter' ) && isset( $removed['installed']['inter'] ) === false
	&& isset( $removed['installed']['roboto-flex'] ) === true && is_dir( $fontsDir. '/inter' ) === false
	&& isset( callDesignAction( $appData, 'apiList' )[1]['families']['inter'] ) === false );
check( '...a second time it is a conflict - there is nothing to remove - and so is a slug that is none',
	callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => 'inter' ] ) === [ 409, [ 'error' => 'the typeface "inter" is not installed' ] ]
	&& callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => '../' ] ) === [ 409, [ 'error' => 'that is no slug of a typeface' ] ]
	&& callDesignAction( $appData, 'apiWebfontsRemove', [] ) === [ 409, [ 'error' => 'that is no slug of a typeface' ] ] );

mkdir( $fontsDir. '/odd-one', 0755, true );
[ , $odd ] = callDesignAction( $appData, 'apiWebfontsList', [] );
[ $status, $oddGone ] = callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => 'odd-one' ] );

check( 'a directory that is no family is listed with the reason, and removed by its name - the sentence has the name it has',
	$odd['broken'] === [ 'odd-one' => 'there is no font.json' ] && $status === 200 && $oddGone['msg'] === $panelText( 'typefaces/msg/removed', 'en_US', 'odd-one' ) && $oddGone['broken'] === [] && is_dir( $fontsDir. '/odd-one' ) === false );

check( 'the log says what was done to a typeface and by which name - and nothing of what was only read; a name that is none is not written as it came',
	Admin::log( 'design/webfonts/install', [ 'slug' => 'inter' ] ) === 'Install webfont inter' && Admin::log( 'design/webfonts/remove', [ 'slug' => 'inter' ] ) === 'Remove webfont inter'
	&& Admin::log( 'design/webfonts/list', [ 'refresh' => true ] ) === ''
	&& Admin::log( 'design/webfonts/install', [ 'slug' => "inter\nforged line" ] ) === 'Install webfont ' && Admin::log( 'design/webfonts/remove', [ 'slug' => [ 'x' ] ] ) === 'Remove webfont '
	&& Admin::log( 'design/webfonts/install', [] ) === 'Install webfont ' );

// What the screen is drawn from, for design-js-smoke.js: the answer the server gives for a project with two families, one of them used, and a directory that is none
callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'inter' ] );
$usedBy( [ 'subtitle' ] );
mkdir( $fontsDir. '/odd-one', 0755, true );
[ , $forTab ] = callDesignAction( $appData, 'apiWebfontsList', [] );
$webfontsFile = ninoSandboxDir( $appData ). '/design-webfonts.json';
file_put_contents( $webfontsFile, json_encode( $forTab, JSON_THROW_ON_ERROR ) );

check( 'the answer that is written down for the screen has what the screen reads: the installed with a face to set the name in, the uses, the directory that is none, and the list that was loaded',
	array_keys( $forTab['installed'] ) !== [] && ( $forTab['uses']['inter'] ?? null ) === [ 'subtitle' ] && array_keys( $forTab['broken'] ) === [ 'odd-one' ] && count( $forTab['families'] ) === 3 && $forTab['fetched'] > 0 );

// Put the sandbox back as the sections after this one found it
$usedBy( [] );

foreach( array_keys( Fonts::installed( $appData ) ) as $slug )
	Webfonts::remove( $appData, $slug );

Webfonts::remove( $appData, 'odd-one' );
\Nino\Filesystem::removeDir( dirname( $fontsDir ) );
@unlink( $cacheFile );
unset( $appData['./nino/filesystem/cache'] );

if( $setupKept === null )
	@unlink( $setupFile );
else
	file_put_contents( $setupFile, $setupKept );

\Nino\Features::saveSettings( $appData, 'design', [ 'webfontsUrl' => Webfonts::DEFAULT_URL, 'webfontsKey' => '' ] );

if( $fillsBefore === null )
	unset( $appData['./nino/html/fills'] );
else
	$appData['./nino/html/fills'] = $fillsBefore;

check( 'and the sandbox is as it was: no family, no directory for them, no cache, no staging, the setup as the sections before left it, and the settings the defaults again',
	Fonts::installed( $appData ) === [] && Fonts::broken( $appData ) === [] && is_dir( dirname( $fontsDir ) ) === false && is_file( $cacheFile ) === false && designStagingClean( $staging ) === true
	&& Webfonts::url( $appData ) === Webfonts::DEFAULT_URL && Webfonts::key( $appData ) === Webfonts::PUBLIC_KEY
	&& ( $setupKept === null ? is_file( $setupFile ) === false : (string) file_get_contents( $setupFile ) === $setupKept ) );


echo "\nThe themes: the whole setup under a name, and the css that goes with it\n";

$themesSection = static function( array &$appData, string $library, array $entryInter ) use ( $richParts, $defaultSets, $panelText ): string {

	$themesFile = ninoSandboxDir( $appData ). '/design-themes.json';

	// What the themes keep is data/design.php and data/themes/ - both put back as they were found when this section ends
	$themeSetupFile 	= \Nino\Filesystem::path( $appData, Setup::PATH );
	$themeSetupKept 	= is_file( $themeSetupFile ) === true ? (string) file_get_contents( $themeSetupFile ) : null;
	$themeFillsKept 	= $appData['./nino/html/fills'] ?? null;
	$themesDir 				= \Nino\Filesystem::path( $appData, Themes::DIR );

	/*	The themes the feature ships are read as they are - every file of library/themes/ - and what a check needs of one it takes from the
		first, the one Setup::defaults() takes its sets from. The words of each in a language are read off its file by the rule describe()
		documents: the line of the language where the file has one, the plain line where it has none	*/
	$shippedFirst	= Themes::SHIPPED[0];
	$shippedFiles	= Themes::shipped( $library );
	$shippedRaw		= array_map( static fn( string $path ): array => (array) include $path, $shippedFiles );
	$wordsOf			= static fn( array $file, string $locale ): array => array_map(
		static fn( string $tag ): string => trim( (string) ( $locale === 'de_DE' ? ( $file[$tag. '-de'] ?? '' ) : '' ) ) ?: trim( (string) ( $file[$tag] ?? '' ) ),
		[ 'name', 'description' ] );
	$shippedWords	= [];

	foreach( [ 'en_US', 'de_DE' ] as $locale )
		foreach( $shippedRaw as $slug => $file )
			$shippedWords[$locale][$slug] = $wordsOf( $file, $locale );

	// The keys of a setup that choose something, as a theme file keeps them: the keys of a setup but the four that are the project's
	$chooses = array_values( array_diff( Setup::KEYS, [ 'format', 'theme', 'css', 'compiled' ] ) );
	// ...and the ones a theme the feature ships chooses: all of them but the palette. A brand colour is the project's - the choices for the features are the theme's, an empty list where it names none
	$choosesShipped = array_values( array_diff( $chooses, [ 'colours' ] ) );
	/*	The theme the feature ships was written before the hover of the pictures was a part of the motion, and it stays as it was until the themes are looked at as a whole: it names the
		parts of the motion it was written with, and the hover of the pictures reads as the default - what the screen holds of such a file is what normalising makes of it. $completed is a file's setup as it
		stands once that default is put where the file names none. So are the toggles, the case of the navigation, the titles and the buttons: the file names the knobs it was written with, and a
		toggle it does not name stands at 0, off - and nothing else may a shipped file leave out	*/
	$later = [ 'hover', 'hover-strength' ];
	$completed = static function( array $given ) use ( $later, $library ): array {

		foreach( $later as $part )
			if( is_array( $given['motion'] ?? null ) === true && array_key_exists( $part, $given['motion'] ) === false )
				$given['motion'][$part] = Setup::defaults( $library )['motion'][$part];

		foreach( Setup::KNOBS as $part => $kinds )
			foreach( $kinds as $knob => $kind )
				if( $kind === 'toggle' && is_array( $given['knobs'][$part] ?? null ) === true && array_key_exists( $knob, $given['knobs'][$part] ) === false )
					$given['knobs'][$part][$knob] = Setup::defaults( $library )['knobs'][$part][$knob];

		return $given;
	};

	check( 'a theme is a setup with two keys more - the theme it was taken from and the css that goes with it - in the order the file is written in, and both empty where none was chosen',
		Setup::KEYS === [ 'format', 'theme', 'size', 'width', 'whitespace', 'radius', 'radius-alt', 'radii', 'motion', 'parts', 'knobs', 'features', 'fonts', 'colours', 'css', 'compiled' ]
		&& array_keys( Setup::defaults( $library ) ) === Setup::KEYS && Setup::defaults( $library )['theme'] === '' && Setup::defaults( $library )['css'] === '' );
	check( '...and the format of the file stays 2: a file without the two keys reads as none and no css, which is what every project had, with no note and nothing migrated',
		Setup::FORMAT === 2 && \Nino\Filesystem::putFileContent( $appData, Setup::PATH, [ 'format' => 2, 'size' => 1, 'width' => -1 ] ) === true
		&& ( $oldNotes = [] ) === [] && ( $old = Setup::read( $appData, $library, $oldNotes ) )['theme'] === '' && $old['css'] === '' && $oldNotes === [] && $old['size'] === 1 && $old['width'] === -1 );

	$notes = [];
	$themed = Setup::normalize( $appData, [ 'theme' => $shippedFirst, 'css' => "a {}\r\nb {}\rc {}" ], $library, $notes );

	check( 'a theme that is there - a shipped one - is kept, and the css with its line breaks all one kind: the machine it was typed on is none of the file\'s business',
		$notes === [] && $themed['theme'] === $shippedFirst && $themed['css'] === "a {}\nb {}\nc {}" );

	$notes = [];
	$longNotes = [];
	$lost = Setup::normalize( $appData, [ 'theme' => 'nowhere', 'css' => 'a {}' ], $library, $notes );

	check( '...one that is not there is none, and the note says which - a note that carries a code and its params, besides the English sentence, and the slug cut to 40 characters, like the other notes - while the css stays whatever the theme was',
		$lost['theme'] === '' && $notes === [ [ 'error' => 'the theme "nowhere" is not there - none is active', 'code' => 'design_theme_missing', 'params' => [ 'nowhere' ] ] ] && $lost['css'] === 'a {}'
		&& Setup::normalize( $appData, [ 'theme' => str_repeat( 'x', 80 ) ], $library, $longNotes )['theme'] === ''
		&& $longNotes === [ [ 'error' => 'the theme "'. str_repeat( 'x', 40 ). '" is not there - none is active', 'code' => 'design_theme_missing', 'params' => [ str_repeat( 'x', 40 ) ] ] ] );

	ninoWarnings();
	$wrongNotes = [];
	$emptyNotes = [];
	$wrong = Setup::normalize( $appData, [ 'theme' => [ $shippedFirst ], 'css' => [ 'a {}' ] ], $library, $wrongNotes );

	check( '...a theme and a css that are no strings are none and no css, and raise nothing - and an empty theme is none, with no note',
		ninoWarnings() === [] && $wrong['theme'] === '' && $wrong['css'] === '' && $wrongNotes === []
		&& Setup::normalize( $appData, [ 'theme' => '' ], $library, $emptyNotes )['theme'] === '' && $emptyNotes === [] );

	Setup::write( $appData, $themed );
	$written = (array) include $themeSetupFile;

	check( 'write() writes the two keys in the order of KEYS, and read() reads them back', array_keys( $written ) === Setup::KEYS && $written['theme'] === $shippedFirst && $written['css'] === "a {}\nb {}\nc {}"
		&& Setup::read( $appData, $library )['theme'] === $shippedFirst && Setup::read( $appData, $library )['css'] === "a {}\nb {}\nc {}" );

	// Name, slug, refusal - the pure functions of the class
	$slugs = [
		'Journal' => 'journal', 'Mein B'. "\u{fc}". 'ro' => 'mein-buero', 'Stra'. "\u{df}". 'e & Co.' => 'strasse-co', "\u{c4}\u{d6}\u{dc} \u{e4}\u{f6}\u{fc} \u{df}" => 'aeoeue-aeoeue-ss',
		'  --Hello,   World!--  ' => 'hello-world', 'Theme 2024' => 'theme-2024', 'a_b' => 'a-b', 'Caf'. "\u{e9}". ' Cr'. "\u{e8}". 'me' => 'caf-cr-me', '!!! ???' => '', '' => '', "\u{65e5}\u{672c}" => '',
	];
	$slugProblems = [];

	foreach( $slugs as $name => $slug )
		if( Themes::slug( $name ) !== $slug )
			$slugProblems[] = $name. ' -> '. Themes::slug( $name );

	check( 'the slug of a name is lower case, the umlauts and the sharp s as ae, oe, ue and ss, every other letter and every run of anything else one hyphen, none at the ends - and nothing where there is nothing to make one of'
		. ( $slugProblems === [] ? '' : ' - '. implode( ', ', $slugProblems ) ), $slugProblems === [] );

	$words = implode( ' ', array_fill( 0, 12, 'abcdefg' ) );

	check( '...at most 64 characters: a long name is cut at a hyphen where one stands in the last 16, and hard where none does - and not at all through a word that ends right at the 64th',
		Themes::slug( $words ) === implode( '-', array_fill( 0, 8, 'abcdefg' ) )
		&& Themes::slug( str_repeat( 'a', 100 ) ) === str_repeat( 'a', 64 )
		&& Themes::slug( str_repeat( 'a', 64 ). ' bbb' ) === str_repeat( 'a', 64 )
		&& strlen( Themes::slug( str_repeat( 'ab ', 40 ) ) ) <= 64 && preg_match( Themes::SLUG, Themes::slug( str_repeat( 'ab ', 40 ) ) ) === 1 );
	check( 'every slug it makes is one the pattern takes, and the pattern takes no path: not an upper case letter, a dot, a slash, a hyphen at the ends, nothing, 65 characters',
		array_filter( array_filter( array_values( $slugs ), static fn( string $slug ): bool => $slug !== '' ), static fn( string $slug ): bool => preg_match( Themes::SLUG, $slug ) !== 1 ) === []
		&& array_filter( [ 'Journal', 'a.b', '../x', 'a/b', '-a', 'a-', 'a--b', '', str_repeat( 'a', 65 ), "a\n" ], static fn( string $slug ): bool => preg_match( Themes::SLUG, $slug ) === 1 ) === [] );

	$bigCss = str_repeat( 'a', 65536 );

	check( 'a css is refused by the code of what is wrong with it: a size over 64 KB first, then an @import, then javascript: or a script, then a closing style tag - and a clean one is not',
		Themes::refusal( '' ) === '' && Themes::refusal( ".a { color: inherit; }\n@media (min-width: 768px) { .b { margin: 0 } }" ) === '' && Themes::refusal( $bigCss ) === ''
		&& Themes::refusal( $bigCss. 'a' ) === 'design_size' && Themes::refusal( $bigCss. '@import "x";' ) === 'design_size'
		&& Themes::refusal( '@import url(x);' ) === 'design_import' && Themes::refusal( "@import\n'x';" ) === 'design_import'
		&& Themes::refusal( '.a { background: url(javascript:alert(1)) }' ) === 'design_script' && Themes::refusal( '</style><script>x</script>' ) === 'design_script'
		&& Themes::refusal( '.a{}</style>' ) === 'design_tag' );
	check( '...whatever the case, a space before the colon, a comment around it - judged on the raw text, so a comment is no place to hide in - and not a word that only begins like one',
		Themes::refusal( '/* @IMPORT x */' ) === 'design_import' && Themes::refusal( '.a { x: JavaScript : y }' ) === 'design_script' && Themes::refusal( '<Script' ) === 'design_script'
		&& Themes::refusal( '/* </STYLE */' ) === 'design_tag' && Themes::refusal( '.a { content: "@importing" } /* the javascriptable thing */' ) === ''
		&& array_keys( Themes::REFUSALS ) === [ 'design_size', 'design_import', 'design_script', 'design_tag' ] );

	// The themes that are there: those the feature ships, which are the files of library/themes/, and the project's own written by the class
	$onDisk = array_map( static fn( string $file ): string => basename( $file, '.php' ), glob( $library. '/themes/*.php' ) ?: [] );
	$extra	= array_values( array_diff( $onDisk, Themes::SHIPPED ) );
	sort( $extra );

	check( 'the themes the feature ships are the files of library/themes/ named by their slugs: the ones SHIPPED names first, in its order, every one of them there, and any other file after them, sorted - a theme added needs no constant',
		array_diff( Themes::SHIPPED, $onDisk ) === [] && array_keys( $shippedFiles ) === array_merge( Themes::SHIPPED, $extra ) && $shippedFirst === Themes::SHIPPED[0]
		&& array_filter( $shippedFiles, static fn( string $path, string $slug ): bool => $path !== $library. '/themes/'. $slug. '.php' || preg_match( Themes::SLUG, $slug ) !== 1, ARRAY_FILTER_USE_BOTH ) === []
		&& array_filter( array_keys( $shippedFiles ), static fn( string $slug ): bool => Themes::isShipped( $library, $slug ) === false ) === []
		&& Themes::isShipped( $library, 'mein-theme' ) === false && Themes::isShipped( $library, '../x' ) === false );

	$own = static fn( string $name, string $description = '', array $css = [] ): array => Themes::record( $name, $description, Setup::normalize( $appData, [ 'parts' => [ 'section' => $richParts['section'] ], 'knobs' => [ 'section' => [ 'title' => 1 ] ] ], $library ), $css[0] ?? '', $css[1] ?? [] );

	\Nino\Filesystem::removeDir( $themesDir );
	check( 'before the project has one, there is no theme of its own - and no directory for them',
		Themes::own( $appData ) === [] && is_dir( $themesDir ) === false );

	foreach( [ 'zeta' => 'Zeta', 'alpha' => 'alpha', 'beta-10' => 'Beta 10', 'beta-2' => 'Beta 2' ] as $slug => $name )
		Themes::write( $appData, $slug, $own( $name, 'Mine: '. $slug ) );

	check( 'write() makes the directory and one file for each, named by the slug, and nothing of a slug that is none',
		array_keys( Themes::own( $appData ) ) === [ 'alpha', 'beta-10', 'beta-2', 'zeta' ] && is_file( $themesDir. '/zeta.php' ) === true
		&& Themes::write( $appData, '../escape', $own( 'x' ) ) === false && Themes::write( $appData, 'Upper', $own( 'x' ) ) === false && Themes::write( $appData, '', $own( 'x' ) ) === false
		&& is_file( dirname( $themesDir ). '/escape.php' ) === false && array_keys( Themes::own( $appData ) ) === [ 'alpha', 'beta-10', 'beta-2', 'zeta' ] );

	$fileOfZeta = (array) include $themesDir. '/zeta.php';
	Themes::write( $appData, 'format-set', [ 'format' => 7, 'name' => 'F', 'stray' => 1 ] );
	$formatSet = (array) include $themesDir. '/format-set.php';

	check( 'a file is the shape of the model: the format, the name, the description, the eight keys of a setup that choose something, the css and the presets, which are none - in that order, and the format set by write()',
		array_keys( $fileOfZeta ) === [ 'format', 'name', 'description', 'setup', 'css', 'presets' ] && $fileOfZeta['format'] === 1 && $fileOfZeta['presets'] === []
		&& array_keys( $fileOfZeta['setup'] ) === $chooses && $fileOfZeta['name'] === 'Zeta'
		&& $formatSet === [ 'format' => 1, 'name' => 'F', 'description' => '', 'setup' => [], 'css' => '', 'presets' => [] ] && Themes::remove( $appData, 'format-set' ) === true );

	check( 'read() answers the array of a shipped theme and of one of the project, and null for everything that is no theme: a slug that is none, one that is not there, a file that is no array, one of another format, one without a name',
		( Themes::read( $appData, $library, $shippedFirst )['name'] ?? '' ) === $shippedRaw[$shippedFirst]['name'] && ( Themes::read( $appData, $library, 'zeta' )['name'] ?? '' ) === 'Zeta'
		&& Themes::read( $appData, $library, '../zeta' ) === null && Themes::read( $appData, $library, 'Zeta' ) === null && Themes::read( $appData, $library, 'missing' ) === null
		&& \Nino\Filesystem::putFileContent( $appData, Themes::DIR. '/no-array.php', 'text' ) === true && Themes::read( $appData, $library, 'no-array' ) === null
		&& \Nino\Filesystem::putFileContent( $appData, Themes::DIR. '/format-two.php', [ 'format' => 2, 'name' => 'Two' ] ) === true && Themes::read( $appData, $library, 'format-two' ) === null
		&& \Nino\Filesystem::putFileContent( $appData, Themes::DIR. '/no-name.php', [ 'format' => 1, 'setup' => [] ] ) === true && Themes::read( $appData, $library, 'no-name' ) === null
		&& \Nino\Filesystem::putFileContent( $appData, Themes::DIR. '/name-number.php', [ 'format' => 1, 'name' => 5 ] ) === true && Themes::read( $appData, $library, 'name-number' ) === null
		&& Themes::exists( $appData, $library, 'zeta' ) === true && Themes::exists( $appData, $library, 'no-name' ) === false && Themes::exists( $appData, $library, $shippedFirst ) === true );
	check( '...a project theme shadows nothing: a file of the project that carries the slug of a shipped theme is never read, and never listed',
		\Nino\Filesystem::putFileContent( $appData, Themes::DIR. '/'. $shippedFirst. '.php', Themes::record( 'Fake', '', [], '' ) ) === true
		&& Themes::read( $appData, $library, $shippedFirst )['name'] === $shippedRaw[$shippedFirst]['name']
		&& array_column( Themes::all( $appData, $library, 'en_US' ), 'name' ) === array_merge( array_column( $shippedWords['en_US'], 0 ), [ 'alpha', 'Beta 2', 'Beta 10', 'Zeta' ] )
		&& Themes::remove( $appData, $shippedFirst ) === true );
	check( 'a theme is removed by its slug - the file is gone, a second time there is nothing to remove, and a shipped one or a slug that is none is not this method\'s',
		Themes::remove( $appData, 'no-array' ) === true && is_file( $themesDir. '/no-array.php' ) === false && Themes::remove( $appData, 'no-array' ) === false
		&& Themes::remove( $appData, '../x' ) === false && Themes::remove( $appData, $shippedFirst ) === false && is_file( $shippedFiles[$shippedFirst] ) === true );

	foreach( [ 'format-two', 'no-name', 'name-number' ] as $stray )
		Themes::remove( $appData, $stray );

	// The list, in both languages
	$listProblems = [];

	foreach( [ 'de_DE', 'en_US', 'fr_FR' ] as $locale ) {

		$list = Themes::all( $appData, $library, $locale );
		$expected = $shippedWords[$locale === 'de_DE' ? 'de_DE' : 'en_US'];

		if( array_slice( array_column( $list, 'slug' ), 0, count( $shippedFiles ) ) !== array_keys( $shippedFiles ) )
			$listProblems[] = $locale. ': the order';

		foreach( array_slice( $list, 0, count( $shippedFiles ) ) as $row )
			if( $row !== [ 'slug' => $row['slug'], 'name' => $expected[$row['slug']][0], 'description' => $expected[$row['slug']][1], 'shipped' => true ] )
				$listProblems[] = $locale. ': '. $row['slug'];
	}

	check( 'the list shows the names and descriptions of the shipped themes as their files give them - the German ones in German where a file has them, the English in English and in any language that has no lines of its own'. ( $listProblems === [] ? '' : ' - '. implode( ', ', $listProblems ) ), $listProblems === [] );

	$ownRows = array_slice( Themes::all( $appData, $library, 'en_US' ), count( $shippedFiles ) );

	check( '...then the themes of the project, sorted by the name as it is shown - naturally and without regard to case - each with the description it was given and no mark of being shipped',
		array_column( $ownRows, 'slug' ) === [ 'alpha', 'beta-2', 'beta-10', 'zeta' ] && array_column( $ownRows, 'name' ) === [ 'alpha', 'Beta 2', 'Beta 10', 'Zeta' ]
		&& array_column( $ownRows, 'shipped' ) === [ false, false, false, false ] && $ownRows[3] === [ 'slug' => 'zeta', 'name' => 'Zeta', 'description' => 'Mine: zeta', 'shipped' => false ] );

	// A theme with a line in each language, which no shipped one has to have: a project's own is made so
	$bilingual = Themes::record( 'Journal', 'Words in English', Setup::normalize( $appData, [], $library ), ".journal { color: inherit; }\n", [ 'name-de' => 'Zeitschrift', 'description-de' => 'Worte auf Deutsch' ] );
	$plain = [ 'name' => 'Plain', 'description' => 'Plain words' ];

	check( 'describe() takes the line of the language where the file has one and the plain one where it has none: de_DE reads name-de and description-de, en_US and fr_FR the plain ones, a theme without lines the plain ones in every language',
		Themes::describe( $bilingual, 'de_DE' ) === [ 'name' => 'Zeitschrift', 'description' => 'Worte auf Deutsch' ]
		&& Themes::describe( $bilingual, 'en_US' ) === [ 'name' => 'Journal', 'description' => 'Words in English' ] && Themes::describe( $bilingual, 'fr_FR' ) === Themes::describe( $bilingual, 'en_US' )
		&& Themes::describe( $plain, 'de_DE' ) === $plain && Themes::describe( $plain, 'en_US' ) === $plain && Themes::describe( $plain, 'C' ) === $plain
		&& Themes::describe( [ 'name' => 'A', 'name-de' => '  ', 'description' => '' ], 'de_DE' ) === [ 'name' => 'A', 'description' => '' ]
		&& Themes::entry( 'x-y', [ 'name' => '' ], 'en_US', false )['name'] === 'x-y'
		&& array_filter( $shippedRaw, static fn( array $file, string $slug ): bool => Themes::describe( $file, 'de_DE' ) !== [ 'name' => $shippedWords['de_DE'][$slug][0], 'description' => $shippedWords['de_DE'][$slug][1] ]
			|| Themes::describe( $file, 'en_US' ) !== [ 'name' => $shippedWords['en_US'][$slug][0], 'description' => $shippedWords['en_US'][$slug][1] ], ARRAY_FILTER_USE_BOTH ) === [] );

	$normalised = Setup::normalize( $appData, [ 'theme' => $shippedFirst, 'css' => 'a {}' ], $library );

	check( 'a snapshot is the eleven keys of a setup that choose something and nothing else - no format, no theme, no css, no record of what was compiled - in the order of KEYS',
		array_keys( Themes::snapshot( $normalised ) ) === $chooses
		&& Themes::snapshot( $normalised ) === array_diff_key( $normalised, array_flip( [ 'format', 'theme', 'css', 'compiled' ] ) ) && Themes::snapshot( [] ) === [] );

	check( 'an export is the file as json - in the order of the shape, the characters of any language and the slashes as they are, a line break at the end - and an import reads it back as the array it was, byte for byte',
		( $exportText = Themes::json( $bilingual ) ) !== '' && str_ends_with( $exportText, "}\n" ) && str_contains( $exportText, '"name-de": "Zeitschrift"' ) === true && str_contains( $exportText, '\\/' ) === false && str_contains( $exportText, '\\u' ) === false
		&& array_keys( json_decode( $exportText, true ) ) === [ 'format', 'name', 'name-de', 'description', 'description-de', 'setup', 'css', 'presets' ]
		&& Themes::fromJson( $exportText ) === $bilingual && Themes::json( Themes::fromJson( $exportText ) ) === $exportText
		&& Themes::fromJson( Themes::json( $fileOfZeta ) ) === $fileOfZeta );
	check( '...and refuses what is no theme file by one code, design_file: text that is no json, json that is no object or is a list, a format that is not 1, a name that is none or no string',
		array_unique( array_map( 'Nino\Modules\Design\Themes::fromJson', [ '', 'text', '"journal"', '5', '[1,2]', '[]', '{"format":2,"name":"X"}', '{"name":"X"}', '{"format":1}', '{"format":1,"name":5}', '{"format":"1","name":"X"}' ] ) ) === [ 'design_file' ]
		&& Themes::fromJson( '{"format":1,"name":"X","stray":true}' ) === [ 'format' => 1, 'name' => 'X', 'stray' => true ] );

	/*	The themes the feature ships, every file of library/themes/ held to what a theme is and never to what these happen to be: a file of
		the shape, a name and a description in both languages, the sets of its seven parts in the library, every position of every one of the forty-six knobs inside its range
		as the file writes it - not as normalising held it there, and a toggle the file does not name at 0 - a css that is not refused, and a setup that is its own normalisation: it
		reads without a note and takes the same snapshot back	*/
	$themeProblems = [];

	foreach( $shippedRaw as $slug => $file ) {

		$given 		= Themes::setup( $file );
		$notes 		= [];
		$taken 		= Setup::normalize( $appData, $given, $library, $notes );
		$css 			= Themes::css( $file );
		$problems = [];
		$strange	= array_filter( array_keys( $file ), static fn( string|int $key ): bool => in_array( $key, [ 'format', 'name', 'description', 'setup', 'css', 'presets' ], true ) === false && preg_match( '/^(?:name|description)-[a-z]{2}$/D', (string) $key ) !== 1 );

		if( preg_match( Themes::SLUG, $slug ) !== 1 || Themes::read( $appData, $library, $slug ) !== $file )
			$problems[] = 'its slug or its file';
		if( ( $file['format'] ?? null ) !== Themes::FORMAT || $strange !== [] || ( $file['presets'] ?? null ) !== [] || is_array( $file['setup'] ?? null ) === false || is_string( $file['css'] ?? null ) === false )
			$problems[] = 'its shape'. ( $strange === [] ? '' : ': '. implode( ', ', $strange ) );

		foreach( [ 'name', 'description', 'description-de' ] as $line )
			if( is_string( $file[$line] ?? null ) === false || trim( $file[$line] ) === '' )
				$problems[] = 'no '. $line;

		// A name is a name: a shipped file carries no name-de (a project theme may, through an import)
		if( array_key_exists( 'name-de', $file ) === true )
			$problems[] = 'a name-de';

		if( array_key_exists( 'colours', $given ) === true )
			$problems[] = 'colours: a theme the feature ships names no palette, the brand colour is the project\'s';
		if( is_array( $given['features'] ?? null ) === false )
			$problems[] = 'features: a theme has the choices for the features, an empty list where it names none';
		if( array_keys( $given ) !== $choosesShipped )
			$problems[] = 'a setup that does not choose '. implode( ', ', $choosesShipped ). ' in that order';
		if( $notes !== [] )
			$problems[] = 'a note: '. implode( '; ', array_map( static fn( string|array $note ): string => is_array( $note ) === true ? $note['error'] : $note, $notes ) );
		if( array_diff_key( Themes::snapshot( $taken ), [ 'colours' => true ] ) !== $completed( $given ) )
			$problems[] = 'a setup that is not its own normalisation';

		$ranged = [];

		foreach( [ 'size', 'width', 'whitespace', 'radius', 'radius-alt' ] as $key )
			if( is_int( $given[$key] ?? null ) === false || in_array( $given[$key], Setup::positions( 'scale' ), true ) === false )
				$ranged[] = $key;

		// The strength of each kind of surface is one of the two words, for every kind there is and for no other
		if( array_keys( (array) ( $given['radii'] ?? [] ) ) !== Setup::SURFACES )
			$ranged[] = 'radii: not the kinds of surface';

		foreach( Setup::SURFACES as $kind )
			if( in_array( $given['radii'][$kind] ?? null, Setup::RADIUS_WORDS, true ) === false )
				$ranged[] = 'radii/'. $kind;

		foreach( Setup::KNOBS as $part => $kinds )
			foreach( $kinds as $knob => $kind ) {

				// A toggle the file does not name is off
				$written = $given['knobs'][$part][$knob] ?? ( $kind === 'toggle' ? 0 : null );

				if( is_int( $written ) === false || in_array( $written, Setup::positions( $kind ), true ) === false )
					$ranged[] = $part. '/'. $knob;
			}

		if( $ranged !== [] )
			$problems[] = 'a position outside of its range as written: '. implode( ', ', $ranged );

		foreach( Setup::PARTS as $part => $kind )
			if( in_array( $given['parts'][$part] ?? null, Setup::available( $library, $part ), true ) === false )
				$problems[] = 'a '. $part. ' that is not in the library';

		if( Themes::refusal( $css ) !== '' || strlen( $css ) > Themes::MAX_CSS )
			$problems[] = 'a css that is refused or longer than '. Themes::MAX_CSS;

		if( $problems !== [] )
			$themeProblems[] = $slug. ' - '. implode( '; ', $problems );
	}

	check( 'every theme the feature ships is a theme: a file of the shape, a name and a description in both languages and no name-de, the sets of its seven parts in the library, every position of the forty-six knobs inside its range as written, a css that is not refused - and its setup is complete and its own normalisation - but for the parts that came after it was written, which read as the default - with no note and no palette: the brand colour is the project\'s'
		. ( $themeProblems === [] ? '' : ' - '. implode( ' | ', $themeProblems ) ), $shippedRaw !== [] && $themeProblems === [] );
	check( '...and the first of them is the setup as it comes: the snapshot of the defaults but for the palette - the framework as it is, which is why it has no css of its own',
		$completed( Themes::setup( $shippedRaw[$shippedFirst] ) ) === array_diff_key( Themes::snapshot( Setup::defaults( $library ) ), [ 'colours' => true ] ) && Themes::css( $shippedRaw[$shippedFirst] ) === '' );

	// The compile
	$notes = [];
	$plainSheet = Compiler::compile( $appData, Setup::normalize( $appData, [], $library ), $library, $notes );

	check( 'compiled with no css, a setup writes no section for it: still fourteen, and the sheet is the delivered one - the header says no theme',
		preg_match_all( '/\/\* ==== (\d+)\. /', $plainSheet, $sections ) === 14 && $sections[1] === array_map( 'strval', range( 1, 14 ) )
		&& str_contains( $plainSheet, " *\ttheme      -\n" ) === true && str_contains( $plainSheet, 'the theme ====' ) === false
		&& Compiler::compile( $appData, Setup::normalize( $appData, [ 'css' => "  \n\t\n" ], $library ), $library ) === $plainSheet );

	$customCss = ".a { color: inherit; }\n.b { margin: 0 }\n";
	$sheet = Compiler::compile( $appData, Setup::normalize( $appData, [ 'css' => $customCss. "\n\n" ], $library ), $library );

	check( 'with a css there is a fifteenth section, the last one, after the knob positions - and the css stands in it as it was typed, byte for byte, with one line break at the end',
		preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $sheet, $sections2, PREG_SET_ORDER ) === 15 && $sections2[13][2] === 'the knob positions' && $sections2[14][1] === '15' && $sections2[14][2] === 'the theme'
		&& str_ends_with( $sheet, "/* ==== 15. the theme ==== */\n". $customCss ) === true && substr_count( $sheet, '.a { color: inherit; }' ) === 1
		&& str_ends_with( Compiler::compile( $appData, Setup::normalize( $appData, [ 'css' => "\n\n  .x {\n\t}  " ], $library ), $library ), "/* ==== 15. the theme ==== */\n\n\n  .x {\n\t}  \n" ) === true );
	check( '...and nothing before it changed: the body up to it is the body of the sheet with none, byte for byte',
		str_starts_with( designBody( $sheet ), rtrim( designBody( $plainSheet ), "\n" ). "\n\n/* ==== 15. the theme ==== */\n" ) === true );
	check( 'the header names the theme the setup was taken from by its slug, and a dash for none',
		str_contains( Compiler::compile( $appData, Setup::normalize( $appData, [ 'theme' => $shippedFirst ], $library ), $library ), " *\ttheme      ". $shippedFirst. "\n" ) === true
		&& str_contains( $sheet, " *\ttheme      -\n" ) === true && preg_match( '/^ \*\tanimation  .*\n \*\ttheme      /m', $sheet ) === 1 );

	$rung = Compiler::compile( $appData, Setup::normalize( $appData, [ 'parts' => [ 'section' => $defaultSets['section'] ], 'knobs' => [ 'section' => [ 'title' => 1 ] ], 'css' => ':root { --section-title--more: 7rem; }' ], $library ), $library );

	check( 'a css that declares a rung of a ladder again stands after the line that selects it, so it wins: the knob keeps choosing, and the rung it chooses is the theme\'s - the one way a theme moves a ladder',
		( $selecting = (int) strpos( $rung, "\t--section-title: var(--section-title--more);" ) ) > 0 && ( $declared = (int) strrpos( $rung, '--section-title--more: 7rem;' ) ) > $selecting
		&& $declared > (int) strpos( $rung, 'the knob positions' ) && substr_count( $rung, '--section-title--more:' ) >= 2 );

	$withNone 	= \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, [], $library ), $library );
	$withCss 		= \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, [ 'css' => 'a {}' ], $library ), $library );
	$withTheme 	= \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, [ 'theme' => $shippedFirst ], $library ), $library );

	check( 'the fingerprint of what a sheet was compiled from moves with the css and with the slug of the theme - a theme chosen and a css typed are changes the panel says were not applied - and with nothing else standing still',
		$withNone !== $withCss && $withNone !== $withTheme && $withCss !== $withTheme
		&& $withNone === \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, [ 'css' => '', 'theme' => '' ], $library ), $library )
		&& $withCss === \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, [ 'css' => "a {}" ], $library ), $library )
		&& $withCss !== \Nino\Modules\Design::fingerprint( $appData, Setup::normalize( $appData, [ 'css' => "a {} " ], $library ), $library ) );

	// The actions of the themes, over callDesignAction() as the panel's own are, in a sandbox with the themes the feature ships and the project's own
	$themesClean = static function() use ( &$appData, $themesDir ): void {

		\Nino\Filesystem::removeDir( $themesDir );

		foreach( array_keys( $appData['./nino/filesystem/cache'] ?? [] ) as $cached )
			if( str_starts_with( (string) $cached, Themes::DIR. '/' ) === true )
				unset( $appData['./nino/filesystem/cache'][$cached] );
	};
	$themesClean();
	@unlink( $themeSetupFile );
	unset( $appData['./nino/filesystem/cache'][Setup::PATH] );
	\Nino\Html::addFills( $appData, (array) include __DIR__. '/../text/de_DE.php', 'de_DE' );
	\Nino\Html::addFills( $appData, (array) include __DIR__. '/../text/en_US.php', 'en_US' );

	$selection = [
		'parts' 		=> $richParts,
		'knobs' 		=> [ 'card' => [ 'space' => 1 ], 'buttons' => [ 'size' => -1 ] ],
		'size' 			=> -1,
		'width' 		=> 1,
		'radius' 		=> 1,
		'radius-alt' => -1,
		'radii' 		=> [ 'row' => 'alternative', 'image' => 'alternative' ],
		'motion' 		=> [ 'effect' => 'zoom', 'strength' => 'soft', 'speed' => 'fast' ],
		'fonts' 		=> [ 'title' => [ 'family' => 'system-serif', 'weight' => 'light', 'size' => '2' ] ],
		'colours' => [ 'primary' => '#8b1d3f', 'harmony' => 2 ],
		'css' 			=> ".nino-card { outline: 0; }\n",
	];
	$selectionSnapshot = Themes::snapshot( Setup::normalize( $appData, $selection, $library ) );
	$shippedOne = $shippedRaw[$shippedFirst];
	$noThemeError 		= [ 'error' => 'there is no such theme', 'code' => 'design_theme' ];
	$answeredCodes 		= [];
	$codesOf 					= static function( array $body ) use ( &$answeredCodes ): array {
		if( isset( $body['code'] ) === true )
			$answeredCodes[] = $body['code'];
		return $body;
	};
	$refusedCss 			= [
		'design_import' => '@import url("x.css");', 'design_script' => '.a { background: url(javascript:x) }', 'design_tag' => '.a {} </style>', 'design_size' => str_repeat( 'a', 65537 ),
	];

	[ $status, $listedNone ] = callDesignAction( $appData, 'apiList' );

	check( 'design/list answers the theme of the stored setup and its css - none and no css where nothing was stored - the themes there are, the shipped ones first in their order and named in the language of the workbench, and the active one, which is none then',
		$status === 200 && $listedNone['theme'] === '' && $listedNone['css'] === '' && $listedNone['active'] === null
		&& array_column( $listedNone['themes'], 'slug' ) === array_keys( $shippedFiles ) && array_column( $listedNone['themes'], 'name' ) === array_column( $shippedWords['de_DE'], 0 )
		&& array_column( $listedNone['themes'], 'shipped' ) === array_fill( 0, count( $shippedFiles ), true ) && array_keys( $listedNone['themes'][0] ) === [ 'slug', 'name', 'description', 'shipped' ]
		&& array_slice( array_keys( $listedNone ), array_search( 'width', array_keys( $listedNone ), true ), 11 ) === [ 'width', 'whitespace', 'radius', 'radius-alt', 'radii', 'radiusWords', 'theme', 'css', 'running', 'themes', 'active' ] && $listedNone['running'] === '' );

	$setupBytes = static fn(): string => is_file( $themeSetupFile ) === true ? (string) file_get_contents( $themeSetupFile ) : '';

	[ $status, $saved ] = callDesignAction( $appData, 'apiSave', [ 'theme' => $shippedFirst, 'css' => ".a { color: inherit; }\r\n" ] + $selection );
	$storedSetup = Setup::read( $appData, $library );

	check( 'design/save stores the theme and the css it is sent with the rest - the css with its line breaks all one kind',
		$status === 200 && $storedSetup['theme'] === $shippedFirst && $storedSetup['css'] === ".a { color: inherit; }\n" && Themes::snapshot( $storedSetup ) === $selectionSnapshot );

	[ , $listedActive ] = callDesignAction( $appData, 'apiList' );

	check( '...and design/list then says them, and the active theme as its file holds it: the row of the list, its own css, and its setup normalised against the library - the css the setup has is the one that was typed, not the theme\'s',
		$listedActive['theme'] === $shippedFirst && $listedActive['css'] === ".a { color: inherit; }\n"
		&& $listedActive['active'] === [ 'slug' => $shippedFirst, 'name' => $shippedWords['de_DE'][$shippedFirst][0], 'description' => $shippedWords['de_DE'][$shippedFirst][1], 'shipped' => true, 'setup' => $completed( Themes::setup( $shippedOne ) ), 'css' => Themes::css( $shippedOne ) ]
		&& $listedActive['active']['css'] !== $listedActive['css'] );

	/*	The theme the website runs is the one of the last apply - the record that apply left in the setup, under the setup it applied - and not the theme of a draft that was saved
		since: the screen tells the two apart, "active" for the one and "loaded, not applied" for the other. A record that is not what an apply writes is no theme	*/
	$recorded = Setup::read( $appData, $library );
	$runs = [];

	foreach( [ [ 'setup' => [ 'theme' => 'older-theme' ] ], [ 'setup' => 'text' ], [ 'setup' => [ 'theme' => [ 'older-theme' ] ] ], [ 'setup' => [] ], [] ] as $record ) {
		Setup::write( $appData, [ 'compiled' => $record ] + $recorded );
		ninoWarnings();
		$runs[] = [ callDesignAction( $appData, 'apiList' )[1]['running'] ?? null, ninoWarnings() ];
	}

	Setup::write( $appData, [ 'compiled' => [] ] + $recorded );

	check( 'design/list says the theme the website runs - the theme under the setup the last apply recorded, which a draft saved since does not move - and none for a record that names no theme, in no shape a warning is raised for',
		$listedActive['running'] === '' && $listedActive['theme'] === $shippedFirst && $runs === [ [ 'older-theme', [] ], [ '', [] ], [ '', [] ], [ '', [] ], [ '', [] ] ] );

	$before = $setupBytes();
	$refusals = [];

	foreach( $refusedCss as $code => $badCss )
		foreach( [ 'apiSave', 'apiPreview' ] as $method ) {

			[ $status, $body ] = callDesignAction( $appData, $method, [ 'css' => $badCss ] + $selection );

			if( [ $status, $codesOf( $body ) ] !== [ 400, [ 'error' => Themes::REFUSALS[$code], 'code' => $code, 'field' => 'css' ] ] )
				$refusals[] = $method. ' '. $code;
		}

	check( 'a css that carries an @import, javascript:, a script, a closing style tag or more than 64 KB is refused by the save and by the preview alike: a 400 with the code of what is wrong, no params and the field css - and nothing is written'
		. ( $refusals === [] ? '' : ' - '. implode( ', ', $refusals ) ), $refusals === [] && $setupBytes() === $before );

	[ $status, $previewed ] = callDesignAction( $appData, 'apiPreview', [ 'css' => ".preview-probe { color: inherit; }\r\n", 'full' => false ] + $selection );

	check( 'the preview of a css that is fine has it in the sheet it answers, as the last section after the knob positions - and still writes nothing',
		$status === 200 && str_ends_with( $previewed['css'], "/* ==== 15. the theme ==== */\n.preview-probe { color: inherit; }\n" ) === true
		&& (int) strpos( $previewed['css'], 'the theme ====' ) > (int) strpos( $previewed['css'], 'the knob positions' ) && $setupBytes() === $before );
	ninoWarnings();
	check( '...and a css that is no string is no css: the same answer as with none, and no warning',
		callDesignAction( $appData, 'apiPreview', [ 'css' => [ '@import' ], 'full' => false ] + $selection )[0] === 200 && ninoWarnings() === []
		&& callDesignAction( $appData, 'apiSave', [ 'css' => 5, 'theme' => [ $shippedFirst ] ] + $selection )[0] === 200 && Setup::read( $appData, $library )['css'] === '' && Setup::read( $appData, $library )['theme'] === '' );

	// Load
	callDesignAction( $appData, 'apiSave', [ 'theme' => $shippedFirst, 'css' => ".a { color: inherit; }\r\n" ] + $selection );
	$before = $setupBytes();
	[ $status, $loaded ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => $shippedFirst ] );

	check( 'loading a theme answers what the screen takes over - its setup as the screen holds one, its css, its name and description in the language of the workbench, whether it is shipped - and nothing about fonts that are missing, as none are',
		$status === 200 && array_keys( $loaded ) === [ 'slug', 'name', 'description', 'shipped', 'setup', 'css', 'missing', 'notes' ] && $loaded['slug'] === $shippedFirst && $loaded['name'] === $shippedWords['de_DE'][$shippedFirst][0]
		&& $loaded['description'] === $shippedWords['de_DE'][$shippedFirst][1] && $loaded['shipped'] === true && $loaded['setup'] === $completed( Themes::setup( $shippedOne ) ) && $loaded['css'] === Themes::css( $shippedOne )
		&& $loaded['missing'] === [] && $loaded['notes'] === [] );
	check( '...no palette - the brand colour is the project\'s, so what answers has no colours and the screen keeps its own - and the choices for the features as the theme has them, an empty list where it names none, which the screen takes in place of its own',
		array_key_exists( 'colours', $loaded['setup'] ) === false && is_array( $loaded['setup']['features'] ?? null ) === true && array_keys( $loaded['setup'] ) === $choosesShipped );
	check( '...it writes nothing - not the setup, not a line of the log - and a slug that names no theme is a 404 with the code of it, whatever it is: not there, no slug, no string, a path',
		$setupBytes() === $before && Admin::log( 'design/theme/load', [ 'slug' => $shippedFirst ] ) === ''
		&& array_unique( array_map( static fn( array $post ): array => $codesOf( callDesignAction( $appData, 'apiThemeLoad', $post )[1] ), [ [ 'slug' => 'nowhere' ], [], [ 'slug' => [ $shippedFirst ] ], [ 'slug' => '../'. $shippedFirst ], [ 'slug' => ucfirst( $shippedFirst ) ] ] ), SORT_REGULAR ) === [ $noThemeError ]
		&& callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'nowhere' ] )[0] === 404 );

	$inkySetup = Setup::normalize( $appData, [], $library );
	$inkySetup['fonts']['title']['family'] = 'inter';
	$inkySetup['fonts']['subtitle']['family'] = 'Not A Slug!';
	$inkySetup['fonts']['text']['family'] = 'nowhere-sans';
	$inkySetup['fonts']['button']['family'] = 'inter';
	$inkySetup['fonts']['code']['family'] = 'system-mono';
	$inkySetup['parts']['section'] = 'no-such-set';
	Themes::write( $appData, 'inky', Themes::record( 'Inky', 'Faces to install', $inkySetup, '' ) );
	[ $status, $inky ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'inky' ] );

	check( 'a theme that names typefaces this project has not installed keeps the families of the file, so that the screen can offer to install them - which a normalising would have replaced with the face of the role - and names them once each, with the slug for a name where no list is cached: the one that is no slug is replaced, and so is one that no role offers (the code has monospace faces only), which is no family to install',
		$status === 200 && $inky['setup']['fonts']['title']['family'] === 'inter' && $inky['setup']['fonts']['text']['family'] === 'nowhere-sans' && $inky['setup']['fonts']['button']['family'] === 'inter'
		&& $inky['setup']['fonts']['subtitle']['family'] === 'open-sans' && $inky['setup']['fonts']['code']['family'] === ''
		&& $inky['missing'] === [ [ 'slug' => 'inter', 'name' => 'inter', 'inList' => false ], [ 'slug' => 'nowhere-sans', 'name' => 'nowhere-sans', 'inList' => false ] ]
		&& $inky['setup']['parts']['section'] === $defaultSets['section'] && count( $inky['notes'] ) === 1 && ( $inky['notes'][0]['code'] ?? '' ) === 'design_set_missing' && ( $inky['notes'][0]['params'][1] ?? '' ) === 'no-such-set' );

	\Nino\Filesystem::putFileContent( $appData, Webfonts::CACHE, [ 'fetched' => time(), 'url' => Webfonts::url( $appData ), 'families' => [ $entryInter ] ] );
	$inkyListed = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'inky' ] )[1];
	@unlink( \Nino\Filesystem::path( $appData, Webfonts::CACHE ) );
	unset( $appData['./nino/filesystem/cache'][Webfonts::CACHE] );

	check( '...and with the list of the Typefaces tab cached, the entry says that the list has the family and under what name - the one of the list',
		$inkyListed['missing'] === [ [ 'slug' => 'inter', 'name' => $entryInter['family'], 'inList' => true ], [ 'slug' => 'nowhere-sans', 'name' => 'nowhere-sans', 'inList' => false ] ] && $entryInter['family'] !== 'inter' );

	/*	The record of the list for a theme that names typefaces the project lacks: a family the screen takes over as '' on a
		load that installs nothing (see _takeTheme() in admin.js) is '' in the record too, so that the chip on the strip's Themes button
		stays quiet when the panel is opened again - the stored setup names the theme for one list, and is put back	*/
	$storedBefore = Setup::read( $appData, $library );
	$inkyStored = $storedBefore;
	$inkyStored['theme'] = 'inky';
	Setup::write( $appData, $inkyStored );
	[ , $inkyList ] = callDesignAction( $appData, 'apiList' );
	Setup::write( $appData, $storedBefore );

	check( 'design/list\'s record of the active theme has \'\' where a family is not installed here - what the screen takes over on a load that installs nothing - and the face of the role where the file names no slug at all, so that the record and the screen agree once the panel is opened again',
		( $inkyList['active']['slug'] ?? '' ) === 'inky' && $inkyList['active']['setup']['fonts']['title']['family'] === '' && $inkyList['active']['setup']['fonts']['text']['family'] === ''
		&& $inkyList['active']['setup']['fonts']['button']['family'] === '' && $inkyList['active']['setup']['fonts']['subtitle']['family'] === 'open-sans' && $inkyList['active']['setup']['fonts']['code']['family'] === ''
		&& $inkyList['active']['setup']['parts']['section'] === $defaultSets['section'] && $inkyList['theme'] === 'inky' );

	Themes::write( $appData, 'edited', Themes::record( 'Edited by hand', '', Setup::normalize( $appData, [], $library ), '@import "x.css";' ) );

	check( 'a css of the project\'s own that somebody edited by hand holds to the same rules as a request: loading it is a 400 with the code and the field, not a css taken over',
		$codesOf( callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'edited' ] )[1] ) === [ 'error' => Themes::REFUSALS['design_import'], 'code' => 'design_import', 'field' => 'css' ]
		&& callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'edited' ] )[0] === 400 );
	Themes::remove( $appData, 'edited' );

	/*	A theme of the project to work on, as a duplicate or an import makes one: the screen kept under a name, with the lines of the file in the other language that a save keeps and a
		rename drops. Written straight, since no action makes a theme out of the screen any more	*/
	$files = static fn(): array => array_map( static fn( string $path ): string => (string) file_get_contents( $path ), Themes::own( $appData ) );
	$before = $setupBytes();
	Themes::write( $appData, 'mein-theme', Themes::record( ' Mein Theme ', "  Eine Beschreibung \n", Setup::normalize( $appData, $selection, $library ), ".nino-card { outline: 0; }\n", [ 'name-de' => 'Mein Thema', 'description-de' => 'Eine deutsche Beschreibung' ] ) );
	$mine = Themes::read( $appData, $library, 'mein-theme' ) ?? [];

	check( 'a theme of the project is a file of the shape, the name and the description trimmed, the lines in another language beside them, the setup a snapshot of the selection, the css and no presets',
		array_keys( $mine ) === [ 'format', 'name', 'name-de', 'description', 'description-de', 'setup', 'css', 'presets' ] && $mine['name'] === 'Mein Theme' && $mine['description'] === 'Eine Beschreibung'
		&& $mine['setup'] === $selectionSnapshot && $mine['css'] === ".nino-card { outline: 0; }\n" && $mine['presets'] === [] && $setupBytes() === $before );

	// Save
	$changed = [ 'knobs' => [ 'card' => [ 'space' => 2 ], 'buttons' => [ 'size' => 1 ] ], 'css' => ".changed { margin: 0 }\n" ] + $selection;
	$changedSnapshot = Themes::snapshot( Setup::normalize( $appData, $changed, $library ) );
	[ $status, $resaved ] = callDesignAction( $appData, 'apiThemeSave', [ 'slug' => 'mein-theme', 'name' => ' Anderer Name ', 'description' => 'Andere Beschreibung' ] + $changed );
	$mine = Themes::read( $appData, $library, 'mein-theme' ) ?? [];

	check( 'saving puts the screen into a theme of the project whole - its setup and its css - and answers the list and the theme: the name and the description are what the file has, whatever was posted with them, and the lines in the other language stay with them, name-de and description-de; the slug stays what it is',
		$status === 200 && array_keys( $resaved ) === [ 'themes', 'active' ] && array_keys( $mine ) === [ 'format', 'name', 'name-de', 'description', 'description-de', 'setup', 'css', 'presets' ]
		&& $mine['name'] === 'Mein Theme' && $mine['name-de'] === 'Mein Thema' && $mine['description'] === 'Eine Beschreibung' && $mine['description-de'] === 'Eine deutsche Beschreibung'
		&& $mine['setup'] === $changedSnapshot && $mine['css'] === ".changed { margin: 0 }\n" && is_file( $themesDir. '/anderer-name.php' ) === false
		&& $resaved['active'] === [ 'slug' => 'mein-theme', 'name' => 'Mein Thema', 'description' => 'Eine deutsche Beschreibung', 'shipped' => false, 'setup' => $changedSnapshot, 'css' => ".changed { margin: 0 }\n" ]
		&& Admin::log( 'design/theme/save', [ 'slug' => 'mein-theme' ] ) === 'Save Design theme mein-theme' );
	check( '...saved with no name posted at all it is the same: the request carries a slug and the selection',
		callDesignAction( $appData, 'apiThemeSave', [ 'slug' => 'mein-theme' ] + $changed )[0] === 200 && ( Themes::read( $appData, $library, 'mein-theme' )['name'] ?? '' ) === 'Mein Theme' );

	$before = $files();
	$saveErrors = [
		[ [ 'slug' => 'nowhere' ], 404, $noThemeError ],
		[ [], 404, $noThemeError ],
		[ [ 'slug' => $shippedFirst ], 409, [ 'error' => 'a shipped theme cannot be changed or deleted - duplicate it', 'code' => 'design_shipped' ] ],
	];

	foreach( $refusedCss as $code => $badCss )
		$saveErrors[] = [ [ 'slug' => 'mein-theme', 'css' => $badCss ], 400, [ 'error' => Themes::REFUSALS[$code], 'code' => $code, 'field' => 'css' ] ];

	$saveProblems = [];

	foreach( $saveErrors as $number => [ $post, $expectedStatus, $expectedBody ] )
		if( [ $expectedStatus, $codesOf( callDesignAction( $appData, 'apiThemeSave', $post + $changed )[1] ) ] !== [ callDesignAction( $appData, 'apiThemeSave', $post + $changed )[0], $expectedBody ] )
			$saveProblems[] = $number;

	check( 'saving refuses a theme that is not there (404), a shipped one (409, design_shipped) and each css that is refused - in that order, every one with its code - and writes nothing'
		. ( $saveProblems === [] ? '' : ' - '. implode( ', ', $saveProblems ) ), $saveProblems === [] && $files() === $before );

	// Rename
	$setupBefore = $setupBytes();
	[ $status, $renamed ] = callDesignAction( $appData, 'apiThemeRename', [ 'slug' => 'mein-theme', 'name' => ' Neuer Name ', 'description' => "  Neue Beschreibung \n" ] + $selection );
	$mine = Themes::read( $appData, $library, 'mein-theme' ) ?? [];

	check( 'renaming writes the name and the description that were typed, trimmed, and nothing else of the file: its setup and its css are the ones it had - whatever selection the request carries -, the lines in the other language go, a name being a name, and the slug stays what it is',
		$status === 200 && array_keys( $mine ) === [ 'format', 'name', 'description', 'setup', 'css', 'presets' ] && $mine['name'] === 'Neuer Name' && $mine['description'] === 'Neue Beschreibung'
		&& $mine['setup'] === $changedSnapshot && $mine['css'] === ".changed { margin: 0 }\n" && is_file( $themesDir. '/neuer-name.php' ) === false && $setupBytes() === $setupBefore );
	check( '...and answers the list and the theme as design/list would give it, and the line of the log says which theme',
		array_keys( $renamed ) === [ 'themes', 'active' ] && $renamed['themes'] === Themes::all( $appData, $library, 'de_DE' )
		&& $renamed['active'] === [ 'slug' => 'mein-theme', 'name' => 'Neuer Name', 'description' => 'Neue Beschreibung', 'shipped' => false, 'setup' => $changedSnapshot, 'css' => ".changed { margin: 0 }\n" ]
		&& Admin::log( 'design/theme/rename', [ 'slug' => 'mein-theme' ] ) === 'Rename Design theme mein-theme' );

	$before = $files();
	$renameErrors = [
		[ [ 'slug' => 'nowhere', 'name' => 'x' ], 404, $noThemeError ],
		[ [ 'name' => 'x' ], 404, $noThemeError ],
		[ [ 'slug' => '../mein-theme', 'name' => 'x' ], 404, $noThemeError ],
		[ [ 'slug' => $shippedFirst, 'name' => 'x' ], 409, [ 'error' => 'a shipped theme cannot be changed or deleted - duplicate it', 'code' => 'design_shipped' ] ],
	];

	foreach( [ [ 'name' => '' ], [ 'name' => '   ' ], [], [ 'name' => [ 'Neuer Name' ] ], [ 'name' => '!!!' ], [ 'name' => ' ?! - ' ] ] as $post )
		$renameErrors[] = [ [ 'slug' => 'mein-theme' ] + $post, 400, [ 'error' => 'the name has no letter or digit in it', 'code' => 'design_name' ] ];

	$renameProblems = [];

	foreach( $renameErrors as $number => [ $post, $expectedStatus, $expectedBody ] )
		if( [ $expectedStatus, $codesOf( callDesignAction( $appData, 'apiThemeRename', $post )[1] ) ] !== [ callDesignAction( $appData, 'apiThemeRename', $post )[0], $expectedBody ] )
			$renameProblems[] = $number;

	check( 'renaming refuses a theme that is not there (404) - whatever the slug is: none, a path -, a shipped one (409, design_shipped) and a name with no letter or digit in it - empty once trimmed, only punctuation, no string - (400, design_name) - in that order, every one with its code - and writes nothing'
		. ( $renameProblems === [] ? '' : ' - '. implode( ', ', $renameProblems ) ), $renameProblems === [] && $files() === $before );

	callDesignAction( $appData, 'apiThemeRename', [ 'slug' => 'mein-theme', 'name' => str_repeat( 'x', 100 ), 'description' => str_repeat( 'y', 500 ) ] );
	$longFile = Themes::read( $appData, $library, 'mein-theme' ) ?? [];

	check( 'a name is cut at 80 characters and a description at 400, and the slug stays what it was: the file is still the same one',
		mb_strlen( $longFile['name'] ) === 80 && mb_strlen( $longFile['description'] ) === 400 && is_file( $themesDir. '/mein-theme.php' ) === true && is_file( $themesDir. '/'. str_repeat( 'x', 64 ). '.php' ) === false );
	[ $status ] = callDesignAction( $appData, 'apiThemeRename', [ 'slug' => 'mein-theme', 'name' => ' Ab ', 'description' => '' ] );

	check( 'a name with a letter or a digit in it is taken, trimmed, whatever else is in it: " Ab " is the name Ab',
		$status === 200 && ( Themes::read( $appData, $library, 'mein-theme' )['name'] ?? '' ) === 'Ab' );
	callDesignAction( $appData, 'apiThemeRename', [ 'slug' => 'mein-theme', 'name' => 'Neuer Name', 'description' => 'Neue Beschreibung' ] );

	/*	A theme is changed in one step, the file read and written back under one lock (Themes::change()): what is written is what the callback answers - in the shape of a file - for the array the file had by then,
		a file that is no theme of this shape or no file at all is none to change, and a slug that is none is no theme. A save and a rename are both made through it	*/
	$changeSeen = [];
	$changedOk = Themes::change( $appData, 'mein-theme', static function( array $file ) use ( &$changeSeen ): array {
		$changeSeen = $file;
		return Themes::record( 'Via change', 'Beschreibung', Themes::setup( $file ), Themes::css( $file ) );
	} );
	$viaChange = Themes::read( $appData, $library, 'mein-theme' ) ?? [];

	check( 'changing a theme writes what the callback answers for the array the file has: the callback is given the file as it is, the record comes back in the shape of a file - format first, no presets of its own - and the file is replaced whole',
		$changedOk === true && ( $changeSeen['name'] ?? '' ) === 'Neuer Name' && ( $changeSeen['setup'] ?? [] ) === $changedSnapshot && array_keys( $viaChange ) === [ 'format', 'name', 'description', 'setup', 'css', 'presets' ]
		&& $viaChange['name'] === 'Via change' && $viaChange['description'] === 'Beschreibung' && $viaChange['setup'] === $changedSnapshot );

	file_put_contents( $themesDir. '/broken.php', '<?php return 5;' );
	file_put_contents( $themesDir. '/notheme.php', '<?php return [ \'format\' => 2, \'name\' => \'x\' ];' );
	unset( $appData['./nino/filesystem/cache'][Themes::DIR. '/broken.php'], $appData['./nino/filesystem/cache'][Themes::DIR. '/notheme.php'] );
	$changeCalls = 0;
	$neverCalled = static function( array $file ) use ( &$changeCalls ): array {
		$changeCalls++;
		return $file;
	};
	$brokenBytes = [ (string) file_get_contents( $themesDir. '/broken.php' ), (string) file_get_contents( $themesDir. '/notheme.php' ) ];
	$aborted = [ Themes::change( $appData, 'broken', $neverCalled ), Themes::change( $appData, 'notheme', $neverCalled ), Themes::change( $appData, 'nowhere', $neverCalled ), Themes::change( $appData, '../mein-theme', $neverCalled ) ];

	check( '...a file that is no array, one of another format, no file and a slug that is none are none to change: false, the callback is not called, and nothing is written - no file is made for a theme that is not there',
		$aborted === [ false, false, false, false ] && $changeCalls === 0 && [ (string) file_get_contents( $themesDir. '/broken.php' ), (string) file_get_contents( $themesDir. '/notheme.php' ) ] === $brokenBytes
		&& is_file( $themesDir. '/nowhere.php' ) === false );
	@unlink( $themesDir. '/broken.php' );
	@unlink( $themesDir. '/notheme.php' );
	unset( $appData['./nino/filesystem/cache'][Themes::DIR. '/broken.php'], $appData['./nino/filesystem/cache'][Themes::DIR. '/notheme.php'] );

	callDesignAction( $appData, 'apiThemeRename', [ 'slug' => 'mein-theme', 'name' => 'Neuer Name', 'description' => 'Neue Beschreibung' ] );

	/*	A theme that cannot be written: the lock the write takes lives in data/.locks/, which is made a file for the length of the request. A directory that is no writable one would do for a user
		who is not root, and this is one for root too	*/
	$locksDir = \Nino\Filesystem::path( $appData, '/data' ). '/.locks';
	$locksSide = $locksDir. '.aside';
	$locksWere = is_dir( $locksDir ) === true && rename( $locksDir, $locksSide ) === true;
	file_put_contents( $locksDir, 'not a directory' );
	[ $status, $blocked ] = callDesignAction( $appData, 'apiThemeRename', [ 'slug' => 'mein-theme', 'name' => 'Blocked' ] );
	@unlink( $locksDir );

	if( $locksWere === true )
		rename( $locksSide, $locksDir );

	check( 'a theme that cannot be written is a 500 with the English sentence and no code - the way every failure of the machine is told in this feature - and the file is as it was',
		$status === 500 && $blocked === [ 'error' => 'could not write /data/themes/mein-theme.php' ] && ( Themes::read( $appData, $library, 'mein-theme' )['name'] ?? '' ) === 'Neuer Name' );

	// Duplicate
	$copyOf = static fn( string $name, string $locale ): string => $panelText( 'theme/copy', $locale, $name );
	$copyName = $copyOf( $shippedWords['de_DE'][$shippedFirst][0], 'de_DE' );
	$ownCopySlug = Themes::slug( $copyOf( 'Neuer Name', 'de_DE' ) );
	$copySlug = Themes::slug( $copyName );
	[ $status, $copied ] = callDesignAction( $appData, 'apiThemeDuplicate', [ 'slug' => $shippedFirst ] );
	$copyFile = Themes::read( $appData, $library, $copySlug ) ?? [];

	check( 'duplicating a shipped theme in a German workbench makes the copy of its name that the German text words (theme/copy) under the slug of that: the description in the language of the workbench, the setup and the css as they are, no lines in other languages and no presets - the answer says the slug and the list',
		$status === 200 && array_keys( $copied ) === [ 'slug', 'themes' ] && $copied['slug'] === $copySlug && array_keys( $copyFile ) === [ 'format', 'name', 'description', 'setup', 'css', 'presets' ]
		&& $copyFile['name'] === $copyName && $copyFile['description'] === $shippedWords['de_DE'][$shippedFirst][1] && $copyFile['setup'] === Themes::setup( $shippedOne ) && $copyFile['css'] === Themes::css( $shippedOne )
		&& in_array( $copySlug, array_column( $copied['themes'], 'slug' ), true ) === true && Admin::log( 'design/theme/duplicate', [ 'slug' => $shippedFirst ] ) === 'Duplicate Design theme '. $shippedFirst. ' as '. $copySlug );

	$second = callDesignAction( $appData, 'apiThemeDuplicate', [ 'slug' => $shippedFirst ] )[1]['slug'] ?? '';
	$ofCopy = callDesignAction( $appData, 'apiThemeDuplicate', [ 'slug' => $copySlug ] )[1]['slug'] ?? '';
	$ofOwn = callDesignAction( $appData, 'apiThemeDuplicate', [ 'slug' => 'mein-theme' ] )[1]['slug'] ?? '';

	check( '...a second copy takes the next free slug, the copy of a copy is a copy of its name, and one of the project\'s own is copied the same way',
		$second === $copySlug. '-2' && Themes::read( $appData, $library, $copySlug. '-2' )['name'] === $copyName
		&& $ofCopy === Themes::slug( $copyOf( $copyName, 'de_DE' ) ) && Themes::read( $appData, $library, Themes::slug( $copyOf( $copyName, 'de_DE' ) ) )['name'] === $copyOf( $copyName, 'de_DE' )
		&& $ofOwn === $ownCopySlug && Themes::read( $appData, $library, $ownCopySlug )['setup'] === $changedSnapshot );

	\Nino\Locales::useLocale( $appData, 'en_US' );
	$englishCopy = callDesignAction( $appData, 'apiThemeDuplicate', [ 'slug' => $shippedFirst ] )[1];
	\Nino\Locales::useLocale( $appData, 'de_DE' );

	$englishName = $copyOf( $shippedWords['en_US'][$shippedFirst][0], 'en_US' );

	check( '...and in an English workbench it is the copy of the English text, with the English description',
		( $englishCopy['slug'] ?? '' ) === Themes::slug( $englishName ) && Themes::read( $appData, $library, Themes::slug( $englishName ) )['name'] === $englishName
		&& Themes::read( $appData, $library, Themes::slug( $englishName ) )['description'] === $shippedWords['en_US'][$shippedFirst][1] );
	check( '...and a theme that is not there cannot be copied',
		$codesOf( callDesignAction( $appData, 'apiThemeDuplicate', [ 'slug' => 'nowhere' ] )[1] ) === $noThemeError && callDesignAction( $appData, 'apiThemeDuplicate', [] )[0] === 404 );

	// What the screen is drawn from, for design-js-smoke.js: the list with a theme of the project active, and the answers of the actions
	callDesignAction( $appData, 'apiSave', [ 'theme' => 'mein-theme', 'css' => ".typed { margin: 0 }\n" ] + $changed );
	[ , $listedOwnActive ] = callDesignAction( $appData, 'apiList' );

	// Delete: the active theme, and one that is not
	$stored = Setup::read( $appData, $library );
	$stored['theme'] = $ownCopySlug;
	$stored['compiled'] = [ 'at' => 'then', 'sha' => 'a', 'input' => 'b', 'setup' => [] ];
	Setup::write( $appData, $stored );
	[ $status, $deleted ] = callDesignAction( $appData, 'apiThemeDelete', [ 'slug' => $ownCopySlug ] );
	$afterDelete = Setup::read( $appData, $library );

	check( 'deleting the active theme removes its file and the slug in the setup - the record of what was compiled stays - and answers the list and the theme the setup has now, which is none',
		$status === 200 && array_keys( $deleted ) === [ 'themes', 'theme' ] && $deleted['theme'] === '' && is_file( $themesDir. '/'. $ownCopySlug. '.php' ) === false && Themes::exists( $appData, $library, $ownCopySlug ) === false
		&& $afterDelete['theme'] === '' && $afterDelete['compiled'] === $stored['compiled'] && ( (array) include $themeSetupFile )['theme'] === ''
		&& in_array( $ownCopySlug, array_column( $deleted['themes'], 'slug' ), true ) === false && Admin::log( 'design/theme/delete', [ 'slug' => $ownCopySlug ] ) === 'Delete Design theme '. $ownCopySlug );

	$stored['theme'] = $shippedFirst;
	Setup::write( $appData, $stored );
	$before = $setupBytes();
	[ $status, $deletedOther ] = callDesignAction( $appData, 'apiThemeDelete', [ 'slug' => $copySlug. '-2' ] );

	check( '...one that is not the active theme is removed and the setup stays as it was, the answer saying which theme it has',
		$status === 200 && $deletedOther['theme'] === $shippedFirst && is_file( $themesDir. '/'. $copySlug. '-2.php' ) === false && $setupBytes() === $before );

	$before = $files();
	$setupBefore = $setupBytes();
	$deleteErrors = [
		[ [ 'slug' => 'nowhere' ], 404, $noThemeError ], [ [], 404, $noThemeError ], [ [ 'slug' => '../data/design' ], 404, $noThemeError ],
		[ [ 'slug' => $shippedFirst ], 409, [ 'error' => 'a shipped theme cannot be changed or deleted - duplicate it', 'code' => 'design_shipped' ] ],
		[ [ 'slug' => array_key_last( $shippedFiles ) ], 409, [ 'error' => 'a shipped theme cannot be changed or deleted - duplicate it', 'code' => 'design_shipped' ] ],
	];

	check( '...a theme that is not there is a 404, a shipped one a 409 with design_shipped - and the files of the feature and of the project stay as they are',
		array_filter( $deleteErrors, static fn( array $one ): bool => [ $one[1], $codesOf( callDesignAction( $appData, 'apiThemeDelete', $one[0] )[1] ) ] !== [ callDesignAction( $appData, 'apiThemeDelete', $one[0] )[0], $one[2] ] ) === []
		&& $files() === $before && is_file( $shippedFiles[$shippedFirst] ) === true && $setupBytes() === $setupBefore );

	// Export
	[ $status, $exported ] = callDesignAction( $appData, 'apiThemeExport', [ 'slug' => $shippedFirst ] );
	$exportedOwn = callDesignAction( $appData, 'apiThemeExport', [ 'slug' => 'mein-theme' ] )[1];

	check( 'exporting answers the file of the theme as json - a shipped one with its lines in the other languages - and the name it is saved under: nino-theme-<slug>.json; it writes nothing and says nothing',
		$status === 200 && array_keys( $exported ) === [ 'json', 'file' ] && $exported['json'] === Themes::json( $shippedOne ) && $exported['file'] === 'nino-theme-'. $shippedFirst. '.json'
		&& $exportedOwn['json'] === Themes::json( Themes::read( $appData, $library, 'mein-theme' ) ) && $exportedOwn['file'] === 'nino-theme-mein-theme.json'
		&& $codesOf( callDesignAction( $appData, 'apiThemeExport', [ 'slug' => 'nowhere' ] )[1] ) === $noThemeError && Admin::log( 'design/theme/export', [ 'slug' => $shippedFirst ] ) === '' );

	// Import
	$file = json_decode( $exported['json'], true );
	$file['name'] = 'Eingespielt';
	$file['name-de'] = 'Eingespielt DE';
	$file['presets'] = [ 'x' ];
	$file['setup']['parts']['header'] = 'no-such-set';
	$file['setup']['fonts']['title']['family'] = 'inter';
	$before = $setupBytes();
	[ $status, $imported ] = callDesignAction( $appData, 'apiThemeImport', [ 'json' => json_encode( $file ) ] );
	$importedFile = Themes::read( $appData, $library, 'eingespielt' ) ?? [];

	check( 'importing writes the theme of an export as a theme of the project under the slug of its name: the setup normalised against what the project has, the css, the name and the description, the lines in other languages kept, the presets none - and the families of the fonts as the file has them, as loading keeps them, so that a typeface that is not installed yet can still be offered - the answer is the slug and the list',
		$status === 200 && array_keys( $imported ) === [ 'slug', 'themes' ] && $imported['slug'] === 'eingespielt' && $importedFile['name'] === 'Eingespielt' && $importedFile['name-de'] === 'Eingespielt DE'
		&& $importedFile['description-de'] === $shippedOne['description-de'] && $importedFile['presets'] === [] && $importedFile['css'] === Themes::css( $shippedOne )
		&& $importedFile['setup']['parts']['header'] === $defaultSets['header'] && $importedFile['setup']['fonts']['title']['family'] === 'inter' && array_keys( $importedFile['setup'] ) === $choosesShipped
		&& $importedFile['setup']['parts']['section'] === Themes::setup( $shippedOne )['parts']['section'] && in_array( 'eingespielt', array_column( $imported['themes'], 'slug' ), true ) === true
		&& $setupBytes() === $before && Admin::log( 'design/theme/import', [ 'json' => json_encode( $file ) ] ) === 'Import Design theme eingespielt' );

	// A file that names colours keeps them as a theme of the project, normalised; one that names none keeps none - the rule is the file's and not the feature's
	$colourFile = $file;
	$colourFile['name'] = 'Eingespielt mit Farben';
	$colourFile['setup']['colours'] = [ 'primary' => '#8b1d3f', 'harmony' => 2 ];
	callDesignAction( $appData, 'apiThemeImport', [ 'json' => json_encode( $colourFile ) ] );
	$colourImported = Themes::read( $appData, $library, 'eingespielt-mit-farben' ) ?? [];

	check( '...a file that names colours is a theme of the project that keeps them, normalised, and one that names none is one that keeps none: an export of a theme the feature ships comes back without a palette',
		array_key_exists( 'colours', $importedFile['setup'] ) === false && array_keys( $colourImported['setup'] ?? [] ) === $chooses
		&& ( $colourImported['setup']['colours']['primary'] ?? '' ) === '#8b1d3f' && ( $colourImported['setup']['colours']['harmony'] ?? 0 ) === 2 && ( $colourImported['setup']['colours']['mode'] ?? '' ) === 'auto' );

	Themes::remove( $appData, 'eingespielt-mit-farben' );

	$before = $files();
	$importErrors = [];

	foreach( [ [], [ 'json' => '' ], [ 'json' => 'not json' ], [ 'json' => '[1]' ], [ 'json' => '{"format":2,"name":"x"}' ], [ 'json' => '{"name":"x"}' ], [ 'json' => [ 'format' => 1 ] ] ] as $post )
		$importErrors[] = [ callDesignAction( $appData, 'apiThemeImport', $post )[0], $codesOf( callDesignAction( $appData, 'apiThemeImport', $post )[1] ) ];

	check( 'a file that is no theme file of this feature - no text, no json, no object, another format, no name - is a 400 with design_file',
		array_unique( $importErrors, SORT_REGULAR ) === [ [ 400, [ 'error' => 'that is no theme file of this feature', 'code' => 'design_file' ] ] ] && $files() === $before );
	[ $takenStatus, $takenBody ] = callDesignAction( $appData, 'apiThemeImport', [ 'json' => $exported['json'] ] );
	$takenShipped = [ $takenStatus, $codesOf( $takenBody ) ];

	check( '...a name no slug can be made of is design_name, a slug that is taken - the shipped one of the export, the project\'s own imported before - is a 409 with design_taken and the slug, and nothing is written',
		callDesignAction( $appData, 'apiThemeImport', [ 'json' => json_encode( [ 'name' => '!!!' ] + $file ) ] ) === [ 400, [ 'error' => 'the name has no letter or digit in it', 'code' => 'design_name' ] ]
		&& $takenShipped === [ 409, [ 'error' => 'a theme with the slug "'. $shippedFirst. '" exists already', 'code' => 'design_taken', 'params' => [ $shippedFirst ] ] ]
		&& callDesignAction( $appData, 'apiThemeImport', [ 'json' => json_encode( $file ) ] ) === [ 409, [ 'error' => 'a theme with the slug "eingespielt" exists already', 'code' => 'design_taken', 'params' => [ 'eingespielt' ] ] ]
		&& $files() === $before );

	$importRefusals = [];

	foreach( $refusedCss as $code => $badCss ) {

		[ $status, $body ] = callDesignAction( $appData, 'apiThemeImport', [ 'json' => json_encode( [ 'name' => 'Css '. $code, 'css' => $badCss ] + $file ) ] );

		if( [ $status, $codesOf( $body ) ] !== [ 400, [ 'error' => Themes::REFUSALS[$code], 'code' => $code, 'field' => 'css' ] ] )
			$importRefusals[] = $code;
	}

	check( '...and a css that is refused is a 400 with its code, as it is everywhere, and no file is written'. ( $importRefusals === [] ? '' : ' - '. implode( ', ', $importRefusals ) ), $importRefusals === [] && $files() === $before );

	// The codes: every sentence is in both text files, with the %s where a parameter goes
	$themeWords = [ (array) include __DIR__. '/../text/de_DE.php', (array) include __DIR__. '/../text/en_US.php' ];
	$codeWords = [ 'design_import', 'design_script', 'design_tag', 'design_size', 'design_name', 'design_taken', 'design_shipped', 'design_theme', 'design_file' ];
	$codeProblems = [];

	foreach( $codeWords as $code )
		foreach( $themeWords as $number => $words )
			if( trim( (string) ( $words['[[/_admin/error/'. $code. ']]'] ?? '' ) ) === '' || str_contains( (string) $words['[[/_admin/error/'. $code. ']]'], '%s' ) !== ( $code === 'design_taken' ) )
				$codeProblems[] = $code. ( $number === 0 ? ' de' : ' en' );

	check( 'the nine codes a person can cause each have their sentence in both languages under /_admin/error/<code> - the %s of a parameter in design_taken and nowhere else - and they are the codes the actions answered above'
		. ( $codeProblems === [] ? '' : ' - '. implode( ', ', $codeProblems ) ),
		$codeProblems === [] && array_diff( array_unique( $answeredCodes ), $codeWords ) === [] && array_diff( $codeWords, array_unique( $answeredCodes ) ) === [] );
	// The notes that carry a code are said the way a refusal is: their sentence is in both languages, with the %s of the params, as many as the sentence for a log has
	$noteProblems = [];

	foreach( Setup::NOTES as $code => $sentence )
		foreach( $themeWords as $number => $words )
			if( trim( (string) ( $words['[[/_admin/error/'. $code. ']]'] ?? '' ) ) === '' || substr_count( (string) $words['[[/_admin/error/'. $code. ']]'], '%s' ) !== substr_count( $sentence, '%s' ) )
				$noteProblems[] = $code. ( $number === 0 ? ' de' : ' en' );

	$noteShapes = [];

	foreach( Setup::NOTES as $code => $sentence ) {
		$made = Setup::note( $code, ...array_fill( 0, substr_count( $sentence, '%s' ), 'x' ) );

		if( array_keys( $made ) !== [ 'error', 'code', 'params' ] || $made['code'] !== $code || $made['params'] !== array_fill( 0, substr_count( $sentence, '%s' ), 'x' ) || str_contains( $made['error'], '%s' ) === true )
			$noteShapes[] = $code;
	}

	check( 'every note that carries a code has its sentence in both languages under /_admin/error/<code>, with as many %s as the English sentence for a log - and is made in the shape a refusal answers in: the sentence, the code, the params'
		. ( $noteProblems === [] && $noteShapes === [] ? '' : ' - '. implode( ', ', array_merge( $noteProblems, $noteShapes ) ) ), $noteProblems === [] && $noteShapes === [] && Setup::NOTES !== [] );
	check( 'a line of the log is one for each action that writes a theme, and none for what only reads; a slug that is none is not written as it came',
		Admin::log( 'design/theme/save', [ 'slug' => "x\nforged line" ] ) === 'Save Design theme ' && Admin::log( 'design/theme/delete', [ 'slug' => [ 'x' ] ] ) === 'Delete Design theme '
		&& Admin::log( 'design/theme/export', [ 'slug' => 'x' ] ) === '' && Admin::log( 'design/theme/load', [ 'slug' => 'x' ] ) === '' && Admin::log( 'design/list', [] ) === '' );


	// What the screen is drawn from, for design-js-smoke.js: the real answers of the actions above
	file_put_contents( $themesFile, json_encode( [
		'none' 			=> $listedNone, 'active' => $listedOwnActive, 'load' => $loaded, 'loadMissing' => $inkyListed, 'renamed' => $renamed, 'saved' => $resaved,
		'duplicated' => $copied, 'deleted' => $deleted, 'exported' => $exported, 'imported' => $imported,
	], JSON_THROW_ON_ERROR ) );

	// The sandbox as the sections after this one found it
	$themesClean();
	@unlink( $themeSetupFile );
	unset( $appData['./nino/filesystem/cache'][Setup::PATH] );

	if( $themeSetupKept !== null )
		file_put_contents( $themeSetupFile, $themeSetupKept );

	if( $themeFillsKept === null )
		unset( $appData['./nino/html/fills'] );
	else
		$appData['./nino/html/fills'] = $themeFillsKept;

	\Nino\Locales::useLocale( $appData, 'de_DE' );

	check( 'and the sandbox is as it was: no theme of the project, no directory for them, the setup as the sections before left it',
		Themes::own( $appData ) === [] && is_dir( $themesDir ) === false && ( $themeSetupKept === null ? is_file( $themeSetupFile ) === false : (string) file_get_contents( $themeSetupFile ) === $themeSetupKept ) );

	return $themesFile;
};

$themesFile = $themesSection( $appData, $library, $entryInter );

\Nino\Auth::logoutUser( $appData );
check( 'every action of the panel refuses a request with no session',
	callDesignAction( $appData, 'apiList' )[0] === 401
	&& callDesignAction( $appData, 'apiSave', [] )[0] === 401
	&& callDesignAction( $appData, 'apiApply' )[0] === 401
	&& callDesignAction( $appData, 'apiPlan' )[0] === 401
	&& callDesignAction( $appData, 'apiRestore' )[0] === 401
	&& callDesignAction( $appData, 'apiPreview', [] )[0] === 401
	&& callDesignAction( $appData, 'apiWebfontsList', [] )[0] === 401
	&& callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'inter' ] )[0] === 401
	&& callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => 'inter' ] )[0] === 401
	&& array_filter( [ 'apiThemeLoad', 'apiThemeRename', 'apiThemeSave', 'apiThemeDuplicate', 'apiThemeDelete', 'apiThemeExport', 'apiThemeImport' ], static fn( string $method ): bool => callDesignAction( $appData, $method, [ 'slug' => Themes::SHIPPED[0], 'name' => 'x' ] )[0] !== 401 ) === [] );

\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/elements/manage' ] );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );
check( '...and an account without /_admin/design/manage with a 403',
	callDesignAction( $appData, 'apiList' )[0] === 403 && callDesignAction( $appData, 'apiApply' )[0] === 403
	&& callDesignAction( $appData, 'apiPlan' )[0] === 403 && callDesignAction( $appData, 'apiRestore' )[0] === 403
	&& callDesignAction( $appData, 'apiPreview', [] )[0] === 403
	&& callDesignAction( $appData, 'apiSave', [] )[0] === 403
	&& callDesignAction( $appData, 'apiWebfontsList', [] )[0] === 403
	&& callDesignAction( $appData, 'apiWebfontsInstall', [ 'slug' => 'inter' ] )[0] === 403
	&& callDesignAction( $appData, 'apiWebfontsRemove', [ 'slug' => 'inter' ] )[0] === 403
	&& array_filter( [ 'apiThemeLoad', 'apiThemeRename', 'apiThemeSave', 'apiThemeDuplicate', 'apiThemeDelete', 'apiThemeExport', 'apiThemeImport' ], static fn( string $method ): bool => callDesignAction( $appData, $method, [ 'slug' => Themes::SHIPPED[0], 'name' => 'x' ] )[0] !== 403 ) === [] );

check( 'the panel names every action it answers', array_keys( Admin::actions() ) === [ 'design/list', 'design/save', 'design/apply', 'design/plan', 'design/restore', 'design/preview',
	'design/webfonts/list', 'design/webfonts/install', 'design/webfonts/remove',
	'design/theme/load', 'design/theme/save', 'design/theme/rename', 'design/theme/duplicate', 'design/theme/delete', 'design/theme/export', 'design/theme/import' ] );
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
	tab, a template and a viewport, an effect with its strength and speed, a font
	role with its columns and its families, and the name of every knob of every part
	with the note under it, which says which way the strip goes. The words of the
	positions are a choice's alone - the three of the stripes of a table and of the
	labels of a form - because a scale shows numbers, -2 to +2, and the root size and
	the width of the page are scales: a name and a note, nothing else. The model is
	the list, so a tab, a knob or a family added to it without its words fails here
	and not as a blank row on a screen - and a word that no longer belongs to anything
	fails as well	*/
$wanted = [];
$radiiAt = [];
foreach( Setup::TABS as $tab )
	$wanted[] = 'tab/'. $tab. '/label';
foreach( Setup::OBJECTS as $object )
	$wanted[] = 'object/'. $object. '/label';
foreach( array_keys( Setup::PARTS ) as $part )
	$wanted[] = 'part/'. $part; // not a label any more - the select of a template says label/template, the head of its block the object - but the word the note of a template that is not in the library names the part with (see _noteText() in admin.js)
array_push( $wanted, 'label/template', 'label/size', 'hint/size', 'label/pagewidth', 'hint/pagewidth', 'label/whitespace', 'hint/whitespace', 'label/radius', 'hint/radius', 'label/radius-alt', 'hint/radius-alt' );
foreach( [ 'phone', 'tablet', 'desktop' ] as $word )
	$wanted[] = 'width/'. $word;
foreach( [ 'current', 'drifted', 'missing', 'foreign' ] as $word )
	$wanted[] = 'state/short/'. $word;
array_push( $wanted, 'motion/default', 'motion/effect/label', 'motion/strength/label', 'motion/speed/label' );
foreach( array_keys( Setup::EFFECTS ) as $effect )
	$wanted[] = 'motion/effect/'. $effect;
foreach( Setup::STRENGTHS as $strength )
	$wanted[] = 'motion/strength/'. $strength;
foreach( array_keys( Setup::SPEEDS ) as $speed )
	$wanted[] = 'motion/speed/'. $speed;
array_push( $wanted, 'motion/hover/label', 'motion/hover-strength/label' );
foreach( array_keys( Setup::HOVERS ) as $hover )
	$wanted[] = 'motion/hover/'. $hover;
foreach( Setup::STRENGTHS as $strength )
	$wanted[] = 'motion/hover-strength/'. $strength;
foreach( Fonts::ROLES as $role )
	$wanted[] = 'font/role/'. $role;
foreach( [ 'family', 'weight', 'size' ] as $head )
	$wanted[] = 'font/head/'. $head;
foreach( Fonts::families( $appData ) as $slug => $family )
	if( $family['files'] === [] )
		$wanted[] = 'family/'. $slug;
array_push( $wanted, 'family/default', 'family/default-code', 'fontsize/unit' );
foreach( Fonts::WEIGHTS as $weight )
	$wanted[] = 'weight/'. $weight;

/*	The Typefaces tab names a category chip after each category the list accepts and an All, a step of the pager and
	a sentence for each thing a refusal of the list can be about (see Webfonts::REFUSALS); the sentences of an install,
	an update and a removal are the server's (see Admin::apiWebfontsInstall())	*/
foreach( array_merge( [ 'all' ], Webfonts::CATEGORIES ) as $category )
	$wanted[] = 'typefaces/category/'. $category;
foreach( [ 'previous', 'next' ] as $step )
	$wanted[] = 'typefaces/page/'. $step;
foreach( Webfonts::REFUSALS as $refusal )
	$wanted[] = 'typefaces/available/'. $refusal;
array_push( $wanted, 'typefaces/msg/installed', 'typefaces/msg/updated', 'typefaces/msg/removed' );

/*	The list of the themes names the buttons of a row by a variable, which is no literal for the check below to find, and the word of a
	copy is the server's: it names the theme a duplicate makes (see Admin::apiThemeDuplicate())	*/
array_push( $wanted, 'theme/load', 'theme/duplicate', 'theme/export', 'theme/delete', 'theme/edit', 'theme/copy' );

/*	The dialog before an apply names a file by what it is for - the stylesheet, and the frame of each part that has one - and what happens
	to it by the state it is in, both by a variable (see _fileWord() and _confirmFiles()): the states are those Compiler::ownership() answers,
	a file that is not there, and one that would stay as it is	*/
$wanted[] = 'confirm/file/look';

foreach( Setup::PARTS as $part => $kind )
	if( $kind === 'frame' )
		$wanted[] = 'confirm/file/'. $part;

foreach( [ 'missing', 'ours', 'edited', 'foreign', 'same' ] as $state )
	$wanted[] = 'confirm/state/'. $state;

preg_match( '/FACE_CATEGORIES\s*:\s*\[([^\]]*)\]/', (string) file_get_contents( __DIR__. '/../assets/admin.js' ), $chips );
check( 'the chips of the Typefaces tab are an All and the categories the list accepts, in the order the list names them',
	array_map( static fn( string $chip ): string => trim( $chip, " '" ), explode( ',', $chips[1] ?? '' ) ) === array_merge( [ 'all' ], Webfonts::CATEGORIES ) );

$choiceKnobs = [];

foreach( Setup::KNOBS as $part => $kinds )
	foreach( $kinds as $knob => $kind ) {

		array_push( $wanted, 'knob/'. $part. '/'. $knob. '/label', 'knob/'. $part. '/'. $knob. '/note' );

		if( $kind === 'choice' ) {
			$choiceKnobs[] = [ $part, $knob ];

			foreach( Setup::words( 'choice' ) as $word )
				$wanted[] = 'knob/'. $part. '/'. $knob. '/'. $word;
		}
	}

// The corners a kind of surface takes: a choice for each place of the table that has radii keys, named by the kind where the place has one and by the object where it has several - which also have a note that says the kinds differ - and the two words a choice is made of
foreach( Setup::LAYOUT as $key => $place )
	if( str_starts_with( $key, 'radii/' ) === true )
		$radiiAt[ implode( '/', $place ) ][] = substr( $key, strlen( 'radii/' ) );

foreach( $radiiAt as $place => $kinds ) {

	$wanted[] = 'radii/'. ( count( $kinds ) === 1 ? $kinds[0] : explode( '/', $place )[1] );

	if( count( $kinds ) > 1 )
		$wanted[] = 'hint/radii';
}

foreach( Setup::RADIUS_WORDS as $word )
	$wanted[] = 'radii/'. $word;

foreach( Colours::choices() as $knob => $meta ) {
	// A scale or a choice has a note under its name; the mode is a select under a heading, which has none
	$wanted[] = 'colour/'. $knob. '/label';

	if( $knob !== 'mode' )
		$wanted[] = 'colour/'. $knob. '/note';

	// ...and only a choice has a word for each of its positions: the positions of a scale are numbers
	if( $meta['kind'] === 'choice' )
		foreach( $meta['steps'] as $position => $step )
			$wanted[] = 'colour/'. $knob. '/'. ( $knob === 'mode' ? $step : $position + 1 );
}

// ...the surfaces a ground a feature brings is chosen from, the tones of an ink, and the two halves of a pair of colours
foreach( Colours::SURFACES as $surface )
	$wanted[] = 'ground/'. $surface;

foreach( array_keys( Setup::INKS ) as $tone )
	$wanted[] = 'ink/'. $tone;

array_push( $wanted, 'label/light', 'label/dark' );

$missingWords = [];
foreach( [ 'en_US' => $textEn, 'de_DE' => $textDe ] as $wordLocale => $wordKeys )
	foreach( $wanted as $word )
		if( in_array( '[[/_admin/design/'. $word. ']]', $wordKeys, true ) === false )
			$missingWords[] = $wordLocale. ' '. $word;

check( 'the texts have a word for everything the model draws by name ('. count( $wanted ). ' of them, in two languages): a label and a note for every knob, the three words of a choice only, and for the palette a label and a note for every knob and one word per position for a choice'
	. ( $missingWords === [] ? '' : ' - missing: '. implode( ', ', array_slice( $missingWords, 0, 6 ) ) ), $missingWords === [] && count( $choiceKnobs ) === 10 );

// ...and nothing else: a key is either one of those, or a literal the script names, or the one page title the server renders
$accounted = array_merge( $wanted, array_values( array_unique( $scriptKeys[1] ) ), [ 'preview/page' ] );
$leftover = array_filter( $textEn, static fn( string $key ): bool => str_starts_with( $key, '[[/_admin/design/' ) === true
	&& in_array( substr( $key, strlen( '[[/_admin/design/' ), -2 ), $accounted, true ) === false );

check( '...and no word is left over that nothing draws: every key is one of those, a literal the script names, or the page title of the preview'
	. ( $leftover === [] ? '' : ' - left over: '. implode( ', ', array_slice( $leftover, 0, 6 ) ) ), $leftover === [] );

/*	The convention of the owner (3.13, point 6): the note under the label of a scale is the direction, left to right, with U+2194 between
	the two ends. A choice keeps a note of its own. What the labels and the words are is the owner's to say, and the checks read them by
	their keys: they hold a text to its shape - the arrow of a note, the capital of a word - and never to a word	*/
$arrow				= "\u{2194}";
$undirected		= [];

foreach( [ 'en_US', 'de_DE' ] as $stepLocale ) {

	foreach( Setup::KNOBS as $part => $kinds )
		foreach( $kinds as $knob => $kind ) {

			$note = $panelWord( 'knob/'. $part. '/'. $knob. '/note', $stepLocale );

			if( substr_count( $note, ' '. $arrow. ' ' ) !== ( $kind === 'scale' ? 1 : 0 ) )
				$undirected[] = $stepLocale. ' '. $part. '/'. $knob. ' "'. $note. '"';
		}

	foreach( [ 'hint/size', 'hint/pagewidth', 'hint/radius', 'hint/radius-alt', 'colour/saturation/note', 'colour/contrast/note', 'colour/depth/note' ] as $key )
		if( substr_count( $panelWord( $key, $stepLocale ), ' '. $arrow. ' ' ) !== 1 )
			$undirected[] = $stepLocale. ' '. $key;
}

check( 'the note under a scale says which way it goes, with U+2194 between the two ends - the strips of the page and the three scales of Colours as well, and a choice has a note without one'
	. ( $undirected === [] ? '' : ' - not so for '. implode( ', ', array_slice( $undirected, 0, 4 ) ) ), $undirected === [] );

/*	The words of the choices. The three words of a choice knob - the keys under /_admin/design/knob/<tab>/<knob>/ that are
	less, default and more - begin with a capital letter in both languages, as the words of the choices of the Colours view do
	(/_admin/design/colour/<knob>/<n>). Only the first letter of a word is the point of it: nothing else of the word
	changed, and the options of a select stay as they are, in lower case - the mode of the palette, as the Colours view keeps
	its select lower case and capitalises its choices. The words of a scale are gone: it shows numbers. And the one middle
	word of the model before - Normal in German and Default in English, for every knob but the choices - has nothing left to
	check: the only knobs that still have words are the ten choices, each with a middle of its own	*/
$initialOf	= static fn( string $word ): string => mb_substr( $word, 0, 1 );
$isCapital	= static fn( string $word ): bool => $word !== '' && $initialOf( $word ) === mb_strtoupper( $initialOf( $word ) ) && $initialOf( $word ) !== mb_strtolower( $initialOf( $word ) );
$isLower		= static fn( string $word ): bool => $word !== '' && $initialOf( $word ) === mb_strtolower( $initialOf( $word ) ) && $initialOf( $word ) !== mb_strtoupper( $initialOf( $word ) );
$stepsOf		= static fn( string $part, string $knob, string $locale ): array => array_map( static fn( string $step ): string => $panelWord( 'knob/'. $part. '/'. $knob. '/'. $step, $locale ), Setup::words( 'choice' ) );

$stepWords		= [ 'en_US' => [], 'de_DE' => [] ];
$uncapital		= [];

foreach( [ 'en_US', 'de_DE' ] as $stepLocale )
	foreach( $choiceKnobs as [ $part, $knob ] )
		foreach( $stepsOf( $part, $knob, $stepLocale ) as $at => $word ) {

			$stepWords[$stepLocale][] = $word;

			if( $isCapital( $word ) === false )
				$uncapital[] = $stepLocale. ' '. $part. '/'. $knob. '/'. Setup::words( 'choice' )[$at]. ' "'. $word. '"';
		}

check( 'every word of a choice begins with a capital letter, in both languages: ten knobs of three words, thirty in each'
	. ( $uncapital === [] ? '' : ' - not so for '. implode( ', ', array_slice( $uncapital, 0, 4 ) ) ),
	$uncapital === [] && count( $stepWords['en_US'] ) === 30 && count( $stepWords['de_DE'] ) === 30 );

$scaleWords = [];

foreach( Setup::KNOBS as $part => $kinds )
	foreach( $kinds as $knob => $kind )
		if( $kind === 'scale' )
			foreach( [ 'en_US', 'de_DE' ] as $stepLocale )
				foreach( Setup::STEPS as $step )
					if( $panelWord( 'knob/'. $part. '/'. $knob. '/'. $step, $stepLocale ) !== '' )
						$scaleWords[] = $stepLocale. ' '. $part. '/'. $knob. '/'. $step;

check( 'a scale has no word for a position in either language: twenty-nine knobs with a name and a note, and none of the 145 words of five steps left - a strip of five shows -2 to +2'
	. ( $scaleWords === [] ? '' : ' - still there: '. implode( ', ', array_slice( $scaleWords, 0, 4 ) ) ), $scaleWords === [] && $scales === 29 );

$toggleWords	= [];
$toggleBare		= [];

foreach( $toggles as $at )
	foreach( [ 'en_US', 'de_DE' ] as $stepLocale ) {

		foreach( Setup::STEPS as $step )
			if( $panelWord( 'knob/'. $at. '/'. $step, $stepLocale ) !== '' )
				$toggleWords[] = $stepLocale. ' '. $at. '/'. $step;

		if( $panelWord( 'knob/'. $at. '/label', $stepLocale ) === '' || $panelWord( 'knob/'. $at. '/note', $stepLocale ) === '' )
			$toggleBare[] = $stepLocale. ' '. $at;
	}

check( 'a toggle has a name and a note in each language and no word for a position - the switch says on and off itself, in the workbench\'s words: seven knobs, and the label of each is another word in German than in English'
	. ( $toggleWords === [] && $toggleBare === [] ? '' : ' - '. implode( ', ', array_slice( array_merge( $toggleWords, $toggleBare ), 0, 4 ) ) ),
	$toggleWords === [] && $toggleBare === [] && count( $toggles ) === 7
	&& array_filter( $toggles, static fn( string $at ): bool => $panelWord( 'knob/'. $at. '/label', 'en_US' ) === $panelWord( 'knob/'. $at. '/label', 'de_DE' ) ) === []
	&& array_filter( $toggles, static fn( string $at ): bool => $panelWord( 'knob/'. $at. '/note', 'en_US' ) === $panelWord( 'knob/'. $at. '/note', 'de_DE' ) ) === [] );

/*	The Colours view: the choices have names, each capitalised, and the three scales say nothing of their positions - a strip of five shows
	numbers, the 0 the framework's own. The names the server publishes for the choices (Colours::choices(), the last fallback of
	the panel) are the English words of the text file	*/
$colourChoices = array_filter( array_diff_key( $choices, [ 'mode' => 1 ] ), static fn( array $knob ): bool => $knob['kind'] === 'choice' );
$colourDrawn = [];

foreach( [ 'en_US', 'de_DE' ] as $stepLocale )
	foreach( $colourChoices as $knob => $choice )
		$colourDrawn[$stepLocale][$knob] = array_map( static fn( int $position ): string => $panelWord( 'colour/'. $knob. '/'. ( $position + 1 ), $stepLocale ), array_keys( $choice['steps'] ) );

$colourScaleWords = [];

foreach( [ 'en_US', 'de_DE' ] as $stepLocale )
	foreach( [ 'saturation', 'contrast', 'depth' ] as $knob )
		foreach( [ 1, 2, 3, 4, 5 ] as $position )
			if( $panelWord( 'colour/'. $knob. '/'. $position, $stepLocale ) !== '' )
				$colourScaleWords[] = $stepLocale. ' '. $knob. '/'. $position;

check( 'the Colours view names the positions of its two choices, each word capitalised, in both languages - and the three scales have none',
	array_keys( $colourChoices ) === [ 'harmony', 'temperature' ] && array_keys( $colourDrawn ) === [ 'en_US', 'de_DE' ]
	&& array_filter( $colourDrawn, static fn( array $knobs ): bool => array_filter( array_merge( ...array_values( $knobs ) ), static fn( string $word ): bool => $isCapital( $word ) === false ) !== [] ) === []
	&& $colourScaleWords === [] );
check( '...and the names Colours::choices() publishes for the positions of a choice are the English words of the text: the same, position for position - the last fallback of the panel says what the text says',
	array_map( static fn( array $knob ): array => $knob['steps'], $colourChoices ) === $colourDrawn['en_US'] );

$selectWords	= [];

foreach( $choices['mode']['steps'] as $mode )
	$selectWords[] = 'colour/mode/'. $mode;

$notLower = [];

foreach( [ 'en_US', 'de_DE' ] as $stepLocale )
	foreach( $selectWords as $key )
		if( $isLower( $panelWord( $key, $stepLocale ) ) === false )
			$notLower[] = $stepLocale. ' '. $key. ' "'. $panelWord( $key, $stepLocale ). '"';

check( 'the options of a select stay lower case: the mode of the palette on Colours - three words in each language, and the sizes and widths of the page are no select any more'
	. ( $notLower === [] ? '' : ' - not so for '. implode( ', ', array_slice( $notLower, 0, 4 ) ) ), $notLower === [] && count( $selectWords ) === 3 );

/*	What the screen stopped drawing took its words with it: the card of the state
	- how the files stand, and when they were last applied - the note under the
	templates and the heading over the knobs; and the sentences nobody read: the one
	under the two buttons, and the ones under the headings of the blocks - the fonts,
	the animation, the tuning and the mode of the palette - with the two words only the
	summary under the frame said a size with, its "default" and its decimal sign. The
	one word a state still has is the short one, on the chip in the action bar, and the
	label of it is the chip's title; the date of the previous version is the title of
	the button that restores it	*/
$gone = [ 'hint/templates', 'label/template-of', 'hint/knobs', 'state/compiled', 'state/current', 'state/drifted', 'state/missing', 'state/foreign',
	'hint/actions', 'hint/fonts', 'hint/motion', 'hint/tuning', 'colour/mode/note', 'fontsize/default', 'fontsize/decimal',
	'size/', 'pagewidth/', 'knob/buttons/small', 'knob/buttons/big' ];
$stillThere = array_filter( array_merge( $textEn, $textDe ), static fn( string $key ): bool => array_filter( $gone, static fn( string $part ): bool => str_contains( $key, '[[/_admin/design/'. $part ) === true ) !== [] );
check( 'the words of the state card, of the note under the templates and of the heading over the knobs are gone from both files - and so are those of the hint under the two buttons, of the sentences under the headings of the blocks and of the summary'
	. ( $stillThere === [] ? '' : ' - still there: '. implode( ', ', array_slice( $stillThere, 0, 4 ) ) ), $stillThere === []
	&& in_array( '[[/_admin/design/state/previous]]', $textEn, true ) === true && in_array( '[[/_admin/design/label/state]]', $textEn, true ) === true
	&& in_array( '[[/_admin/design/fontsize/unit]]', $textEn, true ) === true && in_array( '[[/_admin/design/hint/size]]', $textEn, true ) === true && in_array( '[[/_admin/design/hint/pagewidth]]', $textEn, true ) === true );

/*	The stylesheet of the panel is no test's business as a rule - what it looks
	like is for a browser to say - but the owner's list asked for measures, and a
	measure that goes back to what it was is a change nothing else would see. The
	rules are read with their comments out and their white space squeezed, and a
	declaration is looked for by its words	*/
echo "\nThe panel's stylesheet: what the owner's list asked of it\n";

$panelCss = (string) preg_replace( '/\s+/', ' ', (string) preg_replace( '~/\*.*?\*/~s', '', (string) file_get_contents( __DIR__. '/../assets/admin.css' ) ) );
$cssRule = static fn( string $selector ): string
	=> preg_match( '/(?:^|[{}] )'. preg_quote( $selector, '/' ). ' \{ ([^}]*) \}/', $panelCss, $found ) === 1 ? $found[1] : '';
$cssHas = static fn( string $selector, array $declarations ): bool
	=> array_filter( $declarations, static fn( string $declaration ): bool => str_contains( ' '. $cssRule( $selector ), ' '. $declaration ) === false ) === [];

check( 'the fonts table is three cells to a row - the family, the weight and the size: the switches of the case are the knob rows of the objects, whose label is set in the type a strip\'s label has',
	$cssRule( '.nino-admin .design-fonts tr' ) !== '' && substr_count( $cssRule( '.nino-admin .design-fonts tr' ), 'grid-template-columns: minmax(0, 1.5fr) 6rem minmax(0, 1fr);' ) === 1
	&& $cssHas( '.nino-admin .design-knob-row--switch .nino-admin-switch-copy', [ 'font-size: .8rem;', 'font-weight: 500;' ] ) === true
	&& $cssHas( '.nino-admin .design-knob-row--switch .nino-admin-switch', [ 'min-height: 0;', 'padding: 0;' ] ) === true );
check( 'the stylesheet takes no row of the panel by the place the table gives it - the id of a row is its tab and its object - but the rows of the palette by the class the script puts on them, and the second colour by what it is: where a control stands is for the table to say',
	str_contains( $panelCss, '#design-row-' ) === false
	&& $cssHas( '.nino-admin .design-colour-knob .design-knob-steps .nino-admin-tab', [ 'padding: .3rem .2rem;', 'font-size: .7rem;' ] ) === true
	&& $cssHas( '.nino-admin .design-knob-row > .design-colour-second', [ 'grid-column: 2;', 'grid-row: 1;', 'justify-self: end;' ] ) === true );
check( 'the label of a knob is a row of the icon and the box beside it, centred, a gap of .4rem - and smaller and lighter than it was: .8rem at weight 500, with the note under it at .75rem in the secondary colour',
	$cssHas( '.nino-admin .design-knob-label', [ 'display: flex;', 'align-items: center;', 'gap: .4rem;', 'font-size: .8rem;', 'font-weight: 500;' ] ) === true
	&& $cssHas( '.nino-admin .design-knob-label small', [ 'display: block;', 'font-size: .75rem;', 'color: var(--editor-text-secondary);' ] ) === true
	&& str_contains( $cssRule( '.nino-admin .design-knob-label' ), 'font-weight: 650' ) === false );
check( '...the icon is a square of a rem, drawn with a stroke of 2 in the colour of the text round it, rounded, unfilled, in the secondary colour',
	$cssHas( '.nino-admin .design-icon', [ 'width: 1rem;', 'height: 1rem;', 'fill: none;', 'stroke: currentColor;', 'stroke-width: 2;', 'stroke-linecap: round;', 'stroke-linejoin: round;', 'color: var(--editor-text-secondary);' ] ) === true );
check( 'the rows stand closer: a knob row has .35rem above and below and .25rem between its parts, a card .55rem under it, a font role .35rem above and below',
	$cssHas( '.nino-admin .design-knob-row', [ 'gap: .25rem;', 'padding: .35rem 0;' ] ) === true
	&& $cssHas( '.nino-admin .design-form-section, .nino-admin .design-block', [ 'margin: 0 0 .55rem;' ] ) === true
	&& $cssHas( '.nino-admin .design-fonts tbody tr', [ 'padding: .35rem 0;' ] ) === true );
check( '...and so do the blocks of a card: the grid of fields, the label over a block of rows',
	$cssHas( '.design-form-grid', [ 'gap: .5rem .75rem;' ] ) === true
	&& $cssHas( '.nino-admin .design-section-label', [ 'margin: .65rem 0 .35rem;', 'padding-top: .55rem;' ] ) === true );
check( 'the way back to the previous version stands beside Reset in the same quiet look and on the same side of the bar: the two take the start of the bar, the button that saves takes the rest of the room',
	$cssHas( '.nino-admin .nino-admin-actionbar :is(.design-reset-all, #design-restore)', [ 'order: -3;', 'background: none;', 'color: var(--editor-text-secondary);' ] ) === true
	&& str_contains( $cssRule( '.nino-admin .nino-admin-actionbar :is(.design-reset-all, #design-restore)' ), 'margin-right' ) === false
	&& $cssHas( '.nino-admin .nino-admin-actionbar #design-save', [ 'margin-left: auto;' ] ) === true );

/*	What the panel says stands in the bar with the buttons that say it: the line of what was done, which the
	workbench's bar takes for its status line - and the chip for the state of the files beside it. The line is one
	line, and the room it has is what the chip, the mark of unsaved input and the buttons leave	*/
check( 'the line the panel says things in is one line that gives its room up first: it grows from nothing, is cut by an ellipsis where the bar has less room than it has words, and a failure in it is red - the workbench\'s rule for the status line is the more specific, so the red is said again',
	$cssHas( '.nino-admin .nino-admin-actionbar > #design-msg', [ 'flex: 1 1 0;', 'overflow: hidden;', 'text-overflow: ellipsis;', 'white-space: nowrap;' ] ) === true
	&& $cssHas( '.nino-admin .nino-admin-actionbar > #design-msg.nino-admin-error', [ 'color: var(--editor-red);' ] ) === true );
check( '...a bar that has no room for all of it is rows and not an overflow, and the chip is a pill that keeps its word - ordered as the line is, so that the mark of unsaved input the workbench adds at the end of the bar does not part the two, a line, a round border, small type in the secondary colour and no shrinking, which the line is there for - green while the files are up to date and orange while a saved draft waits for an apply',
	$cssHas( '.nino-admin .nino-admin-actionbar:has(> #design-msg)', [ 'flex-wrap: wrap;' ] ) === true
	&& $cssHas( '.nino-admin .nino-admin-actionbar > .design-state-word', [ 'order: -1;', 'flex: none;', 'border: 1px solid var(--editor-border);', 'border-radius: 999px;', 'color: var(--editor-text-secondary);', 'font-size: .72rem;', 'white-space: nowrap;' ] ) === true
	&& $cssHas( '.nino-admin .nino-admin-actionbar > .design-state-word[data-state="current"]', [ 'color: var(--editor-green);' ] ) === true
	&& $cssHas( '.nino-admin .nino-admin-actionbar > .design-state-word[data-state="drifted"]', [ 'color: var(--editor-orange);' ] ) === true );
check( '...and the mark of unsaved input the workbench writes into the bar keeps its words on one line, where it is not a row of its own: from the width the workbench\'s phone rule ends at',
	preg_match( '/@media \(min-width: 38\.001rem\) \{ ([^{}]+\{[^}]*\} )\}/', $panelCss, $markRules ) === 1
	&& str_contains( $markRules[1], '.nino-admin .nino-admin-actionbar:has(> #design-msg) > .nino-admin-actionbar-dirty { flex: none; white-space: nowrap; }' ) === true );

/*	On a phone - the rules inside the media query of 38rem - the bar is rows. The first is the line and the chip: the
	workbench hides its status line there, and this one is shown, the line taking the room the chip leaves and the chip
	cut by an ellipsis like the line is. The way back to the previous version takes a row above the buttons: alone while Reset
	is hidden, and while Reset is shown the two share that row, Restore at its own width and Reset taking the room that is
	left, which puts the pair at the start of the row. Save theme, which the bar has while the views are open and a theme of the
	project is loaded, takes the quiet row with them. The bar is then the row of the line, the row of the quiet ones, the
	row of the kernel's mark of unsaved input and the row of Save draft and Apply - never a fifth	*/
$phoneCss = preg_match( '/@media \(max-width: 38rem\) \{ ((?:[^{}]+\{[^}]*\} )*)\}/', $panelCss, $phoneRules ) === 1 ? $phoneRules[1] : '';
$onPhone = static fn( string $selector, array $declarations ): bool
	=> str_contains( $phoneCss, $selector. ' { ' ) === true && $cssHas( $selector, $declarations ) === true;
// A selector the stylesheet has outside the media query as well: the rule that is inside it, and no other
$phoneRule = static fn( string $selector ): string
	=> preg_match( '/(?:^|[{}] )'. preg_quote( $selector, '/' ). ' \{ ([^}]*) \}/', $phoneCss, $found ) === 1 ? $found[1] : '';
$phoneHas = static fn( string $selector, array $declarations ): bool
	=> array_filter( $declarations, static fn( string $declaration ): bool => str_contains( ' '. $phoneRule( $selector ), ' '. $declaration ) === false ) === [];

check( 'on a phone the line and the chip are the first row of the bar: the line, shown where the workbench hides its status line, takes the room the chip leaves, and the chip is cut by an ellipsis like the line is - both before everything else',
	$phoneHas( '.nino-admin .nino-admin-actionbar > #design-msg', [ 'display: block;', 'order: -4;', 'flex: 1 1 calc(100% - 10.4rem);' ] ) === true
	&& $phoneHas( '.nino-admin .nino-admin-actionbar > .design-state-word', [ 'order: -4;', 'flex: 0 1 auto;', 'max-width: 10rem;', 'overflow: hidden;', 'text-overflow: ellipsis;' ] ) === true );
check( 'on a phone the way back to the previous version takes a row above the buttons - alone while Reset is hidden, and beside Reset at its own width while that is shown, Reset taking the room left over so that the pair stands at the start: the bar is four rows at the most',
	$onPhone( '.nino-admin .nino-admin-actionbar > #design-restore', [ 'flex: 1 0 100%;' ] ) === true
	&& $onPhone( '.nino-admin .nino-admin-actionbar > #design-restore:has(~ #design-reset:not([hidden]))', [ 'flex: 0 0 auto;' ] ) === true
	&& $onPhone( '.nino-admin .nino-admin-actionbar > .design-reset-all', [ 'margin-right: auto;' ] ) === true );
check( 'on a phone Save theme stands with the quiet buttons - the row of the previous version and Reset - and not in the row of Save draft and Apply, which three buttons of that length would make a fifth row of: its order is the one of the quiet ones, and only on a phone',
	$onPhone( '.nino-admin .nino-admin-actionbar > #design-theme-save', [ 'order: -3;' ] ) === true && str_contains( str_replace( $phoneRules[0] ?? "\0", '', $panelCss ), '#design-theme-save' ) === false );
$outsidePhone = str_replace( $phoneRules[0] ?? "\0", '', $panelCss );
check( '...and only on a phone: nothing of it stands outside the media query, where the bar is one line and Save takes the free room',
	$phoneCss !== '' && str_contains( $outsidePhone, ':has(> #design-restore)' ) === false && str_contains( $outsidePhone, '> #design-restore' ) === false
	&& str_contains( $outsidePhone, '> .design-reset-all' ) === false && str_contains( $outsidePhone, '~ #design-reset' ) === false );
check( 'the rules of what the screen stopped drawing are gone: the state card and its warning - not the chip for the state of the files, which is a rule of its own - the line that dated the previous version, the sentence under the templates - and the eyebrow rule stays, the preview\'s title has one',
	preg_match( '/design-state(?!-word)/', $panelCss ) !== 1 && str_contains( $panelCss, 'design-previous' ) === false && str_contains( $panelCss, 'design-note' ) === false
	&& $cssHas( '.nino-admin .design-eyebrow', [ 'display: block;' ] ) === true );
check( 'nothing is left of what stood under the frame and over the bar: no rule for the summary - the list, a row of it, its label or its value - none for the hint under the two buttons, and none for the small under the title of a block, which has the rest of its measures',
	str_contains( $panelCss, 'design-summary' ) === false && str_contains( $panelCss, 'design-actions-hint' ) === false && str_contains( $panelCss, '.design-section-label small' ) === false
	&& $cssHas( '.nino-admin .design-section-label', [ 'margin: .65rem 0 .35rem;', 'padding-top: .55rem;', 'border-top: 1px solid var(--editor-separator);' ] ) === true
	&& $cssHas( '.nino-admin .design-section-label strong', [ 'display: flex;', 'align-items: center;', 'gap: .4rem;', 'font-size: .82rem;', 'font-weight: 650;' ] ) === true
	&& str_contains( $cssRule( '.nino-admin .design-section-label' ), 'display: grid' ) === false );
check( 'the stage has no height of its own in the stylesheet - the window gives it, and admin.js sets it - and no clamp on the box: what it keeps is the rest of its rules, the port the frame is scaled into, clipped, a line round it and the radius',
	str_contains( $cssRule( '.design-stage' ), 'height' ) === false && str_contains( $panelCss, 'clamp(' ) === false && str_contains( $panelCss, '44vh' ) === false
	&& $cssHas( '.design-stage', [ 'position: relative;', 'overflow: hidden;', 'border: 1px solid var(--editor-border);', 'border-radius: var(--editor-radius-sm);' ] ) === true );
check( '...and the pane it is in is the height of the window less the bar - 100vh less the declared height of the action bar, and the half rem the bottom of its padding goes under it by - where it sticks, no room of the summary that stood under the frame reckoned in it',
	$cssHas( '#design-preview', [ 'position: sticky;', 'top: 0;', 'max-height: calc(100vh - var(--nino-admin-actionbar-height) + .5rem);', 'overflow-y: auto;' ] ) === true
	&& str_contains( $panelCss, '5.5rem' ) === false );

/*	The head of the preview: the heading and the bar are the two sides of one row, and the bar goes under
	the heading only where the pane has no room for both - nothing in the stylesheet is a width. What the
	preview says is not one either: its chip lies over the top corner of the frame, in the stage, where it
	takes no room of any row - so neither the head nor the frame moves when the line comes and goes, and
	the heading is the eyebrow and the title alone. The rules are what is held here - where the line
	breaks is for a browser to measure: at a window of 1280px the pane is 555px and the two stand side by
	side, in either language, with the reload drawn as an icon like the devices - and at a window of 390px, where the bar is wider than the
	pane in German, the bar takes the room the pane has and wraps its controls: the page is as wide as the window and does not scroll sideways	*/
check( 'the head of the preview is one row: a flex container that wraps, its two sides at the bottom edge and spread to the ends, .7rem apart - the heading shrinks to its title, and the bar to the room the pane has: it takes the width its controls need and may shrink below it, so that it wraps them where the pane is the narrower',
	$cssHas( '.nino-admin .design-preview-head', [ 'display: flex;', 'flex-wrap: wrap;', 'align-items: flex-end;', 'justify-content: space-between;', 'gap: .7rem;' ] ) === true
	&& $cssHas( '.nino-admin .design-preview-heading', [ 'flex: 0 1 auto;', 'min-width: 0;' ] ) === true && str_contains( $cssRule( '.nino-admin .design-preview-heading' ), 'display: flex' ) === false
	&& $cssHas( '.nino-admin .design-preview-head > .design-preview-bar', [ 'flex: 0 1 auto;' ] ) === true );
check( '...and the chip the preview says things in lies over the frame and takes no room: absolute at the top corner of the stage, above the frame, one line cut by an ellipsis where the frame is narrower, letting the pointer through - and gone when empty',
	$cssHas( '.nino-admin #design-preview-msg', [ 'position: absolute;', 'top: .5rem;', 'right: .5rem;', 'z-index: 2;', 'max-width: calc(100% - 1rem);', 'white-space: nowrap;', 'overflow: hidden;', 'text-overflow: ellipsis;', 'pointer-events: none;', 'border-radius: 999px;' ] ) === true
	&& $cssHas( '.nino-admin #design-preview-msg:empty', [ 'display: none;' ] ) === true && $cssHas( '.design-stage', [ 'position: relative;' ] ) === true
	&& str_contains( $cssRule( '.nino-admin #design-preview-msg' ), 'flex' ) === false );
check( '...and the reload is drawn as the devices are, a step apart from them',
	$cssHas( '.nino-admin .design-reload', [ 'margin-left: .25rem;' ] ) === true );
check( '...and the bar is a row of its own that wraps, with no fixed width in it: the select of the width and the rule that gave it 9.5rem are gone',
	$cssHas( '.nino-admin .design-preview-bar', [ 'display: flex;', 'flex-wrap: wrap;', 'align-items: center;', 'gap: .6rem;', 'margin: 0;' ] ) === true
	&& str_contains( $panelCss, 'design-preview-bar .design-field' ) === false && str_contains( $panelCss, '9.5rem' ) === false );
/*	The viewport switch is the Builder's, in this feature's names: the two features share no stylesheet, so
	the rules are copies and this is what keeps them whole	*/
check( 'the viewport switch has the look of the Builder\'s: a group of buttons .25rem apart, each an icon beside a gap of .35rem in a box of .2rem by .6rem with a line, rounded as the workbench rounds, flat and transparent - and the lit one in the blue',
	$cssHas( '.design-viewports', [ 'display: flex;', 'gap: .25rem;' ] ) === true
	&& $cssHas( '.design-viewport', [ 'display: inline-flex;', 'align-items: center;', 'justify-content: center;', 'gap: .35rem;', 'padding: .2rem .6rem;', 'border: 1px solid var(--editor-border);',
		'border-radius: var(--editor-radius-sm);', 'background: transparent;', 'box-shadow: none;', 'color: var(--editor-text-secondary);', 'font-size: .74rem;', 'cursor: pointer;' ] ) === true
	&& $cssHas( '.design-viewport.is-active', [ 'border-color: var(--editor-blue);', 'background: var(--editor-blue-soft);', 'color: var(--editor-text);' ] ) === true );
check( '...its icon is the icon of the rows, taking the colour of the button it stands on so that the lit one is lit - and the switch beside it is compact: no margin, no padding, no border, its words at the middle of the track',
	$cssHas( '.nino-admin .design-viewport .design-icon', [ 'color: inherit;' ] ) === true
	&& $cssHas( '.nino-admin .design-preview-follow', [ 'align-items: center;', 'margin: 0;', 'padding: 0;', 'border: 0;' ] ) === true );

/*	...and every control of the bar over the preview - the three devices and the reload, which are buttons of one class, and the switch that lets the frame follow - is a hit
	area of 24px at least each way, and of 44px where the pointer is a finger (the workbench's own size for a control under it): a minimum of none, which took the workbench's
	height off them for the sake of the look, leaves a box of what its icon asks for	*/
preg_match_all( '/@media \(pointer: coarse\) \{((?:[^{}]|\{[^}]*\})*)\}/', $panelCss, $coarseBlocks );
$coarseCss = ' '. implode( ' ', $coarseBlocks[1] );
$pixels = static function( string $rule, string $property ): float {
	return preg_match( '/(?:^| )'. $property. ': ([0-9.]+)px;/', $rule, $found ) === 1 ? (float) $found[1] : 0.0;
};
$coarseRule = static fn( string $selector ): string
	=> preg_match( '/(?:^| )'. preg_quote( $selector, '/' ). ' \{ ([^}]*) \}/', $coarseCss, $found ) === 1 ? $found[1] : '';

check( 'the controls of the bar over the preview are a hit area of 24px at least each way - the devices and the reload by class, the follow switch by height - and of 44px where the pointer is coarse',
	$pixels( $cssRule( '.design-viewport' ), 'min-height' ) >= 24 && $pixels( $cssRule( '.design-viewport' ), 'min-width' ) >= 24
	&& $pixels( $coarseRule( '.design-viewport' ), 'min-height' ) >= 44 && $pixels( $coarseRule( '.design-viewport' ), 'min-width' ) >= 44
	&& $pixels( $cssRule( '.nino-admin .design-preview-follow' ), 'min-height' ) >= 24 && $pixels( $coarseRule( '.nino-admin .design-preview-follow' ), 'min-height' ) >= 44 );

/*	The blocks of the accordion on every view the table draws (see admin.js): the head is a button the width of its card and a hit area of 44px at the least, whatever the
	pointer is, and what it says of a closed block - the controls that stand off the defaults - is one line that the card cuts with an ellipsis where the head is short of
	room, never a second line; the chevron is turned over while the block is open	*/
check( 'the head of a block of the accordion is the width of its card and 44px high at the least and draws the hover of the workbench itself, the line it says of a closed block is cut with an ellipsis and never wraps, and the chevron is turned over while the block is open',
	$cssHas( '.nino-admin .design-accordion-head', [ 'display: flex;', 'align-items: center;', 'width: 100%;' ] ) === true
	&& $cssHas( '.nino-admin .design-accordion-head:hover', [ 'background: var(--editor-blue-soft);' ] ) === true
	&& $pixels( $cssRule( '.nino-admin .design-accordion-head' ), 'min-height' ) >= 44
	&& $cssHas( '.nino-admin .design-accordion-summary', [ 'flex: 1 1 0;', 'min-width: 0;', 'overflow: hidden;', 'text-overflow: ellipsis;', 'white-space: nowrap;' ] ) === true
	&& $cssHas( '.nino-admin .design-accordion-head[aria-expanded="true"] .design-accordion-chevron', [ 'transform: rotate(180deg);' ] ) === true );

/*	The list of the themes: the list is rows, each the name over its description and at its far end the buttons that can be pressed on it,
	which stand under the words where the room is short and always from 768px down; the row that makes a theme is a field and its
	button on one line; the css, which is the view CSS of the setup, is a tall field in the monospace face of the workbench, whose label is kept for whoever hears the field
	and drawn out of sight. The chips are the workbench's own, so not one colour in the rules of the tab is a literal	*/
preg_match_all( '/[^{}]*design-theme[^{}]*\{[^}]*\}/', $panelCss, $themeRules );
$themeCss = implode( '', $themeRules[0] );
$onNarrow = preg_match( '/@media \(max-width: 48rem\) \{ ((?:[^{}]+\{[^}]*\} )*)\}/', $panelCss, $narrowRules ) === 1 ? $narrowRules[1] : '';

check( 'the list of the themes is rows, one to a theme: the words and the buttons on a line that wraps, the words starting from 16rem and taking the room that is left - which in the third of the screen the column has puts the buttons of every row under its words - and a rule between two rows; the name bold, the description small and in the secondary colour',
	$cssHas( '.nino-admin .design-theme-row', [ 'display: flex;', 'flex-wrap: wrap;', 'align-items: center;', 'justify-content: space-between;' ] ) === true
	&& $cssHas( '.nino-admin .design-theme-row + .design-theme-row', [ 'border-top: 1px solid var(--editor-separator);' ] ) === true && $cssHas( '.nino-admin .design-theme-text', [ 'flex: 1 1 16rem;', 'min-width: 0;' ] ) === true
	&& $cssHas( '.nino-admin .design-theme-name', [ 'font-weight: 650;' ] ) === true && $cssHas( '.nino-admin .design-theme-description', [ 'color: var(--editor-text-secondary);', 'font-size: .74rem;' ] ) === true
	&& $cssHas( '.nino-admin .design-theme-buttons', [ 'display: flex;', 'flex: 0 1 auto;', 'flex-wrap: wrap;', 'min-width: 0;', 'max-width: 100%;' ] ) === true );
check( '...from 768px down a row is a column and the buttons stand under the words - the rule is inside that media query and nowhere else',
	$onNarrow !== '' && str_contains( $onNarrow, '.nino-admin .design-theme-row { flex-direction: column; align-items: stretch; }' ) === true
	&& str_contains( str_replace( $narrowRules[0] ?? "\0", '', $panelCss ), 'flex-direction: column' ) === false );
check( '...the pencil of a theme of the project is an icon alone, quiet, of the size of the other icon buttons and of 44px where the pointer is coarse; the row that is renamed has its words on a line of their own, so that the two fields stand one over the other and Save and Cancel under them wherever the card is; the field that takes a file in, under the list, has a rule above it as the rows have',
	$cssHas( '.nino-admin .design-theme-pencil', [ 'display: inline-flex;', 'border: 0;', 'color: var(--editor-text-secondary);' ] ) === true
	&& str_contains( $coarseRule( '.nino-admin .design-theme-pencil' ), 'min-height: 2.75rem;' ) === true && str_contains( $coarseRule( '.nino-admin .design-theme-pencil' ), 'min-width: 2.75rem;' ) === true
	&& $cssHas( '.nino-admin .design-theme-row--renaming .design-theme-text', [ 'flex: 1 0 100%;' ] ) === true
	&& $cssHas( '.nino-admin .design-theme-import', [ 'margin: 0;', 'border-top: 1px solid var(--editor-separator);' ] ) === true );
check( '...the css is a block in the monospace face of the workbench, much taller than a field of a form and resizable up and down only - a height in rem that shows about thirty lines of its own type at most and gives way to a lower window, never under ten lines: the least of two rems, the greater of which is the height of the window less the rem the chrome of the pane takes, and a floor -, the width the workbench\'s own for a textarea - and its label is out of sight but there: not display none, which a screen reader skips',
	$cssHas( '.nino-admin .design-theme-css', [ 'display: block;', 'font-family: var(--nino-admin-mono);', 'resize: vertical;' ] ) === true
	&& str_contains( $cssRule( '.nino-admin .design-theme-css' ), 'width' ) === false
	&& preg_match( '/(?:^| )height: min\(\s*([0-9.]+)rem,\s*max\(\s*([0-9.]+)rem,\s*calc\(\s*100vh\s*-\s*([0-9.]+)rem\s*\)\s*\)\s*\);/', $cssRule( '.nino-admin .design-theme-css' ), $fieldHeight ) === 1
	&& preg_match( '/font-size: ([0-9.]+)rem;/', $cssRule( '.nino-admin .design-theme-css' ), $fieldSize ) === 1 && preg_match( '/line-height: ([0-9.]+);/', $cssRule( '.nino-admin .design-theme-css' ), $fieldLine ) === 1
	&& (float) $fieldHeight[1] / ( (float) $fieldSize[1] * (float) $fieldLine[1] ) >= 28 && (float) $fieldHeight[1] / ( (float) $fieldSize[1] * (float) $fieldLine[1] ) <= 34
	&& (float) $fieldHeight[2] / ( (float) $fieldSize[1] * (float) $fieldLine[1] ) >= 10 && (float) $fieldHeight[2] < (float) $fieldHeight[1] && (float) $fieldHeight[3] > 0
	&& $cssHas( '.nino-admin .design-theme-css-name', [ 'position: absolute;', 'width: 1px;', 'height: 1px;', 'overflow: hidden;', 'clip-path: inset(50%);' ] ) === true
	&& str_contains( $cssRule( '.nino-admin .design-theme-css-name' ), 'display' ) === false );

/*	The loader has no preview to take the right of the pane, so its two cards stand side by side where the pane is as wide as the layout of the preview and the controls wants - above the width that
	layout stacks at - and one over the other below it; the columns may shrink. The line over them that says what an install did is a sentence of the pane: it keeps its height while it is empty, so that
	the cards do not move when it has something to say, and a failure is red as the one in the bar is	*/
preg_match( '/@media \(max-width: ([0-9.]+)rem\) \{ #design-layout \{/', $panelCss, $layoutStacks );

check( 'the two cards of the loader stand one over the other and, from the width above the one the layout of the preview stacks at, side by side - the columns may shrink - and the line over them that says an action is a sentence that keeps its height when it is empty and is red for a failure',
	$cssHas( '.nino-admin .design-faces-cards', [ 'display: grid;', 'grid-template-columns: minmax(0, 1fr);' ] ) === true
	&& isset( $layoutStacks[1] ) === true
	&& preg_match( '/@media \(min-width: '. preg_quote( $layoutStacks[1], '/' ). '\.001rem\) \{ \.nino-admin \.design-faces-cards \{ grid-template-columns: minmax\(0, 1fr\) minmax\(0, [0-9.]+fr\); \} \}/', $panelCss ) === 1
	&& $cssHas( '#design-faces-msg', [ 'min-height:', 'color: var(--editor-text-secondary);' ] ) === true && $cssHas( '#design-faces-msg.nino-admin-error', [ 'color: var(--editor-red);' ] ) === true );
check( '...and not one colour of the tab is a literal, nor a face but the workbench\'s: the chips and the buttons are the workbench\'s own, the rest takes its tokens',
	$themeRules[0] !== [] && count( $themeRules[0] ) >= 14 && preg_match( '/#[0-9a-f]{3,8}\b|\brgba?\(|\bhsla?\(|\boklch\(/i', $themeCss ) !== 1
	&& preg_match( '/font-family:(?! var\(--nino-admin-mono\))/', $themeCss ) !== 1 );

// --- Feature sets ---------------------------------------------------------------------
//
// A feature that is active and carries design/<variant>.css files brings variants and knobs of its own into the panel and into the one sheet. The pins run on
// the fixture feature (tests/fixtures/features/Probe, copied into the features directory of this run, see the top of this file) and on five features that
// this suite makes for itself, where a head is to be wrong, a key is a part's, a feature has one variant only, one has none at all and the variants of one
// declare knobs of their own

echo "\nFeature sets - a feature brings its variants and knobs with it\n";

$featureFile 	= \Nino\Filesystem::path( $appData, Setup::PATH );
$featureKept 	= is_file( $featureFile ) === true ? (string) file_get_contents( $featureFile ) : null;
$localeKept 	= $appData['./nino/locales/current'];
$modulesKept 	= $appData['/nino/modules'] ?? [];
$probeDir 		= \Nino\Features::dir(). '/Probe';

/** Which of the suite's features are active: their classes are listed in /nino/modules, and what the kernel and Design have read of the features is read again */
$featuresOn = static function( array $names ) use ( &$appData ): void {
	$appData['/nino/modules'] = array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names );
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
};

/** The codes of the notes that carry one */
$codesOfNotes = static fn( array $notes ): array => array_values( array_map( static fn( array $note ): string => $note['code'], array_filter( $notes, 'is_array' ) ) );

/** The sentences among the notes, those that carry no code */
$sentencesOf = static fn( array $notes ): array => array_values( array_filter( $notes, 'is_string' ) );

/** What the fixture's own file says in a line of its head: the words after the tag and the name of the knob (and the fields that stand between a knob's name and its label, $skip of them), or after the tag alone */
$fixtureWords = static function( string $variant, string $tag, string $name = '', int $skip = 0 ) use ( $probeDir ): string {

	$head = designOpening( $probeDir. '/design/'. $variant. '.css' );
	$name = $name === '' ? '' : str_replace( ' ', '\s+', preg_quote( $name, '~' ) ). '\s+'. str_repeat( '\S+\s+', $skip );

	return preg_match( '~^\s*\*?\s*'. preg_quote( $tag, '~' ). '\s+'. $name. '(.+?)(?:\s+=\s+\S.*)?$~m', $head, $found ) === 1 ? trim( $found[1] ) : '';
};

$featuresOn( [ 'Design', 'Probe', 'Plain' ] );

$scanNotes 	= [];
$scan 			= Setup::features( $appData, $scanNotes );
$loud 			= $scan['probe']['catalogue']['loud'] ?? [];
$regular 		= $scan['probe']['catalogue']['regular'] ?? [];

check( 'the kernel reads the features directory this run was given and lists the fixture as active - and the feature the suite made without a design/ directory beside it',
	\Nino\Features::dir() === NINO_FEATURES_DIR && ( \Nino\Features::get( $appData, 'probe' )['active'] ?? false ) === true && ( \Nino\Features::get( $appData, 'plain' )['active'] ?? false ) === true
	&& is_file( $probeDir. '/design/regular.css' ) === true && is_dir( \Nino\Features::dir(). '/Plain/design' ) === false );
check( 'the scan lists the features that are active and carry a design/ directory with a stylesheet in it - the fixture, by the name its manifest gives, with the variant it has before anything is chosen - and a feature without such a directory appears nowhere, nor does one that is not active',
	array_keys( $scan ) === [ 'probe' ] && $scan['probe']['name'] === 'Probe' && $scan['probe']['default'] === 'regular' && $scan['probe']['dir'] === $probeDir
	&& isset( $scan['plain'] ) === false && isset( $scan['broken'] ) === false && $scanNotes === [] );
check( '...the variants are the stylesheets, in the order of their names, and a variant is described as a set of the library is: the name and the description its head gives in the language of the workbench',
	array_keys( $scan['probe']['catalogue'] ) === [ 'loud', 'regular' ]
	&& $loud['name'] === $fixtureWords( 'loud', '@name-de' ) && $loud['description'] === $fixtureWords( 'loud', '@description-de' )
	&& $regular['name'] === $fixtureWords( 'regular', '@name-de' ) && $regular['description'] === $fixtureWords( 'regular', '@description-de' ) );
check( '...and carries the knobs its head declares, in the order of its lines: a variant may answer to knobs another does not, as two sets of a part do',
	array_keys( $regular['knobs'] ) === [ 'size', 'line', 'submit', 'label' ] && array_keys( $loud['knobs'] ) === [ 'size', 'line', 'submit', 'label', 'flip', 'turn' ] );
check( '...each with its kind and the tab its head puts it on, the label in the language of the workbench, the default it stands at before anything is chosen - the middle of a ladder, the surface, the tone, the pair of colours - and, for a choice, the three words of its steps',
	array_column( $loud['knobs'], 'kind' ) === [ 'scale', 'choice', 'ground', 'ink', 'colour', 'scale' ] && array_column( $loud['knobs'], 'tab' ) === [ 'type', 'surfaces', 'colours', 'colours', 'colours', 'motion' ]
	&& $loud['knobs']['size']['label'] === $fixtureWords( 'loud', '@knob-de', 'size' ) && $loud['knobs']['flip']['label'] === $fixtureWords( 'loud', '@knob-de', 'flip' )
	&& array_column( $loud['knobs'], 'default' ) === [ 0, 0, 'brand-safe', 'subtitle', [ '#b03a2e', '#e8705f' ], 0 ]
	&& array_keys( $loud['knobs']['line']['options'] ) === Setup::words( 'choice' ) && $loud['knobs']['line']['options']['more'] === $fixtureWords( 'loud', '@option-de', 'line more' )
	&& array_column( array_filter( $loud['knobs'], static fn( array $knob ): bool => $knob['kind'] !== 'choice' ), 'options' ) === array_fill( 0, 5, [] ) );
check( '...a scale and a choice are published by their ladders, with the key of the feature in the place of the part, as the knobs of a part are - and a ground, an ink and a colour declare none: the declaration is their head\'s line',
	$loud['knobs']['size']['ladder'] === [ 'size' ] && $loud['knobs']['line']['ladder'] === [ 'line' ] && isset( $loud['knobs']['submit']['ladder'] ) === false && isset( $loud['knobs']['label']['ladder'] ) === false && isset( $loud['knobs']['flip']['ladder'] ) === false );

$appData['./nino/locales/current'] = 'en_US';
$scanEnglish = Setup::features( $appData )['probe']['catalogue'];
$appData['./nino/locales/current'] = 'de_DE';

check( 'the words come in the language of the workbench: the lines of the other language are read where it is not English, the plain ones where it is - the name, the description, the labels of the knobs and the three words of a choice',
	$scanEnglish['loud']['name'] === $fixtureWords( 'loud', '@name' ) && $scanEnglish['loud']['description'] === $fixtureWords( 'loud', '@description' )
	&& $scanEnglish['loud']['knobs']['size']['label'] === $fixtureWords( 'loud', '@knob', 'size', 2 ) && $scanEnglish['loud']['knobs']['line']['options']['more'] === $fixtureWords( 'loud', '@option', 'line more' )
	&& $scanEnglish['loud']['knobs']['size']['label'] !== $loud['knobs']['size']['label'] && $scanEnglish['loud']['name'] !== $loud['name'] );

/*	A head says everything in both languages, as the head of a set of the library does: the line of the other language stands beside each English one - the name, the
	description, each knob and each option of a choice - and the two lists of what the head names are the same, in the same order. The language smoke leaves the lines of
	the other language out of its reading of a feature's design/ files (see tests/language-smoke.php) on the strength of this	*/
$headKeys = static function( string $variant ) use ( $probeDir ): array {

	$keys = [ '' => [], '-de' => [] ];

	foreach( explode( "\n", designOpening( $probeDir. '/design/'. $variant. '.css' ) ) as $line )
		if( preg_match( '~^[\s*/]*@(name|description|knob|option)(-de)?\b\s*(\S*)\s*(\S*)~', $line, $found ) === 1 )
			$keys[ $found[2] ][] = $found[1]. ( in_array( $found[1], [ 'knob', 'option' ], true ) === true ? ' '. $found[3] : '' ). ( $found[1] === 'option' ? ' '. $found[4] : '' );

	return $keys;
};

check( 'the head of each variant of the fixture says its name, its description, each knob and each option of a choice in both languages: the German line stands for every English one, and for nothing else',
	$headKeys('loud')[''] !== [] && $headKeys('loud')[''] === $headKeys('loud')['-de'] && $headKeys('regular')[''] !== [] && $headKeys('regular')[''] === $headKeys('regular')['-de']
	&& count( array_filter( $headKeys('loud')[''], static fn( string $key ): bool => str_starts_with( $key, 'knob ' ) ) ) === count( $loud['knobs'] )
	&& count( array_filter( $headKeys('loud')[''], static fn( string $key ): bool => str_starts_with( $key, 'option ' ) ) ) === 3 );

// A second feature with one variant, and one whose key is a part's
$featuresOn( [ 'Design', 'Single', 'Probe', 'Cardish' ] );
$twoNotes = [];
$two = Setup::features( $appData, $twoNotes );
$twoByKey = $two;
$twoByName = $two;
ksort( $twoByKey );
uasort( $twoByName, static fn( array $a, array $b ): int => strnatcasecmp( $a['name'], $b['name'] ) );

check( 'the features are sorted by the name they have, not by their directory or their key - and a feature with one variant only has it as the variant it has before anything is chosen, whatever its name is, since there is no regular one',
	array_keys( $two ) === [ 'single', 'probe' ] && array_keys( $two ) !== array_keys( $twoByKey ) && array_column( $two, 'name' ) === array_column( $twoByName, 'name' ) && array_keys( $two['single']['catalogue'] ) === [ 'plain' ] && $two['single']['default'] === 'plain' );
check( 'a feature whose key is the name of a part or of the page is skipped, with a note that names it: the rows of its knobs would be those of the part',
	isset( $two['card'] ) === false && count( array_filter( $twoNotes, static fn( mixed $note ): bool => is_string( $note ) === true && str_contains( $note, '"card"' ) === true ) ) === 1 );
check( '...and such a key is one the model knows as an object: the list of the objects has no feature in it that is called as one',
	array_intersect( array_keys( $two ), Setup::OBJECTS ) === [] && in_array( 'card', Setup::OBJECTS, true ) === true );

// The head of a variant: a line that is wrong is dropped and named, and the rest of the head stands
$featuresOn( [ 'Design', 'Broken' ] );
$brokenNotes = [];
$broken = Setup::features( $appData, $brokenNotes );
$brokenKnobs = $broken['broken']['catalogue']['regular']['knobs'] ?? [];

$wrongTokens = [ 'Nodefault', 'Badsurface', 'Badtone', 'Onehalf', 'Twice', 'Fixed', 'Nokind', 'notab', 'Nocss', 'Notemplates', 'Bad_Name', 'bare', 'Clash', '"tone"', 'ghostlabel', 'ghostoption', '"pad"', 'Form Section' ];

check( 'a head with a line wrong for each thing a line can be wrong in publishes the knobs that are right and nothing else: the first of a name that is declared twice, an ink with its default, a scale with its ladder, a ground with its surface - and not the scale named for a part of that ground, ladder or no ladder: the ground writes that property',
	array_keys( $brokenKnobs ) === [ 'size', 'ink', 'base' ] && $brokenKnobs['size']['label'] === 'Size' && $brokenKnobs['ink']['default'] === 'subtitle' && $brokenKnobs['size']['ladder'] === [ 'size' ] && $brokenKnobs['base']['default'] === 'brand' && isset( $brokenKnobs['base-border'] ) === false );
check( '...each line that was dropped is named, once, led by the feature and the variant it was in: a ground or an ink or a colour without a default of its kind, a scale that is given one, a name twice, a kind or a tab or a name there is none of - the views that have no knobs, the templates and the css, are no tab for one -, a line without its label, a choice without its three words, a label or the words for a knob that is not there, a knob named for a part of a ground of the same head, a scale without a complete ladder, and an anchor that is no id',
	count( $brokenNotes ) === count( $wrongTokens ) && array_filter( $brokenNotes, static fn( mixed $note ): bool => is_string( $note ) === false || str_starts_with( $note, 'broken/regular: ' ) === false ) === []
	&& array_filter( $wrongTokens, static fn( string $token ): bool => count( array_filter( $brokenNotes, static fn( string $note ): bool => str_contains( $note, $token ) === true ) ) !== 1 ) === [] );
check( '...the ladder no head declares is not offered - nothing says where it stands - and a knob that has no label of its language is labelled in English, as a set that has no name of its language is listed under its English one',
	isset( $brokenKnobs['stray'] ) === false && $brokenKnobs['ink']['label'] === 'Ink' && $brokenKnobs['size']['label'] === 'Size' );

// The anchor of a variant: one line of its head, "@anchor <id>", the id of the element of the specimen its block is looked at in
$headAt 			= static fn( string $file ): array => Setup::head( $file, 'en_US' );
$anchorsIn 		= static function( string $file ): array {

	preg_match_all( '~^[\s*]*@anchor[ \t]+(\S.*?)[ \t]*$~m', designOpening( $file ), $found );

	return $found[1];
};
$isAnchor 		= static fn( string $word ): bool => preg_match( '/^[a-z][a-z0-9-]*$/D', $word ) === 1;
$headLoud 		= $headAt( $probeDir. '/design/loud.css' );
$headRegular 	= $headAt( $probeDir. '/design/regular.css' );
$headBroken 	= $headAt( NINO_FEATURES_DIR. '/Broken/design/regular.css' );
$headFirst 		= $headAt( NINO_FEATURES_DIR. '/Pair/design/first.css' );
$headSecond 	= $headAt( NINO_FEATURES_DIR. '/Pair/design/second.css' );
$brokenWords 	= $anchorsIn( NINO_FEATURES_DIR. '/Broken/design/regular.css' );
$firstWords 	= $anchorsIn( NINO_FEATURES_DIR. '/Pair/design/first.css' );
$brokenNote 	= array_values( array_filter( $headBroken['notes'], static fn( string $note ): bool => str_contains( $note, '@anchor' ) === true ) );

check( 'a head may name the place of the specimen its block is looked at in, with one line, "@anchor <id>": head() answers the id the line carries - the word in the file, letters, digits and hyphens - and an empty string for a head without the line, and names neither; the knob lines round it are read as they are without it',
	$anchorsIn( $probeDir. '/design/loud.css' ) === [ $headLoud['anchor'] ] && $isAnchor( $headLoud['anchor'] ) === true && $anchorsIn( $probeDir. '/design/regular.css' ) === [] && $headRegular['anchor'] === ''
	&& $headLoud['notes'] === [] && $headRegular['notes'] === []
	&& array_keys( $headLoud['knobs'] ) === array_keys( $loud['knobs'] ) && array_keys( $headRegular['knobs'] ) === array_keys( $regular['knobs'] ) );
check( 'an anchor that is no id - a space and capitals in it - is dropped and named, once, with the line as it stands in the file: the variant has none, and the variant that is published says so',
	count( $brokenWords ) === 1 && $isAnchor( $brokenWords[0] ) === false && $headBroken['anchor'] === ''
	&& count( $brokenNote ) === 1 && str_contains( $brokenNote[0], $brokenWords[0] ) === true && ( $broken['broken']['catalogue']['regular']['anchor'] ?? null ) === '' );
check( 'a head has one anchor, a variant being looked at in one place: of two lines the first stands, and the second is dropped and named, once, with the line as it stands in the file',
	count( $firstWords ) === 2 && $isAnchor( $firstWords[0] ) === true && $isAnchor( $firstWords[1] ) === true && $firstWords[0] !== $firstWords[1]
	&& $headFirst['anchor'] === $firstWords[0] && count( $headFirst['notes'] ) === 1 && str_contains( $headFirst['notes'][0], $firstWords[1] ) === true && str_contains( $headFirst['notes'][0], $firstWords[0] ) === false );
check( 'the line has no twin in another language, an id being no word: "@anchor-de" is no line of the grammar - not read and not named - and a head that has that line and no other has no anchor, whatever language the workbench speaks',
	count( (array) preg_grep( '~@anchor-de\b~', explode( "\n", designOpening( NINO_FEATURES_DIR. '/Pair/design/second.css' ) ) ) ) === 1 && $anchorsIn( NINO_FEATURES_DIR. '/Pair/design/second.css' ) === []
	&& $headSecond['anchor'] === '' && $headSecond['notes'] === [] && Setup::head( NINO_FEATURES_DIR. '/Pair/design/second.css', 'de_DE' )['anchor'] === ''
	&& array_keys( $headSecond['knobs'] ) === [ 'alpha', 'beta' ] );

$unanchored = [];

foreach( [ $scan, $broken ] as $scanned )
	foreach( $scanned as $scannedKey => $scannedFeature )
		foreach( $scannedFeature['catalogue'] as $scannedVariant => $scannedEntry )
			if( is_string( $scannedEntry['anchor'] ?? null ) === false )
				$unanchored[] = $scannedKey. '/'. $scannedVariant;

check( 'the scan publishes the anchor with each variant as its head says it - the id of the line, and an empty string where the head has none - and with every variant of every feature, so that the screen reads it without asking whether it is there'. ( $unanchored === [] ? '' : ' - none for '. implode( ', ', $unanchored ) ),
	$unanchored === [] && $loud['anchor'] === $headLoud['anchor'] && $loud['anchor'] !== '' && $regular['anchor'] === '' );

// Read once for a request: the answer is kept beside the list of the modules it was made for
$featuresOn( [ 'Design', 'Probe' ] );
Setup::features( $appData );
rename( $probeDir. '/design/loud.css', $probeDir. '/design/loud.off' );
$keptScan = array_keys( Setup::features( $appData )['probe']['catalogue'] );
$featuresOn( [ 'Design', 'Probe' ] );
$freshScan = array_keys( Setup::features( $appData )['probe']['catalogue'] );
rename( $probeDir. '/design/loud.off', $probeDir. '/design/loud.css' );
$featuresOn( [ 'Design', 'Probe' ] );

check( 'the scan is read once for a request and kept in the app data: a variant taken away meanwhile is still listed, until the modules are listed again',
	$keptScan === [ 'loud', 'regular' ] && $freshScan === [ 'regular' ] );

// The choices of a setup, held against the features that are there
$featuresOn( [ 'Design', 'Probe' ] );

$defaultsOf = static fn( string $variant ): array => array_column( $scan['probe']['catalogue'][$variant]['knobs'], 'default' );
$entryOf 		= static function( array $raw, array &$notes = [] ) use ( &$appData, $library ): array {
	return Setup::normalize( $appData, [ 'features' => $raw ], $library, $notes )['features'];
};

$goodNotes 	= [];
$good 			= $entryOf( [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'size' => '2', 'line' => -1, 'submit' => 'danger', 'label' => 'primary', 'flip' => [ '#ABC', '123456' ], 'turn' => 1 ] ] ], $goodNotes );

check( 'a setup chooses a variant for a feature and a value for every knob that variant declares: a position for a scale and a choice, a surface for a ground, a tone for an ink, two hex colours - written as six digits, lower case - for a colour - in the order the head has them',
	$good === [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'size' => 2, 'line' => -1, 'submit' => 'danger', 'label' => 'primary', 'flip' => [ '#aabbcc', '#123456' ], 'turn' => 1 ] ] ] && $goodNotes === [] );

$heldNotes 	= [];
$held 			= $entryOf( [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'size' => 9, 'line' => 2, 'submit' => 'nowhere', 'label' => [ 'x' ], 'flip' => [ '#fff', 5 ], 'turn' => 'fast' ] ] ], $heldNotes );
$heldMore 	= $entryOf( [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'size' => null, 'line' => 'x', 'submit' => 5, 'label' => 'ink ', 'flip' => '#fff #000', 'turn' => [ 1 ] ] ] ] );

check( '...a value that is none of its kind is what the head says the knob stands at - a position out of the range of its kind, a surface or a tone that is none, a colour that is not two hex colours, whatever type it arrives in - and says nothing, as a position of a part does',
	$held['probe']['knobs'] === array_combine( array_keys( $loud['knobs'] ), $defaultsOf( 'loud' ) ) && $heldNotes === [] && $heldMore['probe']['knobs'] === $held['probe']['knobs'] );

$bareNotes 	= [];
$bare 			= $entryOf( [ 'probe' => [ 'set' => 'regular' ] ], $bareNotes );
$emptyOne 	= $entryOf( [ 'probe' => [] ] );

check( '...an entry that names no knob has every knob of its variant at the head\'s default, and one that names no variant is the variant the feature has before anything is chosen',
	$bare === [ 'probe' => [ 'set' => 'regular', 'knobs' => array_combine( array_keys( $regular['knobs'] ), $defaultsOf( 'regular' ) ) ] ] && $bareNotes === [] && $emptyOne === $bare );

$droppedNotes = [];
$dropped 			= $entryOf( [ 'probe' => [ 'set' => 'regular', 'knobs' => [ 'size' => 1, 'flip' => [ '#112233', '#445566' ], 'turn' => 2 ] ] ], $droppedNotes );

check( 'a knob the variant does not declare is dropped, and named, each: the variant is the one that is chosen, not the one the knob was set under - the knobs it declares stand',
	array_keys( $dropped['probe']['knobs'] ) === array_keys( $regular['knobs'] ) && $dropped['probe']['knobs']['size'] === 1
	&& count( $droppedNotes ) === 2 && count( array_filter( $droppedNotes, static fn( mixed $note ): bool => is_string( $note ) === true && str_contains( $note, '"flip"' ) === true && str_contains( $note, '"probe"' ) === true ) ) === 1
	&& count( array_filter( $droppedNotes, static fn( mixed $note ): bool => is_string( $note ) === true && str_contains( $note, '"turn"' ) === true ) ) === 1 );

$missingNotes = [];
$missing 			= $entryOf( [ 'probe' => [ 'set' => 'nowhere', 'knobs' => [ 'size' => 1 ] ] ], $missingNotes );
$anyNotes 		= [];

check( 'a variant that is none of the feature\'s falls to the one it has before anything is chosen, and the note says which, as the note of a part does - the code, the key, the variant that was asked for and the one that is used - and one that is no word at all falls to it silently',
	$missing['probe']['set'] === 'regular' && $missing['probe']['knobs']['size'] === 1 && $missingNotes === [ [ 'error' => '"probe" names the set "nowhere", which is not in the library - using "regular"', 'code' => 'design_set_missing', 'params' => [ 'probe', 'nowhere', 'regular' ] ] ]
	&& $entryOf( [ 'probe' => [ 'set' => 5 ] ], $anyNotes )['probe']['set'] === 'regular' && $anyNotes === [] );

$oddNotes 	= [];
$odd 				= $entryOf( [ 'Bad Key' => [ 'set' => 'regular' ], '7' => [], 'probe' => 'loud', 'fine' => 'text', 'extra' => null ], $oddNotes );

check( 'an entry that is no entry is dropped and named: a key that no feature has, an entry that is no array',
	$odd === [] && count( $oddNotes ) === 5 && array_filter( $oddNotes, 'is_string' ) === $oddNotes
	&& count( array_filter( $oddNotes, static fn( string $note ): bool => str_contains( $note, '"probe"' ) === true ) ) === 1 && count( array_filter( $oddNotes, static fn( string $note ): bool => str_contains( $note, '"Bad Key"' ) === true ) ) === 1 );

$strayNotes = [];
$stray 			= $entryOf( [ 'probe' => [ 'set' => 'regular', 'zzz' => 1 ] ], $strayNotes );

check( '...so is a key of an entry that is neither the variant nor the knobs; the entry has its two keys and no other',
	array_keys( $stray['probe'] ) === [ 'set', 'knobs' ] && count( $strayNotes ) === 1 && str_contains( (string) $strayNotes[0], '"zzz"' ) === true );

check( '...and the entries come in the order of their keys, whichever order they were chosen in, so that the same choices are the same file',
	array_keys( $entryOf( [ 'zed' => [ 'set' => 'a' ], 'probe' => [], 'alpha' => [ 'set' => 'b' ] ] ) ) === [ 'alpha', 'probe', 'zed' ] );

check( '...and normalising what normalising answered changes nothing and says nothing: the choices are their own normalisation, and what a file holds of them is what a request carries',
	$entryOf( $good ) === $good && $entryOf( $held ) === $held && json_decode( (string) json_encode( $good ), true ) === $good && Setup::defaults( $library )['features'] === [] );

// A feature that is not active: the choice stays for the day it is back
$featuresOn( [ 'Design' ] );
$inactiveNotes 	= [];
$inactive 			= $entryOf( [ 'probe' => [ 'set' => 'whatever', 'knobs' => [ 'size' => 99, 'x' => 'y' ] ], 'other' => [ 'set' => 5, 'knobs' => 'text' ] ], $inactiveNotes );

check( 'an entry of a feature that is not active - switched off, taken away - is kept as it is, nothing validated against a feature that is not there, with the note that says so: the code and the key - and one whose two keys are not what they should be is held to their types',
	$inactive === [ 'other' => [ 'set' => '', 'knobs' => [] ], 'probe' => [ 'set' => 'whatever', 'knobs' => [ 'size' => 99, 'x' => 'y' ] ] ]
	&& $inactiveNotes === [ Setup::inactive( 'probe' ), Setup::inactive( 'other' ) ] && $inactiveNotes[0]['code'] === 'design_feature_inactive' && $inactiveNotes[0]['params'] === [ 'probe' ] );
check( '...the note is in the list of the notes that carry a code, with a sentence for a log, and the key of the feature is cut at 40 characters like the other keys in a note',
	isset( Setup::NOTES['design_feature_inactive'] ) === true && substr_count( Setup::NOTES['design_feature_inactive'], '%s' ) === 1
	&& Setup::inactive( str_repeat( 'k', 80 ) )['params'] === [ str_repeat( 'k', 40 ) ] );

// ...and a feature that is there but brings no variants any more is none either
$featuresOn( [ 'Design', 'Plain' ] );

check( '...a feature that is active and brings no design/ directory is not a feature that brings variants, and its choice is kept as well',
	$entryOf( [ 'plain' => [ 'set' => 'x' ] ] ) === [ 'plain' => [ 'set' => 'x', 'knobs' => [] ] ] );

// The rows the features add to the panel
$featuresOn( [ 'Design', 'Probe' ] );
$layout 	= Setup::layout( $appData );
$added 		= array_diff_key( $layout, Setup::LAYOUT );

check( 'the table of the panel is the static one, complete as it was, and the rows the features add: one under Templates for the variant and one for each knob a variant of the feature declares, on the tab the head puts it on, in the object that is the feature',
	array_diff_key( $layout, $added ) === Setup::LAYOUT && array_keys( $added ) === [ 'features/probe/set', 'features/probe/knobs/size', 'features/probe/knobs/line', 'features/probe/knobs/submit', 'features/probe/knobs/label', 'features/probe/knobs/flip', 'features/probe/knobs/turn' ]
	&& $added['features/probe/set'] === [ 'templates', 'probe', 1 ] && $added['features/probe/knobs/size'] === [ 'type', 'probe', 1 ] && $added['features/probe/knobs/line'][0] === 'surfaces' && $added['features/probe/knobs/turn'][0] === 'motion' );
check( '...their places count from 1 on the tab in the object, in the order of the head\'s lines - the variants in the order of their names, a knob where the first of them that declares it puts it - and a knob only one variant declares has its row for both: the variant that does not answer to it has it greyed out',
	array_column( [ $added['features/probe/knobs/submit'], $added['features/probe/knobs/label'], $added['features/probe/knobs/flip'] ], 2 ) === [ 1, 2, 3 ] && array_unique( array_column( $added, 1 ) ) === [ 'probe' ]
	&& isset( $regular['knobs']['flip'] ) === false && isset( $added['features/probe/knobs/flip'] ) === true );
check( '...every row is on a tab that draws rows and in an object the panel has, and none has the key of a row of the static table',
	array_filter( $layout, static fn( array $row ): bool => in_array( $row[0], [ 'templates', 'type', 'surfaces', 'colours', 'motion' ], true ) === false || in_array( $row[1], Setup::objects( $appData ), true ) === false ) === []
	&& array_intersect_key( $added, Setup::LAYOUT ) === [] );
check( '...the objects are the eight of the page and, after the tables, the features that bring variants in the order of the catalogue; with no feature active the table and the objects are the static ones',
	Setup::objects( $appData ) === array_merge( Setup::OBJECTS, [ 'probe' ] ) && ( static function() use ( $featuresOn, &$appData ): bool {
		$featuresOn( [ 'Design' ] );
		$bare = Setup::layout( $appData ) === Setup::LAYOUT && Setup::objects( $appData ) === Setup::OBJECTS;
		$featuresOn( [ 'Design', 'Probe' ] );
		return $bare;
	} )() );

// A feature whose first variant declares the fewer knobs, and one of them on another tab than the second puts it
$featuresOn( [ 'Design', 'Pair' ] );
$pairRows = array_diff_key( Setup::layout( $appData ), Setup::LAYOUT );

check( '...the rows are those of all the variants and not of the first: a knob only the second declares has its row, and a knob both declare is where the first puts it, with the place the first gives it',
	array_keys( $pairRows ) === [ 'features/pair/set', 'features/pair/knobs/alpha', 'features/pair/knobs/beta' ] && $pairRows['features/pair/knobs/alpha'] === [ 'type', 'pair', 1 ] && $pairRows['features/pair/knobs/beta'] === [ 'type', 'pair', 2 ] );
check( '...a stylesheet in design/ whose name no variant can have - a space in it, two dots in a row - is none: it is not offered, and the knobs it declares have no row',
	array_keys( Setup::features( $appData )['pair']['catalogue'] ) === [ 'first', 'second' ] && isset( $pairRows['features/pair/knobs/gamma'] ) === false
	&& is_file( NINO_FEATURES_DIR. '/Pair/design/two words.css' ) === true && is_file( NINO_FEATURES_DIR. '/Pair/design/a..b.css' ) === true );
check( '...and a variant is published with the anchor its head names: the first of two lines where there are two, and none where the only line is one of another language',
	array_column( Setup::features( $appData )['pair']['catalogue'], 'anchor' ) === [ $firstWords[0], '' ] );

// The sheet: the section of the variant, the lines of the knobs, the dark halves
$featuresOn( [ 'Design', 'Probe' ] );

$chooseLoud 	= [ 'features' => [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'size' => 1, 'line' => -1, 'submit' => 'danger', 'label' => 'primary', 'flip' => [ '#112233', '#aabbcc' ], 'turn' => 2 ] ] ] ];
$withEntry 		= Setup::normalize( $appData, $chooseLoud, $library );
$sheetNotes 	= [];
$sheet 				= Compiler::compile( $appData, $withEntry, $library, $sheetNotes );
$titles 			= [];

if( preg_match_all( '~^/\* ==== (\d+)\. (.*?) ==== \*/$~m', $sheet, $titled, PREG_SET_ORDER ) > 0 )
	$titles = array_column( $titled, 2, 1 );

$ladderWords = [];

foreach( $loud['knobs'] as $knob => $meta )
	if( in_array( $meta['kind'], Setup::LADDERS, true ) === true )
		$ladderWords[] = $knob. ' '. Setup::word( $withEntry['features']['probe']['knobs'][$knob] );

$positions 	= designSection( $sheet, 15, Compiler::SECTION );
$rootLines 	= designDeclarations( designBlock( $positions, ':root {' ) );

check( 'a feature that has an entry in the setup gets a section of its own, after the five sets of the library and before the knob positions, which number on from it: "feature <key>: <variant>" and, in the brackets, the step of each of its ladders, as the section of a set has them',
	array_keys( $titles ) === range( 1, 15 ) && str_starts_with( $titles[13], 'tables: ' ) === true && $titles[14] === 'feature probe: loud ('. implode( ', ', $ladderWords ). ')' && $titles[15] === 'the knob positions' && $sheetNotes === [] );
check( '...and holds the stylesheet of the variant as the file has it, comments kept as for a set',
	str_contains( designSection( $sheet, 14, Compiler::SECTION ), rtrim( (string) file_get_contents( $probeDir. '/design/loud.css' ), "\n" ) ) === true
	&& str_contains( designSection( $sheet, 14, Compiler::SECTION ), '@knob' ) === true );
check( '...the sections after it move down by its number: with a theme\'s css the theme is the sixteenth, and a sheet without the feature has the fifteen it had',
	( static function() use ( &$appData, $withEntry, $library ): bool {
		$themed = $withEntry;
		$themed['css'] = '.x { color: red; }';
		$sections = [];
		preg_match_all( '~^/\* ==== (\d+)\. (.*?) ==== \*/$~m', Compiler::compile( $appData, $themed, $library ), $sections, PREG_SET_ORDER );
		return array_column( $sections, 2, 1 )[16] === 'the theme';
	} )() === true );

// The five kinds, line by line
$probeKeys 		= array_keys( $rootLines );
$firstProbe 	= array_search( '--probe-size', $probeKeys, true );
$groundParts 	= Colours::parts();

check( 'the knob positions block is one block: the lines of the parts, sorted, and after them those of the features, in the order of the catalogue and of the head - a ground\'s lines together, in the order of the palette\'s parts',
	$firstProbe !== false && array_filter( array_slice( $probeKeys, 0, (int) $firstProbe ), static fn( string $name ): bool => str_starts_with( $name, '--probe-' ) ) === []
	&& array_slice( $probeKeys, (int) $firstProbe ) === array_merge( [ '--probe-size', '--probe-line' ], array_map( static fn( string $part ): string => '--probe-submit'. ( $part === 'bg' ? '' : '-'. $part ), $groundParts ), [ '--probe-flip', '--probe-turn' ] )
	&& substr_count( $positions, ':root {' ) === 1 );
check( 'a scale and a choice are selected as the ladders of a set are, by the position the setup holds: the var() of the rung, named with the key of the feature in the place of the part',
	$rootLines['--probe-size'] === 'var('. sprintf( Setup::KNOB_TOKEN, 'probe', 'size', 'more' ). ')' && $rootLines['--probe-line'] === 'var('. sprintf( Setup::KNOB_TOKEN, 'probe', 'line', 'less' ). ')'
	&& $rootLines['--probe-turn'] === 'var('. sprintf( Setup::KNOB_TOKEN, 'probe', 'turn', 'most' ). ')' );

$paletteLines = designDeclarations( designBlock( Colours::css( [] ), ':root {' ) );
$dangerTokens = array_keys( array_filter( $paletteLines, static fn( string $value, string $name ): bool => preg_match( '/^--nino-(on-)?danger(-muted|-link|-border|-focus|-disabled|-shadow)?$/D', $name ) === 1, ARRAY_FILTER_USE_BOTH ) );
$groundLines 	= array_filter( $rootLines, static fn( string $name ): bool => str_starts_with( $name, '--probe-submit' ), ARRAY_FILTER_USE_KEY );

check( 'a ground is written as the surface and every part the palette publishes of it - eight lines - each the var() of the palette\'s own token for that surface and that part, so that the text the palette solved for the ground stands on it: the eight tokens are all that the palette declares of that surface, read off the palette\'s own output',
	count( $groundLines ) === count( $groundParts ) && count( $groundParts ) === 8 && count( $dangerTokens ) === 8
	&& array_values( array_map( static fn( string $value ): string => substr( $value, 4, -1 ), $groundLines ) ) === array_map( static fn( string $part ): string => Colours::token( 'danger', $part ), $groundParts )
	&& array_values( array_map( static fn( string $value ): string => substr( $value, 4, -1 ), $groundLines ) ) === $dangerTokens );

$everySurface = [];

foreach( Colours::SURFACES as $surface ) {

	$eachSetup = Setup::normalize( $appData, [ 'features' => [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'submit' => $surface ] ] ] ], $library );
	$eachLines = array_filter( designDeclarations( designBlock( designSection( Compiler::compile( $appData, $eachSetup, $library ), 15, Compiler::SECTION ), ':root {' ) ), static fn( string $name ): bool => str_starts_with( $name, '--probe-submit' ), ARRAY_FILTER_USE_KEY );

	foreach( $eachLines as $value )
		if( isset( $paletteLines[ substr( $value, 4, -1 ) ] ) === false )
			$everySurface[] = $surface. ' '. $value;

	if( count( $eachLines ) !== 8 )
		$everySurface[] = $surface. ' has '. count( $eachLines );
}

check( '...and so it is for each of the eleven surfaces of the palette, in its order: eight lines, and every token a line points at is one the palette declares'. ( $everySurface === [] ? '' : ' - '. implode( ', ', $everySurface ) ),
	count( Colours::SURFACES ) === 11 && $everySurface === [] );

$inkBlock = designBlock( $positions, ':root, :root * {' );

check( 'an ink is one line, the var() of the kernel\'s token for the tone, declared on every element: the sections of Nino.css point those tokens elsewhere on their own ground, and a property declared on the root alone would keep the value the root gave it - so the ink of an element is the ink of the ground it stands on',
	designDeclarations( $inkBlock ) === [ '--probe-label' => 'var('. Setup::INKS['primary']. ')' ] && isset( $rootLines['--probe-label'] ) === false );

$kernelRoot = designDeclarations( designBlock( $ninoCss, ':root {' ) );

check( 'the seven tones of the inks are seven tokens the kernel\'s Nino.css declares on :root, in the words the panel offers them in',
	array_keys( Setup::INKS ) === [ 'ink', 'subtitle', 'title', 'accent', 'primary', 'border', 'focus' ] && array_diff( array_values( Setup::INKS ), array_keys( $kernelRoot ) ) === [] );

// A colour: its light half on the root, its dark half under the selectors the palette's own dark half stands under
$paletteDark = Colours::css( [] );
preg_match( '~^(@media[^\n]*\{\n\t[^\n]*\{\n)\t\t--nino-~m', $paletteDark, $mediaOpener );
preg_match( '~^(:root\[[^\n]*\{\n)\t--nino-~m', $paletteDark, $attributeOpener );

check( 'a colour is its light half on the root, and its dark half in the two blocks the palette\'s own dark half is written in - under the system\'s scheme unless the attribute says light, and under the attribute that says dark - the selectors read off the palette\'s output, and the lines of the features after the palette\'s, in the order of the sheet',
	$rootLines['--probe-flip'] === '#112233' && isset( $mediaOpener[1], $attributeOpener[1] ) === true
	&& str_contains( $positions, $mediaOpener[1]. "\t\t--probe-flip: #aabbcc;\n\t}\n}\n" ) === true && str_contains( $positions, "\n". $attributeOpener[1]. "\t--probe-flip: #aabbcc;\n}" ) === true
	&& strpos( $sheet, $mediaOpener[1] ) < strpos( $sheet, $mediaOpener[1]. "\t\t--probe-flip" ) && substr_count( $sheet, $mediaOpener[1] ) === 2 && substr_count( $sheet, $attributeOpener[1] ) === 2 );
check( '...the palette as one mode alone has no dark half to write: the light mode writes the light half and no block, the dark mode writes the dark half where the light one stands, on the root',
	( static function() use ( &$appData, $withEntry, $library, $mediaOpener ): bool {
		$bothHalves = [];
		foreach( [ 'light' => '#112233', 'dark' => '#aabbcc' ] as $mode => $half ) {
			$modal = $withEntry;
			$modal['colours'] = Colours::normalize( [ 'mode' => $mode ] );
			$modalPositions = designSection( Compiler::compile( $appData, $modal, $library ), 15, Compiler::SECTION );
			$bothHalves[$mode] = designDeclarations( designBlock( $modalPositions, ':root {' ) )['--probe-flip'] === $half && str_contains( $modalPositions, '@media' ) === false && str_contains( $modalPositions, 'data-nino-mode' ) === false;
		}
		return $bothHalves === [ 'light' => true, 'dark' => true ];
	} )() === true );
check( '...a variant with no colour knob writes no dark block, and an ink is written where it stands at its default as well: what was chosen for the feature is written whole',
	( static function() use ( &$appData, $library ): bool {
		$plainLoud = Setup::normalize( $appData, [ 'features' => [ 'probe' => [ 'set' => 'regular' ] ] ], $library );
		$plainPositions = designSection( Compiler::compile( $appData, $plainLoud, $library ), 15, Compiler::SECTION );
		return str_contains( $plainPositions, '@media' ) === false && designDeclarations( designBlock( $plainPositions, ':root, :root * {' ) ) === [ '--probe-label' => 'var('. Setup::INKS['subtitle']. ')' ];
	} )() === true );

/*	The stylesheet the feature brings along, read one rule at a time: where each custom property its knobs move has its default. A default stands where the
	line of the knob positions beats it wherever the two are, and :where() has no specificity - so each property stands in the one place of its kind, and in no
	other: a bare :root would win over that line by order, since the bundle lists the stylesheets of the features after theme.css, and a declaration on the
	feature's own class, or on any element, beats what the root hands down by inheritance. The kinds and the names are the scan's, read off the heads of the
	two variants, and the dark selectors of a colour are the ones the palette's own dark half stands under, read off Colours::dark()	*/
$probeRules 	= designRules( designPlain( (string) file_get_contents( $probeDir. '/assets/probe.css' ) ) );
$darkPlaces 	= array_map( static fn( array $rule ): string => $rule['within']. '|:where('. $rule['selector']. ')', designRules( Colours::dark( static fn( int $depth ): string => '' ) ) );
$knobKinds 		= array_map( static fn( array $knob ): string => $knob['kind'], array_merge( $regular['knobs'], $loud['knobs'] ) );
$placeOfKind 	= [
	'scale' => [ '|:where(:root)' ], 'choice' => [ '|:where(:root)' ], 'ground' => [ '|:where(:root)' ],
	'colour' => array_merge( [ '|:where(:root)' ], $darkPlaces ), 'ink' => [ '|:where(*)' ],
];

/** The custom properties a knob of the fixture moves: its own - and, for a ground, the one of each part the palette publishes of a surface */
$knobProperties = static fn( string $name, string $kind ): array => $kind === 'ground'
	? array_map( static fn( string $part ): string => '--probe-'. $name. ( $part === 'bg' ? '' : '-'. $part ), Colours::parts() )
	: [ '--probe-'. $name ];

/** Where the stylesheet of the fixture declares a custom property: the block and the selector of each rule that does, in the order they stand */
$declaredAt = static fn( string $property ): array => array_values( array_map(
	static fn( array $rule ): string => $rule['within']. '|'. $rule['selector'],
	array_filter( $probeRules, static fn( array $rule ): bool => preg_match( '/(?<![\w-])'. preg_quote( $property, '/' ). '\s*:/', $rule['body'] ) === 1 )
) );

/** The custom properties of the fixture a rule declares with !important - a default that would beat the line of the knob positions whatever its place */
$importantAt = static fn( string $property ): array => array_values( array_map(
	static fn( array $rule ): string => $rule['within']. '|'. $rule['selector'],
	array_filter( $probeRules, static fn( array $rule ): bool => preg_match( '/(?<![\w-])'. preg_quote( $property, '/' ). '\s*:[^;{}]*!\s*important/i', $rule['body'] ) === 1 )
) );

$strayDefaults 		= [];
$missingDefaults 	= [];

foreach( $knobKinds as $name => $kind ) {

	foreach( $knobProperties( $name, $kind ) as $property ) {

		// A colour stands in its three places, each once and in their order - the light half first, in :where(:root) - and the others in theirs
		foreach( $kind === 'colour' && $declaredAt( $property ) !== $placeOfKind[$kind] ? $declaredAt( $property ) : array_diff( $declaredAt( $property ), $placeOfKind[$kind] ) as $place )
			$strayDefaults[] = $property. ' in '. trim( str_replace( '|', ' ', $place ) );

		foreach( $importantAt( $property ) as $place )
			$strayDefaults[] = $property. ' with !important in '. trim( str_replace( '|', ' ', $place ) );
	}

	if( $declaredAt( '--probe-'. $name ) === [] )
		$missingDefaults[] = '--probe-'. $name;
}

check( 'the stylesheet of the fixture declares each custom property its knobs move in the one place of its kind and in no other, property by property, and none of them with !important: a scale, a choice, a ground - its surface and each part of it that the stylesheet sets - and the light half of a colour in :where(:root); the dark half of a colour in :where() of the selectors the palette writes its own dark half under, the three places of a colour each once and in that order; an ink in :where(*), which declares it on every element, so that each resolves the page\'s tone where it stands - in the section it is in'. ( $strayDefaults === [] ? '' : ' - '. implode( ', ', $strayDefaults ) ),
	$strayDefaults === [] && $probeRules !== [] );
check( '...and it is not run over nothing: the fixture has a knob of each of the five kinds, a default for each knob, the text colour of its ground, and the two halves of its colour - the light one and the dark one in each of the two places the palette\'s own dark half has',
	array_diff( Setup::FEATURE_KINDS, $knobKinds ) === [] && $missingDefaults === [] && $declaredAt( '--probe-submit-on' ) !== [] && count( $darkPlaces ) === 2 && count( $declaredAt( '--probe-flip' ) ) === 3 );

/*	The bundle of a project the wizard has set up, as the installer leaves it - the kernel's own entry, then the theme this sheet is written to and the
	project's style.css (the installer's own list is a private constant of a class that a project may delete after the setup, so no test can read it) - and then what the boot adds	*/
$appData['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ Compiler::TARGET, '/assets/style.css' ] ) ];
\Nino\Features::registerAssets( $appData );
$bundle = $appData['/nino/html/assets']['/.cache/style.css'];

check( 'in a booted project the bundle lists the stylesheet of a feature after the theme.css this sheet is written to - the order the :where() of its defaults is for',
	in_array( '/features/Probe/assets/probe.css', $bundle, true ) === true && array_search( '/features/Probe/assets/probe.css', $bundle, true ) > array_search( Compiler::TARGET, $bundle, true )
	&& \Nino\Features::get( $appData, 'probe' )['assets'] === [ '/features/Probe/assets/probe.css' ] );
unset( $appData['/nino/html/assets'], $appData['./nino/features/listed'] );

// With no entry the feature compiles nothing; with the feature off the entry compiles nothing and is told
$noEntry 	= Setup::normalize( $appData, [], $library );
$sheetOn 	= Compiler::compile( $appData, $noEntry, $library );
$sameThemes = [];

foreach( Themes::SHIPPED as $slug ) {

	$themeRaw 	= (array) Themes::read( $appData, $library, $slug );
	$themeSetup = Setup::normalize( $appData, Themes::setup( $themeRaw ) + [ 'theme' => $slug, 'css' => Themes::css( $themeRaw ) ], $library );
	$themeWith 	= Compiler::compile( $appData, $themeSetup, $library );
	$featuresOn( [ 'Design' ] );
	$sameThemes[] = $themeWith === Compiler::compile( $appData, $themeSetup, $library );
	$featuresOn( [ 'Design', 'Probe' ] );
}

$featuresOn( [ 'Design' ] );
$sheetOff = Compiler::compile( $appData, $noEntry, $library );
$offNotes = [];
$entryOff = Compiler::compile( $appData, $withEntry, $library, $offNotes );
$onceNotes = [];
Compiler::compile( $appData, Setup::normalize( $appData, $chooseLoud, $library, $onceNotes ), $library, $onceNotes );
$featuresOn( [ 'Design', 'Probe' ] );

check( 'a feature that has no entry compiles nothing: with the feature active the sheet is the sheet without it, byte for byte - and so are the sheets the shipped themes make',
	$sheetOn === $sheetOff && str_contains( $sheetOn, 'feature probe' ) === false && $sameThemes === array_fill( 0, count( Themes::SHIPPED ), true ) && $sameThemes !== [] );
check( 'an entry of a feature that is not active compiles nothing and is told: no section and not a line, the sheet the setup without the entry makes, and the note once however many readers of the setup told it already',
	$entryOff === $sheetOff && $offNotes === [ Setup::inactive( 'probe' ) ] && count( $onceNotes ) === 1 && $onceNotes[0] === Setup::inactive( 'probe' ) );

// The header
$header = substr( $sheet, 0, (int) strpos( $sheet, "*/\n" ) );
$moved = 'feature probe  loud  (size more, line less, submit danger, label primary, flip #112233/#aabbcc, turn most)';
$restOnly = Compiler::compile( $appData, Setup::normalize( $appData, [ 'features' => [ 'probe' => [ 'set' => 'regular' ] ] ], $library ), $library );

check( 'the header has a line for each feature that was given a variant, after the lines of the parts and before the blank one that leads to the fonts: the key, the variant and, in brackets, the knobs that stand off the head\'s default - the step of a ladder, the word of a ground or an ink, the two halves of a colour',
	str_contains( $header, "\n *   tables     ". $withEntry['parts']['tables']. "\n *   ". $moved. "\n *\n *   title" ) === true );
check( '...and nothing where no feature was: a variant with every knob at its default is its line without brackets, a sheet without entries has no such line',
	str_contains( substr( $restOnly, 0, (int) strpos( $restOnly, "*/\n" ) ), "\n *   feature probe  regular\n *\n" ) === true && str_contains( substr( $sheetOn, 0, (int) strpos( $sheetOn, "*/\n" ) ), "\n *   feature " ) === false );

// The fingerprint
$fingerprint = static function( array $setup ) use ( &$appData, $library ): string {
	return \Nino\Modules\Design::fingerprint( $appData, $setup, $library );
};
$printLoud 		= $fingerprint( $withEntry );
$printPlain 	= $fingerprint( $noEntry );
$loudFile 		= $probeDir. '/design/loud.css';
$loudBytes 		= (string) file_get_contents( $loudFile );
file_put_contents( $loudFile, $loudBytes. "\n.probe { opacity: 1; }\n" );
$printEdited 	= $fingerprint( $withEntry );
file_put_contents( $loudFile, $loudBytes );
$featuresOn( [ 'Design' ] );
$printOff 		= $fingerprint( $withEntry );
$featuresOn( [ 'Design', 'Probe' ] );

check( 'the fingerprint follows the choices for the features and the bytes of the variant files they point at: another variant is another fingerprint, so is an edit of the file of the variant in use, and so is the feature being switched off - which changes what is compiled',
	$printLoud !== $printPlain && $printLoud !== $fingerprint( Setup::normalize( $appData, [ 'features' => [ 'probe' => [ 'set' => 'regular' ] ] ], $library ) )
	&& $printEdited !== $printLoud && $fingerprint( $withEntry ) === $printLoud && $printOff !== $printLoud );
check( '...and a project that chose nothing for any feature hashes as it did before there were features: the empty choices are left out of the input, whichever features are active',
	$printPlain === $fingerprint( Setup::normalize( $appData, [ 'features' => 'x' ], $library ) ) && $printPlain === $beforeFamilies( $noEntry ) );

// The panel's side: what design/list and design/preview answer, what a save keeps - for the account that may manage the design
$featuresOn( [ 'Design', 'Probe' ] );
\Nino\Auth::logoutUser( $appData );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

$stored = Setup::normalize( $appData, [ 'features' => $chooseLoud['features'] + [ 'gone' => [ 'set' => 'x', 'knobs' => [ 'a' => 1 ] ] ] ], $library );
Setup::write( $appData, $stored );

[ $status, $listedFeatures ] = callDesignAction( $appData, 'apiList' );
$answerFeature = $listedFeatures['features']['probe'] ?? [];

check( 'design/list answers the features that bring variants: for each its name, that it is active, the variant it has before anything is chosen and its variants with their knobs - the kind, the tab, the label, the words of a choice, the default - and what the setup chose for it: the variant and the value of each knob',
	$status === 200 && array_keys( $answerFeature ) === [ 'name', 'active', 'default', 'catalogue', 'set', 'knobs' ] && $answerFeature['name'] === 'Probe' && $answerFeature['active'] === true && $answerFeature['default'] === 'regular'
	&& array_keys( $answerFeature['catalogue'] ) === [ 'loud', 'regular' ] && array_keys( $answerFeature['catalogue']['loud'] ) === [ 'name', 'description', 'anchor', 'knobs' ]
	&& array_keys( $answerFeature['catalogue']['loud']['knobs']['line'] ) === [ 'kind', 'tab', 'label', 'options', 'default' ] && $answerFeature['catalogue']['loud']['knobs']['flip']['default'] === [ '#b03a2e', '#e8705f' ]
	&& $answerFeature['set'] === 'loud' && $answerFeature['knobs'] === $stored['features']['probe']['knobs'] );
check( '...with each variant the place of the specimen its head says it is looked at in, as the file has it: the id of its anchor line, and an empty string for the variant that has none',
	$answerFeature['catalogue']['loud']['anchor'] === $fixtureWords( 'loud', '@anchor' ) && $answerFeature['catalogue']['loud']['anchor'] !== '' && $answerFeature['catalogue']['regular']['anchor'] === '' );
check( '...nothing of the server in it that the screen does not read: not the directory of the feature, not the names of the ladders',
	str_contains( (string) json_encode( $listedFeatures['features'] ), $probeDir ) === false && str_contains( (string) json_encode( $listedFeatures['features'] ), 'ladder' ) === false );
check( '...a choice kept for a feature that is not in the catalogue follows them, flagged as not active and as it was kept - so that a save carries it on - and the order is the catalogue\'s, then the kept ones by their keys',
	array_keys( $listedFeatures['features'] ) === [ 'probe', 'gone' ] && $listedFeatures['features']['gone'] === [ 'name' => 'gone', 'active' => false, 'default' => '', 'catalogue' => [], 'set' => 'x', 'knobs' => [ 'a' => 1 ] ] );
check( '...the table of the controls and the objects are the merged ones: the page\'s own and the rows of the feature, the object after the tables',
	$listedFeatures['layout'] === [ 'tabs' => Setup::TABS, 'objects' => Setup::objects( $appData ), 'controls' => Setup::layout( $appData ) ] && end( $listedFeatures['layout']['objects'] ) === 'probe'
	&& $listedFeatures['layout']['controls']['features/probe/knobs/flip'][0] === 'colours' );
check( '...and the words of the two choices out of the palette\'s vocabulary: the surfaces of a ground in the palette\'s order and the tones of an ink',
	$listedFeatures['grounds'] === Colours::SURFACES && $listedFeatures['inks'] === array_keys( Setup::INKS ) );

$groundOf = static fn( string $mode ): string => Colours::palette( [], $mode )['default']['bg'];
$measured = $listedFeatures['contrasts']['probe']['flip'] ?? [];

check( 'design/list says what the colours of the features measure on the page, as the brand does: for each half of a colour the ratio against the ground of the palette in that mode, the ratio a mark has to reach, and whether it does - for the colours of the variant that is chosen, and for nothing else',
	array_keys( $listedFeatures['contrasts'] ) === [ 'probe' ] && array_keys( $listedFeatures['contrasts']['probe'] ) === [ 'flip' ] && array_keys( $measured ) === [ 'light', 'dark' ]
	&& $measured['light'] === [ 'ratio' => round( Colours::contrast( '#112233', $groundOf( 'light' ) ), 2 ), 'target' => Colours::TARGET_UI, 'safe' => round( Colours::contrast( '#112233', $groundOf( 'light' ) ), 2 ) >= Colours::TARGET_UI ]
	&& $measured['dark'] === [ 'ratio' => round( Colours::contrast( '#aabbcc', $groundOf( 'dark' ) ), 2 ), 'target' => Colours::TARGET_UI, 'safe' => round( Colours::contrast( '#aabbcc', $groundOf( 'dark' ) ), 2 ) >= Colours::TARGET_UI ]
	&& $measured['light']['safe'] === true && $measured['dark']['safe'] === true );

$postedLoud = $withEntry;
unset( $postedLoud['format'], $postedLoud['compiled'] );
$weakFeatures = [ 'probe' => [ 'set' => 'loud', 'knobs' => [ 'flip' => [ '#eeeeee', '#222222' ] ] ] ];
[ $weakStatus ] = callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'features' => $weakFeatures ] ) );
$weakMeasured = callDesignAction( $appData, 'apiList' )[1]['contrasts']['probe']['flip'];

check( '...a colour that does not reach the ratio on its page is said so and refused nowhere: both halves are below the target, and the save that holds them is a save like any other',
	$weakStatus === 200 && $weakMeasured['light']['safe'] === false && $weakMeasured['dark']['safe'] === false && $weakMeasured['light']['ratio'] < Colours::TARGET_UI && Setup::read( $appData, $library )['features']['probe']['knobs']['flip'] === [ '#eeeeee', '#222222' ] );

Setup::write( $appData, Setup::normalize( $appData, [ 'features' => [ 'probe' => [ 'set' => 'regular' ] ] ], $library ) );
$regularAnswer = callDesignAction( $appData, 'apiList' )[1];
Setup::write( $appData, Setup::normalize( $appData, [], $library ) );
$noEntryAnswer = callDesignAction( $appData, 'apiList' )[1];
$featuresOn( [ 'Design' ] );
$noneAnswer = callDesignAction( $appData, 'apiList' )[1];
$featuresOn( [ 'Design', 'Probe' ] );

check( '...a variant with no colour measures nothing, a feature that has no entry has no variant and no value in the answer, and with no feature active the answer is the page\'s own: no features, the eight objects and the static table',
	$regularAnswer['contrasts'] === [] && $noEntryAnswer['features']['probe']['set'] === '' && $noEntryAnswer['features']['probe']['knobs'] === []
	&& $noneAnswer['features'] === [] && $noneAnswer['layout']['objects'] === Setup::OBJECTS && $noneAnswer['layout']['controls'] === Setup::LAYOUT && $noneAnswer['contrasts'] === [] );

// design/preview answers the same measure, for the setup it is sent, and tells what it dropped
[ $status, $previewed ] = callDesignAction( $appData, 'apiPreview', array_merge( $postedLoud, [ 'features' => $withEntry['features'] + [ 'gone' => [ 'set' => 'x' ] ] ] ) );

check( 'design/preview answers what the colours of the features measure for the setup it is sent - with every change of the screen, beside the brand - and the notes of what it replaced: the choice kept for a feature that is not there',
	$status === 200 && isset( $previewed['contrasts']['probe']['flip']['light'] ) === true && $previewed['contrasts']['probe']['flip']['light']['ratio'] === $measured['light']['ratio']
	&& array_keys( $previewed ) === [ 'css', 'document', 'style', 'accent', 'brand', 'contrasts', 'notes' ]
	&& $previewed['notes'] === [ Setup::inactive( 'gone' ) ] && str_contains( $previewed['css'], '/* ==== 14. feature probe: loud (' ) === true );

// The preview links the stylesheets of the active features after the compiled sheet, as a bundle of its own (Admin::document()): the paths below /features/ of the list of stylesheets
// the site is built from, in its order, each once - the order of the site, so that what such a sheet sets by default gives way to what the compiled sheet moves
$siteWith = static function( array $sheets ) use ( &$appData ): void {
	$appData['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ Compiler::TARGET, '/assets/style.css' ], $sheets ) ];
};
$pageOfPreview = static function() use ( &$appData ): string {
	unset( $appData['/nino/html/assets'][ Admin::FEATURES_CSS ] );
	[ $answered, $answer ] = callDesignAction( $appData, 'apiPreview', [ 'parts' => [], 'full' => true ] );
	return $answered === 200 ? (string) ( $answer['document'] ?? '' ) : '';
};
$bundleOf = static fn(): string => (string) @file_get_contents( \Nino\Filesystem::path( $appData, Admin::FEATURES_CSS ) );
$sheetFile 		= $probeDir. '/assets/probe.css';
$sheetOriginal = (string) file_get_contents( $sheetFile );
$sheetText 		= trim( $sheetOriginal );

// A project booted with the fixture active: the list the installer leaves, then what the boot adds
$siteWith( [] );
\Nino\Features::registerAssets( $appData );
$siteList 	= array_values( \Nino\Html::getAssets( $appData, '/.cache/style.css' ) );
$pageOn 		= $pageOfPreview();
$sourcesOn 	= array_values( \Nino\Html::getAssets( $appData, Admin::FEATURES_CSS ) );
$bundleOn 	= $bundleOf();

preg_match( '#<link[^>]*href="[^"]*design-preview-features\.css(?:\?v=([a-f0-9]+))?"[^>]*>#', $pageOn, $linkOn, PREG_OFFSET_CAPTURE );

$orderOn = [ strpos( $pageOn, 'design-preview.css' ), strpos( $pageOn, 'id="'. Admin::PREVIEW_STYLE. '"' ), $linkOn[0][1] ?? false, strpos( $pageOn, '</head>' ) ];
$sortedOn = $orderOn;
sort( $sortedOn );

check( 'with a feature active that brings a stylesheet the preview links it after the compiled sheet, as a bundle of its own: the framework, the sheet inline and then the stylesheets of the features, in that order and all in the head - the order of the site, which lets what a feature sets by default give way to what the compiled sheet moves',
	in_array( false, $orderOn, true ) === false && $orderOn === $sortedOn && count( array_unique( $orderOn ) ) === 4
	&& substr_count( $pageOn, 'design-preview-features' ) === 1 && Admin::FEATURES_CSS === '/_admin/.cache/design-preview-features.css' );
check( '...the bundle is written and carries the stylesheet of the feature as its file has it, and its sources are the paths the manifests of the active features name and nothing else of the list the site is built from: not the kernel\'s entry, not the theme the compiled sheet stands for, not the project\'s own style.css',
	$bundleOn !== '' && str_contains( $bundleOn, $sheetText ) === true && $sourcesOn === \Nino\Features::get( $appData, 'probe' )['assets']
	&& in_array( Compiler::TARGET, $siteList, true ) === true && in_array( '/assets/style.css', $siteList, true ) === true && in_array( '/_nino/Nino.css', $siteList, true ) === true && array_diff( $sourcesOn, $siteList ) === [] );

// The address carries the hash of the bundle, which follows its sources: a byte more in the stylesheet and the next preview writes the bundle again
file_put_contents( $sheetFile, $sheetOriginal. "\n" );
clearstatcache();
$pageTouched 		= $pageOfPreview();
$bundleTouched 	= $bundleOf();
file_put_contents( $sheetFile, $sheetOriginal );
clearstatcache();

preg_match( '#design-preview-features\.css\?v=([a-f0-9]+)#', $pageTouched, $linkTouched );

check( '...and the address carries the hash of the bundle, which follows its sources: a byte more in the stylesheet of a feature and the next preview writes the bundle again and links another address',
	( $linkOn[1][0] ?? '' ) !== '' && ( $linkTouched[1] ?? '' ) !== '' && $linkTouched[1] !== $linkOn[1][0] && strlen( $bundleTouched ) === strlen( $bundleOn ) + 1 );

// The list in the order of the site, a path listed twice, and a project that lists the stylesheet of a feature that is no manifest's
$otherSheet = NINO_FEATURES_DIR. '/Pair/assets/pair.css';
mkdir( dirname( $otherSheet ), 0755, true );
file_put_contents( $otherSheet, ".pair-sheet { color: red; }\n" );

[ $probePath, $pairPath ] = [ $sourcesOn[0], '/features/Pair/assets/pair.css' ];

$siteWith( [ $pairPath, $probePath, $probePath ] );
$pageOrder 		= $pageOfPreview();
$sourcesOrder = array_values( \Nino\Html::getAssets( $appData, Admin::FEATURES_CSS ) );
$bundleOrder 	= $bundleOf();
$siteWith( [ $probePath, '/assets/more.css', $pairPath ] );
$pageBack 		= $pageOfPreview();
$sourcesBack 	= array_values( \Nino\Html::getAssets( $appData, Admin::FEATURES_CSS ) );
$bundleBack 	= $bundleOf();
unlink( $otherSheet );
rmdir( dirname( $otherSheet ) );

$pairAt = [ strpos( $bundleOrder, '.pair-sheet' ), strpos( $bundleOrder, $sheetText ), strpos( $bundleBack, '.pair-sheet' ), strpos( $bundleBack, $sheetText ) ];

check( 'the stylesheets are those of the list the site is built from, in its order and each once: a path listed twice is in the bundle once, a path that is none below /features/ is not in it, and the bundle follows the list when the list is the other way round',
	$sourcesOrder === [ $pairPath, $probePath ] && $sourcesBack === [ $probePath, $pairPath ] && str_contains( $pageOrder, 'design-preview-features' ) === true && str_contains( $pageBack, 'design-preview-features' ) === true
	&& array_filter( $pairAt, 'is_int' ) === $pairAt && $pairAt[0] < $pairAt[1] && $pairAt[3] < $pairAt[2] && substr_count( $bundleOrder, $sheetText ) === 1 );

// A project with no feature that brings a stylesheet: Design alone
$featuresOn( [ 'Design' ] );
$siteWith( [] );
\Nino\Features::registerAssets( $appData );
$siteOff 	= array_values( \Nino\Html::getAssets( $appData, '/.cache/style.css' ) );
$pageOff 	= $pageOfPreview();
$featuresOn( [ 'Design', 'Probe' ] );

check( 'with no stylesheet of a feature in the list - Design alone, which brings none - the preview has no link to the bundle of the features: a bundle over nothing is never written, and a link to it would ask for a file that is not there',
	array_filter( $siteOff, static fn( string $path ): bool => str_starts_with( $path, '/features/' ) === true ) === [] && str_contains( $pageOff, 'design-preview.css' ) === true
	&& str_contains( $pageOff, 'id="'. Admin::PREVIEW_STYLE. '"' ) === true && str_contains( $pageOff, 'design-preview-features' ) === false && \Nino\Html::getAssets( $appData, Admin::FEATURES_CSS ) === [] );

unset( $appData['/nino/html/assets'] );

// design/save keeps the choices, and the ones kept for a feature that is not there
[ $status, $savedFeatures ] = callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'features' => $withEntry['features'] + [ 'gone' => [ 'set' => 'x', 'knobs' => [ 'a' => 1 ] ] ] ] ) );
$afterSave = Setup::read( $appData, $library );

check( 'design/save stores the choices for the features, normalised, with the choice kept for a feature that is not there - the note says it - and in the order of the keys',
	$status === 200 && array_keys( $afterSave['features'] ) === [ 'gone', 'probe' ] && $afterSave['features']['probe'] === $withEntry['features']['probe'] && $afterSave['features']['gone'] === [ 'set' => 'x', 'knobs' => [ 'a' => 1 ] ]
	&& $savedFeatures['notes'] === [ Setup::inactive( 'gone' ) ] );
check( '...and a save of the screen that chooses nothing for the features takes the choices away, and so does a request that carries a key that is no array',
	( static function() use ( &$appData, $postedLoud, $library ): bool {
		callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'features' => [] ] ) );
		$cleared = Setup::read( $appData, $library )['features'] === [];
		callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'features' => 'loud' ] ) );
		return $cleared === true && Setup::read( $appData, $library )['features'] === [];
	} )() === true );

// Themes: a theme of the project is the whole setup, and holds the choices for the features
callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'features' => $withEntry['features'] ] ) );
$snapshot = Themes::snapshot( Setup::read( $appData, $library ) );
Themes::write( $appData, 'mit-features', Themes::record( 'Mit Features', '', Setup::normalize( $appData, [], $library ), '' ) );
$made = [ 'slug' => 'mit-features' ];
[ $status ] = callDesignAction( $appData, 'apiThemeSave', array_merge( $postedLoud, [ 'slug' => 'mit-features', 'features' => $withEntry['features'] ] ) );
$madeFile = Themes::read( $appData, $library, 'mit-features' ) ?? [];
[ , $reloaded ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'mit-features' ] );

check( 'a snapshot of a setup keeps the choices for the features as a key that chooses something, between the knobs and the fonts, where Setup::KEYS puts it - and a theme of the project the screen was saved into holds them in its file and gives them back on a load',
	array_keys( $snapshot ) === array_values( array_diff( Setup::KEYS, [ 'format', 'theme', 'css', 'compiled' ] ) ) && array_search( 'features', array_keys( $snapshot ), true ) === array_search( 'knobs', array_keys( $snapshot ), true ) + 1
	&& $snapshot['features'] === $withEntry['features'] && $status === 200 && ( $madeFile['setup']['features'] ?? null ) === $withEntry['features'] && ( $reloaded['setup']['features'] ?? null ) === $withEntry['features'] );

Themes::remove( $appData, (string) ( $made['slug'] ?? '' ) );

// A theme sets the choices for the features: what it names is what the screen has after a load, every other feature is back at its own look, and an entry for a feature that is not there is left out and said
$byHand 			= static fn( string $name, array $features ): array => [ 'format' => Themes::FORMAT, 'name' => $name, 'setup' => [ 'features' => $features ] ];
$choiceGone 	= [ 'set' => 'x', 'knobs' => [ 'a' => 1 ] ];
callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'features' => $withEntry['features'] ] ) );
[ $status, $overShipped ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => Themes::SHIPPED[0] ] );
Themes::write( $appData, 'by-hand', $byHand( 'By hand', [ 'probe' => [ 'set' => 'loud' ] ] ) );
Themes::write( $appData, 'one-gone', $byHand( 'One gone', [ 'probe' => [ 'set' => 'loud' ], 'gone' => $choiceGone ] ) );
[ , $fromHand ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'by-hand' ] );
[ , $oneGone ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'one-gone' ] );

check( 'a theme sets the choices for the features: loaded over a setup that chooses a variant of a feature, the theme the feature ships answers its own choices and not the setup\'s - the key is there, between the knobs and the fonts, with no entry for that feature, so it is back at its own look - and says nothing, and writes nothing',
	$status === 200 && array_key_exists( 'features', $overShipped['setup'] ) === true && array_key_exists( 'probe', $overShipped['setup']['features'] ) === false && $overShipped['notes'] === []
	&& array_search( 'features', array_keys( $overShipped['setup'] ), true ) === array_search( 'knobs', array_keys( $overShipped['setup'] ), true ) + 1
	&& array_key_exists( 'probe', Setup::read( $appData, $library )['features'] ) === true );
check( '...and a theme that names a feature the project has gives it: the variant, with the knobs of the variant at the positions its head gives them - a file by hand names the variant alone - and nothing said',
	( $fromHand['setup']['features']['probe']['set'] ?? '' ) === 'loud' && array_keys( $fromHand['setup']['features']['probe']['knobs'] ?? [] ) === array_keys( Setup::features( $appData )['probe']['catalogue']['loud']['knobs'] )
	&& $fromHand['setup']['features']['probe']['knobs'] === Setup::normalize( $appData, [ 'features' => [ 'probe' => [ 'set' => 'loud' ] ] ], $library )['features']['probe']['knobs'] && $fromHand['notes'] === [] );
check( '...and an entry for a feature that is not there - not active, or without variants - is left out of the answer and said once, in a sentence that names the feature: the choice for the feature that is there stays',
	array_keys( $oneGone['setup']['features'] ) === [ 'probe' ] && count( $oneGone['notes'] ) === 1 && is_string( $oneGone['notes'][0] ) === true && str_contains( $oneGone['notes'][0], '"gone"' ) === true );

callDesignAction( $appData, 'apiSave', array_merge( $postedLoud, [ 'theme' => 'one-gone', 'features' => $withEntry['features'] ] ) );
[ , $listedGone ] = callDesignAction( $appData, 'apiList' );
[ $status, $brought ] = callDesignAction( $appData, 'apiThemeImport', [ 'json' => Themes::json( $byHand( 'Brought in', [ 'gone' => $choiceGone ] ) ) ] );
$broughtFile = Themes::read( $appData, $library, 'brought-in' ) ?? [];
[ , $broughtLoaded ] = callDesignAction( $appData, 'apiThemeLoad', [ 'slug' => 'brought-in' ] );

check( '...the record the screen is compared with is the setup of the load, the entry for the feature that is not there out of it as well - and an import is no taking over but a writing down: it keeps such a choice in the file, as the setup keeps one, for the day the feature is back, where a load of that file leaves it out',
	( $listedGone['active']['slug'] ?? '' ) === 'one-gone' && $listedGone['active']['setup']['features'] === $oneGone['setup']['features']
	&& $status === 200 && ( $broughtFile['setup']['features']['gone'] ?? null ) === $choiceGone && $broughtLoaded['setup']['features'] === [] && count( $broughtLoaded['notes'] ) === 1 );

foreach( [ 'by-hand', 'one-gone', 'brought-in' ] as $removeSlug )
	Themes::remove( $appData, $removeSlug );

// The answer the screen is drawn from, for design-js-smoke.js: the real one, with the fixture active and a choice for it and for a feature that is not there
Setup::write( $appData, $stored );
[ , $forFeatures ] = callDesignAction( $appData, 'apiList' );
$featuresListing = ninoSandboxDir( $appData ). '/design-features.json';
file_put_contents( $featuresListing, json_encode( $forFeatures, JSON_THROW_ON_ERROR ) );

check( 'the answer that is written down for the screen has what the screen reads of the features: the variants with their knobs of every kind, the choice, the choice kept for a feature that is not there, the merged table, the words of the two choices and the measure of the colours',
	array_keys( $forFeatures['features'] ) === [ 'probe', 'gone' ] && isset( $forFeatures['layout']['controls']['features/probe/set'] ) === true && $forFeatures['grounds'] === Colours::SURFACES && isset( $forFeatures['contrasts']['probe']['flip'] ) === true );

// Put the sandbox back as the sections after this one found it
if( $featureKept === null )
	@unlink( $featureFile );
else
	file_put_contents( $featureFile, $featureKept );

$appData['./nino/locales/current'] = $localeKept;
$appData['/nino/modules'] = $modulesKept;
unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
\Nino\Auth::logoutUser( $appData );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );

// --- The panel's script, where node is on the path ------------------------------
//
// design-js-smoke.js beside this file draws the screen over a dom stand-in and
// reads the controls, the line and the chip of the bar back off it; this suite
// runs it too where node is on the path, the way gallery-smoke.php and
// stats-smoke.php run theirs, so bin/check.sh and CI cover both halves in one go
$jsTest	= __DIR__. '/design-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: design-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( 'DESIGN_LISTING='. escapeshellarg( $listingFile ). ' DESIGN_FEATURES='. escapeshellarg( $featuresListing ). ' DESIGN_WEBFONTS='. escapeshellarg( $webfontsFile ). ' DESIGN_THEMES='. escapeshellarg( $themesFile ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );

	// A run that failed says what failed: the checks that did not hold, or what node stopped on
	if( $status !== 0 )
		$summary = implode( ' | ', array_slice( array_values( array_filter( $output, static fn( string $line ): bool => str_starts_with( $line, 'FAIL' ) === true || preg_match( '/Error|at .*\(/', $line ) === 1 ) ) ?: $output, 0, 4 ) );

	check( 'design-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

// ninoDone() removes the sandbox with Filesystem::removeDir(), which neither follows a link nor removes one: the link to the kernel made above kept the directory from being emptied, and it stayed behind in the temp directory after every run
unlink( ninoSandboxDir( $appData ). '/_nino' );

ninoDone( $appData );
