<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-smoke.php		The test of the feature Crm, the base of the CRM: the manifest and the activation, the database - the schema that is made on the first use of the connection and
 *										is whole afterwards, the second connection that changes nothing, two processes that connect to an empty disk at once, a file that is not the core's and is never
 *										touched, a disk that cannot be written, the schemas of packages (twice, a higher version, broken statements, statements that end the transaction, tables that are
 *										not the package's), a package that is off and one that is removed and the data it leaves - the number sequences, the register behind every add(), the settings (keys of the
 *										core and of a package, every rule, injection), the hosts (the Host header in every spelling, the paths, the shared paths, the requests turned away), the mail (the
 *										band in both orders of registration, the words, the outbox, the test of the wordmark), the formats, the status, and the actions of the panel with the permission
 *										and the token each of them needs. A package made in the sandbox of the suite, CrmProbe, docks to every place without the core naming it. One pin for each behaviour,
 *										over the structure and never the words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Crm/tests/crm-smoke.php
 *	       NINO_ROOT=../nino php features/Crm/tests/crm-smoke.php
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
function crmCopy( string $from, string $to ): void {

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
function crmRemove( string $folder ): void {

	if( is_dir( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

/**
 *	Give up the right to write a folder: root becomes nobody, and an account that is not root - it cannot become another one - takes the write bits from the folder and from all that is in it,
 *	so that its own files refuse a write the way the files of another account do. The suite gives the modes back when the process has ended
 *
 *	@param		string		$folder
 *
 *	@return 	void
 */
function crmGiveUp( string $folder ): void {

	if( function_exists( 'posix_setuid' ) === true && posix_getuid() === 0 ) {
		posix_setgid( 65534 );
		posix_setuid( 65534 );

		return;
	}

	$paths = [ $folder ];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST ) as $item )
		$paths[] = (string) $item->getPathname();

	foreach( $paths as $path )
		if( is_link( $path ) === false )
			chmod( $path, fileperms( $path ) & 0555 );
}

/*	The features directory of the suite: a folder of its own, made for the run, with a copy of the feature and the package of the suite (tests/fixtures/CrmProbe) - never the
	folder of the catalogue, which the probe does not belong to. The processes the suite starts are told it	*/
if( ( $argv[1] ?? '' ) !== '' && str_ends_with( (string) $argv[1], '-worker' ) === true )
	$features = (string) $argv[2];
else {
	$features = sys_get_temp_dir(). '/nino-crm-features-'. bin2hex( random_bytes( 6 ) );
	mkdir( $features, 0755, true );
	mkdir( $features. '/Crm', 0755, true );

	foreach( [ 'Crm.php', 'feature.php' ] as $file )
		copy( $source. '/'. $file, $features. '/Crm/'. $file );

	// Every folder of the feature but the tests: the parts of the core that came after the base are copied with it
	foreach( array_diff( (array) scandir( $source ), [ '.', '..', 'tests' ] ) as $folder )
		if( is_dir( $source. '/'. $folder ) === true )
			crmCopy( $source. '/'. $folder, $features. '/Crm/'. $folder );

	crmCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
	crmCopy( $source. '/tests/fixtures/CrmProbeTwo', $features. '/CrmProbeTwo' );
}

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-smoke.php names its own features directory: NINO_FEATURES_DIR is defined already\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

/**
 *	A sandbox of the harness, remembered: the suite takes every one of them away at its end
 *
 *	@param		string		$name
 *
 *	@return 	array											The app data of the sandbox
 */
function crmSandbox( string $name ): array {

	$appData = ninoSandbox( $name );
	$GLOBALS['crmSandboxes'][] = ninoSandboxDir( $appData );

	return $appData;
}

/**
 *	A request of a project that exists: the app data of a sandbox that is on the disk already, the way a request builds it - the directory, the languages, the modules the project lists, and what the modules register in their init()
 *
 *	@param		string		$sandbox							The directory of the project
 *	@param		bool			$init									Whether the modules are initialised
 *	@param		array 		$set									What the request has before the modules are initialised: the settings of the package of the suite
 *
 *	@return 	array
 */
function crmRequest( string $sandbox, bool $init = true, array $set = [] ): array {

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
	$appData['/nino/modules']									= (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
	$appData['./nino/test/sandbox']						= $sandbox;

	// The suite makes more manual backups than a project keeps: the kernel keeps thirty here, so that the archive a test made is there when the test reads it
	$appData['/nino/admin/backups-manual']		= 30;
	$appData									= $set + $appData;

	// The shortcodes of the kernel - [imagevalues] draws the logo of the band - are registered as the boot of a request does it
	\Nino\Html::init( $appData );

	if( $init === true )
		\Nino\Modules::callModules( $appData, 'init' );

	return $appData;
}

/**
 *	The files a worker process is started with: this file again, named in its first argument, pointed at the features directory and the sandbox of the main process
 *
 *	@param		string		$mode
 *	@param		string		...$arguments
 *
 *	@return 	array											The process and its pipes
 */
function crmStart( string $mode, string ...$arguments ): array {

	global $features;

	$process = proc_open( array_merge( [ PHP_BINARY, __FILE__, $mode, $features ], $arguments ), [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, null, [ 'NINO_ROOT' => $GLOBALS['root'] ] + getenv() );

	return [ $process, $pipes ];
}

/**
 *	Wait for a worker and read what it printed
 *
 *	@param		array 		$started					crmStart() answered
 *
 *	@return 	array											The exit status and what it printed
 */
function crmFinish( array $started ): array {

	[ $process, $pipes ] = $started;
	$out = (string) stream_get_contents( $pipes[1] );
	$err = (string) stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );

	return [ proc_close( $process ), trim( $out ), $err ];
}

/**
 *	The state of a file for a pin that says it was not touched: its bytes, its time and the files that stand beside it
 *
 *	@param		string		$file
 *
 *	@return 	array
 */
function crmFingerprint( string $file ): array {

	clearstatcache();

	return [ is_file( $file ) === true ? sha1_file( $file ) : null, is_file( $file ) === true ? filemtime( $file ) : null, is_file( $file ) === true ? filesize( $file ) : null, glob( $file. '-*' ) ?: [] ];
}

/**
 *	A mail transport that keeps the mails in the app data of the suite instead of sending them
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function crmTransport( array &$appData ): void {

	\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ): void {

		// A transport that is down takes no mail: it does not say it was sent
		if( ( $GLOBALS['crmTransportDown'] ?? false ) === true ) {
			$mail['sent'] = false;

			return;
		}

		$GLOBALS['crmMails'][] = $mail;
		$mail['sent'] = true;
	} );
}

/**
 *	The tables, columns and indexes of a database as the file says them, for a pin that compares two
 *
 *	@param		\PDO			$pdo
 *
 *	@return 	array
 */
function crmShape( \PDO $pdo ): array {

	$shape = [];

	foreach( $pdo->query( 'SELECT type, name, tbl_name, sql FROM sqlite_master WHERE name NOT LIKE \'sqlite\_%\' ESCAPE \'\\\' ORDER BY type, name' )->fetchAll( \PDO::FETCH_ASSOC ) as $object ) {

		$columns = $object['type'] === 'table' ? $pdo->query( 'PRAGMA table_xinfo( "'. $object['name']. '" )' )->fetchAll( \PDO::FETCH_NUM ) : [];
		$shape[$object['type']. ' '. $object['name']] = [ $object['tbl_name'], $object['sql'], $columns ];
	}

	return $shape;
}

/**
 *	A project with the core and the package of the suite switched on, and a request over it
 *
 *	@param		string		$name
 *
 *	@return 	array											The app data of the sandbox, the directory, and the app data of a request that initialised the modules
 */
function crmWorld( string $name ): array {

	$project = crmSandbox( $name );
	\Nino\Features::activate( $project, 'crm-probe' );
	$where = ninoSandboxDir( $project );
	$request = crmRequest( $where );

	// What the activation of the kernel says of its own files is not what a test of the core asks about
	ninoWarnings();

	return [ $project, $where, $request ];
}

/**
 *	A schema file for the package of the suite, below its folder, which is below the features directory
 *
 *	@param		string		$name					A file name
 *	@param		array 		$statements		The statements of create
 *	@param		array 		$steps				The steps
 *
 *	@return 	string										The name, as './crm-probe/schema' takes it
 */
function crmSchemaFile( string $name, array $statements, array $steps = [], string $folder = 'CrmProbe' ): string {

	file_put_contents( NINO_FEATURES_DIR. '/'. $folder. '/'. $name, "<?php\nreturn ". var_export( [ 'create' => $statements, 'steps' => $steps ], true ). ";\n" );

	return $name;
}

/**
 *	Another process that holds the database locked: it begins an exclusive transaction and keeps it until it is told to let go, or for a time
 *
 *	@param		string		$file
 *	@param		int				$micro				How long it holds the lock, in microseconds; 0 for as long as crmRelease() is not called
 *
 *	@return 	array											The process and its pipes
 */
function crmHold( string $file, int $micro = 0 ): array {

	$release = $micro > 0 ? 'usleep( '. $micro. ' );' : 'fgets( STDIN );';
	$code = '$p = new PDO( "sqlite:'. $file. '" ); $p->exec( "BEGIN EXCLUSIVE" ); echo "held\n"; fflush( STDOUT ); '. $release. ' $p->exec( "COMMIT" ); file_put_contents( "'. $file. '.let-go", (string) microtime( true ) );';
	$proc = proc_open( [ PHP_BINARY, '-r', $code ], [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ] ], $pipes );
	check( 'the second process holds the database it was asked to hold (a pin that needs it would pass for nothing otherwise)', trim( (string) fgets( $pipes[1] ) ) === 'held' );

	return [ $proc, $pipes ];
}

/**
 *	@param		array 		$held					What crmHold() answered
 *
 *	@return 	void
 */
function crmRelease( array $held ): void {

	@fwrite( $held[1][0], "\n" );
	fclose( $held[1][0] );
	fclose( $held[1][1] );
	proc_close( $held[0] );
}

/**
 *	A writer in a process of its own that has written and holds the database: it opens the file, runs what $code says with its connection $db and says it holds; it rolls back when it is released
 *
 *	@param		string		$file
 *	@param		string		$code					php that runs on the connection $db, for example $db->exec( 'BEGIN IMMEDIATE' );
 *
 *	@return 	array											The process and its pipes
 */
function crmWriter( string $file, string $code ): array {

	$program = '$in = fopen( "php://stdin", "r" ); $db = new PDO( "sqlite:". $argv[1], null, null, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] ); $db->exec( "PRAGMA busy_timeout = 0" ); eval( $argv[2] ); echo "held\n"; fflush( STDOUT ); fgets( $in ); $db->exec( "ROLLBACK" );';
	$process = proc_open( [ PHP_BINARY, '-r', $program, $file, $code ], [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ] ], $pipes );
	check( 'the second process holds the database it was asked to hold (a pin that needs it would pass for nothing otherwise)', trim( (string) fgets( $pipes[1] ) ) === 'held' );

	return [ $process, $pipes ];
}

/**
 *	A request of the site that is turned by the hosts: the Host header and the path it came with, answered as the path the request is for after the hosts had their say
 *
 *	@param		array 		&$appData
 *	@param		string		$host					'' for a request with no Host header
 *	@param		string		$uri
 *	@param		string		$method
 *
 *	@return 	array											The request
 */
function crmVisit( array &$appData, string $host, string $uri, string $method = 'GET' ): array {

	\Nino\Modules\Crm\Hosts::forget( $appData );

	return ninoVisit( $appData, $uri, $method, $host === '' ? [] : [ 'HTTP_HOST' => $host ] );
}

/**
 *	The accounts of the suite: one that may do everything, one that may do nothing, one with the rights of other panels and one with the right of this panel alone
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function crmAccounts( array &$appData ): void {

	\Nino\Auth::insertUser( $appData, 'admin@example.org', 'correct horse battery staple', [ '/*' ] );
	\Nino\Auth::insertUser( $appData, 'plain@example.org', 'plain password', [] );
	\Nino\Auth::insertUser( $appData, 'members@example.org', 'members password', [ '/_admin/members/manage', '/_admin/features/manage', '/_admin/text/manage' ] );
	\Nino\Auth::insertUser( $appData, 'crm@example.org', 'crm password', [ '/_admin/crm/manage' ] );
}

/**
 *	Who is at the workbench: nobody, or an account of the suite by its address and password
 *
 *	@param		array 		&$appData
 *	@param		string		$who					'' for nobody
 *
 *	@return 	void
 */
function crmWorkbench( array &$appData, string $who ): void {

	$_SESSION = [];
	unset( $appData['./nino/auth/current'] );

	if( $who !== '' )
		\Nino\Auth::loginUser( $appData, $who, [ 'admin@example.org' => 'correct horse battery staple', 'plain@example.org' => 'plain password', 'members@example.org' => 'members password', 'crm@example.org' => 'crm password' ][$who] );
}

/**
 *	A section that stopped with an exception is one pin that fell, said with the section and the kind of the exception - no path, no word of what was in it - and the run goes on with the next
 *
 *	@param		string		$section
 *	@param		\Throwable	$error
 *
 *	@return 	void
 */
function crmCrashed( string $section, \Throwable $error ): void {

	if( $error instanceof \Nino\Failure )
		throw $error;

	check( 'the section "'. $section. '" ran to its end: it stopped with '. get_class( $error ), false );
}

/**
 *	The archive a backup made, opened: its files by name below the project, its stamp, and a folder of the suite it was unpacked into (taken away with the sandbox of the project)
 *
 *	@param		array 		&$appData			A request of the project that was backed up
 *	@param		string		$id						The id the backup answered
 *	@param		string|null	$key					The key of the backups of the project, where the request has not read it
 *
 *	@return 	array											files (name => bytes), stamp, folder; files is null where the archive could not be opened
 */
function crmArchive( array &$appData, string $id, ?string $key = null ): array {

	static $count = 0;

	$plain = ninoUnsealed( (string) file_get_contents( \Nino\Modules\Backups::dir( $appData ). '/'. $id. '.php' ), (string) base64_decode( (string) ( $key ?? $appData['/nino/backup/key'] ), true ) );
	$into = ninoSandboxDir( $appData ). '/read-'. ++$count;

	mkdir( $into, 0755, true );
	file_put_contents( $into. '.tar.gz', (string) $plain );

	if( \Nino\Backup::unpack( $into. '.tar.gz', $into ) !== null )
		return [ 'files' => null, 'stamp' => [], 'folder' => $into ];

	$files = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $into, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::LEAVES_ONLY ) as $file )
		$files[substr( $file->getPathname(), strlen( $into ) + 1 )] = (string) file_get_contents( $file->getPathname() );

	ksort( $files );

	return [ 'files' => $files, 'stamp' => (array) include $into. '/nino.php', 'folder' => $into ];
}

/**
 *	A backup that packs the file of the core as it lies: the callback of the core is taken out of the request, so that a copy that is not the core's can be in an archive - the state a restore has to meet
 *
 *	@param		string		$sandbox
 *
 *	@return 	string|array							The id of the archive, or what the kernel answers where it ends
 */
function crmBackupRaw( string $sandbox ): string|array {

	$request = crmRequest( $sandbox );
	unset( $request['./nino/callbacks']['/nino/admin/backup'] );

	return \Nino\Modules\Backups::now( $request );
}

/**
 *	What a backup of the project does, through the code of the kernel that makes it: a request of its own, so that nothing of an earlier connection is in it; the answer, the time it took, the archive
 *	and what the request kept of the core
 *
 *	@param		string		$sandbox			The directory of the project
 *
 *	@return 	array											answer (an id, or what the kernel answers where the backup ends), seconds, key, archive (null where there is none), kept (the keys of the request below ./crm/db)
 */
function crmBackup( string $sandbox ): array {

	$request = crmRequest( $sandbox );
	ninoWarnings();

	$began = microtime( true );
	$answer = \Nino\Modules\Backups::now( $request );
	$took = microtime( true ) - $began;

	return [ 'answer' => $answer, 'seconds' => $took, 'archive' => is_string( $answer ) === true ? crmArchive( $request, $answer ) : null, 'key' => (string) ( $request['/nino/backup/key'] ?? '' ),
		'kept' => array_values( array_filter( array_keys( $request ), static fn( string $key ): bool => in_array( $key, [ './crm/db/connections', './crm/db/ensured', './crm/db/pragmas' ], true ) === true ) ), 'warnings' => ninoWarnings() ];
}

/**
 *	The names below a folder, without the dots and without the folder of the locks the kernel makes for the backup itself
 *
 *	@param		string		$folder
 *
 *	@return 	array											null where the folder is not there
 */
function crmNames( string $folder ): ?array {

	return is_dir( $folder ) === true ? array_values( array_diff( (array) scandir( $folder ), [ '.', '..', '.locks' ] ) ) : null;
}

/*	Worker modes: this same file re-entered as a process of its own, pointed at a sandbox somebody else built. A race on an empty disk, numbers taken by several processes and a
	process that is not the owner of the files are only seen from other processes: flock() is per open file description, and root may write what nobody may	*/
if( in_array( $argv[1] ?? '', [ 'connect-worker', 'numbers-worker', 'nobody-worker', 'unreadable-worker' ], true ) === true ) {

	// The backup of a database the process may not read: a process that is nobody, and the file is the core's and another's - what the callback of the backup says of a file it cannot read
	if( $argv[1] === 'unreadable-worker' ) {

		if( function_exists( 'posix_setuid' ) === true && posix_getuid() === 0 ) {
			posix_setgid( 65534 );
			posix_setuid( 65534 );
		}

		$worker = crmRequest( (string) $argv[3], false );
		$args = [ 'dataDir' => dirname( (string) $argv[4] ), 'staging' => (string) $argv[5] ];
		$said = 'handed over';

		try {
			\Nino\Modules\Crm::callbackBackup( $worker, $args );
			$said = is_file( (string) $argv[5]. '/crm.sqlite' ) === true ? 'handed over' : 'nothing';
		}
		catch( \PDOException ) {
			$said = 'threw';
		}

		echo json_encode( [ 'uid' => posix_getuid(), 'said' => $said, 'kind' => \Nino\Modules\Crm\Db::kind( (string) $argv[4] ), 'staged' => crmNames( (string) $argv[5] ) ] );
		exit( 0 );
	}

	$worker = crmRequest( (string) $argv[3], $argv[1] !== 'connect-worker' );

	// Starts together with the others: waits for the file that says go
	if( $argv[1] === 'connect-worker' ) {

		while( is_file( $argv[4] ) === false )
			usleep( 1000 );

		try {
			$pdo = \Nino\Modules\Crm\Db::pdo( $worker );
			echo json_encode( [ 'ok' => true, 'kind' => \Nino\Modules\Crm\Db::kind( \Nino\Filesystem::path( $worker, \Nino\Modules\Crm\Db::FILE ) ), 'rows' => (int) $pdo->query( 'SELECT COUNT(*) FROM schema_versions' )->fetchColumn() ] );
		}
		catch( \Throwable $e ) {
			echo json_encode( [ 'ok' => false, 'error' => $e->getMessage() ] );
		}

		exit( 0 );
	}

	if( $argv[1] === 'numbers-worker' ) {

		while( is_file( $argv[5] ) === false )
			usleep( 1000 );

		$taken = [];
		$pdo = \Nino\Modules\Crm\Db::pdo( $worker );

		for( $round = 0; $round < (int) $argv[4]; $round++ ) {
			$pdo->exec( 'BEGIN IMMEDIATE' );
			$taken[] = \Nino\Modules\Crm\Sequences::next( $worker, $pdo, 'client', 0, 1 );
			$pdo->exec( 'COMMIT' );
		}

		echo json_encode( $taken );
		exit( 0 );
	}

	// A process that may read the files and take the locks, and may not write them: what the status says of a disk it cannot write. Root becomes nobody; an account that is not root -
	// the runner of the CI is none - takes the write bits from its own files
	crmGiveUp( (string) $argv[3] );

	$errors = [];
	set_error_handler( static function( int $level, string $message ) use ( &$errors ): bool {

		if( ( error_reporting() & $level ) !== 0 )
			$errors[] = $message;

		return true;
	} );
	$lines = \Nino\Modules\Crm\Status::lines( $worker );

	try {
		\Nino\Modules\Crm\Db::exec( $worker, 'INSERT INTO settings ( key, value ) VALUES ( \'seller_name\', \'x\' )' );
		$write = 'written';
	}
	catch( \PDOException $e ) {
		$write = 'refused';
	}

	$fix = \Nino\Modules\Crm\Status::fix( $worker, 'schema' );
	echo json_encode( [ 'uid' => posix_getuid(), 'states' => array_column( $lines, 'state', 'key' ), 'write' => $write, 'fix' => $fix, 'errors' => $errors ] );
	exit( 0 );
}


register_shutdown_function( static function() use ( $features ): void {

	if( str_starts_with( basename( $features ), 'nino-crm-features-' ) === true && ( $GLOBALS['argv'][1] ?? '' ) === '' )
		crmRemove( $features );
} );

$dir = $features. '/Crm';
$appData = ninoSandbox( 'crm6' );
$sandbox = ninoSandboxDir( $appData );
$db = \Nino\Filesystem::path( $appData, '/data/crm.sqlite' );

// --- The feature - manifest, activation and unit ---------------------------------------------------------------------

echo "The feature - manifest, activation and unit\n";

try {

$manifest = \Nino\Features::manifest( $dir );
$sixSections = [ 'shortcodes', 'markup', 'routes', 'panel', 'callbacks', 'install' ];

check( 'the manifest validates, is written for this kernel and is spelled out: category business, requires nothing, the one file of the database, pdo_sqlite, no settings of its own, no component, no stack',
	is_array( $manifest ) === true && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && $manifest['key'] === 'crm' && $manifest['category'] === 'business' && $manifest['requires'] === []
	&& $manifest['data'] === [ '/data/crm.sqlite' ] && $manifest['php'] === [ 'ext' => [ 'pdo_sqlite' ] ] && $manifest['settings'] === [] && $manifest['version'] === '2.0.0'
	&& isset( $manifest['components'] ) === false && isset( $manifest['stacks'] ) === false && array_keys( $manifest['manual'] ) === $sixSections );
check( 'every line of the manual is a sentence of at most 200 characters in both languages, and every handle is at most 120', array_filter( $manifest['manual'], static fn( array $section ): bool => array_filter( $section, static fn( array $line, string $handle ): bool => strlen( $handle ) > 120
	|| array_keys( $line ) !== [ 'en_US', 'de_DE' ] || mb_strlen( $line['en_US'] ) > 200 || mb_strlen( $line['de_DE'] ) > 200 || str_ends_with( $line['en_US'], '.' ) === false || str_ends_with( $line['de_DE'], '.' ) === false, ARRAY_FILTER_USE_BOTH ) !== [] ) === [] );
check( 'the manual names what the feature brings: the shortcode, both routes, the panel, the callbacks, the unit and the data file', isset( $manifest['manual']['shortcodes']['[mail-head]'], $manifest['manual']['routes']['/.crm/place'], $manifest['manual']['routes']['/.crm/probe'], $manifest['manual']['panel']['CRM'], $manifest['manual']['install']['/data/crm.sqlite'] ) === true
	&& count( $manifest['manual']['callbacks'] ) === 22 );
check( 'the unit and the panel words are in the folders the kernel reads, in both languages and with the same keys', array_keys( include $dir. '/text/de_DE.php' ) === array_keys( include $dir. '/text/en_US.php' ) && array_keys( include $dir. '/install/text/de_DE.php' ) === array_keys( include $dir. '/install/text/en_US.php' ) );
$privacy = (array) include $dir. '/install/elements/privacy.php';
check( 'the feature carries its section of the privacy policy, one id in the three buckets at the position its tests/catalogue.php names: the core keeps the clients', array_keys( $privacy ) === [ '*', 'de_DE', 'en_US' ] && array_keys( $privacy['de_DE'] ) === [ 'crm' ] && array_keys( $privacy['en_US'] ) === [ 'crm' ]
	&& $privacy['*']['crm']['order'] === 880 && ( (array) include $source. '/tests/catalogue.php' )['legal'] === [ 800, 899 ] && ( (array) include $dir. '/install/manifest.php' )['elements']['privacy'] === 'elements/privacy.php' );

check( 'the feature is switched on', \Nino\Features::activate( $appData, 'crm' ) === true && ninoWarnings() === [] );
check( 'the class is listed in /nino/modules and the version is recorded', in_array( '\\Nino\\Modules\\Crm', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true && \Nino\Features::get( $appData, 'crm' )['installed'] === '2.0.0' );
check( 'the unit brought the four templates of the band, add-only, in the project\'s own folder of the mails', array_map( 'basename', glob( $sandbox. '/private/templates/mail/*.tpl' ) ) === [ 'crm-head-domain.tpl', 'crm-head-sign.tpl', 'crm-head-word.tpl', 'crm-head.tpl' ] );
check( 'the picture place of the logo in mails is there, 440 by 88, with a label in the native language and no picture', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/html/images']['/logo/mail'] ?? [] ) === [ 'label' => 'Logo in E-Mails', 'width' => 440, 'height' => 88, 'filename' => null ] );
check( 'the words of the test mail are in the text files of the project, in each language', array_keys( array_filter( (array) \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] ), static fn( mixed $text, string $key ): bool => str_starts_with( $key, '[[/feature/crm/mail-brand/' ) && is_string( $text ) === true && $text !== '', ARRAY_FILTER_USE_BOTH ) ) === array_keys( array_filter( (array) include $dir. '/install/text/de_DE.php', static fn( mixed $text, string $key ): bool => str_starts_with( $key, '[[/feature/crm/mail-brand/' ), ARRAY_FILTER_USE_BOTH ) ) );

$request = crmRequest( $sandbox );
check( 'activation and init() open no database and write nothing: there is no file of it, nor a directory of the data of this feature', is_file( $db ) === false && glob( $sandbox. '/private/data/crm*' ) === [] );
check( 'the panel is in the registry while the feature is on, in the group of the features, with the permission of its own', ( \Nino\Admin\Admin::panels( $request )['crm']['group'] ?? '' ) === 'features' && \Nino\Admin\Admin::panels( $request )['crm']['perm'] === '/_admin/crm/manage' && \Nino\Modules\Crm::MANAGE_PERM === '/_admin/crm/manage' );
check( 'the one validator is behind every add(): Register is the class the five of them call', array_filter( [ 'Db/Db.php', 'Settings/Settings.php', 'Sequences/Sequences.php', 'Status/Status.php' ], static fn( string $file ): bool => str_contains( (string) file_get_contents( $GLOBALS['dir']. '/'. $file ), 'Register::add(' ) === false ) === [] );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The feature - manifest, activation and unit', $crash );
}


// --- The database - made on the first use, whole, and made once -----------------------------------------------------------

echo "\nThe database - the schema is made on the first use of the connection\n";

