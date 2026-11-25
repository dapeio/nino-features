<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Notifications		The switches of the mails that tell the owner or the clients of what happened: the block of the settings of the surface that gathers them. The core has none of its own and writes no
	 *											setting of another feature (that is the package's business): a package enters the switches it owns with add() - a key, a rank, a way to read it and a way to write it - and the owner
	 *											changes them through crm/notifications-save, which asks the package to write. crm/notifications answers the switches (key => '0' or '1'), the address the mails to the owner go to, the
	 *											address they go out as and the cap of mails an hour and address that the kernel keeps (a hint with a link to the Config panel in the status: the core does not write it).
	 *
	 *	@package						Dape/Nino
	 */
	class Notifications {

		/**	The key of the kernel's cap of mails an hour and address	*/
		private const string CAP_KEY = '/nino/mail/cap';

		// The switches the packages entered, in the app data of the request
		private const string REGISTER = './crm/notifications/switches';

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'crm/notifications' => [ self::class, 'apiNotifications' ], 'crm/notifications-save' => [ self::class, 'apiSave' ] ];
		}

		/**
		 *	Enter a switch
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					package (the key of the feature that enters it), key (lower case letters, digits and underscores), rank (500 by default), read ([ class, method ]: ( array &$appData ): bool) and write ([ class, method ]: ( array &$appData, bool $on ): true|string)
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - a key another package has among them - and nothing is entered
		 */
		public static function add( array &$appData, array $spec ): bool {

			return Register::add( $appData, self::REGISTER, 'notification switches', [ $spec ], [
				'id' => 'key',
				'idRule' => static fn( string $key ): bool => Register::word( $key, 40, '_' ),
				'fields' => [ 'package' => Register::REQUIRED, 'read' => Register::REQUIRED, 'write' => Register::REQUIRED, 'rank' => 500 ],
				'rules' => [
					'package' => Register::packageRule( $appData ),
					'read' => static fn( mixed $read ): bool => Register::callable( $read ),
					'write' => static fn( mixed $write ): bool => Register::callable( $write ),
					'rank' => static fn( mixed $rank ): bool => is_int( $rank ),
				],
			] );
		}

		/**
		 *	The switches, by rank
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => description
		 */
		private static function switches( array &$appData ): array {

			$all = Register::all( $appData, self::REGISTER );
			uasort( $all, static fn( array $a, array $b ): int => [ $a['rank'], $a[Register::SEQ] ] <=> [ $b['rank'], $b[Register::SEQ] ] );

			return $all;
		}

		/**
		 *	The mail notifications in one answer
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											switches (key => '0' or '1', the value in force), owner (the contact address of the website, where the mails to the owner go), sender (the address the mails go out as) and cap (the mail cap in force)
		 */
		public static function read( array &$appData ): array {

			$switches = [];

			foreach( self::switches( $appData ) as $key => $switch )
				$switches[(string) $key] = call_user_func_array( $switch['read'], [ &$appData ] ) === true ? '1' : '0';

			$cap = \Nino\AppData::stored( $appData, self::CAP_KEY, null );

			return [ 'switches' => (object) $switches, 'owner' => self::owner( $appData ), 'sender' => self::sender( $appData ), 'cap' => (int) ( $cap ?? \Nino\AppData::DEFAULTS[self::CAP_KEY] ) ];
		}

		/**
		 *	The contact address as the packages read it for the mails to the owner: the text as it stands, and an address or nothing
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		private static function owner( array &$appData ): string {

			$mail = trim( (string) \Nino\Html::resolveTextfill( $appData, '/project/company/contact/email' ) );

			return filter_var( $mail, FILTER_VALIDATE_EMAIL ) !== false ? $mail : '';
		}

		/**
		 *	The address the mails go out as, chosen as the kernel's mail reads it: the envelope address where it is an address, else the owner's, else none
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		private static function sender( array &$appData ): string {

			foreach( [ '/project/mail/address/envelope', '/project/mail/address/owner' ] as $key ) {

				$mail = trim( str_replace( [ "\r", "\n", "\0" ], '', \Nino\Html::renderHtml( $appData, '[['. $key. ']]' ) ) );

				if( filter_var( $mail, FILTER_VALIDATE_EMAIL ) !== false )
					return $mail;
			}

			return '';
		}

		/**
		 *	crm/notifications
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiNotifications( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Api::run( $request, static function() use ( &$appData, &$request ): void {
				\Nino\Http::ok( $request, self::read( $appData ) );
			} );
		}

		/**
		 *	crm/notifications-save: save switches - any of the keys that were entered, each '0' or '1' and nothing else. Every key is checked before one is written: a key nobody entered, or any other value, is a 400 at the field
		 *	and nothing is written; a package that cannot write its switch is a 500 and the switches before it stand
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Modules\Crm\Values::posted();
			$switches = self::switches( $appData );
			$posted = [];

			foreach( $data as $key => $value ) {

				if( is_string( $key ) === false || isset( $switches[$key] ) === false ) {
					\Nino\Http::fail( $request, 400, 'the notification is none', 'crm_notify_key', [], is_string( $key ) === true && preg_match( '/^[a-z_]{1,40}$/D', $key ) === 1 ? $key : '' );
					return;
				}

				if( $value !== '0' && $value !== '1' ) {
					\Nino\Http::fail( $request, 400, 'the value of the notification is none', 'crm_notify_value', [], $key );
					return;
				}

				$posted[$key] = $value === '1';
			}

			if( $posted === [] ) {
				\Nino\Http::fail( $request, 400, 'nothing to save', 'crm_notify_key' );
				return;
			}

			foreach( $posted as $key => $on )
				if( call_user_func_array( $switches[$key]['write'], [ &$appData, $on ] ) !== true ) {
					\Nino\Http::fail( $request, 500, 'the setting could not be written', 'crm_notify_write', [], $key );
					return;
				}

			Api::made( $appData, 'Changed the mail notifications: '. implode( ', ', array_keys( $posted ) ) );
			Api::run( $request, static function() use ( &$appData, &$request ): void {
				\Nino\Http::ok( $request, self::read( $appData ) );
			} );
		}
	}
}
