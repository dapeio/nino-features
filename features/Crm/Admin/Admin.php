<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Crm\Admin		The /_admin panel of the Crm feature - see docs/features.md, "The panel"
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Crm {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Crm\Admin					"CRM" panel: the way to the surface of the CRM, the status of the core - what is there, what is missing and the lines a package added -
	 *										as the standard directory of the workbench, a button at the line that has a fix, and the data of packages that are gone, which a typed
	 *										confirmation takes away. The work with clients and projects is the surface's; this panel validates what it is posted and answers with the
	 *										codes of the failures a person can cause.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public static function perm(): string {
			return \Nino\Modules\Crm::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'crm/status'    => [ self::class, 'apiStatus' ],
				'crm/setup'     => [ self::class, 'apiSetup' ],
				'crm/drop-data' => [ self::class, 'apiDropData' ],
			];
		}

		public static function nav(): array {
			return [ 'crm', '/_admin/crm/nav', 30 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/**
		 *	The activity-log line of a completed action: the name of the line or of the package, which an action has checked against its shape before it did anything, and nothing else
		 *
		 *	@param		string		$action
		 *	@param		array 		$data					What the action was posted
		 *
		 *	@return 	string
		 */
		public static function log( string $action, array $data ): string {

			$key = is_string( $data['key'] ?? null ) === true && Register::word( $data['key'] ) === true ? ' '. $data['key'] : '';

			return match( $action ) {
				'crm/setup'     => 'Mended the line'. $key. ' of the status of the CRM',
				'crm/drop-data' => 'Deleted the data of the package'. $key,
				default         => '',
			};
		}

		/**
		 *	What the screen draws: the lines of the status, where the surface is, and the data of the packages that are gone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiStatus( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			\Nino\Http::ok( $request, self::screen( $appData ) );
		}

		/**
		 *	Mend one line of the status: its fix, run by its name. A line that has no fix, and a line nobody entered, are refused; no line is mended that was not named
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiSetup( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$key = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' );

			if( Register::word( $key ) === false ) {
				\Nino\Http::fail( $request, 400, 'the line is none', 'crm_setup_unknown', [], 'key' );
				return;
			}

			$done = Status::fix( $appData, $key );

			if( $done === 'unknown' )
				\Nino\Http::fail( $request, 404, 'there is no such line', 'crm_setup_unknown', [], 'key' );
			else if( $done === 'none' )
				\Nino\Http::fail( $request, 409, 'the line has no fix', 'crm_setup_none', [], 'key' );
			else if( $done === 'unreadable' )
				\Nino\Http::fail( $request, 503, 'the projects cannot be read', 'crm_projects_unreadable' );
			else if( $done !== true )
				\Nino\Http::fail( $request, 500, 'the line could not be mended ('. $done. ')', 'crm_setup_step', [ $key ] );
			else
				\Nino\Http::ok( $request, self::screen( $appData ) );
		}

		/**
		 *	Take away the data of a package that is gone: the tables it made and its row of the register. The package is named and its name typed again as the proof of it; what is
		 *	refused is the data of the core, of a package that is on, of one that is only switched off (it comes back with its data) and of one nobody entered
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiDropData( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = \Nino\Admin\Admin::postString( $data, 'key' );

			if( Register::word( $key ) === false ) {
				\Nino\Http::fail( $request, 400, 'the package is none', 'crm_drop_key', [], 'key' );
				return;
			}

			if( \Nino\Admin\Admin::postString( $data, 'typed' ) !== $key ) {
				\Nino\Http::fail( $request, 400, 'the name of the package was not typed', 'crm_drop_typed', [ $key ], 'typed' );
				return;
			}

			try {
				$dropped = Db::dropSchema( $appData, $key );
			}
			catch( \PDOException $e ) {
				\Nino\Http::fail( $request, 500, 'the database could not be opened', 'crm_database' );
				return;
			}

			if( is_int( $dropped ) === true )
				\Nino\Http::ok( $request, self::screen( $appData ) + [ 'dropped' => $dropped ] );
			else if( $dropped === 'unknown' )
				\Nino\Http::fail( $request, 404, 'there is no data of that package', 'crm_drop_unknown', [ $key ], 'key' );
			else if( $dropped === 'referenced' )
				\Nino\Http::fail( $request, 409, 'a table of the core or of another package points at that data', 'crm_drop_referenced', [ $key ], 'key' );
			else if( $dropped === 'failed' )
				\Nino\Http::fail( $request, 500, 'the data could not be taken away', 'crm_drop_failed', [ $key ] );
			else
				\Nino\Http::fail( $request, 409, 'the data of that package is not taken away', 'crm_drop_refused', [ $key ], 'key' );
		}

		/**
		 *	The answer of the status and of the actions that change it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											lines, whole, open (the address of the surface) and orphans
		 */
		private static function screen( array &$appData ): array {

			$lines = Status::lines( $appData );

			return [
				'lines'   => $lines,
				'whole'   => Status::whole( $lines ),
				// The way to the surface: the address of the page, on the host of the CRM where there is one
				'open'    => [ 'address' => Hosts::address( $appData, Surface::ROUTE ) ],
				// Only the data of a package that is gone: a package that is switched off but installed comes back with its data and is none of them
				'orphans' => array_values( array_map( static fn( array $orphan ): array => [ 'key' => $orphan['package'], 'package' => $orphan['package'], 'version' => $orphan['version'], 'tables' => count( $orphan['tables'] ) ], array_filter( Db::orphans( $appData ), static fn( array $orphan ): bool => $orphan['installed'] === false ) ) ),
			];
		}
	}
}
