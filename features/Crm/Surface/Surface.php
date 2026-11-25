<?php
declare(strict_types=1);

namespace Nino\Modules\Crm {

	/**
	 *	Surface		The surface of the CRM: one page of the project at /crm with the login of the kernel, the endpoint of Api and its assets. The page is a standalone document of its own (not a frame of the
	 *								site, not the shell of the workbench); its screens are drawn by its scripts, which read what they need from one JSON block of the page. Nothing of it enters the bundle of the site.
	 *
	 *								GET  /crm									the page. No session (a member of a customer area is nobody here): the login of the surface, 200, which signs in at the kernel and comes back to /crm. A session
	 *																					without Crm::MANAGE_PERM: the page that says so, 403. A database that cannot be read: the page that says so, 503 - the surface is never open where it cannot ask who may
	 *																					come in. Never cached, never framed, its policy its own: no inline script
	 *								GET  /crm/todo						the second app of the surface: the open tasks of the projects behind a row of tabs, with no rail (see Surface\Todo), the same gate, the same headers, the same endpoint
	 *								GET  /crm/manifest.webmanifest, /crm/icon.svg, /crm/icon.png, /crm/icon-192.png, /crm/icon-512.png, and the same under /crm/todo
	 *																					the web app manifest and the icon of the surface as an app on the home screen (see Surface\App, Surface\Icons): public, no session asked, nothing of a client in them
	 *								GET  /crm/assets/<hash>-<name>		a file of the surface or of a package, whose content hash is the one in the name, for a year
	 *								POST /crm/api							the endpoint, see Api
	 *								The cookie crm_remember (see Surface\Devices): a visitor of GET /crm with the cookie of a device the owner has kept signed in gets the session of the account.
	 *
	 *								The gate (gate()) is open to a page of a package below /crm, which draws with its own script and style sheet of the same origin under the same policy; such a page tells the devices that
	 *								stay signed in about itself with Surface\Devices::allow() and, if it wants a link in the rail, adds an entry with Surface\Menu::add() (or, with a script of its own, with Nino.crm.menu.add).
	 *								What a package brings to the screens itself - its scripts, its style sheets, its words, its data - it adds with Surface\Scripts::add(), addStyle(), addTexts() and Surface\Data::add().
	 *
	 *	@package						Dape/Nino
	 */
	class Surface {

		public const string ROUTE = '/crm';

		/**	The files the surface serves by name: the path below the feature and the type. A request names one of these and never a path	*/
		public const array ASSETS = [
			'crm.css'        => [ 'Surface/assets/crm.css', 'text/css; charset=utf-8' ],
			'login.js'       => [ 'Surface/assets/login.js', 'text/javascript; charset=utf-8' ],
			'crm-format.js'  => [ 'Surface/assets/crm-format.js', 'text/javascript; charset=utf-8' ],
			'crm-kit.js'     => [ 'Surface/assets/crm-kit.js', 'text/javascript; charset=utf-8' ],
			'crm-points.js'  => [ 'Surface/assets/crm-points.js', 'text/javascript; charset=utf-8' ],
			'crm-screens.js' => [ 'Surface/assets/crm-screens.js', 'text/javascript; charset=utf-8' ],
			'crm-boot.js'    => [ 'Surface/assets/crm-boot.js', 'text/javascript; charset=utf-8' ],
		];

		/**	The scripts of the core, in the order they load: the formatter, which stands alone, the kit, then the points, the screens and the start	*/
		public const array SCRIPTS = [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ];

		// The links to panels of the workbench the screens of the core show: name => the panel
		private const array PANELS = [ 'users' => 'users' ];

		// The policy of the page: its own files and nothing inline; data: for an image of the markup's own
		private const string POLICY = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";

		// The directory of the templates of the surface
		private const string TEMPLATES = '/features/Crm/templates';

		// What the module keeps for the request: the hashes of the files served by name
		private const string HASHES = './crm/surface/hashes';

		/**
		 *	Register the routes and what answers them
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			$appData['/nino/http/routes']['GET://crm'] = [ 'uri' => '/crm' ];
			$appData['/nino/http/routes']['GET://crm/assets/*'] = [ 'uri' => '/crm/assets/*' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm', [ self::class, 'callbackPage' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/assets/*', [ self::class, 'callbackAsset' ] );

			// The surface as an app on the home screen: its manifest and its icon, and the second app
			Surface\App::init( $appData );
			Surface\Todo::init( $appData );

			// The devices that stay signed in: a visitor of the page with the cookie of one gets a session before the page is made
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ Surface\Devices::class, 'callbackResume' ], 1 );
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/action', [ Surface\Devices::class, 'callbackAdminAction' ] );
		}

		/**
		 *	The actions of the surface itself, which the endpoint answers: the devices that stay signed in and the end of the session
		 *
		 *	@return 	array											action => [ class, method ]
		 */
		public static function actions(): array {

			return Surface\Devices::actions();
		}

