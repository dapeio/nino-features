<?php
declare(strict_types=1);

namespace Nino\Modules {

	/**
	 *	Nino features					Sysadmin: a mail to the person who looks after the site - a problem mail when
	 *												the report (see \Nino\Modules\Sysadmin\Report) finds something that was not there
	 *												before, and a sign of life at a fixed rhythm - set off by a page request.
	 *
	 *												A request pays for a glance: the recipients of the settings and one read of the
	 *												state file, a time against a time. What is due is worked out, and sent, after
	 *												the response has gone out (see run()), under a lock that a second request does
	 *												not wait for - so two requests at once are one mail, and nobody who asks for a
	 *												page waits for the report.
	 *
	 *												Two clocks. The problem clock looks at the report no oftener than CHECK_EVERY
	 *												and never sooner than the distance of the settings after the last problem mail;
	 *												a problem mail goes out when a finding is new - its key was not in the report that
	 *												was told last - or has become worse - its rank rose. A finding that holds is told
	 *												once and stands in the next sign of life; one that went and comes back is new.
	 *												What happened since the last mail (the errors of the log) is an event, news every
	 *												time. The beat clock is the rhythm of the settings, counted from a fixed slot and
	 *												not from the moment the mail went. A send that failed is noted and tried again
	 *												after RETRY, never with every request
	 */
	class Sysadmin {

		// What the feature keeps between two requests: only times, keys of findings and counts
		public const string STATE = '/data/sysadmin.php';

		// Held by the run, never waited for: the request that finds it taken has nothing to do
		public const string LOCK = '/data/sysadmin-run';

		// The problem clock looks at the report this often at most, in seconds
		public const int CHECK_EVERY = 3600;

		// A send that failed - or a run that was cut short - is not tried again before this
		public const int RETRY = 3600;

		// The whole run - the report and the mails - ends after this many seconds
		public const int RUN_LIMIT = 60;

		// The test mail of the panel, at most this often
		public const int TEST_EVERY = 60;

		// How many addresses a mail goes to
		public const int RECIPIENTS = 5;

		private const array BEATS = [ 'daily' => '+1 day', 'weekly' => '+1 week', 'monthly' => '+1 month' ];

		private const array GAPS = [ 1, 6, 24, 72, 168 ];

		public static function init( array &$appData ): void {

			// The cost of a request that has nothing to do: the stored settings, which are in memory - read as they are
			// stored, since \Nino\Features::setting() reads every manifest of the catalogue on the first call of a request, which
			// is more than a page may pay - and where there is an address, one read of a small file. What is stored is
			// checked where it is used: recipients() asks the kernel, and the send takes only addresses
			if( trim( (string) self::stored( $appData, 'to', '' ) ) === '' )
				return;

			if( self::due( $appData, self::state( $appData ), self::now( $appData ) ) === false )
				return;

			// Tests, and a server that cannot let the response go before the script ends, run it here
			if( ( $appData['./sysadmin/inline'] ?? false ) === true ) {
				self::run( $appData );
				return;
			}

			// After the response is out, where the kernel can let the client go (see \Nino\Http::afterResponse())
			\Nino\Http::afterResponse( $appData, static function() use ( &$appData ): void {
				self::run( $appData );
			} );
		}

		/**
		 *	A setting as the config holds it, unchecked: for the one place that must not read the manifests (see init())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name
		 *	@param		mixed			$default
		 *
		 *	@return 	mixed
		 */
		public static function stored( array &$appData, string $name, mixed $default ): mixed {

			$settings = $appData['/nino/features']['sysadmin']['settings'] ?? null;

			return is_array( $settings ) === true && array_key_exists( $name, $settings ) === true ? $settings[$name] : $default;
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Sysadmin\Admin::class ];
		}

		/**
		 *	The clock: now, or what a test has set
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function now( array &$appData ): int {

			return is_int( $appData['./sysadmin/now'] ?? null ) === true ? $appData['./sysadmin/now'] : time();
		}

		/**
		 *	Who is told: the addresses of the setting that are addresses, at most RECIPIENTS
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										A list of addresses
		 */
		public static function recipients( array &$appData ): array {

			$list = [];

			foreach( explode( ',', (string) \Nino\Features::setting( $appData, 'sysadmin', 'to', '' ) ) as $address ) {

				$address = trim( $address );

				if( $address !== '' && filter_var( $address, FILTER_VALIDATE_EMAIL ) !== false && isset( $list[ strtolower( $address ) ] ) === false )
					$list[ strtolower( $address ) ] = $address;
			}

			return array_slice( array_values( $list ), 0, self::RECIPIENTS );
		}

