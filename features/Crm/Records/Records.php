<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Records		What a person agreed to or did on their own account, kept as evidence: the store of the core, which knows no kind of record. A package enters the kinds it writes with addKind() - an agreement to a
	 *								privacy text, an order, an approval - and writes records of them; the core keeps them, never changes one and deletes them in one place only: with the client they are about (Erasure, Wipe).
	 *								Why the core has no kind of its own: a record is made by a person who acts through a surface that a package owns, with an account (member_id), an address and a browser, and a kind without
	 *								its package could never be written; what the owner does is a line of the logbook, never a record.
	 *
	 *								A record holds the account that agreed (id, address and name as they were) or, for a kind that allows a proof without an account, the person as the package knew it, the client, the
	 *								thing it is about (thing, a word the package chose - document - and thing_id, its id as text; none for a kind that is about no thing), the project and the purpose where the kind has
	 *								them, the text the person was shown, the SHA-256 of that text, the time and the address and browser of the request. A kind may name a parent - a revocation names the consent it takes
	 *								back - which holds what its parent held and which no second record names: a table of the database refuses it. The table refuses the deletion of a client a record names.
	 *
	 *								add() answers the record as of() has it, or a string: 'kind' (not entered), 'origin' (no slug of a package, or crm), 'client' (no such client), 'member' (no account; for a proof without one:
	 *								an account, no address, a name that is no line of text or too long), 'ref' and 'at' (a proof without an account needs its reference and its time, not ahead of the clock), 'thing' (the kind is
	 *								about a thing and none is named, or one the client does not hold, or a kind that is about none and one is named), 'project', 'purpose', 'parent' (no record of the kind it takes back, of
	 *								another client, or one that is named already), 'logbook' (the source did not enter the kind of the entry) and 'shown' (nothing was shown). A database failure is a \PDOException the caller answers.
	 *
	 *	@package						Dape/Nino
	 */
	class Records {

		/**	The size of a portion forThings() asks the database for	*/
		public const int MOST = 500;

		/**	The most of the browser's name that is kept	*/
		public const int AGENT = 300;

		/**	The longest key of a package as an origin	*/
		public const int ORIGIN = 40;

		/**	The longest address and name of the person of a proof without an account, and how far ahead of the clock its time may be (seconds)	*/
		public const int MAIL = 254;
		public const int NAME = 200;
		private const int AHEAD = 300;

		private const string TIME = 'Y-m-d\TH:i:s\Z';
		private const string STAMP = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D';
		private const string REFERENCE = '/^[A-Za-z0-9._-]{1,80}$/D';
		private const string THING = '/^[A-Za-z0-9._:-]{1,80}$/D';

		// The kinds the packages entered, in the app data of the request
		private const string REGISTER = './crm/records/kinds';

		/**
		 *	Enter a kind of record
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					package (the key of the feature that enters it), kind (a slug), thing ('' for a kind about no thing, else a word: the kind of thing it is about), purposes (the purposes a record of the kind may have; none: it has none), logbook ('' or the kind
		 *																	of the entry of the logbook a record writes, which the writing source has entered with Logbook::addKinds()), parent ('' or the kind of the record a record of this kind takes back), foreign
		 *																	(whether a record may be a proof of a person that has no account), owner (none, or [ class, method ] ( array &$appData, string $id ): ?int that says which client holds the thing; a record
		 *																	of another client's thing is refused) and label (none, or [ class, method ] ( array &$appData, string $id ): string that says the thing in a few words for the entry of the logbook)
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - a kind with another description among them - and nothing is entered. The same description again, from the same package or another, is as good as the first time
		 */
		public static function addKind( array &$appData, array $spec ): bool {

			return Register::add( $appData, self::REGISTER, 'record kinds', [ $spec ], [
				'id' => 'kind',
				'idRule' => static fn( string $kind ): bool => Register::word( $kind ),
				'fields' => [ 'package' => Register::REQUIRED, 'thing' => '', 'purposes' => [], 'logbook' => '', 'parent' => '', 'foreign' => false, 'owner' => null, 'label' => null ],
				'rules' => [
					'package' => Register::packageRule( $appData ),
					'thing' => static fn( mixed $thing ): bool => $thing === '' || Register::word( $thing ),
					'purposes' => static fn( mixed $purposes ): bool => is_array( $purposes ) === true && array_is_list( $purposes ) === true && count( $purposes ) <= 20 && array_filter( $purposes, static fn( mixed $purpose ): bool => Register::word( $purpose ) === false ) === [],
					'logbook' => static fn( mixed $logbook ): bool => $logbook === '' || Register::word( $logbook ),
					'parent' => static fn( mixed $parent ): bool => $parent === '' || Register::word( $parent ),
					'foreign' => static fn( mixed $foreign ): bool => is_bool( $foreign ),
					'owner' => static function( mixed $owner ) use ( &$appData ): bool { return $owner === null || Register::callable( $appData, $owner, 2, [ 0 ] ); },
					'label' => static function( mixed $label ) use ( &$appData ): bool { return $label === null || Register::callable( $appData, $label, 2, [ 0 ] ); },
				],
				'ignore' => [ Register::PACKAGE ],
				'refuse' => static fn( array $spec ): string => $spec['parent'] === $spec['kind'] ? 'parent' : '',
			] );
		}

		/**
		 *	The kinds that were entered
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											kind => the description
		 */
		public static function kinds( array &$appData ): array {

			return Register::all( $appData, self::REGISTER );
		}

		/**
		 *	The hash of a shown text: what a record keeps beside the text, and what an agreement is compared by
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string										64 hexadecimal characters
		 */
		public static function hash( string $text ): string {

			return hash( 'sha256', $text );
		}

		/**
		 *	The circumstances of the request that is being answered, as a record keeps them
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$request			The request of the callback
		 *
		 *	@return 	array											ip and agent (the browser's name on one line, cut to AGENT characters)
		 */
		public static function source( array &$appData, array $request ): array {

			$header = $request['/nino/http/request']['header'] ?? [];
			$agent = is_array( $header ) === true && is_string( $header['User-Agent'] ?? null ) === true ? $header['User-Agent'] : '';

			return [
				'ip'    => \Nino\Http::getClientIp( $appData ),
				'agent' => mb_substr( trim( (string) preg_replace( '/[\s\p{C}]+/u', ' ', $agent ) ), 0, self::AGENT, 'UTF-8' ),
			];
		}

		/**
		 *	Keep a record, in a transaction of its own that takes the write lock first: what it reads - the record it takes back - and what it writes are one step, so that two clicks on one page keep one record
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$input				See insert()
		 *
		 *	@return 	array|string							The record; or why not, see the class
		 */
		public static function add( array &$appData, array $input ): array|string {

			$pdo = Db::pdo( $appData );
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$record = self::insert( $appData, $pdo, $input );
				$pdo->exec( is_array( $record ) === true ? 'COMMIT' : 'ROLLBACK' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			return $record;
		}

		/**
		 *	Keep a record on a connection that is in a transaction already (an order: the record and the state of the offer are one step)
		 *
		 *	@param		array 		&$appData
		 *	@param		\PDO			$pdo
		 *	@param		array 		$input				kind, client_id, origin (the key of the package that writes), member (id, email and name of the account), shown (the text), ref and at (a proof without an account: the package's reference to the
		 *																	thing the person agreed in, and the time of the agreement), thing and thing_id, project, purpose, parent (the id of the record this one takes back), source (the key of the package that writes the entry of the logbook; where the kind has one), ip, agent
		 *
		 *	@return 	array|string
		 */
		public static function insert( array &$appData, \PDO $pdo, array $input ): array|string {

			$spec = is_string( $input['kind'] ?? null ) === true ? ( self::kinds( $appData )[$input['kind']] ?? null ) : null;
			$kind = $spec === null ? '' : (string) $input['kind'];
			$origin = $input['origin'] ?? null;
			$member = is_array( $input['member'] ?? null ) ? $input['member'] : [];
			$shown = is_string( $input['shown'] ?? null ) ? $input['shown'] : '';
			$clientId = is_int( $input['client_id'] ?? null ) === true ? $input['client_id'] : 0;
			$foreign = array_key_exists( 'ref', $input ) === true;
			$reference = $foreign === true && is_string( $input['ref'] ) === true ? $input['ref'] : '';
			$at = $foreign === true && array_key_exists( 'at', $input ) === true ? $input['at'] : gmdate( self::TIME );
			$thing = $input['thing'] ?? '';
			$thingId = $input['thing_id'] ?? '';
			$project = $input['project'] ?? '';
			$purpose = $input['purpose'] ?? '';
			$parent = $input['parent'] ?? null;
			$purposes = $spec === null ? [] : $spec['purposes'];

			if( $spec === null )
				return 'kind';

			// A record that takes another back holds what it held: its purpose, its project and the text of its box
			if( $spec['parent'] !== '' ) {

				$taken = is_int( $parent ) === true ? self::revocable( $pdo, $parent, $clientId, (string) $spec['parent'] ) : null;

				if( $taken === null )
					return 'parent';

				[ $purpose, $project, $shown ] = [ (string) $taken['purpose'], (string) $taken['project'], (string) $taken['shown'] ];
				$purposes = array_merge( $purposes, [ $purpose ] );
			}

			$refused = match( true ) {
				is_string( $origin ) === false || strlen( $origin ) > self::ORIGIN || $origin === Logbook::SOURCE || Register::word( $origin ) === false => 'origin',
				$foreign === true && ( $spec['foreign'] === false || self::foreign( $member, $reference, $at ) !== '' ) => $spec['foreign'] === false ? 'member' : self::foreign( $member, $reference, $at ),
				$foreign === false && ( is_string( $member['id'] ?? null ) === false || $member['id'] === '' ) => 'member',
				$spec['parent'] === '' && $parent !== null => 'parent',
				$shown === '' => 'shown',
				is_string( $project ) === false || ( $project !== '' && preg_match( Projects::ID, $project ) !== 1 ) => 'project',
				is_string( $purpose ) === false || ( $purpose === '' ? $spec['purposes'] !== [] && $spec['parent'] === '' : in_array( $purpose, $purposes, true ) === false ) => 'purpose',
				is_string( $thing ) === false || is_string( $thingId ) === false || ( $spec['thing'] === '' ? $thing !== '' || $thingId !== '' : $thing !== $spec['thing'] || preg_match( self::THING, $thingId ) !== 1 ) => 'thing',
				default => '',
			};

			if( $refused !== '' )
				return $refused;

			$statement = $pdo->prepare( 'SELECT id FROM clients WHERE id = :id' );
			$statement->execute( [ 'id' => $clientId ] );

			if( $statement->fetchColumn() === false )
				return 'client';

			// The thing is the client's: a package that knows who holds it says so
			if( $spec['thing'] !== '' && $spec['owner'] !== null ) {

				$holder = call_user_func_array( $spec['owner'], [ &$appData, (string) $thingId ] );

				if( $holder !== $clientId )
					return 'thing';
			}

			$entry = null;

			if( $spec['logbook'] !== '' ) {

				$source = $input['source'] ?? null;

				if( is_string( $source ) === false || Logbook::kindOf( $appData, $source, (string) $spec['logbook'] ) === false || $source === Logbook::SOURCE )
					return 'logbook';

				$entry = [ 'source' => $source, 'kind' => (string) $spec['logbook'] ];
			}

			$values = [
				'kind'         => $kind,
				'origin'       => $origin,
				'client_id'    => $clientId,
				'member_id'    => $foreign === true ? $origin. ':'. $reference : $member['id'],
				'member_email' => (string) ( $member['email'] ?? '' ),
				'member_name'  => (string) ( $member['name'] ?? '' ),
				'thing'        => (string) $thing,
				'thing_id'     => (string) $thingId,
				'project'      => (string) $project,
				'purpose'      => $purpose,
				'parent'       => $spec['parent'] !== '' ? $parent : null,
				'sha256'       => self::hash( $shown ),
				'shown'        => $shown,
				'at'           => $at,
				'ip'           => (string) ( $input['ip'] ?? '' ),
				'agent'        => mb_substr( (string) ( $input['agent'] ?? '' ), 0, self::AGENT, 'UTF-8' ),
			];

			$pdo->prepare( 'INSERT INTO records ( '. implode( ', ', array_keys( $values ) ). ' ) VALUES ( :'. implode( ', :', array_keys( $values ) ). ' )' )->execute( $values );

			$row = [ 'id' => (int) $pdo->lastInsertId() ] + $values;

			// The logbook of the client has it as well, on the same connection: the entry stands and falls with the record
			if( $entry !== null ) {

				$label = $spec['label'] !== null && $values['thing_id'] !== '' ? (string) call_user_func_array( $spec['label'], [ &$appData, $values['thing_id'] ] ) : '';

				$written = Logbook::add( $appData, $entry + [
					'client_id' => $clientId, 'project' => $values['project'], 'ref' => (string) $row['id'], 'at' => $values['at'],
					'by' => $values['member_email'] !== '' ? mb_substr( $values['member_email'], 0, Logbook::VALUE, 'UTF-8' ) : 'visitor',
					'data' => [ 'thing' => $values['thing_id'], 'label' => mb_substr( Logbook::scrub( $label ), 0, Logbook::VALUE, 'UTF-8' ), 'purpose' => (string) $purpose ],
				] );

				if( is_string( $written ) === true )
					trigger_error( 'Crm: the logbook entry of a record was not written ('. $written. ')', E_USER_WARNING );
			}

			return self::shape( $row );
		}

		/**
		 *	Whether a record without an account is complete: it has no account, the person has an address (at most MAIL characters) and a name that is one line of text (at most NAME characters, no control
		 *	character and no mark that turns the text, as the logbook takes a sentence), the package gave a reference, and the time is a time of the module that there is and not ahead of the clock by more than a few minutes
		 *
		 *	@param		array 		$member				email and name of the person
		 *	@param		string		$reference
		 *	@param		mixed			$at
		 *
		 *	@return 	string										'' for a complete one, else member, ref or at
		 */
		private static function foreign( array $member, string $reference, mixed $at ): string {

			$made = is_string( $at ) === true && preg_match( self::STAMP, $at ) === 1 ? \DateTimeImmutable::createFromFormat( '!'. self::TIME, $at, new \DateTimeZone( 'UTC' ) ) : false;
			$name = $member['name'] ?? '';
			$mail = $member['email'] ?? null;

			return match( true ) {
				( $member['id'] ?? '' ) !== '' || is_string( $mail ) === false || strlen( $mail ) > self::MAIL || filter_var( $mail, FILTER_VALIDATE_EMAIL ) === false => 'member',
				is_string( $name ) === false || is_array( Logbook::clean( $name ) ) === false || mb_strlen( $name, 'UTF-8' ) > self::NAME => 'member',
				preg_match( self::REFERENCE, $reference ) !== 1                                       => 'ref',
				$made === false || $made->format( self::TIME ) !== $at || $made->getTimestamp() > time() + self::AHEAD => 'at',
				default                                                                                => '',
			};
		}

		/**
		 *	The records of a client, the newest first
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$clientId
		 *	@param		string|null	$kind					Only this kind; null for every one
		 *	@param		string|null	$purpose			Only this purpose; null for every one. A revocation has the purpose of the agreement it takes back, so that the purpose names both; '' is the records that have none
		 *
		 *	@return 	array											id, kind, origin, client_id, member_id, member_email, member_name, thing, thing_id, project, purpose, parent (null for none), sha256, shown, at, ip, agent
		 */
		public static function of( array &$appData, int $clientId, ?string $kind = null, ?string $purpose = null ): array {

			$params = [ 'client' => $clientId ] + ( $kind === null ? [] : [ 'kind' => $kind ] ) + ( $purpose === null ? [] : [ 'purpose' => $purpose ] );

			return array_map( [ self::class, 'shape' ], Db::rows( $appData, 'SELECT * FROM records WHERE client_id = :client'. ( $kind === null ? '' : ' AND kind = :kind' ). ( $purpose === null ? '' : ' AND purpose = :purpose' ). ' ORDER BY id DESC', $params ) );
		}

		/**
		 *	The records of several clients of one kind, in one question, the newest first: a list of clients - the portal's, a page of the list - is not asked client by client. The ids are those of the clients, whole numbers
		 *	or the digits of one written as text ('7'); anything else in the list - a number with a sign or a zero in front, a float, a boolean, a list, an empty text - is no client and left out, and a client twice is one.
		 *	A long list is asked in portions of MOST and put together, so that no client is left out and none is answered by "nothing found"
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$ids					The ids of the clients
		 *	@param		string		$kind					The kind of the records
		 *
		 *	@return 	array											client id => the records of the client as of() has them, the newest first, the clients by their id; a client that has none is not in it. PHP makes a key of digits a whole number: it is the id of the client as client_id of each of its records has it
		 */
		public static function ofClients( array &$appData, array $ids, string $kind ): array {

			$clients = [];

			foreach( $ids as $id )
				if( ( is_int( $id ) === true && $id >= 1 ) || ( is_string( $id ) === true && preg_match( '/^[1-9][0-9]{0,17}$/D', $id ) === 1 ) )
					$clients[(string) $id] = true;

			$clients = array_map( 'intval', array_keys( $clients ) );

			if( $kind === '' || $clients === [] )
				return [];

			$rows = [];

			foreach( array_chunk( $clients, self::MOST ) as $portion ) {

				$params = [ 'kind' => $kind ];
				$marks = [];

				foreach( $portion as $at => $id ) {
					$marks[] = ':client'. $at;
					$params['client'. $at] = $id;
				}

				array_push( $rows, ...Db::rows( $appData, 'SELECT * FROM records WHERE kind = :kind AND client_id IN ( '. implode( ', ', $marks ). ' )', $params ) );
			}

			usort( $rows, static fn( array $a, array $b ): int => (int) $b['id'] <=> (int) $a['id'] );

			$found = [];

			foreach( array_map( [ self::class, 'shape' ], $rows ) as $record )
				$found[$record['client_id']][] = $record;

			ksort( $found );

			return $found;
		}

		/**
		 *	The records about a thing, the newest first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$thing				The kind of thing
		 *	@param		string		$id						Its id as text
		 *	@param		string|null	$kind					Only this kind of record
		 *
		 *	@return 	array											As of() has them
		 */
		public static function forThing( array &$appData, string $thing, string $id, ?string $kind = null ): array {

			return array_map( [ self::class, 'shape' ], Db::rows( $appData, 'SELECT * FROM records WHERE thing = :thing AND thing_id = :id'. ( $kind === null ? '' : ' AND kind = :kind' ). ' ORDER BY id DESC', [ 'thing' => $thing, 'id' => $id ] + ( $kind === null ? [] : [ 'kind' => $kind ] ) ) );
		}

		/**
		 *	The records about a project, the newest first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of the project, as text ('00001': a number is not the same text)
		 *
		 *	@return 	array											As of() has them; none for an empty text
		 */
		public static function ofProject( array &$appData, string $project ): array {

			return $project === '' ? [] : array_map( [ self::class, 'shape' ], Db::rows( $appData, 'SELECT * FROM records WHERE project = :project ORDER BY id DESC', [ 'project' => $project ] ) );
		}

		/**
		 *	The records about several things of one kind, the newest first. The ids are texts: '007' and '7' are two things, and a number of the list is read as the text it is written as. A long list is asked in
		 *	portions of MOST and put together, so that no id is left out and none is answered by "nothing found"
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$kind					The kind of thing
		 *	@param		array 		$refs					The ids of the things, a list of texts or whole numbers; anything else in it is left out
		 *
		 *	@return 	array											As of() has them, each with its thing_id as text, the newest first over all portions
		 */
		public static function forThings( array &$appData, string $kind, array $refs ): array {

			$ids = [];

			foreach( $refs as $ref )
				if( ( is_string( $ref ) === true && $ref !== '' ) || is_int( $ref ) === true )
					$ids[(string) $ref] = true;

			$ids = array_map( 'strval', array_keys( $ids ) );

			if( $kind === '' || $ids === [] )
				return [];

			$rows = [];

			foreach( array_chunk( $ids, self::MOST ) as $portion ) {

				$params = [ 'thing' => $kind ];
				$marks = [];

				foreach( $portion as $at => $id ) {
					$marks[] = ':id'. $at;
					$params['id'. $at] = $id;
				}

				array_push( $rows, ...Db::rows( $appData, 'SELECT * FROM records WHERE thing = :thing AND thing_id IN ( '. implode( ', ', $marks ). ' )', $params ) );
			}

			usort( $rows, static fn( array $a, array $b ): int => (int) $b['id'] <=> (int) $a['id'] );

			return array_map( [ self::class, 'shape' ], $rows );
		}

		/**
		 *	Whether a record names a thing: a package asks it before it deletes the thing, since the evidence would name what nobody can find
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$thing
		 *	@param		string		$id
		 *
		 *	@return 	bool
		 */
		public static function names( array &$appData, string $thing, string $id ): bool {

			return Db::row( $appData, 'SELECT id FROM records WHERE thing = :thing AND thing_id = :id LIMIT 1', [ 'thing' => $thing, 'id' => $id ] ) !== null;
		}

		/**
		 *	Whether a record of any kind names a project
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$project			The id of the project
		 *
		 *	@return 	bool
		 */
		public static function namesProject( array &$appData, string $project ): bool {

			return $project !== '' && Db::row( $appData, 'SELECT id FROM records WHERE project = :project LIMIT 1', [ 'project' => $project ] ) !== null;
		}

		/**
		 *	The record a record takes back: of the kind, of the client, and not named by another record
		 *
		 *	@param		\PDO			$pdo
		 *	@param		int				$id
		 *	@param		int				$clientId
		 *	@param		string		$kind
		 *
		 *	@return 	array|null
		 */
		private static function revocable( \PDO $pdo, int $id, int $clientId, string $kind ): ?array {

			$statement = $pdo->prepare( 'SELECT * FROM records WHERE id = :id AND client_id = :client AND kind = :kind AND id NOT IN ( SELECT parent FROM records WHERE parent IS NOT NULL )' );
			$statement->execute( [ 'id' => $id, 'client' => $clientId, 'kind' => $kind ] );
			$row = $statement->fetch();

			return $row === false ? null : $row;
		}

		/**
		 *	@param		array 		$row
		 *
		 *	@return 	array
		 */
		private static function shape( array $row ): array {

			$row['id'] = (int) $row['id'];
			$row['client_id'] = (int) $row['client_id'];
			$row['parent'] = ( $row['parent'] ?? null ) === null ? null : (int) $row['parent'];

			return $row;
		}
	}
}