		/**
		 *	Add the words of a package: a directory with one file for each language, <locale>.php, with flat keys (see Surface\Texts)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$directory		The absolute path
		 *	@param		array 		$opts					package (required): the key of the package, see Register::package()
		 *
		 *	@return 	bool											Whether the directory is on the list of the request: its path is good
		 */
		public static function addTexts( array &$appData, string $directory, array $opts = [] ): bool {

			return Surface\Texts::add( $appData, $directory, $opts );
		}

		/**
		 *	The language of the surface: the one of the session of the workbench, else the native language of the site, en_US where the surface has no words in it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										de_DE or en_US
		 */
		public static function locale( array &$appData ): string {

			return Surface\Texts::locale( $appData );
		}

		/**
		 *	The name a file of the surface is linked by: the first twelve characters of the hash of its content, then the name
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					A key of ASSETS
		 *
		 *	@return 	string										'' for a name the surface does not serve
		 */
		public static function assetName( array &$appData, string $name ): string {

			if( isset( self::ASSETS[$name] ) === false )
				return '';

			if( isset( $appData[self::HASHES][$name] ) === false ) {
				$hash = hash_file( 'sha256', self::file( $name ) );
				$appData[self::HASHES][$name] = substr( $hash === false ? '' : $hash, 0, 12 ). '-'. $name;
			}

			return $appData[self::HASHES][$name];
		}

		/**
		 *	GET /crm: the page for a session that holds the permission
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackPage( array &$appData, array &$request ): void {

			self::page( $appData, $request, '' );
		}

		/**
		 *	The page of the surface: the login for nobody, the page that says so for an account without the permission, the page for one with it. The page of the second app (GET /crm/todo, see Surface\Todo, or the app of a package, see Surface\Apps)
		 *	has a template of its own with no rail, a manifest of its own and a data block that says app: todo; the gate, the headers and the endpoint are the same
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$app					'' for the page of the surface, else the key of an app that was entered (Surface\Apps)
		 *
		 *	@return 	void
		 */
		public static function page( array &$appData, array &$request, string $app ): void {

			$entered = $app === '' ? null : Surface\Apps::get( $appData, $app );

			// An app nobody entered is the page of the surface: the key of an app is never taken from anything but the register
			$app = $entered === null ? '' : $app;
			$page = $entered === null ? [ 'route' => self::ROUTE, 'icon' => 'crm', 'values' => [ Surface\App::class, 'values' ] ] : [ 'route' => $entered['route'], 'icon' => $entered['icon'], 'values' => $entered['values'] ];
			$user = self::gate( $appData, $request, $page );

			if( $user === null )
				return;

			$dir = rtrim( \Nino\Filesystem::getDir( $appData ), '/' );
			$links = self::links( $appData, $dir );

			// What the page says of the devices is read from the database: where that cannot be done the page is not made - an answer of "no device" would be a guess
			try {
				$device = Surface\Devices::status( $appData, (string) $user['mail'] );
			}
			catch( \PDOException $e ) {
				self::notice( $appData, $request, 503, 'down' );

				return;
			}

			$data = [
				'base'     => $dir,
				'csrf'     => \Nino\Csrf::getToken( $appData ),
				'today'    => date( 'Y-m-d' ),
				'locale'   => Surface\Texts::locale( $appData ),
				'user'     => [ 'name' => (string) $user['mail'] ],
				'perm'     => \Nino\Modules\Crm::MANAGE_PERM,
				'perms'    => [ 'crm' => true, 'elements' => \Nino\Auth::checkPermission( $appData, Values::PERM ), 'text' => \Nino\Auth::checkPermission( $appData, Company::PERM ) ],
				'device'   => $device,
				'texts'    => Surface\Texts::all( $appData ),
				'links'    => $links + array_map( static function( string $panel ) use ( &$appData ): string { return Hosts::address( $appData, '/_admin/'. \Nino\AdminAddress::of( $panel ) ); }, self::PANELS ),
				'packages' => (object) Surface\Data::all( $appData ),
			];

			if( $app !== '' )
				$data['app'] = $app;

			// The entries a package added to the rail go to the script in the data block, which draws the rail. What a package asked of the page and was refused is said once for each name, here: the page is where
			// its author looks
			if( $app === '' ) {
				Surface\Scripts::report( $appData );
				$data['menu'] = Surface\Menu::items( $appData, $dir );
			}

			// What a package takes as a reference is a copy: the directory of the page is the core's
			$given = $dir;
			$values = self::brands( $appData ) + $page['values']( $appData, $given ) + [
				'css'       => $links['css'],
				'site'      => $links['site'],
				'workbench' => $links['workbench'],
				'user'      => (string) $user['mail'],
			];

			$raw = [
				'entries' => $app === '' ? Surface\Menu::markup( $appData, $dir, Surface\Texts::all( $appData ) ) : '',
				'scripts' => Surface\Scripts::tags( $appData, $dir, self::SCRIPTS ). Surface\Scripts::markup( $appData, $dir ),
				'data'    => self::json( $data ),
			];

			self::send( $request, Surface\App::trim( $appData, self::fill( $appData, self::template( $appData, $app === '' ? 'page' : 'app' ), $values, $raw ), $app === '' ? 'crm' : $app ) );
		}

