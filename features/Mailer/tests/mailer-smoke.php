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
check( 'it declares the file of last errors it keeps, so a backup carries it', is_array( $manifest ) && $manifest['data'] === [ '/data/mailer.php' ] );
check( 'the port may be 0, which is what it is until somebody sets one', is_array( $manifest ) && $manifest['settings']['port']['min'] === 0 && $manifest['settings']['port']['default'] === 0 );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

// Where this kernel has the Legal module: the type its unit creates, which is what
// the feature's section is added to when it is activated below
$legalFile	= $root. '/_nino/Nino/Modules/Legal/install/elements/privacy.php';
$hasLegal		= class_exists( '\\Nino\\Modules\\Legal' ) === true && is_file( $legalFile ) === true;

if( $hasLegal === true )
	check( 'the module\'s type is seeded', \Nino\Elements::seed( $appData, 'privacy', include $legalFile, [ 'de_DE', 'en_US' ] ) === true );

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'mailer' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'mailer' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Mailer', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'mailer' )['installed'] === $manifest['version'] );
check( 'its unit carries the feature\'s section of the privacy policy, and nothing else', array_keys( include $dir. '/install/manifest.php' ) === [ 'elements' ]
	&& ( include $dir. '/install/manifest.php' )['elements'] === [ 'privacy' => 'elements/privacy.php' ] && is_file( $dir. '/install/elements/privacy.php' ) === true );

if( $hasLegal === false )
	echo "  note - this Nino has no \\Nino\\Modules\\Legal: the section is not added to a type here, tests/legal-smoke.php of the catalogue says what it can\n";
else {
	$privacyOf = static fn( string $locale ): array => (array) \Nino\Elements::getElement( $appData, '/privacy/mailer', $locale, false );
	check( 'activation added the section "mailer" to the module\'s type, in both languages and at its position', ( $privacyOf( 'de_DE' )['title'] ?? null ) === 'E-Mail-Versand'
		&& ( $privacyOf( 'en_US' )['title'] ?? null ) === 'Sending emails' && ( $privacyOf( 'en_US' )['order'] ?? null ) === 540 );

	// An editor's change stays, and the next activation adds nothing
	\Nino\Filesystem::mutate( $appData, '/elements/privacy.php', static function( array $type ): array {
		$type['de_DE']['mailer']['title'] = 'Vom Redakteur geändert';
		return $type;
	}, [] );
	$typeBefore = \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] );
	check( 'activating again leaves the type as it is - an edited section stays and nothing is added twice', \Nino\Features::activate( $appData, 'mailer' ) === true
		&& \Nino\Filesystem::getFileContent( $appData, '/elements/privacy.php', [] ) === $typeBefore );
}
check( 'the settings answer their defaults', \Nino\Features::settings( $appData, 'mailer' ) === [
	'host' => '', 'port' => 0, 'encryption' => 'starttls', 'username' => '', 'password' => '',
	'from' => '', 'fromName' => '', 'timeout' => 15, 'verify' => true,
] );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the transport under \\Nino\\Mail::TRANSPORT', isset( $appData['./nino/callbacks'][ \Nino\Mail::TRANSPORT ] ) === true );

echo "\n";


// --- The port, from the encryption -----------------------------------------

echo "Modules\\Mailer::port - 0 is what the encryption says, any other number is used as it is\n";

check( 'port 0 with STARTTLS is 587, with TLS from the start 465, with none 25',
	\Nino\Modules\Mailer::port( [ 'port' => 0, 'encryption' => 'starttls' ] ) === 587
	&& \Nino\Modules\Mailer::port( [ 'port' => 0, 'encryption' => 'tls' ] ) === 465
	&& \Nino\Modules\Mailer::port( [ 'port' => 0, 'encryption' => 'none' ] ) === 25 );
check( 'an explicit port wins over the encryption, a mismatch included', \Nino\Modules\Mailer::port( [ 'port' => 2525, 'encryption' => 'tls' ] ) === 2525
	&& \Nino\Modules\Mailer::port( [ 'port' => 587, 'encryption' => 'tls' ] ) === 587 );
check( 'the settings as they are stored resolve the same way - nothing saved is port 0, so STARTTLS, so 587', \Nino\Modules\Mailer::port( \Nino\Features::settings( $appData, 'mailer' ) ) === 587 );

/*	The Features form posts every field, so a project that ever saved this
	feature's settings holds an explicit 587 whatever it chose, and "from the
	encryption" would never reach it. upgrade() sets a stored port that is its
	encryption's own standard one to 0 - the same port, from then on following
	the encryption - and leaves any other number alone	*/
