<?php
declare(strict_types=1);

/**
 *	Nino features
 *	basis-owners-smoke.php	Test of what the kernel lets a feature do with the names and the blocks of Basis. A demo of a feature that requires Basis may call its components,
 *													where Basis is on and where it is off; a feature that does not require it may not, since nobody owns the name then. A block of the project with the key
 *													of a block of Basis puts it out: the project stands in front of the features.
 *
 *	Usage: php features/Basis/tests/basis-owners-smoke.php
 *				 NINO_ROOT=../nino php features/Basis/tests/basis-owners-smoke.php
 */

require __DIR__. '/prepare.php';
register_shutdown_function( static function(): void {
	foreach( [ 'Plain', 'Needy' ] as $directory )
		\Nino\Filesystem::removeDir( NINO_FEATURES_DIR. '/'. $directory );
} );

require $root. '/tests/harness.php';
require __DIR__. '/support.php';

$ignored = ninoWarnings();
$basisDir = basisUnderTest();

// Two features of this test with one component and a demo that calls components of Basis: Plain requires nothing, Needy requires Basis
foreach( [ 'Plain' => [], 'Needy' => [ 'basis' ] ] as $name => $requires ) {

	$key = strtolower( $name );
	$dir = NINO_FEATURES_DIR. '/'. $name;
	\Nino\Filesystem::removeDir( $dir );
	mkdir( $dir. '/components/'. $key. '-box', 0755, true );
	mkdir( $dir. '/groups/'. $key, 0755, true );
	file_put_contents( $dir. '/feature.php', '<?php return '. var_export( [ 'key' => $key, 'name' => $name, 'category' => 'content', 'version' => '1.0.0', 'requires' => $requires ], true ). ';' );
	file_put_contents( $dir. '/'. $name. '.php', '<?php namespace Nino\\Modules { class '. $name. ' {} }' );
	file_put_contents( $dir. '/components/'. $key. '-box/component.php', '<?php return '. var_export( [ 'group' => $key, 'label' => 'P', 'in' => [ 'page' ], 'template' => '<i></i>' ], true ). ';' );
	file_put_contents( $dir. '/groups/'. $key. '/group.php', '<?php return [ "label" => "'. $name. '" ];' );
	file_put_contents( $dir. '/groups/'. $key. '/demo.tpl', "[section-wrap]\n\t[section-row]\n\t\t[section-col]\n\t\t\t[section-title text=\"T\"]\n\t\t[/section-col]\n\t[/section-row]\n[/section-wrap]\n" );
}

$boot = static function( array $modules, ?array $again = null ): array {
	$appData = $again ?? ninoSandbox( 'basis-owners' );
	foreach( [ \Nino\Components::REGISTRY, \Nino\Components::OWNERS, \Nino\Components::GROUPS, './nino/features/listed', './nino/features/all', './nino/blocks/state' ] as $key )
		unset( $appData[$key] );
	$appData['/nino/modules'] = $modules;
	\Nino\Features::registerComponents( $appData );
	return $appData;
};

echo "A demo may call the components of Basis\n";

$needyOn = $boot( [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Needy' ] );
$needyWarnings = ninoWarnings();
check( 'a feature that requires Basis calls its components in a demo, where Basis is on: the name is Basis\'s', ( \Nino\Components::groups( $needyOn )['needy']['demo'] ?? '' ) !== '' && $needyWarnings === []
	&& ( $needyOn[\Nino\Components::OWNERS]['names']['section-wrap'] ?? '' ) === 'basis' );

$plainOn = $boot( [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Plain' ] );
$plainOff = $boot( [ '\\Nino\\Modules\\Plain' ] );
ninoWarnings();

check( 'a feature that does not require Basis may not call its components in a demo: the demo is left out, with Basis on and with Basis off',
	( \Nino\Components::groups( $plainOn )['plain']['demo'] ?? 'x' ) === '' && ( \Nino\Components::groups( $plainOff )['plain']['demo'] ?? 'x' ) === '' );

echo "\nThe blocks of Basis against a block of the project\n";

$project = $boot( [ '\\Nino\\Modules\\Basis' ] );
$copy = ninoSandboxDir( $project ). '/private/blocks/hero-full';
\Nino\Filesystem::copyDir( $basisDir. '/blocks/hero-full', $copy );
$json = json_decode( (string) file_get_contents( $copy. '/block.json' ), true );
$json['name'] = [ 'de_DE' => 'PROJECT', 'en_US' => 'PROJECT' ];
file_put_contents( $copy. '/block.json', json_encode( $json ) );
$project = $boot( [ '\\Nino\\Modules\\Basis' ], $project );
ninoWarnings();
check( 'a block of the project with the key of a block of Basis puts it out, and the roots are the project and then Basis',
	( \Nino\Blocks::all( $project )['hero-full']['origin'] ?? '' ) === 'project' && array_column( \Nino\Blocks::roots( $project ), 'origin' ) === [ 'project', 'basis' ] );

ninoDone( $project );
