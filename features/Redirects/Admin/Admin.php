<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Redirects\Admin		The /_admin panel of the Redirects feature - see
 *											features/Redirects/README.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Redirects {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Redirects\Admin		The "Redirects" panel. Two screens:
	 *
	 *										  Rules			every rule, what it answers and where it
	 *														sends, with the editor under it and a probe
	 *														that says which rule would catch a path
	 *										  Missing		the addresses nothing answered, most asked
	 *														for first, each with the one button that
	 *														turns it into a rule
	 *
	 *										The second is why the first is usable. A redirect
	 *										nobody knows is missing does not get written, and the
	 *										only place a site finds out which addresses people are
	 *										still asking for is its own 404s.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/redirects/manage';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'redirects/list'		=> [ self::class, 'apiList' ],
				'redirects/save'		=> [ self::class, 'apiSave' ],
				'redirects/delete'	=> [ self::class, 'apiDelete' ],
				'redirects/forget'	=> [ self::class, 'apiForget' ],
				'redirects/probe'		=> [ self::class, 'apiProbe' ],
			];
		}

		/*	The group named here is decorative: \Nino\Admin\Admin::_entry() puts
			every panel a feature brought into 'features' regardless, so granting
			that one group stays a bounded grant	*/
		public static function nav(): array {
			return [ 'redirects', '/_admin/nav/redirects', 40, 'system' ];
		}

		/**
		 *	A Dashboard tile: how many addresses are still being asked for that
		 *	nothing answers. Not the number of rules - a rule that works is not
		 *	news, and a tile is for what somebody should look at
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null							null while there is nothing to say
		 */
		public static function summary( array &$appData ): ?array {

			$file = Rules::read( $appData );

			if( $file['misses'] === [] )
				return null;

			return [
				'value' => (string) count( $file['misses'] ),
				'label' => '/_admin/redirects/label/tile',
			];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>';
		}

		public static function panes(): array {
			return [ 'redirects-rules', 'redirects-misses' ];
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ),
			];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'redirects/save'		=> 'Save redirect ('. ( is_string( $data['from'] ?? null ) === true ? $data['from'] : '?' ). ')',
				'redirects/delete'	=> 'Delete redirect ('. ( is_string( $data['from'] ?? null ) === true ? $data['from'] : '?' ). ')',
				'redirects/forget'	=> 'Forget unanswered addresses',
				default	=> '',
			};
		}

		/**
		 *	Everything both screens draw
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$notes	= [];
			$file		= Rules::read( $appData, $notes );
			$misses	= [];

			foreach( $file['misses'] as $path => $miss )
				$misses[] = [ 'path' => $path, 'count' => $miss['count'], 'last' => $miss['last'] ];

			\Nino\Http::ok( $request, [
				'rules'			=> $file['rules'],
				'misses'		=> $misses,
				'statuses'	=> Rules::STATUSES,
				// What the panel has to say about itself rather than guess: the
				// switch that decides whether the second screen fills at all,
				// and the ceiling it fills to
				'recording'	=> \Nino\Modules\Redirects::records( $appData ),
				'limit'			=> Rules::MISS_LIMIT,
				'notes'			=> self::_notes( $appData, $notes ),
			] );
		}

		/**
		 *	Add a rule, or replace the one that answered a path before.
		 *
		 *	'was' is the path the rule had when the editor opened, so renaming
		 *	the address a rule answers is one save rather than a delete and an
		 *	add - and so two people editing different rules cannot overwrite
		 *	each other by both posting a list
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data 		= \Nino\Admin\Admin::postData();
			$from 		= Rules::path( (string) ( $data['from'] ?? '' ) );
			$to 			= Rules::target( (string) ( $data['to'] ?? '' ) );
			$was 			= Rules::path( (string) ( $data['was'] ?? '' ) );
			$subtree	= ( $data['subtree'] ?? false ) === true;
			$status		= (int) ( $data['status'] ?? 301 );

			if( $from === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/from' ) );
				return;
			}

			if( $to === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/to' ) );
				return;
			}

			if( in_array( $status, Rules::STATUSES, true ) === false ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/status' ) );
				return;
			}

			$loop = Rules::loops( $from, $to, $subtree );

			if( $loop !== '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/loop', self::_say( $appData, $loop ) ) );
				return;
			}

			$file 	= Rules::read( $appData );

			/*	Renaming a rule onto an address another rule already answers
				used to take that other rule with it, hits and all, and answer
				200: the loop below recognises the one being edited by its old
				address and by its new one, so both of them were "the one" and
				the second was dropped. A rule that silently went away is the
				one kind of mistake nobody goes looking for, so this is a
				refusal like the others rather than a merge - the operator can
				still delete the rule that is in the way and rename afterwards	*/
			if( $was !== '' && $was !== $from
				&& count( array_filter( $file['rules'], static fn( array $rule ): bool => $rule['from'] === $from ) ) > 0 ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/taken', $from ) );
				return;
			}

			$rules	= [];
			$kept		= false;

			foreach( $file['rules'] as $rule ) {

				// The one being edited, wherever it was and whatever it is
				// called now
				if( $rule['from'] === $was || $rule['from'] === $from ) {
					if( $kept === true )
						continue;
					$kept 	= true;
					$rules[]= [ 'from' => $from, 'to' => $to, 'status' => $status, 'subtree' => $subtree, 'hits' => $rule['hits'], 'last' => $rule['last'] ];
					continue;
				}

				$rules[] = $rule;
			}

			if( $kept === false )
				$rules[] = [ 'from' => $from, 'to' => $to, 'status' => $status, 'subtree' => $subtree, 'hits' => 0, 'last' => '' ];

			/*	An address that now has a rule is no longer an address nothing
				answers, and leaving it in the list would have somebody write the
				same rule twice	*/
			$misses = $file['misses'];
			unset( $misses[$from] );

			$notes = [];
			$next	= Rules::normalize( [ 'rules' => $rules, 'misses' => $misses ], $notes );

			if( Rules::write( $appData, $next ) === false ) {
				\Nino\Http::fail( $request, 500, 'the rules could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'saved' => $from, 'rules' => $next['rules'], 'notes' => self::_notes( $appData, $notes ) ] );
		}

		/**
		 *	Remove one rule
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$from = Rules::path( (string) ( \Nino\Admin\Admin::postData()['from'] ?? '' ) );

			if( $from === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/from' ) );
				return;
			}

			$file 	= Rules::read( $appData );
			$rules	= array_values( array_filter( $file['rules'], static fn( array $rule ): bool => $rule['from'] !== $from ) );

			if( count( $rules ) === count( $file['rules'] ) ) {
				\Nino\Http::fail( $request, 404, self::_say( $appData, '/_admin/redirects/error/unknown', $from ) );
				return;
			}

			if( Rules::write( $appData, [ 'rules' => $rules, 'misses' => $file['misses'] ] ) === false ) {
				\Nino\Http::fail( $request, 500, 'the rules could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'deleted' => $from, 'rules' => $rules ] );
		}

		/**
		 *	Forget the addresses nothing answered - all of them, or the one
		 *	named. Not a rule and not a promise: the next request for it puts it
		 *	back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiForget( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$file		= Rules::read( $appData );
			$path		= Rules::path( (string) ( \Nino\Admin\Admin::postData()['path'] ?? '' ) );
			$misses	= $file['misses'];

			if( $path === '' )
				$misses = [];
			else
				unset( $misses[$path] );

			if( Rules::write( $appData, [ 'rules' => $file['rules'], 'misses' => $misses ] ) === false ) {
				\Nino\Http::fail( $request, 500, 'the list could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'forgotten' => $path, 'misses' => array_keys( $misses ) ] );
		}

		/**
		 *	What would happen to a path. Three answers, and telling them apart
		 *	is the whole point: a route answers it, a rule answers it, or
		 *	nothing does.
		 *
		 *	A redirect is invisible until somebody follows one, and a rule that
		 *	does not fire looks exactly like a rule that does not exist
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiProbe( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$path = Rules::path( (string) ( \Nino\Admin\Admin::postData()['path'] ?? '' ) );

			if( $path === '' ) {
				\Nino\Http::fail( $request, 400, self::_say( $appData, '/_admin/redirects/error/from' ) );
				return;
			}

			// The same question the callback asks, in the same order, or a
			// probe would answer about a site this is not
			if( \Nino\Http::requestRoute( $appData, $path, 'GET' ) !== null ) {
				\Nino\Http::ok( $request, [ 'path' => $path, 'answer' => 'route' ] );
				return;
			}

			$match = Rules::match( Rules::read( $appData )['rules'], $path );

			\Nino\Http::ok( $request, $match === null
				? [ 'path' => $path, 'answer' => 'nothing' ]
				: [ 'path' => $path, 'answer' => 'rule', 'from' => $match['from'], 'to' => \Nino\Modules\Redirects::address( $appData, $match['to'] ), 'status' => $match['status'] ] );
		}

		/**
		 *	One of this panel's own fills, in the interface language, with %s
		 *	filled in where it carries one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$fill					One of this panel's fill keys
		 *	@param		array|string	$inserts		A token map, or one value for '%s'
		 *
		 *	@return 	string
		 */
		private static function _say( array &$appData, string $fill, array|string $inserts = '' ): string {

			$inserts	= is_array( $inserts ) === true ? $inserts : [ '%s' => $inserts ];
			$fills		= \Nino\Admin\Admin::textFills( $appData, self::text(), \Nino\Admin\Admin::sessionLocale( $appData ) );
			$text			= $fills['[['. $fill. ']]'] ?? '';

			if( is_string( $text ) === false || $text === '' )
				return implode( ' ', $inserts );

			return strtr( $text, $inserts );
		}

		/**
		 *	The notes \Nino\Modules\Redirects\Rules left, in the operator's own
		 *	language.
		 *
		 *	Rules answers with a fill key and what to put in it rather than with
		 *	a sentence: what a stored file is held to is its business, which
		 *	language the workbench says it in is this panel's. A note about a
		 *	dropped loop carries the reason as a key of its own under '%r',
		 *	because the reason is a fill too
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$notes				{ key, inserts } each
		 *
		 *	@return 	array								Plain sentences
		 */
		private static function _notes( array &$appData, array $notes ): array {

			$said = [];

			foreach( $notes as $note ) {

				if( is_array( $note ) === false )
					continue;

				$inserts = is_array( $note['inserts'] ?? null ) === true ? $note['inserts'] : [];

				if( isset( $inserts['%r'] ) === true )
					$inserts['%r'] = self::_say( $appData, (string) $inserts['%r'] );

				$said[] = self::_say( $appData, (string) ( $note['key'] ?? '' ), $inserts );
			}

			return $said;
		}
	}
}