		/**
		 *	A page the surface made itself is the answer as it stands: the response is marked raw, so that the kernel does not read the body once more for shortcodes and fills (Html::response()); a name, a word or a
		 *	value of a package that holds one stays text
		 *
		 *	@param		array 		&$request
		 *	@param		string		$body
		 *
		 *	@return 	void
		 */
		public static function send( array &$request, string $body ): void {

			$request['/nino/http/response']['body'] = $body;
			$request['/nino/http/response']['raw'] = true;
		}

		/**
		 *	The gate of a page of the surface, for the pages of the surface and for a page of a package below /crm: it sets the headers of the page (headers(), the POLICY), answers the login of the surface for
		 *	nobody (200; it signs in at the kernel and comes back to $page['route'], the hash kept), answers the page that says there is no permission for an account without Crm::MANAGE_PERM (403) and hands
		 *	the account over to a page that is for it. The policy is the surface's own: the page of a package draws with a script and a style sheet of its own origin and no inline script, and the policy is not widened for it
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		array 		$page					route: the address of the page below the directory of the project, where the login comes back to; icon: the key of the app ('crm' or 'todo') whose icon the head of the page names; values: a callable
		 *																	( array &$appData, string $dir ): array that answers the tokens the head of the login is filled with (manifest, touch, bar, bar-dark, app-title), asked for the login alone
		 *
		 *	@return 	array|null								The account that may use the page; null where the response is made (the login or the 403)
		 */
		public static function gate( array &$appData, array &$request, array $page ): ?array {

			$user = \Nino\Auth::getCurrentUser( $appData );
			$dir = rtrim( \Nino\Filesystem::getDir( $appData ), '/' );
			$links = self::links( $appData, $dir );

			$request['/nino/http/response']['header'] = self::headers( $request['/nino/http/response']['header'] ?? [], 'text/html; charset=utf-8' );

			// Nobody signed in (a member of a customer area is nobody here): the login of the surface, which returns to the address of the page - where the database can be read: with one that cannot, a device that stays
			// signed in cannot be known, and a password typed into the login would end in the page that says the database failed
			if( $user === false ) {

				if( Surface\Devices::readable( $appData ) === false ) {
					self::notice( $appData, $request, 503, 'down' );

					return null;
				}

				$request['/nino/http/response']['statusCode'] = 200;
				// The way back is the address of the page, below the directory: where the CRM has a host of its own that is its start page
				$back = Hosts::address( $appData, (string) $page['route'] );
				$data = [ 'base' => $dir, 'route' => str_starts_with( $back, $dir. '/' ) === true ? substr( $back, strlen( $dir ) ) : (string) $page['route'], 'csrf' => \Nino\Csrf::getToken( $appData ), 'texts' => Surface\Texts::group( $appData, 'login.' ) ];
				$given = $dir;
				$values = self::brands( $appData ) + $page['values']( $appData, $given ) + [ 'css' => $links['css'], 'site' => $links['site'], 'script' => $dir. '/crm/assets/'. self::assetName( $appData, 'login.js' ) ];

				self::send( $request, Surface\App::trim( $appData, self::fill( $appData, self::template( $appData, 'login' ), $values, [ 'data' => self::json( $data ) ] ), (string) $page['icon'] ) );

				return null;
			}

			if( \Nino\Auth::checkPermission( $appData, \Nino\Modules\Crm::MANAGE_PERM ) === false ) {
				self::notice( $appData, $request, 403, 'forbidden' );

				return null;
			}

			$request['/nino/http/response']['statusCode'] = 200;

			return $user;
		}

