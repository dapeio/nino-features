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
	&& $setup['step'] === 'default' && $setup['size'] === 'm'
	&& $setup['parts']['section']['step'] === null
	&& $setup['parts']['header']['set'] === 'v1' );

$notes = [];
$setup = \Nino\Modules\Design\Setup::normalize( [ 'parts' => [ 'section' => [ 'set' => 'v99' ] ] ], $library, $notes );

check( 'a part naming a set the library does not have falls back, and says which', $setup['parts']['section']['set'] === 'v1'
	&& count( $notes ) === 1 && str_contains( $notes[0], 'v99' ) === true );

$notes = [];
$setup = \Nino\Modules\Design\Setup::normalize( [ 'step' => 'sideways', 'size' => 'xxl', 'parts' => [ 'article' => [ 'step' => 'nope' ] ] ], $library, $notes );

check( 'a knob position that is not one is the default, not an error', $setup['step'] === 'default' && $setup['size'] === 'm' && $setup['parts']['article']['step'] === null );

$setup = \Nino\Modules\Design\Setup::normalize( [ 'step' => 'more', 'parts' => [ 'article' => [ 'step' => 'less' ] ] ], $library );

check( 'a part with no step of its own follows the global knob', \Nino\Modules\Design\Setup::step( $setup, 'section' ) === 'more' );
check( '...and a part that names one does not', \Nino\Modules\Design\Setup::step( $setup, 'article' ) === 'less' );

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
check( '...then the frames, then the sets, in the order the parts are declared in', array_slice( $order, 2, 9 ) === [
	'header: v1', 'footer: v1', 'atf: v1 (default)', 'section: v1 (default)', 'article: v1 (default)',
	'buttons: v1 (default)', 'forms: v1 (default)', 'lists: v1 (default)', 'blocks: v1 (default)',
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
file_put_contents( $sandbox. '/sets/section/v1.css', ":root {\n\t--section-title-size--less: 1.6rem;\n\t--section-title-size--default: 2rem;\n\t--section-title-size--more: 2.6rem;\n}\n.nino-section-title { font-size: var(--section-title-size); }\n" );
file_put_contents( $sandbox. '/sets/buttons/v1.css', ":root {\n\t--btn-radius--less: 0;\n\t--btn-radius--default: .4rem;\n\t--btn-radius--more: 2rem;\n}\n" );

$picked = \Nino\Modules\Design\Compiler::compile( \Nino\Modules\Design\Setup::normalize( [ 'step' => 'more' ], $sandbox ), $sandbox );

check( 'every triple a set declares becomes one selection line', str_contains( $picked, '--section-title-size: var(--section-title-size--more);' ) === true
	&& str_contains( $picked, '--btn-radius: var(--btn-radius--more);' ) === true );
check( '...gathered into one block at the end, not scattered beside the sets', substr_count( $picked, 'the knob positions' ) === 1
	&& strpos( $picked, 'the knob positions' ) > strpos( $picked, '--section-title-size--more' ) );
check( '...and the three steps themselves are still in the sheet, so a project without the feature can move the knob by hand',
	str_contains( $picked, '--section-title-size--less: 1.6rem;' ) === true && str_contains( $picked, '--section-title-size--more: 2.6rem;' ) === true );

$deviating = \Nino\Modules\Design\Compiler::compile(
	\Nino\Modules\Design\Setup::normalize( [ 'step' => 'more', 'parts' => [ 'buttons' => [ 'step' => 'less' ] ] ], $sandbox ), $sandbox );

check( 'a part that deviates is compiled at its own step, the rest at the global one', str_contains( $deviating, '--btn-radius: var(--btn-radius--less);' ) === true
	&& str_contains( $deviating, '--section-title-size: var(--section-title-size--more);' ) === true );

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
check( '...and brings no panel yet, which is the next patch, not a silent nothing', \Nino\Modules\Design::adminPanels( $appData ) === [] );

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

check( 'applying again needs no force - the file is now one of ours', \Nino\Modules\Design::apply( $appData ) === true );

check( 'the compiled sheet is what the bundle already points at', in_array( \Nino\Modules\Design\Compiler::TARGET,
	(array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), true ) === false
	&& \Nino\Modules\Design\Compiler::TARGET === '/assets/theme.css' );

check( 'deactivating leaves the compiled sheet and the setup where they are', \Nino\Features::deactivate( $appData, 'design' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/assets/theme.css' ) === true
	&& is_file( ninoSandboxDir( $appData ). '/private/data/design.php' ) === true );

echo "\n";
ninoDone( $appData );
