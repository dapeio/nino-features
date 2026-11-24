<?php
declare(strict_types=1);

/**
 *	Nino features
 *	members-smoke.php	The test of the feature Members: the manifest and the activation, the four components and the two pages of the
 *										sign-in, the panel's actions and the permission and the token each of them needs, the request for a code (the same
 *										answer for every address, a cap for the visitor and one for the address), the code that signs in - bound to the
 *										session that asked, five tries, once, in time -, the link that opens a page which asks and the button that signs
 *										in and spends it, the gate in front of the protected paths - at every address a route gives them and in every
 *										spelling of it, and with nothing of the route in its answer -, the sign-out and the epoch that signs everybody
 *										out, the change of an address, the devices that stay signed in, the callbacks other features build on - the new
 *										'/members/removed' among them -, the data the source wrote read and written back byte for byte, and - where
 *										node is on the path - the panel script's own test. One pin for each behaviour, over the structure and never the
 *										words. It travels with the feature and runs against the checkout three levels up, or the one NINO_ROOT names.
 *
 *	Usage: php features/Members/tests/members-smoke.php
 *	       NINO_ROOT=../nino php features/Members/tests/members-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

/*	Worker mode: this same file re-entered as a process of its own, pointed at a sandbox somebody else built. A request that is over before the process ends is only seen
	from a second process, and so is a write that comes between two others: flock() is per open file description, so one process proves nothing about it	*/
if( in_array( $argv[1] ?? '', [ 'drop-worker', 'both-worker', 'hold-worker', 'link-worker', 'nobody-worker' ], true ) === true ) {

	$worker = [ './nino/uid' => $argv[2] ];
	\Nino\AppData::prepare( $worker );
	$worker['./nino/filesystem/path']					= $argv[2];
	$worker['./nino/filesystem/configpath']		= $argv[2]. '/private';
	$worker['./nino/filesystem/contentpath']	= $argv[2]. '/private';
	$worker['./nino/filesystem/publicpath']		= $argv[2]. '/public';
	\Nino\AppData::init( $worker );
	$worker['/nino/dir'] = '';
	$_SERVER['REMOTE_ADDR'] = $argv[3];
	\Nino\Html::addFills( $worker, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club' ], '*' );

	// The server cannot let the client go before the work: a length somebody else set. The answer is finished, the kernel is asked what it will run after it - and the
	// process ends, as the request does. The mail has to be out by then. 'both-worker' is the server that can: the kernel hands the work back, it is run, and the process
	// ends as the request does - the shutdown finds nothing left. The addresses ask one after another (a comma between them); the transport throws for $argv[6], and what
	// is reported as a warning is written to $argv[7]
	if( $argv[1] === 'drop-worker' || $argv[1] === 'both-worker' ) {

		set_error_handler( static function( int $level, string $message ) use ( $argv ): bool {
			file_put_contents( $argv[7], $message. "\n", FILE_APPEND );
			return true;
		} );
		\Nino\Callbacks::registerCallback( $worker, \Nino\Mail::TRANSPORT, static function( array &$worker, array &$mail ) use ( $argv ): void {

			if( ( $mail['to'] ?? '' ) === $argv[6] )
				throw new \RuntimeException( 'the transport of '. $argv[6]. ' is down' );

			file_put_contents( $argv[4], json_encode( [ 'to' => $mail['to'] ?? '' ] ). "\n", FILE_APPEND );
			$mail['sent'] = true;
		} );
		\Nino\Modules\Members::init( $worker );
		$asked = 0;

		foreach( explode( ',', $argv[5] ) as $email ) {
			$_POST = [ 'email' => $email, 'return' => '/join' ];
			$request = ninoVisit( $worker, '/.members/login', 'POST' );
			\Nino\Modules\Members::callbackLoginRequest( $worker, $request );
			$asked++;
		}

		if( $argv[1] === 'drop-worker' )
			$request['/nino/http/response']['header']['Content-Length'] = '0';

		\Nino\Callbacks::doCallbacks( $worker, '/nino/http/output', $request );
		$after = \Nino\Http::prepareAfter( $worker, $request );

		foreach( $after as $work )
			$work();

		echo count( $after );
		exit( 0 );
	}

	// A process that may read the files and take the locks, and may not write a file of the data: what is read is written back only where the write is made. It deletes
	// an account and a group, and says what was answered and what was told
	if( $argv[1] === 'nobody-worker' ) {

		$told = [];
		\Nino\Callbacks::registerCallback( $worker, '/members/removed', static function( array &$worker, array &$account ) use ( &$told ): void { $told[] = $account; } );
		// Root gives up its rights; a process that has none to give up is read-only by the files that the test made so
		if( function_exists( 'posix_setuid' ) === true && posix_getuid() === 0 ) {
			posix_setgid( 65534 );
			posix_setuid( 65534 );
		}

		echo json_encode( [ \Nino\Modules\Members::remove( $worker, $argv[4] ), \Nino\Modules\Members::removeGroups( $worker, static fn( string $group ): bool => $group === $argv[5] ), $told ] );
		exit( 0 );
	}

	// Holds the lock of the accounts for a while, and says when it has it
	if( $argv[1] === 'hold-worker' ) {
		\Nino\Filesystem::mutate( $worker, \Nino\Modules\Members::ACCOUNTS, static function() use ( $argv ): ?array {
			file_put_contents( $argv[4], '1' );
			usleep( 700000 );
			return null;
		}, [] );
		exit( 0 );
	}

	// Makes a link for the account while the lock is held: a moment after it was taken
	while( is_file( $argv[4] ) === false )
		usleep( 2000 );

	usleep( 250000 );
	echo json_encode( \Nino\Modules\Members::link( $worker, [ 'id' => $argv[5], 'email' => $argv[6] ], 'login', '', true, false ) !== false );
	exit( 0 );
}

$dir = dirname( __DIR__ );
$appData = ninoSandbox( 'members' );
$appData['./nino/filesystem/path'] = $root;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// The project directory and its fill, which \Nino\request() registers from the same key on every request - empty here, the way a site at the
// domain root has it - and the website's address, which every link of a mail is built on (\Nino\Html::absoluteUrl()): a site served from a
// directory, so that the directory is seen to be kept in them
$appData['/nino/dir'] = '';
\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club' ], '*' );
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

// The workbench's entry point, which ninoCall() hands a panel action
$panel = [ \Nino\Admin\Admin::class, 'handlePost' ];

/*	A transport takes the mail so nothing is actually sent - the same
	\Nino\Mail::TRANSPORT callback the Mailer feature registers. $delivery is what
	the stub says about the mail, so that a refusal is as easy to make as a delivery	*/
$mails = [];
$delivery = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$delivery ): void {
	// A transport that costs time, as an SMTP server does
	usleep( (int) ( $GLOBALS['membersDelay'] ?? 0 ) );
	$mails[] = $mail;
	$mail['sent'] = $delivery;
} );

// What the features that build on this one are told: the account of a sign-in and of a sign-out
$told = [];
\Nino\Callbacks::registerCallback( $appData, '/members/login', static function( array &$appData, array &$account ) use ( &$told ): void {
	$told[] = [ 'login', $account ];
} );
\Nino\Callbacks::registerCallback( $appData, '/members/logout', static function( array &$appData, array &$account ) use ( &$told ): void {
	$told[] = [ 'logout', $account ];
} );

membersOnceCheck( $appData );

/**
 *	A request of the site the way the web server hands it over, answered by the
 *	routes and the callbacks that are registered: the request built, the
 *	global callbacks of the response run - the csrf guard and the gate among them -
 *	and then the route's own. A post carries the csrf token of the session unless
 *	$csrf is false. Nothing is rendered
 *
 *	@param		array 		&$appData
 *	@param		string		$uri
 *	@param		string		$method
 *	@param		array 		$post
 *	@param		bool			$csrf					Whether a post carries the token
 *
 *	@return 	array										The request, response and all
 */
function membersServe( array &$appData, string $uri, string $method = 'GET', array $post = [], bool $csrf = true ): array {

	// What a request keeps for itself alone is gone before the next one begins
	unset( $appData['./members/state'], $appData['./members/forms'], $appData['./members/confirm'] );

	$_POST = $post + ( $method === 'POST' && $csrf === true ? [ '_csrf' => \Nino\Csrf::getToken( $appData ) ] : [] );
	$request = ninoVisit( $appData, $uri, $method );
	$started = hrtime( true );
	\Nino\Http::response( $appData, $request );
	$answered = hrtime( true );

	// What \Nino\Http::output() does once the answer is finished: it asks which work may run after it, and runs that once the client is let go. The time
	// of the answer and of that work are kept apart: a visitor waits for the first
	foreach( \Nino\Http::prepareAfter( $appData, $request ) as $work )
		$work();

	$GLOBALS['membersTimes'] = [ ( $answered - $started ) / 1e6, ( hrtime( true ) - $answered ) / 1e6 ];
	$_POST = [];

	return $request;
}

/**
 *	What a response says in the four places a visitor and a test read it: the
 *	status, the Location and Cache-Control headers and the body
 *
 *	@param		array 		$request
 *
 *	@return 	array										[ status, location, cache-control, body ]
 */
function membersAnswer( array $request ): array {

	$response = $request['/nino/http/response'];

	return [ $response['statusCode'], (string) ( $response['header']['Location'] ?? '' ), (string) ( $response['header']['Cache-Control'] ?? '' ), $response['body'] ?? '' ];
}

/**
 *	The data files of the feature as they are on disk, read fresh
 *
 *	@param		array 		&$appData
 *	@param		string		$name					accounts, tokens or session
 *
 *	@return 	array
 */
function membersFile( array &$appData, string $name ): array {

	$file = [ 'accounts' => '/data/members.php', 'tokens' => '/data/members-tokens.php', 'session' => '/data/members-session.php', 'tries' => '/data/members-tries.php' ][$name];
	$state = \Nino\Filesystem::getFileContent( $appData, $file, [] );

	return is_array( $state ) === true ? $state : [];
}

/**
 *	A new window for every cap: the visitor's and the address's counter of the
 *	feature and the mail cap of the kernel, which counts the same client
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersFresh( array &$appData ): void {

	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', [] );
}

/**
 *	The id of the account with an address
 *
 *	@param		array 		&$appData
 *	@param		string		$email
 *
 *	@return 	string										'' for an address nobody has
 */
function membersIdOf( array &$appData, string $email ): string {

	return (string) ( array_column( \Nino\Modules\Members::accounts( $appData ), 'id', 'email' )[$email] ?? '' );
}

/**
 *	Send an account a link and hand over its token, the way a member holds it: out of
 *	the mail. Every cap starts afresh
 *
 *	@param		array 		&$appData
 *	@param		array 		$account
 *	@param		string		$kind					login or invite
 *	@param		string		$target				The path the link leads to
 *
 *	@return 	string										'' where no mail went out
 */
function membersLink( array &$appData, array $account, string $kind = 'login', string $target = '' ): string {

	global $mails;

	membersFresh( $appData );
	$count = count( $mails );

	return \Nino\Modules\Members::link( $appData, $account, $kind, $target ) === true && count( $mails ) === $count + 1 ? membersTokenOf( $mails[$count] ) : '';
}

/**
 *	Follow a link and press the button of the page it opens: the page is asked for, so that the session
 *	holds the csrf token the page's form carries, and the token is posted with that one
 *
 *	@param		array 		&$appData
 *	@param		string		$token
 *
 *	@return 	array										The answer to the button
 */
function membersConfirm( array &$appData, string $token ): array {

	membersServe( $appData, '/.members/login?t='. $token );

	return membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $token ] ) );
}

/**
 *	Sign an account in the way a member does: ask for a link, follow it and press the button
 *
 *	@param		array 		&$appData
 *	@param		array 		$account
 *	@param		string		$target				The path the link leads to
 *
 *	@return 	array										The answer to the button
 */
function membersSignIn( array &$appData, array $account, string $target = '' ): array {

	return membersConfirm( $appData, membersLink( $appData, $account, 'login', $target ) );
}

/**
 *	What a request of a member leaves for after its answer, done: as \Nino\Http::output() would, for a call of askChange() that no request carries
 *
 *	@param		array 		&$appData
 *	@param		string		$answer				What askChange() answered
 *
 *	@return 	string										The same
 */
function membersAfter( array &$appData, string $answer ): string {

	$request = [ '/nino/http/response' => [ 'body' => '', 'statusCode' => 303, 'header' => [] ] ];

	foreach( \Nino\Http::prepareAfter( $appData, $request ) as $work )
		$work();

	return $answer;
}

/**
 *	A visitor who has never been here: no session, nobody signed in
 *
 *	@return 	void
 */
function membersVisitor(): void {

	$_SESSION = [];
}

/**
 *	The token a mail carries: the value of t in the link it holds, as an address a
 *	visitor can follow - the path and the query, with the website's directory off
 *
 *	@param		array 		$mail
 *
 *	@return 	string										'' for a mail without a link
 */
function membersTokenOf( array $mail ): string {

	return preg_match( '~href="https://example\.org/club/\.members/login\?t=([A-Za-z0-9_-]+)"~', (string) ( $mail['body'] ?? '' ), $found ) === 1 ? $found[1] : '';
}

/**
 *	The code a mail carries, as it is set: two groups of three digits
 *
 *	@param		array 		$mail
 *
 *	@return 	string										'' for a mail without one
 */
function membersCodeOf( array $mail ): string {

	return preg_match( '~>\s*([0-9]{3} [0-9]{3})\s*</p>~', (string) ( $mail['body'] ?? '' ), $found ) === 1 ? $found[1] : '';
}

/**
 *	The request for a code the session of the visitor holds
 *
 *	@param		array 		&$appData
 *
 *	@return 	array|null								email, nonce, tries and until - null for none
 */
function membersPending( array &$appData ): ?array {

	$pending = \Nino\Runtime::getSessionValue( $appData, './members/pending' );

	return is_array( $pending ) === true ? $pending : null;
}

/**
 *	Ask for a code the way a visitor does: the form is posted, every cap starts afresh, and the digits are read
 *	out of the mail
 *
 *	@param		array 		&$appData
 *	@param		string		$email
 *	@param		string		$return				The page the form is on
 *
 *	@return 	string										The six digits; '' where no mail went out
 */
function membersAskCode( array &$appData, string $email, string $return = '/join' ): string {

	global $mails;

	membersFresh( $appData );
	$count = count( $mails );
	membersServe( $appData, '/.members/login', 'POST', [ 'email' => $email, 'return' => $return ] );

	return count( $mails ) === $count + 1 ? str_replace( ' ', '', membersCodeOf( $mails[$count] ) ) : '';
}

/**
 *	Post a code the way the second step of the form does
 *
 *	@param		array 		&$appData
 *	@param		string		$code					As typed
 *	@param		string		$return				The page the form is on
 *	@param		bool			$csrf					Whether the post carries the token
 *
 *	@return 	array										The answer, as membersAnswer() has it
 */
function membersCodeServe( array &$appData, string $code, string $return = '/join', bool $csrf = true ): array {

	return membersAnswer( membersServe( $appData, '/.members/code', 'POST', [ 'code' => $code, 'return' => $return ], $csrf ) );
}

/**
 *	The elements of a rendered piece of html by xpath, as a document reads them
 *
 *	@param		string		$html
 *	@param		string		$query
 *
 *	@return 	array										The DOMElements
 */
function membersFind( string $html, string $query ): array {

	$document = new DOMDocument();
	libxml_use_internal_errors( true );
	$document->loadHTML( '<?xml encoding="UTF-8"><body>'. $html. '</body>' );
	libxml_clear_errors();

	return iterator_to_array( ( new DOMXPath( $document ) )->query( $query ) ?: [] );
}

/**
 *	The text of the first element a query finds in a piece of html
 *
 *	@param		string		$html
 *	@param		string		$query
 *
 *	@return 	string|null								Trimmed; null where the query finds nothing
 */
function membersText( string $html, string $query ): ?string {

	$found = membersFind( $html, $query );

	return $found === [] ? null : trim( $found[0]->textContent );
}

/**
 *	@param		DOMElement	$element
 *
 *	@return 	array										The classes of the element
 */
function membersClasses( DOMElement $element ): array {

	return array_values( array_filter( preg_split( '/\s+/', $element->getAttribute( 'class' ) ) ?: [] ) );
}


// What is told of an account that is gone: the id, the address and the group, once for each
$removed = [];
\Nino\Callbacks::registerCallback( $appData, '/members/removed', static function( array &$appData, array &$account ) use ( &$removed ): void {
	$removed[] = $account;
} );

// --- The feature - manifest and activation -------------------------------------

echo "The feature - manifest and activation\n";

$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "members", written for this kernel, and without a warning', is_array( $manifest ) && $manifest['key'] === 'members' && ninoWarnings() === []
	&& \Nino\Features::satisfies( (string) $manifest['nino'] ) === true && isset( $manifest['components'] ) === false && isset( $manifest['stacks'] ) === false );
check( 'key, class and version are what the directory and the plan say: category security, requires Basis, version 2.0.0', is_array( $manifest ) && $manifest['module'] === '\\Nino\\Modules\\Members' && $manifest['version'] === '2.0.0' && $manifest['category'] === 'security'
	&& $manifest['nino'] === '^2.0' && $manifest['requires'] === [ 'basis' ] );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it owns the three files of the accounts, the links and the epoch - and not the counter, which is an hour long', is_array( $manifest ) && $manifest['data'] === [ '/data/members.php', '/data/members-tokens.php', '/data/members-session.php' ] );
check( 'the settings are the six of the plan, each with its default and its bounds', is_array( $manifest ) && array_keys( $manifest['settings'] ) === [ 'paths', 'start', 'linkMinutes', 'inviteDays', 'attempts', 'rememberDays' ]
	&& $manifest['settings']['paths']['type'] === 'lines' && $manifest['settings']['start']['default'] === '/'
	&& [ $manifest['settings']['linkMinutes']['default'], $manifest['settings']['linkMinutes']['min'], $manifest['settings']['linkMinutes']['max'] ] === [ 15, 5, 1440 ]
	&& [ $manifest['settings']['inviteDays']['default'], $manifest['settings']['inviteDays']['min'], $manifest['settings']['inviteDays']['max'] ] === [ 7, 1, 30 ]
	&& [ $manifest['settings']['attempts']['default'], $manifest['settings']['attempts']['min'], $manifest['settings']['attempts']['max'] ] === [ 5, 1, 50 ]
	&& [ $manifest['settings']['rememberDays']['default'], $manifest['settings']['rememberDays']['min'], $manifest['settings']['rememberDays']['max'] ] === [ 90, 1, 365 ] );
check( 'its manual says all six sections in both languages, and its section of the privacy policy as a starting point', is_array( $manifest ) && array_keys( $manifest['manual'] ) === [ 'shortcodes', 'markup', 'routes', 'panel', 'callbacks', 'install' ] && (function() use ( $manifest ): bool {
	foreach( \Nino\Features::MANUAL_SECTIONS as $section )
		foreach( (array) ( $manifest['manual'][$section] ?? [] ) as $line )
			if( is_array( $line ) === false || trim( (string) ( $line['en_US'] ?? '' ) ) === '' || trim( (string) ( $line['de_DE'] ?? '' ) ) === '' )
				return false;
	return isset( $manifest['manual']['install']['elements/privacy.php'] ) === true && count( $manifest['manual']['shortcodes'] ) >= 4 && count( $manifest['manual']['routes'] ) === 5
		&& array_diff( [ '/members/login', '/members/logout', '/members/start', '/members/login-form', '/members/open', '/members/changed', '/members/removed', '/members/language' ], array_keys( $manifest['manual']['callbacks'] ) ) === [];
} )() );

// The catalogue's preview reads every manifest through the kernel, and is a script of the catalogue: it is there when this test runs in the
// repository, not when the feature has been dropped into a project - bin/check.sh and the CI run it as a step of their own
$preview = dirname( __DIR__, 3 ). '/bin/catalogue.php';

if( is_file( $preview ) === false )
	echo "  note - no bin/catalogue.php beside this feature: the catalogue's preview was not asked\n";
else {
	$listed = json_decode( (string) shell_exec( escapeshellarg( PHP_BINARY ). ' '. escapeshellarg( $preview ). ' '. escapeshellarg( $root ). ' 2>/dev/null' ), true );
	check( 'the catalogue preview lists it, with its category, its version and Basis as what it requires', is_array( $listed ) && count( array_filter( (array) ( $listed['packages'] ?? $listed['features'] ?? [] ), static fn( mixed $entry ): bool => is_array( $entry ) === true
		&& ( $entry['key'] ?? '' ) === 'members' && ( $entry['category'] ?? '' ) === 'security' && ( $entry['version'] ?? '' ) === '2.0.0' && in_array( 'basis', (array) ( $entry['requires']['features'] ?? $entry['requires'] ?? [] ), true ) ) ) === 1 );
}

$privacyType = $root. '/_nino/Nino/Modules/Legal';

// The Legal module's type is what the feature's section is added to: put there first, where the kernel has the module
if( class_exists( '\\Nino\\Modules\\Legal' ) === true ) {
	$routes = [];
	$blacklist = [];
	$config = [];
	\Nino\Features::applyUnit( $appData, $privacyType. '/install', [ 'de_DE', 'en_US' ], $routes, $blacklist, $config, true );
}

ninoWarnings();
check( 'the registry lists it, inactive, with nothing in the way', ( \Nino\Features::get( $appData, 'members' )['active'] ?? null ) === false && ( \Nino\Features::get( $appData, 'members' )['problems'] ?? null ) === [] );
check( 'activation succeeds, Basis first - the pages are made of its components', \Nino\Features::activate( $appData, 'members' ) === true && \Nino\Features::get( $appData, 'basis' )['active'] === true );
check( 'the class is listed in /nino/modules, and the version is recorded', in_array( '\\Nino\\Modules\\Members', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'members' )['installed'] === '2.0.0' );
check( 'the unit copied the login page and the confirm page, and merged its words into both languages', is_file( \Nino\Filesystem::path( $appData, '/templates/page-members-login.tpl' ) ) === true && is_file( \Nino\Filesystem::path( $appData, '/templates/page-members-confirm.tpl' ) ) === true
	&& array_filter( [ 'de_DE', 'en_US' ], static fn( string $locale ): bool => trim( (string) ( \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] )['[[/feature/members/login/request]]'] ?? '' ) ) === '' ) === [] );

// The words of the unit: the same keys in both languages, none empty, and no more and no fewer than the templates and the class read
$unitWords = [ 'de_DE' => (array) include $dir. '/install/text/de_DE.php', 'en_US' => (array) include $dir. '/install/text/en_US.php' ];
$read = [];

foreach( array_merge( glob( $dir. '/templates/*.tpl' ) ?: [], glob( $dir. '/components/*/template.tpl' ) ?: [], glob( $dir. '/install/templates/*.tpl' ) ?: [] ) as $template ) {

	preg_match_all( '~\[\[(/(?:feature|template)/[a-z0-9-]+/[a-z0-9-]+/[a-z0-9-]+)\]\]|\[(?:section-title|text) (/template/[a-z0-9/-]+)~', (string) file_get_contents( $template ), $keys, PREG_SET_ORDER );

	foreach( $keys as $key )
		$read[] = '[['. ( $key[1] !== '' ? $key[1] : $key[2] ). ']]';
}

$stateKeys = ( new ReflectionClassConstant( \Nino\Modules\Members::class, 'STATE_KEYS' ) )->getValue();

foreach( ( new ReflectionClassConstant( \Nino\Modules\Members::class, 'STATES' ) )->getValue() as $state )
	$read[] = '[[/feature/members/state/'. ( $stateKeys[$state] ?? $state ). ']]';

foreach( [ 'login', 'invite', 'send', 'code', 'change', 'changed' ] as $kind )
	$read[] = '[[/feature/members/mail/'. $kind. '-subject]]';

foreach( [ 'name', 'title', 'description' ] as $detail )
	$read[] = '[[/_nino/webpage/.members/'. $detail. ']]';

$read = array_values( array_unique( $read ) );
sort( $read );
$delivered = array_keys( $unitWords['de_DE'] );
sort( $delivered );
check( 'the words of the unit are the same keys in German and English, none of them empty, and the keys the templates and the class read - every one delivered and none left over',
	array_keys( $unitWords['de_DE'] ) === array_keys( $unitWords['en_US'] ) && array_filter( $unitWords['de_DE'] + $unitWords['en_US'], static fn( mixed $text ): bool => trim( (string) $text ) === '' ) === [] && $read === $delivered );
check( 'no word of the unit holds a place nobody fills: the source named the owner in curly brackets, which no feature of this catalogue puts in', array_filter( array_merge( array_values( $unitWords['de_DE'] ), array_values( $unitWords['en_US'] ) ), static fn( mixed $text ): bool => preg_match( '/\{[a-z]+\}/', (string) $text ) === 1 ) === [] );

// Add-only: a copy the project changed stays when the feature is activated again
$pageFile = \Nino\Filesystem::path( $appData, '/templates/page-members-login.tpl' );
file_put_contents( $pageFile, "<!-- the project's own -->\n". (string) file_get_contents( $pageFile ) );
$changedPage = (string) file_get_contents( $pageFile );
unset( $appData['./nino/features/all'] );
check( 'activating again leaves a copy of the page the project changed as it is', \Nino\Features::activate( $appData, 'members' ) === true && (string) file_get_contents( $pageFile ) === $changedPage );
file_put_contents( $pageFile, (string) file_get_contents( $dir. '/install/templates/page-members-login.tpl' ) );

if( class_exists( '\\Nino\\Modules\\Legal' ) === false )
	echo "  note - this Nino has no \\Nino\\Modules\\Legal: the section of the privacy policy was not looked for\n";
else {
	$privacy = \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] );
	check( 'the section of the privacy policy is there in both languages, at its position', isset( $privacy['de_DE']['members']['title'], $privacy['en_US']['members']['text'] ) === true && ( $privacy['*']['members']['order'] ?? null ) === 830 );
	$named = static fn( string $text, int $number ): bool => preg_match( '/(?<![0-9])'. $number. '(?![0-9])/', $text ) === 1;
	check( '...it names the minutes a code is valid, the tries it has and the days of an invitation the settings and the class give by default - and carries no ampersand, no entity and no bracket', array_filter( [ 'de_DE', 'en_US' ], static fn( string $locale ): bool => preg_match( '/[&\[\]]/', (string) $privacy[$locale]['members']['text'] ) === 1
		|| $named( (string) $privacy[$locale]['members']['text'], $manifest['settings']['linkMinutes']['default'] ) === false || $named( (string) $privacy[$locale]['members']['text'], $manifest['settings']['inviteDays']['default'] ) === false
		|| $named( (string) $privacy[$locale]['members']['text'], ( new ReflectionClassConstant( \Nino\Modules\Members::class, 'CODE_TRIES' ) )->getValue() ) === false ) === [] );

	check( '...and the cookie of a device that stays signed in and the days the setting gives it by default, in both languages', array_filter( [ 'de_DE', 'en_US' ], static fn( string $locale ): bool => str_contains( (string) $privacy[$locale]['members']['text'], 'nino_remember' ) === false
		|| $named( (string) $privacy[$locale]['members']['text'], $manifest['settings']['rememberDays']['default'] ) === false || $named( (string) $privacy[$locale]['members']['text'], ( new ReflectionClassConstant( \Nino\Modules\Members::class, 'REMEMBER_MAX' ) )->getValue() ) === false ) === [] );

	$privacy['en_US']['members']['text'] = '<p>The editor changed this.</p>';
	\Nino\Filesystem::putFileContent( $appData, '/elements/privacy.php', $privacy );
	unset( $appData['./nino/features/all'] );
	\Nino\Features::activate( $appData, 'members' );
	check( 'a second activation changes nothing of it - a section the editor changed stays', \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] ) === $privacy );
}

// The kernel boots as \Nino\init() does it: the register reads the folders of the features that are on, then the modules run, then the parts are read
$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
$appData['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Csrf' ], (array) ( $stored['/nino/modules'] ?? [] ) );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Csrf::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
$ignored = ninoWarnings();

echo "\nThe components - registry, and what a visitor is shown\n";

check( 'the folders are read without a reason or a warning', \Nino\Features::componentProblems( $appData, 'members' ) === [] && $ignored === [] );
$schemas = array_intersect_key( array_replace( array_flip( [ 'members-login', 'members-logout', 'members-name', 'members-confirm' ] ), \Nino\Components::components( $appData ) ), array_flip( [ 'members-login', 'members-logout', 'members-name', 'members-confirm' ] ) );
check( 'the registry knows the four, in their group, labelled in both languages, each with an icon of the table and a picture of its own, and the group has a standard part and a demo',
	array_keys( $schemas ) === [ 'members-login', 'members-logout', 'members-name', 'members-confirm' ] && array_unique( array_column( $schemas, 'group' ) ) === [ 'members' ]
	&& array_filter( $schemas, static fn( array $schema ): bool => isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === false || in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === false ) === []
	&& array_values( array_column( $schemas, 'preview' ) ) === [ 'block', 'button', 'text', 'block' ]
	&& ( \Nino\Components::groups( $appData )['members']['part'] ?? false ) === true && trim( (string) ( \Nino\Components::groups( $appData )['members']['demo'] ?? '' ) ) !== '' );
check( 'the standard part keeps the rule of mixing: its own tokens and classes, the tokens of the brand and of the page and nothing else', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/members/part/part.css' ), [ 'members-login', 'members-logout', 'members-name', 'members-confirm' ] ) === [] );
check( 'a tool offers the form and the button of the confirmation in a column, and the sign-out and the name in a column, the header actions and a footer column',
	array_filter( [ 'members-login', 'members-confirm' ], static fn( string $name ): bool => in_array( $name, \Nino\Components::allowed( $appData, 'section-col' ), true ) === false ) === []
	&& array_filter( [ 'members-logout', 'members-name' ], static fn( string $name ): bool => array_filter( [ 'section-col', 'header-actions', 'footer-col' ], static fn( string $place ): bool => in_array( $name, \Nino\Components::allowed( $appData, $place ), true ) === false ) !== [] ) === [] );
check( '...only the form has an attribute: whether it stands in one line, off by default', array_keys( $schemas['members-login']['attributes'] ) === [ 'inline' ] && $schemas['members-login']['attributes']['inline']['type'] === 'bool'
	&& $schemas['members-login']['attributes']['inline']['default'] === false && array_filter( [ 'members-logout', 'members-name', 'members-confirm' ], static fn( string $name ): bool => $schemas[$name]['attributes'] !== [] ) === [] );
check( '...and init registers no shortcode of its own for them: the register draws them, and the one plain shortcode is the place of the band of a mail', in_array( 'members-login', \Nino\Html::shortcodes( $appData ), true ) === false
	&& array_intersect( [ 'members-logout', 'members-name', 'members-confirm' ], \Nino\Html::shortcodes( $appData ) ) === [] && in_array( 'mail-head', \Nino\Html::shortcodes( $appData ), true ) === true );

// The page the visitor is on, as the form reads it: the query decides what it says, the path is where it goes back to
$draw = static function( string $source ) use ( &$appData ): string { return trim( \Nino\Html::renderHtml( $appData, $source ) ); };
ninoVisit( $appData, '/join?members=sent&x=1' );
$form = $draw( '[members-login]' );
$forms = membersFind( $form, '//form' );
$fields = membersFind( $form, '//form//input' );
$byName = [];

foreach( $fields as $field )
	$byName[ $field->getAttribute( 'name' ) ] = $field;

check( 'for a visitor [members-login] is one form that posts to /.members/login, with a field for the address, a button and no password',
	count( $forms ) === 1 && strtolower( $forms[0]->getAttribute( 'method' ) ) === 'post' && $forms[0]->getAttribute( 'action' ) === '/.members/login'
	&& count( membersFind( $form, '//form//button[@type="submit"]' ) ) === 1 && array_keys( $byName ) === [ 'return', '_csrf', 'email' ]
	&& $byName['email']->getAttribute( 'type' ) === 'email' && $byName['email']->hasAttribute( 'required' ) === true && $byName['email']->getAttribute( 'autocomplete' ) === 'email' );
check( '...it carries the session\'s csrf token, and the page it is on as the one the answer goes back to - the path without its query',
	$byName['_csrf']->getAttribute( 'type' ) === 'hidden' && $byName['_csrf']->getAttribute( 'value' ) === \Nino\Csrf::getToken( $appData )
	&& $byName['return']->getAttribute( 'type' ) === 'hidden' && $byName['return']->getAttribute( 'value' ) === '/join' );
check( '...its label belongs to the field', membersFind( $form, '//form//label' )[0]->getAttribute( 'for' ) === $byName['email']->getAttribute( 'id' ) && $byName['email']->getAttribute( 'id' ) !== '' );
check( '...and it is no .nino-form: the kernel\'s script posts such a form by XHR, which swallows the redirect its answer is', in_array( 'nino-form', membersClasses( $forms[0] ), true ) === false
	&& in_array( 'members-login', membersClasses( $forms[0] ), true ) === true && str_contains( $form, 'nino-btn' ) === false );
check( 'the query says what the form says above its button: a paragraph that is not empty and holds no key left unresolved', count( membersFind( $form, '//form/p[contains(concat(" ", normalize-space(@class), " "), " members-login-state ")]' ) ) === 1
	&& trim( membersFind( $form, '//form/p[@role="status"]' )[0]->textContent ) !== '' && str_contains( $form, '[[' ) === false );

$states = [];
$english = [];
$language = \Nino\Locales::getCurrentLocale( $appData );

// The two states of the second step are drawn where the session holds a request for a code, the others where it holds none
$messages = '//form/p[@role="status"]';

foreach( [ 'sent', 'wrong', 'limit', 'invalid', 'out', 'expired', 'gone' ] as $state ) {
	\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );

	if( in_array( $state, [ 'sent', 'wrong' ], true ) === true )
		\Nino\Runtime::setSessionValue( $appData, './members/pending', [ 'email' => 'ada@example.org', 'nonce' => str_repeat( 'a', 32 ), 'tries' => 0, 'until' => time() + 600 ] );

	ninoVisit( $appData, '/join?members='. $state );
	$states[$state] = membersFind( $draw( '[members-login]' ), $messages );
	\Nino\Locales::useLocale( $appData, 'en_US' );
	$english[$state] = membersFind( $draw( '[members-login]' ), $messages );
	\Nino\Locales::useLocale( $appData, $language );
}

\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );

// The language of the page is the one the words come in: a text a language has not got is shown in another, which the same words in two of them give away
check( '...each of the seven states has a message of its own, in the language of the page', array_filter( $states, static fn( array $found ): bool => count( $found ) !== 1 || trim( $found[0]->textContent ) === '' ) === []
	&& count( array_unique( array_map( static fn( array $found ): string => trim( $found[0]->textContent ), $states ) ) ) === 7 && $language !== 'en_US'
	&& array_filter( $states, static fn( array $found, string $state ): bool => count( $english[$state] ) !== 1 || trim( $english[$state][0]->textContent ) === trim( $found[0]->textContent ), ARRAY_FILTER_USE_BOTH ) === [] );
check( '...each says its state in a class beside the one of every message - the words of the key that names it, the code for the one that is sent - so that a page can draw one state in a tone and another in another',
	array_filter( $states, static fn( array $found, string $state ): bool => $found[0]->getAttribute( 'class' ) !== 'members-login-state members-login-state--'. ( $state === 'sent' ? 'code' : $state ), ARRAY_FILTER_USE_BOTH ) === [] );

$none = [];

foreach( [ '', '?members=', '?members=SENT', '?members=other', '?members[]=sent', '?members=sent%20', '?members=<b>x</b>' ] as $query ) {
	ninoVisit( $appData, '/join'. $query );
	$none[$query] = count( membersFind( $draw( '[members-login]' ), '//form/p' ) );
}

check( '...and anything else in the query, an array and a made-up word among it, is no state: there is no paragraph', array_filter( $none ) === [] );

// The second step: where the session holds a request for a code the form asks for it - the address is the one that was typed, escaped, and never a fill or a shortcode
$typed = '"<b>[members-name]"@example.org';
\Nino\Runtime::setSessionValue( $appData, './members/pending', [ 'email' => $typed, 'nonce' => str_repeat( 'b', 32 ), 'tries' => 0, 'until' => time() + 600 ] );
ninoVisit( $appData, '/join?members=sent&x=1' );
$step = $draw( '[members-login]' );
$stepForms = membersFind( $step, '//form' );
$stepFields = [];

foreach( membersFind( $step, '//form//input' ) as $field )
	$stepFields[ $field->getAttribute( 'name' ) ] = $field;

$stepCode = $stepFields['code'] ?? null;
$again = membersFind( $step, '//form/a[contains(concat(" ", normalize-space(@class), " "), " members-login-again ")]' );
check( 'where a code is out [members-login] is the second step: one form that posts to /.members/code, with a field for the code and no field for an address, the page it is on and the csrf token of the session, a button and a link back to the page - and no .nino-form',
	count( $stepForms ) === 1 && strtolower( $stepForms[0]->getAttribute( 'method' ) ) === 'post' && $stepForms[0]->getAttribute( 'action' ) === '/.members/code' && membersClasses( $stepForms[0] ) === [ 'members-login', 'members-login--code' ]
	&& array_keys( $stepFields ) === [ 'return', '_csrf', 'code', 'remember' ] && $stepFields['return']->getAttribute( 'value' ) === '/join' && $stepFields['_csrf']->getAttribute( 'value' ) === \Nino\Csrf::getToken( $appData )
	&& count( membersFind( $step, '//form//button[@type="submit"]' ) ) === 1 && count( $again ) === 1 && $again[0]->getAttribute( 'href' ) === '/join' );
check( '...its field takes six digits the way a phone offers them: text with a numeric keypad, the one-time-code hint, a pattern and a length that let a space through, focused, required - and its label belongs to it',
	$stepCode !== null && $stepCode->getAttribute( 'type' ) === 'text' && $stepCode->getAttribute( 'inputmode' ) === 'numeric' && $stepCode->getAttribute( 'autocomplete' ) === 'one-time-code' && $stepCode->getAttribute( 'pattern' ) === '[0-9 ]*'
	&& $stepCode->getAttribute( 'maxlength' ) === '7' && $stepCode->hasAttribute( 'autofocus' ) === true && $stepCode->hasAttribute( 'required' ) === true && in_array( 'members-login-code', membersClasses( $stepCode ), true ) === true
	&& $stepCode->getAttribute( 'id' ) !== '' && membersFind( $step, '//form//label' )[0]->getAttribute( 'for' ) === $stepCode->getAttribute( 'id' ) );
check( '...the address it names is text: markup in it is no element, a shortcode in it is neither run nor filled, and the message above the button is the one of the state',
	membersText( $step, '//form/p[contains(@class, "members-login-intro")]/strong' ) === $typed && count( membersFind( $step, '//b' ) ) === 0 && str_contains( $step, '&lt;b&gt;[members-name]' ) === true && str_contains( $step, '[[' ) === false
	&& count( membersFind( $step, $messages ) ) === 1 && trim( membersFind( $step, $messages )[0]->textContent ) === trim( $states['sent'][0]->textContent ) );