		/**
		 *	A page of the surface that is none: a sentence, a status and the way on. The headers are the surface's own
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		int				$status
		 *	@param		string		$kind					forbidden (the account may not come in: the link leads to the workbench) or down (the database failed: the link tries again)
		 *
		 *	@return 	void
		 */
		private static function notice( array &$appData, array &$request, int $status, string $kind ): void {

			$dir = rtrim( \Nino\Filesystem::getDir( $appData ), '/' );
			$links = self::links( $appData, $dir );
			$words = Surface\Texts::all( $appData );

			$request['/nino/http/response']['header'] = self::headers( $request['/nino/http/response']['header'] ?? [], 'text/html; charset=utf-8' );
			$request['/nino/http/response']['statusCode'] = $status;
			self::send( $request, self::fill( $appData, self::template( $appData, 'notice' ), [
				'css'    => $links['css'],
				'href'   => $kind === 'forbidden' ? $links['workbench'] : Hosts::address( $appData, self::ROUTE ),
				'title'  => (string) ( $words[$kind. '.title'] ?? '' ),
				'text'   => (string) ( $words[$kind. '.text'] ?? '' ),
				'action' => (string) ( $words[$kind. '.link'] ?? '' ),
			], [] ) );
		}

		/**
		 *	The addresses the pages of the surface link: the style sheet, the website and the workbench
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *
		 *	@return 	array											css, site, workbench
		 */
		private static function links( array &$appData, string $dir ): array {

			return [
				'css'       => $dir. '/crm/assets/'. self::assetName( $appData, 'crm.css' ),
				'site'      => self::site( $appData, $dir ),
				'workbench' => Hosts::address( $appData, '/_admin/' ),
			];
		}

		/**
		 *	The address of the site a page of the surface links to: the start page of the project, and on the host of the CRM, which has none of the website's pages, the address of the website
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory of the project, without the closing slash
		 *
		 *	@return 	string
		 */
		private static function site( array &$appData, string $dir ): string {

			return Hosts::onCrm( $appData ) === true && \Nino\Html::absoluteUrl( $appData, '/' ) !== '' ? \Nino\Html::absoluteUrl( $appData, '/' ) : $dir. '/';
		}

		/**
		 *	The brand of the head of the rail and of the login: the name of the website, else the word of the surface
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											brand and tag
		 */
		private static function brands( array &$appData ): array {

			$brand = \Nino\Modules\Crm::siteName( $appData );
			$tag = Surface\Texts::word( $appData, 'brand.tag' );

			return [ 'brand' => $brand !== '' ? $brand : $tag, 'tag' => $brand !== '' ? $tag : '' ];
		}

		/**
		 *	The JSON of the data block: nothing in it ends the script element, a byte that is no UTF-8 is replaced and is no error, and the brackets of a string are written as \u005b and \u005d, so that a
		 *	fill or a shortcode in a word, a name or an address is no syntax to anything that reads the page
		 *
		 *	@param		array 		$data
		 *
		 *	@return 	string
		 */
		public static function json( array $data ): string {

			$json = json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR );

			return (string) preg_replace_callback( '/"(?:[^"\\\\]|\\\\.)*"/s', static fn( array $string ): string => str_replace( [ '[', ']' ], [ '\\u005b', '\\u005d' ], $string[0] ), $json );
		}

