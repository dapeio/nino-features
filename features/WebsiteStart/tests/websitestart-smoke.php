<?php
declare(strict_types=1);

/**
 *	Nino features
 *	websitestart-smoke.php	Test of the feature Website start with real requests: a project is set up by the steps of the setup wizard - Setup::apiApply,
 *													the account, the application's features one after the other through the Applications step (\Nino\Applications::place(), against an
 *													index of this run's own, signed and served by the stub of the client, made of the folders of Basis and of this feature), and
 *													Finish::apiComplete - and the pages are drawn by requests.
 *
 *														A. an application that provides the start page (Basis and Website start): the pages of the unit are the project's, the wizard writes
 *														   no page of its own beside them, and the pages are drawn byte for byte as the Basis fixtures (fixtures/basis/starter/drawn/) hold
 *														   them - the only differences those of the way the project was set up, named in the test: the version of the bundles, and the list of
 *														   languages in the footer where the project has one language
 *														B. one language, German and English in one project each, and the words of a language the project does not have are not written
 *														C. switched on later, in a project that has the starter pages of the kernel: nothing is replaced, nothing is added twice, no menu
 *														   is rebuilt, the pages draw as they did - and where the project lacks a page, that page and no other is added
 *														D. switched off and removed: templates, words, routes and menus stay, and the pages draw as they did
 *
 *	Usage: php features/WebsiteStart/tests/websitestart-smoke.php
 *	       NINO_ROOT=../nino php features/WebsiteStart/tests/websitestart-smoke.php
 */

$root		= realpath( getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 ) ) ?: ( getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 ) );
$here		= dirname( __DIR__ );
$source	= dirname( $here );

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - set NINO_ROOT to one'. "\n" );
	exit( 2 );
}

if( is_file( $source. '/Basis/feature.php' ) === false || is_file( $source. '/Basis/tests/fixtures/basis/starter/drawn/home.de_DE.html' ) === false ) {
	fwrite( STDERR, 'No features/Basis with its tests beside this feature: the pages are made of its components and compared with its fixtures'. "\n" );
	exit( 2 );
}

// The features directory of this run: what the wizard places lands here and nowhere in the repository
$work = sys_get_temp_dir(). '/nino-websitestart-smoke-'. bin2hex( random_bytes( 6 ) );
mkdir( $work. '/project-features', 0700, true );
define( 'NINO_FEATURES_DIR', $work. '/project-features' );

require $root. '/tests/harness.php';
require $root. '/_admin/install/Install.php';

register_shutdown_function( static fn() => \Nino\Filesystem::removeDir( $work ) );

$fixtures = $source. '/Basis/tests/fixtures/basis/starter';
$unitDir	= $here. '/install';

// --- The index: Basis and this feature as the folders are, signed with a key of this run's own ----------------------

$keypair = openssl_pkey_new( [ 'private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1' ] );
openssl_pkey_export( $keypair, $privateKey );
$publicKey = openssl_pkey_get_details( $keypair )['key'];

/**
 *	A folder as a tar.gz: the one directory, the files in the order of their names, the tests of a feature and the marks of a run of check.sh left out
 *
 *	@param		string		$base					The folder
 *
 *	@return 	string
 */
