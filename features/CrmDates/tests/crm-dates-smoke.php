<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-dates-smoke.php		The test of the feature CrmDates, the dates of the clients on the surface of the CRM: every request goes the way a request of the site goes - the route, the guard of the token, the
 *												session, the permission, the endpoint /crm/api - and ends in the method that answers it, over a project whose features Basis, Events, Crm and CrmDates are on. The places the package docks to
 *												(each field of what it enters, not the first alone), the four actions and what each does and refuses - a date belongs to exactly one client and the actions are no editor of the type: a date of
 *												no group, of a group of Members and of a group that is no client's is not found, not changed and not deleted, byte for byte -, the rights of the Elements panel (the door and the scoped ones),
 *												the times of the site (its zone, a day with no time, the hour the clocks skip, the hour they pass twice, the day they change), the type of Events missing, broken or off (the state, the
 *												line of the status, no write, no delete, the hold on the deletion of a client), the deletion of a client and the test phase with their steps (a step that fails once, the dates of a client and
 *												of no other, a name that is only digits), the overview, the calendar file that names the owner, the page of the surface with its data, script and words, and the package with the core or
 *												Events gone. One pin for each behaviour, over the structure and never the words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/CrmDates/tests/crm-dates-smoke.php
 *	       NINO_ROOT=../nino php features/CrmDates/tests/crm-dates-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$source = dirname( __DIR__ );
$catalogue = dirname( __DIR__, 2 );

/**
 *	Copy a folder with what is in it
 *
 *	@param		string		$from
 *	@param		string		$to
 *
 *	@return 	void
 */
