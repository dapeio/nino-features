<?php
declare(strict_types=1);

/**
 *	Nino
 *	design-smoke.php		Contract test for the Design feature (Modules\Design):
 *											the manifest and activation through \Nino\Features, the
 *											setup's normalisation against a library that is really
 *											there, what the compiler puts in the sheet and in which
 *											order, the step a part is compiled at, the refusal to
 *											overwrite a stylesheet Design did not write, and that the
 *											token layer the feature ships still matches the one the
 *											setup wizard delivers. Travels with the feature and runs
 *											against the checkout three levels up, or the one NINO_ROOT
 *											names (see tests/harness.php there). What the panel's own
 *											script draws is design-js-smoke.js beside this file, which
 *											this suite runs too where node is on the path.
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

echo "The manifest, and what it promises\n";

$feature = \Nino\Features::manifest( dirname( __DIR__ ) );

check( 'the manifest reads, with the key and the class the directory implies', is_array( $feature ) === true
	&& $feature['key'] === 'design' && $feature['module'] === '\\Nino\\Modules\\Design' );
// ^1.4: the frames read words of the kernel's - /project/company/..., the
// words of /template/frame-header and /template/frame-footer, the page
// details after /_nino/webpage - and writes the project's frame-header.tpl
// and frame-footer.tpl, which are named so from 1.4 on. A kernel before it
// has neither the words nor the files. A pre-release of 1.4 counts as 1.4
check( 'it names ^1.4 - the kernel that has the words its frames read', ( $feature['nino'] ?? '' ) === '^1.4' );
check( '...and no 1.3 can run it, a 1.4 pre-release can', \Nino\Features::satisfies( (string) $feature['nino'], '1.3.2' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.2.0-beta' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.4.0-dev' ) === true );
check( 'the setup file is declared under data, so a backup carries it', in_array( \Nino\Modules\Design\Setup::PATH, (array) ( $feature['data'] ?? [] ), true ) === true );

echo "\nThe library it ships\n";

foreach( \Nino\Modules\Design\Setup::PARTS as $part => $kind ) {
	$available = \Nino\Modules\Design\Setup::available( $library, $part );
	check( 'the library offers at least one set for "'. $part. '"', $available !== [] );
}

check( 'a frame offers its template and its stylesheet', \Nino\Modules\Design\Setup::file( $library, 'header', 'v1', 'template' ) !== ''
	&& \Nino\Modules\Design\Setup::file( $library, 'header', 'v1' ) !== '' );
check( 'a set name that is not a set name resolves to nothing', \Nino\Modules\Design\Setup::file( $library, 'section', '../../../etc/passwd' ) === ''
	&& \Nino\Modules\Design\Setup::file( $library, 'section', 'v1/../v1' ) === ''
	&& \Nino\Modules\Design\Setup::file( $library, 'section', '' ) === '' );

/*	The library is written by hand and grows by hand, so what is held here is
	what a new file gets wrong - and gets wrong quietly. A variant with no
	@name is offered under its file name, two under one name are two rows
	nobody can tell apart, a knob outside Setup::KNOBS is a handle the panel
	will never show, a triple missing a step is a knob position that compiles
	to nothing, and a rule reading a token the file never declared is a
	declaration that silently does not apply.

	Written against the shipped library rather than a fixture on purpose: a
	fixture would prove the rule and let the files drift.	*/
foreach( \Nino\Modules\Design\Setup::PARTS as $part => $kind ) {

	$catalogue	= \Nino\Modules\Design\Setup::catalogue( $library, $part );
	$names			= array_column( $catalogue, 'name' );
	$described	= array_filter( $catalogue, static fn( array $entry ): bool => trim( $entry['description'] ) !== '' );

	check( '"'. $part. '" offers a choice rather than a single variant', count( $catalogue ) > 1 );
	check( '...every one of them named, and no two the same', count( array_unique( $names ) ) === count( $names )
		&& count( array_filter( $names, static fn( string $name ): bool => preg_match( '/^v[0-9]+$/', $name ) === 1 ) ) === 0 );
	check( '...and every one of them saying what it is', count( $described ) === count( $catalogue ) );

	$knobProblems	= [];
	$fileProblems	= [];

	foreach( array_keys( $catalogue ) as $set ) {

		$style = \Nino\Modules\Design\Setup::file( $library, $part, $set );

		if( $style === '' || ( $kind === 'frame' && \Nino\Modules\Design\Setup::file( $library, $part, $set, 'template' ) === '' ) ) {
			$fileProblems[] = $set;
			continue;
		}

		/*	Read the way the panel reads it, or the check is looser than the
			thing it is checking: Setup::knobs() looks for the token immediately
			followed by ':' (str_contains, KNOB_TOKEN. ':'), in a stylesheet with
			its comments taken out. A space before the colon is valid css and
			invisible to that, so '--section-measure--less : 42rem' declared a
			step the panel never finds - and a knob whose three steps are all
			written that way is one nobody can turn, with nothing saying so	*/
		$css = \Nino\Modules\Design\Setup::uncomment( (string) file_get_contents( $style ) );

		// What the file publishes: one triple per knob, named after the part
		preg_match_all( '/--'. preg_quote( $part, '/' ). '-([a-z]+)--(less|default|more):/', $css, $found );

		$steps = [];
		foreach( $found[1] as $index => $knob )
			$steps[$knob][] = $found[2][$index];

		foreach( $steps as $knob => $declared ) {

			if( in_array( $knob, \Nino\Modules\Design\Setup::KNOBS, true ) === false )
				$knobProblems[] = $set. ': "'. $knob. '" is not one of the knobs';

			sort( $declared );
			if( array_values( array_unique( $declared ) ) !== [ 'default', 'less', 'more' ] )
				$knobProblems[] = $set. ': "'. $knob. '" is not a triple';
		}

		// ...and what it reads. A rule on a token nothing declared is a rule
		// that does nothing, which is invisible until somebody moves the knob
		preg_match_all( '/var\(\s*--'. preg_quote( $part, '/' ). '-([a-z]+)\s*\)/', $css, $used );

		foreach( array_unique( $used[1] ) as $token )
			if( isset( $steps[$token] ) === false )
				$knobProblems[] = $set. ': reads --'. $part. '-'. $token. ' without declaring it';
	}

	// Both lists carry the file and what is wrong with it; a failure that does
	// not say which of five variants it means is a search rather than a report
	check( '...each resolving to the files its kind is made of'. ( $fileProblems === [] ? '' : ' - '. implode( ', ', $fileProblems ) ), $fileProblems === [] );
	check( '...and every knob it publishes a real triple of a real knob'. ( $knobProblems === [] ? '' : ' - '. implode( ' | ', $knobProblems ) ), $knobProblems === [] );
}

/*	And the one thing reading the files cannot say: that each of them survives
	the compiler. Every variant of every part, one at a time, because a single
	setup naming all of them would prove only that the last one landed	*/
$compileProblems = [];

foreach( \Nino\Modules\Design\Setup::PARTS as $part => $kind )
	foreach( \Nino\Modules\Design\Setup::available( $library, $part ) as $set ) {

		$sheet = \Nino\Modules\Design\Compiler::compile( \Nino\Modules\Design\Setup::normalize( [
			'parts' => [ $part => [ 'set' => $set ] ],
		], $library ), $library );

		if( str_contains( $sheet, $part. ': '. $set ) === false )
			$compileProblems[] = $part. '/'. $set;
	}

check( 'every variant in the library compiles into the sheet under its own name', $compileProblems === [] );

/*	The token layer is shipped twice on purpose - the wizard's base unit
	delivers it, and this feature carries its own copy because _admin/install/
	is gone from a project by the time anything here recompiles. Two copies
	that drift would mean installing Design silently changes how a site looks,
	so they are held to each other wherever a checkout still has the installer */
$delivered = $root. '/_admin/install/library/base/assets/theme.css';

if( is_file( $delivered ) === false )
	echo "  --  this checkout has no installer library, so the token layer is not compared\n";
else {
	$theirs = (string) file_get_contents( $delivered );
	$cut 		= strpos( $theirs, '/* ---- 3.' );
	$ours 	= (string) file_get_contents( $library. '/base.css' );
	$oursAt	= strpos( $ours, '*/' );

	// Trimmed at both ends, because the feature's copy carries a header of its
	// own above the content - everything between is what has to match
	check( 'the token layer this feature ships is the one the wizard delivers, byte for byte', $cut !== false && $oursAt !== false
		&& trim( substr( $theirs, 0, $cut ) ) === trim( substr( $ours, $oursAt + 3 ) ) );
}

/*	The link to the imprint and the privacy policy is a menu of the project: the
	one the Legal module's unit creates and the base unit's footer frame outputs
	(Nino 1.4). Every footer frame outputs it the same way, and none includes
	html-footer-legal, the template that is no longer delivered - it answers ''
	for a file that is not there, and the frame would show no link at all	*/
$legalNav			= '[navigation nav="legal" id="legal__nav"][/navigation]';
$legalProblems	= [];
$footers			= \Nino\Modules\Design\Setup::available( $library, 'footer' );

