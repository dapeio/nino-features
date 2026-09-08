<?php
declare(strict_types=1);

/**
 *	Nino
 *	fake-smtp-server.php	A minimal SMTP server for mailer-smoke.php - never
 *												sends or receives real mail, only proves the client
 *												(Mailer\Smtp) speaks the protocol correctly. Binds
 *												127.0.0.1 on a free port (port 0), prints the port on
 *												stdout once, then serves one session per accepted
 *												connection until killed. The control file is read
 *												fresh at the start of every session, so the already-
 *												running server can be told, between sends, to behave
 *												differently for the next connection: refuse RCPT (550),
 *												refuse AUTH (535), or advertise only AUTH LOGIN.
 *
 *	Usage: php fake-smtp-server.php <controlFile> <logFile>
 */

$controlFile	= $argv[1] ?? '';
$logFile			= $argv[2] ?? '';

$server = stream_socket_server( 'tcp://127.0.0.1:0', $errno, $errstr );
if( $server === false ) {
	fwrite( STDERR, 'could not bind: '. $errstr. "\n" );
	exit( 1 );
}

$name = stream_socket_get_name( $server, false );
$port = (int) substr( $name, (int) strrpos( $name, ':' ) + 1 );

echo $port. "\n";
fflush( STDOUT );

/**
 *	One SMTP session: greeting, EHLO/HELO, an optional STARTTLS handshake
 *	(acknowledged but not actually upgraded - not exercised by the smoke
 *	test), AUTH PLAIN or LOGIN, MAIL FROM, any number of RCPT TO, DATA and
 *	QUIT. Every line the client sends is recorded, and the DATA payload is
 *	captured byte for byte (not line by line) so the test can check its
 *	exact CRLF endings.
 *
 *	@param		resource	$conn
 *	@param		array			$control			{ failRcpt, failAuth, authMethods }
 *
 *	@return 	array										{ commands: string[], data: string }
 */
function fakeSmtpSession( $conn, array $control ): array {

	$commands = [];

	$write = static function( string $line ) use ( $conn ): void {
		fwrite( $conn, $line. "\r\n" );
	};

	$read = static function() use ( $conn, &$commands ): string|false {
		$line = fgets( $conn, 8192 );
		if( $line === false )
			return false;
		$line = rtrim( $line, "\r\n" );
		$commands[] = $line;
		return $line;
	};

	$authMethods	= (string) ( $control['authMethods'] ?? 'PLAIN LOGIN' );
	$data					= '';

	$write( '220 fake.smtp.test ESMTP ready' );

	while( true ) {

		$line = $read();
		if( $line === false )
			break;

		$upper = strtoupper( $line );

		if( str_starts_with( $upper, 'EHLO' ) === true ) {
			$write( '250-fake.smtp.test greets you' );
			$write( '250-8BITMIME' );
			$write( '250 AUTH '. $authMethods );
		} else if( str_starts_with( $upper, 'HELO' ) === true ) {
			$write( '250 fake.smtp.test greets you' );
		} else if( $upper === 'STARTTLS' ) {
			$write( '220 ready to start TLS' );
		} else if( $upper === 'AUTH LOGIN' ) {
			$write( '334 VXNlcm5hbWU6' );
			$read();
			$write( '334 UGFzc3dvcmQ6' );
			$read();
			$write( ( $control['failAuth'] ?? false ) === true ? '535 authentication failed' : '235 authentication successful' );
		} else if( str_starts_with( $upper, 'AUTH PLAIN' ) === true ) {
			$write( ( $control['failAuth'] ?? false ) === true ? '535 authentication failed' : '235 authentication successful' );
		} else if( str_starts_with( $upper, 'MAIL FROM:' ) === true ) {
			$write( '250 ok' );
		} else if( str_starts_with( $upper, 'RCPT TO:' ) === true ) {
			$write( ( $control['failRcpt'] ?? false ) === true ? '550 mailbox unavailable' : '250 ok' );
		} else if( $upper === 'DATA' ) {
			$write( '354 send the mail, end with <CRLF>.<CRLF>' );
			$data = fakeSmtpReadData( $conn );
			$write( '250 queued as fake-1' );
		} else if( $upper === 'QUIT' ) {
			$write( '221 bye' );
			break;
		} else {
			$write( '500 unrecognized command' );
		}
	}

	return [ 'commands' => $commands, 'data' => $data ];
}

/**
 *	Read the raw DATA payload up to and including the terminating
 *	"\r\n.\r\n" - byte for byte, not line by line, so whatever line endings
 *	the client actually used are preserved for the test to check
 *
 *	@param		resource	$conn
 *
 *	@return 	string
 */
function fakeSmtpReadData( $conn ): string {

	$buffer = '';

	while( str_ends_with( $buffer, "\r\n.\r\n" ) === false ) {

		$chunk = fread( $conn, 8192 );
		if( $chunk === false || $chunk === '' )
			break;

		$buffer .= $chunk;

		if( strlen( $buffer ) > 10 * 1024 * 1024 ) // never loop forever on a client that sends no terminator
			break;
	}

	return $buffer;
}

while( true ) {

	$conn = @stream_socket_accept( $server, -1 );
	if( $conn === false )
		continue;

	$control = [];
	if( $controlFile !== '' && is_file( $controlFile ) === true ) {
		$decoded = json_decode( (string) file_get_contents( $controlFile ), true );
		if( is_array( $decoded ) === true )
			$control = $decoded;
	}

	$session = fakeSmtpSession( $conn, $control );

	if( $logFile !== '' )
		file_put_contents( $logFile, json_encode( $session, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

	fclose( $conn );
}
