<?php
declare(strict_types=1);

/**
 *	Nino features
 *	basis-features-smoke.php	Contract test for what \Nino\Features and the Features panel say of the feature Basis, which is a feature like any other: that it is listed, off,
 *														under its own name and with the install unit it brings, with no reason against it, and that it is removed as any feature is - not
 *														while it is on, and not while a feature that is on requires it, and with its folder gone when it is off and nothing requires it.
 *														The removal runs in a process of its own over a features directory of its own, since it takes the folder away. The rest of what
 *														the list does is tests/features-smoke.php of Nino, which reads a directory of its own.
 *
 *	Usage: php features/Basis/tests/basis-features-smoke.php
 *				 NINO_ROOT=../nino php features/Basis/tests/basis-features-smoke.php
 */

require __DIR__. '/prepare.php';

require $root. '/tests/harness.php';
require __DIR__. '/support.php';

$basisDir = basisUnderTest();

$appData = ninoSandbox( 'basis-features' );
$appData['/nino/admin/backups']	= false;
$appData['/nino/admin/logs']		= false;
$appData['/nino/auth/user']			= [];
$appData['/nino/auth/roles']		= [];
ninoUser( $appData, 'dev@example.com', [ \Nino\Modules\Features\Admin::MANAGE_PERM ] );
$ignored = ninoWarnings();

// --- Discovery ---------------------------------------------------------------

echo "Features::all - the feature\n";

$all = \Nino\Features::all( $appData );

check( 'Basis is listed, off, with no reason against it, as a feature below features/', isset( $all['basis'] ) === true && $all['basis']['active'] === false && $all['basis']['problems'] === [] && $all['basis']['dir'] === $basisDir );

// --- The panel ---------------------------------------------------------------

echo "\nThe Features panel - apiList\n";

[ $status, $body ] = array_slice( ninoCall( $appData, [ \Nino\Modules\Features\Admin::class, 'apiList' ] ), 0, 2 );
$byKey = array_column( $body['features'] ?? [], null, 'key' );

check( 'apiList lists it once, by its name in German as the panel reads the list', $status === 200 && count( array_filter( $body['features'] ?? [], static fn( array $feature ): bool => $feature['key'] === 'basis' ) ) === 1
	&& ( $byKey['basis']['name'] ?? '' ) === 'Basis-Komponenten' );
check( 'it brings an install unit', ( $byKey['basis']['unit'] ?? null ) === true );

echo "\nRemoving it\n";

// A features directory of its own with Basis and a feature that requires it; a process that switches them on and asks the panel to remove Basis in each state
$sandbox = ninoSandboxDir( $appData );
$features = $sandbox. '/remove-features';
mkdir( $features, 0755, true );
\Nino\Filesystem::copyDir( $basisDir, $features. '/Basis' );
\Nino\Filesystem::removeDir( $features. '/Basis/tests' );
mkdir( $features. '/Needy', 0755, true );
file_put_contents( $features. '/Needy/feature.php', '<?php return [ \'key\' => \'needy\', \'name\' => \'Needy\', \'category\' => \'content\', \'version\' => \'1.0.0\', \'requires\' => [ \'basis\' ] ];' );
file_put_contents( $features. '/Needy/Needy.php', '<?php namespace Nino\\Modules { class Needy {} }' );
file_put_contents( $sandbox. '/remove.php', <<<'PHP'
<?php
define( 'NINO_FEATURES_DIR', $argv[2] );
require $argv[1]. '/tests/harness.php';

$appData = ninoSandbox( 'basis-remove-child' );
$appData['/nino/admin/backups'] = false;
$appData['/nino/admin/logs'] = false;
$appData['/nino/auth/user'] = [];
$appData['/nino/auth/roles'] = [];
ninoUser( $appData, 'dev@example.com', [ \Nino\Modules\Features\Admin::MANAGE_PERM ] );

$call = static function( string $method, string $key ) use ( &$appData ): array {
	unset( $appData['./nino/features/all'] );
	[ $status, $body ] = array_slice( ninoCall( $appData, [ \Nino\Modules\Features\Admin::class, $method ], [ 'key' => $key ] ), 0, 2 );
	return [ $status, (string) ( $body['code'] ?? '' ) ];
};

\Nino\Features::activate( $appData, 'needy' );
$out = [ 'on' => $call( 'apiRemove', 'basis' ), 'required' => $call( 'apiDeactivate', 'basis' ) ];
\Nino\Features::deactivate( $appData, 'needy' );
\Nino\Features::deactivate( $appData, 'basis' );
$out['removed'] = $call( 'apiRemove', 'basis' );
$out['folder'] = is_dir( $argv[2]. '/Basis' );
echo json_encode( $out );
PHP );
$child = proc_open( [ PHP_BINARY, $sandbox. '/remove.php', $root, $features ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
$out = (string) stream_get_contents( $pipes[1] );
stream_get_contents( $pipes[2] );
fclose( $pipes[1] );
fclose( $pipes[2] );
proc_close( $child );
$removal = (array) json_decode( $out, true );

check( 'Basis is not removed while it is on, and not switched off while a feature that is on requires it - each with a refusal of its own - and is removed, folder and all, once it is off',
	( $removal['on'][0] ?? 0 ) === 400 && ( $removal['on'][1] ?? '' ) === 'features_remove_active' && ( $removal['required'][0] ?? 0 ) === 400 && ( $removal['required'][1] ?? '' ) === 'features_required_by'
	&& ( $removal['removed'][0] ?? 0 ) === 200 && ( $removal['folder'] ?? true ) === false );

ninoDone( $appData );
