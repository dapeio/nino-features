<?php
declare(strict_types=1);

/**
 *	Nino features
 *	crm-documents-smoke.php		The test of the feature CrmDocuments ...
 *
 *	Usage: php features/CrmDocuments/tests/crm-documents-smoke.php
 *	       NINO_ROOT=../nino php features/CrmDocuments/tests/crm-documents-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$source = dirname( __DIR__, 2 );

function docCopy( string $from, string $to, array $skip = [] ): void {

	mkdir( $to, 0755, true );

	foreach( array_diff( (array) scandir( $from ), [ '.', '..' ] ) as $name ) {

		if( in_array( $name, $skip, true ) === true )
			continue;

		if( is_dir( $from. '/'. $name ) === true )
			docCopy( $from. '/'. $name, $to. '/'. $name, $skip );
		else
			copy( $from. '/'. $name, $to. '/'. $name );
	}
}

/**
 *	Wait until some worker processes have started: each says so with a file beside the signal file
 */
function docAwaitUp( string $go, int $count ): void {

	for( $waited = 0; $waited < 20000; $waited++ ) {

		clearstatcache();

		if( count( glob( $go. '-up-*' ) ?: [] ) >= $count )
			return;

		usleep( 1000 );
	}
}

/**
 *	Wait until a process is idle: it has used no time of the processor over some looks one after the other, which is what a process does that waits for the lock of the database. Where /proc is not there it is a short while
 */
function docAwaitIdle( int $pid ): void {

	if( is_readable( '/proc/'. $pid. '/stat' ) === false ) {
		usleep( 1500000 );

		return;
	}

	$last = '';
	$same = 0;

	for( $looks = 0; $looks < 400 && $same < 6; $looks++ ) {

		$fields = explode( ' ', (string) preg_replace( '/^.*\) /', '', (string) @file_get_contents( '/proc/'. $pid. '/stat' ) ) );
		$used = ( $fields[11] ?? '' ). '/'. ( $fields[12] ?? '' );
		$same = $used === $last ? $same + 1 : 0;
		$last = $used;
		usleep( 25000 );
	}
}

function docRemove( string $folder ): void {

	if( is_dir( $folder ) === false && is_link( $folder ) === false )
		return;

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $folder, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $item )
		$item->isDir() === true && $item->isLink() === false ? rmdir( (string) $item->getPathname() ) : unlink( (string) $item->getPathname() );

	rmdir( $folder );
}

if( in_array( $argv[1] ?? '', [ 'worker', 'locker' ], true ) )
	$features = (string) $argv[2];
else {
	$features = sys_get_temp_dir(). '/nino-crm-documents-features-'. bin2hex( random_bytes( 6 ) );
	mkdir( $features, 0755, true );

	foreach( [ 'Crm', 'Pdf', 'CrmDocuments' ] as $name )
		docCopy( $source. '/'. $name, $features. '/'. $name, [ 'tests' ] );
}

defined( 'NINO_FEATURES_DIR' ) === false || exit( "crm-documents-smoke.php names its own features directory\n" );
define( 'NINO_FEATURES_DIR', $features );
require $root. '/tests/harness.php';

// What the run made in the temporary directory - the copy of the features and the sandboxes of the worlds - goes with it, also where a check fails or the run ends in a fatal error
if( in_array( $argv[1] ?? '', [ 'worker', 'locker' ], true ) === false )
	register_shutdown_function( static function() use ( $features ): void {

		foreach( (array) ( $GLOBALS['docSandboxes'] ?? [] ) as $folder )
			docRemove( (string) $folder );

		docRemove( $features );
	} );

function docRequest( string $sandbox, array $set = [] ): array {

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

function docWorld( string $name ): array {

	$project = ninoSandbox( $name );
	$GLOBALS['docSandboxes'][] = ninoSandboxDir( $project );
	\Nino\Features::activate( $project, 'crm' );
	\Nino\Features::activate( $project, 'pdf' );
	\Nino\Features::activate( $project, 'crm-documents' );

	$where = ninoSandboxDir( $project );
	$request = docRequest( $where );
	$GLOBALS['docInitWarnings'] = ninoWarnings();

	\Nino\Auth::insertUser( $request, 'admin@example.org', 'correct horse battery staple', [ '/*' ] );
	\Nino\Auth::insertUser( $request, 'plain@example.org', 'plain password', [] );
	\Nino\Auth::insertUser( $request, 'crm@example.org', 'crm password', [ '/_admin/crm/manage' ] );
	ninoWarnings();

	return [ $project, $where, $request ];
}

function docSignIn( array &$appData, string $who ): void {

	$passwords = [ 'admin@example.org' => 'correct horse battery staple', 'plain@example.org' => 'plain password', 'crm@example.org' => 'crm password' ];

	$_SESSION = [];
	unset( $appData['./nino/auth/current'] );

	if( $who !== '' )
		\Nino\Auth::loginUser( $appData, $who, $passwords[$who] );

	// The session is a global of the process: each request of the suite takes the one of the app data it is made with
	$appData['./doc/session'] = $_SESSION;
}

function docPost( array &$appData, string $action, mixed $data = [], array $o = [] ): array {

	$token = array_key_exists( 'token', $o ) === true ? (string) $o['token'] : \Nino\Csrf::getToken( $appData );
	$server = [ 'REMOTE_ADDR' => '127.0.0.1', 'CONTENT_TYPE' => 'application/json' ];

	if( $token !== '' )
		$server['HTTP_X_CSRF_TOKEN'] = $token;

	$_SESSION = $appData['./doc/session'] ?? [];
	$request = ninoVisit( $appData, '/crm/api', 'POST', $server );
	$request['/nino/http/request']['body'] = json_encode( [ 'action' => $action, 'data' => $data ] );
	$posted = $_POST;
	$_POST = [];

	try {
		\Nino\Http::response( $appData, $request );
	}
	finally {
		$_POST = $posted;
	}

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, $request ];
}

function docAnswer( array &$appData, string $action, mixed $data = [] ): string {

	[ $status, $body ] = docPost( $appData, $action, $data );

	return $status. ' '. ( is_array( $body ) === true && is_string( $body['code'] ?? null ) === true ? $body['code'] : '-' );
}

function docBody( array &$appData, string $action, mixed $data = [] ): array {

	[ $status, $body ] = docPost( $appData, $action, $data );

	return $status === 200 && is_array( $body ) === true ? $body : [];
}


/**
 *	The seller and the templates of a site, as the owner enters them
 *
 *	@param		array 		&$web
 *
 *	@return 	void
 */
function docSetupSeller( array &$web ): void {

	docAnswer( $web, 'crm/settings-save', [ 'seller_name' => 'Studio Example', 'seller_street' => 'Hauptstr. 2', 'seller_zip' => '10115', 'seller_city' => 'Berlin', 'seller_country' => 'DE', 'seller_contact' => 'Max Example', 'seller_phone' => '+49 30 123456',
		'seller_email' => 'studio@example.org', 'vat_id' => 'DE123456789', 'tax_id' => '12/345/67890', 'iban' => 'DE89 3704 0044 0532 0130 00' ] );
	\Nino\Modules\Crm\Status::fix( $web, 'templates-offer' );
}

/**
 *	A client with an address, made through the endpoint
 *
 *	@param		array 		&$web
 *	@param		array 		$fields
 *
 *	@return 	int												Its id; 0 where it was not made
 */
function docClient( array &$web, array $fields = [] ): int {

	return (int) ( docBody( $web, 'crm-clients/save', $fields + [ 'name' => 'Ada Example', 'street' => 'Weg 1', 'zip' => '12345', 'city' => 'Berlin', 'country' => 'DE' ] )['client']['id'] ?? 0 );
}

/**
 *	A draft made through the endpoint: the first line is one of a hundred euros
 *
 *	@param		array 		&$web
 *	@param		string		$category
 *	@param		int				$client
 *	@param		array 		$more					Fields of the post that replace the ones given here
 *
 *	@return 	array											The document as the answer has it; [] where it was refused
 */
function docDraft( array &$web, string $category, int $client, array $more = [] ): array {

	$lines = in_array( $category, [ 'contract', 'privacy', 'information' ], true ) === true ? [] : [ [ 'title' => 'Mix', 'qty' => '1', 'price' => '100,00' ] ];
	$base = [ 'category' => $category, 'client_id' => $client, 'template_key' => [ 'offer' => 'offer', 'invoice' => 'invoice', 'credit' => 'credit-note', 'contract' => 'contract', 'privacy' => 'privacy', 'information' => 'information' ][$category] ];

	if( in_array( $category, [ 'contract', 'privacy', 'information' ], true ) === true )
		$base['title'] = 'Title of '. $category;
	else
		$base['lines'] = $lines;

	return (array) ( docBody( $web, 'crm-documents/save', $more + $base )['document'] ?? [] );
}

/**
 *	A draft made and finalised through the endpoint
 *
 *	@param		array 		&$web
 *	@param		string		$category
 *	@param		int				$client
 *	@param		array 		$more
 *
 *	@return 	array											The finalised document; [] where it was refused
 */
function docFinal( array &$web, string $category, int $client, array $more = [] ): array {

	$draft = docDraft( $web, $category, $client, $more );

	return $draft === [] ? [] : (array) ( docBody( $web, 'crm-documents/finalise', [ 'id' => $draft['id'] ] )['document'] ?? [] );
}

/**
 *	The row of a document as the database holds it, for a pin that says nothing of it changed
 *
 *	@param		array 		&$web
 *	@param		int				$id
 *
 *	@return 	array
 */
/**
 *	A setting as it stands now: what the request of the suite kept of them is dropped first
 *
 *	@param		array 		&$web
 *	@param		string		$key
 *
 *	@return 	string
 */
function docSetting( array &$web, string $key ): string {

	\Nino\Modules\Crm\Db::drop( $web, 'settings' );

	return \Nino\Modules\Crm\Settings::get( $web, $key );
}

function docRow( array &$web, int $id ): array {

	return [ \Nino\Modules\Crm\Db::row( $web, 'SELECT * FROM documents WHERE id = :id', [ 'id' => $id ] ), \Nino\Modules\Crm\Db::rows( $web, 'SELECT * FROM document_lines WHERE document_id = :id ORDER BY id', [ 'id' => $id ] ) ];
}

/**
 *	A page of the CRM host, as a visitor asks for it: the request of the site, the route, the callbacks of the route
 *
 *	@param		array 		&$web
 *	@param		string		$uri
 *
 *	@return 	array											[ status, response ]
 */
function docGet( array &$web, string $uri ): array {

	$_SESSION = $web['./doc/session'] ?? [];
	$request = ninoVisit( $web, $uri, 'GET', [ 'REMOTE_ADDR' => '127.0.0.1' ] );
	\Nino\Http::response( $web, $request );

	return [ (int) $request['/nino/http/response']['statusCode'], $request['/nino/http/response'] ];
}

/**
 *	What an answer of a post says in the first line: the status and the code of a refusal
 *
 *	@param		array 		$answer				As docPost() answers
 *
 *	@return 	string
 */
function docCode( array $answer ): string {

	return $answer[0]. ' '. ( is_array( $answer[1] ) === true && is_string( $answer[1]['code'] ?? null ) === true ? $answer[1]['code'] : '-' );
}

// A process that holds the database locked until the signal file is gone
if( ( $argv[1] ?? '' ) === 'locker' ) {

	$held = new \PDO( 'sqlite:'. $argv[3] );
	$held->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
	$held->exec( 'BEGIN EXCLUSIVE' );
	touch( (string) $argv[4] );

	while( clearstatcache( true ) === null && is_file( (string) $argv[4] ) === true )
		usleep( 2000 );

	$held->exec( 'ROLLBACK' );
	exit( 0 );
}

/*	Worker modes: this same file re-entered as a process of its own, pointed at a sandbox somebody else built. The numbers that processes take at once are only seen from other processes: a lock of the file is per process	*/
if( ( $argv[1] ?? '' ) === 'worker' ) {

	$worker = docRequest( (string) $argv[3] );
	docSignIn( $worker, 'admin@example.org' );

	// A worker that is to crash is killed by the database of its own connection: 1 at the moment the number is written to the row (the files are written under pending names, the commit is not), 2 at the moment the logbook
	// line of the finalising is written, which comes right after the commit and before the files get their names
	if( in_array( getenv( 'DOCS_CRASH' ), [ '1', '2', '3' ], true ) ) {
		$connection = \Nino\Modules\Crm\Db::pdo( $worker );
		$connection->sqliteCreateFunction( 'docs_crash', static function(): int { posix_kill( getmypid(), 9 ); sleep( 5 ); return 0; } );

		// 3: the process stops at the moment the number is written to the row - the files lie under pending names, the write lock is held, nothing is committed - and goes on when the signal file of the release is there
		if( getenv( 'DOCS_CRASH' ) === '3' )
			$connection->sqliteCreateFunction( 'docs_crash', static function() use ( $argv ): int {

				touch( (string) $argv[4]. '-held' );

				while( clearstatcache( true ) === null && is_file( (string) $argv[4]. '-release' ) === false )
					usleep( 1000 );

				return 0;
			} );

		$connection->exec( in_array( getenv( 'DOCS_CRASH' ), [ '1', '3' ], true ) ? 'CREATE TEMP TRIGGER docs_crash BEFORE UPDATE OF number ON documents WHEN NEW.number IS NOT NULL BEGIN SELECT docs_crash(); END'
			: 'CREATE TEMP TRIGGER docs_crash AFTER INSERT ON logbook WHEN NEW.kind = \'document-finalised\' BEGIN SELECT docs_crash(); END' );
	}

	// The worker says it is up, and waits for the word to go
	touch( (string) $argv[4]. '-up-'. getmypid() );

	while( is_file( (string) $argv[4] ) === false )
		usleep( 200 );

	// Two writers of the same names: each answers which names it was given the place for
	if( getenv( 'DOCS_MODE' ) === 'keep' ) {

		$took = [];

		foreach( range( 1, 150 ) as $at ) {
			$pending = \Nino\Modules\CrmDocuments\Render::stage( $worker, '/data/crm/pdf/2019/k'. $at. '.pdf', str_repeat( (string) $argv[5], 262144 ) );
			$took[$at] = $pending !== '' && \Nino\Modules\CrmDocuments\Render::publish( $worker, '/data/crm/pdf/2019/k'. $at. '.pdf', $pending, hash( 'sha256', str_repeat( (string) $argv[5], 262144 ) ) ) === true;
		}

		echo json_encode( $took );
		exit( 0 );
	}

	// Readers of the files of a document whose process died after its commit: all of them ask for the files at once, and each says what it was answered and what the log said
	if( getenv( 'DOCS_MODE' ) === 'download' ) {

		$answers = [];

		foreach( range( 1, 3 ) as $round )
			foreach( [ '/.crm/pdf/', '/.crm/xml/', '/.crm/pdf/', '/.crm/xml/' ] as $route )
				$answers[] = docGet( $worker, $route. $argv[5] )[0];

		$answers[] = \Nino\Modules\CrmDocuments\Status::files( $worker )['state'];
		echo json_encode( [ 'answers' => $answers, 'warnings' => ninoWarnings() ] );
		exit( 0 );
	}

	// A finalising that says it has started, and then asks for the lock of the database, which another process holds
	if( getenv( 'DOCS_MODE' ) === 'finalise-wait' ) {

		touch( $argv[4]. '-ready' );
		echo json_encode( docCode( docPost( $worker, 'crm-documents/finalise', [ 'id' => (int) $argv[5] ] ) ) );
		exit( 0 );
	}

	// A writer of a change that has read its draft and waits for the lock of the database, which another process holds while it finalises the draft
	if( getenv( 'DOCS_MODE' ) === 'change' ) {

		touch( $argv[4]. '-ready' );
		$answer = docCode( docPost( $worker, 'crm-documents/save', [ 'id' => (int) $argv[5], 'lines' => [ [ 'title' => 'Changed', 'qty' => '1', 'price' => '500,00' ] ] ] ) );

		echo json_encode( $answer );
		exit( 0 );
	}

	$made = [];

	foreach( array_slice( $argv, 5 ) as $id ) {
		[ $status, $body ] = docPost( $worker, 'crm-documents/finalise', [ 'id' => (int) $id ] );
		$made[] = $status === 200 ? $body['document']['number'] : $status. ' '. ( $body['code'] ?? '-' );
	}

	echo json_encode( $made );
	exit( 0 );
}

// --- The world of the suite ---------------------------------------------------------------------------------------------

[ $project, $where, $request ] = docWorld( 'docs-main' );
$web = docRequest( $where );
docSignIn( $web, 'admin@example.org' );
$initWarnings = $GLOBALS['docInitWarnings'];

check( 'the package enters itself without a warning', $initWarnings === [] );

// --- What the package enters in the core -----------------------------------------------------------------------------------

$actions = array_keys( \Nino\Modules\Crm\Api::actions( $web ) );
$own = array_values( array_filter( $actions, static fn( string $action ): bool => str_starts_with( $action, 'crm-documents/' ) ) );
sort( $own );

check( 'the actions of the package are entered under its key, and only these', $own === [ 'crm-documents/copy', 'crm-documents/credit', 'crm-documents/figures', 'crm-documents/finalise', 'crm-documents/from-offer', 'crm-documents/get', 'crm-documents/items',
	'crm-documents/list', 'crm-documents/project', 'crm-documents/remind', 'crm-documents/remove', 'crm-documents/restore-file', 'crm-documents/save', 'crm-documents/state', 'crm-documents/templates' ] );
check( 'the package owns three number sequences and the core the one of the clients', \Nino\Modules\Crm\Sequences::categories( $web ) === [ 'client' => 'crm', 'offer' => 'crm-documents', 'invoice' => 'crm-documents', 'credit' => 'crm-documents' ] );
check( 'the element type of the items is entered for the screen of the values', in_array( 'crm-items', \Nino\Modules\Crm\Values::types( $web, 'values' ), true ) === true );
check( 'the step of the deletion of a client that refuses a fixed document and the step that deletes the drafts are entered, and the test phase has its step', array_keys( \Nino\Modules\Crm\Erasure::entered( $web ) ) === [ 'fixed', 'drafts' ] && array_keys( \Nino\Modules\Crm\Wipe::entered( $web ) ) === [ 'documents' ] );
check( 'the six kinds of the logbook are the package\'s', \Nino\Modules\CrmDocuments\Hooks::KINDS === [ 'document-finalised', 'document-released', 'document-sent', 'document-paid', 'document-reminded', 'document-signed' ] && array_filter( \Nino\Modules\CrmDocuments\Hooks::KINDS, static fn( string $kind ): bool => \Nino\Modules\Crm\Logbook::kindOf( $web, 'crm-documents', $kind ) === false ) === [] );
check( 'the files of the books and the owner\'s links to them are shared on every host', \Nino\Modules\Crm\Hosts::shared( $web, '/.crm/pdf/1' ) === true && \Nino\Modules\Crm\Hosts::shared( $web, '/.crm/xml/1' ) === true );

$keys = array_keys( \Nino\Modules\Crm\Settings::defaults( $web ) );
check( 'the settings of the documents stand where the old order had them: the numbers before the start of the clients, the bank before the owner, the seller behind the owner', array_slice( $keys, 0, 11 ) === [ 'vat_mode', 'vat_rates', 'payment_days', 'offer_days', 'currency', 'number_offer', 'number_invoice', 'number_credit', 'start_offer', 'start_invoice', 'start_credit' ]
	&& array_slice( $keys, 11, 3 ) === [ 'start_client', 'start_project', 'tax_id' ] && array_slice( $keys, 15, 5 ) === [ 'iban', 'bic', 'paypal', 'seller_name', 'seller_street' ] && array_search( 'vat_exempt_text', $keys, true ) === array_search( 'brand_word', $keys, true ) - 1 );
check( 'there are twenty-five settings of the documents and none has a default that is a sentence', count( array_filter( [ 'vat_mode', 'vat_rates', 'payment_days', 'offer_days', 'currency', 'number_offer', 'number_invoice', 'number_credit', 'start_offer', 'start_invoice', 'start_credit', 'tax_id', 'bank_name', 'iban', 'bic', 'paypal',
	'seller_street', 'seller_zip', 'seller_city', 'seller_country', 'seller_contact', 'seller_phone', 'seller_email', 'vat_id', 'vat_exempt_text' ], static fn( string $key ): bool => in_array( $key, $keys, true ) === true ) ) === 25
	&& docSetting( $web, 'vat_exempt_text' ) === '' );

// What the files of the package may not hold: a name of another package, a check that the class is there, a field that lives in the class
$source = [];

foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source_dir = dirname( __DIR__ ), \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_ends_with( (string) $file->getPathname(), '.php' ) === true && str_contains( (string) $file->getPathname(), '/tests/' ) === false && str_contains( (string) $file->getPathname(), '/install/' ) === false )
		$source[substr( (string) $file->getPathname(), strlen( $source_dir ) + 1 )] = (string) file_get_contents( (string) $file->getPathname() );

$all = implode( "\n", $source );

check( 'no file of the package names the portal, Sign or Members, or asks whether a class is there', preg_match( '/CrmPortal|Modules\\\\Sign|Modules\\\\Members|class_exists|function_exists|method_exists/', $all ) !== 1 );
check( 'no class of the package has a static field', preg_match( '/^\s*(?:public|private|protected)\s+(?:readonly\s+)?static\s+(?!function)(?!const)[^(]*\$/m', $all ) !== 1 );

/**
 *	The arrow functions of a source that name the app data in their body: the body of one reaches from its arrow to the comma, the semicolon or the bracket that ends the expression it stands in
 *
 *	@param		string		$code
 *
 *	@return 	int
 */
function docArrowsWithAppData( string $code ): int {

	$tokens = token_get_all( $code );
	$found = 0;

	foreach( $tokens as $at => $token ) {

		if( is_array( $token ) === false || $token[0] !== T_FN )
			continue;

		$depth = 0;
		$arrow = false;

		for( $next = $at + 1; $next < count( $tokens ); $next++ ) {

			$one = $tokens[$next];
			$text = is_array( $one ) === true ? $one[1] : $one;

			if( $arrow === false ) {
				$arrow = is_array( $one ) === true && $one[0] === T_DOUBLE_ARROW;
				continue;
			}

			if( in_array( $text, [ '(', '[', '{' ], true ) === true )
				$depth++;
			elseif( in_array( $text, [ ')', ']', '}' ], true ) === true && $depth-- === 0 )
				break;
			elseif( $depth === 0 && in_array( $text, [ ',', ';' ], true ) === true )
				break;

			if( is_array( $one ) === true && $one[0] === T_VARIABLE && $text === '$appData' ) {
				$found++;
				break;
			}
		}
	}

	return $found;
}

check( 'no arrow function of the package takes the app data in: it would be a copy', array_sum( array_map( 'docArrowsWithAppData', $source ) ) === 0 );
check( 'every function that registers names the package, and none registers without it', substr_count( $all, "'package' => 'crm-documents'" ) + substr_count( $all, "'package' => self::KEY" ) >= 12 );

check( 'a document is numbered by the sequences of the core and by nothing the package keeps itself', str_contains( $all, 'UPDATE sequences' ) === false && str_contains( $all, 'INSERT OR IGNORE INTO sequences' ) === false );

// --- Money: the cases the screens run as well -------------------------------------------------------------------------------

use Nino\Modules\CrmDocuments\Money;

$cases = json_decode( (string) file_get_contents( __DIR__. '/money-cases.json' ), true );

check( 'the line amount is quantity times price, rounded half away from zero, in whole numbers', array_filter( $cases['lineAmount'], static fn( array $case ): bool => Money::lineAmount( $case[0], $case[1] ) !== $case[2] ) === [] );
check( 'the tax of a net is the net times the rate, rounded half away from zero', array_filter( $cases['vat'], static fn( array $case ): bool => Money::vat( $case[0], $case[1] ) !== $case[2] ) === [] );
check( 'a price a person writes is read as cents, and what is no number is none', array_filter( $cases['parse'], static fn( array $case ): bool => Money::parse( $case[0] ) !== $case[1] ) === [] );
check( 'a quantity a person writes is read as thousandths', array_filter( $cases['parseQty'], static fn( array $case ): bool => Money::parseQty( $case[0] ) !== $case[1] ) === [] );
check( 'cents are written with a comma, a quantity without trailing zeros, a rate in percent', array_filter( $cases['format'], static fn( array $case ): bool => Money::format( $case[0], $case[1] ) !== $case[2] ) === []
	&& array_filter( $cases['formatQty'], static fn( array $case ): bool => Money::formatQty( $case[0] ) !== $case[1] ) === [] && array_filter( $cases['formatRate'], static fn( array $case ): bool => Money::formatRate( $case[0] ) !== $case[1] ) === [] );

$wrong = [];

foreach( $cases['totals'] as $case ) {

	$got = Money::totals( $case['lines'], $case['mode'], $case['reverse'] );
	$rates = [];

	foreach( $got['rates'] as $rate => $part )
		$rates[] = [ $rate, $part['net'], $part['vat'] ];

	if( [ $got['net'], $got['vat'], $got['gross'], $rates, $got['amounts'] ] !== [ $case['expect']['net'], $case['expect']['vat'], $case['expect']['gross'], $case['expect']['rates'], $case['expect']['amounts'] ] )
		$wrong[] = $case['name'];
}

check( 'the totals of the cases: net per rate, the tax of each rate\'s net, the highest rate first'. ( $wrong === [] ? '' : ' - '. implode( ', ', $wrong ) ), $wrong === [] );

// The rule of the old CRM for the tax of a credit note: rounded on what the invoice has given back in that rate so far, so that the credit notes of an invoice come to its tax in every rate
check( 'the half cent goes away from zero, up for a charge and down for a discount', Money::vat( 5, 1000 ) === 1 && Money::vat( -5, 1000 ) === -1 && Money::vat( 4, 1000 ) === 0 && Money::lineAmount( 1, 500 ) === 1 && Money::lineAmount( -1, 500 ) === -1 );
check( 'an amount at the edge of the range stays a whole number', is_int( Money::lineAmount( Money::MAX_QTY, Money::MAX_PRICE ) ) === true && is_int( Money::vat( Money::MAX_TOTAL, \Nino\Modules\CrmDocuments\Rules::MAX_RATE ) ) === true );

// --- The settings -------------------------------------------------------------------------------------------------------------

$saved = static fn( array $data ): string => docAnswer( $web, 'crm/settings-save', $data );

check( 'a post of the settings with a value that is none changes nothing and says which key', $saved( [ 'vat_mode' => 'sometimes' ] ) === '400 crm_documents_setting' && docSetting( $web, 'vat_mode' ) === 'small_business' );
check( 'the number format needs its counter once, the year at most once and nothing but letters, digits and . _ -', $saved( [ 'number_invoice' => 'R-{YYYY}' ] ) === '400 crm_documents_numberformat' && $saved( [ 'number_invoice' => 'R/{N}' ] ) === '400 crm_documents_numberformat'
	&& $saved( [ 'number_invoice' => '{YYYY}{YY}{N}' ] ) === '400 crm_documents_numberformat' && $saved( [ 'number_invoice' => '{N}{NN}' ] ) === '400 crm_documents_numberformat' && $saved( [ 'number_invoice' => str_repeat( 'R', 40 ). '{N}' ] ) === '400 crm_documents_numberformat' );

$formatsBefore = [ \Nino\Modules\Crm\Settings::get( $web, 'number_offer' ), \Nino\Modules\Crm\Settings::get( $web, 'number_invoice' ), \Nino\Modules\Crm\Settings::get( $web, 'number_credit' ) ];

