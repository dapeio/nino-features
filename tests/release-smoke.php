<?php
declare(strict_types=1);
/**
 *	Nino features
 *	release-smoke.php		bin/release.sh end to end, without GitHub and without
 *											the internet: a keypair per run, server/publish.php on
 *											php's built-in server as the endpoint, a dry run that
 *											posts nothing, the lenient/strict behaviour on a feature
 *											stripped of its changelog, readme and tests, a release
 *											that publishes, and a second release of the same version
 *											that keeps the archive.
 *
 *	Usage: NINO_ROOT=/path/to/nino php tests/release-smoke.php    (harness only)
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__ ). '/nino';
if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. " - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT\n" );
	exit( 2 );
}
require $root. '/tests/harness.php';

$appData	= ninoSandbox( 'release' );
$work			= ninoSandboxDir( $appData );
$repo			= dirname( __DIR__ );
$release	= $repo. '/bin/release.sh';

// --- a key, a token, the endpoint on php -S -----------------------------------

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privateKey );
$publicKey = openssl_pkey_get_details( $keypair )['key'];
file_put_contents( $work. '/catalogue-key.pem', $privateKey );

$token	 = bin2hex( random_bytes( 32 ) );
$docroot = $work. '/docroot';
mkdir( $docroot );
copy( $repo. '/server/publish.php', $docroot. '/publish.php' );
file_put_contents( $docroot. '/publish.config.php', '<?php return '. var_export( [ 'NINO_CATALOGUE_TOKEN' => $token, 'NINO_CATALOGUE_PUBLIC_KEY' => $publicKey ], true ). ';' );

$port		= 18000 + random_int( 0, 999 );
$server	= @proc_open( [ PHP_BINARY, '-S', '127.0.0.1:'. $port, '-t', $docroot ], [ 0 => [ 'file', '/dev/null', 'r' ], 1 => [ 'file', '/dev/null', 'w' ], 2 => [ 'file', $work. '/server.log', 'w' ] ], $pipes );
register_shutdown_function( static function() use ( $server ): void {
	if( is_resource( $server ) === true ) {
		proc_terminate( $server );
		proc_close( $server );
	}
} );

$up = false;
for( $i = 0; $i < 40 && $up === false; $i++ ) {
	usleep( 50000 );
	$probe = @fsockopen( '127.0.0.1', $port, $errno, $errstr, 0.2 );
	if( is_resource( $probe ) === true ) { fclose( $probe ); $up = true; }
}

/**
 *	Run the script with an environment of its own; [ status, stdout, stderr ]
 */