try {

$pdo = \Nino\Modules\Crm\Db::pdo( $request );
$reference = new \PDO( 'sqlite::memory:', null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );

foreach( ( include $dir. '/Db/schema.php' )['create'] as $statement )
	$reference->exec( $statement );

$tables = $pdo->query( 'SELECT name FROM sqlite_master WHERE type = \'table\' AND name NOT LIKE \'sqlite\_%\' ESCAPE \'\\\' ORDER BY name' )->fetchAll( \PDO::FETCH_COLUMN );
$expected = \Nino\Modules\Crm\Db::TABLES;
sort( $expected );

check( 'the file is made on the first use: the core\'s kind, with the tables the class names and no table more', is_file( $db ) === true && \Nino\Modules\Crm\Db::kind( $db ) === 'ours' && $tables === $expected && count( $tables ) === 10 );
check( 'every table, every column and every index is what schema.php says: the file and a database made from the same statements are the same, object by object, with every column\'s type, default, key and constraint', crmShape( $pdo ) === crmShape( $reference ) && count( crmShape( $pdo ) ) === 21 );
check( 'the columns are counted: clients 17, settings 2, sequences 3, records 17, project_states 3, project_shares 6, tasks 11, logbook 10, owner_devices 10, schema_versions 5',
	array_map( static fn( string $table ): int => count( $pdo->query( 'PRAGMA table_info( "'. $table. '" )' )->fetchAll() ), [ 'clients', 'settings', 'sequences', 'records', 'project_states', 'project_shares', 'tasks', 'logbook', 'owner_devices', 'schema_versions' ] ) === [ 17, 2, 3, 17, 3, 6, 11, 10, 10, 5 ] );
check( 'the register holds one row, the core\'s, with the tables it made and a time written in UTC', $pdo->query( 'SELECT package, version, applied FROM schema_versions' )->fetchAll( \PDO::FETCH_NUM )[0][0] === 'crm' && $pdo->query( 'SELECT COUNT(*) FROM schema_versions' )->fetchColumn() === 1
	&& json_decode( (string) $pdo->query( 'SELECT tables FROM schema_versions' )->fetchColumn(), true ) === \Nino\Modules\Crm\Db::TABLES && preg_match( '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/D', (string) $pdo->query( 'SELECT applied FROM schema_versions' )->fetchColumn() ) === 1 );
$formats = [
	'schema_versions' => 'package,version,tables,applied,folder', 'clients' => 'id,number,company,name,street,zip,city,country,email,phone,vat_id,business,buyer_reference,notes,active,created,updated', 'settings' => 'key,value', 'sequences' => 'category,year,next',
	'records' => 'id,kind,origin,client_id,member_id,member_email,member_name,thing,thing_id,project,purpose,parent,sha256,shown,at,ip,agent', 'project_states' => 'project,phase,favorite', 'project_shares' => 'project,client_id,since,by,source,ref',
	'tasks' => 'id,project,text,done,done_at,position,source,ref,created_by,created_at,updated_at', 'logbook' => 'id,client_id,project,at,source,kind,text,data,ref,by', 'owner_devices' => 'id,user_mail,token_sha256,previous_sha256,rotated_at,created_at,last_seen,expires,pw_mark,ua',
];
$columns = [];

foreach( $formats as $table => $unused )
	$columns[$table] = implode( ',', array_column( $pdo->query( 'PRAGMA table_info( "'. $table. '" )' )->fetchAll(), 'name' ) );

$keys = [];

foreach( [ 'records', 'project_shares' ] as $table )
	$keys[$table] = array_map( static fn( array $key ): string => $key['table']. '.'. $key['to']. ' '. $key['on_delete'], $pdo->query( 'PRAGMA foreign_key_list( "'. $table. '" )' )->fetchAll() );

$indexes = $pdo->query( 'SELECT name FROM sqlite_master WHERE type = \'index\' AND name NOT LIKE \'sqlite\\_%\' ESCAPE \'\\\' ORDER BY name' )->fetchAll( \PDO::FETCH_COLUMN );
check( 'the columns of every table are the ones the data has always been written with, in their order - the names a running stock moves with - and the tables that name a client or a record restrict their deletion', $columns === $formats && $keys === [ 'records' => [ 'records.id RESTRICT', 'clients.id RESTRICT' ], 'project_shares' => [ 'clients.id RESTRICT' ] ]
	&& $indexes === [ 'logbook_client', 'logbook_project', 'logbook_ref', 'owner_devices_user', 'project_shares_client', 'records_client_kind', 'records_client_purpose', 'records_parent', 'records_thing', 'tasks_project', 'tasks_ref' ] );
$unique = [];

try { $pdo->exec( 'INSERT INTO clients ( number, created, updated ) VALUES ( \'K-9\', \'t\', \'t\' )' ); $pdo->exec( 'INSERT INTO clients ( number, created, updated ) VALUES ( \'K-9\', \'t\', \'t\' )' ); } catch( \PDOException ) { $unique[] = 'number'; }
try { $pdo->exec( 'INSERT INTO tasks ( project, text, position, source, ref, created_at, updated_at ) VALUES ( \'p\', \'t\', 1, \'comment\', \'r1\', \'t\', \'t\' )' ); $pdo->exec( 'INSERT INTO tasks ( project, text, position, source, ref, created_at, updated_at ) VALUES ( \'q\', \'t\', 1, \'comment\', \'r1\', \'t\', \'t\' )' ); } catch( \PDOException ) { $unique[] = 'ref'; }

$pdo->exec( 'INSERT INTO tasks ( project, text, position, source, ref, created_at, updated_at ) VALUES ( \'p\', \'t\', 1, \'manual\', \'\', \'t\', \'t\' )' );
$pdo->exec( 'INSERT INTO tasks ( project, text, position, source, ref, created_at, updated_at ) VALUES ( \'p\', \'t\', 2, \'manual\', \'\', \'t\', \'t\' )' );
check( 'a number of a client is spent once, a reference of a task that came from another source once - and a task with no reference as often as it is made', $unique === [ 'number', 'ref' ] );
$pdo->exec( 'DELETE FROM tasks' );
$pdo->exec( 'DELETE FROM clients' );

check( 'the journal is DELETE and not WAL, secure_delete and foreign keys are on, and no file of a log lies beside the database after writes', strtolower( (string) $pdo->query( 'PRAGMA journal_mode' )->fetchColumn() ) === 'delete' && (int) $pdo->query( 'PRAGMA secure_delete' )->fetchColumn() === 1
	&& (int) $pdo->query( 'PRAGMA foreign_keys' )->fetchColumn() === 1 && \Nino\Modules\Crm\Db::exec( $request, 'INSERT INTO settings ( key, value ) VALUES ( \'seller_name\', \'x\' )' ) === 1 && glob( $db. '-*' ) === [] && (int) $pdo->query( 'PRAGMA busy_timeout' )->fetchColumn() === 5000 );
check( 'the header of the file says whose it is: the application id of the core', (int) $pdo->query( 'PRAGMA application_id' )->fetchColumn() === \Nino\Modules\Crm\Db::APPLICATION_ID );

$before = crmFingerprint( $db );
$pdo = null;
$again = crmRequest( $sandbox );
$connected = \Nino\Modules\Crm\Db::pdo( $again );
check( 'a second connection, in a request of its own, changes nothing: the bytes, the time and the size of the file are the same, and ensure() made no schema', crmFingerprint( $db ) === $before && \Nino\Modules\Crm\Db::ensure( $again ) === 0 && crmFingerprint( $db ) === $before );
check( 'a connection is kept for the request, by file, in the app data and in no static field', \Nino\Modules\Crm\Db::pdo( $again ) === $connected && ( new \ReflectionClass( \Nino\Modules\Crm\Db::class ) )->getStaticProperties() === [] );
\Nino\Modules\Crm\Db::close( $again );
check( 'close() lets go of the connection: the next call opens the file anew', \Nino\Modules\Crm\Db::pdo( $again ) !== $connected );

// Two requests in one process do not share what one of them kept: each has its connection, its answers and its schemas
$other = crmSandbox( 'crm6-other' );
\Nino\Features::activate( $other, 'crm' );
$otherRequest = crmRequest( ninoSandboxDir( $other ) );
\Nino\Modules\Crm\Db::exec( $otherRequest, 'INSERT INTO settings ( key, value ) VALUES ( \'seller_name\', \'Other\' )' );
check( 'the database of one project is not the database of another in the same process: each reads its own value', \Nino\Modules\Crm\Settings::get( $otherRequest, 'seller_name' ) === 'Other' && \Nino\Modules\Crm\Settings::get( $again, 'seller_name' ) === 'x' && \Nino\Modules\Crm\Settings::readOnly( $otherRequest, 'seller_name' ) === 'Other' && \Nino\Modules\Crm\Settings::readOnly( $again, 'seller_name' ) === 'x' );
crmRemove( ninoSandboxDir( $other ) );

// Two processes that connect to an empty disk at the same moment: the file is made once, and both are told it is there
$race = crmSandbox( 'crm6-race' );
\Nino\Features::activate( $race, 'crm' );
$raceDir = ninoSandboxDir( $race );
$go = $raceDir. '/go';
$workers = [];

for( $worker = 0; $worker < 4; $worker++ )
	$workers[] = crmStart( 'connect-worker', $raceDir, $go );

usleep( 400000 );
touch( $go );
$raced = array_map( static fn( array $started ): array => crmFinish( $started ), $workers );
$raceFile = $raceDir. '/private/data/crm.sqlite';
$raceDb = new \PDO( 'sqlite:'. $raceFile );
check( 'four processes that connect to an empty disk at once all get the connection - none is told the file is not its own or locked - and the file is the core\'s, whole, made once: the same objects as the reference, one row of the register, nothing half made',
	array_filter( $raced, static fn( array $done ): bool => $done[0] !== 0 || ( json_decode( $done[1], true )['ok'] ?? false ) !== true || json_decode( $done[1], true )['rows'] !== 1 ) === [] && \Nino\Modules\Crm\Db::kind( $raceFile ) === 'ours' && crmShape( $raceDb ) === crmShape( $reference ) && (int) $raceDb->query( 'SELECT COUNT(*) FROM schema_versions' )->fetchColumn() === 1 );
$raceDb = null;
crmRemove( $raceDir );

// A file that is not the core's: the file of an older program at the same path - a database with a version of its own and tables the core would make - a file that is no database, a directory, a file of a
// database that is the core's own kind but made by another program
$foreign = crmSandbox( 'crm6-foreign' );
\Nino\Features::activate( $foreign, 'crm' );
$foreignDir = ninoSandboxDir( $foreign );
$foreignFile = $foreignDir. '/private/data/crm.sqlite';
is_dir( dirname( $foreignFile ) ) === true || mkdir( dirname( $foreignFile ), 0755, true );
$older = new \PDO( 'sqlite:'. $foreignFile );
$older->exec( 'CREATE TABLE clients ( id INTEGER PRIMARY KEY, number TEXT NOT NULL UNIQUE, name TEXT )' );
$older->exec( 'INSERT INTO clients ( number, name ) VALUES ( \'K-0001\', \'Old\' )' );
$older->exec( 'PRAGMA user_version = 25' );
$older = null;

foreach( [
	'a database of an older program with a version of its own and a clients table' => static function() {},
	'a file that is no database' => static function( string $file ): void { file_put_contents( $file, "This is not a database\n". str_repeat( 'x', 200 ) ); },
	'a file that has the application id of the core at its place and no header of a database' => static function( string $file ): void { file_put_contents( $file, str_repeat( 'x', 68 ). pack( 'N', \Nino\Modules\Crm\Db::APPLICATION_ID ). str_repeat( 'y', 80 ) ); },
	'a database that has the mark of another program and a table' => static function( string $file ): void { unlink( $file ); $other = new \PDO( 'sqlite:'. $file ); $other->exec( 'PRAGMA application_id = 1234' ); $other->exec( 'CREATE TABLE t ( a )' ); },
	'a database that has no table at all and no mark' => static function( string $file ): void { unlink( $file ); $empty = new \PDO( 'sqlite:'. $file ); $empty->exec( 'PRAGMA user_version = 3' ); $empty->exec( 'CREATE TABLE t ( a )' ); $empty->exec( 'DROP TABLE t' ); },
] as $label => $make ) {

	$make( $foreignFile );
	$kept = crmFingerprint( $foreignFile );
	$request = crmRequest( $foreignDir );
	$thrown = '';

	try {
		\Nino\Modules\Crm\Db::pdo( $request );
	}
	catch( \PDOException $e ) {
		$thrown = $e->getMessage();
	}

	$states = array_column( \Nino\Modules\Crm\Status::lines( $request ), 'state', 'key' );
	$fixed = \Nino\Modules\Crm\Status::fix( $request, 'database' );
	$errors = ninoWarnings();
	check( 'a file that is another database or none ('. $label. ') is told as a failure of the database, and the file is never touched: the same bytes, time and size, no file beside it',
		$thrown !== '' && \Nino\Modules\Crm\Db::kind( $foreignFile ) === 'foreign' && crmFingerprint( $foreignFile ) === $kept );
	check( '...the status says it - the database is not the core\'s, the schema is not - and the one fix of the line is refused without a hand on the file, with a warning that names no path', $states['database'] === 'foreign' && $states['schema'] === 'foreign' && $fixed === 'failed'
		&& crmFingerprint( $foreignFile ) === $kept && count( $errors ) === 1 && str_contains( $errors[0], $foreignDir ) === false && Nino\Modules\Crm\Db::orphans( $request ) === [] && \Nino\Modules\Crm\Settings::readOnly( $request, 'seller_name' ) === '' && crmFingerprint( $foreignFile ) === $kept );
}

unlink( $foreignFile );
mkdir( $foreignFile );
$request = crmRequest( $foreignDir );
$thrown = '';

try {
	\Nino\Modules\Crm\Db::pdo( $request );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

check( 'a directory at the path is not the core\'s either: it is a failure of the database, and nothing of it is changed', $thrown !== '' && is_dir( $foreignFile ) === true && scandir( $foreignFile ) === [ '.', '..' ] && \Nino\Modules\Crm\Status::database( $request )['state'] === 'foreign' );
check( '...and the core\'s upgrade() refuses the update with a warning where the file is not its own', ( static function() use ( &$request ): bool { $result = \Nino\Modules\Crm::upgrade( $request, '1.0.0' ); return $result === false && count( ninoWarnings() ) === 1; } )() === true );
rmdir( $foreignFile );
crmRemove( $foreignDir );

// Another process makes a file that is not the core's between the look at the header and the transaction: the connection is the one that was opened before - here a database of another program, put in its place - and the
// schema is refused inside the transaction, with nothing made in it
$late = crmSandbox( 'crm6-late' );
\Nino\Features::activate( $late, 'crm' );
$lateDir = ninoSandboxDir( $late );
$elsewhere = $lateDir. '/elsewhere.sqlite';
$other = new \PDO( 'sqlite:'. $elsewhere );
$other->exec( 'CREATE TABLE t ( a )' );
$other = null;
$lateRequest = crmRequest( $lateDir );
$lateRequest['./crm/db/connections'][\Nino\Filesystem::path( $lateRequest, \Nino\Modules\Crm\Db::FILE )] = new \PDO( 'sqlite:'. $elsewhere, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
$kept = crmFingerprint( $elsewhere );
$thrown = '';

try {
	\Nino\Modules\Crm\Db::ensure( $lateRequest );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

$seen = new \PDO( 'sqlite:'. $elsewhere );
check( 'a file that turns out to be another database inside the transaction - after the header was looked at - is refused there: no table is made in it and the file is as it was', $thrown !== '' && $seen->query( 'SELECT name FROM sqlite_master' )->fetchAll( \PDO::FETCH_COLUMN ) === [ 't' ] && (int) $seen->query( 'PRAGMA application_id' )->fetchColumn() === 0 && crmFingerprint( $elsewhere ) === $kept );
$seen = null;
ninoWarnings();

// A disk that cannot be written: the data directory is a file, so that nothing can be made there - as root too, who may write what nobody may. The status says it, and nothing breaks
$locked = crmSandbox( 'crm6-locked' );
\Nino\Features::activate( $locked, 'crm' );
$lockedDir = ninoSandboxDir( $locked );
is_dir( $lockedDir. '/private' ) === true || mkdir( $lockedDir. '/private', 0755, true );
crmRemove( $lockedDir. '/private/data' );
file_put_contents( $lockedDir. '/private/data', 'a file where the directory should be' );
$request = crmRequest( $lockedDir );
$thrown = '';

try {
	\Nino\Modules\Crm\Db::pdo( $request );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

$lines = \Nino\Modules\Crm\Status::lines( $request );
$states = array_column( $lines, 'state', 'key' );
$fixed = \Nino\Modules\Crm\Status::fix( $request, 'database' );
$errors = ninoWarnings();
check( 'where the directory of the data cannot be made the connection is a failure of the database, and the status is a line that says so - the disk is not writable, the database is missing - instead of a crash', $thrown !== '' && $states['writable'] === 'unwritable' && $states['database'] === 'missing' && $states['schema'] === 'none' );
check( '...the fix is refused, with one warning that names neither a path nor the data', $fixed === 'failed' && count( $errors ) === 1 && str_contains( $errors[0], $lockedDir ) === false );
unlink( $lockedDir. '/private/data' );
crmRemove( $lockedDir. '/private' );

// ...and as a process that is not the owner of the files, where the disk can be read but not written: the line of the writing says it, a write is refused as an exception, nothing crashes
$readOnly = crmSandbox( 'crm6-readonly' );
\Nino\Features::activate( $readOnly, 'crm' );
$readOnlyDir = ninoSandboxDir( $readOnly );
$owned = crmRequest( $readOnlyDir );
\Nino\Modules\Crm\Db::pdo( $owned );
\Nino\Modules\Crm\Db::close( $owned );
$walk = [ $readOnlyDir ];

foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $readOnlyDir, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST ) as $item )
	$walk[] = (string) $item->getPathname();

foreach( $walk as $path )
	chmod( $path, is_dir( $path ) === true ? 0755 : 0644 );

$nobody = crmFinish( crmStart( 'nobody-worker', $readOnlyDir ) );

// Where the suite is not root the process took the write bits from the files: they are given back, so that the folder can be taken away
foreach( $walk as $path )
	chmod( $path, is_dir( $path ) === true ? 0755 : 0644 );

$said = json_decode( $nobody[1], true );
check( 'a process that cannot write the disk - it is nobody and the files are another\'s, or it is the owner and the files have no write bit - reads the status without a crash: the line of the writing says so, the database is the core\'s, and a write is a failure of the database',
	$nobody[0] === 0 && is_array( $said ) === true && ( $said['uid'] ?? 0 ) !== 0 && $said['states']['writable'] === 'unwritable' && $said['states']['database'] === 'ok' && $said['write'] === 'refused' && $said['errors'] === [] );
crmRemove( $readOnlyDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The database - the schema is made on the first use of the connection', $crash );
}


// --- The schemas of packages: the package of the suite docks, without the core naming it ----------------------------------

echo "\nThe schemas of packages\n";

try {



[ $pk, $pkDir, $pkRequest ] = crmWorld( 'crm6-packages' );
\Nino\Features::activate( $pk, 'crm-probe-two' );
$pkRequest = crmRequest( $pkDir );
ninoWarnings();
$pkFile = $pkDir. '/private/data/crm.sqlite';
check( 'the package of the suite is on, with the core it requires, and its init() asked first whether the core is on', \Nino\Features::get( $pk, 'crm-probe' )['active'] === true && \Nino\Modules\Crm::featureOn( $pkRequest, 'crm' ) === true && \Nino\Modules\Crm::featureOn( $pkRequest, 'crm-probe' ) === true
	&& \Nino\Modules\Crm::featureOn( $pkRequest, 'nothing-of-the-kind' ) === false );
$manifestFile = $features. '/CrmProbe/feature.php';
$original = (string) file_get_contents( $manifestFile );
file_put_contents( $manifestFile, str_replace( "'requires'", "'php' => [ 'ext' => [ 'no_such_extension' ] ],\n\t'requires'", $original ) );
$troubled = crmRequest( $pkDir, false );
$withProblems = [ \Nino\Features::get( $troubled, 'crm-probe' )['active'], \Nino\Features::get( $troubled, 'crm-probe' )['problems'] !== [], \Nino\Modules\Crm::featureOn( $troubled, 'crm-probe' ) ];
file_put_contents( $manifestFile, $original );
check( 'a package that is switched on and has a problem - an extension it needs that is not there - is not on: featureOn() is false for it, so that nothing docks to a package that cannot run', $withProblems === [ true, true, false ] );
check( 'the package entered its schema without the core naming it, and nothing is opened by entering it: the versions are the core\'s and the package\'s, and the file is not there yet', \Nino\Modules\Crm\Db::versions( $pkRequest ) === [ 'crm' => 1, 'crm-probe' => 1 ] && is_file( $pkFile ) === false );
$pkPdo = \Nino\Modules\Crm\Db::pdo( $pkRequest );
check( 'the first use of the connection made both schemas, the core\'s first, and the register holds a row for each with the tables it owns', $pkPdo->query( 'SELECT package, version, tables FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_NUM ) === [ [ 'crm', 1, json_encode( \Nino\Modules\Crm\Db::TABLES ) ], [ 'crm-probe', 1, '["crm_probe_items"]' ] ]
	&& \Nino\Modules\Crm\Db::schemas( $pkRequest )['crm-probe'] === [ 'registered' => true, 'version' => 1, 'stored' => 1, 'tables' => [ 'crm_probe_items' ] ] && \Nino\Modules\Crm\Db::orphans( $pkRequest ) === [] );
check( 'the register keeps beside each package the name of the folder the kernel lists for it - none for the core - and a row that keeps none is a package that is installed', ( static function() use ( $pkPdo, $pkRequest ): bool {
	$folders = $pkPdo->query( 'SELECT package, folder FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_KEY_PAIR );
	$pkPdo->exec( 'UPDATE schema_versions SET folder = \'\' WHERE package = \'crm-probe\'' );
	$cleared = \Nino\Modules\Crm\Db::ensure( $pkRequest ) === 0 && $pkPdo->query( 'SELECT folder FROM schema_versions WHERE package = \'crm-probe\'' )->fetchColumn() === 'CrmProbe';

	return $folders === [ 'crm' => '', 'crm-probe' => 'CrmProbe' ] && $cleared === true;
} )() );
check( 'the table of the package names the clients of the core with a foreign key that restricts: a row for a client nobody has is refused, and so is the deletion of a client that has one', ( static function() use ( $pkPdo ): bool {
	$refused = 0;

	try { $pkPdo->exec( 'INSERT INTO crm_probe_items ( client_id, label ) VALUES ( 99, \'x\' )' ); } catch( \PDOException ) { $refused++; }

	$pkPdo->exec( 'INSERT INTO clients ( id, number, created, updated ) VALUES ( 1, \'K-0001\', \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\' )' );
	$pkPdo->exec( 'INSERT INTO crm_probe_items ( client_id, label ) VALUES ( 1, \'kept\' )' );

	try { $pkPdo->exec( 'DELETE FROM clients WHERE id = 1' ); } catch( \PDOException ) { $refused++; }

	return $refused === 2;
} )() === true );
check( 'entering the same schema again is as good as the first time, and a second description of the same package that differs is refused with a warning and nothing is entered', \Nino\Modules\Crm\Db::addSchema( $pkRequest, [ 'package' => 'crm-probe', 'version' => 1, 'tables' => [ 'crm_probe_items' ], 'file' => $features. '/CrmProbe/schema.php' ] ) === true
	&& \Nino\Modules\Crm\Db::ensure( $pkRequest ) === 0 && \Nino\Modules\Crm\Db::addSchema( $pkRequest, [ 'package' => 'crm-probe', 'version' => 2, 'tables' => [ 'crm_probe_items' ], 'file' => $features. '/CrmProbe/schema-2.php' ] ) === false
	&& count( ninoWarnings() ) === 1 && \Nino\Modules\Crm\Db::versions( $pkRequest ) === [ 'crm' => 1, 'crm-probe' => 1 ] );

// What a description may not be: each with a warning, none entered
$base = [ 'package' => 'crm-probe-two', 'version' => 1, 'tables' => [ 'crm_two_items' ], 'file' => $features. '/CrmProbe/schema.php' ];
$bad = [
	'the key of the core' => [ 'package' => 'crm' ] + $base, 'a key in capitals' => [ 'package' => 'Crm-Two' ] + $base, 'a key with a quote' => [ 'package' => 'two\'; DROP TABLE clients; --' ] + $base, 'a key that is no text' => [ 'package' => 7 ] + $base,
	'no key' => array_diff_key( $base, [ 'package' => 1 ] ), 'a version of 0' => [ 'version' => 0 ] + $base, 'a version that is text' => [ 'version' => '1' ] + $base, 'a version that is a fraction' => [ 'version' => 1.5 ] + $base,
	'no tables' => [ 'tables' => [] ] + $base, 'tables that are no list' => [ 'tables' => 'crm_two_items' ] + $base, 'a table of the core' => [ 'tables' => [ 'clients' ] ] + $base, 'the register of the schemas' => [ 'tables' => [ 'schema_versions' ] ] + $base,
	'a table of another package' => [ 'tables' => [ 'crm_probe_items' ] ] + $base, 'a table of the engine' => [ 'tables' => [ 'sqlite_master' ] ] + $base, 'a table in capitals' => [ 'tables' => [ 'Crm_Two' ] ] + $base,
	'a table with a quote and a semicolon' => [ 'tables' => [ 'x"; DROP TABLE clients; --' ] ] + $base, 'a table with a null byte' => [ 'tables' => [ "crm_two\0" ] ] + $base, 'a table twice' => [ 'tables' => [ 'crm_two_items', 'crm_two_items' ] ] + $base,
	'a table that is a number' => [ 'tables' => [ 7 ] ] + $base, 'no file' => array_diff_key( $base, [ 'file' => 1 ] ), 'a file that is not there' => [ 'file' => $features. '/CrmProbe/nothing.php' ] + $base, 'a file that is relative' => [ 'file' => 'CrmProbe/schema.php' ] + $base,
	'a file outside the features directory' => [ 'file' => $root. '/tests/harness.php' ] + $base, 'a file that climbs out of it' => [ 'file' => $features. '/CrmProbe/../../'. basename( $root ). '/tests/harness.php' ] + $base, 'a file that is no php file' => [ 'file' => $features. '/CrmProbe/feature.txt' ] + $base,
	'a file with a null byte' => [ 'file' => $features. "/CrmProbe/schema.php\0.txt" ] + $base, 'a field that is none' => [ 'owner' => 'me' ] + $base, 'a description that is a list' => [ 'crm-probe-two', 1 ],
];
file_put_contents( $features. '/CrmProbe/feature.txt', '<?php return [];' );
$refused = [];

foreach( $bad as $label => $spec )
	if( \Nino\Modules\Crm\Db::addSchema( $pkRequest, $spec ) !== false || count( ninoWarnings() ) !== 1 )
		$refused[] = $label;

check( 'a schema with a key of the core or that is no slug, a version that is no whole number from 1, tables that are the core\'s, the engine\'s, another package\'s or no names, and a file that is none, outside the features directory or no php is refused with one warning each', $refused === [] && count( $bad ) === 28 );
check( '...and none of them was entered: the versions are what they were, and the warning of one names no table and no path', \Nino\Modules\Crm\Db::versions( $pkRequest ) === [ 'crm' => 1, 'crm-probe' => 1 ] && ( static function() use ( $pkRequest, $base ): bool { \Nino\Modules\Crm\Db::addSchema( $pkRequest, [ 'tables' => [ 'clients' ] ] + $base ); $warning = ninoWarnings()[0] ?? 'x'; return str_contains( $warning, 'clients' ) === false && str_contains( $warning, '/tmp' ) === false; } )() === true );

// A higher version, in a request of its own: the file has the old one, the step leads from it, and what the package holds stays
$next = crmRequest( $pkDir, false );
$next['./crm-probe/version'] = 2;
$next['./crm-probe/schema'] = 'schema-2.php';
\Nino\Modules::callModules( $next, 'init' );
$stepped = \Nino\Modules\Crm\Db::ensure( $next );
$nextPdo = \Nino\Modules\Crm\Db::pdo( $next );
check( 'a higher version is raised by its step, in a transaction, with what was in the table kept: one schema raised, the version is 2, the column is there, the row is as it was', $stepped === 1 && \Nino\Modules\Crm\Db::schemas( $next )['crm-probe']['stored'] === 2
	&& in_array( 'note', array_column( $nextPdo->query( 'PRAGMA table_info( crm_probe_items )' )->fetchAll(), 'name' ), true ) === true && $nextPdo->query( 'SELECT label FROM crm_probe_items' )->fetchColumn() === 'kept' && \Nino\Modules\Crm\Db::ensure( $next ) === 0 );
check( '...the status of the schema is whole for the package that is raised and says "behind" for the request that is not: the code of the first request has version 1 and the file 2', \Nino\Modules\Crm\Status::schema( $next )['state'] === 'ok' && \Nino\Modules\Crm\Status::schema( $pkRequest )['state'] === 'ahead' );

// ...and the older code on the newer file is no downgrade: the connection says so, and the file is as it was
$fileState = crmFingerprint( $pkFile );
ninoWarnings();
$older = crmRequest( $pkDir );
$thrown = '';

try {
	\Nino\Modules\Crm\Db::pdo( $older );
	\Nino\Modules\Crm\Settings::get( $older, 'seller_name' );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

$warned = ninoWarnings();
check( 'older code of a package on a newer file is the failure of that package and never a downgrade: the connection and the core work, the package is listed as failed with the version it asked for, the version of the file stays 2, the column stays, and the bytes are the same',
	$thrown === '' && \Nino\Modules\Crm\Db::failed( $older ) === [ 'crm-probe' => 1 ] && crmFingerprint( $pkFile ) === $fileState && \Nino\Modules\Crm\Db::schemas( $next )['crm-probe']['stored'] === 2 );
check( '...with one warning that names the package and the version and nothing of the file, and the package is no schema of the request any more', count( $warned ) === 1 && str_contains( $warned[0], 'crm-probe' ) === true && str_contains( $warned[0], $pkDir ) === false && isset( \Nino\Modules\Crm\Db::versions( $older )['crm-probe'] ) === false );

// A version with no step to it: nothing changes
$jump = crmRequest( $pkDir, false );
$jump['./crm-probe/version'] = 4;
$jump['./crm-probe/schema'] = 'schema-2.php';
\Nino\Modules::callModules( $jump, 'init' );
$thrown = '';
ninoWarnings();

try {
	\Nino\Modules\Crm\Db::pdo( $jump );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

check( 'a version that has no step to it is the failure of that package: nothing is half raised, the version is 2 and the file is the same, and the core works', $thrown === '' && \Nino\Modules\Crm\Db::failed( $jump ) === [ 'crm-probe' => 4 ] && crmFingerprint( $pkFile ) === $fileState && count( ninoWarnings() ) === 1 );
\Nino\Modules\Crm\Db::close( $pkRequest );
\Nino\Modules\Crm\Db::close( $next );

// Statements that are broken, that end the transaction or that touch what is not the package's: the schema of the package is nothing - no table half made, no row in the register - and the core is as it was
$items = 'CREATE TABLE crm_probe_items ( id INTEGER PRIMARY KEY, label TEXT NOT NULL DEFAULT \'\' )';
$cases = [
	'a statement that is broken, after one that is good' => [ $items, 'CREATE TABLE broken (' ],
	'a statement that is not one' => [ $items, 'this is not sql' ],
	'a statement that commits' => [ $items, 'COMMIT' ],
	'a statement that begins' => [ 'BEGIN', $items ],
	'a statement that ends with end' => [ $items, 'END' ],
	'a statement that rolls back' => [ $items, 'ROLLBACK' ],
	'a savepoint' => [ 'SAVEPOINT x', $items ],
	'a release' => [ $items, 'RELEASE x' ],
	'a pragma after a comment' => [ $items, '/* a comment */ PRAGMA user_version = 9' ],
	'a pragma after a line comment' => [ "-- a comment\nPRAGMA user_version = 9", $items ],
	'an attach' => [ $items, 'ATTACH DATABASE \':memory:\' AS other' ],
	'a detach' => [ $items, 'DETACH DATABASE other' ],
	'a vacuum' => [ $items, 'VACUUM' ],
	'two statements in one' => [ $items. '; DROP TABLE clients' ],
	'two harmless statements in one' => [ $items. '; CREATE INDEX crm_probe_items_label ON crm_probe_items ( id )' ],
	'a schema that makes nothing' => [],
	'a statement that is not text' => [ $items, 7 ],
	'a statement that is empty' => [ $items, '  ' ],
	'a table of the core, changed' => [ $items, 'ALTER TABLE clients ADD COLUMN evil TEXT' ],
	'a table of the core, dropped' => [ $items, 'DROP TABLE settings' ],
	'an index on a table of the core' => [ $items, 'CREATE INDEX evil ON clients ( name )' ],
	'a table that is not declared' => [ $items, 'CREATE TABLE evil ( a )' ],
	'an index of the core, dropped' => [ $items, 'DROP INDEX records_parent' ],
	'no table of the package at all' => [ 'CREATE TABLE evil_two ( a )' ],
	'the table of another package, made over' => [ $items, 'DROP TABLE schema_versions' ],
	// What the first form of the protection let through: a statement that writes
	'a deletion of the clients' => [ $items, 'DELETE FROM clients' ],
	'an update of the settings' => [ $items, 'UPDATE settings SET value = \'pwned\' WHERE key = \'seller_name\'' ],
	'an insert or replace into the settings' => [ $items, 'INSERT OR REPLACE INTO settings ( key, value ) VALUES ( \'crm_host\', \'evil.example.com\' )' ],
	'a replace into the settings' => [ $items, 'REPLACE INTO settings ( key, value ) VALUES ( \'crm_host\', \'evil.example.com\' )' ],
	'a with that updates the clients' => [ $items, 'WITH x AS ( SELECT 1 ) UPDATE clients SET name = \'pwned\'' ],
	'a temporary table that shadows the clients' => [ $items, 'CREATE TEMP TABLE clients ( id INTEGER )' ],
	'a temporary table spelled out' => [ $items, 'CREATE TEMPORARY TABLE crm_probe_items ( id INTEGER )' ],
	'a deletion of the row of the core in the register' => [ $items, 'DELETE FROM schema_versions WHERE package = \'crm\'' ],
	'an update of the register' => [ $items, 'UPDATE schema_versions SET version = 9' ],
	'a trigger on a table of the package that deletes clients' => [ $items, 'CREATE TRIGGER evil AFTER INSERT ON crm_probe_items BEGIN DELETE FROM clients; END' ],
	'a view over the clients' => [ $items, 'CREATE VIEW evil AS SELECT * FROM clients' ],
	'a virtual table' => [ $items, 'CREATE VIRTUAL TABLE evil USING fts5( a )' ],
	'an insert or rollback' => [ $items, 'INSERT OR ROLLBACK INTO crm_probe_items ( label ) VALUES ( \'x\' )' ],
	// ...in disguise: a comment in front of the verb or inside the statement, other letters, line breaks, quotation marks, brackets, backticks, the schema main
	'a comment before the verb' => [ $items, '/* x */ DELETE FROM clients' ],
	'a line comment before the verb' => [ $items, "-- y\nDELETE FROM clients" ],
	'a comment inside the statement' => [ $items, 'DELETE /* a */ FROM /* b */ clients' ],
	'lower case' => [ $items, 'delete from clients' ],
	'line breaks' => [ $items, "DELETE\nFROM\n\tclients" ],
	'the target in quotation marks' => [ $items, 'DELETE FROM "clients"' ],
	'the target in brackets' => [ $items, 'DELETE FROM [clients]' ],
	'the target in backticks' => [ $items, 'DELETE FROM `clients`' ],
	'the target with the schema main' => [ $items, 'DELETE FROM main.clients' ],
	'the target with the schema temp' => [ $items, 'DELETE FROM temp.clients' ],
	'the target in capitals' => [ $items, 'DELETE FROM CLIENTS' ],
	'a statement that ends with a comment and a second statement' => [ $items. ' -- x', 'DELETE FROM crm_probe_items; DELETE FROM clients' ],
	'a quote that is never closed' => [ $items, 'DELETE FROM crm_probe_items WHERE label = \'x' ],
	'a comment that is never closed' => [ $items, 'DELETE FROM crm_probe_items /* x' ],
	'an index of the core made over' => [ $items, 'CREATE INDEX IF NOT EXISTS records_parent ON crm_probe_items ( id )' ],
	'a rename of a table of the package to the clients' => [ $items, 'ALTER TABLE crm_probe_items RENAME TO clients' ],
];
$unmade = [];

foreach( $cases as $label => $statements ) {

	[ $fresh, $freshDir, $freshRequest ] = crmWorld( 'crm6-broken' );

	// The core has its rows before the package comes: a client, a setting, and the register
	$plain = crmRequest( $freshDir, false );
	\Nino\Modules\Crm\Db::exec( $plain, 'INSERT INTO clients ( number, created, updated ) VALUES ( \'K-1\', \'x\', \'x\' )' );
	\Nino\Modules\Crm\Db::exec( $plain, 'INSERT INTO settings ( key, value ) VALUES ( \'seller_name\', \'Owner\' )' );
	\Nino\Modules\Crm\Db::close( $plain );
	$rowsOf = static function( \PDO $pdo ): array {
		return [ $pdo->query( 'SELECT * FROM clients' )->fetchAll( \PDO::FETCH_ASSOC ), $pdo->query( 'SELECT * FROM settings ORDER BY key' )->fetchAll( \PDO::FETCH_ASSOC ), $pdo->query( 'SELECT package, version, tables FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_ASSOC ) ];
	};
	$before = new \PDO( 'sqlite:'. $freshDir. '/private/data/crm.sqlite' );
	$rowsBefore = $rowsOf( $before );
	$before = null;

	$freshRequest['./crm-probe/schema'] = crmSchemaFile( 'schema-broken.php', $statements );
	\Nino\Modules\Crm\Db::close( $freshRequest );
	unset( $freshRequest['./crm/db/schemas'] );
	\Nino\Modules\CrmProbe::init( $freshRequest );
	$thrown = '';
	$temp = -1;

	try {
		$connection = \Nino\Modules\Crm\Db::pdo( $freshRequest );
		$temp = (int) $connection->query( 'SELECT COUNT(*) FROM sqlite_temp_master' )->fetchColumn();
	}
	catch( \PDOException $e ) {
		$thrown = $e->getMessage();
	}

	// What stands in the file: opened on its own, as another process would see it
	$seen = new \PDO( 'sqlite:'. $freshDir. '/private/data/crm.sqlite' );
	$register = $seen->query( 'SELECT package FROM schema_versions' )->fetchAll( \PDO::FETCH_COLUMN );
	$names = array_diff( $seen->query( 'SELECT name FROM sqlite_master WHERE name NOT LIKE \'sqlite\_%\' ESCAPE \'\\\'' )->fetchAll( \PDO::FETCH_COLUMN ), array_column( crmShape( $reference ) === [] ? [] : [], 0 ) );
	$core = crmShape( $seen ) === crmShape( $reference ) || array_diff_key( crmShape( $seen ), crmShape( $reference ) ) !== [];
	$clean = $thrown === '' && \Nino\Modules\Crm\Db::failed( $freshRequest ) === [ 'crm-probe' => 1 ] && $register === [ 'crm' ] && crmShape( $seen ) === crmShape( $reference ) && (int) $seen->query( 'PRAGMA user_version' )->fetchColumn() === 0
		&& $rowsOf( $seen ) === $rowsBefore && $temp === 0;
	$seen = null;

	if( $clean === false )
		$unmade[] = $label;

	ninoWarnings();
	crmRemove( $freshDir );
}

check( 'a schema with a statement that is broken, that begins, ends or leaves the transaction, attaches, vacuums, sets a pragma or is more than one, that is none of the forms of a schema - it writes the clients, the settings or the register, makes a temporary table, a view or a trigger, or is hidden behind a comment, other letters, line breaks, quotes, brackets or the schema main - that changes a table or an index that is not the package\'s, makes a table it did not declare or none it did, is nothing: the failure is that of the package, the connection and the core work, the register has no row for the package, no table is half made, no temporary table is left, the rows of the clients, the settings and the register are as they were, and the core is as it was',
	$unmade === [] && count( $cases ) === 54 );

// A schema that is entered after the connection was opened is made with the next use of it: the mark that the schemas were checked is dropped
[ $lateProject, $lateDir, $unusedRequest ] = crmWorld( 'crm6-latecomer' );
$latecomer = crmRequest( $lateDir, false );
$beforeLate = \Nino\Modules\Crm\Db::ensure( $latecomer );
check( 'before the package is entered the file has the core\'s schema alone', $beforeLate === 1 && array_keys( \Nino\Modules\Crm\Db::schemas( $latecomer ) ) === [ 'crm' ] );
\Nino\Modules\CrmProbe::init( $latecomer );
$lateTables = (int) \Nino\Modules\Crm\Db::pdo( $latecomer )->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'crm_probe_items\'' )->fetchColumn();
check( 'a schema that a package enters after the connection was made is made with the next use of it, in the same request: the table is there and the register has the package', $lateTables === 1 && \Nino\Modules\Crm\Db::schemas( $latecomer )['crm-probe']['stored'] === 1 && \Nino\Modules\Crm\Db::ensure( $latecomer ) === 0 );
\Nino\Modules\Crm\Db::close( $latecomer );
crmRemove( $lateDir );

// A package that is off: its tables and its rows stay, and the core knows them as data nobody serves
$offRequest = crmRequest( $pkDir, false );
\Nino\Features::deactivate( $pk, 'crm-probe' );
$offRequest = crmRequest( $pkDir );
$rows = \Nino\Modules\Crm\Db::pdo( $offRequest )->query( 'SELECT COUNT(*) FROM crm_probe_items' )->fetchColumn();
check( 'a package that is switched off does not enter its schema, and its table and its row of the register stay as they were: the core lists it as data of a package that is gone, installed, with its tables', \Nino\Modules\Crm\Db::versions( $offRequest ) === [ 'crm' => 1 ]
	&& \Nino\Modules\Crm\Db::orphans( $offRequest ) === [ [ 'package' => 'crm-probe', 'version' => 2, 'tables' => [ 'crm_probe_items' ], 'installed' => true ] ] && (int) $rows === 1 );
$twin = crmRequest( $pkDir );
\Nino\Modules\Crm\Db::addSchema( $twin, [ 'package' => 'crm-probe-two', 'version' => 1, 'tables' => [ 'crm_probe_items' ], 'file' => $features. '/CrmProbeTwo/schema.php' ] );
$thrown = '';

try {
	\Nino\Modules\Crm\Db::pdo( $twin );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

check( 'a package whose schema names a table that the register gives to another package - one that is off, and so is not entered in this request - is the failure of that package when the schema is made: the register of the file says who owns it, and the table and its row are as they were',
	$thrown === '' && \Nino\Modules\Crm\Db::failed( $twin ) === [ 'crm-probe-two' => 1 ] && (int) \Nino\Modules\Crm\Db::pdo( $offRequest )->query( 'SELECT COUNT(*) FROM schema_versions WHERE package = \'crm-probe-two\'' )->fetchColumn() === 0 && (int) \Nino\Modules\Crm\Db::pdo( $offRequest )->query( 'SELECT COUNT(*) FROM crm_probe_items' )->fetchColumn() === 1 );
check( 'the core never reads a table of a package: the settings of the package are none, its sequence is none, its shared path is not shared, its line of the status is not there, and the same file is read by the core alone',
	isset( \Nino\Modules\Crm\Settings::defaults( $offRequest )['probe_limit'] ) === false && \Nino\Modules\Crm\Sequences::categories( $offRequest ) === [ 'client' => 'crm' ] && \Nino\Modules\Crm\Hosts::shared( $offRequest, '/.probe/zip' ) === false
	&& in_array( 'probe-line', array_column( \Nino\Modules\Crm\Status::lines( $offRequest ), 'key' ), true ) === false );
check( 'the data of a package that is only off is not taken away - dropSchema() says it is installed and it comes back with its data - and the tables are as they were', \Nino\Modules\Crm\Db::dropSchema( $offRequest, 'crm-probe' ) === 'installed' && (int) \Nino\Modules\Crm\Db::pdo( $offRequest )->query( 'SELECT COUNT(*) FROM crm_probe_items' )->fetchColumn() === 1 );
check( '...and the package coming back finds its data: switched on again it is not an orphan any more, and its row is read', \Nino\Features::activate( $pk, 'crm-probe' ) === true && ( static function() use ( $pkDir ): bool { $again = crmRequest( $pkDir, true, [ './crm-probe/version' => 2, './crm-probe/schema' => 'schema-2.php' ] ); return \Nino\Modules\Crm\Db::orphans( $again ) === [] && (int) \Nino\Modules\Crm\Db::pdo( $again )->query( 'SELECT COUNT(*) FROM crm_probe_items' )->fetchColumn() === 1; } )() === true );

// The core off too: the package is initialised all the same, and returns at once
\Nino\Features::deactivate( $pk, 'crm-probe' );
\Nino\Features::deactivate( $pk, 'crm-probe-two' );
\Nino\Features::deactivate( $pk, 'crm' );
ninoWarnings();
$coreOff = crmRequest( $pkDir, false );
\Nino\Modules\CrmProbe::init( $coreOff );
check( 'with the core switched off a package that is initialised anyway registers nothing: no schema, no setting, no sequence, no line, no shared path, no band and no hook - it begins with the question whether the core is on',
	\Nino\Modules\Crm::featureOn( $coreOff, 'crm' ) === false && isset( $coreOff['./crm/db/schemas'] ) === false && isset( $coreOff['./crm/settings/keys'] ) === false && isset( $coreOff['./crm/sequences/categories'] ) === false && isset( $coreOff['./crm/status/lines'] ) === false
	&& isset( $coreOff['./crm/hosts/shares'] ) === false && isset( $coreOff['./crm/mail/roots'] ) === false && isset( $coreOff['./nino/html/shortcodes']['mail-head'] ) === false && ninoWarnings() === [] );
\Nino\Features::activate( $pk, 'crm-probe' );
\Nino\Features::activate( $pk, 'crm-probe-two' );

// A package that is removed: its directory is gone. Its data is shown as that of a package that is not installed, and only then can it be taken away
\Nino\Features::deactivate( $pk, 'crm-probe' );
$removed = \Nino\Features::remove( $pk, 'crm-probe' );
$goneRequest = crmRequest( $pkDir );
check( 'a package that is removed leaves its data, and the data is listed as that of a package that is not installed, with its tables', $removed === true && \Nino\Features::get( $pk, 'crm-probe' ) === null && \Nino\Modules\Crm\Db::orphans( $goneRequest ) === [ [ 'package' => 'crm-probe', 'version' => 2, 'tables' => [ 'crm_probe_items' ], 'installed' => false ] ] );

$goneDb = \Nino\Modules\Crm\Db::pdo( $goneRequest );
$kept = crmShape( $goneDb );
$refusals = [
	'the core' => \Nino\Modules\Crm\Db::dropSchema( $goneRequest, 'crm' ), 'a package nobody entered' => \Nino\Modules\Crm\Db::dropSchema( $goneRequest, 'crm-nothing' ), 'a key that is no key' => \Nino\Modules\Crm\Db::dropSchema( $goneRequest, 'Crm"; DROP TABLE clients' ),
	'an empty key' => \Nino\Modules\Crm\Db::dropSchema( $goneRequest, '' ),
];
check( 'dropSchema() refuses the core, a package nobody has a row for, and a key that is no slug - each with its word - and nothing of the file changes', $refusals === [ 'the core' => 'core', 'a package nobody entered' => 'unknown', 'a key that is no key' => 'unknown', 'an empty key' => 'unknown' ] && crmShape( $goneDb ) === $kept );

// A row of the register that names what is not the package's - a table of the core, a name that is none, a list that is no list - is not trusted: nothing is dropped
$damaged = [];

foreach( [ '["clients"]', '["crm_probe_items","settings"]', '["x\\"; DROP TABLE clients; --"]', '["crm_probe_items", 7]', 'not json', '{"a":"crm_probe_items"}', '[]x', '["schema_versions"]' ] as $stored ) {

	$goneDb->prepare( 'UPDATE schema_versions SET tables = :tables WHERE package = \'crm-probe\'' )->execute( [ 'tables' => $stored ] );
	$answer = \Nino\Modules\Crm\Db::dropSchema( $goneRequest, 'crm-probe' );

	if( in_array( $answer, [ 'damaged', 0 ], true ) === false || array_diff_key( $kept, crmShape( $goneDb ) ) !== [] || ( $stored === '{"a":"crm_probe_items"}' && ( \Nino\Modules\Crm\Db::orphans( $goneRequest )[0]['tables'] ?? [ 'x' ] ) !== [] ) )
		$damaged[] = $stored. ' => '. var_export( $answer, true );
}

check( 'a row of the register that names a table of the core, a name that is no name, or no list is not trusted: dropSchema() drops nothing of the core or of the register and never runs what the row says', $damaged === [] );
$goneDb->prepare( 'UPDATE schema_versions SET tables = \'["crm_probe_items"]\' WHERE package = \'crm-probe\'' )->execute();
$goneCore = array_diff_key( crmShape( $goneDb ), [ 'table crm_probe_items' => 1, 'index crm_probe_items_client' => 1 ] );
$asked = \Nino\Modules\Crm\Db::dropSchema( $goneRequest, 'crm-probe' );
check( 'the data of a package that is removed is dropped with dropSchema(): its tables and its row are gone, the number of tables is the answer, and the core is as it was, object by object', $asked === 1 && crmShape( $goneDb ) === $goneCore && crmShape( $goneDb ) === crmShape( $reference )
	&& $goneDb->query( 'SELECT package FROM schema_versions' )->fetchAll( \PDO::FETCH_COLUMN ) === [ 'crm' ] && \Nino\Modules\Crm\Db::orphans( $goneRequest ) === [] && \Nino\Modules\Crm\Db::ensure( $goneRequest ) === 0 );
// The package of the suite is made again in its folder: the removal took the folder away
crmCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
check( 'a package that is on, or that entered its schema in this request, is never dropped: dropSchema() says so and the table stays', ( static function() use ( $features ): bool {

	$world = crmWorld( 'crm6-registered' );
	\Nino\Modules\Crm\Db::pdo( $world[2] );
	\Nino\Modules\Crm\Db::ensure( $world[2] );
	$registered = \Nino\Modules\Crm\Db::dropSchema( $world[2], 'crm-probe' );
	$unregistered = crmRequest( $world[1], false );
	$on = \Nino\Modules\Crm\Db::dropSchema( $unregistered, 'crm-probe' );
	$tables = (int) \Nino\Modules\Crm\Db::pdo( $unregistered )->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'crm_probe_items\'' )->fetchColumn();
	$listed = \Nino\Modules\Crm\Db::orphans( $unregistered ) === [] && \Nino\Modules\Crm\Db::orphans( $world[2] ) === [];
	crmRemove( $world[1] );

	return $registered === 'registered' && $on === 'on' && $tables === 1 && $listed === true;
} )() === true );
// What a request keeps of the answers of the database, and the runner a deletion hands to a package
$kept = crmRequest( $pkDir, false );
$reads = 0;
$read = static function() use ( &$reads ): int { return ++$reads; };
$first = \Nino\Modules\Crm\Db::kept( $kept, 'group', 'question', $read );
$again = \Nino\Modules\Crm\Db::kept( $kept, 'group', 'question', $read );
$another = \Nino\Modules\Crm\Db::kept( $kept, 'group', 'another', $read );
\Nino\Modules\Crm\Db::drop( $kept, 'group' );
$dropped = \Nino\Modules\Crm\Db::kept( $kept, 'group', 'question', $read );
\Nino\Modules\Crm\Db::exec( $kept, 'DELETE FROM settings WHERE key = \'nothing\'' );
$afterWrite = \Nino\Modules\Crm\Db::kept( $kept, 'group', 'question', $read );
$kept['./nino/http/requests'] = [ [ 'a' ] ];
$newRequest = \Nino\Modules\Crm\Db::kept( $kept, 'group', 'question', $read );
check( 'an answer of the database is read once for a request - the same question is not asked twice, another is - a write and drop() make it read again, and the next request starts with nothing', [ $first, $again, $another, $dropped, $afterWrite, $newRequest ] === [ 1, 1, 2, 3, 4, 5 ] );
$runnerPdo = \Nino\Modules\Crm\Db::pdo( $kept );
$run = \Nino\Modules\Crm\Db::runner( $runnerPdo );
$stopped = [];

foreach( [ 'PRAGMA user_version = 3', 'BEGIN', 'COMMIT', 'end', '/* x */ ROLLBACK', "-- x\nSAVEPOINT a", 'RELEASE a', 'ATTACH DATABASE \':memory:\' AS o', 'DETACH DATABASE o', 'VACUUM', 'DELETE FROM settings; DELETE FROM clients', 'DELETE FROM settings WHERE key = \'a;b\'' ] as $statement ) {

	try { $run( $statement ); $stopped[] = $statement; } catch( \LogicException ) { }
}

check( 'the runner a package gets inside a deletion runs one prepared statement with its parameters and answers the rows it changed, and refuses what would end or leave the transaction - a pragma, begin, commit, end, rollback, a savepoint, release, attach, detach, vacuum, a second statement, a semicolon anywhere - with an exception', $stopped === [] && ( static function() use ( $run, $runnerPdo ): bool {
	$runnerPdo->exec( 'INSERT INTO settings ( key, value ) VALUES ( \'r1\', \'x\' ), ( \'r2\', \'x\' )' );

	return $run( 'DELETE FROM settings WHERE value = :v', [ 'v' => 'x' ] ) === 2 && $run( 'DELETE FROM settings WHERE key = \'none\';' ) === 0;
} )() === true );

}
catch( \Throwable $crash ) {
	crmCrashed( 'The schemas of packages', $crash );
}


// --- The database held by another process ----------------------------------------------------------------------------------

echo "\nThe database held by another process\n";

try {

[ $lk, $lkDir, $lkRequest ] = crmWorld( 'crm6-locked' );
\Nino\Modules\Crm\Settings::set( $lkRequest, 'crm_host', 'crm.example.org' );
\Nino\Modules\Crm\Settings::set( $lkRequest, 'app_host', 'app.example.org' );
$lkFile = $lkDir. '/private/data/crm.sqlite';
$lkFresh = static function() use ( $lkDir ): array {

	$request = crmRequest( $lkDir );
	\Nino\Html::addFills( $request, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );

	return $request;
};
$lkVisit = static function( string $host, string $uri, int $wait = 150 ) use ( $lkFresh ): array {

	$request = $lkFresh();
	$request['./crm/hosts/wait'] = $wait;
	$sent = crmVisit( $request, $host, $uri );
	$answer = $sent;

	// Only a request that was turned to the route has its answer made there
	$turned = (string) $sent['/nino/http/request']['uri'] === \Nino\Modules\Crm\Hosts::ROUTE;

	if( $turned === true )
		\Nino\Modules\Crm\Hosts::callbackRedirect( $request, $answer );

	return [ (string) $sent['/nino/http/request']['uri'], $turned === true ? (int) $answer['/nino/http/response']['statusCode'] : 0, (string) ( $turned === true ? $answer['/nino/http/response']['header']['Retry-After'] ?? '' : '' ) ];
};
$place = \Nino\Modules\Crm\Hosts::ROUTE;

check( 'with the database free a name that does not own the places of the owner is turned away from them, the CRM host and the website are not', $lkVisit( 'evil.example.org', '/crm' ) === [ $place, 404, '' ] && $lkVisit( 'evil.example.org', '/_admin/' ) === [ $place, 404, '' ]
	&& $lkVisit( 'crm.example.org', '/crm' ) === [ '/crm', 0, '' ] && $lkVisit( 'www.example.org', '/' ) === [ '/', 0, '' ] );

// A lock another process holds for a second: the read of the names waits for it - as long as a write would - and the names are what the settings say
@unlink( $lkFile. '.let-go' );
$held = crmHold( $lkFile, 1500000 );
$started = microtime( true );
$waited = $lkVisit( 'evil.example.org', '/crm', 5000 );
crmRelease( $held );

// The request came while the holder held: it was asked before the holder let go - else this proves nothing of waiting, and says so
$took = is_file( $lkFile. '.let-go' ) === true && $started < (float) file_get_contents( $lkFile. '.let-go' );
check( 'a lock that another process holds for less than the wait does not open the places of the owner: the request waits for it and is answered by the names of the settings - a name that does not own them is turned away, 404', $waited === [ $place, 404, '' ] && $took === true );

// A lock held for longer than the wait: the places of the owner are shut, on every name, and the website is not
$held = crmHold( $lkFile );
$shut = [ $lkVisit( 'evil.example.org', '/crm' ), $lkVisit( 'evil.example.org', '/_admin/' ), $lkVisit( 'evil.example.org', '/CRM/api' ), $lkVisit( 'crm.example.org', '/crm' ), $lkVisit( 'app.example.org', '/_admin/' ), $lkVisit( '', '/crm' ) ];
$open = [ $lkVisit( 'evil.example.org', '/' ), $lkVisit( 'www.example.org', '/' ), $lkVisit( 'www.example.org', '/page' ), $lkVisit( 'evil.example.org', '/page' ) ];
check( 'a database that stays locked past the wait shuts the places of the owner on every name, with 503 and the time to try again, and never opens them: the sign-in, the workbench and the CRM are not the page of a name that cannot be told from the owner\'s',
	array_unique( array_map( 'json_encode', $shut ) ) === [ json_encode( [ $place, 503, '5' ] ) ] );
check( '...and the website itself stays reachable on every name: its pages are not turned', $open === [ [ '/', 0, '' ], [ '/', 0, '' ], [ '/page', 0, '' ], [ '/page', 0, '' ] ] );

$read = $lkFresh();
$readOnly = $lkFresh();
$answers = [ \Nino\Modules\Crm\Settings::readOrNull( $read, 'crm_host', 100 ), \Nino\Modules\Crm\Settings::readOnly( $readOnly, 'crm_host' ) ];
check( 'a read of a setting that cannot be made is told from one that holds nothing: readOrNull() says null where the database is locked, readOnly() says the empty text the callers that only show a name have always had', $answers === [ null, '' ] );

$seen = $lkFresh();
$states = array_column( \Nino\Modules\Crm\Status::lines( $seen ), 'state', 'key' );
$fixable = array_column( \Nino\Modules\Crm\Status::lines( $seen ), 'fixable', 'key' );
check( 'a database that another process holds is busy, and no more: the status says busy for the database, the schema and the addresses, not damaged and not none, the writing is as it was, and neither line offers a press', \Nino\Modules\Crm\Db::inspect( $seen )['file'] === 'busy'
	&& $states['database'] === 'busy' && $states['schema'] === 'busy' && $states['addresses'] === 'busy' && $states['writable'] === 'ok' && $fixable['database'] === false && $fixable['schema'] === false && \Nino\Modules\Crm\Db::orphans( $seen ) === [] );
crmRelease( $held );
$after = $lkFresh();
$states = array_column( \Nino\Modules\Crm\Status::lines( $after ), 'state', 'key' );
check( 'where the lock is let go the status is what it was: the database ok, the schema ok, the addresses ok', $states['database'] === 'ok' && $states['schema'] === 'ok' && $states['addresses'] === 'ok' && \Nino\Modules\Crm\Db::inspect( $after )['file'] === 'ours' );
crmRemove( $lkDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The database held by another process', $crash );
}


// --- A package that fails, a key that is not the package's, the data a package leaves ----------------------------------------

echo "\nA package that fails, and the data a package leaves\n";

try {

// One package that cannot be raised and one that can: the core and the good one work, the bad one is a line of the status in red
[ $fl, $flDir, $flWorld ] = crmWorld( 'crm6-failing' );
\Nino\Features::activate( $fl, 'crm-probe-two' );
$flPlain = crmRequest( $flDir, false );
\Nino\Modules\CrmProbe::init( $flPlain );
$flBad = crmSchemaFile( 'schema-bad.php', [ 'CREATE TABLE crm_two_a ( id INTEGER )', 'THIS IS NOT SQL' ], [], 'CrmProbeTwo' );
ninoWarnings();
\Nino\Modules\Crm\Db::addSchema( $flPlain, [ 'package' => 'crm-probe-two', 'version' => 3, 'tables' => [ 'crm_two_a' ], 'file' => $features. '/CrmProbeTwo/'. $flBad ] );
$thrown = '';

try {
	$flPdo = \Nino\Modules\Crm\Db::pdo( $flPlain );
	\Nino\Modules\Crm\Settings::get( $flPlain, 'seller_name' );
}
catch( \PDOException $e ) {
	$thrown = $e->getMessage();
}

$flWarned = ninoWarnings();
$flLines = array_column( \Nino\Modules\Crm\Status::lines( $flPlain ), null, 'key' );
check( 'a package whose schema cannot be raised is that package alone: the connection and the core work, the good package has its table and its row of the register, the bad one has none of either, and nothing of it is half made', $thrown === ''
	&& (int) $flPdo->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'crm_probe_items\'' )->fetchColumn() === 1 && (int) $flPdo->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'crm_two_a\'' )->fetchColumn() === 0
	&& $flPdo->query( 'SELECT package FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_COLUMN ) === [ 'crm', 'crm-probe' ] && \Nino\Modules\Crm\Db::failed( $flPlain ) === [ 'crm-probe-two' => 3 ] );
check( '...its entry is refused with one warning that names the package and the version and neither a path nor a statement, and it is a schema of this request no more', count( $flWarned ) === 1 && str_contains( $flWarned[0], 'crm-probe-two' ) === true && str_contains( $flWarned[0], 'THIS IS' ) === false
	&& str_contains( $flWarned[0], $flDir ) === false && array_keys( \Nino\Modules\Crm\Db::versions( $flPlain ) ) === [ 'crm', 'crm-probe' ] );
check( '...and the status has a red line of its own for it, with the name of the package and the version it asked for, that no press of the core mends', ( $flLines['schema-crm-probe-two'] ?? [] ) === [ 'key' => 'schema-crm-probe-two', 'hint' => false, 'fixable' => false, 'state' => 'unraised', 'package' => 'crm-probe-two', 'version' => 3, 'label' => 'crm-probe-two' ]
	&& \Nino\Modules\Crm\Status::whole( array_values( $flLines ) ) === false );
// The schemas are checked once for a connection: a use of it later asks the file nothing - a row taken out of the register by another process is not noticed, and no package is tried again
$flOther = new \PDO( 'sqlite:'. $flDir. '/private/data/crm.sqlite' );
$flOther->exec( 'DELETE FROM schema_versions WHERE package = \'crm-probe\'' );
$flOther = null;
\Nino\Modules\Crm\Db::pdo( $flPlain );
check( 'the schemas are checked once for a connection: a second use of it makes and raises nothing, and a package that failed is not tried again - no second warning', ninoWarnings() === [] && \Nino\Modules\Crm\Db::failed( $flPlain ) === [ 'crm-probe-two' => 3 ]
	&& (int) $flPdo->query( 'SELECT COUNT(*) FROM schema_versions WHERE package = \'crm-probe\'' )->fetchColumn() === 0 );
crmRemove( $flDir );

// The key of a schema is the key of a feature that is on
[ $kp, $kpDir, $kpWorld ] = crmWorld( 'crm6-keys' );
$kpRequest = crmRequest( $kpDir, false );
$kpFile = $features. '/CrmProbeTwo/schema.php';
ninoWarnings();
$keyed = [ 'a key that is no feature' => 'crm-nothing', 'the key of a feature that is not on' => 'crm-probe-two' ];
$entered = [];

foreach( $keyed as $label => $key )
	$entered[$label] = [ \Nino\Modules\Crm\Db::addSchema( $kpRequest, [ 'package' => $key, 'version' => 1, 'tables' => [ 'crm_probe_items' ], 'file' => $kpFile ] ), count( ninoWarnings() ) ];

check( 'a schema is entered under the key of a feature that is on and under no other: a key that is no feature and the key of a feature that is switched off - the data of a package that is only off would pass for that of one that is removed - are refused with a warning, and nothing is entered',
	$entered === [ 'a key that is no feature' => [ false, 1 ], 'the key of a feature that is not on' => [ false, 1 ] ] && array_keys( \Nino\Modules\Crm\Db::versions( $kpRequest ) ) === [ 'crm' ] );
check( 'the key of a feature that is on is accepted, and the same key with another package\'s tables is refused', \Nino\Features::activate( $kp, 'crm-probe-two' ) === true && ( static function() use ( $kpDir, $kpFile ): bool {

	$request = crmRequest( $kpDir, false );

	return \Nino\Modules\Crm\Db::addSchema( $request, [ 'package' => 'crm-probe-two', 'version' => 1, 'tables' => [ 'crm_two_items' ], 'file' => $kpFile ] ) === true;
} )() === true );

check( 'a schema is read from the folder of the package that enters it and from none other: the file of another package, the file of the core, a file that only lies below the features directory, a file in a folder whose name only begins like the folder of the package and a link in its folder to a file outside it are refused with one warning each, and nothing is entered', ( static function() use ( $kpDir, $features ): bool {

	$request = crmRequest( $kpDir, false );
	$built = [ $features. '/loose-schema.php' => null, $features. '/CrmProbeTwoX/schema.php' => null, $features. '/CrmProbeTwo/link.php' => $features. '/CrmProbe/schema.php' ];
	copy( $features. '/CrmProbe/schema.php', $features. '/loose-schema.php' );
	mkdir( $features. '/CrmProbeTwoX' );
	copy( $features. '/CrmProbe/schema.php', $features. '/CrmProbeTwoX/schema.php' );
	symlink( $features. '/CrmProbe/schema.php', $features. '/CrmProbeTwo/link.php' );
	$wrong = [];
	ninoWarnings();

	foreach( [ 'another package' => $features. '/CrmProbe/schema.php', 'the core' => $features. '/Crm/Db/schema.php', 'beside the packages' => $features. '/loose-schema.php', 'a folder that begins like it' => $features. '/CrmProbeTwoX/schema.php', 'a link out of its folder' => $features. '/CrmProbeTwo/link.php' ] as $what => $file )
		if( \Nino\Modules\Crm\Db::addSchema( $request, [ 'package' => 'crm-probe-two', 'version' => 1, 'tables' => [ 'crm_two_items' ], 'file' => $file ] ) !== false || count( ninoWarnings() ) !== 1 )
			$wrong[] = $what;

	$none = array_keys( \Nino\Modules\Crm\Db::versions( $request ) ) === [ 'crm' ];
	$own = \Nino\Modules\Crm\Db::addSchema( $request, [ 'package' => 'crm-probe-two', 'version' => 1, 'tables' => [ 'crm_two_items' ], 'file' => $features. '/CrmProbeTwo/schema.php' ] ) === true;

	foreach( array_keys( $built ) as $file )
		is_link( $file ) === true || is_file( $file ) === true ? unlink( $file ) : null;

	unlink( $features. '/CrmProbeTwoX/schema.php' );
	rmdir( $features. '/CrmProbeTwoX' );

	return $wrong === [] && $none === true && $own === true;
} )() === true );

// The words of the mails stand below the key of a feature that is on
ninoWarnings();
$roots = [ '/feature/crm-nothing/' => \Nino\Modules\Crm\Mail::addWords( $kpRequest, '/feature/crm-nothing/', [ 'package' => 'crm-nothing' ] ), '/feature/crm-probe/' => \Nino\Modules\Crm\Mail::addWords( $kpRequest, '/feature/crm-probe/', [ 'package' => 'crm-probe' ] ) ];
check( 'the root of the words of the mails of a package is the one of a feature that is on: the root of a key that is no feature is refused with a warning, the one of the package is entered', $roots === [ '/feature/crm-nothing/' => false, '/feature/crm-probe/' => true ] && count( ninoWarnings() ) === 1 );
\Nino\Features::deactivate( $kp, 'crm-probe-two' );
ninoWarnings();
$offRequest2 = crmRequest( $kpDir, false );
$offRoot = \Nino\Modules\Crm\Mail::addWords( $offRequest2, '/feature/crm-probe-two/', [ 'package' => 'crm-probe-two' ] );
check( '...and the root of a feature that is switched off is refused as well', $offRoot === false && count( ninoWarnings() ) === 1 );
crmRemove( $kpDir );

// A file with the mark of the core and no row of the core in the register is not the database of the CRM: the status and the connection say the same, and the file is never touched
$marked = [
	'the mark and one table that is none of the core\'s' => static function( \PDO $pdo ): void { $pdo->exec( 'CREATE TABLE somebody ( a )' ); },
	'the mark and a register with no row of the core' => static function( \PDO $pdo ): void { $pdo->exec( 'CREATE TABLE schema_versions ( package TEXT PRIMARY KEY, version INTEGER NOT NULL, tables TEXT NOT NULL, applied TEXT NOT NULL, folder TEXT NOT NULL DEFAULT \'\' )' ); $pdo->exec( 'INSERT INTO schema_versions VALUES ( \'crm-probe\', 1, \'["crm_probe_items"]\', \'x\', \'CrmProbe\' )' ); },
];

foreach( $marked as $label => $make ) {

	$mkProject = crmSandbox( 'crm6-marked' );
	\Nino\Features::activate( $mkProject, 'crm' );
	$mkDir = ninoSandboxDir( $mkProject );
	$mkFile = $mkDir. '/private/data/crm.sqlite';
	is_dir( dirname( $mkFile ) ) === true || mkdir( dirname( $mkFile ), 0755, true );
	$mkPdo = new \PDO( 'sqlite:'. $mkFile, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
	$mkPdo->exec( 'PRAGMA application_id = '. \Nino\Modules\Crm\Db::APPLICATION_ID );
	$make( $mkPdo );
	$mkPdo = null;
	$mkKept = crmFingerprint( $mkFile );
	$mkRequest = crmRequest( $mkDir );
	$thrown = '';
	ninoWarnings();

	try {
		\Nino\Modules\Crm\Db::pdo( $mkRequest );
	}
	catch( \PDOException $e ) {
		$thrown = $e->getMessage();
	}

	$mkStates = array_column( \Nino\Modules\Crm\Status::lines( $mkRequest ), 'state', 'key' );
	$mkFixed = \Nino\Modules\Crm\Status::fix( $mkRequest, 'database' );
	ninoWarnings();
	check( 'a file that has the mark of the core and no row of the core in the register ('. $label. ') is told as the connection tells it - not the database of the CRM - and not as ok or behind: the database and the schema are foreign, the fix is refused, the orphans are none, and the file is as it was',
		$thrown !== '' && \Nino\Modules\Crm\Db::inspect( $mkRequest )['file'] === 'foreign' && $mkStates['database'] === 'foreign' && $mkStates['schema'] === 'foreign' && $mkFixed === 'failed' && \Nino\Modules\Crm\Db::orphans( $mkRequest ) === [] && crmFingerprint( $mkFile ) === $mkKept );
	crmRemove( $mkDir );
}

// What the connection is told when it is opened: the pragmas the code sets, in their order, and what the connection says it has
[ $pg, $pgDir, $pgRequest ] = crmWorld( 'crm6-pragmas' );
$pgPdo = \Nino\Modules\Crm\Db::pdo( $pgRequest );
check( 'the connection is opened with the wait for a lock, the keys between tables, the journal that is deleted and secure_delete - each set by the code, in this order, and each as the connection says it has it',
	\Nino\Modules\Crm\Db::pragmas( $pgRequest ) === [ 'busy_timeout = 5000', 'foreign_keys = ON', 'journal_mode = DELETE', 'secure_delete = ON' ] && (int) $pgPdo->query( 'PRAGMA busy_timeout' )->fetchColumn() === 5000 && (int) $pgPdo->query( 'PRAGMA foreign_keys' )->fetchColumn() === 1
	&& $pgPdo->query( 'PRAGMA journal_mode' )->fetchColumn() === 'delete' && (int) $pgPdo->query( 'PRAGMA secure_delete' )->fetchColumn() === 1 );
crmRemove( $pgDir );

// What a fix may answer: a failure of any kind is a failure of the fix, and the reason a fix gives is the reason
[ $fx, $fxDir, $fxRequest ] = crmWorld( 'crm6-fixes' );
$fxAnswers = [];

foreach( [ 'throws' => 'throws', 'a reason' => 'reason', 'a text that is no reason' => 'junk' ] as $label => $how ) {

	$fxRequest['./crm-probe/fix'] = $how;
	$fxAnswers[$label] = [ \Nino\Modules\Crm\Status::fix( $fxRequest, 'probe-line' ), count( ninoWarnings() ) ];
}

check( 'a fix that throws - whatever it throws - is the answer failed with one warning that names the line and nothing of the failure; a reason a fix gives as a word is passed on; any other text is failed', $fxAnswers === [ 'throws' => [ 'failed', 1 ], 'a reason' => [ 'disk-full', 0 ], 'a text that is no reason' => [ 'failed', 0 ] ] );
crmRemove( $fxDir );

// The data of a package with keys between its tables, and of a package another package points at
$ghost = static function( string $name, array $packages, array $rows, string $gone ) use ( $features, $source ): array {

	[ $project, $dir, $unused ] = crmWorld( $name );
	\Nino\Features::activate( $project, 'crm-probe-two' );
	ninoWarnings();
	$request = crmRequest( $dir, false );

	foreach( $packages as $key => $package ) {

		$folder = $key === 'crm-probe-two' ? 'CrmProbeTwo' : 'CrmProbe';
		$file = crmSchemaFile( 'schema-ghost-'. $key. '.php', $package['create'], [], $folder );
		\Nino\Modules\Crm\Db::addSchema( $request, [ 'package' => $key, 'version' => 1, 'tables' => $package['tables'], 'file' => $features. '/'. $folder. '/'. $file ] );
	}

	$pdo = \Nino\Modules\Crm\Db::pdo( $request );
	$pdo->exec( 'INSERT INTO clients ( id, number, created, updated ) VALUES ( 1, \'K-1\', \'x\', \'x\' )' );

	foreach( $rows as $row )
		$pdo->exec( $row );

	\Nino\Modules\Crm\Db::close( $request );
	\Nino\Features::deactivate( $project, $gone );
	\Nino\Features::remove( $project, $gone );
	$after = crmRequest( $dir, false );

	return [ $project, $dir, $after ];
};
$finish = static function() use ( $features, $source ): void {
	is_dir( $features. '/CrmProbeTwo' ) === true || crmCopy( $source. '/tests/fixtures/CrmProbeTwo', $features. '/CrmProbeTwo' );
};
$docs = 'CREATE TABLE crm_two_docs ( id INTEGER PRIMARY KEY, client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT, parent_id INTEGER REFERENCES crm_two_docs( id ) ON DELETE RESTRICT )';
$lines = 'CREATE TABLE crm_two_lines ( id INTEGER PRIMARY KEY, doc INTEGER NOT NULL REFERENCES crm_two_docs( id ) ON DELETE RESTRICT )';
$variants = [
	'a table that points at itself, with rows' => [ [ 'crm-probe-two' => [ 'tables' => [ 'crm_two_docs' ], 'create' => [ $docs ] ] ], [ 'INSERT INTO crm_two_docs VALUES ( 1, 1, NULL )', 'INSERT INTO crm_two_docs VALUES ( 2, 1, 1 )', 'INSERT INTO crm_two_docs VALUES ( 3, 1, 2 )' ] ],
	'a child table with rows, the parent listed first' => [ [ 'crm-probe-two' => [ 'tables' => [ 'crm_two_docs', 'crm_two_lines' ], 'create' => [ $docs, $lines ] ] ], [ 'INSERT INTO crm_two_docs VALUES ( 1, 1, NULL )', 'INSERT INTO crm_two_lines VALUES ( 1, 1 )', 'INSERT INTO crm_two_lines VALUES ( 2, 1 )' ] ],
	'a child table with rows, the child listed first' => [ [ 'crm-probe-two' => [ 'tables' => [ 'crm_two_lines', 'crm_two_docs' ], 'create' => [ $docs, $lines ] ] ], [ 'INSERT INTO crm_two_docs VALUES ( 1, 1, NULL )', 'INSERT INTO crm_two_lines VALUES ( 1, 1 )' ] ],
];

foreach( $variants as $label => [ $packages, $rows ] ) {

	[ $gp, $gDir, $gAfter ] = $ghost( 'crm6-ghost', $packages, $rows, 'crm-probe-two' );
	ninoWarnings();
	$gPdo = \Nino\Modules\Crm\Db::pdo( $gAfter );
	$dropped = \Nino\Modules\Crm\Db::dropSchema( $gAfter, 'crm-probe-two' );
	$gLeft = $gPdo->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name LIKE \'crm\_two\_%\' ESCAPE \'\\\'' )->fetchColumn();
	check( 'the data of a package that is gone is dropped whatever the keys between its tables are ('. $label. '): every table, in any order, and its row of the register; the clients of the core are as they were and the keys between tables are on again',
		$dropped === count( $packages['crm-probe-two']['tables'] ) && (int) $gLeft === 0 && $gPdo->query( 'SELECT package FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_COLUMN ) === [ 'crm' ]
		&& (int) $gPdo->query( 'SELECT COUNT(*) FROM clients' )->fetchColumn() === 1 && (int) $gPdo->query( 'PRAGMA foreign_keys' )->fetchColumn() === 1 && ninoWarnings() === [] );
	$finish();
	crmRemove( $gDir );
}

// A package another package points at: nothing is dropped
[ $gp, $gDir, $gAfter ] = $ghost( 'crm6-ghost', [
	'crm-probe-two' => [ 'tables' => [ 'crm_two_docs' ], 'create' => [ $docs ] ],
	'crm-probe' => [ 'tables' => [ 'crm_probe_items' ], 'create' => [ 'CREATE TABLE crm_probe_items ( id INTEGER PRIMARY KEY, doc INTEGER REFERENCES crm_two_docs( id ) ON DELETE RESTRICT )' ] ],
], [ 'INSERT INTO crm_two_docs VALUES ( 1, 1, NULL )', 'INSERT INTO crm_probe_items VALUES ( 1, 1 )' ], 'crm-probe-two' );
ninoWarnings();
$gPdo = \Nino\Modules\Crm\Db::pdo( $gAfter );
$gShape = crmShape( $gPdo );
$gRows = [ $gPdo->query( 'SELECT * FROM crm_two_docs' )->fetchAll(), $gPdo->query( 'SELECT * FROM schema_versions ORDER BY package' )->fetchAll() ];
$dropped = \Nino\Modules\Crm\Db::dropSchema( $gAfter, 'crm-probe-two' );
check( 'the data of a package that another package has a key to is not dropped: dropSchema() says referenced, every table, row and register row is as it was, and the keys between tables are on',
	$dropped === 'referenced' && crmShape( $gPdo ) === $gShape && [ $gPdo->query( 'SELECT * FROM crm_two_docs' )->fetchAll(), $gPdo->query( 'SELECT * FROM schema_versions ORDER BY package' )->fetchAll() ] === $gRows && (int) $gPdo->query( 'PRAGMA foreign_keys' )->fetchColumn() === 1 );
$finish();
crmRemove( $gDir );

// The forms a schema may use: each runs, in the spelling of the day - quoted names, the schema main, other letters, comments, a semicolon at the end - and a step may drop a table the package had
[ $fm, $fmDir, $fmUnused ] = crmWorld( 'crm6-forms' );
$fmEnter = static function( int $version, string $file, array $tables ) use ( $fmDir, $features ): array {

	$request = crmRequest( $fmDir, false );
	\Nino\Modules\Crm\Db::addSchema( $request, [ 'package' => 'crm-probe', 'version' => $version, 'tables' => $tables, 'file' => $features. '/CrmProbe/'. $file ] );

	return $request;
};
$fmFile = crmSchemaFile( 'schema-forms.php', [
	'CREATE TABLE "crm_probe_items" ( id INTEGER PRIMARY KEY, label TEXT, note TEXT )',
	'CREATE TABLE IF NOT EXISTS main.crm_probe_two ( id INTEGER PRIMARY KEY, a TEXT )',
	'CREATE TABLE crm_probe_old ( id INTEGER )',
	'CREATE UNIQUE INDEX IF NOT EXISTS crm_probe_u ON [crm_probe_items] ( label )',
	'CREATE INDEX crm_probe_i ON `crm_probe_two` ( a )',
	'insert or ignore into crm_probe_items ( label ) values ( \'a\' )',
	'REPLACE INTO crm_probe_items ( id, label ) VALUES ( 1, \'b\' )',
	'INSERT OR REPLACE INTO main.crm_probe_two ( id, a ) VALUES ( 1, \'x\' )',
	'UPDATE crm_probe_items SET label = \'c\' WHERE id = 1',
	'UPDATE OR IGNORE "crm_probe_items" SET note = \'n\'',
	'INSERT INTO crm_probe_two ( a ) SELECT number FROM clients',
	"-- a line comment\nDELETE FROM crm_probe_two WHERE a = 'nothing'; ",
	'/* a comment */ INSERT INTO crm_probe_old VALUES ( 1 )',
], [
	2 => [ 'ALTER TABLE crm_probe_items ADD COLUMN extra TEXT', 'ALTER TABLE crm_probe_items RENAME COLUMN extra TO more', 'ALTER TABLE crm_probe_items DROP COLUMN more', 'DROP INDEX IF EXISTS crm_probe_i', 'DROP TABLE crm_probe_old' ],
	3 => [ 'ALTER TABLE crm_probe_two RENAME TO crm_probe_three' ],
] );
ninoWarnings();
$fmOne = $fmEnter( 1, $fmFile, [ 'crm_probe_items', 'crm_probe_two', 'crm_probe_old' ] );
$fmPdo = \Nino\Modules\Crm\Db::pdo( $fmOne );
check( 'every form a schema may use runs: tables and indexes made in every spelling, rows written, replaced, updated and lifted from the clients, a statement behind a comment and one that ends with a semicolon', \Nino\Modules\Crm\Db::failed( $fmOne ) === [] && ninoWarnings() === [] && $fmPdo->query( 'SELECT label, note FROM crm_probe_items' )->fetchAll( \PDO::FETCH_ASSOC ) === [ [ 'label' => 'c', 'note' => 'n' ] ]
	&& (int) $fmPdo->query( 'SELECT COUNT(*) FROM crm_probe_old' )->fetchColumn() === 1 );
$fmTwo = $fmEnter( 3, $fmFile, [ 'crm_probe_items', 'crm_probe_three' ] );
$fmPdo = \Nino\Modules\Crm\Db::pdo( $fmTwo );
check( 'the steps run the same way: a column added, renamed and dropped, an index dropped, a table that the version no longer has dropped, and a table renamed to one the version has', \Nino\Modules\Crm\Db::failed( $fmTwo ) === [] && ninoWarnings() === []
	&& $fmPdo->query( 'SELECT group_concat( name ) FROM sqlite_master WHERE type = \'table\' AND name LIKE \'crm\_probe%\' ESCAPE \'\\\' ORDER BY name' )->fetchColumn() === 'crm_probe_items,crm_probe_three' && array_column( $fmPdo->query( 'PRAGMA table_info( crm_probe_items )' )->fetchAll(), 'name' ) === [ 'id', 'label', 'note' ] );
// A register row that names a table of the core is not trusted: a step does not get to drop it
$fmPdo->exec( 'UPDATE schema_versions SET tables = \'["crm_probe_items","crm_probe_three","clients"]\' WHERE package = \'crm-probe\'' );
$fmEvil = crmSchemaFile( 'schema-forms-evil.php', [ 'CREATE TABLE crm_probe_items ( id INTEGER )' ], [ 4 => [ 'DROP TABLE clients' ] ] );
$fmThree = $fmEnter( 4, $fmEvil, [ 'crm_probe_items', 'crm_probe_three' ] );
$fmPdo = \Nino\Modules\Crm\Db::pdo( $fmThree );
check( 'a step cannot drop a table of the core by a register row that names it: the step is the failure of the package, and the clients are there', \Nino\Modules\Crm\Db::failed( $fmThree ) === [ 'crm-probe' => 4 ] && count( ninoWarnings() ) === 1 && (int) $fmPdo->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'clients\'' )->fetchColumn() === 1 );
crmRemove( $fmDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'A package that fails, and the data a package leaves', $crash );
}


// --- The number sequences --------------------------------------------------------------------------------------------------

echo "\nThe number sequences\n";

try {

[ $sq, $sqDir, $sqRequest ] = crmWorld( 'crm6-sequences' );
$sqPdo = \Nino\Modules\Crm\Db::pdo( $sqRequest );
check( 'the core entered its category, client, and the package of the suite its own: each is the package\'s, in the order they were entered', \Nino\Modules\Crm\Sequences::categories( $sqRequest ) === [ 'client' => 'crm', 'probe-invoice' => 'crm-probe' ] );
check( 'a category of another package is refused with a warning, the same one again is as good as the first time, a slug that is none and a description with a field it has not are refused, and nothing is entered by a refusal',
	\Nino\Modules\Crm\Sequences::add( $sqRequest, [ 'category' => 'client', 'package' => 'crm-probe' ] ) === false && count( ninoWarnings() ) === 1 && \Nino\Modules\Crm\Sequences::init( $sqRequest ) === true
	&& array_filter( [ [ 'category' => 'Client', 'package' => 'crm' ], [ 'category' => 'a b', 'package' => 'crm' ], [ 'category' => 'x\'; DROP TABLE clients; --', 'package' => 'crm' ], [ 'category' => 'ok', 'package' => 'No Package' ], [ 'category' => 'ok' ], [ 'package' => 'crm' ], [ 'category' => 'ok', 'package' => 'crm', 'year' => 0 ], [ 'category' => 7, 'package' => 'crm' ] ], static fn( array $spec ): bool => \Nino\Modules\Crm\Sequences::add( $GLOBALS['sqRequest'], $spec ) !== false ) === []
	&& count( ninoWarnings() ) === 8 && array_keys( \Nino\Modules\Crm\Sequences::categories( $sqRequest ) ) === [ 'client', 'probe-invoice' ] );

$take = static function( string $category, int $year, int $start = 1 ) use ( $sqPdo, &$sqRequest ): int {
	$sqPdo->exec( 'BEGIN IMMEDIATE' );

	try {
		$number = \Nino\Modules\Crm\Sequences::next( $sqRequest, $sqPdo, $category, $year, $start );
		$sqPdo->exec( 'COMMIT' );
	}
	catch( \Throwable $e ) {
		$sqPdo->exec( 'ROLLBACK' );

		throw $e;
	}

	return $number;
};

check( 'the numbers of a category and year begin at 1 and count one by one, and a category and a year count apart', $take( 'client', 0 ) === 1 && $take( 'client', 0 ) === 2 && $take( 'probe-invoice', 2026 ) === 1 && $take( 'probe-invoice', 2027 ) === 1 && $take( 'probe-invoice', 2026 ) === 2 && $take( 'client', 0 ) === 3 );
check( 'upcoming() says the next number and never moves the counter, and a year with no number yet starts at the start number', \Nino\Modules\Crm\Sequences::upcoming( $sqRequest, 'client', 0 ) === 4 && \Nino\Modules\Crm\Sequences::upcoming( $sqRequest, 'client', 0 ) === 4 && \Nino\Modules\Crm\Sequences::upcoming( $sqRequest, 'probe-invoice', 2030, 5 ) === 5
	&& \Nino\Modules\Crm\Sequences::upcoming( $sqRequest, 'probe-invoice', 2026 ) === 3 && $take( 'client', 0 ) === 4 );
check( 'a start number moves the counting forward - a sequence below it is lifted to it, the next number is the start number - and one that lies below what was given changes nothing, so that no number is given twice', $take( 'client', 0, 10 ) === 10 && $take( 'client', 0, 3 ) === 11 && $take( 'probe-invoice', 2031, 100 ) === 100 );
$sqPdo->exec( 'BEGIN IMMEDIATE' );
$spent = \Nino\Modules\Crm\Sequences::next( $sqRequest, $sqPdo, 'client', 0, 1 );
$sqPdo->exec( 'ROLLBACK' );
check( 'a number is spent with what it numbers: a rollback gives it back, and the numbers are gapless', $spent === 12 && $take( 'client', 0 ) === 12 );
$sqPdo->exec( 'BEGIN IMMEDIATE' );
$run = \Nino\Modules\Crm\Db::runner( $sqPdo );
\Nino\Modules\Crm\Sequences::floor( $run, 'client', 0, 50 );
\Nino\Modules\Crm\Sequences::floor( $run, 'client', 0, 20 );
\Nino\Modules\Crm\Sequences::floor( $run, 'probe-invoice', 2040, 7 );
$sqPdo->exec( 'COMMIT' );
check( 'floor() raises the next number of a sequence, makes one that is not there, and never lowers one: a number a deletion spent is never given again', $take( 'client', 0 ) === 50 && $take( 'probe-invoice', 2040 ) === 7 );
check( 'a category that is no slug or that nobody entered and a year below 0 are refused, in every call, as a mistake of the caller - and no counter is made for one', array_filter( [
	static fn() => $GLOBALS['take']( 'Client', 0 ), static fn() => $GLOBALS['take']( 'client\'; DROP TABLE clients; --', 0 ), static fn() => $GLOBALS['take']( 'client', -1 ),
	static fn() => \Nino\Modules\Crm\Sequences::upcoming( $GLOBALS['sqRequest'], 'a b', 0 ), static fn() => \Nino\Modules\Crm\Sequences::floor( $GLOBALS['run'], '', 0, 1 ), static fn() => \Nino\Modules\Crm\Sequences::floor( $GLOBALS['run'], 'client', -3, 1 ),
	static fn() => $GLOBALS['take']( 'nobody-entered', 0 ), static fn() => \Nino\Modules\Crm\Sequences::upcoming( $GLOBALS['sqRequest'], 'nobody-entered', 0 ),
], static function( \Closure $call ): bool { try { $call(); } catch( \InvalidArgumentException ) { return false; } catch( \PDOException ) { return false; } return true; } ) === [] && (int) $sqPdo->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'clients\'' )->fetchColumn() === 1
	&& (int) $sqPdo->query( 'SELECT COUNT(*) FROM sequences WHERE category = \'nobody-entered\'' )->fetchColumn() === 0 );
check( 'the next number of a category with a start number is read through the setting of the category, 1 where it is none', \Nino\Modules\Crm\Settings::start( $sqRequest, 'client' ) === 1 && \Nino\Modules\Crm\Settings::start( $sqRequest, 'project' ) === 1 );

// Several processes take numbers from the same sequence: every number once, none missing
$sqGo = $sqDir. '/go';
$counters = [];

for( $worker = 0; $worker < 4; $worker++ )
	$counters[] = crmStart( 'numbers-worker', $sqDir, '25', $sqGo );

usleep( 300000 );
touch( $sqGo );
$taken = [];

foreach( $counters as $started ) {
	$done = crmFinish( $started );
	$taken = array_merge( $taken, (array) json_decode( $done[1], true ) );
}

sort( $taken );
check( 'four processes take 25 numbers each from one sequence and every number is given once: 51 to 150, none twice and none missing, and the counter stands at 151', $taken === range( 51, 150 ) && (int) $sqPdo->query( 'SELECT next FROM sequences WHERE category = \'client\' AND year = 0' )->fetchColumn() === 151 );
crmRemove( $sqDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The number sequences', $crash );
}


// --- The register behind every add() ---------------------------------------------------------------------------------------

echo "\nThe register behind every add()\n";

try {

$reg = [ './nino/uid' => 'register' ];
$shape = [ 'id' => 'name', 'idRule' => static fn( string $id ): bool => \Nino\Modules\Crm\Register::word( $id ), 'fields' => [ 'level' => \Nino\Modules\Crm\Register::REQUIRED, 'note' => 'none', 'call' => null ],
	'rules' => [ 'level' => static fn( mixed $level ): bool => is_int( $level ) === true && $level > 0, 'call' => static fn( mixed $call ): bool => $call === null || \Nino\Modules\Crm\Register::callable( $reg, $call ) ],
	'refuse' => static fn( array $spec ): string => $spec['note'] === 'no' ? 'note' : '' ];
check( 'a list is entered whole: the entries are in the register with their defaults put in and the order they were entered in', \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'one', 'level' => 1 ], [ 'name' => 'two', 'level' => 2, 'note' => 'x' ] ], $shape ) === true
	&& \Nino\Modules\Crm\Register::all( $reg, './test/entries' ) === [ 'one' => [ 'name' => 'one', 'level' => 1, 'note' => 'none', 'call' => null, 'seq' => 0 ], 'two' => [ 'name' => 'two', 'level' => 2, 'note' => 'x', 'call' => null, 'seq' => 1 ] ] && ninoWarnings() === [] );
check( 'the same description again is as good as the first time and enters nothing new; the same name with another description is refused', \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'one', 'level' => 1 ] ], $shape ) === true && count( \Nino\Modules\Crm\Register::all( $reg, './test/entries' ) ) === 2
	&& \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'one', 'level' => 3 ] ], $shape ) === false && \Nino\Modules\Crm\Register::all( $reg, './test/entries' )['one']['level'] === 1 && count( ninoWarnings() ) === 1 );
check( 'a field the shape says to ignore does not tell two descriptions apart: the same name from another package is the same entry, and one that differs in another field is still refused', ( static function() use ( $shape ): bool {

	$loose = [ 'ignore' => [ 'level' ] ] + $shape;
	$one = [ 'package' => 'x' ];
	$entered = \Nino\Modules\Crm\Register::add( $one, './test/loose', 'tests', [ [ 'name' => 'a', 'level' => 1, 'note' => 'n' ] ], $loose );

	return $entered === true && \Nino\Modules\Crm\Register::add( $one, './test/loose', 'tests', [ [ 'name' => 'a', 'level' => 2, 'note' => 'n' ] ], $loose ) === true
		&& \Nino\Modules\Crm\Register::add( $one, './test/loose', 'tests', [ [ 'name' => 'a', 'level' => 2, 'note' => 'm' ] ], $loose ) === false && ninoWarnings() !== [] && \Nino\Modules\Crm\Register::all( $one, './test/loose' )['a']['level'] === 1;
} )() );
$before = \Nino\Modules\Crm\Register::all( $reg, './test/entries' );
$refused = [];

foreach( [
	'a field the shape has not' => [ 'name' => 'x', 'level' => 1, 'more' => 1 ], 'a field that is required and missing' => [ 'name' => 'x' ], 'a rule that fails' => [ 'name' => 'x', 'level' => 0 ], 'a name that is none' => [ 'name' => 'X', 'level' => 1 ],
	'a name that is no text' => [ 'name' => 5, 'level' => 1 ], 'a name with a quote' => [ 'name' => 'x\'y', 'level' => 1 ], 'a rule over the whole description' => [ 'name' => 'x', 'level' => 1, 'note' => 'no' ], 'a callable that is a closure' => [ 'name' => 'x', 'level' => 1, 'call' => static fn() => 1 ],
	'a callable that is a string' => [ 'name' => 'x', 'level' => 1, 'call' => 'strlen' ], 'a callable that is no method' => [ 'name' => 'x', 'level' => 1, 'call' => [ '\\Nino\\Modules\\Crm\\Status', 'nothing' ] ], 'a class that is none' => [ 'name' => 'x', 'level' => 1, 'call' => [ 'No\\Such\\Class', 'x' ] ],
	'a class with a quote' => [ 'name' => 'x', 'level' => 1, 'call' => [ 'A\'B', 'x' ] ], 'a description that is a list' => [ 'x', 1 ], 'a description that is empty' => [], 'a description that is no list' => 'x',
] as $label => $spec )
	if( \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ $spec ], $shape ) !== false || count( ninoWarnings() ) !== 1 )
		$refused[] = $label;

