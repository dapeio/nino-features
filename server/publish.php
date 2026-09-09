<?php
declare(strict_types=1);
/**
 *	Nino features
 *	publish.php		The catalogue's publishing endpoint: one https POST from the
 *								release workflow, and no ssh. It lives in the directory the
 *								catalogue is served from - beside catalogue.json, its
 *								signature and the archives - and takes a release as a
 *								multipart form: the signed catalogue.json, catalogue.json.sig
 *								and the archives that are new. Nothing is written before all
 *								of it holds:
 *
 *								- the token matches (a wrong one is a 401, and nothing else);
 *								- the signature verifies with the public key configured here,
 *								  so a leaked token alone publishes nothing: without the
 *								  private key there is no catalogue this accepts;
 *								- every uploaded archive is one the catalogue lists, with the
 *								  digest and the size the catalogue names for it;
 *								- every archive the catalogue lists is either published
 *								  already, with that digest, or in this upload - the catalogue
 *								  never names an archive that is not there;
 *								- a published archive is never overwritten: an upload of the
 *								  same name with other bytes is a 409. A version is immutable.
 *
 *								Then the new archives are written, and catalogue.json and its
 *								signature are replaced, each through a temporary file and a
 *								rename. The answer is JSON either way.
 *
 *	Configuration - environment variables, or a publish.config.php beside this
 *	file returning an array with the same keys (a container sets the
 *	environment, a plain web server writes the file):
 *
 *	  NINO_CATALOGUE_TOKEN             				the token the workflow sends, at least 32 characters
 *	                                    (`openssl rand -hex 32`), the secret NINO_CATALOGUE_TOKEN there
 *	  NINO_CATALOGUE_PUBKEY         		the PEM public key file the catalogue is signed with
 *	                                    \Nino\Catalogue::PUBLIC_KEY
 *	  NINO_CATALOGUE_DIR                where the files are written and served from;
 *	                                    this file's directory when unset
 *
 *	The request: POST, multipart/form-data, the header `X-Publish-Token: <token>`
 *	(or `Authorization: Bearer <token>`), the fields `catalogue` (catalogue.json),
 *	`signature` (catalogue.json.sig) and `archives[]` (zero or more .tar.gz,
 *	named as the catalogue names them). php.ini has to allow the upload:
 *	upload_max_filesize and post_max_size above the largest archive - 32M and
 *	64M leave room - and a proxy in front of php its own body limit.
 *
 *	tests/publish-smoke.php in dapeio/nino-features is this file's test.
 */

const PUBLISH_MAX_CATALOGUE_BYTES	= 1024 * 1024;
const PUBLISH_MAX_SIGNATURE_BYTES	= 4096;
const PUBLISH_MAX_ARCHIVE_BYTES		= 20 * 1024 * 1024;
const PUBLISH_MIN_TOKEN_LENGTH		= 32;

const PUBLISH_ARCHIVE_PATTERN			= '/^([a-z][a-z0-9-]*)-(\d+\.\d+\.\d+(?:-[0-9A-Za-z.]+)?)\.tar\.gz$/';
const PUBLISH_KEY_PATTERN					= '/^[a-z][a-z0-9-]*$/';
const PUBLISH_VERSION_PATTERN			= '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.]+)?$/';

/**
 *	The configuration: the environment first, publish.config.php beside this
 *	file for what the environment does not say
 *
 *	@return 	array										{ token, publicKey, dir }
 */
function publishConfig(): array {

	$file = is_file( __DIR__. '/publish.config.php' ) === true ? include __DIR__. '/publish.config.php' : [];
	$file = is_array( $file ) === true ? $file : [];

	$read = static function( string $name ) use ( $file ): string {
		$value = getenv( $name );
		if( is_string( $value ) === true && $value !== '' )
			return $value;
		return is_string( $file[$name] ?? null ) === true ? $file[$name] : '';
	};

	$publicKey = '';
	if( $read( 'NINO_CATALOGUE_PUBKEY' ) !== '' && is_file( $read( 'NINO_CATALOGUE_PUBKEY' ) ) === true )
		$publicKey = (string) file_get_contents( $read( 'NINO_CATALOGUE_PUBKEY' ) );

	$dir = $read( 'NINO_CATALOGUE_DIR' );

	return [
		'token'			=> $read( 'NINO_CATALOGUE_TOKEN' ),
		'publicKey'	=> trim( $publicKey ),
		'dir'				=> rtrim( $dir === '' ? __DIR__ : $dir, '/' ),
	];
}

