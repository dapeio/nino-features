<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Status		The lines of the status of the core: what the panel of the workbench says is there and what is missing. A line is a key, a rank, a state - a method that answers
	 *							[ 'state' => ok | ..., and the values the sentence of that state needs ] - and, where the owner can mend it with one press, a fix. The core enters its own lines in init()
	 *							(database, writable, schema, addresses, mail, mail-head), and a package enters the ones it owns with add(): what the core asks of it is a list, never a name.
	 *							A line that is only a hint (a thing the owner may leave as it is) never keeps the product from being whole.
	 *
	 *							A state is a read: nothing here makes a database, a file or a mail, and a state that fails - an exception, an answer that is no list - is the line
	 *							'error' and nothing else breaks. A fix is run by name, for one line at a time, and answers true or the reason it did not.
	 *
	 *	@package						Dape/Nino
	 */
	class Status {

		/**	The key of the lines the packages and the core entered, in the app data of the request	*/
		private const string REGISTER = './crm/status/lines';

		/**	The states that are as they should be	*/
		public const array WHOLE = [ 'ok', 'own' ];

		/**	The templates of the band at the head of the mails, which the unit brings to the project	*/
		public const array HEAD = [ 'crm-head', 'crm-head-word', 'crm-head-domain', 'crm-head-sign' ];

		/**	How long a mail may wait in the outbox before the status says nothing delivers it, in seconds	*/
		private const int STUCK = 3600;

		/**
		 *	Enter a line of the status
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec					package (the key of the feature that enters it), key (lower case words joined by hyphens), rank (the order, 500 by default), state ([ class, method ]: ( array &$appData ): array), hint (a line that is only a hint; false) and fix (none, or [ class, method ]: ( array &$appData ): true|string)
		 *
		 *	@return 	bool											false, with a warning, where the description is refused - a key another line has, a callable that is none - and nothing is entered
		 */
		public static function add( array &$appData, array $spec ): bool {

			return self::enter( $appData, $spec, false );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *	@param		bool			$core					The core enters its own line
		 *
		 *	@return 	bool
		 */
		private static function enter( array &$appData, array $spec, bool $core ): bool {

			return Register::add( $appData, self::REGISTER, 'status line', [ $spec ], [
				'id' => 'key',
				'idRule' => static fn( string $key ): bool => Register::word( $key ),
				'fields' => [ 'package' => Register::REQUIRED, 'state' => Register::REQUIRED, 'rank' => 500, 'hint' => false, 'fix' => null ],
				'rules' => [
					'package' => Register::packageRule( $appData, $core ),
					'state' => static fn( mixed $state ): bool => Register::callable( $state ),
					'rank' => static fn( mixed $rank ): bool => is_int( $rank ),
					'hint' => static fn( mixed $hint ): bool => is_bool( $hint ),
					'fix' => static fn( mixed $fix ): bool => $fix === null || Register::callable( $fix ),
				],
			] );
		}

		/**
		 *	The lines of the core
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			foreach( [
				[ 'package' => Api::CORE, 'key' => 'database', 'rank' => 100, 'state' => [ self::class, 'database' ], 'fix' => [ self::class, 'fixDatabase' ] ],
				[ 'package' => Api::CORE, 'key' => 'writable', 'rank' => 110, 'state' => [ self::class, 'writable' ] ],
				[ 'package' => Api::CORE, 'key' => 'strays', 'rank' => 130, 'state' => [ Projects::class, 'strayState' ], 'fix' => [ Projects::class, 'sweepFix' ] ],
				[ 'package' => Api::CORE, 'key' => 'schema', 'rank' => 120, 'state' => [ self::class, 'schema' ], 'fix' => [ self::class, 'fixDatabase' ] ],
				[ 'package' => Api::CORE, 'key' => 'addresses', 'rank' => 200, 'state' => [ self::class, 'addresses' ], 'hint' => true ],
				[ 'package' => Api::CORE, 'key' => 'mail', 'rank' => 300, 'state' => [ self::class, 'mail' ] ],
				[ 'package' => Api::CORE, 'key' => 'mail-head', 'rank' => 310, 'state' => [ self::class, 'head' ] ],
			] as $line )
				self::enter( $appData, $line, true );
		}

		/**
		 *	The lines with their states, in the order of their rank and then of their entering
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											A list of key, hint, fixable, state and the values of the state
		 */
		public static function lines( array &$appData ): array {

			$lines = Register::all( $appData, self::REGISTER );
			uasort( $lines, static fn( array $a, array $b ): int => [ $a['rank'], $a[Register::SEQ] ] <=> [ $b['rank'], $b[Register::SEQ] ] );
			$answers = [];

			foreach( $lines as $key => $line ) {

				try {
					$state = call_user_func_array( $line['state'], [ &$appData ] );
				}
				catch( \Throwable $e ) {

					// A line that breaks is a line that says so; a failure that ends the request is not a state
					if( $e instanceof \Nino\Failure )
						throw $e;

					$state = [ 'state' => 'error' ];
				}

				$clean = self::clean( $state, (string) $key );
				// A line may say that its fix is no use now (the press would do what another line's press does)
				$fixable = $line['fix'] !== null && ( $clean['fixable'] ?? true ) !== false;
				unset( $clean['fixable'] );
				$answers[] = [ 'key' => (string) $key, 'hint' => $line['hint'], 'fixable' => $fixable ] + $clean;
			}

			// A package whose schema could not be raised in this request is a line of its own, red, with its name and the version it asked for: no fix of the core mends it
			foreach( Db::failed( $appData ) as $package => $version )
				$answers[] = [ 'key' => 'schema-'. $package, 'hint' => false, 'fixable' => false, 'state' => 'unraised', 'package' => (string) $package, 'version' => $version, 'label' => (string) $package ];

			return $answers;
		}

		/**
		 *	An answer of a state as the line has it: a state that is a word, and the values that are texts, numbers, switches or lists of those - nothing else is handed on
		 *
		 *	@param		mixed			$answer
		 *	@param		string		$key					For the warning
		 *
		 *	@return 	array
		 */
		private static function clean( mixed $answer, string $key ): array {

			if( is_array( $answer ) === false || Register::word( $answer['state'] ?? null ) === false ) {
				trigger_error( 'Crm: the state of the line '. $key. ' of the status is no state', E_USER_WARNING );

				return [ 'state' => 'error' ];
			}

			$clean = [ 'state' => $answer['state'] ];

			foreach( $answer as $name => $value ) {

				if( $name === 'state' || is_string( $name ) === false || preg_match( '/^[a-z][a-z0-9_]*$/D', $name ) !== 1 )
					continue;

				if( is_scalar( $value ) === true )
					$clean[$name] = $value;
				else if( is_array( $value ) === true && array_is_list( $value ) === true && array_filter( $value, static fn( mixed $one ): bool => is_scalar( $one ) === false ) === [] )
					$clean[$name] = $value;
			}

			return $clean;
		}

		/**
		 *	Whether the lines are all as they should be: a line that is a hint does not count
		 *
		 *	@param		array 		$lines				As lines() answers
		 *
		 *	@return 	bool
		 */
		public static function whole( array $lines ): bool {

			return array_filter( $lines, static fn( array $line ): bool => $line['hint'] === false && in_array( $line['state'], self::WHOLE, true ) === false ) === [];
		}

		/**
		 *	Mend one line: run its fix
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The line, by name - never every line at once
		 *
		 *	@return 	true|string								true where it was mended; else why not: unknown (no such line), none (the line has no fix), failed
		 */
		public static function fix( array &$appData, string $key ): true|string {

			$line = Register::all( $appData, self::REGISTER )[$key] ?? null;

			if( $line === null )
				return 'unknown';

			if( $line['fix'] === null )
				return 'none';

			try {
				$done = call_user_func_array( $line['fix'], [ &$appData ] );
			}
			catch( \Throwable $e ) {

				// A failure that ends the request is not a failure of the fix
				if( $e instanceof \Nino\Failure )
					throw $e;

				trigger_error( 'Crm: the line '. $key. ' of the status could not be mended', E_USER_WARNING );

				return 'failed';
			}

			// A reason the fix gives - a word - is the reason; anything else it does not mend is a failure
			return $done === true ? true : ( Register::word( $done ) === true ? $done : 'failed' );
		}

		// --- the lines of the core ---------------------------------------------------------------------------------------------------

		/**
		 *	The database: there and the core's
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, missing (nothing there yet, or an empty file), foreign (a file that is another database or none: it is never touched) busy (another process holds it locked) or damaged
		 */
		public static function database( array &$appData ): array {

			$state = match( Db::inspect( $appData )['file'] ) {
				'ours' => 'ok',
				'absent', 'empty' => 'missing',
				'foreign' => 'foreign',
				'busy' => 'busy',
				default => 'damaged',
			};

			// A file another process holds is no file to mend now: the press would wait for the lock as every write does
			return $state === 'busy' ? [ 'state' => $state, 'fixable' => false ] : [ 'state' => $state ];
		}

		/**
		 *	Whether the file can be written: the file and the directory it lies in, or - where there is no file yet - the nearest directory that exists
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok or unwritable
		 */
		public static function writable( array &$appData ): array {

			return [ 'state' => Db::inspect( $appData )['writable'] === true ? 'ok' : 'unwritable' ];
		}

		/**
		 *	The schemas of the file: the version of the core and, for every package that entered one, whether the file has it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, none (no database yet), behind (the file has an older version of something than the code), ahead (a newer one), foreign, busy or damaged; version and stored for the core, lacks the packages that are behind
		 */
		public static function schema( array &$appData ): array {

			$seen = Db::inspect( $appData );

			// The line of the database has the press for a file that is not there yet; this one has none for it
			if( in_array( $seen['file'], [ 'foreign', 'damaged', 'unreadable', 'busy' ], true ) === true )
				return [ 'state' => $seen['file'] === 'foreign' || $seen['file'] === 'busy' ? $seen['file'] : 'damaged', 'fixable' => false ];

			if( $seen['file'] !== 'ours' )
				return [ 'state' => 'none', 'version' => Db::VERSION, 'stored' => 0, 'lacks' => [], 'fixable' => false ];

			$lacks = [];
			$ahead = false;

			foreach( Db::versions( $appData ) as $key => $version ) {

				$stored = (int) ( $seen['stored'][$key]['version'] ?? 0 );

				if( $stored > $version )
					$ahead = true;
				else if( $stored < $version )
					$lacks[] = $key;
			}

			return [ 'state' => $ahead === true ? 'ahead' : ( $lacks === [] ? 'ok' : 'behind' ), 'version' => Db::VERSION, 'stored' => (int) ( $seen['stored'][Db::CORE]['version'] ?? 0 ), 'lacks' => $lacks ];
		}

		/**
		 *	The fix of the lines database and schema: make or raise the file, which is what the first use of the connection does - a file that is another database is no file to make
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	true|string
		 */
		public static function fixDatabase( array &$appData ): true|string {

			Db::ensure( $appData );

			return true;
		}

		/**
		 *	The addresses the workbench and the CRM answer under: none is no rule, one that is a name is as it should be, one that the site cannot use (not a host name, the website's own, the same as the other's) is not
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state default (none set), ok, invalid or busy (the database is locked); crm and app, the names
		 */
		public static function addresses( array &$appData ): array {

			$crm = Settings::readOrNull( $appData, Hosts::KEY_CRM );
			$app = Settings::readOrNull( $appData, Hosts::KEY_APP );

			// A database that is locked says nothing of the addresses: not that there are none
			if( $crm === null || $app === null )
				return [ 'state' => 'busy' ];

			$bad = ( $crm !== '' && ( Settings::validHost( $crm ) === false || Settings::isSiteHost( $appData, $crm ) === true ) )
				|| ( $app !== '' && Settings::validHost( $app ) === false ) || ( $crm !== '' && $crm === $app && Settings::mayShare( Hosts::KEY_CRM, Hosts::KEY_APP ) === false );

			return [ 'state' => $bad === true ? 'invalid' : ( $crm === '' && $app === '' ? 'default' : 'ok' ), 'crm' => $crm, 'app' => $app ];
		}

		/**
		 *	The outbox of the kernel: mails that were given up on and mails that wait for longer than an hour say that nothing delivers them
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, failed or stuck; waiting and failed, the counts
		 */
		public static function mail( array &$appData ): array {

			$queued = \Nino\Mail::queued( $appData );
			$oldest = is_string( $queued['oldest'] ?? null ) === true ? strtotime( $queued['oldest'] ) : false;
			$state = match( true ) {
				(int) $queued['failed'] > 0 => 'failed',
				(int) $queued['waiting'] > 0 && $oldest !== false && time() - $oldest > self::STUCK => 'stuck',
				default => 'ok',
			};

			return [ 'state' => $state, 'waiting' => (int) $queued['waiting'], 'failed' => (int) $queued['failed'] ];
		}

		/**
		 *	The band at the head of the mails: its four templates in the project, which the unit of the core brings add-only
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											state ok, partial or none; lacks the templates that are not there
		 */
		public static function head( array &$appData ): array {

			$lacks = array_values( array_filter( self::HEAD, static fn( string $name ): bool => \Nino\Filesystem::fileExists( $appData, Mail::TEMPLATES. '/'. $name. '.tpl' ) === false ) );

			return [ 'state' => $lacks === [] ? 'ok' : ( count( $lacks ) === count( self::HEAD ) ? 'none' : 'partial' ), 'lacks' => $lacks ];
		}
	}
}
