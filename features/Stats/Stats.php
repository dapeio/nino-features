<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Stats						see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Stats							Page-view counting without any personal data: no cookie,
	 *										no ip address, no fingerprint, nothing kept per visitor -
	 *										only counts. Registers on '/nino/http/response' at
	 *										priority 8, ie. before Modules\Cache answers a hit at
	 *										priority 9 (see Cache::init()), so a cached page is still
	 *										counted every time it is served. Counted per calendar day
	 *										into one file per month under /data/stats/ - views per
	 *										uri, views per referrer host, and a total - written
	 *										through \Nino\Filesystem::mutate() (locked, atomic), one
	 *										mutate per counted view. See README.md for the exact
	 *										storage shape, the retention sweep and why there is no
	 *										unique-visitor number. Only pages are counted, and only
	 *										pages are shown: see isPage().
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Stats {

		public const string KEY = 'stats';

		public const string STORAGE_DIR = '/data/stats';

		// A distinct uri beyond the configured maxUris budget is folded into
		// this single bucket instead of its own key - an ellipsis is never a
		// real path, so it cannot collide with one
		public const string OVERFLOW_URI = '/…';

		// What a uri ending in one of these is: a file a crawler or a browser
		// fetches, not a page somebody reads. Counted neither by the counter
		// nor by the panel - the declared Content-Type decides first, this is
		// for the route that declares none (a hand-written /feed.xml)
		private const array FILE_EXTENSIONS = [ 'txt', 'xml', 'json', 'rss', 'atom', 'webmanifest' ];

		/**
		 *	The /_admin screen this feature brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears in the
		 *	workbench exactly while this feature is active
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Stats\Admin::class ];
		}

		/**
		 *	Register the counter at priority 8 - one below Modules\Cache's
		 *	own priority 9 handler on the same hook (see that class'
		 *	callbackResponse()), so this runs and counts the view before a
		 *	cache hit answers the request and ends it. A visitor answered
		 *	from the cache is therefore still one view; only the render
		 *	itself is skipped.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ self::class, 'callbackCount' ], 8 );
		}

		/**
		 *	Count one qualifying view. Never throws, never fails the
		 *	response: a storage problem is left to \Nino\Filesystem::mutate()'s
		 *	own false return, silently skipped here the same way a dropped
		 *	page view is - a visitor's page must never turn into a 500
		 *	because a stats file could not be written.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current request
		 *
		 *	@return 	void
		 */
		public static function callbackCount( array &$appData, array &$request ): void {

			if( self::_countable( $appData, $request ) === false )
				return;

			$uri 			= (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$referrer	= self::_referrerHost( $request );
			$maxUris	= (int) \Nino\Features::setting( $appData, self::KEY, 'maxUris', 500 );

			$day	= date( 'Y-m-d' );
			$path	= self::STORAGE_DIR. '/'. date( 'Y-m' ). '.php';

			$isNewDay = false;

			\Nino\Filesystem::mutate( $appData, $path, function( array $state ) use ( $day, $uri, $referrer, $maxUris, &$isNewDay ): array {

				if( is_array( $state['days'] ?? null ) === false )
					$state['days'] = [];

				if( isset( $state['days'][$day] ) === false ) {
					$isNewDay = true;
					$state['days'][$day] = [ 'total' => 0, 'uris' => [], 'referrers' => [] ];
				}

				$day_ = $state['days'][$day];
				$day_['total'] = (int) ( $day_['total'] ?? 0 ) + 1;

				$uris = is_array( $day_['uris'] ?? null ) ? $day_['uris'] : [];
				if( isset( $uris[$uri] ) === true )
					$uris[$uri]++;
				elseif( count( $uris ) < $maxUris )
					$uris[$uri] = 1;
				else
					$uris[self::OVERFLOW_URI] = (int) ( $uris[self::OVERFLOW_URI] ?? 0 ) + 1;
				$day_['uris'] = $uris;

				if( $referrer !== '' ) {
					$referrers = is_array( $day_['referrers'] ?? null ) ? $day_['referrers'] : [];
					$referrers[$referrer] = (int) ( $referrers[$referrer] ?? 0 ) + 1;
					$day_['referrers'] = $referrers;
				}

				$state['days'][$day] = $day_;

				return $state;
			}, [ 'days' => [] ] );

			// Checked after the write, not before: two views racing the same
			// new day both see it missing under mutate()'s own lock, but only
			// the write that actually happened should trigger the sweep -
			// prune() itself is idempotent, so running it twice costs nothing
			// beyond a wasted glob()
			if( $isNewDay === true )
				self::_prune( $appData, (int) \Nino\Features::setting( $appData, self::KEY, 'retentionMonths', 13 ) );
		}

		/**
		 *	Every month that has a stats file, newest first - what the panel's
		 *	month selector offers
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ 'YYYY-MM', ... ], newest first
		 */
		public static function months( array &$appData ): array {

			$dir = \Nino\Filesystem::path( $appData, self::STORAGE_DIR );
			$months = [];

			foreach( glob( $dir. '/*.php' ) ?: [] as $file ) {
				$name = basename( $file, '.php' );
				if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $name ) === 1 )
					$months[] = $name;
			}

			rsort( $months, SORT_STRING );

			return $months;
		}

		/**
		 *	One month's stored state, read as it is - the panel does the
		 *	aggregating (top uris/referrers, the day list)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$month				'YYYY-MM'
		 *
		 *	@return 	array|null							{ days: { 'YYYY-MM-DD': { total, uris, referrers } } }, null for a malformed month
		 */
		public static function monthData( array &$appData, string $month ): ?array {

			if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) !== 1 )
				return null;

			$state = \Nino\Filesystem::getFileContent( $appData, self::STORAGE_DIR. '/'. $month. '.php', [ 'days' => [] ] );

			if( is_array( $state ) === false )
				return [ 'days' => [] ];

			if( is_array( $state['days'] ?? null ) === false )
				$state['days'] = [];

			return $state;
		}

		/**
		 *	Whether a uri is a page - something a visitor reads - rather than a
		 *	file the site also answers, robots.txt and sitemap.xml among them.
		 *	What the counter does not count from now on, and what the panel and
		 *	the tile leave out of the months that were counted before: the file
		 *	hits of those stay on disk as they were, and are simply not shown.
		 *
		 *	The route decides, the way the request did (\Nino\Http::requestRoute(),
		 *	wildcard routes included): a route that declares a Content-Type other
		 *	than text/html is a file, whatever its address. A route that declares
		 *	none is looked at by its last segment - txt, xml, json, rss, atom and
		 *	webmanifest are files - and an address no route answers any more
		 *	counts as the page it was. Not the extension alone: '/v1.2-release-notes'
		 *	has one to pathinfo(), and is a page.
		 *
		 *	A JSON endpoint that declares no Content-Type on its route still
		 *	counts, then, unless it ends in .json: such a route declares its
		 *	Content-Type on the route, see the feature recipe's /api/catalog.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					As counted: the request path
		 *
		 *	@return 	bool
		 */
		public static function isPage( array &$appData, string $uri ): bool {

			if( $uri === self::OVERFLOW_URI )
				return true;

			$route = \Nino\Http::requestRoute( $appData, $uri, 'GET' );
			$route = is_array( $route ) === true ? $route : [];

			return self::_isHtml( is_array( $route['header'] ?? null ) === true ? $route['header'] : [] ) === true
				&& self::_isFile( $uri ) === false;
		}

		/**
		 *	The Dashboard tile: views over the last 7 days (today included).
		 *	The panel contract's summary() only ever carries { value, label }
		 *	(see \Nino\Admin\Panels::collect() and Dashboard\Admin::apiSummary()),
		 *	so today's count alone is not shown separately - the label says
		 *	what the number covers
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										{ value, label }
		 */
		public static function summary( array &$appData ): array {
			return [ 'value' => (string) self::_rangeTotal( $appData, 7 ), 'label' => '/_admin/stats/label/tile' ];
		}

		/**
		 *	Whether a response qualifies as one countable page view: a plain
		 *	GET (the raw method too, so a HEAD folded to GET for routing is
		 *	not one - see \Nino\Http::_cleanRawMethod()), answered 200, not
		 *	one of the workbench's or a module's own technical uris, not a
		 *	file (the response declares a Content-Type other than text/html, or
		 *	the uri ends in .txt, .xml, ... - see isPage()), not a uri the
		 *	operator excluded, and not a signed-in visitor unless countSignedIn
		 *	is on.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$request
		 *
		 *	@return 	bool
		 */
		private static function _countable( array &$appData, array $request ): bool {

			if( ( $request['/nino/http/request']['method'] ?? '' ) !== 'GET' || ( $request['/nino/http/request']['rawMethod'] ?? '' ) !== 'GET' )
				return false;

			if( (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ) !== 200 )
				return false;

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );

			if( $uri === '' || self::_isTool( $uri ) === true )
				return false;

			// The header the response carries now: the route's own, merged in by
			// \Nino\Http::response() before this callback runs
			if( self::_isHtml( (array) ( $request['/nino/http/response']['header'] ?? [] ) ) === false || self::_isFile( $uri ) === true )
				return false;

			if( \Nino\Features::setting( $appData, self::KEY, 'countSignedIn', false ) !== true && \Nino\Auth::getCurrentUser( $appData ) !== false )
				return false;

			return self::_excluded( (array) \Nino\Features::setting( $appData, self::KEY, 'exclude', [] ), $uri ) === false;
		}

		/**
		 *	Whether a header map allows html: no Content-Type at all does (the
		 *	kernel answers html), one that starts with text/html does, anything
		 *	else - text/plain, application/xml, application/json - is a file.
		 *	The name is looked for in any case, the way a route may spell it.
		 *
		 *	@param		array			$header				name => value
		 *
		 *	@return 	bool
		 */
		private static function _isHtml( array $header ): bool {

			foreach( $header as $name => $value )
				if( is_string( $name ) === true && strtolower( $name ) === 'content-type' )
					return is_string( $value ) === true && str_starts_with( strtolower( trim( $value ) ), 'text/html' ) === true;

			return true;
		}

		/**
		 *	Whether the last segment of a uri ends in one of FILE_EXTENSIONS
		 *
		 *	@param		string		$uri
		 *
		 *	@return 	bool
		 */
		private static function _isFile( string $uri ): bool {

			return in_array( strtolower( pathinfo( $uri, PATHINFO_EXTENSION ) ), self::FILE_EXTENSIONS, true );
		}

		/**
		 *	Whether a uri is one of Nino's own technical endpoints rather than
		 *	a page a visitor reads: the workbench itself, a module's own
		 *	dot-prefixed endpoint (\Nino\Modules\Form's /.form, this
		 *	catalogue's own Newsletter under /.newsletter, Auth's
		 *	/.nino/auth/*), or a /nino/... tool uri, replicated here because
		 *	\Nino\Modules\Cache::_isTool() (Cache.php) is private and answers
		 *	only the first of the three - see that class' own docblock for
		 *	the full "never cached" list this mirrors for counting instead.
		 *
		 *	@param		string		$uri
		 *
		 *	@return 	bool
		 */
		private static function _isTool( string $uri ): bool {

			if( $uri === '/_admin' || str_starts_with( $uri, '/_admin/' ) === true )
				return true;

			if( str_starts_with( $uri, '/.' ) === true )
				return true;

			// No shipped route is ever a literal /nino/... url (that
			// namespace is $appData's own, not something routed to) - kept
			// as a belt-and-braces exclusion in case a module ever wires one
			// up as a technical endpoint under that prefix
			return $uri === '/nino' || str_starts_with( $uri, '/nino/' ) === true;
		}

		/**
		 *	Match a uri against the operator's own exclude list. Plain string
		 *	work on purpose, same reasoning as Cache::_blacklisted(): an
		 *	admin-editable pattern must never reach a regex engine.
		 *
		 *	@param		array			$patterns			One '/path' or '/path/*' per entry
		 *	@param		string		$uri
		 *
		 *	@return 	bool
		 */
		private static function _excluded( array $patterns, string $uri ): bool {

			foreach( $patterns as $pattern ) {

				if( is_string( $pattern ) === false || $pattern === '' )
					continue;

				if( str_ends_with( $pattern, '/*' ) === false ) {
					if( $uri === $pattern )
						return true;
					continue;
				}

				$prefix = substr( $pattern, 0, -2 );

				if( $uri === $prefix || str_starts_with( $uri, $prefix. '/' ) === true )
					return true;
			}

			return false;
		}

		/**
		 *	The Referer header's host, lowercased, with an empty header and
		 *	the site's own host both answering '' - neither is a referrer
		 *	worth counting, and the own-host check is what keeps an internal
		 *	navigation from inflating "views from elsewhere".
		 *
		 *	@param		array			$request
		 *
		 *	@return 	string
		 */
		private static function _referrerHost( array $request ): string {

			$referer = (string) ( $request['/nino/http/request']['header']['Referer'] ?? '' );
			if( $referer === '' )
				return '';

			$host = strtolower( (string) ( parse_url( $referer, PHP_URL_HOST ) ?? '' ) );
			if( $host === '' )
				return '';

			$ownHost = strtolower( (string) ( $request['/nino/http/request']['header']['Host'] ?? '' ) );
			// A Host header may carry a port (eg. "example.com:8080" in a dev
			// setup); parse_url() on the referer never includes one on its
			// own PHP_URL_HOST, so the own side has to drop it to compare
			// like with like
			$ownHost = (string) ( preg_replace( '/:\d+$/', '', $ownHost ) ?? $ownHost );

			return $host === $ownHost ? '' : $host;
		}

		/**
		 *	Views over the last $days calendar days, today included, reading
		 *	each month file at most once even when the range crosses a month
		 *	boundary. The views of the pages only: the days' uris are summed,
		 *	each classified once by isPage(), so the tile says what the panel
		 *	does and a file hit counted before this was fixed is not in it.
		 *	Every counted view increments exactly one uri, so the sum over the
		 *	kept uris is exact.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		int				$days
		 *
		 *	@return 	int
		 */
		private static function _rangeTotal( array &$appData, int $days ): int {

			$total	= 0;
			$cache	= [];
			$pages	= [];

			for( $i = 0; $i < $days; $i++ ) {

				$day	= date( 'Y-m-d', strtotime( '-'. $i. ' days' ) );
				$month = substr( $day, 0, 7 );

				if( array_key_exists( $month, $cache ) === false )
					$cache[$month] = self::monthData( $appData, $month );

				foreach( (array) ( $cache[$month]['days'][$day]['uris'] ?? [] ) as $uri => $views ) {

					$uri = (string) $uri;

					if( isset( $pages[$uri] ) === false )
						$pages[$uri] = self::isPage( $appData, $uri );

					if( $pages[$uri] === true )
						$total += (int) $views;
				}
			}

			return $total;
		}

		/**
		 *	Delete month files older than the configured retention - run once
		 *	whenever the first view of a new day is counted (see
		 *	callbackCount()), never on every single view
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		int				$retentionMonths
		 *
		 *	@return 	void
		 */
		private static function _prune( array &$appData, int $retentionMonths ): void {

			/*	This month counts as one of the months that are kept, so the
				oldest one to survive is the month $retentionMonths - 1 back and
				the cutoff is its first day. Taken from $retentionMonths itself,
				the cutoff left that month on disk as well and the setting kept
				one more file than it says it keeps - "Keep for 13 months" kept
				fourteen	*/
			$cutoff = ( new \DateTime( 'first day of -'. ( $retentionMonths - 1 ). ' months' ) )->setTime( 0, 0 );

			\Nino\RotatingLog::prune( \Nino\Filesystem::path( $appData, self::STORAGE_DIR ), '', 'Y-m', '.php', $cutoff );
		}
	}
}
