<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-domain-smoke.php		The test of the second part of the feature Crm, what the core does with clients, projects, tasks, the logbook and the evidence, and the endpoint it answers them at: every request goes
 *													the way a request of the site goes - the route, the guard of the token, the session, the permission - and ends in the method of an action; no action is called on its own. The endpoint (no session,
 *													no permission, the permission of another panel, no token, a body that is none, an action nobody entered, a post for a package that is off), the actions of the clients, the projects, the tasks,
 *													the logbook, the settings, the overview, the notifications, the texts of the company, the values and the test phase - each with the ids and the texts a stranger may send (no id, a text, a number,
 *													a list, a path, a null byte, a length no id has) and the injection through every text field -, the records and the logbook of a package, the deletion of a client (what hangs on it before and after,
 *													a step that fails and is finished by the next press, the same press twice, the data of a package that is gone), the test phase, numbers that eight processes take at once, two writers on one client and
 *													a reader while a writer holds, the places the second part offers to a package - the package of the suite, CrmProbe, enters at every one of them, on, off, removed and with the core off -, and that
 *													the code of the core names no package. One pin for each behaviour, over the structure and never the words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Crm/tests/crm-domain-smoke.php
 *	       NINO_ROOT=../nino php features/Crm/tests/crm-domain-smoke.php
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
function domCopy( string $from, string $to ): void {

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
function domRemove( string $folder ): void {

	if( is_dir( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

/*	The features directory of the suite: a folder of its own, made for the run, with a copy of the feature and the package of the suite (tests/fixtures/CrmProbe) - never the folder of the catalogue.
	The processes the suite starts are told it	*/
if( ( $argv[1] ?? '' ) !== '' && str_ends_with( (string) $argv[1], '-worker' ) === true )
	$features = (string) $argv[2];
else {
	$features = sys_get_temp_dir(). '/nino-crm-domain-features-'. bin2hex( random_bytes( 6 ) );
	mkdir( $features, 0755, true );
	mkdir( $features. '/Crm', 0755, true );

	foreach( [ 'Crm.php', 'feature.php' ] as $file )
		copy( $source. '/'. $file, $features. '/Crm/'. $file );

	foreach( array_diff( (array) scandir( $source ), [ '.', '..', 'tests' ] ) as $folder )
		if( is_dir( $source. '/'. $folder ) === true )
			domCopy( $source. '/'. $folder, $features. '/Crm/'. $folder );

	domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
	domCopy( $source. '/tests/fixtures/CrmProbeTwo', $features. '/CrmProbeTwo' );
}

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-domain-smoke.php names its own features directory: NINO_FEATURES_DIR is defined already\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

/**
 *	A request of a project that exists: the app data of a sandbox that is on the disk already, the way a request builds it - the directory, the languages, the modules the project lists, and what the
 *	modules register in their init()
 *
 *	@param		string		$sandbox							The directory of the project
 *	@param		array 		$set									What the request has before the modules are initialised: the switches of the package of the suite
 *
 *	@return 	array
 */
function domRequest( string $sandbox, array $set = [] ): array {

	$appData = [ './nino/uid' => $sandbox ];
	\Nino\AppData::prepare( $appData );
	$appData['./nino/filesystem/path']				= $sandbox;
	$appData['./nino/filesystem/configpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/contentpath']	= $sandbox. '/private';
	$appData['./nino/filesystem/publicpath']	= $sandbox. '/public';
	$appData['./nino/locales/current']				= 'de_DE';
	$appData['/nino/locales/native']					= 'de_DE';
	$appData['/nino/locales/available']				= [ 'de_DE', 'en_US' ];
	$appData['/nino/dir']											= '';
	$config																		= (array) \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
	$appData['/nino/modules']									= (array) ( $config['/nino/modules'] ?? [] );
	$appData['/nino/auth/user']								= (array) ( $config['/nino/auth/user'] ?? [] );
	$appData['./nino/test/sandbox']						= $sandbox;
	$appData									= $set + $appData;

	\Nino\Html::init( $appData );
	\Nino\Csrf::init( $appData );
	\Nino\Auth::init( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

/**
 *	A project with the core and the package of the suite switched on, the accounts of the suite and a request over it. The package of the suite enters at the places of the second part where it is told to
 *
 *	@param		string		$name
 *	@param		bool			$probe					Whether the package of the suite is switched on
 *
 *	@return 	array											The app data of the sandbox, the directory, and the app data of a request that initialised the modules
 */
function domWorld( string $name, bool $probe = true ): array {

	$project = ninoSandbox( $name );
	$GLOBALS['domSandboxes'][] = ninoSandboxDir( $project );
	\Nino\Features::activate( $project, 'crm' );

	if( $probe === true )
		\Nino\Features::activate( $project, 'crm-probe' );

	$where = ninoSandboxDir( $project );

	$request = domRequest( $where, ( $probe === true ? [ './crm-probe/domain' => true ] : [] ) + (array) ( $GLOBALS['domSet'] ?? [] ) );

	\Nino\Auth::insertUser( $request, 'admin@example.org', 'correct horse battery staple', [ '/*' ] );
	\Nino\Auth::insertUser( $request, 'plain@example.org', 'plain password', [] );
	\Nino\Auth::insertUser( $request, 'members@example.org', 'members password', [ '/_admin/members/manage', '/_admin/features/manage', '/_admin/text/manage' ] );
	\Nino\Auth::insertUser( $request, 'crm@example.org', 'crm password', [ '/_admin/crm/manage' ] );
	\Nino\Auth::insertUser( $request, 'scoped@example.org', 'scoped password', [ '/_admin/crm/manage', '/_admin/elements/manage', '/_admin/elements/crm-projects/update/title' ] );
	\Nino\Auth::insertUser( $request, 'texts@example.org', 'texts password', [ '/_admin/crm/manage', '/_admin/text/manage', '/_admin/text/update/project/company/general/name' ] );
	\Nino\Auth::insertUser( $request, 'elements@example.org', 'elements password', [ '/_admin/crm/manage', '/_admin/elements/manage' ] );

	// What the activation of the kernel says of its own files is not what a test of the core asks about
	ninoWarnings();

	return [ $project, $where, $request ];
}

/**
 *	A request that is signed in as the owner
 *
 *	@param		string		$sandbox
 *
 *	@return 	array
 */
function domSignedIn( string $sandbox ): array {

	$request = domRequest( $sandbox );
	domSignIn( $request, 'admin@example.org' );

	return $request;
}

/**
 *	Who is at the endpoint: nobody, or an account of the suite by its address
 *
 *	@param		array 		&$appData
 *	@param		string		$who					'' for nobody
 *
 *	@return 	void
 */
function domSignIn( array &$appData, string $who ): void {

	$passwords = [ 'admin@example.org' => 'correct horse battery staple', 'plain@example.org' => 'plain password', 'members@example.org' => 'members password', 'crm@example.org' => 'crm password',
		'scoped@example.org' => 'scoped password', 'texts@example.org' => 'texts password', 'elements@example.org' => 'elements password' ];

	$_SESSION = [];
	unset( $appData['./nino/auth/current'] );

	if( $who !== '' )
		\Nino\Auth::loginUser( $appData, $who, $passwords[$who] );
}

/**
 *	One post to the endpoint, the way the page makes it: the request of the site, the route, the guard of the token, the callbacks of the route - and what is read of the answer
 *
 *	@param		array 		&$appData
 *	@param		string		$action
 *	@param		mixed			$data					What is posted as the data of the action
 *	@param		array 		$o						token (null: the token of the session; '' none; or a text), host (the Host header), body (the body as it is, instead of the json of action and data), type (the Content-Type), fields (what php has read of a form), files
 *
 *	@return 	array											[ status, body of the answer, the request ]
 */
function domPost( array &$appData, string $action, mixed $data = [], array $o = [] ): array {

	$token = array_key_exists( 'token', $o ) === true ? (string) $o['token'] : \Nino\Csrf::getToken( $appData );
	$server = [ 'REMOTE_ADDR' => $o['ip'] ?? '127.0.0.1', 'CONTENT_TYPE' => $o['type'] ?? 'application/json' ] + ( isset( $o['host'] ) === true ? [ 'HTTP_HOST' => $o['host'] ] : [] );

	if( $token !== '' )
		$server['HTTP_X_CSRF_TOKEN'] = $token;

	$request = ninoVisit( $appData, '/crm/api', 'POST', $server );
	$request['/nino/http/request']['body'] = array_key_exists( 'body', $o ) === true ? $o['body'] : json_encode( [ 'action' => $action, 'data' => $data ] );

	$posted = $_POST;
	$files = $_FILES;
	$_POST = $o['fields'] ?? [];
	$_FILES = $o['files'] ?? [];

	try {
		\Nino\Http::response( $appData, $request );
	}
	finally {
		// What the endpoint left in $_POST, before this helper puts back what stood there: the pin on the restore reads it
		$GLOBALS['domPostLeft'] = $_POST;
		$_POST = $posted;
		$_FILES = $files;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request ];
}

/**
 *	The status and the code of a post, for a pin that asks nothing more
 *
 *	@param		array 		&$appData
 *	@param		string		$action
 *	@param		mixed			$data
 *	@param		array 		$o
 *
 *	@return 	string										'400 crm_clients_id' - the status and the code ('-' for none)
 */
function domAnswer( array &$appData, string $action, mixed $data = [], array $o = [] ): string {

	[ $status, $body ] = domPost( $appData, $action, $data, $o );

	return $status. ' '. ( is_array( $body ) === true && is_string( $body['code'] ?? null ) === true ? $body['code'] : '-' );
}

/**
 *	The body of a post that went through
 *
 *	@param		array 		&$appData
 *	@param		string		$action
 *	@param		mixed			$data
 *
 *	@return 	array											The body; [] for an answer that is none
 */
function domBody( array &$appData, string $action, mixed $data = [] ): array {

	[ $status, $body ] = domPost( $appData, $action, $data );

	return $status === 200 && is_array( $body ) === true ? $body : [];
}

/**
 *	The rows of a table of the database of the sandbox, for a pin that counts
 *
 *	@param		array 		&$appData
 *	@param		string		$table
 *
 *	@return 	int
 */
function domCount( array &$appData, string $table, string $where = '1' ): int {

	return (int) \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM '. $table. ' WHERE '. $where )['n'];
}

/**
 *	A client made through the endpoint
 *
 *	@param		array 		&$appData
 *	@param		array 		$fields
 *
 *	@return 	int												Its id; 0 where it was not made
 */
function domClient( array &$appData, array $fields = [ 'name' => 'Ada Example' ] ): int {

	return (int) ( domBody( $appData, 'crm-clients/save', $fields )['client']['id'] ?? 0 );
}

/**
 *	A project made through the endpoint, as the screen makes it: elements/save of the type
 *
 *	@param		array 		&$appData
 *	@param		string		$number				The client
 *	@param		string		$title
 *
 *	@return 	string										Its id; '' where it was not made
 */
function domProject( array &$appData, string $number, string $title = 'Album' ): string {

	$body = domBody( $appData, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => $title, 'client' => $number ] ] );

	return \Nino\Elements::getElementUriFromUri( (string) ( $body['element']['.uri'] ?? '' ) );
}

/**
 *	The first lines of the activity log
 *
 *	@param		array 		&$appData
 *	@param		int				$limit
 *
 *	@return 	array											The lines, newest first
 */
function domLog( array &$appData, int $limit = 5 ): array {

	// The lines the workbench writes by itself - the first sign-in of a session, the daily backup, the base texts - are not what a pin asks about
	$lines = array_map( static fn( mixed $line ): string => is_array( $line ) === true ? json_encode( $line ) : (string) $line, \Nino\Modules\Logs\Admin::recentLines( $appData, 500 ) );

	return array_slice( array_values( array_filter( $lines, static fn( string $line ): bool => preg_match( '/  (Login|Backup created|Add Base Texts \d+)$/', $line ) !== 1 ) ), 0, $limit );
}

/**
 *	The state of a file for a pin that says it was not touched
 *
 *	@param		string		$file
 *
 *	@return 	array
 */
function domFingerprint( string $file ): array {

	clearstatcache();

	return [ is_file( $file ) === true ? sha1_file( $file ) : null, is_file( $file ) === true ? filesize( $file ) : null ];
}

/**
 *	A section that stopped with an exception is one pin that fell, said with the section and the kind of the exception - no path, no word of what was in it - and the run goes on with the next
 *
 *	@param		string		$section
 *	@param		\Throwable	$error
 *
 *	@return 	void
 */
function domCrashed( string $section, \Throwable $error ): void {

	if( $error instanceof \Nino\Failure )
		throw $error;

	check( 'the section "'. $section. '" ran to its end: it stopped with '. get_class( $error ). ' at line '. $error->getLine(), false );
	fwrite( STDERR, $error->getMessage(). "\n" );
}

/*	Worker modes: this same file re-entered as a process of its own, pointed at a sandbox somebody else built. Numbers taken by several processes, two writers on one client and a reader while a writer holds
	are only seen from other processes: flock() is per open file description	*/
if( in_array( $argv[1] ?? '', [ 'clients-worker', 'writer-worker', 'holder-worker', 'reader-worker', 'shared-worker' ], true ) === true ) {

	$worker = domRequest( (string) $argv[3] );

	if( $argv[1] === 'clients-worker' ) {

		while( is_file( $argv[5] ) === false )
			usleep( 1000 );

		$made = [];

		for( $round = 0; $round < (int) $argv[4]; $round++ ) {
			$client = \Nino\Modules\Crm\Clients::add( $worker, [ 'name' => 'Worker '. getmypid(). ' '. $round ] );
			$made[] = is_array( $client ) === true ? $client['number'] : $client;
		}

		echo json_encode( $made );
		exit( 0 );
	}

	if( $argv[1] === 'writer-worker' ) {

		while( is_file( $argv[6] ) === false )
			usleep( 1000 );

		$results = [];

		for( $round = 0; $round < (int) $argv[5]; $round++ ) {
			$changed = \Nino\Modules\Crm\Clients::change( $worker, (int) $argv[4], [ 'phone' => '+49 '. $argv[7]. $round ] );
			$results[] = is_array( $changed ) === true ? 'ok' : $changed;
		}

		echo json_encode( $results );
		exit( 0 );
	}

	// A reader that holds the shared lock for a few seconds and lets go: the commit of another process has to wait for it
	if( $argv[1] === 'shared-worker' ) {

		$pdo = \Nino\Modules\Crm\Db::pdo( $worker );
		$pdo->exec( 'BEGIN' );
		$pdo->query( 'SELECT COUNT(*) FROM project_states' )->fetchAll();
		file_put_contents( $argv[4], 'held' );
		sleep( (int) $argv[5] );
		$pdo->exec( 'ROLLBACK' );
		exit( 0 );
	}

	// A writer that holds the write lock for a while, with the rows it wrote not yet committed
	if( $argv[1] === 'holder-worker' ) {

		$pdo = \Nino\Modules\Crm\Db::pdo( $worker );
		$pdo->exec( 'BEGIN IMMEDIATE' );
		$pdo->exec( 'UPDATE clients SET notes = \'held\' WHERE id = '. (int) $argv[4] );
		file_put_contents( $argv[5], 'held' );

		while( is_file( $argv[6] ) === false )
			usleep( 1000 );

		$pdo->exec( 'COMMIT' );
		echo 'committed';
		exit( 0 );
	}

	// A reader: what it sees of the client while the writer holds, and how long it took
	$started = microtime( true );
	$seen = \Nino\Modules\Crm\Clients::get( $worker, (int) $argv[4] );
	echo json_encode( [ 'notes' => $seen['notes'] ?? null, 'seconds' => round( microtime( true ) - $started, 3 ) ] );
	exit( 0 );
}

/*	A process that may read the files and take the locks, and may not write them: what the second part says of a disk it cannot write. Root gives up its rights	*/
if( ( $argv[1] ?? '' ) === 'nobody-worker' ) {

	$worker = domRequest( (string) $argv[3] );

	if( function_exists( 'posix_setuid' ) === true && posix_getuid() === 0 ) {
		posix_setgid( 65534 );
		posix_setuid( 65534 );
	}

	$warnings = [];
	set_error_handler( static function( int $level, string $message ) use ( &$warnings ): bool {

		if( ( error_reporting() & $level ) !== 0 )
			$warnings[] = $message;

		return true;
	} );
	$entry = \Nino\Modules\Crm\Logbook::add( $worker, [ 'source' => 'crm-probe', 'kind' => 'probe-happened', 'client_id' => (int) $argv[4], 'text' => 'x', 'data' => [ 'n' => 1 ] ] );

	try {
		\Nino\Modules\Crm\Clients::add( $worker, [ 'name' => 'Never Written' ] );
		$client = 'written';
	}
	catch( \PDOException $e ) {
		$client = 'refused';
	}

	echo json_encode( [ 'uid' => posix_getuid(), 'logbook' => $entry, 'warnings' => count( $warnings ), 'client' => $client, 'personal' => str_contains( implode( ' ', $warnings ), 'Never' ) ] );
	exit( 0 );
}

register_shutdown_function( static function() use ( $features ): void {

	if( str_starts_with( basename( $features ), 'nino-crm-domain-features-' ) === true && ( $GLOBALS['argv'][1] ?? '' ) === '' )
		domRemove( $features );
} );

// --- The endpoint -------------------------------------------------------------------------------------------------------------

echo "The endpoint\n";

try {

[ $project, $where, $a ] = domWorld( 'endpoint' );

check( 'the route is entered: POST /crm/api, with the guard of the token', ( $a['/nino/http/routes']['POST://crm/api'] ?? null ) === [ 'uri' => '/crm/api', 'csrf' => true ] );
check( 'the actions of the core are entered under their names, each a [ class, method ] that is there', array_diff( [ 'crm-clients/list', 'crm-clients/save', 'crm-projects/list', 'crm-tasks/add', 'crm-logbook/add', 'crm/status', 'crm/setup', 'crm/overview', 'crm/settings-get', 'crm/settings-save', 'crm/brand-test', 'crm/notifications', 'crm/notifications-save', 'crm/wipe', 'elements/save', 'text/savebatch' ], array_keys( \Nino\Modules\Crm\Api::actions( $a ) ) ) === []
	&& array_filter( \Nino\Modules\Crm\Api::actions( $a ), static fn( array $action ): bool => is_callable( $action['target'] ) === false ) === [] );
check( 'the core enters 42 actions (the 39 of the clients, projects, tasks, logbook, settings, overview, notifications, test phase and panels, and the three of the devices) and the package of the suite five of its own', count( array_filter( \Nino\Modules\Crm\Api::actions( $a ), static fn( array $action ): bool => $action['package'] === 'crm' ) ) === 42 && count( array_filter( \Nino\Modules\Crm\Api::actions( $a ), static fn( array $action ): bool => $action['package'] === 'crm-probe' ) ) === 5 );

domSignIn( $a, '' );
check( 'no session: 401 with the code session, and nothing is run', domAnswer( $a, 'crm-clients/list' ) === '401 session' );
domSignIn( $a, 'plain@example.org' );
check( 'a session without the permission of the CRM: 403, and an action of a package that checks nothing itself is not run', domAnswer( $a, 'crm-clients/list' ) === '403 -' && domAnswer( $a, 'crm-probe/open' ) === '403 -' );
domSignIn( $a, 'members@example.org' );
check( 'a session with the permission of another panel and none of the CRM: 403, for a read and for a write', domAnswer( $a, 'crm-clients/list' ) === '403 -' && domAnswer( $a, 'crm-clients/save', [ 'name' => 'X' ] ) === '403 -' && domCount( $a, 'clients' ) === 0 );
domSignIn( $a, 'crm@example.org' );
check( 'the permission of the CRM alone does for the actions of the core that are the CRM\'s, and not for the panel of the elements or of the texts', domAnswer( $a, 'crm-clients/list' ) === '200 -' && domAnswer( $a, 'elements/list', [ 'type' => 'crm-projects' ] ) === '403 -' && domAnswer( $a, 'text/keys' ) === '403 -' );
check( 'no token: 403 with the code csrf; a wrong one the same; the right one after it is let through', domAnswer( $a, 'crm-clients/list', [], [ 'token' => '' ] ) === '403 csrf' && domAnswer( $a, 'crm-clients/list', [], [ 'token' => str_repeat( 'a', 64 ) ] ) === '403 csrf' && domAnswer( $a, 'crm-clients/list' ) === '200 -' );
check( 'a write without the token is refused and writes nothing', domAnswer( $a, 'crm-clients/save', [ 'name' => 'No token' ], [ 'token' => '' ] ) === '403 csrf' && domCount( $a, 'clients' ) === 0 );

check( 'an action nobody entered is the 404 with the code action; so is a body that is no json, a list, a number and an action that is no text', domAnswer( $a, 'crm-clients/nothing' ) === '404 action'
	&& domAnswer( $a, '', [], [ 'body' => 'not json' ] ) === '404 action' && domAnswer( $a, '', [], [ 'body' => '[1,2]' ] ) === '404 action' && domAnswer( $a, '', [], [ 'body' => '7' ] ) === '404 action'
	&& domAnswer( $a, '', [], [ 'body' => '{"action":["crm-clients/list"]}' ] ) === '404 action' && domAnswer( $a, '', [], [ 'body' => '{"action":7}' ] ) === '404 action' && domAnswer( $a, '', [], [ 'body' => '' ] ) === '404 action' );
check( 'an action name that is an id, a path or a null byte is none either', domAnswer( $a, '../crm-clients/list' ) === '404 action' && domAnswer( $a, "crm-clients/list\0" ) === '404 action' && domAnswer( $a, 'CRM-CLIENTS/LIST' ) === '404 action' && domAnswer( $a, 'crm-clients/list/' ) === '404 action' );
check( 'the data of an action is an object or nothing: a text, a number, a flag in its place is the 400 crm_request and the action is not run', domAnswer( $a, 'crm-clients/save', 'x' ) === '400 crm_request' && domAnswer( $a, 'crm-clients/save', 7 ) === '400 crm_request'
	&& domAnswer( $a, 'crm-clients/save', true ) === '400 crm_request' && domAnswer( $a, '', [], [ 'body' => '{"action":"crm-clients/list","data":"x"}' ] ) === '400 crm_request' && domCount( $a, 'clients' ) === 0 );
check( 'the data may be left out, be null or be a list: the action reads what it knows', domAnswer( $a, '', [], [ 'body' => '{"action":"crm-clients/list"}' ] ) === '200 -' && domAnswer( $a, '', [], [ 'body' => '{"action":"crm-clients/list","data":null}' ] ) === '200 -' && domAnswer( $a, 'crm-clients/list', [ 1, 2 ] ) === '200 -' );
check( 'a body nested deeper than the endpoint reads is no body', domAnswer( $a, '', [], [ 'body' => '{"action":"crm-clients/list","data":'. str_repeat( '{"a":', 40 ). '1'. str_repeat( '}', 40 ). '}' ] ) === '404 action' );
check( 'the answer is never cached', ( domPost( $a, 'crm-clients/list' )[2]['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store' );

domSignIn( $a, 'admin@example.org' );
check( 'the probe of the suite reads the data of the post and nothing else of it: $_POST holds what the action put there for its length only', ( domBody( $a, 'crm-probe/read', [ 'x' => 1 ] )['post'] ?? null ) === [ 'action', 'data' ] && $_POST === [] );
check( 'what stood in $_POST before the call stands there after it: the endpoint itself puts it back, whatever the helper does', ( static function() use ( &$a ): bool { domPost( $a, 'crm-probe/read', [], [ 'fields' => [ 'keep' => 'me' ] ] ); $left = $GLOBALS['domPostLeft'] ?? null; domPost( $a, 'crm-probe/nothing' ); return $left === [ 'keep' => 'me' ] && ( $GLOBALS['domPostLeft'] ?? null ) === []; } )() );
check( 'an action that needs a feature that is not there is the 409 <key>_off, and its method is not run', domAnswer( $a, 'crm-probe/needy' ) === '409 crm_probe_absent_off' );
check( 'an action that takes a file reads its action and data from the fields php has read of the form; one that takes none does not', ( static function() use ( &$a ): bool {
	$fields = [ 'action' => 'crm-probe/upload', 'data' => '{"id":"7"}' ];
	[ $status, $body ] = domPost( $a, '', [], [ 'body' => '', 'type' => 'multipart/form-data; boundary=x', 'fields' => $fields, 'files' => [ 'image' => [ 'name' => 'a.png' ] ] ] );
	[ $other ] = domPost( $a, '', [], [ 'body' => '', 'type' => 'multipart/form-data; boundary=x', 'fields' => [ 'action' => 'crm-probe/read', 'data' => '{}' ] ] );

	return $status === 200 && ( $body['data'] ?? null ) === [ 'id' => '7' ] && ( $body['files'] ?? null ) === [ 'image' ] && $other === 404;
} )() );

// A line of the log is written by the action, after an answer of 200 - never for a read, never for a refusal
$before = count( domLog( $a, 50 ) );
domPost( $a, 'crm-probe/read' );
domPost( $a, 'crm-probe/write', [ 'what' => 'a line' ] );
domPost( $a, 'crm-probe/write', [ 'what' => 'a line that is refused', 'refuse' => true ] );
$after = domLog( $a, 50 );
check( 'the line an action wrote with made() is in the activity log after it went through, with the account; a read writes none', count( $after ) === $before + 1 && str_contains( $after[0], 'The probe wrote a line' ) === true && str_contains( $after[0], 'admin@example.org' ) === true );
domSignIn( $a, 'crm@example.org' );
$before = count( domLog( $a, 50 ) );
domPost( $a, 'crm-clients/save', [ 'name' => '' ] );
check( 'a refusal writes no line', count( domLog( $a, 50 ) ) === $before );

// Every place a package enters itself names its package the same way, and asks the same question of it: a slug that is none of the core's first words and is the key of a feature that is on
check( 'each of the places of the core - settings, sequences, status lines, paths of the hosts, words of the mails, actions, element types, deletions, test phase, kinds of records, kinds of the logbook, switches - takes the package that enters and refuses what is none: no package, the core, a first word of the core, a feature that is not there, a name that is none and a number are each refused with one warning and nothing is entered, and the package that is on is the one that is let in', ( static function() use ( &$a ): bool {

	$calls = [
		'settings' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Settings::add( $r, [ $o + [ 'key' => 'uni_setting', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ] ] ),
		'sequences' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Sequences::add( $r, $o + [ 'category' => 'uni-seq' ] ),
		'status' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Status::add( $r, $o + [ 'key' => 'uni-line', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ] ] ),
		'hosts' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Hosts::share( $r, [ '/.uni/' ], [], $o ),
		'mail' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Mail::addWords( $r, '/feature/'. ( is_string( $o['package'] ?? null ) === true ? $o['package'] : 'crm-probe' ). '/', $o ),
		'actions' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Api::add( $r, [ 'crm-probe/uni' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], $o ),
		'types' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Values::addType( $r, $o + [ 'type' => 'crm-probe-uni' ] ),
		'erasure' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Erasure::add( $r, $o + [ 'key' => 'uni', 'count' => [ \Nino\Modules\CrmProbe\Domain::class, 'count' ], 'reason' => 'uniheld', 'answer' => [ 409, 'x', 'crm_uni' ] ] ),
		'wipe' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Wipe::add( $r, $o + [ 'key' => 'uni', 'leftover' => [ \Nino\Modules\CrmProbe\Domain::class, 'leftover' ] ] ),
		'kinds of records' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Records::addKind( $r, $o + [ 'kind' => 'uni-kind' ] ),
		'kinds of the logbook' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Logbook::addKinds( $r, [ 'uni-entry' ], $o ),
		'switches' => static fn( array &$r, array $o ): bool => \Nino\Modules\Crm\Notifications::add( $r, $o + [ 'key' => 'uni_switch', 'read' => [ \Nino\Modules\CrmProbe\Domain::class, 'readSwitch' ], 'write' => [ \Nino\Modules\CrmProbe\Domain::class, 'writeSwitch' ] ] ),
	];
	$bad = [ 'none' => [], 'the core' => [ 'package' => 'crm' ], 'a first word of the core' => [ 'package' => 'elements' ], 'no feature' => [ 'package' => 'crm-nothing' ], 'a name that is none' => [ 'package' => 'Not A Slug' ], 'a number' => [ 'package' => 7 ] ];
	$wrong = [];

	foreach( $calls as $place => $call ) {

		foreach( $bad as $what => $options ) {
			ninoWarnings();
			$entered = $call( $a, $options );

			if( $entered !== false || count( ninoWarnings() ) !== 1 )
				$wrong[] = $place. ' / '. $what;
		}

		ninoWarnings();

		if( $call( $a, [ 'package' => 'crm-probe' ] ) !== true || ninoWarnings() !== [] )
			$wrong[] = $place. ' / the package that is on';
	}

	return $wrong === [];
} )() );

// The table of the actions: what a package may enter
check( 'a package enters actions under its own name, <key>/<name>, and the same again is as good as the first time', ( static function() use ( &$a ): bool {
	$ok = [ 'crm-probe/more' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ];

	return \Nino\Modules\Crm\Api::add( $a, $ok, [ 'package' => 'crm-probe' ] ) === true && \Nino\Modules\Crm\Api::add( $a, $ok, [ 'package' => 'crm-probe' ] ) === true && ninoWarnings() === [];
} )() );
$refused = [
	'the name of a package that says it is the core' => [ [ 'crm/steal' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm' ] ],
	'the first word of the core as the package' => [ [ 'crm-clients/steal' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-clients' ] ],
	'the panel of the elements as the package' => [ [ 'elements/save' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'elements' ] ],
	'a package that is not a feature that is on' => [ [ 'crm-nothing/read' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-nothing' ] ],
	'the panel option, which is the core\'s' => [ [ 'crm-probe/panel' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe', 'panel' => true ] ],
	'a name after the key and a hyphen' => [ [ 'crm-probe-extra/more' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] ],
	'the name of another package' => [ [ 'crm-clients/steal' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] ],
	'a target in a panel of the workbench' => [ [ 'crm-probe/del' => [ \Nino\Modules\Elements\Admin::class, 'apiDelete' ] ], [ 'package' => 'crm-probe' ] ],
	'a target in the kernel' => [ [ 'crm-probe/kernel' => [ \Nino\Elements::class, 'deleteElement' ] ], [ 'package' => 'crm-probe' ] ],
	'a target in a package whose name only begins like this one' => [ [ 'crm-probe/two' => [ 'Nino\\Modules\\CrmProbeTwo\\CrmProbeTwo', 'init' ] ], [ 'package' => 'crm-probe' ] ],
	'a target that is a class of the core' => [ [ 'crm-probe/core' => [ \Nino\Modules\Crm\Clients\Api::class, 'apiDelete' ] ], [ 'package' => 'crm-probe' ] ],
	'a name that is a path' => [ [ 'crm-probe/../x' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] ],
	'a name with a capital' => [ [ 'crm-probe/Read' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] ],
	'a name without a slash' => [ [ 'crm-probe' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] ],
	'an action a package of the same name has, with another target' => [ [ 'crm-probe/read' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiWrite' ] ], [ 'package' => 'crm-probe' ] ],
	'a target that is none' => [ [ 'crm-probe/none' => [ 'Nino\\Nothing', 'nothing' ] ], [ 'package' => 'crm-probe' ] ],
	'a target that is a closure' => [ [ 'crm-probe/closure' => static fn() => null ], [ 'package' => 'crm-probe' ] ],
	'no package' => [ [ 'crm-probe/nopackage' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [] ],
	'a package that is no slug' => [ [ 'crm-probe/slug' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'Crm Probe' ] ],
	'an option nobody knows' => [ [ 'crm-probe/option' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe', 'wide' => true ] ],
	'a file action that is none of the list' => [ [ 'crm-probe/file' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe', 'files' => [ 'crm-probe/other' ] ] ],
	'a feature that is no slug' => [ [ 'crm-probe/needs' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe', 'needs' => 'Not A Slug' ] ],
];

foreach( $refused as $label => [ $actions, $opts ] ) {
	$count = count( \Nino\Modules\Crm\Api::actions( $a ) );
	$answer = \Nino\Modules\Crm\Api::add( $a, $actions, $opts );
	check( 'a package that enters '. $label. ' is refused, with a warning, and nothing is entered', $answer === false && count( ninoWarnings() ) === 1 && count( \Nino\Modules\Crm\Api::actions( $a ) ) === $count );
}

check( 'the actions of the core are fixed whatever order the packages start in: a package that is first enters its own, the core enters all of its own after it, and the name of one of them is never a package\'s', ( static function() use ( &$a ): bool {
	$core = count( array_filter( \Nino\Modules\Crm\Api::actions( $a ), static fn( array $action ): bool => $action['package'] === 'crm' ) );
	$fresh = $a;
	unset( $fresh['./crm/api/actions'] );
	$first = \Nino\Modules\Crm\Api::add( $fresh, [ 'crm-probe/early' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] );
	$stolen = \Nino\Modules\Crm\Api::add( $fresh, [ 'crm-clients/delete' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] );
	\Nino\Modules\Crm\Api::init( $fresh );
	ninoWarnings();
	$after = \Nino\Modules\Crm\Api::actions( $fresh );

	return $core === 42 && $first === true && $stolen === false && count( array_filter( $after, static fn( array $action ): bool => $action['package'] === 'crm' ) ) === 42 && $after['crm-clients/delete']['target'] === [ \Nino\Modules\Crm\Clients\Api::class, 'apiDelete' ] && isset( $after['crm-probe/early'] ) === true;
} )() );
check( 'a list with one action that is refused enters none of it', ( static function() use ( &$a ): bool {
	$count = count( \Nino\Modules\Crm\Api::actions( $a ) );
	$answer = \Nino\Modules\Crm\Api::add( $a, [ 'crm-probe/fine' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ], 'crm-clients/bad' => [ \Nino\Modules\CrmProbe\Domain::class, 'apiRead' ] ], [ 'package' => 'crm-probe' ] );
	ninoWarnings();

	return $answer === false && count( \Nino\Modules\Crm\Api::actions( $a ) ) === $count && isset( \Nino\Modules\Crm\Api::actions( $a )['crm-probe/fine'] ) === false;
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'the endpoint', $e );
}

// --- The clients ------------------------------------------------------------------------------------------------------------

echo "The clients\n";

try {

[ $project, $where, $a ] = domWorld( 'clients' );
domSignIn( $a, 'crm@example.org' );

check( 'no client yet: the list is empty and no deletion is waiting', domBody( $a, 'crm-clients/list' ) === [ 'clients' => [], 'erasing' => [] ] );

$made = domBody( $a, 'crm-clients/save', [ 'company' => 'Studio GmbH', 'name' => 'Ada Example', 'street' => 'Weg 1', 'zip' => '12345', 'city' => 'Stadt', 'country' => 'at', 'email' => 'ada@example.org', 'phone' => '+49 (30) 123-456', 'vat_id' => 'de 123456789', 'buyer_reference' => 'ref-1', 'notes' => "one\ntwo", 'business' => true ] );
$one = $made['client'] ?? [];
check( 'a client is made with the next number, in one piece: K- and four digits, active, the country and the VAT id in capitals without blanks, the title its company', ( $one['number'] ?? '' ) === 'K-0001' && $one['active'] === true && $one['country'] === 'AT' && $one['vat_id'] === 'DE123456789'
	&& $one['title'] === 'Studio GmbH' && $one['business'] === true && $one['notes'] === "one\ntwo" && is_int( $one['id'] ) && $one['created'] === $one['updated'] );
check( 'the answer holds the keys of the table and the title, and nothing of an account, a message, a group or a document: those are the packages\'', array_keys( $one ) === [ 'id', 'number', 'company', 'name', 'street', 'zip', 'city', 'country', 'email', 'phone', 'vat_id', 'business', 'buyer_reference', 'notes', 'active', 'created', 'updated', 'title' ]
	&& (array) ( $made['packages'] ?? null ) === [] );
check( 'the logbook of the client has the line that it was added, written with the client', domCount( $a, 'logbook', 'client_id = '. $one['id']. ' AND source = \'crm\' AND kind = \'client-added\'' ) === 1 );
check( 'a client with a name alone is made, and is called by it', ( domBody( $a, 'crm-clients/save', [ 'name' => 'Bob' ] )['client']['title'] ?? '' ) === 'Bob' && domCount( $a, 'clients' ) === 2 );
check( 'the numbers go on: one more than the highest', \Nino\Modules\Crm\Clients::upcoming( $a ) === 'K-0003' );

// What a client may not be made of
$refusals = [
	'no company and no name' => [ [ 'email' => 'x@example.org' ], '400 crm_clients_empty' ],
	'a company that is blanks' => [ [ 'company' => '   ' ], '400 crm_clients_empty' ],
	'a name that is a list' => [ [ 'name' => [ 'a' ] ], '400 crm_clients_format' ],
	'a name that is a number' => [ [ 'name' => 7 ], '400 crm_clients_format' ],
	'a name that is an object' => [ [ 'name' => [ 'a' => 'b' ] ], '400 crm_clients_format' ],
	'a name with a control character' => [ [ 'name' => "A\x01B" ], '400 crm_clients_format' ],
	'a name with a null byte' => [ [ 'name' => "A\0B" ], '400 crm_clients_format' ],
	'a name with a line break' => [ [ 'name' => "A\nB" ], '400 crm_clients_format' ],
	'a name that turns the text around' => [ [ 'name' => "A\u{202E}B" ], '400 crm_clients_format' ],
	'a name of 121 characters' => [ [ 'name' => str_repeat( 'a', 121 ) ], '400 crm_clients_long' ],
	'a zip that is a symbol' => [ [ 'name' => 'A', 'zip' => '!!' ], '400 crm_clients_format' ],
	'a zip of 13 characters' => [ [ 'name' => 'A', 'zip' => '1234567890123' ], '400 crm_clients_long' ],
	'a phone that is words' => [ [ 'name' => 'A', 'phone' => 'call me' ], '400 crm_clients_format' ],
	'a VAT id that is none' => [ [ 'name' => 'A', 'vat_id' => '12345' ], '400 crm_clients_format' ],
	'a country that is none of the list' => [ [ 'name' => 'A', 'country' => 'ZZ' ], '400 crm_clients_format' ],
	'a country of three letters' => [ [ 'name' => 'A', 'country' => 'DEU' ], '400 crm_clients_long' ],
	'an address that is none' => [ [ 'name' => 'A', 'email' => 'not an address' ], '400 crm_clients_email' ],
	'an address of 161 characters' => [ [ 'name' => 'A', 'email' => str_repeat( 'a', 150 ). '@example.org' ], '400 crm_clients_long' ],
	'a flag that is a text' => [ [ 'name' => 'A', 'business' => 'yes' ], '400 crm_clients_format' ],
	'a flag that is a number' => [ [ 'name' => 'A', 'business' => 1 ], '400 crm_clients_format' ],
	'a reference with a tab' => [ [ 'name' => 'A', 'buyer_reference' => "a\tb" ], '400 crm_clients_format' ],
	'notes of 2001 characters' => [ [ 'name' => 'A', 'notes' => str_repeat( 'n', 2001 ) ], '400 crm_clients_long' ],
	'notes with a null byte' => [ [ 'name' => 'A', 'notes' => "n\0n" ], '400 crm_clients_format' ],
	'the values of the packages as a list' => [ [ 'name' => 'A', 'packages' => [ 1, 2 ] ], '400 crm_clients_format' ],
	'the values of a package that are a text' => [ [ 'name' => 'A', 'packages' => [ 'crm-probe' => 'x' ] ], '400 crm_clients_format' ],
	'the values of a package of a name that is none' => [ [ 'name' => 'A', 'packages' => [ 'Not A Slug' => [ 'a' => 1 ] ] ], '400 crm_clients_format' ],
];

foreach( $refusals as $label => [ $fields, $answer ] )
	check( 'a client made of '. $label. ' is refused: '. $answer. ', and nothing is written', domAnswer( $a, 'crm-clients/save', $fields ) === $answer && domCount( $a, 'clients' ) === 2 );

check( 'a number is as good as the digits for a field that is a text? no: a number in a text field is refused, and a text of digits is none', domAnswer( $a, 'crm-clients/save', [ 'name' => 'A', 'phone' => 12345 ] ) === '400 crm_clients_format' && ( domBody( $a, 'crm-clients/save', [ 'name' => 'A', 'phone' => '12345' ] )['client']['phone'] ?? '' ) === '12345' );

// The ids a stranger may send
$ids = [ 'no id' => null, 'an empty id' => '', 'nought' => '0', 'a negative id' => '-1', 'an id with a leading zero' => '01', 'a decimal' => '1.5', 'an id with a blank' => ' 1', 'letters' => 'abc', 'an injection' => '1; DROP TABLE clients', 'a path' => '../1',
	'a null byte' => "1\0", 'eleven digits' => '12345678901', 'a list' => [ 1 ], 'an object' => [ 'a' => 1 ], 'a flag' => true, 'a float' => 1.5 ];

foreach( [ 'crm-clients/get', 'crm-clients/active', 'crm-clients/delete-preview', 'crm-clients/delete' ] as $action )
	foreach( $ids as $label => $id )
		check( $action. ' with '. $label. ' is the 400 crm_clients_id', domAnswer( $a, $action, $id === null ? [] : [ 'id' => $id ] ) === '400 crm_clients_id' );

check( 'an id that is no client is the 404 crm_clients_unknown, as a number and as a text, and a client is found by a number as well', domAnswer( $a, 'crm-clients/get', [ 'id' => '99999' ] ) === '404 crm_clients_unknown' && domAnswer( $a, 'crm-clients/get', [ 'id' => 99999 ] ) === '404 crm_clients_unknown'
	&& ( domBody( $a, 'crm-clients/get', [ 'id' => $one['id'] ] )['client']['number'] ?? '' ) === 'K-0001' && ( domBody( $a, 'crm-clients/get', [ 'id' => (string) $one['id'] ] )['client']['number'] ?? '' ) === 'K-0001' );
check( 'a change names the client by its id: an id that is none is the 400, one nobody has the 404, and nothing is made instead', domAnswer( $a, 'crm-clients/save', [ 'id' => 'x', 'name' => 'Z' ] ) === '400 crm_clients_id' && domAnswer( $a, 'crm-clients/save', [ 'id' => '99999', 'name' => 'Z' ] ) === '404 crm_clients_unknown' && domCount( $a, 'clients' ) === 3 );

// A change: what is not sent stays, the number stays
$changed = domBody( $a, 'crm-clients/save', [ 'id' => $one['id'], 'city' => 'Ort' ] )['client'] ?? [];
check( 'a change writes the fields sent and leaves the others, the number and the active flag; the time of the change moves', $changed['city'] === 'Ort' && $changed['company'] === 'Studio GmbH' && $changed['number'] === 'K-0001' && $changed['active'] === true && $changed['business'] === true && $changed['created'] === $one['created'] );
check( 'a change that would leave the client with neither company nor name is refused, and the client is as it was', domAnswer( $a, 'crm-clients/save', [ 'id' => $one['id'], 'company' => '', 'name' => '' ] ) === '400 crm_clients_empty' && ( domBody( $a, 'crm-clients/get', [ 'id' => $one['id'] ] )['client']['company'] ?? '' ) === 'Studio GmbH' );
check( 'a client keeps the country it has though it is none of the list, and the country of a change is looked at only where it is sent', ( static function() use ( &$a, $one ): bool {
	\Nino\Modules\Crm\Db::exec( $a, 'UPDATE clients SET country = \'NZ\' WHERE id = :id', [ 'id' => $one['id'] ] );

	return ( domBody( $a, 'crm-clients/save', [ 'id' => $one['id'], 'city' => 'Neu' ] )['client']['country'] ?? '' ) === 'NZ' && domAnswer( $a, 'crm-clients/save', [ 'id' => $one['id'], 'country' => 'ZZ' ] ) === '400 crm_clients_format';
} )() );

// Injection through every text field: stored as typed, answered as typed, the tables are there
$evil = [ '<script>alert(1)</script>', '\'; DROP TABLE clients; --', '" OR 1=1 --', '{{7*7}}', '[[/project/company/general/name]]', '#/project/company/contact/email#', '${jndi:ldap://x}', '=HYPERLINK("http://x")', '@SUM(1)', '+1+1', '-2+3', '%s%n', '<img src=x onerror=1>' ];

foreach( [ 'company', 'name', 'street', 'city', 'email' ] as $field ) {

	$bad = [];

	foreach( $evil as $text ) {

		if( $field === 'email' )
			continue;

		$id = (int) ( domBody( $a, 'crm-clients/save', [ 'name' => 'Injection', $field => $text ] )['client']['id'] ?? 0 );
		$row = $id === 0 ? [] : \Nino\Modules\Crm\Db::row( $a, 'SELECT '. $field. ' FROM clients WHERE id = :id', [ 'id' => $id ] );

		if( $id === 0 || ( $row[$field] ?? null ) !== $text || ( domBody( $a, 'crm-clients/get', [ 'id' => $id ] )['client'][$field] ?? null ) !== $text )
			$bad[] = $text;
	}

	check( 'the field '. $field. ' keeps what is typed in it, markup, quotes, brackets and formulas as they are: stored by parameters, answered as data', $field === 'email' || $bad === [] );
}

check( 'the tables are there after the injections and the logbook has one line for each client made', domCount( $a, 'clients' ) > 40 && domCount( $a, 'logbook', 'kind = \'client-added\'' ) === domCount( $a, 'clients' ) );
check( 'the text of a client never reaches the log of the activity: the line names the number', ( static function() use ( &$a ): bool {
	$line = domLog( $a, 1 )[0] ?? '';

	return preg_match( '/Added client K-\d{4}$/', $line ) === 1;
} )() );

// Hooks: a package hears of a client, and answers
$a['./crm-probe/steer'][\Nino\Modules\Crm\Clients::ADDED] = [ 'merge' => [ 'answers' => [ 'crm-probe' => [ 'invited' => true ], 'Bad Key' => [ 'x' => 1 ], 'crm-text' => 'no list' ] ] ];
$told = domBody( $a, 'crm-clients/save', [ 'name' => 'Hooked', 'packages' => [ 'crm-probe' => [ 'email' => 'p@example.org' ] ] ] );
$hook = end( $a['./crm-probe/told'][\Nino\Modules\Crm\Clients::ADDED] );
check( 'a client that is made is told to the packages, with the client and what its forms posted; what a listener answers goes to the page under its key, an answer of a key that is none or of a shape that is none left out', $hook['client']['name'] === 'Hooked' && $hook['posted'] === [ 'crm-probe' => [ 'email' => 'p@example.org' ] ]
	&& (array) ( $told['packages'] ?? null ) === [ 'crm-probe' => [ 'invited' => true ] ] );
unset( $a['./crm-probe/steer'] );
$hookedId = (int) $told['client']['id'];
domBody( $a, 'crm-clients/save', [ 'id' => $hookedId, 'city' => 'Elsewhere', 'packages' => [ 'crm-probe' => [ 'k' => 'v' ] ] ] );
check( 'a client that is changed is told too, with what was posted', ( end( $a['./crm-probe/told'][\Nino\Modules\Crm\Clients::CHANGED] )['posted'] ?? null ) === [ 'crm-probe' => [ 'k' => 'v' ] ] );

$a['./crm-probe/steer'][\Nino\Modules\Crm\Clients::ACTIVE] = [ 'throw' => true ];
$warned = [];
$answer = '';
ninoErrors( static function() use ( &$a, $hookedId, &$answer ): void { $answer = domAnswer( $a, 'crm-clients/active', [ 'id' => $hookedId ] ); } );
$warned = ninoWarnings();
check( 'a package that breaks while a client is switched off keeps it as it was: 500 crm_clients_write, with a warning that names the hook and nothing of the client', $answer === '500 crm_clients_write' && ( domBody( $a, 'crm-clients/get', [ 'id' => $hookedId ] )['client']['active'] ?? null ) === true );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Clients::CHANGED] = [ 'throw' => true ];
$lines = [];
ninoErrors( static function() use ( &$a, $hookedId, &$lines ): void { $lines[] = domAnswer( $a, 'crm-clients/save', [ 'id' => $hookedId, 'city' => 'After a break' ] ); } );
check( 'a package that breaks after a client was changed does not undo it: the answer is the client, the change stands', $lines === [ '200 -' ] && ( domBody( $a, 'crm-clients/get', [ 'id' => $hookedId ] )['client']['city'] ?? '' ) === 'After a break' );
unset( $a['./crm-probe/steer'] );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Clients::ACTIVE] = [ 'merge' => [ 'failed' => [ 'crm-probe' ] ] ];
check( 'a package that cannot follow a client that is switched off says so, and the client stays as it was: 500 crm_clients_write', domAnswer( $a, 'crm-clients/active', [ 'id' => $hookedId ] ) === '500 crm_clients_write' && ( domBody( $a, 'crm-clients/get', [ 'id' => $hookedId ] )['client']['active'] ?? null ) === true );
unset( $a['./crm-probe/steer'] );
$off = domBody( $a, 'crm-clients/active', [ 'id' => $hookedId ] );
check( 'a client is switched off without a flag by turning it, and on with active true; the packages are told which way', ( $off['client']['active'] ?? null ) === false && end( $a['./crm-probe/told'][\Nino\Modules\Crm\Clients::ACTIVE] )['active'] === false
	&& ( domBody( $a, 'crm-clients/active', [ 'id' => $hookedId, 'active' => true ] )['client']['active'] ?? null ) === true && domAnswer( $a, 'crm-clients/active', [ 'id' => $hookedId, 'active' => 'no' ] ) === '200 -' );

check( 'the client by its address: the first that is active, compared without case and blanks; none for an address that is none or too long', ( \Nino\Modules\Crm\Clients::byEmail( $a, '  ADA@example.ORG ' )['number'] ?? '' ) === 'K-0001' && \Nino\Modules\Crm\Clients::byEmail( $a, '' ) === null && \Nino\Modules\Crm\Clients::byEmail( $a, str_repeat( 'a', 200 ) ) === null && \Nino\Modules\Crm\Clients::byEmail( $a, 'nobody@example.org' ) === null );

}
catch( \Throwable $e ) {
	domCrashed( 'the clients', $e );
}

// --- The projects and their phases --------------------------------------------------------------------------------------------

echo "The projects and their phases\n";

try {

[ $project, $where, $a ] = domWorld( 'projects' );
domSignIn( $a, 'admin@example.org' );
$ada = domClient( $a, [ 'name' => 'Ada' ] );
$bea = domClient( $a, [ 'name' => 'Bea' ] );
$cleo = domClient( $a, [ 'name' => 'Cleo' ] );

check( 'the unit brought the type of the projects, numbered, with the fields of the plan and no folder, and the type of the phases with its four elements in both languages', ( static function() use ( &$a ): bool {
	$projects = (array) \Nino\Filesystem::getFileContent( $a, '/elements/crm-projects.php', [] );
	$phases = \Nino\Elements::queryElements( $a, '/crm-phases', [], '*', [] );

	return array_keys( $projects['model'] ) === [ 'title', 'client', 'description', 'started', 'done' ] && ( $projects['autoincrement'] ?? null ) === 1 && count( $phases ) === 4
		&& \Nino\Elements::getElementWithFallback( $a, '/crm-phases/mix', 'en_US' )['title'] === 'Mix' && \Nino\Elements::getElementWithFallback( $a, '/crm-phases/recording', 'de_DE' )['title'] === 'Aufnahme';
} )() );
check( 'the types of the core are entered with the Values: the projects with a screen of their own, the phases and the type of the package among the values', \Nino\Modules\Crm\Values::types( $a ) === [ 'crm-projects', 'crm-phases', 'crm-probe-values' ] && \Nino\Modules\Crm\Values::types( $a, 'values' ) === [ 'crm-phases', 'crm-probe-values' ] );

// elements/types: what the screen of the values is offered
$types = domBody( $a, 'elements/types' );
check( 'elements/types is cut to the types of the screen of the values, in their rank; a type that is entered and is not in the project is told as missing; the labels of the fields are the unit\'s', array_column( $types['types'] ?? [], 'type' ) === [ 'crm-phases' ] && ( $types['missing'] ?? null ) === [ 'crm-probe-values' ]
	&& ( $types['native'] ?? '' ) === 'de_DE' && ( $types['types'][0]['defaults'] ?? null ) === [] && ( $types['types'][0]['hidden'] ?? null ) === [] );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Values::START] = [ 'merge' => [ 'defaults' => [ 'order' => 50 ], 'hidden' => [ 'order', 7 ] ] ];
$types = domBody( $a, 'elements/types' );
check( 'what a package says a new element starts with and what the form leaves out is on the type', ( $types['types'][0]['defaults'] ?? null ) === [ 'order' => 50 ] && ( $types['types'][0]['hidden'] ?? null ) === [ 'order' ] );
unset( $a['./crm-probe/steer'] );

// The guard of the type
foreach( [ 'a type nobody entered' => 'posts', 'no type' => '', 'a type that is a path' => '../crm-projects', 'the type with a capital' => 'CRM-PROJECTS' ] as $label => $type )
	foreach( [ 'elements/list', 'elements/get', 'elements/save', 'elements/delete' ] as $action )
		check( $action. ' for '. $label. ' is the 404 crm_values_type: the endpoint is no editor of the elements of the site', domAnswer( $a, $action, [ 'type' => $type, 'uri' => 'x', 'locale' => 'de_DE' ] ) === '404 crm_values_type' );
check( 'a type that is a list is none', domAnswer( $a, 'elements/list', [ 'type' => [ 'crm-projects' ] ] ) === '404 crm_values_type' );
domSignIn( $a, 'crm@example.org' );
check( 'the permission of the Elements panel is the panel\'s: the CRM\'s alone is not enough for any of the five actions', array_unique( array_map( static fn( string $action ): string => domAnswer( $a, $action, [ 'type' => 'crm-projects', 'uri' => '00001', 'locale' => 'de_DE' ] ), [ 'elements/types', 'elements/list', 'elements/get', 'elements/save', 'elements/delete' ] ) ) === [ '403 -' ] );
domSignIn( $a, 'admin@example.org' );

// A project through the screen: elements/save
$noClient = domAnswer( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'T', 'client' => 'K-9999' ] ] );
check( 'a project of a client that is none is refused with the code crm_project_client_unknown, at the field client, and none is made', $noClient === '400 crm_project_client_unknown' && count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) ) === 0 );
check( 'a project with no client field at all is refused by the type: the required key has its own code', domAnswer( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'T' ] ] ) === '400 elements_missing_key' );
$p1 = domProject( $a, 'K-0001', 'First' );
$p2 = domProject( $a, 'K-0001', 'Second' );
$p3 = domProject( $a, 'K-0002', 'Third' );
check( 'projects are numbered by the counter of the type: 00001, 00002, 00003, and are the client\'s by its number', [ $p1, $p2, $p3 ] === [ '00001', '00002', '00003' ] && \Nino\Modules\Crm\Projects::get( $a, $p3 )['client'] === 'K-0002' );
check( 'the logbook of the project says it was made, once, written with the project and with its client', domCount( $a, 'logbook', 'kind = \'project-added\' AND project = \'00001\' AND client_id = '. $ada ) === 1 );
check( 'the list of a client is its projects, the newest first; a client that is none has none; the filters are checked', ( static function() use ( &$a, $ada ): bool {
	$list = domBody( $a, 'crm-projects/list', [ 'client' => (string) $ada ] );
	$none = domBody( $a, 'crm-projects/list', [ 'client' => '99999' ] );

	return array_column( $list['projects'], 'id' ) === [ '00002', '00001' ] && $list['projects'][0]['client_title'] === 'Ada' && $list['projects'][0]['client_id'] === $ada && $list['projects'][0]['client_active'] === true && $list['shared'] === []
		&& $none['projects'] === [] && $none['shared'] === [] && count( $list['phases'] ) === 4 && $list['missing'] === false && $list['native'] === 'de_DE'
		&& domAnswer( $a, 'crm-projects/list', [ 'client' => 'x' ] ) === '400 crm_projects_format' && domAnswer( $a, 'crm-projects/list', [ 'open' => 'yes' ] ) === '400 crm_projects_format';
} )() );
check( 'the search looks in the title and in P-0001, without case; the open ones leave out the done', ( static function() use ( &$a ): bool {
	domBody( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '00002', 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'done' => true ] ] );

	return array_column( domBody( $a, 'crm-projects/list', [ 'q' => 'SECO' ] )['projects'], 'id' ) === [ '00002' ] && array_column( domBody( $a, 'crm-projects/list', [ 'q' => 'p-0003' ] )['projects'], 'id' ) === [ '00003' ]
		&& array_column( domBody( $a, 'crm-projects/list', [ 'open' => true ] )['projects'], 'id' ) === [ '00003', '00001' ];
} )() );

// One project
$got = domBody( $a, 'crm-projects/get', [ 'id' => $p1 ] );
check( 'a project is answered with its client, its phases, what holds back what a package shows, the tasks that are open and the releases', array_keys( $got ) === [ 'native', 'phases', 'project', 'waiting', 'client', 'tasks', 'shares' ] && $got['project']['id'] === '00001' && $got['client'] === [ 'id' => $ada, 'number' => 'K-0001', 'title' => 'Ada', 'active' => true ]
	&& $got['waiting'] === null && $got['tasks'] === 0 && $got['shares'] === [] );
foreach( [ 'no id' => null, 'an empty id' => '', 'an id that is a path' => '../00001', 'an id with a null byte' => "0000\0", 'an id that is a list' => [ '00001' ], 'an id of 101 characters' => str_repeat( 'a', 101 ), 'an id that starts with a hyphen' => '-1' ] as $label => $id )
	check( 'crm-projects/get with '. $label. ' is the 404 crm_projects_unknown', domAnswer( $a, 'crm-projects/get', $id === null ? [] : [ 'id' => $id ] ) === '404 crm_projects_unknown' );
check( 'a project that is none is the 404, and the id of a project is the text it is made of - 00001 - and no number', domAnswer( $a, 'crm-projects/get', [ 'id' => '99999' ] ) === '404 crm_projects_unknown' && domAnswer( $a, 'crm-projects/get', [ 'id' => 1 ] ) === '404 crm_projects_unknown' && ( domBody( $a, 'crm-projects/get', [ 'id' => '00001' ] )['project']['id'] ?? '' ) === '00001' );

// Phase and favorite
check( 'a project is put into a phase and out of it; a phase that is none, a phase that is no text and a project that is none are refused', domBody( $a, 'crm-projects/phase', [ 'id' => $p1, 'phase' => 'mix' ] ) === [ 'id' => '00001', 'phase' => 'mix' ] && ( domBody( $a, 'crm-projects/get', [ 'id' => $p1 ] )['project']['phase'] ?? '' ) === 'mix'
	&& domAnswer( $a, 'crm-projects/phase', [ 'id' => $p1, 'phase' => 'nothing' ] ) === '400 crm_projects_phase' && domAnswer( $a, 'crm-projects/phase', [ 'id' => $p1, 'phase' => [ 'mix' ] ] ) === '400 crm_projects_phase' && domAnswer( $a, 'crm-projects/phase', [ 'id' => $p1 ] ) === '400 crm_projects_phase'
	&& domAnswer( $a, 'crm-projects/phase', [ 'id' => '99999', 'phase' => 'mix' ] ) === '404 crm_projects_unknown' && domBody( $a, 'crm-projects/phase', [ 'id' => $p1, 'phase' => '' ] )['phase'] === '' );
check( 'a project is a favorite and is not; on is a flag and nothing else', domBody( $a, 'crm-projects/favorite', [ 'id' => $p1, 'on' => true ] ) === [ 'id' => '00001', 'favorite' => true ] && ( domBody( $a, 'crm-projects/list', [ 'client' => (string) $ada ] )['projects'][1]['favorite'] ?? null ) === true
	&& domAnswer( $a, 'crm-projects/favorite', [ 'id' => $p1, 'on' => 'yes' ] ) === '400 crm_projects_format' && domAnswer( $a, 'crm-projects/favorite', [ 'id' => '99999', 'on' => true ] ) === '404 crm_projects_unknown' );
check( 'a phase a project is in is not deleted, and says how many projects are in it; one nobody is in goes', ( static function() use ( &$a, $p1 ): bool {
	domBody( $a, 'crm-projects/phase', [ 'id' => $p1, 'phase' => 'review' ] );
	[ $status, $body ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-phases', 'uri' => 'review', 'locale' => 'de_DE' ] );
	$gone = domAnswer( $a, 'elements/delete', [ 'type' => 'crm-phases', 'uri' => 'master', 'locale' => 'de_DE' ] );

	return $status === 409 && $body['code'] === 'crm_phase_used' && $body['params'] === [ 1 ] && $gone === '200 -' && count( \Nino\Modules\Crm\Projects::phaseKeys( $a ) ) === 3;
} )() );
check( 'a phase that was deleted does not come back with the unit applied again', ( static function() use ( &$a ): bool {
	\Nino\Features::activate( $a, 'crm' );
	ninoWarnings();

	return in_array( 'master', \Nino\Modules\Crm\Projects::phaseKeys( $a ), true ) === false;
} )() );

// The saves of a phase
foreach( [ 'a key with a capital' => [ 'Mastering', '400 crm_phase_key' ], 'a key with a blank' => [ 'two words', '400 crm_phase_key' ], 'a key of 31 characters' => [ str_repeat( 'a', 31 ), '400 crm_phase_key' ], 'a key that is a path' => [ '../x', '400 crm_phase_key' ] ] as $label => [ $key, $answer ] )
	check( 'a new phase with '. $label. ' is refused: '. $answer, domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => $key, 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'order' => 5 ] ] ) === $answer );
check( 'the title of a phase is one line of at most sixty characters, the order a whole number from 0 to 999999', domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'en_US', 'isNew' => true, 'fields' => [ 'title' => str_repeat( 't', 61 ) ] ] ) === '400 crm_phase_title'
	&& domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'en_US', 'isNew' => true, 'fields' => [ 'title' => "a\nb" ] ] ) === '400 crm_phase_title' && domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'en_US', 'isNew' => true, 'fields' => [ 'title' => [ 'a' ] ] ] ) === '400 crm_phase_title'
	&& domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'order' => -1 ] ] ) === '400 crm_phase_order' && domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'order' => 1000000 ] ] ) === '400 crm_phase_order'
	&& domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'order' => '5' ] ] ) === '400 crm_phase_order' && domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'qa', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Q A', 'order' => 25 ] ] ) === '200 -' );
