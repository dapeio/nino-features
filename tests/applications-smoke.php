<?php
declare(strict_types=1);

/**
 *	Nino features
 *	applications-smoke.php	What the kernel does with the applications of this catalogue, in whichever of the two cases the kernel it runs against is -
 *													it delivers Basis as a module of its own, or it does not -: each application is set up in a project of its own, a feature
 *													after the other in the order the file lists them, by \Nino\Applications::place() against an index of this run's own, signed
 *													with a key of this run's own and served by the stub of the client, so that nothing leaves the machine. The archives are the
 *													folders of the features as they are here, without their tests; the entries say what the manifests say.
 *
 *														A. the application is offered: every feature of it is in the index at a version this kernel can run - Basis among them, which the
 *														   kernel delivers or does not - and what Basis offers is a state of the kernel's: "current" where it delivers it and "available"
 *														   where it does not, never a version to update to
 *														B. set up, in the order of the file: Basis is placed where the kernel does not deliver it and left alone where it does - its archive
 *														   is not asked for, no second copy of it is in the project's features/, the kernel names no directory it refused - and every
 *														   other feature is placed and switched on, the application is applied and the Features panel has Basis once
 *														C. a second round places and switches on nothing
 *
 *	Usage: php tests/applications-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/applications-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 2 ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

// The features directory of the projects of this run: what is placed lands here and nowhere in this repository
$work = sys_get_temp_dir(). '/nino-applications-smoke-'. bin2hex( random_bytes( 6 ) );
mkdir( $work. '/project-features', 0700, true );

define( 'NINO_FEATURES_DIR', $work. '/project-features' );
require $root. '/tests/harness.php';
require dirname( __DIR__ ). '/bin/delivered.php';

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo			= dirname( __DIR__ );
$delivers	= deliveredFeatures() !== [];

echo 'Kernel '. \Nino\VERSION. ( $delivers === true ? ' delivers Basis itself' : ' delivers no Basis' ). "\n\n";

// --- The index: the features of the applications as they are in this repository --------------------------------

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privateKey );
$publicKey = openssl_pkey_get_details( $keypair )['key'];

/**
 *	A folder as a tar.gz: the folder is the one directory, the files in the order of their names, the tests of a feature left out
 */
$archive = static function( string $folder ) use ( $repo ): string {

	$base = $repo. '/features/'. $folder;
	$entries = [ $folder. '/' => null ];

	$walk = static function( string $dir, string $prefix ) use ( &$walk, &$entries, $base ): void {

		$names = array_diff( scandir( $dir ) ?: [], [ '.', '..' ] );
		sort( $names, SORT_STRING );

		foreach( $names as $name ) {

			if( $dir === $base && $name === 'tests' )
				continue;

			if( is_dir( $dir. '/'. $name ) === true ) {
				$entries[$prefix. $name. '/'] = null;
				$walk( $dir. '/'. $name, $prefix. $name. '/' );
			}
			else
				$entries[$prefix. $name] = (string) file_get_contents( $dir. '/'. $name );
		}
	};

	$walk( $base, $folder. '/' );

	$out = '';

	foreach( $entries as $name => $content ) {

		$header  = str_pad( $name, 100, "\0" ). "0000644\0". "0000000\0". "0000000\0";
		$header .= str_pad( decoct( $content === null ? 0 : strlen( $content ) ), 11, '0', STR_PAD_LEFT ). "\0". str_pad( decoct( 1700000000 ), 11, '0', STR_PAD_LEFT ). "\0";
		$header .= '        '. ( $content === null ? '5' : '0' ). str_pad( '', 100, "\0" ). "ustar\0". '00';
		$header  = str_pad( $header, 512, "\0" );
		$sum = 0;

		for( $i = 0; $i < 512; $i++ )
			$sum += ord( $header[$i] );

		$header = substr( $header, 0, 148 ). str_pad( decoct( $sum ), 6, '0', STR_PAD_LEFT ). "\0 ". substr( $header, 156 );
		$out .= $header. ( $content === null ? '' : str_pad( $content, (int) ( ceil( strlen( $content ) / 512 ) * 512 ), "\0" ) );
	}

	return gzencode( $out. str_repeat( "\0", 1024 ), 6 );
};

$applications = [];
$wanted = [];

foreach( glob( $repo. '/applications/*.php' ) ?: [] as $file ) {

	$entry = (array) ( static fn( string $path ): mixed => include $path )( $file );
	$applications[] = $entry;
	$wanted = array_merge( $wanted, $entry['features'] );
}

$wanted = array_values( array_unique( $wanted ) );
$remote = [];
$entries = [];

// The key of a feature is the one its feature.php says, read as the file says it - the kernel refuses the folder of a feature it delivers, whatever the file says
foreach( glob( $repo. '/features/*', GLOB_ONLYDIR ) ?: [] as $folder ) {

	$manifest = (array) ( static fn( string $path ): mixed => include $path )( $folder. '/feature.php' );

	if( in_array( $manifest['key'] ?? '', $wanted, true ) === false )
		continue;

	$bytes = $archive( basename( $folder ) );
	$url = 'https://catalogue.test/features/'. $manifest['key']. '-'. $manifest['version']. '.tar.gz';
	$remote[$url] = $bytes;
	$entries[$manifest['key']] = [ 'key' => $manifest['key'], 'name' => $manifest['name'], 'description' => $manifest['description'], 'category' => $manifest['category'], 'version' => $manifest['version'],
		'nino' => $manifest['nino'], 'php' => [ 'ext' => [] ], 'requires' => $manifest['requires'] ?? [], 'directory' => basename( $folder ), 'archive' => $url, 'sha256' => hash( 'sha256', $bytes ), 'size' => strlen( $bytes ), 'released' => '2026-11-13' ];
}