check( 'a description with a field the shape has not, one that lacks a required field, a rule that fails, a name that is none, a rule over the whole, a callable that is none, a description that is no description is refused, each with one warning', $refused === [] );
check( 'a refusal in a list enters none of it: the good entry before the bad one is not in the register, and the register is as it was', \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'good', 'level' => 1 ], [ 'name' => 'bad', 'level' => 0 ] ], $shape ) === false && ninoWarnings() !== [] && \Nino\Modules\Crm\Register::all( $reg, './test/entries' ) === $before );
check( 'two descriptions of one name in one list that differ are refused, and two that are the same are one entry', \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'twin', 'level' => 1 ], [ 'name' => 'twin', 'level' => 2 ] ], $shape ) === false && ninoWarnings() !== []
	&& \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'twin', 'level' => 1 ], [ 'name' => 'twin', 'level' => 1 ] ], $shape ) === true && count( \Nino\Modules\Crm\Register::all( $reg, './test/entries' ) ) === 3 );
check( 'a warning of a refusal names the register and the field and never a value of the description', ( static function() use ( &$reg, $shape ): bool { \Nino\Modules\Crm\Register::add( $reg, './test/entries', 'tests', [ [ 'name' => 'secret-name-9', 'level' => 'secret-level-9' ] ], $shape ); $message = ninoWarnings()[0] ?? ''; return str_contains( $message, 'level' ) === true && str_contains( $message, 'secret' ) === false && str_contains( $message, 'tests' ) === true; } )() === true );
check( 'callable() takes [ class, method ] as two strings that php can call and nothing else; word() takes lower case words joined by one joiner, from a letter, no longer than the most', \Nino\Modules\Crm\Register::callable( $reg, [ '\\Nino\\Modules\\Crm\\Status', 'database' ] ) === true && \Nino\Modules\Crm\Register::callable( $reg, [ 'Nino\\Modules\\Crm\\Status', 'database' ] ) === true
	&& \Nino\Modules\Crm\Register::callable( $reg, 'strlen' ) === false && \Nino\Modules\Crm\Register::callable( $reg, [ new \stdClass(), 'x' ] ) === false && \Nino\Modules\Crm\Register::callable( $reg, [ '\\Nino\\Modules\\Crm\\Status' ] ) === false && \Nino\Modules\Crm\Register::callable( $reg, [ 'a' => '\\Nino\\Modules\\Crm\\Status', 'b' => 'database' ] ) === false
	&& \Nino\Modules\Crm\Register::word( 'a-b-c1' ) === true && \Nino\Modules\Crm\Register::word( 'a--b' ) === false && \Nino\Modules\Crm\Register::word( '1a' ) === false && \Nino\Modules\Crm\Register::word( 'a_b' ) === false && \Nino\Modules\Crm\Register::word( 'a_b', 40, '_' ) === true
	&& \Nino\Modules\Crm\Register::word( str_repeat( 'a', 41 ) ) === false && \Nino\Modules\Crm\Register::word( "a\n" ) === false && \Nino\Modules\Crm\Register::word( null ) === false && \Nino\Modules\Crm\Register::word( 7 ) === false );
