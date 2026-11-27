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

			// A step of the test phase that reads what it needs to find its things before the rows go, when the suite asks for it: './crm-probe/gather' says how it behaves
			if( is_string( $appData['./crm-probe/gather'] ?? null ) === true )
				\Nino\Modules\Crm\Wipe::add( $appData, [ 'package' => 'crm-probe', 'key' => 'probeg', 'leftover' => [ self::class, 'wipeKept' ] ] + ( ( $appData['./crm-probe/g-count'] ?? false ) === true ? [ 'count' => true, 'rows' => [ self::class, 'wipeNoRows' ], 'rows_before' => 'records' ] : [] ) + ( $appData['./crm-probe/gather'] === 'none' ? [] : [ 'gather' => [ self::class, 'wipeGather' ] ] ) );

			// What the package adds to a client and to the list of clients, when the suite asks for it: './crm-probe/view' says how it behaves
			if( is_array( $appData['./crm-probe/view'] ?? null ) === true ) {
				\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Clients::VIEW, [ self::class, 'viewClient' ] );
				\Nino\Callbacks::registerCallback( $appData, \Nino\Modules\Crm\Clients::VIEWS, [ self::class, 'viewClients' ] );
			}

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

			// What the suite asks for: a package that is gone appears between the plan and the transaction of the deletion - the plan asks the hook of the holds, which is before the transaction
			if( $hook === \Nino\Modules\Crm\Erasure::HOLDS && ( $appData['./crm-probe/orphan-flip'] ?? false ) === true )
				\Nino\Modules\Crm\Db::exec( $appData, 'INSERT OR IGNORE INTO schema_versions ( package, version, tables, applied ) VALUES ( \'ghost\', 1, \'{}\', \'2026-01-01T00:00:00Z\' )' );

			// What the suite asks for: the same after the database step of the test phase, before the projects
			if( $hook === \Nino\Modules\Crm\Wipe::WIPED && is_string( $appData['./crm-probe/after-wait'] ?? null ) === true )
				\domWaitFile( $appData['./crm-probe/after-wait']. '-restored' );

			// What the suite asks for: the row of the run as it lies when the packages are told - it is kept to see that there was no moment without a row that can be read
			if( $hook === \Nino\Modules\Crm\Wipe::WIPED && ( $appData['./crm-probe/row-at-told'] ?? false ) === true )
				$appData['./crm-probe/row-seen'][] = ( \Nino\Modules\Crm\Db::row( $appData, 'SELECT value FROM settings WHERE key = \'wiping.run\'' ) ?? [ 'value' => null ] )['value'];

			// ...and how often the row was deleted before this: a table that a trigger of the suite fills at every delete of it
			if( $hook === \Nino\Modules\Crm\Wipe::WIPED && ( $appData['./crm-probe/row-at-told'] ?? false ) === true )
				$appData['./crm-probe/deleted-before'][] = (int) ( \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM zz_deleted' )['n'] ?? -1 );

			// What the suite asks for: the process ends when the first project is deleted
			if( $hook === \Nino\Modules\Crm\Projects::REMOVED && ( $appData['./crm-probe/removed-kills'] ?? false ) === true )
				posix_kill( getmypid(), 9 );

			// What the suite asks for: the process ends here as if it were killed from outside - the database step is committed, the projects are not deleted yet
			if( $hook === \Nino\Modules\Crm\Wipe::WIPED && ( $appData['./crm-probe/wiped-kills'] ?? false ) === true )
				posix_kill( getmypid(), 9 );
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

			// What the suite asks for: the gather of a deletion stays in the transaction for a while, a second process is told to go, and the second process of the suite asks for the write lock while it is there
			if( is_string( $appData['./crm-probe/gather-wait'] ?? null ) === true ) {
				file_put_contents( $appData['./crm-probe/gather-wait']. '-go', 'go' );
				$appData['./crm-probe/blocked'][] = \domWaitBlocked( $appData['./crm-probe/gather-wait'] );
				$appData['./crm-probe/peer'][] = isset( $GLOBALS['domPeerGather'] ) === true ? \domPeerAsk( $GLOBALS['domPeerGather'] ) : 'none';
			}

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

			// What the suite asks for: the process waits here, after the database step of the deletion, until a second process has written the database back (a restore)
			if( is_string( $appData['./crm-probe/after-wait'] ?? null ) === true )
				\domWaitFile( $appData['./crm-probe/after-wait']. '-restored' );

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

			if( ( $appData['./crm-probe/rows-throws'] ?? false ) === true )
				throw new \PDOException( 'the probe breaks the database step' );

			return $run( 'DELETE FROM crm_probe_items' );
		}

		/**
		 *	A step of the test phase that has a database step and counts: it deletes no row of its own, the count is its leftover's
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run
		 *
		 *	@return 	int
		 */
		public static function wipeNoRows( array &$appData, callable $run ): int {

			return 0;
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$done
		 *
		 *	@return 	bool
		 */
		public static function leftover( array &$appData, array &$done = [] ): bool {

			// What the suite asks for: the process ends here as if it were killed from outside, after the database step and before the run is over
			if( ( $appData['./crm-probe/leftover-kills'] ?? false ) === true )
				posix_kill( getmypid(), 9 );

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
		 *	What the step of the test phase needs to find later: the numbers of the clients, read while they are there. The suite makes it throw (throws) or answer what is no list (text)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|string
		 */
		public static function wipeGather( array &$appData ): array|string {

			$appData['./crm-probe/gathered'] = ( $appData['./crm-probe/gathered'] ?? 0 ) + 1;
			$how = (string) $appData['./crm-probe/gather'];

			// What the suite asks for: a second process is told to go (a file) and the gather stays in the transaction for a while, so that the process asks for the write lock while this holds it
			if( is_string( $appData['./crm-probe/gather-wait'] ?? null ) === true ) {
				file_put_contents( $appData['./crm-probe/gather-wait']. '-go', 'go' );
				$appData['./crm-probe/blocked'][] = \domWaitBlocked( $appData['./crm-probe/gather-wait'] );
			}

			if( $how === 'throws' )
				throw new \RuntimeException( 'the probe cannot read'. ' password=hunter2' );

			if( $how === 'text' )
				return 'no list';

			// What cannot be held whole: a number that is none, a text that is none, too deep, too large - and the two sizes at the limit
			$deep = [ 'end' => 1 ];

			for( $i = 0; $i < 40; $i++ )
				$deep = [ 'in' => $deep ];

			$limit = \Nino\Modules\Crm\Wipe::HELD_BYTES - strlen( json_encode( [ 'pad' => '' ] ) );

			$levels = static function( int $count ): array {

				$value = [ 'end' => 1 ];

				for( $i = 1; $i < $count; $i++ )
					$value = [ 'in' => $value ];

				return $value;
			};

			// A gather that writes - which is the package's fault - and one that says where it is asked: in the transaction that holds the lock, which a second connection cannot write in
			if( $how === 'write' )
				\Nino\Modules\Crm\Db::exec( $appData, 'INSERT INTO settings ( key, value ) VALUES ( \'probe.wrote\', \'yes\' )' );

			if( $how === 'lock' ) {

				$second = new \PDO( 'sqlite:'. \Nino\Filesystem::path( $appData, \Nino\Modules\Crm\Db::FILE ) );
				$second->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
				$second->setAttribute( \PDO::ATTR_TIMEOUT, 0 );

				try {
					$second->exec( 'INSERT INTO clients ( number, name, created, updated ) VALUES ( \'K-9999\', \'Between\', \'2026-01-01T00:00:00Z\', \'2026-01-01T00:00:00Z\' )' );
					$appData['./crm-probe/locked'] = false;
				}
				catch( \PDOException $e ) {
					$appData['./crm-probe/locked'] = true;
				}

				$appData['./crm-probe/in-transaction'] = \Nino\Modules\Crm\Db::pdo( $appData )->inTransaction();
			}

			// The same from a process of its own: the second process of the suite (domPeerAsk()) asks for the write lock while the gather is asked - what the locks of one process say proves nothing about another
			if( $how === 'lock-process' )
				$appData['./crm-probe/process'] = \domPeerAsk( $GLOBALS['domPeerGather'] );

			switch( $how ) {
				case 'deep28': return $levels( 28 );
				case 'deep29': return $levels( 29 );
				case 'inf': return [ 'x' => INF ];
				case 'utf8': return [ 'x' => "a\xffb" ];
				case 'deep': return $deep;
				case 'big': return [ 'pad' => str_repeat( 'x', $limit + 1 ) ];
				case 'exact': return [ 'pad' => str_repeat( 'x', $limit ) ];
			}

			return [
				'numbers' => array_column( \Nino\Modules\Crm\Db::rows( $appData, 'SELECT number FROM clients ORDER BY id' ), 'number' ),
				'emails' => array_column( \Nino\Modules\Crm\Db::rows( $appData, 'SELECT email FROM clients WHERE email <> \'\' ORDER BY id' ), 'email' ),
				'items' => (int) \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM crm_probe_items' )['n'],
			];
		}

		/**
		 *	What the step keeps of what it read: the suite finds it in a file of the store
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$done
		 *	@param		array 		$gathered
		 *
		 *	@return 	bool
		 */
		public static function wipeKept( array &$appData, array &$done, array $gathered ): mixed {

			if( ( $appData['./crm-probe/held-kills'] ?? false ) === true )
				posix_kill( getmypid(), 9 );

			// What the suite asks for: a leftover that answers what is not true - null, 0, an empty text, a text, a list - and has done nothing
			if( array_key_exists( './crm-probe/held-answer', $appData ) === true )
				return $appData['./crm-probe/held-answer'];

			$appData['./crm-probe/handed'][] = $gathered;

			// What the suite asks for: the step counts what its leftover took, as a package that has rows and a leftover does
			if( ( $appData['./crm-probe/g-count'] ?? false ) === true )
				$done['probeg'] = (int) ( $done['probeg'] ?? 0 ) + count( (array) ( $gathered['numbers'] ?? [] ) );

			// What the contract asks of a leftover: it is given what was read before the rows went, maybe days later - the number may be somebody else's now, and what is somebody's is not taken
			$store = (array) \Nino\Filesystem::getFileContent( $appData, '/data/crm-probe-owned.php', [] );

			foreach( (array) ( $gathered['numbers'] ?? [] ) as $number ) {

				if( \Nino\Modules\Crm\Db::row( $appData, 'SELECT id FROM clients WHERE number = :number', [ 'number' => (string) $number ] ) !== null ) {
					$appData['./crm-probe/skipped'][] = (string) $number;

					continue;
				}

				unset( $store[(string) $number] );
			}

			\Nino\Filesystem::putFileContent( $appData, '/data/crm-probe-owned.php', $store );

			return \Nino\Filesystem::putFileContent( $appData, '/data/crm-probe-kept.php', [ 'gathered' => $gathered, 'clients' => (int) \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM clients' )['n'] ] ) !== false;
		}

		/**
		 *	The part of the probe in the card of a client: how many items it holds. The suite steers it in './crm-probe/view': throw (it breaks), tamper (it writes into what is the core's), also (parts it adds under other keys),
		 *	bad (a value that is no plain one)
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$argument
		 *
		 *	@return 	mixed
		 */
		public static function viewClient( array &$appData, mixed &$argument ): mixed {

			$steer = (array) $appData['./crm-probe/view'];
			$appData['./crm-probe/viewed'][] = $argument;

			if( ( $steer['throw'] ?? false ) === true )
				throw new \RuntimeException( 'the probe breaks the card' );

			$id = (int) ( $argument['client']['id'] ?? 0 );
			$argument['packages']['crm-probe'] = [ 'items' => (int) \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM crm_probe_items WHERE client_id = :id', [ 'id' => $id ] )['n'], 'tags' => [ 'a', 'b' ], 'nested' => [ 'x' => [ 'y' => 1 ] ] ];

			if( isset( $steer['bad'] ) === true )
				$argument['packages']['crm-probe']['bad'] = self::bad( (string) $steer['bad'] );

			// A part of the size the suite asks for, as serialize() counts it: size is the whole part
			if( isset( $steer['size'] ) === true )
				$argument['packages']['crm-probe'] = [ 'pad' => str_repeat( 'x', self::padFor( \Nino\Modules\Crm\Clients::PART_BYTES + (int) $steer['size'] ) ) ];

			// A value the listener keeps a reference to, and another listener changes after this one answered
			if( ( $steer['live'] ?? false ) === true )
				$argument['packages']['crm-probe']['live'] = &$appData['./crm-probe/live'];

			foreach( (array) ( $steer['also'] ?? [] ) as $key => $part )
				$argument['packages'][$key] = $part;

			// A listener that writes into what is the core's: the client it was given, and a field of the answer that is none of its own
			if( ( $steer['tamper'] ?? false ) === true ) {
				$argument['client']['name'] = 'Hijacked';
				$argument['client']['number'] = 'K-9999';
				$argument['name'] = 'Hijacked';
			}

			return $argument;
		}

		/**
		 *	How long the text of a part of the form [ 'pad' => text ] is that makes the part $total bytes as serialize() counts them
		 *
		 *	@param		int				$total
		 *
		 *	@return 	int
		 */
		private static function padFor( int $total ): int {

			$length = $total;

			while( strlen( serialize( [ 'pad' => str_repeat( 'x', $length ) ] ) ) > $total )
				$length--;

			return $length;
		}

		/**
		 *	A value that is no plain one, or is one at its limit: object, inf, nan, utf8 (a text that is no UTF-8), utf8key (a key that is none), deep7 (seven levels), deep6 (six: the most there is), big (more than the part may be)
		 *
		 *	@param		string		$how
		 *
		 *	@return 	mixed
		 */
		private static function bad( string $how ): mixed {

			$deep = static function( int $levels ): array {

				$value = [ 'end' => 1 ];

				for( $i = 1; $i < $levels; $i++ )
					$value = [ 'in' => $value ];

				return $value;
			};

			return match( $how ) {
				'object' => new \stdClass(),
				'closure' => static fn(): int => 1,
				'inf' => INF,
				'nan' => NAN,
				'utf8' => "a\xffb",
				'utf8key' => [ "k\xff" => 1 ],
				'deep7' => $deep( 6 ),
				'deep6' => $deep( 5 ),
				'big' => str_repeat( 'x', 20000 ),
				default => null,
			};
		}

		/**
		 *	The part of the probe in the list of the clients: how many items each client holds, under the id of the client; a client that holds none has no entry. The steering is the one of viewClient()
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			&$argument
		 *
		 *	@return 	mixed
		 */
		public static function viewClients( array &$appData, mixed &$argument ): mixed {

			$steer = (array) $appData['./crm-probe/view'];
			$appData['./crm-probe/viewed'][] = $argument;

			if( ( $steer['throw'] ?? false ) === true )
				throw new \RuntimeException( 'the probe breaks the list' );

			$part = [];

			foreach( \Nino\Modules\Crm\Db::rows( $appData, 'SELECT client_id, COUNT(*) AS n FROM crm_probe_items GROUP BY client_id' ) as $row )
				$part[(int) $row['client_id']] = [ 'items' => (int) $row['n'] ];

			// An id of nobody in the list, which the list does not read
			$part[99999] = [ 'items' => 7 ];

			if( isset( $steer['bad'] ) === true && $steer['bad'] === 'entry' )
				$part[(int) array_key_first( $part )] = 'no list of fields';
			elseif( isset( $steer['bad'] ) === true )
				$part[(int) array_key_first( $part )]['bad'] = self::bad( (string) $steer['bad'] );

			if( ( $steer['live'] ?? false ) === true )
				$part[(int) array_key_first( $part )]['live'] = &$appData['./crm-probe/live'];

			// An entry of the size the suite asks for, one for each client with an item, and a client the list does not know by a text
			if( isset( $steer['size'] ) === true )
				$part[(int) array_key_first( $part )] = [ 'pad' => str_repeat( 'x', self::padFor( \Nino\Modules\Crm\Clients::PART_BYTES + (int) $steer['size'] ) ) ];

			if( isset( $steer['textid'] ) === true )
				$part['abc'] = [ 'items' => 1 ];

			// The whole list at exactly LIST_BYTES plus an offset: every client an entry, the sizes as even as they can be
			if( isset( $steer['listsize'] ) === true ) {

				$part = [];
				$count = count( $argument['clients'] );
				$total = \Nino\Modules\Crm\Clients::LIST_BYTES + (int) $steer['listsize'];

				foreach( array_values( $argument['clients'] ) as $at => $client )
					$part[(int) $client['id']] = [ 'pad' => str_repeat( 'x', self::padFor( intdiv( $total, $count ) + ( $at < $total % $count ? 1 : 0 ) ) ) ];
			}

			// The budget of a package over the whole list: every client gets an entry of bulk bytes
			if( isset( $steer['bulk'] ) === true )
				foreach( $argument['clients'] as $client )
					$part[(int) $client['id']] = [ 'pad' => str_repeat( 'x', (int) $steer['bulk'] ) ];

			$argument['packages']['crm-probe'] = $part;

			foreach( (array) ( $steer['also'] ?? [] ) as $key => $other )
				$argument['packages'][$key] = $other;

			if( ( $steer['tamper'] ?? false ) === true ) {
				$argument['clients'] = [];
				$argument['name'] = 'Hijacked';
			}

			return $argument;
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