check( 'one number format for two kinds of document is refused when it is saved, whichever of the two is the one posted, and nothing is saved', $saved( [ 'number_credit' => $formatsBefore[1] ] ) === '400 crm_documents_numberformat_same' && $saved( [ 'number_offer' => $formatsBefore[1] ] ) === '400 crm_documents_numberformat_same'
	&& $saved( [ 'number_offer' => 'X-{N}', 'number_invoice' => 'X-{N}' ] ) === '400 crm_documents_numberformat_same' && $formatsBefore === [ \Nino\Modules\Crm\Settings::get( $web, 'number_offer' ), \Nino\Modules\Crm\Settings::get( $web, 'number_invoice' ), \Nino\Modules\Crm\Settings::get( $web, 'number_credit' ) ]
	&& $saved( [ 'number_credit' => 'r-{YYYY}-{NNNN}' ] ) === '400 crm_documents_numberformat_same' && $saved( [ 'number_credit' => 'R-'. gmdate( 'Y' ). '-{NNNN}' ] ) === '400 crm_documents_numberformat_same' && $saved( [ 'number_credit' => 'R-20{YY}-{NNNN}' ] ) === '400 crm_documents_numberformat_same'
	&& $saved( [ 'number_credit' => 'R-{YYYY}-{NNN}' ] ) === '400 crm_documents_numberformat_same' && $saved( [ 'number_credit' => 'r-{YYYY}-{NNNNN}' ] ) === '400 crm_documents_numberformat_same' && $saved( [ 'number_credit' => 'G-{YYYY}-{NNNN}' ] ) === '200 -' && $saved( [ 'number_credit' => $formatsBefore[2] ] ) === '200 -'
	&& $saved( [ 'number_offer' => $formatsBefore[0] ] ) === '200 -' && $saved( [ 'number_credit' => 'Y-{N}', 'number_invoice' => 'Z-{N}' ] ) === '200 -' && $saved( [ 'number_credit' => $formatsBefore[2], 'number_invoice' => $formatsBefore[1] ] ) === '200 -' );
check( 'a start number is a whole number from 1 with nine digits at the most', $saved( [ 'start_invoice' => '0' ] ) === '400 crm_startnumber' && $saved( [ 'start_invoice' => '1000000000' ] ) === '400 crm_startnumber' && $saved( [ 'start_invoice' => 'x' ] ) === '400 crm_startnumber' );
check( 'the days a payment and an offer are open are 0 to 365', $saved( [ 'payment_days' => '366' ] ) === '400 crm_documents_setting' && $saved( [ 'payment_days' => '-1' ] ) === '400 crm_documents_setting' && $saved( [ 'offer_days' => '01' ] ) === '400 crm_documents_setting' );
check( 'an IBAN is held to the check of the banks, a BIC to its shape, PayPal to a name', $saved( [ 'iban' => 'DE00 1234' ] ) === '400 crm_documents_particular_iban' && $saved( [ 'iban' => 'DE89 3704 0044 0532 0130 01' ] ) === '400 crm_documents_particular_iban' && $saved( [ 'iban' => 'de89370400440532013000' ] ) === '200 -' && docSetting( $web, 'iban' ) === 'DE89 3704 0044 0532 0130 00' && $saved( [ 'bic' => 'nope' ] ) === '400 crm_documents_particular_bic' && $saved( [ 'paypal' => 'a b' ] ) === '400 crm_documents_particular_paypal'
	&& $saved( [ 'seller_zip' => '-1' ] ) === '400 crm_documents_particular_seller_zip' && $saved( [ 'seller_email' => 'not an address' ] ) === '400 crm_documents_particular_seller_email' && $saved( [ 'vat_id' => 'XX' ] ) === '400 crm_documents_particular_vat_id'
	&& $saved( [ 'seller_phone' => 'call me' ] ) === '400 crm_documents_particular_seller_phone' && $saved( [ 'seller_country' => 'Germany' ] ) === '400 crm_documents_particular_seller_country' );
check( 'a currency is a short word or a sign', $saved( [ 'currency' => '' ] ) === '400 crm_documents_setting' && $saved( [ 'currency' => '123456' ] ) === '400 crm_documents_setting' );
check( 'the rates are hundredths of a percent from 0 to 10000, none twice', $saved( [ 'vat_rates' => '1900,1900' ] ) === '400 crm_documents_setting' && $saved( [ 'vat_rates' => '10001' ] ) === '400 crm_documents_setting' && $saved( [ 'vat_rates' => '19' ] ) === '200 -' );
check( 'the rates written in percent are the rates the settings keep: a semicolon or blanks apart, a decimal comma, and a comma that separates is refused', $saved( [ 'vat_rates_percent' => '19; 7; 5,5; 0' ] ) === '200 -' && docSetting( $web, 'vat_rates' ) === '1900,700,550,0'
	&& $saved( [ 'vat_rates_percent' => '19,7,0' ] ) === '400 crm_documents_setting' && docSetting( $web, 'vat_rates' ) === '1900,700,550,0' && $saved( [ 'vat_rates_percent' => [ '19' ] ] ) === '400 crm_documents_setting' );
check( 'a post that is refused as a whole writes nothing of it', $saved( [ 'tax_id' => '123/456', 'vat_mode' => 'nope' ] ) === '400 crm_documents_setting' && docSetting( $web, 'tax_id' ) === '' );

$view = json_decode( (string) json_encode( docBody( $web, 'crm/settings-get' ) ), true );

check( 'the view of the settings carries the numbers the sequences give next and the templates of Pdf by tag', ( $view['packages']['crm-documents']['next'] ?? [] ) === [ 'offer' => 'A-'. date( 'Y' ). '-0001', 'invoice' => 'R-'. date( 'Y' ). '-0001', 'credit' => 'G-'. date( 'Y' ). '-0001' ]
	&& array_keys( $view['packages']['crm-documents']['templates'] ?? [] ) === [ 'offer', 'invoice', 'contract', 'privacy', 'information' ] && ( $view['packages']['crm-documents']['pdf']['on'] ?? false ) === true );

// The settings the rest of the suite works with: the seller of an invoice, a business that is no small business is set where the section needs it
check( 'the seller\'s particulars are saved', $saved( [ 'seller_name' => 'Studio Example', 'seller_street' => 'Hauptstr. 2', 'seller_zip' => '10115', 'seller_city' => 'Berlin', 'seller_country' => 'DE', 'seller_contact' => 'Max Example', 'seller_phone' => '+49 30 123456',
	'seller_email' => 'studio@example.org', 'vat_id' => 'DE123456789', 'tax_id' => '12/345/67890', 'iban' => 'DE89 3704 0044 0532 0130 00', 'bic' => 'COBADEFFXXX', 'bank_name' => 'Example Bank', 'vat_rates' => '1900,700,0' ] ) === '200 -'
	&& docSetting( $web, 'iban' ) === 'DE89 3704 0044 0532 0130 00' );

// --- The status ---------------------------------------------------------------------------------------------------------------

$lines = array_column( \Nino\Modules\Crm\Status::lines( $web ), null, 'key' );

check( 'the lines of the documents are on the status: Pdf, the tags, a template for an offer and for an invoice, the folder, the files without a document, the items and the backup', count( array_intersect( array_keys( $lines ), [ 'pdf', 'pdf-tags', 'templates-offer', 'templates-invoice', 'pdf-dir', 'pdf-files', 'items', 'backup-size' ] ) ) === 8 );
check( 'a site that has no tags and no templates sees both lines red with a fix, and the hint is no fault', $lines['pdf-tags']['state'] === 'missing' && $lines['pdf-tags']['fixable'] === true && $lines['templates-offer']['state'] === 'none' && $lines['templates-invoice']['fixable'] === true
	&& $lines['backup-size']['hint'] === true && $lines['pdf-files']['hint'] === true && $lines['pdf-files']['state'] === 'ok' && $lines['pdf']['state'] === 'ok' && $lines['items']['state'] === 'ok' && $lines['pdf-dir']['state'] === 'ok' );
check( 'no word of a line is a raw key', array_filter( $lines, static fn( array $line ): bool => isset( $line['label'] ) === true && ( str_starts_with( $line['label'], 'docs.' ) === true || str_starts_with( (string) ( $line['detail'] ?? '' ), 'docs.' ) === true ) ) === [] );

check( 'the line of the templates mends them: the tags first, the six templates add-only', \Nino\Modules\Crm\Status::fix( $web, 'templates-offer' ) === true && array_keys( \Nino\Modules\Pdf::templates( $web ) ) !== [] && count( \Nino\Modules\Pdf::templates( $web ) ) === 6
	&& array_diff( [ 'offer', 'invoice', 'contract', 'privacy', 'information' ], \Nino\Modules\Pdf::tags( $web ) ) === [] );

$before = (string) json_encode( \Nino\Modules\Pdf::templates( $web ) );
$edited = \Nino\Modules\Pdf::template( $web, 'invoice' );

check( 'the fix again replaces nothing and adds nothing', \Nino\Modules\Crm\Status::fix( $web, 'templates-offer' ) === true && (string) json_encode( \Nino\Modules\Pdf::templates( $web ) ) === $before && \Nino\Modules\Pdf::template( $web, 'invoice' ) === $edited );

$lines = array_column( \Nino\Modules\Crm\Status::lines( $web ), null, 'key' );

check( 'after the fix the lines are in order', $lines['pdf-tags']['state'] === 'ok' && $lines['templates-offer']['state'] === 'ok' && $lines['templates-invoice']['state'] === 'ok' );

$GLOBALS['docWarnings'] = ninoWarnings();

check( 'nothing of this made a warning', $GLOBALS['docWarnings'] === [] );

// --- Drafts -------------------------------------------------------------------------------------------------------------------

use Nino\Modules\CrmDocuments\Documents;
use Nino\Modules\CrmDocuments\Figures;
use Nino\Modules\CrmDocuments\Finalise;
use Nino\Modules\Crm\Db;

$ada = docClient( $web );
$bare = docClient( $web, [ 'name' => 'Bare Client', 'street' => '', 'zip' => '', 'city' => '' ] );
$firm = docClient( $web, [ 'name' => 'Grace Hopper', 'company' => 'Firm GmbH', 'business' => true, 'vat_id' => 'DE999999999', 'email' => 'grace@example.org', 'buyer_reference' => '991-12345-67' ] );
check( 'the clients of the suite are there', $ada > 0 && $bare > 0 && $firm > 0 );

$draft = docDraft( $web, 'invoice', $ada );

check( 'a draft has no number, the state draft, the cents of its lines and the due day the setting gives', ( $draft['state'] ?? '' ) === 'draft' && ( $draft['number'] ?? 'x' ) === '' && ( $draft['net'] ?? 0 ) === 10000 && ( $draft['gross'] ?? 0 ) === 10000 && ( $draft['lines'][0]['qty'] ?? 0 ) === 1000
	&& ( $draft['due_date'] ?? '' ) === Documents::addDays( date( 'Y-m-d' ), 14 ) && array_key_exists( 'valid_until', $draft ) === true && $draft['valid_until'] === null );
check( 'every amount of a document is a whole number', is_int( $draft['net'] ) && is_int( $draft['vat'] ) && is_int( $draft['gross'] ) && is_int( $draft['lines'][0]['price_net'] ) && is_int( $draft['lines'][0]['amount_net'] ) && is_int( $draft['lines'][0]['qty'] ) );

$offer = docDraft( $web, 'offer', $ada );

check( 'an offer has a validity day and no due day', ( $offer['valid_until'] ?? '' ) === Documents::addDays( date( 'Y-m-d' ), 30 ) && array_key_exists( 'due_date', $offer ) === true && $offer['due_date'] === null );

$lines = static fn( array $line = [] ): array => [ $line + [ 'title' => 'Mix', 'qty' => '1', 'price' => '10,00' ] ];
$answer = static fn( string $action, array $data ): string => docAnswer( $web, $action, $data );
$id = (int) $draft['id'];

check( 'a draft refuses what is wrong with the status and the code of its field', $answer( 'crm-documents/save', [ 'category' => 'nothing', 'client_id' => $ada ] ) === '400 crm_documents_category'
	&& $answer( 'crm-documents/save', [ 'category' => 'credit', 'client_id' => $ada ] ) === '400 crm_documents_category' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => 9999 ] ) === '400 crm_documents_client'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice' ] ) === '400 crm_documents_client' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'date' => '2026-02-30' ] ) === '400 crm_documents_date'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'period_from' => '2026-02-01' ] ) === '400 crm_documents_period' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'reverse_charge' => true ] ) === '409 crm_documents_reverse'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'notes' => str_repeat( 'x', 5001 ) ] ) === '400 crm_documents_long' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'template_key' => 'no-such' ] ) === '409 crm_documents_template' );
check( 'a line refuses an empty title, a quantity or price that is no number, a rate the list does not have, and a line that is no object, each with its place', $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'title' => ' ' ] ) ] ) === '400 crm_documents_line_empty'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'qty' => 'x' ] ) ] ) === '400 crm_documents_line_amount' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'qty' => '0' ] ) ] ) === '400 crm_documents_line_amount'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'price' => '10000000,01' ] ) ] ) === '400 crm_documents_line_amount' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'vat_rate' => 1900 ] ) ] ) === '400 crm_documents_line_rate'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => [ 'x' ] ] ) === '400 crm_documents_line_format' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'unit_code' => 'ZZZ' ] ) ] ) === '400 crm_documents_line_format'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => $lines( [ 'title' => str_repeat( 'x', 201 ) ] ) ] ) === '400 crm_documents_line_long' && $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => 'text' ] ) === '400 crm_documents_format' );
check( 'more than 200 lines and a sum past the largest are refused', $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => array_fill( 0, 201, $lines()[0] ) ] ) === '400 crm_documents_lines'
	&& $answer( 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'lines' => array_fill( 0, 11, [ 'title' => 'Big', 'qty' => '100', 'price' => '10000000' ] ) ] ) === '400 crm_documents_total' );

$injected = docBody( $web, 'crm-documents/save', [ 'id' => $id, 'notes' => "<script>alert(1)</script> [[/project/company/general/name]] \0 x" ] );

check( 'a text is kept as it was written and goes out as json, never as markup that something reads again', str_contains( (string) ( $injected['document']['notes'] ?? '' ), '<script>' ) === true && is_string( \Nino\Http::class ) );

check( 'a draft is changed with the fields given and the lines replaced', ( docBody( $web, 'crm-documents/save', [ 'id' => $id, 'lines' => [ [ 'title' => 'Two', 'qty' => '2', 'price' => '25,50', 'unit' => 'hrs' ] ], 'title' => 'ignored' ] )['document']['net'] ?? 0 ) === 5100
	&& count( Documents::get( $web, $id )['lines'] ) === 1 && Documents::get( $web, $id )['title'] === '' );
check( 'a draft is deleted with its lines, and a document nobody has is the 404', docBody( $web, 'crm-documents/remove', [ 'id' => (int) $offer['id'] ] ) === [ 'removed' => true ] && Db::row( $web, 'SELECT COUNT(*) AS n FROM document_lines WHERE document_id = :id', [ 'id' => (int) $offer['id'] ] )['n'] === 0
	&& $answer( 'crm-documents/remove', [ 'id' => (int) $offer['id'] ] ) === '404 crm_documents_unknown' && $answer( 'crm-documents/get', [ 'id' => 999999 ] ) === '404 crm_documents_unknown' );
check( 'an id that is none is the 400 and never reaches the database: a text, zero, a negative, a list, an object, a huge number, a null byte', array_filter( [ 'x', '0', '-1', [ 1 ], [ 'a' => 1 ], '99999999999', "1\0", '1e1', ' 1', '01' ], static fn( mixed $one ): bool => in_array( $answer( 'crm-documents/get', [ 'id' => $one ] ), [ '400 crm_documents_id', '404 crm_documents_unknown' ], true ) === false ) === [] );

$items = docBody( $web, 'crm-documents/list' );

check( 'the list carries the documents, the years, the clients with what is missing of their address, the settings with the rates, the items and what the export can do', count( $items['documents'] ?? [] ) === 1 && ( $items['years'] ?? [] ) === [ (int) date( 'Y' ) ] && ( $items['clients'][1]['ready'] ?? true ) === false && ( $items['clients'][0]['ready'] ?? false ) === true
	&& ( $items['settings']['rates'] ?? [] ) === [ 1900, 700, 0 ] && ( $items['export']['limit'] ?? 0 ) === 500 && is_bool( $items['export']['zip'] ?? null ) );
check( 'the list is filtered by kind, state, year, client and project, and a filter that is none is the 400', count( docBody( $web, 'crm-documents/list', [ 'category' => 'offer' ] )['documents'] ) === 0 && count( docBody( $web, 'crm-documents/list', [ 'category' => 'invoice', 'state' => 'draft', 'year' => (int) date( 'Y' ), 'client' => $ada ] )['documents'] ) === 1
	&& $answer( 'crm-documents/list', [ 'year' => 'x' ] ) === '400 crm_documents_format' && $answer( 'crm-documents/list', [ 'client' => '1; DROP TABLE documents' ] ) === '400 crm_documents_format' && $answer( 'crm-documents/list', [ 'project' => 'a b' ] ) === '400 crm_documents_format'
	&& count( docBody( $web, 'crm-documents/list', [ 'category' => "x' OR '1'='1" ] )['documents'] ) === 1 );

// --- Finalising ---------------------------------------------------------------------------------------------------------------

$answer( 'crm-documents/save', [ 'id' => $id, 'lines' => [ [ 'title' => 'Mix', 'qty' => '1,5', 'price' => '100,00' ] ] ] );
$year = (int) date( 'Y' );

$stopped = static fn( array $draft ): array => [ $answer( 'crm-documents/finalise', [ 'id' => (int) $draft['id'] ] ), Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year ] )['next'] ?? null ];

$unready = docDraft( $web, 'invoice', $bare );
$notemplate = docDraft( $web, 'invoice', $ada, [ 'template_key' => '' ] );
$empty = (array) docBody( $web, 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $ada, 'template_key' => 'invoice', 'lines' => [] ] )['document'];
$wrongTemplate = docDraft( $web, 'invoice', $ada, [ 'template_key' => 'offer' ] );

check( 'a draft with nothing to number is stopped before a number is spent: no address of the client, no template, no line, a template of another tag', $stopped( $unready ) === [ '409 crm_documents_unready', null ] && $stopped( $notemplate ) === [ '409 crm_documents_template', null ]
	&& $stopped( $empty ) === [ '409 crm_documents_empty', null ] && $wrongTemplate === [] );
check( 'a draft whose number format is none or whose date lies in the future is stopped as well, and so is an invoice of nothing', $answer( 'crm-documents/finalise', [ 'id' => 999999 ] ) === '404 crm_documents_unknown'
	&& $answer( 'crm-documents/finalise', [ 'id' => (int) docDraft( $web, 'invoice', $ada, [ 'date' => date( 'Y-m-d', time() + 86400 * 3 ) ] )['id'] ] ) === '400 crm_documents_date' && $answer( 'crm-documents/finalise', [ 'id' => (int) docDraft( $web, 'invoice', $ada, [ 'lines' => [ [ 'title' => 'Zero', 'qty' => '1', 'price' => '0' ] ] ] )['id'] ] ) === '409 crm_documents_gross' );

$final = (array) docBody( $web, 'crm-documents/finalise', [ 'id' => $id ] )['document'];
$prefix = 'R-'. $year. '-';

check( 'finalising takes the number of the sequence, the totals, the frozen client and a PDF file with its hash, all at once', ( $final['number'] ?? '' ) === $prefix. '0001' && $final['state'] === 'open' && $final['net'] === 15000 && $final['gross'] === 15000 && ( $final['client']['name'] ?? '' ) === 'Ada Example'
	&& $final['pdf_path'] === '/data/crm/pdf/'. $year. '/'. $prefix. '0001.pdf' && preg_match( '/^[0-9a-f]{64}$/D', $final['pdf_sha256'] ) === 1 && $final['pdf_url'] === '/.crm/pdf/'. $id && $final['finalised'] !== null );

$file = \Nino\Filesystem::path( $web, $final['pdf_path'] );

check( 'the file is there and is what the hash says, a PDF', is_file( $file ) === true && hash_file( 'sha256', $file ) === $final['pdf_sha256'] && str_starts_with( (string) file_get_contents( $file ), '%PDF-' ) );

// the model refuses a finalised document by itself, whatever the action in front of it asks
$before = Db::row( $web, 'SELECT * FROM documents WHERE id = :id', [ 'id' => $id ] );
$kept = [ Documents::change( $web, $id, [ 'notes' => 'changed behind the action' ] ), Documents::change( $web, $id, [ 'date' => '2001-01-01', 'lines' => [] ] ) ];
check( 'the model refuses to change a finalised document, and the row, its lines and its file are as they were', $kept === [ 'fixed', 'fixed' ] && Db::row( $web, 'SELECT * FROM documents WHERE id = :id', [ 'id' => $id ] ) === $before && hash_file( 'sha256', $file ) === $final['pdf_sha256'] );

// What the books hold of the client: the address as it was
Db::exec( $web, 'UPDATE clients SET street = :street WHERE id = :id', [ 'street' => 'Another way 9', 'id' => $ada ] );

check( 'the client of a finalised document is the client it was when it was finalised, and a draft shows the live one', ( Documents::get( $web, $id )['client']['street'] ?? '' ) === 'Weg 1' && ( docDraft( $web, 'invoice', $ada )['client']['street'] ?? '' ) === 'Another way 9' );

// --- A finalised document is fixed: no action changes what it says -------------------------------------------------------------

$frozen = docRow( $web, $id );
$fileBefore = [ hash_file( 'sha256', $file ), filesize( $file ), filemtime( $file ) ];
$each = [
	'save a change of the lines' => [ 'crm-documents/save', [ 'id' => $id, 'lines' => [ [ 'title' => 'Other', 'qty' => '1', 'price' => '1' ] ] ] ],
	'save a change of the date' => [ 'crm-documents/save', [ 'id' => $id, 'date' => '2020-01-01' ] ],
	'save a change of the client' => [ 'crm-documents/save', [ 'id' => $id, 'client_id' => $firm ] ],
	'save the notes with another field' => [ 'crm-documents/save', [ 'id' => $id, 'notes' => 'x', 'title' => 'y' ] ],
	'save the template' => [ 'crm-documents/save', [ 'id' => $id, 'template_key' => 'offer' ] ],
	'save an empty change' => [ 'crm-documents/save', [ 'id' => $id ] ],
	'remove' => [ 'crm-documents/remove', [ 'id' => $id ] ],
	'finalise again' => [ 'crm-documents/finalise', [ 'id' => $id ] ],
];
$refused = [];

foreach( $each as $name => [ $action, $data ] )
	$refused[$name] = $answer( $action, $data );

check( 'no action of the endpoint changes or deletes what a finalised document says: each is the 409 fixed', array_filter( $refused, static fn( string $code ): bool => $code !== '409 crm_documents_fixed' ) === [] );
check( 'and the row, its lines and its file are as they were, to the byte', docRow( $web, $id ) === $frozen && [ hash_file( 'sha256', $file ), filesize( $file ), filemtime( $file ) ] === $fileBefore );

$noted = docBody( $web, 'crm-documents/save', [ 'id' => $id, 'notes' => 'Phoned on the 3rd' ] );

check( 'the notes are what a finalised document still takes, and the rest of the row is as it was', ( $noted['document']['notes'] ?? '' ) === 'Phoned on the 3rd' && array_diff( array_keys( array_diff_assoc( docRow( $web, $id )[0], $frozen[0] ) ), [ 'notes', 'updated' ] ) === [] && docRow( $web, $id )[0]['notes'] === 'Phoned on the 3rd' && docRow( $web, $id )[1] === $frozen[1] );

// --- Numbers that run without a gap and are never given twice ---------------------------------------------------------------------

$next = static function() use ( &$web, $year ): ?int {
	$row = Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year ] );

	return $row === null ? null : (int) $row['next'];
};
$numbers = static fn(): array => array_column( Db::rows( $web, 'SELECT number FROM documents WHERE category = \'invoice\' AND number IS NOT NULL ORDER BY number' ), 'number' );

$d2 = docDraft( $web, 'invoice', $ada );
$d3 = docDraft( $web, 'invoice', $ada );
$gone = docDraft( $web, 'invoice', $ada );
docBody( $web, 'crm-documents/remove', [ 'id' => $gone['id'] ] );

check( 'a draft that is made and deleted spends no number', $next() === 2 );

$f2 = docBody( $web, 'crm-documents/finalise', [ 'id' => $d2['id'] ] )['document'];
$f3 = docBody( $web, 'crm-documents/finalise', [ 'id' => $d3['id'] ] )['document'];

check( 'the next documents follow the first without a gap', $f2['number'] === $prefix. '0002' && $f3['number'] === $prefix. '0003' && $next() === 4 );

// A thing lies where the next file goes, whatever it is and whoever put it there: a file - the file of a document whose row is missing, say - a link, a folder. Nothing is touched and no number is spent
$d4 = docDraft( $web, 'invoice', $ada );
$target = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year. '/'. $prefix. '0004.pdf' );
file_put_contents( $target, 'not ours' );

check( 'a file that lies where the PDF would go and that no row names refuses the finalising: nothing is numbered, the draft stays, the file stays as it was and nothing is set aside', docAnswer( $web, 'crm-documents/finalise', [ 'id' => $d4['id'] ] ) === '409 crm_documents_exists' && $next() === 4
	&& Documents::get( $web, (int) $d4['id'] )['state'] === 'draft' && file_get_contents( $target ) === 'not ours' && glob( $target. '*' ) === [ $target ] );

$stray = \Nino\Modules\CrmDocuments\Status::files( $web );

check( 'the status says that it is the file of a document without a row, names the number as given away and says what to do - and nothing is deleted', $stray['state'] === 'stray' && $stray['files'] === 1 && $stray['names'] === [ $prefix. '0004.pdf' ] && $stray['numbers'] === [ $prefix. '0004' ] && str_contains( $stray['detail'], $prefix. '0004' ) && str_contains( $stray['detail'], $prefix. '0004.pdf' ) === false && str_contains( $stray['detail'], explode( '%s', \Nino\Modules\Crm\Surface\Texts::word( $web, 'docs.detail-pdf-files-stray' ) )[0] ) && is_file( $target ) === true );

unlink( $target );
symlink( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year. '/'. $prefix. '0001.pdf' ), $target );

check( 'so does a link, and the file it points at is not touched', docAnswer( $web, 'crm-documents/finalise', [ 'id' => $d4['id'] ] ) === '409 crm_documents_exists' && $next() === 4 && hash_file( 'sha256', $file ) === $final['pdf_sha256'] );

unlink( $target );
mkdir( $target );

check( 'so does a folder', docAnswer( $web, 'crm-documents/finalise', [ 'id' => $d4['id'] ] ) === '409 crm_documents_exists' && $next() === 4 );

rmdir( $target );
$f4 = docBody( $web, 'crm-documents/finalise', [ 'id' => $d4['id'] ] )['document'];

check( 'once the way is clear the number is the one that was not spent: there is no gap where the refusals were', $f4['number'] === $prefix. '0004' && $next() === 5 && $numbers() === [ $prefix. '0001', $prefix. '0002', $prefix. '0003', $prefix. '0004' ] );

// The PDF could not be made: the template the project has is missing a piece
$templates = \Nino\Filesystem::path( $web, '/templates/pdf' );
$piece = $templates. '/crm-documents-lines.tpl';
$kept = (string) file_get_contents( $piece );
$d5 = docDraft( $web, 'invoice', $ada );
unlink( $piece );
$markup = docPost( $web, 'crm-documents/finalise', [ 'id' => $d5['id'] ] );
file_put_contents( $piece, $kept );