/**
 *	Handle one release. Pure: everything it reads is a parameter, everything
 *	it says is the return value - the test drives it without a web server.
 *
 *	@param		array 		$config				As publishConfig() answers
 *	@param		string		$method				The request method
 *	@param		string		$token				The token the request carried, '' for none
 *	@param		array 		$upload				{ catalogue: path|null, signature: path|null, archives: name => path }
 *
 *	@return 	array										[ http status, body ]
 */
function publishHandle( array $config, string $method, string $token, array $upload ): array {

	if( $method !== 'POST' )
		return [ 405, [ 'error' => 'POST a release: catalogue, signature and archives[]' ] ];

	if( strlen( (string) $config['token'] ) < PUBLISH_MIN_TOKEN_LENGTH )
		return [ 500, [ 'error' => 'the endpoint is not configured: NINO_CATALOGUE_TOKEN has to be at least '. PUBLISH_MIN_TOKEN_LENGTH. ' characters' ] ];

	$publicKey = (string) $config['publicKey'] !== '' ? openssl_pkey_get_public( (string) $config['publicKey'] ) : false;
	if( $publicKey === false )
		return [ 500, [ 'error' => 'the endpoint is not configured: NINO_CATALOGUE_PUBKEY holds no public key' ] ];

	$dir = (string) $config['dir'];
	if( is_dir( $dir ) === false || is_writable( $dir ) === false )  {

		return [ 500, [ 'error' => 'the endpoint is not configured: '.$dir.' is not a writable directory' ] ];
	}
	if( $token === '' || hash_equals( (string) $config['token'], $token ) === false ) {
		usleep( 250000 );
		return [ 401, [ 'error' => 'the token does not match' ] ];
	}

	// --- the catalogue and its signature, believed only together

	$cataloguePath = $upload['catalogue'] ?? null;
	$signaturePath = $upload['signature'] ?? null;

	if( is_string( $cataloguePath ) === false || is_file( $cataloguePath ) === false || is_string( $signaturePath ) === false || is_file( $signaturePath ) === false )
		return [ 400, [ 'error' => 'a release carries the fields "catalogue" and "signature"' ] ];

	if( (int) filesize( $cataloguePath ) > PUBLISH_MAX_CATALOGUE_BYTES || (int) filesize( $signaturePath ) > PUBLISH_MAX_SIGNATURE_BYTES )
		return [ 400, [ 'error' => 'the catalogue or its signature is larger than it can be' ] ];

	$json			= (string) file_get_contents( $cataloguePath );
	$signature	= base64_decode( preg_replace( '/\s+/', '', (string) file_get_contents( $signaturePath ) ) ?? '', true );

	if( $signature === false || $signature === '' || openssl_verify( $json, $signature, $publicKey, OPENSSL_ALGO_SHA256 ) !== 1 )
		return [ 400, [ 'error' => 'the signature does not verify with the public key this endpoint trusts' ] ];

	$entries = publishEntries( $json );
	if( is_string( $entries ) === true )
		return [ 400, [ 'error' => $entries ] ];

	// --- the archives: what was uploaded against what the catalogue says,
	//     and what the catalogue says against what is published

	$published	= [];
	$kept				= [];
	$writes			= [];

	foreach( $upload['archives'] ?? [] as $name => $path ) {

		$name = (string) $name;

		if( preg_match( PUBLISH_ARCHIVE_PATTERN, $name ) !== 1 )
			return [ 400, [ 'error' => 'an archive is named <key>-<version>.tar.gz, not "'. $name. '"' ] ];

		if( is_string( $path ) === false || is_file( $path ) === false )
			return [ 400, [ 'error' => 'the archive "'. $name. '" did not arrive' ] ];

		if( isset( $entries[$name] ) === false )
			return [ 400, [ 'error' => 'the catalogue does not list "'. $name. '"' ] ];

		$size		= (int) filesize( $path );
		$sha256	= hash_file( 'sha256', $path );

		if( $size > PUBLISH_MAX_ARCHIVE_BYTES || $size !== $entries[$name]['size'] || $sha256 !== $entries[$name]['sha256'] )
			return [ 400, [ 'error' => 'the archive "'. $name. '" is not what the catalogue says it is' ] ];

		$target = $dir. '/'. $name;

		if( is_file( $target ) === true ) {
			if( hash_file( 'sha256', $target ) !== $sha256 )
				return [ 409, [ 'error' => '"'. $name. '" is published with other bytes - a version is immutable, release the next one' ] ];
			$kept[] = $name;
			continue;
		}

		$writes[$name]	= $path;
		$published[]		= $name;
	}

	foreach( $entries as $name => $entry ) {

		if( isset( $writes[$name] ) === true )
			continue;

		$target = $dir. '/'. $name;

		if( is_file( $target ) === false )
			return [ 409, [ 'error' => 'the catalogue lists "'. $name. '", which is neither published nor in this upload' ] ];

		if( (int) filesize( $target ) !== $entry['size'] || hash_file( 'sha256', $target ) !== $entry['sha256'] )
			return [ 409, [ 'error' => 'the catalogue names another digest for "'. $name. '" than the published archive has - a version is immutable' ] ];
	}

	// --- write: the archives first, so the catalogue never names a missing
	//     one; each through a temporary file and a rename

	foreach( $writes as $name => $path ) {
		$target = $dir. '/'. $name;
		if( copy( $path, $target. '.tmp' ) === false || rename( $target. '.tmp', $target ) === false ) {
			@unlink( $target. '.tmp' );
			return [ 500, [ 'error' => 'could not write "'. $name. '"' ] ];
		}
		@chmod( $target, 0644 );
	}

	$signatureLine = base64_encode( $signature ). "\n";

	if( file_put_contents( $dir. '/catalogue.json.tmp', $json ) !== strlen( $json ) || file_put_contents( $dir. '/catalogue.json.sig.tmp', $signatureLine ) !== strlen( $signatureLine ) ) {
		@unlink( $dir. '/catalogue.json.tmp' );
		@unlink( $dir. '/catalogue.json.sig.tmp' );
		return [ 500, [ 'error' => 'could not write the catalogue' ] ];
	}

	// The catalogue before its signature: a reader between the two renames
	// sees a signature that does not verify and asks again, never a
	// catalogue it believes for the wrong reason
	if( rename( $dir. '/catalogue.json.tmp', $dir. '/catalogue.json' ) === false || rename( $dir. '/catalogue.json.sig.tmp', $dir. '/catalogue.json.sig' ) === false ) {
		@unlink( $dir. '/catalogue.json.tmp' );
		@unlink( $dir. '/catalogue.json.sig.tmp' );
		return [ 500, [ 'error' => 'could not replace the catalogue' ] ];
	}

	@chmod( $dir. '/catalogue.json', 0644 );
	@chmod( $dir. '/catalogue.json.sig', 0644 );

	return [ 200, [
		'ok'				=> true,
		'features'	=> count( $entries ),
		'published'	=> $published,
		'kept'			=> $kept,
	] ];
}

