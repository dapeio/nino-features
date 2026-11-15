<?php
declare(strict_types=1);

namespace Nino\Modules\Sysadmin {

	/**
	 *	Nino features					Sysadmin report: what is wrong with the site right now, as findings, and
	 *												the facts that go with them - read from the kernel and written
	 *												nowhere. The Dashboard's notices are not worked out here: they are
	 *												\Nino\Modules\Dashboard\Admin::notices(), the list the Dashboard
	 *												draws itself, so the mail says what the Dashboard says. What this
	 *												class adds are findings the Dashboard has no place for (a backup
	 *												that did not run, a refused manifest, a full disk, a form cap that is
	 *												reached) and the figures of the mail (the log since the last mail,
	 *												the backup, the disk), all of them through public calls of the kernel.
	 *
	 *												A finding is { key, rank, event, text }: a key that names it for as long
	 *												as it is the same finding, its rank (NOTICE, WARNING, ERROR), whether it
	 *												is an event - something that happened since the last mail, which is
	 *												news every time - rather than a condition that holds, and its sentence
	 *												in the language asked for
	 */
	class Report {

		public const int NOTICE = 1;
		public const int WARNING = 2;
		public const int ERROR = 3;

		// The word of a rank in the words of the project and of the panel
		public const array RANK_WORDS = [ self::NOTICE => 'notice', self::WARNING => 'warning', self::ERROR => 'error' ];

		// What a mail says of the log: the messages that come up most often
		public const int TOP = 5;

		// A message is cut here, after it was cleaned
		public const int MESSAGE_LENGTH = 120;

		// Where a file count stops: the data directory of a site that has grown out of its
		// proportions is not walked to its end by a request
		private const int DATA_FILES = 20000;

		// The disk: below the one or the other of the first two the finding is an error, below the third a warning
		private const int DISK_ERROR_BYTES = 104857600;
		private const float DISK_ERROR_SHARE = 0.05;
		private const float DISK_WARNING_SHARE = 0.10;

		/**
		 *	Everything the report knows right now: the findings, worst first, and the facts
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The language the sentences are in, a language of the site
		 *	@param		int			$since				The log counts from this time on, a unix time
		 *	@param		int			$now					The clock
		 *	@param		bool			$everyone			Every notice of the Dashboard, as the mail has them; false for what the signed-in account may see
		 *
		 *	@return 	array										{ findings, facts }
		 */
		public static function collect( array &$appData, string $locale, int $since, int $now, bool $everyone = true, int $aborted = 0 ): array {

			$words	= \Nino\Html::getFills( $appData, $locale );
			$facts	= self::facts( $appData, $since, $now );
			$facts['aborted'] = $aborted;

			$found = [];

			if( class_exists( '\\Nino\\Modules\\Dashboard\\Admin' ) === true )
				foreach( \Nino\Modules\Dashboard\Admin::notices( $appData, $everyone ) as $notice )
					$found[] = [ 'key' => 'dashboard:'. $notice['key'], 'rank' => self::NOTICE, 'event' => false, 'text' => self::line( \Nino\Modules\Dashboard\Admin::sentence( $appData, $notice, $locale ) ) ];

			$say = static fn( string $name, array $values = [] ): string => self::say( $words, 'finding/'. $name, $values );

			if( $facts['backup']['failed'] !== null )
				$found[] = [ 'key' => 'backup-failed', 'rank' => self::ERROR, 'event' => false, 'text' => $say( 'backup-failed', [ $facts['backup']['failed'] !== '' ? $facts['backup']['failed'] : self::say( $words, 'word/silent' ) ] ) ];

			if( $facts['backup']['stopped'] !== null )
				$found[] = [ 'key' => 'backup-stopped', 'rank' => self::ERROR, 'event' => false, 'text' => $say( 'backup-stopped', [ $facts['backup']['stopped']['started'], self::say( $words, 'cause/'. ( $facts['backup']['stopped']['cause'] !== '' ? $facts['backup']['stopped']['cause'] : 'none' ) ) ] ) ];

			if( $aborted > 0 )
				$found[] = [ 'key' => 'run-aborted', 'rank' => self::WARNING, 'event' => false, 'text' => $say( 'run-aborted', [ (string) $aborted ] ) ];

			foreach( $facts['refused'] as $directory => $refusal )
				$found[] = [ 'key' => 'manifest:'. $directory, 'rank' => $refusal['active'] === true ? self::ERROR : self::WARNING, 'event' => false, 'text' => $say( $refusal['active'] === true ? 'manifest-active' : 'manifest-off', [ $directory, $refusal['reason'] ] ) ];

			if( $facts['cap'] !== '' )
				$found[] = [ 'key' => 'form-cap:'. $facts['cap'], 'rank' => self::WARNING, 'event' => false, 'text' => $say( 'form-cap-'. $facts['cap'] ) ];

			if( $facts['disk'] !== null && $facts['disk']['rank'] > 0 )
				$found[] = [ 'key' => 'disk', 'rank' => $facts['disk']['rank'], 'event' => false, 'text' => $say( 'disk', [ self::size( $appData, $facts['disk']['free'] ), self::size( $appData, $facts['disk']['total'] ), self::percent( $facts['disk']['free'], $facts['disk']['total'] ) ] ) ];

			// An event, not a condition: what the log holds since the last mail is news whatever it was
			// before, so it has the one key and is never "already told"
			if( $facts['log']['errors'] > 0 )
				$found[] = [ 'key' => 'log-errors', 'rank' => self::ERROR, 'event' => true, 'text' => $say( 'log-errors', [ (string) $facts['log']['errors'], self::stamp( $since ) ] ) ];

			usort( $found, static fn( array $a, array $b ): int => $b['rank'] <=> $a['rank'] );

			return [ 'findings' => $found, 'facts' => $facts ];
		}

