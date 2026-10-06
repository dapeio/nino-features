<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\ProtectedArea\Admin		The /_admin panel of the Protected area feature - see docs/development.md
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\ProtectedArea {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	ProtectedArea\Admin	"Protected area" panel: the three things somebody who runs
	 *										a protected area does after the feature is set up and
	 *										does not want to open the Features panel for - change
	 *										the password, lock every session at once, and choose
	 *										the protected pages from a list of the site's own
	 *										pages instead of typing their uris. The settings the
	 *										feature keeps stay the Features panel's: this panel
	 *										reads and writes the same two (`paths`, `password`)
	 *										through \Nino\Features::saveSettings(), so nothing
	 *										here can disagree with that form.
	 *
	 *										The list is the persisted GET routes of config.php
	 *										that are pages (see _pages()). A page a visitor can
	 *										only reach through a prefix the developer typed in by
	 *										hand - a wildcard feature's records, a path with no
	 *										route - is not in it and is never touched: ticking
	 *										pages replaces the lines the list can name and keeps
	 *										every other one as it stands.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/protected/manage';

		// The same floor the kernel holds an account's password to
		// (\Nino\Admin\Admin, \Nino\Modules\Users\Admin). The ceiling is this
		// panel's own: a shared password is typed and passed around, and a
		// value past it is a paste that went wrong
		private const int MIN_PW_LENGTH = 8;
		private const int MAX_PW_LENGTH = 200;

		private const string FEATURE_KEY = 'protected';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'protected/state'		=> [ self::class, 'apiState' ],
				'protected/pages'		=> [ self::class, 'apiPages' ],
				'protected/password'	=> [ self::class, 'apiPassword' ],
				'protected/signout'	=> [ self::class, 'apiSignOut' ],
			];
		}

		public static function nav(): array {
			return [ 'protected', '/_admin/nav/protected', 40, 'system' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
		}

		public static function panes(): array {
			return [ 'protected-form' ];
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ) ];
		}

		// The panel's own strings, one <locale>.php per interface language
		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		/**
		 *	The activity-log line of a completed action. Never the password,
		 *	nor anything it could be recovered from: the line says that it was
		 *	changed and nothing more.
		 *
		 *	@param		string		$action
		 *	@param		array 		$data					What the action was posted
		 *
		 *	@return 	string
		 */
		public static function log( string $action, array $data ): string {

			return match( $action ) {
				'protected/password'	=> 'Changed the protected area password and signed everybody out',
				'protected/signout'		=> 'Signed everybody out of the protected area',
				'protected/pages'		=> 'Changed the protected pages ('. ( is_array( $data['paths'] ?? null ) ? count( $data['paths'] ) : 0 ). ' chosen)',
				default					=> '',
			};
		}

		/**
		 *	What the screen draws: whether a password is set (never the
		 *	password), the site's pages with what is protected of them, the
		 *	prefixes that are protected without being a page of the list, and
		 *	the length the password has to have
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiState( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, self::_state( $appData ) );
		}

		/**
		 *	Replace the protected pages the list can name with the posted
		 *	choice. Every posted path has to be one the server lists itself,
		 *	in full - this is a picker, not a way to type a prefix, which stays
		 *	the Features panel's job. The prefixes the list cannot name are kept
		 *	as they are, in their order and ahead of the choice.
		 *
		 *	An empty choice is accepted: it switches the protection of every
		 *	listed page off, which the screen asks about first.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiPages( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$posted = \Nino\Admin\Admin::postData()['paths'] ?? null;

			if( is_array( $posted ) === false || array_is_list( $posted ) === false ) {
				\Nino\Http::fail( $request, 400, 'paths must be a list' );
				return;
			}

			$listed		= array_flip( self::_listedPaths( self::_pages( $appData ) ) );
			$selected	= [];

			foreach( $posted as $path ) {

				if( is_string( $path ) === false || isset( $listed[$path] ) === false ) {
					\Nino\Http::fail( $request, 400, 'unknown page' );
					return;
				}

				$selected[$path] = true;
			}

			// Whatever the list cannot name stays: a line is the list's own when
			// its normalised form is one of the listed paths, and then it is the
			// choice that says whether it is still there
			$kept = [];

			foreach( (array) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'paths', [] ) as $line )
				if( is_string( $line ) === true && isset( $listed[ rtrim( $line, '/' ) ] ) === false )
					$kept[] = $line;

			$errors = \Nino\Features::saveSettings( $appData, self::FEATURE_KEY, [ 'paths' => array_merge( $kept, array_keys( $selected ) ) ] );

			if( self::_failed( $request, $errors ) === true )
				return;

			\Nino\Http::ok( $request, self::_state( $appData ) );
		}

		/**
		 *	Set a new password, and lock every session that unlocked with the
		 *	old one. The sessions go first: a write that fails there leaves
		 *	the old password in place, where the other order would leave the
		 *	new one beside sessions it never asked for. The password is posted
		 *	as 'pw', checked twice by the screen and once more here, and never
		 *	echoed back - not in the answer, not in the log.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiPassword( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$pw = \Nino\Admin\Admin::postData()['pw'] ?? null;

			if( is_string( $pw ) === false || strlen( $pw ) < self::MIN_PW_LENGTH ) {
				\Nino\Http::fail( $request, 400, 'password must be at least '. self::MIN_PW_LENGTH. ' characters' );
				return;
			}

			if( strlen( $pw ) > self::MAX_PW_LENGTH ) {
				\Nino\Http::fail( $request, 400, 'password must be at most '. self::MAX_PW_LENGTH. ' characters' );
				return;
			}

			if( \Nino\Modules\ProtectedArea::signOutAll( $appData ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not sign everybody out' );
				return;
			}

			$errors = \Nino\Features::saveSettings( $appData, self::FEATURE_KEY, [ 'password' => $pw ] );

			if( self::_failed( $request, $errors ) === true )
				return;

			\Nino\Http::ok( $request, self::_state( $appData ) );
		}

		/**
		 *	Lock every session at once
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSignOut( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			if( \Nino\Modules\ProtectedArea::signOutAll( $appData ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not sign everybody out' );
				return;
			}

			\Nino\Http::ok( $request, [ 'signedOut' => true ] );
		}

		/**
		 *	The answer of the screen's data - see apiState()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _state( array &$appData ): array {

			$pages		= self::_pages( $appData );
			$prefixes	= \Nino\Modules\ProtectedArea::prefixes( $appData );
			$listed		= array_flip( self::_listedPaths( $pages ) );
			$rows			= [];

			foreach( $pages as $page ) {

				$chosen = array_values( array_filter( $page['paths'], static fn( string $path ): bool => in_array( $path, $prefixes, true ) === true ) );

				// Covered: not a prefix of its own, but every address of it lies
				// below one - protected through a wider path, so ticking it
				// would add a line that changes nothing
				$covered = $chosen === [];

				foreach( $page['paths'] as $path )
					if( count( array_filter( $prefixes, static fn( string $prefix ): bool => str_starts_with( $path, $prefix. '/' ) === true ) ) === 0 )
						$covered = false;

				$rows[] = [
					'uri'			=> $page['uri'],
					'title'		=> $page['title'],
					'paths'		=> $page['paths'],
					// Any of its addresses chosen counts as chosen, and saving
					// then protects every one of them: the safe way round for a
					// page that has a variant per language
					'selected'	=> $chosen !== [],
					'covered'		=> $covered,
				];
			}

			return [
				'hasPassword'	=> (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'password', '' ) !== '',
				'pages'				=> $rows,
				'extra'				=> array_values( array_filter( $prefixes, static fn( string $prefix ): bool => isset( $listed[$prefix] ) === false ) ),
				'minLength'		=> self::MIN_PW_LENGTH,
				'maxLength'		=> self::MAX_PW_LENGTH,
			];
		}

		/**
		 *	Answer a failed save. A message for a setting that did not validate
		 *	is the sender's to fix (400); one without a name is the write that
		 *	did not happen (500).
		 *
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		array 		$errors				What \Nino\Features::saveSettings() answered
		 *
		 *	@return 	bool										True where the request was answered as failed
		 */
		private static function _failed( array &$request, array $errors ): bool {

			if( $errors === [] )
				return false;

			if( isset( $errors[''] ) === true )
				\Nino\Http::fail( $request, 500, 'could not save' );
			else
				\Nino\Http::fail( $request, 400, (string) reset( $errors ) );

			return true;
		}

		/**
		 *	Every path the list names, flat
		 *
		 *	@param		array 		$pages				From _pages()
		 *
		 *	@return 	array
		 */
		private static function _listedPaths( array $pages ): array {

			$paths = [];

			foreach( $pages as $page )
				foreach( $page['paths'] as $path )
					$paths[] = $path;

			return $paths;
		}

		/**
		 *	The pages a protected prefix can be chosen from: the persisted GET
		 *	routes of config.php - read from there the way \Nino\Modules\Seo
		 *	and \Nino\Features::activate() read them, never the live array,
		 *	which also carries this request's own runtime routes - that are
		 *	something a visitor opens in a browser and the gate can hold back.
		 *	Left out: the front page (a prefix of '' is skipped by the gate, so
		 *	ticking it would protect nothing), the tools and module endpoints
		 *	(`/_`, `/.`), an error page (a route with a status of 400 or more),
		 *	and everything that declares a Content-Type other than text/html -
		 *	robots.txt, sitemap.xml, a json endpoint. A wildcard route
		 *	('GET://blog/*') is listed as the prefix it stands for ('/blog').
		 *
		 *	Routes that share the internal uri - the locale variants of one
		 *	page - are one entry with every path they have, so that one tick
		 *	protects every language. Its title is the page's own
		 *	'/webpage<uri>/title', in the route's locale and else the native
		 *	one; '' where no text has one.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										[ [ 'uri', 'title', 'paths' ], ... ] in route order
		 */
		private static function _pages( array &$appData ): array {

			$stored	= \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
			$stored	= is_array( $stored ) === true ? $stored : [];
			$routes	= is_array( $stored['/nino/http/routes'] ?? null ) === true ? $stored['/nino/http/routes'] : \Nino\AppData::DEFAULTS['/nino/http/routes'];
			$native	= \Nino\Locales::getNativeLocale( $appData );
			$fills	= [];
			$pages	= [];

			foreach( $routes as $routeKey => $route ) {

				if( is_string( $routeKey ) === false || str_starts_with( $routeKey, 'GET:/' ) === false || is_array( $route ) === false )
					continue;

				$path = substr( $routeKey, strlen( 'GET:/' ) );

				if( str_ends_with( $path, '/*' ) === true )
					$path = substr( $path, 0, -2 );

				if( preg_match( '#^/[^\s\x00-\x1f\x7f*]+$#', $path ) !== 1 || $path === '/404' )
					continue;

				if( str_starts_with( $path, '/_' ) === true || str_starts_with( $path, '/.' ) === true )
					continue;

				if( (int) ( $route['statusCode'] ?? 200 ) >= 400 || self::_isHtml( $route ) === false )
					continue;

				$uri		= is_string( $route['uri'] ?? null ) === true && $route['uri'] !== '' ? $route['uri'] : $path;
				$locales	= is_string( $route['locale'] ?? null ) === true ? [ $route['locale'], $native ] : [ $native ];
				$title	= '';

				foreach( $locales as $locale ) {

					if( isset( $fills[$locale] ) === false )
						$fills[$locale] = array_merge(
							(array) \Nino\Filesystem::getFileContent( $appData, $appData['/nino/locales/textfiles']. '/global.php', [] ),
							(array) \Nino\Filesystem::getFileContent( $appData, $appData['/nino/locales/textfiles']. '/'. $locale. '.php', [] )
						);

					$title = trim( (string) ( $fills[$locale]['[[/webpage'. $uri. '/title]]'] ?? '' ) );

					if( $title !== '' )
						break;
				}

				if( isset( $pages[$uri] ) === false )
					$pages[$uri] = [ 'uri' => $uri, 'title' => '', 'paths' => [] ];

				if( in_array( $path, $pages[$uri]['paths'], true ) === false )
					$pages[$uri]['paths'][] = $path;

				if( $pages[$uri]['title'] === '' )
					$pages[$uri]['title'] = $title;
			}

			return array_values( $pages );
		}

		/**
		 *	Whether a route answers with html - the one thing a visitor can
		 *	be asked a password in front of. A route that names no Content-Type
		 *	is a page: the kernel's default is html.
		 *
		 *	@param		array 		$route
		 *
		 *	@return 	bool
		 */
		private static function _isHtml( array $route ): bool {

			foreach( is_array( $route['header'] ?? null ) === true ? $route['header'] : [] as $name => $value )
				if( is_string( $name ) === true && strtolower( $name ) === 'content-type' )
					return is_string( $value ) === true && str_starts_with( strtolower( trim( $value ) ), 'text/html' ) === true;

			return true;
		}
	}
}
