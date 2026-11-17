<?php
declare(strict_types=1);

namespace Nino\Modules\Greeting {

	/**
	 *	Nino features					Greeting panel: the card as the Dashboard draws it for this account.
	 *												A workbench takes the script and the words of a feature through a
	 *												panel alone, and the card needs both; the three lines are settings
	 *												of the manifest, which the Features panel puts in this panel as its
	 *												Settings tab
	 */
	class Admin {

		public static function perm(): string {
			return '/_admin/greeting/manage';
		}

		public static function actions(): array {
			return [ 'greeting/list' => [ self::class, 'apiList' ] ];
		}

		public static function nav(): array {
			return [ 'greeting', '/_admin/greeting/nav', 90 ];
		}

		// The sign of the rail entry: Lucide message-circle, geometry and no view
		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>';
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/**
		 *	The card the Dashboard would be answered with for the account that asks
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			\Nino\Http::ok( $request, \Nino\Modules\Greeting::card( $appData ) );
		}
	}
}
