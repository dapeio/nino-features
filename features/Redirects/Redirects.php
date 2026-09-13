<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Redirects		Old addresses that still work, and a list of the ones
 *											that do not
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Redirects					Every site outlives some of its own addresses. A page
	 *										is renamed, a section is reorganised, a site is moved
	 *										onto Nino from something that spelled its urls
	 *										differently - and every link anybody ever made to the
	 *										old address, every bookmark and every search result,
	 *										lands on a 404 that says nothing.
	 *
	 *										A rule is an old path, a new address and a status.
	 *										301 says the address moved for good, which is what a
	 *										search engine acts on; 302 says it moved for now. A
	 *										rule can cover one page or a whole subtree, where what
	 *										stood after the old prefix stands after the new one -
	 *										a section that moved takes its pages with it.
	 *
	 *										Only where nothing else answers. A rule is never
	 *										consulted for a path that has a route, which is the one
	 *										decision that makes this safe to switch on: a mistyped
	 *										rule cannot take a working page off the site. It also
	 *										means a wildcard route answers everything below itself
	 *										(the Posts feature's /blog/* is one), so a moved post
	 *										is that feature's to redirect, not this one's.
	 *
	 *										And the half that makes the other half usable: the
	 *										paths nothing answered are remembered, so the rules
	 *										worth writing can be read off the panel instead of
	 *										guessed. The path only - not who asked for it.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Redirects {

		public const string KEY = 'redirects';

		/*	Late in the batch. Every other response callback has had its say by
			then - the protected-area gate, a feature answering its own route -
			and this one only acts where nothing did, so it has nothing to gain
			from running first and one thing to lose: a 401 turned into a 301	*/
		private const int PRIORITY = 8;

		/**
		 *	The /_admin screen this module brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears exactly
		 *	while this module is active and vanishes with it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Redirects\Admin::class ];
		}

		/**
		 *	Register the one callback. No file is read here: a request to a page
		 *	that exists must not pay for a feature that has nothing to say about
		 *	it, and that is every request but the ones this is for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ self::class, 'callbackRedirect' ], self::PRIORITY );
		}

		/**
		 *	Answer an address nothing else answered: with the rule that covers
		 *	it, or by remembering that nothing did.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackRedirect( array &$appData, array &$request ): void {

			$method = (string) ( $request['/nino/http/request']['method'] ?? '' );
			$path 	= (string) ( $request['/nino/http/request']['uri'] ?? '' );

			/*	GET and HEAD, and nothing else. A POST is a request with a body
				and an intention; 301 and 302 let a browser drop both and repeat
				it as a GET, and 307/308 would ask it to post the same body to
				an address the sender never chose. Neither is a redirect this
				should be making on its own	*/
			if( in_array( $method, [ 'GET', 'HEAD' ], true ) === false )
				return;

			// Somebody got there first - Locales' own locale redirect is the
			// one that really happens, and two Locations are one too many
			if( isset( $request['/nino/http/response']['header']['Location'] ) === true )
				return;

			/*	The whole safety of this feature, in one condition: a path that
				has a route is answered by that route, whatever a rule says about
				it. Asked of the router rather than read off the status code,
				because a project's own /404 route can answer with any status it
				likes and a soft 404 would otherwise look like a page	*/
			if( \Nino\Http::requestRoute( $appData, $path, $method ) !== null )
				return;

			$file 	= Redirects\Rules::read( $appData );
			$match	= Redirects\Rules::match( $file['rules'], $path );

			if( $match === null ) {
				self::_remember( $appData, $path );
				return;
			}

			$request['/nino/http/response']['statusCode']						= $match['status'];
			$request['/nino/http/response']['header']['Location']		= self::address( $appData, $match['to'] );

			// A 301 a browser cached is an address that cannot be corrected
			// without clearing it there, so the page itself is never cached on
			// top of that - see Modules\Cache, which leaves a Location alone
			$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';
			$request['/nino/http/response']['body']											= '';

			self::_count( $appData, $match['from'] );
		}

		/**
		 *	What goes in the Location header. A site path gets the project's own
		 *	directory in front of it, which is the one thing a rule cannot carry:
		 *	the same rules have to work whether the site is at / or at /shop
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$target				A path, or an absolute url
		 *
		 *	@return 	string
		 */
		public static function address( array &$appData, string $target ): string {

			return str_starts_with( $target, '/' ) === true
				? \Nino\Filesystem::getDir( $appData ). $target
				: $target;
		}

		/**
		 *	Whether this installation writes down what happened - the paths
		 *	nothing answered, and how often a rule was used.
		 *
		 *	One switch for both, because they are the same cost: a file write on
		 *	a request that would otherwise have touched nothing. On by default,
		 *	because a redirect feature whose panel cannot say what is missing is
		 *	a text file with a form around it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function records( array &$appData ): bool {

			return \Nino\Features::setting( $appData, self::KEY, 'record', true ) !== false;
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$path
		 *
		 *	@return 	void
		 */
		private static function _remember( array &$appData, string $path ): void {

			if( self::records( $appData ) === true )
				Redirects\Rules::noteMiss( $appData, $path );
		}

		/**
		 *	Count one use of a rule. Never fatal and never checked: a counter
		 *	that could not be written is not a reason to fail a redirect that
		 *	has already been decided
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					The rule's own path
		 *
		 *	@return 	void
		 */
		private static function _count( array &$appData, string $from ): void {

			if( self::records( $appData ) === false )
				return;

			unset( $appData['./redirects/rules'] );

			\Nino\Filesystem::mutate( $appData, Redirects\Rules::PATH, static function( array $file ) use ( $from ): array {

				foreach( (array) ( $file['rules'] ?? [] ) as $index => $rule ) {

					if( is_array( $rule ) === false || ( $rule['from'] ?? '' ) !== $from )
						continue;

					$file['rules'][$index]['hits'] = max( 0, (int) ( $rule['hits'] ?? 0 ) ) + 1;
					$file['rules'][$index]['last'] = date( 'Y-m-d H:i:s' );
				}

				return $file;
			} );
		}
	}
}
