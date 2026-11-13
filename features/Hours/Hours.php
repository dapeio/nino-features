<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Hours				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Hours							Opening hours of one or several places, drawn from the
	 *										elements of two types: locations (a name, an address, one
	 *										text for each day of the week, a note) and closures (a day
	 *										or a run of days on which a location is closed or has
	 *										other hours).
	 *
	 *										A day is text in one notation - 9-12, 14-18 -, read when an
	 *										element is saved. The class registers the veto of the
	 *										kernel's write of both types (/nino/elements/<type>/insert
	 *										and /update): what it cannot read, a date that does not
	 *										exist, a run of days that ends before it begins and a
	 *										location that is not there stop the write with a sentence in
	 *										the language of the workbench, and what it can read is
	 *										stored in its normal form, 09:00-12:00, 14:00-18:00, so that
	 *										every output reads the same source.
	 *
	 *										[hours] draws a location's week as a table with the days
	 *										that have the same hours put together, the closures that are
	 *										coming up and the note; [hours-today] draws one line for
	 *										today with the closures counted in. "Today" is the date in
	 *										the timezone of the feature's setting by the clock of the
	 *										server. There is no "open now": the hours are static and a
	 *										page that is cached would tell a visitor yesterday's minute.
	 *
	 *										A page the Cache module keeps is kept at most until the
	 *										midnight of that timezone: the module has no hook for a
	 *										shortcode to say how long its output lasts, so the lifetime
	 *										of the request in hand is lowered (see limitCache()).
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Hours {

		public const string KEY 				= 'hours';
		public const string TEMPLATES		= '/features/Hours/templates';
		public const string LOCATIONS		= '/locations';
		public const string CLOSURES		= '/closures';

		// The days by their ISO number, the Monday being 1 - and the names of the model's fields, of the words' keys
		public const array DAYS = [ 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun' ];

		// The longest text of a day and the most times in it: more is not a day of hours
		public const int MAX_LENGTH	= 200;
		public const int MAX_SPANS	= 8;

		// The most closures one location lists
		public const int MAX_LISTED	= 30;

		// The days and the weeks of a closure in the schema.org spelling
		private const array SCHEMA_DAYS = [ 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' ];

		/**
		 *	The vetoes of the two types, and the callback that names the field of a refusal.
		 *	Registered at every boot: a callback of /nino/elements/<type>/insert or /update that
		 *	answers false stops the write of that element in the kernel (\Nino\Elements,
		 *	_writeElementData)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOutput' ] );

			foreach( [ 'insert', 'update' ] as $operation ) {
				\Nino\Callbacks::registerCallback( $appData, '/nino/elements'. self::LOCATIONS. '/'. $operation, [ self::class, 'callbackLocations' ] );
				\Nino\Callbacks::registerCallback( $appData, '/nino/elements'. self::CLOSURES. '/'. $operation, [ self::class, 'callbackClosures' ] );
			}
		}

		// ---- The notation ----------------------------------------------------------------------------

		/**
		 *	Read the text of a day: times separated by commas, each from a start to an
		 *	end with a hyphen or an en dash between them and spaces as one likes. A
		 *	time is H, H:MM, HH:MM or H.MM; 24 or 24:00 is an end, never a start; an end
		 *	that is not after the start is on the next day (18-2), and counts for the
		 *	day of the start. Empty is a closed day.
		 *
		 *	@param		string		$text					The text as it was typed or stored
		 *
		 *	@return 	array											{ spans: list of [ start, end ] in minutes after midnight of the start's
		 *																	day, the end past 1440 on the next day; normal: the text in its
		 *																	normal form; error: '' or unreadable, many, minutes, hours, equal, overlap }
		 */
		public static function parseTimes( string $text ): array {

			$failed	= static fn( string $error ): array => [ 'spans' => [], 'normal' => '', 'error' => $error ];

			if( strlen( $text ) > self::MAX_LENGTH )
				return $failed( 'unreadable' );

			$text = preg_replace( '/[\s\x{00A0}\x{202F}\x{2009}]+/u', ' ', $text );

			if( $text === null )
				return $failed( 'unreadable' );

			$text = trim( $text );

			if( $text === '' )
				return [ 'spans' => [], 'normal' => '', 'error' => '' ];

			$parts = explode( ',', $text );

			if( count( $parts ) > self::MAX_SPANS )
				return $failed( 'many' );

			$spans = [];

			foreach( $parts as $part ) {

				if( preg_match( '/^([0-9][0-9:.]*) ?[-–] ?([0-9][0-9:.]*)$/u', trim( $part ), $found ) !== 1 )
					return $failed( 'unreadable' );

				$start	= self::_minute( $found[1], false );
				$end		= self::_minute( $found[2], true );

				if( is_string( $start ) === true )
					return $failed( $start );

				if( is_string( $end ) === true )
					return $failed( $end );

				if( $start === $end )
					return $failed( 'equal' );

				$spans[] = [ $start, $end < $start ? $end + 1440 : $end ];
			}

			usort( $spans, static fn( array $a, array $b ): int => $a[0] <=> $b[0] );

			for( $index = 1; $index < count( $spans ); $index++ )
				if( $spans[$index - 1][1] > $spans[$index][0] )
					return $failed( 'overlap' );

			return [
				'spans'		=> $spans,
				'normal'	=> implode( ', ', array_map( static fn( array $span ): string => self::_hhmm( $span[0], true ). '-'. self::_hhmm( $span[1], true ), $spans ) ),
				'error'		=> '',
			];
		}

		/**
		 *	One time of a day in minutes
		 *
		 *	@param		string		$token				H, H:MM, HH:MM or H.MM
		 *	@param		bool			$isEnd				Whether 24 or 24:00 is allowed
		 *
		 *	@return 	int|string								The minutes after midnight, or the error: unreadable, minutes, hours
		 */
		private static function _minute( string $token, bool $isEnd ): int|string {

			if( preg_match( '/^([0-9]{1,3})(?:[:.]([0-9]{2}))?$/D', $token, $found ) !== 1 )
				return 'unreadable';

			$hours		= (int) $found[1];
			$minutes	= (int) ( $found[2] ?? 0 );

			if( $hours > 24 )
				return 'hours';

			if( $minutes > 59 )
				return 'minutes';

			if( $hours === 24 && ( $minutes > 0 || $isEnd === false ) )
				return 'hours';

			return $hours * 60 + $minutes;
		}

		/**
		 *	A time of day as HH:MM
		 *
		 *	@param		int				$minute				Minutes after the midnight of the start's day, 1440 and more past it
		 *	@param		bool			$keep24				Whether the end of the day is 24:00 (true) or 00:00 (false)
		 *
		 *	@return 	string
		 */
		private static function _hhmm( int $minute, bool $keep24 ): string {

			if( $minute === 1440 && $keep24 === true )
				return '24:00';

			$minute = $minute % 1440;

			return sprintf( '%02d:%02d', intdiv( $minute, 60 ), $minute % 60 );
		}

		/**
		 *	Read a date written 2026-12-24: four digits, two, two, and a day that exists
		 *
		 *	@param		string		$text
		 *
		 *	@return 	\DateTimeImmutable|null			The date at midnight in UTC; null for what is not a date
		 */
		public static function parseDate( string $text ): ?\DateTimeImmutable {

			if( preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $text, $found ) !== 1 || checkdate( (int) $found[2], (int) $found[3], (int) $found[1] ) === false )
				return null;

			return new \DateTimeImmutable( $text. ' 00:00:00', new \DateTimeZone( 'UTC' ) );
		}

		// ---- The veto ---------------------------------------------------------------------------------

		/**
		 *	The veto of /nino/elements/locations/insert and /update: every element that
		 *	differs from the file as it is now has its seven days read and stored in the
		 *	normal form, or the write is stopped with the sentence that says which day
		 *	of which element is wrong. An element that is as it was is not asked again,
		 *	so one that was written by hand and is not readable does not hold up the
		 *	others.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$typeData		(reference) The type file as it is about to be written
		 *
		 *	@return 	mixed											false to stop the write, null to let it go
		 */
		public static function callbackLocations( array &$appData, mixed &$typeData ): mixed {

			if( is_array( $typeData ) === false )
				return null;

			foreach( self::_changed( $appData, $typeData, self::LOCATIONS ) as [ $bucket, $element ] )
				foreach( self::DAYS as $day ) {

					$value = $typeData[$bucket][$element][$day] ?? null;

					if( is_string( $value ) === false )
						continue;

					$read = self::parseTimes( $value );

					if( $read['error'] !== '' )
						return self::_refuse( $appData, $read['error'], $day, $element, $value );

					$typeData[$bucket][$element][$day] = $read['normal'];
				}

			return null;
		}

		/**
		 *	The veto of /nino/elements/closures/insert and /update, as callbackLocations()
		 *	asks it of the other type: the first and the last day have to exist and the
		 *	last not come before the first, the times have to be readable and are stored
		 *	in the normal form, and a location that is named has to be one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$typeData		(reference) The type file as it is about to be written
		 *
		 *	@return 	mixed											false to stop the write, null to let it go
		 */
		public static function callbackClosures( array &$appData, mixed &$typeData ): mixed {

			if( is_array( $typeData ) === false )
				return null;

			foreach( self::_changed( $appData, $typeData, self::CLOSURES ) as [ $bucket, $element ] ) {

				$entry	= $typeData[$bucket][$element];
				$from		= is_string( $entry['from'] ?? null ) === true ? $entry['from'] : '';
				$until	= is_string( $entry['until'] ?? null ) === true ? $entry['until'] : '';
				$place	= is_string( $entry['location'] ?? null ) === true ? $entry['location'] : '';
				$times	= is_string( $entry['times'] ?? null ) === true ? $entry['times'] : null;

				if( $from !== '' && self::parseDate( $from ) === null )
					return self::_refuse( $appData, 'date', 'from', $element, $from );

				if( $until !== '' && self::parseDate( $until ) === null )
					return self::_refuse( $appData, 'date', 'until', $element, $until );

				if( $from !== '' && $until !== '' && $until < $from )
					return self::_refuse( $appData, 'order', 'until', $element, $until );

				if( $times !== null ) {

					$read = self::parseTimes( $times );

					if( $read['error'] !== '' )
						return self::_refuse( $appData, $read['error'], 'times', $element, $times );

					$typeData[$bucket][$element]['times'] = $read['normal'];
				}

				if( $place !== '' && ( preg_match( '#^/locations/(?!\*$)[^/]+$#D', $place ) !== 1 || is_array( \Nino\Elements::getElement( $appData, $place, '*' ) ) === false ) )
					return self::_refuse( $appData, 'unknown', 'location', $element, $place );
			}

			return null;
		}

		/**
		 *	The elements of a type file that are new or differ from the file on disk: the
		 *	write is made under the lock of that file, so the disk still holds the
		 *	elements as they were
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$typeData			The type file as it is about to be written
		 *	@param		string		$type					The type, '/locations' or '/closures'
		 *
		 *	@return 	array											List of [ bucket, element ]
		 */
		private static function _changed( array &$appData, array $typeData, string $type ): array {

			$stored		= \Nino\Filesystem::getFileContent( $appData, '/elements'. $type. '.php', [] );
			$stored		= is_array( $stored ) === true ? $stored : [];
			$changed	= [];

			foreach( $typeData as $bucket => $elements ) {

				if( $bucket === 'model' || is_array( $elements ) === false )
					continue;

				foreach( $elements as $element => $entry )
					if( $element !== '*' && is_array( $entry ) === true && ( $stored[$bucket][$element] ?? null ) !== $entry )
						$changed[] = [ (string) $bucket, (string) $element ];
			}

			return $changed;
		}

		/**
		 *	Stop a write: the sentence goes out as the warning the panel shows for a save that was
		 *	refused, and the field it is about is kept for the answer (callbackOutput())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$error				The word of the error: unreadable, many, minutes, hours, equal, overlap, date, order, unknown
		 *	@param		string		$field				The key of the field in the model: a day, from, until, times or location
		 *	@param		string		$element			The element, its name
		 *	@param		string		$value				What was typed
		 *
		 *	@return 	bool											false
		 */
		private static function _refuse( array &$appData, string $error, string $field, string $element, string $value ): bool {

			$locale = class_exists( \Nino\Admin\Admin::class ) === true ? \Nino\Admin\Admin::sessionLocale( $appData ) : null;
			$words	= \Nino\Html::getFills( $appData, $locale );
			$word		= static fn( string $key ): string => html_entity_decode( (string) ( $words['[['. $key. ']]'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$format	= $word( '/feature/hours/error/'. $error );
			$named	= $word( in_array( $field, self::DAYS, true ) === true ? '/feature/hours/day-long/'. $field : '/feature/hours/field/'. $field );
			$said		= sprintf( $format !== '' ? $format : '%1$s %2$s %3$s', $named, $element, $value );

			$appData['./hours/refused'] = [ 'error' => $said, 'field' => $field ];

			trigger_error( $said );

			return false;
		}

		/**
		 *	The answer of the Elements panel to a refused save names the field it is about. The panel
		 *	answers a vetoed write with the sentence alone, and the shell marks and focuses a field only
		 *	where the answer carries its name (Nino.adminUi.status().error()); this adds the name to
		 *	the answer of this request when the sentence in it is the one this feature said. Nothing
		 *	else of any answer is touched.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The finished request
		 *
		 *	@return 	void
		 */
		public static function callbackOutput( array &$appData, array &$request ): void {

			$refused = $appData['./hours/refused'] ?? null;

			if( is_array( $refused ) === false )
				return;

			unset( $appData['./hours/refused'] );

			$response = $request['/nino/http/response'] ?? null;

			if( is_array( $response ) === true && ( $response['statusCode'] ?? 0 ) === 400 && is_array( $response['body'] ?? null ) === true
				&& ( $response['body']['error'] ?? null ) === $refused['error'] && isset( $response['body']['field'] ) === false )
				$request['/nino/http/response']['body']['field'] = $refused['field'];
		}

		// ---- The time -------------------------------------------------------------------------------

		/**
		 *	The timezone "today" is counted in: the feature's setting, else the php process's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	\DateTimeZone
		 */
		public static function zone( array &$appData ): \DateTimeZone {

			$name = \Nino\Features::setting( $appData, self::KEY, 'timezone', 'server' );

			try {
				return new \DateTimeZone( is_string( $name ) === true && $name !== 'server' ? $name : date_default_timezone_get() );
			}
			catch( \Exception ) {
				return new \DateTimeZone( date_default_timezone_get() );
			}
		}

		/**
		 *	The moment "now" in the zone: the clock of the server, unless a test has
		 *	held it. The one place the feature asks the clock, so that a test sets
		 *	./hours/now (an ISO moment, a key of the runtime that is never stored) and
		 *	every output of the request reads that moment
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	\DateTimeImmutable
		 */
		public static function now( array &$appData ): \DateTimeImmutable {

			$zone		= self::zone( $appData );
			$held		= $appData['./hours/now'] ?? '';

			if( is_string( $held ) === true && $held !== '' ) {
				try {
					return ( new \DateTimeImmutable( $held ) )->setTimezone( $zone );
				}
				catch( \Exception ) {
				}
			}

			return new \DateTimeImmutable( 'now', $zone );
		}

		/**
		 *	Keep a page of the Cache module no longer than until the next midnight of the zone.
		 *	The module has no way for a shortcode to say it, and reads the lifetime from
		 *	/nino/cache/ttl when the page is stored, after it was rendered: so the key of
		 *	this request, never stored, is lowered
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		\DateTimeImmutable	$now		The moment of the request
		 *
		 *	@return 	void
		 */
		public static function limitCache( array &$appData, \DateTimeImmutable $now ): void {

			if( ( $appData['/nino/cache/status'] ?? false ) !== true )
				return;

			$left = $now->setTime( 0, 0 )->modify( '+1 day' )->getTimestamp() - $now->getTimestamp();
			$ttl	= $appData['/nino/cache/ttl'] ?? 3600;

			if( is_int( $ttl ) === false || $ttl < 1 )
				$ttl = 3600;

			if( $left > 0 && $left < $ttl )
				$appData['/nino/cache/ttl'] = $left;
		}

		// ---- The data -------------------------------------------------------------------------------

		/**
		 *	The locations in the order of the elements, each as the language of the page reads it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											uri => element
		 */
		public static function locations( array &$appData ): array {

			$found = [];

			foreach( (array) \Nino\Elements::queryElements( $appData, self::LOCATIONS, [], '', [] ) as $hit ) {

				$uri			= is_array( $hit ) === true ? (string) ( $hit['.uri'] ?? '' ) : '';
				$element	= $uri === '' ? false : \Nino\Elements::getElementWithFallback( $appData, $uri, '' );

				if( is_array( $element ) === true )
					$found[$uri] = $element;
			}

			return $found;
		}

		/**
		 *	The closures that can be read: a first day that exists, a last day that does
		 *	not come before it (the first day where none is given) and times that can be
		 *	read. What is not is not a closure
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	list<array>								{ location, from, until, spans, reason, order }
		 */
		public static function closures( array &$appData ): array {

			$found = [];

			foreach( (array) \Nino\Elements::queryElements( $appData, self::CLOSURES, [], '', [] ) as $hit ) {

				$uri = is_array( $hit ) === true ? (string) ( $hit['.uri'] ?? '' ) : '';
				$element	= $uri === '' ? false : \Nino\Elements::getElementWithFallback( $appData, $uri, '' );

				if( is_array( $element ) === false )
					continue;

				$from		= (string) ( $element['from'] ?? '' );
				$until	= (string) ( $element['until'] ?? '' );
				$until	= $until === '' ? $from : $until;
				$read		= self::parseTimes( (string) ( $element['times'] ?? '' ) );

				if( self::parseDate( $from ) === null || self::parseDate( $until ) === null || $until < $from || $read['error'] !== '' )
					continue;

				$found[] = [
					'location'	=> (string) ( $element['location'] ?? '' ),
					'from'			=> $from,
					'until'			=> $until,
					'spans'			=> $read['spans'],
					'reason'		=> trim( (string) ( $element['reason'] ?? '' ) ),
					'order'			=> count( $found ),
				];
			}

			return $found;
		}

		/**
		 *	The times of the seven days of a location
		 *
		 *	@param		array			$element			The location
		 *
		 *	@return 	array											day number => list of spans; the day of a text that cannot be read is closed
		 */
		public static function week( array $element ): array {

			$week = [];

			foreach( self::DAYS as $number => $day )
				$week[$number] = self::parseTimes( is_string( $element[$day] ?? null ) === true ? $element[$day] : '' )['spans'];

			return $week;
		}

		/**
		 *	The times a location has on a date, the closures counted in: the closure
		 *	that names the location beats one for every location, and of two alike the one
		 *	that begins later, then the one that comes later
		 *
		 *	@param		array			$week					week()
		 *	@param		list<array>	$closures		closures()
		 *	@param		string		$uri					The location
		 *	@param		string		$date					Y-m-d
		 *
		 *	@return 	array											{ spans, reason, closure: whether a closure decided }
		 */
		public static function effective( array $week, array $closures, string $uri, string $date ): array {

			$best = null;

			foreach( $closures as $closure ) {

				if( ( $closure['location'] !== '' && $closure['location'] !== $uri ) || $closure['from'] > $date || $closure['until'] < $date )
					continue;

				$rank = [ $closure['location'] === $uri ? 1 : 0, $closure['from'], $closure['order'] ];

				if( $best === null || $rank > $best[0] )
					$best = [ $rank, $closure ];
			}

			if( $best !== null )
				return [ 'spans' => $best[1]['spans'], 'reason' => $best[1]['reason'], 'closure' => true ];

			$day = (int) ( new \DateTimeImmutable( $date. ' 00:00:00', new \DateTimeZone( 'UTC' ) ) )->format( 'N' );

			return [ 'spans' => $week[$day] ?? [], 'reason' => '', 'closure' => false ];
		}

		/**
		 *	What the closures make of the days from today to the days ahead, as stretches of days that
		 *	have the same result: one truth for each day, the closure that wins on a day being the one
		 *	effective() names, so that a closure another one covers does not show and two that overlap
		 *	never contradict one another. The limits of a stretch are worked out from the closures
		 *	themselves: the result can only change on the first day of a closure and on the day after its
		 *	last, so the days between two such limits have one result, and a stretch is named with its
		 *	real first and last day however far they lie from today.
		 *
		 *	@param		array			$week					week()
		 *	@param		list<array>	$closures		closures()
		 *	@param		string		$uri					The location
		 *	@param		string		$today				Y-m-d
		 *	@param		int				$ahead				Days
		 *
		 *	@return 	list<array>								{ from, until, spans, reason } in the order of the days
		 */
		public static function sections( array $week, array $closures, string $uri, string $today, int $ahead ): array {

			$zone		= new \DateTimeZone( 'UTC' );
			$shift	= static fn( string $date, int $days ): string => ( new \DateTimeImmutable( $date. ' 00:00:00', $zone ) )->modify( ( $days < 0 ? '' : '+' ). $days. ' days' )->format( 'Y-m-d' );
			$limit	= $shift( $today, max( 0, $ahead ) );
			$cuts		= [];

			foreach( $closures as $closure )
				if( $closure['location'] === '' || $closure['location'] === $uri ) {
					$cuts[ $closure['from'] ]								= true;
					$cuts[ $shift( $closure['until'], 1 ) ]	= true;
				}

			$cuts		= array_keys( $cuts );
			$found	= [];

			sort( $cuts );

			for( $index = 0; $index < count( $cuts ) - 1; $index++ ) {

				$from	= (string) $cuts[$index];
				$day	= self::effective( $week, $closures, $uri, $from );

				if( $day['closure'] === false )
					continue;

				$last = count( $found ) - 1;

				if( $last >= 0 && $shift( $found[$last]['until'], 1 ) === $from && $found[$last]['spans'] === $day['spans'] && $found[$last]['reason'] === $day['reason'] )
					$found[$last]['until'] = $shift( (string) $cuts[$index + 1], -1 );
				else
					$found[] = [ 'from' => $from, 'until' => $shift( (string) $cuts[$index + 1], -1 ), 'spans' => $day['spans'], 'reason' => $day['reason'] ];
			}

			$found = array_values( array_filter( $found, static fn( array $section ): bool => $section['until'] >= $today && $section['from'] <= $limit ) );

			return array_slice( $found, 0, self::MAX_LISTED );
		}

		/**
		 *	The days of a week put into runs of days that have the same times, in the order
		 *	the language begins its week
		 *
		 *	@param		array			$week					week()
		 *	@param		int				$first				The number of the first day, 1 (Monday) to 7
		 *
		 *	@return 	list<array>								{ from, to, spans } with the numbers of the days
		 */
		public static function runs( array $week, int $first ): array {

			$runs = [];

			for( $offset = 0; $offset < 7; $offset++ ) {

				$number = ( ( $first - 1 + $offset ) % 7 ) + 1;
				$last		= count( $runs ) - 1;

				if( $last >= 0 && $runs[$last]['spans'] === $week[$number] )
					$runs[$last]['to'] = $number;
				else
					$runs[] = [ 'from' => $number, 'to' => $number, 'spans' => $week[$number] ];
			}

			return $runs;
		}

		// ---- The shortcodes ------------------------------------------------------------------------

		/**
		 *	[hours /locations/main] - the week of a location. Without a location: the one
		 *	there is, or all of them one below the other with their names. In the cell of a
		 *	stack that loops locations, the location of the cell.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the Components module made ready: source, location, ahead, name, closures, class
		 *
		 *	@return 	string										'' where there is no location
		 */
		public static function componentHours( array &$appData, array $args ): string {

			$chosen = self::_choose( $appData, $args );

			if( $chosen === [] )
				return '';

			$now			= self::now( $appData );
			$today		= $now->format( 'Y-m-d' );
			$ahead		= max( 0, min( 366, (int) $args['ahead'] ) );
			$closures	= self::closures( $appData );
			$words		= self::_words( $appData );
			$html			= '';

			self::limitCache( $appData, $now );

			foreach( $chosen as $uri => $element )
				$html .= self::_location( $appData, $words, $args, $uri, $element, $closures, $today, $ahead );

			return $html;
		}

		/**
		 *	[hours-today /locations/main] - one line for today, the closures counted in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					What the Components module made ready: source, location, name, class
		 *
		 *	@return 	string										'' where there is no location
		 */
		public static function componentHoursToday( array &$appData, array $args ): string {

			$chosen = self::_choose( $appData, $args );

			if( $chosen === [] )
				return '';

			$now			= self::now( $appData );
			$words		= self::_words( $appData );
			$closures	= self::closures( $appData );
			$uri			= (string) array_key_first( $chosen );
			$element	= $chosen[$uri];
			$day			= self::effective( self::week( $element ), $closures, $uri, $now->format( 'Y-m-d' ) );

			self::limitCache( $appData, $now );

			return str_replace(
				[ '[[classes]]', '[[closed]]', '[[times]]', '[[reason]]', '[[name]]' ],
				[
					\Nino\Feature::classes( $args ),
					$day['spans'] === [] ? 'true' : 'false',
					self::_times( $appData, $words, $day['spans'] ),
					self::_reason( $appData, $day['reason'] ),
					$args['name'] === '1' ? str_replace( '[[name]]', \Nino\Modules\Components::escape( (string) ( $element['name'] ?? '' ) ), \Nino\Feature::template( $appData, self::TEMPLATES, 'hours-line-name' ) ) : '',
				],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'hours-line' )
			);
		}

		/**
		 *	The locations a call asks for: the one it names, as the first argument, as its
		 *	location or as the element of the cell it stands in; without any, all of them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args
		 *
		 *	@return 	array											uri => element
		 */
		private static function _choose( array &$appData, array $args ): array {

			$all		= self::locations( $appData );
			$asked	= trim( (string) ( $args['source'] ?? '' ) );
			$asked	= $asked !== '' ? $asked : trim( (string) ( $args['location'] ?? '' ) );
			$cell		= \Nino\Modules\Components::element( $appData );
			$inCell	= is_array( $cell ) === true && is_array( $cell['element'] ?? null ) === true ? (string) ( $cell['element']['.uri'] ?? '' ) : '';

			if( $asked === '.uri' || $asked === '.slug' )
				$asked = $asked === '.uri' ? $inCell : ( $inCell === '' ? '' : self::LOCATIONS. '/'. basename( $inCell ) );
			elseif( $asked === '' && str_starts_with( $inCell, self::LOCATIONS. '/' ) === true )
				$asked = $inCell;

			if( $asked !== '' )
				return isset( $all[$asked] ) === true ? [ $asked => $all[$asked] ] : [];

			return $all;
		}

		/**
		 *	The block of one location: its week, its closures, its note, and once for a
		 *	page the data for search engines
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$words				_words()
		 *	@param		array			$args					The arguments of the call
		 *	@param		string		$uri					The location
		 *	@param		array			$element			The location as the language reads it
		 *	@param		list<array>	$closures		closures()
		 *	@param		string		$today				Y-m-d
		 *	@param		int				$ahead				Days
		 *
		 *	@return 	string
		 */
		private static function _location( array &$appData, array $words, array $args, string $uri, array $element, array $closures, string $today, int $ahead ): string {

			$week			= self::week( $element );
			$name			= (string) ( $element['name'] ?? '' );
			$template	= static fn( string $file ): string => \Nino\Feature::template( $appData, self::TEMPLATES, $file );
			$todayDay	= (int) ( new \DateTimeImmutable( $today. ' 00:00:00', new \DateTimeZone( 'UTC' ) ) )->format( 'N' );
			$rows			= '';
			$table		= '';
			$decided		= self::effective( $week, $closures, $uri, $today );
			$shown		= $week;

			// A day a closure decides is told apart from the days of equal hours: the row of today says what holds today
			if( $decided['closure'] === true )
				$shown[$todayDay] = [ 'deviation' => $decided ];

			foreach( self::runs( $shown, $words['first'] ) as $run ) {

				$days = $run['from'] === $run['to']
					? self::_word( $appData, '/feature/hours/day-short/'. self::DAYS[ $run['from'] ] )
					: self::_word( $appData, '/feature/hours/day-short/'. self::DAYS[ $run['from'] ] ). '–'. self::_word( $appData, '/feature/hours/day-short/'. self::DAYS[ $run['to'] ] );
				$holds = self::_covers( $run, $todayDay );

				if( isset( $run['spans']['deviation'] ) === true )
					$rows .= str_replace(
						[ '[[days]]', '[[times]]', '[[reason]]' ],
						[ \Nino\Modules\Components::escape( $days ), self::_times( $appData, $words, $run['spans']['deviation']['spans'] ), self::_reason( $appData, $run['spans']['deviation']['reason'] ) ],
						$template( 'hours-row-deviation' )
					);
				else
					$rows .= str_replace(
						[ '[[days]]', '[[times]]' ],
						[ \Nino\Modules\Components::escape( $days ), self::_times( $appData, $words, $run['spans'] ) ],
						$template( $holds === true ? 'hours-row-today' : 'hours-row' )
					);
			}

			$open			= array_filter( $week, static fn( array $spans ): bool => $spans !== [] );
			$address	= trim( (string) ( $element['address'] ?? '' ) );
			$place		= $args['address'] === '1' && $address !== '' ? str_replace( '[[address]]', \Nino\Html::fieldValue( $address, [ 'type' => 'string', 'breaks' => true ] ), $template( 'hours-address' ) ) : '';
			$title		= $args['name'] === '1' && $name !== '' ? str_replace( '[[name]]', \Nino\Modules\Components::escape( $name ), $template( 'hours-title' ) ) : '';

			if( $open !== [] )
				$table = str_replace(
					[ '[[caption]]', '[[rows]]' ],
					[ ( $title. $place ) === '' ? '' : str_replace( [ '[[name]]', '[[address]]' ], [ $title, $place ], $template( 'hours-caption' ) ), $rows ],
					$template( 'hours-table' )
				);
			else
				$table = ( $title === '' ? '' : str_replace( '[[name]]', \Nino\Modules\Components::escape( $name ), $template( 'hours-line-name' ) ) ). $place;

			$coming		= self::sections( $week, $closures, $uri, $today, $ahead );
			$items		= '';

			if( $args['closures'] === '1' )
				foreach( $coming as $closure )
					$items .= str_replace(
						[ '[[dates]]', '[[times]]', '[[reason]]' ],
						[ self::_dates( $appData, $words, $closure['from'], $closure['until'] ), self::_times( $appData, $words, $closure['spans'] ), self::_reason( $appData, $closure['reason'] ) ],
						$template( 'hours-closure' )
					);

			$note = trim( (string) ( $element['note'] ?? '' ) );

			return str_replace(
				[ '[[classes]]', '[[uri]]', '[[table]]', '[[closures]]', '[[note]]', '[[data]]' ],
				[
					\Nino\Feature::classes( $args ),
					\Nino\Modules\Components::escape( $uri ),
					$table,
					$items === '' ? '' : str_replace( '[[items]]', $items, \Nino\Feature::template( $appData, self::TEMPLATES, 'hours-closures' ) ),
					$note === '' ? '' : str_replace( '[[note]]', \Nino\Html::fieldValue( $note, [ 'type' => 'string', 'breaks' => true ] ), \Nino\Feature::template( $appData, self::TEMPLATES, 'hours-note' ) ),
					self::_data( $appData, $uri, $element, $week, $coming ),
				],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'hours' )
			);
		}

		/**
		 *	The reason of a closure as markup, '' where there is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$reason
		 *
		 *	@return 	string
		 */
		private static function _reason( array &$appData, string $reason ): string {
			return $reason === '' ? '' : str_replace( '[[reason]]', \Nino\Modules\Components::escape( $reason ), \Nino\Feature::template( $appData, self::TEMPLATES, 'hours-reason' ) );
		}

		/**
		 *	Whether a run of days holds a day: the run goes from its first day to its last in the order of the week
		 *
		 *	@param		array			$run					runs()
		 *	@param		int				$day					1 to 7
		 *
		 *	@return 	bool
		 */
		private static function _covers( array $run, int $day ): bool {

			for( $number = $run['from']; ; $number = $number % 7 + 1 ) {

				if( $number === $day )
					return true;

				if( $number === $run['to'] )
					return false;
			}
		}

		/**
		 *	The words of the language that the output is made of: how the clock counts, how a
		 *	range of times and of dates is written, am and pm, and the day the week begins
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											{ clock: 12|24, range, am, pm, midnight, dates, first: 1 to 7 }
		 */
		private static function _words( array &$appData ): array {

			$first = array_search( self::_word( $appData, '/feature/hours/week/first' ), self::DAYS, true );

			return [
				'clock'	=> self::_word( $appData, '/feature/hours/time/clock' ) === '12' ? 12 : 24,
				'range'	=> self::_word( $appData, '/feature/hours/time/range' ) ?: '%1$s–%2$s',
				'am'		=> self::_word( $appData, '/feature/hours/time/am' ),
				'pm'		=> self::_word( $appData, '/feature/hours/time/pm' ),
				'midnight' => self::_word( $appData, '/feature/hours/time/midnight' ),
				'dates'	=> self::_word( $appData, '/feature/hours/date/range' ) ?: '%1$s – %2$s',
				'first'	=> is_int( $first ) === true ? $first : 1,
			];
		}

		/**
		 *	A word of the language of the page, as the characters it stands for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $key ): string {
			return html_entity_decode( \Nino\Html::renderTextfill( $appData, $key ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		/**
		 *	The times of a day for a page: each span as a range between two <time> elements, the
		 *	spans separated by a comma; the word for a closed day where there are none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$words				_words()
		 *	@param		list<array>	$spans			[ start, end ] in minutes
		 *
		 *	@return 	string										Markup, the words in it escaped
		 */
		private static function _times( array &$appData, array $words, array $spans ): string {

			if( $spans === [] )
				return \Nino\Feature::template( $appData, self::TEMPLATES, 'hours-closed' );

			$ranges = [];

			foreach( $spans as [ $start, $end ] )
				$ranges[] = str_replace(
					'[[range]]',
					strtr( \Nino\Modules\Components::escape( $words['range'] ), [ '%1$s' => self::_clock( $appData, $words, $start ), '%2$s' => self::_clock( $appData, $words, $end ) ] ),
					\Nino\Feature::template( $appData, self::TEMPLATES, 'hours-span' )
				);

			return implode( ', ', $ranges );
		}

		/**
		 *	One time of day written the way the language counts and as a <time>
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$words				_words()
		 *	@param		int				$minute				Minutes after the midnight of the day it begins on
		 *
		 *	@return 	string
		 */
		private static function _clock( array &$appData, array $words, int $minute ): string {

			$day		= $minute % 1440;
			$hour		= intdiv( $day, 60 );
			$part		= $day % 60;

			if( $words['clock'] === 24 ) {
				$text = ( $minute === 1440 ? 24 : $hour ). ( $part === 0 ? '' : sprintf( ':%02d', $part ) );
			}
			elseif( $minute === 1440 && $words['midnight'] !== '' ) {
				$text = $words['midnight'];
			}
			else {
				$twelve	= $hour % 12 === 0 ? 12 : $hour % 12;
				$text		= $twelve. ( $part === 0 ? '' : sprintf( ':%02d', $part ) ). ' '. ( $hour < 12 ? $words['am'] : $words['pm'] );
			}

			return str_replace(
				[ '[[datetime]]', '[[text]]' ],
				[ self::_hhmm( $minute, false ), \Nino\Modules\Components::escape( $text ) ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'hours-time' )
			);
		}

		/**
		 *	The first and the last day of a closure for a page, as <time> elements: one date for
		 *	a single day, a range for several
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$words				_words()
		 *	@param		string		$from					Y-m-d
		 *	@param		string		$until				Y-m-d
		 *
		 *	@return 	string
		 */
		private static function _dates( array &$appData, array $words, string $from, string $until ): string {

			$one = static fn( string $date ): string => str_replace(
				[ '[[datetime]]', '[[text]]' ],
				[ $date, \Nino\Modules\Components::escape( \Nino\Format::dateLong( $appData, $date ) ?? $date ) ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'hours-time' )
			);

			return $from === $until ? $one( $from ) : strtr( \Nino\Modules\Components::escape( $words['dates'] ), [ '%1$s' => $one( $from ), '%2$s' => $one( $until ) ] );
		}

		// ---- The data for search engines -----------------------------------------------------

		/**
		 *	The schema.org data of a location, once for a page: a Place with its name, its
		 *	address and an OpeningHoursSpecification for each time of the week and for each
		 *	closure that is listed. The house draws its Organization and LocalBusiness
		 *	without an @id, so there is nothing to hang the hours on, and the Place is
		 *	complete by itself. A block of ld+json is data and no script that runs, so it
		 *	needs no nonce (the Seo feature's [seo-jsonld] says the same).
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					The location
		 *	@param		array			$element			The location
		 *	@param		array			$week					week()
		 *	@param		list<array>	$coming			upcoming()
		 *
		 *	@return 	string										'' for the second time on a page and for a location with no hours at all
		 */
		private static function _data( array &$appData, string $uri, array $element, array $week, array $coming ): string {

			if( isset( $appData['./hours/data'][$uri] ) === true )
				return '';

			$specifications	= [];
			$byTimes				= [];

			foreach( self::DAYS as $number => $day )
				foreach( $week[$number] as [ $start, $end ] )
					$byTimes[ $start. '-'. $end ][] = 'https://schema.org/'. self::SCHEMA_DAYS[$number];

			foreach( $byTimes as $key => $days ) {
				[ $start, $end ] = array_map( 'intval', explode( '-', $key ) );
				$specifications[] = [ '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => self::_hhmm( $start, false ), 'closes' => self::_close( $end ) ];
			}

			foreach( $coming as $closure ) {

				$span = [ 'validFrom' => $closure['from'], 'validThrough' => $closure['until'] ];

				if( $closure['spans'] === [] )
					$specifications[] = [ '@type' => 'OpeningHoursSpecification' ] + $span + [ 'opens' => '00:00', 'closes' => '00:00' ];

				foreach( $closure['spans'] as [ $start, $end ] )
					$specifications[] = [ '@type' => 'OpeningHoursSpecification' ] + $span + [ 'opens' => self::_hhmm( $start, false ), 'closes' => self::_close( $end ) ];
			}

			if( $specifications === [] )
				return '';

			$appData['./hours/data'][$uri] = true;

			$place = [ '@context' => 'https://schema.org', '@type' => 'Place', 'name' => (string) ( $element['name'] ?? '' ) ];
			$lines = preg_split( '/\R/u', trim( (string) ( $element['address'] ?? '' ) ) ) ?: [];
			$lines = array_values( array_filter( array_map( 'trim', $lines ), static fn( string $line ): bool => $line !== '' ) );

			if( $lines !== [] )
				$place['address'] = [ '@type' => 'PostalAddress', 'streetAddress' => implode( ', ', $lines ) ];

			$place['openingHoursSpecification'] = $specifications;

			// JSON_HEX_TAG keeps a '</script>' in a name from ending the element; a bracket inside a string is
			// written as its escape so that the rendering that follows cannot read a name as a fill or a shortcode
			$json = json_encode( $place, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
			$json = is_string( $json ) === true ? preg_replace_callback( '/"(?:[^"\\\\]|\\\\.)*"/s', static fn( array $string ): string => str_replace( [ '[', ']' ], [ "\x5cu005b", "\x5cu005d" ], $string[0] ), $json ) : null;

			return str_replace( '[[json]]', is_string( $json ) === true ? $json : '{}', \Nino\Feature::template( $appData, self::TEMPLATES, 'hours-data' ) );
		}

		/**
		 *	The end of a time for schema.org: the end of the day is 23:59, as 24:00 is no time of day there
		 *
		 *	@param		int				$minute
		 *
		 *	@return 	string
		 */
		private static function _close( int $minute ): string {
			return $minute === 1440 ? '23:59' : self::_hhmm( $minute, false );
		}
	}

}
