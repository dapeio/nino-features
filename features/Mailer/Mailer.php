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
	 *										the panel's test button), keeps the last five in
	 *										/data/mailer.php (for the panel's list of errors) and
	 *										logs it with trigger_error(), the password never among
	 *										the words. The sender of every mail - envelope and From -
	 *										is the setting "from" where it is set, the kernel's sender
	 *										only where it is not; a 5xx to MAIL FROM is explained.
	 *										The port is the setting's own, or - at 0,
	 *										what it is by default - the one the encryption names.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Mailer {

		private const string FEATURE_KEY = 'mailer';

		// The last failures, newest first - a ring of MAX_ERRORS entries, each
		// { date, reason }. The reason is what trigger_error() gets, and never
		// holds the password
		private const string ERRORS_PATH = '/data/mailer.php';

		private const int MAX_ERRORS = 5;

		// A reason is the server's own reply line, which has no length of its
		// own to speak of - a file that is written on every failure does
		private const int MAX_REASON = 300;

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
		 *	The port a send connects to: the setting's own number, or - while it
		 *	is 0, the default - the one the encryption names. 587 for STARTTLS,
		 *	465 for TLS from the start, 25 for none; any other number is used as
		 *	it is
		 *
		 *	@param		array 		$settings			The feature's settings, { port, encryption, ... }
		 *
		 *	@return 	int
		 */
		public static function port( array $settings ): int {

			$port = (int) ( $settings['port'] ?? 0 );

			if( $port > 0 )
				return $port;

			return match( (string) ( $settings['encryption'] ?? 'starttls' ) ) {
				'tls'		=> 465,
				'none'	=> 25,
				default	=> 587,
			};
		}

		/**
		 *	The last failures the transport recorded, newest first - at most
		 *	five, { date, reason } each. A file that is not that, or that does
		 *	not parse, is an empty list: nothing here may take the panel down
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function errors( array &$appData ): array {

			$errors = [];

			try {
				$stored = \Nino\Filesystem::getFileContent( $appData, self::ERRORS_PATH, [] );
			} catch( \Throwable $e ) {
				return [];
			}

			foreach( is_array( $stored ) === true ? $stored : [] as $entry )
				if( is_array( $entry ) === true && is_string( $entry['date'] ?? null ) === true && is_string( $entry['reason'] ?? null ) === true )
					$errors[] = [ 'date' => $entry['date'], 'reason' => $entry['reason'] ];

			return array_slice( $errors, 0, self::MAX_ERRORS );
		}

		/**
		 *	Migrate a stored port that only says what the encryption already
		 *	says. The Features form posts every field, so a project that ever
		 *	saved this feature's settings holds an explicit 587 whatever it
		 *	chose - and "from the encryption", which is what 0 means, would
		 *	never reach it. A port that is the encryption's own standard one is
		 *	set to 0, which sends to the same port and follows the encryption
		 *	from then on; any other number stays. Idempotent, so it needs no
		 *	version guard
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					The version that was recorded
		 *
		 *	@return 	bool
		 */
		public static function upgrade( array &$appData, string $from ): bool {

			$settings = \Nino\Features::settings( $appData, self::FEATURE_KEY );
			$port			= (int) ( $settings['port'] ?? 0 );

			if( $port === 0 || $port !== self::port( [ 'port' => 0, 'encryption' => $settings['encryption'] ?? 'starttls' ] ) )
				return true;

			return \Nino\Features::saveSettings( $appData, self::FEATURE_KEY, [ 'port' => 0 ] ) === [];
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

			/*	The whole settings array once, not nine reads of it.
				Features::setting() answers one name by building every value the
				manifest declares - it reads the feature, then validates each
				stored value against its schema - so asking nine times did that
				nine times over for one mail. Measured: 0.0104 ms against
				0.0012, which is nothing beside an smtp round trip; it is the
				shape that is wrong rather than the cost	*/
			$settings = \Nino\Features::settings( $appData, self::FEATURE_KEY );
			$host			= trim( (string) ( $settings['host'] ?? '' ) );

			// Not configured yet - leave 'sent' at null so \Nino\Mail::send()
			// falls through to mail() exactly as if this feature were not
			// active. Switching the feature on before filling in the host
			// must not turn into silently-missing mail.
			if( $host === '' )
				return;

			$config = [
				'host'				=> $host,
				'port'				=> self::port( $settings ),
				'encryption'	=> (string) ( $settings['encryption'] ?? 'starttls' ),
				'username'		=> trim( (string) ( $settings['username'] ?? '' ) ),
				'password'		=> (string) ( $settings['password'] ?? '' ),
				'timeout'			=> (int) ( $settings['timeout'] ?? 15 ),
				'verify'			=> (bool) ( $settings['verify'] ?? true ),
			];

			/*	The setting is the sender of every mail where it is set: an
				authenticated server lets an account send as its own mailbox
				(or the addresses the provider names), and the site's own
				address - which the kernel hands over whenever the site has
				one - is rarely that. Where the setting is empty the kernel's
				sender is the envelope and the header, as it always was	*/
			$ownFrom			= trim( (string) ( $settings['from'] ?? '' ) );
			$fromAddress	= $ownFrom !== '' ? $ownFrom : (string) $mail['sender'];

			if( $fromAddress === '' ) {
				$mail['sent'] = false;
				self::_fail( $appData, 'no From address is available - set "from" in the mailer settings, or the [[/project/mail/address/envelope]] textfill' );
				return;
			}

			$recipients = self::_recipients( (string) $mail['to'] );

			if( $recipients === [] ) {
				$mail['sent'] = false;
				self::_fail( $appData, 'no valid recipient in "'. $mail['to']. '"' );
				return;
			}

			$fromName	= trim( (string) ( $settings['fromName'] ?? '' ) );
			$payload	= self::_payload( $mail, $ownFrom !== '', $fromAddress, $fromName, $host );

			$result = \Nino\Modules\Mailer\Smtp::send( $config, $fromAddress, $recipients, $payload );

			$mail['sent'] = $result['sent'];

			if( $result['sent'] === false )
				self::_fail(
					$appData,
					(string) ( $result['reason'] ?? 'the mail could not be sent' ),
					self::_portHint( $config['port'], $config['encryption'] ). ( ( $result['senderRefused'] ?? false ) === true ? self::_senderHint( $fromAddress ) : '' )
				);
		}

		/**
		 *	Record why the last send failed - the panel's test button reports
		 *	this - keep it among the last five in /data/mailer.php for the panel
		 *	to list, and log it, the password never among the words. A file
		 *	that cannot be written is not a reason to fail the send twice: it is
		 *	skipped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$reason
		 *	@param		string		$hint					Appended to the reason; kept whole in the panel's list, where the reason is cut on its own before it
		 *
		 *	@return 	void
		 */
		private static function _fail( array &$appData, string $reason, string $hint = '' ): void {

			// A server's reply or a connect error is not ours: one line, no control byte, and the bound the list keeps
			$reason = mb_strcut( (string) preg_replace( '/[\x00-\x1F\x7F]/', ' ', $reason ), 0, self::MAX_REASON, 'UTF-8' );
			$listed = $reason. $hint;
			$reason = $listed;

			$appData['./mailer/last'] = $reason;

			trigger_error( 'Mailer: '. $reason, E_USER_WARNING );

			$entry = [ 'date' => date( 'Y-m-d H:i:s' ), 'reason' => $listed ];

			try {
				\Nino\Filesystem::mutate( $appData, self::ERRORS_PATH, static function( mixed $state ) use ( $entry ): array {
					$errors = is_array( $state ) === true ? $state : [];
					array_unshift( $errors, $entry );
					return array_slice( $errors, 0, self::MAX_ERRORS );
				} );
			} catch( \Throwable $e ) {
				// The visitor's request must not fail over the ring file - the
				// reason is logged above, the lost record is noted here
				trigger_error( 'Mailer: the error could not be recorded: '. $e->getMessage(), E_USER_WARNING );
			}
		}

		/**
		 *	What to add to a failed send's reason when the port and the
		 *	encryption are a pair that does not go together: 587 and 25 do not
		 *	speak TLS from the first byte, 465 does not speak STARTTLS. Keyed on
		 *	the pair rather than on how the send failed - STARTTLS against 465
		 *	connects fine and then waits for a greeting that never comes, so the
		 *	failure is a timeout with nothing in it that says why
		 *
		 *	@param		int				$port					The port the send used
		 *	@param		string		$encryption		'starttls', 'tls' or 'none'
		 *
		 *	@return 	string									'' for a pair that is fine, else a sentence to append
		 */
		private static function _portHint( int $port, string $encryption ): string {

			if( $encryption === 'tls' && ( $port === 587 || $port === 25 ) )
				return ' (hint: port '. $port. ' speaks STARTTLS or no encryption, not TLS from the start - set the encryption to STARTTLS, or set the port to 0 to take 465 from it)';

			if( $encryption === 'starttls' && $port === 465 )
				return ' (hint: port 465 speaks TLS from the start, not STARTTLS - set the encryption to TLS from the start, or set the port to 0 to take 587 from it)';

			return '';
		}

		/**
		 *	What to add to a failed send's reason when the server refused the
		 *	sender with a 5xx on MAIL FROM: that is nearly always an address
		 *	the authenticated account may not send as ("Sender mismatch"). In
		 *	both languages of the feature, since a reason is recorded once and
		 *	read in whichever the operator's panel speaks. Names the address
		 *	that was refused
		 *
		 *	@param		string		$address			The address MAIL FROM carried
		 *
		 *	@return 	string									A sentence to append
		 */
		private static function _senderHint( string $address ): string {

			return ' (hint: the server refused the sender address '. $address. ' - with most providers it has to be the mailbox of the username, or an address the provider lets that account send as; enter it as the From address in the settings)'
				. ' (Hinweis: Der Server hat die Absenderadresse '. $address. ' abgelehnt - bei den meisten Providern muss es das Postfach des Benutzernamens sein oder eine Adresse, als die der Provider dieses Konto senden lässt; trag sie in den Einstellungen als Absenderadresse ein)';
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
		 *	header block - with its From: line replaced by the configured one
		 *	where the setting is set (and a Reply-To of the kernel's sender added
		 *	where the block has none), verbatim otherwise, and the one
		 *	Content-Transfer-Encoding of the body - a blank line, the body
		 *	quoted-printable and dot-stuffed, and the DATA terminator
		 *
		 *	@param		array 		$mail					{ to, subject, body, headers, ... }, see \Nino\Mail::TRANSPORT
		 *	@param		bool			$ownFrom				Whether the From: of the kernel's block is replaced by $fromAddress (the setting is set)
		 *	@param		string		$fromAddress	The envelope/header From address
		 *	@param		string		$fromName			The configured display name, '' for none
		 *	@param		string		$smtpHost			The configured SMTP host, for the Message-ID's right-hand side
		 *
		 *	@return 	string
		 */
		private static function _payload( array $mail, bool $ownFrom, string $fromAddress, string $fromName, string $smtpHost ): string {

			$lines = [];
			$lines[] = 'Date: '. date( DATE_RFC2822 );
			$lines[] = 'Message-ID: <'. bin2hex( random_bytes( 16 ) ). '@'. self::_messageIdHost( $smtpHost ). '>';
			$lines[] = 'To: '. $mail['to'];
			$lines[] = 'Subject: '. self::_encodeHeaderValue( (string) $mail['subject'] );
			$lines[] = 'MIME-Version: 1.0';

			// $mail['headers'] is the block \Nino\Mail::send() would hand
			// mail() - which already starts with its own "MIME-Version: 1.0"
			// (see Mail.php) - so that one line is dropped here rather than
			// duplicated; everything else (Content-Type, Reply-To) is kept
			// verbatim. The From: line is the configured one where the setting
			// is set - in the place the kernel's stood - and the kernel's own
			// otherwise
			$fromWritten = false;
			$hasReplyTo	 = false;

			foreach( preg_split( '/\r\n|\r|\n/', trim( (string) $mail['headers'] ) ) ?: [] as $headerLine ) {

				// The body is encoded below and says so once: a line of the
				// block that names an encoding of its own would make two
				if( trim( $headerLine ) === '' || preg_match( '/^(MIME-Version|Content-Transfer-Encoding):/i', trim( $headerLine ) ) === 1 )
					continue;

				if( $ownFrom === true && preg_match( '/^From:/i', $headerLine ) === 1 ) {

					if( $fromWritten === false )
						$lines[] = 'From: '. self::_fromHeaderValue( $fromAddress, $fromName );

					$fromWritten = true;
					continue;
				}

				if( preg_match( '/^Reply-To:/i', $headerLine ) === 1 )
					$hasReplyTo = true;

				$lines[] = $headerLine;
			}

			// Replies to a mail that names no Reply-To of its own (the test
			// mail, an invitation) belong to the owner of the site, whose
			// address the From: line no longer carries - not to the mailbox
			// of the SMTP account
			if( $ownFrom === true && $hasReplyTo === false && trim( (string) $mail['sender'] ) !== '' )
				$lines[] = 'Reply-To: '. trim( (string) $mail['sender'] );

			if( $ownFrom === true && $fromWritten === false )
				$lines[] = 'From: '. self::_fromHeaderValue( $fromAddress, $fromName );

			// An html body is one long line per paragraph, and SMTP takes 998
			// octets a line (RFC 5321 section 4.5.3.1.6), 78 are meant
			// (RFC 5322 section 2.1.1): quoted-printable keeps every line to 76
			// and a raw view readable. The kernel makes no other kind of body
			$lines[] = 'Content-Transfer-Encoding: quoted-printable';

			return implode( "\r\n", $lines ). "\r\n\r\n". self::_dotStuff( self::_encodeBody( (string) $mail['body'] ) ). "\r\n.\r\n";
		}

		/**
		 *	A body as quoted-printable with CRLF line ends. The line ends are
		 *	made CRLF first: quoted_printable_encode() takes a bare LF or CR
		 *	for a byte to encode (=0A), not for the end of a line
		 *
		 *	@param		string		$body
		 *
		 *	@return 	string
		 */
		private static function _encodeBody( string $body ): string {

			$normalized = preg_replace( '/\r\n|\r|\n/', "\r\n", $body );

			return quoted_printable_encode( is_string( $normalized ) ? $normalized : $body );
		}

		/**
		 *	@param		string		$address
		 *	@param		string		$name					'' for none
		 *
		 *	@return 	string									"address", "\"name\" <address>", or the name as RFC 2047 words before <address> when it is not plain ASCII
		 */
		private static function _fromHeaderValue( string $address, string $name ): string {

			if( $name === '' )
				return $address;

			/*	An encoded word is not a quoted-string and may not stand inside
				one (RFC 2047 section 5). The quotes used to go round it anyway,
				so a reader that takes them at their word shows the site owner
				"=?UTF-8?B?Q2Fmw6kgTmluMg==?=" where the name should be. A name
				that needs no encoding keeps its quotes, which is what lets it
				carry a comma or a full stop	*/
			if( mb_check_encoding( $name, 'ASCII' ) === false )
				return self::_encodeHeaderValue( $name ). ' <'. $address. '>';

			return '"'. str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $name ). '" <'. $address. '>';
		}

		/**
		 *	A header value as RFC 2047 B-encoded UTF-8 words, through the same
		 *	call \Nino\Mail::send() encodes a subject with when no transport
		 *	takes the mail - so this feature really does encode the way it says
		 *	it does.
		 *
		 *	It used to base64 the whole value into one word of whatever length
		 *	came out. An encoded word may be 75 characters and no more (RFC 2047
		 *	section 2), and a header line is meant to stay under 78 (RFC 5322
		 *	section 2.1.1) - a subject somebody wrote in a language with umlauts
		 *	passes both without being long. mb_encode_mimeheader() splits the
		 *	value into words that fit and folds between them, and leaves plain
		 *	ASCII alone, with one exception
		 *	- a value with a line too long for a server is cut into words of
		 *	  its own (below)
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		private static function _encodeHeaderValue( string $value ): string {

			$encoded = mb_encode_mimeheader( $value, 'UTF-8', 'B' );

			// Plain ASCII is left alone and folded only at a space, so a long
			// value without one stays a single line, past the 998 octets a
			// server takes. Such a value is cut into words of its own
			foreach( explode( "\r\n", $encoded ) as $line )
				if( strlen( $line ) > 900 ) {

					$words = [];

					foreach( mb_str_split( $value, 11, 'UTF-8' ) as $chunk )
						$words[] = '=?UTF-8?B?'. base64_encode( $chunk ). '?=';

					return implode( "\r\n ", $words );
				}

			return $encoded;
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