ninoVisit( $appData, '/join?members=wrong' );
$wrongStep = $draw( '[members-login]' );
ninoVisit( $appData, '/join?members=expired' );
$expiredStep = $draw( '[members-login]' );
check( '...a wrong code draws it again, with its own message - and the state expired draws the first step, the request stays where it is', count( membersFind( $wrongStep, '//form[contains(@class, "members-login--code")]' ) ) === 1
	&& trim( membersFind( $wrongStep, $messages )[0]->textContent ) === trim( $states['wrong'][0]->textContent ) && count( membersFind( $expiredStep, '//form[contains(@class, "members-login")]' ) ) === 1 && count( membersFind( $expiredStep, '//form[contains(@class, "members-login--code")]' ) ) === 0
	&& ( membersPending( $appData )['email'] ?? '' ) === $typed );

// What a field says of its failure: invalid, and described by the sentence - which has an id
$describes = static function( string $html, string $field ): array {
	$input = membersFind( $html, '//form//input[@name="'. $field. '"]' )[0] ?? null;
	$said = $input === null || $input->getAttribute( 'aria-describedby' ) === '' ? [] : membersFind( $html, '//p[@id="'. $input->getAttribute( 'aria-describedby' ). '"][@role="status"]' );
	return [ $input?->getAttribute( 'aria-invalid' ), count( $said ) ];
};
ninoVisit( $appData, '/join?members=out' );
$outStep = $draw( '[members-login]' );
check( 'after a wrong code the field of the code is invalid and described by the sentence: aria-invalid is true and aria-describedby names the paragraph, which has that id', $describes( $wrongStep, 'code' ) === [ 'true', 1 ] );
check( '...so is the field of the address under each failure of the first step - a request that expired, one that is refused, a link that does not work, a code that is gone - and under none of the states that say what went well',
	$describes( $expiredStep, 'email' ) === [ 'true', 1 ] && array_filter( [ 'limit', 'invalid', 'gone' ], static function( string $state ) use ( &$appData, $draw, $describes ): bool { ninoVisit( $appData, '/join?members='. $state ); return $describes( $draw( '[members-login]' ), 'email' ) !== [ 'true', 1 ]; } ) === []
	&& $describes( $outStep, 'email' ) === [ '', 0 ] && $describes( $step, 'code' ) === [ '', 0 ] );
check( '...and the sentence of two forms on one page has an id of its own for each', ( static function() use ( &$appData, $draw ): bool {
	ninoVisit( $appData, '/join?members=limit' );
	$ids = array_map( static fn( DOMElement $paragraph ): string => $paragraph->getAttribute( 'id' ), membersFind( $draw( '[members-login][members-login]' ), '//p[@role="status"]' ) );
	return count( $ids ) === 2 && $ids[0] !== '' && $ids[0] !== $ids[1];
} )() );

$noRequest = [];

foreach( [ 'no request' => null, 'run out' => [ 'until' => time() - 1 ], 'no try left' => [ 'tries' => 5 ], 'no nonce' => [ 'nonce' => 'xyz' ], 'tries that are no number' => [ 'tries' => '0' ], 'no address' => [ 'email' => 7 ] ] as $kind => $change ) {
	\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );

	if( $change !== null )
		\Nino\Runtime::setSessionValue( $appData, './members/pending', $change + [ 'email' => 'ada@example.org', 'nonce' => str_repeat( 'b', 32 ), 'tries' => 0, 'until' => time() + 600 ] );

	foreach( [ 'sent', 'wrong' ] as $state ) {
		ninoVisit( $appData, '/join?members='. $state );
		$asked = $draw( '[members-login]' );
		$noRequest[$kind. ', '. $state] = count( membersFind( $asked, '//form[contains(@class, "members-login")]' ) ) === 1 && count( membersFind( $asked, '//input[@name="email"]' ) ) === 1 && count( membersFind( $asked, '//form[contains(@class, "members-login--code")]' ) ) === 0
			&& count( membersFind( $asked, $messages ) ) === 1 && trim( membersFind( $asked, $messages )[0]->textContent ) === trim( $states['gone'][0]->textContent );
	}
}

\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );
check( '...and where there is no request to ask a code for - none, one that has run out, one with no try left, one that is none - sent and wrong are said as gone, over the field for an address', array_filter( $noRequest, static fn( bool $same ): bool => $same === false ) === [] && count( $noRequest ) === 12 );

ninoVisit( $appData, '/join?members=sent' );
$appData['./members/state'] = 'invalid';
$set = membersFind( $draw( '[members-login]' ), '//form/p' );
ninoVisit( $appData, '/join' );
$setOnly = membersFind( $draw( '[members-login]' ), '//form/p' );
unset( $appData['./members/state'] );
$appData['./members/state'] = 'not a state';
$setWrong = membersFind( $draw( '[members-login]' ), '//form/p' );
unset( $appData['./members/state'] );

check( 'a state a callback set wins over the query, and is a state without it - when it is one of the seven', count( $set ) === 1 && count( $setOnly ) === 1 && $set[0]->textContent === $setOnly[0]->textContent
	&& $set[0]->textContent !== $states['sent'][0]->textContent && $set[0]->textContent === $states['invalid'][0]->textContent && count( $setWrong ) === 0 );

ninoVisit( $appData, '/join' );
$twice = $draw( '[members-login inline="1"][members-login]' );
$both = membersFind( $twice, '//form' );
$labels = membersFind( $twice, '//form//label' );

check( 'inline puts the form on one line and the label for screen readers only, and the bare word is the flag; a form that is not inline has neither', count( $both ) === 2
	&& in_array( 'members-login--inline', membersClasses( $both[0] ), true ) === true && in_array( 'nino-sr-only', membersClasses( $labels[0] ), true ) === true
	&& in_array( 'members-login--inline', membersClasses( $both[1] ), true ) === false && in_array( 'nino-sr-only', membersClasses( $labels[1] ), true ) === false
	&& in_array( 'members-login--inline', membersClasses( membersFind( $draw( '[members-login inline]' ), '//form' )[0] ), true ) === true );
check( '...two forms on a page do not share an id, each label pointing at its own field', $labels[0]->getAttribute( 'for' ) !== $labels[1]->getAttribute( 'for' )
	&& count( membersFind( $twice, '//input[@id="'. $labels[0]->getAttribute( 'for' ). '"]' ) ) === 1 && count( membersFind( $twice, '//input[@id="'. $labels[1]->getAttribute( 'for' ). '"]' ) ) === 1 );
\Nino\Runtime::setSessionValue( $appData, './members/pending', [ 'email' => 'ada@example.org', 'nonce' => str_repeat( 'd', 32 ), 'tries' => 0, 'until' => time() + 600 ] );
ninoVisit( $appData, '/join?members=sent' );
$codes = membersFind( $draw( '[members-login][members-login]' ), '//form//input[@name="code"]' );
\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );
check( '...and so do two forms of the second step: the field of the code of each has an id of its own', count( $codes ) === 2 && $codes[0]->getAttribute( 'id' ) !== $codes[1]->getAttribute( 'id' ) );
check( '[members-logout] and [members-name] show a visitor nothing at all, so that a header can carry them for everybody - and [members-confirm] shows nothing where no link is being confirmed',
	$draw( '[members-logout]' ) === '' && $draw( '[members-name]' ) === '' && $draw( '[members-confirm]' ) === '' );
check( 'a hand-written call with an attribute nobody declared still renders', count( membersFind( $draw( '[members-login nonsense="1"]' ), '//form' ) ) === 1 && ninoWarnings() === [] );
check( 'a call inside another is no call to the form: nothing a visitor typed or a text holds is read twice', preg_match( '/\[\[|\[csrf\]|\[template/', $step ) === 0 );

// The two pages of the unit are made of components, a tool reads them as blocks with nothing it keeps as free html - and the block of the feature is valid
foreach( [ 'page-members-login', 'page-members-confirm' ] as $page ) {

	$source = (string) file_get_contents( $dir. '/install/templates/'. $page. '.tpl' );
	$found = [];

	foreach( \Nino\Components::check( $appData, \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ) ) ) as $at => $problems )
		foreach( $problems as $problem )
			if( ( in_array( $problem['code'], [ 'unknown', 'bare' ], true ) === true && $problem['detail'] === 'template' ) === false && ( $problem['code'] === 'free' && $at === 0 ) === false )
				$found[] = $problem['code'];

	check( 'the page '. $page. ' is made of components and the two includes of the header and the footer: no class of Nino 1, no element of its own, nothing a tool keeps as free html, the form among them',
		preg_match( '/nino-|<section|<div|<form|<h1/', $source ) === 0 && str_contains( $source, $page === 'page-members-login' ? '[members-login]' : '[members-confirm]' ) === true && $found === [] );
}

$block = \Nino\Blocks::get( $appData, 'members-login' );
check( 'the block is valid, names the feature and brings the form, and the demo of the group draws it', is_array( $block ) === true && in_array( 'members', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === []
	&& substr_count( $draw( (string) ( \Nino\Components::groups( $appData )['members']['demo'] ?? '' ) ), '<form class="members-login"' ) === 1 );

// The look of the site's mails: the base unit's words, which a project has from its wizard
if( is_file( $root. '/_admin/install/library/base/text/global.php' ) === true )
	\Nino\Html::addFills( $appData, (array) include $root. '/_admin/install/library/base/text/global.php', '*' );

\Nino\Html::addFills( $appData, [ '[[/nino/dir]]' => '', '[[/project/website/general/url]]' => 'example.org/club' ], '*' );

// The minimum of a page: the header and the footer the login page is rendered between
file_put_contents( \Nino\Filesystem::path( $appData, '/templates/html-header.tpl' ), '<html><body>' );
file_put_contents( \Nino\Filesystem::path( $appData, '/templates/html-footer.tpl' ), '</body></html>' );

// Pages for the protected paths and outside them
$appData['/nino/http/routes']['GET://club/news'] = [ 'uri' => '/club/news', 'body' => 'FOR MEMBERS' ];
$appData['/nino/http/routes']['GET://clubhouse'] = [ 'uri' => '/clubhouse', 'body' => 'FOR EVERYBODY' ];
$appData['/nino/http/routes']['GET://news'] = [ 'uri' => '/news', 'body' => 'FOR EVERYBODY' ];

// The page /club has an address in each language its routes give it: the German ones carry its uri and a locale of their own
$appData['/nino/http/routes']['GET://club'] = [ 'uri' => '/club', 'locale' => 'en_US', 'body' => 'FOR MEMBERS' ];
$appData['/nino/http/routes']['GET://verein'] = [ 'uri' => '/club', 'locale' => 'de_DE', 'body' => 'FOR MEMBERS' ];
$appData['/nino/http/routes']['GET://verein/*'] = [ 'uri' => '/club/*', 'locale' => 'de_DE', 'body' => 'FOR MEMBERS' ];

echo "\n";
// --- The panel -----------------------------------------------------------------

echo "Modules\\Members\\Admin - the accounts, and their permission\n";

// A set-up project with a full-access account, the way the wizard leaves one
$appData['/nino/install/completed'] = true;
ninoUser( $appData, 'plain@example.com', [], 'plain password' );
ninoUser( $appData, 'admin@example.com' );

check( 'the panel is in the registry while the feature is active, in the rail\'s group of the features', isset( \Nino\Admin\Admin::panels( $appData )['members'] ) === true
	&& \Nino\Admin\Admin::panels( $appData )['members']['group'] === 'features' );
check( '...with one action for each thing it does, all under its slug', array_keys( array_filter( \Nino\Admin\Admin::actions( $appData ), static fn( string $action ): bool => str_starts_with( $action, 'members/' ), ARRAY_FILTER_USE_KEY ) )
	=== [ 'members/list', 'members/add', 'members/save', 'members/delete', 'members/link', 'members/signout' ] );

$posts = [ 'members/list' => [], 'members/add' => [ 'email' => 'nobody@example.org' ], 'members/save' => [ 'id' => '0123456789ab', 'name' => 'x' ], 'members/delete' => [ 'id' => '0123456789ab' ],
	'members/link' => [ 'id' => '0123456789ab' ], 'members/signout' => [ 'all' => true ] ];
$denied = [ 'signed out' => [], 'without the permission' => [] ];

unset( $appData['./nino/auth/current'] );

foreach( $posts as $action => $data ) {
	[ $status, $body ] = ninoCall( $appData, $panel, $data, $action );
	$denied['signed out'][$action] = [ $status, $body['code'] ?? '' ];
}

\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );

foreach( $posts as $action => $data ) {
	[ $status, $body ] = ninoCall( $appData, $panel, $data, $action );
	$denied['without the permission'][$action] = [ $status, $body['code'] ?? '' ];
}

check( 'every action refuses a visitor with 401 and the code session', array_values( array_unique( array_map( 'json_encode', $denied['signed out'] ) ) ) === [ '[401,"session"]' ] && count( $denied['signed out'] ) === 6 );
check( '...and an account without the permission with 403, writing nothing', array_values( array_unique( array_map( 'json_encode', $denied['without the permission'] ) ) ) === [ '[403,""]' ]
	&& membersFile( $appData, 'accounts' ) === [] && $mails === [] && membersFile( $appData, 'session' ) === [] );

\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );
membersFresh( $appData );

[ $status, $body ] = ninoCall( $appData, $panel, [], 'members/list' );
check( 'the list of a project without a member is empty, and so are the groups', $status === 200 && $body === [ 'rows' => [], 'groups' => [] ] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => ' Ada@Example.ORG ', 'name' => ' Ada Lovelace ', 'group' => 'Premium Members', 'invite' => false ], 'members/add' );
$stored = membersFile( $appData, 'accounts' )['accounts'] ?? [];
$id = (string) array_key_first( $stored );
$record = $stored[$id] ?? [];

check( 'add stores the account the way the plan has it: an id of 12 hex characters, the address in lower case, the name trimmed, the group as a slug, active, the time of its making, no sign-in yet',
	$status === 200 && preg_match( '/^[0-9a-f]{12}$/D', $id ) === 1 && count( $stored ) === 1 && array_keys( $record ) === [ 'email', 'name', 'group', 'active', 'created', 'login' ]
	&& $record['email'] === 'ada@example.org' && $record['name'] === 'Ada Lovelace' && $record['group'] === 'premium-members' && $record['active'] === true && $record['login'] === null
	&& preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $record['created'] ) === 1 && abs( strtotime( $record['created'] ) - time() ) < 10 );
check( '...and answers with it, id and all, and with no invitation sent when the form did not ask for one', $body['account'] === [ 'id' => $id ] + $record && $body['invited'] === false && $mails === [] );
check( '...the module\'s own readings agree: account(), accounts(), groups() and, with no session, current()', \Nino\Modules\Members::account( $appData, $id ) === [ 'id' => $id ] + $record
	&& \Nino\Modules\Members::accounts( $appData ) === [ [ 'id' => $id ] + $record ] && \Nino\Modules\Members::groups( $appData ) === [ 'premium-members' ]
	&& \Nino\Modules\Members::account( $appData, 'ffffffffffff' ) === null && \Nino\Modules\Members::current( $appData ) === null );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'ADA@example.org', 'name' => 'Another Ada' ], 'members/add' );
check( 'an address that is a member\'s already - in whatever case - is refused with 400, the code members_taken and the field email', $status === 400 && $body['code'] === 'members_taken' && $body['field'] === 'email' && count( membersFile( $appData, 'accounts' )['accounts'] ) === 1 );

$refused = [];

foreach( [ 'a text' => 'not an address', 'empty' => '', 'two addresses' => 'a@example.org, b@example.org', 'a list' => [ 'a@example.org' ], 'too long' => str_repeat( 'a', 250 ). '@example.org' ] as $kind => $email ) {
	ninoWarnings();
	[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => $email ], 'members/add' );
	$refused[$kind] = [ $status, $body['code'] ?? '', $body['field'] ?? '', ninoWarnings() ];
}

check( 'an address that is none - a text, nothing, two, a list, one too long - is 400, members_invalid, the field email, and no warning', array_values( array_unique( array_map( 'json_encode', $refused ) ) ) === [ '[400,"members_invalid","email",[]]' ] && count( $refused ) === 5 );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'long@example.org', 'name' => str_repeat( 'x', 121 ) ], 'members/add' );
[ $statusEdge ] = ninoCall( $appData, $panel, [ 'email' => 'edge@example.org', 'name' => str_repeat( 'ü', 120 ), 'invite' => false ], 'members/add' );
check( 'a name of more than 120 characters is 400 with the code members_long, the limit in its params and the field name - 120 of them are fine, counted as characters',
	$status === 400 && $body['code'] === 'members_long' && $body['params'] === [ 120 ] && $body['field'] === 'name' && $statusEdge === 200 && count( membersFile( $appData, 'accounts' )['accounts'] ) === 2 );

foreach( [ 'members_invalid', 'members_taken', 'members_long', 'members_unknown', 'members_inactive', 'members_mail', 'members_ratelimit' ] as $code )
	foreach( [ 'en_US', 'de_DE' ] as $locale )
		check( "...the code $code has its sentence in $locale", trim( \Nino\Admin\Admin::textFills( $appData, '/features/Members/text', $locale )['[[/_admin/error/'. $code. ']]'] ?? '' ) !== '' );

foreach( [ 'en_US', 'de_DE' ] as $locale )
	check( "...the sentence of members_ratelimit in $locale is not empty and names the hour", preg_match( '/Stunde|hour/', trim( \Nino\Admin\Admin::textFills( $appData, '/features/Members/text', $locale )['[[/_admin/error/members_ratelimit]]'] ?? '' ) ) === 1 );

// An invitation: the account is added and a link goes to its address, valid for the days the setting names
membersFresh( $appData );
$mails = [];
$before = time();
[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'bert@example.org', 'name' => 'bert', 'group' => '', 'invite' => true ], 'members/add' );
$bert = (string) ( $body['account']['id'] ?? '' );
$token = membersTokenOf( $mails[0] ?? [] );
$tokens = membersFile( $appData, 'tokens' );
$entry = $tokens[ hash( 'sha256', $token ) ] ?? [];
$mailText = strip_tags( (string) ( $mails[0]['body'] ?? '' ) );

check( 'add with the invitation ticked sends one mail to the address: a link of the website with a token of 43 characters, the site named and no key or token left in the text',
	$status === 200 && $body['invited'] === true && count( $mails ) === 1 && $mails[0]['to'] === 'bert@example.org' && strlen( $token ) === 43 && trim( (string) $mails[0]['subject'] ) !== '' && str_contains( (string) $mails[0]['subject'], '[[' ) === false
	&& str_contains( (string) $mails[0]['body'], '[[' ) === false && str_contains( $mailText, \Nino\Html::renderTextfill( $appData, '/project/company/general/name' ) ) === true );
$introCode = trim( strip_tags( \Nino\Html::renderTextfill( $appData, '/feature/members/mail/invite-intro-code' ) ) );
check( '...the intro of the invitation is the key invite-intro-code - the words of a sign-in with a code - and the unit has it in both languages', $introCode !== '' && str_contains( $mailText, $introCode ) === true && str_contains( (string) file_get_contents( $dir. '/templates/mail-invite.tpl' ), 'mail/invite-intro-code]]' ) === true
	&& array_filter( $unitWords, static fn( array $words ): bool => trim( (string) ( $words['[[/feature/members/mail/invite-intro-code]]'] ?? '' ) ) === '' ) === [] );
check( '...the mail replies to the contact address of the project', $mails[0]['replyTo'] === \Nino\Html::renderTextfill( $appData, '/project/company/contact/email' ) && $mails[0]['replyTo'] !== '' );
check( '...the file of the links keeps the sha256 of the token and never the token: an invitation of this account, good for the days of the setting, with no page to go on to',
	count( $tokens ) === 1 && array_keys( $entry ) === [ 'account', 'kind', 'expires', 'target' ] && $entry['account'] === $bert && $entry['kind'] === 'invite' && $entry['target'] === ''
	&& $entry['expires'] >= $before + 7 * 86400 - 2 && $entry['expires'] <= time() + 7 * 86400 + 2 && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members-tokens.php' ) ), $token ) === false );
check( '...and says the days: the setting\'s number stands in the text', preg_match( '/(?<![\d.])7(?![\d.])/', $mailText ) === 1 );

// The place of the band at the head of the mail: [mail-head], which a module of the project answers - the feature itself names none
$bandOf = static function() use ( &$appData, &$mails, $bert ): string {

	$mails = [];
	membersFresh( $appData );
	\Nino\Modules\Members::link( $appData, (array) \Nino\Modules\Members::account( $appData, $bert ), 'login' );

	return (string) ( $mails[0]['body'] ?? '' );
};
$noBand = $bandOf();
\Nino\Html::addShortcode( $appData, 'mail-head', static fn( array &$appData, mixed $args ): string => '<p data-band="head">BAND</p>' );
$withBand = $bandOf();
array_pop( $appData['./nino/callbacks']['/nino/html/shortcode/mail-head'][5] );
check( 'the feature registers [mail-head] itself, and where no module answers it the mail with a link to sign in goes out with nothing in its place: no shortcode as text, no picture, no table of a band', in_array( 'mail-head', \Nino\Html::shortcodes( $appData ), true ) === true && $noBand !== '' && preg_match( '~\[mail-head\]~', $noBand ) === 0
	&& str_contains( $noBand, 'data-band' ) === false && str_contains( $noBand, '<img' ) === false && str_contains( $noBand, '<table role="presentation" width="100%"' ) === false
	&& membersTokenOf( [ 'body' => $noBand ] ) !== '' );
check( '...and where a module answers it, its answer stands in the place, once, before the heading - the feature leaves the place to it', substr_count( $withBand, '<p data-band="head">BAND</p>' ) === 1 && strpos( $withBand, 'data-band' ) < strpos( $withBand, '<h1' ) && preg_match( '~\[mail-head\]~', $withBand ) === 0 );

\Nino\Features::saveSettings( $appData, 'members', [ 'inviteDays' => 3 ] );
membersFresh( $appData );
$mails = [];
ninoCall( $appData, $panel, [ 'email' => 'c@example.org', 'invite' => true ], 'members/add' );
check( '...a different number of days is the number in the next one, and the entry runs out then', preg_match( '/(?<![\d.])3(?![\d.])/', strip_tags( (string) ( $mails[0]['body'] ?? '' ) ) ) === 1
	&& ( membersFile( $appData, 'tokens' )[ hash( 'sha256', membersTokenOf( $mails[0] ?? [] ) ) ]['expires'] ?? 0 ) <= time() + 3 * 86400 + 2 );
\Nino\Features::saveSettings( $appData, 'members', [ 'inviteDays' => 7 ] );

// The site\'s own language, whoever is at the screen: the account is written to in the language of the website, and the screen\'s stays
$appData['./nino/locales/current'] = 'en_US';
membersFresh( $appData );
$mails = [];
ninoCall( $appData, $panel, [ 'email' => 'd@example.org', 'invite' => true ], 'members/add' );
$native = \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/members/mail/invite-subject]]'];
$other = \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/members/mail/invite-subject]]'];
check( 'an invitation goes out in the site\'s native language, and the language of the request is back afterwards', $native !== $other && ( $mails[0]['subject'] ?? '' ) === $native && $appData['./nino/locales/current'] === 'en_US' );
$appData['./nino/locales/current'] = 'de_DE';
ninoCall( $appData, $panel, [ 'id' => membersIdOf( $appData, 'd@example.org' ) ], 'members/delete' );

membersLanguageChecks( $appData, $panel );

// A mail no transport takes: the account is there all the same, and the answer says that the mail is what failed
membersFresh( $appData );
$delivery = false;
ninoWarnings();
[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'zoe@example.org', 'name' => 'Zoe', 'invite' => true ], 'members/add' );
$delivery = true;
check( 'an invitation that could not be sent is a 500 with the code members_mail - and the account is there', $status === 500 && $body['code'] === 'members_mail'
	&& in_array( 'zoe@example.org', array_column( membersFile( $appData, 'accounts' )['accounts'], 'email' ), true ) === true );

// The list: sorted by name, the address where there is none - and the member made last, with the group that sorts last, is neither the last of the list nor the last of the groups
ninoCall( $appData, $panel, [ 'id' => membersIdOf( $appData, 'edge@example.org' ) ], 'members/delete' );
ninoCall( $appData, $panel, [ 'email' => 'abe@example.org', 'name' => 'abe', 'group' => 'Zeta', 'invite' => false ], 'members/add' );

[ $status, $body ] = ninoCall( $appData, $panel, [], 'members/list' );
check( 'the list is sorted by name, without regard to case, and by the address where an account has no name; the groups are the ones in use', $status === 200
	&& array_column( $body['rows'], 'email' ) === [ 'abe@example.org', 'ada@example.org', 'bert@example.org', 'c@example.org', 'zoe@example.org' ] && $body['groups'] === [ 'premium-members', 'zeta' ]
	&& array_keys( $body['rows'][0] ) === [ 'key', 'id', 'email', 'name', 'group', 'active', 'created', 'login' ] && array_column( $body['rows'], 'key' ) === array_column( $body['rows'], 'id' ) );

// save: the member has signed in once, which is a time the change keeps
$stamped = membersFile( $appData, 'accounts' );
$stamped['accounts'][$id]['login'] = '2024-05-06T07:08:09Z';
\Nino\Filesystem::putFileContent( $appData, '/data/members.php', $stamped );
$whole = membersFile( $appData, 'accounts' )['accounts'];
ninoWarnings();
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $id, 'name' => ' Ada L. ', 'group' => 'Old Staff', 'active' => false, 'email' => 'other@example.org', 'created' => '1999-01-01T00:00:00Z', 'login' => '1999-01-01T00:00:00Z' ], 'members/save' );
$after = membersFile( $appData, 'accounts' )['accounts'];
check( 'save changes the name, the group and whether the account is active, and keeps everything else of it - the address and the times a request names among it', $status === 200
	&& array_diff_key( $after[$id], [ 'epoch' => 1 ] ) === [ 'email' => 'ada@example.org', 'name' => 'Ada L.', 'group' => 'old-staff', 'active' => false, 'created' => $whole[$id]['created'], 'login' => '2024-05-06T07:08:09Z' ]
	&& $body['account'] === [ 'id' => $id ] + array_diff_key( $after[$id], [ 'epoch' => 1 ] ) );
check( '...switching it off gives it an epoch of its own - 32 hex characters, which no session from before carries - and no other change does', preg_match( '/^[0-9a-f]{32}$/D', (string) ( $after[$id]['epoch'] ?? '' ) ) === 1 && isset( $whole[$id]['epoch'] ) === false );
check( '...and every other account as it was', array_diff_key( $after, [ $id => 1 ] ) === array_diff_key( $whole, [ $id => 1 ] ) );

ninoCall( $appData, $panel, [ 'id' => $id, 'name' => 'Ada Lovelace' ], 'members/save' );
ninoCall( $appData, $panel, [ 'id' => $id, 'active' => 'no', 'group' => [ 'x' ] ], 'members/save' );
$partial = membersFile( $appData, 'accounts' )['accounts'][$id];
check( 'what is not posted - or is posted as something else than it is - stays: a name alone changes the name, a word for the flag and a list for the group change nothing', $partial['name'] === 'Ada Lovelace'
	&& $partial['group'] === 'old-staff' && $partial['active'] === false && ninoWarnings() === [] );

[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $id, 'name' => str_repeat( 'x', 121 ) ], 'members/save' );
check( '...a name that is too long is refused the way add refuses it, and nothing is saved', $status === 400 && $body['code'] === 'members_long' && $body['field'] === 'name' && membersFile( $appData, 'accounts' )['accounts'][$id] === $partial );

$unknown = [];

foreach( [ 'no such id' => 'ffffffffffff', 'a path' => '../etc/passwd', 'nothing' => '', 'too short' => 'abc', 'a list' => [ $id ] ] as $kind => $wrong )
	foreach( [ 'members/save', 'members/delete', 'members/link' ] as $action ) {
		[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $wrong, 'name' => 'x' ], $action );
		$unknown[$kind. ' '. $action] = [ $status, $body['code'] ?? '' ];
	}

check( 'an id nobody has - or one that is none - is 404 with the code members_unknown on save, delete and link alike, and changes nothing', array_values( array_unique( array_map( 'json_encode', $unknown ) ) ) === [ '[404,"members_unknown"]' ]
	&& count( $unknown ) === 15 && membersFile( $appData, 'accounts' )['accounts'][$id] === $partial );

// link
membersFresh( $appData );
$mails = [];
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $id ], 'members/link' );
check( 'an account that is switched off is sent no link: 400, the code members_inactive, no mail and no token', $status === 400 && $body['code'] === 'members_inactive' && $mails === [] && array_filter( membersFile( $appData, 'tokens' ), static fn( array $each ): bool => $each['account'] === $id ) === [] );

ninoCall( $appData, $panel, [ 'id' => $id, 'active' => true ], 'members/save' );
$tokensBefore = membersFile( $appData, 'tokens' );
$before = time();
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $id ], 'members/link' );
$token = membersTokenOf( $mails[0] ?? [] );
$entry = membersFile( $appData, 'tokens' )[ hash( 'sha256', $token ) ] ?? [];
check( 'link sends the member a link to sign in: one mail to the address, a token of the kind login that runs out after the minutes of the setting, and not the days of an invitation', $status === 200 && $body === [ 'sent' => true ]
	&& count( $mails ) === 1 && $mails[0]['to'] === 'ada@example.org' && ( $entry['kind'] ?? '' ) === 'login' && ( $entry['account'] ?? '' ) === $id && ( $entry['expires'] ?? 0 ) >= $before + 900 - 2 && ( $entry['expires'] ?? 0 ) <= time() + 900 + 2
	&& preg_match( '/(?<![\d.])15(?![\d.])/', strip_tags( (string) $mails[0]['body'] ) ) === 1 );
check( '...and the links that were out stay good', array_diff_key( membersFile( $appData, 'tokens' ), [ hash( 'sha256', $token ) => 1 ] ) === $tokensBefore );

// ...asked not to mail: the token is kept, the link is the answer, once
membersFresh( $appData );
$mails = [];
$tokensBefore = membersFile( $appData, 'tokens' );
$before = time();
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $id, 'notify' => false ], 'members/link' );
$quietToken = preg_match( '~^https://example\.org/club/\.members/login\?t=([A-Za-z0-9_-]+)$~D', (string) ( $body['link'] ?? '' ), $found ) === 1 ? $found[1] : '';
$quietEntry = membersFile( $appData, 'tokens' )[ hash( 'sha256', $quietToken ) ] ?? [];
check( 'link with notify false mails nobody and answers the link as the mail would have carried it; the token is kept for the account as a mailed one is - the kind login, the minutes of the setting - and as its sha256 only',
	$status === 200 && $body['sent'] === false && $quietToken !== '' && $mails === [] && ( $quietEntry['kind'] ?? '' ) === 'login' && ( $quietEntry['account'] ?? '' ) === $id && ( $quietEntry['expires'] ?? 0 ) >= $before + 900 - 2 && ( $quietEntry['expires'] ?? 0 ) <= time() + 900 + 2
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members-tokens.php' ) ), $quietToken ) === false && count( membersFile( $appData, 'tokens' ) ) === count( $tokensBefore ) + 1 );
membersVisitor();
$signedIn = membersConfirm( $appData, $quietToken );
check( '...and the link signs the account in, as a link of a mail does - once: the next use of it is the page of a link that is spent', $signedIn[0] < 400 && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $id
	&& membersAnswer( membersServe( $appData, '/.members/login?t='. $quietToken ) )[0] !== 200 );
membersVisitor();
check( '...while notify true, absent, or anything that is no false mails and answers no link', ( static function() use ( &$appData, $panel, $id ): bool {
	global $mails;
	$mails = [];
	$answers = [ ninoCall( $appData, $panel, [ 'id' => $id, 'notify' => true ], 'members/link' ), ninoCall( $appData, $panel, [ 'id' => $id, 'notify' => 'false' ], 'members/link' ), ninoCall( $appData, $panel, [ 'id' => $id, 'notify' => 0 ], 'members/link' ) ];
	return count( $mails ) === 3 && array_filter( $answers, static fn( array $each ): bool => $each[0] !== 200 || isset( $each[1]['link'] ) || $each[1] !== [ 'sent' => true ] ) === [];
} )() );
$logged = \Nino\Modules\Members\Admin::log( 'members/link', [ 'id' => $id, 'notify' => false ] );
check( '...the line of the activity log says that no mail was sent, and holds nothing of a link', str_contains( $logged, 'without mail' ) === true && str_contains( $logged, $quietToken ) === false && str_contains( \Nino\Modules\Members\Admin::log( 'members/link', [ 'id' => $id ] ), 'without mail' ) === false
	&& str_contains( \Nino\Modules\Members\Admin::log( 'members/add', [ 'invite' => true, 'notify' => false ] ), 'without mail' ) === true && str_contains( \Nino\Modules\Members\Admin::log( 'members/add', [ 'invite' => true ] ), 'without mail' ) === false
	&& str_contains( \Nino\Modules\Members\Admin::log( 'members/add', [ 'invite' => false, 'notify' => false ] ), 'without mail' ) === false );

// ...a link without the mail is charged to the cap of mails an hour as a mail is: the sixth is refused, with the code of the cap, and so is a mail after five
membersFresh( $appData );
$mails = [];
$quietRuns = [];
$tokensKept = [];
for( $each = 0; $each < 6; $each++ ) {
	$quietRuns[] = ninoCall( $appData, $panel, [ 'id' => $id, 'notify' => false ], 'members/link' );
	$tokensKept[] = count( membersFile( $appData, 'tokens' ) );
}
$mailedAfter = ninoCall( $appData, $panel, [ 'id' => $id ], 'members/link' );
check( 'the sixth link in an hour without a mail is a 429 with members_ratelimit and makes no token - as the sixth mail is - and a mail after five links is refused by the same counter',
	array_column( $quietRuns, 0 ) === [ 200, 200, 200, 200, 200, 429 ] && ( $quietRuns[5][1]['code'] ?? '' ) === 'members_ratelimit' && isset( $quietRuns[5][1]['link'] ) === false
	&& $mailedAfter[0] === 429 && $mails === [] && \Nino\Mail::rateLimited( $appData ) === true && $tokensKept[5] === $tokensKept[4] && $tokensKept[4] === $tokensKept[0] + 4 );
$asked = \Nino\Modules\Members::link( $appData, \Nino\Modules\Members::account( $appData, $id ), 'login', '', true, false );
check( '...and Members::link() with $notify false answers false over the cap, with the flag of a refused mail', $asked === false && \Nino\Mail::rateLimited( $appData ) === true );
membersFresh( $appData );

// ...the cap is the configured one, '/nino/mail/cap': at 2 the third link is refused, at 100 and at 0 (no cap) the sixth goes through
$capRuns = [];
foreach( [ 2 => 3, 100 => 6, 0 => 6 ] as $cap => $times ) {
	membersFresh( $appData );
	$appData['/nino/mail/cap'] = $cap;
	$statuses = [];
	for( $each = 0; $each < $times; $each++ )
		$statuses[] = ninoCall( $appData, $panel, [ 'id' => $id, 'notify' => false ], 'members/link' )[0];
	$capRuns[$cap] = $statuses;
}
unset( $appData['/nino/mail/cap'] );
membersFresh( $appData );
check( 'a link without the mail is charged against the configured cap: at 2 the third is a 429 and the two before are links, at 100 and at 0 (no cap) six pass',
	$capRuns === [ 2 => [ 200, 200, 429 ], 100 => [ 200, 200, 200, 200, 200, 200 ], 0 => [ 200, 200, 200, 200, 200, 200 ] ] );
$mails = [];
membersFresh( $appData );
for( $each = 0; $each < 5; $each++ )
	\Nino\Feature::count( $appData, 'ratelimit', \Nino\Http::getClientIp( $appData ), 5, 3600 );
$beforeAdd = count( membersFile( $appData, 'accounts' )['accounts'] );
[ $capStatus, $capBody ] = ninoCall( $appData, $panel, [ 'email' => 'capped-quiet@example.org', 'name' => 'Capped', 'invite' => true, 'notify' => false ], 'members/add' );
check( 'members/add with an invitation whose link is not mailed, at the cap, answers the cap - a 429 members_ratelimit and not a 500 - and the link is not in the answer', $capStatus === 429 && ( $capBody['code'] ?? '' ) === 'members_ratelimit' && isset( $capBody['link'] ) === false );
ninoCall( $appData, $panel, [ 'id' => membersIdOf( $appData, 'capped-quiet@example.org' ) ], 'members/delete' );
membersFresh( $appData );

membersFresh( $appData );
$mails = [];
[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'quiet-invite@example.org', 'name' => 'Quiet', 'invite' => true, 'notify' => false ], 'members/add' );
$inviteToken = preg_match( '~^https://example\.org/club/\.members/login\?t=([A-Za-z0-9_-]+)$~D', (string) ( $body['link'] ?? '' ), $found ) === 1 ? $found[1] : '';
$inviteEntry = membersFile( $appData, 'tokens' )[ hash( 'sha256', $inviteToken ) ] ?? [];
check( 'add with an invitation and notify false adds the account, mails nobody and answers the link of the invitation: the kind invite, the days of the setting',
	$status === 200 && $body['invited'] === true && $mails === [] && $inviteToken !== '' && ( $inviteEntry['kind'] ?? '' ) === 'invite' && ( $inviteEntry['account'] ?? '' ) === ( $body['account']['id'] ?? '-' ) && ( $inviteEntry['expires'] ?? 0 ) >= time() + 7 * 86400 - 5 );
[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'no-invite@example.org', 'invite' => false, 'notify' => false ], 'members/add' );
check( '...and without an invitation notify has nothing to do: no link, no mail', $status === 200 && $body['invited'] === false && isset( $body['link'] ) === false && $mails === [] );
ninoCall( $appData, $panel, [ 'id' => membersIdOf( $appData, 'quiet-invite@example.org' ) ], 'members/delete' );
ninoCall( $appData, $panel, [ 'id' => membersIdOf( $appData, 'no-invite@example.org' ) ], 'members/delete' );

$mails = [];
membersFresh( $appData );
$account = \Nino\Modules\Members::account( $appData, $id );
$asLogin = \Nino\Modules\Members::link( $appData, $account, 'login', '/portal', true, false );
$asInvite = \Nino\Modules\Members::link( $appData, $account, 'invite', '', false, false );
$asMail = \Nino\Modules\Members::link( $appData, $account, 'login', '', true );
check( 'Members::link() with $notify false answers the link and sends nothing - for both kinds; with the default it answers true and mails',
	is_string( $asLogin ) === true && is_string( $asInvite ) === true && str_starts_with( $asLogin, 'https://example.org/club/.members/login?t=' ) === true && $asLogin !== $asInvite && $asMail === true && count( $mails ) === 1 );