function dtCopy( string $from, string $to ): void {

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
function dtRemove( string $folder ): void {

	if( is_dir( $folder ) === false && is_link( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

/*	The features directory of the suite: a folder of its own, made for the run, with copies of the features the package stands on and the package itself - never the folder of the catalogue	*/
$features = sys_get_temp_dir(). '/nino-crm-dates-features-'. bin2hex( random_bytes( 6 ) );
mkdir( $features, 0755, true );

foreach( [ 'Basis', 'Events', 'Crm', 'CrmDates' ] as $name )
	dtCopy( $catalogue. '/'. $name, $features. '/'. $name );

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-dates-smoke.php names its own features directory: NINO_FEATURES_DIR is defined already\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

date_default_timezone_set( 'UTC' );

/**
 *	A request of a project that exists: the app data of a sandbox that is on the disk already, the way a request builds it, with the session of the account that is signed in
 *
 *	@param		string		$sandbox							The directory of the project
 *	@param		array|null	$session						What the session holds; null for a visitor
 *	@param		array 		$set									What the request has before the modules are initialised
 *
 *	@return 	array
 */
function dtRequest( string $sandbox, ?array $session = null, array $set = [] ): array {

	$_SESSION = $session ?? [];

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
	$appData['/nino/features']								= (array) ( $config['/nino/features'] ?? [] );
	$appData['./nino/test/sandbox']						= $sandbox;
	$appData																	= $set + ( $GLOBALS['dtSet'] ?? [] ) + $appData;

	\Nino\Html::init( $appData );
	\Nino\Csrf::init( $appData );
	\Nino\Auth::init( $appData );
	\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

const DT_ACCOUNTS = [
	'admin@example.org'		=> [ 'admin password', [ '/*' ] ],
	'plain@example.org'		=> [ 'plain password', [] ],
	'crm@example.org'			=> [ 'crm password', [ '/_admin/crm/manage' ] ],
	'elements@example.org'	=> [ 'elements password', [ '/_admin/crm/manage', '/_admin/elements/manage' ] ],
	'door@example.org'		=> [ 'door password', [ '/_admin/elements/manage' ] ],
	'delete@example.org'		=> [ 'delete password', [ '/_admin/crm/manage', '/_admin/elements/manage', '/_admin/elements/events/delete' ] ],
	'insert@example.org'		=> [ 'insert password', [ '/_admin/crm/manage', '/_admin/elements/manage', '/_admin/elements/events/insert' ] ],
	'title@example.org'		=> [ 'title password', [ '/_admin/crm/manage', '/_admin/elements/manage', '/_admin/elements/events/update/title' ] ],
];

/**
 *	A project with Basis, Events, the core and the package switched on, and the accounts of the suite
 *
 *	@param		string		$name
 *	@param		array 		$only					The features to switch on, in order; all four by default
 *
 *	@return 	array											[ the directory of the project, the sessions of the accounts by their address ]
 */
function dtWorld( string $name, array $only = [ 'basis', 'events', 'crm', 'crm-dates' ] ): array {

	$project = ninoSandbox( $name );
	$GLOBALS['dtSandboxes'][] = ninoSandboxDir( $project );

	foreach( $only as $key )
		\Nino\Features::activate( $project, $key );

	$where = ninoSandboxDir( $project );
	$a = dtRequest( $where );
	$sessions = [];

	foreach( DT_ACCOUNTS as $mail => [ $password, $perms ] )
		\Nino\Auth::insertUser( $a, $mail, $password, $perms );

	foreach( DT_ACCOUNTS as $mail => [ $password ] ) {
		$b = dtRequest( $where );
		\Nino\Auth::loginUser( $b, $mail, $password );
		$sessions[$mail] = $_SESSION;
	}

	check( 'the world of the suite, '. $name. ', starts with no warning or notice of PHP', ninoWarnings() === [] );

	return [ $where, $sessions ];
}

/**
 *	One post to the endpoint, the way the page makes it: the request of the site, the route, the guard of the token, the callbacks of the route. Each is a request of its own, with the session of the account
 *
 *	@param		string		$where
 *	@param		array|null	$session
 *	@param		string		$action
 *	@param		mixed			$data
 *	@param		array 		$o						token (null: the token of the session; '' none), body (the body as it is), set (what the request has before the modules start), hook (a function the request is given once its modules have started)
 *
 *	@return 	array											[ status, body, the app data of the request ]
 */
function dtPost( string $where, ?array $session, string $action, mixed $data = [], array $o = [] ): array {

	$a = dtRequest( $where, $session, (array) ( $o['set'] ?? [] ) );

	if( isset( $o['hook'] ) === true )
		$o['hook']( $a );

	$token = array_key_exists( 'token', $o ) === true ? (string) $o['token'] : \Nino\Csrf::getToken( $a );
	$server = [ 'REMOTE_ADDR' => '127.0.0.1', 'CONTENT_TYPE' => 'application/json' ];

	if( $token !== '' )
		$server['HTTP_X_CSRF_TOKEN'] = $token;

	$request = ninoVisit( $a, '/crm/api', 'POST', $server );
	$request['/nino/http/request']['body'] = array_key_exists( 'body', $o ) === true ? $o['body'] : json_encode( [ 'action' => $action, 'data' => $data ] );

	$posted = $_POST;
	$_POST = [];

	try {
		\Nino\Http::response( $a, $request );
	}
	catch( \Throwable $e ) {

		// What a request that ends in an error is to the person: no answer of the package - the pins that expect one say it on their own lines
		$request['/nino/http/response']['statusCode'] = 599;
		$request['/nino/http/response']['body'] = [ 'thrown' => get_class( $e ) ];
	}
	finally {
		$_POST = $posted;
	}

	// What the page reads: the answer as json carries it
	$body = $request['/nino/http/response']['body'] ?? null;
	$body = is_array( $body ) === true ? json_decode( (string) json_encode( $body ), true ) : $body;

	return [ (int) $request['/nino/http/response']['statusCode'], $body, $a ];
}

/**
 *	The status and the code of a post, for a pin that asks nothing more
 *
 *	@param		string		$where
 *	@param		array|null	$session
 *	@param		string		$action
 *	@param		mixed			$data
 *	@param		array 		$o
 *
 *	@return 	string										'400 crm_dates_title' - the status and the code ('-' for none)
 */
function dtAnswer( string $where, ?array $session, string $action, mixed $data = [], array $o = [] ): string {

	[ $status, $body ] = dtPost( $where, $session, $action, $data, $o );

	return $status. ' '. ( is_array( $body ) === true && is_string( $body['code'] ?? null ) === true ? $body['code'] : '-' );
}

/**
 *	The body of a post that was answered 200; [] for any other answer
 *
 *	@param		string		$where
 *	@param		array|null	$session
 *	@param		string		$action
 *	@param		mixed			$data
 *	@param		array 		$o
 *
 *	@return 	array
 */
function dtBody( string $where, ?array $session, string $action, mixed $data = [], array $o = [] ): array {

	[ $status, $body ] = dtPost( $where, $session, $action, $data, $o );

	return $status === 200 && is_array( $body ) === true ? $body : [];
}

/**
 *	One GET of the site, the way the server hands it over, with the callbacks of the response run
 *
 *	@param		string		$where
 *	@param		string		$uri
 *	@param		array|null	$session
 *	@param		array 		$set
 *
 *	@return 	array											[ status, body, headers, the app data of the request ]
 */
function dtGet( string $where, string $uri, ?array $session = null, array $set = [] ): array {

	$a = dtRequest( $where, $session, $set );
	$kept = $_SERVER;
	$_SERVER = [ 'REMOTE_ADDR' => '127.0.0.1' ] + $_SERVER;

	try {
		$request = \Nino\request( $a, [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1' ] );
	}
	finally {
		$_SERVER = $kept;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request['/nino/http/response']['header'] ?? [], $a ];
}

/**
 *	A date made as the actions make one: the element, through the kernel, in the native language, with the fields the type has - and, where its group is the group of a client that is there, the row that
 *	says it is that client's. A date made by hand in the Elements panel has no row: it is made with $row false
 *
 *	@param		string		$where
 *	@param		string		$name
 *	@param		array 		$fields
 *	@param		bool			$row
 *
 *	@return 	void
 */
function dtMake( string $where, string $name, array $fields, bool $row = true ): void {

	$a = dtRequest( $where );
	$made = \Nino\Elements::insertElement( $a, '/events/'. $name, $fields, 'de_DE' );

	if( is_array( $made ) === false )
		echo "FAIL  - the fixture $name was not made\n";

	$number = strtoupper( trim( (string) ( $fields['group'] ?? '' ) ) );
	$client = $row === true && preg_match( '/^K-[0-9]{4,}$/D', $number ) === 1 ? \Nino\Modules\Crm\Clients::byNumber( $a, $number ) : null;

	if( $client !== null )
		\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO crm_dates ( name, client_id ) VALUES ( :name, :client )', [ 'name' => $name, 'client' => $client['id'] ] );

	\Nino\Modules\Crm\Db::close( $a );
}

/**
 *	The bytes of the file of the type
 *
 *	@param		string		$where
 *
 *	@return 	string
 */
function dtFile( string $where ): string {

	return (string) @file_get_contents( $where. '/private/elements/events.php' );
}

/**
 *	What the file of the type holds, as the kernel reads it, without one element
 *
 *	@param		string		$where
 *	@param		array 		$without				Names of elements that are left out
 *
 *	@return 	array
 */
function dtData( string $where, array $without = [] ): array {

	$data = (array) ( include $where. '/private/elements/events.php' );

	foreach( $data as $bucket => $elements )
		if( $bucket !== 'model' && is_array( $elements ) === true )
			foreach( $without as $name )
				unset( $data[$bucket][$name] );

	return $data;
}

/**
 *	A hook for a request that vetoes the deletion of an element of the type a number of times: what the deletion of a client and of the test phase meet when a step of the package fails once
 *
 *	@param		int				$times
 *
 *	@return 	callable
 */
function dtVeto( int $times ): callable {

	$left = $times;

	return static function( array &$a ) use ( &$left ): void {
		\Nino\Callbacks::registerCallback( $a, '/nino/elements/delete/events', static function( array &$appData, mixed &$typeData ) use ( &$left ): mixed {

			if( $left > 0 ) {
				$left--;
				return false;
			}

			return $typeData;
		} );
	};
}

/**
 *	The rows of the table of the package: name and client id, in the order of the names
 *
 *	@param		string		$where
 *
 *	@return 	array
 */
function dtRows( string $where ): array {

	$a = dtRequest( $where );
	$rows = \Nino\Modules\Crm\Db::rows( $a, 'SELECT name, client_id FROM crm_dates ORDER BY name' );
	\Nino\Modules\Crm\Db::close( $a );

	return array_map( static fn( array $row ): array => [ (string) $row['name'], (int) $row['client_id'] ], $rows );
}

/**
 *	The names of the dates of the type, whatever group they have
 *
 *	@param		string		$where
 *
 *	@return 	array											A list of names, as text
 */
function dtNames( string $where ): array {

	$a = dtRequest( $where );

	return array_map( static fn( mixed $element ): string => substr( (string) $element['.uri'], strlen( '/events/' ) ), \Nino\Elements::queryElements( $a, '/events', [], '*', [] ) );
}

/**
 *	One date as the file holds it, in the native language and the global bucket together
 *
 *	@param		string		$where
 *	@param		string		$name
 *
 *	@return 	array|null
 */
function dtOne( string $where, string $name ): ?array {

	$a = dtRequest( $where );
	$found = \Nino\Elements::getElement( $a, '/events/'. $name, '*' );

	return is_array( $found ) === true ? $found : null;
}

/**
 *	The clock of the site, as the number of seconds the request is given: a wall-clock time of the zone the project reads its dates in
 *
 *	@param		string		$local				2026-11-25T12:00
 *	@param		string		$zone
 *
 *	@return 	array											What a request is given before its modules start
 */
function dtClock( string $local, string $zone = 'Europe/Berlin' ): array {

	return [ './events/now' => ( new \DateTimeImmutable( $local, new \DateTimeZone( $zone ) ) )->getTimestamp() ];
}

/**
 *	The clients of the world: Ada (K-0001), Bob (K-0002) and Carla (K-0003, switched off)
 *
 *	@param		string		$where
 *	@param		array 		$session				The owner
 *
 *	@return 	array											number => id
 */
function dtClients( string $where, array $session ): array {

	$ids = [];

	foreach( [ 'K-0001' => 'Ada Example', 'K-0002' => 'Bob Beispiel', 'K-0003' => 'Carla Aus' ] as $number => $name ) {
		$made = dtBody( $where, $session, 'crm-clients/save', [ 'name' => $name ] );
		$ids[$number] = (int) ( $made['client']['id'] ?? 0 );

		if( ( $made['client']['number'] ?? '' ) !== $number )
			echo "FAIL  - the client $name has not the number $number\n";
	}

	dtBody( $where, $session, 'crm-clients/active', [ 'id' => (string) $ids['K-0003'], 'active' => false ] );

	return $ids;
}

/**
 *	The dates a world starts with, which the checks of the actions read: the dates of the clients Ada and Bob and of Carla, and the elements that are none of a client's
 *
 *	@param		string		$where
 *
 *	@return 	void
 */
function dtDates( string $where ): void {

	$day = static fn( string $start, string $group, string $title, array $more = [] ): array => [ 'title' => $title, 'start' => $start, 'group' => $group ] + $more;

	dtMake( $where, 'on-a-now', $day( '2026-11-25T11:00', 'k-0001', 'Running', [ 'end' => '2026-11-25T13:00' ] ) );
	dtMake( $where, 'on-a-future', $day( '2026-12-01T10:00', 'k-0001', 'Kickoff', [ 'end' => '2026-12-01T12:00', 'place' => 'Hall 1', 'text' => '<p>Bring <strong>slides</strong></p><ul><li>one</li><li>two</li></ul>' ] ) );
	dtMake( $where, 'on-b-future', $day( '2026-12-03T09:00', 'k-0002', 'Review' ) );
	dtMake( $where, 'on-c-future', $day( '2026-12-05T10:00', 'k-0003', 'Carla meets' ) );
	dtMake( $where, '20261224', $day( '2026-12-24T18:00', 'k-0001', 'Christmas' ) );
	dtMake( $where, 'on-a-past', $day( '2026-11-10T09:00', 'k-0001', 'Earlier', [ 'end' => '2026-11-10T10:00' ] ) );
	dtMake( $where, 'on-a-far', $day( '2027-06-01T00:00', 'K-0001', 'Far away' ) );
	dtMake( $where, 'public', $day( '2026-12-02T10:00', '', 'Open house' ) );
	dtMake( $where, 'team', $day( '2026-12-02T11:00', 'team', 'Team day' ) );
	dtMake( $where, 'ghost', $day( '2026-12-02T12:00', 'k-0099', 'Nobody\'s' ) );
	dtMake( $where, 'typo', $day( '2026-12-02T13:00', '!!!', 'Typed group' ) );
}

/**
 *	The sessions as the checks use them, and the clients: a world with its dates
 *
 *	@param		string		$name
 *
 *	@return 	array											[ where, sessions, client ids ]
 */
function dtStart( string $name ): array {

	[ $where, $s ] = dtWorld( $name );
	$ids = dtClients( $where, $s['admin@example.org'] );
	dtDates( $where );

	return [ $where, $s, $ids ];
}

/**
 *	The ids of a list of dates, as the screens read them
 *
 *	@param		mixed			$dates
 *
 *	@return 	array
 */
function dtIds( mixed $dates ): array {

	return is_array( $dates ) === true ? array_column( $dates, 'id' ) : [ 'none' ];
}


/**
 *	Set the setting feedContact of the package in the files of the project
 *
 *	@param		string		$where
 *	@param		mixed			$value				true, false, or null for not saved
 *
 *	@return 	void
 */
function dtFeed( string $where, mixed $value ): void {

	$a = dtRequest( $where );
	\Nino\Filesystem::mutate( $a, '/config.php', static function( array $content ) use ( $value ): array {
		$content['/nino/features']['crm-dates']['settings'] = $value === null ? [] : [ 'feedContact' => $value ];
		$content['/nino/features']['crm-dates']['version'] ??= '2.0.0';

		return $content;
	} );
}

/**
 *	A request of its own in another process that stops at a signal file: the hook is a listener of the insert of the element, which waits - polling a file, no time is guessed - until the file $go is there.
 *	The process says it waits by making the file $ready, and is gone, without the shutdown of the suite, when it has answered
 *
 *	@param		string		$where
 *	@param		array 		$session
 *	@param		array 		$data						The post of crm-dates/save
 *	@param		string		$ready
 *	@param		string		$go
 *
 *	@return 	int												The process id
 */
function dtStalled( string $where, array $session, array $data, string $ready, string $go, string $out = '' ): int {

	$pid = pcntl_fork();

	if( $pid !== 0 )
		return $pid;

	try {
		[ $status, $body ] = dtPost( $where, $session, 'crm-dates/save', $data, [ 'hook' => static function( array &$a ) use ( $ready, $go ): void {
			\Nino\Callbacks::registerCallback( $a, '/nino/elements/events/insert', static function( array &$appData, mixed &$typeData ) use ( $ready, $go ): mixed {

				touch( $ready );

				while( file_exists( $go ) === false )
					usleep( 5000 );

				return $typeData;
			} );
		} ] );

		if( $out !== '' )
			file_put_contents( $out, $status. ' '. ( is_array( $body ) === true ? (string) ( $body['code'] ?? '-' ) : '-' ) );
	}
	catch( \Throwable $e ) {
	}

	posix_kill( getmypid(), SIGKILL );
	exit( 0 );
}

/**
 *	A post in a process of its own, which writes the status of the answer into a file and is gone when it has answered
 *
 *	@param		string		$where
 *	@param		array 		$session
 *	@param		string		$action
 *	@param		array 		$data
 *	@param		string		$out						The file for the status
 *
 *	@return 	int												The process id
 */
function dtForked( string $where, array $session, string $action, array $data, string $out ): int {

	$pid = pcntl_fork();

	if( $pid !== 0 )
		return $pid;

	try {
		[ $status ] = dtPost( $where, $session, $action, $data );
		file_put_contents( $out, (string) $status );
	}
	catch( \Throwable $e ) {
		file_put_contents( $out, 'thrown' );
	}

	posix_kill( getmypid(), SIGKILL );
	exit( 0 );
}

/**
 *	Wait for a signal file the other process makes, for at most a number of seconds
 *
 *	@param		string		$file
 *
 *	@return 	bool
 */
function dtWaitFor( string $file ): bool {

	for( $i = 0; $i < 2000 && file_exists( $file ) === false; $i++ )
		usleep( 5000 );

	return file_exists( $file );
}

/**
 *	Whether the system tells what another process of it holds open: the pins of the save that meets a client that is deleted wait for it
 *
 *	@return 	bool
 */
function dtProcOpen(): bool {

	return is_dir( '/proc/self/fd' ) === true;
}

/**
 *	A save in a process of its own that is held back at the lock of the type of the dates, while the process that asked holds it: the request has read the date, passed the checks and waits for the write. When it is
 *	waiting, the one who holds the lock does what another request would do - deletes the client and what is its - and lets go. Nothing of the package is changed or waited for from inside: the lock is the real one
 *
 *	@param		string		$where
 *	@param		array 		$session
 *	@param		array 		$data						What the save posts
 *	@param		callable	$meanwhile			Called with the app data that holds the lock, once the save waits; it does what happens in the meantime
 *	@param		callable|null	$hook				Given to the request of the save, to register what it is to meet
 *	@param		array 		$set						What the request has before its modules start
 *
 *	@return 	array											[ the status and the code, the number of warnings the save raised ]; [ 'no /proc', 0 ] where the system does not tell
 */
function dtSaveMeets( string $where, array $session, array $data, callable $meanwhile, ?callable $hook = null, array $set = [] ): array {

	if( dtProcOpen() === false )
		return [ 'no /proc', 0 ];

	$out = $where. '/meets-'. bin2hex( random_bytes( 4 ) );
	$pid = pcntl_fork();

	// The save starts when the lock is held by the other: it opens the lock file itself, and no handle it shares with the holder
	if( $pid === 0 ) {

		try {
			dtWaitFor( $out. '.start' );
			[ $status, $body ] = dtPost( $where, $session, 'crm-dates/save', $data, [ 'hook' => $hook, 'set' => $set ] );
			file_put_contents( $out, $status. ' '. ( is_array( $body ) === true ? (string) ( $body['code'] ?? '-' ) : '-' ). ' '. count( ninoWarnings() ) );
		}
		catch( \Throwable $e ) {
		}

		posix_kill( getmypid(), SIGKILL );
		exit( 0 );
	}

	$holder = dtRequest( $where );

	if( \Nino\Filesystem::lockFile( $holder, '/elements/events.php' ) === false ) {
		touch( $out. '.start' );
		pcntl_waitpid( $pid, $status );

		return [ 'no lock', 0 ];
	}

	$held = array_values( (array) $holder['./nino/filesystem/locks'] )[0] ?? null;
	$lock = is_resource( $held ) === true ? (string) ( stream_get_meta_data( $held )['uri'] ?? '' ) : '';
	touch( $out. '.start' );

	// The save waits when it has the lock file open: it has opened it to ask for the lock, which the other holds
	$waits = false;

	for( $i = 0; $i < 2000 && $waits === false; $i++ ) {

		foreach( (array) glob( '/proc/'. $pid. '/fd/*' ) as $fd )
			if( @readlink( (string) $fd ) === $lock )
				$waits = true;

		$waits === true || usleep( 5000 );
	}

	if( $waits === true )
		$meanwhile( $holder );

	foreach( (array) $holder['./nino/filesystem/locks'] as $handle )
		is_resource( $handle ) === true && fclose( $handle );

	unset( $holder['./nino/filesystem/locks'] );
	pcntl_waitpid( $pid, $status );
	$answer = trim( (string) @file_get_contents( $out ) );
	@unlink( $out );
	@unlink( $out. '.start' );

	$parts = $answer === '' ? [] : explode( ' ', $answer );

	return $waits === true ? [ ( $parts[0] ?? '-' ). ' '. ( $parts[1] ?? '-' ), (int) ( $parts[2] ?? 0 ) ] : [ 'never waited', 0 ];
}

/**
 *	An action in a process of its own that is held back at the lock of the database: the other process holds the write lock, has deleted what it deletes in its transaction, and lets go when the action sleeps in the
 *	wait for it - the process says it has come as far as the action, and is then asleep for a good while: all it has left to do is the write
 *
 *	@param		string		$where
 *	@param		array 		$session
 *	@param		string		$action
 *	@param		array 		$data
 *	@param		callable	$meanwhile			Called with the connection that holds the lock, to delete what is deleted in the meantime
 *	@param		array 		$set
 *
 *	@return 	string										The status and the code of the answer; 'no /proc' where the system tells nothing
 */
function dtActionWaits( string $where, array $session, string $action, array $data, callable $meanwhile, array $set = [] ): string {

	if( dtProcOpen() === false )
		return 'no /proc';

	$out = $where. '/waits-'. bin2hex( random_bytes( 4 ) );
	$pid = pcntl_fork();

	if( $pid === 0 ) {

		try {
			dtWaitFor( $out. '.start' );
			[ $status, $body ] = dtPost( $where, $session, $action, $data, [ 'set' => $set, 'hook' => static function( array &$a ) use ( $out ): void {
				touch( $out. '.ready' );
			} ] );
			file_put_contents( $out, $status. ' '. ( is_array( $body ) === true ? (string) ( $body['code'] ?? '-' ) : '-' ) );
		}
		catch( \Throwable $e ) {
		}

		posix_kill( getmypid(), SIGKILL );
		exit( 0 );
	}

	$holder = dtRequest( $where );
	$pdo = \Nino\Modules\Crm\Db::pdo( $holder );
	$pdo->exec( 'BEGIN IMMEDIATE' );
	$meanwhile( $pdo );
	touch( $out. '.start' );
	$asleep = 0;

	if( dtWaitFor( $out. '.ready' ) === true )
		for( $i = 0; $i < 4000 && $asleep < 40; $i++ ) {

			$state = (string) @file_get_contents( '/proc/'. $pid. '/stat' );
			$asleep = preg_match( '/^[0-9]+ \(.*\) S /s', $state ) === 1 ? $asleep + 1 : 0;
			usleep( 2500 );
		}

	$pdo->exec( 'COMMIT' );
	pcntl_waitpid( $pid, $status );
	$answer = trim( (string) @file_get_contents( $out ) );
	@unlink( $out );
	@unlink( $out. '.start' );
	@unlink( $out. '.ready' );

	return $answer === '' ? 'no answer' : $answer;
}

$clock = dtClock( '2026-11-25T12:00', 'UTC' );

echo "\nThe package: what it enters, and where it is on\n";

[ $where, $s, $ids ] = dtStart( 'enters' );
$a = dtRequest( $where, $s['admin@example.org'] );
$first = $a;
$manifest = (array) require $features. '/CrmDates/feature.php';

check( 'the manifest: key crm-dates, the core and Events required, category business, Nino 2, version 2.0.0, no data of its own and the one setting, the contact in the calendar file, which is off', $manifest['key'] === 'crm-dates' && $manifest['requires'] === [ 'crm', 'events' ] && $manifest['category'] === 'business'
	&& $manifest['nino'] === '^2.0' && $manifest['version'] === '2.0.0' && $manifest['data'] === [] && array_keys( $manifest['settings'] ) === [ 'feedContact' ] && $manifest['settings']['feedContact']['type'] === 'bool' && $manifest['settings']['feedContact']['default'] === false && $manifest['assets'] === [] );
check( 'the feature is on, with no problem, and a project that has it is warned of nothing', \Nino\Modules\Crm::featureOn( $a, 'crm-dates' ) === true && ninoWarnings() === [] );

$actions = \Nino\Modules\Crm\Api::actions( $a );
check( 'the six actions are entered, each under the package and its own key, with the method that answers it, none with a file and none that needs another feature',
	array_values( array_filter( array_keys( $actions ), static fn( string $name ): bool => str_starts_with( $name, 'crm-dates/' ) ) ) === [ 'crm-dates/list', 'crm-dates/get', 'crm-dates/save', 'crm-dates/delete', 'crm-dates/adopt', 'crm-dates/tidy' ]
	&& array_filter( [ 'list' => 'apiList', 'get' => 'apiGet', 'save' => 'apiSave', 'delete' => 'apiDelete', 'adopt' => 'apiAdopt', 'tidy' => 'apiTidy' ], static fn( string $method, string $name ): bool => ( $actions['crm-dates/'. $name]['target'] ?? null ) !== [ \Nino\Modules\CrmDates\Api::class, $method ]
		|| $actions['crm-dates/'. $name]['package'] !== 'crm-dates' || $actions['crm-dates/'. $name]['files'] !== false || $actions['crm-dates/'. $name]['needs'] !== '', ARRAY_FILTER_USE_BOTH ) === [] );

$schemas = \Nino\Modules\Crm\Db::schemas( $a );
check( 'the package has its table in the file of the CRM under its own key: registered, written for version 1, stored as 1, and the one table - with the time each row was made in (created) and a key on the client that restricts - and no step of a version that was never there', ( $schemas['crm-dates'] ?? [] ) === [ 'registered' => true, 'version' => 1, 'stored' => 1, 'tables' => [ 'crm_dates' ] ] && array_column( \Nino\Modules\Crm\Db::rows( $a, 'PRAGMA table_info( crm_dates )' ), 'name' ) === [ 'name', 'client_id', 'created' ] && ( (array) ( include $features. '/CrmDates/schema.php' ) )['steps'] === []
	&& \Nino\Modules\Crm\Db::row( $a, 'SELECT COUNT(*) AS n FROM sqlite_master WHERE name = :t', [ 't' => 'crm_dates' ] )['n'] === 1 && ( \Nino\Modules\Crm\Db::rows( $a, 'PRAGMA foreign_key_list( crm_dates )' )[0]['table'] ?? '' ) === 'clients' && ( \Nino\Modules\Crm\Db::rows( $a, 'PRAGMA foreign_key_list( crm_dates )' )[0]['on_delete'] ?? '' ) === 'RESTRICT' );

$lines = \Nino\Modules\Crm\Status::lines( $a );
$mine = array_values( array_filter( $lines, static fn( array $one ): bool => $one['key'] === 'dates-type' ) );
$entered = \Nino\Modules\Crm\Register::all( $a, './crm/status/lines' )['dates-type'] ?? [];
$unas = \Nino\Modules\Crm\Register::all( $a, './crm/status/lines' )['dates-unassigned'] ?? [];
$unasLine = array_values( array_filter( $lines, static fn( array $one ): bool => $one['key'] === 'dates-unassigned' ) );
check( 'the line of the status dates-unassigned: the package named, its state its own method, a rank after the other line, no fix, only a hint; it says leftover for the date of this world that has a group and no row, with a label and a sentence', ( $unas['package'] ?? '' ) === 'crm-dates' && ( $unas['state'] ?? null ) === [ \Nino\Modules\CrmDates\Status::class, 'unassigned' ]
	&& ( $unas['rank'] ?? 0 ) === 521 && array_key_exists( 'fix', $unas ) === true && $unas['fix'] === null && ( $unas['hint'] ?? false ) === true && count( $unasLine ) === 1 && $unasLine[0]['state'] === 'leftover' && $unasLine[0]['hint'] === true && $unasLine[0]['label'] !== '' && $unasLine[0]['detail'] !== '' && str_contains( $unasLine[0]['detail'], 'unassigned' ) === false );
check( 'the line of the status dates-type: the package named, its state its own method, a rank, no fix, not a hint; it answers ok, with a label and a sentence', ( $entered['package'] ?? '' ) === 'crm-dates' && ( $entered['state'] ?? null ) === [ \Nino\Modules\CrmDates\Status::class, 'state' ] && ( $entered['rank'] ?? 0 ) === 520
	&& array_key_exists( 'fix', $entered ) === true && $entered['fix'] === null && ( $entered['hint'] ?? true ) === false && count( $mine ) === 1 && $mine[0]['state'] === 'ok' && $mine[0]['label'] !== '' && $mine[0]['label'] !== 'dates.status-label' && $mine[0]['detail'] !== '' && $mine[0]['detail'] !== 'dates.status-ok' && $mine[0]['fixable'] === false );

$erasure = \Nino\Modules\Crm\Erasure::entered( $a )['dates'] ?? [];
check( 'the step of the deletion of a client: the package named, the count, the gather, the rows of its table and the finish its own methods, the permission of the Elements panel, no reason, none before another count, nothing shared',
	( $erasure['package'] ?? '' ) === 'crm-dates' && ( $erasure['count'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'count' ] && ( $erasure['gather'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'gather' ]
	&& ( $erasure['finish'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'finish' ] && ( $erasure['perm'] ?? null ) === '/_admin/elements/manage' && ( $erasure['rows'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'rows' ] && $erasure['reason'] === null && $erasure['rows_before'] === null && $erasure['before'] === null && $erasure['shared'] === null && $erasure['answer'] === null );

$wipe = \Nino\Modules\Crm\Wipe::entered( $a )['dates'] ?? [];
check( 'the step of the test phase: the package named, a count of its own, the rows of its table in front of the clients, a gather and a leftover its own, the permission of the Elements panel, none before another count',
	( $wipe['package'] ?? '' ) === 'crm-dates' && ( $wipe['count'] ?? null ) === true && ( $wipe['rows'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'rowsAll' ] && ( $wipe['gather'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'gatherAll' ] && ( $wipe['leftover'] ?? null ) === [ \Nino\Modules\CrmDates\Erasure::class, 'leftover' ]
	&& ( $wipe['perm'] ?? null ) === '/_admin/elements/manage' && $wipe['before'] === null && $wipe['rows_before'] === 'clients' );
check( 'the answer of the test phase has the count dates, after those of the core, and the preview of the deletion of a client has it too', array_search( 'dates', \Nino\Modules\Crm\Wipe::keys( $a ), true ) > array_search( 'shares', \Nino\Modules\Crm\Wipe::keys( $a ), true ) && in_array( 'dates', \Nino\Modules\Crm\Erasure::keys( $a ), true ) === true );

$scripts = array_values( array_filter( \Nino\Modules\Crm\Surface\Scripts::entries( $a ), static fn( array $one ): bool => $one['package'] === 'crm-dates' ) );
check( 'the style sheet and the script of the package are on the list of the page, the style first, each of the package and in its folder, and none was refused', array_column( $scripts, 'name' ) === [ 'crm-dates.css', 'crm-dates.js' ] && \Nino\Modules\Crm\Surface\Scripts::refused( $a ) === []
	&& array_filter( $scripts, static fn( array $one ): bool => str_starts_with( (string) realpath( $one['file'] ), (string) realpath( $one['folder'] ) ) === false ) === [] );
$data = \Nino\Modules\Crm\Register::all( $a, './crm/surface/data' )['crm-dates'] ?? [];
check( 'the data of the page are the package\'s: its key, its own method', ( $data['package'] ?? '' ) === 'crm-dates' && ( $data['source'] ?? null ) === [ \Nino\Modules\CrmDates\Surface::class, 'data' ] );
check( 'the words of the package are on the page, in both languages, with the same keys, none empty, and the page has them', ( static function() use ( $features ): bool {
	$de = (array) require $features. '/CrmDates/Surface/text/de_DE.php';
	$en = (array) require $features. '/CrmDates/Surface/text/en_US.php';

	return array_keys( $de ) === array_keys( $en ) && count( $de ) > 60 && array_filter( array_merge( $de, $en ), static fn( mixed $text ): bool => is_string( $text ) === false || $text === '' ) === [];
} )() === true && \Nino\Modules\Crm\Surface\Texts::word( $a, 'dates.title' ) === 'Termine' );
check( 'the package listens at the overview, at the holds of a client and at the organizer of Events, each its own method', \Nino\Callbacks::registered( $a, '/crm/overview' ) === [ [ \Nino\Modules\CrmDates\Overview::class, 'callbackOverview' ] ]
	&& in_array( [ \Nino\Modules\CrmDates\Erasure::class, 'callbackHolds' ], \Nino\Callbacks::registered( $a, '/crm/client/holds' ), true ) === true
	&& \Nino\Callbacks::registered( $a, '/events/organizer' ) === [ [ \Nino\Modules\CrmDates\Organizer::class, 'callbackOrganizer' ] ] );
check( 'the package enters no element type with the endpoint of the values: the actions of the Elements panel are none for the type of the dates, 404 crm_values_type, and nothing was written', in_array( 'events', \Nino\Modules\Crm\Values::types( $a ), true ) === false
	&& dtAnswer( $where, $s['admin@example.org'], 'elements/list', [ 'type' => 'events' ] ) === '404 crm_values_type' && dtAnswer( $where, $s['admin@example.org'], 'elements/get', [ 'type' => 'events', 'uri' => 'public' ] ) === '404 crm_values_type'
	&& dtAnswer( $where, $s['admin@example.org'], 'elements/save', [ 'type' => 'events', 'uri' => 'x', 'isNew' => true, 'locale' => 'de_DE', 'fields' => [ 'title' => 'x', 'start' => '2026-12-01T10:00' ] ] ) === '404 crm_values_type'
	&& dtAnswer( $where, $s['admin@example.org'], 'elements/delete', [ 'type' => 'events', 'uri' => 'public' ] ) === '404 crm_values_type' && in_array( 'public', dtNames( $where ), true ) === true && in_array( 'x', dtNames( $where ), true ) === false );
check( 'the group of a client is the convention of the core, and only the core says it: Clients::group() and Clients::GROUP, strict - K- and at least four digits, nothing around them - and ofGroup() for the client of a group; a text that only looks like one is none, and the package has no pattern of its own', ( static function() use ( $where, $features ): bool {
	$a = dtRequest( $where );
	$core = \Nino\Modules\Crm\Clients::class;

	foreach( [ 'K-0001' => 'k-0001', 'K-12345' => 'k-12345' ] as $number => $group )
		if( $core::group( $number ) !== $group || preg_match( $core::GROUP, $group ) !== 1 )
			return false;

	foreach( [ ' K-0001 ', "K-0001\n", 'k-0001', 'K 0001', 'K-001', 'K-00a1', "K\u{2010}0001" ] as $text )
		if( $core::group( $text ) !== '' )
			return false;

	return $core::ofGroup( $a, 'k-0001' )['number'] === 'K-0001' && $core::ofGroup( $a, 'k-1' ) === null && $core::ofGroup( $a, 'K-0001' ) === null && $core::ofGroup( $a, 'k-0099' ) === null && defined( \Nino\Modules\CrmDates\Dates::class. '::GROUP' ) === false
		&& str_contains( (string) file_get_contents( $features. '/CrmDates/Dates/Dates.php' ), "'/^k-" ) === false;
} )() === true );

check( 'no arrow function of the package takes the app data with it - the body of an arrow function is a copy of what is around it, not the array of the request - and no closure takes it by value (use ( $appData ) without the &); no class of it keeps a static property or a static variable', ( static function() use ( $features ): bool {

	$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $features. '/CrmDates', \FilesystemIterator::SKIP_DOTS ) );

	foreach( $files as $file ) {

		if( $file->getExtension() !== 'php' || str_contains( $file->getPathname(), '/tests/' ) === true )
			continue;

		$code = '';

		// Comments and strings are left out: what is read is the code
		foreach( \PhpToken::tokenize( (string) file_get_contents( $file->getPathname() ) ) as $token )
			$code .= in_array( $token->id, [ T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING ], true ) === true ? ' ' : $token->text;

		if( preg_match( '/\b(?:public|protected|private)\s+static\s+(?!function\b)(?:[^;()]*?)\$\w+/', $code ) === 1 || preg_match( '/(?:^|[;{}])\s*static\s+\$\w+/m', $code ) === 1 )
			return false;

		if( preg_match_all( '/\buse\s*\(([^)]*)\)/', $code, $uses ) > 0 )
			foreach( $uses[1] as $list )
				foreach( explode( ',', $list ) as $one )
					if( preg_match( '/^\s*\$appData\s*$/', $one ) === 1 )
						return false;

		// The body of every arrow function: from its => to the comma or the closing bracket that ends it
		foreach( [ 'fn' ] as $word )
			for( $at = 0; ( $at = strpos( $code, $word. '(', $at ) ) !== false; $at++ ) {

				if( $at > 0 && preg_match( '/\w/', $code[$at - 1] ) === 1 )
					continue;

				$arrow = strpos( $code, '=>', $at );
				$depth = 0;
				$body = '';

				for( $i = $arrow + 2; $i < strlen( $code ); $i++ ) {

					$char = $code[$i];

					if( in_array( $char, [ '(', '[', '{' ], true ) === true )
						$depth++;
					elseif( in_array( $char, [ ')', ']', '}' ], true ) === true && --$depth < 0 )
						break;
					elseif( in_array( $char, [ ',', ';' ], true ) === true && $depth === 0 )
						break;

					$body .= $char;
				}

				if( str_contains( $body, '$appData' ) === true )
					return false;
			}
	}

	return true;
} )() === true );

echo "\nThe list: the dates of the clients, and no other\n";

$GLOBALS['dtWhere'] = $where;
$GLOBALS['dtSession'] = $s['elements@example.org'];
$list = dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [], [ 'set' => $clock ] );
check( 'the list is the dates of clients that are coming within sixty days, the soonest first - the one that is running, then those of Ada, Bob and Carla by their start - and not the date far away, nor the dates with no group, of a group of Members, of a client that is not there or of a group that is no slug',
	dtIds( $list['upcoming'] ?? null ) === [ 'on-a-now', 'on-a-future', 'on-b-future', 'on-c-future', '20261224' ] );
check( '...a name that is only digits is text as it comes back (not the integer an array key would make of it)', ( $list['upcoming'][4]['id'] ?? null ) === '20261224' );
check( '...the dates that are over are the past, newest first, within thirty days', dtIds( $list['past'] ?? null ) === [ 'on-a-past' ] );
check( 'a date comes with its start and end as the site writes them, a flag for a running date, its group and its client with the title the lists show', ( $list['upcoming'][1] ?? [] ) === [ 'id' => 'on-a-future', 'title' => 'Kickoff', 'place' => 'Hall 1', 'start' => '2026-12-01T10:00', 'end' => '2026-12-01T12:00', 'all_day' => false, 'unreadable' => false, 'running' => false,
	'group' => 'k-0001', 'client_id' => $ids['K-0001'], 'client' => 'K-0001', 'client_title' => 'Ada Example' ] && ( $list['upcoming'][0]['running'] ?? null ) === true );
check( 'the answer says the state (ok), the language the words are written in, the moment of the site, the zone its times are read in and what the account may do', ( $list['state'] ?? '' ) === 'ok' && ( $list['native'] ?? '' ) === 'de_DE' && ( $list['now'] ?? '' ) === '2026-11-25T12:00' && ( $list['zone'] ?? '' ) === 'UTC'
	&& ( $list['can'] ?? [] ) === [ 'insert' => true, 'update' => true, 'delete' => true ] );
check( 'the dates of one client: its own, in order - and a client that is not there is 404, not an empty list', dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'client' => (string) $ids['K-0001'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ) === [ 'on-a-now', 'on-a-future', '20261224', 'on-a-far' ]
	&& array_unique( array_map( static fn( string $client ): string => dtAnswer( $GLOBALS['dtWhere'], $GLOBALS['dtSession'], 'crm-dates/list', [ 'client' => $client ], [ 'set' => dtClock( '2026-11-25T12:00', 'UTC' ) ] ), [ '9999', '01', '', ' 1', '1 ', '0', '1e0', '-1' ] ) ) === [ '404 crm_dates_client_unknown' ] );
check( 'the number of days is the reach: the date far away is in the list for 400 days, and a client with none in a day has none', dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ) === [ 'on-a-now', 'on-a-future', 'on-b-future', 'on-c-future', '20261224', 'on-a-far' ]
	&& dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'client' => (string) $ids['K-0002'], 'days' => 1 ], [ 'set' => $clock ] )['upcoming'] ?? [ 'none' ] ) === [] );
check( 'a filter that is none is 400 crm_dates_format: a client that is no text, days that are text, a fraction, none, too many', array_unique( [ dtAnswer( $where, $s['elements@example.org'], 'crm-dates/list', [ 'client' => 5 ] ), dtAnswer( $where, $s['elements@example.org'], 'crm-dates/list', [ 'days' => '60' ] ),
	dtAnswer( $where, $s['elements@example.org'], 'crm-dates/list', [ 'days' => 6.5 ] ), dtAnswer( $where, $s['elements@example.org'], 'crm-dates/list', [ 'days' => 0 ] ), dtAnswer( $where, $s['elements@example.org'], 'crm-dates/list', [ 'days' => 3661 ] ) ] ) === [ '400 crm_dates_format' ] );
dtMake( $where, 'a.b', [ 'title' => 'Dotted', 'start' => '2026-12-06T10:00', 'group' => 'k-0001' ] );
check( 'a name with a dot is a date of a client that the lists do not name: it is not listed and not read - it is deleted with its client (below)', in_array( 'a.b', dtNames( $where ), true ) === true && in_array( 'a.b', dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true ) === false
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/get', [ 'id' => 'a.b' ] ) === '404 crm_dates_unknown' );

echo "\nWho may ask\n";

check( 'without the permission of the CRM every action is 403, whatever the account holds besides: nothing is read', array_unique( [
	dtAnswer( $where, $s['plain@example.org'], 'crm-dates/list' ), dtAnswer( $where, $s['door@example.org'], 'crm-dates/get', [ 'id' => 'on-a-future' ] ), dtAnswer( $where, $s['plain@example.org'], 'crm-dates/save', [] ), dtAnswer( $where, $s['door@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-future' ] ) ] ) === [ '403 -' ] );
check( 'without a session it is 401, without the token or with a wrong one 403 csrf, and the date is still there', dtAnswer( $where, null, 'crm-dates/list' ) === '401 session' && dtAnswer( $where, null, 'crm-dates/list', [], [ 'token' => '' ] ) === '403 csrf' && dtAnswer( $where, $s['elements@example.org'], 'crm-dates/list', [], [ 'token' => 'x' ] ) === '403 csrf'
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-future' ], [ 'token' => '' ] ) === '403 csrf' && dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-a-future' ], [ 'token' => 'x' ] ) === '403 csrf' && in_array( 'on-a-future', dtNames( $where ), true ) === true );
check( 'an account with the permission of the CRM and not of the Elements panel reads, and may not write: the answer says so (can), and every write is 403 and changes nothing', ( dtBody( $where, $s['crm@example.org'], 'crm-dates/list', [], [ 'set' => $clock ] )['can'] ?? [] ) === [ 'insert' => false, 'update' => false, 'delete' => false ]
	&& dtAnswer( $where, $s['crm@example.org'], 'crm-dates/save', [ 'client' => (string) $ids['K-0001'], 'title' => 'x', 'start' => '2026-12-01T10:00', 'end' => '', 'place' => '' ] ) === '403 -' && dtAnswer( $where, $s['crm@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-future' ] ) === '403 -'
	&& dtAnswer( $where, $s['crm@example.org'], 'crm-dates/get', [ 'id' => 'on-a-future' ] ) === '200 -' && in_array( 'on-a-future', dtNames( $where ), true ) === true );

echo "\nOne date\n";

$get = dtBody( $where, $s['elements@example.org'], 'crm-dates/get', [ 'id' => 'on-a-future' ], [ 'set' => $clock ] );
check( 'a date of a client with its words as plain lines - a paragraph a block, the list items as lines, the markup gone - and its link', ( $get['date']['text'] ?? '' ) === "Bring slides\n\none\n\ntwo" && ( $get['date']['link'] ?? 'x' ) === '' && ( $get['date']['client'] ?? '' ) === 'K-0001' && ( $get['state'] ?? '' ) === 'ok' );
check( 'a name that is another element - of no group, of a group of Members, of a group that is no client\'s, one that is no slug - and a name that is none is 404 crm_dates_unknown, the same for each: no date of another group is read through the actions',
	array_unique( array_map( static fn( string $id ): string => dtAnswer( $where, $s['elements@example.org'], 'crm-dates/get', [ 'id' => $id ] ), [ 'public', 'team', 'ghost', 'typo', 'nothing-here', '../elements', 'a b', '', str_repeat( 'a', 101 ), "x\0y" ] ) ) === [ '404 crm_dates_unknown' ]
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/get', [ 'id' => 5 ] ) === '400 crm_dates_format' && dtAnswer( $where, $s['elements@example.org'], 'crm-dates/get', [] ) === '400 crm_dates_format' );
check( 'a name that is only digits is read as the date it is, and its id is the text', ( dtBody( $where, $s['elements@example.org'], 'crm-dates/get', [ 'id' => '20261224' ] )['date']['id'] ?? null ) === '20261224' );

echo "\nMaking a date\n";

$post = [ 'client' => (string) $ids['K-0002'], 'title' => 'Workshop', 'start' => '2026-12-10T09:30', 'end' => '2026-12-10T11:00', 'place' => 'Studio', 'text' => "First line\nsecond line\n\nNew <b>paragraph</b> & more" ];
$before = dtData( $where );
[ $status, $made ] = dtPost( $where, $s['elements@example.org'], 'crm-dates/save', $post, [ 'set' => $clock ] );
$name = (string) ( $made['date']['id'] ?? '' );
$stored = dtOne( $where, $name );
check( 'a date is made for a client: 200 with the date, a name the site serves it by (lower-case letters, digits and hyphens, the day in it), and the group of its client in the element', $status === 200 && preg_match( '/^on-20261210-[a-z0-9]{6}$/D', $name ) === 1
	&& ( $stored['group'] ?? '' ) === 'k-0002' && ( $made['date']['client'] ?? '' ) === 'K-0002' && ( $made['date']['group'] ?? '' ) === 'k-0002' && ( $made['date']['start'] ?? '' ) === '2026-12-10T09:30' && ( $made['date']['end'] ?? '' ) === '2026-12-10T11:00' );
check( '...its title and place are written in the native language, and the words are paragraphs: a line break stays, a blank line divides, a tag that was typed is text', ( $stored['title'] ?? '' ) === 'Workshop' && ( $stored['place'] ?? '' ) === 'Studio'
	&& ( $stored['text'] ?? '' ) === '<p>First line<br>second line</p><p>New &lt;b&gt;paragraph&lt;/b&gt; &amp; more</p>' );
check( '...the other dates are what they were: the type holds what it held, and this date', dtData( $where, [ $name ] ) === $before && in_array( $name, dtNames( $where ), true ) === true );
check( '...it is in the list of its client and of no other', dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'client' => (string) $ids['K-0002'], 'days' => 100 ], [ 'set' => $clock ] )['upcoming'] ?? null ) === [ 'on-b-future', $name ]
	&& in_array( $name, dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'client' => (string) $ids['K-0001'], 'days' => 100 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true ) === false );
$day = dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'client' => (string) $ids['K-0001'], 'title' => 'A day', 'start' => '2026-12-11T00:00', 'end' => '', 'place' => '' ] );
check( 'a date with no end, no place and no words is a date: the end is empty, the place and the words are empty, and a day with no time is a day (all_day)', ( $day['date']['end'] ?? 'x' ) === '' && ( $day['date']['all_day'] ?? false ) === true && ( $day['date']['place'] ?? 'x' ) === '' && ( $day['date']['text'] ?? 'x' ) === '' );

$fills = dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'client' => (string) $ids['K-0001'], 'title' => 'Fills', 'start' => '2026-12-12T10:00', 'end' => '', 'place' => '', 'text' => 'See [events] and [[/project/company/general/name]] and &#91;x' ] );
check( 'a text that carries what a page would read - a shortcode, a fill, a character reference - is kept as text and comes back as it was typed: it is stored as the panel stores it (the character reference is text too: its ampersand is escaped), and what is drawn from it is the business of the page that draws it, which reads nothing it is given', ( $fills['date']['text'] ?? '' ) === 'See [events] and [[/project/company/general/name]] and &#91;x'
	&& ( dtOne( $where, (string) ( $fills['date']['id'] ?? '' ) )['text'] ?? '' ) === '<p>See [events] and [[/project/company/general/name]] and &amp;#91;x</p>' );

