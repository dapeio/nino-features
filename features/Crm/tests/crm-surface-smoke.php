<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-surface-smoke.php		The test of the server of the surface of the feature Crm, the page /crm and what stands around it: every request goes the way a request of the site goes - the route, the callbacks of the
 *													response, the session, the permission - and ends in the method that answers it; the endpoint is posted to with the token the page carries. The page for nobody (the login), for an account
 *													without the permission (403) and for one with it, its headers (the policy, never cached, never framed) and the data block it carries, the files it serves by name (only the ones that were
 *													declared; no path, no null byte, no length), the devices that stay signed in (the token as a hash, its replacement at every visit and the grace of the one it replaced, the end of the days,
 *													the password the device was made under, the tries of an address, the three actions and what they do without a session, without the permission and without the token) and a database
 *													that cannot be read (the page says so with 503 and the devices are never "none"), the language of the page, the manifest and the icon, and the places a package docks to - its words, its
 *													script and style sheet, an entry of the rail, the data of the page, a page the devices come back to, an app with its icon - on, off and removed. The signatures a package may rely on are
 *													pinned. One pin for each behaviour, over the structure and never the words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Crm/tests/crm-surface-smoke.php
 *	       NINO_ROOT=../nino php features/Crm/tests/crm-surface-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$source = dirname( __DIR__ );

/**
 *	Copy a folder with what is in it
 *
 *	@param		string		$from
 *	@param		string		$to
 *
 *	@return 	void
 */
function srfCopy( string $from, string $to ): void {

	mkdir( $to, 0755, true );

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $from, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST ) as $item ) {

		$target = $to. '/'. substr( (string) $item->getPathname(), strlen( $from ) + 1 );

		if( $item->isDir() === true )
			mkdir( $target, 0755, true );
		else
			copy( (string) $item->getPathname(), $target );
	}
}

/**
 *	Take a folder of the suite away with what is in it
 *
 *	@param		string		$folder
 *
 *	@return 	void
 */