check( 'a template the project lacks makes no PDF and names the piece: 409, nothing numbered, no file written', docCode( $markup ) === '409 crm_documents_markup' && ( $markup[1]['params'][0] ?? '' ) === '/templates/pdf/crm-documents-lines.tpl' && $next() === 5
	&& glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*' ) === glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*.pdf' ) && count( glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*.pdf' ) ) === 4 );
ninoWarnings();

// A word the project lacks
$texts = \Nino\Filesystem::path( $web, '/text/de_DE.php' );
$words = (string) file_get_contents( $texts );
file_put_contents( $texts, str_replace( "'[[/feature/crm-documents/pdf-head/qty]]'", "'[[/feature/crm-documents/pdf-head/qty-gone]]'", $words ) );
$missingWord = docPost( $web, 'crm-documents/finalise', [ 'id' => $d5['id'] ] );
file_put_contents( $texts, $words );

check( 'a word the project lacks makes no PDF either, and names the word', docCode( $missingWord ) === '409 crm_documents_words' && ( $missingWord[1]['params'][0] ?? '' ) === '/feature/crm-documents/pdf-head/qty' && $next() === 5 );
ninoWarnings();

// A number that is taken: a format with no year meets the next year
$web2 = docRequest( $where );
docSignIn( $web2, 'admin@example.org' );
check( 'the format of the numbers is saved without the year', docAnswer( $web2, 'crm/settings-save', [ 'number_invoice' => 'N{N}' ] ) === '200 -' );

$old = docDraft( $web2, 'invoice', $ada, [ 'date' => ( $year - 1 ). '-06-01' ] );
$new = docDraft( $web2, 'invoice', $ada, [ 'date' => ( $year - 2 ). '-01-01' ] );
$oldFinal = docBody( $web2, 'crm-documents/finalise', [ 'id' => $old['id'] ] )['document'] ?? [];
$taken = docPost( $web2, 'crm-documents/finalise', [ 'id' => $new['id'] ] );

check( 'a number that another document has is never given again: the document of another year, whose counter says the same number, is refused, no counter of that year is made, the draft stays', ( $oldFinal['number'] ?? '' ) === 'N1' && docCode( $taken ) === '409 crm_documents_number'
	&& Db::row( $web2, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year - 2 ] ) === null && Documents::get( $web2, (int) $new['id'] )['state'] === 'draft' );
check( 'the number of a document of the earlier year is in the folder of its date', is_file( \Nino\Filesystem::path( $web2, '/data/crm/pdf/'. ( $year - 1 ). '/N1.pdf' ) ) === true );

docAnswer( $web2, 'crm/settings-save', [ 'number_invoice' => 'R-{YYYY}-{NNNN}' ] );
$web = docRequest( $where );
docSignIn( $web, 'admin@example.org' );

// Eight processes finalise drafts at once, and four of them the same drafts
$mine = [];

for( $at = 0; $at < 24; $at++ )
	$mine[] = (int) docDraft( $web, 'invoice', $ada )['id'];

$go = $where. '/go-finalise';
$processes = [];
$pipes = [];

foreach( range( 0, 7 ) as $worker )
	$processes[$worker] = proc_open( array_merge( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $go ], $worker < 4 ? array_slice( $mine, $worker * 5, 5 ) : array_slice( $mine, 20, 4 ) ), [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes[$worker], null, [ 'NINO_ROOT' => $root ] + getenv() );

docAwaitUp( $go, 8 );
file_put_contents( $go, 'go' );
$results = [];

foreach( $processes as $worker => $process ) {
	$results[$worker] = json_decode( (string) stream_get_contents( $pipes[$worker][1] ), true );
	stream_get_contents( $pipes[$worker][2] );
	proc_close( $process );
}

$issued = array_column( Db::rows( $web, 'SELECT number FROM documents WHERE category = \'invoice\' AND number IS NOT NULL AND id IN ( '. implode( ',', $mine ). ' ) ORDER BY number' ), 'number' );
$expected = array_map( static fn( int $n ): string => $prefix. sprintf( '%04d', $n ), range( 5, 5 + 20 + 4 - 1 ) );
$received = array_merge( ...array_values( array_filter( $results, 'is_array' ) ) );
$wins = array_values( array_filter( $received, static fn( string $one ): bool => str_starts_with( $one, $prefix ) ) );

check( 'eight processes that finalise 24 drafts at once - four of them the same four - leave 24 numbers, all different, one after the other, with no gap after the 4 before them', count( array_filter( $results, 'is_array' ) ) === 8 && $issued === $expected && count( array_unique( $issued ) ) === 24 );
check( 'a draft that four processes finalised at once is finalised once: the others are told it is fixed', count( $wins ) === 24 && count( array_filter( $received, static fn( string $one ): bool => $one === '409 crm_documents_fixed' ) ) === 12 && $next() === 29 );
check( 'and every file of those is there once, as its hash says, and no file lies in the folder that no document names', count( glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*.pdf' ) ) === 28
	&& array_filter( Db::rows( $web, 'SELECT pdf_path, pdf_sha256 FROM documents WHERE pdf_path <> \'\'' ), static fn( array $row ): bool => hash_file( 'sha256', \Nino\Filesystem::path( $web, $row['pdf_path'] ) ) !== $row['pdf_sha256'] ) === []
	&& glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*.tmp' ) === [] );

// --- Writers at the same time -------------------------------------------------------------------------------------------------

// Two processes are given the same hundred and fifty names of a quarter of a megabyte to write at once: each name is given to one of them, and holds what that one wrote - a rename() would give it to both and keep what came last
$go = $where. '/go-keep';
$pair = [];

foreach( [ 'A', 'B' ] as $tag )
	$pair[$tag] = proc_open( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $go, $tag ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pairPipes[$tag], null, [ 'DOCS_MODE' => 'keep' ] + getenv() );

docAwaitUp( $go, 2 );
file_put_contents( $go, 'go' );
$took = [];

foreach( $pair as $tag => $process ) {
	$took[$tag] = (array) json_decode( (string) stream_get_contents( $pairPipes[$tag][1] ), true );
	stream_get_contents( $pairPipes[$tag][2] );
	proc_close( $process );
}

unlink( $go );
$twice = 0;
$wrong = 0;
$nobody = 0;

foreach( range( 1, 150 ) as $at ) {

	$who = array_keys( array_filter( [ 'A' => $took['A'][$at] ?? false, 'B' => $took['B'][$at] ?? false ] ) );
	$held = (string) @file_get_contents( \Nino\Filesystem::path( $web, '/data/crm/pdf/2019/k'. $at. '.pdf' ), false, null, 0, 1 );

	$twice += count( $who ) > 1 ? 1 : 0;
	$nobody += $who === [] ? 1 : 0;
	$wrong += count( $who ) === 1 && $held !== $who[0] ? 1 : 0;
}

docRemove( \Nino\Filesystem::path( $web, '/data/crm/pdf/2019' ) );

check( 'two processes that write the same hundred and fifty names of a quarter of a megabyte at once: each name is given to exactly one of them and holds what that one wrote', $twice === 0 && $nobody === 0 && $wrong === 0 );

// A change that has read its draft as a draft and waits for the lock of the database while the draft is finalised meets a finalised document when it gets the lock: it changes nothing. The lock is held by this process, which
// finalises the draft by hand while the writer of the change waits, then lets go
$race = docDraft( $web, 'invoice', $ada, [ 'date' => ( $year - 5 ). '-02-02' ] );
$raceBefore = docRow( $web, (int) $race['id'] );
$go = $where. '/go-race';
$raceLock = Db::pdo( $web );
$raceLock->exec( 'BEGIN IMMEDIATE' );
$writer = proc_open( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $go, (string) $race['id'] ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $racePipes, null, [ 'DOCS_MODE' => 'change' ] + getenv() );
file_put_contents( $go, 'go' );

while( is_file( $go. '-ready' ) === false )
	usleep( 1000 );

// The writer has started: it reads the draft, cleans the input and asks for the lock, which it waits for without doing anything - what is told by the process using no time of the processor for a while
docAwaitIdle( (int) proc_get_status( $writer )['pid'] );
$raceLock->exec( 'UPDATE documents SET state = \'open\', number = \'RACE-1\', finalised = \'2020-01-01T00:00:00Z\' WHERE id = '. (int) $race['id'] );
$raceLock->exec( 'COMMIT' );
$raced = json_decode( (string) stream_get_contents( $racePipes[1] ), true );
stream_get_contents( $racePipes[2] );
proc_close( $writer );
unlink( $go );
unlink( $go. '-ready' );
$raceAfter = docRow( $web, (int) $race['id'] );

check( 'a change that waited for the lock while its draft was finalised is told the document is fixed and changes nothing: not the lines, not the totals', $raced === '409 crm_documents_fixed' && $raceAfter[1] === $raceBefore[1] && $raceAfter[0]['gross'] === $raceBefore[0]['gross'] && $raceAfter[0]['state'] === 'open' );

// --- A process that is killed while it finalises ---------------------------------------------------------------------------------

$crashed = static function( int $draft, string $mode ) use ( &$web, $where, $features ): int {
	$go = $where. '/go-crash-'. $draft;
	file_put_contents( $go, 'go' );
	$process = proc_open( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $go, (string) $draft ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, null, [ 'DOCS_CRASH' => $mode ] + getenv() );
	$out = stream_get_contents( $pipes[1] );
	stream_get_contents( $pipes[2] );
	$code = proc_close( $process );
	unlink( $go );

	return $out === '' ? $code : 0;
};
$counter = static fn( int $on ): mixed => ( Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $on ] ) ?? [ 'next' => null ] )['next'];
$killed = static fn( int $code ): bool => in_array( $code, [ 9, 137 ], true );
$crashLine = [ 'title' => 'Mix', 'qty' => '1', 'price' => '100,00', 'unit_code' => 'HUR', 'unit' => 'h' ];
$crashFiles = static fn( int $on, string $pattern ): array => array_map( 'basename', glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on ). '/'. $pattern ) ?: [] );

// Before the commit: the files lie under pending names that no document can have. They block nothing: the draft is a draft with no number, the counter is as it was, and the next invoice has the number
foreach( [ 'pdf' => [ $ada, $crashYear = $year - 4 ], 'xml' => [ $firm, $year - 8 ] ] as $what => [ $client, $on ] ) {

	$draft = docDraft( $web, 'invoice', $client, [ 'date' => $on. '-03-03', 'lines' => [ $crashLine ] ] );
	$before = $counter( $on );
	$code = $crashed( (int) $draft['id'], '1' );
	$name = 'R-'. $on. '-0001';
	$pendings = $crashFiles( $on, '*' );

	check( 'a process killed between the files and the commit leaves the draft a draft with no number, the counter as it was, and the file'. ( $what === 'xml' ? 's' : '' ). ' under a pending name that no document can have ('. $what. ')',
		$killed( $code ) && Documents::get( $web, (int) $draft['id'] )['state'] === 'draft' && Documents::get( $web, (int) $draft['id'] )['number'] === '' && $counter( $on ) === $before
		&& count( $pendings ) === ( $what === 'xml' ? 2 : 1 ) && array_filter( $pendings, static fn( string $file ): bool => preg_match( '~^'. preg_quote( $name, '~' ). '\.(pdf|xml)\.pending-[0-9a-f]{12}$~D', $file ) !== 1 ) === [] );

	$line = \Nino\Modules\CrmDocuments\Status::files( $web );

	check( 'the status names them as files that were left, as a hint, and counts none of them as a stray file or a missing one ('. $what. ')', $line['state'] === 'pending' && $line['pending'] === count( array_filter( $line['pending_names'], static fn( string $one ): bool => str_starts_with( $one, $on. '/' ) ) ) + count( array_filter( $line['pending_names'], static fn( string $one ): bool => str_starts_with( $one, $on. '/' ) === false ) ) && $line['pending'] >= count( $pendings ) && $line['files'] === 0 && $line['missing'] === 0 && str_contains( $line['detail'], $on. '/'. $name. '.pdf.pending-' ) );

	$other = docDraft( $web, 'invoice', $what === 'xml' ? $ada : $client, [ 'date' => $on. '-03-04', 'lines' => [ $crashLine ] ] );
	$done = docBody( $web, 'crm-documents/finalise', [ 'id' => $other['id'] ] )['document'] ?? [];
	$folderOf = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on );

	check( 'the next invoice gets the number that was not spent - to a client of the other kind too - with files of its own whose hashes are the stored ones, and the files of the crash are deleted by that finalising, for they hold the address and the mail of a client and no row holds their hash ('. $what. ')',
		( $done['number'] ?? '' ) === $name && hash_file( 'sha256', $folderOf. '/'. $name. '.pdf' ) === $done['pdf_sha256'] && $counter( $on ) === 2 && $crashFiles( $on, '*.pending-*' ) === []
		&& ( $what === 'pdf' || ( is_file( $folderOf. '/'. $name. '.xml' ) === false && ( $done['xml_path'] ?? 'x' ) === '' ) ) );

	if( $what === 'xml' ) {

		$again = docFinal( $web, 'invoice', $firm, [ 'date' => $on. '-03-05', 'lines' => [ $crashLine ] ] );

		check( 'and the next invoice of a business client has its electronic invoice, and no electronic invoice of another document lies under a name of a document ('. $what. ')', ( $again['number'] ?? '' ) === 'R-'. $on. '-0002' && hash_file( 'sha256', $folderOf. '/R-'. $on. '-0002.xml' ) === $again['xml_sha256']
			&& count( $crashFiles( $on, '*.xml' ) ) === 1 );
	}
}

// After the commit: the row is there and the files lie under pending names. They get their names where they are asked for - by the download, the status, the export and the next finalising - when their hash is the row's. Each case
// is looked at before the next process starts, for a process that finalises gives the files of the cases before it their names
$died = static function( string $what, int $client, int $on ) use ( &$web, $crashed, $killed, $crashLine, $crashFiles ): array {

	$draft = docDraft( $web, 'invoice', $client, [ 'date' => $on. '-03-03', 'lines' => [ $crashLine ] ] );
	$code = $crashed( (int) $draft['id'], '2' );
	$name = 'R-'. $on. '-0001';
	$row = Documents::get( $web, (int) $draft['id'] );

	check( 'a process killed after the commit has left the document finalised with its number, and its files under pending names only ('. $what. ')',
		$killed( $code ) && $row['state'] === 'open' && $row['number'] === $name && $row['pdf_path'] === '/data/crm/pdf/'. $on. '/'. $name. '.pdf' && ( $what === 'private' ? $row['xml_path'] === '' : $row['xml_path'] === '/data/crm/pdf/'. $on. '/'. $name. '.xml' )
		&& count( $crashFiles( $on, $name. '.*.pending-*' ) ) === ( $what === 'private' ? 1 : 2 ) && $crashFiles( $on, $name. '.pdf' ) === [] && $crashFiles( $on, $name. '.xml' ) === [] );

	return [ $draft, $on, $name ];
};

[ $draft, $on, $name ] = $died( 'download', $firm, $year - 9 );
$pdfDownload = docGet( $web, '/.crm/pdf/'. $draft['id'] );

check( 'the download of the PDF gives the file its name where its hash is the one of the row, and sends it', $pdfDownload[0] === 200 && hash_file( 'sha256', \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on. '/'. $name. '.pdf' ) ) === Documents::get( $web, (int) $draft['id'] )['pdf_sha256'] && $crashFiles( $on, $name. '.pdf.pending-*' ) === [] && count( $crashFiles( $on, $name. '.xml.pending-*' ) ) === 1 );

$xmlDownload = docGet( $web, '/.crm/xml/'. $draft['id'] );

check( 'and so does the download of the electronic invoice, apart from the PDF', $xmlDownload[0] === 200 && hash_file( 'sha256', \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on. '/'. $name. '.xml' ) ) === Documents::get( $web, (int) $draft['id'] )['xml_sha256'] && $crashFiles( $on, $name. '.*pending-*' ) === [] );

[ $draft, $on, $name ] = $died( 'tampered', $firm, $year - 10 );
$xmlPending = $crashFiles( $on, $name. '.xml.pending-*' )[0] ?? '';
$pending = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on ). '/'. $xmlPending;
file_put_contents( $pending, 'tampered', FILE_APPEND );
$tamperedPdf = docGet( $web, '/.crm/pdf/'. $draft['id'] );
$tamperedXml = docGet( $web, '/.crm/xml/'. $draft['id'] );
$missing = \Nino\Modules\CrmDocuments\Status::files( $web );

check( 'a file under a pending name whose hash is not the row\'s is never given the name of the document: the PDF is sent, the electronic invoice is the 409 that says the file is missing and no file of another content lies under its name', $tamperedPdf[0] === 200 && $tamperedXml[0] === 409 && is_file( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on. '/'. $name. '.xml' ) ) === false && is_file( $pending ) === true );
check( 'and the status says that a file is missing, names it, and says the file that was left is another', $missing['state'] === 'missing' && $missing['missing'] === 1 && $missing['missing_names'] === [ $name. '.xml' ] && str_contains( $missing['detail'], $name. '.xml' ) && in_array( $on. '/'. $xmlPending, $missing['pending_names'], true ) === true );

// What the owner does about it: the file that is lost is no part of the document any more, and the tampered file that was left goes
Db::exec( $web, 'UPDATE documents SET xml_path = \'\', xml_sha256 = \'\' WHERE id = :id', [ 'id' => $draft['id'] ] );
unlink( $pending );

[ $draft, $on, $name ] = $died( 'private', $ada, $year - 11 );
$privateDownload = docGet( $web, '/.crm/pdf/'. $draft['id'] );

check( 'the PDF of an invoice of a private client, which has no electronic invoice, is given its name by its download as well, and no electronic invoice appears', $privateDownload[0] === 200 && $crashFiles( $on, $name. '.*' ) === [ $name. '.pdf' ] && docGet( $web, '/.crm/xml/'. $draft['id'] )[0] === 404 );

[ $draft, $on, $name ] = $died( 'export', $firm, $year - 14 );
[ , $zipped ] = docGet( $web, '/crm/export/documents-'. $on. '.zip' );
$archive = new \ZipArchive();
$zipNames = [];

if( (string) ( $zipped['file'] ?? '' ) !== '' && $archive->open( (string) $zipped['file'] ) === true ) {

	for( $at = 0; $at < $archive->numFiles; $at++ )
		$zipNames[] = (string) $archive->getNameIndex( $at );

	$archive->close();
}

\Nino\Modules\CrmDocuments\Export::cleanup( $web );

check( 'the export of the year gives the files of a document whose process died after its commit their names and puts them into the ZIP', in_array( $name. '.pdf', $zipNames, true ) === true && in_array( $name. '.xml', $zipNames, true ) === true && $crashFiles( $on, $name. '.*' ) === [ $name. '.pdf', $name. '.xml' ] );

[ $draft, $on, $name ] = $died( 'status', $firm, $year - 15 );
$looked = \Nino\Modules\CrmDocuments\Status::files( $web );

check( 'the status gives the files of a document whose process died after its commit their names, and does not call them missing', $crashFiles( $on, $name. '.*' ) === [ $name. '.pdf', $name. '.xml' ] && $looked['missing'] === 0 && array_filter( $looked['pending_names'], static fn( string $one ): bool => str_starts_with( $one, $on. '/' ) ) === [] );

[ $draft, $on, $name ] = $died( 'next', $firm, $year - 12 );
$successor = docFinal( $web, 'invoice', $firm, [ 'date' => $on. '-03-04', 'lines' => [ $crashLine ] ] );

check( 'the next finalising gives the files of a document whose process died after its commit their names before it draws its number, and takes the number after that one', ( $successor['number'] ?? '' ) === 'R-'. $on. '-0002' && $crashFiles( $on, $name. '.*' ) === [ $name. '.pdf', $name. '.xml' ]
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $on. '/'. $name. '.pdf' ) ) === Documents::get( $web, (int) $draft['id'] )['pdf_sha256'] );

// Many processes ask for the files of a document at once after the process that finalised it died after its commit: each is answered with the file, and the log says nothing of a name that was not given, of a file that is gone, or of a file that could not be read
[ $draft, $on, $name ] = $died( 'race', $firm, $year - 16 );
$go = $where. '/go-download';
$readers = [];

foreach( range( 1, 8 ) as $reader )
	$readers[$reader] = proc_open( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $go, (string) $draft['id'] ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $readerPipes[$reader], null, [ 'DOCS_MODE' => 'download' ] + getenv() );

docAwaitUp( $go, 8 );
file_put_contents( $go, 'go' );
$readerAnswers = [];
$readerWarnings = [];

foreach( $readers as $reader => $process ) {
	$told = (array) json_decode( (string) stream_get_contents( $readerPipes[$reader][1] ), true );
	stream_get_contents( $readerPipes[$reader][2] );
	proc_close( $process );
	$readerAnswers = array_merge( $readerAnswers, array_slice( (array) ( $told['answers'] ?? [] ), 0, 12 ) );
	$readerWarnings = array_merge( $readerWarnings, (array) ( $told['warnings'] ?? [ 'no answer' ] ) );
}

unlink( $go );

check( 'eight processes that ask for the files of a document at once after the process that finalised it died after its commit are all answered with the files, and the log says nothing', count( $readerAnswers ) === 96 && array_filter( $readerAnswers, static fn( mixed $one ): bool => $one !== 200 ) === [] && $readerWarnings === []
	&& $crashFiles( $on, $name. '.*' ) === [ $name. '.pdf', $name. '.xml' ] );

// The step that gives a file its name was cut off after the link and before the pending name went: the pending name lies beside the right file - as a link, or as a copy - and goes, and the status does not call it left over
$linked = docFinal( $web, 'invoice', $firm, [ 'date' => ( $year - 17 ). '-03-03', 'lines' => [ $crashLine ] ] );
$linkedFolder = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. ( $year - 17 ) );
link( $linkedFolder. '/'. $linked['number']. '.pdf', $linkedFolder. '/'. $linked['number']. '.pdf.pending-aaaaaaaaaaaa' );
copy( $linkedFolder. '/'. $linked['number']. '.xml', $linkedFolder. '/'. $linked['number']. '.xml.pending-bbbbbbbbbbbb' );
$cleaned = \Nino\Modules\CrmDocuments\Status::files( $web );

check( 'a pending name that lies beside the right file, as a link or as a copy, is taken away, and the status does not call it left over', $crashFiles( $year - 17, $linked['number']. '.*' ) === [ $linked['number']. '.pdf', $linked['number']. '.xml' ] && array_filter( $cleaned['pending_names'], static fn( string $one ): bool => str_starts_with( $one, ( $year - 17 ). '/' ) ) === []
	&& hash_file( 'sha256', $linkedFolder. '/'. $linked['number']. '.pdf' ) === $linked['pdf_sha256'] && hash_file( 'sha256', $linkedFolder. '/'. $linked['number']. '.xml' ) === $linked['xml_sha256'] );

// A file that another process gave the name first is the file of the name where its hash is the stored one, and then the pending name goes; a file of another hash at the name is refused, and what lies there is not touched
$directPath = '/data/crm/pdf/2019/direct.pdf';
$directBytes = 'the bytes of a document';
$directHash = hash( 'sha256', $directBytes );
$first = \Nino\Modules\CrmDocuments\Render::stage( $web, $directPath, $directBytes );
copy( $first, \Nino\Filesystem::path( $web, $directPath ) );
$givenByOther = \Nino\Modules\CrmDocuments\Render::publish( $web, $directPath, $first, $directHash );
$second = \Nino\Modules\CrmDocuments\Render::stage( $web, $directPath, 'another content' );
$keptOther = \Nino\Modules\CrmDocuments\Render::publish( $web, $directPath, $second, $directHash );
$refused = \Nino\Modules\CrmDocuments\Render::publish( $web, $directPath, $second, hash( 'sha256', 'another content' ) );
symlink( \Nino\Filesystem::path( $web, $directPath ), \Nino\Filesystem::path( $web, '/data/crm/pdf/2019/link.pdf' ) );
$viaLink = \Nino\Modules\CrmDocuments\Render::settle( $web, '/data/crm/pdf/2019/link.pdf', $directHash );

check( 'where another process gave the file its name first publishing says it is there and takes the pending name away; where the name holds a file of another hash it says no and leaves both files; and a link at a name is no file of it', $givenByOther === true && is_file( $first ) === false
	&& $keptOther === true && is_file( $second ) === true && $refused === false && file_get_contents( \Nino\Filesystem::path( $web, $directPath ) ) === $directBytes && $viaLink === false );

docRemove( \Nino\Filesystem::path( $web, '/data/crm/pdf/2019' ) );

// A link at the name of a file is no file of the document, and the status says that something else lies there; what it says of two things at once, it says of both
$linkedXml = $linkedFolder. '/'. $linked['number']. '.xml';
$awayXml = dirname( $linkedFolder ). '/away.xml';
rename( $linkedXml, $awayXml );
symlink( $awayXml, $linkedXml );
file_put_contents( $linkedFolder. '/R-9998.pdf', 'a stray one' );
$both = \Nino\Modules\CrmDocuments\Status::files( $web );
unlink( $linkedXml );
rename( $awayXml, $linkedXml );
unlink( $linkedFolder. '/R-9998.pdf' );
$wordOf = static fn( string $key ): string => explode( '%s', \Nino\Modules\Crm\Surface\Texts::word( $web, 'docs.detail-pdf-files-'. $key ) )[0];

check( 'the status counts a link at the name of a file as something else at the name, and tells the stray file as well, each with its own sentence', $both['state'] === 'foreign' && $both['foreign'] === 1 && $both['foreign_names'] === [ $linked['number']. '.xml' ] && $both['missing'] === 0 && $both['numbers'] === [ 'R-9998' ]
	&& str_contains( $both['detail'], $wordOf( 'foreign' ) ) && str_contains( $both['detail'], $wordOf( 'stray' ) ) );

// The refusal of a format that two kinds share names the field that was posted
$sameFormat = \Nino\Modules\Crm\Settings::get( $web, 'number_invoice' );
$fieldOf = static fn( array $post ): string => (string) ( docPost( $web, 'crm/settings-save', $post )[1]['field'] ?? '' );

check( 'the refusal of a format that another kind has names the field that was posted, whichever kind it is', $fieldOf( [ 'number_credit' => $sameFormat ] ) === 'number_credit' && $fieldOf( [ 'number_offer' => $sameFormat ] ) === 'number_offer' );

// A pending file that a row holds by its hash is never deleted, one that no row holds is: the first is the document, which needs only its name, and the other is what a cut-off attempt left
$kept = $linkedFolder. '/'. $linked['number']. '.pdf.pending-cccccccccccc';
$left = $linkedFolder. '/R-9999.pdf.pending-dddddddddddd';
file_put_contents( $kept, file_get_contents( $linkedFolder. '/'. $linked['number']. '.pdf' ) );
file_put_contents( $left, 'Weg 1, 12345 Berlin, ada@example.org' );
$purged = \Nino\Modules\CrmDocuments\Render::purge( $web );

check( 'what no row holds by its hash is deleted from the pending files and what a row holds is not', $purged === 1 && is_file( $kept ) === true && is_file( $left ) === false );

unlink( $kept );

// A finalising that is cut off before its commit leaves the address and the mail of a client in its files: the deletion of the client, whose only draft it was, deletes them too, and no file of the folder has them any more
$private = docClient( $web, [ 'name' => 'Gone Entirely', 'company' => 'Gone GmbH', 'business' => true, 'vat_id' => 'DE999999998', 'street' => 'Tilgungsweg 77', 'email' => 'gone.entirely@example.org', 'buyer_reference' => '991-12345-68' ] );
$privateDraft = docDraft( $web, 'invoice', $private, [ 'date' => ( $year - 18 ). '-03-03', 'lines' => [ $crashLine ] ] );
$killed18 = $crashed( (int) $privateDraft['id'], '1' );
$privateFolder = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. ( $year - 18 ) );
$before = array_filter( glob( $privateFolder. '/*' ) ?: [], static fn( string $file ): bool => str_contains( (string) file_get_contents( $file ), 'Tilgungsweg 77' ) || str_contains( (string) file_get_contents( $file ), 'gone.entirely@example.org' ) );
$erased = docBody( $web, 'crm-clients/delete', [ 'id' => $private, 'confirm' => (string) \Nino\Modules\Crm\Clients::get( $web, $private )['number'] ] );
$after = array_filter( glob( $privateFolder. '/*' ) ?: [], static fn( string $file ): bool => str_contains( (string) file_get_contents( $file ), 'Tilgungsweg 77' ) || str_contains( (string) file_get_contents( $file ), 'gone.entirely@example.org' ) );

check( 'the files that a cut-off finalising left carry the address of the client, and the deletion of the client deletes them: no file of the folder holds the street or the mail afterwards', $killed($killed18) && count( $before ) >= 1 && ( $erased['number'] ?? '' ) !== '' && $after === [] && glob( $privateFolder. '/*' ) === [] );

