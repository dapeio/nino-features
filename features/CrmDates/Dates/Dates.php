<?php
declare(strict_types=1);

namespace Nino\Modules\CrmDates {

	/**
	 *	Dates		What the owner has entered as the dates of a client. A date is an element of the type /events of the feature Events and belongs to a client only where two things hold: a row of the table of
	 *						this package names the element and the client (by the id of the client, never by its number) and the group of the element, as Events reads it, is the group of that client (Clients::group(), the
	 *						number as a slug, k-0003). A number that is given again to another client after the test phase therefore inherits no date, and a date made by hand in the Elements panel under such a group is
	 *						nobody's until the owner says whose it is (unassigned(), the action adopt). The row is what a client holds of this package: the deletion of a client and the test phase take the rows, and the
	 *						elements that the rows name, and the core keeps the deletion from going on where the package is off or gone with its rows still there.
	 *
	 *						The elements are read through the kernel by the name the row gives, in the native language of the site - the language the package writes in, so that what a form shows is what it writes -, and
	 *						the moments through Events (\Nino\Modules\Events::moment(), selects(), now()). A date whose start is no moment still belongs to its client: it is listed as one whose time cannot be read,
	 *						can be changed and deleted, and goes with the client. A date at midnight with no end is a day, as Events has it, and nothing else is: a date that ends is shown with its start and its end.
	 *
	 *						The type can be missing or broken, and then nothing is read: state() says which, and what it answers is never an empty list. Missing, broken and off are three words of one question - may the
	 *						dates be trusted - and every caller says so to the person instead of showing nothing.
	 *
	 *	@package						Dape/Nino
	 */
	class Dates {

		/**	The feature the dates are the elements of, and its type	*/
		public const string EVENTS = 'events';
		public const string TYPE = '/events';
		public const string FILE = '/elements/events.php';

		/**	The markup the package writes a text with: a paragraph and a break, files of the folder of the package	*/
		public const string TEMPLATES = '/features/CrmDates/templates';

		/**	What the name of a date looks like in this package: the slug the feature serves it under, or any name of this form a person gave an element	*/
		public const string ID = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,99}$/D';

		/**	The table of the package: the name of an element of the type and the id of the client it belongs to	*/
		public const string TABLE = 'crm_dates';

		/**	How the moments of a form look, as the type holds them	*/
		public const string MOMENT = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/D';

		/**	How far back the list reaches for the dates that are over and how far ahead it reads by default, in days; the longest the list may look ahead	*/
		public const int PAST = 30;

		/**	How old a row is, in seconds, before it is counted as a row whose element is gone: the element of a younger row may be being written	*/
		public const int GRACE = 600;
		public const int AHEAD = 60;
		public const int MOST = 3660;

		/**	The longest title and place of a date and the longest text, in characters: the type does not hold a text to the length of its field	*/
		public const int TITLE = 200;
		public const int PLACE = 200;
		public const int TEXT = 2000;

		// What the model of the type has to say of the fields this package writes: a type that lacks one, or has another kind of it, is broken
		private const array MODEL = [ 'title' => 'string', 'start' => 'datetime', 'end' => 'datetime', 'place' => 'string', 'text' => 'string', 'link' => 'string', 'group' => 'string' ];

		// The keys of the type file that are no bucket of elements
		private const array BESIDES = [ 'model', 'title', 'autoincrement' ];

