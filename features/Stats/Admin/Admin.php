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
	 *										selector, a bar per day and the two top-50 tables (uris,
	 *										referrer hosts). Read-only - the counting itself happens
	 *										in Stats.php's callback, this panel only ever reads what
	 *										is already on disk and aggregates it for display.
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
		 *	bar row, the grand total, and the top 50 uris and referrer hosts
		 *	across the whole month.
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

			foreach( $days as $day => $entry ) {

				if( is_array( $entry ) === false )
					continue;

				$total = (int) ( $entry['total'] ?? 0 );
				$dayRows[] = [ 'day' => (string) $day, 'total' => $total ];
				$totalViews += $total;

				foreach( is_array( $entry['uris'] ?? null ) ? $entry['uris'] : [] as $uri => $views )
					$uriTotals[(string) $uri] = ( $uriTotals[(string) $uri] ?? 0 ) + (int) $views;

				foreach( is_array( $entry['referrers'] ?? null ) ? $entry['referrers'] : [] as $host => $views )
					$referrerTotals[(string) $host] = ( $referrerTotals[(string) $host] ?? 0 ) + (int) $views;
			}

			arsort( $uriTotals );
			arsort( $referrerTotals );

			$uriRows = [];
			foreach( array_slice( $uriTotals, 0, self::TOP_LIMIT, true ) as $uri => $views )
				$uriRows[] = [ 'uri' => $uri, 'views' => $views ];

			$referrerRows = [];
			foreach( array_slice( $referrerTotals, 0, self::TOP_LIMIT, true ) as $host => $views )
				$referrerRows[] = [ 'host' => $host, 'views' => $views ];

			\Nino\Http::ok( $request, [
				'month'			=> $month,
				'days'			=> $dayRows,
				'totals'		=> [ 'views' => $totalViews, 'days' => count( $days ) ],
				'uris'			=> $uriRows,
				'referrers'	=> $referrerRows,
			] );
		}
	}
}