foreach( $footers as $set ) {

	$markup = (string) file_get_contents( \Nino\Modules\Design\Setup::file( $library, 'footer', $set, 'template' ) );

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
	foreach( \Nino\Modules\Design\Setup::PARTS as $part => $kind ) {

		if( $kind !== 'frame' )
			continue;

		foreach( \Nino\Modules\Design\Setup::available( $library, $part ) as $set ) {
			preg_match_all( '/\[\[(\/[^\[\]]+)\]\]/', (string) file_get_contents( \Nino\Modules\Design\Setup::file( $library, $part, $set, 'template' ) ), $fills );
			foreach( array_unique( $fills[1] ) as $key )
				if( isset( $shipped[$key] ) === false )
					$unshipped[] = $part. '/'. $set. ': '. $key;
		}
	}

	check( 'every fill a frame reads is one the base unit ships or the kernel fills'. ( $unshipped === [] ? '' : ' - '. implode( ', ', $unshipped ) ), $unshipped === [] );
}

echo "\nThe setup, held against the library that is there\n";

$notes = [];
$setup = \Nino\Modules\Design\Setup::normalize( [], $library, $notes );

check( 'a setup out of nothing is every part on its first set, no deviations, the delivered size', $notes === []
	&& $setup['knobs'] === array_fill_keys( \Nino\Modules\Design\Setup::KNOBS, 'default' )
	&& $setup['size'] === 'm'
	&& $setup['parts']['section']['knobs'] === []
	&& $setup['parts']['header']['set'] === 'v1' );

$notes = [];
$setup = \Nino\Modules\Design\Setup::normalize( [ 'parts' => [ 'section' => [ 'set' => 'v99' ] ] ], $library, $notes );

check( 'a part naming a set the library does not have falls back, and says which', $setup['parts']['section']['set'] === 'v1'
	&& count( $notes ) === 1 && str_contains( $notes[0], 'v99' ) === true );

$notes = [];
$setup = \Nino\Modules\Design\Setup::normalize( [
	'knobs' => [ 'spacing' => 'sideways', 'nonsense' => 'more' ], 'size' => 'xxl',
	'parts' => [ 'article' => [ 'knobs' => [ 'spacing' => 'nope' ] ] ],
], $library, $notes );

check( 'a knob position that is not one is the default, not an error',
	$setup['knobs']['spacing'] === 'default' && $setup['size'] === 'm'
	&& $setup['parts']['article']['knobs'] === [] );
check( '...and a knob that is not one is not a knob', isset( $setup['knobs']['nonsense'] ) === false );

// A setup written before the knobs were told apart carries one position for
// everything; it seeds every knob rather than being thrown away
$seeded = \Nino\Modules\Design\Setup::normalize( [ 'step' => 'more' ], $library );
check( 'a setup from before this knew one knob from another keeps its position',
	$seeded['knobs'] === array_fill_keys( \Nino\Modules\Design\Setup::KNOBS, 'more' ) );

$setup = \Nino\Modules\Design\Setup::normalize( [
	'knobs' => [ 'spacing' => 'more' ],
	'parts' => [ 'article' => [ 'knobs' => [ 'spacing' => 'less' ] ] ],
], $library );

check( 'a part with no position of its own follows the global one', \Nino\Modules\Design\Setup::step( $setup, 'section', 'spacing' ) === 'more' );
check( '...and a part that names one does not', \Nino\Modules\Design\Setup::step( $setup, 'article', 'spacing' ) === 'less' );
check( '...while every other knob of that part keeps following', \Nino\Modules\Design\Setup::step( $setup, 'article', 'shaping' ) === 'default' );

echo "\n";
echo "What the compiler produces\n";

$notes 	= [];
$setup 	= \Nino\Modules\Design\Setup::normalize( [], $library );
$css 		= \Nino\Modules\Design\Compiler::compile( $setup, $library, $notes );

check( 'compiling a default setup reports nothing missing', $notes === [] );
check( 'the sheet opens with a header naming what made it and forbidding hand edits', str_starts_with( $css, '/*' ) === true
	&& str_contains( $css, 'Generated by the Design feature' ) === true && str_contains( $css, 'Do not edit' ) === true );
check( '...and the header lists the chosen set of every part', array_filter( array_keys( \Nino\Modules\Design\Setup::PARTS ),
	static fn( string $part ): bool => preg_match( '/^ \* +'. $part. ' +v/m', $css ) !== 1 ) === [] );

// The order is the whole contract: tokens before roles before frames before
// sets, and the knob block last because it reads what the sets declared
$order = [];
if( preg_match_all( '/\/\* ==== (\d+)\. (.+?) ==== \*\//', $css, $sections, PREG_SET_ORDER ) > 0 )
	foreach( $sections as $section )
		$order[] = trim( $section[2] );

check( 'the tokens come first, then the palette, then the root size', ( $order[0] ?? '' ) === 'the design tokens and their roles'
	&& ( $order[1] ?? '' ) === 'the palette' && ( $order[2] ?? '' ) === 'the root size' );
check( '...then the frames, then the sets, in the order the parts are declared in, each saying where its knobs stand', array_slice( $order, 3, 9 ) === [
	'header: v1', 'footer: v1',
	'atf: v1 (volume default, spacing default)',
	'section: v1 (volume default, spacing default)',
	'article: v1 (volume default, spacing default, shaping default)',
	'buttons: v1 (spacing default, shaping default)',
	'forms: v1 (spacing default, shaping default)',
	'lists: v1 (spacing default, shaping default)',
	'blocks: v1 (spacing default, shaping default)',
] );
check( 'the token layer really is in there', str_contains( $css, '--nino-default:' ) === true && str_contains( $css, '--color-title:' ) === true );
check( 'the root size is the relative pair, never a length', str_contains( $css, '--nino-base-size: 100%;' ) === true
	&& str_contains( $css, '@media (min-width: 768px) { :root { --nino-base-size: 112.5%; } }' ) === true
	&& preg_match( '/--nino-base-size:\s*\d+px/', $css ) !== 1 );

echo "\n";


// --- The palette -----------------------------------------------------------

echo "Colours - the surfaces a look bands with, solved rather than written\n";

/**	Every --nino-* declaration inside a stretch of css, as key => value */
function designDecls( string $css ): array {
	$out = [];
	if( preg_match_all( '/(--nino-[a-z0-9-]+)\s*:\s*([^;]+);/', $css, $m ) === 1 || $m[1] !== [] )
		foreach( $m[1] as $i => $key )
			$out[$key] = trim( $m[2][$i] );
	return $out;
}

$base				= (string) file_get_contents( $library. '/base.css' );
$baseLight	= designDecls( substr( $base, (int) strpos( $base, ':root {' ), (int) strpos( $base, '@media (min-width' ) - (int) strpos( $base, ':root {' ) ) );
$darkAt			= (int) strpos( $base, ':root[data-nino-mode="dark"] {' );
$baseDark		= designDecls( substr( $base, $darkAt, (int) strpos( $base, '}', (int) strpos( $base, '--nino-scrim', $darkAt ) ) - $darkAt ) );

$colours		= \Nino\Modules\Design\Colours::css( [] );
$genLight		= designDecls( substr( $colours, 0, (int) strpos( $colours, '@media' ) ) );
$genDark		= designDecls( substr( $colours, (int) strpos( $colours, ':root[data-nino-mode="dark"]' ) ) );

/*	The property that makes the tab adoptable, and the reason the maths was
	lifted unchanged rather than rewritten: with nothing touched, the solver
	lands on the framework's own palette to the byte. A project that never
	opens this tab therefore compiles to the colours library/base.css already
	declares, and one that does gets the same tokens one block further down the
	same cascade	*/
check( 'the untouched palette reproduces base.css exactly, light', $genLight !== [] && array_intersect_key( $baseLight, $genLight ) === $genLight );
check( '...and dark', $genDark !== [] && array_intersect_key( $baseDark, $genDark ) === $genDark );
check( 'both blocks publish the full surface vocabulary', count( $genLight ) === count( $genDark )
	&& count( $genLight ) === count( \Nino\Modules\Design\Colours::SURFACES ) * 10 + 1 );

/*	Three reader states, not two: an explicit choice stamps data-nino-mode, the
	default "follow the system" setting stamps nothing at all - so the media
	query has to carry the unstamped case while the attribute rule wins in both
	directions once somebody has chosen	*/
check( 'the light palette is the bare :root', str_starts_with( $colours, ":root {\n\t--nino-default:" ) === true );
check( 'the system default is carried by the media query', str_contains( $colours, '@media (prefers-color-scheme: dark) {' ) === true
	&& str_contains( $colours, ':root:not([data-nino-mode="light"]) {' ) === true );
check( '...and an explicit choice wins in both directions', str_contains( $colours, ':root[data-nino-mode="dark"] {' ) === true );
check( 'no color-scheme declaration of its own - base.css sets that, this only replaces values', preg_match( '/^\s*color-scheme\s*:/m', $colours ) !== 1 );

/*	The one line in the engine that is not negotiable. 4.5:1 is WCAG 2.2
	SC 1.4.3 for body copy, and it holds for every solved surface in both
	modes. brand and accent are the two exceptions and say so: they are the
	colours the picker returned, byte for byte, so there is no lightness left
	to solve with - which is exactly why the two -safe roles exist	*/
$unsafe = [];

