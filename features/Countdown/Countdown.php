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
	 *										Which offset that is, is the call's to say first:
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
	 *										markup (components/countdown/template.tpl);
	 *										countdown.js puts the counter in front of it
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
	 *										letters F, M, l and D of `dateFormat` are put in as the
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
	 *										countdown.js reaches the browser in the site's script
	 *										bundle through the manifest's assets; the look is the
	 *										standard part of the group countdown.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Countdown {

		// The feature's key, which is what Features::setting() is asked by
		public const string KEY = 'countdown';

		// The parts a countdown can be split into, largest first - which is
		// also the order they are drawn in, and the only order they may be
		// drawn in: "3 Minuten 2 Tage" is not a duration anybody reads. The
		// names are those of the attributes that switch a part on or off
		public const array UNITS = [ 'days', 'hours', 'minutes', 'seconds' ];

		// How the date under the counter is written where the call does
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

		// The markup the renderer writes besides the template of the component: one part of the counter, the tokens in
		// double brackets. A static asset cannot read a text fill, so both forms of the name of a part stand on it
		public static array $html = [
			'part' => '<span class="countdown-part" data-countdown-unit="[[unit]]" data-countdown-one="[[one]]" data-countdown-many="[[many]]"><span class="countdown-value">&mdash;</span><span class="countdown-name">[[many]]</span></span>',
		];

		/**
		 *	The renderer of the component countdown, [countdown to="2026-12-24 18:00"]
		 *
		 *	Renders nothing where the moment is not one: a counter with no
		 *	moment behind it counts nothing, and a date that could not be read
		 *	is better said out loud in the log than shown as a wrong one. The
		 *	register has made the attributes ready, so each arrives escaped:
		 *	what is parsed is decoded first, and what is written is escaped
		 *	again where this makes it.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call
		 *	@param		string		$content			Nothing: the component holds nothing
		 *	@param		array			$context			Where the call stands
		 *
		 *	@return 	string								The counter, or '' where "to" is not a moment
		 */
		public static function componentCountdown( array &$appData, array $args, string $content, array $context ): string {

			$moment = self::moment( html_entity_decode( $args['to'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), self::zone( $appData, $args ) );

			if( $moment === null )
				return '';

			$format = trim( html_entity_decode( $args['dateFormat'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			$format = $format === '' ? self::FORMAT_DEFAULT : $format;

			// The sentence for afterwards is the call's, which the register has made text; where the call says nothing it is the word of the project
			$parts = '';

			foreach( self::units( $args ) as $unit )
				$parts .= self::_part( $appData, $unit );

			return \Nino\Components::fill( $appData, 'countdown', $args, $content, [
				'[[iso]]'		=> \Nino\Components::escape( $moment->format( \DateTimeInterface::ATOM ) ),
				'[[done]]'	=> $args['done'] === '' ? self::_word( $appData, '/feature/countdown/end/message' ) : $args['done'],
				'[[date]]'	=> \Nino\Components::escape( self::date( $appData, $moment, $format ) ),
				'[[parts]]'	=> $parts,
			] );
		}

		/**
		 *	The timezones a call can name, for the choice `tz`: the empty word first, which takes the setting's, then every
		 *	timezone PHP knows
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$attribute		The attribute that asks
		 *
		 *	@return 	array									value => label
		 */
		public static function zones( array &$appData, string $attribute ): array {

			return [ '' => [ 'en_US' => 'As the setting says', 'de_DE' => 'Wie in den Einstellungen' ] ] + array_combine( \DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers() );
		}

		/**
		 *	The timezone a countdown is read in: the one the call names,
		 *	else the setting's, else - '' - the php process's.
		 *
		 *	A setting still on 'server' answers '', which is what every
		 *	countdown was read in before the setting existed. A stored value
		 *	that is not an option any more never gets here: Features::settings()
		 *	answers the default for it.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The attributes of the call
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
		 *	it comes from zone(): the call's tz, else the setting. Nino
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
					trigger_error( 'Nino: [countdown tz="'. self::_said( $zone ). '"] is not a timezone this can read.', E_USER_WARNING );
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
				trigger_error( 'Nino: [countdown to="'. self::_said( $to ). '"] is not a moment this can read.', E_USER_WARNING );

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
		 *	were switched on in: "3 Minuten 2 Tage" is not a duration anybody reads
		 *
		 *	@param		array			$args					The attributes of the call: days, hours, minutes and seconds, each '1' or '0'
		 *
		 *	@return 	array									A non-empty subset of UNITS, in UNITS' order: all four where none is switched on
		 */
		public static function units( array $args ): array {

			$units = array_values( array_filter( self::UNITS, static fn( string $unit ): bool => ( $args[$unit] ?? '1' ) === '1' ) );

			return $units === [] ? self::UNITS : $units;
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
			$part = '/feature/countdown/unit-'. rtrim( $unit, 's' ). '/';

			return strtr( self::$html['part'], [
				'[[unit]]'	=> $unit,
				'[[one]]'		=> self::_word( $appData, $part. 'one' ),
				'[[many]]'	=> self::_word( $appData, $part. 'many' ),
			] );
		}

		/**
		 *	A word of the counter as it is written into html: the text of the key in the language of the page, escaped;
		 *	where no language has it, the key stands there, as it does everywhere else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $key ): string {
			return \Nino\Components::escape( \Nino\Html::resolveTextfill( $appData, $key ) ?? '[['. $key. ']]' );
		}

		/**
		 *	What somebody wrote, cut and cleaned to go into a line of the log: it is whatever was pasted
		 *
		 *	@param		string		$written
		 *
		 *	@return 	string
		 */
		private static function _said( string $written ): string {
			return (string) preg_replace( '/[\x00-\x1F\x7F]+/', ' ', mb_strcut( $written, 0, 200, 'UTF-8' ) );
		}
	}

}