function srfRemove( string $folder ): void {

	if( is_dir( $folder ) === false && is_link( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

/*	The features directory of the suite: a folder of its own, made for the run, with a copy of the feature and the package of the suite (tests/fixtures/CrmSurfaceProbe) - never the folder of the catalogue.
	A script of the core that is not written yet is a file of the suite in the copy, so that what the server does with the files it serves is seen either way	*/
$worker = ( $argv[1] ?? '' ) === 'removed-worker';

if( $worker === true )
	$features = (string) $argv[2];
else {
	$features = sys_get_temp_dir(). '/nino-crm-surface-features-'. bin2hex( random_bytes( 6 ) );
	mkdir( $features. '/Crm', 0755, true );

	foreach( [ 'Crm.php', 'feature.php' ] as $file )
		copy( $source. '/'. $file, $features. '/Crm/'. $file );

	foreach( array_diff( (array) scandir( $source ), [ '.', '..', 'tests' ] ) as $folder )
		if( is_dir( $source. '/'. $folder ) === true )
			srfCopy( $source. '/'. $folder, $features. '/Crm/'. $folder );

	srfCopy( $source. '/tests/fixtures/CrmSurfaceProbe', $features. '/CrmSurfaceProbe' );
	srfCopy( $source. '/tests/fixtures/CrmSurfaceProbeTwo', $features. '/CrmSurfaceProbeTwo' );

	foreach( [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js', 'crm.css', 'login.js' ] as $file )
		if( is_file( $features. '/Crm/Surface/assets/'. $file ) === false ) {
			is_dir( $features. '/Crm/Surface/assets' ) === true || mkdir( $features. '/Crm/Surface/assets', 0755, true );
			file_put_contents( $features. '/Crm/Surface/assets/'. $file, '/* a file of the suite */'. "\n" );
		}
}

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-surface-smoke.php names its own features directory: NINO_FEATURES_DIR is defined already\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

/**
 *	The app data of a request of a project that exists: the directory, the languages, the modules the project lists, and what the modules register in their init() - with the session and the cookies the caller names
 *
 *	@param		string		$sandbox							The directory of the project
 *	@param		array 		|null	$session			What the session holds: null for none (a visitor)
 *	@param		array 		$cookies							The cookies of the request
 *
 *	@return 	array
 */
function srfRequest( string $sandbox, ?array $session = null, array $cookies = [] ): array {

	$_SESSION = $session ?? [];
	$_COOKIE = $cookies;

	$appData = [ './nino/uid' => $sandbox ];
	\Nino\AppData::prepare( $appData );
	$appData['./nino/filesystem/path']				= $sandbox;
	$appData['./nino/filesystem/configpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/contentpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/publicpath']	= $sandbox. '/public';
	$appData['./nino/locales/current']				= 'de_DE';
	$appData['/nino/locales/native']					= $GLOBALS['srfNative'] ?? 'de_DE';
	$appData['/nino/locales/available']				= array_values( array_unique( [ $GLOBALS['srfNative'] ?? 'de_DE', 'de_DE', 'en_US' ] ) );
	$appData['/nino/dir']											= '';
	$config																		= (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
	$appData['/nino/modules']									= (array) ( $config['/nino/modules'] ?? [] );
	$appData['/nino/auth/user']								= (array) ( $config['/nino/auth/user'] ?? [] );
	$appData['./nino/test/sandbox']						= $sandbox;
	$appData																	= ( $GLOBALS['srfSet'] ?? [] ) + $appData;

	\Nino\Html::init( $appData );
	\Nino\Csrf::init( $appData );
	\Nino\Auth::init( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	// Fills the suite makes up, to see whether a page of the surface is read for them once more: [[/probe/inject]] holds markup that would end the data block and make an element
	if( isset( $GLOBALS['srfFills'] ) === true )
		\Nino\Html::addFills( $appData, (array) $GLOBALS['srfFills'], '*' );

	if( isset( $GLOBALS['srfName'] ) === true )
		\Nino\Html::addFills( $appData, [ '[[/project/company/general/name]]' => $GLOBALS['srfName'] ], '*' );

	return $appData;
}

const SRF_ACCOUNTS = [ 'admin@example.org' => [ 'correct horse battery staple', [ '/*' ] ], 'plain@example.org' => [ 'plain password', [] ], 'crm@example.org' => [ 'crm password', [ '/_admin/crm/manage' ] ],
	'other@example.org' => [ 'other password', [ '/_admin/crm/manage' ] ], 'text@example.org' => [ 'text password', [ '/_admin/text/manage' ] ] ];

/**
 *	A session of an account that is signed in now: the one a session that was ended or revoked is no more
 *
 *	@param		string		$where
 *	@param		string		$mail
 *	@param		string		$password			Another one where the password was changed
 *
 *	@return 	array
 */
function srfSession( string $where, string $mail, string $password = '' ): array {

	$a = srfRequest( $where );
	\Nino\Auth::loginUser( $a, $mail, $password !== '' ? $password : SRF_ACCOUNTS[$mail][0] );

	return $_SESSION;
}

/**
 *	A project with the core switched on - and the package of the suite, as the world says - and the accounts of the suite
 *
 *	@param		string		$name
 *	@param		string		$probe					'on' (switched on), 'off' (never switched on) or 'removed' (switched on and its folder gone)
 *
 *	@return 	array											[ the directory of the project, the sessions of the accounts by their address ]
 */
function srfWorld( string $name, string $probe = 'off' ): array {

	$project = ninoSandbox( $name );
	$GLOBALS['srfSandboxes'][] = ninoSandboxDir( $project );
	\Nino\Features::activate( $project, 'crm' );

	if( $probe !== 'off' ) {
		\Nino\Features::activate( $project, 'crm-surface-probe' );
		$probe === 'on' && \Nino\Features::activate( $project, 'crm-surface-probe-two' );
	}

	$where = ninoSandboxDir( $project );
	$a = srfRequest( $where );
	$passwords = SRF_ACCOUNTS;
	$sessions = [];

	foreach( $passwords as $mail => [ $password, $perms ] )
		\Nino\Auth::insertUser( $a, $mail, $password, $perms );

	foreach( $passwords as $mail => [ $password ] ) {
		$b = srfRequest( $where );
		\Nino\Auth::loginUser( $b, $mail, $password );
		$sessions[$mail] = $_SESSION;
	}

	ninoWarnings();

	return [ $where, $sessions ];
}

/**
 *	A call of a place of the surface the way a package makes it: from the folder of a package, which is what the places ask for
 *
 *	@param		string		$class
 *	@param		string		$method
 *	@param		array 		&$appData
 *	@param		mixed			...$arguments
 *
 *	@return 	mixed
 */
function srfVia( string $class, string $method, array &$appData, mixed ...$arguments ): mixed {

	global $features;
	require_once $features. '/CrmSurfaceProbe/CrmSurfaceProbe.php';

	return \Nino\Modules\CrmSurfaceProbe::via( $class, $method, $appData, ...$arguments );
}

/**
 *	A writer in a process of its own that holds the database locked: it begins an exclusive transaction, says it holds, and rolls back when it is released. A lock held by another process is only seen from a process of its own
 *
 *	@param		string		$file
 *
 *	@return 	array											The process and its pipes
 */
function srfHold( string $file ): array {

	$program = '$in = fopen( "php://stdin", "r" ); $db = new PDO( "sqlite:". $argv[1], null, null, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] ); $db->exec( "PRAGMA busy_timeout = 0" ); $db->exec( "BEGIN EXCLUSIVE" ); echo "held\n"; fflush( STDOUT ); fgets( $in ); $db->exec( "ROLLBACK" );';
	$process = proc_open( [ PHP_BINARY, '-r', $program, $file ], [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ] ], $pipes );
	fgets( $pipes[1] );

	return [ $process, $pipes ];
}

/**
 *	Let the writer go
 *
 *	@param		array 		$held					What srfHold() answered
 *
 *	@return 	void
 */
function srfRelease( array $held ): void {

	@fwrite( $held[1][0], "\n" );
	fclose( $held[1][0] );
	fclose( $held[1][1] );
	proc_close( $held[0] );
}

/**
 *	One GET of the site, the way the server hands it over, with the callbacks of the response run
 *
 *	@param		string		$where
 *	@param		string		$uri
 *	@param		array 		|null	$session
 *	@param		array 		$cookies
 *	@param		array 		$server				More of the server array: REMOTE_ADDR, HTTP_HOST, HTTPS
 *	@param		string		$method
 *
 *	@return 	array											[ status, body, headers, the app data of the request, the request ]
 */
function srfGet( string $where, string $uri, ?array $session = null, array $cookies = [], array $server = [], string $method = 'GET' ): array {

	$a = srfRequest( $where, $session, $cookies );
	$server += [ 'REMOTE_ADDR' => '127.0.0.1' ];
	$kept = $_SERVER;
	$_SERVER = $server + $_SERVER;

	try {
		// The whole way a request takes in the front controller (index.php): the route, the callbacks, and the reading of the body by Html::response(), which is what the browser is given
		$request = \Nino\request( $a, array_merge( [ 'REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1' ], $server ) );
	}
	finally {
		$_SERVER = $kept;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request['/nino/http/response']['header'] ?? [], $a, $request ];
}

/**
 *	One post to the endpoint, the way the page makes it: the request of the site, the route, the guard of the token, the callbacks of the route
 *
 *	@param		string		$where
 *	@param		array 		|null	$session
 *	@param		array 		$cookies
 *	@param		string		$action
 *	@param		array 		$o						token (null: the token of the session; '' none; or a text), ip, https (bool), body, type, server (more of the server array)
 *
 *	@return 	array											[ status, body of the answer, the app data of the request, the request ]
 */
function srfPost( string $where, ?array $session, array $cookies, string $action, array $o = [] ): array {

	$a = srfRequest( $where, $session, $cookies );
	$token = array_key_exists( 'token', $o ) === true ? (string) $o['token'] : \Nino\Csrf::getToken( $a );
	$server = [ 'REMOTE_ADDR' => $o['ip'] ?? '127.0.0.1', 'CONTENT_TYPE' => $o['type'] ?? 'application/json' ] + (array) ( $o['server'] ?? [] );

	if( $token !== '' )
		$server['HTTP_X_CSRF_TOKEN'] = $token;

	$kept = $_SERVER;
	$_SERVER = $server + $_SERVER;

	if( ( $o['https'] ?? false ) === true )
		$_SERVER['HTTPS'] = 'on';

	$posted = $_POST;
	$_POST = [];

	try {
		$request = ninoVisit( $a, '/crm/api', 'POST', $server );
		$request['/nino/http/request']['body'] = array_key_exists( 'body', $o ) === true ? $o['body'] : json_encode( [ 'action' => $action, 'data' => $o['data'] ?? [] ] );

		\Nino\Http::response( $a, $request );
	}
	finally {
		$_POST = $posted;
		$_SERVER = $kept;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $a, $request ];
}

/**
 *	The cookie of a device a request set: the last line of the cookies the surface kept for the request
 *
 *	@param		array 		$a						The app data of the request
 *
 *	@return 	array											[ the value, the whole line ]; [ '', '' ] where none was set
 */
function srfCookie( array $a ): array {

	$lines = (array) ( $a['./crm/cookies'] ?? [] );
	$line = $lines === [] ? '' : (string) end( $lines );

	return [ preg_match( '/^crm_remember=([^;]*);/', $line, $found ) === 1 ? $found[1] : '', $line ];
}

/**
 *	The JSON block of a page, as the script reads it
 *
 *	@param		mixed			$body
 *
 *	@return 	array											[] where the page has none
 */
function srfData( mixed $body ): array {

	return preg_match( '~<script type="application/json" id="crm-data">(.*?)</script>~s', (string) $body, $found ) === 1 ? (array) json_decode( $found[1], true ) : [];
}

/**
 *	The rows of a table of the database of the sandbox
 *
 *	@param		string		$where
 *	@param		string		$sql
 *	@param		array 		$params
 *
 *	@return 	array
 */
function srfRows( string $where, string $sql, array $params = [] ): array {

	$a = srfRequest( $where );
	$rows = \Nino\Modules\Crm\Db::rows( $a, $sql, $params );
	\Nino\Modules\Crm\Db::close( $a );

	return $rows;
}

/**
 *	One statement on the database of the sandbox
 *
 *	@param		string		$where
 *	@param		string		$sql
 *	@param		array 		$params
 *
 *	@return 	void
 */
function srfExec( string $where, string $sql, array $params = [] ): void {

	$a = srfRequest( $where );
	\Nino\Modules\Crm\Db::exec( $a, $sql, $params );
	\Nino\Modules\Crm\Db::close( $a );
}

/**
 *	Whether a page is the login, the page that says there is no permission, the page of the surface or the page that says the data cannot be read
 *
 *	@param		int				$status
 *	@param		mixed			$body
 *
 *	@return 	string										login, forbidden, down, page, app or other
 */
function srfKind( int $status, mixed $body ): string {

	$body = (string) $body;

	if( $status === 403 )
		return 'forbidden';

	if( $status === 503 )
		return 'down';

	if( $status !== 200 )
		return 'other';

	return str_contains( $body, 'id="login"' ) === true ? 'login' : ( str_contains( $body, 'class="app solo"' ) === true ? 'app' : ( str_contains( $body, 'class="rail"' ) === true ? 'page' : 'other' ) );
}

/**
 *	A section that stopped with an exception is one pin that fell, said with the section and the kind of the exception, and the run goes on with the next
 *
 *	@param		string		$section
 *	@param		\Throwable	$error
 *
 *	@return 	void
 */
function srfCrashed( string $section, \Throwable $error ): void {

	if( $error instanceof \Nino\Failure )
		throw $error;

	check( 'the section "'. $section. '" ran to its end: it stopped with '. get_class( $error ). ' at line '. $error->getLine(), false );
	fwrite( STDERR, $error->getMessage(). "\n" );
}

/*	Worker mode: this same file re-entered as a process of its own, pointed at a sandbox somebody else built and a features directory that does not hold the package of the suite any more - the
	one thing a process of the suite cannot do for itself, since a class that was loaded stays loaded	*/
if( $worker === true ) {

	$session = (array) json_decode( (string) file_get_contents( (string) $argv[4] ), true );
	[ $status, $body, , $a ] = srfGet( (string) $argv[3], '/crm', $session );
	$data = srfData( $body );
	[ $manifestStatus ] = srfGet( (string) $argv[3], '/crm/probe', $session );
	[ $appStatus ] = srfGet( (string) $argv[3], '/crm/probeapp', $session );
	echo json_encode( [ 'status' => $status, 'kind' => srfKind( $status, $body ), 'menu' => $data['menu'] ?? null, 'packages' => $data['packages'] ?? null, 'scripts' => preg_match_all( '~<(?:script src|link rel="stylesheet" href)="[^"]*"~', (string) $body ), 'probe' => $manifestStatus, 'app' => $appStatus,
		'warnings' => ninoWarnings(), 'allowed' => \Nino\Modules\Crm\Surface\Devices::pages( $a ), 'apps' => array_keys( \Nino\Modules\Crm\Surface\Apps::all( $a ) ) ] );
	exit( 0 );
}

$GLOBALS['srfSandboxes'] = [];

/*	The routes and the files the surface declares	*/
try {

	[ $where, $S ] = srfWorld( 'surface' );
	$crm = $S['crm@example.org'];
	$none = [ 'admin@example.org' => $S['admin@example.org'], 'plain@example.org' => $S['plain@example.org'] ];
	$a = srfRequest( $where );

	check( 'the route of the surface is /crm and the files it serves by name are the seven it declares: the style sheet, the login and the four scripts of the core, in the order they load', \Nino\Modules\Crm\Surface::ROUTE === '/crm'
		&& array_keys( \Nino\Modules\Crm\Surface::ASSETS ) === [ 'crm.css', 'login.js', 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ] && \Nino\Modules\Crm\Surface::SCRIPTS === [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ] );
	check( 'every file the surface declares lies in Surface/assets under its name, its type is the one of its ending, and the style sheet and the login script are there (the scripts of the core are a file of the suite where they are not written)', ( static function() use ( $source ): bool {

		foreach( \Nino\Modules\Crm\Surface::ASSETS as $name => [ $path, $type ] ) {

			if( $path !== 'Surface/assets/'. $name || str_ends_with( $name, '.css' ) !== str_starts_with( $type, 'text/css' ) || str_ends_with( $name, '.js' ) !== str_starts_with( $type, 'text/javascript' ) )
				return false;

			if( in_array( $name, [ 'crm.css', 'login.js' ], true ) === true && is_file( $source. '/'. $path ) === false )
				return false;
		}

		return true;
	} )() );
	check( 'the routes of the page, its second app, the manifests and the files are entered, and nothing of it is in the bundle of the site', ( static function() use ( &$a ): bool {

		foreach( [ 'GET://crm', 'GET://crm/assets/*', 'GET://crm/todo', 'GET://crm/manifest.webmanifest', 'GET://crm/todo/manifest.webmanifest' ] as $route )
			if( isset( $a['/nino/http/routes'][$route] ) === false )
				return false;

		return isset( $a['/nino/http/routes']['POST://crm'] ) === false && isset( $a['/nino/http/routes']['GET://crm/assets'] ) === false;
	} )() );
	check( 'the name a file is linked by is twelve characters of its hash and its name; a name the surface does not serve has none', preg_match( '/^[0-9a-f]{12}-crm\.css$/D', \Nino\Modules\Crm\Surface::assetName( $a, 'crm.css' ) ) === 1
		&& substr( \Nino\Modules\Crm\Surface::assetName( $a, 'crm.css' ), 0, 12 ) === substr( (string) hash_file( 'sha256', $features. '/Crm/Surface/assets/crm.css' ), 0, 12 )
		&& \Nino\Modules\Crm\Surface::assetName( $a, 'secret.js' ) === '' && \Nino\Modules\Crm\Surface::assetName( $a, '../crm.css' ) === '' && \Nino\Modules\Crm\Surface::assetName( $a, '' ) === '' && \Nino\Modules\Crm\Surface::assetName( $a, "crm.css\0" ) === '' );
}
catch( \Throwable $e ) {
	srfCrashed( 'the routes and the files the surface declares', $e );
}

/*	The page: for nobody, for an account without the permission, for one with it	*/
try {

	$policy = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";

	[ $status, $body, $header ] = srfGet( $where, '/crm' );
	$data = srfData( $body );
	$token = '';
	$request = srfRequest( $where );

	check( 'a visitor with no session gets the login of the surface: 200, the form, and a data block that holds the way back, the token and the words of the login alone', $status === 200 && srfKind( $status, $body ) === 'login'
		&& array_keys( $data ) === [ 'base', 'route', 'csrf', 'texts' ] && $data['route'] === '/crm' && $data['base'] === '' && preg_match( '/^[0-9a-f]{64}$/D', (string) $data['csrf'] ) === 1
		&& $data['texts'] !== [] && array_filter( array_keys( $data['texts'] ), static fn( mixed $key ): bool => str_starts_with( (string) $key, 'login.' ) === false ) === [] );
	check( 'the sentence of the login is an alert beside the fields, which the script marks and describes: the element is a paragraph with the id the script names, and the fields are those it names', preg_match( '~<p class="state-note bad" id="login-msg" role="alert"></p>~', (string) $body ) === 1 && str_contains( (string) $body, 'id="login-user"' ) === true && str_contains( (string) $body, 'id="login-pw"' ) === true );
	check( 'the login names its script and its style sheet by their hash and carries nothing of a signed-in page: no rail, no user, no menu, no data of a package', str_contains( (string) $body, '/crm/assets/'. substr( (string) hash_file( 'sha256', $features. '/Crm/Surface/assets/login.js' ), 0, 12 ). '-login.js' )
		&& str_contains( (string) $body, '/crm/assets/'. substr( (string) hash_file( 'sha256', $features. '/Crm/Surface/assets/crm.css' ), 0, 12 ). '-crm.css' ) && str_contains( (string) $body, 'class="rail"' ) === false
		&& isset( $data['user'] ) === false && isset( $data['menu'] ) === false && isset( $data['packages'] ) === false && str_contains( (string) $body, 'crm@example.org' ) === false );
	check( 'the head of every page of the surface says it is never cached, never framed and never read out of its origin: its own policy with no inline script and no eval, the referrer kept at home', $header['Content-Security-Policy'] === $policy
		&& $header['Cache-Control'] === 'private, no-store' && $header['X-Frame-Options'] === 'DENY' && $header['Referrer-Policy'] === 'same-origin' && str_starts_with( $header['Content-Type'], 'text/html' )
		&& str_contains( $policy, 'unsafe' ) === false && str_contains( $policy, "script-src 'self';" ) === true && str_contains( $policy, "frame-ancestors 'none'" ) === true );
	check( 'the page carries no inline script (only its data block, a type no browser runs), no handler attribute and no style attribute, and links its files from its own origin', preg_match_all( '~<script\b([^>]*)>~i', (string) $body, $found ) > 0
		&& array_filter( $found[1], static fn( string $attributes ): bool => str_contains( $attributes, 'src="' ) === false && str_contains( $attributes, 'type="application/json"' ) === false ) === []
		&& preg_match( '/\son[a-z]+\s*=/i', (string) $body ) === 0 && preg_match( '/\sstyle\s*=/i', (string) $body ) === 0 && preg_match( '~(?:src|href)="https?://~', (string) $body ) === 0 );
	check( 'the html element carries the language of the page', str_starts_with( (string) strstr( (string) $body, '<html' ), '<html lang="de-DE">' ) === true );

	foreach( [ '/crm?next=https://evil.example/', '/crm?route=//evil.example', '/crm/', '/crm?x[]=1' ] as $uri ) {
		[ $status, $body ] = srfGet( $where, $uri );
		$guess[$uri] = srfData( $body )['route'] ?? null;
	}

	check( 'the way back after the login is the address of the page and never one of the request: a query that names another place changes nothing, and a trailing slash is the page', array_unique( array_values( $guess ) ) === [ '/crm' ] );

	[ $status, $body, $header ] = srfGet( $where, '/crm', $none['plain@example.org'] );
	check( 'an account without the permission of the CRM gets the page that says so: 403, the way to the workbench, the same head, no data block, nothing of the account', $status === 403 && srfKind( $status, $body ) === 'forbidden'
		&& srfData( $body ) === [] && str_contains( (string) $body, 'plain@example.org' ) === false && str_contains( (string) $body, 'href="/_admin/"' ) === true && $header['Content-Security-Policy'] === $policy && $header['Cache-Control'] === 'private, no-store'
		&& $header['X-Frame-Options'] === 'DENY' && str_contains( (string) $body, '<script' ) === false );

	$onlyText = $S['text@example.org'];
	[ $status, $body ] = srfGet( $where, '/crm', $onlyText );
	check( 'the permission of another panel is no permission of the CRM', $status === 403 );

	[ $status, $body, $header, $a ] = srfGet( $where, '/crm', $crm );
	$data = srfData( $body );
	check( 'an account with the permission gets the page: 200, the rail with the entries of the core, the data block with the token of the session and the account, the language, the day and what the account may do', $status === 200 && srfKind( $status, $body ) === 'page'
		&& $data['csrf'] === \Nino\Csrf::getToken( $a ) && $data['user'] === [ 'name' => 'crm@example.org' ] && $data['perm'] === \Nino\Modules\Crm::MANAGE_PERM && $data['locale'] === 'de_DE' && $data['today'] === date( 'Y-m-d' )
		&& $data['perms'] === [ 'crm' => true, 'elements' => false, 'text' => false ] && $data['device'] === [ 'remembered' => false, 'count' => 0 ] && $data['menu'] === [] && is_array( $data['texts'] ) === true && count( $data['texts'] ) > 5
		&& array_key_exists( 'packages', $data ) === true && $data['packages'] === [] && is_array( $data['links'] ) === true && $data['links']['workbench'] === '/_admin/' && $data['links']['site'] === '/' );
	check( 'the rail lists the entries of the core in the order of Menu::CORE with the key of each in its link, and the same data block stands for the whole of the rail', preg_match_all( '~<a href="#([a-z]+)" data-r="([a-z]+)" id="nav-([a-z]+)">~', (string) $body, $found ) === count( \Nino\Modules\Crm\Surface\Menu::CORE )
		&& $found[2] === array_keys( \Nino\Modules\Crm\Surface\Menu::CORE ) && $found[2] === $found[3] && $found[1] === array_column( \Nino\Modules\Crm\Surface\Menu::CORE, 2 ) );
	check( 'the page of an account with the permission has the same head as the others, and the data block is the last thing in the body', $header['Content-Security-Policy'] === $policy && $header['Cache-Control'] === 'private, no-store' && $header['X-Frame-Options'] === 'DENY'
		&& preg_match( '~<script type="application/json" id="crm-data">[^<]*</script>\s*</body>~', (string) $body ) === 1 );

	[ $status, $body ] = srfGet( $where, '/crm', $none['admin@example.org'] );
	check( 'the account that may do everything may come in', $status === 200 && srfKind( $status, $body ) === 'page' && srfData( $body )['perms'] === [ 'crm' => true, 'elements' => true, 'text' => true ] );

	foreach( [ 'POST', 'PUT', 'DELETE', 'PATCH' ] as $method ) {
		$out = ninoErrors( static function() use ( $where, $method, $crm, &$status, &$body ): void {
			[ $status, $body ] = srfGet( $where, '/crm', $crm, [], [], $method );
		}, $failure );
		$other[$method] = [ $status, srfKind( $status, $body ), $failure === null ? '' : 'failed' ];
	}

	check( 'a request of another method than GET is no way to the page: it is turned away by the route with a status that is no 200 page', array_filter( $other, static fn( array $answer ): bool => $answer[1] === 'page' || $answer[1] === 'login' ) === [] );

	[ $status, $body, $header ] = srfGet( $where, '/crm/todo', $crm );
	check( 'the second app is the page of the surface with no rail, the key of the app in its data block, the same head and the same token', $status === 200 && srfKind( $status, $body ) === 'app' && srfData( $body )['app'] === 'todo' && isset( srfData( $body )['menu'] ) === false
		&& $header['Content-Security-Policy'] === $policy && str_contains( (string) $body, 'class="rail"' ) === false && str_contains( (string) $body, 'href="/crm/todo/manifest.webmanifest"' ) === true );
	[ $status, $body ] = srfGet( $where, '/crm/todo' );
	check( 'the login of the second app comes back to the second app', srfKind( $status, $body ) === 'login' && srfData( $body )['route'] === '/crm/todo' );
	[ $status, $body ] = srfGet( $where, '/crm/todo', $none['plain@example.org'] );
	check( 'and the second app has the page that says there is no permission', $status === 403 && srfData( $body ) === [] );
}
catch( \Throwable $e ) {
	srfCrashed( 'the page', $e );
}

/*	The files the surface serves	*/
try {

	$a = srfRequest( $where );
	$css = \Nino\Modules\Crm\Surface::assetName( $a, 'crm.css' );
	$login = \Nino\Modules\Crm\Surface::assetName( $a, 'login.js' );
	$kit = \Nino\Modules\Crm\Surface::assetName( $a, 'crm-kit.js' );

	[ $status, $body, $header, $b, $request ] = srfGet( $where, '/crm/assets/'. $css );
	check( 'a file the surface declares is served to anybody, by its name with its hash: the file itself, its type, for a year, with no session asked', $status === 200 && $request['/nino/http/response']['file'] === $features. '/Crm/Surface/assets/crm.css'
		&& $header['Content-Type'] === 'text/css; charset=utf-8' && $header['Cache-Control'] === 'public, max-age=31536000' && ( $request['/nino/http/response']['body'] ?? '' ) === '' );
	[ $status, , $header, , $request ] = srfGet( $where, '/crm/assets/'. $login );
	check( 'the login script is served as a script of the same origin', $status === 200 && $header['Content-Type'] === 'text/javascript; charset=utf-8' && $request['/nino/http/response']['file'] === $features. '/Crm/Surface/assets/login.js' );
	[ $status, , $header ] = srfGet( $where, '/crm/assets/'. $kit, $crm );
	check( 'a script of the core is served the same way to an account that is signed in', $status === 200 && $header['Content-Type'] === 'text/javascript; charset=utf-8' );

	$hash = substr( $css, 0, 12 );
	$long = str_repeat( 'a', 5000 );
	$wrong = [
		'the hash of another content' => '/crm/assets/'. str_repeat( '0', 12 ). '-crm.css',
		'the hash of another file' => '/crm/assets/'. substr( $login, 0, 12 ). '-crm.css',
		'no hash' => '/crm/assets/crm.css',
		'a short hash' => '/crm/assets/'. substr( $hash, 0, 11 ). '-crm.css',
		'a long hash' => '/crm/assets/'. $hash. 'a-crm.css',
		'a hash in capitals' => '/crm/assets/'. strtoupper( $hash ). '-crm.css',
		'a name nobody declared' => '/crm/assets/'. $hash. '-other.css',
		'a name that is a path' => '/crm/assets/'. $hash. '-../crm.css',
		'a path that climbs' => '/crm/assets/../Surface/Surface.php',
		'a path that climbs, encoded' => '/crm/assets/%2e%2e/Surface/Surface.php',
		'a path that climbs, encoded twice' => '/crm/assets/%252e%252e/Surface/Surface.php',
		'a slash in the name, encoded' => '/crm/assets/'. $hash. '-..%2fSurface%2fSurface.php',
		'a backslash' => '/crm/assets/'. $hash. '-..\\Surface.php',
		'a null byte at the end' => '/crm/assets/'. $css. '%00',
		'a null byte in the name' => '/crm/assets/'. $hash. '-crm.css%00.js',
		'a null byte before the name' => '/crm/assets/%00'. $css,
		'a line feed' => '/crm/assets/'. $css. '%0a',
		'a name of 5000 characters' => '/crm/assets/'. $hash. '-'. $long. '.css',
		'a hash of 5000 characters' => '/crm/assets/'. $long. '-crm.css',
		'nothing' => '/crm/assets/',
		'a directory' => '/crm/assets/'. $hash. '-',
		'a dot' => '/crm/assets/'. $hash. '-.',
		'the page file of the feature' => '/crm/assets/'. substr( (string) hash_file( 'sha256', $features. '/Crm/Surface/Surface.php' ), 0, 12 ). '-Surface.php',
		'the texts of the feature' => '/crm/assets/'. substr( (string) hash_file( 'sha256', $features. '/Crm/Surface/text/de_DE.php' ), 0, 12 ). '-de_DE.php',
		'the file of a database' => '/crm/assets/'. $hash. '-crm.sqlite',
	];
	$answers = [];

	foreach( $wrong as $what => $uri ) {
		$errors = ninoErrors( static function() use ( $where, $uri, &$status, &$body, &$request ): void {
			[ $status, $body, , , $request ] = srfGet( $where, $uri );
		}, $failure );
		$answers[$what] = [ $status, isset( $request['/nino/http/response']['file'] ), $errors === [] && $failure === null ];
	}

	check( 'a request for a file by anything but its name with its hash is a 404, a file is never named by a path, and no file goes out: '. count( $wrong ). ' requests, no error, no warning', array_filter( $answers, static fn( array $answer ): bool => $answer[0] === 404 && $answer[1] === false && $answer[2] === true ) === $answers );

	[ $status ] = srfGet( $where, '/crm/assets/'. $css. '?v=1' );
	check( 'a query on the link of a file is not part of its name', $status === 200 );

	$request = ninoVisit( $a, '/crm/assets/'. $css, 'POST', [ 'REMOTE_ADDR' => '127.0.0.1' ] );
	\Nino\Http::response( $a, $request );
	check( 'a post to a file is no file', isset( $request['/nino/http/response']['file'] ) === false );

	$changed = $features. '/Crm/Surface/assets/crm.css';
	$before = (string) file_get_contents( $changed );
	file_put_contents( $changed, $before. "\n/* changed */\n" );
	[ $status ] = srfGet( $where, '/crm/assets/'. $css );
	[ $statusNew ] = srfGet( $where, '/crm/assets/'. \Nino\Modules\Crm\Surface::assetName( srfRequest( $where ), 'crm.css' ) );
	file_put_contents( $changed, $before );
	check( 'the link of a file is its content: where the file changes the old link is a 404 and the new one is the file', $status === 404 && $statusNew === 200 );
}
catch( \Throwable $e ) {
	srfCrashed( 'the files', $e );
}

/*	The devices that stay signed in: the token, the replacement of it, the grace, the end	*/
try {

	[ $where, $S ] = srfWorld( 'devices' );
	$crm = $S['crm@example.org'];
	$other = $S['other@example.org'];
	$hits = static fn( string $mail ): array => srfRows( $where, 'SELECT * FROM owner_devices WHERE user_mail = :mail ORDER BY id', [ 'mail' => $mail ] );
	$tokenOf = static fn( string $value ): string => (string) substr( $value, (int) strpos( $value, '.' ) + 1 );

	[ $status, $body, $a ] = srfPost( $where, $crm, [], 'crm/device-remember' );
	[ $value, $line ] = srfCookie( $a );
	$rows = $hits( 'crm@example.org' );
	check( 'the owner keeps a device with a word: 200 and the answer says it is kept and how many the account has; the cookie is "<id of the row>.<token>", the token 32 random bytes', $status === 200 && $body['remembered'] === true && $body['count'] === 1
		&& count( $rows ) === 1 && $value === $rows[0]['id']. '.'. $tokenOf( $value ) && preg_match( '/^[1-9][0-9]*\.[A-Za-z0-9_-]{43}$/D', $value ) === 1 );
	check( 'the table keeps the sha256 of the token and never the token: not in the row, not in the answer, not in the activity log', $rows[0]['token_sha256'] === hash( 'sha256', $tokenOf( $value ) ) && $rows[0]['previous_sha256'] === ''
		&& str_contains( json_encode( $rows ), $tokenOf( $value ) ) === false && str_contains( json_encode( $body ), $tokenOf( $value ) ) === false
		&& str_contains( json_encode( \Nino\Modules\Logs\Admin::recentLines( $a, 500 ) ), $tokenOf( $value ) ) === false );
	check( 'the cookie is for the path of the surface alone, HttpOnly, SameSite=Lax, for ninety days, and not Secure where the request is not https', str_contains( $line, '; Path=/crm;' ) === true && str_contains( $line, '; HttpOnly' ) === true && str_contains( $line, '; SameSite=Lax' ) === true
		&& str_contains( $line, 'Max-Age='. ( 90 * 86400 ) ) === true && str_contains( $line, 'Secure' ) === false && count( (array) $a['./crm/cookies'] ) === 1 );
	check( 'the row ends the ninety days after now, holds the account and the mark of its password', abs( (int) $rows[0]['expires'] - ( time() + 90 * 86400 ) ) < 30 && $rows[0]['pw_mark'] === hash( 'sha256', (string) \Nino\Auth::getUser( $a, 'crm@example.org' )['pw'] ) );

	[ , , $secure ] = srfPost( $where, $other, [], 'crm/device-remember', [ 'https' => true ] );
	check( 'where the request is https the cookie is Secure', str_contains( srfCookie( $secure )[1], '; Secure' ) === true );

	// A visit with the cookie and no session signs in, and the token is replaced
	[ $status, $body, , $b ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	[ $next, $nextLine ] = srfCookie( $b );
	$row = $hits( 'crm@example.org' )[0];
	check( 'a visit of the page with the cookie of a device and no session is signed in: the page, not the login, for the account of the device', $status === 200 && srfKind( $status, $body ) === 'page' && srfData( $body )['user'] === [ 'name' => 'crm@example.org' ] );
	check( 'the token is replaced at that visit: a new cookie for the same row, the hash of the new token in the row and the hash of the old one beside it, with the time', $next !== '' && $next !== $value && strstr( $next, '.', true ) === strstr( $value, '.', true )
		&& $row['token_sha256'] === hash( 'sha256', $tokenOf( $next ) ) && $row['previous_sha256'] === hash( 'sha256', $tokenOf( $value ) ) && abs( (int) $row['rotated_at'] - time() ) < 30 && str_contains( $nextLine, 'Max-Age='. ( 90 * 86400 ) ) === true );
	check( 'the days start again at a visit, and the activity log says which account was resumed and never a token', abs( (int) $row['expires'] - ( time() + 90 * 86400 ) ) < 30
		&& str_contains( json_encode( \Nino\Modules\Logs\Admin::recentLines( $b, 500 ) ), 'Resumed the session of crm@example.org from a remembered device' ) === true && str_contains( json_encode( \Nino\Modules\Logs\Admin::recentLines( $b, 500 ) ), $tokenOf( $next ) ) === false );

	// Two tabs that open at once bring the same cookie: the old token still signs in for the grace, with no new token and no write
	[ $status, $body, , $c ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	$again = $hits( 'crm@example.org' )[0];
	check( 'the token that was replaced signs in for a short grace more, and does not replace again: no cookie set, the row as it was', $status === 200 && srfKind( $status, $body ) === 'page' && srfCookie( $c ) === [ '', '' ] && $again === $row );

	// The newest token is the way in, and each visit gives the next
	[ $status, $body, , $e ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $next ] );
	$newest = srfCookie( $e )[0];
	check( 'the newest token is the way in, and each visit gives the next', srfKind( $status, $body ) === 'page' && $newest !== '' && $newest !== $next );
	$value = $newest;

	// After the grace the replaced token is a way in no more, the cookie is deleted, the address has tried once, and the row goes with it: somebody holds a copy of a cookie the browser of the owner has moved on from
	$row = $hits( 'crm@example.org' )[0];
	srfExec( $where, 'UPDATE owner_devices SET rotated_at = :at WHERE id = :id', [ 'at' => time() - \Nino\Modules\Crm\Surface\Devices::GRACE - 2, 'id' => $row['id'] ] );
	[ $status, $body, , $d ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $next ] );
	[ $gone, $goneLine ] = srfCookie( $d );
	check( 'once the grace is over the old token is no way in: the login, and the cookie is deleted', $status === 200 && srfKind( $status, $body ) === 'login' && $gone === '' && str_contains( $goneLine, 'crm_remember=;' ) === true && str_contains( $goneLine, 'Max-Age=0' ) === true );
	check( 'and the device is gone with it: the row is deleted, the newest token is no way in either', $hits( 'crm@example.org' ) === [] && srfKind( ...array_slice( srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] ), 0, 2 ) ) === 'login' );

	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ], [ 'HTTP_USER_AGENT' => "Agent\x01\x02 ". str_repeat( 'é', 300 ) ] );
	$ua = (string) $hits( 'crm@example.org' )[0]['ua'];
	check( 'the browser the device was is kept cut and with no control character', mb_strlen( $ua, 'UTF-8' ) <= 120 && preg_match( '/[\x00-\x1f\x7f]/', $ua ) === 0 );
}
catch( \Throwable $e ) {
	srfCrashed( 'the devices that stay signed in', $e );
}

/*	A cookie that is no good, an account that is not the one, the tries of an address	*/
try {

	[ $where, $S ] = srfWorld( 'cookies' );
	$crm = $S['crm@example.org'];
	$tokenOf = static fn( string $value ): string => (string) substr( $value, (int) strpos( $value, '.' ) + 1 );
	$remember = static function( string $mail ) use ( $where, $S ): string {
		[ , , $a ] = srfPost( $where, $S[$mail], [], 'crm/device-remember' );

		return srfCookie( $a )[0];
	};
	$rows = static fn(): array => srfRows( $where, 'SELECT id, user_mail, token_sha256, previous_sha256, rotated_at, expires, pw_mark FROM owner_devices ORDER BY id' );
	$value = $remember( 'crm@example.org' );
	$id = (int) strstr( $value, '.', true );
	$token = $tokenOf( $value );
	$bad = [
		'a token the row does not hold' => $id. '.'. str_repeat( 'A', 43 ),
		'a row that is not there' => '99999.'. $token,
		'the id of another row, with this token' => ( $id + 1 ). '.'. $token,
		'no id' => $token,
		'no token' => $id. '.',
		'a token that is too short' => $id. '.'. substr( $token, 0, 42 ),
		'a token that is too long' => $id. '.'. $token. 'A',
		'a token with a character that is none of the alphabet' => $id. '.'. substr( $token, 0, 42 ). '+',
		'an id with a leading zero' => '0'. $id. '.'. $token,
		'an id of zero' => '0.'. $token,
		'a negative id' => '-'. $id. '.'. $token,
		'an id of eleven digits' => '10000000000.'. $token,
		'an id in exponent form' => '1e0.'. $token,
		'an id with a sign' => '+'. $id. '.'. $token,
		'an id that is a word' => 'abc.'. $token,
		'a space' => $id. '.'. $token. ' ',
		'a null byte' => $id. '.'. $token. "\0",
		'a line feed' => $id. '.'. $token. "\n",
		'two dots' => $id. '.'. $token. '.'. $token,
		'a cookie of 10000 characters' => str_repeat( 'x', 10000 ),
		'nothing' => '',
	];
	$results = [];

	foreach( $bad as $what => $cookie ) {
		$errors = ninoErrors( static function() use ( $where, $cookie, &$status, &$body, &$a ): void {
			[ $status, $body, , $a ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $cookie ] );
		}, $failure );
		$results[$what] = [ srfKind( $status, $body ), $errors === [] && $failure === null, str_contains( srfCookie( $a )[1], 'Max-Age=0' ) ];
	}

	check( 'a cookie that is not of the form, one for a row that is not there, one with a token the row does not hold: '. count( $bad ). ' of them, none is a way in, none is a warning or an error', array_filter( $results, static fn( array $result ): bool => $result[0] === 'login' && $result[1] === true ) === $results );
	check( 'each of them is deleted at the visit - but the empty one, which there is nothing to delete of - and the row of the device is as it was', array_keys( array_filter( $results, static fn( array $result ): bool => $result[2] === false ) ) === [ 'nothing' ] && $rows()[0]['token_sha256'] === hash( 'sha256', $token ) && count( $rows() ) === 1 );

	[ $status, $body, , $a ] = srfGet( $where, '/crm', null, [ 'crm_remember' => [ 'x' ] ] );
	check( 'a cookie that is a list is no cookie of a device', srfKind( $status, $body ) === 'login' );

	// The cookie that is a good one was not touched by all that
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'and the good one is the way in after all of them', srfKind( $status, $body ) === 'page' );

	// Only the pages of the surface sign a device in
	$value = (string) srfCookie( srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] )[3] )[0] ?: $value;
	$before = $rows();
	$signed = [];

	foreach( [ [ 'GET', '/crm/assets/'. \Nino\Modules\Crm\Surface::assetName( srfRequest( $where ), 'crm.css' ) ], [ 'GET', '/crm/manifest.webmanifest' ], [ 'GET', '/crm/icon.svg' ], [ 'HEAD', '/crm' ], [ 'GET', '/crm/other' ], [ 'GET', '/_admin/' ], [ 'GET', '/' ] ] as [ $method, $uri ] ) {
		[ $status, , , $a ] = srfGet( $where, $uri, null, [ 'crm_remember' => $value ], [], $method );
		$signed[$method. ' '. $uri] = [ \Nino\Auth::getCurrentUser( $a ) !== false, srfCookie( $a ) ];
	}

	check( 'only the request for a page of the surface itself signs a device in: not a file, the manifest, the icon, a HEAD, a page nobody told the devices about, the workbench or the site - and none of them touches the row', array_filter( $signed, static fn( array $result ): bool => $result[0] === true || $result[1] !== [ '', '' ] ) === [] && $rows() === $before );

	[ $status, $body, , $a ] = srfPost( $where, null, [ 'crm_remember' => $value ], 'crm-clients/list' );
	check( 'a post to the endpoint with the cookie of a device has no session: the cookie is read at a GET of a page and at nothing else', $status === 401 || $status === 403 );

	[ $status, $body ] = srfGet( $where, '/crm/todo', null, [ 'crm_remember' => $value ] );
	check( 'the second app is a page the device comes back to', srfKind( $status, $body ) === 'app' );

	// The tries of an address: an address that has made TRIES of them is not counted any more, and its cookie is not deleted any more - a good token is a way in all the same
	$ip = '203.0.113.77';

	for( $try = 0; $try < \Nino\Modules\Crm\Surface\Devices::TRIES; $try++ )
		srfGet( $where, '/crm', null, [ 'crm_remember' => $id. '.'. str_repeat( 'B', 43 ) ], [ 'REMOTE_ADDR' => $ip ] );

	[ $status, $body, , $a ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $id. '.'. str_repeat( 'C', 43 ) ], [ 'REMOTE_ADDR' => $ip ] );
	check( 'an address that has tried the limit of times is not counted and its cookie is not deleted any more', srfKind( $status, $body ) === 'login' && srfCookie( $a ) === [ '', '' ] );
	$value = $rows()[0]['token_sha256'] !== '' ? $value : $value;
	[ $status, $body, , $a ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ], [ 'REMOTE_ADDR' => $ip ] );
	check( 'but the count never stands in the way of a good token: the address that is over the limit signs in with the token of its device', srfKind( $status, $body ) === 'page' );
	[ $status, $body, , $a ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $id. '.'. str_repeat( 'C', 43 ) ], [ 'REMOTE_ADDR' => '203.0.113.78' ] );
	check( 'and another address is counted for itself: its cookie that is no good is deleted', str_contains( srfCookie( $a )[1], 'Max-Age=0' ) === true );

	// The counter reads an address by its bucket: the whole /64 of an IPv6 address is one address
	$v6 = '2001:db8:aaaa:bbbb:';

	for( $try = 0; $try < \Nino\Modules\Crm\Surface\Devices::TRIES; $try++ )
		srfGet( $where, '/crm', null, [ 'crm_remember' => $id. '.'. str_repeat( 'D', 43 ) ], [ 'REMOTE_ADDR' => $v6. dechex( 0x1000 + $try ). ':1:2:3' ] );

	[ $status, $body, , $a ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $id. '.'. str_repeat( 'D', 43 ) ], [ 'REMOTE_ADDR' => $v6. 'ffff:9:9:9' ] );
	check( 'the limit is counted by the bucket of the address (a whole /64 for an IPv6 address): a visitor that changes its address within it is counted still', srfCookie( $a ) === [ '', '' ] );
}
catch( \Throwable $e ) {
	srfCrashed( 'the cookies that are no good', $e );
}