$storedPort = static function( array &$appData ): int {
	return (int) \Nino\Features::settings( $appData, 'mailer' )['port'];
};
$upgraded = static function( array $stored ) use ( &$appData, $storedPort ): int {
	\Nino\Features::saveSettings( $appData, 'mailer', array_merge( [ 'port' => '587', 'encryption' => 'starttls' ], $stored ) );
	$answer = \Nino\Modules\Mailer::upgrade( $appData, '1.0.0' );
	return $answer === true ? $storedPort( $appData ) : -1;
};

check( 'upgrade() turns a stored 587 with STARTTLS into 0', $upgraded( [ 'port' => '587', 'encryption' => 'starttls' ] ) === 0 );
check( '...a stored 465 with TLS from the start, and a 25 with none, the same', $upgraded( [ 'port' => '465', 'encryption' => 'tls' ] ) === 0 && $upgraded( [ 'port' => '25', 'encryption' => 'none' ] ) === 0 );
check( '...and leaves 2525 alone, whatever the encryption', $upgraded( [ 'port' => '2525', 'encryption' => 'starttls' ] ) === 2525 );
check( '...and 587 with TLS from the start, which is no standard pair and may be on purpose', $upgraded( [ 'port' => '587', 'encryption' => 'tls' ] ) === 587 );
check( 'it is idempotent: what it migrated, it leaves at 0', $upgraded( [ 'port' => '587', 'encryption' => 'starttls' ] ) === 0 && \Nino\Modules\Mailer::upgrade( $appData, '1.0.0' ) === true && $storedPort( $appData ) === 0 );
check( '...and touches nothing else it stored', ( static function() use ( &$appData ): bool {
	\Nino\Features::saveSettings( $appData, 'mailer', [ 'host' => 'smtp.example.com', 'username' => 'u', 'from' => 'a@example.org', 'port' => '587', 'encryption' => 'starttls' ] );
	\Nino\Modules\Mailer::upgrade( $appData, '1.0.0' );
	$settings = \Nino\Features::settings( $appData, 'mailer' );
	return $settings['host'] === 'smtp.example.com' && $settings['username'] === 'u' && $settings['from'] === 'a@example.org' && $settings['encryption'] === 'starttls' && $settings['port'] === 0;
} )() );

/*	The way it runs for a project: an older version on record, then the
	Features panel's update, which is activate() once more. The hook is not
	called directly here	*/
\Nino\Features::saveSettings( $appData, 'mailer', [ 'host' => 'smtp.example.com', 'password' => 'secret', 'port' => '587', 'encryption' => 'starttls' ] );
$appData[ \Nino\Features::STATE_KEY ]['mailer']['version'] = '0.0.1';
\Nino\AppData::writeContentData( $appData, [ \Nino\Features::STATE_KEY ] );
unset( $appData['./nino/features/all'] );
check( 'the older version is on record, so an update is pending', \Nino\Features::get( $appData, 'mailer' )['update'] === true );
check( 'activating it again runs the upgrade: the port is 0, host and password are kept, the new version is recorded', \Nino\Features::activate( $appData, 'mailer' ) === true
	&& $storedPort( $appData ) === 0 && \Nino\Features::settings( $appData, 'mailer' )['host'] === 'smtp.example.com' && \Nino\Features::settings( $appData, 'mailer' )['password'] === 'secret'
	&& \Nino\Features::get( $appData, 'mailer' )['installed'] === $manifest['version'] );

\Nino\Features::saveSettings( $appData, 'mailer', [ 'host' => '', 'username' => '', 'from' => '', 'port' => '0', 'encryption' => 'starttls' ] );

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
/*	Not quoted: an encoded word is not a quoted-string and may not stand
	inside one (RFC 2047 section 5), so a reader that takes the quotes at
	their word shows the site owner "=?UTF-8?B?Q2Fmw6kgTmluMg==?=". A name
	that needs no encoding keeps its quotes, which is what lets it carry a
	comma or a full stop	*/
check( 'the From: header uses the configured address and RFC 2047-encodes the non-ascii display name, unquoted',
	preg_match( '/^From: (=\?UTF-8\?B\?[A-Za-z0-9+\/=]+\?=) <no-reply@example\.org>\r$/m', $data, $m ) === 1
	&& mb_decode_mimeheader( $m[1] ) === 'Café Nino' );

echo "\n";


// --- A subject longer than one encoded word may be --------------------------

echo "A long non-ascii subject - RFC 2047 words, folded the way the kernel folds\n";

resetMailerRateLimit( $appData );
resetMailerLog( $server );

