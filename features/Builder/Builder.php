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

		// What the drafts of the previews are called in \Nino\Preview
		public const string KIND = 'builder';

		public static function init( array &$appData ): void {
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/request', [ self::class, 'request' ] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Builder\Admin::class ];
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

			$held = \Nino\Preview::held( $appData, $request, self::KIND, self::PERM );

			if( $held === null )
				return;

			$draft = $held['data'];

			if( is_string( $draft['template'] ?? null ) === false || is_string( $draft['source'] ?? null ) === false || is_array( $draft['fills'] ?? null ) === false )
				return;

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
