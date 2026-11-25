<?php
declare(strict_types=1);

namespace Nino\Modules\CrmProbe {

	/**
	 *	Domain		What the package of the suite enters at the places of the second part of the core: records of kinds of its own, entries of the logbook, actions of the endpoint (one that writes, one that takes a file, one that
	 *						needs a feature that is not there), an element type the screen of the values edits, the hooks about a client and about a project, what it holds of a client in a table and in a file outside the database
	 *						(which fails once where the suite says so), the test phase, the overview, a switch of the notifications and the settings. The suite switches it on with './crm-probe/domain' in app data and steers it with
	 *						the keys below './crm-probe/'. Nothing of it is a feature of the catalogue.
	 */
	class Domain {

		public const string STORE = '/data/crm-probe-store.php';

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Modules\Crm\Logbook::addKinds( $appData, [ 'probe-happened', 'probe-agreed', 'probe-revoked' ], [ 'package' => 'crm-probe' ] );

			\Nino\Modules\Crm\Records::addKind( $appData, [ 'package' => 'crm-probe', 'kind' => 'probe-agree', 'thing' => 'probe-item', 'purposes' => [ 'alpha', 'beta' ], 'logbook' => 'probe-agreed', 'owner' => [ self::class, 'ownerOf' ], 'label' => [ self::class, 'labelOf' ] ] );
			\Nino\Modules\Crm\Records::addKind( $appData, [ 'package' => 'crm-probe', 'kind' => 'probe-revoke', 'parent' => 'probe-agree', 'logbook' => 'probe-revoked' ] );
			\Nino\Modules\Crm\Records::addKind( $appData, [ 'package' => 'crm-probe', 'kind' => 'probe-visit', 'foreign' => true ] );

			\Nino\Modules\Crm\Api::add( $appData, [ 'crm-probe/read' => [ self::class, 'apiRead' ], 'crm-probe/write' => [ self::class, 'apiWrite' ], 'crm-probe/open' => [ self::class, 'apiOpen' ] ], [ 'package' => 'crm-probe' ] );
			\Nino\Modules\Crm\Api::add( $appData, [ 'crm-probe/upload' => [ self::class, 'apiUpload' ] ], [ 'package' => 'crm-probe', 'files' => [ 'crm-probe/upload' ] ] );
			\Nino\Modules\Crm\Api::add( $appData, [ 'crm-probe/needy' => [ self::class, 'apiRead' ] ], [ 'package' => 'crm-probe', 'needs' => 'crm-probe-absent' ] );

			\Nino\Modules\Crm\Values::addType( $appData, [ 'type' => 'crm-probe-values', 'screen' => 'values', 'rank' => 300, 'package' => 'crm-probe' ] );

			\Nino\Modules\Crm\Erasure::add( $appData, [ 'package' => 'crm-probe', 'key' => 'fixedp', 'before' => 'records', 'count' => [ self::class, 'countFixed' ], 'reason' => 'probefixed', 'answer' => [ 409, 'a row of the probe is fixed', 'crm_probe_fixed' ] ] );
			\Nino\Modules\Crm\Erasure::add( $appData, [
				'package' => 'crm-probe', 'key' => ( $appData['./crm-probe/only-q'] ?? false ) === true ? 'probeq' : 'probe', 'before' => 'records', 'count' => [ self::class, 'count' ], 'rows' => [ self::class, 'rows' ], 'rows_before' => 'records', 'shared' => [ self::class, 'shared' ], 'gather' => [ self::class, 'gather' ], 'finish' => [ self::class, 'finish' ], 'perm' => '/_admin/crm-probe/manage',
			] );
			\Nino\Modules\Crm\Wipe::add( $appData, [ 'package' => 'crm-probe', 'key' => 'probe', 'count' => true, 'rows' => [ self::class, 'wipeRows' ], 'rows_before' => 'records', 'leftover' => [ self::class, 'leftover' ], 'perm' => '/_admin/crm-probe/manage' ] );

			\Nino\Modules\Crm\Notifications::add( $appData, [ 'package' => 'crm-probe', 'key' => 'probe_owner', 'rank' => 100, 'read' => [ self::class, 'readSwitch' ], 'write' => [ self::class, 'writeSwitch' ] ] );

			// A veto the suite switches on: the element of the probe is not deleted, the way a listener of the kernel's veto says it
			\Nino\Callbacks::registerCallback( $appData, '/nino/elements/delete/crm-probe-values', [ self::class, 'veto' ] );