function wsArchive( string $base ): string {

	$folder = basename( $base );
	$entries = [ $folder. '/' => null ];

	$walk = static function( string $dir, string $prefix ) use ( &$walk, &$entries, $base ): void {

		$names = array_diff( scandir( $dir ) ?: [], [ '.', '..' ] );
		sort( $names, SORT_STRING );

		foreach( $names as $name ) {

			if( ( $dir === $base && $name === 'tests' ) || $name === '.check-sh-mark' )
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
}

$remote = [];
$entries = [];

foreach( [ 'Basis', basename( $here ) ] as $folder ) {

	$manifest = (array) ( static fn( string $path ): mixed => include $path )( $source. '/'. $folder. '/feature.php' );
	$bytes = wsArchive( $source. '/'. $folder );
	$url = 'https://catalogue.test/features/'. $manifest['key']. '-'. $manifest['version']. '.tar.gz';
	$remote[$url] = $bytes;
	$entries[] = [ 'key' => $manifest['key'], 'name' => $manifest['name'], 'description' => $manifest['description'], 'category' => $manifest['category'], 'version' => $manifest['version'],
		'nino' => $manifest['nino'], 'php' => [ 'ext' => [] ], 'requires' => $manifest['requires'] ?? [], 'directory' => $folder, 'archive' => $url, 'sha256' => hash( 'sha256', $bytes ), 'size' => strlen( $bytes ), 'released' => '2026-11-13' ];
}

// The application of the run: what the application Website says of the start page and of these two features
$application = [ 'key' => 'start', 'name' => [ 'en_US' => 'Start', 'de_DE' => 'Start' ], 'description' => [ 'en_US' => 'A start', 'de_DE' => 'Ein Start' ], 'features' => [ 'basis', 'websitestart' ], 'next' => null, 'start' => 'own' ];
$json = ninoCatalogue( $entries, [ $application ] );
openssl_sign( $json, $signature, $privateKey, OPENSSL_ALGO_SHA256 );
$remote[\Nino\Catalogue::DEFAULT_URL] = $json;
$remote[\Nino\Catalogue::DEFAULT_URL. '.sig'] = base64_encode( $signature );

// --- A project, the wizard's steps, the pages drawn ----------------------------------------------------------------

/**
 *	A project the way the wizard has it after its second step: the features directory empty, the languages the project has, the setup applied
 *
 *	@param		array 		$locales			The languages of the project, the first one native
 *
 *	@return 	array										$appData
 */
$project = static function( array $locales ) use ( &$remote, $publicKey ): array {

	foreach( glob( NINO_FEATURES_DIR. '/*', GLOB_ONLYDIR ) ?: [] as $featureDir )
		\Nino\Filesystem::removeDir( $featureDir );

	$app = ninoSandbox( 'websitestart' );
	$dir = ninoSandboxDir( $app );
	$app['/nino/dir']								= '';
	$app['/nino/locales/available']	= $locales;
	$app['/nino/html/navs']					= \Nino\AppData::DEFAULTS['/nino/html/navs'];
	$app['/nino/modules']						= [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Elements', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Jstext', '\\Nino\\Modules\\Csrf' ];

	foreach( [ '/private/templates', '/public/images', '/private/text', '/private/assets' ] as $sub )
		mkdir( $dir. $sub, 0777, true );

	\Nino\Filesystem::putFileContent( $app, '/config.php', [ '/nino/locales/native' => $locales[0], '/nino/locales/available' => $locales ] );
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

	[ $status ] = ninoCall( $app, [ \Nino\Install\Setup::class, 'apiApply' ], [ 'locales' => $locales, 'native' => $locales[0], 'modules' => [] ] );

	if( $status !== 200 )
		throw new \RuntimeException( 'Setup::apiApply: '. $status );

	[ $status ] = ninoCall( $app, [ \Nino\Install\Accounts::class, 'apiCreate' ], [ 'mail' => 'owner@example.com', 'pw' => 'a long enough password' ] );

	if( $status !== 200 )
		throw new \RuntimeException( 'Accounts::apiCreate: '. $status );

	$app[ \Nino\Install\Applications::KEY_OVERRIDE ] = $publicKey;
	$app['./nino/fetch/stub'] = static function( string $url, array $options ) use ( &$remote ): array {
		return isset( $remote[$url] ) === false ? [ 'ok' => false, 'status' => 404, 'error' => 'http 404' ] : [ 'ok' => true, 'status' => 200, 'body' => $remote[$url] ];
	};

	return $app;
};

/**
 *	The two last steps of the wizard: the application's features one after the other, then the end. A request of its own each - the list of the features is read again
 *
 *	@param		array 		&$app
 *	@param		array 		$keys					The applications chosen; none for the way without one
 *
 *	@return 	array										[ status of the end, its answer, the answers of the features ]
 */
function wsWizard( array &$app, array $keys ): array {

	$call = static function( string $action, array $data ) use ( &$app ): array {
		return ninoCall( $app, [ \Nino\Install\Install::class, 'handlePost' ], $data, $action );
	};

	$placed = [];

	if( $keys !== [] ) {

		$call( 'applications/list', [] );
		[ , $plan ] = $call( 'applications/plan', [ 'applications' => $keys ] );

		foreach( (array) ( $plan['features'] ?? [] ) as $feature ) {
			[ $status, $body ] = $call( 'applications/apply', [ 'applications' => $keys, 'feature' => $feature['key'] ] );
			$placed[$feature['key']] = [ $status, $body ];
		}

		unset( $app['./nino/features/all'] );
	}

	[ $status, $body ] = ninoCall( $app, [ \Nino\Install\Finish::class, 'apiComplete' ], [ 'password' => 'a brand new dev password', 'applications' => $keys ] );

	return [ $status, (array) $body, $placed ];
}

/**
 *	What the test of Basis writes into its site besides the starter pages, so that the pages are drawn with the same company, the same page 'blank' at
 *	/new-webpage and the same menu positions: the data of the step "personal data" and one route
 *
 *	@param		array 		&$app
 *	@param		array 		$locales
 *
 *	@return 	void
 */
function wsTestData( array &$app, array $locales ): void {

	global $fixtures;

	$routes = \Nino\AppData::stored( $app, '/nino/http/routes', [] );
	$routes['GET://new-webpage'] = [ 'uri' => '/blank', 'body' => '[template /templates/page-blank]', 'navs' => [ 'main' => 4, 'footer' => 4 ] ];
	$app['/nino/http/routes'] = $routes;
	\Nino\AppData::writeContentData( $app, [ '/nino/http/routes' ] );
	\Nino\Features::mergeText( $app, '/text/global.php', [ '[[/_nino/webpage/blank/uri]]' => '/new-webpage' ] );

	$manifest = (array) include $fixtures. '/pages/blank/manifest.php';

	foreach( $locales as $locale ) {

		$meta = [];

		foreach( [ 'name', 'title', 'description' ] as $field )
			$meta['[[/_nino/webpage/blank/'. $field. ']]'] = \Nino\Text::sanitizeValue( \Nino\Features::localized( $manifest['suggest'][$field] ?? '', $locale ), 'plain' );

		\Nino\Features::mergeText( $app, '/text/'. $locale. '.php', $meta );
	}

	$company = [
		'[[/project/company/general/name]]'					=> 'Muster & Söhne GmbH',
		'[[/project/company/general/description]]'	=> 'Probe-Beschreibung für N107.',
		'[[/project/company/contact/email]]'				=> 'kontakt@example.com',
		'[[/project/company/contact/phone]]'				=> '+49 30 000000',
		'[[/project/company/contact/address]]'			=> "Musterstraße 1\n10115 Berlin",
		'[[/project/company/contact/country]]'			=> 'DE',
		'[[/project/website/general/url]]'					=> 'example.com',
		'[[/project/website/general/author]]'				=> 'N107',
	];

	foreach( $company as $key => $value )
		$company[$key] = \Nino\Text::sanitizeValue( $value, 'plain' );

	\Nino\Features::mergeText( $app, '/text/global.php', $company, true );
}

/**
 *	The project booted the way a request boots it: the site, its features and their components, the token of the form and the nonce of the script block
 *	fixed at their source and the year of the footer the clock's of the test
 *
 *	@param		string		$sandbox
 *	@param		string		$locale
 *
 *	@return 	array										$appData
 */
function wsBoot( string $sandbox, string $locale ): array {

	$_SESSION = [];

	$appData = [ './nino/uid' => $sandbox ];
	\Nino\AppData::prepare( $appData );

	$appData['./nino/filesystem/path']				= $sandbox;
	$appData['./nino/filesystem/configpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/contentpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/publicpath']	= $sandbox. '/public';
	$appData['./nino/test/sandbox']						= $sandbox;

	\Nino\Filesystem::init( $appData );
	\Nino\AppData::init( $appData );
	$appData['/nino/dir'] = '';
	\Nino\Locales::init( $appData );
	\Nino\Csrf::init( $appData );
	\Nino\Html::init( $appData );
	\Nino\Auth::init( $appData );

	\Nino\Features::registerAssets( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );

	$appData['./nino/locales/current'] = $locale;

	\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', static function( array &$appData, array &$request ): void {
		\Nino\Html::addFills( $appData, [ '/nino/date/year' => '2026' ], '*' );
	} );

	\Nino\Runtime::setSessionValue( $appData, './nino/csrf/token', str_repeat( 'c', 64 ) );
	$appData['./nino/jstext/nonce'] = str_repeat( 'a', 32 );

	return $appData;
}

