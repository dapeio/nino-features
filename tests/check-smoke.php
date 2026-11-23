<?php
declare(strict_types=1);

/**
 *	Nino features
 *	check-smoke.php	What bin/check.sh does with the features/ of the checkout it runs in, as the script runs for a person: a repository of this run's own - the
 *									scripts of bin/ and two features, Probe and Basis, that hold one test each - against a checkout whose kernel is the one this suite runs
 *									against (its _nino, _admin and tests/ are the kernel's, its features/ is of its own).
 *
 *										A. a run copies the features, runs their tests, ends with 0 and leaves only what the checkout had: a file in features/ and a feature of
 *										   another name stay
 *										B. a run that a signal ends - INT, TERM, HUP - in the middle of the tests ends with a status of its own and leaves nothing
 *										C. a run that is killed (KILL) leaves its folders with their mark and its lock; the next run takes away those and only those, takes the
 *										   lock over because its process is gone, and runs
 *										D. a folder of the name of a feature that was there before the run, without the mark, is not touched - unless it is empty, which a run that
 *										   was cut off leaves, and rmdir takes it: the run ends with 2 and a sentence,
 *										   copies nothing, and the features/.htaccess is as it was
 *										E. a second run in the same checkout while the first is going ends with 2 and a sentence, and the first one ends with 0
 *										F. a Nino with a Basis of its own is turned away by bin/check.sh and bin/applicable.php with a sentence and a status that is not 0, with
 *										   nothing copied
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

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo = dirname( __DIR__ );

echo 'Kernel '. \Nino\VERSION. "\n\n";

// The repository of this run
mkdir( $work. '/repo/bin', 0755, true );
mkdir( $work. '/repo/tests', 0755, true );

foreach( [ 'check.sh', 'applicable.php', 'catalogue.php', 'packages.php', 'applications.php' ] as $file )
	copy( $repo. '/bin/'. $file, $work. '/repo/bin/'. $file );

// The test of Probe can end its run: with the signal CHECK_SMOKE_SIGNAL names, sent to the script that started it, or - with CHECK_SMOKE_WAIT - by waiting
// a while after it has written the file "started" beside the checkout, so that a second run can be started in the meantime
$probe = <<<'PHP'
<?php
echo "RAN probe from ", basename( dirname( __DIR__ ) ), "\n";
$parent = (int) trim( (string) shell_exec( "ps -o ppid= -p ". getmypid() ) );

if( getenv( "CHECK_SMOKE_SIGNAL" ) !== false ) {
	shell_exec( "kill -". getenv( "CHECK_SMOKE_SIGNAL" ). " ". $parent );
	sleep( 1 );
}

if( getenv( "CHECK_SMOKE_WAIT" ) !== false ) {
	file_put_contents( dirname( getenv( "NINO_ROOT" ) ?: "." ). "/started", "1" );
	sleep( (int) getenv( "CHECK_SMOKE_WAIT" ) );
}
PHP;

foreach( [ 'Probe' => 'probe', 'Basis' => 'basis' ] as $directory => $key ) {

	mkdir( $work. '/repo/features/'. $directory. '/tests', 0755, true );
	file_put_contents( $work. '/repo/features/'. $directory. '/feature.php', '<?php return [ \'key\' => \''. $key. '\', \'name\' => [ \'en_US\' => \''. $directory. '\', \'de_DE\' => \''. $directory. '\' ], \'description\' => [ \'en_US\' => \'A probe.\', \'de_DE\' => \'Eine Probe.\' ], \'category\' => \'system\', \'version\' => \'1.0.0\', \'nino\' => \'^2.0\' ];' );
	file_put_contents( $work. '/repo/features/'. $directory. '/'. $directory. '.php', '<?php namespace Nino\\Modules { class '. $directory. ' {} }' );
	file_put_contents( $work. '/repo/features/'. $directory. '/tests/'. $key. '-smoke.php', $key === 'probe' ? $probe : '<?php echo "RAN basis from ", basename( dirname( __DIR__ ) ), "\n";' );
}

/**
 *	A checkout: the kernel's folders, a features/ of its own
 */
$checkout = static function( string $name ) use ( $work, $root ): string {

	mkdir( $work. '/'. $name. '/features', 0755, true );

	foreach( [ '_nino', '_admin', 'tests' ] as $folder )
		symlink( $root. '/'. $folder, $work. '/'. $name. '/'. $folder );

	file_put_contents( $work. '/'. $name. '/features/.htaccess', "Require all denied\n" );

	return $work. '/'. $name;
};

$main = $checkout( 'checkout' );

/**
 *	The script over a checkout, started: the process and its pipes
 *
 *	@param		string		$script		The script below repo/bin
 *	@param		array			$env			More of the environment
 */
