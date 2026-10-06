<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Stats\Admin		The /_admin panel of the Stats module - see docs/development.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Stats {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules						Optional modules
	 *	Stats							One pane on \Nino\Modules\Stats's own counts: a month
	 *										selector, a bar per day and the two top-50 tables (pages,
	 *										referrer hosts). Read-only - the counting itself happens
	 *										in Stats.php's callback, this panel only ever reads what
	 *										is already on disk and aggregates it for display. Of
	 *										what is on disk it shows the pages only (see
	 *										\Nino\Modules\Stats::isPage()): a month counted before
	 *										files were left out keeps its file hits in the file and
	 *										shows none of them here.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string VIEW_PERM = '/_admin/stats/view';

		// The panel shows top-50 uris/referrers - enough to see what matters
		// on a small site without the pane turning into an unbounded table
		private const int TOP_LIMIT = 50;

		public static function actions(): array {
			return [
				'stats/months'	=> [ self::class, 'apiMonths' ],
				'stats/month'		=> [ self::class, 'apiMonth' ],
			];
		}

		public static function nav(): array {
			return [ 'stats', '/_admin/nav/stats', 70, 'content' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16v-4"/><path d="M12 16V8"/><path d="M17 16v-7"/></svg>';
		}

		public static function perm(): string {
			return self::VIEW_PERM;
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function summary( array &$appData ): array {
			return \Nino\Modules\Stats::summary( $appData );
		}

		// Read-only: opening the panel and looking at a chart is not
		// something the activity log has any use recording
		public static function log( string $action, array $data ): string {
			return '';
		}

		/**
		 *	Every month that has a stats file, newest first
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiMonths( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::VIEW_PERM ) === false )
				return;

			\Nino\Http::ok( $request, [ 'months' => \Nino\Modules\Stats::months( $appData ) ] );
		}

		/**
		 *	One month, aggregated for the pane: the day-by-day totals for the
		 *	bar row, the grand total, and the top 50 pages and referrer hosts
		 *	across the whole month.
		 *
		 *	Pages only. Each distinct uri of the month is classified once by
		 *	Stats::isPage() and a file - robots.txt, sitemap.xml, a json
		 *	endpoint - is dropped, the overflow bucket kept. A day's total is
		 *	then the sum of the uris that are left, which is exact because a
		 *	counted view increments exactly one uri; a day with nothing left is
		 *	dropped, and `totals` is counted from what is kept. The referrers
		 *	of a day are kept or dropped with the day: a referrer is stored per
		 *	day, not per page, so a day that was both has the referrers of
		 *	both.
		 *
		 *	A page row carries its title - the page's own '/_nino/webpage<uri>/title'
		 *	text, in the route's locale and else the native one - and '' where
		 *	no route or no text has one.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiMonth( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::VIEW_PERM ) === false )
				return;

			$month = (string) ( \Nino\Admin\Admin::postData()['month'] ?? '' );

			if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) !== 1 ) {
				\Nino\Http::fail( $request, 400, 'invalid month' );
				return;
			}

			$state = \Nino\Modules\Stats::monthData( $appData, $month );
			$days = is_array( $state['days'] ?? null ) ? $state['days'] : [];
			ksort( $days );

			$dayRows			= [];
			$totalViews		= 0;
			$uriTotals		= [];
			$referrerTotals	= [];
			$pages				= [];

			foreach( $days as $day => $entry ) {

				if( is_array( $entry ) === false )
					continue;

				$kept = 0;

				foreach( is_array( $entry['uris'] ?? null ) ? $entry['uris'] : [] as $uri => $views ) {

					$uri = (string) $uri;

					if( isset( $pages[$uri] ) === false )
						$pages[$uri] = \Nino\Modules\Stats::isPage( $appData, $uri );

					if( $pages[$uri] === false )
						continue;

					$kept += (int) $views;
					$uriTotals[$uri] = ( $uriTotals[$uri] ?? 0 ) + (int) $views;
				}

				// A day that had files and nothing else is not a day with data
				if( $kept === 0 )
					continue;

				$dayRows[] = [ 'day' => (string) $day, 'total' => $kept ];
				$totalViews += $kept;

				foreach( is_array( $entry['referrers'] ?? null ) ? $entry['referrers'] : [] as $host => $views )
					$referrerTotals[(string) $host] = ( $referrerTotals[(string) $host] ?? 0 ) + (int) $views;
			}

			arsort( $uriTotals );
			arsort( $referrerTotals );

			$fills		= [];
			$uriRows	= [];
			foreach( array_slice( $uriTotals, 0, self::TOP_LIMIT, true ) as $uri => $views )
				$uriRows[] = [ 'uri' => $uri, 'title' => self::_title( $appData, $uri, $fills ), 'views' => $views ];

			$referrerRows = [];
			foreach( array_slice( $referrerTotals, 0, self::TOP_LIMIT, true ) as $host => $views )
				$referrerRows[] = [ 'host' => $host, 'views' => $views ];

			\Nino\Http::ok( $request, [
				'month'			=> $month,
				'days'			=> $dayRows,
				'totals'		=> [ 'views' => $totalViews, 'days' => count( $dayRows ) ],
				'uris'			=> $uriRows,
				'referrers'	=> $referrerRows,
			] );
		}

		/**
		 *	The title of the page a counted uri was: '/_nino/webpage<route uri>/title'
		 *	in the locale of the route that answers it, else in the native one,
		 *	read from the project's global and locale text files the way
		 *	\Nino\Modules\Seo reads them for llms.txt - a request carries one
		 *	locale, and this lists every page's own. The route is resolved as
		 *	the request was, wildcards included; the title belongs to its
		 *	internal uri, not to the address a visitor typed.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					As counted
		 *	@param		array 		&$fills				Text files read so far, by locale - the caller's memo
		 *
		 *	@return 	string										'' where no route or no text has one
		 */
		private static function _title( array &$appData, string $uri, array &$fills ): string {

			$route = \Nino\Http::requestRoute( $appData, $uri, 'GET' );

			if( is_array( $route ) === false || is_string( $route['uri'] ?? null ) === false )
				return '';

			$locales = [ \Nino\Locales::getNativeLocale( $appData ) ];

			if( is_string( $route['locale'] ?? null ) === true )
				array_unshift( $locales, $route['locale'] );

			foreach( $locales as $locale ) {

				if( isset( $fills[$locale] ) === false )
					$fills[$locale] = array_merge(
						(array) \Nino\Filesystem::getFileContent( $appData, $appData['/nino/locales/textfiles']. '/global.php', [] ),
						(array) \Nino\Filesystem::getFileContent( $appData, $appData['/nino/locales/textfiles']. '/'. $locale. '.php', [] )
					);

				$title = trim( (string) ( $fills[$locale]['[[/_nino/webpage'. $route['uri']. '/title]]'] ?? '' ) );

				if( $title !== '' )
					return $title;
			}

			return '';
		}
	}
}