		/**
		 *	The figures: backup, log since a time, mail, caps, locks, refused manifests, disk, data, versions
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		int			$since				The log counts from this time on
		 *	@param		int			$now					The clock
		 *
		 *	@return 	array
		 */
		public static function facts( array &$appData, int $since, int $now ): array {

			$cap = \Nino\Form::capped( $appData );

			return [
				'backup'	=> self::backup( $appData, $now ),
				'log'			=> self::log( $appData, $since, $now ),
				'mail'		=> \Nino\Mail::failure( $appData ),
				'cap'			=> in_array( $cap, [ 'hour', 'month' ], true ) === true ? $cap : '',
				'locked'	=> count( \Nino\Auth::lockedAccounts( $appData ) ),
				'refused'	=> self::refused( $appData ),
				'disk'		=> self::disk( $appData ),
				'data'		=> self::dataSize( $appData ),
				'php'			=> PHP_VERSION,
				'nino'		=> \Nino\VERSION,
			];
		}

		/**
		 *	The last backup that was made, of either kind - its date, its age in days and its size - and what went wrong
		 *	with an attempt after it: today's daily attempt that failed (\Nino\Modules\Backups::missed()) and a run that
		 *	php or the server ended (stopped()), each lifted by an archive that is newer than its note, a backup by hand
		 *	included. All null where the backup is switched off or the module is not there
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		int			$now					The clock
		 *
		 *	@return 	array										{ on, date, days, bytes, failed (the reason cleaned, '' for none given, null for none), stopped ({ started, cause } or null) }
		 */
		public static function backup( array &$appData, int $now ): array {

			$none = [ 'on' => false, 'date' => null, 'days' => null, 'bytes' => null, 'failed' => null, 'stopped' => null ];

			if( class_exists( '\\Nino\\Modules\\Backups' ) === false || ( $appData['/nino/admin/backups'] ?? true ) === false )
				return $none;

			$dir = \Nino\Modules\Backups::dir( $appData );

			// The newest archive of either kind - "Y-m-d", "Y-m-d-His" and "Y-m-d-His-manual" - by its time of writing
			$newest = [ 'time' => 0, 'file' => '' ];

			foreach( glob( $dir. '/*.php' ) ?: [] as $archive )
				if( preg_match( '/^\d{4}-\d{2}-\d{2}(?:-\d{6}(?:'. preg_quote( \Nino\Modules\Backups::MANUAL_SUFFIX, '/' ). ')?)?$/D', basename( $archive, '.php' ) ) === 1 && (int) filemtime( $archive ) >= $newest['time'] )
					$newest = [ 'time' => (int) filemtime( $archive ), 'file' => $archive ];

			$date		= $newest['file'] === '' ? null : substr( basename( $newest['file'], '.php' ), 0, 10 );
			$days		= $date === null ? null : max( 0, (int) floor( ( strtotime( date( 'Y-m-d', $now ) ) - (int) strtotime( $date ) ) / 86400 ) );
			$bytes	= $newest['file'] === '' ? null : (int) filesize( $newest['file'] );

			$missed		= \Nino\Modules\Backups::missed( $appData );
			$noted		= (int) @filemtime( $dir. '/'. \Nino\Modules\Backups::ATTEMPT_FILE );
			$stopped	= \Nino\Modules\Backups::stopped( $appData );

			return [
				'on'				=> true,
				'date'			=> $date,
				'days'			=> $days,
				'bytes'			=> $bytes,
				'failed'		=> $missed === null || $newest['time'] > $noted ? null : self::clean( $appData, (string) ( $missed['reason'] ?? '' ) ),
				'stopped'		=> $stopped === null || $newest['time'] > (int) $stopped['started'] ? null : [ 'started' => self::stamp( (int) $stopped['started'] ), 'cause' => (string) $stopped['cause'] ],
			];
		}

