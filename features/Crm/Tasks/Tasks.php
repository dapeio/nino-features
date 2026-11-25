<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Tasks		The tasks of a project: a plain list the owner keeps for each project, open ones first and the done ones after them. A task is a row of the table tasks that names its project by the id of the element, as
	 *							project_states does, and goes with it (removeProject(), told by Projects::callbackCommitted(); the wipe empties the table). It has a text of at most TEXT characters - line breaks stay -, whether it is done and
	 *							when, and a place among the open ones of its project: a task that is made, and one that is opened again, stands at the top; move() swaps a task with the open neighbour above or below it, and a done one
	 *							has no place to move. The owner makes and changes tasks in the surface (see Tasks\Api); nothing of them is shown to a client.
	 *
	 *							A package makes a task of its own with addFrom(): a comment a customer wrote, say. The task names its source (the key of the package) and the reference of the source (the id of the comment), and
	 *							one reference is a task once, so that a package that is told twice makes it once.
	 *
	 *	@package						Dape/Nino
	 */
	class Tasks {

		// How many characters a text has at most, and how many tasks a project holds
		public const int TEXT = 4000;
		public const int PER_PROJECT = 1000;

		// The marks a text is free of: a control character (a line break and a tab excepted), a separator of lines, and a sign that turns the direction of the text around
		private const string CONTROL = '/[\x{0}-\x{8}\x{B}\x{C}\x{E}-\x{1F}\x{7F}-\x{9F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

		/**
		 *	The text of a task as the owner types it: line breaks are \n, a tab is a space, the ends are trimmed
		 *
		 *	@param		mixed			$text
		 *
		 *	@return 	array|string							[ text ] where it is a text a task can have; else format (no string, a control character, no UTF-8), empty or long
		 */
		public static function clean( mixed $text ): array|string {

			if( is_string( $text ) === false || preg_match( '//u', $text ) !== 1 )
				return 'format';

			$text = str_replace( [ "\r\n", "\r", "\t" ], [ "\n", "\n", ' ' ], $text );

			// Looked for before the ends are trimmed: trim() takes a null byte at an end for a blank
			if( preg_match( self::CONTROL, $text ) !== 0 )
				return 'format';

			$text = trim( $text );

			if( $text === '' )
				return 'empty';

			return mb_strlen( $text, 'UTF-8' ) > self::TEXT ? 'long' : [ $text ];
		}

		/**
		 *	A text of somebody else made fit: what clean() refuses is taken out (a control character, a mark that turns the text around) and a byte that is no UTF-8 is made a ?; the text is cut at TEXT characters
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string										May be empty
		 */
		public static function scrub( string $text ): string {

			$text = (string) preg_replace( self::CONTROL, '', trim( str_replace( [ "\r\n", "\r", "\t" ], [ "\n", "\n", ' ' ], mb_scrub( $text, 'UTF-8' ) ) ) );

			return trim( mb_substr( $text, 0, self::TEXT, 'UTF-8' ) );
		}

		/**
		 *	The tasks of a project: the open ones from the top, then the done ones, the latest done first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project
		 *
		 *	@return 	array											id, text, done, done_at, created_at, source and ref (the reference of the package that made it; '' for a task made by hand) of each - nothing of who made it
		 */
		public static function list( array &$appData, string $project ): array {

			$rows = Db::rows( $appData, 'SELECT id, text, done, done_at, source, ref, created_at FROM tasks WHERE project = :project ORDER BY done, CASE WHEN done = 0 THEN -position END, done_at DESC, id DESC', [ 'project' => $project ] );

			return array_map( static fn( array $row ): array => [
				'id'         => (int) $row['id'],
				'text'       => (string) $row['text'],
				'done'       => (int) $row['done'] === 1,
				'done_at'    => (string) $row['done_at'],
				'created_at' => (string) $row['created_at'],
				'source'     => (string) $row['source'],
				'ref'        => (string) $row['ref'],
			], $rows );
		}

		/**
		 *	How many tasks of a project are open
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *
		 *	@return 	int
		 */
		public static function openCount( array &$appData, string $project ): int {

			return (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM tasks WHERE project = :project AND done = 0', [ 'project' => $project ] )['n'] ?? 0 );
		}

		/**
		 *	The projects as the app lists them: those with open tasks, and - apart - those that are not finished and have none. Both by client and number, so that the order stays the same from one call to the next
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											projects and others: id, label (P-0001), title, client_title and open (how many open tasks) of each
		 */
		public static function openProjects( array &$appData ): array {

			if( Projects::missing( $appData ) === true )
				return [ 'projects' => [], 'others' => [] ];

			$open = [];

			foreach( Db::rows( $appData, 'SELECT project, COUNT(*) AS n FROM tasks WHERE done = 0 GROUP BY project' ) as $row )
				$open[(string) $row['project']] = (int) $row['n'];

			$clients = [];

			foreach( Clients::list( $appData ) as $client )
				$clients[(string) $client['number']] = (string) $client['title'];

			$with = [];
			$without = [];

			foreach( Projects::list( $appData ) as $project ) {

				$count = $open[$project['id']] ?? 0;

				if( $count === 0 && $project['done'] === true )
					continue;

				$entry = [ 'id' => $project['id'], 'label' => Projects::label( $project['id'] ), 'title' => $project['title'], 'client_title' => $clients[$project['client']] ?? '', 'open' => $count ];

				if( $count > 0 )
					$with[] = $entry;
				else
					$without[] = $entry;
			}

			$order = static function( array $a, array $b ): int {
				return [ mb_strtolower( $a['client_title'], 'UTF-8' ), strlen( $a['id'] ), $a['id'] ] <=> [ mb_strtolower( $b['client_title'], 'UTF-8' ), strlen( $b['id'] ), $b['id'] ];
			};

			usort( $with, $order );
			usort( $without, $order );

			return [ 'projects' => $with, 'others' => $without ];
		}

		/**
		 *	The project a task belongs to
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	string|null								null for no such task
		 */
		public static function projectOf( array &$appData, int $id ): ?string {

			$row = Db::row( $appData, 'SELECT project FROM tasks WHERE id = :id', [ 'id' => $id ] );

			return $row === null ? null : (string) $row['project'];
		}

		/**
		 *	Make a task at the top of the open ones of a project
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project, which is there
		 *	@param		string		$text					As clean() returns it
		 *	@param		string		$by						The account of the surface
		 *
		 *	@return 	string										'' where it is made; full where the project holds PER_PROJECT tasks
		 */
		public static function add( array &$appData, string $project, string $text, string $by ): string {

			return self::atomic( $appData, static function() use ( &$appData, $project, $text, $by ): string {

				if( (int) Db::row( $appData, 'SELECT COUNT(*) AS n FROM tasks WHERE project = :project', [ 'project' => $project ] )['n'] >= self::PER_PROJECT )
					return 'full';

				self::insert( $appData, $project, $text, 'manual', '', $by );

				return '';
			} );
		}

		/**
		 *	Change the text of a task
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		string		$text					As clean() returns it
		 *
		 *	@return 	bool											False for no such task
		 */
		public static function setText( array &$appData, int $id, string $text ): bool {

			return Db::exec( $appData, 'UPDATE tasks SET text = :text, updated_at = :at WHERE id = :id', [ 'text' => $text, 'at' => self::now(), 'id' => $id ] ) === 1;
		}

		/**
		 *	Mark a task as done, or open it again: it is then the top of the open ones. What is as it is stays as it is
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		bool			$done
		 *
		 *	@return 	bool											False for no such task
		 */
		public static function setDone( array &$appData, int $id, bool $done ): bool {

			return self::atomic( $appData, static function() use ( &$appData, $id, $done ): bool {

				$row = Db::row( $appData, 'SELECT project, done FROM tasks WHERE id = :id', [ 'id' => $id ] );

				if( $row === null )
					return false;

				if( ( (int) $row['done'] === 1 ) === $done )
					return true;

				$now = self::now();

				if( $done === true )
					Db::exec( $appData, 'UPDATE tasks SET done = 1, done_at = :now, updated_at = :now WHERE id = :id', [ 'now' => $now, 'id' => $id ] );
				else
					Db::exec( $appData, 'UPDATE tasks SET done = 0, done_at = \'\', position = ( SELECT COALESCE( MAX( position ), 0 ) + 1 FROM tasks WHERE project = :project ), updated_at = :now WHERE id = :id', [ 'project' => $row['project'], 'now' => $now, 'id' => $id ] );

				return true;
			} );
		}

		/**
		 *	Swap an open task with the open one above it or below it
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		string		$direction		up or down
		 *
		 *	@return 	string										'' where it moved; unknown for no such task; edge for a task that has no neighbour that way, and for one that is done
		 */
		public static function move( array &$appData, int $id, string $direction ): string {

			return self::atomic( $appData, static function() use ( &$appData, $id, $direction ): string {

				$row = Db::row( $appData, 'SELECT project, position, done FROM tasks WHERE id = :id', [ 'id' => $id ] );

				if( $row === null )
					return 'unknown';

				if( (int) $row['done'] === 1 )
					return 'edge';

				$next = Db::row( $appData, $direction === 'up'
					? 'SELECT id, position FROM tasks WHERE project = :project AND done = 0 AND position > :position ORDER BY position ASC LIMIT 1'
					: 'SELECT id, position FROM tasks WHERE project = :project AND done = 0 AND position < :position ORDER BY position DESC LIMIT 1', [ 'project' => $row['project'], 'position' => $row['position'] ] );

				if( $next === null )
					return 'edge';

				$now = self::now();

				Db::exec( $appData, 'UPDATE tasks SET position = :position, updated_at = :now WHERE id = :id', [ 'position' => $next['position'], 'now' => $now, 'id' => $id ] );
				Db::exec( $appData, 'UPDATE tasks SET position = :position, updated_at = :now WHERE id = :id', [ 'position' => $row['position'], 'now' => $now, 'id' => $next['id'] ] );

				return '';
			} );
		}

		/**
		 *	Delete a task
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	bool											False for no such task
		 */
		public static function remove( array &$appData, int $id ): bool {

			return Db::exec( $appData, 'DELETE FROM tasks WHERE id = :id', [ 'id' => $id ] ) === 1;
		}

		/**
		 *	The tasks of a project that is gone go with it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *
		 *	@return 	void
		 */
		public static function removeProject( array &$appData, string $project ): void {

			Db::exec( $appData, 'DELETE FROM tasks WHERE project = :project', [ 'project' => $project ] );
		}

		/**
		 *	A task a package makes: at the top of the open ones of a project, with the key of the package as its source and the reference of the source. The reference makes it once: a second call with the same
		 *	source and reference makes nothing. The inputs are checked here, whoever calls
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of a project that is there
		 *	@param		string		$text					One line or more of text, as clean() takes it
		 *	@param		string		$source				The key of the package, a slug; manual and crm are none of a package's
		 *	@param		string		$ref					The reference of the source: letters, digits, dot, colon, underscore and hyphen, 1 to 100
		 *	@param		string		$by						Who made it: an address, visitor or system; '' for none
		 *
		 *	@return 	string										'' where it is made; why not: project (no such project), text, source, ref, by (not what they are asked to be), full (the project holds PER_PROJECT tasks) or taken (the reference made one before)
		 */
		public static function addFrom( array &$appData, string $project, string $text, string $source, string $ref, string $by ): string {

			$clean = self::clean( $text );

			$refused = match( true ) {
				preg_match( Projects::ID, $project ) !== 1 || Projects::get( $appData, $project ) === null => 'project',
				is_string( $clean ) === true => 'text',
				$source === 'manual' || $source === Logbook::SOURCE || strlen( $source ) > 40 || Register::word( $source ) === false => 'source',
				preg_match( '/^[A-Za-z0-9._:-]{1,100}$/D', $ref ) !== 1 => 'ref',
				Logbook::scrub( $by ) !== $by => 'by',
				default => '',
			};

			if( $refused !== '' )
				return $refused;

			return self::atomic( $appData, static function() use ( &$appData, $project, $clean, $source, $ref, $by ): string {

				if( (int) Db::row( $appData, 'SELECT COUNT(*) AS n FROM tasks WHERE project = :project', [ 'project' => $project ] )['n'] >= self::PER_PROJECT )
					return 'full';

				if( Db::row( $appData, 'SELECT id FROM tasks WHERE source = :source AND ref = :ref', [ 'source' => $source, 'ref' => $ref ] ) !== null )
					return 'taken';

				self::insert( $appData, $project, $clean[0], $source, $ref, $by );

				return '';
			} );
		}

		/**
		 *	One row at the top of the open tasks of a project; a reference that was taken already is not taken twice
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project
		 *	@param		string		$text
		 *	@param		string		$source				manual, or the key of a package
		 *	@param		string		$ref					The reference of the source, '' for none
		 *	@param		string		$by
		 *
		 *	@return 	void
		 */
		private static function insert( array &$appData, string $project, string $text, string $source, string $ref, string $by ): void {

			$now = self::now();

			Db::exec( $appData, 'INSERT INTO tasks ( project, text, position, source, ref, created_by, created_at, updated_at ) VALUES ( :project, :text, ( SELECT COALESCE( MAX( position ), 0 ) + 1 FROM tasks WHERE project = :project ), :source, :ref, :by, :now, :now ) ON CONFLICT DO NOTHING',
				[ 'project' => $project, 'text' => $text, 'source' => $source, 'ref' => $ref, 'by' => $by, 'now' => $now ] );
		}

		/**
		 *	Run steps in one transaction that holds the write lock from its start
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run
		 *
		 *	@return 	mixed											What the steps return
		 */
		private static function atomic( array &$appData, callable $run ): mixed {

			$pdo = Db::pdo( $appData );

			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$result = $run();
				$pdo->exec( 'COMMIT' );

				return $result;
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}
		}

		/**
		 *	@return 	string										The moment as the server writes it
		 */
		private static function now(): string {

			return gmdate( 'Y-m-d\TH:i:s\Z' );
		}
	}
}