/*	The account behind a device	*/
try {

	[ $where, $S ] = srfWorld( 'accounts' );
	$remember = static function( string $mail ) use ( $where ): string {
		[ , , $a ] = srfPost( $where, srfSession( $where, $mail ), [], 'crm/device-remember' );

		return srfCookie( $a )[0];
	};
	$count = static fn( string $mail = '' ): int => count( srfRows( $where, $mail === '' ? 'SELECT id FROM owner_devices' : 'SELECT id FROM owner_devices WHERE user_mail = :mail', $mail === '' ? [] : [ 'mail' => $mail ] ) );
	$pick = static function( string $mail ) use ( $where ): void {
		$a = srfRequest( $where );
		$a['/nino/auth/user'][$mail]['perms'] = [];
		\Nino\AppData::writeContentData( $a, [ '/nino/auth/user' ] );
	};

	// The password the device was made under
	$value = $remember( 'crm@example.org' );
	$a = srfRequest( $where );
	\Nino\Auth::updateUser( $a, 'crm@example.org', 'crm@example.org', 'a new password for the account' );
	[ $status, $body, , $b ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'a device is bound to the password it was made under: where the password was changed the cookie is no way in, the row is gone and the cookie is deleted', srfKind( $status, $body ) === 'login' && $count( 'crm@example.org' ) === 0 && str_contains( srfCookie( $b )[1], 'Max-Age=0' ) === true );

	// An account that is off
	$value = $remember( 'other@example.org' );
	$a = srfRequest( $where );
	\Nino\Auth::setStatus( $a, 'other@example.org', false );
	[ $status, $body, , $b ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'a device of an account that is switched off is no way in: the login, and the cookie is deleted and not replaced by another', srfKind( $status, $body ) === 'login' && str_contains( srfCookie( $b )[1], 'Max-Age=0' ) === true );
	$a = srfRequest( $where );
	\Nino\Auth::setStatus( $a, 'other@example.org', true );
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'and the account that is switched on again has its device as it was', srfKind( $status, $body ) === 'page' );

	// An account that may not open the surface any more: its device is dead, because the session it opens is the account's own
	$value = $remember( 'other@example.org' );
	$pick( 'other@example.org' );
	$row = (int) strstr( $value, '.', true );
	$had = srfRows( $where, 'SELECT id FROM owner_devices WHERE id = :id', [ 'id' => $row ] );
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'a device of an account that does not hold the permission of the CRM any more is no way in, and its row goes', srfKind( $status, $body ) === 'login' && count( $had ) === 1 && srfRows( $where, 'SELECT id FROM owner_devices WHERE id = :id', [ 'id' => $row ] ) === [] );

	// An account that is gone
	$value = $remember( 'admin@example.org' );
	$a = srfRequest( $where );
	\Nino\Auth::deleteUser( $a, 'admin@example.org' );
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'a device of an account that is deleted is no way in', srfKind( $status, $body ) === 'login' );

	// A device of one account is never a way in as another: the row holds the account
	$crm2 = srfWorld( 'accounts-two' );
	$v1 = ( static function() use ( $crm2 ): string {
		[ , , $a ] = srfPost( $crm2[0], $crm2[1]['crm@example.org'], [], 'crm/device-remember' );

		return srfCookie( $a )[0];
	} )();
	[ $status, $body ] = srfGet( $crm2[0], '/crm', null, [ 'crm_remember' => $v1 ] );
	check( 'the session a device opens is the one of the account of its row', srfData( $body )['user'] === [ 'name' => 'crm@example.org' ] );

	// The most of devices an account has
	[ $where, $S ] = srfWorld( 'many' );
	$remember = static function( string $mail ) use ( $where ): string {
		[ , , $a ] = srfPost( $where, srfSession( $where, $mail ), [], 'crm/device-remember' );

		return srfCookie( $a )[0];
	};
	$ids = [];

	for( $n = 0; $n < \Nino\Modules\Crm\Surface\Devices::MAX + 3; $n++ ) {
		$ids[] = (int) strstr( $remember( 'crm@example.org' ), '.', true );
		srfExec( $where, 'UPDATE owner_devices SET created_at = created_at + :n WHERE id = :id', [ 'n' => $n, 'id' => end( $ids ) ] );
	}

	$kept = array_map( static fn( array $row ): int => (int) $row['id'], srfRows( $where, 'SELECT id FROM owner_devices WHERE user_mail = :mail ORDER BY id', [ 'mail' => 'crm@example.org' ] ) );
	check( 'an account has at most ten devices: the oldest go', count( $kept ) === \Nino\Modules\Crm\Surface\Devices::MAX && $kept === array_slice( $ids, 3 ) );

	// A device kept again from a browser that brings its cookie replaces its own row
	$value = $remember( 'other@example.org' );
	$count = (int) srfRows( $where, 'SELECT COUNT(*) AS n FROM owner_devices WHERE user_mail = :mail', [ 'mail' => 'other@example.org' ] )[0]['n'];
	[ , $answer, $a ] = srfPost( $where, srfSession( $where, 'other@example.org' ), [ 'crm_remember' => $value ], 'crm/device-remember' );
	$count2 = (int) srfRows( $where, 'SELECT COUNT(*) AS n FROM owner_devices WHERE user_mail = :mail', [ 'mail' => 'other@example.org' ] )[0]['n'];
	check( 'a device that is kept again from the browser that has its cookie takes the place of its row and does not make a second', $count === 1 && $count2 === 1 && srfCookie( $a )[0] !== $value );

	// A device row of an account that ran out is taken away when the account keeps another
	srfExec( $where, 'UPDATE owner_devices SET expires = :at WHERE user_mail = :mail', [ 'at' => time() - 5, 'mail' => 'other@example.org' ] );
	[ , , $a ] = srfPost( $where, srfSession( $where, 'other@example.org' ), [], 'crm/device-remember' );
	check( 'the rows of an account that have run out go when it keeps the next', (int) srfRows( $where, 'SELECT COUNT(*) AS n FROM owner_devices WHERE user_mail = :mail', [ 'mail' => 'other@example.org' ] )[0]['n'] === 1 );
	srfExec( $where, 'UPDATE owner_devices SET expires = :at WHERE user_mail = :mail', [ 'at' => time() - 5, 'mail' => 'other@example.org' ] );
	$expired = srfCookie( $a )[0];
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $expired ] );
	check( 'a device whose days are over is no way in', srfKind( $status, $body ) === 'login' );
}
catch( \Throwable $e ) {
	srfCrashed( 'the account behind a device', $e );
}

