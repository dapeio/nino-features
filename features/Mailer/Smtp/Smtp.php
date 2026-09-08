<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Mailer\\Smtp		A minimal, pure-php SMTP client - see Mailer.php beside this
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Mailer {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Mailer\Smtp				One SMTP session over a plain stream socket: connect
	 *										(plain, or tls:// for implicit TLS), EHLO with a HELO
	 *										fallback, STARTTLS when asked (refusing to go on
	 *										unencrypted if it fails), AUTH PLAIN or LOGIN depending
	 *										on what EHLO advertised, MAIL FROM, one RCPT TO per
	 *										recipient, DATA and QUIT. Everything Mailer::callbackSend()
	 *										already resolved - the config, the envelope sender, the
	 *										recipient list, the finished CRLF message - arrives as
	 *										plain arguments; this class knows nothing about $appData,
	 *										settings or the kernel's mail array, which is what makes it
	 *										testable against a fake server with nothing else booted.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Smtp {

		/**
		 *	Send one message over one SMTP session. Never throws - every
		 *	failure comes back as { sent: false, reason: '...' }, the reason
		 *	naming what went wrong (a connect failure, a refused command with
		 *	the server's own reply line) but never the password.
		 *
		 *	@param		array			$config				{ host, port, encryption ('starttls'|'tls'|'none'), username, password, timeout, verify }
		 *	@param		string		$envelopeFrom	MAIL FROM address
		 *	@param		array			$recipients		RCPT TO addresses, one per entry
		 *	@param		string		$payload			The complete CRLF message: headers, blank line, dot-stuffed body, ending "\r\n.\r\n"
		 *
		 *	@return 	array										{ sent: bool, reason: string|null }
		 */
		public static function send( array $config, string $envelopeFrom, array $recipients, string $payload ): array {

			$stream = self::_connect( $config );

			if( is_string( $stream ) === true )
				return [ 'sent' => false, 'reason' => $stream ];

			try {

				$greeting = self::_readReply( $stream );
				if( self::_ok( $greeting ) === false )
					return [ 'sent' => false, 'reason' => self::_reason( $greeting, 'no greeting from the server' ) ];

				$localName = self::_localName();

				$capabilities = self::_ehlo( $stream, $localName );
				if( is_string( $capabilities ) === true )
					return [ 'sent' => false, 'reason' => $capabilities ];

				if( $config['encryption'] === 'starttls' ) {

					$upgraded = self::_startTls( $stream, $localName );
					if( is_string( $upgraded ) === true )
						return [ 'sent' => false, 'reason' => $upgraded ];

					$capabilities = $upgraded;
				}

				if( $config['username'] !== '' ) {

					$authFailure = self::_authenticate( $stream, $capabilities, $config['username'], $config['password'] );
					if( $authFailure !== null )
						return [ 'sent' => false, 'reason' => $authFailure ];
				}

				$mailFrom = self::_command( $stream, 'MAIL FROM:<'. $envelopeFrom. '>' );
				if( self::_ok( $mailFrom ) === false )
					return [ 'sent' => false, 'reason' => self::_reason( $mailFrom, 'MAIL FROM refused' ) ];

				foreach( $recipients as $recipient ) {

					$rcpt = self::_command( $stream, 'RCPT TO:<'. $recipient. '>' );
					if( self::_ok( $rcpt ) === false )
						return [ 'sent' => false, 'reason' => self::_reason( $rcpt, 'RCPT TO <'. $recipient. '> refused' ) ];
				}

				$data = self::_command( $stream, 'DATA' );
				if( self::_ok( $data ) === false )
					return [ 'sent' => false, 'reason' => self::_reason( $data, 'DATA refused' ) ];

				if( @fwrite( $stream, $payload ) === false )
					return [ 'sent' => false, 'reason' => 'the connection closed while the message was sent' ];

				$queued = self::_readReply( $stream );
				if( self::_ok( $queued ) === false )
					return [ 'sent' => false, 'reason' => self::_reason( $queued, 'the message was refused' ) ];

				// Best effort - the mail is already queued by the time QUIT
				// is sent, so a bad or missing reply here does not undo that
				self::_command( $stream, 'QUIT' );

				return [ 'sent' => true, 'reason' => null ];

			} finally {

				if( is_resource( $stream ) === true )
					fclose( $stream );
			}
		}

		/**
		 *	@param		array			$config
		 *
		 *	@return 	mixed										The connected stream, or a string naming why it could not connect
		 */
		private static function _connect( array $config ): mixed {

			$context = stream_context_create( [
				'ssl' => [
					'verify_peer'				=> $config['verify'],
					'verify_peer_name'	=> $config['verify'],
					'allow_self_signed'	=> $config['verify'] !== true,
				],
			] );

			$scheme	= $config['encryption'] === 'tls' ? 'tls://' : 'tcp://';
			$target	= $scheme. $config['host']. ':'. $config['port'];

			$stream = @stream_socket_client( $target, $errno, $errstr, $config['timeout'], STREAM_CLIENT_CONNECT, $context );

			if( $stream === false )
				return 'could not connect to '. $config['host']. ':'. $config['port']. ( $errstr !== '' ? ' - '. $errstr : '' );

			stream_set_timeout( $stream, $config['timeout'] );

			return $stream;
		}

		/**
		 *	EHLO, falling back to HELO for a server that does not know it -
		 *	either way the connection is left ready for MAIL FROM
		 *
		 *	@param		mixed			$stream
		 *	@param		string		$localName
		 *
		 *	@return 	array|string						Advertised capabilities ('auth' => [ 'PLAIN', ... ]), or the failure reason
		 */
		private static function _ehlo( mixed $stream, string $localName ): array|string {

			$reply = self::_command( $stream, 'EHLO '. $localName );

			if( is_string( $reply ) === true )
				return $reply;

			if( self::_ok( $reply ) === true )
				return self::_capabilities( $reply['lines'] );

			$helo = self::_command( $stream, 'HELO '. $localName );

			if( is_string( $helo ) === true )
				return $helo;

			if( self::_ok( $helo ) === true )
				return [];

			return self::_reason( $reply, 'neither EHLO nor HELO was accepted' );
		}

		/**
		 *	STARTTLS, then EHLO again over the now-encrypted stream - a
		 *	server may only advertise AUTH after the upgrade. A failure here
		 *	means the caller gives up rather than falling back to plaintext.
		 *
		 *	@param		mixed			$stream
		 *	@param		string		$localName
		 *
		 *	@return 	array|string						Capabilities after the upgrade, or the failure reason
		 */
		private static function _startTls( mixed $stream, string $localName ): array|string {

			$reply = self::_command( $stream, 'STARTTLS' );

			if( self::_ok( $reply ) === false )
				return self::_reason( $reply, 'STARTTLS refused' );

			if( @stream_socket_enable_crypto( $stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT ) !== true )
				return 'STARTTLS negotiation failed - refusing to send unencrypted';

			return self::_ehlo( $stream, $localName );
		}

		/**
		 *	AUTH PLAIN when the server advertised it (one round trip), else
		 *	AUTH LOGIN, else a failure naming that neither is offered
		 *
		 *	@param		mixed			$stream
		 *	@param		array			$capabilities	From _capabilities()
		 *	@param		string		$username
		 *	@param		string		$password
		 *
		 *	@return 	string|null							Null on success, else the failure reason - never the password
		 */
		private static function _authenticate( mixed $stream, array $capabilities, string $username, string $password ): ?string {

			$methods = $capabilities['auth'] ?? [];

			if( in_array( 'PLAIN', $methods, true ) === true ) {

				$reply = self::_command( $stream, 'AUTH PLAIN '. base64_encode( "\0". $username. "\0". $password ) );

				return self::_ok( $reply ) === true ? null : self::_reason( $reply, 'AUTH PLAIN refused' );
			}

			if( in_array( 'LOGIN', $methods, true ) === true ) {

				$reply = self::_command( $stream, 'AUTH LOGIN' );
				if( self::_ok( $reply ) === false )
					return self::_reason( $reply, 'AUTH LOGIN refused' );

				$userReply = self::_command( $stream, base64_encode( $username ) );
				if( self::_ok( $userReply ) === false )
					return self::_reason( $userReply, 'AUTH LOGIN refused the username' );

				$passReply = self::_command( $stream, base64_encode( $password ) );
				return self::_ok( $passReply ) === true ? null : self::_reason( $passReply, 'AUTH LOGIN refused the password' );
			}

			return 'the server does not advertise AUTH PLAIN or LOGIN';
		}

		/**
		 *	Every "AUTH ..." capability line EHLO answered with, uppercased
		 *
		 *	@param		array			$lines				Raw EHLO reply lines, eg. "250-AUTH PLAIN LOGIN"
		 *
		 *	@return 	array										{ auth: [ 'PLAIN', 'LOGIN', ... ] }
		 */
		private static function _capabilities( array $lines ): array {

			$capabilities = [ 'auth' => [] ];

			foreach( $lines as $line ) {

				$line = (string) preg_replace( '/^\d{3}[ -]/', '', $line );

				if( preg_match( '/^AUTH\s+(.+)$/i', trim( $line ), $m ) === 1 )
					$capabilities['auth'] = array_map( 'strtoupper', preg_split( '/\s+/', trim( $m[1] ) ) ?: [] );
			}

			return $capabilities;
		}

		/**
		 *	Write one command line and read its reply
		 *
		 *	@param		mixed			$stream
		 *	@param		string		$line					Without the trailing CRLF
		 *
		 *	@return 	array|string						See _readReply()
		 */
		private static function _command( mixed $stream, string $line ): array|string {

			if( @fwrite( $stream, $line. "\r\n" ) === false )
				return 'the connection closed while sending "'. $line. '"';

			return self::_readReply( $stream );
		}

		/**
		 *	Read one reply, following every "code-text" continuation line
		 *	through to the final "code text" one
		 *
		 *	@param		mixed			$stream
		 *
		 *	@return 	array|string						{ code: int, lines: string[] }, or a string naming a timeout/closed connection/unparseable line
		 */
		private static function _readReply( mixed $stream ): array|string {

			$code	= 0;
			$lines	= [];

			while( true ) {

				$line = fgets( $stream, 2048 );

				if( $line === false ) {
					$meta = stream_get_meta_data( $stream );
					return $meta['timed_out'] === true ? 'timed out waiting for the server' : 'the connection closed while waiting for a reply';
				}

				$line = rtrim( $line, "\r\n" );

				if( preg_match( '/^(\d{3})([ -]?)(.*)$/', $line, $m ) !== 1 )
					return 'unexpected reply: "'. $line. '"';

				$code			= (int) $m[1];
				$lines[]	= $line;

				if( $m[2] !== '-' )
					break;
			}

			return [ 'code' => $code, 'lines' => $lines ];
		}

		/**
		 *	@param		array|string	$reply			From _readReply()/_command()
		 *
		 *	@return 	bool											Whether it is a reply, and a 2xx/3xx one
		 */
		private static function _ok( array|string $reply ): bool {

			return is_array( $reply ) === true && $reply['code'] >= 200 && $reply['code'] < 400;
		}

		/**
		 *	@param		array|string	$reply			From _readReply()/_command()
		 *	@param		string				$context		What was being attempted
		 *
		 *	@return 	string										"$context: <the server's own last line>", or "$context: <the connection failure>"
		 */
		private static function _reason( array|string $reply, string $context ): string {

			if( is_string( $reply ) === true )
				return $context. ': '. $reply;

			return $context. ': '. (string) end( $reply['lines'] );
		}

		/**
		 *	The name this client introduces itself with in EHLO/HELO - the
		 *	local host's own name is enough, no project state is involved
		 *
		 *	@return 	string
		 */
		private static function _localName(): string {

			$name = gethostname();

			return ( is_string( $name ) === true && $name !== '' ) ? $name : 'localhost';
		}
	}
}
