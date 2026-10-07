<?php
declare(strict_types=1);
/**
 *	Nino features
 *	release-smoke.php		bin/release.sh end to end, without a server: a keypair per
 *											run, a directory as the target (rsync works between two
 *											paths as well as over ssh) and --quick. Three runs from a
 *											copy of the repository: the first publishes every feature
 *											and a catalogue that verifies and parses, the second
 *											changes no byte, the third after a version bump of Hello
 *											replaces its archive and leaves every other one as it was;
 *											a dry run uploads nothing, and a file only public/ holds
 *											is not put back. Skipped where rsync is missing.
 *
 *	Usage: NINO_ROOT=/path/to/nino php tests/release-smoke.php    (harness only)
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__ ). '/nino';
if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. " - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT\n" );
	exit( 2 );
}

if( trim( (string) shell_exec( 'command -v rsync' ) ) === '' ) {
	echo "release-smoke: rsync is not installed - skipped\n";
	exit( 0 );
}

$work = sys_get_temp_dir(). '/nino-release-smoke-'. uniqid();
mkdir( $work. '/features', 0700, true );

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $work. '/features' );
require $root. '/tests/harness.php';

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo = dirname( __DIR__ );
$copy = $work. '/repo';

// The script writes public/ beside itself, so it runs from a copy and this
// repository's own public/ stays as it is
mkdir( $copy );
foreach( [ 'bin', 'features' ] as $directory )
	shell_exec( 'cp -R '. escapeshellarg( $repo. '/'. $directory ). ' '. escapeshellarg( $copy. '/' ) );

/**
 *	Run the copy's script with an environment of its own; [ status, stdout, stderr ]
 */
function runRelease( string $copy, array $env, array $args ): array {

	$descriptors = [ 0 => [ 'file', '/dev/null', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ];
	$process = proc_open( array_merge( [ 'sh', $copy. '/bin/release.sh' ], $args ), $descriptors, $pipes, null, array_merge( [ 'PATH' => (string) getenv( 'PATH' ), 'HOME' => (string) getenv( 'HOME' ) ], $env ) );
	$stdout = (string) stream_get_contents( $pipes[1] );
	$stderr = (string) stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );

	return [ proc_close( $process ), $stdout, $stderr ];
}

/**
 *	@return 	array										relative path => sha256, every file below a directory
 */
function listing( string $directory ): array {

	$files = [];
	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		$files[ substr( (string) $file->getPathname(), strlen( $directory ) + 1 ) ] = hash_file( 'sha256', (string) $file->getPathname() );
	ksort( $files );

	return $files;
}

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privateKey );
$publicKey = openssl_pkey_get_details( $keypair )['key'];
file_put_contents( $work. '/catalogue-key.pem', $privateKey );

$target	= $work. '/server';
mkdir( $target );
$env		= [ 'NINO_ROOT' => $root, 'NINO_CATALOGUE_KEY' => $work. '/catalogue-key.pem', 'NINO_CATALOGUE_TARGET' => $target. '/', 'NINO_CATALOGUE_URL' => 'https://catalogue.test' ];

$directories = array_filter( scandir( $repo. '/features' ) ?: [], static fn( string $entry ): bool => $entry[0] !== '.' && is_dir( $repo. '/features/'. $entry ) === true );

echo "bin/release.sh - a dry run\n";

[ $status, $stdout ] = runRelease( $copy, $env, [ '--quick', '--dry-run' ] );
check( 'a dry run succeeds', $status === 0 );
check( 'it builds into public/', count( glob( $copy. '/public/*.tar.gz' ) ?: [] ) === count( $directories ) && is_file( $copy. '/public/catalogue.json' ) === true );
check( 'and uploads nothing', listing( $target ) === [] && str_contains( $stdout, 'published to' ) === false );

echo "\nbin/release.sh - the first release\n";

[ $status, $stdout, $stderr ] = runRelease( $copy, $env, [ '--quick' ] );
check( 'the release succeeds', $status === 0 && str_contains( $stdout, 'published to' ) === true );

$archives	= glob( $target. '/*.tar.gz' ) ?: [];
$json			= (string) @file_get_contents( $target. '/catalogue.json' );
$parsed		= \Nino\Catalogue::parse( $json );
check( 'the server holds one archive per feature directory', count( $archives ) === count( $directories ) );
check( 'the catalogue verifies with the public key', \Nino\Catalogue::verify( $json, (string) @file_get_contents( $target. '/catalogue.json.sig' ), $publicKey ) === true );
check( 'the kernel parses it, one entry per feature', is_array( $parsed ) === true && count( $parsed['features'] ) === count( $directories ) );
check( 'and every entry names an archive that is there, with its digest', is_array( $parsed ) === true && array_filter( $parsed['features'], fn( array $entry ): bool => is_file( $target. '/'. basename( $entry['archive'] ) ) === false || hash_file( 'sha256', $target. '/'. basename( $entry['archive'] ) ) !== $entry['sha256'] ) === [] );

$first = listing( $target );

echo "\nbin/release.sh - a second release, nothing changed\n";

[ $status ] = runRelease( $copy, $env, [ '--quick' ] );
check( 'the release succeeds', $status === 0 );
check( 'not a byte of the server changed, no file added or removed', listing( $target ) === $first );

file_put_contents( $copy. '/public/left-over.txt', 'from an earlier run' );
[ $status ] = runRelease( $copy, $env, [ '--quick' ] );
check( 'a file public/ holds and the server does not is not put back', $status === 0 && is_file( $target. '/left-over.txt' ) === false );

echo "\nbin/release.sh - after a version bump of Hello\n";

$manifest = $copy. '/features/Hello/feature.php';
$source = (string) file_get_contents( $manifest );
preg_match( "/'version'\t+=> '(\d+\.\d+\.)(\d+)'/", $source, $version );
$old = $version[1]. $version[2];
$new = $version[1]. ( (int) $version[2] + 1 );
file_put_contents( $manifest, str_replace( "'". $old. "'", "'". $new. "'", $source ) );

[ $status ] = runRelease( $copy, $env, [ '--quick' ] );
$second = listing( $target );
check( 'the release succeeds', $status === 0 );
check( 'hello-'. $old. ' is replaced by hello-'. $new, isset( $first['hello-'. $old. '.tar.gz'] ) === true && isset( $second['hello-'. $old. '.tar.gz'] ) === false && isset( $second['hello-'. $new. '.tar.gz'] ) === true );
check( 'every other archive is byte for byte what it was', array_diff_key( array_filter( $second, static fn( string $file ): bool => str_ends_with( $file, '.tar.gz' ) === true, ARRAY_FILTER_USE_KEY ), [ 'hello-'. $new. '.tar.gz' => 1 ] ) === array_diff_key( array_filter( $first, static fn( string $file ): bool => str_ends_with( $file, '.tar.gz' ) === true, ARRAY_FILTER_USE_KEY ), [ 'hello-'. $old. '.tar.gz' => 1 ] ) );

$json = (string) @file_get_contents( $target. '/catalogue.json' );
check( 'the catalogue names the new version and still verifies', str_contains( $json, 'hello-'. $new. '.tar.gz' ) === true && str_contains( $json, 'hello-'. $old. '.tar.gz' ) === false && \Nino\Catalogue::verify( $json, (string) @file_get_contents( $target. '/catalogue.json.sig' ), $publicKey ) === true );

echo "\n";

$appData = [];
ninoDone( $appData );
