<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Surface\Devices		The devices of the owner that stay signed in to the surface: the cookie crm_remember, the table owner_devices and the way back into a session without the password.
	 *												The cookie is a key to the workbench (the session it opens is the account's own), so it is made only on the owner's word - the button that keeps this device signed in,
	 *												for a session that holds the permission of the CRM - and is kept the way Members keeps the devices of a member:
	 *
	 *												- a token is 32 random bytes; the table keeps its sha256 and never the token. The cookie is '<id of the row>.<token>', HttpOnly, SameSite=Lax, Secure where the request is
	 *													https (or the setting /nino/session/force-secure-cookie says so), for the path of the surface alone: /crm under the directory of the website, where a request of the workbench or of the site does not carry it. On the host the CRM has of its own the path is / and has to be: the start page of the CRM there is / itself (mapped to /crm), and a cookie is sent for the paths below its path only, so /crm would leave out the one address the owner types. The host answers the CRM and the workbench (/_admin, where the app has no name of its own) and nothing else, so the cookie is sent to the workbench too - and is taken for no sign-in there: only a GET of one of the pages of the surface signs a device in (see pages()), never the workbench, the sign-in of the kernel, a file, a manifest, an icon or the endpoint
	 *												- at most MAX devices for an account (the oldest goes), DAYS days from the last visit (a visit starts the days again), the end of them in the row
	 *												- the token is replaced at every visit that signs in; the one it replaced signs in for GRACE seconds more, with no new token and no write, since two tabs that open at once bring
	 *													the same cookie
	 *												- a cookie that is no good - not of the form, a row that is gone, a token the row does not hold, run out, an account that is gone or off or whose password is not the one the
	 *													device was made under - is deleted and counts as a try of its address; an address that has made TRIES of them in an hour is not counted and its cookie not deleted any more:
	 *													the count never stands in the way of a good token. The hashes are compared with hash_equals()
	 *												- only the request for one of the pages itself (GET /crm and GET /crm/todo, and a page of a feature below /crm that has told allow() so; no HEAD, no POST, nothing of the manifests, the icon, the assets or the endpoint) with no session signs in; the session is opened by
	 *													\Nino\Auth::resumeUser(), and the activity log says which account was resumed - never a token
	 *
	 *												crm/device-remember		Make the token of this device and set the cookie. The answer says what the page shows (see status())
	 *												crm/devices-forget		Take every device of the account out of the table and delete the cookie; every session of the account but the one that asks ends
	 *												crm/signout				Take this device out of the table, delete its cookie and end the session
	 *
	 *												The mark of the password is the sha256 of the whole hash the account has (it is another one after a change of the password), kept in the row.
	 */
	class Devices {

		// The cookie, how many devices an account has, how long a device stays, how long the replaced token still signs in
		public const string COOKIE = 'crm_remember';
		public const int MAX = 10;
		public const int DAYS = 90;
		public const int GRACE = 30;

		// The tries of an address with a cookie that is no good in a window, as the counter file of Members counts them
		public const int TRIES = 20;
		public const int WINDOW = 3600;
		public const string COUNTER = 'crm-remember';

		// The longest user agent kept
		private const int AGENT = 120;

		// The key of the pages a feature has added to the two of the surface, in the app data of the request
		private const string PAGES = './crm/surface/devices/pages';
		// The first parts of an address below /crm that are the surface's own: its endpoint, its files, its icons and its manifest
		private const array RESERVED = [ 'api', 'assets', 'icon', 'icon-192', 'icon-512', 'manifest' ];
		// ...and the first parts of the pages of the core below /crm: no package has them
		private const array CORE_PAGES = [ 'todo' ];
		private const string OWNERS = './crm/surface/devices/owners';

		/**
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return [ 'crm/device-remember' => [ self::class, 'apiRemember' ], 'crm/devices-forget' => [ self::class, 'apiForget' ], 'crm/signout' => [ self::class, 'apiSignout' ] ];
		}

		/**
		 *	The pages a device signs in again at: the two of the surface and those a feature added by allow()
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The addresses below the directory of the project, without a closing slash
		 */
		public static function pages( array &$appData ): array {

			return array_merge( [ \Nino\Modules\Crm\Surface::ROUTE, Todo::ROUTE ], (array) ( $appData[self::PAGES] ?? [] ) );
		}

		/**
		 *	Let a page of a package sign a device in again: the cookie is for /crm, and a page below it is a page of the surface to the visitor. Called from the package's init(), where the page is registered. The page has to be
		 *	one the calling package entered itself, with Apps::add() (an app) or Menu::add() (an entry of the rail): a package does not let the devices in at the page of another, and not at a route of the surface
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$route				The address of the page below the directory of the project: /crm/<name>, lower case letters, digits, hyphens and slashes, no slash at the end
		 *	@param		array 		$opts					package (required): the key of the package that enters the page, see Register::package()
		 *
		 *	@return 	bool											Whether the page is on the list now; false for an address that is not below /crm, that is the surface's own or the core's, or that the package did not enter
		 */
		public static function allow( array &$appData, string $route, array $opts = [] ): bool {

			if( array_diff_key( $opts, [ 'package' => 1 ] ) !== [] || \Nino\Modules\Crm\Register::package( $appData, $opts['package'] ?? null ) === null || self::isRoute( $route ) === false || ( $appData[self::OWNERS][$route] ?? null ) !== $opts['package'] )
				return false;

			return self::let( $appData, $route );
		}

		/**
		 *	Put a page on the list the devices come back to
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$route
		 *
		 *	@return 	bool											true
		 */
		private static function let( array &$appData, string $route ): bool {

			if( in_array( $route, self::pages( $appData ), true ) === false )
				$appData[self::PAGES][] = $route;

			return true;
		}

		/**
		 *	Note which package entered a page: called by Apps::add() and Menu::add(), which are the places a page is entered at. The first package to enter a route keeps it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$route
		 *	@param		string		$package			The key of the package that enters it, already known to be one
		 *
		 *	@return 	bool											Whether the package has the route now
		 */
		public static function claim( array &$appData, string $route, string $package ): bool {

			if( self::isRoute( $route ) === false )
				return false;

			$appData[self::OWNERS][$route] ??= $package;

			return $appData[self::OWNERS][$route] === $package;
		}

		/**
		 *	Whether an address is one a package may add to the surface: below /crm, lower case letters, digits, hyphens and slashes, no slash at the end, and none of the core's - its endpoint, its files, its icons,
		 *	its manifest and its page of the tasks. allow(), Menu::add() and Apps::add() ask it
		 *
		 *	@param		string		$route
		 *	@param		bool			$core					The core asks for itself: its own pages are the ones it may name
		 *
		 *	@return 	bool
		 */
		public static function isRoute( string $route, bool $core = false ): bool {

			return preg_match( '~^/crm/[a-z0-9][a-z0-9/-]*(?<!/)$~D', $route ) === 1 && str_contains( $route, '//' ) === false
				&& in_array( strtok( substr( $route, 5 ), '/' ), array_merge( self::RESERVED, $core === true ? [] : self::CORE_PAGES ), true ) === false;
		}

		/**
		 *	Whether the table of the devices can be read: the question a login is shown for
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function readable( array &$appData ): bool {

			try {
				\Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM owner_devices WHERE id < 0' );
			}
			catch( \PDOException $e ) {
				return false;
			}

			return true;
		}

		/**
		 *	The line of the activity log of an action of this class - no account, no token
		 *
		 *	@param		string		$action
		 *	@param		array 		$data
		 *
		 *	@return 	string
		 */
		public static function log( string $action, array $data ): string {

			return $action === 'crm/signout' ? 'Signed out of the CRM' : ( $action === 'crm/device-remember' ? 'Kept a device signed in to the CRM' : ( $action === 'crm/devices-forget' ? 'Signed out the remembered devices of the CRM' : '' ) );
		}

		/**
		 *	crm/device-remember
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiRemember( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$user = \Nino\Auth::getCurrentUser( $appData );

			\Nino\Modules\Crm\Api::run( $request, static function() use ( &$appData, &$request, $user ): void {

				if( is_array( $user ) === false || self::remember( $appData, (string) $user['mail'] ) === false ) {
					\Nino\Http::fail( $request, 500, 'the device could not be kept', 'crm_device_write' );

					return;
				}

				\Nino\Http::ok( $request, [ 'remembered' => true ] + self::status( $appData, (string) $user['mail'] ) );
			} );
		}

		/**
		 *	crm/devices-forget
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiForget( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$user = \Nino\Auth::getCurrentUser( $appData );

			\Nino\Modules\Crm\Api::run( $request, static function() use ( &$appData, &$request, $user ): void {

				if( is_array( $user ) === true )
					self::signOutOthers( $appData, (string) $user['mail'] );

				// The token of the session is a new one where the session was taken up again: the page carries the one that is good now
				\Nino\Http::ok( $request, self::status( $appData, is_array( $user ) === true ? (string) $user['mail'] : '' ) + [ 'csrf' => \Nino\Csrf::getToken( $appData ) ] );
			} );
		}

		/**
		 *	crm/signout: the account leaves the CRM on this device: the row of this device and its cookie go - a device that is signed out is not signed in again by it - and the session ends. The other devices stay
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function apiSignout( array &$appData, array &$request ): void {

			if( \Nino\Modules\Crm\Values::permit( $appData, $request, \Nino\Modules\Crm::MANAGE_PERM ) === false )
				return;

			$user = \Nino\Auth::getCurrentUser( $appData );
			$cookie = self::cookie();

			\Nino\Modules\Crm\Api::run( $request, static function() use ( &$appData, &$request, $user, $cookie ): void {

				if( is_array( $user ) === true && $cookie !== null )
					\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE id = :id AND user_mail = :mail', [ 'id' => $cookie[0], 'mail' => (string) $user['mail'] ] );

				self::setCookie( $appData, '', 0 );
				\Nino\Auth::logoutUser( $appData );
				\Nino\Http::ok( $request, [ 'signedout' => true ] );
			} );
		}

		/**
		 *	What the page shows of the devices: whether this device stays signed in (its cookie names a row of the account that is good) and how many devices the account has
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$mail
		 *
		 *	@return 	array											remembered (bool), count (int)
		 */
		public static function status( array &$appData, string $mail ): array {

			$now = time();
			$row = self::rowOf( $appData, self::cookie() );
			$hash = self::cookie() === null ? '' : hash( 'sha256', self::cookie()[1] );
			$mine = $row !== null && $row['user_mail'] === $mail && (int) $row['expires'] > $now && self::matches( $row, $hash, $now ) !== '' && self::mark( $appData, $mail ) === $row['pw_mark'];
			$count = \Nino\Modules\Crm\Db::row( $appData, 'SELECT COUNT(*) AS n FROM owner_devices WHERE user_mail = :mail AND expires > :now AND pw_mark = :mark', [ 'mail' => $mail, 'now' => $now, 'mark' => self::mark( $appData, $mail ) ] );

			return [ 'remembered' => $mine, 'count' => (int) ( $count['n'] ?? 0 ) ];
		}

		/**
		 *	The mark of the password an account has: the sha256 of its whole hash
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$mail
		 *
		 *	@return 	string										'' for an account that is not there
		 */
		public static function mark( array &$appData, string $mail ): string {

			$user = \Nino\Auth::getUser( $appData, $mail );

			return $user === false ? '' : hash( 'sha256', (string) $user['pw'] );
		}

		/**
		 *	Make the token of this device for an account that is signed in, keep its hash and set the cookie. The entries of the account that have run out go, the token of this device from an
		 *	earlier time - if the browser brings one - is replaced, and the oldest goes where there are more than MAX
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$mail									The account that is signed in
		 *
		 *	@return 	bool											Whether the cookie was set
		 */
		public static function remember( array &$appData, string $mail ): bool {

			$mark = self::mark( $appData, $mail );

			if( $mark === '' )
				return false;

			$token = self::token();
			$now = time();
			$old = self::cookie();
			$oldHash = $old === null ? '' : hash( 'sha256', $old[1] );
			$pdo = \Nino\Modules\Crm\Db::pdo( $appData );
			$id = 0;

			$pdo->beginTransaction();

			try {

				\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE user_mail = :mail AND ( expires <= :now OR pw_mark <> :mark )', [ 'mail' => $mail, 'now' => $now, 'mark' => $mark ] );

				if( $old !== null && $oldHash !== '' )
					\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE user_mail = :mail AND id = :id', [ 'mail' => $mail, 'id' => $old[0] ] );

				// The oldest go until there is room for this one
				foreach( \Nino\Modules\Crm\Db::rows( $appData, 'SELECT id FROM owner_devices WHERE user_mail = :mail ORDER BY created_at DESC, id DESC', [ 'mail' => $mail ] ) as $at => $row )
					if( $at >= self::MAX - 1 )
						\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE id = :id', [ 'id' => $row['id'] ] );

				\Nino\Modules\Crm\Db::exec( $appData, 'INSERT INTO owner_devices ( user_mail, token_sha256, created_at, last_seen, expires, pw_mark, ua ) VALUES ( :mail, :hash, :now, :now, :expires, :mark, :ua )',
					[ 'mail' => $mail, 'hash' => hash( 'sha256', $token ), 'now' => $now, 'expires' => $now + self::DAYS * 86400, 'mark' => $mark, 'ua' => self::agent() ] );
				$id = (int) $pdo->lastInsertId();
				$pdo->commit();
			}
			catch( \Throwable $e ) {
				$pdo->rollBack();

				return false;
			}

			self::setCookie( $appData, $id. '.'. $token, self::DAYS * 86400 );

			return true;
		}

		/**
		 *	Take every device of an account out of the table and delete the cookie of this request
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$mail
		 *
		 *	@return 	void
		 */
		public static function forgetAll( array &$appData, string $mail ): void {

			\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE user_mail = :mail', [ 'mail' => $mail ] );
			self::setCookie( $appData, '', 0 );
		}

		/**
		 *	Every device of the account is forgotten and every session of the account but the one that asks is over: the sessions a device has opened with its cookie end with the cookie. The session that asks is taken up again, so
		 *	that it stays open
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$mail									The account that is signed in in this request
		 *
		 *	@return 	void
		 */
		private static function signOutOthers( array &$appData, string $mail ): void {

			self::forgetAll( $appData, $mail );

			if( \Nino\Auth::logoutAllSessions( $appData, $mail ) === true )
				\Nino\Auth::resumeUser( $appData, $mail );
		}

		/**
		 *	'/nino/http/response': sign in the visitor who has no session and brings the cookie of a device (see the description of the class)
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackResume( array &$appData, array &$request ): void {

			try {
				self::resume( $appData, $request );
			}
			catch( \PDOException $e ) {
				// A database that cannot be read signs nobody in; the page says so (Surface::gate(), which asks readable())
			}
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		private static function resume( array &$appData, array &$request ): void {

			if( is_string( $_COOKIE[self::COOKIE] ?? null ) === false || $_COOKIE[self::COOKIE] === '' )
				return;

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );

			if( ( $request['/nino/http/request']['rawMethod'] ?? '' ) !== 'GET' || in_array( rtrim( (string) strtok( $uri, '?#' ), '/' ), self::pages( $appData ), true ) === false || \Nino\Auth::getCurrentUser( $appData ) !== false )
				return;

			$cookie = self::cookie();
			$now = time();
			$mail = '';
			$rotate = false;

			if( $cookie !== null ) {

				$row = self::rowOf( $appData, $cookie );
				$hash = hash( 'sha256', $cookie[1] );
				$which = $row === null ? '' : self::matches( $row, $hash, $now );

				// The token a visit replaced, brought after its grace: somebody has a copy of a cookie that the owner's browser has moved on from. The device is not one any more, the row goes with the cookie
				if( $row !== null && $which === '' && (string) $row['previous_sha256'] !== '' && hash_equals( (string) $row['previous_sha256'], $hash ) === true )
					\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE id = :id', [ 'id' => $row['id'] ] );
				$user = $row === null ? false : \Nino\Auth::getUser( $appData, (string) $row['user_mail'] );

				if( $row !== null && $which !== '' && (int) $row['expires'] > $now && $user !== false && $user['status'] === \Nino\Auth::STATUS_ACTIVE ) {

					// A device of an account that may not open the surface is dead as well: the session it would open is the account's own
					if( self::mark( $appData, (string) $row['user_mail'] ) === $row['pw_mark'] && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM, (string) $row['user_mail'] ) === true ) {

						$mail = (string) $row['user_mail'];
						$rotate = $which === 'current';
					}
					else
						\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE id = :id', [ 'id' => $row['id'] ] );
				}
			}

			if( $mail !== '' && $rotate === true ) {

				// The same row is taken once: a second request that brought the same token has lost the race and is signed in on the grace of the token that was replaced
				$next = self::token();
				$taken = \Nino\Modules\Crm\Db::exec( $appData, 'UPDATE owner_devices SET token_sha256 = :next, previous_sha256 = :previous, rotated_at = :now, last_seen = :now, expires = :expires, ua = :ua WHERE id = :id AND token_sha256 = :previous',
					[ 'next' => hash( 'sha256', $next ), 'previous' => hash( 'sha256', $cookie[1] ?? '' ), 'now' => $now, 'expires' => $now + self::DAYS * 86400, 'ua' => self::agent(), 'id' => $cookie[0] ?? 0 ] );

				if( $taken === 1 )
					self::setCookie( $appData, ( $cookie[0] ?? 0 ). '.'. $next, self::DAYS * 86400 );
			}

			if( $mail === '' ) {

				// Not a way in: the cookie goes and the address has tried once - unless it has tried TRIES times, when the count is left alone. The counter has its own file (COUNTER), so the key is the address
				// alone: the counter reads it by its bucket (a whole /64 for an IPv6 address), which a key with a prefix would hide. No address, no count: one shared key would lock everybody out
				$who = \Nino\Http::getClientIp( $appData );

				if( $who === '' )
					self::setCookie( $appData, '', 0 );
				elseif( \Nino\Feature::spent( $appData, self::COUNTER, $who, self::TRIES ) === false ) {
					self::setCookie( $appData, '', 0 );
					\Nino\Feature::count( $appData, self::COUNTER, $who, self::TRIES, self::WINDOW );
				}

				return;
			}

			if( \Nino\Auth::resumeUser( $appData, $mail ) === false )
				return;

			\Nino\Modules\Crm\Api::record( $appData, $mail, 'Resumed the session of '. $mail. ' from a remembered device' );
		}

		/**
		 *	'/nino/admin/action': an account that is signed out everywhere or switched off in the Users panel loses its devices as well - the cookie of one would sign it in again
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$event						action, panel, status, user, data
		 *
		 *	@return 	void
		 */
		public static function callbackAdminAction( array &$appData, array &$event ): void {

			$action = (string) ( $event['action'] ?? '' );
			$data = (array) ( $event['data'] ?? [] );

			if( ( $event['status'] ?? 0 ) !== 200 || is_string( $data['username'] ?? null ) === false || ( $action !== 'users/logoutall' && ( $action !== 'users/status' || ( $data['active'] ?? null ) !== false ) ) )
				return;

			try {
				\Nino\Modules\Crm\Db::exec( $appData, 'DELETE FROM owner_devices WHERE user_mail = :mail', [ 'mail' => $data['username'] ] );
			}
			catch( \PDOException $e ) {
				// The devices of an account that is signed out everywhere stay where the database cannot be written: said, never taken for done
				trigger_error( 'Crm: the devices of an account that was signed out could not be taken away, the database failed', E_USER_WARNING );
			}
		}

		/**
		 *	Which token of the row the hash is: 'current', 'previous' (the token a visit has just replaced, for GRACE seconds) or '' for none. Both are compared, whichever matches
		 *
		 *	@param		array 		$row
		 *	@param		string		$hash
		 *	@param		int				$now
		 *
		 *	@return 	string
		 */
		private static function matches( array $row, string $hash, int $now ): string {

			$current = hash_equals( (string) $row['token_sha256'], $hash );
			$previous = (string) $row['previous_sha256'] !== '' && hash_equals( (string) $row['previous_sha256'], $hash ) === true && $now - (int) $row['rotated_at'] <= self::GRACE;

			return $current === true ? 'current' : ( $previous === true ? 'previous' : '' );
		}

		/**
		 *	The row a cookie names
		 *
		 *	@param		array 		&$appData
		 *	@param		array|null	$cookie				[ id, token ]
		 *
		 *	@return 	array|null
		 */
		private static function rowOf( array &$appData, ?array $cookie ): ?array {

			return $cookie === null ? null : \Nino\Modules\Crm\Db::row( $appData, 'SELECT * FROM owner_devices WHERE id = :id', [ 'id' => $cookie[0] ] );
		}

		/**
		 *	The cookie of this request taken apart
		 *
		 *	@return 	array|null								[ id, token ]; null for none and for one that is not of the form
		 */
		private static function cookie(): ?array {

			$raw = is_string( $_COOKIE[self::COOKIE] ?? null ) === true ? $_COOKIE[self::COOKIE] : '';

			return preg_match( '/^([1-9][0-9]{0,9})\.([A-Za-z0-9_-]{43})$/D', $raw, $found ) === 1 ? [ (int) $found[1], $found[2] ] : null;
		}

		/**
		 *	@return 	string										32 random bytes, base64url
		 */
		private static function token(): string {

			return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
		}

		/**
		 *	The user agent of the request, cut: the owner sees what a device was by it, nobody reads more
		 *
		 *	@return 	string
		 */
		private static function agent(): string {

			return mb_substr( (string) preg_replace( '/[\x00-\x1f\x7f]/', '', (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, self::AGENT, 'UTF-8' );
		}

		/**
		 *	Set the cookie of a device - or, with no seconds, delete it. HttpOnly, SameSite=Lax, Secure where the request is https, for the path of the surface (/crm under the directory of the project, and / on the host the CRM has of its own,
		 *	whose start page it is). Where that is not the path /crm, the cookie a device was given for /crm before is deleted with it: the two are never both there. The lines are kept in the app data
		 *	for the request ('./crm/cookies') and sent unless the headers have gone: added, never one that replaces the session cookie
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$value
		 *	@param		int				$seconds
		 *
		 *	@return 	void
		 */
		private static function setCookie( array &$appData, string $value, int $seconds ): void {

			$secure = ( $appData['/nino/session/force-secure-cookie'] ?? false ) === true || ( ( $_SERVER['HTTPS'] ?? '' ) !== '' && ( $_SERVER['HTTPS'] ?? '' ) !== 'off' );
			$old = rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). \Nino\Modules\Crm\Surface::ROUTE;
			$path = \Nino\Modules\Crm\Hosts::crm( $appData ) !== '' && \Nino\Modules\Crm\Hosts::onCrm( $appData ) === true ? '/' : $old;
			$lines = [ self::cookieLine( $value, $seconds, $path, $secure ) ];

			if( $path !== $old )
				$lines[] = self::cookieLine( '', 0, $old, $secure );

			$appData['./crm/cookies'] = array_merge( (array) ( $appData['./crm/cookies'] ?? [] ), $lines );

			if( headers_sent() === false )
				foreach( $lines as $line )
					header( 'Set-Cookie: '. $line, false );
		}

		/**
		 *	The line of a Set-Cookie of the cookie of a device
		 *
		 *	@param		string		$value
		 *	@param		int				$seconds				0 or less deletes it
		 *	@param		string		$path
		 *	@param		bool			$secure
		 *
		 *	@return 	string
		 */
		private static function cookieLine( string $value, int $seconds, string $path, bool $secure ): string {

			return self::COOKIE. '='. $value. '; Max-Age='. max( 0, $seconds ). '; Path='. $path. ( $seconds <= 0 ? '; Expires=Thu, 01 Jan 1970 00:00:00 GMT' : '' ). '; HttpOnly'. ( $secure === true ? '; Secure' : '' ). '; SameSite=Lax';
		}
	}
}