check( 'the kernel vetoes the same in the Elements panel itself: a phase with a key that is none is no phase wherever it is written, and an element with a path in its name is no element for the panel', ( static function() use ( &$a ): bool {
	$errors = ninoErrors( static function() use ( &$a, &$result ): void { $result = \Nino\Elements::insertElement( $a, '/crm-phases/Bad Key', [ 'order' => 1, 'title' => 'x' ], 'de_DE' ); } );

	return $result !== true && is_array( $result ) === false && count( $errors ) >= 1 && domAnswer( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'a/b', 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'order' => 1 ] ] ) === '400 crm_elements_uri';
} )() );

// A project a package holds is not deleted and keeps its client
$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::HOLDS] = [ 'merge' => [ 'holds' => [ [ 'key' => 'crm-probe', 'count' => 2, 'delete' => true, 'client' => true, 'why' => "two things\nname it", 'names' => 'Things', 'delete_code' => 'crm_probe_held', 'client_code' => 'Bad Code' ] ] ] ];
[ $status, $body ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $p3, 'locale' => 'de_DE' ] );
check( 'a project a package holds is not deleted: 409, the sentence of the hold as one line, the code the package gave, the count as its parameter', $status === 409 && $body['code'] === 'crm_probe_held' && $body['params'] === [ 2 ] && $body['error'] === 'two things name it' && \Nino\Modules\Crm\Projects::get( $a, $p3 ) !== null );
[ $status, $body ] = domPost( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => $p3, 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'client' => 'K-0003' ] ] );
check( 'its client does not change while it holds: 409 at the field client, with the code of the core where the package gave none that is one, and the key and the count as parameters', $status === 409 && $body['code'] === 'crm_project_client_held' && $body['params'] === [ 'crm-probe', 2 ] && $body['field'] === 'client' && \Nino\Modules\Crm\Projects::get( $a, $p3 )['client'] === 'K-0002' );
check( 'other fields of the project change all the same', domAnswer( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => $p3, 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'title' => 'Third, renamed' ] ] ) === '200 -' );
$denied = ninoErrors( static function() use ( &$a, $p3, &$result ): void { $result = \Nino\Elements::deleteElement( $a, '/crm-projects/'. $p3, '*' ); } );
check( 'the kernel vetoes the deletion wherever it is asked, not at the endpoint alone, and the veto names what holds - in the language of the request, from the words of the feature', $result !== true && str_contains( implode( ' ', array_column( $denied, 1 ) ), 'Things nennen das Projekt' ) === true );
$a['./crm/surface/texts/locale'] = 'en_US';
unset( $a['./crm/surface/texts/words'] );
$denied = ninoErrors( static function() use ( &$a, $p3, &$result ): void { $result = \Nino\Elements::deleteElement( $a, '/crm-projects/'. $p3, '*' ); } );
check( '...and in English where the request is English', $result !== true && str_contains( implode( ' ', array_column( $denied, 1 ) ), 'Things name the project' ) === true && str_contains( implode( ' ', array_column( $denied, 1 ) ), 'nennen' ) === false );
unset( $a['./crm/surface/texts/locale'], $a['./crm/surface/texts/words'] );
unset( $a['./crm-probe/steer'] );
check( 'the holds are asked on their own: one listener that answers with nonsense takes nothing from the others, and a project nobody holds is deleted', ( static function() use ( &$a, $p3 ): bool {
	$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::HOLDS] = [ 'merge' => [ 'holds' => [ [ 'key' => 'Bad Key', 'count' => 1, 'delete' => true ], [ 'key' => 'ok', 'count' => 0, 'delete' => true ], [ 'key' => 'fine', 'count' => 1, 'delete' => false, 'client' => false ], 'text' ] ] ];
	$held = \Nino\Modules\Crm\Projects::holds( $a, $p3 );
	unset( $a['./crm-probe/steer'] );

	return array_column( $held, 'key' ) === [ 'fine' ] && \Nino\Modules\Crm\Projects::held( $a, $p3, 'delete' ) === null;
} )() );

// A record names a project: it stays
\Nino\Modules\Crm\Records::add( $a, [ 'kind' => 'probe-visit', 'client_id' => $bea, 'origin' => 'crm-probe', 'member' => [ 'email' => 'v@example.org', 'name' => 'V' ], 'ref' => 'v1', 'shown' => 'text', 'project' => $p3 ] );
check( 'a project that a record names is not deleted, in the endpoint and in the kernel: 409 crm_project_records', domAnswer( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $p3, 'locale' => 'de_DE' ] ) === '409 crm_project_records' && \Nino\Elements::deleteElement( $a, '/crm-projects/'. $p3, '*' ) !== true );
ninoWarnings();

// A project a stranger names
check( 'a project that is deleted takes its state, its releases, its tasks and its entries of the logbook with it, and the packages are told it is gone; an entry that names the client stays with the client', ( static function() use ( &$a, $p2, $ada ): bool {
	domBody( $a, 'crm-projects/phase', [ 'id' => $p2, 'phase' => 'mix' ] );
	domBody( $a, 'crm-tasks/add', [ 'project' => $p2, 'text' => 'a task' ] );
	domBody( $a, 'crm-projects/share', [ 'id' => $p2, 'client' => '3' ] );
	$entries = domCount( $a, 'logbook', 'project = \'00002\'' );
	$gone = domAnswer( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $p2, 'locale' => 'de_DE' ] );

	return $gone === '200 -' && $entries >= 2 && domCount( $a, 'project_states', 'project = \'00002\'' ) === 0 && domCount( $a, 'project_shares', 'project = \'00002\'' ) === 0 && domCount( $a, 'tasks', 'project = \'00002\'' ) === 0
		&& domCount( $a, 'logbook', 'project = \'00002\' AND client_id IS NULL' ) === 0 && domCount( $a, 'logbook', 'project = \'00002\' AND kind = \'project-shared\'' ) === 0 && domCount( $a, 'logbook', 'project = \'00002\' AND kind = \'project-added\'' ) === 1
		&& ( end( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::REMOVED] ) ?? null ) === [ 'project' => '00002' ];
} )() );


// The releases of a project to other clients
$s1 = domProject( $a, 'K-0001', 'Shared one' );
check( 'a project is released to a client: created, and the releases are listed with the client\'s title and who made it; the same again changes nothing and says so', ( static function() use ( &$a, $s1, $bea ): bool {
	$first = domBody( $a, 'crm-projects/share', [ 'id' => $s1, 'client' => (string) $bea ] );
	$again = domBody( $a, 'crm-projects/share', [ 'id' => $s1, 'client' => $bea ] );

	return $first['created'] === true && $again['created'] === false && count( $first['shares'] ) === 1 && $first['shares'][0]['title'] === 'Bea' && $first['shares'][0]['source'] === 'crm' && $first['shares'][0]['by'] === 'admin@example.org' && $first['owner'] === 1
		&& domCount( $a, 'logbook', 'kind = \'project-shared\' AND project = \''. $s1. '\' AND client_id = '. $bea ) === 1;
} )() );
check( 'a project is not released to its own client (409 crm_projects_owner), to a client that is none (404 crm_projects_client), with an id that is none (400), and a release that is none is not ended (removed false) or taken over (404 crm_projects_none)',
	domAnswer( $a, 'crm-projects/share', [ 'id' => $s1, 'client' => '1' ] ) === '409 crm_projects_owner' && domAnswer( $a, 'crm-projects/share', [ 'id' => $s1, 'client' => '99999' ] ) === '404 crm_projects_client' && domAnswer( $a, 'crm-projects/share', [ 'id' => $s1, 'client' => 'x' ] ) === '400 crm_projects_client_id'
	&& domAnswer( $a, 'crm-projects/share', [ 'id' => '99999', 'client' => '2' ] ) === '404 crm_projects_unknown' && domBody( $a, 'crm-projects/unshare', [ 'id' => $s1, 'client' => (string) $cleo ] )['removed'] === false && domAnswer( $a, 'crm-projects/keep', [ 'id' => $s1, 'client' => (string) $cleo ] ) === '404 crm_projects_none' );
check( 'the clients a project is released to see it in their own list, as a guest, and it is no part of the projects they own', ( static function() use ( &$a, $s1, $bea ): bool {
	$list = domBody( $a, 'crm-projects/list', [ 'client' => (string) $bea ] );

	return array_column( $list['shared'], 'id' ) === [ $s1 ] && $list['shared'][0]['owner'] === 'K-0001' && array_column( $list['projects'], 'id' ) === [ '00003' ] && \Nino\Modules\Crm\Projects::shared( $a, $bea, $s1 )['guest'] === true && \Nino\Modules\Crm\Projects::shared( $a, $bea, $s1 )['phase'] === '';
} )() );
check( 'a release a package made is taken over by the owner: the source becomes crm; one that is the owner\'s already says kept false', ( static function() use ( &$a, $s1, $cleo ): bool {
	\Nino\Modules\Crm\Projects::share( $a, $s1, $cleo, [ 'source' => 'crm-probe', 'ref' => 'r-1', 'by' => 'system' ] );
	$kept = domBody( $a, 'crm-projects/keep', [ 'id' => $s1, 'client' => (string) $cleo ] );
	$again = domBody( $a, 'crm-projects/keep', [ 'id' => $s1, 'client' => (string) $cleo ] );
	$row = \Nino\Modules\Crm\Projects::sharesOf( $a, $s1, $cleo )[0];

	return $kept['kept'] === true && $again['kept'] === false && $row['source'] === 'crm' && $row['ref'] === '';
} )() );
check( 'a package releases a project in its own name, never in the name of the core; a source that is none, a reference with a control character and an account that is no line are refused', ( static function() use ( &$a, $s1, $ada ): bool {
	return \Nino\Modules\Crm\Projects::share( $a, $s1, $ada, [ 'source' => 'crm' ] ) === 'source' && \Nino\Modules\Crm\Projects::share( $a, $s1, $ada, [ 'source' => 'Bad Source' ] ) === 'source' && \Nino\Modules\Crm\Projects::share( $a, $s1, $ada, [ 'source' => 'crm-probe', 'ref' => "a\nb" ] ) === 'ref'
		&& \Nino\Modules\Crm\Projects::share( $a, $s1, $ada, [ 'source' => 'crm-probe', 'by' => "a\0" ] ) === 'by' && \Nino\Modules\Crm\Projects::share( $a, '../x', $ada, [ 'source' => 'crm-probe' ] ) === 'project';
} )() );
check( 'a release ends: removed, the logbook says so, and a project that changes its client to the client it was released to ends that release and keeps the others', ( static function() use ( &$a, $s1, $bea, $cleo ): bool {
	$ended = domBody( $a, 'crm-projects/unshare', [ 'id' => $s1, 'client' => (string) $bea ] );
	$moved = domAnswer( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => $s1, 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'client' => 'K-0003' ] ] );

	return $ended['removed'] === true && domCount( $a, 'logbook', 'kind = \'project-unshared\' AND project = \''. $s1. '\'' ) === 2 && $moved === '200 -' && \Nino\Modules\Crm\Projects::sharesOf( $a, $s1 ) === [] && domCount( $a, 'logbook', 'kind = \'project-unshared\' AND project = \''. $s1. '\'' ) === 2;
} )() );

// A project that a package makes, and the places the packages enter at
$ids = [];
check( 'a package makes a project for a client: the project is the screen\'s, the entry of the logbook is the package\'s own with the sentence it gave, and the packages are told', ( static function() use ( &$a, $ada, &$ids ): bool {
	$made = \Nino\Modules\Crm\Projects::add( $a, $ada, [ 'title' => 'From a package', 'description' => 'd', 'started' => '2026-01-31' ], [ 'source' => 'crm-probe', 'text' => 'The probe made it', 'ref' => 'order-7', 'by' => 'system' ] );
	$ids[] = $made['project']['id'];
	$entry = \Nino\Modules\Crm\Db::row( $a, 'SELECT * FROM logbook WHERE project = :project AND kind = \'project-added\'', [ 'project' => $made['project']['id'] ] );

	return is_array( $made ) === true && $made['project']['title'] === 'From a package' && $made['project']['started'] === '2026-01-31' && $entry['source'] === 'crm-probe' && $entry['text'] === 'The probe made it' && $entry['ref'] === 'order-7' && (int) $entry['client_id'] === $ada
		&& domCount( $a, 'logbook', 'project = \''. $made['project']['id']. '\'' ) === 1 && end( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::ADDED] )['project'] === $made['project']['id'];
} )() );
$bad = [
	'source' => [ [ 'source' => 'crm', 'text' => 'x' ], [ 'title' => 'T' ] ], 'text' => [ [ 'source' => 'crm-probe' ], [ 'title' => 'T' ] ], 'ref' => [ [ 'source' => 'crm-probe', 'text' => 'x', 'ref' => "a\nb" ], [ 'title' => 'T' ] ], 'by' => [ [ 'source' => 'crm-probe', 'text' => 'x', 'by' => "a\0" ], [ 'title' => 'T' ] ],
	'title' => [ [ 'source' => 'crm-probe', 'text' => 'x' ], [ 'title' => "a\nb" ] ], 'description' => [ [ 'source' => 'crm-probe', 'text' => 'x' ], [ 'title' => 'T', 'description' => str_repeat( 'd', 2001 ) ] ],
	'started' => [ [ 'source' => 'crm-probe', 'text' => 'x' ], [ 'title' => 'T', 'started' => '2026-02-31' ] ],
];

foreach( $bad as $why => [ $by, $fields ] )
	check( 'a project of a package with a '. $why. ' that is not what is asked is refused with '. $why. ', and none is made', \Nino\Modules\Crm\Projects::add( $a, $ada, $fields, $by ) === $why && count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) ) === 4 );
check( 'a project for a client that is none or that is switched off is refused', \Nino\Modules\Crm\Projects::add( $a, 99999, [ 'title' => 'T' ], [ 'source' => 'crm-probe', 'text' => 'x' ] ) === 'client' && ( static function() use ( &$a ): bool { domBody( $a, 'crm-clients/active', [ 'id' => 3, 'active' => false ] ); $r = \Nino\Modules\Crm\Projects::add( $a, 3, [ 'title' => 'T' ], [ 'source' => 'crm-probe', 'text' => 'x' ] ); domBody( $a, 'crm-clients/active', [ 'id' => 3, 'active' => true ] ); return $r === 'inactive'; } )() );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::ADDED] = [ 'merge' => [ 'refusal' => [ 'status' => 409, 'message' => 'the probe says no', 'code' => 'crm_probe_refused', 'params' => [ 7 ], 'field' => 'title' ] ] ];
check( 'a package that refuses the project it is told of takes it back: the answer is "refused", nothing is left of the element or the entries of the logbook', ( static function() use ( &$a, $ada ): bool {
	$count = count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) );
	$logs = domCount( $a, 'logbook' );

	return \Nino\Modules\Crm\Projects::add( $a, $ada, [ 'title' => 'Refused' ], [ 'source' => 'crm-probe', 'text' => 'x' ] ) === 'refused' && count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) ) === $count && domCount( $a, 'logbook' ) === $logs;
} )() );
[ $status, $body ] = domPost( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Screen', 'client' => 'K-0001' ], 'packages' => [ 'crm-probe' => [ 'folder' => true ] ] ] );
check( 'the same for the screen: the refusal is the answer at the field it names, with its code and parameters, and the project is gone', $status === 409 && $body['code'] === 'crm_probe_refused' && $body['params'] === [ 7 ] && $body['field'] === 'title' && count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) ) === 4
	&& end( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::ADDED] )['posted'] === [ 'crm-probe' => [ 'folder' => true ] ] );
unset( $a['./crm-probe/steer'] );

