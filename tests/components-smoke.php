<?php
declare(strict_types=1);

/**
 *	Nino features
 *	components-smoke.php	Every component of every feature says what a tool needs to offer it: a group, a label and an icon of the table
 *												(\Nino\Components::ICONS), a label for each attribute, all in de_DE and en_US. Every group has its group.php, a part
 *												that keeps the rule of mixing (\Nino\Components::partProblems()) and a demo. Every feature whose components are offered
 *												anywhere brings a block that \Nino\Blocks reads without 'missing' - but for the features named below, with their reason.
 *
 *												Reads features/ only, against the kernel NINO_ROOT names (default: ../nino).
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

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
require $root. '/tests/harness.php';
require $repo. '/bin/delivered.php';

/*	The features that offer a component and bring no block, by the key of their manifest, and why. A name on this list that has a block,
	or no component that is offered anywhere, fails the test, so the list cannot outlive what it was for	*/
const COMPONENTS_WITHOUT_BLOCK = [
	'modeswitch' => 'the switch is put into a header or a footer block of the theme, which cannot name a feature of its own; its demo shows it',
	'progress'   => 'a bar is no section: it stands in a page beside the sections, and its demo shows it',
];

$appData = ninoSandbox( 'components' );
$appData[\Nino\Html::TREES_SWITCH] = false;
// The modules the kernel delivers itself are on - the copy below features/ of a feature it delivers rests, and a feature of this repository that requires it stands on those
$appData['/nino/modules'] = array_map( static fn( string $directory ): string => '\\Nino\\Modules\\'. $directory, array_values( deliveredFeatures() ) );

// The folder of every feature of this repository by the key its feature.php names - read as the file says it, since the kernel refuses the folder of a feature it delivers
$folders = [];

foreach( (array) scandir( NINO_FEATURES_DIR ) as $directory )
	if( $directory[0] !== '.' && is_file( NINO_FEATURES_DIR. '/'. $directory. '/feature.php' ) === true )
		$folders[(string) ( (array) require NINO_FEATURES_DIR. '/'. $directory. '/feature.php' )['key']] = NINO_FEATURES_DIR. '/'. $directory;

/**
 *	The names of the components of the features a feature requires, through the features they require
 *
 *	@param		array			$requires			The keys a manifest requires
 *	@param		array			$folders			key => folder
 *
 *	@return 	array
 */
function componentsBuiltOn( array $requires, array $folders ): array {

	$names = [];
	$seen = [];

	while( $requires !== [] ) {

		$key = (string) array_shift( $requires );

		if( isset( $seen[$key] ) === true || isset( $folders[$key] ) === false )
			continue;

		$seen[$key] = true;
		$names = array_merge( $names, \Nino\Features::componentNames( $folders[$key] ) );
		$requires = array_merge( $requires, (array) ( ( (array) require $folders[$key]. '/feature.php' )['requires'] ?? [] ) );
	}

	return array_values( array_unique( $names ) );
}

// A feature of this repository with components, by the key of its manifest: its folder, its manifest and what the kernel reads of its folders
$features = [];

foreach( (array) scandir( NINO_FEATURES_DIR ) as $directory ) {

	$dir = NINO_FEATURES_DIR. '/'. $directory;

	if( $directory[0] === '.' || is_dir( $dir. '/components' ) === false )
		continue;

	$manifest = \Nino\Features::manifest( $dir );

	if( is_array( $manifest ) === false )
		continue;

	$appData['/nino/modules'][] = '\\'. ltrim( (string) $manifest['module'], '\\' );
	$features[(string) $manifest['key']] = [ 'dir' => $dir, 'name' => $directory, 'read' => \Nino\Features::readComponents( $dir, (string) $manifest['key'], componentsBuiltOn( $manifest['requires'], $folders ) ) ];
}

\Nino\Features::registerComponents( $appData );
$ignored = ninoWarnings();

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );

check( 'the features with components were read', count( $features ) > 10 );

$unread = [];
$missing = [];
$components = [];
$labels = [];
$byGroup = [];

foreach( $features as $key => $feature ) {

	if( is_array( $feature['read'] ) === false ) {
		$unread[] = $feature['name']. ': '. $feature['read'];
		continue;
	}

	foreach( $feature['read']['rejected'] as $name => $why )
		$unread[] = $feature['name']. '/'. $name. ': '. $why;

	foreach( (array) glob( $feature['dir']. '/components/*/component.php' ) as $file ) {

		$name = basename( dirname( (string) $file ) );
		$schema = (array) require $file;
		$where = $feature['name']. ' ['. $name. ']';
		$components[$name] = $key;
		$byGroup[$feature['name']. '/'. (string) ( $schema['group'] ?? '' )][] = $name;

		$pair = static fn( mixed $text ): bool => is_array( $text ) === true && is_string( $text['de_DE'] ?? null ) === true && trim( $text['de_DE'] ) !== ''
			&& is_string( $text['en_US'] ?? null ) === true && trim( $text['en_US'] ) !== '';

		if( is_string( $schema['group'] ?? null ) === false || $schema['group'] === '' )
			$missing[] = $where. ' has no group';
		elseif( is_file( $feature['dir']. '/groups/'. $schema['group']. '/group.php' ) === false )
			$missing[] = $where. ' is of the group '. $schema['group']. ', which the feature has not';

		if( $pair( $schema['label'] ?? null ) === false )
			$labels[] = $where. ' has no label in both languages';

		if( in_array( $schema['icon'] ?? null, \Nino\Components::ICONS, true ) === false )
			$missing[] = $where. ' names no icon of the table';

		foreach( (array) ( $schema['attributes'] ?? [] ) as $attribute => $declared )
			if( $pair( is_array( $declared ) === true ? ( $declared['label'] ?? null ) : null ) === false )
				$labels[] = $where. ' attribute '. $attribute. ' has no label in both languages';
	}
}