/*	An encoded word may be 75 characters and no more (RFC 2047 section 2), and
	a subject this long is several of them. \Nino\Mail::send() encodes a
	subject with mb_encode_mimeheader(), which splits and folds; this used to
	base64 the whole value into one word of whatever length came out, so a
	subject somebody wrote in German went onto the wire as a single header line
	of 148 characters - past what an encoded word may be and, for a longer one,
	past what a header line may be	*/
$longSubject = 'Grüße aus München: der monatliche Rundbrief über Bücher, Größen und Straßenbahnen im Frühjahr';

check( 'a long non-ascii subject sends', \Nino\Mail::send( $appData, 'to@example.org', $longSubject, '<p>Hello</p>', '' ) === true );

$longData	= (string) ( waitForMailerLog( $server )['data'] ?? '' );
// The Subject line and everything folded under it, which is every following
// line that begins with whitespace
$subjectLines = preg_match( '/^Subject: [^\r\n]*(?:\r\n[ \t][^\r\n]*)*/m', $longData, $m ) === 1
	? preg_split( '/\r\n/', $m[0] ) ?: []
	: [];

check( 'the subject is encoded exactly the way \Nino\Mail::send() encodes one, which is what this feature claims to do',
	str_contains( $longData, 'Subject: '. mb_encode_mimeheader( $longSubject, 'UTF-8', 'B' ). "\r\n" ) === true );
check( '...so it stands on several lines rather than on one long one', count( $subjectLines ) > 1 );
/*	75 is the whole of an encoded word, delimiters included. The first line is
	81 characters all the same, because mb_encode_mimeheader() is not told that
	"Subject: " stands in front of it - the kernel does not tell it either,
	since mail() puts that prefix on afterwards, and being the same as the
	kernel is what this is measured against	*/
check( '...as several encoded words, none over the 75 characters RFC 2047 allows one',
	preg_match_all( '/=\?[^?]+\?[BbQq]\?[^?]*\?=/', implode( '', $subjectLines ), $words ) > 1
	&& array_filter( $words[0], static fn( string $word ): bool => strlen( $word ) > 75 ) === [] );
check( '...and the whole of it decodes back to what was sent',
	mb_decode_mimeheader( substr( implode( "\r\n", $subjectLines ), strlen( 'Subject: ' ) ) ) === $longSubject );

echo "\n";


// --- From resolution: the kernel's sender wins over the setting ------------

echo "From resolution\n";

// The kernel's envelope sender, set the way the kernel reads it: the
// '[[/project/mail/address/envelope]]' textfill, which Nino 1.4 has and this feature needs
\Nino\Html::addFills( $appData, [ '[[/project/mail/address/envelope]]' => 'kernel-sender@example.org' ], '*' );
resetMailerRateLimit( $appData );
resetMailerLog( $server );
check( 'a send with a kernel sender still succeeds', \Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' ) === true );

$kernelSenderSession = waitForMailerLog( $server );
$kernelSenderData = (string) ( $kernelSenderSession['data'] ?? '' );
$kernelSenderCommands = $kernelSenderSession['commands'] ?? [];
check( 'MAIL FROM uses the kernel\'s own sender, not the setting', in_array( 'MAIL FROM:<kernel-sender@example.org>', $kernelSenderCommands, true ) === true );
check( 'the header block\'s own From: is kept, and Mailer adds none of its own', substr_count( $kernelSenderData, "\r\nFrom:" ) === 1
	&& str_contains( $kernelSenderData, "From: kernel-sender@example.org\r\n" ) === true );

\Nino\Html::addFills( $appData, [ '[[/project/mail/address/envelope]]' => '' ], '*' );

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

/*	A pair that does not go together - TLS from the start on 587, the STARTTLS
	port - gets a sentence that says so. The reason is keyed on the pair, not on
	how the send failed: STARTTLS against 465 connects fine and then waits for
	a greeting that never comes, so there is nothing in that failure that says
	why. A mailbox listening on 587 here would make this not a dead port, so
	it is skipped where there is one	*/
$probe = @stream_socket_client( 'tcp://127.0.0.1:587', $probeErrno, $probeErrstr, 1 );
$probe465 = @stream_socket_client( 'tcp://127.0.0.1:465', $probeErrno, $probeErrstr, 1 );
if( $probe !== false || $probe465 !== false ) {
	foreach( [ $probe, $probe465 ] as $open )
		if( $open !== false )
			fclose( $open );
	echo "  --  - something listens on 127.0.0.1:587 or :465 here: the hint check was NOT run\n";
} else {
	\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => '587', 'encryption' => 'tls' ] );
	resetMailerRateLimit( $appData );
	ninoWarnings();
	$hintResult = \Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' );
	$hintReason = (string) ( $appData['./mailer/last'] ?? '' );
	check( 'TLS from the start on port 587, nothing listening, fails and says why the pair is wrong', $hintResult === false
		&& str_starts_with( $hintReason, 'could not connect' ) === true && str_contains( $hintReason, 'hint: port 587 speaks STARTTLS' ) === true );

	\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => '465', 'encryption' => 'starttls' ] );
	resetMailerRateLimit( $appData );
	\Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' );
	check( 'STARTTLS on port 465 gets the other sentence', str_contains( (string) ( $appData['./mailer/last'] ?? '' ), 'hint: port 465 speaks TLS from the start' ) === true );

	\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => '0', 'encryption' => 'starttls' ] );
	resetMailerRateLimit( $appData );
	\Nino\Mail::send( $appData, 'to@example.org', 'x', 'y', '' );
	check( 'a pair that is fine - port 0, so 587 with STARTTLS - gets none', str_contains( (string) ( $appData['./mailer/last'] ?? '' ), 'hint:' ) === false );
	ninoWarnings();
}