			// One listener for every hook there is: what it was asked is kept for the suite, and it answers as the suite steers it for that hook
			foreach( [ \Nino\Modules\Crm\Clients::ADDED, \Nino\Modules\Crm\Clients::CHANGED, \Nino\Modules\Crm\Clients::ACTIVE, \Nino\Modules\Crm\Erasure::HOLDS, \Nino\Modules\Crm\Erasure::DELETED, \Nino\Modules\Crm\Wipe::WIPED,
				\Nino\Modules\Crm\Projects::ADDED, \Nino\Modules\Crm\Projects::REMOVED, \Nino\Modules\Crm\Projects::HOLDS, \Nino\Modules\Crm\Projects::FIGURES, \Nino\Modules\Crm\Projects::WITHHELD,
				\Nino\Modules\Crm\Overview::HOOK, \Nino\Modules\Crm\Settings\Api::SAVE, \Nino\Modules\Crm\Values::CHECK, \Nino\Modules\Crm\Values::START ] as $hook )
				\Nino\Callbacks::registerCallback( $appData, $hook, static function( array &$appData, mixed &$argument ) use ( $hook ): mixed {
					return self::told( $appData, $hook, $argument );
				} );
		}

		/**
		 *	What a hook was asked is kept for the suite, and the listener answers as the suite steers it for that hook: throw (it breaks), merge (keys it puts into the argument)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$hook
		 *	@param		mixed			$argument
		 *
		 *	@return 	mixed
		 */
		private static function told( array &$appData, string $hook, mixed $argument ): mixed {

			$appData['./crm-probe/told'][$hook][] = $argument;
			$steer = $appData['./crm-probe/steer'][$hook] ?? [];

			if( ( $steer['throw'] ?? false ) === 'pdo' )
				throw new \PDOException( 'the probe breaks the database' );

			if( ( $steer['throw'] ?? false ) === true )
				throw new \RuntimeException( 'the probe breaks' );

			if( is_array( $argument ) === false || count( $appData['./crm-probe/told'][$hook] ) <= (int) ( $steer['after'] ?? 0 ) )
				return null;

			foreach( (array) ( $steer['merge'] ?? [] ) as $key => $value )
				$argument[$key] = $value;

			return $argument;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		mixed			&$typeData
		 *
		 *	@return 	mixed
		 */
		public static function veto( array &$appData, mixed &$typeData ): mixed {

			if( ( $appData['./crm-probe/veto'] ?? false ) !== false ) {

				// A veto that gives a reason says it, one that does not leaves the word of the kernel
				if( $appData['./crm-probe/veto'] === 'reason' )
					trigger_error( 'The probe keeps it.' );

				return false;
			}

			return $typeData;
		}

		/**
		 *	An action that checks nothing itself: what keeps others from it is the endpoint
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiOpen( array &$appData, array &$request ): void {

			\Nino\Http::ok( $request, [ 'open' => true ] );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiRead( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [ 'data' => \Nino\Admin\Admin::postData(), 'post' => array_keys( $_POST ) ] );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiWrite( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			\Nino\Modules\Crm\Api::made( $appData, 'The probe wrote '. \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'what', 'something' ) );

			// A package that says what it did and then refuses: the endpoint writes no line for an answer that is none of 200
			if( ( \Nino\Admin\Admin::postData()['refuse'] ?? false ) === true ) {
				\Nino\Http::fail( $request, 409, 'the probe refuses', 'crm_probe_refused' );
				return;
			}

			\Nino\Http::ok( $request, [ 'done' => true ] );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiUpload( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [ 'data' => \Nino\Admin\Admin::postData(), 'files' => array_keys( $_FILES ) ] );
		}

		/**
		 *	The client that holds an item of the probe
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id
		 *
		 *	@return 	int|null
		 */
		public static function ownerOf( array &$appData, string $id ): ?int {

			$row = \Nino\Modules\Crm\Db::row( $appData, 'SELECT client_id FROM crm_probe_items WHERE id = :id', [ 'id' => (int) $id ] );

			return $row === null ? null : (int) $row['client_id'];
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$id
		 *
		 *	@return 	string
		 */
		public static function labelOf( array &$appData, string $id ): string {

			return (string) ( \Nino\Modules\Crm\Db::row( $appData, 'SELECT label FROM crm_probe_items WHERE id = :id', [ 'id' => (int) $id ] )['label'] ?? '' );
		}

		/**
		 *	How many rows of the probe the client has
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client
		 *
		 *	@return 	int
		 */
		public static function count( array &$appData, array $client ): int {

			return (int) \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM crm_probe_items WHERE client_id = :id AND label <> \'fixed\'', [ 'id' => $client['id'] ] )['n'];
		}

		/**
		 *	How many rows of the probe the client has that are fixed
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client
		 *
		 *	@return 	int
		 */
		public static function countFixed( array &$appData, array $client ): int {

			// What the suite asks for: a row that is fixed only after the plan was made - the first count says none, the later ones say one
			if( ( $appData['./crm-probe/count-flip'] ?? false ) === true ) {
				$appData['./crm-probe/count-seen'] = (int) ( $appData['./crm-probe/count-seen'] ?? 0 ) + 1;

				return $appData['./crm-probe/count-seen'] === 1 ? 0 : 1;
			}

			return (int) \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM crm_probe_items WHERE client_id = :id AND label = \'fixed\'', [ 'id' => $client['id'] ] )['n'];
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		callable	$run
		 *	@param		array 		$client
		 *
		 *	@return 	int
		 */
		public static function rows( array &$appData, callable $run, array $client ): int {

			return $run( 'DELETE FROM crm_probe_items WHERE client_id = :id', [ 'id' => $client['id'] ] );
		}

		/**
		 *	The projects of the client that the probe says another client names: the ones the suite lists
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array 		$projects
		 *
		 *	@return 	array
		 */
		public static function shared( array &$appData, int $id, array $projects ): array {

			return array_values( array_intersect( $projects, (array) ( $appData['./crm-probe/shared'] ?? [] ) ) );
		}

		/**
		 *	What the finish step needs to find: the ids of the items, read before they are deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client
		 *
		 *	@return 	array
		 */
		public static function gather( array &$appData, array $client ): array {

			// What the suite asks for: a package that is gone appears between the plan and the transaction of the deletion - the register has a row of a package nobody stands for
			if( ( $appData['./crm-probe/orphan-flip'] ?? false ) === true )
				\Nino\Modules\Crm\Db::exec( $appData, 'INSERT OR IGNORE INTO schema_versions ( package, version, tables, applied ) VALUES ( \'ghost\', 1, \'{}\', \'2026-01-01T00:00:00Z\' )' );

			if( ( $appData['./crm-probe/gather-nested'] ?? false ) === true )
				return [ 'items' => [ 1, [ 2 ] ], 'who' => $client['number'] ];

			return [ 'items' => array_map( 'intval', array_column( \Nino\Modules\Crm\Db::rows( $appData, 'SELECT id FROM crm_probe_items WHERE client_id = :id', [ 'id' => $client['id'] ] ), 'id' ) ), 'who' => $client['number'] ];
		}

		/**
		 *	The step outside the database: the store of the probe is a file, and the step fails once where the suite says so
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$gathered
		 *	@param		array 		&$done
		 *
		 *	@return 	bool
		 */
		public static function finish( array &$appData, array $gathered, array &$done ): bool {

			if( ( $appData['./crm-probe/fail-once'] ?? false ) === true ) {
				unset( $appData['./crm-probe/fail-once'] );

				return false;
			}

			// What the suite asks for: a package that writes to counts that are not its own, and to its own below what stood there
			if( ( $appData['./crm-probe/finish-tamper'] ?? false ) === true ) {
				$done['records'] = 99;
				$done['clients'] = 0;
				$done['probe'] = -5;
				$done['other'] = 1;

				return true;
			}

			$store = (array) \Nino\Filesystem::getFileContent( $appData, self::STORE, [] );
			$kept = array_values( array_filter( $store, static fn( mixed $one ): bool => in_array( (string) $one, array_map( 'strval', (array) ( $gathered['items'] ?? [] ) ), true ) === false ) );
			$done['probe'] = ( $done['probe'] ?? 0 ) + count( $store ) - count( $kept );

			return \Nino\Filesystem::putFileContent( $appData, self::STORE, $kept ) !== false;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		callable	$run
		 *
		 *	@return 	int
		 */
		public static function wipeRows( array &$appData, callable $run ): int {

			return $run( 'DELETE FROM crm_probe_items' );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$done
		 *
		 *	@return 	bool
		 */
		public static function leftover( array &$appData, array &$done = [] ): bool {

			if( ( $appData['./crm-probe/leftover-fails'] ?? false ) === true )
				return false;

			// What the suite asks for: a package that writes to a count that is not its own
			if( ( $appData['./crm-probe/leftover-tamper'] ?? false ) === true ) {
				$done['records'] = 99;
				$done['probe'] = 'many';
				$done['other'] = 1;
			}
			else
				$done['probe'] = (int) ( $done['probe'] ?? 0 ) + count( (array) \Nino\Filesystem::getFileContent( $appData, self::STORE, [] ) );

			return \Nino\Filesystem::putFileContent( $appData, self::STORE, [] ) !== false;
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function readSwitch( array &$appData ): bool {

			return ( $appData['./crm-probe/switch'] ?? false ) === true;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		bool			$on
		 *
		 *	@return 	true|string
		 */
		public static function writeSwitch( array &$appData, bool $on ): true|string {

			if( ( $appData['./crm-probe/switch-fails'] ?? false ) === true )
				return 'failed';

			$appData['./crm-probe/switch'] = $on;

			return true;
		}
	}
}
