<?php
declare(strict_types=1);
/**
 *	Nino features
 *	publish-smoke.php		The publishing endpoint's own test: server/publish.php
 *											driven as a library - every refusal, a release, a second
 *											release that keeps what is published and refuses other
 *											bytes for a published name - and then once through a real
 *											request against php's built-in server, so the multipart
 *											parsing and the headers are exercised too.
 *
 *	Usage: NINO_ROOT=/path/to/nino php tests/publish-smoke.php    (harness only)
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__ ). '/nino';
if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. " - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT\n" );
	exit( 2 );
}
require $root. '/tests/harness.php';
require dirname( __DIR__ ). '/server/publish.php';

$appData	= ninoSandbox( 'publish' );
$work			= ninoSandboxDir( $appData );

// --- helpers -----------------------------------------------------------------

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privateKey );
$publicKey = openssl_pkey_get_details( $keypair )['key'];

$otherKeypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $otherKeypair, $otherPrivateKey );

$token = bin2hex( random_bytes( 32 ) );

function sign( string $data, string $privateKey ): string {
	openssl_sign( $data, $signature, $privateKey, OPENSSL_ALGO_SHA256 );
	return base64_encode( $signature ). "\n";
}

function entryFor( string $key, string $version, string $bytes ): array {
	return [
		'key' => $key, 'name' => ucfirst( $key ), 'version' => $version, 'nino' => '^1.0', 'php' => [ 'ext' => [] ], 'requires' => [],
		'directory' => ucfirst( $key ), 'archive' => 'https://catalogue.test/'. $key. '-'. $version. '.tar.gz',
		'sha256' => hash( 'sha256', $bytes ), 'size' => strlen( $bytes ), 'released' => '2026-09-07',
	];
}

function catalogueJson( array $entries ): string {
	return json_encode( [ 'format' => 1, 'generated' => '2026-09-07T12:00:00Z', 'features' => $entries ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ). "\n";
}

/**
 *	Files as they would have arrived: written below the work directory,
 *	the paths handed over the way publishUpload() hands them
 */
function arrived( string $work, ?string $catalogue, ?string $signature, array $archives ): array {
	$dir = $work. '/upload-'. bin2hex( random_bytes( 4 ) );
	mkdir( $dir );
	$put = static function( string $name, ?string $content ) use ( $dir ): ?string {
		if( $content === null )
			return null;
		file_put_contents( $dir. '/'. $name, $content );
		return $dir. '/'. $name;
	};
	$files = [];
	foreach( $archives as $name => $content )
		$files[$name] = $put( $name, $content );
	return [ 'catalogue' => $put( 'catalogue.json', $catalogue ), 'signature' => $put( 'catalogue.json.sig', $signature ), 'archives' => $files ];
}

$served = $work. '/served';
mkdir( $served );
$config = [ 'token' => $token, 'publicKey' => $publicKey, 'dir' => $served ];

$helper100	= 'helper 1.0.0 archive bytes';
$helper110	= 'helper 1.1.0 archive bytes';
$sample100	= 'sample 1.0.0 archive bytes';

$release1		= catalogueJson( [ entryFor( 'helper', '1.0.0', $helper100 ) ] );
$signature1	= sign( $release1, $privateKey );


// --- refusals, and that nothing is written on any of them ------------------

echo "server/publish.php - what is refused before anything is written\n";

$nothing = static fn( string $served ): bool => ( scandir( $served ) ?: [] ) === [ '.', '..' ];

[ $status ] = publishHandle( $config, 'GET', $token, arrived( $work, $release1, $signature1, [] ) );
check( 'GET is a 405', $status === 405 );

[ $status, $body ] = publishHandle( [ 'token' => 'short', 'publicKey' => $publicKey, 'dir' => $served ], 'POST', 'short', arrived( $work, $release1, $signature1, [] ) );
check( 'a token below 32 characters is a configuration error, not a weak endpoint', $status === 500 && str_contains( $body['error'], 'NINO_CATALOGUE_TOKEN' ) );

[ $status, $body ] = publishHandle( [ 'token' => $token, 'publicKey' => '', 'dir' => $served ], 'POST', $token, arrived( $work, $release1, $signature1, [] ) );
check( 'without a public key nothing is published', $status === 500 && str_contains( $body['error'], 'NINO_CATALOGUE_PUBLIC_KEY' ) );