		/**
		 *	Whether the dates may be read and written: the feature Events is on and the type is there and whole. The answer is a word and never a guess: a type that is missing, one that cannot be read and one
		 *	whose shape is not the one this package writes are not "no dates"
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										ok; off (the feature Events is not on); missing (it has no type file); broken (the file cannot be read, has no model of the fields, or a bucket that is no list)
		 */
		public static function state( array &$appData ): string {

			if( \Nino\Modules\Crm::featureOn( $appData, self::EVENTS ) === false )
				return 'off';

			if( \Nino\Filesystem::fileExists( $appData, self::FILE ) === false )
				return 'missing';

			try {
				$file = \Nino\Filesystem::getFileContent( $appData, self::FILE, false );
			}
			catch( \Throwable $e ) {

				// A request that is ended is not a type that is broken
				if( $e instanceof \Nino\Failure )
					throw $e;

				return 'broken';
			}

			if( is_array( $file ) === false || is_array( $file['model'] ?? null ) === false )
				return 'broken';

			foreach( self::MODEL as $field => $type )
				if( is_array( $file['model'][$field] ?? null ) === false || ( $file['model'][$field]['type'] ?? null ) !== $type )
					return 'broken';

			foreach( $file as $key => $bucket ) {

				if( in_array( $key, self::BESIDES, true ) === true )
					continue;

				if( is_array( $bucket ) === false || array_filter( $bucket, static fn( mixed $fields ): bool => is_array( $fields ) === false ) !== [] )
					return 'broken';
			}

			return 'ok';
		}

		/**
		 *	Whether the file of the type holds an element, looked at under the lock of the type: the lock is taken for the request and the file is read from the disk. The write of the kernel makes an element that is
		 *	not there, so a caller that changes one asks first, and the lock it holds keeps another request from deleting it before the write. The write lets the lock go (the kernel releases it with the file it writes), so what comes after the write holds none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *
		 *	@return 	bool|null									null where the lock cannot be had or the file cannot be read
		 */
		public static function stored( array &$appData, string $name ): ?bool {

			if( preg_match( self::ID, $name ) !== 1 )
				return false;

			$found = self::locked( $appData, $name );

			return $found === null ? null : $found !== false;
		}

		/**
		 *	The group an element has in the file of the type, looked at under the lock of the type and read from the disk: not what this request has read before, which another request may have changed since - the
		 *	deletion of a client, that finds its dates by the group they have, asks this
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *
		 *	@return 	string|null								The group as Events reads it (a slug, '' for none); null for an element that is not there, or where the lock cannot be had or the file cannot be read
		 */
		public static function groupStored( array &$appData, string $name ): ?string {

			$found = self::locked( $appData, $name );

			return is_string( $found ) === true ? $found : null;
		}

		/**
		 *	The element of a name in the file of the type under the lock, which is taken for the request (and let go by the next write of the file)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *
		 *	@return 	string|false|null					The group of the element as Events reads it ('' for none); false where the file has no such element; null where the lock cannot be had or the file cannot be read
		 */
		private static function locked( array &$appData, string $name ): string|false|null {

			if( \Nino\Filesystem::lockFile( $appData, self::FILE ) === false )
				return null;

			try {
				$file = \Nino\Filesystem::getFileContent( $appData, self::FILE, false );
			}
			catch( \Throwable $e ) {

				if( $e instanceof \Nino\Failure )
					throw $e;

				return null;
			}

			if( is_array( $file ) === false )
				return null;

			$found = false;

			foreach( $file as $key => $bucket ) {

				if( in_array( $key, self::BESIDES, true ) === true || is_array( $bucket ) === false || array_key_exists( $name, $bucket ) === false )
					continue;

				$found = $found === false ? '' : $found;

				if( is_array( $bucket[$name] ) === true && is_string( $bucket[$name]['group'] ?? null ) === true )
					$found = self::slug( $bucket[$name]['group'] );
			}

			return $found;
		}

		/**
		 *	The clients by their id
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											id => the client as Clients::list() has it
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function clients( array &$appData ): array {

			$clients = [];

			foreach( \Nino\Modules\Crm\Clients::list( $appData ) as $client )
				$clients[(int) $client['id']] = $client;

			return $clients;
		}

		/**
		 *	The group of a client, as the core writes it
		 *
		 *	@param		array 		$client
		 *
		 *	@return 	string										k-0003; '' for a number that is no number of a client
		 */
		public static function groupOf( array $client ): string {

			return \Nino\Modules\Crm\Clients::group( (string) $client['number'] );
		}

