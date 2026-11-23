<?php
declare(strict_types=1);

/**
 *	Nino features
 *	basis-standalone-smoke.php	A suite of Basis started by hand, as its Usage line says - php features/Basis/tests/<suite>.php with NINO_ROOT naming a checkout - does
 *															what it does under bin/check.sh, or says in a sentence why not and ends with 2: never a stack of the language. Held on the suite of the
 *															demo and on the script suite over a checkout of this run's own (the kernel's _nino, _admin and tests/ and a features/ of its own):
 *															they say that the folder of Basis has to be below features/ - and run once it is; a root that is no checkout is said so, and so is a
 *															kernel that carries a Basis of its own.
 *
 *	Usage: php tests/basis-standalone-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/basis-standalone-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

$work = sys_get_temp_dir(). '/nino-standalone-smoke-'. bin2hex( random_bytes( 6 ) );
mkdir( $work. '/features', 0700, true );

define( 'NINO_FEATURES_DIR', $work. '/features' );
require $root. '/tests/harness.php';

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

echo 'Kernel '. \Nino\VERSION. "\n\n";

mkdir( $work. '/checkout/features', 0755, true );

foreach( [ '_nino', '_admin', 'tests' ] as $folder )
	symlink( $root. '/'. $folder, $work. '/checkout/'. $folder );

/**
 *	One suite, started the way a person starts it: the status, what it wrote on stderr and the last line of stdout
 */
$start = static function( string $tool, string $suite, string $checkout ) use ( $repo ): array {

	$process = proc_open( $tool. ' '. escapeshellarg( $repo. '/features/Basis/tests/'. $suite ), [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, $repo, [ 'NINO_ROOT' => $checkout, 'PATH' => (string) getenv( 'PATH' ), 'HOME' => (string) getenv( 'HOME' ) ] );
	$out = trim( (string) stream_get_contents( $pipes[1] ) );
	$err = trim( (string) stream_get_contents( $pipes[2] ) );

	return [ proc_close( $process ), $err, (string) array_slice( explode( "\n", $out ), -1 )[0] ];
};

$suites = [ [ PHP_BINARY, 'basis-demo-smoke.php' ], [ 'node', 'basis-header-nav-js-smoke.js' ] ];

echo "A root that is no checkout\n";

foreach( $suites as [ $tool, $suite ] ) {

	[ $status, $err ] = $start( $tool, $suite, $work. '/nowhere' );

	check( $suite. ': ends with 2 and says that there is no checkout, not a stack', $status === 2 && str_contains( $err, 'No Nino checkout' ) === true && str_contains( $err, 'Stack trace' ) === false && str_contains( $err, 'Fatal' ) === false );
}

echo "\nA checkout with no features of its own\n";

foreach( $suites as [ $tool, $suite ] ) {

	[ $status, $err, $last ] = $start( $tool, $suite, $work. '/checkout' );

	check( $suite. ': it ends with 2 and says that Basis has to be below features/ of the checkout',
		$status === 2 && str_contains( $err, 'features/Basis' ) === true && str_contains( $err, 'Stack trace' ) === false && str_contains( $err, 'Fatal' ) === false );
}

echo "\nThe folder of Basis below features/ of the checkout\n";

$copy = static function( string $from, string $to ) use ( &$copy ): void {

	mkdir( $to, 0755, true );

	foreach( array_diff( scandir( $from ) ?: [], [ '.', '..' ] ) as $entry )
		if( $entry !== 'tests' || $to !== $GLOBALS['work']. '/checkout/features/Basis' )
			is_dir( $from. '/'. $entry ) === true ? $copy( $from. '/'. $entry, $to. '/'. $entry ) : copy( $from. '/'. $entry, $to. '/'. $entry );
};

$copy( $repo. '/features/Basis', $work. '/checkout/features/Basis' );

foreach( $suites as [ $tool, $suite ] ) {

	[ $status, , $last ] = $start( $tool, $suite, $work. '/checkout' );

	check( $suite. ': with the folder below features/ of the checkout it runs and passes', $status === 0 && str_contains( $last, ' 0 failed' ) === true );
}

echo "\nA kernel with a Basis of its own\n";

mkdir( $work. '/own/_nino/Nino/Modules/Basis', 0755, true );
mkdir( $work. '/own/features', 0755 );

foreach( [ '_admin', 'tests' ] as $folder )
	symlink( $root. '/'. $folder, $work. '/own/'. $folder );

foreach( [ 'Nino' ] as $folder )
	foreach( array_diff( scandir( $root. '/_nino' ) ?: [], [ '.', '..', 'Nino' ] ) as $entry )
		symlink( $root. '/_nino/'. $entry, $work. '/own/_nino/'. $entry );

foreach( array_diff( scandir( $root. '/_nino/Nino' ) ?: [], [ '.', '..', 'Modules' ] ) as $entry )
	symlink( $root. '/_nino/Nino/'. $entry, $work. '/own/_nino/Nino/'. $entry );

foreach( array_diff( scandir( $root. '/_nino/Nino/Modules' ) ?: [], [ '.', '..', 'Basis' ] ) as $entry )
	symlink( $root. '/_nino/Nino/Modules/'. $entry, $work. '/own/_nino/Nino/Modules/'. $entry );

$copy( $repo. '/features/Basis', $work. '/own/features/Basis' );

foreach( $suites as [ $tool, $suite ] ) {

	[ $status, $err ] = $start( $tool, $suite, $work. '/own' );

	check( $suite. ': ends with 2 and says that this catalogue needs a Nino without a Basis of its own', $status === 2 && str_contains( $err, 'without a Basis of its own' ) === true && str_contains( $err, 'Stack trace' ) === false );
}

$empty = [];
ninoDone( $empty );
