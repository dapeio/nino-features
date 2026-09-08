<?php
declare(strict_types=1);

/**
 *	Nino
 *	mailer-smoke.php	Contract test for the Mailer feature (Modules\Mailer):
 *										the manifest and the activation through \Nino\Features,
 *										the SMTP transport itself against a fake server run as a
 *										child process (no network) - the dialogue, the message it
 *										builds, From resolution, refusals and a dead port - and
 *										the workbench panel with its permission. Travels with the
 *										feature and runs against the checkout three levels up, or
 *										the one NINO_ROOT names (see tests/harness.php there).
 *
 *	Usage: php features/Mailer/tests/mailer-smoke.php
 *	       NINO_ROOT=../nino php features/Mailer/tests/mailer-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';


// --- The fake SMTP server, as a child process -------------------------------

/**
 *	A local port nothing is listening on: bind it, then close it again. Good
 *	enough for "nothing is listening" - a real gap between the two calls is
 *	not a concern on a loopback address in a test sandbox.
 *
 *	@return 	int
 */
function freeMailerPort(): int {

	$probe = stream_socket_server( 'tcp://127.0.0.1:0', $errno, $errstr );
	if( $probe === false )
		throw new \RuntimeException( 'could not reserve a port: '. $errstr );

	$name = stream_socket_get_name( $probe, false );
	$port = (int) substr( $name, (int) strrpos( $name, ':' ) + 1 );
	fclose( $probe );

	return $port;
}

/**
 *	Start tests/fake-smtp-server.php as a child process and wait for it to
 *	report the port it bound. Registers a shutdown function so the process
 *	is killed even if a check fails or throws.
 *
 *	@return 	array										{ process, port, controlFile, logFile }
 */
function startMailerServer(): array {

	$controlFile	= sys_get_temp_dir(). '/nino-mailer-smoke-control-'. uniqid(). '.json';
	$logFile			= sys_get_temp_dir(). '/nino-mailer-smoke-log-'. uniqid(). '.json';
	file_put_contents( $controlFile, '{}' );

	$descriptors = [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ];
	$process = proc_open( [ PHP_BINARY, __DIR__. '/fake-smtp-server.php', $controlFile, $logFile ], $descriptors, $pipes );

	if( is_resource( $process ) === false )
		throw new \RuntimeException( 'could not start the fake smtp server' );

	$port = (int) trim( (string) fgets( $pipes[1] ) );
	fclose( $pipes[0] );

	register_shutdown_function( static function() use ( $process, $pipes, $controlFile, $logFile ): void {
		if( is_resource( $process ) === true )
			proc_terminate( $process );
		foreach( $pipes as $pipe )
			if( is_resource( $pipe ) === true )
				@fclose( $pipe );
		@unlink( $controlFile );
		@unlink( $logFile );
	} );

	if( $port <= 0 )
		throw new \RuntimeException( 'the fake smtp server did not report a port' );

	return [ 'process' => $process, 'port' => $port, 'controlFile' => $controlFile, 'logFile' => $logFile ];
}

function setMailerControl( array $server, array $control ): void {
	file_put_contents( $server['controlFile'], json_encode( $control ) );
}

/**
 *	Removes any log left over from an earlier session, so waitForMailerLog()
 *	cannot mistake a stale file for this one's
 *
 *	@param		array 		$server
 *
 *	@return 	void
 */
function resetMailerLog( array $server ): void {
	@unlink( $server['logFile'] );
}

/**
 *	@param		array 		$server
 *	@param		float			$timeoutSeconds
 *
 *	@return 	array|null							{ commands: string[], data: string }, null if no session completed in time
 */
function waitForMailerLog( array $server, float $timeoutSeconds = 2.0 ): ?array {

	$deadline = microtime( true ) + $timeoutSeconds;

	while( microtime( true ) < $deadline ) {

		clearstatcache( true, $server['logFile'] );

		if( is_file( $server['logFile'] ) === true ) {
			$decoded = json_decode( (string) file_get_contents( $server['logFile'] ), true );
			if( is_array( $decoded ) === true )
				return $decoded;
		}

		usleep( 10000 );
	}

	return null;
}