check( 'no class of the feature keeps state in a static field: the registers, the connections and the answers of a request are in the app data', ( static function() use ( $dir ): bool {

	$found = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $item ) {

		if( str_ends_with( (string) $item, '.php' ) === false )
			continue;

		$tokens = token_get_all( (string) file_get_contents( (string) $item ) );

		foreach( $tokens as $at => $token )
			if( is_array( $token ) === true && $token[0] === T_STATIC )
				for( $next = $at + 1; $next < count( $tokens ); $next++ ) {

					// A static function, a static closure and static:: end the look; a static property or a static variable names a variable before it ends
					if( $tokens[$next] === '(' || $tokens[$next] === ';' || $tokens[$next] === '=' || ( is_array( $tokens[$next] ) === true && in_array( $tokens[$next][0], [ T_FUNCTION, T_FN, T_DOUBLE_COLON ], true ) === true ) )
						break;

					if( is_array( $tokens[$next] ) === true && $tokens[$next][0] === T_VARIABLE ) {
						$found[] = basename( (string) $item ). ':'. $tokens[$next][1];
						break;
					}
				}
	}

	return $found === [];
} )() === true );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The register behind every add()', $crash );
}


// --- The settings ----------------------------------------------------------------------------------------------------------

echo "\nThe settings\n";

try {

[ $st, $stDir, $stRequest ] = crmWorld( 'crm6-settings' );
$stPdo = \Nino\Modules\Crm\Db::pdo( $stRequest );
$core = [ 'start_client', 'start_project', 'seller_name', 'brand_word', 'brand_domain', 'brand_color', 'brand_ink', 'crm_host', 'app_host' ];

check( 'the keys of the core are the nine of the class, with their defaults, and no key of another feature - the notices, the consent texts, the privacy text - is among them', array_keys( \Nino\Modules\Crm\Settings::DEFAULTS ) === $core && array_filter( \Nino\Modules\Crm\Settings::DEFAULTS, static fn( string $default, string $key ): bool => $default !== ( $key === 'start_client' || $key === 'start_project' ? '1' : '' ), ARRAY_FILTER_USE_BOTH ) === [] );
check( 'the keys of a package stand in front of the key of the core they name, by rank, and the others at the end; the settings are all read by one SELECT and have the defaults where the table has no row',
	array_keys( \Nino\Modules\Crm\Settings::all( $stRequest ) ) === [ 'start_client', 'start_project', 'probe_limit', 'seller_name', 'brand_word', 'brand_domain', 'brand_color', 'brand_ink', 'crm_host', 'app_host', 'probe_host' ] && \Nino\Modules\Crm\Settings::get( $stRequest, 'probe_limit' ) === '5' && \Nino\Modules\Crm\Settings::get( $stRequest, 'start_client' ) === '1' );
check( 'the host keys are crm_host and the ones a package entered with host, the crm_host first', \Nino\Modules\Crm\Settings::hostKeys( $stRequest ) === [ 'crm_host', 'probe_host' ] && \Nino\Modules\Crm\Settings::refusal( $stRequest, 'probe_limit' ) === 'crm_probe_limit' && \Nino\Modules\Crm\Settings::refusal( $stRequest, 'brand_color' ) === 'crm_brand_color'
	&& \Nino\Modules\Crm\Settings::refusal( $stRequest, 'brand_ink' ) === 'crm_brand_color' && \Nino\Modules\Crm\Settings::refusal( $stRequest, 'seller_name' ) === '' && \Nino\Modules\Crm\Settings::refusal( $stRequest, 'brand_word' ) === 'crm_brand_word' && \Nino\Modules\Crm\Settings::refusal( $stRequest, 'brand_domain' ) === 'crm_brand_domain' );
check( 'a key nobody entered is none: get() throws, set() says unknown, all() holds no such key, and a key of a package is its own', ( static function() use ( $stRequest ): bool { try { \Nino\Modules\Crm\Settings::get( $stRequest, 'nothing' ); return false; } catch( \InvalidArgumentException ) { return \Nino\Modules\Crm\Settings::set( $stRequest, 'nothing', '1' ) === 'unknown' && \Nino\Modules\Crm\Settings::known( $stRequest, 'nothing' ) === false && array_key_exists( 'nothing', \Nino\Modules\Crm\Settings::all( $stRequest ) ) === false; } catch( \Throwable ) { return false; } } )() === true );

$badKeys = [
	'a key of the core' => [ 'package' => 'crm-probe', 'key' => 'seller_name', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], 'a key in capitals' => [ 'package' => 'crm-probe', 'key' => 'Mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ],
	'a key with a quote' => [ 'package' => 'crm-probe', 'key' => 'x\'; DROP TABLE settings; --', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], 'a key of 41 characters' => [ 'package' => 'crm-probe', 'key' => str_repeat( 'a', 41 ), 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ],
	'a key with two underscores' => [ 'package' => 'crm-probe', 'key' => 'a__b', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], 'a key that is no text' => [ 'package' => 'crm-probe', 'key' => 3, 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ],
	'a default that is a number' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => 5, 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], 'a default that is a list' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => [], 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ],
	'no default' => [ 'package' => 'crm-probe', 'key' => 'mine', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], 'no rule for a key that a post may write' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '' ], 'a rule that is a closure' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => static fn() => '' ],
	'a rule that is no method' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'nothing' ] ], 'a flag that is text' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'host' => 'yes' ],
	'a read-only flag that is a number' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'read_only' => 1 ], 'a written that is no method' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'written' => [ 'Nope', 'nope' ] ],
	'a refusal with capitals' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'refusal' => 'Crm Bad' ], 'a before that is no name of a key' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'before' => 'Probe Limit' ], 'a before that is the key itself' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'before' => 'mine' ], 'a before that is a list' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'before' => [ 'seller_name' ] ],
	'a rank that is text' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'rank' => '5' ], 'a field the description has not' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ], 'secret' => true ],
	'a key another package has, with another rule' => [ 'package' => 'crm-probe', 'key' => 'probe_limit', 'default' => '9', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], 'a key that is a type of another feature' => [ 'package' => 'crm-probe', 'key' => 'mine', 'default' => '', 'clean' => 'strtoupper' ],
];
$refused = [];

foreach( $badKeys as $label => $spec )
	if( \Nino\Modules\Crm\Settings::add( $stRequest, [ $spec ] ) !== false || count( ninoWarnings() ) !== 1 )
		$refused[] = $label;

check( 'a key of the core or of another package, a key that is no key, a default that is no text, a rule that is none, a flag that is no flag, a field the description has not and a type of another feature are each refused with one warning', $refused === [] && count( $badKeys ) === 23 );
check( 'a list that has one bad key enters none of it; the same list again, after the good one, is as good as the first time, and the keys of the request are as they were', \Nino\Modules\Crm\Settings::add( $stRequest, [ [ 'package' => 'crm-probe', 'key' => 'fine_one', 'default' => '', 'clean' => [ \Nino\Modules\Crm\Settings::class, 'host' ] ], $badKeys['a key of the core'] ] ) === false && ninoWarnings() !== [] && \Nino\Modules\Crm\Settings::known( $stRequest, 'fine_one' ) === false
	&& array_keys( \Nino\Modules\Crm\Settings::all( $stRequest ) ) === [ 'start_client', 'start_project', 'probe_limit', 'seller_name', 'brand_word', 'brand_domain', 'brand_color', 'brand_ink', 'crm_host', 'app_host', 'probe_host' ] );

$rules = [
	'start_client' => [ [ '5', '5' ], [ ' 7 ', '7' ], [ '007', '7' ], [ 12, '12' ], [ '9999999', '9999999' ], [ '0', null ], [ '-1', null ], [ '10000000', null ], [ '1.5', null ], [ 'abc', null ], [ '', null ], [ '5; DROP TABLE settings', null ], [ [ '5' ], null ], [ null, null ], [ 5.0, null ] ],
	'start_project' => [ [ '1', '1' ], [ '0', null ] ],
	'seller_name' => [ [ 'Studio Nord', 'Studio Nord' ], [ '', '' ], [ "  Spaced  ", 'Spaced' ], [ "Two\nLines", null ], [ "Tab\there", null ], [ "Null\0byte", null ], [ str_repeat( 'a', 120 ), str_repeat( 'a', 120 ) ], [ str_repeat( 'a', 121 ), null ], [ "Bidi \u{202E}x", null ], [ '<b>[csrf]</b>', '<b>[csrf]</b>' ] ],
	'brand_word' => [ [ 'Acme', 'Acme' ], [ str_repeat( 'a', 24 ), str_repeat( 'a', 24 ) ], [ str_repeat( 'a', 25 ), null ], [ "a\nb", null ], [ "a\u{2028}b", null ], [ "\u{3000}Acme\u{3000}", 'Acme' ] ],
	'brand_domain' => [ [ str_repeat( 'a', 40 ), str_repeat( 'a', 40 ) ], [ str_repeat( 'a', 41 ), null ] ],
	'brand_color' => [ [ '#aabbcc', '#AABBCC' ], [ '#AABBCC', '#AABBCC' ], [ '', '' ], [ '#abc', null ], [ 'aabbcc', null ], [ '#aabbccd', null ], [ '#gggggg', null ], [ 'red', null ], [ "#aabbcc\n", '#AABBCC' ] ],
	'brand_ink' => [ [ '#112233', '#112233' ], [ '#12', null ] ],
	'crm_host' => [ [ 'crm.example.org', 'crm.example.org' ], [ 'CRM.Example.ORG', 'crm.example.org' ], [ '', '' ], [ 'localhost', null ], [ 'crm', null ], [ 'http://crm.example.org', null ], [ 'crm.example.org/path', null ], [ 'crm.example.org:8080', null ], [ 'crm.example.org.', null ], [ '192.168.1.1', null ],
		[ 'bücher.example', null ], [ 'xn--bcher-kva.example', 'xn--bcher-kva.example' ], [ "crm.example.org\0.evil.test", null ], [ 'crm example.org', null ], [ '-crm.example.org', null ], [ 'crm.example.123', null ], [ 'crm..example.org', null ], [ str_repeat( 'a', 64 ). '.example.org', null ] ],
];
$wrong = [];

foreach( $rules as $key => $cases )
	foreach( $cases as [ $value, $expected ] ) {

		$answer = \Nino\Modules\Crm\Settings::clean( $stRequest, $key, $value );

		if( ( $expected === null ? ( is_array( $answer ) === true && $answer === [ 'value:'. $key ] ) : $answer === $expected ) === false )
			$wrong[] = $key. ' '. json_encode( $value ). ' => '. json_encode( $answer );
	}

check( 'every rule of the core holds: whole numbers from 1 with seven digits at most, one line of plain text without a control character, a mark that turns the text round or a null byte, colours as #rrggbb in capitals, hosts as lower case names with two labels - no scheme, port, path, dot at the end, address of numbers, null byte, blank or letter outside ASCII', $wrong === [] );
check( 'the rule of a key of a package is its own - what it answers that is no text is the code of a value that fails - and a key it keeps itself is written by no post', \Nino\Modules\Crm\Settings::clean( $stRequest, 'probe_limit', '12' ) === '12' && \Nino\Modules\Crm\Settings::clean( $stRequest, 'probe_limit', 'x' ) === [ 'value:probe_limit' ]
	&& \Nino\Modules\Crm\Settings::clean( $stRequest, 'probe_limit', 12.5 ) === [ 'value:probe_limit' ] && \Nino\Modules\Crm\Settings::clean( $stRequest, 'nothing', 'x' ) === [ 'unknown' ] );
check( 'the rules a package shares with the core are the core\'s: note() keeps line breaks and tabs and refuses other control characters and marks that turn the text, flag() is 0 or 1, oneOf() is one of the words', \Nino\Modules\Crm\Settings::note( 'k', "A\nB\tC" ) === "A\nB\tC" && \Nino\Modules\Crm\Settings::note( 'k', "A\x01" ) === [ 'value:k' ] && \Nino\Modules\Crm\Settings::note( 'k', str_repeat( 'a', 2001 ) ) === [ 'value:k' ]
	&& \Nino\Modules\Crm\Settings::note( 'k', "a\u{202E}" ) === [ 'value:k' ] && \Nino\Modules\Crm\Settings::flag( 'k', '1' ) === '1' && \Nino\Modules\Crm\Settings::flag( 'k', '2' ) === [ 'value:k' ] && \Nino\Modules\Crm\Settings::oneOf( 'k', 'b', [ 'a', 'b' ] ) === 'b' && \Nino\Modules\Crm\Settings::oneOf( 'k', 'B', [ 'a', 'b' ] ) === [ 'value:k' ] );

// Writing: the value that passes, the writes that are told, and values that are meant to harm
$told = [];
\Nino\Callbacks::registerCallback( $stRequest, '/crm/settings/changed', static function( array &$appData, array &$what ) use ( &$told ): void { $told[] = $what; } );
$written = \Nino\Modules\Crm\Settings::set( $stRequest, 'seller_name', '  Studio Nord ' );
$reread = crmRequest( $stDir );
check( 'a value that passes is written and read back, in the request that wrote it and in another, and the hook is told the key and the clean value once', $written === true && \Nino\Modules\Crm\Settings::get( $stRequest, 'seller_name' ) === 'Studio Nord' && $told === [ [ 'key' => 'seller_name', 'value' => 'Studio Nord' ] ]
	&& \Nino\Modules\Crm\Settings::get( $reread, 'seller_name' ) === 'Studio Nord' && \Nino\Modules\Crm\Settings::readOnly( $stRequest, 'seller_name' ) === 'Studio Nord' );
$told = [];
$stored = [ \Nino\Modules\Crm\Settings::set( $stRequest, 'start_client', '0' ), \Nino\Modules\Crm\Settings::set( $stRequest, 'seller_name', "x\ny" ), \Nino\Modules\Crm\Settings::set( $stRequest, 'nothing', 'x' ), \Nino\Modules\Crm\Settings::set( $stRequest, 'crm_host', 'a b' ), \Nino\Modules\Crm\Settings::set( $stRequest, 'seller_name', [ 'x' ] ) ];
check( 'a value that fails is refused with the code of its key, a key nobody entered with unknown, and nothing is written or told', $stored === [ 'value:start_client', 'value:seller_name', 'unknown', 'value:crm_host', 'value:seller_name' ] && $told === [] && \Nino\Modules\Crm\Settings::get( $stRequest, 'seller_name' ) === 'Studio Nord' && \Nino\Modules\Crm\Settings::get( $stRequest, 'start_client' ) === '1' );
$hostile = [ 'seller_name' => "'); DROP TABLE settings; --", 'brand_word' => '" OR 1=1 --', 'brand_domain' => 'a\' || (SELECT 1) || \'' ];

foreach( $hostile as $key => $value )
	\Nino\Modules\Crm\Settings::set( $stRequest, $key, $value );

$reread = crmRequest( $stDir );
check( 'values and keys that are SQL are stored as the text they are: the table of the settings is there with the rows it had, and the values are read back as typed - the statements are prepared, with parameters', $stPdo->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name IN ( \'settings\', \'clients\' )' )->fetchColumn() === 2
	&& \Nino\Modules\Crm\Settings::get( $reread, 'seller_name' ) === "'); DROP TABLE settings; --" && \Nino\Modules\Crm\Settings::get( $reread, 'brand_word' ) === '" OR 1=1 --' && \Nino\Modules\Crm\Settings::get( $reread, 'brand_domain' ) === 'a\' || (SELECT 1) || \'' );
check( 'a null byte and a control character in a value are refused wherever they stand, and the setting stays what it was', ( static function() use ( $stRequest ): bool {
	$was = \Nino\Modules\Crm\Settings::get( $stRequest, 'seller_name' );

	return array_filter( [ "a\0b", "\0", "a\x1fb", "a\x7fb", "\u{0085}" ], static fn( string $value ): bool => \Nino\Modules\Crm\Settings::set( $GLOBALS['stRequest'], 'seller_name', $value ) !== 'value:seller_name' ) === [] && \Nino\Modules\Crm\Settings::get( $stRequest, 'seller_name' ) === $was;
} )() === true );

// What a key of a package says to be done after it was written, and whether the value changed
$stRequest['./crm-probe/written'] = [];
\Nino\Modules\Crm\Settings::set( $stRequest, 'probe_host', 'probe.example.org' );
\Nino\Modules\Crm\Settings::set( $stRequest, 'probe_host', 'probe.example.org' );
\Nino\Modules\Crm\Settings::set( $stRequest, 'probe_host', '' );
check( 'the callable of a key of a package is called after each write, with the clean value and whether it differs from the one before', $stRequest['./crm-probe/written'] === [ [ 'probe.example.org', true ], [ 'probe.example.org', false ], [ '', true ] ] );

// Hosts that may not be taken: the host of the website, another host key's
\Nino\Html::addFills( $stRequest, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
check( 'a host may be neither the website\'s nor the value of another host key; the workbench may be where the CRM is and no other two may share a name',
	\Nino\Modules\Crm\Settings::set( $stRequest, 'crm_host', 'www.example.org' ) === 'value:crm_host' && \Nino\Modules\Crm\Settings::set( $stRequest, 'app_host', 'www.example.org' ) === true && \Nino\Modules\Crm\Settings::set( $stRequest, 'crm_host', 'crm.example.org' ) === true
	&& \Nino\Modules\Crm\Settings::set( $stRequest, 'app_host', 'crm.example.org' ) === true && \Nino\Modules\Crm\Settings::set( $stRequest, 'probe_host', 'crm.example.org' ) === 'value:probe_host' && \Nino\Modules\Crm\Settings::set( $stRequest, 'probe_host', 'WWW.example.org' ) === 'value:probe_host'
	&& \Nino\Modules\Crm\Settings::mayShare( 'crm_host', 'app_host' ) === true && \Nino\Modules\Crm\Settings::mayShare( 'crm_host', 'probe_host' ) === false && \Nino\Modules\Crm\Settings::isSiteHost( $stRequest, 'WWW.example.org' ) === false && \Nino\Modules\Crm\Settings::isSiteHost( $stRequest, 'www.example.org' ) === true
	&& \Nino\Modules\Crm\Settings::isSiteHost( $stRequest, '' ) === false );

// What the packages show of themselves in the view of the settings: a part under a key that is a slug and is a list, and nothing else
check( 'view() asks the hook and keeps the parts that are lists under a key that is a slug: the part of the package of the suite is in it, the key in capitals and the part that is text are left out', \Nino\Modules\Crm\Settings::view( $stRequest ) === [ 'packages' => [ 'crm-probe' => [ 'next' => 7 ] ] ] );
check( 'the owner is called by the setting where it has one, else by the name of the company, the author or the address of the site - one line, no control character, no null byte, no mark that turns the text round, at most 120 characters',
	( static function() use ( $stRequest ): bool {
		\Nino\Modules\Crm\Settings::set( $stRequest, 'seller_name', '' );
		\Nino\Html::addFills( $stRequest, [ '[[/project/company/general/name]]' => "Firma\0 &amp; [csrf]\n\u{202E}Co" ], '*' );
		$fromCompany = \Nino\Modules\Crm\Settings::ownerName( $stRequest );
		$sameSite = \Nino\Modules\Crm::siteName( $stRequest ) === $fromCompany;
		// The name of the site and the name of the owner follow one rule: a null byte is a control character in both, and the same name is the same in both places
		\Nino\Html::addFills( $stRequest, [ '[[/project/company/general/name]]' => "Studio\0Nord" ], '*' );
		$nul = [ \Nino\Modules\Crm\Settings::ownerName( $stRequest ), \Nino\Modules\Crm::siteName( $stRequest ) ];
		\Nino\Modules\Crm\Settings::set( $stRequest, 'seller_name', 'Studio' );
		unset( $stRequest['./crm/settings/reader'] );

		return $fromCompany === 'Firma & [csrf] Co' && $sameSite === true && $nul === [ 'Studio Nord', 'Studio Nord' ] && \Nino\Modules\Crm\Settings::ownerName( $stRequest ) === 'Studio';
	} )() === true );
// A reader that only reads: a database that another connection holds locked is not waited for twice in a request, and a file that is not the core's is no file to read
$lockedRead = crmRequest( $stDir );
$holder = crmHold( $stDir. '/private/data/crm.sqlite' );
$began = microtime( true );
$busy = \Nino\Modules\Crm\Settings::readOnly( $lockedRead, 'seller_name' );
$waited = microtime( true ) - $began;
crmRelease( $holder );
$retried = \Nino\Modules\Crm\Settings::readOnly( $lockedRead, 'seller_name' );
$next = crmRequest( $stDir );
check( 'a read that only reads waits for a locked database for the wait it has, answers nothing, is not tried again in the same request - the answer stays nothing though the lock is gone - and the next request reads the value', $busy === '' && $retried === '' && \Nino\Modules\Crm\Settings::readOnly( $next, 'seller_name' ) !== '' );
$strange = crmSandbox( 'crm6-strange' );
\Nino\Features::activate( $strange, 'crm' );
$strangeDir = ninoSandboxDir( $strange );
is_dir( $strangeDir. '/private/data' ) === true || mkdir( $strangeDir. '/private/data', 0755, true );
$foreignDb = new \PDO( 'sqlite:'. $strangeDir. '/private/data/crm.sqlite' );
$foreignDb->exec( 'CREATE TABLE settings ( key TEXT, value TEXT )' );
$foreignDb->exec( 'INSERT INTO settings VALUES ( \'seller_name\', \'Foreign\' )' );
$foreignDb = null;
$strangeRequest = crmRequest( $strangeDir );
$strangeKept = crmFingerprint( $strangeDir. '/private/data/crm.sqlite' );
\Nino\Html::addFills( $strangeRequest, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
$strangeBand = \Nino\Html::renderHtml( $strangeRequest, '<i>[mail-head]</i>' );
check( 'a file that is not the core\'s is no file to read a setting from: the owner is not named by what a foreign table says, and the band of a mail is drawn without a word of it - no failure - and the file is not touched', \Nino\Modules\Crm\Settings::readOnly( $strangeRequest, 'seller_name' ) === '' && $strangeBand === '<i></i>' && crmFingerprint( $strangeDir. '/private/data/crm.sqlite' ) === $strangeKept );

}
catch( \Throwable $crash ) {
	crmCrashed( 'The settings', $crash );
}


// --- The hosts ---------------------------------------------------------------------------------------------------------------

echo "\nThe hosts\n";

try {

[ $hs, $hsDir, $hsRequest ] = crmWorld( 'crm6-hosts' );
\Nino\Html::addFills( $hsRequest, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );


$turned = static fn( array $request ): string => (string) $request['/nino/http/request']['uri'];
$names = [
	'crm.example.org' => 'crm.example.org', 'CRM.Example.ORG' => 'crm.example.org', 'crm.example.org:8443' => 'crm.example.org', 'crm.example.org.' => 'crm.example.org', 'Crm.Example.Org.:80' => 'crm.example.org', '  crm.example.org  ' => 'crm.example.org',
	'[::1]' => '[::1]', '[::1]:8080' => '[::1]:8080', '' => '', 'crm.example.org:' => 'crm.example.org', "crm.example.org\0.evil.test" => "crm.example.org\0.evil.test", 'bücher.example' => 'bücher.example',
];
$wrong = [];

foreach( $names as $header => $expected )
	if( \Nino\Modules\Crm\Hosts::normalise( (string) $header ) !== $expected )
		$wrong[] = $header;

check( 'the Host header is compared in lower case, without the port and without the dot of a name written complete; a null byte, a name in another alphabet and an address in brackets are left as they are, and so match no name of a setting', $wrong === [] );
check( 'a path is read as a web server reads it - lower case, percent-decoded once, dot segments resolved, every run of slashes one - and is the CRM\'s, the workbench\'s or neither',
	array_map( [ \Nino\Modules\Crm\Hosts::class, 'path' ], [ '/CRM', '/%63rm', '/crm/../_admin', '//crm//x/./y', '/a/../../b', '', '/crm%2f..' ] ) === [ '/crm', '/crm', '/_admin', '/crm/x/y', '/b', '/', '/' ]
	&& array_map( [ \Nino\Modules\Crm\Hosts::class, 'surface' ], [ '/crm', '/CRM/api', '/.nino/login', '/_admin', '/_ADMIN/x', '/%5fadmin', '/crm/../_admin', '/crmx', '/crm.evil', '/', '/page', '/.nino', '/_administrator' ] ) === [ true, true, true, true, true, true, true, false, false, false, false, true, true ]
	&& array_map( [ \Nino\Modules\Crm\Hosts::class, 'page' ], [ '/crm', '/crm/', '/crm/todo', '/CRM', '/crmx', '/_admin', '/crm/../x' ] ) === [ true, true, true, true, false, false, false ] && array_map( [ \Nino\Modules\Crm\Hosts::class, 'bench' ], [ '/_admin', '/_admin/x', '/crm', '/x/_admin' ] ) === [ true, true, false, false ] );
check( 'the module registers the two routes and the callbacks it owns, and nothing is read from the disk to do it', ( $hsRequest['/nino/http/routes']['GET://.crm/place']['uri'] ?? '' ) === '/.crm/place' && ( $hsRequest['/nino/http/routes']['GET://.crm/probe']['uri'] ?? '' ) === '/.crm/probe'
	&& \Nino\Modules\Crm\Hosts::ROUTE === '/.crm/place' && \Nino\Modules\Crm\Hosts::PROBE === '/.crm/probe' );

// With no name set, nothing is turned away: every place answers on every name
foreach( [ [ 'www.example.org', '/' ], [ 'crm.example.org', '/crm' ], [ 'anything.test', '/_admin' ], [ '', '/crm' ], [ 'www.example.org', '/crm/api' ] ] as [ $host, $uri ] )
	$untouched[] = $turned( crmVisit( $hsRequest, $host, $uri ) ) === $uri;

check( 'where neither name is set no request is turned: every place answers on every name, the CRM on the website\'s too', array_filter( $untouched, static fn( bool $same ): bool => $same === false ) === [] && \Nino\Modules\Crm\Hosts::crm( $hsRequest ) === '' && \Nino\Modules\Crm\Hosts::app( $hsRequest ) === '' );

\Nino\Modules\Crm\Settings::set( $hsRequest, 'crm_host', 'crm.example.org' );
$ROUTE = \Nino\Modules\Crm\Hosts::ROUTE;
$cases = [
	'the CRM host asks for its start page, which is the CRM' => [ 'crm.example.org', '/', '/crm' ],
	'...in capitals, with a port' => [ 'CRM.Example.ORG:8443', '/', '/crm' ],
	'...with a dot at the end' => [ 'crm.example.org.', '/', '/crm' ],
	'the CRM host asks for a place of the owner' => [ 'crm.example.org', '/crm/api', '/crm/api' ],
	'...and for the workbench, which is here where the app has no name of its own' => [ 'crm.example.org', '/_admin', '/_admin' ],
	'...and for the sign-in of the kernel' => [ 'crm.example.org', '/.nino/login', '/.nino/login' ],
	'the CRM host asks for a page of the website, which is turned away' => [ 'crm.example.org', '/contact', $ROUTE ],
	'...and for the same page in another spelling' => [ 'crm.example.org', '/%63ontact', $ROUTE ],
	'the CRM host asks for a path that every host answers' => [ 'crm.example.org', '/.probe/zip', '/.probe/zip' ],
	'...or a path below one' => [ 'crm.example.org', '/.probe/files/x/../y', '/.probe/files/x/../y' ],
	'...but not one that climbs out of it' => [ 'crm.example.org', '/.probe/files/../contact', $ROUTE ],
	'the website asks for a place of the owner, which is turned away' => [ 'www.example.org', '/crm', $ROUTE ],
	'...in capitals' => [ 'www.example.org', '/CRM', $ROUTE ],
	'...in another spelling' => [ 'www.example.org', '/%63rm/api', $ROUTE ],
	'...with dot segments' => [ 'www.example.org', '/page/../crm', $ROUTE ],
	'...with slashes' => [ 'www.example.org', '//crm', $ROUTE ],
	'...the workbench, which the CRM host owns where the app has no name' => [ 'www.example.org', '/_admin', $ROUTE ],
	'...the workbench in capitals' => [ 'www.example.org', '/_ADMIN/x', $ROUTE ],
	'the website asks for its own page, which is none of the owner\'s' => [ 'www.example.org', '/contact', '/contact' ],
	'...and for its start page, which is not mapped to the CRM' => [ 'www.example.org', '/', '/' ],
	'...and for a path that looks like the CRM and is none' => [ 'www.example.org', '/crmx', '/crmx' ],
	'a name nobody owns asks for a place of the owner' => [ 'evil.test', '/crm', $ROUTE ],
	'...and for the start page' => [ 'evil.test', '/', '/' ],
	'a Host header with a null byte after the name of the CRM is not the CRM' => [ "crm.example.org\0.evil.test", '/', '/' ],
	'...and asks for a place of the owner in vain' => [ "crm.example.org\0.evil.test", '/crm', $ROUTE ],
	'a name in another alphabet that looks like the CRM host is not it' => [ 'crm.exаmple.org', '/crm', $ROUTE ],
	'a request with no Host header fails closed for a place of the owner' => [ '', '/crm', $ROUTE ],
	'...and leaves the website alone' => [ '', '/contact', '/contact' ],
];
$wrong = [];

foreach( $cases as $label => [ $host, $uri, $expected ] )
	if( $turned( crmVisit( $hsRequest, $host, $uri ) ) !== $expected )
		$wrong[] = $label;

\Nino\Modules\Crm\Hosts::share( $hsRequest, [ '/.probe/files/' ], [ '/.probe/zip' ], [ 'package' => 'crm-probe' ] );

check( 'with the CRM host set the places of the owner answer on it, its start page is the CRM, the website answers its own paths, and a request that is for another place is turned into the route that answers 404 - in every spelling, with null bytes, capitals, dots and slashes', $wrong === [] || ( function() use ( $wrong ) { fwrite( STDERR, json_encode( $wrong ). "\n" ); return false; } )() );

// The workbench has a name of its own: it answers there alone, the CRM wherever its host puts it, and the old address of the workbench on the CRM host is sent to the new one
\Nino\Modules\Crm\Settings::set( $hsRequest, 'app_host', 'app.example.org' );
$cases = [
	'the app host asks for the workbench' => [ 'app.example.org', '/_admin', '/_admin' ],
	'...and for the sign-in of the kernel' => [ 'app.example.org', '/.nino/login', '/.nino/login' ],
	'...and for the CRM, which the CRM host owns, so it is turned away' => [ 'app.example.org', '/crm', $ROUTE ],
	'the CRM host asks for its start page' => [ 'crm.example.org', '/', '/crm' ],
	'the website asks for the workbench, which is turned away' => [ 'www.example.org', '/_admin', $ROUTE ],
	'a name nobody owns asks for the sign-in of the kernel, which is turned away' => [ 'evil.test', '/.nino/login', $ROUTE ],
];
$wrong = [];

foreach( $cases as $label => [ $host, $uri, $expected ] )
	if( $turned( crmVisit( $hsRequest, $host, $uri ) ) !== $expected )
		$wrong[] = $label;

check( 'with an app host the workbench answers on that name alone, the CRM on the CRM host alone, and the sign-in of the kernel on the name of whichever is asked', $wrong === [] && \Nino\Modules\Crm\Hosts::workbench( $hsRequest ) === 'app.example.org' && \Nino\Modules\Crm\Hosts::app( $hsRequest ) === 'app.example.org' );

$moved = crmVisit( $hsRequest, 'crm.example.org', '/_admin/x/y?tab=1&b=2' );
$answer = $moved;
\Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer );
check( 'the workbench asked for on the CRM host, where it was before it had a name of its own, is a bookmark of before: a GET is sent to the app host with the same path and the query, 301', $turned( $moved ) === $ROUTE && $answer['/nino/http/response']['statusCode'] === 301 && ( $answer['/nino/http/response']['header']['Location'] ?? '' ) === 'https://app.example.org/_admin/x/y?tab=1&b=2' );
check( 'the 301 of a bookmark is not kept by a browser: the owner may change the name of the app, so the answer says no-store', ( $answer['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store' );
$refused = [];

foreach( [ [ 'POST', '/_admin/x' ], [ 'GET', '/_ADMIN/x' ], [ 'GET', '/_admin//x' ], [ 'GET', "/_admin/x\r\nSet-Cookie: a=b" ], [ 'GET', '/_admin/%2e%2e/x' ], [ 'GET', '/_admin/../x' ], [ 'GET', '/_admin/a/..' ] ] as [ $method, $uri ] ) {

	$sent = crmVisit( $hsRequest, 'crm.example.org', $uri, $method );
	$answer = $sent;
	\Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer );

	if( $turned( $sent ) !== $ROUTE || $answer['/nino/http/response']['statusCode'] !== 404 || isset( $answer['/nino/http/response']['header']['Location'] ) === true )
		$refused[] = $method. ' '. $uri. ' '. $turned( $sent ). ' '. $answer['/nino/http/response']['statusCode'];
}

check( 'a bookmark with another method, a spelling a web server would read differently, a line break or a blank is no redirect: it is a place that is not there, 404, with no Location', $refused === [] );
$query = crmVisit( $hsRequest, 'crm.example.org', "/_admin/x?a=1\r\nX: y" );
$answer = $query;
\Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer );
$odd = [];

foreach( [ '/_admin/x y', '/_admin/x%20y', "/_admin/x\ty", '/_admin/ü', '/_admin/x?a=1 2' ] as $uri ) {

	$sent = crmVisit( $hsRequest, 'crm.example.org', $uri );
	$answer = $sent;
	\Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer );
	$location = $answer['/nino/http/response']['header']['Location'] ?? '';

	// What is sent on is a whole address of the app host with nothing in it that a header or a browser reads as more than one thing - or it is nothing
	if( $location !== '' && preg_match( '#^https://app\.example\.org/_admin[A-Za-z0-9._~/-]*(?:\?[\x21-\x7e]*)?$#D', $location ) !== 1 )
		$odd[] = $uri;
}

