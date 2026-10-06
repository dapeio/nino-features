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
	 *										And two questions the panel asks about a target
	 *										without storing the answer: which pages of the site
	 *										there are to send to (routes()), and what answers an
	 *										address a rule sends to (answer()).
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

		// How many rules answer one another before a chain is called too long.
		// A browser gives up after about twenty redirects; a chain of this
		// length is not one anybody wrote on purpose. That it comes back to an
		// address it has been at is another thing, and is called a loop
		public const int HOPS = 8;

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

			$misses = self::misses( (array) ( $raw['misses'] ?? [] ) );

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

				// The home page is the one target that is nothing but a slash:
				// a subtree that moved there has to answer '/', never ''
				$to = rtrim( $rule['to'], '/' ). $rest;

				return [ 'from' => $rule['from'], 'to' => $to === '' ? '/' : $to, 'status' => $rule['status'] ];
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

			if( $subtree === true && str_starts_with( $to, $from. '/' ) === true )
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
		 *	The home page, '/', is a target although path() answers '' for it:
		 *	an old address that moved to the front page is the commonest rule
		 *	there is, and what a rule *starts* from is another question
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

			// Query and fragment go first, as path() drops them: '/?x' is the
			// home page the way '/a?x' is '/a'
			if( str_starts_with( $value, '/' ) === true )
				$value = (string) preg_replace( '/[?#].*$/', '', $value );

			if( $value === '/' )
				return '/';

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
		 *	The pages a rule could send to, for the panel's picker.
		 *
		 *	Read from the routes of this request rather than from config.php,
		 *	because a route a module registers in init() answers an address as
		 *	well as one a project persisted. A GET route without a wildcard and
		 *	shaped like a page (see pageShaped(), which also drops the
		 *	workbench and a name like sitemap.xml) is offered; '/' is.
		 *
		 *	The label is the page's name the way a menu resolves it: the
		 *	locale-independent global.php under the file of a locale, the
		 *	native one first and so winning where several name the page.
		 *	Navigation's own lookup is private, so this is its reading again.
		 *	A page nobody named is its path.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array								path => [ 'path', 'label', 'locale' ], by path
		 */
		public static function routes( array &$appData ): array {

			$textDir	= '/text';
			$global		= \Nino\Filesystem::getFileContent( $appData, $textDir. '/global.php', [] );
			$global		= is_array( $global ) === true ? $global : [];
			$texts		= [];

			foreach( array_unique( array_merge( [ \Nino\Locales::getNativeLocale( $appData ) ], \Nino\Locales::getAvailableLocales( $appData ) ) ) as $locale ) {
				$fills		= \Nino\Filesystem::getFileContent( $appData, $textDir. '/'. $locale. '.php', [] );
				$texts[]	= array_merge( $global, is_array( $fills ) === true ? $fills : [] );
			}

			$pages = [];

			foreach( (array) ( $appData['/nino/http/routes'] ?? [] ) as $key => $route ) {

				if( is_string( $key ) === false || str_starts_with( $key, 'GET://' ) === false || str_ends_with( $key, '/*' ) === true )
					continue;

				$path = substr( $key, strlen( 'GET:/' ) );

				if( ( $path !== '/' && self::pageShaped( $path ) === false ) || isset( $pages[$path] ) === true )
					continue;

				$uri		= is_array( $route ) === true && is_string( $route['uri'] ?? null ) === true ? $route['uri'] : $path;
				$label	= '';

				foreach( $texts as $fills ) {

					$name = $fills['[[/_nino/webpage'. $uri. '/name]]'] ?? '';

					if( is_string( $name ) === false )
						continue;

					$label = trim( html_entity_decode( strip_tags( $name ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

					if( $label !== '' )
						break;
				}

				$pages[$path] = [
					'path'		=> $path,
					'label'		=> $label !== '' ? $label : $path,
					'locale'	=> is_array( $route ) === true && is_string( $route['locale'] ?? null ) === true ? $route['locale'] : '',
				];
			}

			ksort( $pages );

			return $pages;
		}

		/**
		 *	What answers an address a rule sends to, in the order a request is
		 *	asked: a file, another site, a route, a rule, or nothing.
		 *
		 *	'file' is a file of the project's public directory, which the web
		 *	server answers without Nino - before any route or rule, so a rule
		 *	for such an address is never reached. 'external' is an https
		 *	address, which is never fetched - what is there is that site's.
		 *	'route' is a page of this site; for a subtree rule it is also any
		 *	route below the target, because a page or a wildcard there answers
		 *	the pages that moved. 'rule' is another rule answering the target,
		 *	so a visitor is redirected twice; the chain is followed for HOPS
		 *	rules and is 'loop' where it comes back to an address it has been
		 *	at, and 'chain' where it is longer than that without coming back -
		 *	a browser gives up on either. A chain is a 'rule' where its last
		 *	address is answered, by a page, a file or another site, and
		 *	'nothing' where it is not. Everything else is 'nothing': a visitor
		 *	gets the 404 page.
		 *
		 *	The one question behind the panel's probe, the warning after a save
		 *	and the flag in the table, so the three never disagree.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$to						A rule's target
		 *	@param		bool			$subtree			Whether the rule covers everything below its address
		 *
		 *	@return 	string								'external', 'route', 'rule', 'loop', 'chain', 'file' or 'nothing'
		 */
		public static function answer( array &$appData, string $to, bool $subtree = false ): string {

			if( str_starts_with( $to, 'https://' ) === true )
				return 'external';

			$rules	= self::read( $appData )['rules'];
			$seen		= [];

			for( $hop = 0; $hop <= self::HOPS; $hop++ ) {

				// A chain that ends on another site is an answer, and not ours
				// to follow
				if( str_starts_with( $to, 'https://' ) === true )
					return 'rule';

				$to = $to === '/' ? '/' : self::path( $to );

				if( $to === '' )
					return 'nothing';

				if( isset( $seen[$to] ) === true )
					return 'loop';

				$seen[$to] = true;

				if( self::_file( $appData, $to ) === true )
					return $hop === 0 ? 'file' : 'rule';

				if( \Nino\Http::requestRoute( $appData, $to, 'GET' ) !== null )
					return $hop === 0 ? 'route' : 'rule';

				if( $hop === 0 && $subtree === true && self::_routeBelow( $appData, $to ) === true )
					return 'route';

				$match = self::match( $rules, $to );

				if( $match === null )
					return 'nothing';

				$to = $match['to'];
			}

			// Every address of the chain was a new one: it is not a cycle, it is long
			return 'chain';
		}

		/**
		 *	Whether any GET route lies below an address - a page, or a wildcard
		 *	that answers everything under it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$to						A path, '/' for the whole site
		 *
		 *	@return 	bool
		 */
		private static function _routeBelow( array &$appData, string $to ): bool {

			$prefix = 'GET:/'. rtrim( $to, '/' ). '/';

			foreach( array_keys( (array) ( $appData['/nino/http/routes'] ?? [] ) ) as $key )
				if( is_string( $key ) === true && str_starts_with( $key, $prefix ) === true )
					return true;

			return false;
		}

		/**
		 *	Whether a path is a file below the project's public directory. Only
		 *	that tree: the web server answers it as it stands, and the rest of
		 *	the project is code or private
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$to						A path as path() answers it
		 *
		 *	@return 	bool
		 */
		private static function _file( array &$appData, string $to ): bool {

			if( str_starts_with( $to, '/public/' ) === false )
				return false;

			return is_file( \Nino\Filesystem::getPublicPath( $appData ). substr( $to, strlen( '/public' ) ) ) === true;
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

				// What is on disk is held to what a miss may be before it is
				// counted, the same way a rule is when the file is read
				$misses = self::misses( is_array( $file['misses'] ?? null ) ? $file['misses'] : [] );

				$misses[$path] = [
					'count'	=> max( 1, (int) ( $misses[$path]['count'] ?? 0 ) + 1 ),
					'last'	=> date( 'Y-m-d H:i:s' ),
				];

				$file['format']	= self::FORMAT;
				$file['rules']	= array_values( is_array( $file['rules'] ?? null ) ? $file['rules'] : [] );
				// The address just asked for keeps its place through the cut,
				// or a list that is already full could never learn about it
				$file['misses']	= self::trimMisses( $misses, $path );

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
		 *	Whatever stood under 'misses', held to what one may be: an address,
		 *	a count and a time.
		 *
		 *	The file is one a person may have edited by hand - README.md says
		 *	so - and an entry somebody shaped there is input like any other.
		 *	Without this the sort below is handed whatever the file held, and a
		 *	note to self where a count belongs takes every unanswered request
		 *	on the site with it
		 *
		 *	@param		array 		$raw					Whatever stood under 'misses'
		 *
		 *	@return 	array								path => [ 'count', 'last' ]
		 */
		public static function misses( array $raw ): array {

			$misses = [];

			foreach( $raw as $path => $miss ) {

				$path = self::path( is_string( $path ) === true ? $path : '' );

				if( $path === '' || is_array( $miss ) === false || isset( $misses[$path] ) === true )
					continue;

				$misses[$path] = [
					'count'	=> max( 1, (int) ( $miss['count'] ?? 1 ) ),
					'last'	=> self::stamp( (string) ( $miss['last'] ?? '' ) ),
				];
			}

			return $misses;
		}

		/**
		 *	The list, cut to MISS_LIMIT - the most asked for first, and the most
		 *	recent of those with the same count.
		 *
		 *	$keep is the address that was just asked for, and it survives the
		 *	cut even where it ranks last. It arrives at a count of one and sorts
		 *	under everything that was ever asked for twice, so a list that once
		 *	filled up would drop it again on every request and its count could
		 *	never reach two: the list would go on answering about the pages that
		 *	broke last year and never learn about the one that broke today. It
		 *	takes the place of the least asked for rather than a place at the
		 *	front - the panel draws the list in the order it is in, and the
		 *	newest address is not the most asked for one
		 *
		 *	@param		array 		$misses
		 *	@param		string		$keep					An address that stays, or ''
		 *
		 *	@return 	array
		 */
		public static function trimMisses( array $misses, string $keep = '' ): array {

			$order = static fn( array $a, array $b ): int =>
				[ $b['count'], $b['last'] ] <=> [ $a['count'], $a['last'] ];

			uasort( $misses, $order );

			if( $keep === '' || isset( $misses[$keep] ) === false )
				return array_slice( $misses, 0, self::MISS_LIMIT, true );

			$held = $misses[$keep];
			unset( $misses[$keep] );

			$misses = array_slice( $misses, 0, self::MISS_LIMIT - 1, true ) + [ $keep => $held ];

			uasort( $misses, $order );

			return $misses;
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
