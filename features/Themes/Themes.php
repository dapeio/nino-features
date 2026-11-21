<?php
declare(strict_types=1);
/**
 *	Nino							A compact filesystembased php framework
 *	Modules\Themes		see _nino/Nino/Modules/Modules.php for the
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
	 *	Themes						The look of the site as a choice: a theme brings a part for the page and for each group of the
	 *										components, the project mixes them, sets its brand and keeps the result as a stylesheet
	 *
	 *										What a request does is little: the stylesheet the compiler wrote, private/assets/theme.css, is
	 *										a file of the style bundle - which puts it in the layer nino.theme, after the standard parts and
	 *										before the project's own css - and the variants of the active mix are told to the register by
	 *										its hook. Everything else happens when somebody saves: the reader (Reader) checks the themes,
	 *										the compiler (Compiler) writes the stylesheet and the variants, the store (Store) keeps the
	 *										state, the last state and the copy of every part in use under data/theme/, and Admin is the
	 *										API of the workbench. With the feature off, no file is in the bundle and no variant is told:
	 *										the site is drawn by the standard parts alone, as it is without a theme.
	 *
	 *										A preview is a draft of \Nino\Preview, a token bound to the session that asked for it: a request with
	 *										?nino-preview=<token> by the account that holds the token and the right, while it lasts, draws the
	 *										draft in place of the stylesheet and its variants in place of the active ones - in the
	 *										bundle of that request only, built in memory by \Nino\Modules\Assets::stylesheet(), so that
	 *										the public bundle stays as it is. With ?nino-theme-demo=<group> as well, the page it answers is
	 *										not the page of the route but the demo of that group - the piece of a page the group brings
	 *										(groups/<group>/demo.tpl) - in a page of its own that has the bundle of the draft and nothing
	 *										else: the demo of the header stands where a header stands, that of the footer where a footer
	 *										does, and any other in the main part.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Themes {

		// What the feature keeps under data/ - the state, the last state, the copies of the parts in use - and the cache of what is read of the
		// themes, which the page cache of the kernel does not take with it: it empties data/cache after every write of the workbench, which what is
		// read of the themes had better outlast (measured: 2.2 ms read cold, 0.2 ms kept). The drafts of the previews are the kernel's
		// (\Nino\Preview), of the kinds KIND and PICTURES
		public const string DIR = '/data/theme';
		public const string CACHE = '/data/theme/.cache';

		// The own themes: a folder in the form of a theme package for each, private/themes/<key>/, which the project owns and nobody installs
		public const string OWN = '/private/themes';

		// The folder that held the drafts of earlier previews, which Admin::apiList() takes away
		public const string DRAFTS = '/data/theme/.preview';

		// The right to work in the panel and through its API; the Editor role does not get it by itself
		public const string PERM = '/_admin/themes/manage';

		// The right to write the own css of the project (see ProjectCss): css that nobody has checked is on every page, which is more than to choose a theme,
		// so the right of the panel is not enough and no role gets this one by itself
		public const string CSS_PERM = '/_admin/themes/css';

		// The kind of the drafts of a preview - a session holds five of them - and of the thumbnails of the list, which are a kind of their own: five
		// previews are what a person works with, and a list of themes is as many pictures as there are themes
		public const string KIND = 'themes';
		public const string PICTURES = 'themes-pictures';
		public const int PREVIEWS_MAX = 5;
		public const int PICTURES_MAX = 120;

		// The query of a request that asks for a component to be outlined in the preview, and the one that asks for the demo of a group in place of the page
		public const string MARK = 'mark';
		public const string DEMO = 'nino-theme-demo';

		// The stylesheet this feature writes: the file the kernel puts in the layer nino.theme
		public const string SHEET = '/assets/theme.css';

		// The stylesheet of the project, the file the kernel gives every site for its own css - the layer project, the last of the four, after the theme - and that the
		// tab "Own CSS" writes: it is no file of this feature, the bundle has it with the feature off as well, and no theme, last state or package holds it. The
		// kind of its drafts is a kind of its own
		public const string PROJECT_SHEET = '/assets/style.css';
		public const string CSS_KIND = 'themes-css';

		// The style element a preview draws in the place of the link to the bundle; what stands before its token is how it begins
		private const string PREVIEW_STYLE = '<style data-nino-theme-preview>[[css]]</style>';

		// What the draft of this request is - 'css' and 'variants' as the compiler made them - and null for a request that has none
		private static ?array $_draft = null;
		private static ?string $_link = null;
		// The component a preview request asks to have outlined, '' for none, and the group whose demo it asks for in place of the page
		private static string $_mark = '';
		private static string $_demo = '';
		private static bool $_picture = false;
		// The draft of the own css of the project that this request is asked to show, null for none
		private static ?string $_project = null;

		public static function init( array &$appData ): void {

			// A request begins without a preview: the hook of the request says whether it asks for one
			self::$_draft = null;

			// A stylesheet that is not there is no file of the bundle: with none written the bundle is what it is without a theme
			if( \Nino\Modules\Themes\Store::readable( \Nino\Filesystem::path( $appData, self::SHEET ) ) === true )
				\Nino\Html::addAsset( $appData, '/.cache/style.css', self::SHEET );

			\Nino\Callbacks::registerCallback( $appData, \Nino\Components::VARIANTS, [ self::class, 'variants' ] );
			\Nino\Callbacks::registerCallback( $appData, \Nino\Blocks::HOOK, [ self::class, 'blocks' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'request' ] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Themes\Admin::class ];
		}

		/**
		 *	What the active mix says of the variants of a component, over what the schema says: the variants its part adds, the label each has
		 *	in the part, and which one stands where a page names none. A preview asks its draft instead
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$asked				[ 'name' => ..., 'variants' => [...], 'standard' => 'regular' ]
		 *
		 *	@return 	array											The same, with 'labels' => variant => text or locale => text where the part gives some
		 */
		public static function variants( array &$appData, array $asked ): array {

			$file = self::DIR. '/state/variants.php';

			// Read once for the request: the register asks a name once for each drawing. The file is php the store wrote - the one read that costs
			// nothing where php keeps the compiled script, which a cache of the filesystem by name and modification time would not tell from the one
			// before it after the states were swapped. The draft of a preview has it in the draft
			if( self::$_draft === null && isset( $appData['./nino/themes/variants'][$file] ) === false ) {

				$path = \Nino\Filesystem::path( $appData, $file );

				// No state where there are themes is a change that was broken off: the first request after it mends it
				if( is_file( $path ) === false && \Nino\Modules\Themes\Store::heal( $appData ) === true )
					clearstatcache();

				$appData['./nino/themes/variants'][$file] = is_file( $path ) === true ? (array) ( static fn( string $script ): mixed => include $script )( $path ) : [];
			}

			$mine = ( self::$_draft['variants'] ?? $appData['./nino/themes/variants'][$file] ?? [] )[$asked['name']] ?? null;

			if( is_array( $mine ) === false || is_array( $mine[0] ?? null ) === false || is_string( $mine[1] ?? null ) === false || is_array( $mine[2] ?? [] ) === false )
				return $asked;

			return [ 'name' => $asked['name'], 'variants' => array_merge( $asked['variants'], $mine[0] ), 'standard' => $mine[1], 'labels' => array_merge( (array) ( $asked['labels'] ?? [] ), $mine[2] ?? [] ) ];
		}

		/**
		 *	The folders of blocks of the themes that are installed - not only the one that is on: a block of a theme is there to be put in a page whichever
		 *	theme shows the site -, each with the key of its theme as the origin the kernel names the block by. The kernel checks every block it finds there
		 *	as it checks its own; one that fails is left out and the others are not touched
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$roots				The entries the callbacks before this one added: [ 'dir' => ..., 'origin' => ... ]
		 *
		 *	@return 	array											The same, and an entry for each theme that has a folder blocks/
		 */
		public static function blocks( array &$appData, array $roots ): array {

			// The kernel asks this hook where it reads the blocks anew, which it does after Blocks::forget() - what \Nino\Packages::place() and remove() call - or at
			// the first need of a request: what this request read of the themes is then dropped too, so that a theme put in place or taken away in it is seen
			unset( $appData['./nino/themes/read'] );

			foreach( \Nino\Modules\Themes\Reader::read( $appData )['themes'] as $key => $theme )
				if( is_dir( $theme['dir']. '/blocks' ) === true )
					$roots[] = [ 'dir' => $theme['dir']. '/blocks', 'origin' => (string) $key, 'label' => $theme['name'] ];

			return $roots;
		}

		/**
		 *	The first stage of a request: does it ask for the preview, and may it have it. The draft is the one this session was given and has
		 *	not run out, by the account that asked for it and holds the right still (\Nino\Preview::held()); anything else is a request for the page
		 *	as it is, and says nothing of why. A token of a thumbnail is a draft as well, and makes the page a picture
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function request( array &$appData, array &$request ): void {

			self::$_draft = null;
			self::$_mark = '';
			self::$_demo = '';
			self::$_picture = false;
			self::$_project = null;

			// The draft of the own css: a token of its own kind, held by an account that has the right to write it. The bundle of this request has the text in
			// place of the file; the theme is the one that is saved
			$project = \Nino\Preview::held( $appData, $request, self::CSS_KIND, self::CSS_PERM );

			if( $project !== null && is_string( $project['data']['css'] ?? null ) === true ) {

				self::$_project = $project['data']['css'];

				if( in_array( [ self::class, 'previewCss' ], \Nino\Callbacks::registered( $appData, '/nino/shortcodes/assets/output/css' ), true ) === false )
					\Nino\Callbacks::registerCallback( $appData, '/nino/shortcodes/assets/output/css', [ self::class, 'previewCss' ] );

				return;
			}

			$held = \Nino\Preview::held( $appData, $request, self::KIND, self::PERM );

			if( $held === null ) {

				$held = \Nino\Preview::held( $appData, $request, self::PICTURES, self::PERM );
				self::$_picture = $held !== null;
			}

			if( $held === null || is_string( $held['data']['css'] ?? null ) === false || is_array( $held['data']['variants'] ?? null ) === false )
				return;

			self::$_draft = $held['data'];

			// A page that is asked for with the token of a thumbnail is a picture: the scripts of the site do not run in a frame that the workbench draws
			// thirty times, and a page that is drawn small has no use for them
			if( self::$_picture === true && in_array( [ self::class, 'noScript' ], \Nino\Callbacks::registered( $appData, '/nino/shortcodes/assets/output/js' ), true ) === false )
				\Nino\Callbacks::registerCallback( $appData, '/nino/shortcodes/assets/output/js', [ self::class, 'noScript' ] );

			// A component to outline: only a request that holds a preview may ask for it, and only a slug is a name
			$mark = $request['/nino/http/request']['query'][self::MARK] ?? null;
			if( is_string( $mark ) === true && strlen( $mark ) <= \Nino\Components::NAME_LENGTH && preg_match( \Nino\Components::NAME, $mark ) === 1 )
				self::$_mark = $mark;

			// Only a request that asks for a preview has the link of its bundle looked at
			if( in_array( [ self::class, 'previewCss' ], \Nino\Callbacks::registered( $appData, '/nino/shortcodes/assets/output/css' ), true ) === false )
				\Nino\Callbacks::registerCallback( $appData, '/nino/shortcodes/assets/output/css', [ self::class, 'previewCss' ] );

			// The demo of a group: only the name of a group the register has, and only one that has a demo, is looked at - the text of the demo is the
			// register's and nothing of the request ever is
			$demo = $request['/nino/http/request']['query'][self::DEMO] ?? null;

			if( is_string( $demo ) === true && strlen( $demo ) <= \Nino\Components::NAME_LENGTH && preg_match( \Nino\Components::NAME, $demo ) === 1
				&& ( \Nino\Components::groups( $appData )[$demo]['demo'] ?? '' ) !== '' ) {

				self::$_demo = $demo;

				if( in_array( [ self::class, 'demo' ], \Nino\Callbacks::registered( $appData, '/nino/http/response' ), true ) === false )
					\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ self::class, 'demo' ] );
			}
		}

		/**
		 *	The link to the script bundle of a page that is a picture: none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The link, as the bundle's shortcode has it
		 *
		 *	@return 	string
		 */
		public static function noScript( array &$appData, string $template ): string {
			return self::$_picture === true ? '' : $template;
		}

		/**
		 *	The answer of a request that asks for the demo of a group: a page of its own in place of the one the route names. The header and the footer
		 *	are drawn where a page has them, the other groups in its main part, and what is not there is left empty. Drawn by the response like any
		 *	page, so that the bundle is the one of the draft (see previewCss()); not kept, and none for a search engine
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function demo( array &$appData, array &$request ): void {

			// The start address of the site and no other: a page of the preview is any page, the demo is the answer of one
			if( ( $request['/nino/http/request']['uri'] ?? '' ) !== '/' || ( $request['/nino/http/request']['method'] ?? '' ) !== 'GET' )
				return;

			$source = self::$_demo === '' ? '' : (string) ( \Nino\Components::groups( $appData )[self::$_demo]['demo'] ?? '' );
			$page = $source === '' ? '' : \Nino\Feature::template( $appData, '/features/Themes/templates', 'demo' );

			if( $page === '' )
				return;

			$at = [ 'header' => '[[before]]', 'footer' => '[[after]]' ][self::$_demo] ?? '[[main]]';

			// One pass: what the demo holds is no template of this page
			$request['/nino/http/response']['body'] = strtr( $page, [ '[[before]]' => $at === '[[before]]' ? $source : '', '[[main]]' => $at === '[[main]]' ? $source : '', '[[after]]' => $at === '[[after]]' ? $source : '', '[[hidden]]' => $at === '[[main]]' ? '' : ' hidden' ] );
			$request['/nino/http/response']['statusCode'] = 200;
			$request['/nino/http/response']['header'] = [ 'Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex' ] + (array) $request['/nino/http/response']['header'];
		}

		/**
		 *	The link to the stylesheet of the site, where the request asks for a preview: a style element with the bundle built in
		 *	memory, the draft in the place of the stylesheet of the theme, and after it the outline of the component the request
		 *	names. The public bundle is not touched, and the draft stands in the layer of the theme whether or not a stylesheet is
		 *	written yet
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template			The link, as the bundle's shortcode has it
		 *
		 *	@return 	string
		 */
		public static function previewCss( array &$appData, string $template ): string {

			// The bundle's shortcode hands this callback its own template to be changed in place, and what the callback answers is the
			// template for as long as the process lives: the link is kept, so that a request that asks for no preview has it back
			if( self::$_link === null && str_starts_with( $template, (string) strstr( self::PREVIEW_STYLE, '[[css]]', true ) ) === false )
				self::$_link = $template;

			if( self::$_draft === null && self::$_project === null )
				return self::$_link ?? $template;

			$target = '/.cache/style.css';
			$list = $appData['/nino/html/assets'][$target] ?? [];
			$swap = [];

			// A file as the list spells it, which is what the bundle matches a replacement by; one that is not on it yet is, for this call
			foreach( [ self::SHEET => self::$_draft['css'] ?? null, self::PROJECT_SHEET => self::$_project ] as $sheet => $text ) {

				if( $text === null )
					continue;

				\Nino\Html::addAsset( $appData, $target, $sheet );
				$own = \Nino\Filesystem::path( $appData, $sheet );
				$named = $sheet;

				foreach( \Nino\Html::getAssets( $appData, $target ) as $file )
					if( \Nino\Filesystem::path( $appData, $file ) === $own )
						$named = $file;

				$swap[$named] = $text;
			}

			$bundle = \Nino\Modules\Assets::stylesheet( $appData, $target, $swap );
			$appData['/nino/html/assets'][$target] = $list;

			// The outline is no layer: it stands over every rule of the bundle, which is what a mark is for
			$outline = self::$_mark === '' ? '' : "\n.". self::$_mark. " { outline: 2px solid #e8590c !important; outline-offset: -2px !important; }";

			// A style element ends at the first closing tag in it, so none may stand in the text
			return $bundle === '' ? ( self::$_link ?? $template ) : strtr( self::PREVIEW_STYLE, [ '[[css]]' => str_replace( '</', '<\\/', $bundle. $outline ) ] );
		}
	}
}
