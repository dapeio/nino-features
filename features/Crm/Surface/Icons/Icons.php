<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Surface\Icons		The icon of an app on the home screen of a phone, for every app that is registered: the surface at /crm and the tasks at /crm/todo are the core's own (init()), a package adds
	 *										its own with add(). The picture the owner put into the image slot of the app where it has one - drawn with GD into the PNGs of 180, 192 and 512 pixels, once for each content of the file, under a
	 *										name of the prefix of the app and the hash of the file; a PNG goes out as it is where the server has no GD - else the icon set of the website where it is its own, else the motif of the app drawn on
	 *										the surface of the brand (letters, or a shape: a check mark; as SVG and, with GD, as PNG), else the touch icon of the set. svg(), png(), touch(), touchSource(), touchQuery(), pngIcons() and
	 *										manifestIcons() take the key of the app. The routes of an app (<route>.svg and the PNGs) are registered by registerIcons(); the answer is the SVG of the motif with the type and the cache of a day, or
	 *										the PNG the chain names - never a path of the request. The colours are the ones the theme the project is painted by declares (--nino-brand-safe, --nino-default, --nino-on-default of assets/theme.css), as numbers.
	 *
	 *										An app is a description (add()): key, prefix of its files in data/crm-icon/, image slot, start of its routes, and its motif - letters (a text, or a static method of the package that says them from the app data) or a shape (a
	 *										polygon in a square of 512 units). This class knows nothing of who adds one.
	 */
	class Icons {

		// Where the PNG is kept
		public const string DIRECTORY = '/data/crm-icon';

		// The icon set of the website (the Favicon tab of the Images panel): the file the apple-touch-icon is, the sha1 of the one the base unit ships (a site whose touch icon is another has a set of its own), and the
		// other icons of the manifest with their sizes
		private const string TOUCH = 'apple-touch-icon.png';
		private const string STANDARD = '7a8b7e6dbf3e7420b4d0a7e7dd61adf9f3352c57';
		private const int SET_MAX = 2097152;
		private const int SET_SIDE = 1024;
		private const array SET_ICONS = [ 'favicon-192x192.png' => '192x192', 'favicon-512x512.png' => '512x512' ];

		// The apps of the core: the surface and the tasks - with the start of the name of their files in data/crm-icon/, the image slot of the owner's picture and the start of their routes (the SVG is <route>.svg,
		// the PNGs <route>.png, <route>-192.png and <route>-512.png); the motif of the first is the letters CRM, the other is a shape in a square of 512 units: a check mark (57% wide, a stroke of 12%)
		public const array CORE = [
			'crm'   => [ 'prefix' => 'surface-', 'slot' => '/icon/crm', 'route' => '/crm/icon', 'letters' => 'CRM', 'shape' => null ],
			'todo'  => [ 'prefix' => 'todo-', 'slot' => '/icon/crm-tasks', 'route' => '/crm/todo/icon', 'letters' => '', 'shape' => [ [ 110, 269 ], [ 216, 375 ], [ 402, 178 ], [ 358, 137 ], [ 215, 289 ], [ 153, 227 ] ] ],
		];

		// The sizes of the PNGs made from the picture of a slot: the apple-touch-icon, and the two of the manifest
		private const array SIZES = [ 180, 192, 512 ];

		// The key of the app data that says whether the PNG can be drawn (a test of a server without GD), and the one that holds the apps
		private const string GD = './crm/icons/gd';
		private const string REGISTER = './crm/icons/apps';

		// The apps whose letters a method did not say in this request (a warning is raised once for each), and the letters that are drawn then: the letters of the app of the core
		private const string UNSAID = './crm/icons/unsaid';
		private const string FALLBACK = 'CRM';

		// What the theme has not said: the colours the kernel's own theme.css starts from
		private const array DEFAULTS = [ 'brand' => '#147bb7', 'ground' => '#ffffff', 'ink' => '#0b0e10' ];

		/**
		 *	The apps of the core, registered: called by the first thing that asks for an app, and by the apps themselves
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											The apps by key, in the order they were added
		 */
		private static function apps( array &$appData ): array {

			if( isset( $appData[self::REGISTER] ) === false ) {
				$appData[self::REGISTER] = [];

				foreach( self::CORE as $key => $spec )
					$appData[self::REGISTER][$key] = [ 'key' => $key, 'keep' => [] ] + $spec;
			}

			return $appData[self::REGISTER];
		}

		/**
		 *	Add an app: its icon is drawn and answered like those of the core. The description: package (the key of the feature that enters it, see Register::package()), key (lower case letters, at most 20), prefix (the start of its files in data/crm-icon/: lower case letters and a hyphen, or
		 *	'' for one app), slot (an image slot /icon/<name>), route (the start of its routes, a page below /crm that is none of the core's: Devices::isRoute()), letters (a text of one to three characters, none a control character - a text is always the letters, whatever else its name is - or [ class, method ] of a public static method ( array &$appData ): string of a class of the package, see Register::owned(), that says
		 *	them) or shape (a polygon of at least three points [ x, y ] in a square of 512 units), and keep (the prefixes of other files in data/crm-icon/ that are none of an app's and are not touched when an icon
		 *	replaces its old files; none by default). The same description again is as good as the first time. A key of the core, a key, a prefix, a slot or a route another app has, and a description that is none are refused
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$spec
		 *
		 *	@return 	bool											false, with a warning, where the description is refused
		 */
		public static function add( array &$appData, array $spec ): bool {

			$apps = self::apps( $appData );
			$spec += [ 'letters' => '', 'shape' => null, 'keep' => [] ];
			$key = $spec['key'] ?? null;
			\Nino\Modules\Crm\Register::forget( $appData );

			if( is_string( $key ) === true && isset( $apps[$key] ) === true && isset( self::CORE[$key] ) === false && $apps[$key] === [ 'key' => $key, 'package' => $spec['package'] ?? null, 'prefix' => $spec['prefix'] ?? null, 'slot' => $spec['slot'] ?? null, 'route' => $spec['route'] ?? null, 'letters' => $spec['letters'], 'shape' => $spec['shape'], 'keep' => is_array( $spec['keep'] ) === true ? array_values( $spec['keep'] ) : null ] )
				return true;

			$shape = $spec['shape'];
			$letters = $spec['letters'];
			$bad = match( true ) {
				\Nino\Modules\Crm\Register::package( $appData, $spec['package'] ?? null ) === null => 'package',
				is_string( $key ) === false || preg_match( '/^[a-z]{1,20}$/D', $key ) !== 1 => 'key',
				isset( self::CORE[$key] ) === true || isset( $apps[$key] ) === true => 'key taken',
				is_string( $spec['prefix'] ?? null ) === false || preg_match( '/^(?:[a-z]{1,20}-)?$/D', $spec['prefix'] ) !== 1 || in_array( $spec['prefix'], array_column( $apps, 'prefix' ), true ) === true => 'prefix',
				is_string( $spec['slot'] ?? null ) === false || preg_match( '~^/icon/[a-z0-9-]{1,30}$~D', $spec['slot'] ) !== 1 || in_array( $spec['slot'], array_column( $apps, 'slot' ), true ) === true => 'slot',
				is_string( $spec['route'] ?? null ) === false || Devices::isRoute( $spec['route'] ) === false || preg_match( '~^(?:/[a-z0-9-]{1,30}){1,3}$~D', $spec['route'] ) !== 1 || in_array( $spec['route'], array_column( $apps, 'route' ), true ) === true => 'route',
				$shape !== null && ( is_array( $shape ) === false || count( $shape ) < 3 || array_filter( $shape, static fn( mixed $point ): bool => is_array( $point ) === false || count( $point ) !== 2 || is_int( $point[0] ?? null ) === false || is_int( $point[1] ?? null ) === false ) !== [] ) => 'shape',
				( $shape === null || $letters !== '' ) && ( is_string( $letters ) === true ? self::sayable( $letters ) === false : \Nino\Modules\Crm\Register::owned( $appData, $spec['package'] ?? null, $letters, 1, [ 0 ] ) === false ) => 'letters',
				is_array( $spec['keep'] ) === false || array_filter( $spec['keep'], static fn( mixed $prefix ): bool => is_string( $prefix ) === false || preg_match( '/^[a-z]{1,20}-$/D', $prefix ) !== 1 ) !== [] => 'keep',
				default => '',
			};

			if( $bad !== '' ) {
				trigger_error( 'Crm: the app of an icon was refused ('. $bad. ')'. ( $bad === 'letters' ? \Nino\Modules\Crm\Register::cause( $appData ) : '' ), E_USER_WARNING );

				return false;
			}

			$appData[self::REGISTER][$key] = [ 'key' => $key, 'package' => $spec['package'], 'prefix' => $spec['prefix'], 'slot' => $spec['slot'], 'route' => $spec['route'], 'letters' => $letters, 'shape' => $shape, 'keep' => array_values( $spec['keep'] ) ];

			return true;
		}

		/**
		 *	The description of an app: the app of that key, else the first of the apps (the surface)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app						A key
		 *
		 *	@return 	array
		 */
		private static function spec( array &$appData, string $app ): array {

			$apps = self::apps( $appData );

			return $apps[$app] ?? $apps[array_key_first( $apps )];
		}

		/**
		 *	The routes of the icon of an app - the SVG and the PNGs of 180, 192 and 512 pixels - and what answers them: the methods callbackIcon, callbackIconPng, callbackIconPng192 and callbackIconPng512 of the class
		 *	given, or - where none is - answerSvg() and answerPng() for the app
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *	@param		string|null	$class				The class that answers
		 *
		 *	@return 	bool											false, with a warning, where no app has the key - an app is added (add()) and then gets its routes - or the class is none of the package that entered the app
		 */
		public static function registerIcons( array &$appData, string $app, ?string $class = null ): bool {

			if( isset( self::apps( $appData )[$app] ) === false ) {
				trigger_error( 'Crm: the routes of an icon were not added: no app has the key "'. $app. '"', E_USER_WARNING );

				return false;
			}

			// The class that answers is the package's own: the app was entered by a package, and the class lies in the namespace of its feature
			$entered = self::apps( $appData )[$app];
			$package = isset( $entered['package'] ) === true ? \Nino\Modules\Crm\Register::package( $appData, $entered['package'] ) : null;

			// ...in the namespace of its feature, as text first (nothing is loaded for a name that is not), and the class is written in the folder of the package
			$file = false;
			\Nino\Modules\Crm\Register::forget( $appData );

			if( $class !== null && $package !== null && ( str_starts_with( ltrim( $class, '\\' ), $package['space']. '\\' ) === true || ltrim( $class, '\\' ) === $package['space'] ) ) {

				$file = \Nino\Modules\Crm\Register::reflect( $appData, ltrim( $class, '\\' ) )?->getFileName() ?? false;
			}

			if( $class !== null && ( $file === false || \Nino\Modules\Crm\Register::within( $file, $package['folder'] ) === false ) ) {
				trigger_error( 'Crm: the routes of an icon were not added: the class is no class of the package of the app "'. $app. '"'. \Nino\Modules\Crm\Register::cause( $appData ), E_USER_WARNING );

				return false;
			}

			$base = self::spec( $appData, $app )['route'];
			$icons = [ '.svg' => [ 'callbackIcon', 'image/svg+xml; charset=utf-8', 0 ], '.png' => [ 'callbackIconPng', 'image/png', 180 ], '-192.png' => [ 'callbackIconPng192', 'image/png', 192 ], '-512.png' => [ 'callbackIconPng512', 'image/png', 512 ] ];

			foreach( $icons as $end => [ $method, $type, $size ] ) {
				$appData['/nino/http/routes']['GET:/'. $base. $end] = [ 'uri' => $base. $end, 'header' => [ 'Content-Type' => $type, 'Cache-Control' => 'public, max-age=86400' ] ];
				\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET:/'. $base. $end, $class !== null ? [ $class, $method ] : static function( array &$appData, array &$request ) use ( $app, $size ): void {
					$size === 0 ? self::answerSvg( $appData, $request, $app ) : self::answerPng( $appData, $request, $app, $size );
				} );
			}

			return true;
		}

		/**
		 *	Whether the PNG can be drawn: GD is there with the functions that make it. The app data may say otherwise ('./crm/icons/gd', true or false: a test of a server without GD)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	bool
		 */
		public static function gd( array &$appData ): bool {

			return is_bool( $appData[self::GD] ?? null ) === true ? $appData[self::GD] : ( function_exists( 'imagecreatetruecolor' ) === true && function_exists( 'imagepng' ) === true && function_exists( 'imagestring' ) === true );
		}

		/**
		 *	The route of a file of the icon of an app: the SVG, or the PNG of 180, 192 or 512 pixels
		 *
		 *	@param		string		$app					A key of an app
		 *	@param		int				$size					0 for the SVG, else 180, 192 or 512
		 *
		 *	@return 	string										The path, without the directory the project is served from
		 */
		public static function route( array &$appData, string $app, int $size = 180 ): string {

			return self::spec( $appData, $app )['route']. ( $size === 0 ? '.svg' : ( $size === 180 ? '.png' : '-'. $size. '.png' ) );
		}

		/**
		 *	The colours of the app as numbers: brand (the surface the initials stand on, white on it reads), page (the ground of a page, light), bar (the ground of the bar, light) and dark (the ground
		 *	of the dark scheme). Read from the first 64 KB of assets/theme.css - the tokens it declares are at the top of the file, the light ones first; the ground of the dark scheme is the one in the block of @media (prefers-color-scheme: dark)
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											brand, page, light and dark - each '#rrggbb', dark null where the theme has no block for the dark scheme
		 */
		public static function colours( array &$appData ): array {

			$file = \Nino\Filesystem::path( $appData, '/assets/theme.css' );
			$css = $file !== '' && is_file( $file ) === true ? (string) file_get_contents( $file, false, null, 0, 65536 ) : '';
			$find = static function( string $token, int $nth, string $default ) use ( $css ): string {

				return preg_match_all( '/--'. $token. ':\s*(#[0-9a-fA-F]{6})\b/', $css, $found ) > $nth ? strtolower( $found[1][$nth] ) : $default;
			};
			$ground = $find( 'nino-default', 0, self::DEFAULTS['ground'] );

			return [
				'brand' => $find( 'nino-brand-safe', 0, self::DEFAULTS['brand'] ),
				'page'  => self::mix( $ground, $find( 'nino-on-default', 0, self::DEFAULTS['ink'] ), .04 ),
				'light' => $ground,
				'dark'  => preg_match( '/@media\s*\(prefers-color-scheme:\s*dark\)\s*\{[^{}]*\{[^{}]*?--nino-default:\s*(#[0-9a-fA-F]{6})\b/', $css, $dark ) === 1 ? strtolower( $dark[1] ) : null,
			];
		}

		/**
		 *	Two colours mixed: $share of the second in the first
		 *
		 *	@param		string		$base					'#rrggbb'
		 *	@param		string		$other				'#rrggbb'
		 *	@param		float			$share				0 to 1
		 *
		 *	@return 	string										'#rrggbb'
		 */
		private static function mix( string $base, string $other, float $share ): string {

			$out = '#';

			foreach( [ 1, 3, 5 ] as $at )
				$out .= sprintf( '%02x', (int) round( hexdec( substr( $base, $at, 2 ) ) * ( 1 - $share ) + hexdec( substr( $other, $at, 2 ) ) * $share ) );

			return $out;
		}

		/**
		 *	A file of the icon set of the website below /favicon, where it is there and is a PNG (the signature is read and the size of the file and of the picture is bounded: 2 MB, 1024 pixels). The name is one of the constants of this class, never a part of a request
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					TOUCH or one of the names of SET_ICONS
		 *
		 *	@return 	string|null								The absolute path; null where the file is not there or is no PNG
		 */
		private static function setFile( array &$appData, string $name ): ?string {

			$file = \Nino\Filesystem::path( $appData, \Nino\Images::FAVICON_DIR. '/'. $name );

			clearstatcache( true, $file );

			if( $file === '' || is_file( $file ) === false || (int) @filesize( $file ) > self::SET_MAX || (string) @file_get_contents( $file, false, null, 0, 8 ) !== "\x89PNG\r\n\x1a\n" )
				return null;

			$size = @getimagesize( $file );

			return $size !== false && $size[2] === IMAGETYPE_PNG && $size[0] <= self::SET_SIDE && $size[1] <= self::SET_SIDE ? $file : null;
		}

		/**
		 *	The picture the owner put into the image slot of an app (an app), where it is one this class draws an icon from: the file of the slot below /images/ (a name with a part that climbs out, a null byte, a backslash or nothing at all is no
		 *	picture), a PNG, a WebP or a JPEG that getimagesize() reads, of at most 2 MB and 1024 pixels on each side. Nothing is drawn or written
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	array|null								file (the absolute path), hash (the sha1 of the file) and type (an IMAGETYPE_ constant); null for none
		 */
		private static function slotImage( array &$appData, string $app ): ?array {

			$slot = \Nino\Images::getSlot( $appData, self::spec( $appData, $app )['slot'] );
			$filename = is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true ? $slot['filename'] : '';

			if( $filename === '' || str_contains( $filename, '..' ) === true || str_contains( $filename, "\0" ) === true || str_contains( $filename, '\\' ) === true || str_starts_with( $filename, '/' ) === true )
				return null;

			$file = \Nino\Filesystem::path( $appData, '/images/'. $filename );

			clearstatcache( true, $file );

			if( $file === '' || is_file( $file ) === false || (int) @filesize( $file ) > self::SET_MAX )
				return null;

			$size = @getimagesize( $file );

			if( $size === false || in_array( $size[2], [ IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_JPEG ], true ) === false || $size[0] < 1 || $size[1] < 1 || $size[0] > self::SET_SIDE || $size[1] > self::SET_SIDE )
				return null;

			$hash = (string) @sha1_file( $file );

			return $hash === '' ? null : [ 'file' => $file, 'hash' => $hash, 'type' => $size[2] ];
		}

		/**
		 *	Whether GD can read a picture of a type and write the PNG: GD is there (gd()), and so is the reader of the type
		 *
		 *	@param		array 		&$appData
		 *	@param		int				$type					IMAGETYPE_PNG, IMAGETYPE_WEBP or IMAGETYPE_JPEG
		 *
		 *	@return 	bool
		 */
		private static function reads( array &$appData, int $type ): bool {

			$reader = [ IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp', IMAGETYPE_JPEG => 'imagecreatefromjpeg' ][$type] ?? '';

			return self::gd( $appData ) === true && $reader !== '' && function_exists( $reader ) === true && function_exists( 'imagepng' ) === true && function_exists( 'imagecreatefromstring' ) === true;
		}

		/**
		 *	Where the apple-touch-icon of an app comes from, in this order: 'slot' (the picture in the image slot of the app, drawn with GD - or, without GD, a PNG, which goes out as it is), 'own' (the icon set of the website, where its touch
		 *	icon is not the one the base unit ships), 'drawn' (the motif of the app, with GD), 'theme' (the touch icon of the set the site has, the one of the base unit); '' where there is none of the four. Nothing is drawn or written
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *	@param		bool			$slot					Whether the slot is looked at (touch() asks without it when the picture could not be drawn)
		 *
		 *	@return 	string
		 */
		public static function touchSource( array &$appData, string $app, bool $slot = true ): string {

			$image = $slot === true ? self::slotImage( $appData, $app ) : null;

			if( $image !== null && ( self::reads( $appData, $image['type'] ) === true || $image['type'] === IMAGETYPE_PNG ) )
				return 'slot';

			$set = self::setFile( $appData, self::TOUCH );

			if( $set !== null && sha1_file( $set ) !== self::STANDARD )
				return 'own';

			if( self::gd( $appData ) === true )
				return 'drawn';

			return $set === null ? '' : 'theme';
		}

		/**
		 *	The file the apple-touch-icon of an app is, the one touchSource() names; where the picture of the slot cannot be drawn or the motif cannot be drawn or written the touch icon of the set, if the site has one
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *	@param		int				$size					180, or 192 or 512 for the PNGs of the picture of a slot (null for those where the slot is no source)
		 *
		 *	@return 	array|null								file (the absolute path) and set (whether it is a file of the icon set of the site, with the other sizes beside it); null for none
		 */
		public static function touch( array &$appData, string $app, int $size = 180 ): ?array {

			$source = self::touchSource( $appData, $app );

			if( $source === 'slot' ) {

				$made = self::slotPng( $appData, $app, $size );

				if( $made !== null )
					return [ 'file' => $made, 'set' => false ];

				// The picture could not be drawn: the chain goes on without it
				$source = self::touchSource( $appData, $app, false );
			}

			if( $size !== 180 )
				return null;

			$drawn = $source === 'drawn' ? self::png( $appData, $app ) : null;

			if( $drawn !== null )
				return [ 'file' => $drawn, 'set' => false ];

			$set = self::setFile( $appData, self::TOUCH );

			return $set === null ? null : [ 'file' => $set, 'set' => true ];
		}

		/**
		 *	Whether the app has an apple-touch-icon to name: a picture in its slot, GD draws one, or the website has a touch icon in its set. Cheap - nothing is drawn
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	bool
		 */
		public static function hasTouch( array &$appData, string $app ): bool {

			return self::touchSource( $appData, $app ) !== '';
		}

		/**
		 *	The PNG icons of a manifest: the route of the touch icon (180 pixels) and, where the icon is the picture of the slot of the app, the PNGs of 192 and 512 pixels (the last one 'any maskable': the panel cut the picture to the square, and the
		 *	owner keeps its margin free); where the icon is the website's set, its icons of 192 and 512 pixels that are there. None where there is no icon at all. Nothing is drawn
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *	@param		string		$route				The route of the app that answers the touch icon (ICON_PNG of the app)
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	array											The entries of icons, add to them
		 */
		public static function pngIcons( array &$appData, string $dir, string $route, string $app ): array {

			$source = self::touchSource( $appData, $app );

			if( $source === '' )
				return [];

			$query = self::touchQuery( $appData, $app );
			$icons = [ [ 'src' => $dir. $route. $query, 'sizes' => '180x180', 'type' => 'image/png', 'purpose' => 'any' ] ];

			if( $source === 'slot' ) {

				foreach( [ 192 => 'any', 512 => 'any maskable' ] as $size => $purpose )
					$icons[] = [ 'src' => $dir. (string) preg_replace( '~icon\.png$~', 'icon-'. $size. '.png', $route ). $query, 'sizes' => $size. 'x'. $size, 'type' => 'image/png', 'purpose' => $purpose ];

				return $icons;
			}

			if( $source === 'drawn' )
				return $icons;

			foreach( self::SET_ICONS as $name => $sizes ) {

				$file = self::setFile( $appData, $name );

				if( $file !== null )
					$icons[] = [ 'src' => \Nino\Filesystem::url( $appData, \Nino\Images::FAVICON_DIR. '/'. $name ). '?v='. (int) @filemtime( $file ), 'sizes' => $sizes, 'type' => 'image/png', 'purpose' => 'any' ];
			}

			return $icons;
		}

		/**
		 *	The icons of a manifest: the SVG of the motif of the app (not where the picture of the slot is the icon: the motif would be the wrong one) and the PNGs of pngIcons()
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *	@param		string		$svg					The route of the SVG
		 *	@param		string		$png					The route of the PNG of 180 pixels
		 *
		 *	@return 	array
		 */
		public static function manifestIcons( array &$appData, string $app, string $dir, string $svg, string $png ): array {

			$icons = self::touchSource( $appData, $app ) === 'slot' ? [] : [ [ 'src' => $dir. $svg, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any maskable' ] ];

			return array_merge( $icons, self::pngIcons( $appData, $dir, $png, $app ) );
		}

		/**
		 *	The SVG of the motif of an app as the answer of its route: public, the cache of a day
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	void
		 */
		public static function answerSvg( array &$appData, array &$request, string $app ): void {

			$request['/nino/http/response']['statusCode'] = 200;
			$request['/nino/http/response']['header']['Content-Type'] = 'image/svg+xml; charset=utf-8';
			$request['/nino/http/response']['header']['Cache-Control'] = 'public, max-age=86400';
			$request['/nino/http/response']['body'] = self::svg( $appData, $app );
			$request['/nino/http/response']['raw'] = true;
		}

		/**
		 *	The icon as an SVG document: the surface of the brand and the motif of the app - the letters of the app or a check mark. The letters are a text, the check mark a path
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	string
		 */
		public static function svg( array &$appData, string $app ): string {

			$shape = self::spec( $appData, $app )['shape'];

			if( $shape !== null )
				return \Nino\Modules\Crm\Surface::fragment( $appData, 'icon-shape', [ 'brand' => self::colours( $appData )['brand'], 'd' => 'M'. implode( 'L', array_map( static fn( array $point ): string => $point[0]. ' '. $point[1], $shape ) ). 'Z' ] );

			$letters = self::motif( $appData, $app );
			// Three letters are wider than the two initials, so the size is smaller than theirs for the PNG's own scale of letters to fit within the same margin
			$size = mb_strlen( $letters, 'UTF-8' ) > 2 ? 152 : ( mb_strlen( $letters, 'UTF-8' ) > 1 ? 224 : 288 );

			// The letters are the initials of a name or the word CRM; they are escaped all the same, and the brackets of a fill or a shortcode cannot be among them
			return \Nino\Modules\Crm\Surface::fragment( $appData, 'icon-letters', [ 'brand' => self::colours( $appData )['brand'], 'size' => $size, 'text' => str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], htmlspecialchars( $letters, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8' ) ) ] );
		}

		/**
		 *	Whether a text is letters an icon can carry: one to three characters, none of them a control character (a null byte, a line break, the controls of the text encoding), and valid UTF-8
		 *
		 *	@param		mixed			$text
		 *
		 *	@return 	bool
		 */
		private static function sayable( mixed $text ): bool {

			return is_string( $text ) === true && mb_check_encoding( $text, 'UTF-8' ) === true && mb_strlen( $text, 'UTF-8' ) >= 1 && mb_strlen( $text, 'UTF-8' ) <= 3 && preg_match( '/\p{Cc}/u', $text ) === 0;
		}

		/**
		 *	What an app draws on the surface of the brand: the letters of the app, or the name of its shape. What a method of the package answers is drawn only where it is letters (sayable()); a method that throws, or answers anything else - a text that is too long,
		 *	a list, an object - is no reason for a page to fail: the letters of the core are drawn and a warning says so (once for the app in a request)
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	string
		 */
		private static function motif( array &$appData, string $app ): string {

			$spec = self::spec( $appData, $app );

			if( $spec['shape'] !== null )
				return $spec['key'];

			if( is_array( $spec['letters'] ) === false )
				return (string) $spec['letters'];

			try {
				$said = call_user_func_array( $spec['letters'], [ &$appData ] );
			}
			catch( \Throwable $e ) {
				$said = null;
			}

			if( self::sayable( $said ) === true )
				return $said;

			if( isset( $appData[self::UNSAID][$spec['key']] ) === false ) {
				$appData[self::UNSAID][$spec['key']] = true;
				trigger_error( 'Crm: the letters of the icon of the app "'. $spec['key']. '" are not letters: a method answered something else than one to three characters, or threw', E_USER_WARNING );
			}

			return self::FALLBACK;
		}

		/**
		 *	The PNG of an app as the answer of its route: the file touch() names, public, the cache of a day; a 404 where there is none. The sizes of 192 and 512 pixels are the picture of the slot alone - without it they are a 404
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		&$request
		 *	@param		string		$app					A key of an app
		 *	@param		int				$size					180, 192 or 512
		 *
		 *	@return 	void
		 */
		public static function answerPng( array &$appData, array &$request, string $app, int $size ): void {

			$touch = in_array( $size, self::SIZES, true ) === true ? self::touch( $appData, $app, $size ) : null;

			if( $touch === null ) {
				$request['/nino/http/response']['statusCode'] = 404;
				$request['/nino/http/response']['body'] = 'Not Found';
				$request['/nino/http/response']['header']['Content-Type'] = 'text/plain; charset=utf-8';
				$request['/nino/http/response']['header']['Cache-Control'] = 'no-store';

				return;
			}

			$request['/nino/http/response']['statusCode'] = 200;
			$request['/nino/http/response']['file'] = $touch['file'];
			$request['/nino/http/response']['header']['Content-Type'] = 'image/png';
			$request['/nino/http/response']['header']['Cache-Control'] = 'public, max-age=86400';
		}

		/**
		 *	The PNG of the picture of the slot of an app, drawn when it is not there: the picture upright and fitted into the square of the size, written once under a name of the prefix of the app, 'slot-', the start of the hash of the file and the size;
		 *	the PNGs of another picture of the app are taken away (the files of the other apps stay). Where the server has no GD (or no reader of the type) a PNG goes out as it is; any other picture is none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *	@param		int				$size					180, 192 or 512
		 *
		 *	@return 	string|null								The absolute path; null where the slot has no picture this server can use, or the file could not be made or written
		 */
		private static function slotPng( array &$appData, string $app, int $size ): ?string {

			$image = self::slotImage( $appData, $app );

			if( $image === null || in_array( $size, self::SIZES, true ) === false )
				return null;

			if( self::reads( $appData, $image['type'] ) === false )
				return $image['type'] === IMAGETYPE_PNG ? $image['file'] : null;

			$prefix = self::spec( $appData, $app )['prefix']. 'slot-'. substr( $image['hash'], 0, 16 ). '-';
			$file = \Nino\Filesystem::path( $appData, self::DIRECTORY. '/'. $prefix. $size. '.png' );

			if( is_file( $file ) === true )
				return $file;

			$source = (string) @file_get_contents( $image['file'] );
			$canvas = $source === '' ? false : \Nino\Images::square( $source, $size );

			if( $canvas === false )
				return null;

			imagealphablending( $canvas, false );
			imagesavealpha( $canvas, true );
			ob_start();
			$drawn = imagepng( $canvas );
			$bytes = (string) ob_get_clean();

			if( $drawn === false || $bytes === '' || self::write( $appData, $file, $bytes, $app ) === false )
				return null;

			// A PNG of another picture of this app is not wanted any more
			foreach( glob( dirname( $file ). '/*.png' ) ?: [] as $other )
				if( str_starts_with( basename( $other ), $prefix ) === false && self::prefixOf( $appData, basename( $other ) ) === self::spec( $appData, $app )['prefix'] )
					@unlink( $other );

			return $file;
		}

		/**
		 *	A file of data/crm-icon/, written through a temporary one
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$file					The absolute path
		 *	@param		string		$bytes
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	bool
		 */
		private static function write( array &$appData, string $file, string $bytes, string $app ): bool {

			\Nino\Filesystem::forceDir( $appData, self::DIRECTORY );

			$temporary = $file. '.'. bin2hex( random_bytes( 6 ) ). '.tmp';

			if( @file_put_contents( $temporary, $bytes ) !== strlen( $bytes ) || @rename( $temporary, $file ) === false ) {
				@unlink( $temporary );
				trigger_error( 'Crm: the icon of the app '. $app. ' could not be written', E_USER_WARNING );

				return false;
			}

			return true;
		}

		/**
		 *	The file of the PNG, drawn when it is not there: the brand's surface and the motif of the app in white. The initials and the letters are drawn with GD's own font (ISO-8859-1: a letter it has no sign for is left out) on a small canvas and
		 *	enlarged with smoothing, so that no font file of the server is needed; the check mark is a polygon drawn large and scaled down
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app. An icon replaces only those files of its own prefix
		 *
		 *	@return 	string|null								The absolute path; null where GD is not there or the file could not be written
		 */
		public static function png( array &$appData, string $app ): ?string {

			if( self::gd( $appData ) === false || function_exists( 'imagecreatetruecolor' ) === false )
				return null;

			$prefix = self::spec( $appData, $app )['prefix'];
			$motif = self::motif( $appData, $app );
			$brand = self::colours( $appData )['brand'];
			$name = $prefix. self::drawnHash( $motif, $brand ). '.png';
			$file = \Nino\Filesystem::path( $appData, self::DIRECTORY. '/'. $name );

			if( is_file( $file ) === true )
				return $file;

			$shape = self::spec( $appData, $app )['shape'];
			$bytes = $shape !== null ? self::drawShape( $shape, $brand ) : self::draw( $motif, $brand );

			if( $bytes === null || self::write( $appData, $file, $bytes, $app ) === false )
				return null;

			// An icon of another name or colour is not wanted any more - one of this app's own prefix; the files of the other apps and the ones an app keeps stay
			foreach( glob( dirname( $file ). '/*.png' ) ?: [] as $other )
				if( $other !== $file && self::prefixOf( $appData, basename( $other ) ) === $prefix )
					@unlink( $other );

			return $file;
		}

		/**
		 *	The part of the name of the drawn PNG that holds what it is made of
		 *
		 *	@param		string		$motif
		 *	@param		string		$brand				'#rrggbb'
		 *
		 *	@return 	string
		 */
		private static function drawnHash( string $motif, string $brand ): string {

			return substr( hash( 'sha256', $motif. '|'. $brand ), 0, 16 );
		}

		/**
		 *	The query that names the state of the icon in its address (?v=), so that a new picture, a new set or a new name is fetched and not read from a cache of a day: the short hash of the picture of the slot, the time of the file of the icon set, or the short hash
		 *	of what the motif is made of. '' where there is no icon
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$app					A key of an app
		 *
		 *	@return 	string
		 */
		public static function touchQuery( array &$appData, string $app ): string {

			$source = self::touchSource( $appData, $app );

			if( $source === 'slot' )
				return '?v='. substr( (string) ( self::slotImage( $appData, $app )['hash'] ?? '' ), 0, 8 );

			if( $source === 'drawn' )
				return '?v='. substr( self::drawnHash( self::motif( $appData, $app ), self::colours( $appData )['brand'] ), 0, 8 );

			$file = $source === '' ? null : self::setFile( $appData, self::TOUCH );

			return $file === null ? '' : '?v='. (int) @filemtime( $file );
		}

		/**
		 *	The prefix a file of data/crm-icon/ carries: one an app keeps (keep), that of an app, or none (the files of the app whose prefix is empty)
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		private static function prefixOf( array &$appData, string $name ): string {

			$apps = self::apps( $appData );

			foreach( array_merge( array_merge( [], ...array_column( $apps, 'keep' ) ), array_filter( array_column( $apps, 'prefix' ), static fn( string $prefix ): bool => $prefix !== '' ) ) as $prefix )
				if( str_starts_with( $name, $prefix ) === true )
					return $prefix;

			return '';
		}

		/**
		 *	The check mark, drawn: the polygon filled in white on the brand's surface at four times the size and scaled down with smoothing
		 *
		 *	@param		array 		$points				[ x, y ] in a square of 512 units
		 *	@param		string		$brand				'#rrggbb'
		 *
		 *	@return 	string|null								The bytes; null where GD does not make them
		 */
		private static function drawShape( array $points, string $brand ): ?string {

			$size = 180;
			$large = 4 * $size;
			$canvas = imagecreatetruecolor( $large, $large );
			$small = imagecreatetruecolor( $size, $size );

			if( $canvas === false || $small === false )
				return null;

			imagefill( $canvas, 0, 0, (int) imagecolorallocate( $canvas, (int) hexdec( substr( $brand, 1, 2 ) ), (int) hexdec( substr( $brand, 3, 2 ) ), (int) hexdec( substr( $brand, 5, 2 ) ) ) );

			$flat = [];

			foreach( $points as $point ) {
				$flat[] = (int) round( $point[0] * $large / 512 );
				$flat[] = (int) round( $point[1] * $large / 512 );
			}

			imagefilledpolygon( $canvas, $flat, (int) imagecolorallocate( $canvas, 255, 255, 255 ) );
			imagecopyresampled( $small, $canvas, 0, 0, 0, 0, $size, $size, $large, $large );
			ob_start();
			imagepng( $small );

			return (string) ob_get_clean();
		}

		/**
		 *	The PNG of letters, drawn
		 *
		 *	@param		string		$initials
		 *	@param		string		$brand				'#rrggbb'
		 *
		 *	@return 	string|null								The bytes; null where GD does not make them
		 */
		private static function draw( string $initials, string $brand ): ?string {

			$size = 180;
			$text = (string) @iconv( 'UTF-8', 'ISO-8859-1//IGNORE', $initials );
			$text = $text === '' ? '?' : $text;
			$font = 5;
			$cell = [ imagefontwidth( $font ) * strlen( $text ), imagefontheight( $font ) ];
			$mask = imagecreatetruecolor( $cell[0], $cell[1] );
			$canvas = imagecreatetruecolor( $size, $size );

			if( $mask === false || $canvas === false )
				return null;

			imagefill( $mask, 0, 0, (int) imagecolorallocate( $mask, 0, 0, 0 ) );
			imagestring( $mask, $font, 0, 0, $text, (int) imagecolorallocate( $mask, 255, 255, 255 ) );

			// The height of the letters is 40% of the icon, which is inside the part a mask leaves
			$height = (int) round( $size * ( strlen( $text ) > 1 ? .36 : .46 ) );
			$width = (int) round( $cell[0] * $height / $cell[1] );
			$large = imagescale( $mask, $width, $height, IMG_BILINEAR_FIXED );
			$colour = [ (int) hexdec( substr( $brand, 1, 2 ) ), (int) hexdec( substr( $brand, 3, 2 ) ), (int) hexdec( substr( $brand, 5, 2 ) ) ];

			imagefill( $canvas, 0, 0, (int) imagecolorallocate( $canvas, $colour[0], $colour[1], $colour[2] ) );

			if( $large !== false ) {

				$left = (int) round( ( $size - $width ) / 2 );
				$top = (int) round( ( $size - $height ) / 2 );

				// The brightness of the enlarged mask is how much of the letter a pixel holds: white over the brand's surface
				for( $y = 0; $y < $height; $y++ )
					for( $x = 0; $x < $width; $x++ ) {

						$share = ( imagecolorat( $large, $x, $y ) & 0xff ) / 255;

						if( $share > 0 )
							imagesetpixel( $canvas, $left + $x, $top + $y, ( (int) round( $colour[0] + ( 255 - $colour[0] ) * $share ) << 16 ) | ( (int) round( $colour[1] + ( 255 - $colour[1] ) * $share ) << 8 ) | (int) round( $colour[2] + ( 255 - $colour[2] ) * $share ) );
					}
			}

			ob_start();
			imagepng( $canvas );

			return (string) ob_get_clean();
		}
	}
}