/**
 *	What the signed catalogue says about its archives - enough of format 1
 *	to hold an upload against it. Nino's \Nino\Catalogue::parse() reads the
 *	rest; what it would refuse, bin/build.php refused before signing.
 *
 *	@param		string		$json
 *
 *	@return 	array|string						archive file name => { key, version, sha256, size }, or what is wrong
 */
function publishEntries( string $json ): array|string {

	$document = json_decode( $json, true );

	if( is_array( $document ) === false || ( $document['format'] ?? null ) !== 1 || is_array( $document['features'] ?? null ) === false )
		return 'the catalogue is not a format 1 document';

	$entries = [];

	foreach( $document['features'] as $index => $entry ) {

		$key			= (string) ( $entry['key'] ?? '' );
		$version	= (string) ( $entry['version'] ?? '' );
		$archive	= (string) ( $entry['archive'] ?? '' );
		$sha256		= strtolower( (string) ( $entry['sha256'] ?? '' ) );
		$size			= $entry['size'] ?? null;

		if( preg_match( PUBLISH_KEY_PATTERN, $key ) !== 1 || preg_match( PUBLISH_VERSION_PATTERN, $version ) !== 1 )
			return 'catalogue entry '. $index. ' has no valid key and version';

		$name = $key. '-'. $version. '.tar.gz';

		if( str_starts_with( $archive, 'https://' ) === false || basename( $archive ) !== $name )
			return 'catalogue entry '. $index. ': "archive" has to be the https url of '. $name;

		if( preg_match( '/^[a-f0-9]{64}$/', $sha256 ) !== 1 || is_int( $size ) === false || $size < 1 || $size > PUBLISH_MAX_ARCHIVE_BYTES )
			return 'catalogue entry '. $index. ': "sha256" and "size" have to name the archive';

		if( isset( $entries[$name] ) === true && ( $entries[$name]['sha256'] !== $sha256 || $entries[$name]['size'] !== $size ) )
			return 'catalogue entry '. $index. ': "'. $name. '" is listed twice with different digests';

		$entries[$name] = [ 'key' => $key, 'version' => $version, 'sha256' => $sha256, 'size' => $size ];
	}

	if( $entries === [] )
		return 'the catalogue lists no features';

	return $entries;
}

