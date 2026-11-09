<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Menu				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Menu							A menu of the site whose entries may open a dropdown panel
	 *										of content of their own, drawn by [menu /<key>].
	 *
	 *										A menu has one level. An entry is a link to a page of the
	 *										site or to an address, and may carry a panel of one to
	 *										four columns; a column holds titles, subtitles, pictures,
	 *										links to pages and to addresses, and HTML+ as it was typed
	 *										- and nothing else. The words are text keys of the grammar
	 *										/feature/menu/<menu>/<name> that the Text panel translates,
	 *										the pictures are image slots of the same name; the menus
	 *										themselves are /data/menus.php, written by the panel of
	 *										the feature. A menu key may be one of the kernel's
	 *										(/nino/html/navs): the menu then stands in for the kernel's
	 *										navigation of that name wherever the Design's frames name
	 *										it, see callbackNavigations().
	 *
	 *										The shortcode draws the hull of the kernel's navigation
	 *										(\Nino\Modules\Navigation::$html) around the list, so that
	 *										Nino.ui.js folds it behind the burger below 768px and the
	 *										header sets of the Design style it as they do the kernel's;
	 *										the panels are menu.js and menu.css.
	 *
	 *										What a menu holds is validated by entries() whether it is
	 *										written or read: a save names the entry that is wrong, a
	 *										page draws what is right and leaves out the rest.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Menu {

		public const string STORAGE = '/data/menus.php';
		public const string TEMPLATES = '/features/Menu/templates';
		public const string NAVIGATIONS = '/feature/design/navigations';

		// A menu key and an entry id are the words of a text key and of an image slot, which start with a letter
		public const string SLUG = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D';
		public const int MAX_SLUG = 32;
		public const string RESERVED_MENU = 'panel';
		public const int MAX_ENTRIES = 24;
		public const int MAX_ITEMS = 24;
		public const int MAX_COLUMNS = 4;
		public const int MAX_HTML = 20000;
		public const int MAX_HREF = 500;

		// The kinds of the items of a column, and no others
		public const array ITEMS = [ 'title', 'subtitle', 'image', 'page', 'link', 'html' ];

		/**
		 *	The /_admin screen this feature brings along
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Menu\Admin::class ];
		}

		/**
		 *	Register the shortcode and answer the Design's question for the menus
		 *	it can put into a frame. No restore callback: a menu is content, and
		 *	the text files and the image slots it points at come back with the
		 *	same backup, so the plain copy of /data/menus.php is the state they
		 *	belong to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Feature::shortcode( $appData, 'menu', [ self::class, 'doShortcode' ] );
			\Nino\Callbacks::registerCallback( $appData, self::NAVIGATIONS, [ self::class, 'callbackNavigations' ] );
		}

		/**
		 *	A later shape of /data/menus.php is migrated here; 1.0.0 is the first, so
		 *	there is nothing to move and the file is left exactly as it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$fromVersion	The version recorded
		 *
		 *	@return 	bool
		 */
		public static function upgrade( array &$appData, string $fromVersion ): bool {
			return true;
		}

		/**
		 *	The navigations this feature offers the Design: one for every menu in the
		 *	data, [menu /<key> burger] in the header and [menu /<key>] in the footer.
		 *	Fired by the Design with [ 'part' => 'header'|'footer', 'options' => [ ... ] ];
		 *	what is added is options, and nothing else of the argument changes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$args				(reference) [ part, options ]
		 *
		 *	@return 	void
		 */
		public static function callbackNavigations( array &$appData, array &$args ): void {

			$options = is_array( $args['options'] ?? null ) === true ? $args['options'] : [];

			foreach( array_keys( self::menus( $appData ) ) as $key )
				$options['menu-'. $key] = [
					'shortcode'	=> ( $args['part'] ?? '' ) === 'header' ? '[menu /'. $key. ' burger]' : '[menu /'. $key. ']',
					'title'			=> [ 'de_DE' => 'Menü „'. $key. '“', 'en_US' => 'Menu "'. $key. '"' ],
				];

			$args['options'] = $options;
		}

		/**
		 *	The menus of the data by key, each as [ 'entries' => ... ] read with entries(): what is not
		 *	valid is not in it. A key that is no slug is no menu
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										key => [ 'entries' => [ ... ] ]
		 */
		public static function menus( array &$appData ): array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::STORAGE, [] );
			$menus = [];

			foreach( is_array( $stored ) === true ? $stored : [] as $key => $menu ) {

				if( is_string( $key ) === false || self::isMenuKey( $key ) === false || is_array( $menu ) === false )
					continue;

				$problems = [];
				$menus[$key] = [ 'entries' => self::entries( $key, $menu['entries'] ?? [], $problems, true ) ];
			}

			return $menus;
		}

		/**
		 *	Whether a text is a slug a menu key and an entry id may be
		 *
		 *	@param		string		$text
		 *
		 *	@return 	bool
		 */
		public static function isSlug( string $text ): bool {
			return strlen( $text ) <= self::MAX_SLUG && preg_match( self::SLUG, $text ) === 1;
		}

		/**
		 *	Whether a text is a key a menu may have: a slug, and not panel - the keys of a menu are /feature/menu/<menu>/<name>, and
		 *	/feature/menu/panel/open is the feature's own word of the button that opens a panel
		 *
		 *	@param		string		$text
		 *
		 *	@return 	bool
		 */
		public static function isMenuKey( string $text ): bool {
			return self::isSlug( $text ) === true && $text !== self::RESERVED_MENU;
		}

		/**
		 *	Whether a text is the Element-URI of a page: a path of letters, digits, hyphens, underscores and
		 *	dots, with none of the segments being . or .. - the form of the details a page is given under
		 *	/_nino/webpage<uri>/name
		 *
		 *	@param		string		$uri
		 *
		 *	@return 	bool
		 */
		public static function isUri( string $uri ): bool {
			return strlen( $uri ) > 1 && strlen( $uri ) <= 200 && preg_match( '#^/[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*$#D', $uri ) === 1 && preg_match( '#(?:^|/)\.{1,2}(?:/|$)#', $uri ) !== 1;
		}

		/**
		 *	An address as it may be written into a link: a fragment as it stands, a path of this site with the
		 *	directory of the project in front of it, an address with the scheme https, http, mailto or tel.
		 *	Anything else - another scheme, a protocol-relative address, a space or a control character in it -
		 *	is none
		 *
		 *	@param		string		$href
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *
		 *	@return 	string										'' for an address that is not allowed
		 */
		public static function address( string $href, string $dir ): string {

			$href = trim( $href );

			if( $href === '' || strlen( $href ) > self::MAX_HREF || preg_match( '/[\x00-\x20\x7f]/', $href ) === 1 )
				return '';

			if( $href[0] === '#' )
				return $href;

			// A path that climbs - with a dot segment, written or percent-encoded - leaves the site's own pages for what the server makes of it
			$path = (string) preg_split( '/[?#]/', $href )[0];

			if( $href[0] === '/' && ( preg_match( '#(?:^|/)\.{1,2}(?:/|$)#', $path ) === 1 || stripos( $path, '%2e' ) !== false || str_contains( $path, '\\' ) === true ) )
				return '';

			if( $href[0] === '/' )
				return isset( $href[1] ) === true && ( $href[1] === '/' || $href[1] === '\\' ) ? '' : $dir. $href;

			return preg_match( '#^(?:https?://[^/?\#]\S*|mailto:\S+|tel:[+0-9().;-]+)$#iD', $href ) === 1 ? $href : '';
		}

		/**
		 *	The name of a text key or an image slot of an entry: /feature/menu/<menu>/<id> for the label of the
		 *	entry itself, /feature/menu/<menu>/<id>-<n> for the n-th thing in its panel
		 *
		 *	@param		string		$menu
		 *	@param		string		$id
		 *	@param		int				$number				0 for the entry's own
		 *
		 *	@return 	string
		 */
		public static function name( string $menu, string $id, int $number = 0 ): string {
			return '/feature/menu/'. $menu. '/'. $id. ( $number > 0 ? '-'. $number : '' );
		}

		/**
		 *	The entries of a menu, read: the ones that are valid come back in the shape the data has, and the
		 *	problems are told, each naming the entry - its id where it has one, else its place - and what is
		 *	wrong. A list of any other shape, an entry or an item that is no array and a key that is none of
		 *	the entry's own are problems too. Whoever writes (the panel) refuses a menu that has any;
		 *	whoever draws leaves out what is wrong
		 *
		 *	@param		string		$menu					The menu's key
		 *	@param		mixed			$raw					What the data (or a request) holds for 'entries'
		 *	@param		array			&$problems		(reference) Appended: [ 'entry' => the id or '#<place>', 'why' => a sentence in English ]
		 *	@param		bool			$draw					Whether the entries are read to be drawn: an address that is not allowed is kept then, and drawn as #; written, it is a problem
		 *
		 *	@return 	array										The valid entries, in order
		 */
		public static function entries( string $menu, mixed $raw, array &$problems, bool $draw = false ): array {

			if( is_array( $raw ) === false || array_is_list( $raw ) === false ) {
				$problems[] = [ 'entry' => '#0', 'why' => 'the entries are a list' ];
				return [];
			}

			if( count( $raw ) > self::MAX_ENTRIES )
				$problems[] = [ 'entry' => '#0', 'why' => 'a menu has '. self::MAX_ENTRIES. ' entries at the most' ];

			$entries = [];
			$ids = [];

			foreach( array_slice( $raw, 0, self::MAX_ENTRIES ) as $place => $entry ) {

				$id = is_array( $entry ) === true && is_string( $entry['id'] ?? null ) === true && self::isSlug( $entry['id'] ) === true ? $entry['id'] : '#'. ( $place + 1 );
				$why = '';
				$read = self::_entry( $menu, $entry, $ids, $why, $draw );

				if( $read === null ) {
					$problems[] = [ 'entry' => $id, 'why' => $why ];
					continue;
				}

				$ids[$read['id']] = true;
				$entries[] = $read;
			}

			return $entries;
		}

		/**
		 *	One entry read
		 *
		 *	@param		string		$menu
		 *	@param		mixed			$entry
		 *	@param		array			$ids					The ids that are taken: id => true
		 *	@param		string		&$why					(reference) Why it is none
		 *
		 *	@return 	array|null
		 */
		private static function _entry( string $menu, mixed $entry, array $ids, string &$why, bool $draw ): ?array {

			if( is_array( $entry ) === false )
				return self::_no( $why, 'an entry is a map' );

			$id = $entry['id'] ?? null;

			if( is_string( $id ) === false || self::isSlug( $id ) === false || preg_match( '/-[0-9]+$/D', $id ) === 1 || $id === 'list' )
				return self::_no( $why, 'the id is a slug of lower case words that does not end in a number and is not "list"' );

			if( isset( $ids[$id] ) === true )
				return self::_no( $why, 'the id is the id of another entry' );

			$own = self::name( $menu, $id );
			$read = [ 'id' => $id, 'kind' => $entry['kind'] ?? null ];

			if( $read['kind'] === 'page' ) {

				if( is_string( $entry['uri'] ?? null ) === false || self::isUri( $entry['uri'] ) === false )
					return self::_no( $why, 'the uri is the path of a page' );

				$read['uri'] = $entry['uri'];

				if( ( $entry['label'] ?? null ) !== '' && ( $entry['label'] ?? null ) !== $own )
					return self::_no( $why, 'the label of a page is empty or the key '. $own );
			}
			else if( $read['kind'] === 'link' ) {

				$link = self::_link( $entry, $draw, $why );

				if( $link === null )
					return null;

				$read += $link;

				if( ( $entry['label'] ?? null ) !== $own )
					return self::_no( $why, 'the label of a link is the key '. $own );
			}
			else
				return self::_no( $why, 'the kind is page or link' );

			$read['label'] = $entry['label'];
			$read['panel'] = null;

			if( ( $entry['panel'] ?? null ) === null )
				return $read;

			$panel = self::_panel( $menu, $id, $entry['panel'], $why, $draw );

			if( $panel === null )
				return null;

			$read['panel'] = $panel;

			return $read;
		}

		/**
		 *	The address and the target of a link, which is an entry or an item
		 *
		 *	@param		array			$link
		 *
		 *	@return 	array|null								[ 'href', 'target' ]; null where the address is not allowed
		 */
		private static function _link( array $link, bool $draw, string &$why ): ?array {

			if( is_string( $link['href'] ?? null ) === false )
				return self::_no( $why, 'the address is text' );

			if( in_array( $link['target'] ?? '', [ '', '_blank' ], true ) === false )
				return self::_no( $why, 'the target is empty (the same window) or _blank (a new one)' );

			// What a page draws writes # for an address that is not allowed; what is written is refused
			if( $draw === true ? ( strlen( $link['href'] ) > self::MAX_HREF || str_contains( $link['href'], "\0" ) === true ) : self::address( $link['href'], '' ) === '' )
				return self::_no( $why, 'the address is a path, a fragment or an address with https, http, mailto or tel' );

			return [ 'href' => trim( $link['href'] ), 'target' => $link['target'] ?? '' ];
		}

		/**
		 *	The panel of an entry: its columns and the items in them
		 *
		 *	@param		string		$menu
		 *	@param		string		$id
		 *	@param		mixed			$panel
		 *	@param		string		&$why
		 *
		 *	@return 	array|null
		 */
		private static function _panel( string $menu, string $id, mixed $panel, string &$why, bool $draw ): ?array {

			if( is_array( $panel ) === false || is_int( $panel['columns'] ?? null ) === false || $panel['columns'] < 1 || $panel['columns'] > self::MAX_COLUMNS )
				return self::_no( $why, 'a panel has 1 to '. self::MAX_COLUMNS. ' columns' );

			if( is_array( $panel['cols'] ?? null ) === false || array_is_list( $panel['cols'] ) === false || count( $panel['cols'] ) !== $panel['columns'] )
				return self::_no( $why, 'a panel has as many lists of items as it has columns' );

			$names = [];
			$cols = [];

			foreach( $panel['cols'] as $items ) {

				if( is_array( $items ) === false || array_is_list( $items ) === false || count( $items ) > self::MAX_ITEMS )
					return self::_no( $why, 'a column is a list of at most '. self::MAX_ITEMS. ' items' );

				$col = [];

				foreach( $items as $item ) {

					$read = self::_item( $menu, $id, $item, $names, $why, $draw );

					if( $read === null )
						return null;

					$col[] = $read;
				}

				$cols[] = $col;
			}

			return [ 'columns' => $panel['columns'], 'cols' => $cols ];
		}

		/**
		 *	One item of a column
		 *
		 *	@param		string		$menu
		 *	@param		string		$id
		 *	@param		mixed			$item
		 *	@param		array			&$names				(reference) The keys and slots of the entry's items: name => true
		 *	@param		string		&$why
		 *
		 *	@return 	array|null
		 */
		private static function _item( string $menu, string $id, mixed $item, array &$names, string &$why, bool $draw ): ?array {

			if( is_array( $item ) === false || in_array( $item['kind'] ?? null, self::ITEMS, true ) === false )
				return self::_no( $why, 'an item is a title, a subtitle, an image, a page, a link or html' );

			$kind = $item['kind'];

			if( $kind === 'html' ) {

				if( is_string( $item['html'] ?? null ) === false || strlen( $item['html'] ) > self::MAX_HTML || str_contains( $item['html'], "\0" ) === true )
					return self::_no( $why, 'the html is text of at most '. self::MAX_HTML. ' bytes without a null byte' );

				return [ 'kind' => 'html', 'html' => $item['html'] ];
			}

			// What the item is called: the key of a text, the slot of a picture, the label of a link
			$field = match( $kind ) { 'image' => 'slot', 'page', 'link' => 'label', default => 'key' };
			$name = $item[$field] ?? null;

			if( $kind === 'page' && $name === '' )
				$name = null;
			else if( is_string( $name ) === false || preg_match( '#^'. preg_quote( self::name( $menu, $id ), '#' ). '-([1-9][0-9]{0,2})$#D', $name ) !== 1 )
				return self::_no( $why, 'the '. $field. ' of '. ( $kind === 'image' ? 'a picture' : 'an item' ). ' is '. self::name( $menu, $id ). ', a hyphen and a number from 1' );
			else if( isset( $names[$name] ) === true )
				return self::_no( $why, 'the name '. $name. ' belongs to two items' );

			if( $name !== null )
				$names[$name] = true;

			return match( $kind ) {
				'page'	=> is_string( $item['uri'] ?? null ) === true && self::isUri( $item['uri'] ) === true
					? [ 'kind' => 'page', 'uri' => $item['uri'], 'label' => $name ?? '' ]
					: self::_no( $why, 'the uri is the path of a page' ),
				'link'	=> ( $link = self::_link( $item, $draw, $why ) ) === null || $name === null
					? ( $link === null ? null : self::_no( $why, 'a link has a label' ) )
					: [ 'kind' => 'link' ] + $link + [ 'label' => $name ],
				'image'	=> [ 'kind' => 'image', 'slot' => $name ],
				default	=> [ 'kind' => $kind, 'key' => $name ],
			};
		}

		/**
		 *	Say why and answer none
		 *
		 *	@param		string		&$why
		 *	@param		string		$because
		 *
		 *	@return 	null
		 */
		private static function _no( string &$why, string $because ): null {
			$why = $because;
			return null;
		}

		/**
		 *	[menu /<key>] and [menu /<key> burger]: the list of the menu in the hull of the kernel's navigation
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The arguments: the menu's key as the first word, burger as a flag
		 *
		 *	@return 	string										The menu; '' for a key that is none, a menu that is not there or one with nothing to draw
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$key = '';

			foreach( $args as $name => $value )
				if( is_int( $name ) === true && is_string( $value ) === true && str_starts_with( $value, '/' ) === true ) {
					$key = substr( $value, 1 );
					break;
				}

			if( self::isMenuKey( $key ) === false )
				return '';

			$stored = \Nino\Filesystem::getFileContent( $appData, self::STORAGE, [] );

			if( is_array( $stored ) === false || is_array( $stored[$key] ?? null ) === false )
				return '';

			$problems = [];
			$entries = self::entries( $key, $stored[$key]['entries'] ?? [], $problems, true );

			// The second menu of this key on a page (the header's and the footer's) gets ids of its own
			$count = $appData['./menu/count'][$key] = (int) ( $appData['./menu/count'][$key] ?? 0 ) + 1;
			// Joined with a double hyphen, which no slug has: the id of a panel of one menu is never the id of another's
			$base = 'nino-menu-'. $key. ( $count > 1 ? '--'. $count : '' );
			$current = (string) ( \Nino\Http::getRequest( $appData )['/nino/http/response']['uri'] ?? '' );
			$dir = rtrim( \Nino\Filesystem::getDir( $appData ), '/' );
			$items = '';

			foreach( $entries as $entry )
				$items .= self::_drawEntry( $appData, $entry, $base, $current, $dir );

			if( $items === '' )
				return '';

			$list = str_replace( '[[items]]', $items, \Nino\Feature::template( $appData, self::TEMPLATES, 'menu-list' ) );

			if( \Nino\Feature::flag( $args, 'burger' ) === true )
				return str_replace( [ '[[id]]', '[[class]]', '[[list]]', '[[content]]' ], [ $base, 'nino-menu-wrap', $base. '--list', $list ], \Nino\Modules\Navigation::$html['nav-burger'] );

			return str_replace( [ '[[id]]', '[[class]]', '[[content]]' ], [ $base, 'nino-menu-wrap', $list ], \Nino\Modules\Navigation::$html['nav-regular'] );
		}

		/**
		 *	One entry of the list
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$entry				As entries() reads it
		 *	@param		string		$base					nino-menu-<key>, the start of the ids of the panels
		 *	@param		string		$current			The Element-URI of the page that is shown
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *
		 *	@return 	string										'' for an entry with nothing to show: a page that has no route or no name
		 */
		private static function _drawEntry( array &$appData, array $entry, string $base, string $current, string $dir ): string {

			// The link of an entry with a panel has an id, which the button of the panel is described by: every button would else have the same name
			$id = $base. '--'. $entry['id'];
			$link = self::_drawLink( $appData, $entry, $current, $dir, 'menu-link', $entry['panel'] !== null ? $id. '--link' : '' );

			if( $link === '' )
				return '';

			$panel = '';
			$here = $entry['kind'] === 'page' && $entry['uri'] === $current;

			if( $entry['panel'] !== null ) {

				$cols = '';

				foreach( $entry['panel']['cols'] as $items ) {

					$col = '';

					foreach( $items as $item ) {

						$col .= self::_drawItem( $appData, $item, $current, $dir );

						if( $item['kind'] === 'page' && $item['uri'] === $current )
							$here = true;
					}

					if( $col !== '' )
						$cols .= str_replace( '[[items]]', $col, \Nino\Feature::template( $appData, self::TEMPLATES, 'menu-col' ) );
				}

				// A panel whose items all have nothing to show is not drawn, and its button with it
				if( $cols !== '' )
					$panel = str_replace( [ '[[id]]', '[[linkid]]', '[[columns]]', '[[cols]]' ], [ $id, $id. '--link', (string) $entry['panel']['columns'], $cols ], \Nino\Feature::template( $appData, self::TEMPLATES, 'menu-panel' ) );
			}

			return str_replace(
				[ '[[modifiers]]', '[[link]]', '[[panel]]' ],
				[ ( $panel === '' ? '' : ' nino-menu-item--panel' ). ( $here === true ? ' nino-menu-item--current' : '' ), $link, $panel ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'menu-entry' )
			);
		}

		/**
		 *	The link of an entry or of a link item: a page's, with its name as the label where the label is empty
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$link					An entry or an item of the kind page or link
		 *	@param		string		$current
		 *	@param		string		$dir
		 *	@param		string		$template			menu-link for the link of an entry, menu-sublink for one in a panel
		 *	@param		string		$id						The id of the link, '' for none
		 *
		 *	@return 	string
		 */
		private static function _drawLink( array &$appData, array $link, string $current, string $dir, string $template, string $id = '' ): string {

			$label = (string) $link['label'];
			$active = false;

			if( $link['kind'] === 'page' ) {

				$route = \Nino\Http::findRouteUri( $appData, $link['uri'], \Nino\Locales::getCurrentLocale( $appData ) );

				// A page that has no route any more is not a link to draw
				if( $route === null || str_ends_with( $route, '/*' ) === true )
					return '';

				$href = $dir. substr( $route, strlen( 'GET:/' ) );
				$label = $label === '' ? '/_nino/webpage'. $link['uri']. '/name' : $label;
				$active = $link['uri'] === $current;
				$target = '';
			}
			else {
				$href = self::address( $link['href'], $dir );
				$target = $link['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
			}

			// A label nobody wrote in any language is no link to draw
			if( \Nino\Html::renderTextfill( $appData, $label ) === '' )
				return '';

			return str_replace(
				[ '[[id]]', '[[active]]', '[[current]]', '[[target]]', '[[href]]', '[[label]]' ],
				[ $id === '' ? '' : ' id="'. $id. '"', $active === true ? ' nino-is-active' : '', $active === true ? ' aria-current="page"' : '', $target, \Nino\Modules\Components::escape( $href === '' ? '#' : $href ), '[['. $label. ']]' ],
				\Nino\Feature::template( $appData, self::TEMPLATES, $template )
			);
		}

		/**
		 *	One item of a column
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$item					As entries() reads it
		 *	@param		string		$current
		 *	@param		string		$dir
		 *
		 *	@return 	string										'' for an item with nothing to show - a text nobody wrote, an empty slot, a page that is gone
		 */
		private static function _drawItem( array &$appData, array $item, string $current, string $dir ): string {

			switch( $item['kind'] ) {

				case 'html':
					return $item['html'];

				case 'page':
				case 'link':
					return self::_drawLink( $appData, $item, $current, $dir, 'menu-sublink' );

				case 'image':

					$slot = \Nino\Images::getSlot( $appData, $item['slot'] );

					if( $slot === false || is_string( $slot['filename'] ?? null ) === false || $slot['filename'] === '' )
						return '';

					$alt = is_array( $slot['alt'] ?? null ) === true ? $slot['alt'] : [];
					$text = $alt[\Nino\Locales::getCurrentLocale( $appData )] ?? $alt[\Nino\Locales::getNativeLocale( $appData )] ?? '';

					return str_replace(
						[ '[[alt]]', '[[src]]' ],
						[ \Nino\Modules\Components::escape( (string) $text ), \Nino\Modules\Components::escape( \Nino\Images::getUrl( $appData, $slot['filename'] ) ) ],
						\Nino\Feature::template( $appData, self::TEMPLATES, 'menu-image' )
					);

				default:

					if( \Nino\Html::renderTextfill( $appData, $item['key'] ) === '' )
						return '';

					return str_replace( '[[text]]', '[['. $item['key']. ']]', \Nino\Feature::template( $appData, self::TEMPLATES, 'menu-'. $item['kind'] ) );
			}
		}
	}
}