/*	The three actions: forget, sign out, and what they do without a session, without the permission and without the token	*/
try {

	[ $where, $S ] = srfWorld( 'actions' );
	$crm = $S['crm@example.org'];
	$remember = static function( string $mail ) use ( $where ): string {
		[ , , $a ] = srfPost( $where, srfSession( $where, $mail ), [], 'crm/device-remember' );

		return srfCookie( $a )[0];
	};
	$count = static fn( string $mail ): int => (int) srfRows( $where, 'SELECT COUNT(*) AS n FROM owner_devices WHERE user_mail = :mail', [ 'mail' => $mail ] )[0]['n'];

	$mine = $remember( 'crm@example.org' );
	$second = $remember( 'crm@example.org' );
	$third = $remember( 'crm@example.org' );
	$foreign = $remember( 'other@example.org' );

	// Each of them without a session, without the permission and without the token
	$actions = [ 'crm/device-remember', 'crm/devices-forget', 'crm/signout' ];
	$refused = [];

	foreach( $actions as $action ) {
		$rowsBefore = srfRows( $where, 'SELECT * FROM owner_devices ORDER BY id' );
		$answers = [
			'no session' => srfPost( $where, null, [ 'crm_remember' => $mine ], $action ),
			'the permission of another panel' => srfPost( $where, $S['text@example.org'], [], $action ),
			'no permission' => srfPost( $where, $S['plain@example.org'], [], $action ),
			'no token' => srfPost( $where, $crm, [ 'crm_remember' => $mine ], $action, [ 'token' => '' ] ),
			'a token that is none' => srfPost( $where, $crm, [ 'crm_remember' => $mine ], $action, [ 'token' => str_repeat( 'a', 64 ) ] ),
			'the token of another session' => srfPost( $where, $crm, [ 'crm_remember' => $mine ], $action, [ 'token' => \Nino\Csrf::getToken( srfRequest( $where, $S['other@example.org'] ) ) ] ),
		];

		foreach( $answers as $what => [ $status, $body, $a ] )
			$refused[$action][$what] = [ in_array( $status, [ 401, 403 ], true ), srfCookie( $a ) === [ '', '' ], \Nino\Auth::getCurrentUser( $a ) !== false || $what === 'no session' || str_contains( $what, 'permission' ) === false ];

		$refused[$action]['rows'] = srfRows( $where, 'SELECT * FROM owner_devices ORDER BY id' ) === $rowsBefore;
	}

	check( 'each of the three actions refuses a post with no session, with the permission of another panel, with none, with no token, with a token that is none and with the token of another session: 401 or 403, no cookie, no row touched', ( static function() use ( $refused ): bool {

		foreach( $refused as $answers )
			foreach( $answers as $what => $answer )
				if( $what === 'rows' ? $answer !== true : ( $answer[0] !== true || $answer[1] !== true ) )
					return false;

		return true;
	} )() );

	[ $status ] = srfGet( $where, '/crm/api', $crm );
	check( 'the endpoint is no page: a GET of it is no answer of the endpoint', $status === 404 );

	// forget
	[ $status, $body, $a ] = srfPost( $where, srfSession( $where, 'crm@example.org' ), [ 'crm_remember' => $mine ], 'crm/devices-forget' );
	check( 'forgetting takes every device of the account out of the table, deletes the cookie of this one and says there are none; the session stays and the devices of another account too', $status === 200 && $body['remembered'] === false && $body['count'] === 0 && $count( 'crm@example.org' ) === 0
		&& str_contains( srfCookie( $a )[1], 'Max-Age=0' ) === true && \Nino\Auth::getCurrentUser( $a ) !== false && $count( 'other@example.org' ) === 1 );
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $second ] );
	check( 'and a device that was forgotten is no way in', srfKind( $status, $body ) === 'login' );

	// sign out: this device only
	$one = $remember( 'crm@example.org' );
	$two = $remember( 'crm@example.org' );
	[ $status, $body, $a ] = srfPost( $where, srfSession( $where, 'crm@example.org' ), [ 'crm_remember' => $one ], 'crm/signout' );
	check( 'signing out takes this device out of the table, deletes its cookie and ends the session; the other devices of the account stay', $status === 200 && $body === [ 'signedout' => true ] && $count( 'crm@example.org' ) === 1
		&& str_contains( srfCookie( $a )[1], 'Max-Age=0' ) === true && \Nino\Auth::getCurrentUser( $a ) === false );
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $one ] );
	check( 'a device that was signed out is not signed in again by its cookie, and the other device of the account is', srfKind( $status, $body ) === 'login' && srfKind( ...array_slice( srfGet( $where, '/crm', null, [ 'crm_remember' => $two ] ), 0, 2 ) ) === 'page' );
	[ $status, $body, $a ] = srfPost( $where, srfSession( $where, 'crm@example.org' ), [], 'crm/signout' );
	check( 'signing out of a browser that keeps no device ends the session and touches no row', $status === 200 && $count( 'crm@example.org' ) === 1 && \Nino\Auth::getCurrentUser( $a ) === false );
	$session = srfSession( $where, 'crm@example.org' );
	$tokenBefore = \Nino\Csrf::getToken( srfRequest( $where, $session ) );
	[ , , $a ] = srfPost( $where, $session, [], 'crm/signout' );
	check( 'the token of the page is another one after the session has ended', \Nino\Csrf::getToken( $a ) !== $tokenBefore );

	// The cookie a signed-out device of another account names is no way to delete its row
	$foreignId = (int) strstr( $foreign, '.', true );
	srfPost( $where, srfSession( $where, 'crm@example.org' ), [ 'crm_remember' => $foreign ], 'crm/signout' );
	check( 'the device an account signs out is one of its own: the cookie of another account names no row to delete', $count( 'other@example.org' ) === 1 && $foreignId > 0 );
	srfPost( $where, srfSession( $where, 'crm@example.org' ), [ 'crm_remember' => $foreign ], 'crm/device-remember' );
	check( 'and keeping a device from a browser with the cookie of another account takes nothing of it', $count( 'other@example.org' ) === 1 );

	// The accounts that are signed out everywhere or switched off lose their devices
	$a = srfRequest( $where );
	$event = [ 'action' => 'users/logoutall', 'panel' => 'users', 'status' => 200, 'user' => 'admin@example.org', 'data' => [ 'username' => 'other@example.org' ] ];
	\Nino\Modules\Crm\Surface\Devices::callbackAdminAction( $a, $event );
	check( 'the account that is signed out everywhere in the Users panel loses its devices; those of another account stay', $count( 'other@example.org' ) === 0 && $count( 'crm@example.org' ) === 2 );
	$kept = [];

	foreach( [ [ 'users/logoutall', 403, 'crm@example.org' ], [ 'users/status', 200, 'crm@example.org', true ], [ 'users/status', 200, 'crm@example.org', null ], [ 'users/rename', 200, 'crm@example.org' ], [ 'users/logoutall', 200, 12345 ], [ 'users/logoutall', 200, [ 'x' ] ] ] as $case ) {
		$a = srfRequest( $where );
		$event = [ 'action' => $case[0], 'panel' => 'users', 'status' => $case[1], 'user' => 'admin@example.org', 'data' => [ 'username' => $case[2], 'active' => $case[3] ?? null ] ];
		\Nino\Modules\Crm\Surface\Devices::callbackAdminAction( $a, $event );
		$kept[] = $count( 'crm@example.org' );
	}

	check( 'a refused action, an account switched on, an action of another kind and a name that is no text take no device away', $kept === [ 2, 2, 2, 2, 2, 2 ] );
	$a = srfRequest( $where );
	$event = [ 'action' => 'users/status', 'panel' => 'users', 'status' => 200, 'user' => 'admin@example.org', 'data' => [ 'username' => 'crm@example.org', 'active' => false ] ];
	\Nino\Modules\Crm\Surface\Devices::callbackAdminAction( $a, $event );
	check( 'an account that is switched off in the Users panel loses its devices', $count( 'crm@example.org' ) === 0 );
}
catch( \Throwable $e ) {
	srfCrashed( 'the three actions of the devices', $e );
}

/*	Signing out everywhere ends the sessions of the account, the cookie of a device on the host of the CRM, the mark of the password	*/
try {

	[ $where, $S ] = srfWorld( 'sessions' );
	$crm = $S['crm@example.org'];
	$other = srfSession( $where, 'crm@example.org' );
	$mine = srfSession( $where, 'crm@example.org' );
	[ , , $a ] = srfPost( $where, $mine, [], 'crm/device-remember' );
	$value = srfCookie( $a )[0];

	[ $status, $body ] = srfGet( $where, '/crm', $other );
	$before = $status === 200 && srfKind( $status, $body ) === 'page';
	[ $status, $answer, $c ] = srfPost( $where, $mine, [ 'crm_remember' => $value ], 'crm/devices-forget' );
	$kept = $_SESSION;
	[ $theirs ] = srfGet( $where, '/crm', $other );
	[ $ours, $ourBody ] = srfGet( $where, '/crm', $kept );
	check( 'signing out all devices ends every other session of the account - a session a device opened with its cookie too - and the session that asked stays open with a token that is good: the answer carries it', $before === true && $status === 200 && $answer['remembered'] === false && $answer['count'] === 0
		&& $theirs === 200 && srfKind( $theirs, srfGet( $where, '/crm', $other )[1] ) === 'login' && $ours === 200 && srfKind( $ours, $ourBody ) === 'page' && preg_match( '/^[0-9a-f]{64}$/D', (string) ( $answer['csrf'] ?? '' ) ) === 1 && $answer['csrf'] === srfData( $ourBody )['csrf'] );
	[ $post ] = srfPost( $where, $kept, [], 'crm/device-remember', [ 'token' => $answer['csrf'] ] );
	check( 'and the token the answer carries is the one the next action of that session needs', $post === 200 );
	[ $stale ] = srfPost( $where, $kept, [], 'crm/device-remember', [ 'token' => 'x' ] );
	check( 'while the account of another is not touched', $stale === 403 && srfKind( ...array_slice( srfGet( $where, '/crm', $S['other@example.org'] ), 0, 2 ) ) === 'page' );

	// The password the device was made under is the whole hash: a hash that differs after its sixteenth character is another password
	[ $where, $S ] = srfWorld( 'mark' );
	[ , , $a ] = srfPost( $where, $S['crm@example.org'], [], 'crm/device-remember' );
	$value = srfCookie( $a )[0];
	$b = srfRequest( $where );
	$hash = (string) \Nino\Auth::getUser( $b, 'crm@example.org' )['pw'];
	$b['/nino/auth/user']['crm@example.org']['pw'] = substr( $hash, 0, 16 ). ( substr( $hash, 16, 1 ) === 'a' ? 'b' : 'a' ). substr( $hash, 17 );
	\Nino\AppData::writeContentData( $b, [ '/nino/auth/user' ] );
	[ $status, $body ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'a device is bound to the whole hash of the password: a hash that differs behind its first sixteen characters is no way in', srfKind( $status, $body ) === 'login' && \Nino\Modules\Crm\Surface\Devices::mark( $b, 'crm@example.org' ) === hash( 'sha256', (string) \Nino\Auth::getUser( $b, 'crm@example.org' )['pw'] ) );

	// The cookie where the CRM has a host of its own is for the whole host, and the one of the old path is deleted
	[ $where, $S ] = srfWorld( 'host' );
	$a = srfRequest( $where );
	\Nino\Modules\Crm\Settings::set( $a, 'crm_host', 'crm.example.org' );
	[ $status, $answer, $c ] = srfPost( $where, $S['crm@example.org'], [], 'crm/device-remember', [ 'server' => [ 'HTTP_HOST' => 'crm.example.org' ] ] );
	$lines = (array) ( $c['./crm/cookies'] ?? [] );
	check( 'on the host of the CRM the cookie of a device is for the path / and HttpOnly, and a line deletes the one of the path /crm', count( $lines ) === 2 && str_contains( $lines[0], '; Path=/;' ) === true && str_contains( $lines[0], '; HttpOnly' ) === true && str_contains( $lines[1], '; Path=/crm;' ) === true && str_contains( $lines[1], 'Max-Age=0' ) === true );
	$value = explode( ';', $lines[0] )[0];
	$value = substr( $value, strlen( 'crm_remember=' ) );
	[ $status, $body ] = srfGet( $where, '/', null, [ 'crm_remember' => $value ], [ 'HTTP_HOST' => 'crm.example.org' ] );
	check( 'and the device comes back there at the address of the host', srfKind( $status, $body ) === 'page' );
	// The path of the cookie is / on that host, since the start page of the CRM is /: every address of the host carries it. Only the pages of the surface sign a device in - the workbench, the sign-in of the kernel, the files and the endpoint never do
	$at = [];

	foreach( [ '/_admin', '/_admin/', '/_admin/crm', '/.nino', '/crm/api', '/crm/assets/'. \Nino\Modules\Crm\Surface::assetName( srfRequest( $where ), 'crm.css' ), '/crm/manifest.webmanifest', '/crm/icon.svg', '/elsewhere' ] as $uri ) {
		$visit = srfGet( $where, $uri, null, [ 'crm_remember' => $value ], [ 'HTTP_HOST' => 'crm.example.org' ] );
		$at[$uri] = \Nino\Auth::getCurrentUser( $visit[4] ) !== false || str_contains( implode( ' ', array_map( 'strval', (array) ( $visit[2]['Set-Cookie'] ?? [] ) ) ), 'crm_remember=' ) === true;
	}

	check( 'the cookie of a device that is sent to every address of the host signs in nobody at the workbench, at the sign-in of the kernel, at a file or at the endpoint: '. count( $at ). ' addresses, no account and no new cookie', array_filter( $at ) === [] );
}
catch( \Throwable $e ) {
	srfCrashed( 'the sessions, the mark and the host cookie', $e );
}

/*	A database that cannot be read: the page says so and the devices are never "none"	*/
try {

	[ $where, $S ] = srfWorld( 'down' );
	$crm = srfSession( $where, 'crm@example.org' );
	[ , , $a ] = srfPost( $where, $crm, [], 'crm/device-remember' );
	$value = srfCookie( $a )[0];
	$file = $where. '/private/data/crm.sqlite';
	$file = \Nino\Filesystem::path( $a, \Nino\Modules\Crm\Db::FILE );
	$saved = $file. '.saved';
	$policy = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";
	copy( $file, $saved );
	file_put_contents( $file, str_repeat( 'this is no database ', 50 ) );

	[ $status, $body, $header ] = srfGet( $where, '/crm', $crm );
	check( 'where the database cannot be read the page for a session that holds the permission is the page that says so: 503, never the page, with the head of the surface and no data block', $status === 503 && srfKind( $status, $body ) === 'down'
		&& srfData( $body ) === [] && str_contains( (string) $body, 'class="rail"' ) === false && $header['Cache-Control'] === 'private, no-store' && $header['Content-Security-Policy'] === $policy && str_contains( (string) $body, 'href="/crm"' ) === true && str_contains( (string) $body, 'crm@example.org' ) === false );
	[ $status, $body ] = srfGet( $where, '/crm/todo', $crm );
	check( 'and so is the second app', $status === 503 && srfKind( $status, $body ) === 'down' );
	[ $status, $body ] = srfGet( $where, '/crm', srfSession( $where, 'plain@example.org' ) );
	check( 'an account without the permission is told so all the same, with no look at the database', $status === 403 );
	[ $status, $body ] = srfGet( $where, '/crm' );
	check( 'a visitor with no session is not shown the login either: a password typed into it would end in the page that says the database failed', $status === 503 && srfKind( $status, $body ) === 'down' && str_contains( (string) $body, 'id="login"' ) === false );
	[ $status, $body, , $b ] = srfGet( $where, '/crm', null, [ 'crm_remember' => $value ] );
	check( 'a device does not sign in where the database cannot be read, and is not offered the login either: the page that says so (503), and the cookie is not deleted as one that is no good', $status === 503 && srfKind( $status, $body ) === 'down' && \Nino\Auth::getCurrentUser( $b ) === false && str_contains( srfCookie( $b )[1], 'Max-Age=0' ) === false );

	$answers = [];

	foreach( [ 'crm/device-remember', 'crm/devices-forget', 'crm/signout' ] as $action ) {
		[ $status, $body, $c ] = srfPost( $where, srfSession( $where, 'crm@example.org' ), [ 'crm_remember' => $value ], $action );
		$answers[$action] = [ $status, $body['code'] ?? '', isset( $body['remembered'] ) || isset( $body['count'] ) || isset( $body['signedout'] ), \Nino\Auth::getCurrentUser( $c ) !== false, srfCookie( $c ) ];
	}

	check( 'the three actions answer 500 with the code of the database and no answer of a device ("none", a count, "signed out"); the session stays, no cookie is set or deleted', array_filter( $answers, static fn( array $answer ): bool => $answer[0] === 500 && $answer[1] === 'crm_database' && $answer[2] === false && $answer[3] === true && $answer[4] === [ '', '' ] ) === $answers );

	copy( $saved, $file );
	unlink( $saved );
	[ $status, $body ] = srfGet( $where, '/crm', $crm );
	check( 'the page is the page again where the database can be read', srfKind( $status, $body ) === 'page' );

	// The same for a database that is there and is locked by a writer that does not let go
	$holder = srfHold( $file );
	$started = microtime( true );
	[ $status, $body ] = srfGet( $where, '/crm', $crm );
	$waited = microtime( true ) - $started;
	srfRelease( $holder );
	check( 'a database that is locked by a writer is no "no device" either: the page says it cannot be read, after the time the core waits and no longer', srfKind( $status, $body ) === 'down' && $waited < 20 );
}
catch( \Throwable $e ) {
	srfCrashed( 'the database that cannot be read', $e );
}