$noAddress = '';
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => '' ], '*' );
ninoWarnings();
$noAddress = \Nino\Modules\Members::link( $appData, $account, 'login', '', true, false );
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => 'example.org/club' ], '*' );
check( '...and a site with no address of its own has no link to give: false, with a warning, and no token kept', $noAddress === false && count( ninoWarnings() ) >= 1 );
membersFresh( $appData );

membersFresh( $appData );
$delivery = false;
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $id ], 'members/link' );
$delivery = true;
check( 'a link whose mail was not taken is a 500 with the code members_mail', $status === 500 && $body['code'] === 'members_mail' );

// The kernel's own cap of mails an hour from one address is a failure of its own, and the person at the screen is told so
membersFresh( $appData );

for( $each = 0; $each < 5; $each++ )
	\Nino\Mail::send( $appData, 'cap@example.org', 'x', 'x', '' );

$mails = [];
[ $statusLink, $bodyLink ] = ninoCall( $appData, $panel, [ 'id' => $id ], 'members/link' );
[ $statusAdd, $bodyAdd ] = ninoCall( $appData, $panel, [ 'email' => 'capped@example.org', 'invite' => true ], 'members/add' );
$cappedId = membersIdOf( $appData, 'capped@example.org' );
check( 'a mail the kernel\'s cap refuses is a 429 with the code members_ratelimit - on a link and on an invitation alike - and the account of the invitation is there all the same, with no mail gone out',
	$statusLink === 429 && ( $bodyLink['code'] ?? '' ) === 'members_ratelimit' && $statusAdd === 429 && ( $bodyAdd['code'] ?? '' ) === 'members_ratelimit' && $cappedId !== '' && $mails === [] );
ninoCall( $appData, $panel, [ 'id' => $cappedId ], 'members/delete' );
membersFresh( $appData );

// delete
$before = membersFile( $appData, 'accounts' )['accounts'];
[ $status ] = ninoCall( $appData, $panel, [ 'id' => $bert ], 'members/delete' );
$after = membersFile( $appData, 'accounts' )['accounts'];
check( 'delete removes the account it is given - that one and no other', $status === 200 && array_keys( $after ) === array_values( array_diff( array_keys( $before ), [ $bert ] ) ) && $after === array_diff_key( $before, [ $bert => 1 ] ) );
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $bert ], 'members/delete' );
check( '...and the same account again is an id nobody has', $status === 404 && $body['code'] === 'members_unknown' && membersFile( $appData, 'accounts' )['accounts'] === $after );

// the Dashboard and the activity log
$assets = \Nino\Modules\Members\Admin::assets();
check( 'the panel brings its script and no stylesheet of its own: the directory and the record are the workbench\'s, and what it draws is theirs to style', count( $assets ) === 1 && str_ends_with( $assets[0], '/assets/admin.js' ) === true && is_file( $dir. '/assets/admin.js' ) === true && is_file( $dir. '/assets/admin.css' ) === false );
check( 'the Dashboard\'s tile counts the accounts, with a label that has its sentence in both languages', \Nino\Modules\Members\Admin::summary( $appData )['value'] === count( $after )
	&& array_filter( [ 'en_US', 'de_DE' ], static fn( string $locale ): bool => trim( (string) ( \Nino\Admin\Admin::textFills( $GLOBALS['appData'], '/features/Members/text', $locale )['[['. \Nino\Modules\Members\Admin::summary( $GLOBALS['appData'] )['label']. ']]'] ?? '' ) ) === '' ) === [] );

$lines = [];

foreach( [ 'members/add', 'members/save', 'members/delete', 'members/link', 'members/signout', 'members/list' ] as $action )
	$lines[$action] = \Nino\Modules\Members\Admin::log( $action, [ 'id' => $id, 'email' => 'ada@example.org', 'name' => 'Ada Lovelace' ] );

check( 'the activity log has a line for every action that changes something - none for a read - and not the address nor the name of a member in any of them',
	$lines['members/list'] === '' && array_filter( array_diff_key( $lines, [ 'members/list' => 1 ] ), static fn( string $line ): bool => $line === '' ) === []
	&& array_filter( $lines, static fn( string $line ): bool => str_contains( $line, 'ada@example.org' ) === true || str_contains( $line, 'Lovelace' ) === true ) === [] && str_contains( $lines['members/save'], $id ) === true
	&& \Nino\Modules\Members\Admin::log( 'members/save', [ 'id' => '<script>' ] ) === \Nino\Modules\Members\Admin::log( 'members/save', [] ) );

// signout
$epochs = [];
[ $status, $body ] = ninoCall( $appData, $panel, [ 'all' => true ], 'members/signout' );
$epochs[] = membersFile( $appData, 'session' )['epoch'] ?? '';
ninoCall( $appData, $panel, [ 'all' => true ], 'members/signout' );
$epochs[] = membersFile( $appData, 'session' )['epoch'] ?? '';
check( 'signout writes a new epoch of 32 hex characters each time it is pressed', $status === 200 && $body === [ 'signedOut' => true ] && array_filter( $epochs, static fn( string $epoch ): bool => preg_match( '/^[0-9a-f]{32}$/D', $epoch ) !== 1 ) === []
	&& $epochs[0] !== $epochs[1] && array_keys( membersFile( $appData, 'session' ) ) === [ 'epoch' ] );

// signout of one account: its sessions, devices and links end, the epoch of everybody else stays, and an id that is none is no way to sign everybody out
$one = \Nino\Modules\Members::add( $appData, 'signme@example.org', 'Sign Me', 'p' );
$oneLink = membersLink( $appData, $one );
$epochAll = membersFile( $appData, 'session' )['epoch'] ?? '';
$epochOne = membersFile( $appData, 'accounts' )['accounts'][ $one['id'] ]['epoch'] ?? '';
[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $one['id'] ], 'members/signout' );
$liveOfOne = array_filter( membersFile( $appData, 'tokens' ), static fn( array $entry ): bool => $entry['account'] === $one['id'] && $entry['expires'] > time() );
check( 'signing one member out gives that account an epoch of its own and ends the links it has out - and leaves the epoch of everybody else as it was', $oneLink !== '' && $status === 200 && $body === [ 'signedOut' => true ]
	&& ( membersFile( $appData, 'accounts' )['accounts'][ $one['id'] ]['epoch'] ?? '' ) !== $epochOne && preg_match( '/^[0-9a-f]{32}$/D', (string) ( membersFile( $appData, 'accounts' )['accounts'][ $one['id'] ]['epoch'] ?? '' ) ) === 1
	&& $liveOfOne === [] && ( membersFile( $appData, 'session' )['epoch'] ?? '' ) === $epochAll );
$unknownOut = [];

foreach( [ str_repeat( 'a', 12 ), 'not-an-id', '', [ 'x' ] ] as $bad ) {
	[ $status, $body ] = ninoCall( $appData, $panel, [ 'id' => $bad ], 'members/signout' );
	$unknownOut[] = [ $status, $body['code'] ?? '' ];
}

check( '...an id nobody has, or that is none, is a 404 and no sign-out of everybody', $unknownOut === array_fill( 0, 4, [ 404, 'members_unknown' ] ) && ( membersFile( $appData, 'session' )['epoch'] ?? '' ) === $epochAll );

// Everybody is signed out when it is asked for - all true - and by nothing else: a post that is broken, empty or spelled otherwise, and one that asks for everybody and a member, are a 400
$vague = [];

foreach( [ 'not json', '[]', '{"ID":"x"}', '{"all":false}', '{"all":"true"}', '{"all":1}', '{"all":true,"id":"'. $one['id']. '"}', 'null', '"all"' ] as $raw ) {
	$_POST = [ 'action' => 'members/signout', 'data' => $raw ];
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$panel( $appData, $request );
	$vague[] = $request['/nino/http/response']['statusCode'];
}

check( 'a post that does not say, with all true, whom to sign out signs nobody out: not json, an empty list, a word spelled otherwise, all that is not true, and all with an id are each a 400 - and the epoch of everybody is the one it was', $vague === array_fill( 0, 9, 400 ) && ( membersFile( $appData, 'session' )['epoch'] ?? '' ) === $epochAll );

echo "\n";


// --- The request for a code ----------------------------------------------------

echo "Modules\\Members - the request for a code: the same answer for every address, and the caps\n";

// The accounts of the rest of the test: one that signs in, one that is switched off, one of no name
unset( $appData['./nino/auth/current'] );
\Nino\Filesystem::putFileContent( $appData, '/data/members.php', [] );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
$ada = \Nino\Modules\Members::add( $appData, 'ada@example.org', 'Ada Lovelace', 'staff' );
$off = \Nino\Modules\Members::add( $appData, 'off@example.org', 'Off', '' );
$bare = \Nino\Modules\Members::add( $appData, 'bare@example.org', '', '' );
\Nino\Modules\Members::change( $appData, $off['id'], [ 'active' => false ] );
$appData['/nino/http/routes']['GET://'] = [ 'uri' => '/home', 'body' => 'HOME' ];

membersFresh( $appData );
membersVisitor();
$mails = [];
$before = time();
$unknown = membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'nobody@example.org', 'return' => '/join' ] );
$mailsUnknown = count( $mails );
$tokensUnknown = membersFile( $appData, 'tokens' );
$pendingUnknown = membersPending( $appData ) ?? [];
$known = membersServe( $appData, '/.members/login', 'POST', [ 'email' => ' ADA@Example.org ', 'return' => '/join' ] );
$tokens = membersFile( $appData, 'tokens' );
$pending = membersPending( $appData ) ?? [];
$shown = membersCodeOf( $mails[0] ?? [] );
$digits = str_replace( ' ', '', $shown );
$entry = $tokens[ hash( 'sha256', ( $pending['nonce'] ?? '' ). '|'. $digits ) ] ?? [];

check( 'a request for an address nobody has and one for a member\'s answer the same: a redirect (303), back to the page the form was on with ?members=sent, stored by no one',
	membersAnswer( $unknown ) === [ 303, '/join?members=sent', 'no-store', '' ] && membersAnswer( $known ) === membersAnswer( $unknown ) );
check( '...and only the member\'s leaves a mail and a record of a code: nothing for the address nobody has', $mailsUnknown === 0 && $tokensUnknown === [] && count( $mails ) === 1 && count( $tokens ) === 1 );
check( '...but the session keeps a request for both - the address as the form made it, a nonce of 32 hex characters, no try used, the time of the setting\'s minutes - and the second replaces the first',
	array_keys( $pendingUnknown ) === [ 'email', 'nonce', 'tries', 'until' ] && $pendingUnknown['email'] === 'nobody@example.org' && array_keys( $pending ) === [ 'email', 'nonce', 'tries', 'until' ] && $pending['email'] === 'ada@example.org'
	&& preg_match( '/^[0-9a-f]{32}$/D', $pending['nonce'] ) === 1 && preg_match( '/^[0-9a-f]{32}$/D', $pendingUnknown['nonce'] ) === 1 && $pending['nonce'] !== $pendingUnknown['nonce'] && $pending['tries'] === 0
	&& $pending['until'] >= $before + 900 - 2 && $pending['until'] <= time() + 900 + 2 );
check( '...the mail is to the address of the account and holds six digits, set as two groups of three, and no link; the record of the code is under the hash of the nonce and the digits - of the kind code, for that account, good until the request is, with no page to lead to - and nothing of the nonce or of the digits alone is in the file',
	$mails[0]['to'] === 'ada@example.org' && preg_match( '/^[0-9]{3} [0-9]{3}$/D', $shown ) === 1 && str_contains( (string) $mails[0]['body'], '.members/login' ) === false && str_contains( (string) $mails[0]['body'], 'href=' ) === false
	&& array_keys( $entry ) === [ 'account', 'kind', 'expires', 'target' ] && $entry['account'] === $ada['id'] && $entry['kind'] === 'code' && $entry['target'] === '' && $entry['expires'] === $pending['until']
	&& isset( $tokens[ hash( 'sha256', $digits ) ] ) === false && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members-tokens.php' ) ), $pending['nonce'] ) === false );
check( '...the subject is the one of the code mail, rendered', trim( (string) $mails[0]['subject'] ) !== '' && str_contains( (string) $mails[0]['subject'], '[[' ) === false && str_contains( (string) $mails[0]['body'], '[[' ) === false );

$off1 = membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'off@example.org', 'return' => '/join' ] );
check( 'a member who is switched off is answered like the others - and gets nothing, and the session keeps the request all the same', membersAnswer( $off1 ) === membersAnswer( $unknown ) && count( $mails ) === 1 && count( membersFile( $appData, 'tokens' ) ) === 1
	&& ( membersPending( $appData )['email'] ?? '' ) === 'off@example.org' );

// What a post can hold that is no address, and no string: the same answer, no warning, nothing sent
membersFresh( $appData );
ninoWarnings();
$odd = [];

foreach( [ 'nothing' => [], 'a list' => [ 'email' => [ 'ada@example.org' ] ], 'a text' => [ 'email' => 'ada' ], 'two' => [ 'email' => 'ada@example.org, off@example.org' ], 'long' => [ 'email' => str_repeat( 'a', 300 ). '@example.org' ] ] as $kind => $post ) {
	$request = membersServe( $appData, '/.members/login', 'POST', $post + [ 'return' => [ '/join' ] ] );
	$odd[$kind] = membersAnswer( $request );
}

check( 'a post without an address, with a list for the address or the return, or with a text that is none is answered the same, without a warning and without a mail - and it ends the request that was out: the session holds none', array_values( array_unique( array_map( 'json_encode', $odd ) ) ) === [ '[303,"\\/?members=sent","no-store",""]' ]
	&& ninoWarnings() === [] && count( $mails ) === 1 && membersPending( $appData ) === null );

// Where the answer goes back to: a path of this site and nothing else
membersFresh( $appData );
$goes = [];

foreach( [ '/club/page?x=1#f' => '/club/page?members=sent', '//evil.example/x' => '/?members=sent', '/\\evil.example' => '/?members=sent', 'https://evil.example' => '/?members=sent', '/javascript:alert(1)' => '/?members=sent',
	"/a\r\nSet-Cookie: x=1" => '/?members=sent', '/a b' => '/?members=sent', "/a\r\nb" => '/?members=sent', "/a\tb" => '/?members=sent', "/a\x00b" => '/?members=sent', "/a\x7fb" => '/?members=sent', '/.members/login' => '/?members=sent', '' => '/?members=sent', 'relative/path' => '/?members=sent', '/a/b:c' => '/a/b:c?members=sent' ] as $return => $expected ) {
	membersFresh( $appData );
	$goes[$return] = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'nobody@example.org', 'return' => $return ] ) )[1] === $expected;
}

check( 'the answer goes back to the page the form was on without its query - and to the start page for anything that is not a path of this site, an endpoint among it', array_filter( $goes, static fn( bool $same ): bool => $same === false ) === [] );

$codeGoes = [];

foreach( [ '//evil.example/x', 'https://evil.example', '/\\evil.example', "/a\r\nSet-Cookie: x=1", '/.members/login', '', '/club/page?x=1#f' ] as $return ) {
	membersVisitor();
	$digits = membersAskCode( $appData, 'ada@example.org' );
	$codeGoes[$return] = membersAnswer( membersServe( $appData, '/.members/code', 'POST', [ 'code' => sprintf( '%06d', ( (int) $digits + 1 ) % 1000000 ), 'return' => $return ] ) )[1];
}

check( 'the answer to a code goes back to a path of this site and nothing else: another host, a scheme, a backslash, a header break and an endpoint are the start page', $codeGoes === [ '//evil.example/x' => '/?members=wrong', 'https://evil.example' => '/?members=wrong', '/\\evil.example' => '/?members=wrong',
	"/a\r\nSet-Cookie: x=1" => '/?members=wrong', '/.members/login' => '/?members=wrong', '' => '/?members=wrong', '/club/page?x=1#f' => '/club/page?members=wrong' ] );

check( 'the start page is a setting that is a path of this site: a path is taken, an address of another host, a protocol-relative one and a text with a space are refused',
	\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/club' ] ) === [] && array_filter( [ 'https://evil.example', '//evil.example', '/\\evil.example', '/a b', 'club', '/javascript:x', "/a\nb", '/a?b' ],
		static fn( string $value ): bool => \Nino\Features::saveSettings( $appData, 'members', [ 'start' => $value ] ) === [] ) === [] && \Nino\Features::setting( $appData, 'members', 'start', '' ) === '/club' );
membersFresh( $appData );
$start = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'nobody@example.org', 'return' => '//evil.example' ] ) );

// The field cleared and an endpoint of a module are settings the manifest lets through, and no start page: the front page is
$elsewhere = [];

foreach( [ '', '/.members/logout' ] as $value ) {
	\Nino\Features::saveSettings( $appData, 'members', [ 'start' => $value ] );
	membersFresh( $appData );
	$elsewhere[$value] = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'nobody@example.org', 'return' => '//evil.example' ] ) )[1];
}

\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/' ] );
check( '...and the start page is where an answer goes that has no page of its own to go back to - the front page where the setting is empty or none', $start[1] === '/club?members=sent'
	&& $elsewhere === [ '' => '/?members=sent', '/.members/logout' => '/?members=sent' ] );

// The page that asked for the login: kept by the gate, taken into the record of the code, and gone from the session
\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ] ] );
membersFresh( $appData );
membersVisitor();
$mails = [];
membersFresh( $appData );
membersServe( $appData, '/club/news' );
membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/club/news' ] );
$withTarget = membersFile( $appData, 'tokens' )[ hash( 'sha256', ( membersPending( $appData )['nonce'] ?? '' ). '|'. str_replace( ' ', '', membersCodeOf( $mails[0] ?? [] ) ) ) ] ?? [];
$kept = \Nino\Runtime::getSessionValue( $appData, './members/target' );
membersFresh( $appData );
membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/news' ] );
$without = membersFile( $appData, 'tokens' )[ hash( 'sha256', ( membersPending( $appData )['nonce'] ?? '' ). '|'. str_replace( ' ', '', membersCodeOf( $mails[1] ?? [] ) ) ) ] ?? [];
check( 'the path a protected page was asked for is the target of the next code, and is gone from the session once a code carries it - the code after that has none', ( $withTarget['target'] ?? null ) === '/club/news' && $kept === null && ( $without['target'] ?? null ) === '' );
\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [] ] );

// The csrf guard: no token, no request
membersFresh( $appData );
membersVisitor();
$mails = [];
membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'nobody@example.org', 'return' => '/join' ] );
$pendingBefore = membersPending( $appData );
membersFresh( $appData );
$mails = [];
$tokensBefore = membersFile( $appData, 'tokens' );
$none = membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/join' ], false );
$wrong = membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/join', '_csrf' => 'not-the-token' ], false );
check( 'a request without the csrf token - or with another - is answered with a redirect (303) to the page the form was on and ?members=expired, in place of the kernel\'s bare 403, sends no mail, makes no record of a code, leaves the request of the session as it is and is not counted', membersAnswer( $none ) === [ 303, '/join?members=expired', 'no-store', '' ] && membersAnswer( $wrong ) === membersAnswer( $none ) && $mails === []
	&& membersFile( $appData, 'tokens' ) === $tokensBefore && membersFile( $appData, 'tries' ) === [] && $pendingBefore !== null && membersPending( $appData ) === $pendingBefore );

// The cap of the visitor: attempts an hour, then ?members=limit and nothing else
membersFresh( $appData );
$mails = [];
$tokensBefore = membersFile( $appData, 'tokens' );
$limits = [];

for( $try = 1; $try <= 6; $try++ ) {
	$limits[$try] = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => $try === 6 ? 'ada@example.org' : 'nobody'. $try. '@example.org', 'return' => '/join' ] ) )[1];
}

check( 'the sixth request of one visitor in an hour - the setting attempts says five - is answered ?members=limit, and leaves no record of a code and no mail, a member\'s address or not',
	array_values( array_slice( $limits, 0, 5 ) ) === array_fill( 0, 5, '/join?members=sent' ) && $limits[6] === '/join?members=limit' && $mails === [] && membersFile( $appData, 'tokens' ) === $tokensBefore );
check( '...the counter has a file of its own, hashed - the accounts are what they were, and neither the address of the visitor nor an email address is written anywhere in it',
	count( membersFile( $appData, 'accounts' )['accounts'] ) === 3 && count( membersFile( $appData, 'tries' ) ) === 6 && str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members-tries.php' ) ), '127.0.0.1' ) === false
	&& str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members-tries.php' ) ), 'example.org' ) === false );

\Nino\Features::saveSettings( $appData, 'members', [ 'attempts' => 2 ] );
membersFresh( $appData );
$twice = [];

for( $try = 1; $try <= 3; $try++ )
	$twice[$try] = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'nobody'. $try. '@example.org', 'return' => '/join' ] ) )[1];

check( '...the number is the setting\'s', $twice === [ 1 => '/join?members=sent', 2 => '/join?members=sent', 3 => '/join?members=limit' ] );

// The cap of the address: three an hour, whoever asks
\Nino\Features::saveSettings( $appData, 'members', [ 'attempts' => 50 ] );
membersFresh( $appData );
$mails = [];
$asked = [];

foreach( [ '10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4' ] as $address ) {
	$_SERVER['REMOTE_ADDR'] = $address;
	$asked[] = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/join' ] ) )[1];
}

$out = [ count( $mails ), array_values( array_unique( array_column( $mails, 'to' ) ) ) ];
$_SERVER['REMOTE_ADDR'] = '10.0.0.5';
$other = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'bare@example.org', 'return' => '/join' ] ) )[1];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
check( 'the fourth request for one address in an hour is answered ?members=limit, whoever asks and from wherever - three codes are out, and another address still gets its own',
	$asked === [ '/join?members=sent', '/join?members=sent', '/join?members=sent', '/join?members=limit' ] && $out === [ 3, [ 'ada@example.org' ] ] && $other === '/join?members=sent' && count( $mails ) === 4 );
\Nino\Features::saveSettings( $appData, 'members', [ 'attempts' => 5 ] );

// A mail that is not taken: the same answer, and a warning for the log. A site without an address mails its code all the same - there is
// no link to build - and cannot make a link
membersFresh( $appData );
membersVisitor();
$mails = [];
$delivery = false;
ninoWarnings();
membersServe( $appData, '/club/news' );
$refused = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/join' ] ) );
$delivery = true;
$warned = ninoWarnings();
\Nino\Html::removeFill( $appData, '/project/website/general/url', '*' );
\Nino\Html::removeFill( $appData, '/project/website/general/url', 'de_DE' );
$appData['./nino/html/fills']['*']['[[/project/website/general/url]]'] = '';
membersFresh( $appData );
$mails = [];
$noAddress = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'ada@example.org', 'return' => '/join' ] ) );
$warnedNoAddress = ninoWarnings();
$codeNoAddress = membersCodeOf( $mails[0] ?? [] );
membersFresh( $appData );
$linkNoAddress = \Nino\Modules\Members::link( $appData, $ada, 'login' );
$warnedLink = ninoWarnings();
\Nino\Html::addFills( $appData, [ '[[/project/website/general/url]]' => 'example.org/club' ], '*' );
check( 'a mail that no transport takes is swallowed into the same answer, with a warning for the log that names no address', $refused === [ 303, '/join?members=sent', 'no-store', '' ] && count( $warned ) >= 1
	&& array_filter( $warned, static fn( string $warning ): bool => str_contains( $warning, 'ada@example.org' ) === true ) === [] );
check( '...a site that has no address mails the code all the same, with no warning - and has no address to build a link on: nothing is sent for one, a warning', $noAddress === [ 303, '/join?members=sent', 'no-store', '' ] && count( $mails ) === 1 && preg_match( '/^[0-9]{3} [0-9]{3}$/D', $codeNoAddress ) === 1
	&& $warnedNoAddress === [] && $linkNoAddress === false && count( $warnedLink ) === 1 );

echo "\n";


// --- The code that signs in ----------------------------------------------------

echo "Modules\\Members - the code that signs in: bound to the session that asked, once, five tries, in time\n";

$uid = $appData['./nino/uid'];
\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ] ] );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );

// The way in: a code asked for from a protected page leads back to it, and the space the mail sets in the middle is no part of it
membersVisitor();
$mails = [];
$told = [];
membersServe( $appData, '/club/news' );
$digits = membersAskCode( $appData, 'ada@example.org', '/club/news' );
$nonce = (string) ( membersPending( $appData )['nonce'] ?? '' );
$csrfBefore = \Nino\Csrf::getToken( $appData );
$stamp = time();
$through = membersCodeServe( $appData, substr( $digits, 0, 3 ). ' '. substr( $digits, 3 ), '/club/news' );
$member = \Nino\Modules\Members::current( $appData );
$record = membersFile( $appData, 'accounts' )['accounts'][ $ada['id'] ] ?? [];
check( 'the code, typed with its space, signs the member in and sends them on (303) to the page that asked for the login, stored by no one', $through === [ 303, '/club/news', 'no-store', '' ] && ( $member['id'] ?? '' ) === $ada['id'] && strlen( $digits ) === 6 );
check( '...the record of the code is spent, the request is gone from the session, which holds the account and the epoch and has a csrf token of its own now, and the time of the sign-in is stamped on the account',
	isset( membersFile( $appData, 'tokens' )[ hash( 'sha256', $nonce. '|'. $digits ) ] ) === false && membersPending( $appData ) === null
	&& \Nino\Runtime::getSessionValue( $appData, './members/account' ) === [ 'id' => $ada['id'], 'epoch' => membersFile( $appData, 'session' )['epoch'] ?? '' ] && \Nino\Csrf::getToken( $appData ) !== $csrfBefore
	&& abs( strtotime( (string) ( $record['login'] ?? '' ) ) - $stamp ) < 10 && ( $member['login'] ?? null ) === ( $record['login'] ?? '' ) );
check( '...and the features that build on it are told, once, with the account', count( $told ) === 1 && $told[0][0] === 'login' && $told[0][1] === $member );

// Without a page to lead to the way is the start page. Six digits, set as the mail sets them, always: a code that starts with a zero is no shorter, and is taken as it is shown or without its space
$formats = [];
$zero = '';
$nonZero = '';

// The range of the code is the six digits: some code does not start with a zero
for( $round = 0; $round < 100 && $nonZero === ''; $round++ ) {
	membersVisitor();
	$candidate = membersAskCode( $appData, 'ada@example.org' );
	$formats[] = membersCodeOf( end( $mails ) ?: [] );

	if( $candidate !== '' && str_starts_with( $candidate, '0' ) === false )
		$nonZero = $candidate;
}

for( $round = 0; $round < 300 && $zero === ''; $round++ ) {
	membersVisitor();
	$candidate = membersAskCode( $appData, 'ada@example.org' );
	$formats[] = membersCodeOf( end( $mails ) ?: [] );

	if( str_starts_with( $candidate, '0' ) === true )
		$zero = $candidate;
}

$started = membersCodeServe( $appData, ' '. $zero, '/join' );
check( 'the code is six digits whatever they are, set as two groups of three - and one that starts with a zero signs in, typed without its space', $zero !== '' && $nonZero !== '' && array_filter( $formats, static fn( string $shown ): bool => preg_match( '/^[0-9]{3} [0-9]{3}$/D', $shown ) !== 1 ) === []
	&& $started === [ 303, '/', 'no-store', '' ] && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $ada['id'] );

// Once: the same code again is gone, and a code of an earlier request is no code of this one
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$first = membersCodeServe( $appData, $digits );
$again = membersCodeServe( $appData, $digits );
membersVisitor();
membersAskCode( $appData, 'ada@example.org' );
$replayed = membersCodeServe( $appData, $digits );
check( 'a code works once: sent again from the session it signed in, it is gone; in a session with a request of its own, it is wrong - and nobody is signed in by it', $first[1] === '/' && $again === [ 303, '/join?members=gone', 'no-store', '' ] && $replayed === [ 303, '/join?members=wrong', 'no-store', '' ]
	&& \Nino\Modules\Members::current( $appData ) === null );

// Five tries: four wrong ones are told, and the fifth try may still be the right one
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$bad = sprintf( '%06d', ( (int) $digits + 1 ) % 1000000 );
$wrongs = [];
$counted = [];
$triesBefore = membersFile( $appData, 'tries' );

for( $try = 1; $try <= 4; $try++ ) {
	$wrongs[] = membersCodeServe( $appData, $bad );
	$counted[] = membersPending( $appData )['tries'] ?? null;
}

$fifth = membersCodeServe( $appData, $digits );
check( 'a wrong code is told ?members=wrong and counted in the session - the fifth try, if it is the right code, signs in; and none of it is counted by the counter of the requests',
	array_values( array_unique( array_map( 'json_encode', $wrongs ) ) ) === [ '[303,"\\/join?members=wrong","no-store",""]' ] && count( $wrongs ) === 4 && $counted === [ 1, 2, 3, 4 ] && $fifth[1] === '/'
	&& ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $ada['id'] && membersFile( $appData, 'tries' ) === $triesBefore );

// ...and the fifth wrong one ends the request: the right code goes nowhere after it, and a new one has to be asked for
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$bad = sprintf( '%06d', ( (int) $digits + 1 ) % 1000000 );
$answers = [];

for( $try = 1; $try <= 5; $try++ )
	$answers[] = membersCodeServe( $appData, $bad )[1];

$sixth = membersCodeServe( $appData, $digits );
check( 'the fifth wrong code ends the request (?members=gone, none in the session) and the right code is gone as well, nobody is signed in', $answers === [ '/join?members=wrong', '/join?members=wrong', '/join?members=wrong', '/join?members=wrong', '/join?members=gone' ]
	&& membersPending( $appData ) === null && $sixth === [ 303, '/join?members=gone', 'no-store', '' ] && \Nino\Modules\Members::current( $appData ) === null );
ninoVisit( $appData, '/join?members=gone' );
check( '...and the form on the page is the first step again, with the message that the code is gone', count( membersFind( \Nino\Html::renderHtml( $appData, '[members-login]' ), '//form[contains(@class, "members-login")]//input[@name="email"]' ) ) === 1
	&& trim( membersText( \Nino\Html::renderHtml( $appData, '[members-login]' ), '//form/p' ) ?? '' ) === trim( $states['gone'][0]->textContent ) );

// What is no code: seven digits, letters, a null byte, a list, nothing - each is a wrong try, nothing is spent, no warning
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$nonce = (string) ( membersPending( $appData )['nonce'] ?? '' );
ninoWarnings();
$malformed = [];
$malformedTries = [];

foreach( [ 'seven digits' => $digits. '0', 'letters' => 'abcdef', 'a null byte' => substr( $digits, 0, 3 ). "\x00". substr( $digits, 3 ), 'a list' => [ $digits ] ] as $kind => $typed ) {
	$malformed[$kind] = membersAnswer( membersServe( $appData, '/.members/code', 'POST', [ 'code' => $typed, 'return' => '/join' ] ) );
	$malformedTries[] = membersPending( $appData )['tries'] ?? null;
}

check( 'a code of seven digits, one with letters, one with a null byte in it and a list for the code are told wrong and each counts a try - without a warning, and the record of the code is still there', array_values( array_unique( array_map( 'json_encode', $malformed ) ) ) === [ '[303,"\\/join?members=wrong","no-store",""]' ]
	&& count( $malformed ) === 4 && $malformedTries === [ 1, 2, 3, 4 ] && ninoWarnings() === [] && isset( membersFile( $appData, 'tokens' )[ hash( 'sha256', $nonce. '|'. $digits ) ] ) === true && \Nino\Modules\Members::current( $appData ) === null );
check( '...and the code itself, five tries in, is still taken', membersCodeServe( $appData, $digits )[1] === '/' && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $ada['id'] );

// Bound to the session: a code from another session is nothing - with no request there it is gone, with a request of its own it is wrong - and the session that asked still has it
membersVisitor();
$digitsA = membersAskCode( $appData, 'ada@example.org' );
$sessionA = $_SESSION;
membersVisitor();
$elsewhere = membersCodeServe( $appData, $digitsA );
$digitsB = $digitsA;

for( $round = 0; $round < 20 && $digitsB === $digitsA; $round++ )
	$digitsB = membersAskCode( $appData, 'ada@example.org' );

check( 'the session that asked second holds a code of its own, other than the first\'s', $digitsB !== $digitsA && $digitsB !== '' );

$elsewhereAsked = membersCodeServe( $appData, $digitsA );
$signedElsewhere = \Nino\Modules\Members::current( $appData );
$_SESSION = $sessionA;
$home = membersCodeServe( $appData, $digitsA );
check( 'a code from another session signs nobody in: in a session with no request it is gone, in one that asked for a code of its own it is wrong', $elsewhere === [ 303, '/join?members=gone', 'no-store', '' ] && $elsewhereAsked === [ 303, '/join?members=wrong', 'no-store', '' ] && $signedElsewhere === null );
check( '...and in the session that asked it still signs in', $home[1] === '/' && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $ada['id'] );

// In time: a request that has run out is gone, and so is a code whose record has
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$_SESSION[$uid]['./members/pending']['until'] = time() - 1;
$late = membersCodeServe( $appData, $digits );
$pendingLate = membersPending( $appData );
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$ran = membersFile( $appData, 'tokens' );

foreach( $ran as $key => $each )
	$ran[$key]['expires'] = time() - 1;

\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $ran );
$lateRecord = membersCodeServe( $appData, $digits );
check( 'a code asked for longer ago than the setting says is gone, even the right one - and so is one whose record has run out, which no code is found for', $late === [ 303, '/join?members=gone', 'no-store', '' ] && $pendingLate === null
	&& $lateRecord === [ 303, '/join?members=wrong', 'no-store', '' ] && \Nino\Modules\Members::current( $appData ) === null );

// The csrf guard: a code posted without the token uses nothing up, and the request stays
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$pendingBefore = membersPending( $appData );
$tokensBefore = membersFile( $appData, 'tokens' );
$blocked = membersCodeServe( $appData, $digits, '/join', false );
check( 'a code posted without the csrf token is answered ?members=expired and uses nothing up: the request and the record of the code are as they were, nobody is signed in - and the code is taken with the token', $blocked === [ 303, '/join?members=expired', 'no-store', '' ]
	&& membersPending( $appData ) === $pendingBefore && membersFile( $appData, 'tokens' ) === $tokensBefore && \Nino\Modules\Members::current( $appData ) === null && membersCodeServe( $appData, $digits )[1] === '/' );

// With no request at all
membersVisitor();
$unasked = membersCodeServe( $appData, '123456' );
check( 'a code posted from a session that asked for none is gone, and nothing is signed in', $unasked === [ 303, '/join?members=gone', 'no-store', '' ] && \Nino\Modules\Members::current( $appData ) === null );

// The account is looked at when the code is spent: one that was switched off, or deleted, since the mail went out gets nowhere - the code is spent all the same
$told = [];
membersVisitor();
$digits = membersAskCode( $appData, 'ada@example.org' );
$nonce = (string) ( membersPending( $appData )['nonce'] ?? '' );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => false ] );
$switchedOff = membersCodeServe( $appData, $digits );
$offSpent = isset( membersFile( $appData, 'tokens' )[ hash( 'sha256', $nonce. '|'. $digits ) ] ) === false;
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => true ] );
$temporary = \Nino\Modules\Members::add( $appData, 'temporary@example.org', 'Temporary', '' );
membersVisitor();
$digits = membersAskCode( $appData, 'temporary@example.org' );
\Nino\Modules\Members::remove( $appData, $temporary['id'] );
$deleted = membersCodeServe( $appData, $digits );
check( 'a code for an account that was deleted after the mail is answered ?members=gone, one for an account that was switched off is a code that is wrong (switching off deletes the codes and links out) - and nobody is signed in, nobody is told, nothing is left of the code', $switchedOff === [ 303, '/join?members=wrong', 'no-store', '' ] && $deleted === [ 303, '/join?members=gone', 'no-store', '' ] && $offSpent === true
	&& \Nino\Modules\Members::current( $appData ) === null && $told === [] && membersPending( $appData ) === null );

// A code is no link and a link is no code: the record of a code cannot be reached with ?t=, and the links stay links
membersVisitor();
$mails = [];
membersFresh( $appData );
$digits = membersAskCode( $appData, 'ada@example.org' );
$recordHash = hash( 'sha256', (string) ( membersPending( $appData )['nonce'] ?? '' ). '|'. $digits );
$tokensBefore = membersFile( $appData, 'tokens' );
$reached = [];

foreach( [ 'the digits' => $digits, 'the hash' => $recordHash, 'a token long enough' => substr( $recordHash, 0, 43 ) ] as $kind => $typed ) {
	$reached[$kind. ', asked'] = membersAnswer( membersServe( $appData, '/.members/login?t='. $typed ) )[0];
	$reached[$kind. ', pressed'] = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $typed ] ) )[0];
}

$linkAsked = \Nino\Modules\Members::link( $appData, $ada, 'code' );
check( 'the record of a code is out of reach of the link: its digits, its hash and a part of it asked for as ?t= or pressed are 400, nothing is spent and nobody is signed in - and link() makes no code', array_values( array_unique( $reached ) ) === [ 400 ] && count( $reached ) === 6
	&& membersFile( $appData, 'tokens' ) === $tokensBefore && \Nino\Modules\Members::current( $appData ) === null && $linkAsked === false );

membersFresh( $appData );
$count = count( $mails );
$loginLink = \Nino\Modules\Members::link( $appData, $ada, 'login' );
$inviteLink = \Nino\Modules\Members::link( $appData, $ada, 'invite' );
check( 'the link of an invitation and the login link the owner sends stay links - a mail with the address of the page that asks, and no code in it',
	$loginLink === true && $inviteLink === true && count( $mails ) === $count + 2 && membersTokenOf( $mails[$count] ) !== '' && membersTokenOf( $mails[$count + 1] ) !== '' && membersCodeOf( $mails[$count] ) === '' && membersCodeOf( $mails[$count + 1] ) === '' );

\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [] ] );
membersVisitor();
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );

echo "\n";


// --- Following the link --------------------------------------------------------

echo "Modules\\Members - following the link: a page asks, the button signs in, and it is spent\n";

// Nobody has signed in yet: there is no epoch, and the first sign-in is what writes one
@unlink( \Nino\Filesystem::path( $appData, '/data/members-session.php' ) );
unset( $appData['./nino/filesystem/cache']['/data/members-session.php'] );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
membersVisitor();
$mails = [];
$told = [];
$token = membersLink( $appData, $ada );
$withTarget = membersLink( $appData, $ada, 'login', '/club/news' );
$tokensBefore = membersFile( $appData, 'tokens' );