foreach( [ 'light', 'dark' ] as $mode )
	foreach( \Nino\Modules\Design\Colours::palette( [], $mode ) as $surface => $values )
		if( in_array( $surface, [ 'brand', 'accent' ], true ) === false
			&& \Nino\Modules\Design\Colours::contrast( $values['on'], $values['bg'] ) < 4.5 )
			$unsafe[] = $mode. '/'. $surface;

check( 'every solved surface clears 4.5:1 in both modes', $unsafe === [] );

$corporate = [ 'primary' => '#8b1d3f', 'secondary' => '#0f766e' ];
$picked2 = \Nino\Modules\Design\Colours::palette( $corporate, 'light' );

check( 'the brand is the hex that was typed, untouched', $picked2['brand']['bg'] === '#8b1d3f' );
check( '...and so is the second colour', $picked2['accent']['bg'] === '#0f766e' );
check( '...while their -safe roles are the same colours solved until text survives',
	\Nino\Modules\Design\Colours::contrast( $picked2['brand-safe']['on'], $picked2['brand-safe']['bg'] ) >= 4.5
	&& \Nino\Modules\Design\Colours::contrast( $picked2['accent-safe']['on'], $picked2['accent-safe']['bg'] ) >= 4.5 );
check( 'a corporate hex still clears the target on every solved surface',
	array_filter( \Nino\Modules\Design\Colours::palette( $corporate, 'dark' ), static fn( array $v, string $k ): bool =>
		in_array( $k, [ 'brand', 'accent' ], true ) === false
		&& \Nino\Modules\Design\Colours::contrast( $v['on'], $v['bg'] ) < 4.5, ARRAY_FILTER_USE_BOTH ) === [] );

/*	...and the one value the panel needs published on its own: the second
	colour as it will be compiled. A Secondary nobody set is not a missing
	value but a derived one, and the swatch standing for it has to show the
	colour the wheel produced rather than the primary standing in for it	*/
check( 'the second colour is published, as typed where one was typed',
	\Nino\Modules\Design\Colours::accent( $corporate ) === '#0f766e' );

$wheel = [ 'primary' => '#8b1d3f' ];
$derived = [];

foreach( [ 1, 2, 3, 4 ] as $harmony )
	$derived[$harmony] = \Nino\Modules\Design\Colours::accent( $wheel + [ 'harmony' => $harmony ] );

check( '...and where none was, it is what Harmony carried round the wheel - a different colour at every position but the first',
	$derived[1] === '#8b1d3f' && count( array_unique( $derived ) ) === 4
	&& preg_match( '/^#[0-9a-f]{6}$/', $derived[4] ) === 1 );
check( '...which is exactly the accent the palette itself uses, so the swatch cannot drift from the stylesheet',
	$derived[3] === \Nino\Modules\Design\Colours::palette( $wheel + [ 'harmony' => 3 ], 'light' )['accent']['bg'] );

// Status hues are fixed on purpose - no brand knob may turn a danger surface
// into something reassuring
$hot = \Nino\Modules\Design\Colours::palette( [ 'primary' => '#2e7d32' ], 'light' );
check( 'red stays red whatever the brand is', $hot['danger']['bg'] !== $hot['success']['bg']
	&& \Nino\Modules\Design\Colours::oklch( $hot['danger']['bg'] )[2] < 1.0 );

// ...and a knob that moves has to move something
$flat = \Nino\Modules\Design\Colours::css( [ 'depth' => 1 ] );
$neutral = \Nino\Modules\Design\Colours::css( [ 'temperature' => 1 ] );
check( 'Depth moves the alternate ground', designDecls( $flat )['--nino-alt'] !== $genLight['--nino-alt'] );
check( 'Temperature takes the colour out of the greys at Neutral', designDecls( $neutral )['--nino-alt'] !== $genLight['--nino-alt'] );
check( 'an unknown knob position falls back rather than indexing a table with it',
	\Nino\Modules\Design\Colours::normalize( [ 'contrast' => 99, 'primary' => 'nonsense' ] ) === \Nino\Modules\Design\Colours::normalize( [] ) );

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
	SC 1.4.11, and a Raised border 7:1 where the ground leaves room for one. The brand and accent
	surfaces are the two that are the colour that was picked, so as in the
	check above they are not measured for text.	*/
$reach = [];
$reached = 0;

