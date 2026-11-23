<?php
declare(strict_types=1);

/**
 *	Nino features
 *	delivered-smoke.php	The switch for a feature the kernel still delivers itself (bin/delivered.php), held in whichever of the two cases the
 *											kernel it runs against is: it delivers Basis as a module (the copy below features/ rests: not copied, not read, not
 *											listed, and said so), or it does not (the copy is the feature: copied, read, listed). The question the scripts ask is
 *											the kernel's - is \Nino\Modules\Basis a module of the kernel - so the answer is read from the folder of the kernel
 *											here, by hand, and every script has to agree with it:
 *
 *												A. bin/applicable.php names Basis for copying exactly where the kernel does not deliver it, says on stderr that it
 *												   does not copy it where it does, and prints it with --delivered where it does
 *												B. bin/catalogue.php reads the whole repository with exit 0 in both cases, lists the entry of Basis where it is
 *												   the feature and lists none where the kernel delivers it, and says on stderr which of the two it is
 *												C. an application that names a feature the kernel delivers is no application any kernel offers (Catalogue::applications()
 *												   needs every feature of it in the index): bin/build.php refuses to build it, with the application named and nothing
 *												   written, and builds it where the index can list the feature
 *
 *	Usage: php tests/delivered-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/delivered-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

$work = sys_get_temp_dir(). '/nino-delivered-smoke-'. bin2hex( random_bytes( 6 ) );
mkdir( $work, 0700, true );

define( 'NINO_FEATURES_DIR', $work. '/project-features' );
require $root. '/tests/harness.php';

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo = dirname( __DIR__ );

/**
 *	Run a script of this repository with this php: the exit status, stdout and stderr
 */
function runScript( string $script, array $args ): array {

	global $work;

	$command = escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $script );
	foreach( $args as $arg )
		$command .= ' '. escapeshellarg( (string) $arg );

	$errors		= $work. '/stderr-'. bin2hex( random_bytes( 4 ) );
	$process	= proc_open( $command, [ 1 => [ 'pipe', 'w' ], 2 => [ 'file', $errors, 'w' ] ], $pipes );

	if( is_resource( $process ) === false )
		return [ -1, '', 'proc_open failed' ];

	$stdout = (string) stream_get_contents( $pipes[1] );
	fclose( $pipes[1] );
	$status = proc_close( $process );
	$stderr = (string) @file_get_contents( $errors );
	@unlink( $errors );

	return [ $status, $stdout, $stderr ];
}

// The oracle: the folder of the kernel, read by hand
$delivers = is_dir( $root. '/_nino/Nino/Modules/Basis' ) === true;

echo 'Kernel '. \Nino\VERSION. ( $delivers === true ? ' delivers Basis itself: the copy below features/ rests' : ' delivers no Basis: the copy below features/ is the feature' ). "\n\n";

echo "A. bin/applicable.php\n";

[ $status, $stdout, $stderr ] = runScript( $repo. '/bin/applicable.php', [ $root ] );
$names = array_filter( explode( "\n", $stdout ) );

check( 'it names Basis for copying where the kernel delivers none, and names it not where the kernel does - and every other feature either way', $status === 0 && in_array( 'Basis', $names, true ) === ( $delivers === false )
	&& in_array( 'Hello', $names, true ) === true );
check( 'where the kernel delivers it, the reason is on stderr, with the name of the module - not a silence, not a manifest the kernel refused', str_contains( $stderr, 'Basis: delivered by Nino ' ) === $delivers
	&& str_contains( $stderr, 'directory name is taken' ) === false );

[ $status, $stdout ] = runScript( $repo. '/bin/applicable.php', [ $root, '--delivered' ] );

check( 'with --delivered it prints the features the kernel delivers, one per line: Basis where it does, nothing where it does not', $status === 0 && trim( $stdout ) === ( $delivers === true ? 'Basis' : '' ) );

echo "\nB. bin/catalogue.php\n";

[ $status, $stdout, $stderr ] = runScript( $repo. '/bin/catalogue.php', [ $root ] );
$preview = (array) json_decode( $stdout, true );
$keys = array_column( array_filter( (array) ( $preview['packages'] ?? [] ), static fn( array $entry ): bool => $entry['kind'] === 'feature' ), 'key' );

check( 'the repository is read whole with exit 0 in both cases, and the entry of Basis is in the index exactly where the kernel does not deliver it', $status === 0 && is_array( $preview['packages'] ?? null ) === true
	&& in_array( 'basis', $keys, true ) === ( $delivers === false ) && in_array( 'hello', $keys, true ) === true );
check( 'the preview says which of the two it is: a line on stderr where the copy rests, none where it is listed', str_contains( $stderr, 'features/Basis: delivered by the kernel' ) === $delivers
	&& ( $delivers === true || $stderr === '' ) );

echo "\nC. bin/build.php\n";

// A tree of this run's own: the tool, a feature that requires Basis, the copy of Basis where the repository has it, and an application that names it
$tree = $work. '/tree';
mkdir( $tree. '/features/Hello', 0755, true );
mkdir( $tree. '/applications', 0755, true );
mkdir( $tree. '/bin', 0755, true );