check( 'every feature the applications name is a folder of this repository - the index has them all', array_values( array_diff( $wanted, array_keys( $entries ) ) ) === [] && count( $applications ) >= 2 );

$json = ninoCatalogue( array_values( $entries ), $applications );
openssl_sign( $json, $signature, $privateKey, OPENSSL_ALGO_SHA256 );
$remote[\Nino\Catalogue::DEFAULT_URL] = $json;
$remote[\Nino\Catalogue::DEFAULT_URL. '.sig'] = base64_encode( $signature );

/**
 *	A project of its own: a fresh features/ for it, the catalogue served by the stub, and the files the setup of a project has
 */
$requests = [];
$project = static function( string $name ) use ( &$remote, &$requests, $publicKey ): array {

	foreach( glob( NINO_FEATURES_DIR. '/*', GLOB_ONLYDIR ) ?: [] as $featureDir )
		\Nino\Filesystem::removeDir( $featureDir );

	$requests = [];
	$app = ninoSandbox( $name );
	$dir = ninoSandboxDir( $app );

	foreach( [ '/private/templates', '/private/text', '/public/images', '/private/assets' ] as $sub )
		mkdir( $dir. $sub, 0777, true );

	$app['./nino/filesystem/path']	= $GLOBALS['root'];
	$app['/nino/catalogue/key']		= $publicKey;
	$app['/nino/dir']							= '';

	\Nino\Filesystem::putFileContent( $app, '/config.php', [ '/nino/locales/native' => 'de_DE', '/nino/locales/available' => [ 'de_DE', 'en_US' ], '/nino/modules' => [] ] );

	$app['./nino/fetch/stub'] = static function( string $url, array $options ) use ( &$remote, &$requests ): array {
		$requests[] = $url;
		return isset( $remote[$url] ) === false ? [ 'ok' => false, 'status' => 404, 'error' => 'http 404' ] : [ 'ok' => true, 'status' => 200, 'body' => $remote[$url] ];
	};

	return $app;
};

$sandboxes = [];

foreach( $applications as $application ) {

	echo "\nThe application ". $application['key']. "\n";

	$app = $project( 'applications-'. $application['key'] );
	$sandboxes[] = ninoSandboxDir( $app );
	$catalogue = \Nino\Catalogue::fetch( $app );

	if( is_array( $catalogue ) === false ) {
		check( 'the index of this run is read: '. (string) $catalogue, false );
		continue;
	}

	// A. offered
	$offered = array_column( \Nino\Catalogue::applications( $app, $catalogue ), 'key' );
	$offer = \Nino\Catalogue::offers( $app, $catalogue, \Nino\Packages::FEATURE )['basis'] ?? [];

	check( 'it is offered, with Basis among its features, and what the kernel offers of Basis is "current" where it delivers it and "available" where it does not - never an update',
		in_array( $application['key'], $offered, true ) === true && in_array( 'basis', $application['features'], true ) === true && ( $offer['state'] ?? '' ) === ( $delivers === true ? 'current' : 'available' )
		&& ( $offer['shipped'] ?? null ) === $delivers );

	// B. set up
	$results = [];

	foreach( \Nino\Applications::features( [ $application ] ) as $feature )
		$results[$feature] = \Nino\Applications::place( $app, $catalogue, $feature );

	$basis = $results['basis'] ?? [];
	$others = array_filter( $results, static fn( string $key ): bool => $key !== 'basis', ARRAY_FILTER_USE_KEY );
	$asked = array_filter( $requests, static fn( string $url ): bool => str_contains( $url, '/basis-' ) );

	check( 'Basis is placed where the kernel does not deliver it - its archive asked for, its folder below features/ - and switched on, and where the kernel delivers it nothing is fetched and no second folder is made',
		( $basis['status'] ?? 0 ) === 200 && ( $basis['activated'] ?? false ) === true && ( $basis['placed'] ?? null ) === ( $delivers === false ) && count( $asked ) === ( $delivers === true ? 0 : 1 )
		&& is_dir( NINO_FEATURES_DIR. '/Basis' ) === ( $delivers === false ) );
	check( 'every other feature of it is placed and switched on, each with the answer 200 and no reason',
		count( $others ) === count( $application['features'] ) - 1 && array_filter( $others, static fn( array $result ): bool => $result !== [ 'placed' => true, 'activated' => true, 'reason' => '', 'status' => 200 ] ) === [] );

	unset( $app['./nino/features/all'] );
	$features = \Nino\Features::all( $app );
	$problems = array_filter( array_column( $features, 'problems' ) );

	check( 'the application is applied, the Features panel has Basis once and every feature of the project has nothing against it, and the kernel refused no folder',
		\Nino\Applications::isApplied( $app, $application ) === true && isset( $features['basis'] ) === true && count( array_filter( $features, static fn( array $feature ): bool => $feature['key'] === 'basis' ) ) === 1
		&& $features['basis']['shipped'] === $delivers && $problems === [] && \Nino\Features::rejected( $app ) === [] && ninoWarnings() === [] );

	// C. a second round
	$again = [];

	foreach( \Nino\Applications::features( [ $application ] ) as $feature )
		$again[$feature] = \Nino\Applications::place( $app, $catalogue, $feature );

	check( 'a second round places and switches on nothing: every feature is on already',
		array_filter( $again, static fn( array $result ): bool => $result !== [ 'placed' => false, 'activated' => false, 'reason' => '', 'status' => 200 ] ) === [] );
}

$empty = [];
ninoDone( $empty );
