<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Hosts		The names an installation answers under: the app, the CRM and the portal are three addresses, and this class owns the first two (the portal's is the portal's own, which asks this class for the Host header and the path as a web server reads it).
	 *
	 *								The workbench (/_admin, with recovery.php, and the sign-in of the kernel /.nino it uses) belongs to the APP address, the setting app_host; the CRM (/crm, and the sign-in) to the CRM address, the setting crm_host.
	 *								Both are a name in lower case (the rule of Settings::validHost()), and both are read through the connection that only reads (Settings::readOrNull()), once for the request, by a request whose Host header is not
	 *								the site's own or that is for a place of the owner. A project served from a directory (/nino/dir) has none of them. The Host header alone decides, never X-Forwarded-Host.
	 *
	 *								Empty app_host and empty crm_host are no rule: every place answers on every name (but the portal's). With crm_host alone the owner's places answer on the CRM host only, the workbench with them, and the CRM host answers
	 *								nothing else; with app_host the workbench answers on that name alone and the CRM wherever crm_host puts it (on every name where it has none). The app may be the CRM's name (crm.example.com/ is the CRM and
	 *								crm.example.com/_admin the workbench), and it may be the website's, but it is never the portal's (Settings::clash()). On the CRM host / is the CRM (it is mapped to /crm, as / is /portal on the portal host); /crm... is the same
	 *								page, since the apps, the assets and the endpoints are named so, a manifest keeps its scope there and a bookmark, a mail or a cookie of before reaches it. On the CRM host /_admin, where the app has another name, is a
	 *								bookmark of before: a GET with the plain spelling of the path is sent there (301, query kept), anything else is 404.
	 *
	 *								Where the app has a name of its own the setting is saved only after the name has been shown to be this installation: the workbench cannot be put where it is not (Hosts::probe()).
	 *								Every address the module writes comes from the settings, never from the header: address() is the one function the pages ask, absolute addresses (mails, documents) go through Html::absoluteUrl() and the hook
	 *								'/nino/html/absolute-url' this class answers. A package that has a client on the other side answers one hook, '/crm/hosts/shared': the paths that every host answers because the owner and the client both use them.
	 *
	 *								A name that cannot be read - the database is locked by another process for longer than a write waits (Db::BUSY) - is not an empty name: the walls between the places of the owner and everyone else
	 *								are the names, so a request for a place of the owner (/crm, the workbench, the sign-in of the kernel) is answered 503 on every host until they can be read, and the pages of the website are not touched.
	 *
	 *	@package						Dape/Nino
	 */
	class Hosts {

		// The settings; the shape of a name they may hold is the rule of Settings::validHost()
		public const string KEY_CRM = 'crm_host';
		public const string KEY_APP = 'app_host';

		// The route that answers what was refused or moved, the route that shows the name is this installation, the file that holds what the route answers, how long it does
		public const string ROUTE = '/.crm/place';
		public const string PROBE = '/.crm/probe';
		public const string PROBE_FILE = '/data/crm-probe.php';
		public const int PROBE_LIFE = 600;

		// The hook that asks which more paths every host answers (a package answers it, see share())
		public const string SHARED = '/crm/hosts/shared';

		// The start page of the CRM is the path of the module
		private const string ROOT = '/crm';

		// The places of the owner: the CRM and the sign-in of the kernel (compared in lower case, on the path a web server reads - see path())
		private const array SURFACE = [ '/crm', '/.nino' ];

		// What the module keeps for the request: the names the settings hold, and the answer a request was turned into
		private const string CACHE_CRM = './crm/hosts/crm';
		private const string CACHE_APP = './crm/hosts/app';
		private const string REDIRECT = './crm/hosts/redirect';
		private const string SHARES = './crm/hosts/shares';

		// Set for the request when a name could not be read from the settings: the places of the owner are closed then, with 503, and the flag a refused request carries to the route of its answer
		private const string BROKEN = './crm/hosts/broken';
		private const string CLOSED = './crm/hosts/closed';

		// How long a read of a name waits for a locked database, in milliseconds: Db::BUSY, which is what a write waits too - app data may carry a shorter time under this key, for a test that holds a lock
		private const string WAIT = './crm/hosts/wait';

		/**
		 *	The routes and the callbacks: called by init() of the module
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			$appData['/nino/http/routes']['GET://.crm/place'] = [ 'uri' => self::ROUTE ];
			$appData['/nino/http/routes']['GET://.crm/probe'] = [ 'uri' => self::PROBE ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'callbackRequest' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.crm/place', [ self::class, 'callbackRedirect' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.crm/probe', [ self::class, 'callbackProbe' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/html/absolute-url', [ self::class, 'callbackAbsoluteUrl' ] );
		}

		/**
		 *	The name of a Host header as it is compared: lower case, without the port and without the dot of a name that is written complete
		 *
		 *	@param		string		$header
		 *
		 *	@return 	string
		 */
		public static function normalise( string $header ): string {

			$header = strtolower( trim( $header ) );

			if( str_starts_with( $header, '[' ) === true )
				return $header;

			return (string) preg_replace( '/\.$/D', '', (string) preg_replace( '/:[0-9]*$/D', '', $header ) );
		}

		/**
		 *	The Host header of a request, normalised
		 *
		 *	@param		array 		$request
		 *
		 *	@return 	string										'' where the request has none
		 */
		public static function seen( array $request ): string {

			$header = $request['/nino/http/request']['header']['Host'] ?? '';

			return is_string( $header ) === true ? self::normalise( $header ) : '';
		}

		/**
		 *	Whether the request is for the site's own host: the host of the address of the website
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$request
		 *
		 *	@return 	bool
		 */
		public static function onSite( array &$appData, array $request ): bool {

			$seen = self::seen( $request );

			return $seen !== '' && $seen === Settings::siteHost( $appData );
		}

		/**
		 *	A name a setting holds: the setting where it is a name and the project is not served from a directory; '' for none. The CRM host is another name than the website's. Read once for the request
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		string		$cache
		 *
		 *	@return 	string
		 */
		private static function read( array &$appData, string $key, string $cache ): string {

			if( is_string( $appData[$cache] ?? null ) === true )
				return $appData[$cache];

			$host = '';

			// The directory is not there where nothing has asked for a request yet (a job, a worker of the tests): the project is at the root of its host
			if( rtrim( (string) ( $appData['/nino/dir'] ?? '' ), '/' ) === '' ) {

				// A read that fails is not an empty setting: the names are the walls between the places of the owner and everyone else, so a read that cannot be made waits the whole time a write
				// may take (Db::BUSY) and, where it still fails, shuts the places of the owner instead of opening them (see callbackRequest())
				$value = Settings::readOrNull( $appData, $key, max( 1, (int) ( $appData[self::WAIT] ?? Db::BUSY ) ) );

				if( $value === null ) {
					$appData[self::BROKEN] = true;
					$value = '';
				}

				$host = Settings::validHost( $value ) === true && ( $key === self::KEY_APP || Settings::isSiteHost( $appData, $value ) === false ) ? $value : '';
			}

			return $appData[$cache] = $host;
		}

		/**
		 *	The CRM host the owner set; '' for none
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function crm( array &$appData ): string {

			return self::read( $appData, self::KEY_CRM, self::CACHE_CRM );
		}

		/**
		 *	The app host the owner set; '' for none
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function app( array &$appData ): string {

			return self::read( $appData, self::KEY_APP, self::CACHE_APP );
		}

		/**
		 *	The one name the workbench answers on: the app host, else the CRM host, else none ('' - every name that is not the portal's)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function workbench( array &$appData ): string {

			return self::app( $appData ) !== '' ? self::app( $appData ) : self::crm( $appData );
		}

		/**
		 *	Whether the request is for the CRM host
		 *
		 *	@param		array 		&$appData
		 *	@param		array|null	$request			The request; the one being answered where none is given
		 *
		 *	@return 	bool
		 */
		public static function onCrm( array &$appData, ?array $request = null ): bool {

			$request ??= \Nino\Http::getRequest( $appData );

			if( $request === false )
				return false;

			$seen = self::seen( $request );

			return $seen !== '' && self::onSite( $appData, $request ) === false && $seen === self::crm( $appData );
		}

		/**
		 *	The settings are read again: called by Settings::set() when one of them is written
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function forget( array &$appData ): void {

			unset( $appData[self::CACHE_CRM], $appData[self::CACHE_APP], $appData[self::BROKEN] );
		}

		/**
		 *	A path as a web server reads it to decide which script answers: in lower case, percent-decoded once, the dot segments resolved and every run of slashes one
		 *
		 *	@param		string		$uri					A cleaned path of a request
		 *
		 *	@return 	string
		 */
		public static function path( string $uri ): string {

			$parts = [];

			foreach( explode( '/', strtolower( rawurldecode( $uri ) ) ) as $part ) {

				if( $part === '' || $part === '.' )
					continue;

				if( $part === '..' )
					array_pop( $parts );
				else
					$parts[] = $part;
			}

			return '/'. implode( '/', $parts );
		}

		/**
		 *	Whether a path is one of the owner's: the CRM (/crm...), the workbench (/_admin..., whatever follows the name, as router.php reads it) or the sign-in of the kernel (/.nino...)
		 *
		 *	@param		string		$uri					A cleaned path of a request
		 *
		 *	@return 	bool
		 */
		public static function surface( string $uri ): bool {

			if( self::bench( $uri ) === true )
				return true;

			$path = self::path( $uri );

			foreach( self::SURFACE as $name )
				if( $path === $name || str_starts_with( $path, $name. '/' ) === true )
					return true;

			return false;
		}

		/**
		 *	Whether a path is the CRM's own: /crm or a path below it, as a web server reads it (the sign-in /.nino is the kernel's and belongs to neither)
		 *
		 *	@param		string		$uri					A cleaned path of a request
		 *
		 *	@return 	bool
		 */
		public static function page( string $uri ): bool {

			$path = self::path( $uri );

			return $path === '/crm' || str_starts_with( $path, '/crm/' ) === true;
		}

		/**
		 *	Whether a path is the workbench's: /_admin, whatever follows the name as router.php reads it
		 *
		 *	@param		string		$uri					A cleaned path of a request
		 *
		 *	@return 	bool
		 */
		public static function bench( string $uri ): bool {

			return str_starts_with( self::path( $uri ), '/_admin' );
		}

		/**
		 *	Enter paths that every host answers, each deciding by the session: the PDF and the electronic invoice of a document, the files and the comment of a review - what a package
		 *	needs that both the owner and the client open. A path is lower case letters, digits and . _ ~ - in segments, none of them . or .., and the same path again is as good as the first
		 *	time; a list with a path that is none enters nothing
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$prefixes			Paths that begin and end with a slash
		 *	@param		array 		$exact				Whole paths
		 *	@param		array 		$opts					package (required): the key of the package that shares them, see Register::package()
		 *
		 *	@return 	bool											false, with a warning, where a path is none or the package is none
		 */
		public static function share( array &$appData, array $prefixes, array $exact = [], array $opts = [] ): bool {

			if( array_diff_key( $opts, [ 'package' => 1 ] ) !== [] || Register::package( $appData, $opts['package'] ?? null ) === null ) {
				trigger_error( 'Crm: the paths a package shares on every host were refused (package)', E_USER_WARNING );

				return false;
			}

			foreach( $prefixes as $prefix )
				if( self::sharable( $prefix, true ) === false ) {
					trigger_error( 'Crm: the paths a package shares on every host were refused (a prefix)', E_USER_WARNING );

					return false;
				}

			foreach( $exact as $path )
				if( self::sharable( $path, false ) === false ) {
					trigger_error( 'Crm: the paths a package shares on every host were refused (a path)', E_USER_WARNING );

					return false;
				}

			$appData[self::SHARES] = [ 'prefixes' => array_values( array_unique( array_merge( (array) ( $appData[self::SHARES]['prefixes'] ?? [] ), $prefixes ) ) ), 'exact' => array_values( array_unique( array_merge( (array) ( $appData[self::SHARES]['exact'] ?? [] ), $exact ) ) ) ];

			return true;
		}

		/**
		 *	Whether a text is a path that may be shared on every host: lower case, in segments of letters, digits and . _ ~ -, none of them . or .., and - for a prefix - with a slash at its end
		 *
		 *	@param		mixed			$path
		 *	@param		bool			$prefix
		 *
		 *	@return 	bool
		 */
		private static function sharable( mixed $path, bool $prefix ): bool {

			if( is_string( $path ) === false || preg_match( $prefix === true ? '#^/(?:[a-z0-9._~-]+/)+$#D' : '#^/[a-z0-9._~-]+(?:/[a-z0-9._~-]+)*$#D', $path ) !== 1 )
				return false;

			return array_intersect( explode( '/', $path ), [ '.', '..' ] ) === [];
		}

		/**
		 *	Whether a path is one the owner and the client both use, each deciding by the session: what was entered with share(), and what the hook of the portal adds
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$uri					A cleaned path of a request
		 *
		 *	@return 	bool
		 */
		public static function shared( array &$appData, string $uri ): bool {

			$path = self::path( $uri );

			if( in_array( $path, (array) ( $appData[self::SHARES]['exact'] ?? [] ), true ) === true )
				return true;

			foreach( (array) ( $appData[self::SHARES]['prefixes'] ?? [] ) as $prefix )
				if( str_starts_with( $path, (string) $prefix ) === true )
					return true;

			$ask = [ 'path' => $path, 'shared' => false ];

			\Nino\Callbacks::doCallbacks( $appData, self::SHARED, $ask );

			return ( $ask['shared'] ?? false ) === true;
		}

		/**
		 *	The path on the CRM host for a path in the space of the module, with the query and the place it may carry: /crm is /, /crm#client-1 is /#client-1. Any other path (/crm/events, /crm/api) is left alone
		 *
		 *	@param		string		$path
		 *
		 *	@return 	string
		 */
		public static function crmExternal( string $path ): string {

			return preg_match( '~^/crm(?=$|[?#])~', $path ) === 1 ? '/'. substr( $path, strlen( '/crm' ) ) : $path;
		}

		/**
		 *	The address a page writes for a place of the owner's - the CRM (/crm, /crm/todo) or the workbench (/_admin/#files): below the directory of the project, as it is, where the place is on the host of the page; on the host the setting names,
		 *	absolute, where it is another one (the link from the workbench to the CRM, from the CRM to the workbench); on the CRM host / for /crm. The one function that says where the CRM and the workbench are
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$path					The path below the directory of the project, with the query and the place it may carry
		 *
		 *	@return 	string
		 */
		public static function address( array &$appData, string $path ): string {

			$own = rtrim( (string) ( $appData['/nino/dir'] ?? '' ), '/' ). $path;
			$bare = substr( $path, 0, strcspn( $path, '?#' ) );
			$request = \Nino\Http::getRequest( $appData );
			$seen = $request === false ? '' : self::seen( $request );

			if( self::page( $bare ) === true && self::crm( $appData ) !== '' )
				return $seen === self::crm( $appData ) ? self::crmExternal( $path ) : 'https://'. self::crm( $appData ). self::crmExternal( $path );

			if( self::bench( $bare ) === true && self::workbench( $appData ) !== '' )
				return $seen === self::workbench( $appData ) ? $own : 'https://'. self::workbench( $appData ). $path;

			return $own;
		}

		/**
		 *	The query of a request as it may be written into a Location: what is written after the question mark and nothing that can end the line of a header
		 *
		 *	@param		array 		$request
		 *
		 *	@return 	string										'' for none
		 */
		public static function query( array $request ): string {

			$raw = (string) ( $request['REQUEST_URI'] ?? '' );
			$cut = strpos( $raw, '?' );
			$query = $cut === false ? '' : substr( $raw, $cut + 1 );
			$query = substr( $query, 0, strcspn( $query, '#' ) );

			return preg_match( '/^[\x21-\x7e]*$/D', $query ) === 1 ? $query : '';
		}

		/**
		 *	'/nino/http/request': who answers the places of the owner. Nothing is done where neither setting is there. The CRM host has / for the CRM; the places of the owner are refused on the names that do not own them
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackRequest( array &$appData, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$seen = self::seen( $request );

			// What shows that a name is this installation is asked on every name
			if( $uri === self::PROBE )
				return;

			$surface = self::surface( $uri );

			// A request that names no host fails closed: the places of the owner are not there where a setting names a host for them
			if( $seen === '' ) {

				if( $surface === true && ( self::crm( $appData ) !== '' || self::app( $appData ) !== '' || ( $appData[self::BROKEN] ?? false ) === true ) )
					self::refuse( $request, ( $appData[self::BROKEN] ?? false ) === true );

				return;
			}

			$root = $uri === '' || $uri === '/';

			// The settings are read for every request whose host is not the site's and for a path of the owner's: a page of the website on the website's host opens no connection
			$read = self::onSite( $appData, $request ) === false || $surface === true;
			$crm = $read === true ? self::crm( $appData ) : '';
			$app = $read === true ? self::app( $appData ) : '';

			// The settings could not be read: no name is known, and a place of the owner is answered by none - 503, on every name - until they can be. The website itself is not a place of the owner
			if( ( $appData[self::BROKEN] ?? false ) === true ) {

				if( $surface === true )
					self::refuse( $request, true );

				return;
			}

			if( $crm === '' && $app === '' )
				return;

			if( $seen === $crm ) {

				if( self::bench( $uri ) === true ) {

					// The workbench is here where the app has no name of its own, or this one; else a bookmark of before is sent to the app
					if( $app !== '' && $app !== $crm )
						self::towards( $app, $request );

					return;
				}

				if( $root === true ) {
					$request['/nino/http/request']['uri'] = self::ROOT;
					$request['/nino/http/response']['uri'] = self::ROOT;

					return;
				}

				if( $surface === false && self::shared( $appData, $uri ) === false )
					self::refuse( $request );

				return;
			}

			if( $surface === false )
				return;

			// The other names: the CRM is where crm_host puts it - on every name where it has none -, the workbench where the app is, and the sign-in of the kernel with whichever of them is there
			$work = self::bench( $uri ) === true;
			$here = $seen === $app;

			if( ( $work === true && $here === false ) || ( $work === false && $crm !== '' && ( self::page( $uri ) === true || $here === false ) ) )
				self::refuse( $request );
		}

		/**
		 *	The interface for a package that answers a host of its own (the portal): a request that is not for this host is turned into the route that answers 404 - no word of what is on another host, no redirect,
		 *	whatever the method. Called from a listener of the hook '/nino/http/request' with the argument it was given, the way the core calls it: $request is the array of that hook, and the request of the page is
		 *	changed in it - its method becomes GET and its route the one of this class (ROUTE), which answers the 404 (or, with $closed, the 503) and nothing else. Whatever redirect an earlier listener of the core put
		 *	there is taken away. The call makes no answer of its own and reads nothing: where the request goes on after the call is the kernel's, and a package calls it as the last thing it does for a request
		 *
		 *	@param		array 		&$request			(reference) The argument of the hook '/nino/http/request'
		 *	@param		bool			$closed				That the answer is 503 and not 404: the place is shut because it cannot be told whose it is, not because it is not there
		 *
		 *	@return 	void
		 */
		public static function refuse( array &$request, bool $closed = false ): void {

			unset( $request[self::REDIRECT], $request[self::CLOSED] );

			if( $closed === true )
				$request[self::CLOSED] = true;

			$request['/nino/http/request']['method'] = 'GET';
			$request['/nino/http/request']['uri'] = self::ROUTE;
			$request['/nino/http/response']['uri'] = self::ROUTE;
		}

		/**
		 *	The interface for a package that moves a place to another host: the request is turned into the route of the answer that sends it to $app - a GET with the plain spelling of a path of the workbench (/_admin and below)
		 *	answers 301 with the address on that host (the query kept, the place the browser keeps; the route of the answer is a route of GET, so another method never reaches it); anything else - another method, a spelling a web server would
		 *	read differently, a path of another place - is refused as refuse() refuses it, and nothing moves. Called from a listener of the hook '/nino/http/request' with its argument, like refuse(); a host that is no name
		 *	(Settings::validHost()) and the host the request is on (which would send it round in a circle) are refused as well, with a warning (a host name comes from a setting that holds one, never from the request)
		 *
		 *	@param		string		$app					The name of the host the place is on: a host name, no scheme and no path
		 *	@param		array 		&$request			(reference) The argument of the hook '/nino/http/request'
		 *
		 *	@return 	void
		 */
		public static function towards( string $app, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );

			// A host that is no name is none to move to, and the host the request is on is no place to move it to - it would come back to the same place, again and again: the request is refused, and the package that asked is told
			if( Settings::validHost( $app ) === false || $app === self::seen( $request ) ) {
				trigger_error( 'Crm: a request was not moved to a host that is no name or to the host it is on', E_USER_WARNING );
				self::refuse( $request );

				return;
			}

			// Only a GET with the plain spelling of the path is a bookmark of before; any other method is no request that moves
			if( ( $request['/nino/http/request']['method'] ?? '' ) !== 'GET' || preg_match( '#^/_admin(?:/[A-Za-z0-9._~/-]*)?$#D', $uri ) !== 1 || str_contains( $uri, '..' ) === true || str_contains( $uri, '//' ) === true ) {
				self::refuse( $request );

				return;
			}

			$query = self::query( $request );
			$request[self::REDIRECT] = [ 'to' => 'https://'. $app. $uri. ( $query === '' ? '' : '?'. $query ), 'status' => 301 ];
			$request['/nino/http/request']['uri'] = self::ROUTE;
			$request['/nino/http/response']['uri'] = self::ROUTE;
		}

		/**
		 *	The answer of the route a request was turned into: the redirect callbackRequest() made, once. The route asked for by hand has none and is 404
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackRedirect( array &$appData, array &$request ): void {

			$redirect = $request[self::REDIRECT] ?? null;
			$closed = ( $request[self::CLOSED] ?? false ) === true;
			unset( $request[self::REDIRECT], $request[self::CLOSED] );

			if( $closed === true ) {
				$request['/nino/http/response']['statusCode'] = 503;
				unset( $request['/nino/http/response']['header']['Location'] );
				$request['/nino/http/response']['header']['Content-Type'] = 'text/plain; charset=utf-8';
				$request['/nino/http/response']['header']['Retry-After'] = '5';
				$request['/nino/http/response']['header']['Cache-Control'] = 'no-store';
				$request['/nino/http/response']['body'] = 'Service Unavailable';

				return;
			}

			if( is_array( $redirect ) === false || is_string( $redirect['to'] ?? null ) === false ) {
				$request['/nino/http/response']['statusCode'] = 404;
				unset( $request['/nino/http/response']['header']['Location'] );
				$request['/nino/http/response']['header']['Content-Type'] = 'text/plain; charset=utf-8';
				$request['/nino/http/response']['body'] = 'Not Found';

				return;
			}

			\Nino\Http::redirect( $appData, $request, $redirect['to'], (int) ( $redirect['status'] ?? 301 ) );
		}

		/**
		 *	Whether a name is this installation: the name answers the route PROBE with what a file of the installation holds (a number made for the question, which lives for PROBE_LIFE seconds). A name that is another installation, a server
		 *	of the same files on another machine or nothing at all does not answer it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$host					A name as the setting holds it
		 *
		 *	@return 	bool
		 */
		public static function probe( array &$appData, string $host ): bool {

			if( Settings::validHost( $host ) === false )
				return false;

			$nonce = bin2hex( random_bytes( 16 ) );

			if( \Nino\Filesystem::putFileContent( $appData, self::PROBE_FILE, [ 'nonce' => $nonce, 'at' => time() ] ) === false ) {
				trigger_error( 'Crm: the file that shows a name is this installation could not be written', E_USER_WARNING );

				return false;
			}

			$answer = \Nino\Fetch::get( $appData, 'https://'. $host. self::PROBE, [ 'timeout' => 5, 'maxBytes' => 128 ] );

			// What was asked is gone with the answer
			\Nino\Filesystem::putFileContent( $appData, self::PROBE_FILE, [] );

			return $answer['ok'] === true && hash_equals( hash( 'sha256', $nonce ), trim( (string) $answer['body'] ) );
		}

		/**
		 *	The answer of the route PROBE: the sha256 of the number a question made, while it lives; 404 otherwise, on every name and for everybody
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackProbe( array &$appData, array &$request ): void {

			$held = \Nino\Filesystem::getFileContent( $appData, self::PROBE_FILE, [] );
			$fresh = is_array( $held ) === true && is_string( $held['nonce'] ?? null ) === true && is_int( $held['at'] ?? null ) === true && time() - $held['at'] <= self::PROBE_LIFE && time() >= $held['at'];

			$request['/nino/http/response']['header']['Cache-Control'] = 'no-store';
			$request['/nino/http/response']['header']['Content-Type'] = 'text/plain; charset=utf-8';
			$request['/nino/http/response']['statusCode'] = $fresh === true ? 200 : 404;
			$request['/nino/http/response']['body'] = $fresh === true ? hash( 'sha256', (string) $held['nonce'] ) : 'Not Found';
		}

		/**
		 *	'/nino/html/absolute-url': the address of the CRM is the CRM host's, where there is one, and / is its start page; the address of the workbench is the app's (the CRM host's where there is no app host); any other path is left to the site.
		 *	Whatever host the request had
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$args				path and url
		 *
		 *	@return 	void
		 */
		public static function callbackAbsoluteUrl( array &$appData, array &$args ): void {

			$path = $args['path'] ?? null;

			if( is_string( $path ) === false )
				return;

			$bare = substr( $path, 0, strcspn( $path, '?#' ) );

			if( self::page( $bare ) === true && self::crm( $appData ) !== '' )
				$args['url'] = 'https://'. self::crm( $appData ). self::crmExternal( $path );
			else if( self::bench( $bare ) === true && self::workbench( $appData ) !== '' )
				$args['url'] = 'https://'. self::workbench( $appData ). $path;
		}
	}
}
