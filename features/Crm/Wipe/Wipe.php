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

		/**	The row of the table settings that holds an open run: what the gather of each step read, until the leftover of that step is through. The key has a dot, as the keys of the deletion of a client have (erasing.<number>): the keys a setting has are words
		 *	with no dot, so that no package and no request can enter, read or write it as a setting. The most a step may hold, as json, in bytes; and how deep the row may nest	*/
		public const string JOURNAL = 'wiping.run';

		/**	The key of the step of the core in the row of a run: what the core itself does after the commit (the packages are told, the projects go). It has a dot, so that no step of a package is it, and it is never given up	*/
		public const string CORE = '.core';
		public const int HELD_BYTES = 1048576;
		public const int HELD_DEPTH = 28;
		public const int ROW_DEPTH = 40;

		// The lock that lets one run at a time delete
		private const string LOCK = '/data/crm-wipe';

		// The key of the packages that were entered, in the app data of the request
		private const string REGISTER = './crm/wipe/tables';

		/**
		 *	Enter what a package holds of the clients: the core deletes its own tables only, and a package that keeps rows of the clients in a table of its own says how they go. The description: package (the key of the feature that enters it, see Register::package()), key (the name of its step, and of its
		 *	count where it has one: lower case letters, at most twenty, none of COUNTS), rows (a callable [ class, method ] ( array &$appData, callable $run ): int|void that deletes them - inside the transaction of the database step:
		 *	$run( string $sql, array $params = [] ): int runs one statement of it and answers how many rows it changed, a statement that would end the transaction is refused (Db::runner())), rows_before (a step of STEPS the rows run in
		 *	front of; none: in front of sequences, after the tasks), count (true: the answer of rows is the number the answer of the run carries under key; default false), before (with count: a key of COUNTS the count stands in front of; none: at
		 *	the end), gather (a callable [ class, method ] ( array &$appData ): array that reads what leftover needs to find its things while they are still there - the accounts of the clients, say, which are gone with the database step -:
		 *	THE RULE OF GATHER AND LEFTOVER: a run goes under the lock of the run, in ONE transaction that holds the write lock: every gather is asked - in that transaction, so that no client can come between the reading and the deleting -, the row 'wiping.run' of the table settings is written
		 *	- in the same transaction, in EVERY run, with the step of the core in it (the key '.core', never given up) beside the steps of the packages that gather -, the database step goes, COMMIT. Whatever fails before the commit, or ends the process, takes the row with it: there is never a row without the database step. A gather reads and writes nothing: what a gather that writes writes goes into this transaction, with the database step or back with it - it is
		 *	the fault of the package. After the commit the core does what is its own - the packages are told, the projects are deleted - and is taken from the row, then the leftovers run, and each one that is through - it answered true: null, 0, an empty text, a text, a list and a throw are not through - is taken from the row; with the last one the row goes, also where the owner gave a step up. The row holds, by the key of the step, the package, its folder and what the gather read (up to HELD_BYTES of json for a step, HELD_DEPTH levels deep): data about
		 *	persons, so in the database and its backup; the key has a dot, so that no setting can be it. A press that finds the row - a leftover failed, the process ended after the commit, a backup with the row was restored - reads nothing anew and joins nothing: it first does what is the core's, then finishes the leftovers that are open, each with the list it held, and where
		 *	they are all through, and the row is gone, a new run begins in the same press, and the answer counts both (so that the clients that came since are not left behind); where a leftover is not through the press ends with its failure and nothing new is deleted. THE CONTRACT OF A LEFTOVER: it is given what was read BEFORE the rows went, and it can run days later or after a restore. It has to bear that an entry it is given
		 *	is gone already, and it must delete nothing that belongs to somebody again - numbers and ids are given again: before it deletes it checks that the entry is an orphan. The core cannot check that for it. A gather that throws, answers what is no list or one that cannot be held whole - too deep, too large, no valid UTF-8, a number json does not know -
		 *	stops the run before anything is removed (the transaction goes back), with the step in the answer and a warning that names the step and the kind of the throwable, never its message; the next press asks for it. A row that cannot be read is no row of nothing: the run is refused (journal) before anything is removed - the owner throws such a row away with discard: the row is REPLACED, in one write, by one that can be read and holds only the core's step in the mode of the projects of the clients that are gone ('dead': a press that ends anywhere after it finishes it in that mode), with a warning and a line of the activity log, and the run goes on the way of any run that is open. What a step holds that is not
		 *	entered any more is not let go: the package is off - the run waits for it -, is removed or is on and has no such step; the run says so (held:off, held:gone, held:step, with the package and the step) and removes nothing, and only the owner gives a step up, where the package is removed or has no such step, by naming it in forget - a list of pairs { step, package }, the package as the owner typed it, compared with the one the row holds; another package, the core's key or a form that is none give up nothing -: with a warning and a line of
		 *	the activity log, and no leftover takes it; it needs a leftover), leftover (a callable [ class, method ]
		 *	( array &$appData, array &$done, array $gathered ): bool - the third argument is the list of its gather, as it was held, [] where the package has none - that removes what the run leaves behind of the package outside the database - files -, once the other
		 *	steps are through; true says it is through, anything else stops the run at the package, and the next press asks it again; $done is the counts so far, where it adds to its own key - one that count => true gave it - and to no other, as finish does at the deletion of a client; $done is the only
		 *	reference, $gathered is a value) and perm (the permission of the panel of the package that an account needs for the run). Every callable is a method of a class in the namespace of the package that enters it (Register::owned()): a closure, a function and a class of another namespace are refused. A key another package has, a key of COUNTS, a field that is none and a description that
		 *	is none are refused. The same description again is as good as the first time
		 *	While the row is there the core makes no client and no project: Clients::add() and Projects::add() answer 'wiping' (the endpoint of the clients: the 409 crm_wipe_unfinished), whoever calls them. Reading, changing and deleting stay as they are; the press that finishes the run lifts it.
		 *	A gather runs inside the write transaction of the run, on the connection of the core: it reads with Db::row() and Db::rows() and never opens the file of the database itself (no file_get_contents(), no second connection, not Db::kind() or Db::inspect()) - closing a second descriptor of that file in the process lets go of the locks the process holds on it, and another process could write into the open transaction. The core itself asks nothing inside the transaction that opens the file.
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
				'fields' => [ 'package' => Register::REQUIRED, 'rows' => null, 'count' => false, 'before' => null, 'rows_before' => null, 'gather' => null, 'leftover' => null, 'perm' => null ],
				'rules' => [
					'package' => Register::packageRule( $appData ),
					'count' => static fn( mixed $count ): bool => is_bool( $count ),
					'before' => static fn( mixed $before ): bool => $before === null || in_array( $before, self::COUNTS, true ),
					'rows' => static function( mixed $rows, array $full ) use ( &$appData ): bool { return $rows === null || Register::owned( $appData, $full['package'], $rows, 2, [ 0 ] ); },
					'gather' => static function( mixed $gather, array $full ) use ( &$appData ): bool { return $gather === null || Register::owned( $appData, $full['package'], $gather, 1, [ 0 ] ); },
					'leftover' => static function( mixed $leftover, array $full ) use ( &$appData ): bool { return $leftover === null || Register::owned( $appData, $full['package'], $leftover, 3, [ 0, 1 ] ); },
					'rows_before' => static fn( mixed $step ): bool => $step === null || in_array( $step, self::STEPS, true ),
					'perm' => static fn( mixed $perm ): bool => $perm === null || ( is_string( $perm ) === true && preg_match( '~^/_admin/[a-z0-9/-]{1,80}$~D', $perm ) === 1 ),
				],
				'refuse' => static fn( array $spec ): string => match( true ) {
					$spec['rows'] === null && $spec['leftover'] === null => 'rows',
					$spec['rows'] === null && ( $spec['count'] === true || $spec['rows_before'] !== null ) => 'rows',
					$spec['before'] !== null && $spec['count'] === false => 'before',
					$spec['gather'] !== null && $spec['leftover'] === null => 'gather',
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
		public static function run( array &$appData, ?array &$done = null, array $forget = [], bool $discard = false ): array|string {

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
				return self::step( $appData, $done, $forget, $discard );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, self::LOCK );
			}
		}

		/**
		 *	The steps of a press, under its lock. A run that was begun by an earlier press - its row is there: a leftover failed after the database step, or a backup with the row was restored - is finished first: each leftover that is
		 *	left is given the list it held, and nothing is read anew and nothing is joined. Only when no row is left a new run begins, in the same press, so that the clients that came since are not left behind as done
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$counts				The counts so far, which the caller keeps for a step that fails
		 *	@param		array 		$forget					The keys of the steps the owner gives up
		 *
		 *	@return 	array|string
		 */
		private static function step( array &$appData, array &$counts, array $forget, bool $discard ): array|string {

			// A row that cannot be read is not "nothing held": nothing is deleted
			$run = self::open( $appData );

			if( $run === false && $discard === false )
				return 'journal';

			// The owner throws away a row that cannot be read: what it held is lost (the lists of the packages). The row is REPLACED, in one write, by one that can be read and holds the step of the core in the mode that deletes
			// only the projects of clients that are gone: a press that ends anywhere after this finishes it as the same press would, and there is no moment without the row
			if( $run === false ) {

				if( self::keep( $appData, gmdate( 'Y-m-d\TH:i:s\Z' ), [ self::CORE => [ 'package' => 'crm', 'folder' => 'Crm', 'list' => [], 'dead' => true ] ] ) === false )
					return 'journal';

				trigger_error( 'Crm: the row of the run of the test phase could not be read and was thrown away by the owner', E_USER_WARNING );
				self::said( $appData, 'Threw away the row of the run of the test phase that could not be read' );
				$run = self::open( $appData );

				if( $run === false || $run === null )
					return 'journal';
			}

			if( $run !== null ) {

				// The owner gives a step up by naming it with the package that holds it: a step that names the core, or another package than the one the row holds, gives up nothing
				foreach( $forget as $name => $package )
					if( (string) $name === self::CORE )
						return 'forget:core:'. $name;
					elseif( isset( $run['steps'][(string) $name] ) === true && $run['steps'][(string) $name]['package'] !== $package )
						return 'forget:package:'. $name;

				$held = $run['steps'];
				unset( $held[self::CORE] );
				$stay = $held;
				$entered = self::entered( $appData );

				// The core's own step first, as in a run: the packages are told, and the projects of the clients that are gone go. The row says it is still to do until it is done, and it cannot be given up
				if( isset( $run['steps'][self::CORE] ) === true ) {
					if( self::stands( $appData, $run['began'] ) === false )
						return $counts;

					self::told( $appData );

					if( self::projects( $appData, $counts, $run['began'], ( $run['steps'][self::CORE]['dead'] ?? false ) === true ) === false )
						return 'projects';

					if( self::stands( $appData, $run['began'] ) === false )
						return $counts;

					self::keep( $appData, $run['began'], $held );
				}

				// What a step holds that is not entered any more is not let go without a word: the run is told, and only the owner gives it up (forget) - where the package is removed, or is on and has no such step; never where it is
				// only switched off, since it comes back with its step
				foreach( $held as $key => $part ) {

					if( isset( $entered[$key] ) === true && $entered[$key]['gather'] !== null && $entered[$key]['package'] === $part['package'] )
						continue;

					$gone = Register::gone( $appData, (string) $part['package'], (string) $part['folder'] );
					$kind = $gone === true ? 'gone' : ( \Nino\Modules\Crm::featureOn( $appData, (string) $part['package'] ) === true ? 'step' : 'off' );

					if( $kind !== 'off' && isset( $forget[(string) $key] ) === true ) {
						trigger_error( 'Crm: what the step '. $key. ' held of the clients was given up with the package, no leftover has taken it', E_USER_WARNING );
						self::said( $appData, 'Gave up what the step '. $key. ' of the package '. $part['package']. ' held of the clients: '. count( $part['list'], COUNT_RECURSIVE ). ' values' );
						unset( $held[$key] );

						continue;
					}

					return 'held:'. $kind. ':'. $key. ':'. $part['package'];
				}

				// What was given up is gone from the row, and with the last of them the row is
				if( $held !== $stay )
					self::keep( $appData, $run['began'], $held );

				// The leftovers that are open, each with the list it held
				foreach( $entered as $key => $package ) {

					if( isset( $held[(string) $key] ) === false )
						continue;

					if( self::leftover( $appData, (string) $key, $package, $held[(string) $key]['list'], $counts ) === false )
						return 'step:'. $key;

					unset( $held[(string) $key] );
					self::keep( $appData, $run['began'], $held );
				}
			}

			return self::begin( $appData, $counts );
		}

		/**
		 *	A new run: in one transaction that holds the write lock - so that no client can come between the reading and the deleting - every gather is asked, the row of the run is written and the database step goes; the transaction
		 *	is committed or goes back whole, the row with it. Then the other steps: the projects, and the leftovers, each with what its gather read, each taken from the row when it answered true
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$counts
		 *
		 *	@return 	array|string
		 */
		private static function begin( array &$appData, array &$counts ): array|string {

			$gathered = [];
			$held = [ self::CORE => [ 'package' => 'crm', 'folder' => 'Crm', 'list' => [] ] ];
			$began = gmdate( 'Y-m-d\TH:i:s\Z' );

			$tabled = self::tables( $appData, $counts, static function() use ( &$appData, &$gathered, &$held, $began ): ?string {

				foreach( self::entered( $appData ) as $key => $package ) {

					if( $package['gather'] === null )
						continue;

					$read = null;

					try {
						$read = call_user_func_array( $package['gather'], [ &$appData ] );
					}
					catch( \Throwable $e ) {
						trigger_error( 'Crm: the package '. $key. ' could not read what it keeps of the clients ('. strtok( get_class( $e ), "\0" ). ')', E_USER_WARNING );

						return 'step:'. $key;
					}

					// What is held is what the json says of it: a list that json cannot say whole - too deep, too large, no valid text, a number that is none - is no list that can be held
					$list = is_array( $read ) === true ? self::flat( $read ) : null;
					$text = $list === null ? false : json_encode( $list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, self::HELD_DEPTH );

					if( $list === null || is_string( $text ) === false || strlen( $text ) > self::HELD_BYTES ) {
						trigger_error( 'Crm: the package '. $key. ' could not read what it keeps of the clients ('. ( $list === null ? 'no list' : 'it cannot be held' ). ')', E_USER_WARNING );

						return 'step:'. $key;
					}

					$held[$key] = [ 'package' => (string) $package['package'], 'folder' => Register::directory( $appData, (string) $package['package'] ), 'list' => $list ];

					if( self::fits( $began, $held ) === false ) {
						trigger_error( 'Crm: the package '. $key. ' could not read what it keeps of the clients (it cannot be held)', E_USER_WARNING );

						return 'step:'. $key;
					}

					$gathered[(string) $key] = $list;
				}

				// The row is written in every run, whether a package reads anything or not: it is the word that the run is not over - the core's own step is in it, and with the last step it goes
				if( self::keep( $appData, $began, $held ) === false ) {
					trigger_error( 'Crm: the row of the run of the test phase could not be written', E_USER_WARNING );

					return 'journal';
				}

				return null;
			} );

			if( is_string( $tabled ) === true )
				return $tabled;

			$counts = $tabled;

			// The row is the word that this run is the one that stands: where it is not, the database was written back and the clients that are there are not those of this run
			if( self::stands( $appData, $began ) === false ) {
				trigger_error( 'Crm: the run of the test phase does not stand any more, nothing more is done for it', E_USER_WARNING );

				return $counts;
			}

			// The clients are gone from the database and their numbers begin again: a package that kept a number is told now, before any step that can fail
			self::told( $appData );

			if( self::projects( $appData, $counts, $began ) === false )
				return 'projects';

			if( self::stands( $appData, $began ) === false )
				return $counts;

			unset( $held[self::CORE] );
			self::keep( $appData, $began, $held );

			foreach( self::entered( $appData ) as $key => $package ) {

				if( self::leftover( $appData, (string) $key, $package, $gathered[(string) $key] ?? [], $counts ) === false )
					return 'step:'. $key;

				// This step is through: what its gather read of the clients goes, and with the last of them the run is over
				if( isset( $held[(string) $key] ) === true ) {
					unset( $held[(string) $key] );
					self::keep( $appData, $began, $held );
				}
			}

			return $counts;
		}

		/**
		 *	The leftover of a step, with the list it is given: what the package says it removed goes into its own count - and into no other: it is given a copy of the counts, and only its own key is taken back from it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		array 		$package				The step as entered
		 *	@param		array 		$list						What its gather read
		 *	@param		array 		&$counts
		 *
		 *	@return 	bool											False where it did not end: the run stops at the step
		 */
		private static function leftover( array &$appData, string $key, array $package, array $list, array &$counts ): bool {

			if( $package['leftover'] === null )
				return true;

			$mine = $counts;

			try {
				$answer = call_user_func_array( $package['leftover'], [ &$appData, &$mine, $list ] );
				$ended = $answer === true;

				// false is the answer that says it is not through; any other answer that is not true is not what the contract asks, and the warning names its type
				if( $ended === false && $answer !== false )
					trigger_error( 'Crm: the package '. $key. ' did not say that it removed what it kept of the clients: it answered '. get_debug_type( $answer ), E_USER_WARNING );
			}
			catch( \Throwable $e ) {
				trigger_error( 'Crm: the package '. $key. ' could not remove what it kept of the clients', E_USER_WARNING );
				$ended = false;
			}

			if( array_key_exists( $key, $counts ) === true && is_int( $mine[$key] ?? null ) === true && $mine[$key] >= $counts[$key] )
				$counts[$key] = $mine[$key];

			return $ended;
		}

		/**
		 *	Whether the run that is meant is the one the row stands for: the row is there and can be read, it is of that run (the time it began) and it holds the step of the core. The database may have been written back to an older state - a restore - between two
		 *	steps of the core after the commit: then the clients that the archive brought back are not those of this run, and what the core does for the run (the projects of the clients that are gone) is not done on them
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$began
		 *
		 *	@return 	bool
		 */
		private static function stands( array &$appData, string $began ): bool {

			$run = self::open( $appData );

			return is_array( $run ) === true && $run['began'] === $began && isset( $run['steps'][self::CORE] ) === true;
		}

		/**
		 *	Whether a run of the test phase is not finished: the row of the journal is there, whether it can be read or not. While it is, the core makes no client and no project (Clients::add(), Projects::add()
		 *	answer 'wiping'): what a package still holds from the run was read before the rows went, and a new row could be taken for one of them. Reading stays what it is
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function unfinished( array &$appData ): bool {

			return Db::row( $appData, 'SELECT 1 AS there FROM settings WHERE key = :key', [ 'key' => self::JOURNAL ] ) !== null;
		}

		/**
		 *	The run that is open: the row of the journal as it was written
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|false|null					began and steps (key => package, folder, list); null where no run is open; false where the row is there and is none that can be read - which is never "nothing"
		 */
		private static function open( array &$appData ): array|false|null {

			$row = Db::row( $appData, 'SELECT value FROM settings WHERE key = :key', [ 'key' => self::JOURNAL ] );

			if( $row === null )
				return null;

			$run = json_decode( (string) $row['value'], true, self::ROW_DEPTH + 1 );

			if( is_array( $run ) === false || is_string( $run['began'] ?? null ) === false || is_array( $run['steps'] ?? null ) === false )
				return false;

			foreach( $run['steps'] as $key => $part )
				if( is_string( $key ) === false || is_array( $part ) === false || is_string( $part['package'] ?? null ) === false || is_string( $part['folder'] ?? null ) === false || is_array( $part['list'] ?? null ) === false )
					return false;

			return $run;
		}

		/**
		 *	Keep what the steps hold, or take the row away where none holds anything: the run is over
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$began
		 *	@param		array 		$held
		 *
		 *	@return 	bool											False where it could not be kept whole
		 */
		private static function keep( array &$appData, string $began, array $held ): bool {

			if( $held === [] ) {
				Db::exec( $appData, 'DELETE FROM settings WHERE key = :key', [ 'key' => self::JOURNAL ] );

				return true;
			}

			if( self::fits( $began, $held ) === false )
				return false;

			Db::exec( $appData, 'INSERT INTO settings ( key, value ) VALUES ( :key, :value ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'key' => self::JOURNAL, 'value' => json_encode( [ 'began' => $began, 'steps' => $held ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, self::ROW_DEPTH ) ] );

			return true;
		}

		/**
		 *	Whether the row of a run, with these steps, can be written and read whole: the same json that is written is read back and is what was meant
		 *
		 *	@param		string		$began
		 *	@param		array 		$held
		 *
		 *	@return 	bool
		 */
		private static function fits( string $began, array $held ): bool {

			$text = json_encode( [ 'began' => $began, 'steps' => $held ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, self::ROW_DEPTH );

			return is_string( $text ) === true && json_decode( $text, true, self::ROW_DEPTH + 1 ) === [ 'began' => $began, 'steps' => $held ];
		}

		/**
		 *	One line of the activity log, in the name of the account that is signed in
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$line
		 *
		 *	@return 	void
		 */
		private static function said( array &$appData, string $line ): void {

			$user = \Nino\Auth::getCurrentUser( $appData );
			Values::log( $appData, is_array( $user ) === true ? (string) $user['mail'] : '', $line );
		}

		/**
		 *	What a gather answered as the json says it, and as nothing else
		 *
		 *	@param		array 		$read
		 *
		 *	@return 	array|null								null for what json cannot say (no valid text, a number that is none) or says as another thing
		 */
		private static function flat( array $read ): ?array {

			$text = json_encode( $read, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, self::HELD_DEPTH );
			$list = is_string( $text ) === true ? json_decode( $text, true, self::HELD_DEPTH + 1 ) : null;

			return is_array( $list ) === true ? $list : null;
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
		 *	@param		callable	$first					Asked once the write lock is held and before anything is deleted: null, or the answer of the run where it must not go on - the transaction goes back
		 *
		 *	@return 	array|string							The counts with clients, records, logbook and shares, and those of the packages; the answer of $first where it refused
		 */
		private static function tables( array &$appData, array $counts, callable $first ): array|string {

			$pdo = Db::pdo( $appData );

			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				// What is read of the clients is read here, in the transaction that holds the write lock and is the one that deletes them: no client comes between. A gather that writes writes into this transaction, which commits or goes back with it
				$refused = $first();

				if( $refused !== null ) {
					$pdo->exec( 'ROLLBACK' );

					return $refused;
				}

				$run = Db::runner( $pdo );

				// What the packages keep of the clients goes in the same transaction, each in front of the step of the core it names
				$packages = static function( string $step ) use ( &$appData, $run, &$counts ): void {

					foreach( self::entered( $appData ) as $key => $package ) {

						if( $package['rows'] === null || ( $package['rows_before'] ?? 'sequences' ) !== $step )
							continue;

						$gone = call_user_func_array( $package['rows'], [ &$appData, $run ] );

						if( $package['count'] === true )
							$counts[$key] = (int) ( $counts[$key] ?? 0 ) + (int) $gone;
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
		private static function projects( array &$appData, array &$counts, string $began, bool $dead = false ): bool {

			Projects::forget( $appData );

			foreach( Projects::list( $appData ) as $project ) {

				// Before every project: the run still stands. Where the database is another one since - a restore - the rest is not done, with a warning and no failure
				if( self::stands( $appData, $began ) === false ) {
					trigger_error( 'Crm: the run of the test phase does not stand any more, the projects that are left are not deleted', E_USER_WARNING );

					return true;
				}

				// Where the row that said what is left was thrown away, only the projects of clients that are gone are deleted
				if( $dead === true && Clients::byNumber( $appData, (string) $project['client'] ) !== null )
					continue;

				if( \Nino\Elements::deleteElement( $appData, Projects::TYPE. '/'. $project['id'], '*' ) !== true )
					return false;

				$counts['projects']++;
			}

			// The projects number again from 00001: the counter of the type is seeded from what it holds, which is nothing
			if( $dead === true || self::stands( $appData, $began ) === false )
				return true;

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