/*	The language of the page	*/
try {

	[ $where, $S ] = srfWorld( 'language' );
	$crm = $S['crm@example.org'];
	$de = (array) require $source. '/Surface/text/de_DE.php';
	$en = (array) require $source. '/Surface/text/en_US.php';
	$withLocale = static function( string $locale ) use ( $where, $crm ): array {
		$a = srfRequest( $where, $crm );
		\Nino\Runtime::setSessionValue( $a, './admin/locale', $locale );

		return $_SESSION;
	};

	[ $status, $body ] = srfGet( $where, '/crm', $crm );
	$data = srfData( $body );
	check( 'the page speaks the native language of the site where the session names none: the language of the html element, of the data block and the words are that one', str_contains( (string) $body, '<html lang="de-DE">' ) === true && $data['locale'] === 'de_DE' && $data['texts']['nav.overview'] === $de['nav.overview'] );
	[ $status, $body ] = srfGet( $where, '/crm', $withLocale( 'en_US' ) );
	$data = srfData( $body );
	check( 'it speaks the language of the session of the workbench: the html element, the data block, the words of the rail and of the page, the sentences of the endpoint', str_contains( (string) $body, '<html lang="en-US">' ) === true && $data['locale'] === 'en_US' && $data['texts']['nav.overview'] === $en['nav.overview'] && str_contains( (string) $body, '>'. $en['nav.overview']. '</a>' ) === true
		&& $de['nav.overview'] !== $en['nav.overview'] && isset( $data['texts']['error.crm_database'] ) === true && $data['texts']['error.crm_database'] !== '' );
	[ $status, $body ] = srfGet( $where, '/crm', $withLocale( 'xx_XX' ) );
	check( 'a language of the session that is none of the site is the native one', str_contains( (string) $body, '<html lang="de-DE">' ) === true );
	[ $status, $body ] = srfGet( $where, '/crm', $withLocale( 'de_DE' ) );
	check( 'and the other language of the surface is spoken as well', str_contains( (string) $body, '<html lang="de-DE">' ) === true );

	[ $status, $body ] = srfGet( $where, '/crm', $S['plain@example.org'] );
	check( 'the page that says there is no permission speaks the language too, as the login does', str_contains( (string) $body, '<html lang="de-DE">' ) === true && str_contains( (string) $body, $de['forbidden.title'] ) === true );
	$GLOBALS['srfNative'] = 'en_US';
	[ $status, $body ] = srfGet( $where, '/crm' );
	check( 'the login of a site whose native language is English is English', str_contains( (string) $body, '<html lang="en-US">' ) === true && str_contains( (string) $body, '<h1>'. $en['login.title']. '</h1>' ) === true );
	[ $status, $body ] = srfGet( $where, '/crm/manifest.webmanifest' );
	check( 'and so is its manifest', ( json_decode( (string) $body, true ) ?: [] )['lang'] === 'en-US' );
	$GLOBALS['srfNative'] = 'fr_FR';
	[ $status, $body ] = srfGet( $where, '/crm' );
	check( 'a site whose native language the surface has no words in is shown in English, whatever its own is', str_contains( (string) $body, '<html lang="en-US">' ) === true );
	[ $status, $body ] = srfGet( $where, '/crm', $crm );
	check( 'and so is the page', srfData( $body )['locale'] === 'en_US' );
	unset( $GLOBALS['srfNative'] );

	// The words of the surface: the same keys in both languages, the same places for values, no word that is empty
	$places = static fn( string $text ): int => substr_count( $text, '%s' ) + substr_count( $text, '%d' );
	check( 'the words of the surface are the same keys in both languages, each with as many places for a value in both, none empty and none that is no text', array_keys( $de ) === array_keys( $en ) && count( $de ) > 20
		&& array_filter( array_keys( $de ), static fn( string|int $key ): bool => is_string( $de[$key] ) === false || is_string( $en[$key] ) === false || trim( $de[$key] ) === '' || trim( $en[$key] ) === '' || $places( $de[$key] ) !== $places( $en[$key] ) ) === [] );
	check( 'the keys of the words are flat names of lower case letters, digits, dots and hyphens, so that no key is read as a number or as a path', array_filter( array_keys( $de ), static fn( string|int $key ): bool => preg_match( '/^[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)+$/D', (string) $key ) !== 1 ) === [] );
	check( 'and the words the server itself asks for are among them', array_diff( [ 'page.title', 'page.skip', 'page.noscript', 'brand.tag', 'app.name', 'app.short', 'nav.label', 'login.title', 'login.user', 'login.pw', 'login.submit', 'login.wrong', 'login.stale', 'login.status', 'login.offline', 'rail.site', 'rail.workbench', 'rail.signout', 'keys.open', 'forbidden.title', 'forbidden.text', 'forbidden.link', 'down.title', 'down.text', 'down.link', 'todo.page-title', 'todo.app-name', 'todo.app-short' ], array_keys( $de ) ) === []
		&& array_diff( array_column( \Nino\Modules\Crm\Surface\Menu::CORE, 1 ), array_keys( $de ) ) === [] );
}
catch( \Throwable $e ) {
	srfCrashed( 'the language of the page', $e );
}