		/**
		 *	The error log since a time: how many errors and how many warnings, and the messages that come up
		 *	most often, each cleaned (see clean()) and with its count. An error is what ended a request - the
		 *	kernel answers 500 for every level but the four it lets the request survive - a warning a
		 *	E_USER_WARNING. Each month between the two times is read, at most twelve
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		int			$since				A unix time
		 *	@param		int			$now					The clock
		 *
		 *	@return 	array										{ errors, warnings, top: [ { text, count } ] }
		 */
		public static function log( array &$appData, int $since, int $now ): array {

			$from		= date( 'Y-m-d H:i:s', $since );
			$to			= date( 'Y-m-d H:i:s', $now );
			$months	= [];

			// Every month from the first to the last, however long it is since a mail went out; the log keeps three
			for( $month = (int) strtotime( date( 'Y-m-01', $since ) ); $month <= $now && count( $months ) < 12; $month = (int) strtotime( '+1 month', $month ) )
				$months[] = date( 'Y-m', $month );

			$months = array_unique( array_merge( $months, [ date( 'Y-m', $now ) ] ) );
			$errors	= 0;
			$warnings	= 0;
			$groups	= [];

			foreach( $months as $month ) {

				$entries = \Nino\Filesystem::getFileContent( $appData, '/data/logs.'. $month. '.php', [] );

				foreach( is_array( $entries ) === true ? $entries : [] as $entry ) {

					if( is_array( $entry ) === false || is_string( $entry['date'] ?? null ) === false || $entry['date'] < $from || $entry['date'] > $to )
						continue;

					$type = $entry['type'] ?? null;

					if( $type === E_USER_WARNING )
						$warnings++;
					else if( $type === 'Exception' || ( is_int( $type ) === true && in_array( $type, [ E_USER_NOTICE, E_USER_DEPRECATED, E_DEPRECATED ], true ) === false ) )
						$errors++;
					else
						continue;

					$text = self::clean( $appData, (string) ( $entry['message'] ?? '' ), 0 );

					// The numbers of a message are the one thing that differs between two of a kind
					$signature = preg_replace( '/\d+/', '#', $text ) ?? $text;

					$groups[$signature] = [ 'text' => self::cut( $text, self::MESSAGE_LENGTH ), 'count' => ( $groups[$signature]['count'] ?? 0 ) + 1 ];
				}
			}

			uasort( $groups, static fn( array $a, array $b ): int => $b['count'] <=> $a['count'] );

			return [ 'errors' => $errors, 'warnings' => $warnings, 'top' => array_slice( array_values( $groups ), 0, self::TOP ) ];
		}