[ $status, $body ] = publishHandle( [ 'token' => $token, 'publicKey' => $publicKey, 'dir' => $work. '/no-such-dir' ], 'POST', $token, arrived( $work, $release1, $signature1, [] ) );
check( 'a directory that is not there is a configuration error', $status === 500 && str_contains( $body['error'], 'NINO_CATALOGUE_DIR' ) );

[ $status ] = publishHandle( $config, 'POST', '', arrived( $work, $release1, $signature1, [] ) );
check( 'no token is a 401', $status === 401 );
[ $status ] = publishHandle( $config, 'POST', strrev( $token ), arrived( $work, $release1, $signature1, [] ) );
check( 'a wrong token is a 401', $status === 401 );

[ $status ] = publishHandle( $config, 'POST', $token, arrived( $work, null, $signature1, [] ) );
check( 'no catalogue is a 400', $status === 400 );
[ $status ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, null, [] ) );
check( 'no signature is a 400', $status === 400 );

[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, sign( $release1, $otherPrivateKey ), [ 'helper-1.0.0.tar.gz' => $helper100 ] ) );
check( 'a signature by another key is a 400', $status === 400 && str_contains( $body['error'], 'signature' ) );
[ $status ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1. ' ', $signature1, [ 'helper-1.0.0.tar.gz' => $helper100 ] ) );
check( 'a catalogue changed after signing is a 400', $status === 400 );
[ $status ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, 'not base64!', [ 'helper-1.0.0.tar.gz' => $helper100 ] ) );
check( 'a signature that is not one is a 400', $status === 400 );

$notFormat1 = str_replace( '"format": 1', '"format": 2', $release1 );
[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $notFormat1, sign( $notFormat1, $privateKey ), [ 'helper-1.0.0.tar.gz' => $helper100 ] ) );
check( 'a signed document that is not format 1 is a 400', $status === 400 && str_contains( $body['error'], 'format 1' ) );

[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, $signature1, [ '../evil.tar.gz' => $helper100 ] ) );
check( 'an archive not named <key>-<version>.tar.gz is a 400', $status === 400 && str_contains( $body['error'], 'named' ) );
[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, $signature1, [ 'other-1.0.0.tar.gz' => $helper100 ] ) );
check( 'an archive the catalogue does not list is a 400', $status === 400 && str_contains( $body['error'], 'does not list' ) );
[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, $signature1, [ 'helper-1.0.0.tar.gz' => $helper100. 'x' ] ) );
check( 'an archive whose bytes are not what the catalogue says is a 400', $status === 400 && str_contains( $body['error'], 'not what the catalogue says' ) );

[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, $signature1, [] ) );
check( 'a catalogue listing an archive that is neither published nor uploaded is a 409', $status === 409 && str_contains( $body['error'], 'neither published nor in this upload' ) );

check( 'none of that wrote a file', $nothing( $served ) === true );

echo "\n";


// --- a release ---------------------------------------------------------------

echo "server/publish.php - a release, and the next\n";

[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release1, $signature1, [ 'helper-1.0.0.tar.gz' => $helper100 ] ) );
check( 'the first release is a 200 naming what was published', $status === 200 && $body['ok'] === true && $body['published'] === [ 'helper-1.0.0.tar.gz' ] && $body['kept'] === [] && $body['features'] === 1 );
check( 'the archive, the catalogue and the signature are served', file_get_contents( $served. '/helper-1.0.0.tar.gz' ) === $helper100
	&& file_get_contents( $served. '/catalogue.json' ) === $release1 && file_get_contents( $served. '/catalogue.json.sig' ) === $signature1 );
check( 'no temporary file stayed behind', ( scandir( $served ) ?: [] ) === [ '.', '..', 'catalogue.json', 'catalogue.json.sig', 'helper-1.0.0.tar.gz' ] );

// The second release lists both versions and both features, uploads only
// what is new - the workflow's way - and the published archive stays
$release2		= catalogueJson( [ entryFor( 'helper', '1.1.0', $helper110 ), entryFor( 'helper', '1.0.0', $helper100 ), entryFor( 'sample', '1.0.0', $sample100 ) ] );
$signature2	= sign( $release2, $privateKey );

[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release2, $signature2, [ 'helper-1.1.0.tar.gz' => $helper110, 'sample-1.0.0.tar.gz' => $sample100 ] ) );
check( 'the next release publishes the new archives beside the old', $status === 200 && $body['published'] === [ 'helper-1.1.0.tar.gz', 'sample-1.0.0.tar.gz' ] && $body['features'] === 3
	&& file_get_contents( $served. '/helper-1.1.0.tar.gz' ) === $helper110 && file_get_contents( $served. '/sample-1.0.0.tar.gz' ) === $sample100 && file_get_contents( $served. '/helper-1.0.0.tar.gz' ) === $helper100 );
