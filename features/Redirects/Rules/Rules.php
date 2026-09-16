<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Redirects\Rules		see features/Redirects/Redirects.php for the
 *											feature's own docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Redirects {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Rules							The rules, and the paths nobody had an answer for.
	 *										Read and written as /data/redirects.php, declared under
	 *										`data` in feature.php so a backup carries it - it is the
	 *										one thing here that cannot be worked out again from what
	 *										is on disk.
	 *
	 *										Everything a stored file says is held against what a
	 *										rule may be before any of it is used: a path that could
	 *										leave the site, a status that is not a redirect, a rule
	 *										that would send a visitor back into itself. A file
	 *										somebody edited by hand is input like any other.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Rules {

		public const string PATH = '/data/redirects.php';

		// The shape's own version, so a later format can recognise an older
		// file rather than misreading it
		public const int FORMAT = 1;

		/*	Both of them, and nothing else. 307 and 308 exist and are the right
			answer for a method that carries a body - which is exactly why they
			are not here: this only ever answers GET and HEAD (see
			Redirects::callbackRedirect()), and offering a status for a case that
			cannot arise is an option somebody has to think about once and can
			never use.	*/
		public const array STATUSES = [ 301, 302 ];

		// How many unanswered paths are kept. A crawler finds more of them in
		// an afternoon than a person will ever read; what makes the list useful
		// is that the ones worth a rule are near the top, not that it is complete
		public const int MISS_LIMIT = 50;

		/**
		 *	The stored file, normalised
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$notes				(reference) What had to be dropped or corrected
		 *
		 *	@return 	array										[ 'format', 'rules', 'misses' ]
		 */
		public static function read( array &$appData, array &$notes = [] ): array {

			if( isset( $appData['./redirects/rules'] ) === true && $notes === [] )
				return $appData['./redirects/rules'];

			$stored = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );

			$appData['./redirects/rules'] = self::normalize( is_array( $stored ) === true ? $stored : [], $notes );

			return $appData['./redirects/rules'];
		}

		/**
		 *	Write the file and forget the copy this request read
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$data					A normalised file
		 *
		 *	@return 	bool
		 */
		public static function write( array &$appData, array $data ): bool {

			unset( $appData['./redirects/rules'] );

			return \Nino\Filesystem::putFileContent( $appData, self::PATH, [
				'format'	=> self::FORMAT,
				'rules'		=> array_values( $data['rules'] ?? [] ),
				'misses'	=> $data['misses'] ?? [],
			] );
		}

		/**
		 *	Whatever the file held, held to what a rule may be.
		 *
		 *	Nothing is repaired silently: every rule that had to be dropped or
		 *	changed is named in $notes, which the panel shows. A redirect that
		 *	quietly became a different redirect is the one kind of mistake
		 *	nobody would go looking for.
		 *
		 *	@param		array 		$raw					Whatever was in the file
		 *	@param		array 		&$notes				(reference) What had to go
		 *
		 *	@return 	array
		 */
		public static function normalize( array $raw, array &$notes = [] ): array {

			$rules	= [];
			$seen		= [];

			foreach( (array) ( $raw['rules'] ?? [] ) as $entry ) {

				if( is_array( $entry ) === false )
					continue;

				$from = self::path( (string) ( $entry['from'] ?? '' ) );
				$to		= self::target( (string) ( $entry['to'] ?? '' ) );

				if( $from === '' || $to === '' ) {
					$notes[] = [ 'key' => '/_admin/redirects/note/incomplete', 'inserts' => [] ];
					continue;
				}

				if( isset( $seen[$from] ) === true ) {
					$notes[] = [ 'key' => '/_admin/redirects/note/duplicate', 'inserts' => [ '%s' => $from ] ];
					continue;
				}

				$subtree	= ( $entry['subtree'] ?? false ) === true;
				$status		= (int) ( $entry['status'] ?? 301 );

				if( in_array( $status, self::STATUSES, true ) === false ) {
					$notes[] = [ 'key' => '/_admin/redirects/note/status', 'inserts' => [ '%s' => $from, '%d' => (string) $status ] ];
					$status = 301;
				}

				$loop = self::loops( $from, $to, $subtree );

				if( $loop !== '' ) {
					$notes[] = [ 'key' => '/_admin/redirects/note/dropped', 'inserts' => [ '%s' => $from, '%r' => $loop ] ];
					continue;
				}

				$seen[$from]	= true;
				$rules[]			= [
					'from'		=> $from,
					'to'			=> $to,
					'status'	=> $status,
					'subtree'	=> $subtree,
					'hits'		=> max( 0, (int) ( $entry['hits'] ?? 0 ) ),
					'last'		=> self::stamp( (string) ( $entry['last'] ?? '' ) ),
				];
			}

			$misses = [];

			foreach( (array) ( $raw['misses'] ?? [] ) as $path => $miss ) {

				$path = self::path( is_string( $path ) === true ? $path : '' );

				if( $path === '' || is_array( $miss ) === false || isset( $misses[$path] ) === true )
					continue;

				$misses[$path] = [
					'count'	=> max( 1, (int) ( $miss['count'] ?? 1 ) ),
					'last'	=> self::stamp( (string) ( $miss['last'] ?? '' ) ),
				];
			}

			/*	Longest first, so the rule for "/shop/archive" is reached before
				the one for "/shop" that would swallow it. A subtree rule is a
				prefix, and a prefix list nobody ordered answers by accident */
			usort( $rules, static fn( array $a, array $b ): int => strlen( $b['from'] ) <=> strlen( $a['from'] ) );

			return [ 'format' => self::FORMAT, 'rules' => $rules, 'misses' => self::trimMisses( $misses ) ];
		}

		/**
		 *	The rule that answers a path, with the address it sends to.
		 *
		 *	An exact rule wins over a subtree one however long either is: "this
		 *	one page moved there" is a statement about that page, and a subtree
		 *	rule over it is a statement about everything else.
		 *
		 *	@param		array 		$rules				Normalised rules, longest first
		 *	@param		string		$path					The path that was asked for
		 *
		 *	@return 	array|null							[ 'from', 'to', 'status' ] - 'to' resolved for a subtree
		 */
		public static function match( array $rules, string $path ): ?array {

			$path = self::path( $path );

			if( $path === '' )
				return null;

			foreach( $rules as $rule )
				if( $rule['subtree'] === false && $rule['from'] === $path )
					return [ 'from' => $rule['from'], 'to' => $rule['to'], 'status' => $rule['status'] ];

			foreach( $rules as $rule ) {

				if( $rule['subtree'] === false )
					continue;

				if( $path !== $rule['from'] && str_starts_with( $path, $rule['from']. '/' ) === false )
					continue;

				// What stood after the old prefix stands after the new one, so
				// a section that moved takes its pages with it rather than
				// sending every one of them to the same page
				$rest = substr( $path, strlen( $rule['from'] ) );

				return [ 'from' => $rule['from'], 'to' => rtrim( $rule['to'], '/' ). $rest, 'status' => $rule['status'] ];
			}

			return null;
		}

		/**
		 *	Why a rule would send a visitor back to itself, or '' where it would
		 *	not.
		 *
		 *	Checked here rather than left to the browser: a rule this feature
		 *	only applies where nothing answers the path is a rule whose target,
		 *	if nothing answers that either, comes straight back through the same
		 *	rule. The browser stops after some tries and says so in a way nobody
		 *	can act on
		 *
		 *	@param		string		$from
		 *	@param		string		$to						A path or an absolute url
		 *	@param		bool			$subtree
		 *
		 *	@return 	string								A fill key naming the reason, or ''
		 */
		public static function loops( string $from, string $to, bool $subtree ): string {

			// Somewhere else entirely: whatever happens there is that site's
			if( str_starts_with( $to, '/' ) === false )
				return '';

			if( $from === $to )
				return '/_admin/redirects/reason/self';

			if( $subtree === true && ( $to === $from || str_starts_with( $to, $from. '/' ) === true ) )
				return '/_admin/redirects/reason/subtree';

			return '';
		}

		/**
		 *	A site path, or '' where the value is not one. Query and fragment go
		 *	- a rule is about a path, and a redirect that only applied to one
		 *	spelling of the same page would be a rule that looks broken
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string								'/old/page', or ''
		 */
		public static function path( string $value ): string {

			$value = trim( $value );
			$value = (string) preg_replace( '/[?#].*$/', '', $value );
			$value = rtrim( $value, '/' );

			if( $value === '' || str_starts_with( $value, '/' ) === false )
				return '';

			// Two slashes at the front is a protocol-relative url, which is
			// another host with the scheme left out - never a path here
			if( str_starts_with( $value, '//' ) === true || str_contains( $value, '..' ) === true )
				return '';

			// Whitespace and control characters cannot reach a header, and a
			// path carrying them is not a path anybody typed
			if( preg_match( '/[\x00-\x20\x7f]/', $value ) === 1 )
				return '';

			return $value;
		}

		/**
		 *	Where a rule may send somebody: a path of this site, or an https
		 *	address of another one.
		 *
		 *	http is refused rather than passed through. A redirect is the one
		 *	moment a site chooses the next address for somebody, and choosing a
		 *	plaintext one hands that request to whoever is on the wire
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string								The target, or ''
		 */
		public static function target( string $value ): string {

			$value = trim( $value );

			if( str_starts_with( $value, '/' ) === true )
				return self::path( $value );

			if( str_starts_with( $value, 'https://' ) === false )
				return '';

			if( preg_match( '/[\x00-\x20\x7f]/', $value ) === 1 )
				return '';

			$host = parse_url( $value, PHP_URL_HOST );

			return is_string( $host ) === true && $host !== '' ? rtrim( $value, '/' ) : '';
		}

		/**
		 *	Remember a path nothing answered.
		 *
		 *	Only what a page's address looks like: not below /_ or /. , which are
		 *	the workbench and a module's own technical endpoints rather than
		 *	anything anybody linked to, and no dot in the last segment,
		 *	or a name ending in .html or .htm, which is what a site migrated
		 *	from somewhere else still gets asked for. Everything else that
		 *	reaches a 404 on a public site is a scanner looking for wp-login.php
		 *	and .env, and a list of those is a list nobody reads twice.
		 *
		 *	The path only. Not who asked, not when they came from where - a
		 *	list of addresses to fix is not a visitor log, and this file is
		 *	carried by every backup
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$path
		 *
		 *	@return 	bool										Whether it was recorded
		 */
		public static function noteMiss( array &$appData, string $path ): bool {

			$path = self::path( $path );

			if( $path === '' || self::pageShaped( $path ) === false )
				return false;

			unset( $appData['./redirects/rules'] );

			return \Nino\Filesystem::mutate( $appData, self::PATH, static function( array $file ) use ( $path ): array {

				$misses = is_array( $file['misses'] ?? null ) ? $file['misses'] : [];

				$misses[$path] = [
					'count'	=> max( 1, (int) ( $misses[$path]['count'] ?? 0 ) + 1 ),
					'last'	=> date( 'Y-m-d H:i:s' ),
				];

				$file['format']	= self::FORMAT;
				$file['rules']	= array_values( is_array( $file['rules'] ?? null ) ? $file['rules'] : [] );
				$file['misses']	= self::trimMisses( $misses );

				return $file;
			} );
		}

		/**
		 *	Whether a path is shaped like a page somebody could have linked to
		 *
		 *	@param		string		$path
		 *
		 *	@return 	bool
		 */
		public static function pageShaped( string $path ): bool {

			// The same two prefixes the Seo feature leaves out of a sitemap: a
			// mistyped workbench address is not a page somebody lost
			if( str_starts_with( $path, '/_' ) === true || str_starts_with( $path, '/.' ) === true )
				return false;

			$last = basename( $path );

			if( str_contains( $last, '.' ) === false )
				return true;

			return preg_match( '/\.html?$/i', $last ) === 1;
		}

		/**
		 *	The list, cut to MISS_LIMIT - the most asked for first, and the most
		 *	recent of those with the same count
		 *
		 *	@param		array 		$misses
		 *
		 *	@return 	array
		 */
		public static function trimMisses( array $misses ): array {

			uasort( $misses, static fn( array $a, array $b ): int =>
				[ $b['count'], $b['last'] ] <=> [ $a['count'], $a['last'] ] );

			return array_slice( $misses, 0, self::MISS_LIMIT, true );
		}

		/**
		 *	A stored timestamp, or '' where it is not one
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		public static function stamp( string $value ): string {

			return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', trim( $value ) ) === 1 ? trim( $value ) : '';
		}
	}
}