// An older backup was restored: the document that was finalised after it has no row and its file is still under the pending name. It was never handed out, for every download, status, export and finalising names such a file
// before; the draft it was made from is in the restored database, and the next finalising deletes what no row holds and gives the number again
$lastYear = $year - 19;
$lastDraft = docDraft( $web, 'invoice', $firm, [ 'date' => $lastYear. '-03-03', 'lines' => [ $crashLine ] ] );
$killed19 = $crashed( (int) $lastDraft['id'], '2' );
Db::exec( $web, 'DELETE FROM documents WHERE id = :id', [ 'id' => $lastDraft['id'] ] );
Db::exec( $web, 'UPDATE sequences SET next = 1 WHERE category = \'invoice\' AND year = :year', [ 'year' => $lastYear ] );
$orphans = $crashFiles( $lastYear, '*.pending-*' );
$again19 = docFinal( $web, 'invoice', $firm, [ 'date' => $lastYear. '-03-04', 'lines' => [ $crashLine ] ] );

check( 'after an older backup was restored the pending files of a document whose row is gone are deleted by the next finalising, which gives the number again with files of its own, and nothing that a row holds is lost', $killed( $killed19 ) && count( $orphans ) === 2 && ( $again19['number'] ?? '' ) === 'R-'. $lastYear. '-0001' && $crashFiles( $lastYear, '*' ) === [ 'R-'. $lastYear. '-0001.pdf', 'R-'. $lastYear. '-0001.xml' ]
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $lastYear. '/R-'. $lastYear. '-0001.pdf' ) ) === $again19['pdf_sha256'] );

// The file of a finalised document is gone: the download says so, and the owner has it made again, which is taken only where its hash is the stored one
$gap = docFinal( $web, 'invoice', $firm, [ 'date' => ( $year - 20 ). '-03-03', 'lines' => [ $crashLine ] ] );
$gapFolder = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. ( $year - 20 ) );
$gapPdf = $gapFolder. '/'. $gap['number']. '.pdf';
$gapXml = $gapFolder. '/'. $gap['number']. '.xml';
$gapBytes = [ file_get_contents( $gapPdf ), file_get_contents( $gapXml ) ];
unlink( $gapPdf );
unlink( $gapXml );
$gapDownload = docGet( $web, '/.crm/pdf/'. $gap['id'] );
$gapLine = \Nino\Modules\CrmDocuments\Status::files( $web );

check( 'a document whose files are gone is the 409 with the sentence that says so, for the PDF and the electronic invoice, and the status says missing and names the files', $gapDownload[0] === 409 && docGet( $web, '/.crm/xml/'. $gap['id'] )[0] === 409 && $gapLine['state'] === 'missing' && $gapLine['missing'] === 2 && str_contains( $gapLine['detail'], $gap['number']. '.pdf' ) );

// The template has been changed since: the file that would be made is another, and nothing is written - for the PDF of an invoice of a private client the answer is the 409, and for the invoice with both files the electronic invoice, which the
// template does not touch, is made and the PDF is told as not made
$privateGap = docFinal( $web, 'invoice', $ada, [ 'date' => ( $year - 20 ). '-03-06', 'lines' => [ $crashLine ] ] );
$privatePdf = \Nino\Filesystem::path( $web, $privateGap['pdf_path'] );
unlink( $privatePdf );
$piece = \Nino\Filesystem::path( $web, '/templates/pdf' ). '/crm-documents-line.tpl';
$pieceBytes = (string) file_get_contents( $piece );
file_put_contents( $piece, str_replace( '[[title]]', '[[title]]!', $pieceBytes ) );
$differs = docPost( $web, 'crm-documents/restore-file', [ 'id' => $privateGap['id'] ] );
$partial = docPost( $web, 'crm-documents/restore-file', [ 'id' => $gap['id'] ] );
file_put_contents( $piece, $pieceBytes );

check( 'where the template has been changed since the file would have another hash: the 409 with its sentence, and nothing is written', docCode( $differs ) === '409 crm_documents_restore_differs' && is_file( $privatePdf ) === false );
check( 'and of a document with both files the one that does not depend on the template is made and the other is told as not made, and not written', $partial[0] === 200 && ( $partial[1]['restored'] ?? [] ) === [ $gap['number']. '.xml' ] && ( $partial[1]['differs'] ?? [] ) === [ $gap['number']. '.pdf' ] && is_file( $gapPdf ) === false && is_file( $gapXml ) === true );

docPost( $web, 'crm-documents/restore-file', [ 'id' => $privateGap['id'] ] );
$made = docPost( $web, 'crm-documents/restore-file', [ 'id' => $gap['id'] ] );
$madeBody = $made[1];

check( 'the owner has the file made again: it is written with the hash that is stored, byte for byte what it was, and the downloads send the files', $made[0] === 200 && ( $madeBody['restored'] ?? [] ) === [ $gap['number']. '.pdf' ] && hash_file( 'sha256', $gapPdf ) === $gap['pdf_sha256'] && file_get_contents( $gapPdf ) === $gapBytes[0]
	&& file_get_contents( $gapXml ) === $gapBytes[1] && docGet( $web, '/.crm/pdf/'. $gap['id'] )[0] === 200 && docGet( $web, '/.crm/xml/'. $gap['id'] )[0] === 200 && glob( $gapFolder. '/*.pending-*' ) === [] );
check( 'and where no file is missing there is nothing to make: the 409, and a draft has no files to make either', docCode( docPost( $web, 'crm-documents/restore-file', [ 'id' => $gap['id'] ] ) ) === '409 crm_documents_restore_nothing' && docCode( docPost( $web, 'crm-documents/restore-file', [ 'id' => docDraft( $web, 'invoice', $ada )['id'] ] ) ) === '409 crm_documents_state'
	&& docCode( docPost( $web, 'crm-documents/restore-file', [ 'id' => 999999 ] ) ) === '404 crm_documents_unknown' );

// A credit note, a document without lines and an invoice of a private client are made again just so, each of its own
$creditOfGap = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $gap['id'] ] )['document'];
docAnswer( $web, 'crm-documents/save', [ 'id' => $creditOfGap['id'], 'date' => ( $year - 20 ). '-03-04', 'template_key' => 'credit-note' ] );
$creditGap = docBody( $web, 'crm-documents/finalise', [ 'id' => $creditOfGap['id'] ] )['document'] ?? [];
$plainGap = docFinal( $web, 'information', $firm, [] );
$kinds = [ $creditGap, $plainGap, docFinal( $web, 'invoice', $ada, [ 'date' => ( $year - 20 ). '-03-05', 'lines' => [ $crashLine ] ] ) ];
$againOk = [];

foreach( $kinds as $one ) {
	$pdfOf = \Nino\Filesystem::path( $web, $one['pdf_path'] ?? '/none' );
	$was = (string) @file_get_contents( $pdfOf );
	@unlink( $pdfOf );
	$xmlOf = ( $one['xml_path'] ?? '' ) === '' ? '' : \Nino\Filesystem::path( $web, $one['xml_path'] );
	$xmlWas = $xmlOf === '' ? '' : (string) @file_get_contents( $xmlOf );

	if( $xmlOf !== '' )
		@unlink( $xmlOf );

	$answer = docPost( $web, 'crm-documents/restore-file', [ 'id' => $one['id'] ?? 0 ] );
	$againOk[] = $answer[0] === 200 && $was !== '' && file_get_contents( $pdfOf ) === $was && ( $xmlOf === '' || file_get_contents( $xmlOf ) === $xmlWas );
}

check( 'a credit note, a document without lines and an invoice of a private client are made again the same way, with the bytes they had', $againOk === [ true, true, true ] );

// Every kind of document is made again with the bytes it had: an offer, an invoice of a business (PDF and electronic invoice), a credit note, a contract with its offer - also when the state of the offer has changed since -, a privacy text and an information sheet
$remadeOffer = docFinal( $web, 'offer', $ada, [ 'date' => ( $year - 20 ). '-03-08' ] );
$remadeContract = docFinal( $web, 'contract', $ada, [ 'parent_id' => $remadeOffer['id'], 'title' => 'Contract on the offer' ] );
$remadeContractAgain = docFinal( $web, 'contract', $ada, [ 'parent_id' => $remadeOffer['id'], 'title' => 'Second contract on the offer' ] );
$remadeKinds = [ 'offer' => $remadeOffer, 'contract' => $remadeContract, 'privacy' => docFinal( $web, 'privacy', $ada, [] ), 'information' => $plainGap, 'invoice with an electronic invoice' => docFinal( $web, 'invoice', $firm, [ 'date' => ( $year - 20 ). '-03-09', 'lines' => [ $crashLine ] ] ), 'credit note' => $creditGap ];
$remadeOk = [];

foreach( $remadeKinds as $what => $one ) {
	$files = array_filter( [ $one['pdf_path'] ?? '', $one['xml_path'] ?? '' ], static fn( string $path ): bool => $path !== '' );
	$was = array_map( static fn( string $path ): string => (string) @file_get_contents( \Nino\Filesystem::path( $web, $path ) ), $files );

	foreach( $files as $path )
		@unlink( \Nino\Filesystem::path( $web, $path ) );

	$answer = docPost( $web, 'crm-documents/restore-file', [ 'id' => $one['id'] ?? 0 ] );
	$now = array_map( static fn( string $path ): string => (string) @file_get_contents( \Nino\Filesystem::path( $web, $path ) ), $files );
	$remadeOk[$what] = $answer[0] === 200 && $files !== [] && $now === $was && ( $answer[1]['differs'] ?? [ 'x' ] ) === [];
}

check( 'the files of every kind of document are made again byte for byte: '. implode( ', ', array_keys( $remadeKinds ) ), array_filter( $remadeOk ) === $remadeOk && count( $remadeOk ) === 6 );

// The state of the offer has changed since the contract was finalised: the PDF of the contract is the same
$contractPdf = \Nino\Filesystem::path( $web, (string) ( $remadeContract['pdf_path'] ?? '/none' ) );
$contractWas = (string) @file_get_contents( $contractPdf );
docAnswer( $web, 'crm-documents/state', [ 'id' => $remadeOffer['id'], 'state' => 'declined' ] );
@unlink( $contractPdf );
$contractAnswer = docPost( $web, 'crm-documents/restore-file', [ 'id' => $remadeContract['id'] ] );

check( 'a contract whose offer has been declined since is made again with the bytes it had, and a contract needs an offer to be finalised at all', $contractAnswer[0] === 200 && $contractWas !== '' && @file_get_contents( $contractPdf ) === $contractWas && is_array( $remadeContractAgain ) && ( $remadeContractAgain['pdf_path'] ?? '' ) !== ''
	&& docAnswer( $web, 'crm-documents/save', [ 'category' => 'contract', 'title' => 'x', 'template_key' => 'contract' ] ) === '400 crm_documents_offer' );

// Another file, a link or a folder lies at the name of a file that is missing: the status says so, the download is the 409, and the action answers its own 409 and writes, moves and deletes nothing - not the other file of the document either
$foreignDoc = docFinal( $web, 'invoice', $firm, [ 'date' => ( $year - 20 ). '-03-10', 'lines' => [ $crashLine ] ] );
$foreignPdf = \Nino\Filesystem::path( $web, $foreignDoc['pdf_path'] );
$foreignXml = \Nino\Filesystem::path( $web, $foreignDoc['xml_path'] );
$foreignPdfBytes = (string) file_get_contents( $foreignPdf );
$foreignXmlBytes = (string) file_get_contents( $foreignXml );
unlink( $foreignPdf );
unlink( $foreignXml );
$foreignOther = str_repeat( 'x', strlen( $foreignPdfBytes ) );
file_put_contents( $foreignPdf, $foreignOther );
$foreignInode = fileinode( $foreignPdf );
$foreignTime = filemtime( $foreignPdf );
$foreignLine = \Nino\Modules\CrmDocuments\Status::files( $web );
$foreignDownload = docGet( $web, '/.crm/pdf/'. $foreignDoc['id'] );
$foreignRestore = docPost( $web, 'crm-documents/restore-file', [ 'id' => $foreignDoc['id'] ] );
clearstatcache();

check( 'a file of other content and the size of the file at the name of a document is not told by the status, which does not read the files: it lists the file that is really missing, and no other thing', $foreignLine['state'] === 'missing' && $foreignLine['foreign'] === 0 && $foreignLine['missing'] === 1 && $foreignLine['missing_names'] === [ basename( $foreignXml ) ] );
check( 'its download is the 409', $foreignDownload[0] === 409 );
check( 'and the action answers 409 with its own code, not the 500 of a write: nothing is written, the other file of the document is not made either, and the file of somebody else is where it was, as it was',
	docCode( $foreignRestore ) === '409 crm_documents_restore_foreign' && file_get_contents( $foreignPdf ) === $foreignOther && fileinode( $foreignPdf ) === $foreignInode && filemtime( $foreignPdf ) === $foreignTime && file_exists( $foreignXml ) === false
	&& glob( dirname( $foreignPdf ). '/*.pending-*' ) === [] && glob( dirname( $foreignPdf ). '/*.orphan-*' ) === [] );
check( 'the sentence of that code is in German and in English, and says that nothing is overwritten', ( static function() use ( $source ): bool {
	$words = [];

	foreach( [ 'de_DE', 'en_US' ] as $locale )
		$words[$locale] = (array) require dirname( __DIR__ ). '/Surface/text/'. $locale. '.php';

	return isset( $words['de_DE']['error.crm_documents_restore_foreign'], $words['en_US']['error.crm_documents_restore_foreign'], $words['de_DE']['docs.detail-pdf-files-foreign'], $words['en_US']['docs.detail-pdf-files-foreign'] ) && $words['de_DE']['error.crm_documents_restore_foreign'] !== $words['en_US']['error.crm_documents_restore_foreign']
		&& str_contains( $words['en_US']['error.crm_documents_restore_foreign'], 'not overwritten' ) && str_contains( $words['de_DE']['error.crm_documents_restore_foreign'], 'nicht überschrieben' );
} )() );

// A link and a folder are in the way just the same; nothing of them is touched
unlink( $foreignPdf );
$awayLink = dirname( $foreignPdf ). '/away-target.txt';
file_put_contents( $awayLink, 'target' );
symlink( $awayLink, $foreignPdf );
$linkRestore = docPost( $web, 'crm-documents/restore-file', [ 'id' => $foreignDoc['id'] ] );
$linkLine = \Nino\Modules\CrmDocuments\Status::files( $web );
$linkKept = is_link( $foreignPdf ) === true && file_get_contents( $awayLink ) === 'target';
unlink( $foreignPdf );
unlink( $awayLink );
mkdir( $foreignPdf );
$folderRestore = docPost( $web, 'crm-documents/restore-file', [ 'id' => $foreignDoc['id'] ] );
$folderKept = is_dir( $foreignPdf ) === true && ( scandir( $foreignPdf ) ?: [] ) === [ '.', '..' ];
$folderLine = \Nino\Modules\CrmDocuments\Status::files( $web );
rmdir( $foreignPdf );
touch( $foreignPdf );
$emptyLine = \Nino\Modules\CrmDocuments\Status::files( $web );
$emptyRestore = docPost( $web, 'crm-documents/restore-file', [ 'id' => $foreignDoc['id'] ] );
$emptyKept = is_file( $foreignPdf ) === true && filesize( $foreignPdf ) === 0;
unlink( $foreignPdf );

check( 'a link and a folder at the name are told the same way and are left as they were', docCode( $linkRestore ) === '409 crm_documents_restore_foreign' && docCode( $folderRestore ) === '409 crm_documents_restore_foreign' && $linkKept === true && $folderKept === true && $linkLine['state'] === 'foreign' );
check( 'the status says foreign, names the file and has the sentence of its own for a link, a folder and a file of no bytes at the name - and says what it does not tell', $linkLine['foreign_names'] === [ basename( $foreignPdf ) ] && $folderLine['foreign'] === 1 && $emptyLine['state'] === 'foreign' && $emptyLine['foreign'] === 1 && $emptyLine['foreign_names'] === [ basename( $foreignPdf ) ]
	&& str_contains( $emptyLine['detail'], $wordOf( 'foreign' ) ) && $wordOf( 'foreign' ) !== '' && str_contains( $emptyLine['detail'], basename( $foreignPdf ) ) && docCode( $emptyRestore ) === '409 crm_documents_restore_foreign' && $emptyKept === true );

// Taken away by hand, the files are made again with the bytes they had
$foreignDone = docPost( $web, 'crm-documents/restore-file', [ 'id' => $foreignDoc['id'] ] );

check( 'and where the owner has taken the other file away the files are made again with the bytes they had', $foreignDone[0] === 200 && file_get_contents( $foreignPdf ) === $foreignPdfBytes && file_get_contents( $foreignXml ) === $foreignXmlBytes && \Nino\Modules\CrmDocuments\Status::files( $web )['foreign'] === 0 );

// A title of a document without lines and a buyer reference refuse the marks of direction, the line and paragraph separator and private use as the lines do
$marked = [];

foreach( [ "a\u{2028}b", "a\u{2029}b", "a\u{202E}b", "a\u{E000}b", "a\u{200E}b" ] as $mark ) {
	$marked[] = docAnswer( $web, 'crm-documents/save', [ 'category' => 'privacy', 'client_id' => $ada, 'title' => $mark, 'template_key' => 'privacy' ] );
	$marked[] = docAnswer( $web, 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $firm, 'template_key' => 'invoice', 'lines' => [ [ 'title' => 'Mix', 'qty' => '1', 'price' => '1,00' ] ], 'buyer_reference' => $mark ] );
}

$markDraft = docDraft( $web, 'privacy', $ada );
$markChange = [ docAnswer( $web, 'crm-documents/save', [ 'id' => $markDraft['id'], 'title' => "a\u{2028}b" ] ), docAnswer( $web, 'crm-documents/save', [ 'id' => $markDraft['id'], 'title' => "a\u{2029}b" ] ) ];
$markOk = [ docAnswer( $web, 'crm-documents/save', [ 'category' => 'privacy', 'client_id' => $ada, 'title' => "a b \u{1F600} \u{E9}", 'template_key' => 'privacy' ] ),
	docAnswer( $web, 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $firm, 'template_key' => 'invoice', 'lines' => [ [ 'title' => 'Mix', 'qty' => '1', 'price' => '1,00' ] ], 'buyer_reference' => '04011000-12345-34' ] ) ];

check( 'a title without lines and a buyer reference with U+2028, U+2029, a mark of direction or a private use character are refused with the format, in a new draft and in a change', array_filter( $marked, static fn( string $code ): bool => $code !== '400 crm_documents_format' ) === [] && count( $marked ) === 10 && $markChange === [ '400 crm_documents_format', '400 crm_documents_format' ] && Documents::get( $web, (int) $markDraft['id'] )['title'] === 'Title of privacy' );
check( 'and an emoji and a letter with an accent in the title, and an ordinary buyer reference, are text', $markOk === [ '200 -', '200 -' ] );

// The file of a finalising that has not committed yet is not touched by another process that asks for the lock: the purge is made under the lock, so the second process waits with the pending files of the first untouched
$holdYear = $year - 24;
$holdA = docDraft( $web, 'invoice', $ada, [ 'date' => $holdYear. '-03-03', 'lines' => [ $crashLine ] ] );
$holdB = docDraft( $web, 'invoice', $ada, [ 'date' => $holdYear. '-03-04', 'lines' => [ $crashLine ] ] );
$holdGo = $where. '/go-hold';
file_put_contents( $holdGo, 'go' );
$holder = proc_open( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $holdGo, (string) $holdA['id'] ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $holderPipes, null, [ 'DOCS_CRASH' => '3' ] + getenv() );

for( $waited = 0; $waited < 20000 && is_file( $holdGo. '-held' ) === false; $waited++ ) {
	clearstatcache();
	usleep( 1000 );
}

$heldFiles = $crashFiles( $holdYear, '*' );
$waiter = proc_open( [ PHP_BINARY, __FILE__, 'worker', $features, $where, $holdGo, (string) $holdB['id'] ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $waiterPipes, null, [ 'DOCS_MODE' => 'finalise-wait' ] + getenv() );

for( $waited = 0; $waited < 20000 && is_file( $holdGo. '-ready' ) === false; $waited++ ) {
	clearstatcache();
	usleep( 1000 );
}

// The second process has started: it settles, purges and asks for the lock, which it waits for without using time of the processor
docAwaitIdle( (int) proc_get_status( $waiter )['pid'] );
$heldDuring = $crashFiles( $holdYear, '*' );
file_put_contents( $holdGo. '-release', 'go' );
$holderOut = json_decode( (string) stream_get_contents( $holderPipes[1] ), true );
stream_get_contents( $holderPipes[2] );
proc_close( $holder );
$waiterOut = json_decode( (string) stream_get_contents( $waiterPipes[1] ), true );
stream_get_contents( $waiterPipes[2] );
proc_close( $waiter );

foreach( [ '-held', '-ready', '-release', '' ] as $end )
	@unlink( $holdGo. $end );

$heldA = Documents::get( $web, (int) $holdA['id'] );
$heldB = Documents::get( $web, (int) $holdB['id'] );

check( 'a finalising that has written its files and not committed holds the lock: the files lie under pending names, and a second process that asks for the lock and purges leaves them where they are, waits, and both are finalised with their files',
	count( array_filter( $heldFiles, static fn( string $name ): bool => str_contains( $name, '.pdf.pending-' ) ) ) === 1 && $heldDuring === $heldFiles && is_array( $holderOut ) && $holderOut === [ 'R-'. $holdYear. '-0001' ] && $waiterOut === '200 -'
	&& $heldA['number'] === 'R-'. $holdYear. '-0001' && $heldB['number'] === 'R-'. $holdYear. '-0002' && $crashFiles( $holdYear, '*' ) === [ 'R-'. $holdYear. '-0001.pdf', 'R-'. $holdYear. '-0002.pdf' ]
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, $heldA['pdf_path'] ) ) === $heldA['pdf_sha256'] );

// A file that has the name of a document and that no row names is the file of a document whose row is gone - an older backup, restored - and no crash leaves one: it is never set aside, and the number is not given
$restoreYear = $year - 13;
$kept = docFinal( $web, 'invoice', $ada, [ 'date' => $restoreYear. '-03-03', 'lines' => [ $crashLine ] ] );
$lost = docFinal( $web, 'invoice', $ada, [ 'date' => $restoreYear. '-03-04', 'lines' => [ $crashLine ] ] );
$lostBytes = (string) file_get_contents( \Nino\Filesystem::path( $web, $lost['pdf_path'] ) );
$lostHash = hash_file( 'sha256', \Nino\Filesystem::path( $web, $lost['pdf_path'] ) );

// The database as the backup had it: the lost document and the counter after it are not there
Db::exec( $web, 'DELETE FROM documents WHERE id = :id', [ 'id' => $lost['id'] ] );
Db::exec( $web, 'UPDATE sequences SET next = 2 WHERE category = \'invoice\' AND year = :year', [ 'year' => $restoreYear ] );
$restored = docDraft( $web, 'invoice', $ada, [ 'date' => $restoreYear. '-03-05', 'lines' => [ $crashLine ] ] );
$refused = docPost( $web, 'crm-documents/finalise', [ 'id' => $restored['id'] ] );
$numbersAfter = array_column( Db::rows( $web, 'SELECT number FROM documents WHERE category = \'invoice\' AND number IS NOT NULL AND date LIKE :year ORDER BY number', [ 'year' => $restoreYear. '%' ] ), 'number' );
$rows = $numbersAfter;
$stray = \Nino\Modules\CrmDocuments\Status::files( $web );

check( 'after an older backup was restored the number of a document whose file is there is not given again: the finalising is the 409 exists, the file is where it was with the bytes it had, nothing was set aside, and no number is spent twice', docCode( $refused ) === '409 crm_documents_exists'
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, $lost['pdf_path'] ) ) === $lostHash && $crashFiles( $restoreYear, '*' ) === [ 'R-'. $restoreYear. '-0001.pdf', 'R-'. $restoreYear. '-0002.pdf' ] && $rows === [ 'R-'. $restoreYear. '-0001' ] && Documents::get( $web, (int) $restored['id'] )['state'] === 'draft' );
check( 'and the status says it is the file of a document without a row, with the number that is given away, and how to go on: the start number of the kind raised above the highest', $stray['state'] === 'stray' && $stray['numbers'] === [ 'R-'. $restoreYear. '-0002' ] && str_contains( $stray['detail'], 'R-'. $restoreYear. '-0002' ) && str_contains( $stray['detail'], explode( '%s', \Nino\Modules\Crm\Surface\Texts::word( $web, 'docs.detail-pdf-files-stray' ) )[2] ) );

docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '3' ] );
$past = docBody( $web, 'crm-documents/finalise', [ 'id' => $restored['id'] ] )['document'] ?? [];
docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '1' ] );

check( 'and with the start number raised over the highest the next number is the one after it, with no number given twice', ( $past['number'] ?? '' ) === 'R-'. $restoreYear. '-0003' && hash_file( 'sha256', \Nino\Filesystem::path( $web, $lost['pdf_path'] ) ) === $lostHash );

// A number is held to be one number without a difference of the case: a file system that does not tell R-2013-0001 from r-2013-0001 would put two documents on one file
$gate = Db::pdo( $web );
$gate->exec( 'BEGIN IMMEDIATE' );
$rewind = static fn() => $gate->exec( 'UPDATE sequences SET next = 1 WHERE category = \'invoice\' AND year = '. $restoreYear );
$rewind();
$sameCase = \Nino\Modules\CrmDocuments\Numbers::next( $web, $gate, 'invoice', $restoreYear, 'R-{YYYY}-{NNNN}' );
$rewind();
$otherCase = \Nino\Modules\CrmDocuments\Numbers::next( $web, $gate, 'invoice', $restoreYear, 'r-{YYYY}-{NNNN}' );
$rewind();
$otherNumber = \Nino\Modules\CrmDocuments\Numbers::next( $web, $gate, 'invoice', $restoreYear, 'x-{YYYY}-{NNNN}' );
$gate->exec( 'ROLLBACK' );

check( 'a number that another document has is not given again, whatever its case: R-2013-0001 refuses r-2013-0001 as it refuses itself, and another number is given', $sameCase === null && $otherCase === null && $otherNumber === 'x-'. $restoreYear. '-0001' );

// Formats that meet only at another counter, or in a name that is fixed, are no business of the check of the settings and stay as they were: the number that is given already is refused at the finalising, and is never given twice
$formatsNow = [ \Nino\Modules\Crm\Settings::get( $web, 'number_offer' ), \Nino\Modules\Crm\Settings::get( $web, 'number_invoice' ), \Nino\Modules\Crm\Settings::get( $web, 'number_credit' ) ];
$meetYear = $year - 21;
docAnswer( $web, 'crm/settings-save', [ 'number_credit' => 'R-1{NNN}', 'number_invoice' => 'R-{NNNN}' ] );
$meetInvoice = docFinal( $web, 'invoice', $ada, [ 'date' => $meetYear. '-03-03', 'lines' => [ $crashLine ] ] );
$meetCredit = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $meetInvoice['id'] ] )['document'];
docAnswer( $web, 'crm-documents/save', [ 'id' => $meetCredit['id'], 'date' => $meetYear. '-03-03', 'template_key' => 'credit-note' ] );
$meetCreditDone = docBody( $web, 'crm-documents/finalise', [ 'id' => $meetCredit['id'] ] )['document'] ?? [];
docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '1001' ] );
$meetNext = docDraft( $web, 'invoice', $ada, [ 'date' => $meetYear. '-03-04', 'lines' => [ $crashLine ] ] );
$meetAnswer = docAnswer( $web, 'crm-documents/finalise', [ 'id' => $meetNext['id'] ] );
$meetRows = Db::row( $web, 'SELECT COUNT(*) AS n FROM documents WHERE lower( number ) = \'r-1001\'' )['n'];

check( 'formats that meet at another counter (R-1{NNN} and R-{NNNN}) are saved, and the number that is given already is refused when the second kind comes to it: the 409 for the number, and it is not given twice', ( $meetInvoice['number'] ?? '' ) === 'R-0001' && ( $meetCreditDone['number'] ?? '' ) === 'R-1001' && $meetAnswer === '409 crm_documents_number' && $meetRows === 1
	&& Documents::get( $web, (int) $meetNext['id'] )['state'] === 'draft' );