/**
 *	A visitor's request of the project
 *
 *	@param		string		$sandbox
 *	@param		string		$locale
 *	@param		string		$uri
 *
 *	@return 	array										[ status, body ]
 */
function wsVisit( string $sandbox, string $locale, string $uri ): array {

	$appData = wsBoot( $sandbox, $locale );
	$request = \Nino\request( $appData, [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => 'example.com', 'HTTPS' => 'on' ] );

	return [ (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ), (string) ( $request['/nino/http/response']['body'] ?? '' ) ];
}

/**
 *	The pages the fixtures hold, as the project draws them: file name => body
 *
 *	@param		string		$sandbox
 *	@param		array 		$locales
 *
 *	@return 	array
 */
function wsPages( string $sandbox, array $locales ): array {

	$pages = [];

	foreach( [ 'home' => '/', 'contact' => '/contact', '404' => '/404', 'blank' => '/new-webpage' ] as $page => $uri )
		foreach( $locales as $locale )
			$pages[$page. '.'. $locale. '.html'] = wsVisit( $sandbox, $locale, $uri )[1];

	return $pages;
}

/**
 *	A page without the stamps that name the files of a bundle - the version of the style sheet and of the script, which is made of their paths, sizes and times, and the
 *	stamp of the favicons, the time of their files: how the project was set up is not what the page says
 *
 *	@param		string		$body
 *
 *	@return 	string
 */
function wsStamps( string $body ): string {
	return (string) preg_replace( '/\?v=[0-9a-f]+/', '?v=STAMP', $body );
}

/**
 *	The routes of a project as config.php keeps them
 *
 *	@param		array 		&$app
 *
 *	@return 	array
 */
function wsRoutes( array &$app ): array {
	return (array) ( \Nino\Filesystem::getFileContent( $app, '/config.php', [] )['/nino/http/routes'] ?? [] );
}

/**
 *	The routes that are pages: a GET route that renders a templates/page-*.tpl, the legal pages and the page of the test (wsTestData) not among them
 *
 *	@param		array 		$routes
 *
 *	@return 	array										The keys
 */
function wsPageRoutes( array $routes ): array {

	return array_keys( array_filter( $routes, static fn( mixed $route, string $key ): bool => str_starts_with( $key, 'GET://' ) === true && is_array( $route ) === true
		&& preg_match( '~^\[template /templates/page-~', (string) ( $route['body'] ?? '' ) ) === 1 && str_contains( (string) $route['body'], 'page-legal-' ) === false && $key !== 'GET://new-webpage', ARRAY_FILTER_USE_BOTH ) );
}

/**
 *	The bytes of every file of a directory: relative path => md5
 *
 *	@param		string		$base
 *
 *	@return 	array
 */
