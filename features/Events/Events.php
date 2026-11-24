<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Events			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Events						Dates as elements of the project, shown and never booked. The
	 *										owner keeps them as elements of the type /events in the Elements
	 *										panel - the install unit brings the type - and a page of
	 *										components shows them: the component events draws what it holds
	 *										once for each date that is coming (or was, or all of them),
	 *										event-when writes a date in the language of the page, event-ics
	 *										links one date as a calendar file and event-feed links the feed.
	 *
	 *										A date has a start, an end that is optional, a place, a text, a
	 *										link and a group of the Members feature, or none. The group is
	 *										the whole of the visibility: a date without one is for everyone,
	 *										a date with one is for the signed-in members of that group where
	 *										the Members feature is on, and for nobody where it is not.
	 *										Members is optional - the class asks the Features whether it is
	 *										on, and never reaches for it otherwise.
	 *
	 *										The times an editor types are wall-clock times of the site. Nino
	 *										has no timezone of its own (the kernel sets none), so the setting
	 *										timezone names it: they are read in that zone - 'server', the
	 *										default, is the one php runs in, date_default_timezone_get() -,
	 *										the chip writes in it and the calendar file converts from it to UTC.
	 *
	 *										Two routes: /.events.ics, the feed of the public dates (and, with
	 *										the token of a member in ?k=, of the dates of their group as well,
	 *										see Events\Tokens), and /.events/<slug>.ics, one date. The file
	 *										itself is Events\Ics' business.
	 *
	 *										Nothing is stored about a visitor. The one file the feature writes,
	 *										/data/events.php, holds the feed tokens of members. It has no
	 *										callback of '/nino/admin/restore', and that is on purpose: the
	 *										index is regenerable. A member who draws the page again gets a
	 *										link, and an old link that the restored file does not know opens
	 *										the public feed - so a restored backup ends no feed but the links
	 *										a member has made since it was taken. An account that is removed
	 *										takes its tokens with it: the hook '/members/removed' is heard.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Events {

		// The feature's key, which is what Features::setting() is asked by
		public const string KEY = 'events';

		// The key of the feature that answers who is signed in, and the hook it fires when it removes an account
		public const string MEMBERS = 'members';
		public const string REMOVED = '/members/removed';

		// The type of the dates and the two public addresses
		public const string TYPE = '/events';
		public const string FEED = '/.events.ics';
		public const string SINGLE = '/.events/';

		// A request's own state: the clock a test sets (a whole number of seconds, else the time is the real one)
		public const string CLOCK = './events/now';
		public const string ORGANIZER = '/events/organizer';

		// The slug of a date, which is the name of its element, as an address and a calendar file name have it: lower case
		// letters, digits and hyphens - what the editor of the Elements panel has to type for a date to have a file
		public const string NAME = '[a-z0-9-]+';

		// What a list may select: the dates that are not over, the ones that are, and both
		public const array SCOPES = [ 'upcoming', 'past', 'all' ];

		// A group that is typed and makes no slug belongs to nobody, so that a date is never public by a typing mistake
		private const string NOBODY = '!';

		// The fixed patterns of a language the intl extension is not there to write: date and time, by locale, then by
		// language, then the unambiguous default
		public const array PATTERNS = [
			'de'		=> [ 'd.m.Y', 'H:i' ],
			'en_US'	=> [ 'm/d/Y', 'g:i A' ],
			'en'		=> [ 'd/m/Y', 'H:i' ],
			''			=> [ 'Y-m-d', 'H:i' ],
		];

		/**
		 *	The two routes with their handlers, and the ear for the removal of an account
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			$appData['/nino/http/routes']['GET://.events.ics']	= [ 'uri' => self::FEED ];
			$appData['/nino/http/routes']['GET://.events/*']		= [ 'uri' => self::SINGLE. '*' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.events.ics', [ self::class, 'callbackFeed' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.events/*', [ self::class, 'callbackEvent' ] );
			\Nino\Callbacks::registerCallback( $appData, self::REMOVED, [ self::class, 'callbackRemoved' ] );
		}

		// --- Who sees what -------------------------------------------------------------

		/**
		 *	Whether the Members feature is there to ask: the Features say it is on. Whether its class can be loaded is no
		 *	answer, since the class of a feature that is off is loaded all the same; the calls of Members stand behind class_exists() as well, which
		 *	only tells the static analysis that the class is there to call
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function members( array &$appData ): bool {

			return ( \Nino\Features::get( $appData, self::MEMBERS )['active'] ?? false ) === true;
		}

		/**
		 *	The group of the member who is signed in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string								The slug; '' for a visitor, for a member without a group and where Members is not on
		 */
		public static function viewer( array &$appData ): string {

			$member = self::members( $appData ) === true && class_exists( '\\Nino\\Modules\\Members' ) === true ? \Nino\Modules\Members::current( $appData ) : null;

			return $member === null ? '' : (string) $member['group'];
		}

		/**
		 *	Whether the viewer of this request may see a date of $group: everybody sees a date without a group, a date with
		 *	one is for the signed-in members of that group
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$group				The group of the date, '' for none
		 *
		 *	@return 	bool
		 */
		public static function visible( array &$appData, string $group ): bool {

			return $group === '' || self::viewer( $appData ) === $group;
		}

		// --- Time -----------------------------------------------------------------------

		/**
		 *	The timezone of the site: the setting timezone, and for 'server' the one php runs in, which is UTC unless the host
		 *	says otherwise
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	\DateTimeZone
		 */
		public static function zone( array &$appData ): \DateTimeZone {

			$zone = \Nino\Features::setting( $appData, self::KEY, 'timezone', 'server' );

			return new \DateTimeZone( is_string( $zone ) === true && $zone !== 'server' ? $zone : date_default_timezone_get() );
		}

		/**
		 *	The clock of this request, in the timezone of the site. A test sets the key CLOCK to a number of seconds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	\DateTimeImmutable
		 */
		public static function now( array &$appData ): \DateTimeImmutable {

			$seconds = is_int( $appData[self::CLOCK] ?? null ) === true ? $appData[self::CLOCK] : time();

			return ( new \DateTimeImmutable( '@'. $seconds ) )->setTimezone( self::zone( $appData ) );
		}

		/**
		 *	A moment as an editor types it: 2026-12-24T18:00, with seconds or with a space for the T, or a day alone, 2026-12-24,
		 *	which is its midnight. Read in the timezone of the site. A day that does not exist (2027-02-30) is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$value
		 *
		 *	@return 	\DateTimeImmutable|null
		 */
		public static function moment( array &$appData, mixed $value ): ?\DateTimeImmutable {

			if( is_string( $value ) === false || preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?$/D', trim( $value ), $found ) !== 1 )
				return null;

			[ , $year, $month, $day ] = array_map( 'intval', array_slice( $found, 0, 4 ) );
			$hour		= (int) ( $found[4] ?? 0 );
			$minute	= (int) ( $found[5] ?? 0 );
			$second	= (int) ( $found[6] ?? 0 );

			if( checkdate( $month, $day, $year ) === false || $hour > 23 || $minute > 59 || $second > 59 )
				return null;

			return ( new \DateTimeImmutable( 'now', self::zone( $appData ) ) )->setDate( $year, $month, $day )->setTime( $hour, $minute, $second );
		}

		// --- A date ---------------------------------------------------------------------

		/**
		 *	The date an element is: what the list, the chip, the link and the feed all read the same way
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$element			As Elements answers it, with its '.uri'
		 *
		 *	@return 	array|null						slug, uri, title, place, text (as stored), link, group ('' for none, NOBODY for one that is no slug), start, end
		 *																	(null where it is none, not after the start or no moment), allDay (the start is a midnight and
		 *																	there is no end) and over (the moment the date is over: the end; for a day, when its day ends;
		 *																	for a date with a time and no end, when it begins) - null for an element whose start is no moment
		 */
		public static function record( array &$appData, array $element ): ?array {

			$start = self::moment( $appData, $element['start'] ?? null );

			if( $start === null )
				return null;

			$end	= self::moment( $appData, $element['end'] ?? null );
			$end	= $end !== null && $end > $start ? $end : null;
			$day	= $end === null && $start->format( 'H:i:s' ) === '00:00:00';
			$uri	= (string) ( $element['.uri'] ?? '' );
			$raw	= trim( (string) ( $element['group'] ?? '' ) );
			$group	= $raw === '' ? '' : \Nino\Feature::slug( $raw );
			$text	= static fn( string $field ): string => is_scalar( $element[$field] ?? null ) === true ? trim( (string) $element[$field] ) : '';

			return [
				'slug'		=> substr( $uri, (int) strrpos( $uri, '/' ) + 1 ),
				'uri'			=> $uri,
				'title'		=> $text( 'title' ),
				'place'		=> $text( 'place' ),
				'text'		=> $text( 'text' ),
				'link'		=> $text( 'link' ),
				'group'		=> $raw !== '' && $group === '' ? self::NOBODY : $group,
				'start'		=> $start,
				'end'			=> $end,
				'allDay'	=> $day,
				'over'		=> $day === true ? $start->modify( '+1 day' ) : ( $end ?? $start ),
			];
		}

		/**
		 *	Whether a date belongs to a scope at a moment: upcoming is what is not over (a date that has begun and not ended is
		 *	in it, a day - a midnight start and no end - is over when its day ends, and a date with a time and no end when it begins) and begins within the days ahead; past is what is over,
		 *	back as far as the days past say - 0 is all of it; all is what lies in the window of both
		 *
		 *	@param		array 		$record				From record()
		 *	@param		string		$scope				upcoming, past or all
		 *	@param		\DateTimeImmutable	$now
		 *	@param		int				$ahead				Days
		 *	@param		int				$past					Days, 0 for no limit
		 *
		 *	@return 	bool
		 */
		public static function selects( array $record, string $scope, \DateTimeImmutable $now, int $ahead, int $past ): bool {

			$over		= $record['over'];
			$limit	= $now->modify( '+'. $ahead. ' days' );
			$from		= $past > 0 ? $now->modify( '-'. $past. ' days' ) : null;
			$recent	= $from === null || $over >= $from;

			return match( $scope ) {
				'upcoming'	=> $over >= $now && $record['start'] <= $limit,
				'past'			=> $over < $now && $recent,
				default			=> $recent && $record['start'] <= $limit,
			};
		}

		/**
		 *	The dates of one scope that the viewer of this request may see, soonest first
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$elements			Elements, as a loop has them
		 *	@param		string		$scope
		 *	@param		string		$group				The group whose dates are shown besides the public ones, '' for none
		 *	@param		bool			$feed					Whether the dates are for the feed, which reads 0 days back as a year
		 *
		 *	@return 	array									Records, each with its element under 'element'
		 */
		public static function dates( array &$appData, array $elements, string $scope, string $group, bool $feed = false ): array {

			$now		= self::now( $appData );
			$ahead	= (int) \Nino\Features::setting( $appData, self::KEY, 'ahead', 365 );
			$past		= (int) \Nino\Features::setting( $appData, self::KEY, 'past', 0 );
			$dates	= [];

			// A feed that reached back for ever would grow for ever: for the feed, 0 days back is a year
			if( $feed === true && $past === 0 )
				$past = 365;

			foreach( $elements as $element ) {

				$record = is_array( $element ) === true ? self::record( $appData, $element ) : null;

				if( $record === null || ( $record['group'] !== '' && $record['group'] !== $group ) || self::selects( $record, $scope, $now, $ahead, $past ) === false )
					continue;

				$dates[] = $record + [ 'element' => $element ];
			}

			usort( $dates, static fn( array $a, array $b ): int => [ $a['start'], $a['uri'] ] <=> [ $b['start'], $b['uri'] ] );

			return $dates;
		}

		// --- The components -------------------------------------------------------------

		/**
		 *	[events] - what it holds, drawn once for each date of its scope that this viewer sees, in the order of their
		 *	start, with the date as the element of the round: a component in it reads .title, .start, .slug and the other fields
		 *	of the date. The loop is the kernel's (\Nino\Modules\Elements::queryLoop(): the query narrows by a field) with the scope,
		 *	the order and the viewer's visibility applied to what it found, and then offset and limit, which count what the viewer sees.
		 *	Where nothing is in the scope one line says so
		 *
		 *	order is read from the date that is nearest to now: asc is the soonest first, and for past that is the one that
		 *	starts last, so that past - asc is newest first and past - desc oldest first
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in, with the children
		 *
		 *	@return 	string
		 */
		public static function componentEvents( array &$appData, array $args, string $content, array $context ): string {

			$scope	= in_array( $args['scope'], self::SCOPES, true ) === true ? $args['scope'] : 'upcoming';
			$flip		= $scope === 'past' ? $args['order'] === 'asc' : $args['order'] === 'desc';
			$query	= html_entity_decode( $args['query'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			$dates = self::dates( $appData, \Nino\Modules\Elements::queryLoop( $appData, [ self::TYPE, 'query' => $query ], true ), $scope, self::viewer( $appData ) );

			if( $flip === true )
				$dates = array_reverse( $dates );

			$limit	= (int) $args['limit'];
			$dates	= array_slice( $dates, (int) $args['offset'], $limit > 0 ? $limit : null );

			if( $dates === [] )
				return \Nino\Components::fill( $appData, 'events', $args, $content, [ '[[state]]' => \Nino\Components::escape( self::_word( $appData, '/feature/events/state/'. $scope ) ) ] );

			$model	= \Nino\Elements::getElementModel( $appData, self::TYPE );
			$nodes	= self::_unid( $appData, (array) ( $context['children'] ?? [] ) );
			$fills	= is_array( $context['fills'] ?? null ) === true ? $context['fills'] : [];
			$round	= $context;
			$html		= '';

			foreach( array_values( $dates ) as $index => $date ) {

				// The names of the kernel's example elements, which are the only fields a block may read: .date is the start
				[ $element, $shape ] = self::_shaped( $date['element'], $model );

				$round['element']	= [ 'uri' => self::TYPE, 'id' => $index, 'element' => $element, 'model' => $shape ];
				$round['fills']		= array_map( 'strval', \Nino\Modules\Elements::fills( $element, $shape, $index ) ) + $fills;

				$html .= \Nino\Components::render( $appData, $nodes, $round );
			}

			return $html;
		}

		/**
		 *	The date as the children of a list read it: the element, and the fields of its type with .date added where the type has no
		 *	field of that name - the start, so that a block written against the kernel's example elements (title, text, date, link) draws
		 *	the dates of the project
		 *
		 *	@param		array 		$element			The date as the loop found it
		 *	@param		array 		$model				The model of the type
		 *
		 *	@return 	array									[ the element, the model it is read by ]
		 */
		private static function _shaped( array $element, array $model ): array {

			if( isset( $model['date'] ) === false ) {
				$element['date']	= is_scalar( $element['start'] ?? null ) === true ? (string) $element['start'] : '';
				$model['date']		= is_array( $model['start'] ?? null ) === true ? $model['start'] : [ 'type' => 'string' ];
			}

			return [ $element, $model ];
		}

		/**
		 *	[event-when .start end="end"] - a <time> that writes the start, and the end where there is one, in the language of the
		 *	page: the day (today and tomorrow in words), the time, or both. The text is the intl extension's where it is loaded and the
		 *	fixed pattern of the language where it is not (see pattern()); the datetime attribute is the start in the form a
		 *	machine reads, whatever the language. The chip carries event-when--today for a date that takes place today and
		 *	event-when--past for one that is over
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments made ready by the register: value (the start, escaped), end (the field that holds the end) and show
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in; the element of a loop is the date whose end is read
		 *
		 *	@return 	string
		 */
		public static function componentEventWhen( array &$appData, array $args, string $content, array $context ): string {

			$start = self::moment( $appData, html_entity_decode( $args['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			if( $start === null )
				return '';

			$end		= null;
			$field	= html_entity_decode( $args['end'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$cell		= $context['element'] ?? null;

			if( $field !== '' && is_array( $cell ) === true && is_array( $cell['element'] ?? null ) === true )
				$end = self::moment( $appData, $cell['element'][$field] ?? null );

			$end		= $end !== null && $end > $start ? $end : null;
			$now		= self::now( $appData );
			$show		= $args['show'];
			$allDay	= $end === null && $start->format( 'H:i:s' ) === '00:00:00';
			$same		= $end === null || $end->format( 'Y-m-d' ) === $start->format( 'Y-m-d' );
			$day		= static fn( \DateTimeImmutable $at ): string => self::_day( $appData, $at, $now );
			$time		= static fn( \DateTimeImmutable $at ): string => self::pattern( $appData, $at, false );

			if( $show === 'time' ) {
				$text	= $end === null ? $time( $start ) : $time( $start ). ( $same === true ? '-' : ' '. self::_word( $appData, '/feature/events/when/until' ). ' ' ). $time( $end );
				$iso	= $start->format( 'H:i' );
			}
			else if( $show === 'date' || $allDay === true ) {
				$text	= $same === true ? $day( $start ) : $day( $start ). ' '. self::_word( $appData, '/feature/events/when/until' ). ' '. $day( $end );
				$iso	= $start->format( 'Y-m-d' );
			}
			else {
				$from	= $day( $start ). ', '. $time( $start );
				$text	= $end === null ? $from : ( $same === true ? $from. '-'. $time( $end ) : $from. ' '. self::_word( $appData, '/feature/events/when/until' ). ' '. $day( $end ). ', '. $time( $end ) );
				$iso	= $start->format( 'Y-m-d\TH:i' );
			}

			// A day is over when its day ends, a date with a time and no end when it begins
			$over		= $allDay === true ? $start->modify( '+1 day' ) : ( $end ?? $start );
			$today	= $now->format( 'Y-m-d' );
			$state	= ( $start->format( 'Y-m-d' ) <= $today && ( $end ?? $start )->format( 'Y-m-d' ) >= $today ? ' event-when--today' : '' ). ( $over < $now ? ' event-when--past' : '' );

			return \Nino\Components::fill( $appData, 'event-when', $args, $content, [
				'[[state]]'	=> $state,
				'[[iso]]'		=> \Nino\Components::escape( $iso ),
				'[[text]]'	=> \Nino\Components::escape( $text ),
			] );
		}

		/**
		 *	[event-ics .slug] - a link to one date as a calendar file, /.events/<slug>.ics. Nothing for a date the viewer may not
		 *	see, for one that does not exist and for a name that is no slug a file can have
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The arguments made ready by the register: value (the slug or the uri of the date, escaped)
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in
		 *
		 *	@return 	string
		 */
		public static function componentEventIcs( array &$appData, array $args, string $content, array $context ): string {

			$value	= html_entity_decode( $args['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			if( preg_match( '~^(?:'. preg_quote( self::TYPE. '/', '~' ). ')?('. self::NAME. ')$~D', $value, $name ) !== 1 )
				return '';

			$slug = $name[1];

			$element = \Nino\Elements::getElement( $appData, self::TYPE. '/'. $slug );
			$record	= is_array( $element ) === true ? self::record( $appData, $element ) : null;

			if( $record === null || self::visible( $appData, $record['group'] ) === false )
				return '';

			return \Nino\Components::fill( $appData, 'event-ics', $args, $content, [ '[[href]]' => \Nino\Components::escape( \Nino\Filesystem::getDir( $appData ). self::SINGLE. $slug. '.ics' ) ] );
		}

		/**
		 *	[event-feed] - a link to the feed. For a member who is signed in it carries their token (made on the first
		 *	render and the same on every one after it, see Events\Tokens), for everybody else it is the public feed. Nothing
		 *	while the setting feed is off
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The arguments made ready by the register
		 *	@param		string		$content			''
		 *	@param		array			$context			The place the call stands in
		 *
		 *	@return 	string
		 */
		public static function componentEventFeed( array &$appData, array $args, string $content, array $context ): string {

			if( \Nino\Features::setting( $appData, self::KEY, 'feed', true ) !== true )
				return '';

			$path		= self::FEED;
			$label	= 'label';
			$member	= self::members( $appData ) === true && class_exists( '\\Nino\\Modules\\Members' ) === true ? \Nino\Modules\Members::current( $appData ) : null;
			$token	= $member === null ? '' : \Nino\Modules\Events\Tokens::forAccount( $appData, (string) $member['id'] );

			if( $token !== '' ) {
				$path		.= '?k='. $token;
				$label	= 'member';
			}

			$address = \Nino\Html::absoluteUrl( $appData, $path );

			return \Nino\Components::fill( $appData, 'event-feed', $args, $content, [
				'[[href]]'	=> \Nino\Components::escape( $address !== '' ? $address : \Nino\Filesystem::getDir( $appData ). $path ),
				'[[label]]'	=> \Nino\Components::escape( self::_word( $appData, '/feature/events/feed/'. $label ) ),
			] );
		}

		// --- The words and the pattern of a date ----------------------------------------

		/**
		 *	A date or a time written in the language of the page: the intl extension's, as the locale writes it (a medium date, a short
		 *	time), where the extension is loaded, else fixed() - which has no names of months, and is the same for every site
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		\DateTimeImmutable	$moment
		 *	@param		bool			$date					A date; a time otherwise
		 *
		 *	@return 	string								Plain text, not escaped
		 */
		public static function pattern( array &$appData, \DateTimeImmutable $moment, bool $date ): string {

			$locale = \Nino\Locales::getCurrentLocale( $appData );

			if( extension_loaded( 'intl' ) === true ) {

				$formatter	= new \IntlDateFormatter( $locale, $date === true ? \IntlDateFormatter::MEDIUM : \IntlDateFormatter::NONE, $date === true ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT, $moment->getTimezone(), \IntlDateFormatter::GREGORIAN );
				$text				= $formatter->format( $moment );

				if( is_string( $text ) === true )
					return $text;
			}

			return self::fixed( $locale, $moment, $date );
		}

		/**
		 *	A date or a time by the fixed pattern of a locale: de_DE writes 24.12.2026 and 18:00, en_US 12/24/2026 and 6:00 PM, any other
		 *	English 24/12/2026 and 18:00, every other language 2026-12-24 and 18:00. The numbers, no names of months
		 *
		 *	@param		string		$locale				eg. de_DE
		 *	@param		\DateTimeInterface	$moment
		 *	@param		bool			$date
		 *
		 *	@return 	string
		 */
		public static function fixed( string $locale, \DateTimeInterface $moment, bool $date ): string {

			$patterns = self::PATTERNS[$locale] ?? self::PATTERNS[ substr( $locale, 0, 2 ) ] ?? self::PATTERNS[''];

			return $moment->format( $patterns[ $date === true ? 0 : 1 ] );
		}

		/**
		 *	A day for the chip: today and tomorrow in words, any other as pattern() writes it
		 *
		 *	@param		array 		&$appData
		 *	@param		\DateTimeImmutable	$moment
		 *	@param		\DateTimeImmutable	$now
		 *
		 *	@return 	string
		 */
		private static function _day( array &$appData, \DateTimeImmutable $moment, \DateTimeImmutable $now ): string {

			$day = $moment->format( 'Y-m-d' );

			if( $day === $now->format( 'Y-m-d' ) )
				return self::_word( $appData, '/feature/events/when/today' );

			return $day === $now->modify( '+1 day' )->format( 'Y-m-d' ) ? self::_word( $appData, '/feature/events/when/tomorrow' ) : self::pattern( $appData, $moment, true );
		}

		/**
		 *	One of the words of the feature, in the language of the page - the key itself where no language has it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					eg. /feature/events/when/today
		 *
		 *	@return 	string								Plain text, not escaped
		 */
		private static function _word( array &$appData, string $key ): string {

			$word = \Nino\Html::getFills( $appData )['[['. $key. ']]'] ?? null;

			return is_string( $word ) === true ? html_entity_decode( $word, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '[['. $key. ']]';
		}

		/**
		 *	The nodes of a loop without the ids of what they hold: an id would stand once for each date, and a ~name of it reads the
		 *	scope outside the loop. Which attribute is an id is the register's to say
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$nodes
		 *
		 *	@return 	array
		 */
		private static function _unid( array &$appData, array $nodes ): array {

			$ids = [];

			foreach( \Nino\Components::components( $appData ) as $name => $schema )
				foreach( $schema['attributes'] as $attribute => $declared )
					if( $declared['type'] === 'id' )
						$ids[$name][] = $attribute;

			$strip = static function( array $nodes ) use ( &$strip, $ids ): array {

				foreach( $nodes as $at => $node ) {

					if( is_array( $node ) === false || isset( $node['n'] ) === false )
						continue;

					foreach( (array) ( $ids[(string) $node['n']] ?? [] ) as $attribute )
						unset( $nodes[$at]['a'][$attribute] );

					if( is_array( $node['c'] ?? null ) === true )
						$nodes[$at]['c'] = $strip( $node['c'] );
				}

				return $nodes;
			};

			return $strip( $nodes );
		}

		// --- The routes -----------------------------------------------------------------

		/**
		 *	GET /.events.ics - the feed: the public dates within the window of the settings, and with the token of a member
		 *	in ?k= the dates of their group as well. A token that is none, that nobody holds or whose account is gone, switched
		 *	off or without a group is the public feed. 404 while the setting feed is off
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackFeed( array &$appData, array &$request ): void {

			if( \Nino\Features::setting( $appData, self::KEY, 'feed', true ) !== true ) {
				self::_notFound( $request );
				return;
			}

			$query	= $request['/nino/http/request']['query'] ?? [];
			$token	= is_array( $query ) === true && is_string( $query['k'] ?? null ) === true ? $query['k'] : '';
			$id			= $token !== '' && self::members( $appData ) === true ? \Nino\Modules\Events\Tokens::account( $appData, $token ) : null;
			$member	= $id !== null && class_exists( '\\Nino\\Modules\\Members' ) === true ? \Nino\Modules\Members::account( $appData, $id ) : null;
			$member	= $member !== null && $member['active'] === true ? $member : null;

			$dates		= self::dates( $appData, self::_elements( $appData ), 'all', $member === null ? '' : (string) $member['group'], true );

			$response = &$request['/nino/http/response'];

			$organizer = self::_organizer( $appData, $member === null ? null : (string) $member['id'] );

			$response['body']														= \Nino\Modules\Events\Ics::calendar( self::_calendarName( $appData ), array_map( fn( array $date ): array => self::_event( $appData, $date, $organizer ), $dates ) );
			$response['header']['Content-Type']					= 'text/calendar; charset=utf-8';
			$response['header']['Content-Disposition']	= 'inline; filename="events.ics"';
			$response['header']['Cache-Control']				= $member === null ? 'public, max-age=3600' : 'private, max-age=3600';
		}

		/**
		 *	GET /.events/<slug>.ics - one date as a calendar file. A date with a group is answered to a signed-in member of that group
		 *	and is a 404 to everybody else, as is a name that is no slug and a date that is none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackEvent( array &$appData, array &$request ): void {

			$uri		= (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$slug		= preg_match( '~^'. preg_quote( self::SINGLE, '~' ). '('. self::NAME. ')\.ics$~D', $uri, $name ) === 1 ? $name[1] : '';
			$element	= $slug !== '' ? \Nino\Elements::getElementWithFallback( $appData, self::TYPE. '/'. $slug, '' ) : false;
			$record	= is_array( $element ) === true ? self::record( $appData, $element ) : null;

			if( $record === null || self::visible( $appData, $record['group'] ) === false ) {
				self::_notFound( $request );
				return;
			}

			$response = &$request['/nino/http/response'];

			$response['body']														= \Nino\Modules\Events\Ics::calendar( self::_calendarName( $appData ), [ self::_event( $appData, $record, self::_organizer( $appData, self::members( $appData ) === true && class_exists( '\\Nino\\Modules\\Members' ) === true ? ( \Nino\Modules\Members::current( $appData )['id'] ?? null ) : null ) ) ] );
			$response['header']['Content-Type']					= 'text/calendar; charset=utf-8';
			$response['header']['Content-Disposition']	= 'inline; filename="'. $slug. '.ics"';
			$response['header']['Cache-Control']				= $record['group'] === '' ? 'public, max-age=3600' : 'private, no-store';
		}

		/**
		 *	The hook of Members when it removes an account: the feed tokens of that account are taken out of the index, so that
		 *	its link opens nothing. Told the account as an array of 'id', 'email' and 'group', once for each account; anything else is not what Members fires and is left alone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			&$account			(reference) The account that was removed: id, and what else Members says of it
		 *
		 *	@return 	void
		 */
		public static function callbackRemoved( array &$appData, mixed &$account ): void {

			if( is_array( $account ) === true && is_string( $account['id'] ?? null ) === true && $account['id'] !== '' && is_string( $account['email'] ?? null ) === true && is_string( $account['group'] ?? null ) === true )
				\Nino\Modules\Events\Tokens::forget( $appData, [ $account['id'] ] );
		}

		/**
		 *	Every date of the type, each in the language of the page with what that language has no text for taken from another
		 *	(\Nino\Elements::getElementWithFallback()): a calendar entry has to have a title
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array									Elements
		 */
		private static function _elements( array &$appData ): array {

			$elements	= \Nino\Elements::queryElements( $appData, self::TYPE, [], '', [] );
			$found		= [];

			foreach( is_array( $elements ) === true ? $elements : [] as $element ) {

				$with = is_array( $element ) === true && is_string( $element['.uri'] ?? null ) === true ? \Nino\Elements::getElementWithFallback( $appData, $element['.uri'], '' ) : false;

				if( is_array( $with ) === true )
					$found[] = $with;
			}

			return $found;
		}

		/**
		 *	The answer to anything the routes do not give: a plain 404, the same for a date that is not there and one that is not for this
		 *	viewer, kept out of every cache
		 *
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		private static function _notFound( array &$request ): void {

			$request['/nino/http/response']['statusCode']								= 404;
			$request['/nino/http/response']['body']										= 'Not Found';
			$request['/nino/http/response']['header']['Content-Type']		= 'text/plain; charset=utf-8';
			$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';
		}

		/**
		 *	The name of the calendar: the setting, else the name of the company, else the feature's own word
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string								Plain text
		 */
		private static function _calendarName( array &$appData ): string {

			$name = trim( (string) \Nino\Features::setting( $appData, self::KEY, 'name', '' ) );

			if( $name === '' )
				$name = trim( html_entity_decode( (string) ( \Nino\Html::getFills( $appData )['[[/project/company/general/name]]'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			return $name !== '' ? $name : self::_word( $appData, '/feature/events/feed/name' );
		}

		/**
		 *	A date as Ics::event() reads it. The stamp is the start - the Elements keep no time of their last change - the
		 *	description is the text as plain text, the address is the link where it is one a calendar can use, and the UID is
		 *	the slug with the host of the website, the slug alone for a website that has no address
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$date					From record()
		 *	@param		array|null	$organizer		From _organizer(); an event that is with nobody has none
		 *
		 *	@return 	array
		 */
		private static function _event( array &$appData, array $date, ?array $organizer = null ): array {

			$site		= \Nino\Html::absoluteUrl( $appData );
			$host		= $site === '' ? '' : (string) parse_url( $site, PHP_URL_HOST );
			$link		= $date['link'];

			if( str_starts_with( $link, '/' ) === true && str_starts_with( $link, '//' ) === false && $site !== '' )
				$link = \Nino\Html::absoluteUrl( $appData, $link );

			$event = [
				'uid'					=> $host !== '' ? $date['slug']. '@'. $host : $date['slug'],
				'stamp'				=> $date['start'],
				'start'				=> $date['start'],
				'end'					=> $date['end'],
				'allDay'			=> $date['allDay'],
				'summary'			=> $date['title'],
				'location'		=> $date['place'],
				'description'	=> self::_plain( $date['text'] ),
				'url'					=> preg_match( '~^https?://[^\s]+$~i', $link ) === 1 ? $link : '',
			];

			if( $organizer !== null ) {
				$event['organizer']	= $organizer;
				$event['url']				= $event['url'] !== '' ? $event['url'] : $organizer['url'];
			}

			return $event;
		}

		/**
		 *	Who the dates are with, as the callback /events/organizer says it - once for a file, not for each of its dates. The callback is
		 *	handed name, email, phone, address (lines allowed) and url, all empty, and account, the account the file is for, and fills what it knows, and may add label, the word in front of
		 *	the name; nothing answers where nobody listens or where the answer has no name. The address is the organizer's and is never the place of a date. The word in front of
		 *	the name is the label of the answer where it is a text, else the key /feature/events/organizer/at in the language of the request, left out where the project has neither
		 *
		 *	@param		array 		&$appData
		 *	@param		string|null	$account			The id of the account the file is for - the member signed in for the file of a date, the one of the token for the feed - handed to the callback as account, null for a visitor
		 *
		 *	@return 	array|null						name, email, phone, address, url (a link a calendar can use, else ''), label; null for none
		 */
		private static function _organizer( array &$appData, ?string $account = null ): ?array {

			$organizer = [ 'name' => '', 'email' => '', 'phone' => '', 'address' => '', 'url' => '', 'account' => $account ];
			$answer = \Nino\Callbacks::doCallbacks( $appData, self::ORGANIZER, $organizer );

			if( is_array( $answer ) === false )
				return null;

			foreach( [ 'name', 'email', 'phone', 'address', 'url' ] as $key )
				$organizer[$key] = is_string( $answer[$key] ?? null ) === true ? trim( $answer[$key] ) : '';

			if( trim( (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $organizer['name'] ) ) === '' )
				return null;

			$organizer['url']		= preg_match( '~^https?://[^\s]+$~i', $organizer['url'] ) === 1 ? $organizer['url'] : '';
			$label = is_string( $answer['label'] ?? null ) === true ? trim( $answer['label'] ) : '';
			$organizer['label']	= $label !== '' ? $label : trim( html_entity_decode( (string) ( \Nino\Html::getFills( $appData )['[[/feature/events/organizer/at]]'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

			return $organizer;
		}

		/**
		 *	The text of a date as plain text: each paragraph and each list item of its markup is a line of its own, the tags go and the
		 *	entities are the characters they stand for
		 *
		 *	@param		string		$html					As stored: sanitized, with paragraphs and lists
		 *
		 *	@return 	string
		 */
		private static function _plain( string $html ): string {

			$text = (string) preg_replace( [ '~<\s*li\b[^>]*>~i', '~<\s*/?\s*(?:br|p|li|div|ul|ol|h[1-6])\b[^>]*>~i' ], [ "\n- ", "\n" ], $html );
			$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$text = (string) preg_replace( [ '~[ \t]+(?=\n)~', '~\n+~' ], [ '', "\n" ], str_replace( [ "\r\n", "\r" ], "\n", $text ) );

			return trim( $text );
		}
	}
}
