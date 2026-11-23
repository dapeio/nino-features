<?php
declare(strict_types=1);

/**
 *	Nino features
 *	applications-smoke.php	What the kernel does with the applications of this catalogue: each application is set up in a project of its own, a feature
 *													after the other in the order the file lists them, by \Nino\Applications::place() against an index of this run's own, signed
 *													with a key of this run's own and served by the stub of the client, so that nothing leaves the machine. The archives are the
 *													folders of the features as they are here, without their tests; the entries say what the manifests say.
 *
 *														A. the application is offered: every feature of it is in the index at a version this kernel can run - Basis among them, which is
 *														   a feature of the catalogue like the others and is offered as available
 *														B. set up, in the order of the file: Basis is placed first, its archive asked for once, and every other feature is placed and
 *														   switched on, the application is applied and the Features panel has Basis once
 *														C. a second round places and switches on nothing
 *														D. the application Website, which provides the start page: the wizard's steps run to their end - Setup, the account, each feature of
 *														   it placed and switched on through the Applications step, Finish -, and the project has the pages of Website start in components, drawn
 *														   by requests, and no page of the starter of the kernel beside them
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

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$repo			= dirname( __DIR__ );

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

// The key of a feature is the one its feature.php says
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

	check( 'it is offered, with Basis among its features, and what the kernel offers of Basis is "available": it is a feature of the catalogue like the others',
		in_array( $application['key'], $offered, true ) === true && in_array( 'basis', $application['features'], true ) === true && ( $offer['state'] ?? '' ) === 'available' );

	// B. set up
	$results = [];

	foreach( \Nino\Applications::features( [ $application ] ) as $feature )
		$results[$feature] = \Nino\Applications::place( $app, $catalogue, $feature );

	$basis = $results['basis'] ?? [];
	$others = array_filter( $results, static fn( string $key ): bool => $key !== 'basis', ARRAY_FILTER_USE_KEY );
	$asked = array_filter( $requests, static fn( string $url ): bool => str_contains( $url, '/basis-' ) );

	check( 'Basis is placed first - its archive asked for once, its folder below features/ - and switched on',
		( $basis['status'] ?? 0 ) === 200 && ( $basis['activated'] ?? false ) === true && ( $basis['placed'] ?? null ) === true && count( $asked ) === 1 && is_dir( NINO_FEATURES_DIR. '/Basis' ) === true );
	check( 'every other feature of it is placed and switched on, each with the answer 200 and no reason',
		count( $others ) === count( $application['features'] ) - 1 && array_filter( $others, static fn( array $result ): bool => $result !== [ 'placed' => true, 'activated' => true, 'reason' => '', 'status' => 200 ] ) === [] );

	unset( $app['./nino/features/all'] );
	$features = \Nino\Features::all( $app );
	$problems = array_filter( array_column( $features, 'problems' ) );

	check( 'the application is applied, the Features panel has Basis once and every feature of the project has nothing against it, and the kernel refused no folder',
		\Nino\Applications::isApplied( $app, $application ) === true && isset( $features['basis'] ) === true && count( array_filter( $features, static fn( array $feature ): bool => $feature['key'] === 'basis' ) ) === 1
		&& $problems === [] && \Nino\Features::rejected( $app ) === [] && ninoWarnings() === [] );

	// C. a second round
	$again = [];

	foreach( \Nino\Applications::features( [ $application ] ) as $feature )
		$again[$feature] = \Nino\Applications::place( $app, $catalogue, $feature );

	check( 'a second round places and switches on nothing: every feature is on already',
		array_filter( $again, static fn( array $result ): bool => $result !== [ 'placed' => false, 'activated' => false, 'reason' => '', 'status' => 200 ] ) === [] );
}

// --- D. The application Website through the steps of the wizard ---------------------------------------------------

require $root. '/_admin/install/Install.php';

// Website and Blog - the Blog is the Website and its posts - provide the start page, each run through the steps of the wizard
foreach( [ 'website', 'blog' ] as $appKey ) {

echo "\nThe application ". $appKey. ", to the end of the wizard\n";

$website = array_values( array_filter( $applications, static fn( array $entry ): bool => $entry['key'] === $appKey ) )[0] ?? [];
$order = $website['features'] ?? [];

check( 'The application provides its own start page and lists Website start right behind Basis, the feature whose components its pages are made of', ( $website['start'] ?? '' ) === 'own' && array_search( 'websitestart', $order, true ) === array_search( 'basis', $order, true ) + 1 );

$app = $project( 'applications-'. $appKey. '-wizard' );
$dir = ninoSandboxDir( $app );
$app['/nino/html/navs'] = \Nino\AppData::DEFAULTS['/nino/html/navs'];
$app['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Jstext', '\\Nino\\Modules\\Csrf' ];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
[ $setup ] = ninoCall( $app, [ \Nino\Install\Setup::class, 'apiApply' ], [ 'locales' => [ 'de_DE' ], 'native' => 'de_DE', 'modules' => [] ] );
[ $account ] = ninoCall( $app, [ \Nino\Install\Accounts::class, 'apiCreate' ], [ 'mail' => 'owner@example.com', 'pw' => 'a long enough password' ] );
$app[ \Nino\Install\Applications::KEY_OVERRIDE ] = $publicKey;

$step = static function( string $action, array $data ) use ( &$app ): array {
	return ninoCall( $app, [ \Nino\Install\Install::class, 'handlePost' ], $data, $action );
};

$step( 'applications/list', [] );
[ , $plan ] = $step( 'applications/plan', [ 'applications' => [ $appKey ] ] );
$answers = [];

foreach( (array) ( $plan['features'] ?? [] ) as $feature )
	$answers[$feature['key']] = $step( 'applications/apply', [ 'applications' => [ $appKey ], 'feature' => $feature['key'] ] )[0];

unset( $app['./nino/features/all'] );
[ $finished ] = ninoCall( $app, [ \Nino\Install\Finish::class, 'apiComplete' ], [ 'password' => 'a brand new dev password', 'applications' => [ $appKey ] ] );

check( 'Setup, the account, every feature of it in its order and the end of the wizard answer 200', $setup === 200 && $account === 200 && $finished === 200 && array_keys( $answers ) === $order && array_values( array_unique( $answers ) ) === [ 200 ] );

$config = \Nino\Filesystem::getFileContent( $app, '/config.php', [] );
$pageRoutes = array_keys( array_filter( (array) $config['/nino/http/routes'], static fn( mixed $route, string $key ): bool => str_starts_with( $key, 'GET://' ) === true && preg_match( '~^\[template /templates/page-(?!legal)~', (string) ( $route['body'] ?? '' ) ) === 1, ARRAY_FILTER_USE_BOTH ) );

check( 'the project has the three pages of Website start and no other, in components: the header and the footer are the unit\'s, no template of the starter site of the kernel is there',
	$pageRoutes === [ 'GET://', 'GET://contact', 'GET://404' ] && str_contains( (string) file_get_contents( $dir. '/private/templates/frame-header.tpl' ), '[header-wrap' ) === true && str_contains( (string) file_get_contents( $dir. '/private/templates/page-home.tpl' ), '[section-wrap' ) === true
	&& array_filter( glob( $dir. '/private/templates/*.tpl' ) ?: [], static fn( string $file ): bool => str_contains( (string) file_get_contents( $file ), 'site-header' ) === true || str_contains( (string) file_get_contents( $file ), 'class="hero' ) === true ) === [] );

// A visitor's request of the project: booted as a request boots it
$booted = [ './nino/uid' => $dir ];
\Nino\AppData::prepare( $booted );

foreach( [ 'path' => $dir, 'configpath' => $dir. '/private', 'contentpath' => $dir. '/private', 'publicpath' => $dir. '/public' ] as $part => $path )
	$booted['./nino/filesystem/'. $part] = $path;

\Nino\Filesystem::init( $booted );
\Nino\AppData::init( $booted );
$booted['/nino/dir'] = '';
\Nino\Locales::init( $booted );
\Nino\Csrf::init( $booted );
\Nino\Html::init( $booted );
\Nino\Auth::init( $booted );
\Nino\Features::registerAssets( $booted );
\Nino\Features::registerComponents( $booted );
\Nino\Modules::callModules( $booted, 'init' );
\Nino\Features::registerParts( $booted );
$booted['./nino/locales/current'] = 'de_DE';

$visit = static function( string $uri ) use ( $booted ): array {

	$copy = $booted;
	$request = \Nino\request( $copy, [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'example.com', 'HTTPS' => 'on' ] );

	return [ (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ), (string) ( $request['/nino/http/response']['body'] ?? '' ) ];
};

$drawn = [ 'start' => $visit( '/' ), 'contact' => $visit( '/contact' ), 'none' => $visit( '/not-here' ), 'imprint' => $visit( '/impressum' ) ];
$posts = $appKey === 'blog' ? $visit( '/blog' ) : [ 200, '<header class="header-wrap"><footer><h1>' ];
$legal = \Nino\Modules\Legal::check( $booted );

check( 'the pages are drawn from components, one header, one footer and one h1 each, with the header of Basis and its menu: Start 200, Contact 200, an address that is not there 404, the imprint 200 - and the log has nothing to say',
	array_column( $drawn, 0 ) === [ 200, 200, 404, 200 ] && array_filter( $drawn, static fn( array $page ): bool => substr_count( $page[1], '<header' ) !== 1 || substr_count( $page[1], '<footer' ) !== 1 || substr_count( $page[1], '<h1' ) !== 1
		|| str_contains( $page[1], 'class="header-wrap' ) === false || str_contains( $page[1], 'class="header-nav' ) === false ) === [] && ninoWarnings() === [] );
check( '...the page of the posts, where the application has them, is drawn in the same header and footer, and the legal check finds nothing to tell', $posts[0] === 200 && substr_count( $posts[1], '<header' ) === 1 && substr_count( $posts[1], '<footer' ) === 1 && $legal === [] && ninoWarnings() === [] );
check( 'the start page, the header and the footer are those of Website start, byte for byte, whatever the features that stand before it in the list brought: no feature of the application places a page ahead of the feature that is the start of the site',
	(string) file_get_contents( $dir. '/private/templates/page-home.tpl' ) === (string) file_get_contents( $repo. '/features/WebsiteStart/install/templates/page-home.tpl' )
	&& (string) file_get_contents( $dir. '/private/templates/page-contact.tpl' ) === (string) file_get_contents( $repo. '/features/WebsiteStart/install/templates/page-contact.tpl' )
	&& ( $config['/nino/http/routes']['GET://']['body'] ?? '' ) === '[template /templates/page-home]' && ( $config['/nino/http/routes']['GET://404']['statusCode'] ?? 0 ) === 404 );
check( 'the application is applied: every feature of it is on at the version of its directory', \Nino\Applications::isApplied( $app, $website ) === true );

}

$empty = [];
ninoDone( $empty );