		/**
		 *	The group of an element as Events reads it: the text as a slug; '' for none
		 *
		 *	@param		mixed			$raw
		 *
		 *	@return 	string
		 */
		public static function slug( mixed $raw ): string {

			return is_string( $raw ) === true ? \Nino\Feature::slug( trim( $raw ) ) : '';
		}

		/**
		 *	The rows of the table: which element belongs to which client, as the package noted it
		 *
		 *	@param		array 		&$appData
		 *	@param		int|null	$client				Only the rows of this client; null for all
		 *
		 *	@return 	array											A list of [ name (text), client id ]
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function rows( array &$appData, ?int $client = null ): array {

			$rows = $client === null ? \Nino\Modules\Crm\Db::rows( $appData, 'SELECT name, client_id FROM '. self::TABLE. ' ORDER BY name' ) : \Nino\Modules\Crm\Db::rows( $appData, 'SELECT name, client_id FROM '. self::TABLE. ' WHERE client_id = :client ORDER BY name', [ 'client' => $client ] );

			return array_map( static fn( array $row ): array => [ (string) $row['name'], (int) $row['client_id'] ], $rows );
		}

		/**
		 *	One element by its name, in the native language: the fields of that language and the global ones, and no text of another language in the place of one this language has none of
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *
		 *	@return 	array|null								null for an element that is not there
		 */
		public static function element( array &$appData, string $name ): ?array {

			$element = \Nino\Elements::getElement( $appData, self::TYPE. '/'. $name, \Nino\Locales::getNativeLocale( $appData ), false );

			return is_array( $element ) === true ? $element : null;
		}

		/**
		 *	Whether an element is the client's: its group, as Events reads it, is the group of the client
		 *
		 *	@param		array 		$element
		 *	@param		array 		$client
		 *
		 *	@return 	bool
		 */
		private static function fits( array $element, array $client ): bool {

			$group = self::groupOf( $client );

			return $group !== '' && self::slug( $element['group'] ?? null ) === $group;
		}