$bad = static fn( array $change ): string => dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', $change + $post, [ 'set' => $clock ] );
$bytes = dtFile( $where );
check( 'what is wrong is refused with a code, before anything is written: no title, a title with a line break, a title of 201 letters, a place with a control character, words of 2001 letters, a start that is none, a day that is none, an end that is none, an end that is not after the start, an end that is the start', [
	$bad( [ 'title' => '   ' ] ), $bad( [ 'title' => "a\nb" ] ), $bad( [ 'title' => str_repeat( 'a', 201 ) ] ), $bad( [ 'place' => "a\x07" ] ), $bad( [ 'text' => str_repeat( 'a', 2001 ) ] ), $bad( [ 'start' => '2026-12-10' ] ), $bad( [ 'start' => '2026-13-10T09:00' ] ),
	$bad( [ 'end' => 'tomorrow' ] ), $bad( [ 'end' => '2026-12-10T09:00' ] ), $bad( [ 'end' => '2026-12-10T09:30' ] ) ] === [ '400 crm_dates_title', '400 crm_dates_title', '400 crm_dates_title', '400 crm_dates_place', '400 crm_dates_text', '400 crm_dates_start', '400 crm_dates_start', '400 crm_dates_end', '400 crm_dates_end', '400 crm_dates_end' ]
	&& dtFile( $where ) === $bytes );
$field = static fn( array $change ): string => ( static function() use ( $where, $s, $post, $clock, $change ): string {
	[ , $body ] = dtPost( $where, $s['elements@example.org'], 'crm-dates/save', $change + $post, [ 'set' => $clock ] );

	return (string) ( $body['field'] ?? '' );
} )();
check( 'a refusal names the field the screen puts it at: the title, the place, the words, the start time (a day and an hour are one field), the end time', [ $field( [ 'title' => '' ] ), $field( [ 'place' => "a\x07" ] ), $field( [ 'text' => str_repeat( 'a', 2001 ) ] ), $field( [ 'start' => 'x' ] ), $field( [ 'end' => 'x' ] ), $field( [ 'client' => '99999' ] ), $field( [ 'client' => (string) $ids['K-0003'] ] ) ]
	=== [ 'title', 'place', 'text', 'start-time', 'end-time', 'client', 'client' ] );
check( 'a title of 200 letters is taken, one with a line or paragraph separator or a mark that turns the text is refused, and what is no text at all is 400 crm_dates_format', $bad( [ 'title' => str_repeat( 'ä', 200 ) ] ) === '200 -' && $bad( [ 'title' => "a\u{2028}b" ] ) === '400 crm_dates_title' && $bad( [ 'title' => "a\u{202E}b" ] ) === '400 crm_dates_title'
	&& $bad( [ 'title' => [ 'a' ] ] ) === '400 crm_dates_format' && $bad( [ 'client' => 5 ] ) === '400 crm_dates_format' && $bad( [ 'start' => null ] ) === '400 crm_dates_format' && $bad( [ 'place' => 5 ] ) === '400 crm_dates_format' && $bad( [ 'text' => [ 'x' ] ] ) === '400 crm_dates_format' && $bad( [ 'id' => [ 'x' ] ] ) === '400 crm_dates_format' );
check( 'the client is one that is there and is on: none, a number that is no id, a client that is not there, one that is switched off, and one in a text that carries more - each refused, nothing written', [ $bad( [ 'client' => '' ] ), $bad( [ 'client' => '0' ] ), $bad( [ 'client' => '99999' ] ), $bad( [ 'client' => (string) $ids['K-0003'] ] ), $bad( [ 'client' => $ids['K-0001'].'; DROP' ] ) ]
	=== [ '400 crm_dates_client_unknown', '400 crm_dates_client_unknown', '400 crm_dates_client_unknown', '400 crm_dates_client_off', '400 crm_dates_client_unknown' ] );
check( 'a post without a client is 400 crm_dates_format', dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'title' => 'x', 'start' => '2026-12-10T09:30', 'end' => '', 'place' => '' ] ) === '400 crm_dates_format' );
check( 'a name that is given for a new date is none that is made: a post with an id that no date has is 404 and nothing is made under it; with the id of an element that is no date of a client it is 404 as well, and the element is as it was', dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'my-own-name' ] + $post ) === '404 crm_dates_unknown' && in_array( 'my-own-name', dtNames( $where ), true ) === false
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'public' ] + $post ) === '404 crm_dates_unknown' && ( dtOne( $where, 'public' )['title'] ?? '' ) === 'Open house' );

echo "\nChanging a date\n";

$bytes = dtFile( $where );
check( 'a date that is no date of a client is not changed through the actions - of no group, of a group of Members, of a group nobody has, of a group that is no slug: 404 for each, and the file is the bytes it was', array_unique( array_map( static fn( string $id ): string => dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => $id ] + $post ), [ 'public', 'team', 'ghost', 'typo' ] ) ) === [ '404 crm_dates_unknown' ] && dtFile( $where ) === $bytes );
check( 'a date of a client switched off is changed - it stays with its client - and is moved only to a client that is on', dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-c-future', 'client' => (string) $ids['K-0003'], 'title' => 'Carla again' ] + $post ) === '200 -' && ( dtOne( $where, 'on-c-future' )['title'] ?? '' ) === 'Carla again'
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-b-future', 'client' => (string) $ids['K-0003'] ] + $post ) === '400 crm_dates_client_off' && ( dtOne( $where, 'on-b-future' )['group'] ?? '' ) === 'k-0002' );
$moved = dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-b-future', 'client' => (string) $ids['K-0001'], 'title' => 'Review', 'start' => '2026-12-03T09:00', 'end' => '', 'place' => '' ], [ 'set' => $clock ] );
check( 'a date is moved to another client that is on: its group is the group of the new client, and it is in the list of that client and no longer in the other\'s', ( $moved['date']['client'] ?? '' ) === 'K-0001' && ( dtOne( $where, 'on-b-future' )['group'] ?? '' ) === 'k-0001'
	&& in_array( 'on-b-future', dtIds( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [ 'client' => (string) $ids['K-0002'] ], [ 'set' => $clock ] )['upcoming'] ?? null ), true ) === false );
dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-b-future', 'client' => (string) $ids['K-0002'], 'title' => 'Review', 'start' => '2026-12-03T09:00', 'end' => '', 'place' => '' ] );
$change = dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-a-future', 'client' => (string) $ids['K-0001'], 'title' => 'Kickoff moved', 'start' => '2026-12-01T14:00', 'end' => '2026-12-01T15:00', 'place' => '' ], [ 'set' => $clock ] );
$edited = dtOne( $where, 'on-a-future' );
check( 'a date is changed: its title, moments and place are the new ones; the words, left out of the post, are as they were - the list in them is still a list', ( $edited['title'] ?? '' ) === 'Kickoff moved' && ( $edited['start'] ?? '' ) === '2026-12-01T14:00' && ( $edited['place'] ?? 'x' ) === ''
	&& ( $edited['text'] ?? '' ) === '<p>Bring <strong>slides</strong></p><ul><li>one</li><li>two</li></ul>' && ( $change['date']['title'] ?? '' ) === 'Kickoff moved' && ( $edited['group'] ?? '' ) === 'k-0001' );
dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-a-future', 'client' => (string) $ids['K-0001'], 'title' => 'Kickoff moved', 'start' => '2026-12-01T14:00', 'end' => '2026-12-01T15:00', 'place' => '', 'text' => '' ] );
check( 'words that are sent are the words: sent empty they are empty', ( dtOne( $where, 'on-a-future' )['text'] ?? 'x' ) === '' );
$a = dtRequest( $where );
$english = \Nino\Elements::insertElement( $a, '/events/two-tongues', [ 'title' => 'Zwei', 'start' => '2026-12-12T10:00', 'group' => 'k-0001', 'place' => 'Ort' ], 'de_DE' );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO crm_dates ( name, client_id ) VALUES ( \'two-tongues\', :client )', [ 'client' => $ids['K-0001'] ] );
\Nino\Elements::updateElement( $a, '/events/two-tongues', [ 'title' => 'Two', 'place' => 'Place' ], 'en_US' );
dtBody( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'two-tongues', 'client' => (string) $ids['K-0001'], 'title' => 'Zwei neu', 'start' => '2026-12-12T10:00', 'end' => '', 'place' => 'Ort neu' ] );
$a = dtRequest( $where );
check( 'a change writes the native language and leaves the other as it was', is_array( $english ) === true && ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'de_DE' )['title'] ?? '' ) === 'Zwei neu' && ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'en_US' )['title'] ?? '' ) === 'Two'
	&& ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'en_US' )['place'] ?? '' ) === 'Place' );
check( 'a save that a listener of the element refuses is 409 crm_dates_vetoed with the sentence, and the date is as it was', ( static function() use ( $where, $s, $ids ): bool {
	$before = dtFile( $where );
	[ $status, $body ] = dtPost( $where, $s['elements@example.org'], 'crm-dates/save', [ 'id' => 'on-a-past', 'client' => (string) $ids['K-0001'], 'title' => 'Refused', 'start' => '2026-11-10T09:00', 'end' => '', 'place' => '' ], [ 'hook' => static function( array &$a ): void {
		\Nino\Callbacks::registerCallback( $a, '/nino/elements/events/update', static function( array &$appData, mixed &$typeData ): mixed {
			return \Nino\Elements::refuse( $appData, 'title', 'Not on a Friday' );
		} );
	} ] );

	return $status === 409 && ( $body['code'] ?? '' ) === 'crm_dates_vetoed' && ( $body['params'] ?? [] ) === [ 'Not on a Friday' ] && dtFile( $where ) === $before;
} )() === true );
check( 'no warning or notice of PHP was raised by what the listeners that refuse and hold back were asked', ninoWarnings() === [] );

echo "\nDeleting a date\n";