function wsTree( string $base ): array {

	$tree = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$path = substr( $file->getPathname(), strlen( $base ) );

		// What a request leaves behind - the kept trees of the templates, the bundles - is no file of the project
		if( $file->isFile() === true && str_starts_with( $path, '/private/data/' ) === false && str_starts_with( $path, '/public/.cache/' ) === false )
			$tree[$path] = md5_file( $file->getPathname() );
	}

	ksort( $tree );

	return $tree;
}

$locales = [ 'de_DE', 'en_US' ];

// --- A. The application provides the start page ------------------------------------------------------------------

echo "The application provides the start page, in two languages\n";

$app = $project( $locales );
$dir = ninoSandboxDir( $app );
[ $status, , $placed ] = wsWizard( $app, [ 'start' ] );

check( 'Basis and Website start are placed and switched on, one after the other, and the end of the wizard answers 200', $status === 200 && array_keys( $placed ) === [ 'basis', 'websitestart' ]
	&& array_filter( $placed, static fn( array $result ): bool => $result[0] !== 200 || ( $result[1]['placed'] ?? false ) !== true || ( $result[1]['activated'] ?? false ) !== true ) === [] );

$routes = wsRoutes( $app );
$unit = (array) include $unitDir. '/manifest.php';
$projectTemplates = array_map( static fn( string $name ): string => (string) file_get_contents( $dir. '/private/templates/'. $name ), $unit['templates'] );

check( 'the pages are the unit\'s: its three routes and no other page route, its five templates byte for byte - the wizard wrote no page of its own beside them, and no header or footer of the starter',
	wsPageRoutes( $routes ) === [ 'GET://', 'GET://contact', 'GET://404' ] && array_map( static fn( string $key ): array => array_diff_key( $routes[$key], [ 'navs' => 1 ] ), [ 'GET://', 'GET://contact', 'GET://404' ] ) === array_map( static fn( string $key ): array => array_diff_key( $unit['routes'][$key], [ 'navs' => 1 ] ), [ 'GET://', 'GET://contact', 'GET://404' ] )
	&& $projectTemplates === array_map( static fn( string $name ): string => (string) file_get_contents( $unitDir. '/templates/'. $name ), $unit['templates'] )
	&& str_contains( (string) file_get_contents( $dir. '/private/templates/frame-header.tpl' ), '[header-wrap' ) === true );

check( 'the unit ran before the end of the wizard and in the languages the project has: the 404 page it brings is the one that answers, and the words of its pages are in both languages\' files',
	( $routes['GET://404']['statusCode'] ?? 0 ) === 404 && isset( \Nino\Filesystem::getFileContent( $app, '/text/de_DE.php', [] )['[[/_nino/webpage/home/name]]'], \Nino\Filesystem::getFileContent( $app, '/text/en_US.php', [] )['[[/_nino/webpage/home/name]]'] ) === true );

check( 'the menus are the routes\': Start and Contact in the main menu and the footer menu, in this order, the 404 page in none - and the menus the project has are what they were',
	array_map( static fn( string $key ): array => $routes[$key]['navs'] ?? [], [ 'GET://', 'GET://contact', 'GET://404' ] ) === [ [ 'main' => 1, 'footer' => 1 ], [ 'main' => 2, 'footer' => 2 ], [] ]
	&& ( \Nino\Filesystem::getFileContent( $app, '/config.php', [] )['/nino/html/navs'] ?? [] ) === [ 'main', 'footer', 'legal' ] );

check( 'the picture place of the title area is there with its placeholder - the drawing of the fixtures - as the start page asks for it',
	( \Nino\Filesystem::getFileContent( $app, '/config.php', [] )['/nino/html/images']['/template/page-home/hero/background']['filename'] ?? '' ) === 'template/page-home/hero/background.svg'
	&& md5_file( $dir. '/public/images/template/page-home/hero/background.svg' ) === md5_file( $fixtures. '/pages/home/images/template/page-home/hero/background.svg' ) );
check( 'the addresses the templates link to by name are fills of the project in every language and are no words an editor translates: on the blacklist of the texts',
	( \Nino\Filesystem::getFileContent( $app, '/text/global.php', [] )['[[/_nino/webpage/contact/uri]]'] ?? '' ) === '/contact' && ( \Nino\Filesystem::getFileContent( $app, '/text/global.php', [] )['[[/_nino/webpage/home/uri]]'] ?? '' ) === '/'
	&& array_diff( [ '/_nino/webpage/home/uri', '/_nino/webpage/contact/uri', '/_nino/webpage/404/uri' ], (array) \Nino\Filesystem::getFileContent( $app, '/text/blacklist.php', [] ) ) === [] );

wsTestData( $app, $locales );
$drawn = wsPages( $dir, $locales );
$ignored = ninoWarnings();

// The pages of the fixtures are the pages of the project, byte for byte - no stamp of a bundle is compared, and nothing else is left out
$differing = [];

foreach( $drawn as $name => $body )
	if( wsStamps( $body ) !== wsStamps( (string) file_get_contents( $fixtures. '/drawn/'. $name ) ) )
		$differing[] = $name;

