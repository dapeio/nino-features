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

		// What a person may do once inside, in three steps: the content (the blocks of a page and the words in them), the design (the components of a block, their
		// settings and the frames) and the development (the rest, html among it). The scope is the panel's: an account that holds no permission below it - the door
		// alone - is not described in detail and may design; one that is described holds what it is given, and the permission of a step is any that lies below it
		public const string SCOPE = '/_admin/builder/';
		public const string CONTENT = '/_admin/builder/content/edit';
		public const string DESIGN = '/_admin/builder/design/edit';
		public const string DEVELOP = '/_admin/builder/develop/edit';

		// The steps as numbers
		public const int LEVEL_CONTENT = 1;
		public const int LEVEL_DESIGN = 2;
		public const int LEVEL_DEVELOP = 3;

		// What the drafts of the previews are called in \Nino\Preview
		public const string KIND = 'builder';

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
		 *	The step of work the signed-in account may do, from the highest it holds down: 3 for the one who may do everything (the blanket of the panel, the whole
		 *	site), 2 for the one who may design - the door without a description in detail, or the permission of the design -, 1 for the content, 0 for none of them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		public static function level( array &$appData ): int {

			if( \Nino\Auth::checkPermission( $appData, self::DEVELOP ) === true )
				return self::LEVEL_DEVELOP;

			if( \Nino\Admin\Admin::scoped( $appData, self::SCOPE, self::DESIGN ) === true )
				return self::LEVEL_DESIGN;

			return \Nino\Admin\Admin::scoped( $appData, self::SCOPE, self::CONTENT ) === true ? self::LEVEL_CONTENT : 0;
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

			if( $held === null )
				return;

			$draft = $held['data'];

			if( is_string( $draft['template'] ?? null ) === false || is_string( $draft['source'] ?? null ) === false || is_array( $draft['fills'] ?? null ) === false )
				return;

			if( $tile === true )
				\Nino\Callbacks::registerCallback( $appData, '/nino/shortcodes/assets/output/js', [ self::class, 'noScript' ] );
			else
				\Nino\Preview::trace( $appData );

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
		}
	}
}
