<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Surface\Todo		The second app of the surface: the open tasks of the projects at /crm/todo. It has no rail and no menu bar - one row of tabs, a tab for each project that has open tasks, and under it the list of the tasks of the project that is open,
	 *									drawn by the script of the surface from the hash (#tasks-<id>), as the tab of the tasks of the card of a project draws it, with no date and the actions of a task in the menu of its row.
	 *
	 *									GET  /crm/todo									the page: the surface's own login without a session (200, back to this address), the page that says so with 403 for a session without the permission of the CRM, the page of the app for a session
	 *																						that has it (see Surface::page(): the same policy, the same endpoint, the same token; the data block says app: todo)
	 *									GET  /crm/todo/manifest.webmanifest	the web app manifest of this app: an id, a start address and a scope of its own. Public, no session asked, nothing of a client or of a task in it - the routes ask for no session
	 *																						and answering them changes nothing
	 *									GET  /crm/todo/icon.svg, icon.png, icon-192.png, icon-512.png		the icon of the app: the picture of the image slot /icon/crm-tasks, else the icon set of the website, else a check mark on the brand's surface (Icons). Public
	 *
	 *									Every action of the app is one of the tasks (crm-tasks/*) and goes through POST /crm/api with the token; the rights are checked on the server as for the card of a project. The cookie of a device that is kept
	 *									signed in (see Devices) is read at a GET of the pages and at nothing else.
	 */
	class Todo {

		// The routes: the page and its manifest
		public const string ROUTE = '/crm/todo';
		public const string MANIFEST = '/crm/todo/manifest.webmanifest';

		// The part of the site the app covers
		public const string SCOPE = '/crm/todo';

		/**
		 *	The routes and what answers them: called by Surface::init()
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			$appData['/nino/http/routes']['GET://crm/todo'] = [ 'uri' => self::ROUTE ];
			$appData['/nino/http/routes']['GET://crm/todo/manifest.webmanifest'] = [ 'uri' => self::MANIFEST, 'header' => [ 'Content-Type' => 'application/manifest+json; charset=utf-8', 'Cache-Control' => 'public, max-age=3600' ] ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/todo', [ self::class, 'callbackPage' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/todo/manifest.webmanifest', [ self::class, 'callbackManifest' ] );

			Icons::registerIcons( $appData, 'todo' );

			// The app of the core goes the way an app of a package goes: it is entered, and its page, its devices and its head are answered from the register
			Apps::init( $appData );
		}

		/**
		 *	The name of the app: the name of the website as text and the word of the app behind it; the word alone where the website has no name
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										Not escaped
		 */
		public static function name( array &$appData ): string {

			$site = \Nino\Modules\Crm::siteName( $appData );
			return $site === '' ? Texts::word( $appData, 'todo.app-short' ) : str_replace( '%s', $site, Texts::word( $appData, 'todo.app-name' ) );
		}

		/**
		 *	GET /crm/todo: the page of the app, or the login, or the page that says there is no permission
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackPage( array &$appData, array &$request ): void {

			\Nino\Modules\Crm\Surface::page( $appData, $request, 'todo' );
		}

		/**
		 *	GET /crm/todo/manifest.webmanifest: the web app manifest of the tasks. An id, a start address and a scope of its own, standalone, the colours and the icons of the surface
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *
		 *	@return 	void
		 */
		public static function callbackManifest( array &$appData, array &$request ): void {

			$dir = rtrim( \Nino\Filesystem::getDir( $appData ), '/' );

			$request['/nino/http/response']['statusCode'] = 200;
			$request['/nino/http/response']['header']['Content-Type'] = 'application/manifest+json; charset=utf-8';
			$request['/nino/http/response']['header']['Cache-Control'] = 'public, max-age=3600';
			$request['/nino/http/response']['body'] = App::json( [
				'id'               => $dir. self::ROUTE,
				'name'             => self::name( $appData ),
				'short_name'       => Texts::word( $appData, 'todo.app-short' ),
				'start_url'        => $dir. self::ROUTE,
				'scope'            => $dir. self::SCOPE,
				'display'          => 'standalone',
				'background_color' => App::COLOURS['page'],
				'theme_color'      => App::COLOURS['bar'],
				'lang'             => Texts::tag( $appData ),
				'icons'            => App::icons( $appData, $dir, 'todo' ),
			] );
		}

		/**
		 *	The values the head of the page of the app and of its login are filled with: those of the surface, with the manifest and the title of this app
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *
		 *	@return 	array											[[token]] => value, to be escaped by the filling
		 */
		public static function values( array &$appData, string $dir ): array {

			return [
				'manifest'  => $dir. self::MANIFEST,
				'app-title' => Texts::word( $appData, 'todo.app-short' ),
				'title' => Texts::word( $appData, 'todo.page-title' ),
			] + App::values( $appData, $dir, 'todo' );
		}
	}
}