check( 'the starter pages in both languages are drawn byte for byte as the fixtures hold them, but for the stamps of the bundles'. ( $differing === [] ? '' : ' - '. implode( ', ', $differing ) ), $differing === [] && count( $drawn ) === 8 );

[ $statusHome, $home ] = wsVisit( $dir, 'de_DE', '/' );
[ $statusNone, $none ] = wsVisit( $dir, 'de_DE', '/gibt-es-nicht' );
check( 'one header, one footer, one h1 and the menus of the routes on the start page; a page that is not there is the page of the unit with the status 404',
	$statusHome === 200 && substr_count( $home, '<header' ) === 1 && substr_count( $home, '<footer' ) === 1 && substr_count( $home, '<h1' ) === 1 && substr_count( $home, 'class="header-nav-list"' ) === 1 && substr_count( $home, 'class="footer-nav"' ) === 1
	&& substr_count( $home, 'aria-current="page"' ) === 2 && $statusNone === 404 && str_contains( $none, 'Seite nicht gefunden' ) === true && substr_count( $none, '<header' ) === 1 );

$booted = wsBoot( $dir, 'de_DE' );
$findings = array_filter( \Nino\Modules\Legal::check( $booted ), static fn( array $finding ): bool => $finding['kind'] === 'nav' );
check( 'the imprint and the privacy policy are reached from the footer: the legal check has no finding of a page nobody links to', $findings === [] && wsVisit( $dir, 'de_DE', '/impressum' )[0] === 200 && wsVisit( $dir, 'en_US', '/privacy' )[0] === 200 );

// An entry in one menu only: the header draws the main menu and the footer the footer menu, and neither draws the other
$menus = $project( [ 'de_DE' ] );
$menuDir = ninoSandboxDir( $menus );
wsWizard( $menus, [ 'start' ] );
$menuRoutes = wsRoutes( $menus );
$menuRoutes['GET://nur-haupt'] = [ 'uri' => '/nur-haupt', 'body' => '[template /templates/page-blank]', 'navs' => [ 'main' => 7 ] ];
$menuRoutes['GET://nur-fuss'] = [ 'uri' => '/nur-fuss', 'body' => '[template /templates/page-blank]', 'navs' => [ 'footer' => 7 ] ];
$menus['/nino/http/routes'] = $menuRoutes;
\Nino\AppData::writeContentData( $menus, [ '/nino/http/routes' ] );
\Nino\Features::mergeText( $menus, '/text/de_DE.php', [ '[[/_nino/webpage/nur-haupt/name]]' => 'Nur Hauptmenue', '[[/_nino/webpage/nur-fuss/name]]' => 'Nur Fussmenue' ] );
$menuPage = wsVisit( $menuDir, 'de_DE', '/' )[1];
preg_match( '#<nav class="header-nav".*?</nav>#s', $menuPage, $headerNav );
preg_match( '#<nav class="footer-nav".*?</nav>#s', $menuPage, $footerNav );

check( 'the header draws the main menu and the footer the footer menu: an entry in the main menu only is in the header and not in the footer, an entry in the footer menu only is in the footer and not in the header',
	str_contains( $headerNav[0] ?? '', 'Nur Hauptmenue' ) === true && str_contains( $headerNav[0] ?? '', 'Nur Fussmenue' ) === false && str_contains( $footerNav[0] ?? '', 'Nur Fussmenue' ) === true && str_contains( $footerNav[0] ?? '', 'Nur Hauptmenue' ) === false );

// The bundle of the site: the standard parts of Basis and none of the rules of the starter site of the kernel, which an application that provides the start page does not have
$built = wsBoot( $menuDir, 'de_DE' );
$built['./nino/filesystem/path'] = $root;
\Nino\Modules\Assets::doShortcode( $built, [ '/.cache/style.css' ] );
$bundle = (string) @file_get_contents( \Nino\Filesystem::path( $built, '/.cache/style.css' ) );
check( 'the style bundle of a site whose start page the application provides holds the standard parts of Basis and no rule of the starter site of the kernel: no .site-header, .site-nav, .site-footer, .footer-cols, .hero or .brand',
	str_contains( $bundle, '.header-wrap' ) === true && array_filter( [ '.site-header', '.site-nav', '.site-footer', '.footer-cols', '.hero', '.brand' ], static fn( string $selector ): bool => str_contains( $bundle, $selector ) === true ) === [] );
check( 'the category is content, the first the rules of the catalogue give it: the feature brings content and touches no personal data of its own', ( \Nino\Features::manifest( $here ) ?? [] )['category'] === 'content' );

// --- B. One language ----------------------------------------------------------------------------------------------

echo "\nOne language\n";