$bytes = dtFile( $where );
check( 'a date that is no date of a client is not deleted: 404 for each - of no group, of a group of Members, of a group that is no client\'s, one that is no slug, one that is not there, one that is no name - and the file is the bytes it was', array_unique( array_map( static fn( string $id ): string => dtAnswer( $where, $s['elements@example.org'], 'crm-dates/delete', [ 'id' => $id ] ), [ 'public', 'team', 'ghost', 'typo', 'none', '../events', '' ] ) ) === [ '404 crm_dates_unknown' ]
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/delete', [] ) === '400 crm_dates_format' && dtAnswer( $where, $s['elements@example.org'], 'crm-dates/delete', [ 'id' => [ 'public' ] ] ) === '400 crm_dates_format' && dtFile( $where ) === $bytes );
check( 'a date of a client is deleted: 200, it is gone from the type - in every language - and the others are there; asked again it is 404', dtAnswer( $where, $s['elements@example.org'], 'crm-dates/delete', [ 'id' => 'two-tongues' ] ) === '200 -' && in_array( 'two-tongues', dtNames( $where ), true ) === false
	&& dtAnswer( $where, $s['elements@example.org'], 'crm-dates/delete', [ 'id' => 'two-tongues' ] ) === '404 crm_dates_unknown' && in_array( 'on-a-future', dtNames( $where ), true ) === true && ( dtOne( $where, 'two-tongues' ) ) === null );
check( 'a listener that holds a date back from its deletion is 409 crm_dates_kept, and the date stays', ( static function() use ( $where, $s ): bool {
	[ $status, $body ] = dtPost( $where, $s['elements@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-past' ], [ 'hook' => dtVeto( 1 ) ] );

	return $status === 409 && ( $body['code'] ?? '' ) === 'crm_dates_kept' && in_array( 'on-a-past', dtNames( $where ), true ) === true;
} )() === true );

echo "\nThe rights of the Elements panel\n";

$one = [ 'client' => (string) $ids['K-0001'], 'title' => 'Scoped', 'start' => '2026-12-14T10:00', 'end' => '', 'place' => '' ];
$bytes = dtFile( $where );
check( 'an account that holds the door of the Elements panel and not the CRM is turned away, and one that is described in detail may do what it is described to do and no more: delete only', ( dtBody( $where, $s['delete@example.org'], 'crm-dates/list', [], [ 'set' => $clock ] )['can'] ?? [] ) === [ 'insert' => false, 'update' => false, 'delete' => true ]
	&& dtAnswer( $where, $s['delete@example.org'], 'crm-dates/save', $one ) === '403 crm_dates_denied' && dtAnswer( $where, $s['delete@example.org'], 'crm-dates/save', [ 'id' => 'on-a-past' ] + $one ) === '403 crm_dates_denied' && dtFile( $where ) === $bytes );
check( 'insert only is no right to make a date: the panel asks for the right to change each field of a new one as well - nothing is made, and nothing is deleted', ( dtBody( $where, $s['insert@example.org'], 'crm-dates/list', [], [ 'set' => $clock ] )['can'] ?? [] ) === [ 'insert' => false, 'update' => false, 'delete' => false ]
	&& dtAnswer( $where, $s['insert@example.org'], 'crm-dates/save', $one ) === '403 crm_dates_denied' && dtAnswer( $where, $s['insert@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-past' ] ) === '403 crm_dates_denied' && dtFile( $where ) === $bytes );
check( 'the right to change the title alone is no right to change a date that names the place, the moments and the client: a post is refused whole, and the date is as it was', dtAnswer( $where, $s['title@example.org'], 'crm-dates/save', [ 'id' => 'on-a-past' ] + $one ) === '403 crm_dates_denied' && dtFile( $where ) === $bytes );
check( 'the one that may delete deletes: the date is gone, the others are not', dtAnswer( $where, $s['delete@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-past' ] ) === '200 -' && in_array( 'on-a-past', dtNames( $where ), true ) === false && in_array( 'on-a-far', dtNames( $where ), true ) === true );
check( 'an account with the door of the Elements panel and of the CRM, described in no detail, may do all of it', ( dtBody( $where, $s['elements@example.org'], 'crm-dates/list', [], [ 'set' => $clock ] )['can'] ?? [] ) === [ 'insert' => true, 'update' => true, 'delete' => true ] );
check( 'no warning or notice of PHP was raised by what was asked of the actions', ninoWarnings() === [] );

echo "\nThe times of the site\n";

[ $where2, $s2, $ids2 ] = dtStart( 'times' );
$a = dtRequest( $where2 );
$berlin = \Nino\Features::saveSettings( $a, 'events', [ 'timezone' => 'Europe/Berlin' ] );
check( 'the zone of the dates is the setting of Events: Europe/Berlin is saved, and the list says so', $berlin === [] && ( dtBody( $where2, $s2['admin@example.org'], 'crm-dates/list', [], [ 'set' => dtClock( '2026-03-20T12:00' ) ] )['zone'] ?? '' ) === 'Europe/Berlin' );
$save = static fn( array $change ): array => dtPost( $where2, $s2['admin@example.org'], 'crm-dates/save', $change + [ 'client' => (string) $ids2['K-0001'], 'title' => 'Clock', 'start' => '2026-03-29T01:30', 'end' => '', 'place' => '' ], [ 'set' => dtClock( '2026-03-20T12:00' ) ] );
$gap = $save( [ 'start' => '2026-03-29T02:30' ] );
$gapEnd = $save( [ 'start' => '2026-03-29T03:00', 'end' => '2026-03-29T02:15' ] );
check( 'the hour the clocks skip does not exist: 02:30 on the day they go forward is 400 crm_dates_gap at the start time, nothing is written; an end in it is the same at the end time', $gap[0] === 400 && $gap[1]['code'] === 'crm_dates_gap' && $gap[1]['field'] === 'start-time' && $gapEnd[0] === 400 && $gapEnd[1]['code'] === 'crm_dates_gap' && $gapEnd[1]['field'] === 'end-time'
	&& array_filter( dtNames( $where2 ), static fn( string $n ): bool => str_starts_with( $n, 'on-20260329' ) ) === [] );
check( '...the minute before the gap and the minute after it are times', $save( [ 'start' => '2026-03-29T01:59' ] )[0] === 200 && $save( [ 'start' => '2026-03-29T03:00' ] )[0] === 200 && $save( [ 'start' => '2026-03-29T03:00' ] )[1]['date']['start'] === '2026-03-29T03:00' );
$twice = $save( [ 'start' => '2026-10-25T02:30' ] );
check( 'the hour they pass twice is a time, read as the first of the two as Events reads it: 02:30 on the day they go back is written as it is typed; a date that ends at 02:10 that day does not end after it, and one that ends at 03:10 does', $twice[0] === 200 && $twice[1]['date']['start'] === '2026-10-25T02:30'
	&& $save( [ 'start' => '2026-10-25T02:30', 'end' => '2026-10-25T02:10' ] )[1]['code'] === 'crm_dates_end' && $save( [ 'start' => '2026-10-25T02:30', 'end' => '2026-10-25T03:10' ] )[0] === 200 );
check( 'a day with no time and no end is a day, as Events understands it - a start at midnight and no end; there is no rule of our own: a date from midnight that ends at 23:59 is a date with an end, and so is one that ends later, or starts at a minute past midnight', $save( [ 'start' => '2026-04-01T00:00' ] )[1]['date']['all_day'] === true && $save( [ 'start' => '2026-04-01T00:00', 'end' => '2026-04-03T23:59' ] )[1]['date']['all_day'] === false
	&& $save( [ 'start' => '2026-04-01T00:00', 'end' => '2026-04-03T23:58' ] )[1]['date']['all_day'] === false && $save( [ 'start' => '2026-04-01T09:00' ] )[1]['date']['all_day'] === false && $save( [ 'start' => '2026-04-01T00:01' ] )[1]['date']['all_day'] === false );
dtBody( $where2, $s2['admin@example.org'], 'crm-dates/save', [ 'client' => (string) $ids2['K-0001'], 'title' => 'Change day', 'start' => '2026-03-29T00:00', 'end' => '', 'place' => '' ] );
$status = static function( string $at, string $zone = 'Europe/Berlin' ) use ( $where2, $s2, $ids2 ): array {
	$list = dtBody( $where2, $s2['admin@example.org'], 'crm-dates/list', [ 'client' => (string) $ids2['K-0001'], 'days' => 400 ], [ 'set' => dtClock( $at, $zone ) ] );
	$found = array_values( array_filter( array_merge( $list['upcoming'] ?? [], $list['past'] ?? [] ), static fn( array $one ): bool => $one['title'] === 'Change day' ) );

	return [ 'running' => $found[0]['running'] ?? null, 'upcoming' => in_array( 'Change day', array_column( $list['upcoming'] ?? [], 'title' ), true ), 'past' => in_array( 'Change day', array_column( $list['past'] ?? [], 'title' ), true ), 'now' => $list['now'] ?? '' ];
};
check( 'the day the clocks go forward is a day of 23 hours: upcoming the minute before it, running from its midnight to the next one, over a minute after', $status( '2026-03-28T23:59' ) === [ 'running' => false, 'upcoming' => true, 'past' => false, 'now' => '2026-03-28T23:59' ]
	&& $status( '2026-03-29T00:00' )['running'] === true && $status( '2026-03-29T23:59' )['running'] === true && $status( '2026-03-30T00:01' ) === [ 'running' => false, 'upcoming' => false, 'past' => true, 'now' => '2026-03-30T00:01' ] );
check( 'the moment of the answer is the time of the zone: in Berlin, 12:00 on the day the clocks go forward, an hour later in UTC than the same wall clock the day before - the instants differ by 23 hours', $status( '2026-03-29T12:00' )['now'] === '2026-03-29T12:00' && ( new \DateTimeImmutable( '2026-03-29T12:00', new \DateTimeZone( 'Europe/Berlin' ) ) )->getTimestamp() - ( new \DateTimeImmutable( '2026-03-28T12:00', new \DateTimeZone( 'Europe/Berlin' ) ) )->getTimestamp() === 23 * 3600 );
$a = dtRequest( $where2 );
\Nino\Features::saveSettings( $a, 'events', [ 'timezone' => 'America/New_York' ] );
check( 'the zone is the setting and nothing else: in New York the same date is another instant - it is not running a minute before its midnight there, it is running at its midnight there - and the answer names the zone', $status( '2026-03-29T03:59', 'UTC' )['running'] === false && $status( '2026-03-29T04:00', 'UTC' )['running'] === true && $status( '2026-03-29T04:00', 'UTC' )['now'] === '2026-03-29T00:00'
	&& ( dtBody( $where2, $s2['admin@example.org'], 'crm-dates/list', [], [ 'set' => dtClock( '2026-03-29T04:00', 'UTC' ) ] )['zone'] ?? '' ) === 'America/New_York' );
check( 'no warning or notice of PHP was raised by the times', ninoWarnings() === [] );

echo "\nThe type of the dates missing, broken or off\n";

foreach( [ 'missing', 'garbage', 'no model', 'wrong field', 'bucket', 'element', 'off' ] as $case ) {

	[ $where3, $s3, $ids3 ] = dtStart( 'type-'. str_replace( ' ', '-', $case ) );
	$file = $where3. '/private/elements/events.php';

	$write = static function( array $data ) use ( $file ): void {
		file_put_contents( $file, "<?php\nreturn ". var_export( $data, true ). ";\n" );
	};
	$kept = (array) ( include $file );

	if( $case === 'missing' )
		unlink( $file );
	elseif( $case === 'garbage' )
		file_put_contents( $file, "<?php\nreturn [ 'x' ;\n" );
	elseif( $case === 'no model' )
		$write( [ '*' => [ 'x' => [ 'title' => 'a' ] ] ] );
	elseif( $case === 'wrong field' )
		$write( array_replace_recursive( $kept, [ 'model' => [ 'start' => [ 'type' => 'string' ] ] ] ) );
	elseif( $case === 'bucket' )
		$write( [ '*' => 5 ] + $kept );
	elseif( $case === 'element' )
		$write( array_replace_recursive( $kept, [ '*' => [ 'stray' => 'a text where a date should be' ] ] ) );
	else {
		$a = dtRequest( $where3 );
		\Nino\Filesystem::mutate( $a, '/config.php', static function( array $content ): array {
			unset( $content['/nino/features']['events'] );
			$content['/nino/modules'] = array_values( array_diff( (array) $content['/nino/modules'], [ '\\Nino\\Modules\\Events' ] ) );

			return $content;
		} );
	}

	$expected = $case === 'missing' ? 'missing' : ( $case === 'off' ? 'off' : 'broken' );
	$bytes = dtFile( $where3 );
	$list = dtBody( $where3, $s3['admin@example.org'], 'crm-dates/list', [], [ 'set' => $clock ] );
	check( "[$case] the list says why there are no dates and is no empty list that says none: the state is $expected, the lists are empty, and there is no zone or moment", ( $list['state'] ?? '' ) === $expected && ( $list['upcoming'] ?? [ 'x' ] ) === [] && ( $list['past'] ?? [ 'x' ] ) === [] && ( $list['zone'] ?? 'x' ) === '' && ( $list['now'] ?? 'x' ) === '' );
	check( "[$case] nothing is read, made, changed or deleted: get, save and delete are 409 crm_dates_$expected, and the file is the bytes it was", dtAnswer( $where3, $s3['admin@example.org'], 'crm-dates/get', [ 'id' => 'on-a-future' ] ) === "409 crm_dates_$expected"
		&& dtAnswer( $where3, $s3['admin@example.org'], 'crm-dates/save', [ 'client' => (string) $ids3['K-0001'], 'title' => 'x', 'start' => '2026-12-01T10:00', 'end' => '', 'place' => '' ] ) === "409 crm_dates_$expected"
		&& dtAnswer( $where3, $s3['admin@example.org'], 'crm-dates/delete', [ 'id' => 'on-a-future' ] ) === "409 crm_dates_$expected" && dtFile( $where3 ) === $bytes );
	$a = dtRequest( $where3, $s3['admin@example.org'] );
	$line = array_values( array_filter( \Nino\Modules\Crm\Status::lines( $a ), static fn( array $one ): bool => $one['key'] === 'dates-type' ) );
	$word = $expected === 'broken' ? 'damaged' : 'missing';
	check( "[$case] the line of the status says it: the state is $word, with a sentence and a label, and the status of the core is not whole", count( $line ) === 1 && $line[0]['state'] === $word && $line[0]['detail'] !== '' && $line[0]['detail'] !== 'dates.status-'. $expected && $line[0]['label'] !== '' && \Nino\Modules\Crm\Status::whole( \Nino\Modules\Crm\Status::lines( $a ) ) === false );
	check( "[$case] the overview says the dates cannot be read and not that none is coming", ( dtBody( $where3, $s3['admin@example.org'], 'crm/overview' )['packages']['crm-dates'] ?? [] ) === [ 'state' => $expected, 'next' => null ] );
	$plan = dtBody( $where3, $s3['admin@example.org'], 'crm-clients/delete-preview', [ 'id' => (string) $ids3['K-0001'] ] )['preview'] ?? [];
	check( "[$case] a client is not deleted while its dates cannot be read: the preview names a hold that blocks, with a sentence, and the reason is held; the deletion is 409, the client is there and the type is as it was", ( $plan['deletable'] ?? true ) === false && in_array( 'held', $plan['reasons'] ?? [], true ) === true
		&& array_filter( $plan['features'] ?? [], static fn( array $one ): bool => $one['key'] === 'crm-dates' && $one['blocks'] === true && $one['why'] !== '' && $one['why'] !== 'dates.hold-'. $expected ) !== []
		&& dtAnswer( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'id' => (string) $ids3['K-0001'], 'confirm' => 'K-0001' ] ) === '409 crm_clients_delete_held' && ( dtBody( $where3, $s3['admin@example.org'], 'crm-clients/get', [ 'id' => (string) $ids3['K-0001'] ] )['client']['number'] ?? '' ) === 'K-0001'
		&& dtFile( $where3 ) === $bytes );
	check( "[$case] no warning or notice of PHP was raised by asking", ninoWarnings() === [] );
}

echo "\nA deletion that was begun and the type went\n";

[ $where3, $s3, $ids3 ] = dtStart( 'journal' );
$journal = dtBody( $where3, $s3['admin@example.org'], 'crm-clients/delete-preview', [ 'id' => (string) $ids3['K-0001'] ] )['preview'] ?? [];
[ $status, $body ] = dtPost( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'id' => (string) $ids3['K-0001'], 'confirm' => 'K-0001' ], [ 'hook' => dtVeto( 1 ) ] );
$left = dtData( $where3 );
check( 'a date that cannot be deleted stops the run at the step: 500 crm_clients_delete_step with the key of the step, the client is gone - the database step is through - and the dates are all still there', ( $journal['counts']['dates'] ?? -1 ) === 5 && $status === 500 && ( $body['code'] ?? '' ) === 'crm_clients_delete_step' && ( $body['params'] ?? [] ) === [ 'dates' ]
	&& dtAnswer( $where3, $s3['admin@example.org'], 'crm-clients/get', [ 'id' => (string) $ids3['K-0001'] ] ) === '404 crm_clients_unknown' && count( dtNames( $where3 ) ) === 11 );
$good = dtFile( $where3 );
file_put_contents( $where3. '/private/elements/events.php', "<?php\nreturn [ 'x' ;\n" );
$broken = dtFile( $where3 );
check( 'the next press while the type cannot be read is not done either: the same answer, nothing is deleted, the file is the bytes it was - a step that cannot read does not take "none left" for done', dtAnswer( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] ) === '500 crm_clients_delete_step' && dtFile( $where3 ) === $broken );
file_put_contents( $where3. '/private/elements/events.php', $good );
[ $status, $body ] = dtPost( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
$names = dtNames( $where3 );
check( 'the press after that finishes it: 200, the dates of the client are counted and gone - the digits-only name, the one far ahead, the one that is over, the one that runs - and those of no client and of other clients stay, byte for byte', $status === 200 && ( $body['counts']['dates'] ?? -1 ) === 5
	&& array_diff( [ 'on-a-now', 'on-a-future', '20261224', 'on-a-past', 'on-a-far' ], $names ) === [ 'on-a-now', 'on-a-future', '20261224', 'on-a-past', 'on-a-far' ] && array_diff( [ 'on-b-future', 'on-c-future', 'public', 'team', 'ghost', 'typo' ], $names ) === [] && dtData( $where3, [ 'on-a-now', 'on-a-future', '20261224', 'on-a-past', 'on-a-far' ] ) === dtData( $where3 ) );
check( 'no warning or notice of PHP was raised by the deletion that stopped and went on', ninoWarnings() === [] );

[ $where3, $s3, $ids3 ] = dtStart( 'late' );
$GLOBALS['dtW3'] = $where3;
[ $status, $body ] = dtPost( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'id' => (string) $ids3['K-0001'], 'confirm' => 'K-0001' ], [ 'hook' => static function( array &$a ): void {

	$done = false;

	\Nino\Callbacks::registerCallback( $a, '/nino/elements/committed', static function( array &$appData, mixed &$change ) use ( &$done ): mixed {

		// While the first date of the client is deleted another process makes it again, with the group of the client
		if( $done === false && is_array( $change ) === true && ( $change['operation'] ?? '' ) === 'delete' ) {
			$done = true;
			\Nino\Elements::insertElement( $appData, (string) $change['uri'], [ 'title' => 'Late', 'start' => '2026-12-30T10:00', 'group' => 'k-0001' ], 'de_DE' );
		}

		return $change;
	} );
} ] );
check( 'the step is done only when the dates of the group are read again and none is left: a date that was made while it ran is not forgotten - the step says it is not done, 500 crm_clients_delete_step, and the date is still there', $status === 500 && ( $body['code'] ?? '' ) === 'crm_clients_delete_step' && ( $body['params'] ?? [] ) === [ 'dates' ] && count( array_filter( dtNames( $where3 ), static fn( string $name ): bool => dtOne( $GLOBALS['dtW3'], $name )['title'] === 'Late' ) ) === 1 );
[ $status, $body ] = dtPost( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'number' => 'K-0001', 'confirm' => 'K-0001' ] );
check( '...and the next press takes it too: 200, nothing of the client is left, the dates of the others are', $status === 200 && count( array_filter( dtNames( $where3 ), static fn( string $name ): bool => dtOne( $GLOBALS['dtW3'], $name )['title'] === 'Late' ) ) === 0 && array_diff( [ 'on-b-future', 'on-c-future', 'public', 'team', 'ghost', 'typo' ], dtNames( $where3 ) ) === [] && count( dtNames( $where3 ) ) === 6 );
check( 'no warning or notice of PHP was raised by the date that came in the middle', ninoWarnings() === [] );

[ $where3, $s3, $ids3 ] = dtStart( 'erasure-all' );
dtMake( $where3, 'a.b', [ 'title' => 'Dotted', 'start' => '2026-12-06T10:00', 'group' => 'k-0001' ] );
dtMake( $where3, 'caps', [ 'title' => 'Caps', 'start' => '2026-12-07T10:00', 'group' => ' K-0001 ' ] );
$bob = dtOne( $where3, 'on-b-future' );
$stay = dtData( $where3, [ 'on-a-now', 'on-a-future', '20261224', 'on-a-past', 'on-a-far', 'a.b', 'caps' ] );
$preview = dtBody( $where3, $s3['admin@example.org'], 'crm-clients/delete-preview', [ 'id' => (string) $ids3['K-0001'] ] )['preview'] ?? [];
check( 'the preview counts the dates of the client: every one of its group, whatever its name, however its group is written, far ahead and over - and no other: Bob\'s one is Bob\'s', ( $preview['counts']['dates'] ?? -1 ) === 7 && ( $preview['deletable'] ?? false ) === true
	&& ( dtBody( $where3, $s3['admin@example.org'], 'crm-clients/delete-preview', [ 'id' => (string) $ids3['K-0002'] ] )['preview']['counts']['dates'] ?? -1 ) === 1 && ( dtBody( $where3, $s3['admin@example.org'], 'crm-clients/delete-preview', [ 'id' => (string) $ids3['K-0003'] ] )['preview']['counts']['dates'] ?? -1 ) === 1 );
[ $status, $body ] = dtPost( $where3, $s3['admin@example.org'], 'crm-clients/delete', [ 'id' => (string) $ids3['K-0001'], 'confirm' => 'K-0001' ] );
check( 'a client is deleted with its dates in one press: the count is in the answer, the dates of the group are gone however they are named, the dates of the others are the bytes they were', $status === 200 && ( $body['counts']['dates'] ?? -1 ) === 7 && dtData( $where3 ) === $stay && dtOne( $where3, 'on-b-future' ) === $bob );
check( 'the number is spent: the next client is K-0004 and not the number of a client whose dates would be given to it', ( dtBody( $where3, $s3['admin@example.org'], 'crm-clients/save', [ 'name' => 'Dora New' ] )['client']['number'] ?? '' ) === 'K-0004'
	&& ( dtBody( $where3, $s3['admin@example.org'], 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? [] ) !== [] );
check( 'no warning or notice of PHP was raised by the deletion', ninoWarnings() === [] );

echo "\nThe test phase\n";

[ $where4, $s4, $ids4 ] = dtStart( 'wipe' );

[ $status, $body ] = dtPost( $where4, $s4['admin@example.org'], 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ], [ 'hook' => dtVeto( 1 ) ] );
check( 'a date that cannot be deleted stops the run at the step: 500 crm_wipe_step with the key, the clients are gone - the database step is through - and no date is deleted', $status === 500 && ( $body['code'] ?? '' ) === 'crm_wipe_step' && ( $body['params'] ?? [] ) === [ 'dates' ] && ( dtBody( $where4, $s4['admin@example.org'], 'crm-clients/list' )['clients'] ?? [ 'x' ] ) === [] && count( dtNames( $where4 ) ) === 11 );
$good = dtFile( $where4 );
file_put_contents( $where4. '/private/elements/events.php', "<?php\nreturn 'x';\n" );
$broken = dtFile( $where4 );
check( 'while the type cannot be read the step is not done: the same answer, the file is the bytes it was', dtAnswer( $where4, $s4['admin@example.org'], 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] ) === '500 crm_wipe_step' && dtFile( $where4 ) === $broken );
file_put_contents( $where4. '/private/elements/events.php', $good );
$keepers = [ 'public' => dtOne( $where4, 'public' ), 'team' => dtOne( $where4, 'team' ), 'typo' => dtOne( $where4, 'typo' ) ];
[ $status, $body ] = dtPost( $where4, $s4['admin@example.org'], 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( 'the press after that finishes it: every date that has the group of a client is gone with its element, with a row or without - the clients are gone and their numbers begin again - and the count says how many; the dates of no group, of a group of Members and of a text that is no group stay, byte for byte', $status === 200 && ( $body['dates'] ?? -1 ) === 8
	&& dtNames( $where4 ) === array_keys( $keepers ) && dtOne( $where4, 'public' ) === $keepers['public'] && dtOne( $where4, 'team' ) === $keepers['team'] && dtOne( $where4, 'typo' ) === $keepers['typo'] );
check( 'a run after that finds nothing: the count is 0', ( dtBody( $where4, $s4['admin@example.org'], 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] )['dates'] ?? -1 ) === 0 && count( dtNames( $where4 ) ) === 3 );
check( 'no warning or notice of PHP was raised by the test phase', ninoWarnings() === [] );

echo "\nWhose a date is: the row and the group\n";

// --- A date that has a group and no row is nobody's: the Elements panel makes them, legacy leaves them, "delete data of removed packages" keeps them; and what a number was given to once is not given again
[ $where5, $s5, $ids5 ] = dtStart( 'owners' );
$admin5 = $s5['admin@example.org'];
$day5 = static fn( string $start, string $group, string $title, array $more = [] ): array => [ 'title' => $title, 'start' => $start, 'group' => $group ] + $more;
dtMake( $where5, 'orphan', $day5( '2026-12-08T10:00', 'k-0001', 'Made in the panel' ), false );
dtMake( $where5, 'orphan-two', $day5( '2026-12-09T10:00', 'k-0001', 'Also from the panel' ), false );
dtMake( $where5, 'members', $day5( '2026-12-10T10:00', 'K 2024', 'Members group' ), false );
dtMake( $where5, 'members-long', $day5( '2026-12-11T10:00', 'k-10000', 'A number far ahead' ), false );
$list5 = dtBody( $where5, $admin5, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] );
$own5 = dtBody( $where5, $admin5, 'crm-dates/list', [ 'client' => (string) $ids5['K-0001'], 'days' => 400 ], [ 'set' => $clock ] );
check( 'a date with the group of a client and no row is under no client: it is not in the list, not in the list of the client whose group it carries, and it is not read by the actions - 404', !in_array( 'orphan', dtIds( $list5['upcoming'] ?? null ), true ) && !in_array( 'orphan', dtIds( $own5['upcoming'] ?? null ), true )
	&& dtAnswer( $where5, $admin5, 'crm-dates/get', [ 'id' => 'orphan' ] ) === '404 crm_dates_unknown' && dtAnswer( $where5, $admin5, 'crm-dates/delete', [ 'id' => 'orphan' ] ) === '404 crm_dates_unknown'
	&& dtAnswer( $where5, $admin5, 'crm-dates/save', [ 'id' => 'orphan', 'client' => (string) $ids5['K-0001'], 'title' => 'Mine now', 'start' => '2026-12-08T10:00', 'end' => '', 'place' => '' ] ) === '404 crm_dates_unknown' && dtOne( $where5, 'orphan' )['title'] === 'Made in the panel' );
