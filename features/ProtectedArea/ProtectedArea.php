<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\ProtectedArea			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	ProtectedArea			Puts one or more uri prefixes behind one shared
	 *										password - a members' area, a client preview, an
	 *										internal page - without accounts and without any
	 *										per-visitor tracking. A visitor under a protected
	 *										prefix whose session has not unlocked sees the
	 *										password form instead of the page (the gate, see
	 *										callbackGate(), priority 1 on '/nino/http/response' -
	 *										before Modules\Cache's 9, so a locked response is
	 *										never the one that gets cached, and before the
	 *										render, so the form is what actually renders).
	 *										POST /.protected checks the posted password against
	 *										the one configured setting and, once right, marks the
	 *										session unlocked (\Nino\Runtime::setSessionValue()) -
	 *										no account, no per-user record, just this one flag.
	 *										GET /.protected/logout clears it again. Wrong attempts
	 *										are capped per client ip and hour in
	 *										/data/protected.php, the same fixed-window idea as
	 *										\Nino\Mail::_hit() (copied, not called - that counter
	 *										is mail's own send cap, this one is this feature's).
	 *
	 *										The directory is ProtectedArea, not Protected: the
	 *										class is derived from the directory name, and
	 *										"Protected" is a reserved php word that cannot name a
	 *										class. The key stays "protected".
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class ProtectedArea {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/ProtectedArea/templates';
		private const string FEATURE_KEY 	= 'protected';

		// A runtime session flag, never written to config.php - the one
		// thing that decides whether a locked visitor is actually this
		// feature's visitor, not an account of any kind
		private const string SESSION_KEY 	= './protected/unlocked';

		// Wrong-attempt counter, keyed by client ip - what the manifest's
		// 'data' entry documents
		private const string DATA_PATH 		= '/data/protected.php';

		private const int DEFAULT_ATTEMPTS	= 5;
		private const int WINDOW 					= 3600;

		/**
		 *	Register the gate, the two routes this feature owns and their
		 *	handlers, and extend the full-page cache's blacklist for as long
		 *	as a password is actually configured
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'protected-error', [ self::class, 'doErrorShortcode' ] );
			\Nino\Html::addShortcode( $appData, 'protected-logout', [ self::class, 'doLogoutShortcode' ] );

			// Priority 1: before Modules\Cache's own callback (9) ever gets to
			// decide whether this response may be stored or served from the
			// cache, and before the render turns the route's own body into
			// html - a locked visitor has to see the password form, not the
			// page the resolved route would otherwise answer
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ self::class, 'callbackGate' ], 1 );

			$appData['/nino/http/routes']['POST://.protected'] 				= [ 'uri' => '/.protected' ];
			$appData['/nino/http/routes']['GET://.protected/logout'] 	= [ 'uri' => '/.protected/logout' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.protected', [ self::class, 'callbackUnlock' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.protected/logout', [ self::class, 'callbackLogout' ] );

			self::_extendCacheBlacklist( $appData );
		}

		/**
		 *	Whether $uri lies under one of the configured protected prefixes -
		 *	the prefix itself and everything below it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri
		 *
		 *	@return 	bool
		 */
		public static function protects( array &$appData, string $uri ): bool {

			if( self::_password( $appData ) === '' )
				return false;

			foreach( self::_prefixes( $appData ) as $prefix )
				if( $uri === $prefix || str_starts_with( $uri, $prefix. '/' ) === true )
					return true;

			return false;
		}

		/**
		 *	Whether this session has already unlocked - the one thing the
		 *	gate checks besides protects(), see callbackGate()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function unlocked( array &$appData ): bool {

			return \Nino\Runtime::getSessionValue( $appData, self::SESSION_KEY ) === true;
		}

		/**
		 *	The gate: replace the response of a protected, still-locked
		 *	request with the password form. A plain 401 - never a 200, so
		 *	nothing caches it, and honest about the fact that nothing was
		 *	actually served. No exception for a signed-in workbench user: the
		 *	password is the only way in, on purpose - see the feature's
		 *	README for why.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackGate( array &$appData, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );

			if( self::protects( $appData, $uri ) === false || self::unlocked( $appData ) === true )
				return;

			\Nino\Html::addFills( $appData, [ '[[/protected/return]]' => htmlspecialchars( $uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) ], '*' );

			$request['/nino/http/response']['body']										= '[template /templates/page-protected]';
			$request['/nino/http/response']['statusCode']							= 401;
			$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';
		}

		/**
		 *	POST /.protected: check the posted password against the
		 *	configured one and either unlock the session and redirect to
		 *	'return', or re-render the form with an error. Attempts are capped
		 *	per client ip and hour (_claimAttempt()) - once the cap is hit the
		 *	form refuses even a correct password for the rest of the window, so
		 *	a leaked or guessed password cannot be brute forced past a guessed
		 *	prefix either. The password itself never appears in a log or a
		 *	response, success or failure.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackUnlock( array &$appData, array &$request ): void {

			// Respect a rejection from the earlier global Csrf callback - same
			// guard Form/Newsletter/Auth use, see Csrf::callbackResponse()'s
			// own docblock for why this is a dedicated flag rather than
			// checking statusCode
			if( ( $request['./nino/csrf/blocked'] ?? false ) === true )
				return;

			// is_string() rather than a (string) cast: nothing says a post
			// carries strings, and 'return[]=x' used to raise an "Array to
			// string conversion" - a level \Nino\Runtime treats as fatal, ie.
			// an unauthenticated 500 from a post anybody can send. Same reading
			// as \Nino\Form::posted(), whose docblock says why
			$ip 			= \Nino\Http::getClientIp( $appData );
			$return 	= is_string( $_POST['return'] ?? null ) === true ? $_POST['return'] : '';
			$password	= is_string( $_POST['password'] ?? null ) === true ? $_POST['password'] : '';
			$limit 		= (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'attempts', self::DEFAULT_ATTEMPTS );

			// Claimed before the password is compared, and inside the same lock
			// that records it - a check made first and a failure recorded
			// afterwards is a cap every request of a burst walks through at
			// once. Once the cap is hit for this window the form refuses every
			// further try, right password included, or the cap protects nothing
			if( self::_claimAttempt( $appData, $ip, $limit ) === false ) {
				self::_answerForm( $appData, $request, $return, 'locked', 429 );
				return;
			}

			$configured = self::_password( $appData );

			// hash_equals(), not '===': both operands are attacker-influenced,
			// and a plain comparison would leak one correct character at a
			// time through its timing. Never against an empty $configured -
			// an inert feature (see protects()) must not accept an equally
			// empty posted password as a match.
			if( $configured !== '' && hash_equals( $configured, $password ) === true ) {

				// The try this request claimed, and the wrong ones before it,
				// are spent: somebody who is through is not somebody the cap
				// should still be counting down on
				self::_clearAttempts( $appData, $ip );

				\Nino\Runtime::setSessionValue( $appData, self::SESSION_KEY, true );

				// The uri a request carries is the project's own - routes are
				// keyed without the directory a site may sit in - so the
				// directory goes in front of every address this feature sends a
				// visitor to, the way the Forms feature writes its action. Without
				// it a site at /shop unlocked into /intern, one level above itself
				$request['/nino/http/response']['statusCode']					= 303;
				$request['/nino/http/response']['header']['Location'] = (string) ( $appData['/nino/dir'] ?? '' ). self::_safeReturn( $return );
				$request['/nino/http/response']['body']								= '';
				return;
			}

			self::_answerForm( $appData, $request, $return, 'wrong', 401 );
		}

		/**
		 *	GET /.protected/logout: lock the session again and send the
		 *	visitor to the project's front page - the project directory in
		 *	front, see callbackUnlock(). A plain link rather than a form on purpose - it
		 *	discloses nothing a visitor could not already tell from being on
		 *	the page, so it needs no csrf token to be safe.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLogout( array &$appData, array &$request ): void {

			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_KEY );

			$request['/nino/http/response']['statusCode']					= 303;
			$request['/nino/http/response']['header']['Location'] = (string) ( $appData['/nino/dir'] ?? '' ). '/';
			$request['/nino/http/response']['body']								= '';
		}

		/**
		 *	[protected-error] - the wrong-password/locked line the form
		 *	shows, only while this very request actually failed one.
		 *	Re-rendered through renderHtml() the way every shortcode's output
		 *	is (see Html::_doShortcode()), so the fill inside still resolves
		 *	per locale.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments (unused)
		 *
		 *	@return 	string
		 */
		public static function doErrorShortcode( array &$appData, array $args ): string {

			$error = (string) ( $appData['./protected/error'] ?? '' );

			if( in_array( $error, [ 'wrong', 'locked' ], true ) === false )
				return '';

			return str_replace( '[[error]]', $error, self::template( $appData, 'protected-error' ) );
		}

		/**
		 *	[protected-logout] - a link to lock the area again, rendered only
		 *	while this session currently reads unlocked; a footer can carry
		 *	it unconditionally and it renders nothing for every other
		 *	visitor.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					Shortcode arguments (unused)
		 *
		 *	@return 	string
		 */
		public static function doLogoutShortcode( array &$appData, array $args ): string {

			if( self::unlocked( $appData ) === false )
				return '';

			return self::template( $appData, 'protected-logout' );
		}

		/**
		 *	Re-render the password form with an error and the posted return
		 *	path carried forward, instead of redirecting - the visitor has to
		 *	see the error, which a redirect would have nowhere to carry.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$return				As posted, echoed back into the hidden field
		 *	@param		string		$error				'wrong' or 'locked'
		 *	@param		int				$statusCode
		 *
		 *	@return 	void
		 */
		private static function _answerForm( array &$appData, array &$request, string $return, string $error, int $statusCode ): void {

			// The '[' goes the way \Nino\Modules\Elements swaps it, and for the
			// same reason: this lands in a page that is rendered afterwards, so
			// an escaped value was still read by the fill and the shortcode
			// pass - a locked visitor could put any of the project's templates,
			// and any of its texts, into the 401 they were served
			\Nino\Html::addFills( $appData, [ '[[/protected/return]]' => str_replace( '[', '&#91;', htmlspecialchars( $return, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) ) ], '*' );

			// A runtime-only, per-request flag - never persisted - read back
			// by doErrorShortcode() a few lines above
			$appData['./protected/error'] = $error;

			$request['/nino/http/response']['body']										= '[template /templates/page-protected]';
			$request['/nino/http/response']['statusCode']							= $statusCode;
			$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';
		}

		/**
		 *	'return' as posted, made safe to redirect to: a local path only -
		 *	starting with a single '/', no protocol-relative '//' and no
		 *	scheme - anything else becomes '/' instead of silently sending an
		 *	unlocked visitor to another host.
		 *
		 *	@param		string		$return
		 *
		 *	@return 	string
		 */
		private static function _safeReturn( string $return ): string {

			if( str_starts_with( $return, '/' ) === false || str_starts_with( $return, '//' ) === true )
				return '/';

			// Whitespace or a control character has no place in a Location
			// header - php refuses a header with a line break, this refuses
			// the whole value rather than sending a mangled one
			if( preg_match( '/[\s\x00-\x1f\x7f]/', $return ) === 1 )
				return '/';

			// A colon before the next slash reads as a scheme
			// ('/javascript:...', '/https:evil.example') rather than an
			// ordinary path segment
			if( preg_match( '#^/[^/]*:#', $return ) === 1 )
				return '/';

			return $return;
		}

		/**
		 *	The configured shared password - '' means the feature is inert,
		 *	nothing is protected while there is nothing to unlock with
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _password( array &$appData ): string {

			return (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'password', '' );
		}

		/**
		 *	The configured protected prefixes, normalized and validated: one
		 *	uri prefix per line, has to start with '/', must not start with
		 *	'/_' (the tools) or '/.' (module endpoints - this feature's own
		 *	/.protected included), trailing slash stripped, duplicates
		 *	dropped. An entry that fails any of this is silently skipped
		 *	rather than refused - the settings form already accepted it as a
		 *	'lines' value, and there is nothing left to refuse it with here.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _prefixes( array &$appData ): array {

			$prefixes = [];

			foreach( (array) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'paths', [] ) as $path ) {

				if( is_string( $path ) === false )
					continue;

				$path = rtrim( $path, '/' );

				if( $path === '' || str_starts_with( $path, '/' ) === false || str_starts_with( $path, '/_' ) === true || str_starts_with( $path, '/.' ) === true )
					continue;

				if( in_array( $path, $prefixes, true ) === false )
					$prefixes[] = $path;
			}

			return $prefixes;
		}

		/**
		 *	Keep the kernel's full-page cache (Modules\Cache) from both
		 *	storing and serving a protected page: an unlocked visitor's 200
		 *	would otherwise be cached and handed to the very next, still
		 *	locked visitor. Runtime only, appended to the live array and
		 *	never written to config.php - a later change to 'paths' takes
		 *	effect on the next request instead of leaving a stale entry
		 *	behind. Skipped entirely while the feature is inert (no
		 *	password): an ordinary, unprotected page needs no exclusion.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		private static function _extendCacheBlacklist( array &$appData ): void {

			if( self::_password( $appData ) === '' )
				return;

			$blacklist = (array) ( $appData['/nino/cache/blacklist'] ?? [] );

			foreach( self::_prefixes( $appData ) as $prefix )
				foreach( [ $prefix, $prefix. '/*' ] as $entry )
					if( in_array( $entry, $blacklist, true ) === false )
						$blacklist[] = $entry;

			$appData['/nino/cache/blacklist'] = $blacklist;
		}

		/**
		 *	Claim one attempt for $ip against the per-hour cap, and answer
		 *	whether this request may go on to compare a password at all.
		 *
		 *	Counting and comparing used to be two steps in two places: a
		 *	lockless read of the file decided whether to go on, the password
		 *	was compared, and only a wrong one was written back. Every
		 *	parallel request in between read the same count and passed the
		 *	same check, so a cap of five was a cap of five per burst - and a
		 *	burst is the case a cap exists for. The claim is the first thing
		 *	that happens now, inside the lock that records it, so a request
		 *	either holds a try or it does not.
		 *
		 *	Every attempt is claimed, the right password included, and a
		 *	successful unlock drops the ip's counter again (_clearAttempts()):
		 *	otherwise a visitor who unlocks five times in an hour would have
		 *	locked themselves out with five correct passwords.
		 *
		 *	A stale entry, any key's and not just this one, is dropped on the
		 *	write - the same idea as \Nino\Mail::_hit(), so the file cannot
		 *	grow without bound - and nothing is written where nothing changed,
		 *	so a caller already at the cap does not rewrite the file on every
		 *	further try. A write that does not happen refuses the attempt: one
		 *	this counter cannot record is one it cannot cap either.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$ip
		 *	@param		int				$limit				Wrong attempts allowed per window
		 *
		 *	@return 	bool										False where the cap is reached or the counter unwritable
		 */
		private static function _claimAttempt( array &$appData, string $ip, int $limit ): bool {

			$claimed = false;

			$written = \Nino\Filesystem::mutate( $appData, self::DATA_PATH, function( array $state ) use ( $ip, $limit, &$claimed ): ?array {

				$before = $state;

				foreach( $state as $key => $entry )
					if( (int) ( $entry['reset'] ?? 0 ) <= time() )
						unset( $state[$key] );

				$entry = $state[$ip] ?? [ 'tries' => 0, 'reset' => time() + self::WINDOW ];

				if( (int) $entry['tries'] < $limit ) {

					$entry['tries']	= (int) $entry['tries'] + 1;
					$state[$ip] 		= $entry;
					$claimed 				= true;
				}

				return $state === $before ? null : $state;
			} );

			return $claimed === true && $written === true;
		}

		/**
		 *	Drop $ip's counter after a successful unlock, so the tries it took
		 *	to get there are not still held against the visitor - see
		 *	_claimAttempt(), which counts the successful one too
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$ip
		 *
		 *	@return 	void
		 */
		private static function _clearAttempts( array &$appData, string $ip ): void {

			\Nino\Filesystem::mutate( $appData, self::DATA_PATH, function( array $state ) use ( $ip ): ?array {

				$before = $state;

				unset( $state[$ip] );

				foreach( $state as $key => $entry )
					if( (int) ( $entry['reset'] ?? 0 ) <= time() )
						unset( $state[$key] );

				return $state === $before ? null : $state;
			} );
		}

		/**
		 *	One of this feature's own templates, read the way a project's are.
		 *	Markup belongs in a template - see AGENTS.md, "Markup belongs in a
		 *	template" - so what this class holds is which one and what goes in it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					A file name below TEMPLATES, without .tpl
		 *
		 *	@return 	string								'' where the file is not there, which is logged
		 */
		public static function template( array &$appData, string $name ): string {

			// A name from this class and nowhere else, and held to a slug anyway:
			// the one thing this could otherwise be turned into is a read of
			// something outside the feature
			if( preg_match( '/^[a-z][a-z0-9-]*$/', $name ) !== 1 )
				return '';

			$template = \Nino\Filesystem::getFileContent( $appData, self::TEMPLATES. '/'. $name. '.tpl', '' );

			$template = is_string( $template ) === true ? rtrim( $template, "\n" ) : '';

			/*	A template that is not there renders as nothing, which on a page looks
				like a shortcode nobody wrote rather than like a feature missing a file.
				Said out loud instead: E_USER_WARNING is Nino's "record this and carry
				on" channel (see \Nino\Runtime::NON_FATAL_LEVELS), so the request
				finishes and the log says which file	*/
			if( $template === '' )
				trigger_error( 'Nino: the template '. self::TEMPLATES. '/'. $name. '.tpl is missing or empty.', E_USER_WARNING );

			return $template;
		}
	}
}
