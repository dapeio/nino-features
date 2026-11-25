<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Settings {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Company;
	use Nino\Modules\Crm\Hosts;
	use Nino\Modules\Crm\Mail;
	use Nino\Modules\Crm\Projects;
	use Nino\Modules\Crm\Settings;

	/**
	 *	Settings\Api		The settings of the CRM behind the endpoint: what the screen of the settings reads and writes, the core's keys and the keys the packages entered, and the test mail of the wordmark.
	 *
	 *										crm/settings-get		settings (every key with its value), addresses (where things are: the workbench and the CRM, and what a package adds), next (the number the next client and the next
	 *																				project get), company (the texts of the company), and packages - what each package shows of itself in the settings, key => part (the hook '/crm/settings/view'), and wipe: steps, the key of each step a package entered for the test phase
	 *										crm/settings-save		Save: any of the keys; every value is checked before one is written, and a key a package keeps itself is not written. The hook '/crm/settings/save' ( posted, clean, refusal ) is asked
	 *																				before anything is written: a package may refuse the whole post with [ status, message, code, params, field ], and may add keys it keeps of its own to clean. A name for the
	 *																				workbench is put in only after it was shown to be this installation (409 crm_app_host_probe)
	 *										crm/brand-test			Send the test mail of the wordmark: one mail to the contact address of the company with the band at the head as the settings make it; mailed says how many were delivered
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**	The hook that is asked before a post of the settings is written	*/
		public const string SAVE = '/crm/settings/save';

		// What a refusal of a key answers with, where the key has no code of its own: a start number, the name of the owner, the hosts
		private const array PARTICULARS = [ 'seller_name' => 'crm_particular_seller_name', 'crm_host' => 'crm_crm_host', 'app_host' => 'crm_app_host' ];

		// What the test mail refuses with: the status, the code and the field it is about
		private const array BRAND = [
			'brand_empty' => [ 409, 'crm_brand_empty', 'brand_word' ],
			'contact'     => [ 409, 'crm_brand_contact', '' ],
			'limit'       => [ 429, 'crm_brand_limit', '' ],
		];

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'crm/settings-get' => [ self::class, 'apiGet' ], 'crm/settings-save' => [ self::class, 'apiSave' ], 'crm/brand-test' => [ self::class, 'apiBrandTest' ] ];
		}

		/**
		 *	The settings, and what the screen of the settings shows beside them
		 */
		public static function apiGet( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				\Nino\Http::ok( $request, [
					'settings'  => Settings::all( $appData ),
					// Where the things are, as an address: the workbench (the app) and the CRM; a package adds its own under packages
					'addresses' => [ [ 'key' => 'app', 'address' => \Nino\Html::absoluteUrl( $appData, '/_admin' ) ], [ 'key' => 'crm', 'address' => \Nino\Html::absoluteUrl( $appData, '/crm' ) ] ],
					'next'      => [ 'client' => \Nino\Modules\Crm\Clients::upcoming( $appData ), 'project' => Projects::upcoming( $appData ) ],
					'company'   => Company::company( $appData ),
					'packages'  => (object) Settings::view( $appData )['packages'],
					// What the test phase takes besides the core's own: the key of every step a package entered, so that the dialog of the test phase names what the server does
					'wipe'      => [ 'steps' => array_map( 'strval', array_keys( \Nino\Modules\Crm\Wipe::entered( $appData ) ) ) ],
				] );
			} );
		}

		/**
		 *	Save settings: any of the keys; every value is checked before one is written
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Modules\Crm\Values::posted();
			$clean = [];
			$hosts = array_merge( Settings::hostKeys( $appData ), [ 'app_host' ] );

			foreach( array_keys( Settings::defaults( $appData ) ) as $key ) {

				// What a package keeps itself (the day its text changed) is not posted
				if( array_key_exists( $key, $data ) === false || Settings::isReadOnly( $appData, $key ) === true )
					continue;

				$value = Settings::clean( $appData, $key, $data[$key] );

				// The hosts are other ones than the website's own, and than each other's: the one posted with it counts as well
				if( is_string( $value ) === true && Settings::clash( $appData, $key, $value ) === true )
					$value = [ 'value:'. $key ];

				if( is_string( $value ) === true && $value !== '' && in_array( $key, $hosts, true ) === true && array_filter( array_diff( $hosts, [ $key ] ), static fn( string $other ): bool => Settings::mayShare( $key, $other ) === false && ( $clean[$other] ?? null ) === $value ) !== [] )
					$value = [ 'value:'. $key ];

				if( is_array( $value ) === true ) {
					self::refused( $request, $value[0], Settings::refusal( $appData, $key ) );
					return;
				}

				$clean[$key] = $value;
			}

			// A package may refuse the post as a whole, and add what it keeps of its own to what is written
			$ask = [ 'posted' => $data, 'clean' => $clean, 'refusal' => null ];

			foreach( Endpoint::asked( $appData, self::SAVE, $ask ) as $answer ) {

				$refusal = $answer['refusal'] ?? null;

				if( is_array( $refusal ) === true && is_int( $refusal['status'] ?? null ) === true && $refusal['status'] >= 400 && $refusal['status'] <= 499 && is_string( $refusal['message'] ?? null ) === true
					&& is_string( $refusal['code'] ?? null ) === true && is_array( $refusal['params'] ?? null ) === true && is_string( $refusal['field'] ?? null ) === true ) {

					\Nino\Http::fail( $request, $refusal['status'], $refusal['message'], $refusal['code'], $refusal['params'], $refusal['field'] );
					return;
				}

				foreach( is_array( $answer['clean'] ?? null ) === true ? $answer['clean'] : [] as $key => $value )
					if( is_string( $key ) === true && is_string( $value ) === true && Settings::known( $appData, $key ) === true && isset( $clean[$key] ) === false && Settings::isReadOnly( $appData, $key ) === false )
						$clean[$key] = $value;
			}

			// The workbench is put under a name only after the name has been shown to be this installation: a name that is not would lock the owner out of it
			if( ( $clean['app_host'] ?? '' ) !== '' && $clean['app_host'] !== Settings::get( $appData, 'app_host' ) && Hosts::probe( $appData, $clean['app_host'] ) === false ) {
				\Nino\Http::fail( $request, 409, 'the name is not this installation', 'crm_app_host_probe', [], 'app_host' );
				return;
			}

			Endpoint::run( $request, static function() use ( &$appData, &$request, $clean ): void {

				foreach( $clean as $key => $value )
					Settings::set( $appData, (string) $key, $value );

				// The projects are numbered by the counter of their element type: a start number above it lifts it now, so that the Elements panel numbers by it as well
				if( isset( $clean['start_project'] ) === true )
					Projects::lift( $appData );

				if( $clean !== [] )
					Endpoint::made( $appData, 'Changed the settings of the CRM: '. implode( ', ', array_keys( $clean ) ) );

				\Nino\Http::ok( $request, [ 'settings' => Settings::all( $appData ) ] );
			} );
		}

		/**
		 *	Send the test mail of the wordmark: one mail to the contact address of the company. The answer says how many mails were delivered; nothing is posted and nothing is written
		 */
		public static function apiBrandTest( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$sent = Mail::brandTest( $appData );

			if( is_string( $sent ) === true ) {

				[ $status, $code, $field ] = self::BRAND[$sent] ?? [ 500, 'crm_brand_failed', '' ];
				\Nino\Http::fail( $request, $status, 'the test mail was refused: '. $sent, $code, [], $field );

				return;
			}

			Endpoint::made( $appData, 'Sent the test mail of the wordmark' );
			\Nino\Http::ok( $request, [ 'mailed' => $sent ] );
		}

		/**
		 *	The answer to a value of a setting that fails
		 *
		 *	@param		array 		&$request
		 *	@param		string		$why					value:<key> or format:<key>
		 *	@param		string		$code					The code the key has of its own; '' for none
		 *
		 *	@return 	void
		 */
		private static function refused( array &$request, string $why, string $code ): void {

			[ $kind, $key ] = array_pad( explode( ':', $why, 2 ), 2, '' );

			if( $kind === 'unknown' ) {
				\Nino\Http::fail( $request, 404, 'there is no such setting', 'crm_setting_unknown' );
				return;
			}

			if( str_starts_with( $key, 'start_' ) === true )
				\Nino\Http::fail( $request, 400, 'the start number is none', 'crm_startnumber', [], $key );
			else
				\Nino\Http::fail( $request, 400, 'the setting has a wrong value', $code !== '' ? $code : ( self::PARTICULARS[$key] ?? 'crm_setting' ), [], $key );
		}
	}
}