$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::FIGURES] = [ 'merge' => [ 'figures' => [ '00001' => [ 'open' => 2, 'late' => true, 'note' => "a\nb", 'id' => 'taken', 'Bad Name' => 1, 'list' => [ 1 ] ], '99999' => [ 'open' => 1 ] ] ] ];
$row = domBody( $a, 'crm-projects/list', [ 'client' => (string) $ada ] )['projects'];
$figured = array_values( array_filter( $row, static fn( array $project ): bool => $project['id'] === '00001' ) )[0] ?? [];
check( 'the figures a package adds to the rows of a list are small values under names of their own, never over a field of the row, and only for projects of the list', ( $figured['open'] ?? null ) === 2 && ( $figured['late'] ?? null ) === true && ( $figured['note'] ?? null ) === 'a b' && $figured['id'] === '00001' && isset( $figured['Bad Name'] ) === false && isset( $figured['list'] ) === false
	&& array_column( $row, 'open' ) !== [] );
unset( $a['./crm-probe/steer'] );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::WITHHELD] = [ 'merge' => [ 'withheld' => [ [ 'key' => 'crm-probe', 'id' => 7, 'label' => 'Waits for the client', 'detail' => [ 'a' => 1 ] ] ] ] ];
$waiting = domBody( $a, 'crm-projects/get', [ 'id' => '00001' ] )['waiting'] ?? null;
check( 'what a package holds back from the client of a project is told with its id and its label', $waiting === [ 'id' => 7, 'number' => 'Waits for the client' ] );
unset( $a['./crm-probe/steer'] );

// The sentences of the panel that the core turns into codes, each asked as a request
domSignIn( $a, 'scoped@example.org' );
check( 'an account that is described in detail for a type may change the fields it was given and none else: the panel says it with its own code, and an account that may not add is told in a sentence that is coded', domAnswer( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '00001', 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'description' => 'x' ] ] ) === '403 elements_fields_not_allowed'
	&& domAnswer( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'x', 'client' => 'K-0001' ] ] ) === '403 crm_elements_denied' && domAnswer( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => '00001', 'locale' => 'de_DE' ] ) === '403 crm_elements_denied' );
domSignIn( $a, 'admin@example.org' );
$sentences = [
	'a locale that is none' => [ 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'zz', 'locale' => 'xx_XX', 'isNew' => true, 'fields' => [ 'title' => 'x' ] ], '400 crm_elements_locale' ],
	'a uri that is a path' => [ 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'a/b', 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'order' => 1 ] ], '400 crm_elements_uri' ],
	'a uri to delete that is a path' => [ 'elements/delete', [ 'type' => 'crm-phases', 'uri' => 'a/b', 'locale' => 'de_DE' ], '400 crm_elements_uri' ],
	'an element that is not there, changed' => [ 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'nowhere', 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'order' => 1 ] ], '400 crm_elements_unknown' ],
	'an element that is not there, read' => [ 'elements/get', [ 'type' => 'crm-phases', 'uri' => 'nowhere', 'locale' => 'de_DE' ], '404 crm_elements_unknown' ],
	'an element that exists, made again' => [ 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'mix', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Mix', 'order' => 1 ] ], '400 crm_elements_exists' ],
	'a value of the wrong type' => [ 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'mix', 'locale' => 'de_DE', 'isNew' => false, 'fields' => [ 'order' => 'x' ] ], '400 crm_phase_order' ],
	'a type that is entered and has no file' => [ 'elements/list', [ 'type' => 'crm-probe-values' ], '404 crm_elements_unknown' ],
];

foreach( $sentences as $label => [ $action, $data, $answer ] )
	check( 'the sentence of the panel for '. $label. ' has the code '. $answer, domAnswer( $a, $action, $data ) === $answer );
check( 'the field of such a refusal is the one it is about, so that the screen says it at the field', ( static function() use ( &$a ): bool {
	[ , $body ] = domPost( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'mix', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Mix', 'order' => 1 ] ] );
	[ , $other ] = domPost( $a, 'elements/save', [ 'type' => 'crm-phases', 'uri' => 'xx', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'order' => 1 ] ] );

	return ( $body['field'] ?? '' ) === 'uri' && ( $other['field'] ?? '' ) === 'title' && ( $other['code'] ?? '' ) === 'elements_missing_key';
} )() );
}
catch( \Throwable $e ) {
	domCrashed( 'the projects', $e );
}

// --- The tasks --------------------------------------------------------------------------------------------------------------

echo "The tasks\n";

try {

[ $project, $where, $a ] = domWorld( 'tasks' );
domSignIn( $a, 'crm@example.org' );
$ada = domClient( $a, [ 'name' => 'Ada' ] );
domSignIn( $a, 'admin@example.org' );
$p1 = domProject( $a, 'K-0001', 'One' );
$p2 = domProject( $a, 'K-0001', 'Two' );
domSignIn( $a, 'crm@example.org' );

$list = domBody( $a, 'crm-tasks/add', [ 'project' => $p1, 'text' => 'first' ] );
domBody( $a, 'crm-tasks/add', [ 'project' => $p1, 'text' => "second\r\nline\ttab" ] );
$list = domBody( $a, 'crm-tasks/add', [ 'project' => $p1, 'text' => 'third' ] );
check( 'a task is made at the top of the open ones; the answer is the whole list of the project, with how many are open', array_column( $list['tasks'], 'text' ) === [ 'third', "second\nline tab", 'first' ] && $list['open'] === 3 && $list['project'] === '00001' && array_keys( $list['tasks'][0] ) === [ 'id', 'text', 'done', 'done_at', 'created_at', 'source', 'ref' ]
	&& $list['tasks'][0]['source'] === 'manual' && $list['tasks'][0]['ref'] === '' );
check( 'the text of a task: line breaks are one kind, a tab is a blank, the ends are trimmed', ( domBody( $a, 'crm-tasks/add', [ 'project' => $p1, 'text' => "  \n x \n  " ] )['tasks'][0]['text'] ?? '' ) === 'x' );
$text = [ 'an empty text' => [ '', '400 crm_tasks_empty' ], 'blanks' => [ "  \n\t ", '400 crm_tasks_empty' ], 'a control character' => [ "a\x01", '400 crm_tasks_format' ], 'a null byte' => [ "a\0b", '400 crm_tasks_format' ], 'a mark that turns the text' => [ "a\u{202E}b", '400 crm_tasks_format' ],
	'a text of 4001 characters' => [ str_repeat( 'a', 4001 ), '400 crm_tasks_long' ], 'a list' => [ [ 'a' ], '400 crm_tasks_format' ], 'a number' => [ 7, '400 crm_tasks_format' ], 'nothing' => [ null, '400 crm_tasks_format' ] ];

foreach( $text as $label => [ $value, $answer ] )
	check( 'a task made of '. $label. ' is refused: '. $answer, domAnswer( $a, 'crm-tasks/add', [ 'project' => $p1, 'text' => $value ] ) === $answer );

check( 'a text of 4000 characters is the longest a task takes, and is kept whole', ( domBody( $a, 'crm-tasks/add', [ 'project' => $p1, 'text' => str_repeat( 'ä', 4000 ) ] )['tasks'][0]['text'] ?? '' ) === str_repeat( 'ä', 4000 ) );
foreach( [ 'no project' => [], 'an empty project' => [ 'project' => '' ], 'a path' => [ 'project' => '../00001' ], 'a null byte' => [ 'project' => "0000\0" ], 'a list' => [ 'project' => [ $p1 ] ], 'a project of 101 characters' => [ 'project' => str_repeat( '1', 101 ) ] ] as $label => $data )
	check( 'a task for '. $label. ' is the 400 crm_tasks_project', domAnswer( $a, 'crm-tasks/add', $data + [ 'text' => 'x' ] ) === '400 crm_tasks_project' && domAnswer( $a, 'crm-tasks/list', $data ) === '400 crm_tasks_project' );
check( 'a project that is none is the 404 crm_tasks_unknown', domAnswer( $a, 'crm-tasks/add', [ 'project' => '99999', 'text' => 'x' ] ) === '404 crm_tasks_unknown' && domAnswer( $a, 'crm-tasks/list', [ 'project' => '99999' ] ) === '404 crm_tasks_unknown' );

$ids = array_column( domBody( $a, 'crm-tasks/list', [ 'project' => $p1 ] )['tasks'], 'id' );
$first = (int) end( $ids );
check( 'the text of a task is changed, the id of a task is looked at as every id is, and a task that is none is the 404', ( domBody( $a, 'crm-tasks/text', [ 'id' => $first, 'text' => 'first, changed' ] )['tasks'] ?? [] ) !== [] && \Nino\Modules\Crm\Db::row( $a, 'SELECT text FROM tasks WHERE id = :id', [ 'id' => $first ] )['text'] === 'first, changed'
	&& domAnswer( $a, 'crm-tasks/text', [ 'id' => '99999', 'text' => 'x' ] ) === '404 crm_tasks_unknown' && domAnswer( $a, 'crm-tasks/text', [ 'id' => 'x', 'text' => 'x' ] ) === '400 crm_tasks_unknown' && domAnswer( $a, 'crm-tasks/text', [ 'id' => $first, 'text' => '' ] ) === '400 crm_tasks_empty' );
$taskCount = domCount( $a, 'tasks' );

foreach( [ 'crm-tasks/text', 'crm-tasks/done', 'crm-tasks/move', 'crm-tasks/delete' ] as $action )
	foreach( [ 'no id' => [], 'a negative id' => [ 'id' => '-1' ], 'an id with a leading zero' => [ 'id' => '01' ], 'a path' => [ 'id' => '../1' ], 'a null byte' => [ 'id' => "1\0" ], 'a list' => [ 'id' => [ 1 ] ], 'eleven digits' => [ 'id' => '12345678901' ] ] as $label => $data )
		check( $action. ' with '. $label. ' is the 400 crm_tasks_unknown and changes nothing', domAnswer( $a, $action, $data + [ 'text' => 'x', 'on' => true, 'dir' => 'up' ] ) === '400 crm_tasks_unknown' && domCount( $a, 'tasks' ) === $taskCount );

check( 'a task is done and opened again; opened again it is the top of the open ones; on is a flag', ( static function() use ( &$a, $p1, $first ): bool {
	$done = domBody( $a, 'crm-tasks/done', [ 'id' => $first, 'on' => true ] );
	$again = domBody( $a, 'crm-tasks/done', [ 'id' => $first, 'on' => false ] );

	return $done['open'] === 4 && $done['tasks'][count( $done['tasks'] ) - 1]['done'] === true && $done['tasks'][count( $done['tasks'] ) - 1]['done_at'] !== '' && $again['tasks'][0]['id'] === $first && $again['open'] === 5
		&& domAnswer( $a, 'crm-tasks/done', [ 'id' => $first, 'on' => 'yes' ] ) === '400 crm_tasks_format' && domAnswer( $a, 'crm-tasks/done', [ 'id' => '99999', 'on' => true ] ) === '404 crm_tasks_unknown';
} )() );
check( 'an open task swaps with its neighbour; the first and the last are the edge (409), a direction that is none is the 400, and a done task has no place to move', ( static function() use ( &$a, $p1 ): bool {
	$order = array_column( domBody( $a, 'crm-tasks/list', [ 'project' => $p1 ] )['tasks'], 'id' );
	$moved = array_column( domBody( $a, 'crm-tasks/move', [ 'id' => $order[1], 'dir' => 'up' ] )['tasks'], 'id' );
	$top = domAnswer( $a, 'crm-tasks/move', [ 'id' => $moved[0], 'dir' => 'up' ] );
	$bottom = domAnswer( $a, 'crm-tasks/move', [ 'id' => end( $moved ), 'dir' => 'down' ] );
	$doneId = $moved[2];
	domBody( $a, 'crm-tasks/done', [ 'id' => $doneId, 'on' => true ] );

	return $moved[0] === $order[1] && $moved[1] === $order[0] && $top === '409 crm_tasks_edge' && $bottom === '409 crm_tasks_edge' && domAnswer( $a, 'crm-tasks/move', [ 'id' => $doneId, 'dir' => 'up' ] ) === '409 crm_tasks_edge'
		&& domAnswer( $a, 'crm-tasks/move', [ 'id' => $moved[0], 'dir' => 'sideways' ] ) === '400 crm_tasks_format' && domAnswer( $a, 'crm-tasks/move', [ 'id' => '99999', 'dir' => 'up' ] ) === '404 crm_tasks_unknown';
} )() );
check( 'a task is deleted, once: the second press is the 404', ( static function() use ( &$a, $p1 ): bool {
	$id = (int) domBody( $a, 'crm-tasks/list', [ 'project' => $p1 ] )['tasks'][0]['id'];

	return domAnswer( $a, 'crm-tasks/delete', [ 'id' => $id ] ) === '200 -' && domAnswer( $a, 'crm-tasks/delete', [ 'id' => $id ] ) === '404 crm_tasks_unknown';
} )() );
$open = domBody( $a, 'crm-tasks/open' );
check( 'the app lists the projects with open tasks and, apart, the ones that are not finished and have none; a project that is done and has none is left out', array_column( $open['projects'], 'id' ) === [ '00001' ] && array_column( $open['others'], 'id' ) === [ '00002' ] && $open['projects'][0]['label'] === 'P-0001' && $open['projects'][0]['client_title'] === 'Ada' && $open['projects'][0]['open'] === count( array_filter( domBody( $a, 'crm-tasks/list', [ 'project' => $p1 ] )['tasks'], static fn( array $task ): bool => $task['done'] === false ) ) );

\Nino\Modules\Crm\Db::exec( $a, 'WITH RECURSIVE n( i ) AS ( SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 1000 ) INSERT INTO tasks ( project, text, position, created_at, updated_at ) SELECT \'00002\', \'t\', i, \'x\', \'x\' FROM n LIMIT '. ( 1000 - 0 ) );
check( 'a project holds at most a thousand tasks: the next is the 409 crm_tasks_full with the number, and a package\'s task is refused with full as well', domAnswer( $a, 'crm-tasks/add', [ 'project' => $p2, 'text' => 'one too many' ] ) === '409 crm_tasks_full' && \Nino\Modules\Crm\Tasks::addFrom( $a, $p2, 'x', 'crm-probe', 'c-1', '' ) === 'full' );

// The task a package makes
check( 'a package makes a task of its own: its source and its reference are on it, and one reference is one task', ( static function() use ( &$a, $p1 ): bool {
	$made = \Nino\Modules\Crm\Tasks::addFrom( $a, $p1, "A comment\nof a client", 'crm-probe', 'comment:7', 'client@example.org' );
	$again = \Nino\Modules\Crm\Tasks::addFrom( $a, $p1, 'Another text', 'crm-probe', 'comment:7', '' );
	$row = \Nino\Modules\Crm\Db::row( $a, 'SELECT * FROM tasks WHERE source = \'crm-probe\'' );
	$listed = array_values( array_filter( domBody( $a, 'crm-tasks/list', [ 'project' => $p1 ] )['tasks'], static fn( array $task ): bool => $task['source'] === 'crm-probe' ) );

	return $made === '' && $again === 'taken' && $row['ref'] === 'comment:7' && $row['created_by'] === 'client@example.org' && count( $listed ) === 1 && $listed[0]['ref'] === 'comment:7' && $listed[0]['text'] === "A comment\nof a client";
} )() );
$refusals = [ 'a project that is none' => [ [ '99999', 'x', 'crm-probe', 'r' ], 'project' ], 'a project that is a path' => [ [ '../1', 'x', 'crm-probe', 'r' ], 'project' ], 'a text that is empty' => [ [ '00001', '  ', 'crm-probe', 'r' ], 'text' ], 'a text with a null byte' => [ [ '00001', "a\0", 'crm-probe', 'r' ], 'text' ],
	'a source that is manual' => [ [ '00001', 'x', 'manual', 'r' ], 'source' ], 'the source of the core' => [ [ '00001', 'x', 'crm', 'r' ], 'source' ], 'a source that is no slug' => [ [ '00001', 'x', 'Not A Slug', 'r' ], 'source' ], 'a reference with a blank' => [ [ '00001', 'x', 'crm-probe', 'a b' ], 'ref' ],
	'no reference' => [ [ '00001', 'x', 'crm-probe', '' ], 'ref' ], 'a reference of 101 characters' => [ [ '00001', 'x', 'crm-probe', str_repeat( 'r', 101 ) ], 'ref' ], 'an account with a line break' => [ [ '00001', 'x', 'crm-probe', 'r', "a\nb" ], 'by' ] ];

foreach( $refusals as $label => [ $args, $why ] )
	check( 'a task of a package with '. $label. ' is refused with "'. $why. '"', \Nino\Modules\Crm\Tasks::addFrom( $a, $args[0], $args[1], $args[2], $args[3], $args[4] ?? '' ) === $why );
check( 'a task is a task of the project that is deleted with it', ( static function() use ( &$a, $p1 ): bool {
	$before = domCount( $a, 'tasks', 'project = \''. $p1. '\'' );
	domSignIn( $a, 'admin@example.org' );
	domAnswer( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $p1, 'locale' => 'de_DE' ] );

	return $before > 0 && domCount( $a, 'tasks', 'project = \''. $p1. '\'' ) === 0 && domCount( $a, 'tasks', 'project = \'00002\'' ) === 1000;
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'the tasks', $e );
}

// --- The logbook ------------------------------------------------------------------------------------------------------------

echo "The logbook\n";

try {

[ $project, $where, $a ] = domWorld( 'logbook' );
domSignIn( $a, 'admin@example.org' );
$ada = domClient( $a, [ 'name' => 'Ada' ] );
$bea = domClient( $a, [ 'name' => 'Bea' ] );
$p1 = domProject( $a, 'K-0001', 'One' );

check( 'the line of the owner is written into the logbook of a client, with the account and the time; the answer is the entry as the list has it', ( static function() use ( &$a, $ada ): bool {
	$entry = domBody( $a, 'crm-logbook/add', [ 'client' => (string) $ada, 'text' => 'Called on the phone' ] )['entry'] ?? [];

	return array_keys( $entry ) === [ 'id', 'at', 'source', 'kind', 'text', 'by', 'data', 'project', 'project_label', 'project_known' ] && $entry['kind'] === 'note' && $entry['source'] === 'crm' && $entry['by'] === 'admin@example.org' && $entry['text'] === 'Called on the phone' && $entry['project'] === ''
		&& preg_match( '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/D', $entry['at'] ) === 1;
} )() );
check( 'a line for a project takes the client of the project, and shows the project by its number and title', ( static function() use ( &$a, $p1, $ada ): bool {
	$entry = domBody( $a, 'crm-logbook/add', [ 'project' => $p1, 'text' => 'Mix approved' ] )['entry'] ?? [];

	return $entry['project'] === '00001' && $entry['project_label'] === 'P-0001 One' && $entry['project_known'] === true && \Nino\Modules\Crm\Db::row( $a, 'SELECT client_id FROM logbook WHERE id = :id', [ 'id' => $entry['id'] ] )['client_id'] === $ada;
} )() );

foreach( [ 'both' => [ 'client' => '1', 'project' => '00001' ], 'neither' => [], 'a client that is no id' => [ 'client' => 'x' ], 'a client of eleven digits' => [ 'client' => '12345678901' ], 'a project that is a path' => [ 'project' => '../1' ], 'a client that is a list' => [ 'client' => [ 1 ] ], 'a project that is a list' => [ 'project' => [ '00001' ] ] ] as $label => $scope )
	check( 'crm-logbook/list and add with '. $label. ' is the 400 crm_logbook_scope', domAnswer( $a, 'crm-logbook/list', $scope ) === '400 crm_logbook_scope' && domAnswer( $a, 'crm-logbook/add', $scope + [ 'text' => 'x' ] ) === '400 crm_logbook_scope' );
check( 'a client or a project that is none is the 404 crm_logbook_unknown', domAnswer( $a, 'crm-logbook/list', [ 'client' => '99999' ] ) === '404 crm_logbook_unknown' && domAnswer( $a, 'crm-logbook/list', [ 'project' => '99999' ] ) === '404 crm_logbook_unknown' && domAnswer( $a, 'crm-logbook/add', [ 'client' => '99999', 'text' => 'x' ] ) === '404 crm_logbook_unknown' );
$texts = [ 'an empty text' => [ '', '400 crm_logbook_empty' ], 'blanks that show nothing' => [ "\u{00A0}\u{200B}", '400 crm_logbook_format' ], 'a line break' => [ "a\nb", '400 crm_logbook_format' ], 'a control character' => [ "a\x07", '400 crm_logbook_format' ], 'a null byte' => [ "a\0", '400 crm_logbook_format' ],
	'a mark that turns the text' => [ "a\u{202E}b", '400 crm_logbook_format' ], 'a zero width space' => [ "a\u{200B}b", '400 crm_logbook_format' ], 'a text of 501 characters' => [ str_repeat( 'a', 501 ), '400 crm_logbook_long' ], 'a list' => [ [ 'a' ], '400 crm_logbook_format' ], 'a number' => [ 5, '400 crm_logbook_format' ] ];

foreach( $texts as $label => [ $value, $answer ] )
	check( 'a line of '. $label. ' is refused: '. $answer, domAnswer( $a, 'crm-logbook/add', [ 'client' => (string) $ada, 'text' => $value ] ) === $answer );
foreach( [ '<script>alert(1)</script>', '\'; DROP TABLE logbook; --', '{{7*7}}', '[[/project/company/general/name]]', '=1+1', '@x' ] as $evil )
	domBody( $a, 'crm-logbook/add', [ 'client' => (string) $ada, 'text' => $evil ] );
check( 'a line is kept as typed, and is answered as text: the logbook is there after the injections', array_slice( array_column( domBody( $a, 'crm-logbook/list', [ 'client' => (string) $ada ] )['entries'], 'text' ), 0, 6 ) === [ '@x', '=1+1', '[[/project/company/general/name]]', '{{7*7}}', '\'; DROP TABLE logbook; --', '<script>alert(1)</script>' ] );

// Pages
\Nino\Modules\Crm\Db::exec( $a, 'WITH RECURSIVE n( i ) AS ( SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 120 ) INSERT INTO logbook ( client_id, project, at, source, kind, text, data, ref, "by" ) SELECT '. $bea. ', \'\', \'2027-01-01T00:00:00Z\', \'crm\', \'note\', \'line \' || i, \'{}\', \'\', \'x\' FROM n' );
$page = domBody( $a, 'crm-logbook/list', [ 'client' => (string) $bea ] );
$next = domBody( $a, 'crm-logbook/list', [ 'client' => (string) $bea, 'before' => $page['entries'][49]['id'] ] );
$last = domBody( $a, 'crm-logbook/list', [ 'client' => (string) $bea, 'before' => $next['entries'][49]['id'] ] );
check( 'a page of the logbook is fifty entries, the newest first; before is the id of the last entry of the page before, and more says whether older entries are there', count( $page['entries'] ) === 50 && $page['more'] === true && count( $next['entries'] ) === 50 && $next['more'] === true && count( $last['entries'] ) === 21 && $last['more'] === false
	&& $page['entries'][0]['text'] === 'line 120' && $next['entries'][0]['text'] === 'line 70' && $last['entries'][19]['text'] === 'line 1' );
check( 'a page after an entry that is none, or after an id that is none, is the 400', domAnswer( $a, 'crm-logbook/list', [ 'client' => (string) $bea, 'before' => '99999' ] ) === '400 crm_logbook_scope' && domAnswer( $a, 'crm-logbook/list', [ 'client' => (string) $bea, 'before' => 'x' ] ) === '400 crm_logbook_scope' && domAnswer( $a, 'crm-logbook/list', [ 'client' => (string) $bea, 'before' => [ 1 ] ] ) === '400 crm_logbook_scope' );
check( 'the page of a client holds the releases of its projects to other clients, and the page of the project holds them too; a release of a project of another client is none of this client\'s entries', ( static function() use ( &$a, $p1, $bea ): bool {
	domBody( $a, 'crm-projects/share', [ 'id' => $p1, 'client' => (string) $bea ] );
	$mine = domBody( $a, 'crm-logbook/list', [ 'client' => '1' ] )['entries'];
	$project = domBody( $a, 'crm-logbook/list', [ 'project' => $p1 ] )['entries'];
	$hers = \Nino\Modules\Crm\Logbook::of( $a, [ 'client' => $bea ], null, 200 )['rows'];

	return count( array_filter( $mine, static fn( array $entry ): bool => $entry['kind'] === 'project-shared' ) ) === 1 && count( array_filter( $project, static fn( array $entry ): bool => $entry['kind'] === 'project-shared' ) ) === 1 && count( array_filter( $hers, static fn( array $entry ): bool => $entry['kind'] === 'project-shared' ) ) === 1;
} )() );

// What a package writes
check( 'a package writes an entry in its own name with the sentence it says; the core\'s own source is refused, and so is a kind that is none', ( static function() use ( &$a, $ada ): bool {
	$entry = \Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-probe', 'kind' => 'anything', 'text' => 'The probe says so', 'client_id' => $ada, 'data' => [ 'n' => 3 ], 'ref' => 'r1', 'by' => 'visitor' ] );

	return is_array( $entry ) === true && $entry['source'] === 'crm-probe' && \Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm', 'kind' => 'note', 'text' => 'x', 'client_id' => $ada ] ) === 'source' && \Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-probe', 'kind' => 'Bad Kind', 'text' => 'x', 'client_id' => $ada ] ) === 'kind';
} )() );
check( 'a package that entered a kind writes it without a sentence, and the values of that entry are for its script: the list gives them; an entry of the same kind of another source needs a sentence', ( static function() use ( &$a, $ada ): bool {
	$entry = \Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-probe', 'kind' => 'probe-happened', 'client_id' => $ada, 'data' => [ 'label' => 'A1', 'n' => 3 ] ] );
	$other = \Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-other', 'kind' => 'probe-happened', 'client_id' => $ada ] );
	$listed = domBody( $a, 'crm-logbook/list', [ 'client' => (string) $ada ] )['entries'];
	$mine = array_values( array_filter( $listed, static fn( array $one ): bool => $one['kind'] === 'probe-happened' ) )[0] ?? [];
	$said = array_values( array_filter( $listed, static fn( array $one ): bool => $one['kind'] === 'anything' ) )[0] ?? [];

	return is_array( $entry ) === true && $entry['text'] === '' && $other === 'text' && (array) $mine['data'] === [ 'label' => 'A1', 'n' => 3 ] && (array) $said['data'] === [];
} )() );
$bad = [ 'no target' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x' ], 'target' ], 'a client that is none' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 99999 ], 'client' ], 'a client that is a text' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => '1' ], 'client' ],
	'a project that is none' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'project' => '99999' ], 'project' ], 'a project of another client' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'project' => '00001', 'client_id' => 2 ], 'project' ],
	'a source that is none' => [ [ 'source' => 'Bad Source', 'kind' => 'k', 'text' => 'x', 'client_id' => 1 ], 'source' ], 'a source that is a number' => [ [ 'source' => 7, 'kind' => 'k', 'text' => 'x', 'client_id' => 1 ], 'source' ], 'a text that is a line break' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => "a\nb", 'client_id' => 1 ], 'text' ],
	'a text that is a number' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 5, 'client_id' => 1 ], 'text' ], 'values that are nested' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'data' => [ 'a' => [ 1 ] ] ], 'data' ], 'a name of a value that is a capital' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'data' => [ 'A' => 1 ] ], 'data' ],
	'values of 3000 bytes' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'data' => array_fill_keys( array_map( static fn( int $i ): string => 'k'. $i, range( 1, 15 ) ), str_repeat( 'v', 200 ) ) ], 'data' ],
	'a reference with a null byte' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'ref' => "a\0" ], 'ref' ], 'an account of 201 characters' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'by' => str_repeat( 'a', 201 ) ], 'by' ],
	'a time ahead of the clock' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 3600 ) ], 'at' ], 'a time that is none' => [ [ 'source' => 'crm-probe', 'kind' => 'k', 'text' => 'x', 'client_id' => 1, 'at' => '2026-02-31T00:00:00Z' ], 'at' ] ];

foreach( $bad as $label => [ $entry, $why ] )
	check( 'an entry with '. $label. ' is refused with "'. $why. '", and none is written', ( static function() use ( &$a, $entry, $why ): bool { $count = domCount( $a, 'logbook' ); return \Nino\Modules\Crm\Logbook::add( $a, $entry ) === $why && domCount( $a, 'logbook' ) === $count; } )() );
check( 'the kinds of a package: a name that is no word, the source of the core, a kind of the core and a kind another source has are refused; the same again is as good as the first time; a source that is all digits is none', ( static function() use ( &$a ): bool {
	$ok = \Nino\Modules\Crm\Logbook::addKinds( $a, [ 'probe-happened' ], [ 'package' => 'crm-probe' ] ) === true && ninoWarnings() === [];
	$refused = [ \Nino\Modules\Crm\Logbook::addKinds( $a, [ 'x-y' ], [ 'package' => 'crm' ] ), \Nino\Modules\Crm\Logbook::addKinds( $a, [ 'note' ], [ 'package' => 'crm-probe' ] ), \Nino\Modules\Crm\Logbook::addKinds( $a, [ 'probe-happened' ], [ 'package' => 'crm-other' ] ), \Nino\Modules\Crm\Logbook::addKinds( $a, [ 'Bad Kind' ], [ 'package' => 'crm-probe' ] ),
		\Nino\Modules\Crm\Logbook::addKinds( $a, [ 'digits' ], [ 'package' => '123' ] ), \Nino\Modules\Crm\Logbook::addKinds( $a, [], [ 'package' => 'crm-probe' ] ), \Nino\Modules\Crm\Logbook::addKinds( $a, [ 7 ], [ 'package' => 'crm-probe' ] ) ];

	return $ok === true && $refused === array_fill( 0, 7, false ) && count( ninoWarnings() ) === 7 && \Nino\Modules\Crm\Logbook::kindOf( $a, '123', 'digits' ) === false;
} )() );
check( 'the core writes the kinds it knows without a sentence and no other: a kind of a package written through crm() is not written and says so', ( static function() use ( &$a, $ada ): bool {
	$count = domCount( $a, 'logbook' );
	$errors = ninoErrors( static function() use ( &$a, $ada ): void { \Nino\Modules\Crm\Logbook::crm( $a, 'probe-happened', [ 'client_id' => $ada ] ); } );

	return domCount( $a, 'logbook' ) === $count && count( $errors ) === 1 && $errors[0][0] === E_USER_WARNING;
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'the logbook', $e );
}

// --- The evidence ----------------------------------------------------------------------------------------------------------

echo "The evidence\n";

try {

[ $project, $where, $a ] = domWorld( 'records' );
domSignIn( $a, 'admin@example.org' );
$ada = domClient( $a, [ 'name' => 'Ada', 'email' => 'ada@example.org' ] );
$bea = domClient( $a, [ 'name' => 'Bea' ] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO crm_probe_items ( client_id, label ) VALUES ( :c, \'Offer A\' ), ( :d, \'Offer B\' )', [ 'c' => $ada, 'd' => $bea ] );
$member = [ 'id' => 'abcdef012345', 'email' => 'ada@example.org', 'name' => 'Ada' ];
$agree = [ 'kind' => 'probe-agree', 'client_id' => $ada, 'origin' => 'crm-probe', 'member' => $member, 'shown' => '<b>The text shown</b>', 'thing' => 'probe-item', 'thing_id' => '1', 'purpose' => 'alpha', 'source' => 'crm-probe', 'ip' => '192.0.2.1', 'agent' => 'Agent/1' ];

check( 'the core holds no kind of record of its own: what a person agreed to is written by the package whose surface asked for it', array_keys( \Nino\Modules\Crm\Records::kinds( $a ) ) === [ 'probe-agree', 'probe-revoke', 'probe-visit' ] );
$record = \Nino\Modules\Crm\Records::add( $a, $agree );
check( 'a record is kept with the account, the text shown, its hash, the thing it is about and the circumstances of the request', is_array( $record ) === true && $record['kind'] === 'probe-agree' && $record['member_id'] === 'abcdef012345' && $record['member_email'] === 'ada@example.org' && $record['sha256'] === hash( 'sha256', '<b>The text shown</b>' )
	&& $record['shown'] === '<b>The text shown</b>' && $record['thing'] === 'probe-item' && $record['thing_id'] === '1' && $record['purpose'] === 'alpha' && $record['parent'] === null && $record['origin'] === 'crm-probe' && $record['ip'] === '192.0.2.1' && is_int( $record['id'] ) && is_int( $record['client_id'] ) );
$entry = \Nino\Modules\Crm\Db::row( $a, 'SELECT * FROM logbook WHERE kind = \'probe-agreed\'' );
check( 'the logbook of the client has it as well: written under the source the package named, with the id of the record as the reference, the account as the one who did it, and the label of the thing', is_array( $entry ) === true && $entry['source'] === 'crm-probe' && $entry['ref'] === (string) $record['id'] && $entry['by'] === 'ada@example.org' && (int) $entry['client_id'] === $ada
	&& json_decode( $entry['data'], true ) === [ 'thing' => '1', 'label' => 'Offer A', 'purpose' => 'alpha' ] );
$refusals = [
	'a kind nobody entered' => [ [ 'kind' => 'nothing' ], 'kind' ], 'no kind' => [ [ 'kind' => null ], 'kind' ], 'a kind that is a list' => [ [ 'kind' => [ 'probe-agree' ] ], 'kind' ],
	'an origin that is the core' => [ [ 'origin' => 'crm' ], 'origin' ], 'an origin that is no slug' => [ [ 'origin' => 'Not A Slug' ], 'origin' ], 'no origin' => [ [ 'origin' => null ], 'origin' ], 'an origin of 41 characters' => [ [ 'origin' => str_repeat( 'a', 41 ) ], 'origin' ],
	'a client that is none' => [ [ 'client_id' => 99999 ], 'client' ], 'a client that is a text' => [ [ 'client_id' => '1' ], 'client' ], 'no account' => [ [ 'member' => [ 'email' => 'x@example.org' ] ], 'member' ], 'an account id that is a number' => [ [ 'member' => [ 'id' => 5 ] ], 'member' ],
	'no text shown' => [ [ 'shown' => '' ], 'shown' ], 'a text shown that is a list' => [ [ 'shown' => [ 'a' ] ], 'shown' ], 'no thing' => [ [ 'thing' => '', 'thing_id' => '' ], 'thing' ], 'a thing of another kind' => [ [ 'thing' => 'other' ], 'thing' ], 'a thing id that is a path' => [ [ 'thing_id' => '../1' ], 'thing' ],
	'a thing id that is a number' => [ [ 'thing_id' => 1 ], 'thing' ], 'a thing id with a null byte' => [ [ 'thing_id' => "1\0" ], 'thing' ], 'a thing another client holds' => [ [ 'thing_id' => '2' ], 'thing' ], 'a thing that is none' => [ [ 'thing_id' => '99999' ], 'thing' ],
	'no purpose' => [ [ 'purpose' => '' ], 'purpose' ], 'a purpose that is none of the kind' => [ [ 'purpose' => 'gamma' ], 'purpose' ], 'a parent where the kind has none' => [ [ 'parent' => 1 ], 'parent' ], 'a project that is a path' => [ [ 'project' => '../1' ], 'project' ],
	'no source for the logbook' => [ [ 'source' => null ], 'logbook' ], 'a source that did not enter the kind' => [ [ 'source' => 'crm-other' ], 'logbook' ], 'the source of the core' => [ [ 'source' => 'crm' ], 'logbook' ],
];

foreach( $refusals as $label => [ $change, $why ] ) {
	$count = domCount( $a, 'records' );
	$logbook = domCount( $a, 'logbook' );
	check( 'a record with '. $label. ' is refused with "'. $why. '", and nothing is written, not a line of the logbook', \Nino\Modules\Crm\Records::add( $a, $change + $agree ) === $why && domCount( $a, 'records' ) === $count && domCount( $a, 'logbook' ) === $logbook );
}

// A record that takes another back
$revoke = [ 'kind' => 'probe-revoke', 'client_id' => $ada, 'origin' => 'crm-probe', 'member' => $member, 'parent' => $record['id'], 'source' => 'crm-probe' ];
$other = \Nino\Modules\Crm\Records::add( $a, [ 'client_id' => $bea, 'thing_id' => '2', 'member' => [ 'id' => 'b1' ] ] + $agree );
check( 'a record that takes another back holds what that held - its purpose, its project and the text of its box - and names it as its parent', ( static function() use ( &$a, $revoke, $record ): bool {
	$back = \Nino\Modules\Crm\Records::add( $a, $revoke + [ 'shown' => 'ignored', 'purpose' => 'ignored' ] );

	return is_array( $back ) === true && $back['parent'] === $record['id'] && $back['purpose'] === 'alpha' && $back['shown'] === '<b>The text shown</b>' && $back['sha256'] === $record['sha256'] && $back['thing'] === '' && domCount( $a, 'logbook', 'kind = \'probe-revoked\'' ) === 1;
} )() );
check( 'a record is taken back once: the second try, a parent of another client, one that is none, one of another kind and one that is a text are refused with "parent"', ( static function() use ( &$a, $revoke, $record, $other ): bool {
	$count = domCount( $a, 'records' );

	return \Nino\Modules\Crm\Records::add( $a, $revoke ) === 'parent' && \Nino\Modules\Crm\Records::add( $a, [ 'parent' => $other['id'] ] + $revoke ) === 'parent' && \Nino\Modules\Crm\Records::add( $a, [ 'parent' => 99999 ] + $revoke ) === 'parent'
		&& \Nino\Modules\Crm\Records::add( $a, [ 'parent' => $other['id'], 'client_id' => 2 ] + $revoke ) !== 'kind' && \Nino\Modules\Crm\Records::add( $a, [ 'parent' => (string) $record['id'] ] + $revoke ) === 'parent' && \Nino\Modules\Crm\Records::add( $a, [ 'parent' => null ] + $revoke ) === 'parent' && domCount( $a, 'records' ) === $count + 1;
} )() );
check( 'the table itself refuses a second record that takes the same one back - two presses at once cannot make two', ( static function() use ( &$a, $record ): bool {
	try {
		\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO records ( kind, origin, client_id, member_id, parent, sha256, shown, at ) VALUES ( \'probe-revoke\', \'x\', 1, \'m\', :parent, \'h\', \'s\', \'t\' )', [ 'parent' => $record['id'] ] );

		return false;
	}
	catch( \PDOException $e ) {
		return true;
	}
} )() );

// A person with no account
$visit = [ 'kind' => 'probe-visit', 'client_id' => $ada, 'origin' => 'crm-probe', 'member' => [ 'email' => 'guest@example.org', 'name' => 'A Guest' ], 'ref' => 'event.7-a', 'at' => '2026-11-01T10:00:00Z', 'shown' => 'The privacy text' ];
$proof = \Nino\Modules\Crm\Records::add( $a, $visit );
check( 'a proof of a person who has no account is kept under the key of the package and the reference it gave, with the person as the package knew it and the time of the agreement', is_array( $proof ) === true && $proof['member_id'] === 'crm-probe:event.7-a' && $proof['member_email'] === 'guest@example.org' && $proof['member_name'] === 'A Guest' && $proof['at'] === '2026-11-01T10:00:00Z' );
$bad = [ 'an account besides' => [ [ 'member' => [ 'id' => 'x', 'email' => 'g@example.org' ] ], 'member' ], 'an address that is none' => [ [ 'member' => [ 'email' => 'nope' ] ], 'member' ], 'a name with a line break' => [ [ 'member' => [ 'email' => 'g@example.org', 'name' => "A\nB" ] ], 'member' ],
	'a name of 201 characters' => [ [ 'member' => [ 'email' => 'g@example.org', 'name' => str_repeat( 'n', 201 ) ] ], 'member' ], 'a reference with a blank' => [ [ 'ref' => 'a b' ], 'ref' ], 'a reference that is a path' => [ [ 'ref' => '../x' ], 'ref' ], 'a time ahead of the clock' => [ [ 'at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 3600 ) ], 'at' ],
	'a time that is none' => [ [ 'at' => '2026-02-31T00:00:00Z' ], 'at' ], 'a time that is a number' => [ [ 'at' => 5 ], 'at' ], 'a kind that allows none' => [ [ 'kind' => 'probe-agree' ], 'member' ] ];

foreach( $bad as $label => [ $change, $why ] )
	check( 'a proof with '. $label. ' is refused with "'. $why. '"', \Nino\Modules\Crm\Records::add( $a, $change + $visit ) === $why );

// What the evidence answers
check( 'the records of a client are read, the newest first, of a kind or of all; the records of a thing and whether a thing or a project is named', count( \Nino\Modules\Crm\Records::of( $a, $ada ) ) === 3 && count( \Nino\Modules\Crm\Records::of( $a, $ada, 'probe-agree' ) ) === 1 && \Nino\Modules\Crm\Records::of( $a, $ada )[0]['kind'] === 'probe-visit'
	&& count( \Nino\Modules\Crm\Records::forThing( $a, 'probe-item', '1' ) ) === 1 && \Nino\Modules\Crm\Records::names( $a, 'probe-item', '1' ) === true && \Nino\Modules\Crm\Records::names( $a, 'probe-item', '77' ) === false && \Nino\Modules\Crm\Records::namesProject( $a, '' ) === false );
check( 'a text shown that is markup or a formula is kept as it is, with its hash: the evidence is the text, not what a page makes of it', ( static function() use ( &$a, $agree ): bool {
	$text = "<script>alert(1)</script>\n=1+1 '; DROP TABLE records; --";
	$kept = \Nino\Modules\Crm\Records::add( $a, [ 'shown' => $text, 'thing_id' => '1', 'purpose' => 'beta' ] + $agree );

	return is_array( $kept ) === true && $kept['shown'] === $text && $kept['sha256'] === hash( 'sha256', $text ) && domCount( $a, 'records' ) >= 5;
} )() );
check( 'the agent of a browser is cut to 300 characters', ( static function() use ( &$a, $agree ): bool {
	return mb_strlen( \Nino\Modules\Crm\Records::add( $a, [ 'agent' => str_repeat( 'ä', 400 ), 'thing_id' => '1' ] + $agree )['agent'] ) === 300;
} )() );
check( 'the circumstances of a request: the address and the browser on one line, cut', ( static function() use ( &$a ): bool {
	$source = \Nino\Modules\Crm\Records::source( $a, [ '/nino/http/request' => [ 'header' => [ 'User-Agent' => "Agent\n/ 1\t". str_repeat( 'x', 400 ) ] ] ] );

	return is_string( $source['ip'] ) === true && mb_strlen( $source['agent'] ) === 300 && str_contains( $source['agent'], "\n" ) === false && str_starts_with( $source['agent'], 'Agent / 1 ' );
} )() );

// What a package may enter as a kind
$kinds = [ 'a kind that is no slug' => [ 'package' => 'crm-probe', 'kind' => 'Bad Kind' ], 'a thing that is no word' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'thing' => 'Bad Thing' ], 'purposes that are a text' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'purposes' => 'alpha' ], 'a purpose that is no word' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'purposes' => [ 'Bad' ] ],
	'a parent that is the kind itself' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'parent' => 'k-one' ], 'a field nobody knows' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'colour' => 'red' ], 'an owner that is none' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'owner' => [ 'Nothing', 'nothing' ] ], 'a logbook kind that is no slug' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'logbook' => 'Bad Kind' ],
	'a flag that is a text' => [ 'package' => 'crm-probe', 'kind' => 'k-one', 'foreign' => 'yes' ], 'a kind that another description has' => [ 'package' => 'crm-probe', 'kind' => 'probe-agree', 'thing' => 'something-else' ] ];