foreach( [ 'de_DE', 'en_US' ] as $one ) {

	$app = $project( [ $one ] );
	$dir = ninoSandboxDir( $app );
	[ $status ] = wsWizard( $app, [ 'start' ] );
	wsTestData( $app, [ $one ] );
	$drawn = wsPages( $dir, [ $one ] );
	$ignored = ninoWarnings();
	$other = $one === 'de_DE' ? 'en_US' : 'de_DE';

	// What a project of one language does not have is the list of the languages in the footer: a footer-legal-locales without it
	$differing = [];

	foreach( $drawn as $name => $body ) {

		$expected = wsStamps( (string) file_get_contents( $fixtures. '/drawn/'. $name ) );
		$expected = (string) preg_replace( '#<ul class="footer-legal-locales">.*?</ul>#s', '', $expected );

		if( wsStamps( $body ) !== $expected )
			$differing[] = $name;
	}

	check( $one. ': the pages are drawn as the fixtures hold them, but for the stamps of the bundles and the list of languages, which a project with one language does not have'. ( $differing === [] ? '' : ' - '. implode( ', ', $differing ) ),
		$status === 200 && $differing === [] && count( $drawn ) === 4 && str_contains( $drawn['home.'. $one. '.html'], 'footer-legal-locales' ) === false );

	$text = \Nino\Filesystem::getFileContent( $app, '/text/'. $one. '.php', [] );
	check( $one. ': the words of the pages are written for this language, and no file is written for the other: '. $other, isset( $text['[[/template/page-home/hero/title]]'], $text['[[/_nino/webpage/404/description]]'] ) === true
		&& \Nino\Filesystem::fileExists( $app, '/text/'. $other. '.php' ) === false );
}

// --- C. Switched on later --------------------------------------------------------------------------------------------

echo "\nSwitched on later\n";

/**
 *	What a project is made of, apart from what an activation records of itself: every file below it but the kept trees and the bundles, and config.php without the list of the modules and the record of the features
 *
 *	@param		array 		&$app
 *
 *	@return 	array
 */
function wsProject( array &$app ): array {

	$dir = ninoSandboxDir( $app );
	$tree = wsTree( $dir );
	unset( $tree['/private/config.php'] );
	$config = \Nino\Filesystem::getFileContent( $app, '/config.php', [] );
	unset( $config['/nino/modules'], $config['/nino/features'] );

	return [ $tree, $config ];
}

/**
 *	Basis switched on as the Features panel does it, then a snapshot of the project, then Website start installed and switched on - so that what is compared is what the feature did and what Basis brings is not
 *
 *	@param		array 		&$app
 *	@param		string		$dir
 *	@param		array 		$uris				The addresses drawn before and after
 *
 *	@return 	array										[ result of the activation, its report, project before, project after, pages before, pages after ]
 */
function wsSwitchOn( array &$app, string $dir, array $uris ): array {

	\Nino\Applications::place( $app, \Nino\Catalogue::fetch( $app ), 'basis' );
	unset( $app['./nino/features/all'] );
	$before = wsProject( $app );
	$visitsBefore = [];

	foreach( $uris as $uri )
		$visitsBefore[$uri] = wsVisit( $dir, 'de_DE', $uri );

	\Nino\Catalogue::install( $app, \Nino\Packages::FEATURE, 'websitestart', '1.0.0' );
	$report = null;
	$result = \Nino\Features::activate( $app, 'websitestart', [], $report );
	unset( $app['./nino/features/all'] );
	$visitsAfter = [];

	foreach( $uris as $uri )
		$visitsAfter[$uri] = wsVisit( $dir, 'de_DE', $uri );

	return [ $result, $report, $before, wsProject( $app ), array_map( 'wsStamps', array_column( $visitsBefore, 1 ) ), array_map( 'wsStamps', array_column( $visitsAfter, 1 ) ), array_column( $visitsBefore, 0 ) === array_column( $visitsAfter, 0 ) ];
}

// The project has the starter pages of the kernel
$app = $project( $locales );
$dir = ninoSandboxDir( $app );
wsWizard( $app, [] );
wsTestData( $app, $locales );
$htmlHome = (string) file_get_contents( $dir. '/private/templates/page-home.tpl' );
[ $result, $report, $projectBefore, $projectAfter, $pagesBefore, $pagesAfter, $sameStatus ] = wsSwitchOn( $app, $dir, [ '/', '/contact', '/404', '/impressum' ] );
$ignored = ninoWarnings();

check( 'the project has the starter pages of the kernel: html, no component of Basis in them', str_contains( $htmlHome, 'section-wrap' ) === false && wsPageRoutes( wsRoutes( $app ) ) === [ 'GET://', 'GET://contact', 'GET://404' ] );
check( 'Website start is switched on in a project that has pages, and the unit places nothing: no route, template, word, picture place or file - the project and the pages it draws are what they were, and the report says it was left out',
	$result === true && $projectBefore === $projectAfter && $pagesBefore === $pagesAfter && $sameStatus === true && ( $report['skipped'] ?? '' ) === 'pages' && array_sum( array_column( array_filter( (array) $report, 'is_array' ), 'added' ) ) === 0
	&& in_array( '\\Nino\\Modules\\WebsiteStart', (array) ( \Nino\Filesystem::getFileContent( $app, '/config.php', [] )['/nino/modules'] ?? [] ), true ) === true );

