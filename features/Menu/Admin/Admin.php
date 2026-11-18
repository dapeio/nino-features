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
	 *	Admin							The Menu's workbench panel: the table of the menus, one menu on a view of its own
	 *										with its entries in order, and one entry on a view of its own with the columns of
	 *										its panel. A developer surface - an address and a piece of HTML+ are written, and a
	 *										save makes text keys and image slots - so every action, the reads among them, asks for
	 *										MANAGE_PERM, which is a developer permission as the Builder's is, and
	 *										a menu is written whole in one request. The keys and the slots are
	 *										made and the texts written by the panels that own them, which ask for
	 *										their own permissions: a save that has to make a key or write a text
	 *										is refused for an account that lacks the Text Keys permission, one that
	 *										has to make a slot for an account that lacks the Image Slots permission,
	 *										and one that brings an HTML+ part nobody had before for an account that lacks
	 *										the permission of the Elements panel, which is where the kernel lets a person
	 *										write rich text. The pictures of the slots are not this panel's to store: the
	 *										script posts them to the Images panel itself, once the menu is saved.
	 *
	 *										The names a menu has - the text keys and the slots, /feature/menu/<menu>/<name> -
	 *										belong to the one menu and the one entry that made them: a save refuses a name
	 *										that is not in the menu as it is stored when a text key or a slot of that name
	 *										exists, so a menu or an entry that is deleted and made again, or one whose
	 *										place another takes, never takes the words of the one before.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/menu/manage';

		// Rich text is written where the kernel lets a person write it: the Elements panel's own permission
		public const string HTML_PERM = '/_admin/elements/manage';

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
			return [ 'menu', '/_admin/nav/menu', 40 ];
		}

		// Three levels: the table of the menus, one menu, one entry with the columns of its panel
		public static function panes(): array {
			return [ 'menu-list', 'menu-form', 'menu-entry' ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><rect width="16" height="6" x="4" y="15" rx="1"/></svg>';
		}

		public static function assets(): array {
			return [
				\Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ),
				'/_admin/assets/html-editor.js',
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
		 *	The menus of the data: the key, how many entries each has and how many of them have a panel, and how
		 *	many menus the data may hold
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$menus = [];

			foreach( \Nino\Modules\Menu::menus( $appData ) as $key => $menu )
				$menus[] = [ 'key' => $key, 'entries' => count( $menu['entries'] ), 'panels' => count( array_filter( $menu['entries'], static fn( array $entry ): bool => $entry['panel'] !== null ) ) ];

			\Nino\Http::ok( $request, [ 'menus' => $menus, 'limit' => \Nino\Modules\Menu::MAX_MENUS ] + self::_registry( $appData ) );
		}

		/**
		 *	What the editor's forms are built from, asked again: the pages of the site, the languages and the image slots the menus use
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiRegistry( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, self::_registry( $appData ) );
		}

		/**
		 *	The pages of the site, the languages and the image slots the menus use
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _registry( array &$appData ): array {

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

			return [
				'pages'			=> array_values( $pages ),
				'locales'		=> $locales,
				'names'			=> (object) $names,
				'locale'		=> $locale,
				'native'		=> \Nino\Locales::getNativeLocale( $appData ),
				'slots'			=> $slots,
			];
		}

		/**
		 *	One menu as the editor works on it: its entries, the texts of every key they name in every language,
		 *	and the names of the keys and slots the menu has had, so that a new item is called what no item was.
		 *	A menu that is not there is a 404 - unless the editor asks for a fresh one, which it makes a draft
		 *	of: the names that earlier menus of that key left behind are what it must not take again
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiLoad( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = \Nino\Admin\Admin::postString( $data, 'key' );

			if( \Nino\Modules\Menu::isMenuKey( $key ) === false ) {
				\Nino\Http::fail( $request, 400, 'a menu key is a slug of lower case words', 'menu_key', [], 'key' );
				return;
			}

			$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
			$exists = is_array( $stored ) === true && is_array( $stored[$key] ?? null ) === true;

			if( $exists === false && ( $data['fresh'] ?? false ) !== true ) {
				\Nino\Http::fail( $request, 404, 'there is no such menu', 'menu_missing', [ $key ] );
				return;
			}

			if( $exists === false && is_array( $stored ) === true && count( $stored ) >= \Nino\Modules\Menu::MAX_MENUS ) {
				\Nino\Http::fail( $request, 400, 'there are '. \Nino\Modules\Menu::MAX_MENUS. ' menus at the most', 'menu_limit', [ \Nino\Modules\Menu::MAX_MENUS ] );
				return;
			}

			$problems = [];
			$entries = $exists === true ? \Nino\Modules\Menu::entries( $key, $stored[$key]['entries'] ?? [], $problems, true ) : [];
			$prefix = '/feature/menu/'. $key. '/';
			$names = self::_names( $entries );
			$texts = [];

			foreach( \Nino\Text::entries( $appData ) as $entry ) {

				if( str_starts_with( (string) $entry['key'], $prefix ) === false )
					continue;

				if( in_array( $entry['key'], $names, true ) === true )
					$texts[$entry['key']] = (object) array_map( static fn( mixed $text ): string => \Nino\Modules\Menu::plain( (string) $text ), array_filter( (array) $entry['values'], static fn( mixed $text ): bool => $text !== null ) );
			}

			\Nino\Http::ok( $request, [
				'key'				=> $key,
				'exists'		=> $exists,
				'version'		=> self::state( $appData, $key, $exists === true ? $stored[$key] : null ),
				'entries'		=> $entries,
				'texts'			=> (object) $texts,
				'taken'			=> self::_taken( $appData, $prefix ),
				'problems'	=> array_column( $problems, 'entry' ),
			] );
		}

		/**
		 *	The version of a menu as the file holds it: what an editor read is what it writes over, or the save is a conflict
		 *
		 *	@param		mixed			$menu					What the file holds under the key, null where it holds none
		 *
		 *	@return 	string
		 */
		public static function version( mixed $menu ): string {
			return sha1( serialize( $menu ) );
		}

		/**
		 *	Whether the file, as the lock holds it, has another menu under the key than the one the save was checked against: a save that came in
		 *	between is a conflict as well. Asked again under the lock, since the first check is made before the keys and the slots are made
		 *
		 *	@param		mixed			$state				The file's content
		 *	@param		string		$key
		 *	@param		string		$stand				version() of the menu the save was checked against
		 *
		 *	@return 	bool
		 */
		public static function changed( mixed $state, string $key, string $stand ): bool {
			return self::version( is_array( $state ) === true ? ( $state[$key] ?? null ) : null ) !== $stand;
		}

		/**
		 *	What an editor read of a menu, as one version: the file's entries and the words they name - every text of every key in each language, and the
		 *	alternative text and file of every picture - so that a session that only changed a word is a session that wrote something too
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The menu's key
		 *	@param		mixed			$menu					What the file holds under the key, null where it holds none
		 *
		 *	@return 	string
		 */
		public static function state( array &$appData, string $key, mixed $menu ): string {

			$words = [];

			if( is_array( $menu ) === true ) {

				$problems = [];
				$entries = \Nino\Modules\Menu::entries( $key, $menu['entries'] ?? [], $problems, true );
				$names = self::_names( $entries );

				foreach( \Nino\Text::entries( $appData ) as $entry )
					if( in_array( $entry['key'], $names, true ) === true )
						$words[(string) $entry['key']] = $entry['values'];

				foreach( self::_slots( $entries ) as $uri ) {
					$slot = \Nino\Images::getSlot( $appData, $uri );
					$words[$uri] = $slot === false ? false : [ $slot['filename'] ?? null, $slot['alt'] ?? null ];
				}

				ksort( $words );
			}

			return sha1( serialize( [ $menu, $words ] ) );
		}

		/**
		 *	Every text key and every image slot there is under a prefix - the names a menu of that key has made, whether the
		 *	menu is still there or not
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$prefix				/feature/menu/<menu>/
		 *
		 *	@return 	array										The names, each once
		 */
		private static function _taken( array &$appData, string $prefix ): array {

			$taken = [];

			foreach( \Nino\Text::entries( $appData ) as $entry )
				if( str_starts_with( (string) $entry['key'], $prefix ) === true )
					$taken[] = (string) $entry['key'];

			foreach( array_keys( \Nino\Images::getSlots( $appData ) ) as $uri )
				if( str_starts_with( (string) $uri, $prefix ) === true )
					$taken[] = (string) $uri;

			return array_values( array_unique( $taken ) );
		}

		/**
		 *	The menu written whole: refused with the entry that is wrong where it is not valid, and with the
		 *	permission of the panel that owns what it has to make - the Text Keys' for a key it makes or a text
		 *	it writes, the Image Slots' for a slot it makes, the Elements' for a piece of HTML+ nobody had before -
		 *	where the account lacks it. A name that the menu as it is stored does not have, and that a text key or a
		 *	slot has already, is refused: it was made by another menu or entry. The keys and the slots are
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

			$stored = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Menu::STORAGE, [] );
			$stored = is_array( $stored ) === true ? $stored : [];

			// What the editor read is what is there: another session wrote the menu since, or made it - the file is not written over unseen. A menu that did not exist
			// when it was read is never written over, whatever the editor says (force is for a menu that was there)
			$read = \Nino\Admin\Admin::postString( $data, 'version' );
			$now = self::state( $appData, $key, $stored[$key] ?? null );
			$absent = self::state( $appData, $key, null );

			// A menu that another session deleted is gone: there is nothing to write over, and the editor is told so. Force is for a menu that is there, with the version the editor read
			if( isset( $stored[$key] ) === false && $read !== $absent && preg_match( '/^[0-9a-f]{40}$/D', $read ) === 1 ) {
				\Nino\Http::fail( $request, 409, 'the menu was deleted by another session', 'menu_gone', [ $key ] );
				return;
			}

			if( $read !== $now && ( ( $data['force'] ?? false ) !== true || $read === $absent || preg_match( '/^[0-9a-f]{40}$/D', $read ) !== 1 ) ) {
				\Nino\Http::fail( $request, 409, 'the menu was changed by another session', 'menu_changed', [ $key ] );
				return;
			}

			if( isset( $stored[$key] ) === false && count( $stored ) >= \Nino\Modules\Menu::MAX_MENUS ) {
				\Nino\Http::fail( $request, 400, 'there are '. \Nino\Modules\Menu::MAX_MENUS. ' menus at the most', 'menu_limit', [ \Nino\Modules\Menu::MAX_MENUS ] );
				return;
			}

			// What the menu is as it stands: the names it has and the HTML+ it holds
			$had = [];
			$hadHtml = [];

			if( is_array( $stored[$key] ?? null ) === true ) {

				$before = [];
				$old = \Nino\Modules\Menu::entries( $key, $stored[$key]['entries'] ?? [], $before, true );
				$had = array_merge( self::_names( $old ), self::_slots( $old ) );
				$hadHtml = array_map( static fn( string $html ): string => \Nino\Html::sanitizeHtml( $html, 'blocks' ), self::_htmls( $old ) );
			}

			$names = self::_names( $entries );
			$slots = self::_slots( $entries );
			$texts = self::_texts( $appData, $data['texts'] ?? [], $names );

			if( $texts === null ) {
				\Nino\Http::fail( $request, 400, 'the texts are a map of the keys of the menu to a text in each language', 'menu_texts' );
				return;
			}

			$known = array_column( \Nino\Text::entries( $appData ), null, 'key' );

			// A name that is not the stored menu's own and that something has already: another menu or entry made it
			foreach( array_merge( $names, $slots ) as $name )
				if( in_array( $name, $had, true ) === false && ( isset( $known[$name] ) === true || \Nino\Images::getSlot( $appData, $name ) !== false ) ) {
					\Nino\Http::fail( $request, 409, 'the name '. $name. ' belongs to another menu or entry', 'menu_taken', [ $name ] );
					return;
				}

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

			if( array_diff( self::_htmls( $entries ), $hadHtml ) !== [] )
				$permissions[] = self::HTML_PERM;

			foreach( $permissions as $permission )
				if( \Nino\Auth::checkPermission( $appData, $permission ) === false ) {
					\Nino\Http::fail( $request, 403, 'this save makes keys or slots, writes texts or brings HTML+, which asks for the permission '. $permission, 'menu_permission', [ $permission ] );
					return;
				}

			$made = [ 'keys' => [], 'slots' => [] ];

			foreach( $newKeys as $name ) {

				[ $status, $body ] = self::_api( $appData, 'keys/create', [ 'key' => $name, 'global' => false, 'format' => 'plain', 'values' => $texts[$name] ?? [] ] );

				// A key that another request made in the meantime (409) is theirs: it is not this menu's and it is not taken away
				if( $status !== 200 ) {
					self::_unmake( $appData, $made );
					\Nino\Http::fail( $request, $status, (string) ( $body['error'] ?? 'the key could not be made' ), 'menu_key_made', [ $name ] );
					return;
				}

				$made['keys'][] = $name;
			}

			foreach( $newSlots as $uri ) {

				[ $status, $body ] = self::_api( $appData, 'slots/create', [ 'uri' => $uri, 'label' => ucfirst( str_replace( '-', ' ', substr( $uri, strlen( '/feature/menu/' ) ) ) ), 'width' => self::SLOT_WIDTH, 'height' => self::SLOT_HEIGHT ] );

				if( $status !== 200 ) {
					self::_unmake( $appData, $made );
					\Nino\Http::fail( $request, $status, (string) ( $body['error'] ?? 'the slot could not be made' ), 'menu_slot_made', [ $uri ] );
					return;
				}

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

			$raced = false;
			$stand = self::version( $stored[$key] ?? null );
			$written = \Nino\Filesystem::mutate( $appData, \Nino\Modules\Menu::STORAGE, static function( mixed $state ) use ( $key, $entries, $stand, &$raced ): ?array {

				$state = is_array( $state ) === true ? $state : [];

				// Looked at again under the lock: a save that came in between is a conflict as well. The menus stand in the order they were made
				if( self::changed( $state, $key, $stand ) === true ) {
					$raced = true;
					return null;
				}

				$state[$key] = [ 'entries' => $entries ];

				return $state;
			}, [] );

			if( $raced === true ) {
				self::_api( $appData, 'keys/savebatch', [ 'items' => $before ] );
				self::_unmake( $appData, $made );
				\Nino\Http::fail( $request, 409, 'the menu was changed by another session', 'menu_changed', [ $key ] );
				return;
			}

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
		 *	The HTML+ of every part of the entries that is HTML+
		 *
		 *	@param		array			$entries
		 *
		 *	@return 	array
		 */
		private static function _htmls( array $entries ): array {

			$htmls = [];

			foreach( $entries as $entry )
				foreach( $entry['panel']['cols'] ?? [] as $items )
					foreach( $items as $item )
						if( $item['kind'] === 'html' )
							$htmls[] = $item['html'];

			return $htmls;
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