docAnswer( $web, 'crm/settings-save', [ 'number_credit' => $formatsNow[2], 'number_invoice' => $formatsNow[1], 'start_invoice' => '1' ] );
$fixedName = docFinal( $web, 'privacy', $ada, [] );
docAnswer( $web, 'crm/settings-save', [ 'number_offer' => 'privacy-{N}', 'start_offer' => (string) $fixedName['id'] ] );
$namedOffer = docDraft( $web, 'offer', $ada, [ 'lines' => [ $crashLine ] ] );
$namedAnswer = docAnswer( $web, 'crm-documents/finalise', [ 'id' => $namedOffer['id'] ] );

check( 'a format for the offers that meets the name of a document without lines (privacy-{N}, whose id is fixed) is saved, and the offer that would be given that name is refused: the 409 for the file, and the file of the document is as it was', $namedAnswer === '409 crm_documents_exists' && Documents::get( $web, (int) $namedOffer['id'] )['state'] === 'draft'
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, $fixedName['pdf_path'] ) ) === $fixedName['pdf_sha256'] );

docAnswer( $web, 'crm/settings-save', [ 'number_offer' => $formatsNow[0], 'start_offer' => '1' ] );

unset( $lostBytes );

// --- Start numbers ---------------------------------------------------------------------------------------------------------------------------------

$ancient = $year - 6;
$started = static fn( string $start, int $yearOf ): string => (string) ( docBody( $web, 'crm-documents/finalise', [ 'id' => docDraft( $web, 'invoice', $ada, [ 'date' => $yearOf. '-05-05' ] )['id'] ] )['document']['number'] ?? '-' );

check( 'a start number raises the counter of a year that has none: the first document of the year gets it', docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '500' ] ) === '200 -' && $started( '500', $ancient ) === 'R-'. $ancient. '-0500' );
check( 'a start number that lies below what was given changes nothing, and one that is raised moves the counting forward: no number is given twice', docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '10' ] ) === '200 -' && $started( '10', $ancient ) === 'R-'. $ancient. '-0501'
	&& docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '600' ] ) === '200 -' && $started( '600', $ancient ) === 'R-'. $ancient. '-0600' && docAnswer( $web, 'crm/settings-save', [ 'start_invoice' => '1' ] ) === '200 -' && $started( '1', $ancient ) === 'R-'. $ancient. '-0601' );
check( 'the number the settings show as the next is the number the next document gets', ( docBody( $web, 'crm/settings-get' )['packages']->{'crm-documents'}['next']['invoice'] ?? '' ) === 'R-'. $year. '-'. sprintf( '%04d', Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year ] )['next'] ) );

// --- States -------------------------------------------------------------------------------------------------------------------

$step = static fn( int $id, string $state, ?string $date = null ): string => docAnswer( $web, 'crm-documents/state', [ 'id' => $id, 'state' => $state ] + ( $date === null ? [] : [ 'date' => $date ] ) );
$state = static fn( int $id ): string => (string) Documents::get( $web, $id )['state'];
$today = date( 'Y-m-d' );
$inv = (int) $f2['id'];

check( 'an invoice that is open is paid only after it was sent, and a state that is none is the 409', $step( $inv, 'paid' ) === '409 crm_documents_state' && $step( $inv, 'accepted' ) === '409 crm_documents_state' && $step( $inv, 'sent', 'x' ) === '400 crm_documents_date'
	&& $step( $inv, 'sent', '2026-02-30' ) === '400 crm_documents_date' && $step( $inv, 'cancelled' ) === '409 crm_documents_state' && $step( $inv, 'draft' ) === '409 crm_documents_state' && $step( $inv, "sent'" ) === '409 crm_documents_state' );
check( 'the day of sending is no day before the document and none to come', $step( $inv, 'sent', date( 'Y-m-d', time() - 400 * 86400 ) ) === '400 crm_documents_date_range' && $step( $inv, 'sent', Documents::addDays( $today, 1 ) ) === '400 crm_documents_date_range' && $state( $inv ) === 'open' );
check( 'the step to sent keeps the day, the step to paid cannot be before it', $step( $inv, 'sent', $today ) === '200 -' && Documents::get( $web, $inv )['sent_at'] === $today. 'T00:00:00Z' && $step( $inv, 'sent' ) === '409 crm_documents_state'
	&& $step( $inv, 'paid', date( 'Y-m-d', time() - 1 * 86400 ) ) === '400 crm_documents_date_range' && $step( $inv, 'paid', $today ) === '200 -' && $state( $inv ) === 'paid' && $step( $inv, 'paid' ) === '409 crm_documents_state' );

$sent = Documents::get( $web, $inv );

check( 'a document that is paid has no step left and is no draft any more', $step( $inv, 'sent' ) === '409 crm_documents_state' && $sent['paid_at'] === $today. 'T00:00:00Z' && $sent['number'] === $f2['number'] );

// --- Offers, and the invoice that comes of one -------------------------------------------------------------------------------

$o1 = docFinal( $web, 'offer', $ada );

check( 'an offer is numbered in its own sequence', str_starts_with( $o1['number'] ?? '', 'A-'. $year. '-' ) && ( $o1['number'] ?? '' ) === 'A-'. $year. '-0001' && $o1['valid_until'] !== null && $o1['due_date'] === null );
check( 'an offer is accepted or declined, once', $step( (int) $o1['id'], 'declined' ) === '200 -' && $step( (int) $o1['id'], 'accepted' ) === '409 crm_documents_state' && docAnswer( $web, 'crm-documents/from-offer', [ 'id' => $o1['id'] ] ) === '409 crm_documents_state' );

$o2 = docFinal( $web, 'offer', $ada );
$made = docBody( $web, 'crm-documents/from-offer', [ 'id' => $o2['id'] ] );

check( 'the invoice of an offer is a draft of the same client and lines, and the offer is accepted by it', ( $made['document']['category'] ?? '' ) === 'invoice' && ( $made['document']['state'] ?? '' ) === 'draft' && ( $made['document']['parent_id'] ?? 0 ) === $o2['id'] && ( $made['document']['gross'] ?? 0 ) === $o2['gross']
	&& $state( (int) $o2['id'] ) === 'accepted' && array_column( $made['document']['lines'], 'title' ) === array_column( $o2['lines'], 'title' ) );
check( 'an invoice that is not an offer, and a draft, give no invoice, and an offer that was accepted gives another', docAnswer( $web, 'crm-documents/from-offer', [ 'id' => $inv ] ) === '409 crm_documents_state' && docAnswer( $web, 'crm-documents/from-offer', [ 'id' => $d5['id'] ] ) === '409 crm_documents_state' && docAnswer( $web, 'crm-documents/from-offer', [ 'id' => $o2['id'] ] ) === '200 -' );
check( 'a copy is a draft of the same kind with the lines, and the copy of a credit note is none', ( docBody( $web, 'crm-documents/copy', [ 'id' => $o2['id'] ] )['document']['state'] ?? '' ) === 'draft' && docAnswer( $web, 'crm-documents/copy', [ 'id' => 99999 ] ) === '404 crm_documents_unknown' );

// --- A credit note: the tax is rounded on what the invoice has given back --------------------------------------------------------

check( 'the VAT mode is switched to the regular taxation', docAnswer( $web, 'crm/settings-save', [ 'vat_mode' => 'regular' ] ) === '200 -' );

// --- Credit notes of one second whose numbers are G-9 and G-10 --------------------------------------------------------------

$creditFormat = \Nino\Modules\Crm\Settings::get( $web, 'number_credit' );
$creditStart = \Nino\Modules\Crm\Settings::get( $web, 'start_credit' );
$creditDay = ( $year - 7 ). '-04-04';
$rate = max( \Nino\Modules\CrmDocuments\Rules::rates( $web ) );
$threeCents = [ [ 'title' => 'A', 'qty' => '1', 'price' => '0,03', 'vat_rate' => $rate ], [ 'title' => 'B', 'qty' => '1', 'price' => '0,03', 'vat_rate' => $rate ] ];
$chargedTwice = docFinal( $web, 'invoice', $ada, [ 'date' => $creditDay, 'lines' => $threeCents ] );
docAnswer( $web, 'crm/settings-save', [ 'number_credit' => 'G-{N}', 'start_credit' => '9' ] );
$pair = [];

foreach( [ 'A', 'B' ] as $title ) {
	$one = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $chargedTwice['id'] ] )['document'];
	docAnswer( $web, 'crm-documents/save', [ 'id' => $one['id'], 'date' => $creditDay, 'template_key' => 'credit-note', 'lines' => [ [ 'title' => $title, 'qty' => '-1', 'price' => '0,03', 'vat_rate' => $rate ] ] ] );
	$pair[] = (int) $one['id'];
}

$creditA = docBody( $web, 'crm-documents/finalise', [ 'id' => $pair[0] ] )['document'] ?? [];
$creditB = docBody( $web, 'crm-documents/finalise', [ 'id' => $pair[1] ] )['document'] ?? [];

// Both were finalised in one second: the order of the numbers is what puts the one before the other
Db::exec( $web, 'UPDATE documents SET finalised = ( SELECT finalised FROM documents WHERE id = :first ) WHERE id = :second', [ 'first' => $pair[0], 'second' => $pair[1] ] );
$creditA = (array) Documents::get( $web, $pair[0] );
$creditB = (array) Documents::get( $web, $pair[1] );
$shown = array_map( static fn( array $one ): array => [ $one['vat'], array_sum( array_column( (array) Documents::get( $web, (int) $one['id'] )['rates'], 'vat' ) ) ], [ $creditA, $creditB ] );

check( 'two credit notes of one second with the numbers G-9 and G-10 show the tax of each rate as it was given: the sums of the rates are what is stored, and together they are the tax of the invoice', ( $creditA['number'] ?? '' ) === 'G-9' && ( $creditB['number'] ?? '' ) === 'G-10' && ( $creditA['finalised'] ?? 'a' ) === ( $creditB['finalised'] ?? 'b' )
	&& $shown[0][0] === $shown[0][1] && $shown[1][0] === $shown[1][1] && $creditA['vat'] + $creditB['vat'] === -$chargedTwice['vat'] );

docAnswer( $web, 'crm/settings-save', [ 'number_credit' => $creditFormat, 'start_credit' => (string) $creditStart ] );

$tiny = docFinal( $web, 'invoice', $ada, [ 'lines' => [ [ 'title' => 'A', 'qty' => '1', 'price' => '0,03', 'vat_rate' => 1900 ], [ 'title' => 'B', 'qty' => '1', 'price' => '0,03', 'vat_rate' => 1900 ] ] ] );

check( 'the tax of a rate is taken of the net of the rate, not of each line: two lines of three cents are six cents and one cent of tax', ( $tiny['net'] ?? 0 ) === 6 && ( $tiny['vat'] ?? 0 ) === 1 && ( $tiny['gross'] ?? 0 ) === 7 && ( $tiny['rates'] ?? [] ) === [] );

$c1 = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $tiny['id'] ] )['document'];

check( 'a credit note is a draft with the lines of its invoice negated and the invoice as its parent', ( $c1['category'] ?? '' ) === 'credit' && array_column( $c1['lines'], 'qty' ) === [ -1000, -1000 ] && $c1['parent_id'] === $tiny['id'] && $c1['net'] === -6 && $c1['vat_mode'] === 'regular' && $c1['due_date'] === null && $c1['valid_until'] === null );

$partial = static fn( array $c, array $line ): array => (array) docBody( $web, 'crm-documents/save', [ 'id' => $c['id'], 'template_key' => 'credit-note', 'lines' => [ $line ] ] )['document'];
$line = [ 'title' => 'A', 'qty' => '-1', 'price' => '0,03', 'vat_rate' => 1900 ];
$partial( $c1, $line );
$cf1 = docBody( $web, 'crm-documents/finalise', [ 'id' => $c1['id'] ] )['document'];

check( 'the first credit note takes one line back: its own tax rounds away from zero', ( $cf1['net'] ?? 0 ) === -3 && ( $cf1['vat'] ?? 0 ) === -1 && ( $cf1['gross'] ?? 0 ) === -4 && str_starts_with( $cf1['number'] ?? '', 'G-'. $year. '-' ) && ( $cf1['rates'] ?? [] ) === [ [ 'rate' => 1900, 'net' => -3, 'vat' => -1 ] ] && $state( (int) $tiny['id'] ) === 'open' );

$c2 = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $tiny['id'] ] )['document'];
$partial( $c2, $line );
$cf2 = docBody( $web, 'crm-documents/finalise', [ 'id' => $c2['id'] ] )['document'];

check( 'the second takes the rest and its tax is what is left of the tax of the invoice, so that the credit notes come to the tax of the invoice and to its gross', ( $cf2['vat'] ?? 9 ) === 0 && ( $cf2['gross'] ?? 0 ) === -3 && ( $cf1['gross'] + $cf2['gross'] ) === -7 && ( $cf1['vat'] + $cf2['vat'] ) === -( $tiny['vat'] ) );
check( 'the credit notes that take every rate of the invoice back cancel it, and a cancelled invoice has no step and gives no credit note', $state( (int) $tiny['id'] ) === 'cancelled' && $step( (int) $tiny['id'], 'sent' ) === '409 crm_documents_state' && docAnswer( $web, 'crm-documents/credit', [ 'id' => $tiny['id'] ] ) === '409 crm_documents_state' );

$next2 = static fn(): ?int => ( Db::row( $web, 'SELECT next FROM sequences WHERE category = \'credit\' AND year = :year', [ 'year' => $year ] )['next'] ?? null );
$three = docFinal( $web, 'invoice', $ada, [ 'lines' => [ [ 'title' => 'Mix', 'qty' => '2', 'price' => '50,00', 'vat_rate' => 1900 ], [ 'title' => 'Book', 'qty' => '1', 'price' => '20,00', 'vat_rate' => 700 ] ] ] );

check( 'an invoice with two rates has the tax of each rate', ( $three['net'] ?? 0 ) === 12000 && ( $three['vat'] ?? 0 ) === 1900 + 140 && ( $three['rates'] ?? [] ) === [] );

$over = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $three['id'] ] )['document'];
$before = $next2();

check( 'a credit note of more than the invoice charged is refused when it is finalised, with nothing numbered', docAnswer( $web, 'crm-documents/save', [ 'id' => $over['id'], 'template_key' => 'credit-note', 'lines' => [ [ 'title' => 'Too much', 'qty' => '-3', 'price' => '50,00', 'vat_rate' => 1900 ] ] ] ) === '400 crm_documents_credit_lines'
	&& docAnswer( $web, 'crm-documents/save', [ 'id' => $over['id'], 'template_key' => 'credit-note', 'lines' => [ [ 'title' => 'Other rate', 'qty' => '-1', 'price' => '50,00', 'vat_rate' => 0 ] ] ] ) === '400 crm_documents_credit_lines'
	&& docAnswer( $web, 'crm-documents/save', [ 'id' => $over['id'], 'template_key' => 'credit-note', 'lines' => [ [ 'title' => 'A charge', 'qty' => '1', 'price' => '50,00', 'vat_rate' => 1900 ] ] ] ) === '400 crm_documents_credit_lines' && $next2() === $before );

docBody( $web, 'crm-documents/save', [ 'id' => $over['id'], 'template_key' => 'credit-note', 'lines' => [ [ 'title' => 'Mix', 'qty' => '-2', 'price' => '50,00', 'vat_rate' => 1900 ] ] ] );
$taken = docBody( $web, 'crm-documents/finalise', [ 'id' => $over['id'] ] )['document'];
$over2 = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $three['id'] ] )['document'];
docBody( $web, 'crm-documents/save', [ 'id' => $over2['id'], 'template_key' => 'credit-note', 'lines' => [ [ 'title' => 'Mix again', 'qty' => '-1', 'price' => '50,00', 'vat_rate' => 1900 ] ] ] );

check( 'what the credit notes before took counts: the sum of them is held to the invoice, and a draft that would exceed it stays a draft', ( $taken['vat'] ?? 0 ) === -1900 && docAnswer( $web, 'crm-documents/finalise', [ 'id' => $over2['id'] ] ) === '409 crm_documents_credit_over' && $state( (int) $over2['id'] ) === 'draft' && $next2() === $before + 1 );

check( 'a credit note of a credit note, of an offer and of a draft is the 409', docAnswer( $web, 'crm-documents/credit', [ 'id' => $taken['id'] ] ) === '409 crm_documents_state' && docAnswer( $web, 'crm-documents/credit', [ 'id' => $o2['id'] ] ) === '409 crm_documents_state'
	&& docAnswer( $web, 'crm-documents/credit', [ 'id' => $over2['id'] ] ) === '409 crm_documents_state' && docAnswer( $web, 'crm-documents/credit', [ 'id' => 99999 ] ) === '404 crm_documents_unknown' );
check( 'the invoice names its credit notes, and nothing of them touches what it says', array_column( Documents::get( $web, (int) $three['id'] )['children'], 'id' ) === [ $taken['id'], $over2['id'] ] && Documents::get( $web, (int) $three['id'] )['gross'] === $three['gross'] );

$credited = Documents::get( $web, (int) $taken['id'] );

check( 'a credit note is held to the VAT mode of its invoice, whatever the setting says now', docAnswer( $web, 'crm/settings-save', [ 'vat_mode' => 'small_business' ] ) === '200 -' && ( (array) docBody( $web, 'crm-documents/credit', [ 'id' => $three['id'] ] )['document'] )['vat_mode'] === 'regular' && $credited['vat_mode'] === 'regular' );

// --- Contracts, privacy documents and information: no lines, no number ---------------------------------------------------------

$contract = (array) docBody( $web, 'crm-documents/save', [ 'category' => 'contract', 'parent_id' => $o2['id'], 'title' => 'Contract on the offer', 'template_key' => 'contract' ] )['document'];

check( 'a contract hangs on a finalised offer of the client and takes its client', ( $contract['parent_id'] ?? 0 ) === $o2['id'] && ( $contract['client_id'] ?? 0 ) === $ada && ( $contract['lines'] ?? [ 1 ] ) === [] && ( $contract['number'] ?? 'x' ) === '' );
check( 'a contract without an offer, with a draft, with an offer of another client or with a client that is not its offer\'s is refused', docAnswer( $web, 'crm-documents/save', [ 'category' => 'contract', 'title' => 'x', 'template_key' => 'contract' ] ) === '400 crm_documents_offer'
	&& docAnswer( $web, 'crm-documents/save', [ 'category' => 'contract', 'parent_id' => 999, 'title' => 'x', 'template_key' => 'contract' ] ) === '400 crm_documents_offer'
	&& docAnswer( $web, 'crm-documents/save', [ 'category' => 'contract', 'parent_id' => $o2['id'], 'client_id' => $firm, 'title' => 'x', 'template_key' => 'contract' ] ) === '400 crm_documents_client' );
check( 'a document without lines has a title: it is missing, too long or with a control character', docAnswer( $web, 'crm-documents/save', [ 'category' => 'privacy', 'client_id' => $ada, 'title' => ' ', 'template_key' => 'privacy' ] ) === '400 crm_documents_title'
	&& docAnswer( $web, 'crm-documents/save', [ 'category' => 'privacy', 'client_id' => $ada, 'title' => str_repeat( 'x', 121 ), 'template_key' => 'privacy' ] ) === '400 crm_documents_long' && docAnswer( $web, 'crm-documents/save', [ 'category' => 'privacy', 'client_id' => $ada, 'title' => "a\u{202E}b", 'template_key' => 'privacy' ] ) === '400 crm_documents_format'
	&& docAnswer( $web, 'crm-documents/save', [ 'category' => 'privacy', 'client_id' => $ada, 'title' => 'T', 'lines' => $lines(), 'template_key' => 'privacy' ] ) === '400 crm_documents_format' );

$cn = (array) docBody( $web, 'crm-documents/finalise', [ 'id' => $contract['id'] ] )['document'];
$privacy = docFinal( $web, 'privacy', $bare );
$info = docFinal( $web, 'information', $ada );

check( 'a document without lines is finalised with no number and no amount, under the name of its kind and id, and a client that has no address yet may be given one', ( $cn['number'] ?? 'x' ) === '' && $cn['pdf_path'] === '/data/crm/pdf/'. $year. '/contract-'. $cn['id']. '.pdf' && ( $privacy['pdf_path'] ?? '' ) === '/data/crm/pdf/'. $year. '/privacy-'. $privacy['id']. '.pdf'
	&& $cn['gross'] === 0 && is_file( \Nino\Filesystem::path( $web, $info['pdf_path'] ) ) === true && $cn['state'] === 'open' );
check( 'the sequences were not touched by them', Db::row( $web, 'SELECT COUNT(*) AS n FROM sequences WHERE category NOT IN ( \'client\', \'offer\', \'invoice\', \'credit\' )' )['n'] === 0 );
check( 'a contract cannot be finalised once its offer is a draft again - it never is, and a draft of another client is refused as a parent', $step( (int) $cn['id'], 'sent' ) === '200 -' && $step( (int) $cn['id'], 'paid' ) === '409 crm_documents_state' );

// --- A project of the client ---------------------------------------------------------------------------------------------------

$adaNumber = (string) Documents::get( $web, $inv )['client']['number'];
$firmNumber = (string) \Nino\Modules\Crm\Clients::get( $web, $firm )['number'];
$projectBody = docBody( $web, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Album', 'client' => $adaNumber ] ] );
$pid = \Nino\Elements::getElementUriFromUri( (string) ( $projectBody['element']['.uri'] ?? '' ) );
$other = docBody( $web, 'elements/save', [ 'type' => 'crm-projects', 'uri' => '', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Single', 'client' => $firmNumber ] ] );
$oid = \Nino\Elements::getElementUriFromUri( (string) ( $other['element']['.uri'] ?? '' ) );

check( 'the projects of the suite are made', $pid !== '' && $oid !== '' );
check( 'a document names a project of its own client, of any state and without a change of what it says: a finalised document too, and its PDF and hash stay', docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => $pid ] ) === '200 -' && Documents::get( $web, $inv )['project'] === $pid
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, Documents::get( $web, $inv )['pdf_path'] ) ) === Documents::get( $web, $inv )['pdf_sha256'] && docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => '' ] ) === '200 -' && docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => $pid ] ) === '200 -' );
check( 'a project that is none, that is another client\'s or that is no id is refused with its code, and the project stays as it was', docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => '99999' ] ) === '400 crm_documents_project' && docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => $oid ] ) === '409 crm_documents_project_foreign'
	&& docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => '../x' ] ) === '400 crm_documents_format' && docAnswer( $web, 'crm-documents/project', [ 'id' => $inv, 'project' => 5 ] ) === '400 crm_documents_format' && Documents::get( $web, $inv )['project'] === $pid );

$holds = array_column( \Nino\Modules\Crm\Projects::holds( $web, $pid ), null, 'key' );

check( 'the documents that name a project hold it: it is not deleted and does not change its client, with the codes of the package', ( $holds['crm-documents']['count'] ?? 0 ) === 1 && ( $holds['crm-documents']['delete'] ?? false ) === true && ( $holds['crm-documents']['client'] ?? false ) === true
	&& ( $holds['crm-documents']['delete_code'] ?? '' ) === 'crm_documents_project_named' && ( $holds['crm-documents']['client_code'] ?? '' ) === 'crm_documents_project_client' );

$deleted = docPost( $web, 'elements/delete', [ 'type' => 'crm-projects', 'uri' => '/crm-projects/'. $pid, 'locale' => '*' ] );
ninoWarnings();

check( 'a project that a document names is not deleted - by the endpoint of the surface or by the workbench', $deleted[0] !== 200 && \Nino\Modules\Crm\Projects::get( $web, $pid ) !== null );
check( 'a project that a document of another client names is not the client\'s to take along: the deletion of that client names it as shared', \Nino\Modules\CrmDocuments\Hooks::sharedProjects( $web, $firm, [ $pid ] ) === [ $pid ] && \Nino\Modules\CrmDocuments\Hooks::sharedProjects( $web, $ada, [ $pid ] ) === [] && \Nino\Modules\CrmDocuments\Hooks::sharedProjects( $web, $ada, [] ) === [] );

$figures = [ 'projects' => [ $pid, $oid ], 'figures' => [] ];
\Nino\Modules\CrmDocuments\Hooks::callbackProjectFigures( $web, $figures );

check( 'the row of a project has its number of documents and of open offers; a project without documents has zeros', $figures['figures'][$pid] === [ 'documents' => 1, 'open' => 0 ] && $figures['figures'][$oid] === [ 'documents' => 0, 'open' => 0 ] );

$derived = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $inv ] )['document'];

check( 'what is made of a document keeps its project where it is still the client\'s', ( Documents::get( $web, (int) $derived['id'] )['project'] ?? 'x' ) === $pid );

// --- Erasure of a client: nothing is deleted behind the books --------------------------------------------------------------------

$del = docClient( $web, [ 'name' => 'Deleted Soon' ] );
$delDraft = docDraft( $web, 'invoice', $del );
$delNumber = (string) \Nino\Modules\Crm\Clients::get( $web, $del )['number'];
$preview = docBody( $web, 'crm-clients/delete-preview', [ 'id' => $del ] );

check( 'the preview of the deletion counts the drafts that go with the client and refuses nothing', ( $preview['preview']['counts']['drafts'] ?? null ) === 1 && ( $preview['preview']['counts']['fixed'] ?? null ) === 0 && ( $preview['preview']['deletable'] ?? false ) === true && ( $preview['preview']['reasons'] ?? [ 1 ] ) === [] );

$delDone = docBody( $web, 'crm-clients/delete', [ 'id' => $del, 'confirm' => $delNumber ] );

check( 'a client with drafts only is deleted with them, and with their lines', ( $delDone['number'] ?? '' ) === $delNumber && Db::row( $web, 'SELECT COUNT(*) AS n FROM documents WHERE client_id = :id', [ 'id' => $del ] )['n'] === 0 && Db::row( $web, 'SELECT COUNT(*) AS n FROM document_lines WHERE document_id = :id', [ 'id' => (int) $delDraft['id'] ] )['n'] === 0 );

$keep = docClient( $web, [ 'name' => 'Kept Client', 'street' => 'Weg 2', 'zip' => '12345', 'city' => 'Berlin', 'country' => 'DE' ] );
$keepFinal = docFinal( $web, 'invoice', $keep );
$keepDraft = docDraft( $web, 'invoice', $keep );
$keepNumber = (string) \Nino\Modules\Crm\Clients::get( $web, $keep )['number'];
$keepRows = [ docRow( $web, (int) $keepFinal['id'] ), docRow( $web, (int) $keepDraft['id'] ) ];
$deletable = docBody( $web, 'crm-clients/delete-preview', [ 'id' => $keep ] );

check( 'the preview names the refusal of the fixed document and says the client is not deletable', ( $deletable['preview']['counts']['fixed'] ?? null ) === 1 && ( $deletable['preview']['deletable'] ?? true ) === false && ( $deletable['preview']['reasons'][0] ?? '' ) === 'fixed' && ( $deletable['preview']['counts']['drafts'] ?? null ) === 1 );
check( 'a client with a finalised document is not deleted: 409 with the code of the package, whatever is typed, and nothing is deleted - not the document, not the draft, not the file', docAnswer( $web, 'crm-clients/delete', [ 'id' => $keep, 'confirm' => $keepNumber ] ) === '409 crm_documents_client_fixed'
	&& docRow( $web, (int) $keepFinal['id'] ) === $keepRows[0] && docRow( $web, (int) $keepDraft['id'] ) === $keepRows[1] && is_file( \Nino\Filesystem::path( $web, $keepFinal['pdf_path'] ) ) === true && \Nino\Modules\Crm\Clients::get( $web, $keep ) !== null );
check( 'and the same press again says the same', docAnswer( $web, 'crm-clients/delete', [ 'id' => $keep, 'confirm' => $keepNumber ] ) === '409 crm_documents_client_fixed' && docAnswer( $web, 'crm-clients/delete', [ 'id' => $keep, 'confirm' => 'wrong' ] ) !== '200 -' );
check( 'a client that is switched off still has its documents', docAnswer( $web, 'crm-clients/active', [ 'id' => $keep, 'active' => false ] ) === '200 -' && Documents::get( $web, (int) $keepFinal['id'] ) !== null && docAnswer( $web, 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $keep, 'template_key' => 'invoice', 'lines' => $lines() ] ) === '409 crm_documents_inactive' );

