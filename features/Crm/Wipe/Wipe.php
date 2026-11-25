<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Wipe		The test phase: everything the clients moved is deleted and everything of the company stays. The button at the foot of the settings of the surface (crm/wipe, see Wipe\Api) runs run().
	 *
	 *							What goes - the movements of the clients:
	 *							- the tables clients, records, logbook, project_shares, project_states, tasks and sequences of the database, and the tables the packages entered (add()) - and the journal rows of the deletions of single clients
	 *								that were not finished (settings, erasing.*) -, in one transaction: where it fails, nothing is deleted. The numbers of the clients begin again (and the sequences of the packages with them); the ids of the records
	 *								and of the logbook count on (AUTOINCREMENT), so that a screen that still shows an old one cannot write into a new one - the ids of the clients are made anew, as the table has no such counter
	 *							- the elements of the type /crm-projects (the favorites and the phases of the projects were in project_states; the projects number from 00001 again, or from the start number)
	 *							- what a package holds outside the database, which it removes in its leftover
	 *
	 *							What stays - the company: the table settings (and with it the notifications, the start numbers and the wordmark), the table owner_devices, the elements of the type /crm-phases, the templates, the texts
	 *							and pictures of the site, the settings of the mail, the activity log, the icons of the app, the state of the setup.
	 *
	 *							The packages are told when the database step is through (told(), the hook WIPED): a package that kept a client number or the id of a project forgets it. The core names none of them.
	 *
	 *							The runs are idempotent: a second one finds nothing and answers zeros. The steps after the database go one after the other - where one fails, the run stops there and answers what it was (a string), and a
	 *							second press finishes the rest; the database step is the only one that is atomic. Where the data of a package that is gone is in the database (Db::orphans()), nothing is wiped: its tables may name the clients.
	 *							A destructive call checks the right itself: the account holds the permission of the CRM and of every panel whose data goes along, or the answer is denied and nothing changes.
	 *
	 *	@package						Dape/Nino
	 */
	class Wipe {

		/**	The hook that tells the packages the clients are gone: what they keep that names a client by its number or by what it was made of is theirs to forget, because the numbers begin again. The argument is the list gone (key => how many) and failed (the keys of those that could not)	*/
		public const string WIPED = '/crm/wiped';

		/**	The counts of an answer, in the order the surface names them; the packages that count add theirs	*/
		public const array COUNTS = [ 'clients', 'projects', 'records', 'logbook', 'shares' ];

		/**	The steps of the core in the transaction of the database step, in the order they run: a package puts its rows in front of one of them (add(), rows_before)	*/
		public const array STEPS = [ 'records', 'shares', 'clients', 'logbook', 'tasks', 'sequences' ];

		// The lock that lets one run at a time delete
		private const string LOCK = '/data/crm-wipe';

		// The key of the packages that were entered, in the app data of the request
		private const string REGISTER = './crm/wipe/tables';

		/**
		 *	Enter what a package holds of the clients: the core deletes its own tables only, and a package that keeps rows of the clients in a table of its own says how they go. The description: package (the key of the feature that enters it, see Register::package()), key (the name of its step, and of its
		 *	count where it has one: lower case letters, at most twenty, none of COUNTS), rows (a callable [ class, method ] ( array &$appData, callable $run ): int|void that deletes them - inside the transaction of the database step:
		 *	$run( string $sql, array $params = [] ): int runs one statement of it and answers how many rows it changed, a statement that would end the transaction is refused (Db::runner())), rows_before (a step of STEPS the rows run in
		 *	front of; none: in front of sequences, after the tasks), count (true: the answer of rows is the number the answer of the run carries under key; default false), before (with count: a key of COUNTS the count stands in front of; none: at
		 *	the end), leftover (a callable [ class, method ] ( array &$appData, array &$done ): bool that removes what the run leaves behind of the package outside the database - files -, once the other steps are through; false stops the run at the
		 *	package, and the next press asks it again; $done is the counts so far, where it adds to its own key - one that count => true gave it - and to no other, as finish does at the deletion of a client) and perm (the permission of the panel of the package that an account needs for the run). Every callable is a method of a class in the namespace of the package that enters it (Register::owned()): a closure, a function and a class of another namespace are refused. A key another package has, a key of COUNTS, a field that is none and a description that
		 *	is none are refused. The same description again is as good as the first time
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *
		 *	@return 	bool											false, with a warning, where the description is refused
		 */
		public static function add( array &$appData, array $spec ): bool {

			return Register::add( $appData, self::REGISTER, 'test phase', [ $spec ], [
				'id' => 'key',
				'idRule' => static fn( string $key ): bool => preg_match( '/^[a-z]{1,20}$/D', $key ) === 1 && in_array( $key, self::COUNTS, true ) === false,
				'fields' => [ 'package' => Register::REQUIRED, 'rows' => null, 'count' => false, 'before' => null, 'rows_before' => null, 'leftover' => null, 'perm' => null ],
				'rules' => [
					'package' => Register::packageRule( $appData ),
					'count' => static fn( mixed $count ): bool => is_bool( $count ),
					'before' => static fn( mixed $before ): bool => $before === null || in_array( $before, self::COUNTS, true ),
					'rows' => static function( mixed $rows, array $full ) use ( &$appData ): bool { return $rows === null || Register::owned( $appData, $full['package'], $rows, 2, [ 0 ] ); },
					'leftover' => static function( mixed $leftover, array $full ) use ( &$appData ): bool { return $leftover === null || Register::owned( $appData, $full['package'], $leftover, 2, [ 0, 1 ] ); },
					'rows_before' => static fn( mixed $step ): bool => $step === null || in_array( $step, self::STEPS, true ),
					'perm' => static fn( mixed $perm ): bool => $perm === null || ( is_string( $perm ) === true && preg_match( '~^/_admin/[a-z0-9/-]{1,80}$~D', $perm ) === 1 ),
				],
				'refuse' => static fn( array $spec ): string => match( true ) {
					$spec['rows'] === null && $spec['leftover'] === null => 'rows',
					$spec['rows'] === null && ( $spec['count'] === true || $spec['rows_before'] !== null ) => 'rows',
					$spec['before'] !== null && $spec['count'] === false => 'before',
					default => '',
				},
			] );
		}

		/**
		 *	What the packages entered, in the order they were entered
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => description
		 */
		public static function entered( array &$appData ): array {

			return Register::all( $appData, self::REGISTER );
		}

		/**
		 *	The names of the counts of an answer: those of COUNTS, with those of the packages that count each in front of the key it names, or at the end
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of keys
		 */
		public static function keys( array &$appData ): array {

			$entered = array_filter( self::entered( $appData ), static fn( array $spec ): bool => $spec['count'] === true );
			$keys = [];

			foreach( self::COUNTS as $key ) {

				foreach( $entered as $name => $spec )
					if( $spec['before'] === $key )
						$keys[] = (string) $name;

				$keys[] = $key;
			}

			foreach( $entered as $name => $spec )
				if( $spec['before'] === null )
					$keys[] = (string) $name;

			return $keys;
		}

		/**
		 *	The permissions a run needs besides the CRM's: the Elements panel's (the projects are elements) and the panel of every package that entered one for what it holds, in the test phase or in the deletion of a client.
		 *	An account that lacks one is refused whole
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of permissions
		 */
		public static function permissions( array &$appData ): array {

			$perms = [ Values::PERM ];

			foreach( array_merge( self::entered( $appData ), Erasure::entered( $appData ) ) as $spec )
				if( $spec['perm'] !== null )
					$perms[] = (string) $spec['perm'];

			return array_values( array_unique( $perms ) );
		}

		/**
		 *	Whether the account that is signed in holds the permission of every panel whose data a run takes along
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function permitted( array &$appData ): bool {

			foreach( self::permissions( $appData ) as $perm )
				if( \Nino\Auth::checkPermission( $appData, $perm ) === false )
					return false;

			return true;
		}

		/**
		 *	The word that has to be typed, exactly, before anything is deleted: the word of the language the account works in
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function word( array &$appData ): string {

			return Values::word( $appData, '[[/_admin/crm/wipe/word]]' );
		}

		/**
		 *	Delete the movements of the clients (see the class)
		 *
		 *	@param		array 		&$appData
		 *	@param		array|null	&$done					The counts so far, also where a step failed
		 *
		 *	@return 	array|string							The counts (keys() => how many went); or why not: denied (the account may not), orphans (the data of a package that is gone is in the database), lock (another run holds it and did not let go), projects, or the key of a package whose leftover failed (step:<key>) - the database step is atomic, and a \PDOException is the database refusing, with nothing deleted
		 */
		public static function run( array &$appData, ?array &$done = null ): array|string {

			$done = array_fill_keys( self::keys( $appData ), 0 );

			if( \Nino\Auth::getCurrentUser( $appData ) === false || \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false || self::permitted( $appData ) === false )
				return 'denied';

			$orphans = Erasure::orphanKeys( $appData );

			if( $orphans === null )
				return 'busy';

			if( $orphans !== [] )
				return 'orphans';

			$swept = Projects::sweep( $appData );

			if( is_string( $swept ) === true )
				return $swept;

			// A deletion that was begun is finished first: its journal is the only word of where a package keeps what it has not yet taken
			if( Erasure::pending( $appData ) !== [] )
				return 'open';

			// One run at a time: a second press at the same moment waits here, finds nothing and answers zeros
			if( \Nino\Filesystem::lockFile( $appData, self::LOCK ) === false )
				return 'lock';

			try {
				return self::step( $appData, $done );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, self::LOCK );
			}
		}

		/**
		 *	The steps of a run, under its lock
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$counts				The counts so far, which the caller keeps for a step that fails
		 *
		 *	@return 	array|string
		 */
		private static function step( array &$appData, array &$counts ): array|string {

			$counts = self::tables( $appData, $counts );

			// The clients are gone from the database and their numbers begin again: a package that kept a number is told now, before any step that can fail, and a second press tells it once more
			self::told( $appData );

			if( self::projects( $appData, $counts ) === false )
				return 'projects';

			foreach( self::entered( $appData ) as $key => $package ) {

				if( $package['leftover'] === null )
					continue;

				// What the package says it removed goes into its own count - and into no other: it is given a copy of the counts, and only its own key is taken back from it
				$mine = $counts;

				try {
					$ended = call_user_func_array( $package['leftover'], [ &$appData, &$mine ] ) !== false;
				}
				catch( \Throwable $e ) {
					trigger_error( 'Crm: the package '. $key. ' could not remove what it kept of the clients', E_USER_WARNING );
					$ended = false;
				}

				if( array_key_exists( $key, $counts ) === true && is_int( $mine[$key] ?? null ) === true && $mine[$key] >= $counts[$key] )
					$counts[$key] = $mine[$key];

				if( $ended === false )
					return 'step:'. $key;
			}

			return $counts;
		}

		/**
		 *	Tell the packages the clients are gone (WIPED). A package that cannot forget what it kept says so in failed; that is a warning in the log and no failure of the run, and the next run asks it again
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		private static function told( array &$appData ): void {

			$args = [ 'gone' => [], 'failed' => [] ];

			\Nino\Callbacks::doCallbacks( $appData, self::WIPED, $args );

			foreach( is_array( $args ) === true && is_array( $args['failed'] ?? null ) === true ? $args['failed'] : [] as $key )
				trigger_error( 'Crm: the package '. ( is_string( $key ) === true && Register::word( $key ) === true ? $key : '?' ). ' could not forget what it kept of the clients', E_USER_WARNING );
		}

		/**
		 *	The database: the rows of the clients and of what hangs on them, in one transaction. A row that others name goes after the rows that name it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$counts
		 *
		 *	@return 	array											The counts with clients, records, logbook and shares, and those of the packages
		 */
		private static function tables( array &$appData, array $counts ): array {

			$pdo = Db::pdo( $appData );

			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$run = Db::runner( $pdo );

				// What the packages keep of the clients goes in the same transaction, each in front of the step of the core it names
				$packages = static function( string $step ) use ( &$appData, $run, &$counts ): void {

					foreach( self::entered( $appData ) as $key => $package ) {

						if( $package['rows'] === null || ( $package['rows_before'] ?? 'sequences' ) !== $step )
							continue;

						$gone = call_user_func_array( $package['rows'], [ &$appData, $run ] );

						if( $package['count'] === true )
							$counts[$key] = (int) $gone;
					}
				};

				$packages( 'records' );
				$counts['records'] = self::peel( $run, 'records', 'parent' );
				$packages( 'shares' );
				$counts['shares'] = (int) $pdo->exec( 'DELETE FROM project_shares' );
				$packages( 'clients' );
				$counts['clients'] = (int) $pdo->exec( 'DELETE FROM clients' );
				$packages( 'logbook' );
				$counts['logbook'] = (int) $pdo->exec( 'DELETE FROM logbook' );
				$packages( 'tasks' );
				$pdo->exec( 'DELETE FROM project_states' );
				$pdo->exec( 'DELETE FROM tasks' );
				$packages( 'sequences' );
				$pdo->exec( 'DELETE FROM sequences' );

				// The deletions of single clients that were not finished (Erasure) are moot: their clients are gone, and the numbers begin anew
				$pdo->exec( 'DELETE FROM settings WHERE key LIKE \''. Erasure::JOURNAL. '%\'' );
				$pdo->exec( 'COMMIT' );
				Db::drop( $appData );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			return $counts;
		}

		/**
		 *	Delete all the rows of a table that names itself in one of its columns: the rows nobody names first, then the ones they named, until none is left. A package whose table names itself calls it from its rows (add())
		 *
		 *	@param		callable	$run					( string $sql, array $params = [] ): int, a statement of the transaction
		 *	@param		string		$table				The name of a table: lower case letters and underscores
		 *	@param		string		$column				The column that names the id of another row of the table
		 *
		 *	@return 	int												How many rows went
		 */
		public static function peel( callable $run, string $table, string $column ): int {

			if( preg_match( '/^[a-z_]{1,40}$/D', $table ) !== 1 || preg_match( '/^[a-z_]{1,40}$/D', $column ) !== 1 )
				throw new \InvalidArgumentException( 'Crm: the table and the column of a peel are names' );

			$gone = 0;

			do {
				$step = $run( 'DELETE FROM '. $table. ' WHERE id NOT IN ( SELECT '. $column. ' FROM '. $table. ' WHERE '. $column. ' IS NOT NULL )' );
				$gone += $step;
			}
			while( $step > 0 );

			// A cycle is none the module can make: the last statement refuses it with the constraint, and the transaction goes back
			return $gone + $run( 'DELETE FROM '. $table );
		}

		/**
		 *	The projects: every element of the type; what the module kept for the request about the projects (the lookups) is forgotten first, and the kernel's notice of each delete forgets it again
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$counts
		 *
		 *	@return 	bool											False where an element could not be deleted
		 */
		private static function projects( array &$appData, array &$counts ): bool {

			Projects::forget( $appData );

			foreach( Projects::list( $appData ) as $project ) {

				if( \Nino\Elements::deleteElement( $appData, Projects::TYPE. '/'. $project['id'], '*' ) !== true )
					return false;

				$counts['projects']++;
			}

			// The projects number again from 00001: the counter of the type is seeded from what it holds, which is nothing
			$file = '/elements/'. ltrim( Projects::TYPE, '/' ). '.php';

			if( \Nino\Filesystem::fileExists( $appData, $file ) === true ) {

				$seeded = \Nino\Filesystem::mutate( $appData, $file, static function( mixed $typeData ): ?array {

					$seed = is_array( $typeData ) === true ? \Nino\Elements::autoincrementSeed( $typeData ) : 0;

					if( is_array( $typeData ) === false || \Nino\Elements::readAutoincrement( $typeData ) === null || \Nino\Elements::readAutoincrement( $typeData ) === $seed )
						return null;

					$typeData['autoincrement'] = $seed;

					return $typeData;
				}, false );

				unset( $appData['./nino/elements/cache'] );

				if( $seeded === false && \Nino\Elements::getAutoincrement( $appData, Projects::TYPE ) !== 1 && \Nino\Elements::getAutoincrement( $appData, Projects::TYPE ) !== null )
					return false;

				// ...or from the start number of the projects, where the owner set one
				if( Projects::lift( $appData ) === false )
					return false;
			}

			return true;
		}
	}
}