foreach( $kinds as $label => $spec )
	check( 'a kind with '. $label. ' is refused, with a warning, and nothing is entered', \Nino\Modules\Crm\Records::addKind( $a, $spec ) === false && count( ninoWarnings() ) === 1 && count( \Nino\Modules\Crm\Records::kinds( $a ) ) === 3 );
check( 'the same description again, from the same package or another, is as good as the first time', \Nino\Modules\Crm\Records::addKind( $a, [ 'package' => 'crm-probe', 'kind' => 'probe-agree', 'thing' => 'probe-item', 'purposes' => [ 'alpha', 'beta' ], 'logbook' => 'probe-agreed', 'owner' => [ \Nino\Modules\CrmProbe\Domain::class, 'ownerOf' ], 'label' => [ \Nino\Modules\CrmProbe\Domain::class, 'labelOf' ] ] ) === true && ninoWarnings() === [] );

}
catch( \Throwable $e ) {
	domCrashed( 'the evidence', $e );
}

/**
 *	The rows of every table the deletion of a client and the test phase touch, and the journal rows, the items of the package of the suite and what its store holds - for a pin that compares before and after
 *
 *	@param		array 		&$appData
 *
 *	@return 	array											name => how many
 */
function domTables( array &$appData ): array {

	$counts = [];

	foreach( [ 'clients', 'records', 'logbook', 'project_shares', 'project_states', 'tasks', 'sequences', 'owner_devices', 'crm_probe_items' ] as $table )
		$counts[$table] = domCount( $appData, $table );

	$counts['journal'] = domCount( $appData, 'settings', 'key LIKE \'erasing.%\'' );
	$counts['settings'] = domCount( $appData, 'settings', 'key NOT LIKE \'erasing.%\'' );
	$counts['projects'] = count( \Nino\Elements::queryElements( $appData, '/crm-projects', [], '*', [] ) );
	$counts['store'] = count( (array) \Nino\Filesystem::getFileContent( $appData, '/data/crm-probe-store.php', [] ) );

	return $counts;
}

/**
 *	The difference of two such answers, the tables that changed
 *
 *	@param		array 		$before
 *	@param		array 		$after
 *
 *	@return 	array											name => after - before, for those that differ
 */
function domDiff( array $before, array $after ): array {

	$diff = [];

	foreach( $after as $name => $count )
		if( $count !== $before[$name] )
			$diff[$name] = $count - $before[$name];

	return $diff;
}

/**
 *	A world with clients, projects, tasks, releases, records, a logbook, items of the package of the suite and what its store holds - the data a deletion has to take apart
 *
 *	@param		string		$name
 *
 *	@return 	array											The app data of the sandbox, the directory, the app data of the request and what was made: clients and projects by name
 */
function domFilled( string $name ): array {

	[ $project, $where, $a ] = domWorld( $name );
	domSignIn( $a, 'admin@example.org' );
	$made = [ 'a' => domClient( $a, [ 'name' => 'Ada', 'email' => 'ada@example.org' ] ), 'b' => domClient( $a, [ 'name' => 'Bea' ] ), 'c' => domClient( $a, [ 'name' => 'Cleo' ] ) ];
	$made['p1'] = domProject( $a, 'K-0001', 'One' );
	$made['p2'] = domProject( $a, 'K-0001', 'Two' );
	$made['p3'] = domProject( $a, 'K-0002', 'Three' );
	domBody( $a, 'crm-tasks/add', [ 'project' => $made['p1'], 'text' => 'a task' ] );
	domBody( $a, 'crm-tasks/add', [ 'project' => $made['p3'], 'text' => 'another' ] );
	domBody( $a, 'crm-projects/phase', [ 'id' => $made['p1'], 'phase' => 'mix' ] );
	domBody( $a, 'crm-projects/share', [ 'id' => $made['p1'], 'client' => (string) $made['b'] ] );
	domBody( $a, 'crm-projects/share', [ 'id' => $made['p3'], 'client' => (string) $made['a'] ] );
	domBody( $a, 'crm-logbook/add', [ 'client' => (string) $made['a'], 'text' => 'A note' ] );
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO crm_probe_items ( client_id, label ) VALUES ( :a, \'x\' ), ( :a, \'y\' ), ( :b, \'z\' )', [ 'a' => $made['a'], 'b' => $made['b'] ] );
	\Nino\Filesystem::putFileContent( $a, '/data/crm-probe-store.php', [ 1, 2, 3 ] );
	\Nino\Modules\Crm\Records::add( $a, [ 'kind' => 'probe-visit', 'client_id' => $made['a'], 'origin' => 'crm-probe', 'member' => [ 'email' => 'g@example.org', 'name' => 'G' ], 'ref' => 'v1', 'shown' => 'text', 'project' => $made['p1'] ] );
	\Nino\Modules\Crm\Records::add( $a, [ 'kind' => 'probe-agree', 'client_id' => $made['a'], 'origin' => 'crm-probe', 'member' => [ 'id' => 'm1', 'email' => 'ada@example.org' ], 'shown' => 'text', 'thing' => 'probe-item', 'thing_id' => '1', 'purpose' => 'alpha', 'source' => 'crm-probe' ] );
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO owner_devices ( user_mail, token_sha256, created_at, last_seen, expires, pw_mark ) VALUES ( \'admin@example.org\', \'h\', 1, 1, 9999999999, \'m\' )' );
	ninoWarnings();

	return [ $project, $where, $a, $made ];
}

// --- Deleting a client --------------------------------------------------------------------------------------------------------

echo "Deleting a client\n";

try {

[ $project, $where, $a, $m ] = domFilled( 'erasure' );
$before = domTables( $a );
$preview = domBody( $a, 'crm-clients/delete-preview', [ 'id' => $m['a'] ] )['preview'] ?? [];

check( 'the preview says what hangs on a client as numbers, by the names of the counts - the core\'s and the package\'s in front of the one they name - and that it may go', ( $preview['found'] ?? null ) === true && $preview['number'] === 'K-0001' && $preview['deletable'] === true && $preview['reasons'] === [] && $preview['permitted'] === true
	&& array_keys( $preview['counts'] ) === [ 'projects', 'shares', 'received', 'fixedp', 'probe', 'records', 'logbook', 'tasks' ] && $preview['counts'] === [ 'projects' => 2, 'shares' => 1, 'received' => 1, 'fixedp' => 0, 'probe' => 2, 'records' => 2, 'logbook' => 7, 'tasks' => 1 ] && $preview['shared'] === [] && $preview['features'] === [] );
check( 'a preview changes nothing', domDiff( $before, domTables( $a ) ) === [] );

foreach( [ 'no number' => [], 'the number in small letters' => [ 'confirm' => 'k-0001' ], 'the number with a blank' => [ 'confirm' => ' K-0001' ], 'the number of another client' => [ 'confirm' => 'K-0002' ], 'a number that is a number' => [ 'confirm' => 1 ], 'a list' => [ 'confirm' => [ 'K-0001' ] ], 'the word delete' => [ 'confirm' => 'delete' ] ] as $label => $confirm )
	check( 'deleting with '. $label. ' is the 400 crm_clients_delete_confirm with the number it asks for, and nothing is changed', ( static function() use ( &$a, $m, $confirm, $before ): bool {
		[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'id' => $m['a'] ] + $confirm );

		return $status === 400 && $body['code'] === 'crm_clients_delete_confirm' && $body['params'] === [ 'K-0001' ] && $body['field'] === 'confirm' && domDiff( $before, domTables( $a ) ) === [];
	} )() );

// Who may
domSignIn( $a, 'crm@example.org' );
check( 'an account that has the permission of the CRM and not the one of a package whose data goes along is refused: 403 crm_clients_delete_perm, the preview says permitted false, and nothing is changed', domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] ) === '403 crm_clients_delete_perm'
	&& ( domBody( $a, 'crm-clients/delete-preview', [ 'id' => $m['a'] ] )['preview']['permitted'] ?? null ) === false && domDiff( $before, domTables( $a ) ) === [] );
check( 'the method does not trust its caller: with no account, with an account that has no permission of the CRM and with one that lacks the permission of a panel it answers denied and deletes nothing', ( static function() use ( &$a, $m, $before ): bool {
	$answers = [];

	foreach( [ '', 'plain@example.org', 'crm@example.org', 'elements@example.org' ] as $who ) {
		domSignIn( $a, $who );
		$answers[] = \Nino\Modules\Crm\Clients::delete( $a, $m['a'] );
		$answers[] = \Nino\Modules\Crm\Erasure::resume( $a, 'K-0001' );
	}

	return $answers === array_fill( 0, 8, 'denied' ) && domDiff( $before, domTables( $a ) ) === [];
} )() );
domSignIn( $a, 'admin@example.org' );

// A step after the database that fails, and the press that finishes it
$a['./crm-probe/fail-once'] = true;
[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );
$mid = domTables( $a );
check( 'a step of a package after the database that fails stops the run: 500 crm_clients_delete_step with the name of the package, the database step is through and the journal says what is left', $status === 500 && $body['code'] === 'crm_clients_delete_step' && $body['params'] === [ 'probe' ]
	&& domDiff( $before, $mid ) === [ 'clients' => -1, 'records' => -2, 'logbook' => -7, 'project_shares' => -2, 'project_states' => -1, 'tasks' => -1, 'sequences' => 1, 'crm_probe_items' => -2, 'journal' => 1 ] );
check( 'the client is gone from the list and the deletion that is not finished is in it', ( static function() use ( &$a, $m ): bool {
	$list = domBody( $a, 'crm-clients/list' );

	return array_column( $list['clients'], 'number' ) === [ 'K-0002', 'K-0003' ] && $list['erasing'] === [ 'K-0001' ] && domAnswer( $a, 'crm-clients/get', [ 'id' => $m['a'] ] ) === '404 crm_clients_unknown';
} )() );
check( 'the projects and the store of the package are as they were until the press that finishes: nothing that a person can reach is left, and what is left is named in the journal', $mid['projects'] === $before['projects'] && $mid['store'] === 3 && ( static function() use ( &$a ): bool {
	$journal = json_decode( (string) \Nino\Modules\Crm\Db::row( $a, 'SELECT value FROM settings WHERE key = \'erasing.K-0001\'' )['value'], true );

	return $journal['number'] === 'K-0001' && $journal['projects'] === [ '00002', '00001' ] && $journal['steps'] === [ 'probe' => [ 'items' => [ 1, 2 ], 'who' => 'K-0001' ] ];
} )() );
check( 'a press with the number that is not finished and a wrong word is refused, one with a number nobody is deleting is the 404', domAnswer( $a, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0002' ] ) === '400 crm_clients_delete_confirm' && domAnswer( $a, 'crm-clients/delete', [ 'number' => 'K-0009', 'confirm' => 'K-0009' ] ) === '404 crm_clients_unknown'
	&& domAnswer( $a, 'crm-clients/delete', [ 'number' => 'k-0001', 'confirm' => 'k-0001' ] ) === '404 crm_clients_unknown' && domAnswer( $a, 'crm-clients/delete', [ 'number' => [ 'K-0001' ], 'confirm' => 'K-0001' ] ) === '400 crm_clients_id' );
[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
$after = domTables( $a );
check( 'the next press finishes it: the projects and the store of the package are taken, the journal goes, the answer counts what went', $status === 200 && $body['number'] === 'K-0001' && $body['counts']['projects'] === 2 && $body['counts']['probe'] === 2 && $after['journal'] === 0 && $after['projects'] === 1 && $after['store'] === 1 && $after['crm_probe_items'] === 1 );
check( 'every table has what the other clients had, and only that: the counts before and after, table by table', domDiff( $before, $after ) === [ 'clients' => -1, 'records' => -2, 'logbook' => -7, 'project_shares' => -2, 'project_states' => -1, 'tasks' => -1, 'sequences' => 1, 'crm_probe_items' => -2, 'projects' => -2, 'store' => -2 ] );
check( 'the second press and a press by the id say there is nothing: 404 both', domAnswer( $a, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ) === '404 crm_clients_unknown' && domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] ) === '404 crm_clients_unknown' );
check( 'what hung on the other clients is there: the project of Bea, its task, the release to nobody - the release of it to Ada went with her -, her item and her place in the store', ( static function() use ( &$a, $m ): bool {
	return \Nino\Modules\Crm\Projects::get( $a, $m['p3'] )['client'] === 'K-0002' && domCount( $a, 'tasks', 'project = \''. $m['p3']. '\'' ) === 1 && domCount( $a, 'project_shares' ) === 0 && domCount( $a, 'crm_probe_items', 'client_id = '. $m['b'] ) === 1 && domCount( $a, 'logbook', 'client_id = '. $m['b'] ) >= 1;
} )() );
check( 'the number is spent: the highest was not the one deleted, and when it is, the next client does not get it', ( static function() use ( &$a, $m ): bool {
	$gone = domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );
	$next = domBody( $a, 'crm-clients/save', [ 'name' => 'Newcomer' ] )['client']['number'];

	return $gone === '200 -' && $next === 'K-0004' && (int) \Nino\Modules\Crm\Db::row( $a, 'SELECT next FROM sequences WHERE category = \'client\' AND year = 0' )['next'] === 4;
} )() );
check( 'the line of the log says the number and the step, never a name', ( static function() use ( &$a ): bool {
	$lines = domLog( $a, 6 );

	return count( array_filter( $lines, static fn( string $line ): bool => str_contains( $line, 'Stopped deleting client K-0001 at probe' ) === true ) ) === 1 && count( array_filter( $lines, static fn( string $line ): bool => str_contains( $line, 'Deleted client K-0003' ) === true ) ) === 1 && count( array_filter( $lines, static fn( string $line ): bool => str_contains( $line, 'Ada' ) === true ) ) === 0;
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'deleting a client', $e );
}

// --- What keeps a client from being deleted ---------------------------------------------------------------------------------

echo "What keeps a client from being deleted\n";

try {

[ $project, $where, $a, $m ] = domFilled( 'holds' );
$before = domTables( $a );
$press = static function( int $id, string $number ) use ( &$a ): string { return domAnswer( $a, 'crm-clients/delete', [ 'id' => $id, 'confirm' => $number ] ); };

\Nino\Modules\Crm\Db::exec( $a, 'UPDATE crm_probe_items SET label = \'fixed\' WHERE client_id = :id AND label = \'x\'', [ 'id' => $m['a'] ] );
check( 'a package that holds rows that may not go says so: the client is not deleted, with the answer the package entered, and the plan names the reason first', $press( $m['a'], 'K-0001' ) === '409 crm_probe_fixed' && domDiff( $before, domTables( $a ) ) === [] && \Nino\Modules\Crm\Clients::deletable( $a, $m['a'] )['reasons'] === [ 'probefixed' ] && \Nino\Modules\Crm\Clients::deletable( $a, $m['a'] )['counts']['fixedp'] === 1 );
\Nino\Modules\Crm\Db::exec( $a, 'UPDATE crm_probe_items SET label = \'x\' WHERE client_id = :id', [ 'id' => $m['a'] ] );
$a['./crm-probe/shared'] = [ $m['p3'] ];
check( 'a project that a package says another client names stops the deletion of its client: 409 crm_clients_delete_shared, and nothing changes', $press( $m['b'], 'K-0002' ) === '409 crm_clients_delete_shared' && domDiff( $before, domTables( $a ) ) === [] && \Nino\Modules\Crm\Clients::deletable( $a, $m['b'] )['shared'] === [ $m['p3'] ] );
$a['./crm-probe/shared'] = [];
\Nino\Modules\Crm\Records::add( $a, [ 'kind' => 'probe-visit', 'client_id' => $m['c'], 'origin' => 'crm-probe', 'member' => [ 'email' => 'g@example.org', 'name' => 'G' ], 'ref' => 'v9', 'shown' => 'text', 'project' => $m['p3'] ] );
$before = domTables( $a );
check( 'so does a record of another client that names the project: the evidence stays where it is', $press( $m['b'], 'K-0002' ) === '409 crm_clients_delete_shared' && domDiff( $before, domTables( $a ) ) === [] );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Erasure::HOLDS] = [ 'merge' => [ 'holds' => [ [ 'key' => 'crm-probe', 'label' => "A hold\nof the probe", 'count' => 2, 'blocks' => true, 'why' => str_repeat( 'w', 400 ) ], [ 'key' => 'Bad Key', 'count' => 1, 'blocks' => true ], [ 'key' => 'ok', 'count' => -1 ], 'text' ] ] ];
$plan = \Nino\Modules\Crm\Clients::deletable( $a, $m['c'] );
[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );
check( 'what a package holds back is asked: the holds are checked and cut, one that is none is left out; a client one of them blocks is not deleted: 409 crm_clients_delete_held with the sentence of the hold', count( $plan['features'] ) === 1 && $plan['features'][0]['label'] === 'A hold of the probe' && mb_strlen( $plan['features'][0]['why'] ) === 300 && $plan['reasons'] === [ 'held' ]
	&& $status === 409 && $body['code'] === 'crm_clients_delete_held' && $body['params'] === [ str_repeat( 'w', 300 ) ] && domCount( $a, 'clients' ) === 3 );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Erasure::HOLDS] = [ 'merge' => [ 'holds' => [ [ 'key' => 'crm-probe', 'count' => 2, 'blocks' => false ] ] ] ];
$a['./crm-probe/steer'][\Nino\Modules\Crm\Erasure::DELETED] = [ 'merge' => [ 'gone' => [ 'crm-probe' => 3, 'Bad' => 1, 'x' => -1 ] ] ];
[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );
check( 'a hold that does not block does not stop the deletion; the packages are told the client is gone, with its number and addresses, and what they answer as gone is counted', $status === 200 && $body['features'] === [ 'crm-probe' => 3 ] && end( $a['./crm-probe/told'][\Nino\Modules\Crm\Erasure::DELETED] )['number'] === 'K-0003' );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Erasure::DELETED] = [ 'merge' => [ 'failed' => [ 'crm-probe' ] ] ];
[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'id' => $m['b'], 'confirm' => 'K-0002' ] );
$a['./crm-probe/shared'] = [];
check( 'a package that could not be told leaves the journal and stops the run: 500 crm_clients_delete_step "callback"; the next press asks it again and finishes', ( static function() use ( &$a, $status, $body, $m ): bool {
	unset( $a['./crm-probe/steer'][\Nino\Modules\Crm\Erasure::DELETED] );
	[ $again ] = domPost( $a, 'crm-clients/delete', [ 'number' => 'K-0002', 'confirm' => 'K-0002' ] );

	return $status === 500 && $body['code'] === 'crm_clients_delete_step' && $body['params'] === [ 'callback' ] && $again === 200 && domCount( $a, 'settings', 'key LIKE \'erasing.%\'' ) === 0;
} )() );
unset( $a['./crm-probe/steer'] );

check( 'a package that breaks while it is told keeps the journal as well: the warning names the client\'s number and no name', ( static function() use ( &$a, $m ): bool {
	$left = domClient( $a, [ 'name' => 'Dora Secret' ] );
	$number = (string) \Nino\Modules\Crm\Clients::get( $a, $left )['number'];
	$a['./crm-probe/steer'][\Nino\Modules\Crm\Erasure::DELETED] = [ 'throw' => true ];
	$answer = '';
	$errors = ninoErrors( static function() use ( &$a, $left, $number, &$answer ): void { $answer = domAnswer( $a, 'crm-clients/delete', [ 'id' => $left, 'confirm' => $number ] ); } );
	unset( $a['./crm-probe/steer'] );
	$text = implode( ' ', array_column( $errors, 1 ) );

	return $answer === '500 crm_clients_delete_step' && str_contains( $text, $number ) === true && str_contains( $text, 'Dora' ) === false && domCount( $a, 'settings', 'key = \'erasing.'. $number. '\'' ) === 1 && domAnswer( $a, 'crm-clients/delete', [ 'number' => $number, 'confirm' => $number ] ) === '200 -';
} )() );

