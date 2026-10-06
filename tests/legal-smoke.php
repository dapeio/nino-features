<?php
declare(strict_types=1);

/**
 *	Nino features
 *	legal-smoke.php	The sections of the privacy policy the features bring
 *									(install/elements/privacy.php of Consent, Embed, Forms,
 *									Mailer, Newsletter, ProtectedArea and Stats), which Nino's
 *									Legal module (1.4) shows in the type 'privacy'. Two parts:
 *
 *									Part 1 needs nothing of the kernel and runs against every
 *									checkout:
 *
 *										1. every file is named in the manifest of its unit, brings
 *										   no type of its own (no 'model', no 'title') and has the
 *										   buckets '*', de_DE and en_US with the same ids
 *										2. an id is the feature's key or the key and a name, a
 *										   position lies in the feature's range and is the same
 *										   in no two sections, title and text are not empty
 *										3. a text has the form the sanitizer gives a field with
 *										   'blocks': p, br, ul, ol, li, strong, em and a, a link
 *										   to an anchor of the policy or to https, no '&', no
 *										   entity, no '['
 *										4. a placeholder is one the Legal module replaces
 *
 *									Part 2 runs only where the checkout has the Legal module
 *									(\Nino\Modules\Legal): in a sandbox the module's unit is
 *									applied, then every feature's, add-only as an activation
 *									does it:
 *
 *										5. the sections are there, a second run changes nothing,
 *										   the type's model and title stay the module's
 *										6. no id or position is one of the module's, and every link to an
 *										   anchor names a section of the module or of a feature
 *										7. the field's own model leaves every text and title as
 *										   it is - the form in the Elements panel does not
 *										   report a section nobody touched as unsaved
 *										8. Legal::contributions() names the sections of a
 *										   feature
 *
 *									Where Part 2 cannot run, it says so in a line starting
 *									"note", as build-smoke.php does for a Nino without
 *									\Nino\Catalogue.
 *
 *	Usage: php tests/legal-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/legal-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

$repo = dirname( __DIR__ );

// This repository's features, as the catalogue and bin/catalogue.php read them
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
require $root. '/tests/harness.php';

// Where in the policy each feature's sections stand: the module keeps 100-399
// and 900-999, and 400, 410, 500 and 510 (cookies, server logs, contact form,
// contact), so the 400-599 ranges are shared with it; the rest is divided by
// what a section is about
const LEGAL_RANGES = [
	'consent'		=> [ 400, 499 ],
	'forms'			=> [ 500, 599 ],
	'newsletter'	=> [ 500, 599 ],
	'mailer'		=> [ 500, 599 ],
	'stats'			=> [ 600, 699 ],
	'embed'			=> [ 700, 799 ],
	'protected'		=> [ 800, 899 ],
];

// What the Legal module replaces: /project/company/<group>/<name> and /project/website/general/<name>
const LEGAL_PLACEHOLDER = '#^/project/(?:company/[a-z0-9]+(?:-[a-z0-9]+)*|website/general)/[a-z0-9]+(?:-[a-z0-9]+)*$#';

$appData = ninoSandbox( 'legal' );
$appData['/nino/dir'] = '';
$appData['/nino/locales/textfiles'] = '/text';

/**
 *	@param		string		$dir					The feature's directory
 *
 *	@return 	array										Its feature.php, its unit's manifest and its privacy.php, as arrays - empty where a file is not there
 */
function legalFiles( string $dir ): array {

	$read = static fn( string $file ): array => is_file( $file ) === true ? (array) ( include $file ) : [];

	return [ 'feature' => $read( $dir. '/feature.php' ), 'unit' => $read( $dir. '/install/manifest.php' ), 'privacy' => $read( $dir. '/install/elements/privacy.php' ) ];
}


// --- Part 1: what every file is --------------------------------------------------

echo "The features' sections of the privacy policy\n";

$contributors	= [];
$ids					= [];
$orders				= [];