foreach( [ '#4faae8', '#8b1d3f', '#facc15', '#111827' ] as $primary )
	foreach( [ 1, 2, 3 ] as $saturation )
		foreach( [ 1, 2, 3 ] as $contrast )
			foreach( [ 1, 2, 3 ] as $depth )
				foreach( [ 'light', 'dark' ] as $mode ) {

					$settings = [ 'primary' => $primary, 'saturation' => $saturation, 'contrast' => $contrast, 'depth' => $depth ];
					$label		= $primary. ' sat '. $saturation. ' con '. $contrast. ' dep '. $depth. ' '. $mode;

					foreach( \Nino\Modules\Design\Colours::palette( $settings, $mode ) as $surface => $v ) {

						$reached++;

						if( in_array( $surface, [ 'brand', 'accent' ], true ) === true )
							continue;

						$ratio = static fn( string $key ): float => \Nino\Modules\Design\Colours::contrast( $v[$key], $v['bg'] );
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
	return \Nino\Modules\Design\Colours::oklch( \Nino\Modules\Design\Colours::palette( [ 'primary' => '#4faae8', 'saturation' => $saturation ], 'light' )['brand-safe']['bg'] )[1];
};

check( 'Saturation never takes colour away as it goes up, and Muted and Rich are apart from Standard',
	$chromaAt( 1 ) <= $chromaAt( 2 ) && $chromaAt( 2 ) <= $chromaAt( 3 ) && $chromaAt( 1 ) < $chromaAt( 2 ) - 0.02 );

$depthOf = static fn( int $depth, string $mode ): array => \Nino\Modules\Design\Colours::palette( [ 'depth' => $depth ], $mode );

foreach( [ 'light', 'dark' ] as $depthMode ) {
	check( 'Depth 1 is not Depth 2 in '. $depthMode. ': neither the band nor the shadow is the same',
		$depthOf( 1, $depthMode )['alt']['bg'] !== $depthOf( 2, $depthMode )['alt']['bg']
		&& $depthOf( 1, $depthMode )['default']['shadow'] !== $depthOf( 2, $depthMode )['default']['shadow'] );
	// Flat is flatter, not gone: with no band at all the alternate sections
	// vanish into the page
	$band = \Nino\Modules\Design\Colours::contrast( $depthOf( 1, $depthMode )['alt']['bg'], $depthOf( 1, $depthMode )['default']['bg'] );
	check( '...and Flat keeps a trace of the band in '. $depthMode. ' ('. round( $band, 3 ). ':1 against the page)', $band >= 1.015 && $band < 1.05 );
}

/*	The scrim over a cover photograph has a table of its own. At Strong the
	text asks 10:1, and a scrim solved to that would take a photograph from 80%
	dark to about 91% - nearly black - for a headline that is far above AAA
	already at 7:1	*/
$scrimAt = static function( int $contrast ): array {
	preg_match_all( '/--nino-scrim:\s*rgb\([^\/]+\/\s*(\d+)%\)/', \Nino\Modules\Design\Colours::css( [ 'contrast' => $contrast ] ), $found );
	return array_map( 'intval', $found[1] );
};

check( 'Strong does not darken a cover photograph beyond 7:1 - the scrim stays at or under 80%, in both modes',
	$scrimAt( 3 ) !== [] && max( $scrimAt( 3 ) ) <= 80 );
check( 'the revision of the colour tables is a number that can be raised', \Nino\Modules\Design\Colours::REVISION >= 1 );

echo "\n";
echo "Compiler - the sheet a setup produces\n";

$large = \Nino\Modules\Design\Compiler::compile( \Nino\Modules\Design\Setup::normalize( [ 'size' => 'l' ], $library ), $library );
check( 'the size knob moves the pair, not one half of it', str_contains( $large, '--nino-base-size: 112.5%;' ) === true
	&& str_contains( $large, '--nino-base-size: 131.25%;' ) === true );

/*	A set declares what its three steps are; the knob picks one. Written into a
	throwaway library so the assertion does not depend on what the shipped sets
	happen to declare today */
$sandbox = ninoSandboxDir( $appData ). '/library';
mkdir( $sandbox. '/sets/section', 0755, true );
mkdir( $sandbox. '/sets/buttons', 0755, true );
file_put_contents( $sandbox. '/base.css', ":root { --x: 1; }\n" );
file_put_contents( $sandbox. '/sets/section/v1.css', ":root {\n\t--section-volume--less: 1.6rem;\n\t--section-volume--default: 2rem;\n\t--section-volume--more: 2.6rem;\n}\n.nino-section-title { font-size: var(--section-volume); }\n" );
file_put_contents( $sandbox. '/sets/buttons/v1.css', ":root {\n\t--buttons-shaping--less: 0;\n\t--buttons-shaping--default: .4rem;\n\t--buttons-shaping--more: 2rem;\n}\n" );

$picked = \Nino\Modules\Design\Compiler::compile( \Nino\Modules\Design\Setup::normalize( [
	'knobs' => array_fill_keys( \Nino\Modules\Design\Setup::KNOBS, 'more' ),
], $sandbox ), $sandbox );

check( 'every knob a set answers to becomes one selection line', str_contains( $picked, '--section-volume: var(--section-volume--more);' ) === true
	&& str_contains( $picked, '--buttons-shaping: var(--buttons-shaping--more);' ) === true );
check( '...and a knob no set answers to becomes nothing at all',
	str_contains( $picked, '--section-shaping:' ) === false && str_contains( $picked, '--buttons-volume:' ) === false );
check( '...gathered into one block at the end, not scattered beside the sets', substr_count( $picked, 'the knob positions' ) === 1
	&& strpos( $picked, 'the knob positions' ) > strpos( $picked, '--section-volume--more' ) );
check( '...and the three steps themselves are still in the sheet, so a project without the feature can move the knob by hand',
	str_contains( $picked, '--section-volume--less: 1.6rem;' ) === true && str_contains( $picked, '--section-volume--more: 2.6rem;' ) === true );

$deviating = \Nino\Modules\Design\Compiler::compile( \Nino\Modules\Design\Setup::normalize( [
	'knobs' => array_fill_keys( \Nino\Modules\Design\Setup::KNOBS, 'more' ),
	'parts' => [ 'buttons' => [ 'knobs' => [ 'shaping' => 'less' ] ] ],
], $sandbox ), $sandbox );

check( 'a part that deviates is compiled at its own position, the rest at the global one', str_contains( $deviating, '--buttons-shaping: var(--buttons-shaping--less);' ) === true
	&& str_contains( $deviating, '--section-volume: var(--section-volume--more);' ) === true );

/*	What the panel compares to say whether the stylesheet still answers to the
	screen: the setup's own choices AND the bytes of every library file they
	point at, so a set that changed under a project - a reinstall, an edited
	library - is visible rather than silent. This is the whole of it; the
	digest apply() used to write beside each part was never read by anything	*/
$sandboxSetup 	= \Nino\Modules\Design\Setup::normalize( [ 'parts' => [ 'section' => [ 'set' => 'v1' ] ] ], $sandbox );
$beforeSetEdit 	= \Nino\Modules\Design::fingerprint( $sandboxSetup, $sandbox );
$sectionSet 		= $sandbox. '/sets/section/v1.css';
$sectionSource 	= (string) file_get_contents( $sectionSet );
file_put_contents( $sectionSet, $sectionSource. "\n/* a set the project got from somewhere else */\n" );

check( 'the fingerprint follows the bytes of the sets it names, not only the names', \Nino\Modules\Design::fingerprint( $sandboxSetup, $sandbox ) !== $beforeSetEdit );
check( '...and a setup that still carries a digest per part from an older version fingerprints the same as one without', \Nino\Modules\Design::fingerprint( $sandboxSetup, $sandbox ) === \Nino\Modules\Design::fingerprint(
	\Nino\Modules\Design\Setup::normalize( [ 'parts' => [ 'section' => [ 'set' => 'v1', 'sha' => 'written by a version before this one' ] ] ], $sandbox ), $sandbox ) );

file_put_contents( $sectionSet, $sectionSource );

/*	The colour tables moved at positions 1 and 3 of three knobs and not at 2,
	so the fingerprint carries their revision for a project with one of those
	knobs off 2 and for no other: a project that never touched them compiles
	the same bytes as before and has no reason to read "not applied"	*/
$beforeRevision = static function( array $setup, string $lib ): string {
	$files = [];
	foreach( array_keys( \Nino\Modules\Design\Setup::PARTS ) as $printPart ) {
		$printSet 					= (string) ( $setup['parts'][$printPart]['set'] ?? '' );
		$printFile 					= \Nino\Modules\Design\Setup::file( $lib, $printPart, $printSet );
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

echo "\nWriting it, and what it will not write over\n";

$written = \Nino\Modules\Design\Compiler::write( $appData, $css );
check( 'the first write succeeds - there is no theme.css in a sandbox yet', $written === true );
check( '...and produces the file the css bundle already names', is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true );

$onDisk = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' );
check( 'a sheet Design wrote knows itself', \Nino\Modules\Design\Compiler::stamped( $onDisk ) === true );

$second = \Nino\Modules\Design\Compiler::write( $appData, \Nino\Modules\Design\Compiler::compile(
	\Nino\Modules\Design\Setup::normalize( [ 'size' => 'l' ], $library ), $library ) );
check( 'compiling again writes over its own file without being asked twice', $second === true
	&& str_contains( (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' ), '131.25%' ) === true );

// The delivered theme.css is explicitly editable by hand, so the file this
// finds on a first run may well be somebody's work
file_put_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css', "/* mine */\nbody { color: red; }\n" );
$refused = \Nino\Modules\Design\Compiler::write( $appData, $css );

check( 'a stylesheet Design did not write is not overwritten, and the refusal says why', $refused !== true
	&& str_contains( (string) $refused, 'not written by Design' ) === true );
check( '...and the file is still exactly what it was', (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === "/* mine */\nbody { color: red; }\n" );

check( 'an edit to a sheet Design did write is caught the same way', ( static function( array $appData, string $css ): bool {
	$path = ninoSandboxDir( $appData ). '/private/assets/theme.css';
	file_put_contents( $path, $css. "\nbody { color: blue; }\n" );
	return \Nino\Modules\Design\Compiler::stamped( (string) file_get_contents( $path ) ) === false;
} )( $appData, $css ) );

check( 'forcing it through overwrites anyway', \Nino\Modules\Design\Compiler::write( $appData, $css, true ) === true );

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

$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Setup::PATH, [] );

check( 'the setup is written beside it, with the format it is in', is_array( $stored ) === true && ( $stored['format'] ?? 0 ) === \Nino\Modules\Design\Setup::FORMAT );
check( '...recording what was compiled, so the panel can tell the file from the setup', ( $stored['compiled']['sha'] ?? '' ) !== '' && ( $stored['compiled']['at'] ?? '' ) !== '' );
/*	A part holds what was chosen and nothing worked out from it, which is what
	the manual promises of this file. apply() also wrote a digest of the set's
	file beside every part, normalize() carried it, fingerprint() took it back
	out again - and nothing anywhere ever read one. What a reinstall that
	changed a set has to be told by is 'compiled'['input'], which hashes those
	same files itself	*/
check( '...and a part records what was chosen and nothing derived from it', array_keys( (array) ( $stored['parts']['section'] ?? [] ) ) === [ 'set', 'knobs' ] );

/*	Which makes upgrade() the place a stored digest goes away: it reads the
	setup through normalize() and writes it back, and that is the whole of what
	an upgrade does here - a new version may ship changed sets, and moving a
	site nobody asked to move is not an upgrade	*/
$legacy = $stored;
$legacy['parts']['section']['sha'] = 'written by a version before this one';
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Design\Setup::PATH, $legacy );

check( 'upgrading drops a digest an older version stored and leaves the choices alone', \Nino\Modules\Design::upgrade( $appData, '0.1.0' ) === true );
$upgraded = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Setup::PATH, [] );
check( '...so the file holds the same choices, without it', array_keys( (array) ( $upgraded['parts']['section'] ?? [] ) ) === [ 'set', 'knobs' ]
	&& ( $upgraded['parts']['section']['set'] ?? '' ) === ( $stored['parts']['section']['set'] ?? '' )
	&& ( $upgraded['compiled']['input'] ?? '' ) === ( $stored['compiled']['input'] ?? '' ) );

/*	A frame is a stylesheet AND the markup it was drawn against. Compiling one
	without writing the other is how a page ends up with v3's css over v1's
	html, which is exactly what this did before the panel went looking */
// The two templates the kernel's base unit delivers, which html-header.tpl and html-footer.tpl include
check( 'the frames it writes are the base unit\'s frame-header.tpl and frame-footer.tpl', sprintf( \Nino\Modules\Design\Compiler::FRAME_TARGET, 'header' ) === '/templates/frame-header.tpl'
	&& sprintf( \Nino\Modules\Design\Compiler::FRAME_TARGET, 'footer' ) === '/templates/frame-footer.tpl' );
$headerTemplate = \Nino\Filesystem::path( $appData, sprintf( \Nino\Modules\Design\Compiler::FRAME_TARGET, 'header' ) );
check( 'applying writes the chosen frame\'s markup, not only its stylesheet',
	is_file( $headerTemplate ) === true
	&& str_contains( (string) file_get_contents( $headerTemplate ), 'nino-scroll-header' ) === true
	&& \Nino\Modules\Design\Compiler::stampedFrame( (string) file_get_contents( $headerTemplate ) ) === true );
check( '...and the markup is the variant the setup names', str_contains( (string) file_get_contents( $headerTemplate ), 'header v1' ) === true );

$byHand = "<header>my own</header>\n";
file_put_contents( $headerTemplate, $byHand );
$notes = [];
$stylesheetBefore = (string) file_get_contents( \Nino\Filesystem::path( $appData, \Nino\Modules\Design\Compiler::TARGET ) );
// A setup that would compile to something else, so "nothing was written"
// is a statement about this run rather than about two identical files
$sizedSetup = \Nino\Modules\Design\Setup::read( $appData, \Nino\Modules\Design::libraryDir(), $notes );
$sizedSetup['size'] = $sizedSetup['size'] === 'l' ? 'm' : 'l';
\Nino\Modules\Design\Setup::write( $appData, $sizedSetup );
$refusedFrame = \Nino\Modules\Design::apply( $appData, $notes );
check( 'a frame template somebody edited is not overwritten either', $refusedFrame !== true
	&& str_contains( (string) $refusedFrame, 'frame-header.tpl was not written by Design' ) === true
	&& file_get_contents( $headerTemplate ) === $byHand );
// "Nothing was overwritten" is what the refusal says, and it has to be true:
// the stylesheet used to be written first, so a hand-taken header left the
// project with a new theme.css, an old header and a message saying neither
check( '...and nothing else was written either, which is what the refusal says',
	(string) file_get_contents( \Nino\Filesystem::path( $appData, \Nino\Modules\Design\Compiler::TARGET ) ) === $stylesheetBefore );
$notes = [];
check( 'and forcing it through takes that one over too', \Nino\Modules\Design::apply( $appData, $notes, true ) === true
	&& \Nino\Modules\Design\Compiler::stampedFrame( (string) file_get_contents( $headerTemplate ) ) === true );

check( 'applying again needs no force - the file is now one of ours', \Nino\Modules\Design::apply( $appData ) === true );

check( 'the compiled sheet is what the bundle already points at', in_array( \Nino\Modules\Design\Compiler::TARGET,
	(array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), true ) === false
	&& \Nino\Modules\Design\Compiler::TARGET === '/assets/theme.css' );

check( 'deactivating leaves the compiled sheet and the setup where they are', \Nino\Features::deactivate( $appData, 'design' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/data/design.php' ) === true );

echo "\n";

echo "\nThe panel: what it lists, what it saves, and what it refuses to overwrite\n";

function callDesignAction( array &$appData, string $method, array $post = [] ): array {
	$_POST['data'] = json_encode( $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Design\Admin::$method( $appData, $request );
	$_POST = [];
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ];
}

\Nino\Auth::insertUser( $appData, 'dev@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::loginUser( $appData, 'dev@example.com', 'correct horse battery staple' );

[ $status, $listed ] = callDesignAction( $appData, 'apiList' );
check( 'the panel lists one row per part, in the order the cascade wants them', $status === 200
	&& array_column( (array) ( $listed['parts'] ?? [] ), 'part' ) === array_keys( \Nino\Modules\Design\Setup::PARTS ) );
check( 'a row carries the catalogue it can be given, and what is chosen',
	isset( $listed['parts'][0]['catalogue']['v1'] ) === true
	&& ( $listed['parts'][0]['set'] ?? null ) === 'v1'
	&& ( $listed['parts'][0]['kind'] ?? null ) === 'frame' );
check( 'every variant comes with the name and description its own file carries',
	( $listed['parts'][0]['catalogue']['v1']['name'] ?? '' ) === 'Plain bar'
	&& str_contains( (string) ( $listed['parts'][0]['catalogue']['v1']['description'] ?? '' ), 'rule under it' ) === true );
check( 'and the knob, the size and what they can be', ( $listed['steps'] ?? null ) === \Nino\Modules\Design\Setup::STEPS
	&& ( $listed['sizes'] ?? null ) === array_keys( \Nino\Modules\Design\Setup::SIZES ) );
check( 'a row names the knobs its own set answers to, and the screen the ones anything answers to',
	( $listed['parts'][3]['knobs'] ?? null ) === [ 'volume', 'spacing' ]
	&& ( $listed['parts'][0]['knobs'] ?? null ) === []
	&& ( $listed['global'] ?? null ) === [ 'volume', 'spacing', 'shaping' ] );
check( 'the file on disk is ours and answers to the setup', ( $listed['exists'] ?? null ) === true
	&& ( $listed['ours'] ?? null ) === true && ( $listed['current'] ?? null ) === true );

// Saving is not compiling: the decision lands, the stylesheet does not move
[ $status, $saved ] = callDesignAction( $appData, 'apiSave', [
	'parts' => [ 'section' => [ 'set' => 'v1', 'knobs' => [ 'spacing' => 'more', 'shaping' => 'less' ] ] ],
	'knobs' => [ 'spacing' => 'less' ], 'size' => 'l',
] );
$stored = \Nino\Modules\Design\Setup::read( $appData, \Nino\Modules\Design::libraryDir() );

check( 'saving stores the selection', $status === 200 && $stored['size'] === 'l' );
check( '...and says the file no longer answers to it, rather than moving it', ( $saved['current'] ?? null ) === false
	&& ( $saved['ours'] ?? null ) === true );
check( 'a part may deviate from a knob\'s global position, and one that names nothing follows it',
	\Nino\Modules\Design\Setup::step( $stored, 'section', 'spacing' ) === 'more'
	&& \Nino\Modules\Design\Setup::step( $stored, 'buttons', 'spacing' ) === 'less' );
check( '...and a position for a knob the part\'s set does not answer to is not kept',
	isset( $stored['parts']['section']['knobs']['shaping'] ) === false );

[ $status, $applied ] = callDesignAction( $appData, 'apiApply' );
check( 'applying compiles it and the file answers again', $status === 200 && ( $applied['current'] ?? null ) === true );
check( '...and the root size the save asked for is in the stylesheet', str_contains(
	(string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' ), '131.25%' ) === true );

// A hand edit makes the file somebody else's again, and the panel says so
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, "/* edited by hand */\n" );
[ , $foreign ] = callDesignAction( $appData, 'apiList' );
check( 'a file somebody edited reads as not ours', ( $foreign['ours'] ?? null ) === false && ( $foreign['exists'] ?? null ) === true );
check( 'and applying over it is a 409 with the reason, not a silent overwrite',
	callDesignAction( $appData, 'apiApply' ) === [ 409, [ 'error' => 'assets/theme.css was not written by Design - it is the delivered file, or somebody edited it. Nothing was overwritten.' ] ] );
check( '...the file is still exactly what it was',
	\Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' ) === "/* edited by hand */\n" );
check( 'forcing it through takes the file over', callDesignAction( $appData, 'apiApply', [ 'force' => true ] )[0] === 200
	&& \Nino\Modules\Design\Compiler::stamped( (string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' ) ) === true );

// A set that is not in the library is not written, and the answer says why
[ $status, $refused ] = callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'section' => [ 'set' => 'nope' ] ], 'step' => 'default', 'size' => 'm' ] );
check( 'a variant that is not in the library falls back and is named', $status === 200
	&& count( (array) ( $refused['notes'] ?? [] ) ) === 1
	&& str_contains( (string) ( $refused['notes'][0] ?? '' ), '"nope"' ) === true );
check( 'a set name that could climb out of the library never becomes a path',
	callDesignAction( $appData, 'apiSave', [ 'parts' => [ 'section' => [ 'set' => '../../../etc/passwd' ] ], 'step' => 'default', 'size' => 'm' ] )[0] === 200
	&& \Nino\Modules\Design\Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['parts']['section']['set'] === 'v1' );

// ...and neither does a size that is not a string. Cast, an array raises
// "Array to string conversion", a level the kernel treats as fatal - a 500
// on the panel's own save where a fallback is the answer
ninoWarnings();
check( 'a size posted as an array falls back rather than raising',
	callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'step' => 'default', 'size' => [ 'l' ] ] )[0] === 200
	&& ninoWarnings() === []
	&& \Nino\Modules\Design\Setup::read( $appData, \Nino\Modules\Design::libraryDir() )['size'] === 'm' );

echo "\nApplying asks first, keeps the version before, and restores it\n";

$slotPath	= \Nino\Filesystem::path( $appData, \Nino\Modules\Design\Previous::PATH );
$targets	= \Nino\Modules\Design\Previous::targets();
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
	in_array( \Nino\Modules\Design\Previous::PATH, (array) ( $feature['data'] ?? [] ), true ) === true );

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
$setupBefore = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Setup::PATH, [] );

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

$slot 		= \Nino\Modules\Design\Previous::read( $appData );
$applied 	= $onDisk();
$setupApplied = \Nino\Modules\Design\Setup::read( $appData, $library );

check( 'the previous version holds the old bytes of all three files', $slot !== null && $slot['files'] === $delivered );
check( '...and when it was kept - with no setup, because the delivered files were written under none of Design\'s: the record in data/design.php names another stylesheet', $slot !== null && $slot['setup'] === null
	&& ( $setupBefore['compiled']['sha'] ?? '' ) !== hash( 'sha256', $delivered['/assets/theme.css'] )
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T/', $slot['at'] ) === 1 );
check( '...while the files on disk are now Design\'s own',
	count( array_filter( $targets, static fn( string $target ): bool => \Nino\Modules\Design\Compiler::ownership( (string) $applied[$target], $target !== \Nino\Modules\Design\Compiler::TARGET ) === 'ours' ) ) === 3 );
check( 'plan afterwards finds every file ours and nothing to change',
	array_column( $planOf(), 'state' ) === [ 'ours', 'ours', 'ours' ] && array_filter( array_column( $planOf(), 'changes' ) ) === [] );

// Applying the same thing again must not replace the one version there is
// with a copy of the present. The date is fixed first, so "untouched" is a
// statement about the whole slot and not about a clock
$fixed = $slot;
$fixed['at'] = '2026-01-01T00:00:00+00:00';
\Nino\Modules\Design\Previous::write( $appData, $fixed );

check( 'applying again with nothing changed leaves the previous version exactly as it was',
	callDesignAction( $appData, 'apiApply' )[0] === 200 && \Nino\Modules\Design\Previous::read( $appData ) === $fixed
	&& $onDisk() === $applied );

// A restore is a swap
[ $status, $restored ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring puts the three files back byte for byte', $status === 200 && $onDisk() === $delivered );
check( '...and the slot now holds what was applied, so the swap can be undone',
	( \Nino\Modules\Design\Previous::read( $appData )['files'] ?? [] ) === $applied );
$afterApply = $setupApplied;
unset( $afterApply['compiled'] );
check( '...and with no setup in the slot only the record of what was compiled goes: the choices stay, and nothing claims the files answer to them',
	\Nino\Modules\Design\Setup::read( $appData, $library ) === $afterApply + [ 'compiled' => [] ] );
check( '...and the answer says what the files are now: not Design\'s, so not current',
	array_column( (array) ( $restored['files'] ?? [] ), 'state' ) === [ 'foreign', 'foreign', 'foreign' ] && ( $restored['current'] ?? null ) === false
	&& is_array( $restored['previous'] ?? null ) === true && ( $restored['previous']['files'] ?? [] ) === $targets );

// A request already failed by Csrf, which runs before an action, writes nothing
$_POST['data'] = '[]';
$failed = [ '/nino/http/response' => [ 'statusCode' => 403 ] ];
$slotNow = \Nino\Modules\Design\Previous::read( $appData );
\Nino\Modules\Design\Admin::apiRestore( $appData, $failed );
$_POST = [];

check( 'a restore the Csrf check already failed writes nothing',
	$failed['/nino/http/response']['statusCode'] === 403 && $onDisk() === $delivered && \Nino\Modules\Design\Previous::read( $appData ) === $slotNow );

check( 'restoring again returns to what was applied',
	callDesignAction( $appData, 'apiRestore' )[0] === 200 && $onDisk() === $applied
	&& ( \Nino\Modules\Design\Previous::read( $appData )['files'] ?? [] ) === $delivered );

// What came back is an older Design output with its own setup, which is a
// file that answers to the setup beside it. Without a setup in the slot
// there is nothing saying what the files were compiled from - and the panel
// does not say they are current
[ , $listed ] = callDesignAction( $appData, 'apiList' );
check( 'an older Design output that comes back with its setup is current, because the two belong together', ( $listed['current'] ?? null ) === true );

$slotNoSetup = \Nino\Modules\Design\Previous::read( $appData );
$slotNoSetup['setup'] = null;
\Nino\Modules\Design\Previous::write( $appData, $slotNoSetup );
callDesignAction( $appData, 'apiRestore' );
[ , $listed ] = callDesignAction( $appData, 'apiList' );

check( 'one that comes back with no setup to match it is not current - there is no record of what it was compiled from',
	( $listed['current'] ?? null ) === false && ( $listed['compiled'] ?? 'x' ) === '' );

// ...a restore the disk refuses is not half done
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );
$before 	= $onDisk();
$slotHeld = \Nino\Modules\Design\Previous::read( $appData );

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
	&& \Nino\Modules\Design\Previous::read( $appData ) === $slotHeld );

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
	&& \Nino\Modules\Design\Compiler::ownership( (string) file_get_contents( $footerPath ), true ) === 'ours' );
check( '...and the edited footer is in the previous version',
	str_contains( (string) ( \Nino\Modules\Design\Previous::read( $appData )['files']['/templates/frame-footer.tpl'] ?? '' ), '<p>[consent-settings]</p>' ) === true );

/*	The panel's own sequence, which is not apply on its own: _save(true) posts
	the draft, asks for the plan and only then applies. By the time the apply
	takes the snapshot, data/design.php already holds the NEW choices - and the
	version before is the choices the files were written under, not those	*/
$parts = static fn( string $header, string $footer ): array => [ 'header' => [ 'set' => $header ], 'footer' => [ 'set' => $footer ] ];
$sans = static function( array $setup ): array {
	unset( $setup['compiled'] );
	return $setup;
};

callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v1', 'v1' ), 'knobs' => [], 'size' => 'm', 'colours' => [] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply', [ 'force' => true ] );

$setupA = $sans( \Nino\Modules\Design\Setup::read( $appData, $library ) );
$filesA = $onDisk();

callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v3', 'v2' ), 'knobs' => [], 'size' => 'l', 'colours' => [ 'contrast' => 3 ] ] );
callDesignAction( $appData, 'apiPlan' );
callDesignAction( $appData, 'apiApply' );

$setupB = $sans( \Nino\Modules\Design\Setup::read( $appData, $library ) );
$filesB = $onDisk();

check( 'the two versions of the sequence differ in files, choices, size and colours', $filesA !== $filesB && $setupA !== $setupB
	&& $setupA['size'] === 'm' && $setupB['size'] === 'l' && $setupB['parts']['header']['set'] === 'v3' && $setupB['colours']['contrast'] === 3 );
check( 'the slot holds the choices the files were written under, not the draft the panel saved a moment before the apply',
	( \Nino\Modules\Design\Previous::read( $appData )['files'] ?? [] ) === $filesA
	&& $sans( (array) ( \Nino\Modules\Design\Previous::read( $appData )['setup'] ?? [] ) ) === $setupA );

[ $status, $back ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring after save, plan and apply brings back the files AND the setup: the choices, the size and the colours of the older version',
	$status === 200 && $onDisk() === $filesA && $sans( \Nino\Modules\Design\Setup::read( $appData, $library ) ) === $setupA );
check( '...and the panel reads that version as applied, with nothing "saved, not applied"', ( $back['current'] ?? null ) === true
	&& ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );

[ $status, $forth ] = callDesignAction( $appData, 'apiRestore' );

check( 'restoring again gives the newer version back, files and setup, and current',
	$status === 200 && $onDisk() === $filesB && $sans( \Nino\Modules\Design\Setup::read( $appData, $library ) ) === $setupB
	&& ( $forth['current'] ?? null ) === true );

// A draft saved over what was applied is not what the files answer to, and
// the next apply must not keep it as the version before
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v5', 'v3' ), 'knobs' => [], 'size' => 's', 'colours' => [] ] );
callDesignAction( $appData, 'apiApply' );

check( 'a third apply keeps what the second one wrote, with the choices that wrote it',
	( \Nino\Modules\Design\Previous::read( $appData )['files'] ?? [] ) === $filesB
	&& $sans( (array) ( \Nino\Modules\Design\Previous::read( $appData )['setup'] ?? [] ) ) === $setupB );

// A project that applied with the release before this one has a record
// without 'setup'. The draft saved over it cannot be told from the choices, so
// the slot keeps data/design.php whole with its record, and a restore reads
// "saved, not applied" - not "never applied", and not foreign
$wrote = \Nino\Modules\Design\Setup::read( $appData, $library );
unset( $wrote['compiled']['setup'] );
\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Design\Setup::PATH, $wrote );
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
$legacyOff = \Nino\Modules\Design\Setup::read( $appData, $library );
$legacyOff['colours']['contrast'] = 3;
\Nino\Modules\Design\Setup::write( $appData, $legacyOff );
callDesignAction( $appData, 'apiApply' );
$legacyOff = \Nino\Modules\Design\Setup::read( $appData, $library );
check( 'a project applied with Contrast at Strong reads current', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === true );
$legacyOff['compiled']['input'] = $beforeRevision( $legacyOff, $library );
\Nino\Modules\Design\Setup::write( $appData, $legacyOff );
check( '...and once it was applied before the revision existed it reads saved, not applied', ( callDesignAction( $appData, 'apiList' )[1]['current'] ?? null ) === false );

$legacyDefault = \Nino\Modules\Design\Setup::read( $appData, $library );
$legacyDefault['colours']['contrast'] = 2;
\Nino\Modules\Design\Setup::write( $appData, $legacyDefault );
callDesignAction( $appData, 'apiApply' );
$legacyDefault = \Nino\Modules\Design\Setup::read( $appData, $library );
$legacyDefault['compiled']['input'] = $beforeRevision( $legacyDefault, $library );
\Nino\Modules\Design\Setup::write( $appData, $legacyDefault );
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
$oldSlot	= \Nino\Modules\Design\Previous::read( $appData );

// The draft that changes all three files: what the apply writes, and so what it can fail at
callDesignAction( $appData, 'apiSave', [ 'parts' => $parts( 'v3', 'v3' ), 'knobs' => [], 'size' => 's', 'colours' => [] ] );
$draft = (string) file_get_contents( $pathOf( \Nino\Modules\Design\Setup::PATH ) );

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
		\Nino\Modules\Design\Previous::write( $appData, $oldSlot );
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
		'slot' 			=> \Nino\Modules\Design\Previous::read( $appData ),
		'setup' 		=> (string) file_get_contents( $pathOf( \Nino\Modules\Design\Setup::PATH ) ),
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
	&& ( \Nino\Modules\Design\Previous::read( $appData )['files'] ?? [] ) === $old );

// What the preview tests further down take for the stored setup
callDesignAction( $appData, 'apiSave', [ 'parts' => [], 'knobs' => [], 'size' => 'm' ] );

echo "\nThe knob: the framework's own vocabulary, and the two levels it asks\n";

check( 'a set publishes a knob by declaring its triple, and no other way',
	\Nino\Modules\Design\Setup::knobs( $library, 'section', 'v1' ) === [ 'volume', 'spacing' ]
	&& \Nino\Modules\Design\Setup::knobs( $library, 'buttons', 'v1' ) === [ 'spacing', 'shaping' ] );
check( 'a frame answers to none - a knob is about a set\'s own triples',
	\Nino\Modules\Design\Setup::knobs( $library, 'header', 'v1' ) === []
	&& \Nino\Modules\Design\Setup::knobs( $library, 'section', 'nope' ) === [] );
check( 'an example in a comment is documentation, not a declaration',
	in_array( 'measure', \Nino\Modules\Design\Setup::knobs( $library, 'section', 'v1' ), true ) === false );
check( 'the global position is a position of whatever any chosen set follows',
	\Nino\Modules\Design\Setup::knobsInUse( $library, \Nino\Modules\Design\Setup::defaults( $library ) ) === [ 'volume', 'spacing', 'shaping' ] );

$knobbed = \Nino\Modules\Design\Setup::normalize( [
	'knobs' => array_fill_keys( \Nino\Modules\Design\Setup::KNOBS, 'more' ),
	'parts' => [ 'section' => [ 'set' => 'v1', 'knobs' => [ 'spacing' => 'less' ] ] ],
], $library );

$notes 		= [];
$knobCss 	= \Nino\Modules\Design\Compiler::compile( $knobbed, $library, $notes );

check( 'the compiled sheet asks each knob where it stands for that part',
	str_contains( $knobCss, '--section-spacing: var(--section-spacing--less);' ) === true
	&& str_contains( $knobCss, '--section-volume: var(--section-volume--more);' ) === true
	&& str_contains( $knobCss, '--buttons-shaping: var(--buttons-shaping--more);' ) === true );

// The selection block alone: the sets read the same names in their own rules
$knobBlock = substr( $knobCss, (int) strpos( $knobCss, 'the knob positions' ) );

check( '...one line per knob a set answers to, and none for a frame, which answers to none',
	preg_match_all( '/^\t--section-[a-z-]+: var\(/m', $knobBlock ) === 2
	&& str_contains( $knobBlock, '--header-' ) === false
	&& preg_match_all( '/^\t--[a-z-]+: var\(/m', $knobBlock ) === 15 );
check( 'and the compiled header says where every knob stands, globally and per part',
	str_contains( $knobCss, 'section  v1  (spacing less)' ) === true
	&& str_contains( $knobCss, 'knobs    volume more, spacing more, shaping more, measure more' ) === true
	&& str_contains( $knobCss, 'header   v1'. "\n" ) === true );

// Every triple's --default is the framework's own value, so a knob nobody
// moved compiles to what the page already looked like
check( 'a set declares all three steps for every knob it answers to', ( static function() use ( $library ): bool {
	foreach( \Nino\Modules\Design\Setup::PARTS as $part => $kind ) {
		if( $kind !== 'set' )
			continue;
		$css = \Nino\Modules\Design\Setup::uncomment( (string) file_get_contents( \Nino\Modules\Design\Setup::file( $library, $part, 'v1' ) ) );
		foreach( \Nino\Modules\Design\Setup::knobs( $library, $part, 'v1' ) as $knob )
			foreach( \Nino\Modules\Design\Setup::STEPS as $step )
				if( str_contains( $css, sprintf( \Nino\Modules\Design\Setup::KNOB_TOKEN, $part, $knob, $step ). ':' ) === false )
					return false;
	}
	return true;
} )() === true );
check( 'and every set in the library answers to at least one - a part with no knob has nothing to finetune',
	count( array_filter( array_keys( \Nino\Modules\Design\Setup::PARTS ), static function( string $part ) use ( $library ): bool {
		return \Nino\Modules\Design\Setup::PARTS[$part] !== 'set'
			|| \Nino\Modules\Design\Setup::knobs( $library, $part, 'v1' ) !== [];
	} ) ) === count( \Nino\Modules\Design\Setup::PARTS ) );


echo "\nThe preview: a selection, before it is one\n";

/*	The framework's own halves are read out of the checkout and bundled, and a
	sandbox has no kernel in it at all - so it gets one, the same way a project
	has one. Modules\Assets is what writes the bundle, Modules\Template what a
	frame's [template] includes resolve through */
$root = realpath( $root ) ?: $root;	// a relative NINO_ROOT (release.yml) would make a dangling link
symlink( $root. '/_nino', ninoSandboxDir( $appData ). '/_nino' );
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template' ];
\Nino\Modules::callModules( $appData, 'init' );

/*	The logo is the kernel's slot, as the base unit's own frames ask for it - never
	a file name of a library template, which a project that uploaded its logo
	through the Images panel does not have. One slot serves every variant: a frame
	that was drawn for a dark ground says so with nino-logo--invert and applies no
	filter of its own	*/
\Nino\Modules\Images::init( $appData );
$logoNamed		= [];
$logoRender		= [ 'filled' => [], 'empty' => [] ];
$logoUrl			= \Nino\Images::getUrl( $appData, 'logo.webp' );

foreach( [ 'header', 'footer' ] as $part )
	foreach( \Nino\Modules\Design\Setup::available( $library, $part ) as $set ) {

		$source = (string) file_get_contents( \Nino\Modules\Design\Setup::file( $library, $part, $set, 'template' ) );

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

$specimen = \Nino\Modules\Design\Preview::specimen( $appData );

check( 'the specimen brings no frame of its own - markup() puts the chosen ones around it',
	str_contains( $specimen, 'frame-header' ) === false && str_contains( $specimen, 'frame-footer' ) === false );
check( 'it has a section per part a set can reach, named after the part',
	count( array_filter( [ 'atf', 'section', 'article', 'buttons', 'forms', 'lists', 'blocks' ],
		static fn( string $part ): bool => str_contains( $specimen, 'id="'. $part. '"' ) ) ) === 7 );
check( 'its one picture is a data uri, so it needs no route wherever it is shown',
	str_starts_with( \Nino\Modules\Design\Preview::PLACEHOLDER, 'data:image/svg+xml' ) === true
	&& str_contains( $specimen, \Nino\Modules\Design\Preview::PLACEHOLDER ) === true
	&& str_contains( $specimen, 'src="/images/' ) === false );

/*	The specimen is what a set is judged on, so it has to be built the way a page
	this framework produces is built: one .nino-section, one .nino-grid-row inside
	it, and every child of that row a grid cell. Not a rule of taste - the row is
	the only thing carrying the horizontal padding and the max-width (Nino.css,
	"02 Grid"), and it carries no vertical margin, so a second row stacked under
	the first sits flush against it. Every page the wizard installs and every
	section the Template Builder compiles (AreaComposer::render() wraps a whole
	section body in exactly one row) is this shape	*/

$document = new DOMDocument();
$previous = libxml_use_internal_errors( true );
$document->loadHTML( '<?xml encoding="utf-8"?><div id="specimen-root">'. $specimen. '</div>', LIBXML_NOWARNING | LIBXML_NOERROR );
libxml_clear_errors();
libxml_use_internal_errors( $previous );
$xpath = new DOMXPath( $document );

/** Elements below $context carrying $class, as an array */
$byClass = static function( DOMXPath $xpath, string $class, ?DOMNode $context = null ): array {
	$query = './/*[contains(concat(" ",normalize-space(@class)," ")," '. $class. ' ")]';
	$found = [];
	foreach( $xpath->query( $query, $context ) as $node )
		$found[] = $node;
	return $found;
};

$sections = $byClass( $xpath, 'nino-section' );
$rowCounts = [];
$strayCells = [];

foreach( $sections as $section ) {

	$rows = $byClass( $xpath, 'nino-grid-row', $section );
	$rowCounts[] = count( $rows );

	foreach( $rows as $row )
		foreach( $row->childNodes as $child ) {
			if( $child instanceof DOMElement === false )
				continue;
			if( preg_match( '/(?:^| )nino-grid-(?:s-|m-|l-|xl-)?(?:25|33|50|66|75|100)(?: |$)/', $child->getAttribute( 'class' ) ) !== 1 )
				$strayCells[] = $child->tagName. '.'. $child->getAttribute( 'class' );
		}
}

check( 'every section of the specimen holds exactly one grid row - stacked rows have no margin between them and would sit flush',
	$sections !== [] && array_unique( $rowCounts ) === [ 1 ] );
check( 'and every child of a row is a grid cell, so nothing sits in the row without a width',
	$strayCells === [] );

/*	The rhythm between the blocks inside a row is a spacing utility on the cell,
	which is where the Template Builder's own presets put it - a heading area is
	'nino-grid-100 nino-mb-3', an action area 'nino-grid-100 nino-mt-3', a card
	'nino-article ... nino-mb-3'. Without it the cells of a wrapping row meet at
	the .5rem an element brings of its own	*/
$multi = 0;
$spaced = 0;

foreach( $sections as $section )
	foreach( $byClass( $xpath, 'nino-grid-row', $section ) as $row ) {

		$cells = [];
		foreach( $row->childNodes as $child )
			if( $child instanceof DOMElement )
				$cells[] = $child;

		if( count( $cells ) < 2 )
			continue;

		$multi++;

		// Every cell but the last one, which is the section's own bottom padding
		array_pop( $cells );
		$missing = array_filter( $cells, static fn( DOMElement $cell ): bool
			=> preg_match( '/(?:^| )nino-(?:m|mt|mb|my|p|pt|pb)-[0-6](?: |$)/', $cell->getAttribute( 'class' ) ) !== 1 );

		if( $missing === [] )
			$spaced++;
	}

check( 'a row with more than one cell spaces them - every cell but the last carries a spacing utility',
	$multi > 0 && $spaced === $multi );

$notes 		= [];
$chosen 	= \Nino\Modules\Design\Setup::normalize( [ 'parts' => [ 'header' => [ 'set' => 'v3' ], 'footer' => [ 'set' => 'v5' ] ] ], $library );
$markup 	= \Nino\Modules\Design\Preview::markup( $appData, $library, $chosen, $notes );

check( 'markup() reads the chosen frames out of the library rather than off disk', $notes === []
	&& str_starts_with( trim( $markup ), trim( (string) file_get_contents( \Nino\Modules\Design\Setup::file( $library, 'header', 'v3', 'template' ) ) ) ) === true
	&& str_ends_with( trim( $markup ), trim( (string) file_get_contents( \Nino\Modules\Design\Setup::file( $library, 'footer', 'v5', 'template' ) ) ) ) === true );

$notes = [];
check( 'a frame with no template is named rather than quietly left out',
	\Nino\Modules\Design\Preview::frame( $library, [ 'parts' => [ 'header' => [ 'set' => 'nope' ] ] ], 'header', $notes ) === ''
	&& count( $notes ) === 1 && str_contains( $notes[0], '"header"' ) === true );

$notes = [];
$shown = \Nino\Modules\Design\Preview::css( $chosen, $library, '/somewhere/public', $notes );
check( 'the sheet a preview is shown under resolves the public prefix, or the webfaces never load',
	str_contains( $shown, "url('/somewhere/public/fonts/" ) === true && str_contains( $shown, '[[/nino/public]]' ) === false );

$document = \Nino\Modules\Design\Preview::document( $appData, 'de_DE', '<style>a{}</style>', '<main>b</main>', '<script></script>' );
check( 'the document says which language it is in and says no to crawlers',
	str_starts_with( $document, '<!doctype html>' ) === true
	&& str_contains( $document, '<html lang="de">' ) === true
	&& str_contains( $document, 'name="robots" content="noindex, nofollow"' ) === true );

// What the panel answers with. The setup on disk is size 'm' here (the fallback
// save above wrote it), and the post below asks for 's' - so the preview showing
// 's' is the whole point: it is the selection on screen, not the one stored
$stored 	= \Nino\Modules\Design\Setup::read( $appData, $library );
$sheet 		= (string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' );

[ $status, $preview ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [ 'header' => [ 'set' => 'v3' ] ], 'step' => 'default', 'size' => 's', 'full' => true,
] );

check( 'the preview answers with a whole document and the id of the sheet inside it', $status === 200
	&& str_starts_with( (string) ( $preview['document'] ?? '' ), '<!doctype html>' ) === true
	&& ( $preview['style'] ?? '' ) === \Nino\Modules\Design\Admin::PREVIEW_STYLE
	&& str_contains( (string) $preview['document'], 'id="'. \Nino\Modules\Design\Admin::PREVIEW_STYLE. '"' ) === true );
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
	str_contains( (string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Admin::FRAMEWORK_CSS, '' ), '.nino-section' ) === true
	&& str_contains( (string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Admin::FRAMEWORK_JS, '' ), 'Nino.ui' ) === true );

[ $status, $partial ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'step' => 'default', 'size' => 'l', 'full' => false,
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
	$status === 200 && ( $wheeled['accent'] ?? '' ) === \Nino\Modules\Design\Colours::accent( [ 'primary' => '#8b1d3f', 'harmony' => 4 ] )
	&& ( $wheeled['accent'] ?? '' ) !== '#8b1d3f'
	&& isset( $wheeled['brand']['light']['ratio'] ) === true );

check( 'previewing writes neither the setup nor the stylesheet',
	\Nino\Modules\Design\Setup::read( $appData, $library ) === $stored
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' ) === $sheet );

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
	&& array_keys( (array) ( $listed['palette'] ?? [] ) ) === [ 'harmony', 'temperature', 'saturation', 'contrast', 'depth' ] );
check( '...each knob with its own positions, three for a scale and four for a choice',
	count( (array) ( $listed['palette']['harmony']['steps'] ?? [] ) ) === 4
	&& count( (array) ( $listed['palette']['contrast']['steps'] ?? [] ) ) === 3
	&& ( $listed['palette']['harmony']['kind'] ?? '' ) === 'choice'
	&& ( $listed['palette']['contrast']['kind'] ?? '' ) === 'scale' );
check( '...and whether the colour that was picked is one text survives on',
	isset( $listed['brand']['light']['safe'] ) === true && isset( $listed['brand']['dark']['ratio'] ) === true );
check( '...and the second colour as it will be compiled, for the swatch that stands for it',
	preg_match( '/^#[0-9a-f]{6}$/', (string) ( $listed['accent'] ?? '' ) ) === 1 );

/*	So a published knob has to have its words, in every locale this feature
	ships - that is the half the table cannot carry, and the half nothing
	checked. A knob added without them draws as a blank row	*/
$knobWords = [];
foreach( [ 'en_US', 'de_DE' ] as $knobLocale ) {

	$knobText = (array) @include __DIR__. '/../text/'. $knobLocale. '.php';

	foreach( (array) ( $listed['palette'] ?? [] ) as $knobKey => $knobMeta ) {

		$wanted = [ 'label', 'note' ];
		foreach( array_keys( (array) ( $knobMeta['steps'] ?? [] ) ) as $position )
			$wanted[] = (string) ( $position + 1 );

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
	'colours' => [ 'primary' => '#8b1d3f', 'secondary' => '#0f766e', 'temperature' => 1 ],
] );
$saved = \Nino\Modules\Design\Setup::read( $appData, $library );

check( 'a save keeps the palette, normalized', $status === 200
	&& ( $saved['colours']['primary'] ?? '' ) === '#8b1d3f'
	&& ( $saved['colours']['secondary'] ?? '' ) === '#0f766e'
	&& ( $saved['colours']['temperature'] ?? 0 ) === 1
	&& ( $saved['colours']['contrast'] ?? 0 ) === 2 );
check( '...and the compiled sheet carries that colour, exactly as it was typed',
	str_contains( \Nino\Modules\Design\Compiler::compile( $saved, $library ), '--nino-brand: #8b1d3f;' ) === true );

[ $status, $coloured ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'size' => 'm', 'colours' => [ 'primary' => '#2e7d32' ], 'full' => false,
] );
check( 'a preview shows the colour that was posted rather than the one on disk', $status === 200
	&& str_contains( (string) ( $coloured['css'] ?? '' ), '--nino-brand: #2e7d32;' ) === true
	&& ( \Nino\Modules\Design\Setup::read( $appData, $library )['colours']['primary'] ?? '' ) === '#8b1d3f' );

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

check( 'the panel names every action it answers', array_keys( \Nino\Modules\Design\Admin::actions() ) === [ 'design/list', 'design/save', 'design/apply', 'design/plan', 'design/restore', 'design/preview' ] );
check( 'and previewing is not written to the activity log - it happens on every select and changes nothing',
	\Nino\Modules\Design\Admin::log( 'design/preview', [] ) === '' );
check( 'and ships the pane and the two assets it is drawn with',
	\Nino\Modules\Design\Admin::panes() === [ 'design-form' ] && count( \Nino\Modules\Design\Admin::assets() ) === 2 );
check( 'taking over files Design had not written is written to the activity log as that, and a restore as one',
	str_contains( \Nino\Modules\Design\Admin::log( 'design/apply', [ 'force' => true ] ), 'took over files Design had not written' ) === true
	&& \Nino\Modules\Design\Admin::log( 'design/restore', [] ) === 'Restore previous Design version' );
check( 'planning only reads, so it is not written to the log either', \Nino\Modules\Design\Admin::log( 'design/plan', [] ) === '' );

/*	Both files hold the same keys. Nothing but this checks it: a key one
	language lacks draws as the fill itself in that language's panel, and the
	script reads a dozen of them by name	*/
$textEn = array_keys( (array) include __DIR__. '/../text/en_US.php' );
$textDe = array_keys( (array) include __DIR__. '/../text/de_DE.php' );
check( 'the panel\'s two text files carry the same keys, in the same order', $textEn === $textDe );

preg_match_all( "#'/_admin/design/([a-z0-9/-]*[a-z0-9])'#", (string) file_get_contents( __DIR__. '/../assets/admin.js' ), $scriptKeys );
$unwritten = array_filter( array_unique( $scriptKeys[1] ), static fn( string $key ): bool => in_array( '[[/_admin/design/'. $key. ']]', $textEn, true ) === false );
check( '...and every text the script names literally is one of them'. ( $unwritten === [] ? '' : ' - missing: '. implode( ', ', $unwritten ) ), $unwritten === [] );

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
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'design-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
