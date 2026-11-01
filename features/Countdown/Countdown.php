<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Countdown		see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Countdown					The time left until a moment, counted down on the page.
	 *
	 *										What the server writes is the moment itself, once, as
	 *										an ISO-8601 string with its offset in it - so a reader
	 *										in another timezone counts down to the same instant
	 *										rather than to the same wall clock. The arithmetic is
	 *										the browser's, because a page that is cached for an
	 *										hour would otherwise be an hour wrong.
	 *
	 *										Which offset that is, is the shortcode's to say first:
	 *										`tz` names the timezone the wall-clock moment in `to`
	 *										is read in. Where it says nothing the setting
	 *										`timezone` does - its default, 'server', is the
	 *										php process's own, which is UTC unless the host says
	 *										otherwise: Nino has no timezone of its own, there is
	 *										no such key in AppData::DEFAULTS and the kernel calls
	 *										no date_default_timezone_set(). A moment that carries
	 *										its own offset or zone name is read at that one,
	 *										whatever `tz` or the setting say.
	 *
	 *										Without JavaScript what stands there is the date, in a
	 *										<time datetime="..."> - written out for a reader and
	 *										machine-readable for everything else. That is the
	 *										markup; countdown.js puts the counter in front of it
	 *										and takes it out again when the moment passes. A
	 *										countdown that cannot count is still a date, which is
	 *										the fact it was carrying all along.
	 *
	 *										A moment is absolute and valid or it is none: nothing
	 *										in the text is relative to the day it is read in
	 *										("tomorrow", "+3 days", "next friday", "18:00" alone,
	 *										a month and day without a year), because those end at
	 *										a different instant at every rendering. A month alone
	 *										("2026-12", "Dec 2026") is its first day, and a Unix
	 *										timestamp ("@1798132800") is as absolute as a date is.
	 *										And \DateTimeImmutable's own verdict has to be clean
	 *										("2027-02-30" is read as the 2nd of March, with a
	 *										warning).
	 *
	 *										The names of months and days in the readable date are
	 *										the project's words, not PHP's English ones: the
	 *										letters F, M, l and D of `format` are put in as the
	 *										text keys /feature/countdown/month/<name>,
	 *										/month-short/<name>, /day/<name> and /day-short/<name>
	 *										in the language of the page - and where no language
	 *										of the project has such a word, the key stands there,
	 *										as it does everywhere else.
	 *
	 *										Units are named in both their forms - "1 Tag", "2
	 *										Tage" - and both reach the browser as data attributes
	 *										on the part they belong to. A static asset cannot read
	 *										a text fill (docs/development.md, "Assets Are Not
	 *										Templates"), so the two words are put where the fill
	 *										engine resolves them and the script only chooses.
	 *
	 *										countdown.css/countdown.js reach the browser the way
	 *										the kernel ships its own Nino.css/Nino.js: the
	 *										manifest's assets add them to the SAME site-wide
	 *										bundles the base install's html-header.tpl/
	 *										html-footer.tpl already load on every page.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Countdown {

		// The feature's key, which is what Features::setting() is asked by
		public const string KEY = 'countdown';

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Countdown/templates';

		// The parts a countdown can be split into, largest first - which is
		// also the order they are drawn in, and the only order they may be
		// drawn in: "3 Minuten 2 Tage" is not a duration anybody reads
		public const array UNITS = [ 'days', 'hours', 'minutes', 'seconds' ];
		public const array UNITS_DEFAULT = [ 'days', 'hours', 'minutes', 'seconds' ];

		// How the date under the counter is written where the shortcode does
		// not say. Unambiguous in every language, which a numeric day/month
		// pair is not
		public const string FORMAT_DEFAULT = 'Y-m-d H:i';

		// A Unix timestamp, which date_parse() marks as relative to 1970 although
		// it is the most absolute moment there is
		public const string TIMESTAMP = '/^@[+-]?\d+$/';

		// The letters of date() that are words, not numbers, and the part of
		// the key each one reads: F January, M Jan, l Monday, D Mon. The name
		// in the key is PHP's English one in lower case, whatever the language
		public const array WORDS = [
			'F' => [ 'month', 'F' ],
			'M' => [ 'month-short', 'F' ],
			'l' => [ 'day', 'l' ],
			'D' => [ 'day-short', 'l' ],
		];

		/**
		 *	Register the shortcode where the Components module does not own it:
		 *	the manifest declares [countdown] as a component, which that module
		 *	renders through doShortcode()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Feature::shortcode( $appData, 'countdown', [ self::class, 'doShortcode' ] );
		}

		/**
		 *	[countdown to="2026-12-24 18:00"]
		 *
		 *	Renders nothing where the moment is not one: a counter with no
		 *	moment behind it counts nothing, and a date that could not be read
		 *	is better said out loud in the log than shown as a wrong one.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes (see feature.php's manual)
		 *
		 *	@return 	string								The counter, or '' where "to" is not a moment
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$moment = self::moment( (string) ( $args['to'] ?? '' ), self::zone( $appData, $args ) );

			if( $moment === null )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			$format = trim( (string) ( $args['format'] ?? '' ) );
			$format = $format === '' ? self::FORMAT_DEFAULT : $format;

			/*	The sentence for afterwards is editor text that may be a textfill,
				so it is rendered first and escaped after; where the shortcode says
				nothing, the fill the install unit wrote is left for the kernel	*/
			$done = trim( (string) ( $args['done'] ?? '' ) );
			$done = $done === ''
				? '[[/feature/countdown/end/message]]'
				: $safe( \Nino\Html::renderHtml( $appData, $done ) );

			$parts = '';

			foreach( self::units( $args ) as $unit )
				$parts .= self::_part( $appData, $unit );

			/*	The date is made of words an editor wrote, so a bracket in it opens
				nothing: it is written as &#91;, the way Components::escape() does,
				before the next rendering pass can read it as a fill or a shortcode	*/
			$date = str_replace( '[', '&#91;', $safe( self::date( $appData, $moment, $format ) ) );

			// The parts last - they are built markup, and str_replace() works
			// through its arrays in order
			return str_replace(
				[ '[[classes]]', '[[iso]]', '[[done]]', '[[date]]', '[[parts]]' ],
				[
					\Nino\Feature::classes( $args ),
					$safe( $moment->format( \DateTimeInterface::ATOM ) ),
					$done,
					$date,
					$parts,
				],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'countdown' )
			);
		}

		/**
		 *	The timezone a countdown is read in: the one the shortcode names,
		 *	else the setting's, else - '' - the php process's.
		 *
		 *	A setting still on 'server' answers '', which is what every
		 *	countdown was read in before the setting existed. A stored value
		 *	that is not an option any more never gets here: Features::settings()
		 *	answers the default for it.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	string								A timezone name, '' for the process default
		 */
		public static function zone( array &$appData, array $args ): string {

			$zone = trim( (string) ( $args['tz'] ?? '' ) );

			if( $zone !== '' )
				return $zone;

			$zone = \Nino\Features::setting( $appData, self::KEY, 'timezone', 'server' );

			return is_string( $zone ) === true && $zone !== 'server' ? $zone : '';
		}

		/**
		 *	The moment a countdown counts to, or null where what was written is
		 *	not one: not absolute (something in it is relative to the day it is
		 *	read in: "tomorrow", "+3 days", a time of day alone, a date without a
		 *	year, an ISO week) or not valid ("2027-02-30"). Both are said out loud
		 *	in the log. A month alone is its first day, and a Unix timestamp
		 *	("@1798132800") is a moment.
		 *
		 *	A wall-clock time is only half a moment: "18:00" is a different
		 *	instant in Berlin than it is in London. $zone is the other half, and
		 *	it comes from zone(): the shortcode's tz, else the setting. Nino
		 *	declares no timezone of its own (\Nino\AppData::DEFAULTS has no such
		 *	key) and sets none, so without $zone this reads what was written in
		 *	whatever timezone the php process runs in - UTC on an installation
		 *	whose host says nothing. A $to that carries its own offset or zone
		 *	name is read at that one and $zone does not enter into it, which is
		 *	\DateTimeImmutable's own rule.
		 *
		 *	@param		string		$to						An absolute date, with or without a time, as \DateTimeImmutable reads it
		 *	@param		string		$zone					A timezone name for a $to without one, '' for the process default
		 *
		 *	@return 	\DateTimeImmutable|null
		 */
		public static function moment( string $to, string $zone = '' ): ?\DateTimeImmutable {

			$to 	= trim( $to );
			$zone = trim( $zone );

			if( $to === '' )
				return null;

			$timezone = null;

			/*	A zone nobody can read is the same mistake as a date nobody can
				read, and a worse one to pass over: the moment would still be a
				moment, just hours away from the one that was meant, and nothing
				on the page would look wrong	*/
			if( $zone !== '' ) {
				try {
					$timezone = new \DateTimeZone( $zone );
				}
				catch( \Exception ) {
					trigger_error( 'Nino: [countdown tz="'. $zone. '"] is not a timezone this can read.', E_USER_WARNING );
					return null;
				}
			}

			/*	Not @-suppressed and not caught silently: a date somebody typed
				wrong is a countdown that will never count, and the one moment it
				can be noticed is the one somebody is looking at the page.

				A moment is absolute and valid. Absolute: nothing in the text is
				relative to the day it is read in, because "tomorrow", "next
				friday", "+3 days" or a bare "18:00" is read against the clock of
				every rendering and would end at a different instant each time -
				date_parse() finds a year, a month and a day in the text itself
				and no relative part. A Unix timestamp is the one thing it marks
				as relative that is not, and is let through as it is written.
				Valid: what \DateTimeImmutable read it as is what was written -
				"2027-02-30" is read as the 2nd of March and only a warning says
				so; getLastErrors() answers false where there is nothing to say,
				and an array of counts where there is	*/
			$parsed = date_parse( $to );
			$moment = null;

			if( preg_match( self::TIMESTAMP, $to ) === 1
				|| ( is_int( $parsed['year'] ) === true && is_int( $parsed['month'] ) === true && is_int( $parsed['day'] ) === true && isset( $parsed['relative'] ) === false ) ) {
				try {
					$moment = new \DateTimeImmutable( $to, $timezone );
				}
				catch( \Exception ) {
					$moment = null;
				}

				$said = \DateTimeImmutable::getLastErrors();

				if( is_array( $said ) === true && $said['warning_count'] + $said['error_count'] > 0 )
					$moment = null;
			}

			if( $moment === null )
				trigger_error( 'Nino: [countdown to="'. $to. '"] is not a moment this can read.', E_USER_WARNING );

			return $moment;
		}

		/**
		 *	The moment written the way $format says, with the names of months
		 *	and days in the language of the page.
		 *
		 *	The format is read letter by letter. F, M, l and D are words, and the
		 *	word is the project's: the text key of WORDS for that moment, resolved
		 *	as any text fill of the page is, so that what stands there is what the
		 *	Text panel says in this language - and, where no language of the
		 *	project has it, the key itself. A word is text: a shortcode in it does
		 *	not run. Everything else, escaped letters ("\F" is an F) and the
		 *	digits and separators with them, is \DateTimeImmutable::format()'s, in
		 *	runs, so that its own escape rule stays its own. A backslash at the
		 *	very end escapes nothing and is dropped.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		\DateTimeImmutable	$moment		The moment
		 *	@param		string		$format				PHP's date() letters
		 *
		 *	@return 	string								Not escaped
		 */
		public static function date( array &$appData, \DateTimeImmutable $moment, string $format ): string {

			$written	= '';
			$run			= '';
			$length		= strlen( $format );

			for( $at = 0; $at < $length; $at++ ) {

				$letter = $format[$at];

				// A backslash takes the letter after it with it, whatever that is
				if( $letter === '\\' ) {
					if( $at + 1 < $length )
						$run .= $letter. $format[$at + 1];
					$at++;
					continue;
				}

				if( isset( self::WORDS[$letter] ) === false ) {
					$run .= $letter;
					continue;
				}

				[ $part, $name ] = self::WORDS[$letter];

				$key = '/feature/countdown/'. $part. '/'. strtolower( $moment->format( $name ) );

				$written .= $moment->format( $run ). ( \Nino\Html::resolveTextfill( $appData, $key ) ?? '[['. $key. ']]' );
				$run = '';
			}

			return $written. $moment->format( $run );
		}

		/**
		 *	Which parts are drawn, always largest first whatever order they
		 *	were written in: "3 Minuten 2 Tage" is not a duration anybody reads
		 *
		 *	@param		array			$args					Shortcode attributes
		 *
		 *	@return 	array									A non-empty subset of UNITS, in UNITS' order
		 */
		public static function units( array $args ): array {

			$given = trim( (string) ( $args['units'] ?? '' ) );

			if( $given === '' )
				return self::UNITS_DEFAULT;

			$wanted = array_map( 'trim', explode( ',', strtolower( $given ) ) );
			$units	= array_values( array_filter( self::UNITS, static fn( string $unit ): bool => in_array( $unit, $wanted, true ) ) );

			return $units === [] ? self::UNITS_DEFAULT : $units;
		}

		/**
		 *	One part of the counter, with both forms of its name where the fill
		 *	engine resolves them - a static asset cannot read a text fill, so
		 *	the script is handed the two words rather than the key
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$unit					A member of UNITS
		 *
		 *	@return 	string
		 */
		private static function _part( array &$appData, string $unit ): string {

			// The key is the singular's - unit-day - with both forms below it
			$part = '[[/feature/countdown/unit-'. rtrim( $unit, 's' ). '/';

			return str_replace(
				[ '[[unit]]', '[[one]]', '[[many]]' ],
				[ $unit, $part. 'one]]', $part. 'many]]' ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'countdown-part' )
			);
		}
	}

}
