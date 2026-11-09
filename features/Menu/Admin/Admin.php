<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Menu\Admin		The /_admin panel of the Menu feature
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Menu {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Admin							The Menu's workbench panel: the list of the menus and the editor of
	 *										one, a workspace like the Builder's. A developer surface - an address and
	 *										a piece of HTML+ are written as typed, and a save makes text keys and
	 *										image slots - so every action, the reads among them, asks for
	 *										MANAGE_PERM, which is a developer permission as the Builder's is, and
	 *										a menu is written whole in one request. The keys and the slots are
	 *										made and the texts written by the panels that own them, which ask for
	 *										their own permissions: a save that has to make a key or write a text
	 *										is refused for an account that lacks the Text Keys permission, one that
	 *										has to make a slot for an account that lacks the Image Slots permission.
	 *										The pictures of the slots are not this panel's to store: the script posts
	 *										them to the Images panel itself.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/menu/manage';

		// What a slot of a picture in a panel is made in: the canvas an upload is cut to
		public const int SLOT_WIDTH = 600;
		public const int SLOT_HEIGHT = 400;

		// The longest text of a label, a title or a subtitle
		public const int MAX_TEXT = 300;

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'menu/list'			=> [ self::class, 'apiList' ],
				'menu/registry'	=> [ self::class, 'apiRegistry' ],
				'menu/load'			=> [ self::class, 'apiLoad' ],
				'menu/save'			=> [ self::class, 'apiSave' ],
				'menu/delete'		=> [ self::class, 'apiDelete' ],
			];
		}

		// No group is named here: a panel a feature brought always lands in the rail's own "features" group
		public static function nav(): array {
			return [ 'menu', '/_admin/menu/nav', 40 ];
		}

		// The panel renders its own regions (see templates/panel.tpl) rather than mount points a script fills
		public static function template(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/templates/panel' );
		}

		public static function layout(): string {
			return 'workspace';
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><rect width="16" height="6" x="4" y="15" rx="1"/></svg>';
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

		/*	What the activity log records, per action - a read says nothing	*/
		public static function log( string $action, array $data ): string {

			$key = is_string( $data['key'] ?? null ) === true && \Nino\Modules\Menu::isSlug( $data['key'] ) === true ? $data['key'] : '?';

			return match( $action ) {
				'menu/save'		=> 'Save Menu "'. $key. '"',
				'menu/delete'	=> 'Delete Menu "'. $key. '"',
				default				=> '',
			};
		}

		/**
		 *	The menus of the data: the key, how many entries each has, and whether the key is one of the kernel's
		 *	navigations, which the menu then stands in for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$navs = self::_navs( $appData );
			$menus = [];

			foreach( \Nino\Modules\Menu::menus( $appData ) as $key => $menu )
				$menus[] = [ 'key' => $key, 'entries' => count( $menu['entries'] ), 'kernel' => in_array( $key, $navs, true ) ];

			\Nino\Http::ok( $request, [ 'menus' => $menus, 'navs' => $navs ] );
		}

		/**
		 *	What the editor's forms are built from: the pages of the site, the navigations of the kernel with the
		 *	pages each lists, the languages, and the image slots the menus use
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiRegistry( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$locale = \Nino\Admin\Admin::contentLocale( $appData, true );
			$fills = \Nino\Html::getFills( $appData, $locale );
			$pages = [];

			// The pages a visitor can open that have a name in the working language, as the Routes panel lists them
			foreach( \Nino\Http::pages( (array) ( $appData['/nino/http/routes'] ?? [] ) ) as $page ) {

				$name = (string) ( $fills['[[/_nino/webpage'. $page['uri']. '/name]]'] ?? '' );

				if( $page['wildcard'] === true || $page['html'] === false || $page['status'] >= 400 || \Nino\Modules\Menu::isUri( $page['uri'] ) === false || trim( $name ) === '' )
					continue;

				$pages[$page['uri']] = [ 'uri' => $page['uri'], 'name' => $name, 'names' => [] ];
			}

			// The name of each page in every language, which stands in the field of its label where the label is empty
			foreach( \Nino\Locales::getTextLocales( $appData ) as $one )
				foreach( \Nino\Html::getFills( $appData, $one ) as $fill => $text )
					if( preg_match( '~^\[\[/_nino/webpage(/.+)/name\]\]$~D', (string) $fill, $found ) === 1 && isset( $pages[$found[1]] ) === true && is_string( $text ) === true && trim( $text ) !== '' )
						$pages[$found[1]]['names'][$one] = $text;

			foreach( $pages as $uri => $page )
				$pages[$uri]['names'] = (object) $page['names'];

			$navPages = [];

			foreach( self::_navs( $appData ) as $nav ) {

				$navPages[$nav] = [];

				foreach( \Nino\Modules\Navigation::routeLines( $appData, $nav ) as $line ) {

					// The line is "<path>:<name>": the page it is, by the route that has the path
					$path = explode( ':', $line, 2 )[0];
					$uri = (string) ( $appData['/nino/http/routes']['GET:/'. $path]['uri'] ?? $path );

					if( \Nino\Modules\Menu::isUri( $uri ) === true && in_array( $uri, $navPages[$nav], true ) === false )
						$navPages[$nav][] = $uri;
				}
			}

			$locales = \Nino\Locales::getTextLocales( $appData );
			$names = [];

			foreach( $locales as $one ) {
				$name = (string) ( $fills['[[/_nino/locale/'. $one. '/name]]'] ?? '' );
				$names[$one] = $name !== '' ? $name : $one;
			}

			$slots = [];

			foreach( \Nino\Images::getSlots( $appData ) as $uri => $slot )
				if( str_starts_with( (string) $uri, '/feature/menu/' ) === true && is_array( $slot ) === true )
					$slots[] = [
						'uri'			=> (string) $uri,
						'width'		=> (int) ( $slot['width'] ?? 0 ),
						'height'	=> (int) ( $slot['height'] ?? 0 ),
						'url'			=> is_string( $slot['filename'] ?? null ) === true && $slot['filename'] !== '' ? \Nino\Images::getUrl( $appData, $slot['filename'] ) : null,
						'alt'			=> is_array( $slot['alt'] ?? null ) === true ? $slot['alt'] : (object) [],
					];

			\Nino\Http::ok( $request, [
				'pages'			=> array_values( $pages ),
				'navs'			=> self::_navs( $appData ),
				'navPages'	=> (object) $navPages,
				'locales'		=> $locales,
				'active'		=> \Nino\Locales::getAvailableLocales( $appData ),
				'names'			=> (object) $names,
				'locale'		=> $locale,
				'native'		=> \Nino\Locales::getNativeLocale( $appData ),
				'slots'			=> $slots,
				'size'			=> [ 'width' => self::SLOT_WIDTH, 'height' => self::SLOT_HEIGHT ],
			] );
		}

		/**
		 *	One menu as the editor works on it: its entries, the texts of every key they name in every language,
		 *	and the names of the keys and slots the menu has, so that a new item is called what no item was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiLoad( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' );

			if( \Nino\Modules\Menu::isMenuKey( $key ) === false ) {
				\Nino\Http::fail( $request, 400, 'a menu key is a slug of lower case words', 'menu_key', [], 'key' );
				return;
			}

			$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );

			if( is_array( $stored ) === false || is_array( $stored[$key] ?? null ) === false ) {
				\Nino\Http::fail( $request, 404, 'there is no such menu', 'menu_missing', [ $key ] );
				return;
			}

			$problems = [];
			$entries = \Nino\Modules\Menu::entries( $key, $stored[$key]['entries'] ?? [], $problems, true );
			$prefix = '/feature/menu/'. $key. '/';
			$names = self::_names( $entries );
			$texts = [];
			$taken = [];

			foreach( \Nino\Text::entries( $appData ) as $entry ) {

				if( str_starts_with( (string) $entry['key'], $prefix ) === false )
					continue;

				$taken[] = (string) $entry['key'];

				if( in_array( $entry['key'], $names, true ) === true )
					$texts[$entry['key']] = (object) array_map( 'strval', array_filter( (array) $entry['values'], static fn( mixed $text ): bool => $text !== null ) );
			}

			foreach( array_keys( \Nino\Images::getSlots( $appData ) ) as $uri )
				if( str_starts_with( (string) $uri, $prefix ) === true )
					$taken[] = (string) $uri;

			\Nino\Http::ok( $request, [
				'key'				=> $key,
				'kernel'		=> in_array( $key, self::_navs( $appData ), true ),
				'entries'		=> $entries,
				'texts'			=> (object) $texts,
				'taken'			=> array_values( array_unique( $taken ) ),
				'problems'	=> array_column( $problems, 'entry' ),
			] );
		}

		/**
		 *	The menu written whole: refused with the entry that is wrong where it is not valid, and with the
		 *	permission of the panel that owns what it has to make - the Text Keys' for a key it makes or a text
		 *	it writes, the Image Slots' for a slot it makes - where the account lacks it. The keys and the slots are
		 *	made first, the texts written next, the data last; what was made is taken away again, and what was
		 *	written put back, where a step fails
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = \Nino\Admin\Admin::postString( $data, 'key' );

			if( \Nino\Modules\Menu::isMenuKey( $key ) === false ) {
				\Nino\Http::fail( $request, 400, 'a menu key is a slug of lower case words', 'menu_key', [], 'key' );
				return;
			}

			$problems = [];
			$entries = \Nino\Modules\Menu::entries( $key, $data['entries'] ?? null, $problems );

			// A problem of the whole list - too many entries, no list - names the list, not an entry
			if( in_array( '#0', array_column( $problems, 'entry' ), true ) === true ) {
				\Nino\Http::fail( $request, 400, 'the entries are not a valid list: '. implode( '; ', array_map( static fn( array $problem ): string => $problem['why'], array_filter( $problems, static fn( array $problem ): bool => $problem['entry'] === '#0' ) ) ), 'menu_list', [ \Nino\Modules\Menu::MAX_ENTRIES ] );
				return;
			}

			if( $problems !== [] ) {
				\Nino\Http::fail( $request, 400, 'the menu has entries that are not valid: '. implode( '; ', array_map( static fn( array $problem ): string => $problem['entry']. ': '. $problem['why'], $problems ) ), 'menu_invalid', [ implode( ', ', array_unique( array_column( $problems, 'entry' ) ) ) ] );
				return;
			}

			$names = self::_names( $entries );
			$texts = self::_texts( $appData, $data['texts'] ?? [], $names );

			if( $texts === null ) {
				\Nino\Http::fail( $request, 400, 'the texts are a map of the keys of the menu to a text in each language', 'menu_texts' );
				return;
			}

			$known = array_column( \Nino\Text::entries( $appData ), null, 'key' );
			$slots = self::_slots( $entries );
			$newKeys = array_values( array_filter( $names, static fn( string $name ): bool => isset( $known[$name] ) === false ) );
			$newSlots = array_values( array_filter( $slots, static fn( string $uri ): bool => \Nino\Images::getSlot( $appData, $uri ) === false ) );
			$writes = [];
			$before = [];

			// A text for a key that is there is written where it differs from the one it has
			foreach( $texts as $name => $given )
				if( isset( $known[$name] ) === true )
					foreach( $given as $locale => $text ) {

						$held = (string) ( $known[$name]['global'] === true ? ( $known[$name]['values']['*'] ?? '' ) : ( $known[$name]['values'][$locale] ?? '' ) );

						if( \Nino\Text::sanitizeValue( $text, (string) $known[$name]['format'] ) !== $held ) {
							$writes[] = [ 'key' => $name, 'locale' => $locale, 'value' => $text ];
							$before[] = [ 'key' => $name, 'locale' => $locale, 'value' => $held ];
						}
					}

			$permissions = [];

			if( $newKeys !== [] || $writes !== [] )
				$permissions[] = \Nino\Modules\Text\Keys::MANAGE_PERM;

			if( $newSlots !== [] )
				$permissions[] = \Nino\Modules\Images\Slots::MANAGE_PERM;

			foreach( $permissions as $permission )
				if( \Nino\Auth::checkPermission( $appData, $permission ) === false ) {
					\Nino\Http::fail( $request, 403, 'this save makes keys or slots, or writes texts, which asks for the permission '. $permission, 'menu_permission', [ $permission ] );
					return;
				}

			$made = [ 'keys' => [], 'slots' => [] ];

			foreach( $newKeys as $name ) {

				[ $status, $body ] = self::_api( $appData, 'keys/create', [ 'key' => $name, 'global' => false, 'format' => 'plain', 'values' => $texts[$name] ?? [] ] );

				// A key that another request made in the meantime (409) is theirs: it is used and not taken away
				if( in_array( $status, [ 200, 409 ], true ) === false ) {
					self::_unmake( $appData, $made );
					\Nino\Http::fail( $request, $status, (string) ( $body['error'] ?? 'the key could not be made' ), 'menu_key_made', [ $name ] );
					return;
				}

				if( $status === 200 )
					$made['keys'][] = $name;
			}

			foreach( $newSlots as $uri ) {

				[ $status, $body ] = self::_api( $appData, 'slots/create', [ 'uri' => $uri, 'label' => ucfirst( str_replace( '-', ' ', substr( $uri, strlen( '/feature/menu/' ) ) ) ), 'width' => self::SLOT_WIDTH, 'height' => self::SLOT_HEIGHT ] );

				if( in_array( $status, [ 200, 409 ], true ) === false ) {
					self::_unmake( $appData, $made );
					\Nino\Http::fail( $request, $status, (string) ( $body['error'] ?? 'the slot could not be made' ), 'menu_slot_made', [ $uri ] );
					return;
				}

				if( $status === 200 )
					$made['slots'][] = $uri;
			}

			if( $writes !== [] ) {

				[ $status, $body ] = self::_api( $appData, 'keys/savebatch', [ 'items' => $writes ] );

				if( $status !== 200 || array_filter( (array) ( $body['results'] ?? [] ), static fn( mixed $result ): bool => ( $result['ok'] ?? false ) !== true ) !== [] ) {
					self::_api( $appData, 'keys/savebatch', [ 'items' => $before ] );
					self::_unmake( $appData, $made );
					\Nino\Http::fail( $request, 500, 'the texts could not be written' );
					return;
				}
			}

			$written = \Nino\Filesystem::mutate( $appData, \Nino\Modules\Menu::STORAGE, static function( mixed $state ) use ( $key, $entries ): array {

				$state = is_array( $state ) === true ? $state : [];
				$state[$key] = [ 'entries' => $entries ];
				ksort( $state );

				return $state;
			}, [] );

			if( $written === false ) {
				self::_api( $appData, 'keys/savebatch', [ 'items' => $before ] );
				self::_unmake( $appData, $made );
				\Nino\Http::fail( $request, 500, 'could not write the menu' );
				return;
			}

			\Nino\Http::ok( $request, [ 'key' => $key, 'entries' => count( $entries ), 'made' => $made ] );
		}

		/**
		 *	A menu taken out of the data. The texts and the slots it made stay: the scans of the Text and
		 *	Images panels find them as keys and slots that nothing uses
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$key = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' );
			$gone = false;

			if( \Nino\Modules\Menu::isMenuKey( $key ) === false ) {
				\Nino\Http::fail( $request, 400, 'a menu key is a slug of lower case words', 'menu_key', [], 'key' );
				return;
			}

			$written = \Nino\Filesystem::mutate( $appData, \Nino\Modules\Menu::STORAGE, static function( mixed $state ) use ( $key, &$gone ): ?array {

				if( is_array( $state ) === false || isset( $state[$key] ) === false ) {
					$gone = true;
					return null;
				}

				unset( $state[$key] );

				return $state;
			}, [] );

			if( $written === false && $gone === true )
				\Nino\Http::fail( $request, 404, 'there is no such menu', 'menu_missing', [ $key ] );
			else if( $written === false )
				\Nino\Http::fail( $request, 500, 'could not write the menus' );
			else
				\Nino\Http::ok( $request, [ 'key' => $key ] );
		}

		/**
		 *	The navigations of the kernel, as /nino/html/navs lists them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Keys that are slugs
		 */
		private static function _navs( array &$appData ): array {
			return array_values( array_unique( array_filter( array_map( 'strval', (array) ( $appData['/nino/html/navs'] ?? [] ) ), [ \Nino\Modules\Menu::class, 'isMenuKey' ] ) ) );
		}

		/**
		 *	Every text key the entries name: the labels of the entries and the items that are keys
		 *
		 *	@param		array			$entries			As entries() reads them
		 *
		 *	@return 	array
		 */
		private static function _names( array $entries ): array {

			$names = [];

			foreach( $entries as $entry ) {

				if( $entry['label'] !== '' )
					$names[] = $entry['label'];

				foreach( $entry['panel']['cols'] ?? [] as $items )
					foreach( $items as $item ) {

						if( in_array( $item['kind'], [ 'title', 'subtitle' ], true ) === true )
							$names[] = $item['key'];
						else if( in_array( $item['kind'], [ 'page', 'link' ], true ) === true && $item['label'] !== '' )
							$names[] = $item['label'];
					}
			}

			return $names;
		}

		/**
		 *	Every image slot the entries name
		 *
		 *	@param		array			$entries
		 *
		 *	@return 	array
		 */
		private static function _slots( array $entries ): array {

			$slots = [];

			foreach( $entries as $entry )
				foreach( $entry['panel']['cols'] ?? [] as $items )
					foreach( $items as $item )
						if( $item['kind'] === 'image' )
							$slots[] = $item['slot'];

			return $slots;
		}

		/**
		 *	The texts a save carries, read: key => language => text, the keys among those the menu names, the
		 *	languages among those that have a text file, every text one line of at most MAX_TEXT characters
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$posted
		 *	@param		array			$names				The keys of the menu
		 *
		 *	@return 	array|null								null where the texts are none
		 */
		private static function _texts( array &$appData, mixed $posted, array $names ): ?array {

			if( is_array( $posted ) === false )
				return null;

			$locales = \Nino\Locales::getTextLocales( $appData );
			$texts = [];

			foreach( $posted as $name => $given ) {

				if( in_array( $name, $names, true ) === false || is_array( $given ) === false )
					return null;

				foreach( $given as $locale => $text )
					if( in_array( $locale, $locales, true ) === false || is_string( $text ) === false || mb_strlen( $text ) > self::MAX_TEXT || preg_match( '/[\x00-\x1f\x7f]/', $text ) === 1 )
						return null;

				$texts[$name] = $given;
			}

			return $texts;
		}

		/**
		 *	Take away what a save made, the slots first and the keys after them, by the actions that made them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$made					[ 'keys' => [ ... ], 'slots' => [ ... ] ]
		 *
		 *	@return 	void
		 */
		private static function _unmake( array &$appData, array $made ): void {

			foreach( array_reverse( $made['slots'] ) as $uri )
				self::_api( $appData, 'slots/delete', [ 'uri' => $uri ] );

			foreach( array_reverse( $made['keys'] ) as $name )
				self::_api( $appData, 'keys/delete', [ 'key' => $name ] );
		}

		/**
		 *	An action of another panel, called the way the workbench calls it: the data posted, the request answered
		 *	into - and the posted data of this request put back
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$action				keys/create, keys/savebatch, keys/delete, slots/create or slots/delete
		 *	@param		array			$data
		 *
		 *	@return 	array										[ status, body ]
		 */
		private static function _api( array &$appData, string $action, array $data ): array {

			$posted		= $_POST;
			$request	= [ '/nino/http/response' => [ 'statusCode' => 200 ] ];

			$_POST['data'] = (string) json_encode( $data );

			try {

				match( $action ) {
					'keys/create'		=> \Nino\Modules\Text\Keys::apiCreate( $appData, $request ),
					'keys/savebatch'	=> \Nino\Modules\Text\Keys::apiSaveBatch( $appData, $request ),
					'keys/delete'		=> \Nino\Modules\Text\Keys::apiDelete( $appData, $request ),
					'slots/delete'		=> \Nino\Modules\Images\Slots::apiDelete( $appData, $request ),
					default					=> \Nino\Modules\Images\Slots::apiCreate( $appData, $request ),
				};
			}
			finally {
				$_POST = $posted;
			}

			$body = $request['/nino/http/response']['body'] ?? [];

			return [ (int) ( $request['/nino/http/response']['statusCode'] ?? 0 ), is_array( $body ) === true ? $body : (array) json_decode( (string) $body, true ) ];
		}
	}

}