// A project with pages of its own under other keys, a menu of its own and no header and no footer
$app = $project( [ 'de_DE' ] );
$dir = ninoSandboxDir( $app );
$routes = \Nino\AppData::stored( $app, '/nino/http/routes', [] );
$routes['GET://'] = [ 'uri' => '/start', 'body' => '[template /templates/page-start]', 'navs' => [ 'main' => 1 ] ];
$routes['GET://kontakt'] = [ 'uri' => '/kontakt', 'body' => '[template /templates/page-kontakt]', 'navs' => [ 'main' => 2 ] ];
$routes['GET://ueber'] = [ 'uri' => '/ueber', 'body' => '[template /templates/page-ueber]', 'navs' => [ 'main' => 3 ] ];
$app['/nino/http/routes'] = $routes;
\Nino\AppData::writeContentData( $app, [ '/nino/http/routes' ] );

foreach( [ 'start' => 'Meine Startseite', 'kontakt' => 'Mein Kontakt', 'ueber' => 'Über uns' ] as $name => $title ) {
	file_put_contents( $dir. '/private/templates/page-'. $name. '.tpl', '[template /templates/html-header]<main><h1>'. $title. '</h1></main>[template /templates/html-footer]' );
	\Nino\Features::mergeText( $app, '/text/de_DE.php', [ '[[/_nino/webpage/'. $name. '/name]]' => $title, '[[/_nino/webpage/'. $name. '/title]]' => $title, '[[/_nino/webpage/'. $name. '/description]]' => $title ] );
	\Nino\Features::mergeText( $app, '/text/global.php', [ '[[/_nino/webpage/'. $name. '/uri]]' => $name === 'start' ? '/' : '/'. $name ] );
}

wsWizard( $app, [] );
$ownBefore = wsRoutes( $app );
[ $result, $report, $projectBefore, $projectAfter, $pagesBefore, $pagesAfter ] = wsSwitchOn( $app, $dir, [ '/', '/kontakt', '/ueber' ] );
$ignored = ninoWarnings();

check( 'a project with pages of its own under other keys and a menu of its own is left as it is: the routes, the menus and their order, the templates, the words, the picture places and the pages drawn are what they were - no second contact page in the menu, no header over the pages',
	$result === true && $projectBefore === $projectAfter && wsRoutes( $app ) === $ownBefore && $pagesBefore === $pagesAfter && ( $report['skipped'] ?? '' ) === 'pages'
	&& is_file( $dir. '/private/templates/frame-header.tpl' ) === false && substr_count( implode( '', $pagesAfter ), '<header' ) === 0 );

// The project had a start of a site, and the page it deleted does not come back while it has others
$app = $project( $locales );
$dir = ninoSandboxDir( $app );
wsWizard( $app, [ 'start' ] );
\Nino\Filesystem::mutate( $app, '/config.php', static function( array $content ): array {
	unset( $content['/nino/http/routes']['GET://contact'] );
	return $content;
} );
$app['/nino/http/routes'] = wsRoutes( $app );
\Nino\Features::activate( $app, 'websitestart', [], $report, true );
\Nino\Features::deactivate( $app, 'websitestart' );
\Nino\Features::activate( $app, 'websitestart' );
unset( $app['./nino/features/all'] );
[ $statusPanel, $bodyPanel ] = array_slice( ninoCall( $app, [ \Nino\Modules\Features\Admin::class, 'apiReapply' ], [ 'key' => 'websitestart' ] ), 0, 2 );

check( 'a project that has deleted its contact page keeps it deleted - switched on a second time, off and on, and applied from the panel - and the panel says that nothing was placed because the project has pages',
	isset( wsRoutes( $app )['GET://contact'] ) === false && isset( wsRoutes( $app )['GET://'] ) === true && ( $report['skipped'] ?? '' ) === 'pages' && ( $bodyPanel['applied']['skipped'] ?? '' ) === 'pages' && $statusPanel === 200 );

// A project without a page: the whole start of a site, as the wizard writes it
$app = $project( $locales );
$dir = ninoSandboxDir( $app );
$none = wsRoutes( $app );
ninoCall( $app, [ \Nino\Install\Install::class, 'handlePost' ], [], 'applications/list' );
\Nino\Applications::place( $app, \Nino\Catalogue::fetch( $app ), 'basis' );
unset( $app['./nino/features/all'] );
\Nino\Catalogue::install( $app, \Nino\Packages::FEATURE, 'websitestart', '1.0.0' );
$report = null;
$result = \Nino\Features::activate( $app, 'websitestart', [], $report );
unset( $app['./nino/features/all'] );
[ $statusHome, $home ] = wsVisit( $dir, 'de_DE', '/' );

check( 'a project without a page, which has switched the feature on in the Features panel, has the whole start of a site: its three routes, its five templates, the picture place, and the start page is drawn from components under one header',
	$result === true && wsPageRoutes( wsRoutes( $app ) ) === [ 'GET://', 'GET://contact', 'GET://404' ] && wsPageRoutes( $none ) === [] && ( $report['skipped'] ?? '' ) === '' && $report['routes']['added'] === 3 && $report['templates']['added'] === 5
	&& isset( \Nino\Filesystem::getFileContent( $app, '/config.php', [] )['/nino/html/images']['/template/page-home/hero/background'] ) === true && $statusHome === 200 && substr_count( $home, '<header' ) === 1 && substr_count( $home, 'class="header-nav-list"' ) === 1 );