		/**
		 *	The directories of features whose manifest the kernel refused, with the reason - cleaned - and
		 *	whether the feature is on all the same
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										directory => { reason, active }
		 */
		public static function refused( array &$appData ): array {

			$active		= \Nino\Features::rejectedActive( $appData );
			$refused	= [];

			foreach( \Nino\Features::rejected( $appData ) as $directory => $reason )
				$refused[ (string) $directory ] = [ 'reason' => self::clean( $appData, (string) $reason ), 'active' => isset( $active[$directory] ) === true ];

			return $refused;
		}

		/**
		 *	Free and total space of the disk the project is on, and the rank of what is free
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								{ free, total, rank (0 for room enough) }, null where the server does not say
		 */
		public static function disk( array &$appData ): ?array {

			$directory = \Nino\Filesystem::path( $appData, '/data' );

			// A test sets the two numbers, since the disk it runs on is whatever it is
			$set = $appData['./sysadmin/disk'] ?? null;
			$free = is_array( $set ) === true ? (float) $set[0] : @disk_free_space( $directory );
			$total = is_array( $set ) === true ? (float) $set[1] : @disk_total_space( $directory );

			if( is_float( $free ) === false || is_float( $total ) === false || $total <= 0 )
				return null;

			$share = $free / $total;
			$rank	 = ( $free < self::DISK_ERROR_BYTES || $share < self::DISK_ERROR_SHARE ) ? self::ERROR : ( $share < self::DISK_WARNING_SHARE ? self::WARNING : 0 );

			return [ 'free' => (int) $free, 'total' => (int) $total, 'rank' => $rank ];
		}

		/**
		 *	How many bytes the files below private/data take, counted up to DATA_FILES files
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int|null									Null where the directory cannot be read
		 */
		public static function dataSize( array &$appData ): ?int {

			$directory = \Nino\Filesystem::path( $appData, '/data' );

			if( is_dir( $directory ) === false )
				return null;

			$bytes = 0;
			$files = 0;

			try {
				foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

					if( $file->isFile() === true )
						$bytes += $file->getSize();

					if( ++$files >= self::DATA_FILES )
						break;
				}
			} catch( \Throwable ) {
				return null;
			}

			return $bytes;
		}

		/**
		 *	One line of the log as the mail may carry it: a single line, the project's own path taken off a
		 *	path and every other path cut to its file, an email address and an address of the network
		 *	made unrecognisable, and no more than MESSAGE_LENGTH characters. What a form posted is never
		 *	in a message of the kernel; this is for the ones that carry an address or a path all the same
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$text
		 *	@param		int			$length				Where the text is cut; 0 for where it is not (the sentences of the Dashboard are said whole)
		 *
		 *	@return 	string
		 */
		public static function clean( array &$appData, string $text, int $length = self::MESSAGE_LENGTH ): string {

			$text = self::line( $text );

			// An address of a person - with letters of any language and %40 for the @, a name of a host with no dot as well
			$text = (string) preg_replace( '/[\p{L}\p{N}._%+\-]+(?:@|%40)[\p{L}\p{N}\-]+(?:\.[\p{L}\p{N}\-]+)*/u', '[email]', $text );

			// The project's own directory, then every path that is left down to its file name - of the server and of windows
			$root = dirname( \Nino\Filesystem::path( $appData, '/data' ), 2 );

			if( $root !== '' && $root !== '/' && $root !== '.' )
				$text = str_replace( $root, '', $text );

			$text = (string) preg_replace_callback( '#(?<![\w.:/])/(?:[\w.\-]+/)+[\w.\-]+#', static fn( array $match ): string => '…/'. basename( $match[0] ), $text );
			$text = (string) preg_replace_callback( '#\b[A-Za-z]:\\\\(?:[^\\\\\s]+\\\\)*[^\\\\\s?\#]*#', static fn( array $match ): string => '…\\'. (string) preg_replace( '#^.*\\\\#', '', $match[0] ), $text );

			// What follows the path of anything - an address, a path of the site - is what a visitor typed: a query, a fragment
			$text = (string) preg_replace( '/(?<=[\w\/.\-])[?#]\S+/u', '?…', $text );

			// A value of three parts of base64 joined by dots: a token of a login
			$text = (string) preg_replace( '/\beyJ[A-Za-z0-9_\-]{4,}\.[A-Za-z0-9_\-]{4,}\.[A-Za-z0-9_\-]*|\b[A-Za-z0-9_\-]{16,}\.[A-Za-z0-9_\-]{16,}\.[A-Za-z0-9_\-]{16,}/', '[token]', $text );

			// An address of the network: version 4 as it stands, version 6 by the run of its characters, which may begin or end
			// with "::", carry a version 4 address ("::ffff:198.51.100.4") or have a name glued to it ("client:2001:db8::9").
			// The whole find is looked at: from the front, the first place from where what is left is an address
			$text = (string) preg_replace_callback( '/(?<![\w.])(?:\d{1,3}\.){3}\d{1,3}(?![\w.])|[0-9A-Fa-f:.]*:[0-9A-Fa-f:.]*/', static function( array $match ): string {

				$run = $match[0];

				for( $at = 0, $length = strlen( $run ); $at < $length; $at++ ) {

					$rest = substr( $run, $at );

					foreach( [ $rest, rtrim( $rest, '.' ), rtrim( $rest, ':.' ) ] as $candidate )
						if( $candidate !== '' && filter_var( $candidate, FILTER_VALIDATE_IP ) !== false )
							return substr( $run, 0, $at ). '[ip]'. substr( $run, $at + strlen( $candidate ) );
				}

				return $run;
			}, $text );

			// A value that is a key: a long run of hexadecimal digits, or of the characters of base64
			$text = (string) preg_replace( '/\b[A-Fa-f0-9]{24,}\b|(?<![\w\/.+\-])[A-Za-z0-9+\/_\-]{32,}={0,2}/', '[token]', $text );

			return self::cut( $text, $length );
		}