// Following the link only asks: a mail scanner, a browser that fetches ahead and a HEAD follow links too
$opened = membersServe( $appData, '/.members/login?t='. $token );
$page = \Nino\Html::renderHtml( $appData, (string) membersAnswer( $opened )[3] );
$pageWithTarget = \Nino\Html::renderHtml( $appData, (string) membersAnswer( membersServe( $appData, '/.members/login?t='. $withTarget ) )[3] );
$headed = membersServe( $appData, '/.members/login?t='. $token, 'HEAD' );

check( 'following the link only asks: the page that names the account is answered (200, stored by no one, headed by the details of the start page, with no referrer) - and the tokens are in the file as they were, nobody is signed in, no epoch is written, nobody is told',
	membersAnswer( $opened ) === [ 200, '', 'no-store', '[template /templates/page-members-confirm]' ] && $opened['/nino/http/response']['header']['Referrer-Policy'] === 'no-referrer' && $opened['/nino/http/response']['uri'] === '/.members'
	&& membersFile( $appData, 'tokens' ) === $tokensBefore && \Nino\Modules\Members::current( $appData ) === null && membersFile( $appData, 'session' ) === [] && $told === [] );
check( '...so does a HEAD for it, which is folded to a GET: the same answer and nothing used up', membersAnswer( $headed ) === membersAnswer( $opened ) && $headed['/nino/http/request']['rawMethod'] === 'HEAD'
	&& membersFile( $appData, 'tokens' ) === $tokensBefore && \Nino\Modules\Members::current( $appData ) === null );

$confirmForm = membersFind( $page, '//form[contains(@class, "members-confirm")]' );
$confirmFields = [];

foreach( membersFind( $page, '//form//input' ) as $field )
	$confirmFields[ $field->getAttribute( 'name' ) ] = $field->getAttribute( 'value' );

check( '...and the page is a whole one: a title, and one form that names the account and posts the token with the csrf token of the session - and nothing of where the member goes after it',
	count( membersFind( $page, '//h1' ) ) === 1 && membersText( $page, '//h1' ) !== '' && count( $confirmForm ) === 1 && strtolower( $confirmForm[0]->getAttribute( 'method' ) ) === 'post' && $confirmForm[0]->getAttribute( 'action' ) === '/.members/confirm'
	&& in_array( 'nino-form', membersClasses( $confirmForm[0] ), true ) === false && membersText( $page, '//p[contains(@class, "members-confirm-name")]' ) === 'Ada Lovelace'
	&& array_keys( $confirmFields ) === [ 'token', '_csrf' ] && $confirmFields['token'] === $token && $confirmFields['_csrf'] === \Nino\Csrf::getToken( $appData ) && count( membersFind( $page, '//form//button[@type="submit"]' ) ) === 1
	&& count( membersFind( $page, '//form/p[contains(@class, "members-login-state")]' ) ) === 0 && str_contains( $pageWithTarget, '/club/news' ) === false && str_contains( $pageWithTarget, 'return' ) === false && str_contains( $page, '[[' ) === false );

// The csrf guard: a button pressed with a session that is not the one the page was drawn for uses nothing up, and the page is there again
$blocked = membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $token ], false );
$blockedPage = \Nino\Html::renderHtml( $appData, (string) membersAnswer( $blocked )[3] );
check( 'a button pressed without the csrf token uses nothing up: the page is answered again (403, with no referrer) with the message that the session has run out, a form with the token of the session as it is now - instead of the kernel\'s bare 403',
	membersAnswer( $blocked ) === [ 403, '', 'no-store', '[template /templates/page-members-confirm]' ] && $blocked['/nino/http/response']['header']['Referrer-Policy'] === 'no-referrer'
	&& membersText( $blockedPage, '//form/p[contains(@class, "members-login-state")]' ) === trim( $states['expired'][0]->textContent ) && membersText( $blockedPage, '//p[contains(@class, "members-confirm-name")]' ) === 'Ada Lovelace'
	&& membersFile( $appData, 'tokens' ) === $tokensBefore && \Nino\Modules\Members::current( $appData ) === null && $told === [] );

$csrfBefore = \Nino\Csrf::getToken( $appData );
$stamp = time();
$followed = membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $token ] );
$signedIn = \Nino\Modules\Members::current( $appData );
$record = membersFile( $appData, 'accounts' )['accounts'][ $ada['id'] ] ?? [];

check( 'pressing the button signs the member in and sends them on (303) to the start page, with no state on it', membersAnswer( $followed ) === [ 303, '/', 'no-store', '' ] && $signedIn !== null && $signedIn['id'] === $ada['id'] );
check( '...the time of the sign-in is stamped on the account, in UTC, and is the one the module reads', preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', (string) ( $record['login'] ?? '' ) ) === 1 && abs( strtotime( (string) $record['login'] ) - $stamp ) < 10
	&& ( $signedIn['login'] ?? null ) === $record['login'] && $record['email'] === 'ada@example.org' );
check( '...the token is spent: the file of the links holds nothing of it any more - and the other link is still there', membersFile( $appData, 'tokens' ) === array_diff_key( $tokensBefore, [ hash( 'sha256', $token ) => 1 ] ) && count( membersFile( $appData, 'tokens' ) ) === 1 );
check( '...the first sign-in wrote the epoch, the session holds the account and that epoch, and the csrf token of the session was replaced', preg_match( '/^[0-9a-f]{32}$/D', membersFile( $appData, 'session' )['epoch'] ?? '' ) === 1
	&& array_diff_key( (array) \Nino\Runtime::getSessionValue( $appData, './members/account' ), [ 'own' => 1 ] ) === [ 'id' => $ada['id'], 'epoch' => membersFile( $appData, 'session' )['epoch'] ] && \Nino\Csrf::getToken( $appData ) !== $csrfBefore );
check( '...and the features that build on it are told, once, with the account', count( $told ) === 1 && $told[0][0] === 'login' && $told[0][1] === $signedIn );

// Where the member goes is what the record of the token on the server says, whatever the post adds
membersVisitor();
$forged = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $withTarget, 'target' => '//evil.example', 'return' => '//evil.example', 'redirect' => '//evil.example' ] ) );
check( '...and where they are sent is the path the record of the token carries - nothing the post says about it', $forged === [ 303, '/club/news', 'no-store', '' ] && count( membersFile( $appData, 'tokens' ) ) === 0 );

membersVisitor();
$toldBefore = count( $told );
$againPage = membersServe( $appData, '/.members/login?t='. $token );
$again = membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $token ] );
$page = \Nino\Html::renderHtml( $appData, (string) membersAnswer( $again )[3] );
check( 'the same link again - on the page and on the button - is answered with the login page in place: 400, stored by no one, headed by the details of the start page',
	membersAnswer( $againPage ) === [ 400, '', 'no-store', '[template /templates/page-members-login]' ] && membersAnswer( $again ) === membersAnswer( $againPage ) && $again['/nino/http/response']['uri'] === '/.members' && \Nino\Modules\Members::current( $appData ) === null );
check( '...and that page is a whole one: a title, the form, and the message that the link does not work, with the page it is on to go back to', count( membersFind( $page, '//h1' ) ) === 1 && membersText( $page, '//h1' ) !== ''
	&& count( membersFind( $page, '//form[contains(@class, "members-login")]' ) ) === 1 && count( membersFind( $page, '//form/p[contains(@class, "members-login-state")]' ) ) === 1
	&& membersText( $page, '//form/p[contains(@class, "members-login-state")]' ) === trim( $states['invalid'][0]->textContent ) && count( $told ) === $toldBefore );

// The member, in the three components
membersSignIn( $appData, $ada );
$member = \Nino\Html::renderHtml( $appData, '[members-login]' );
$signed = membersFind( $member, '//div[contains(@class, "members-login--signed")]' );
check( 'for a member [members-login] is their name and the form to sign out - a post to the endpoint with the csrf token and one button, no field for an address', count( $signed ) === 1 && membersClasses( $signed[0] ) === [ 'members-login', 'members-login--signed' ] && count( membersFind( $member, '//form' ) ) === 1 && count( membersFind( $member, '//input[@type="email"]' ) ) === 0
	&& trim( membersFind( $member, '//strong[contains(@class, "members-login-name")]' )[0]->textContent ) === 'Ada Lovelace'
	&& membersFind( $member, '//form' )[0]->getAttribute( 'action' ) === '/.members/logout' && strtolower( membersFind( $member, '//form' )[0]->getAttribute( 'method' ) ) === 'post'
	&& count( membersFind( $member, '//form/input[@type="hidden"][@name="_csrf"]' ) ) === 1 && count( membersFind( $member, '//form/button[@type="submit"][contains(@class, "members-logout")]' ) ) === 1 && count( membersFind( $member, '//a[contains(@class, "members-logout")]' ) ) === 0 );
$logout = membersFind( \Nino\Html::renderHtml( $appData, '[members-logout]' ), '//form' );
$name = membersFind( \Nino\Html::renderHtml( $appData, '[members-name]' ), '//span' );
check( '[members-logout] is that form and [members-name] the name', count( $logout ) === 1 && $logout[0]->getAttribute( 'action' ) === '/.members/logout' && membersClasses( $logout[0] ) === [ 'members-logout' ]
	&& count( $name ) === 1 && $name[0]->textContent === 'Ada Lovelace' && membersClasses( $name[0] ) === [ 'members-name' ] );

$hostile = [ '<img src=x onerror=alert(1)>' => 'img', '"><script>alert(1)</script>' => 'script', '[[/project/company/general/name]]' => '', '[members-logout]' => '' ];
$escaped = [];

foreach( $hostile as $name => $element ) {
	\Nino\Modules\Members::change( $appData, $ada['id'], [ 'name' => $name ] );
	$html = \Nino\Html::renderHtml( $appData, '[members-name][members-login]' );
	$asked = \Nino\Html::renderHtml( $appData, (string) membersAnswer( membersServe( $appData, '/.members/login?t='. membersLink( $appData, $ada ) ) )[3] );
	$escaped[$name] = ( $element === '' || count( membersFind( $html, '//'. $element ) ) === 0 )  && count( membersFind( $html, '//form/button[contains(@class, "members-logout")]' ) ) === 1
		&& membersFind( $html, '//span[contains(@class, "members-name")]' )[0]->textContent === $name
		&& ( $element === '' || count( membersFind( $asked, '//'. $element ) ) === 0 ) && membersText( $asked, '//p[contains(@class, "members-confirm-name")]' ) === $name;
}

check( 'a name is text wherever it is written: markup in it is not an element, and a key or a shortcode in it is neither filled in nor run', array_filter( $escaped, static fn( bool $safe ): bool => $safe === false ) === [] && count( $escaped ) === 4 );

\Nino\Modules\Members::change( $appData, $ada['id'], [ 'name' => '' ] );
check( 'an account without a name is shown by its address', membersFind( \Nino\Html::renderHtml( $appData, '[members-name]' ), '//span' )[0]->textContent === 'ada@example.org' );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'name' => 'Ada Lovelace' ] );

// A link that does not lead in, in each of the ways it can fail - asked for on the page and pressed on the button
$tokensBefore = membersFile( $appData, 'tokens' );
$told = [];
ninoWarnings();
$notValid = [];

foreach( [ 'no token' => [ '/.members/login', [] ], 'an empty one' => [ '/.members/login?t=', [ 'token' => '' ] ], 'a short one' => [ '/.members/login?t=abc', [ 'token' => 'abc' ] ],
	'a list' => [ '/.members/login?t[]=x', [ 'token' => [ 'x' ] ] ], 'one nobody made' => [ '/.members/login?t='. str_repeat( 'A', 43 ), [ 'token' => str_repeat( 'A', 43 ) ] ],
	'one with a character it cannot have' => [ '/.members/login?t='. str_repeat( 'A', 42 ). '*', [ 'token' => str_repeat( 'A', 42 ). '*' ] ] ] as $kind => [ $uri, $post ] ) {
	membersVisitor();
	$notValid[$kind. ', asked'] = membersAnswer( membersServe( $appData, $uri ) );
	$notValid[$kind. ', pressed'] = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', $post ) );
}

check( 'a link with no token, an empty one, a short one, a list, one nobody made or one with a character it cannot have is answered the same on the page and on the button, without a warning, and nobody is signed in by it',
	array_values( array_unique( array_map( 'json_encode', $notValid ) ) ) === [ '[400,"","no-store","[template \\/templates\\/page-members-login]"]' ] && count( $notValid ) === 12 && ninoWarnings() === [] && \Nino\Modules\Members::current( $appData ) === null
	&& membersFile( $appData, 'tokens' ) === $tokensBefore && $told === [] );

$locations = [];
foreach( [ 'a target on another host' => [ 'target' => '//evil.example/x' ], 'a target of a scheme' => [ 'target' => 'javascript:alert(1)' ], 'a target that is an endpoint' => [ 'target' => '/.members/logout' ], 'a path' => [ 'target' => '/club/news' ] ] as $kind => $change ) {
	membersVisitor();
	$token = membersLink( $appData, $ada );
	$stored = membersFile( $appData, 'tokens' );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [ hash( 'sha256', $token ) => $change + $stored[ hash( 'sha256', $token ) ] ] );
	$locations[$kind] = membersConfirm( $appData, $token )[1];
}
membersVisitor();
$token = membersLink( $appData, $ada );
$stored = membersFile( $appData, 'tokens' );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [ hash( 'sha256', $token ) => [ 'kind' => 'reset' ] + $stored[ hash( 'sha256', $token ) ] ] );
$resetAsked = membersAnswer( membersServe( $appData, '/.members/login?t='. $token ) );
$reset = membersConfirm( $appData, $token );

check( 'a link goes on to the path it carries, if that is a path of this site - the start page for any other: another host, a scheme, an endpoint', $locations === [ 'a target on another host' => '/', 'a target of a scheme' => '/', 'a target that is an endpoint' => '/', 'a path' => '/club/news' ] );
check( '...and a token of a kind this feature does not make is none, on the page and on the button', $resetAsked[0] === 400 && $reset[0] === 400 && \Nino\Modules\Members::current( $appData ) === null );

// Run out: refused, on the page and on the button, and dropped from the file with everything else that has - in the same write that adds or spends one
membersVisitor();
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
$old = membersLink( $appData, $ada );
$stale = membersLink( $appData, $ada );
$file = membersFile( $appData, 'tokens' );
$file[ hash( 'sha256', $old ) ]['expires'] = time() - 1;
$kept = ( new ReflectionClassConstant( \Nino\Modules\Members::class, 'LINK_KEEP' ) )->getValue();
$file[ hash( 'sha256', $stale ) ]['expires'] = time() - $kept - 3600;
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );
$fresh = membersLink( $appData, $ada );
$afterIssue = membersFile( $appData, 'tokens' );
$expired = membersAnswer( membersServe( $appData, '/.members/login?t='. $old ) );
$expiredPressed = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $old ] ) );
check( 'a token that has run out is refused - the same page, 400, on the page and on the button', $expired === [ 400, '', 'no-store', '[template /templates/page-members-login]' ] && $expiredPressed === $expired && \Nino\Modules\Members::current( $appData ) === null );
$keys = array_keys( $afterIssue );
sort( $keys );
$both = [ hash( 'sha256', $old ), hash( 'sha256', $fresh ) ];
sort( $both );
check( '...and the record of a link that ran out is kept for a while, for the page that says it no longer works to lead to where the link led; one that is older than that goes in the write that makes the next token', $keys === $both && count( membersFile( $appData, 'tokens' ) ) === 2 );

$file = membersFile( $appData, 'tokens' );
$file[ hash( 'sha256', $old ) ] = [ 'account' => $ada['id'], 'kind' => 'login', 'expires' => time() - $kept - 5, 'target' => '' ];
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );

// What cannot be a token is not looked up, so it writes nothing either - the dead entry is still there when it has been pressed
membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => 'abc' ] );
$untouched = membersFile( $appData, 'tokens' ) === $file;
membersConfirm( $appData, $fresh );
check( '...a link that is followed takes the entries that are dead for good with it - and a text that cannot be a token leaves the file as it is', $untouched === true && membersFile( $appData, 'tokens' ) === [] );

// An invitation signs in in the same two steps
membersVisitor();
$told = [];
$invite = membersLink( $appData, $ada, 'invite' );
$inviteAsked = membersAnswer( membersServe( $appData, '/.members/login?t='. $invite ) );
$stillThere = count( membersFile( $appData, 'tokens' ) );
$invited = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $invite ] ) );
check( 'an invitation\'s link works in the same two steps: the page asks and uses nothing up, the button signs in and spends it', $inviteAsked[0] === 200 && $stillThere === 1 && $invited[0] === 303 && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $ada['id']
	&& membersFile( $appData, 'tokens' ) === [] && count( $told ) === 1 );

// An account that is switched off or gone holds a link that leads nowhere - asked for it is not valid, and pressed it is spent on the way
membersVisitor();
$told = [];
$gone = \Nino\Modules\Members::add( $appData, 'gone@example.org', 'Gone', '' );
$offLink = membersLink( $appData, $off );
$goneLink = membersLink( $appData, $gone );
\Nino\Modules\Members::remove( $appData, $gone['id'] );
$a = membersAnswer( membersServe( $appData, '/.members/login?t='. $offLink ) );
$b = membersAnswer( membersServe( $appData, '/.members/login?t='. $goneLink ) );
$askedOnly = count( membersFile( $appData, 'tokens' ) );
$c = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $offLink ] ) );
$d = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $goneLink ] ) );
check( 'a link for an account that is switched off, or was deleted, is not valid either - refused on the page, spent by the button, and nobody is signed in', $a[0] === 400 && $b[0] === 400 && $c[0] === 400 && $d[0] === 400 && $askedOnly === 2
	&& \Nino\Modules\Members::current( $appData ) === null && membersFile( $appData, 'tokens' ) === [] && $told === [] );

echo "\n";


// --- The gate ------------------------------------------------------------------

echo "Modules\\Members - the gate in front of the protected paths\n";

// The setting is lines: a prefix with a slash behind it is the prefix, the tools and the endpoints of the modules
// cannot be protected, the front page is no prefix, and what is there twice is there once
check( 'the protected paths are the lines of the setting, made prefixes: no trailing slash, nothing under /_ or /., not the front page, each once',
	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => "/club\n/clubs/\n/_admin\n/.members\n/\n  \n/club/\nclub-without-slash" ] ) === [] && \Nino\Modules\Members::prefixes( $appData ) === [ '/club', '/clubs' ] );

$under = [];

foreach( [ '/club' => true, '/club/news' => true, '/club/a/b' => true, '/clubs/x' => true, '/clubhouse' => false, '/club2' => false, '/news' => false, '/' => false, '/_admin' => false, '/_admin/club' => false, '/.members/login' => false, '' => false ] as $uri => $expected )
	$under[$uri] = \Nino\Modules\Members::protects( $appData, $uri ) === $expected;

check( '...and a path is protected when it is a prefix or lies below one - and not when it only begins with the same letters', array_filter( $under, static fn( bool $same ): bool => $same === false ) === [] );

membersVisitor();
$gated = membersServe( $appData, '/club/news' );
$login = \Nino\Html::renderHtml( $appData, (string) membersAnswer( $gated )[3] );
$keptFirst = \Nino\Runtime::getSessionValue( $appData, './members/target' );
$prefix = membersServe( $appData, '/club' );

check( 'a visitor under a protected path is shown the login page in place of the page: 401, not stored by anyone, and the page it asked for is kept in the session', membersAnswer( $gated ) === [ 401, '', 'no-store', '[template /templates/page-members-login]' ]
	&& $keptFirst === '/club/news' );
check( '...the page the visitor sees is the whole login page, its form going back to the page asked for', count( membersFind( $login, '//h1' ) ) === 1 && count( membersFind( $login, '//form[contains(@class, "members-login")]' ) ) === 1
	&& membersFind( $login, '//form//input[@name="return"]' )[0]->getAttribute( 'value' ) === '/club/news' );
$unanswered = membersAnswer( membersServe( $appData, '/clubs/nobody-wrote-this' ) );
check( '...the prefix itself is shut as well, and so is a path below it that no route answers, and the session keeps the path asked for last', membersAnswer( $prefix ) === [ 401, '', 'no-store', '[template /templates/page-members-login]' ]
	&& $unanswered === membersAnswer( $prefix ) && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === '/clubs/nobody-wrote-this' );

membersVisitor();
$outside = [ membersAnswer( membersServe( $appData, '/clubhouse' ) ), membersAnswer( membersServe( $appData, '/news' ) ) ];
$own = [ membersAnswer( membersServe( $appData, '/.members/login' ) )[0], membersAnswer( membersServe( $appData, '/.members/logout', 'POST' ) )[0], membersAnswer( membersServe( $appData, '/_admin' ) )[0] ];
check( 'a path outside the protected ones, the endpoints of the feature and the workbench are not gated: the page is the page, and the session keeps no path', $outside[0][0] === 200 && $outside[0][3] === 'FOR EVERYBODY' && $outside[1][3] === 'FOR EVERYBODY'
	&& $own === [ 400, 303, 404 ] && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === null );

// The member passes
membersVisitor();
membersSignIn( $appData, $ada );
$passed = membersAnswer( membersServe( $appData, '/club/news' ) );
$sibling = $_SESSION;
membersVisitor();
$other = membersAnswer( membersServe( $appData, '/club/news' ) );
check( 'a signed-in member passes: the page is the page, with its own status', $passed === [ 200, '', 'no-store', 'FOR MEMBERS' ] );
check( '...and another visitor, in a session of their own, is still shown the login page', $other[0] === 401 && $other[3] === '[template /templates/page-members-login]' );
$_SESSION = $sibling;

// A page has an address for each language its routes give it. The setting names the page, /club: the German address and the German subtree are the same page
membersVisitor();
$german = [];

foreach( [ '/club', '/verein', '/verein/vorstand' ] as $each )
	$german[$each] = membersAnswer( membersServe( $appData, $each ) );

$keptGerman = \Nino\Runtime::getSessionValue( $appData, './members/target' );
membersSignIn( $appData, $ada );
$germanMember = [];

foreach( [ '/club', '/verein', '/verein/vorstand' ] as $each )
	$germanMember[$each] = membersAnswer( membersServe( $appData, $each ) );

check( 'a page that has a route for each language is shut at every address: the German one and the German subtree answer a visitor with the login page, as the page\'s own address does, and keep the path asked for - and a member passes at all three',
	array_values( array_unique( array_map( 'json_encode', $german ) ) ) === [ '[401,"","no-store","[template \\/templates\\/page-members-login]"]' ] && count( $german ) === 3 && $keptGerman === '/verein/vorstand'
	&& array_values( array_unique( array_map( 'json_encode', $germanMember ) ) ) === [ '[200,"","no-store","FOR MEMBERS"]' ] && count( $germanMember ) === 3 );
$_SESSION = $sibling;

// The page that asked for the login is kept with its query, as it came - and with nothing a header or a browser would take for more than the page
$wanted = [];
$long = '/club/news?x=';

foreach( [
	'a query' => [ '/club/news?issue=7&tab=files', '/club/news?issue=7&tab=files' ],
	'a query that is encoded' => [ '/club/news?q=a%20b&x=%0A%00', '/club/news?q=a%20b&x=%0A%00' ],
	'a fragment, which no browser sends, is no part of it' => [ '/club/news?issue=7#top', '/club/news?issue=7' ],
	'a question mark and nothing behind it' => [ '/club/news?', '/club/news' ],
	'a line break in the query' => [ "/club/news?x=\n", '/club/news' ],
	'a null byte in the query' => [ "/club/news?x=\0", '/club/news' ],
	'a carriage return' => [ "/club/news?x=1\r&y=2", '/club/news' ],
	'a blank' => [ '/club/news?x=a b', '/club/news' ],
	'a tab' => [ "/club/news?x=a\tb", '/club/news' ],
	'2000 characters in all' => [ $long. str_repeat( 'a', 2000 - strlen( $long ) ), $long. str_repeat( 'a', 2000 - strlen( $long ) ) ],
	'one more than that: the path alone' => [ $long. str_repeat( 'a', 2001 - strlen( $long ) ), '/club/news' ],
] as $label => [ $uri, $expected ] ) {
	membersVisitor();
	membersServe( $appData, $uri );
	$wanted[$label] = \Nino\Runtime::getSessionValue( $appData, './members/target' ) === $expected;
}

check( 'the page a protected page was asked for is kept with its query: the path and what follows the question mark, as it came, without a fragment - and the path alone for a query that holds a control character or a blank, or that makes the whole longer than 2000 characters', array_keys( array_filter( $wanted, static fn( bool $same ): bool => $same === false ) ) === [] && count( $wanted ) === 11 );

membersVisitor();
$mails = [];
membersServe( $appData, '/club/news?issue=7&tab=files' );
$digits = membersAskCode( $appData, 'ada@example.org', '/club/news' );
$withQuery = membersFile( $appData, 'tokens' )[ hash( 'sha256', ( membersPending( $appData )['nonce'] ?? '' ). '|'. $digits ) ] ?? [];
$through = membersCodeServe( $appData, $digits, '/club/news' );
check( '...the record of the code carries it, and the code leads the member back to the page with its query', ( $withQuery['target'] ?? null ) === '/club/news?issue=7&tab=files' && $through === [ 303, '/club/news?issue=7&tab=files', 'no-store', '' ] && \Nino\Modules\Members::current( $appData ) !== null );

membersVisitor();
check( '...so does the link: the page it carries, with its query and the place on the page, is where the button leads; a target with a line break in its query is the start page', membersSignIn( $appData, $ada, '/club/news?issue=7&tab=files#top' ) === [ 303, '/club/news?issue=7&tab=files#top', 'no-store', '' ]
	&& ( membersVisitor() ?? true ) && membersSignIn( $appData, $ada, "/club/news?x=\n" ) === [ 303, '/', 'no-store', '' ] && ( membersVisitor() ?? true ) && membersSignIn( $appData, $ada, '/club/news?x='. str_repeat( 'a', 2000 ) ) === [ 303, '/', 'no-store', '' ] );

// '/members/start': where a sign-in leads is told to the features that build on it, and they may say another page
$heard = [];
$startSays = [];
\Nino\Callbacks::registerCallback( $appData, '/members/start', static function( array &$appData, array &$args ) use ( &$heard, &$startSays ): void {

	$heard[] = $args;

	if( $startSays !== [] )
		$args['to'] = $startSays['to'];
} );

membersVisitor();
$mails = [];
$answer = membersSignIn( $appData, $ada, '/club/news?issue=7' );
$plain = membersSignIn( $appData, $ada );
membersVisitor();
$noTarget = membersSignIn( $appData, $ada );
$accountKeys = array_keys( $heard[0]['account'] ?? [] );
sort( $accountKeys );
check( 'a sign-in by a link fires /members/start with the account, the target and where it leads - the target, else the start page - and leads there when no callback says another', count( $heard ) === 3 && $answer === [ 303, '/club/news?issue=7', 'no-store', '' ] && $noTarget === [ 303, '/', 'no-store', '' ]
	&& $heard[0] === [ 'account' => $heard[0]['account'], 'target' => '/club/news?issue=7', 'to' => '/club/news?issue=7' ] && $heard[0]['account']['id'] === $ada['id'] && $heard[2]['target'] === '' && $heard[2]['to'] === '/' && $plain === [ 303, '/', 'no-store', '' ]
	&& $accountKeys === [ 'active', 'created', 'email', 'group', 'id', 'login', 'name' ] );

membersVisitor();
$mails = [];
membersServe( $appData, '/club/news?issue=8' );
$digits = membersAskCode( $appData, 'ada@example.org', '/club/news' );
$heard = [];
$byCode = membersCodeServe( $appData, $digits, '/club/news' );
check( '...so does a sign-in by a code, with the page that asked for it', count( $heard ) === 1 && $heard[0]['target'] === '/club/news?issue=8' && $heard[0]['to'] === '/club/news?issue=8' && $heard[0]['account']['id'] === $ada['id'] && $byCode[1] === '/club/news?issue=8' );
membersSpentTargetChecks( $appData, $ada );
membersMessageChecks( $appData, $ada );

membersVisitor();
$heard = [];
$digits = membersAskCode( $appData, 'ada@example.org', '/join' );
$wrong = membersCodeServe( $appData, sprintf( '%06d', ( (int) $digits + 1 ) % 1000000 ), '/join' );
$badLink = membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => 'not-a-token' ] ) );
check( '...and nothing is fired where nobody was signed in: a code that is wrong, a link that is not one', $heard === [] && $wrong[0] === 303 && $wrong[1] === '/join?members=wrong' && $badLink[0] === 400 );

$leads = [];

foreach( [ 'a page of the site' => [ '/club/other?x=1', '/club/other?x=1' ], 'a page with a place' => [ '/club/other#top', '/club/other#top' ], 'the front page' => [ '/', '/' ] ] as $label => [ $says, $expected ] ) {
	membersVisitor();
	$startSays = [ 'to' => $says ];
	$leads[$label] = membersSignIn( $appData, $ada, '/club/news' )[1] === $expected;
}

check( '...a callback that sets to to a path of the site leads the member there, whatever the target was', array_keys( array_filter( $leads, static fn( bool $same ): bool => $same === false ) ) === [] && count( $leads ) === 3 );

$refused = [];

foreach( [ 'another host' => '//evil.example/x', 'a scheme' => 'https://evil.example/', 'javascript' => 'javascript:alert(1)', 'a colon before the next slash' => '/javascript:alert(1)', 'a backslash' => '/\\evil.example', 'an endpoint' => '/.members/logout', 'a line break' => "/club/x\nSet-Cookie: a=b", 'a null byte' => "/club/x\0", 'a blank' => '/club/x y', 'no slash' => 'club/x', 'nothing' => '', 'more than 2000 characters' => '/club/x?y='. str_repeat( 'a', 2000 ), 'a number' => 5, 'an array' => [ '/club/x' ], 'null' => null ] as $label => $says ) {
	membersVisitor();
	$startSays = [ 'to' => $says ];
	$refused[$label] = membersSignIn( $appData, $ada, '/club/news' ) === [ 303, '/', 'no-store', '' ];
}

$startSays = [];
check( '...and any other value of to is the start page: another host, a scheme, a colon before the next slash, a backslash, an endpoint, a line break, a null byte, a blank, no slash, nothing, too many characters, a number, an array', array_keys( array_filter( $refused, static fn( bool $same ): bool => $same === false ) ) === [] && count( $refused ) === 15 );

// The Seo feature is told what a crawler has no business with
$exclude = [ '/old/*' ];
\Nino\Callbacks::doCallbacks( $appData, '/seo/exclude', $exclude );
check( 'the Seo feature is given the protected paths to keep out of its sitemap, each with everything below it, after the ones it had - and the addresses of a protected page in the other languages, which Seo matches as they stand',
	$exclude === [ '/old/*', '/club/*', '/clubs/*', '/verein', '/verein/*' ] );

// The full-page cache: nothing under a protected path, and nothing a member has seen
$fresh = $appData;
$fresh['./nino/callbacks'] = [];
$fresh['/nino/cache/blacklist'] = [ '/mine/*' ];
\Nino\Modules\Members::init( $fresh );
$none = $appData;
$none['./nino/callbacks'] = [];
$none['/nino/cache/blacklist'] = [];
\Nino\Features::saveSettings( $none, 'members', [ 'paths' => [] ] );
\Nino\Modules\Members::init( $none );
check( 'the protected paths join the blacklist of the full-page cache when the feature boots - after the entries the project has, once - and an empty setting adds nothing', $fresh['/nino/cache/blacklist'] === [ '/mine/*', '/club/*', '/clubs/*' ]
	&& $none['/nino/cache/blacklist'] === [] );

// The settings are on disk, and the copy of the app data saved to the very same file: the project's own paths are put back
\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club', '/clubs' ] ] );

$cached = static function() use ( &$appData ): int {
	return count( glob( \Nino\Filesystem::path( $appData, '/data/cache' ). '/*.php' ) ?: [] );
};
$appData['/nino/cache/status'] = true;
$appData['/nino/cache/blacklist'] = [];
\Nino\Filesystem::removeDir( \Nino\Filesystem::path( $appData, '/data/cache' ) );
unset( $appData['./nino/auth/current'] );

membersVisitor();
$page = membersServe( $appData, '/news' );
\Nino\Modules\Cache::callbackOutput( $appData, $page );
$storedForVisitor = $cached();
\Nino\Filesystem::removeDir( \Nino\Filesystem::path( $appData, '/data/cache' ) );
$appData['/nino/cache/blacklist'] = [];
membersSignIn( $appData, $ada );
$page = membersServe( $appData, '/news' );
\Nino\Modules\Cache::callbackOutput( $appData, $page );
$listedNow = $appData['/nino/cache/blacklist'];

// A path the list has already is not put there twice, and a request that has no path adds nothing to it
$appData['/nino/cache/blacklist'] = [ '/news', '/mine/*' ];
membersServe( $appData, '/news' );
$listedTwice = $appData['/nino/cache/blacklist'];
$appData['/nino/cache/blacklist'] = [];
$pathless = [];
\Nino\Modules\Members::callbackGate( $appData, $pathless );
check( 'a page for a visitor is stored by the full-page cache, and the same page for a member is not - it may carry their name, and its path is kept out of the cache for that request, once', $storedForVisitor === 1 && $cached() === 0
	&& $listedNow === [ '/news' ] && $listedTwice === [ '/news', '/mine/*' ] && $appData['/nino/cache/blacklist'] === [] );
$appData['/nino/cache/status'] = false;
$appData['/nino/cache/blacklist'] = [];

// Logging out, and the epoch
echo "\n";

echo "Modules\\Members - signing out, for one member and for everybody\n";

membersSignIn( $appData, $ada );
$told = [];
$out = membersServe( $appData, '/.members/logout', 'POST' );
check( 'the logout form posts to the endpoint: it signs the member out and goes (303) to the start page with ?members=out, stored by no one; the features that build on it are told, with the account', membersAnswer( $out ) === [ 303, '/?members=out', 'no-store', '' ]
	&& \Nino\Modules\Members::current( $appData ) === null && \Nino\Runtime::getSessionValue( $appData, './members/account' ) === null && count( $told ) === 1 && $told[0][0] === 'logout' && $told[0][1]['id'] === $ada['id'] );
$told = [];
$again = membersServe( $appData, '/.members/logout', 'POST' );
check( '...and it is the same for somebody who is not signed in - and nobody is told of that', membersAnswer( $again ) === membersAnswer( $out ) && $told === [] );
ninoVisit( $appData, '/?members=out' );
$page = membersFind( \Nino\Html::renderHtml( $appData, '[members-login]' ), '//form/p' );
check( '...and the page it goes to says so: the state out, in the form', count( $page ) === 1 && $page[0]->textContent === $states['out'][0]->textContent );

\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/club' ] );
check( '...the start page of the setting is where it goes', membersAnswer( membersServe( $appData, '/.members/logout', 'POST' ) )[1] === '/club?members=out' );
\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/' ] );

// Everybody out at once: two members in two browsers, a new epoch, and both are out
membersVisitor();
membersSignIn( $appData, $ada );
$browserA = $_SESSION;
membersVisitor();
membersSignIn( $appData, $bare );
$browserB = $_SESSION;
$_SESSION = $browserA;
$inBefore = [ \Nino\Modules\Members::current( $appData )['id'] ?? '', ( $_SESSION = $browserB ) ? \Nino\Modules\Members::current( $appData )['id'] ?? '' : '' ];
$epochBefore = membersFile( $appData, 'session' )['epoch'];
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );
[ $status ] = ninoCall( $appData, $panel, [ 'all' => true ], 'members/signout' );
$_SESSION = $browserA;
$outA = \Nino\Modules\Members::current( $appData );
$_SESSION = $browserB;
$outB = \Nino\Modules\Members::current( $appData );
check( 'signing everybody out ends the sessions of every browser at once - they hold the account of the old epoch', $inBefore === [ $ada['id'], $bare['id'] ] && $status === 200 && $outA === null && $outB === null
	&& membersFile( $appData, 'session' )['epoch'] !== $epochBefore );
$gatedNow = membersServe( $appData, '/club/news' );
check( '...and a protected path is shut to them', membersAnswer( $gatedNow )[0] === 401 );

unset( $appData['./nino/auth/current'] );
membersSignIn( $appData, $bare );
check( '...they sign in again with a link, under the new epoch', ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $bare['id'] );

// An account that is switched off, or gone, with a session
membersVisitor();
membersSignIn( $appData, $ada );
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => false ] );
$whileOff = [ \Nino\Modules\Members::current( $appData ), membersAnswer( membersServe( $appData, '/club/news' ) )[0], \Nino\Html::renderHtml( $appData, '[members-name][members-logout]' ) ];
\Nino\Modules\Members::change( $appData, $ada['id'], [ 'active' => true ] );
check( 'an account that is switched off reads as signed out at once, whatever its session says: no member, the login page on a protected path, no name and no link to sign out',
	$whileOff[0] === null && $whileOff[1] === 401 && $whileOff[2] === '' );
check( '...and switching it on is no way back for the session it had: the session from before reads signed out still, and a link made before it was switched off signs nobody in', \Nino\Modules\Members::current( $appData ) === null );

$extra = \Nino\Modules\Members::add( $appData, 'extra@example.org', 'Extra', '' );
membersVisitor();
membersSignIn( $appData, $extra );
\Nino\Modules\Members::remove( $appData, $extra['id'] );
check( '...so does one that was deleted', \Nino\Modules\Members::current( $appData ) === null && membersAnswer( membersServe( $appData, '/club/news' ) )[0] === 401 );
\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [] ] );

// The sign-in is a value of the kernel's session and nothing else: the answers set no cookie of their own
membersVisitor();
$token = membersLink( $appData, $ada );
$asked = membersServe( $appData, '/.members/login?t='. $token );
$request = membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $token ] );
check( 'the sign-in is a value of the session - the answers that ask for it and bring it set no cookie of their own, nor does the one that ends it', array_key_exists( 'Set-Cookie', $asked['/nino/http/response']['header'] ) === false
	&& array_key_exists( 'Set-Cookie', $request['/nino/http/response']['header'] ) === false && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $ada['id']
	&& array_key_exists( 'Set-Cookie', membersServe( $appData, '/.members/logout', 'POST' )['/nino/http/response']['header'] ) === false );

echo "\n";


// --- Changing the address: the owner's write -------------------------------------------

echo "Modules\\Members - changing the address: the owner's write\n";

\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [] ] );
unset( $appData['./nino/auth/current'] );
\Nino\Filesystem::putFileContent( $appData, '/data/members.php', [] );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
membersVisitor();
membersFresh( $appData );
$mails = [];
$delivery = true;
$changedTold = [];
\Nino\Callbacks::registerCallback( $appData, '/members/changed', static function( array &$appData, array &$told ) use ( &$changedTold ): void {
	$changedTold[] = $told;
} );

