<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Erasure		Delete one client with everything that hangs on it (Clients::deletable() says what that is and whether it may be done, Clients::delete() does it; the button of the card is crm-clients/delete, see Clients\Api).
	 *
	 *								The rule: a client that a package refuses is not deleted - the package says a reason where it holds rows that may not go (a fixed document, say), and the plan names it -; a project that a record of another
	 *								client names, or that a package says another client names, is not deleted with its client either, and neither is a client that a package holds back (the hook HOLDS). Everything else goes with the client, and only the
	 *								client's: the records (the one place the module deletes a record), the logbook, what the packages hold of it (add()), the projects with their states and tasks, and what a package holds outside the database. The number
	 *								is spent: the next client is never given it (a row of sequences, category client, year 0, holds the least number the next client may get).
	 *
	 *								The order is the one that leaves nothing behind that a person can reach if a step fails. The database goes first, in one transaction - the rows of the client, the number spent and a journal row of settings
	 *								(erasing.<number>: the number, the projects and what the packages gathered) that says what is still to go; where anything in it fails, nothing changes. Then, one after the other: the steps of the packages (finish), the
	 *								projects, and the packages that are told the client is gone (the hook DELETED). Each step can be done twice; the first that fails stops the run and answers its name, the journal stays, and the next press of the
	 *								button (resume(), by the number of the client that is not there any more) finishes the rest. The journal goes when the last step is done.
	 *
	 *								A package that keeps rows of a client in a table of its own enters a count, a deletion and, where it refuses the deletion, a reason (add()); one that keeps something outside the database - files, accounts of
	 *								another store - enters gather (what it needs to find that later, read before the database step goes, and kept in the journal) and finish (what it deletes, after the commit, again and again until it is done). The
	 *								core names no package. Where the data of a package that is gone is still in the database (Db::orphans()), nothing is deleted: its tables may name the client, and its holds are not asked.
	 *
	 *								Nothing is mailed by a deletion. A destructive call checks the right itself: the account holds the permission of the CRM and of every panel whose data goes along, or the answer is denied and nothing changes.
	 *
	 *	@package						Dape/Nino
	 */
	class Erasure {

		/**	The lock that lets one deletion at a time run	*/
		private const string LOCK = '/data/crm-client-delete';

		/**	The hooks that ask a package what it holds and that tell it the client is gone	*/
		public const string HOLDS = '/crm/client/holds';
		public const string DELETED = '/crm/client/deleted';

		/**	The key of the journal row of a client that is deleted: erasing.K-0001	*/
		public const string JOURNAL = 'erasing.';

		/**	The counts of a preview and of an answer of the core, in the order the surface names them; the packages add theirs (add(), keys())	*/
		public const array COUNTS = [ 'projects', 'shares', 'received', 'records', 'logbook', 'tasks' ];

		/**	The steps of the core in the transaction of the database step, in the order they run: a package puts its rows in front of one of them (add(), rows_before)	*/
		public const array STEPS = [ 'records', 'logbook', 'shares', 'tasks', 'client' ];

		// The words a refusal of a package may not be: what the core answers itself, and the steps that stop a run after the database
		private const array RESERVED = [ 'shared', 'held', 'unknown', 'lock', 'denied', 'orphans', 'projects', 'step', 'busy', 'off', 'unreadable' ];

		// The key of the packages that were entered, in the app data of the request
		private const string REGISTER = './crm/erasure/tables';

		// The most characters of the label of a hold and of its sentence
		private const int LABEL = 60;
		private const int WHY = 300;

		/**
		 *	Enter what a package holds of a client: the core counts and deletes its own tables only, and asks the package for its own. The description: package (the key of the feature that enters it, see Register::package(); it is kept in the journal as the owner of the step), key (the name of its count in a preview and in an answer: lower case letters,
		 *	at most twenty, none of COUNTS and neither clients nor features, which the answer has besides), before (a key of COUNTS the count stands in front of; none: at the end), count (a callable [ class, method ]
		 *	( array &$appData, array $client ): int that says how many rows the client has - the preview asks it), and what the package does with them, one or more of: rows (a callable [ class, method ]
		 *	( array &$appData, callable $run, array $client ): int that deletes them - inside the transaction of the database step, in which the client row goes last: $run( string $sql, array $params = [] ): int runs one statement
		 *	of it and answers how many rows it changed, a statement that would end the transaction is refused (Db::runner()); the answer of rows is the count of the answer), reason (the word of a refusal, lower case letters, at most
		 *	twenty, none of RESERVED and none another package has; answer: [ status 4xx, message, code ] the API answers with it, see answerOf(): while the count is above nought the client is not deleted, and the plan names the reason
		 *	in front of those of the core, in the order the packages entered - the count is asked again inside the transaction, with the write lock held) and finish (a callable [ class, method ]
		 *	( array &$appData, array $gathered, array &$done ): bool that deletes what the package holds outside the database, after the commit: it is run again and again until it answers true, so it deletes what is left and counts
		 *	only what it deleted now; $done is the answer's counts, where it adds to its own key). gather (a callable [ class, method ] ( array &$appData, array $client ): array, with finish: what finish needs to find its things
		 *	when the client row is gone, as a flat json of small values - it is asked before the database step and kept in the journal). rows_before (a step of STEPS the rows run in front of; none: in front of the client row) puts the
		 *	rows between the statements of the core. shared (a callable [ class, method ] ( array &$appData, int $id, array $projects ): array that answers which of the projects the package has rows of another client name - it is asked in
		 *	the preview and inside the transaction). perm (the permission of the panel of the package that an account needs to delete what the package holds). $client has id, number and email. Every callable is a method of a class in the namespace of the package that enters it (Register::owned()): a closure, a function and a class of another namespace are refused. A key of COUNTS, a key another
		 *	package has, a field that is none and a description that is none are refused. The same description again is as good as the first time
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *
		 *	@return 	bool											false, with a warning, where the description is refused
		 */
		public static function add( array &$appData, array $spec ): bool {

			// The reasons the others have: the entry of the same key is the same description again, and its reason is its own
			$key = $spec['key'] ?? null;
			$others = array_diff_key( (array) ( $appData[self::REGISTER] ?? [] ), is_string( $key ) === true ? [ $key => true ] : [] );
			$reasons = array_filter( array_column( $others, 'reason' ), static fn( mixed $reason ): bool => $reason !== null );
			// What each of them is called with: rows ( &$appData, $gone, $who ), shared ( &$appData, $id, $projects ), gather ( &$appData, $who ), finish ( &$appData, $steps, &$mine ): the references are the positions that hold a variable
			// A function takes $appData as a reference, an arrow function takes a copy of it, and what the register keeps of a class that could not be loaded would be kept in the copy
			$callable = static function( int $arguments, array $reference = [ 0 ] ) use ( &$appData ): \Closure { return static function( mixed $one, array $full ) use ( &$appData, $arguments, $reference ): bool { return $one === null || Register::owned( $appData, $full['package'], $one, $arguments, $reference ); }; };

			return Register::add( $appData, self::REGISTER, 'deletion of a client', [ $spec ], [
				'id' => 'key',
				'idRule' => static fn( string $key ): bool => preg_match( '/^[a-z]{1,20}$/D', $key ) === 1 && in_array( $key, array_merge( self::COUNTS, [ 'clients', 'features' ] ), true ) === false,
				'fields' => [ 'package' => Register::REQUIRED, 'before' => null, 'count' => Register::REQUIRED, 'rows' => null, 'rows_before' => null, 'reason' => null, 'answer' => null, 'shared' => null, 'gather' => null, 'finish' => null, 'perm' => null ],
				'rules' => [
					'package' => Register::packageRule( $appData ),
					'before' => static fn( mixed $before ): bool => $before === null || in_array( $before, self::COUNTS, true ),
					'count' => static function( mixed $count, array $full ) use ( &$appData ): bool { return Register::owned( $appData, $full['package'], $count, 2, [ 0 ] ); },
					'rows' => $callable( 3 ),
					'shared' => $callable( 3 ),
					'gather' => $callable( 2 ),
					'finish' => $callable( 3, [ 0, 2 ] ),
					'rows_before' => static fn( mixed $step ): bool => $step === null || in_array( $step, self::STEPS, true ),
					'reason' => static fn( mixed $reason ): bool => $reason === null || ( is_string( $reason ) === true && preg_match( '/^[a-z]{1,20}$/D', $reason ) === 1 && in_array( $reason, array_merge( self::RESERVED, $reasons ), true ) === false ),
					'answer' => static fn( mixed $answer ): bool => $answer === null || ( is_array( $answer ) === true && array_is_list( $answer ) === true && count( $answer ) === 3 && is_int( $answer[0] ) === true && $answer[0] >= 400 && $answer[0] <= 499
						&& is_string( $answer[1] ) === true && $answer[1] !== '' && is_string( $answer[2] ) === true && preg_match( '/^[a-z_]{1,60}$/D', $answer[2] ) === 1 ),
					'perm' => static fn( mixed $perm ): bool => $perm === null || ( is_string( $perm ) === true && preg_match( '~^/_admin/[a-z0-9/-]{1,80}$~D', $perm ) === 1 ),
				],
				'refuse' => static fn( array $one ): string => match( true ) {
					$one['rows'] === null && $one['reason'] === null && $one['finish'] === null => 'rows',
					$one['rows_before'] !== null && $one['rows'] === null => 'rows_before',
					$one['answer'] !== null && $one['reason'] === null => 'answer',
					$one['gather'] !== null && $one['finish'] === null => 'gather',
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
		 *	The names of the counts of a preview and of an answer: those of COUNTS, with those of the packages each in front of the key it names, or at the end
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of keys
		 */
		public static function keys( array &$appData ): array {

			$keys = [];

			foreach( self::COUNTS as $key ) {

				foreach( self::entered( $appData ) as $entered => $spec )
					if( $spec['before'] === $key )
						$keys[] = (string) $entered;

				$keys[] = $key;
			}

			foreach( self::entered( $appData ) as $entered => $spec )
				if( $spec['before'] === null )
					$keys[] = (string) $entered;

			return $keys;
		}

		/**
		 *	The keys of the packages whose data is in the database while the package is gone or off: the reason nothing is deleted
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of package keys
		 */
		public static function orphanKeys( array &$appData ): ?array {

			$orphans = Db::orphansKnown( $appData );

			return $orphans === null ? null : array_column( $orphans, 'package' );
		}

		/**
		 *	Whether what stops a deletion or the test phase is data that only packages which are REMOVED hold: the database holds the data of at least one package that is not on, and Register::gone() says of every one of them that the package is removed.
		 *	Where one of them is only off, cannot be read or has something unknown beside it in the features directory, it is no. Where the register cannot be read it is no
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function dataOfRemoved( array &$appData ): bool {

			$orphans = Db::orphansKnown( $appData );

			return $orphans !== null && $orphans !== [] && array_filter( $orphans, static fn( array $orphan ): bool => $orphan['installed'] !== false ) === [];
		}

		/**
		 *	Whether what stops an open deletion is the steps of packages that are REMOVED: the journal has steps that no package that is on stands for, and of every one of them Register::gone() says that its package is removed. Asked after
		 *	the data of the packages were found to be none, so that these steps are the ones that skippable() lists. Where one of them is of a package that is only off or cannot be read, it is no
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number				K-0001
		 *
		 *	@return 	bool
		 */
		public static function stepsOfRemoved( array &$appData, string $number ): bool {

			$journal = self::journal( $appData, $number );

			if( $journal === null )
				return false;

			$entered = array_map( 'strval', array_keys( self::entered( $appData ) ) );
			$off = array_values( array_diff( array_map( 'strval', array_keys( $journal['steps'] ) ), $entered ) );

			return $off !== [] && array_filter( $off, static function( string $key ) use ( &$appData, $journal ): bool { return self::removed( $appData, $journal, $key ) === false; } ) === [];
		}

		/**
		 *	Whether the account that is signed in may delete clients: it holds the permission of the CRM and the permission of every panel whose data goes along. A call that asks nobody is not answered yes
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		private static function allowed( array &$appData ): bool {

			return \Nino\Auth::getCurrentUser( $appData ) !== false && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === true && Wipe::permitted( $appData ) === true;
		}

		/**
		 *	What hangs on a client, as numbers, and whether it may be deleted
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array											found (whether the client is there; false: the rest is empty), number, deletable, reasons (why not: orphans, the reasons the packages entered, then shared, held), counts (keys() => how many), shared (the projects that a record or a package says another client names), features (what the packages hold: key, label, count, blocks, why)
		 */
		public static function plan( array &$appData, int $id ): array {

			$client = Clients::get( $appData, $id );
			$entered = self::entered( $appData );

			if( $client === null )
				return [ 'found' => false, 'number' => '', 'deletable' => false, 'reasons' => [ 'unknown' ], 'counts' => array_fill_keys( self::keys( $appData ), 0 ), 'shared' => [], 'features' => [] ];

			$projects = array_column( Projects::ofClient( $appData, (string) $client['number'] ), 'id' );
			$counts = array_fill_keys( self::keys( $appData ), 0 );
			$mine = [ 'id' => $id ];
			$who = self::who( $client );

			$counts['projects'] = count( $projects );
			$counts['records'] = (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM records WHERE client_id = :id', $mine )['n'] ?? 0 );

			foreach( $entered as $key => $table )
				$counts[$key] = max( 0, (int) call_user_func_array( $table['count'], [ &$appData, $who ] ) );

			[ $in, $named ] = self::list( $projects );
			$counts['logbook'] = (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM logbook WHERE client_id = :id'. ( $projects === [] ? '' : ' OR ( ( client_id IS NULL OR ( source = \'crm\' AND kind IN ( '. Logbook::shareKinds(). ' ) ) ) AND project IN ( '. $in. ' ) )' ), $mine + $named )['n'] ?? 0 );
			$counts['received'] = (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM project_shares WHERE client_id = :id', $mine )['n'] ?? 0 );
			$counts['shares'] = $projects === [] ? 0 : (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM project_shares WHERE client_id <> :id AND project IN ( '. $in. ' )', $mine + $named )['n'] ?? 0 );
			$counts['tasks'] = $projects === [] ? 0 : (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM tasks WHERE project IN ( '. $in. ' )', $named )['n'] ?? 0 );

			$shared = self::shared( $appData, $id, $projects );
			$features = self::features( $appData, $client );
			$orphans = self::orphanKeys( $appData );
			$reasons = array_merge( $orphans === null ? [ 'busy' ] : ( $orphans === [] ? [] : [ 'orphans' ] ),
				array_column( array_filter( $entered, static fn( array $table, string $key ): bool => $table['reason'] !== null && $counts[$key] > 0, ARRAY_FILTER_USE_BOTH ), 'reason' ),
				array_keys( array_filter( [ 'shared' => $shared !== [], 'held' => array_filter( $features, static fn( array $one ): bool => $one['blocks'] === true ) !== [] ] ) ) );

			return [ 'found' => true, 'number' => (string) $client['number'], 'deletable' => $reasons === [], 'reasons' => $reasons, 'counts' => $counts, 'shared' => $shared, 'features' => $features ];
		}

		/**
		 *	Delete a client
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array|null	&$done				The counts so far, also where a step failed
		 *
		 *	@return 	array|string							number, counts (keys() => how many went) and features (key => how many); or why not: denied (the account may not), unknown (no such client), orphans (the data of a package that is gone is in the database), the reason a package entered, shared, held (a package refuses; the plan says which), lock (another deletion holds the lock and did not let go) - nothing is changed for any of these - or the step that stopped, with the database step done: projects, or step:<key> of a package. A \PDOException is the database refusing, with nothing deleted
		 */
		public static function run( array &$appData, int $id, ?array &$done = null ): array|string {

			$done = array_fill_keys( self::keys( $appData ), 0 ) + [ 'clients' => 0, 'features' => [] ];

			if( self::allowed( $appData ) === false )
				return 'denied';

			if( \Nino\Filesystem::lockFile( $appData, self::LOCK ) === false )
				return 'lock';

			try {
				return Clients::get( $appData, $id ) === null ? 'unknown' : self::start( $appData, $id, $done );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, self::LOCK );
			}
		}

		/**
		 *	Finish the deletion of a client whose run stopped after the database step: the steps that are done are done again, harmlessly
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number				K-0001
		 *	@param		array|null	&$done				The counts so far, also where a step failed
		 *
		 *	@return 	array|string							As run() answers; unknown for a number with no deletion to finish - and for one that a client holds again (after the test phase the numbers begin anew), whose journal is dropped
		 */
		public static function resume( array &$appData, string $number, ?array &$done = null, array $skip = [] ): array|string {

			$done = array_fill_keys( self::keys( $appData ), 0 ) + [ 'clients' => 0, 'features' => [] ];

			if( self::allowed( $appData ) === false )
				return 'denied';

			if( \Nino\Filesystem::lockFile( $appData, self::LOCK ) === false )
				return 'lock';

			try {
				$journal = self::journal( $appData, $number );

				if( $journal === null )
					return 'unknown';

				if( Clients::byNumber( $appData, $number ) !== null ) {
					self::forget( $appData, $number );
					return 'unknown';
				}

				// As the first press: the data of a package that is gone, and a database that cannot be read, stop it - and so does a step in the journal that no package that is on stands for any more, whose
				// data would be left where they are and the journal, the only word of where, forgotten
				$orphans = self::orphanKeys( $appData );

				if( $orphans === null )
					return 'busy';

				if( $orphans !== [] )
					return 'orphans';

				$swept = Projects::sweep( $appData );

				if( is_string( $swept ) === true )
					return $swept;

				$off = array_values( array_diff( array_map( 'strval', array_keys( $journal['steps'] ) ), array_map( 'strval', array_keys( self::entered( $appData ) ) ) ) );

				// The owner may give up the step of a package that is gone - and only that, by name: the keys named in $skip, never "all"; the owner of a step is the one the journal kept when the step was begun
				$skipped = array_values( array_filter( $off, static function( string $key ) use ( &$appData, $journal, $skip ): bool { return in_array( $key, array_map( 'strval', $skip ), true ) === true && self::removed( $appData, $journal, $key ) === true; } ) );
				$left = array_values( array_diff( $off, $skipped ) );

				if( $left !== [] )
					return 'off:'. implode( ', ', $left );

				if( $skipped !== [] ) {
					$journal['skipped'] = $skipped;
					Db::exec( $appData, 'UPDATE settings SET value = :value WHERE key = :key', [ 'key' => self::JOURNAL. $journal['number'], 'value' => json_encode( $journal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ] );
				}

				$result = self::finish( $appData, $journal, $done );

				return is_array( $result ) === true && $skipped !== [] ? $result + [ 'skipped' => $skipped ] : $result;
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, self::LOCK );
			}
		}

		/**
		 *	Whether the package that owns a step is removed: Register::gone() says, asked with the owner and the folder the journal kept when the step was begun (a package that is only off is still installed and comes back, and one whose manifest
		 *	cannot be read just now is still there). The data of a package that is gone stop the deletion before this is asked (orphanKeys(): any data in the database, whoever owns it), so a package that is removed here has none. A step with no
		 *	owner or no folder in the journal - one that was begun before the journal kept them - is never removed
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$journal
		 *	@param		string		$key					The key of the step
		 *
		 *	@return 	bool
		 */
		private static function removed( array &$appData, array $journal, string $key ): bool {

			$owner = $journal['owners'][$key] ?? null;
			$folder = $journal['folders'][$key] ?? null;

			return is_string( $owner ) === true && $owner !== '' && is_string( $folder ) === true && $folder !== '' && Register::gone( $appData, $owner, $folder ) === true;
		}

		/**
		 *	The steps of an open deletion that the owner may give up: those of a package that is removed - Register::gone() says so of the owner and the folder the journal names for the step - and has no data left in the database (a package that is only off comes back, and one with data
		 *	is a reason to remove the data first). The page offers the button from this list
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number
		 *
		 *	@return 	array											The keys
		 */
		public static function skippable( array &$appData, string $number ): array {

			$journal = self::journal( $appData, $number );
			$orphans = self::orphanKeys( $appData );

			if( $journal === null || $orphans !== [] )
				return [];

			$entered = array_map( 'strval', array_keys( self::entered( $appData ) ) );

			return array_values( array_filter( array_map( 'strval', array_keys( $journal['steps'] ) ), static function( string $key ) use ( &$appData, $journal, $entered ): bool { return in_array( $key, $entered, true ) === false && self::removed( $appData, $journal, $key ) === true; } ) );
		}

		/**
		 *	The deletions that were begun and are not finished: the database step went through, a later step did not
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The numbers, in order
		 */
		public static function pending( array &$appData ): array {

			$found = [];

			foreach( Db::rows( $appData, 'SELECT key, value FROM settings WHERE key LIKE :key ORDER BY key', [ 'key' => self::JOURNAL. '%' ] ) as $row ) {

				$journal = self::decode( (string) $row['value'] );

				if( $journal !== null && self::JOURNAL. $journal['number'] === $row['key'] )
					$found[] = $journal['number'];
			}

			return $found;
		}

		/**
		 *	The number of a client whose deletion is not finished, as the journal has it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number
		 *
		 *	@return 	string										'' for a number with no deletion to finish
		 */
		public static function pendingNumber( array &$appData, string $number ): string {

			return preg_match( '/^K-[0-9]{4,}$/D', $number ) === 1 ? ( self::journal( $appData, $number )['number'] ?? '' ) : '';
		}

		/**
		 *	The first part of a run: the client is there. The plan, the database in one transaction, then the rest
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array 		&$done
		 *
		 *	@return 	array|string
		 */
		private static function start( array &$appData, int $id, array &$done ): array|string {

			// What is left of projects that are gone is cleared first: nothing of a person stays in rows no project holds
			$swept = Projects::sweep( $appData );

			if( is_string( $swept ) === true )
				return $swept;

			$plan = self::plan( $appData, $id );

			if( $plan['deletable'] === false )
				return $plan['reasons'][0];

			$client = Clients::get( $appData, $id );

			// A press that was first has deleted it since the plan was made
			if( $client === null )
				return 'unknown';

			$who = self::who( $client );
			$projects = array_column( Projects::ofClient( $appData, (string) $client['number'] ), 'id' );
			$journal = [ 'number' => (string) $client['number'], 'projects' => $projects, 'emails' => $who['email'] === '' ? [] : [ $who['email'] ], 'steps' => [], 'owners' => [], 'folders' => [] ];

			// What a package needs to find its things when the client row is gone is read now and kept in the journal
			foreach( self::entered( $appData ) as $key => $table )
				if( $table['gather'] !== null ) {
					$journal['steps'][(string) $key] = self::flat( call_user_func_array( $table['gather'], [ &$appData, $who ] ) );
					$journal['owners'][(string) $key] = (string) $table['package'];
					$journal['folders'][(string) $key] = Register::directory( $appData, (string) $table['package'] );
				}

			$pdo = Db::pdo( $appData );
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				// What decides is read inside the transaction, with the write lock held: a row that is fixed now is fixed before this, or after the client is gone and cannot be - and so is the register of the
				// packages, which another process may have changed since the plan
				$orphans = self::orphanKeys( $appData );

				if( $orphans !== [] ) {
					$pdo->exec( 'ROLLBACK' );
					return $orphans === null ? 'busy' : 'orphans';
				}

				foreach( self::entered( $appData ) as $table )
					if( $table['reason'] !== null && (int) call_user_func_array( $table['count'], [ &$appData, $who ] ) > 0 ) {
						$pdo->exec( 'ROLLBACK' );
						return $table['reason'];
					}

				if( self::shared( $appData, $id, $projects ) !== [] ) {
					$pdo->exec( 'ROLLBACK' );
					return 'shared';
				}

				if( self::rows( $appData, $pdo, $id, $who, $journal, $done ) === false ) {
					$pdo->exec( 'ROLLBACK' );
					return 'unknown';
				}

				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			Db::drop( $appData );

			return self::finish( $appData, $journal, $done );
		}

		/**
		 *	The database step: the rows, the number spent and the journal, inside the transaction of the caller. A row that others name goes after the rows that name it
		 *
		 *	@param		array 		&$appData
		 *	@param		\PDO			$pdo
		 *	@param		int				$id
		 *	@param		array 		$who					The client as the packages are handed it
		 *	@param		array 		$journal
		 *	@param		array 		&$done
		 *
		 *	@return 	bool											False where the client row was not there to delete: the caller goes back
		 */
		private static function rows( array &$appData, \PDO $pdo, int $id, array $who, array $journal, array &$done ): bool {

			$mine = [ 'id' => $id ];
			$gone = Db::runner( $pdo );

			// What the packages hold of the client goes in the same transaction, each in front of the step of the core it names
			$packages = static function( string $step ) use ( &$appData, $gone, $who, &$done ): void {

				foreach( self::entered( $appData ) as $key => $table )
					if( $table['rows'] !== null && ( $table['rows_before'] ?? 'client' ) === $step )
						$done[$key] = (int) call_user_func_array( $table['rows'], [ &$appData, $gone, $who ] );
			};

			$packages( 'records' );
			$done['records'] = self::peel( $gone, 'records', 'parent', $id );

			[ $in, $named ] = self::list( $journal['projects'] );
			$packages( 'logbook' );
			$done['logbook'] = $gone( 'DELETE FROM logbook WHERE client_id = :id'. ( $journal['projects'] === [] ? '' : ' OR ( ( client_id IS NULL OR ( source = \'crm\' AND kind IN ( '. Logbook::shareKinds(). ' ) ) ) AND project IN ( '. $in. ' ) )' ), $mine + $named );

			// The releases to the client go with it, and so do the releases of its projects to other clients
			$packages( 'shares' );
			$done['received'] = $gone( 'DELETE FROM project_shares WHERE client_id = :id', $mine );
			$done['shares'] = $journal['projects'] === [] ? 0 : $gone( 'DELETE FROM project_shares WHERE project IN ( '. $in. ' )', $named );

			$packages( 'tasks' );

			if( $journal['projects'] !== [] ) {
				$done['tasks'] = $gone( 'DELETE FROM tasks WHERE project IN ( '. $in. ' )', $named );
				$gone( 'DELETE FROM project_states WHERE project IN ( '. $in. ' )', $named );
			}

			$packages( 'client' );

			// The client is there inside the transaction, or the deletion is none: a second one that was waiting for the lock finds nothing
			if( $gone( 'DELETE FROM clients WHERE id = :id', $mine ) !== 1 )
				return false;

			// The number is spent: the next client gets one more than the highest there is or was
			Sequences::floor( $gone, Clients::CATEGORY, 0, (int) substr( $journal['number'], 2 ) + 1 );
			$gone( 'INSERT INTO settings ( key, value ) VALUES ( :key, :value ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'key' => self::JOURNAL. $journal['number'], 'value' => json_encode( $journal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ] );

			$done['clients'] = 1;

			return true;
		}

		/**
		 *	Delete the rows of a table of one client that name themselves in one of their columns: the rows nobody names first, then the ones they named, until none is left. A cycle is none the module can make: the last
		 *	statement refuses it with the constraint, and the transaction goes back. A package whose table names itself (the rows of a table of its own) calls it from its rows (add())
		 *
		 *	@param		callable	$run					( string $sql, array $params = [] ): int, a statement of the transaction
		 *	@param		string		$table				The name of a table: lower case letters and underscores
		 *	@param		string		$column				The column that names the id of another row of the table
		 *	@param		int				$client
		 *	@param		array 		$only					Column => value (null: IS NULL) the rows must have to go: the others stay, and a row that names one of them or that one names is refused by the constraint with the transaction going back
		 *
		 *	@return 	int												How many rows went
		 */
		public static function peel( callable $run, string $table, string $column, int $client, array $only = [] ): int {

			if( preg_match( '/^[a-z_]{1,40}$/D', $table ) !== 1 || preg_match( '/^[a-z_]{1,40}$/D', $column ) !== 1 || array_filter( array_keys( $only ), static fn( mixed $name ): bool => is_string( $name ) === false || preg_match( '/^[a-z_]{1,40}$/D', $name ) !== 1 ) !== [] )
				throw new \InvalidArgumentException( 'Crm: the table, the column and the columns of a peel are names' );

			$gone = 0;
			$mine = [ 'id' => $client ];
			$where = '';

			foreach( array_keys( $only ) as $at => $name ) {

				if( $only[$name] === null ) {
					$where .= ' AND '. $name. ' IS NULL';
					continue;
				}

				$where .= ' AND '. $name. ' = :o'. $at;
				$mine['o'. $at] = $only[$name];
			}

			do {
				$step = $run( 'DELETE FROM '. $table. ' WHERE client_id = :id'. $where. ' AND id NOT IN ( SELECT '. $column. ' FROM '. $table. ' WHERE '. $column. ' IS NOT NULL )', $mine );
				$gone += $step;
			}
			while( $step > 0 );

			return $gone + $run( 'DELETE FROM '. $table. ' WHERE client_id = :id'. $where, $mine );
		}

		/**
		 *	How the API answers a refusal a package entered with an answer (add()): [ status, message, code ], or null for a word that is none of theirs
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$reason
		 *
		 *	@return 	array|null
		 */
		public static function answerOf( array &$appData, string $reason ): ?array {

			if( $reason === 'busy' )
				return [ 503, 'the database is held by another process', 'crm_busy' ];

			if( $reason === 'unreadable' )
				return [ 503, 'the projects cannot be read', 'crm_projects_unreadable' ];

			foreach( self::entered( $appData ) as $table )
				if( $table['reason'] === $reason && $table['answer'] !== null )
					return $table['answer'];

			return null;
		}

		/**
		 *	The steps after the database: those of the packages, the projects, and the packages that are told. The journal goes with the last
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$journal
		 *	@param		array 		&$done
		 *
		 *	@return 	array|string
		 */
		private static function finish( array &$appData, array $journal, array &$done ): array|string {

			foreach( self::entered( $appData ) as $key => $table ) {

				if( $table['finish'] === null )
					continue;

				// A step that throws has failed as much as one that answers no: the journal stays and the next press asks it again
				try {
					$mine = $done;
					$ended = call_user_func_array( $table['finish'], [ &$appData, $journal['steps'][$key] ?? [], &$mine ] ) === true;

					// What it says it took goes into its own count and into no other: a whole number, not below what stood there
					if( array_key_exists( $key, $done ) === true && is_int( $mine[$key] ?? null ) === true && $mine[$key] >= $done[$key] )
						$done[$key] = $mine[$key];
				}
				catch( \Throwable $e ) {
					trigger_error( 'Crm: the step of the package '. $key. ' of the deletion of client '. $journal['number']. ' failed', E_USER_WARNING );
					$ended = false;
				}

				if( $ended === false )
					return 'step:'. $key;
			}

			Projects::forget( $appData );
			Db::drop( $appData );

			foreach( Projects::ofClient( $appData, $journal['number'] ) as $project ) {

				if( \Nino\Elements::deleteElement( $appData, Projects::TYPE. '/'. $project['id'], '*' ) !== true )
					return 'projects';

				$done['projects']++;
			}

			$told = [ 'number' => $journal['number'], 'emails' => $journal['emails'], 'gone' => [], 'failed' => [] ];

			// A package that throws has failed: the journal stays and the next press asks it again
			try {
				\Nino\Callbacks::doCallbacks( $appData, self::DELETED, $told );
			}
			catch( \Throwable $e ) {
				trigger_error( 'Crm: a package could not be told that client '. $journal['number']. ' is deleted', E_USER_WARNING );
				$told = [ 'gone' => [], 'failed' => [ 'callback' ] ];
			}

			$told = is_array( $told ) === true ? $told : [];

			foreach( (array) ( $told['gone'] ?? [] ) as $key => $count )
				if( Register::word( (string) $key ) === true && is_int( $count ) === true && $count >= 0 )
					$done['features'][$key] = $count;

			if( (array) ( $told['failed'] ?? [] ) !== [] )
				return 'step:callback';

			self::forget( $appData, $journal['number'] );

			return [ 'number' => $journal['number'], 'counts' => array_diff_key( $done, [ 'features' => 0 ] ), 'features' => $done['features'] ];
		}

		/**
		 *	What the packages hold on a client: the hook is asked and what it answers is checked, an entry that is none is left out
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client
		 *
		 *	@return 	array											A list of key, label, count, blocks and why, by key
		 */
		private static function features( array &$appData, array $client ): array {

			$who = self::who( $client );
			$asked = [ 'id' => $client['id'], 'number' => $who['number'], 'emails' => $who['email'] === '' ? [] : [ $who['email'] ], 'holds' => [] ];
			\Nino\Callbacks::doCallbacks( $appData, self::HOLDS, $asked );

			$found = [];

			foreach( is_array( $asked ) === true && is_array( $asked['holds'] ?? null ) === true ? $asked['holds'] : [] as $hold ) {

				$key = is_array( $hold ) === true && is_string( $hold['key'] ?? null ) === true ? $hold['key'] : '';
				$count = is_array( $hold ) === true ? ( $hold['count'] ?? null ) : null;

				if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $key ) !== 1 || is_int( $count ) === false || $count < 0 )
					continue;

				$found[$key] = [
					'key'    => $key,
					'label'  => self::line( $hold['label'] ?? '', self::LABEL ),
					'count'  => $count,
					'blocks' => ( $hold['blocks'] ?? false ) === true,
					'why'    => self::line( $hold['why'] ?? '', self::WHY ),
				];
			}

			ksort( $found );

			return array_values( $found );
		}

		/**
		 *	A word of a package as one line of plain text, cut to a length
		 *
		 *	@param		mixed			$text
		 *	@param		int				$max
		 *
		 *	@return 	string
		 */
		private static function line( mixed $text, int $max ): string {

			return mb_substr( trim( (string) preg_replace( '/\s{2,}/u', ' ', (string) preg_replace( '/[\x{0}-\x{1F}\x{7F}-\x{9F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]+/u', ' ', is_string( $text ) === true ? $text : '' ) ) ), 0, $max, 'UTF-8' );
		}

		/**
		 *	The client as the packages are handed it: id, number and the address, in lower case and trimmed
		 *
		 *	@param		array 		$client				As Clients::get() has it
		 *
		 *	@return 	array
		 */
		private static function who( array $client ): array {

			return [ 'id' => (int) $client['id'], 'number' => (string) $client['number'], 'email' => mb_strtolower( trim( (string) $client['email'] ), 'UTF-8' ) ];
		}

		/**
		 *	What a package gathered, as a flat json: small values under names, nothing else is kept
		 *
		 *	@param		mixed			$gathered
		 *
		 *	@return 	array
		 */
		private static function flat( mixed $gathered ): array {

			if( is_array( $gathered ) === false )
				return [];

			$flat = [];

			foreach( $gathered as $name => $value ) {

				if( is_scalar( $value ) === true || $value === null )
					$flat[$name] = $value;
				elseif( is_array( $value ) === true && array_filter( $value, static fn( mixed $one ): bool => is_scalar( $one ) === false && $one !== null ) === [] )
					$flat[$name] = $value;
			}

			return $flat;
		}

		/**
		 *	The projects of the client that a record of another client names, or that a package says another client names: such a project is not the client's alone
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array 		$projects			The ids of the projects of the client
		 *
		 *	@return 	array											The ids that are named
		 */
		private static function shared( array &$appData, int $id, array $projects ): array {

			if( $projects === [] )
				return [];

			$found = [];

			// What the packages say, in the order they entered, then what a record of another client names
			foreach( self::entered( $appData ) as $table )
				if( $table['shared'] !== null )
					foreach( (array) call_user_func_array( $table['shared'], [ &$appData, $id, $projects ] ) as $project )
						$found[(string) $project] = true;

			[ $in, $named ] = self::list( $projects );

			foreach( Db::rows( $appData, 'SELECT DISTINCT project FROM records WHERE client_id <> :id AND project IN ( '. $in. ' )', [ 'id' => $id ] + $named ) as $row )
				$found[(string) $row['project']] = true;

			return array_map( 'strval', array_keys( $found ) );
		}

		/**
		 *	A list of values as the placeholders of a statement and the values under their names
		 *
		 *	@param		array 		$values
		 *
		 *	@return 	array											The text for IN ( ... ) and the parameters
		 */
		private static function list( array $values ): array {

			$named = [];

			foreach( array_values( $values ) as $at => $value )
				$named['v'. $at] = (string) $value;

			return [ implode( ', ', array_map( static fn( string $name ): string => ':'. $name, array_keys( $named ) ) ), $named ];
		}

		/**
		 *	The journal of a deletion that is not finished
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number
		 *
		 *	@return 	array|null								number, projects, emails, steps, owners (step => the package that owns it), folders (step => the name of the folder of that package)
		 */
		private static function journal( array &$appData, string $number ): ?array {

			$row = Db::row( $appData, 'SELECT value FROM settings WHERE key = :key', [ 'key' => self::JOURNAL. $number ] );

			return $row === null ? null : self::decode( (string) $row['value'] );
		}

		/**
		 *	@param		string		$json
		 *
		 *	@return 	array|null								number, projects, emails, steps, owners, folders; null for a journal that is none
		 */
		private static function decode( string $json ): ?array {

			$journal = json_decode( $json, true );

			if( is_array( $journal ) === false || preg_match( '/^K-[0-9]{4,}$/D', (string) ( $journal['number'] ?? '' ) ) !== 1 )
				return null;

			$steps = [];

			foreach( is_array( $journal['steps'] ?? null ) === true ? $journal['steps'] : [] as $key => $gathered )
				if( is_string( $key ) === true && is_array( $gathered ) === true )
					$steps[$key] = $gathered;

			$owners = [];

			foreach( is_array( $journal['owners'] ?? null ) === true ? $journal['owners'] : [] as $key => $owner )
				if( isset( $steps[$key] ) === true && Register::word( $owner ) === true )
					$owners[(string) $key] = $owner;

			$folders = [];

			foreach( is_array( $journal['folders'] ?? null ) === true ? $journal['folders'] : [] as $key => $folder )
				if( isset( $owners[$key] ) === true && is_string( $folder ) === true && preg_match( \Nino\Features::DIRECTORY_PATTERN, $folder ) === 1 )
					$folders[(string) $key] = $folder;

			return [ 'number' => (string) $journal['number'], 'projects' => array_values( array_map( 'strval', (array) ( $journal['projects'] ?? [] ) ) ), 'emails' => array_values( array_map( 'strval', (array) ( $journal['emails'] ?? [] ) ) ), 'steps' => $steps, 'owners' => $owners, 'folders' => $folders ] + ( is_array( $journal['skipped'] ?? null ) === true ? [ 'skipped' => array_values( array_map( 'strval', $journal['skipped'] ) ) ] : [] );
		}

		/**
		 *	The deletion is finished: its journal goes, overwritten as the rows of the client were (the journal holds an address until then)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number
		 *
		 *	@return 	void
		 */
		private static function forget( array &$appData, string $number ): void {

			Db::exec( $appData, 'DELETE FROM settings WHERE key = :key', [ 'key' => self::JOURNAL. $number ] );
		}
	}
}