check( 'the catalogue and the signature are the new ones', file_get_contents( $served. '/catalogue.json' ) === $release2 && file_get_contents( $served. '/catalogue.json.sig' ) === $signature2 );

[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $release2, $signature2, [ 'helper-1.1.0.tar.gz' => $helper110 ] ) );
check( 'a re-run uploading a published archive with the same bytes keeps it', $status === 200 && $body['published'] === [] && $body['kept'] === [ 'helper-1.1.0.tar.gz' ] );

// Other bytes under a published name: refused, and the catalogue that
// names them stays unpublished
$evil			= catalogueJson( [ entryFor( 'helper', '1.1.0', $helper110. ' tampered' ), entryFor( 'helper', '1.0.0', $helper100 ), entryFor( 'sample', '1.0.0', $sample100 ) ] );
[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $evil, sign( $evil, $privateKey ), [ 'helper-1.1.0.tar.gz' => $helper110. ' tampered' ] ) );
check( 'other bytes for a published name are a 409 - a version is immutable', $status === 409 && str_contains( $body['error'], 'immutable' ) );
[ $status, $body ] = publishHandle( $config, 'POST', $token, arrived( $work, $evil, sign( $evil, $privateKey ), [] ) );
check( 'so is a catalogue that names another digest for a published archive', $status === 409 && str_contains( $body['error'], 'immutable' ) );
check( 'and the served catalogue, its signature and the archive are untouched', file_get_contents( $served. '/catalogue.json' ) === $release2 && file_get_contents( $served. '/catalogue.json.sig' ) === $signature2 && file_get_contents( $served. '/helper-1.1.0.tar.gz' ) === $helper110 );

// A catalogue may stop listing a version - the archive stays on disk,
// nothing points at it any more
$release3 = catalogueJson( [ entryFor( 'helper', '1.1.0', $helper110 ), entryFor( 'sample', '1.0.0', $sample100 ) ] );
[ $status ] = publishHandle( $config, 'POST', $token, arrived( $work, $release3, sign( $release3, $privateKey ), [] ) );
check( 'a catalogue that lists fewer versions is published without an upload; the archives stay', $status === 200 && is_file( $served. '/helper-1.0.0.tar.gz' ) === true && file_get_contents( $served. '/catalogue.json' ) === $release3 );

check( 'a Nino kernel with \\Nino\\Catalogue verifies what was published', class_exists( '\\Nino\\Catalogue' ) === false
	|| \Nino\Catalogue::verify( (string) file_get_contents( $served. '/catalogue.json' ), (string) file_get_contents( $served. '/catalogue.json.sig' ), $publicKey ) === true );

echo "\n";


// --- configuration -----------------------------------------------------------

echo "server/publish.php - configuration from the environment and the file\n";

file_put_contents( $work. '/key.pub.pem', $publicKey );
putenv( 'NINO_CATALOGUE_TOKEN='. $token );
putenv( 'NINO_CATALOGUE_PUBLIC_KEY_FILE='. $work. '/key.pub.pem' );
putenv( 'NINO_CATALOGUE_DIR='. $served. '/' );
$read = publishConfig();
check( 'the environment names token, key file and directory', $read['token'] === $token && $read['publicKey'] === trim( $publicKey ) && $read['dir'] === $served );
putenv( 'NINO_CATALOGUE_PUBLIC_KEY='. $publicKey );
check( 'a key in the environment wins over the file', publishConfig()['publicKey'] === trim( $publicKey ) );
putenv( 'NINO_CATALOGUE_TOKEN' );
putenv( 'NINO_CATALOGUE_PUBLIC_KEY' );
putenv( 'NINO_CATALOGUE_PUBLIC_KEY_FILE' );
putenv( 'NINO_CATALOGUE_DIR' );
check( 'without either the directory is the script\'s own and the rest empty', publishConfig()['dir'] === dirname( __DIR__ ). '/server' && publishConfig()['token'] === '' && publishConfig()['publicKey'] === '' );

echo "\n";


// --- once for real: php's built-in server ------------------------------------

echo "server/publish.php - one request through php -S\n";

$docroot = $work. '/docroot';
mkdir( $docroot );
copy( dirname( __DIR__ ). '/server/publish.php', $docroot. '/publish.php' );
file_put_contents( $docroot. '/publish.config.php', '<?php return '. var_export( [ 'NINO_CATALOGUE_TOKEN' => $token, 'NINO_CATALOGUE_PUBLIC_KEY' => $publicKey ], true ). ';' );