$cleo = \Nino\Modules\Members::add( $appData, 'cleo@example.org', 'Cleo', 'staff' );
$dora = \Nino\Modules\Members::add( $appData, 'dora@example.org', 'Dora', '' );
$contact = \Nino\Html::renderTextfill( $appData, '/project/company/contact/email' );
$accountsFile = static fn(): string => (string) file_get_contents( \Nino\Filesystem::path( $GLOBALS['appData'], '/data/members.php' ) );
$tokensOf = static fn( string $id ): array => array_keys( array_filter( membersFile( $GLOBALS['appData'], 'tokens' ), static fn( array $entry ): bool => $entry['account'] === $id ) );

// What is no address, and what another account has
$cleoLink = membersLink( $appData, $cleo );
$doraLink = membersLink( $appData, $dora );
$fileBefore = $accountsFile();
$tokensBefore = membersFile( $appData, 'tokens' );
$mailsBefore = count( $mails );
$refused = [];

foreach( [ 'no address' => 'nope', 'an empty one' => '  ', 'markup' => 'a<b>@example.org', 'a bracket' => 'a[1]@example.org', 'a null byte' => "a\x00b@example.org", 'a list' => [ 'cleo2@example.org' ] ] as $kind => $typed )
	$refused[$kind] = \Nino\Modules\Members::change( $appData, $cleo['id'], [ 'email' => $typed ] );

$taken = [ \Nino\Modules\Members::change( $appData, $cleo['id'], [ 'email' => 'dora@example.org' ] ), \Nino\Modules\Members::change( $appData, $cleo['id'], [ 'email' => ' DORA@Example.ORG ' ] ) ];
check( 'an address that is none - text, empty, markup, a bracket, a null byte, a list - is invalid, and one another account has, in any case, is taken: nothing is written or mailed, and the links the account has out are still there',
	array_values( array_unique( $refused ) ) === [ 'invalid' ] && count( $refused ) === 6 && $taken === [ 'taken', 'taken' ] && $accountsFile() === $fileBefore && membersFile( $appData, 'tokens' ) === $tokensBefore && count( $mails ) === $mailsBefore
	&& $cleoLink !== '' && $doraLink !== '' && count( $tokensOf( $cleo['id'] ) ) === 1 );
check( '...an id nobody has is unknown, with an address and without', \Nino\Modules\Members::change( $appData, 'ffffffffffff', [ 'email' => 'new@example.org' ] ) === 'unknown' && \Nino\Modules\Members::change( $appData, 'ffffffffffff', [ 'name' => 'x' ] ) === 'unknown' );

$same = \Nino\Modules\Members::change( $appData, $cleo['id'], [ 'email' => ' CLEO@example.org ' ] );
check( 'the address the account has - in any case - is no change: the account as it is, nothing written, no epoch, nothing mailed, nobody told, the links stay',
	is_array( $same ) && $same['email'] === 'cleo@example.org' && $accountsFile() === $fileBefore && membersFile( $appData, 'tokens' ) === $tokensBefore && count( $mails ) === $mailsBefore && $changedTold === [] );

// Two browsers of the account, a code of a visitor that is out and a link of each kind
membersVisitor();
membersSignIn( $appData, $cleo );
$browserA = $_SESSION;
membersVisitor();
membersSignIn( $appData, $cleo );
$browserB = $_SESSION;
membersVisitor();
$digitsOut = membersAskCode( $appData, 'cleo@example.org' );
$visitorOut = $_SESSION;
$inviteLink = membersLink( $appData, $cleo, 'invite' );
$loginLink = membersLink( $appData, $cleo, 'login' );
$cleoTokens = $tokensOf( $cleo['id'] );
$doraTokens = $tokensOf( $dora['id'] );
$epochKept = membersFile( $appData, 'session' );
$mails = [];
$moved = \Nino\Modules\Members::change( $appData, $cleo['id'], [ 'email' => ' Cleo.New@Example.org ' ] );
$record = membersFile( $appData, 'accounts' )['accounts'][ $cleo['id'] ] ?? [];
check( 'a new address is written the way add() writes one - lower case - and the account as it is comes back with its id and without the epoch it was given', is_array( $moved ) && $moved['email'] === 'cleo.new@example.org' && array_keys( $moved ) === [ 'id', 'email', 'name', 'group', 'active', 'created', 'login' ]
	&& $moved['name'] === 'Cleo' && ( $record['email'] ?? '' ) === 'cleo.new@example.org' && preg_match( '/^[0-9a-f]{32}$/D', (string) ( $record['epoch'] ?? '' ) ) === 1 && \Nino\Modules\Members::account( $appData, $cleo['id'] ) === $moved
	&& array_keys( \Nino\Modules\Members::accounts( $appData )[0] ) === [ 'id', 'email', 'name', 'group', 'active', 'created', 'login' ] );

$_SESSION = $visitorOut;
$codeAfter = membersCodeServe( $appData, $digitsOut );
check( '...every link and code of the account is deleted - the one from before, the code, the invitation and the login link, four - and no other account\'s: they are found nowhere, and the code of the session that asked is wrong',
	count( $cleoTokens ) === 4 && $tokensOf( $cleo['id'] ) === [] && $tokensOf( $dora['id'] ) === $doraTokens && count( $doraTokens ) === 1 && membersConfirm( $appData, $inviteLink )[0] === 400 && membersConfirm( $appData, $loginLink )[0] === 400
	&& $codeAfter === [ 303, '/join?members=wrong', 'no-store', '' ] && \Nino\Modules\Members::current( $appData ) === null );
$_SESSION = $browserA;
$outA = \Nino\Modules\Members::current( $appData );
$_SESSION = $browserB;
$outB = \Nino\Modules\Members::current( $appData );
check( '...every session of the account ends - both browsers read signed out - while the epoch of everybody is the one it was', $outA === null && $outB === null && membersFile( $appData, 'session' ) === $epochKept );

$notice = array_values( array_filter( $mails, static fn( array $mail ): bool => $mail['to'] === 'cleo@example.org' ) );
check( 'the old address is told, once, and it is the only mail: it says the address was changed and whom to write to, names neither the new address nor a code, has no link and no key left in it',
	count( $notice ) === 1 && count( $mails ) === 1 && $contact !== '' && str_contains( (string) $notice[0]['body'], $contact ) === true && str_contains( (string) $notice[0]['body'], 'cleo.new' ) === false && membersCodeOf( $notice[0] ) === ''
	&& str_contains( (string) $notice[0]['body'], 'href=' ) === false && str_contains( (string) $notice[0]['body'], '[[' ) === false && trim( (string) $notice[0]['subject'] ) !== '' && str_contains( (string) $notice[0]['subject'], '[[' ) === false );
check( '...and the features that build on it are told once, with the account as it is, the address it had and who asked: the owner', count( $changedTold ) === 1 && $changedTold[0] === [ 'account' => $moved, 'old' => 'cleo@example.org', 'by' => 'owner' ] );

$mails = [];
membersVisitor();
$oldAsked = membersAskCode( $appData, 'cleo@example.org' );
$newDigits = membersAskCode( $appData, 'cleo.new@example.org' );
$signed = membersCodeServe( $appData, $newDigits );
$own = \Nino\Runtime::getSessionValue( $appData, './members/account' );
check( 'the old address asks for a code and nobody is mailed; the new one is mailed and signs in, and the session of an account that has an epoch carries it', $oldAsked === '' && $newDigits !== '' && $signed[1] === '/' && ( \Nino\Modules\Members::current( $appData )['email'] ?? '' ) === 'cleo.new@example.org'
	&& array_keys( $own ) === [ 'id', 'epoch', 'own' ] && $own['own'] === $record['epoch'] );

membersVisitor();
membersSignIn( $appData, $dora );
$doraSession = \Nino\Runtime::getSessionValue( $appData, './members/account' );
check( 'an account without an epoch signs in as it did - its session holds the account and the epoch of everybody and nothing else - and the session is good when another account has changed its address',
	array_keys( $doraSession ) === [ 'id', 'epoch' ] && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $dora['id'] && array_key_exists( 'epoch', membersFile( $appData, 'accounts' )['accounts'][ $dora['id'] ] ) === false );

// A notice no transport takes is a warning, and the write that cannot delete the links does not write
$delivery = false;
$mails = [];
ninoWarnings();
$late = \Nino\Modules\Members::change( $appData, $dora['id'], [ 'email' => 'dora.late@example.org' ] );
$warned = ninoWarnings();
$delivery = true;
check( 'a notice no transport takes is a warning that names no address, and the address is changed all the same', is_array( $late ) && $late['email'] === 'dora.late@example.org' && count( $warned ) === 1 && str_contains( implode( ' ', $warned ), 'example.org' ) === false );
\Nino\Modules\Members::change( $appData, $dora['id'], [ 'email' => 'dora@example.org' ] );

$locks = \Nino\Filesystem::path( $appData, '/data' ). '/.locks';
membersLink( $appData, $dora );
rename( $locks, $locks. '.kept' );
file_put_contents( $locks, '' );
$fileBefore = $accountsFile();
$mails = [];
$changedTold = [];
ninoWarnings();
$stuck = \Nino\Modules\Members::change( $appData, $dora['id'], [ 'email' => 'dora.stuck@example.org' ] );
ninoWarnings();
unlink( $locks );
rename( $locks. '.kept', $locks );
check( 'where the links cannot be deleted the address is not written: write, the account as it was, nobody mailed or told - no way in is left through the old address', $stuck === 'write' && $accountsFile() === $fileBefore && $mails === [] && $changedTold === [] && count( $tokensOf( $dora['id'] ) ) === 1 );

// ...and where only the file of the links is stuck - its lock is a directory - the accounts file is still not written: the links are deleted first, so that a failure there leaves the account as it was
foreach( glob( $locks. '/*.lock' ) ?: [] as $lockFile )
	unlink( $lockFile );

\Nino\Filesystem::mutate( $appData, '/data/members-tokens.php', static fn( mixed $state ): ?array => null, [] );
$tokenLocks = glob( $locks. '/*.lock' ) ?: [];
$linkedAgain = membersLink( $appData, $dora );
unlink( $tokenLocks[0] ?? '' );
mkdir( $tokenLocks[0] ?? '' );
$fileBefore = $accountsFile();
$mails = [];
$changedTold = [];
ninoWarnings();
$stuckLinks = \Nino\Modules\Members::change( $appData, $dora['id'], [ 'email' => 'dora.stuck2@example.org' ] );
ninoWarnings();
rmdir( $tokenLocks[0] ?? '' );
check( '...the same where only the links cannot be written: write - the address is not written, nobody is mailed or told, the link made is still the account\'s own', count( $tokenLocks ) === 1 && $linkedAgain !== '' && $stuckLinks === 'write' && $accountsFile() === $fileBefore && $mails === [] && $changedTold === [] && count( $tokensOf( $dora['id'] ) ) === 2 );

// A code that was asked for the old address and landed after the links were deleted: the race a change leaves - the record is put back after the change, as a request that was between its reading and its writing would
membersVisitor();
membersFresh( $appData );
$raceDigits = membersAskCode( $appData, 'dora@example.org' );
$raceTokens = membersFile( $appData, 'tokens' );
$moved = \Nino\Modules\Members::change( $appData, $dora['id'], [ 'email' => 'dora.moved@example.org' ] );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', membersFile( $appData, 'tokens' ) + $raceTokens );
$raceAnswer = membersCodeServe( $appData, $raceDigits );
check( 'a code asked for the old address that is still out after the change signs nobody in: gone, with the account on the new address - and nobody is signed in by it', $raceDigits !== '' && is_array( $moved ) && count( array_filter( $raceTokens, static fn( array $entry ): bool => $entry['account'] === $dora['id'] ) ) >= 1
	&& $raceAnswer === [ 303, '/join?members=gone', 'no-store', '' ] && \Nino\Modules\Members::current( $appData ) === null && \Nino\Modules\Members::account( $appData, $dora['id'] )['email'] === 'dora.moved@example.org' );
\Nino\Modules\Members::change( $appData, $dora['id'], [ 'email' => 'dora@example.org' ] );

// The panel's save does not take an address
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );
[ $status ] = ninoCall( $appData, $panel, [ 'id' => $dora['id'], 'name' => 'Dora D', 'email' => 'dora.panel@example.org' ], 'members/save' );
unset( $appData['./nino/auth/current'] );
check( 'the panel\'s save changes the name and leaves the address alone, whatever else it is posted', $status === 200 && ( \Nino\Modules\Members::account( $appData, $dora['id'] )['name'] ?? '' ) === 'Dora D' && ( \Nino\Modules\Members::account( $appData, $dora['id'] )['email'] ?? '' ) === 'dora@example.org' );

echo "\n";


// --- The member's way: askChange() and confirmChange() ---------------------------------

echo "Modules\\Members - the member's way: a code to the new address, in the session that asked\n";

\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
$gus = \Nino\Modules\Members::add( $appData, 'gus@example.org', 'Gus', '' );
$hal = \Nino\Modules\Members::add( $appData, 'hal@example.org', 'Hal', '' );
$me = static fn(): array => (array) \Nino\Modules\Members::current( $GLOBALS['appData'] );
$ask = static fn( string $email ): string => membersAfter( $GLOBALS['appData'], \Nino\Modules\Members::askChange( $GLOBALS['appData'], $GLOBALS['me'](), $email ) );
$confirm = static fn( string $code ): string => \Nino\Modules\Members::confirmChange( $GLOBALS['appData'], $GLOBALS['me'](), $code );
$request = static fn(): ?array => is_array( \Nino\Runtime::getSessionValue( $GLOBALS['appData'], './members/change' ) ) === true ? \Nino\Runtime::getSessionValue( $GLOBALS['appData'], './members/change' ) : null;
$digitsOfLast = static fn(): string => str_replace( ' ', '', membersCodeOf( end( $GLOBALS['mails'] ) ?: [] ) );
$mailsTo = static fn( string $email ): array => array_values( array_filter( $GLOBALS['mails'], static fn( array $mail ): bool => $mail['to'] === $email ) );
$wrong = static fn( string $digits ): string => sprintf( '%06d', ( (int) $digits + 1 ) % 1000000 );

// Not for another account's sake
membersVisitor();
membersFresh( $appData );
$mails = [];
$asGuest = membersAfter( $appData, \Nino\Modules\Members::askChange( $appData, $gus, 'gus.new@example.org' ) );
membersSignIn( $appData, $hal );
$mails = [];
$asOther = membersAfter( $appData, \Nino\Modules\Members::askChange( $appData, $gus, 'gus.new@example.org' ) );
check( 'a visitor, and a member who asks for the address of another account, are refused - invalid - with no request in the session, no code in the file and no mail', $asGuest === 'invalid' && $asOther === 'invalid' && $request() === null
	&& membersFile( $appData, 'tokens' ) === [] && $mails === [] && \Nino\Modules\Members::changePending( $appData, $gus ) === null );

membersVisitor();
membersSignIn( $appData, $gus );
$gusLink = membersLink( $appData, $gus );
$counted = membersFile( $appData, 'tries' );
$mails = [];
$answers = [ 'no address' => $ask( 'nope' ), 'markup' => $ask( 'a<b>@example.org' ), 'a bracket' => $ask( 'a[1]@example.org' ), 'a null byte' => $ask( "a\x00b@example.org" ) ];
$sameAnswer = $ask( ' GUS@Example.org ' );
check( 'an address that is none is invalid and the address the account has is same - nothing is kept, mailed or counted for either', array_values( array_unique( $answers ) ) === [ 'invalid' ] && $sameAnswer === 'same' && $request() === null && $mails === [] && membersFile( $appData, 'tries' ) === $counted );

$answer = $ask( ' Gus.New@Example.org ' );
$first = $request() ?? [];
$firstDigits = $digitsOfLast();
$record = membersFile( $appData, 'tokens' )[ hash( 'sha256', ( $first['nonce'] ?? '' ). '|'. $firstDigits ) ] ?? [];
check( 'a free address is sent: the session keeps the request - the account, the address as the form made it, a nonce of 32 hex characters, no try and the time of the setting\'s minutes -, and the new address is mailed six digits, no link',
	$answer === 'sent' && array_keys( $first ) === [ 'account', 'email', 'nonce', 'tries', 'until' ] && $first['account'] === $gus['id'] && $first['email'] === 'gus.new@example.org' && preg_match( '/^[0-9a-f]{32}$/D', $first['nonce'] ) === 1 && $first['tries'] === 0
	&& $first['until'] >= time() + 900 - 2 && $first['until'] <= time() + 900 + 2 && count( $mails ) === 1 && $mails[0]['to'] === 'gus.new@example.org' && preg_match( '/^[0-9]{6}$/D', $firstDigits ) === 1 && membersCodeOf( $mails[0] ) !== ''
	&& str_contains( (string) $mails[0]['body'], 'href=' ) === false && str_contains( (string) $mails[0]['body'], '.members/login' ) === false && str_contains( (string) $mails[0]['body'], '[[' ) === false && trim( (string) $mails[0]['subject'] ) !== '' && str_contains( (string) $mails[0]['subject'], '[[' ) === false );
check( '...the record of the code is under the hash of the nonce and the digits - of the kind change, for this account and this address, good as long as the request is -, and nothing of the account is touched: its address, its session and its links are as they were',
	array_keys( $record ) === [ 'account', 'kind', 'expires', 'target', 'email' ] && $record['account'] === $gus['id'] && $record['kind'] === 'change' && $record['email'] === 'gus.new@example.org' && $record['expires'] === $first['until']
	&& \Nino\Modules\Members::account( $appData, $gus['id'] )['email'] === 'gus@example.org' && $me()['id'] === $gus['id'] && $tokensOf( $gus['id'] ) !== [] && in_array( hash( 'sha256', $gusLink ), $tokensOf( $gus['id'] ), true ) === true
	&& \Nino\Modules\Members::changePending( $appData, $me() ) === 'gus.new@example.org' && $mailsTo( 'gus@example.org' ) === [] );

// A new request replaces the one before, whose code is no code of it
$secondAnswer = $ask( 'gus.second@example.org' );
$second = $request() ?? [];
$secondDigits = $digitsOfLast();
$replaced = $confirm( $firstDigits );
check( 'a second request replaces the first: another nonce, the new address - and the code of the first is wrong in it, a try used', $secondAnswer === 'sent' && $second['email'] === 'gus.second@example.org' && $second['nonce'] !== $first['nonce'] && $replaced === 'wrong' && ( $request()['tries'] ?? -1 ) === 1 && $secondDigits !== $firstDigits );

// Four wrong ones are told - the first is the code of the request that was replaced -, the fifth try may be the right one, typed with a space in the middle; a second browser of the account, a link of it and one of another account are out
$wrongOnes = [];
$tries = [];
$bad = $wrong( $secondDigits );

for( $try = 1; $try <= 3; $try++ ) {
	$wrongOnes[] = $confirm( $bad );
	$tries[] = $request()['tries'] ?? null;
}

$browserG = $_SESSION;
membersVisitor();
membersSignIn( $appData, $gus );
$browserG2 = $_SESSION;
$_SESSION = $browserG;
$halLink = membersLink( $appData, $hal );
$gusLink = membersLink( $appData, $gus );
$csrfBefore = \Nino\Csrf::getToken( $appData );
$accountBefore = \Nino\Runtime::getSessionValue( $appData, './members/account' );
$mails = [];
$changedTold = [];
$result = $confirm( substr( $secondDigits, 0, 3 ). ' '. substr( $secondDigits, 3 ) );
$gusNow = \Nino\Modules\Members::account( $appData, $gus['id'] ) ?? [];
$gusRecord = membersFile( $appData, 'accounts' )['accounts'][ $gus['id'] ] ?? [];
check( 'a wrong code is told wrong and counted in the session', $wrongOnes === [ 'wrong', 'wrong', 'wrong' ] && $tries === [ 2, 3, 4 ] );
check( 'the fifth try, if it is the right code, writes the address: ok - the account has the new address and an epoch of its own, nothing else about it changed, and the session that confirmed it is still the member - the same session, the same token of the form, now with the account\'s new epoch',
	$result === 'ok' && $gusNow['email'] === 'gus.second@example.org' && $gusNow['name'] === 'Gus' && preg_match( '/^[0-9a-f]{32}$/D', (string) ( $gusRecord['epoch'] ?? '' ) ) === 1 && $me()['id'] === $gus['id'] && $me()['email'] === 'gus.second@example.org'
	&& \Nino\Csrf::getToken( $appData ) === $csrfBefore && \Nino\Runtime::getSessionValue( $appData, './members/account' ) === $accountBefore + [ 'own' => $gusRecord['epoch'] ] && $request() === null && \Nino\Modules\Members::changePending( $appData, $me() ) === null );
$confirmedAgain = $confirm( $secondDigits );
$browserG = $_SESSION;
$_SESSION = $browserG2;
$otherEnded = \Nino\Modules\Members::current( $appData );
$_SESSION = $browserG;
check( '...the code is spent: sent again it is gone - and the other browser of the account is signed out, where this one is not', $confirmedAgain === 'gone' && $otherEnded === null && $me()['id'] === $gus['id'] );
check( '...every link and code of the account is deleted - its login link, the codes - and no other account\'s link', $tokensOf( $gus['id'] ) === [] && count( $tokensOf( $hal['id'] ) ) === 1 && membersConfirm( $appData, $gusLink )[0] === 400 );
$_SESSION = $browserG;
$told = $mailsTo( 'gus@example.org' );
check( 'the old address is told - one mail, the new address is not in it, no code - and the new one is not mailed again; the features that build on it are told once, the account, the old address and who asked: the member',
	count( $told ) === 1 && count( $mails ) === 1 && str_contains( (string) $told[0]['body'], 'gus.second' ) === false && membersCodeOf( $told[0] ) === '' && count( $changedTold ) === 1 && $changedTold[0] === [ 'account' => $gusNow, 'old' => 'gus@example.org', 'by' => 'member' ] );

// Five tries: the fifth wrong code ends the request, and the right one is gone with it
membersFresh( $appData );
$mails = [];
$ask( 'gus.third@example.org' );
$thirdDigits = $digitsOfLast();
$bad = $wrong( $thirdDigits );
$fives = [];

for( $try = 1; $try <= 5; $try++ )
	$fives[] = $confirm( $bad );

$afterFive = $confirm( $thirdDigits );
check( 'the fifth wrong code ends the request - gone, none in the session - and the right code is gone after it, the address is as it was', $fives === [ 'wrong', 'wrong', 'wrong', 'wrong', 'gone' ] && $request() === null && $afterFive === 'gone' && $me()['email'] === 'gus.second@example.org' );

// What is no code: seven digits, letters, a null byte - each is a wrong try and spends nothing
$ask( 'gus.third@example.org' );
$thirdDigits = $digitsOfLast();
$recordsBefore = membersFile( $appData, 'tokens' );
ninoWarnings();
$malformed = [ $confirm( $thirdDigits. '0' ), $confirm( 'abcdef' ), $confirm( substr( $thirdDigits, 0, 3 ). "\x00". substr( $thirdDigits, 3 ) ), $confirm( '' ) ];
check( 'a code of seven digits, one with letters, one with a null byte and none at all are told wrong, each a try - and nothing is spent, no warning', $malformed === [ 'wrong', 'wrong', 'wrong', 'wrong' ] && ( $request()['tries'] ?? -1 ) === 4 && membersFile( $appData, 'tokens' ) === $recordsBefore && ninoWarnings() === [] );
check( '...and the right code, the fifth try, is taken', $confirm( $thirdDigits ) === 'ok' && $me()['email'] === 'gus.third@example.org' );

// In time
membersFresh( $appData );
$ask( 'gus.late@example.org' );
$lateDigits = $digitsOfLast();
$_SESSION[ $appData['./nino/uid'] ]['./members/change']['until'] = time() - 1;
$lateRequest = $confirm( $lateDigits );
$lateKept = $request();
$pendingLate = \Nino\Modules\Members::changePending( $appData, $me() );
membersFresh( $appData );
$ask( 'gus.late@example.org' );
$lateDigits = $digitsOfLast();
$ran = membersFile( $appData, 'tokens' );

foreach( $ran as $key => $each )
	$ran[$key]['expires'] = time() - 1;

\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $ran );
$lateRecord = $confirm( $lateDigits );
check( 'a request that has run out is gone, even with the right code - and so is nothing in the session; a code whose record has run out is wrong', $lateRequest === 'gone' && $lateKept === null && $pendingLate === null && $lateRecord === 'wrong' && $me()['email'] === 'gus.third@example.org' );

// Without a request at all, and in a session that is not the member's
membersFresh( $appData );
\Nino\Runtime::unsetSessionValue( $appData, './members/change' );
$request0 = $confirm( '123456' );
check( 'a code posted by a member who asked for none is gone', $request0 === 'gone' );

$ask( 'gus.fourth@example.org' );
$fourthDigits = $digitsOfLast();
$browserAsked = $_SESSION;
membersVisitor();
membersSignIn( $appData, $hal );
$elsewhere = $confirm( $fourthDigits );
$_SESSION = $browserAsked;
check( 'the code from another browser, signed in as another account, changes nothing: gone - and in the browser that asked it still works', $elsewhere === 'gone' && \Nino\Modules\Members::account( $appData, $hal['id'] )['email'] === 'hal@example.org' && $confirm( $fourthDigits ) === 'ok' && $me()['email'] === 'gus.fourth@example.org' );

membersFresh( $appData );
$ask( 'gus.fifth@example.org' );
$fifthDigits = $digitsOfLast();
$forged = $request();
membersSignIn( $appData, $hal );
$asked = $request();
$_SESSION[ $appData['./nino/uid'] ]['./members/change'] = $forged;
$stolen = $confirm( $fifthDigits );
check( 'the other account that signs in in the same browser starts without the request - and one that is put into its session by hand is no request of it: gone, nothing changes for either', $asked === null && $stolen === 'gone' && $request() === null
	&& \Nino\Modules\Members::account( $appData, $hal['id'] )['email'] === 'hal@example.org' && \Nino\Modules\Members::account( $appData, $gus['id'] )['email'] === 'gus.fourth@example.org' );

// Two confirmations of one request: once ok
membersVisitor();
membersSignIn( $appData, $gus );
membersFresh( $appData );
$ask( 'gus.race@example.org' );
$raceDigits = $digitsOfLast();
$copy = $_SESSION;
$mails = [];
$changedTold = [];
$winner = $confirm( $raceDigits );
$epochNow = membersFile( $appData, 'accounts' )['accounts'][ $gus['id'] ]['epoch'] ?? '';
$_SESSION = $copy;
$loser = $confirm( $raceDigits );
$_SESSION = $copy;
$_SESSION[ $appData['./nino/uid'] ]['./members/account']['own'] = $epochNow;
$late = $confirm( $raceDigits );
check( 'two confirmations of one request - the second from a copy of the session, even one that carries the new epoch - are one ok and nothing else: the address is written once, one notice, one call',
	$winner === 'ok' && $loser === 'gone' && $late === 'wrong' && ( membersFile( $appData, 'accounts' )['accounts'][ $gus['id'] ]['epoch'] ?? '' ) === $epochNow && count( $changedTold ) === 1 && count( $mailsTo( 'gus.fourth@example.org' ) ) === 1 );

// The address is another account's by the time the code comes back
membersVisitor();
membersSignIn( $appData, $gus );
$gusLink = membersLink( $appData, $gus );
membersFresh( $appData );
$ask( 'taken.later@example.org' );
$laterDigits = $digitsOfLast();
\Nino\Modules\Members::add( $appData, 'Taken.Later@example.org', 'Later', '' );
$mails = [];
$changedTold = [];
$beforeLater = $accountsFile();
$tooLate = $confirm( $laterDigits );
check( 'an address that another account has taken since is gone - nobody is told whose it is - the request is over, the address is as it was, and nothing was deleted, mailed or told', $tooLate === 'gone' && $request() === null && $accountsFile() === $beforeLater && $mails === [] && $changedTold === []
	&& in_array( hash( 'sha256', $gusLink ), $tokensOf( $gus['id'] ), true ) === true && \Nino\Modules\Members::current( $appData ) !== null );

// An address that another account has is answered like a free one - and mails nothing
membersFresh( $appData );
$mails = [];
$changeRecords = static fn(): int => count( array_filter( membersFile( $GLOBALS['appData'], 'tokens' ), static fn( array $entry ): bool => $entry['kind'] === 'change' ) );
$recordsBefore = $changeRecords();
$haveAnswer = $ask( ' HAL@Example.org ' );
$haveRequest = $request() ?? [];
$haveRecords = $changeRecords();
$haveMails = count( $mails );
$haveWrongs = [];

for( $try = 1; $try <= 5; $try++ )
	$haveWrongs[] = $confirm( '000000' );

$freeAnswer = $ask( 'free.address@example.org' );
$freeDigits = $digitsOfLast();
$freeWrongs = [];

for( $try = 1; $try <= 5; $try++ )
	$freeWrongs[] = $confirm( $wrong( $freeDigits ) );

check( 'an address another account has is sent, as a free one is: the request is kept - with that address, lower case - and no mail goes out and no record of a code is made, hal is not troubled', $haveAnswer === 'sent' && $freeAnswer === 'sent' && ( $haveRequest['email'] ?? '' ) === 'hal@example.org'
	&& $haveMails === 0 && $haveRecords === $recordsBefore && $mailsTo( 'hal@example.org' ) === [] && count( $mails ) === 1 && $changeRecords() === $recordsBefore + 1 );
check( '...and what follows is the same for both: four wrong, then gone, whatever the code - the address is never given away', $haveWrongs === [ 'wrong', 'wrong', 'wrong', 'wrong', 'gone' ] && $freeWrongs === $haveWrongs && \Nino\Modules\Members::account( $appData, $hal['id'] )['email'] === 'hal@example.org' );

// A code to sign in is no code for a change, and the other way round
membersFresh( $appData );
$ask( 'gus.kinds@example.org' );
$changeDigits = $digitsOfLast();
$loginDigits = membersAskCode( $appData, 'gus.race@example.org' );
$loginRequest = membersPending( $appData );
$loginForChange = $confirm( $loginDigits );
$changeForLogin = membersCodeServe( $appData, $changeDigits );
$tokensKinds = array_count_values( array_column( membersFile( $appData, 'tokens' ), 'kind' ) );
check( 'a code to sign in does not confirm a change and the code of a change does not sign in: each is wrong in the other, and neither is spent', $loginDigits !== '' && $changeDigits !== '' && $loginForChange === 'wrong' && $changeForLogin === [ 303, '/join?members=wrong', 'no-store', '' ]
	&& ( $tokensKinds['code'] ?? 0 ) >= 1 && ( $tokensKinds['change'] ?? 0 ) >= 1 && $loginRequest !== null );
check( '...the code of the change, then, confirms it', $confirm( $changeDigits ) === 'ok' && $me()['email'] === 'gus.kinds@example.org' );
membersFresh( $appData );
$ask( 'gus.forged@example.org' );
$forgedRequest = (array) \Nino\Runtime::getSessionValue( $appData, './members/change' );
$forgedHash = hash( 'sha256', ( $forgedRequest['nonce'] ?? '' ). '|424242' );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', membersFile( $appData, 'tokens' ) + [ $forgedHash => [ 'account' => $forgedRequest['account'] ?? '', 'kind' => 'code', 'expires' => time() + 600, 'target' => '' ] ] );
$forgedAnswer = $confirm( '424242' );
check( 'a record of another kind under the hash of the request is no code of the change: wrong, the record is not spent and the address is as it was', $forgedAnswer === 'wrong' && array_key_exists( $forgedHash, membersFile( $appData, 'tokens' ) ) === true && $me()['email'] === 'gus.kinds@example.org' );
membersFresh( $appData );
$ask( 'gus.theirs@example.org' );
$theirRequest = (array) \Nino\Runtime::getSessionValue( $appData, './members/change' );
$theirHash = hash( 'sha256', ( $theirRequest['nonce'] ?? '' ). '|535353' );
$halBefore = \Nino\Modules\Members::account( $appData, $hal['id'] );
\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', membersFile( $appData, 'tokens' ) + [ $theirHash => [ 'account' => $hal['id'], 'kind' => 'change', 'expires' => time() + 600, 'target' => '', 'email' => 'gus.theirs@example.org' ] ] );
$theirAnswer = $confirm( '535353' );
check( 'a record of the right kind that is another account\'s is no code of this member: gone, the request is over and neither account has a new address', $theirAnswer === 'gone' && $me()['email'] === 'gus.kinds@example.org' && \Nino\Modules\Members::account( $appData, $hal['id'] ) === $halBefore && \Nino\Runtime::getSessionValue( $appData, './members/change' ) === null );

// The caps: three requests for one address an hour, and the visitor's own
membersFresh( $appData );
$cap = [ $ask( 'cap.address@example.org' ), $ask( 'cap.address@example.org' ), $ask( 'cap.address@example.org' ) ];
$thirdRequest = $request();
$over = $ask( 'cap.address@example.org' );
check( 'the address is counted: three requests an hour are sent, the fourth is the limit - the request of the session is the third\'s, nothing is replaced', $cap === [ 'sent', 'sent', 'sent' ] && $over === 'limit' && $request() === $thirdRequest );
membersFresh( $appData );
\Nino\Features::saveSettings( $appData, 'members', [ 'attempts' => 2 ] );
$visitor = [ $ask( 'v1@example.org' ), $ask( 'v2@example.org' ), $ask( 'v3@example.org' ) ];
\Nino\Features::saveSettings( $appData, 'members', [ 'attempts' => 5 ] );
membersFresh( $appData );
check( '...and so is the visitor, by the setting attempts', $visitor === [ 'sent', 'sent', 'limit' ] );
membersFresh( $appData );
$ask( 'mix@example.org' );
membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'mix@example.org', 'return' => '/join' ] );
$mixed = [ $ask( 'mix@example.org' ), $ask( 'mix@example.org' ) ];
check( '...the counter is the one of the requests for a login code: a request for a change, one for a code to sign in and another change are the three an address has an hour, the next is the limit', $mixed === [ 'sent', 'limit' ] );

// Hostile addresses: valid ones with a bracket or a quote in them are kept and mailed nothing of their own, and a warning names none
membersFresh( $appData );
$delivery = false;
$mails = [];
ninoWarnings();
$hostile = $ask( '"a[b"@example.org' );
$kept = $request()['email'] ?? '';
$unsent = ninoWarnings();
$delivery = true;
check( 'an address with a bracket in it, a valid one, is kept as it is; a mail no transport takes is a warning that names no address - and the answer is sent all the same', $hostile === 'sent' && $kept === '"a[b"@example.org' && count( $unsent ) >= 1 && str_contains( implode( ' ', $unsent ), 'example.org' ) === false && str_contains( implode( ' ', $unsent ), 'a[b' ) === false );

echo "\n";


membersGateChecks( $appData );
membersBothGatesChecks( $appData );
membersWorkerChecks( $appData );
membersEndChecks( $appData );
membersRemovedChecks( $appData );
membersProofChecks( $appData );
membersRoundTripChecks( $appData );

// --- Deactivation ---------------------------------------------------------------

echo "Modules\\Members - deactivation takes the panel away and keeps what the project has\n";

$kept = [ 'accounts' => membersFile( $appData, 'accounts' ), 'tokens' => membersFile( $appData, 'tokens' ), 'session' => membersFile( $appData, 'session' ) ];
check( 'before it, the three files of the feature are there', array_filter( [ '/data/members.php', '/data/members-tokens.php', '/data/members-session.php' ], static fn( string $file ): bool => \Nino\Filesystem::fileExists( $GLOBALS['appData'], $file ) === false ) === [] );
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );
check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'members' ) === true );
check( '...the panel is not in the registry any more, and its actions are unknown', isset( \Nino\Admin\Admin::panels( $appData )['members'] ) === false && ninoCall( $appData, $panel, [], 'members/list' )[0] === 404 );
check( '...the three files stay as they were, and so does the copy of the login page', $kept === [ 'accounts' => membersFile( $appData, 'accounts' ), 'tokens' => membersFile( $appData, 'tokens' ), 'session' => membersFile( $appData, 'session' ) ]
	&& $kept['accounts'] !== [] && is_file( \Nino\Filesystem::path( $appData, '/templates/page-members-login.tpl' ) ) === true );
check( '...and the class is out of /nino/modules', in_array( '\\Nino\\Modules\\Members', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );

echo "\n";


// --- Devices that stay signed in: the box, the cookie, its token, and every way a device is ended -------------------------------

echo "Modules\\Members - devices that stay signed in: nino_remember, one token for each, replaced with every use\n";

/**
 *	The lines of Set-Cookie the feature set since the last call, as the request left them
 *
 *	@param		array 		&$appData
 *
 *	@return 	array										A list of lines
 */
function membersCookies( array &$appData ): array {

	$lines = (array) ( $appData['./members/cookies'] ?? [] );
	unset( $appData['./members/cookies'] );

	return array_values( array_filter( $lines, 'is_string' ) );
}

/**
 *	The value a line of Set-Cookie sets: the id of the account, a dot and the token - '' for a line that deletes the cookie
 *
 *	@param		string		$line
 *
 *	@return 	string
 */
function membersCookieValue( string $line ): string {

	return preg_match( '/^nino_remember=([0-9a-f]{12}\.[A-Za-z0-9_-]{43});/D', $line, $found ) === 1 ? $found[1] : '';
}

/**
 *	Give an account a device the way a member does: ask for a code, give it with the box ticked or not
 *
 *	@param		array 		&$appData
 *	@param		string		$email
 *	@param		bool			$remember
 *	@param		string		$cookie				The cookie the browser brings along, '' for none
 *
 *	@return 	array										The lines of Set-Cookie of the answer
 */
function membersDevice( array &$appData, string $email, bool $remember = true, string $cookie = '' ): array {

	membersVisitor();
	unset( $_COOKIE['nino_remember'], $appData['./members/cookies'] );
	$digits = membersAskCode( $appData, $email, '/club/news' );

	if( $cookie !== '' )
		$_COOKIE['nino_remember'] = $cookie;

	membersServe( $appData, '/.members/code', 'POST', [ 'code' => $digits, 'return' => '/club/news' ] + ( $remember === true ? [ 'remember' => '1' ] : [] ) );
	unset( $_COOKIE['nino_remember'] );

	return membersCookies( $appData );
}

/**
 *	A visit with a cookie and no session, the way a device comes back
 *
 *	@param		array 		&$appData
 *	@param		string		$cookie				The value of the cookie
 *	@param		string		$uri
 *	@param		string		$method
 *
 *	@return 	array										[ the request, the lines of Set-Cookie, the member who is signed in or null ]
 */
function membersVisitWith( array &$appData, string $cookie, string $uri = '/club/news', string $method = 'GET' ): array {

	membersVisitor();
	unset( $appData['./members/cookies'] );
	$_COOKIE['nino_remember'] = $cookie;
	$request = membersServe( $appData, $uri, $method );
	unset( $_COOKIE['nino_remember'] );

	return [ $request, membersCookies( $appData ), \Nino\Modules\Members::current( $appData ) ];
}

/**
 *	The devices an account keeps, as the file holds them
 *
 *	@param		array 		&$appData
 *	@param		string		$id
 *
 *	@return 	array
 */
function membersDevices( array &$appData, string $id ): array {

	return (array) ( membersFile( $appData, 'accounts' )['accounts'][$id]['remember'] ?? [] );
}

/**
 *	Every file of the sandbox that holds a text - where a token must never be
 *
 *	@param		array 		&$appData
 *	@param		string		$needle
 *
 *	@return 	array										The paths of the files that hold it
 */
function membersFilesHolding( array &$appData, string $needle ): array {

	$found = [];

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( ninoSandboxDir( $appData ), FilesystemIterator::SKIP_DOTS ) ) as $file )
		if( $file->isFile() === true && str_contains( (string) file_get_contents( $file->getPathname() ), $needle ) === true )
			$found[] = $file->getPathname();

	return $found;
}