foreach( glob( $repo. '/features/*', GLOB_ONLYDIR ) ?: [] as $dir ) {

	$files = legalFiles( $dir );

	if( $files['privacy'] === [] )
		continue;

	$name = basename( $dir );
	$key	= (string) ( $files['feature']['key'] ?? '' );

	$contributors[$key] = $dir;

	check( $name. ': the unit names the file under "elements", and the feature says what it is', ( $files['unit']['elements'] ?? null ) === [ 'privacy' => 'elements/privacy.php' ]
		&& isset( $files['feature']['manual']['install']['elements/privacy.php'] ) === true );
	check( $name. ': it has a position range here, so a section cannot land among the module\'s', isset( LEGAL_RANGES[$key] ) === true );
	check( $name. ': it brings no type of its own - no model, no title - so it can only add to the module\'s', array_diff( array_keys( $files['privacy'] ), [ '*', 'de_DE', 'en_US' ] ) === [] );

	$stars	= array_keys( array_diff_key( (array) ( $files['privacy']['*'] ?? [] ), [ '*' => 1 ] ) );
	$german	= array_keys( (array) ( $files['privacy']['de_DE'] ?? [] ) );
	$english	= array_keys( (array) ( $files['privacy']['en_US'] ?? [] ) );

	sort( $stars );
	sort( $german );
	sort( $english );

	check( $name. ': the buckets *, de_DE and en_US carry the same sections', $stars !== [] && $stars === $german && $german === $english );

	$problems = [];

	foreach( $german as $id ) {

		$id = (string) $id;

		if( preg_match( '#^'. preg_quote( $key, '#' ). '(?:-[a-z0-9]+(?:-[a-z0-9]+)*)?$#', $id ) !== 1 )
			$problems[] = $id. ': the id is not the feature\'s key or the key and a name';

		if( isset( $ids[$id] ) === true )
			$problems[] = $id. ': another feature has the id';

		$ids[$id] = $key;

		$order = $files['privacy']['*'][$id]['order'] ?? null;

		if( is_int( $order ) === false || isset( LEGAL_RANGES[$key] ) === false || $order < LEGAL_RANGES[$key][0] || $order > LEGAL_RANGES[$key][1] )
			$problems[] = $id. ': the position is not one of the feature\'s range';
		elseif( isset( $orders[$order] ) === true )
			$problems[] = $id. ': the position is also '. $orders[$order]. '\'s';

		if( is_int( $order ) === true )
			$orders[$order] ??= $id;

		if( array_key_exists( 'hidden', (array) ( $files['privacy']['*'][$id] ?? [] ) ) === true )
			$problems[] = $id. ': it stores a hidden flag, which is the editor\'s';

		foreach( [ 'de_DE', 'en_US' ] as $locale )
			foreach( [ 'title', 'text' ] as $field ) {

				$value = $files['privacy'][$locale][$id][$field] ?? null;

				if( is_string( $value ) === false || trim( $value ) === '' ) {
					$problems[] = $id. ' '. $locale. ': the '. $field. ' is empty';
					continue;
				}

				if( preg_match( '/[&\[\]]/', $value ) === 1 )
					$problems[] = $id. ' '. $locale. ' '. $field. ': has a "&", a "[" or a "]" - an entity or a bracket the sanitizer would change';

				if( $field === 'title' ) {

					if( $value !== strip_tags( $value ) )
						$problems[] = $id. ' '. $locale. ': the title has markup';

					continue;
				}

				preg_match_all( '#</?([a-z0-9]+)\b[^>]*>#i', $value, $tags );

				foreach( array_unique( array_map( 'strtolower', $tags[1] ) ) as $tag )
					if( in_array( $tag, [ 'p', 'br', 'ul', 'ol', 'li', 'strong', 'em', 'a' ], true ) === false )
						$problems[] = $id. ' '. $locale. ': the tag <'. $tag. '> is not one of the form a field with blocks has';

				preg_match_all( '#<a\b([^>]*)>#i', $value, $links );

				foreach( $links[1] as $attributes )
					if( preg_match( '#^ href="(?:\#privacy-[a-z0-9]+(?:-[a-z0-9]+)*|https://[^"\s]+)"$#', $attributes ) !== 1 )
						$problems[] = $id. ' '. $locale. ': a link is neither an anchor of the policy nor https, or has an attribute besides href';

				// A placeholder is a candidate by its shape, and one only if the module replaces it
				preg_match_all( '/(?<!#)#(\/[^#\s<>]{1,200})#(?!#)/', $value, $candidates );

				foreach( $candidates[1] as $candidate )
					if( preg_match( LEGAL_PLACEHOLDER, $candidate ) !== 1 )
						$problems[] = $id. ' '. $locale. ': #'. $candidate. '# is a placeholder the module would leave as it is';

				$plain = (string) preg_replace( '/<[^>]*>/', ' ', $value );

				if( $locale === 'en_US' && preg_match( '/\b(und|der|das|nicht|Deine|Dein|Dich|Dir)\b/u', $plain ) === 1 )
					$problems[] = $id. ' en_US: a German word';
			}
	}

	check( $name. ': every section is in the form of the field, in its range, with a title and a text in both languages'. ( $problems === [] ? '' : ' - '. implode( ' | ', $problems ) ), $problems === [] );
}

$names = array_keys( $contributors );
$known = array_keys( LEGAL_RANGES );
sort( $names );
sort( $known );

check( 'the pattern for a placeholder takes the company\'s and the website\'s facts and nothing else', preg_match( LEGAL_PLACEHOLDER, '/project/company/contact/email' ) === 1
	&& preg_match( LEGAL_PLACEHOLDER, '/project/website/general/host' ) === 1 && preg_match( LEGAL_PLACEHOLDER, '/project/mail/address/owner' ) === 0
	&& preg_match( LEGAL_PLACEHOLDER, '/project/website/general/a/b' ) === 0 && preg_match( LEGAL_PLACEHOLDER, '/project/company/x' ) === 0 );
check( 'the sections come from the features the policy knows about, and no feature that brings one is missing from its table', $names === $known );

echo "\n";


// --- Part 2: against the module ----------------------------------------------------

$moduleDir = $root. '/_nino/Nino/Modules/Legal';