// A delete while another process holds the database: the deletion waits and then does what was asked
check( 'a deletion that finds the database held by a writer in another process waits for it and goes through; a reader that comes while the writer holds sees the row as it was, at once', ( static function() use ( &$a, $where ): bool {
	$id = domClient( $a, [ 'name' => 'Held' ] );
	$number = (string) \Nino\Modules\Crm\Clients::get( $a, $id )['number'];
	$ready = $where. '/holder-ready';
	$release = $where. '/holder-release';
	$holder = proc_open( [ PHP_BINARY, __FILE__, 'holder-worker', $GLOBALS['features'], $where, (string) $id, $ready, $release ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	for( $wait = 0; $wait < 5000 && is_file( $ready ) === false; $wait++ )
		usleep( 1000 );

	$reader = proc_open( [ PHP_BINARY, __FILE__, 'reader-worker', $GLOBALS['features'], $where, (string) $id ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $readerPipes, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );
	$seen = json_decode( (string) stream_get_contents( $readerPipes[1] ), true );
	proc_close( $reader );
	file_put_contents( $release, 'go' );
	$started = microtime( true );
	$deleted = domAnswer( $a, 'crm-clients/delete', [ 'id' => $id, 'confirm' => $number ] );
	$waited = microtime( true ) - $started;
	stream_get_contents( $pipes[1] );
	proc_close( $holder );

	return is_array( $seen ) === true && $seen['notes'] === '' && $seen['seconds'] < 2.0 && $deleted === '200 -' && \Nino\Modules\Crm\Clients::get( $a, $id ) === null && $waited < 5.0;
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'what keeps a client from being deleted', $e );
}

// --- The test phase ---------------------------------------------------------------------------------------------------------

echo "The test phase\n";

try {

[ $project, $where, $a, $m ] = domFilled( 'wipe' );
domBody( $a, 'crm/settings-save', [ 'seller_name' => 'The Owner', 'start_project' => '7', 'start_client' => '5' ] );
$a['/crm-probe-wipe'] = true;
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO sequences ( category, year, next ) VALUES ( \'client\', 0, 9 )' );
$before = domTables( $a );
check( 'there is something to wipe: three clients, three projects, the tables of the core and of the package, and the store of the package', $before['clients'] === 3 && $before['projects'] === 3 && $before['crm_probe_items'] === 3 && $before['store'] === 3 && $before['owner_devices'] === 1 && $before['records'] === 2 && $before['sequences'] === 1 );

check( 'the word is the one of the language of the account: the German word in German, the English one in English', \Nino\Modules\Crm\Wipe::word( $a ) === 'LÖSCHEN' && ( static function() use ( &$a ): bool { \Nino\Runtime::setSessionValue( $a, './admin/locale', 'en_US' ); $word = \Nino\Modules\Crm\Wipe::word( $a ); \Nino\Runtime::setSessionValue( $a, './admin/locale', 'de_DE' ); return $word === 'DELETE'; } )() );
foreach( [ 'nothing' => [], 'the word in small letters' => [ 'confirm' => 'löschen' ], 'the word with a blank' => [ 'confirm' => 'LÖSCHEN ' ], 'the word of the other language' => [ 'confirm' => 'DELETE' ], 'a list' => [ 'confirm' => [ 'LÖSCHEN' ] ], 'a number' => [ 'confirm' => 0 ], 'true' => [ 'confirm' => true ] ] as $label => $data ) {
	[ $status, $body ] = domPost( $a, 'crm/wipe', $data );
	check( 'the test phase with '. $label. ' is the 400 crm_wipe_confirm with the word it asks for, and nothing is changed', $status === 400 && $body['code'] === 'crm_wipe_confirm' && $body['params'] === [ 'LÖSCHEN' ] && $body['field'] === 'confirm' && domDiff( $before, domTables( $a ) ) === [] );
}
domSignIn( $a, 'crm@example.org' );
check( 'an account without the permission of a panel whose data goes along is refused before the word is read: 403 crm_wipe_perm, nothing changes', domAnswer( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ) === '403 crm_wipe_perm' && domDiff( $before, domTables( $a ) ) === [] );
check( 'the method does not trust its caller: nobody, an account without the permission of the CRM and one without that of a panel get denied, and nothing is changed', ( static function() use ( &$a, $before ): bool {
	$answers = [];

	foreach( [ '', 'plain@example.org', 'crm@example.org', 'elements@example.org' ] as $who ) {
		domSignIn( $a, $who );
		$answers[] = \Nino\Modules\Crm\Wipe::run( $a );
	}

	domSignIn( $a, 'admin@example.org' );

	return $answers === array_fill( 0, 4, 'denied' ) && domDiff( $before, domTables( $a ) ) === [];
} )() );
domSignIn( $a, 'admin@example.org' );

\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( \'erasing.K-0099\', \'{}\' )' );
$a['./crm-probe/leftover-fails'] = true;
[ $status, $body ] = domPost( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
$mid = domTables( $a );
check( 'a step after the database that fails stops the run: 500 crm_wipe_step with the name of the package; the database step is through - it is atomic - and what a package holds outside it waits for the next press', $status === 500 && $body['code'] === 'crm_wipe_step' && $body['params'] === [ 'probe' ] && $mid['clients'] === 0 && $mid['projects'] === 0 && $mid['journal'] === 0 );
unset( $a['./crm-probe/leftover-fails'] );
[ $status, $body ] = domPost( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
$after = domTables( $a );
check( 'the next press finishes it: the leftover of the package is done, and the answer is the counts of the run - zeros, the database was through already, and the package\'s own count of what its leftover took - in the order of the keys, the package\'s at the end', $status === 200 && $body === [ 'clients' => 0, 'projects' => 0, 'records' => 0, 'logbook' => 0, 'shares' => 0, 'probe' => 3 ] );
check( 'every table of the movements of the clients is empty, the sequences with them - the numbers of the clients begin again from the start number - and the store of the package is empty', $after['clients'] === 0 && $after['records'] === 0 && $after['logbook'] === 0 && $after['project_shares'] === 0 && $after['project_states'] === 0 && $after['tasks'] === 0 && $after['sequences'] === 0 && $after['crm_probe_items'] === 0
	&& $after['journal'] === 0 && $after['projects'] === 0 && $after['store'] === 0 );
check( 'what is the company\'s stays: the settings of the owner, the devices that stay signed in, the four phases and the account', $after['settings'] === $before['settings'] && $after['owner_devices'] === 1 && \Nino\Modules\Crm\Settings::get( $a, 'seller_name' ) === 'The Owner' && count( \Nino\Modules\Crm\Projects::phaseKeys( $a ) ) === 4 );
check( 'the projects number again from the start number, the clients too: the next of each is the start number, though numbers went before', \Nino\Modules\Crm\Projects::upcoming( $a ) === '00007' && \Nino\Modules\Crm\Clients::upcoming( $a ) === 'K-0005' && domProject( $a, ( static function() use ( &$a ): string { return (string) domBody( $a, 'crm-clients/save', [ 'name' => 'First again' ] )['client']['number']; } )(), 'Again' ) === '00007' );
$answer = domBody( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( 'the second run counts what it took, which is the client and the project just made - and the third finds nothing', ( $answer['clients'] ?? 0 ) === 1 && ( $answer['projects'] ?? 0 ) === 1 && ( domBody( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] )['clients'] ?? 9 ) === 0 );
check( 'the packages are told the clients are gone; the line of the log says what was wiped, with counts and never a name', count( $a['./crm-probe/told'][\Nino\Modules\Crm\Wipe::WIPED] ) >= 3 && str_contains( domLog( $a, 3 )[0], 'Wiped the data of the clients: ' ) === true && preg_match( '/Wiped the data of the clients: (?:\d+ [a-z]+(?:, )?)+$/', domLog( $a, 3 )[0] ) === 1 );
check( 'the second client is the first of the new numbering and the logbook has its line: the ids of the logbook count on, so that a screen that still shows an old one cannot write into a new one', ( static function() use ( &$a ): bool {
	$id = domClient( $a, [ 'name' => 'Post wipe' ] );

	return $id >= 1 && (int) \Nino\Modules\Crm\Db::row( $a, 'SELECT MIN( id ) AS n FROM logbook' )['n'] > 1;
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'the test phase', $e );
}

// --- A package that is off, gone or has the core off --------------------------------------------------------------------------

echo "A package that is off, gone or has the core off\n";

try {

[ $project, $where, $a, $m ] = domFilled( 'orphans' );
$id = $m['a'];
\Nino\Features::deactivate( $project, 'crm-probe' );
$off = domRequest( $where );
domSignIn( $off, 'admin@example.org' );
check( 'a package that is switched off enters nothing of the second part: its actions are none, its kinds are none, its hooks are not told, its type is none', domAnswer( $off, 'crm-probe/read' ) === '404 action' && \Nino\Modules\Crm\Records::kinds( $off ) === [] && \Nino\Modules\Crm\Values::types( $off ) === [ 'crm-projects', 'crm-phases' ] && \Nino\Modules\Crm\Erasure::keys( $off ) === [ 'projects', 'shares', 'received', 'records', 'logbook', 'tasks' ]
	&& isset( $off['./crm-probe/told'] ) === false && \Nino\Modules\Crm\Logbook::kindOf( $off, 'crm-probe', 'probe-happened' ) === false );
check( 'the evidence of a package that is off is not refused to be read, and what it wrote stays: the records of a client are there', count( \Nino\Modules\Crm\Records::of( $off, $id ) ) === 2 && domCount( $off, 'logbook', 'source = \'crm-probe\'' ) === 1 && domCount( $off, 'crm_probe_items' ) === 3 );
$offTables = [];
foreach( [ 'clients', 'records', 'logbook', 'crm_probe_items', 'tasks', 'project_shares' ] as $table )
	$offTables[$table] = domCount( $off, $table );

check( 'the data of a package that is off is the reason nothing is deleted: the preview says orphans, the press is 409 crm_package_off with the key of the package, and the client is as it was', ( static function() use ( &$off, $id, $offTables ): bool {
	$plan = \Nino\Modules\Crm\Clients::deletable( $off, $id );
	[ $status, $body ] = domPost( $off, 'crm-clients/delete', [ 'id' => $id, 'confirm' => 'K-0001' ] );
	$same = true;

	foreach( $offTables as $table => $count )
		$same = $same && domCount( $off, $table ) === $count;

	return $plan['deletable'] === false && $plan['reasons'] === [ 'orphans' ] && $status === 409 && $body['code'] === 'crm_package_off' && $body['params'] === [ 'crm-probe' ] && $same;
} )() );
check( 'the same for the test phase: 409 crm_package_off and nothing is deleted', ( static function() use ( &$off, $offTables ): bool {
	[ $status, $body ] = domPost( $off, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
	$same = true;

	foreach( $offTables as $table => $count )
		$same = $same && domCount( $off, $table ) === $count;

	return $status === 409 && $body['code'] === 'crm_package_off' && $body['params'] === [ 'crm-probe' ] && $same && \Nino\Modules\Crm\Wipe::run( $off ) === 'orphans';
} )() );
check( 'a client with nothing a package holds is deleted all the same? no: the rule is the core\'s and holds for every client, since a table of the package may name any of them', domAnswer( $off, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] ) === '409 crm_package_off' );

// The package is removed: its data is taken away, and then the deletion goes through
\Nino\Features::remove( $project, 'crm-probe' );
$gone = domRequest( $where );
domSignIn( $gone, 'admin@example.org' );
check( 'a package that is removed leaves its data as the data of a package that is not installed; still the deletion is refused, with the answer that says the package is removed and its data are there', ( \Nino\Modules\Crm\Db::orphans( $gone )[0]['installed'] ?? null ) === false && domAnswer( $gone, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] ) === '409 crm_package_data' );
$dropped = domBody( $gone, 'crm/drop-data', [] );
check( 'the data of the removed package is taken away with the typed name, in the panel of the workbench, and the deletion of a client goes through', ( static function() use ( &$gone, $m ): bool {
	$_POST = [ 'action' => 'crm/drop-data', 'data' => json_encode( [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) ];
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Crm\Admin::apiDropData( $gone, $request );
	$_POST = [];

	return $request['/nino/http/response']['statusCode'] === 200 && \Nino\Modules\Crm\Db::orphans( $gone ) === [] && domAnswer( $gone, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] ) === '200 -';
} )() );
check( 'the evidence and the logbook of a package that is gone are the client\'s, and go with it when the client goes', ( static function() use ( &$gone, $m ): bool {
	return domAnswer( $gone, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] ) === '200 -' && domCount( $gone, 'records' ) === 0 && domCount( $gone, 'logbook', 'source = \'crm-probe\'' ) === 0;
} )() );

// What the removal took from the folder of the suite is put back, for the sections that come after
if( is_dir( $features. '/CrmProbe' ) === false )
	domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// The core off: the endpoint is not there, and the package that is initialised all the same registers nothing of it
[ $project2, $where2, $b ] = domWorld( 'coreoff' );
\Nino\Features::deactivate( $project2, 'crm-probe' );
\Nino\Features::deactivate( $project2, 'crm' );
$coreOff = domRequest( $where2, [ './crm-probe/domain' => true ] );
\Nino\Modules\CrmProbe::init( $coreOff );
check( 'with the core switched off there is no endpoint and no action, no kind, no element type, and a package that is initialised anyway registers nothing of the second part: it begins with the question whether the core is on',
	isset( $coreOff['/nino/http/routes']['POST://crm/api'] ) === false && isset( $coreOff['./crm/api/actions'] ) === false && isset( $coreOff['./crm/records/kinds'] ) === false && isset( $coreOff['./crm/values/types'] ) === false && isset( $coreOff['./crm/logbook/kinds'] ) === false
	&& isset( $coreOff['./crm/erasure/tables'] ) === false && isset( $coreOff['./crm/wipe/tables'] ) === false && isset( $coreOff['./crm/notifications/switches'] ) === false && ninoWarnings() === [] );
domSignIn( $coreOff, 'admin@example.org' );
check( 'a post to the endpoint of a CRM that is off is a request for a route nobody has', ( static function() use ( &$coreOff ): bool { [ $status ] = domPost( $coreOff, 'crm-clients/list' ); return $status === 404; } )() );

}
catch( \Throwable $e ) {
	domCrashed( 'a package that is off', $e );
}

// --- The settings, the overview, the notifications and the texts of the company ------------------------------------------------

echo "The settings, the overview, the notifications and the texts of the company\n";

try {

[ $project, $where, $a ] = domWorld( 'settings' );
domSignIn( $a, 'admin@example.org' );
domClient( $a, [ 'name' => 'Ada' ] );

$got = domBody( $a, 'crm/settings-get' );
check( 'the settings are answered with every key of the core and of the package, the addresses of the workbench and of the CRM, the numbers the next client and project get, the texts of the company, and what each package shows of itself - key => part', array_keys( $got ) === [ 'settings', 'addresses', 'next', 'company', 'packages', 'wipe' ] && $got['wipe'] === [ 'steps' => [ 'probe' ] ] && ( $got['settings']['probe_limit'] ?? '' ) === '5' && ( $got['settings']['start_client'] ?? '' ) === '1'
	&& array_column( $got['addresses'], 'key' ) === [ 'app', 'crm' ] && $got['next'] === [ 'client' => 'K-0002', 'project' => '00001' ] && array_keys( $got['company'] ) === [ 'keys', 'locales', 'native' ] && (array) $got['packages'] === [ 'crm-probe' => [ 'next' => 7 ] ] );
$saved = domBody( $a, 'crm/settings-save', [ 'seller_name' => 'The Owner', 'brand_word' => 'Studio', 'brand_color' => '#aabbcc', 'start_client' => '10', 'probe_limit' => '12', 'nothing' => 'x' ] );
check( 'settings are saved: the keys of the core and of the package that were posted, a key nobody entered is left alone, the value is as the rule of its key writes it (a colour in capitals), and a start number changes what the next client is', ( $saved['settings']['seller_name'] ?? '' ) === 'The Owner' && $saved['settings']['brand_color'] === '#AABBCC' && $saved['settings']['probe_limit'] === '12' && isset( $saved['settings']['nothing'] ) === false
	&& \Nino\Modules\Crm\Clients::upcoming( $a ) === 'K-0010' && preg_match( '/Changed the settings of the CRM: (?=.*\bseller_name\b)(?=.*\bbrand_word\b)(?=.*\bbrand_color\b)(?=.*\bstart_client\b)(?=.*\bprobe_limit\b)[a-z_, ]+$/', domLog( $a, 1 )[0] ) === 1 );
$bad = [ 'a start number of nought' => [ [ 'start_client' => '0' ], '400 crm_startnumber' ], 'a start number with a letter' => [ [ 'start_project' => '1a' ], '400 crm_startnumber' ], 'a start number of eight digits' => [ [ 'start_client' => '12345678' ], '400 crm_startnumber' ], 'a start number that is a list' => [ [ 'start_client' => [ 1 ] ], '400 crm_startnumber' ],
	'a seller name with a control character' => [ [ 'seller_name' => "A\x01" ], '400 crm_particular_seller_name' ], 'a seller name with a null byte' => [ [ 'seller_name' => "A\0" ], '400 crm_particular_seller_name' ], 'a seller name of 121 characters' => [ [ 'seller_name' => str_repeat( 's', 121 ) ], '400 crm_particular_seller_name' ],
	'a first line of the wordmark of 25 characters' => [ [ 'brand_word' => str_repeat( 'w', 25 ) ], '400 crm_brand_word' ], 'a second line with a line break' => [ [ 'brand_domain' => "a\nb" ], '400 crm_brand_domain' ], 'a colour that is none' => [ [ 'brand_color' => 'red' ], '400 crm_brand_color' ], 'an ink that is none' => [ [ 'brand_ink' => '#12' ], '400 crm_brand_color' ],
	'a host with a scheme' => [ [ 'crm_host' => 'https://crm.example.org' ], '400 crm_crm_host' ], 'a host with a port' => [ [ 'crm_host' => 'crm.example.org:8080' ], '400 crm_crm_host' ], 'a host that is an address of numbers' => [ [ 'crm_host' => '192.168.0.1' ], '400 crm_crm_host' ], 'a host with a capital that is none' => [ [ 'crm_host' => 'crm.exa mple.org' ], '400 crm_crm_host' ],
	'an app host that is a path' => [ [ 'app_host' => 'a.example.org/x' ], '400 crm_app_host' ], 'both hosts the same name' => [ [ 'crm_host' => 'x.example.org', 'app_host' => 'x.example.org' ], '200 -' ], 'the two hosts that may not be the same' => [ [ 'crm_host' => 'same.example.org', 'probe_host' => 'same.example.org' ], '400 crm_setting' ],
	'a value of the package that fails' => [ [ 'probe_limit' => 'many' ], '400 crm_probe_limit' ] ];

foreach( $bad as $label => [ $post, $answer ] )
	if( $answer !== '200 -' )
		check( 'settings with '. $label. ' are refused: '. $answer. ', and nothing of the post is written', ( static function() use ( &$a, $post, $answer ): bool {
			$was = \Nino\Modules\Crm\Settings::all( $a );

			return domAnswer( $a, 'crm/settings-save', $post + [ 'seller_name' => 'Written with it' ] ) === $answer && \Nino\Modules\Crm\Settings::all( $a ) === $was;
		} )() );

check( 'the host of the website is none for the CRM: 400 crm_crm_host, nothing is written, and the workbench may be put under it', ( static function() use ( &$a ): bool {
	\Nino\Html::addFills( $a, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
	$was = \Nino\Modules\Crm\Settings::all( $a );
	$refused = domAnswer( $a, 'crm/settings-save', [ 'crm_host' => 'www.example.org', 'seller_name' => 'Written with it' ] ) === '400 crm_crm_host' && \Nino\Modules\Crm\Settings::all( $a ) === $was;

	return $refused && domAnswer( $a, 'crm/settings-save', [ 'app_host' => 'www.example.org' ] ) !== '400 crm_setting';
} )() );
check( 'the name the workbench is put under is shown to be this installation first: a name that is not is the 409 crm_app_host_probe at the field, and nothing is written', ( static function() use ( &$a ): bool {
	[ $status, $body ] = domPost( $a, 'crm/settings-save', [ 'app_host' => 'not-this-installation.invalid', 'seller_name' => 'Written with it' ] );

	return $status === 409 && $body['code'] === 'crm_app_host_probe' && $body['field'] === 'app_host' && \Nino\Modules\Crm\Settings::get( $a, 'seller_name' ) === 'The Owner' && \Nino\Modules\Crm\Settings::get( $a, 'app_host' ) === '';
} )() );
check( 'a start number of the projects above the counter of the type lifts the counter at once, so that the Elements panel numbers by it as well', ( static function() use ( &$a ): bool {
	domBody( $a, 'crm/settings-save', [ 'start_project' => '50' ] );
	domSignIn( $a, 'admin@example.org' );

	return \Nino\Modules\Crm\Projects::upcoming( $a ) === '00050' && domProject( $a, 'K-0001', 'Fifty' ) === '00050';
} )() );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Settings\Api::SAVE] = [ 'merge' => [ 'clean' => [ 'probe_limit' => '99', 'nothing' => 'x', 'seller_name' => 'Taken over' ] ] ];
$did = domBody( $a, 'crm/settings-save', [ 'seller_name' => 'Posted' ] );
check( 'a package that is asked before a post is written may add what it keeps of its own to what is written - a key that is none of the settings is left out, and what the owner posted stands', ( $did['settings']['probe_limit'] ?? '' ) === '99' && $did['settings']['seller_name'] === 'Posted' && isset( $did['settings']['nothing'] ) === false && ( end( $a['./crm-probe/told'][\Nino\Modules\Crm\Settings\Api::SAVE] )['posted'] ?? null ) === [ 'seller_name' => 'Posted' ] );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Settings\Api::SAVE] = [ 'merge' => [ 'refusal' => [ 'status' => 409, 'message' => 'no', 'code' => 'crm_probe_no', 'params' => [], 'field' => 'seller_name' ] ] ];
check( 'or refuse it as a whole, with a code of its own, and nothing is written', domAnswer( $a, 'crm/settings-save', [ 'seller_name' => 'Refused' ] ) === '409 crm_probe_no' && \Nino\Modules\Crm\Settings::get( $a, 'seller_name' ) === 'Posted' );
unset( $a['./crm-probe/steer'] );

// The test mail
$mails = [];
\Nino\Callbacks::registerCallback( $a, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails ): void { $mails[] = $mail; $mail['sent'] = true; } );
\Nino\Text::saveBatch( $a, [ [ 'key' => '/project/company/contact/email', 'locale' => '', 'value' => '' ] ], false );
domBody( $a, 'crm/settings-save', [ 'brand_word' => '' ] );
check( 'the test mail with no first line of the wordmark is the 409 crm_brand_empty, at the field; with no contact address of the company the 409 crm_brand_contact; neither sends a mail', ( static function() use ( &$a, &$mails ): bool {
	$empty = domAnswer( $a, 'crm/brand-test' );
	domBody( $a, 'crm/settings-save', [ 'brand_word' => 'Studio' ] );
	$contact = domAnswer( $a, 'crm/brand-test' );

	return $empty === '409 crm_brand_empty' && $contact === '409 crm_brand_contact' && $mails === [];
} )() );
\Nino\Text::saveBatch( $a, [ [ 'key' => '/project/company/contact/email', 'locale' => '', 'value' => 'owner@example.org' ] ], false );
$sent = domPost( $a, 'crm/brand-test' );
check( 'with both, one mail goes to the contact address of the company and the answer says how many were delivered; the line of the log says it was sent and names nobody', $sent[0] === 200 && $sent[1] === [ 'mailed' => 1 ] && count( $mails ) === 1 && $mails[0]['to'] === 'owner@example.org' && str_contains( domLog( $a, 1 )[0], 'Sent the test mail of the wordmark' ) === true && str_contains( domLog( $a, 1 )[0], 'owner@example.org' ) === false );

// The overview
$over = domBody( $a, 'crm/overview' );
check( 'the overview says how many clients are active and has no need and no part of a package of its own', $over['summary'] === [ 'clients' => 1 ] && $over['needs'] === [] && (array) $over['packages'] === [] );
$a['./crm-probe/steer'][\Nino\Modules\Crm\Overview::HOOK] = [ 'merge' => [ 'packages' => [ 'crm-probe' => [ 'open' => 3 ], 'Bad Key' => [ 'x' => 1 ], 'crm-text' => 'no list' ], 'needs' => [ [ 'kind' => 'overdue', 'package' => 'crm-probe', 'count' => 2 ], [ 'kind' => 'Bad Kind', 'package' => 'crm-probe' ], [ 'package' => 'crm-probe' ], 'text' ] ] ];
$over = domBody( $a, 'crm/overview' );
check( 'a package adds its part under its key and what needs the owner as needs that have a kind and a package: what is not that shape is left out', (array) $over['packages'] === [ 'crm-probe' => [ 'open' => 3 ] ] && $over['needs'] === [ [ 'kind' => 'overdue', 'package' => 'crm-probe', 'count' => 2 ] ] );
unset( $a['./crm-probe/steer'] );

// The notifications
$notify = domBody( $a, 'crm/notifications' );
check( 'the notifications are answered with the switches that were entered, the address the mails to the owner go to, the one they go out as and the cap of the kernel', array_keys( $notify ) === [ 'switches', 'owner', 'sender', 'cap' ] && (array) $notify['switches'] === [ 'probe_owner' => '0' ] && $notify['owner'] === 'owner@example.org' && is_int( $notify['cap'] ) === true );
check( 'a switch is written by the package that owns it: the answer is the notifications as they are now, the line of the log names the switch; every key is checked before one is written', ( static function() use ( &$a ): bool {
	$on = domBody( $a, 'crm/notifications-save', [ 'probe_owner' => '1' ] );
	$bad = [ domAnswer( $a, 'crm/notifications-save', [ 'probe_owner' => '0', 'nothing' => '1' ] ), domAnswer( $a, 'crm/notifications-save', [ 'probe_owner' => 'yes' ] ), domAnswer( $a, 'crm/notifications-save', [ 'probe_owner' => true ] ), domAnswer( $a, 'crm/notifications-save', [ 'probe_owner' => 1 ] ), domAnswer( $a, 'crm/notifications-save', [] ), domAnswer( $a, 'crm/notifications-save', [ 'probe_owner' ] ) ];

	return (array) $on['switches'] === [ 'probe_owner' => '1' ] && $bad === [ '400 crm_notify_key', '400 crm_notify_value', '400 crm_notify_value', '400 crm_notify_value', '400 crm_notify_key', '400 crm_notify_key' ] && ( $a['./crm-probe/switch'] ?? false ) === true && str_contains( domLog( $a, 1 )[0], 'Changed the mail notifications: probe_owner' ) === true;
} )() );
$a['./crm-probe/switch-fails'] = true;
check( 'a package that cannot write its switch is the 500 crm_notify_write at the switch, and the log has no line', ( static function() use ( &$a ): bool { $line = domLog( $a, 1 )[0]; $answer = domAnswer( $a, 'crm/notifications-save', [ 'probe_owner' => '0' ] ); return $answer === '500 crm_notify_write' && domLog( $a, 1 )[0] === $line; } )() );
unset( $a['./crm-probe/switch-fails'] );

// The texts of the company
\Nino\Text::saveBatch( $a, [ [ 'key' => '/project/company/general/name', 'locale' => '', 'value' => 'Studio' ], [ 'key' => '/project/company/contact/phone', 'locale' => '', 'value' => '123' ] ], false );
$keys = domBody( $a, 'text/keys' );
check( 'the texts of the company are answered with their values in every language and whether the account may write each, the languages and the native one; the keys of the site are not among them', count( $keys['keys'] ) === 6 && array_filter( array_column( $keys['keys'], 'key' ), static fn( string $key ): bool => str_starts_with( $key, '/project/company/' ) === false ) === [] && array_filter( array_column( $keys['keys'], 'writable' ), static fn( mixed $one ): bool => $one !== true ) === [] && $keys['native'] === 'de_DE' && count( $keys['locales'] ) === 2 );
$batch = static fn( array $items ): string => domAnswer( $a, 'text/savebatch', [ 'items' => $items ] );
check( 'a text of the company is saved in a language; a batch with a key that is none of the company\'s is refused as a whole before any is written, and a value or a language that is no text is the 400', ( static function() use ( &$a, $batch ): bool {
	$ok = domBody( $a, 'text/savebatch', [ 'items' => [ [ 'key' => '/project/company/general/name', 'locale' => 'de_DE', 'value' => 'Studio Neu' ] ] ] );
	$whole = $batch( [ [ 'key' => '/project/company/general/name', 'locale' => 'de_DE', 'value' => 'Never' ], [ 'key' => '/project/website/general/url', 'locale' => 'de_DE', 'value' => 'x' ] ] );
	$bad = [ $batch( [ [ 'key' => '/project/company/general/name', 'locale' => [ 'de_DE' ], 'value' => 'x' ] ] ), $batch( [ [ 'key' => '/project/company/general/name', 'locale' => 'de_DE', 'value' => [ 'x' ] ] ] ), $batch( [ [ 'key' => '/project/company/../website/general/url', 'locale' => 'de_DE', 'value' => 'x' ] ] ), $batch( [ 'text' ] ) ];

	return ( $ok['results']['/project/company/general/name']['ok'] ?? false ) === true && \Nino\Html::resolveTextfill( $a, '/project/company/general/name' ) !== 'Never' && $whole === '404 crm_texts_key' && $bad === [ '400 crm_texts_value', '400 crm_texts_value', '404 crm_texts_key', '404 crm_texts_key' ];
} )() );
check( 'an account that holds the permission of the texts and of the CRM writes a key it was given and no other: the row of the key it was not given says so, and the rest of the batch is written', ( static function() use ( &$a ): bool {
	domSignIn( $a, 'texts@example.org' );
	$done = domBody( $a, 'text/savebatch', [ 'items' => [ [ 'key' => '/project/company/general/name', 'locale' => 'de_DE', 'value' => 'By the scoped one' ], [ 'key' => '/project/company/contact/phone', 'locale' => 'de_DE', 'value' => '999' ] ] ] );
	domSignIn( $a, 'admin@example.org' );

	return ( $done['results']['/project/company/general/name']['ok'] ?? false ) === true && ( $done['results']['/project/company/contact/phone']['ok'] ?? true ) === false && ( $done['results']['/project/company/contact/phone']['error'] ?? '' ) === 'not allowed' && \Nino\Html::resolveTextfill( $a, '/project/company/contact/phone' ) === '123';
} )() );
check( 'the permission of the CRM alone is not the permission of the texts', ( static function() use ( &$a ): bool { domSignIn( $a, 'crm@example.org' ); $answers = [ domAnswer( $a, 'text/keys' ), domAnswer( $a, 'text/savebatch', [ 'items' => [] ] ) ]; domSignIn( $a, 'admin@example.org' ); return $answers === [ '403 -', '403 -' ]; } )() );

// The status of the core, as the endpoint of the surface asks it
$status = domBody( $a, 'crm/status' );
check( 'the status is the one of the panel: the lines of the core and of the package, whether it is whole, where the CRM is, and the data of packages that are gone', array_keys( $status ) === [ 'lines', 'whole', 'open', 'orphans' ] && in_array( 'probe-line', array_column( $status['lines'], 'key' ), true ) === true && $status['orphans'] === [] );
check( 'the one line that is fixed is named: an unknown line is the 404, a line that has no fix the 409, a key that is none the 400', domAnswer( $a, 'crm/setup', [ 'key' => 'nothing' ] ) === '404 crm_setup_unknown' && domAnswer( $a, 'crm/setup', [ 'key' => 'mail' ] ) === '409 crm_setup_none' && domAnswer( $a, 'crm/setup', [ 'key' => 'Bad Key' ] ) === '400 crm_setup_unknown' && domAnswer( $a, 'crm/setup', [ 'key' => 'database' ] ) === '200 -' );
check( 'the action of the panel that takes the data of a package away is no action of the endpoint', domAnswer( $a, 'crm/drop-data', [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) === '404 action' );

}
catch( \Throwable $e ) {
	domCrashed( 'the settings', $e );
}

// --- Many processes at once ---------------------------------------------------------------------------------------------------

echo "Many processes at once\n";

try {

[ $project, $where, $a ] = domWorld( 'processes' );
domSignIn( $a, 'admin@example.org' );
domClient( $a, [ 'name' => 'First' ] );
$go = $where. '/go-clients';
$workers = [];

for( $worker = 0; $worker < 8; $worker++ )
	$workers[] = proc_open( [ PHP_BINARY, __FILE__, 'clients-worker', $features, $where, '6', $go ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes[$worker], null, [ 'NINO_ROOT' => $root ] + getenv() );

file_put_contents( $go, 'go' );
$numbers = [];
$failed = 0;

foreach( $workers as $worker => $process ) {
	$made = json_decode( (string) stream_get_contents( $pipes[$worker][1] ), true );
	stream_get_contents( $pipes[$worker][2] );
	proc_close( $process );

	if( is_array( $made ) === false )
		$failed++;
	else
		$numbers = array_merge( $numbers, $made );
}

sort( $numbers );
check( 'eight processes that make six clients each, started together, get 48 numbers that are all different and follow each other without a gap, after the one that was there', $failed === 0 && count( $numbers ) === 48 && count( array_unique( $numbers ) ) === 48 && $numbers === array_map( static fn( int $n ): string => 'K-'. sprintf( '%04d', $n ), range( 2, 49 ) ) && domCount( $a, 'clients' ) === 49 );
check( 'every client of that run has its line in the logbook, once: an entry is there exactly when the client is', domCount( $a, 'logbook', 'kind = \'client-added\'' ) === 49 );

// Two writers on one client
$id = domClient( $a, [ 'name' => 'Contended' ] );
$go = $where. '/go-writers';
$one = proc_open( [ PHP_BINARY, __FILE__, 'writer-worker', $features, $where, (string) $id, '40', $go, '1' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $first, null, [ 'NINO_ROOT' => $root ] + getenv() );
$two = proc_open( [ PHP_BINARY, __FILE__, 'writer-worker', $features, $where, (string) $id, '40', $go, '2' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $second, null, [ 'NINO_ROOT' => $root ] + getenv() );
file_put_contents( $go, 'go' );
$resultOne = json_decode( (string) stream_get_contents( $first[1] ), true );
$resultTwo = json_decode( (string) stream_get_contents( $second[1] ), true );
proc_close( $one );
proc_close( $two );
$phone = (string) \Nino\Modules\Crm\Clients::get( $a, $id )['phone'];
check( 'two writers on one client, 40 changes each, are all let through - none is refused, none throws - and the client is one of the last writes, whole', $resultOne === array_fill( 0, 40, 'ok' ) && $resultTwo === array_fill( 0, 40, 'ok' ) && in_array( $phone, [ '+49 139', '+49 239' ], true ) === true && domCount( $a, 'clients', 'id = '. $id ) === 1 );

// A write that fails is said, and not lost in silence: as the account of the server and as an account that cannot write the file
\Nino\Modules\Crm\Db::exec( $a, 'CREATE TRIGGER logbook_refuses BEFORE INSERT ON logbook WHEN NEW.kind = \'probe-happened\' BEGIN SELECT RAISE( ABORT, \'refused\' ); END' );
$errors = ninoErrors( static function() use ( &$a, $id, &$answer ): void { $answer = \Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-probe', 'kind' => 'probe-happened', 'client_id' => $id, 'data' => [ 'secret_name' => 'Ada Example' ] ] ); } );
check( 'an entry of the logbook that the database refuses is said: "write", and a warning that names the kind and the source and nothing of the client - as the account the server runs as', $answer === 'write' && count( $errors ) === 1 && $errors[0][0] === E_USER_WARNING && str_contains( $errors[0][1], 'probe-happened' ) === true && str_contains( $errors[0][1], 'Ada' ) === false );
\Nino\Modules\Crm\Db::exec( $a, 'DROP TRIGGER logbook_refuses' );

$denied = proc_open( [ PHP_BINARY, __FILE__, 'nobody-worker', $features, $where, (string) $id ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes2, null, [ 'NINO_ROOT' => $root ] + getenv() );
$report = json_decode( (string) stream_get_contents( $pipes2[1] ), true );
proc_close( $denied );
check( 'as an account that may read the files and not write them - where root would write all the same - the write is refused and said: the entry of the logbook is "write" with a warning, a client is not made (a \PDOException the endpoint answers 500 crm_database), nothing of the client is in what is said', is_array( $report ) === true && $report['uid'] !== 0 && $report['logbook'] === 'write' && $report['warnings'] === 1 && $report['client'] === 'refused' && $report['personal'] === false
	|| ( function_exists( 'posix_setuid' ) === false && is_array( $report ) === true ) );
check( 'the client the refused account tried to make is not there, and the entry is not either', domCount( $a, 'clients', 'name = \'Never Written\'' ) === 0 && domCount( $a, 'logbook', 'kind = \'probe-happened\'' ) === 0 );

}
catch( \Throwable $e ) {
	domCrashed( 'many processes', $e );
}

// --- What the code of the core names ---------------------------------------------------------------------------------------

echo "What the code of the core names\n";

try {

$names = [];
$statics = [];
$words = [ 'Members', 'Files', 'Pdf', 'Sign', 'Events', 'Review', 'Documents', 'Portal', 'Workshops', 'Dateien', 'Termine', 'Belege', 'Kunden', 'Projekte', 'Aufgaben' ];

foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

	$path = (string) $file->getPathname();
	$relative = substr( $path, strlen( $source ) + 1 );

	if( str_ends_with( $path, '.php' ) === false || str_starts_with( $relative, 'tests/' ) === true || $relative === 'feature.php' )
		continue;

	$tokens = token_get_all( (string) file_get_contents( $path ) );

	foreach( $tokens as $at => $token ) {

		if( is_array( $token ) === false )
			continue;

		// What is code: names, strings - never a comment
		if( in_array( $token[0], [ T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING ], true ) === true && ( preg_match( '~^\\\\?Nino\\\\Modules\\\\(?:'. implode( '|', $words ). ')\b|^\\\\?Nino\\\\Modules\\\\Crm[A-Z]~', $token[1] ) === 1 || in_array( $token[1], $words, true ) === true ) )
			$names[] = $relative. ': '. $token[1];

		if( in_array( $token[0], [ T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE ], true ) === true && preg_match( '~crm[-_](?:documents|portal|files|mediareview|dates|workshops)|crm_documents|\bclient_accounts\b|\bdismissals\b|\bdocuments\b|\bdocument_lines\b|portal~i', $token[1] ) === 1 )
			$names[] = $relative. ': '. substr( $token[1], 0, 40 );

		// A static field, in a class of the core
		if( $token[0] === T_STATIC && is_array( $tokens[$at + 2] ?? null ) === true && ( $tokens[$at + 2][0] ?? 0 ) === T_VARIABLE )
			$statics[] = $relative. ': '. $tokens[$at + 2][1];

		// A static field with a type: static, then a type, then the variable
		if( $token[0] === T_VARIABLE )
			for( $back = $at - 1; $back >= 0; $back-- ) {

				$before = $tokens[$back];

				if( is_array( $before ) === true && $before[0] === T_STATIC ) {
					$statics[] = $relative. ': '. $token[1];
					break;
				}

				if( $before === '?' || $before === '|' || ( is_array( $before ) === true && in_array( $before[0], [ T_WHITESPACE, T_STRING, T_ARRAY, T_CALLABLE, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_PRIVATE, T_PUBLIC, T_PROTECTED, T_READONLY ], true ) === true ) )
					continue;

				break;
			}
	}
}

check( 'no class, no action, no table, no code of an error of the core names a package: not by the namespace, not by a word of a string - the names a package has are entered by the package', $names === [] );
check( 'the core keeps no state in a static field: whatever a request knows stands in the app data of the request', array_unique( $statics ) === [] );
check( 'the one grip on the shell of the workbench is one class: only Values names the file of the shell, the classes of the Elements panel and the helpers of the workbench - the panel of the feature, which the workbench loads as a panel, uses exactly its ten calls of guardPerm, postData, postString and relative', ( static function() use ( $source ): bool {
	$holders = [];
	$panel = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$path = (string) $file->getPathname();
		$relative = substr( $path, strlen( $source ) + 1 );

		if( str_ends_with( $path, '.php' ) === false || str_starts_with( $relative, 'tests/' ) === true )
			continue;

		$code = (string) file_get_contents( $path );

		if( preg_match_all( '~[\'"]/_admin/Admin\.php[\'"]|\\\\Nino\\\\Modules\\\\Elements\\\\Admin::|\\\\Nino\\\\Modules\\\\Text\\\\Admin|\\\\Nino\\\\Admin\\\\(?:Admin|Panels)::~', $code ) > 0 )
			$holders[] = $relative;

		// The panel of the feature is loaded by the workbench as a panel and uses its helpers as a panel does: these calls, and no other
		if( $relative === 'Admin/Admin.php' ) {
			preg_match_all( '~\\\\Nino\\\\Admin\\\\(?:Admin|Panels)::[a-zA-Z]+~', $code, $found );
			$panel = array_count_values( $found[0] );
			ksort( $panel );
		}
	}

	return $holders === [ 'Admin/Admin.php', 'Values/Values.php' ] && $panel === [ '\\Nino\\Admin\\Admin::guardPerm' => 3, '\\Nino\\Admin\\Admin::postData' => 2, '\\Nino\\Admin\\Admin::postString' => 3, '\\Nino\\Admin\\Panels::relative' => 2 ];
} )() );
check( 'every sentence of an error the core says is in both languages, with the same keys and a sentence for every code the sources name', ( static function() use ( $source ): bool {
	$en = (array) include $source. '/text/en_US.php';
	$de = (array) include $source. '/text/de_DE.php';
	$codes = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$relative = substr( (string) $file->getPathname(), strlen( $source ) + 1 );

		if( str_ends_with( $relative, '.php' ) === false || str_starts_with( $relative, 'tests/' ) === true || str_starts_with( $relative, 'text/' ) === true || str_starts_with( $relative, 'install/' ) === true )
			continue;

		preg_match_all( '/\'(crm_[a-z_]+[a-z]|action)\'/', (string) file_get_contents( (string) $file->getPathname() ), $found );
		$codes = array_merge( $codes, $found[1] );
	}

	$missing = array_filter( array_unique( $codes ), static fn( string $code ): bool => isset( $en['[[/_admin/error/'. $code. ']]'] ) === false );
	$dynamic = [ 'crm_elements_locale', 'crm_elements_uri', 'crm_logbook_empty', 'crm_logbook_long', 'crm_tasks_empty', 'crm_tasks_long', 'crm_tasks_format' ];

	return array_keys( $en ) === array_keys( $de ) && array_diff( $missing, [ 'crm_logbook_', 'crm_tasks_', 'crm_elements_', 'crm_host', 'crm_remember' ] ) === [] && array_filter( $dynamic, static fn( string $code ): bool => isset( $en['[[/_admin/error/'. $code. ']]'], $de['[[/_admin/error/'. $code. ']]'] ) === false ) === [];
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'what the code names', $e );
}

// --- What the fixing round of the scaffolding asked of the domain ----------------------------------------------------------

echo "What packages ask of the domain\n";

try {

[ $project, $where, $a, $m ] = domFilled( 'asks' );

check( 'the group of a client is its number as a slug, and the client of a group is found by it: only for a group that is one - a number, a short one, a long one, text with a break and a group of nobody are none', ( static function() use ( &$a, $m ): bool {
	$found = \Nino\Modules\Crm\Clients::ofGroup( $a, 'k-0002' );
	$none = [ 'K-0002', 'k-2', 'k-0002 ', "k-0002\n", 'k-0099', '', 'k-00002', '../k-0002', 'k-0002%', '0002' ];

	return \Nino\Modules\Crm\Clients::group( 'K-0001' ) === 'k-0001' && \Nino\Modules\Crm\Clients::group( 'K-12345' ) === 'k-12345' && ( $found['id'] ?? 0 ) === $m['b'] && ( $found['number'] ?? '' ) === 'K-0002'
		&& array_filter( array_map( static fn( string $group ): ?array => \Nino\Modules\Crm\Clients::ofGroup( $a, $group ), $none ) ) === [];
} )() );

check( 'the master data are complete for an invoice when a company or a name and the street, the postcode, the town and the country are there: blanks, lists and numbers are nothing, and the fields are not looked up anywhere', ( static function(): bool {
	$full = [ 'company' => 'Studio', 'street' => 'A 1', 'zip' => '1010', 'city' => 'Wien', 'country' => 'AT' ];
	$check = static fn( array $values ): bool => \Nino\Modules\Crm\Clients::complete( $values );
	$without = static function( string $field ) use ( $full ): array { unset( $full[$field] ); return $full; };

	return $check( $full ) === true && $check( [ 'name' => 'Ada' ] + $without( 'company' ) ) === true && $check( $without( 'company' ) ) === false && $check( [ 'company' => '  ', 'name' => "\t" ] + $full ) === false
		&& $check( $without( 'street' ) ) === false && $check( $without( 'zip' ) ) === false && $check( $without( 'city' ) ) === false && $check( $without( 'country' ) ) === false
		&& $check( [ 'zip' => '  ' ] + $full ) === false && $check( [ 'zip' => 1010 ] + $full ) === false && $check( [ 'city' => [ 'Wien' ] ] + $full ) === false && $check( [] ) === false;
} )() );
check( 'the master data of the clients the suite made say what the invoice needs: one with an address is complete, one with a name alone is not', ( static function() use ( &$a, $m ): bool {
	$full = \Nino\Modules\Crm\Clients::change( $a, $m['a'], [ 'street' => 'Weg 1', 'zip' => '1010', 'city' => 'Wien', 'country' => 'AT' ] );

	return is_array( $full ) === true && \Nino\Modules\Crm\Clients::complete( $full ) === true && \Nino\Modules\Crm\Clients::complete( (array) \Nino\Modules\Crm\Clients::get( $a, $m['c'] ) ) === false;
} )() );

// The records about a project and about several things: the ids are texts
$insert = static function( array &$a, string $thing, string $id, string $project, int $client ): void {
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO records ( kind, origin, client_id, member_id, thing, thing_id, project, sha256, shown, at ) VALUES ( \'probe-visit\', \'crm-probe\', :client, \'m\', :thing, :id, :project, \'h\', \'t\', \'2026-01-01T00:00:00Z\' )', [ 'client' => $client, 'thing' => $thing, 'id' => $id, 'project' => $project ] );
};
$insert( $a, 'probe-thing', '7', '', $m['a'] );
$insert( $a, 'probe-thing', '007', '', $m['a'] );
$insert( $a, 'probe-thing', '1e1', '', $m['b'] );
$insert( $a, 'probe-thing', '10', '', $m['b'] );
$insert( $a, 'probe-other', '7', '', $m['a'] );
check( 'the records about a project are those that name it, the newest first, as the other reads have them; a project nobody names, an empty text and a number written differently are none', ( static function() use ( &$a, $m ): bool {
	$of = \Nino\Modules\Crm\Records::ofProject( $a, $m['p1'] );

	return count( $of ) === 1 && $of[0]['project'] === $m['p1'] && $of[0]['kind'] === 'probe-visit' && is_int( $of[0]['id'] ) === true && is_int( $of[0]['client_id'] ) === true && $of[0]['parent'] === null
		&& \Nino\Modules\Crm\Records::ofProject( $a, $m['p2'] ) === [] && \Nino\Modules\Crm\Records::ofProject( $a, '' ) === [] && \Nino\Modules\Crm\Records::ofProject( $a, '1' ) === [] && \Nino\Modules\Crm\Records::ofProject( $a, "00001\n" ) === [];
} )() );
check( 'the records about several things of one kind: the ids are texts - 7, 007, 1e1 and 10 are four things - a number of the list is the text it is written as, a thing of another kind is not found, and the answer keeps each thing_id as text', ( static function() use ( &$a ): bool {
	$ids = static fn( array $rows ): array => array_map( static fn( array $row ): string => $row['thing_id'], $rows );
	$seven = \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [ '7' ] );
	$both = \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [ 7, '007', '7' ] );
	$sci = \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [ 10 ] );

	return $ids( $seven ) === [ '7' ] && $ids( $both ) === [ '007', '7' ] && $ids( $sci ) === [ '10' ] && $ids( \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [ '1e1' ] ) ) === [ '1e1' ]
		&& $ids( \Nino\Modules\Crm\Records::forThings( $a, 'probe-other', [ '7', '10' ] ) ) === [ '7' ] && array_unique( array_map( 'gettype', array_column( $both, 'thing_id' ) ) ) === [ 'string' ]
		&& \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [] ) === [] && \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [ '', null, 1.5, true, [ '7' ] ] ) === [] && \Nino\Modules\Crm\Records::forThings( $a, '', [ '7' ] ) === []
		&& \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', [ "7' OR '1'='1", '7 OR 1=1' ] ) === []
		&& count( \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', range( 1, \Nino\Modules\Crm\Records::MOST ) ) ) === 2;
} )() );
check( 'a list of more than a portion is asked in portions and put together: with 501 and with 1201 ids the records of the first, the last and the middle portion are all there, the newest first over the portions', ( static function() use ( &$a, $m, $insert ): bool {
	$insert( $a, 'probe-thing', '501', '', $m['a'] );
	$insert( $a, 'probe-thing', '1200', '', $m['a'] );
	$ids = static fn( array $rows ): array => array_map( static fn( array $row ): string => $row['thing_id'], $rows );

	return $ids( \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', range( 1, 501 ) ) ) === [ '501', '10', '7' ] && $ids( \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', range( 1, 1201 ) ) ) === [ '1200', '501', '10', '7' ]
		&& $ids( \Nino\Modules\Crm\Records::forThings( $a, 'probe-thing', array_merge( range( 1201, 1, -1 ), [ '1e1', '007' ] ) ) ) === [ '1200', '501', '10', '1e1', '007', '7' ];
} )() );

// The logbook, found by what a package kept in it
check( 'the entries of a source and a kind are found by their reference and by their project, the newest first, with the row a package needs; a source or a kind that was not entered, a condition that is none and a key that is none give nothing', ( static function() use ( &$a, $m ): bool {
	foreach( [ [ 'v1', $m['p1'], 'one' ], [ '007', $m['p1'], 'two' ], [ '7', $m['p3'], 'three' ], [ 'v1', $m['p3'], 'four' ] ] as [ $ref, $project, $label ] )
		\Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-probe', 'kind' => 'probe-happened', 'client_id' => $project === $m['p3'] ? $m['b'] : $m['a'], 'project' => $project, 'ref' => $ref, 'data' => [ 'label' => $label ], 'text' => '', 'at' => '2026-02-0'. ( count( $a['./crm-probe/seen'] ?? [] ) + 1 ). 'T10:00:00Z' ] );

	// A kind nobody entered with rows in the table, and an entry with no reference at all
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO logbook ( client_id, project, at, source, kind, text, data, ref ) VALUES ( :client, \'\', \'2026-03-01T00:00:00Z\', \'crm-probe\', \'probe-ghost\', \'x\', \'{}\', \'v1\' )', [ 'client' => $m['a'] ] );
	\Nino\Modules\Crm\Logbook::add( $a, [ 'source' => 'crm-probe', 'kind' => 'probe-happened', 'client_id' => $m['a'], 'data' => [ 'label' => 'bare' ], 'at' => '2026-02-01T10:00:00Z' ] );
	$find = static fn( array $where, string $source = 'crm-probe', string $kind = 'probe-happened', int $limit = 50 ): array => \Nino\Modules\Crm\Logbook::find( $a, $source, $kind, $where, $limit );
	$labels = static fn( array $rows ): array => array_map( static fn( array $row ): string => (string) ( $row['data']['label'] ?? '' ), $rows );
	$one = $find( [ 'ref' => 'v1' ] );

	return $labels( $one ) === [ 'four', 'one' ] && array_keys( $one[0] ) === [ 'id', 'client_id', 'project', 'ref', 'data', 'at' ] && $one[0]['client_id'] === $m['b'] && $one[0]['ref'] === 'v1' && $one[0]['project'] === $m['p3']
		&& $labels( $find( [ 'project' => $m['p1'] ] ) ) === [ 'two', 'one' ] && $labels( $find( [ 'ref' => 'v1', 'project' => $m['p1'] ] ) ) === [ 'one' ]
		&& $labels( $find( [ 'ref' => 7 ] ) ) === [ 'three' ] && $labels( $find( [ 'ref' => '007' ] ) ) === [ 'two' ] && $labels( $find( [ 'ref' => 'v1' ], 'crm-probe', 'probe-happened', 1 ) ) === [ 'four' ]
		&& $find( [] ) === [] && $find( [ 'ref' => '' ] ) === [] && $find( [ 'ref' => 'v1' ], 'crm-probe', 'probe-ghost' ) === [] && $find( [ 'ref' => [ 'v1' ] ] ) === [] && $find( [ 'text' => 'x' ] ) === [] && $find( [ 'ref' => 'v1', 'text' => 'x' ] ) === [] && $find( [ 'v1' ] ) === []
		&& $find( [ 'ref' => 'v1' ], 'crm-other' ) === [] && $find( [ 'ref' => 'v1' ], 'crm-probe', 'probe-agreed' ) === [] && $find( [ 'ref' => 'v1' ], 'crm', 'note' ) === [] && $find( [ 'ref' => "v1' OR '1'='1" ] ) === []
		&& $find( [ 'ref' => 'v1' ], 'Bad Source' ) === [] && count( $find( [ 'ref' => 'v1' ], 'crm-probe', 'probe-happened', 0 ) ) === 1 && count( $find( [ 'ref' => 'v1' ], 'crm-probe', 'probe-happened', -5 ) ) === 1;
} )() );

// A line of the log in the name of a package
check( 'a package writes a line of the activity log with its account and its words: a break in the line or in the account never makes a second line, the line is cut, and an empty line or account writes nothing', ( static function() use ( &$a ): bool {
	$before = count( domLog( $a, 200 ) );
	$written = [ \Nino\Modules\Crm\Api::record( $a, 'admin@example.org', "Sent a thing\n2026-01-01 forged@example.org Deleted everything" ), \Nino\Modules\Crm\Api::record( $a, "admin@example.org\r\nforged@example.org", 'Second' ), \Nino\Modules\Crm\Api::record( $a, 'admin@example.org', str_repeat( 'x', 1000 ) ) ];
	$refused = [ \Nino\Modules\Crm\Api::record( $a, 'admin@example.org', '' ), \Nino\Modules\Crm\Api::record( $a, 'admin@example.org', "\n\t " ), \Nino\Modules\Crm\Api::record( $a, '', 'No one' ), \Nino\Modules\Crm\Api::record( $a, "\n", 'No one' ) ];
	$lines = domLog( $a, 3 );

	return $written === [ true, true, true ] && $refused === array_fill( 0, 4, false ) && count( domLog( $a, 200 ) ) === $before + 3 && str_contains( $lines[2], 'Sent a thing 2026-01-01 forged@example.org Deleted everything' ) === true && str_contains( $lines[2], 'admin@example.org' ) === true
		&& str_contains( $lines[1], 'Second' ) === true && str_contains( $lines[1], 'admin@example.org forged@example.org' ) === true && strlen( $lines[0] ) < 700 && preg_match( '/x{300}(?!x)/', $lines[0] ) === 1 && array_filter( $lines, static fn( string $line ): bool => str_contains( $line, "\n" ) ) === [];
} )() );

// What a leftover of the test phase may add to the counts
check( 'a leftover that counts what it took adds to its own count and to no other: a number that is none, a count of another package and a key nobody has are not taken, and the count of what it took before stays', ( static function() use ( &$a ): bool {
	$a['./crm-probe/leftover-tamper'] = true;
	\Nino\Filesystem::putFileContent( $a, '/data/crm-probe-store.php', [ 1, 2 ] );
	$answer = domBody( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
	unset( $a['./crm-probe/leftover-tamper'] );

	return ( $answer['probe'] ?? null ) === ( $answer['probe'] ?? 'x' ) && is_int( $answer['probe'] ?? null ) === true && ( $answer['records'] ?? 0 ) !== 99 && isset( $answer['other'] ) === false && ( $answer['clients'] ?? 0 ) === 3;
} )() );
check( 'a leftover that counts what it took is told in the answer, and in the line of the log: the package\'s own count is what it said', ( static function() use ( &$a ): bool {
	\Nino\Filesystem::putFileContent( $a, '/data/crm-probe-store.php', [ 1, 2, 3, 4 ] );
	$answer = domBody( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );

	return ( $answer['probe'] ?? null ) === 4 && array_keys( $answer ) === [ 'clients', 'projects', 'records', 'logbook', 'shares', 'probe' ];
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'what packages ask of the domain', $e );
}

// --- What the review asked for ---------------------------------------------------------------------------------------------

echo "What the review asked for\n";

try {

// A deletion that stopped at a package, and the package is switched off before the second press: nothing is reported as deleted, the journal and what the package kept stay
[ $project, $where, $a, $m ] = domFilled( 'resumed' );
$a['./crm-probe/fail-once'] = true;
$first = domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );
$journals = static fn( array &$request ): int => domCount( $request, 'settings', 'key LIKE \'erasing.%\'' );
check( 'a deletion that stopped at a package leaves its journal and what the package kept', $first === '500 crm_clients_delete_step' && $journals( $a ) === 1 && \Nino\Filesystem::getFileContent( $a, '/data/crm-probe-store.php', [] ) === [ 1, 2, 3 ] );
$rows = domCount( $a, 'crm_probe_items' );
\Nino\Features::deactivate( $project, 'crm-probe' );
$off = domRequest( $where );
domSignIn( $off, 'admin@example.org' );
check( 'the second press with the package switched off is 409 crm_package_off with the package named, and nothing is deleted: the journal, the data of the package outside the database and the rows of the package stay', ( static function() use ( &$off, $journals, $rows ): bool {
	[ $status, $body ] = domPost( $off, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );

	return $status === 409 && $body['code'] === 'crm_package_off' && $body['params'] === [ 'crm-probe' ] && $journals( $off ) === 1 && \Nino\Filesystem::getFileContent( $off, '/data/crm-probe-store.php', [] ) === [ 1, 2, 3 ] && domCount( $off, 'crm_probe_items' ) === $rows;
} )() );
check( 'a step in the journal that no package that is on stands for is a reason as well, though no data of it is in the database: the answer is "off:" and the package, and the journal stays', ( static function() use ( &$a, $journals ): bool {
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( \'erasing.K-0077\', :value )', [ 'value' => json_encode( [ 'number' => 'K-0077', 'projects' => [], 'emails' => [], 'steps' => [ 'ghost' => [ 'a' => 1 ] ] ] ) ] );
	$before = $journals( $a );
	$answer = \Nino\Modules\Crm\Erasure::resume( $a, 'K-0077' );

	return $answer === 'off:ghost' && $journals( $a ) === $before;
} )() );

// The register of the packages cannot be read: no answer of "none"
[ $project, $where, $a, $m ] = domFilled( 'busy' );
check( 'where the file is held and the register of the packages cannot be read, the data of a package that is gone are not "none": orphansKnown() and orphanKeys() say null, and the test phase says busy - with 503 crm_busy and the time to try again - and deletes nothing', ( static function() use ( &$a ): bool {
	$file = \Nino\Filesystem::path( $a, '/data/crm.sqlite' );
	$free = \Nino\Modules\Crm\Db::orphansKnown( $a ) === [] && \Nino\Modules\Crm\Erasure::orphanKeys( $a ) === [];
	$holder = new \PDO( 'sqlite:'. $file );
	$holder->exec( 'BEGIN EXCLUSIVE' );

	try {
		$known = \Nino\Modules\Crm\Db::orphansKnown( $a );
		$keys = \Nino\Modules\Crm\Erasure::orphanKeys( $a );
		$run = \Nino\Modules\Crm\Wipe::run( $a );
		$plain = \Nino\Modules\Crm\Db::orphans( $a );
	}
	finally {
		$holder->exec( 'ROLLBACK' );
	}

	return $free && $known === null && $keys === null && $run === 'busy' && $plain === [] && domCount( $a, 'clients' ) === 3;
} )() );
check( 'the answer of a busy database to the test phase is 503 crm_busy with Retry-After', ( static function() use ( &$a ): bool {
	$file = \Nino\Filesystem::path( $a, '/data/crm.sqlite' );
	$holder = new \PDO( 'sqlite:'. $file );
	$holder->exec( 'BEGIN EXCLUSIVE' );

	try {
		[ $status, $body, $request ] = domPost( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
	}
	finally {
		$holder->exec( 'ROLLBACK' );
	}

	return $status === 503 && ( $body['code'] ?? '' ) === 'crm_busy' && ( $request['/nino/http/response']['header']['Retry-After'] ?? '' ) === '5' && domCount( $a, 'clients' ) === 3;
} )() );

// A reason that turns up between the plan and the transaction
[ $project, $where, $a, $m ] = domFilled( 'flip' );
$a['./crm-probe/count-flip'] = true;
check( 'a reason a package has only after the plan was made is asked again in the transaction, with the write lock held: the press is refused with its code and the client is still there', ( static function() use ( &$a, $m ): bool {
	$answer = domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );

	return $answer === '409 crm_probe_fixed' && domCount( $a, 'clients' ) === 3 && ( $a['./crm-probe/count-seen'] ?? 0 ) >= 2;
} )() );
unset( $a['./crm-probe/count-flip'], $a['./crm-probe/count-seen'] );

// What a package may say of the counts of a deletion, and what it may gather
check( 'a step of a package that writes to counts that are not its own, or below what stood there, changes none of them: the answer has the counts the core made', ( static function() use ( &$a, $m ): bool {
	$a['./crm-probe/finish-tamper'] = true;
	$answer = domBody( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );
	unset( $a['./crm-probe/finish-tamper'] );

	return ( $answer['counts']['records'] ?? 0 ) === 2 && isset( $answer['counts']['other'] ) === false && ( $answer['counts']['probe'] ?? -1 ) >= 0;
} )() );
check( 'what a package gathers is flat: a list in a list is not kept, and the rest of what it gathered is', ( static function() use ( &$a, $m ): bool {
	$a['./crm-probe/gather-nested'] = true;
	$a['./crm-probe/fail-once'] = true;
	domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['b'], 'confirm' => 'K-0002' ] );
	unset( $a['./crm-probe/gather-nested'] );
	$journal = json_decode( (string) \Nino\Modules\Crm\Db::row( $a, 'SELECT value FROM settings WHERE key = \'erasing.K-0002\'' )['value'], true );

	return ( $journal['steps']['probe'] ?? null ) === [ 'who' => 'K-0002' ];
} )() );
check( 'the test phase does not begin while a deletion is open: 409 crm_wipe_open, nothing is deleted; once it is finished the test phase goes', ( static function() use ( &$a ): bool {
	$before = domCount( $a, 'clients' );
	[ $status, $body ] = domPost( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
	$refused = $status === 409 && ( $body['code'] ?? '' ) === 'crm_wipe_open' && domCount( $a, 'clients' ) === $before;
	$finished = domAnswer( $a, 'crm-clients/delete', [ 'number' => 'K-0002', 'confirm' => 'K-0002' ] );

	return $refused && $finished === '200 -' && domAnswer( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ) === '200 -';
} )() );

// The endpoint while the file is held longer than the wait: the places of the owner are shut with 503 and the time to try again, and nothing is run
[ $project, $where, $a, $m ] = domFilled( 'held' );
\Nino\Modules\Crm\Settings::set( $a, 'crm_host', 'crm.example.org' );
\Nino\Modules\Crm\Settings::set( $a, 'app_host', 'app.example.org' );
$GLOBALS['where_held'] = $where;
check( 'POST /crm/api while another process holds the file longer than the wait is 503 with Retry-After, whatever the name, and no action is run; with the lock gone it is answered as before', ( static function() use ( &$a, $m ): bool {
	$holder = new \PDO( 'sqlite:'. \Nino\Filesystem::path( $a, '/data/crm.sqlite' ) );
	$holder->exec( 'BEGIN EXCLUSIVE' );
	$answers = [];

	try {
		foreach( [ 'crm.example.org', 'evil.example.org' ] as $host ) {
			[ $status, $body, $request ] = domPost( $a, 'crm-clients/save', [ 'id' => $m['a'], 'city' => 'Held' ], [ 'host' => $host ] );
			$answers[] = [ $status, (string) ( $request['/nino/http/response']['header']['Retry-After'] ?? '' ) ];
		}
	}
	finally {
		$holder->exec( 'ROLLBACK' );
	}

	$later = domRequest( $GLOBALS['where_held'] );
	domSignIn( $later, 'admin@example.org' );

	return $answers === [ [ 503, '5' ], [ 503, '5' ] ] && ( \Nino\Modules\Crm\Clients::get( $later, $m['a'] )['city'] ?? '' ) !== 'Held' && domPost( $later, 'crm-clients/list', [], [ 'host' => 'crm.example.org' ] )[0] === 200;
} )() );

// The releases of a project, with releases in the fixture (the recording of the old CRM has none)
[ $project, $where, $a, $m ] = domFilled( 'shares' );
check( 'the releases of a project are listed with the client each is released to, in the shape the screen reads: id, number, title, who made it and when - and a project nobody released is an empty list, a project that is none is 404', ( static function() use ( &$a, $m ): bool {
	$one = domBody( $a, 'crm-projects/shares', [ 'id' => $m['p1'] ] );
	$row = $one['shares'][0] ?? [];

	return count( $one['shares'] ?? [] ) === 1 && ( $row['client'] ?? $row['client_id'] ?? null ) !== null && is_array( $row ) === true && array_key_exists( 'source', $row ) === true && domBody( $a, 'crm-projects/shares', [ 'id' => $m['p2'] ] )['shares'] === [] && domAnswer( $a, 'crm-projects/shares', [ 'id' => '99999' ] ) === '404 crm_projects_unknown';
} )() );

check( 'the section of the privacy policy says what the core keeps and what stays in the backups of the website, in both languages, and says nothing of confirmations that only a package writes', ( static function() use ( $source ): bool {
	$privacy = (array) include $source. '/install/elements/privacy.php';
	$text = $privacy['de_DE']['crm']['text'].' '.$privacy['en_US']['crm']['text'];

	return str_contains( $privacy['de_DE']['crm']['text'], 'von Hand ausgelöste Sicherung bleibt' ) === true && str_contains( $privacy['en_US']['crm']['text'], 'A backup made by hand stays' ) === true && str_contains( $privacy['de_DE']['crm']['text'], 'tägliche Sicherung entsteht' ) === true && str_contains( $privacy['en_US']['crm']['text'], 'newest three' ) === true
		&& preg_match( '/Nachweis|Bestätig|evidence|confirm|agreed|IP address|IP-Adresse/iu', $text ) === 0;
} )() );

check( 'every place a package enters itself or calls is named in the class of the core with its signature: each public add, addKind, addKinds, addSchema, addStyle, addTexts, addType, addWords, addFrom, allow and share of the core stands in the list of Crm.php', ( static function() use ( $source ): bool {
	$doc = (string) file_get_contents( $source. '/Crm.php' );
	$missing = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$path = (string) $file->getPathname();
		$relative = substr( $path, strlen( $source ) + 1 );

		if( str_ends_with( $path, '.php' ) === false || str_starts_with( $relative, 'tests/' ) === true || $relative === 'Register/Register.php' || $relative === 'Crm.php' )
			continue;

		$class = basename( dirname( $path ) ) === 'Api' && $relative !== 'Api/Api.php' ? basename( dirname( $path, 2 ) ) : basename( dirname( $path ) );
		preg_match_all( '/public static function (add|addKind|addKinds|addSchema|addType|addWords|addFrom|addStyle|addTexts|allow|share)\(( array &\$appData[^)]*)\)/', (string) file_get_contents( $path ), $found, PREG_SET_ORDER );

		foreach( $found as $one ) {

			// The signature as the list writes it: without the return type
			$signature = $class. '::'. $one[1]. '('. $one[2]. ')';

			if( str_contains( $doc, $class. '::'. $one[1]. '(' ) === false || ( str_contains( $doc, $signature ) === false && str_contains( $doc, $class. '::'. $one[1]. '()' ) === false ) )
				$missing[] = $signature;
		}
	}

	return $missing === [];
} )() );

check( 'the types of the values screen carry the note of the unit in the language of the installation, as the source said it: the phases have theirs; the projects have a screen of their own and are not in the list', ( static function() use ( &$a, $source ): bool {
	$types = array_column( (array) ( domBody( $a, 'elements/types' )['types'] ?? [] ), null, 'type' );
	$native = \Nino\Locales::getNativeLocale( $a );
	$text = (array) include $source. '/text/'. $native. '.php';

	return ( $types['crm-phases']['hint'] ?? '' ) !== '' && $types['crm-phases']['hint'] === $text['[[/_admin/elements/type/crm-phases/hint]]']	&& ( $types['crm-projects'] ?? null ) === null;
} )() );

// An open deletion whose package was removed: the owner may give the step up, by name, and only then
[ $project, $where, $a, $m ] = domFilled( 'giveup' );
$a['./crm-probe/fail-once'] = true;
domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );
\Nino\Features::deactivate( $project, 'crm-probe' );
$off = domRequest( $where );
domSignIn( $off, 'admin@example.org' );
check( 'an open deletion whose package is only off is 409 crm_package_off, and nothing may be given up: the answer lists no key that may be skipped, and a skip that names the package is no use', ( static function() use ( &$off ): bool {
	[ $status, $body ] = domPost( $off, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
	[ $again, $bodyTwo ] = domPost( $off, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'crm-probe' ] ] );

	return $status === 409 && ( $body['skippable'] ?? null ) === [] && $again === 409 && ( $bodyTwo['skippable'] ?? null ) === [];
} )() );
\Nino\Features::remove( $project, 'crm-probe' );
$gone = domRequest( $where );
domSignIn( $gone, 'admin@example.org' );
$_POST = [ 'action' => 'crm/drop-data', 'data' => json_encode( [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) ];
$dropRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Crm\Admin::apiDropData( $gone, $dropRequest );
$_POST = [];
check( 'where the package is removed and its data are gone the answer says which keys may be given up, and never all: "all", a key that is none, a key of another kind and a list that is none leave the deletion open', ( static function() use ( &$gone ): bool {
	[ $status, $body ] = domPost( $gone, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
	$tries = [ [ 'all' ], [ '*' ], [ 'nothing' ], 'probe', [ [ 'probe' ] ], [ 'Bad Key' ] ];
	$answers = array_map( static function( mixed $skip ) use ( &$gone ): int { return domPost( $gone, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => $skip ] )[0]; }, $tries );

	return $status === 409 && ( $body['code'] ?? '' ) === 'crm_package_gone' && ( $body['skippable'] ?? null ) === [ 'probe' ] && $answers === array_fill( 0, 6, 409 ) && \Nino\Modules\Crm\Erasure::pending( $gone ) === [ 'K-0001' ];
} )() );
check( 'the owner gives the step up by naming it: the deletion is finished, the answer and the line of the log name it, the journal is gone and so are the projects of the client', ( static function() use ( &$gone ): bool {
	$before = count( \Nino\Elements::queryElements( $gone, '/crm-projects', [], '*', [] ) );
	[ $status, $body ] = domPost( $gone, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probe' ] ] );

	return $status === 200 && ( $body['skipped'] ?? null ) === [ 'probe' ] && str_contains( domLog( $gone, 1 )[0], 'gave up the steps of probe' ) === true && \Nino\Modules\Crm\Erasure::pending( $gone ) === []
		&& count( \Nino\Elements::queryElements( $gone, '/crm-projects', [], '*', [] ) ) < $before;
} )() );

// The package that was removed is put back for the sections that follow
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// The owner of a step is the package that entered it and the journal kept, never a guess from the look of its key: a package that is only off, or whose key is none of the features, is not removed
$GLOBALS['domSet'] = [ './crm-probe/only-q' => true ];
[ $project, $where, $a, $m ] = domFilled( 'owner' );
unset( $GLOBALS['domSet'] );
$a['./crm-probe/fail-once'] = true;
domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );
$journalOf = static function( array &$request ): array { return (array) json_decode( (string) ( \Nino\Modules\Crm\Db::row( $request, 'SELECT value FROM settings WHERE key = :key', [ 'key' => 'erasing.K-0001' ] )['value'] ?? '[]' ), true ); };
check( 'the journal of an open deletion keeps the owner of each step: the key of the step probeq is the step of the package crm-probe', array_keys( $journalOf( $a )['steps'] ?? [] ) === [ 'probeq' ] && ( $journalOf( $a )['owners'] ?? null ) === [ 'probeq' => 'crm-probe' ] );
\Nino\Features::deactivate( $project, 'crm-probe' );
$off = domRequest( $where );
domSignIn( $off, 'admin@example.org' );
\Nino\Modules\Crm\Db::exec( $off, 'DROP TABLE crm_probe_items' );
\Nino\Modules\Crm\Db::exec( $off, 'DELETE FROM schema_versions WHERE package = \'crm-probe\'' );
check( 'a package that is installed and switched off enters nothing and is no package for the question of the core: Register::package() says none, and an add refuses it with a warning', ( static function() use ( &$off ): bool {

	ninoWarnings();

	return \Nino\Modules\Crm\Register::package( $off, 'crm-probe' ) === null && \Nino\Modules\Crm\Logbook::addKinds( $off, [ 'off-kind' ], [ 'package' => 'crm-probe' ] ) === false && count( ninoWarnings() ) === 1 && \Nino\Modules\Crm\Register::package( $off, 'crm' )  === null;
} )() );
check( 'a package that is only switched off, with no table left, is no package that is removed: its step is not to be given up, the answer says 409 crm_package_off with nothing to skip, and the journal stays', ( static function() use ( &$off, $journalOf ): bool {
	[ $status, $body ] = domPost( $off, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] );

	return \Nino\Modules\Crm\Erasure::orphanKeys( $off ) === [] && \Nino\Modules\Crm\Erasure::skippable( $off, 'K-0001' ) === [] && $status === 409 && ( $body['code'] ?? '' ) === 'crm_package_off' && ( $body['skippable'] ?? null ) === []
		&& \Nino\Modules\Crm\Erasure::pending( $off ) === [ 'K-0001' ] && array_keys( $journalOf( $off )['steps'] ?? [] ) === [ 'probeq' ];
} )() );
\Nino\Features::remove( $project, 'crm-probe' );
$gone = domRequest( $where );
domSignIn( $gone, 'admin@example.org' );
check( 'a step of a removed package is not listed to be given up while data of a package that is gone are in the database: the data come first', ( static function() use ( &$gone ): bool {

	\Nino\Modules\Crm\Db::exec( $gone, 'INSERT INTO schema_versions ( package, version, tables, applied ) VALUES ( \'ghost\', 1, \'{}\', \'2026-01-01T00:00:00Z\' )' );
	$held = \Nino\Modules\Crm\Erasure::skippable( $gone, 'K-0001' );
	\Nino\Modules\Crm\Db::exec( $gone, 'DELETE FROM schema_versions WHERE package = \'ghost\'' );

	return $held === [] && \Nino\Modules\Crm\Erasure::skippable( $gone, 'K-0001' ) === [ 'probeq' ];
} )() );
check( 'the same step is to be given up once the package is removed from the project: it is listed, and a step with no owner in the journal is never listed or given up, whatever its key', ( static function() use ( &$gone, $journalOf ): bool {
	$listed = \Nino\Modules\Crm\Erasure::skippable( $gone, 'K-0001' );
	$journal = $journalOf( $gone );
	unset( $journal['owners'] );
	\Nino\Modules\Crm\Db::exec( $gone, 'UPDATE settings SET value = :value WHERE key = \'erasing.K-0001\'', [ 'value' => json_encode( $journal ) ] );
	[ $status, $body ] = domPost( $gone, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] );

	return $listed === [ 'probeq' ] && \Nino\Modules\Crm\Erasure::skippable( $gone, 'K-0001' ) === [] && $status === 409 && ( $body['skippable'] ?? null ) === [] && \Nino\Modules\Crm\Erasure::pending( $gone ) === [ 'K-0001' ];
} )() );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// A package is removed where one place says so, Register::gone(): the kernel knows no such feature, names neither its key nor its folder among the directories it refuses, and its folder is not there. A package whose manifest cannot be read just
// now is there, and nothing is given up, skipped or dropped for it
$begun = static function( string $name ): array {

	$GLOBALS['domSet'] = [ './crm-probe/only-q' => true ];
	[ $project, $where, $a, $m ] = domFilled( $name );
	unset( $GLOBALS['domSet'] );
	$a['./crm-probe/fail-once'] = true;
	domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );
	\Nino\Modules\Crm\Db::exec( $a, 'DROP TABLE crm_probe_items' );
	\Nino\Modules\Crm\Db::exec( $a, 'DELETE FROM schema_versions WHERE package = \'crm-probe\'' );

	return [ $project, $where, $a ];
};
$journalOf = static function( array &$request ): array { return (array) json_decode( (string) ( \Nino\Modules\Crm\Db::row( $request, 'SELECT value FROM settings WHERE key = \'erasing.K-0001\'' )['value'] ?? '[]' ), true ); };
$storeOf = static function( array &$request ): mixed { return \Nino\Filesystem::getFileContent( $request, '/data/crm-probe-store.php', null ); };
$manifest = $features. '/CrmProbe/feature.php';
$kept = (string) file_get_contents( $manifest );
$broken = [
	'a manifest that does not parse' => [ static fn() => file_put_contents( $manifest, "<?php return [ 'key' => 'crm-probe', 'version' => " ), static fn() => file_put_contents( $manifest, $kept ) ],
	'a manifest file that is not there' => [ static fn() => rename( $manifest, $manifest. '.away' ), static fn() => rename( $manifest. '.away', $manifest ) ],
	'a manifest the kernel refuses' => [ static fn() => file_put_contents( $manifest, (string) preg_replace( "/'version'\s*=> '[^']*'/", "'version' => 'two'", $kept ) ), static fn() => file_put_contents( $manifest, $kept ) ],
];

foreach( $broken as $what => [ $break, $mend ] ) {

	[ $project, $where, $a ] = $begun( 'gone-'. md5( $what ) );
	$break();
	$r = domRequest( $where );
	domSignIn( $r, 'admin@example.org' );
	$state = [ \Nino\Features::get( $r, 'crm-probe' ) === null, \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ), \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) ];
	[ $status, $body ] = domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] );
	$answer = [ $status, $body['code'] ?? '', $body['skippable'] ?? null, \Nino\Modules\Crm\Erasure::pending( $r ), $storeOf( $r ) ];
	$mend();
	ninoWarnings();
	check( 'with '. $what. ' and its folder there the kernel does not know the package, and the package is not removed: nothing is listed to give up, a skip by name is 409 crm_package_off, the journal stays and so does what the step would have taken', $state === [ true, false, [] ] && $answer === [ 409, 'crm_package_off', [], [ 'K-0001' ], [ 1, 2, 3 ] ] );

	$back = domRequest( $where, [ './crm-probe/domain' => true, './crm-probe/only-q' => true ] );
	domSignIn( $back, 'admin@example.org' );
	check( 'with the manifest back the deletion goes on in the package, which takes what it holds of the client: the step is not given up', domAnswer( $back, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ) === '200 -' && \Nino\Modules\Crm\Erasure::pending( $back ) === [] && $storeOf( $back ) === [ 3 ] );
}