\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => (string) $server['port'], 'encryption' => 'none' ] );

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

/*	The workbench renders a head over every pane but the Dashboard and names
	the panel in it (Nino.adminUi.panelHead() in the kernel), so the screen
	starts with what it is about rather than with the panel's name a second
	time, a line under the first. Read off the script, since this suite has
	no dom to draw it in: init(), which draws the whole screen, from its own
	line to the one that closes it	*/
$screen = preg_match( '/^\t\tinit : function\(\) \{\n(.*?)^\t\t\},$/ms',
	(string) file_get_contents( __DIR__. '/../assets/admin.js' ), $method ) === 1 ? $method[1] : '';
check( 'the screen draws no heading of its own - the head over the pane names the panel',
	$screen !== '' && preg_match( '/createElement\(\s*\'h[1-6]\'\s*\)/', $screen ) === 0 );
check( '...and the first line it draws under that head is its hint',
	preg_match( '/\bappendChild\(\s*(\w+)\s*\)/', $screen, $first ) === 1
	&& str_contains( $screen, $first[1]. '.className = \'nino-admin-hint\'' ) === true );

/*	The failures so far are in the ring file; the panel's own checks start
	from an empty one, so what it lists is what these checks did	*/
$ringFile = \Nino\Filesystem::path( $appData, '/data/mailer.php' );
check( 'every failure above was kept in the ring file the manifest declares, five at most',
	is_file( $ringFile ) === true && count( \Nino\Modules\Mailer::errors( $appData ) ) === 5 );
unlink( $ringFile );
unset( $appData['./nino/filesystem/cache']['/data/mailer.php'] );

[ $statusCode, $statusBody ] = callAdminPost( $appData, 'mailer/status' );
check( 'mailer/status answers host, the port a send really uses, encryption, the address to test with and the last errors - and never the password or username', $statusCode === 200
	&& $statusBody === [ 'host' => '127.0.0.1', 'port' => $server['port'], 'encryption' => 'none', 'testTo' => 'admin@example.com', 'errors' => [] ]
	&& array_key_exists( 'password', $statusBody ) === false && array_key_exists( 'username', $statusBody ) === false );

/*	The port the status line names is the one a send connects to: port 0 is
	what the encryption says	*/
\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => '0', 'encryption' => 'tls' ] );
[ , $resolvedBody ] = callAdminPost( $appData, 'mailer/status' );
check( 'with port 0 the status line shows the one the encryption names', ( $resolvedBody['port'] ?? null ) === 465 );
\Nino\Features::saveSettings( $appData, 'mailer', [ 'port' => (string) $server['port'], 'encryption' => 'none' ] );

// The address the test mail is offered to: the signed-in account's own while
// it is an address, the From address after that, none at all otherwise
$account = $appData['./nino/auth/current'];
$appData['./nino/auth/current']['mail'] = 'not-an-address';
[ , $fallbackBody ] = callAdminPost( $appData, 'mailer/status' );
\Nino\Features::saveSettings( $appData, 'mailer', [ 'from' => '' ] );
[ , $noneBody ] = callAdminPost( $appData, 'mailer/status' );
\Nino\Features::saveSettings( $appData, 'mailer', [ 'from' => 'no-reply@example.org' ] );
$appData['./nino/auth/current'] = $account;
check( 'the address to test with is the From address where the account\'s own is none', ( $fallbackBody['testTo'] ?? null ) === 'no-reply@example.org' );
check( '...and nothing where neither is an address', ( $noneBody['testTo'] ?? null ) === '' );

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

