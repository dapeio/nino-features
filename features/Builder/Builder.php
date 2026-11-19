<?php
declare(strict_types=1);
/**
 *	Nino							A compact filesystembased php framework
 *	Modules\Builder		see _nino/Nino/Modules/Modules.php for the
 *										package-level docblock
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules						All optional modules
	 *	Builder						A page as a row of blocks: the list of the pages, the page drawn as a draft with its blocks moved, copied or left out and its
	 *										texts written where they stand, saved in one step with the last state kept
	 *
	 *										What a request does is little: one that carries the query of a preview - \Nino\Preview::QUERY - is asked whether
	 *										the session holds the draft and the account still has the right, and then draws the draft in place of the template of
	 *										the page, with the trace and the draft's texts for the language of the view. Any other request is the page as it
	 *										is and is not looked at. Everything else is the API of the workbench (Admin): the tree a person sends is checked against
	 *										the one on disk by one rule (Tree::refused()), and a save writes the last state, the words, the slots and the
	 *										template in that order (Store).
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Builder {

		// The right to work in the panel and through its API; the Editor role does not get it by itself
		public const string PERM = '/_admin/builder/manage';

		// What a person may do once inside, in two steps: the content (the blocks of a page and the words in them) and the design (the components of a block and their
		// settings). The scope is the panel's: an account that holds no permission below it - the door alone - is not described in detail and may design; one that is
		// described holds what it is given, and the permission of a step is any that lies below it. The frames of the site and the call of html are no step of this panel:
		// they are written in the editor of the blocks, with the right of that panel (see Themelab\Frames)
		public const string SCOPE = '/_admin/builder/';
		public const string CONTENT = '/_admin/builder/content/edit';
		public const string DESIGN = '/_admin/builder/design/edit';

		// The step that was once "Development" and the permissions of it: they have no effect any more, and the update of the feature gives whoever held one the right of the
		// Themelab (see upgrade())
		public const string DEVELOPED = '/_admin/builder/develop/';

		// The steps as numbers. The third is no step of an account: it is what a request has that the editor of the blocks passes on for a frame, which may be written down to its
		// markup - html among it
		public const int LEVEL_CONTENT = 1;
		public const int LEVEL_DESIGN = 2;
		public const int LEVEL_DEVELOP = 3;

		// The right of Themelab, by its name: the bridge from a page to it is shown to whoever holds it
		public const string BRIDGE = '/_admin/themelab/manage';

		// What the editor of the blocks tells the actions of this panel it runs for it: this request is for a frame of the site and the right of the editor was held
		public const string AS = './nino/builder/as';

		// What the drafts of the previews are called in \Nino\Preview - and the ones of a frame, which the editor of the blocks holds with the right of that panel
		public const string KIND = 'builder';
		public const string KIND_FRAME = 'builder-frame';

		// The right of the panel Routes: a page is made with its routes, so whoever makes one holds it as well as the step of the design
		public const string ROUTES = '/_admin/routes/manage';

		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'request' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/pages/removed', [ self::class, 'removed' ] );
		}

		/**
		 *	A page was removed by the kernel: what the Builder keeps of it - its last state, the drafts of its previews - goes with it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$event				(reference) What \Nino\Pages::remove() answered: 'template' is the name of the page that is gone
		 *
		 *	@return 	void
		 */
		public static function removed( array &$appData, array &$event ): void {

			if( is_string( $event['template'] ?? null ) === true )
				\Nino\Modules\Builder\Store::forget( $appData, $event['template'] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Builder\Admin::class ];
		}

		/**
			 *	The step of work the signed-in account may do: 2 for the one who may design - the door without a description in detail, the permission of the design, the blanket of the
		 *	panel or of the site, and the step of the development of an older version, which is no more than the design here -, 1 for the content, 0 for none of them. 3 is no step
		 *	of this panel: it is the step of the editor of the blocks, which that editor gives a request for a frame through the mark AS, and nothing a request brings gives it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function level( array &$appData ): int {

			// What held the step of the development held that of the design too: it still does, until the update has written the design into the account
			if( \Nino\Admin\Admin::scoped( $appData, self::SCOPE, self::DESIGN ) === true || \Nino\Auth::checkPermission( $appData, self::DEVELOPED. 'edit' ) === true )
				return self::LEVEL_DESIGN;

			return \Nino\Admin\Admin::scoped( $appData, self::SCOPE, self::CONTENT ) === true ? self::LEVEL_CONTENT : 0;
		}

		/**
		 *	The step of the development is gone from this panel: what it was for - the markup of a component, the frames - is written in the editor of the blocks. The accounts that held
		 *	it are given what that costs them, in plain sight and once: the right of the editor of the blocks - which is the whole of what the step could do, own blocks included - and
		 *	the step of the design in place of the one that is gone. The blanket of the site and of the whole of the panel hold what they held; nobody holds more than before
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$fromVersion	The version that was installed
		 *
		 *	@return 	bool
		 */
		public static function upgrade( array &$appData, string $fromVersion ): bool {

			// The move is made once, by the update that comes from a version before this one: a later update gives nobody the right - not back to one it was taken from, not to
			// an account that was made after the move with the blanket of the panel
			if( version_compare( $fromVersion, '1.1.0', '>=' ) === true )
				return true;

			// Who could develop: the door of the panel and the step held together, as the account holds them - by a permission of its own, by a blanket or by its role - the
			// same reading of a list of permissions that gave the step to a request
			$could = static fn( array $perms ): bool => \Nino\Auth::holds( $perms, self::PERM ) === true && \Nino\Auth::holds( $perms, self::DEVELOPED. 'edit' ) === true;
			$dead = static fn( array $perms ): array => array_values( array_filter( $perms, static fn( string $perm ): bool => $perm !== self::DEVELOPED. 'edit' && $perm !== self::DEVELOPED. '*' ) );
			$given = static function( array $perms ) use ( $dead ): array {

				$perms = $dead( $perms );

				if( \Nino\Auth::holds( $perms, self::BRIDGE ) === false )
					$perms[] = self::BRIDGE;

				if( \Nino\Auth::holds( $perms, self::DESIGN ) === false )
					$perms[] = self::DESIGN;

				return array_values( array_unique( $perms ) );
			};
			$roles = (array) ( $appData['/nino/auth/roles'] ?? [] );
			$users = (array) ( $appData['/nino/auth/user'] ?? [] );
			$strings = static fn( mixed $perms ): array => is_array( $perms ) === true ? array_values( array_filter( $perms, 'is_string' ) ) : [];
			$grants = [];

			// An account is given the right where it could develop as it stands - by its own permissions and its role together - and gets it among its own; what holds the step
			// without the door never had a way in, and has nothing to move
			foreach( $users as $mail => $user )
				if( is_array( $user ) === true && $could( \Nino\Auth::permissions( $appData, $user ) ) === true )
					$grants['user'][$mail] = true;

			foreach( $roles as $id => $role )
				if( is_array( $role ) === true && $could( $strings( $role['perms'] ?? null ) ) === true )
					$grants['role'][$id] = true;

			foreach( $roles as $id => $role )
				if( is_array( $role ) === true && is_array( $role['perms'] ?? null ) === true )
					$appData['/nino/auth/roles'][$id]['perms'] = isset( $grants['role'][$id] ) === true ? $given( $strings( $role['perms'] ) ) : $dead( $strings( $role['perms'] ) );

			foreach( $users as $mail => $user ) {

				if( is_array( $user ) === false || is_array( $user['perms'] ?? null ) === false )
					continue;

				$mine = $strings( $user['perms'] );
				// Held through a role that was moved, the right is the role's; held any other way it is written into the account
				$viaRole = \Nino\Auth::holds( \Nino\Auth::permissions( $appData, [ 'perms' => [], 'role' => (string) ( $user['role'] ?? '' ) ] ), self::BRIDGE ) === true && isset( $grants['user'][$mail] ) === true && $could( $mine ) === false;
				$appData['/nino/auth/user'][$mail]['perms'] = isset( $grants['user'][$mail] ) === true && $viaRole === false ? $given( $mine ) : $dead( $mine );
			}

			// Nothing is written where nothing moved
			if( ( $appData['/nino/auth/roles'] ?? [] ) !== $roles || ( $appData['/nino/auth/user'] ?? [] ) !== $users )
				\Nino\AppData::writeContentData( $appData, [ '/nino/auth/roles', '/nino/auth/user' ] );

			return true;
		}

		/**
		 *	Whether the signed-in account may go from a page to the editor of the blocks: the right of that panel is held and the feature is on - what the bridge of a page shows
		 *	and nothing more: the actions of the editor ask for the right themselves
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function bridge( array &$appData ): bool {
			return ( \Nino\Features::all( $appData )['themelab']['active'] ?? false ) === true && \Nino\Auth::checkPermission( $appData, self::BRIDGE ) === true;
		}

		/**
		 *	The link to the script bundle of a page that is the picture of a block: none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The link, as the bundle's shortcode has it
		 *
		 *	@return 	string
		 */
		public static function noScript( array &$appData, string $template ): string {
			return '';
		}

		/**
		 *	The first stage of a request: does it ask for the draft of a page, and may it have it. A request without the query is not looked at; one with it that
		 *	may not have the draft - another session, an account without the right, a token that ran out - is the page as the visitors have it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function request( array &$appData, array &$request ): void {

			if( isset( $request['/nino/http/request']['query'][\Nino\Preview::QUERY] ) === false )
				return;

			// A draft of a page, or the small picture of a block in the dialog: a page that is drawn without the trace, and without the scripts of the site
			$held = \Nino\Preview::held( $appData, $request, self::KIND, self::PERM );
			$tile = false;

			if( $held === null ) {

				$held = \Nino\Preview::held( $appData, $request, \Nino\Modules\Builder\Library::KIND, self::PERM );
				$tile = true;
			}

			// The draft of a frame is the editor of the blocks': it is held for the right of that panel, and drawn as a page's is
			if( $held === null ) {

				$held = \Nino\Preview::held( $appData, $request, self::KIND_FRAME, self::BRIDGE );
				$tile = false;
			}

			if( $held === null )
				return;

			// The right was checked where the draft was held: the token is the session's and the account still has the permission
			self::draw( $appData, $request, $held['data'], true, $tile === false );
		}

		/**
		 *	Draw a draft in place of its template: the template is swapped for the draft's source, the language of the view is the draft's and its words are the fills of the
		 *	page. For a page of the Builder and for any other feature that holds drafts of its own with \Nino\Preview. The right is not looked at here and is no default: whoever
		 *	calls says, in $allowed, that the account may have the draft - a call that says no draws nothing - and a draft that is not one is not drawn
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		array			$draft				template (the name), source, fills (key => text), locale and slots (uri => slot of a picture)
		 *	@param		bool			$allowed			Whether the account may have the draft - the caller's check, with no default
		 *	@param		bool			$trace				With the trace of the drawing, as the views that mark the page need it; without it the scripts of the site are left out, as for the picture of a block
		 *
		 *	@return 	bool											Whether the draft was drawn
		 */
		public static function draw( array &$appData, array &$request, array $draft, bool $allowed, bool $trace ): bool {

			if( $allowed === false || is_string( $draft['template'] ?? null ) === false || is_string( $draft['source'] ?? null ) === false || is_array( $draft['fills'] ?? null ) === false )
				return false;

			if( $trace === true )
				\Nino\Preview::trace( $appData );
			else
				\Nino\Callbacks::registerCallback( $appData, '/nino/shortcodes/assets/output/js', [ self::class, 'noScript' ] );

			\Nino\Modules\Template::swap( $appData, '/templates/'. $draft['template'], $draft['source'] );

			// The language of the view: the words of the draft are the ones of that language, and the page is drawn in it for this request only
			$locale = is_string( $draft['locale'] ?? null ) === true ? $draft['locale'] : '';

			if( $locale !== '' && \Nino\Locales::verifyLocale( $appData, $locale ) === true ) {
				\Nino\Locales::useLocale( $appData, $locale );
				$request['/nino/http/response']['locale'] = $locale;
				\Nino\Html::addFills( $appData, array_map( 'strval', $draft['fills'] ), $locale );

				// The slots of the copies the draft holds: the pictures are drawn before they are saved
				foreach( (array) ( $draft['slots'] ?? [] ) as $uri => $slot )
					if( is_string( $uri ) === true && is_array( $slot ) === true )
						$appData['/nino/html/images'][$uri] = $slot;
			}

			return true;
		}

		/**
		 *	Draw the draft of ONE block - a source whose top level is a single call of a block with nothing but blanks round it - with the trace: the picture of a block that is
		 *	worked on, for a feature that edits the blocks of the library. The right is the caller's and is asked for here as a parameter that has no default; a draft with more than
		 *	one call, with free markup or with none is not drawn
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		array			$draft				As draw() takes it
		 *	@param		bool			$allowed			Whether the account may have the draft - the caller's check, with no default
		 *
		 *	@return 	bool											Whether the draft was drawn
		 */
		public static function drawBlock( array &$appData, array &$request, array $draft, bool $allowed ): bool {

			if( $allowed === false || is_string( $draft['source'] ?? null ) === false )
				return false;

			$calls = 0;

			foreach( \Nino\Modules\Builder\Tree::read( $appData, $draft['source'] ) as $node ) {

				if( isset( $node['n'] ) === true )
					$calls++;
				elseif( trim( (string) ( $node['t'] ?? '.' ), " \t\r\n" ) !== '' || isset( $node['f'] ) === true )
					return false;
			}

			return $calls === 1 && self::draw( $appData, $request, $draft, true, true );
		}
	}
}
