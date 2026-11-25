<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Settings		The settings of the core in the table settings, with their defaults and the rules of every key. get() answers the value a key has, set() writes one that
	 *							passes its rule. A rule that fails is a string: 'unknown' (no such key), 'value:<key>' (the value is none of the key's) or 'format:<key>' (a number format
	 *							that does not hold the parts a format needs). The keys of the core are DEFAULTS: the start numbers of the clients and of the projects (start_client, start_project;
	 *							whole numbers from 1 with at most seven digits: the least number the next client and the next project get), the name of the owner (seller_name), the wordmark
	 *							of the mails (brand_word, brand_domain, brand_color, brand_ink: the band of Mail draws it) and the hosts the CRM and the workbench are served under (crm_host
	 *							and app_host: lower case DNS names, empty for none; validHost(), isSiteHost()). The keys of a package are its own: it enters them with add() in its init(), with
	 *							the default, the rule, whether the module keeps the key itself (read-only), whether it is a host and what is done after it was written - and a key it did not
	 *							enter is none (get() throws, set() answers 'unknown', all() does not hold it). add() refuses a key of the core and a key another package has (false, with a
	 *							warning) and takes the same list again as good as the first time. set() tells what it wrote to whoever keeps a copy of a setting - the hook
	 *							'/crm/settings/changed' with the key and the value -, and view() asks the hook '/crm/settings/view' for what the packages show of themselves in the settings.
	 *							The rules a package shares with the core are note(), line(), flag(), oneOf(), host() and validHost(). ownerName() is the name the portal and the mails call
	 *							the owner by, and readOnly() reads one setting through a connection that only reads and never makes a database - the one thing of it a page of a visitor
	 *							asks for. A database failure is a \PDOException the caller answers.
	 *
	 *	@package						Dape/Nino
	 */
	class Settings {

		/**	The keys of the core, with their defaults; the packages enter the keys of their own with add()	*/
		public const array DEFAULTS = [
			'start_client'  => '1',
			'start_project' => '1',
			'seller_name'   => '',
			'brand_word'    => '',
			'brand_domain'  => '',
			'brand_color'   => '',
			'brand_ink'     => '',
			'crm_host'      => '',
			'app_host'      => '',
		];

		/**	The code the API answers with for a value of a key of the core that fails, where the key has one of its own: the wordmark of the mails (the first line, the second, the colour of the first and of the second)	*/
		public const array REFUSALS = [ 'brand_word' => 'crm_brand_word', 'brand_domain' => 'crm_brand_domain', 'brand_color' => 'crm_brand_color', 'brand_ink' => 'crm_brand_color' ];

		/**	The wordmark of the mails: the longest first line, the longest second line, and the shape of a colour (empty is the colour of the portal, or the grey of the second line)	*/
		public const int MAX_BRAND_WORD = 24;
		public const int MAX_BRAND_DOMAIN = 40;
		public const string COLOR_SHAPE = '/^#[0-9A-Fa-f]{6}$/D';

		/**	The key of the keys the packages entered, in the app data of the request	*/
		private const string REGISTER = './crm/settings/keys';

		/**	The connections of the reads that only read, by file, and the longest wait a read by file has failed after in this request	*/
		private const string READERS = './crm/settings/reader';
		private const string TRIED = './crm/settings/tried';

		/**	The longest name of the owner, and how long a read that only reads waits for a locked database, in milliseconds	*/
		private const int OWNER_MAX = 120;
		private const int SELLER_WAIT = 200;

		/**	The greatest start number of the clients and of the projects: seven digits, so that a client's number (K- and its digits) stays as short as the field of a project that names it	*/
		public const int MAX_START_ID = 9999999;
		public const int MAX_MESSAGE = 2000;

		/**	The longest name of the seller	*/
		public const int MAX_SELLER = 120;

		/**	The shape of a name a host setting may hold: at least two labels of letters, digits and hyphens, the last one beginning with a letter (an address of the numbers is none); lower case, ASCII (a name with other characters is written as punycode)	*/
		public const string HOST_NAME = '/^(?=.{4,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/D';

		/**
		 *	Enter the settings of a package: the keys it owns, each with its default, its rule and where it stands among the others. The core names none of them; a key that is not entered is none.
		 *	The description of a key: package (the key of the feature that enters it, see Register::package()), key (lower case words of letters and digits joined by underscores, at most 40 characters), default (a string), clean (what checks a value: a callable ( string $key, string $value ): string|array that
		 *	gets the value as text, trimmed, and answers the text to store, or [ 'value:<key>' ] or [ 'format:<key>' ] for a value that fails; none for a read_only key), read_only (the module keeps the key itself, a post never
		 *	writes it; false), host (the key holds a host: it may be neither the website's nor another host key's; false), written (a callable ( array &$appData, string $value, bool $changed ): void that is called after the key was
		 *	written and before the hook '/crm/settings/changed' is told; none), refusal (the code the API of the settings answers with for a value that fails; none: the one it has for all), before (a key of the core: the key stands
		 *	in front of it in all(); none: at the end) and rank (the order of the keys that stand in front of the same key, 500 by default, then the order they were entered in). A key of the core, a key another package has, a key
		 *	with a description that is none or with a field the description does not have, and a callable that is none are refused, and none of the list is entered then. The same list again is as good as the first time. Callables are given as [ class, method ]
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$keys					A list of descriptions
		 *
		 *	@return 	bool											false, with a warning, where a description is refused
		 */
		public static function add( array &$appData, array $keys ): bool {

			$added = Register::add( $appData, self::REGISTER, 'settings', $keys, [
				'id' => 'key',
				'idRule' => static fn( string $key ): bool => Register::word( $key, 40, '_' ),
				'fields' => [ 'package' => Register::REQUIRED, 'default' => Register::REQUIRED, 'clean' => null, 'read_only' => false, 'host' => false, 'written' => null, 'refusal' => null, 'before' => null, 'rank' => 500 ],
				'rules' => [
					'package' => Register::packageRule( $appData ),
					'default' => static fn( mixed $default ): bool => is_string( $default ) === true,
					'read_only' => static fn( mixed $flag ): bool => is_bool( $flag ),
					'host' => static fn( mixed $flag ): bool => is_bool( $flag ),
					'clean' => static fn( mixed $clean ): bool => $clean === null || Register::callable( $clean ),
					'written' => static fn( mixed $written ): bool => $written === null || Register::callable( $written ),
					'refusal' => static fn( mixed $code ): bool => $code === null || ( is_string( $code ) === true && preg_match( '/^[a-z][a-z0-9_]*$/D', $code ) === 1 ),
					'before' => static fn( mixed $before ): bool => $before === null || ( is_string( $before ) === true && isset( self::DEFAULTS[$before] ) === true ),
					'rank' => static fn( mixed $rank ): bool => is_int( $rank ),
				],
				'refuse' => static fn( array $spec ): string => match( true ) {
					isset( self::DEFAULTS[$spec['key']] ) === true => 'key of the core',
					$spec['read_only'] === false && $spec['clean'] === null => 'clean',
					default => '',
				},
			] );

			// What was read for the request is read again with the keys that came
			if( $added === true )
				Db::drop( $appData, 'settings' );

			return $added;
		}

		/**
		 *	The keys with their defaults: the keys of the core, and the keys of the packages each in front of the key of the core it names (or at the end), by rank
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => default
		 */
		public static function defaults( array &$appData ): array {

			$entered = Register::all( $appData, self::REGISTER );

			if( $entered === [] )
				return self::DEFAULTS;

			$stand = [];

			foreach( $entered as $key => $spec )
				$stand[(string) $spec['before']][] = [ $spec['rank'], $spec[Register::SEQ], (string) $key, $spec['default'] ];

			foreach( $stand as &$list )
				usort( $list, static fn( array $a, array $b ): int => [ $a[0], $a[1] ] <=> [ $b[0], $b[1] ] );

			unset( $list );
			$all = [];

			foreach( self::DEFAULTS as $key => $default ) {

				foreach( $stand[$key] ?? [] as [ , , $entry, $value ] )
					$all[$entry] = $value;

				$all[$key] = $default;
			}

			foreach( $stand[''] ?? [] as [ , , $entry, $value ] )
				$all[$entry] = $value;

			return $all;
		}

		/**
		 *	Whether the module keeps a key itself: no post writes it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	bool
		 */
		public static function isReadOnly( array &$appData, string $key ): bool {

			return ( $appData[self::REGISTER][$key]['read_only'] ?? false ) === true;
		}

		/**
		 *	The keys that hold a host: crm_host and the keys the packages entered with host
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											Keys, crm_host first
		 */
		public static function hostKeys( array &$appData ): array {

			return array_merge( [ 'crm_host' ], array_keys( array_filter( Register::all( $appData, self::REGISTER ), static fn( array $spec ): bool => $spec['host'] === true ) ) );
		}

		/**
		 *	The code the API answers with for a value of a key that fails
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	string										'' for a key that has none
		 */
		public static function refusal( array &$appData, string $key ): string {

			return self::REFUSALS[$key] ?? (string) ( $appData[self::REGISTER][$key]['refusal'] ?? '' );
		}

		/**
		 *	All settings, a key for every default
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => value, as text
		 */
		public static function all( array &$appData ): array {

			// Read once for the request: a page asks for a setting at every file, document and project it draws, and a write drops what was kept
			return (array) Db::kept( $appData, 'settings', 'all', static function() use ( &$appData ): array {

				$stored = array_column( Db::rows( $appData, 'SELECT key, value FROM settings' ), 'value', 'key' );
				$all = [];

				foreach( self::defaults( $appData ) as $key => $default )
					$all[$key] = isset( $stored[$key] ) === true ? (string) $stored[$key] : $default;

				return $all;
			} );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$key					One of the keys of the core or of those a package entered
		 *
		 *	@return 	string
		 *
		 *	@throws		\InvalidArgumentException	A key nobody entered
		 */
		public static function get( array &$appData, string $key ): string {

			if( self::known( $appData, $key ) === false )
				throw new \InvalidArgumentException( 'There is no setting "'. $key. '"' );

			return self::all( $appData )[$key];
		}

		/**
		 *	Whether a key is one of the core's or of a package's
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	bool
		 */
		public static function known( array &$appData, string $key ): bool {

			return isset( self::DEFAULTS[$key] ) === true || isset( $appData[self::REGISTER][$key] ) === true;
		}

		/**
		 *	The name the CRM calls its owner by - the one source of every sentence of the portal and of the mails that speaks of the person or the company behind the site: the name of the seller in the
		 *	settings (the seller's name, seller_name), else the name of the company of the site, else the author of the site, else its address. Put in as text, like the name of the site: what is
		 *	typed there is shown as typed - markup, a fill or a shortcode in it is words, a control character and a null byte fall away, the name is one line and at most 120 characters
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										Not escaped; '' where the settings and the site name nobody
		 */
		public static function ownerName( array &$appData ): string {

			$candidates = [ self::readOnly( $appData, 'seller_name' ) ];

			foreach( [ '/project/company/general/name', '/project/website/general/author', '/project/website/general/url' ] as $fill )
				$candidates[] = html_entity_decode( (string) \Nino\Html::resolveTextfill( $appData, $fill ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			foreach( $candidates as $candidate ) {

				$name = self::name( $candidate );

				if( $name !== '' )
					return mb_substr( $name, 0, self::OWNER_MAX, 'UTF-8' );
			}

			return '';
		}

		/**
		 *	A setting of the module, read on its own: a row of the table settings through a connection that only reads - the one thing of the database a page of a visitor or of the site asks for. The connection is
		 *	opened on a file that is the core's and is there (a read makes none), never migrates, never writes, and waits for a locked database no longer than SELLER_WAIT milliseconds - once for the request, a read that failed is not tried again.
		 *	It cannot say that a read failed: for that readOrNull() answers null. What a caller that only shows a name does with the empty answer is its own business; a caller that decides something by the answer asks readOrNull()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the setting
		 *
		 *	@return 	string										'' where there is no database yet, it is not the core's, it is locked or damaged or the setting holds nothing
		 */
		public static function readOnly( array &$appData, string $key ): string {

			return self::readOrNull( $appData, $key ) ?? '';
		}

		/**
		 *	A setting of the module as readOnly() reads it, with the failure told apart from the empty setting
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the setting
		 *	@param		int				$wait					How long a locked database is waited for, in milliseconds: the most that was asked for in the request counts - a read that failed after a short wait is tried again for a longer one
		 *
		 *	@return 	string|null								The value, '' where the setting holds nothing or there is no database of the core's to read (none yet, or another file); null where there is one and it could not be read - it is locked past the wait, or damaged
		 */
		public static function readOrNull( array &$appData, string $key, int $wait = self::SELLER_WAIT ): ?string {

			$file = \Nino\Filesystem::path( $appData, Db::FILE );

			if( Db::kind( $file ) !== 'ours' )
				return '';

			// A read that failed - the database is locked, there is no table yet - is not tried again in this request for a wait that is not longer: a page asks for the name many times and waits no more than once
			if( ( $appData[self::TRIED][$file] ?? 0 ) >= $wait )
				return null;

			try {
				if( isset( $appData[self::READERS][$file] ) === false )
					$appData[self::READERS][$file] = new \PDO( 'sqlite:file:'. $file. '?mode=ro', null, null, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, \PDO::ATTR_TIMEOUT => 0 ] );

				$appData[self::READERS][$file]->exec( 'PRAGMA busy_timeout = '. $wait );
				$statement = $appData[self::READERS][$file]->prepare( 'SELECT value FROM settings WHERE key = :key' );
				$statement->execute( [ 'key' => $key ] );

				return (string) ( $statement->fetchColumn() ?: '' );
			}
			catch( \PDOException $e ) {
				$appData[self::TRIED][$file] = $wait;

				return null;
			}
		}

		/**
		 *	A name as the CRM shows it: put in as text, the one rule of every name of the site and of the owner - a control character, a line break and every run of blanks is one blank, what has no
		 *	shape (format characters, private use, unassigned) falls away, bad bytes are scrubbed, the ends are trimmed. A null byte is a control character: it is a blank, as in any other name
		 *
		 *	@param		string		$raw					Text as it was typed or read
		 *
		 *	@return 	string
		 */
		public static function name( string $raw ): string {

			return trim( (string) preg_replace( '/[\s\p{Cc}]+/u', ' ', (string) preg_replace( '/[\p{Cf}\p{Co}\p{Cn}]+/u', '', mb_scrub( $raw, 'UTF-8' ) ) ) );
		}

		/**
		 *	The start number of a sequence: the least the next client or the next project gets, and the counter a category of a package begins at where the package entered its start
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$category			client or project; a category whose package entered the key start_<category>
		 *
		 *	@return 	int												1 or more
		 */
		public static function start( array &$appData, string $category ): int {

			return max( 1, (int) self::get( $appData, 'start_'. $category ) );
		}

		/**
		 *	Whether a text is a name a host setting may hold
		 *
		 *	@param		string		$host
		 *
		 *	@return 	bool
		 */
		public static function validHost( string $host ): bool {

			return preg_match( self::HOST_NAME, $host ) === 1;
		}

		/**
		 *	The host of the address of the website, lower case
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										'' where the website has none
		 */
		public static function siteHost( array &$appData ): string {

			$host = parse_url( \Nino\Html::absoluteUrl( $appData ), PHP_URL_HOST );

			return is_string( $host ) === true ? strtolower( $host ) : '';
		}

		/**
		 *	Whether a name is the host of the website, which a host setting may not be
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$host
		 *
		 *	@return 	bool
		 */
		public static function isSiteHost( array &$appData, string $host ): bool {

			return $host !== '' && $host === self::siteHost( $appData );
		}

		/**
		 *	What the packages show of themselves in the settings: the hook '/crm/settings/view' is asked with [ 'packages' => [] ], and each listener puts its own part under
		 *	packages.<its key>. An answer that is not the shape asked for - a key that is no slug, a part that is no list - is left out
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											[ 'packages' => key => part ]
		 */
		public static function view( array &$appData ): array {

			$ask = [ 'packages' => [] ];

			\Nino\Callbacks::doCallbacks( $appData, '/crm/settings/view', $ask );

			$packages = [];

			foreach( is_array( $ask ) === true && is_array( $ask['packages'] ?? null ) === true ? $ask['packages'] : [] as $key => $part )
				if( Register::word( (string) $key ) === true && is_array( $part ) === true )
					$packages[(string) $key] = $part;

			return [ 'packages' => $packages ];
		}

		/**
		 *	Whether a clean value of a host setting is a name that may not be taken: the host of the website, or the value of another host setting
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					Any key; only the keys that hold a host (hostKeys(), and app_host) have a clash
		 *	@param		string		$value				As clean() answers it
		 *
		 *	@return 	bool
		 */
		public static function clash( array &$appData, string $key, string $value ): bool {

			$hosts = array_merge( self::hostKeys( $appData ), [ 'app_host' ] );

			if( in_array( $key, $hosts, true ) === false || $value === '' )
				return false;

			// The app may be the website's own name; the others may not
			if( $key !== 'app_host' && self::isSiteHost( $appData, $value ) === true )
				return true;

			foreach( $hosts as $other )
				if( $other !== $key && self::mayShare( $key, $other ) === false && self::get( $appData, $other ) === $value )
					return true;

			return false;
		}

		/**
		 *	Whether two host keys may hold the same name: the workbench may be where the CRM is (crm.example.com/ and crm.example.com/_admin), and no other two may
		 *
		 *	@param		string		$a
		 *	@param		string		$b
		 *
		 *	@return 	bool
		 */
		public static function mayShare( string $a, string $b ): bool {

			return in_array( [ $a, $b ], [ [ 'crm_host', 'app_host' ], [ 'app_host', 'crm_host' ] ], true );
		}

		/**
		 *	Write a setting that passes the rule of its key
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		mixed			$value
		 *
		 *	@return 	true|string								Why not, see the class
		 */
		public static function set( array &$appData, string $key, mixed $value ): true|string {

			if( self::known( $appData, $key ) === false )
				return 'unknown';

			$clean = self::clean( $appData, $key, $value );

			if( is_array( $clean ) === true )
				return $clean[0];

			// The hosts are other names than the site's own, and than each other's
			if( self::clash( $appData, $key, $clean ) === true )
				return 'value:'. $key;

			// A key of a package says what it wants done after it is written, and whether the value is another than the one before
			$written = $appData[self::REGISTER][$key]['written'] ?? null;
			$differs = $written !== null && self::get( $appData, $key ) !== $clean;

			Db::exec( $appData, 'INSERT INTO settings ( key, value ) VALUES ( :k, :v ) ON CONFLICT( key ) DO UPDATE SET value = excluded.value', [ 'k' => $key, 'v' => $clean ] );

			// What was read for the request is read again: the reader that only reads is none of the connection that wrote
			unset( $appData[self::READERS], $appData[self::TRIED] );

			if( $written !== null )
				call_user_func_array( $written, [ &$appData, $clean, $differs ] );

			if( in_array( $key, [ Hosts::KEY_CRM, Hosts::KEY_APP ], true ) === true )
				Hosts::forget( $appData );

			// Whoever keeps a copy of a setting for the request drops it: the packages are told which key is written, after everything that goes with it is written
			$told = [ 'key' => $key, 'value' => $clean ];

			\Nino\Callbacks::doCallbacks( $appData, '/crm/settings/changed', $told );

			return true;
		}

		/**
		 *	The value a key takes, or why not
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		mixed			$value
		 *
		 *	@return 	string|array							The text to store; or [ code ] for a value that fails: [ 'unknown' ] for a key nobody entered
		 */
		public static function clean( array &$appData, string $key, mixed $value ): string|array {

			// A null byte is no part of a value, wherever it stands: trim() would take one at the end of it
			if( ( is_string( $value ) === false && is_int( $value ) === false ) || str_contains( (string) $value, "\0" ) === true )
				return [ 'value:'. $key ];

			$value = trim( (string) $value );
			$entered = $appData[self::REGISTER][$key] ?? null;

			// A key of a package: its rule says; what it answers that is no text is the code of a value that fails
			if( $entered !== null ) {

				if( $entered['read_only'] === true )
					return [ 'value:'. $key ];

				$answer = call_user_func( $entered['clean'], $key, $value );

				return is_string( $answer ) === true ? $answer : [ is_array( $answer ) === true && in_array( $answer[0] ?? null, [ 'value:'. $key, 'format:'. $key ], true ) === true ? $answer[0] : 'value:'. $key ];
			}

			switch( $key ) {

				// The host the CRM is served under (crm_host) and the host the workbench is (app_host)
				case 'crm_host':
				case 'app_host':
					return self::host( $key, $value );

				// The wordmark of the mails: two lines of plain text - no control character, no mark that changes the direction of the text, no line break - and two colours as #rrggbb; each may stay empty
				case 'brand_word':
				case 'brand_domain':
					return self::line( $key, $value, $key === 'brand_word' ? self::MAX_BRAND_WORD : self::MAX_BRAND_DOMAIN );

				case 'brand_color':
				case 'brand_ink':
					return $value === '' ? '' : ( preg_match( self::COLOR_SHAPE, $value ) === 1 ? strtoupper( $value ) : [ 'value:'. $key ] );

				// The start number of the clients and of the projects: a whole number from 1 with at most seven digits
				case 'start_client':
				case 'start_project':
					return preg_match( '/^[0-9]{1,7}$/D', $value ) === 1 && (int) $value >= 1 ? (string) (int) $value : [ 'value:'. $key ];

				// The name of the owner: one line, no control character
				case 'seller_name':
					return mb_strlen( $value, 'UTF-8' ) <= self::MAX_SELLER && preg_match( '/^[^\p{C}]*$/Du', $value ) === 1 ? $value : [ 'value:'. $key ];
			}

			return [ 'unknown' ];
		}

		/**
		 *	The rule of a text with line breaks, as long as a client's own: no control character but the line break and the tab, no mark that changes the direction of the text, at most MAX_MESSAGE characters
		 *
		 *	@param		string		$key
		 *	@param		string		$value				Trimmed
		 *
		 *	@return 	string|array							The text; or [ 'value:<key>' ]
		 */
		public static function note( string $key, string $value ): string|array {

			$value = trim( str_replace( [ "\r\n", "\r" ], "\n", $value ) );

			return mb_strlen( $value, 'UTF-8' ) <= self::MAX_MESSAGE && preg_match( '/[^\P{Cc}\n\t]|[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', $value ) === 0 ? $value : [ 'value:'. $key ];
		}

		/**
		 *	The rule of one line of plain text: blanks of every kind at either end are no part of it, no control character, no mark that changes the direction of the text, no line break, at most $most characters; it may stay empty
		 *
		 *	@param		string		$key
		 *	@param		string		$value				Trimmed
		 *	@param		int				$most
		 *
		 *	@return 	string|array							The text; or [ 'value:<key>' ]
		 */
		public static function line( string $key, string $value, int $most ): string|array {

			$value = preg_replace( '/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $value ) ?? $value;

			return mb_strlen( $value, 'UTF-8' ) <= $most && preg_match( '/^[^\p{C}\p{Zl}\p{Zp}]*$/Du', $value ) === 1 ? $value : [ 'value:'. $key ];
		}

		/**
		 *	The rule of a switch: '0' or '1'
		 *
		 *	@param		string		$key
		 *	@param		string		$value				Trimmed
		 *
		 *	@return 	string|array							The text; or [ 'value:<key>' ]
		 */
		public static function flag( string $key, string $value ): string|array {

			return self::oneOf( $key, $value, [ '0', '1' ] );
		}

		/**
		 *	The rule of a choice: one of the words
		 *
		 *	@param		string		$key
		 *	@param		string		$value				Trimmed
		 *	@param		array 		$words
		 *
		 *	@return 	string|array							The text; or [ 'value:<key>' ]
		 */
		public static function oneOf( string $key, string $value, array $words ): string|array {

			return in_array( $value, $words, true ) === true ? $value : [ 'value:'. $key ];
		}

		/**
		 *	The rule of a host: a name of letters, digits and hyphens in at least two labels, written in lower case, or empty for none; no scheme, port, path, address of numbers, blank or character outside ASCII
		 *
		 *	@param		string		$key
		 *	@param		string		$value				Trimmed
		 *
		 *	@return 	string|array							The text; or [ 'value:<key>' ]
		 */
		public static function host( string $key, string $value ): string|array {

			$value = strtolower( $value );

			return $value === '' || self::validHost( $value ) === true ? $value : [ 'value:'. $key ];
		}
	}
}