check( 'a bookmark with a blank, a percent sign, a tab, a letter outside ASCII or a space in its query is sent on only as a whole, plain address of the app host, or not at all', $odd === [] );
check( 'a query that could end the line of a header is dropped from the Location and nothing else of the request is lost', $answer['/nino/http/response']['statusCode'] === 301 && str_contains( $answer['/nino/http/response']['header']['Location'] ?? '', "\n" ) === false && str_contains( $answer['/nino/http/response']['header']['Location'] ?? '', 'app.example.org/_admin/x' ) === true );
$answer = crmVisit( $hsRequest, 'www.example.org', '/.crm/place' );
$byHand = ninoErrors( static function() use ( &$hsRequest, &$answer ): void { \Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer ); } );
check( 'the route that answers what was refused is 404 when it is asked for by hand, and answers no redirect that was not made for this request', $byHand === [] && $answer['/nino/http/response']['statusCode'] === 404 && isset( $answer['/nino/http/response']['header']['Location'] ) === false );

$answer = crmVisit( $hsRequest, 'crm.example.org', '/_admin/once' );
\Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer );
\Nino\Modules\Crm\Hosts::callbackRedirect( $hsRequest, $answer );
check( 'the redirect a request was turned into is made once: asked again for the same request the route is 404', $answer['/nino/http/response']['statusCode'] === 404 && isset( $answer['/nino/http/response']['header']['Location'] ) === false );
\Nino\Modules\Crm\Settings::set( $hsRequest, 'app_host', 'workbench.example.org' );
$firstHost = \Nino\Modules\Crm\Hosts::app( $hsRequest );
\Nino\Modules\Crm\Settings::set( $hsRequest, 'app_host', 'app.example.org' );
check( 'a host that is written is the host that is read in the same request: the names are read again after a write, not kept from before', $firstHost === 'workbench.example.org' && \Nino\Modules\Crm\Hosts::app( $hsRequest ) === 'app.example.org' );

// The address a page writes for a place of the owner: the one function that says where the CRM and the workbench are
$onWebsite = crmVisit( $hsRequest, 'www.example.org', '/contact' );
$address = static fn( string $path ): string => \Nino\Modules\Crm\Hosts::address( $GLOBALS['hsRequest'], $path );
$written = [ $address( '/crm' ), $address( '/crm#client-4' ), $address( '/crm/todo' ), $address( '/crm?a=1' ), $address( '/_admin/#files' ), $address( '/contact' ) ];
$onCrm = crmVisit( $hsRequest, 'crm.example.org', '/crm' );
$onCrmWrote = [ $address( '/crm' ), $address( '/crm#client-4' ), $address( '/crm/todo' ), $address( '/_admin/#files' ) ];
$onApp = crmVisit( $hsRequest, 'app.example.org', '/_admin' );
$onAppWrote = [ $address( '/_admin/#files' ), $address( '/crm' ) ];
check( 'the address of the CRM and of the workbench is on the host the setting names - absolute from another host, as it is on its own, and / for the start page of the CRM - and any other path is left alone',
	$written === [ 'https://crm.example.org/', 'https://crm.example.org/#client-4', 'https://crm.example.org/crm/todo', 'https://crm.example.org/?a=1', 'https://app.example.org/_admin/#files', '/contact' ]
	&& $onCrmWrote === [ '/', '/#client-4', '/crm/todo', 'https://app.example.org/_admin/#files' ] && $onAppWrote === [ '/_admin/#files', 'https://crm.example.org/' ] );
check( 'the absolute address a mail or a document writes for a place of the owner is the host\'s, whatever host the request had; any other path is the site\'s',
	\Nino\Html::absoluteUrl( $hsRequest, '/crm' ) === 'https://crm.example.org/' && \Nino\Html::absoluteUrl( $hsRequest, '/crm/api?x=1' ) === 'https://crm.example.org/crm/api?x=1' && \Nino\Html::absoluteUrl( $hsRequest, '/_admin' ) === 'https://app.example.org/_admin' && \Nino\Html::absoluteUrl( $hsRequest, '/contact' ) === 'https://www.example.org/contact'
	&& \Nino\Modules\Crm\Hosts::crmExternal( '/crm' ) === '/' && \Nino\Modules\Crm\Hosts::crmExternal( '/crm?a=1' ) === '/?a=1' && \Nino\Modules\Crm\Hosts::crmExternal( '/crmx' ) === '/crmx' && \Nino\Modules\Crm\Hosts::crmExternal( '/crm/events' ) === '/crm/events' );
check( 'a name that is not a name the site can use is no name: a setting that holds the host of the website, one that is not a host and one that is written to the table by hand are read as none - the CRM host is not taken from a stored value that fails its rule',
	( static function() use ( $hsRequest, $hsDir ): bool {
		$raw = \Nino\Modules\Crm\Db::pdo( $hsRequest );
		$read = [];

		foreach( [ 'www.example.org', 'localhost', 'http://x.org', 'CRM.example.org', "a.example.org\0x" ] as $stored ) {
			$raw->prepare( 'INSERT INTO settings ( key, value ) VALUES ( \'crm_host\', :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value' )->execute( [ 'v' => $stored ] );
			$fresh = crmRequest( $hsDir );
			\Nino\Html::addFills( $fresh, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
			$read[] = \Nino\Modules\Crm\Hosts::crm( $fresh );
		}

		$raw->prepare( 'INSERT INTO settings ( key, value ) VALUES ( \'crm_host\', \'crm.example.org\' ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value' )->execute();

		return $read === [ '', '', '', '', '' ];
	} )() === true );
check( 'a project served from a directory has no host of the owner: the settings are not read, and no request is turned', ( static function() use ( $hsDir ): bool {
	$below = crmRequest( $hsDir );
	$below['/nino/dir'] = '/club';
	\Nino\Html::addFills( $below, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );

	return \Nino\Modules\Crm\Hosts::crm( $below ) === '' && \Nino\Modules\Crm\Hosts::app( $below ) === '';
} )() === true );

// Paths that every host answers
$shared = [ 'a prefix with a slash at its end' => [ [ '/.docs/pdf/' ], [] ], 'a whole path' => [ [], [ '/.docs/zip' ] ] ];
$entered = [];

foreach( $shared as $label => [ $prefixes, $exact ] )
	$entered[$label] = \Nino\Modules\Crm\Hosts::share( $hsRequest, $prefixes, $exact, [ 'package' => 'crm-probe' ] );

check( 'a package enters the paths every host answers - a prefix with a slash at the end, a whole path - and the same again is as good as the first time',  $entered === [ 'a prefix with a slash at its end' => true, 'a whole path' => true ] && \Nino\Modules\Crm\Hosts::share( $hsRequest, [ '/.docs/pdf/' ], [ '/.docs/zip' ], [ 'package' => 'crm-probe' ] ) === true && ninoWarnings() === [] );
check( '...and a path is shared by its prefix and by the whole path, in any spelling the web server reads the same, and not otherwise', \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.docs/pdf/7' ) === true && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.DOCS/PDF/7' ) === true && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.docs/zip' ) === true
	&& \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.docs/zipx' ) === false && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.docs/pdf' ) === false && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.docs/pdf/../../secret' ) === false && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/contact' ) === false );
$badPaths = [ 'a prefix with no slash at its end' => [ [ '/.docs/x' ], [] ], 'a prefix that is not absolute' => [ [ '.docs/x/' ], [] ], 'a prefix in capitals' => [ [ '/.Docs/' ], [] ], 'a prefix that climbs' => [ [ '/a/../b/' ], [] ], 'a prefix that is a dot' => [ [ '/./' ], [] ],
	'a prefix that is the root' => [ [ '/' ], [] ], 'a prefix with a null byte' => [ [ "/a\0/" ], [] ], 'a prefix with a space' => [ [ '/a b/' ], [] ], 'a prefix that is a number' => [ [ 7 ], [] ], 'a whole path with a slash at its end' => [ [], [ '/a/' ] ], 'a whole path that climbs' => [ [], [ '/a/..' ] ],
	'a whole path with a query' => [ [], [ '/a?b=1' ] ], 'a whole path with a line break' => [ [], [ "/a\n" ] ], 'a good one beside a bad one' => [ [ '/.fine/' ], [ '/bad path' ] ] ];
$refused = [];

foreach( $badPaths as $label => [ $prefixes, $exact ] )
	if( \Nino\Modules\Crm\Hosts::share( $hsRequest, $prefixes, $exact, [ 'package' => 'crm-probe' ] ) !== false || count( ninoWarnings() ) !== 1 )
		$refused[] = $label;

check( 'a path that is no path - no slash at the start or at the end of a prefix, capitals, dot segments, a null byte, a blank, a query, a line break - is refused with one warning, and a list with one bad path enters none of it', $refused === [] && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.fine/x' ) === false && count( $badPaths ) === 14 );
$asked = [ 'path' => '/.late/x', 'shared' => false ];
\Nino\Callbacks::registerCallback( $hsRequest, '/crm/hosts/shared', static function( array &$appData, array &$ask ): void { $ask['shared'] = str_starts_with( $ask['path'], '/.late/' ); } );
check( 'the hook asks a package that has a path of its own: what it answers for is shared, and the answer of one that says nothing is no', \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.late/x' ) === true && \Nino\Modules\Crm\Hosts::shared( $hsRequest, '/.later/x' ) === false );

// The route that shows that a name is this installation
$probe = \Nino\Filesystem::path( $hsRequest, \Nino\Modules\Crm\Hosts::PROBE_FILE );
$call = static function( mixed $content ) use ( &$hsRequest, $probe ): array {

	if( $content === null )
		is_file( $probe ) === true && unlink( $probe );
	else
		\Nino\Filesystem::putFileContent( $hsRequest, \Nino\Modules\Crm\Hosts::PROBE_FILE, $content );

	$answer = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Crm\Hosts::callbackProbe( $hsRequest, $answer );

	return [ $answer['/nino/http/response']['statusCode'], $answer['/nino/http/response']['body'], $answer['/nino/http/response']['header']['Cache-Control'] ?? '' ];
};
check( 'the probe answers the hash of the number a question made while it lives - 200, never cached - and 404 in every other case: no file, an empty file, a number that is old - the limit is ten minutes, and the check stays off its edge by two seconds, since the second can turn between the two calls -, one from the future, and a file with a number that is no text',
	$call( [ 'nonce' => 'abc', 'at' => time() ] ) === [ 200, hash( 'sha256', 'abc' ), 'no-store' ] && $call( null )[0] === 404 && $call( [] )[0] === 404 && $call( [ 'nonce' => 'abc', 'at' => time() - 603 ] )[0] === 404 && $call( [ 'nonce' => 'abc', 'at' => time() + 600 ] )[0] === 404
	&& $call( [ 'nonce' => 7, 'at' => time() ] )[0] === 404 && $call( [ 'nonce' => 'abc' ] )[0] === 404 && $call( 'text' )[0] === 404 && $call( [ 'nonce' => 'abc', 'at' => time() - 598 ] )[0] === 200 );
$call( null );
$asked = \Nino\Modules\Crm\Hosts::probe( $hsRequest, 'not a host' );
check( 'asking whether a name is this installation is no request for a name that is none, and writes nothing', $asked === false && is_file( $probe ) === false && \Nino\Modules\Crm\Hosts::probe( $hsRequest, "a.example.org\0@evil.test" ) === false && \Nino\Modules\Crm\Hosts::probe( $hsRequest, 'a.example.org/x' ) === false && is_file( $probe ) === false );
check( 'a probe that cannot write its file says so in a warning that names no path and is no success', ( static function() use ( &$hsRequest ): bool {
	$blocked = crmRequest( ninoSandboxDir( $hsRequest ) );
	$file = \Nino\Filesystem::path( $blocked, \Nino\Modules\Crm\Hosts::PROBE_FILE );
	is_file( $file ) === true && unlink( $file );
	mkdir( $file );
	ninoWarnings();
	$result = ninoErrors( static function() use ( &$blocked, &$answer ): void { $answer = \Nino\Modules\Crm\Hosts::probe( $blocked, 'crm.example.org' ); } );
	rmdir( $file );

	return $answer === false && array_filter( $result, static fn( array $error ): bool => $error[0] === E_USER_WARNING && str_contains( $error[1], ninoSandboxDir( $GLOBALS['hsRequest'] ) ) === false ) !== [];
} )() === true );
crmRemove( $hsDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The hosts', $crash );
}


// --- The mail ---------------------------------------------------------------------------------------------------------------

echo "\nThe mail\n";

try {

[ $ml, $mlDir, $mlRequest ] = crmWorld( 'crm6-mail' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$fills = [ '[[/project/website/general/url]]' => 'www.example.org', '[[/project/mail/spacing/large]]' => '24px', '[[/project/mail/spacing/medium]]' => '12px', '[[/project/mail/color/border]]' => '#dddddd', '[[/project/company/contact/email]]' => 'owner@example.org', '[[/project/mail/address/envelope]]' => 'no-reply@example.org' ];
\Nino\Html::addFills( $mlRequest, $fills, '*' );
$band = static fn( array &$appData ): string => \Nino\Html::renderHtml( $appData, '<i>[mail-head]</i>' );

check( 'with no wordmark and no picture in the slot the band is nothing at all - no table, no space, no shortcode as text', $band( $mlRequest ) === '<i></i>' && ninoWarnings() === [] );
mkdir( $mlDir. '/public/images', 0755, true );
file_put_contents( $mlDir. '/public/images/logo.png', base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
$slots = [ '/logo/mail' => [ 'label' => 'Logo', 'width' => 440, 'height' => 88, 'filename' => 'logo.png' ] ];
$mlRequest['/nino/html/images'] = $slots;
$logo = $band( $mlRequest );
check( 'drawing the band of a mail where there is no database yet reads and makes nothing: the file is not there afterwards', is_file( \Nino\Filesystem::path( $mlRequest, \Nino\Modules\Crm\Db::FILE ) ) === false );

check( 'with a picture in the slot and no wordmark the band is the picture, linked to the website, at its absolute address, with the alt text of the picture - one image', substr_count( $logo, '<img ' ) === 1 && str_contains( $logo, 'src="https://www.example.org/public/images/logo.png"' ) === true && str_contains( $logo, '<a href="https://www.example.org"' ) === true
	&& str_contains( $logo, 'border-bottom:1px solid #dddddd' ) === true && str_contains( $logo, '[[' ) === false && str_contains( $logo, '[imagevalues' ) === false );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_word', 'Ac<me>[x] &' );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_domain', 'dom.example' );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_color', '#112233' );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_ink', '#ffff00' );
$word = $band( $mlRequest );
check( 'with a wordmark the band is a table: the first line in capitals in the colour of the setting, the second in the ink - which falls to the grey where the colour would not read on white - and the picture of a slot that is wider than twice its height is left out, so that the words are not pushed off a phone',
	str_contains( $word, '<table' ) === true && str_contains( $word, 'color:#112233' ) === true && str_contains( $word, 'color:#6B6E70' ) === true && str_contains( $word, 'DOM.EXAMPLE' ) === true && str_contains( $word, '<img' ) === false && str_contains( $word, '[[' ) === false );
check( 'a value of the settings is text in the band: markup is escaped, a bracket is a bracket, and nothing in it is read as a fill or a shortcode', str_contains( $word, 'AC&lt;ME&gt;[X] &amp;' ) === true && str_contains( $word, '<ME>' ) === false );
$mlRequest['/nino/html/images']['/logo/mail'] = [ 'width' => 100, 'height' => 100 ] + $slots['/logo/mail'];
$square = $band( $mlRequest );
check( 'a picture that is no wider than twice its height stands to the right of the words, 44 pixels high and as wide as its shape, with no alt text', substr_count( $square, '<img ' ) === 1 && str_contains( $square, 'width="44" height="44"' ) === true && str_contains( $square, 'alt=""' ) === true && str_contains( $square, 'AC&lt;ME&gt;' ) === true );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_domain', '' );
check( 'without a second line the band has none', str_contains( $band( $mlRequest ), 'DOM.EXAMPLE' ) === false && str_contains( $band( $mlRequest ), 'AC&lt;ME&gt;' ) === true );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_word', '' );
check( 'the setting that is emptied gives the band of the picture back', $band( $mlRequest ) === $logo );

// The band is the same whichever of the module and the packages registers first, and a package without a band hands on what it was given
$both = [];

foreach( [ [ '\\Nino\\Modules\\Crm', '\\Nino\\Modules\\CrmProbe' ], [ '\\Nino\\Modules\\CrmProbe', '\\Nino\\Modules\\Crm' ] ] as $order ) {

	$variant = crmRequest( $mlDir, false );
	\Nino\Html::addFills( $variant, $fills, '*' );
	$variant['/nino/html/images'] = $slots;

	foreach( $order as $class )
		$class::init( $variant );

	$both[] = $band( $variant );
}

check( 'in both orders of registration - the core before the package that has a mail and the package before the core - the band is the band: a package that has no band of its own answers what it was given, and the core takes nothing as nobody\'s', $both[0] === $both[1] && $both[0] === $logo && ninoWarnings() === [] );
$other = crmRequest( $mlDir, false );
\Nino\Html::addFills( $other, $fills, '*' );
\Nino\Html::addShortcode( $other, 'mail-head', static fn( array &$appData, mixed $args ): string => '<band-of-another-module/>', false );
\Nino\Modules\Crm::init( $other );
check( 'a band that another module drew before stands: the core hands it on and draws none of its own', $band( $other ) === '<i><band-of-another-module/></i>' );
$twice = crmRequest( $mlDir, false );
\Nino\Html::addFills( $twice, $fills, '*' );
\Nino\Modules\Crm::init( $twice );
\Nino\Modules\Crm\Mail::registerHead( $twice );
\Nino\Modules\Crm::init( $twice );
$twice['/nino/html/images'] = $slots;
check( 'registering the band twice in one request registers it once: the band is not drawn twice', $band( $twice ) === $logo );

check( 'the band is registered once for a request: one callback of the shortcode however often the core is initialised', count( $twice['./nino/callbacks']['/nino/html/shortcode/mail-head'][5] ?? [] ) === 1 );
$rawDb = \Nino\Modules\Crm\Db::pdo( $mlRequest );
$rawDb->exec( 'INSERT INTO settings ( key, value ) VALUES ( \'brand_word\', \'Raw\' ), ( \'brand_domain\', \'<b>[x]</b>\' ), ( \'brand_color\', \'#zz"><script>\' ), ( \'brand_ink\', \'red; background:url(x)\' ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value' );
$rawRequest = crmRequest( $mlDir );
\Nino\Html::addFills( $rawRequest, $fills, '*' );
$raw = $band( $rawRequest );
check( 'what the table of the settings holds - written by hand, never through the rules - is no markup in the band: the second line is escaped, a colour that is none is the colour of the brand and an ink that is none the ink of the brand', str_contains( $raw, '&lt;B&gt;[X]&lt;/B&gt;' ) === true && str_contains( $raw, '<b>' ) === false && str_contains( $raw, 'color:#54BCD6' ) === true && str_contains( $raw, 'color:#444647' ) === true && str_contains( $raw, '<script' ) === false && str_contains( $raw, 'background:url' ) === false );
$rawDb->exec( 'DELETE FROM settings' );

// A part of the band that the project does not have is a warning that names the template and a band without that part: the mail it stands in goes out all the same
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_word', 'Acme' );
unlink( $mlDir. '/private/templates/mail/crm-head-word.tpl' );
ninoWarnings();
$without = $band( $mlRequest );
$said = ninoWarnings();
check( 'a template of the band that is missing is one warning that names the path, and the band is nothing - the mail around it is not lost', $without === '<i></i>' && count( $said ) === 1 && str_contains( $said[0], '/templates/mail/crm-head-word.tpl' ) === true );
copy( $features. '/Crm/install/templates/mail/crm-head-word.tpl', $mlDir. '/private/templates/mail/crm-head-word.tpl' );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_word', '' );

// The colours of the band
check( 'the ink is the colour of the setting where it reads on white, the grey where it does not and the ink of the brand where there is none or it is no colour: contrast() is the one of WCAG 2', \Nino\Modules\Crm\Mail::ink( '' ) === '#444647' && \Nino\Modules\Crm\Mail::ink( '#abc' ) === '#444647' && \Nino\Modules\Crm\Mail::ink( '#ffff00' ) === '#6B6E70'
	&& \Nino\Modules\Crm\Mail::ink( '#000000' ) === '#000000' && \Nino\Modules\Crm\Mail::ink( '#112233' ) === '#112233' && round( \Nino\Modules\Crm\Mail::contrast( '#ffffff', '#000000' ), 1 ) === 21.0 && round( \Nino\Modules\Crm\Mail::contrast( '#ffffff', '#ffffff' ), 1 ) === 1.0
	&& \Nino\Modules\Crm\Mail::contrast( '#6B6E70', '#ffffff' ) >= 4.5 && \Nino\Modules\Crm\Mail::contrast( '#444647', '#ffffff' ) >= 4.5 );

// The words of the mails
$words = [
	'/feature/crm/mail-test/plain' => [ 'de_DE' => 'Hallo %s', 'en_US' => 'Hello %s' ], '/feature/crm/mail-test/places' => [ 'de_DE' => '%s und %s', 'en_US' => '%s and %s' ], '/feature/crm/mail-test/percent' => [ 'de_DE' => '100% aus', 'en_US' => '100% off' ],
	'/feature/crm/mail-test/escaped' => [ 'de_DE' => '100%% aus', 'en_US' => '100%% off' ], '/feature/crm/mail-test/numbered' => [ 'de_DE' => '%2$s vor %1$s', 'en_US' => '%2$s before %1$s' ], '/feature/crm/mail-test/native' => [ 'de_DE' => 'Nur deutsch', 'en_US' => '' ],
	'/feature/crm/mail-test/blank' => [ 'de_DE' => '   ', 'en_US' => '   ' ], '/feature/crm/mail-test/number' => [ 'de_DE' => 'Anzahl %d', 'en_US' => 'Count %d' ], '/feature/other/mail-test/plain' => [ 'de_DE' => 'Fremd', 'en_US' => 'Foreign' ], '/feature/crm-probe/mail/subject' => [ 'de_DE' => 'Probe', 'en_US' => 'Probe en' ],
];

foreach( [ 'de_DE', 'en_US' ] as $locale )
	\Nino\Filesystem::mutate( $mlRequest, '/text/'. $locale. '.php', static function( mixed $state ) use ( $words, $locale ): array { return array_merge( is_array( $state ) === true ? $state : [], array_combine( array_map( static fn( string $key ): string => '[['. $key. ']]', array_keys( $words ) ), array_column( $words, $locale ) ) ); }, [] );

$text = static fn( string $key, string ...$params ): string => \Nino\Modules\Crm\Mail::text( $GLOBALS['mlRequest'], $key, ...$params );
$thrown = static function( callable $call ): bool { try { $call(); } catch( \OutOfBoundsException ) { return true; } return false; };
check( 'a word is read from the text file of the project in the native language, with its places filled', $text( '/feature/crm/mail-test/plain', 'Ada' ) === 'Hallo Ada' && $text( '/feature/crm/mail-test/places', 'a', 'b' ) === 'a und b' && $text( '/feature/crm/mail-test/escaped' ) === '100% aus' && $text( '/feature/crm/mail-test/numbered', 'eins', 'zwei' ) === 'zwei vor eins' );
check( 'a word the project changed may carry no place but %s, %n$s and %% - a bare percent sign, and more places than the mail has values, are no word - and a word that is missing, blank or below a root nobody entered is no word either: a mail cannot say a key or an empty line',
	$thrown( static fn() => $GLOBALS['text']( '/feature/crm/mail-test/percent' ) ) === true && $thrown( static fn() => $GLOBALS['text']( '/feature/crm/mail-test/number', '5' ) ) === true && $thrown( static fn() => $GLOBALS['text']( '/feature/crm/mail-test/places', 'only one' ) ) === true && $thrown( static fn() => $GLOBALS['text']( '/feature/crm/mail-test/missing' ) ) === true
	&& $thrown( static fn() => $GLOBALS['text']( '/feature/crm/mail-test/blank' ) ) === true && $thrown( static fn() => $GLOBALS['text']( '/feature/other/mail-test/plain' ) ) === true && $thrown( static fn() => $GLOBALS['text']( '/project/company/general/name' ) ) === true && $thrown( static fn() => $GLOBALS['text']( '/feature/crm' ) ) === true );
check( 'a package reads the words below its own root, which it entered - and the core\'s below the core\'s', $text( '/feature/crm-probe/mail/subject' ) === 'Probe' && \Nino\Modules\Crm\Mail::addWords( $mlRequest, '/feature/crm-probe/', [ 'package' => 'crm-probe' ] ) === true && array_filter( [ 'feature/x/', '/feature/x', '/feature/X/', '/feature/x/y/', '/project/', '/feature//', "/feature/x\0/" ], static fn( string $root ): bool => \Nino\Modules\Crm\Mail::addWords( $GLOBALS['mlRequest'], $root, [ 'package' => 'crm-probe' ] ) !== false ) === [] && count( ninoWarnings() ) === 7 );
$before = \Nino\Modules\Crm\Mail::speak( $mlRequest, 'en_US' );
check( 'speak() reads the words of a mail in another language where the site has it and the file has words of the mails, and resume() puts the language back', $before === '' && $text( '/feature/crm/mail-test/plain', 'Ada' ) === 'Hello Ada' && $text( '/feature/crm/mail-test/native' ) === 'Nur deutsch' && \Nino\Modules\Crm\Mail::speak( $mlRequest, 'de_DE' ) === 'en_US' && $text( '/feature/crm/mail-test/plain', 'Ada' ) === 'Hallo Ada'
	&& ( \Nino\Modules\Crm\Mail::resume( $mlRequest, 'en_US' ) ?? true ) === true && $text( '/feature/crm/mail-test/plain', 'Ada' ) === 'Hello Ada' );
\Nino\Modules\Crm\Mail::resume( $mlRequest, '' );

// A language that the site has not, and one that the site has but whose file has no word of the mails, is the native language - and one that has words is itself
$mlRequest['/nino/locales/available'] = [ 'de_DE', 'en_US', 'es_ES', 'fr_FR' ];
\Nino\Filesystem::putFileContent( $mlRequest, '/text/es_ES.php', [ '[[/feature/crm/mail-test/plain]]' => 'Hola %s', '[[/project/company/general/name]]' => 'Otro' ] );
\Nino\Filesystem::putFileContent( $mlRequest, '/text/fr_FR.php', [ '[[/project/company/general/name]]' => 'Autre' ] );
\Nino\Filesystem::putFileContent( $mlRequest, '/text/it_IT.php', [ '[[/feature/crm/mail-test/plain]]' => 'Ciao %s' ] );
check( 'a language is the language of the words when the site has it and its file has a word of the mails: Spanish has, French is a site language with no word of the mails, Italian has words and is no language of the site, and a code that is none, empty, with a null byte or in the wrong case is the native language',
	array_map( static fn( string $locale ): string => \Nino\Modules\Crm\Mail::wordsLocale( $GLOBALS['mlRequest'], $locale ), [ 'es_ES', 'fr_FR', 'it_IT', 'xx', '', "en_US\0", 'EN_us', 'de_DE', 'en_US' ] ) === [ 'es_ES', 'de_DE', 'de_DE', 'de_DE', 'de_DE', 'de_DE', 'de_DE', 'de_DE', 'en_US' ] );
check( 'a name of a template that is none - a path, a space, capitals, nothing - is a mistake of the caller, and the name of the band is none of them', array_filter( [ '../x', 'a b', 'Crm', '', 'a/b', "a\0b", '-a' ], static function( string $name ): bool { try { \Nino\Modules\Crm\Mail::template( $GLOBALS['mlRequest'], $name ); return true; } catch( \InvalidArgumentException ) { return false; } } ) === [] );

// A mail is made inside attempt(): the words are read once, a word that is missing is one warning that names the mail - and no word of it - and the answer of a mail that was not made
$files = \Nino\Filesystem::path( $mlRequest, '/text/de_DE.php' );
$seen = \Nino\Modules\Crm\Mail::attempt( $mlRequest, 'a test', [ 'none' ], static function() use ( &$mlRequest, $files ): array {

	$first = \Nino\Modules\Crm\Mail::text( $mlRequest, '/feature/crm/mail-test/plain', 'a' );
	\Nino\Filesystem::mutate( $mlRequest, '/text/de_DE.php', static fn( mixed $state ): array => array_merge( (array) $state, [ '[[/feature/crm/mail-test/plain]]' => 'Geaendert %s' ] ), [] );

	return [ $first, \Nino\Modules\Crm\Mail::text( $mlRequest, '/feature/crm/mail-test/plain', 'a' ) ];
} );
check( 'inside attempt() the text files are read once - a change of the file in the middle of a mail is not seen by it - and after it the file is read again', $seen === [ 'Hallo a', 'Hallo a' ] && $text( '/feature/crm/mail-test/plain', 'a' ) === 'Geaendert a' && ( $mlRequest['./crm/mail/words'] ?? null ) === null && ( $mlRequest['./crm/mail/depth'] ?? 0 ) === 0 );
ninoWarnings();
$failed = \Nino\Modules\Crm\Mail::attempt( $mlRequest, 'the invoice of a client', [ 'none' ], static fn(): array => [ \Nino\Modules\Crm\Mail::text( $GLOBALS['mlRequest'], '/feature/crm/mail-test/missing' ) ] );
$said = ninoWarnings();
check( 'a mail whose word the project lacks is not made: the answer is what a mail that was not made is answered with, and one warning names what the mail is about and what is missing', $failed === [ 'none' ] && count( $said ) === 1 && str_contains( $said[0], 'the invoice of a client' ) === true && str_contains( $said[0], 'mail-test/missing' ) === true );
$parts = \Nino\Modules\Crm\Mail::attempt( $mlRequest, 'two mails', [], static fn(): array => [ \Nino\Modules\Crm\Mail::part( 'the first', static fn(): array => [ 'ok' ] ), \Nino\Modules\Crm\Mail::part( 'the second', static fn(): array => [ \Nino\Modules\Crm\Mail::text( $GLOBALS['mlRequest'], '/feature/crm/mail-test/missing' ) ] ) ] );
check( 'one mail of several that cannot be made is left out with its warning, and the others go out', $parts === [ [ 'ok' ], null ] && count( ninoWarnings() ) === 1 );

// The outbox of the kernel
crmTransport( $mlRequest );
$GLOBALS['crmMails'] = [];
$mail = static fn( string $to, string $subject = 'S', string $body = '<p>B</p>' ): array => [ 'to' => $to, 'subject' => $subject, 'body' => $body, 'replyTo' => '' ];
$taken = \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'a@example.org' ), $mail( 'b@example.org', 'T' ) ], 'the mails about a test', true );
check( 'mails are handed to the outbox of the kernel in one call: the answer is how many it took, nothing is sent in the request, and what is delivered after it is the mail that was finished - subject and body as they were', $taken === 2 && $GLOBALS['crmMails'] === [] && \Nino\Mail::queued( $mlRequest )['waiting'] === 2 );
$delivered = \Nino\Mail::deliverQueued( $mlRequest, 10 );
check( '...and the outbox delivers them, each once, to the address it was made for', $delivered['delivered'] === 2 && ( static function(): array { $by = array_column( $GLOBALS['crmMails'], 'subject', 'to' ); ksort( $by ); return $by; } )() === [ 'a@example.org' => 'S', 'b@example.org' => 'T' ] && array_unique( array_column( $GLOBALS['crmMails'], 'body' ) ) === [ '<p>B</p>' ] );
$GLOBALS['crmMails'] = [];
$keyed = [ \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'k@example.org' ) ], 'a mail', false, 'crm/once' ), \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'k@example.org' ) ], 'a mail', false, 'crm/once' ) ];
\Nino\Mail::deliverQueued( $mlRequest, 10 );
check( 'the same mail under the same key is one mail: a request that comes twice sends it once', $keyed === [ 1, 1 ] && count( $GLOBALS['crmMails'] ) === 1 );
ninoWarnings();
$conflict = \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'other@example.org' ) ], 'a mail', false, 'crm/once' );
$said = ninoWarnings();
check( 'another mail under a key that is known is not taken: one warning, with the reason the outbox gives and no address', $conflict === 0 && count( $said ) === 1 && str_contains( $said[0], 'conflict' ) === true && str_contains( $said[0], '@' ) === false );
$invalid = \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'not an address' ), $mail( "x@example.org\r\nBcc: y@example.org" ), 'no mail', $mail( 'good@example.org' ) ], 'the mails about invalid ones', true );
$said = ninoWarnings();
check( 'a mail that is no mail - an address that is none, a line break in it, no list - is not taken, each with a warning that names the outbox\'s reason and never an address, and the good one goes on', $invalid === 1 && count( $said ) === 3 && array_filter( $said, static fn( string $warning ): bool => str_contains( $warning, '@' ) === true || str_contains( $warning, 'invalid' ) === false ) === [] );
\Nino\Mail::deliverQueued( $mlRequest, 10 );
$mlRequest['/nino/mail/cap'] = 1;
$_SERVER['REMOTE_ADDR'] = '203.0.113.50';
$GLOBALS['crmMails'] = [];
$limited = [ \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'c1@example.org' ) ], 'a mail' ), \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 'c2@example.org' ) ], 'a mail' ), \Nino\Modules\Crm\Mail::deliver( $mlRequest, [ $mail( 's1@example.org' ), $mail( 's2@example.org' ) ], 'mails of the system', true, '', \Nino\Mail::SYSTEM ) ];
$said = ninoWarnings();
check( 'a mail of a client counts one try of the cap of the hour and is refused over it - the answer is limit and a warning that names the cap - while the mails of the system count against the cap of the site and are taken', $limited === [ 1, 'limit', 2 ] && count( $said ) === 1 && str_contains( $said[0], 'cap' ) === true );
unset( $mlRequest['/nino/mail/cap'] );
\Nino\Mail::deliverQueued( $mlRequest, 10 );
$_SERVER['REMOTE_ADDR'] = '203.0.113.60';