check( '...the list says what is nobody\'s: the groups that are the groups of clients, with their count and the client that has the group today, and no group a Members page made up - "K 2024" is no number of a client, and neither is a number the core does not write', ( $list5['unassigned'] ?? [] ) === [
	[ 'group' => 'k-0001', 'count' => 2, 'client' => [ 'id' => $ids5['K-0001'], 'number' => 'K-0001', 'title' => 'Ada Example', 'active' => true ] ], [ 'group' => 'k-0099', 'count' => 1, 'client' => null ], [ 'group' => 'k-10000', 'count' => 1, 'client' => null ] ] );
$a = dtRequest( $where5, $admin5 );
$unasLine = array_values( array_filter( \Nino\Modules\Crm\Status::lines( $a ), static fn( array $one ): bool => $one['key'] === 'dates-unassigned' ) );
check( 'the status names them: the line dates-unassigned says leftover with the number of dates and of groups - four dates in three groups - and is a hint, not a failure', count( $unasLine ) === 1 && $unasLine[0]['state'] === 'leftover' && $unasLine[0]['hint'] === true && str_contains( $unasLine[0]['detail'], '4' ) && str_contains( $unasLine[0]['detail'], '3' ) );
check( 'the dates of a client with a row are the client\'s, whatever the group of a Members page says: Members "K 2024" and a group the core would not write are touched by nothing - not by the list, not by the adoption', dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-2024', 'mode' => 'delete', 'confirm' => 'k-2024', 'count' => 1 ] ) === '404 crm_dates_group_unknown'
	&& dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'K 2024', 'mode' => 'delete', 'confirm' => 'K 2024', 'count' => 1 ] ) === '400 crm_dates_confirm' && dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'team', 'mode' => 'delete', 'confirm' => 'team', 'count' => 1 ] ) === '400 crm_dates_confirm'
	&& dtOne( $where5, 'members' )['group'] === 'K 2024' && in_array( 'members', dtNames( $where5 ), true ) === true );

dtMake( $where5, 'carla-orphan', $day5( '2026-12-12T10:00', 'k-0003', 'For a client that is off' ), false );
$before5 = dtFile( $where5 );
$asg = static fn( string $group, string $client, int $count ): array => [ 'group' => $group, 'mode' => 'assign', 'client' => $client, 'count' => $count ];
check( 'the adoption is asked for as the owner says it: to delete, the group typed (capitals or not, nothing around it, no other dash) and the number of dates as the dialog named them - a wrong group: 400 crm_dates_confirm; a capital K-0001 is the group, and a number of dates that is not the number there is 409 crm_dates_changed, for the deletion as for the assignment - and to assign, the client and the number of dates as the dialog named them; a mode that is none, a group that is not a text, no client, a count that is no whole number: 400 and nothing is changed', array_unique( [
	dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => 'k-0002', 'count' => 2 ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => '', 'count' => 2 ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => ' k-0001', 'count' => 2 ] ),
		dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => "k-0001\n", 'count' => 2 ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => "k\u{2010}0001", 'count' => 2 ] ) ] ) === [ '400 crm_dates_confirm' ]
	&& array_unique( [ dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => 'K-0001', 'count' => 3 ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => 'k-0001', 'count' => 1 ] ) ] ) === [ '409 crm_dates_changed' ]
	&& array_unique( [ dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'move', 'confirm' => 'k-0001' ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 5, 'mode' => 'assign', 'client' => 'K-0001', 'count' => 2 ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete' ] ),
		dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'assign', 'count' => 2 ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'assign', 'client' => 'K-0001', 'count' => '2' ] ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'assign', 'client' => 'K-0001' ] ) ] ) === [ '400 crm_dates_format' ]
	&& dtFile( $where5 ) === $before5 && count( dtRows( $where5 ) ) === 7 );