// The constraint of the table is the last word: a delete of the client behind the back of every step is refused
$restricted = '';

try {
	Db::exec( $web, 'DELETE FROM clients WHERE id = :id', [ 'id' => $keep ] );
}
catch( \PDOException $e ) {
	$restricted = 'restricted';
}

check( 'a client whose document is fixed cannot be deleted from under it: the foreign key restricts', $restricted === 'restricted' );

// --- The route of the files ---------------------------------------------------------------------------------------------------

$visitor = docRequest( $where );
docSignIn( $visitor, '' );
$plain = docRequest( $where );
docSignIn( $plain, 'plain@example.org' );
$crmOnly = docRequest( $where );
docSignIn( $crmOnly, 'crm@example.org' );

$pdfUri = '/.crm/pdf/'. $inv;
[ $status, $response ] = docGet( $web, $pdfUri );

check( 'the PDF of a finalised document is answered to the owner: the file, as a PDF, shown, not kept by a cache, not sniffed', $status === 200 && ( $response['file'] ?? '' ) === \Nino\Filesystem::path( $web, Documents::get( $web, $inv )['pdf_path'] ) && $response['header']['Content-Type'] === 'application/pdf'
	&& str_starts_with( $response['header']['Content-Disposition'], 'inline;' ) && $response['header']['Cache-Control'] === 'private, no-store' && $response['header']['X-Content-Type-Options'] === 'nosniff' );
check( 'the same with the permission of the CRM alone, and as a download where it is asked for', docGet( $crmOnly, $pdfUri )[0] === 200 && str_starts_with( docGet( $web, $pdfUri. '?download=1' )[1]['header']['Content-Disposition'], 'attachment;' ) );

$denied = [ docGet( $visitor, $pdfUri )[0], docGet( $plain, $pdfUri )[0] ];

check( 'nobody and an account without the permission get the 404, as for a document that is not there', $denied === [ 404, 404 ] && docGet( $web, '/.crm/pdf/99999' )[0] === 404 && docGet( $web, '/.crm/pdf/'. $d5['id'] )[0] === 404 );
check( 'an id that is no number is the 404 and reaches no file: a path, a null byte, a leading zero, a huge number, nothing', array_filter( [ '0', '01', 'x', '1/../2', '1%00', '99999999999', '', '-1', '1.pdf', '%2e%2e', '1e0' ], static fn( string $id ): bool => docGet( $web, '/.crm/pdf/'. $id )[0] !== 404 ) === [] );
check( 'the files lie below a folder the server does not serve: no address of them is a file, to the owner as well', array_filter( [ '/data/crm/pdf/'. $year. '/'. $prefix. '0002.pdf', '/private/data/crm/pdf/'. $year. '/'. $prefix. '0002.pdf', '/crm/pdf/'. $year. '/'. $prefix. '0002.pdf', '/.crm/pdf/'. $year. '/'. $prefix. '0002.pdf' ], static fn( string $uri ): bool => docGet( $web, $uri )[0] !== 404 || docGet( $visitor, $uri )[0] !== 404 ) === [] );

$stored = (string) file_get_contents( $file );
$real = \Nino\Filesystem::path( $web, Documents::get( $web, $inv )['pdf_path'] );
$bytes = (string) file_get_contents( $real );
file_put_contents( $real, $bytes. ' ' );
$tampered = docGet( $web, $pdfUri );
ninoWarnings();
file_put_contents( $real, $bytes );
rename( $real, $real. '.away' );
$gone = docGet( $web, $pdfUri );
ninoWarnings();
rename( $real. '.away', $real );

check( 'a file whose hash is not the stored one is the 409, never sent; a file that is gone is the 409 that says the file is missing, and no 404; and the file is back as it was', $tampered[0] === 409 && ( $tampered[1]['file'] ?? null ) === null && $gone[0] === 409 && ( $gone[1]['body'] ?? '' ) === \Nino\Modules\Crm\Surface\Texts::word( $web, 'error.crm_documents_file_missing' ) && docGet( $web, $pdfUri )[0] === 200 );

Db::exec( $web, 'UPDATE documents SET pdf_path = :path WHERE id = :id', [ 'path' => '/data/crm/pdf/'. $year. '/../../../config.php', 'id' => $inv ] );
$escaped = docGet( $web, $pdfUri );
ninoWarnings();
Db::exec( $web, 'UPDATE documents SET pdf_path = :path WHERE id = :id', [ 'path' => '/data/crm/pdf/'. $year. '/'. $prefix. '0002.pdf', 'id' => $inv ] );

check( 'a path in the database that is none of the shape the package writes is no file: the 409 that says it is missing, and nothing outside the folder is read', $escaped[0] === 409 && ( $escaped[1]['file'] ?? null ) === null );

// The preview of a draft
$pre = (array) docDraft( $web, 'invoice', $ada );
$sequenceBefore = Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year ] )['next'];
$filesBefore = glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*' );
[ $status, $response ] = docGet( $web, '/crm/preview/'. $pre['id'] );

check( 'the preview of a draft is a PDF that is made now: no number is taken, no file is written', $status === 200 && str_starts_with( (string) $response['body'], '%PDF-' ) && $response['header']['Content-Type'] === 'application/pdf' && Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year ] )['next'] === $sequenceBefore
	&& glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*' ) === $filesBefore && Documents::get( $web, (int) $pre['id'] )['state'] === 'draft' );
check( 'the preview of a finalised document is the 409, of nothing the 404, to nobody the 401 and without the permission the 403', docGet( $web, '/crm/preview/'. $inv )[0] === 409 && docGet( $web, '/crm/preview/99999' )[0] === 404 && docGet( $visitor, '/crm/preview/'. $pre['id'] )[0] === 401 && docGet( $plain, '/crm/preview/'. $pre['id'] )[0] === 403
	&& docGet( $web, '/crm/preview/x' )[0] === 404 && docGet( $web, '/crm/preview/0' )[0] === 404 );

$noTemplate = docDraft( $web, 'invoice', $ada, [ 'template_key' => '' ] );

check( 'a draft without a template has no preview, and says it', docGet( $web, '/crm/preview/'. $noTemplate['id'] )[0] === 409 );

// --- The electronic invoice -----------------------------------------------------------------------------------------------------

$unit = [ 'title' => 'Mix', 'qty' => '1', 'price' => '100,00', 'unit_code' => 'HUR', 'unit' => 'h' ];
$lacking = docFinal( $web, 'invoice', $firm, [ 'lines' => [ [ 'title' => 'No unit', 'qty' => '1', 'price' => '10,00' ] ] ] );

check( 'a document of a business client that lacks something is finalised without a file and keeps the list of what was missing', ( $lacking['xml_path'] ?? 'x' ) === '' && in_array( 'line_unit', $lacking['xml_lacks'] ?? [], true ) === true && ( $lacking['erechnung']['xml'] ?? true ) === false && count( $lacking['erechnung']['lacks'] ?? [] ) >= 1
	&& is_string( $lacking['erechnung']['lacks'][0]['text'] ?? null ) === true && str_starts_with( $lacking['erechnung']['lacks'][0]['text'], 'lack.' ) === false );

$e = docFinal( $web, 'invoice', $firm, [ 'lines' => [ $unit ] ] );

check( 'an invoice of a business client that lacks nothing has an electronic invoice beside its PDF, with its hash', ( $e['xml_path'] ?? '' ) === '/data/crm/pdf/'. $year. '/'. ( $e['number'] ?? 'x' ). '.xml' && ( $e['erechnung']['xml'] ?? false ) === true && ( $e['xml_lacks'] ?? [ 1 ] ) === []
	&& hash_file( 'sha256', \Nino\Filesystem::path( $web, $e['xml_path'] ?? '/none' ) ) === $e['xml_sha256'] && $e['xml_url'] === '/.crm/xml/'. $e['id'] );

$xml = new \DOMDocument();
$loaded = $xml->load( \Nino\Filesystem::path( $web, $e['xml_path'] ) );
$path = new \DOMXPath( $xml );
$path->registerNamespace( 'rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100' );
$path->registerNamespace( 'ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100' );
$value = static fn( string $query ): string => (string) $path->evaluate( 'string( '. $query. ' )' );

check( 'the file is the invoice: well formed, its number, its type 380, the seller and the buyer, the total in euros with two decimals', $loaded === true && $value( '//rsm:ExchangedDocument/ram:ID' ) === $e['number'] && $value( '//rsm:ExchangedDocument/ram:TypeCode' ) === '380'
	&& $value( '//ram:SellerTradeParty/ram:Name' ) === 'Studio Example' && $value( '//ram:BuyerTradeParty/ram:Name' ) === 'Firm GmbH' && $value( '//ram:GrandTotalAmount' ) === '100.00' && $value( '//ram:BuyerReference' ) === '991-12345-67' && $value( '//ram:DuePayableAmount' ) === '100.00' );
check( 'under small business the file carries the exemption: the word of the PDF where the setting has none', str_contains( $xml->saveXML() ?: '', 'Gemäß § 19 UStG' ) === true );

check( 'the route answers the file to the owner, as an attachment, checked against its hash, and to nobody else', docGet( $web, '/.crm/xml/'. $e['id'] )[0] === 200 && docGet( $web, '/.crm/xml/'. $e['id'] )[1]['header']['Content-Type'] === 'application/xml' && str_starts_with( docGet( $web, '/.crm/xml/'. $e['id'] )[1]['header']['Content-Disposition'], 'attachment;' )
	&& docGet( $visitor, '/.crm/xml/'. $e['id'] )[0] === 404 && docGet( $plain, '/.crm/xml/'. $e['id'] )[0] === 404 && docGet( $web, '/.crm/xml/'. $lacking['id'] )[0] === 404 && docGet( $web, '/.crm/xml/x' )[0] === 404 );

$xmlFile = \Nino\Filesystem::path( $web, $e['xml_path'] );
$xmlBytes = (string) file_get_contents( $xmlFile );
file_put_contents( $xmlFile, $xmlBytes. ' ' );
$xmlTampered = docGet( $web, '/.crm/xml/'. $e['id'] )[0];
ninoWarnings();
file_put_contents( $xmlFile, $xmlBytes );

check( 'an electronic invoice whose hash is not the stored one is the 409', $xmlTampered === 409 && docGet( $web, '/.crm/xml/'. $e['id'] )[0] === 200 );

check( 'an invoice of a private client and an offer of a business client have no file and no list', ( $inv > 0 && ( Documents::get( $web, $inv )['xml_path'] ?? 'x' ) === '' ) && Documents::get( $web, $inv )['xml_lacks'] === [] && ( docFinal( $web, 'offer', $firm )['xml_path'] ?? 'x' ) === '' );

// A file of an electronic invoice that is in the way: nothing is finalised and the PDF that was written for it is taken back
$nx = docDraft( $web, 'invoice', $firm, [ 'lines' => [ $unit ] ] );
$nextNumber = $prefix. sprintf( '%04d', Db::row( $web, 'SELECT next FROM sequences WHERE category = \'invoice\' AND year = :year', [ 'year' => $year ] )['next'] );
$inTheWay = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year. '/'. $nextNumber. '.xml' );
file_put_contents( $inTheWay, 'in the way' );
$blocked = docPost( $web, 'crm-documents/finalise', [ 'id' => $nx['id'] ] );

check( 'a file in the way of the electronic invoice refuses the finalising with its own code: the PDF written for it is taken back (it lay under a pending name), the counter goes back, the file in the way stays', docCode( $blocked ) === '409 crm_documents_exists_xml' && is_file( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year. '/'. $nextNumber. '.pdf' ) ) === false
	&& file_get_contents( $inTheWay ) === 'in the way' && glob( \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year ). '/*.pending-*' ) === [] && Documents::get( $web, (int) $nx['id'] )['state'] === 'draft' );

unlink( $inTheWay );
$nxFinal = docBody( $web, 'crm-documents/finalise', [ 'id' => $nx['id'] ] )['document'] ?? [];

check( 'and the number that was not spent is the next', ( $nxFinal['number'] ?? '' ) === $nextNumber && ( $nxFinal['xml_path'] ?? '' ) !== '' );

// The window of the address is the class of the sheet of Pdf, which the markup of the package no longer spells itself: the fragment is made with it
$addressed = \Nino\Modules\CrmDocuments\Render::address( $web, [ 'company' => 'Firm GmbH', 'name' => 'Grace Hopper', 'street' => 'Road 1', 'zip' => '10115', 'city' => 'Berlin', 'country' => 'DE' ] );

check( 'the address of a PDF is a paragraph of the class address, which the sheet of Pdf reads as the window of the address, and a line break of the description is the fragment of the line break', str_starts_with( $addressed, '<p class="address">' ) && str_ends_with( $addressed, '</p>' ) && str_contains( $addressed, 'Firm GmbH' )
	&& str_contains( \Nino\Modules\CrmDocuments\Render::lines( $web, [ [ 'title' => 'Mix', 'description' => "One\nTwo", 'unit' => '', 'unit_code' => '', 'qty' => 1000, 'price_net' => 100, 'amount_net' => 100, 'vat_rate' => 1900 ] ], [ 'amounts' => [ 100 ] ], false, 'EUR' ), 'One<br>Two' ) );

// A control character and what XML 1.0 cannot carry are refused in a line, and nothing else: the break of a line and a tab are the description's, and the marks that show nothing, the marks of direction, joined emoji and private use are text
$saveLine = static fn( array $line ): string => docAnswer( $web, 'crm-documents/save', [ 'category' => 'invoice', 'client_id' => $firm, 'template_key' => 'invoice', 'lines' => [ [ 'qty' => '1', 'price' => '1,00' ] + $line ] ] );
$controls = array_map( $saveLine, [
	[ 'title' => "Mix\x01" ], [ 'title' => "Mi\x0Bx" ], [ 'title' => "Mix\u{FFFE}" ], [ 'title' => "Mix\u{FFFF}" ], [ 'title' => "Mi\u{85}x" ], [ 'title' => "Mi\x7Fx" ], [ 'title' => "Mi\tx" ], [ 'title' => "Mi\nx" ], [ 'title' => 'Mix', 'unit' => "h\x07" ],
	[ 'title' => 'Mix', 'unit' => "h\n" . 'x' ], [ 'title' => 'Mix', 'description' => "Ten\x01 tracks" ], [ 'title' => 'Mix', 'description' => "Ten\u{85} tracks" ], [ 'title' => 'Mix', 'description' => "Ten\u{FFFE} tracks" ], [ 'title' => 'Mix', 'description' => "Ten\x0B tracks" ],
	[ 'title' => "Mi\u{202E}x" ], [ 'title' => "Mi\u{2066}x" ], [ 'title' => "Mi\u{200E}x" ], [ 'title' => "Mi\u{200F}x" ], [ 'title' => "Mi\u{E000}x" ], [ 'title' => "Mi\u{2028}x" ], [ 'title' => 'Mix', 'unit' => "h\u{202A}" ], [ 'title' => 'Mix', 'description' => "Ten\u{2029}tracks" ], [ 'title' => 'Mix', 'description' => "Ten\u{202D}tracks" ], [ 'title' => 'Mix', 'description' => "Ten\u{200E}tracks" ], [ 'title' => 'Mix', 'description' => "Ten\u{E000}tracks" ], [ 'title' => "Mi\u{E0001}x" ], [ 'title' => "Mi\u{FDD0}x" ], [ 'title' => "Mi\u{1FFFE}x" ] ] );
$texts = array_map( $saveLine, [
	[ 'title' => "Mi\u{AD}x" ], [ 'title' => "Mix \u{1F468}\u{200D}\u{1F469}\u{200D}\u{1F467} \u{1F1E9}\u{1F1EA} 1\u{FE0F}\u{20E3} \u{1F600}" ], [ 'title' => "M\u{200C}i\u{200D}x\u{FEFF}\u{FE0F}" ], [ 'title' => 'Mix', 'unit' => "h\u{AD}" ],
	[ 'title' => 'Mix', 'description' => "Ten tracks\n\tStereo\r\nMono \u{1F600}\u{200D}" ] ] );

check( 'a control character - a tab and a break in the title and the unit too - and a character XML 1.0 cannot carry in the title, the description or the unit of a line is refused with the format', array_filter( $controls, static fn( string $code ): bool => $code !== '400 crm_documents_line_format' ) === [] );
check( 'and nothing else is: a soft hyphen, a joined emoji, a flag, a keycap, a joiner and the byte order mark are text, and a break and a tab are the description\'s', array_filter( $texts, static fn( string $code ): bool => $code !== '200 -' ) === [] );

$xmlDocument = (array) Documents::get( $web, (int) $nxFinal['id'] );
$xmlDocument['client']['company'] = "Firm\x01 \x0B GmbH";
$xmlDocument['lines'][0]['title'] = "Mix\x02 \u{1F468}\u{200D}\u{1F469} \u{AD}";
$xmlDocument['inputs']['einleitung'] = "Thanks\x08 for \u{FFFE}your order";
$xmlText = \Nino\Modules\CrmDocuments\XRechnung::build( $web, $xmlDocument );

check( 'the electronic invoice carries no character that XML cannot, whatever a text holds: it is read as XML, and the texts are there without the marks', simplexml_load_string( $xmlText ) !== false && preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F]|\xEF\xBF\xBE/', $xmlText ) === 0 && str_contains( $xmlText, 'Firm  GmbH' ) && str_contains( $xmlText, 'Thanks for your order' ) && str_contains( $xmlText, "\u{1F468}\u{200D}\u{1F469}" ) && str_contains( $xmlText, "\u{AD}" ) );

// The credit note of an electronic invoice is an electronic invoice of the type 381
$ec = (array) docBody( $web, 'crm-documents/credit', [ 'id' => $e['id'] ] )['document'];
docBody( $web, 'crm-documents/save', [ 'id' => $ec['id'], 'template_key' => 'credit-note', 'buyer_reference' => '991-12345-67' ] );
$ecf = docBody( $web, 'crm-documents/finalise', [ 'id' => $ec['id'] ] )['document'] ?? [];
$xmlC = new \DOMDocument();
$xmlC->load( \Nino\Filesystem::path( $web, $ecf['xml_path'] ?? '/none' ) );
$pathC = new \DOMXPath( $xmlC );
$pathC->registerNamespace( 'rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100' );

check( 'the credit note of it has a file of the type 381 with a positive amount, naming the invoice it takes back, and takes the invoice out of the books', (string) $pathC->evaluate( 'string( //rsm:ExchangedDocument/*[local-name()="TypeCode"] )' ) === '381' && str_contains( $xmlC->saveXML() ?: '', $e['number'] ) === true
	&& ( $ecf['gross'] ?? 0 ) === -10000 && $state( (int) $e['id'] ) === 'cancelled' );

// --- The export for the accountant ---------------------------------------------------------------------------------------------------

$evil = docClient( $web, [ 'name' => '=HYPERLINK("http://example.org")', 'company' => 'A; "B"', 'street' => 'Weg 3', 'zip' => '12345', 'city' => 'Berlin', 'country' => 'DE' ] );
$evilDoc = docFinal( $web, 'invoice', $evil );
$csvUri = '/crm/export/documents.csv?year='. $year;
[ $status, $csv ] = docGet( $web, $csvUri );
$body = (string) ( $csv['body'] ?? '' );
$rows = explode( "\r\n", rtrim( $body, "\r\n" ) );

check( 'the CSV of a year is an attachment that is read as UTF-8, with a semicolon between the cells and a line for each document that is no draft and has an amount', $status === 200 && str_starts_with( $body, "\xEF\xBB\xBF" ) && str_starts_with( $csv['header']['Content-Type'], 'text/csv' ) && str_starts_with( $csv['header']['Content-Disposition'], 'attachment;' )
	&& $csv['header']['Cache-Control'] === 'private, no-store' && $csv['header']['X-Content-Type-Options'] === 'nosniff' && count( $rows ) === 1 + count( Figures::ledger( $web, $year ) ) && count( Figures::ledger( $web, $year ) ) > 10 );
check( 'the amounts are written as the German books write them, a credit note with a minus', str_contains( $body, $f2['number']. ';' ) === true && preg_match( '/'. preg_quote( $cf1['number'], '/' ). ';[^;]*;[0-9.]+;[^;]*;-0,03;-0,01;-0,04;/', $body ) === 1 && preg_match( '/'. preg_quote( $f2['number'], '/' ). ';[^;]*;[0-9.]+;[^;]*;100,00;0,00;100,00;/', $body ) === 1 );

$cells = [];

foreach( $rows as $row )
	if( str_contains( $row, $evilDoc['number'] ) === true )
		$cells = str_getcsv( $row, ';', '"', '' );

check( 'the client of a document, as it was frozen, is a cell that cannot be a formula, and the quotes of a name are kept whole', ( $cells[3] ?? '' ) === 'A; "B"' && Nino\Modules\CrmDocuments\Export::text( '=1+1' ) === '\'=1+1' && Nino\Modules\CrmDocuments\Export::text( '+1' ) === '\'+1' && Nino\Modules\CrmDocuments\Export::text( '@x' ) === '\'@x'
	&& Nino\Modules\CrmDocuments\Export::text( "\tx" ) === "'\tx" && Nino\Modules\CrmDocuments\Export::text( "a\0b" ) === 'ab' && Nino\Modules\CrmDocuments\Export::text( 'plain' ) === 'plain' && Nino\Modules\CrmDocuments\Export::amount( -5 ) === '-0,05' && Nino\Modules\CrmDocuments\Export::amount( 123456 ) === '1234,56' );

$formula = docClient( $web, [ 'name' => 'Formula', 'company' => '=1+1', 'street' => 'Weg 4', 'zip' => '12345', 'city' => 'Berlin', 'country' => 'DE' ] );
$formulaDoc = docFinal( $web, 'invoice', $formula );
$csvAgain = (string) ( docGet( $web, $csvUri )[1]['body'] ?? '' );

check( 'a client named like a formula is written with an apostrophe in front, as text', str_contains( $csvAgain, ';\'=1+1;' ) === true );
check( 'the year is four digits or the answer is the 400, and nobody and an account without the permission get the 401 and the 403', docGet( $web, '/crm/export/documents.csv' )[0] === 400 && docGet( $web, '/crm/export/documents.csv?year=26' )[0] === 400 && docGet( $web, '/crm/export/documents.csv?year[]=2026' )[0] === 400 && docGet( $web, '/crm/export/documents.csv?year=1999' )[0] === 400
	&& docGet( $visitor, $csvUri )[0] === 401 && docGet( $plain, $csvUri )[0] === 403 && docGet( $web, '/crm/export/nothing.csv' )[0] === 404 );

if( \Nino\Modules\CrmDocuments\Export::zipAvailable() === true ) {

	[ $status, $zip ] = docGet( $web, '/crm/export/documents-'. $year. '.zip' );
	$archive = new \ZipArchive();
	$opened = $archive->open( (string) ( $zip['file'] ?? '/none' ) );
	$names = [];

	for( $at = 0; $opened === true && $at < $archive->numFiles; $at++ )
		$names[] = (string) $archive->getNameIndex( $at );

	$archive->close();

	check( 'the ZIP of a year holds the PDF of each document and the electronic invoice beside it, each under its number, as an attachment the kernel streams', $status === 200 && $opened === true && in_array( $f2['number']. '.pdf', $names, true ) === true && in_array( $e['number']. '.pdf', $names, true ) === true && in_array( $e['number']. '.xml', $names, true ) === true
		&& in_array( 'missing.txt', $names, true ) === false && str_starts_with( $zip['header']['Content-Disposition'], 'attachment;' ) && $zip['header']['Content-Type'] === 'application/zip' && count( $names ) === count( array_unique( $names ) ) );
	check( 'the ZIP lies in a folder of its own until the request ends, and what the request remembers goes', is_file( (string) $zip['file'] ) === true && \Nino\Modules\CrmDocuments\Export::cleanup( $web ) >= 1 && is_file( (string) $zip['file'] ) === false );

	$away = \Nino\Filesystem::path( $web, Documents::get( $web, (int) $f3['id'] )['pdf_path'] );
	rename( $away, $away. '.away' );
	[ , $left ] = docGet( $web, '/crm/export/documents-'. $year. '.zip' );
	$archive = new \ZipArchive();
	$archive->open( (string) $left['file'] );
	$missing = (string) $archive->getFromName( 'missing.txt' );
	$leftNames = [];

	for( $at = 0; $at < $archive->numFiles; $at++ )
		$leftNames[] = (string) $archive->getNameIndex( $at );

	$archive->close();
	\Nino\Modules\CrmDocuments\Export::cleanup( $web );
	rename( $away. '.away', $away );

	check( 'a document whose file is gone is left out of the ZIP and named in missing.txt', str_contains( $missing, $f3['number'] ) === true && in_array( $f3['number']. '.pdf', $leftNames, true ) === false && in_array( $f2['number']. '.pdf', $leftNames, true ) === true );

	// A file whose bytes are not the ones the hash says is as good as gone: it is not put into the ZIP
	$original = (string) file_get_contents( $away );
	file_put_contents( $away, $original. 'tampered' );
	[ , $changed ] = docGet( $web, '/crm/export/documents-'. $year. '.zip' );
	$archive = new \ZipArchive();
	$archive->open( (string) $changed['file'] );
	$missing = (string) $archive->getFromName( 'missing.txt' );
	$changedNames = [];

	for( $at = 0; $at < $archive->numFiles; $at++ )
		$changedNames[] = (string) $archive->getNameIndex( $at );

	$archive->close();
	\Nino\Modules\CrmDocuments\Export::cleanup( $web );
	file_put_contents( $away, $original );

	check( 'a document whose file is not what its hash says is left out of the ZIP and named in missing.txt as one whose file is gone is', str_contains( $missing, $f3['number'] ) === true && in_array( $f3['number']. '.pdf', $changedNames, true ) === false && in_array( $f2['number']. '.pdf', $changedNames, true ) === true );

	[ $status, $some ] = docGet( $web, '/crm/export/documents.zip?ids='. $f2['id']. ','. $e['id'] );
	$archive = new \ZipArchive();
	$archive->open( (string) ( $some['file'] ?? '/none' ) );
	$someNames = [];

	for( $at = 0; $at < $archive->numFiles; $at++ )
		$someNames[] = (string) $archive->getNameIndex( $at );

	$archive->close();
	\Nino\Modules\CrmDocuments\Export::cleanup( $web );

	check( 'the ZIP of a choice holds the documents the ids name', $status === 200 && $someNames === [ $f2['number']. '.pdf', $e['number']. '.pdf', $e['number']. '.xml' ] );
	check( 'a choice with an id that is none, a draft, a contract or nothing at all is refused as a whole, and no ZIP is made', docGet( $web, '/crm/export/documents.zip?ids='. $f2['id']. ',99999' )[0] === 404 && docGet( $web, '/crm/export/documents.zip?ids='. $f2['id']. ','. $pre['id'] )[0] === 404 && docGet( $web, '/crm/export/documents.zip?ids='. $f2['id']. ','. $cn['id'] )[0] === 404
		&& docGet( $web, '/crm/export/documents.zip' )[0] === 400 && docGet( $web, '/crm/export/documents.zip?ids=' )[0] === 400 && docGet( $web, '/crm/export/documents.zip?ids=1,x' )[0] === 400 && docGet( $web, '/crm/export/documents.zip?ids=1,,2' )[0] === 400
		&& docGet( $web, '/crm/export/documents.zip?ids='. implode( ',', range( 1, 501 ) ) )[0] === 400 && docGet( $web, '/crm/export/documents.zip?ids[]=1' )[0] === 400 && docGet( $web, '/crm/export/documents-'. ( $year + 5 ). '.zip' )[0] === 404 );

	$stale = \Nino\Filesystem::path( $web, \Nino\Modules\CrmDocuments\Export::DIRECTORY ). '/documents-0000.zip';
	touch( $stale, time() - 7200 );
	$fresh = \Nino\Filesystem::path( $web, \Nino\Modules\CrmDocuments\Export::DIRECTORY ). '/documents-1111.zip';
	touch( $fresh );

	check( 'a ZIP that an interrupted request left is swept, a fresh one is not', \Nino\Modules\CrmDocuments\Export::sweep( $web ) === 1 && is_file( $stale ) === false && is_file( $fresh ) === true );

	unlink( $fresh );
}

