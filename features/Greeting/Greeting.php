<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	Nino features					The card the Dashboard of the workbench opens with: a formula
	 *												for the time of day with the account's name, a sentence picked
	 *												at random and a line of facts about the last visit. This class
	 *												is the server's half: it answers the callback the Dashboard
	 *												fires for the head of its summary with the facts, the switches
	 *												and the sentences, and nothing else - the words, the hour and
	 *												the dice are the script's (see assets/admin.js)
	 *
	 *												The three switches are keys of config.php, and the sentences
	 *												the elements of the type TYPE: a project that holds them
	 *												keeps them under these names
	 */
	class Greeting {

		// The element type the sentences are, and the words an element may carry in
		// its three fields that say when it fits
		public const string TYPE = '/greetings';

		// The three lines of the card and the key of config.php that switches each off, on until it is
		public const array LINES = [
			'hello' 		=> '/nino/admin/greeting/hello',
			'sentence' 	=> '/nino/admin/greeting/sentence',
			'facts' 		=> '/nino/admin/greeting/facts',
		];

		private const array PHASES 		= [ 'any', 'early', 'morning', 'noon', 'afternoon', 'evening', 'night' ];
		private const array DAYS 			= [ 'any', 'monday', 'friday', 'weekend' ];
		private const array OCCASIONS = [ 'none', 'christmas', 'newyear' ];

		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/dashboard/head', [ self::class, 'head' ] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Greeting\Admin::class ];
		}

		/**
		 *	The callback of the Dashboard's head: the area of the card, for the account that
		 *	asks - and none where all three lines are switched off
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$areas				(reference) The areas so far, name => data
		 *
		 *	@return 	array										The areas, the card among them
		 */
		public static function head( array &$appData, array &$areas ): array {

			$card = self::card( $appData );

			if( in_array( true, $card['show'], true ) === true )
				$areas['greeting'] = $card;

			return $areas;
		}

		/**
		 *	Whether a line is on: every one is until a project switched it off
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$line					One of the keys of LINES
		 *
		 *	@return 	bool
		 */
		public static function lineOn( array &$appData, string $line ): bool {
			return ( $appData[ self::LINES[$line] ] ?? true ) !== false;
		}

		/**
		 *	What the card is made of: the three switches, and for each line that is on the
		 *	facts it needs - the account's name, the time of the login before this one (see
		 *	\Nino\Auth, null where the session holds none) and, only where the module is
		 *	there and the account may see it, the number of log lines since that visit and
		 *	the days since the last backup. The sentences are the visible elements of the
		 *	type TYPE, in the interface language, each with when it fits. A line that is off
		 *	needs nothing: no sentence is read, no log counted
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										{ show, name, previousLogin, activitySince?, backupDays?, sentences? }
		 */
		public static function card( array &$appData ): array {

			$show = [];
			foreach( array_keys( self::LINES ) as $line )
				$show[$line] = self::lineOn( $appData, $line );

			$current 	= \Nino\Auth::getCurrentUser( $appData );
			$name 		= $current !== false && is_string( $current['name'] ?? null ) === true ? $current['name'] : '';
			$previous = \Nino\Runtime::getSessionValue( $appData, './nino/auth/previous', null );

			$card = [ 'show' => $show, 'name' => $name, 'previousLogin' => is_int( $previous ) === true ? $previous : null ];

			if( $show['sentence'] === true )
				$card['sentences'] = self::sentences( $appData );

			if( $show['facts'] === false )
				return $card;

			if( $current !== false && is_int( $previous ) === true && $previous > 0
				&& class_exists( '\\Nino\\Modules\\Logs\\Admin' ) === true
				&& \Nino\Auth::checkPermission( $appData, \Nino\Modules\Logs\Admin::VIEW_PERM ) === true )
				$card['activitySince'] = \Nino\Modules\Logs\Admin::countSince( $appData, $previous, (string) $current['mail'], \Nino\Auth::lastLogin( $current ) );

			$last 		= class_exists( '\\Nino\\Modules\\Backups' ) === true ? \Nino\Modules\Backups::lastDate( $appData ) : null;
			$backup 	= $last === null ? false : \DateTimeImmutable::createFromFormat( 'Y-m-d|', $last );

			if( $backup !== false )
				$card['backupDays'] = max( 0, (int) $backup->diff( new \DateTimeImmutable('today') )->format('%r%a') );

			return $card;
		}

		/**
		 *	The sentences of the card: every element of the type TYPE that is not hidden and
		 *	has a text, in the interface language (a language an element has no text in takes
		 *	the native one, as everywhere). The three fields that say when it fits are cut to
		 *	the words they may hold - a value a hand-edited file holds otherwise is 'any' or
		 *	'none'. The type is put in place first where the project has none and has not
		 *	deleted it (see seed())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ { text, phase, day, occasion }, ... ]
		 */
		public static function sentences( array &$appData ): array {

			// A type that is not there is told by its file: asking the Elements for a type of no
			// file is a warning each time the Dashboard opens, and a project that deleted it has none
			if( \Nino\Filesystem::fileExists( $appData, '/elements/'. trim( self::TYPE, '/' ). '.php' ) === false
				&& ( isset( $appData[ \Nino\Elements::REMOVED ][ trim( self::TYPE, '/' ) ] ) === true
					|| self::seed( $appData, \Nino\Locales::getAvailableLocales( $appData ) ) !== true ) )
				return [];

			$elements = \Nino\Elements::queryElements( $appData, self::TYPE, [], '*', [] );

			$locale = \Nino\Admin\Admin::sessionLocale( $appData );
			$list 	= [];

			foreach( $elements as $element ) {

				$full = is_array( $element ) === true && is_string( $element['.uri'] ?? null ) === true ? \Nino\Elements::getElementWithFallback( $appData, $element['.uri'], $locale ) : false;

				if( $full === false || ( $full['hidden'] ?? false ) === true || is_string( $full['text'] ?? null ) === false || trim( $full['text'] ) === '' )
					continue;

				$list[] = [
					'text' 			=> $full['text'],
					'phase' 		=> in_array( $full['phase'] ?? null, self::PHASES, true ) === true ? $full['phase'] : 'any',
					'day' 			=> in_array( $full['day'] ?? null, self::DAYS, true ) === true ? $full['day'] : 'any',
					'occasion' 	=> in_array( $full['occasion'] ?? null, self::OCCASIONS, true ) === true ? $full['occasion'] : 'none',
				];
			}

			return $list;
		}

		/**
		 *	Put the type TYPE and its sentences in place: what the unit file of the feature
		 *	brings, added to the project and never replacing anything of it (see
		 *	\Nino\Elements::seed()). Done by the first request of the Dashboard that finds the
		 *	project without the type and without a note in `/nino/elements/removed` that it
		 *	was deleted (see \Nino\Modules\Elements\Types::apiDelete()); a sentence an editor
		 *	changed, hid or deleted is not put back, because the type is only seeded where it
		 *	is not there
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$locales			The languages to bring the sentences in
		 *
		 *	@return 	true|string							true, or why it failed
		 */
		public static function seed( array &$appData, array $locales ): true|string {

			$content = include __DIR__. '/install/elements/greetings.php';

			return is_array( $content ) === true ? \Nino\Elements::seed( $appData, self::TYPE, $content, $locales ) : 'could not read the greetings';
		}
	}
}
