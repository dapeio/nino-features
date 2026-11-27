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
	 *									A run that was begun holds what the steps of the packages read of the clients (Wipe::JOURNAL): where that cannot be read the answer is the 409 crm_wipe_journal and nothing is deleted - unless the post carries discard: true, which replaces the row, in one write, by one that holds only the step of the core in the mode of the clients that are gone (a warning, a line of the activity log), and goes on - the projects of the clients that are gone are deleted first -, and where a package that is not on holds a step the
	 *									answer is the 409 crm_wipe_held_off (the package is off: it has to be on), crm_wipe_held_step (the package is on and has no such step any more) or crm_wipe_held_gone (the package is removed), with the key of the package and the key of the step in the params;
	 *									the owner gives a step of a removed package, or of one that is on and has no such step, up by naming it in forget, a list of pairs { step, package } in the post - the package as typed, compared with the one the row holds: another package is the 409 crm_wipe_forget_package, the key of the core's step the 400 crm_wipe_forget_core, a form that is none the 400 crm_wipe_forget_form, and nothing is given up -, and the run goes on with a line of the activity log and a warning.
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

			Endpoint::run( $request, static function() use ( &$appData, &$request, $data ): void {

				$so = [];
				// forget: a list of { step, package } - or a map step => package: the owner names the package that holds the step, as typed; a form that is none is refused and nothing is given up
				$forget = [];

				foreach( is_array( $data['forget'] ?? null ) === true ? $data['forget'] : [] as $name => $one ) {

					$step = is_array( $one ) === true ? ( $one['step'] ?? null ) : ( is_string( $name ) === true ? $name : null );
					$package = is_array( $one ) === true ? ( $one['package'] ?? null ) : $one;

					if( is_string( $step ) === false || is_string( $package ) === false ) {
						\Nino\Http::fail( $request, 400, 'a step is given up with the package that holds it', 'crm_wipe_forget_form', [], 'forget' );
						return;
					}

					$forget[$step] = $package;
				}

				$done = Wipe::run( $appData, $so, $forget, ( $data['discard'] ?? false ) === true );
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

					// A run that was begun holds what the steps read of the clients: a row that cannot be read, and what a package that is off or removed holds, keep the run from going on - nothing is deleted
					if( $done === 'journal' ) {
						\Nino\Http::fail( $request, 409, 'the run that was begun cannot be read', 'crm_wipe_journal' );
						return;
					}

					if( str_starts_with( $done, 'forget:' ) === true ) {
						[ , $why, $step ] = explode( ':', $done, 3 );
						\Nino\Http::fail( $request, $why === 'core' ? 400 : 409, 'a step is given up with the package that holds it', $why === 'core' ? 'crm_wipe_forget_core' : 'crm_wipe_forget_package', [ $step ] );
						return;
					}

					if( str_starts_with( $done, 'held:' ) === true ) {
						[ , $why, $step, $package ] = explode( ':', $done, 4 );
						\Nino\Http::fail( $request, 409, 'a package that is not on holds what a run that was begun read', [ 'gone' => 'crm_wipe_held_gone', 'step' => 'crm_wipe_held_step' ][$why] ?? 'crm_wipe_held_off', [ $package, $step ] );
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
