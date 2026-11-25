<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Surface\App		The surface at /crm as an app on the home screen of a phone: the web app manifest (GET /crm/manifest.webmanifest), the icon (GET /crm/icon.svg, and GET /crm/icon.png of 180
	 *										pixels for the apple-touch-icon, GET /crm/icon-192.png and GET /crm/icon-512.png where the picture of the owner is the icon) and the lines the head of the page and of its login carry. The icon is
	 *										the picture in the image slot /icon/crm where there is one, else the icon set of the website where it is its own, else the letters CRM on the brand's surface (Icons, which registers the routes; the same chain
	 *										serves the tasks, Todo, and the apps of the packages, Apps).
	 *
	 *										The manifest and the icons name nobody but the website: its name and the initials of it, the colours of the surface. A visitor may read them - a browser asks for a manifest
	 *										without a session, and the app is opened with the manifest of the page a session has ended on - and nothing in them is a client's, an account's or a document's: the routes ask
	 *										for no session, and answering them changes nothing. The name is text: it is put into the JSON escaped, its brackets as [ (the response is rendered once more, and a fill or
	 *										a shortcode of the name would be read), and a null byte is left out.
	 *
	 *										The app opens the overview (start_url '/crm#overview', the scope '/crm'; on the host of the CRM '/'). Without a session the page of the surface is its login, which comes back to the address it was
	 *										called at with the hash - so the app lands on the overview after the sign-in (see login.js). A device the owner has kept signed in (Surface\Devices) has no login at all
	 *										until its days are over; the manifest and the icons never use that cookie.
	 */
	class App {

		// The routes: the manifest, the icon as SVG and the icon as PNG
		public const string MANIFEST = '/crm/manifest.webmanifest';
		public const string ICON = '/crm/icon.svg';
		public const string ICON_PNG = '/crm/icon.png';

		// The page the app opens, and the part of the site it covers
		public const string START = '/crm#overview';
		public const string SCOPE = '/crm';

		// The colours of the surface as numbers - a browser needs them before a style sheet is read: the ground of the page and the surface of the bar, light and dark (the tokens --bg and --surface of crm.css)
		public const array COLOURS = [ 'page' => '#f3f5f7', 'bar' => '#ffffff', 'page-dark' => '#14181d', 'bar-dark' => '#1c2127' ];

		/**
		 *	The routes and what answers them: called by Surface::init()
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			$appData['/nino/http/routes']['GET://crm/manifest.webmanifest'] = [ 'uri' => self::MANIFEST, 'header' => [ 'Content-Type' => 'application/manifest+json; charset=utf-8', 'Cache-Control' => 'public, max-age=3600' ] ];
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://crm/manifest.webmanifest', [ self::class, 'callbackManifest' ] );

			Icons::registerIcons( $appData, 'crm' );
		}

		/**
		 *	The name of the app: the name of the website as text and the word of the surface behind it; the word alone where the website has no name
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string										Not escaped
		 */
		public static function name( array &$appData ): string {

			$site = \Nino\Modules\Crm::siteName( $appData );
			return $site === '' ? Texts::word( $appData, 'app.short' ) : str_replace( '%s', $site, Texts::word( $appData, 'app.name' ) );
		}

		/**
		 *	The icons of a manifest of an app of the surface: the SVG of its motif and the PNGs of its icon, or the PNGs of the picture of its slot alone (Icons::manifestIcons())
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *	@param		string		$app					'crm' or 'todo'
		 *
		 *	@return 	array
		 */
		public static function icons( array &$appData, string $dir, string $app = 'crm' ): array {

			return Icons::manifestIcons( $appData, $app, $dir, Icons::route( $appData, $app, 0 ), Icons::route( $appData, $app ) );
		}

		/**
		 *	The JSON of a manifest: unescaped slashes and letters, one string at a time with its brackets as \u005b and \u005d (the response is rendered once more, and a fill or a shortcode in the name of the website would be read), and nothing in it that ends a script element
		 *
		 *	@param		array 		$document
		 *
		 *	@return 	string
		 */
		public static function json( array $document ): string {

			$json = json_encode( $document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR );

			return (string) preg_replace_callback( '/"(?:[^"\\\\]|\\\\.)*"/s', static fn( array $string ): string => str_replace( [ '[', ']' ], [ '\\u005b', '\\u005d' ], $string[0] ), (string) $json );
		}

		/**
		 *	GET /crm/manifest.webmanifest: the web app manifest. The name of the website with the word CRM, the word CRM as the short form, the page the app opens and the part of the site it covers (with the
		 *	directory the project is served from), standalone, the colours of the surface, the language of the surface and the icons: the SVG and the PNGs
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
			$request['/nino/http/response']['body'] = self::json( [
				'name'             => self::name( $appData ),
				'short_name'       => Texts::word( $appData, 'app.short' ),
				'start_url'        => \Nino\Modules\Crm\Hosts::address( $appData, self::START ),
				'scope'            => \Nino\Modules\Crm\Hosts::address( $appData, self::SCOPE ),
				'display'          => 'standalone',
				'background_color' => self::COLOURS['page'],
				'theme_color'      => self::COLOURS['bar'],
				'lang'             => Texts::tag( $appData ),
				'icons'            => self::icons( $appData, $dir ),
			] );
		}

		/**
		 *	The values the head of a page of the surface (the page and its login) is filled with: the manifest, the icon for iOS, the colours of the bar of the browser and the title of the app
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *	@param		string		$app					'crm' or 'todo': whose icon the head names
		 *
		 *	@return 	array											[[token]] => value, to be escaped by the filling
		 */
		public static function values( array &$appData, string $dir, string $app = 'crm' ): array {

			return [
				'manifest'  => $dir. self::MANIFEST,
				'touch'     => $dir. Icons::route( $appData, $app ). Icons::touchQuery( $appData, $app ),
				'bar'       => self::COLOURS['bar'],
				'bar-dark'  => self::COLOURS['bar-dark'],
				'app-title' => Texts::word( $appData, 'app.short' ),
			];
		}

		/**
		 *	The head of a page without the line of the apple-touch-icon where the site has no icon at all (no GD and no icon set): the route is the 404 of the surface and the head does not name it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$page
		 *	@param		string		$app					'crm' or 'todo'
		 *
		 *	@return 	string
		 */
		public static function trim( array &$appData, string $page, string $app = 'crm' ): string {

			return Icons::hasTouch( $appData, $app ) === true ? $page : (string) preg_replace( '~^[ \t]*<link\b[^>\n]*\brel="apple-touch-icon"[^>\n]*>[ \t]*\r?\n~mi', '', $page );
		}
	}
}