/*	The manifest and the icon: public, and nothing of a client in them	*/
try {

	[ $where, $S ] = srfWorld( 'manifest' );
	[ $status, $body, $header ] = srfGet( $where, '/crm/manifest.webmanifest' );
	$manifest = (array) json_decode( (string) $body, true );

	check( 'the manifest of the app is asked for with no session: the name of the app, its address and scope, standalone, the colours of the surface, the language and the icons - an SVG and a PNG', $status === 200 && str_starts_with( $header['Content-Type'], 'application/manifest+json' ) && array_keys( $manifest ) === [ 'name', 'short_name', 'start_url', 'scope', 'display', 'background_color', 'theme_color', 'lang', 'icons' ]
		&& $manifest['start_url'] === '/crm#overview' && $manifest['scope'] === '/crm' && $manifest['display'] === 'standalone' && $manifest['background_color'] === \Nino\Modules\Crm\Surface\App::COLOURS['page'] && $manifest['theme_color'] === \Nino\Modules\Crm\Surface\App::COLOURS['bar']
		&& $manifest['lang'] === 'de-DE' && array_column( $manifest['icons'], 'type' ) === [ 'image/svg+xml', 'image/png' ] && $manifest['icons'][0]['src'] === '/crm/icon.svg' );
	[ $status, $body ] = srfGet( $where, '/crm/todo/manifest.webmanifest' );
	$todo = (array) json_decode( (string) $body, true );
	check( 'the manifest of the second app has an id, a start address and a scope of its own', $status === 200 && $todo['id'] === '/crm/todo' && $todo['start_url'] === '/crm/todo' && $todo['scope'] === '/crm/todo' && $todo['icons'][0]['src'] === '/crm/todo/icon.svg' );

	$GLOBALS['srfName'] = "Firma\0 &amp; [csrf] [[x]] <b>\"Co\"</b>";
	[ $status, $body, $header ] = srfGet( $where, '/crm/manifest.webmanifest' );
	$manifest = (array) json_decode( (string) $body, true );
	preg_match_all( '/"(?:[^"\\\\]|\\\\.)*"/s', (string) $body, $strings );
	check( 'the name of the website is put into the manifest as text: every string of it has its brackets, its angle brackets and its ampersands written as escapes, so that nothing that reads the body twice would read a fill, a shortcode or a tag in it, and no null byte stays', $status === 200
		&& array_filter( $strings[0], static fn( string $string ): bool => preg_match( '/[\[\]<>&\x00]/', $string ) === 1 ) === [] && str_contains( (string) $body, '\\u005b\\u005bx\\u005d\\u005d' ) === true );
	check( 'and it reads as it was written, with the word of the surface behind it and no null byte', $manifest['name'] === 'Firma & [csrf] [[x]] <b>"Co"</b> – CRM' );
	[ $status, $body ] = srfGet( $where, '/crm' );
	check( 'the login names it as text: escaped, so that no tag of it is one, and what is in brackets is put in once and never read', str_contains( (string) $body, '<b>"Co"' ) === false && str_contains( (string) $body, '&lt;b&gt;&quot;Co&quot;&lt;/b&gt;' ) === true && str_contains( html_entity_decode( (string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), '[csrf] [[x]]' ) === true && str_contains( (string) $body, "\0" ) === false );
	unset( $GLOBALS['srfName'] );

	[ $status, $body, $header ] = srfGet( $where, '/crm/icon.svg' );
	check( 'the icon is an SVG with the letters of the surface, asked for with no session, for a day', $status === 200 && str_starts_with( $header['Content-Type'], 'image/svg+xml' ) && str_contains( (string) $body, '<svg' ) === true && str_contains( (string) $body, '>CRM</text>' ) === true && $header['Cache-Control'] === 'public, max-age=86400' );
	[ $status, $body, $header, , $request ] = srfGet( $where, '/crm/icon.png' );
	check( 'the PNG of the icon is a file of the kind image/png, and a size nobody declared is none', $status === 200 && $header['Content-Type'] === 'image/png' && is_file( (string) ( $request['/nino/http/response']['file'] ?? '' ) ) === true && str_starts_with( (string) file_get_contents( (string) $request['/nino/http/response']['file'] ), "\x89PNG" ) === true );
	foreach( [ '/crm/icon.gif', '/crm/icon-193.png', '/crm/icon.svg/x', '/crm/icon.png/../x', '/crm/todo/icon.gif', '/crm/icon.svg%00' ] as $uri )
		$icons[$uri] = srfGet( $where, $uri )[0];
	check( 'an icon that nobody declared is a 404', array_filter( $icons, static fn( int $status ): bool => $status !== 404 ) === [] );
	[ $status, $body ] = srfGet( $where, '/crm/manifest.webmanifest', null, [], [], 'POST' );
	check( 'a post to the manifest is no manifest', $status !== 200 || str_contains( (string) $body, '"name"' ) === false );
}
catch( \Throwable $e ) {
	srfCrashed( 'the manifest and the icon', $e );
}

/*	The places a package docks to: its words, script and style sheet, an entry of the rail, the data of the page, a page the devices come back to, an app and its icon - on	*/
try {

	[ $where, $S ] = srfWorld( 'probe-on', 'on' );
	$crm = $S['crm@example.org'];
	$de = (array) require $source. '/Surface/text/de_DE.php';
	$a = srfRequest( $where );
	$answers = (array) ( $a['./crm-surface-probe/answers'] ?? [] );

	check( 'a package that is on enters at every place the surface offers - words, style sheet, script, entry of the rail (and one whose key is digits), data of the page, a page for the devices, the icon and the app - and every place says yes', array_keys( $answers ) === [ 'texts', 'style', 'script', 'menu', 'digits', 'data', 'allow', 'icons', 'app' ]
		&& array_filter( $answers, static fn( mixed $answer ): bool => $answer !== true ) === [] && ninoWarnings() === [] );

	[ $status, $body, $header, $b ] = srfGet( $where, '/crm', $crm );
	$data = srfData( $body );
	check( 'the page links the files of the package after those of the core, a style sheet before the script it belongs to, each by the hash of its content', preg_match_all( '~<(?:script src|link rel="stylesheet" href)="/crm/assets/([0-9a-f]{12})-([a-z0-9.-]+)"~', (string) $body, $found ) === 8
		&& $found[2] === [ 'crm.css', 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js', 'probe.css', 'probe.js' ] && $found[1][6] === substr( (string) hash_file( 'sha256', $features. '/CrmSurfaceProbe/assets/probe.css' ), 0, 12 ) );
	check( 'and serves them by name to anybody: the file of the package, its type, for a year - a wrong hash, another package name and a file below a private directory are 404', ( static function() use ( $where, $b, $features ): bool {

		$name = \Nino\Modules\Crm\Surface\Scripts::assetName( $b, 'probe.js' );
		[ $ok, , $header, , $request ] = srfGet( $where, '/crm/assets/'. $name );
		[ $stale ] = srfGet( $where, '/crm/assets/'. str_repeat( '0', 12 ). '-probe.js' );
		[ $unknown ] = srfGet( $where, '/crm/assets/'. substr( $name, 0, 12 ). '-other.js' );
		[ $secret ] = srfGet( $where, '/crm/assets/'. substr( (string) hash_file( 'sha256', $features. '/CrmSurfaceProbe/private/secret.js' ), 0, 12 ). '-secret.js' );
		[ $css ] = srfGet( $where, '/crm/assets/'. \Nino\Modules\Crm\Surface\Scripts::assetName( $b, 'probe.css' ) );

		return $ok === 200 && $request['/nino/http/response']['file'] === $features. '/CrmSurfaceProbe/assets/probe.js' && $header['Content-Type'] === 'text/javascript; charset=utf-8' && $header['Cache-Control'] === 'public, max-age=31536000' && $stale === 404 && $unknown === 404 && $secret === 404 && $css === 200;
	} )() );

	check( 'a file of a package is checked again when it is asked for: one that was replaced after it was added by a link into a private directory, with the same content, is no longer served under the name it had', ( static function() use ( $where, $b, $features ): bool {

		$file = $features. '/CrmSurfaceProbe/assets/probe.js';
		$copy = $features. '/CrmSurfaceProbe/private/probe-copy.js';
		$name = \Nino\Modules\Crm\Surface\Scripts::assetName( $b, 'probe.js' );
		$kept = (string) file_get_contents( $file );
		file_put_contents( $copy, $kept );
		rename( $file, $file. '.kept' );
		symlink( $copy, $file );

		try {
			[ $status ] = srfGet( $where, '/crm/assets/'. $name );
			$none = \Nino\Modules\Crm\Surface\Scripts::file( $b, 'probe.js' ) === '';
		}
		finally {
			unlink( $file );
			rename( $file. '.kept', $file );
			unlink( $copy );
		}

		[ $back ] = srfGet( $where, '/crm/assets/'. $name );

		return $status === 404 && $none === true && $back === 200;
	} )() );

	check( 'the words of the package are in the page in the language of the page, next to the core\'s and never over them, and a key that is digits stays a key that is a text', $data['texts']['probe.title'] === 'Probe' && $data['texts']['probe.12'] === 'zwölf' && $data['texts']['2024'] === 'ein Jahr' && $data['texts']['nav.overview'] === $de['nav.overview'] );
	check( 'the entries of the rail the package added are in the data block as a list of key, word and address, the key of each a text - the one that is digits too - and in the rail between the projects and the settings, the word escaped', $data['menu'] === [ [ 'key' => 'probe', 'word' => 'Probe <b>', 'href' => '/crm/probe' ], [ 'key' => '2024', 'word' => 'Year', 'href' => '/crm/2024' ] ]
		&& is_string( $data['menu'][1]['key'] ) === true && preg_match_all( '~<a href="[^"]*" data-(?:r|page)="([a-z0-9-]+)" id="nav-([a-z0-9-]+)">~', (string) $body, $nav ) === 7 && $nav[1] === [ 'overview', 'clients', 'projects', 'probe', '2024', 'settings', 'values' ]
		&& str_contains( (string) $body, '<a href="/crm/probe" data-page="probe" id="nav-probe">Probe &lt;b&gt;</a>' ) === true && str_contains( (string) $body, 'Probe <b>' ) === false );
	check( 'the data of the package are in the block under its key', $data['packages'] === [ 'crm-surface-probe' => [ 'answer' => 42 ] ] );

	// What the package says is data: nothing of it ends the block or becomes markup
	$GLOBALS['srfSet'] = [ './crm-surface-probe/data' => [ 'x' => '</script><img src=x onerror=alert(1)>', 'y' => '&amp; <!-- ', 'z' => "line\u{2028}break", 'n' => 7 ] ];
	[ $status, $body ] = srfGet( $where, '/crm', $crm );
	unset( $GLOBALS['srfSet'] );
	check( 'a value of the data of a package cannot end the block: the angle brackets and the ampersand are escapes in it, and it reads back as it was', str_contains( (string) $body, '</script><img' ) === false && str_contains( (string) $body, 'onerror=alert(1)>' ) === false
		&& srfData( $body )['packages']['crm-surface-probe']['x'] === '</script><img src=x onerror=alert(1)>' && srfData( $body )['packages']['crm-surface-probe']['y'] === '&amp; <!-- ' && substr_count( (string) $body, '</script>' ) === substr_count( (string) $body, '<script' ) );

	// The login has nothing of a package
	[ $status, $body ] = srfGet( $where, '/crm' );
	check( 'the login carries nothing of a package: no data, no entry, no word of its own - the files it links are its own', srfKind( $status, $body ) === 'login' && str_contains( (string) $body, 'crm-surface-probe' ) === false && str_contains( (string) $body, 'probe' ) === false && isset( srfData( $body )['texts']['probe.title'] ) === false );

	// A page of the package under the gate of the surface, and the devices come back to it
	[ $status, $body, $header ] = srfGet( $where, '/crm/probe', $crm );
	check( 'a page of a package below /crm that goes through the gate has the head of the surface and is the page of the package for an account that may come in', $status === 200 && $body === 'the probe page of crm@example.org' && $header['Content-Security-Policy'] === "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'" && $header['Cache-Control'] === 'private, no-store' );
	[ $status, $body ] = srfGet( $where, '/crm/probe' );
	check( 'for a visitor it is the login that comes back to the page of the package', srfKind( $status, $body ) === 'login' && srfData( $body )['route'] === '/crm/probe' );
	[ $status, $body ] = srfGet( $where, '/crm/probe', $S['plain@example.org'] );
	check( 'for an account without the permission it is the page that says so', $status === 403 && str_contains( (string) $body, 'probe page' ) === false );
	[ , , $made ] = srfPost( $where, srfSession( $where, 'crm@example.org' ), [], 'crm/device-remember' );
	$value = srfCookie( $made )[0];
	[ $status, $body, , $c ] = srfGet( $where, '/crm/probe', null, [ 'crm_remember' => $value ] );
	check( 'a device that stays signed in comes back to a page the package told the devices about, and to the page of its app', $body === 'the probe page of crm@example.org' && in_array( '/crm/probe', \Nino\Modules\Crm\Surface\Devices::pages( $b ), true ) === true
		&& in_array( '/crm/probeapp', \Nino\Modules\Crm\Surface\Devices::pages( $b ), true ) === true );
	[ $status, $body ] = srfGet( $where, '/crm/probeapp', null, [ 'crm_remember' => srfCookie( $c )[0] ] );
	check( 'the page of the app of a package is the page of the surface with no rail, the key of the app in its block, its own manifest and title - for the device as for a session', $status === 200 && srfKind( $status, $body ) === 'app' && srfData( $body )['app'] === 'probeapp'
		&& str_contains( (string) $body, 'href="/crm/probeapp/manifest.webmanifest"' ) === true && str_contains( (string) $body, '<title>Probe app</title>' ) === true && isset( srfData( $body )['menu'] ) === false );
	$pageBody = (string) $body;
	[ $status, $body ] = srfGet( $where, '/crm/probeapp' );
	check( 'the login of the app of a package comes back to the app', srfKind( $status, $body ) === 'login' && srfData( $body )['route'] === '/crm/probeapp' );
	check( 'a values method of an app that takes the directory as a reference, which the place allows, changes a copy: the login and the page of the app still lie below the directory of the core and say nothing of what the method put in it', str_contains( (string) $body, 'hijacked' ) === false && str_contains( (string) $body, '<script src="/crm/assets/' ) === true && str_contains( $pageBody, 'hijacked' ) === false && str_contains( $pageBody, '<script src="/crm/assets/' ) === true );
	[ $status, $body, $header ] = srfGet( $where, '/crm/probeapp/icon.svg' );
	check( 'the icon of the app of a package is answered by the routes it registered, public, with its letters', $status === 200 && str_contains( (string) $body, '>PRB</text>' ) === true );
	$request = srfRequest( $where, $crm );
	$answer = ninoVisit( $request, '/crm', 'GET', [ 'REMOTE_ADDR' => '127.0.0.1' ] );
	\Nino\Modules\Crm\Surface::page( $request, $answer, 'nothing' );
	check( 'a key of an app nobody entered is the page of the surface: the key of an app is never taken from anything but the register', srfKind( 200, $answer['/nino/http/response']['body'] ) === 'page' && isset( srfData( $answer['/nino/http/response']['body'] )['app'] ) === false && array_keys( \Nino\Modules\Crm\Surface\Apps::all( $request ) ) === [ 'todo', 'probeapp' ] );
}
catch( \Throwable $e ) {
	srfCrashed( 'the package of the suite, on', $e );
}

/*	The letters of the icon of an app are a text, whatever the text is: the name of a function of php is letters too, and the answers a browser gets - the icon and the page of the app - show it. Only a method of the package, written as
	[ class, method ], says them from the app	*/
try {

	[ $where, $S ] = srfWorld( 'letters', 'on' );
	$crm = $S['crm@example.org'];
	$wrong = [];

	foreach( [ 'End', 'Log', 'Pi', 'Max', 'Key', 'Abs', 'Dir', 'Ord' ] as $letters ) {

		$GLOBALS['srfSet'] = [ './crm-surface-probe/letters' => $letters ];
		[ $status, $body ] = srfGet( $where, '/crm/probeapp/icon.svg' );
		[ $page, $html ] = srfGet( $where, '/crm/probeapp', $crm );

		if( $status !== 200 || str_contains( (string) $body, '>'. $letters. '</text>' ) === false || $page !== 200 || srfKind( $page, $html ) !== 'app' )
			$wrong[] = $letters;
	}

	$GLOBALS['srfSet'] = [ './crm-surface-probe/letters' => [ \Nino\Modules\CrmSurfaceProbe::class, 'initials' ] ];
	[ $status, $body ] = srfGet( $where, '/crm/probeapp/icon.svg' );
	check( 'letters that are the name of a function of php are the letters, in the icon and in the page of the app, and a method of the package says them from the app', $wrong === [] && $status === 200 && str_contains( (string) $body, '>FN</text>' ) === true );

	foreach( [ [ 'self', 'initials' ], [ \Nino\Modules\CrmSurfaceProbeTwo::class, 'init' ], 'strtoupper' ] as $letters ) {

		$GLOBALS['srfSet'] = [ './crm-surface-probe/letters' => $letters ];
		[ $status, $body ] = srfGet( $where, '/crm/probeapp/icon.svg' );

		if( $status === 200 )
			$wrong[] = json_encode( $letters );
	}

	check( 'letters that are neither a text nor a method of the package leave the app out: its icon is a 404', $wrong === [] );
	unset( $GLOBALS['srfSet'] );
}
catch( \Throwable $e ) {
	srfCrashed( 'the letters of the icon of an app', $e );
}

/*	A page is read once: what the surface puts in is text, whatever it holds - the name of the website, a word or a menu word of a package, the data of a package, the address of the account. The pins ask the whole way the
	request takes (srfGet() runs the front controller's request(), Html::response() included), the way the browser gets the page	*/
try {

	[ $where, $S ] = srfWorld( 'once', 'on' );
	$crm = $S['crm@example.org'];
	$evil = '</script><b id=injected>';
	$GLOBALS['srfFills'] = [ '[[/probe/inject]]' => $evil ];
	$GLOBALS['srfName'] = 'ACME [[/nino/html/lang]] X [csrf] Y [template x]';
	$GLOBALS['srfSet'] = [ './crm-surface-probe/menu-word' => 'Menu [[/probe/inject]] [csrf]', './crm-surface-probe/data' => [ 'k' => 'Data [[/probe/inject]] [csrf] [template x]' ] ];

	[ $status, $body, $header, , $request ] = srfGet( $where, '/crm' );
	$text = html_entity_decode( (string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	check( 'the name of the website stands in the login as the kernel reads a text - the fill in it is resolved once - and what is left of a shortcode or a template call is text, and the page is marked as the answer it is', srfKind( $status, $body ) === 'login'
		&& str_contains( $text, '<b>ACME de-DE X [csrf] Y [template x]</b>' ) === true && str_contains( $text, '[[/nino/html/lang]]' ) === false && ( $request['/nino/http/response']['raw'] ?? false ) === true );

	[ $status, $body, , , $request ] = srfGet( $where, '/crm', $crm );
	$text = html_entity_decode( (string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$data = srfData( $body );
	check( 'the name of the website stands in the rail as the kernel reads it, and the page of the surface is marked raw: nothing in it is read once more for fills and shortcodes', $status === 200 && srfKind( $status, $body ) === 'page'
		&& str_contains( $text, '<b>ACME de-DE X [csrf] Y [template x]</b>' ) === true && ( $request['/nino/http/response']['raw'] ?? false ) === true );
	check( 'a word of a package with a fill whose value ends the data block and makes an element: the block reads back with the word as it was written, and no element of the page is the one the fill meant', $data['texts']['probe.fill'] === 'Probe [[/probe/inject]] [csrf] [template x]'
		&& str_contains( (string) $body, 'id=injected' ) === false && substr_count( (string) $body, '</script>' ) === substr_count( (string) $body, '<script' ) );
	check( 'and so does the data of a package, the word of an entry of its in the rail, and every other string of the block: no bracket stands in the block that is not syntax of the JSON', $data['packages']['crm-surface-probe']['k'] === 'Data [[/probe/inject]] [csrf] [template x]'
		&& $data['menu'][0]['word'] === 'Menu [[/probe/inject]] [csrf]' && str_contains( (string) $body, 'Menu [[/probe/inject]]' ) === false && str_contains( $text, 'Menu [[/probe/inject]] [csrf]</a>' ) === true
		&& preg_match( '~<script type="application/json" id="crm-data">(.*?)</script>~s', (string) $body, $block ) === 1 && preg_match_all( '/"(?:[^"\\\\]|\\\\.)*"/s', $block[1], $strings ) > 50
		&& array_filter( $strings[0], static fn( string $string ): bool => str_contains( $string, '[' ) === true || str_contains( $string, ']' ) === true ) === [] );

	// The account: an address with brackets and a tag in a quoted local part
	$odd = '"a[csrf]b[[/probe/inject]]<i>"@example.org';
	$a = srfRequest( $where );

	if( \Nino\Auth::insertUser( $a, $odd, 'odd password', [ '/_admin/crm/manage' ] ) !== false ) {
		$b = srfRequest( $where );
		\Nino\Auth::loginUser( $b, $odd, 'odd password' );
		[ $status, $body ] = srfGet( $where, '/crm', $_SESSION );
		$data = srfData( $body );
		check( 'the address of the account is text in the rail and in the block: nothing of it is read, no element is made of it', $status === 200 && ( $data['user']['name'] ?? '' ) === $odd && str_contains( (string) $body, 'id=injected' ) === false
			&& str_contains( (string) $body, '<i>' ) === false && str_contains( (string) $body, 'a&#91;csrf&#93;b&#91;&#91;/probe/inject&#93;&#93;&lt;i&gt;' ) === true );
	}
	else
		check( 'the address of the account is text in the rail and in the block (the project took the address)', false );

	// The page that says there is no permission and the page that says the database is down are answered the same way
	[ $status, $body, , , $request ] = srfGet( $where, '/crm', $S['plain@example.org'] );
	check( 'the page that says there is no permission is marked raw too', $status === 403 && ( $request['/nino/http/response']['raw'] ?? false ) === true );

	// The packages key of the block: a name of a package the block is read under
	$GLOBALS['srfSet'] = [ './crm-surface-probe/data' => [ 'a[b]' => '[[/probe/inject]]' ] ];
	[ , $body ] = srfGet( $where, '/crm', $crm );
	check( 'the keys of the data of a package are text too, and a value that is a fill stays one', srfData( $body )['packages']['crm-surface-probe'] === [ 'a[b]' => '[[/probe/inject]]' ] && str_contains( (string) $body, 'id=injected' ) === false );

	check( 'whatever the surface puts into the markup as text has no bracket left in it, so that nothing that reads the page once more finds a fill or a shortcode: the brackets are entities, the tags and quotes are escaped', \Nino\Modules\Crm\Surface::text( '[csrf] [[/probe/inject]] <b>"x"</b>' ) === '&#91;csrf&#93; &#91;&#91;/probe/inject&#93;&#93; &lt;b&gt;&quot;x&quot;&lt;/b&gt;' );

	// Text of the answers of the endpoint is JSON, which the kernel does not read: the same strings come back as they were written
	$class = new \ReflectionMethod( \Nino\Modules\Crm\Surface::class, 'json' );
	check( 'a byte that is no UTF-8 in a word or in the data of a package is replaced and is no error', str_contains( \Nino\Modules\Crm\Surface::json( [ 'x' => "a\xB1b" ] ), 'a' ) === true && $class->isPublic() === true );

	// The name in the other language, and a fill in the name whose value is markup, a bracket or a token: it is read once and shown as text - on the page, in the login, in the manifest
	$GLOBALS['srfSet'] = [ './nino/locales/current' => 'en_US' ];
	[ , $english ] = srfGet( $where, '/crm', $crm );
	$GLOBALS['srfName'] = 'ACME [[/nino/html/lang]] [[/probe/inject]] [csrf]';
	$GLOBALS['srfFills'] = [ '[[/probe/inject]]' => '</script><b id=injected> [[/nino/html/lang]] [template x]' ];
	$GLOBALS['srfSet'] = [];
	[ , $login ] = srfGet( $where, '/crm' );
	[ , $page ] = srfGet( $where, '/crm', $crm );
	[ , $manifest ] = srfGet( $where, '/crm/manifest.webmanifest' );
	$called = (string) ( json_decode( (string) $manifest, true ) ?: [] )['name'];
	check( 'the name of the website is read in the language of the request: [[/nino/html/lang]] in it is en-US in English and de-DE in German', str_contains( html_entity_decode( (string) $english, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), '<b>ACME en-US X [csrf] Y [template x]</b>' ) === true );
	check( 'a fill in the name whose value is markup, a fill and a token is read once and shown as text: in the login, on the page and in the manifest nothing is an element, a fill or a token that works, and the value stands as it was', array_filter( [ $login, $page ], static fn( mixed $body ): bool => str_contains( (string) $body, '<b id=injected>' ) === true || str_contains( (string) $body, '</script><b' ) === true || str_contains( (string) $body, '[[/nino/html/lang]]' ) === true || str_contains( (string) $body, '[csrf]' ) === true || str_contains( (string) $body, '&lt;b id=injected&gt;' ) === false ) === []
		&& str_contains( $called, 'ACME de-DE ' ) === true && str_contains( $called, '</script><b id=injected>' ) === true && str_contains( $called, '[template x]' ) === true );
	unset( $GLOBALS['srfFills'], $GLOBALS['srfName'], $GLOBALS['srfSet'] );
}
catch( \Throwable $e ) {
	unset( $GLOBALS['srfFills'], $GLOBALS['srfName'], $GLOBALS['srfSet'] );
	srfCrashed( 'a page is read once', $e );
}

/*	The places a package docks to: what each of them refuses	*/
try {

	// The package of the suite enters nothing by itself here: the places are tried by hand, as that package, and as the second one
	$GLOBALS['srfSet'] = [ './crm-surface-probe/quiet' => true ];
	[ $where, $S ] = srfWorld( 'probe-refusals', 'on' );
	$crm = $S['crm@example.org'];
	$a = srfRequest( $where );
	$probe = $features. '/CrmSurfaceProbe';
	file_put_contents( $probe. '/assets/notes.txt', 'a text' );
	file_put_contents( $where. '/outside.js', 'window.outside = 1;' );
	file_put_contents( $where. '/private/private.js', 'window.private = 1;' );
	mkdir( $features. '/OtherPackage/assets', 0755, true );
	mkdir( $features. '/OtherPackage/text', 0755, true );
	file_put_contents( $features. '/OtherPackage/assets/other.js', 'window.other = 1;' );
	symlink( $where. '/outside.js', $probe. '/assets/outside.js' );
	symlink( $features. '/Crm/Surface/Surface.php', $probe. '/assets/php.js' );
	mkdir( $probe. '/assets/folder.js' );
	$ok = $probe. '/assets/probe.js';

	// Scripts and style sheets
	$cases = [
		'a name with a capital' => [ 'Probe.js', $ok ],
		'a name of another kind' => [ 'probe.css', $ok ],
		'a name with a path' => [ '../probe.js', $ok ],
		'a name with a slash' => [ 'a/probe.js', $ok ],
		'a name with two hyphens' => [ 'a--b.js', $ok ],
		'a name that starts with a hyphen' => [ '-a.js', $ok ],
		'a name that ends with a hyphen' => [ 'a-.js', $ok ],
		'a name with a dot before the ending' => [ 'a.b.js', $ok ],
		'a name that is long' => [ str_repeat( 'a', 62 ). '.js', $ok ],
		'a name with a null byte' => [ "a\0.js", $ok ],
		'no name' => [ '.js', $ok ],
		'the name of a file of the surface' => [ 'crm-kit.js', $ok ],
		'a path that is not absolute' => [ 'rel.js', 'assets/probe.js' ],
		'no path' => [ 'a.js', '' ],
		'a path with a null byte' => [ 'a.js', $ok. "\0.txt" ],
		'a path that is nothing' => [ 'a.js', $probe. '/assets/missing.js' ],
		'a directory' => [ 'a.js', $probe. '/assets/folder.js' ],
		'a file that is no script' => [ 'a.js', $probe. '/assets/notes.txt' ],
		'a symlink to a file outside the features' => [ 'a.js', $probe. '/assets/outside.js' ],
		'a symlink to a file of another kind' => [ 'a.js', $probe. '/assets/php.js' ],
		'a file below a directory named private' => [ 'a.js', $probe. '/private/secret.js' ],
		'a file of the private half of the project' => [ 'a.js', $where. '/private/private.js' ],
		'a file outside the features' => [ 'a.js', $where. '/outside.js' ],
		'a file of the core, which is no package\'s' => [ 'a.js', $features. '/Crm/Surface/assets/login.js' ],
		'a file of another package' => [ 'a.js', $features. '/OtherPackage/assets/other.js' ],
		'a file of the package whose folder begins with the name of this one' => [ 'a.js', $features. '/CrmSurfaceProbeTwo/assets/two.js' ],
		'a path that climbs out' => [ 'a.js', $probe. '/assets/../../../../../../../../etc/passwd' ],
		'a path that climbs and comes back' => [ 'a.js', $probe. '/assets/../private/secret.js' ],
	];
	$answers = [];

	foreach( $cases as $what => [ $name, $file ] )
		$answers[$what] = srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'add', $a, $name, $file );

	check( 'a script is refused for a name that is none (capitals, a path, doubled or loose hyphens, a dot, a length, a null byte, the name of a file of the surface) and for a file that is none (not absolute, not there, a directory, not a script, a link to one outside, below private, of the private half, outside the features, a path that climbs): '. count( $cases ). ' of them, none on the list', array_filter( $answers ) === [] && \Nino\Modules\Crm\Surface\Scripts::entries( $a ) === [] );
	check( 'a style sheet is refused for the same reasons and for the ending of a script', srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'addStyle', $a, 'probe.js', $probe. '/assets/probe.css' ) === false && srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'addStyle', $a, 'probe.css', $ok ) === false && srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'addStyle', $a, 'crm.css', $probe. '/assets/probe.css' ) === false
		&& srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'addStyle', $a, 'probe.css', $probe. '/data/secret.css' ) === false && \Nino\Modules\Crm\Surface\Scripts::entries( $a ) === [] );
	$kept = \Nino\Modules\Crm\Surface\Scripts::refused( $a );
	check( 'a refusal is kept with the rule it broke for each name, and nothing is warned of where it is made: the request that adds is the one of a file or of the endpoint as often as of the page', count( $kept ) >= 13 && isset( $kept['Probe.js'], $kept['a.js'], $kept['rel.js'] ) === true && array_filter( $kept, static fn( mixed $rule ): bool => is_string( $rule ) === false || $rule === '' || str_contains( $rule, $where ) === true ) === [] && ninoWarnings() === [] );

	$a = srfRequest( $where, $crm );
	srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'add', $a, 'refused.js', $where. '/outside.js' );
	srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'add', $a, 'refused.js', $where. '/outside.js' );
	srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'addStyle', $a, 'refused.css', $probe. '/data/secret.css' );
	ninoWarnings();
	$request = ninoVisit( $a, '/crm', 'GET', [ 'REMOTE_ADDR' => '127.0.0.1' ] );
	\Nino\Http::response( $a, $request );
	$warned = ninoWarnings();
	check( 'the page says what was refused once for each name, where its author looks, and names the rule and not the file', count( $warned ) === 2 && str_contains( $warned[0], 'refused.js' ) === true && str_contains( $warned[0], 'outside the folder of the package' ) === true && str_contains( $warned[1], 'refused.css' ) === true && str_contains( $warned[1], $where ) === false );
	$request = ninoVisit( $a, '/crm/assets/'. \Nino\Modules\Crm\Surface::assetName( $a, 'crm.css' ), 'GET', [ 'REMOTE_ADDR' => '127.0.0.1' ] );
	\Nino\Http::response( $a, $request );
	check( 'and the request of a file warns of nothing', ninoWarnings() === [] );

	// The same name again: the first stays
	$a = srfRequest( $where );
	check( 'a script that is added twice is on the list once: the second with the same file says false and changes nothing, and the first is the one served', srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'add', $a, 'twice.js', $ok ) === true && srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'add', $a, 'twice.js', $probe. '/assets/probe.css' ) === false
		&& count( \Nino\Modules\Crm\Surface\Scripts::entries( $a ) ) === 1 && \Nino\Modules\Crm\Surface\Scripts::file( $a, 'twice.js' ) === realpath( $ok ) );
	check( 'a file that was good when it was added and is not any more is served no more: the link is empty and the name is no file', ( static function() use ( &$a, $probe ): bool {

		file_put_contents( $probe. '/assets/gone.js', 'x' );
		srfVia( \Nino\Modules\Crm\Surface\Scripts::class, 'add', $a, 'gone.js', $probe. '/assets/gone.js' );
		$before = \Nino\Modules\Crm\Surface\Scripts::assetName( $a, 'gone.js' ) !== '';
		unlink( $probe. '/assets/gone.js' );
		symlink( $features. '/Crm/Surface/Surface.php', $probe. '/assets/gone.js' );
		$after = \Nino\Modules\Crm\Surface\Scripts::assetName( $a, 'gone.js' ) === '' && \Nino\Modules\Crm\Surface\Scripts::file( $a, 'gone.js' ) === '' && str_contains( \Nino\Modules\Crm\Surface\Scripts::markup( $a, '' ), 'gone.js' ) === false;
		unlink( $probe. '/assets/gone.js' );

		return $before === true && $after === true;
	} )() );

	// Words
	$a = srfRequest( $where );
	mkdir( $probe. '/text-conflict' );
	file_put_contents( $probe. '/text-conflict/de_DE.php', "<?php\nreturn [ 'nav.overview' => 'taken', 'fresh.word' => 'fresh' ];\n" );
	file_put_contents( $probe. '/text-conflict/en_US.php', "<?php\nreturn [ 'nav.overview' => 'taken', 'fresh.word' => 'fresh' ];\n" );
	mkdir( $probe. '/private/text' );
	$words = [
		'a path that is not absolute' => 'text',
		'nothing' => '',
		'a path that is nothing' => $probe. '/nothing',
		'a file' => $probe. '/feature.php',
		'a path with a null byte' => $probe. "/text\0",
		'a directory outside the features' => $where. '/private',
		'a directory of the temporary files' => sys_get_temp_dir(),
		'the directory of the words of the core' => $features. '/Crm/Surface/text',
		'the directory of another package' => $features. '/OtherPackage/text',
		'the directory of the package whose folder begins with the name of this one' => $features. '/CrmSurfaceProbeTwo/text',
		'a directory below a directory named private' => $probe. '/private',
		'a directory named data' => $probe. '/data',
		'a path that climbs' => $probe. '/text/../../../../../../',
	];
	$refused = [];
	ninoWarnings();

	foreach( $words as $what => $directory )
		$refused[$what] = srfVia( \Nino\Modules\Crm\Surface::class, 'addTexts', $a, $directory );

	check( 'a directory of words is refused where it is not an absolute path to a directory in the folder of the package that adds it that is not below a directory named private or data: '. count( $words ). ' of them, each with a warning', array_filter( $refused ) === [] && count( ninoWarnings() ) === count( $words ) );
	check( 'and one that is good is on the list once, however often it is added', srfVia( \Nino\Modules\Crm\Surface::class, 'addTexts', $a, $probe. '/text' ) === true && srfVia( \Nino\Modules\Crm\Surface::class, 'addTexts', $a, $probe. '/text/' ) === true && srfVia( \Nino\Modules\Crm\Surface::class, 'addTexts', $a, $probe. '/text/../text' ) === true && ninoWarnings() === [] );
	$a = srfRequest( $where );
	$de = (array) require $source. '/Surface/text/de_DE.php';
	srfVia( \Nino\Modules\Crm\Surface::class, 'addTexts', $a, $probe. '/text-conflict' );
	$all = \Nino\Modules\Crm\Surface\Texts::all( $a );
	$all2 = \Nino\Modules\Crm\Surface\Texts::all( $a );
	$warned = ninoWarnings();
	check( 'a file of words that has a key the surface has is refused as a whole, with one warning for the request: it adds nothing, not even its own keys, and never overwrites a sentence', $all['nav.overview'] === $de['nav.overview'] && isset( $all['fresh.word'] ) === false && $all2 === $all && count( $warned ) === 1 && str_contains( $warned[0], 'nav.overview' ) === true );
	check( 'a word that is not there is its key', \Nino\Modules\Crm\Surface\Texts::word( $a, 'no.such.word' ) === 'no.such.word' && \Nino\Modules\Crm\Surface\Texts::word( $a, 'nav.overview' ) === $de['nav.overview'] );

	// Data of a package
	$a = srfRequest( $where );
	$source1 = [ \Nino\Modules\CrmSurfaceProbe::class, 'pageData' ];
	$mine = [ 'package' => 'crm-surface-probe' ];
	$data = [
		'no package' => [ $source1, [] ],
		'the package of the core' => [ $source1, [ 'package' => 'crm' ] ],
		'a package that is no feature' => [ $source1, [ 'package' => 'crm-nothing' ] ],
		'a package with a capital' => [ $source1, [ 'package' => 'Probe' ] ],
		'a package that is a number' => [ $source1, [ 'package' => 7 ] ],
		'a package that is a list' => [ $source1, [ 'package' => [ 'crm-surface-probe' ] ] ],
		'a first word of the core as the package' => [ $source1, [ 'package' => 'elements' ] ],
		'a field nobody knows' => [ $source1, $mine + [ 'wide' => true ] ],
		'a list of one' => [ [ \Nino\Modules\CrmSurfaceProbe::class ], $mine ],
		'a method that is none' => [ [ \Nino\Modules\CrmSurfaceProbe::class, 'nothing' ], $mine ],
		'a class that is none' => [ [ 'Nino\\Nothing', 'pageData' ], $mine ],
	];
	$refused = [];
	ninoWarnings();

	foreach( $data as $what => [ $source2, $opts ] )
		$refused[$what] = \Nino\Modules\Crm\Surface\Data::add( $a, $source2, $opts );

	check( 'the data of a package are refused for a package that is none (none named, the core, no feature, a capital, a number, a list, a first word of the core) and for a source that is no [ class, method ] that can be called: '. count( $data ). ' of them', array_filter( $refused ) === [] && \Nino\Modules\Crm\Surface\Data::all( $a ) === [] && count( ninoWarnings() ) === count( $data ) );
	check( 'the same entry again is as good as the first, another source for the package is refused, and a method that answers no list is left out of the page with a warning that says which', ( static function() use ( &$a, $source1, $mine ): bool {

		$one = \Nino\Modules\Crm\Surface\Data::add( $a, $source1, $mine ) === true && \Nino\Modules\Crm\Surface\Data::add( $a, $source1, $mine ) === true;
		$taken = \Nino\Modules\Crm\Surface\Data::add( $a, [ \Nino\Modules\CrmSurfaceProbe::class, 'appValues' ], $mine ) === false;
		$warned = ninoWarnings();
		$b = srfRequest( $GLOBALS['where'] );
		$none = \Nino\Modules\Crm\Surface\Data::add( $b, [ \Nino\Modules\CrmSurfaceProbe::class, 'init' ], $mine ) === true;
		$all = \Nino\Modules\Crm\Surface\Data::all( $b );
		$left = ninoWarnings();

		return $one === true && $taken === true && count( $warned ) === 1 && $none === true && $all === [] && count( $left ) === 1 && str_contains( $left[0], 'crm-surface-probe' ) === true && array_keys( \Nino\Modules\Crm\Surface\Data::all( $a ) ) === [ 'crm-surface-probe' ];
	} )() );

	// The rail
	$a = srfRequest( $where );
	$menu = [
		'a route outside /crm' => [ '/other', 'W', 'k1' ],
		'the route /crm' => [ '/crm', 'W', 'k2' ],
		'a route with a capital' => [ '/crm/Probe', 'W', 'k3' ],
		'a route with a slash at the end' => [ '/crm/probe/', 'W', 'k4' ],
		'a route with two slashes' => [ '/crm//probe', 'W', 'k5' ],
		'a route that climbs' => [ '/crm/../_admin', 'W', 'k6' ],
		'a route with a null byte' => [ "/crm/x\0", 'W', 'k7' ],
		'a route with a space' => [ '/crm/a b', 'W', 'k8' ],
		'a route with a query' => [ '/crm/a?x=1', 'W', 'k9' ],
		'a route with a hash' => [ '/crm/a#x', 'W', 'ka' ],
		'no word' => [ '/crm/kb', '', 'kb' ],
		'a word of blanks' => [ '/crm/kc', "  \t", 'kc' ],
		'a key with a capital' => [ '/crm/kd', 'W', 'Kd' ],
		'no key' => [ '/crm/ke', 'W', '' ],
		'a key of 41 characters' => [ '/crm/kf', 'W', str_repeat( 'k', 41 ) ],
		'a key with a space' => [ '/crm/kg', 'W', 'k g' ],
		'the page of the tasks, which is the core\'s' => [ '/crm/todo', 'W', 'ki' ],
		'a page below the page of the tasks' => [ '/crm/todo/x', 'W', 'kj' ],
		'the key of an entry of the core' => [ '/crm/kh', 'W', 'overview' ],
	];
	$refused = [];
	ninoWarnings();

	foreach( $menu as $what => [ $route, $word, $key ] )
		$refused[$what] = \Nino\Modules\Crm\Surface\Menu::add( $a, $route, $word, $key, [ 'package' => 'crm-surface-probe' ] );

	$warned = ninoWarnings();
	check( 'an entry of the rail is refused for a route that is none (outside /crm, the page itself, capitals, slashes, a climb, a null byte, a space, a query, a hash), for no word or one of blanks, for a key that is none and for the key of an entry of the core: '. count( $menu ). ' of them, each with one warning that says why and names neither the route, the word nor the key', array_filter( $refused ) === [] && \Nino\Modules\Crm\Surface\Menu::entries( $a ) === [] && count( $warned ) === count( $menu ) && str_contains( end( $warned ), 'key of the core' ) === true
		&& array_filter( $warned, static fn( string $line ): bool => str_contains( $line, '/crm' ) === true || str_contains( $line, 'overview' ) === true ) === [] );
	ninoWarnings();
	check( 'an entry that is good is on the list; the same route or the same key again is refused, each with one warning, and the first stays; a key of digits is a key, and so is "0"', \Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/one', 'One', 'one', [ 'package' => 'crm-surface-probe' ] ) === true && ninoWarnings() === [] && \Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/one', 'Two', 'two', [ 'package' => 'crm-surface-probe' ] ) === false && count( ninoWarnings() ) === 1 && \Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/two', 'Two', 'one', [ 'package' => 'crm-surface-probe' ] ) === false && count( ninoWarnings() ) === 1
		&& \Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/n1', 'N', '0', [ 'package' => 'crm-surface-probe' ] ) === true && \Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/n2', 'N', '123', [ 'package' => 'crm-surface-probe' ] ) === true && array_column( \Nino\Modules\Crm\Surface\Menu::items( $a, '' ), 'key' ) === [ 'one', '0', '123' ]
		&& array_filter( array_column( \Nino\Modules\Crm\Surface\Menu::items( $a, '' ), 'key' ), 'is_string' ) === [ 'one', '0', '123' ] && \Nino\Modules\Crm\Surface\Menu::items( $a, '/sub' )[0]['href'] === '/sub/crm/one' );
	check( 'the rail is made in one pass: a word that holds a token of the markup is a word, and the entries of the core stand in their rank beside those of the features', ( static function() use ( &$a ): bool {

		\Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/tok', '[[key]] [[word]] "x"', 'tok', [ 'package' => 'crm-surface-probe' ] );
		$markup = html_entity_decode( \Nino\Modules\Crm\Surface\Menu::markup( $a, '', [ 'nav.overview' => 'O [[href]]', 'nav.clients' => 'C', 'nav.projects' => 'P', 'nav.settings' => 'S', 'nav.values' => 'V' ] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return str_contains( $markup, '>[[key]] [[word]] "x"</a>' ) === true && str_contains( $markup, '>O [[href]]</a>' ) === true && preg_match_all( '/id="nav-([a-z0-9-]+)"/', $markup, $ids ) === 9 && array_slice( $ids[1], 0, 3 ) === [ 'overview', 'clients', 'projects' ] && end( $ids[1] ) === 'values';
	} )() );

	// Devices
	$a = srfRequest( $where );
	$routes = [ '/crm', '/crm/', '/crm/A', '/crm//x', '/crm/x/', '/crm/../x', 'crm/x', '/x', '', "/crm/x\0", '/crm/x y', '/crm/x?a=1', '/crm/-', '/crm/x_y' ];
	$before = \Nino\Modules\Crm\Surface\Devices::pages( $a );
	ninoWarnings();
	check( 'a page for the devices is refused where its address is none: '. count( $routes ). ' addresses, each with one warning, and the list is as it was', array_filter( array_map( static fn( string $route ): bool => \Nino\Modules\Crm\Surface\Devices::allow( $a, $route, [ 'package' => 'crm-surface-probe' ] ), $routes ) ) === [] && \Nino\Modules\Crm\Surface\Devices::pages( $a ) === $before && count( ninoWarnings() ) === count( $routes ) );
	$enter = static function( string $route ) use ( &$a ): bool {

		return \Nino\Modules\Crm\Surface\Devices::claim( $a, $route, 'crm-surface-probe' ) === true && srfVia( \Nino\Modules\Crm\Surface\Devices::class, 'allow', $a, $route ) === true;
	};
	check( 'a good one is on the list once, however often it is told; a name of digits is one, and the page of the surface and of its second app are on it from the start', $enter( '/crm/files' ) === true && $enter( '/crm/files' ) === true && $enter( '/crm/2024' ) === true && $enter( '/crm/a/b-c' ) === true
		&& \Nino\Modules\Crm\Surface\Devices::pages( $a ) === [ '/crm', '/crm/todo', '/crm/files', '/crm/2024', '/crm/a/b-c' ] && \Nino\Modules\Crm\Surface\Devices::isRoute( '/crm/a' ) === true && \Nino\Modules\Crm\Surface\Devices::isRoute( '/crm' ) === false );
	check( 'the devices come back to a page the calling package entered itself and to none other: a page nobody entered, a page of the package of the suite asked for by another caller, and the routes of the surface (its endpoint, its files, its icons, its manifest) are refused, and the list is as it was',
		srfVia( \Nino\Modules\Crm\Surface\Devices::class, 'allow', $a, '/crm/never-entered' ) === false && \Nino\Modules\Crm\Surface\Devices::allow( $a, '/crm/files' ) === false && \Nino\Modules\Crm\Surface\Devices::allow( $a, '/crm/files', [ 'package' => 'crm-surface-probe-two' ] ) === false && \Nino\Modules\Crm\Surface\Devices::allow( $a, '/crm/2024', [ 'package' => 'crm-surface-probe-two' ] ) === false && \Nino\Modules\Crm\Surface\Devices::allow( $a, '/crm/todo', [ 'package' => 'crm-surface-probe' ] ) === false
		&& array_filter( [ '/crm/api', '/crm/assets/x', '/crm/icon', '/crm/icon-192/x', '/crm/manifest' ], static fn( string $route ): bool => \Nino\Modules\Crm\Surface\Devices::isRoute( $route ) === true ) === []
		&& \Nino\Modules\Crm\Surface\Devices::pages( $a ) === [ '/crm', '/crm/todo', '/crm/files', '/crm/2024', '/crm/a/b-c' ] );

	// Every place of the surface takes the package that calls and refuses what is none: no package, the core, a first word of the core, a feature that is not there and a number
	$calls = [
		'texts' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Texts::add( $r, $GLOBALS['probe']. '/text', $o ),
		'texts through the surface' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface::addTexts( $r, $GLOBALS['probe']. '/text', $o ),
		'a script' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Scripts::add( $r, 'uni.js', $GLOBALS['probe']. '/assets/probe.js', $o ),
		'a style sheet' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Scripts::addStyle( $r, 'uni.css', $GLOBALS['probe']. '/assets/probe.css', $o ),
		'an entry of the rail' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Menu::add( $r, '/crm/uni', 'Uni', 'uni', $o ),
		'data of the page' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Data::add( $r, [ \Nino\Modules\CrmSurfaceProbe::class, 'pageData' ], $o ),
		'an icon' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Icons::add( $r, $o + [ 'key' => 'uniapp', 'prefix' => 'uni-', 'slot' => '/icon/crm-uni', 'route' => '/crm/uniapp/icon', 'letters' => 'UNI' ] ),
		'an app' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Surface\Apps::add( $r, $o + [ 'key' => 'uniapp', 'route' => '/crm/uniapp', 'scope' => '/crm/uniapp', 'manifest' => '/crm/uniapp/manifest.webmanifest', 'values' => [ \Nino\Modules\CrmSurfaceProbe::class, 'appValues' ], 'icon' => 'uniapp' ] ),
	];
	$GLOBALS['probe'] = $probe;
	$bad = [ 'none' => [], 'the core' => [ 'package' => 'crm' ], 'a first word of the core' => [ 'package' => 'elements' ], 'no feature' => [ 'package' => 'crm-nothing' ], 'a number' => [ 'package' => 7 ] ];
	$wrong = [];

	foreach( $calls as $place => $call ) {

		$b = srfRequest( $where );

		foreach( $bad as $what => $options ) {
			ninoWarnings();

			if( $call( $b, $options ) !== false )
				$wrong[] = $place. ' / '. $what;
		}

		ninoWarnings();

		if( $call( $b, [ 'package' => 'crm-surface-probe' ] ) !== true )
			$wrong[] = $place. ' / the package that is on';
	}

	check( 'every place of the surface - words, a script, a style sheet, an entry of the rail, data, an icon, an app - takes the package that calls and refuses what is none: no package, the core, a first word of the core, a feature that is not there and a number are each refused, and the package that is on is let in', $wrong === [] );
	$b = srfRequest( $where );
	check( 'and the second package, which is on as well, is no way into the first one\'s folder or its pages: its words, its script, and its entry of a page the first has entered are refused', ( static function() use ( &$b, $probe, $features ): bool {

		$two = [ 'package' => 'crm-surface-probe-two' ];
		$one = \Nino\Modules\Crm\Surface\Menu::add( $b, '/crm/shared', 'Shared', 'shared', [ 'package' => 'crm-surface-probe' ] ) === true;

		return $one === true && \Nino\Modules\Crm\Surface\Texts::add( $b, $probe. '/text', $two ) === false && \Nino\Modules\Crm\Surface\Scripts::add( $b, 'two.js', $probe. '/assets/probe.js', $two ) === false && \Nino\Modules\Crm\Surface\Scripts::add( $b, 'two.js', $features. '/CrmSurfaceProbeTwo/assets/two.js', $two ) === true
			&& \Nino\Modules\Crm\Surface\Menu::add( $b, '/crm/shared', 'Mine', 'mine', $two ) === false && \Nino\Modules\Crm\Surface\Devices::allow( $b, '/crm/shared', $two ) === false && \Nino\Modules\Crm\Surface\Devices::allow( $b, '/crm/shared', [ 'package' => 'crm-surface-probe' ] ) === true;
	} )() );

	check( 'an entry of the rail for the route of an app of another package, and the devices let in for a page of another package, are refused each with one warning, and the page stays the first package\'s', ( static function() use ( $where ): bool {

		$b = srfRequest( $where );
		$two = [ 'package' => 'crm-surface-probe-two' ];
		$entered = \Nino\Modules\Crm\Surface\Apps::add( $b, [ 'package' => 'crm-surface-probe', 'key' => 'claimed', 'route' => '/crm/claimed', 'scope' => '/crm/claimed', 'manifest' => '/crm/claimed/manifest.webmanifest', 'values' => [ \Nino\Modules\CrmSurfaceProbe::class, 'appValues' ], 'icon' => 'claimed' ] );
		ninoWarnings();
		$menu = \Nino\Modules\Crm\Surface\Menu::add( $b, '/crm/claimed', 'Mine', 'mine', $two );
		$menuWarnings = count( ninoWarnings() );
		$devices = \Nino\Modules\Crm\Surface\Devices::allow( $b, '/crm/claimed', $two );
		$deviceWarnings = count( ninoWarnings() );

		return $entered === true && $menu === false && $menuWarnings === 1 && $devices === false && $deviceWarnings === 1 && in_array( 'mine', array_column( \Nino\Modules\Crm\Surface\Menu::items( $b, '' ), 'key' ), true ) === false;
	} )() );

	// Apps and their icons
	$a = srfRequest( $where );
	$app = [ 'package' => 'crm-surface-probe', 'key' => 'filesapp', 'route' => '/crm/filesapp', 'scope' => '/crm/filesapp', 'manifest' => '/crm/filesapp/manifest.webmanifest', 'values' => [ \Nino\Modules\CrmSurfaceProbe::class, 'appValues' ], 'icon' => 'filesapp' ];
	$bad = [
		'a key with a capital' => [ 'key' => 'Files' ] + $app,
		'a key with a digit' => [ 'key' => 'f1' ] + $app,
		'a key of 21 characters' => [ 'key' => str_repeat( 'f', 21 ) ] + $app,
		'a key of the core' => [ 'key' => 'todo' ] + $app,
		'a scope of another page' => [ 'scope' => '/crm/probe' ] + $app,
		'a manifest of another app' => [ 'manifest' => '/crm/todo/manifest.webmanifest' ] + $app,
		'no package' => array_diff_key( $app, [ 'package' => 1 ] ),
		'the core as the package' => [ 'package' => 'crm' ] + $app,
		'a package that is only installed' => [ 'package' => 'crm-surface-probe-off' ] + $app,
		'a route outside /crm' => [ 'route' => '/files' ] + $app,
		'a route that is the one of another app' => [ 'route' => '/crm/todo' ] + $app,
		'a scope that is none' => [ 'scope' => '/crm/' ] + $app,
		'a manifest outside /crm' => [ 'manifest' => '/files/manifest.webmanifest' ] + $app,
		'a manifest that climbs' => [ 'manifest' => '/crm/../x' ] + $app,
		'values that are a closure' => [ 'values' => static fn( array &$appData, string $dir ): array => [] ] + $app,
		'values that are none' => [ 'values' => [ 'Nino\\Nothing', 'x' ] ] + $app,
		'an icon with a digit' => [ 'icon' => 'i1' ] + $app,
		'a field nobody knows' => $app + [ 'wide' => true ],
		'no icon' => array_diff_key( $app, [ 'icon' => 1 ] ),
	];
	$refused = [];
	ninoWarnings();

	foreach( $bad as $what => $spec )
		$refused[$what] = \Nino\Modules\Crm\Surface\Apps::add( $a, $spec );

	check( 'an app is refused for a key that is none (a capital, a digit, a length, the key of the core), a route, a scope or a manifest that is none or the route of another app, values or an icon that are none, a field nobody knows, and for a field that is missing: '. count( $bad ). ' of them', array_filter( $refused ) === [] && array_keys( \Nino\Modules\Crm\Surface\Apps::all( $a ) ) === [ 'todo' ] && count( ninoWarnings() ) === count( $bad ) );
	check( 'an app that is good is entered with its route told to the devices; the same description again is as good as the first; another key for its route is refused', \Nino\Modules\Crm\Surface\Apps::add( $a, $app ) === true && \Nino\Modules\Crm\Surface\Apps::add( $a, $app ) === true && \Nino\Modules\Crm\Surface\Apps::get( $a, 'filesapp' )['route'] === '/crm/filesapp'
		&& \Nino\Modules\Crm\Surface\Apps::add( $a, [ 'key' => 'other' ] + $app ) === false && in_array( '/crm/filesapp', \Nino\Modules\Crm\Surface\Devices::pages( $a ), true ) === true && \Nino\Modules\Crm\Surface\Apps::get( $a, 'nothing' ) === null && \Nino\Modules\Crm\Surface\Apps::get( $a, '0' ) === null );
	ninoWarnings();

	$icon = [ 'package' => 'crm-surface-probe', 'key' => 'filesapp', 'prefix' => 'files-', 'slot' => '/icon/crm-files', 'route' => '/crm/filesapp/icon', 'letters' => 'FIL' ];
	$bad = [
		'a key of the core' => [ 'key' => 'crm' ] + $icon,
		'a key with a digit' => [ 'key' => 'a1' ] + $icon,
		'a prefix with a capital' => [ 'prefix' => 'Files-' ] + $icon,
		'a prefix of a core app' => [ 'prefix' => 'todo-' ] + $icon,
		'a slot that is none' => [ 'slot' => '/icons/x' ] + $icon,
		'the slot of a core app' => [ 'slot' => '/icon/crm-tasks' ] + $icon,
		'a route that climbs' => [ 'route' => '/crm/../x' ] + $icon,
		'the route of a core app' => [ 'route' => '/crm/icon' ] + $icon,
		'letters of four characters' => [ 'letters' => 'ABCD' ] + $icon,
		'no letters and no shape' => [ 'letters' => '' ] + $icon,
		'a shape of two points' => [ 'letters' => '', 'shape' => [ [ 1, 1 ], [ 2, 2 ] ] ] + $icon,
		'a shape with a point that is no number' => [ 'letters' => '', 'shape' => [ [ 1, 1 ], [ 2, 2 ], [ 'x', 3 ] ] ] + $icon,
		'a keep that is none' => [ 'keep' => [ 'Files' ] ] + $icon,
	];
	$refused = [];
	ninoWarnings();

	foreach( $bad as $what => $spec )
		$refused[$what] = \Nino\Modules\Crm\Surface\Icons::add( $a, $spec );

	check( 'the icon of an app is refused for a key of the core, a key, a prefix, a slot or a route that is none or that an app has, letters or a shape that are none and a keep that is none: '. count( $bad ). ' of them, each with a warning', array_filter( $refused ) === [] && count( ninoWarnings() ) === count( $bad ) );
	check( 'the routes of the icon of an app are answered by a class of the package that entered the app, and by none other: the class of the core, of another package and of the kernel are refused with a warning', ( static function() use ( &$a, $icon ): bool {

		\Nino\Modules\Crm\Surface\Icons::add( $a, $icon );
		ninoWarnings();
		$refused = array_filter( [ \Nino\Modules\Crm\Surface::class, \Nino\Modules\Crm\Surface\Icons::class, \Nino\Modules\CrmSurfaceProbeTwo::class, \Nino\Auth::class ], static fn( string $class ): bool => \Nino\Modules\Crm\Surface\Icons::registerIcons( $a, 'filesapp', $class ) !== false );

		return $refused === [] && count( ninoWarnings() ) === 4;
	} )() );
	check( 'a good one is entered, the same again is as good as the first, and the routes of its icon are the ones it names', \Nino\Modules\Crm\Surface\Icons::add( $a, $icon ) === true && \Nino\Modules\Crm\Surface\Icons::add( $a, $icon ) === true && \Nino\Modules\Crm\Surface\Icons::add( $a, [ 'key' => 'gone' ] + $icon ) === false && ninoWarnings() !== [] );
}
catch( \Throwable $e ) {
	srfCrashed( 'what the places of the surface refuse', $e );
}

/*	A package that is off, and one that was removed: the surface is the surface of the core alone	*/
try {

	$core = [ 'menu' => [], 'packages' => [], 'allowed' => [ '/crm', '/crm/todo' ], 'apps' => [ 'todo' ] ];
	[ $where, $S ] = srfWorld( 'probe-off', 'off' );
	$crm = $S['crm@example.org'];
	$a = srfRequest( $where );
	[ $status, $body ] = srfGet( $where, '/crm', $crm );
	$data = srfData( $body );
	$de = (array) require $source. '/Surface/text/de_DE.php';
	$tags = preg_match_all( '~<(?:script src|link rel="stylesheet" href)="[^"]*"~', (string) $body );

	check( 'with the package off nothing of it is in the page: no entry of the rail, no data, no file linked but those of the core, no word of its own', $data['menu'] === $core['menu'] && $data['packages'] === $core['packages'] && $tags === 6 && isset( $data['texts']['probe.title'] ) === false && str_contains( (string) $body, 'crm-surface-probe' ) === false && str_contains( (string) $body, '/crm/probe' ) === false );
	check( 'the pages and the apps the devices come back to and the list of the apps are the two of the core', \Nino\Modules\Crm\Surface\Devices::pages( $a ) === $core['allowed'] && array_keys( \Nino\Modules\Crm\Surface\Apps::all( $a ) ) === $core['apps'] && \Nino\Modules\Crm\Surface\Scripts::entries( $a ) === [] && \Nino\Modules\Crm\Surface\Menu::entries( $a ) === [] && \Nino\Modules\Crm\Surface\Data::all( $a ) === []
		&& isset( $a['./crm-surface-probe/answers'] ) === false );
	[ $page ] = srfGet( $where, '/crm/probe', $crm );
	[ $app ] = srfGet( $where, '/crm/probeapp', $crm );
	[ $icon ] = srfGet( $where, '/crm/probeapp/icon.svg' );
	check( 'the pages, the app and the icon of the package are 404', $page === 404 && $app === 404 && $icon === 404 );
	$name = substr( (string) hash_file( 'sha256', $features. '/CrmSurfaceProbe/assets/probe.js' ), 0, 12 ). '-probe.js';
	[ $file ] = srfGet( $where, '/crm/assets/'. $name );
	check( 'and so is the file of the package, whatever the hash', $file === 404 );

	// Removed: a process of its own that has no folder of the package, over the project that had it on
	[ $where2, $S2 ] = srfWorld( 'probe-removed', 'removed' );
	$features2 = sys_get_temp_dir(). '/nino-crm-surface-removed-'. bin2hex( random_bytes( 6 ) );
	mkdir( $features2. '/Crm', 0755, true );
	srfCopy( $features. '/Crm', $features2. '/Crm' );
	$GLOBALS['srfSandboxes'][] = $features2;
	$file = $where2. '/session.json';
	file_put_contents( $file, json_encode( $S2['crm@example.org'] ) );
	$process = proc_open( [ PHP_BINARY, __FILE__, 'removed-worker', $features2, $where2, $file ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, null, [ 'NINO_ROOT' => $root ] + $_ENV + [ 'PATH' => (string) getenv( 'PATH' ) ] );
	$out = (string) stream_get_contents( $pipes[1] );
	$err = (string) stream_get_contents( $pipes[2] );
	proc_close( $process );
	$seen = (array) json_decode( $out, true );

	check( 'with the folder of the package gone from a project that had it on, the surface is the surface of the core alone and answers: the page, no entry, no data, no file of the package, no page of it, no app, and no warning but, where something asks which features there are, the sentence of the kernel that the folder is gone', ( $seen['kind'] ?? '' ) === 'page' && ( $seen['menu'] ?? null ) === [] && ( $seen['packages'] ?? null ) === [] && ( $seen['scripts'] ?? 0 ) === 6 && ( $seen['probe'] ?? 0 ) === 404 && ( $seen['app'] ?? 0 ) === 404
		&& ( $seen['allowed'] ?? null ) === $core['allowed'] && ( $seen['apps'] ?? null ) === $core['apps'] && array_filter( (array) ( $seen['warnings'] ?? [ 'x' ] ), static fn( mixed $warning ): bool => str_contains( (string) $warning, 'CrmSurfaceProbe is switched on but its directory' ) === false || str_contains( (string) $warning, 'nothing of it is loaded' ) === false ) === [] && $err === '' );
}
catch( \Throwable $e ) {
	srfCrashed( 'a package that is off or removed', $e );
}

/*	The signatures a package relies on	*/
try {

	$signature = static function( string $class, string $method ): string {

		$reflection = new \ReflectionMethod( $class, $method );
		$parameters = array_map( static fn( \ReflectionParameter $parameter ): string => ( $parameter->getType() === null ? '' : (string) $parameter->getType(). ' ' ). ( $parameter->isPassedByReference() === true ? '&' : '' ). '$'. $parameter->getName(). ( $parameter->isDefaultValueAvailable() === true ? ' = '. str_replace( "array (\n)", '[]', var_export( $parameter->getDefaultValue(), true ) ) : '' ), $reflection->getParameters() );

		return ( $reflection->isStatic() === true ? 'static ' : '' ). $method. '( '. implode( ', ', $parameters ). ' ): '. $reflection->getReturnType();
	};
	$app = \Nino\Modules\Crm\Surface\App::class;
	$surface = \Nino\Modules\Crm\Surface::class;

	check( 'the signatures the packages call on the page are these: values( &$appData, $dir, $app = "crm" ): array, icons( &$appData, $dir, $app = "crm" ): array, json( $document ): string, trim( &$appData, $page, $app = "crm" ): string', $signature( $app, 'values' ) === "static values( array &\$appData, string \$dir, string \$app = 'crm' ): array"
		&& $signature( $app, 'icons' ) === "static icons( array &\$appData, string \$dir, string \$app = 'crm' ): array" && $signature( $app, 'json' ) === 'static json( array $document ): string' && $signature( $app, 'trim' ) === "static trim( array &\$appData, string \$page, string \$app = 'crm' ): string" );
	$css = (string) file_get_contents( $source. '/Surface/assets/crm.css' );
	preg_match( '/:root \{\s*--bg: (#[0-9a-f]{6}); --surface: (#[0-9a-f]{6});/', $css, $light );
	preg_match( '/:root\[data-theme="dark"\] \{\s*--bg: (#[0-9a-f]{6}); --surface: (#[0-9a-f]{6});/', $css, $dark );
	check( 'the colours of the surface a manifest carries are four, as numbers, and they are the ground and the surface of the style sheet, light and dark: a browser that reads the manifest before the style sheet paints what the style sheet will', array_keys( \Nino\Modules\Crm\Surface\App::COLOURS ) === [ 'page', 'bar', 'page-dark', 'bar-dark' ]
		&& array_filter( \Nino\Modules\Crm\Surface\App::COLOURS, static fn( string $colour ): bool => preg_match( '/^#[0-9a-f]{6}$/D', $colour ) !== 1 ) === [] && array_values( \Nino\Modules\Crm\Surface\App::COLOURS ) === [ $light[1] ?? '', $light[2] ?? '', $dark[1] ?? '', $dark[2] ?? '' ] );
	check( 'the signatures of the page are these: locale( &$appData ): string, assetName( &$appData, $name ): string, ROUTE is /crm, gate( &$appData, &$request, $page ): ?array, page( &$appData, &$request, $app ): void, addTexts( &$appData, $directory, $opts ): bool, plain( &$request, $status, $body ): void',
		$signature( $surface, 'locale' ) === 'static locale( array &$appData ): string' && $signature( $surface, 'assetName' ) === 'static assetName( array &$appData, string $name ): string' && \Nino\Modules\Crm\Surface::ROUTE === '/crm'
		&& $signature( $surface, 'gate' ) === 'static gate( array &$appData, array &$request, array $page ): ?array' && $signature( $surface, 'page' ) === 'static page( array &$appData, array &$request, string $app ): void' && $signature( $surface, 'addTexts' ) === 'static addTexts( array &$appData, string $directory, array $opts = [] ): bool'
		&& $signature( $surface, 'plain' ) === 'static plain( array &$request, int $status, string $body ): void' );
	check( 'the signatures of the places a package docks to are these, and each names the package that calls: the option package of the last argument ($opts) where the call takes plain arguments, the field package of the description where it takes one - Scripts::add/addStyle( &$appData, $name, $file, $opts ): bool, Menu::add( &$appData, $route, $word, $key, $opts ): bool, Data::add( &$appData, $source, $opts ): bool, Devices::allow( &$appData, $route, $opts ): bool, Apps::add( &$appData, $spec ): bool, Icons::add( &$appData, $spec ): bool, Icons::registerIcons( &$appData, $app, $class = null ): bool',
		$signature( \Nino\Modules\Crm\Surface\Scripts::class, 'add' ) === 'static add( array &$appData, string $name, string $file, array $opts = [] ): bool' && $signature( \Nino\Modules\Crm\Surface\Scripts::class, 'addStyle' ) === 'static addStyle( array &$appData, string $name, string $file, array $opts = [] ): bool'
		&& $signature( \Nino\Modules\Crm\Surface\Menu::class, 'add' ) === 'static add( array &$appData, string $route, string $word, string $key, array $opts = [] ): bool' && $signature( \Nino\Modules\Crm\Surface\Data::class, 'add' ) === 'static add( array &$appData, array $source, array $opts = [] ): bool'
		&& $signature( \Nino\Modules\Crm\Surface\Texts::class, 'add' ) === 'static add( array &$appData, string $directory, array $opts = [] ): bool'
		&& $signature( \Nino\Modules\Crm\Surface\Devices::class, 'allow' ) === 'static allow( array &$appData, string $route, array $opts = [] ): bool' && $signature( \Nino\Modules\Crm\Surface\Apps::class, 'add' ) === 'static add( array &$appData, array $spec ): bool'
		&& $signature( \Nino\Modules\Crm\Surface\Icons::class, 'add' ) === 'static add( array &$appData, array $spec ): bool' && $signature( \Nino\Modules\Crm\Surface\Icons::class, 'registerIcons' ) === "static registerIcons( array &\$appData, string \$app, ?string \$class = NULL ): bool" );
	check( 'the three actions of the devices are the ones the script posts to, in the endpoint under the names crm/device-remember, crm/devices-forget and crm/signout', array_keys( \Nino\Modules\Crm\Surface::actions() ) === [ 'crm/device-remember', 'crm/devices-forget', 'crm/signout' ] && array_keys( \Nino\Modules\Crm\Surface\Devices::actions() ) === array_keys( \Nino\Modules\Crm\Surface::actions() ) );
	check( 'the limits of the devices are ten devices, ninety days, a grace of thirty seconds and twenty tries in an hour, and the cookie is crm_remember', \Nino\Modules\Crm\Surface\Devices::MAX === 10 && \Nino\Modules\Crm\Surface\Devices::DAYS === 90 && \Nino\Modules\Crm\Surface\Devices::GRACE === 30 && \Nino\Modules\Crm\Surface\Devices::TRIES === 20 && \Nino\Modules\Crm\Surface\Devices::WINDOW === 3600 && \Nino\Modules\Crm\Surface\Devices::COOKIE === 'crm_remember' );
}
catch( \Throwable $e ) {
	srfCrashed( 'the signatures', $e );
}

// the end of this part of the suite is written below
foreach( (array) ( $GLOBALS['srfSandboxes'] ?? [] ) as $folder )
	srfRemove( (string) $folder );

srfRemove( $features );

$done = []; ninoDone( $done );
