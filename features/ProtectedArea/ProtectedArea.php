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
	 *										prefix - or at any address of a page whose uri lies
	 *										under one - whose session has not unlocked is shown
	 *										the password form instead of the page (the gate, see
	 *										callbackGate(), priority 1 on '/nino/http/response' -
	 *										before Modules\Cache's 9, so a locked response is
	 *										never the one that gets cached, and before the
	 *										render, so the form is what actually renders). The
	 *										answer is a response of the gate's own, not the
	 *										route's with a new body: nothing the route brought -
	 *										a file, a header, a callback bound to its uri - is
	 *										left in it.
	 *
	 *										The form is the component protected-form and the page
	 *										it stands on is made of components
	 *										(install/templates/page-protected.tpl). POST /.protected
	 *										checks the posted password against the hash held in
	 *										the setting 'password' (password_verify() - the
	 *										password itself is never held: the panel and the
	 *										gate hash what they are given) and, once right, marks
	 *										the session unlocked (\Nino\Runtime::setSessionValue())
	 *										- no account, no per-user record, just this one
	 *										flag. GET /.protected/logout clears it again. Wrong
	 *										attempts are capped per client ip and hour in
	 *										/data/protected.php, by \Nino\Feature::count().
	 *										Every unlock is stamped with the session epoch of
	 *										/data/protected-session.php (see _epoch()), and
	 *										signOutAll() - the panel's button, and every password
	 *										change - writes a new one, so every session that
	 *										unlocked before it reads locked again. The feature
	 *										also has a small panel (Admin/Admin.php) for the
	 *										password and the protected paths, and tells
	 *										\Nino\Modules\Seo, where that is installed, which
	 *										prefixes to keep out of the sitemap, and the address
	 *										of every route whose page lies under one
	 *										(callbackSeoExclude()).
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

		private const string FEATURE_KEY 	= 'protected';

		// A runtime session flag, never written to config.php - the one
		// thing that decides whether a locked visitor is actually this
		// feature's visitor, not an account of any kind. Its value is the
		// session epoch the unlock happened under (see _epoch())
		private const string SESSION_KEY 	= './protected/unlocked';

		// The session epoch - what 'sign everybody out' rewrites. Listed under
		// 'data' in the manifest, next to the attempt counter
		private const string EPOCH_PATH 		= '/data/protected-session.php';

		// Wrong-attempt counter: the kernel's, \Nino\Feature::count() under this
		// name, keyed by the client's address - the file /data/protected.php that
		// the manifest's 'data' entry documents
		private const string COUNTER 		= 'protected';

		private const int DEFAULT_ATTEMPTS	= 5;
		private const int WINDOW 					= 3600;

		// The address the answer of the gate stands under. A module endpoint, so no page of the project has it: the
		// callbacks bound to the uri of the page that was asked for do not run for it, and neither does the title of
		// that page reach a visitor who has not unlocked
		private const string LOGIN_URI 		= '/.protected';

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

			// Priority 1: before Modules\Cache's own callback (9) ever gets to
			// decide whether this response may be stored or served from the
			// cache, and before the render turns the route's own body into
			// html - a locked visitor has to see the password form, not the
			// page the resolved route would otherwise answer
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ self::class, 'callbackGate' ], 1 );

			$appData['/nino/http/routes']['POST://.protected'] 				= [ 'uri' => self::LOGIN_URI ];
			$appData['/nino/http/routes']['GET://.protected/logout'] 	= [ 'uri' => '/.protected/logout' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.protected', [ self::class, 'callbackUnlock' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.protected/logout', [ self::class, 'callbackLogout' ] );

			// The literal string, not Seo::EXCLUDE: this feature does not require
			// Seo, and a constant of a class that is not there is a fatal error
			// where a callback nobody fires costs one array entry
			\Nino\Callbacks::registerCallback( $appData, '/seo/exclude', [ self::class, 'callbackSeoExclude' ] );

			// The password can also be changed in the Features panel, where it is
			// a setting like any other: that save has to lock the sessions of the
			// old one out as well, and to keep the hash and not what was typed.
			// The kernel announces every workbench action after the fact
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/action', [ self::class, 'callbackAdminAction' ] );

			// A backup carries the epoch it was made under: the one it brings back is replaced, or everybody who
			// was signed out since would be signed in again
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/restore', [ self::class, 'callbackRestore' ] );

			self::_extendCacheBlacklist( $appData );
		}

		// The panel comes and goes with the feature: the kernel asks every
		// active module, in /nino/modules order (see \Nino\Modules::collect())
		public static function adminPanels( array &$appData ): array {

			return [ \Nino\Modules\ProtectedArea\Admin::class ];
		}

		/**
		 *	Whether $uri lies under one of the configured protected prefixes -
		 *	the prefix itself and everything below it. Both sides are folded
		 *	first (see _fold()): the server answers one page at several
		 *	spellings of its address, and an answer that is not the page is
		 *	harmless where one that is would be open
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri
		 *
		 *	@return 	bool
		 */
		public static function protects( array &$appData, string $uri ): bool {

			if( self::_password( $appData ) === '' )
				return false;

			$uri = self::_fold( $uri );

			foreach( self::_patterns( $appData, true ) as $pattern )
				if( \Nino\Http::matchesPath( $uri, [ $pattern. '/*' ] ) === true )
					return true;

			return false;
		}

		/**
		 *	What is protected, as the subtrees the gate, the sitemap and the cache all name: the configured prefixes,
		 *	and the uri of a GET route whose path a prefix names exactly, and the path of every route whose uri
		 *	lies under what is named. A page has a route for each language, every one with the page's uri and a path of its own,
		 *	so a prefix written as the path of one of them - /members for the page /intern - shuts the page in every language. A
		 *	route that only lies below a prefix (an alias with a uri of its own) shuts nothing but itself, a route that answers an
		 *	error is left out, and a uri that is the front page, a tool or a module endpoint is never taken in: a route must not
		 *	widen the protection to the whole site.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		bool			$folded				True for the form the gate compares (see _fold()), false for the spelling of the routes, which the sitemap and the cache are matched in
		 *
		 *	@return 	array										Addresses, each standing for itself and everything below it
		 */
		private static function _patterns( array &$appData, bool $folded ): array {

			$prefixes = self::prefixes( $appData );
			$routes = (array) ( $appData['/nino/http/routes'] ?? [] );

			// Worked out once for a state of the setting and of the routes: the gate, the sitemap and the cache ask for it
			// several times a request, and a route that is added, or a path that is written, is another state
			$sign = md5( serialize( [ $prefixes, $routes ] ) );
			$key = $folded === true ? 'folded' : 'written';

			if( ( $appData['./protected/patterns'][$key][0] ?? '' ) === $sign )
				return $appData['./protected/patterns'][$key][1];

			$subtrees = static fn( array $names ): array => array_map( static fn( string $name ): string => self::_fold( $name ). '/*', $names );
			// What a route may add: not the front page, not a tool, not an endpoint of a module
			$fit = static fn( string $each ): bool => ( $kept = self::_fold( $each ) ) !== '/' && str_starts_with( $kept, '/_' ) === false && str_starts_with( $kept, '/.' ) === false;
			$rows = [];

			// An error page is nobody's page, and a route that answers an error shuts nothing
			foreach( \Nino\Http::pages( $routes ) as $row )
				if( $row['status'] < 400 )
					$rows[] = [ 'path' => $row['path'], 'uri' => preg_replace( '#/\*$#', '', $row['uri'] ) ?? '' ];

			$patterns = $prefixes;
			$named = array_map( static fn( string $prefix ): string => self::_fold( $prefix ), $prefixes );

			// A prefix that names the path of a route exactly - /members for the page /intern - names the page: its uri.
			// A route that only lies below a prefix is a page below it, and its uri is its own business (an alias)
			foreach( $rows as $row )
				if( in_array( self::_fold( $row['path'] ), $named, true ) === true && $fit( $row['uri'] ) === true )
					$patterns[] = $row['uri'];

			// And the paths of a page in every language: the routes whose uri is under what is named
			$reach = $subtrees( $patterns );

			foreach( $rows as $row )
				if( \Nino\Http::matchesPath( self::_fold( $row['uri'] ), $reach ) === true && $fit( $row['path'] ) === true )
					$patterns[] = $row['path'];

			$result = [
				'folded'	=> array_values( array_unique( array_map( static fn( string $each ): string => self::_fold( $each ), $patterns ) ) ),
				'written'	=> array_values( array_unique( $patterns ) ),
			];

			$appData['./protected/patterns'] = [ 'folded' => [ $sign, $result['folded'] ], 'written' => [ $sign, $result['written'] ] ];

			return $result[$key];
		}

		/**
		 *	Whether this session has already unlocked - the one thing the
		 *	gate checks besides protects(), see callbackGate(). It has when the
		 *	epoch it unlocked under is the current one: a sign-out writes a
		 *	new one, and a session from before it reads locked again. Where no
		 *	epoch was ever written nobody has unlocked, whatever the session
		 *	holds.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		public static function unlocked( array &$appData ): bool {

			$session = \Nino\Runtime::getSessionValue( $appData, self::SESSION_KEY );

			// Nothing to compare for a visitor who never unlocked, so the file
			// is only read for one who did
			if( is_string( $session ) === false || $session === '' )
				return false;

			$epoch = self::_epoch( $appData );

			return $epoch !== null && hash_equals( $epoch, $session );
		}

		/**
		 *	The password as the setting holds it: a hash a visitor's try is
		 *	checked against with password_verify(). The password is run through
		 *	sha256 first - the hash of PASSWORD_DEFAULT reads 72 bytes and no
		 *	more, and a password of 200 characters has to count to its end
		 *
		 *	@param		string		$password
		 *
		 *	@return 	string
		 */
		public static function hashPassword( string $password ): string {

			return password_hash( self::_digest( $password ), PASSWORD_DEFAULT );
		}

		/**
		 *	Lock every session at once: write a new session epoch, which no
		 *	session that unlocked before it carries. The panel's 'Sign
		 *	everybody out' and every change of the password end here - a
		 *	member who left must not stay in because the browser kept the
		 *	session. Nobody is identified, so there is no one to name; the
		 *	epoch is all there is to rotate.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool										False where the file could not be written
		 */
		public static function signOutAll( array &$appData ): bool {

			$password = self::_password( $appData );

			return \Nino\Filesystem::mutate( $appData, self::EPOCH_PATH, static fn( mixed $state ): array => self::_stamp( $password ), [] );
		}

		/**
		 *	'/nino/admin/action': a password set in the Features panel
		 *	(features/settings for this feature, with a password among the
		 *	posted fields) is a setting like any other, which the kernel stores as
		 *	it was typed: it is hashed here and locks every session, the way this
		 *	feature's own screen does. A save that was refused changed nothing
		 *	and rotates nothing, and neither does one that sent no password.
		 *
		 *	Whether the password changed is not read off the event: a secret
		 *	sent empty keeps the stored one, and the kernel blanks a secret in
		 *	what it announces, so the value says nothing. A stored value that is no
		 *	hash is a password that was typed there - nothing else writes one - and a
		 *	new one; for a hash the epoch file says which password it was written under
		 *	(signOutAll()), and the password in force now is compared with that.
		 *	An epoch written before it said so has nothing to compare with, and a
		 *	save that sent a password is then taken as a change - once, since the
		 *	rotation writes what the next one compares with.
		 *
		 *	The announcement is the kernel's, after the answer was given, so a
		 *	failure here is a warning and not an answer: the screen of this
		 *	feature, which is the one that says so, signs out before and after
		 *	its own write
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$event				(reference) { action, panel, status, user, data }
		 *
		 *	@return 	void
		 */
		public static function callbackAdminAction( array &$appData, array &$event ): void {

			if( ( $event['action'] ?? '' ) !== 'features/settings' || (int) ( $event['status'] ?? 0 ) !== 200 )
				return;

			$data = is_array( $event['data'] ?? null ) ? $event['data'] : [];

			if( ( $data['key'] ?? '' ) !== self::FEATURE_KEY || is_array( $data['fields'] ?? null ) === false || array_key_exists( 'password', $data['fields'] ) === false )
				return;

			$stored = self::_password( $appData );

			if( $stored !== '' && password_get_info( $stored )['algo'] === null ) {

				$errors = \Nino\Features::saveSettings( $appData, self::FEATURE_KEY, [ 'password' => self::hashPassword( $stored ) ] );

				if( $errors !== [] ) {
					trigger_error( 'ProtectedArea: the password was changed in the Features panel, but it could not be stored as a hash' );
					return;
				}
			}
			else {

				$state = \Nino\Filesystem::getFileContent( $appData, self::EPOCH_PATH, [] );
				$epoch = is_array( $state ) === true ? ( $state['epoch'] ?? null ) : null;
				$known = is_array( $state ) === true ? ( $state['pw'] ?? null ) : null;

				if( is_string( $epoch ) === true && is_string( $known ) === true && hash_equals( $known, hash_hmac( 'sha256', $stored, $epoch ) ) === true )
					return;
			}

			if( self::signOutAll( $appData ) === false )
				trigger_error( 'ProtectedArea: the password was changed in the Features panel, but the sessions could not be locked out' );
		}

		/**
		 *	'/nino/admin/restore': the extracted backup gets a new session epoch before it is copied over the live
		 *	data, so that a restore signs everybody out and does not bring back the sessions of a time the epoch
		 *	has been replaced since. The password the backup brings is not known here, so the record names none:
		 *	the next save in the Features panel is taken for a change and signs out once more
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				(reference) { dataDir: live /data (read only), staging: extracted backup }
		 *
		 *	@return 	void
		 */
		public static function callbackRestore( array &$appData, array &$args ): void {

			$staging = (string) ( $args['staging'] ?? '' );

			if( $staging === '' || ( is_dir( $staging. '/data' ) === false && @mkdir( $staging. '/data', 0775, true ) === false ) )
				return;

			file_put_contents( $staging. '/data/protected-session.php', '<?php return '. var_export( self::_stamp( '' ), true ). ';' );
		}

		/**
		 *	The addresses a crawler has no business listing, answered under
		 *	'/seo/exclude' for the Seo feature, where that is installed: every
		 *	protected prefix and everything below it, and the address of every
		 *	route whose page lies under one - Seo matches the addresses of the
		 *	routes, and a page has one for each language (see _patterns()). Every
		 *	address is named with its subtree. All of it while a password
		 *	is set, because without one nothing is protected and nothing is
		 *	excluded.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$list				(reference) Patterns, in the spelling of Seo's 'exclude' setting
		 *
		 *	@return 	void
		 */
		public static function callbackSeoExclude( array &$appData, array &$list ): void {

			if( self::_password( $appData ) === '' )
				return;

			// The same addresses the gate shuts, in the spelling of the routes: a page has one for each language
			foreach( self::_patterns( $appData, false ) as $pattern )
				$list[] = $pattern. '/*';
		}

		/**
		 *	The gate: replace the response of a protected, still-locked
		 *	request with the password form. A request is protected when the path
		 *	asked for lies under a protected prefix, or when the page that answers
		 *	it does (see _behindTheGate()). A plain 401 - never a 200, so
		 *	nothing caches it, and honest about the fact that nothing was
		 *	actually served. No exception for a signed-in workbench user: the
		 *	password is the only way in, on purpose.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackGate( array &$appData, array &$request ): void {

			// The cache is asked what it may store before it is asked what to serve, and by then the routes of every module are in
			self::_extendCacheBlacklist( $appData );

			// A page is the same page for everybody the cache gives it to: what an unlocked visitor is shown - the link to lock
			// again, the protected pages - is neither stored nor served from a copy for this request, and the copies of the
			// others stay as they are
			if( self::_password( $appData ) !== '' && self::unlocked( $appData ) === true )
				$appData['/nino/cache/blacklist'] = array_merge( (array) ( $appData['/nino/cache/blacklist'] ?? [] ), [ '/*' ] );

			// The workbench is never behind the gate, whatever the configuration says: a hand-written line must not shut
			// the way back in, nor the recovery page
			$asked = self::_fold( (string) ( $request['/nino/http/request']['uri'] ?? '' ) );

			if( $asked === '/_admin' || str_starts_with( $asked, '/_admin/' ) === true )
				return;

			if( self::_behindTheGate( $appData, $request ) === false || self::unlocked( $appData ) === true )
				return;

			// The path that was asked for, not the uri of the page that answers it: that
			// is where the form returns to, at the visitor's own address
			self::_answerForm( $appData, $request, (string) ( $request['/nino/http/request']['uri'] ?? '' ), '', 401 );
		}

		/**
		 *	Whether a request is for a page behind the gate: the path asked for lies
		 *	under a protected prefix, or the uri of the route that answers it does. A
		 *	page can have a route for each language, every one with the page's uri and
		 *	an address of its own, while a prefix is written once - /intern, say - and
		 *	its address in another language would be open where /intern is shut. A
		 *	wildcard route's uri, /intern/*, lies under the prefix as well. The route
		 *	is laid into the response before the callbacks of the response run, so at
		 *	the gate's priority the response's uri is the route's
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$request			Current server request, its response already routed
		 *
		 *	@return 	bool
		 */
		private static function _behindTheGate( array &$appData, array $request ): bool {

			return self::protects( $appData, (string) ( $request['/nino/http/request']['uri'] ?? '' ) ) === true
				|| self::protects( $appData, (string) ( $request['/nino/http/response']['uri'] ?? '' ) ) === true;
		}

		/**
		 *	POST /.protected: check the posted password against the
		 *	configured one and either unlock the session and redirect to
		 *	'return', or answer the form again with an error. Attempts are capped
		 *	per client ip and hour (\Nino\Feature::count()) - once the cap is hit
		 *	the form refuses even a correct password for the rest of the window,
		 *	so a leaked or guessed password cannot be brute forced past a guessed
		 *	prefix either. The password itself never appears in a log or a
		 *	response, success or failure, and the answer of a wrong password is
		 *	the same for every wrong one.
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
			if( \Nino\Form::blocked( $request ) === true )
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

			// Counted before the password is compared, and inside the same lock
			// that records it - a check made first and a failure recorded
			// afterwards is a cap every request of a burst walks through at
			// once. Every attempt counts, the right password included, and a
			// successful unlock lets the ip go again (below): otherwise a visitor
			// who unlocks five times in an hour would have locked themselves out
			// with five correct passwords. Once the cap is hit for this window the
			// form refuses every further try, right password included, or the cap
			// protects nothing. A count that cannot be recorded is a refusal: one
			// this counter cannot record is one it cannot cap either. The counter
			// is kept by the address and not by the session: a visitor who throws
			// their cookie away starts nothing new
			if( \Nino\Feature::count( $appData, self::COUNTER, $ip, $limit, self::WINDOW ) === false ) {
				self::_answerForm( $appData, $request, $return, 'locked', 429 );
				return;
			}

			$configured = self::_password( $appData );

			// password_verify() compares in constant time, and never against an empty
			// $configured - an inert feature (see protects()) must not accept an
			// equally empty posted password as a match. A stored value that is no
			// hash verifies nothing: the gate stays shut
			if( $configured !== '' && password_verify( self::_digest( $password ), $configured ) === true ) {

				// The try this request claimed, and the wrong ones before it,
				// are spent: somebody who is through is not somebody the cap
				// should still be counting down on
				\Nino\Feature::forget( $appData, self::COUNTER, $ip );

				$epoch = self::_issueEpoch( $appData );

				if( $epoch === null ) {
					self::_answerForm( $appData, $request, $return, 'wrong', 401 );
					return;
				}

				// The identity of the session changes: a new id and a new token, as
				// a sign-in does (see \Nino\Auth), so that an id somebody else knew
				// before is worth nothing now. There is no session to rotate on the
				// command line, where tests run
				if( \Nino\Runtime::startSession( $appData ) === true )
					session_regenerate_id( true );

				\Nino\Csrf::rotateToken( $appData );
				\Nino\Runtime::setSessionValue( $appData, self::SESSION_KEY, $epoch );

				// The uri a request carries is the project's own - routes are
				// keyed without the directory a site may sit in - and the redirect
				// puts the directory in front of it. Without it a site at /shop
				// unlocked into /intern, one level above itself
				\Nino\Http::redirect( $appData, $request, self::_safeReturn( $return ), 303 );
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

			\Nino\Http::redirect( $appData, $request, '/', 303 );
		}

		/**
		 *	The renderer of the component protected-form: the password field with its
		 *	error line, a form that posts to /.protected and runs without a script.
		 *	It carries the token of the session (the kernel refuses a post without
		 *	it) and the path to go back to; what it says of a failed try is the error
		 *	of this very request, one of two, and nothing of the password
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string									Finished markup
		 */
		public static function componentProtectedForm( array &$appData, array $args, string $content, array $context ): string {

			$error = (string) ( $appData['./protected/error'] ?? '' );
			$flag = static fn( bool $on, string $attribute ): string => $on === true ? ' '. $attribute : '';

			// A second form on a page has ids of its own
			$count = (int) ( $appData['./protected/forms'] ?? 0 ) + 1;
			$appData['./protected/forms'] = $count;
			$suffix = $count === 1 ? '' : '-'. $count;

			return \Nino\Components::fill( $appData, 'protected-form', $args, $content, [
				'[[suffix]]'				=> $suffix,
				'[[action]]'				=> \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). self::LOGIN_URI ),
				'[[csrf]]'					=> \Nino\Components::escape( \Nino\Csrf::getToken( $appData ) ),
				'[[return]]'				=> \Nino\Components::escape( (string) ( $appData['./protected/return'] ?? '' ) ),
				'[[wrong-hidden]]'	=> $flag( $error !== 'wrong', 'hidden' ),
				'[[locked-hidden]]'	=> $flag( $error !== 'locked', 'hidden' ),
				'[[invalid]]'				=> $flag( $error !== '', 'aria-invalid="true" aria-describedby="protected-error-'. $error. $suffix. '" autofocus' ),
			] );
		}

		/**
		 *	The renderer of the component protected-logout: the link that locks the area
		 *	again, drawn only while this session currently reads unlocked, so a footer can
		 *	carry it unconditionally and it draws nothing for every other visitor
		 *
		 *	@param		array 		&$appData
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string									Finished markup
		 */
		public static function componentProtectedLogout( array &$appData, array $args, string $content, array $context ): string {

			if( self::unlocked( $appData ) === false )
				return '';

			return \Nino\Components::fill( $appData, 'protected-logout', $args, $content, [
				'[[href]]' => \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). '/.protected/logout' ),
			] );
		}

		/**
		 *	Answer the visitor with the password form and nothing of the route: a
		 *	response of the gate's own, under its own uri - the callbacks the route
		 *	has, a file it names, the headers it sets and the title of its page stay
		 *	out of it. The path asked for is carried forward as the place to return
		 *	to, as a path of this site or '/', and the error says what failed.
		 *	Nothing is redirected: the visitor has to see the error, which a
		 *	redirect would have nowhere to carry. Never cached.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$return				As asked for or posted
		 *	@param		string		$error				'' for the plain form, 'wrong' or 'locked'
		 *	@param		int				$statusCode
		 *
		 *	@return 	void
		 */
		private static function _answerForm( array &$appData, array &$request, string $return, string $error, int $statusCode ): void {

			// Runtime-only, per-request - never persisted - and read back by
			// componentProtectedForm(), which escapes them where it writes them: a visitor's own
			// address is the one thing that lands in the form
			$appData['./protected/return']	= self::_safeReturn( $return );
			$appData['./protected/error']		= $error;
			$appData['./protected/forms']		= 0;

			$header = self::_keptHeaders( (array) ( $request['/nino/http/response']['header'] ?? [] ) );
			$header['Cache-Control'] = 'no-store';

			$request['/nino/http/response'] = [
				'uri'					=> self::LOGIN_URI,
				'locale'			=> $request['/nino/http/response']['locale'] ?? \Nino\Locales::getCurrentLocale( $appData ),
				'header'			=> $header,
				'body'				=> '[template /templates/page-protected]',
				'statusCode'	=> $statusCode,
			];
		}

		/**
		 *	The header fields of a response a locked visitor is answered with: the policy, which the callbacks of the
		 *	response have widened for this request, and nothing else a route put there - a type, a disposition, a
		 *	link, a refresh, a cross-origin grant. The security headers are the kernel's: it mixes its defaults into
		 *	every response when it sends it
		 *
		 *	@param		array 		$header				As the response carries them
		 *
		 *	@return 	array
		 */
		private static function _keptHeaders( array $header ): array {

			$kept = [];

			foreach( $header as $name => $value )
				if( strtolower( (string) $name ) === 'content-security-policy' )
					$kept[$name] = $value;

			return $kept;
		}

		/**
		 *	'return' as posted, made safe to redirect to: a path of this site only -
		 *	starting with a single '/', no protocol-relative '//' (nor '/\', which
		 *	a browser reads the same), no scheme, no control character - anything
		 *	else becomes '/' instead of silently sending an unlocked visitor to
		 *	another host. The kernel's \Nino\Http::redirect() holds a path to the same
		 *	rules and leaves the response alone, with a warning, for one that breaks
		 *	them: a visitor is sent to the front page, not to nowhere.
		 *
		 *	@param		string		$return
		 *
		 *	@return 	string
		 */
		private static function _safeReturn( string $return ): string {

			return \Nino\Http::isSitePath( $return ) === true ? $return : '/';
		}

		/**
		 *	An address as the gate compares it: percent escapes decoded - as often
		 *	as there is one, since '%252F' is '%2F' is '/' -, a backslash a slash,
		 *	letters lower case, runs of slashes one, '.' and '..' segments resolved
		 *	and the dots and blanks a segment ends in dropped, so that '/Intern',
		 *	'//intern//', '/intern/.', '/intern.' and '/x/../intern' are the one
		 *	address a prefix names. Stricter than the server, on purpose: an address
		 *	it would answer with a 404 is asked for the password as well
		 *
		 *	@param		string		$uri
		 *
		 *	@return 	string										Starting with '/', '/' for nothing
		 */
		private static function _fold( string $uri ): string {

			for( $round = 0; $round < 8 && str_contains( $uri, '%' ) === true; $round++ ) {

				$decoded = rawurldecode( $uri );

				if( $decoded === $uri )
					break;

				$uri = $decoded;
			}

			$uri = mb_strtolower( str_replace( '\\', '/', $uri ), 'UTF-8' );
			$kept = [];

			foreach( explode( '/', $uri ) as $segment ) {

				if( $segment === '..' ) {
					array_pop( $kept );
					continue;
				}

				// Only after the dot segments: '..' without its dots would be nothing
				$segment = rtrim( $segment, ". \t\n\r\0\x0B" );

				if( $segment !== '' )
					$kept[] = $segment;
			}

			return '/'. implode( '/', $kept );
		}

		/**
		 *	The password the way the hash is made of it, see hashPassword()
		 *
		 *	@param		string		$password
		 *
		 *	@return 	string
		 */
		private static function _digest( string $password ): string {

			return base64_encode( hash( 'sha256', $password, true ) );
		}

		/**
		 *	The record of an epoch: the random token the unlocks are stamped with, and
		 *	a keyed hash of the password it was written under - not the password: it
		 *	only says whether that is still the one in force (see callbackAdminAction())
		 *
		 *	@param		string		$password			As the setting holds it
		 *
		 *	@return 	array
		 */
		private static function _stamp( string $password ): array {

			$epoch = bin2hex( random_bytes( 16 ) );

			return [ 'epoch' => $epoch, 'pw' => hash_hmac( 'sha256', $password, $epoch ) ];
		}

		/**
		 *	The session epoch the current unlocks are stamped with, null where none
		 *	was written yet. A file that holds anything else counts as none.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string|null
		 */
		private static function _epoch( array &$appData ): ?string {

			$state = \Nino\Filesystem::getFileContent( $appData, self::EPOCH_PATH, [] );
			$epoch = is_array( $state ) === true ? ( $state['epoch'] ?? null ) : null;

			return is_string( $epoch ) === true && $epoch !== '' ? $epoch : null;
		}

		/**
		 *	The epoch an unlock is stamped with: the one on file, or - for the first
		 *	unlock there ever was - a new one, written under the lock so that two
		 *	first unlocks agree on it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string|null								Null where it could not be written
		 */
		private static function _issueEpoch( array &$appData ): ?string {

			if( self::_epoch( $appData ) === null ) {

				$password = self::_password( $appData );

				\Nino\Filesystem::mutate( $appData, self::EPOCH_PATH, static function( mixed $state ) use ( $password ): ?array {

					$epoch = is_array( $state ) === true ? ( $state['epoch'] ?? null ) : null;

					return is_string( $epoch ) === true && $epoch !== '' ? null : self::_stamp( $password );
				}, [] );
			}

			return self::_epoch( $appData );
		}

		/**
		 *	The configured shared password as the setting holds it - a hash, see
		 *	hashPassword(); '' means the feature is inert, nothing is protected while
		 *	there is nothing to unlock with
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _password( array &$appData ): string {

			return (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'password', '' );
		}

		/**
		 *	One line of the setting 'paths' as a prefix: trailing slashes dropped, and null
		 *	where it cannot be one - it has to start with '/', holds no control character,
		 *	no escape, no backslash and no '.' or '..' segment, and must not fold (see _fold())
		 *	to the front page, to '/_...' (the tools) or to '/.' (module endpoints - this feature's own
		 *	/.protected included)
		 *
		 *	@param		string		$line
		 *
		 *	@return 	string|null
		 */
		public static function cleanPrefix( string $line ): ?string {

			$path = rtrim( trim( $line ), '/' );

			if( $path === '' || str_starts_with( $path, '/' ) === false || preg_match( '/[\x00-\x1f\x7f%\\\\]/', $path ) === 1 )
				return null;

			foreach( explode( '/', $path ) as $segment )
				if( $segment === '.' || $segment === '..' )
					return null;

			// Judged as the gate compares it: '/Intern' and '//_admin' are what they fold to
			$folded = self::_fold( $path );

			if( $folded === '/' || str_starts_with( $folded, '/_' ) === true || str_starts_with( $folded, '/.' ) === true )
				return null;

			return $path;
		}

		/**
		 *	The configured protected prefixes, normalized and validated by
		 *	cleanPrefix(), duplicates dropped. An entry that fails is silently
		 *	skipped rather than refused - the settings form already accepted it as a
		 *	'lines' value, and there is nothing left to refuse it with here. Public for
		 *	the panel, which shows what is protected through the same reading the gate
		 *	has.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function prefixes( array &$appData ): array {

			$prefixes = [];

			foreach( (array) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'paths', [] ) as $line ) {

				$path = is_string( $line ) === true ? self::cleanPrefix( $line ) : null;

				if( $path !== null && in_array( $path, $prefixes, true ) === false )
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

			// The subtree entry covers the address itself: the cache matches its blacklist with
			// \Nino\Http::matchesPath(). The same addresses as the sitemap is told: the one in the other language too
			foreach( self::_patterns( $appData, false ) as $pattern )
				if( in_array( $pattern. '/*', $blacklist, true ) === false )
					$blacklist[] = $pattern. '/*';

			$appData['/nino/cache/blacklist'] = $blacklist;
		}
	}
}
