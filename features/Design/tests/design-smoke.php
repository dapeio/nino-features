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
 *											names (see tests/harness.php there).
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
check( 'it names ^1.2 - the release where the look became one file this compiles over', ( $feature['nino'] ?? '' ) === '^1.2' );
check( '...and 1.1 really cannot run it', \Nino\Features::satisfies( (string) $feature['nino'], '1.1.0' ) === false
	&& \Nino\Features::satisfies( (string) $feature['nino'], '1.2.0-beta' ) === true );
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

check( 'the tokens come first and the root size second', ( $order[0] ?? '' ) === 'the design tokens and their roles' && ( $order[1] ?? '' ) === 'the root size' );
check( '...then the frames, then the sets, in the order the parts are declared in, each saying where its knobs stand', array_slice( $order, 2, 9 ) === [
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

$large = \Nino\Modules\Design\Compiler::compile( \Nino\Modules\Design\Setup::normalize( [ 'size' => 'l' ], $library ), $library );
check( 'the size knob moves the pair, not one half of it', str_contains( $large, '--nino-base-size: 106.25%;' ) === true
	&& str_contains( $large, '--nino-base-size: 118.75%;' ) === true );

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

echo "\nWriting it, and what it will not write over\n";

$written = \Nino\Modules\Design\Compiler::write( $appData, $css );
check( 'the first write succeeds - there is no theme.css in a sandbox yet', $written === true );
check( '...and produces the file the css bundle already names', is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true );

$onDisk = (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' );
check( 'a sheet Design wrote knows itself', \Nino\Modules\Design\Compiler::stamped( $onDisk ) === true );

$second = \Nino\Modules\Design\Compiler::write( $appData, \Nino\Modules\Design\Compiler::compile(
	\Nino\Modules\Design\Setup::normalize( [ 'size' => 'l' ], $library ), $library ) );
check( 'compiling again writes over its own file without being asked twice', $second === true
	&& str_contains( (string) file_get_contents( ninoSandboxDir( $appData ). '/private/assets/theme.css' ), '106.25%' ) === true );

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
check( '...and a fingerprint per part, so a reinstall that changed a set is visible rather than silent',
	( $stored['parts']['section']['sha'] ?? '' ) === hash_file( 'sha256', \Nino\Modules\Design\Setup::file( $library, 'section', 'v1' ) ) );

/*	A frame is a stylesheet AND the markup it was drawn against. Compiling one
	without writing the other is how a page ends up with v3's css over v1's
	html, which is exactly what this did before the panel went looking */
$headerTemplate = \Nino\Filesystem::path( $appData, sprintf( \Nino\Modules\Design\Compiler::FRAME_TARGET, 'header' ) );
check( 'applying writes the chosen frame\'s markup, not only its stylesheet',
	is_file( $headerTemplate ) === true
	&& str_contains( (string) file_get_contents( $headerTemplate ), 'nino-scroll-header' ) === true
	&& \Nino\Modules\Design\Compiler::stampedFrame( (string) file_get_contents( $headerTemplate ) ) === true );
check( '...and the markup is the variant the setup names', str_contains( (string) file_get_contents( $headerTemplate ), 'header v1' ) === true );

$byHand = "<header>my own</header>\n";
file_put_contents( $headerTemplate, $byHand );
$notes = [];
$refusedFrame = \Nino\Modules\Design::apply( $appData, $notes );
check( 'a frame template somebody edited is not overwritten either', $refusedFrame !== true
	&& str_contains( (string) $refusedFrame, 'theme.header.tpl was not written by Design' ) === true
	&& file_get_contents( $headerTemplate ) === $byHand );
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
	(string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' ), '106.25%' ) === true );

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
symlink( $root. '/_nino', ninoSandboxDir( $appData ). '/_nino' );
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template' ];
\Nino\Modules::callModules( $appData, 'init' );

$specimen = \Nino\Modules\Design\Preview::specimen();

check( 'the specimen brings no frame of its own - markup() puts the chosen ones around it',
	str_contains( $specimen, 'theme.header' ) === false && str_contains( $specimen, 'theme.footer' ) === false );
check( 'it has a section per part a set can reach, named after the part',
	count( array_filter( [ 'atf', 'section', 'article', 'buttons', 'forms', 'lists', 'blocks' ],
		static fn( string $part ): bool => str_contains( $specimen, 'id="'. $part. '"' ) ) ) === 7 );
check( 'its one picture is a data uri, so it needs no route wherever it is shown',
	str_starts_with( \Nino\Modules\Design\Preview::PLACEHOLDER, 'data:image/svg+xml' ) === true
	&& str_contains( $specimen, \Nino\Modules\Design\Preview::PLACEHOLDER ) === true
	&& str_contains( $specimen, 'src="/images/' ) === false );

$notes 		= [];
$chosen 	= \Nino\Modules\Design\Setup::normalize( [ 'parts' => [ 'header' => [ 'set' => 'v3' ], 'footer' => [ 'set' => 'v5' ] ] ], $library );
$markup 	= \Nino\Modules\Design\Preview::markup( $library, $chosen, $notes );

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

$document = \Nino\Modules\Design\Preview::document( 'de_DE', '<style>a{}</style>', '<main>b</main>', '<script></script>' );
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
	str_contains( (string) ( $preview['css'] ?? '' ), '93.75%' ) === true
	&& ( $stored['size'] ?? '' ) === 'm'
	&& str_contains( (string) $preview['document'], 'nino-grid-row nino-grid-row--wide' ) === true );
check( 'the framework is linked, not inlined - the workbench sends a csp that refuses an inline script',
	str_contains( (string) $preview['document'], 'design-preview.js"></script>' ) === true
	&& str_contains( (string) $preview['document'], 'design-preview.css"' ) === true
	&& str_contains( (string) $preview['document'], '<script>' ) === false );
check( '...and the bundle it points at really carries the framework',
	str_contains( (string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Admin::FRAMEWORK_CSS, '' ), '.nino-section' ) === true
	&& str_contains( (string) \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Admin::FRAMEWORK_JS, '' ), 'Nino.ui' ) === true );

[ $status, $partial ] = callDesignAction( $appData, 'apiPreview', [
	'parts' => [], 'step' => 'default', 'size' => 'l', 'full' => false,
] );
check( 'a change that is only a stylesheet sends only that - the frame on screen keeps its page', $status === 200
	&& ( $partial['document'] ?? null ) === '' && str_contains( (string) ( $partial['css'] ?? '' ), '118.75%' ) === true );

check( 'previewing writes neither the setup nor the stylesheet',
	\Nino\Modules\Design\Setup::read( $appData, $library ) === $stored
	&& \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Design\Compiler::TARGET, '' ) === $sheet );

\Nino\Auth::logoutUser( $appData );
check( 'every action of the panel refuses a request with no session',
	callDesignAction( $appData, 'apiList' )[0] === 401
	&& callDesignAction( $appData, 'apiSave', [] )[0] === 401
	&& callDesignAction( $appData, 'apiApply' )[0] === 401
	&& callDesignAction( $appData, 'apiPreview', [] )[0] === 401 );

\Nino\Auth::insertUser( $appData, 'editor@example.com', 'correct horse battery staple', [ '/_admin/elements/manage' ] );
\Nino\Auth::loginUser( $appData, 'editor@example.com', 'correct horse battery staple' );
check( '...and an account without /_admin/design/manage with a 403',
	callDesignAction( $appData, 'apiList' )[0] === 403 && callDesignAction( $appData, 'apiApply' )[0] === 403
	&& callDesignAction( $appData, 'apiPreview', [] )[0] === 403 );

check( 'the panel names every action it answers', array_keys( \Nino\Modules\Design\Admin::actions() ) === [ 'design/list', 'design/save', 'design/apply', 'design/preview' ] );
check( 'and previewing is not written to the activity log - it happens on every select and changes nothing',
	\Nino\Modules\Design\Admin::log( 'design/preview', [] ) === '' );
check( 'and ships the pane and the two assets it is drawn with',
	\Nino\Modules\Design\Admin::panes() === [ 'design-form' ] && count( \Nino\Modules\Design\Admin::assets() ) === 2 );
check( 'taking the delivered file over is written to the activity log as that',
	str_contains( \Nino\Modules\Design\Admin::log( 'design/apply', [ 'force' => true ] ), 'took over' ) === true );

ninoDone( $appData );