		/**
		 *	Every date of the clients, or of one: those a row names for a client that is there, whose element is there and whose group is the client's - as [ shape, record, element ] each
		 *
		 *	@param		array 		&$appData
		 *	@param		int|null	$client				Only this client
		 *
		 *	@return 	array|string							A list; the word state() gives where the dates cannot be read
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function read( array &$appData, ?int $client = null ): array|string {

			$state = self::state( $appData );

			if( $state !== 'ok' )
				return $state;

			$clients = self::clients( $appData );
			$now = \Nino\Modules\Events::now( $appData );
			$found = [];

			foreach( self::rows( $appData, $client ) as [ $name, $owner ] ) {

				$element = isset( $clients[$owner] ) === true ? self::element( $appData, $name ) : null;

				if( $element !== null && self::fits( $element, $clients[$owner] ) === true )
					$found[] = self::entry( $appData, $name, $element, $clients[$owner], $now );
			}

			return $found;
		}

		/**
		 *	The dates that are coming and the dates that are over, each soonest first, and those whose start is no moment
		 *
		 *	@param		array 		&$appData
		 *	@param		int|null	$client				Only the dates of the client with this id; null for every client
		 *	@param		int				$ahead				How many days ahead a date may begin
		 *
		 *	@return 	array|string							upcoming (dates that are not over and begin within the days ahead, the soonest first), past (those over within the last PAST days, the newest first) and unreadable (those with no start that is a moment, by name), each date as shape() has it; the word state() gives where the dates cannot be read
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function list( array &$appData, ?int $client = null, int $ahead = self::AHEAD ): array|string {

			$read = self::read( $appData, $client );

			if( is_string( $read ) === true )
				return $read;

			$now = \Nino\Modules\Events::now( $appData );
			$upcoming = [];
			$past = [];
			$unreadable = [];

			foreach( $read as [ $shape, $record ] ) {

				if( preg_match( self::ID, $shape['id'] ) !== 1 )
					continue;

				if( $record === null )
					$unreadable[] = $shape;
				elseif( \Nino\Modules\Events::selects( $record, 'upcoming', $now, $ahead, 0 ) === true )
					$upcoming[] = $shape;
				elseif( \Nino\Modules\Events::selects( $record, 'past', $now, $ahead, self::PAST ) === true )
					$past[] = $shape;
			}

			usort( $upcoming, static fn( array $a, array $b ): int => [ $a['start'], $a['id'] ] <=> [ $b['start'], $b['id'] ] );
			usort( $past, static fn( array $a, array $b ): int => [ $b['start'], $b['id'] ] <=> [ $a['start'], $a['id'] ] );
			usort( $unreadable, static fn( array $a, array $b ): int => $a['id'] <=> $b['id'] );

			return [ 'upcoming' => $upcoming, 'past' => $past, 'unreadable' => $unreadable ];
		}

		/**
		 *	One date of a client, with the words it is written in
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$id						The name of the element
		 *
		 *	@return 	array|string|null					As shape() has it, with text (as plain lines) and link; null for a name that is no date of a client; the word state() gives where the dates cannot be read
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function get( array &$appData, string $id ): array|string|null {

			if( preg_match( self::ID, $id ) !== 1 )
				return null;

			$state = self::state( $appData );

			if( $state !== 'ok' )
				return $state;

			$row = \Nino\Modules\Crm\Db::row( $appData, 'SELECT client_id FROM '. self::TABLE. ' WHERE name = :name', [ 'name' => $id ] );
			$client = $row === null ? null : \Nino\Modules\Crm\Clients::get( $appData, (int) $row['client_id'] );
			$element = $client === null ? null : self::element( $appData, $id );

			if( $client === null || $element === null || self::fits( $element, $client ) === false )
				return null;

			return self::entry( $appData, $id, $element, $client, \Nino\Modules\Events::now( $appData ) )[0] + [ 'text' => self::plain( self::text( $element, 'text' ) ), 'link' => self::text( $element, 'link' ) ];
		}

		/**
		 *	The date of a client that comes next
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$client				The id
		 *
		 *	@return 	array|string|null					As shape() has it; null where none is coming; the word state() gives where the dates cannot be read
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function next( array &$appData, int $client ): array|string|null {

			$list = self::list( $appData, $client, self::MOST );

			return is_string( $list ) === true ? $list : ( $list['upcoming'][0] ?? null );
		}

		/**
		 *	The next date of every client that has one: the first that is coming of all of them
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|string|null					As shape() has it; null where none is coming; the word state() gives where the dates cannot be read
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function first( array &$appData ): array|string|null {

			$list = self::list( $appData, null, self::MOST );

			return is_string( $list ) === true ? $list : ( $list['upcoming'][0] ?? null );
		}

		/**
		 *	The elements that carry the group of a client and are no client's: made by hand in the Elements panel, kept from before, or left where the data of a package that was removed were deleted. A
		 *	group counts that is written as the group of a client is - k-0003 or K-0003, with nothing else in it, as the core has it (Clients::GROUP, Clients::group()) - and no other: the groups of members
		 *	that are named freely stay out, whatever slug they make
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|string							A list of [ group, the names of its elements ] by group; the word state() gives where the dates cannot be read
		 *
		 *	@throws		\PDOException							The database cannot be read
		 */
		public static function unassigned( array &$appData ): array|string {

			$state = self::state( $appData );

			if( $state !== 'ok' )
				return $state;

			$clients = self::clients( $appData );
			$owner = [];

			foreach( self::rows( $appData ) as [ $name, $id ] )
				$owner['n'. $name] = $id;

			$groups = [];

			foreach( \Nino\Modules\Events::elements( $appData ) as $element ) {

				$raw = is_string( $element['group'] ?? null ) === true ? trim( $element['group'] ) : '';
				$uri = (string) ( $element['.uri'] ?? '' );

				if( str_starts_with( $uri, self::TYPE. '/' ) === false || ( preg_match( \Nino\Modules\Crm\Clients::GROUP, $raw ) !== 1 && \Nino\Modules\Crm\Clients::group( $raw ) === '' ) )
					continue;

				$name = substr( $uri, strlen( self::TYPE ) + 1 );
				$group = self::slug( $raw );
				$client = $clients[$owner['n'. $name] ?? 0] ?? null;

				if( $client === null || self::groupOf( $client ) !== $group )
					$groups['g'. $group][] = $name;
			}

			ksort( $groups );

			return array_map( static fn( string $key, array $names ): array => [ substr( $key, 1 ), $names ], array_keys( $groups ), array_values( $groups ) );
		}

