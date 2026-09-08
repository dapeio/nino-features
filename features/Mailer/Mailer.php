<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Mailer						see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Mailer						Delivers every mail \Nino\Mail::send() hands out over
	 *										SMTP instead of the server's mail() - registered under
	 *										\Nino\Mail::TRANSPORT (see Mail.php), so nothing outside
	 *										this feature has to know it exists. callbackSend() builds
	 *										the CRLF message and the envelope, and hands both to
	 *										Mailer\Smtp (Smtp.php beside this file), a small, pure-php
	 *										SMTP client that knows nothing about $appData or settings.
	 *										A host left empty leaves the mail untouched for mail() to
	 *										try - an operator who switches this on before configuring
	 *										it loses no mail. A failure is never thrown: it sets
	 *										'sent' to false, records why under './mailer/last' (for
	 *										the panel's test button) and logs it with
	 *										trigger_error(), the password never among the words.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Mailer {

		private const string FEATURE_KEY = 'mailer';

		/**
		 *	The /_admin screen this feature brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears in the
		 *	workbench exactly while this feature is active
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Mailer\Admin::class ];
		}

		/**
		 *	Register the SMTP transport under \Nino\Mail::TRANSPORT - every
		 *	mail \Nino\Mail::send() hands out passes through callbackSend()
		 *	before mail() ever runs
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, [ self::class, 'callbackSend' ] );
		}

		/**
		 *	The transport itself. Leaves 'sent' at null - passing the mail on
		 *	to the next transport, or finally to mail() - while the feature
		 *	has no host configured; otherwise sends over SMTP and sets 'sent'
		 *	to true on a 250 after DATA, false on any failure. Never throws.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$mail				(reference) { to, subject, body, replyTo, sender, headers, sent }, see \Nino\Mail::TRANSPORT
		 *
		 *	@return 	void
		 */
		public static function callbackSend( array &$appData, array &$mail ): void {

			$host = trim( (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'host', '' ) );

			// Not configured yet - leave 'sent' at null so \Nino\Mail::send()
			// falls through to mail() exactly as if this feature were not
			// active. Switching the feature on before filling in the host
			// must not turn into silently-missing mail.
			if( $host === '' )
				return;

			$config = [
				'host'				=> $host,
				'port'				=> (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'port', 587 ),
				'encryption'	=> (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'encryption', 'starttls' ),
				'username'		=> trim( (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'username', '' ) ),
				'password'		=> (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'password', '' ),
				'timeout'			=> (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'timeout', 15 ),
				'verify'			=> (bool) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'verify', true ),
			];

			$hasKernelFrom = (string) $mail['sender'] !== '';
			$fromAddress	 = $hasKernelFrom === true ? (string) $mail['sender'] : trim( (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'from', '' ) );

			if( $fromAddress === '' ) {
				$mail['sent'] = false;
				self::_fail( $appData, 'no From address is available - set "from" in the mailer settings, or /nino/mail/sender' );
				return;
			}

			$recipients = self::_recipients( (string) $mail['to'] );

			if( $recipients === [] ) {
				$mail['sent'] = false;
				self::_fail( $appData, 'no valid recipient in "'. $mail['to']. '"' );
				return;
			}

			$fromName	= trim( (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'fromName', '' ) );
			$payload	= self::_payload( $mail, $hasKernelFrom, $fromAddress, $fromName, $host );

			$result = \Nino\Modules\Mailer\Smtp::send( $config, $fromAddress, $recipients, $payload );

			$mail['sent'] = $result['sent'];

			if( $result['sent'] === false )
				self::_fail( $appData, (string) ( $result['reason'] ?? 'the mail could not be sent' ) );
		}

		/**
		 *	Record why the last send failed - the panel's test button reports
		 *	this - and log it, the password never among the words
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$reason
		 *
		 *	@return 	void
		 */
		private static function _fail( array &$appData, string $reason ): void {

			$appData['./mailer/last'] = $reason;

			trigger_error( 'Mailer: '. $reason, E_USER_WARNING );
		}

		/**
		 *	The kernel's 'to' as mail() itself would treat it: a plain,
		 *	comma-separated list of addresses, each RCPT TO'd on its own -
		 *	\Nino\Mail::send() only ever hands this a single validated
		 *	address, but a caller that puts several on the same line (as
		 *	mail() itself allows) gets them all delivered
		 *
		 *	@param		string		$to
		 *
		 *	@return 	array										Valid addresses, in order, without duplicates
		 */
		private static function _recipients( string $to ): array {

			$recipients = [];

			foreach( explode( ',', $to ) as $address ) {

				$address = trim( $address );

				if( $address !== '' && filter_var( $address, FILTER_VALIDATE_EMAIL ) !== false && in_array( $address, $recipients, true ) === false )
					$recipients[] = $address;
			}

			return $recipients;
		}

		/**
		 *	The complete CRLF message: Date, Message-ID, To, Subject (RFC 2047
		 *	when it is not plain ASCII), MIME-Version, then the kernel's own
		 *	header block verbatim, a From: of our own only when that block has
		 *	none, a blank line, the dot-stuffed body and the DATA terminator
		 *
		 *	@param		array 		$mail					{ to, subject, body, headers, ... }, see \Nino\Mail::TRANSPORT
		 *	@param		bool			$hasKernelFrom	Whether $mail['headers'] already carries a From: line
		 *	@param		string		$fromAddress	The envelope/header From address
		 *	@param		string		$fromName			The configured display name, '' for none
		 *	@param		string		$smtpHost			The configured SMTP host, for the Message-ID's right-hand side
		 *
		 *	@return 	string
		 */
		private static function _payload( array $mail, bool $hasKernelFrom, string $fromAddress, string $fromName, string $smtpHost ): string {

			$lines = [];
			$lines[] = 'Date: '. date( DATE_RFC2822 );
			$lines[] = 'Message-ID: <'. bin2hex( random_bytes( 16 ) ). '@'. self::_messageIdHost( $smtpHost ). '>';
			$lines[] = 'To: '. $mail['to'];
			$lines[] = 'Subject: '. self::_encodeHeaderValue( (string) $mail['subject'] );
			$lines[] = 'MIME-Version: 1.0';

			// $mail['headers'] is the block \Nino\Mail::send() would hand
			// mail() - which already starts with its own "MIME-Version: 1.0"
			// (see Mail.php) - so that one line is dropped here rather than
			// duplicated; everything else (Content-Type, From, Reply-To) is
			// kept verbatim
			foreach( preg_split( '/\r\n|\r|\n/', trim( (string) $mail['headers'] ) ) ?: [] as $headerLine )
				if( trim( $headerLine ) !== '' && preg_match( '/^MIME-Version:/i', trim( $headerLine ) ) !== 1 )
					$lines[] = $headerLine;

			if( $hasKernelFrom === false )
				$lines[] = 'From: '. self::_fromHeaderValue( $fromAddress, $fromName );

			return implode( "\r\n", $lines ). "\r\n\r\n". self::_dotStuff( (string) $mail['body'] ). "\r\n.\r\n";
		}

		/**
		 *	@param		string		$address
		 *	@param		string		$name					'' for none
		 *
		 *	@return 	string									"address", or "\"name\" <address>" with the name RFC 2047-encoded when it is not plain ASCII
		 */
		private static function _fromHeaderValue( string $address, string $name ): string {

			if( $name === '' )
				return $address;

			$quoted = mb_check_encoding( $name, 'ASCII' ) === true
				? str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $name )
				: self::_encodeHeaderValue( $name );

			return '"'. $quoted. '" <'. $address. '>';
		}

		/**
		 *	A header value as-is when it is plain ASCII, else RFC 2047
		 *	B-encoded UTF-8 - the same encoding \Nino\Mail::send() uses for the
		 *	subject when no transport takes the mail (mb_encode_mimeheader())
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function _encodeHeaderValue( string $value ): string {

			return mb_check_encoding( $value, 'ASCII' ) === true ? $value : '=?UTF-8?B?'. base64_encode( $value ). '?=';
		}

		/**
		 *	The right-hand side of a generated Message-ID - the configured
		 *	SMTP host with anything that is not hostname-shaped stripped, so
		 *	an operator-typed value can never end up somewhere it should not
		 *
		 *	@param		string		$smtpHost
		 *
		 *	@return 	string
		 */
		private static function _messageIdHost( string $smtpHost ): string {

			$host = preg_replace( '/[^A-Za-z0-9.\-]/', '', $smtpHost );

			return ( is_string( $host ) === true && $host !== '' ) ? $host : 'localhost';
		}

		/**
		 *	Dot-stuff a body for the DATA phase: a line starting with "."
		 *	gets another "." in front, and every line ending normalized to
		 *	CRLF first, whatever the body itself uses
		 *
		 *	@param		string		$body
		 *
		 *	@return 	string
		 */
		private static function _dotStuff( string $body ): string {

			$normalized = preg_replace( '/\r\n|\r|\n/', "\r\n", $body );
			$lines			= explode( "\r\n", is_string( $normalized ) ? $normalized : $body );

			foreach( $lines as &$line )
				if( str_starts_with( $line, '.' ) === true )
					$line = '.'. $line;
			unset( $line );

			return implode( "\r\n", $lines );
		}
	}
}
