<?php
declare(strict_types=1);
/**
 *	Nino features
 *	components-smoke.php	What the components of the features say of themselves.
 *												The Builder offers a feature's components by what their
 *												schemas tell it and knows none of them by name: a
 *												component without a picture is the Builder's puzzle, one
 *												without a place is offered everywhere, one without a
 *												second line is a label and nothing else. So every
 *												component a manifest declares says, in the words of
 *												Nino's Components module (docs/features.md, "Components
 *												and stacks"):
 *
 *													1. which of the fixed pictures the Builder draws it as
 *													   (preview, one of Components::PREVIEWS)
 *													2. what it draws on the outside (draws): a tag and the
 *													   one class a theme addresses it by, or the tag alone -
 *													   and a template of the feature opens that tag with
 *													   that class, so that the second line of the
 *													   placeholder is what the page gets
 *													3. where it may stand (in): a list of places, none of
 *													   them twice
 *													4. the picture it is offered by (icon)
 *
 *												A component that draws no element - a bare number, text
 *												in the line it stands in - has nothing to name, and is
 *												the one thing a manifest may leave without draws; it is
 *												listed below with its reason, and a name on that list that
 *												has none or declares one fails the test, so the list cannot
 *												outlive what it was for.
 *
 *												The shape of each key - the characters of draws, the
 *												parts of an icon, the places - is the kernel's to refuse
 *												(Components::validate(), through the manifest), and a
 *												manifest it refuses fails bin/catalogue.php; what is
 *												checked here is that every component says it at all.
 *
 *												Runs against the checkout beside this repository (../nino)
 *												or the one NINO_ROOT names, for the features
 *												bin/applicable.php names for it.
 *
 *	Usage: php tests/components-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/components-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

// This repository's features/ is where the kernel looks them up
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
require $root. '/tests/harness.php';

/*	Components that draw no element, by the name they are registered under, and why. [search-count]
	is a bare number, written into the line of the text it stands in	*/
const BARE = [ 'search-count' ];

$appData = ninoSandbox( 'components' );

$applicable = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $repo. '/bin/applicable.php' ). ' '. escapeshellarg( $root ). ' 2>/dev/null' ) );
$applicable = $applicable === '' ? [] : explode( "\n", $applicable );

echo "Components - against Nino ". \Nino\VERSION. ", ". count( $applicable ). " features run on it\n";

$components	= 0;
$names			= [];

foreach( $applicable as $directory ) {

	$dir = NINO_FEATURES_DIR. '/'. $directory;
	$manifest = \Nino\Features::manifest( $dir );

	if( is_array( $manifest ) === false ) {
		check( $directory. ': the manifest is read', false );
		continue;
	}

	// What the manifest says, as it is written: the kernel's reading fills in a preview and a link where it is left out
	$declared = (array) ( ( require $dir. '/feature.php' )['components'] ?? [] );

	if( $declared === [] )
		continue;

	// The templates of the feature, whole: a tag opened with a class is found in one of them
	$templates = '';
	foreach( (array) glob( $dir. '/templates/*.tpl' ) as $template )
		$templates .= (string) file_get_contents( (string) $template ). "\n";

	foreach( $declared as $name => $schema ) {

		$name = (string) $name;
		$read = $manifest['components'][$name] ?? null;
		$where = $directory. ' ['. $name. ']';
		$components++;
		$names[] = $name;

		check( $where. ': the kernel reads it', is_array( $read ) === true );

		if( is_array( $read ) === false )
			continue;

		check( $where. ': it names the picture the Builder draws it as, one of the fixed ones',
			in_array( $schema['preview'] ?? null, \Nino\Modules\Components::PREVIEWS, true ) === true );

		if( in_array( $name, BARE, true ) === true )
			check( $where. ': it draws no element, and says none', array_key_exists( 'draws', $schema ) === false );
		else {

			$draws = $schema['draws'] ?? null;

			check( $where. ': it says what it draws on the outside - a tag, and the one class a theme addresses it by',
				is_string( $draws ) === true && preg_match( '/^(?:[a-z][a-z0-9]*|\{[a-zA-Z0-9]+\})(?: \.[a-z][a-z0-9-]*)?$/D', $draws ) === 1 && ( $read['draws'] ?? null ) === $draws );

			// What the Builder writes under the label is what a template of the feature draws
			if( is_string( $draws ) === true && preg_match( '/^([a-z][a-z0-9]*)(?: \.([a-z][a-z0-9-]*))?$/D', $draws, $parts ) === 1 ) {
				$class = $parts[2] ?? '';
				check( $where. ': a template of the feature opens <'. $parts[1]. '>'. ( $class === '' ? '' : ' with the class '. $class ),
					preg_match( '/<'. $parts[1]. '(?=[\s>\[])[^>]*'. ( $class === '' ? '' : '\bclass="[^"]*\b'. preg_quote( $class, '/' ). '(?![a-z0-9-])' ). '/', $templates ) === 1 );
			}
		}

		$places = $schema['in'] ?? null;
		check( $where. ': it says where it may stand - a list of places, each once, and the kernel keeps it',
			is_array( $places ) === true && $places !== [] && array_is_list( $places ) === true && count( array_unique( $places ) ) === count( $places )
			&& array_diff( $places, \Nino\Modules\Components::PLACES ) === [] && ( $read['in'] ?? null ) === $places );

		$icon = $schema['icon'] ?? null;
		check( $where. ': it is offered by a picture of its own - the parts of an icon, and the kernel keeps them',
			is_array( $icon ) === true && $icon !== [] && ( $read['icon'] ?? null ) === $icon );
	}
}

check( 'the components of the features were read ('. $components. ')', $components > 0 );

// A name on the list that no component has is a leftover - unless the feature it was for does not run on this Nino
$features = array_filter( (array) scandir( NINO_FEATURES_DIR ), static fn( string $entry ): bool => $entry[0] !== '.' && is_dir( NINO_FEATURES_DIR. '/'. $entry ) === true );
check( 'every name on the list of the components that draw no element is a component'. ( array_diff( BARE, $names ) === [] ? '' : ' - '. implode( ', ', array_diff( BARE, $names ) ). ' is none' ),
	array_diff( BARE, $names ) === [] || count( $applicable ) < count( $features ) );

ninoDone( $appData );