echo "Components\n";

check( 'the kernel reads every folder of components and registers them without a reason'. $what( $unread ), $unread === [] && count( $components ) > 20 && count( array_diff_key( $components, \Nino\Components::components( $appData ) ) ) === 0 );
check( 'every component has a group of its feature and an icon of the table'. $what( $missing ), $missing === [] );
check( 'every component and every attribute has a label in de_DE and en_US'. $what( $labels ), $labels === [] );

echo "\nGroups\n";

$groups = [];
$parts = [];
$demos = [];

foreach( $features as $key => $feature ) {

	foreach( (array) glob( $feature['dir']. '/groups/*', GLOB_ONLYDIR ) as $folder ) {

		$group = basename( (string) $folder );
		$where = $feature['name']. ' ['. $group. ']';
		$read = is_array( $feature['read'] ) === true ? ( $feature['read']['groups'][$group] ?? null ) : null;

		if( is_array( $read ) === false || is_file( $folder. '/group.php' ) === false )
			$groups[] = $where. ' has no group.php the kernel reads';

		if( empty( $byGroup[$feature['name']. '/'. $group] ) === true )
			$groups[] = $where. ' is the group of no component';

		$css = is_file( $folder. '/part/part.css' ) === true ? (string) file_get_contents( $folder. '/part/part.css' ) : null;
		$found = $css === null ? [ 'has no part/part.css' ] : \Nino\Components::partProblems( $css, $byGroup[$feature['name']. '/'. $group] ?? [] );

		if( $found !== [] )
			$parts[] = $where. ' '. $found[0];

		if( is_array( $read ) === false || is_string( $read['demo'] ?? null ) === false || trim( $read['demo'] ) === '' )
			$demos[] = $where;
	}
}

check( 'every group has its group.php, which the kernel reads, and is the group of a component'. $what( $groups ), $groups === [] );
check( 'the part of every group keeps the rule of mixing'. $what( $parts ), $parts === [] );
check( 'every group has a demo'. $what( $demos ), $demos === [] );

echo "\nBlocks\n";

$blocks = \Nino\Blocks::all( $appData );
$rejected = \Nino\Blocks::rejected( $appData );
$without = [];
$stale = [];

foreach( $features as $key => $feature ) {

	$offered = false;

	foreach( (array) glob( $feature['dir']. '/components/*/component.php' ) as $file )
		$offered = $offered === true || empty( ( (array) require $file )['in'] ) === false;

	if( $offered === false ) {

		if( isset( COMPONENTS_WITHOUT_BLOCK[$key] ) === true )
			$stale[] = $key. ' offers no component';

		continue;
	}

	$own = array_filter( array_keys( $blocks ), static fn( string $block ): bool => $blocks[$block]['origin'] === $key && \Nino\Blocks::get( $appData, $block )['missing'] === [] && in_array( $key, $blocks[$block]['features'], true ) === true );

	if( $own === [] && isset( COMPONENTS_WITHOUT_BLOCK[$key] ) === false )
		$without[] = $feature['name'];

	if( $own !== [] && isset( COMPONENTS_WITHOUT_BLOCK[$key] ) === true )
		$stale[] = $key. ' has a block now';
}

check( 'no block of the features is refused by the kernel'. $what( array_map( static fn( string $block, string $why ): string => $block. ': '. $why, array_keys( $rejected ), $rejected ) ), $rejected === [] );
check( 'every feature whose components are offered brings a block that the kernel reads without "missing" and that calls its components'. $what( $without ), $without === [] );
check( 'every feature the list of those without a block names is one that offers a component and has none'. $what( $stale )
	. ( array_diff( array_keys( COMPONENTS_WITHOUT_BLOCK ), array_keys( $features ) ) === [] ? '' : ' - no feature: '. implode( ', ', array_diff( array_keys( COMPONENTS_WITHOUT_BLOCK ), array_keys( $features ) ) ) ),
	$stale === [] && array_diff( array_keys( COMPONENTS_WITHOUT_BLOCK ), array_keys( $features ) ) === [] );

ninoDone( $appData );
