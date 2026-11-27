<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Erasure		What the package holds of a client that goes with its deletion and with the test phase: the rows of its table and the elements of the type of Events that the rows name. The step of the deletion
	 *							of a client (Erasure::add() of the core, key dates) counts the dates, takes the names before the rows go (gather), deletes the rows in the transaction of the database step (rows) and the
	 *							elements after the commit (finish), again and again until none is left; the step of the test phase does the same for every client - and for every date that has the group of a client and no row, since after it no client is left it could belong to -, reading what it needs before a client is deleted (gather of
	 *							Wipe::add()). An element goes with its client only where it is still the client's - its group is the group of the client -, and a date that was moved to another group by hand is no longer.
	 *
	 *							Nothing here takes "I cannot read them" for "there are none". Where a client has rows and the type of Events is missing, broken or off, a hold (the hook /crm/client/holds) keeps the client from
	 *							being deleted with a sentence that says why; the gather of the test phase throws, so that the run ends before a client is deleted. A step that is asked all the same - the journal of a deletion
	 *							that was begun before the type went - answers that it is not done, and the next press asks again. A step is done when the elements of its names are read once more and none of them is the
	 *							client's and there. Where the package is off or gone, its rows are there: the core keeps the deletion and the test phase from going on (crm_package_off), and says so.
	 *
	 *	@package						Dape/Nino
	 */
	class Erasure {

		/**	The key of the step of the deletion and of the test phase: the name of its count	*/
		public const string KEY = 'dates';

		/**
		 *	How many dates the client has: the elements its rows name that are its own where the dates can be read, the rows where they cannot - the hold says so
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client				id, number and email
		 *
		 *	@return 	int
		 */
		public static function count( array &$appData, array $client ): int {

			$names = array_column( Dates::rows( $appData, (int) $client['id'] ), 0 );

			if( Dates::state( $appData ) !== 'ok' )
				return count( $names );

			return count( array_filter( $names, static function( string $name ) use ( &$appData, $client ): bool { return self::there( $appData, $name, self::group( $client ) ); } ) );
		}

		/**
		 *	What the step needs to find the elements when the rows are gone: the group and the names, asked before the database step and kept in the journal
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$client				id, number and email
		 *
		 *	@return 	array											group and names
		 */
		public static function gather( array &$appData, array $client ): array {

			return [ 'group' => self::group( $client ), 'names' => array_column( Dates::rows( $appData, (int) $client['id'] ), 0 ) ];
		}

		/**
		 *	The rows of the client, inside the transaction of the database step: the client row goes after them
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run					A statement of the transaction
		 *	@param		array 		$client				id, number and email
		 *
		 *	@return 	int												0: the dates that went are counted by the step that deletes the elements
		 */
		public static function rows( array &$appData, callable $run, array $client ): int {

			$run( 'DELETE FROM '. Dates::TABLE. ' WHERE client_id = :id', [ 'id' => (int) $client['id'] ] );

			// The count of the answer is the dates that went - the elements, which finish() counts - and not the rows, of which one may name an element that is no longer the client's
			return 0;
		}

		/**
		 *	Delete the elements the names say, where they are still the client's. Done only where the type could be read and, read once more, none of them is the client's and there; a step that cannot read,
		 *	cannot delete or finds one left answers false
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$gathered			group and names
		 *	@param		array 		&$done				The counts so far: dates is raised to what was deleted now
		 *
		 *	@return 	bool
		 */
		public static function finish( array &$appData, array $gathered, array &$done ): bool {

			$group = is_string( $gathered['group'] ?? null ) === true ? $gathered['group'] : '';
			$names = $gathered['names'] ?? null;

			if( preg_match( \Nino\Modules\Crm\Clients::GROUP, $group ) !== 1 || is_array( $names ) === false || array_filter( $names, static fn( mixed $name ): bool => is_string( $name ) === false ) !== [] )
				return false;

			return self::remove( $appData, array_map( 'strval', $names ), $group, $done );
		}

		/**
		 *	The hook /crm/client/holds: what keeps a client from being deleted while its dates cannot be trusted
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$asked				id, number, emails and holds, to which an entry is added
		 *
		 *	@return 	void
		 */
		public static function callbackHolds( array &$appData, array &$asked ): void {

			if( is_array( $asked['holds'] ?? null ) === false || is_int( $asked['id'] ?? null ) === false )
				return;

			try {
				$rows = count( Dates::rows( $appData, $asked['id'] ) );
				$state = $rows === 0 ? 'ok' : Dates::state( $appData );
			}
			catch( \PDOException $e ) {
				$rows = 0;
				$state = 'broken';
			}

			if( $state === 'ok' )
				return;

			$asked['holds'][] = [
				'key'			=> 'crm-dates',
				'label'		=> \Nino\Modules\Crm\Surface\Texts::word( $appData, 'dates.hold-label' ),
				'count'		=> $rows,
				'blocks'	=> true,
				'why'			=> \Nino\Modules\Crm\Surface\Texts::word( $appData, 'dates.hold-'. $state ),
			];
		}

		/**
		 *	What the test phase has to find, read before a client or a row goes: the name of every date of a client and the group of that client, and every date that has the group of a client and no row that says
		 *	it is that client's (Dates::unassigned()) - after the test phase there is no client left it could belong to. Where there are rows and the dates cannot be read this throws, and the run ends before
		 *	anything is deleted; a group a Members page made up ("K 2024") is no group of a client and is not among them
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of [ name, group ]
		 *
		 *	@throws		\RuntimeException					The type of Events cannot be read while dates are held
		 */
		public static function gatherAll( array &$appData ): array {

			$rows = Dates::rows( $appData );
			$state = Dates::state( $appData );

			if( $state !== 'ok' && $rows !== [] )
				throw new \RuntimeException( 'the dates cannot be read' );

			if( $state !== 'ok' )
				return [];

			$clients = Dates::clients( $appData );
			$gathered = array_map( static fn( array $row ): array => [ $row[0], isset( $clients[$row[1]] ) === true ? Dates::groupOf( $clients[$row[1]] ) : '' ], $rows );

			// After the test phase no client is left to whom a date with the group of a client could belong, and the next client has the number of the first: every one of them goes, with a row or without - those of a group no client has, and those a row says are another's
			$nobody = Dates::unassigned( $appData );

			if( is_string( $nobody ) === true )
				throw new \RuntimeException( 'the dates cannot be read' );

			foreach( $nobody as [ $group, $names ] )
				foreach( $names as $name )
					$gathered[] = [ $name, $group ];

			return $gathered;
		}

		/**
		 *	The step of the test phase that has rows: the rows of every client, in front of the clients; the answer counts them
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run					A statement of the transaction
		 *
		 *	@return 	int												0: the dates that went are counted by the step that deletes the elements
		 */
		public static function rowsAll( array &$appData, callable $run ): int {

			$run( 'DELETE FROM '. Dates::TABLE );

			return 0;
		}

		/**
		 *	The test phase: delete the elements the gather read, where they are still their client's. Done only where the dates could be read and none of them is left
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$done				The counts so far: dates is raised to what was deleted now
		 *	@param		array 		$gathered			A list of [ name, group ]
		 *
		 *	@return 	bool
		 */
		public static function leftover( array &$appData, array &$done, array $gathered ): bool {

			$groups = [];

			foreach( $gathered as $pair ) {

				if( is_array( $pair ) === false || is_string( $pair[0] ?? null ) === false || is_string( $pair[1] ?? null ) === false )
					return false;

				$groups[$pair[1]][] = $pair[0];
			}

			$deleted = 0;

			foreach( $groups as $group => $names ) {

				if( preg_match( \Nino\Modules\Crm\Clients::GROUP, (string) $group ) !== 1 )
					continue;

				$counted = [];

				if( self::remove( $appData, $names, (string) $group, $counted ) === false )
					return false;

				$deleted += (int) ( $counted[self::KEY] ?? 0 );
			}

			$done[self::KEY] = max( (int) ( $done[self::KEY] ?? 0 ), $deleted );

			return true;
		}

		/**
		 *	Delete the elements of the names that are the group's, and read them once more
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$names
		 *	@param		string		$group
		 *	@param		array 		&$done
		 *
		 *	@return 	bool											True where the type could be read and no element of the names is of the group and there
		 */
		private static function remove( array &$appData, array $names, string $group, array &$done ): bool {

			if( $names === [] )
				return true;

			if( Dates::state( $appData ) !== 'ok' )
				return false;

			$deleted = 0;

			foreach( $names as $name ) {

				if( self::orphan( $appData, $name, $group ) === false )
					continue;

				if( \Nino\Elements::deleteElement( $appData, Dates::TYPE. '/'. $name, '*' ) !== true )
					return false;

				$deleted++;
			}

			$done[self::KEY] = max( (int) ( $done[self::KEY] ?? 0 ), $deleted );

			foreach( $names as $name )
				if( self::orphan( $appData, $name, $group ) === true )
					return false;

			return true;
		}

		/**
		 *	Whether an element of an old list is still to go: it is there with the group, and no client that is alive has it by a row that fits - a client that was made since, with the number of the
		 *	one that went, may have been given it, and what a client has is not what the list was about
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		$group
		 *
		 *	@return 	bool
		 */
		private static function orphan( array &$appData, string $name, string $group ): bool {

			// The group the element has in the file now, not the one this request read before: a date that is given to the client that goes is written while the deletion runs, and the deletion has to see it
			$found = Dates::groupStored( $appData, $name );

			if( $found === null || $group === '' || $found !== $group )
				return false;

			$row = \Nino\Modules\Crm\Db::row( $appData, 'SELECT client_id FROM '. Dates::TABLE. ' WHERE name = :name', [ 'name' => $name ] );
			$client = $row === null ? null : \Nino\Modules\Crm\Clients::get( $appData, (int) $row['client_id'] );

			return $client === null || Dates::groupOf( $client ) !== $group;
		}

		/**
		 *	Whether an element is there and carries the group
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		$group
		 *
		 *	@return 	bool
		 */
		private static function there( array &$appData, string $name, string $group ): bool {

			$element = Dates::element( $appData, $name );

			return $element !== null && $group !== '' && Dates::slug( $element['group'] ?? null ) === $group;
		}

		/**
		 *	The group of a client as the step files it: '' for a number that is none
		 *
		 *	@param		array 		$client
		 *
		 *	@return 	string
		 */
		private static function group( array $client ): string {

			return is_string( $client['number'] ?? null ) === true ? \Nino\Modules\Crm\Clients::group( $client['number'] ) : '';
		}
	}
}