[ , $afterRefusal ] = callAdminPost( $appData, 'mailer/status' );
check( 'the refusal is the newest of the errors the panel lists, with a date and without a password', count( $afterRefusal['errors'] ) === 1
	&& str_contains( $afterRefusal['errors'][0]['reason'], 'mailbox unavailable' ) === true
	&& preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $afterRefusal['errors'][0]['date'] ) === 1
	&& str_contains( json_encode( $afterRefusal ). (string) file_get_contents( $ringFile ), 's3cret' ) === false );

// Newest first, and a ring: the sixth failure pushes the first one out
for( $i = 2; $i <= 6; $i++ ) {
	resetMailerRateLimit( $appData );
	callAdminPost( $appData, 'mailer/test', [ 'to' => 'refused'. $i. '@example.org' ] );
}
$ring = \Nino\Modules\Mailer::errors( $appData );
check( 'the list is capped at five, the oldest one gone', count( $ring ) === 5 );
check( '...newest first', array_column( $ring, 'date' ) === ( static function( array $dates ): array { rsort( $dates ); return $dates; } )( array_column( $ring, 'date' ) ) );
check( 'a delivered test mail adds nothing to it', ( static function() use ( &$appData, $server ): bool {
	setMailerControl( $server, [] );
	resetMailerRateLimit( $appData );
	$before = \Nino\Modules\Mailer::errors( $appData );
	$code = callAdminPost( $appData, 'mailer/test', [ 'to' => 'fine@example.org' ] )[0];
	return $code === 200 && \Nino\Modules\Mailer::errors( $appData ) === $before;
} )() );

// A file that is not what the class wrote is an empty list, not a broken panel
\Nino\Filesystem::putFileContent( $appData, '/data/mailer.php', [ 'junk', [ 'date' => 1, 'reason' => [] ], [ 'date' => '2026-01-01 00:00:00', 'reason' => 'kept' ] ] );
check( 'a ring file with entries of another shape lists the ones that are entries', \Nino\Modules\Mailer::errors( $appData ) === [ [ 'date' => '2026-01-01 00:00:00', 'reason' => 'kept' ] ] );

// ...and a file that holds no array at all is an empty list as well - the
// panel answers, and the next failure is recorded instead of lost
\Nino\Filesystem::putFileContent( $appData, '/data/mailer.php', 'not a list' );
ninoWarnings();
check( 'a ring file that holds no array lists nothing and raises nothing', \Nino\Modules\Mailer::errors( $appData ) === [] && ninoWarnings() === [] );
[ $brokenCode, $brokenBody ] = callAdminPost( $appData, 'mailer/status' );
check( '...mailer/status still answers, with no errors', $brokenCode === 200 && ( $brokenBody['errors'] ?? null ) === [] );
setMailerControl( $server, [ 'failRcpt' => true ] );
resetMailerRateLimit( $appData );
callAdminPost( $appData, 'mailer/test', [ 'to' => 'refused-after-junk@example.org' ] );
$recorded = \Nino\Modules\Mailer::errors( $appData );
check( '...and the next failure is recorded over it', count( $recorded ) === 1 && str_contains( $recorded[0]['reason'], 'mailbox unavailable' ) === true );
\Nino\Filesystem::putFileContent( $appData, '/data/mailer.php', [] );
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

/*	The transport reads its settings once. Features::setting() answers one
	name by building every value the manifest declares - it reads the feature
	and validates each stored value against its schema - so the nine names
	this transport needs used to cost nine of those per mail. Nothing next to
	an smtp round trip (0.0104 ms against 0.0012), but nine reads of one thing
	is nine places for the ninth to be forgotten	*/
$transportSource = (string) file_get_contents( __DIR__. '/../Mailer.php' );
$transportBody	 = preg_match( '/function callbackSend\(.*?\n\t\t\}\n/s', $transportSource, $transport ) === 1 ? $transport[0] : '';
check( 'the transport takes its settings in one read', $transportBody !== '' && substr_count( $transportBody, '\Nino\Features::settings(' ) === 1
	&& substr_count( $transportSource, '\Nino\Features::setting(' ) === 0 );

// --- The panel's script, where node is on the path ------------------------------
//
// mailer-js-smoke.js beside this file draws the screen over a dom stand-in;
// this suite runs it too where node is on the path, so bin/check.sh and CI
// cover both halves in one go
$jsTest	= __DIR__. '/mailer-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: mailer-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'mailer-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