check( 'the ZIP of the files is no file of the books: the folder of the export is no part of the backup', array_filter( array_keys( \Nino\Backup::manifest( $web ) ), static fn( string $path ): bool => str_contains( $path, '/crm-zip' ) ) === [] );
$exported = array_values( array_filter( \Nino\Modules\Logs\Admin::recentLines( $web, 500 ), static fn( mixed $line ): bool => str_contains( (string) $line, 'Exported' ) ) );

check( 'every export writes one line to the log that counts and names no client, no number and no amount', count( $exported ) === 7 && array_filter( $exported, static fn( string $line ): bool => preg_match( '/[0-9]{4}-[0-9]{4}|K-[0-9]|Example|Hopper|[0-9],[0-9]{2}/', preg_replace( '/^[0-9: -]+/', '', $line ) ) === 1 || str_contains( $line, 'HYPERLINK' ) ) === [] );

// --- The test phase --------------------------------------------------------------------------------------------------------------

$beforeWipe = [ Db::row( $web, 'SELECT COUNT(*) AS n FROM documents' )['n'], Db::row( $web, 'SELECT COUNT(*) AS n FROM document_lines' )['n'] ];

check( 'there are documents and files to wipe', $beforeWipe[0] > 20 && glob( \Nino\Filesystem::path( $web, '/data/crm/pdf' ). '/*' ) !== [] );
check( 'the test phase asks for its word, and a word that is not it deletes nothing', docAnswer( $web, 'crm/wipe', [ 'confirm' => 'delete' ] ) === '400 crm_wipe_confirm' && Db::row( $web, 'SELECT COUNT(*) AS n FROM documents' )['n'] === $beforeWipe[0] );

$word = \Nino\Modules\Crm\Wipe::word( $web );
$wiped = docBody( $web, 'crm/wipe', [ 'confirm' => $word ] );

check( 'the test phase deletes the documents with their lines and counts them', ( $wiped['documents'] ?? -1 ) === $beforeWipe[0] && Db::row( $web, 'SELECT COUNT(*) AS n FROM documents' )['n'] === 0 && Db::row( $web, 'SELECT COUNT(*) AS n FROM document_lines' )['n'] === 0 );
check( 'and the files of the years go, and nothing else of the data folder', glob( \Nino\Filesystem::path( $web, '/data/crm/pdf' ). '/*' ) === [] && is_dir( \Nino\Filesystem::path( $web, '/data/crm' ) ) === true );
check( 'the numbers begin again, and the second press finds nothing', Db::row( $web, 'SELECT COUNT(*) AS n FROM sequences WHERE category IN ( \'offer\', \'invoice\', \'credit\' )' )['n'] === 0 && ( docBody( $web, 'crm/wipe', [ 'confirm' => $word ] )['documents'] ?? -1 ) === 0 );

// After the test phase the numbers begin again, and a file that was removed is no obstacle to the number that is given again
$again = docClient( $web, [ 'name' => 'Again Client', 'street' => 'Weg 1', 'zip' => '12345', 'city' => 'Berlin', 'country' => 'DE', 'email' => 'again@example.org' ] );
$first = docFinal( $web, 'invoice', $again );

check( 'after the test phase the first invoice is the first number again, and its file is written where the old one was removed', ( $first['number'] ?? '' ) === $prefix. '0001' && is_file( \Nino\Filesystem::path( $web, $first['pdf_path'] ?? '/none' ) ) === true );

// --- The logbook, the first screen and the reminder -----------------------------------------------------------------------------------

$logs = static fn( int $client ): array => (array) ( json_decode( (string) json_encode( docBody( $web, 'crm-logbook/list', [ 'client' => $client ] ) ), true )['entries'] ?? [] );

$step( (int) $first['id'], 'sent', $today );
$step( (int) $first['id'], 'paid', $today );

$kinds = array_values( array_filter( array_map( static fn( array $entry ): string => $entry['source'] === 'crm-documents' ? $entry['kind'] : '', $logs( $again ) ) ) );
sort( $kinds );

check( 'what happens to a document is in the logbook of its client under the package: finalised, sent, paid', $kinds === [ 'document-finalised', 'document-paid', 'document-sent' ] );

$entry = array_values( array_filter( $logs( $again ), static fn( array $one ): bool => $one['kind'] === 'document-finalised' ) )[0] ?? [];

check( 'an entry names the document by its id and its number, in small values, and nothing of the client', ( $entry['data']['label'] ?? '' ) === $first['number'] && ( $entry['data']['document'] ?? 0 ) === $first['id'] && ( $entry['source'] ?? '' ) === 'crm-documents' && str_contains( json_encode( $entry ), 'Again Client' ) === false );

$unpaid = docFinal( $web, 'invoice', $again );
$late = docFinal( $web, 'invoice', $again );
Db::exec( $web, 'UPDATE documents SET due_date = :past WHERE id = :id', [ 'past' => date( 'Y-m-d', time() - 20 * 86400 ), 'id' => $late['id'] ] );
$expiring = docFinal( $web, 'offer', $again );
Db::exec( $web, 'UPDATE documents SET valid_until = :soon WHERE id = :id', [ 'soon' => Documents::addDays( $today, 3 ), 'id' => $expiring['id'] ] );
$overview = docBody( $web, 'crm/overview' );
$overview = json_decode( (string) json_encode( $overview ), true );
$part = $overview['packages']['crm-documents'] ?? [];

check( 'the first screen has the books at a glance: the open invoices and what they come to, what is overdue and since when, the offers, what was paid this year and the drafts', ( $part['summary']['count'] ?? 0 ) === 2 && ( $part['summary']['gross'] ?? 0 ) === 20000 && ( $part['summary']['overdue_count'] ?? 0 ) === 1 && ( $part['summary']['overdue_gross'] ?? 0 ) === 10000
	&& ( $part['summary']['overdue_oldest'] ?? '' ) === date( 'Y-m-d', time() - 20 * 86400 ) && ( $part['summary']['offers_count'] ?? 0 ) === 1 && ( $part['summary']['paid_count'] ?? 0 ) === 1 && ( $part['summary']['paid_gross'] ?? 0 ) === 10000 && ( $part['summary']['paid_year'] ?? 0 ) === $year );
check( 'the documents of the latest dates and the currency are on it, and what needs the owner: an invoice that is overdue and an offer that runs out', count( $part['recent'] ?? [] ) === 4 && ( $part['currency'] ?? '' ) === '€'
	&& array_values( array_map( static fn( array $need ): string => $need['kind']. ':'. $need['package'], array_filter( $overview['needs'] ?? [], static fn( array $need ): bool => $need['package'] === 'crm-documents' ) ) ) === [ 'overdue:crm-documents', 'expiring:crm-documents' ] );

// The note of a payment reminder
$remind = static fn( int $id, array $more = [] ): string => docAnswer( $web, 'crm-documents/remind', [ 'id' => $id ] + $more );
$late = (array) Documents::get( $web, (int) $late['id'] );

check( 'a reminder by mail needs the accounts of the client, which the portal brings: without it the 409 says so, and nothing is noted', $remind( (int) $late['id'] ) === '409 crm_documents_portal_off' && Documents::get( $web, (int) $late['id'] )['reminded_at'] === '' );
check( 'a reminder that is only noted is noted - with the time, as not mailed - and shows on the logbook', $remind( (int) $late['id'], [ 'notify' => false ] ) === '200 -' && Documents::get( $web, (int) $late['id'] )['reminded_at'] !== '' && Documents::get( $web, (int) $late['id'] )['reminded_mail'] === false
	&& array_filter( $logs( $again ), static fn( array $one ): bool => $one['kind'] === 'document-reminded' ) !== [] );
check( 'only an invoice that is open or sent is reminded, and what is none is the 404', $remind( (int) $first['id'], [ 'notify' => false ] ) === '409 crm_documents_state' && $remind( (int) $expiring['id'], [ 'notify' => false ] ) === '409 crm_documents_state' && $remind( 99999, [ 'notify' => false ] ) === '404 crm_documents_unknown' );

// The figures
$figures = docBody( $web, 'crm-documents/figures' );

check( 'the figures of the year: the months of a year with what was billed, what was paid and what is open, the clients billed most, the offers by what became of them', count( $figures['months'] ?? [] ) === 12 && ( $figures['months'][0]['month'] ?? '' ) === date( 'Y-m' ) && ( $figures['months'][0]['billed'] ?? 0 ) === 30000 && ( $figures['months'][0]['paid'] ?? 0 ) === 10000
	&& ( $figures['months'][0]['open'] ?? 0 ) === 20000 && ( $figures['clients'][0]['billed'] ?? 0 ) === 30000 && ( $figures['offers']['open']['count'] ?? 0 ) === 1 && ( $figures['year'] ?? 0 ) === $year );

// --- The items ---------------------------------------------------------------------------------------------------------------------

