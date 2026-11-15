<?php
declare(strict_types=1);

namespace Nino\Modules\Sysadmin {

	/**
	 *	Nino features					Sysadmin panel: who is told and what the report finds right now, the clocks of
	 *												the two mails, and one button that sends a test mail to the saved recipients. The
	 *												recipients, the distance of two problem mails and the rhythm of the sign of life are
	 *												settings of the feature: the Settings tab the kernel puts beside this panel is
	 *												their form
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/sysadmin/manage';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [ 'sysadmin/status' => [ self::class, 'apiStatus' ], 'sysadmin/test' => [ self::class, 'apiTest' ] ];
		}

		public static function nav(): array {
			return [ 'sysadmin', '/_admin/sysadmin/nav', 36 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>';
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return $action === 'sysadmin/test' ? 'Send Sysadmin test mail' : '';
		}

		/**
		 *	The screen: the recipients, the two clocks, the last failed send and what the report finds now,
		 *	its sentences in the language of the workbench where the site has it, else the native one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiStatus( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$now			= \Nino\Modules\Sysadmin::now( $appData );
			$state		= \Nino\Modules\Sysadmin::state( $appData );
			$beat			= \Nino\Modules\Sysadmin::beat( $appData );
			$since		= ( $state['lastMail'] ?? 0 ) > 0 ? $state['lastMail'] : ( $state['since'] ?? $now );
			$report		= \Nino\Modules\Sysadmin\Report::collect( $appData, self::_language( $appData ), $since, $now, false, \Nino\Modules\Sysadmin::aborted( $state, $now ) );
			$stamp		= static fn( int $time ): ?string => $time > 0 ? \Nino\Modules\Sysadmin\Report::stamp( $time ) : null;
			$recipients = \Nino\Modules\Sysadmin::recipients( $appData );

			\Nino\Http::ok( $request, [
				'recipients'	=> $recipients,
				'beat'				=> $beat,
				'gap'					=> (int) ( \Nino\Modules\Sysadmin::gap( $appData ) / 3600 ),
				'lastProblem'	=> $stamp( (int) ( $state['lastProblem'] ?? 0 ) ),
				'lastBeat'		=> $stamp( (int) ( $state['lastMail'] ?? 0 ) ),
				'nextBeat'		=> $state === [] || $beat === 'off' ? null : $stamp( \Nino\Modules\Sysadmin::after( $beat, $state['lastBeat'] ) ),
				'failed'			=> ( $state['failed'] ?? 0 ) > 0 ? [ 'at' => $stamp( $state['failed'] ), 'count' => $state['fails'] ] : null,
				'aborted'			=> \Nino\Modules\Sysadmin::aborted( $state, $now ),
				'testWait'		=> max( 0, ( $state['tested'] ?? 0 ) + \Nino\Modules\Sysadmin::TEST_EVERY - $now ),
				'findings'		=> array_map( static fn( array $finding ): array => [ 'rank' => $finding['rank'], 'text' => $finding['text'], 'event' => $finding['event'] ], $report['findings'] ),
			] );
		}

		/**
		 *	Send the report to the saved recipients as a test mail: the same mail, the same transport and the same
		 *	cap as the two that go out by themselves, and it changes none of what they remember but the time of
		 *	this one, which is what holds the next test back for TEST_EVERY seconds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiTest( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$recipients = \Nino\Modules\Sysadmin::recipients( $appData );

			if( $recipients === [] ) {
				\Nino\Http::fail( $request, 400, 'there is no address to send the test mail to', 'sysadmin_none' );
				return;
			}

			$now		= \Nino\Modules\Sysadmin::now( $appData );
			$wait		= 0;
			$locked	= \Nino\Filesystem::mutate( $appData, \Nino\Modules\Sysadmin::STATE, static function( mixed $stored ) use ( $now, &$wait ): ?array {

				$state	= is_array( $stored ) === true ? $stored : [];
				$tested	= is_int( $state['tested'] ?? null ) === true ? $state['tested'] : 0;

				if( $now < $tested + \Nino\Modules\Sysadmin::TEST_EVERY ) {
					$wait = $tested + \Nino\Modules\Sysadmin::TEST_EVERY - $now;
					return null;
				}

				return array_merge( [ 'format' => 1 ], $state, [ 'tested' => $now ] );
			} );

			if( $wait > 0 ) {
				\Nino\Http::fail( $request, 429, 'a test mail was sent a moment ago', 'sysadmin_wait', [ (string) $wait ] );
				return;
			}

			if( $locked === false ) {
				\Nino\Http::fail( $request, 500, 'the state of the feature could not be written' );
				return;
			}

			$state	= \Nino\Modules\Sysadmin::state( $appData );
			$since	= ( $state['lastMail'] ?? 0 ) > 0 ? $state['lastMail'] : ( $state['since'] ?? $now );
			$mail		= \Nino\Modules\Sysadmin::inNative( $appData, function( string $native ) use ( &$appData, $since, $state, $now ): array {
				return \Nino\Modules\Sysadmin::compose( $appData, $native, 'test', \Nino\Modules\Sysadmin\Report::collect( $appData, $native, $since, $now, true, \Nino\Modules\Sysadmin::aborted( $state, $now ) ), [], $state, $now );
			} );

			if( \Nino\Modules\Sysadmin::send( $appData, $recipients, $mail, [], \Nino\Mail::TEST ) === true ) {
				\Nino\Http::ok( $request, [ 'sent' => count( $recipients ) ] );
				return;
			}

			if( \Nino\Mail::rateLimited( $appData ) === true ) {
				\Nino\Http::fail( $request, 429, 'rate limit reached - the cap of the system\'s test mails an hour', 'sysadmin_ratelimit' );
				return;
			}

			\Nino\Http::fail( $request, 502, 'the mail could not be sent', 'sysadmin_failed' );
		}

		/**
		 *	The language the panel's sentences are in: the language of the workbench where the site serves it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _language( array &$appData ): string {

			$locale = \Nino\Admin\Admin::sessionLocale( $appData );

			return \Nino\Locales::verifyLocale( $appData, $locale ) === true ? $locale : \Nino\Locales::getNativeLocale( $appData );
		}
	}
}
