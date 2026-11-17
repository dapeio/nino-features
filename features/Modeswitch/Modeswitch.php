<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Modeswitch	see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modeswitch				Three states, one control: read the site light, read it
	 *										dark, or read it the way the system asks - which is
	 *										where it starts, and the only one of the three that
	 *										is not a decision.
	 *
	 *										Dark is not invented here. Every colour of a Nino page is
	 *										a light-dark() pair and the mode of the page is the token
	 *										--brand-scheme of the root element: "light dark" for the
	 *										visitor's own, "light" or "dark" where the brand decided.
	 *										This feature is the control that sets the token for the
	 *										visitor who may choose, and that is the whole of it. The
	 *										control is the component mode-switch, which needs no
	 *										renderer: its schema and its template are all of it, and
	 *										the script shows it only where the brand leaves the mode
	 *										open.
	 *
	 *										Nothing reaches the server. The choice lives in the
	 *										reader's own browser (localStorage), so there is no
	 *										cookie to declare, no consent to ask for, and a cached
	 *										page is as switchable as a fresh one - the token is set
	 *										after the html arrives, not into it.
	 *
	 *										What the class does is the one thing the script of the
	 *										bundle cannot: be early. That script is the last in the
	 *										body, and a visitor whose choice differs from their
	 *										system would see the page in the system's mode first.
	 *										So the output phase puts the few lines of
	 *										assets/mode-early.js into the head, as an inline script
	 *										under the nonce of the page.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Modeswitch {

		// The early script as the head carries it: one line, the nonce and the code in double brackets
		public static array $html = [
			'early'	=> '<script nonce="[[nonce]]">[[code]]</script>',
		];

		// Where the code of the early script is, as \Nino\Filesystem resolves it
		public const string EARLY = '/features/Modeswitch/assets/mode-early.js';

		/**
		 *	Hook the output phase, where the head of the finished page can be reached
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// Below priority 9, where the page cache stores the page and its nonce
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/output', [ self::class, 'callbackOutput' ] );
		}

		/**
		 *	Put the early script before the end of the head of a page of the site: of an html response with a
		 *	head, not of the workbench, not of a draft that is previewed - a picture of a page shows the theme and
		 *	not the choice of whoever draws it - and only where the page has the nonce its policy names, since
		 *	an inline script without one is refused by the browser and the bundle script does the same later
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) Current request and its response
		 *
		 *	@return 	void
		 */
		public static function callbackOutput( array &$appData, array &$request ): void {

			foreach( [ $request['/nino/http/request']['uri'] ?? '', $request['/nino/http/response']['uri'] ?? '' ] as $uri )
				if( is_string( $uri ) === true && ( $uri === '/_admin' || str_starts_with( $uri, '/_admin/' ) === true ) )
					return;

			$body		= $request['/nino/http/response']['body'] ?? null;
			$type		= $request['/nino/http/response']['header']['Content-Type'] ?? 'text/html';
			$nonce	= $appData['./nino/jstext/nonce'] ?? '';

			if( is_string( $body ) === false || is_string( $type ) === false || stripos( $type, 'text/html' ) !== 0 || is_string( $nonce ) === false || $nonce === '' || \Nino\Preview::active( $appData ) === true )
				return;

			$end = stripos( $body, '</head>' );

			if( $end === false )
				return;

			$code = \Nino\Filesystem::getFileContent( $appData, self::EARLY, '' );

			if( is_string( $code ) === false || trim( $code ) === '' ) {
				trigger_error( 'Modeswitch: the early script '. self::EARLY. ' is missing or empty', E_USER_WARNING );
				return;
			}

			$request['/nino/http/response']['body'] = substr( $body, 0, $end ). strtr( self::$html['early'], [ '[[nonce]]' => $nonce, '[[code]]' => trim( $code ) ] ). "\n". substr( $body, $end );
		}
	}

}