// A project that has only a 404 page - what an application that provides a start page and brings none leaves behind - has not begun a site: the whole start is placed, the 404 page it has stays the project's
$app = $project( $locales );
$dir = ninoSandboxDir( $app );
file_put_contents( $dir. '/private/templates/page-404.tpl', '<main><h1>Mein 404</h1></main>' );
\Nino\Filesystem::mutate( $app, '/config.php', static function( array $content ): array {
	$content['/nino/http/routes']['GET://404'] = [ 'uri' => '/404', 'body' => '[template /templates/page-404]', 'statusCode' => 404 ];
	return $content;
} );
$app['/nino/http/routes'] = wsRoutes( $app );
ninoCall( $app, [ \Nino\Install\Install::class, 'handlePost' ], [], 'applications/list' );
\Nino\Applications::place( $app, \Nino\Catalogue::fetch( $app ), 'basis' );
unset( $app['./nino/features/all'] );
\Nino\Catalogue::install( $app, \Nino\Packages::FEATURE, 'websitestart', '1.0.0' );
$report = null;
$result = \Nino\Features::activate( $app, 'websitestart', [], $report );
unset( $app['./nino/features/all'] );
$routesOnly404 = wsRoutes( $app );
[ $statusStart, $start ] = wsVisit( $dir, 'de_DE', '/' );

check( 'a project that has only a 404 page gets the whole start of a site in the Features panel - the start page and the contact page, the header and the footer - and its own 404 page, route and template, stays as it is',
	$result === true && ( $report['skipped'] ?? '' ) === '' && isset( $routesOnly404['GET://'], $routesOnly404['GET://contact'] ) === true && ( $routesOnly404['GET://404']['body'] ?? '' ) === '[template /templates/page-404]'
	&& (string) file_get_contents( $dir. '/private/templates/page-404.tpl' ) === '<main><h1>Mein 404</h1></main>' && $statusStart === 200 && substr_count( $start, '<header' ) === 1 && substr_count( $start, '<footer' ) === 1 );

// --- D. Switched off and removed --------------------------------------------------------------------------------------

echo "\nSwitched off and removed\n";

$app = $project( $locales );
$dir = ninoSandboxDir( $app );
wsWizard( $app, [ 'start' ] );
wsTestData( $app, $locales );
$visitsOn = [];

foreach( $locales as $locale )
	foreach( [ '/', '/contact', '/404' ] as $uri )
		$visitsOn[$locale. ' '. $uri] = wsVisit( $dir, $locale, $uri );

$treeOn = wsTree( $dir );
$refused = \Nino\Features::deactivate( $app, 'basis' );
$off = \Nino\Features::deactivate( $app, 'websitestart' );
$modules = (array) ( \Nino\Filesystem::getFileContent( $app, '/config.php', [] )['/nino/modules'] ?? [] );
$redrawn = [];

foreach( $visitsOn as $label => [ $statusOn, $bodyOn ] ) {

	[ $locale, $uri ] = explode( ' ', $label );
	[ $statusOff, $bodyOff ] = wsVisit( $dir, $locale, $uri );

	if( $statusOff !== $statusOn || wsStamps( $bodyOff ) !== wsStamps( $bodyOn ) )
		$redrawn[] = $label;
}

$treeOff = wsTree( $dir );
check( 'Basis is not switched off while Website start is on, and Website start is: its class leaves the list of modules and nothing else changes - not a template, not a word, not a route, not a picture', is_string( $refused ) === true
	&& $off === true && in_array( '\\Nino\\Modules\\WebsiteStart', $modules, true ) === false && in_array( '\\Nino\\Modules\\Basis', $modules, true ) === true
	&& array_diff_key( $treeOn, $treeOff, [ '/private/config.php' => 1 ] ) === [] && array_diff_key( $treeOff, $treeOn ) === [] && array_diff( $treeOn, $treeOff ) === [ '/private/config.php' => $treeOn['/private/config.php'] ] );
check( 'the pages are drawn as they were, in both languages, with the feature off'. ( $redrawn === [] ? '' : ' - '. implode( ', ', $redrawn ) ), $redrawn === [] );

$removed = \Nino\Features::remove( $app, 'websitestart' );
$routesRemoved = wsRoutes( $app );
$redrawn = [];

foreach( $visitsOn as $label => [ $statusOn, $bodyOn ] ) {

	[ $locale, $uri ] = explode( ' ', $label );
	[ $statusGone, $bodyGone ] = wsVisit( $dir, $locale, $uri );

	if( $statusGone !== $statusOn || wsStamps( $bodyGone ) !== wsStamps( $bodyOn ) )
		$redrawn[] = $label;
}

check( 'removed, its folder is gone and the pages stay the project\'s: templates, words, routes with their menus and the picture place are still there, and the pages are drawn as they were',
	$removed === true && is_dir( NINO_FEATURES_DIR. '/WebsiteStart' ) === false && wsPageRoutes( $routesRemoved ) === [ 'GET://', 'GET://contact', 'GET://404' ] && ( $routesRemoved['GET://contact']['navs'] ?? [] ) === [ 'main' => 2, 'footer' => 2 ]
	&& wsTree( $dir ) === $treeOff && $redrawn === [] );

$empty = [];
ninoDone( $empty );