		/**
		 *	The distance of two problem mails, in seconds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function gap( array &$appData ): int {

			$hours = (int) self::stored( $appData, 'problemGap', 24 );

			return ( in_array( $hours, self::GAPS, true ) === true ? $hours : 24 ) * 3600;
		}

		/**
		 *	The rhythm of the sign of life
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										'off', 'daily', 'weekly' or 'monthly'
		 */
		public static function beat( array &$appData ): string {

			$beat = (string) self::stored( $appData, 'beat', 'weekly' );

			return $beat === 'off' || isset( self::BEATS[$beat] ) === true ? $beat : 'weekly';
		}

		/**
		 *	What the feature remembers, with every key present
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										{ since, lastEval, lastProblem, lastBeat, lastMail, reported: key => rank, try, failed, fails, aborts, tested, skewed }; [] before the first run
		 */
		public static function state( array &$appData ): array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::STATE, [] );

			if( is_array( $stored ) === false || is_int( $stored['since'] ?? null ) === false )
				return [];

			$reported = [];

			foreach( is_array( $stored['reported'] ?? null ) === true ? $stored['reported'] : [] as $key => $rank )
				if( is_string( $key ) === true && is_int( $rank ) === true )
					$reported[$key] = $rank;

			$state = [ 'reported' => $reported ];

			$now = self::now( $appData );

			// A clock that jumped forward and back leaves times in the future: they are now, so that nothing waits for a time that
			// is not coming for as long as the jump was
			foreach( [ 'since', 'lastEval', 'lastProblem', 'lastBeat', 'lastMail', 'try', 'failed', 'tested' ] as $name )
				$state[$name] = is_int( $stored[$name] ?? null ) === true ? min( $stored[$name], $now ) : 0;

			// A time of the file that is after now: the clock went forward and back. The run writes them as now (see run())
			$state['skewed'] = count( array_filter( [ 'since', 'lastEval', 'lastProblem', 'lastBeat', 'lastMail', 'try', 'failed', 'tested' ], static fn( string $name ): bool => is_int( $stored[$name] ?? null ) === true && $stored[$name] > $now ) ) > 0;

			// Counts, which no clock changes
			foreach( [ 'fails', 'aborts' ] as $name )
				$state[$name] = is_int( $stored[$name] ?? null ) === true ? $stored[$name] : 0;

			// Who has the mail of a run that failed halfway, and of which kind it was
			$delivered = is_array( $stored['delivered'] ?? null ) === true ? $stored['delivered'] : [];
			$state['delivered'] = [ 'kind' => is_string( $delivered['kind'] ?? null ) === true ? $delivered['kind'] : '', 'to' => array_values( array_filter( (array) ( $delivered['to'] ?? [] ), 'is_string' ) ) ];

			return $state;
		}

		/**
		 *	How many runs ended without a word: those counted, and the one whose note has stood for longer than a run may take
		 *	- an attempt that was written and never ended, and is no send that failed (that one says so in 'failed')
		 *
		 *	@param		array 		$state				state()
		 *	@param		int			$now
		 *
		 *	@return 	int
		 */
		public static function aborted( array $state, int $now ): int {

			if( $state === [] )
				return 0;

			return $state['aborts'] + ( $state['try'] > 0 && $state['failed'] !== $state['try'] && $now >= $state['try'] + self::RETRY ? 1 : 0 );
		}

		/**
		 *	When the sign of life is due next: one rhythm after the slot of the last one
		 *
		 *	@param		string		$beat					'daily', 'weekly' or 'monthly'
		 *	@param		int			$slot					The slot of the last sign of life
		 *
		 *	@return 	int
		 */
		public static function after( string $beat, int $slot ): int {

			return (int) ( new \DateTimeImmutable( '@'. $slot ) )->setTimezone( new \DateTimeZone( date_default_timezone_get() ) )->modify( self::BEATS[$beat] ?? '+1 week' )->format( 'U' );
		}

		/**
		 *	The latest slot of the rhythm that is not in the future: where a sign of life that went out now
		 *	stands, so that one that comes late is no later for the next
		 *
		 *	@param		string		$beat
		 *	@param		int			$slot					A slot
		 *	@param		int			$now
		 *
		 *	@return 	int
		 */
		public static function slot( string $beat, int $slot, int $now ): int {

			// A site that was without a visit for years makes one mail and no backlog; the loop is bounded all the same
			for( $step = 0; $step < 5000; $step++ ) {

				$next = self::after( $beat, $slot );

				if( $next > $now )
					break;

				$slot = $next;
			}

			return $slot;
		}

		/**
		 *	Whether the problem clock or the beat clock has run out, or the state is not there yet. A run
		 *	that began or failed lately holds both back (see RETRY)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$state				state()
		 *	@param		int			$now
		 *
		 *	@return 	bool
		 */
		public static function due( array &$appData, array $state, int $now ): bool {

			if( $state === [] || $state['skewed'] === true )
				return true;

			if( $state['try'] > 0 && $now < $state['try'] + self::RETRY )
				return false;

			return self::problemDue( $appData, $state, $now ) || self::beatDue( $appData, $state, $now );
		}

		public static function problemDue( array &$appData, array $state, int $now ): bool {

			return $now >= max( $state['lastProblem'] + self::gap( $appData ), $state['lastEval'] + self::CHECK_EVERY );
		}

		public static function beatDue( array &$appData, array $state, int $now ): bool {

			$beat = self::beat( $appData );

			return $beat !== 'off' && $now >= self::after( $beat, $state['lastBeat'] );
		}

		/**
		 *	What the run does, after the response: take the lock, look again, and make what is due
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									What came of it: 'none' (nothing to do or somebody else is at it), 'checked' (looked, nothing to tell), 'problem', 'beat' or 'failed'
		 */
		public static function run( array &$appData ): string {

			$recipients = self::recipients( $appData );
			$locked = false;

			if( $recipients === [] )
				return 'none';

			try {

				// Not waited for: the one that holds it is making the mail
				$locked = \Nino\Filesystem::lockFile( $appData, self::LOCK, false );

				if( $locked === false )
					return 'none';

				$now = self::now( $appData );

				// Under the lock the state is read again: the request that had it before has written it
				$state = self::state( $appData );

				if( $state === [] ) {

					if( self::_write( $appData, static fn( array $state ): array => [ 'since' => $now, 'lastBeat' => $now ] ) === false )
						return 'none';

					$state = self::state( $appData );
				}

				// Times after now, from a clock that went forward and back, are written as now: a sign of life would wait for them
				if( $state['skewed'] === true ) {

					$capped = $state;

					if( self::_write( $appData, static fn( array $stored ): array => array_diff_key( $capped, [ 'skewed' => 1, 'reported' => 1 ] ) + $stored ) === false )
						return 'none';

					$state = self::state( $appData );
				}

				if( self::due( $appData, $state, $now ) === false )
					return 'none';

				$problemDue	= self::problemDue( $appData, $state, $now );
				$beatDue		= self::beatDue( $appData, $state, $now );

				// The attempt is on disk before it is made: a run that dies in it - a time limit, a memory limit - costs
				// the one request, and the next one waits RETRY like after a failure. Where it cannot be written - the disk is
				// full, the file is not the feature's to write - nothing is collected and nothing is sent: a run that cannot
				// remember that it ran would run again with every request
				$aborts = self::aborted( $state, $now );

				if( self::_write( $appData, static fn( array $state ): array => [ 'try' => $now, 'aborts' => $aborts ] + $state ) === false )
					return 'none';

				// A limit of the run as a whole. It does not bound the wait for a socket - the time of a connection is the mailer's own -
				// the protection against a mail server that does not answer is that the sending ends at the first address that fails
				@set_time_limit( self::RUN_LIMIT );

				$since		= $state['lastMail'] > 0 ? $state['lastMail'] : $state['since'];
				$report		= self::inNative( $appData, function( string $native ) use ( &$appData, $since, $now, $aborts ): array { return \Nino\Modules\Sysadmin\Report::collect( $appData, $native, $since, $now, true, $aborts ); } );
				$news			= self::news( $report['findings'], $state['reported'] );
				$problem	= $problemDue === true && $news !== [] && $now >= $state['lastProblem'] + self::gap( $appData );
				$kind			= $problem === true ? 'problem' : ( $beatDue === true ? 'beat' : '' );
				$told			= self::told( $report['findings'] );

				if( $kind === '' ) {

					// Looked, and nothing to say: what has gone is gone, so that it is new when it comes back
					self::_write( $appData, static fn( array $state ): array => [ 'lastEval' => $now, 'reported' => $told, 'try' => 0 ] + $state );
					return 'checked';
				}

				$mail = self::inNative( $appData, function( string $native ) use ( &$appData, $kind, $report, $news, $state, $now ): array { return self::compose( $appData, $native, $kind, $report, array_keys( $news ), $state, $now ); } );

				$have = $state['delivered']['kind'] === $kind ? $state['delivered']['to'] : [];

				if( self::send( $appData, $recipients, $mail, $have ) === false ) {

					$delivered = [ 'kind' => $kind, 'to' => array_values( array_unique( (array) ( $appData['./sysadmin/delivered'] ?? [] ) ) ) ];

					self::_write( $appData, static fn( array $state ): array => [ 'failed' => $now, 'fails' => $state['fails'] + 1, 'try' => $now, 'delivered' => $delivered ] + $state );
					return 'failed';
				}

				$beat = self::beat( $appData );

				self::_write( $appData, static fn( array $state ): array => [
					'lastEval'		=> $now,
					'lastMail'		=> $now,
					'lastProblem'	=> $kind === 'problem' ? $now : $state['lastProblem'],
					// A problem mail says all there is to say: where the sign of life was due as well, it is this one
					'lastBeat'		=> $beatDue === true && $beat !== 'off' ? self::slot( $beat, $state['lastBeat'], $now ) : $state['lastBeat'],
					'reported'		=> $told,
					'try'					=> 0,
					'failed'			=> 0,
					'fails'				=> 0,
					'aborts'			=> 0,
					'delivered'		=> [ 'kind' => '', 'to' => [] ],
				] + $state );

				return $kind;

			} catch( \Throwable $error ) {

				// Not thrown: the page that set it off is long gone, and a report that cannot be made must not be an error of every request
				trigger_error( 'Sysadmin: '. get_class( $error ). ' - '. $error->getMessage(), E_USER_WARNING );

				return 'failed';

			} finally {

				if( $locked === true )
					\Nino\Filesystem::unlockFile( $appData, self::LOCK );
			}
		}

		/**
		 *	Of the findings, those the last report did not tell: a key that was not in it, a rank that is higher
		 *	than the one it had, or an event
		 *
		 *	@param		array 		$findings			Report::collect()'s
		 *	@param		array 		$reported			key => rank of the report that was told last
		 *
		 *	@return 	array										key => rank of the new ones
		 */
		public static function news( array $findings, array $reported ): array {

			$news = [];

			foreach( $findings as $finding )
				if( $finding['event'] === true || isset( $reported[ $finding['key'] ] ) === false || $finding['rank'] > $reported[ $finding['key'] ] )
					$news[ $finding['key'] ] = $finding['rank'];

			return $news;
		}

		/**
		 *	What a report that is told leaves in the state: the conditions with their rank, and no event
		 *
		 *	@param		array 		$findings
		 *
		 *	@return 	array										key => rank
		 */
		public static function told( array $findings ): array {

			$told = [];

			foreach( $findings as $finding )
				if( $finding['event'] === false )
					$told[ $finding['key'] ] = $finding['rank'];

			return $told;
		}

		/**
		 *	The mail: its subject and its text, in a language, from the words of the project
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale
		 *	@param		string		$kind					'problem', 'beat' or 'test'
		 *	@param		array 		$report				Report::collect()'s
		 *	@param		array 		$new					The keys of the findings that are new
		 *	@param		array 		$state				state()
		 *	@param		int			$now
		 *
		 *	@return 	array										{ subject, text, body }: the body is the html the mail is sent in
		 */
		public static function compose( array &$appData, string $locale, string $kind, array $report, array $new, array $state, int $now ): array {

			$words	= \Nino\Html::getFills( $appData, $locale );
			$say		= static fn( string $name, array $values = [] ): string => \Nino\Modules\Sysadmin\Report::say( $words, $name, $values );
			$facts	= $report['facts'];
			$site		= self::site( $appData );
			$lines	= [];

			$lines[] = $site. ' - '. $say( 'mail/kind-'. $kind ). ' - '. \Nino\Modules\Sysadmin\Report::stamp( $now ). date( ' T', $now );
			$lines[] = '';

			// The findings: rank and sentence, and which of them the last mail did not have
			$lines[] = $say( 'mail/findings', [ (string) count( $report['findings'] ) ] );

			foreach( $report['findings'] as $finding )
				$lines[] = '  '. $say( 'rank/'. \Nino\Modules\Sysadmin\Report::RANK_WORDS[ $finding['rank'] ] ). ( in_array( $finding['key'], $new, true ) === true && $kind !== 'test' ? ' ('. $say( 'mail/new' ). ')' : '' ). ': '. $finding['text'];

			if( $report['findings'] === [] )
				$lines[] = '  '. $say( 'mail/none' );

			// The backup
			$lines[] = '';
			$lines[] = $say( 'mail/backup' );

			if( $facts['backup']['on'] === false )
				$lines[] = '  '. $say( 'backup/off' );
			else if( $facts['backup']['date'] === null )
				$lines[] = '  '. $say( 'backup/never' );
			else
				$lines[] = '  '. $say( 'backup/last', [ $facts['backup']['date'], (string) $facts['backup']['days'], $facts['backup']['bytes'] === null ? '-' : \Nino\Modules\Sysadmin\Report::size( $appData, $facts['backup']['bytes'] ) ] );

			if( $facts['backup']['failed'] !== null )
				$lines[] = '  '. $say( 'backup/failed', [ $facts['backup']['failed'] !== '' ? $facts['backup']['failed'] : $say( 'word/silent' ) ] );

			if( $facts['backup']['stopped'] !== null )
				$lines[] = '  '. $say( 'backup/stopped', [ $facts['backup']['stopped']['started'], $say( 'cause/'. ( $facts['backup']['stopped']['cause'] !== '' ? $facts['backup']['stopped']['cause'] : 'none' ) ) ] );

			// The log since the last mail
			$since = ( $state['lastMail'] ?? 0 ) > 0 ? $state['lastMail'] : ( $state['since'] ?? $now );

			$lines[] = '';
			$lines[] = $say( 'mail/log', [ \Nino\Modules\Sysadmin\Report::stamp( $since ) ] );
			$lines[] = '  '. $say( 'log/counts', [ (string) $facts['log']['errors'], (string) $facts['log']['warnings'] ] );

			foreach( $facts['log']['top'] as $top )
				$lines[] = '  '. $top['count']. 'x '. $top['text'];

			// What the kernel counts about the operation
			$lines[] = '';
			$lines[] = $say( 'mail/operation' );
			$lines[] = '  '. ( $facts['mail'] === null ? $say( 'operation/mail-ok' ) : $say( 'operation/mail-failing', [ $facts['mail']['since'], (string) $facts['mail']['count'] ] ) );
			$lines[] = '  '. ( $facts['cap'] === '' ? $say( 'operation/cap-ok' ) : $say( 'operation/cap-'. $facts['cap'] ) );
			$lines[] = '  '. $say( 'operation/locked', [ (string) $facts['locked'] ] );
			$lines[] = '  '. ( $facts['refused'] === [] ? $say( 'operation/refused-none' ) : $say( 'operation/refused', [ implode( ', ', array_keys( $facts['refused'] ) ) ] ) );

			if( ( $report['facts']['aborted'] ?? 0 ) > 0 )
				$lines[] = '  '. $say( 'operation/aborted', [ (string) $report['facts']['aborted'] ] );

			if( ( $state['failed'] ?? 0 ) > 0 )
				$lines[] = '  '. $say( 'operation/send-failed', [ \Nino\Modules\Sysadmin\Report::stamp( $state['failed'] ), (string) $state['fails'] ] );

			// The machine
			$lines[] = '';
			$lines[] = $say( 'mail/server' );

			if( $facts['disk'] !== null )
				$lines[] = '  '. $say( 'server/disk', [ \Nino\Modules\Sysadmin\Report::size( $appData, $facts['disk']['free'] ), \Nino\Modules\Sysadmin\Report::size( $appData, $facts['disk']['total'] ), \Nino\Modules\Sysadmin\Report::percent( $facts['disk']['free'], $facts['disk']['total'] ) ] );

			if( $facts['data'] !== null )
				$lines[] = '  '. $say( 'server/data', [ \Nino\Modules\Sysadmin\Report::size( $appData, $facts['data'] ) ] );

			$lines[] = '  '. $say( 'server/versions', [ $facts['php'], $facts['nino'] ] );

			$link = \Nino\Html::absoluteUrl( $appData, '/_admin/' );

			// An address that is no address - the project wrote a line break or a tag into its own - is no link
			if( preg_match( '#^https?://[^\s<>"]+$#', $link ) === 1 ) {
				$lines[] = '';
				$lines[] = $say( 'mail/workbench' ). ' '. $link;
			}

			$text = implode( "\n", $lines );
			$count = count( $new );

			return [
				'subject'	=> $say( 'mail/subject-'. $kind, [ $site, (string) $count ] ),
				'text'		=> $text,
				'body'		=> str_replace( '[[text]]', \Nino\Components::escape( $text ), \Nino\Feature::template( $appData, '/features/Sysadmin/templates', 'sysadmin-mail' ) ),
			];
		}

		/**
		 *	The name of the site in a subject and the head of a mail: its address where the project has one, else
		 *	the name of the company, else Nino. Never the Host header of the request that set it off, which a
		 *	visitor chooses
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function site( array &$appData ): string {

			foreach( [ '/project/website/general/url', '/project/company/general/name' ] as $key ) {

				$value = trim( (string) \Nino\Html::resolveTextfill( $appData, $key ) );

				if( $value !== '' )
					return trim( (string) preg_replace( '/[\x00-\x1F\x7F\s]+/u', ' ', strip_tags( $value ) ), " /\t" );
			}

			return 'Nino';
		}

		/**
		 *	Send a composed mail to everybody on the list, one call of the kernel's mail for each as a mail of the
		 *	system: a refusal of its cap, a transport that failed and an address nobody takes end the sending
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$recipients
		 *	@param		array 		$mail					compose()'s
		 *
		 *	@param		array 		$have					The addresses that have this mail already, from a run that failed halfway
		 *	@param		string		$kind					SYSTEM or TEST, see \Nino\Mail::sendSystem()
		 *
		 *	@return 	bool										Whether it went to every address; who has it is in './sysadmin/delivered'
		 */
		public static function send( array &$appData, array $recipients, array $mail, array $have = [], string $kind = \Nino\Mail::SYSTEM ): bool {

			$mails = [];

			foreach( $recipients as $address )
				if( in_array( $address, $have, true ) === false )
					$mails[] = [ 'to' => $address, 'subject' => $mail['subject'], 'body' => $mail['body'], 'replyTo' => '' ];

			// One unit of the cap of the system's mails for the run, all addresses in it; the first that does not take the mail ends it
			$done = \Nino\Mail::sendSystem( $appData, $mails, $kind );

			// Who has it by now, for the run that tries again
			$appData['./sysadmin/delivered'] = $have;

			foreach( (array) ( $appData['./nino/mail/results'] ?? [] ) as $index => $delivered )
				if( $delivered === true )
					$appData['./sysadmin/delivered'][] = $mails[$index]['to'];

			return $done;
		}


		/**
		 *	Read-modify-write of the state, always with every key of it present
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		callable	$change				fn( array $state ): array - what to change, as the keys that are new; the rest stays
		 *
		 *	@return 	bool
		 */
		private static function _write( array &$appData, callable $change ): bool {

			return \Nino\Filesystem::mutate( $appData, self::STATE, static function( mixed $stored ) use ( $change ): array {

				$state = is_array( $stored ) === true ? $stored : [];

				return array_merge( [ 'format' => 1 ], $state, $change( self::_normal( $state ) ) );
			} );
		}

		/**
		 *	The state as stored, with a number for every key that names one
		 *
		 *	@param		array 		$stored
		 *
		 *	@return 	array
		 */
		private static function _normal( array $stored ): array {

			$state = [ 'reported' => is_array( $stored['reported'] ?? null ) === true ? $stored['reported'] : [] ];

			foreach( [ 'since', 'lastEval', 'lastProblem', 'lastBeat', 'lastMail', 'try', 'failed', 'fails', 'aborts', 'tested' ] as $name )
				$state[$name] = is_int( $stored[$name] ?? null ) === true ? $stored[$name] : 0;

			return $state;
		}

		/**
		 *	Run something in the native language of the site, and put the language back: the mail is written in it
		 *	whoever set it off - a visitor in English, an account of the workbench in another language - since a
		 *	mail that changed its language with the person who happened to load the page could not be read side by side
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		callable	$fn						fn( string $locale ): mixed
		 *
		 *	@return 	mixed											What $fn answered
		 */
		public static function inNative( array &$appData, callable $fn ): mixed {

			$native		= \Nino\Locales::getNativeLocale( $appData );
			$current	= \Nino\Locales::getCurrentLocale( $appData );

			\Nino\Locales::useLocale( $appData, $native );

			try {
				return $fn( $native );
			} finally {
				\Nino\Locales::useLocale( $appData, $current );
			}
		}
	}
}
