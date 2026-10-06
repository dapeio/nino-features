<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Newsletter\Admin		The /_admin panel of the Newsletter module - see docs/development.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Newsletter {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules						Optional modules
	 *	Newsletter				View + delete of the newsletter signups that
	 *												\Nino\Modules\Newsletter (in Newsletter.php beside
	 *												this) records - it reads them through
	 *												Newsletter::PATH, and a delete records the removal
	 *												through Newsletter::recordRemoval(). Project-root
	 *												/data, plain array file - not a workbench concern.
	 *												Besides apiDelete, entries also go away via the
	 *												self-service unsubscribe link (Modules\Newsletter).
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/newsletter/manage';

		public static function actions(): array {
			return [
				'newsletter/list' 	=> [ self::class, 'apiList' ],
				'newsletter/delete' => [ self::class, 'apiDelete' ],
			];
		}

		public static function nav(): array {
			return [ 'newsletter', '/_admin/nav/newsletter', 65, 'content' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mailbox-icon lucide-mailbox"><path d="M22 17a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9.5C2 7 4 5 6.5 5H18c2.2 0 4 1.8 4 4v8Z"/><polyline points="15,9 18,9 18,11"/><path d="M6.5 5C9 5 11 7 11 9.5V17a2 2 0 0 1-2 2"/><line x1="6" x2="7" y1="10" y2="10"/></svg>';
		}

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function summary( array &$appData ): array {
			return [ 'value' => self::count( $appData ), 'label' => '/_admin/dashboard/label/newsletter' ];
		}

		public static function log( string $action, array $data ): string {
			return $action === 'newsletter/delete' ? 'Delete Newsletter Subscriber '. ( $data['email'] ?? '' ) : '';
		}

		/**
		 *	How many subscribers are currently on file - the confirmed ones,
		 *	which summary() puts on the Dashboard's tile. A pending signup has
		 *	not agreed to anything yet and is not a subscriber
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function count( array &$appData ): int {

			$subscribed = 0;

			foreach( \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Newsletter::PATH, [] ) as $entry )
				if( is_array( $entry ) === true && self::_status( $entry ) === 'subscribed' )
					$subscribed++;

			return $subscribed;
		}

		/**
		 *	What an entry is: 'pending' until its confirm link was visited,
		 *	'subscribed' otherwise. An entry written before the double opt-in
		 *	flow has no status and counts as subscribed - the same reading
		 *	\Nino\Modules\Newsletter applies to it
		 *
		 *	@param		array 		$entry
		 *
		 *	@return 	string									'pending' or 'subscribed'
		 */
		private static function _status( array $entry ): string {
			return ( $entry['status'] ?? '' ) === 'pending' ? 'pending' : 'subscribed';
		}

		/**
		 *	List every recorded newsletter signup, most recent first, each with
		 *	its status ('pending' or 'subscribed'), the count of either and the
		 *	address of the page where a subscriber asks for an unsubscribe link
		 *	- the one thing a BCC mail can carry in place of a personal link
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$entries = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Newsletter::PATH, [] );

			/*	Without the token. It is not a field, it is a credential: presented
				as ?unsubscribe=<token> on the public route it takes that address
				off the list, and as ?confirm=<token> it confirms a signup, both
				without anything else. The panel never draws it and deletes by
				address, but Nino.admin.exportCsv() writes the union of every
				row's keys - so it went into a file that gets opened in a
				spreadsheet, mailed around and handed to a sending provider, and
				whoever held that file could unsubscribe the whole list.

				The ip stays: it is the record of a consent, which is what it was
				stored for, and it does not let anybody act	*/
			$counts = [ 'subscribed' => 0, 'pending' => 0 ];
			$listed = array_map(
				static function( mixed $entry ) use ( &$counts ): mixed {

					if( is_array( $entry ) === false )
						return $entry;

					unset( $entry['token'] );

					// Normalised, so the panel and the CSV read one value where an
					// entry from before the double opt-in flow has none
					$entry['status'] = self::_status( $entry );
					$counts[ $entry['status'] ]++;

					return $entry;
				},
				$entries
			);

			\Nino\Http::ok( $request, [
				'entries' 				=> array_reverse( $listed ),
				'counts' 					=> $counts,
				// Same https://[[/project/website/general/url]] convention as the links in the mails
				'unsubscribeUrl' 	=> 'https://'. \Nino\Html::renderHtml( $appData, '[[/project/website/general/url]]' ). '/.newsletter/unsubscribe',
			] );
		}

		/**
		 *	Delete one subscriber by email - the admin-side counterpart to
		 *	the visitor's own self-service unsubscribe link (see
		 *	Modules\Newsletter in Newsletter.php beside this)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$email = (string) ( \Nino\Admin\Admin::postData()['email'] ?? '' );

			if( $email === '' ) {
				\Nino\Http::fail( $request, 404, 'unknown email' );
				return;
			}

			$outcome = 'notfound';
			$readEntries = false;

			$written = \Nino\Filesystem::mutate( $appData, \Nino\Modules\Newsletter::PATH, function( array $entries ) use ( $email, &$appData, &$outcome, &$readEntries ): ?array {

				$readEntries = true;

				$filtered = array_values( array_filter( $entries, function( $entry ) use ( $email ) { return ( $entry['email'] ?? null ) !== $email; } ) );

				if( count( $filtered ) === count( $entries ) )
					return null;

				// Persist the durable removal before dropping the address. If that
				// write fails, leave the subscriber list untouched; if the following
				// list write fails, a retry is safe because the hash is idempotent.
				if( \Nino\Modules\Newsletter::recordRemoval( $appData, $email ) === false ) {
					$outcome = 'removal-failed';
					return null;
				}

				$outcome = 'ready';
				return $filtered;
			} );

			if( $readEntries === false ) {
				\Nino\Http::fail( $request, 500, 'subscriber list could not be locked' );
				return;
			}

			if( $outcome === 'notfound' ) {
				\Nino\Http::fail( $request, 404, 'unknown email' );
				return;
			}

			if( $outcome !== 'ready' || $written === false ) {
				\Nino\Http::fail( $request, 500, 'subscriber could not be deleted' );
				return;
			}

			\Nino\Http::ok( $request );
		}
	}

}