/**
 *	The uploaded files of a request, in the shape publishHandle() takes:
 *	only what arrived whole, keyed by the name the client gave it
 *
 *	@param		array 		$files				$_FILES
 *
 *	@return 	array										{ catalogue, signature, archives }
 */
function publishUpload( array $files ): array {

	$one = static function( string $field ) use ( $files ): ?string {
		$file = $files[$field] ?? null;
		return is_array( $file ) === true && ( $file['error'] ?? -1 ) === UPLOAD_ERR_OK && is_string( $file['tmp_name'] ?? null ) === true && is_uploaded_file( $file['tmp_name'] ) === true
			? $file['tmp_name']
			: null;
	};

	$archives = [];
	$many = $files['archives'] ?? null;

	if( is_array( $many ) === true && is_array( $many['name'] ?? null ) === true )
		foreach( $many['name'] as $i => $name )
			if( ( $many['error'][$i] ?? -1 ) === UPLOAD_ERR_OK && is_string( $many['tmp_name'][$i] ?? null ) === true && is_uploaded_file( $many['tmp_name'][$i] ) === true )
				$archives[ basename( (string) $name ) ] = $many['tmp_name'][$i];

	return [ 'catalogue' => $one( 'catalogue' ), 'signature' => $one( 'signature' ), 'archives' => $archives ];
}

// Under a web server this is the request; under the cli it is a library the
// test includes
if( PHP_SAPI !== 'cli' ) {

	$token = (string) ( $_SERVER['HTTP_X_NINO_CATALOGUE_TOKEN'] ?? '' );
	if( $token === '' && preg_match( '/^Bearer\s+(\S+)$/', (string) ( $_SERVER['HTTP_AUTHORIZATION'] ?? '' ), $m ) === 1 )
		$token = $m[1];

	[ $status, $body ] = publishHandle( publishConfig(), (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ), $token, publishUpload( $_FILES ) );

	if( $status !== 200 )
		error_log( 'publish.php: '. $status. ' '. ( $body['error'] ?? '' ) );

	http_response_code( $status );
	header( 'Content-Type: application/json; charset=UTF-8' );
	header( 'Cache-Control: no-store' );
	echo json_encode( $body, JSON_UNESCAPED_SLASHES ), "\n";
}