/**
 *	The checks of the devices: the box, the cookie and its token, the sign-in with it and the replacement of the token, the ends of a device and the count of a bad cookie
 *
 *	@param		array 		&$appData
 *	@param		array 		&$told				What the features that build on the feature were told
 *
 *	@return 	void
 */
function membersDeviceChecks( array &$appData, array &$told ): void {

	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ] ] );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
	$_SERVER['HTTP_USER_AGENT'] = "Agent/1.0 (Test)\r\n". str_repeat( 'x', 400 );
	unset( $_SERVER['HTTPS'] );

	$eve = \Nino\Modules\Members::add( $appData, 'eve@example.org', 'Eve', 'staff' );
	$eveId = is_array( $eve ) === true ? $eve['id'] : '';

	// The box of the second step: a checkbox, off, in the form of the code, in front of the button
	membersVisitor();
	\Nino\Runtime::setSessionValue( $appData, './members/pending', [ 'email' => 'eve@example.org', 'nonce' => str_repeat( 'c', 32 ), 'tries' => 0, 'until' => time() + 600 ] );
	ninoVisit( $appData, '/club/news?members=sent' );
	$step = \Nino\Html::renderHtml( $appData, '[members-login]' );
	$box = membersFind( $step, '//form[contains(concat(" ", normalize-space(@class), " "), " members-login--code ")]//label[contains(concat(" ", normalize-space(@class), " "), " members-login-remember ")]//input' );
	$controls = membersFind( $step, '//form//*[self::input or self::button]' );
	check( 'the second step of the form has a checkbox named remember, value 1, off - inside a label that holds a sentence, in front of the button',
		count( $box ) === 1 && $box[0]->getAttribute( 'type' ) === 'checkbox' && $box[0]->getAttribute( 'name' ) === 'remember' && $box[0]->getAttribute( 'value' ) === '1' && $box[0]->hasAttribute( 'checked' ) === false
		&& trim( (string) membersText( $step, '//label[contains(@class,"members-login-remember")]' ) ) !== '' && $controls !== [] && end( $controls )->nodeName === 'button' );
	\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );
	membersVisitor();
	check( '...the first step has no such box', membersFind( \Nino\Html::renderHtml( $appData, '[members-login]' ), '//input[@name="remember"]' ) === [] );

	// A code without the box makes no device and no cookie
	$plain = membersDevice( $appData, 'eve@example.org', false );
	check( 'a code given without the box signs in and makes no cookie and no device', ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $eveId && $plain === [] && membersDevices( $appData, $eveId ) === [] );

	// With the box: the cookie, its attributes, and what the account keeps
	$stamp = time();
	$lines = membersDevice( $appData, 'eve@example.org' );
	$value = membersCookieValue( $lines[0] ?? '' );
	[ $cookieId, $token ] = array_pad( explode( '.', $value, 2 ), 2, '' );
	$kept = membersDevices( $appData, $eveId );
	check( 'a code given with the box ticked sets one cookie: nino_remember = <id of the account>.<token of 43 characters>, for 90 days (Max-Age), site-wide, HttpOnly, SameSite=Lax - and no Secure over http',
		count( $lines ) === 1 && $value !== '' && $cookieId === $eveId && preg_match( '/; Max-Age=7776000; Path=\/; HttpOnly; SameSite=Lax$/D', $lines[0] ) === 1 && str_contains( $lines[0], 'Secure' ) === false );
	check( '...the account keeps one entry: the sha256 of the token, the time it was made, the end of its 90 days, the user agent cut at 300 characters with its control characters out',
		count( $kept ) === 1 && $kept[0]['hash'] === hash( 'sha256', $token ) && abs( strtotime( (string) $kept[0]['created'] ) - $stamp ) < 10 && abs( $kept[0]['expires'] - ( $stamp + 90 * 86400 ) ) < 10
		&& mb_strlen( $kept[0]['agent'] ) === 300 && str_starts_with( $kept[0]['agent'], 'Agent/1.0 (Test)xxx' ) && preg_match( '/[\x00-\x1f]/', $kept[0]['agent'] ) !== 1 && array_keys( $kept[0] ) === [ 'hash', 'created', 'expires', 'agent' ] );
	check( '...the token is in the cookie and nowhere else: in no file of the project, not in the log and not in a mail - and the account the others read has no list', membersFilesHolding( $appData, $token ) === []
		&& array_key_exists( 'remember', \Nino\Modules\Members::account( $appData, $eveId ) ?? [] ) === false && array_key_exists( 'remember', \Nino\Modules\Members::current( $appData ) ?? [ 'remember' => 1 ] ) === false
		&& str_contains( json_encode( $GLOBALS['mails'] ) ?: '', $token ) === false );

	$_SERVER['HTTPS'] = 'on';
	$secure = membersDevice( $appData, 'eve@example.org' );
	unset( $_SERVER['HTTPS'] );
	check( '...over https the cookie is Secure as well', count( $secure ) === 1 && str_ends_with( $secure[0], '; Secure; SameSite=Lax' ) === true && str_contains( $secure[0], 'HttpOnly' ) === true );
	$cookieOne = $value;
	$valueTwo = membersCookieValue( $secure[0] ?? '' );
	$replaced = membersDevice( $appData, 'eve@example.org', true, $cookieOne );
	$tokenOne = explode( '.', $cookieOne, 2 )[1] ?? '';
	check( '...a device that brings the cookie of an earlier visit takes the place of its token: the list does not grow, and the old token is not in it', count( $replaced ) === 1 && count( membersDevices( $appData, $eveId ) ) === 2
		&& in_array( hash( 'sha256', $tokenOne ), array_column( membersDevices( $appData, $eveId ), 'hash' ), true ) === false );
	$value = $valueTwo;
	$token = explode( '.', $value, 2 )[1] ?? '';

	// The way back: no session, a good cookie
	$told = [];

	// The device had only a short time left: a use gives it the days again
	$state = membersFile( $appData, 'accounts' );
	$state['accounts'][$eveId]['remember'] = array_map( static fn( array $entry ): array => hash_equals( $entry['hash'], hash( 'sha256', $token ) ) === true ? [ 'expires' => time() + 1000 ] + $entry : $entry, $state['accounts'][$eveId]['remember'] );
	\Nino\Filesystem::putFileContent( $appData, '/data/members.php', $state );
	$stamp = time();
	[ $request, $set, $member ] = membersVisitWith( $appData, $value );
	[ $newId, $newToken ] = array_pad( explode( '.', membersCookieValue( $set[0] ?? '' ), 2 ), 2, '' );
	$devices = membersDevices( $appData, $eveId );
	check( 'a visitor with no session and a good cookie is signed in on the page asked for: no gate (not the 401), the member is Eve, the features that build on it are told once',
		( $member['id'] ?? '' ) === $eveId && $request['/nino/http/response']['statusCode'] !== 401 && count( $told ) === 1 && $told[0][0] === 'login' && $told[0][1]['id'] === $eveId );
	check( '...the token is replaced by a new one: another cookie of the same form, the entry holds the hash of the new token and keeps its day of making, its end is 90 days from now whatever it had left, and the old token is in no entry',
		count( $set ) === 1 && $newId === $eveId && $newToken !== '' && $newToken !== $token && array_search( hash( 'sha256', $newToken ), array_column( $devices, 'hash' ), true ) !== false
		&& in_array( hash( 'sha256', $token ), array_column( $devices, 'hash' ), true ) === false && count( $devices ) === 2 && ( array_column( $devices, 'expires', 'hash' )[ hash( 'sha256', $newToken ) ] ?? 0 ) - $stamp >= 90 * 86400 - 10 );
	// Two tabs that open at once bring the same cookie: the one a use has just replaced signs in as well, with no new cookie, no count and no write
	[ , $setTwin, $memberTwin ] = membersVisitWith( $appData, $value );
	$twinDevices = membersDevices( $appData, $eveId );
	check( '...a request that brings the token that was replaced within thirty seconds - two tabs that open at once - is signed in as well, and leaves the new cookie in place: no cookie line, no new token, no try counted',
		( $memberTwin['id'] ?? '' ) === $eveId && $setTwin === [] && $twinDevices === $devices && \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 1 ) === false );

	// ...and thirty-one seconds later the old token is dead
	$state = membersFile( $appData, 'accounts' );
	$state['accounts'][$eveId]['remember'] = array_map( static fn( array $entry ): array => isset( $entry['rotated'] ) === true ? [ 'rotated' => time() - 31 ] + $entry : $entry, $state['accounts'][$eveId]['remember'] );
	\Nino\Filesystem::putFileContent( $appData, '/data/members.php', $state );
	check( '...but after thirty seconds the old token is dead: it signs nobody in, its cookie is deleted (Max-Age=0, no value) and the address has made one wrong try',
		(function() use ( &$appData, $value ): bool {
			[ $request, $set, $member ] = membersVisitWith( $appData, $value );
			return $member === null && $request['/nino/http/response']['statusCode'] === 401 && count( $set ) === 1 && str_starts_with( $set[0], 'nino_remember=; Max-Age=0;' ) && str_contains( $set[0], 'HttpOnly' ) && \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 1 ) === true;
		} )() );

	// The page that is read: a post, an endpoint of the feature and an answer that is no page do not look at the cookie
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$cookie = $newId. '.'. $newToken;
	[ , $setPost, $memberPost ] = membersVisitWith( $appData, $cookie, '/club/news', 'POST' );
	[ , $setEndpoint, $memberEndpoint ] = membersVisitWith( $appData, $cookie, '/.members/login?t=x' );
	$appData['/nino/http/routes']['GET://club/data.json'] = [ 'uri' => '/club/data.json', 'header' => [ 'Content-Type' => 'application/json' ] ];
	[ , $setJson, $memberJson ] = membersVisitWith( $appData, $cookie, '/club/data.json' );
	check( 'only the request for a page is signed in by a cookie: a post, an endpoint of the feature and an answer that is no html leave the token alone, set no cookie and make no session',
		$memberPost === null && $setPost === [] && $memberEndpoint === null && $setEndpoint === [] && $memberJson === null && $setJson === [] && in_array( hash( 'sha256', $newToken ), array_column( membersDevices( $appData, $eveId ), 'hash' ), true ) === true );
	unset( $appData['/nino/http/routes']['GET://club/data.json'] );

	// A HEAD is folded to a GET by the kernel before the feature looks: a monitor or a prefetch must not sign in nor replace the token
	[ , $setHead, $memberHead ] = membersVisitWith( $appData, $cookie, '/club/news', 'HEAD' );
	check( 'a HEAD with a good cookie signs nobody in, sets no cookie and leaves the token as it is', $memberHead === null && $setHead === [] && in_array( hash( 'sha256', $newToken ), array_column( membersDevices( $appData, $eveId ), 'hash' ), true ) === true );

	// A member who is signed in is no business of the cookie: the token stays and no cookie is set
	membersVisitor();
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	membersSignIn( $appData, (array) \Nino\Modules\Members::account( $appData, $eveId ) );
	$_COOKIE['nino_remember'] = $cookie;
	unset( $appData['./members/cookies'] );
	membersServe( $appData, '/club/news' );
	unset( $_COOKIE['nino_remember'] );
	check( 'a visit that has a session leaves the cookie as it is: no new token, no cookie', membersCookies( $appData ) === [] && in_array( hash( 'sha256', $newToken ), array_column( membersDevices( $appData, $eveId ), 'hash' ), true ) === true );

	// Ends of a device: run out, switched off, the epoch of the account renewed, the address changed, a cookie that is none
	$rewrite = static function( string $id, callable $change ) use ( &$appData ): void {
		$state = membersFile( $appData, 'accounts' );
		$state['accounts'][$id]['remember'] = array_map( $change, $state['accounts'][$id]['remember'] );
		\Nino\Filesystem::putFileContent( $appData, '/data/members.php', $state );
	};
	$rewrite( $eveId, static fn( array $entry ): array => [ 'expires' => time() - 5 ] + $entry );
	[ $request, $set, $member ] = membersVisitWith( $appData, $cookie );
	check( 'a token that has run out signs nobody in, and its cookie is deleted', $member === null && count( $set ) === 1 && str_starts_with( $set[0], 'nino_remember=; Max-Age=0' ) );

	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$lines = membersDevice( $appData, 'eve@example.org' );
	$cookie = membersCookieValue( $lines[0] ?? '' );
	\Nino\Modules\Members::change( $appData, $eveId, [ 'active' => false ] );
	check( 'switching an account off empties its list, and a cookie of it signs nobody in', membersDevices( $appData, $eveId ) === [] && membersVisitWith( $appData, $cookie )[2] === null );
	// A list that is there for an account that is off - a file edited by hand, a restore - is no way in either: the account has to be active
	$state = membersFile( $appData, 'accounts' );
	$state['accounts'][$eveId]['remember'] = [ [ 'hash' => hash( 'sha256', explode( '.', $cookie, 2 )[1] ?? '' ), 'created' => '2026-11-01T10:00:00Z', 'expires' => time() + 5000, 'agent' => 'Agent' ] ];
	\Nino\Filesystem::putFileContent( $appData, '/data/members.php', $state );
	[ , $setOff, $memberOff ] = membersVisitWith( $appData, $cookie );
	check( '...and so is a device in the list of an account that is off: it signs nobody in', $memberOff === null && count( $setOff ) === 1 && str_starts_with( $setOff[0], 'nino_remember=; Max-Age=0' ) );
	\Nino\Modules\Members::change( $appData, $eveId, [ 'active' => true ] );

	$lines = membersDevice( $appData, 'eve@example.org' );
	$cookie = membersCookieValue( $lines[0] ?? '' );
	$beforeEpoch = membersFile( $appData, 'accounts' )['accounts'][$eveId]['epoch'] ?? '';
	$changed = \Nino\Modules\Members::change( $appData, $eveId, [ 'email' => 'eve2@example.org' ] );
	check( 'a change of the sign-in address by the owner empties the list: the cookie of the old address signs nobody in', is_array( $changed ) && membersDevices( $appData, $eveId ) === [] && membersVisitWith( $appData, $cookie )[2] === null );

	$lines = membersDevice( $appData, 'eve2@example.org' );
	$cookie = membersCookieValue( $lines[0] ?? '' );
	membersAfter( $appData, \Nino\Modules\Members::askChange( $appData, (array) \Nino\Modules\Members::current( $appData ), 'eve3@example.org' ) );
	$digits = str_replace( ' ', '', membersCodeOf( end( $GLOBALS['mails'] ) ?: [] ) );
	$done = \Nino\Modules\Members::confirmChange( $appData, (array) \Nino\Modules\Members::current( $appData ), $digits );
	check( '...and so does the member\'s own change, with the code of the new address', $done === 'ok' && membersDevices( $appData, $eveId ) === [] && membersVisitWith( $appData, $cookie )[2] === null );

	// Signing out: this device goes, the others stay
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$one = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	$two = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	check( 'two devices: the account keeps two tokens', count( membersDevices( $appData, $eveId ) ) === 2 );
	membersVisitor();
	unset( $appData['./members/cookies'] );
	$_COOKIE['nino_remember'] = $two;
	$told = [];
	membersServe( $appData, '/.members/logout', 'POST' );
	unset( $_COOKIE['nino_remember'] );
	$left = membersDevices( $appData, $eveId );
	$out = membersCookies( $appData );
	check( 'signing out takes the token of this device out of the account and deletes its cookie - the token of the other device stays',
		count( $left ) === 1 && $left[0]['hash'] === hash( 'sha256', explode( '.', $one )[1] ) && count( $out ) === 1 && str_starts_with( $out[0], 'nino_remember=; Max-Age=0;' ) && str_contains( $out[0], 'SameSite=Lax' ) );
	check( '...and the device that signed out is out: the same cookie signs nobody in', membersVisitWith( $appData, $two )[2] === null );
	membersVisitor();
	unset( $appData['./members/cookies'] );
	$_COOKIE['nino_remember'] = 'garbage';
	membersServe( $appData, '/.members/logout', 'POST' );
	unset( $_COOKIE['nino_remember'] );
	check( '...a sign-out with a cookie that is garbage deletes it too and changes nothing', count( membersCookies( $appData ) ) === 1 && count( membersDevices( $appData, $eveId ) ) === 1 );

	// The eleventh device takes the place of the oldest
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$first = $one;
	$cookies = [ $first ];
	for( $round = 0; $round < 10; $round++ )
		$cookies[] = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	$list = membersDevices( $appData, $eveId );
	check( 'an account keeps ten devices at most: the eleventh makes the oldest go, and the cookie of that one signs nobody in',
		count( $list ) === 10 && in_array( hash( 'sha256', explode( '.', $first )[1] ), array_column( $list, 'hash' ), true ) === false && in_array( hash( 'sha256', explode( '.', $cookies[10] )[1] ), array_column( $list, 'hash' ), true ) === true
		&& membersVisitWith( $appData, $first )[2] === null );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	check( '...and the newest of them is as good as ever', ( membersVisitWith( $appData, $cookies[10] )[2]['id'] ?? '' ) === $eveId );

	// Every device of an account: forgetAll() - the list empty, the epoch of the account new, no session of it left but the one that asked
	$mine = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	$sessionOther = $_SESSION;
	$epoch = membersFile( $appData, 'accounts' )['accounts'][$eveId]['epoch'] ?? '';
	$_COOKIE['nino_remember'] = $mine;
	unset( $appData['./members/cookies'] );
	$did = \Nino\Modules\Members::forgetAll( $appData, $eveId );
	$stayed = ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $eveId;
	$cookieOut = membersCookies( $appData );
	unset( $_COOKIE['nino_remember'] );
	$newEpoch = membersFile( $appData, 'accounts' )['accounts'][$eveId]['epoch'] ?? '';
	$sessionHere = $_SESSION;
	$_SESSION = $sessionOther;
	check( 'forgetAll() empties the list and gives the account an epoch of its own - 32 hex characters, not the one it had', $did === true && membersDevices( $appData, $eveId ) === [] && preg_match( '/^[0-9a-f]{32}$/D', $newEpoch ) === 1 && $newEpoch !== $epoch );
	check( '...the session that asked stays - written again with the new epoch - and the cookie of that device is deleted', $stayed === true && count( $cookieOut ) === 1 && str_starts_with( $cookieOut[0], 'nino_remember=; Max-Age=0;' ) );
	check( '...a session of another device, which carries the old epoch, reads signed out', \Nino\Modules\Members::current( $appData ) === null && ( $sessionHere[$appData['./nino/uid']]['./members/account']['own'] ?? '' ) === $newEpoch );
	check( '...a device that had a token is out as well, and an account nobody has is false', membersVisitWith( $appData, $mine )[2] === null && \Nino\Modules\Members::forgetAll( $appData, str_repeat( 'a', 12 ) ) === false );

	// forgetAll() for another account does not touch the session of the one who is signed in (the owner's way)
	membersVisitor();
	$flo = \Nino\Modules\Members::add( $appData, 'flo@example.org', 'Flo', '' );
	membersSignIn( $appData, (array) \Nino\Modules\Members::account( $appData, $eveId ) );
	$sessionOf = \Nino\Runtime::getSessionValue( $appData, './members/account' );
	unset( $appData['./members/cookies'] );
	$other = \Nino\Modules\Members::forgetAll( $appData, is_array( $flo ) === true ? $flo['id'] : '' );
	check( 'forgetAll() of another account leaves the session of whoever asks and its cookie as they are', $other === true && \Nino\Runtime::getSessionValue( $appData, './members/account' ) === $sessionOf && membersCookies( $appData ) === [] );

	// Sign everybody out ends the devices of everybody
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$cookieA = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	check( 'signOutAll() ends the devices as it ends the sessions: every list is empty and the cookie signs nobody in', \Nino\Modules\Members::signOutAll( $appData ) === true && membersDevices( $appData, $eveId ) === [] && membersVisitWith( $appData, $cookieA )[2] === null );
	check( '...and with nothing to end it is as quiet as before', \Nino\Modules\Members::signOutAll( $appData ) === true );

	// A bad cookie is one try of its address, and an address that has brought twenty is not looked at any more
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$bad = [ 'garbage', 'abcdefabcdef.'. str_repeat( 'A', 43 ), $eveId. '.'. str_repeat( 'B', 43 ), $eveId. '.'. str_repeat( 'B', 42 ) ];
	$deleted = 0;

	foreach( $bad as $oneBad ) {
		$visit = membersVisitWith( $appData, $oneBad );
		$deleted += count( array_filter( $visit[1], static fn( string $line ): bool => str_starts_with( $line, 'nino_remember=; Max-Age=0' ) ) );
	}

	check( 'a cookie that is garbage, of an account nobody has, of a token the account does not hold or of the wrong length is deleted each time', $deleted === 4 );
	check( '...and each one is a try of the address: four are in the counter, and the address is not yet at its end', \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 4 ) === true && \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 5 ) === false );
	$good = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );

	for( $round = 0; $round < 20; $round++ )
		\Nino\Feature::count( $appData, 'members-tries', 'remember|127.0.0.1', 20, 3600 );

	[ , $set, $member ] = membersVisitWith( $appData, $good );
	check( 'after twenty wrong cookies in an hour a good cookie still signs in, and rotates: the count never stands in the way of a good token',
		( $member['id'] ?? '' ) === $eveId && count( $set ) === 1 && membersCookieValue( $set[0] ) !== '' && membersCookieValue( $set[0] ) !== $good );
	[ , $setBad ] = membersVisitWith( $appData, 'abcdefabcdef.'. str_repeat( 'A', 43 ) );
	check( '...a wrong cookie from such an address is neither counted nor deleted any more - the count stays at twenty', $setBad === [] && \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 20 ) === true && \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 21 ) === false );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	[ , $setNext ] = membersVisitWith( $appData, 'abcdefabcdef.'. str_repeat( 'A', 43 ) );
	check( '...and in the next window a wrong cookie is deleted and counted again', count( $setNext ) === 1 && str_starts_with( $setNext[0], 'nino_remember=; Max-Age=0' ) && \Nino\Feature::spent( $appData, 'members-tries', 'remember|127.0.0.1', 1 ) === true );

	// The days of a device: the setting - and the default where the setting is out of its bounds (the kernel hands the default for a value that does not validate)
	$settingsWas = $appData['/nino/features']['members']['settings']['rememberDays'] ?? null;
	$ages = [];

	foreach( [ 30, 400, 0 ] as $days ) {
		$appData['/nino/features']['members']['settings']['rememberDays'] = $days;
		$ages[$days] = preg_match( '/; Max-Age=([0-9]+);/', membersDevice( $appData, 'eve3@example.org' )[0] ?? '', $found ) === 1 ? (int) $found[1] : -1;
	}

	if( $settingsWas === null )
		unset( $appData['/nino/features']['members']['settings']['rememberDays'] );
	else
		$appData['/nino/features']['members']['settings']['rememberDays'] = $settingsWas;

	check( 'the Max-Age of the cookie is the days of the setting rememberDays: 30 days for 30, and the 90 of the default for a value outside 1 to 365 - 400 and 0 - as the kernel hands it', $ages === [ 30 => 30 * 86400, 400 => 90 * 86400, 0 => 90 * 86400 ] );

	// The word of the box is the project's: where it has none the box is not drawn, and no key of the unit is left standing in the form
	$wordWas = \Nino\Html::resolveTextfill( $appData, '/feature/members/code/remember' );
	membersVisitor();
	\Nino\Runtime::setSessionValue( $appData, './members/pending', [ 'email' => 'eve@example.org', 'nonce' => str_repeat( 'd', 32 ), 'tries' => 0, 'until' => time() + 600 ] );
	ninoVisit( $appData, '/club/news?members=sent' );
	$texts = [];

	foreach( [ 'de_DE', 'en_US' ] as $locale ) {
		$texts[$locale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
		$cut = (array) $texts[$locale];
		unset( $cut['[[/feature/members/code/remember]]'] );
		\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $cut );
	}

	$bare = \Nino\Html::renderHtml( $appData, '[members-login]' );

	foreach( $texts as $locale => $text )
		\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $text );

	\Nino\Runtime::unsetSessionValue( $appData, './members/pending' );
	check( 'a project whose words have no label for the box gets no box and no bare key: the form has the field for the code and not the box, and no [[/feature/ is left in it', $wordWas !== null && membersFind( $bare, '//input[@name="code"]' ) !== [] && membersFind( $bare, '//input[@name="remember"]' ) === [] && str_contains( $bare, '[[/feature/' ) === false );

	// A sign-in the epoch cannot be written for is no sign-in: a code that is good is answered with the 500 and nobody is signed in
	$locks = \Nino\Filesystem::path( $appData, '/data' ). '/.locks';
	membersVisitor();
	$digits = membersAskCode( $appData, 'eve3@example.org', '/club/news' );
	foreach( glob( $locks. '/*.lock' ) ?: [] as $lockFile )
		unlink( $lockFile );
	\Nino\Filesystem::mutate( $appData, '/data/members-session.php', static fn( mixed $state ): ?array => null, [] );
	$epochLock = ( glob( $locks. '/*.lock' ) ?: [] )[0] ?? '';
	unlink( $epochLock );
	mkdir( $epochLock );
	ninoWarnings();
	$refused = membersServe( $appData, '/.members/code', 'POST', [ 'code' => $digits, 'return' => '/club/news', 'remember' => '1' ] );
	rmdir( $epochLock );
	ninoWarnings();
	check( 'where the epoch cannot be written a good code answers 500 and signs nobody in - and sets no cookie, makes no device', $refused['/nino/http/response']['statusCode'] === 500 && \Nino\Modules\Members::current( $appData ) === null && membersCookies( $appData ) === [] );

	// Signing out with the token a use has just replaced - the second tab - takes the device as well
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tries.php', [] );
	$first = membersCookieValue( membersDevice( $appData, 'eve3@example.org' )[0] ?? '' );
	$before = count( membersDevices( $appData, $eveId ) );
	[ , $setRotated ] = membersVisitWith( $appData, $first );
	membersVisitor();
	unset( $appData['./members/cookies'] );
	$_COOKIE['nino_remember'] = $first;
	membersServe( $appData, '/.members/logout', 'POST' );
	unset( $_COOKIE['nino_remember'] );
	membersCookies( $appData );
	check( 'signing out with the token that was just replaced takes the device out of the account all the same: the list is one shorter, and the new token signs nobody in',
		count( $setRotated ) === 1 && count( membersDevices( $appData, $eveId ) ) === $before - 1 && membersVisitWith( $appData, membersCookieValue( $setRotated[0] ) )[2] === null );

	// The box is a row that starts at the edge: the form stands in a centred section, and the sentence of the box must not centre itself
	check( 'the rule of the form sets text-align: start - the sentence beside the box does not take the centring of the section the form stands in',
		preg_match( '/\.members-login,[^{]*\{[^}]*text-align:\s*start;/', (string) file_get_contents( dirname( __DIR__ ). '/groups/members/part/part.css' ) ) === 1 );

	// A token is compared with hash_equals() wherever a list of devices is looked at - for a signature of the code that no test of behaviour can see
	$source = (string) file_get_contents( dirname( __DIR__ ). '/Members.php' );
	check( 'the hash of a token is compared with hash_equals() in the sign-in, the sign-out and the replacement of a device, and with no other operator',
		substr_count( $source, "hash_equals( \$entry['hash'], " ) === 3 && substr_count( $source, "hash_equals( \$entry['previous'] ?? " ) === 2 && preg_match( '/\$entry\[.(hash|previous).\]\s*[!=]==?\s/', $source ) !== 1 );

	// The paths a feature opens under a protected prefix
	$open = static function( array &$appData, array &$list ): void { $list = array_merge( $list, [ '/club/app.webmanifest', '/club/icons/*', '/.members/x', '/_admin/x', 7, 'club/relative', "/club/a\0b" ] ); };
	\Nino\Callbacks::registerCallback( $appData, '/members/open', $open );
	check( 'a feature opens paths under a protected prefix through /members/open: those are not protected, the rest of the prefix is - and an endpoint, a path with a null byte and what is no path are never opened',
		\Nino\Modules\Members::protects( $appData, '/club/app.webmanifest' ) === false && \Nino\Modules\Members::protects( $appData, '/club/icons/a.svg' ) === false && \Nino\Modules\Members::protects( $appData, '/club/news' ) === true
		&& \Nino\Modules\Members::open( $appData ) === [ '/club/app.webmanifest', '/club/icons/*' ] );
	$appData['/nino/http/routes']['GET://club/app.webmanifest'] = [ 'uri' => '/club/app.webmanifest', 'header' => [ 'Content-Type' => 'application/manifest+json' ] ];
	membersVisitor();
	$page = membersServe( $appData, '/club/app.webmanifest' );
	check( '...and a visitor is answered what the route answers there, not the login page', $page['/nino/http/response']['statusCode'] !== 401 );
	unset( $appData['/nino/http/routes']['GET://club/app.webmanifest'] );
	unset( $appData['./nino/callbacks']['/members/open'] );
	check( '...and with no feature that opens one the whole prefix is protected again', \Nino\Modules\Members::protects( $appData, '/club/app.webmanifest' ) === true );

	unset( $_SERVER['HTTP_USER_AGENT'] );
}

membersDeviceChecks( $appData, $told );
echo "\n";

// --- The slot over the field of the login form -------------------------------------------

