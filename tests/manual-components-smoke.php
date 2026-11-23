<?php
declare(strict_types=1);

/**
 *	Nino features
 *	manual-components-smoke.php	A feature that brings components says of each of them, in the shortcodes of its manual, what it is: one entry whose handle
 *															is the call of the component - [name] or [name attribute="..."] - for every folder below its components/. Held for every
 *															feature of the catalogue.
 *
 *															For Basis the entries are held to the schemas of the components as well, as far as a machine can read them:
 *															  - an attribute that a handle names (attribute="...") is one of the component, and every value of it is one the schema
 *															    lists - with m: or l: in front only where the attribute is responsive
 *															  - an attribute that a line names with its values - "hide off or on", "gap 0 to 4", "ground (base, alt)" - is one of the
 *															    component, every value is one it lists, and a range runs from the first to the last of its numbers
 *															  - a value that a line gives "by default" is the default of the attribute it belongs to
 *															  - a line that speaks of m: or l: belongs to a component with a responsive attribute
 *															  - the entry of a block says "picture" only where the block has an image slot, and names every variant the block has
 *
 *	Usage: php tests/manual-components-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/manual-components-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'manual-components' );

/**
 *	The manifest of a feature, as its file says it
 */
function manualOf( string $directory ): array {

	return (array) ( static fn( string $path ): mixed => include $path )( $directory. '/feature.php' );
}

/**
 *	The components of a feature that no entry of the shortcodes of its manual names
 *
 *	@param		string		$directory		The folder of the feature below features/
 *
 *	@return 	array										[ the components, the names without an entry ]
 */
function manualGaps( string $directory ): array {

	$names = \Nino\Features::componentNames( $directory );
	$handles = array_keys( (array) ( manualOf( $directory )['manual']['shortcodes'] ?? [] ) );
	$gaps = [];

	foreach( $names as $name )
		if( array_filter( $handles, static fn( string $handle ): bool => preg_match( '/\[\/?'. preg_quote( $name, '/' ). '[\s\]\/=]/', $handle ) === 1 ) === [] )
			$gaps[] = $name;

	return [ $names, $gaps ];
}

echo "Every feature that brings components\n";

$gapsOf = [];
$brings = 0;

foreach( (array) scandir( $repo. '/features' ) as $directory ) {

	if( $directory[0] === '.' || is_file( $repo. '/features/'. $directory. '/feature.php' ) === false )
		continue;

	[ $names, $gaps ] = manualGaps( $repo. '/features/'. $directory );

	if( $names !== [] )
		$brings++;

	if( $gaps !== [] )
		$gapsOf[] = $directory. ' ('. implode( ', ', $gaps ). ')';
}

check( 'the features that bring components were read, Basis with its twenty among them', $brings > 20 && count( \Nino\Features::componentNames( $repo. '/features/Basis' ) ) === 20 );
check( 'every component of every feature has an entry in the shortcodes of the manual of its feature'. ( $gapsOf === [] ? '' : ' - NOT: '. implode( ', ', $gapsOf ) ), $gapsOf === [] );

echo "\nThe entries of Basis against the schemas of its components\n";

$basis = $repo. '/features/Basis';
$schemas = [];
$allAttributes = [];

foreach( \Nino\Features::componentNames( $basis ) as $name ) {

	$schemas[$name] = (array) ( static fn( string $path ): mixed => include $path )( $basis. '/components/'. $name. '/component.php' );

	foreach( array_keys( (array) ( $schemas[$name]['attributes'] ?? [] ) ) as $attribute )
		$allAttributes[$attribute] = true;
}

/**
 *	The values a schema lists for an attribute: the options of a choice, true and false of a flag, [] where it takes anything
 */
$optionsOf = static fn( array $attribute ): array => is_array( $attribute['options'] ?? null ) === true ? array_map( 'strval', $attribute['options'] ) : [];

/**
 *	What is wrong with the claims of an entry about its component
 *
 *	@return 	array										Sentences
 */