		/**
		 *	The names of the rows whose element is not there and that are older than GRACE - a younger one may belong to an element that is being written -: a crash between the two writes, or an element that was deleted by hand, leaves them. Only where the type can be read - where it cannot, nothing is known
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array|string							A list of names, or the state of the type where it is not ok
		 */
		public static function lonely( array &$appData ): array|string {

			$state = self::state( $appData );

			if( $state !== 'ok' )
				return $state;

			$old = \Nino\Modules\Crm\Db::rows( $appData, 'SELECT name FROM '. self::TABLE. ' WHERE created < :before ORDER BY name', [ 'before' => time() - self::GRACE ] );

			return array_values( array_filter( array_column( $old, 'name' ), static function( string $name ) use ( &$appData ): bool { return self::element( $appData, $name ) === null; } ) );
		}

		/**
		 *	Take the rows away whose element is not there - the ones lonely() names, and only when the owner said how many they are. Nothing calls this by itself: an element that is not found may come back
		 *	(an older file of the type put back, and the right one after it), and then the rows are what says whose it is
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$count				How many rows the owner was shown
		 *
		 *	@return 	int|string								How many rows went; 'changed' where there are not that many, the state of the type where it is not ok
		 */
		public static function tidy( array &$appData, int $count ): int|string {

			$names = self::lonely( $appData );

			if( is_string( $names ) === true )
				return $names;

			if( count( $names ) !== $count )
				return 'changed';

			foreach( $names as $name )
				\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM '. self::TABLE. ' WHERE name = :name', [ 'name' => $name ] );

			return count( $names );
		}

		/**
		 *	A date as the screens have it: id (the name of the element, as text), title, place, start and end (Y-m-d\TH:i in the zone of the site; '' where the start is no moment, and for no end), all_day (it
		 *	begins at midnight and has no end: a day, as Events has it), unreadable (the start is no moment), running (it has begun and is not over), group, and the client it belongs to - and the record
		 *	Events\selects() asks for, with the start and the moment the date is over, null for a start that is no moment
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		array 		$element			As element() has it
		 *	@param		array 		$client				As Clients::list() has it
		 *	@param		\DateTimeImmutable	$now
		 *
		 *	@return 	array											[ the date, the record ]
		 */
		private static function entry( array &$appData, string $name, array $element, array $client, \DateTimeImmutable $now ): array {

			$start = \Nino\Modules\Events::moment( $appData, $element['start'] ?? null );
			$end = \Nino\Modules\Events::moment( $appData, $element['end'] ?? null );
			$end = $start !== null && $end !== null && $end > $start ? $end : null;
			$day = $start !== null && $end === null && $start->format( 'H:i:s' ) === '00:00:00';
			$over = $start === null ? null : ( $day === true ? $start->modify( '+1 day' ) : ( $end ?? $start ) );

			return [ [
				'id'						=> $name,
				'title'					=> self::text( $element, 'title' ),
				'place'					=> self::text( $element, 'place' ),
				'start'					=> $start === null ? '' : $start->format( 'Y-m-d\TH:i' ),
				'end'						=> $end === null ? '' : $end->format( 'Y-m-d\TH:i' ),
				'all_day'				=> $day,
				'unreadable'		=> $start === null,
				'running'				=> $start !== null && $over !== null && $start <= $now && $over >= $now,
				'group'					=> self::groupOf( $client ),
				'client_id'			=> (int) $client['id'],
				'client'				=> (string) $client['number'],
				'client_title'	=> (string) $client['title'],
			], $start === null ? null : [ 'start' => $start, 'over' => $over ] ];
		}