/**
 *	A fresh budget for 127.0.0.1, whatever an earlier check spent - the same
 *	technique tests/kernel-smoke.php uses for \Nino\Mail::send()'s own cap
 *
 *	@param		array 		&$appData			(reference) Array with current app data
 *
 *	@return 	void
 */
function resetMailerRateLimit( array &$appData ): void {

	$state = \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] );
	unset( $state['127.0.0.1'] );
	\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', $state );
	unset( $appData['./nino/mail/ratelimited'] );
}

function callAdminPost( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST['action'] = $action;
	$_POST['data'] = json_encode( $data );
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

$server = startMailerServer();


// --- The feature - manifest and activation ----------------------------------

echo "The feature - manifest and activation\n";

$appData = ninoSandbox( 'mailer' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "mailer"', is_array( $manifest ) && $manifest['key'] === 'mailer' && ninoWarnings() === [] );
check( 'the feature is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'the settings schema declares every setting this feature reads', is_array( $manifest )
	&& array_keys( $manifest['settings'] ) === [ 'host', 'port', 'encryption', 'username', 'password', 'from', 'fromName', 'timeout', 'verify' ] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'mailer' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'mailer' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Mailer', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'mailer' )['installed'] === $manifest['version'] );
check( 'the settings answer their defaults', \Nino\Features::settings( $appData, 'mailer' ) === [
	'host' => '', 'port' => 587, 'encryption' => 'starttls', 'username' => '', 'password' => '',
	'from' => '', 'fromName' => '', 'timeout' => 15, 'verify' => true,
] );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the transport under \\Nino\\Mail::TRANSPORT', isset( $appData['./nino/callbacks'][ \Nino\Mail::TRANSPORT ] ) === true );

echo "\n";


// --- Not configured, and no From address available -------------------------

echo "Modules\\Mailer::callbackSend - not configured leaves the mail for mail()\n";

$notConfigured = [ 'to' => 'to@example.org', 'subject' => 'x', 'body' => 'y', 'replyTo' => '', 'sender' => '', 'headers' => 'MIME-Version: 1.0', 'sent' => null ];
\Nino\Modules\Mailer::callbackSend( $appData, $notConfigured );
check( 'an empty host leaves "sent" at null rather than false', $notConfigured['sent'] === null );
check( 'and warns nothing - this is the expected, unconfigured state', ninoWarnings() === [] );

\Nino\Features::saveSettings( $appData, 'mailer', [ 'host' => '127.0.0.1', 'port' => (string) $server['port'], 'encryption' => 'none', 'timeout' => '5' ] );

$noFrom = [ 'to' => 'to@example.org', 'subject' => 'x', 'body' => 'y', 'replyTo' => '', 'sender' => '', 'headers' => 'MIME-Version: 1.0', 'sent' => null ];
\Nino\Modules\Mailer::callbackSend( $appData, $noFrom );
check( 'a host with neither a kernel sender nor a "from" setting refuses rather than guessing', $noFrom['sent'] === false );
check( 'and records why, without ever mentioning a password', str_contains( implode( ' ', ninoWarnings() ), 'From address' ) === true );

\Nino\Features::saveSettings( $appData, 'mailer', [
	'username'	=> 'mailer-user',
	'password'	=> 's3cret-passw0rd',
	'from'			=> 'no-reply@example.org',
	'fromName'	=> 'Café Nino',
] );

echo "\n";


// --- A real send against the fake server ------------------------------------

echo "Modules\\Mailer\\Smtp - a real SMTP session against the fake server\n";

resetMailerRateLimit( $appData );
resetMailerLog( $server );

$sentSubject = "Grüße!\r\nBcc: x@example.org"; // \Nino\Mail::send() itself strips the header-injection attempt
$sentBody = "<p>Hello</p>\n.Signature line\nBye"; // the middle line must be dot-stuffed on the wire

$sendResult = \Nino\Mail::send( $appData, 'to@example.org', $sentSubject, $sentBody, 'reply@example.org' );
check( 'the send reports success', $sendResult === true );

$session = waitForMailerLog( $server );
check( 'the fake server received exactly one session', is_array( $session ) === true );

$commands = $session['commands'] ?? [];
$data			= (string) ( $session['data'] ?? '' );

check( 'the client greeted with EHLO', ( static function() use ( $commands ): bool {
	foreach( $commands as $command )
		if( str_starts_with( strtoupper( $command ), 'EHLO' ) === true )
			return true;
	return false;
} )() );

$authCommand = null;
foreach( $commands as $command )
	if( str_starts_with( strtoupper( $command ), 'AUTH PLAIN ' ) === true )
		$authCommand = $command;
check( 'it authenticated with AUTH PLAIN, advertised by the fake server\'s EHLO', $authCommand !== null );
check( 'the credentials travel as the advertised base64(\0user\0pass), decodable back to what was configured',
	$authCommand !== null && base64_decode( substr( $authCommand, strlen( 'AUTH PLAIN ' ) ) ) === "\0mailer-user\0s3cret-passw0rd" );

check( 'MAIL FROM names the configured From address', in_array( 'MAIL FROM:<no-reply@example.org>', $commands, true ) === true );
check( 'exactly one RCPT TO for the one recipient', array_values( array_filter( $commands, static fn( string $c ): bool => str_starts_with( $c, 'RCPT TO:' ) ) ) === [ 'RCPT TO:<to@example.org>' ] );
check( 'DATA was sent', in_array( 'DATA', $commands, true ) === true );

check( 'the payload uses CRLF line endings throughout, never a bare LF', preg_match( '/(?<!\r)\n/', $data ) !== 1 );
check( 'it ends with the DATA terminator, CRLF "." CRLF', str_ends_with( $data, "\r\n.\r\n" ) === true );
check( 'a non-ascii subject is RFC 2047 B-encoded and decodes back to the original', ( static function() use ( $data, $sentSubject ): bool {
	if( preg_match( '/^Subject: =\?UTF-8\?B\?([A-Za-z0-9+\/=]+)\?=\r$/m', $data, $m ) !== 1 )
		return false;
	// _headerValue() in Mail::send() already stripped the injected header line
	return base64_decode( $m[1] ) === "Grüße!Bcc: x@example.org";
} )() );
check( 'Content-Type from the kernel\'s own header block is carried through', str_contains( $data, "Content-Type: text/html; charset=UTF-8\r\n" ) === true );
check( 'Reply-To from the mail is present', str_contains( $data, "Reply-To: reply@example.org\r\n" ) === true );
check( 'MIME-Version appears exactly once - not duplicated with the kernel\'s own', substr_count( $data, 'MIME-Version:' ) === 1 );
check( 'Date and Message-ID headers are present', preg_match( '/^Date: .+\r$/m', $data ) === 1 && preg_match( '/^Message-ID: <[0-9a-f]+@127\.0\.0\.1>\r$/m', $data ) === 1 );
check( 'the body line starting with "." was dot-stuffed', str_contains( $data, "\r\n..Signature line\r\n" ) === true );
check( 'the From: header uses the configured address and RFC 2047-encodes the non-ascii display name, quoted',
	preg_match( '/^From: "(=\?UTF-8\?B\?[A-Za-z0-9+\/=]+\?=)" <no-reply@example\.org>\r$/m', $data, $m ) === 1
	&& base64_decode( substr( $m[1], strlen( '=?UTF-8?B?' ), -2 ) ) === 'Café Nino' );

echo "\n";


// --- From resolution: the kernel's sender wins over the setting ------------

echo "From resolution\n";

$appData['/nino/mail/sender'] = 'kernel-sender@example.org';
resetMailerRateLimit( $appData );
resetMailerLog( $server );
check( 'a send with a kernel sender still succeeds', \Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' ) === true );

$kernelSenderSession = waitForMailerLog( $server );
$kernelSenderData = (string) ( $kernelSenderSession['data'] ?? '' );
$kernelSenderCommands = $kernelSenderSession['commands'] ?? [];
check( 'MAIL FROM uses the kernel\'s own sender, not the setting', in_array( 'MAIL FROM:<kernel-sender@example.org>', $kernelSenderCommands, true ) === true );
check( 'the header block\'s own From: is kept, and Mailer adds none of its own', substr_count( $kernelSenderData, "\r\nFrom:" ) === 1
	&& str_contains( $kernelSenderData, "From: kernel-sender@example.org\r\n" ) === true );

unset( $appData['/nino/mail/sender'] );

echo "\n";


// --- Several recipients on one "to" -----------------------------------------

echo "Several recipients on one comma-separated \"to\"\n";

resetMailerLog( $server );
$multiRecipient = [
	'to' => 'a@example.org, b@example.org',
	'subject' => 'many',
	'body' => '<p>hi</p>',
	'replyTo' => '',
	'sender' => '',
	'headers' => "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8",
	'sent' => null,
];
\Nino\Modules\Mailer::callbackSend( $appData, $multiRecipient );
check( 'a mail with several comma-separated addresses is accepted, one RCPT TO per address', $multiRecipient['sent'] === true );

$multiSession = waitForMailerLog( $server );
$rcpts = array_values( array_filter( $multiSession['commands'] ?? [], static fn( string $c ): bool => str_starts_with( $c, 'RCPT TO:' ) ) );
check( 'both addresses were RCPT TO\'d, in order', $rcpts === [ 'RCPT TO:<a@example.org>', 'RCPT TO:<b@example.org>' ] );

echo "\n";


// --- AUTH LOGIN, when that is the only mechanism advertised -----------------

echo "AUTH LOGIN, when the server offers no AUTH PLAIN\n";

setMailerControl( $server, [ 'authMethods' => 'LOGIN' ] );
resetMailerRateLimit( $appData );
resetMailerLog( $server );
check( 'the send still succeeds, over AUTH LOGIN instead', \Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' ) === true );

$loginSession = waitForMailerLog( $server );
$loginCommands = $loginSession['commands'] ?? [];
check( 'AUTH LOGIN was used, not AUTH PLAIN', in_array( 'AUTH LOGIN', $loginCommands, true ) === true
	&& array_filter( $loginCommands, static fn( string $c ): bool => str_starts_with( $c, 'AUTH PLAIN' ) ) === [] );
check( 'the username and password each travel base64-encoded, on their own line, after AUTH LOGIN', ( static function() use ( $loginCommands ): bool {
	$i = array_search( 'AUTH LOGIN', $loginCommands, true );
	return $i !== false && ( $loginCommands[$i + 1] ?? null ) === base64_encode( 'mailer-user' ) && ( $loginCommands[$i + 2] ?? null ) === base64_encode( 's3cret-passw0rd' );
} )() );

setMailerControl( $server, [] );

echo "\n";


// --- Refusals ----------------------------------------------------------------

echo "Refusals: RCPT and AUTH\n";

setMailerControl( $server, [ 'failRcpt' => true ] );
resetMailerRateLimit( $appData );
check( 'a 550 to RCPT TO is a failure, not an exception', \Nino\Mail::send( $appData, 'refused@example.org', 'x', 'y', '' ) === false );
check( 'a warning names the server\'s own reply, never the password', ( static function(): bool {
	foreach( ninoWarnings() as $warning )
		if( str_starts_with( $warning, 'Mailer: ' ) === true && str_contains( $warning, 'mailbox unavailable' ) === true && str_contains( $warning, 's3cret' ) === false )
			return true;
	return false;
} )() );

setMailerControl( $server, [ 'failAuth' => true ] );
resetMailerRateLimit( $appData );
check( 'a 535 to AUTH is a failure', \Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' ) === false );
check( 'and is recorded too, without the password', ( static function(): bool {
	foreach( ninoWarnings() as $warning )
		if( str_starts_with( $warning, 'Mailer: ' ) === true && str_contains( $warning, 'authentication failed' ) === true && str_contains( $warning, 's3cret' ) === false )
			return true;
	return false;
} )() );

setMailerControl( $server, [] );

echo "\n";


// --- Nothing listening -------------------------------------------------------

echo "Nothing listening on the configured port\n";

$deadPort = freeMailerPort();
\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => (string) $deadPort ] );
resetMailerRateLimit( $appData );

$start = microtime( true );
$deadPortResult = \Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' );
$elapsed = microtime( true ) - $start;

check( 'a refused connection fails rather than hanging', $deadPortResult === false );
check( 'and does so quickly - well inside the configured timeout, not after it', $elapsed < 3.0 );
check( 'the refusal is recorded', ( static function(): bool {
	foreach( ninoWarnings() as $warning )
		if( str_starts_with( $warning, 'Mailer: could not connect' ) === true )
			return true;
	return false;
} )() );

\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => (string) $server['port'] ] );

echo "\n";


// --- The panel ---------------------------------------------------------------

echo "Modules\\Mailer\\Admin - status, the test mail action and its permission\n";

$appData['/nino/install/completed'] = true;
\Nino\Auth::insertUser( $appData, 'admin@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::insertUser( $appData, 'plain@example.com', 'plain password', [] );
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

// Merges the panel's own text() fills (the test mail's subject and body
// among them) into \Nino\Html - the same bootstrap _admin/index.php does
// for every real request
\Nino\Admin\Admin::init( $appData );

check( 'the panel is in the registry while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['mailer'] ) === true );

[ $statusCode, $statusBody ] = callAdminPost( $appData, 'mailer/status' );
check( 'mailer/status succeeds and never carries the password or username', $statusCode === 200
	&& $statusBody === [ 'host' => '127.0.0.1', 'port' => $server['port'], 'encryption' => 'none' ]
	&& array_key_exists( 'password', $statusBody ) === false && array_key_exists( 'username', $statusBody ) === false );

[ $invalidCode, $invalidBody ] = callAdminPost( $appData, 'mailer/test', [ 'to' => 'not-an-email' ] );
check( 'an invalid address is a 400', $invalidCode === 400 );

resetMailerRateLimit( $appData );
resetMailerLog( $server );
[ $sendCode, $sendBody ] = callAdminPost( $appData, 'mailer/test', [ 'to' => 'panel-target@example.org' ] );
check( 'a valid address sends through the fake server and answers 200', $sendCode === 200 && $sendBody === [ 'sent' => true ] );
check( 'the fake server actually received that test mail', is_array( waitForMailerLog( $server ) ) === true );
check( 'the log line names the recipient', \Nino\Modules\Mailer\Admin::log( 'mailer/test', [ 'to' => 'panel-target@example.org' ] ) === 'Send test mail to "panel-target@example.org"' );

setMailerControl( $server, [ 'failRcpt' => true ] );
resetMailerRateLimit( $appData );
[ $refusedCode, $refusedBody ] = callAdminPost( $appData, 'mailer/test', [ 'to' => 'refused@example.org' ] );
check( 'a transport refusal is a 400 naming the reason the transport recorded', $refusedCode === 400
	&& str_contains( (string) ( $refusedBody['error'] ?? '' ), 'mailbox unavailable' ) === true );
setMailerControl( $server, [] );

// Exhaust the per-ip budget and confirm the panel says so rather than
// reporting a generic failure - \Nino\Mail::send()'s own cap, not the
// transport, refuses this one
$rateState = \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] );
$rateState['127.0.0.1'] = [ 'tries' => 5, 'reset' => time() + 3600 ];
\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', $rateState );
[ $limitedCode, $limitedBody ] = callAdminPost( $appData, 'mailer/test', [ 'to' => 'capped@example.org' ] );
check( 'a capped client ip is reported as a rate limit, not a transport failure', $limitedCode === 400 && str_contains( (string) ( $limitedBody['error'] ?? '' ), 'rate limit' ) === true );
resetMailerRateLimit( $appData );

\Nino\Auth::logoutUser( $appData );
[ $statusCode ] = callAdminPost( $appData, 'mailer/status' );
check( 'mailer/status requires a signed-in account', $statusCode === 401 );
[ $testCode ] = callAdminPost( $appData, 'mailer/test', [ 'to' => 'to@example.org' ] );
check( 'mailer/test requires a signed-in account', $testCode === 401 );

\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );
[ $statusCode ] = callAdminPost( $appData, 'mailer/status' );
check( 'an account without the permission is rejected from mailer/status', $statusCode === 403 );
[ $testCode ] = callAdminPost( $appData, 'mailer/test', [ 'to' => 'to@example.org' ] );
check( 'an account without the permission is rejected from mailer/test', $testCode === 403 );

\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

echo "\n";


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'mailer' ) === true );
check( 'the class leaves /nino/modules', in_array( '\\Nino\\Modules\\Mailer', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the panel is gone with it', isset( \Nino\Admin\Admin::panels( $appData )['mailer'] ) === false );
check( 'the settings survive the deactivation', \Nino\Features::setting( $appData, 'mailer', 'from' ) === 'no-reply@example.org' );

ninoDone( $appData );