[ $project, $where, $a ] = $begun( 'gone-journal' );
check( 'the journal of an open deletion keeps the folder of the package of each step beside its owner', ( $journalOf( $a )['folders'] ?? null ) === [ 'probeq' => 'CrmProbe' ] && ( $journalOf( $a )['owners'] ?? null ) === [ 'probeq' => 'crm-probe' ] );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
$withFolder = static function( array &$request, array $folders ) use ( $journalOf ): void {
	$journal = $journalOf( $request );

	if( $folders === [] )
		unset( $journal['folders'] );
	else
		$journal['folders'] = $folders;

	\Nino\Modules\Crm\Db::exec( $request, 'UPDATE settings SET value = :value WHERE key = \'erasing.K-0001\'', [ 'value' => json_encode( $journal ) ] );
};
check( 'a package that is removed - the kernel knows none, refuses no directory, knows every entry of the features directory and the folder of the package is gone - is the one case where its step is listed to give up, and the deletion is finished by naming it', ( static function() use ( &$r ): bool {
	$gone = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === true && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe' ) === false && \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) === [ 'probeq' ];
	[ $status, $body ] = domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] );

	return $gone === true && $status === 200 && ( $body['skipped'] ?? null ) === [ 'probeq' ] && \Nino\Modules\Crm\Erasure::pending( $r ) === [];
} )() );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

[ $project, $where, $a ] = $begun( 'gone-folder' );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
check( 'a step whose journal keeps no folder is never removed, and one that keeps a folder that is there is not either, whatever the key of its package is: the folder the journal names decides', ( static function() use ( &$r, $withFolder, $features ): bool {
	$withFolder( $r, [] );
	$none = \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) === [] && domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] )[0] === 409;
	$withFolder( $r, [ 'probeq' => 'Elsewhere' ] );
	mkdir( $features. '/Elsewhere' );
	$there = \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) === [] && domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] )[0] === 409;
	rmdir( $features. '/Elsewhere' );
	$away = \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) === [ 'probeq' ];

	return $none === true && $there === true && $away === true && \Nino\Modules\Crm\Erasure::pending( $r ) === [ 'K-0001' ];
} )() );
check( 'nothing is taken from a name: the folder the package was seen in decides, never the key - with no folder nothing is removed, a folder or a key that is none is never removed, and the folder under another case or as a file or a dead link is there', ( static function() use ( &$r, $features ): bool {
	$free = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === true && \Nino\Modules\Crm\Register::gone( $r, 'crm-nothing', 'Nothing' ) === true && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'Ledger' ) === true;
	$known = \Nino\Modules\Crm\Register::gone( $r, 'crm-nothing', 'CrmProbeTwo' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-nothing', 'CRMPROBETWO' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-nothing', 'Crm' ) === false;
	$empty = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', '' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-nothing' ) === false;
	mkdir( $features. '/CrmProbe' );
	$named = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === false;
	rmdir( $features. '/CrmProbe' );
	mkdir( $features. '/CRMPROBE' );
	$cased = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CRMPROBE' ) === false;
	rmdir( $features. '/CRMPROBE' );
	file_put_contents( $features. '/CrmProbe', 'a file' );
	$file = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === false;
	unlink( $features. '/CrmProbe' );
	symlink( $features. '/Nowhere', $features. '/CrmProbe' );
	$link = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === false;
	unlink( $features. '/CrmProbe' );
	$none = \Nino\Modules\Crm\Register::gone( $r, 'Bad Key' ) === false && \Nino\Modules\Crm\Register::gone( $r, '', 'CrmProbe' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', '../x' ) === false && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'lower' ) === false
		&& \Nino\Modules\Crm\Register::gone( $r, 'Bad Key', 'CrmProbe' ) === false;

	return $free === true && $known === true && $empty === true && $named === true && $cased === true && $file === true && $link === true && $none === true;
} )() );
check( 'a directory the kernel refuses is a package that may be the one that is looked for: where the kernel refuses any directory at all - this one or another - nothing is removed', ( static function() use ( &$r ): bool {
	$free = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === true;
	$cached = $r;
	$cached['./nino/features/rejected'] = [ 'CrmProbe' => 'there is no directory features/CrmProbe: the feature is switched on and is not installed' ];
	$byFolder = \Nino\Modules\Crm\Register::gone( $cached, 'crm-probe', 'CrmProbe' ) === false;
	$cached['./nino/features/rejected'] = [ 'Other' => 'x' ];

	return $free === true && $byFolder === true && \Nino\Modules\Crm\Register::gone( $cached, 'crm-probe', 'CrmProbe' ) === false && \Nino\Modules\Crm\Register::gone( $cached, 'crm-probe', 'Elsewhere' ) === false;
} )() );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// The data of a package are offered and taken as those of a package that is gone only where it is gone
[ $project, $where, $a, $m ] = domFilled( 'gone-data' );
$manifestKept = (string) file_get_contents( $manifest );
file_put_contents( $manifest, "<?php return [ 'key' => 'crm-probe', 'version' => " );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
ninoWarnings();
$rows = domCount( $r, 'crm_probe_items' );
$orphans = \Nino\Modules\Crm\Db::orphans( $r );
$_POST = [ 'action' => 'crm/drop-data', 'data' => json_encode( [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) ];
$dropRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Crm\Admin::apiDropData( $r, $dropRequest );
$_POST = [];
$refusal = [ $dropRequest['/nino/http/response']['statusCode'], $dropRequest['/nino/http/response']['body']['code'] ?? '' ];
$asked = \Nino\Modules\Crm\Db::dropSchema( $r, 'crm-probe' );
file_put_contents( $manifest, $manifestKept );
ninoWarnings();
check( 'where the manifest of a package cannot be read and its folder is there, its data are listed as those of a package that is still installed, the panel refuses to take them away - 409 crm_drop_refused - and so does the core, and every row is there', $rows === 3 && count( $orphans ) === 1 && $orphans[0]['installed'] === true
	&& $refusal === [ 409, 'crm_drop_refused' ] && $asked === 'installed' && domCount( $r, 'crm_probe_items' ) === 3 );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
check( 'where the package is removed its data are listed as gone and are taken away on its typed name, as before', ( static function() use ( &$r ): bool {
	$orphans = \Nino\Modules\Crm\Db::orphans( $r );
	$_POST = [ 'action' => 'crm/drop-data', 'data' => json_encode( [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) ];
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Crm\Admin::apiDropData( $r, $request );
	$_POST = [];

	return count( $orphans ) === 1 && $orphans[0]['installed'] === false && $request['/nino/http/response']['statusCode'] === 200 && ( $request['/nino/http/response']['body']['dropped'] ?? null ) === 1 && \Nino\Modules\Crm\Db::orphans( $r ) === [];
} )() );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// Nothing is derived from a name. A package whose folder is not named like its key: the folder Ledger holds the package crm-probe, whose classes are \Nino\Modules\Ledger
$settle = static function( string $name ) use ( $features, $source ): void {

	domRemove( $features. '/CrmProbe' );
	domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/'. $name );

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $features. '/'. $name, \FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( str_ends_with( (string) $file, '.php' ) === true )
			file_put_contents( (string) $file, str_replace( 'CrmProbe', $name, (string) file_get_contents( (string) $file ) ) );

	rename( $features. '/'. $name. '/CrmProbe.php', $features. '/'. $name. '/'. $name. '.php' );
};
$unsettle = static function( string $name ) use ( $features, $source ): void {

	domRemove( $features. '/'. $name );
	domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
};
$unparsable = "<?php return [ 'key' => 'crm-probe', 'version' => ";
$skipped = static fn( array &$request ): array => domPost( $request, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] );

$settle( 'Ledger' );
$ledger = $features. '/Ledger/feature.php';
$ledgerKept = (string) file_get_contents( $ledger );
[ $project, $where, $a ] = $begun( 'ledger-journal' );
check( 'the journal of a package whose folder is not named like its key keeps the folder as the kernel lists it, beside the owner', ( $journalOf( $a )['folders'] ?? null ) === [ 'probeq' => 'Ledger' ] && ( $journalOf( $a )['owners'] ?? null ) === [ 'probeq' => 'crm-probe' ] );
file_put_contents( $ledger, $unparsable );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
$state = [ \Nino\Features::get( $r, 'crm-probe' ) === null, array_keys( \Nino\Features::rejected( $r ) ), \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'Ledger' ), \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) ];
[ $status, $body ] = $skipped( $r );
$answer = [ $status, $body['code'] ?? '', \Nino\Modules\Crm\Erasure::pending( $r ), $storeOf( $r ) ];
file_put_contents( $ledger, $ledgerKept );
ninoWarnings();
check( 'a package whose folder is not named like its key and whose manifest cannot be read is a package that is there: nothing is listed to give up, a skip is 409 crm_package_off, the journal stays and so does what the step would have taken', $state === [ true, [ 'Ledger' ], false, [] ] && $answer === [ 409, 'crm_package_off', [ 'K-0001' ], [ 1, 2, 3 ] ] );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
[ $status, $body ] = $skipped( $r );
check( 'the same package taken away - the folder Ledger is gone and nothing else is unknown - is removed: its step is listed and a skip by name finishes the deletion', \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'Ledger' ) === true && $status === 200 && ( $body['skipped'] ?? null ) === [ 'probeq' ] && \Nino\Modules\Crm\Erasure::pending( $r ) === [] );
$unsettle( 'Ledger' );

$settle( 'Ledger' );
[ $project, $where, $a, $m ] = domFilled( 'ledger-data' );
check( 'the register of the schemas keeps the folder of each package beside its row: none for the core', \Nino\Modules\Crm\Db::pdo( $a )->query( 'SELECT package, folder FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_KEY_PAIR ) === [ 'crm' => '', 'crm-probe' => 'Ledger' ] );
file_put_contents( $ledger, $unparsable );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
ninoWarnings();
$orphans = \Nino\Modules\Crm\Db::orphans( $r );
$_POST = [ 'action' => 'crm/drop-data', 'data' => json_encode( [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) ];
$dropRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Crm\Admin::apiDropData( $r, $dropRequest );
$_POST = [];
$refusal = [ $dropRequest['/nino/http/response']['statusCode'], $dropRequest['/nino/http/response']['body']['code'] ?? '' ];
$asked = \Nino\Modules\Crm\Db::dropSchema( $r, 'crm-probe' );
file_put_contents( $ledger, $ledgerKept );
ninoWarnings();
check( 'the data of a package whose folder is not named like its key, whose manifest cannot be read, are those of a package that is installed: the panel refuses to take them away - 409 crm_drop_refused - and every row is there', count( $orphans ) === 1 && $orphans[0]['installed'] === true
	&& $refusal === [ 409, 'crm_drop_refused' ] && $asked === 'installed' && domCount( $r, 'crm_probe_items' ) === 3 );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
check( 'the same package taken away: its data are listed as gone and taken away on its typed name', ( static function() use ( &$r ): bool {
	$orphans = \Nino\Modules\Crm\Db::orphans( $r );
	$_POST = [ 'action' => 'crm/drop-data', 'data' => json_encode( [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ) ];
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Crm\Admin::apiDropData( $r, $request );
	$_POST = [];

	return count( $orphans ) === 1 && $orphans[0]['installed'] === false && $request['/nino/http/response']['statusCode'] === 200 && ( $request['/nino/http/response']['body']['dropped'] ?? null ) === 1 && \Nino\Modules\Crm\Db::orphans( $r ) === [];
} )() );
$unsettle( 'Ledger' );

// A row of the register that keeps no folder is a package that is installed, however clean the features directory is; one that keeps a folder is removed where that folder is gone
[ $project, $where, $a ] = domWorld( 'register-folder', false );
check( 'a row of the register without a folder is installed - never an orphan that may be dropped - and the row with a folder that is not there is removed', ( static function() use ( &$a ): bool {
	$pdo = \Nino\Modules\Crm\Db::pdo( $a );
	$insert = $pdo->prepare( 'INSERT INTO schema_versions ( package, version, tables, applied, folder ) VALUES ( :package, 1, \'[]\', \'2026-01-01T00:00:00Z\', :folder )' );
	$insert->execute( [ 'package' => 'crm-bare', 'folder' => '' ] );
	$insert->execute( [ 'package' => 'crm-kept', 'folder' => 'Kept' ] );
	$listed = array_column( \Nino\Modules\Crm\Db::orphans( $a ), 'installed', 'package' );
	$asked = [ \Nino\Modules\Crm\Db::dropSchema( $a, 'crm-bare' ), \Nino\Modules\Crm\Db::dropSchema( $a, 'crm-kept' ) ];

	return $listed === [ 'crm-bare' => true, 'crm-kept' => false ] && $asked === [ 'installed', 0 ] && array_keys( \Nino\Modules\Crm\Db::schemas( $a ) ) === [ 'crm', 'crm-bare' ];
} )() );

// A package that is a link: the folder is the name the kernel lists, not the name of what the link leads to
$release = sys_get_temp_dir(). '/nino-crm-domain-release-'. bin2hex( random_bytes( 6 ) );
mkdir( $release, 0755, true );
domRemove( $features. '/CrmProbe' );
domCopy( $source. '/tests/fixtures/CrmProbe', $release. '/Release' );
symlink( $release. '/Release', $features. '/CrmProbe' );
$linked = $release. '/Release/feature.php';
[ $project, $where, $a ] = $begun( 'link-journal' );
check( 'where the folder of a package is a link, the journal keeps the name of the link as the kernel lists it, not the name of the folder it leads to', ( $journalOf( $a )['folders'] ?? null ) === [ 'probeq' => 'CrmProbe' ] );
file_put_contents( $linked, $unparsable );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
$state = [ \Nino\Features::get( $r, 'crm-probe' ) === null, \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ), \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ), $skipped( $r )[0] ];
file_put_contents( $linked, $kept );
ninoWarnings();
check( 'a package that is a link and whose manifest cannot be read is there: nothing is given up', $state === [ true, false, [], 409 ] && \Nino\Modules\Crm\Erasure::pending( $r ) === [ 'K-0001' ] );
\Nino\Features::deactivate( $project, 'crm-probe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
$stillThere = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === false && $skipped( $r )[0] === 409;
unlink( $features. '/CrmProbe' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
check( 'a link that is taken away is a folder that is gone: while it was there nothing was given up, once it is gone the step is listed', $stillThere === true && \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === true && \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) === [ 'probeq' ] );
domRemove( $release. '/Release' );
rmdir( $release );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// What else the features directory holds: a package that is gone is removed only where the whole directory is accounted for
[ $project, $where, $a ] = $begun( 'strays' );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$strays = [
	'a folder with no manifest' => [ 'Stray', static fn( string $path ) => mkdir( $path ) ],
	'a hidden folder' => [ '.git', static fn( string $path ) => mkdir( $path ) ],
	'a folder put aside by the kernel while it exchanges a package' => [ '.previous-crm-probe-0123456789ab', static fn( string $path ) => mkdir( $path ) ],
	'a folder the kernel stages a package in' => [ '.incoming-CrmProbe-0123456789ab', static fn( string $path ) => mkdir( $path ) ],
	'a folder the kernel puts aside to delete it' => [ '.removing-crm-probe-0123456789ab', static fn( string $path ) => mkdir( $path ) ],
	'a link that leads nowhere' => [ 'Dangling', static fn( string $path ) => symlink( dirname( $path ). '/Nowhere', $path ) ],
	'a link to a folder' => [ 'Release', static fn( string $path ) => symlink( dirname( $path ). '/Crm', $path ) ],
	'a link named like a file the system leaves there' => [ '.DS_Store', static fn( string $path ) => symlink( dirname( $path ). '/Crm/feature.php', $path ) ],
	'a link to nowhere named like a file the system leaves there' => [ '.DS_Store', static fn( string $path ) => symlink( dirname( $path ). '/Nowhere', $path ) ],
	'a folder named like the file of the kernel' => [ '.htaccess', static fn( string $path ) => mkdir( $path ) ],
	'a link named like the file of the kernel' => [ '.htaccess', static fn( string $path ) => symlink( dirname( $path ). '/Crm/feature.php', $path ) ],
	'a file named like the folder of the package' => [ 'CrmProbe', static fn( string $path ) => file_put_contents( $path, 'x' ) ],
	'a file named like the folder of the package under another case' => [ 'CRMPROBE', static fn( string $path ) => file_put_contents( $path, 'x' ) ],
];

foreach( $strays as $what => [ $name, $make ] ) {

	$make( $features. '/'. $name );
	$r = domRequest( $where );
	domSignIn( $r, 'admin@example.org' );
	$state = [ \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ), \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ), $skipped( $r )[0], \Nino\Modules\Crm\Erasure::pending( $r ) ];
	is_dir( $features. '/'. $name ) === true && is_link( $features. '/'. $name ) === false ? rmdir( $features. '/'. $name ) : unlink( $features. '/'. $name );
	check( 'with '. $what. ' in the features directory the package that is gone is not removed - the kernel knows no such entry, and it may be the package - nothing is listed to give up and a skip is 409', $state === [ false, [], 409, [ 'K-0001' ] ] );
}

// A package is a directory for the kernel: a regular file is no package, whatever it is called or holds, and is no reason to keep what a package left
foreach( [ '.DS_Store' => "\0\0\0\1Bud1", 'README.md' => '# notes', '.htaccess' => 'Require all denied', 'notes.txt' => 'x', '.hidden' => 'x', 'Thumbs.db' => '' ] as $file => $content ) {

	file_put_contents( $features. '/'. $file, $content );
	$r = domRequest( $where );
	domSignIn( $r, 'admin@example.org' );
	$state = [ \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ), \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' ) ];
	unlink( $features. '/'. $file );
	check( 'a regular file in the features directory - '. $file. ' - is no entry: the package that is gone is removed and its step is listed to give up', $state === [ true, [ 'probeq' ] ] );
}

domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
[ $project, $where, $a ] = $begun( 'files' );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );

foreach( [ '.DS_Store', 'README.md', '.htaccess', 'notes.txt' ] as $file )
	file_put_contents( $features. '/'. $file, 'x' );

$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
$answers = [ domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ), $skipped( $r ) ];
check( 'with such files all there, an open deletion asks for the step of the removed package by the code that says so, and giving the step up by name finishes it', ( $answers[0][1]['code'] ?? '' ) === 'crm_package_gone' && ( $answers[0][1]['skippable'] ?? null ) === [ 'probeq' ] && $answers[1][0] === 200 && \Nino\Modules\Crm\Erasure::pending( $r ) === [] );

foreach( [ '.DS_Store', 'README.md', '.htaccess', 'notes.txt' ] as $file )
	unlink( $features. '/'. $file );

domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
[ $project, $where, $a ] = $begun( 'strays-again' );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );

mkdir( $features. '/Broken' );
file_put_contents( $features. '/Broken/feature.php', "<?php return [ 'key' => 'broken', 'version' => " );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
ninoWarnings();
$pdo = \Nino\Modules\Crm\Db::pdo( $r );
$pdo->exec( 'INSERT INTO schema_versions ( package, version, tables, applied, folder ) VALUES ( \'crm-probe\', 1, \'[]\', \'2026-01-01T00:00:00Z\', \'CrmProbe\' )' );
$withBroken = [ \Nino\Features::get( $r, 'crm-probe' ), array_keys( \Nino\Features::rejected( $r ) ), array_column( \Nino\Modules\Crm\Db::orphans( $r ), 'installed', 'package' ), \Nino\Modules\Crm\Db::dropSchema( $r, 'crm-probe' ) ];
$pdo->exec( 'DELETE FROM schema_versions WHERE package = \'crm-probe\'' );
$withBroken[] = \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' );
$withBroken[] = \Nino\Modules\Crm\Erasure::skippable( $r, 'K-0001' );
$withBroken[] = $skipped( $r )[0];
check( 'a package of another key whose manifest cannot be read is a directory the kernel refuses that may be the one that is gone: nothing is removed, given up or dropped while it is there', $withBroken === [ null, [ 'Broken' ], [ 'crm-probe' => true ], 'installed', false, [], 409 ] );
domRemove( $features. '/Broken' );
$r = domRequest( $where );
domSignIn( $r, 'admin@example.org' );
check( 'with that directory taken away the package is removed and its step is given up by name', \Nino\Modules\Crm\Register::gone( $r, 'crm-probe', 'CrmProbe' ) === true && $skipped( $r )[0] === 200 && \Nino\Modules\Crm\Erasure::pending( $r ) === [] );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// What an answer says of a package is true in every state it is given in: crm_package_off where a package is NOT removed (off, cannot be read, something unknown beside it in the features directory), crm_package_data where the package is removed and
// its data are in the database, crm_package_gone where it is removed with no data and a step is to be given up - for the press of a client, for finishing a deletion and for the test phase
$withData = static function( string $name ): array {

	$GLOBALS['domSet'] = [ './crm-probe/only-q' => true ];
	[ $project, $where, $a, $m ] = domFilled( $name );
	unset( $GLOBALS['domSet'] );
	$a['./crm-probe/fail-once'] = true;
	domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['a'], 'confirm' => 'K-0001' ] );

	return [ $project, $where, $m ];
};
$ways = static function( string $where, array $m ): array {

	$r = domRequest( $where );
	domSignIn( $r, 'admin@example.org' );
	$answer = [ domAnswer( $r, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] ), domAnswer( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ), domAnswer( $r, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ) ];
	ninoWarnings();

	return $answer;
};

[ $project, $where, $m ] = $withData( 'states-off' );
\Nino\Features::deactivate( $project, 'crm-probe' );
check( 'a package that is only switched off, its data in the database: every way says it is not removed - the press of a client, the finishing of a deletion and the test phase', $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_off' ) );
[ $project, $where, $m ] = $withData( 'states-broken' );
file_put_contents( $manifest, "<?php return [ 'key' => 'crm-probe', 'version' => " );
$answers = $ways( $where, $m );
file_put_contents( $manifest, $kept );
check( 'a package whose manifest cannot be read, its data in the database: not removed, in every way', $answers === array_fill( 0, 3, '409 crm_package_off' ) );
[ $project, $where, $m ] = $withData( 'states-removed' );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
check( 'a package that is removed, its data in the database: every way says the data are the reason', $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_data' ) );

foreach( [ '.DS_Store', 'README.md', '.htaccess' ] as $file )
	file_put_contents( $features. '/'. $file, 'x' );

check( 'the same with files the system leaves in the features directory: they are no entry, the package is still removed and the answer is the same in every way', $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_data' ) );

foreach( [ '.DS_Store', 'README.md', '.htaccess' ] as $file )
	unlink( $features. '/'. $file );

mkdir( $features. '/Stray' );
check( 'with a folder the kernel does not know beside it the package may be that folder: the answer is that it is not removed, in every way', $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_off' ) );
rmdir( $features. '/Stray' );
check( 'the folder taken away, the answer is the one for a package that is removed and has data', $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_data' ) );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// A state of two packages is that of the one that is not removed: the answer says the data are of a removed package only where every package that has data is removed, and that the steps are of a removed package only where every package that owns one is
[ $project, $where, $m ] = $withData( 'states-mixed-data' );
\Nino\Features::activate( $project, 'crm-probe-two' );
file_put_contents( $features. '/CrmProbeTwo/schema-own.php', "<?php\nreturn [ 'create' => [ 'CREATE TABLE crm_two_items ( id INTEGER PRIMARY KEY )' ], 'steps' => [] ];\n" );
$r = domRequest( $where );
$entered = \Nino\Modules\Crm\Db::addSchema( $r, [ 'package' => 'crm-probe-two', 'version' => 1, 'tables' => [ 'crm_two_items' ], 'file' => $features. '/CrmProbeTwo/schema-own.php' ] );
$made = domCount( $r, 'crm_two_items' ) === 0;
ninoWarnings();
\Nino\Features::deactivate( $project, 'crm-probe-two' );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
check( 'the data of two packages, one removed and the other only switched off: the data are not those of a removed package, in every way - and where the other is removed too, they are', $entered === true && $made === true && $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_off' )
	&& ( static function() use ( $project, $where, $ways, $m, $features ): bool {

		\Nino\Features::remove( $project, 'crm-probe-two' );

		return $ways( $where, $m ) === array_fill( 0, 3, '409 crm_package_data' );
	} )() );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
domCopy( $source. '/tests/fixtures/CrmProbeTwo', $features. '/CrmProbeTwo' );

[ $project, $where, $a ] = $begun( 'states-mixed-steps' );
$row = \Nino\Modules\Crm\Db::row( $a, 'SELECT value FROM settings WHERE key = :key', [ 'key' => 'erasing.K-0001' ] );
$journal = (array) json_decode( (string) $row['value'], true );
$journal['steps']['x'] = $journal['steps']['probeq'];
$journal['owners']['x'] = 'crm-probe-two';
$journal['folders']['x'] = 'CrmProbeTwo';
\Nino\Modules\Crm\Db::exec( $a, 'UPDATE settings SET value = :v WHERE key = :key', [ 'v' => json_encode( $journal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), 'key' => 'erasing.K-0001' ] );
\Nino\Features::deactivate( $project, 'crm-probe' );
\Nino\Features::remove( $project, 'crm-probe' );
$r = domSignedIn( $where );
$answer = domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
check( 'the steps of an open deletion that belong to two packages, one removed and the other on: the steps are not those of a removed package, nothing is offered to be given up, and the other step may not be given up either', ( $answer[1]['code'] ?? '' ) === 'crm_package_off' && ( $answer[1]['skippable'] ?? null ) === []
	&& ( static function() use ( &$r ): bool {

		[ $status, $body ] = domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001', 'skip' => [ 'probeq' ] ] );

		return $status === 409 && ( $body['code'] ?? '' ) === 'crm_package_off' && in_array( 'K-0001', \Nino\Modules\Crm\Erasure::pending( $r ), true ) === true;
	} )() );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

[ $project, $where, $a ] = $begun( 'states-nodata' );
\Nino\Features::deactivate( $project, 'crm-probe' );
$r = domSignedIn( $where );
$answers = [ 'off' => domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ) ];
\Nino\Features::remove( $project, 'crm-probe' );
$r = domSignedIn( $where );
$answers['removed'] = domPost( $r, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
check( 'with no data left, a package that is switched off is not removed and offers nothing to give up; one that is removed is asked for its step by its own code, which offers the step - and the answer of the one is never that of the other', ( $answers['off'][1]['code'] ?? '' ) === 'crm_package_off' && ( $answers['off'][1]['skippable'] ?? null ) === []
	&& ( $answers['removed'][1]['code'] ?? '' ) === 'crm_package_gone' && ( $answers['removed'][1]['skippable'] ?? null ) === [ 'probeq' ] && $answers['removed'][0] === 409 );
domCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );

// What a package registers to be run for it is its own code: the callables of a description lie in the namespace of the package that names itself
[ $project, $where, $a ] = domWorld( 'owned' );
\Nino\Features::activate( $project, 'crm-probe-two' );
$r = domRequest( $where, [ './crm-probe/domain' => true ] );
ninoWarnings();
$own = [ \Nino\Modules\CrmProbeTwo::class, 'init' ];
$foreign = [ \Nino\Modules\CrmProbe\Domain::class, 'count' ];
$cases = [
	'a count of another package' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twoa', 'count' => $foreign, 'reason' => 'twoa' ] ],
	'a count in a namespace that only begins like the package' => [ 'erasure', [ 'package' => 'crm-probe', 'key' => 'twob', 'count' => $own, 'reason' => 'twob' ] ],
	'rows of another package' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twoc', 'count' => $own, 'rows' => $foreign ] ],
	'a finish of another package' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twod', 'count' => $own, 'finish' => $foreign ] ],
	'a gather of another package' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twoe', 'count' => $own, 'finish' => $own, 'gather' => $foreign ] ],
	'a shared of another package' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twof', 'count' => $own, 'reason' => 'twof', 'shared' => $foreign ] ],
	'a closure for a count' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twog', 'count' => static fn(): int => 0, 'reason' => 'twog' ] ],
	'a function for a count' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twoh', 'count' => 'strlen', 'reason' => 'twoh' ] ],
	'a method of the kernel for a count' => [ 'erasure', [ 'package' => 'crm-probe-two', 'key' => 'twoi', 'count' => 'Nino\\Elements::getElement', 'reason' => 'twoi' ] ],
	'rows of the test phase of another package' => [ 'wipe', [ 'package' => 'crm-probe-two', 'key' => 'twoj', 'rows' => $foreign ] ],
	'a leftover of another package' => [ 'wipe', [ 'package' => 'crm-probe-two', 'key' => 'twok', 'leftover' => [ \Nino\Modules\CrmProbe\Domain::class, 'leftover' ] ] ],
	'a leftover that is a closure' => [ 'wipe', [ 'package' => 'crm-probe-two', 'key' => 'twol', 'leftover' => static fn(): bool => true ] ],
];

foreach( $cases as $what => [ $place, $spec ] ) {

	ninoWarnings();
	$entered = $place === 'erasure' ? \Nino\Modules\Crm\Erasure::add( $r, $spec ) : \Nino\Modules\Crm\Wipe::add( $r, $spec );
	check( 'a package that enters '. $what. ' is refused with one warning and nothing is entered', $entered === false && count( ninoWarnings() ) === 1 && isset( \Nino\Modules\Crm\Erasure::entered( $r )[$spec['key']] ) === false && isset( \Nino\Modules\Crm\Wipe::entered( $r )[$spec['key']] ) === false );
}

check( 'a package that enters methods of its own class - as [ class, method ] or as a text class::method - is taken, and the same again is as good as the first time', ( static function() use ( &$r, $own ): bool {

	ninoWarnings();
	$erasure = [ 'package' => 'crm-probe-two', 'key' => 'twoz', 'count' => $own, 'finish' => 'Nino\\Modules\\CrmProbeTwo::init' ];
	$wipe = [ 'package' => 'crm-probe-two', 'key' => 'twoz', 'leftover' => $own, 'rows' => 'Nino\\Modules\\CrmProbeTwo::init' ];

	return \Nino\Modules\Crm\Erasure::add( $r, $erasure ) === true && \Nino\Modules\Crm\Erasure::add( $r, $erasure ) === true && \Nino\Modules\Crm\Wipe::add( $r, $wipe ) === true && \Nino\Modules\Crm\Wipe::add( $r, $wipe ) === true && ninoWarnings() === [];
} )() );

check( 'a package that declares a class in the namespace of another package is refused: the class has to be written in the folder of the package that enters it, not only to say so by its name - for either of the two', ( static function() use ( &$r, $features ): bool {

	file_put_contents( $features. '/CrmProbe/Impostor.php', "<?php\nnamespace Nino\\Modules\\CrmProbeTwo;\nclass Impostor { public static function run(): bool { return true; } }\n" );
	file_put_contents( $features. '/CrmProbeTwo/Impostor.php', "<?php\nnamespace Nino\\Modules\\CrmProbe;\nclass Impostor { public static function run(): bool { return true; } }\n" );
	require_once $features. '/CrmProbe/Impostor.php';
	require_once $features. '/CrmProbeTwo/Impostor.php';
	$wrong = [];

	foreach( [ 'a class of the namespace of the other, in the folder of the one that enters it' => [ 'crm-probe-two', \Nino\Modules\CrmProbeTwo\Impostor::class ], 'the same for the other package' => [ 'crm-probe', \Nino\Modules\CrmProbe\Impostor::class ] ] as $what => [ $package, $class ] ) {

		ninoWarnings();
		$erasure = \Nino\Modules\Crm\Erasure::add( $r, [ 'package' => $package, 'key' => 'twoq', 'count' => [ $class, 'run' ], 'reason' => 'twoq' ] );
		$wipe = \Nino\Modules\Crm\Wipe::add( $r, [ 'package' => $package, 'key' => 'twoq', 'rows' => [ $class, 'run' ] ] );

		if( $erasure !== false || $wipe !== false || count( ninoWarnings() ) !== 2 || \Nino\Modules\Crm\Register::owned( $r, $package, [ $class, 'run' ] ) !== false || isset( \Nino\Modules\Crm\Erasure::entered( $r )['twoq'] ) === true || isset( \Nino\Modules\Crm\Wipe::entered( $r )['twoq'] ) === true )
			$wrong[] = $what;
	}

	unlink( $features. '/CrmProbe/Impostor.php' );
	unlink( $features. '/CrmProbeTwo/Impostor.php' );

	return $wrong === [] && \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', [ \Nino\Modules\CrmProbeTwo::class, 'init' ] ) === true;
} )() );
check( 'self, static and parent are refused before php is asked whether they can be called: the refusal is the one warning of the register, and php raises no deprecation of its own - as an array, as a text, in capitals and with a backslash', ( static function() use ( &$r ): bool {

	// Deprecations are on for this check, whatever the configuration of the php that runs it says
	$reporting = error_reporting( E_ALL );
	$wrong = [];
	$values = [ 'self' => [ 'self', 'init' ], 'static' => [ 'static', 'init' ], 'parent' => [ 'parent', 'init' ], 'a text with self' => 'self::init', 'a text with static' => 'static::init', 'a text with parent' => 'parent::init',
		'self in capitals' => [ 'SELF', 'init' ], 'self with a backslash' => [ '\\self', 'init' ] ];

	foreach( $values as $what => $value ) {

		$entered = null;
		$called = null;
		$errors = ninoErrors( static function() use ( &$r, $value, &$entered, &$called ): void {
			$called = \Nino\Modules\Crm\Register::callable( is_string( $value ) === true ? explode( '::', $value ) : $value ) === false && \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $value ) === false;
			$entered = \Nino\Modules\Crm\Erasure::add( $r, [ 'package' => 'crm-probe-two', 'key' => 'twos', 'count' => $value, 'reason' => 'twos' ] );
		} );

		if( $called !== true || $entered !== false || array_column( $errors, 0 ) !== [ E_USER_WARNING ] || isset( \Nino\Modules\Crm\Erasure::entered( $r )['twos'] ) === true )
			$wrong[] = $what;
	}

	error_reporting( $reporting );

	return $wrong === [];
} )() );
check( 'the method part of a callable is checked as the class part is: a plain name of a public static method of the class itself - never a name with a :: in it (parent::, self::, the method of a class of another namespace), a method that is private, protected, not static or not there, or a name that is none - and php raises no deprecation of its own', ( static function() use ( &$r, $features ): bool {

	file_put_contents( $features. '/CrmProbeTwo/Shapes.php', "<?php\nnamespace Nino\\Modules\\CrmProbeTwo;\nclass Shapes extends \\ArrayObject {\n\tpublic static function run(): bool { return true; }\n\tprivate static function hidden(): bool { return true; }\n\tprotected static function guarded(): bool { return true; }\n\tpublic function instance(): bool { return true; }\n}\nclass Magic {\n\tpublic static function __callStatic( string \$name, array \$arguments ): bool { return true; }\n}\n" );
	require_once $features. '/CrmProbeTwo/Shapes.php';
	$class = \Nino\Modules\CrmProbeTwo\Shapes::class;
	$reporting = error_reporting( E_ALL );
	$wrong = [];
	$bad = [ 'parent::count' => [ $class, 'parent::count' ], 'self::run' => [ $class, 'self::run' ], 'static::run' => [ $class, 'static::run' ], 'a method of a class of another namespace' => [ $class, 'Foreign\\Lib\\Base::count' ], 'the class and the method again' => [ $class, $class. '::run' ],
		'a text with parent' => $class. '::parent::count', 'a private method' => [ $class, 'hidden' ], 'a protected method' => [ $class, 'guarded' ], 'a method that is not static' => [ $class, 'instance' ], 'a method that is not there' => [ $class, 'nothing' ],
		'a magic method that answers every name' => [ \Nino\Modules\CrmProbeTwo\Magic::class, 'anything' ], 'an empty name' => [ $class, '' ], 'a name with a blank' => [ $class, 'run ' ], 'a name with a bracket' => [ $class, 'run()' ], 'a name with a null byte' => [ $class, "run\0" ], 'a name that begins with a digit' => [ $class, '1run' ] ];

	foreach( $bad as $what => $value ) {

		$entered = null;
		$called = null;
		$errors = ninoErrors( static function() use ( &$r, $value, &$entered, &$called ): void {
			$array = is_string( $value ) === true ? explode( '::', $value, 2 ) : $value;
			$called = \Nino\Modules\Crm\Register::callable( $array ) === false && \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $value ) === false;
			$entered = \Nino\Modules\Crm\Erasure::add( $r, [ 'package' => 'crm-probe-two', 'key' => 'twov', 'count' => $value, 'reason' => 'twov' ] );
		} );

		if( $called !== true || $entered !== false || array_column( $errors, 0 ) !== [ E_USER_WARNING ] || isset( \Nino\Modules\Crm\Erasure::entered( $r )['twov'] ) === true )
			$wrong[] = $what;
	}

	$entered = [];
	$errors = ninoErrors( static function() use ( &$r, $class, &$entered ): void {
		$entered = [ \Nino\Modules\Crm\Register::callable( [ $class, 'run' ] ), \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $class. '::run' ), \Nino\Modules\Crm\Erasure::add( $r, [ 'package' => 'crm-probe-two', 'key' => 'twow', 'count' => [ $class, 'run' ], 'reason' => 'twow' ] ) ];
	} );
	error_reporting( $reporting );
	unlink( $features. '/CrmProbeTwo/Shapes.php' );

	return $wrong === [] && $entered === [ true, true, true ] && $errors === [];
} )() );
check( 'a method is the package\'s only where it is written in the folder of the package, as the class is: a static method that is inherited from a file of another package, one that comes from a trait of another file, a class written in the folder of another package and the methods the language makes (cases and from of an enum, which have no file) are refused with the one warning, and a static method that is abstract or has no body is none at all', ( static function() use ( &$r, $features ): bool {

	file_put_contents( $features. '/CrmProbe/Foreign.php', "<?php\nnamespace Foreign\\Lib;\nclass Base { public static function wipe(): bool { return true; } }\ntrait Help { public static function helped(): bool { return true; } }\n" );
	file_put_contents( $features. '/CrmProbeTwo/Derived.php', "<?php\nnamespace Nino\\Modules\\CrmProbeTwo;\nclass Child extends \\Foreign\\Lib\\Base { use \\Foreign\\Lib\\Help; public static function own(): bool { return true; } }\nenum Suit: string { case A = 'a'; public static function pick(): bool { return true; } }\ninterface Face { public static function shape(): bool; }\nabstract class Half { abstract public static function part(): bool; public static function whole(): bool { return true; } }\n" );
	// A class that says it is the package's and lies in the folder of another, with a method that is written in the folder of the package: the trait is the package's, the class is not
	file_put_contents( $features. '/CrmProbeTwo/Helping.php', "<?php\nnamespace Nino\\Modules\\CrmProbeTwo;\ntrait Helping { public static function helping(): bool { return true; } }\n" );
	file_put_contents( $features. '/CrmProbe/Blended.php', "<?php\nnamespace Nino\\Modules\\CrmProbeTwo;\nclass Blended { use Helping; }\n" );
	require_once $features. '/CrmProbe/Foreign.php';
	require_once $features. '/CrmProbeTwo/Derived.php';
	require_once $features. '/CrmProbeTwo/Helping.php';
	require_once $features. '/CrmProbe/Blended.php';
	$space = 'Nino\\Modules\\CrmProbeTwo\\';
	$reporting = error_reporting( E_ALL );
	$wrong = [];
	$other = [ 'inherited from a class of another package' => [ $space. 'Child', 'wipe' ], 'from a trait written in another file' => [ $space. 'Child', 'helped' ], 'cases of an enum' => [ $space. 'Suit', 'cases' ], 'from of an enum' => [ $space. 'Suit', 'from' ], 'tryFrom of an enum' => [ $space. 'Suit', 'tryFrom' ], 'a class written in the folder of another package, with a method of a trait of this one' => [ $space. 'Blended', 'helping' ] ];

	foreach( $other as $what => $value ) {

		$entered = null;
		$owned = null;
		$errors = ninoErrors( static function() use ( &$r, $value, &$entered, &$owned ): void {
			$owned = \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $value );
			$entered = \Nino\Modules\Crm\Erasure::add( $r, [ 'package' => 'crm-probe-two', 'key' => 'twoy', 'count' => $value, 'reason' => 'twoy' ] );
		} );

		if( $owned !== false || $entered !== false || array_column( $errors, 0 ) !== [ E_USER_WARNING ] || isset( \Nino\Modules\Crm\Erasure::entered( $r )['twoy'] ) === true )
			$wrong[] = $what;
	}

	foreach( [ 'a method that is abstract' => [ $space. 'Half', 'part' ], 'a method of an interface' => [ $space. 'Face', 'shape' ] ] as $what => $value )
		if( \Nino\Modules\Crm\Register::callable( $value ) !== false || \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $value ) !== false )
			$wrong[] = $what;

	$errors = ninoErrors( static function() use ( &$r, $space, &$wrong ): void {

		foreach( [ 'a static method of the class itself' => [ $space. 'Child', 'own' ], 'a static method of an enum' => [ $space. 'Suit', 'pick' ], 'a static method of an abstract class that has a body' => [ $space. 'Half', 'whole' ] ] as $what => $value )
			if( \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $value ) !== true )
				$wrong[] = $what;
	} );
	error_reporting( $reporting );
	unlink( $features. '/CrmProbe/Foreign.php' );
	unlink( $features. '/CrmProbeTwo/Derived.php' );
	unlink( $features. '/CrmProbeTwo/Helping.php' );
	unlink( $features. '/CrmProbe/Blended.php' );

	return $wrong === [] && $errors === [];
} )() );
check( 'the class of another package is refused by its name, as text, before anything is loaded to ask about it: its file is not read and its code does not run - for a class of a package that is switched off, as an array, as a text, with a backslash and in capitals', ( static function() use ( $features ): bool {

	[ $project, $where ] = domWorld( 'owned-load' );
	\Nino\Features::activate( $project, 'crm-probe-two' );
	\Nino\Features::deactivate( $project, 'crm-probe' );
	mkdir( $features. '/CrmProbe/Unused' );
	file_put_contents( $features. '/CrmProbe/Unused/Unused.php', "<?php\nnamespace Nino\\Modules\\CrmProbe;\n\$GLOBALS['domLoaded'] = true;\nclass Unused { public static function x(): bool { return true; } }\n" );
	$r = domRequest( $where );
	ninoWarnings();
	unset( $GLOBALS['domLoaded'] );
	$class = 'Nino\\Modules\\CrmProbe\\Unused';
	$refused = [];

	foreach( [ [ $class, 'x' ], [ '\\'. $class, 'x' ], $class. '::x', '\\'. $class. '::x', [ strtoupper( $class ), 'x' ] ] as $value )
		$refused[] = \Nino\Modules\Crm\Register::owned( $r, 'crm-probe-two', $value ) === false && \Nino\Modules\Crm\Erasure::add( $r, [ 'package' => 'crm-probe-two', 'key' => 'twol', 'count' => $value, 'reason' => 'twol' ] ) === false;

	$untouched = isset( $GLOBALS['domLoaded'] ) === false;
	ninoWarnings();
	// The file is one the autoloader would have loaded: the pin means nothing where the class cannot be reached at all
	$reachable = class_exists( $class ) === true && ( $GLOBALS['domLoaded'] ?? false ) === true;
	domRemove( $features. '/CrmProbe/Unused' );
	unset( $GLOBALS['domLoaded'] );

	return $untouched === true && $reachable === true && array_filter( $refused ) === $refused && count( $refused ) === 5;
} )() );
check( 'the letters of an icon are a text, whatever else the text is - the name of a function of php too - and what says them from the app is a method of the package written as [ class, method ]: a closure, a function, self, a method of another package and a method of this one that is none are refused with the one warning, and php says nothing of its own', ( static function() use ( &$r, $own, $foreign ): bool {

	$reporting = error_reporting( E_ALL );
	$wrong = [];
	$icon = [ 'package' => 'crm-probe-two', 'key' => 'two', 'prefix' => 'two-', 'slot' => '/icon/two', 'route' => '/crm/two' ];

	foreach( [ 'Log', 'Pi', 'Max', 'Key', 'Abs', 'Dir', 'Ord', 'End', 'Pos', 'dl', 'TW', 'A' ] as $letters ) {

		$copy = $r;
		$entered = null;
		$errors = ninoErrors( static function() use ( &$copy, $icon, $letters, &$entered ): void {
			$entered = \Nino\Modules\Crm\Surface\Icons::add( $copy, [ 'letters' => $letters ] + $icon );
		} );

		if( $entered !== true || $errors !== [] || str_contains( \Nino\Modules\Crm\Surface\Icons::svg( $copy, 'two' ), '>'. $letters. '</text>' ) === false )
			$wrong[] = $letters;
	}

	$bad = [ 'a closure' => static fn( array &$appData ): string => 'X', 'self' => [ 'self', 'init' ], 'static' => [ 'static', 'init' ], 'a method of another package' => $foreign, 'a method that is not there' => [ \Nino\Modules\CrmProbeTwo::class, 'nothing' ],
		'a function' => 'strtoupper', 'a text class::method' => 'Nino\\Modules\\CrmProbeTwo::init', 'an object' => new \stdClass(), 'a list of three' => [ \Nino\Modules\CrmProbeTwo::class, 'init', 'x' ] ];

	foreach( $bad as $what => $letters ) {

		$copy = $r;
		$entered = null;
		$errors = ninoErrors( static function() use ( &$copy, $icon, $letters, &$entered ): void {
			$entered = \Nino\Modules\Crm\Surface\Icons::add( $copy, [ 'letters' => $letters ] + $icon );
		} );

		if( $entered !== false || array_column( $errors, 0 ) !== [ E_USER_WARNING ] || str_contains( \Nino\Modules\Crm\Surface\Icons::svg( $copy, 'two' ), '>CRM</text>' ) === false )
			$wrong[] = $what;
	}

	$copy = $r;
	$errors = ninoErrors( static function() use ( &$copy, $icon, $own, &$wrong ): void {

		if( \Nino\Modules\Crm\Surface\Icons::add( $copy, [ 'letters' => $own ] + $icon ) !== true )
			$wrong[] = 'a method of the package';
	} );
	error_reporting( $reporting );

	return $wrong === [] && $errors === [];
} )() );
check( 'every place a package enters itself answers a bool and says no on a field that is no text where a text is meant: an object, a list, a number, a flag or nothing in a field is false with the one warning of the register - never an Error, a warning of php or a notice - and an id, a key or a package that is none is always refused', ( static function() use ( &$r, $features, $own ): bool {

	file_put_contents( $features. '/CrmProbeTwo/probe.js', '//' );
	file_put_contents( $features. '/CrmProbeTwo/probe.css', '/**/' );
	mkdir( $features. '/CrmProbeTwo/words' );
	$package = 'crm-probe-two';
	$weird = [ 'an object' => new \stdClass(), 'a list' => [ 'x' ], 'a nested list' => [ [ 'x' ] ], 'a number' => 7, 'a float' => 1.5, 'a flag' => true, 'nothing' => null ];
	$spec = static fn( array $base, array $ids ): array => [ 'base' => $base, 'ids' => $ids ];
	// place => [ what to call with a description, the description, the fields that name the entry or its package: they are never taken ]
	$places = [
		'Erasure::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Erasure::add( $a, $d ), [ 'package' => $package, 'key' => 'twoa', 'before' => 'logbook', 'count' => $own, 'rows' => $own, 'rows_before' => 'client', 'reason' => 'twoa', 'answer' => [ 409, 'x', 'crm_two' ], 'shared' => $own, 'gather' => $own, 'finish' => $own, 'perm' => '/_admin/crm' ], [ 'package', 'key', 'count' ] ],
		'Wipe::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Wipe::add( $a, $d ), [ 'package' => $package, 'key' => 'twob', 'rows' => $own, 'count' => true, 'before' => 'logbook', 'rows_before' => 'clients', 'leftover' => $own, 'perm' => '/_admin/crm' ], [ 'package', 'key' ] ],
		'Db::addSchema' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Db::addSchema( $a, $d ), [ 'package' => $package, 'version' => 1, 'tables' => [ 'crm_two_items' ], 'file' => $features. '/CrmProbeTwo/schema.php' ], [ 'package', 'file' ] ],
		'Settings::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Settings::add( $a, [ $d ] ), [ 'package' => $package, 'key' => 'two_key', 'default' => 'x', 'clean' => $own, 'read_only' => false, 'host' => false, 'written' => $own, 'refusal' => 'crm_two', 'before' => null, 'rank' => 500 ], [ 'package', 'key' ] ],
		'Sequences::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Sequences::add( $a, $d ), [ 'package' => $package, 'category' => 'twocat' ], [ 'package', 'category' ] ],
		'Status::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Status::add( $a, $d ), [ 'package' => $package, 'key' => 'twostatus', 'rank' => 500, 'state' => $own, 'hint' => false, 'fix' => $own ], [ 'package', 'key', 'state' ] ],
		'Notifications::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Notifications::add( $a, $d ), [ 'package' => $package, 'key' => 'two_note', 'read' => $own, 'write' => $own, 'rank' => 500 ], [ 'package', 'key', 'read', 'write' ] ],
		'Values::addType' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Values::addType( $a, $d ), [ 'package' => $package, 'type' => 'two-type', 'screen' => 'values', 'rank' => 500 ], [ 'package', 'type' ] ],
		'Records::addKind' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Records::addKind( $a, $d ), [ 'package' => $package, 'kind' => 'two-kind', 'thing' => 'two-thing', 'purposes' => [ 'one' ], 'logbook' => 'two-log', 'parent' => '', 'foreign' => false, 'owner' => $own, 'label' => $own ], [ 'package', 'kind' ] ],
		'Icons::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Surface\Icons::add( $a, $d ), [ 'package' => $package, 'key' => 'two', 'prefix' => 'two-', 'slot' => '/icon/two', 'route' => '/crm/two', 'letters' => 'TW', 'shape' => null, 'keep' => [ 'two-' ] ], [ 'package', 'key', 'prefix', 'slot', 'route' ] ],
		'Apps::add' => [ static fn( array &$a, array $d ): bool => \Nino\Modules\Crm\Surface\Apps::add( $a, $d ), [ 'package' => $package, 'key' => 'two', 'route' => '/crm/two', 'scope' => '/crm/two', 'manifest' => '/crm/two/manifest.json', 'values' => $own, 'icon' => 'two' ], [ 'package', 'key', 'route', 'scope', 'manifest', 'values', 'icon' ] ],
	];
	// The places that take plain arguments and an option array: what is tried is the options, and the list or the value that is entered
	$plain = [
		'Api::add' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Api::add( $a, [ 'crm-probe-two/run' => $own ], $o ), [ 'package' => $package, 'files' => [ 'crm-probe-two/run' ], 'needs' => '' ], [ 'package' ] ],
		'Api::add (the action)' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Api::add( $a, [ 'crm-probe-two/run' => $o['target'] ], [ 'package' => $package ] ), [ 'target' => $own ], [ 'target' ] ],
		'Logbook::addKinds' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Logbook::addKinds( $a, [ 'two-kind' ], $o ), [ 'package' => $package ], [ 'package' ] ],
		'Logbook::addKinds (the kind)' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Logbook::addKinds( $a, [ $o['kind'] ], [ 'package' => $package ] ), [ 'kind' => 'two-kind' ], [ 'kind' ] ],
		'Menu::add' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/two', 'Two', 'two', $o ), [ 'package' => $package ], [ 'package' ] ],
		'Scripts::add' => [ static function( array &$a, array $o ) use ( $features ): bool {
			$added = \Nino\Modules\Crm\Surface\Scripts::add( $a, 'two.js', $features. '/CrmProbeTwo/probe.js', $o );
			// A refusal of a script is kept and said once by the page that links the scripts (Scripts::report())
			\Nino\Modules\Crm\Surface\Scripts::report( $a );

			return $added;
		}, [ 'package' => $package ], [ 'package' ] ],
		'Scripts::addStyle' => [ static function( array &$a, array $o ) use ( $features ): bool {
			$added = \Nino\Modules\Crm\Surface\Scripts::addStyle( $a, 'two.css', $features. '/CrmProbeTwo/probe.css', $o );
			\Nino\Modules\Crm\Surface\Scripts::report( $a );

			return $added;
		}, [ 'package' => $package ], [ 'package' ] ],
		// The page is one the package entered itself: the entry of the rail is made first, with the same options
		'Devices::allow' => [ static function( array &$a, array $o ): bool {
			\Nino\Modules\Crm\Surface\Menu::add( $a, '/crm/two', 'Two', 'two', $o );

			return \Nino\Modules\Crm\Surface\Devices::allow( $a, '/crm/two', $o );
		}, [ 'package' => $package ], [ 'package' ] ],
		'Surface::addTexts' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Surface::addTexts( $a, $features. '/CrmProbeTwo/words', $o ), [ 'package' => $package ], [ 'package' ] ],
		'Texts::add' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Surface\Texts::add( $a, $features. '/CrmProbeTwo/words', $o ), [ 'package' => $package ], [ 'package' ] ],
		'Data::add (the source)' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Surface\Data::add( $a, $o['source'], [ 'package' => $package ] ), [ 'source' => $own ], [ 'source' ] ],
		'Data::add (the package)' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Surface\Data::add( $a, $own, $o ), [ 'package' => $package ], [ 'package' ] ],
		'Mail::addWords' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Mail::addWords( $a, '/feature/crm-probe-two/', $o ), [ 'package' => $package ], [ 'package' ] ],
		'Hosts::share' => [ static fn( array &$a, array $o ): bool => \Nino\Modules\Crm\Hosts::share( $a, [ '/two/' ], [ '/two' ], $o ), [ 'package' => $package ], [ 'package' ] ],
	];
	$reporting = error_reporting( E_ALL );
	$wrong = [];

	foreach( [ $places, $plain ] as $group => $list )
		foreach( $list as $name => [ $call, $base, $ids ] ) {

			$copy = $r;
			ninoWarnings();

			// The description as it should be is taken: the matrix says something only if the one field is what the refusal comes from
			if( $call( $copy, $base ) !== true || ninoWarnings() !== [] )
				$wrong[] = $name. ' (the good description)';

			// The menu and the devices say no with false alone: the rail of the page is made on every request
			$quiet = in_array( $name, [ 'Menu::add', 'Devices::allow' ], true );

			foreach( array_keys( $base ) as $field )
				foreach( $weird as $what => $value ) {

					// The source is an argument that php types as a list itself
					if( $name === 'Data::add (the source)' && is_array( $value ) === false )
						continue;

					$copy = $r;
					$result = null;
					$threw = null;
					$errors = ninoErrors( static function() use ( $call, &$copy, $base, $field, $value, &$result, &$threw ): void {

						try {
							$result = $call( $copy, [ $field => $value ] + $base );
						}
						catch( \Throwable $e ) {
							$threw = get_class( $e );
						}
					} );
					$levels = array_column( $errors, 0 );
					$good = $threw === null && ( ( $result === false && ( $levels === [ E_USER_WARNING ] || ( $quiet === true && $levels === [] ) ) ) || ( $result === true && $levels === [] && in_array( $field, $ids, true ) === false ) );

					if( $good === false )
						$wrong[] = $name. ': '. $field. ' as '. $what. ( $threw !== null ? ' threw '. $threw : '' );
				}
		}

	error_reporting( $reporting );
	unlink( $features. '/CrmProbeTwo/probe.js' );
	unlink( $features. '/CrmProbeTwo/probe.css' );
	rmdir( $features. '/CrmProbeTwo/words' );

	if( $wrong !== [] )
		fwrite( STDERR, 'WRONG '. implode( '; ', array_slice( $wrong, 0, 40 ) ). "\n" );

	return $wrong === [];
} )() );
check( 'the same description a second time is as good as the first - with a reason too, which is the entry\'s own - and another key may not take that reason', ( static function() use ( &$r, $own ): bool {

	ninoWarnings();
	$spec = [ 'package' => 'crm-probe-two', 'key' => 'twot', 'count' => $own, 'reason' => 'twot' ];
	$twice = \Nino\Modules\Crm\Erasure::add( $r, $spec ) === true && \Nino\Modules\Crm\Erasure::add( $r, $spec ) === true && ninoWarnings() === [];
	$taken = \Nino\Modules\Crm\Erasure::add( $r, [ 'key' => 'twou' ] + $spec ) === false && count( ninoWarnings() ) === 1 && isset( \Nino\Modules\Crm\Erasure::entered( $r )['twou'] ) === false;

	return $twice === true && $taken === true && count( array_filter( array_keys( \Nino\Modules\Crm\Erasure::entered( $r ) ), static fn( mixed $key ): bool => $key === 'twot' ) ) === 1;
} )() );