		/**
		 *	A field of an element as text
		 *
		 *	@param		array 		$element
		 *	@param		string		$field
		 *
		 *	@return 	string
		 */
		private static function text( array $element, string $field ): string {

			return is_scalar( $element[$field] ?? null ) === true ? trim( (string) $element[$field] ) : '';
		}

		/**
		 *	A moment as a person typed it, read in the zone of the site
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$value				Y-m-dTH:i
		 *
		 *	@return 	\DateTimeImmutable|string	The moment; the word of what is wrong with it: format (it is no moment, or no day) or gap (the clocks skip it on the day they go forward: it is not a time that exists)
		 */
		public static function moment( array &$appData, string $value ): \DateTimeImmutable|string {

			if( preg_match( self::MOMENT, $value ) !== 1 )
				return 'format';

			$moment = \Nino\Modules\Events::moment( $appData, $value );

			if( $moment === null )
				return 'format';

			// php moves a time that does not exist to the one after the gap: what was typed is not what would be written
			return $moment->format( 'Y-m-d\TH:i' ) === $value ? $moment : 'gap';
		}

		/**
		 *	The paragraphs the type holds for the words a person typed: blank lines divide the paragraphs, a line break inside one stays, everything is text. The markup of a paragraph and of a break are the
		 *	templates of the package; each line is escaped before it is filled in, in one pass, and nothing that was filled in is read again
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$text
		 *
		 *	@return 	string										'' for no words
		 */
		public static function paragraphs( array &$appData, string $text ): string {

			$paragraph = \Nino\Feature::template( $appData, self::TEMPLATES, 'dates-paragraph' );
			$break = \Nino\Feature::template( $appData, self::TEMPLATES, 'dates-break' );
			$escape = static fn( string $line ): string => htmlspecialchars( $line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$paragraphs = array_filter( array_map( 'trim', (array) preg_split( '/\n\s*\n/', str_replace( [ "\r\n", "\r" ], "\n", $text ) ) ), static fn( string $one ): bool => $one !== '' );

			return implode( '', array_map( static fn( string $one ): string => strtr( $paragraph, [ '[[text]]' => implode( $break, array_map( $escape, explode( "\n", $one ) ) ) ] ), $paragraphs ) );
		}

		/**
		 *	The words of a date as the type holds them (paragraphs and lists as markup) written as plain text for the field of the form: a paragraph is a block of lines, a blank line between two
		 *
		 *	@param		string		$html
		 *
		 *	@return 	string
		 */
		public static function plain( string $html ): string {

			$text = (string) preg_replace( [ '~<\s*br\s*/?\s*>~i', '~<\s*/\s*(?:p|li|ul|ol)\s*>\s*~i' ], [ "\n", "\n\n" ], $html );
			$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			return trim( (string) preg_replace( '/\n{3,}/', "\n\n", str_replace( "\xC2\xA0", ' ', $text ) ) );
		}

		/**
		 *	Whether a text is one line a person may read: no control character, no line or paragraph separator, no mark that turns the direction of the text
		 *
		 *	@param		string		$text
		 *
		 *	@return 	bool
		 */
		public static function line( string $text ): bool {

			return preg_match( '//u', $text ) === 1 && preg_match( '/[\x{0}-\x{1F}\x{7F}-\x{9F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', $text ) !== 1;
		}
	}
}