/**
 *	The callback '/members/login-form': what a feature puts into note stands over the field, as it is - and nothing stands there without one
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersLoginSlotChecks( array &$appData ): void {

	membersVisitor();
	ninoVisit( $appData, '/join?x=1' );
	$plain = \Nino\Html::renderHtml( $appData, '[members-login]' );
	$seen = [];
	\Nino\Callbacks::registerCallback( $appData, '/members/login-form', static function( array &$appData, array &$args ) use ( &$seen ): void {
		$seen[] = $args;
		$args['note'] = '<p class="slot-note">'. \Nino\Components::escape( (string) $args['return'] ). '</p>';
	} );
	ninoVisit( $appData, '/join?x=1' );
	$filled = \Nino\Html::renderHtml( $appData, '[members-login]' );
	\Nino\Callbacks::removeCallbacks( $appData, '/members/login-form' );
	check( 'the form fires /members/login-form with the path of the page and an empty note; what a callback puts into note stands in the form, before the label, as it is',
		count( $seen ) === 1 && $seen[0] === [ 'return' => '/join', 'note' => '' ] && count( membersFind( $filled, '//form/p[contains(@class, "slot-note")]/following-sibling::label' ) ) === 1
		&& membersFind( $filled, '//form/p[contains(@class, "slot-note")]' )[0]->textContent === '/join' );
	check( '...without a callback nothing stands there and no token of the slot is left; the form is the form it was', membersFind( $plain, '//form/p[contains(@class, "slot-note")]' ) === [] && str_contains( $plain, '[[note]]' ) === false && str_contains( $filled, '[[note]]' ) === false
		&& count( membersFind( $plain, '//form//input' ) ) === count( membersFind( $filled, '//form//input' ) ) );
}

membersLoginSlotChecks( $appData );

// --- The panel's script, where node is on the path -----------------------------------
//
// members-js-smoke.js beside this file draws the screen over the kernel's own
// Nino.admin.js and a stand-in for the rest of Nino - the checkout this suite runs
// against, which is handed on; this suite runs it too where node is on the path, so
// that bin/check.sh and CI cover both halves in one go
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: members-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/members-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'members-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

echo "\n";
membersFixRoundChecks( $appData );
membersRememberFixChecks( $appData );
membersRemoveGroupsChecks( $appData );

echo "\n";
ninoDone( $appData );

/**
 *	The checks of the first round of errors: the link of the owner with its kind, its days and its mail; where a link that ran out led; the place on the page behind the
 *	return of the form; and the sign-out as a post with the token
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersFixRoundChecks( array &$appData ): void {

	global $mails, $told;

	echo "Modules\\Members - the first round of errors\n";

	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ], 'start' => '/' ] );
	\Nino\Filesystem::putFileContent( $appData, '/data/members.php', [] );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
	$member = \Nino\Modules\Members::add( $appData, 'fix@example.org', 'Fix', '' );
	$days = (int) \Nino\Features::setting( $appData, 'members', 'inviteDays', 7 ) * 86400;
	$minutes = (int) \Nino\Features::setting( $appData, 'members', 'linkMinutes', 15 ) * 60;
	$target = '/club/news#beleg-7';

	// The kind send: the link the owner sends to an account that is a member already
	membersFresh( $appData );
	$count = count( $mails );
	$sent = \Nino\Modules\Members::link( $appData, $member, 'send', $target, true );
	$token = $sent === true && count( $mails ) === $count + 1 ? membersTokenOf( $mails[$count] ) : '';
	$entry = (array) ( membersFile( $appData, 'tokens' )[ hash( 'sha256', $token ) ] ?? [] );
	membersFresh( $appData );
	$login = membersLink( $appData, $member, 'login' );
	$loginEntry = (array) ( membersFile( $appData, 'tokens' )[ hash( 'sha256', $login ) ] ?? [] );
	check( 'a link of the kind send is kept for the days of an invitation, a link to sign in for the minutes of the setting, and the record carries the target', ( $entry['kind'] ?? '' ) === 'send' && abs( ( $entry['expires'] ?? 0 ) - time() - $days ) <= 5 && ( $entry['target'] ?? '' ) === $target
		&& ( $loginEntry['kind'] ?? '' ) === 'login' && abs( ( $loginEntry['expires'] ?? 0 ) - time() - $minutes ) <= 5 && $days > $minutes );
	check( '...its mail is the one of its kind: the subject is the word of send-subject and not the one of the login link, and the link is in it', trim( (string) $mails[$count]['subject'] ) === trim( \Nino\Html::renderHtml( $appData, '[[/feature/members/mail/send-subject]]' ) ) && $mails[$count]['subject'] !== \Nino\Html::renderHtml( $appData, '[[/feature/members/mail/login-subject]]' )
		&& str_contains( (string) $mails[$count]['body'], '[[' ) === false && str_contains( (string) $mails[$count]['body'], '/.members/login?t='. $token ) === true );
	check( '...and its body is the text of the kind: the intro and the validity of send, and not the intro of the login link', str_contains( (string) $mails[$count]['body'], trim( \Nino\Html::renderHtml( $appData, '[[/feature/members/mail/send-intro]]' ) ) ) === true && str_contains( (string) $mails[$count]['body'], trim( \Nino\Html::renderHtml( $appData, '[[/feature/members/mail/send-valid]]' ) ) ) === true
		&& str_contains( (string) $mails[$count]['body'], trim( \Nino\Html::renderHtml( $appData, '[[/feature/members/mail/login-intro]]' ) ) ) === false );
	check( '...a kind that is none is no link', \Nino\Modules\Members::link( $appData, $member, 'code', '', true ) === false && \Nino\Modules\Members::link( $appData, $member, 'bogus', '', true ) === false );

	// What the page of a link that ran out leads on to: the target the server stored, for the code of the next sign-in, and nothing the address says
	$file = membersFile( $appData, 'tokens' );
	$file[ hash( 'sha256', $token ) ]['expires'] = time() - 60;
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );
	membersVisitor();
	$ended = membersAnswer( membersServe( $appData, '/.members/login?t='. $token. '&return=%2Fclub%2Fother' ) );
	check( 'the link that ran out answers the login page in place, 400, and the session holds the target the record has - not one of the address', $ended[0] === 400 && $ended[3] === '[template /templates/page-members-login]' && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === $target );
	$code = membersAskCode( $appData, 'fix@example.org', '/.members/login' );
	$signed = membersCodeServe( $appData, $code, '/.members/login' );
	check( '...and the code the form of that page - whose return is the page of the link - asks for leads to the page of the link, place and all', $signed[0] === 303 && $signed[1] === $target && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $member['id'] );

	$none = [];
	$forged = static fn(): string => rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	$put = static function( string $text, int $expires, string $kind = 'send' ) use ( &$appData, $member, $forged ): string {
		$token = $forged();
		$file = membersFile( $appData, 'tokens' );
		$file[ hash( 'sha256', $token ) ] = [ 'account' => $member['id'], 'kind' => $kind, 'expires' => $expires, 'target' => $text ];
		\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );

		return $token;
	};
	$kept = ( new ReflectionClassConstant( \Nino\Modules\Members::class, 'LINK_KEEP' ) )->getValue();

	foreach( [ 'a token the file does not know' => $forged(), 'a target that is no path' => $put( '//evil.example', time() - 60 ), 'a target that is an endpoint' => $put( '/.members/logout', time() - 60 ), 'a record older than it is kept' => $put( $target, time() - $kept - 60 ),
		'a record of a code' => $put( $target, time() - 60, 'code' ), 'a text that is no token' => 'abc' ] as $label => $text ) {
		membersVisitor();
		membersServe( $appData, '/.members/login?t='. $text );
		membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => $text ] );
		$none[$label] = \Nino\Runtime::getSessionValue( $appData, './members/target' ) === null;
	}

	$live = $put( $target, time() + 600 );
	membersVisitor();
	$page = membersAnswer( membersServe( $appData, '/.members/login?t='. $live ) );
	$none['a link that is still good'] = $page[0] === 200 && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === null;
	membersConfirm( $appData, $live );
	membersVisitor();
	membersServe( $appData, '/.members/login?t='. $live );
	$none['a link that was spent'] = \Nino\Runtime::getSessionValue( $appData, './members/target' ) === null;
	check( 'no target is kept for a token the file does not know, for a target that is no path or an endpoint, a record older than the server keeps it, a code, a text that is no token, a link that is still good and a link that was spent', array_filter( $none, static fn( bool $same ): bool => $same === false ) === [] && count( $none ) === 8 );

	// Codes that ran out go with the next write, links that ran out stay for the time they are kept; a switched-off account with a link that is good has no target to lead to
	$file = membersFile( $appData, 'tokens' );
	$dead = [ 'code' => [ 'a1', [] ], 'change' => [ 'a2', [ 'email' => 'neu@example.org' ] ] ];

	foreach( $dead as $kind => [ $label, $extra ] )
		$file[ hash( 'sha256', 'dead-'. $kind ) ] = [ 'account' => $member['id'], 'kind' => $kind, 'expires' => time() - 60, 'target' => '/club/x' ] + $extra;

	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );
	$linkKept = $put( $target, time() - 60 );
	membersLink( $appData, $member, 'login' );
	$after = membersFile( $appData, 'tokens' );
	check( 'an entry of a code or of a change that ran out goes with the next write that makes a token, and the record of a link that ran out stays', isset( $after[ hash( 'sha256', 'dead-code' ) ] ) === false && isset( $after[ hash( 'sha256', 'dead-change' ) ] ) === false && isset( $after[ hash( 'sha256', $linkKept ) ] ) === true );
	$off = \Nino\Modules\Members::add( $appData, 'off@example.org', 'Off', '' );
	\Nino\Modules\Members::change( $appData, $off['id'], [ 'active' => false ] );
	$offToken = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	$file = membersFile( $appData, 'tokens' );
	$file[ hash( 'sha256', $offToken ) ] = [ 'account' => $off['id'], 'kind' => 'send', 'expires' => time() + 600, 'target' => $target ];
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );
	membersVisitor();
	$offPage = membersAnswer( membersServe( $appData, '/.members/login?t='. $offToken ) );
	check( 'a switched-off account with a link that is good is refused (400) and the session keeps no target for it', $offPage[0] === 400 && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === null );

	// The place on the page behind the return of the form: kept for the page the gate kept the path of, as letters, digits, hyphens and underscores
	$cases = [
		'a place'                 => [ '/club/news#a-1', '/club/news?x=1#a-1' ],
		'capitals and underscore' => [ '/club/news#Beleg_7', '/club/news?x=1#Beleg_7' ],
		'no place'                => [ '/club/news', '/club/news?x=1' ],
		'a second hash'           => [ '/club/news#a#b', '/club/news?x=1' ],
		'markup'                  => [ '/club/news#<img', '/club/news?x=1' ],
		'an encoded hash'         => [ '/club/news#a%23b', '/club/news?x=1' ],
		'an empty place'          => [ '/club/news#', '/club/news?x=1' ],
		'a blank'                 => [ '/club/news#a b', '/club/news?x=1' ],
		'65 characters'           => [ '/club/news#'. str_repeat( 'a', 65 ), '/club/news?x=1' ],
		'another page'            => [ '/club/other#a-1', '/' ],
		'a query of the return'   => [ '/club/news?y=2#a-1', '/club/news?x=1#a-1' ],
	];
	$led = [];

	foreach( $cases as $label => [ $return, $expected ] ) {
		membersVisitor();
		membersServe( $appData, '/club/news?x=1' );
		$code = membersAskCode( $appData, 'fix@example.org', $return );
		$led[$label] = membersCodeServe( $appData, $code, $return )[1] === $expected;
	}

	check( 'the gate keeps the page asked for and the form of the login brings the place of the address behind its return: the sign-in leads to the page with the place - and to the page alone where the place is no letters, digits, hyphens and underscores, is longer than 64 - the query of the return does not matter - and to the start page where the return names another page', array_filter( $led, static fn( bool $same ): bool => $same === false ) === [] && count( $led ) === 11 );
	membersVisitor();
	$redirect = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'fix@example.org', 'return' => '/club/news#a-1' ] ) );
	check( '...and the return that carries it still goes back to the path of the page alone: the redirect of the form has no place and no query of the return', $redirect[1] === '/club/news?members=sent' );
	membersVisitor();
	membersServe( $appData, '/club/news?x=1' );
	$asked = membersAskCode( $appData, 'fix@example.org', '/club/news#a-1' );
	$again = membersCodeServe( $appData, $asked, '/club/news#zzz-9' );
	check( '...and what the code step posts as its return is not where it leads: the record of the code does', $again[1] === '/club/news?x=1#a-1' );

	// The sign-out is a post with the token of the session
	membersSignIn( $appData, $member );
	$told = [];
	$get = membersServe( $appData, '/.members/logout' );
	check( 'a GET of /.members/logout is 405 with the header Allow and a page that links to the start page - nobody is signed out, nobody is told', $get['/nino/http/response']['statusCode'] === 405 && ( $get['/nino/http/response']['header']['Allow'] ?? '' ) === 'POST' && ( $get['/nino/http/response']['header']['Cache-Control'] ?? '' ) === 'no-store'
		&& membersFind( (string) $get['/nino/http/response']['body'], '//a/@href' )[0]->nodeValue === '/' && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $member['id'] && $told === [] );
	$blocked = membersServe( $appData, '/.members/logout', 'POST', [], false );
	$wrong = membersServe( $appData, '/.members/logout', 'POST', [ '_csrf' => 'not-the-token' ], false );
	check( '...a post without the token and one with a token that is wrong are 403 and sign nobody out', $blocked['/nino/http/response']['statusCode'] === 403 && $wrong['/nino/http/response']['statusCode'] === 403 && ( \Nino\Modules\Members::current( $appData )['id'] ?? '' ) === $member['id'] && $told === [] );
	$out = membersServe( $appData, '/.members/logout', 'POST' );
	check( '...a post with the token signs the member out, tells the features once and goes to the start page with ?members=out', membersAnswer( $out )[0] === 303 && membersAnswer( $out )[1] === '/?members=out' && \Nino\Modules\Members::current( $appData ) === null && count( $told ) === 1 && $told[0][0] === 'logout' );
	check( '...the routes of the endpoint are the GET that answers 405 and the POST that signs out', ( $appData['/nino/http/routes']['POST://.members/logout']['uri'] ?? '' ) === '/.members/logout' && ( $appData['/nino/http/routes']['GET://.members/logout']['uri'] ?? '' ) === '/.members/logout' );
}

/**
 *	The checks of the last round: a GET of the sign-out leaves the device as it is, and a link that ran out is no reason for a device that stays signed in to ask for a code
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersRememberFixChecks( array &$appData ): void {

	global $mails;

	echo "Modules\\Members - the sign-out by GET and a link that ran out, on a device that stays signed in\n";

	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ], 'start' => '/' ] );
	$dev = \Nino\Modules\Members::add( $appData, 'dev-fix@example.org', 'Dev', '' );
	membersFresh( $appData );
	$cookie = membersCookieValue( membersDevice( $appData, 'dev-fix@example.org' )[0] ?? '' );
	$before = membersDevices( $appData, $dev['id'] );

	$get = membersVisitWith( $appData, $cookie, '/.members/logout' );
	check( 'a GET of /.members/logout is 405 and leaves the token of the device in the account and the cookie alone: no line of Set-Cookie, and the same cookie still signs in', $get[0]['/nino/http/response']['statusCode'] === 405 && $get[1] === []
		&& membersDevices( $appData, $dev['id'] ) === $before && ( membersVisitWith( $appData, $cookie )[2]['id'] ?? '' ) === $dev['id'] );

	// The cookie was replaced by that last visit: a device of its own
	membersFresh( $appData );
	$cookie = membersCookieValue( membersDevice( $appData, 'dev-fix@example.org' )[0] ?? '' );
	$target = '/club/news#beleg-3';
	membersFresh( $appData );
	$count = count( $mails );
	\Nino\Modules\Members::link( $appData, $dev, 'send', $target, true );
	$token = membersTokenOf( $mails[$count] );
	$file = membersFile( $appData, 'tokens' );
	$file[ hash( 'sha256', $token ) ]['expires'] = time() - 60;
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $file );
	$count = count( $mails );

	$with = membersVisitWith( $appData, $cookie, '/.members/login?t='. $token );
	$answer = membersAnswer( $with[0] );
	check( 'a link that ran out, opened on a device with a good cookie: 303 to the page the link led to, the member is signed in and no mail goes out - no page that says the link does not work, no form', $answer[0] === 303 && $answer[1] === $target && ( $with[2]['id'] ?? '' ) === $dev['id'] && count( $mails ) === $count );

	$without = membersVisitWith( $appData, 'garbage', '/.members/login?t='. $token );
	membersVisitor();
	unset( $_COOKIE['nino_remember'] );
	$plain = membersAnswer( membersServe( $appData, '/.members/login?t='. $token ) );
	check( '...and with no cookie, or one that is no good, it is the page that says so (400) with the target kept for the code, as it is', $plain[0] === 400 && $plain[3] === '[template /templates/page-members-login]' && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === $target
		&& membersAnswer( $without[0] )[0] === 400 && $without[2] === null );
}

/**
 *	Members::removeGroups(): the accounts of the groups a test picks are deleted in one write, with the links and codes they had out; the accounts of other groups, with their links, stay; a pick that matches nothing writes nothing
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersRemoveGroupsChecks( array &$appData ): void {

	echo "\nThe removal of groups\n";

	$members = \Nino\Modules\Members::class;
	$ignored = ninoWarnings();
	membersFresh( $appData );
	$one = $members::add( $appData, 'rg-one@example.org', 'Eins', 'rg-gone' );
	$two = $members::add( $appData, 'rg-two@example.org', 'Zwei', 'rg-gone' );
	$kept = $members::add( $appData, 'rg-kept@example.org', 'Bleibt', 'rg-kept' );
	$links = [ 'one' => (string) $members::link( $appData, $one, 'invite', '', true, false ), 'two' => (string) $members::link( $appData, $two, 'login', '', true, false ), 'kept' => (string) $members::link( $appData, $kept, 'invite', '', true, false ) ];
	$tokens = static fn(): array => array_column( (array) \Nino\Filesystem::getFileContent( $appData, $members::TOKENS, [] ), 'account' );
	$accountsBefore = (string) file_get_contents( \Nino\Filesystem::path( $appData, $members::ACCOUNTS ) );

	check( 'the world is there: three accounts in two groups, each with a link out', is_array( $one ) && is_array( $two ) && is_array( $kept ) && count( array_filter( $links ) ) === 3 && count( array_intersect( $tokens(), [ $one['id'], $two['id'], $kept['id'] ] ) ) === 3 );

	$nothing = $members::removeGroups( $appData, static fn( string $group ): bool => $group === 'rg-nobody' );
	check( 'a pick that matches no group deletes nothing and answers an empty list: the file of the accounts is as it was, byte for byte', $nothing === [] && (string) file_get_contents( \Nino\Filesystem::path( $appData, $members::ACCOUNTS ) ) === $accountsBefore );

	$gone = $members::removeGroups( $appData, static fn( string $group ): bool => $group === 'rg-gone' );
	sort( $gone );
	$expected = [ $one['id'], $two['id'] ];
	sort( $expected );
	$warned = ninoWarnings();
	check( 'the accounts of the group picked go - the answer lists their ids, and no warning is raised', $gone === $expected && $warned === [] );
	check( '...the accounts of the group are gone, each of them, and the account of the other group stays', $members::account( $appData, $one['id'] ) === null && $members::account( $appData, $two['id'] ) === null && $members::account( $appData, $kept['id'] ) !== null );
	check( '...the links and codes of the accounts that went are gone with them, and the link of the account that stays is still out', $tokens() !== [] && array_intersect( $tokens(), $expected ) === [] );
	check( '...and that link is the account\'s own that stayed', in_array( $kept['id'], $tokens(), true ) === true );
	check( '...a second pick of the same group finds nothing', $members::removeGroups( $appData, static fn( string $group ): bool => $group === 'rg-gone' ) === [] );

	$members::removeGroups( $appData, static fn( string $group ): bool => $group === 'rg-kept' );

	// An id of nothing but digits is a number as a key of an array in php: the account is found, deleted and its links and codes go with it all the same
	$plain = $members::add( $appData, 'rg-digits@example.org', 'Ziffern', 'rg-digits' );
	\Nino\Filesystem::mutate( $appData, $members::ACCOUNTS, static function( mixed $state ) use ( $plain ): array {
		$state['accounts']['123456789012'] = $state['accounts'][ $plain['id'] ];
		unset( $state['accounts'][ $plain['id'] ] );
		return $state;
	}, [] );
	$digits = $members::account( $appData, '123456789012' );
	$digitsLink = is_array( $digits ) === true ? (string) $members::link( $appData, $digits, 'login', '', true, false ) : '';
	check( 'an account whose id is twelve digits is there, with a link out', is_array( $digits ) === true && $digits['id'] === '123456789012' && $digitsLink !== '' && in_array( '123456789012', $tokens(), true ) === true );
	$digitsGone = $members::removeGroups( $appData, static fn( string $group ): bool => $group === 'rg-digits' );
	check( '...removing its group deletes it and answers its id as a string, and the link and the codes it had go with it', $digitsGone === [ '123456789012' ] && $members::account( $appData, '123456789012' ) === null && in_array( '123456789012', $tokens(), true ) === false );

	// The same for the other place that holds an id as a key and compares it as a string: forgetAll() ends the links and codes of such an account
	$made = $members::add( $appData, 'rg-forget@example.org', 'Ziffern', 'rg-digits' );
	\Nino\Filesystem::mutate( $appData, $members::ACCOUNTS, static function( mixed $state ) use ( $made ): array {
		$state['accounts']['345678901234'] = $state['accounts'][ $made['id'] ];
		unset( $state['accounts'][ $made['id'] ] );
		return $state;
	}, [] );
	$forgetLink = (string) $members::link( $appData, (array) $members::account( $appData, '345678901234' ), 'login', '', true, false );
	$forgot = $members::forgetAll( $appData, '345678901234' );
	check( '...so does forgetAll() for such an id: the links and codes of the account end with its sessions', $forgetLink !== '' && $forgot === true && in_array( '345678901234', $tokens(), true ) === false );
	$members::removeGroups( $appData, static fn( string $group ): bool => $group === 'rg-digits' );
}

/**
 *	The language of an account, where a module knows one: mailLocale() asks the callback of '/members/language' with the id of the account and takes a locale of the site; a mail to the account - the link the owner sends,
 *	the code the visitor asks for - is written in it, ahead of the site's native language and of the request's; a locale that is none of the site's, a text that is none and no answer are no language
 *
 *	@param		array 		&$appData
 *	@param		callable	$panel				The panel handler of the suite
 *
 *	@return 	void
 */
function membersLanguageChecks( array &$appData, callable $panel ): void {

	global $mails;

	echo "\nThe language of an account\n";

	$named = '';
	$asked = [];
	$target = 'none';
	\Nino\Callbacks::registerCallback( $appData, '/members/language', static function( array &$appData, array &$ask ) use ( &$named, &$asked, &$target ): void {
		$asked[] = $ask['account'] ?? null;
		$ask['locale'] = ( $ask['account'] ?? null ) === $target ? $named : '';
	} );
	membersFresh( $appData );
	ninoCall( $appData, $panel, [ 'email' => 'lang@example.org', 'invite' => false ], 'members/add' );
	$account = \Nino\Modules\Members::account( $appData, membersIdOf( $appData, 'lang@example.org' ) );
	$target = $account['id'];
	$native = \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/members/mail/send-subject]]'];
	$other = \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/members/mail/send-subject]]'];
	$send = static function( string $locale ) use ( &$appData, &$named, &$mails, $account ): string {
		$named = $locale;
		membersFresh( $appData );
		$mails = [];
		\Nino\Modules\Members::link( $appData, $account, 'send', '', true );

		return (string) ( $mails[0]['subject'] ?? '' );
	};

	check( 'mailLocale() answers the locale the callback names, for the account it was asked for - and nothing where it names none, a locale the site does not have, a path or markup', \Nino\Modules\Members::mailLocale( $appData, $account['id'] ) === '' && ( $named = 'en_US' ) === 'en_US' && \Nino\Modules\Members::mailLocale( $appData, $account['id'] ) === 'en_US'
		&& ( $named = 'fr_FR' ) === 'fr_FR' && \Nino\Modules\Members::mailLocale( $appData, $account['id'] ) === '' && ( $named = '../de_DE' ) !== '' && \Nino\Modules\Members::mailLocale( $appData, $account['id'] ) === '' && ( $named = '<b>' ) !== '' && \Nino\Modules\Members::mailLocale( $appData, $account['id'] ) === ''
		&& array_unique( $asked ) === [ $account['id'] ] );
	$none = $send( '' );
	$english = $send( 'en_US' );
	$german = $send( 'de_DE' );
	$foreign = $send( 'fr_FR' );
	check( 'the link the owner sends is written in the language of the account where the callback names one, whatever the native language of the site is, and in the native language where it names none or one the site does not have', $native !== $other && $none === $native && $english === $other && $german === $native && $foreign === $native );

	// The code a visitor asks for: the account's language, ahead of the one the visitor's session has
	$named = 'en_US';
	$appData['./nino/locales/current'] = 'de_DE';
	membersAskCode( $appData, 'lang@example.org' );
	$codeEnglish = (string) ( $mails[count( $mails ) - 1]['subject'] ?? '' );
	$named = '';
	membersAskCode( $appData, 'lang@example.org' );
	$codeNone = (string) ( $mails[count( $mails ) - 1]['subject'] ?? '' );
	$appData['./nino/locales/current'] = 'en_US';
	membersAskCode( $appData, 'lang@example.org' );
	$codeSession = (string) ( $mails[count( $mails ) - 1]['subject'] ?? '' );
	$appData['./nino/locales/current'] = 'de_DE';
	check( 'the code is written in the language of the account too, though the visitor looked at the page in the other; an account with no language gets the language of the request, as it did', $codeEnglish === \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/members/mail/code-subject]]'] && $codeNone === \Nino\Html::getFills( $appData, 'de_DE' )['[[/feature/members/mail/code-subject]]']
		&& $codeSession === $codeEnglish && $codeNone !== $codeEnglish && $appData['./nino/locales/current'] === 'de_DE' );

	// The code to the new address of an account that asks to change it is written in the language of the account
	$named = 'en_US';
	membersFresh( $appData );
	membersSignIn( $appData, $account );
	$mails = [];
	$appData['./nino/locales/current'] = 'de_DE';
	$changeAsked = membersAfter( $appData, \Nino\Modules\Members::askChange( $appData, \Nino\Modules\Members::current( $appData ) ?? $account, 'lang.next@example.org' ) );
	check( '...and so is the code to the new address that an account asks for', $changeAsked === 'sent' && ( $mails[0]['to'] ?? '' ) === 'lang.next@example.org' && ( $mails[0]['subject'] ?? '' ) === \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/members/mail/change-subject]]'] );
	membersVisitor();

	// The notice to the old address of an account whose address was changed is written in the language of the account
	$named = 'en_US';
	membersFresh( $appData );
	$mails = [];
	\Nino\Modules\Members::change( $appData, $account['id'], [ 'email' => 'lang-new@example.org' ] );
	check( '...and so is the notice to the old address that it was changed', ( $mails[0]['to'] ?? '' ) === 'lang@example.org' && ( $mails[0]['subject'] ?? '' ) === \Nino\Html::getFills( $appData, 'en_US' )['[[/feature/members/mail/changed-subject]]'] );
	$named = '';
	ninoCall( $appData, $panel, [ 'id' => $account['id'] ], 'members/delete' );
	unset( $appData['./nino/callbacks']['/members/language'] );
}

/**
 *	The page the gate kept for a visitor is used up by the sign-in: the redirect back to the protected page after a code was asked for keeps it again, and a sign-in that comes later in the same session - from another page, or the
 *	front page - leads to the start page and not to the page of the first one. A visitor who asked for a protected page and has not signed in yet is still led to it
 *
 *	@param		array 		&$appData
 *	@param		array 		$ada					An active account
 *
 *	@return 	void
 */
function membersSpentTargetChecks( array &$appData, array $ada ): void {

	echo "\nThe page the gate kept, used up by the sign-in\n";

	$paths = \Nino\Features::setting( $appData, 'members', 'paths', [] );
	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ] ] );
	membersVisitor();
	membersServe( $appData, '/club/news?issue=9' );
	$asked = membersAskCode( $appData, $ada['email'], '/club/news' );
	$again = membersAnswer( membersServe( $appData, '/club/news?issue=9' ) )[0];
	$kept = \Nino\Runtime::getSessionValue( $appData, './members/target' );
	$first = membersCodeServe( $appData, $asked, '/club/news' );
	$afterFirst = \Nino\Runtime::getSessionValue( $appData, './members/target' );
	membersServe( $appData, '/.members/logout', 'POST' );
	$second = membersCodeServe( $appData, membersAskCode( $appData, $ada['email'], '/' ), '/' );

	check( 'a visitor who asked for a protected page is led there by the code, though the redirect back to the page kept it a second time - and nothing of it is left in the session', $asked !== '' && $again === 401 && $kept === '/club/news?issue=9' && $first[1] === '/club/news?issue=9' && $afterFirst === null );
	check( '...the next sign-in in that session, asked for from the front page, leads to the start page and not to the page of the first one', $second[0] === 303 && $second[1] === '/' );

	// the page the sign-out leads to is a protected page of the visitor who has just signed out: the page, and not the state the feature put on it
	membersVisitor();
	membersServe( $appData, '/club/news?members=out' );
	$plainState = \Nino\Runtime::getSessionValue( $appData, './members/target' );
	membersServe( $appData, '/club/news?issue=9&members=sent&x=1' );
	$mixedState = \Nino\Runtime::getSessionValue( $appData, './members/target' );
	membersServe( $appData, '/club/news?membership=1' );
	$otherName = \Nino\Runtime::getSessionValue( $appData, './members/target' );
	check( '...the state the feature adds to a page - ?members=out after a sign-out - is no part of the page the gate keeps: the page alone, the rest of a query as it came, and a query of another name is kept', $plainState === '/club/news' && $mixedState === '/club/news?issue=9&x=1' && $otherName === '/club/news?membership=1' );

	membersVisitor();
	membersServe( $appData, '/club/news?issue=9' );
	$link = membersSignIn( $appData, $ada, '/club/other' );
	check( '...a sign-in by a link that names a page leads there and uses up the page the gate kept as well', $link[1] === '/club/other' && \Nino\Runtime::getSessionValue( $appData, './members/target' ) === null );

	// a page of the gate that was asked for and where nobody signed in: only a code asked for from that page leads back to it
	membersVisitor();
	membersServe( $appData, '/club/news?issue=9' );
	$fromDoor = membersCodeServe( $appData, membersAskCode( $appData, $ada['email'], '/' ), '/' );
	membersVisitor();
	membersServe( $appData, '/club/news?issue=9' );
	$fromPage = membersCodeServe( $appData, membersAskCode( $appData, $ada['email'], '/club/news' ), '/club/news' );
	check( 'a visitor who asked for the protected page A and signed in nowhere there, and asks for the code at the front page, is led to the start page - and from page A back to A', $fromDoor[1] === '/' && $fromPage[1] === '/club/news?issue=9' );

	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => $paths ] );
}

/**
 *	The message of the owner in the mail of an invitation and of a link of the owner: a paragraph before the words that lead to the link, written as text - escaped, with its line breaks as breaks, and nothing in it read
 *	as a fill, a shortcode or a slot -, in both kinds of mail; without a message (none, empty, only blanks) the mail is the one the template has without its slot, and the subject is the same in all; the mail of a code to sign in
 *	has no slot; where nobody is mailed the message is of no use
 *
 *	@param		array 		&$appData
 *	@param		array 		$ada					An active account
 *
 *	@return 	void
 */
function membersMessageChecks( array &$appData, array $ada ): void {

	global $mails;

	echo "\nThe message of the owner in the mail of a link\n";

	// The mail a link makes, with the token of the link masked: the token is the only thing that differs from one mail to the next
	$write = static function( string $kind, ?string $message ) use ( &$appData, $ada, &$mails ): array {

		membersFresh( $appData );
		$count = count( $mails );
		$sent = $message === null ? \Nino\Modules\Members::link( $appData, $ada, $kind, '', true ) : \Nino\Modules\Members::link( $appData, $ada, $kind, '', true, true, $message );
		$mail = $mails[$count] ?? [];

		return [ $sent === true && count( $mails ) === $count + 1, preg_replace( '~t=[A-Za-z0-9_-]+~', 't=TOKEN', (string) ( $mail['body'] ?? '' ) ), (string) ( $mail['subject'] ?? '' ) ];
	};
	$typed = "Hallo Ada,\r\nbitte <b>klicken</b> & {count} [[/feature/members/login/label]] [[link]] [[message]] [[message-text]] [mail-head]\u{2028}Zeile \u{202E}drei\x07\0\n\nEnde \"quote\" 'x'";

	foreach( [ 'invite', 'send' ] as $kind ) {

		[ $okNone, $none, $subjectNone ] = $write( $kind, null );
		[ $okEmpty, $empty, $subjectEmpty ] = $write( $kind, '' );
		[ $okBlank, $blank, ] = $write( $kind, " \n\t \r\n " );
		[ $okWith, $with, $subjectWith ] = $write( $kind, $typed );
		$paragraph = preg_match( '~\t<p style="[^"]*border-left[^"]*">.*?</p>\n~s', $with, $found ) === 1 ? $found[0] : '';
		$link = (int) strpos( $with, 'href="https://example.org/club/.members/login?t=TOKEN"' );

		check( 'a link of the kind '. $kind. ' without a message, with an empty one and with one of blanks is one and the same mail, without a trace of the slot or of the paragraph', $okNone && $okEmpty && $okBlank && $none === $empty && $none === $blank
			&& str_contains( $none, '[[message' ) === false && str_contains( $none, 'border-left' ) === false && $subjectNone === $subjectEmpty );
		check( '...with a message the mail is that mail with one paragraph more, which follows the heading at once, before the words and the link, and the subject is the same', $okWith && $paragraph !== '' && str_replace( $paragraph, '', $with ) === $none
			&& strpos( $with, $paragraph ) === (int) strpos( $with, '</h1>' ) + 6 && strpos( $with, $paragraph ) + strlen( $paragraph ) <= $link && $link > 0 && $subjectWith === $subjectNone && substr_count( $with, 'href="https://example.org/club/.members/login?t=TOKEN"' ) === 1 );
		check( '...the message is text: no tag of it opens, a fill, a shortcode, the slots and the link in it are written out as letters and none is put in, a quote is a quote', str_contains( $paragraph, '<b>' ) === false && str_contains( $paragraph, '&lt;b&gt;klicken&lt;/b&gt;' ) === true && str_contains( $paragraph, '&amp; {count}' ) === true
			&& str_contains( $paragraph, '[[/feature/members/login/label]]' ) === true && str_contains( $paragraph, '[[link]]' ) === true && str_contains( $paragraph, '[[message]]' ) === true && str_contains( $paragraph, '[[message-text]]' ) === true
			&& str_contains( $paragraph, '[mail-head]' ) === true && str_contains( $paragraph, '&quot;quote&quot; &#039;x&#039;' ) === true && substr_count( $with, 'mail-head' ) === 1 && substr_count( $with, 'TOKEN' ) === 1 );
		check( '...its line breaks are breaks - those of a Windows text and the separator of a line as well, a blank line is two -, and a control character, the null byte and a mark that turns the text around are left out', str_contains( $paragraph, "Hallo Ada,<br>\nbitte " ) === true && str_contains( $paragraph, "<br>\nZeile drei<br>\n<br>\nEnde" ) === true
			&& substr_count( $paragraph, '<br>' ) === 4 && preg_match( '~[\x00-\x08\x0B-\x1F]|\x{202E}|\x{2028}~u', $paragraph ) === 0 );
	}

	// A fill that spells out the slots is no slot: the site's name is in the heading, and the paragraph is there once
	$name = (string) \Nino\Html::resolveTextfill( $appData, '/project/company/general/name' );
	\Nino\Html::addFills( $appData, [ '[[/project/company/general/name]]' => 'ACME [[message]] [[message-text]]' ], '*' );
	$spelled = [];

	foreach( [ 'invite', 'send' ] as $kind ) {
		[ , $spelled[$kind], ] = $write( $kind, 'Einmal' );
	}

	\Nino\Html::addFills( $appData, [ '[[/project/company/general/name]]' => $name ], '*' );
	check( 'a fill that holds the slot of the message, as the name of the site does in the heading, is no slot: the paragraph is in the mail once and its text once, in both kinds of mail', array_filter( $spelled, static fn( string $mail ): bool => substr_count( $mail, 'border-left' ) === 1 && substr_count( $mail, 'Einmal' ) === 1 && str_contains( $mail, 'ACME ' ) === true ) === $spelled && count( $spelled ) === 2 );

	// A mail that has no slot has no paragraph; a link nobody is mailed has no mail to put it in
	[ $okLogin, $login, ] = $write( 'login', null );
	[ $okLoginWith, $loginWith, ] = $write( 'login', 'Hallo' );
	membersFresh( $appData );
	$count = count( $mails );
	$shown = \Nino\Modules\Members::link( $appData, $ada, 'invite', '', true, false, 'Hallo' );
	check( 'the mail of a link to sign in has no slot for the message, and a link that is shown and not mailed makes no mail of it - the answer is the link', $okLogin && $okLoginWith && $login === $loginWith && str_contains( $loginWith, 'Hallo' ) === false && is_string( $shown ) === true && str_contains( $shown, '/.members/login?t=' ) === true && count( $mails ) === $count );
}