		/**
		 *	GET /crm/assets/<hash>-<name>
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackAsset( array &$appData, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$wanted = str_starts_with( $uri, '/crm/assets/' ) === true ? substr( $uri, strlen( '/crm/assets/' ) ) : '';
			$name = preg_match( '/^[0-9a-f]{12}-([a-z0-9][a-z0-9.-]*)$/D', $wanted, $found ) === 1 ? $found[1] : '';

			// The name is one of the surface's, or a script or style sheet a package added (see Surface\Scripts), and the hash is the one of the file now: a stale link is a 404, not an old file under a new name
			if( $name !== '' && isset( self::ASSETS[$name] ) === false && Surface\Scripts::assetName( $appData, $name ) === $wanted ) {
				$request['/nino/http/response']['file'] = Surface\Scripts::file( $appData, $name );
				$request['/nino/http/response']['header']['Content-Type'] = str_ends_with( $name, '.css' ) === true ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8';
				$request['/nino/http/response']['header']['Cache-Control'] = 'public, max-age=31536000';

				return;
			}

			if( $name === '' || self::assetName( $appData, $name ) !== $wanted ) {
				self::plain( $request, 404, 'Not Found' );

				return;
			}

			$request['/nino/http/response']['file'] = self::file( $name );
			$request['/nino/http/response']['header']['Content-Type'] = self::ASSETS[$name][1];
			$request['/nino/http/response']['header']['Cache-Control'] = 'public, max-age=31536000';
		}

		/**
		 *	The headers of a page of the surface: the response's own, with its policy, never cached, never framed
		 *
		 *	@param		array 		$header				The response's
		 *	@param		string		$type					The Content-Type
		 *
		 *	@return 	array
		 */
		private static function headers( array $header, string $type ): array {

			return [
				'Content-Type'            => $type,
				'Cache-Control'           => 'private, no-store',
				'X-Frame-Options'         => 'DENY',
				'Content-Security-Policy' => self::POLICY,
				'Referrer-Policy'         => 'same-origin',
			] + $header;
		}

		/**
		 *	A plain-text answer that is no file
		 *
		 *	@param		array 		&$request
		 *	@param		int				$status
		 *	@param		string		$body
		 *
		 *	@return 	void
		 */
		public static function plain( array &$request, int $status, string $body ): void {

			$request['/nino/http/response']['statusCode'] = $status;
			$request['/nino/http/response']['body'] = $body;
			$request['/nino/http/response']['header']['Content-Type'] = 'text/plain; charset=utf-8';
			$request['/nino/http/response']['header']['Cache-Control'] = 'no-store';
		}

		/**
		 *	The absolute path of a served file
		 *
		 *	@param		string		$name					A key of ASSETS
		 *
		 *	@return 	string
		 */
		private static function file( string $name ): string {

			return dirname( __DIR__ ). '/'. self::ASSETS[$name][0];
		}

		/**
		 *	A piece of markup of the surface (templates/surface-<name>.tpl) with its tokens in: the values are put in as they stand, so a value that is text goes through text() first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					Lower case letters, digits and hyphens
		 *	@param		array 		$tokens				token without brackets => value
		 *
		 *	@return 	string
		 */
		public static function fragment( array &$appData, string $name, array $tokens ): string {

			$put = [];

			foreach( $tokens as $token => $value )
				$put['[['. $token. ']]'] = (string) $value;

			return strtr( self::template( $appData, $name ), $put );
		}

		/**
		 *	A text as markup: escaped, and its brackets written as entities so that no fill and no shortcode is read in it
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		public static function text( string $text ): string {

			return str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], \Nino\Components::escape( $text ) );
		}

		/**
		 *	The markup of a template of templates/surface-<name>.tpl
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					Lower case letters
		 *
		 *	@return 	string
		 */
		private static function template( array &$appData, string $name ): string {

			return \Nino\Feature::template( $appData, self::TEMPLATES, 'surface-'. $name );
		}

		/**
		 *	A template with its words and its values in, in one pass over the markup: [[t.<key>]] is a word, [[lang]] the tag of the language, a value is its token; all are escaped and their brackets written as
		 *	entities, and what is put in is never read again (the page is sent raw, see send()). A token of the raw values is put in as it is - the markup and the JSON of the page, which the surface made itself
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$markup
		 *	@param		array 		$values				token => value, escaped
		 *	@param		array 		$raw					token => markup made by the surface
		 *
		 *	@return 	string
		 */
		private static function fill( array &$appData, string $markup, array $values, array $raw ): string {

			$words = Surface\Texts::all( $appData );
			$values['lang'] = Surface\Texts::tag( $appData );

			return (string) preg_replace_callback( '/\[\[([a-z0-9.-]+)\]\]/', static function( array $found ) use ( $words, $values, $raw ): string {

				if( str_starts_with( $found[1], 't.' ) === true )
					return self::text( (string) ( $words[substr( $found[1], 2 )] ?? '' ) );

				if( isset( $raw[$found[1]] ) === true )
					return (string) $raw[$found[1]];

				return isset( $values[$found[1]] ) === true ? self::text( (string) $values[$found[1]] ) : $found[0];
			}, $markup );
		}
	}
}