// The projects cannot be read: where the type file is not there, has no model or is none, nobody knows which projects there are, and no row is taken for the row of a project that is gone
[ $project, $where, $a, $m ] = domFilled( 'unreadable' );
$dora = domClient( $a, [ 'name' => 'Dora' ] );
$a['./crm-probe/fail-once'] = true;
domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );
$file = $where. '/private/elements/crm-projects.php';
$kept = (string) file_get_contents( $file );
$rowsOf = static function( array &$request ): array { return [ domCount( $request, 'tasks' ), domCount( $request, 'project_states' ), domCount( $request, 'project_shares' ), domCount( $request, 'logbook' ), domCount( $request, 'clients' ), count( \Nino\Modules\Crm\Erasure::pending( $request ) ) ]; };
$start = $rowsOf( $a );
check( 'there are rows of projects to lose: tasks, a phase, a release, entries of the logbook, and a deletion that is open', $start[0] === 2 && $start[1] === 1 && $start[2] === 2 && $start[3] >= 1 && $start[5] === 1 );

foreach( [ 'a type file that is not there' => null, 'a type file with no model' => "<?php return [];\n", 'a type file that holds no array' => "<?php return 'x';\n" ] as $what => $content ) {

	$content === null ? unlink( $file ) : file_put_contents( $file, $content );
	$lost = domRequest( $where );
	domSignIn( $lost, 'admin@example.org' );
	$change = [ 'operation' => 'delete', 'type' => \Nino\Modules\Crm\Projects::TYPE, 'uri' => \Nino\Modules\Crm\Projects::TYPE. '/'. $m['p3'] ];
	\Nino\Modules\Crm\Projects::callbackCommitted( $lost, $change );
	check( 'the kernel telling that a project is deleted is no word that it is gone where the type file cannot be read: with '. $what. ' its rows stay', $rowsOf( $lost ) === $start );
	check( 'with '. $what. ' nobody knows which projects there are: no row is cleared by anything that clears them - a client deleted, a deletion resumed, the test phase, the button of the status - each says 503 crm_projects_unreadable, and the line of the status says it', ( static function() use ( &$lost, $dora, $start, $rowsOf ): bool {

		ninoWarnings();
		$answers = [ domAnswer( $lost, 'crm-clients/delete', [ 'id' => $dora, 'confirm' => 'K-0004' ] ), domAnswer( $lost, 'crm-clients/delete', [ 'number' => 'K-0003', 'confirm' => 'K-0003' ] ), domAnswer( $lost, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ), domAnswer( $lost, 'crm/setup', [ 'key' => 'strays' ] ) ];

		return $answers === array_fill( 0, 4, '503 crm_projects_unreadable' ) && $rowsOf( $lost ) === $start && \Nino\Modules\Crm\Projects::strays( $lost ) === null && \Nino\Modules\Crm\Projects::sweep( $lost ) === 'unreadable'
			&& \Nino\Modules\Crm\Projects::strayState( $lost ) === [ 'state' => 'unreadable' ] && \Nino\Modules\Crm\Projects::sweepFix( $lost ) === 'unreadable';
	} )() );
}

file_put_contents( $file, $kept );
$back = domRequest( $where );
domSignIn( $back, 'admin@example.org' );
check( 'the type file back: everything is there as it was, nothing is a leftover, and the client that was not named is deleted as it always is', $rowsOf( $back ) === $start && \Nino\Modules\Crm\Projects::strays( $back ) === [] && domAnswer( $back, 'crm-clients/delete', [ 'id' => $dora, 'confirm' => 'K-0004' ] ) === '200 -' && domCount( $back, 'tasks' ) === 2 && domCount( $back, 'project_states' ) === 1 );
check( 'and a project whose element is not there in a type file that can be read is a leftover, and is cleared: the one true case still works', ( static function() use ( &$back ): bool {

	\Nino\Modules\Crm\Db::exec( $back, 'INSERT INTO tasks ( project, text, position, created_at, updated_at ) VALUES ( \'99999\', \'orphan\', 1, \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\' )' );
	$strays = \Nino\Modules\Crm\Projects::strays( $back );

	return $strays === [ '99999' ] && \Nino\Modules\Crm\Projects::sweep( $back ) === [ '99999' ] && domCount( $back, 'tasks' ) === 2;
} )() );

check( 'the line of the activity log is written through Api::record() for a package: only the endpoint, the deletion of a client and the test phase call Values::log() itself, and its docblock says it is internal', ( static function() use ( $source ): bool {
	$callers = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$path = (string) $file->getPathname();
		$relative = substr( $path, strlen( $source ) + 1 );

		if( str_ends_with( $path, '.php' ) === true && str_starts_with( $relative, 'tests/' ) === false && $relative !== 'Values/Values.php' && str_contains( (string) file_get_contents( $path ), 'Values::log(' ) === true )
			$callers[] = $relative;
	}

	sort( $callers );

	return $callers === [ 'Api/Api.php', 'Clients/Api/Api.php', 'Wipe/Api/Api.php' ] && preg_match( '~@internal[^/]*/\s*public static function log\(~s', (string) file_get_contents( $source. '/Values/Values.php' ) ) === 1;
} )() );

// Where the type of the projects is not there - a state the core supports - and no row of any project is in the database either, there is nothing to clear and nothing to lose: the deletion of a client, its resuming, the test phase and the
// button of the status go through. Where one row of any of the four tables is there, it is not known which projects there are, and nothing is deleted
[ $project, $where, $a ] = domWorld( 'notype' );
domSignIn( $a, 'admin@example.org' );
$nora = domClient( $a, [ 'name' => 'Nora' ] );
$a['./crm-probe/fail-once'] = true;
domAnswer( $a, 'crm-clients/delete', [ 'id' => $nora, 'confirm' => 'K-0001' ] );
$typeFile = $where. '/private/elements/crm-projects.php';
$typeKept = (string) file_get_contents( $typeFile );
unlink( $typeFile );
$bare = domRequest( $where, [ './crm-probe/domain' => true ] );
domSignIn( $bare, 'admin@example.org' );
ninoWarnings();
$projectRows = static fn( array &$request ): int => domCount( $request, 'tasks' ) + domCount( $request, 'project_states' ) + domCount( $request, 'project_shares' ) + domCount( $request, 'logbook', 'project <> \'\'' );
check( 'with no type of the projects and no row of a project there is nothing to clear: the leftovers are none, the line of the status is ok and its button is no trouble', \Nino\Modules\Crm\Projects::missing( $bare ) === true && $projectRows( $bare ) === 0 && \Nino\Modules\Crm\Projects::strays( $bare ) === []
	&& \Nino\Modules\Crm\Projects::sweep( $bare ) === [] && \Nino\Modules\Crm\Projects::strayState( $bare ) === [ 'state' => 'ok' ] && \Nino\Modules\Crm\Projects::sweepFix( $bare ) === true && domAnswer( $bare, 'crm/setup', [ 'key' => 'strays' ] ) === '200 -' );
check( 'a deletion that was begun is finished by the next press where the type of the projects is not there and no row of a project is: the journal is gone and the package took what it held', domAnswer( $bare, 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ) === '200 -' && \Nino\Modules\Crm\Erasure::pending( $bare ) === [] );
$mia = domClient( $bare, [ 'name' => 'Mia' ] );
check( 'a client is deleted where the type of the projects is not there and no row of a project is, and so is the test phase', domAnswer( $bare, 'crm-clients/delete', [ 'id' => $mia, 'confirm' => 'K-0002' ] ) === '200 -' && domCount( $bare, 'clients' ) === 0
	&& ( static function() use ( &$bare ): bool { domClient( $bare, [ 'name' => 'Ola' ] ); return domAnswer( $bare, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ) === '200 -' && domCount( $bare, 'clients' ) === 0; } )() );

file_put_contents( $typeFile, "<?php return [];\n" );
$bare = domRequest( $where, [ './crm-probe/domain' => true ] );
domSignIn( $bare, 'admin@example.org' );
$quin = domClient( $bare, [ 'name' => 'Quin' ] );
ninoWarnings();
check( 'a type file that is there and cannot be read is no type file that is missing: even with no row of a project nobody knows which projects there are, and nothing is deleted', \Nino\Modules\Crm\Projects::missing( $bare ) === false && $projectRows( $bare ) === 0 && \Nino\Modules\Crm\Projects::sweep( $bare ) === 'unreadable'
	&& \Nino\Modules\Crm\Projects::strayState( $bare ) === [ 'state' => 'unreadable' ] && domAnswer( $bare, 'crm-clients/delete', [ 'id' => $quin, 'confirm' => (string) \Nino\Modules\Crm\Clients::get( $bare, $quin )['number'] ] ) === '503 crm_projects_unreadable' && domCount( $bare, 'clients' ) === 1 );
\Nino\Modules\Crm\Db::exec( $bare, 'DELETE FROM clients' );
unlink( $typeFile );

$rowOf = [
	'a state' => 'INSERT INTO project_states ( project, phase, favorite ) VALUES ( \'99999\', \'\', 0 )',
	'a release' => 'INSERT INTO project_shares ( project, client_id, since ) VALUES ( \'99999\', :client, \'2026-01-01T00:00:00Z\' )',
	'a task' => 'INSERT INTO tasks ( project, text, position, created_at, updated_at ) VALUES ( \'99999\', \'task\', 1, \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\' )',
	'an entry of the logbook of a project' => 'INSERT INTO logbook ( project, at, source, kind, text ) VALUES ( \'99999\', \'2026-01-01T00:00:00Z\', \'crm\', \'note\', \'x\' )',
];

foreach( $rowOf as $what => $insert ) {

	$bare = domRequest( $where, [ './crm-probe/domain' => true ] );
	domSignIn( $bare, 'admin@example.org' );
	$other = domClient( $bare, [ 'name' => 'Pia' ] );
	$number = (string) \Nino\Modules\Crm\Clients::get( $bare, $other )['number'];
	\Nino\Modules\Crm\Db::exec( $bare, $insert, str_contains( $insert, ':client' ) === true ? [ 'client' => $other ] : [] );
	ninoWarnings();
	$before = $projectRows( $bare );
	$answers = [ domAnswer( $bare, 'crm-clients/delete', [ 'id' => $other, 'confirm' => $number ] ), domAnswer( $bare, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ), domAnswer( $bare, 'crm/setup', [ 'key' => 'strays' ] ) ];
	check( 'with no type of the projects and '. $what. ' of a project in the database nobody knows which projects there are: the deletion of a client, the test phase and the button each say 503 crm_projects_unreadable, and nothing is deleted', $answers === array_fill( 0, 3, '503 crm_projects_unreadable' )
		&& $projectRows( $bare ) === $before && $before >= 1 && domCount( $bare, 'clients' ) === 1 && \Nino\Modules\Crm\Projects::sweep( $bare ) === 'unreadable' && \Nino\Modules\Crm\Projects::strayState( $bare ) === [ 'state' => 'unreadable' ] );
	\Nino\Modules\Crm\Db::exec( $bare, 'DELETE FROM project_shares' );
	\Nino\Modules\Crm\Db::exec( $bare, 'DELETE FROM project_states' );
	\Nino\Modules\Crm\Db::exec( $bare, 'DELETE FROM tasks' );
	\Nino\Modules\Crm\Db::exec( $bare, 'DELETE FROM logbook WHERE project <> \'\'' );
	\Nino\Modules\Crm\Db::exec( $bare, 'DELETE FROM clients' );
}

$bare = domRequest( $where, [ './crm-probe/domain' => true ] );
domSignIn( $bare, 'admin@example.org' );
check( 'where the database is held, whether it holds a row of a project cannot be told: nothing is cleared and the answer is busy, not "none"', ( static function() use ( &$bare ): bool {
	$holder = new \PDO( 'sqlite:'. \Nino\Filesystem::path( $bare, '/data/crm.sqlite' ) );
	$holder->exec( 'BEGIN EXCLUSIVE' );

	try {
		return \Nino\Modules\Crm\Projects::sweep( $bare ) === 'busy' && \Nino\Modules\Crm\Projects::strays( $bare ) === null;
	}
	finally {
		$holder->exec( 'ROLLBACK' );
	}
} )() );
file_put_contents( $typeFile, $typeKept );

// A project is there for the leftovers wherever the type file has it - in the bucket for all and in the bucket of any language, whether the site serves that language or not - as get() finds it; the file is read once for a decision
[ $project, $where, $a, $m ] = domFilled( 'buckets' );
$typeFile = $where. '/private/elements/crm-projects.php';
$type = include $typeFile;
$type['fr_FR'][$m['p1']] = $type['*'][$m['p1']];
unset( $type['*'][$m['p1']] );
\Nino\Filesystem::putFileContent( $a, '/elements/crm-projects.php', $type );
$r = domRequest( $where, [ './crm-probe/domain' => true ] );
domSignIn( $r, 'admin@example.org' );
ninoWarnings();
$unrelated = domClient( $r, [ 'name' => 'Eve' ] );
$unrelatedNumber = (string) \Nino\Modules\Crm\Clients::get( $r, $unrelated )['number'];
$rowsOf = static fn( array &$request ): array => [ domCount( $request, 'tasks' ), domCount( $request, 'project_states' ), domCount( $request, 'project_shares' ), domCount( $request, 'logbook', 'project <> \'\'' ) ];
$was = $rowsOf( $r );
check( 'a project that stands only in the bucket of a language the site does not serve is a project that is there: the list of the ids has it, it is no leftover, and the deletion of a client that has nothing to do with it clears none of its rows', in_array( $m['p1'], (array) \Nino\Modules\Crm\Projects::elementIds( $r ), true ) === true && \Nino\Modules\Crm\Projects::get( $r, $m['p1'] ) !== null
	&& \Nino\Modules\Crm\Projects::strays( $r ) === [] && domAnswer( $r, 'crm-clients/delete', [ 'id' => $unrelated, 'confirm' => $unrelatedNumber ] ) === '200 -' && $rowsOf( $r ) === $was );
$type['*']['zz9'] = 'not an element';
\Nino\Filesystem::putFileContent( $r, '/elements/crm-projects.php', $type );
$r = domRequest( $where, [ './crm-probe/domain' => true ] );
domSignIn( $r, 'admin@example.org' );
check( 'a bucket entry that is no element and a bucket for all that is no list make the type file unreadable, and the leftovers are not known: nothing is cleared', ( static function() use ( &$r, $type, $where ): bool {
	$entry = \Nino\Modules\Crm\Projects::elementIds( $r ) === null && \Nino\Modules\Crm\Projects::sweep( $r ) === 'unreadable';
	unset( $type['*']['zz9'] );
	$type['*'] = 'broken';
	\Nino\Filesystem::putFileContent( $r, '/elements/crm-projects.php', $type );
	$bucket = domRequest( $where, [ './crm-probe/domain' => true ] );

	return $entry === true && \Nino\Modules\Crm\Projects::elementIds( $bucket ) === null && \Nino\Modules\Crm\Projects::sweep( $bucket ) === 'unreadable';
} )() );
$type['*'] = [];
$type['*'][$m['p1']] = $type['fr_FR'][$m['p1']];
unset( $type['fr_FR'] );
file_put_contents( $typeFile, "<?php\n\$GLOBALS['crmTypeReads'] = ( \$GLOBALS['crmTypeReads'] ?? 0 ) + 1;\n@touch( __FILE__, time() + 10 * \$GLOBALS['crmTypeReads'] );\nreturn ". var_export( $type, true ). ";\n" );
$GLOBALS['crmTypeReads'] = 0;
$r = domRequest( $where, [ './crm-probe/domain' => true ] );
domSignIn( $r, 'admin@example.org' );
$GLOBALS['crmTypeReads'] = 0;
\Nino\Modules\Crm\Projects::strays( $r );
$strays = $GLOBALS['crmTypeReads'];
$GLOBALS['crmTypeReads'] = 0;
\Nino\Modules\Crm\Projects::strayState( $r );
$state = $GLOBALS['crmTypeReads'];
check( 'the type file is read once for a decision about leftovers, and both answers - which projects there are, and whether rows are left of one that is gone - come from that one reading: a file that changes between two readings would have been read twice', $strays === 1 && $state === 1 );
unset( $GLOBALS['crmTypeReads'] );

// The register of the packages changes between the plan and the transaction of a deletion
[ $project, $where, $a, $m ] = domFilled( 'flipped' );
check( 'data of a package that is gone, which appear after the plan was made, stop the deletion in the transaction: the client is still there, nothing of it is deleted and the answer names the package', ( static function() use ( &$a, $m ): bool {
	$a['./crm-probe/orphan-flip'] = true;
	[ $status, $body ] = domPost( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );
	unset( $a['./crm-probe/orphan-flip'] );

	return $status === 409 && ( $body['code'] ?? '' ) === 'crm_package_off' && domCount( $a, 'clients' ) === 3 && domCount( $a, 'settings', 'key LIKE \'erasing.%\'' ) === 0;
} )() );

// A project is deleted with its rows or not at all
[ $project, $where, $a, $m ] = domFilled( 'drop' );
$rowsOf = static fn( array &$request, string $id ): array => [ domCount( $request, 'tasks', 'project = \''. $id. '\'' ), domCount( $request, 'project_shares', 'project = \''. $id. '\'' ), domCount( $request, 'logbook', 'project = \''. $id. '\'' ) ];
$was = $rowsOf( $a, $m['p3'] );
check( 'a project that is deleted while the file is held by another process - a process of its own, with a write lock - is not touched: 503 crm_busy with the time to try again, the element is there and so are its tasks, its releases and its logbook', ( static function() use ( &$a, $m, $rowsOf, $was, $where ): bool {
	$ready = $where. '/busy-ready';
	$release = $where. '/busy-release';
	$holder = proc_open( [ PHP_BINARY, __FILE__, 'holder-worker', $GLOBALS['features'], $where, (string) $m['a'], $ready, $release ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $held, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	for( $wait = 0; $wait < 5000 && is_file( $ready ) === false; $wait++ )
		usleep( 1000 );

	try {
		[ $status, $body, $request ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $m['p3'], 'locale' => '*' ] );
	}
	finally {
		file_put_contents( $release, 'go' );
		stream_get_contents( $held[1] );
		proc_close( $holder );
	}

	return $status === 503 && ( $body['code'] ?? '' ) === 'crm_busy' && ( $request['/nino/http/response']['header']['Retry-After'] ?? '' ) === '5' && \Nino\Modules\Crm\Projects::get( $a, $m['p3'] ) !== null && $rowsOf( $a, $m['p3'] ) === $was && $was[0] > 0 && $was[1] > 0 && $was[2] > 0;
} )() );
check( 'the workbench\'s own panel of the elements is no other way round: it deletes the element where the file is held by another process - a process of its own - and the listener of the kernel refuses with the sentence of the core in the language of the request: the element is there, and so are its rows', ( static function() use ( &$a, $m, $rowsOf, $was, $where ): bool {
	$ready = $where. '/panel-ready';
	$release = $where. '/panel-release';
	$holder = proc_open( [ PHP_BINARY, __FILE__, 'holder-worker', $GLOBALS['features'], $where, (string) $m['a'], $ready, $release ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $held, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	for( $wait = 0; $wait < 5000 && is_file( $ready ) === false; $wait++ )
		usleep( 1000 );

	try {
		$_POST = [ 'action' => 'elements/delete', 'data' => json_encode( [ 'type' => 'crm-projects', 'uri' => $m['p3'], 'locale' => '*' ] ) ];
		$panel = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
		$denied = ninoErrors( static function() use ( &$a, &$panel ): void { \Nino\Modules\Elements\Admin::apiDelete( $a, $panel ); } );
	}
	finally {
		$_POST = [];
		file_put_contents( $release, 'go' );
		stream_get_contents( $held[1] );
		proc_close( $holder );
	}

	$said = (string) ( $panel['/nino/http/response']['body']['error'] ?? '' ). ' '. implode( ' ', array_column( $denied, 1 ) );

	return (int) $panel['/nino/http/response']['statusCode'] !== 200 && str_contains( $said, 'Das CRM ist gerade' ) === true && \Nino\Modules\Crm\Projects::get( $a, $m['p3'] ) !== null && $rowsOf( $a, $m['p3'] ) === $was;
} )() );
check( 'a project the element store refuses to delete after its rows were taken is as it was: the rows are back, the answer is the refusal of the store', ( static function() use ( &$a, $m, $rowsOf, $was ): bool {
	$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::HOLDS] = [ 'after' => 2, 'merge' => [ 'holds' => [ [ 'key' => 'crm-probe', 'count' => 1, 'delete' => true, 'why' => 'late', 'names' => 'Things', 'delete_code' => 'crm_probe_held' ] ] ] ];
	[ $status, $body ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $m['p3'], 'locale' => '*' ] );
	unset( $a['./crm-probe/steer'], $a['./crm-probe/told'] );

	return $status === 409 && \Nino\Modules\Crm\Projects::get( $a, $m['p3'] ) !== null && $rowsOf( $a, $m['p3'] ) === $was;
} )() );
check( 'a project that is deleted goes with its rows, and the packages that keep something of it are told', ( static function() use ( &$a, $m, $rowsOf ): bool {
	$status = domAnswer( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $m['p3'], 'locale' => '*' ] );

	return $status === '200 -' && \Nino\Modules\Crm\Projects::get( $a, $m['p3'] ) === null && $rowsOf( $a, $m['p3'] ) === [ 0, 0, 1 ] && count( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::REMOVED] ?? [] ) === 1;
} )() );

// A project that is made by the screen is made whole or not at all
check( 'a package that breaks when it is told of a new project, and a logbook that cannot take its line, each take the project back: the answer says the database failed, and no project is left', ( static function() use ( &$a, $m ): bool {
	$count = static function() use ( &$a ): int { return count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) ); };
	$save = static function() use ( &$a ): array { return domPost( $a, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Whole', 'client' => 'K-0001' ] ] ); };
	$before = $count();
	$a['./crm-probe/steer'][\Nino\Modules\Crm\Projects::ADDED] = [ 'throw' => 'pdo' ];
	[ $broke, $bodyOne ] = ( static function() use ( &$a, $save ): array { ninoErrors( static function() use ( &$a, $save, &$answer ): void { $answer = $save(); } ); return $answer ?? [ 0, null ]; } )();
	unset( $a['./crm-probe/steer'] );
	$afterOne = $count();
	\Nino\Modules\Crm\Db::exec( $a, 'CREATE TRIGGER no_project_line BEFORE INSERT ON logbook WHEN NEW.kind = \'project-added\' BEGIN SELECT RAISE( ABORT, \'no\' ); END' );
	[ $lost, $bodyTwo ] = ( static function() use ( &$a, $save ): array { ninoErrors( static function() use ( &$a, $save, &$answer ): void { $answer = $save(); } ); return $answer ?? [ 0, null ]; } )();
	\Nino\Modules\Crm\Db::exec( $a, 'DROP TRIGGER no_project_line' );
	[ $fine ] = $save();


	return $broke === 500 && ( $bodyOne['code'] ?? '' ) === 'crm_database' && $afterOne === $before && $lost === 500 && ( $bodyTwo['code'] ?? '' ) === 'crm_database' && $count() === $before + 1 && $fine === 200;
} )() );

[ $project, $where, $a, $m ] = domFilled( 'panel' );
\Nino\Filesystem::putFileContent( $a, '/elements/crm-probe-values.php', [ 'title' => 'Probe', 'model' => [ 'title' => [ 'type' => 'string', 'locale' => true, 'label' => [ 'en_US' => 'T' ] ], 'ref' => [ 'type' => 'element', 'elementType' => '/crm-phases', 'label' => [ 'en_US' => 'R' ] ] ], '*' => [ '*' => [] ] ] );
domPost( $a, 'elements/save', [ 'type' => 'crm-probe-values', 'uri' => 'three', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'X' ] ] );
check( 'the sentences of the panel have their codes, each shown by a real request: a reference that points elsewhere is crm_elements_value at its field; a refusal that leaves the element there is crm_elements_vetoed with the listener\'s sentence, whether the listener gave one or not; one that leaves nothing to veto is crm_elements_failed', ( static function() use ( &$a ): bool {
	[ $one, $bodyOne ] = domPost( $a, 'elements/save', [ 'type' => 'crm-probe-values', 'uri' => 'one', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'X', 'ref' => '/wrong/x' ] ] );
	$a['./crm-probe/veto'] = 'silent';
	[ $two, $bodyTwo ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-probe-values', 'uri' => 'three', 'locale' => 'de_DE' ] );
	$a['./crm-probe/veto'] = 'reason';
	[ $three, $bodyThree ] = ( static function() use ( &$a ): array { $answer = []; ninoErrors( static function() use ( &$a, &$answer ): void { $answer = domPost( $a, 'elements/delete', [ 'type' => 'crm-probe-values', 'uri' => 'three', 'locale' => 'de_DE' ] ); } ); return $answer; } )();
	unset( $a['./crm-probe/veto'] );

	// A 409 of the panel for an element that is not there is no veto: the same translation, asked with the post of one
	$coded = new \ReflectionMethod( \Nino\Modules\Crm\Values::class, 'coded' );
	$answer = static function( string $uri ) use ( &$a, $coded ): string {
		$_POST = [ 'action' => 'elements/delete', 'data' => json_encode( [ 'type' => 'crm-probe-values', 'uri' => $uri ] ) ];
		$request = [ '/nino/http/response' => [ 'statusCode' => 409, 'body' => [ 'error' => 'The listener says no.' ] ] ];
		$coded->invokeArgs( null, [ &$a, &$request ] );
		$_POST = [];

		return (string) $request['/nino/http/response']['body']['code'];
	};

	return $one === 400 && $bodyOne['code'] === 'crm_elements_value' && $bodyOne['field'] === 'ref' && $two === 409 && $bodyTwo['code'] === 'crm_elements_vetoed' && $three === 409 && $bodyThree['code'] === 'crm_elements_vetoed' && str_contains( (string) $bodyThree['error'], 'The probe keeps it' ) === true
		&& $answer( 'three' ) === 'crm_elements_vetoed' && $answer( 'nothere' ) === 'crm_elements_failed';
} )() );

// A reader that lets go after the first wait
[ $project, $where, $a, $m ] = domFilled( 'retry' );
check( 'a commit that waits for a reader who lets go after the first wait is tried a second time and goes through: the project is deleted with its rows and the answer is the plain one', ( static function() use ( &$a, $m, $where ): bool {
	$ready = $where. '/shared-ready';
	$holder = proc_open( [ PHP_BINARY, __FILE__, 'shared-worker', $GLOBALS['features'], $where, $ready, '7' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $held, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	for( $wait = 0; $wait < 5000 && is_file( $ready ) === false; $wait++ )
		usleep( 1000 );

	[ $status, $body ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $m['p3'], 'locale' => '*' ] );
	stream_get_contents( $held[1] );
	proc_close( $holder );

	return $status === 200 && isset( $body['leftover'] ) === false && \Nino\Modules\Crm\Projects::get( $a, $m['p3'] ) === null && \Nino\Modules\Crm\Projects::strays( $a ) === [];
} )() );
check( 'a step of an open deletion that belongs to a package that is installed is not to be given up, whatever the owner names: the answer stays off:<key> and lists nothing to skip', ( static function() use ( &$a ): bool {
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( \'erasing.K-0078\', :value )', [ 'value' => json_encode( [ 'number' => 'K-0078', 'projects' => [], 'emails' => [], 'steps' => [ 'crm' => [ 'a' => 1 ] ] ] ) ] );
	$done = [];
	$answer = \Nino\Modules\Crm\Erasure::resume( $a, 'K-0078', $done, [ 'crm' ] );
	$skippable = \Nino\Modules\Crm\Erasure::skippable( $a, 'K-0078' );
	\Nino\Modules\Crm\Db::exec( $a, 'DELETE FROM settings WHERE key = \'erasing.K-0078\'' );

	return $answer === 'off:crm' && $skippable === [];
} )() );

// A project whose element goes while the rows cannot: healed, said, and cleared before anything of a person is deleted
[ $project, $where, $a, $m ] = domFilled( 'strays' );
check( 'a commit that the readers keep off for the whole wait does not leave a naked failure: the project is deleted, the answer says the rest is cleared later, the rows are left as strays that the status shows, the button clears them and the packages are told then - with a reader that is a process of its own, which holds the database for longer than both waits', ( static function() use ( &$a, $m, $where ): bool {
	$ready = $where. '/shared-ready';
	$holder = proc_open( [ PHP_BINARY, __FILE__, 'shared-worker', $GLOBALS['features'], $where, $ready, (string) ( 2 * \Nino\Modules\Crm\Db::BUSY / 1000 + 4 ) ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $held, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	for( $wait = 0; $wait < 5000 && is_file( $ready ) === false; $wait++ )
		usleep( 1000 );

	try {
		[ $status, $body ] = domPost( $a, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => $m['p3'], 'locale' => '*' ] );
	}
	finally {
		stream_get_contents( $held[1] );
		proc_close( $holder );
	}

	$gone = \Nino\Modules\Crm\Projects::get( $a, $m['p3'] ) === null;
	$strays = \Nino\Modules\Crm\Projects::strays( $a );
	$told = count( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::REMOVED] ?? [] );
	$line = \Nino\Modules\Crm\Projects::strayState( $a )['state'];
	$fix = \Nino\Modules\Crm\Projects::sweepFix( $a );

	return $status === 200 && ( $body['leftover'] ?? false ) === true && $gone === true && $strays === [ $m['p3'] ] && $line === 'leftover' && $fix === true && \Nino\Modules\Crm\Projects::strays( $a ) === []
		&& \Nino\Modules\Crm\Projects::strayState( $a )['state'] === 'ok' && domCount( $a, 'tasks', 'project = \''. $m['p3']. '\'' ) === 0 && count( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::REMOVED] ?? [] ) === $told + 1;
} )() );
check( 'the element of a project is not deleted through the Elements of the kernel while the file is held: the veto says the CRM is busy and the project stays; with the lock gone it goes, rows and all', ( static function() use ( &$a, $m ): bool {
	$holder = new \PDO( 'sqlite:'. \Nino\Filesystem::path( $a, '/data/crm.sqlite' ) );
	$holder->exec( 'BEGIN IMMEDIATE' );

	try {
		$warnings = [];
		ninoErrors( static function() use ( &$a, $m, &$result ): void { $result = \Nino\Elements::deleteElement( $a, '/crm-projects/'. $m['p2'], '*' ); } );
	}
	finally {
		$holder->exec( 'ROLLBACK' );
	}

	$kept = \Nino\Modules\Crm\Projects::get( $a, $m['p2'] ) !== null;
	$later = \Nino\Elements::deleteElement( $a, '/crm-projects/'. $m['p2'], '*' );

	return $result !== true && $kept === true && $later === true && \Nino\Modules\Crm\Projects::get( $a, $m['p2'] ) === null && \Nino\Modules\Crm\Projects::strays( $a ) === [];
} )() );
check( 'rows no project holds are cleared at the start of the deletion of a client and of the test phase, so that nothing of a person survives in them', ( static function() use ( &$a, $m ): bool {
	foreach( [ '99991', '99992' ] as $stray ) {
		\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO tasks ( project, text, done, position, created_at, updated_at, source, ref ) VALUES ( :project, \'Call Mrs Example\', 0, 1, \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\', \'manual\', \'\' )', [ 'project' => $stray ] );
		\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO logbook ( client_id, project, at, source, kind, text, data, ref ) VALUES ( NULL, :project, \'2026-01-01T00:00:00Z\', \'crm\', \'note\', \'Phone of Mrs Example\', \'{}\', \'\' )', [ 'project' => $stray ] );
	}

	$before = \Nino\Modules\Crm\Projects::strays( $a );
	$deleted = domAnswer( $a, 'crm-clients/delete', [ 'id' => $m['c'], 'confirm' => 'K-0003' ] );
	$mid = \Nino\Modules\Crm\Projects::strays( $a );
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO tasks ( project, text, done, position, created_at, updated_at, source, ref ) VALUES ( \'99993\', \'Again\', 0, 1, \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\', \'manual\', \'\' )' );
	$told = count( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::REMOVED] ?? [] );
	$wiped = domAnswer( $a, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );

	return in_array( '99993', array_column( array_slice( $a['./crm-probe/told'][\Nino\Modules\Crm\Projects::REMOVED] ?? [], $told ), 'project' ), true ) === true && $before === [ '99991', '99992' ] && $deleted === '200 -' && $mid === [] && $wiped === '200 -' && domCount( $a, 'tasks' ) === 0 && domCount( $a, 'logbook', 'text LIKE \'%Mrs Example%\'' ) === 0;
} )() );

// Two writers of one client, on different fields: neither takes the other's change back
[ $project, $where, $a, $m ] = domFilled( 'fields' );
check( 'two real processes change two fields of one client: the one that holds the write lock with its field not yet committed and the one that changes another field behind it - both fields are there at the end, and what the second read is what the first wrote', ( static function() use ( &$a, $m, $where ): bool {
	$ready = $where. '/f-ready';
	$release = $where. '/f-release';
	$start = $where. '/f-start';
	file_put_contents( $start, 'go' );
	$holder = proc_open( [ PHP_BINARY, __FILE__, 'holder-worker', $GLOBALS['features'], $where, (string) $m['a'], $ready, $release ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $held, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	for( $wait = 0; $wait < 5000 && is_file( $ready ) === false; $wait++ )
		usleep( 1000 );

	$writer = proc_open( [ PHP_BINARY, __FILE__, 'writer-worker', $GLOBALS['features'], $where, (string) $m['a'], '1', $start, '5' ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $wrote, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );
	usleep( 400000 );
	file_put_contents( $release, 'go' );
	$said = json_decode( (string) stream_get_contents( $wrote[1] ), true );
	stream_get_contents( $held[1] );
	proc_close( $writer );
	proc_close( $holder );
	$client = (array) \Nino\Modules\Crm\Clients::get( $a, $m['a'] );

	return $said === [ 'ok' ] && $client['notes'] === 'held' && str_starts_with( (string) $client['phone'], '+49 5' ) === true;
} )() );
check( 'a client is changed in the fields that were sent and only those are looked at: a field the client holds that would not be taken today stays as it is, and the change of another field goes through', ( static function() use ( &$a, $m ): bool {
	\Nino\Modules\Crm\Db::exec( $a, 'UPDATE clients SET email = \'not an address\' WHERE id = :id', [ 'id' => $m['b'] ] );
	$changed = \Nino\Modules\Crm\Clients::change( $a, $m['b'], [ 'city' => 'Graz' ] );
	$refused = \Nino\Modules\Crm\Clients::change( $a, $m['b'], [ 'email' => 'still not an address' ] );

	return is_array( $changed ) === true && $changed['city'] === 'Graz' && $changed['email'] === 'not an address' && is_string( $refused ) === true;
} )() );

// A description of a project that a package makes: no control character
[ $project, $where, $a, $m ] = domFilled( 'described' );
check( 'the description of a project of a package has no control character, no list and no bad text: each is refused with description, and none is made', ( static function() use ( &$a, $m ): bool {
	$count = count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) );
	$answers = array_map( static fn( mixed $description ): mixed => \Nino\Modules\Crm\Projects::add( $a, $m['a'], [ 'title' => 'T', 'description' => $description ], [ 'source' => 'crm-probe', 'text' => 'x' ] ), [ "a\x01b", "a\x7Fb", "a\0b", [ 'x' ], 7 ] );

	return $answers === array_fill( 0, 5, 'description' ) && count( \Nino\Elements::queryElements( $a, '/crm-projects', [], '*', [] ) ) === $count && is_array( \Nino\Modules\Crm\Projects::add( $a, $m['a'], [ 'title' => 'T', 'description' => "a\nb\tc" ], [ 'source' => 'crm-probe', 'text' => 'x' ] ) );
} )() );

}
catch( \Throwable $e ) {
	domCrashed( 'what the review asked for', $e );
}

// the end of this part of the suite is written below
foreach( (array) ( $GLOBALS['domSandboxes'] ?? [] ) as $folder )
	domRemove( (string) $folder );

$done = []; ninoDone( $done );