check( 'what the owner was shown is what is done: an assign that names another client or another number of dates than there are is 409 crm_dates_changed, and nothing is changed', array_unique( [ dtAnswer( $where5, $admin5, 'crm-dates/adopt', $asg( 'k-0001', 'K-0002', 2 ) ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', $asg( 'k-0001', 'K-0001', 3 ) ), dtAnswer( $where5, $admin5, 'crm-dates/adopt', $asg( 'k-0001', 'K-0001', 1 ) ) ] ) === [ '409 crm_dates_changed' ]
	&& dtFile( $where5 ) === $before5 && count( dtRows( $where5 ) ) === 7 );
check( 'an account with the CRM and not the Elements panel may adopt nothing: 403, and the rows are as they were', dtAnswer( $where5, $s5['crm@example.org'], 'crm-dates/adopt', $asg( 'k-0001', 'K-0001', 2 ) ) === '403 -' && dtAnswer( $where5, $s5['crm@example.org'], 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'delete', 'confirm' => 'k-0001', 'count' => 1 ] ) === '403 -' && count( dtRows( $where5 ) ) === 7 );
check( 'the adoption asks the scoped rights of the Elements panel, as the other writes do: an account that may delete and not change the group may not assign (403), one that may only insert or only change the title may not delete (403), and the rows and the elements are as they were', dtAnswer( $where5, $s5['delete@example.org'], 'crm-dates/adopt', $asg( 'k-0001', 'K-0001', 2 ) ) === '403 crm_dates_denied'
	&& dtAnswer( $where5, $s5['insert@example.org'], 'crm-dates/adopt', [ 'group' => 'k-0099', 'mode' => 'delete', 'confirm' => 'k-0099', 'count' => 1 ] ) === '403 crm_dates_denied' && dtAnswer( $where5, $s5['title@example.org'], 'crm-dates/adopt', [ 'group' => 'k-0099', 'mode' => 'delete', 'confirm' => 'k-0099', 'count' => 1 ] ) === '403 crm_dates_denied'
	&& dtFile( $where5 ) === $before5 && count( dtRows( $where5 ) ) === 7 );
check( 'a group no client has can be deleted and not assigned: assign is 404 crm_dates_client_unknown, nothing is made a row', dtAnswer( $where5, $admin5, 'crm-dates/adopt', $asg( 'k-0099', 'K-0001', 1 ) ) === '404 crm_dates_client_unknown' && count( dtRows( $where5 ) ) === 7 && dtFile( $where5 ) === $before5 );
check( 'a client that is switched off gets none: assign is 400 crm_dates_client_off, the card says so (active false), and nothing is changed - the dates can still be deleted', dtAnswer( $where5, $admin5, 'crm-dates/adopt', $asg( 'k-0003', 'K-0003', 1 ) ) === '400 crm_dates_client_off' && count( dtRows( $where5 ) ) === 7 && dtFile( $where5 ) === $before5
	&& array_values( array_filter( dtBody( $where5, $admin5, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['unassigned'] ?? [], static fn( array $one ): bool => $one['group'] === 'k-0003' ) )[0]['client']['active'] === false );
[ $status, $body ] = dtPost( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0099', 'mode' => 'delete', 'confirm' => 'K-0099', 'count' => 1 ] );
check( 'a group no client has is deleted on the owner\'s word, typed - in capitals, which is the same word: 200 with the count, the element is gone, the others are the bytes they were', $status === 200 && ( $body['adopted'] ?? -1 ) === 1 && in_array( 'ghost', dtNames( $where5 ), true ) === false && dtData( $where5, [ 'ghost' ] ) === dtData( $where5 ) && in_array( 'orphan', dtNames( $where5 ), true ) === true );
dtAnswer( $where5, $admin5, 'crm-dates/adopt', [ 'group' => 'k-0003', 'mode' => 'delete', 'confirm' => 'k-0003', 'count' => 1 ] );

// --- A row whose element is not found: nothing takes it by itself. "Not found" is never a reason to delete - an older file of the type that is put back, and the right one after it, makes the rows needed again
[ $whereT, $sT, $idsT ] = dtStart( 'tidy' );
$adminT = $sT['admin@example.org'];
dtAnswer( $whereT, $adminT, 'crm-dates/adopt', [ 'group' => 'k-0099', 'mode' => 'delete', 'confirm' => 'k-0099', 'count' => 1 ] );
$aT = dtRequest( $whereT, $adminT );
$lineT = static function() use ( $whereT, $adminT ): array {
	$a = dtRequest( $whereT, $adminT );

	return array_values( array_filter( \Nino\Modules\Crm\Status::lines( $a ), static fn( array $one ): bool => $one['key'] === 'dates-unassigned' ) )[0] ?? [];
};
check( 'with nothing that is nobody\'s and no row without an element the line of the status is ok', ( $lineT() )['state'] === 'ok' );
$aT = dtRequest( $whereT );
\Nino\Modules\Crm\Db::exec( $aT, 'INSERT INTO crm_dates ( name, client_id, created ) VALUES ( \'gone-old\', :c, :t )', [ 'c' => $idsT['K-0001'], 't' => time() - \Nino\Modules\CrmDates\Dates::GRACE - 5 ] );
\Nino\Modules\Crm\Db::exec( $aT, 'INSERT INTO crm_dates ( name, client_id, created ) VALUES ( \'gone-young\', :c, :t )', [ 'c' => $idsT['K-0001'], 't' => time() ] );
$detailT = ( $lineT() )['detail'] ?? '';
$wordT = \Nino\Modules\Crm\Surface\Texts::word( $aT, 'dates.unassigned-rows' );
check( 'a row with no element is named by the status where there is nothing else: leftover, the sentence of the rows with their number - one: the one older than ten minutes, the younger may be being written - and no sentence of dates that are nobody\'s', ( $lineT() )['state'] === 'leftover' && $detailT === sprintf( $wordT, 1 ) );
$listT = dtBody( $whereT, $adminT, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] );
check( '...the list says how many there are (1), and the list of a client is no place for it; the list of the client does not show the row', ( $listT['lonely'] ?? -1 ) === 1 && ( dtBody( $whereT, $adminT, 'crm-dates/list', [ 'client' => (string) $idsT['K-0001'], 'days' => 400 ], [ 'set' => $clock ] )['lonely'] ?? -1 ) === 0 );
dtAnswer( $whereT, $adminT, 'crm-dates/save', [ 'client' => (string) $idsT['K-0002'], 'title' => 'Not a sweep', 'start' => '2026-12-13T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock ] );
dtAnswer( $whereT, $adminT, 'crm-dates/delete', [ 'id' => 'on-b-future' ] );
dtMake( $whereT, 'panel-t', $day5( '2026-12-14T10:00', 'k-0001', 'Made in the panel' ), false );
dtAnswer( $whereT, $adminT, 'crm-dates/adopt', [ 'group' => 'k-0001', 'mode' => 'assign', 'client' => 'K-0001', 'count' => 1 ] );
check( 'no write of a date takes it away - not a save, not a delete, not an adoption: the rows of both are there', in_array( 'gone-old', array_column( dtRows( $whereT ), 0 ), true ) === true && in_array( 'gone-young', array_column( dtRows( $whereT ), 0 ), true ) === true );

// The restore: an older file of the type is put back, the rows are older than ten minutes, the next changes go through - and the right file is back: the date is its client's again
$fileNew0 = dtFile( $whereT );
[ $status, $madeT ] = dtPost( $whereT, $adminT, 'crm-dates/save', [ 'client' => (string) $idsT['K-0001'], 'title' => 'Comes back', 'start' => '2026-12-15T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock ] );
$nameT = (string) ( $madeT['date']['id'] ?? '' );
$fileNew = dtFile( $whereT );
file_put_contents( $whereT. '/private/elements/events.php', $fileNew0 );
$aT = dtRequest( $whereT );
\Nino\Modules\Crm\Db::exec( $aT, 'UPDATE crm_dates SET created = 0 WHERE name = :n', [ 'n' => $nameT ] );
dtAnswer( $whereT, $adminT, 'crm-dates/save', [ 'client' => (string) $idsT['K-0002'], 'title' => 'After the restore', 'start' => '2026-12-16T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock ] );
dtAnswer( $whereT, $adminT, 'crm-dates/delete', [ 'id' => 'on-c-future' ] );
$rowsOld = array_column( dtRows( $whereT ), 0 );
$lostT = ( dtBody( $whereT, $adminT, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['lonely'] ?? -1 );
file_put_contents( $whereT. '/private/elements/events.php', $fileNew );
check( 'an older file of the type that is put back does not cost the date its client: the row is there after changes that went through, the date is counted among the rows without an element while the old file is there, and when the right file is back the date is in the list of its client again', $status === 200 && in_array( $nameT, $rowsOld, true ) === true && $lostT === 2
	&& in_array( $nameT, dtIds( dtBody( $whereT, $adminT, 'crm-dates/list', [ 'client' => (string) $idsT['K-0001'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true ) === true );

// Only the owner, with the number he was shown, takes them away
$fileT = dtFile( $whereT );
file_put_contents( $whereT. '/private/elements/events.php', $fileNew0 );
check( 'the action that takes the rows away asks for the number of the rows as the card named them: a number that is not the number is 409 crm_dates_changed, one that is no whole number or less than one 400, an account with the CRM and not the Elements panel 403 - and nothing is changed', array_unique( [ dtAnswer( $whereT, $adminT, 'crm-dates/tidy', [ 'count' => 1 ] ), dtAnswer( $whereT, $adminT, 'crm-dates/tidy', [ 'count' => 3 ] ) ] ) === [ '409 crm_dates_changed' ]
	&& array_unique( [ dtAnswer( $whereT, $adminT, 'crm-dates/tidy', [ 'count' => 0 ] ), dtAnswer( $whereT, $adminT, 'crm-dates/tidy', [ 'count' => '2' ] ), dtAnswer( $whereT, $adminT, 'crm-dates/tidy', [] ) ] ) === [ '400 crm_dates_format' ]
	&& dtAnswer( $whereT, $sT['crm@example.org'], 'crm-dates/tidy', [ 'count' => 2 ] ) === '403 -' && in_array( 'gone-old', array_column( dtRows( $whereT ), 0 ), true ) === true );
check( 'the action that takes the rows away asks for the right to delete the dates, as the surface does for its button: an account the Elements panel is described in detail for, that may insert or change a title and may not delete, is 403 crm_dates_denied - with the right number and with a wrong one - and one that may delete is told the number is wrong (409): the rows are as they were', array_unique( [ dtAnswer( $whereT, $sT['insert@example.org'], 'crm-dates/tidy', [ 'count' => 2 ] ), dtAnswer( $whereT, $sT['insert@example.org'], 'crm-dates/tidy', [ 'count' => 3 ] ), dtAnswer( $whereT, $sT['title@example.org'], 'crm-dates/tidy', [ 'count' => 2 ] ) ] ) === [ '403 crm_dates_denied' ]
	&& dtAnswer( $whereT, $sT['delete@example.org'], 'crm-dates/tidy', [ 'count' => 3 ] ) === '409 crm_dates_changed' && in_array( 'gone-old', array_column( dtRows( $whereT ), 0 ), true ) === true && dtFile( $whereT ) === $fileNew0 );
[ $status, $bodyT ] = dtPost( $whereT, $adminT, 'crm-dates/tidy', [ 'count' => 2 ] );
check( 'with the number that was shown it takes the rows away that are older than ten minutes and have no element - and only those: 200 with the number, the younger row, the rows of the dates that are there and every element are as they were', $status === 200 && ( $bodyT['tidied'] ?? -1 ) === 2 && !in_array( 'gone-old', array_column( dtRows( $whereT ), 0 ), true ) && !in_array( $nameT, array_column( dtRows( $whereT ), 0 ), true )
	&& in_array( 'gone-young', array_column( dtRows( $whereT ), 0 ), true ) === true && in_array( 'on-a-future', array_column( dtRows( $whereT ), 0 ), true ) === true && dtFile( $whereT ) === $fileNew0 );
file_put_contents( $whereT. '/private/elements/events.php', $fileT );
check( 'no warning or notice of PHP was raised by the rows without an element', ninoWarnings() === [] );

// The number is a number: after the test phase no client is left a date could belong to, and the next client has the number of the first
$keepers5 = [ 'members' => dtOne( $where5, 'members' ), 'public' => dtOne( $where5, 'public' ), 'team' => dtOne( $where5, 'team' ), 'typo' => dtOne( $where5, 'typo' ) ];
[ $status, $body ] = dtPost( $where5, $admin5, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( 'the test phase takes every date that has the group of a client, with a row or without - those the panel made, those of a group no client has - and the count says how many: ten; the dates of no group, of a group of Members ("K 2024") and of a text that is no group are the bytes they were', $status === 200 && ( $body['dates'] ?? -1 ) === 10
	&& array_diff( dtNames( $where5 ), array_keys( $keepers5 ) ) === [] && array_diff( array_keys( $keepers5 ), dtNames( $where5 ) ) === [] && dtOne( $where5, 'members' ) === $keepers5['members'] && dtOne( $where5, 'public' ) === $keepers5['public'] && dtOne( $where5, 'typo' ) === $keepers5['typo'] && dtRows( $where5 ) === [] );
$made5 = dtBody( $where5, $admin5, 'crm-clients/save', [ 'name' => 'New Ada' ] );
$new5 = (int) ( $made5['client']['id'] ?? 0 );
$fresh5 = dtBody( $where5, $admin5, 'crm-dates/list', [ 'client' => (string) $new5, 'days' => 400 ], [ 'set' => $clock ] );
$a = dtRequest( $where5, null, $clock );
$seen5 = array_map( static fn( array $record ): string => $record['element']['.uri'] ?? '', \Nino\Modules\Events::dates( $a, \Nino\Modules\Events::elements( $a ), 'upcoming', 'k-0001' ) );
check( 'a client that is made after it has the number K-0001 again and no date: its list is empty, the card of the dates that are nobody\'s is empty, and a member of the group k-0001 sees no old date - Events shows that group only what is public', ( $made5['client']['number'] ?? '' ) === 'K-0001' && ( $fresh5['upcoming'] ?? [ 'x' ] ) === [] && ( $fresh5['unassigned'] ?? [] ) === []
	&& ( dtBody( $where5, $admin5, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['unassigned'] ?? [ 'x' ] ) === [] && array_diff( $seen5, [ '/events/public' ] ) === [] && dtRows( $where5 ) === [] );
check( '...its deletion takes nothing and is not held: the preview says none', ( dtBody( $where5, $admin5, 'crm-clients/delete-preview', [ 'id' => (string) $new5 ] )['preview']['counts']['dates'] ?? -1 ) === 0 );
dtMake( $where5, 'later', $day5( '2026-12-14T10:00', 'k-0001', 'Made in the panel later' ), false );
$keep5 = dtOne( $where5, 'later' );
[ $status, $body ] = dtPost( $where5, $admin5, 'crm-dates/adopt', $asg( 'k-0001', 'K-0001', 1 ) );
$mine5 = dtBody( $where5, $admin5, 'crm-dates/list', [ 'client' => (string) $new5, 'days' => 400 ], [ 'set' => $clock ] );
check( 'only the owner\'s word gives a date to a client: assign is 200 with the count, the client has a row, and the element is the bytes it was', $status === 200 && ( $body['adopted'] ?? -1 ) === 1 && dtIds( $mine5['upcoming'] ?? null ) === [ 'later' ] && dtOne( $where5, 'later' ) === $keep5 && array_column( dtRows( $where5 ), 0 ) === [ 'later' ] );
check( 'no warning or notice of PHP was raised by the dates that are nobody\'s', ninoWarnings() === [] );

// --- The package off: the rows stay, and the core keeps the deletion and the test phase from going on
[ $where6, $s6, $ids6 ] = dtStart( 'package-off' );
$admin6 = $s6['admin@example.org'];
$bytes6 = dtFile( $where6 );
$a = dtRequest( $where6 );
$off6 = \Nino\Features::deactivate( $a, 'crm-dates' );
$preview6 = dtBody( $where6, $admin6, 'crm-clients/delete-preview', [ 'id' => (string) $ids6['K-0001'] ] )['preview'] ?? [];
[ $status, $body ] = dtPost( $where6, $admin6, 'crm-clients/delete', [ 'id' => (string) $ids6['K-0001'], 'confirm' => 'K-0001' ] );
check( 'the package off keeps the client: the preview says it is not deletable, the press is 409 crm_package_off with the key of the package, the client is there, and no date was touched', $off6 === true && ( $preview6['deletable'] ?? true ) === false && $status === 409 && ( $body['code'] ?? '' ) === 'crm_package_off' && ( $body['params'] ?? [] ) === [ 'crm-dates' ]
	&& dtFile( $where6 ) === $bytes6 && count( dtBody( $where6, $admin6, 'crm-clients/list' )['clients'] ?? [] ) === 3 );
[ $status, $body ] = dtPost( $where6, $admin6, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( 'the same for the test phase: 409 crm_package_off, the clients are there and so are the dates', $status === 409 && ( $body['code'] ?? '' ) === 'crm_package_off' && count( dtBody( $where6, $admin6, 'crm-clients/list' )['clients'] ?? [] ) === 3 && dtFile( $where6 ) === $bytes6 );
check( 'no warning or notice of PHP was raised with the package off', ninoWarnings() === [] );

// --- A date whose start nobody can read is its client's all the same
[ $where7, $s7, $ids7 ] = dtStart( 'unreadable' );
$admin7 = $s7['admin@example.org'];
dtMake( $where7, 'bad-start', $day5( '2026-12-20T10:00', 'k-0002', 'Start is gone' ) );
dtMake( $where7, 'bad-start-a', $day5( '2026-12-21T10:00', 'k-0001', 'Start is gone too' ) );
$text7 = dtFile( $where7 );
file_put_contents( $where7. '/private/elements/events.php', str_replace( [ '2026-12-20T10:00', '2026-12-21T10:00' ], [ 'sometime', '' ], $text7 ) );
$list7 = dtBody( $where7, $admin7, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] );
$bob7 = dtBody( $where7, $admin7, 'crm-dates/list', [ 'client' => (string) $ids7['K-0002'], 'days' => 400 ], [ 'set' => $clock ] );
check( 'a date with a start that is no moment is the client\'s: not in the lists of what is coming or over, in the list of the dates nobody can read - for all clients and for its own - with the flag, and in no list of another client', in_array( 'bad-start', dtIds( $list7['unreadable'] ?? null ), true ) === true && in_array( 'bad-start-a', dtIds( $list7['unreadable'] ?? null ), true ) === true
	&& !in_array( 'bad-start', dtIds( $list7['upcoming'] ?? null ), true ) && !in_array( 'bad-start', dtIds( $list7['past'] ?? null ), true ) && dtIds( $bob7['unreadable'] ?? null ) === [ 'bad-start' ] && ( $bob7['unreadable'][0]['unreadable'] ?? false ) === true && ( $bob7['unreadable'][0]['client'] ?? '' ) === 'K-0002' && ( $bob7['unreadable'][0]['start'] ?? 'x' ) === ''
	&& ( $list7['unassigned'] ?? [ 'x' ] ) === [ [ 'group' => 'k-0099', 'count' => 1, 'client' => null ] ] );
check( '...it is read, and it can be changed: a save that gives it a start makes it a date that is coming, and the row is the one it was', ( dtBody( $where7, $admin7, 'crm-dates/get', [ 'id' => 'bad-start' ] )['date']['unreadable'] ?? false ) === true
	&& dtAnswer( $where7, $admin7, 'crm-dates/save', [ 'id' => 'bad-start', 'client' => (string) $ids7['K-0002'], 'title' => 'Start is back', 'start' => '2026-12-20T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock ] ) === '200 -'
	&& in_array( 'bad-start', dtIds( dtBody( $where7, $admin7, 'crm-dates/list', [ 'client' => (string) $ids7['K-0002'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true ) === true && dtOne( $where7, 'bad-start' )['start'] === '2026-12-20T10:00' );
check( '...and it can be deleted: 200, the element and the row are gone', dtAnswer( $where7, $admin7, 'crm-dates/delete', [ 'id' => 'bad-start-a' ] ) === '200 -' && in_array( 'bad-start-a', dtNames( $where7 ), true ) === false && !in_array( 'bad-start-a', array_column( dtRows( $where7 ), 0 ), true ) );
file_put_contents( $where7. '/private/elements/events.php', str_replace( '2026-12-20T10:00', 'sometime', dtFile( $where7 ) ) );
$prev7 = dtBody( $where7, $admin7, 'crm-clients/delete-preview', [ 'id' => (string) $ids7['K-0002'] ] )['preview'] ?? [];
[ $status, $body ] = dtPost( $where7, $admin7, 'crm-clients/delete', [ 'id' => (string) $ids7['K-0002'], 'confirm' => 'K-0002' ] );
check( 'a client with a date whose start is lost is deleted with it: the preview counts it, the deletion is 200 and takes it - the element and the row - and the dates of the others stay', ( $prev7['counts']['dates'] ?? -1 ) === 2 && $status === 200 && in_array( 'bad-start', dtNames( $where7 ), true ) === false && !in_array( 'bad-start', array_column( dtRows( $where7 ), 0 ), true ) && in_array( 'on-a-future', dtNames( $where7 ), true ) === true );
dtMake( $where7, 'bad-test', $day5( '2026-12-22T10:00', 'k-0001', 'Lost for the test phase' ) );
file_put_contents( $where7. '/private/elements/events.php', str_replace( '2026-12-22T10:00', 'later', dtFile( $where7 ) ) );
[ $status, $body ] = dtPost( $where7, $admin7, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( 'the test phase takes it along: 200, nothing is left of the dates that have a row, and the element is gone', $status === 200 && in_array( 'bad-test', dtNames( $where7 ), true ) === false && dtRows( $where7 ) === [] );
check( 'no warning or notice of PHP was raised by the dates whose start is lost', ninoWarnings() === [] );

// --- The test phase meets a type it cannot read: the run ends before a client is deleted, with everything where it was
[ $where8, $s8, $ids8 ] = dtStart( 'wipe-broken' );
$admin8 = $s8['admin@example.org'];
$good8 = dtFile( $where8 );
$rows8 = dtRows( $where8 );
file_put_contents( $where8. '/private/elements/events.php', "<?php\nreturn 'x';\n" );
$broken8 = dtFile( $where8 );
[ $status, $body ] = dtPost( $where8, $admin8, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( 'a type that cannot be read stops the test phase before it deletes a client: 500 crm_wipe_step with the key, the three clients are there, the rows are there, and the file is the bytes it was', $status === 500 && ( $body['code'] ?? '' ) === 'crm_wipe_step' && ( $body['params'] ?? [] ) === [ 'dates' ]
	&& count( dtBody( $where8, $admin8, 'crm-clients/list' )['clients'] ?? [] ) === 3 && dtRows( $where8 ) === $rows8 && dtFile( $where8 ) === $broken8 );
$warned8 = ninoWarnings();
check( 'the core says why in one warning that names the package and the kind of the throwable and no word of the date', count( $warned8 ) === 1 && str_starts_with( (string) $warned8[0], 'Crm: the package dates ' ) === true && str_contains( (string) $warned8[0], '(RuntimeException)' ) === true && str_contains( (string) $warned8[0], 'cannot be read' ) === false );
file_put_contents( $where8. '/private/elements/events.php', $good8 );
[ $status, $body ] = dtPost( $where8, $admin8, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( '...and when the type is read again the run goes through: 200, the clients and the rows are gone', $status === 200 && ( dtBody( $where8, $admin8, 'crm-clients/list' )['clients'] ?? [ 'x' ] ) === [] && dtRows( $where8 ) === [] );

// --- The test phase is done when the dates are read again and none is left: a date that is made again while it runs is not forgotten
[ $where9, $s9, $ids9 ] = dtStart( 'wipe-late' );
$admin9 = $s9['admin@example.org'];
$late9 = static function( array &$a ): void {

	$done = false;

	\Nino\Callbacks::registerCallback( $a, '/nino/elements/committed', static function( array &$appData, mixed &$change ) use ( &$done ): mixed {

		if( $done === false && is_array( $change ) === true && ( $change['operation'] ?? '' ) === 'delete' ) {
			$done = true;
			\Nino\Elements::insertElement( $appData, (string) $change['uri'], [ 'title' => 'Again', 'start' => '2026-12-30T10:00', 'group' => 'k-0001' ], 'de_DE' );
		}

		return $change;
	} );
};
[ $status, $body ] = dtPost( $where9, $admin9, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ], [ 'hook' => $late9 ] );
$GLOBALS['dtW9'] = $where9;
check( 'the test phase is not done while a date of the gathered names is there again: 500 crm_wipe_step with the key, and the date is there', $status === 500 && ( $body['code'] ?? '' ) === 'crm_wipe_step' && ( $body['params'] ?? [] ) === [ 'dates' ] && count( array_filter( dtNames( $where9 ), static fn( string $name ): bool => ( dtOne( $GLOBALS['dtW9'], $name )['title'] ?? '' ) === 'Again' ) ) === 1 );
[ $status, $body ] = dtPost( $where9, $admin9, 'crm/wipe', [ 'confirm' => 'LÖSCHEN' ] );
check( '...and the next press takes it: 200, none of the dates of the clients is left', $status === 200 && count( array_filter( dtNames( $where9 ), static fn( string $name ): bool => ( dtOne( $GLOBALS['dtW9'], $name )['title'] ?? '' ) === 'Again' ) ) === 0 && dtRows( $where9 ) === [] );

// --- The past reaches back thirty days and not further
[ $where10, $s10, $ids10 ] = dtStart( 'past' );
$admin10 = $s10['admin@example.org'];
dtMake( $where10, 'old-29', $day5( '2026-10-27T10:00', 'k-0001', 'Twenty-nine days ago' ) );
dtMake( $where10, 'old-55', $day5( '2026-10-01T10:00', 'k-0001', 'Fifty-five days ago' ) );
$past10 = dtBody( $where10, $admin10, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['past'] ?? [];
check( 'the dates that are over are listed for the thirty days back and not for ever: the one of 29 days ago is, the one of 55 days is not - and the list of one client has the same reach', in_array( 'old-29', dtIds( $past10 ), true ) === true && in_array( 'old-55', dtIds( $past10 ), true ) === false && in_array( 'on-a-past', dtIds( $past10 ), true ) === true
	&& in_array( 'old-55', dtIds( dtBody( $where10, $admin10, 'crm-dates/list', [ 'client' => (string) $ids10['K-0001'], 'days' => 400 ], [ 'set' => $clock ] )['past'] ?? null ), true ) === false );

// --- A date that is moved by hand to another group is no longer the client's; a save that is refused leaves the rows as they were; a client with no dates is not held for them
[ $where13, $s13, $ids13 ] = dtStart( 'moved' );
$admin13 = $s13['admin@example.org'];
dtMake( $where13, 'moved', $day5( '2026-12-16T10:00', 'k-0002', 'Moved in the panel' ) );
dtMake( $where13, 'moved-over', $day5( '2026-12-18T10:00', 'k-0002', 'Moved to the group of another client' ) );
$a = dtRequest( $where13 );
\Nino\Elements::updateElement( $a, '/events/moved', [ 'group' => 'team' ], '*' );
\Nino\Elements::updateElement( $a, '/events/moved-over', [ 'group' => 'k-0001' ], '*' );
$over13 = dtBody( $where13, $admin13, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] );
check( 'a date with a row that was moved by hand to the group of another client is nobody\'s: it is not in the list of either client, and the card of the dates with no client has it under that group, with the client that has the group today - a row and a group that do not fit are no ownership', !in_array( 'moved-over', dtIds( $over13['upcoming'] ?? null ), true )
	&& !in_array( 'moved-over', dtIds( dtBody( $where13, $admin13, 'crm-dates/list', [ 'client' => (string) $ids13['K-0001'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true ) && !in_array( 'moved-over', dtIds( dtBody( $where13, $admin13, 'crm-dates/list', [ 'client' => (string) $ids13['K-0002'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true )
	&& array_values( array_filter( $over13['unassigned'] ?? [], static fn( array $one ): bool => $one['group'] === 'k-0001' ) )[0]['count'] === 1 && dtAnswer( $where13, $admin13, 'crm-dates/get', [ 'id' => 'moved-over' ] ) === '404 crm_dates_unknown' );
$kept13 = dtOne( $where13, 'moved' );
check( 'a date whose group was changed by hand in the panel is no longer its client\'s, whatever its row says: not in the lists of the client, not read, not changed, not deleted - and the client takes only what is its own', !in_array( 'moved', dtIds( dtBody( $where13, $admin13, 'crm-dates/list', [ 'client' => (string) $ids13['K-0002'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), true )
	&& dtAnswer( $where13, $admin13, 'crm-dates/get', [ 'id' => 'moved' ] ) === '404 crm_dates_unknown' && dtAnswer( $where13, $admin13, 'crm-dates/delete', [ 'id' => 'moved' ] ) === '404 crm_dates_unknown'
	&& ( dtBody( $where13, $admin13, 'crm-clients/delete-preview', [ 'id' => (string) $ids13['K-0002'] ] )['preview']['counts']['dates'] ?? -1 ) === 1 );
[ $status, $body ] = dtPost( $where13, $admin13, 'crm-clients/delete', [ 'id' => (string) $ids13['K-0002'], 'confirm' => 'K-0002' ] );
check( '...its deletion takes the date that is its own and leaves the one that was moved, byte for byte', $status === 200 && ( $body['counts']['dates'] ?? -1 ) === 1 && in_array( 'on-b-future', dtNames( $where13 ), true ) === false && dtOne( $where13, 'moved' ) === $kept13 );
$rows13 = dtRows( $where13 );
$file13 = dtFile( $where13 );
[ $status, $body ] = dtPost( $where13, $admin13, 'crm-dates/save', [ 'client' => (string) $ids13['K-0001'], 'title' => 'Refused', 'start' => '2026-12-17T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock, 'hook' => static function( array &$a ): void {
	\Nino\Callbacks::registerCallback( $a, '/nino/elements/events/insert', static function( array &$appData, mixed &$typeData ): mixed {
		return \Nino\Elements::refuse( $appData, 'title', 'Not today' );
	} );
} ] );
check( 'a new date that a listener refuses is no row: 409, and the rows and the file are as they were', $status === 409 && ( $body['code'] ?? '' ) === 'crm_dates_vetoed' && dtRows( $where13 ) === $rows13 && dtFile( $where13 ) === $file13 );
[ $status, $body ] = dtPost( $where13, $admin13, 'crm-dates/save', [ 'id' => 'on-a-future', 'client' => (string) $ids13['K-0001'], 'title' => 'Refused', 'start' => '2026-12-01T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock, 'hook' => static function( array &$a ): void {
	\Nino\Callbacks::registerCallback( $a, '/nino/elements/events/update', static function( array &$appData, mixed &$typeData ): mixed {
		return \Nino\Elements::refuse( $appData, 'title', 'Not today' );
	} );
} ] );
check( '...so is a change that is refused: the date is still its client\'s', $status === 409 && dtRows( $where13 ) === $rows13 && dtFile( $where13 ) === $file13 );
dtAnswer( $where13, $admin13, 'crm-dates/delete', [ 'id' => 'on-a-past' ] );
check( 'a date that is deleted leaves no row', !in_array( 'on-a-past', array_column( dtRows( $where13 ), 0 ), true ) && in_array( 'on-a-past', dtNames( $where13 ), true ) === false && count( dtRows( $where13 ) ) === count( $rows13 ) - 1 );
$madeNew = dtBody( $where13, $admin13, 'crm-clients/save', [ 'name' => 'No Dates' ] );
$none13 = (int) ( $madeNew['client']['id'] ?? 0 );
file_put_contents( $where13. '/private/elements/events.php', "<?php\nreturn [ 'x' ;\n" );
$prev13 = dtBody( $where13, $admin13, 'crm-clients/delete-preview', [ 'id' => (string) $none13 ] )['preview'] ?? [];
$held13 = dtBody( $where13, $admin13, 'crm-clients/delete-preview', [ 'id' => (string) $ids13['K-0001'] ] )['preview'] ?? [];
check( 'a client that has no dates is not held by a type that cannot be read - there is nothing in it to be unsure of - and one that has dates is', ( $prev13['deletable'] ?? false ) === true && array_filter( $prev13['features'] ?? [], static fn( array $one ): bool => $one['key'] === 'crm-dates' ) === []
	&& ( $held13['deletable'] ?? true ) === false && array_filter( $held13['features'] ?? [], static fn( array $one ): bool => $one['key'] === 'crm-dates' && $one['blocks'] === true ) !== [] );
file_put_contents( $where13. '/private/elements/events.php', $file13 );
check( 'no warning or notice of PHP was raised by the date that was moved, the refusals and the client with no dates', ninoWarnings() === [] );

// The step of the test phase takes what the gather says and the group that goes with it, and nothing of any other group
$a = dtRequest( $where13 );
$done13 = [];
$left13 = \Nino\Modules\CrmDates\Erasure::leftover( $a, $done13, [ [ 'team', 'team' ], [ 'members-not-there', 'k-0001' ] ] );
check( 'a gathered name that is under a group that is no group of a client is not deleted by the test phase: the step is done, and the date is the bytes it was', $left13 === true && in_array( 'team', dtNames( $where13 ), true ) === true && ( $done13['dates'] ?? 0 ) === 0 );

// --- Two requests at once: the sweep never takes the row of an element that is being written, and a client that is deleted while a date is made leaves no row and no date under it
[ $whereR, $sR, $idsR ] = dtStart( 'race' );
$adminR = $sR['admin@example.org'];
dtMake( $whereR, 'panel-r', $day5( '2026-12-26T10:00', 'k-0002', 'Made in the panel' ), false );
$readyA = $whereR. '/a.ready';
$goA = $whereR. '/a.go';
$pidA = dtStalled( $whereR, $adminR, [ 'client' => (string) $idsR['K-0001'], 'title' => 'Being written', 'start' => '2026-12-22T10:00', 'end' => '', 'place' => '' ], $readyA, $goA );
$waited = dtWaitFor( $readyA );
$rowsMid = array_column( dtRows( $whereR ), 0 );
$namesMid = dtNames( $whereR );
$young = array_values( array_diff( $rowsMid, $namesMid ) );
// The other request does not write an element (the write of an element is under a lock the first holds): it gives a date that is nobody's to its client, and the sweep is the end of that
[ $statusB ] = dtPost( $whereR, $adminR, 'crm-dates/adopt', [ 'group' => 'k-0002', 'mode' => 'assign', 'client' => 'K-0002', 'count' => 1 ] );
$rowsAfterB = array_column( dtRows( $whereR ), 0 );
touch( $goA );
pcntl_waitpid( $pidA, $stat );
$listR = dtBody( $whereR, $adminR, 'crm-dates/list', [ 'client' => (string) $idsR['K-0001'], 'days' => 400 ], [ 'set' => $clock ] );
$allR = dtBody( $whereR, $adminR, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] );
check( 'a request that has written the row of a date and not yet its element is not swept by another that gives dates to a client: the row is there while the element is not (one young row), the other request is 200 and leaves that row - and when the first goes on the date is its client\'s, in the list of that client, and nobody\'s is none', $waited === true && count( $young ) === 1 && $statusB === 200 && in_array( 'panel-r', array_column( dtRows( $whereR ), 0 ), true ) === true && in_array( $young[0], $rowsAfterB, true ) === true
	&& in_array( $young[0], dtIds( $listR['upcoming'] ?? null ), true ) === true && array_filter( $allR['unassigned'] ?? [], static fn( array $one ): bool => $one['group'] === 'k-0001' ) === [] );

// A client is deleted while a date is made for it
$readyC = $whereR. '/c.ready';
$goC = $whereR. '/c.go';
dtBody( $whereR, $adminR, 'crm-clients/active', [ 'id' => (string) $idsR['K-0003'], 'active' => true ] );
$pidC = dtStalled( $whereR, $adminR, [ 'client' => (string) $idsR['K-0003'], 'title' => 'For a client that goes', 'start' => '2026-12-25T10:00', 'end' => '', 'place' => '' ], $readyC, $goC, $whereR. '/c.answer' );
$stopped = dtWaitFor( $readyC );
$outD = $whereR. '/d.status';
$pidD = dtForked( $whereR, $adminR, 'crm-clients/delete', [ 'id' => (string) $idsR['K-0003'], 'confirm' => 'K-0003' ], $outD );

// The deletion goes as far as it can without the element: the database step is through when the client is no longer in the list, and the step of the dates waits for the lock the first request holds
for( $i = 0; $i < 4000 && in_array( 'K-0003', array_column( dtBody( $whereR, $adminR, 'crm-clients/list' )['clients'] ?? [], 'number' ), true ) === true; $i++ )
	usleep( 5000 );

touch( $goC );
pcntl_waitpid( $pidC, $stat );
pcntl_waitpid( $pidD, $stat );
$statusD = (int) @file_get_contents( $outD );
$again = $statusD === 200 ? 200 : dtPost( $whereR, $adminR, 'crm-clients/delete', [ 'number' => 'K-0003', 'confirm' => 'K-0003' ] )[0];
$clientsR = array_column( dtBody( $whereR, $adminR, 'crm-clients/list' )['clients'] ?? [], 'number' );
$a = dtRequest( $whereR );
$GLOBALS['dtWR'] = $whereR;
$strays = \Nino\Modules\Crm\Db::rows( $a, 'SELECT name FROM crm_dates WHERE client_id NOT IN ( SELECT id FROM clients )' );
$answerC = trim( (string) @file_get_contents( $whereR. '/c.answer' ) );
$GLOBALS['dtWR'] = $whereR;
check( 'the save that was under way answers that the client is not there any more - 409 crm_dates_client_gone, no 500 that says the date could not be written - and the element it had written is not left: no date of that title is in the type, under no client and under none', $answerC === '409 crm_dates_client_gone' && array_filter( dtNames( $whereR ), static fn( string $name ): bool => ( dtOne( $whereR, $name )['title'] ?? '' ) === 'For a client that goes' ) === [] );
check( 'a client that is deleted while a date is made for it: the deletion is 200 or stops at its step and the next press, 200, ends it - the client is gone - never a row without a client; no date is listed under the client that is gone, and the date is nobody\'s or not there', $stopped === true && $strays === [] && $again === 200 && in_array( 'K-0003', $clientsR, true ) === false && $statusD !== 0
	&& array_filter( dtIds( dtBody( $whereR, $adminR, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? null ), static fn( string $id ): bool => ( dtOne( $GLOBALS['dtWR'], $id )['title'] ?? '' ) === 'For a client that goes' ) === [] );
// The client is gone before the element is written, and nothing else deletes the date after it: the save takes back what it wrote
$madeG = dtBody( $whereR, $adminR, 'crm-clients/save', [ 'name' => 'Goes Soon' ] );
$idG = (int) ( $madeG['client']['id'] ?? 0 );
$readyG = $whereR. '/g.ready';
$goG = $whereR. '/g.go';
$pidG = dtStalled( $whereR, $adminR, [ 'client' => (string) $idG, 'title' => 'For a client that is gone', 'start' => '2026-12-27T10:00', 'end' => '', 'place' => '' ], $readyG, $goG, $whereR. '/g.answer' );
dtWaitFor( $readyG );
$aG = dtRequest( $whereR );
\Nino\Modules\Crm\Db::exec( $aG, 'DELETE FROM crm_dates WHERE client_id = :id', [ 'id' => $idG ] );
\Nino\Modules\Crm\Db::exec( $aG, 'DELETE FROM clients WHERE id = :id', [ 'id' => $idG ] );
touch( $goG );
pcntl_waitpid( $pidG, $stat );
check( 'a client that is gone before the element of its date is written: the save answers 409 crm_dates_client_gone and takes back what it wrote - no element of that title is left, no row for the client', trim( (string) @file_get_contents( $whereR. '/g.answer' ) ) === '409 crm_dates_client_gone'
	&& array_filter( dtNames( $whereR ), static fn( string $name ): bool => ( dtOne( $GLOBALS['dtWR'], $name )['title'] ?? '' ) === 'For a client that is gone' ) === [] && \Nino\Modules\Crm\Db::row( $aG, 'SELECT COUNT(*) AS n FROM crm_dates WHERE client_id = :id', [ 'id' => $idG ] )['n'] === 0 );
// --- A date that is changed while its client is deleted: the save meets the deletion at the lock of the type, which another request holds - it has read the date and waits to write -, and what that request does is what the deletion of a client does: the rows, the client and the element go
// Whatever the save does then, no element of the dates is left with no group (it would be public), and none is left with what the save wrote
$meetClient = static function( string $name ) use ( $whereR, $adminR ): array {

	$made = dtBody( $whereR, $adminR, 'crm-clients/save', [ 'name' => $name ] )['client'] ?? [];

	return [ (int) ( $made['id'] ?? 0 ), \Nino\Modules\Crm\Clients::group( (string) ( $made['number'] ?? '' ) ) ];
};
$meetFields = [ 'title', 'start', 'end', 'place', 'text', 'group' ];
$meetSame = static function( ?array $one, ?array $other ) use ( $meetFields ): bool {

	foreach( $meetFields as $field )
		if( $one === null || $other === null || (string) ( $one[$field] ?? '' ) !== (string) ( $other[$field] ?? '' ) )
			return false;

	return true;
};
$meetPublic = static function( string $title ) use ( $whereR, $clock ): bool {

	return str_contains( (string) dtGet( $whereR, '/.events.ics', null, $clock )[1], 'SUMMARY:'. $title );
};
$meetUnassigned = static function( string $group, string $name ) use ( $whereR ): bool {

	$a = dtRequest( $whereR );

	foreach( (array) \Nino\Modules\CrmDates\Dates::unassigned( $a ) as [ $one, $names ] )
		if( $one === $group )
			return in_array( $name, $names, true );

	return false;
};
$meetElement = static function( string $name ) use ( $whereR ): ?array {

	$a = dtRequest( $whereR );

	return \Nino\Modules\CrmDates\Dates::element( $a, $name );
};
$meetPost = static fn( int $client, string $name, string $title ): array => [ 'id' => $name, 'client' => (string) $client, 'title' => $title, 'start' => '2026-12-20T10:00', 'end' => '', 'place' => 'Old place' ];
$meetErase = static function( int $client, string $name, bool $element ): callable {

	return static function( array &$holder ) use ( $client, $name, $element ): void {
		\Nino\Modules\Crm\Db::exec( $holder, 'DELETE FROM crm_dates WHERE client_id = :id', [ 'id' => $client ] );
		\Nino\Modules\Crm\Db::exec( $holder, 'DELETE FROM clients WHERE id = :id', [ 'id' => $client ] );

		if( $element === true )
			\Nino\Elements::deleteElement( $holder, '/events/'. $name, '*' );
	};
};
dtMake( $whereR, 'meet-control', [ 'title' => 'Control that is public', 'start' => '2026-12-20T11:00' ], false );

if( dtProcOpen() === false )
	echo "  SKIP  - the system tells nothing of what another process holds open (no /proc): the saves that meet a deletion are not run\n";

// The element is deleted with the client before the save writes: nothing is written and nothing is made again - not the date, not a group, not a row - and the answer says there is no such date
[ $idM, $groupM ] = $meetClient( 'Changes While Deleted' );
dtMake( $whereR, 'meet-change', [ 'title' => 'Before the deletion', 'start' => '2026-12-20T10:00', 'group' => $groupM, 'place' => 'Old place', 'text' => '<p>Old words</p>' ] );
[ $answerM, $warnM ] = dtSaveMeets( $whereR, $adminR, $meetPost( $idM, 'meet-change', 'Changed while the client goes' ), $meetErase( $idM, 'meet-change', true ), null, $clock );
check( 'a date that is changed while its client is deleted and its element with it: 409 crm_dates_client_gone_removed - the element is not made anew: none of that name, no title of it in the file, no row, not public, in no card', dtProcOpen() === false || ( $answerM === '409 crm_dates_client_gone_removed' && $warnM === 0 && $meetElement( 'meet-change' ) === null
	&& str_contains( dtFile( $whereR ), 'Changed while the client goes' ) === false && str_contains( dtFile( $whereR ), 'Before the deletion' ) === false && $meetPublic( 'Changed while the client goes' ) === false && $meetUnassigned( $groupM, 'meet-change' ) === false
	&& in_array( 'meet-change', array_column( dtRows( $whereR ), 0 ), true ) === false ) );

// The client is deleted and the element is still there when the save writes: the save deletes the date with the client, and says so
[ $idN, $groupN ] = $meetClient( 'Is Deleted Under It' );
dtMake( $whereR, 'meet-kept', [ 'title' => 'Before the stay', 'start' => '2026-12-20T10:00', 'group' => $groupN, 'place' => 'Old place' ] );
[ $answerN, $warnN ] = dtSaveMeets( $whereR, $adminR, $meetPost( $idN, 'meet-kept', 'Written for nobody' ), $meetErase( $idN, 'meet-kept', false ), null, $clock );
check( 'a date that is changed while its client is deleted, the element still there: 409 crm_dates_client_gone_removed - the date went with the client: no element, no title of it in the file, no row, not public, in no card, no warning', dtProcOpen() === false || ( $answerN === '409 crm_dates_client_gone_removed' && $warnN === 0 && $meetElement( 'meet-kept' ) === null
	&& str_contains( dtFile( $whereR ), 'Written for nobody' ) === false && str_contains( dtFile( $whereR ), 'Before the stay' ) === false && $meetPublic( 'Written for nobody' ) === false && $meetUnassigned( $groupN, 'meet-kept' ) === false && in_array( 'meet-kept', array_column( dtRows( $whereR ), 0 ), true ) === false ) );

// Nothing works: the date stands as the save wrote it - with its group - and the answer is a 500 that says it, with a warning
[ $idO, $groupO ] = $meetClient( 'Nothing Works' );
dtMake( $whereR, 'meet-stuck', [ 'title' => 'Before the stop', 'start' => '2026-12-20T10:00', 'group' => $groupO, 'place' => 'Old place' ] );
[ $answerO, $warnO ] = dtSaveMeets( $whereR, $adminR, $meetPost( $idO, 'meet-stuck', 'Written and stuck' ), $meetErase( $idO, 'meet-stuck', false ), static function( array &$a ): void {
	\Nino\Callbacks::registerCallback( $a, '/nino/elements/delete/events', static fn( array &$appData, mixed &$typeData ): mixed => false );
}, $clock );
$afterO = $meetElement( 'meet-stuck' );
check( 'where the date cannot be deleted, the answer is a 500 crm_dates_client_gone_stuck and the save raised a warning - and the element that stands has a group: the group of the client that is gone, so it is nobody\'s and is not public, and the card of the dates with no client has it', dtProcOpen() === false || ( $answerO === '500 crm_dates_client_gone_stuck' && $warnO === 1 && ( $afterO['group'] ?? '' ) === $groupO && $meetPublic( 'Written and stuck' ) === false && $meetUnassigned( $groupO, 'meet-stuck' ) === true ) );

// The date is given to a client whose deletion comes first - its transaction holds the write lock of the database when the save comes to write the row: the key refuses the row, nothing was touched, and the date is as it was, with the client it had
[ $idP, $groupP ] = $meetClient( 'Gets Moved To' );
[ $idQ, $groupQ ] = $meetClient( 'Loses The Date' );
dtMake( $whereR, 'meet-moved', [ 'title' => 'Before the move', 'start' => '2026-12-20T10:00', 'group' => $groupQ, 'place' => 'Old place' ] );
$keptP = $meetElement( 'meet-moved' );
$fileP = dtFile( $whereR );
$rowsP = dtRows( $whereR );
$answerP = dtActionWaits( $whereR, $adminR, 'crm-dates/save', $meetPost( $idP, 'meet-moved', 'Moved while the client goes' ), static function( \PDO $pdo ) use ( $idP ): void {
	$pdo->exec( 'DELETE FROM crm_dates WHERE client_id = '. $idP );
	$pdo->exec( 'DELETE FROM clients WHERE id = '. $idP );
}, $clock );
check( 'a date that is given to a client whose deletion comes first stays as it was: 409 crm_dates_client_gone_changed, the element is the bytes it was, the row names the client it had, and it is that client\'s date', $answerP === 'no /proc' || ( $answerP === '409 crm_dates_client_gone_changed' && dtFile( $whereR ) === $fileP && dtRows( $whereR ) === $rowsP && $meetSame( $meetElement( 'meet-moved' ), $keptP ) === true
	&& ( dtBody( $whereR, $adminR, 'crm-dates/get', [ 'id' => 'meet-moved' ] )['date']['title'] ?? '' ) === 'Before the move' ) );

// The deletion of the client it is given to comes after the row: the date was that client's and goes with it - whether the deletion has taken the element when the save comes to write it, or has not - and the answer says that, not that nothing was saved
foreach( [ 'taken' => true, 'left' => false ] as $how => $element ) {

	[ $idX, $groupX ] = $meetClient( 'Target '. $how );
	[ $idY, $groupY ] = $meetClient( 'Source '. $how );
	dtMake( $whereR, 'meet-'. $how, [ 'title' => 'Before '. $how, 'start' => '2026-12-20T10:00', 'group' => $groupY, 'place' => 'Old place' ] );
	[ $answerX, $warnX ] = dtSaveMeets( $whereR, $adminR, $meetPost( $idX, 'meet-'. $how, 'Moved '. $how ), $meetErase( $idX, 'meet-'. $how, $element ), null, $clock );
	check( 'a date that is given to a client whose deletion comes after the row goes with it, the deletion having '. $how. ' the element: 409 crm_dates_client_gone_removed, no element, no row, no title of it in the file, not public, in no card, no warning', dtProcOpen() === false || ( $answerX === '409 crm_dates_client_gone_removed' && $warnX === 0 && $meetElement( 'meet-'. $how ) === null
		&& array_filter( dtRows( $whereR ), static fn( array $row ): bool => $row[0] === 'meet-'. $how ) === [] && str_contains( dtFile( $whereR ), 'Moved '. $how ) === false && str_contains( dtFile( $whereR ), 'Before '. $how ) === false && $meetPublic( 'Moved '. $how ) === false
		&& $meetUnassigned( $groupX, 'meet-'. $how ) === false && $meetUnassigned( $groupY, 'meet-'. $how ) === false ) );
}

// The date is given to a client that is there while the client it had is deleted, and its element with it: nothing is made again - 404, no element, no row for the client that is there
[ $idT, $groupT ] = $meetClient( 'Loses It While Moved' );
[ $idU, $groupU ] = $meetClient( 'Gets It While Deleted' );
dtMake( $whereR, 'meet-over', [ 'title' => 'Before the move over', 'start' => '2026-12-20T10:00', 'group' => $groupT, 'place' => 'Old place', 'end' => '2026-12-20T12:00' ] );
[ $answerT ] = dtSaveMeets( $whereR, $adminR, [ 'id' => 'meet-over', 'client' => (string) $idU, 'title' => 'Moved over', 'start' => '2026-12-20T10:00', 'end' => '2026-12-20T12:00', 'place' => 'Old place' ], $meetErase( $idT, 'meet-over', true ), null, $clock );
check( 'a date that is given to a client that is there while the client it had is deleted, and its element with it, is not made again: 404 crm_dates_unknown, no element, no row for either client, no title of it in the file', dtProcOpen() === false || ( $answerT === '404 crm_dates_unknown' && $meetElement( 'meet-over' ) === null && array_filter( dtRows( $whereR ), static fn( array $row ): bool => $row[0] === 'meet-over' ) === []
	&& str_contains( dtFile( $whereR ), 'Moved over' ) === false && str_contains( dtFile( $whereR ), 'Before the move over' ) === false ) );

// The same date is saved and deleted at once (the client is there): the save finds it gone and writes nothing - it is not brought back
[ $idZ, $groupZ ] = $meetClient( 'Keeps Living' );
dtMake( $whereR, 'meet-both', [ 'title' => 'Before both', 'start' => '2026-12-20T10:00', 'group' => $groupZ, 'place' => 'Old place' ] );
[ $answerZ ] = dtSaveMeets( $whereR, $adminR, $meetPost( $idZ, 'meet-both', 'Saved while deleted' ), static function( array &$holder ): void {
	\Nino\Elements::deleteElement( $holder, '/events/meet-both', '*' );
	\Nino\Modules\Crm\Db::exec( $holder, 'DELETE FROM crm_dates WHERE name = :name', [ 'name' => 'meet-both' ] );
}, null, $clock );
check( 'a date that is saved while it is deleted is not brought back by the save: 404 crm_dates_unknown, no element, no row, no title of it in the file, the client is still there', dtProcOpen() === false || ( $answerZ === '404 crm_dates_unknown' && $meetElement( 'meet-both' ) === null && array_filter( dtRows( $whereR ), static fn( array $row ): bool => $row[0] === 'meet-both' ) === []
	&& str_contains( dtFile( $whereR ), 'Saved while deleted' ) === false && in_array( $groupZ, array_map( static fn( array $one ): string => \Nino\Modules\Crm\Clients::group( (string) $one['number'] ), dtBody( $whereR, $adminR, 'crm-clients/list' )['clients'] ?? [] ), true ) === true ) );

// What no request can be held at - the element is gone when the save looks after its write - is a call of the step itself: nothing is left, and that is not said to be stuck
$takeBack = new \ReflectionMethod( \Nino\Modules\CrmDates\Api::class, 'takeBack' );
$aB = dtRequest( $whereR );
$goneNew = $takeBack->invokeArgs( null, [ &$aB, 'meet-nothing-there' ] );
check( 'a date whose element is not there when the save looks is not made anew: nothing is left, and that is not said to be stuck', $goneNew === 'removed' && in_array( 'meet-nothing-there', dtNames( $whereR ), true ) === false );
// The step took the lock of the type for the request it was called in: the request is let go, so that the processes after it are not held
unset( $aB );

// A new date is made for a client that is deleted before the write: nothing is left, the answer is the one that says it was not made
[ $idR, $groupR ] = $meetClient( 'Gets A New Date' );
[ $answerR ] = dtSaveMeets( $whereR, $adminR, [ 'client' => (string) $idR, 'title' => 'Made for nobody', 'start' => '2026-12-20T10:00', 'end' => '', 'place' => '' ], $meetErase( $idR, 'none', false ), null, $clock );
check( 'a new date for a client that is deleted while the save waits: 409 crm_dates_client_gone - the date was not made -, no element of that title, no row for the client', dtProcOpen() === false || ( $answerR === '409 crm_dates_client_gone' && $meetPublic( 'Made for nobody' ) === false
	&& array_filter( dtNames( $whereR ), static fn( string $name ): bool => ( dtOne( $whereR, $name )['title'] ?? '' ) === 'Made for nobody' ) === [] && array_filter( dtRows( $whereR ), static fn( array $row ): bool => $row[1] === $idR ) === [] ) );
check( 'no warning or notice of PHP was raised by the saves that met a deletion', ninoWarnings() === [] );

// The dates of a group are given to a client that is deleted while the action runs: it waits for the write lock of the database, the client goes with its rows, and the write is refused by the key - no date is given to anybody, the dates are as they were
[ $idK, $groupK ] = $meetClient( 'Goes During The Giving' );
dtMake( $whereR, 'adopt-one', [ 'title' => 'Nobody\'s one', 'start' => '2026-12-20T10:00', 'group' => $groupK ], false );
dtMake( $whereR, 'adopt-two', [ 'title' => 'Nobody\'s two', 'start' => '2026-12-20T11:00', 'group' => $groupK ], false );
$fileK = dtFile( $whereR );
$rowsK = dtRows( $whereR );
$answerK = dtActionWaits( $whereR, $adminR, 'crm-dates/adopt', [ 'group' => $groupK, 'mode' => 'assign', 'client' => strtoupper( $groupK ), 'count' => 2 ], static function( \PDO $pdo ) use ( $idK ): void {
	$pdo->exec( 'DELETE FROM crm_dates WHERE client_id = '. $idK );
	$pdo->exec( 'DELETE FROM clients WHERE id = '. $idK );
}, $clock );
check( 'dates that are given to a client that is deleted while the action runs: 409 crm_dates_assign_gone - nothing is given to anybody: the rows and the file of the type are as they were, no row names the client that is gone, the dates are nobody\'s', $answerK === 'no /proc' || ( $answerK === '409 crm_dates_assign_gone' && dtRows( $whereR ) === $rowsK && dtFile( $whereR ) === $fileK && $meetUnassigned( $groupK, 'adopt-one' ) === true && $meetUnassigned( $groupK, 'adopt-two' ) === true ) );

// A failure of the database at the giving that is no failure of the key is a failure of the database: the client is there, and nothing is given
[ $idJ, $groupJ ] = $meetClient( 'Stays During The Giving' );
dtMake( $whereR, 'adopt-three', [ 'title' => 'Nobody\'s three', 'start' => '2026-12-20T12:00', 'group' => $groupJ ], false );
$aJ = dtRequest( $whereR );
\Nino\Modules\Crm\Db::exec( $aJ, 'CREATE TRIGGER adopt_refuse BEFORE INSERT ON crm_dates BEGIN SELECT RAISE( ABORT, \'refused\' ); END' );
$rowsJ = dtRows( $whereR );
$countJ = 0;

foreach( (array) \Nino\Modules\CrmDates\Dates::unassigned( $aJ ) as [ $one, $names ] )
	$countJ = $one === $groupJ ? count( $names ) : $countJ;

$answerJ = dtAnswer( $whereR, $adminR, 'crm-dates/adopt', [ 'group' => $groupJ, 'mode' => 'assign', 'client' => strtoupper( $groupJ ), 'count' => $countJ ] );
\Nino\Modules\Crm\Db::exec( $aJ, 'DROP TRIGGER adopt_refuse' );
check( 'a database that refuses the rows of the giving is a failure of the database - 500 crm_database - and not a client that is gone: no row is made and the date is nobody\'s', $answerJ === '500 crm_database' && dtRows( $whereR ) === $rowsJ && $meetUnassigned( $groupJ, 'adopt-three' ) === true );

// A date of a group that a client had and has no more, with no row - what is left where a save came in between the names that the deletion of a client gathered and its database step - is nobody's: not public, in the card of the dates with no client, no date of any client
[ $idS, $groupS ] = $meetClient( 'Goes With A Window' );
dtBody( $whereR, $adminR, 'crm-clients/delete', [ 'id' => (string) $idS, 'confirm' => strtoupper( $groupS ) ] );
dtMake( $whereR, 'window-date', [ 'title' => 'Saved in the window', 'start' => '2026-12-20T12:00', 'group' => $groupS ], false );
$listS = dtBody( $whereR, $adminR, 'crm-dates/list', [ 'days' => 400 ], [ 'set' => $clock ] );
check( 'a date with the group of a client that was deleted and no row is nobody\'s: not public in the calendar file, in no list of a client, not found by the actions, and the card of the dates with no client names its group, with no client that has it', $meetPublic( 'Saved in the window' ) === false && $meetPublic( 'Control that is public' ) === true && $meetUnassigned( $groupS, 'window-date' ) === true
	&& dtAnswer( $whereR, $adminR, 'crm-dates/get', [ 'id' => 'window-date' ] ) === '404 crm_dates_unknown' && array_filter( dtIds( $listS['upcoming'] ?? null ), static fn( string $id ): bool => $id === 'window-date' ) === []
	&& array_column( array_filter( $listS['unassigned'] ?? [], static fn( array $one ): bool => $one['group'] === $groupS ), 'client' ) === [ null ] );

// A failure of the database at the row that is no failure of the key: the client is there, and the save does not say it is not
$aX = dtRequest( $whereR );
\Nino\Modules\Crm\Db::exec( $aX, 'CREATE TRIGGER dates_refuse BEFORE INSERT ON crm_dates BEGIN SELECT RAISE( ABORT, \'refused\' ); END' );
$rowsX = dtRows( $whereR );
$fileX = dtFile( $whereR );
$answerX = dtAnswer( $whereR, $adminR, 'crm-dates/save', [ 'client' => (string) $idsR['K-0001'], 'title' => 'Not for the database', 'start' => '2026-12-22T10:00', 'end' => '', 'place' => '' ], [ 'set' => $clock ] );
\Nino\Modules\Crm\Db::exec( $aX, 'DROP TRIGGER dates_refuse' );
check( 'a database that refuses the row of a date is a failure of the database - 500 crm_database - and not a client that is gone: the client is there, no element is written, the rows and the file are as they were', $answerX === '500 crm_database' && dtRows( $whereR ) === $rowsX && dtFile( $whereR ) === $fileX && in_array( 'K-0001', array_column( dtBody( $whereR, $adminR, 'crm-clients/list' )['clients'] ?? [], 'number' ), true ) === true );
$a = dtRequest( $whereR );
$refused = false;
try {
	\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO crm_dates ( name, client_id ) VALUES ( \'ghost-row\', 9999 )' );
}
catch( \PDOException $e ) {
	$refused = true;
}
$kept = false;
try {
	\Nino\Modules\Crm\Db::exec( $a, 'DELETE FROM clients WHERE id = :id', [ 'id' => $idsR['K-0001'] ] );
}
catch( \PDOException $e ) {
	$kept = true;
}
check( 'the database holds what the code only means: a row for a client that is not there is refused, and a client that has rows cannot be deleted while they are - the key restricts', $refused === true && $kept === true && in_array( 'K-0001', array_column( dtBody( $whereR, $adminR, 'crm-clients/list' )['clients'] ?? [], 'number' ), true ) === true );
check( 'no warning or notice of PHP was raised by two requests at once', ninoWarnings() === [] );

// --- The test phase finishes an old list: an element of it goes only if it is nobody's now - no client that is alive has it by a row that fits - and the list is read where the core reads it, inside the transaction, and writes nothing
[ $whereL, $sL, $idsL ] = dtStart( 'old-list' );
$adminL = $sL['admin@example.org'];
dtMake( $whereL, 'moved-row', $day5( '2026-12-17T10:00', 'k-0002', 'Row of Bob' ) );
$a = dtRequest( $whereL );
\Nino\Elements::updateElement( $a, '/events/moved-row', [ 'group' => 'k-0001' ], '*' );
dtMake( $whereL, 'nobody-l', $day5( '2026-12-18T10:00', 'k-0001', 'Nobody has it' ), false );
$aL = dtRequest( $whereL );
$doneL = [];
$leftL = \Nino\Modules\CrmDates\Erasure::leftover( $aL, $doneL, [ [ 'on-a-future', 'k-0001' ], [ 'moved-row', 'k-0001' ], [ 'nobody-l', 'k-0001' ] ] );
check( 'an element of an old list that a client that is alive has by a row that fits stays - the client of that number was made since, and what it has is not what the list was about - while one that nobody has, and one whose row is another\'s, go: the step is done, and one date went for each of the two', $leftL === true && in_array( 'on-a-future', dtNames( $whereL ), true ) === true && in_array( 'on-a-future', array_column( dtRows( $whereL ), 0 ), true ) === true
	&& in_array( 'moved-row', dtNames( $whereL ), true ) === false && in_array( 'nobody-l', dtNames( $whereL ), true ) === false && ( $doneL['dates'] ?? 0 ) === 2 );
// The requests that called the step took the lock of the type for themselves: they are let go, as a request ends
unset( $aL );

// The step reads the group an element has in the file now: this request has read the element before, and a date that is given to the client that goes is written while the deletion runs
dtMake( $whereL, 'stale-one', $day5( '2026-12-19T10:00', 'k-0002', 'Moved under the deletion' ), false );
$aS = dtRequest( $whereL );
\Nino\Modules\CrmDates\Dates::element( $aS, 'stale-one' );
$bS = dtRequest( $whereL );
\Nino\Elements::updateElement( $bS, '/events/stale-one', [ 'group' => 'k-0001' ], '*' );
$doneS = [];
$leftS = \Nino\Modules\CrmDates\Erasure::leftover( $aS, $doneS, [ [ 'stale-one', 'k-0001' ] ] );
check( 'an element that was given the group of the client that goes after this request read it is deleted by the step - it reads the file, not what it read before: the step is done, one date went, none is left', $leftS === true && ( $doneS['dates'] ?? 0 ) === 1 && in_array( 'stale-one', dtNames( $whereL ), true ) === false );
unset( $aS, $bS );

$aL = dtRequest( $whereL );
$pdoL = \Nino\Modules\Crm\Db::pdo( $aL );
$fileL = dtFile( $whereL );
$rowsL = dtRows( $whereL );
$pdoL->exec( 'BEGIN IMMEDIATE' );
$changesL = (int) $pdoL->query( 'SELECT total_changes()' )->fetchColumn();
$gatheredL = \Nino\Modules\CrmDates\Erasure::gatherAll( $aL );
$sameL = (int) $pdoL->query( 'SELECT total_changes()' )->fetchColumn() === $changesL;
$pdoL->exec( 'ROLLBACK' );
check( 'the list is read inside the open transaction of the database step and writes nothing there: the rows are the rows it had, no change counted by the database, the file of the type is the bytes it was - and it names a pair of element and group for every row and every date that is nobody\'s', is_array( $gatheredL ) === true && $sameL === true && dtRows( $whereL ) === $rowsL && dtFile( $whereL ) === $fileL
	&& in_array( [ 'on-a-future', 'k-0001' ], $gatheredL, true ) === true && in_array( [ 'on-b-future', 'k-0002' ], $gatheredL, true ) === true && in_array( [ 'ghost', 'k-0099' ], $gatheredL, true ) === true && array_filter( $gatheredL, static fn( array $pair ): bool => in_array( $pair[0], [ 'public', 'team', 'typo' ], true ) ) === [] );
check( 'no warning or notice of PHP was raised by the old list', ninoWarnings() === [] );

// --- What a day is: the three places that show a date show it the same way - this list, the record Events reads and the calendar file
[ $where11, $s11, $ids11 ] = dtStart( 'three-ways' );
$admin11 = $s11['admin@example.org'];
$shapes = [ 'day' => [ '2026-12-14T00:00', '' ], 'camp' => [ '2026-12-15T00:00', '2026-12-17T23:59' ], 'timed' => [ '2026-12-18T09:00', '2026-12-18T10:30' ], 'noend' => [ '2026-12-19T09:00', '' ], 'same' => [ '2026-12-20T00:00', '2026-12-20T00:00' ], 'late' => [ '2026-12-21T00:30', '' ] ];
foreach( $shapes as $shape => [ $start, $end ] ) {
	dtMake( $where11, 'mine-'. $shape, $day5( $start, 'k-0001', 'Mine '. $shape, $end === '' ? [] : [ 'end' => $end ] ) );
	dtMake( $where11, 'pub-'. $shape, $day5( $start, '', 'Public '. $shape, $end === '' ? [] : [ 'end' => $end ] ) );
}
$mine11 = [];
foreach( dtBody( $where11, $admin11, 'crm-dates/list', [ 'client' => (string) $ids11['K-0001'], 'days' => 400 ], [ 'set' => $clock ] )['upcoming'] ?? [] as $one )
	$mine11[$one['id']] = $one;
$a = dtRequest( $where11, null, $clock );
$events11 = [];
foreach( \Nino\Modules\Events::elements( $a ) as $element ) {
	$record = \Nino\Modules\Events::record( $a, $element );
	$events11[$record['slug']] = $record['allDay'];
}
$feed11 = (string) dtGet( $where11, '/.events.ics', null, $clock )[1];
$ics11 = [];
foreach( $shapes as $shape => $times )
	$ics11[$shape] = preg_match( '/BEGIN:VEVENT(?:(?!END:VEVENT).)*?SUMMARY:Public '. $shape. '\r?\n(?:(?!END:VEVENT).)*?END:VEVENT/s', $feed11, $block ) === 1 ? str_contains( $block[0], 'DTSTART;VALUE=DATE:' ) : null;
$mine11flags = array_map( static fn( string $shape ): ?bool => isset( $mine11['mine-'. $shape] ) ? $mine11['mine-'. $shape]['all_day'] : null, array_keys( $shapes ) );
check( 'a day is what Events says it is - a start at midnight and no end, an end that is not after the start is none - and the list of the CRM, the record of Events and the calendar file say the same of the same six dates', array_combine( array_keys( $shapes ), $mine11flags ) === [ 'day' => true, 'camp' => false, 'timed' => false, 'noend' => false, 'same' => true, 'late' => false ]
	&& array_map( static fn( string $shape ): bool => $events11['pub-'. $shape] ?? false, array_keys( $shapes ) ) === $mine11flags && array_values( $ics11 ) === $mine11flags );
check( '...and what the list shows of the moments is what the dates have: the start and the end as they are, the end of "same" is no end', ( $mine11['mine-camp']['start'] ?? '' ) === '2026-12-15T00:00' && ( $mine11['mine-camp']['end'] ?? '' ) === '2026-12-17T23:59' && ( $mine11['mine-same']['end'] ?? 'x' ) === '' && ( $mine11['mine-day']['end'] ?? 'x' ) === '' && ( $mine11['mine-timed']['end'] ?? '' ) === '2026-12-18T10:30' );

// --- Read and write in the same language: a session in English on a site whose native language is German
[ $where12, $s12, $ids12 ] = dtStart( 'languages' );
$admin12 = $s12['admin@example.org'];
$a = dtRequest( $where12 );
\Nino\Elements::insertElement( $a, '/events/two-tongues', [ 'title' => 'Zwei Sprachen', 'start' => '2026-12-12T10:00', 'group' => 'k-0001', 'place' => 'Ort', 'text' => '<p>Mit <strong>Text</strong></p>' ], 'de_DE' );
\Nino\Elements::updateElement( $a, '/events/two-tongues', [ 'title' => 'Two tongues', 'place' => 'Place', 'text' => '<p>With words</p>' ], 'en_US' );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO crm_dates ( name, client_id ) VALUES ( \'two-tongues\', :client )', [ 'client' => $ids12['K-0001'] ] );
dtMake( $where12, 'german-only', $day5( '2026-12-13T10:00', 'k-0001', 'Nur deutsch', [ 'place' => 'Hier', 'text' => '<p>Nur Text</p>' ] ) );
$en = [ 'set' => $clock + [ './nino/locales/current' => 'en_US' ] ];
$file12 = dtFile( $where12 );
$same12 = true;
$shown12 = [];

foreach( [ 'two-tongues', 'german-only', 'on-a-future', 'on-a-now' ] as $name ) {
	$got = dtBody( $where12, $admin12, 'crm-dates/get', [ 'id' => $name ], $en )['date'] ?? [];
	$shown12[$name] = $got['title'] ?? '';
	[ $status, $body ] = dtPost( $where12, $admin12, 'crm-dates/save', [ 'id' => $name, 'client' => (string) $ids12['K-0001'], 'title' => $got['title'] ?? '', 'start' => $got['start'] ?? '', 'end' => $got['end'] ?? '', 'place' => $got['place'] ?? '', 'text' => $got['text'] ?? '' ], $en );
	$same12 = $same12 && $status === 200 && dtFile( $where12 ) === $file12;
}
check( 'a session in English on a German site reads the language the save writes: the form shows the German title and place and words, and a save with nothing changed is 200 and leaves the file the bytes it was - none of the English words becomes German, none of the German an English word', $shown12['two-tongues'] === 'Zwei Sprachen' && $shown12['german-only'] === 'Nur deutsch' && $same12 === true );
[ $status, $body ] = dtPost( $where12, $admin12, 'crm-dates/save', [ 'id' => 'two-tongues', 'client' => (string) $ids12['K-0001'], 'title' => 'Zwei Sprachen neu', 'start' => '2026-12-12T10:00', 'end' => '', 'place' => 'Ort', 'text' => 'Mit Text' ], $en );
$a = dtRequest( $where12 );
check( '...a field that is changed is written in the German and only that field: the English words are the ones they were, the place of the German is as it was', $status === 200 && ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'de_DE' )['title'] ?? '' ) === 'Zwei Sprachen neu' && ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'en_US' )['title'] ?? '' ) === 'Two tongues'
	&& ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'en_US' )['place'] ?? '' ) === 'Place' && ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'en_US' )['text'] ?? '' ) === '<p>With words</p>' && ( \Nino\Elements::getElement( $a, '/events/two-tongues', 'de_DE' )['place'] ?? '' ) === 'Ort' );
check( 'no warning or notice of PHP was raised by the languages', ninoWarnings() === [] );

// --- What the owner may do through the package is what the Elements panel lets that account do, and the result is the panel\'s: the same bytes
[ $whereP, $sP, $idsP ] = dtStart( 'panel-a' );
[ $whereQ, $sQ, $idsQ ] = dtStart( 'panel-b' );
$save = [ 'client' => (string) $idsP['K-0001'], 'title' => 'Twin', 'start' => '2026-12-10T09:30', 'end' => '2026-12-10T11:00', 'place' => 'Studio', 'text' => "One\n\nTwo" ];
[ $status, $body ] = dtPost( $whereP, $sP['admin@example.org'], 'crm-dates/save', $save, [ 'set' => $clock ] );
$nameP = (string) ( $body['date']['id'] ?? '' );
$a = dtRequest( $whereQ, $sQ['admin@example.org'] );
$fieldsQ = [ 'title' => 'Twin', 'start' => '2026-12-10T09:30', 'end' => '2026-12-10T11:00', 'place' => 'Studio', 'group' => 'k-0001', 'text' => '<p>One</p><p>Two</p>' ];
$made = \Nino\Elements::insertElement( $a, '/events/'. $nameP, $fieldsQ, 'de_DE' );
check( 'what the package writes is what the panel writes: the element made through the package and the element made with the kernel call the panel makes are the same bytes in the file of the type', $status === 200 && is_array( $made ) === true && dtOne( $whereP, $nameP ) === dtOne( $whereQ, $nameP ) );
check( 'no warning or notice of PHP was raised by the twin', ninoWarnings() === [] );

echo "\nThe overview\n";

[ $where, $s, $ids ] = dtStart( 'overview' );
$over = dtBody( $where, $s['admin@example.org'], 'crm/overview', [], [ 'set' => $clock ] );
check( 'the overview carries the date that comes next of all the clients, under the key of the package: state ok and the date as the list has it - the one that is running', ( $over['packages']['crm-dates'] ?? [] )['state'] === 'ok' && ( $over['packages']['crm-dates']['next']['id'] ?? '' ) === 'on-a-now' && ( $over['packages']['crm-dates']['next']['client'] ?? '' ) === 'K-0001' );
$after = dtBody( $where, $s['admin@example.org'], 'crm/overview', [], [ 'set' => dtClock( '2027-01-01T12:00', 'UTC' ) ] );
check( 'with nothing coming within ten years the part says state ok and no date: none coming is said in the state ok', ( $after['packages']['crm-dates']['state'] ?? '' ) === 'ok' && array_key_exists( 'next', $after['packages']['crm-dates'] ) === true && ( $after['packages']['crm-dates']['next']['id'] ?? '' ) === 'on-a-far' );
$none = dtBody( $where, $s['admin@example.org'], 'crm/overview', [], [ 'set' => dtClock( '2028-01-01T12:00', 'UTC' ) ] );
check( '...and past the last date it is null', ( $none['packages']['crm-dates'] ?? [] ) === [ 'state' => 'ok', 'next' => null ] );

echo "\nWho the calendar file says the dates are with\n";

$a = dtRequest( $where, $s['admin@example.org'] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_email', 'v' => 'post@example.org' ] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_phone', 'v' => '+49 40 123456' ] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_street', 'v' => 'Hafenstraße 1' ] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_zip', 'v' => '20457' ] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_city', 'v' => 'Hamburg' ] );
\Nino\Modules\Crm\Db::close( $a );
dtBody( $where, $s['admin@example.org'], 'crm/settings-save', [ 'seller_name' => 'Studio Nord' ] );
$ask = static function( array $set = [] ) use ( $where ): array {
	$a = dtRequest( $where, null, $set );
	$organizer = [ 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'url' => '', 'account' => null ];
	\Nino\Callbacks::doCallbacks( $a, '/events/organizer', $organizer );

	return $organizer;
};
$db = $where. '/private'. \Nino\Modules\Crm\Db::FILE;
$parked = $db. '.parked';
$bytes = (string) file_get_contents( $db );
$off = [];

foreach( [ 'not saved' => null, 'false' => false, 'the word' => 'yes', 'zero' => 0 ] as $case => $value ) {
	dtFeed( $where, $value );
	rename( $db, $parked );
	$off[$case] = $ask();
	$feedOff = dtGet( $where, '/.events.ics', null, $clock );
	$created = file_exists( $db );
	rename( $parked, $db );
	$off[$case.'-feed'] = [ $feedOff[0], str_contains( (string) $feedOff[1], 'Studio Nord' ), $created ];
}
check( 'the organizer line is a setting of the package and it is off: not saved, false, a word that is no boolean and zero answer nothing - the answer of Events as it came, no name, no contact - and the listener did not open the database (the file was away and none was made), the calendar file has no owner in it', $off['not saved'] === [ 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'url' => '', 'account' => null ]
	&& $off['false'] === $off['not saved'] && $off['the word'] === $off['not saved'] && $off['zero'] === $off['not saved'] && array_filter( [ 'not saved', 'false', 'the word', 'zero' ], static fn( string $case ): bool => $off[$case.'-feed'] !== [ 200, false, false ] ) === [] );
dtFeed( $where, true );
$who = $ask();
check( 'the answer to Events, the setting on: the name the CRM names its owner by, the email and the phone of the settings, the street and the zip with the town as two lines of the address - the address of the website is not given, the account is not touched, and only these four fields are answered', $who['name'] === 'Studio Nord' && $who['url'] === '' && $who['email'] === 'post@example.org' && $who['phone'] === '+49 40 123456' && $who['address'] === "Hafenstraße 1\n20457 Hamburg" && $who['account'] === null && array_keys( $who ) === [ 'name', 'email', 'phone', 'address', 'url', 'account' ] );
$feed = dtGet( $where, '/.events.ics', null, $clock );
check( 'a calendar file of the site says whom the dates are with: the name of the owner, once for the file, with the dates that are public', $feed[0] === 200 && substr_count( (string) $feed[1], 'BEGIN:VEVENT' ) === 1 && str_contains( (string) $feed[1], 'Studio Nord' ) === true && str_contains( (string) $feed[1], 'Open house' ) === true );
$a = dtRequest( $where, $s['admin@example.org'] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_phone', 'v' => "1\r\nBEGIN:VEVENT\0\u{2028}2" ] );
\Nino\Modules\Crm\Db::exec( $a, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => 'seller_name', 'v' => "Evil\r\nBEGIN:VEVENT\0 Name" ] );
\Nino\Modules\Crm\Db::close( $a );
$evil = $ask();
$feed = dtGet( $where, '/.events.ics', null, $clock );
check( 'whatever the settings hold is one line of text: no line break, no null byte, no separator in a value - a phone that carries the line of a calendar file makes no event, and the file has the one event of the public date', $evil['phone'] === '1 BEGIN:VEVENT 2' && preg_match( '/[\x00-\x1F\x{2028}]/u', $evil['name']. $evil['phone'] ) !== 1
	&& preg_match_all( '/^BEGIN:VEVENT\r?$/m', (string) $feed[1] ) === 1 );
$a = dtRequest( $where );
\Nino\Html::addFills( $a, [ '[[/project/company/general/name]]' => "Evil\r\nBEGIN:VEVENT\0\u{2028} Name" ], '*' );
$named = [ 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'url' => '', 'account' => null ];
\Nino\Callbacks::doCallbacks( $a, '/events/organizer', $named );
check( '...so is a name the texts of the site hold: one line, whatever it carries', $named['name'] === 'Evil BEGIN:VEVENT Name' );
$a = dtRequest( $where, $s['admin@example.org'] );
\Nino\Modules\Crm\Db::exec( $a, 'DELETE FROM settings WHERE key IN ( \'seller_email\', \'seller_phone\', \'seller_street\', \'seller_zip\', \'seller_city\', \'seller_name\' )' );
\Nino\Modules\Crm\Db::close( $a );
$bare = $ask();
check( 'where the settings hold none of it the answer has no line for it: the street and the town are no address, the name is the one of the site - and nothing is made up', $bare['email'] === '' && $bare['phone'] === '' && $bare['address'] === '' && $bare['name'] !== '' );
check( 'no warning or notice of PHP was raised by the answers', ninoWarnings() === [] );

echo "\nThe page of the surface\n";

$page = dtGet( $where, '/crm', $s['admin@example.org'] );
$block = preg_match( '~<script type="application/json" id="crm-data">(.*?)</script>~s', (string) $page[1], $found ) === 1 ? (array) json_decode( $found[1], true ) : [];
check( 'the page of the owner carries the data of the package under its key: the permission of the package for the script and nothing else - what the account may do the script reads from the answers (the page makes it a permission of its own from there)', $page[0] === 200 && ( $block['packages']['crm-dates'] ?? [] ) === [ 'perm' => true ] && isset( $block['perms']['crm-dates'] ) === false );
$page = dtGet( $where, '/crm', $s['crm@example.org'] );
$block = preg_match( '~<script type="application/json" id="crm-data">(.*?)</script>~s', (string) $page[1], $found ) === 1 ? (array) json_decode( $found[1], true ) : [];
check( 'an account without the permission of the Elements panel is given the data that say it may not, and the words of the package are in the page in its language', ( $block['packages']['crm-dates'] ?? [] ) === [ 'perm' => false ] && ( $block['texts']['dates.title'] ?? '' ) === 'Termine' && ( $block['texts']['error.crm_dates_title'] ?? '' ) !== '' );
preg_match_all( '~/crm/assets/([0-9a-f]+-crm-dates\.(?:js|css))~', (string) $page[1], $assets );
check( 'the page links the style sheet and the script of the package by the hash of their content, and the files are served: the bytes of the files of the feature', count( $assets[1] ) === 2 && ( static function() use ( $where, $s, $assets, $features ): bool {
	foreach( $assets[1] as $asset ) {
		$served = dtGet( $where, '/crm/assets/'. $asset, $s['crm@example.org'] );

		if( $served[0] !== 200 || str_ends_with( $asset, '.js' ) === false && str_ends_with( $asset, '.css' ) === false )
			return false;
	}

	return true;
} )() === true );
check( 'the page of a visitor has none of it: no data of the package, no script', ( static function() use ( $where ): bool {
	$visitor = dtGet( $where, '/crm', null );

	return str_contains( (string) $visitor[1], 'crm-dates' ) === false;
} )() === true );

echo "\nThe package with the core or Events gone\n";

[ $where5, $s5 ] = dtWorld( 'events-gone' );
$away = $features. '-gone';
rename( $features. '/Events', $away );
$gone = dtRequest( $where5, $s5['admin@example.org'] );
check( 'with the folder of Events gone the package is not on: no action, no line of the status, no step of the deletion or of the test phase, no hook - and no warning', \Nino\Modules\Crm::featureOn( $gone, 'crm-dates' ) === false && array_filter( array_keys( \Nino\Modules\Crm\Api::actions( $gone ) ), static fn( string $name ): bool => str_starts_with( $name, 'crm-dates/' ) ) === []
	&& array_filter( \Nino\Modules\Crm\Status::lines( $gone ), static fn( array $one ): bool => $one['key'] === 'dates-type' ) === [] && array_key_exists( 'dates', \Nino\Modules\Crm\Erasure::entered( $gone ) ) === false && array_key_exists( 'dates', \Nino\Modules\Crm\Wipe::entered( $gone ) ) === false
	&& \Nino\Callbacks::registered( $gone, '/events/organizer' ) === [] && \Nino\Callbacks::registered( $gone, '/crm/overview' ) === [] && dtAnswer( $where5, $s5['admin@example.org'], 'crm-dates/list' ) === '404 action' && ninoWarnings() === [] );
rename( $away, $features. '/Events' );
$back = dtRequest( $where5, $s5['admin@example.org'] );
check( 'with Events back it is on again', \Nino\Modules\Crm::featureOn( $back, 'crm-dates' ) === true );
check( 'no warning or notice of PHP was raised by the package with Events away and back', ninoWarnings() === [] );

echo "\nThe script of the page\n";

$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );
$js = $source. '/tests/crm-dates-js-smoke.js';

if( $node !== '' && is_file( $js ) === true ) {

	$output = [];
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $js ). ' 2>&1', $output, $code );
	echo implode( "\n", array_slice( $output, -3 ) ), "\n";
	check( 'the suite of the script passes', $code === 0 );
}

echo "\n";

foreach( (array) ( $GLOBALS['dtSandboxes'] ?? [] ) as $folder )
	dtRemove( $folder );

dtRemove( $features );
ninoDone( $first );