		/**
		 *	A text as one line: its line breaks and control characters are a space
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		public static function line( string $text ): string {

			return trim( (string) preg_replace( '/[\x00-\x1F\x7F\s]+/u', ' ', $text ) );
		}

		/**
		 *	A text cut to a length with an ellipsis, 0 for none
		 *
		 *	@param		string		$text
		 *	@param		int			$length
		 *
		 *	@return 	string
		 */
		public static function cut( string $text, int $length ): string {

			return $length > 0 && mb_strlen( $text ) > $length ? mb_substr( $text, 0, $length - 1 ). '…' : $text;
		}


		/**
		 *	A text of the feature in a language with its %s filled in order, the key without /feature/sysadmin/
		 *
		 *	@param		array 		$words				The fills of the language
		 *	@param		string		$name					eg. 'finding/disk'
		 *	@param		array 		$values
		 *
		 *	@return 	string										The text as the project has it; the name itself where it has none
		 */
		public static function say( array $words, string $name, array $values = [] ): string {

			$text = html_entity_decode( (string) ( $words['[[/feature/sysadmin/'. $name. ']]'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			if( $text === '' )
				return $name;

			$from = 0;

			foreach( $values as $value ) {

				$at = strpos( $text, '%s', $from );

				if( $at === false )
					break;

				$text = substr_replace( $text, (string) $value, $at, 2 );
				$from = $at + strlen( (string) $value );
			}

			return $text;
		}

		/**
		 *	A number of bytes in the unit that fits, with the decimals of the language
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		int			$bytes
		 *
		 *	@return 	string
		 */
		public static function size( array &$appData, int $bytes ): string {

			foreach( [ 'GB' => 1073741824, 'MB' => 1048576, 'KB' => 1024 ] as $unit => $size )
				if( $bytes >= $size )
					return ( \Nino\Format::amount( $appData, $bytes / $size, 1 ) ?? (string) round( $bytes / $size, 1 ) ). ' '. $unit;

			return $bytes. ' B';
		}

		/**
		 *	A share as a whole percent
		 *
		 *	@param		int			$part
		 *	@param		int			$whole
		 *
		 *	@return 	string
		 */
		public static function percent( int $part, int $whole ): string {

			return $whole <= 0 ? '0 %' : round( $part * 100 / $whole ). ' %';
		}

		/**
		 *	A time as the mail writes every time: the day and the minute, in the time zone of the server
		 *
		 *	@param		int			$time					A unix time
		 *
		 *	@return 	string
		 */
		public static function stamp( int $time ): string {

			return date( 'Y-m-d H:i', $time );
		}
	}
}