$wrong = static function( string $name, string $handle, array $line ) use ( $schemas, $allAttributes, $optionsOf ): array {

	$schema = $schemas[$name];
	$attributes = (array) ( $schema['attributes'] ?? [] );
	$wrong = [];
	$responsive = array_filter( $attributes, static fn( array $attribute ): bool => ( $attribute['responsive'] ?? false ) === true ) !== [];

	// What the handle names: attribute="value value"
	preg_match_all( '/(\w+)="([^"]*)"/', $handle, $named, PREG_SET_ORDER );

	foreach( $named as [ , $attribute, $values ] ) {

		if( $attribute === 'variant' ) {
			if( in_array( $values, (array) ( $schema['variants'] ?? [] ), true ) === false )
				$wrong[] = $name. ': the handle names the variant "'. $values. '", which the schema does not have';

			continue;
		}

		if( isset( $attributes[$attribute] ) === false ) {
			$wrong[] = $name. ': the handle names "'. $attribute. '", which the schema does not have';
			continue;
		}

		foreach( preg_split( '/\s+/', trim( $values ) ) ?: [] as $value ) {

			$plain = preg_replace( '/^[ml]:/', '', $value );

			if( $plain !== $value && ( $attributes[$attribute]['responsive'] ?? false ) !== true )
				$wrong[] = $name. ': the handle puts '. $value. ' on "'. $attribute. '", which is not responsive';

			if( $optionsOf( $attributes[$attribute] ) !== [] && in_array( $plain, $optionsOf( $attributes[$attribute] ), true ) === false )
				$wrong[] = $name. ': the handle gives "'. $attribute. '" the value "'. $plain. '", which the schema does not list';
		}
	}

	foreach( $line as $locale => $text ) {

		$prefix = $name. ' '. $locale. ': ';

		// attribute a, b or c - attribute (a, b, c) - the values in a list
		preg_match_all( '/\b([a-z]+)\s+(?:\(([^)]*)\)|((?:[\w-]+,\s*)*[\w-]+\s+(?:or|oder)\s+[\w-]+))/u', $text, $lists, PREG_SET_ORDER );

		foreach( $lists as $list ) {

			$attribute = $list[1];

			if( isset( $allAttributes[$attribute] ) === false )
				continue;

			if( isset( $attributes[$attribute] ) === false ) {
				$wrong[] = $prefix. 'names "'. $attribute. '" with values, which the component does not have';
				continue;
			}

			foreach( preg_split( '/,\s*|\s+(?:or|oder)\s+/', ( $list[3] ?? '' ) !== '' ? $list[3] : $list[2] ) ?: [] as $value ) {

				$plain = preg_replace( '/^[ml]:/', '', trim( $value ) );

				if( $optionsOf( $attributes[$attribute] ) !== [] && in_array( $plain, $optionsOf( $attributes[$attribute] ), true ) === false )
					$wrong[] = $prefix. 'gives "'. $attribute. '" the value "'. $plain. '", which the schema does not list';
			}
		}

		// attribute 1 to 4 - attribute 16 bis 100
		preg_match_all( '/\b([a-z]+)\s+(\d+)\s+(?:to|bis)\s+(\d+)/u', $text, $ranges, PREG_SET_ORDER );

		foreach( $ranges as [ , $attribute, $from, $to ] ) {

			if( isset( $allAttributes[$attribute] ) === false )
				continue;

			$numbers = isset( $attributes[$attribute] ) === true ? array_values( array_filter( $optionsOf( $attributes[$attribute] ), 'is_numeric' ) ) : [];

			if( $numbers === [] )
				$wrong[] = $prefix. 'names the range '. $from. ' to '. $to. ' of "'. $attribute. '", which has no numbers in the schema';
			elseif( (string) min( $numbers ) !== $from || (string) max( $numbers ) !== $to )
				$wrong[] = $prefix. 'says "'. $attribute. ' '. $from. ' to '. $to. '", the schema runs from '. min( $numbers ). ' to '. max( $numbers );
		}

		// a value "by default" belongs to the attribute that lists it
		if( preg_match( '/\b(\w+)\s+by default|standardmäßig\s+(\w+)/u', $text, $default ) === 1 ) {

			$value = ( $default[2] ?? '' ) !== '' ? $default[2] : $default[1];
			$owners = array_filter( $attributes, fn( array $attribute ): bool => in_array( $value, $optionsOf( $attribute ), true ) === true );

			if( $owners !== [] && array_filter( $owners, static fn( array $attribute ): bool => (string) ( $attribute['default'] ?? '' ) === $value ) === [] )
				$wrong[] = $prefix. 'gives "'. $value. '" as a default, which no attribute that lists it has as its default';
		}

		if( preg_match( '/\b[ml]:/', $text ) === 1 && $responsive === false )
			$wrong[] = $prefix. 'speaks of m: or l:, and the component has no responsive attribute';
	}

	return $wrong;
};

$found = [];
$entries = 0;

foreach( (array) ( manualOf( $basis )['manual']['shortcodes'] ?? [] ) as $handle => $line ) {

	if( preg_match( '/^\[(?:\/)?([a-z0-9-]+)/', (string) $handle, $call ) !== 1 || isset( $schemas[$call[1]] ) === false ) {
		$found[] = $handle. ': the handle is no call of a component of Basis';
		continue;
	}

	$entries++;
	$found = array_merge( $found, $wrong( $call[1], (string) $handle, (array) $line ) );
}

check( 'every shortcode entry of Basis names a component, its attributes and values - and the ranges and defaults it states - as the schema has them ('. $entries. ' entries)'
	. ( $found === [] ? '' : ' - NOT: '. implode( ' | ', array_slice( $found, 0, 6 ) ) ), $found === [] && $entries > 20 );

// The blocks
$blockFound = [];
$blocks = 0;

foreach( (array) ( manualOf( $basis )['manual']['markup'] ?? [] ) as $handle => $line ) {

	if( str_starts_with( (string) $handle, 'blocks/' ) === false )
		continue;

	$blocks++;
	$key = substr( (string) $handle, 7 );
	$file = $basis. '/blocks/'. $key. '/block.json';

	if( is_file( $file ) === false ) {
		$blockFound[] = $handle. ': there is no such block';
		continue;
	}

	$block = (array) json_decode( (string) file_get_contents( $file ), true );

	foreach( [ 'en_US' => 'picture', 'de_DE' => 'Bild' ] as $locale => $word )
		if( preg_match( '/\b'. $word. '\b/u', (string) $line[$locale] ) === 1 && ( $block['images'] ?? [] ) === [] )
			$blockFound[] = $handle. ' '. $locale. ': says "'. $word. '", and the block has no image slot';

	foreach( array_keys( (array) ( $block['variants'] ?? [] ) ) as $variant )
		if( str_contains( (string) $line['en_US'], $variant ) === false || str_contains( (string) $line['de_DE'], $variant ) === false )
			$blockFound[] = $handle. ': does not name the variant '. $variant. ' in both languages';
}

$onDisk = count( glob( $basis. '/blocks/*', GLOB_ONLYDIR ) ?: [] );

check( 'every block of Basis has an entry in the markup of the manual, which says picture only where the block has an image slot and names its variants'
	. ( $blockFound === [] ? '' : ' - NOT: '. implode( ' | ', array_slice( $blockFound, 0, 6 ) ) ), $blockFound === [] && $blocks === $onDisk && $blocks === 8 );

ninoDone( $appData );
