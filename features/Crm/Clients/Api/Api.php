<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Clients {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Clients;
	use Nino\Modules\Crm\Erasure;
	use Nino\Modules\Crm\Register;
	use Nino\Modules\Crm\Wipe;

	/**
	 *	Clients\Api		The actions of the clients behind the endpoint: the clients (list, card, add, change, deactivate) and the deletion of one, under the permission of the CRM. What a package keeps of a client
	 *										is the package's own actions; it hears of a client through the hooks of Clients, and what a form of a package posts for the client travels in packages (key of the package => values), and what a package adds to a client or to the list - crm-clients/get and crm-clients/list - comes back in packages, in the same shape, under its key (Clients::VIEW, VIEWS). Deleting a client is two
	 *										actions: delete-preview (what hangs on it, as numbers, and whether it may go) and delete (the number typed is compared here, on the server, and the deletion of Erasure runs). It validates what it is
	 *										posted and answers the failures a person can cause with codes; the rules of the data are Clients', the storage Db's. A database failure is answered 500 with the code crm_database.
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [
				'crm-clients/list'           => [ self::class, 'apiList' ],
				'crm-clients/get'            => [ self::class, 'apiGet' ],
				'crm-clients/save'           => [ self::class, 'apiSave' ],
				'crm-clients/active'         => [ self::class, 'apiActive' ],
				'crm-clients/delete-preview' => [ self::class, 'apiDeletePreview' ],
				'crm-clients/delete'         => [ self::class, 'apiDelete' ],
			];
		}

		/**
		 *	The clients, by number - all of them: the table of the surface searches, sorts and pages them where it stands - and the deletions that were begun and not finished
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {
				\Nino\Http::ok( $request, [ 'clients' => array_map( [ self::class, 'shown' ], Clients::views( $appData, Clients::list( $appData ) ) ), 'erasing' => Erasure::pending( $appData ), 'wiping' => \Nino\Modules\Crm\Wipe::unfinished( $appData ) ] );
			} );
		}

		/**
		 *	One client
		 */
		public static function apiGet( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$client = self::client( $appData, $request, \Nino\Modules\Crm\Values::posted() );

				if( $client !== null )
					\Nino\Http::ok( $request, [ 'client' => self::shown( Clients::view( $appData, $client ) ) ] );
			} );
		}

		/**
		 *	Add a client (no id) or change one (id). What the forms of the packages posted for it is packages
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Modules\Crm\Values::posted();
			$input = [];

			foreach( array_keys( Clients::FIELDS ) as $field ) {

				if( array_key_exists( $field, $data ) === false )
					continue;

				if( is_string( $data[$field] ) === false ) {
					\Nino\Http::fail( $request, 400, 'the value is no text', 'crm_clients_format', [], $field );
					return;
				}

				$input[$field] = $data[$field];
			}

			// Whether the client is a business is a flag, not a text
			if( array_key_exists( 'business', $data ) === true ) {

				if( is_bool( $data['business'] ) === false ) {
					\Nino\Http::fail( $request, 400, 'the value is no flag', 'crm_clients_format', [], 'business' );
					return;
				}

				$input['business'] = $data['business'];
			}

			$posted = self::packages( $request, $data );

			if( $posted === null )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request, $data, $input, $posted ): void {

				if( \Nino\Modules\Crm\Values::string( $data, 'id' ) === '' ) {
					$client = Clients::add( $appData, $input, $posted );
					$line = 'Added client';
				}
				else {
					$existing = self::client( $appData, $request, $data );

					if( $existing === null )
						return;

					$client = Clients::change( $appData, $existing['id'], $input, $posted );
					$line = 'Changed client';
				}

				if( is_string( $client ) === true ) {
					self::refused( $request, $client );
					return;
				}

				Endpoint::made( $appData, $line. ' '. $client['number'] );
				\Nino\Http::ok( $request, [ 'client' => array_diff_key( $client, [ 'packages' => 1 ] ), 'packages' => (object) $client['packages'] ] );
			} );
		}

		/**
		 *	Switch a client on or off: active true or false, without it the other way round. The packages that keep something of a client are told, and one that cannot follow keeps the client as it was (500 crm_clients_write)
		 */
		public static function apiActive( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$client = self::client( $appData, $request, $data );

				if( $client === null )
					return;

				$active = is_bool( $data['active'] ?? null ) === true ? $data['active'] : $client['active'] === false;
				$changed = Clients::setActive( $appData, $client['id'], $active );

				if( is_string( $changed ) === true ) {

					if( $changed === 'write' )
						\Nino\Http::fail( $request, 500, 'a package could not follow', 'crm_clients_write' );
					else
						\Nino\Http::fail( $request, 404, 'there is no such client', 'crm_clients_unknown', [], 'id' );

					return;
				}

				Endpoint::made( $appData, 'Switched client '. $changed['number'] );
				\Nino\Http::ok( $request, [ 'client' => array_diff_key( $changed, [ 'packages' => 1 ] ), 'packages' => (object) $changed['packages'] ] );
			} );
		}

		/**
		 *	What deleting a client would take with it, as numbers, and whether it may be done (see Erasure::plan()): the answer is the preview, with permitted - whether the account holds the permission of every
		 *	panel whose data goes along. Nothing is changed
		 */
		public static function apiDeletePreview( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$client = self::client( $appData, $request, \Nino\Modules\Crm\Values::posted() );

				if( $client !== null )
					\Nino\Http::ok( $request, [ 'preview' => Clients::deletable( $appData, $client['id'] ) + [ 'permitted' => Wipe::permitted( $appData ) ] ] );
			} );
		}

		/**
		 *	Delete a client with everything that hangs on it (id and confirm, which has to be the number of the client exactly - not trimmed, not in small letters - else 400 crm_clients_delete_confirm and nothing is
		 *	changed), or finish the deletion of one that stopped (number and confirm, the same number). The permission of the CRM is asked for by the endpoint and, before the number is read, the permission of every
		 *	panel whose data goes (403 crm_clients_delete_perm). A client a package refuses is 409 with the code the package entered, one with a project another client names 409 crm_clients_delete_shared and one a package
		 *	holds back 409 crm_clients_delete_held; a step that stopped after the database is 500 crm_clients_delete_projects (the core's) or crm_clients_delete_step (a package's) and the same press finishes it. The log names the
		 *	number of the client, never a name or an address
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$data = \Nino\Modules\Crm\Values::posted();
				$posted = \Nino\Modules\Crm\Values::string( $data, 'number' );
				$client = null;

				if( $posted !== '' ) {

					$number = Erasure::pendingNumber( $appData, $posted );

					if( $number === '' ) {
						\Nino\Http::fail( $request, 404, 'there is no such client', 'crm_clients_unknown', [], 'number' );
						return;
					}
				}
				else {
					$client = self::client( $appData, $request, $data );

					if( $client === null )
						return;

					$number = (string) $client['number'];
				}

				if( Wipe::permitted( $appData ) === false ) {
					\Nino\Http::fail( $request, 403, 'not allowed', 'crm_clients_delete_perm' );
					return;
				}

				// Compared here and not in the script: the word is the number of the client, as it is written, exactly
				if( ( $data['confirm'] ?? null ) !== $number ) {
					\Nino\Http::fail( $request, 400, 'the number is not the one', 'crm_clients_delete_confirm', [ $number ], 'confirm' );
					return;
				}

				$so = [];
				$user = \Nino\Auth::getCurrentUser( $appData );
				$mail = is_array( $user ) === true ? (string) $user['mail'] : '';
				$skip = is_array( $data['skip'] ?? null ) === true && array_is_list( $data['skip'] ) === true && count( $data['skip'] ) <= 20 ? array_values( array_filter( $data['skip'], static fn( mixed $key ): bool => is_string( $key ) === true && Register::word( $key ) === true ) ) : [];
				$done = $client === null ? Erasure::resume( $appData, $number, $so, $skip ) : Clients::delete( $appData, $client['id'], $so );

				if( is_string( $done ) === true ) {

					if( $done === 'projects' || str_starts_with( $done, 'step:' ) === true ) {
						\Nino\Modules\Crm\Values::log( $appData, $mail, 'Stopped deleting client '. $number. ' at '. ( $done === 'projects' ? $done : substr( $done, 5 ) ) );

						if( $done === 'projects' )
							\Nino\Http::fail( $request, 500, 'the client could not be deleted completely', 'crm_clients_delete_projects' );
						else
							\Nino\Http::fail( $request, 500, 'the client could not be deleted completely', 'crm_clients_delete_step', [ substr( $done, 5 ) ] );

						return;
					}

					if( $done === 'held' && $client !== null ) {
						$why = array_values( array_filter( array_column( array_filter( Clients::deletable( $appData, $client['id'] )['features'], static fn( array $one ): bool => $one['blocks'] === true ), 'why' ) ) );
						\Nino\Http::fail( $request, 409, 'a package holds the client', 'crm_clients_delete_held', [ $why[0] ?? '' ] );
						return;
					}

					if( $done === 'busy' ) {
						\Nino\Http::fail( $request, 503, 'the database is held by another process', 'crm_busy' );
						$request['/nino/http/response']['header']['Retry-After'] = '5';
						return;
					}

					// A package that is not removed (off, not readable, something unknown beside it) is one answer; a package that is removed is another, and with its data still in the database a third. Only where
					// every step of the deletion is of a package that is removed and has no data is a step offered to be given up
					if( str_starts_with( $done, 'off:' ) === true ) {
						$removed = Erasure::stepsOfRemoved( $appData, $number );
						\Nino\Http::fail( $request, 409, 'a package the deletion was begun with is not on', $removed === true ? 'crm_package_gone' : 'crm_package_off', [ substr( $done, 4 ) ] );
						$request['/nino/http/response']['body']['skippable'] = $removed === true ? Erasure::skippable( $appData, $number ) : [];
						return;
					}

					if( $done === 'orphans' ) {
						\Nino\Http::fail( $request, 409, 'the data of a package that is gone is still here', Erasure::dataOfRemoved( $appData ) === true ? 'crm_package_data' : 'crm_package_off', [ implode( ', ', (array) Erasure::orphanKeys( $appData ) ) ] );
						$request['/nino/http/response']['body']['skippable'] = [];
						return;
					}

					match( $done ) {
						'shared'  => \Nino\Http::fail( $request, 409, 'a project of the client is named by another client', 'crm_clients_delete_shared' ),
						'lock'    => \Nino\Http::fail( $request, 409, 'another deletion is running', 'crm_clients_delete_lock' ),
						'denied'  => \Nino\Http::fail( $request, 403, 'not allowed', 'crm_clients_delete_perm' ),
						default   => ( $answer = Erasure::answerOf( $appData, $done ) ) === null ? \Nino\Http::fail( $request, 404, 'there is no such client', 'crm_clients_unknown', [], 'id' ) : \Nino\Http::fail( $request, $answer[0], $answer[1], $answer[2] ),
					};

					return;
				}

				Endpoint::made( $appData, 'Deleted client '. $done['number']. ( is_array( $done['skipped'] ?? null ) === true ? ' and gave up the steps of '. implode( ', ', $done['skipped'] ) : '' ) );
				\Nino\Http::ok( $request, $done );
			} );
		}

		/**
		 *	The client a request names by id, or the 400/404 that says why not
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	array|null
		 */
		private static function client( array &$appData, array &$request, array $data ): ?array {

			$id = Endpoint::id( $request, $data, 'crm_clients_id' );

			if( $id === null )
				return null;

			$client = Clients::get( $appData, $id );

			if( $client === null )
				\Nino\Http::fail( $request, 404, 'there is no such client', 'crm_clients_unknown', [], 'id' );

			return $client;
		}

		/**
		 *	A client as the answer says it: what the packages add to it is an object of objects, key of the package => its fields - {} where none adds - and never a list
		 *
		 *	@param		array 		$client				As Clients::view() has it
		 *
		 *	@return 	array
		 */
		private static function shown( array $client ): array {

			$client['packages'] = (object) array_map( static fn( array $part ): object => (object) $part, (array) ( $client['packages'] ?? [] ) );

			return $client;
		}

		/**
		 *	What the forms of the packages posted: an object of objects, key of the package => values; none where nothing was posted
		 *
		 *	@param		array 		&$request
		 *	@param		array 		$data
		 *
		 *	@return 	array|null								Null after the 400 that says why not
		 */
		private static function packages( array &$request, array $data ): ?array {

			if( array_key_exists( 'packages', $data ) === false )
				return [];

			$packages = $data['packages'];

			if( is_array( $packages ) === false || ( $packages !== [] && array_is_list( $packages ) === true ) )
				$packages = null;

			foreach( $packages ?? [] as $key => $values )
				if( is_string( $key ) === false || \Nino\Modules\Crm\Register::word( $key ) === false || is_array( $values ) === false )
					$packages = null;

			if( $packages === null ) {
				\Nino\Http::fail( $request, 400, 'the values of the packages are an object of objects', 'crm_clients_format', [], 'packages' );
				return null;
			}

			return $packages;
		}

		/**
		 *	The answer to a refusal of the model
		 *
		 *	@param		array 		&$request
		 *	@param		string		$why					'empty', 'email:email', 'long:<field>', 'format:<field>' or 'unknown'
		 *
		 *	@return 	void
		 */
		private static function refused( array &$request, string $why ): void {

			[ $kind, $field ] = array_pad( explode( ':', $why, 2 ), 2, '' );

			match( $kind ) {
				'wiping'  => \Nino\Http::fail( $request, 409, 'the test phase is not finished yet', 'crm_wipe_unfinished' ),
				'empty'   => \Nino\Http::fail( $request, 400, 'a client needs a company or a name', 'crm_clients_empty', [], 'company' ),
				'email'   => \Nino\Http::fail( $request, 400, 'the address is not valid', 'crm_clients_email', [], 'email' ),
				'long'    => \Nino\Http::fail( $request, 400, 'the value is too long', 'crm_clients_long', [ Clients::FIELDS[$field] ?? 0 ], $field ),
				'format'  => \Nino\Http::fail( $request, 400, 'the value has a wrong format', 'crm_clients_format', [], $field ),
				default   => \Nino\Http::fail( $request, 404, 'there is no such client', 'crm_clients_unknown', [], 'id' ),
			};
		}
	}
}