$port		= 18000 + random_int( 0, 999 );
$server	= @proc_open( [ PHP_BINARY, '-S', '127.0.0.1:'. $port, '-t', $docroot ], [ 0 => [ 'file', '/dev/null', 'r' ], 1 => [ 'file', '/dev/null', 'w' ], 2 => [ 'file', $work. '/server.log', 'w' ] ], $pipes );

$up = false;
for( $i = 0; $i < 40 && $up === false; $i++ ) {
	usleep( 50000 );
	$probe = @fsockopen( '127.0.0.1', $port, $errno, $errstr, 0.2 );
	if( is_resource( $probe ) === true ) { fclose( $probe ); $up = true; }
}

function multipart( array $fields, array $files ): array {
	$boundary = '----nino'. bin2hex( random_bytes( 8 ) );
	$body = '';
	foreach( $fields as $name => $value )
		$body .= '--'. $boundary. "\r\n". 'Content-Disposition: form-data; name="'. $name. '"'. "\r\n\r\n". $value. "\r\n";
	foreach( $files as [ $field, $name, $content ] )
		$body .= '--'. $boundary. "\r\n". 'Content-Disposition: form-data; name="'. $field. '"; filename="'. $name. '"'. "\r\n". "Content-Type: application/octet-stream\r\n\r\n". $content. "\r\n";
	$body .= '--'. $boundary. "--\r\n";
	return [ 'multipart/form-data; boundary='. $boundary, $body ];
}

function post( int $port, array $headers, string $contentType, string $body ): array {
	$context = stream_context_create( [ 'http' => [ 'method' => 'POST', 'header' => implode( "\r\n", array_merge( $headers, [ 'Content-Type: '. $contentType ] ) ), 'content' => $body, 'ignore_errors' => true, 'timeout' => 10 ] ] );
	$answer = @file_get_contents( 'http://127.0.0.1:'. $port. '/publish.php', false, $context );
	$status = 0;
	foreach( $http_response_header ?? [] as $line )
		if( preg_match( '#^HTTP/\S+\s+(\d{3})#', $line, $m ) === 1 )
			$status = (int) $m[1];
	return [ $status, json_decode( (string) $answer, true ) ];
}

if( $up === false ) {
	echo "  (php -S did not come up on 127.0.0.1:$port - the request round trip is skipped)\n";
}
else {
	$release	= catalogueJson( [ entryFor( 'helper', '2.0.0', 'helper 2.0.0 over http' ) ] );
	[ $type, $body ] = multipart( [], [ [ 'catalogue', 'catalogue.json', $release ], [ 'signature', 'catalogue.json.sig', sign( $release, $privateKey ) ], [ 'archives[]', 'helper-2.0.0.tar.gz', 'helper 2.0.0 over http' ] ] );

	[ $status, $answer ] = post( $port, [], $type, $body );
	check( 'without the token header the request is a 401', $status === 401 && isset( $answer['error'] ) );

	[ $status, $answer ] = post( $port, [ 'X-Publish-Token: '. $token ], $type, $body );
	check( 'with X-Publish-Token the release is published', $status === 200 && ( $answer['ok'] ?? false ) === true && $answer['published'] === [ 'helper-2.0.0.tar.gz' ] );
	check( 'the docroot serves the three files beside publish.php', file_get_contents( $docroot. '/helper-2.0.0.tar.gz' ) === 'helper 2.0.0 over http' && file_get_contents( $docroot. '/catalogue.json' ) === $release && is_file( $docroot. '/catalogue.json.sig' ) === true );

	[ $status, $answer ] = post( $port, [ 'Authorization: Bearer '. $token ], $type, $body );
	check( 'a bearer header is taken too, and a re-run keeps the archive', $status === 200 && $answer['kept'] === [ 'helper-2.0.0.tar.gz' ] );

	$context = stream_context_create( [ 'http' => [ 'method' => 'GET', 'ignore_errors' => true, 'timeout' => 10 ] ] );
	@file_get_contents( 'http://127.0.0.1:'. $port. '/publish.php', false, $context );
	check( 'a GET is a 405', in_array( 'HTTP/1.1 405 Method Not Allowed', $http_response_header ?? [], true ) === true );
}

if( is_resource( $server ) === true ) {
	proc_terminate( $server );
	proc_close( $server );
}

echo "\n";

ninoDone( $appData );
