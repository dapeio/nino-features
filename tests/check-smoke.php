<?php
declare(strict_types=1);

/**
 *	Nino features
 *	check-smoke.php	What bin/check.sh does with a feature the kernel delivers (bin/delivered.php), run as the script runs for a person: a repository of this
 *									run's own - the scripts of bin/, a feature Probe and a folder Basis that holds one test - against a checkout whose kernel is the one this
 *									suite runs against (its _nino, _admin and tests/ are the kernel's, its features/ is empty). Where the kernel delivers Basis the test of
 *									the folder is run from features/.delivered-Basis/tests/ of the checkout and the output says that it is delivered; where it does not the folder
 *									is copied whole and run from features/Basis/tests/. In both the test of Probe runs, the script ends with 0, and the checkout's features/
 *									is empty again when it has run - after a run that a signal ended as well, and a run that finds what a cut-off run left takes away what the
 *									script put and nothing else.
 *
 *	Usage: php tests/check-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/check-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

$work = sys_get_temp_dir(). '/nino-check-smoke-'. bin2hex( random_bytes( 6 ) );
mkdir( $work. '/features', 0700, true );

define( 'NINO_FEATURES_DIR', $work. '/features' );
require $root. '/tests/harness.php';
require dirname( __DIR__ ). '/bin/delivered.php';

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo = dirname( __DIR__ );
$delivers = deliveredFeatures() !== [];

echo 'Kernel '. \Nino\VERSION. ( $delivers === true ? ' delivers Basis itself' : ' delivers no Basis' ). "\n\n";

// The repository of this run
mkdir( $work. '/repo/bin', 0755, true );
mkdir( $work. '/repo/tests', 0755, true );

foreach( [ 'check.sh', 'applicable.php', 'catalogue.php', 'packages.php', 'applications.php', 'delivered.php' ] as $file )
	copy( $repo. '/bin/'. $file, $work. '/repo/bin/'. $file );

foreach( [ 'Probe' => 'probe', 'Basis' => 'basis' ] as $directory => $key ) {

	mkdir( $work. '/repo/features/'. $directory. '/tests', 0755, true );
	file_put_contents( $work. '/repo/features/'. $directory. '/feature.php', '<?php return [ \'key\' => \''. $key. '\', \'name\' => [ \'en_US\' => \''. $directory. '\', \'de_DE\' => \''. $directory. '\' ], \'description\' => [ \'en_US\' => \'A probe.\', \'de_DE\' => \'Eine Probe.\' ], \'category\' => \'system\', \'version\' => \'1.0.0\', \'nino\' => \'^2.0\' ];' );
	file_put_contents( $work. '/repo/features/'. $directory. '/'. $directory. '.php', '<?php namespace Nino\\Modules { class '. $directory. ' {} }' );
	file_put_contents( $work. '/repo/features/'. $directory. '/tests/'. $key. '-smoke.php', '<?php echo "RAN '. $key. ' from ", basename( dirname( __DIR__ ) ), "\n"; if( getenv( "CHECK_SMOKE_KILL" ) === "1" && "'. $key. '" === "probe" ) { shell_exec( "kill -TERM ". (int) trim( (string) shell_exec( "ps -o ppid= -p ". getmypid() ) ) ); sleep( 1 ); }' );
}

// The checkout: the kernel's folders, a features/ of its own
mkdir( $work. '/checkout/features', 0755, true );

foreach( [ '_nino', '_admin', 'tests' ] as $folder )
	symlink( $root. '/'. $folder, $work. '/checkout/'. $folder );

/**
 *	One run of the script over the checkout
 *
 *	@param		array			$env					More of the environment
 *
 *	@return 	array										[ status, stdout, stderr ]
 */
$run = static function( array $env = [] ) use ( $work ): array {

	$process = proc_open( 'sh '. escapeshellarg( $work. '/repo/bin/check.sh' ), [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, $work. '/repo', $env + [ 'NINO_ROOT' => $work. '/checkout', 'PATH' => (string) getenv( 'PATH' ) ] );
	$out = (string) stream_get_contents( $pipes[1] );
	$err = (string) stream_get_contents( $pipes[2] );

	return [ proc_close( $process ), $out, $err ];
};

$left = static fn(): array => array_values( array_diff( scandir( $GLOBALS['work']. '/checkout/features' ) ?: [], [ '.', '..' ] ) );

[ $status, $out, $err ] = $run();

check( 'the script ends with 0 and the test of Probe ran from the folder of Probe', $status === 0 && str_contains( $out, 'RAN probe from Probe' ) === true );
check( 'where the kernel delivers Basis the output says so and its test ran from features/.delivered-Basis, and where it does not the folder was copied whole and ran from Basis',
	$delivers === true ? ( str_contains( $out, 'features/Basis: delivered by the kernel' ) === true && str_contains( $out, 'RAN basis from .delivered-Basis' ) === true && str_contains( $err, 'Basis: delivered by Nino' ) === true )
		: ( str_contains( $out, 'delivered by the kernel' ) === false && str_contains( $out, 'RAN basis from Basis' ) === true && str_contains( $err, 'delivered by' ) === false ) );
check( 'what the script put into the features/ of the checkout is gone when it ends, the hidden directory too', $left() === [] );

echo "\nA run that is cut off\n";

[ $status, $out ] = $run( [ 'CHECK_SMOKE_KILL' => '1' ] );

check( 'a run that is ended by a signal in the middle of the tests ends with a status of its own and leaves nothing in the features/ of the checkout', $status !== 0 && str_contains( $out, 'RAN probe' ) === true && $left() === [] );

// What a run that was killed leaves: the list it keeps, what it put, the hidden directory - and a feature that was there before, which is none of its
mkdir( $work. '/checkout/features/Probe', 0755, true );
file_put_contents( $work. '/checkout/features/Probe/old.txt', 'x' );
mkdir( $work. '/checkout/features/.delivered-Basis/tests', 0755, true );
file_put_contents( $work. '/checkout/features/.check-sh', "Probe\n.delivered-Basis\n" );
mkdir( $work. '/checkout/features/Mine', 0755, true );
file_put_contents( $work. '/checkout/features/Mine/keep.txt', 'mine' );

[ $status, $out ] = $run();

check( 'a run that finds what a run before it left takes it away and runs: status 0, the test of Probe ran, the feature of the checkout that was not on the list is as it was, and nothing else is left',
	$status === 0 && str_contains( $out, 'RAN probe from Probe' ) === true && $left() === [ 'Mine' ] && is_file( $work. '/checkout/features/Mine/keep.txt' ) === true );

$empty = [];
ninoDone( $empty );
