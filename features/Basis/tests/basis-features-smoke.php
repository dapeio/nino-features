<?php
declare(strict_types=1);

/**
 *	Nino features
 *	basis-features-smoke.php	Contract test for what \Nino\Features and the Features panel say of the feature Basis: that it is listed, off, under its own
 *														name and with the install unit it brings - as a feature of the kernel where the kernel delivers it, which the panel
 *														does not remove, and as a feature below features/ where it does not. The rest of what the list does is
 *														tests/features-smoke.php of Nino, which reads a directory of its own.
 *
 *	Usage: php features/Basis/tests/basis-features-smoke.php
 *				 NINO_ROOT=../nino php features/Basis/tests/basis-features-smoke.php
 */

require __DIR__. '/prepare.php';

require $root. '/tests/harness.php';
require __DIR__. '/support.php';

[ , $delivered ] = basisUnderTest();

$appData = ninoSandbox( 'basis-features' );
$appData['/nino/admin/backups']	= false;
$appData['/nino/admin/logs']		= false;
$appData['/nino/auth/user']			= [];
$appData['/nino/auth/roles']		= [];
ninoUser( $appData, 'dev@example.com', [ \Nino\Modules\Features\Admin::MANAGE_PERM ] );
$ignored = ninoWarnings();

// --- Discovery ---------------------------------------------------------------

echo "Features::all - the feature of the kernel\n";

$all = \Nino\Features::all( $appData );

check( 'Basis is listed, off, with no reason against it, and it is the one feature the kernel delivers where it does - a module of the kernel with a feature.php - and none where it does not',
	isset( $all['basis'] ) === true && $all['basis']['active'] === false && $all['basis']['problems'] === [] && array_keys( array_filter( $all, static fn( array $feature ): bool => $feature['shipped'] === true ) ) === ( $delivered === true ? [ 'basis' ] : [] ) );

// --- The panel ---------------------------------------------------------------

echo "\nThe Features panel - apiList\n";

[ $status, $body ] = array_slice( ninoCall( $appData, [ \Nino\Modules\Features\Admin::class, 'apiList' ] ), 0, 2 );
$byKey = array_column( $body['features'] ?? [], null, 'key' );

check( 'apiList lists it once, by its name in German as the panel reads the list', $status === 200 && count( array_filter( $body['features'] ?? [], static fn( array $feature ): bool => $feature['key'] === 'basis' ) ) === 1
	&& ( $byKey['basis']['name'] ?? '' ) === 'Basis-Komponenten' );
check( 'it says whether the kernel delivers it, and that it brings an install unit', ( $byKey['basis']['shipped'] ?? null ) === $delivered && ( $byKey['basis']['unit'] ?? null ) === true );

if( $delivered === true ) {
	[ $removeStatus, $removeBody ] = ninoCall( $appData, [ \Nino\Modules\Features\Admin::class, 'apiRemove' ], [ 'key' => 'basis' ] );
	check( 'the panel refuses to remove it, off or on: it is a module of the kernel', $removeStatus === 400 && ( $removeBody['code'] ?? '' ) === 'features_remove_shipped' );
}
else
	echo "  skipped - the kernel delivers no Basis: removing it takes the folder below features/ of the run away, which the suites after this one read\n";

ninoDone( $appData );