// The test mail of the wordmark: what the owner waits for in the request
$GLOBALS['crmMails'] = [];
$empty = \Nino\Modules\Crm\Mail::brandTest( $mlRequest );
\Nino\Modules\Crm\Settings::set( $mlRequest, 'brand_word', 'Brand <b>' );
$mlRequest['/nino/html/images'] = [];
\Nino\Html::addFills( $mlRequest, [ '[[/project/company/contact/email]]' => 'not an address' ], '*' );
$contact = \Nino\Modules\Crm\Mail::brandTest( $mlRequest );
\Nino\Html::addFills( $mlRequest, [ '[[/project/company/contact/email]]' => 'owner@example.org' ], '*' );
\Nino\Filesystem::mutate( $mlRequest, '/text/de_DE.php', static fn( mixed $state ): array => array_merge( (array) $state, [ '[[/feature/crm/mail-brand/title]]' => 'Title <i>[x]</i> &' ] ), [] );
$sent = \Nino\Modules\Crm\Mail::brandTest( $mlRequest );
check( 'the test of the wordmark says brand_empty where the settings hold no first line, contact where the company has no contact address that is one, and with both sends one mail to the contact address, inside the request: the answer is 1 and the transport has the mail', $empty === 'brand_empty' && $contact === 'contact' && $sent === 1
	&& count( $GLOBALS['crmMails'] ) === 1 && $GLOBALS['crmMails'][0]['to'] === 'owner@example.org' && \Nino\Mail::queued( $mlRequest )['waiting'] === 0 );
$brandMail = $GLOBALS['crmMails'][0];
check( 'a word of the test mail that holds markup or a bracket is text in the mail, escaped, and no place of the template reads it', str_contains( $GLOBALS['crmMails'][0]['body'], 'Title &lt;i&gt;[x]&lt;/i&gt; &amp;' ) === true && str_contains( $GLOBALS['crmMails'][0]['body'], '<i>' ) === false );
check( 'the test mail has the band with the wordmark escaped, its subject and its three sentences from the words of the project, and no place of the template is left over', str_contains( $brandMail['body'], 'BRAND &lt;B&gt;' ) === true && $brandMail['subject'] === $text( '/feature/crm/mail-brand/subject' ) && str_contains( $brandMail['body'], $text( '/feature/crm/mail-brand/intro' ) ) === true
	&& str_contains( $brandMail['body'], htmlspecialchars( $text( '/feature/crm/mail-brand/notice' ), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) ) === true && preg_match( '/\[\[(heading|intro|notice|word|sign|domain)\]\]/', $brandMail['body'] ) === 0 && str_contains( $brandMail['body'], '[mail-head]' ) === false );
$GLOBALS['crmTransportDown'] = true;
$_SERVER['REMOTE_ADDR'] = '203.0.113.61';
$down = \Nino\Modules\Crm\Mail::brandTest( $mlRequest );
$said = ninoWarnings();
unset( $GLOBALS['crmTransportDown'] );
$mlRequest['/nino/mail/cap'] = 1;
$_SERVER['REMOTE_ADDR'] = '203.0.113.62';
\Nino\Modules\Crm\Mail::brandTest( $mlRequest );
$over = \Nino\Modules\Crm\Mail::brandTest( $mlRequest );
$overSaid = ninoWarnings();
check( 'a transport that is down is an answer of 0 with one warning that names no address, and the cap of the hour is limit with its warning', $down === 0 && count( array_filter( $said, static fn( string $warning ): bool => str_contains( $warning, 'owner@example.org' ) === false ) ) >= 1 && $over === 'limit' && array_filter( $overSaid, static fn( string $warning ): bool => str_contains( $warning, 'cap' ) === true ) !== [] );
$noWord = crmRequest( $mlDir );
\Nino\Html::addFills( $noWord, $fills, '*' );
\Nino\Modules\Crm\Settings::set( $noWord, 'brand_word', 'Brand' );
unlink( $mlDir. '/private/text/de_DE.php' );
check( 'a word the project lacks makes the test mail a warning and an answer of 0: it is not sent and the page of the owner is not broken', \Nino\Modules\Crm\Mail::brandTest( $noWord ) === 0 && count( ninoWarnings() ) >= 1 );
crmRemove( $mlDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The mail', $crash );
}


// --- The formats ------------------------------------------------------------------------------------------------------------

echo "\nThe formats\n";

try {

check( 'day() writes a Y-m-d as d.m.Y and nothing else as a day: a date with a time, a day with other separators, a blank, a day written short and no text are none', \Nino\Modules\Crm\Format::day( '2026-11-24' ) === '24.11.2026' && array_filter( [ '2026-11-24 10:00', '2026/11/24', '', ' 2026-11-24', '26-11-24', '2026-1-4', "2026-11-24\n", 'x' ], static fn( string $date ): bool => \Nino\Modules\Crm\Format::day( $date ) !== '' ) === [] );
check( 'country() names a country in the language it is asked for where the intl extension is there and gives the code where it is not, for a code that is none and for a language that is none', extension_loaded( 'intl' ) === false || ( \Nino\Modules\Crm\Format::country( 'AT', 'de_DE' ) === 'Österreich' && \Nino\Modules\Crm\Format::country( 'AT', 'en_US' ) === 'Austria' ) );
check( '...and gives the code for a code that is none, for a language that is none and for a null byte', \Nino\Modules\Crm\Format::country( 'a', 'de_DE' ) === 'a' && \Nino\Modules\Crm\Format::country( 'AT', 'german' ) === 'AT' && \Nino\Modules\Crm\Format::country( "AT\0", 'de_DE' ) === "AT\0" && \Nino\Modules\Crm\Format::country( 'AT', '' ) === 'AT' && \Nino\Modules\Crm\Format::country( 'ZZ', 'de_DE' ) !== '' );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The formats', $crash );
}


// --- The status ------------------------------------------------------------------------------------------------------------

echo "\nThe status\n";

try {

[ $su, $suDir, $suRequest ] = crmWorld( 'crm6-status' );
$suFile = $suDir. '/private/data/crm.sqlite';
$by = static fn( array &$appData ): array => array_column( \Nino\Modules\Crm\Status::lines( $appData ), null, 'key' );

$lines = \Nino\Modules\Crm\Status::lines( $suRequest );
check( 'the lines of the core and of the package that entered one stand in the order of their rank and then of their entering, and where there is no database the status is a read: it makes none and writes nothing',
	array_column( $lines, 'key' ) === [ 'database', 'writable', 'schema', 'strays', 'probe-line', 'addresses', 'mail', 'mail-head' ] && is_file( $suFile ) === false && glob( $suDir. '/private/data/crm*' ) === [] );
check( 'with no database the database is missing, it can be written, the schema is none - the one press that makes it is at the line of the database, and the line of the schema has none -, and the line of the package says its own words: a label, a detail and the values that are text, numbers or lists of them - an object, a list of lists and a name that is no name are left out',
	array_column( $lines, 'state', 'key' ) === [ 'database' => 'missing', 'writable' => 'ok', 'schema' => 'none', 'strays' => 'ok', 'probe-line' => 'ok', 'addresses' => 'default', 'mail' => 'ok', 'mail-head' => 'ok' ] && $lines[2]['stored'] === 0 && $lines[2]['version'] === 1
	&& $lines[4] === [ 'key' => 'probe-line', 'hint' => false, 'fixable' => true, 'state' => 'ok', 'label' => 'Probe', 'detail' => 'A line of a package', 'items' => 3, 'list' => [ 'x', 'y' ] ] && array_column( $lines, 'fixable', 'key' ) === [ 'database' => true, 'writable' => false, 'schema' => false, 'strays' => true, 'probe-line' => true, 'addresses' => false, 'mail' => false, 'mail-head' => false ]
	&& array_column( $lines, 'hint', 'key' )['addresses'] === true );
check( '...and an empty file is no database either: it is missing, and the fix makes the schema in it', ( static function() use ( &$suRequest, $suFile ): bool {
	touch( $suFile );
	$state = \Nino\Modules\Crm\Status::database( $suRequest )['state'];
	$fixed = \Nino\Modules\Crm\Status::fix( $suRequest, 'database' );

	return $state === 'missing' && $fixed === true && \Nino\Modules\Crm\Status::database( $suRequest )['state'] === 'ok';
} )() === true );
$lines = $by( $suRequest );
check( 'a database that is there is whole: the database is ok, the schema is ok with the version of the file, and the status of the whole is that nothing is lacking - the line that is only a hint does not count', $lines['database']['state'] === 'ok' && $lines['schema']['state'] === 'ok' && $lines['schema']['stored'] === 1 && $lines['schema']['lacks'] === [] && \Nino\Modules\Crm\Status::whole( array_values( $lines ) ) === true );

// A schema the code wants and the file lacks, and one the file has and the code does not know
$ahead = crmRequest( $suDir, false, [ './crm-probe/version' => 2, './crm-probe/schema' => 'schema-2.php' ] );
\Nino\Modules::callModules( $ahead, 'init' );
$behind = \Nino\Modules\Crm\Status::schema( $ahead );
check( 'a package whose code wants a version the file does not have is behind, with the package named, and the fix raises it; a file that holds a newer version than the code knows is ahead', $behind['state'] === 'behind' && $behind['lacks'] === [ 'crm-probe' ] && $behind['version'] === 1 && \Nino\Modules\Crm\Status::fix( $ahead, 'schema' ) === true && \Nino\Modules\Crm\Status::schema( $ahead )['state'] === 'ok' && \Nino\Modules\Crm\Status::schema( $suRequest )['state'] === 'ahead' );
check( 'the lines that are not whole make the status not whole, and a hint that fails does not: the line of the package that is broken is a line that is not whole, and without it the status is whole', ( static function() use ( &$ahead, $by ): bool {

	$ahead['./crm-probe/state'] = 'broken';
	$broken = $by( $ahead );
	$ahead['./crm-probe/state'] = 'ok';

	return $broken['probe-line']['state'] === 'broken' && \Nino\Modules\Crm\Status::whole( array_values( $broken ) ) === false && \Nino\Modules\Crm\Status::whole( array_filter( array_values( $broken ), static fn( array $line ): bool => $line['key'] !== 'probe-line' ) ) === true;
} )() === true );

$suRequest['./crm-probe/state'] = 'broken';
$fixed = [ \Nino\Modules\Crm\Status::fix( $suRequest, 'probe-line' ), \Nino\Modules\Crm\Status::fix( $suRequest, 'nothing' ), \Nino\Modules\Crm\Status::fix( $suRequest, 'writable' ), \Nino\Modules\Crm\Status::fix( $suRequest, 'Bad Key' ), \Nino\Modules\Crm\Status::fix( $suRequest, '' ), \Nino\Modules\Crm\Status::fix( $suRequest, 'all' ) ];
check( 'a fix is run by the name of one line: the fix of the package mends its line - once, with one call - a line nobody entered is unknown, a line without a fix is none, and there is no name that mends every line', $fixed === [ true, 'unknown', 'none', 'unknown', 'unknown', 'unknown' ] && $suRequest['./crm-probe/fixed'] === 1 && $by( $suRequest )['probe-line']['state'] === 'ok' );

// A line that breaks, and one that answers what is no state
$suRequest['./crm-probe/throws'] = true;
$broken = $by( $suRequest );
unset( $suRequest['./crm-probe/throws'] );
$suRequest['./crm-probe/shape'] = 'none';
ninoWarnings();
$shaped = $by( $suRequest );
$said = ninoWarnings();
unset( $suRequest['./crm-probe/shape'] );
check( 'a line that throws is a line that says "error" and nothing else breaks, and one that answers what is no state is "error" with one warning that names the line - the others are as they were', $broken['probe-line']['state'] === 'error' && $broken['database']['state'] === 'ok' && $shaped['probe-line'] === [ 'key' => 'probe-line', 'hint' => false, 'fixable' => true, 'state' => 'error' ]
	&& count( $said ) === 1 && str_contains( $said[0], 'probe-line' ) === true && $shaped['mail']['state'] === 'ok' );

$badLines = [
	'a key in capitals' => [ 'package' => 'crm-probe', 'key' => 'Mine', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ] ], 'a key with a quote' => [ 'package' => 'crm-probe', 'key' => 'a\'b', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ] ], 'a state that is a closure' => [ 'package' => 'crm-probe', 'key' => 'mine', 'state' => static fn() => [] ],
	'a state that is no method' => [ 'package' => 'crm-probe', 'key' => 'mine', 'state' => [ \Nino\Modules\Crm\Status::class, 'nothing' ] ], 'no state' => [ 'package' => 'crm-probe', 'key' => 'mine' ], 'a rank that is text' => [ 'package' => 'crm-probe', 'key' => 'mine', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ], 'rank' => '1' ],
	'a hint that is a number' => [ 'package' => 'crm-probe', 'key' => 'mine', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ], 'hint' => 1 ], 'a fix that is a string' => [ 'package' => 'crm-probe', 'key' => 'mine', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ], 'fix' => 'phpinfo' ],
	'a field the line has not' => [ 'package' => 'crm-probe', 'key' => 'mine', 'state' => [ \Nino\Modules\Crm\Status::class, 'database' ], 'owner' => 1 ], 'a line another has, with another state' => [ 'package' => 'crm-probe', 'key' => 'database', 'state' => [ \Nino\Modules\Crm\Status::class, 'writable' ] ],
];
$refused = [];

foreach( $badLines as $label => $spec )
	if( \Nino\Modules\Crm\Status::add( $suRequest, $spec ) !== false || count( ninoWarnings() ) !== 1 )
		$refused[] = $label;

check( 'a line with a key that is no key, a state or a fix that is no method of a class, a rank that is no number, a flag that is no flag, a field it has not, or a key another line has is refused with one warning and nothing is entered', $refused === [] && array_column( \Nino\Modules\Crm\Status::lines( $suRequest ), 'key' ) === [ 'database', 'writable', 'schema', 'strays', 'probe-line', 'addresses', 'mail', 'mail-head' ] && count( $badLines ) === 10 );

