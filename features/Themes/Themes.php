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
	 *										A preview is a token, bound to the session that asked for it: a request with
	 *										?nino-theme-preview=<token> by the account that holds the token, while it lasts, draws the
	 *										draft in place of the stylesheet and its variants in place of the active ones - in the
	 *										bundle of that request only, built in memory by \Nino\Modules\Assets::stylesheet(), so that
	 *										the public bundle stays as it is.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Themes {

		// What the feature keeps under data/ - the state, the last state, the copies of the parts in use - and the drafts of the previews,
		// which a backup does not carry because the name of their folder begins with a dot, and which the page cache of the kernel does
		// not take with it: it empties data/cache after every write of the workbench, which a draft has to outlast and what is read of the
		// themes had better (measured: 2.2 ms read cold, 0.2 ms kept)
		public const string DIR = '/data/theme';
		public const string DRAFTS = '/data/theme/.preview';
		public const string CACHE = '/data/theme/.cache';

		// The right to work in the panel and through its API; the Editor role does not get it by itself
		public const string PERM = '/_admin/themes/manage';

		// The query of a request that wants the preview, the one that asks for a component to be outlined in it, and how long a token holds, in seconds
		public const string QUERY = 'nino-theme-preview';
		public const string MARK = 'mark';
		public const int TTL = 900;
		public const string TOKEN = '/^[0-9a-f]{32}$/D';

		// The stylesheet this feature writes: the file the kernel puts in the layer nino.theme
		public const string SHEET = '/assets/theme.css';

		// The token a request previews with, '' for none, and the key of the session that holds the tokens of an account
		private static string $_preview = '';
		private static ?string $_link = null;
		// The component a preview request asks to have outlined, '' for none
		private static string $_mark = '';
		public const string SESSION = './themes/preview';

		public static function init( array &$appData ): void {

			// A request begins without a preview: the hook of the request says whether it asks for one
			self::$_preview = '';

			// A stylesheet that is not there is no file of the bundle: with none written the bundle is what it is without a theme
			if( is_file( \Nino\Filesystem::path( $appData, self::SHEET ) ) === true )
				\Nino\Html::addAsset( $appData, '/.cache/style.css', self::SHEET );

			\Nino\Callbacks::registerCallback( $appData, \Nino\Components::VARIANTS, [ self::class, 'variants' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'request' ] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Themes\Admin::class ];
		}

		/**
		 *	What the active mix says of the variants of a component, over what the schema says: the variants its part adds and which
		 *	one stands where a page names none. A preview asks its draft instead
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$asked				[ 'name' => ..., 'variants' => [...], 'standard' => 'regular' ]
		 *
		 *	@return 	array
		 */
		public static function variants( array &$appData, array $asked ): array {

			$file = self::$_preview === '' ? self::DIR. '/state/variants.php' : self::DRAFTS. '/'. self::$_preview. '/variants.php';

			// Read once for the request, for the preview as well: the register asks a name once for each drawing. The file is php the
			// store wrote - the one read that costs nothing where php keeps the compiled script, which a cache of the filesystem by
			// name and modification time would not tell from the one before it after the states were swapped
			if( isset( $appData['./nino/themes/variants'][$file] ) === false ) {

				$path = \Nino\Filesystem::path( $appData, $file );

				// No state where there are themes is a change that was broken off: the first request after it mends it
				if( is_file( $path ) === false && self::$_preview === '' && \Nino\Modules\Themes\Store::heal( $appData ) === true )
					clearstatcache();

				$appData['./nino/themes/variants'][$file] = is_file( $path ) === true ? (array) ( static fn( string $script ): mixed => include $script )( $path ) : [];
			}

			$mine = $appData['./nino/themes/variants'][$file][$asked['name']] ?? null;

			if( is_array( $mine ) === false || is_array( $mine[0] ?? null ) === false || is_string( $mine[1] ?? null ) === false )
				return $asked;

			return [ 'name' => $asked['name'], 'variants' => array_merge( $asked['variants'], $mine[0] ), 'standard' => $mine[1] ];
		}

		/**
		 *	The first stage of a request: does it ask for the preview, and may it have it. The token is the one this session was given
		 *	and has not expired, by the account that asked for it and holds the right still; anything else is a request for the
		 *	page as it is, and says nothing of why
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function request( array &$appData, array &$request ): void {

			self::$_preview = '';
			self::$_mark = '';
			$token = $request['/nino/http/request']['query'][self::QUERY] ?? null;

			if( is_string( $token ) === false || preg_match( self::TOKEN, $token ) !== 1 )
				return;

			$held = \Nino\Runtime::getSessionValue( $appData, self::SESSION, [] );
			$user = \Nino\Auth::getCurrentUser( $appData );

			if( is_array( $held ) === false || ( $held[$token] ?? 0 ) <= time() || $user === false || \Nino\Auth::checkPermission( $appData, self::PERM ) === false )
				return;

			$meta = \Nino\Filesystem::getFileContent( $appData, self::DRAFTS. '/'. $token. '/meta.json', [] );

			if( is_array( $meta ) === true && ( $meta['user'] ?? null ) === $user['mail'] && ( $meta['expires'] ?? 0 ) > time() ) {

				self::$_preview = $token;

				// A component to outline: only a request that holds a preview may ask for it, and only a slug is a name
				$mark = $request['/nino/http/request']['query'][self::MARK] ?? null;
				if( is_string( $mark ) === true && strlen( $mark ) <= \Nino\Components::NAME_LENGTH && preg_match( \Nino\Components::NAME, $mark ) === 1 )
					self::$_mark = $mark;

				// Only a request that asks for a preview has the link of its bundle looked at
				if( in_array( [ self::class, 'previewCss' ], \Nino\Callbacks::registered( $appData, '/nino/shortcodes/assets/output/css' ), true ) === false )
					\Nino\Callbacks::registerCallback( $appData, '/nino/shortcodes/assets/output/css', [ self::class, 'previewCss' ] );
			}
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
			if( self::$_link === null && str_starts_with( $template, '<style data-nino-theme-preview>' ) === false )
				self::$_link = $template;

			if( self::$_preview === '' )
				return self::$_link ?? $template;

			$draft = \Nino\Filesystem::getFileContent( $appData, self::DRAFTS. '/'. self::$_preview. '/theme.css', null );

			if( is_string( $draft ) === false )
				return self::$_link ?? $template;

			$target = '/.cache/style.css';
			$list = $appData['/nino/html/assets'][$target] ?? [];

			// The file as the list spells it, which is what the bundle matches a replacement by; one that is not on it yet is, for
			// this call
			\Nino\Html::addAsset( $appData, $target, self::SHEET );
			$own = \Nino\Filesystem::path( $appData, self::SHEET );
			$named = self::SHEET;

			foreach( \Nino\Html::getAssets( $appData, $target ) as $file )
				if( \Nino\Filesystem::path( $appData, $file ) === $own )
					$named = $file;

			$bundle = \Nino\Modules\Assets::stylesheet( $appData, $target, [ $named => $draft ] );
			$appData['/nino/html/assets'][$target] = $list;

			// The outline is no layer: it stands over every rule of the bundle, which is what a mark is for
			$outline = self::$_mark === '' ? '' : "\n.". self::$_mark. " { outline: 2px solid #e8590c !important; outline-offset: -2px !important; }";

			// A style element ends at the first closing tag in it, so none may stand in the text
			return $bundle === '' ? ( self::$_link ?? $template ) : '<style data-nino-theme-preview>'. str_replace( '</', '<\\/', $bundle. $outline ). '</style>';
		}
	}
}