foreach( [ 'build.php', 'packages.php', 'applications.php', 'delivered.php' ] as $file )
	copy( $repo. '/bin/'. $file, $tree. '/bin/'. $file );

$copy = static function( string $from, string $to ) use ( &$copy ): void {

	is_dir( $to ) === true || mkdir( $to, 0755, true );

	foreach( array_diff( scandir( $from ) ?: [], [ '.', '..', 'tests' ] ) as $entry )
		is_dir( $from. '/'. $entry ) === true ? $copy( $from. '/'. $entry, $to. '/'. $entry ) : copy( $from. '/'. $entry, $to. '/'. $entry );
};

$copy( $repo. '/features/Hello', $tree. '/features/Hello' );
$copy( $repo. '/features/Basis', $tree. '/features/Basis' );

$application = static fn( array $features ): string => "<?php\nreturn [ 'key' => 'site', 'name' => [ 'de_DE' => 'Seite', 'en_US' => 'Site' ], 'description' => [ 'de_DE' => 'Eine Seite', 'en_US' => 'A site' ],\n\t'features' => [ '"
	. implode( "', '", $features ). "' ], 'next' => null, 'start' => null, 'menu' => null ];\n";

file_put_contents( $tree. '/applications/site.php', $application( [ 'basis', 'hello' ] ) );

[ $status, $stdout, $stderr ] = runScript( $tree. '/bin/build.php', [ $root, $work. '/out-basis', '--base-url', 'https://catalogue.test/packages' ] );
$files = is_dir( $work. '/out-basis' ) === true ? array_diff( scandir( $work. '/out-basis' ) ?: [], [ '.', '..' ] ) : [];

check( 'an application that names Basis is built where the index lists Basis, and refused where the kernel delivers it - the application and the feature named, no file written',
	$delivers === false ? ( $status === 0 && in_array( 'feature-basis-1.0.0.tar.gz', $files, true ) === true )
		: ( $status === 1 && str_contains( $stderr, 'applications/site.php' ) === true && str_contains( $stderr, '"basis"' ) === true && $files === [] ) );

// The refusal is one sentence for each application that names what the kernel delivers: the file, the feature, the kernel and the index, so that a person reads why and what to do
$lines = array_filter( explode( "\n", trim( $stderr ) ) );

check( 'where the kernel delivers Basis the refusal is a single sentence that names the application, the feature, the kernel it was built with and the index, and says to build with another kernel; where it does not there is none',
	$delivers === false ? $stderr === '' : ( count( $lines ) === 1 && str_contains( $stderr, 'applications/site.php' ) === true && str_contains( $stderr, '"basis"' ) === true && str_contains( $stderr, $root ) === true
		&& str_contains( $stderr, 'index' ) === true && str_contains( $stderr, 'build the catalogue with a kernel that does not deliver Basis' ) === true && preg_match( '/[.!?]\s+\S/', $stderr ) !== 1 ) );

file_put_contents( $tree. '/applications/site.php', $application( $delivers === true ? [ 'hello' ] : [ 'basis', 'hello' ] ) );

[ $status, $stdout, $stderr ] = runScript( $tree. '/bin/build.php', [ $root, $work. '/out-hello', '--base-url', 'https://catalogue.test/packages' ] );
$files = array_diff( scandir( $work. '/out-hello' ) ?: [], [ '.', '..' ] );

check( 'an application that names none of what the kernel delivers is built in both cases - Basis listed before Hello, which requires it, where the kernel does not deliver it; where it does the build says it skipped the copy and holds no archive of it',
	$status === 0 && in_array( 'feature-hello-2.0.0.tar.gz', $files, true ) === true && in_array( 'feature-basis-1.0.0.tar.gz', $files, true ) === ( $delivers === false )
	&& str_contains( $stdout, 'skipped  features/Basis' ) === $delivers );

$appData = ninoSandbox( 'delivered' );
echo "\nA module of the kernel of the name that is not the feature\n";

// The kernel of this run with an empty folder where Basis stands: it is a module of the kernel that no feature.php names the key of
\Nino\Filesystem::copyDir( $root. '/_nino', $work. '/odd/_nino' );
\Nino\Filesystem::removeDir( $work. '/odd/_nino/Nino/Modules/Basis' );
mkdir( $work. '/odd/_nino/Nino/Modules/Basis', 0755, true );

[ $status, , $stderr ] = runScript( $repo. '/bin/catalogue.php', [ $work. '/odd' ] );
[ $listed, , $listedError ] = runScript( $repo. '/bin/applicable.php', [ $work. '/odd' ] );

check( 'a folder of the kernel that is no feature Basis ends the preview and the list of what to copy with a sentence that names the folder and says what to do, and with nothing of a stack',
	$status === 1 && $listed === 1 && str_contains( $stderr, 'Modules/Basis' ) === true && str_contains( $stderr, 'no feature "basis"' ) === true && str_contains( $stderr, 'kernel without the folder' ) === true
	&& trim( $listedError ) === trim( $stderr ) && str_contains( $stderr, 'Stack trace' ) === false && count( array_filter( explode( "\n", trim( $stderr ) ) ) ) === 1 );

ninoDone( $appData );