// The addresses: none, a name, and one the site cannot use
\Nino\Html::addFills( $suRequest, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
$addresses = [ \Nino\Modules\Crm\Status::addresses( $suRequest ) ];
\Nino\Modules\Crm\Settings::set( $suRequest, 'crm_host', 'crm.example.org' );
$addresses[] = \Nino\Modules\Crm\Status::addresses( $suRequest );
$suPdo = \Nino\Modules\Crm\Db::pdo( $suRequest );

foreach( [ [ 'crm_host', 'www.example.org' ], [ 'crm_host', 'localhost' ], [ 'app_host', 'bad host' ] ] as [ $key, $value ] ) {
	$suPdo->exec( 'DELETE FROM settings' );
	$suPdo->prepare( 'INSERT INTO settings ( key, value ) VALUES ( :key, :value )' )->execute( [ 'key' => $key, 'value' => $value ] );
	$fresh = crmRequest( $suDir );
	\Nino\Html::addFills( $fresh, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
	$addresses[] = \Nino\Modules\Crm\Status::addresses( $fresh );
}

check( 'the addresses are a hint: none set is the default, a name is ok, and the host of the website, a name that is none and a stored value that fails the rule are invalid - the names are told, so that the owner sees what is set',
	array_column( $addresses, 'state' ) === [ 'default', 'ok', 'invalid', 'invalid', 'invalid' ] && $addresses[1]['crm'] === 'crm.example.org' && $addresses[1]['app'] === '' );

// The outbox: mails given up on, and mails that wait for longer than an hour
crmTransport( $suRequest );
$_SERVER['REMOTE_ADDR'] = '203.0.113.70';
$suRequest['./nino/mail/now'] = time() - 7200;
\Nino\Mail::queue( $suRequest, [ 'to' => 'late@example.org', 'subject' => 'S', 'body' => 'B', 'replyTo' => '' ], \Nino\Mail::SYSTEM, 'crm/late' );
unset( $suRequest['./nino/mail/now'] );
$stuck = \Nino\Modules\Crm\Status::mail( $suRequest );
$GLOBALS['crmTransportDown'] = true;
$later = time();

for( $round = 0; $round < 8; $round++ ) {
	$later += 13 * 3600;
	$suRequest['./nino/mail/now'] = $later;
	\Nino\Mail::deliverQueued( $suRequest, 5 );
}

unset( $GLOBALS['crmTransportDown'], $suRequest['./nino/mail/now'] );
ninoWarnings();
$failed = \Nino\Modules\Crm\Status::mail( $suRequest );
check( 'the outbox is a line: ok where it delivers, stuck where a mail has waited for more than an hour, failed where mails were given up on - the counts are told, and no address or word of a mail', $stuck === [ 'state' => 'stuck', 'waiting' => 1, 'failed' => 0 ] && $failed['state'] === 'failed' && $failed['failed'] === 1 && $failed['waiting'] === 0 && array_keys( $failed ) === [ 'state', 'waiting', 'failed' ] );

// The band of the mails: the four templates of the project
$head = [ \Nino\Modules\Crm\Status::head( $suRequest )['state'] ];
unlink( $suDir. '/private/templates/mail/crm-head-sign.tpl' );
$head[] = \Nino\Modules\Crm\Status::head( $suRequest );
foreach( [ 'crm-head', 'crm-head-word', 'crm-head-domain' ] as $name )
	unlink( $suDir. '/private/templates/mail/'. $name. '.tpl' );
$head[] = \Nino\Modules\Crm\Status::head( $suRequest );
check( 'the templates of the band are a line: ok with the four, partial with the one that is missing named, none without any', $head[0] === 'ok' && $head[1] === [ 'state' => 'partial', 'lacks' => [ 'crm-head-sign' ] ] && $head[2] === [ 'state' => 'none', 'lacks' => [ 'crm-head', 'crm-head-word', 'crm-head-domain', 'crm-head-sign' ] ] );
crmRemove( $suDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The status', $crash );
}


// --- The panel - the actions, and who may ask for them -----------------------------------------------------------------------

echo "\nThe panel\n";

try {

[ $pw, $pwDir, $pwRequest ] = crmWorld( 'crm6-panel' );
\Nino\Html::addFills( $pwRequest, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
$post = [ \Nino\Admin\Admin::class, 'handlePost' ];
$pwFile = $pwDir. '/private/data/crm.sqlite';



crmAccounts( $pwRequest );
$pwActions = \Nino\Admin\Admin::actions( $pwRequest );
check( 'the three actions of the panel are known to the workbench, each with the class of the panel', array_keys( array_filter( $pwActions, static fn( array $target, string $action ): bool => str_starts_with( $action, 'crm/' ), ARRAY_FILTER_USE_BOTH ) ) === [ 'crm/status', 'crm/setup', 'crm/drop-data' ] && $pwActions['crm/status'][0] === \Nino\Modules\Crm\Admin::class );

$ask = static fn( string $action, array $data = [], int $before = 200 ): array => ninoCall( $GLOBALS['pwRequest'], $GLOBALS['post'], $data, $action, $before );
$kinds = [ 'crm/status' => [], 'crm/setup' => [ 'key' => 'database' ], 'crm/drop-data' => [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ] ];
$denied = [];

foreach( [ 'nobody is signed in' => '', 'an account that may do nothing' => 'plain@example.org', 'an account with the rights of other panels' => 'members@example.org' ] as $label => $who ) {

	crmWorkbench( $pwRequest, $who );
	$denied[$label] = [];

	foreach( $kinds as $action => $data )
		$denied[$label][$action] = $ask( $action, $data )[0];
}

check( 'every action refuses without a sign-in (401), with an account that may do nothing (403) and with the right of another panel - members, features, text - (403)', $denied === [ 'nobody is signed in' => [ 'crm/status' => 401, 'crm/setup' => 401, 'crm/drop-data' => 401 ], 'an account that may do nothing' => array_fill_keys( array_keys( $kinds ), 403 ), 'an account with the rights of other panels' => array_fill_keys( array_keys( $kinds ), 403 ) ] );
check( 'and nothing was made by the refusals: there is no database', is_file( $pwFile ) === false );
crmWorkbench( $pwRequest, 'crm@example.org' );
$blocked = [];

foreach( $kinds as $action => $data )
	$blocked[$action] = $ask( $action, $data, 403 )[0];

check( 'with the right of the panel and no token of the session - the guard of the cross-site request has refused the request - no action is run: the answer stays 403 and nothing is made', $blocked === array_fill_keys( array_keys( $kinds ), 403 ) && is_file( $pwFile ) === false );

$status = $ask( 'crm/status' );
check( 'the status answers the lines, whether all of them are as they should be, where the surface is and the data of packages that are gone - and, asked, makes no database: it is a read', $status[0] === 200 && array_keys( $status[1] ) === [ 'lines', 'whole', 'open', 'orphans' ] && $status[1]['whole'] === false && $status[1]['orphans'] === [] && is_file( $pwFile ) === false
	&& array_column( $status[1]['lines'], 'state', 'key' )['database'] === 'missing' && array_filter( $status[1]['lines'], static fn( array $line ): bool => array_keys( $line ) === array_unique( array_keys( $line ) ) && isset( $line['key'], $line['state'], $line['hint'], $line['fixable'] ) ) === $status[1]['lines'] );
check( 'the way to the surface is an address and the route of the surface is entered: the address is the one of the CRM, on its host where there is one', $status[1]['open'] === [ 'address' => '/crm' ] && isset( $pwRequest['/nino/http/routes']['GET://crm'] ) === true );

// The fixes
$setup = [ 'a name that is none' => $ask( 'crm/setup', [ 'key' => 'Bad Key' ] ), 'no name' => $ask( 'crm/setup', [] ), 'a name that is a list' => $ask( 'crm/setup', [ 'key' => [ 'database' ] ] ), 'a name nobody has' => $ask( 'crm/setup', [ 'key' => 'nothing' ] ), 'every line' => $ask( 'crm/setup', [ 'key' => 'all' ] ),
	'a line without a fix' => $ask( 'crm/setup', [ 'key' => 'writable' ] ), 'a name with a quote' => $ask( 'crm/setup', [ 'key' => 'x\'; DROP TABLE clients; --' ] ) ];
check( 'a fix is asked for by the name of one line: a name that is none, none at all or a list is 400, a line nobody has is 404, one without a fix is 409, and there is no name for every line - each with its code and the field - and nothing is made',
	array_map( static fn( array $answer ): array => [ $answer[0], $answer[1]['code'] ?? '' ], $setup ) === [ 'a name that is none' => [ 400, 'crm_setup_unknown' ], 'no name' => [ 400, 'crm_setup_unknown' ], 'a name that is a list' => [ 400, 'crm_setup_unknown' ], 'a name nobody has' => [ 404, 'crm_setup_unknown' ], 'every line' => [ 404, 'crm_setup_unknown' ],
		'a line without a fix' => [ 409, 'crm_setup_none' ], 'a name with a quote' => [ 400, 'crm_setup_unknown' ] ] && is_file( $pwFile ) === false && $setup['a name that is none'][1]['field'] === 'key' );
$made = $ask( 'crm/setup', [ 'key' => 'database' ] );
check( 'the fix of the database makes it, whole, and the answer is the screen as it is then: the lines say it is there and the status is whole', $made[0] === 200 && is_file( $pwFile ) === true && array_column( $made[1]['lines'], 'state', 'key' )['database'] === 'ok' && array_column( $made[1]['lines'], 'state', 'key' )['schema'] === 'ok' && $made[1]['whole'] === true );
$again = $ask( 'crm/setup', [ 'key' => 'schema' ] );
check( 'a fix that has nothing to do is as good as the first: the same press again changes nothing - the same bytes - and the answer is the screen', $again[0] === 200 && ( static function() use ( $pwFile, $ask ): bool { $before = crmFingerprint( $pwFile ); $ask( 'crm/setup', [ 'key' => 'database' ] ); return crmFingerprint( $pwFile ) === $before; } )() === true );
$line = $ask( 'crm/setup', [ 'key' => 'probe-line' ] );
check( 'the fix of the line of a package is run by the panel, and the log line of the action names the line and nothing the browser sent', $line[0] === 200 && $pwRequest['./crm-probe/fixed'] === 1 && \Nino\Modules\Crm\Admin::log( 'crm/setup', [ 'key' => 'probe-line' ] ) === 'Mended the line probe-line of the status of the CRM'
	&& \Nino\Modules\Crm\Admin::log( 'crm/setup', [ 'key' => "x\nInjected line" ] ) === 'Mended the line of the status of the CRM' && \Nino\Modules\Crm\Admin::log( 'crm/setup', [ 'key' => [ 'x' ] ] ) === 'Mended the line of the status of the CRM'
	&& \Nino\Modules\Crm\Admin::log( 'crm/drop-data', [ 'key' => 'crm-probe' ] ) === 'Deleted the data of the package crm-probe' && \Nino\Modules\Crm\Admin::log( 'crm/status', [] ) === '' && \Nino\Modules\Crm\Admin::log( 'crm/nothing', [] ) === '' );

$pwRequest['./crm-probe/fix'] = 'throws';
ninoWarnings();
$broken = $ask( 'crm/setup', [ 'key' => 'probe-line' ] );
$brokenWarned = count( ninoWarnings() );
$pwRequest['./crm-probe/fix'] = 'reason';
$reasoned = $ask( 'crm/setup', [ 'key' => 'probe-line' ] );
unset( $pwRequest['./crm-probe/fix'] );
ninoWarnings();
check( 'a fix that throws something that is no database failure is a 500 with the code of a step that failed and the name of the line - not a crash of the action - and one that gives a reason is the same 500 and says the reason', $broken[0] === 500 && $broken[1]['code'] === 'crm_setup_step' && $broken[1]['params'] === [ 'probe-line' ] && $brokenWarned === 1 && $reasoned[0] === 500 && $reasoned[1]['code'] === 'crm_setup_step'
	&& str_contains( (string) ( $reasoned[1]['error'] ?? '' ), 'disk-full' ) === true && str_contains( (string) ( $broken[1]['error'] ?? '' ), 'breaks' ) === false );

// A file that is not the core's: the fix is a 500 with its code, and the file is as it was
$foreignPanel = crmSandbox( 'crm6-panel-foreign' );
\Nino\Features::activate( $foreignPanel, 'crm' );
$fpDir = ninoSandboxDir( $foreignPanel );
$fpFile = $fpDir. '/private/data/crm.sqlite';
is_dir( dirname( $fpFile ) ) === true || mkdir( dirname( $fpFile ), 0755, true );
file_put_contents( $fpFile, 'not a database' );
$fpRequest = crmRequest( $fpDir );
crmAccounts( $fpRequest );
crmWorkbench( $fpRequest, 'admin@example.org' );
$kept = crmFingerprint( $fpFile );
$fpAnswer = ninoCall( $fpRequest, $post, [ 'key' => 'database' ], 'crm/setup' );
$fpStatus = ninoCall( $fpRequest, $post, [], 'crm/status' );
ninoWarnings();
$fpDrop = ninoCall( $fpRequest, $post, [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ], 'crm/drop-data' );
check( 'where the file is not the core\'s the fix is refused with the code of a step that failed and the line it was asked for, the data of a package is a failure of the database, the status says the file is foreign, and the bytes are the same', $fpAnswer[0] === 500 && $fpAnswer[1]['code'] === 'crm_setup_step' && $fpAnswer[1]['params'] === [ 'database' ] && $fpDrop[0] === 500 && $fpDrop[1]['code'] === 'crm_database' && $fpStatus[0] === 200 && array_column( $fpStatus[1]['lines'], 'state', 'key' )['database'] === 'foreign' && crmFingerprint( $fpFile ) === $kept );
crmRemove( $fpDir );

// The data of packages that are gone
crmWorkbench( $pwRequest, 'crm@example.org' );
$one = crmSandbox( 'crm6-panel-data' );
\Nino\Features::activate( $one, 'crm-probe' );
$oneDir = ninoSandboxDir( $one );
$oneRequest = crmRequest( $oneDir );
crmAccounts( $oneRequest );
\Nino\Modules\Crm\Db::pdo( $oneRequest );
\Nino\Features::deactivate( $one, 'crm-probe' );
$oneRequest = crmRequest( $oneDir );
crmAccounts( $oneRequest );
crmWorkbench( $oneRequest, 'crm@example.org' );
$off = ninoCall( $oneRequest, $post, [], 'crm/status' );
check( 'the data of a package that is switched off is no data of a removed package: the panel lists none, since the package comes back with its data and nothing here takes it away', $off[1]['orphans'] === [] && \Nino\Modules\Crm\Db::orphans( $oneRequest ) === [ [ 'package' => 'crm-probe', 'version' => 1, 'tables' => [ 'crm_probe_items' ], 'installed' => true ] ] );
$refused = [
	'a name that is none' => ninoCall( $oneRequest, $post, [ 'key' => 'x"; DROP TABLE clients; --', 'typed' => 'x"; DROP TABLE clients; --' ], 'crm/drop-data' ), 'no name' => ninoCall( $oneRequest, $post, [], 'crm/drop-data' ), 'a name that is a list' => ninoCall( $oneRequest, $post, [ 'key' => [ 'crm-probe' ], 'typed' => 'crm-probe' ], 'crm/drop-data' ),
	'the name not typed' => ninoCall( $oneRequest, $post, [ 'key' => 'crm-probe' ], 'crm/drop-data' ), 'another name typed' => ninoCall( $oneRequest, $post, [ 'key' => 'crm-probe', 'typed' => 'crm' ], 'crm/drop-data' ), 'the name typed in capitals' => ninoCall( $oneRequest, $post, [ 'key' => 'crm-probe', 'typed' => 'CRM-PROBE' ], 'crm/drop-data' ),
	'a package that is only off' => ninoCall( $oneRequest, $post, [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ], 'crm/drop-data' ), 'the core' => ninoCall( $oneRequest, $post, [ 'key' => 'crm', 'typed' => 'crm' ], 'crm/drop-data' ), 'a package nobody has' => ninoCall( $oneRequest, $post, [ 'key' => 'crm-nothing', 'typed' => 'crm-nothing' ], 'crm/drop-data' ),
	'every package' => ninoCall( $oneRequest, $post, [ 'key' => 'all', 'typed' => 'all' ], 'crm/drop-data' ),
];
check( 'the data of a package is taken away only on its name and its name typed: a name that is none, none, a list, a name not typed, typed with another or in capitals is 400 with its code; the data of a package that is only off and of the core are 409; of a package nobody has and of "every package" 404 - and nothing is lost',
	array_map( static fn( array $answer ): array => [ $answer[0], $answer[1]['code'] ?? '' ], $refused ) === [ 'a name that is none' => [ 400, 'crm_drop_key' ], 'no name' => [ 400, 'crm_drop_key' ], 'a name that is a list' => [ 400, 'crm_drop_key' ], 'the name not typed' => [ 400, 'crm_drop_typed' ], 'another name typed' => [ 400, 'crm_drop_typed' ], 'the name typed in capitals' => [ 400, 'crm_drop_typed' ],
		'a package that is only off' => [ 409, 'crm_drop_refused' ], 'the core' => [ 409, 'crm_drop_refused' ], 'a package nobody has' => [ 404, 'crm_drop_unknown' ], 'every package' => [ 404, 'crm_drop_unknown' ] ]
	&& (int) \Nino\Modules\Crm\Db::pdo( $oneRequest )->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name = \'crm_probe_items\'' )->fetchColumn() === 1 && crmShape( \Nino\Modules\Crm\Db::pdo( $oneRequest ) ) !== [] );
\Nino\Features::remove( $one, 'crm-probe' );
crmCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
\Nino\Features::remove( $one, 'crm-probe' );
exec( 'rm -rf '. escapeshellarg( $features. '/CrmProbe' ) );
$oneRequest = crmRequest( $oneDir );
crmAccounts( $oneRequest );
crmWorkbench( $oneRequest, 'crm@example.org' );
$gone = ninoCall( $oneRequest, $post, [], 'crm/status' );
check( 'the data of a package that is removed is listed as that of a package that is not installed', $gone[1]['orphans'] === [ [ 'key' => 'crm-probe', 'package' => 'crm-probe', 'version' => 1, 'tables' => 1 ] ] );
$dropped = ninoCall( $oneRequest, $post, [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ], 'crm/drop-data' );
check( 'the data of a package that is removed is deleted on its typed name: the answer is the screen without it and the number of tables, the table is gone, the core is as it was and the status is whole', $dropped[0] === 200 && $dropped[1]['dropped'] === 1 && $dropped[1]['orphans'] === [] && (int) \Nino\Modules\Crm\Db::pdo( $oneRequest )->query( 'SELECT COUNT(*) FROM sqlite_master WHERE name LIKE \'crm\_probe%\' ESCAPE \'\\\'' )->fetchColumn() === 0
	&& crmShape( \Nino\Modules\Crm\Db::pdo( $oneRequest ) ) === crmShape( $reference ) && $dropped[1]['whole'] === true );
check( 'asked twice it is 404: the data is gone', ninoCall( $oneRequest, $post, [ 'key' => 'crm-probe', 'typed' => 'crm-probe' ], 'crm/drop-data' )[0] === 404 );
crmCopy( $source. '/tests/fixtures/CrmProbe', $features. '/CrmProbe' );
// ...and the data another package points at is not taken away: the panel says so with a code of its own
[ $rp, $rDir, $rAfter ] = $ghost( 'crm6-ghost-panel', [
	'crm-probe-two' => [ 'tables' => [ 'crm_two_docs' ], 'create' => [ $docs ] ],
	'crm-probe' => [ 'tables' => [ 'crm_probe_items' ], 'create' => [ 'CREATE TABLE crm_probe_items ( id INTEGER PRIMARY KEY, doc INTEGER REFERENCES crm_two_docs( id ) ON DELETE RESTRICT )' ] ],
], [ 'INSERT INTO crm_two_docs VALUES ( 1, 1, NULL )', 'INSERT INTO crm_probe_items VALUES ( 1, 1 )' ], 'crm-probe-two' );
ninoWarnings();
$rPanel = crmRequest( $rDir );
crmAccounts( $rPanel );
crmWorkbench( $rPanel, 'crm@example.org' );
$rAnswer = ninoCall( $rPanel, $post, [ 'key' => 'crm-probe-two', 'typed' => 'crm-probe-two' ], 'crm/drop-data' );
$finish();
check( 'the data of a package that another package has a key to is refused by the panel with a 409 and a code of its own, and nothing is lost', $rAnswer[0] === 409 && ( $rAnswer[1]['code'] ?? '' ) === 'crm_drop_referenced' && (int) \Nino\Modules\Crm\Db::pdo( $rPanel )->query( 'SELECT COUNT(*) FROM crm_two_docs' )->fetchColumn() === 1 );
crmRemove( $rDir );
crmRemove( $oneDir );
crmRemove( $pwDir );
$_SESSION = [];
}
catch( \Throwable $crash ) {
	crmCrashed( 'The panel', $crash );
}


// --- What a package that docks to the core finds, and the core finds of it --------------------------------------------------

echo "\nThe package of the suite docks to every place\n";

try {

$orders = [];

foreach( [ [ '\\Nino\\Modules\\Crm', '\\Nino\\Modules\\CrmProbe' ], [ '\\Nino\\Modules\\CrmProbe', '\\Nino\\Modules\\Crm' ] ] as $order ) {

	[ $dockProject, $dockDir, $unused ] = crmWorld( 'crm6-dock' );
	$dock = crmRequest( $dockDir, false );

	foreach( $order as $class )
		$class::init( $dock );

	\Nino\Html::addFills( $dock, [ '[[/project/website/general/url]]' => 'www.example.org' ], '*' );
	\Nino\Modules\Crm\Db::pdo( $dock );
	$orders[] = [ 'versions' => \Nino\Modules\Crm\Db::versions( $dock ), 'defaults' => \Nino\Modules\Crm\Settings::defaults( $dock ), 'categories' => ( static function( array $categories ): array { ksort( $categories ); return $categories; } )( \Nino\Modules\Crm\Sequences::categories( $dock ) ), 'lines' => array_column( \Nino\Modules\Crm\Status::lines( $dock ), 'state', 'key' ),
		'shared' => [ \Nino\Modules\Crm\Hosts::shared( $dock, '/.probe/zip' ), \Nino\Modules\Crm\Hosts::shared( $dock, '/.probe/files/x' ), \Nino\Modules\Crm\Hosts::shared( $dock, '/.probe/other' ) ], 'band' => \Nino\Html::renderHtml( $dock, '<i>[mail-head]</i>' ), 'view' => \Nino\Modules\Crm\Settings::view( $dock ),
		'words' => ( static function( array $roots ): array { ksort( $roots ); return $roots; } )( $dock['./crm/mail/roots'] ?? [] ), 'tables' => array_keys( crmShape( \Nino\Modules\Crm\Db::pdo( $dock ) ) ) ];
	crmRemove( $dockDir );
}

check( 'a package docks to the schema, the settings, the number sequences, the status, the shared paths, the words and the band of the mails and the view of the settings - and in either order of the registration of the core and the package the result is the same, point by point', $orders[0] === $orders[1]
	&& $orders[0]['versions'] === [ 'crm' => 1, 'crm-probe' => 1 ] && array_keys( $orders[0]['defaults'] ) === [ 'start_client', 'start_project', 'probe_limit', 'seller_name', 'brand_word', 'brand_domain', 'brand_color', 'brand_ink', 'crm_host', 'app_host', 'probe_host' ] && $orders[0]['shared'] === [ true, true, false ]
	&& array_keys( $orders[0]['categories'] ) === [ 'client', 'probe-invoice' ] && $orders[0]['lines']['probe-line'] === 'ok' && in_array( 'table crm_probe_items', $orders[0]['tables'], true ) === true && array_keys( $orders[0]['words'] ) === [ '/feature/crm-probe/', '/feature/crm/' ] );
check( 'the core names no package: no file of the feature outside its tests names the package of the suite, another feature of the catalogue, or a class that begins with Crm after the module - the one place that says a package is on is featureOn()', ( static function() use ( $dir ): bool {

	$named = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $item ) {

		$source = (string) file_get_contents( (string) $item );

		if( preg_match( '/crm-probe(?!\.php)|CrmProbe|\\\\Nino\\\\Modules\\\\(?:Members|Files|Pdf|Sign|Events|Review|Forms|Mailer)\b|\\\\Nino\\\\Modules\\\\Crm[A-Z]/', $source ) === 1 )
			$named[] = (string) $item;
	}

	return $named === [];
} )() === true );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The package of the suite docks to every place', $crash );
}


// --- upgrade() --------------------------------------------------------------------------------------------------------------

echo "\nupgrade()\n";

try {

[ $up, $upDir, $upRequest ] = crmWorld( 'crm6-upgrade' );
$upFile = $upDir. '/private/data/crm.sqlite';
$first = \Nino\Modules\Crm::upgrade( $upRequest, '1.0.0' );
$made = crmFingerprint( $upFile );
check( 'upgrade() raises the database to the schemas of this version - on a file that is not there it makes it - answers true, and a second call changes nothing: it is idempotent', $first === true && \Nino\Modules\Crm\Db::kind( $upFile ) === 'ours' && \Nino\Modules\Crm::upgrade( $upRequest, '1.0.0' ) === true && crmFingerprint( $upFile ) === $made && ninoWarnings() === [] );
$stale = crmRequest( $upDir, false, [ './crm-probe/version' => 1 ] );
\Nino\Modules\Crm\Db::pdo( $stale )->exec( 'UPDATE schema_versions SET version = 9 WHERE package = \'crm\'' );
$upNew = crmRequest( $upDir );
ninoWarnings();
$refusal = \Nino\Modules\Crm::upgrade( $upNew, '1.0.0' );
check( 'an update that the file refuses - it holds a newer schema of the core than this code - is refused: upgrade() answers false with one warning that names no path, so that the kernel asks again at the next activation', $refusal === false && count( ninoWarnings() ) === 1 );
crmRemove( $upDir );

// What the suite made of its own is taken away: the sandboxes it asked the harness for, and what its processes left
array_map( 'crmRemove', array_unique( $GLOBALS['crmSandboxes'] ) );
check( 'the suite leaves no sandbox of its own behind but the one the harness removes at the end', array_filter( $GLOBALS['crmSandboxes'], static fn( string $folder ): bool => file_exists( $folder ) === true ) === [] && count( $GLOBALS['crmSandboxes'] ) >= 14 );
}
catch( \Throwable $crash ) {
	crmCrashed( 'upgrade()', $crash );
}


// --- The backup: the copy of the database the kernel packs ------------------------------------------------------------------

echo "\nThe backup\n";

try {

[ $bk, $bkDir, $bkRequest ] = crmWorld( 'crm7-backup' );
$bkFile = $bkDir. '/private/data/crm.sqlite';
$bkData = dirname( $bkFile );
$bkRows = static fn( string $file ): array => ( static function() use ( $file ): array {
	$pdo = new \PDO( 'sqlite:'. $file );
	$rows = [ (int) $pdo->query( 'PRAGMA application_id' )->fetchColumn(), (string) $pdo->query( 'PRAGMA integrity_check' )->fetchColumn(), $pdo->query( 'SELECT key, value FROM settings ORDER BY key' )->fetchAll( \PDO::FETCH_ASSOC ), $pdo->query( 'SELECT package, version FROM schema_versions ORDER BY package' )->fetchAll( \PDO::FETCH_ASSOC ) ];

	return $rows;
} )();
$bkFill = static function( int $count ) use ( $bkDir ): void {

	$request = crmRequest( $bkDir );
	$pdo = \Nino\Modules\Crm\Db::pdo( $request );
	$put = $pdo->prepare( 'INSERT OR REPLACE INTO settings ( key, value ) VALUES ( :key, :value )' );

	for( $i = 0; $i < $count; $i++ )
		$put->execute( [ 'key' => 'bk.'. sprintf( '%04d', $i ), 'value' => str_repeat( chr( 65 + $i % 26 ), 1500 ) ] );
};

// No database yet: the first days of the feature
check( 'the project has no database yet', is_file( $bkFile ) === false );
$dataBefore = crmNames( $bkData );
$run = crmBackup( $bkDir );
check( 'no database: the backup is made, hands over no copy and the archive has no database; nothing is made for it - no file, no folder below data/, nothing in the request - and no warning is raised',
	is_string( $run['answer'] ) === true && is_array( $run['archive']['files'] ) === true && isset( $run['archive']['files']['data/crm.sqlite'] ) === false && ( $run['archive']['stamp']['copies'] ?? [] ) === [] && is_file( $bkFile ) === false && crmNames( $bkData ) === $dataBefore && $run['kept'] === [] && $run['warnings'] === [] );

// A database that is the core's: a whole copy that is a state
$bkFill( 60 );
// Rows that were deleted leave free pages in the file; a copy made by VACUUM INTO has none
$bkRequestOwn = crmRequest( $bkDir );
\Nino\Modules\Crm\Db::exec( $bkRequestOwn, 'DELETE FROM settings WHERE key >= \'bk.0040\' AND key < \'bk.0060\'' );
$bkRequestOwn = null;
$bkFree = static fn( string $file ): int => (int) ( new \PDO( 'sqlite:'. $file ) )->query( 'PRAGMA freelist_count' )->fetchColumn();
$live = crmFingerprint( $bkFile );
$names = crmNames( $bkData );
$expect = $bkRows( $bkFile );
$run = crmBackup( $bkDir );
$copy = $run['archive']['folder'] ?? '';
check( 'a database: the backup is made through the kernel and its archive holds the copy where the live file stands, whole - the application id of the core, integrity_check ok, and the rows of the live file, the register of the schemas included - and the stamp names it as handed over',
	is_string( $run['answer'] ) === true && ( $run['archive']['stamp']['copies'] ?? [] ) === [ 'data/crm.sqlite' ] && is_file( $copy. '/data/crm.sqlite' ) === true && $bkRows( $copy. '/data/crm.sqlite' ) === $expect && $expect[0] === \Nino\Modules\Crm\Db::APPLICATION_ID && $expect[1] === 'ok' && count( $expect[2] ) >= 40 );
check( '...as a copy that VACUUM INTO makes and not the file as it lies: the free pages the live file has are not in it, and it is the smaller', $bkFree( $bkFile ) > 0 && $bkFree( $copy. '/data/crm.sqlite' ) === 0 && filesize( $copy. '/data/crm.sqlite' ) < filesize( $bkFile ) );
check( '...with no file of a log beside the copy, none beside the live file, and the live file as it was: not a byte written, no file added below data/, no connection of the request opened',
	array_filter( array_keys( $run['archive']['files'] ?? [] ), static fn( string $name ): bool => preg_match( '/crm\.sqlite-(journal|wal|shm)$/', $name ) === 1 ) === [] && glob( $bkFile. '-*' ) === [] && crmFingerprint( $bkFile ) === $live && crmNames( $bkData ) === $names && $run['kept'] === [] && $run['warnings'] === [] );
$direct = $bkDir. '/staging-direct';
mkdir( $direct, 0700 );
$handed = [ 'dataDir' => $bkData, 'staging' => $direct ];
$own = crmRequest( $bkDir );
\Nino\Modules\Crm::callbackBackup( $own, $handed );
check( 'the callback itself leaves exactly the copy in the folder it is given: no file of a log, no half-made file, nothing beside it - and it touches nothing of the request', crmNames( $direct ) === [ 'crm.sqlite' ] && $bkRows( $direct. '/crm.sqlite' ) === $expect && array_intersect( array_keys( $own ), [ './crm/db/connections', './crm/db/ensured' ] ) === [] );
$again = false;

try {
	\Nino\Modules\Crm::callbackBackup( $own, $handed );
}
catch( \PDOException ) {
	$again = true;
}

check( 'a copy that is in the way is not written over: the callback throws and leaves the one that is there', $again === true && crmNames( $direct ) === [ 'crm.sqlite' ] && $bkRows( $direct. '/crm.sqlite' ) === $expect );
unlink( $direct. '/crm.sqlite' );
rmdir( $direct );
check( 'the callback is no state: neither the core nor the class of the database has a static field', ( new \ReflectionClass( \Nino\Modules\Crm::class ) )->getStaticProperties() === [] && ( new \ReflectionClass( \Nino\Modules\Crm\Db::class ) )->getStaticProperties() === [] );

// A writer that has begun a transaction and not ended it: SQLite lets a reader see the file as it was before it, and the copy is that state
$holder = crmWriter( $bkFile, '$db->exec( "BEGIN IMMEDIATE" ); $db->exec( "INSERT INTO settings ( key, value ) VALUES ( \'bk.held\', \'not yet\' )" );' );
$live = array_slice( crmFingerprint( $bkFile ), 0, 3 );

try {
	$run = crmBackup( $bkDir );
}
finally {
	crmRelease( $holder );
}

$copy = $run['archive']['folder'] ?? '';
$held = is_file( $copy. '/data/crm.sqlite' ) === true ? $bkRows( $copy. '/data/crm.sqlite' ) : [];
check( 'a writer that holds an open transaction while the backup runs: the backup is made at once, and its copy is the state before that transaction - whole, with the rows that were committed and not the one that was not',
	is_string( $run['answer'] ) === true && $run['seconds'] < 3 && ( $held[1] ?? '' ) === 'ok' && ( $held[0] ?? 0 ) === \Nino\Modules\Crm\Db::APPLICATION_ID && $held[2] === $expect[2] && in_array( 'bk.held', array_column( $held[2], 'key' ), true ) === false && array_slice( crmFingerprint( $bkFile ), 0, 3 ) === $live );

// A writer at its commit holds the file for readers: the copy cannot be made, and the backup ends with the core named, after the wait
$holder = crmHold( $bkFile );
$archives = glob( \Nino\Modules\Backups::dir( $bkRequest ). '/*.php' ) ?: [];
$live = array_slice( crmFingerprint( $bkFile ), 0, 3 );

try {
	$run = crmBackup( $bkDir );
}
finally {
	crmRelease( $holder );
}

check( 'a database that stays locked for longer than the wait is no copy: the backup ends with 500, the code backups_hook and the key of the core, after the five seconds the core waits for a lock and not much more, no archive is made, and the file is as it was',
	is_array( $run['answer'] ) === true && $run['answer'][0] === 500 && ( $run['answer'][2] ?? '' ) === 'backups_hook' && ( $run['answer'][3] ?? [] ) === [ 'crm' ] && $run['seconds'] > 4.5 && $run['seconds'] < 15 && ( glob( \Nino\Modules\Backups::dir( $bkRequest ). '/*.php' ) ?: [] ) === $archives && array_slice( crmFingerprint( $bkFile ), 0, 3 ) === $live && glob( $bkFile. '-*' ) === [] );
$run = crmBackup( $bkDir );
check( '...and once the lock is gone the next backup is made, with the copy', is_string( $run['answer'] ) === true && ( $run['archive']['stamp']['copies'] ?? [] ) === [ 'data/crm.sqlite' ] && $bkRows( $run['archive']['folder']. '/data/crm.sqlite' ) === $expect );

// A file that is not the core's - another database, a text, a file with nothing in it, a directory - is not handed over and not touched: the backup packs it as it lies, as it would without the callback
[ $fb, $fbDir ] = crmWorld( 'crm7-backup-foreign' );
$fbFile = $fbDir. '/private/data/crm.sqlite';
is_dir( dirname( $fbFile ) ) === true || mkdir( dirname( $fbFile ), 0755, true );
$fbCases = [
	'another database' => static function( string $file ): void { $other = new \PDO( 'sqlite:'. $file ); $other->exec( 'CREATE TABLE clients ( id INTEGER PRIMARY KEY, name TEXT )' ); $other->exec( 'INSERT INTO clients ( name ) VALUES ( \'Old\' )' ); },
	'a text' => static function( string $file ): void { file_put_contents( $file, "This is not a database\n". str_repeat( 'x', 200 ) ); },
	'a file with nothing in it' => static function( string $file ): void { file_put_contents( $file, '' ); },
	'a directory' => static function( string $file ): void { mkdir( $file ); file_put_contents( $file. '/inside.txt', 'in a directory' ); },
];

foreach( $fbCases as $label => $make ) {

	$make( $fbFile );
	$lies = is_dir( $fbFile ) === true ? [ 'inside.txt' => 'in a directory' ] : (string) file_get_contents( $fbFile );
	$kept = array_slice( crmFingerprint( $fbFile ), 0, 3 );
	$names = crmNames( dirname( $fbFile ) );
	$run = crmBackup( $fbDir );
	$packed = is_dir( $fbFile ) === true ? [ 'inside.txt' => (string) ( $run['archive']['files']['data/crm.sqlite/inside.txt'] ?? '' ) ] : ( $run['archive']['files']['data/crm.sqlite'] ?? null );

	check( 'a file at the place of the database that is '. $label. ' is not the core\'s: the backup goes on without a copy, the archive has it as it lies, the file is not opened, written, made whole or taken away - and nothing is made beside it',
		is_string( $run['answer'] ) === true && ( $run['archive']['stamp']['copies'] ?? [] ) === [] && $packed === $lies && ( is_dir( $fbFile ) === true || array_slice( crmFingerprint( $fbFile ), 0, 3 ) === $kept ) && crmNames( dirname( $fbFile ) ) === $names && glob( $fbFile. '-*' ) === [] && $run['kept'] === [] && $run['warnings'] === [] );

	if( is_dir( $fbFile ) === true ) {
		unlink( $fbFile. '/inside.txt' );
		rmdir( $fbFile );
	}
	else
		unlink( $fbFile );
}

// The database of the core is damaged: it is no state that can be handed over, and nothing falls back to the live file - the backup ends with the core named. The restore of a good backup is what is wanted then, and it is not kept from happening
$good = crmBackup( $bkDir );
$goodId = (string) $good['answer'];
$goodKey = (string) $good['key'];

// A damage of an index is one that the copy has as the file has: VACUUM INTO carries the trees of the indexes over as they are, and PRAGMA quick_check does not read an index against its table. The schema of the index is
// changed under the file (writable_schema) so that the entries it holds are not the ones its table asks for: wrong ones, none for some rows, or a key twice in a unique index
$bkIndex = static function( string $file, int $form ): void {

	$pdo = new \PDO( 'sqlite:'. $file, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
	$pdo->exec( 'CREATE TABLE bk_index ( id INTEGER PRIMARY KEY, a TEXT, b TEXT )' );
	$pdo->exec( 'WITH RECURSIVE n( i ) AS ( SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 60 ) INSERT INTO bk_index ( a, b ) SELECT \'a\'|| i, \'b\'|| ( i % 5 ) FROM n' );

	if( $form === 1 )
		$pdo->exec( 'CREATE INDEX bk_index_i ON bk_index ( a )' );
	elseif( $form === 2 )
		$pdo->exec( 'CREATE INDEX bk_index_i ON bk_index ( a ) WHERE id <= 30' );
	else
		$pdo->exec( 'CREATE INDEX bk_index_i ON bk_index ( b )' );

	$pdo->exec( 'PRAGMA writable_schema = ON' );
	$pdo->exec( 'UPDATE sqlite_schema SET sql = '. $pdo->quote( [ 1 => 'CREATE INDEX bk_index_i ON bk_index ( b )', 2 => 'CREATE INDEX bk_index_i ON bk_index ( a )', 3 => 'CREATE UNIQUE INDEX bk_index_i ON bk_index ( b )' ][$form] ). ' WHERE name = \'bk_index_i\'' );
};

foreach( [ 'an index has entries that are none of its table' => static function( string $file ) use ( $bkIndex ): void { $bkIndex( $file, 1 ); },
	'an index has no entry for rows of its table' => static function( string $file ) use ( $bkIndex ): void { $bkIndex( $file, 2 ); },
	'a unique index holds a key twice' => static function( string $file ) use ( $bkIndex ): void { $bkIndex( $file, 3 ); },
	'pages of the file are overwritten' => static function( string $file ): void { $h = fopen( $file, 'r+b' ); fseek( $h, 4096 * 4 ); fwrite( $h, str_repeat( "\xAB", 4096 * 6 ) ); fclose( $h ); },
	'the count of the free pages in its header is wrong (a damage a copy does not show)' => static function( string $file ): void { $h = fopen( $file, 'r+b' ); fseek( $h, 36 ); fwrite( $h, pack( 'N', 9999 ) ); fclose( $h ); },
	'the file is cut off in the middle of its pages' => static function( string $file ): void { $h = fopen( $file, 'r+b' ); ftruncate( $h, 4096 * 7 + 100 ); fclose( $h ); } ] as $label => $damage ) {

	copy( $good['archive']['folder']. '/data/crm.sqlite', $bkFile );
	$damage( $bkFile );
	$kept = array_slice( crmFingerprint( $bkFile ), 0, 3 );

	// The pin of an index means nothing where the file shows its damage to the quick check the callback makes of the file: it is the copy that has to be looked at
	if( str_contains( $label, 'index' ) === true ) {

		$seen = new \PDO( 'sqlite:file:'. $bkFile. '?mode=ro' );
		check( 'a database of the core where '. $label. ': the damage is none that PRAGMA quick_check finds, and PRAGMA integrity_check does', $seen->query( 'PRAGMA quick_check' )->fetchColumn() === 'ok' && $seen->query( 'PRAGMA integrity_check' )->fetchColumn() !== 'ok' );
		$seen = null;
		$kept = array_slice( crmFingerprint( $bkFile ), 0, 3 );
	}

	$archives = glob( \Nino\Modules\Backups::dir( $bkRequest ). '/*.php' ) ?: [];
	$direct = $bkDir. '/staging-damaged';
	mkdir( $direct, 0700 );
	$handed = [ 'dataDir' => $bkData, 'staging' => $direct ];
	$own = crmRequest( $bkDir );
	$left = 'no throw';

	try {
		\Nino\Modules\Crm::callbackBackup( $own, $handed );
	}
	catch( \PDOException ) {
		$left = crmNames( $direct ) === [] ? 'nothing' : 'something';
	}

	rmdir( $direct );
	$run = crmBackup( $bkDir );

	check( 'a database of the core where '. $label. ': the callback itself throws and leaves nothing in the folder of the copy', $left === 'nothing' );
	check( 'a database of the core where '. $label. ': the backup ends with 500, the code backups_hook and the key of the core, no archive is made, the file is not touched and no file of a log is left beside it',
		\Nino\Modules\Crm\Db::kind( $bkFile ) === 'ours' && is_array( $run['answer'] ) === true && $run['answer'][0] === 500 && ( $run['answer'][2] ?? '' ) === 'backups_hook' && ( $run['answer'][3] ?? [] ) === [ 'crm' ] && ( glob( \Nino\Modules\Backups::dir( $bkRequest ). '/*.php' ) ?: [] ) === $archives
		&& array_slice( crmFingerprint( $bkFile ), 0, 3 ) === $kept && glob( $bkFile. '-*' ) === [] );
}

$notice = null;
$damaged = (string) file_get_contents( $bkFile );
$restored = \Nino\Modules\Backups\Admin::restore( $bkRequest, $goodId, $notice );
$snapshots = array_values( array_filter( array_map( static fn( string $file ): string => basename( $file, '.php' ), glob( \Nino\Modules\Backups::dir( $bkRequest ). '/pre-restore-*.php' ) ?: [] ) ) );
check( 'a restore of the good backup over a database that SQLite opens but cannot overwrite - the file is cut off in the middle of its pages - is refused with the core named and a sentence of its own: the answer is 500 with backups_restore_callback, the file is the bytes it was, and nothing is left beside it',
	is_array( $restored ) === true && $restored[0] === 500 && ( $restored[2] ?? '' ) === 'backups_restore_callback' && ( $restored[4]['features'] ?? [] ) === [ 'crm' ] && (string) file_get_contents( $bkFile ) === $damaged && ( glob( $bkFile. '-*' ) ?: [] ) === [] && ( glob( $bkFile. '.restore-*' ) ?: [] ) === [] );
check( '...and the snapshot it took first holds the damaged file as it lay, byte for byte, with no copy of the core in it', count( $snapshots ) >= 1 && ( static function() use ( &$bkRequest, $snapshots, $damaged, $goodKey ): bool { $held = crmArchive( $bkRequest, end( $snapshots ), $goodKey ); return ( $held['files']['data/crm.sqlite'] ?? null ) === $damaged && ( $held['stamp']['copies'] ?? [] ) === []; } )() );

// The rare rest, and the way out: the file is taken away, and the next restore writes the copy where there is no file
unlink( $bkFile );
$notice = null;
$restored = \Nino\Modules\Backups\Admin::restore( $bkRequest, $goodId, $notice );
check( 'a restore of the good backup where the file that could not be overwritten is gone goes through: whole, with the rows it had', $restored === true && $bkRows( $bkFile ) === $expect && ( glob( $bkFile. '-*' ) ?: [] ) === [] );

// The file that is in the way is whatever it is: pages that are overwritten, a file of no byte, a file of text, a header that is cut off, a database whose index lacks rows - each is replaced, and the file is the archive's afterwards
$replaced = [];

foreach( [
	'pages overwritten' => static function( string $file ): void { $h = fopen( $file, 'r+b' ); fseek( $h, 4096 * 2 ); fwrite( $h, str_repeat( "\xAB", 4096 ) ); fclose( $h ); },
	'no byte' => static function( string $file ): void { file_put_contents( $file, '' ); },
	'text' => static function( string $file ): void { file_put_contents( $file, str_repeat( 'a text that is no database ', 400 ) ); },
	'a header that is cut off' => static function( string $file ): void { file_put_contents( $file, substr( (string) file_get_contents( $file ), 0, 50 ) ); },
	'an index that lacks rows' => static function( string $file ) use ( $bkIndex ): void { $bkIndex( $file, 2 ); },
] as $label => $break ) {
	$noticeOf = null;
	$break( $bkFile );
	$answer = \Nino\Modules\Backups\Admin::restore( $bkRequest, $goodId, $noticeOf );
	$replaced[$label] = $answer === true && $bkRows( $bkFile ) === $expect && ( glob( $bkFile. '-*' ) ?: [] ) === [] && ( glob( $bkFile. '.restore-*' ) ?: [] ) === [];
}

if( array_values( array_unique( $replaced ) ) !== [ true ] )
	echo '        saw: '. json_encode( $replaced ). "\n";

check( 'a live file that is the core\'s and is damaged in its pages, has no byte, is a text, has a header that is cut off or has an index that lacks rows is replaced by the restore of the panel: the answer is true, the file is the copy of the archive, whole, and nothing is left beside it', array_values( array_unique( $replaced ) ) === [ true ] );

// A database of another application in that place is not written over, and a file that another process holds a lock on is not replaced
$wholeBytes = (string) file_get_contents( $bkFile );
$other = new \PDO( 'sqlite:'. $bkFile );
$other->exec( 'PRAGMA application_id = 77' );
$other = null;
$otherBytes = (string) file_get_contents( $bkFile );
$noticeOf = null;
$answer = \Nino\Modules\Backups\Admin::restore( $bkRequest, $goodId, $noticeOf );
check( 'a valid database of another application in the place of the file is not written over: the answer is 500 with the core named, and the file is the bytes it was', is_array( $answer ) === true && $answer[0] === 500 && ( $answer[4]['features'] ?? [] ) === [ 'crm' ] && (string) file_get_contents( $bkFile ) === $otherBytes && ( glob( $bkFile. '-*' ) ?: [] ) === [] && ( glob( $bkFile. '.restore-*' ) ?: [] ) === [] );
file_put_contents( $bkFile, $wholeBytes );
$bkIndex( $bkFile, 2 );
$damagedBytes = (string) file_get_contents( $bkFile );
$holder = crmWriter( $bkFile, '$db->exec( "BEGIN IMMEDIATE" ); $db->exec( "INSERT INTO settings ( key, value ) VALUES ( \'held.row\', \'yes\' )" );' );
$noticeOf = null;
$answer = \Nino\Modules\Backups\Admin::restore( $bkRequest, $goodId, $noticeOf );
crmRelease( $holder );
check( 'a file that is damaged and that another process holds a lock on is not replaced - it is in use: the answer is 500 with the core named, and the file is the bytes it was, with nothing made on the way', is_array( $answer ) === true && $answer[0] === 500 && ( $answer[4]['features'] ?? [] ) === [ 'crm' ] && (string) file_get_contents( $bkFile ) === $damagedBytes && ( glob( $bkFile. '-*' ) ?: [] ) === [] && ( glob( $bkFile. '.restore-*' ) ?: [] ) === [] );
file_put_contents( $bkFile, $wholeBytes );

// A copy in the archive that is not to be written: the panel's own function, with the archive that holds it. The live database is the bytes it was, nothing of the core is left in the staging for the kernel to copy over it, and the answer
// names the core (backups_restore_callback). A copy in the log mode of writes is written, and the file is in the DELETE mode
$goodBytes = (string) file_get_contents( $bkFile );
$refusals = [];

foreach( [
	'damaged in its bytes' => static function( string $file ): void { file_put_contents( $file, random_bytes( 8192 ) ); },
	'of no byte' => static function( string $file ): void { file_put_contents( $file, '' ); },
	'of another application id' => static function( string $file ): void { $p = new \PDO( 'sqlite:'. $file ); $p->exec( 'PRAGMA application_id = 77' ); },
	'of a newer version' => static function( string $file ): void { $p = new \PDO( 'sqlite:'. $file ); $p->exec( 'UPDATE schema_versions SET version = '. ( \Nino\Modules\Crm\Db::VERSION + 1 ). ' WHERE package = \'crm\'' ); },
	'with no register of the core' => static function( string $file ): void { $p = new \PDO( 'sqlite:'. $file ); $p->exec( 'DELETE FROM schema_versions' ); },
	'with an index that lacks rows' => static function( string $file ) use ( $bkIndex ): void { $bkIndex( $file, 2 ); },
] as $label => $make ) {
	file_put_contents( $bkFile, $goodBytes );
	$make( $bkFile );
	$id = crmBackupRaw( $bkDir );
	file_put_contents( $bkFile, $goodBytes );
	$noticeOf = null;
	$answer = is_string( $id ) === true ? \Nino\Modules\Backups\Admin::restore( $bkRequest, $id, $noticeOf ) : $id;
	$refusals[$label] = is_array( $answer ) === true && $answer[0] === 500 && ( $answer[2] ?? '' ) === 'backups_restore_callback' && ( $answer[4]['features'] ?? [] ) === [ 'crm' ] && (string) file_get_contents( $bkFile ) === $goodBytes && glob( $bkFile. '-*' ) === [];
}

if( array_values( array_unique( $refusals ) ) !== [ true ] )
	echo '        saw: '. json_encode( $refusals ). "\n";
check( 'a copy in the archive that is damaged, of no byte, of another application id, of a newer version, with no register of the core or with an index that lacks rows is not written by the restore of the panel, and the kernel copies nothing over the live database: the answer is 500 with backups_restore_callback and the core named, and the live file is the bytes it was', array_values( array_unique( $refusals ) ) === [ true ] );
check( 'a restore with a live database that another process holds longer than the wait is the same: 500 backups_restore_callback, the live file is the bytes it was, and what the holder wrote is its own', ( static function() use ( $bkFile, $bkDir, $bkRequest, $goodBytes ): bool {

	file_put_contents( $bkFile, $goodBytes );
	$id = crmBackupRaw( $bkDir );
	$holder = crmWriter( $bkFile, '$db->exec( "BEGIN IMMEDIATE" ); $db->exec( "INSERT INTO settings ( key, value ) VALUES ( \'held.row\', \'yes\' )" );' );
	$noticeOf = null;
	$answer = is_string( $id ) === true ? \Nino\Modules\Backups\Admin::restore( $bkRequest, $id, $noticeOf ) : $id;
	crmRelease( $holder );
	$same = (string) file_get_contents( $bkFile ) === $goodBytes;

	return is_array( $answer ) === true && in_array( $answer[2] ?? '', [ 'backups_restore_callback', 'backups_restore_both' ], true ) === true && $same === true;
} )() );

// The restore through the code of the kernel - the panel's own function, which asks the callback of the core - while a process of its own writes to the database again and again: the file is whole after every restore, and what the writer
// had committed is in it or is not, and nothing else is the matter; the state after the last one is the archive's, with what the writer wrote since
$writerFile = $bkDir. '/writer.stop';
$writerProgram = '$db = new PDO( "sqlite:". $argv[1], null, null, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] ); $db->exec( "PRAGMA busy_timeout = 5000" ); echo "up\n"; fflush( STDOUT ); $n = 0; $fails = 0; while( is_file( $argv[2] ) === false ) { try { $db->exec( "INSERT INTO settings ( key, value ) VALUES ( \'writer.". ( $n++ ). "\', \'x\' ) ON CONFLICT( key ) DO NOTHING" ); } catch( Throwable $e ) { $fails++; } usleep( 300 ); } echo json_encode( [ $n, $fails ] );';
$writer = proc_open( [ PHP_BINARY, '-r', $writerProgram, $bkFile, $writerFile ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $writerPipes );
fgets( $writerPipes[1] );
$answers = [];

for( $round = 0; $round < 6; $round++ ) {
	$noticeOf = null;
	$answers[] = \Nino\Modules\Backups\Admin::restore( $bkRequest, $goodId, $noticeOf );
	usleep( 20000 );
}

touch( $writerFile );
$writerSaid = json_decode( (string) stream_get_contents( $writerPipes[1] ), true );
stream_get_contents( $writerPipes[2] );
proc_close( $writer );
unlink( $writerFile );
$after = new \PDO( 'sqlite:'. $bkFile );
$codes = array_map( static fn( mixed $one ): string => $one === true ? 'true' : ( is_array( $one ) === true ? (string) ( $one[2] ?? '' ) : 'other' ), $answers );
check( 'the restore of the panel, six times while another process writes to the database: the file is whole after it - integrity check and foreign keys find nothing, no file of a log is left, the register of the core is there - and no restore ends as "written while it was in use" (the copy over a live file is the damage the callback of the core keeps from it)',
	$after->query( 'PRAGMA integrity_check' )->fetchColumn() === 'ok' && $after->query( 'PRAGMA foreign_key_check' )->fetchAll() === [] && glob( $bkFile. '-*' ) === []
	&& array_diff( $codes, [ 'true', 'backups_restore_callback', 'backups_restore_inuse', 'backups_restore_both' ] ) === [] && is_array( $writerSaid ) === true && (int) $after->query( 'SELECT COUNT(*) FROM schema_versions WHERE package = \'crm\'' )->fetchColumn() === 1 );
$after = null;

// The copy is looked at before it is handed over, whole: a copy that is damaged in an index or has the application id of another is none
$verify = $bkDir. '/verify.sqlite';
copy( $bkFile, $verify );
$seen = crmFingerprint( $verify );
$verified = [];

foreach( [ 'a copy that is whole' => null, 'a copy of another application id' => static function( \PDO $pdo ): void { $pdo->exec( 'PRAGMA application_id = 77' ); }, 'a copy with an index that lacks rows' => static function( \PDO $pdo ) use ( $bkIndex, $verify ): void { $bkIndex( $verify, 2 ); } ] as $what => $break ) {

	copy( $bkFile, $verify );

	if( $break !== null ) {
		$pdo = new \PDO( 'sqlite:'. $verify, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
		$break( $pdo );
		$pdo = null;
	}

	$seen = crmFingerprint( $verify );
	$threw = false;

	try {
		\Nino\Modules\Crm\Db::verifyCopy( $verify );
	}
	catch( \PDOException ) {
		$threw = true;
	}

	$verified[$what] = [ $threw, crmFingerprint( $verify ) === $seen ];
}

check( 'Db::verifyCopy() lets a whole copy pass and throws for a copy of another application id and for one whose index lacks rows, and writes nothing to the copy it looks at', $verified === [ 'a copy that is whole' => [ false, true ], 'a copy of another application id' => [ true, true ], 'a copy with an index that lacks rows' => [ true, true ] ] );
unlink( $verify );

// The look at a copy opens it for reading only: a journal that a writer that was ended left beside it stays where it is, and the file keeps its bytes and its time. A copy that is opened for writing is rolled back by SQLite
// (made by taking the file and its journal while a transaction of more than the cache is open: the state of a writer that was ended in the middle of it)
$hotLive = $bkDir. '/hot-live.sqlite';
$hot = $bkDir. '/hot.sqlite';
$pdo = new \PDO( 'sqlite:'. $hotLive, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
$pdo->exec( 'PRAGMA application_id = '. \Nino\Modules\Crm\Db::APPLICATION_ID );
$pdo->exec( 'CREATE TABLE hot ( id INTEGER PRIMARY KEY, v TEXT )' );
$pdo->beginTransaction();

for( $row = 0; $row < 300; $row++ )
	$pdo->exec( "INSERT INTO hot ( v ) VALUES ( '". str_repeat( 'x', 200 ). "' )" );

$pdo->commit();
$pdo->exec( 'PRAGMA cache_size = 2' );
$pdo->beginTransaction();
$pdo->exec( "UPDATE hot SET v = '". str_repeat( 'y', 200 ). "'" );
$premise = is_file( $hotLive. '-journal' ) === true && filesize( $hotLive. '-journal' ) > 4096;
copy( $hotLive, $hot );
copy( $hotLive. '-journal', $hot. '-journal' );
$pdo->rollBack();
$pdo = null;
unlink( $hotLive );
touch( $hot, 1700000000 );
$hotSeen = [ hash_file( 'sha256', $hot ), filemtime( $hot ), hash_file( 'sha256', $hot. '-journal' ), glob( $bkDir. '/hot.sqlite*' ) ];
$hotThrew = false;

try {
	\Nino\Modules\Crm\Db::verifyCopy( $hot );
}
catch( \PDOException ) {
	$hotThrew = true;
}

clearstatcache();
check( 'Db::verifyCopy() opens the copy for reading only: the copy that a writer left a hot journal beside is not rolled back by it - it throws, and the file keeps its bytes and its time, the journal stays and no file is added', $premise === true && $hotThrew === true && [ hash_file( 'sha256', $hot ), filemtime( $hot ), hash_file( 'sha256', $hot. '-journal' ), glob( $bkDir. '/hot.sqlite*' ) ] === $hotSeen );
array_map( 'unlink', glob( $bkDir. '/hot.sqlite*' ) ?: [] );

// A callback that is not told where the data is and where the copy goes does nothing and says so
$handedBad = [];

foreach( [ 'no folder of the data' => [ 'staging' => $bkDir ], 'no folder of the copy' => [ 'dataDir' => $bkData ], 'a folder of the data that is no text' => [ 'dataDir' => 7, 'staging' => $bkDir ], 'an empty folder of the copy' => [ 'dataDir' => $bkData, 'staging' => '' ] ] as $what => $args ) {

	$names = crmNames( $bkDir );
	$own = crmRequest( $bkDir );
	$told = 'no throw';

	try {
		\Nino\Modules\Crm::callbackBackup( $own, $args );
	}
	catch( \RuntimeException ) {
		$told = 'threw';
	}

	$handedBad[$what] = $told === 'threw' && crmNames( $bkDir ) === $names;
}

check( 'the callback of the backup that is not told where the data is and where the copy goes throws and makes nothing', array_filter( $handedBad ) === $handedBad && count( $handedBad ) === 4 );

// A writer that was ended in the middle of a transaction leaves its journal beside the file: the callback opens the file for writing, SQLite rolls the journal back before anything is read, and the copy is the state before that transaction
$before = $bkRows( $bkFile );
$crash = strtr( <<<'CODE'
$p = new PDO( 'sqlite:FILE' );
$p->exec( 'PRAGMA cache_size = 2' );
$p->exec( 'BEGIN IMMEDIATE' );
for( $i = 0; $i < 400; $i++ )
	$p->exec( "INSERT INTO settings ( key, value ) VALUES ( 'crash.$i', '" . str_repeat( 'x', 1200 ) . "' )" );
$p->exec( "UPDATE settings SET value = 'HALF' WHERE key < 'bk.0010'" );
$p->exec( "UPDATE settings SET value = 'HALF' WHERE key >= 'bk.0030'" );
function_exists( 'posix_kill' ) === true ? posix_kill( getmypid(), 9 ) : shell_exec( 'kill -9 ' . getmypid() );
CODE, [ 'FILE' => $bkFile ] );
exec( escapeshellarg( PHP_BINARY ). ' -r '. escapeshellarg( $crash ). ' 2>&1', $ended, $endedCode );
clearstatcache();
$lying = $bkDir. '/hot-alone.sqlite';
copy( $bkFile, $lying );
$alone = new \PDO( 'sqlite:'. $lying, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );

try {
	$aloneState = (string) $alone->query( 'PRAGMA integrity_check' )->fetchColumn() !== 'ok' || (int) $alone->query( 'SELECT count(*) FROM settings WHERE value = \'HALF\'' )->fetchColumn() > 0 ? 'torn' : 'whole';
}
catch( \PDOException ) {
	$aloneState = 'torn';
}

$alone = null;
unlink( $lying );
check( 'the premise of the pin of the hot journal: a writer that was ended is a journal beside the file, and the file without its journal is no state of the database', $endedCode !== 0 && is_file( $bkFile. '-journal' ) === true && $aloneState === 'torn' );
$run = crmBackup( $bkDir );
$copy = $run['archive']['folder'] ?? '';
$hot = is_file( $copy. '/data/crm.sqlite' ) === true ? $bkRows( $copy. '/data/crm.sqlite' ) : [];
check( 'a journal that a writer left beside the file is rolled back by the callback, which opens the file for writing: the backup is made, its copy is the state before the transaction that was ended - whole, with no row of it - and the journal is gone',
	is_string( $run['answer'] ) === true && $hot === $before && is_file( $bkFile. '-journal' ) === false && $bkRows( $bkFile ) === $before && ( $run['archive']['stamp']['copies'] ?? [] ) === [ 'data/crm.sqlite' ] );

// A writer that has written to the file - a transaction larger than its cache - holds it for readers, and the callback waits for it as for a writer at its commit
$size = filesize( $bkFile );
$holder = crmWriter( $bkFile, '$db->exec( "PRAGMA cache_size = 2" ); $db->exec( "BEGIN IMMEDIATE" ); for( $i = 0; $i < 200; $i++ ) $db->exec( "INSERT INTO settings ( key, value ) VALUES ( \'spill.". $i. "\', \'". str_repeat( "y", 1500 ). "\')" );' );

clearstatcache( true, $bkFile );
$spilled = filesize( $bkFile ) > $size;
$archives = glob( \Nino\Modules\Backups::dir( $bkRequest ). '/*.php' ) ?: [];

try {
	$run = crmBackup( $bkDir );
}
finally {
	crmRelease( $holder );
}

check( 'a writer whose transaction has written to the file is waited for, not seen in the copy: the file has grown, the backup ends after the five seconds with 500, backups_hook and the key of the core, and no archive is made',
	$spilled === true && is_array( $run['answer'] ) === true && $run['answer'][0] === 500 && ( $run['answer'][2] ?? '' ) === 'backups_hook' && ( $run['answer'][3] ?? [] ) === [ 'crm' ] && $run['seconds'] > 4.5 && $run['seconds'] < 15 && ( glob( \Nino\Modules\Backups::dir( $bkRequest ). '/*.php' ) ?: [] ) === $archives );
$run = crmBackup( $bkDir );
check( '...and once the writer has ended the next backup is made, with the copy of the file as the writer left it', is_string( $run['answer'] ) === true && $bkRows( $run['archive']['folder']. '/data/crm.sqlite' ) === $before );

// A database the core may not read is not "nothing": the callback says it cannot, and does not hand over, make or touch anything
$stage = $bkDir. '/staging-of-the-suite';
mkdir( $stage, 0777 );
chmod( $stage, 0777 );
$mode = fileperms( $bkFile ) & 0777;
chmod( $bkFile, 0000 );
$unreadable = crmFinish( crmStart( 'unreadable-worker', $bkDir, $bkFile, $stage ) );
chmod( $bkFile, $mode );
$said = json_decode( $unreadable[1], true );
check( 'a database that cannot be read: the callback of the backup throws and hands nothing over - it is not "no database" - and nothing is made in the folder of the copy; the file is the core\'s by its header all the same',
	$unreadable[0] === 0 && is_array( $said ) === true && $said['said'] === 'threw' && $said['kind'] === 'unreadable' && $said['staged'] === [] && \Nino\Modules\Crm\Db::kind( $bkFile ) === 'ours' );
rmdir( $stage );

crmRemove( $bkDir );
crmRemove( $fbDir );
}
catch( \Throwable $crash ) {
	crmCrashed( 'The backup', $crash );
}


// --- The script of the panel, where node is on the path ------------------------------------------------------------------------

echo "\nThe script of the panel\n";

try {

$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' )
	echo "  --  node is not on the path: the test of the script of the panel is not run\n";
else {

	$output = [];
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/crm-js-smoke.js' ). ' 2>&1', $output, $exit );
	echo implode( "\n", $output ), "\n";
	check( 'the test of the script of the panel runs and every one of its checks passes', $exit === 0 );
}
}
catch( \Throwable $crash ) {
	crmCrashed( 'The script of the panel', $crash );
}


// --- What the packages asked of the core a second time ------------------------------------------------------------------------

echo "\nWhat the packages asked of the core a second time\n";

try {

[ $c8, $c8Dir, $c8Request ] = crmWorld( 'crm-c8b' );

// A package turns a request away, or moves it, with the calls of Hosts
$c8Hosts = static function( string $how, string $uri, string $method = 'GET', string $host = 'www.example.org' ) use ( $c8Dir ): array {

	$request = crmRequest( $c8Dir, true, [ './crm-probe/hosts' => $how ] );
	$sent = crmVisit( $request, $host, $uri, $method );
	$answer = $sent;
	\Nino\Modules\Crm\Hosts::callbackRedirect( $request, $answer );
	$response = $answer['/nino/http/response'];

	return [ 'route' => (string) $sent['/nino/http/request']['uri'], 'method' => (string) $sent['/nino/http/request']['method'], 'status' => (int) $response['statusCode'], 'to' => (string) ( $response['header']['Location'] ?? '' ), 'retry' => (string) ( $response['header']['Retry-After'] ?? '' ) ];
};
check( 'refuse() and towards() are the interface of the class: public, with the hook that hands them their request in the description', array_map( static fn( string $method ): bool => ( new \ReflectionMethod( \Nino\Modules\Crm\Hosts::class, $method ) )->isPublic() === true && str_contains( (string) ( new \ReflectionMethod( \Nino\Modules\Crm\Hosts::class, $method ) )->getDocComment(), '/nino/http/request' ), [ 'refuse', 'towards' ] ) === [ true, true ] );
$c8Route = \Nino\Modules\Crm\Hosts::ROUTE;
$refused = [ $c8Hosts( 'refuse', '/_admin/probe/x', 'POST' ), $c8Hosts( 'refuse', '/_admin/probe/x?a=1' ) ];
$shut = $c8Hosts( 'closed', '/_admin/probe/x', 'POST' );
$other = $c8Hosts( 'refuse', '/page' );
check( 'a package turns a request away with Hosts::refuse(): the request is the route that answers 404 with the method GET whatever it came with, nothing is redirected, and a request of another place is not touched', $refused[0] === [ 'route' => $c8Route, 'method' => 'GET', 'status' => 404, 'to' => '', 'retry' => '' ] && $refused[1]['route'] === $c8Route && $refused[1]['status'] === 404
	&& $other['route'] === '/page' && $other['method'] === 'GET' );
check( 'a package that shuts a place for want of knowing whose it is says so with Hosts::refuse( closed ): the answer is 503 and wait five seconds, nothing is redirected', $shut['route'] === $c8Route && $shut['status'] === 503 && $shut['retry'] === '5' && $shut['to'] === '' );
ninoWarnings();
$moved = $c8Hosts( 'towards', '/_admin/probe/x?tab=1&b=2' );
$post = $c8Hosts( 'towards', '/_admin/probe/x', 'POST' );
$odd = array_map( static fn( string $uri ): array => $c8Hosts( 'towards', $uri ), [ '/_admin/probe/../x', '/_admin/probe//x', '/_admin/probe/%2e%2e/x', "/_admin/probe/x\r\nSet-Cookie: a=b" ] );
$same = $c8Hosts( 'towards', "/_admin/probe/x?a=1\r\nX: y" );
check( 'a package moves a place with Hosts::towards(): a GET in the plain spelling of a path of the workbench is sent to the host it names, 301, with the query; any other method, a spelling a web server reads differently and a blank are refused as a place that is not there, with no Location', $moved === [ 'route' => $c8Route, 'method' => 'GET', 'status' => 301, 'to' => 'https://moved.example.org/_admin/probe/x?tab=1&b=2', 'retry' => '' ]
	&& $post['status'] === 404 && $post['to'] === '' && array_filter( $odd, static fn( array $answer ): bool => $answer['status'] !== 404 || $answer['to'] !== '' || $answer['route'] !== \Nino\Modules\Crm\Hosts::ROUTE ) === [] && $same['status'] === 301 && str_contains( $same['to'], "\n" ) === false && ninoWarnings() === [] );
$bad = $c8Hosts( 'bad', '/_admin/probe/x' );
$said = ninoWarnings();
check( 'a host that is no name is none to move a place to: the request is refused as a place that is not there, one warning says it and it names no host', $bad['status'] === 404 && $bad['to'] === '' && $bad['route'] === $c8Route && count( $said ) === 1 && str_contains( $said[0], 'evil' ) === false && str_contains( $said[0], 'moved.example' ) === false );
$loop = $c8Hosts( 'towards', '/_admin/probe/x', 'GET', 'moved.example.org' );
$loopSaid = ninoWarnings();
check( 'a place is not moved to the host the request is on, which would send it round in a circle: the request is refused as a place that is not there, one warning says so, and the same call from another host moves it', $loop['status'] === 404 && $loop['to'] === '' && $loop['route'] === $c8Route && count( $loopSaid ) === 1 && $moved['status'] === 301 );

check( 'the description of deliver() says of the kind VOID what the outbox does with it - it counts against the cap of the client - and not that it counts against none', str_contains( (string) file_get_contents( $source. '/Mail/Mail.php' ), 'of no cap (VOID' ) === false && str_contains( (string) file_get_contents( $source. '/Mail/Mail.php' ), 'CLIENT, and VOID, which goes to nobody' ) === true );

// The mail: what became of each, and the kinds
$c8Mail = crmRequest( $c8Dir );
\Nino\Html::addFills( $c8Mail, [ '[[/project/company/contact/email]]' => 'owner@example.org', '[[/project/mail/address/envelope]]' => 'no-reply@example.org' ], '*' );
crmTransport( $c8Mail );
crmAccounts( $c8Mail );
$GLOBALS['crmMails'] = [];
$_SERVER['REMOTE_ADDR'] = '203.0.113.71';
$c8Make = static fn( string $to, string $subject = 'S' ): array => [ 'to' => $to, 'subject' => $subject, 'body' => '<p>B</p>', 'replyTo' => '' ];
$ids = [ 'stale' ];
$taken = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'a@example.org' ), $c8Make( 'not an address' ), 'no mail', $c8Make( 'b@example.org' ) ], 'the mails about ids', true, 'crm/ids', \Nino\Mail::CLIENT, $ids );
$said = ninoWarnings();
$again = [];
$takenAgain = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'a@example.org' ), $c8Make( 'not an address' ), 'no mail', $c8Make( 'b@example.org' ) ], 'the mails about ids', true, 'crm/ids', \Nino\Mail::CLIENT, $again );
ninoWarnings();
check( 'deliver() says which mail the outbox took: the id of each, in the order of the mails, and nothing for a mail it did not take - what stood in the list is gone, the list is as long as the mails, and the same mails under the same key are the same ids', $taken === 2 && count( $ids ) === 4 && preg_match( '/^[0-9a-f]{16}$/D', $ids[0] ) === 1 && $ids[1] === '' && $ids[2] === '' && preg_match( '/^[0-9a-f]{16}$/D', $ids[3] ) === 1 && $ids[0] !== $ids[3]
	&& count( $said ) === 2 && $takenAgain === 2 && $again === $ids );