if( is_file( $moduleDir. '/Legal.php' ) === false || class_exists( '\\Nino\\Modules\\Legal' ) === false ) {

	echo "  note - this Nino (". \Nino\VERSION. ") has no \\Nino\\Modules\\Legal: only what a file is was checked, not how the module takes it\n";
	ninoDone( $appData );
}

echo "Added to the module's type, the way an activation adds them\n";

$locales	= [ 'de_DE', 'en_US' ];
$routes		= [];
$blacklist	= [];
$config		= [];

$applied = \Nino\Features::applyUnit( $appData, $moduleDir. '/install', $locales, $routes, $blacklist, $config, true );
check( 'the module\'s own unit applies in the sandbox', $applied === true );

$typeFile		= static fn(): array => \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] );
$moduleType	= $typeFile();
$moduleIds		= array_keys( (array) ( $moduleType['de_DE'] ?? [] ) );

check( 'it brought the type with a model, and sections of its own', isset( $moduleType['model']['text'] ) === true && count( $moduleIds ) > 5 );

foreach( $contributors as $key => $dir ) {

	$name		= basename( $dir );
	$files	= legalFiles( $dir );
	$own		= array_keys( (array) $files['privacy']['de_DE'] );

	$result = \Nino\Features::applyUnit( $appData, $dir. '/install', $locales, $routes, $blacklist, $config, false );
	check( $name. ': its unit applies add-only', $result === true && ninoWarnings() === [] );

	$type = $typeFile();

	check( $name. ': every section is in the type, in both languages and at its position', array_diff( $own, array_keys( $type['de_DE'] ) ) === [] && array_diff( $own, array_keys( $type['en_US'] ) ) === []
		&& array_filter( $own, static fn( string|int $id ): bool => ( $type['*'][$id]['order'] ?? null ) !== $files['privacy']['*'][$id]['order'] ) === [] );
	check( $name. ': the type\'s model and title stay the module\'s', ( $type['model'] ?? null ) === $moduleType['model'] && ( $type['title'] ?? null ) === $moduleType['title'] );
	check( $name. ': none of its ids is one of the module\'s', array_intersect( $own, $moduleIds ) === [] );

	$once = $typeFile();
	\Nino\Features::applyUnit( $appData, $dir. '/install', $locales, $routes, $blacklist, $config, false );
	check( $name. ': a second run changes nothing', $typeFile() === $once );
}

echo "\n";

$type			= $typeFile();
$model		= \Nino\Elements::getElementModel( $appData, '/privacy' );
$sections	= array_keys( $type['de_DE'] );

// A text in the form of the field is one the form in the Elements panel does not call unsaved
$changed = [];
$anchors = [];

foreach( $contributors as $key => $dir )
	foreach( (array) legalFiles( $dir )['privacy']['de_DE'] as $id => $section )
		foreach( $locales as $locale ) {

			$version = $type[$locale][$id];

			foreach( [ 'title', 'text' ] as $field )
				if( \Nino\Html::fieldValue( (string) $version[$field], (array) ( $model[$field] ?? [] ) ) !== $version[$field] )
					$changed[] = $id. ' '. $locale. ' '. $field;

			preg_match_all( '/href="#privacy-([a-z0-9-]+)"/', (string) $version['text'], $found );

			foreach( $found[1] as $anchor )
				if( in_array( $anchor, $sections, true ) === false )
					$anchors[] = $id. ' '. $locale. ': #privacy-'. $anchor;
		}

$positions = array_filter( array_map( static fn( array $section ): mixed => $section['order'] ?? null, array_diff_key( (array) $type['*'], [ '*' => 1 ] ) ), 'is_int' );
check( 'no two sections of the type, the module\'s and the features\', stand at the same position', count( $positions ) === count( $sections ) && count( array_unique( $positions ) ) === count( $positions ) );
check( 'the field\'s own model leaves every text and title as it is'. ( $changed === [] ? '' : ' - '. implode( ', ', $changed ) ), $changed === [] );
check( 'every link to an anchor of the policy names a section of the module or of a feature'. ( $anchors === [] ? '' : ' - '. implode( ', ', $anchors ) ), $anchors === [] );

// What the Features panel tells whoever switches a feature off or removes it
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Legal' ];
$appData['./nino/locales/current'] = 'de_DE';

foreach( $contributors as $key => $dir ) {

	$own			= array_keys( (array) legalFiles( $dir )['privacy']['de_DE'] );
	$named		= \Nino\Modules\Legal::contributions( $appData, $key );
	$namedIds	= array_map( 'strval', array_column( $named, 'id' ) );

	sort( $own );
	sort( $namedIds );

	check( basename( $dir ). ': the module names its sections, with their titles, by what the unit brings', $namedIds === array_map( 'strval', $own ) && array_unique( array_column( $named, 'type' ) ) === [ 'privacy' ]
		&& array_filter( $named, static fn( array $section ): bool => $section['title'] !== $type['de_DE'][$section['id']]['title'] ) === [] );
}

check( 'a feature without a section is named no sections', \Nino\Modules\Legal::contributions( $appData, 'gallery' ) === [] );

ninoDone( $appData );
