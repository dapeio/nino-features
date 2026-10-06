<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Mailer\Admin		The /_admin panel of the Mailer feature - see docs/development.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Mailer {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Mailer\Admin			"Mailer" panel: a status line naming the configured
	 *										host/port/encryption (never the password, and "not
	 *										configured" while host is empty), one button that
	 *										sends a test mail through \Nino\Mail::send() - the same
	 *										path, and the same per-ip cap, every other mail on the
	 *										site goes through - and the last failures the transport
	 *										recorded. The actual settings form is the Features
	 *										panel's own; this panel only proves the configured
	 *										settings actually deliver.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/mailer/manage';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'mailer/status'	=> [ self::class, 'apiStatus' ],
				'mailer/test'		=> [ self::class, 'apiSendTest' ],
			];
		}

		public static function nav(): array {
			return [ 'mailer', '/_admin/nav/mailer', 35, 'system' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>';
		}

		public static function panes(): array {
			return [ 'mailer-form' ];
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
		}

		// The panel's own strings, one <locale>.php per interface language,
		// including the subject and body of the test mail itself
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return $action === 'mailer/test' ? 'Send test mail to "'. (string) ( $data['to'] ?? '' ). '"' : '';
		}

		/**
		 *	The status line's data - host, the port a send really connects to
		 *	(the setting's own, or the one its encryption names) and encryption,
		 *	never the username or the password - the address the test mail is
		 *	offered to (the signed-in account's own, else the From address, else
		 *	none) and the last failures
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiStatus( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$settings = \Nino\Features::settings( $appData, 'mailer' );
			$account	= \Nino\Auth::getCurrentUser( $appData );
			$testTo		= '';

			foreach( [ is_array( $account ) === true ? (string) ( $account['mail'] ?? '' ) : '', (string) $settings['from'] ] as $candidate )
				if( $testTo === '' && filter_var( $candidate, FILTER_VALIDATE_EMAIL ) !== false )
					$testTo = $candidate;

			\Nino\Http::ok( $request, [
				'host'				=> $settings['host'],
				'port'				=> \Nino\Modules\Mailer::port( $settings ),
				'encryption'	=> $settings['encryption'],
				'testTo'			=> $testTo,
				'errors'			=> \Nino\Modules\Mailer::errors( $appData ),
			] );
		}

		/**
		 *	Send one test mail through \Nino\Mail::send() - the same
		 *	transport, the same per-ip cap (5/hour) every other mail on the
		 *	site goes through. A 400 either names the reason the transport
		 *	itself recorded (\Nino\Modules\Mailer::callbackSend() leaves it
		 *	under './mailer/last') or, when the cap itself refused the send,
		 *	says so instead - the transport was never even asked.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSendTest( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$to = trim( (string) ( \Nino\Admin\Admin::postData()['to'] ?? '' ) );

			if( filter_var( $to, FILTER_VALIDATE_EMAIL ) === false ) {
				\Nino\Http::fail( $request, 400, 'not a valid email address' );
				return;
			}

			unset( $appData['./mailer/last'], $appData['./nino/mail/ratelimited'] );

			$subject = \Nino\Html::renderHtml( $appData, '[[/_admin/mailer/mail/subject]]' );
			$body		 = \Nino\Html::renderHtml( $appData, '[[/_admin/mailer/mail/body]]' );

			if( \Nino\Mail::send( $appData, $to, $subject, $body, '' ) === true ) {
				\Nino\Http::ok( $request, [ 'sent' => true ] );
				return;
			}

			$reason = ( $appData['./nino/mail/ratelimited'] ?? false ) === true
				? 'rate limit reached - at most 5 test or other mails per hour and client ip'
				: (string) ( $appData['./mailer/last'] ?? 'the mail could not be sent' );

			\Nino\Http::fail( $request, 400, $reason );
		}
	}
}