$start = static function( string $script, string $in, array $env = [] ) use ( $work ): array {

	$process = proc_open( [ str_ends_with( $script, '.php' ) === true ? PHP_BINARY : 'sh', $work. '/repo/bin/'. $script, $in ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, $work. '/repo', $env + [ 'NINO_ROOT' => $in, 'PATH' => (string) getenv( 'PATH' ) ] );

	return [ $process, $pipes ];
};

$finish = static function( array $started ): array {

	[ $process, $pipes ] = $started;
	$out = (string) stream_get_contents( $pipes[1] );
	$err = (string) stream_get_contents( $pipes[2] );

	return [ proc_close( $process ), $out, $err ];
};

/**
 *	One run of bin/check.sh over a checkout
 *
 *	@return 	array										[ status, stdout, stderr ]
 */
$run = static fn( string $in, array $env = [] ): array => $finish( $start( 'check.sh', $in, $env ) );

$left = static fn( string $in ): array => array_values( array_diff( scandir( $in. '/features' ) ?: [], [ '.', '..' ] ) );

echo "A. A run\n";

mkdir( $main. '/features/Mine', 0755, true );
file_put_contents( $main. '/features/Mine/keep.txt', 'mine' );

[ $status, $out, $err ] = $run( $main );

check( 'the script ends with 0 and the tests of Probe and Basis ran from their folders', $status === 0 && str_contains( $out, 'RAN probe from Probe' ) === true && str_contains( $out, 'RAN basis from Basis' ) === true );
check( 'what it copied is gone when it ends, and the file and the feature the checkout had are as they were', $left( $main ) === [ '.htaccess', 'Mine' ] && is_file( $main. '/features/Mine/keep.txt' ) === true
	&& file_get_contents( $main. '/features/.htaccess' ) === "Require all denied\n" );
check( 'it says nothing of Basis on stderr: Basis is a feature like the others', str_contains( $err, 'delivered' ) === false && str_contains( $out, 'delivered' ) === false );

echo "\nB. A run that a signal ends\n";

foreach( [ 'INT' => 130, 'TERM' => 143, 'HUP' => 129 ] as $signal => $expected ) {

	[ $status, $out ] = $run( $main, [ 'CHECK_SMOKE_SIGNAL' => $signal ] );

	check( 'SIG'. $signal. ' in the middle of the tests: the status is '. $expected. ', the test had started, and the checkout has what it had before',
		$status === $expected && str_contains( $out, 'RAN probe' ) === true && $left( $main ) === [ '.htaccess', 'Mine' ] && is_file( $main. '/features/Mine/keep.txt' ) === true );
}

echo "\nC. A run that is killed\n";

[ $status, $out ] = $run( $main, [ 'CHECK_SMOKE_SIGNAL' => 'KILL' ] );

check( 'a run that is killed leaves its folders with the mark and its lock', $status !== 0 && is_file( $main. '/features/Probe/.check-sh-mark' ) === true && is_file( $main. '/features/.check-sh.lock' ) === true );

[ $status, $out, $err ] = $run( $main );

check( 'the next run takes the lock over, takes away what the one before it left, and runs: status 0, the test of Probe ran, the feature of the checkout is as it was, nothing else is left',
	$status === 0 && str_contains( $out, 'RAN probe from Probe' ) === true && $left( $main ) === [ '.htaccess', 'Mine' ] && is_file( $main. '/features/Mine/keep.txt' ) === true );

// A lock of a process that is gone, with nothing left beside it
file_put_contents( $main. '/features/.check-sh.lock', "999999\n" );
[ $status, $out ] = $run( $main );

check( 'a lock whose process is gone is taken over, and the run ends with 0 and without the lock', $status === 0 && str_contains( $out, 'RAN probe' ) === true && $left( $main ) === [ '.htaccess', 'Mine' ] );

echo "\nD. A folder that was there before the run\n";

mkdir( $main. '/features/Probe', 0755, true );
file_put_contents( $main. '/features/Probe/old.txt', 'x' );

[ $status, $out, $err ] = $run( $main );

check( 'a folder of the name of a feature without the mark is not overwritten: status 2, the sentence names it, no test ran, nothing was copied, and what the checkout had is as it was',
	$status === 2 && str_contains( $err, 'features/Probe is there without the mark of bin/check.sh' ) === true && $out === '' && is_file( $main. '/features/Probe/old.txt' ) === true
	&& $left( $main ) === [ '.htaccess', 'Mine', 'Probe' ] && is_dir( $main. '/features/Probe/tests' ) === false && file_get_contents( $main. '/features/.htaccess' ) === "Require all denied\n" );

// The same folder with a file of the name of the mark but another text in it is as little the script's
file_put_contents( $main. '/features/Probe/.check-sh-mark', 'mine' );
[ $status, , $err ] = $run( $main );

check( 'a mark with another text is no mark: the folder stays and the run ends with 2', $status === 2 && is_file( $main. '/features/Probe/old.txt' ) === true && str_contains( $err, 'features/Probe is there without the mark' ) === true );

// What a run leaves that is cut off between making the folder and marking it: an empty folder of the name. rmdir takes it and nothing that holds anything
unlink( $main. '/features/Probe/.check-sh-mark' );
unlink( $main. '/features/Probe/old.txt' );
[ $status, $out, $err ] = $run( $main );

check( 'an empty folder of the name without the mark is taken away with rmdir, said on stderr, and the run goes on: status 0, the test of Probe ran, nothing but what the checkout had is left',
	$status === 0 && str_contains( $err, 'empty folder without the mark' ) === true && str_contains( $out, 'RAN probe from Probe' ) === true && $left( $main ) === [ '.htaccess', 'Mine' ] );

mkdir( $main. '/features/Probe/sub', 0755, true );
[ $status, , $err ] = $run( $main );

check( 'a folder of the name that holds only an empty folder is not empty: it stays and the run ends with 2', $status === 2 && is_dir( $main. '/features/Probe/sub' ) === true && str_contains( $err, 'features/Probe is there without the mark' ) === true );
rmdir( $main. '/features/Probe/sub' );
rmdir( $main. '/features/Probe' );

echo "\nE. Two runs at once\n";

$second = $checkout( 'second' );
mkdir( $second. '/features/Mine', 0755, true );
$first = $start( 'check.sh', $second, [ 'CHECK_SMOKE_WAIT' => '3' ] );

for( $try = 0; $try < 100 && is_file( $work. '/started' ) === false; $try++ )
	usleep( 100000 );

[ $statusSecond, $outSecond, $errSecond ] = $run( $second );

check( 'the second run in the checkout ends with 2 and says that a run is going, with its process, and copies and removes nothing',
	$statusSecond === 2 && str_contains( $errSecond, 'may be running' ) === true && str_contains( $errSecond, 'take the lock away by hand' ) === true && preg_match( '/process [0-9]+/', $errSecond ) === 1 && $outSecond === '' );
check( 'the folders of the first run are there while it runs', is_dir( $second. '/features/Probe' ) === true && is_file( $second. '/features/Probe/.check-sh-mark' ) === true );

[ $statusFirst, $outFirst ] = $finish( $first );

check( 'the first run ends with 0 and its tests ran, and the checkout is as it was', $statusFirst === 0 && str_contains( $outFirst, 'RAN probe from Probe' ) === true && $left( $second ) === [ '.htaccess', 'Mine' ] );

echo "\nE2. A lock that is no readable file\n";

mkdir( $second. '/features/.check-sh.lock', 0755 );
[ $status, $out, $err ] = $run( $second );

check( 'a lock that is a folder ends the run with 2 and a sentence that names it, not with a message of rm, and nothing is copied or taken away',
	$status === 2 && str_contains( $err, 'is not a readable file (it is a folder)' ) === true && str_contains( $err, 'rm:' ) === false && $out === '' && is_dir( $second. '/features/.check-sh.lock' ) === true && is_dir( $second. '/features/Probe' ) === false );
rmdir( $second. '/features/.check-sh.lock' );

symlink( $work. '/nowhere', $second. '/features/.check-sh.lock' );
[ $status, , $err ] = $run( $second );

check( 'a lock that is a link to nothing ends the run with 2 and a sentence', $status === 2 && str_contains( $err, 'leads nowhere' ) === true && $left( $second ) === [ '.check-sh.lock', '.htaccess', 'Mine' ] );
unlink( $second. '/features/.check-sh.lock' );

echo "\nF. A Nino with a Basis of its own\n";

$own = $work. '/own';
mkdir( $own. '/features', 0755, true );
mkdir( $own. '/_nino/Nino/Modules/Basis', 0755, true );

foreach( [ '_admin', 'tests' ] as $folder )
	symlink( $root. '/'. $folder, $own. '/'. $folder );

foreach( array_diff( scandir( $root. '/_nino' ) ?: [], [ '.', '..', 'Nino' ] ) as $entry )
	symlink( $root. '/_nino/'. $entry, $own. '/_nino/'. $entry );

foreach( array_diff( scandir( $root. '/_nino/Nino' ) ?: [], [ '.', '..', 'Modules' ] ) as $entry )
	symlink( $root. '/_nino/Nino/'. $entry, $own. '/_nino/Nino/'. $entry );

foreach( array_diff( scandir( $root. '/_nino/Nino/Modules' ) ?: [], [ '.', '..', 'Basis' ] ) as $entry )
	symlink( $root. '/_nino/Nino/Modules/'. $entry, $own. '/_nino/Nino/Modules/'. $entry );

[ $status, $out, $err ] = $run( $own );

check( 'bin/check.sh ends with 2 and says that the catalogue needs a Nino without a Basis of its own, and nothing is below features/', $status === 2 && str_contains( $err, 'without a Basis of its own' ) === true && $out === '' && $left( $own ) === [] );

[ $status, $out, $err ] = $finish( $start( 'applicable.php', $own ) );

check( 'bin/applicable.php ends with a status that is not 0, names no feature and says the same', $status !== 0 && $out === '' && str_contains( $err, 'without a Basis of its own' ) === true );

$empty = [];
ninoDone( $empty );
