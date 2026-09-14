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
	 *										an ISO-8601 string with the site's own offset in it -
	 *										so a reader in another timezone counts down to the
	 *										same instant rather than to the same wall clock. The
	 *										arithmetic is the browser's, because a page that is
	 *										cached for an hour would otherwise be an hour wrong.
	 *
	 *										Without JavaScript what stands there is the date, in a
	 *										<time datetime="..."> - written out for a reader and
	 *										machine-readable for everything else. That is the
	 *										markup; countdown.js puts the counter in front of it
	 *										and takes it out again when the moment passes. A
	 *										countdown that cannot count is still a date, which is
	 *										the fact it was carrying all along.
	 *
	 *										Units are named in both their forms - "1 Tag", "2
	 *										Tage" - and both reach the browser as data attributes
	 *										on the part they belong to. A static asset cannot read
	 *										a text fill (docs/development.md, "Assets Are Not
	 *										Templates"), so the two words are put where the fill
	 *										engine resolves them and the script only chooses.
	 *
	 *										countdown.css/countdown.js reach the browser the way
	 *										the kernel ships its own Nino.css/Nino.js: added to
	 *										the SAME site-wide bundles the base install's
	 *										html-header.tpl/html-footer.tpl already load on every
	 *										page.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Countdown {

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

		/**
		 *	Register the shortcode and ship the two static files
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'countdown', [ self::class, 'doShortcode' ] );

			/*	A source outside \Nino\Filesystem::PRIVATE_DIRS/PUBLIC_DIRS
				resolves against the project root (\Nino\Filesystem::path()'s
				fallback) - the same way '/_nino/Nino.css' already does for the
				kernel's own bundle	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Countdown/assets/countdown.css' );
			\Nino\Html::addAsset( $appData, '/.cache/script.js', '/features/Countdown/assets/countdown.js' );
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

			$moment = self::moment( (string) ( $args['to'] ?? '' ) );

			if( $moment === null )
				return '';

			$safe = static fn( string $value ): string => htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );

			$format = trim( (string) ( $args['format'] ?? '' ) );
			$format = $format === '' ? self::FORMAT_DEFAULT : $format;

			/*	The sentence for afterwards is editor text that may be a textfill,
				so it is rendered first and escaped after; where the shortcode says
				nothing, the fill the install unit wrote is left for the kernel	*/
			$done = trim( (string) ( $args['done'] ?? '' ) );
			$done = $done === ''
				? '[[/countdown/done]]'
				: $safe( \Nino\Html::renderHtml( $appData, $done ) );

			$parts = '';

			foreach( self::units( $args ) as $unit )
				$parts .= self::_part( $appData, $unit );

			// The parts last - they are built markup, and str_replace() works
			// through its arrays in order
			return str_replace(
				[ '[[iso]]', '[[date]]', '[[done]]', '[[parts]]' ],
				[
					$safe( $moment->format( \DateTimeInterface::ATOM ) ),
					$safe( $moment->format( $format ) ),
					$done,
					$parts,
				],
				self::template( $appData, 'countdown' )
			);
		}

		/**
		 *	The moment a countdown counts to, in the site's own timezone, or
		 *	null where what was written is not one
		 *
		 *	@param		string		$to						Anything \DateTimeImmutable reads
		 *
		 *	@return 	\DateTimeImmutable|null
		 */
		public static function moment( string $to ): ?\DateTimeImmutable {

			$to = trim( $to );

			if( $to === '' )
				return null;

			/*	Not @-suppressed and not caught silently: a date somebody typed
				wrong is a countdown that will never count, and the one moment it
				can be noticed is the one somebody is looking at the page	*/
			try {
				$moment = new \DateTimeImmutable( $to );
			}
			catch( \Exception ) {
				trigger_error( 'Nino: [countdown to="'. $to. '"] is not a moment this can read.', E_USER_WARNING );
				return null;
			}

			return $moment;
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

			return str_replace(
				[ '[[unit]]', '[[one]]', '[[many]]' ],
				[ $unit, '[[/countdown/'. rtrim( $unit, 's' ). ']]', '[[/countdown/'. $unit. ']]' ],
				self::template( $appData, 'countdown-part' )
			);
		}

		/**
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway
			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}

}