function runRelease( string $release, array $env, array $args ): array {
	$descriptors = [ 0 => [ 'file', '/dev/null', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ];
	$command = array_merge( [ 'sh', $release ], $args );
	$process = proc_open( $command, $descriptors, $pipes, null, array_merge( [ 'PATH' => (string) getenv( 'PATH' ), 'HOME' => (string) getenv( 'HOME' ) ], $env ) );
	$stdout = (string) stream_get_contents( $pipes[1] );
	$stderr = (string) stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	return [ proc_close( $process ), $stdout, $stderr ];
}

// The feature under test: the first the repository holds, with its version
$manifests = json_decode( (string) shell_exec( PHP_BINARY. ' '. escapeshellarg( $repo. '/bin/catalogue.php' ). ' '. escapeshellarg( $root ) ), true )['features'] ?? [];
$feature	 = $manifests[0] ?? null;
check( 'the repository holds a feature to release', is_array( $feature ) === true );
$key		 = (string) ( $feature['key'] ?? '' );
$version = (string) ( $feature['version'] ?? '' );
$archive = $key. '-'. $version. '.tar.gz';

$hadCopy = is_dir( $root. '/features/'. (string) ( $feature['directory'] ?? '' ) );

$dist = $work. '/dist';
$env	= [
	'NINO_ROOT'			=> $root,
	'CATALOGUE_URL'	=> 'https://catalogue.example',
	'PUBLISH_URL'		=> 'http://127.0.0.1:'. $port. '/publish.php',
	'CATALOGUE_KEY'	=> $work. '/catalogue-key.pem',
	'PUBLISH_TOKEN'	=> $token,
	'DIST'					=> $dist,
];


// --- refusals ----------------------------------------------------------------

echo "bin/release.sh - what is refused before anything runs\n";

[ $status, , $stderr ] = runRelease( $release, $env, [] );
check( 'no key is a usage error', $status === 2 && str_contains( $stderr, 'Usage' ) === true );
[ $status, , $stderr ] = runRelease( $release, $env, [ 'Not-A-Key' ] );
check( 'a key that is not a slug is refused', $status === 2 && str_contains( $stderr, 'not a feature key' ) === true );
[ $status, , $stderr ] = runRelease( $release, array_merge( $env, [ 'CATALOGUE_KEY' => $work. '/missing.pem' ] ), [ $key, '--offline' ] );
check( 'a missing signing key is refused', $status === 2 && str_contains( $stderr, 'CATALOGUE_KEY' ) === true );
[ $status, , $stderr ] = runRelease( $release, array_merge( $env, [ 'PUBLISH_TOKEN' => '' ] ), [ $key, '--offline' ] );
check( 'a missing token is refused unless it is a dry run', $status === 2 && str_contains( $stderr, 'PUBLISH_TOKEN' ) === true );
[ $status, , $stderr ] = runRelease( $release, $env, [ 'no-such-feature', '--offline', '--dry-run' ] );
check( 'an unknown key is refused', $status === 1 && str_contains( $stderr, 'No feature' ) === true );
check( 'nothing was written', is_dir( $dist ) === false || ( scandir( $dist ) ?: [] ) === [ '.', '..' ] );

echo "\n";


// --- a dry run ---------------------------------------------------------------

echo "bin/release.sh --dry-run --offline - test, build, sign, post nothing\n";

[ $status, $stdout, $stderr ] = runRelease( $release, array_merge( $env, [ 'PUBLISH_TOKEN' => '' ] ), [ $key, '--dry-run', '--offline' ] );
check( 'the dry run succeeds without a token', $status === 0 );
check( 'it ran the feature\'s test and built the archive', str_contains( $stdout, 'checks, 0 failed' ) === true && str_contains( $stdout, 'built    ' ) === true && str_contains( $stdout, 'dry run: nothing posted' ) === true );
check( 'dist holds the archive, the catalogue and its signature', is_file( $dist. '/'. $archive ) === true && filesize( $dist. '/'. $archive ) > 0 && is_file( $dist. '/catalogue.json' ) === true && is_file( $dist. '/catalogue.json.sig' ) === true );
check( 'the signature verifies with the public key', openssl_verify( (string) file_get_contents( $dist. '/catalogue.json' ), (string) base64_decode( trim( (string) file_get_contents( $dist. '/catalogue.json.sig' ) ) ), $publicKey, OPENSSL_ALGO_SHA256 ) === 1 );
check( 'nothing reached the endpoint', is_file( $docroot. '/catalogue.json' ) === false && is_file( $docroot. '/'. $archive ) === false );
check( 'the checkout is as it was: no copy left behind, a copy that was there left alone', is_dir( $root. '/features/'. $feature['directory'] ) === $hadCopy );

echo "\n";


// --- lenient by default, strict on request ------------------------------------

echo "bin/release.sh - a feature without changelog, readme and tests\n";

// release.sh reads features from its own repository ($here/features), so a
// feature stripped of the three has to live in a repository of its own: a
// copy of bin/ and server/ (release.sh needs both) plus one feature, with
// CHANGELOG.md, README.md and tests/ removed below the copy
$directory	= (string) ( $feature['directory'] ?? '' );
$bare				= $work. '/bare';
mkdir( $bare, 0755, true );
foreach( [ 'bin', 'server' ] as $part )
	shell_exec( 'cp -R '. escapeshellarg( $repo. '/'. $part ). ' '. escapeshellarg( $bare. '/'. $part ) );

mkdir( $bare. '/features', 0755, true );
$bareFeature = $bare. '/features/'. $directory;
shell_exec( 'cp -R '. escapeshellarg( $repo. '/features/'. $directory ). ' '. escapeshellarg( $bareFeature ) );
foreach( [ 'CHANGELOG.md', 'README.md' ] as $file )
	@unlink( $bareFeature. '/'. $file );
shell_exec( 'rm -rf '. escapeshellarg( $bareFeature. '/tests' ) );

$bareEnv = array_merge( $env, [ 'DIST' => $work. '/bare-dist', 'PUBLISH_TOKEN' => '' ] );

if( $hadCopy === true ) {

	// features/$directory already sits in the checkout (bin/check.sh placed
	// it, with its own tests) - release.sh tests that copy, not the bare
	// one, so the no-tests notes and refusals never fire here
	echo "  (features/$directory is in the checkout - its tests are found there, the no-tests checks are skipped)\n";

	[ $status, $stdout ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline' ] );
	check( 'without changelog and readme the dry run still succeeds', $status === 0 );
	check( 'and says what it did not find', str_contains( $stdout, 'no CHANGELOG.md entry' ) === true );

	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline', '--strict' ] );
	check( '--strict refuses the missing changelog entry', $status === 1 && str_contains( $stderr, 'CHANGELOG.md' ) === true );

	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', array_merge( $bareEnv, [ 'RELEASE_STRICT' => '1' ] ), [ $key, '--dry-run', '--offline' ] );
	check( 'RELEASE_STRICT=1 does the same', $status === 1 && str_contains( $stderr, 'CHANGELOG.md' ) === true );

	file_put_contents( $bareFeature. '/CHANGELOG.md', "# Changelog\n\n## $version — 2026-01-01\n\n- a note.\n" );
	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline', '--strict' ] );
	check( 'with the entry back, strict refuses the missing README.md', $status === 1 && str_contains( $stderr, 'README.md' ) === true );
}
else {

	[ $status, $stdout ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline' ] );
	check( 'without changelog, readme and tests the dry run still succeeds', $status === 0 );
	check( 'and says what it did not find', str_contains( $stdout, 'no CHANGELOG.md entry' ) === true && str_contains( $stdout, 'carries no tests/' ) === true );
	check( 'the archive was built all the same', is_file( $work. '/bare-dist/'. $archive ) === true && filesize( $work. '/bare-dist/'. $archive ) > 0 );

	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline', '--strict' ] );
	check( '--strict refuses the missing changelog entry', $status === 1 && str_contains( $stderr, 'CHANGELOG.md' ) === true );

	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', array_merge( $bareEnv, [ 'RELEASE_STRICT' => '1' ] ), [ $key, '--dry-run', '--offline' ] );
	check( 'RELEASE_STRICT=1 does the same', $status === 1 && str_contains( $stderr, 'CHANGELOG.md' ) === true );

	file_put_contents( $bareFeature. '/CHANGELOG.md', "# Changelog\n\n## $version — 2026-01-01\n\n- a note.\n" );
	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline', '--strict' ] );
	check( 'with the entry back, strict refuses the missing README.md', $status === 1 && str_contains( $stderr, 'README.md' ) === true );

	file_put_contents( $bareFeature. '/README.md', "# $key\n\nWhat it does.\n" );
	[ $status, , $stderr ] = runRelease( $bare. '/bin/release.sh', $bareEnv, [ $key, '--dry-run', '--offline', '--strict' ] );
	check( 'with a readme back, strict refuses the missing test', $status === 1 && str_contains( $stderr, 'tests/' ) === true );
}

check( 'the checkout is as it was after the bare runs too', is_dir( $root. '/features/'. $directory ) === $hadCopy );

echo "\n";


// --- a release ---------------------------------------------------------------

echo "bin/release.sh --offline - the same, then published\n";

if( $up === false ) {
	echo "  (php -S did not come up on 127.0.0.1:$port - the publishing part is skipped)\n";
}
else {
	$firstArchive = (string) file_get_contents( $dist. '/'. $archive );

	[ $status, $stdout, $stderr ] = runRelease( $release, $env, [ $key, '--offline' ] );
	check( 'the release succeeds', $status === 0 && str_contains( $stdout, 'Published '. $key. ' '. $version ) === true );
	check( 'the archive of the dry run was kept, not rebuilt', str_contains( $stdout, 'kept     ' ) === true && (string) file_get_contents( $dist. '/'. $archive ) === $firstArchive );
	check( 'the endpoint took the release: the three files are served', (string) file_get_contents( $docroot. '/'. $archive ) === $firstArchive
		&& (string) file_get_contents( $docroot. '/catalogue.json' ) === (string) file_get_contents( $dist. '/catalogue.json' )
		&& is_file( $docroot. '/catalogue.json.sig' ) === true );
	check( 'the endpoint answered with what it published', str_contains( $stdout, '"published":["'. $archive. '"]' ) === true );

	[ $status, $stdout ] = runRelease( $release, $env, [ $key, '--offline' ] );
	check( 'a second release of the same version keeps the archive on both sides', $status === 0 && str_contains( $stdout, '"kept":["'. $archive. '"]' ) === true );

	[ $status, , $stderr ] = runRelease( $release, array_merge( $env, [ 'PUBLISH_TOKEN' => strrev( $token ) ] ), [ $key, '--offline' ] );
	check( 'a wrong token is a failed release, and says so', $status === 1 && str_contains( $stderr, 'answered 401' ) === true );
}

echo "\n";

ninoDone( $appData );
