<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Wipe {

	use Nino\Modules\Crm\Api as Endpoint;
	use Nino\Modules\Crm\Erasure;
	use Nino\Modules\Crm\Wipe;

	/**
	 *	Wipe\Api		The button at the foot of the settings of the surface that deletes everything the clients moved, for the test phase (see Wipe for what goes and what stays):
	 *
	 *									crm/wipe		Delete it. The post carries confirm, which has to be the word of the language the account works in (Wipe::word()) exactly - not trimmed, not in small letters - else the answer is the 400 with
	 *																the code crm_wipe_confirm and nothing is deleted. The permission of the CRM is asked for (by the endpoint), and with it - each on its own, before the word is read - the permission of the Elements panel
	 *																(the projects are elements) and of the panel of each package whose data goes: an account that lacks one gets the 403 with the code crm_wipe_perm and nothing is deleted. Where the data of a
	 *																package that is gone is in the database the answer is the 409 with the code crm_package_data where every package that holds data is removed, else crm_package_off, and nothing is deleted. The answer is the counts; a second press answers zeros. The activity log
	 *																gets one line, "Wiped ..." with the counts - never a name, a number or an address
	 *
	 *	@package						Dape/Nino
	 */
	class Api {

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'crm/wipe' => [ self::class, 'apiWipe' ] ];
		}

		/**
		 *	crm/wipe
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiWipe( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			if( Wipe::permitted( $appData ) === false ) {
				\Nino\Http::fail( $request, 403, 'not allowed', 'crm_wipe_perm' );
				return;
			}

			$data = \Nino\Modules\Crm\Values::posted();
			$word = Wipe::word( $appData );

			if( $word === '' || ( $data['confirm'] ?? null ) !== $word ) {
				\Nino\Http::fail( $request, 400, 'the word is not the one', 'crm_wipe_confirm', [ $word ], 'confirm' );
				return;
			}

			Endpoint::run( $request, static function() use ( &$appData, &$request ): void {

				$so = [];
				$done = Wipe::run( $appData, $so );
				$keys = Wipe::keys( $appData );
				$line = static fn( array $counts ): string => implode( ', ', array_map( static fn( string $key ): string => (int) ( $counts[$key] ?? 0 ). ' '. $key, $keys ) );

				if( is_string( $done ) === true ) {

					if( $done === 'orphans' ) {
						\Nino\Http::fail( $request, 409, 'the data of a package that is gone is still here', Erasure::dataOfRemoved( $appData ) === true ? 'crm_package_data' : 'crm_package_off', [ implode( ', ', (array) Erasure::orphanKeys( $appData ) ) ] );
						return;
					}

					if( $done === 'busy' ) {
						\Nino\Http::fail( $request, 503, 'the database is held by another process', 'crm_busy' );
						$request['/nino/http/response']['header']['Retry-After'] = '5';
						return;
					}

					if( $done === 'open' ) {
						\Nino\Http::fail( $request, 409, 'finish the open deletion of a client first', 'crm_wipe_open' );
						return;
					}

					if( $done === 'denied' ) {
						\Nino\Http::fail( $request, 403, 'not allowed', 'crm_wipe_perm' );
						return;
					}

					if( $done === 'lock' ) {
						\Nino\Http::fail( $request, 409, 'another run is going on', 'crm_wipe_lock' );
						return;
					}

					if( $done === 'unreadable' ) {
						\Nino\Http::fail( $request, 503, 'the projects cannot be read', 'crm_projects_unreadable' );
						return;
					}

					// An answer of 500 writes no line of the dispatcher: the stop is told to the activity log here
					$user = \Nino\Auth::getCurrentUser( $appData );
					\Nino\Modules\Crm\Values::log( $appData, is_array( $user ) === true ? (string) $user['mail'] : '', 'Stopped wiping the data of the clients at '. ( str_starts_with( $done, 'step:' ) === true ? substr( $done, 5 ) : $done ). ': '. $line( $so ) );

					if( str_starts_with( $done, 'step:' ) === true )
						\Nino\Http::fail( $request, 500, 'the data could not be wiped completely', 'crm_wipe_step', [ substr( $done, 5 ) ] );
					else
						\Nino\Http::fail( $request, 500, 'the data could not be wiped completely', 'crm_wipe_projects' );

					return;
				}

				Endpoint::made( $appData, 'Wiped the data of the clients: '. $line( $done ) );
				\Nino\Http::ok( $request, $done );
			} );
		}
	}
}