$made = docPost( $web, 'elements/save', [ 'type' => 'crm-items', 'uri' => 'mixing', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Mixing', 'price' => 12.5, 'vat' => 19, 'unit' => 'hrs', 'unit_code' => 'HUR' ] ] );
$hidden = docPost( $web, 'elements/save', [ 'type' => 'crm-items', 'uri' => 'secret', 'locale' => 'de_DE', 'isNew' => true, 'fields' => [ 'title' => 'Hidden', 'price' => 1, 'hidden' => true ] ] );
$catalogue = docBody( $web, 'crm-documents/items' );
$listed = docBody( $web, 'crm-documents/list' );

check( 'an item is an element of the type the package brings: the price in cents and the rate in hundredths, the items that are hidden only in the screen of the items', $made[0] === 200 && $hidden[0] === 200 && count( $catalogue['items'] ?? [] ) === 2 && ( $catalogue['missing'] ?? true ) === false && ( $catalogue['unit_code'] ?? false ) === true
	&& array_column( $listed['items'], 'title' ) === [ 'Mixing' ] && $listed['items'][0]['price_net'] === 1250 && $listed['items'][0]['unit_code'] === 'HUR' && $listed['items'][0]['vat_rate'] === 0 );

docAnswer( $web, 'crm/settings-save', [ 'vat_mode' => 'regular' ] );

check( 'under regular taxation the item has its rate', docBody( $web, 'crm-documents/list' )['items'][0]['vat_rate'] === 1900 );

$types = docBody( $web, 'elements/types' );
$start = array_values( array_filter( (array) ( $types['types'] ?? [] ), static fn( array $type ): bool => ( $type['type'] ?? '' ) === 'crm-items' ) )[0] ?? [];

check( 'a new item starts with the highest rate of the settings, and a small business is asked for none', ( $start['defaults']['vat'] ?? null ) === 19 && ( $start['hidden'] ?? [ 1 ] ) === [] );

docAnswer( $web, 'crm/settings-save', [ 'vat_mode' => 'small_business' ] );
$start = array_values( array_filter( (array) ( docBody( $web, 'elements/types' )['types'] ?? [] ), static fn( array $type ): bool => ( $type['type'] ?? '' ) === 'crm-items' ) )[0] ?? [];

check( 'under small business the rate is hidden from the form', ( $start['hidden'] ?? [] ) === [ 'vat' ] );

// --- What Pdf asks the documents ---------------------------------------------------------------------------------------------------------

$uses = \Nino\Modules\Pdf::uses( $web );

check( 'Pdf is told which templates documents name, and by how many', ( $uses['uses']['invoice']['crm-documents'] ?? 0 ) === 3 && ( $uses['uses']['offer']['crm-documents'] ?? 0 ) === 1 && isset( $uses['uses']['information'] ) === false && $uses['failed'] === [] && $uses['unknown'] === [] );
check( 'a template that a document names is not deleted, and one that none names is', ( \Nino\Modules\Pdf\Store::deleteTemplate( $web, 'invoice' )['fail'][2] ?? '' ) === 'pdf_template_used' && ( \Nino\Modules\Pdf\Store::deleteTemplate( $web, 'information' )['ok'] ?? false ) === true && \Nino\Modules\Pdf::template( $web, 'invoice' ) !== null );

$vocabulary = \Nino\Modules\Pdf::vocabulary( $web );
$tokens = [];

foreach( $vocabulary['groups'] as $group )
	foreach( $group['tokens'] as $token )
		$tokens[] = $token['token'];

check( 'Pdf is told what a template of a document may use: four groups of tokens, the three slots and a sample, each with a label in both languages', array_column( $vocabulary['groups'], 'key' ) === [ 'document', 'invoice', 'offer', 'customer' ] && array_column( $vocabulary['slots'], 'name' ) === [ 'address', 'lines', 'totals' ] && count( $tokens ) === 9 + 7 + 6 + 9 && in_array( '/document/number', $tokens, true ) === true
	&& array_filter( $vocabulary['groups'], static fn( array $group ): bool => array_filter( $group['tokens'], static fn( array $token ): bool => is_array( $token['label'] ) === false || count( $token['label'] ) !== 2 ) !== [] ) === [] && ( $vocabulary['sample']['/site/iban'] ?? '' ) === 'DE89 3704 0044 0532 0130 00' && ( $vocabulary['sample']['/document/number'] ?? '' ) !== '' );
$offerDoc = Documents::get( $web, (int) $expiring['id'] );
$union = array_merge( array_keys( \Nino\Modules\CrmDocuments\Render::data( $web, Documents::get( $web, (int) $first['id'] ), Documents::get( $web, (int) $first['id'] )['lines'], Documents::get( $web, (int) $first['id'] )['client_frozen'] )['tokens'] ),
	array_keys( \Nino\Modules\CrmDocuments\Render::data( $web, $offerDoc, $offerDoc['lines'], $offerDoc['client_frozen'] )['tokens'] ) );

check( 'every token the documents hand to a template of an offer or an invoice is in the vocabulary, and every token of the vocabulary is handed to one of them', array_diff( $union, array_merge( $tokens, [ '/site/portal', '/site/login' ] ) ) === [] && array_diff( $tokens, $union ) === [] );

$sites = [ '/site/portal' => '', '/site/login' => '' ];
$offered = \Nino\Modules\CrmDocuments\Render::data( $web, Documents::get( $web, (int) $first['id'] ), Documents::get( $web, (int) $first['id'] )['lines'], Documents::get( $web, (int) $first['id'] )['client_frozen'] )['tokens'];

check( 'the tokens of the portal are empty where nobody answers them, so that a template that names them draws nothing', array_intersect_key( $offered, $sites ) === $sites );

\Nino\Callbacks::registerCallback( $web, \Nino\Modules\CrmDocuments\Render::TOKENS, static function( array &$appData, array &$ask ): void { $ask['tokens']['/site/portal'] = 'https://example.org/portal'; $ask['tokens']['/other'] = 'x'; $ask['tokens']['/site/Bad'] = 'x'; $ask['tokens']['/site/login'] = [ 'no' ]; } );
$hooked = \Nino\Modules\CrmDocuments\Render::data( $web, Documents::get( $web, (int) $first['id'] ), Documents::get( $web, (int) $first['id'] )['lines'], Documents::get( $web, (int) $first['id'] )['client_frozen'] )['tokens'];

check( 'what another package says of the site is taken where it is a token /site/<word> and a text, and nothing else is', $hooked['/site/portal'] === 'https://example.org/portal' && $hooked['/site/login'] === '' && isset( $hooked['/other'] ) === false && isset( $hooked['/site/Bad'] ) === false );

// --- Files that no document names ---------------------------------------------------------------------------------------------------------------------

$strayFile = \Nino\Filesystem::path( $web, '/data/crm/pdf/'. $year. '/R-'. $year. '-9999.pdf' );
file_put_contents( $strayFile, 'a file of a database that was older' );
$stray = \Nino\Modules\CrmDocuments\Status::files( $web );
$strayBytes = (string) file_get_contents( $strayFile );
unlink( $strayFile );

check( 'a file in the folder of the books that no document names is told, and not touched: the status line is a hint and says how many', $stray['state'] === 'stray' && $stray['files'] === 1 && $strayBytes === 'a file of a database that was older' && \Nino\Modules\CrmDocuments\Status::files( $web )['state'] === 'ok' );

// --- The template a new draft starts with ------------------------------------------------------------------------------------------------------------------

$templateOf = static fn( string $category, int $client ): string => Documents::template( $web, $category, $client );
$fresh = docClient( $web, [ 'name' => 'Template Client' ] );

check( 'a draft starts with the only template of its tag, else with the one the client was last written with, else with the standard one of its kind, else with none', $templateOf( 'offer', $fresh ) === 'offer' && $templateOf( 'invoice', $fresh ) === 'invoice' && $templateOf( 'credit', $fresh ) === 'credit-note' && $templateOf( 'nothing', $fresh ) === '' );

docDraft( $web, 'invoice', $fresh, [ 'template_key' => 'credit-note' ] );

check( '...the one the client was last written with is the one before the standard', $templateOf( 'invoice', $fresh ) === 'credit-note' && $templateOf( 'invoice', $ada ) !== '' );

// --- The files are in the backup, and the line of the backup says what they weigh ---------------------------------------------------------

$kept = docFinal( $web, 'invoice', $again );
$manifest = \Nino\Backup::manifest( $web );
$real = \Nino\Filesystem::path( $web, $kept['pdf_path'] );

check( 'the PDF files of the documents are in the daily backup, by the folder the manifest of the feature names - and the folder of the ZIPs of the export is not', array_key_exists( $real, $manifest ) === true && str_starts_with( (string) $manifest[$real], 'data/crm/pdf/' ) === true && array_filter( array_keys( $manifest ), static fn( string $path ): bool => str_contains( $path, '/data/crm-zip/' ) ) === [] );
check( 'and so is the database, as the copy the core makes', array_filter( array_keys( $manifest ), static fn( string $path ): bool => str_ends_with( $path, '/data/crm.sqlite' ) ) !== [] );

$line = \Nino\Modules\CrmDocuments\Status::backup( $web );

check( 'the line of the backup is a hint that says how many files and how many megabytes the books hold, and says nothing is wrong where the disk has room', $line['state'] === 'ok' && $line['files'] === count( glob( \Nino\Filesystem::path( $web, '/data/crm/pdf' ). '/*/*' ) ) && $line['files'] === 5 && $line['megabytes'] === 1 );

\Nino\Backup::$diskFree = 1024;
$short = \Nino\Modules\CrmDocuments\Status::backup( $web );
\Nino\Backup::$diskFree = null;

check( 'where the disk that packs the backup has no room for it the line says so, with what is needed and what is free', $short['state'] === 'short' && $short['free'] === 0 && $short['need'] >= 4 );

$layout = \Nino\Filesystem::path( $web, '/data/pdf' );
@mkdir( $layout. '/images', 0755, true );
$png = static function( int $bytes ): string {
	$side = (int) ceil( sqrt( $bytes / 3 ) );
	$im = imagecreatetruecolor( $side, $side );
	mt_srand( 7 );

	for( $x = 0; $x < $side; $x++ )
		for( $y = 0; $y < $side; $y++ )
			imagesetpixel( $im, $x, $y, imagecolorallocate( $im, mt_rand( 0, 255 ), mt_rand( 0, 255 ), mt_rand( 0, 255 ) ) );

	ob_start();
	imagepng( $im, null, 9 );

	return (string) ob_get_clean();
};
file_put_contents( $layout. '/images/logo.png', $png( 200000 ) );
file_put_contents( $layout. '/layout.php', "<?php\nreturn ". var_export( [ 'logo' => 'logo.png' ], true ). ";\n" );

check( 'a logo that no text of a PDF names is in no document: the line says nothing of it', \Nino\Modules\CrmDocuments\Status::backup( $web )['state'] === 'ok' );

file_put_contents( $layout. '/layout.php', "<?php\nreturn ". var_export( [ 'logo' => 'logo.png', 'header' => '<p><img src="pdf:logo" width="30" alt=""></p>' ], true ). ";\n" );
$heavy = \Nino\Modules\CrmDocuments\Status::backup( $web );

check( 'a logo that the header names and that is large is said, with its size in kilobytes: every PDF carries it', $heavy['state'] === 'logo' && $heavy['logo'] > 100000 && $heavy['files'] === 5 );

file_put_contents( $layout. '/images/logo.png', $png( 30000 ) );

check( 'a logo of thirty kilobytes is no reason to say anything', \Nino\Modules\CrmDocuments\Status::backup( $web )['state'] === 'ok' );

@unlink( $layout. '/layout.php' );
@unlink( $layout. '/images/logo.png' );

// --- The words ------------------------------------------------------------------------------------------------------------------------------

$de = (array) require dirname( __DIR__ ). '/Surface/text/de_DE.php';
$en = (array) require dirname( __DIR__ ). '/Surface/text/en_US.php';

check( 'the words of the surface are the same keys in both languages, each a text of its own, none the key itself', array_keys( $de ) === array_keys( $en ) && array_filter( $de, static fn( string $text, string $key ): bool => $text === '' || $text === $key, ARRAY_FILTER_USE_BOTH ) === []
	&& array_filter( $en, static fn( string $text, string $key ): bool => $text === '' || $text === $key, ARRAY_FILTER_USE_BOTH ) === [] && array_filter( array_keys( $en ), static fn( string $key ): bool => $de[$key] === $en[$key] && preg_match( '/^(?:error|lack|category|state|export|preview|docs)\./', $key ) === 1 && in_array( $key, [ 'export.col-state', 'docs.line-pdf' ], true ) === false && $key !== 'category.information' ) === [] );
check( 'a word has the same places for values in both languages', array_filter( $en, static fn( string $text, string $key ): bool => substr_count( $text, '%s' ) !== substr_count( $de[$key], '%s' ), ARRAY_FILTER_USE_BOTH ) === [] );

$codes = [];

foreach( $source as $file => $code )
	if( preg_match_all( '/\'(crm_documents_[a-z_]+)\'/', $code, $found ) > 0 )
		$codes = array_merge( $codes, $found[1] );

foreach( array_keys( ( new \ReflectionClass( \Nino\Modules\CrmDocuments\Api::class ) )->getConstant( 'REFUSALS' ) ) as $kind )
	$codes[] = ( new \ReflectionClass( \Nino\Modules\CrmDocuments\Api::class ) )->getConstant( 'REFUSALS' )[$kind][1];

$codes = array_unique( array_merge( array_filter( $codes, static fn( string $code ): bool => str_ends_with( $code, '_' ) === false ), array_map( static fn( string $kind ): string => 'crm_documents_line_'. $kind, [ 'empty', 'long', 'format', 'amount', 'rate' ] ) ) );

check( 'every code the package answers a person with has a sentence in both languages', array_filter( $codes, static fn( string $code ): bool => isset( $en['error.'. $code], $de['error.'. $code] ) === false ) === [] );
check( 'every sentence of an error is the sentence of a code the package answers with: none is left over', array_filter( array_keys( $en ), static fn( string $key ): bool => str_starts_with( $key, 'error.' ) === true && in_array( substr( $key, 6 ), $codes, true ) === false ) === [] );
check( 'every point of the checklist of the electronic invoice has its sentence', array_filter( \Nino\Modules\CrmDocuments\XRechnung::KEYS, static fn( string $key ): bool => isset( $en['lack.'. $key], $de['lack.'. $key] ) === false ) === [] && count( array_filter( array_keys( $en ), static fn( string $key ): bool => str_starts_with( $key, 'lack.' ) ) ) === count( \Nino\Modules\CrmDocuments\XRechnung::KEYS ) );

$placed = [];

foreach( [ 'de_DE', 'en_US' ] as $locale ) {

	$words = (array) require dirname( __DIR__ ). '/install/text/'. $locale. '.php';
	$placed[$locale] = array_keys( $words );
}

check( 'the 21 words of the PDF are in both languages under the key the package reads them by, and nothing else is in the install text', $placed['de_DE'] === $placed['en_US'] && count( $placed['de_DE'] ) === 21 && array_filter( $placed['de_DE'], static fn( string $key ): bool => preg_match( '~^\[\[/feature/crm-documents/pdf-(title|due|valid|period|vatnote|head|totals)/[a-z-]+\]\]$~D', $key ) !== 1 ) === [] );

$read = [];

foreach( [ 'title.offer', 'title.invoice', 'title.credit', 'due.sentence', 'due.sentence-none', 'valid.sentence', 'valid.sentence-none', 'period.line', 'period.none', 'period.none-offer', 'vatnote.small-business', 'vatnote.reverse', 'head.pos', 'head.title', 'head.qty', 'head.price', 'head.rate', 'head.amount', 'totals.net', 'totals.vat', 'totals.gross' ] as $key )
	$read[] = '[[/feature/crm-documents/pdf-'. str_replace( '.', '/', $key ). ']]';

check( 'those are the words the package asks for: each is there, and no other', array_diff( $read, $placed['de_DE'] ) === [] && array_diff( $placed['de_DE'], $read ) === [] );

$manifest = (array) require dirname( __DIR__ ). '/install/manifest.php';
$fragments = array_map( static fn( string $file ): string => basename( $file, '.tpl' ), (array) glob( dirname( __DIR__ ). '/install/templates/pdf/*.tpl' ) );

check( 'the unit brings the ten fragments of the PDF and the type of the items, and every fragment the package reads is one of them', count( $manifest['templates'] ) === 10 && array_diff( array_map( static fn( string $name ): string => 'pdf/'. $name. '.tpl', $fragments ), $manifest['templates'] ) === [] && count( $fragments ) === 10 && $manifest['elementTypes'] === [ 'crm-items.php' ] && is_file( dirname( __DIR__ ). '/install/crm-items.php' ) === true
	&& array_filter( [ 'address', 'address-line', 'line', 'line-description', 'line-break', 'line-rate', 'lines', 'lines-rate-head', 'totals', 'totals-rate' ], static fn( string $name ): bool => in_array( 'crm-documents-'. $name, $fragments, true ) === false ) === [] );

// The seed of each language installs add-only into a site of its own, and each refuses nothing
foreach( [ 'de_DE', 'en_US' ] as $locale ) {

	$seeded = docWorld( 'docs-seed-'. $locale );
	$seedWeb = docRequest( $seeded[1] );
	$seedWeb['/nino/locales/native'] = $locale;
	$seed = \Nino\Modules\CrmDocuments\Templates::seed( $seedWeb );
	$tags = \Nino\Features::saveSettings( $seedWeb, 'pdf', [ 'tags' => \Nino\Modules\CrmDocuments\Templates::TAGS ] );
	$done = \Nino\Modules\Pdf::install( $seedWeb, $seed['templates'], $seed['blocks'], $seed['layout'], false );

	check( 'the seed in '. $locale. ' makes six templates with the English keys, four text blocks and a layout, and Pdf refuses none of them', $tags === [] && array_keys( $seed['templates'] ) === [ 'offer', 'invoice', 'credit-note', 'contract', 'privacy', 'information' ] && count( $done['added']['templates'] ?? [] ) === 6 && array_filter( array_map( 'count', (array) ( $done['refused'] ?? [ 'x' ] ) ) ) === []
		&& count( $done['added']['blocks'] ?? [] ) === 4 && array_keys( $seed['blocks'] ) === [ 'payment-terms', 'validity', 'signature', 'contact' ] );
}

ninoWarnings();

// --- Every action with nothing and with what a stranger sends: no 5xx, no warning --------------------------------------------------------------

$sent = [];
$wrong = [];

foreach( $own as $action ) {

	foreach( [ [], [ 'id' => 1 ], [ 'id' => 'x' ], [ 'id' => 999999 ], [ 'id' => [ 1 ] ], [ 'category' => 'invoice' ], [ 'category' => [ 'x' ] ], [ 'client_id' => 'x', 'category' => 'offer' ], [ 'id' => 1, 'lines' => 'x', 'inputs' => 'x', 'project' => [ 'x' ] ], [ 'id' => "1\0", 'state' => "paid\0" ],
		[ 'year' => '2026', 'state' => 'x', 'project' => 'a b' ], [ 'id' => $first['id'], 'state' => [ 'paid' ], 'date' => [ 'x' ] ], [ 'id' => $first['id'], 'notes' => [ 'x' ] ], [ 'id' => $first['id'], 'notify' => 'x' ] ] as $data ) {

		[ $status ] = docPost( $web, $action, $data );
		$sent[] = $status;

		if( $status >= 500 || $status === 0 )
			$wrong[] = $action. ' '. json_encode( $data ). ' '. $status;
	}
}

check( 'no action answers a request that makes no sense with a 5xx: '. count( $sent ). ' requests'. ( $wrong === [] ? '' : ', '. $wrong[0] ), $wrong === [] && count( $sent ) === count( $own ) * 14 );
check( 'and none of them made a warning', ninoWarnings() === [] );
check( 'every action refuses an account that is not the owner\'s, with a session that has no permission of the CRM, and a post without its token', array_filter( $own, static function( string $action ) use ( &$plain, &$web ): bool {
	$tokenless = docPost( $web, $action, [ 'id' => 1 ], [ 'token' => '' ] );
	$noperm = docPost( $plain, $action, [ 'id' => 1 ] );

	return $tokenless[0] !== 403 || $noperm[0] !== 403;
} ) === [] );

// ...and each method asks for the permission itself, on top of what the endpoint asks: it is called as the workbench calls a panel, with an account that has none
$targets = array_map( static fn( array $entered ): array => $entered['target'], array_filter( \Nino\Modules\Crm\Api::actions( $web ), static fn( array $entered ): bool => $entered['package'] === 'crm-documents' ) );
$unguarded = array_keys( array_filter( $targets, static function( array $target, string $action ) use ( &$plain ): bool {
	$denied = ninoCall( $plain, $target, [ 'id' => 1, 'category' => 'invoice', 'client_id' => 1 ], $action );
	$rows = Db::row( $plain, 'SELECT COUNT(*) AS n FROM documents' )['n'];

	return $denied[0] !== 403 || $rows !== Db::row( $plain, 'SELECT COUNT(*) AS n FROM documents' )['n'];
}, ARRAY_FILTER_USE_BOTH ) );

check( 'each of the fifteen methods of the actions refuses an account without the permission of the CRM by itself: the 403', count( $targets ) === 15 && $unguarded === [] );

// --- The bytes of a document are what they were -------------------------------------------------------------------------------------------------

// The documents of fixtures/pdf-cases.php were finalised by the Nino 1 stand, which recorded what it wrote (fixtures/pdf-hashes.json): the same documents here have to come to the same bytes - the PDF and the
// electronic invoice - and the same numbers, the same totals and the same sentences of the checklist. Nothing of it is data of anybody
$cases = require __DIR__. '/fixtures/pdf-cases.php';
$recorded = json_decode( (string) file_get_contents( __DIR__. '/fixtures/pdf-hashes.json' ), true );
$bytes = docWorld( 'docs-bytes' );
$bytesWeb = docRequest( $bytes[1] );
docSignIn( $bytesWeb, 'admin@example.org' );

\Nino\Features::saveSettings( $bytesWeb, 'pdf', [ 'tags' => \Nino\Modules\CrmDocuments\Templates::TAGS ] );
\Nino\Modules\Pdf::install( $bytesWeb, $cases['templates'], [], [], false );

foreach( $cases['settings'] as $key => $value )
	\Nino\Modules\Crm\Settings::set( $bytesWeb, $key, $value );

$clients = [];

foreach( $cases['clients'] as $name => $fields )
	$clients[$name] = \Nino\Modules\Crm\Clients::add( $bytesWeb, $fields )['id'];

$got = [];
$ids = [];

foreach( $cases['documents'] as $step ) {

	if( isset( $step['vat_mode'] ) === true )
		\Nino\Modules\Crm\Settings::set( $bytesWeb, 'vat_mode', $step['vat_mode'] );

	$input = array_intersect_key( $step, array_flip( [ 'date', 'template_key', 'inputs', 'period_from', 'period_to', 'lines', 'reverse_charge', 'title' ] ) );

	if( isset( $step['credit_of'] ) === true )
		$draft = Documents::change( $bytesWeb, (int) Finalise::creditOf( $bytesWeb, $ids[$step['credit_of']] )['id'], $input );
	else
		$draft = Documents::add( $bytesWeb, $step['category'], $step['category'] === 'contract' ? 0 : $clients[$step['client']], $input + ( isset( $step['parent'] ) === true ? [ 'parent_id' => $ids[$step['parent']] ] : [] ) );

	if( is_string( $draft ) === true ) {
		$got[$step['name']] = [ 'draft' => $draft ];
		continue;
	}

	$ids[$step['name']] = $draft['id'];
	$final = Finalise::finalise( $bytesWeb, $draft['id'] );

	if( isset( $step['vat_mode'] ) === true )
		\Nino\Modules\Crm\Settings::set( $bytesWeb, 'vat_mode', $cases['settings']['vat_mode'] );

	if( is_string( $final ) === true ) {
		$got[$step['name']] = [ 'final' => $final ];
		continue;
	}

	$pdf = \Nino\Filesystem::path( $bytesWeb, $final['pdf_path'] );
	$xml = $final['xml_path'] === '' ? null : \Nino\Filesystem::path( $bytesWeb, $final['xml_path'] );
	$got[$step['name']] = [ 'number' => $final['number'], 'net' => $final['net'], 'vat' => $final['vat'], 'gross' => $final['gross'], 'pdf' => [ filesize( $pdf ), hash_file( 'sha256', $pdf ) ], 'xml' => $xml === null ? null : [ filesize( $xml ), hash_file( 'sha256', $xml ) ], 'lacks' => $final['xml_lacks'] ];
}

$different = array_keys( array_filter( $recorded, static fn( array $one, string $name ): bool => ( $got[$name] ?? null ) !== $one, ARRAY_FILTER_USE_BOTH ) );

check( 'the ten documents of the cases are the documents the Nino 1 stand wrote, to the byte - the PDF and the electronic invoice, their lengths and hashes, and the numbers and totals: '. ( $different === [] ? 'all' : implode( ', ', $different ) ), $different === [] && count( $got ) === 10 && count( $recorded ) === 10 );
check( 'the totals of the cases are the ones the books of the old CRM had: the tax of a rate is taken of its net, the credit notes of the invoice come to its tax, a discount and a negative price are kept', $got['invoice']['net'] === 103001 && $got['invoice']['vat'] === 19091 && $got['credit-one']['vat'] + $got['credit-two']['vat'] === -$got['invoice']['vat'] && $got['credit-one']['gross'] + $got['credit-two']['gross'] === -$got['invoice']['gross']
	&& $got['reverse']['lacks'] === [ 'reverse_charge' ] && $got['reverse']['xml'] === null && $got['private']['xml'] === null && $got['small']['xml'] !== null );

// --- The file of a document is written once -----------------------------------------------------------------------------------------------------------

$keepWeb = $bytesWeb;
$staged = \Nino\Modules\CrmDocuments\Render::stage( $keepWeb, '/data/crm/pdf/2020/once.pdf', 'first' );
$restaged = \Nino\Modules\CrmDocuments\Render::stage( $keepWeb, '/data/crm/pdf/2020/once.pdf', 'second' );
$kept = \Nino\Modules\CrmDocuments\Render::publish( $keepWeb, '/data/crm/pdf/2020/once.pdf', $staged, hash( 'sha256', 'first' ) );
$again = \Nino\Modules\CrmDocuments\Render::publish( $keepWeb, '/data/crm/pdf/2020/once.pdf', $restaged, hash( 'sha256', 'second' ) );

check( 'a file of the books is written once: it lies under a pending name until it is given its name, two files for one name have two pending names, the second is refused the name and the first bytes stay, and no temporary file is left',
	$staged !== '' && $restaged !== '' && $staged !== $restaged && preg_match( '~/once\.pdf\.pending-[0-9a-f]{12}$~D', $staged ) === 1 && $kept === true && $again === false && is_file( $restaged ) === true && file_get_contents( \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020/once.pdf' ) ) === 'first'
	&& is_file( $staged ) === false && glob( \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020' ). '/*.tmp' ) === [] );

unlink( $restaged );

check( 'only a place of the shape the package writes takes a file: other years, other names, a path out of the folder, a null byte', array_filter( [ '/data/crm/pdf/20/a.pdf', '/data/crm/pdf/2020/a.php', '/data/crm/pdf/2020/../x.pdf', '/data/crm/pdf/2020/a/b.pdf', '/data/crm/other/2020/a.pdf', "/data/crm/pdf/2020/a\0.pdf", '/data/crm/pdf/2020/'. str_repeat( 'a', 61 ). '.pdf', '/data/crm/pdf/2020/.pdf' ],
	static fn( string $path ): bool => \Nino\Modules\CrmDocuments\Render::stage( $keepWeb, $path, 'x' ) !== '' ) === [] && glob( \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020' ). '/*' ) === [ \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020/once.pdf' ) ] );
check( 'the file reader gives back only a regular file of that shape, and never a link', \Nino\Modules\CrmDocuments\Render::file( $keepWeb, '/data/crm/pdf/2020/once.pdf' ) !== null && \Nino\Modules\CrmDocuments\Render::file( $keepWeb, '/data/crm/pdf/2020/../2020/once.pdf' ) === null && \Nino\Modules\CrmDocuments\Render::file( $keepWeb, '/data/crm.sqlite' ) === null && is_file( \Nino\Filesystem::path( $keepWeb, '/data/crm.sqlite' ) ) === true && \Nino\Modules\CrmDocuments\Render::file( $keepWeb, '' ) === null );

symlink( \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020/once.pdf' ), \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020/linked.pdf' ) );

check( 'a link in the folder is no file of the books', \Nino\Modules\CrmDocuments\Render::file( $keepWeb, '/data/crm/pdf/2020/linked.pdf' ) === null && \Nino\Modules\CrmDocuments\Render::free( $keepWeb, '/data/crm/pdf/2020/linked.pdf' ) === false && \Nino\Modules\CrmDocuments\Render::publish( $keepWeb, '/data/crm/pdf/2020/linked.pdf', \Nino\Modules\CrmDocuments\Render::stage( $keepWeb, '/data/crm/pdf/2020/linked.pdf', 'x' ), hash( 'sha256', 'x' ) ) === false );

docRemove( \Nino\Filesystem::path( $keepWeb, '/data/crm/pdf/2020' ) );

// --- The core off, the package off, the package removed ---------------------------------------------------------------------------------------

// --- A database that cannot be read -------------------------------------------------------------------------------------------

// What cannot be read is no document that is not there: the files are answered with the 503, and nothing is said of a number, a file or a client. The table of the documents is out of reach for the length of the requests
$lockedId = (int) $final['id'];
$lockedXml = (int) $nxFinal['id'];
$pdoConnection = Db::pdo( $web );
$pdoConnection->exec( 'PRAGMA foreign_keys = OFF' );
$pdoConnection->exec( 'PRAGMA legacy_alter_table = ON' );
$pdoConnection->exec( 'ALTER TABLE documents RENAME TO documents_away' );
$lockedWeb = docRequest( $where );
docSignIn( $lockedWeb, 'admin@example.org' );
ninoWarnings();
$lockedAnswers = [ 'pdf' => docGet( $lockedWeb, '/.crm/pdf/'. $lockedId ), 'xml' => docGet( $lockedWeb, '/.crm/xml/'. $lockedXml ), 'preview' => docGet( $lockedWeb, '/crm/preview/'. $lockedId ),
	'csv' => docGet( $lockedWeb, '/crm/export/documents.csv?year='. $year ), 'zip' => docGet( $lockedWeb, '/crm/export/documents-'. $year. '.zip' ) ];
$pdoConnection->exec( 'ALTER TABLE documents_away RENAME TO documents' );
$pdoConnection->exec( 'PRAGMA legacy_alter_table = OFF' );
$pdoConnection->exec( 'PRAGMA foreign_keys = ON' );
ninoWarnings();

check( 'where the table of the documents cannot be read the download of a PDF and of an electronic invoice, the preview and the export are the 503 - never the 404, which would say that the document is not there', array_filter( $lockedAnswers, static fn( array $one ): bool => $one[0] !== 503 ) === [] && ( $lockedAnswers['pdf'][1]['body'] ?? '' ) !== '' );

// The settings that cannot be read leave the form of an item as it is, and say so in the log
$settingsTable = Db::pdo( $web );
$settingsTable->exec( 'PRAGMA foreign_keys = OFF' );
$settingsTable->exec( 'PRAGMA legacy_alter_table = ON' );
$settingsTable->exec( 'ALTER TABLE settings RENAME TO settings_away' );
$formWeb = docRequest( $where );
ninoWarnings();
$formArgs = [ 'type' => 'crm-items', 'defaults' => [], 'hidden' => [] ];
\Nino\Modules\CrmDocuments\Rules::callbackStart( $formWeb, $formArgs );
$formWarnings = ninoWarnings();
$settingsTable->exec( 'ALTER TABLE settings_away RENAME TO settings' );
$settingsTable->exec( 'PRAGMA legacy_alter_table = OFF' );
$settingsTable->exec( 'PRAGMA foreign_keys = ON' );

check( 'where the settings cannot be read the form of an item starts empty, hides nothing and says why in the log', $formArgs === [ 'type' => 'crm-items', 'defaults' => [], 'hidden' => [] ] && array_filter( $formWarnings, static fn( string $warning ): bool => str_contains( $warning, 'the settings of the documents could not be read' ) ) !== [] );

// And a lock that is real: another process holds the database exclusively, and the download of a file is the 503 for it, too. The process says when it holds it and is let go by the removal of the signal file
$prepared = docRequest( $where );
docSignIn( $prepared, 'admin@example.org' );
$lockedDoc = docFinal( $prepared, 'invoice', $ada, [ 'lines' => [ $crashLine ] ] );
$lockFile = (string) $settingsTable->query( 'PRAGMA database_list' )->fetch( \PDO::FETCH_ASSOC )['file'];
$mark = $where. '/locked';
$locker = proc_open( [ PHP_BINARY, __FILE__, 'locker', $features, $lockFile, $mark ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $lockerPipes );
$waited = 0;

while( is_file( $mark ) === false && $waited++ < 10000 )
	usleep( 1000 );

$lockedWeb = docRequest( $where );
docSignIn( $lockedWeb, 'admin@example.org' );
$locked = docGet( $lockedWeb, '/.crm/pdf/'. $lockedDoc['id'] );
unlink( $mark );
stream_get_contents( $lockerPipes[1] );
stream_get_contents( $lockerPipes[2] );
proc_close( $locker );
$released = docRequest( $where );
docSignIn( $released, 'admin@example.org' );

check( 'where another process holds the database locked the download of a PDF is the 503, as the actions are, and it is the file again when the lock is let go', $locked[0] === 503 && docGet( $released, '/.crm/pdf/'. $lockedDoc['id'] )[0] === 200 );

// --- A restore of a backup carries the files of the books with the database, and only with it ----------------------------------------------------

$rest = docWorld( 'docs-restore' );
$restWeb = docRequest( $rest[1] );
docSignIn( $restWeb, 'admin@example.org' );
docSetupSeller( $restWeb );
$restAda = docClient( $restWeb, [ 'name' => 'Ada Example' ] );
$restBob = docClient( $restWeb, [ 'name' => 'Bob Example', 'street' => 'Weg 2' ] );
$restYear = $year - 3;
$restLine = static fn( string $price ): array => [ 'title' => 'Mix', 'qty' => '1', 'price' => $price ];

// The archive holds R-<year>-0001 of Ada
$adaDoc = docFinal( $restWeb, 'invoice', $restAda, [ 'date' => $restYear. '-03-03', 'lines' => [ $restLine( '100,00' ) ] ] );
$adaFile = \Nino\Filesystem::path( $restWeb, $adaDoc['pdf_path'] );
$adaBytes = (string) file_get_contents( $adaFile );
$archive = \Nino\Modules\Backups::now( $restWeb );

// Afterwards the books are made anew, as the test phase and a new start do: the number R-<year>-0001 is given to Bob, with a file of the same name and another content
$restPdo = Db::pdo( $restWeb );
$restPdo->exec( 'PRAGMA foreign_keys = OFF' );
$restPdo->exec( 'DELETE FROM document_lines WHERE document_id = '. (int) $adaDoc['id'] );
$restPdo->exec( 'DELETE FROM documents WHERE id = '. (int) $adaDoc['id'] );
$restPdo->exec( 'PRAGMA foreign_keys = ON' );
$restPdo->exec( 'UPDATE sequences SET next = 1 WHERE category = \'invoice\' AND year = '. $restYear );
unlink( $adaFile );
$bobDoc = docFinal( $restWeb, 'invoice', $restBob, [ 'date' => $restYear. '-03-03', 'lines' => [ $restLine( '250,00' ) ] ] );
$bobFile = \Nino\Filesystem::path( $restWeb, $bobDoc['pdf_path'] );
$bobBytes = (string) file_get_contents( $bobFile );
$laterDoc = docFinal( $restWeb, 'invoice', $restBob, [ 'date' => $restYear. '-03-04', 'lines' => [ $restLine( '10,00' ) ] ] );
$laterFile = \Nino\Filesystem::path( $restWeb, $laterDoc['pdf_path'] );

// The database is held by another process, so the restore leaves it as it is: not one file of the books of a live document is written over
$restDb = (string) $restPdo->query( 'PRAGMA database_list' )->fetch( \PDO::FETCH_ASSOC )['file'];
$restMark = $rest[1]. '/locked-restore';
$restLocker = proc_open( [ PHP_BINARY, __FILE__, 'locker', $features, $restDb, $restMark ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $restLockerPipes );

for( $waited = 0; is_file( $restMark ) === false && $waited < 10000; $waited++ )
	usleep( 1000 );

$heldNotice = null;
$held = \Nino\Modules\Backups\Admin::restore( $restWeb, (string) $archive, $heldNotice );
unlink( $restMark );
stream_get_contents( $restLockerPipes[1] );
stream_get_contents( $restLockerPipes[2] );
proc_close( $restLocker );
ninoWarnings();
$restWeb = docRequest( $rest[1] );
docSignIn( $restWeb, 'admin@example.org' );
$heldStatus = \Nino\Modules\CrmDocuments\Status::files( $restWeb );
$heldDownload = docGet( $restWeb, '/.crm/pdf/'. $bobDoc['id'] );

check( 'a restore that leaves the database as it is (it is held by another process) does not write one file of the books: the file of a live document is what it was, and the status and the download say so',
	is_array( $held ) === true && file_get_contents( $bobFile ) === $bobBytes && is_file( $laterFile ) === true && $heldStatus['state'] === 'ok' && $heldDownload[0] === 200
	&& Documents::get( $restWeb, (int) $bobDoc['id'] )['number'] === 'R-'. $restYear. '-0001' && glob( dirname( $bobFile ). '/*.pending-*' ) === [] );

// The database is free: the restore puts the database of the archive in place, and the files of the archive with it - a live file with a name that the archive has is the file of the archive, which is the file of the row that is there now
$freed = \Nino\Modules\Backups\Admin::restore( $restWeb, (string) $archive, $freedNotice );
ninoWarnings();
$restWeb = docRequest( $rest[1] );
docSignIn( $restWeb, 'admin@example.org' );
$freedStatus = \Nino\Modules\CrmDocuments\Status::files( $restWeb );
$adaBack = Documents::get( $restWeb, (int) $adaDoc['id'] );
$freedDownload = docGet( $restWeb, '/.crm/pdf/'. $adaDoc['id'] );

check( 'a restore that puts the database of the archive in place carries the files of the books: the file with the name of the archive is the file of the archive, byte for byte, and the document of the archive is downloaded with it',
	$freed === true && $adaBack !== null && $adaBack['number'] === 'R-'. $restYear. '-0001' && file_get_contents( $bobFile ) === $adaBytes && $freedDownload[0] === 200 );
check( 'and a file of a document that was made after the archive and has no row now is a file without a row, as the status tells it, and is not touched', is_file( $laterFile ) === true && $freedStatus['state'] === 'stray' && in_array( basename( $laterFile, '.pdf' ), $freedStatus['numbers'], true ) === true );

// Every file of the books is taken out of the staging where the database is kept - the pending names too - and nothing else is: asked with a staging of its own, while another process holds the database
$offArgs = [ 'dataDir' => \Nino\Filesystem::path( $restWeb, '/data' ), 'staging' => $rest[1]. '/staging-kept' ];
mkdir( $offArgs['staging']. '/data/crm/pdf/2000', 0755, true );
file_put_contents( $offArgs['staging']. '/data/crm/pdf/2000/R-2000-0001.pdf', 'x' );
file_put_contents( $offArgs['staging']. '/data/crm/pdf/2000/R-2000-0001.pdf.pending-0123456789ab', 'y' );
file_put_contents( $offArgs['staging']. '/data/crm.sqlite', 'not a database' );
file_put_contents( $offArgs['staging']. '/data/other.txt', 'z' );
$keptMark = $rest[1]. '/locked-kept';
$keptLocker = proc_open( [ PHP_BINARY, __FILE__, 'locker', $features, $restDb, $keptMark ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $keptLockerPipes );

for( $waited = 0; is_file( $keptMark ) === false && $waited < 10000; $waited++ )
	usleep( 1000 );

$offApp = docRequest( $rest[1] );
$keptThrown = '';

try {
	\Nino\Modules\CrmDocuments\Hooks::callbackRestore( $offApp, $offArgs );
}
catch( \RuntimeException $e ) {
	$keptThrown = $e->getMessage();
}

unlink( $keptMark );
stream_get_contents( $keptLockerPipes[1] );
stream_get_contents( $keptLockerPipes[2] );
proc_close( $keptLocker );
ninoWarnings();

check( 'where the database is kept every file of the books, the pending names with them, is taken out of the staging and nothing else is', $keptThrown === '' && file_exists( $offArgs['staging']. '/data/crm/pdf' ) === false && is_file( $offArgs['staging']. '/data/other.txt' ) === true );
check( 'and a restore that is asked with no place for the files is refused with a sentence that names no path', ( static function() use ( $offApp ): string {
	$args = [ 'dataDir' => '', 'staging' => '' ];

	try {
		\Nino\Modules\CrmDocuments\Hooks::callbackRestore( $offApp, $args );
	}
	catch( \RuntimeException $e ) {
		return $e->getMessage();
	}

	return '';
} )() === 'the restore did not say where the data is and where the copy lies' );

$gone = docWorld( 'docs-gone' );
$goneWeb = docRequest( $gone[1] );
docSignIn( $goneWeb, 'admin@example.org' );
docSetupSeller( $goneWeb );

$keepClient = docClient( $goneWeb, [ 'name' => 'Stays Behind' ] );
$keepDoc = docFinal( $goneWeb, 'invoice', $keepClient );
$keepFile = \Nino\Filesystem::path( $goneWeb, $keepDoc['pdf_path'] ?? '/none' );
$keepHash = [ hash_file( 'sha256', $keepFile ), docRow( $goneWeb, (int) $keepDoc['id'] ) ];
$goneProject = $gone[0];

\Nino\Features::deactivate( $goneProject, 'crm-documents' );
$offWeb = docRequest( $gone[1] );
docSignIn( $offWeb, 'admin@example.org' );

check( 'with the package switched off it registers nothing: its actions are unknown, its routes are none, its settings are no keys, and nothing of it is a warning', docAnswer( $offWeb, 'crm-documents/list' ) === '404 action' && docGet( $offWeb, '/.crm/pdf/'. $keepDoc['id'] )[0] === 404 && docGet( $offWeb, '/crm/export/documents.csv?year='. $year )[0] === 404
	&& array_key_exists( 'iban', \Nino\Modules\Crm\Settings::defaults( $offWeb ) ) === false && ninoWarnings() === [] && isset( $offWeb['./crm/sequences/categories']['invoice'] ) === false );
check( 'its data stays where it was and the core knows it as the data of a package that is off, which comes back with it', array_column( \Nino\Modules\Crm\Db::orphans( $offWeb ), 'installed', 'package' ) === [ 'crm-documents' => true ] && docRow( $offWeb, (int) $keepDoc['id'] ) === $keepHash[1] && hash_file( 'sha256', $keepFile ) === $keepHash[0] );

$offDelete = docPost( $offWeb, 'crm-clients/delete', [ 'id' => $keepClient, 'confirm' => 'K-0001' ] );
$offWipe = docPost( $offWeb, 'crm/wipe', [ 'confirm' => \Nino\Modules\Crm\Wipe::word( $offWeb ) ] );

check( 'a client is not deleted, and the test phase does not run, while the data of the package lies in the database without the package: the answer says so, and nothing is deleted', $offDelete[0] === 409 && in_array( $offDelete[1]['code'] ?? '', [ 'crm_package_off', 'crm_package_data' ], true ) === true && $offWipe[0] === 409 && in_array( $offWipe[1]['code'] ?? '', [ 'crm_package_off', 'crm_package_data' ], true ) === true
	&& docRow( $offWeb, (int) $keepDoc['id'] ) === $keepHash[1] && is_file( $keepFile ) === true && \Nino\Modules\Crm\Clients::get( $offWeb, $keepClient ) !== null );

check( 'the package switched on again finds its data as it was', \Nino\Features::activate( $goneProject, 'crm-documents' ) === true && ( static function() use ( $gone, $keepDoc, $keepHash ): bool { $again = docRequest( $gone[1] ); docSignIn( $again, 'admin@example.org' ); return docRow( $again, (int) $keepDoc['id'] ) === $keepHash[1] && Documents::get( $again, (int) $keepDoc['id'] )['number'] === $keepDoc['number']; } )() );

\Nino\Features::deactivate( $goneProject, 'crm-documents' );
$removed = \Nino\Features::remove( $goneProject, 'crm-documents' );
$goneWeb = docRequest( $gone[1] );
docSignIn( $goneWeb, 'admin@example.org' );
ninoWarnings();

check( 'the package removed: the folder is gone and the data is listed as that of a package that is not installed, with its two tables', $removed === true && \Nino\Features::get( $goneProject, 'crm-documents' ) === null && \Nino\Modules\Crm\Db::orphans( $goneWeb ) === [ [ 'package' => 'crm-documents', 'version' => 1, 'tables' => [ 'documents', 'document_lines' ], 'installed' => false ] ] );

$goneDelete = docPost( $goneWeb, 'crm-clients/delete', [ 'id' => $keepClient, 'confirm' => \Nino\Modules\Crm\Clients::get( $goneWeb, $keepClient )['number'] ] );
$goneWipe = docPost( $goneWeb, 'crm/wipe', [ 'confirm' => \Nino\Modules\Crm\Wipe::word( $goneWeb ) ] );

check( 'a client whose documents are of a package that is removed is not deleted and the test phase does not run: 409 with the code of the core for data that has no package, and nothing is deleted - not the rows, not the file', $goneDelete[0] === 409 && ( $goneDelete[1]['code'] ?? '' ) === 'crm_package_data' && $goneWipe[0] === 409 && ( $goneWipe[1]['code'] ?? '' ) === 'crm_package_data'
	&& Db::row( $goneWeb, 'SELECT COUNT(*) AS n FROM documents' )['n'] === 1 && docRow( $goneWeb, (int) $keepDoc['id'] ) === $keepHash[1] && hash_file( 'sha256', $keepFile ) === $keepHash[0] && \Nino\Modules\Crm\Clients::get( $goneWeb, $keepClient ) !== null );
check( 'what a package that is removed leaves is no address: the file is no route of anybody, to the owner either', docGet( $goneWeb, '/.crm/pdf/'. $keepDoc['id'] )[0] === 404 && docGet( $goneWeb, '/data/crm/pdf/'. $year. '/'. $keepDoc['number']. '.pdf' )[0] === 404 );

$status = docBody( $goneWeb, 'crm/status' );

check( 'the status of the core lists the data of the removed package with its tables, and is not whole while it is there', ( $status['orphans'][0]['package'] ?? '' ) === 'crm-documents' && ( $status['orphans'][0]['tables'] ?? 0 ) === 2 );

// The core off: the package asks first, and registers nothing
\Nino\Features::deactivate( $gone[0], 'crm' );
ninoWarnings();
$coreOff = docRequest( $gone[1] );

check( 'with the core switched off the package, switched on or not, registers nothing and warns of nothing', \Nino\Modules\Crm::featureOn( $coreOff, 'crm' ) === false && isset( $coreOff['./crm/db/schemas'] ) === false && isset( $coreOff['./crm/settings/keys'] ) === false && isset( $coreOff['/nino/http/routes']['GET://.crm/pdf/*'] ) === false && ninoWarnings() === [] );

ninoDone( $web );