$none = null;
$left = [];
$taken = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'c@example.org' ) ], 'a mail', false, '', \Nino\Mail::CLIENT, $none );
$empty = [ 'x' ];
\Nino\Modules\Crm\Mail::deliver( $c8Mail, [], 'no mails', true, '', \Nino\Mail::CLIENT, $empty );
check( 'a caller that has no use for the ids passes none and gets the number as before, and no mail at all is a list of none', $taken === 1 && $none !== null && count( $none ) === 1 && $empty === [] );
\Nino\Mail::deliverQueued( $c8Mail, 20 );
$GLOBALS['crmMails'] = [];
$c8Mail['/nino/mail/cap'] = 1;
$_SERVER['REMOTE_ADDR'] = '203.0.113.72';
$cap = [];
$limit = [ \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'cap1@example.org' ) ], 'a mail' ), \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'cap2@example.org' ), $c8Make( 'cap3@example.org' ) ], 'the mails about the cap', true, '', \Nino\Mail::CLIENT, $cap ) ];
$said = ninoWarnings();
check( 'a mail the cap of the hour refused has no id and the mails after it were not asked: the list is as long as the mails and holds nothing, and the answer is limit with the warning that names the cap', $limit === [ 1, 'limit' ] && $cap === [ '', '' ] && count( $said ) === 1 && str_contains( $said[0], 'cap' ) === true );
$mine = [];
$void = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'nobody@example.org' ) ], 'a mail to nobody', false, 'crm/void', \Nino\Mail::VOID, $mine );
$voidSaid = ninoWarnings();
check( 'a mail of the kind VOID counts against the cap of the hour of a client as a mail of a client does: over a full cap it is refused - limit, no id, one warning that names the cap', $void === 'limit' && $mine === [ '' ] && count( $voidSaid ) === 1 && str_contains( $voidSaid[0], 'cap' ) === true );
unset( $c8Mail['/nino/mail/cap'] );
$_SERVER['REMOTE_ADDR'] = '203.0.113.73';
\Nino\Mail::deliverQueued( $c8Mail, 20 );
$GLOBALS['crmMails'] = [];
$mine = [];
$void = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'nobody@example.org' ) ], 'a mail to nobody', false, 'crm/void2', \Nino\Mail::VOID, $mine );
$before = \Nino\Mail::state( $c8Mail, 'crm/void2' );
\Nino\Mail::deliverQueued( $c8Mail, 20 );
$sentTo = array_column( $GLOBALS['crmMails'], 'to' );
check( 'a mail of the kind VOID is taken with an id, waits under its key, is let go to nobody - the transport is not asked - and its key tells it was delivered', $void === 1 && preg_match( '/^[0-9a-f]{16}$/D', $mine[0] ?? '' ) === 1 && $before === \Nino\Mail::STATE_WAITING && $sentTo === [] && \Nino\Mail::state( $c8Mail, 'crm/void2' ) === \Nino\Mail::STATE_SENT && ninoWarnings() === [] );

// The kind OWNER: a signed-in account of the workbench and a POST
$_SERVER['REQUEST_METHOD'] = 'GET';
crmWorkbench( $c8Mail, '' );
$visitor = [];
$none = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'o1@example.org' ), $c8Make( 'o2@example.org' ) ], 'the mails of the owner', true, 'crm/owner-v', \Nino\Mail::OWNER, $visitor );
$visitorSaid = ninoWarnings();
crmWorkbench( $c8Mail, 'admin@example.org' );
$get = [];
$onGet = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'o3@example.org' ) ], 'the mail of the owner', false, 'crm/owner-g', \Nino\Mail::OWNER, $get );
$getSaid = ninoWarnings();
$_SERVER['REQUEST_METHOD'] = 'POST';
$owner = [];
$sent = \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'o4@example.org' ), $c8Make( 'o5@example.org' ) ], 'the mails of the owner', true, 'crm/owner', \Nino\Mail::OWNER, $owner );
$ownerSaid = ninoWarnings();
$states = [ \Nino\Mail::state( $c8Mail, 'crm/owner-0' ), \Nino\Mail::state( $c8Mail, 'crm/owner-v-0' ) ];
$c8Mail['/nino/mail/cap'] = 1;
$_SERVER['REMOTE_ADDR'] = '203.0.113.74';
$full = [ \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'cl1@example.org' ) ], 'a mail' ), \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'cl2@example.org' ) ], 'a mail' ), \Nino\Modules\Crm\Mail::deliver( $c8Mail, [ $c8Make( 'o6@example.org' ) ], 'the mail of the owner', false, 'crm/owner-c', \Nino\Mail::OWNER ) ];
$fullSaid = ninoWarnings();
unset( $c8Mail['/nino/mail/cap'] );
check( 'a mail of the kind OWNER needs a session of an account of the workbench: from a visitor each mail is refused with a warning that says session - no id, none taken -, from an account in a request that is no POST with one that says method, from an account in a POST it is taken with its id', $none === 0 && $visitor === [ '', '' ] && count( $visitorSaid ) === 2 && array_filter( $visitorSaid, static fn( string $line ): bool => str_contains( $line, '(session)' ) === false ) === []
	&& $onGet === 0 && $get === [ '' ] && count( $getSaid ) === 1 && str_contains( $getSaid[0], '(method)' ) === true && $sent === 2 && count( array_filter( $owner, static fn( string $id ): bool => preg_match( '/^[0-9a-f]{16}$/D', $id ) === 1 ) ) === 2 && $ownerSaid === [] );
check( 'a mail of the owner counts against no cap of the hour: where the cap of a client is full and refuses its second mail, the mail of the owner is taken, and what became of it is asked by its key: it waits, and the one nobody took is unknown', $full === [ 1, 'limit', 1 ] && count( $fullSaid ) === 1 && $states === [ \Nino\Mail::STATE_WAITING, \Nino\Mail::STATE_UNKNOWN ] );
$GLOBALS['crmMails'] = [];
\Nino\Mail::deliverQueued( $c8Mail, 40 );
$gotten = array_column( $GLOBALS['crmMails'], 'to' );
sort( $gotten );
check( 'the mails of the owner are delivered, each once, to the address it was made for, and their keys tell they were sent', array_values( array_intersect( $gotten, [ 'o4@example.org', 'o5@example.org', 'o6@example.org' ] ) ) === [ 'o4@example.org', 'o5@example.org', 'o6@example.org' ] && in_array( 'o1@example.org', $gotten, true ) === false && in_array( 'o3@example.org', $gotten, true ) === false
	&& \Nino\Mail::state( $c8Mail, 'crm/owner-0' ) === \Nino\Mail::STATE_SENT && \Nino\Mail::state( $c8Mail, 'crm/owner-1' ) === \Nino\Mail::STATE_SENT );
$_SERVER['REQUEST_METHOD'] = 'GET';
crmWorkbench( $c8Mail, '' );
$_SERVER['REMOTE_ADDR'] = '203.0.113.60';


// The settings: a key stands in front of any key, of the core, of its own package or of another
\Nino\Features::activate( $c8, 'crm-probe-two' );
$c8Clean = [ \Nino\Modules\Crm\Settings::class, 'host' ];
$c8Two = [ \Nino\Modules\CrmProbeTwo::class, 'clean' ];
$c8Key = static fn( string $key, ?string $before, int $rank = 500, string $package = 'crm-probe' ): array => [ 'package' => $package, 'key' => $key, 'default' => '', 'clean' => $package === 'crm-probe' ? [ \Nino\Modules\Crm\Settings::class, 'host' ] : [ \Nino\Modules\CrmProbeTwo::class, 'clean' ], 'before' => $before, 'rank' => $rank ];
$c8Order = static function( array ...$calls ) use ( $c8Dir, $c8Key ): array {

	$request = crmRequest( $c8Dir );
	ninoWarnings();
	$entered = [];

	foreach( $calls as $call )
		$entered[] = \Nino\Modules\Crm\Settings::add( $request, array_map( static fn( array $key ): array => $c8Key( ...$key ), $call ) );

	return [ array_keys( \Nino\Modules\Crm\Settings::all( $request ) ), $entered, ninoWarnings(), $request ];
};
$core = [ 'start_client', 'start_project', 'probe_limit', 'seller_name', 'brand_word', 'brand_domain', 'brand_color', 'brand_ink', 'crm_host', 'app_host', 'probe_host' ];
$put = static fn( array $keys, string $before, string ...$new ): array => array_merge( array_slice( $keys, 0, (int) array_search( $before, $keys, true ) ), $new, array_slice( $keys, (int) array_search( $before, $keys, true ) ) );
$own = $c8Order( [ [ 'mine_a', 'probe_limit' ] ] );
$other = $c8Order( [ [ 'two_a', 'probe_limit', 500, 'crm-probe-two' ] ] );
$request = $other[3];
check( 'a key of a package stands in front of a key of its own package or of another: of the core as before, of its own package\'s probe_limit and of the key another package entered - and the key is known and has its default like any other', $own[0] === $put( $core, 'probe_limit', 'mine_a' ) && $other[0] === $put( $core, 'probe_limit', 'two_a' ) && $own[1] === [ true ] && $other[1] === [ true ] && $own[2] === [] && $other[2] === []
	&& \Nino\Modules\Crm\Settings::known( $request, 'two_a' ) === true && \Nino\Modules\Crm\Settings::get( $request, 'two_a' ) === '' );
$forward = $c8Order( [ [ 'mine_a', 'probe_limit' ], [ 'two_a', 'probe_limit', 500, 'crm-probe-two' ] ] );
$backward = $c8Order( [ [ 'two_a', 'probe_limit', 500, 'crm-probe-two' ] ], [ [ 'mine_a', 'probe_limit' ] ] );
$ranked = $c8Order( [ [ 'two_a', 'probe_limit', 200, 'crm-probe-two' ] ], [ [ 'mine_a', 'probe_limit', 100 ] ] );
$rankedBack = $c8Order( [ [ 'mine_a', 'probe_limit', 100 ] ], [ [ 'two_a', 'probe_limit', 200, 'crm-probe-two' ] ] );
$same = $c8Order( [ [ 'mine_a', 'probe_limit', 500 ], [ 'mine_b', 'probe_limit', 500 ] ] );
$sameBack = $c8Order( [ [ 'mine_b', 'probe_limit', 500 ] ], [ [ 'mine_a', 'probe_limit', 500 ] ] );
check( 'the order does not depend on which package entered itself first, at any place: keys that stand in front of the same key go by their rank, then by the key of their package, then by their own key - and the order is the same with the packages entered in either order, at the same rank and at another', $forward[0] === $put( $core, 'probe_limit', 'mine_a', 'two_a' ) && $backward[0] === $forward[0]
	&& $ranked[0] === $put( $core, 'probe_limit', 'mine_a', 'two_a' ) && $rankedBack[0] === $ranked[0] && $same[0] === $put( $core, 'probe_limit', 'mine_a', 'mine_b' ) && $sameBack[0] === $same[0] );
$late = $c8Order( [ [ 'mine_a', 'later_key' ] ] );
$after = $c8Order( [ [ 'mine_a', 'later_key' ] ], [ [ 'later_key', 'app_host' ] ] );
$before = $c8Order( [ [ 'later_key', 'app_host' ] ], [ [ 'mine_a', 'later_key' ] ] );
check( 'a key whose anchor is not entered - the package that has it is off or removed - goes to the end with no refusal and no warning, and stands in front of it where the anchor is entered after it, as where it is entered before', $late[0] === $put( $core, 'probe_host', 'mine_a' ) && $late[1] === [ true ] && $late[2] === [] && $after[0] === $put( $core, 'app_host', 'mine_a', 'later_key' ) && $before[0] === $after[0] );
$chain = $c8Order( [ [ 'chain_b', 'chain_a' ], [ 'chain_a', 'seller_name' ] ] );
$chainBack = $c8Order( [ [ 'chain_a', 'seller_name' ] ], [ [ 'chain_b', 'chain_a' ] ] );
$deep = $c8Order( [ [ 'deep_c', 'deep_b' ], [ 'deep_b', 'deep_a', 100 ], [ 'deep_a', 'probe_limit' ], [ 'deep_d', 'deep_b', 50 ] ] );
check( 'a key stands in front of a key that stands in front of another, however deep and whichever was entered first: the keys that stand in front of it go in front of it as they go in front of a key of the core', $chain[0] === $put( $core, 'seller_name', 'chain_b', 'chain_a' ) && $chainBack[0] === $chain[0]
	&& $deep[0] === $put( $core, 'probe_limit', 'deep_d', 'deep_c', 'deep_b', 'deep_a' ) );
$ringKeys = [ [ 'ring_a', 'ring_b' ], [ 'ring_b', 'ring_a' ], [ 'ring_c', 'ring_c2' ], [ 'ring_c2', 'ring_c3' ], [ 'ring_c3', 'ring_c' ], [ 'hang', 'ring_a' ] ];
$ringBack = $c8Order( ...array_map( static fn( array $one ): array => [ $one ], array_reverse( $ringKeys ) ) );
[ $ring, $ringEntered, $ringSaid ] = $c8Order( [ [ 'ring_a', 'ring_b' ], [ 'ring_b', 'ring_a' ], [ 'ring_c', 'ring_c2' ], [ 'ring_c2', 'ring_c3' ], [ 'ring_c3', 'ring_c' ], [ 'hang', 'ring_a' ] ] );
check( 'keys that stand in front of each other in a ring are none of the core\'s to place: they go to the end in the order they were entered in, with no refusal, no warning and no loop, a key that stands in front of one of them stands in front of it there, and the keys of the core are where they were', $ring === array_merge( $core, [ 'hang', 'ring_a', 'ring_b', 'ring_c', 'ring_c2', 'ring_c3' ] ) && $ringEntered === [ true ] && $ringSaid === [] && $ringBack[0] === $ring );
$none = $c8Order( [ [ 'mine_a', null ], [ 'mine_b', null, 100 ] ] );
check( 'a key with no anchor stands at the end by its rank, then by the key of its package and its own key', $none[0] === array_merge( array_slice( $core, 0, 10 ), [ 'mine_b', 'mine_a', 'probe_host' ] ) );

// The schema of a package: a partial index and a table that names itself
[ $c8s, $c8sDir ] = crmWorld( 'crm-c8b-schema' );
$c8Enter = static function( int $version, string $file, array $tables ) use ( $c8sDir, $features ): array {

	$request = crmRequest( $c8sDir, false );
	ninoWarnings();
	\Nino\Modules\Crm\Db::addSchema( $request, [ 'package' => 'crm-probe', 'version' => $version, 'tables' => $tables, 'file' => $features. '/CrmProbe/'. $file ] );

	return $request;
};
$c8Schema = crmSchemaFile( 'schema-c8b.php', [
	'CREATE TABLE crm_probe_items ( id INTEGER PRIMARY KEY, client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT, parent_id INTEGER REFERENCES crm_probe_items( id ) ON DELETE RESTRICT, label TEXT NOT NULL DEFAULT \'\', gone TEXT )',
	'CREATE INDEX crm_probe_items_client ON crm_probe_items ( client_id )',
	'CREATE UNIQUE INDEX crm_probe_items_open ON crm_probe_items ( label ) WHERE gone IS NULL',
	'CREATE INDEX crm_probe_items_children ON crm_probe_items ( parent_id ) WHERE parent_id IS NOT NULL',
], [
	2 => [ 'CREATE INDEX crm_probe_items_gone ON crm_probe_items ( gone ) WHERE gone IS NOT NULL', 'DROP INDEX crm_probe_items_children', 'CREATE INDEX crm_probe_items_children ON crm_probe_items ( parent_id, label ) WHERE parent_id IS NOT NULL' ],
] );
$c8Db = $c8Enter( 1, $c8Schema, [ 'crm_probe_items' ] );
$c8Pdo = null;

try {
	$c8Pdo = \Nino\Modules\Crm\Db::pdo( $c8Db );
}
catch( \Throwable $e ) {
	// The pin that asks says it: a schema that is refused has no tables, and the section goes on to the end of it
	$c8Pdo = new \PDO( 'sqlite::memory:' );
}

$c8Sql = static fn( string $name ) => $c8Pdo->query( 'SELECT sql FROM sqlite_master WHERE name = '. $c8Pdo->quote( $name ) )->fetchColumn();
$c8Fails = static function( string $sql ) use ( $c8Pdo ): bool {

	try {
		$c8Pdo->exec( $sql );

		return false;
	}
	catch( \PDOException $e ) {
		return true;
	}
};
check( 'the schema of a package is taken with a partial index - a unique one and one on a column that may be empty - and with a table that names itself: the package is entered, nothing is refused and nothing warns, and the index holds the condition it was made with', ( static function() use ( $c8Db, $c8Pdo, $c8Sql ): bool {

	// A schema that is refused makes no database to ask: the pin says so, and the section goes on
	try {
		return \Nino\Modules\Crm\Db::failed( $c8Db ) === [] && ninoWarnings() === [] && \Nino\Modules\Crm\Db::versions( $c8Db )['crm-probe'] === 1
			&& str_contains( (string) $c8Sql( 'crm_probe_items_open' ), 'WHERE gone IS NULL' ) === true && str_contains( (string) $c8Sql( 'crm_probe_items_children' ), 'WHERE parent_id IS NOT NULL' ) === true
			&& array_map( static fn( array $row ): string => $row['table'], $c8Pdo->query( 'PRAGMA foreign_key_list( crm_probe_items )' )->fetchAll( \PDO::FETCH_ASSOC ) ) === [ 'crm_probe_items', 'clients' ];
	}
	catch( \Throwable $e ) {
		return false;
	}
} )() );
$c8Pdo->exec( 'INSERT INTO clients ( id, number, name, created, updated ) VALUES ( 1, \'K-0001\', \'Ada\', \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\' )' );
$c8Pdo->exec( 'INSERT INTO crm_probe_items ( id, client_id, label ) VALUES ( 1, 1, \'root\' )' );
$c8Pdo->exec( 'INSERT INTO crm_probe_items ( id, client_id, parent_id, label ) VALUES ( 2, 1, 1, \'child\' )' );
$c8Pdo->exec( 'INSERT INTO crm_probe_items ( client_id, label, gone ) VALUES ( 1, \'root\', \'2026-01-01\' )' );
check( 'what the table that names itself and the partial index say holds: a row of the table names a row of it - one that is not there is refused, and the row that is named cannot be deleted while it is -, and the partial unique index refuses a second open row of a label and takes one that is gone', $c8Fails( 'INSERT INTO crm_probe_items ( client_id, parent_id, label ) VALUES ( 1, 99, \'orphan\' )' ) === true && $c8Fails( 'DELETE FROM crm_probe_items WHERE id = 1' ) === true
	&& $c8Fails( 'INSERT INTO crm_probe_items ( client_id, label ) VALUES ( 1, \'root\' )' ) === true && $c8Fails( 'INSERT INTO crm_probe_items ( client_id, label, gone ) VALUES ( 1, \'root\', \'2026-02-02\' )' ) === false );
$c8Peel = static function( array &$a ): int {

	$pdo = \Nino\Modules\Crm\Db::pdo( $a );
	$pdo->exec( 'BEGIN IMMEDIATE' );

	try {
		$gone = \Nino\Modules\Crm\Wipe::peel( \Nino\Modules\Crm\Db::runner( $pdo ), 'crm_probe_items', 'parent_id' );
		$pdo->exec( 'COMMIT' );
	}
	catch( \Throwable $e ) {
		$pdo->exec( 'ROLLBACK' );
		throw $e;
	}

	return $gone;
};
check( 'the rows of a table that names itself go in one run of the test phase: the rows nobody names first, then the ones they named, none left, in the transaction of the database step', $c8Peel( $c8Db ) === 4 && (int) $c8Pdo->query( 'SELECT COUNT(*) FROM crm_probe_items' )->fetchColumn() === 0 );
$c8Next = $c8Enter( 2, $c8Schema, [ 'crm_probe_items' ] );
$c8Pdo = \Nino\Modules\Crm\Db::pdo( $c8Next );
check( 'a step of a package makes a partial index, drops one and makes it again with another column: the package is at version 2 and nothing failed', \Nino\Modules\Crm\Db::failed( $c8Next ) === [] && \Nino\Modules\Crm\Db::versions( $c8Next )['crm-probe'] === 2 && str_contains( (string) $c8Sql( 'crm_probe_items_gone' ), 'WHERE gone IS NOT NULL' ) === true
	&& str_contains( (string) $c8Sql( 'crm_probe_items_children' ), 'parent_id, label' ) === true );
// What the rules of the schema still refuse, said through a partial index and a self-reference as through any other
$c8Rules = [
	'a partial index on a table of the core' => [ 'CREATE INDEX crm_probe_x ON clients ( name ) WHERE active = 1' ],
	'a partial index on the table of another package' => [ 'CREATE INDEX crm_probe_x ON crm_two_items ( id ) WHERE id > 0' ],
	'an index with a condition that reads the clients' => [ 'CREATE INDEX crm_probe_x ON crm_probe_items ( label ) WHERE label IN ( SELECT number FROM clients )' ],
	'a statement that follows a partial index' => [ 'CREATE INDEX crm_probe_x ON crm_probe_items ( label ) WHERE label <> \'\'; DROP TABLE clients' ],
	'a table that names itself and then drops the clients' => [ 'CREATE TABLE crm_probe_old ( id INTEGER PRIMARY KEY, up INTEGER REFERENCES crm_probe_old( id ) )', 'DROP TABLE clients' ],
	'an index that has the name of an index of the core' => [ 'CREATE INDEX records_client_kind ON crm_probe_items ( label ) WHERE label <> \'\'' ],
];
$c8Kept = (string) $c8Pdo->query( 'SELECT group_concat( sql, \';\' ) FROM sqlite_master WHERE name NOT LIKE \'crm\_probe%\' ESCAPE \'\\\' ORDER BY name' )->fetchColumn();
$c8Wrong = [];

foreach( $c8Rules as $what => $statements ) {

	$file = crmSchemaFile( 'schema-c8b-rule.php', [ 'CREATE TABLE crm_probe_items ( id INTEGER PRIMARY KEY, client_id INTEGER NOT NULL REFERENCES clients( id ), label TEXT )' ], [ 3 => $statements ] );
	$request = $c8Enter( 3, $file, [ 'crm_probe_items', 'crm_probe_old' ] );
	$now = (string) \Nino\Modules\Crm\Db::pdo( $request )->query( 'SELECT group_concat( sql, \';\' ) FROM sqlite_master WHERE name NOT LIKE \'crm\_probe%\' ESCAPE \'\\\' ORDER BY name' )->fetchColumn();

	if( \Nino\Modules\Crm\Db::failed( $request ) !== [ 'crm-probe' => 3 ] || $now !== $c8Kept )
		$c8Wrong[] = $what;
}

check( 'the rules of what a schema may hold are the same for a partial index and for a table that names itself: on a table that is not the package\'s, with a second statement, with a condition that reads the clients, under the name of an index of the core - the package fails, and what the file holds of the core and of the others is as it was', $c8Wrong === [] );


// What the packages call of the core is declared in the description of the class, and held here
$declared = <<<'LIST'
Crm::MANAGE_PERM|const "/_admin/crm/manage"
Crm::featureOn|( array &$appData, string $key ): bool
Crm::restore|( array &$appData, array &$args ): string
Crm::siteName|( array &$appData ): string
Api::ROUTE|const "/crm/api"
Api::add|( array &$appData, array $actions, array $opts = [] ): bool
Api::made|( array &$appData, string $line ): void
Api::record|( array &$appData, string $actor, string $line ): bool
Api::id|( array &$request, array $data, string $code, string $key = 'id' ): ?int
Api::run|( array &$request, callable $run ): void
Clients::GROUP|const "/^k-[0-9]{4,}$/D"
Clients::VIEW|const "/crm/client/view"
Clients::VIEWS|const "/crm/clients/view"
Clients::PART_DEPTH|const 6
Clients::PART_BYTES|const 16384
Clients::LIST_BYTES|const 1048576
Clients::get|( array &$appData, int $id ): ?array
Clients::list|( array &$appData ): array
Clients::byNumber|( array &$appData, string $number ): ?array
Clients::title|( array $client, string $fallback = '' ): string
Clients::complete|( array $values ): bool
Clients::group|( string $number ): string
Clients::ofGroup|( array &$appData, string $group ): ?array
Clients::view|( array &$appData, array $client ): array
Clients::views|( array &$appData, array $clients ): array
Db::addSchema|( array &$appData, array $spec ): bool
Db::ensure|( array &$appData ): int
Db::exec|( array &$appData, string $sql, array $params = [] ): int
Db::pdo|( array &$appData ): PDO
Db::row|( array &$appData, string $sql, array $params = [] ): ?array
Db::rows|( array &$appData, string $sql, array $params = [] ): array
Erasure::add|( array &$appData, array $spec ): bool
Erasure::peel|( callable $run, string $table, string $column, int $client, array $only = [] ): int
Format::country|( string $code, string $locale ): string
Format::day|( string $date ): string
Hosts::share|( array &$appData, array $prefixes, array $exact = [], array $opts = [] ): bool
Hosts::refuse|( array &$request, bool $closed = false ): void
Hosts::towards|( string $app, array &$request ): void
Logbook::add|( array &$appData, array $entry ): array|string
Logbook::addKinds|( array &$appData, array $kinds, array $opts = [] ): bool
Logbook::find|( array &$appData, string $source, string $kind, array $where, int $limit = 50 ): array
Logbook::scrub|( string $value ): string
Logbook::who|( array &$appData ): string
Mail::deliver|( array &$appData, array $mails, string $about, bool $many = false, string $key = '', string $kind = 'client', ?array &$ids = null ): string|int
Mail::addWords|( array &$appData, string $root, array $opts = [] ): bool
Erasure::HOLDS|const "/crm/client/holds"
Overview::HOOK|const "/crm/overview"
Projects::FIGURES|const "/crm/project/figures"
Projects::HOLDS|const "/crm/project/holds"
Projects::ID|const "/^[A-Za-z0-9][A-Za-z0-9_-]{0,99}$/D"
Projects::forget|( array &$appData ): void
Projects::get|( array &$appData, string $id ): ?array
Projects::named|( string $id, string $title ): string
Projects::ofClient|( array &$appData, string $clientNumber ): array
Projects::titles|( array &$appData ): array
Records::of|( array &$appData, int $clientId, ?string $kind = null, ?string $purpose = null ): array
Records::ofClients|( array &$appData, array $ids, string $kind ): array
Records::names|( array &$appData, string $thing, string $id ): bool
Sequences::add|( array &$appData, array $spec ): bool
Sequences::next|( array &$appData, PDO $pdo, string $category, int $year, int $start = 1 ): int
Sequences::upcoming|( array &$appData, string $category, int $year, int $start = 1 ): int
Settings::add|( array &$appData, array $keys ): bool
Settings::all|( array &$appData ): array
Settings::get|( array &$appData, string $key ): string
Settings::oneOf|( string $key, string $value, array $words ): array|string
Settings::start|( array &$appData, string $category ): int
Settings::ownerName|( array &$appData ): string
Settings::readOnly|( array &$appData, string $key ): string
Settings\Api::SAVE|const "/crm/settings/save"
Status::add|( array &$appData, array $spec ): bool
Surface::addTexts|( array &$appData, string $directory, array $opts = [] ): bool
Surface\Data::add|( array &$appData, array $source, array $opts = [] ): bool
Surface\Scripts::add|( array &$appData, string $name, string $file, array $opts = [] ): bool
Surface\Scripts::addStyle|( array &$appData, string $name, string $file, array $opts = [] ): bool
Surface\Texts::add|( array &$appData, string $directory, array $opts = [] ): bool
Surface\Texts::word|( array &$appData, string $key ): string
Surface\Texts::locale|( array &$appData ): string
Surface\Texts::all|( array &$appData ): array
Values::START|const "/crm/element/start"
Values::addType|( array &$appData, array $spec ): bool
Values::permit|( array &$appData, array &$request, string $perm ): bool
Values::posted|(): array
Values::string|( array $data, string $key, string $default = '' ): string
Values::scoped|( array &$appData, string $prefix, string $perm ): bool
Values::workbench|( array &$appData ): bool
Wipe::add|( array &$appData, array $spec ): bool
Wipe::peel|( callable $run, string $table, string $column ): int
LIST;
$whole = (string) file_get_contents( $source. '/Crm.php' );
$from = strpos( $whole, 'THE CALLS A PACKAGE MAKES OF THE CORE' );
$till = strpos( $whole, 'THE NAMES A SCRIPT OF A PACKAGE' );

// The section of the description that declares them, and no other place: a line that is gone from it is a failure even where the older list above names the same signature
$described = $from === false || $till === false || $till < $from ? '' : substr( $whole, $from, $till - $from );
$differ = [];
$seen = 0;

foreach( explode( "\n", $declared ) as $line ) {

	[ $name, $signature ] = explode( '|', $line, 2 );
	[ $class, $member ] = explode( '::', $name, 2 );
	$full = '\\Nino\\Modules\\Crm'. ( $class === 'Crm' ? '' : '\\'. $class );
	$seen++;

	if( str_starts_with( $signature, 'const ' ) === true ) {

		$value = constant( $full. '::'. $member );

		if( 'const '. json_encode( $value, JSON_UNESCAPED_SLASHES ) !== $signature || str_contains( $described, $name. ' = \''. $value. '\'' ) === false && str_contains( $described, $name. ' = '. $value ) === false )
			$differ[] = $name;

		continue;
	}

	try {
		$method = new \ReflectionMethod( $full, $member );
	}
	catch( \ReflectionException $e ) {
		$differ[] = $name;

		continue;
	}

	$parameters = [];

	foreach( $method->getParameters() as $parameter ) {

		$default = '';

		if( $parameter->isDefaultValueAvailable() === true ) {
			$value = $parameter->getDefaultValue();
			$default = ' = '. ( $value === [] ? '[]' : ( $value === null ? 'null' : var_export( $value, true ) ) );
		}

		$parameters[] = ( $parameter->hasType() === true ? $parameter->getType(). ' ' : '' ). ( $parameter->isPassedByReference() === true ? '&' : '' ). '$'. $parameter->getName(). $default;
	}

	$said = ( $parameters === [] ? '()' : '( '. implode( ', ', $parameters ). ' )' ). ': '. ( $method->hasReturnType() === true ? $method->getReturnType() : 'mixed' );

	if( $method->isPublic() === false || $method->isStatic() === false || $said !== $signature || str_contains( $described, $name. $signature ) === false )
		$differ[] = $name;
}

check( 'the description says of the calls of the database what they are: a package is code with the rights of the kernel, and what Db::exec() and Db::pdo() let it write passes no check of the core', str_contains( $whole, 'with the rights of the kernel' ) === true && str_contains( $whole, 'Db::exec(), Db::pdo() - passes no check of the core' ) === true && str_contains( (string) substr( $described, (int) strpos( $described, '- Db::exec( array' ), 700 ), 'the tables of the core are not the package' ) === true );
check( 'every call that the packages make of the core is declared in the description of the class with the signature php says - a public static method of that name, its parameters with their types, references and defaults, its type of answer - and every constant with its value: a signature that changes is a failure of this line, and so is a line of the description that is no longer what the code says ('. $seen. ' places)', $differ === [] && $seen === 87 );

}
catch( \Throwable $crash ) {
	crmCrashed( 'What the packages asked of the core a second time', $crash );
}


ninoDone( $appData );
