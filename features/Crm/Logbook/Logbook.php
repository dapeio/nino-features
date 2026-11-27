<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Logbook		The logbook of the clients and the projects: one row for each thing that happened, with the time, in the table logbook. An entry is about a client, a project or both, and holds the time (UTC),
	 *								the source (crm, or the key of the package that wrote it), the kind (a slug), the sentence the source wrote (text; empty where the source says the kind in its own words), a few small values for
	 *								that sentence (data), a reference of the source (ref: the id of a document, a record ...) and who did it (by: the address of an account, visitor or system). An entry that names a project and
	 *								not the client takes the client of the project. The module has no statement that changes an entry. An entry goes with the client it is about (Erasure, Wipe) and with the project when it names that
	 *								alone (Projects::callbackCommitted()) or is about a release of the project to another client (project-shared, project-unshared: they name the client it is released to, and the logbook of that
	 *								client and of the project show them); any other entry that names a client stays with the client when the project goes, and the page shows the project as text.
	 *
	 *								The opening for packages. add() is what a package calls to write into the logbook, with its own key as the source and a finished sentence in the language of the surface, as Menu::add() is
	 *								what it calls for the rail: the core does not know the package, shows the sentence as text (never as markup, a shortcode or a placeholder) and links nothing. A package that says its
	 *								sentence in its own script enters the kinds with addKinds() and then writes them with no text. The source crm is the core's own and is refused to add(); the core writes through crm() and note().
	 *
	 *								Writing follows what happened and never stands in its way: an entry that cannot be written is a warning in the log, and the step that asked goes on. The core writes on the connection of the step,
	 *								after the row it is about, so that an entry is there exactly when the step is.
	 *
	 *								add() answers the entry as of() has it, or a string: 'source' (no slug, or crm), 'kind' (no slug), 'target' (neither a client nor a project), 'client' (no such client), 'project' (not the id of
	 *								a project), 'text' (not a sentence of one line of at most TEXT characters, or empty where the source has not entered the kind), 'data' (not a flat list of small values of at most DATA bytes),
	 *								'ref', 'by', 'at' (not a time of the module) or 'write' (the database refused; a warning is in the log).
	 *
	 *	@package						Dape/Nino
	 */
	class Logbook {

		/**	The source of the core's own entries	*/
		public const string SOURCE = 'crm';

		/**	The kind of the line the owner writes	*/
		public const string NOTE = 'note';

		/**	The most characters of a sentence, the most bytes of the values, the longest reference, the longest value, the entries of a page and the most a page may ask for	*/
		public const int TEXT = 500;
		public const int DATA = 2048;
		public const int REF = 100;
		public const int VALUE = 200;
		public const int PAGE = 50;
		public const int MOST = 200;

		/**	The kinds the core writes	*/
		public const array KINDS = [ 'client-added', 'project-added', self::NOTE, 'project-shared', 'project-unshared' ];

		/**	The kinds of the entries about a release of a project to a client: they are about the client it is released to and the project, and go with the project	*/
		public const array OF_SHARE = [ 'project-shared', 'project-unshared' ];

		private const string SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';
		private const string TIME = 'Y-m-d\TH:i:s\Z';
		private const string STAMP = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D';

		// The marks a sentence is free of: a control character, a separator of lines, and a sign that turns the direction of the text around
		private const string CONTROL = '/[\x{0}-\x{1F}\x{7F}-\x{9F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

		// ...and, in a sentence of a package or of the owner, the marks that show nothing or turn the text: the two direction marks, the zero width space, the word joiner, the byte order mark and the Arabic letter mark
		private const string INVISIBLE = '/[\x{200B}\x{200E}\x{200F}\x{2060}\x{FEFF}\x{061C}]/u';

		// The kinds the packages entered, source => kinds, in the app data of the request
		private const string REGISTER = './crm/logbook/kinds';

		// How far ahead of the server's clock an entry may be dated (seconds)
		private const int AHEAD = 300;

		/**
		 *	Enter the kinds a package writes without a sentence: its script says them. A source and its kinds are the package's: a kind another source has entered, a source that is crm, a name that is no slug and
		 *	a kind of the core are refused (false, with a warning), and none of the list is entered then. The same list again is as good as the first time
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$kinds				A list of kinds
		 *	@param		array 		$opts					package (required): the key of the package, see Register::package(); it is the source of the entries
		 *
		 *	@return 	bool
		 */
		public static function addKinds( array &$appData, array $kinds, array $opts = [] ): bool {

			$bad = match( true ) {
				array_diff_key( $opts, [ 'package' => 1 ] ) !== [] || Register::package( $appData, $opts['package'] ?? null ) === null => 'package',
				$kinds === [] || array_is_list( $kinds ) === false || count( $kinds ) > 60 => 'kinds',
				default => '',
			};

			// The package is a text where it is one: nothing of $opts is turned into one before that is known
			$source = $bad === '' ? (string) $opts['package'] : '';

			foreach( $bad === '' ? $kinds : [] as $kind ) {

				if( is_string( $kind ) === false || strlen( $kind ) > 40 || preg_match( self::SLUG, $kind ) !== 1 || in_array( $kind, self::KINDS, true ) === true )
					$bad = 'kind';

				foreach( (array) ( $appData[self::REGISTER] ?? [] ) as $other => $entered )
					if( $bad === '' && (string) $other !== $source && in_array( $kind, $entered, true ) === true )
						$bad = 'kind taken';
			}

			if( $bad !== '' ) {
				trigger_error( 'Crm: the kinds of the logbook of a package were refused ('. $bad. ')', E_USER_WARNING );

				return false;
			}

			$appData[self::REGISTER][$source] = array_values( array_unique( array_merge( (array) ( $appData[self::REGISTER][$source] ?? [] ), $kinds ) ) );

			return true;
		}

		/**
		 *	Whether a source has entered a kind
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$source
		 *	@param		string		$kind
		 *
		 *	@return 	bool
		 */
		public static function kindOf( array &$appData, string $source, string $kind ): bool {

			return $source === self::SOURCE ? in_array( $kind, self::KINDS, true ) : in_array( $kind, (array) ( $appData[self::REGISTER][$source] ?? [] ), true );
		}

		/**
		 *	Write an entry: the opening for packages
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$entry				source (the key of the package), kind (a slug), text (the finished sentence, one line; none for a kind the source entered), client_id and/or project (the id of the project), data (a flat list of small values, optional), ref, by (an address, visitor or system), at (a time of the module, Y-m-d\TH:i:s\Z; now where none is given)
		 *
		 *	@return 	array|string							The entry as of() has it; or why not, see the class
		 */
		public static function add( array &$appData, array $entry ): array|string {

			if( ( $entry['source'] ?? null ) === self::SOURCE )
				return 'source';

			try {
				return self::write( $appData, $entry, false, false );
			}
			catch( \Throwable $e ) {
				trigger_error( 'Crm: the logbook entry of '. ( is_string( $entry['source'] ?? null ) === true ? $entry['source'] : '' ). ' could not be written', E_USER_WARNING );

				return 'write';
			}
		}

		/**
		 *	Write an entry of the core. A refusal is a warning in the log and no failure of the step that asked
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$kind					One of KINDS
		 *	@param		array 		$entry				client_id and/or project, data, ref, by, at - and text for a line the owner wrote
		 *
		 *	@return 	void
		 */
		public static function crm( array &$appData, string $kind, array $entry ): void {

			// A value that goes into a sentence is made fit: a mark that is none for a line is taken out of it, so that a title cannot make the step that wrote it fail
			foreach( is_array( $entry['data'] ?? null ) === true ? $entry['data'] : [] as $name => $value )
				if( is_string( $value ) === true )
					$entry['data'][$name] = self::scrub( $value );

			// Where the step has a transaction that the database ends (a trigger that rolls back), the failure goes on to the step, which was undone with it
			$done = self::write( $appData, [ 'source' => self::SOURCE, 'kind' => $kind ] + $entry + [ 'by' => self::who( $appData ) ], true, true );

			if( is_string( $done ) === true && $done !== 'write' )
				trigger_error( 'Crm: the logbook entry '. $kind. ' was not written ('. $done. ')', E_USER_WARNING );
		}

		/**
		 *	The line the owner writes into the logbook of a client or a project
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$scope				client (the id) and/or project (the id)
		 *	@param		string		$text
		 *	@param		string		$by						The address of the account
		 *
		 *	@return 	array|string							The entry; or why not, see the class
		 */
		public static function note( array &$appData, array $scope, string $text, string $by ): array|string {

			try {
				return self::write( $appData, [ 'source' => self::SOURCE, 'kind' => self::NOTE, 'text' => $text, 'client_id' => $scope['client'] ?? null, 'project' => $scope['project'] ?? '', 'by' => $by ], false, false );
			}
			catch( \Throwable $e ) {
				trigger_error( 'Crm: the line of the owner could not be written', E_USER_WARNING );

				return 'write';
			}
		}

		/**
		 *	Who is acting: the address of the account of the workbench that is signed in, else system
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function who( array &$appData ): string {

			$user = \Nino\Auth::getCurrentUser( $appData );

			return is_array( $user ) === true && is_string( $user['mail'] ?? null ) === true && $user['mail'] !== '' ? $user['mail'] : 'system';
		}

		/**
		 *	A value made fit for a sentence: valid UTF-8, no control character, no mark that shows nothing or turns the text, one line, cut to VALUE characters
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		public static function scrub( string $value ): string {

			$value = (string) preg_replace( [ self::CONTROL, self::INVISIBLE ], '', mb_scrub( $value, 'UTF-8' ) );

			return mb_substr( trim( $value ), 0, self::VALUE, 'UTF-8' );
		}

		/**
		 *	The text of an entry as a sentence: one line of valid UTF-8 with no control character, trimmed, of at most TEXT characters
		 *
		 *	@param		mixed			$text
		 *
		 *	@return 	array|string							[ text ] - which may be empty; or format (no string, no UTF-8, a control character) or long
		 */
		public static function clean( mixed $text ): array|string {

			if( is_string( $text ) === false || preg_match( '//u', $text ) !== 1 || preg_match( self::CONTROL, $text ) !== 0 || preg_match( self::INVISIBLE, $text ) !== 0 )
				return 'format';

			$text = trim( $text );

			// A text of nothing but blanks that show nothing (a no-break space) is none
			if( $text !== '' && preg_replace( '/[\s\p{Z}\p{Cf}]+/u', '', $text ) === '' )
				return 'format';

			return mb_strlen( $text, 'UTF-8' ) > self::TEXT ? 'long' : [ $text ];
		}

		/**
		 *	The entries of a client or a project, the newest first
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$scope				client (the id) and/or project (the id): an entry has to be about all that is given; the page of a client alone holds, beside its own entries, those of OF_SHARE about the projects that belong to it
		 *	@param		int|null	$before				The id of the last entry of the page before: the page that follows it. Null for the first page
		 *	@param		int				$limit				At most MOST
		 *
		 *	@return 	array|string							rows (id, client_id, project, at, source, kind, text, data, ref, by) and more (whether older entries are there); or scope (neither a client nor a project), before (no entry of that id)
		 */
		public static function of( array &$appData, array $scope, ?int $before = null, int $limit = self::PAGE ): array|string {

			$where = [];
			$params = [];

			if( array_key_exists( 'client', $scope ) === true ) {

				if( is_int( $scope['client'] ) === false || $scope['client'] < 1 )
					return 'scope';

				$where[] = 'client_id = :client';
				$params['client'] = $scope['client'];

				// The page of a client also shows the releases of its projects to other clients: they are about its projects, and the entry is the one the project's page shows - no second one is written
				if( array_key_exists( 'project', $scope ) === false )
					$where[0] = self::ownReleases( $appData, $scope['client'], $params );
			}

			if( array_key_exists( 'project', $scope ) === true ) {

				if( is_string( $scope['project'] ) === false || preg_match( Projects::ID, $scope['project'] ) !== 1 )
					return 'scope';

				$where[] = 'project = :project';
				$params['project'] = $scope['project'];
			}

			if( $where === [] )
				return 'scope';

			if( $before !== null ) {

				$cut = Db::row( $appData, 'SELECT at, id FROM logbook WHERE id = :id', [ 'id' => $before ] );

				if( $cut === null )
					return 'before';

				$where[] = '( at < :at OR ( at = :at AND id < :id ) )';
				$params += [ 'at' => $cut['at'], 'id' => $cut['id'] ];
			}

			$limit = max( 1, min( self::MOST, $limit ) );
			$rows = Db::rows( $appData, 'SELECT * FROM logbook WHERE '. implode( ' AND ', $where ). ' ORDER BY at DESC, id DESC LIMIT '. ( $limit + 1 ), $params );

			return [ 'rows' => array_map( [ self::class, 'shape' ], array_slice( $rows, 0, $limit ) ), 'more' => count( $rows ) > $limit ];
		}

		/**
		 *	The condition of the page of a client: its own entries, and the entries of OF_SHARE about the projects that belong to it
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$client
		 *	@param		array 		&$params			Gets the names of the projects
		 *
		 *	@return 	string
		 */
		private static function ownReleases( array &$appData, int $client, array &$params ): string {

			$row = Db::row( $appData, 'SELECT number FROM clients WHERE id = :id', [ 'id' => $client ] );
			$ids = $row === null || Projects::missing( $appData ) === true ? [] : array_column( Projects::list( $appData, (string) $row['number'] ), 'id' );

			if( $ids === [] )
				return 'client_id = :client';

			$names = [];

			foreach( $ids as $index => $id ) {
				$names[] = ':own'. $index;
				$params['own'. $index] = (string) $id;
			}

			$params['ownsource'] = self::SOURCE;

			return '( client_id = :client OR ( source = :ownsource AND kind IN ( '. self::shareKinds(). ' ) AND project IN ( '. implode( ', ', $names ). ' ) ) )';
		}

		/**
		 *	The entries of a source and a kind that are about a reference or a project, the newest first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$source				The key of the package, or crm
		 *	@param		string		$kind					A kind the source has entered, or a kind of the core (KINDS) that the core wrote in the name of the source: Projects::add() writes project-added under the key of the package that made the project, and a package finds the projects it made by it
		 *	@param		array 		$where				ref (a text or a whole number: as text) and/or project (the id, as text); at least one, no other key
		 *	@param		int				$limit				1 to MOST
		 *
		 *	@return 	array											Rows of id, client_id (null for none), project, ref, data (a list; none where the entry has none), at; none for a source or a kind that was not entered (and is no kind of the core), and for a condition that is none
		 */
		public static function find( array &$appData, string $source, string $kind, array $where, int $limit = 50 ): array {

			// A kind of the core is the source's own to read where the core wrote it for the source (only its own rows are asked for: the source is in the statement); no other kind is found that the source did not enter
			if( Register::word( $source ) === false || ( self::kindOf( $appData, $source, $kind ) === false && in_array( $kind, self::KINDS, true ) === false ) || $where === [] || array_diff( array_map( 'strval', array_keys( $where ) ), [ 'ref', 'project' ] ) !== [] )
				return [];

			$sql = 'SELECT id, client_id, project, ref, data, at FROM logbook WHERE source = :source AND kind = :kind';
			$params = [ 'source' => $source, 'kind' => $kind ];

			foreach( [ 'ref', 'project' ] as $field ) {

				if( array_key_exists( $field, $where ) === false )
					continue;

				if( ( is_string( $where[$field] ) === false && is_int( $where[$field] ) === false ) || (string) $where[$field] === '' )
					return [];

				$sql .= ' AND '. $field. ' = :'. $field;
				$params[$field] = (string) $where[$field];
			}

			return array_map( static function( array $row ): array {

				$data = json_decode( (string) $row['data'], true );

				return [ 'id' => (int) $row['id'], 'client_id' => $row['client_id'] === null ? null : (int) $row['client_id'], 'project' => (string) $row['project'], 'ref' => (string) $row['ref'], 'data' => is_array( $data ) === true ? $data : [], 'at' => (string) $row['at'] ];
			}, Db::rows( $appData, $sql. ' ORDER BY at DESC, id DESC LIMIT '. max( 1, min( self::MOST, $limit ) ), $params ) );
		}

		/**
		 *	The client of a project: the id of the client whose number the element names
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *
		 *	@return 	int|null									Null for a project that is not there or names no client
		 */
		public static function clientOf( array &$appData, string $project ): ?int {

			$found = preg_match( Projects::ID, $project ) === 1 ? Projects::get( $appData, $project ) : null;
			$row = $found === null || $found['client'] === '' ? null : Db::row( $appData, 'SELECT id FROM clients WHERE number = :number', [ 'number' => $found['client'] ] );

			return $row === null ? null : (int) $row['id'];
		}

		/**
		 *	Take the entries of a client away with it
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$client
		 *
		 *	@return 	int												How many went
		 */
		public static function dropClient( array &$appData, int $client ): int {

			return Db::exec( $appData, 'DELETE FROM logbook WHERE client_id = :client', [ 'client' => $client ] );
		}

		/**
		 *	Take the entries of a project away with it: the ones that name no client, and the ones about its releases to other clients (OF_SHARE). Any other entry that names the client stays with the client
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *
		 *	@return 	int												How many went
		 */
		public static function dropProject( array &$appData, string $project ): int {

			return Db::exec( $appData, 'DELETE FROM logbook WHERE project = :project AND ( client_id IS NULL OR ( source = :source AND kind IN ( '. self::shareKinds(). ' ) ) )', [ 'project' => $project, 'source' => self::SOURCE ] );
		}

		/**
		 *	The kinds of OF_SHARE as the list of a statement: quoted, divided by commas
		 *
		 *	@return 	string
		 */
		public static function shareKinds(): string {

			return implode( ', ', array_map( static fn( string $kind ): string => '\''. $kind. '\'', self::OF_SHARE ) );
		}

		/**
		 *	The write: the checks, the client of the project, the row
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$entry				See add()
		 *	@param		bool			$own					The core writes: a kind it knows needs no sentence, and a project may be one that is gone
		 *	@param		bool			$join					The step that writes has a transaction of its own: if the database ends it with the failure, the failure goes on
		 *
		 *	@return 	array|string
		 */
		private static function write( array &$appData, array $entry, bool $own, bool $join ): array|string {

			$source = $entry['source'] ?? null;
			$kind = $entry['kind'] ?? null;
			$client = $entry['client_id'] ?? null;
			$project = $entry['project'] ?? '';
			$ref = $entry['ref'] ?? '';
			$by = $entry['by'] ?? '';
			$at = $entry['at'] ?? gmdate( self::TIME );
			$data = $entry['data'] ?? [];
			$clean = array_key_exists( 'text', $entry ) === true ? self::clean( $entry['text'] ) : [ '' ];
			$text = is_array( $clean ) === true ? $clean[0] : null;

			$refused = match( true ) {
				is_string( $source ) === false || strlen( $source ) > 40 || preg_match( self::SLUG, $source ) !== 1 => 'source',
				is_string( $kind ) === false || strlen( $kind ) > 40 || preg_match( self::SLUG, $kind ) !== 1       => 'kind',
				$client !== null && ( is_int( $client ) === false || $client < 1 )                                  => 'client',
				is_string( $project ) === false || ( $project !== '' && preg_match( Projects::ID, $project ) !== 1 ) => 'project',
				$client === null && $project === ''                                                                  => 'target',
				$text === null || ( $text === '' && self::kindOf( $appData, $source, $kind ) === false )              => 'text',
				self::plain( $data ) === false                                                                       => 'data',
				is_string( $ref ) === false || self::small( $ref, self::REF ) === false                             => 'ref',
				is_string( $by ) === false || self::small( $by, self::VALUE ) === false                             => 'by',
				self::moment( $at, $own === false ) === false                                                        => 'at',
				default                                                                                              => '',
			};

			$json = $refused === '' ? json_encode( $data === [] ? new \stdClass() : $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : false;

			if( $refused === '' && ( is_string( $json ) === false || strlen( $json ) > self::DATA ) )
				$refused = 'data';

			if( $refused !== '' )
				return $refused;

			try {
				if( $client !== null && Db::row( $appData, 'SELECT id FROM clients WHERE id = :id', [ 'id' => $client ] ) === null )
					return 'client';

				// A project a package names is there, and the client it names is the project's own
				if( $own === false && $project !== '' ) {

					if( Projects::get( $appData, (string) $project ) === null )
						return 'project';

					$owner = self::clientOf( $appData, (string) $project );

					if( $client !== null && $owner !== null && $owner !== $client )
						return 'project';
				}

				$client ??= self::clientOf( $appData, (string) $project );

				if( $client === null && $project === '' )
					return 'target';

				$pdo = Db::pdo( $appData );
			}
			catch( \Throwable $e ) {
				trigger_error( 'Crm: the logbook entry '. (string) $kind. ' of '. (string) $source. ' could not be written', E_USER_WARNING );

				return 'write';
			}

			// The row is written in a savepoint of the step's transaction, or in a transaction of its own where there is none (a BEGIN that is refused says there is one: the connection does not tell)
			$mine = false;

			try {
				$pdo->exec( 'BEGIN IMMEDIATE' );
				$mine = true;
			}
			catch( \Throwable $e ) {
				// The step holds a transaction already
			}

			try {
				if( $mine === false )
					$pdo->exec( 'SAVEPOINT logbook' );

				$pdo->prepare( 'INSERT INTO logbook ( client_id, project, at, source, kind, text, data, ref, "by" ) VALUES ( :client, :project, :at, :source, :kind, :text, :data, :ref, :by )' )
					->execute( [ 'client' => $client, 'project' => $project, 'at' => $at, 'source' => $source, 'kind' => $kind, 'text' => $text, 'data' => $json, 'ref' => $ref, 'by' => $by ] );
				$id = (int) $pdo->lastInsertId();
				$pdo->exec( $mine === true ? 'COMMIT' : 'RELEASE logbook' );

				return self::shape( [ 'id' => $id, 'client_id' => $client, 'project' => $project, 'at' => $at, 'source' => $source, 'kind' => $kind, 'text' => $text, 'data' => $json, 'ref' => $ref, 'by' => $by ] );
			}
			catch( \Throwable $e ) {

				// What was written is taken back to the savepoint, and the step goes on; where the transaction itself is gone (the database ended it) there is nothing to go back to, and the step is told
				try {
					$pdo->exec( $mine === true ? 'ROLLBACK' : 'ROLLBACK TO logbook' );

					if( $mine === false )
						$pdo->exec( 'RELEASE logbook' );
				}
				catch( \Throwable $gone ) {

					if( $join === true && $mine === false )
						throw $e;
				}

				trigger_error( 'Crm: the logbook entry '. (string) $kind. ' of '. (string) $source. ' could not be written', E_USER_WARNING );

				return 'write';
			}
		}

		/**
		 *	Whether a string is a moment of the module that there is: Y-m-d\TH:i:s\Z, a day that exists, not ahead of the clock by more than a few minutes
		 *
		 *	@param		mixed			$at
		 *	@param		bool			$checkAhead		False for the core's own entries, whose time is the clock of the step that wrote them
		 *
		 *	@return 	bool
		 */
		private static function moment( mixed $at, bool $checkAhead = true ): bool {

			if( is_string( $at ) === false || preg_match( self::STAMP, $at ) !== 1 )
				return false;

			$made = \DateTimeImmutable::createFromFormat( '!'. self::TIME, $at, new \DateTimeZone( 'UTC' ) );

			return $made !== false && $made->format( self::TIME ) === $at && ( $checkAhead === false || $made->getTimestamp() <= time() + self::AHEAD );
		}

		/**
		 *	Whether the values of an entry are a flat list of small ones: a name of letters, digits and the underscore, and a string of at most VALUE characters, a number, a boolean or null
		 *
		 *	@param		mixed			$data
		 *
		 *	@return 	bool
		 */
		private static function plain( mixed $data ): bool {

			if( is_array( $data ) === false || count( $data ) > 20 )
				return false;

			foreach( $data as $name => $value ) {

				if( is_string( $name ) === false || preg_match( '/^[a-z][a-z0-9_]{0,31}$/D', $name ) !== 1 )
					return false;

				if( is_string( $value ) === true ? ( preg_match( '//u', $value ) !== 1 || mb_strlen( $value, 'UTF-8' ) > self::VALUE ) : is_scalar( $value ) === false && $value !== null )
					return false;

				if( is_float( $value ) === true && is_finite( $value ) === false )
					return false;
			}

			return true;
		}

		/**
		 *	Whether a string is one line of valid UTF-8 without a control character and at most $most characters
		 *
		 *	@param		string		$value
		 *	@param		int				$most
		 *
		 *	@return 	bool
		 */
		private static function small( string $value, int $most ): bool {

			return preg_match( '//u', $value ) === 1 && preg_match( self::CONTROL, $value ) === 0 && mb_strlen( $value, 'UTF-8' ) <= $most;
		}

		/**
		 *	@param		array 		$row					As the table holds it
		 *
		 *	@return 	array											id, client_id (null for none), project ('' for none), at, source, kind, text, data (an array), ref, by
		 */
		private static function shape( array $row ): array {

			$data = json_decode( (string) $row['data'], true );

			return [
				'id'        => (int) $row['id'],
				'client_id' => $row['client_id'] === null ? null : (int) $row['client_id'],
				'project'   => (string) $row['project'],
				'at'        => (string) $row['at'],
				'source'    => (string) $row['source'],
				'kind'      => (string) $row['kind'],
				'text'      => (string) $row['text'],
				'data'      => is_array( $data ) === true ? $data : [],
				'ref'       => (string) $row['ref'],
				'by'        => (string) $row['by'],
			];
		}
	}
}
