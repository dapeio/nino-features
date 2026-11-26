<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Db		The SQLite layer of the core: one file, /data/crm.sqlite, one connection for each file and request, kept in $appData. The core's schema is
	 *				Db/schema.php, whole, version 1; a package that has tables of its own enters them with addSchema() - key, version, the tables it owns and the
	 *				file that holds the statements - in its init(), and Db::ensure() makes or raises them in a transaction of their own on the first use of the
	 *				connection. The register of the schemas of the file is the table schema_versions: a row for each package, the version it made and the tables it
	 *				owns, so that a package that is switched off or removed leaves its rows and tables where they are and the core knows them as orphans() - and can
	 *				take the data of one that is not installed any more away with dropSchema(), and only that.
	 *
	 *				The file is the core's or nobody's: a file that holds another database - the file of an older program at the same path, a file that is no database
	 *				at all - is told by the header of the file (the application id the core writes into a file it makes, which no other file has) and is never opened for
	 *				writing, never given a table and never removed; pdo() throws a \PDOException that says so, and inspect() says it as a state for the status.
	 *
	 *				The journal is DELETE and not WAL, so that there is no file of a log beside the file - a copy of a database in WAL mode misses what is still in the log -
	 *				and secure_delete zeroes what a deletion frees. What a backup carries is not the file as it lies but a whole copy of it, which the core makes when the
	 *				backup asks (backup(), with VACUUM INTO, called by Crm::callbackBackup()): the state of the file before a write that is open.
	 *				Transactions are short: BEGIN IMMEDIATE, the writes, COMMIT. A failure is a \PDOException that the caller answers.
	 *
	 *				The core is the file; a package is a guest in it. A core whose schema cannot be made is no connection, but a package whose schema cannot be made or raised is that package alone: its entry
	 *				is taken back with a warning that names it and the version, failed() lists it, the status shows it as a line in red, and the connection works for the core and the others. A package enters
	 *				its schema under its own key - the key of a feature that is on - and its statements are of the forms a schema needs, aimed at its own tables (see addSchema()).
	 *
	 *	@package						Dape/Nino
	 */
	class Db {

		/**	The file of the database, below the private directory	*/
		public const string FILE = '/data/crm.sqlite';

		/**	The key of the core in the register of the schemas, and the version of its schema (Db/schema.php)	*/
		public const string CORE = 'crm';
		public const int VERSION = 1;

		/**	The tables the core makes: no package may have one of these names	*/
		public const array TABLES = [ 'schema_versions', 'clients', 'settings', 'sequences', 'records', 'project_states', 'project_shares', 'tasks', 'logbook', 'owner_devices' ];

		/**	What the header of a file made by the core says of it (PRAGMA application_id): the letters NCRM	*/
		public const int APPLICATION_ID = 0x4E43524D;

		/**	How long a connection waits for a lock another process holds, in milliseconds	*/
		public const int BUSY = 5000;

		// What the module keeps of the database for the request: the connections by file, the files whose schemas were checked, and the schemas packages entered
		private const string CONNECTIONS = './crm/db/connections';

		// The device and inode of the file each connection of the request was opened on, by file (see kindFor())
		private const string INODES = './crm/db/inodes';
		private const string ENSURED = './crm/db/ensured';
		private const string SCHEMAS = './crm/db/schemas';

		// What the module keeps of the answers of the database for the request: the number of the request it was read in, and the answers by group and question
		private const string KEPT = './crm/db/kept';

		// What the module keeps of the failures of the packages (key => the version asked for) and of what was set on the connections (file => the pragmas, in order)
		private const string FAILED = './crm/db/failed';
		private const string PRAGMA_LOG = './crm/db/pragmas';

		// What is set on every connection, in this order: the wait for a lock, the keys between tables, the journal that keeps the file whole, and the zeroing of what a deletion frees
		private const array PRAGMAS = [ 'busy_timeout = '. self::BUSY, 'foreign_keys = ON', 'journal_mode = DELETE', 'secure_delete = ON' ];

		// The statements the runner of a deletion may not run inside the transaction of the core: the ones that begin, end or leave it, change the connection or the file
		private const string OUTSIDE = '/^(?:\s|--[^\n]*(?:\n|$)|\/\*.*?\*\/)*(?:BEGIN|COMMIT|END|ROLLBACK|SAVEPOINT|RELEASE|ATTACH|DETACH|PRAGMA|VACUUM)\b/isD';

		// The conflict words a statement of a schema may carry (INSERT OR ...); ROLLBACK is none of them: it would end the transaction of the run
		private const array OR_WORDS = [ 'IGNORE', 'REPLACE', 'ABORT', 'FAIL' ];

		// The shape of a table name a package may have, and of the time written to the register
		private const string TABLE = '/^[a-z][a-z0-9_]{0,62}$/D';
		private const string TIME = 'Y-m-d\TH:i:s\Z';

		/**
		 *	The connection to the database, opened and checked on first use: a file that is not there is made with the core's schema, one that is the core's
		 *	is raised to the schemas the packages entered, and one that is another database is no connection at all
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	\PDO
		 *
		 *	@throws		\PDOException							The file is not the core's, cannot be opened or written, or a schema fails - nothing is made half
		 */
		public static function pdo( array &$appData ): \PDO {

			$file = \Nino\Filesystem::path( $appData, self::FILE );
			$pdo = $appData[self::CONNECTIONS][$file] ?? null;

			if( $pdo instanceof \PDO === false ) {
				$pdo = self::connect( $appData, $file );
				$appData[self::CONNECTIONS][$file] = $pdo;
			}

			if( isset( $appData[self::ENSURED][$file] ) === false )
				self::ensure( $appData );

			return $pdo;
		}

		/**
		 *	Let go of the connections of this request: the next pdo() opens and checks the file anew
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function close( array &$appData ): void {

			unset( $appData[self::CONNECTIONS], $appData[self::INODES], $appData[self::ENSURED] );
		}

		/**
		 *	What a file is: absent, empty (a file that was made and has nothing yet), the core's, or foreign - a database that is not the core's, a file that
		 *	is no database, a directory. Read from the header of the file alone: nothing is opened, so nothing of a foreign file is touched. A file with the mark of the core and no row of the core in its register is called ours here, as the header says; inspect() and pdo() tell it as it is - not the database of the CRM
		 *
		 *	@param		string		$file
		 *	@param		array 		$held					Device and inode of every file a connection of this process is open on, as held() has them: such a file is ours and is not read (see kindFor())
		 *
		 *	@return 	string										absent, empty, ours, foreign or unreadable
		 */
		public static function kind( string $file, array $held = [] ): string {

			clearstatcache( true, $file );

			if( file_exists( $file ) === false && is_link( $file ) === false )
				return 'absent';

			if( is_file( $file ) === false )
				return 'foreign';

			// A file a connection is open on is not opened here: close() of any descriptor of a file lets go of every POSIX lock the process holds on it (fcntl(2)) - the locks of SQLite
			// of that connection too, the write lock of an open transaction among them - and another process then writes into the middle of that transaction
			$stat = $held === [] ? false : stat( $file );

			if( is_array( $stat ) === true && in_array( [ $stat['dev'], $stat['ino'] ], $held, true ) === true )
				return 'ours';

			if( filesize( $file ) === 0 )
				return 'empty';

			if( is_readable( $file ) === false )
				return 'unreadable';

			$head = file_get_contents( $file, false, null, 0, 100 );

			if( is_string( $head ) === false || strlen( $head ) < 72 || str_starts_with( $head, "SQLite format 3\0" ) === false )
				return 'foreign';

			return (int) ( unpack( 'N', substr( $head, 68, 4 ) )[1] ?? 0 ) === self::APPLICATION_ID ? 'ours' : 'foreign';
		}

		/**
		 *	What kind() says of the file, for a request: a file that a connection of the request is open on - the same device and inode - is ours as connect() found it, and is never opened and closed beside that connection.
		 *	Every caller that can run while the request holds a connection, a transaction among them, asks this and never kind()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$file
		 *
		 *	@return 	string										As kind()
		 */
		public static function kindFor( array &$appData, string $file ): string {

			return self::kind( $file, self::held( $appData ) );
		}

		/**
		 *	The device and inode of the files the connections of this request are open on: the connection of pdo() and the reader of the settings
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of [ device, inode ]
		 */
		public static function held( array &$appData ): array {

			$held = [];

			foreach( $appData[self::CONNECTIONS] ?? [] as $file => $pdo )
				if( $pdo instanceof \PDO && ( $appData[self::INODES][$file] ?? [] ) !== [] )
					$held[] = $appData[self::INODES][$file];

			return array_merge( $held, Settings::held( $appData ) );
		}

		/**
		 *	The device and inode of a file that was just opened
		 *
		 *	@param		string		$file
		 *
		 *	@return 	array											[ device, inode ], [] where the file cannot be told
		 */
		public static function identity( string $file ): array {

			clearstatcache( true, $file );
			$stat = stat( $file );

			return is_array( $stat ) === true ? [ $stat['dev'], $stat['ino'] ] : [];
		}

		/**
		 *	Open the file the way the core uses it, without making or raising anything
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$file
		 *
		 *	@return 	\PDO
		 *
		 *	@throws		\PDOException
		 */
		private static function connect( array &$appData, string $file ): \PDO {

			\Nino\Filesystem::forceDir( $appData, '/data' );

			if( is_dir( dirname( $file ) ) === false )
				throw new \PDOException( 'the directory of the database cannot be made' );

			$kind = self::kindFor( $appData, $file );

			if( $kind === 'foreign' || $kind === 'unreadable' )
				throw new \PDOException( $kind === 'foreign' ? 'the file is not the database of the CRM' : 'the database of the CRM cannot be read' );

			$pdo = new \PDO( 'sqlite:'. $file, null, null, [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES => false,
			] );

			foreach( self::PRAGMAS as $pragma ) {
				$pdo->exec( 'PRAGMA '. $pragma );
				$appData[self::PRAGMA_LOG][$file][] = $pragma;
			}

			$appData[self::INODES][$file] = self::identity( $file );

			return $pdo;
		}

		/**
		 *	A whole copy of the database for a backup, written with VACUUM INTO to the file the caller names. It is made through a connection of its own - the connection of the request
		 *	is never used, so that nothing is made, raised or written to the file - and only for a file that is the core's by its header: a file that is not there, one that was made and
		 *	has nothing in it, and one that is another database, a text or a directory are none to hand over (false), and are never opened. A file that cannot be read, that stays locked
		 *	for longer than BUSY, or that is damaged is no copy: it throws, and nothing is left at the target. What is checked is the copy: PRAGMA integrity_check, the full one, since VACUUM INTO carries
		 *	the trees of the indexes over as they are and a copy of a file whose index lacks a row, holds a row that is none or has two rows of a unique key has the same damage, and the application id of the core,
		 *	which the copy has to carry; and the file, before the copy is looked at, with PRAGMA quick_check, which finds what the copy does not show - the count of the free pages in the header, say, which the copy
		 *	has made new. A foreign key that no row answers to is not looked at - PRAGMA integrity_check does not look at foreign keys (PRAGMA foreign_key_check does) -, so that what integrity_check passes is handed over as it is, a row of a note whose client is gone, say, which only a writer with foreign_keys off can have made (the core writes with it on). The connection opens the file for writing, as every connection of the core does: where a process that wrote to the file was ended in the middle of a transaction and left its journal beside it
		 *	(a hot journal), SQLite rolls the journal back before it reads - the one thing this call writes below data/, and the file is whole after it as it was before that transaction, which is the state the copy then has;
		 *	opened for reading only, SQLite cannot do it and the call throws until the next writer does. Where a writer has begun a transaction and not ended it, and has written nothing to the file yet (a transaction
		 *	keeps its changes in the cache of its connection until the cache is full or the transaction ends), the copy is the state before that transaction and the writer is not waited for; where it has written
		 *	to the file - a large transaction, or one at its commit - the reader waits BUSY for it and then throws. The copy has the application id of the core, as VACUUM INTO carries it over, has no free pages, and has no file of a log beside it
		 *
		 *	@param		string		$file					The live file
		 *	@param		string		$target				Where the copy is written: a file that does not exist
		 *	@param		array 		$held					What held() says of the caller's connections: the live file is not read for its header when a connection of the caller is open on it
		 *
		 *	@return 	bool											true where a copy was written, false where there is nothing of the core to hand over
		 *
		 *	@throws		\PDOException							The file cannot be read or copied, or the copy is not whole
		 */
		public static function backup( string $file, string $target, array $held = [] ): bool {

			$kind = self::kind( $file, $held );

			if( $kind === 'unreadable' )
				throw new \PDOException( 'the database of the CRM cannot be read' );

			if( $kind !== 'ours' )
				return false;

			if( file_exists( $target ) === true || is_link( $target ) === true )
				throw new \PDOException( 'the copy of the database of the CRM has a file in its way' );

			$live = null;

			try {

				$live = new \PDO( 'sqlite:'. $file, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] );
				$live->exec( 'PRAGMA busy_timeout = '. self::BUSY );

				$live->exec( 'VACUUM INTO '. $live->quote( $target ) );

				// The copy does not show every damage of the file: the count of the free pages, say, is made new
				if( $live->query( 'PRAGMA quick_check' )->fetchColumn() !== 'ok' )
					throw new \PDOException( 'the database of the CRM is damaged' );

				// The copy is what is handed over, and it is looked at whole
				self::verifyCopy( $target );
			}
			catch( \PDOException $e ) {

				$live = null;

				foreach( [ '', '-journal', '-wal', '-shm' ] as $suffix )
					if( is_file( $target. $suffix ) === true )
						@unlink( $target. $suffix );

				throw $e;
			}

			return true;
		}

		/**
		 *	Look at a copy of the database that is to be handed over, whole: PRAGMA integrity_check, the full one - every index against its table, the unique keys, the pages - and the application id of the core in its header.
		 *	The file is opened for reading only and nothing is written to it - a copy that VACUUM INTO made has the journal mode DELETE; a file with the header of the log mode would get its -wal and -shm beside it even so. A foreign key that no row answers to is not looked at: integrity_check does not look at foreign keys, and what it passes is handed over as it is
		 *
		 *	@param		string		$copy					The file of the copy
		 *
		 *	@return 	void
		 *
		 *	@throws		\PDOException							The copy cannot be opened, is damaged or is not the core's
		 */
		public static function verifyCopy( string $copy ): void {

			$pdo = new \PDO( 'sqlite:'. $copy, null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::SQLITE_ATTR_OPEN_FLAGS => \PDO::SQLITE_OPEN_READONLY ] );

			if( $pdo->query( 'PRAGMA integrity_check' )->fetchAll( \PDO::FETCH_COLUMN ) !== [ 'ok' ] || (int) $pdo->query( 'PRAGMA application_id' )->fetchColumn() !== self::APPLICATION_ID )
				throw new \PDOException( 'the copy of the database of the CRM is damaged' );
		}

		/**
		 *	What was set on the connection of this request, in the order it was set: the pragmas of PRAGMAS, one entry for each. For a test and for the status: the connection says what it
		 *	has, this says what the code asked of it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of the pragmas, without the word PRAGMA; empty where no connection was opened
		 */
		public static function pragmas( array &$appData ): array {

			return (array) ( $appData[self::PRAGMA_LOG][\Nino\Filesystem::path( $appData, self::FILE )] ?? [] );
		}

		/**
		 *	The packages whose schema could not be made or raised in this request: key => the version they asked for. A package that is here is not in the schemas of the request - its
		 *	entry was refused - and the core and the other packages work as if it had entered none
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array
		 */
		public static function failed( array &$appData ): array {

			return (array) ( $appData[self::FAILED] ?? [] );
		}

		/**
		 *	What the module keeps for the request of the answers of the database
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$group				What the answer is about (settings): the unit that drops it
		 *	@param		string		$question			What was asked, with every value the answer depends on
		 *	@param		callable	$read					Reads the answer where none is kept
		 *
		 *	@return 	mixed
		 */
		public static function kept( array &$appData, string $group, string $question, callable $read ): mixed {

			$request = count( $appData['./nino/http/requests'] ?? [] );

			if( ( $appData[self::KEPT]['request'] ?? null ) !== $request )
				$appData[self::KEPT] = [ 'request' => $request, 'answers' => [] ];

			if( array_key_exists( $question, $appData[self::KEPT]['answers'][$group] ?? [] ) === false ) {

				$answer = $read();
				$appData[self::KEPT]['request'] = $request;
				$appData[self::KEPT]['answers'][$group][$question] = $answer;
			}

			return $appData[self::KEPT]['answers'][$group][$question];
		}

		/**
		 *	Drop what kept() holds in a group: what the group is about was written
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$group				'' for every group
		 *
		 *	@return 	void
		 */
		public static function drop( array &$appData, string $group = '' ): void {

			if( $group === '' )
				unset( $appData[self::KEPT] );
			else
				unset( $appData[self::KEPT]['answers'][$group] );
		}

		// --- the schemas ---------------------------------------------------------------------------------------------------------

		/**
		 *	Enter the schema of a package: the tables it owns and the file that holds their statements. Nothing is opened, made or read from the file here -
		 *	the first use of the connection does it, so that no init() touches the disk. The file returns [ 'create' => [ statement, ... ], 'steps' => [ 2 => [ statement, ... ], ... ] ]:
		 *	the whole schema of the version, for a file that has none of it, and for each version n the statements that lead from n - 1 to n, for a file that has an older one
		 *	(none at version 1). A statement is one statement that begins with one of the forms a step of a schema needs - CREATE TABLE, CREATE [UNIQUE] INDEX, ALTER TABLE ... ADD,
		 *	RENAME or DROP, DROP TABLE, DROP INDEX, INSERT [OR ...] INTO, REPLACE INTO, UPDATE [OR ...] and DELETE FROM - and whose target is a table of the package (an index: the table
		 *	it is on), named as the package's own tables are - plain, in quotation marks, in square brackets or backticks, or with the schema main. Anything else is refused and never run:
		 *	temporary tables, WITH, views, triggers, ATTACH, PRAGMA, a transaction's own statements, a second statement, and any target that is the core's, another package's, schema_versions
		 *	or sqlite_*. A run is checked once more after it ran (verify()): what the file holds of the core and of the others is as it was, or the whole run goes back.
		 *
		 *	What this holds and what it does not: the target of the statement is the package's, so no statement of a package writes outside its tables. What it reads is not held: a
		 *	subquery, INSERT ... SELECT or UPDATE ... FROM may read any table of the file, the core's and the clients' too - that is how a package lifts its own rows from the clients -
		 *	and a package is code of the site: this keeps its schema from breaking the core by a mistake, it is no sandbox for a package that means harm. The same description again is as good as the first time
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					package (the key of the feature that enters it, see Register::package()), version (a whole number from 1), tables (the tables it owns:
		 *																	lower case letters, digits and underscores, none of the core's and none of another package's) and file (the absolute path of a php file
		 *																	in the folder of the package)
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - nothing is entered then
		 */
		public static function addSchema( array &$appData, array $spec ): bool {

			$added = Register::add( $appData, self::SCHEMAS, 'schema', [ $spec ], [
				'id' => 'package',
				// A package enters one schema, under its own key
				'idRule' => Register::packageRule( $appData ),
				'fields' => [ 'version' => Register::REQUIRED, 'tables' => Register::REQUIRED, 'file' => Register::REQUIRED ],
				'rules' => [
					'version' => static fn( mixed $version ): bool => is_int( $version ) === true && $version >= 1 && $version <= 100000,
					'tables' => static fn( mixed $tables, array $full, array $known ): bool => self::tablesAllowed( $tables, (string) $full['package'], $known ),
					'file' => static function( mixed $file, array $full ) use ( &$appData ): bool { return self::fileAllowed( $appData, $file, (string) $full['package'] ); },
				],
			] );

			// A schema that comes after the connection is checked with the next use of it
			if( $added === true )
				unset( $appData[self::ENSURED] );

			return $added;
		}

		/**
		 *	Whether a list of tables is one a package may own
		 *
		 *	@param		mixed			$tables
		 *	@param		string		$key					The package
		 *	@param		array 		$known				The schemas entered so far
		 *
		 *	@return 	bool
		 */
		private static function tablesAllowed( mixed $tables, string $key, array $known ): bool {

			if( is_array( $tables ) === false || array_is_list( $tables ) === false || $tables === [] || count( $tables ) > 40 )
				return false;

			$others = [];

			foreach( $known as $other => $schema )
				if( $other !== $key )
					$others = array_merge( $others, (array) $schema['tables'] );

			foreach( $tables as $table )
				if( is_string( $table ) === false || preg_match( self::TABLE, $table ) !== 1 || str_starts_with( $table, 'sqlite_' ) === true
					|| in_array( $table, self::TABLES, true ) === true || in_array( $table, $others, true ) === true )
					return false;

			return count( array_unique( $tables ) ) === count( $tables );
		}

		/**
		 *	Whether a file is one a schema may be read from: a php file in the folder of the package that enters it, the folder the kernel has for that feature (Register::within())
		 *
		 *	@param		array 		&$appData
		 *	@param		mixed			$file
		 *	@param		string		$package			The key of the package that enters the schema
		 *
		 *	@return 	bool
		 */
		private static function fileAllowed( array &$appData, mixed $file, string $package ): bool {

			if( is_string( $file ) === false || str_contains( $file, "\0" ) === true || str_starts_with( $file, '/' ) === false || str_ends_with( $file, '.php' ) === false || is_file( $file ) === false )
				return false;

			$folder = Register::package( $appData, $package )['folder'] ?? '';

			return is_string( $folder ) === true && Register::within( $file, $folder );
		}

		/**
		 *	The schemas the file is to have: the core's and the ones the packages entered, in the order they were entered
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => version, tables, file, folder (the name of the folder the kernel has for the package, as it lists it)
		 */
		private static function wanted( array &$appData ): array {

			$wanted = [ self::CORE => [ 'version' => self::VERSION, 'tables' => self::TABLES, 'file' => __DIR__. '/schema.php', 'folder' => '' ] ];

			foreach( Register::all( $appData, self::SCHEMAS ) as $key => $schema )
				$wanted[(string) $key] = [ 'version' => (int) $schema['version'], 'tables' => (array) $schema['tables'], 'file' => (string) $schema['file'], 'folder' => Register::directory( $appData, (string) $key ) ];

			return $wanted;
		}

		/**
		 *	The versions the code is written for: the core's and the ones the packages entered in this request
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => version, the core first
		 */
		public static function versions( array &$appData ): array {

			return array_map( static fn( array $schema ): int => $schema['version'], self::wanted( $appData ) );
		}

		/**
		 *	Make or raise the schemas of the file: the core's first, then the packages'. Each is made in a transaction of its own (BEGIN IMMEDIATE), read again inside it, so
		 *	that two processes that start together on an empty disk make it once, run through the statements, checked - every table the package says it owns is there and
		 *	nothing that is not its own has changed - and written to the register. A schema that fails is nothing: its transaction goes back. A file that holds a newer
		 *	version than the code knows is not downgraded
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int												How many schemas were made or raised
		 *
		 *	@throws		\PDOException
		 */
		public static function ensure( array &$appData ): int {

			$file = \Nino\Filesystem::path( $appData, self::FILE );
			$pdo = $appData[self::CONNECTIONS][$file] ?? null;

			if( $pdo instanceof \PDO === false ) {
				$pdo = self::connect( $appData, $file );
				$appData[self::CONNECTIONS][$file] = $pdo;
			}

			$ran = 0;
			$stored = self::stored( $pdo );

			foreach( self::wanted( $appData ) as $key => $schema ) {

				$have = (int) ( $stored[$key]['version'] ?? 0 );

				if( $have === $schema['version'] ) {

					// The folder the register keeps is the one the kernel has now: a package that was put in another folder says so
					if( $key !== self::CORE && ( $stored[$key]['folder'] ?? '' ) !== $schema['folder'] )
						self::keep( $pdo, $key, $schema['folder'] );

					continue;
				}

				// The core is the file: a core that fails is no database. A package that fails is that package alone - it is refused, and the rest works
				if( $key === self::CORE ) {
					$ran += self::raise( $pdo, $key, $schema );
					continue;
				}

				try {
					$ran += self::raise( $pdo, $key, $schema );
				}
				catch( \PDOException ) {
					self::fail( $appData, $key, (int) $schema['version'] );
				}
			}

			$appData[self::ENSURED][$file] = true;

			return $ran;
		}

		/**
		 *	Write the folder of a package into its row of the register. A write that cannot be made now (the file is held) leaves the old name, which is no harm: a package is removed only where its folder is not there and nothing
		 *	the kernel lists is unknown
		 *
		 *	@param		\PDO			$pdo
		 *	@param		string		$key
		 *	@param		string		$folder
		 *
		 *	@return 	void
		 */
		private static function keep( \PDO $pdo, string $key, string $folder ): void {

			try {
				$pdo->prepare( 'UPDATE schema_versions SET folder = :folder WHERE package = :package' )->execute( [ 'folder' => $folder, 'package' => $key ] );
			}
			catch( \PDOException ) {
				// Written with the next request
			}
		}

		/**
		 *	A package whose schema could not be made or raised: its entry is taken back, so that nothing of this request treats it as a schema of the file, and it is noted for the status. The
		 *	warning names the package and the version, nothing of the file
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		int				$version
		 *
		 *	@return 	void
		 */
		private static function fail( array &$appData, string $key, int $version ): void {

			unset( $appData[self::SCHEMAS][$key] );
			$appData[self::FAILED][$key] = $version;

			trigger_error( 'Crm: the schema of the package '. $key. ' could not be raised to version '. $version. ' - its entry is refused and the package does without its tables', E_USER_WARNING );
		}

		/**
		 *	Make or raise the schema of one package, in a transaction of its own
		 *
		 *	@param		\PDO			$pdo
		 *	@param		string		$key
		 *	@param		array 		$schema				version, tables and file
		 *
		 *	@return 	int												1 where it was made or raised, 0 where another process had
		 *
		 *	@throws		\PDOException
		 */
		private static function raise( \PDO $pdo, string $key, array $schema ): int {

			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				// Read again inside the transaction: another process may have made it while this one waited for the lock
				$stored = self::stored( $pdo );
				$have = (int) ( $stored[$key]['version'] ?? 0 );

				if( $have > $schema['version'] )
					throw new \PDOException( 'the file holds a newer schema of '. $key. ' than the code knows' );

				if( $have === $schema['version'] ) {
					$pdo->exec( 'COMMIT' );

					return 0;
				}

				self::apply( $pdo, $key, $schema, $have, $stored );
				$pdo->exec( 'COMMIT' );

				return 1;
			}
			catch( \Throwable $e ) {

				try {
					$pdo->exec( 'ROLLBACK' );
				}
				catch( \PDOException ) {
					// No transaction is open: the failure that is thrown on is the one that matters
				}

				throw $e instanceof \PDOException ? $e : new \PDOException( 'the schema of '. $key. ' failed: '. $e->getMessage() );
			}
		}

		/**
		 *	Run the statements of a schema and write it into the register, inside the transaction the caller holds
		 *
		 *	@param		\PDO			$pdo
		 *	@param		string		$key
		 *	@param		array 		$schema
		 *	@param		int				$have					The version the file has, 0 for none
		 *	@param		array 		$stored				The register as the transaction reads it
		 *
		 *	@return 	void
		 *
		 *	@throws		\PDOException
		 */
		private static function apply( \PDO $pdo, string $key, array $schema, int $have, array $stored ): void {

			$source = include $schema['file'];

			if( is_array( $source ) === false || is_array( $source['create'] ?? null ) === false || is_array( $source['steps'] ?? [] ) === false )
				throw new \PDOException( 'the file of the schema of '. $key. ' does not return create and steps' );

			if( $have === 0 )
				$statements = $source['create'];
			else {

				$statements = [];

				for( $step = $have + 1; $step <= $schema['version']; $step++ ) {

					if( is_array( $source['steps'][$step] ?? null ) === false )
						throw new \PDOException( 'the schema of '. $key. ' has no step to version '. $step );

					$statements = array_merge( $statements, $source['steps'][$step] );
				}
			}

			$before = self::objects( $pdo );

			// The core makes the file: one that holds anything of another program is not the core's, and the transaction goes back without a table made in it
			if( $key === self::CORE && $have === 0 && ( $before !== [] || (int) $pdo->query( 'PRAGMA application_id' )->fetchColumn() !== 0 ) )
				throw new \PDOException( 'the file is not the database of the CRM' );

			$others = [];

			foreach( $stored as $package => $row )
				if( $package !== $key )
					$others = array_merge( $others, $row['tables'] );

			foreach( $schema['tables'] as $table )
				if( in_array( $table, $others, true ) === true )
					throw new \PDOException( 'the table '. $table. ' belongs to another package' );

			// What the package owns while the run is on: its tables of this version and the ones it had before - a step may drop one that this version no longer has
			$previous = $have === 0 ? [] : array_diff( (array) ( $stored[$key]['tables'] ?? [] ), $key === self::CORE ? [] : self::TABLES, $others );
			$owned = array_values( array_unique( array_merge( $schema['tables'], $previous ) ) );

			foreach( $statements as $statement ) {

				if( self::allowed( $pdo, $statement, $owned ) === false )
					throw new \PDOException( 'a statement of the schema of '. $key. ' is none of the forms a schema may run, or its target is not a table of the package' );

				$pdo->exec( $statement );
			}

			self::verify( $key, $schema['tables'], $owned, $before, self::objects( $pdo ) );

			if( $key === self::CORE && $have === 0 )
				$pdo->exec( 'PRAGMA application_id = '. self::APPLICATION_ID );

			$write = $pdo->prepare( 'INSERT INTO schema_versions ( package, version, tables, applied, folder ) VALUES ( :package, :version, :tables, :applied, :folder )
				ON CONFLICT( package ) DO UPDATE SET version = excluded.version, tables = excluded.tables, applied = excluded.applied, folder = excluded.folder' );
			$write->execute( [ 'package' => $key, 'version' => $schema['version'], 'tables' => json_encode( array_values( $schema['tables'] ) ), 'applied' => gmdate( self::TIME ), 'folder' => $schema['folder'] ] );
		}

		/**
		 *	Whether a statement of a schema may run: one statement of one of the allowed forms, aimed at a table of the package (see addSchema() for the forms and for what this holds)
		 *
		 *	@param		\PDO			$pdo					The connection, inside the transaction: an index is looked up where it is
		 *	@param		mixed			$statement
		 *	@param		array 		$tables				The tables of the package
		 *
		 *	@return 	bool
		 */
		private static function allowed( \PDO $pdo, mixed $statement, array $tables ): bool {

			if( is_string( $statement ) === false || str_contains( $statement, "\0" ) === true )
				return false;

			$tokens = self::tokens( $statement );

			if( $tokens === null || $tokens === [] )
				return false;

			// One statement: a semicolon is allowed at the very end and nowhere else
			if( end( $tokens ) === [ 'p', ';' ] )
				array_pop( $tokens );

			if( in_array( [ 'p', ';' ], $tokens, true ) === true )
				return false;

			$at = 0;
			$word = static fn( int $i, string ...$names ): bool => isset( $tokens[$i] ) === true && $tokens[$i][0] === 'w' && in_array( strtoupper( $tokens[$i][1] ), $names, true );
			$own = static function( int &$i ) use ( &$tokens, $tables ): bool {

				$name = self::name( $tokens, $i );

				return $name !== null && in_array( $name, $tables, true ) === true;
			};
			// An index is the package's where it is on a table of the package, or is not there yet
			$index = static function( int &$i ) use ( &$tokens, $tables, $pdo ): bool {

				$name = self::name( $tokens, $i );

				if( $name === null )
					return false;

				$on = $pdo->prepare( 'SELECT tbl_name FROM sqlite_master WHERE type = \'index\' AND name = :name COLLATE NOCASE' );
				$on->execute( [ 'name' => $name ] );
				$table = $on->fetchColumn();

				return $table === false || in_array( strtolower( (string) $table ), $tables, true ) === true;
			};

			switch( true ) {

				case $word( 0, 'CREATE' ):

					$at = $word( 1, 'UNIQUE' ) === true ? 2 : 1;

					if( $at === 1 && $word( 1, 'TABLE' ) === true ) {
						$at = $word( 2, 'IF' ) === true && $word( 3, 'NOT' ) === true && $word( 4, 'EXISTS' ) === true ? 5 : 2;

						return $own( $at );
					}

					if( $word( $at, 'INDEX' ) === false )
						return false;

					$at = $word( $at + 1, 'IF' ) === true && $word( $at + 2, 'NOT' ) === true && $word( $at + 3, 'EXISTS' ) === true ? $at + 4 : $at + 1;

					if( $index( $at ) === false || $word( $at, 'ON' ) === false )
						return false;

					$at++;

					return $own( $at );

				case $word( 0, 'ALTER' ):

					$at = 2;

					if( $word( 1, 'TABLE' ) === false || $own( $at ) === false )
						return false;

					if( $word( $at, 'ADD', 'DROP' ) === true )
						return true;

					if( $word( $at, 'RENAME' ) === false )
						return false;

					// RENAME TO a table of the package; RENAME [COLUMN] a TO b renames a column of the table
					if( $word( $at + 1, 'TO' ) === true && isset( $tokens[$at + 2] ) === true ) {
						$at += 2;

						return $own( $at ) === true && isset( $tokens[$at] ) === false;
					}

					return true;

				case $word( 0, 'DROP' ):

					$at = $word( 2, 'IF' ) === true && $word( 3, 'EXISTS' ) === true ? 4 : 2;

					if( $word( 1, 'TABLE' ) === true )
						return $own( $at );

					return $word( 1, 'INDEX' ) === true && $index( $at );

				case $word( 0, 'INSERT' ):

					$at = 1;

					if( $word( 1, 'OR' ) === true ) {

						if( $word( 2, ...self::OR_WORDS ) === false )
							return false;

						$at = 3;
					}

					if( $word( $at, 'INTO' ) === false )
						return false;

					$at++;

					return $own( $at );

				case $word( 0, 'REPLACE' ):

					$at = 2;

					return $word( 1, 'INTO' ) === true && $own( $at );

				case $word( 0, 'UPDATE' ):

					$at = 1;

					if( $word( 1, 'OR' ) === true ) {

						if( $word( 2, ...self::OR_WORDS ) === false )
							return false;

						$at = 3;
					}

					return $own( $at );

				case $word( 0, 'DELETE' ):

					$at = 2;

					return $word( 1, 'FROM' ) === true && $own( $at );
			}

			return false;
		}

		/**
		 *	A statement as the words it is made of, the way SQLite reads it: comments dropped, a name in quotation marks, square brackets or backticks one word, a string one word. A
		 *	token is [ kind, text ] with the kind w (a bare word or name), i (a name in quotes or brackets), s (a string), n (a number) or p (any other character)
		 *
		 *	@param		string		$sql
		 *
		 *	@return 	array|null								null for a text SQLite would read as something else: a quote or a comment that is never closed
		 */
		private static function tokens( string $sql ): ?array {

			$tokens = [];
			$length = strlen( $sql );
			$i = 0;

			while( $i < $length ) {

				$char = $sql[$i];

				if( ctype_space( $char ) === true ) {
					$i++;

					continue;
				}

				if( $char === '-' && ( $sql[$i + 1] ?? '' ) === '-' ) {
					$end = strpos( $sql, "\n", $i );
					$i = $end === false ? $length : $end + 1;

					continue;
				}

				if( $char === '/' && ( $sql[$i + 1] ?? '' ) === '*' ) {
					$end = strpos( $sql, '*/', $i + 2 );

					if( $end === false )
						return null;

					$i = $end + 2;

					continue;
				}

				if( $char === "'" || $char === '"' || $char === '`' ) {

					$text = '';
					$from = $i + 1;

					while( true ) {

						$end = strpos( $sql, $char, $from );

						if( $end === false )
							return null;

						$text .= substr( $sql, $from, $end - $from );

						if( ( $sql[$end + 1] ?? '' ) === $char ) {
							$text .= $char;
							$from = $end + 2;

							continue;
						}

						$i = $end + 1;

						break;
					}

					$tokens[] = [ $char === "'" ? 's' : 'i', $text ];

					continue;
				}

				if( $char === '[' ) {
					$end = strpos( $sql, ']', $i + 1 );

					if( $end === false )
						return null;

					$tokens[] = [ 'i', substr( $sql, $i + 1, $end - $i - 1 ) ];
					$i = $end + 1;

					continue;
				}

				if( preg_match( '/\G[A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*/', $sql, $match, 0, $i ) === 1 ) {
					$tokens[] = [ 'w', $match[0] ];
					$i += strlen( $match[0] );

					continue;
				}

				if( preg_match( '/\G[0-9][0-9A-Za-z_.]*/', $sql, $match, 0, $i ) === 1 ) {
					$tokens[] = [ 'n', $match[0] ];
					$i += strlen( $match[0] );

					continue;
				}

				$tokens[] = [ 'p', $char ];
				$i++;
			}

			return $tokens;
		}

		/**
		 *	The name at a place of the tokens, lower case, and the place after it: a name, or the schema main and a dot and a name. A name of the schema temp or of any other is none
		 *
		 *	@param		array 		$tokens
		 *	@param		int				&$at					Where the name begins; where it ends afterwards
		 *
		 *	@return 	string|null								null where no name stands there, or it is in a schema that is not main
		 */
		private static function name( array $tokens, int &$at ): ?string {

			$first = $tokens[$at] ?? null;

			if( $first === null || in_array( $first[0], [ 'w', 'i' ], true ) === false )
				return null;

			$at++;

			if( ( $tokens[$at] ?? null ) !== [ 'p', '.' ] )
				return strtolower( $first[1] );

			$second = $tokens[$at + 1] ?? null;

			if( $second === null || in_array( $second[0], [ 'w', 'i' ], true ) === false || strtolower( $first[1] ) !== 'main' )
				return null;

			$at += 2;

			return strtolower( $second[1] );
		}

		/**
		 *	The objects of the file: name => type, table and the statement that made it
		 *
		 *	@param		\PDO			$pdo
		 *
		 *	@return 	array
		 */
		private static function objects( \PDO $pdo ): array {

			$objects = [];

			foreach( $pdo->query( 'SELECT name, type, tbl_name, sql FROM sqlite_master WHERE name NOT LIKE \'sqlite\_%\' ESCAPE \'\\\'' )->fetchAll() as $row )
				$objects[(string) $row['name']] = [ (string) $row['type'], (string) $row['tbl_name'], (string) $row['sql'] ];

			return $objects;
		}

		/**
		 *	That a run changed nothing but what the package owns: every table it says it owns is there, what was there before is as it was - unless it belongs to one of
		 *	the package's tables - and what is new belongs to one of them
		 *
		 *	@param		string		$key
		 *	@param		array 		$tables				The tables of the package in this version: every one is there afterwards
		 *	@param		array 		$owned				The tables the package owned during the run: these and the ones of its earlier versions
		 *	@param		array 		$before				objects() before
		 *	@param		array 		$after				objects() after
		 *
		 *	@return 	void
		 *
		 *	@throws		\PDOException
		 */
		private static function verify( string $key, array $tables, array $owned, array $before, array $after ): void {

			foreach( $tables as $table )
				if( ( $after[$table][0] ?? '' ) !== 'table' )
					throw new \PDOException( 'the schema of '. $key. ' did not make the table '. $table );

			foreach( $before as $name => $object )
				if( in_array( $object[1], $owned, true ) === false && ( $after[$name] ?? null ) !== $object )
					throw new \PDOException( 'the schema of '. $key. ' changes what is not its own' );

			foreach( $after as $name => $object )
				if( isset( $before[$name] ) === false && in_array( $object[1], $owned, true ) === false )
					throw new \PDOException( 'the schema of '. $key. ' makes what is not its own' );
		}

		/**
		 *	The register of the schemas of the file, as it stands
		 *
		 *	@param		\PDO			$pdo
		 *
		 *	@return 	array											package => version, tables (a list of names, what a damaged row holds that is none is left out), applied, folder ('' where the row keeps none)
		 */
		private static function stored( \PDO $pdo ): array {

			if( $pdo->query( 'SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = \'schema_versions\'' )->fetchColumn() === false )
				return [];

			$stored = [];

			foreach( $pdo->query( 'SELECT package, version, tables, applied, folder FROM schema_versions ORDER BY package' )->fetchAll() as $row ) {

				$tables = json_decode( (string) $row['tables'], true );
				$tables = is_array( $tables ) === true && array_is_list( $tables ) === true ? array_values( array_filter( $tables, static fn( mixed $table ): bool => is_string( $table ) === true && preg_match( self::TABLE, $table ) === 1 ) ) : [];
				$stored[(string) $row['package']] = [ 'version' => (int) $row['version'], 'tables' => $tables, 'applied' => (string) $row['applied'], 'folder' => (string) $row['folder'] ];
			}

			return $stored;
		}

		/**
		 *	The schemas the code knows and the ones the file holds
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => registered (whether a package entered it in this request), version (the one the code is written for, null for none), stored (the one the file holds, null for none) and tables
		 */
		public static function schemas( array &$appData ): array {

			$stored = self::stored( self::pdo( $appData ) );
			$wanted = self::wanted( $appData );
			$all = [];

			foreach( $wanted + $stored as $key => $unused )
				$all[(string) $key] = [
					'registered' => isset( $wanted[$key] ),
					'version' => isset( $wanted[$key] ) === true ? $wanted[$key]['version'] : null,
					'stored' => isset( $stored[$key] ) === true ? $stored[$key]['version'] : null,
					'tables' => array_values( $wanted[$key]['tables'] ?? $stored[$key]['tables'] ?? [] ),
				];

			return $all;
		}

		/**
		 *	The same as orphans(), for a decision: null where the register could not be read, which no caller takes for none. A caller that is about to delete refuses then, with a word that says the database is busy
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|null								As orphans() has it; null for a file that is held, damaged or foreign
		 */
		public static function orphansKnown( array &$appData ): ?array {

			$file = self::inspect( $appData )['file'];

			// A file that is not made yet holds no data of anybody; one that cannot be read - held by another process, damaged, not the core's - is not the same as one that holds none
			if( in_array( $file, [ 'absent', 'empty' ], true ) === true )
				return [];

			return $file === 'ours' ? self::orphans( $appData ) : null;
		}

		/**
		 *	The schemas of the file that no package that is on stands for: the rows of the register of a package that is switched off, removed or whose init() did not
		 *	enter its schema in this request. The tables of such a package are as they were left; nothing reads or writes them but dropSchema(). A read: the file is not made
		 *	or raised for it, and one that is not the core's has none
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of package, version, tables and installed (whether the package is not removed, as Register::gone() has it, asked with the folder the register keeps for it: a package that is only off, or whose manifest cannot be read just now, or whose row keeps no folder, is still there and comes back)
		 */
		public static function orphans( array &$appData ): array {

			$seen = self::inspect( $appData );
			$wanted = self::wanted( $appData );
			$orphans = [];

			foreach( $seen['file'] === 'ours' ? $seen['stored'] : [] as $key => $row )
				if( $key !== self::CORE && isset( $wanted[$key] ) === false && \Nino\Modules\Crm::featureOn( $appData, (string) $key ) === false )
					$orphans[] = [ 'package' => (string) $key, 'version' => $row['version'], 'tables' => $row['tables'], 'installed' => Register::gone( $appData, (string) $key, $row['folder'] ) === false ];

			return $orphans;
		}

		/**
		 *	Take the data of a package that is gone away: its tables and its row of the register. Only for an orphan - a package that is on, one that entered its schema
		 *	in this request, one that is switched off but still installed (it comes back with its data) and the core are refused - and only the tables the register names
		 *	for it, each checked once more against the shape of a table name
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The package
		 *
		 *	@return 	int|string								How many tables were dropped; or why not: unknown (no such row or no such key), core, registered, on, installed, damaged (the register names a table that is none or is another's), referenced (a table of the core or of another package has a key to a table of this one: nothing is dropped) or failed
		 */
		public static function dropSchema( array &$appData, string $key ): int|string {

			if( $key === self::CORE )
				return 'core';

			if( Register::word( $key ) === false )
				return 'unknown';

			$pdo = self::pdo( $appData );
			$stored = self::stored( $pdo )[$key] ?? null;

			if( $stored === null )
				return 'unknown';

			if( isset( self::wanted( $appData )[$key] ) === true )
				return 'registered';

			if( \Nino\Modules\Crm::featureOn( $appData, $key ) === true )
				return 'on';

			if( Register::gone( $appData, $key, $stored['folder'] ) === false )
				return 'installed';

			$raw = json_decode( (string) $pdo->query( 'SELECT tables FROM schema_versions WHERE package = '. $pdo->quote( $key ) )->fetchColumn(), true );

			// A row that names what is no table of the package - a name that is none, a table of the core or of another package - is not trusted: nothing is dropped
			if( is_array( $raw ) === false || array_is_list( $raw ) === false || count( $raw ) !== count( $stored['tables'] ) )
				return 'damaged';

			$others = [];

			foreach( self::stored( $pdo ) as $package => $row )
				if( $package !== $key )
					$others = array_merge( $others, $row['tables'] );

			foreach( $stored['tables'] as $table )
				if( in_array( $table, self::TABLES, true ) === true || in_array( $table, $others, true ) === true )
					return 'damaged';

			$referenced = false;

			try {
				// The keys between tables are off while the tables go - the tables of a package point at each other, a row at its own table too, and with the keys on the order of the drops decides
				// whether one can be made - and on again whatever happens. They cannot be switched inside a transaction, so this is outside of it
				$pdo->exec( 'PRAGMA foreign_keys = OFF' );
				$pdo->exec( 'BEGIN IMMEDIATE' );

				// What does not belong to the package may not point at it: a table of the core or of another package that has a key to one of these tables would be left with a key to nothing
				foreach( $pdo->query( 'SELECT name FROM sqlite_master WHERE type = \'table\' AND name NOT LIKE \'sqlite\_%\' ESCAPE \'\\\'' )->fetchAll( \PDO::FETCH_COLUMN ) as $other ) {

					if( in_array( strtolower( (string) $other ), $stored['tables'], true ) === true )
						continue;

					foreach( $pdo->query( 'PRAGMA foreign_key_list( "'. str_replace( '"', '""', (string) $other ). '" )' )->fetchAll() as $reference )
						if( in_array( strtolower( (string) $reference['table'] ), $stored['tables'], true ) === true )
							$referenced = true;
				}

				if( $referenced === false ) {

					foreach( $stored['tables'] as $table )
						$pdo->exec( 'DROP TABLE IF EXISTS "'. $table. '"' );

					$pdo->prepare( 'DELETE FROM schema_versions WHERE package = :package' )->execute( [ 'package' => $key ] );
					$pdo->exec( 'COMMIT' );
				}
				else
					$pdo->exec( 'ROLLBACK' );
			}
			catch( \PDOException $e ) {

				try {
					$pdo->exec( 'ROLLBACK' );
				}
				catch( \PDOException ) {
					// No transaction is open
				}

				trigger_error( 'Crm: the data of a package that is gone could not be taken away ('. $key. ')', E_USER_WARNING );

				return 'failed';
			}
			finally {
				$pdo->exec( 'PRAGMA foreign_keys = ON' );
			}

			if( $referenced === true )
				return 'referenced';

			self::drop( $appData );

			return count( $stored['tables'] );
		}

		/**
		 *	What the file is and holds, for the status: read from the header and, where the file is the core's, from the register - never made, never raised, never opened
		 *	for writing
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											file (absent, empty, ours, foreign, unreadable, busy - another process holds it locked - or damaged), writable (whether a write can be made: the file and its directory, or for a file that is not there yet the nearest directory that is) and stored (package => version, tables)
		 */
		public static function inspect( array &$appData ): array {

			$file = \Nino\Filesystem::path( $appData, self::FILE );
			$kind = self::kindFor( $appData, $file );
			$stored = [];

			if( $kind === 'ours' ) {

				try {
					$pdo = new \PDO( 'sqlite:file:'. $file. '?mode=ro', null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC ] );
					$pdo->exec( 'PRAGMA busy_timeout = 200' );
					$stored = self::stored( $pdo );

					// The mark of the core and no row of the core in the register is what pdo() refuses as not the database of the CRM: the status says the same
					if( isset( $stored[self::CORE] ) === false )
						$kind = 'foreign';
				}
				catch( \PDOException $e ) {
					// A file another process holds locked is busy, not damaged: it is read when the lock is gone
					$kind = in_array( (int) ( $e->errorInfo[1] ?? 0 ) & 0xFF, [ 5, 6 ], true ) === true ? 'busy' : 'damaged';
				}
			}

			return [ 'file' => $kind, 'writable' => self::writable( $file, $kind ), 'stored' => $stored ];
		}

		/**
		 *	Whether a write to the file can be made: it needs the file and the directory it lies in (the journal is a file beside it), or, where there is no file yet, the nearest directory that exists
		 *
		 *	@param		string		$file
		 *	@param		string		$kind					As kind() has it
		 *
		 *	@return 	bool
		 */
		private static function writable( string $file, string $kind ): bool {

			$dir = dirname( $file );

			if( in_array( $kind, [ 'ours', 'damaged', 'foreign', 'unreadable' ], true ) === true )
				return is_writable( $file ) === true && is_writable( $dir ) === true;

			// A directory that is a file, or is below one, cannot be made
			while( file_exists( $dir ) === false && $dir !== dirname( $dir ) )
				$dir = dirname( $dir );

			return is_dir( $dir ) === true && is_writable( $dir ) === true && ( $kind === 'absent' || is_writable( $file ) === true );
		}

		// --- the statements --------------------------------------------------------------------------------------------------------

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$sql
		 *	@param		array 		$params
		 *
		 *	@return 	array										All rows
		 */
		public static function rows( array &$appData, string $sql, array $params = [] ): array {

			$statement = self::pdo( $appData )->prepare( $sql );
			$statement->execute( $params );

			return $statement->fetchAll();
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$sql
		 *	@param		array 		$params
		 *
		 *	@return 	array|null								The first row, null for none
		 */
		public static function row( array &$appData, string $sql, array $params = [] ): ?array {

			return self::rows( $appData, $sql, $params )[0] ?? null;
		}

		/**
		 *	The id of the row the last insert of the connection made: ask right after the exec() that inserted it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int										0 where nothing was inserted yet
		 */
		public static function lastId( array &$appData ): int {

			return (int) self::pdo( $appData )->lastInsertId();
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$sql
		 *	@param		array 		$params
		 *
		 *	@return 	int											The rows the statement changed
		 */
		public static function exec( array &$appData, string $sql, array $params = [] ): int {

			$statement = self::pdo( $appData )->prepare( $sql );
			$statement->execute( $params );

			// What kept() holds may have been moved by the write: whatever a page asks for next, it asks the database
			self::drop( $appData );

			return $statement->rowCount();
		}

		/**
		 *	The statement runner a package gets inside the transaction of a deletion (Erasure::add(), Wipe::add()): $run( string $sql, array $params = [] ): int runs one prepared statement and answers how many rows it changed. It refuses
		 *	what would end or leave the transaction of the caller - a statement that begins with BEGIN, COMMIT, END, ROLLBACK, SAVEPOINT, RELEASE, ATTACH, DETACH, PRAGMA or VACUUM, a comment in front of it included, and a text with
		 *	a second statement in it - with a \LogicException, which takes the step back. A semicolon anywhere in the text counts, in a literal or a comment as well: values belong in the parameters
		 *
		 *	@param		\PDO			$pdo
		 *
		 *	@return 	callable									( string $sql, array $params = [] ): int
		 */
		public static function runner( \PDO $pdo ): callable {

			return static function( string $sql, array $params = [] ) use ( $pdo ): int {

				if( preg_match( self::OUTSIDE, $sql ) === 1 || str_contains( rtrim( $sql, " \t\r\n;" ), ';' ) === true )
					throw new \LogicException( 'Crm: a statement of a package may not end or leave the transaction' );

				$statement = $pdo->prepare( $sql );
				$statement->execute( $params );

				return $statement->rowCount();
			};
		}
	}
}