/**
 *	The gate is the gate's own, and holds for every spelling of an address: the path asked for in another case, with runs of slashes, a dot segment, an escaped
 *	letter or slash, twice escaped, a trailing dot - a visitor is answered with the login page and never with the page, and nothing the route brought is in it: no file, no
 *	header, no callback bound to its uri
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersGateChecks( array &$appData ): void {

	echo "\nThe gate - its own answer, in every spelling\n";

	$members = \Nino\Modules\Members::class;
	$login = static fn( array $request ): bool => $request['/nino/http/response']['statusCode'] === 401 && $request['/nino/http/response']['body'] === '[template /templates/page-members-login]';

	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club', '/vault/Room' ] ] );
	$appData['/nino/http/routes']['GET://vault/room'] = [ 'uri' => '/vault/room', 'body' => 'SECRET-VAULT' ];
	$appData['/nino/http/routes']['GET://files'] = [ 'uri' => '/club/files', 'file' => '/data/members.php', 'body' => 'SECRET-FILE',
		'header' => [ 'Content-Type' => 'application/octet-stream', 'Content-Disposition' => 'attachment; filename=members.php', 'Link' => '</club/news>; rel=preload', 'Refresh' => '0;url=/club/news', 'Access-Control-Allow-Origin' => '*' ] ];
	\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://club/files', static function( array &$appData, array &$request ): void { $request['/nino/http/response']['body'] = 'SECRET-BY-CALLBACK'; } );
	membersVisitor();

	$spelled = [];

	foreach( [ '/club', '/Club', '/CLUB/news', '//club', '/club//news', '/club/', '/club/.', '/club/./news', '/club.', '/club./news', '/x/../club', '/x/../club/news', '/club/x/../news', '/club%2Fnews', '/club%252Fnews', '/%63lub', '/%43lub/news',
		'/club\\news', '/vault/room', '/vault/Room', '/VAULT/ROOM', '/vault//room', '/vault/%72oom', '/Vault/../vault/room' ] as $each ) {

		$answer = membersServe( $appData, $each );
		$body = (string) $answer['/nino/http/response']['body'];

		if( $login( $answer ) === false && str_contains( $body, 'SECRET' ) === true || $members::protects( $appData, $each ) === false )
			$spelled[] = $each;
	}

	check( 'the gate holds for every spelling of an address - case, runs of slashes, a trailing slash or dot, a dot segment, an escaped slash or letter, twice escaped, a backslash - and the prefix of the setting is read as it is written, in whatever case'. ( $spelled === [] ? '' : ' - '. implode( ' ', $spelled ) ), $spelled === [] );

	$neighbours = [];

	foreach( [ '/clubhouse', '/club2', '/clu', '/vault', '/vault/roomy', '/news', '/' ] as $each )
		if( $members::protects( $appData, $each ) === true )
			$neighbours[] = $each;

	check( '...and what only looks like a prefix is none: a neighbour with the same letters, the parent of the prefix, the front page'. ( $neighbours === [] ? '' : ' - '. implode( ' ', $neighbours ) ), $neighbours === [] );
	check( '...a prefix that folds to the front page, to the tools or to an endpoint of a module is no prefix, whatever way it is written', $members::cleanPrefix( '/Club' ) === '/Club' && array_filter( [ '', '/', '//', '/./', '/_admin', '//_Admin/x', '/.members', '/%2e/x', '/a/../b', '/a\\b', '/a%2Fb', "/a\x00b", 'club' ], static fn( string $line ): bool => $members::cleanPrefix( $line ) !== null ) === [] );

	$answer = membersServe( $appData, '/files' );
	$headers = array_change_key_case( (array) $answer['/nino/http/response']['header'] );
	check( 'the answer to a visitor is the gate\'s own: 401, never cached, under the address of the feature, with no file, none of the route\'s headers, no callback of its uri and none of its body',
		$login( $answer ) === true && isset( $answer['/nino/http/response']['file'] ) === false && $answer['/nino/http/response']['uri'] === '/.members' && ( $headers['cache-control'] ?? '' ) === 'no-store'
		&& array_intersect_key( $headers, array_flip( [ 'content-disposition', 'link', 'refresh', 'access-control-allow-origin' ] ) ) === [] && ( $headers['content-type'] ?? '' ) !== 'application/octet-stream'
		&& str_contains( json_encode( $answer['/nino/http/response'] ), 'SECRET' ) === false );

	$policy = [ 'Content-Security-Policy' => "default-src 'self'; img-src 'self' https://cdn.example.org" ];
	$appData['/nino/http/routes']['GET://policy'] = [ 'uri' => '/club/policy', 'body' => 'SECRET-POLICY', 'header' => $policy ];
	check( '...but for the policy of the response, which stays', ( array_change_key_case( (array) membersServe( $appData, '/policy' )['/nino/http/response']['header'] )['content-security-policy'] ?? '' ) !== '' );

	$head = membersServe( $appData, '/club/news', 'HEAD' );
	$post = membersServe( $appData, '/club/news', 'POST', [ 'x' => '1' ] );
	check( 'a HEAD is gated as the GET it stands for, and so is a POST to a protected path: the answer is the login page and the page does not run', $login( $head ) === true && $login( $post ) === true );

	// Members who pass: the page is the page, in the one spelling the server routes
	$ada = $members::add( $appData, 'gate-probe@example.org', 'Gate Probe', 'g' );
	membersSignIn( $appData, $ada );
	check( 'a member passes the gate with the page as it is - and nobody else in the same spelling does', membersServe( $appData, '/club/news' )['/nino/http/response']['body'] === 'FOR MEMBERS'
		&& membersServe( $appData, '/vault/room' )['/nino/http/response']['body'] === 'SECRET-VAULT' );
	membersVisitor();

	// A feature opens what a browser fetches without a session, folded as the paths are
	\Nino\Callbacks::registerCallback( $appData, '/members/open', static function( array &$appData, array &$list ): void { $list[] = '/club/Manifest.webmanifest'; $list[] = '/club/../_admin'; $list[] = '//evil.example.org/x'; $list[] = '/.members/login'; } );
	check( 'a path a feature opens under a prefix is open in every spelling of it - and an endpoint, a tool and an address of another host that a callback offers are dropped',
		$members::open( $appData ) === [ '/club/Manifest.webmanifest' ] && $members::protects( $appData, '/club/manifest.webmanifest' ) === false && $members::protects( $appData, '/CLUB/MANIFEST.WEBMANIFEST' ) === false && $members::protects( $appData, '/club/other' ) === true );
	\Nino\Callbacks::removeCallbacks( $appData, '/members/open' );

	// The cache: a page of a member is out of it for the request, a protected prefix is on its blacklist from the boot
	$booted = $appData;
	$booted['/nino/cache/blacklist'] = [];
	$members::init( $booted );
	check( 'the cache is told once, where the key lives: the two prefixes with their subtrees, and not twice when the feature boots again', $booted['/nino/cache/blacklist'] === [ '/club/*', '/vault/Room/*' ] && ( static function() use ( $members, $booted ): bool { $members::init( $booted ); return $booted['/nino/cache/blacklist'] === [ '/club/*', '/vault/Room/*' ]; } )() );

	// What the 405 page says is the site's own path, drawn as text
	\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/start[[/project/company/general/name]]' ] );
	$answer = membersServe( $appData, '/.members/logout' );
	check( 'the page of a GET to the sign-out is a whole page from its template, 405 with the header Allow, and a start page with brackets in it is written as text: nothing of it is drawn as a fill',
		$answer['/nino/http/response']['statusCode'] === 405 && str_contains( (string) $answer['/nino/http/response']['body'], '<!doctype html>' ) === true && str_contains( (string) $answer['/nino/http/response']['body'], '[[' ) === false
		&& str_contains( (string) $answer['/nino/http/response']['body'], '&#91;&#91;/project/company/general/name&#93;&#93;' ) === true );
	\Nino\Features::saveSettings( $appData, 'members', [ 'start' => '/', 'paths' => [] ] );
}

/**
 *	What is told of an account that is gone, to the features that keep something of a member: '/members/removed' - the id, the address and the group - once for
 *	each, after the write, from remove(), from removeGroups() and from the delete of the panel, and from no way that does not delete
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersRemovedChecks( array &$appData ): void {

	global $removed, $panel;

	echo "\nThe callback /members/removed\n";

	$members = \Nino\Modules\Members::class;
	$removed = [];
	$one = $members::add( $appData, 'Gone-One@example.org', 'Eins', 'Crew' );
	$two = $members::add( $appData, 'gone-two@example.org', 'Zwei', 'crew' );
	$three = $members::add( $appData, 'gone-three@example.org', 'Drei', '' );
	$seen = [];
	\Nino\Callbacks::registerCallback( $appData, '/members/removed', static function( array &$appData, array &$account ) use ( &$seen, $members, $one ): void {
		$seen[] = [ $account, $members::account( $appData, $one['id'] ) === null ];
	} );

	check( 'nothing is told while nothing is deleted: an add, a change, a link and an unknown id leave the callback alone', $removed === [] && $members::remove( $appData, 'ffffffffffff' ) === 'unknown' && $members::remove( $appData, 'no' ) === 'unknown'
		&& is_array( $members::change( $appData, $two['id'], [ 'name' => 'Zwei 2' ] ) ) && $removed === [] && $seen === [] );

	check( 'remove() tells the id, the address as it is kept and the group as a slug - the three and nothing else - once, and after the write: the account is gone when the callback runs',
		$members::remove( $appData, $one['id'] ) === 'ok' && $removed === [ [ 'id' => $one['id'], 'email' => 'gone-one@example.org', 'group' => 'crew' ] ] && array_keys( $removed[0] ) === [ 'id', 'email', 'group' ] && count( $seen ) === 1 && $seen[0][1] === true );
	check( '...the same account again is unknown and tells nobody', $members::remove( $appData, $one['id'] ) === 'unknown' && count( $removed ) === 1 );

	$removed = [];
	$gone = $members::removeGroups( $appData, static fn( string $group ): bool => $group === 'crew' );
	check( 'removeGroups() tells each account that went, in the order it was deleted, with the group it had - and none that stayed', $gone === [ $two['id'] ] && $removed === [ [ 'id' => $two['id'], 'email' => 'gone-two@example.org', 'group' => 'crew' ] ] && $members::account( $appData, $three['id'] ) !== null );
	$removed = [];
	check( '...a pick that matches nothing, and a write that fails, tell nobody', $members::removeGroups( $appData, static fn( string $group ): bool => $group === 'nobody' ) === [] && $removed === [] );

	// The delete of the panel: the right and the token of the session first, then the callback
	ninoUser( $appData, 'removed-admin@example.org' );
	$before = $members::accounts( $appData );
	[ $status ] = ninoCall( $appData, $panel, [ 'id' => $three['id'] ], 'members/delete', 403 );
	check( 'the delete of the panel without the token of the session is refused, the account stays and nobody is told', $status === 403 && $members::accounts( $appData ) === $before && $removed === [] );

	ninoUser( $appData, 'removed-plain@example.org', [] );
	[ $status ] = ninoCall( $appData, $panel, [ 'id' => $three['id'] ], 'members/delete' );
	check( '...so is one without the right, and one that names a thing that is no id', $status === 403 && $removed === [] && $members::account( $appData, $three['id'] ) !== null );
	\Nino\Auth::loginUser( $appData, 'removed-admin@example.org', 'correct horse battery staple' );
	check( '...and a path, a list or an id with a null byte is an id nobody has', array_filter( [ '../../x', [ 'a' ], "0123456789ab\0", '0123456789AB', '' ], static fn( mixed $id ): bool => ninoCall( $appData, $panel, [ 'id' => $id ], 'members/delete' )[0] !== 404 ) === [] && $removed === [] );

	[ $status ] = ninoCall( $appData, $panel, [ 'id' => $three['id'] ], 'members/delete' );
	check( 'the delete of the panel tells the callback: one account, with its id, address and group - the group empty as the account had none', $status === 200 && $removed === [ [ 'id' => $three['id'], 'email' => 'gone-three@example.org', 'group' => '' ] ] );
	\Nino\Callbacks::removeCallbacks( $appData, '/members/removed' );
	\Nino\Callbacks::registerCallback( $appData, '/members/removed', static function( array &$appData, array &$account ) use ( &$removed ): void { $removed[] = $account; } );
}

/**
 *	The properties of the security that the plan names, each by a probe of its own: the tokens kept as hashes and nothing else, the request that answers all
 *	addresses alike, a header that cannot be injected, the writes that need the token of the session - the panel's among them
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersProofChecks( array &$appData ): void {

	global $mails, $panel;

	echo "\nThe proofs - what is kept, what is told and what is refused\n";

	$members = \Nino\Modules\Members::class;
	$ada = $members::add( $appData, 'proof@example.org', 'Proof', 'p' );
	membersFresh( $appData );
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', [] );
	$token = membersLink( $appData, $ada, 'login' );
	membersVisitor();
	$code = membersAskCode( $appData, 'proof@example.org' );
	$nonce = (string) ( membersPending( $appData )['nonce'] ?? '' );
	$asking = $_SESSION;
	$raw = (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members-tokens.php' ) );
	$stored = membersFile( $appData, 'tokens' );

	check( 'a link and a code are kept as hashes and nothing else: every key of the file is a sha256 - of the token, of the nonce and the digits -, and neither the token, nor the digits, nor the nonce is written anywhere in it',
		count( $stored ) === 2 && array_filter( array_keys( $stored ), static fn( string $key ): bool => preg_match( '/^[0-9a-f]{64}$/D', $key ) !== 1 ) === [] && isset( $stored[ hash( 'sha256', $token ) ], $stored[ hash( 'sha256', $nonce. '|'. $code ) ] )
		&& $token !== '' && $code !== '' && str_contains( $raw, $token ) === false && str_contains( $raw, $code ) === false && str_contains( $raw, $nonce ) === false && array_filter( $stored, static fn( array $entry ): bool => array_diff( array_keys( $entry ), [ 'account', 'kind', 'expires', 'target' ] ) !== [] ) === [] );
	check( '...the account file holds no token either, and the file of the counter holds neither an address nor a digit of the visitor\'s: the keys are hashes', str_contains( (string) file_get_contents( \Nino\Filesystem::path( $appData, '/data/members.php' ) ), $token ) === false
		&& array_filter( array_keys( membersFile( $appData, 'tries' ) ), static fn( string $key ): bool => preg_match( '/^[0-9a-f]{64}$/D', $key ) !== 1 ) === [] );

	// Once and in time: the link is spent by its button, and one that has run out is none - checked by the proofs above, here by the file
	membersVisitor();
	$spent = membersConfirm( $appData, $token );
	check( 'a link works once: the button spends it and the file has no entry of it, and the second press is the page of a link that does not work - 400', $spent[0] === 303 && isset( membersFile( $appData, 'tokens' )[ hash( 'sha256', $token ) ] ) === false && membersConfirm( $appData, $token )[0] === 400 );
	$entries = membersFile( $appData, 'tokens' );
	$key = (string) array_key_first( $entries );
	$entries[$key]['expires'] = time() - 1;
	\Nino\Filesystem::putFileContent( $appData, '/data/members-tokens.php', $entries );
	$_SESSION = $asking;
	$late = membersCodeServe( $appData, $code );
	check( 'a code whose record has run out signs nobody in, even with the right digits: it is a wrong one', $members::current( $appData ) === null && $late[1] === '/join?members=wrong' );

	// The same answer for every address, and for every kind of the address: the status, the place, the headers, the body and the kinds of record the session keeps
	$answers = [];

	foreach( [ 'a member' => 'proof@example.org', 'nobody' => 'nobody-at-all@example.org', 'a member in capitals' => 'PROOF@EXAMPLE.ORG', 'a switched-off one' => 'off@example.org' ] as $kind => $address ) {
		if( $kind === 'a switched-off one' )
			$members::change( $appData, membersIdOf( $appData, $address ) !== '' ? membersIdOf( $appData, $address ) : (string) $members::add( $appData, $address, 'Off', 'p' )['id'], [ 'active' => false ] );

		membersVisitor();
		membersFresh( $appData );
		$answer = membersServe( $appData, '/.members/login', 'POST', [ 'email' => $address, 'return' => '/join' ] );
		$response = $answer['/nino/http/response'];
		ksort( $response['header'] );
		$answers[$kind] = [ $response['statusCode'], $response['header'], $response['body'], array_keys( (array) ( membersPending( $appData ) ?? [] ) ) ];
	}

	check( 'a request for a code is answered the same for a member, for an address nobody has, for another case of a member\'s and for one that is switched off: the status, every header, the body and what the session keeps', count( array_unique( array_map( 'json_encode', $answers ) ) ) === 1 );

	// The time the answer takes belongs to the same class: the mail is sent after the answer, so that a transport that costs 150 ms is not seen by the visitor. A member, an address
	// nobody has, another case of a member's, a switched-off account: the median of the answers of eight requests each, with that transport
	$GLOBALS['membersDelay'] = 150000;
	$timing = [];
	$delivered = [];

	foreach( [ 'member' => 'proof@example.org', 'nobody' => 'nobody-at-all@example.org', 'capitals' => 'PROOF@EXAMPLE.ORG', 'switched off' => 'off@example.org' ] as $kind => $address ) {
		$timing[$kind] = [];
		$count = count( $mails );

		for( $round = 0; $round < 8; $round++ ) {
			membersVisitor();
			membersFresh( $appData );
			membersServe( $appData, '/.members/login', 'POST', [ 'email' => $address, 'return' => '/join' ] );
			$timing[$kind][] = $GLOBALS['membersTimes'][0];
		}

		$delivered[$kind] = count( $mails ) - $count;
		sort( $timing[$kind] );
	}

	$median = static fn( array $times ): float => $times[(int) ( count( $times ) / 2 )];
	check( 'with a transport that costs 150 ms the four ways take the same class of time to answer: the medians of eight requests for a member, for nobody, for a member in capitals and for one that is switched off are within 50 ms of each other and under the cost of one mail',
		max( array_map( $median, $timing ) ) - min( array_map( $median, $timing ) ) < 50 && max( array_map( $median, $timing ) ) < 100 );
	check( '...and the mail comes: eight for the member and eight for the capitals, none for nobody and none for the switched-off account', $delivered['nobody'] === 0 && $delivered['switched off'] === 0 && $delivered['member'] === 8 && $delivered['capitals'] === 8 );

	// The change of an address: the member who asks for a free address and the one who asks for an address that is another account's
	$asker = $members::add( $appData, 'asker@example.org', 'Asker', 'p' );
	$other = $members::add( $appData, 'taken-by-other@example.org', 'Other', 'p' );
	membersSignIn( $appData, $asker );
	$asks = [];

	foreach( [ 'free' => 'free-new@example.org', 'taken' => 'taken-by-other@example.org' ] as $kind => $address ) {
		$asks[$kind] = [];
		$count = count( $mails );

		for( $round = 0; $round < 5; $round++ ) {
			membersFresh( $appData );
			$started = hrtime( true );
			$members::askChange( $appData, (array) $members::current( $appData ), $address );
			$asks[$kind][] = ( hrtime( true ) - $started ) / 1e6;
			membersAfter( $appData, '' );
		}

		$delivered[$kind] = count( $mails ) - $count;
		sort( $asks[$kind] );
	}

	check( 'askChange() answers a free address and one that another account has in the same class of time, with the same transport - and only the free address is mailed',
		abs( $median( $asks['free'] ) - $median( $asks['taken'] ) ) < 50 && $median( $asks['free'] ) < 100 && $delivered['free'] === 5 && $delivered['taken'] === 0 );
	$GLOBALS['membersDelay'] = 0;
	membersVisitor();

	// The wrong cookies of a device are counted by the /64 of an IPv6 address too, as the requests for a code are: two addresses of one network are one counter
	membersFresh( $appData );
	foreach( [ '2001:db8:7:7::1', '2001:db8:7:7::2' ] as $ip ) {
		$_SERVER['REMOTE_ADDR'] = $ip;
		membersVisitor();
		membersVisitWith( $appData, '0123456789ab.'. str_repeat( 'A', 43 ), '/club/news' );
	}
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	check( 'the wrong cookies of a device are counted by the /64 of an IPv6 address: one bad cookie from each of two addresses of one network are two tries of one counter', \Nino\Feature::spent( $appData, 'members-tries', 'remember|'. \Nino\Http::clientBucket( '2001:db8:7:7::9' ), 2 ) === true && \Nino\Feature::spent( $appData, 'members-tries', 'remember|'. \Nino\Http::clientBucket( '2001:db8:7:7::9' ), 3 ) === false );
	membersFresh( $appData );

	// An address that ends the line: nothing is mailed to the second line
	$count = count( $mails );
	membersVisitor();
	membersFresh( $appData );
	$injected = membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => "proof@example.org\r\nBcc: evil@example.org", 'return' => "/join\r\nSet-Cookie: x=1" ] ) );
	check( 'an address with a line break and a second header in it is no address: no mail, no request in the session, and the answer is the same redirect - a return with a line break is the start page', count( $mails ) === $count && membersPending( $appData ) === null
		&& $injected[0] === 303 && $injected[1] === '/?members=sent' && str_contains( $injected[1], "\n" ) === false );

	$count = count( $mails );
	membersFresh( $appData );
	membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'proof@example.org', 'return' => '/join' ] );
	check( '...and the address of a mail is the one the account has, never a posted one: a member who signs in by a case of it is mailed at the address of the account', count( $mails ) === $count + 1 && $mails[$count]['to'] === 'proof@example.org' );

	// Every write needs the token of the session: the four routes of the visitor, and the six actions of the panel
	membersVisitor();
	$kept = $mails;
	$blocked = [
		'login'		=> membersAnswer( membersServe( $appData, '/.members/login', 'POST', [ 'email' => 'proof@example.org', 'return' => '/join' ], false ) ),
		'code'		=> membersAnswer( membersServe( $appData, '/.members/code', 'POST', [ 'code' => '123456', 'return' => '/join' ], false ) ),
		'confirm'	=> membersAnswer( membersServe( $appData, '/.members/confirm', 'POST', [ 'token' => str_repeat( 'A', 43 ) ], false ) ),
		'logout'	=> membersAnswer( membersServe( $appData, '/.members/logout', 'POST', [], false ) ),
	];
	check( 'no write of the visitor goes through without the token of the session: the form that asks for a code and the form that takes it are told the session ran out, the link\'s button is refused, the sign-out is the 403 - and nothing is mailed',
		$blocked['login'][1] === '/join?members=expired' && $blocked['code'][1] === '/join?members=expired' && $blocked['confirm'][0] === 400 && $blocked['logout'][0] === 403 && $mails === $kept );

	$before = [ membersFile( $appData, 'accounts' ), membersFile( $appData, 'tokens' ), membersFile( $appData, 'session' ) ];
	ninoUser( $appData, 'proof-admin@example.org' );
	$refused = [];

	foreach( [ 'members/list' => [], 'members/add' => [ 'email' => 'csrf@example.org', 'invite' => true ], 'members/save' => [ 'id' => $ada['id'], 'name' => 'csrf' ], 'members/delete' => [ 'id' => $ada['id'] ],
		'members/link' => [ 'id' => $ada['id'] ], 'members/signout' => [] ] as $action => $post ) {
		[ $status, $body ] = ninoCall( $appData, $panel, $post, $action, 403 );
		$refused[$action] = [ $status, $body['code'] ?? '' ];
	}

	check( 'every action of the panel refuses a post the guard of the session refused - 403, whoever asks - and does not run: no account, no link, no epoch, no mail', array_values( array_unique( array_map( 'json_encode', $refused ) ) ) === [ '[403,""]' ] && count( $refused ) === 6
		&& $before === [ membersFile( $appData, 'accounts' ), membersFile( $appData, 'tokens' ), membersFile( $appData, 'session' ) ] && $mails === $kept );

	// The group is a slug and the name is text: nothing a panel is posted is written as it came
	[ $status, $body ] = ninoCall( $appData, $panel, [ 'email' => 'slug@example.org', 'name' => '<b>x</b>', 'group' => "../../etc/passwd\0 <script>", 'invite' => false ], 'members/add' );
	check( 'a group is made a slug whatever it was posted: no slash, no dot, no markup and no null byte reaches the file - the name is kept as the text it is', $status === 200 && preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', (string) $body['account']['group'] ) === 1 && $body['account']['name'] === '<b>x</b>' );
}

/**
 *	The three files of the source read and written back: the bytes the source's writer made - plain php, var_export - are read by the feature, and a write that changes one
 *	account changes that account's bytes and nothing of another's; a sign-in leaves the epoch as it is; a spent link takes its own entry out and no other
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersRoundTripChecks( array &$appData ): void {

	global $mails;

	echo "\nThe data of the source - read, and written back byte for byte\n";

	$members = \Nino\Modules\Members::class;
	$write = static fn( array $state ): string => '<?php return '. var_export( $state, true ). ';';
	$put = static function( string $file, string $bytes ) use ( &$appData ): void {
		file_put_contents( \Nino\Filesystem::path( $appData, $file ), $bytes );
		unset( $appData['./nino/filesystem/cache'][$file] );
		clearstatcache();
		function_exists( 'opcache_invalidate' ) === true && @opcache_invalidate( \Nino\Filesystem::path( $appData, $file ), true );
	};
	$read = static fn( string $file ): string => (string) file_get_contents( \Nino\Filesystem::path( $appData, $file ) );

	// What the source's writer made: three accounts, one with devices, one with an empty list of them (an older version wrote that), one with an epoch of its own
	$device = [ 'hash' => str_repeat( 'a', 64 ), 'created' => '2026-11-11T14:32:37Z', 'expires' => time() + 86400 * 30, 'agent' => 'Agent/1.0' ];
	$accounts = [ 'accounts' => [
		'dcf5c496d8c5' => [ 'email' => 'person-1@example.org', 'name' => 'Dana Beispiel', 'group' => 'k-1', 'active' => true, 'created' => '2026-11-11T14:29:49Z', 'login' => '2026-11-15T06:19:12Z' ],
		'9973c38d2e1d' => [ 'email' => 'person-2@example.org', 'name' => 'Bente Beispiel', 'group' => 'k-2', 'active' => true, 'created' => '2026-11-11T14:32:37Z', 'login' => '2026-11-11T18:17:21Z', 'remember' => [] ],
		'5fcff8c4394f' => [ 'email' => 'person-3@example.org', 'name' => 'Chris Beispiel', 'group' => '', 'active' => false, 'created' => '2026-11-11T15:01:44Z', 'login' => null, 'epoch' => str_repeat( 'b', 32 ), 'remember' => [ $device ] ],
	] ];
	$link = hash( 'sha256', 'a-token-of-the-source' );
	$gone = hash( 'sha256', 'another-token' );
	$tokens = [
		$link => [ 'account' => 'dcf5c496d8c5', 'kind' => 'login', 'expires' => time() + 900, 'target' => '/club/news' ],
		$gone => [ 'account' => '9973c38d2e1d', 'kind' => 'invite', 'expires' => time() + 86400, 'target' => '' ],
	];
	$epoch = [ 'epoch' => '5ef568a992fdbbbef9c6f1c9875a3e7f' ];

	$put( '/data/members.php', $write( $accounts ) );
	$put( '/data/members-tokens.php', $write( $tokens ) );
	$put( '/data/members-session.php', $write( $epoch ) );
	check( 'the writer of the kernel is the writer of the source: what \\Nino\\Filesystem writes for these three states is, byte for byte, plain php of var_export - the files a project has are the files this writes', ( static function() use ( &$appData, $accounts, $tokens, $epoch, $write ): bool {
		$probe = [ '/data/rt-a.php' => $accounts, '/data/rt-b.php' => $tokens, '/data/rt-c.php' => $epoch ];

		foreach( $probe as $file => $state ) {
			\Nino\Filesystem::putFileContent( $appData, $file, $state );

			if( (string) file_get_contents( \Nino\Filesystem::path( $appData, $file ) ) !== $write( $state ) )
				return false;
		}

		return true;
	} )() );
	check( 'the accounts of the source are read as they are: three, the address lower case, the group as it stands, the devices left out of what is shown', count( $members::accounts( $appData ) ) === 3
		&& $members::account( $appData, '9973c38d2e1d' ) === [ 'id' => '9973c38d2e1d' ] + array_diff_key( $accounts['accounts']['9973c38d2e1d'], [ 'remember' => 1 ] ) && $members::account( $appData, '5fcff8c4394f' )['active'] === false && $members::groups( $appData ) === [ 'k-1', 'k-2' ] );

	// A write that changes one account is a change of that account's bytes: its name line, and nothing of the others - the empty list of devices stays as the source left it
	check( 'a change that is none writes nothing: the three files are as they were, byte for byte', is_array( $members::change( $appData, 'dcf5c496d8c5', [ 'name' => 'Dana Beispiel', 'group' => 'k-1', 'active' => true ] ) )
		&& $read( '/data/members.php' ) === $write( $accounts ) && $read( '/data/members-tokens.php' ) === $write( $tokens ) && $read( '/data/members-session.php' ) === $write( $epoch ) );
	$members::change( $appData, 'dcf5c496d8c5', [ 'name' => 'Dana Neu' ] );
	$changed = $accounts;
	$changed['accounts']['dcf5c496d8c5']['name'] = 'Dana Neu';
	check( 'a change of one name rewrites the file with that name and with every other byte of every account as it was - the order, the empty list of devices, the device of the third, the epoch of its own', $read( '/data/members.php' ) === $write( $changed )
		&& str_replace( "'Dana Neu'", "'Dana Beispiel'", $read( '/data/members.php' ) ) === $write( $accounts ) );

	$members::change( $appData, 'dcf5c496d8c5', [ 'name' => 'Dana Beispiel' ] );
	$put( '/data/members.php', $write( $accounts ) );

	// A sign-in: the epoch of the source is the epoch of the session - nothing writes a new one - and the time of the sign-in is the one line that changes of the account
	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [] ] );
	membersVisitor();
	$mailsKept = count( $mails );
	$answer = membersConfirm( $appData, ( static function() use ( &$appData, $members, $put, $write, $tokens, $accounts, $link ): string {
		// The token of the source is only a hash: a link made now stands beside it, which the sign-in then spends
		membersFresh( $appData );
		$token = membersLink( $appData, [ 'id' => 'dcf5c496d8c5' ] + $accounts['accounts']['dcf5c496d8c5'], 'login', '/club/news' );
		return $token;
	} )() );
	$signed = $accounts;
	$login = (string) ( $members::account( $appData, 'dcf5c496d8c5' )['login'] ?? '' );
	$signed['accounts']['dcf5c496d8c5']['login'] = $login;
	check( 'a sign-in leaves the epoch of the source as it is, byte for byte, and writes the time of the sign-in in the one account - every other byte of the accounts file stays', $answer[0] === 303 && $read( '/data/members-session.php' ) === $write( $epoch ) && $read( '/data/members.php' ) === $write( $signed )
		&& $login !== $accounts['accounts']['dcf5c496d8c5']['login'] && preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $login ) === 1 );

	$remaining = $read( '/data/members-tokens.php' );
	$left = membersFile( $appData, 'tokens' );
	check( 'the link that was spent took its own entry out of the file of the links and the two of the source stayed as they were, in the order they were', isset( $left[$link], $left[$gone] ) === true && array_slice( $left, 0, 2, true ) === $tokens && count( $left ) === 2 && $remaining === $write( $tokens ) );

	// Every sign-out writes a new epoch of 32 hex characters, and a restore brings the epoch of the source back as it was
	$members::signOutAll( $appData );
	check( 'signing everybody out replaces the epoch with another of 32 hex characters and takes the lists of devices of the accounts away - the empty one too', preg_match( '/^[0-9a-f]{32}$/D', membersFile( $appData, 'session' )['epoch'] ?? '' ) === 1 && membersFile( $appData, 'session' )['epoch'] !== $epoch['epoch']
		&& array_filter( membersFile( $appData, 'accounts' )['accounts'], static fn( array $account ): bool => isset( $account['remember'] ) === true ) === [] );
}

/**
 *	What happens where Members and the shared password of Protected area (features/ProtectedArea) shut the same path, measured: both gates run, each at priority 1
 *	of the response, in the order the two modules stand in /nino/modules. A page opens for a visitor who has passed both and for nobody else; a visitor who has
 *	passed none is answered by the form of the module that stands last, one who has passed one is answered by the form of the other
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersBothGatesChecks( array &$appData ): void {

	echo "\nBoth gates on one path - Members and the shared password of Protected area\n";

	$members = \Nino\Modules\Members::class;
	$protected = \Nino\Modules\ProtectedArea::class;

	if( is_dir( dirname( __DIR__, 2 ). '/ProtectedArea' ) === false ) {
		echo "  note - no feature ProtectedArea beside this one: the two gates were not measured\n";
		return;
	}

	if( \Nino\Features::activate( $appData, 'protected' ) !== true ) {
		check( 'the feature Protected area can be switched on beside this one', false );
		return;
	}

	\Nino\Features::saveSettings( $appData, 'protected', [ 'paths' => [ '/club' ], 'password' => $protected::hashPassword( 'sesam' ) ] );
	\Nino\Features::saveSettings( $appData, 'members', [ 'paths' => [ '/club' ] ] );
	$appData['/nino/http/routes']['GET://club/both'] = [ 'uri' => '/club/both', 'body' => 'THE PAGE' ];
	$account = $members::add( $appData, 'both@example.org', 'Both', '' );

	$answers = [];

	foreach( [ 'Members first' => [ $members, $protected ], 'Protected area first' => [ $protected, $members ] ] as $name => $order ) {

		$both = $appData;
		$both['./nino/callbacks'] = [];

		foreach( $order as $class )
			$class::init( $both );

		$serve = static function() use ( &$both ): string {
			$request = ninoVisit( $both, '/club/both' );
			\Nino\Http::response( $both, $request );
			return $request['/nino/http/response']['statusCode']. ' '. $request['/nino/http/response']['body'];
		};
		$passMembers = static function() use ( &$both, $account ): void {
			\Nino\Filesystem::putFileContent( $both, '/data/members-session.php', [ 'epoch' => str_repeat( 'a', 32 ) ] );
			\Nino\Runtime::setSessionValue( $both, './members/account', [ 'id' => $account['id'], 'epoch' => str_repeat( 'a', 32 ) ] );
		};
		$passPassword = static function() use ( &$both ): void {
			\Nino\Filesystem::putFileContent( $both, '/data/protected-session.php', [ 'epoch' => 'e1' ] );
			\Nino\Runtime::setSessionValue( $both, './protected/unlocked', 'e1' );
		};

		membersVisitor();
		$none = $serve();
		$passPassword();
		$password = $serve();
		membersVisitor();
		$passMembers();
		$member = $serve();
		$passPassword();
		$answers[$name] = [ $none, $password, $member, $serve() ];
	}

	$form = [ 'members' => '401 [template /templates/page-members-login]', 'protected' => '401 [template /templates/page-protected]' ];
	check( 'on one path both gates must be passed: the page opens for a visitor who is signed in and has the password, for nobody else - whatever the order of the two modules',
		$answers['Members first'][3] === '200 THE PAGE' && $answers['Protected area first'][3] === '200 THE PAGE' && in_array( '200 THE PAGE', array_merge( array_slice( $answers['Members first'], 0, 3 ), array_slice( $answers['Protected area first'], 0, 3 ) ), true ) === false );
	check( '...a visitor who has passed neither is answered by the form of the module that stands last in /nino/modules, and a visitor who has passed one is asked for the other',
		$answers['Members first'][0] === $form['protected'] && $answers['Protected area first'][0] === $form['members'] && $answers['Members first'][1] === $form['members'] && $answers['Protected area first'][1] === $form['members']
		&& $answers['Members first'][2] === $form['protected'] && $answers['Protected area first'][2] === $form['protected'] );
}

/**
 *	The first thing the suite asks, and with a short time: where both ways run - the kernel hands the work back and the process then ends - the mail of a code goes out exactly
 *	once. A queue that is not emptied sends every mail before it with every request after it, and a suite that goes on from there takes minutes to say so: it stops here
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersOnceCheck( array &$appData ): void {

	echo "\nThe mail that two ways could send\n";

	$members = \Nino\Modules\Members::class;
	$sandbox = ninoSandboxDir( $appData );
	$mailFile = $sandbox. '/once-mails.jsonl';
	$warnFile = $sandbox. '/once-warnings.txt';
	@unlink( $mailFile );
	$members::add( $appData, 'both@example.org', 'Both', 'p' );
	membersFresh( $appData );

	$process = proc_open( [ PHP_BINARY, __FILE__, 'both-worker', $sandbox, '203.0.113.81', $mailFile, 'both@example.org', '', $warnFile ], [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
	stream_set_blocking( $pipes[1], false );
	stream_set_blocking( $pipes[2], false );
	$said = '';

	// Five seconds are plenty for a process that sends one mail
	for( $waited = 0; $waited < 5000; $waited += 10 ) {
		$said .= (string) stream_get_contents( $pipes[1] );
		stream_get_contents( $pipes[2] );

		if( proc_get_status( $process )['running'] === false )
			break;

		usleep( 10000 );
	}

	$ended = proc_get_status( $process )['running'] === false;
	$ended === true || proc_terminate( $process, 9 );
	$said .= (string) stream_get_contents( $pipes[1] );
	proc_close( $process );
	$sent = is_file( $mailFile ) === true ? array_filter( explode( "\n", trim( (string) file_get_contents( $mailFile ) ) ) ) : [];
	$once = $ended === true && (int) trim( $said ) >= 1 && count( $sent ) === 1 && ( json_decode( (string) ( $sent[0] ?? '' ), true )['to'] ?? '' ) === 'both@example.org';

	check( 'where the kernel runs the work after the answer and the process then ends - both ways are there - the mail of a code goes out exactly once, in the time a mail needs', $once === true );

	if( $once === false ) {
		echo "  stopped: a queue of mails that is not emptied makes every request after it slower, and the rest of the suite would only say the same\n";
		ninoDone( $appData );
	}

	// What the check made is taken away: the suite starts from a project with no member
	@unlink( $mailFile );

	foreach( [ $members::ACCOUNTS, $members::TOKENS, '/data/members-session.php', '/data/members-tries.php' ] as $file )
		@unlink( \Nino\Filesystem::path( $appData, $file ) );

	unset( $appData['./nino/filesystem/cache'] );
	membersFresh( $appData );
}

/**
 *	What only a second process can show: a mail that the kernel cannot send after the answer (it says so once the answer is finished, and drops the work) is still sent,
 *	as the request ends; and the link that is made while the change of an address waits for the accounts, between the two deletions of the links, is no link after it
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersWorkerChecks( array &$appData ): void {

	echo "\nTwo processes - the mail that the kernel drops, and the link made while an address is changed\n";

	$members = \Nino\Modules\Members::class;
	$sandbox = ninoSandboxDir( $appData );
	$run = static function( array $args ): array {
		$process = proc_open( array_merge( [ PHP_BINARY, __FILE__ ], $args ), [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
		return [ $process, $pipes ];
	};
	// A worker that does not end - a queue that is never emptied is a loop - is a failed pin and no hung suite: it is ended after ten seconds
	$finish = static function( array $started ): string {

		stream_set_blocking( $started[1][1], false );
		stream_set_blocking( $started[1][2], false );
		$out = '';

		for( $waited = 0; $waited < 10000; $waited += 10 ) {
			$out .= (string) stream_get_contents( $started[1][1] );
			stream_get_contents( $started[1][2] );

			if( proc_get_status( $started[0] )['running'] === false )
				break;

			usleep( 10000 );
		}

		$ended = proc_get_status( $started[0] )['running'] === false;
		$ended === true || proc_terminate( $started[0], 9 );
		$out .= (string) stream_get_contents( $started[1][1] );
		proc_close( $started[0] );

		return $ended === true ? trim( $out ) : 'timeout';
	};

	// The kernel drops the work after the answer where a length was told by somebody else: the mail goes out as the process ends
	$mine = $members::add( $appData, 'dropped@example.org', 'Dropped', 'p' );
	$mailFile = $sandbox. '/dropped-mails.jsonl';
	@unlink( $mailFile );
	membersFresh( $appData );
	$warnFile = $sandbox. '/dropped-warnings.txt';
	@unlink( $warnFile );
	$said = $finish( $run( [ 'drop-worker', $sandbox, '203.0.113.77', $mailFile, 'dropped@example.org', '', $warnFile ] ) );
	$sent = is_file( $mailFile ) === true ? array_filter( explode( "\n", trim( (string) file_get_contents( $mailFile ) ) ) ) : [];
	check( 'where the kernel cannot let the client go before the work (it drops it: it runs nothing after the answer) the mail of a code is not lost: it goes out as the request ends, once', is_array( $mine ) === true && $said === '0'
		&& count( $sent ) === 1 && ( json_decode( (string) ( $sent[0] ?? '' ), true )['to'] ?? '' ) === 'dropped@example.org' );

	// A transport that throws at the first of two mails ends neither the answer nor the second mail, where the work runs as the process ends - and what is said of it is the class of the failure
	foreach( [ 'boom@example.org', 'second@example.org' ] as $address )
		$members::add( $appData, $address, 'Two', 'p' );

	@unlink( $mailFile );
	@unlink( $warnFile );
	membersFresh( $appData );
	$said = $finish( $run( [ 'drop-worker', $sandbox, '203.0.113.82', $mailFile, 'boom@example.org,second@example.org', 'boom@example.org', $warnFile ] ) );
	$sent = is_file( $mailFile ) === true ? array_map( static fn( string $line ): string => (string) ( json_decode( $line, true )['to'] ?? '' ), array_filter( explode( "\n", trim( (string) file_get_contents( $mailFile ) ) ) ) ) : [];
	$warned = is_file( $warnFile ) === true ? (string) file_get_contents( $warnFile ) : '';
	check( 'a transport that throws at the first of two mails, where the work runs as the process ends: the answer is the one it is otherwise (nothing but the count of the work the kernel handed back, 0), the second mail arrives', $said === '0' && array_values( $sent ) === [ 'second@example.org' ] );
	check( '...and what is told of the failure is a warning with the class of it and neither its message nor an address', substr_count( $warned, 'RuntimeException' ) === 1 && str_contains( $warned, 'boom' ) === false && str_contains( $warned, 'is down' ) === false && str_contains( $warned, '@' ) === false );

	// A write that fails, with the work done before it: the account is not deleted and nobody is told it is. The process may read, take the locks and not write the data:
	// root gives up its rights for it, any other user has the file and the directory of the accounts made read-only for it
	$stay = $members::add( $appData, 'stays@example.org', 'Stays', 'stay-group' );
	\Nino\Filesystem::mutate( $appData, $members::ACCOUNTS, static fn( mixed $state ): ?array => null, [] );
	$lockDir = \Nino\Filesystem::path( $appData, '/data' ). '/.locks';
	$accountsFile = \Nino\Filesystem::path( $appData, $members::ACCOUNTS );
	$dataDir = dirname( $accountsFile );
	$privileged = function_exists( 'posix_getuid' ) === true && posix_getuid() === 0;

	if( $privileged === true ) {
		chmod( $lockDir, 0777 );

		foreach( glob( $lockDir. '/*.lock' ) ?: [] as $lockFile )
			chmod( $lockFile, 0666 );
	}
	else {
		chmod( $accountsFile, 0444 );
		chmod( $dataDir, 0555 );
	}

	$accountsBefore = (string) file_get_contents( $accountsFile );
	[ $answer, $groups, $told ] = (array) json_decode( $finish( $run( [ 'nobody-worker', $sandbox, '203.0.113.80', (string) $stay['id'], 'stay-group' ] ) ), true ) + [ '', '', [ 'not run' ] ];

	if( $privileged === false ) {
		chmod( $dataDir, 0755 );
		chmod( $accountsFile, 0644 );
	}

	check( 'where the write of the accounts fails - the account was found, the file is not written - remove() and removeGroups() answer write, nothing is deleted and /members/removed tells nobody',
		$answer === 'write' && $groups === 'write' && $told === [] && (string) file_get_contents( $accountsFile ) === $accountsBefore && $members::account( $appData, (string) $stay['id'] ) !== null );

	// The race the second deletion of the links closes
	$racer = $members::add( $appData, 'racer@example.org', 'Racer', 'p' );
	$ready = $sandbox. '/race-ready';
	@unlink( $ready );
	membersFresh( $appData );
	$holder = $run( [ 'hold-worker', $sandbox, '203.0.113.78', $ready ] );
	$linker = $run( [ 'link-worker', $sandbox, '203.0.113.79', $ready, (string) $racer['id'], 'racer@example.org' ] );

	for( $waited = 0; is_file( $ready ) === false && $waited < 5000; $waited += 5 )
		usleep( 5000 );

	$changed = $members::change( $appData, (string) $racer['id'], [ 'email' => 'racer.new@example.org' ] );
	$made = $finish( $linker );
	$finish( $holder );
	unset( $appData['./nino/filesystem/cache'] );
	check( 'a link made while the change of an address waits for the accounts - after the first deletion of the links, before the write - is none after the change: it is made, and it is deleted by the second deletion',
		is_file( $ready ) === true && $made === 'true' && is_array( $changed ) === true && $changed['email'] === 'racer.new@example.org' && array_filter( membersFile( $appData, 'tokens' ), static fn( array $entry ): bool => $entry['account'] === $racer['id'] ) === [] );
}

/**
 *	What ends with a sign-out of everybody and with the switching off of an account: the sessions, the devices - and the links and codes that are out, so that none made
 *	before signs in after; an account switched on again does not get its old session back
 *
 *	@param		array 		&$appData
 *
 *	@return 	void
 */
function membersEndChecks( array &$appData ): void {

	echo "\nWhat ends a session ends the links with it\n";

	$members = \Nino\Modules\Members::class;
	$ended = $members::add( $appData, 'ended@example.org', 'Ended', 'e' );
	$tokens = static fn( string $id ): array => array_filter( membersFile( $appData, 'tokens' ), static fn( array $entry ): bool => $entry['account'] === $id && $entry['expires'] > time() );

	// Everybody out: a link and a code made before are none after
	membersVisitor();
	membersFresh( $appData );
	$link = membersLink( $appData, $ended );
	$code = membersAskCode( $appData, 'ended@example.org', '/join' );
	$asking = $_SESSION;
	$before = count( $tokens( $ended['id'] ) );
	membersVisitor();
	$_SESSION = $asking;
	check( 'a link and a code are out for the account', $link !== '' && $code !== '' && $before === 2 );
	// A link that ran a minute ago: its record is kept for the page of a link that does not work, and a sign-out of everybody leaves it
	$ranOut = hash( 'sha256', 'a link that ran out' );
	\Nino\Filesystem::mutate( $appData, $members::TOKENS, static fn( mixed $state ): array => ( is_array( $state ) === true ? $state : [] ) + [ $ranOut => [ 'account' => $ended['id'], 'kind' => 'login', 'expires' => time() - 60, 'target' => '/join' ] ], [] );
	check( 'signing everybody out deletes the links and the codes that are out: the file of them holds nothing live', $members::signOutAll( $appData ) === true && $tokens( $ended['id'] ) === [] );
	check( '...and what ran out stays for the page of a link that does not work: the record of the link that ran out a minute ago is in the file still, with its target, and no record of a live one is', ( membersFile( $appData, 'tokens' )[$ranOut]['target'] ?? '' ) === '/join' && array_filter( membersFile( $appData, 'tokens' ), static fn( array $entry ): bool => $entry['expires'] > time() ) === [] );
	check( '...so the link made before signs nobody in - the page of a link that does not work, 400 - and neither does the code: wrong, nobody is signed in', membersConfirm( $appData, $link )[0] === 400 && $members::current( $appData ) === null
		&& membersCodeServe( $appData, $code )[1] === '/join?members=wrong' && $members::current( $appData ) === null );

	// Switched off and on: no session, no link, no way back
	membersVisitor();
	membersFresh( $appData );
	$link = membersLink( $appData, $ended );
	membersSignIn( $appData, $ended );
	$session = $_SESSION;
	$again = membersLink( $appData, $ended );
	$members::change( $appData, $ended['id'], [ 'active' => false ] );
	check( 'switching an account off deletes the links and the codes it has out', $tokens( $ended['id'] ) === [] && $link !== '' && $again !== '' );
	$members::change( $appData, $ended['id'], [ 'active' => true ] );
	$_SESSION = $session;
	check( '...and switching it on again brings back neither its session - the session it had reads signed out - nor a link: the link made before is the page of a link that does not work', $members::current( $appData ) === null );
	membersVisitor();
	check( '...the link made before it was switched off signs nobody in', membersConfirm( $appData, $again )[0] === 400 && $members::current( $appData ) === null );
	membersFresh( $appData );
	check( '...and a sign-in made now is a sign-in: the account is the same, and signs in', membersSignIn( $appData, $ended )[0] === 303 && ( $members::current( $appData )['id'] ?? '' ) === $ended['id'] );
	membersVisitor();
}
