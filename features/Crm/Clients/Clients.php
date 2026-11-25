<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Clients		The clients of the business. A client has a number K-0001, counted inside a write transaction; all storage is Db and every statement is prepared. A client is known by its title(): the company,
	 *								else the name, else the address. Only the master data are the core's - what a package keeps of a client (accounts, a message, files, documents) is the package's, and it hears of a client
	 *								through the hooks of the core: '/crm/client/added' (client, posted: what the form of the package posted for it, key => values; answers: what a listener answers the page with),
	 *								'/crm/client/changed' (client) and '/crm/client/active' (client, active, answers).
	 *
	 *								A client that a package refuses to lose is not deleted (see Erasure); its number is not given again.
	 *
	 *								A write answers the client as list() has it, or a string: 'empty' (neither company nor name), 'email:email', 'long:<field>' or 'format:<field>' (the field a value was refused for - a control
	 *								character (the line break of the notes excepted), a text that is no UTF-8 and a country that is none of COUNTRIES are 'format:'; format:business for a flag that is none) or 'unknown'
	 *								(no such client). A database failure is a \PDOException the caller answers.
	 *
	 *	@package						Dape/Nino
	 */
	class Clients {

		/**	The fields a client is edited by, with the longest value each takes	*/
		public const array FIELDS = [
			'company' => 120, 'name' => 120, 'street' => 120, 'zip' => 12, 'city' => 80, 'country' => 2,
			'email' => 160, 'phone' => 40, 'vat_id' => 20, 'buyer_reference' => 100, 'notes' => 2000,
		];

		// What a field may hold besides its length, as a full-match pattern (an empty value always passes)
		private const array FORMATS = [
			'zip'     => '/^[A-Za-z0-9][A-Za-z0-9 -]*$/D',
			'country' => '/^[A-Z]{2}$/D',
			'phone'   => '/^[0-9+][0-9 +()\/.-]*$/D',
			'vat_id'  => '/^[A-Z]{2}[A-Za-z0-9]{2,18}$/D',
		];

		/**	The countries a client's data is offered in, by their code: the neighbours and the usual ones, Germany first; the client's own code besides where it is none of these, so that nothing is lost	*/
		public const array COUNTRIES = [ 'DE', 'AT', 'CH', 'NL', 'BE', 'LU', 'DK', 'FR', 'IT', 'ES', 'PT', 'PL', 'CZ', 'SE', 'NO', 'FI', 'IE', 'GB', 'US' ];

		/**	The hooks that tell a package a client was made, was changed or was switched on or off	*/
		public const string ADDED = '/crm/client/added';
		public const string CHANGED = '/crm/client/changed';
		public const string ACTIVE = '/crm/client/active';

		/**	The category of the number sequence of the clients: one row, year 0, whose counter is the least the next client may get	*/
		public const string CATEGORY = 'client';

		private const string TIME = 'Y-m-d\TH:i:s\Z';

		// The highest number a client has or had, as a figure: the highest of the clients there are and the number a deleted client spent (Erasure keeps the least the next one may get in sequences), so that a number is never given twice
		private const string HIGHEST = 'SELECT MAX( COALESCE( ( SELECT MAX( CAST( SUBSTR( number, 3 ) AS INTEGER ) ) FROM clients ), 0 ), COALESCE( ( SELECT next - 1 FROM sequences WHERE category = \'client\' AND year = 0 ), 0 ) ) AS highest';

		/**
		 *	The clients, by number
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of clients
		 */
		public static function list( array &$appData ): array {

			return array_map( [ self::class, 'shape' ], Db::rows( $appData, 'SELECT c.* FROM clients c ORDER BY c.number' ) );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array|null
		 */
		public static function get( array &$appData, int $id ): ?array {

			$row = Db::row( $appData, 'SELECT c.* FROM clients c WHERE c.id = :id', [ 'id' => $id ] );

			return $row === null ? null : self::shape( $row );
		}

		/**
		 *	The client with a number
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$number				K-0001
		 *
		 *	@return 	array|null								As get() has it; null for a number nobody has
		 */
		public static function byNumber( array &$appData, string $number ): ?array {

			$row = Db::row( $appData, 'SELECT c.id FROM clients c WHERE c.number = :number', [ 'number' => $number ] );

			return $row === null ? null : self::get( $appData, (int) $row['id'] );
		}

		/**
		 *	The group of a client: its number as a slug (k-0001), the name a package files what belongs to the client under
		 *
		 *	@param		string		$number				K-0001
		 *
		 *	@return 	string										k-0001
		 */
		public static function group( string $number ): string {

			return \Nino\Feature::slug( $number );
		}

		/**
		 *	The client a group is the group of
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$group				k-0001
		 *
		 *	@return 	array|null								As get() has it; null for a text that is the group of no client, among them one that only looks like it (k-1, K-0001, k-0002 with a blank)
		 */
		public static function ofGroup( array &$appData, string $group ): ?array {

			if( preg_match( '/^k-[0-9]{4,}$/D', $group ) !== 1 )
				return null;

			return self::byNumber( $appData, strtoupper( $group ) );
		}

		/**
		 *	Whether the master data of a client are complete for an invoice: a company or a name, and street, postcode, town and country. Of the fields the core holds, no more
		 *
		 *	@param		array 		$values				The fields of a client as get() has them, or as they are posted
		 *
		 *	@return 	bool
		 */
		public static function complete( array $values ): bool {

			$text = static fn( string $field ): string => is_string( $values[$field] ?? null ) === true ? trim( $values[$field] ) : '';

			if( $text( 'company' ) === '' && $text( 'name' ) === '' )
				return false;

			foreach( [ 'street', 'zip', 'city', 'country' ] as $field )
				if( $text( $field ) === '' )
					return false;

			return true;
		}

		/**
		 *	The client whose own address an address is, compared without regard to case and the blanks around it. Where several clients have it, an active one comes first, then the oldest. Nothing is changed;
		 *	a caller that tells a stranger nothing about who is a client uses it for what it does with the client, never for an answer
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$email
		 *
		 *	@return 	array|null								As get() has it; null for an address that is no client's, and for one that is none
		 */
		public static function byEmail( array &$appData, string $email ): ?array {

			$email = strtolower( trim( $email ) );

			if( $email === '' || mb_strlen( $email, 'UTF-8' ) > self::FIELDS['email'] )
				return null;

			$row = Db::row( $appData, 'SELECT id FROM clients WHERE LOWER( TRIM( email ) ) = :email ORDER BY active DESC, id LIMIT 1', [ 'email' => $email ] );

			return $row === null ? null : self::get( $appData, (int) $row['id'] );
		}

		/**
		 *	How many clients are active
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	int
		 */
		public static function countActive( array &$appData ): int {

			return (int) ( Db::row( $appData, 'SELECT COUNT(*) AS n FROM clients WHERE active = 1' )['n'] ?? 0 );
		}

		/**
		 *	How a client is called: its company, else its name, else its address
		 *
		 *	@param		array 		$client				company, name and email - a frozen client of a document has them as well
		 *	@param		string		$fallback			What it is called by where it has none of them
		 *
		 *	@return 	string										'' for a client with nothing to be called by
		 */
		public static function title( array $client, string $fallback = '' ): string {

			foreach( [ 'company', 'name', 'email' ] as $field )
				if( trim( (string) ( $client[$field] ?? '' ) ) !== '' )
					return trim( (string) $client[$field] );

			return trim( $fallback );
		}

		/**
		 *	A number of a client as it is written: K- and the count, zero-padded to four digits and never cut
		 *
		 *	@param		int				$count
		 *
		 *	@return 	string
		 */
		public static function number( int $count ): string {

			return 'K-'. sprintf( '%04d', $count );
		}

		/**
		 *	The number the next client gets, as it is now: read, never taken - one more than the highest there is, and never below the start number of the clients
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function upcoming( array &$appData ): string {

			$row = Db::row( $appData, self::HIGHEST );

			return self::number( max( (int) ( $row['highest'] ?? 0 ) + 1, Settings::start( $appData, 'client' ) ) );
		}

		/**
		 *	Add a client with the next number
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$input				Any of FIELDS, and business (a boolean)
		 *	@param		array 		$posted				What the forms of the packages posted for the client, key of the package => values: the hook ADDED hands it on
		 *
		 *	@return 	array|string							The client with packages (what the listeners answered, key => values; none where none did); or why not, see the class
		 */
		public static function add( array &$appData, array $input, array $posted = [] ): array|string {

			$values = self::clean( $input );

			if( is_string( $values ) === true )
				return $values;

			$pdo = Db::pdo( $appData );
			$now = gmdate( self::TIME );

			// The start number of the clients is the least a new client gets: it is one more than the highest there is, and never below the start
			$start = Settings::start( $appData, 'client' );

			// IMMEDIATE takes the write lock before the number is read, so two requests cannot count the same one
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$max = (int) $pdo->query( self::HIGHEST )->fetchColumn();
				$number = self::number( max( $max + 1, $start ) );

				$statement = $pdo->prepare( 'INSERT INTO clients ( number, company, name, street, zip, city, country, email, phone, vat_id, buyer_reference, business, notes, active, created, updated )
					VALUES ( :number, :company, :name, :street, :zip, :city, :country, :email, :phone, :vat_id, :buyer_reference, :business, :notes, 1, :now, :now )' );
				$statement->execute( [ 'number' => $number, 'now' => $now ] + $values );
				$id = (int) $pdo->lastInsertId();

				Logbook::crm( $appData, 'client-added', [ 'client_id' => $id, 'ref' => (string) $id ] );

				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			Db::drop( $appData );

			$client = (array) self::get( $appData, $id );
			$told = [ 'client' => $client, 'posted' => $posted, 'answers' => [] ];

			self::tell( $appData, self::ADDED, $told );

			return $client + [ 'packages' => self::answers( $told ) ];
		}

		/**
		 *	Tell the packages: a listener that breaks is a warning that names the hook and nothing of the client - what it was told about has happened already, and the answer of the others stands
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$hook
		 *	@param		mixed			&$told
		 *
		 *	@return 	bool											false where a listener broke
		 */
		private static function tell( array &$appData, string $hook, mixed &$told ): bool {

			try {
				\Nino\Callbacks::doCallbacks( $appData, $hook, $told );
			}
			catch( \Throwable $e ) {

				if( $e instanceof \Nino\Failure )
					throw $e;

				trigger_error( 'Crm: a package that was told by '. $hook. ' broke', E_USER_WARNING );

				return false;
			}

			return true;
		}

		/**
		 *	What the listeners of a hook answered, as the endpoint hands it on: key of the package => a list of small values
		 *
		 *	@param		mixed			$told
		 *
		 *	@return 	array
		 */
		private static function answers( mixed $told ): array {

			$answers = [];

			foreach( is_array( $told ) === true && is_array( $told['answers'] ?? null ) === true ? $told['answers'] : [] as $key => $answer )
				if( is_string( $key ) === true && Register::word( $key ) === true && is_array( $answer ) === true )
					$answers[$key] = $answer;

			return $answers;
		}

		/**
		 *	What hangs on a client and whether it may be deleted: see Erasure
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *
		 *	@return 	array											found, number, deletable, reasons, counts, shared and features, see Erasure::plan()
		 */
		public static function deletable( array &$appData, int $id ): array {

			return Erasure::plan( $appData, $id );
		}

		/**
		 *	Delete a client with everything that hangs on it and spend its number: see Erasure. The one place the module deletes a client
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array|null	&$done				The counts so far, also where a step failed
		 *
		 *	@return 	array|string							number, counts and features; or why not, see Erasure::run()
		 */
		public static function delete( array &$appData, int $id, ?array &$done = null ): array|string {

			return Erasure::run( $appData, $id, $done );
		}

		/**
		 *	Change the fields given; the others, the number and the active flag stay. The read and the write are one transaction of its own (BEGIN IMMEDIATE): a caller that has a transaction open is refused
		 *	with a \PDOException ("cannot start a transaction within a transaction") and its transaction is as it was
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		array 		$input				Any of FIELDS, and business (a boolean)
		 *	@param		array 		$posted				What the forms of the packages posted, see add()
		 *
		 *	@return 	array|string
		 */
		public static function change( array &$appData, int $id, array $input, array $posted = [] ): array|string {

			$pdo = Db::pdo( $appData );

			// The client is read, the fields sent are put on it and written in one piece with the write lock held: a second writer that changes another field in the meantime keeps its change, for only the
			// fields that were sent are written
			$pdo->exec( 'BEGIN IMMEDIATE' );

			try {
				$current = self::get( $appData, $id );

				if( $current === null ) {
					$pdo->exec( 'ROLLBACK' );
					return 'unknown';
				}

				$sent = array_map( 'strval', array_keys( $input ) );
				$values = self::clean( $input + $current, (string) $current['country'], $sent );

				if( is_string( $values ) === true ) {
					$pdo->exec( 'ROLLBACK' );
					return $values;
				}

				$columns = array_values( array_intersect( array_merge( array_keys( self::FIELDS ), [ 'business' ] ), $sent ) );
				$params = [ 'id' => $id, 'now' => gmdate( self::TIME ) ];

				foreach( $columns as $column )
					$params[$column] = $values[$column];

				Db::exec( $appData, 'UPDATE clients SET '. implode( ', ', array_map( static fn( string $column ): string => $column. ' = :'. $column, $columns ) ). ( $columns === [] ? '' : ', ' ). 'updated = :now WHERE id = :id', $params );

				$pdo->exec( 'COMMIT' );
			}
			catch( \Throwable $e ) {
				$pdo->exec( 'ROLLBACK' );
				throw $e;
			}

			$client = (array) self::get( $appData, $id );
			$told = [ 'client' => $client, 'posted' => $posted, 'answers' => [] ];

			self::tell( $appData, self::CHANGED, $told );

			return $client + [ 'packages' => self::answers( $told ) ];
		}

		/**
		 *	Switch a client on or off. A client is never deleted by it: its documents belong to the books. What a package keeps of a client that is switched off (an account that may not reach the portal) the
		 *	package decides when the hook ACTIVE tells it; one that cannot do it says so in failed, and the client stays as it was
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$id
		 *	@param		bool			$active
		 *
		 *	@return 	array|string							The client with packages (what the listeners answered); or 'unknown', or 'write' (a package could not follow - the client stays as it was)
		 */
		public static function setActive( array &$appData, int $id, bool $active ): array|string {

			$client = self::get( $appData, $id );

			if( $client === null )
				return 'unknown';

			$told = [ 'client' => $client, 'active' => $active, 'answers' => [], 'failed' => [] ];

			// A package that breaks, and one that says it could not follow, is no reason to go on: the client is as it was
			if( self::tell( $appData, self::ACTIVE, $told ) === false || ( is_array( $told ) === true && is_array( $told['failed'] ?? null ) === true && $told['failed'] !== [] ) )
				return 'write';

			Db::exec( $appData, 'UPDATE clients SET active = :active, updated = :now WHERE id = :id', [ 'active' => $active === true ? 1 : 0, 'now' => gmdate( self::TIME ), 'id' => $id ] );

			return (array) self::get( $appData, $id ) + [ 'packages' => self::answers( $told ) ];
		}

		/**
		 *	Trim and check the fields of a client
		 *
		 *	@param		array 		$input
		 *	@param		string		$country			The code the client has already, which stays valid besides the countries
		 *	@param		array|null	$sent					The fields the caller sent; the others are what the client holds and are not looked at - null for all
		 *
		 *	@return 	array|string							name => value for every field; or why not
		 */
		private static function clean( array $input, string $country = '', ?array $sent = null ): array|string {

			$values = [];

			foreach( self::FIELDS as $field => $max ) {

				$value = $input[$field] ?? '';

				if( is_string( $value ) === false )
					return 'format:'. $field;

				$looked = $sent === null || in_array( $field, $sent, true ) === true;

				// The master data are one line: no control character (the null byte among them), no separator of lines, no mark that turns the text around, and no text that is no UTF-8 -
				// preg_match() answers false for it. The notes keep their line breaks; the reference of the buyer is what a document takes: no character of the class C at all
				if( $looked === true ) {

					$pattern = $field === 'buyer_reference' ? '/[\p{C}\x{2028}\x{2029}]/u' : '/[\p{Cc}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

					if( preg_match( $pattern, $field === 'notes' ? str_replace( [ "\r", "\n" ], '', $value ) : $value ) !== 0 )
						return 'format:'. $field;
				}

				$value = trim( $value );

				if( $field === 'country' || $field === 'vat_id' )
					$value = strtoupper( str_replace( ' ', '', $value ) );

				if( $field === 'country' && $value === '' )
					$value = 'DE';

				if( $looked === true && mb_strlen( $value, 'UTF-8' ) > $max )
					return 'long:'. $field;

				if( $looked === true && $value !== '' && isset( self::FORMATS[$field] ) === true && preg_match( self::FORMATS[$field], $value ) !== 1 )
					return 'format:'. $field;

				// The country is one of COUNTRIES, or the code the client has already
				if( $looked === true && $field === 'country' && in_array( $value, self::COUNTRIES, true ) === false && $value !== $country )
					return 'format:country';

				if( $looked === true && $field === 'email' && $value !== '' && filter_var( $value, FILTER_VALIDATE_EMAIL ) === false )
					return 'email:email';

				$values[$field] = $value;
			}

			if( $values['company'] === '' && $values['name'] === '' )
				return 'empty';

			// Whether the client is a business, and so gets an electronic invoice: a flag the editor posts as a boolean; a text is none
			$business = $input['business'] ?? false;

			if( is_bool( $business ) === false && $business !== 0 && $business !== 1 )
				return 'format:business';

			$values['business'] = $business === true || $business === 1 ? 1 : 0;

			return $values;
		}

		/**
		 *	@param		array 		$row
		 *
		 *	@return 	array											The row with id as a number, active and business as flags and title, how the client is called
		 */
		private static function shape( array $row ): array {

			$row['id'] = (int) $row['id'];
			$row['active'] = (int) $row['active'] === 1;
			$row['business'] = (int) $row['business'] === 1;
			$row['title'] = self::title( $row );

			return $row;
		}
	}
}
