<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Members					see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Members						Member accounts for the website, without passwords. An
	 *											account is an email address, a name and a group, kept in
	 *											/data/members.php. A member signs in with a code: POST
	 *											/.members/login keeps the address and a random nonce in the
	 *											session of the browser that asked, makes six digits for the
	 *											address and puts their mail in the outbox of the kernel - to the
	 *											address where it belongs to an active account, to nobody where it
	 *											does not (\Nino\Mail::VOID) - and answers every address alike,
	 *											and the form then asks for the code, which
	 *											POST /.members/code takes. The code is kept only as
	 *											sha256( nonce | code ) in /data/members-tokens.php, so it is
	 *											worth nothing outside the session that asked for it, and it is
	 *											good for one sign-in, for as long as the setting linkMinutes
	 *											allows and for five tries. An invitation, and the link the
	 *											owner sends from the panel, are links: GET
	 *											/.members/login?t=<token> opens a page that names the
	 *											account and asks for a button to be pressed, which POSTs to
	 *											/.members/confirm and spends the token: a GET changes
	 *											nothing, so a mail scanner or a prefetch that follows the link
	 *											uses nobody's link up. That token is 32 random bytes, mailed
	 *											once and kept only as its sha256 in the same file, good for one
	 *											confirmation and for as long as linkMinutes (inviteDays for an
	 *											invitation) allows. The sign-in is a value of the kernel's
	 *											session, stamped with the session epoch of
	 *											/data/members-session.php (see _openEpoch()): signOutAll() -
	 *											the panel's button - writes a new one, and every session
	 *											from before it reads signed out. The one cookie of the feature's
	 *											own is nino_remember, for a member who ticked the box of the code
	 *											form: its value is the id of the account and a token of 32 random bytes,
	 *											of which the account keeps only the sha256 (the list remember, ten at most,
	 *											the oldest one goes first). A visitor who has no session and brings a good one
	 *											is signed in as after a code, and the token is replaced by a new one
	 *											with every use (see _resume()); signing out takes the token of that
	 *											device, forgetAll() those of all.
	 *
	 *											A path under one of the protected prefixes of the
	 *											setting paths - or an address of a page whose uri lies under
	 *											one, in whichever language its routes give it, and in whichever
	 *											spelling it is asked for (see _fold()) - is answered with the login
	 *											page, a response of the gate's own with the status 401, to everyone
	 *											who is not signed in (the gate, see callbackGate(), priority 1 on
	 *											'/nino/http/response' - before Modules\Cache's 9, so a page for a member
	 *											is never the one that gets cached). The page asked for is kept in the
	 *											session - with its query, as it came, without the state members= that a
	 *											redirect of the feature adds -, and the code requested from that page
	 *											takes the member back to it; a code asked for from another page leads
	 *											to the start page.
	 *
	 *											Four components put the form, a button to sign out, the member's name
	 *											and the button of the page a link opens on any page (components/, drawn
	 *											by componentMembersLogin(), componentMembersLogout(), componentMembersName()
	 *											and componentMembersConfirm()), and the panel in Admin/Admin.php manages
	 *											the accounts. The form draws the field for the address and - once a code
	 *											is out, in the session of the visitor - the field for the code.
	 *
	 *											An account's address is changed by the owner (change() with
	 *											'email') or by the member: askChange() keeps the request in the
	 *											session of the member and mails six digits to the new address -
	 *											whoever the address belongs to is not told -, confirmChange() takes
	 *											them, once, in time and within five tries. Every link and code the
	 *											account has out dies with the change, the account gets an epoch of
	 *											its own that ends every other session of it (the one that confirms
	 *											keeps its place), and the old address is told.
	 *
	 *											Other features build on it through current(), account(),
	 *											accounts() and groups(), and by the callbacks '/members/login',
	 *											'/members/logout', '/members/changed' and '/members/removed', which
	 *											are fired with the account as their argument (the third with who changed
	 *											the address, too, the last with the id, the address and the group of an
	 *											account that is gone), and '/members/start', which a sign-in fires before
	 *											it leads on: a feature may say where to. A mail to an account is written in
	 *											the language a callback of '/members/language' names for it (mailLocale()),
	 *											which the mails of the other features ask as well.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Members {

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Members/templates';

		// The accounts: [ 'accounts' => [ <id> => [ email, name, group, active, created, login ] ] ]; an account whose address was changed has an
		// 'epoch' besides, 32 hex characters, which the sessions of the account carry (see current())
		public const string ACCOUNTS = '/data/members.php';

		// The links and the codes that are out: [ <sha256 of the token, or of nonce|code> => [ account, kind, expires, target ] ] - and the new address, for the code of a change
		public const string TOKENS = '/data/members-tokens.php';

		// The session epoch - what 'sign everybody out' rewrites: [ 'epoch' => <32 hex> ]
		public const string EPOCH = '/data/members-session.php';

		private const string FEATURE_KEY = 'members';

		// The address the answer of the gate, of a link that does not lead in and of the page a link opens stands under. A module endpoint, so no page of the project
		// has it: the callbacks bound to the uri of the page that was asked for do not run for it, and neither does the title of that page reach a visitor who
		// is not signed in
		private const string PAGE_URI = '/.members';

		// A runtime session value - who is signed in, and under which epoch -, the
		// path a protected page was asked for until a code is requested, and the code
		// that is out: [ email, nonce, tries, until ]. The kernel's session carries
		// all three: the feature has no cookie of its own
		private const string SESSION_ACCOUNT = './members/account';
		private const string SESSION_TARGET = './members/target';

		// The longest page a sign-in may lead back to, its query included
		private const int TARGET_MAX = 2000;
		private const string SESSION_PENDING = './members/pending';

		// The change of an address the member asked for: [ account, email, nonce, tries, until ]
		private const string SESSION_CHANGE = './members/change';

		// Request-lifecycle keys: the state a callback of this request set for the
		// form to say, how many forms this request has drawn, and the link a page
		// asks to be confirmed (its token, the name of the account, a state)
		private const string STATE = './members/state';
		private const string FORMS = './members/forms';

		private const string CONFIRM = './members/confirm';

		// The counter of the login requests, \Nino\Feature::count() under this
		// name: the folder /data/.counters/members-tries. Not 'members' all the same: a
		// counter and the file of the accounts of one name do not disturb each other, but the kernel names the file in a
		// warning and the operator who finds both has to guess. It is not in the manifest's 'data': a window of an hour is
		// nothing a backup carries
		private const string COUNTER = 'members-tries';

		private const int WINDOW = 3600;
		private const int ADDRESS_LIMIT = 3;
		private const int DEFAULT_ATTEMPTS = 5;

		// The share of the places of the outbox that the requests for a code may hold at once: half of them, thirty of MailQueue::MAX_CLIENT. What a visitor's mail of any other feature
		// needs stays free (see _braked())
		private const int SITE_DIVISOR = 2;

		// The most live records of codes the file of the codes holds, the ones of accounts and the ones of addresses with no account together: a write that would leave more drops the oldest
		// records of addresses with no account - a record of an account is never dropped (see _keep())
		private const int CODE_MAX = 200;

		private const int NAME_LENGTH = 120;
		private const int CODE_TRIES = 5;
		private const string TIME = 'Y-m-d\TH:i:s\Z';

		// The cookie of a device that stays signed in, the tokens an account keeps of them, the longest user agent that is kept with one, the days it holds
		// by default, the wrong cookies of an address that are counted in an hour and the seconds the token that was replaced still signs in
		private const string REMEMBER = 'nino_remember';
		private const int REMEMBER_MAX = 10;
		private const int AGENT_LENGTH = 300;
		private const int DEFAULT_REMEMBER = 90;
		private const int REMEMBER_TRIES = 20;
		private const int REMEMBER_GRACE = 30;
		private const string REMEMBER_COUNTS = 'remember|';

		// What the form may say besides nothing, the key of its words where that is
		// not the state's own name, what a token may be for, and what of it a link
		// may be: the entry of a code is made by a request for one and never by link()
		private const array STATES = [ 'sent', 'limit', 'invalid', 'out', 'expired', 'wrong', 'gone' ];
		private const array STATE_KEYS = [ 'sent' => 'code' ];

		// The states that say something went wrong: the field of the form is invalid under them
		private const array FAILURES = [ 'limit', 'invalid', 'expired', 'wrong', 'gone' ];
		private const array KINDS = [ 'login', 'invite', 'send', 'code', 'change' ];
		private const array LINKS = [ 'login', 'invite', 'send' ];

		// How long the record of a link that has run out is kept: what the link led to is all it is kept for, so that the page that says the link no longer works can lead the
		// code of the sign-in to the same page
		private const int LINK_KEEP = 2592000;

		/**
		 *	Register the five routes this feature owns with their handlers,
		 *	the gate and the exclusion for the Seo feature, and keep the full-page
		 *	cache away from the protected paths
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// [mail-head] at the head of the mails is the place of a band: the project's own module answers it, and where none does it is empty
			\Nino\Feature::shortcode( $appData, 'mail-head', [ self::class, 'doShortcodeMailHead' ], false );

			$appData['/nino/http/routes']['POST://.members/login']	= [ 'uri' => '/.members/login' ];
			$appData['/nino/http/routes']['POST://.members/code']		= [ 'uri' => '/.members/code' ];
			$appData['/nino/http/routes']['GET://.members/login']		= [ 'uri' => '/.members/login' ];
			$appData['/nino/http/routes']['GET://.members/logout']	= [ 'uri' => '/.members/logout' ];
			$appData['/nino/http/routes']['POST://.members/logout']	= [ 'uri' => '/.members/logout' ];
			$appData['/nino/http/routes']['POST://.members/confirm']	= [ 'uri' => '/.members/confirm' ];

			// Priority 1: before Modules\Cache's own callback (9) decides whether
			// this response may be stored or served from the cache, and before the
			// render, so the login page is what actually renders for a visitor
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response', [ self::class, 'callbackGate' ], 1 );

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.members/login', [ self::class, 'callbackLoginRequest' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.members/code', [ self::class, 'callbackCode' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.members/login', [ self::class, 'callbackLoginLink' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.members/confirm', [ self::class, 'callbackConfirm' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.members/logout', [ self::class, 'callbackLogout' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.members/logout', [ self::class, 'callbackLogoutGet' ] );

			// The literal string, not Seo::EXCLUDE: this feature does not require
			// Seo, and a constant of a class that is not there is a fatal error
			// where a callback nobody fires costs one array entry
			\Nino\Callbacks::registerCallback( $appData, '/seo/exclude', [ self::class, 'callbackSeoExclude' ] );

			// No callback of '/nino/admin/restore', and that is on purpose: a restore
			// replaces the three files whole (the manifest's 'data'), and nothing is
			// to be merged - the accounts and the links that are out come back as
			// they were, and the epoch of the backup ends every session that was
			// signed in since, which is right

			self::_keepOutOfCache( $appData, array_map( static fn( string $prefix ): string => $prefix. '/*', self::prefixes( $appData ) ) );
		}

		/**
		 *	The shortcode [mail-head], the first line of every mail that has the frame: the place of the band at its head. Another module's answer for it stands (the shortcode runs
		 *	the callbacks registered for it in turn, each given the answer of the one before); where no module answers it the place is empty
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		mixed			$args					The arguments of the shortcode, or the answer of a callback that ran before this one
		 *
		 *	@return 	string
		 */
		public static function doShortcodeMailHead( array &$appData, mixed $args ): string {

			return is_string( $args ) === true ? $args : '';
		}

		// The panel comes and goes with the feature: the kernel asks every
		// active module, in /nino/modules order (see \Nino\Modules::collect())
		public static function adminPanels( array &$appData ): array {

			return [ \Nino\Modules\Members\Admin::class ];
		}

		/**
		 *	The member who is signed in, as the account has it plus its id: an
		 *	array of id, email, name, group, active, created and login. Only while
		 *	the session's epoch is the current one and the account exists and is
		 *	active - a session from before 'sign everybody out', one of an account
		 *	that was deleted and one of an account that was switched off read as
		 *	nobody - and one that does not carry the epoch the account has now: a change of the
		 *	address gives the account an epoch of its own and ends every session of it but the one
		 *	that confirmed the change. An account that has none is the account of a session that carries none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								Null for a visitor
		 */
		public static function current( array &$appData ): ?array {

			$session = \Nino\Runtime::getSessionValue( $appData, self::SESSION_ACCOUNT );

			if( is_array( $session ) === false || is_string( $session['id'] ?? null ) === false || is_string( $session['epoch'] ?? null ) === false )
				return null;

			$epoch = self::_epoch( $appData );

			if( $epoch === '' || hash_equals( $epoch, $session['epoch'] ) === false )
				return null;

			$record = self::_record( $appData, $session['id'] );

			if( $record === null || $record['active'] === false || hash_equals( $record['epoch'] ?? '', is_string( $session['own'] ?? null ) === true ? $session['own'] : '' ) === false )
				return null;

			return [ 'id' => $session['id'] ] + self::_plain( $record );
		}

		/**
		 *	One account by its id
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						12 hex characters
		 *
		 *	@return 	array|null								id, email, name, group, active, created and login - null for an id nobody has
		 */
		public static function account( array &$appData, string $id ): ?array {

			$record = self::_record( $appData, $id );

			return $record === null ? null : [ 'id' => $id ] + self::_plain( $record );
		}

		/**
		 *	The language a mail to an account is written in, where something other than the site knows one: a callback of '/members/language' is told the id of the
		 *	account and may name a locale of the site (a module that keeps the language of its customers does). A locale that is none of the site's, and no answer,
		 *	is no language: the mail is written in the language of the request, or in the site's native one where the owner sends it, as it is without the callback
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						12 hex characters
		 *
		 *	@return 	string										A locale of the site, eg. 'en_US'; '' for none
		 */
		public static function mailLocale( array &$appData, string $id ): string {

			$ask = [ 'account' => $id, 'locale' => '' ];

			\Nino\Callbacks::doCallbacks( $appData, '/members/language', $ask );

			return is_string( $ask['locale'] ?? null ) === true && \Nino\Locales::verifyLocale( $appData, $ask['locale'] ) === true ? $ask['locale'] : '';
		}

		/**
		 *	Every account, sorted by name (the email address where an account has
		 *	none), each as account() has it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										A list of accounts
		 */
		public static function accounts( array &$appData ): array {

			$accounts = [];

			foreach( self::_accountsOf( \Nino\Filesystem::getFileContent( $appData, self::ACCOUNTS, [] ) ) as $id => $record )
				$accounts[] = [ 'id' => (string) $id ] + self::_plain( $record );

			usort( $accounts, static fn( array $a, array $b ): int => [ mb_strtolower( self::_label( $a ) ), $a['email'], $a['id'] ] <=> [ mb_strtolower( self::_label( $b ) ), $b['email'], $b['id'] ] );

			return $accounts;
		}

		/**
		 *	The groups that are in use, each once
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Slugs, sorted
		 */
		public static function groups( array &$appData ): array {

			$groups = array_values( array_unique( array_column( self::accounts( $appData ), 'group' ) ) );
			$groups = array_values( array_filter( $groups, static fn( string $group ): bool => $group !== '' ) );

			sort( $groups );

			return $groups;
		}

		/**
		 *	Whether $uri lies under one of the protected prefixes of the setting
		 *	paths - the prefix itself and everything below it - and no feature has opened it (open()).
		 *	Both sides are folded first (see _fold()): the server answers one page at several spellings
		 *	of its address, and an answer that is not the page is harmless where one that is would be open
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri					A request path without the directory
		 *
		 *	@return 	bool
		 */
		public static function protects( array &$appData, string $uri ): bool {

			$uri = self::_fold( $uri );

			return \Nino\Http::matchesPath( $uri, array_map( static fn( string $prefix ): string => self::_fold( $prefix ). '/*', self::prefixes( $appData ) ) ) === true
				&& \Nino\Http::matchesPath( $uri, array_map( static fn( string $path ): string => self::_fold( $path ), self::open( $appData ) ) ) === false;
		}

		/**
		 *	The paths a feature has opened under a protected prefix: a file the browser fetches without a session - the manifest of a web app, its icon. The callback
		 *	'/members/open' is handed a list and adds to it the uris of its routes, in the spelling Http::matchesPath() reads (a path, or a prefix with /*); anything that is
		 *	no string, no path of this site or an endpoint of a module (/., /_) is dropped
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Uris and patterns
		 */
		public static function open( array &$appData ): array {

			$list = [];

			\Nino\Callbacks::doCallbacks( $appData, '/members/open', $list );

			// What is opened is judged as it is asked for (see _fold()): '/club/../_admin' is the tools and no path a feature may open
		return array_values( array_filter( is_array( $list ) === true ? $list : [], static fn( mixed $uri ): bool => is_string( $uri ) === true && preg_match( '~^/(?![._/])[^\s\x00-\x1f\x7f?#]*$~D', $uri ) === 1
			&& str_starts_with( self::_fold( $uri ), '/_' ) === false && str_starts_with( self::_fold( $uri ), '/.' ) === false && self::_fold( $uri ) !== '/' ) );
		}

		/**
		 *	The configured protected prefixes, normalized and validated by cleanPrefix(), each
		 *	once. An entry that fails is skipped rather than refused - the settings form took it
		 *	as a 'lines' value, and there is nothing left to refuse it with here
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Prefixes without a trailing slash
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
		 *	One line of the setting 'paths' as a prefix: trailing slashes dropped, and null where it
		 *	cannot be one - it has to start with '/', holds no control character, no escape, no
		 *	backslash and no '.' or '..' segment, and must not fold (see _fold()) to the front page, to
		 *	'/_...' (the tools) or to '/.' (module endpoints - this feature's own /.members included)
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

			// Judged as the gate compares it: '/Club' and '//_admin' are what they fold to
			$folded = self::_fold( $path );

			if( $folded === '/' || str_starts_with( $folded, '/_' ) === true || str_starts_with( $folded, '/.' ) === true )
				return null;

			return $path;
		}

		/**
		 *	Add an account, active, with the address lower-cased. The address has to
		 *	be one (FILTER_VALIDATE_EMAIL) and unique without regard to case - the
		 *	check is made inside the write, so two requests at once cannot both add
		 *	it - and the name at most 120 characters. A group is made a slug, and a
		 *	text without a letter or a digit is no group
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email
		 *	@param		string		$name
		 *	@param		string		$group
		 *
		 *	@return 	array|string							The account as account() has it; or why not: 'invalid' (the address), 'long' (the name), 'taken' (the address is a member's already), 'write' (the file could not be written)
		 */
		public static function add( array &$appData, string $email, string $name, string $group ): array|string {

			$email = self::_email( $email );
			$name = trim( $name );

			if( $email === '' )
				return 'invalid';

			if( mb_strlen( $name, 'UTF-8' ) > self::NAME_LENGTH )
				return 'long';

			$record = [ 'email' => $email, 'name' => $name, 'group' => \Nino\Feature::slug( $group ), 'active' => true, 'created' => gmdate( self::TIME ), 'login' => null ];
			$id = '';
			$taken = false;

			$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $record, &$id, &$taken ): ?array {

				$accounts = self::_accountsOf( $state );

				foreach( $accounts as $each )
					if( $each['email'] === $record['email'] ) {
						$taken = true;
						return null;
					}

				do
					$id = bin2hex( random_bytes( 6 ) );
				while( isset( $accounts[$id] ) === true );

				$accounts[$id] = $record;

				return [ 'accounts' => $accounts ];
			}, [] );

			if( $taken === true )
				return 'taken';

			return $written === true ? [ 'id' => $id ] + $record : 'write';
		}

		/**
		 *	Change the name, the group, whether an account is active and its address -
		 *	what $changes names and nothing else; the times and the id stay. The rules
		 *	of add() hold for the name, the group and the address, which is the owner's
		 *	to change here: the member's own way is askChange() and confirmChange(). The
		 *	address is looked at in the lock that writes it - one that another account
		 *	has is 'taken', whatever its case - and the address the account has already is
		 *	no change and writes nothing. A new address deletes every link and code the
		 *	account has out, gives the account a new epoch of its own, which ends every
		 *	session of it (see current()), mails the old address that it was changed and
		 *	fires '/members/changed' with by 'owner'
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		array 		$changes			Any of 'name' (string), 'group' (string), 'active' (bool) and 'email' (string)
		 *
		 *	@return 	array|string							The account as it is now; or why not: 'unknown' (no such id), 'long' (the name), 'invalid' (the address), 'taken' (the address is another account's), 'write'
		 */
		public static function change( array &$appData, string $id, array $changes ): array|string {

			$changed = self::_rewrite( $appData, $id, $changes, 'owner' );

			return is_array( $changed ) === true ? [ 'id' => $id ] + self::_plain( $changed ) : $changed;
		}

		/**
		 *	Ask for the address of the signed-in member's account to be another: the new
		 *	address gets six digits and nothing is changed until they are given back
		 *	(confirmChange()). The request is kept in the session of the member - the new
		 *	address, a random nonce, no tries yet and the time it is good until, the
		 *	setting linkMinutes - and the code under sha256( nonce | code ), so that it is
		 *	worth nothing outside that session. It is the same for an address that is
		 *	free and for one another account has: the request is kept either way, the code
		 *	is made, kept and put in the outbox of the kernel either way (see _issueCode()),
		 *	and only a free address is mailed, the other is sent the mail to nobody
		 *	(\Nino\Mail::VOID) - nothing in the answer tells the member whose address it is.
		 *	The visitor is counted (the setting attempts, per hour) and so is the new address
		 *	(3 per hour), as the request for a login code counts them. A request for the address
		 *	the session asked for a code for already, which is live, keeps its nonce and its
		 *	tries; an older request for another address is replaced
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$account			The member who is signed in, as current() has it
		 *	@param		string		$newEmail			As typed
		 *
		 *	@return 	string										'sent' (a code is on its way if the address is free), 'invalid' (no address, or $account is not the member of this session), 'same' (the address the account has), 'limit'
		 */
		public static function askChange( array &$appData, array $account, string $newEmail ): string {

			$email = self::_email( $newEmail );
			$record = self::_own( $appData, $account ) === true ? self::_record( $appData, (string) $account['id'] ) : null;

			if( $email === '' || $record === null )
				return 'invalid';

			if( $email === $record['email'] )
				return 'same';

			$attempts = self::_attempts( $appData );

			if( \Nino\Feature::count( $appData, self::COUNTER, self::_visitor( $appData ), $attempts, self::WINDOW ) === false
				|| \Nino\Feature::count( $appData, self::COUNTER, 'address|'. $email, self::ADDRESS_LIMIT, self::WINDOW ) === false )
				return 'limit';

			// The code of this session that is out for this address stays good: a second request - a double click, a form sent again - has the mail of the first, which the
			// outbox does not send twice (see _issueCode()), and the code of that mail must still be one
			$held = self::_changePending( $appData );
			$reuse = $held !== null && $held['account'] === (string) $account['id'] && $held['email'] === $email;
			$nonce = $reuse === true ? $held['nonce'] : bin2hex( random_bytes( 16 ) );
			$until = time() + self::_minutes( $appData ) * 60;

			\Nino\Runtime::setSessionValue( $appData, self::SESSION_CHANGE, [ 'account' => (string) $account['id'], 'email' => $email, 'nonce' => $nonce, 'tries' => $reuse === true ? $held['tries'] : 0, 'until' => $until ] );

			// The same work for an address that is free and for one another account has: the record of the code, the mail of the same template, the outbox. The one difference is
			// where the mail goes, and that is decided by the kind of it
			self::_issueCode( $appData, 'change', (string) $account['id'], self::_byEmail( $appData, $email ) === null, $email, $nonce, $until, '' );

			return 'sent';
		}

		/**
		 *	The address a code was asked for, while the request of this session is live
		 *	and the member's own: what the second step of the form names
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$account			The member who is signed in, as current() has it
		 *
		 *	@return 	string|null								The new address; null for none, one that ran out and one of another account
		 */
		public static function changePending( array &$appData, array $account ): ?string {

			$pending = self::_changePending( $appData );

			return $pending !== null && self::_own( $appData, $account ) === true && $pending['account'] === $account['id'] ? $pending['email'] : null;
		}

		/**
		 *	Take the code of the mail askChange() sent: the request is the one in the
		 *	session and the account's own - without one, after its time, one of another
		 *	account or after the fifth wrong try it is 'gone' and over. The try is counted
		 *	first, the code - six digits, spaces left out; anything else is a wrong one -
		 *	is looked up as sha256( nonce | code ) and spent, and only a record of the kind
		 *	'change' for this account and this address counts. A good one ends the
		 *	request and writes the address the way change() does, from the member's side
		 *	(its links and codes die, the account's other sessions end, the old address is
		 *	told, '/members/changed' is fired with by 'member'); this session keeps its
		 *	place and carries the new epoch of the account. An address another account has
		 *	by now is 'gone' as well, as it would have been before: nobody is told whose it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$account			The member who is signed in, as current() has it
		 *	@param		string		$code					As typed
		 *
		 *	@return 	string										'ok', 'wrong', 'gone', or 'write' (the address could not be written - the code is spent)
		 */
		public static function confirmChange( array &$appData, array $account, string $code ): string {

			$pending = self::_changePending( $appData );

			if( $pending === null || self::_own( $appData, $account ) === false || $pending['account'] !== $account['id'] ) {
				\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_CHANGE );
				return 'gone';
			}

			$pending['tries']++;
			\Nino\Runtime::setSessionValue( $appData, self::SESSION_CHANGE, $pending );

			$code = preg_replace( '/\s+/', '', $code ) ?? '';
			$spent = preg_match( '/^[0-9]{6}$/D', $code ) === 1 ? self::_spendHash( $appData, hash( 'sha256', $pending['nonce']. '|'. $code ), 'change' ) : null;

			if( $spent === null ) {

				if( $pending['tries'] >= self::CODE_TRIES ) {
					\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_CHANGE );
					return 'gone';
				}

				return 'wrong';
			}

			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_CHANGE );

			if( $spent['account'] !== $account['id'] || ( $spent['email'] ?? '' ) !== $pending['email'] )
				return 'gone';

			$changed = self::_rewrite( $appData, $pending['account'], [ 'email' => $pending['email'] ], 'member' );

			if( is_array( $changed ) === false )
				return $changed === 'write' ? 'write' : 'gone';

			$session = \Nino\Runtime::getSessionValue( $appData, self::SESSION_ACCOUNT );

			\Nino\Runtime::setSessionValue( $appData, self::SESSION_ACCOUNT, [ 'id' => $pending['account'], 'epoch' => (string) ( $session['epoch'] ?? '' ) ] + ( ( $changed['epoch'] ?? '' ) !== '' ? [ 'own' => $changed['epoch'] ] : [] ) );

			return 'ok';
		}

		/**
		 *	Delete an account - that one and no other. The links it still has out
		 *	are good for nothing then: they name an account that is gone. Once the
		 *	file is written the callback '/members/removed' is told the id, the
		 *	address and the group of the account, so that the features that keep
		 *	something of a member clear it away
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	string										'ok', 'unknown' (no such id) or 'write'
		 */
		public static function remove( array &$appData, string $id ): string {

			$ran = false;
			$gone = null;

			$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, &$ran, &$gone ): ?array {

				$ran = true;
				$gone = null;
				$accounts = self::_accountsOf( $state );

				if( isset( $accounts[$id] ) === false )
					return null;

				$gone = [ 'id' => $id, 'email' => $accounts[$id]['email'], 'group' => $accounts[$id]['group'] ];
				unset( $accounts[$id] );

				return [ 'accounts' => $accounts ];
			}, [] );

			if( $ran === true && $gone === null )
				return 'unknown';

			if( $written !== true || $gone === null )
				return 'write';

			self::_removed( $appData, [ $gone ] );

			return 'ok';
		}

		/**
		 *	Delete every account whose group a test picks, the links and codes they still have out with them: the accounts leave the file in one write, their tokens in a second,
		 *	and then '/members/removed' is told each of them, as remove() tells one. A session of a deleted account reads signed out, since its account is gone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		callable	$select				( string $group ): bool - whether the accounts of the group go
		 *
		 *	@return 	array|string							The ids that went; or 'write' (the accounts could not be written: nothing changed). Tokens that could not be dropped are a warning, they name nobody any more
		 */
		public static function removeGroups( array &$appData, callable $select ): array|string {

			$ran = false;
			$gone = [];

			$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $select, &$ran, &$gone ): ?array {

				$ran = true;
				$gone = [];
				$accounts = self::_accountsOf( $state );

				foreach( $accounts as $id => $record )
					if( $select( $record['group'] ) === true ) {
						$gone[(string) $id] = [ 'id' => (string) $id, 'email' => $record['email'], 'group' => $record['group'] ];
						unset( $accounts[$id] );
					}

				return $gone === [] ? null : [ 'accounts' => $accounts ];
			}, [] );

			if( $ran === false || ( $gone !== [] && $written === false ) )
				return 'write';

			if( $gone === [] )
				return [];

			// A key of only digits is a number in php: the ids are strings, as the tokens carry them
			$ids = array_map( 'strval', array_keys( $gone ) );
			$changed = false;
			$looked = false;

			$dropped = \Nino\Filesystem::mutate( $appData, self::TOKENS, static function( mixed $state ) use ( $ids, &$changed, &$looked ): ?array {

				$looked = true;
				$kept = array_filter( self::_liveTokens( $state, time() ), static fn( array $entry ): bool => in_array( $entry['account'], $ids, true ) === false );

				if( $kept === ( is_array( $state ) === true ? $state : [] ) )
					return null;

				$changed = true;

				return $kept;
			}, [] );

			if( $dropped === false && ( $changed === true || $looked === false ) )
				trigger_error( 'Members: the links and codes of the deleted accounts could not be dropped', E_USER_WARNING );

			self::_removed( $appData, array_values( $gone ) );

			return array_map( 'strval', $ids );
		}

		/**
		 *	Send an account a link and keep its token: a link to sign in ('login',
		 *	valid for the setting linkMinutes), the one of an invitation ('invite',
		 *	valid for inviteDays) or one the owner sends to an account that is a member
		 *	already ('send', valid for inviteDays, with a mail of its own). The mail is absolute (the website's address is
		 *	needed) and goes out in the language of the request, or - for the panel,
		 *	which writes to somebody who is not in the room - in the site's native one.
		 *	A token the mail never carried is good for nobody: it is not found.
		 *	With $notify false nobody is mailed: the token is kept all the same and the link, as the
		 *	mail would have carried it, is the answer - once, since the index keeps the sha256 of the
		 *	token only. It is as good as a mailed one, for the same time, and whoever has it signs in
		 *	as the account: the owner who asks for it hands it over himself
		 *
		 *	The mail of an invitation or of a link of the owner ('invite', 'send') may carry a message of the owner, a paragraph before the
		 *	words that lead to the link (the slot [[message]] of the template, the paragraph of mail-message). It is written as text: escaped, so that
		 *	nothing in it is a tag, a fill or a shortcode, with its line breaks kept as breaks - a line or paragraph separator is one, any other control character or mark that
		 *	turns the text around is left out. An empty message leaves the mail as it is without one; a message is not kept anywhere and is of no use where nobody is mailed
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$account			As account() has it
		 *	@param		string		$kind					'login', 'invite' or 'send'
		 *	@param		string		$target				The path the member asked for, '' for none - where the link leads after the sign-in
		 *	@param		bool			$native				Write in the site's native language
		 *	@param		bool			$notify				Whether the account is mailed the link
		 *	@param		string		$message			The message of the owner for the mail of 'invite' and 'send'; '' for none
		 *
		 *	@return 	bool|string							Whether the mail was delivered - false, with a warning that names no address, where there is no website address to build the link on, the token could not be stored or the mail was not taken; with $notify false the link, or false where it could not be made
		 */
		public static function link( array &$appData, array $account, string $kind, string $target = '', bool $native = false, bool $notify = true, string $message = '' ): bool|string {

			if( in_array( $kind, self::LINKS, true ) === false || is_string( $account['id'] ?? null ) === false || is_string( $account['email'] ?? null ) === false )
				return false;

			// A site without an address has no link to put in the mail, and a mail
			// without its link is no better than none
			if( \Nino\Html::absoluteUrl( $appData ) === '' ) {
				trigger_error( 'Members: no link was sent, the site has no address (the fill /project/website/general/url)', E_USER_WARNING );
				return false;
			}

			// A link that is shown and not mailed is charged to the cap of mails as a mail would be - the configured one, '/nino/mail/cap', five by default, none at 0 - and refused over it as a mail is
			if( $notify === false ) {

				$appData['./nino/mail/ratelimited'] = false;

				if( \Nino\Feature::count( $appData, 'ratelimit', \Nino\Http::getClientIp( $appData ), (int) ( $appData['/nino/mail/cap'] ?? \Nino\AppData::DEFAULTS['/nino/mail/cap'] ), 3600 ) === false ) {
					$appData['./nino/mail/ratelimited'] = true;
					return false;
				}
			}

			$seconds = $kind === 'login' ? self::_minutes( $appData ) * 60 : self::_days( $appData ) * 86400;
			$token = self::_issue( $appData, $account['id'], $kind, $seconds, $target );

			if( $token === null ) {
				trigger_error( 'Members: a link could not be stored', E_USER_WARNING );
				return false;
			}

			if( $notify === false )
				return \Nino\Html::absoluteUrl( $appData, '/.members/login?t='. rawurlencode( $token ) );

			if( self::_mail( $appData, $account['email'], $account['id'], $kind, $token, $native, $message ) === false ) {
				trigger_error( 'Members: a link could not be mailed', E_USER_WARNING );
				return false;
			}

			return true;
		}

		/**
		 *	Sign every member out at once: write a new session epoch, which no
		 *	session that signed in before it carries, take every token of a device
		 *	that stays signed in (a cookie would sign the member in again) and every link and code that is out. The panel's
		 *	'Sign everybody out' ends here - a member who left must not stay in because
		 *	the browser kept the session. Nobody is identified, so there is no one to
		 *	name; the epoch is all there is to rotate.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool										False where a file could not be written
		 */
		public static function signOutAll( array &$appData ): bool {

			$written = \Nino\Filesystem::mutate( $appData, self::EPOCH, static fn(): array => [ 'epoch' => bin2hex( random_bytes( 16 ) ) ], [] );
			$ran = false;
			$dirty = false;

			$cleared = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( &$ran, &$dirty ): ?array {

				$ran = true;
				$accounts = self::_accountsOf( $state );

				foreach( $accounts as $id => $record )
					if( isset( $record['remember'] ) === true ) {
						unset( $accounts[$id]['remember'] );
						$dirty = true;
					}

				return $dirty === true ? [ 'accounts' => $accounts ] : null;
			}, [] );

			// The links and the codes that are out end with the sessions: a link made before the button was pressed signs nobody in after it. What has run out stays,
			// for the page that says a link no longer works to lead where it led (see _liveTokens())
			$now = time();
			$looked = false;
			$changed = false;

			$dropped = \Nino\Filesystem::mutate( $appData, self::TOKENS, static function( mixed $state ) use ( $now, &$looked, &$changed ): ?array {

				$looked = true;
				$kept = array_filter( self::_liveTokens( $state, $now ), static fn( array $entry ): bool => $entry['expires'] <= $now );

				if( $kept === ( is_array( $state ) === true ? $state : [] ) )
					return null;

				$changed = true;

				return $kept;
			}, [] );

			return $written === true && $ran === true && ( $dirty === false || $cleared === true ) && $looked === true && ( $changed === false || $dropped === true );
		}

		/**
		 *	Sign one account out of every device: the list of the tokens of devices that stay signed in is emptied
		 *	and the account gets an epoch of its own, as a change of the address gives it, so that every session of it
		 *	ends - and no token is left that would sign it in again: the links and codes it has out end with it. The session of the member who asks - where it is
		 *	the account's own - stays: it is written again with the new epoch, and the cookie of this device is
		 *	deleted. Anybody else's session, the owner's among them, is left alone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						12 hex characters
		 *
		 *	@return 	bool										False for an id nobody has and where the file could not be written
		 */
		public static function forgetAll( array &$appData, string $id ): bool {

			$mine = self::_own( $appData, [ 'id' => $id ] );
			$epoch = bin2hex( random_bytes( 16 ) );
			$found = false;

			$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, $epoch, &$found ): ?array {

				$accounts = self::_accountsOf( $state );

				if( isset( $accounts[$id] ) === false )
					return null;

				$found = true;
				unset( $accounts[$id]['remember'] );
				$accounts[$id]['epoch'] = $epoch;

				return [ 'accounts' => $accounts ];
			}, [] );

			if( $found === false || $written === false || self::_forget( $appData, $id ) === false )
				return false;

			if( $mine === true ) {

				$session = \Nino\Runtime::getSessionValue( $appData, self::SESSION_ACCOUNT );

				\Nino\Runtime::setSessionValue( $appData, self::SESSION_ACCOUNT, [ 'id' => $id, 'epoch' => (string) ( is_array( $session ) === true ? ( $session['epoch'] ?? '' ) : '' ), 'own' => $epoch ] );
				self::_cookie( $appData, '', 0 );
			}

			return true;
		}

		/**
		 *	The gate: replace the response of a request under a protected path - or
		 *	for a page whose uri lies under one, at whichever address a route gives it
		 *	(see _behindTheGate()) - by somebody who is not signed in, with the login
		 *	page. The answer is a response of the gate's own, not the route's with a new
		 *	body: nothing the route brought - a file, a header, a callback bound to its uri,
		 *	the title of its page - is left in it. A plain 401 - never a 200, so nothing
		 *	caches it, and honest about the fact that nothing was actually served. The
		 *	path and the query of the page, as they came, are kept in the session, for
		 *	the code that is requested from this page to lead back to it (see _wanted(),
		 *	which leaves the state members= out). The workbench and the endpoints of this
		 *	feature are never protected (see cleanPrefix()), and a signed-in workbench
		 *	user is no exception: the sign-in is the only way in.
		 *
		 *	A request of a signed-in member is kept out of the full-page cache as
		 *	well, whatever its path: a page that is rendered for them may carry their
		 *	name, and the cache would hand it to the next visitor
		 *
		 *	The gate is also where a device that stays signed in is signed in (see
		 *	_resume()): before anything reads the member, once for a request
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackGate( array &$appData, array &$request ): void {

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );

			self::_resume( $appData, $request );

			if( \Nino\Runtime::getSessionValue( $appData, self::SESSION_ACCOUNT ) !== null )
				self::_keepOutOfCache( $appData, [ $uri ] );

			// The workbench is never behind the gate, whatever the configuration says
			$asked = self::_fold( $uri );

			if( $asked === '/_admin' || str_starts_with( $asked, '/_admin/' ) === true )
				return;

			if( self::_behindTheGate( $appData, $request ) === false || self::current( $appData ) !== null )
				return;

			\Nino\Runtime::setSessionValue( $appData, self::SESSION_TARGET, self::_wanted( $uri, (string) ( $request['REQUEST_URI'] ?? '' ) ) );

			self::_answerPage( $appData, $request, 'page-members-login', 401 );
		}

		/**
		 *	Whether a request is for a page behind the gate: the path asked for lies
		 *	under a protected prefix, or the uri of the route that answers it does. The
		 *	Routes panel gives a page one route for each language, every one with the
		 *	page's uri and an address of its own, and the setting names the page - /club,
		 *	say - not each of its addresses: /verein would be open while /club is shut. A
		 *	wildcard route's uri, /club/*, lies under the prefix as well
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
		 *	The page a visitor wanted, as a sign-in leads back to it: the path and the query as they came, without a fragment - no browser sends one - and without the state the feature itself adds (members=). A query that makes
		 *	the whole no path _isPath() accepts (a control character, a blank), or one that makes it longer than TARGET_MAX, leaves the path alone
		 *
		 *	@param		string		$uri					The path of the request
		 *	@param		string		$raw					The address of the request as the server got it
		 *
		 *	@return 	string
		 */
		private static function _wanted( string $uri, string $raw ): string {

			$cut = strpos( $raw, '?' );
			$query = $cut === false ? '' : substr( $raw, $cut + 1 );
			$query = substr( $query, 0, strcspn( $query, '#' ) );

			// The state a redirect of the feature adds to a page (?members=out after a sign-out, ?members=sent) is the answer to a request and no part of the page the visitor wanted
			$query = implode( '&', array_filter( explode( '&', $query ), static fn( string $pair ): bool => preg_match( '/^members(?:=|$)/', $pair ) !== 1 ) );
			$target = $query === '' ? $uri : $uri. '?'. $query;

			return strlen( $target ) <= self::TARGET_MAX && self::_isPath( $target ) === true ? $target : $uri;
		}

		/**
		 *	Where a sign-in leads: the page it was asked from or the link carries, else the start page - and the features that build on the sign-in may say another, through the callback
		 *	'/members/start', fired with the account, the target (the page of the sign-in, '' for none) and to (where it leads: the target or the start page). A callback that sets
		 *	to to a path _isPath() accepts moves the member there; any other value of to is the start page
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$account			As account() has it
		 *	@param		string		$target				The path the record of the code or of the link carries
		 *
		 *	@return 	string										A path of this site
		 */
		private static function _arrival( array &$appData, array $account, string $target ): string {

			$target = strlen( $target ) <= self::TARGET_MAX && self::_isPath( $target ) === true ? $target : '';
			$start = self::_start( $appData );

			// The page the gate kept for a visitor is used up by the sign-in: the redirect back to a protected page, after a code was asked for, keeps it again, and the next sign-in would lead there
			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_TARGET );

			$args = [ 'account' => $account, 'target' => $target, 'to' => $target !== '' ? $target : $start ];

			\Nino\Callbacks::doCallbacks( $appData, '/members/start', $args );

			$to = is_array( $args ) === true ? ( $args['to'] ?? null ) : null;

			return is_string( $to ) === true && strlen( $to ) <= self::TARGET_MAX && self::_isPath( $to ) === true ? $to : $start;
		}

		/**
		 *	POST /.members/login: ask for a code. The sender is counted (the setting attempts, ten at most, per hour), so is the address (3 per hour) and, inside _issueCode(), the outbox as it stands (see _braked()), by the kernel's
		 *	counter and before anything else, whoever the address belongs to; over
		 *	either the answer is a redirect with ?members=limit and nothing else
		 *	happens. Otherwise, for an address that is one, the session keeps the
		 *	request - the address, a random nonce, no tries yet and the time the code
		 *	is good until - whoever the address belongs to, and the same work is done for
		 *	every address (see _issueCode()): a code of six digits, kept as the sha256 of the
		 *	nonce and the code - worth nothing outside this session -, its mail put in the outbox
		 *	of the kernel, to the address for an active account and to nobody for any other. The answer
		 *	is the same redirect to the page the form was on, ?members=sent, for all of them, and
		 *	never depends on what the outbox answered. A text that is no address ends the request that
		 *	was out, and leaves the form to say that there is none. A post the csrf guard refused - a session that
		 *	ran out under a form that stayed open - is answered with a redirect to the
		 *	same page and ?members=expired, in place of the 403 and the empty body the
		 *	guard leaves, and is not counted
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLoginRequest( array &$appData, array &$request ): void {

			$return = self::_returnPath( $appData, self::_posted( 'return' ) );

			// Respect a rejection from the earlier global Csrf callback - same guard
			// Form/Newsletter/Auth use - and say it to the visitor
			if( \Nino\Form::blocked( $request ) === true ) {
				self::_redirect( $appData, $request, $return, 'expired' );
				return;
			}

			$email = self::_email( self::_posted( 'email' ) );
			$attempts = self::_attempts( $appData );

			// The visitor first, then - for a text that is an address - the address,
			// each in the lock of its own write: a check made first and a try
			// recorded afterwards is a cap every request of a burst walks through at
			// once. A count that cannot be recorded is a refusal, as the counter
			// answers it
			if( \Nino\Feature::count( $appData, self::COUNTER, self::_visitor( $appData ), $attempts, self::WINDOW ) === false
				|| ( $email !== '' && \Nino\Feature::count( $appData, self::COUNTER, 'address|'. $email, self::ADDRESS_LIMIT, self::WINDOW ) === false ) ) {
				self::_redirect( $appData, $request, $return, 'limit' );
				return;
			}

			if( $email === '' )
				\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_PENDING );
			else {

				// An address that is a member's and one that is not are kept alike: the form asks for a code for both. A code of this session for this address that is live stays
				// good - the second request has the mail of the first (see _issueCode()), and a new nonce would end the code that mail carries - and keeps its tries
				$held = self::_pending( $appData );
				$reuse = $held !== null && $held['email'] === $email;
				$nonce = $reuse === true ? $held['nonce'] : bin2hex( random_bytes( 16 ) );
				$until = time() + self::_minutes( $appData ) * 60;

				\Nino\Runtime::setSessionValue( $appData, self::SESSION_PENDING, [ 'email' => $email, 'nonce' => $nonce, 'tries' => $reuse === true ? $held['tries'] : 0, 'until' => $until ] );

				$account = self::_byEmail( $appData, $email );
				$account = $account !== null && $account['active'] === true ? $account : null;

				$target = \Nino\Runtime::getSessionValue( $appData, self::SESSION_TARGET );
				$target = is_string( $target ) === true && self::_isPath( $target ) === true ? $target : '';
				$posted = self::_posted( 'return' );

				// The page the gate kept goes with a code that is asked for from that page - or from the page of a link that did not lead in, which keeps its target on purpose - and with no other
				if( substr( $posted, 0, strcspn( $posted, '?#' ) ) !== substr( $target, 0, strcspn( $target, '?#' ) ) && str_starts_with( $posted, '/.members/' ) === false )
					$target = '';

				$target = self::_anchored( $target, $posted );

				// The page the gate kept travels with the code, and the session has used it up: for every address alike
				\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_TARGET );

				// The same work for an address that has an account and one that has none - a code, its record, the mail of the same template and the outbox -, and the answer below is
				// the same for both whatever the outbox says. A switched-off account is none
				self::_issueCode( $appData, 'code', $account === null ? bin2hex( random_bytes( 6 ) ) : $account['id'], $account !== null, $email, $nonce, $until, $target );
			}

			self::_redirect( $appData, $request, $return, 'sent' );
		}

		/**
		 *	The place on the page a sign-in leads to: the target the gate kept - the page a visitor asked for, path and query, which no browser sends a fragment with - with the
		 *	anchor the form carries behind its 'return' (a script of the page appends the fragment of the address, #angebot-15). The anchor is kept only when the target is
		 *	the page the form stood on, and only as letters, digits, hyphens and underscores, 64 of them at most; anything else - a second #, a character that is
		 *	none of those, a form that names another page - leaves the target as it is
		 *
		 *	@param		string		$target				What the session kept, '' for none
		 *	@param		string		$posted				The field 'return' of the form
		 *
		 *	@return 	string
		 */
		private static function _anchored( string $target, string $posted ): string {

			$at = strpos( $posted, '#' );

			if( $target === '' || $at === false || preg_match( '/^[A-Za-z0-9_-]{1,64}$/D', substr( $posted, $at + 1 ) ) !== 1 || substr( $posted, 0, strcspn( $posted, '?#' ) ) !== substr( $target, 0, strcspn( $target, '?#' ) ) )
				return $target;

			return strlen( $target. substr( $posted, $at ) ) <= self::TARGET_MAX ? $target. substr( $posted, $at ) : $target;
		}

		/**
		 *	POST /.members/code: the code of the mail. The request of this session is
		 *	the one in the session - without one, or after its time, the answer is a
		 *	redirect to the page the form was on with ?members=gone and the request is
		 *	over. A post the csrf guard refused is answered with ?members=expired and
		 *	leaves the request as it is, with no try used. Otherwise the try is counted
		 *	first, and the code - six digits, spaces left out; anything else is a code
		 *	that is wrong - is looked up as sha256( nonce | code ) and spent. A code
		 *	that is good ends the request, signs the member in (_signIn()), tells the
		 *	features that build on it and redirects, 303, to the path the record of the
		 *	code carries, else to the start page, as callbackConfirm() does - where the
		 *	callback '/members/start' may name another page (see _arrival()); one that is
		 *	good for an account that is gone or switched off ends the request with
		 *	?members=gone. One that is not found is ?members=wrong - and, the fifth time,
		 *	the end of the request and ?members=gone: a new code has to be asked for.
		 *	With the box remember posted, a good code also makes the token of this device and sets the cookie (see _remember())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackCode( array &$appData, array &$request ): void {

			$return = self::_returnPath( $appData, self::_posted( 'return' ) );

			if( \Nino\Form::blocked( $request ) === true ) {
				self::_redirect( $appData, $request, $return, 'expired' );
				return;
			}

			$pending = self::_pending( $appData );

			if( $pending === null ) {
				\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_PENDING );
				self::_redirect( $appData, $request, $return, 'gone' );
				return;
			}

			$pending['tries']++;
			\Nino\Runtime::setSessionValue( $appData, self::SESSION_PENDING, $pending );

			$code = preg_replace( '/\s+/', '', self::_posted( 'code' ) ) ?? '';
			$spent = preg_match( '/^[0-9]{6}$/D', $code ) === 1 ? self::_spendHash( $appData, hash( 'sha256', $pending['nonce']. '|'. $code ), 'code' ) : null;

			if( $spent === null ) {

				if( $pending['tries'] >= self::CODE_TRIES )
					\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_PENDING );

				self::_redirect( $appData, $request, $return, $pending['tries'] >= self::CODE_TRIES ? 'gone' : 'wrong' );
				return;
			}

			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_PENDING );

			$account = self::account( $appData, $spent['account'] );

			// A code asked for an address the account has left is no way in: the account still has the address the request was made for
			if( $account === null || $account['active'] === false || $account['email'] !== $pending['email'] ) {
				self::_redirect( $appData, $request, $return, 'gone' );
				return;
			}

			if( self::_signIn( $appData, $account ) === false ) {
				self::_refuse( $appData, $request, 500 );
				return;
			}

			\Nino\Callbacks::doCallbacks( $appData, '/members/login', $account );

			if( self::_posted( 'remember' ) === '1' )
				self::_remember( $appData, $account['id'] );

			\Nino\Http::redirect( $appData, $request, self::_arrival( $appData, $account, $spent['target'] ), 303 );
		}

		/**
		 *	GET /.members/login?t=<token>: follow a link. Nothing is changed by it - a mail
		 *	scanner or a browser that fetches the page ahead, a HEAD, uses nobody's link
		 *	up. A token that is unknown, expired, of no kind this feature makes or for an
		 *	account that is gone or switched off answers the login page in place (see
		 *	_refuse(): status 400, not stored by anyone) with the state 'invalid'. A good
		 *	one answers a page that names the account and holds one button (see
		 *	componentMembersConfirm()), which posts the token to /.members/confirm - status
		 *	200, not stored by anyone and with no referrer, since the address of the page
		 *	carries the token
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLoginLink( array &$appData, array &$request ): void {

			$query = $request['/nino/http/request']['query'] ?? [];
			$token = is_array( $query ) === true && is_string( $query['t'] ?? null ) === true ? $query['t'] : '';
			$found = self::_find( $appData, $token );
			$account = $found === null ? null : self::account( $appData, $found['account'] );

			if( $account === null || $account['active'] === false ) {
				self::_refuseLink( $appData, $request, $token );
				return;
			}

			self::_confirmPage( $appData, $request, $token, $account, '', 200 );
		}

		/**
		 *	POST /.members/confirm: the button of the page a link opens. The token is in
		 *	the post and nothing else of the link: where the member is sent after the
		 *	sign-in stays in the record of the token on the server, where nobody can change
		 *	it. A post the csrf guard refused leaves a 403 and an empty body; the page is
		 *	answered again instead, with a form that carries the token of the session as
		 *	it is now and the state 'expired' - the token of the link has not been used. A
		 *	token that is spent, has run out or is for an account that is gone or switched
		 *	off answers the login page in place, 400, with the state 'invalid' (an account
		 *	that is switched off has the token spent all the same). A good one is spent
		 *	(deleted), the member is signed in (_signIn()), the features that build on it are
		 *	told, and the answer is a redirect, 303, to the path the token carries, else to
		 *	the start page - where the callback '/members/start' may name another page (see _arrival())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackConfirm( array &$appData, array &$request ): void {

			$token = self::_posted( 'token' );

			if( \Nino\Form::blocked( $request ) === true ) {

				$found = self::_find( $appData, $token );
				$account = $found === null ? null : self::account( $appData, $found['account'] );

				if( $account === null || $account['active'] === false )
					self::_refuseLink( $appData, $request, $token );
				else
					self::_confirmPage( $appData, $request, $token, $account, 'expired', 403 );

				return;
			}

			$spent = self::_spend( $appData, $token );
			$account = $spent === null ? null : self::account( $appData, $spent['account'] );

			if( $account === null || $account['active'] === false ) {
				self::_refuseLink( $appData, $request, $token );
				return;
			}

			if( self::_signIn( $appData, $account ) === false ) {
				self::_refuse( $appData, $request, 500 );
				return;
			}

			\Nino\Callbacks::doCallbacks( $appData, '/members/login', $account );

			\Nino\Http::redirect( $appData, $request, self::_arrival( $appData, $account, $spent['target'] ), 303 );
		}

		/**
		 *	POST /.members/logout: sign the member out and go to the start page with
		 *	?members=out. A form with the csrf token, as every write of this feature is: a
		 *	link would let any other page sign a member out. A post the csrf guard refused
		 *	leaves the 403 and an empty body, and the member stays signed in. The token
		 *	of this device goes with the session, and the cookie that carried it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLogout( array &$appData, array &$request ): void {

			if( \Nino\Form::blocked( $request ) === true )
				return;

			$account = self::current( $appData );

			self::_dropDevice( $appData );

			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_ACCOUNT );
			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_CHANGE );

			if( $account !== null )
				\Nino\Callbacks::doCallbacks( $appData, '/members/logout', $account );

			self::_redirect( $appData, $request, self::_start( $appData ), 'out' );
		}

		/**
		 *	GET /.members/logout: nothing is signed out by a request that is no post. The answer is 405 with the header Allow and a short page that links to the start page,
		 *	not stored by anyone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLogoutGet( array &$appData, array &$request ): void {

			$request['/nino/http/response']['statusCode']							= 405;
			$request['/nino/http/response']['header']['Allow']					= 'POST';
			$request['/nino/http/response']['header']['Cache-Control']	= 'no-store';
			$request['/nino/http/response']['header']['Content-Type']	= 'text/html; charset=utf-8';
			// The path of the start page is the site's own, and the body is drawn as a page is: every '[' of it is written as text
			$request['/nino/http/response']['body']										= strtr( \Nino\Feature::template( $appData, self::TEMPLATES, 'members-method' ), [
				'[[lang]]'	=> \Nino\Components::escape( substr( \Nino\Locales::getCurrentLocale( $appData ), 0, 2 ) ),
				'[[home]]'	=> str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). self::_start( $appData ) ) ),
			] );
		}

		/**
		 *	The addresses a crawler has no business listing, answered under
		 *	'/seo/exclude' for the Seo feature, where that is installed: every
		 *	protected prefix and everything below it, and the address of every route
		 *	whose page lies under one - Seo matches the addresses of the routes, and a
		 *	page has one for each language (see _behindTheGate())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$list				(reference) Patterns, in the spelling of Seo's 'exclude' setting
		 *
		 *	@return 	void
		 */
		public static function callbackSeoExclude( array &$appData, array &$list ): void {

			foreach( self::prefixes( $appData ) as $prefix )
				$list[] = $prefix. '/*';

			foreach( \Nino\Http::pages( (array) ( $appData['/nino/http/routes'] ?? [] ) ) as $row )
				if( self::protects( $appData, $row['path'] ) === false && self::protects( $appData, $row['uri'] ) === true )
					$list[] = $row['wildcard'] === true ? $row['path']. '/*' : $row['path'];
		}

		/**
		 *	The renderer of [members-login] - the form that asks for a code, for a visitor; for a member
		 *	the line that names them, with the button to sign out. The form is in two
		 *	steps. The first is the one POST /.members/login takes: a label and the
		 *	field for the address, the page it stands on as 'return' (where the answer
		 *	goes back to), the csrf token and the button. The second is the one POST
		 *	/.members/code takes (template members-code): the address the code went to,
		 *	the field for the code, the same 'return' and token, the button and a link
		 *	back to the page, to ask for a new code. The second step is drawn while
		 *	the session holds a request that is live (_pending()) and the state is
		 *	'sent' or 'wrong'; a state of the two without such a request is said as
		 *	'gone', in the first step. Both say the state, if there is one, above the
		 *	button: what a callback of this request set, else what the query 'members'
		 *	names, of STATES and nothing else. inline puts the field and the button of
		 *	the first step on one line, where the label is read by screen readers only;
		 *	the second step is never inline. The words are the project's /feature/members/ keys.
		 *	Over the label of the first step stands the slot note, which the callback
		 *	'/members/login-form' may fill (it is fired with return, the path of the
		 *	page, and note, empty): the markup is the callback's and is put in as it
		 *	is, so a callback escapes whatever it took from the request. What the
		 *	component draws is finished: nothing of it is read again, so the token of
		 *	the session stands in its form as the value of a field and not as a call
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string										Finished markup
		 */
		public static function componentMembersLogin( array &$appData, array $args, string $content, array $context ): string {

			$member = self::current( $appData );
			$inline = ( $args['inline'] ?? '0' ) === '1';
			// The classes of the call without the one of the step: each step says its own
			$class = trim( str_replace( ' members-login--inline', '', (string) ( $args['class'] ?? '' ) ) );

			if( $member !== null )
				return self::_draw( $appData, 'members-signed', [
					'[[class]]'		=> $class. ' members-login--signed',
					'[[name]]'		=> \Nino\Components::escape( self::_label( $member ) ),
					'[[logout]]'	=> self::_logout( $appData, 'members-logout' ),
				] );

			$state = self::_state( $appData );
			$request = \Nino\Http::getRequest( $appData );
			$path = $request === false ? '' : (string) ( $request['/nino/http/request']['uri'] ?? '' );
			$return = \Nino\Components::escape( $path );
			$pending = in_array( $state, [ 'sent', 'wrong' ], true ) === true ? self::_pending( $appData ) : null;

			// A request that is over - or that no session knows - is gone, and the form starts again
			if( $pending === null && in_array( $state, [ 'sent', 'wrong' ], true ) === true )
				$state = 'gone';

			$forms = $appData[self::FORMS] = (int) ( $appData[self::FORMS] ?? 0 ) + 1;
			$said = 'members-state-'. $forms;
			$common = [ '[[action]]' => self::_action( $appData, 'login' ), '[[csrf]]' => \Nino\Components::escape( \Nino\Csrf::getToken( $appData ) ), '[[return]]' => $return, '[[state]]' => self::_say( $appData, $state, $said ),
				// The field the sentence is about: where the state is a failure - a code that is wrong, a request that is refused - the field is invalid and the sentence describes it
				'[[invalid]]' => in_array( $state, $pending !== null ? [ 'wrong' ] : self::FAILURES, true ) === true ? ' aria-invalid="true" aria-describedby="'. $said. '"' : '' ];

			if( $pending !== null )
				return self::_draw( $appData, 'members-code', [
					'[[class]]'			=> $class. ' members-login--code',
					'[[action]]'		=> self::_action( $appData, 'code' ),
					'[[field]]'			=> 'members-code-'. $forms,
					'[[email]]'			=> \Nino\Components::escape( $pending['email'] ),
					'[[again]]'			=> \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). $path ),
					'[[remember]]'	=> self::_box( $appData ),
				] + $common );

			// The slot over the field: a feature that builds on the sign-in may put a sentence there (markup it has escaped itself); a callback that does not answer leaves it empty
			$slot = [ 'return' => $path, 'note' => '' ];
			\Nino\Callbacks::doCallbacks( $appData, '/members/login-form', $slot );

			return \Nino\Components::fill( $appData, 'members-login', $args, $content, [
				'[[field]]'		=> 'members-login-'. $forms,
				'[[hidden]]'	=> $inline === true ? ' nino-sr-only' : '',
				'[[note]]'		=> is_array( $slot ) === true && is_string( $slot['note'] ?? null ) === true ? $slot['note'] : '',
			] + $common );
		}

		/**
		 *	The renderer of [members-logout] - a button, in a form with the csrf token, that signs the member out; nothing for a
		 *	visitor, so that a header can carry it for everybody
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string										Finished markup
		 */
		public static function componentMembersLogout( array &$appData, array $args, string $content, array $context ): string {

			if( self::current( $appData ) === null )
				return '';

			return self::_logout( $appData, (string) ( $args['class'] ?? '' ) );
		}

		/**
		 *	The renderer of [members-name] - the name of the member who is signed in, their email
		 *	address where the account has no name; nothing for a visitor
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string										Finished markup
		 */
		public static function componentMembersName( array &$appData, array $args, string $content, array $context ): string {

			$member = self::current( $appData );

			if( $member === null )
				return '';

			return \Nino\Components::fill( $appData, 'members-name', $args, $content, [ '[[name]]' => \Nino\Components::escape( self::_label( $member ) ) ] );
		}

		/**
		 *	The renderer of [members-confirm] - the button of the page a link opens: the name of the
		 *	account the link signs in, the token of the link as a hidden field, the csrf token
		 *	and one button that posts them to /.members/confirm. It is the page of the
		 *	install unit that has it; anywhere else, and on every page that no link is
		 *	opened on, it shows nothing. Where it is shown again after a post the csrf
		 *	guard refused, the state 'expired' is said above the button, as the form of
		 *	[members-login] says it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$args					The attributes of the call
		 *	@param		string		$content			What stands between its tags (nothing: it is a leaf)
		 *	@param		array 		$context			Where the call stands
		 *
		 *	@return 	string										Finished markup
		 */
		public static function componentMembersConfirm( array &$appData, array $args, string $content, array $context ): string {

			$pending = $appData[self::CONFIRM] ?? null;

			if( is_array( $pending ) === false )
				return '';

			return \Nino\Components::fill( $appData, 'members-confirm', $args, $content, [
				'[[action]]'	=> self::_action( $appData, 'confirm' ),
				'[[csrf]]'		=> \Nino\Components::escape( \Nino\Csrf::getToken( $appData ) ),
				'[[name]]'		=> \Nino\Components::escape( (string) ( $pending['label'] ?? '' ) ),
				'[[token]]'		=> \Nino\Components::escape( (string) ( $pending['token'] ?? '' ) ),
				'[[state]]'		=> self::_say( $appData, (string) ( $pending['state'] ?? '' ), 'members-state-confirm' ),
			] );
		}

		/**
		 *	A template of the feature's own filled: the tokens first, and the words of the template - every
		 *	/feature/members/... fill of it - as the project has them, each as text. One pass over the template, so
		 *	nothing that is put in is read again
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The template, without .tpl, in TEMPLATES
		 *	@param		array 		$tokens				[ '[[token]]' => finished markup or escaped text ]
		 *
		 *	@return 	string
		 */
		private static function _draw( array &$appData, string $name, array $tokens ): string {

			$text = \Nino\Feature::template( $appData, self::TEMPLATES, $name );

			preg_match_all( '~\[\[(/feature/members/[a-z0-9/-]+)\]\]~', $text, $found );

			foreach( array_unique( $found[1] ) as $key )
				$tokens['[['. $key. ']]'] = \Nino\Components::escape( (string) \Nino\Html::resolveTextfill( $appData, $key ) );

			return strtr( $text, $tokens );
		}

		/**
		 *	The form that signs out, which [members-logout] is and the line of a signed-in member holds
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$class				The classes of the form
		 *
		 *	@return 	string
		 */
		private static function _logout( array &$appData, string $class ): string {

			return \Nino\Components::fill( $appData, 'members-logout', [ 'class' => $class ], '', [
				'[[action]]'	=> self::_action( $appData, 'logout' ),
				'[[csrf]]'		=> \Nino\Components::escape( \Nino\Csrf::getToken( $appData ) ),
			] );
		}

		/**
		 *	The address a form of the feature posts to, with the directory of the site in front
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$endpoint			login, code, confirm or logout
		 *
		 *	@return 	string										Escaped
		 */
		private static function _action( array &$appData, string $endpoint ): string {

			return \Nino\Components::escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). '/.members/'. $endpoint );
		}

		/**
		 *	The write behind change() and confirmChange(): the rules of change(), for whoever asks. A new
		 *	address ends what the old one let in, in this order: the account's links and codes are deleted
		 *	first, then the address is written, then they are deleted once more - a write that fails leaves
		 *	the account as it was with nothing left that signs in, and a link or a code made between the two
		 *	deletions does not outlive the address - a code to sign in is taken only while the account still has the
		 *	address the request was made for (callbackCode()), so one that was asked for the old address after
		 *	the first deletion is gone as well. What the lock would refuse (an address another account
		 *	has) is refused before anything is deleted, so a change that does not happen takes nobody's
		 *	links away. The old address is mailed that it was changed - in the language of the request,
		 *	in the site's native one for the owner, who is not in the room - and '/members/changed' is
		 *	fired; a notice that could not be sent is a warning and the change stays
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *	@param		array 		$changes			See change()
		 *	@param		string		$by						'owner' or 'member': who asked, for the callback and the language of the notice
		 *
		 *	@return 	array|string							The account as the file has it, with its id and its epoch; or why not, see change()
		 */
		private static function _rewrite( array &$appData, string $id, array $changes, string $by ): array|string {

			$name = is_string( $changes['name'] ?? null ) === true ? trim( $changes['name'] ) : null;
			$email = null;

			if( array_key_exists( 'email', $changes ) === true ) {

				$email = is_string( $changes['email'] ) === true ? self::_email( $changes['email'] ) : '';

				if( $email === '' )
					return 'invalid';
			}

			if( $name !== null && mb_strlen( $name, 'UTF-8' ) > self::NAME_LENGTH )
				return 'long';

			$group = is_string( $changes['group'] ?? null ) === true ? \Nino\Feature::slug( $changes['group'] ) : null;
			$active = is_bool( $changes['active'] ?? null ) === true ? $changes['active'] : null;
			$current = $email === null ? null : self::_record( $appData, $id );

			if( $current !== null && $current['email'] !== $email ) {

				if( self::_byEmail( $appData, (string) $email ) !== null )
					return 'taken';

				if( self::_forget( $appData, $id ) === false )
					return 'write';
			}

			$ran = false;
			$found = null;
			$taken = false;
			$same = false;
			$from = '';
			$ended = false;

			$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, $name, $group, $active, $email, &$ran, &$found, &$taken, &$same, &$from, &$ended ): ?array {

				$ran = true;
				$accounts = self::_accountsOf( $state );

				if( isset( $accounts[$id] ) === false )
					return null;

				$before = $accounts[$id];
				$accounts[$id] = array_merge( $before, array_filter( [ 'name' => $name, 'group' => $group, 'active' => $active ], static fn( mixed $value ): bool => $value !== null ) );

				// An account that is switched off keeps no device that would sign it in again, and no session it had: it gets an epoch of its own, so that
				// switching it on is no way back for a session from before - and its open links and codes are deleted below
				if( $active === false )
					unset( $accounts[$id]['remember'] );

				if( $active === false && $before['active'] === true ) {
					$accounts[$id]['epoch'] = bin2hex( random_bytes( 16 ) );
					$ended = true;
				}

				if( $email !== null && $email !== $before['email'] ) {

					foreach( $accounts as $other => $each )
						if( (string) $other !== $id && $each['email'] === $email ) {
							$taken = true;
							return null;
						}

					$accounts[$id]['email'] = $email;
					$accounts[$id]['epoch'] = bin2hex( random_bytes( 16 ) );

					// The devices were signed in under the old address
					unset( $accounts[$id]['remember'] );
					$from = $before['email'];
				}

				$found = [ 'id' => $id ] + $accounts[$id];
				$same = $accounts[$id] === $before;

				return $same === true ? null : [ 'accounts' => $accounts ];
			}, [] );

			if( $ran === true && $found === null )
				return $taken === true ? 'taken' : 'unknown';

			if( $found === null || ( $written === false && $same === false ) )
				return 'write';

			if( $ended === true && self::_forget( $appData, $id ) === false )
				trigger_error( 'Members: the links of an account that was switched off could not be deleted', E_USER_WARNING );

			if( $from === '' )
				return $found;

			if( self::_forget( $appData, $id ) === false )
				trigger_error( 'Members: the links of an account could not be deleted after its address was changed', E_USER_WARNING );

			// The owner's write is answered by the panel, in the language of the site, and its notice is sent here; the member's own confirmation is answered before the notice goes, which waits in the outbox
			if( ( $by === 'owner' ? self::_mail( $appData, $from, $id, 'changed', '', true ) : self::_notice( $appData, $from, $id ) ) === false )
				trigger_error( 'Members: the old address of an account could not be told that it was changed', E_USER_WARNING );

			$told = [ 'account' => [ 'id' => $id ] + self::_plain( $found ), 'old' => $from, 'by' => $by ];

			\Nino\Callbacks::doCallbacks( $appData, '/members/changed', $told );

			return $found;
		}

		/**
		 *	Sign an account in: the epoch is opened (written, when the first sign-in
		 *	of the project happens), the time of the sign-in is stamped on the
		 *	account, and the session gets the account and the epoch. The session id
		 *	and the csrf token are replaced first, now that the identity of the
		 *	session changes - the fixation defence \Nino\Auth::loginUser() makes, and
		 *	the same calls: startSession() answers false for a caller that has no
		 *	session to rotate (a test)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$account			(reference) The account; its 'login' is the time now
		 *
		 *	@return 	bool										False where the epoch could not be written - nobody is signed in then
		 */
		private static function _signIn( array &$appData, array &$account ): bool {

			$epoch = self::_openEpoch( $appData );

			if( $epoch === '' )
				return false;

			$now = gmdate( self::TIME );
			$id = $account['id'];

			// A time that could not be stored is a warning and no reason to turn
			// a member away who holds a good link
			$stamped = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, $now ): ?array {

				$accounts = self::_accountsOf( $state );

				if( isset( $accounts[$id] ) === false )
					return null;

				$accounts[$id]['login'] = $now;

				return [ 'accounts' => $accounts ];
			}, [] );

			if( $stamped === false )
				trigger_error( 'Members: the time of a sign-in could not be stored', E_USER_WARNING );
			else
				$account['login'] = $now;

			if( \Nino\Runtime::startSession( $appData ) === true )
				session_regenerate_id( true );

			\Nino\Csrf::rotateToken( $appData );

			// A change of an address that was asked for belongs to the one who asked: whoever signs in here starts without it
			\Nino\Runtime::unsetSessionValue( $appData, self::SESSION_CHANGE );

			$own = (string) ( self::_record( $appData, $id )['epoch'] ?? '' );

			\Nino\Runtime::setSessionValue( $appData, self::SESSION_ACCOUNT, [ 'id' => $id, 'epoch' => $epoch ] + ( $own !== '' ? [ 'own' => $own ] : [] ) );

			return true;
		}

		/**
		 *	The devices of an account that stay signed in, each read for what it has to be: the sha256 of its token (64 hex characters), the time it was made, the time
		 *	it is good until (an integer) and the user agent it was made from (a string) - and, for thirty seconds after a use, the sha256 of the token it replaced and the time of the replacement. An entry that is none is left out, and so is one that has run out
		 *
		 *	@param		mixed			$list					As the account's record holds it
		 *
		 *	@return 	array										A list of hash, created, expires and agent, the oldest first; at most REMEMBER_MAX
		 */
		private static function _devicesOf( mixed $list ): array {

			$devices = [];
			$now = time();

			foreach( is_array( $list ) === true ? $list : [] as $entry )
				if( is_array( $entry ) === true && is_string( $entry['hash'] ?? null ) === true && preg_match( '/^[0-9a-f]{64}$/D', $entry['hash'] ) === 1
					&& is_string( $entry['created'] ?? null ) === true && is_int( $entry['expires'] ?? null ) === true && $entry['expires'] > $now && is_string( $entry['agent'] ?? null ) === true )
					$devices[] = [ 'hash' => $entry['hash'], 'created' => $entry['created'], 'expires' => $entry['expires'], 'agent' => mb_substr( $entry['agent'], 0, self::AGENT_LENGTH, 'UTF-8' ) ]
						+ ( is_string( $entry['previous'] ?? null ) === true && preg_match( '/^[0-9a-f]{64}$/D', $entry['previous'] ) === 1 && is_int( $entry['rotated'] ?? null ) === true && $now - $entry['rotated'] <= self::REMEMBER_GRACE
							? [ 'previous' => $entry['previous'], 'rotated' => $entry['rotated'] ] : [] );

			return array_slice( $devices, -self::REMEMBER_MAX );
		}

		/**
		 *	The user agent of the request, as it is kept with a device: control characters out, at most AGENT_LENGTH characters
		 *
		 *	@return 	string
		 */
		private static function _agent(): string {

			$agent = is_string( $_SERVER['HTTP_USER_AGENT'] ?? null ) === true ? $_SERVER['HTTP_USER_AGENT'] : '';

			return mb_substr( preg_replace( '/[\x00-\x1f\x7f]/', '', mb_scrub( $agent, 'UTF-8' ) ) ?? '', 0, self::AGENT_LENGTH, 'UTF-8' );
		}

		/**
		 *	A token of 32 random bytes as base64url, 43 characters
		 *
		 *	@return 	string
		 */
		private static function _deviceToken(): string {

			return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
		}

		/**
		 *	The cookie of this request taken apart: the id of an account and a token, in the form _remember() writes them
		 *
		 *	@return 	array|null								[ id, token ]; null for no cookie, and for one that is not of that form
		 */
		private static function _deviceCookie(): ?array {

			$raw = is_string( $_COOKIE[self::REMEMBER] ?? null ) === true ? $_COOKIE[self::REMEMBER] : '';

			return preg_match( '/^([0-9a-f]{12})\.([A-Za-z0-9_-]{43})$/D', $raw, $found ) === 1 ? [ $found[1], $found[2] ] : null;
		}

		/**
		 *	Set the cookie of a device that stays signed in - or, with no seconds, delete it. HttpOnly, SameSite=Lax, Secure where the request is https (as the session cookie
		 *	is), for the whole site. The line is kept in the app data for the request ('./members/cookies') and sent unless the headers have gone: a header added, never one that
		 *	replaces another, since the session cookie is sent with it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$value				The id of the account, a dot and the token; '' for a cookie that is deleted
		 *	@param		int				$seconds			How long the browser keeps it; 0 deletes it
		 *
		 *	@return 	void
		 */
		private static function _cookie( array &$appData, string $value, int $seconds ): void {

			$secure = ( $appData['/nino/session/force-secure-cookie'] ?? false ) === true || ( ( $_SERVER['HTTPS'] ?? '' ) !== '' && ( $_SERVER['HTTPS'] ?? '' ) !== 'off' );
			$line = self::REMEMBER. '='. $value. '; Max-Age='. max( 0, $seconds ). '; Path=/'. ( $seconds <= 0 ? '; Expires=Thu, 01 Jan 1970 00:00:00 GMT' : '' ). '; HttpOnly'. ( $secure === true ? '; Secure' : '' ). '; SameSite=Lax';

			$appData['./members/cookies'] = array_merge( (array) ( $appData['./members/cookies'] ?? [] ), [ $line ] );

			if( headers_sent() === false )
				header( 'Set-Cookie: '. $line, false );
		}

		/**
		 *	Make the token of this device after a code that was given with the box ticked: 32 random bytes, of which the account keeps the sha256, the time, the end of its
		 *	days (the setting rememberDays) and the user agent - in the list remember, with the entries that have run out gone and the oldest one going when there are
		 *	more than ten. The token of this device from an earlier time, if the browser brings one, is replaced. The cookie is set; nothing is set where the account could not be written
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The account that is signed in
		 *
		 *	@return 	bool											Whether the cookie was set
		 */
		private static function _remember( array &$appData, string $id ): bool {

			$token = self::_deviceToken();
			$days = self::_rememberDays( $appData );
			$now = time();
			$agent = self::_agent();
			$stale = self::_deviceCookie();
			$staleHash = $stale !== null && $stale[0] === $id ? hash( 'sha256', $stale[1] ) : '';
			$found = false;

			$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, $token, $days, $now, $agent, $staleHash, &$found ): ?array {

				$accounts = self::_accountsOf( $state );

				if( isset( $accounts[$id] ) === false || $accounts[$id]['active'] === false )
					return null;

				$found = true;
				$devices = array_values( array_filter( $accounts[$id]['remember'] ?? [], static fn( array $entry ): bool => $staleHash === '' || hash_equals( $entry['hash'], $staleHash ) === false ) );
				$devices[] = [ 'hash' => hash( 'sha256', $token ), 'created' => gmdate( self::TIME, $now ), 'expires' => $now + $days * 86400, 'agent' => $agent ];
				$accounts[$id]['remember'] = array_slice( $devices, -self::REMEMBER_MAX );

				return [ 'accounts' => $accounts ];
			}, [] );

			if( $found === false || $written === false )
				return false;

			self::_cookie( $appData, $id. '.'. $token, $days * 86400 );

			return true;
		}

		/**
		 *	Sign in a visitor who has no session and brings the cookie of a device that stays signed in. Only the request for a page that is read (a GET, no endpoint of this
		 *	feature, no answer that is no html - the manifest and the icon of a portal look at nobody), once for a request, as the gate is. A good token - the account is
		 *	there and active, the sha256 of the token is in its list, compared with hash_equals, and not run out - signs the member in as a code does (_signIn(), the callback
		 *	'/members/login') and is replaced by a new one in the same write (the old one is dead from then on, the days start again) and in the cookie. A cookie that is no good - not of
		 *	the form, an account that is gone, a token the account does not hold, one that has run out - is deleted and counts as a try of its address, as a wrong code does; an
		 *	address that has brought REMEMBER_TRIES of them in an hour is no longer counted and no longer has its cookie deleted - the count never stands in the way of a good token. The token a use
		 *	has just replaced signs in for REMEMBER_GRACE seconds more, with no new token, no cookie and no write: two tabs that open at once bring the same cookie. No token and no cookie is ever written down
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request, its response already routed
		 *
		 *	@return 	void
		 */
		private static function _resume( array &$appData, array &$request, bool $link = false ): void {

			if( is_string( $_COOKIE[self::REMEMBER] ?? null ) === false || $_COOKIE[self::REMEMBER] === '' )
				return;

			$uri = (string) ( $request['/nino/http/request']['uri'] ?? '' );

			if( ( $request['/nino/http/request']['rawMethod'] ?? '' ) !== 'GET' || ( $link === false && str_starts_with( $uri, '/.members' ) === true ) || str_starts_with( $uri, '/_' ) === true
				|| \Nino\Http::answersHtml( (array) ( $request['/nino/http/response'] ?? [] ) ) === false || self::current( $appData ) !== null )
				return;

			$who = self::REMEMBER_COUNTS. \Nino\Http::clientBucket( \Nino\Http::getClientIp( $appData ) );
			$device = self::_deviceCookie();
			$next = self::_deviceToken();
			$id = $device === null ? '' : $device[0];
			$hash = $device === null ? '' : hash( 'sha256', $device[1] );
			$days = self::_rememberDays( $appData );
			$now = time();
			$agent = self::_agent();
			$rotated = false;
			$grace = false;
			$written = false;

			if( $device !== null )
				$written = \Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, $hash, $next, $days, $now, $agent, &$rotated, &$grace ): ?array {

					$accounts = self::_accountsOf( $state );

					if( isset( $accounts[$id] ) === false || $accounts[$id]['active'] === false )
						return null;

					// Every entry is compared, whichever one matches: the time it takes does not say which place it was
					$at = null;
					$before = false;

					foreach( $accounts[$id]['remember'] ?? [] as $place => $entry ) {

						if( hash_equals( $entry['hash'], $hash ) === true )
							$at = $place;

						if( hash_equals( $entry['previous'] ?? str_repeat( '0', 64 ), $hash ) === true && isset( $entry['previous'] ) === true )
							$before = true;
					}

					if( $at === null ) {

						// The token a use has just replaced: a second request that brought it - two tabs that opened at once - is signed in as well, and changes nothing
						$grace = $before;

						return null;
					}

					$accounts[$id]['remember'][$at] = [ 'hash' => hash( 'sha256', $next ), 'created' => $accounts[$id]['remember'][$at]['created'], 'expires' => $now + $days * 86400, 'agent' => $agent, 'previous' => $hash, 'rotated' => $now ];
					$rotated = true;

					return [ 'accounts' => $accounts ];
				}, [] );

			if( $grace === false && ( $rotated === false || $written === false ) ) {

				// Not a way in: the cookie goes and the address has tried once - unless it has tried twenty times, when the count is left alone. The count never stands in the way of a good token
				if( \Nino\Feature::spent( $appData, self::COUNTER, $who, self::REMEMBER_TRIES ) === false ) {
					self::_cookie( $appData, '', 0 );
					\Nino\Feature::count( $appData, self::COUNTER, $who, self::REMEMBER_TRIES, self::WINDOW );
				}

				return;
			}

			if( $rotated === true )
				self::_cookie( $appData, $id. '.'. $next, $days * 86400 );

			$account = self::account( $appData, $id );

			if( $account === null || self::_signIn( $appData, $account ) === false )
				return;

			\Nino\Callbacks::doCallbacks( $appData, '/members/login', $account );
		}

		/**
		 *	Take the token of this device out of its account - the one the cookie of the request names, where the account holds it - and delete the cookie. Whoever is
		 *	signed in or not: the browser that signs out has no cookie left, and a token that is out of the list signs nobody in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		private static function _dropDevice( array &$appData ): void {

			if( is_string( $_COOKIE[self::REMEMBER] ?? null ) === false || $_COOKIE[self::REMEMBER] === '' )
				return;

			$device = self::_deviceCookie();

			if( $device !== null ) {

				$id = $device[0];
				$hash = hash( 'sha256', $device[1] );

				\Nino\Filesystem::mutate( $appData, self::ACCOUNTS, static function( mixed $state ) use ( $id, $hash ): ?array {

					$accounts = self::_accountsOf( $state );
					$kept = array_values( array_filter( $accounts[$id]['remember'] ?? [], static fn( array $entry ): bool => hash_equals( $entry['hash'], $hash ) === false && hash_equals( $entry['previous'] ?? $entry['hash'], $hash ) === false ) );

					if( isset( $accounts[$id] ) === false || $kept === ( $accounts[$id]['remember'] ?? [] ) )
						return null;

					if( $kept === [] )
						unset( $accounts[$id]['remember'] );
					else
						$accounts[$id]['remember'] = $kept;

					return [ 'accounts' => $accounts ];
				}, [] );
			}

			self::_cookie( $appData, '', 0 );
		}

		/**
		 *	Make a token for an account and keep its hash: 32 random bytes, base64url,
		 *	which is mailed once and never stored
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The account
		 *	@param		string		$kind					One of LINKS
		 *	@param		int				$seconds			How long it is good for
		 *	@param		string		$target				The path the member asked for, '' for none
		 *
		 *	@return 	string|null								The token; null where it could not be stored
		 */
		private static function _issue( array &$appData, string $id, string $kind, int $seconds, string $target ): ?string {

			$token = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );

			return self::_keep( $appData, hash( 'sha256', $token ), $id, $kind, time() + $seconds, $target ) === true ? $token : null;
		}

		/**
		 *	Keep the record of a token or of a code under its hash. The entries that
		 *	have run out go in the same write
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$hash					The sha256 of the token, or of the nonce, a bar and the code
		 *	@param		string		$id						The account
		 *	@param		string		$kind					One of KINDS
		 *	@param		int				$expires			The time it is good until
		 *	@param		string		$target				The path the member asked for, '' for none
		 *	@param		string		$email				For the code of a change: the new address, which the record keeps; '' for any other
		 *	@param		string		$retire				The hash of the earlier code of the session, which goes in this write; '' for none
		 *
		 *	@return 	bool											Whether it was written
		 *
		 *	The file of the codes is written ONCE, whatever the record is for, and what a write leaves in it does not tell an address with an account from one with none:
		 *	1. The earlier code of the session ($retire) is taken out first, in the same write that adds the new record - one write whether there is one to take or not, so that
		 *	   how long a record of an earlier request lived (a record of nobody is thinned away, a record of an account is not) shows in no write.
		 *	2. The records of codes that are left are counted ALL, the ones of accounts and the ones of nobody, and as many of the records of nobody are dropped, the oldest first,
		 *	   as it takes to leave CODE_MAX - 1 of them; the new record, of a member or of nobody, makes it CODE_MAX. A file that holds its most is as large after a request for an
		 *	   account as after one for nobody, whatever the earlier request of the session was.
		 *	3. A record of an account is never dropped - the sign-in of a member who asked is never undone by what strangers ask. Where there are more live records of accounts than
		 *	   CODE_MAX - 1 (each of them is a request of a visitor who named a real address in the last fifteen minutes, and an address takes three an hour) every record of nobody
		 *	   goes with each write, the records of accounts stay, and the file holds one record more with every request, whoever it is for, until the first of them runs out: it is
		 *	   not bounded by CODE_MAX then, and a request that retires the record of an account leaves the file one record smaller than one that retires none. That is the one
		 *	   state in which the size of the file tells the two apart, and it takes live codes for more than CODE_MAX - 1 real accounts at once to bring it about
		 */
		private static function _keep( array &$appData, string $hash, string $id, string $kind, int $expires, string $target, string $email = '', string $retire = '' ): bool {

			$now = time();

			// The accounts are read for both kinds of record, to know which records are nobody's
			$accounts = $kind === 'code' ? self::_accountsOf( \Nino\Filesystem::getFileContent( $appData, self::ACCOUNTS, [] ) ) : [];

			return \Nino\Filesystem::mutate( $appData, self::TOKENS, static function( mixed $state ) use ( $hash, $id, $kind, $expires, $target, $email, $retire, $now, $accounts ): array {

				$tokens = self::_liveTokens( $state, $now );

				if( $retire !== '' && ( $tokens[$retire]['kind'] ?? '' ) === $kind )
					unset( $tokens[$retire] );

				if( $kind === 'code' ) {

					$codes = array_filter( $tokens, static fn( array $entry ): bool => $entry['kind'] === 'code' );
					$nobodys = array_filter( $codes, static fn( array $entry ): bool => isset( $accounts[ $entry['account'] ] ) === false );
					uasort( $nobodys, static fn( array $a, array $b ): int => $a['expires'] <=> $b['expires'] );

					foreach( array_slice( array_keys( $nobodys ), 0, max( 0, count( $codes ) - ( self::CODE_MAX - 1 ) ) ) as $old )
						unset( $tokens[$old] );
				}

				$tokens[$hash] = [ 'account' => $id, 'kind' => $kind, 'expires' => $expires, 'target' => $target ] + ( $kind === 'change' ? [ 'email' => $email ] : [] );

				return $tokens;
			}, [] );
		}

		/**
		 *	Delete every link and code an account has out, of every kind - what a new
		 *	address ends: the old address let them in. Nothing is written where it has none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The account
		 *
		 *	@return 	bool											False where the file could not be written
		 */
		private static function _forget( array &$appData, string $id ): bool {

			$ran = false;
			$dirty = false;

			$written = \Nino\Filesystem::mutate( $appData, self::TOKENS, static function( mixed $state ) use ( $id, &$ran, &$dirty ): ?array {

				$ran = true;
				$kept = array_filter( self::_liveTokens( $state, time() ), static fn( array $entry ): bool => $entry['account'] !== $id );

				if( $kept === ( is_array( $state ) === true ? $state : [] ) )
					return null;

				$dirty = true;

				return $kept;
			}, [] );

			return $ran === true && ( $dirty === false || $written === true );
		}

		/**
		 *	Make a code, keep it under the hash of the session's nonce and the code - nothing else of it is stored - and put its mail in the outbox of the kernel
		 *	(\Nino\Mail::queue()), which sends it after the answer or with a later request, whatever becomes of this one. Six digits, filled up with zeros, from random_int(),
		 *	for every address alike: a request for an address that has an account and one for an address that has none do the same work, the mail of the same template - in
		 *	the language of the request for both, since a callback of '/members/language' knows accounts and would tell them apart - to the address as it was typed, a record of
		 *	the code, and the same call of the outbox under a key. The only difference is the kind of the mail: \Nino\Mail::CLIENT where the code goes to somebody,
		 *	\Nino\Mail::VOID - a mail to nobody, which the outbox takes the whole way and lets go without a transport - where it does not. The record of a code that goes to nobody
		 *	names an id that no account has - twelve random hex digits, as long as an id is, so that the file is as large as the one of a member's code - and what the record is
		 *	spent for is 'gone', as it is for an account that was deleted since.
		 *
		 *	The key is made of the session, the address, the nonce of the request and the time slot of the kernel's memory for keys (MailQueue::KEY_MEMORY): within a slot a
		 *	second request is the mail of the first - the outbox answers 'conflict' for a mail that waits or went out lately, for both kinds alike - and in the next slot it is
		 *	a mail of its own, for both kinds alike, since the kernel has forgotten the key of a mail to nobody by then and a mail that waits for its transport would still hold
		 *	it. Under one nonce there is only ever ONE live code: the record of an earlier code of this session is taken back in the very write that keeps the new one - one write of the file for every
		 *	address, whether the record of the earlier code is still there or not (see _keep()) -, and the new one is taken back when the outbox takes no mail for it, so that a second code
		 *	never doubles the chance of a guess. A request the outbox refuses leaves the session with no live code, the earlier one included: it asks again.
		 *
		 *	A second request of the session in the slot of a request that was queued makes nothing and asks the outbox for nothing, for every address (the slot and the nonce are kept in the
		 *	session): a double click across the end of a slot is the one case that queues twice, the same for both kinds, and the code of the first mail is then no code (one is live). Before
		 *	anything is done the outbox is looked at (see _braked()): where it holds the share, nothing is kept and nothing is queued, for every address alike. What the outbox answers changes nothing the visitor can see: 'conflict', 'full', 'ratelimited', 'unwritable' and the rest end the same. The log says why, in a
		 *	line that names no address and at most once an hour for each reason - except for 'conflict', which a double click of the visitor causes and is routine
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$kind					'code' (to sign in) or 'change' (a code to the new address of an account)
		 *	@param		string		$holder				The account the record of the code names: the member's, or a random id where nobody is
		 *	@param		bool			$real					Whether the code goes to somebody: the address has an account (or, for a change, is free)
		 *	@param		string		$email				The address as it was typed, in lower case
		 *	@param		string		$nonce				32 hex characters of the request the session holds
		 *	@param		int				$until				The time the code is good until
		 *	@param		string		$target				The path the member asked for, '' for none - where the sign-in leads
		 *
		 *	@return 	void
		 */
		private static function _issueCode( array &$appData, string $kind, string $holder, bool $real, string $email, string $nonce, int $until, string $target ): void {

			// A second request in the slot of a request that was queued is the mail of that one, for every address: nothing is made and the outbox is not asked
			$slot = [ $nonce, intdiv( \Nino\MailQueue::now( $appData ), \Nino\MailQueue::KEY_MEMORY ) ];

			if( \Nino\Runtime::getSessionValue( $appData, './members/slot-'. $kind ) === $slot )
				return;

			if( self::_braked( $appData ) === true )
				return;

			$code = sprintf( '%06d', random_int( 0, 999999 ) );
			$hash = hash( 'sha256', $nonce. '|'. $code );

			// The earlier code of this session goes in the write that keeps the new one: a new request is a new code, and only one is live. One write for every address, whether there
			// is an earlier code or not and whether its record is still in the file or was thinned away
			$held = './members/held-'. $kind;
			$before = \Nino\Runtime::getSessionValue( $appData, $held );

			if( self::_keep( $appData, $hash, $holder, $kind, $until, $target, $kind === 'change' ? $email : '', is_string( $before ) === true ? $before : '' ) === false ) {
				trigger_error( 'Members: a code could not be stored', E_USER_WARNING );
				return;
			}

			// The code of the language of the request: the same for every address, whoever the hook of the language would name for an account
			[ $subject, $body, $replyTo ] = self::_compose( $appData, '', $kind, $code, false, '' );

			$queued = \Nino\Mail::queue( $appData, [ 'to' => $email, 'subject' => $subject, 'body' => $body, 'replyTo' => $replyTo ], $real === true ? \Nino\Mail::CLIENT : \Nino\Mail::VOID,
				'members/'. $kind. '-'. hash( 'sha256', \Nino\Csrf::getToken( $appData ). '|'. $email. '|'. $nonce. '|'. intdiv( \Nino\MailQueue::now( $appData ), \Nino\MailQueue::KEY_MEMORY ) ) );

			if( $queued !== '' ) {
				\Nino\Runtime::setSessionValue( $appData, $held, $hash );
				\Nino\Runtime::setSessionValue( $appData, './members/slot-'. $kind, $slot );

				return;
			}

			// A code that no mail carries is good for nobody: it goes, for every address alike
			self::_spendHash( $appData, $hash );

			$why = \Nino\Mail::queueRefused( $appData );

			if( $why !== 'conflict' && \Nino\Feature::count( $appData, self::COUNTER, 'said-'. $why, 1, self::WINDOW ) === true )
				trigger_error( 'Members: no mail queued ('. $why. ')', E_USER_WARNING );
		}

		/**
		 *	Whether the requests for a code hold their share of the outbox: as many mails wait in it as half of the places for visitors. It goes by what waits and not by how
		 *	many requests came, so that nothing is held back while the mails are delivered - they wait only a moment - and where the delivery stalls (the server drops the work after the answer,
		 *	the transport is down) the requests hold at most that share and the mails of the other features keep their places. The same read for every request that gets as far as a code,
		 *	before any address is looked at, and a mail to nobody waits like a mail of a member until it is let go, so both kinds see the same state and decide the same - no oracle. Where the brake
		 *	holds nothing is kept and nothing is queued, but the steps that change nothing are taken all the same (the lock and the read of the file of the codes, a read of the outbox, the mail
		 *	made from the template), so that the time before the answer says as little as can be said without a wait; one line without an address says it once an hour. The file of the
		 *	codes is bounded apart from this: CODE_MAX live records in all, of which only the ones for addresses with no account are ever dropped, and for the accounts the cap of three requests an hour for an address (see _keep())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		private static function _braked( array &$appData ): bool {

			if( \Nino\Mail::queued( $appData )['waiting'] < self::siteShare() )
				return false;

			// The steps of a request that is let through, taken with nothing changed: the accounts are read, the file of the codes is read and written back as it is
			self::_accountsOf( \Nino\Filesystem::getFileContent( $appData, self::ACCOUNTS, [] ) );
			\Nino\Filesystem::mutate( $appData, self::TOKENS, static fn( mixed $state ): array => is_array( $state ) === true ? $state : [], [] );
			\Nino\MailQueue::peek( $appData, \Nino\Mail::CLIENT, '' );
			self::_compose( $appData, '', 'code', '000000', false, '' );

			if( \Nino\Feature::count( $appData, self::COUNTER, 'said-braked', 1, self::WINDOW ) === true )
				trigger_error( 'Members: requests for a code are held back, mails wait in the outbox', E_USER_WARNING );

			return true;
		}

		/**
		 *	How many mails of codes may wait in the outbox at once: the share of the places of the outbox that are for visitors
		 *
		 *	@return 	int
		 */
		public static function siteShare(): int {

			return intdiv( \Nino\MailQueue::MAX_CLIENT, self::SITE_DIVISOR );
		}

		/**
		 *	What the panel tells about the sign-in: whether the share of the site is used up for this hour, and the cap of mails of the kernel for a visitor where the setting for requests
		 *	is above it (that cap acts first, and is shared with the other features)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										braked (bool), cap (the kernel's cap where it is lower than the requests the setting allows, 0 for no such case)
		 */
		public static function standing( array &$appData ): array {

			$cap = (int) ( $appData['/nino/mail/cap'] ?? \Nino\AppData::DEFAULTS['/nino/mail/cap'] );

			return [ 'braked' => \Nino\Mail::queued( $appData )['waiting'] >= self::siteShare(), 'cap' => $cap > 0 && self::_attempts( $appData ) > $cap ? $cap : 0 ];
		}

		/**
		 *	The change of an address the session holds, as long as it is live: well formed - an
		 *	account, the new address, a nonce of 32 hex characters, the tries used and the time it
		 *	is good until - not run out and with a try left
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								account, email, nonce, tries and until - null for none, or one that is over
		 */
		private static function _changePending( array &$appData ): ?array {

			$pending = \Nino\Runtime::getSessionValue( $appData, self::SESSION_CHANGE );

			if( is_array( $pending ) === false || is_string( $pending['account'] ?? null ) === false || preg_match( '/^[0-9a-f]{12}$/D', $pending['account'] ) !== 1
				|| is_string( $pending['email'] ?? null ) === false || self::_email( $pending['email'] ) !== $pending['email'] || is_string( $pending['nonce'] ?? null ) === false
				|| preg_match( '/^[0-9a-f]{32}$/D', $pending['nonce'] ) !== 1 || is_int( $pending['tries'] ?? null ) === false || is_int( $pending['until'] ?? null ) === false )
				return null;

			if( $pending['until'] <= time() || $pending['tries'] >= self::CODE_TRIES )
				return null;

			return [ 'account' => $pending['account'], 'email' => $pending['email'], 'nonce' => $pending['nonce'], 'tries' => $pending['tries'], 'until' => $pending['until'] ];
		}

		/**
		 *	The request for a code the session holds, as long as it is live: well formed
		 *	- an address, a nonce of 32 hex characters, the tries used and the time it
		 *	is good until - not run out and with a try left
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								email, nonce, tries and until - null for none, or one that is over
		 */
		private static function _pending( array &$appData ): ?array {

			$pending = \Nino\Runtime::getSessionValue( $appData, self::SESSION_PENDING );

			if( is_array( $pending ) === false || is_string( $pending['email'] ?? null ) === false || is_string( $pending['nonce'] ?? null ) === false
				|| preg_match( '/^[0-9a-f]{32}$/D', $pending['nonce'] ) !== 1 || is_int( $pending['tries'] ?? null ) === false || is_int( $pending['until'] ?? null ) === false )
				return null;

			if( $pending['until'] <= time() || $pending['tries'] >= self::CODE_TRIES )
				return null;

			return [ 'email' => $pending['email'], 'nonce' => $pending['nonce'], 'tries' => $pending['tries'], 'until' => $pending['until'] ];
		}

		/**
		 *	Look at a token without using it up: the record of a token that is good, as
		 *	_spend() would hand it over, and nothing written - a page that only asks
		 *	whether a link leads in must not be the one that spends it. An unknown,
		 *	expired or malformed one is nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token
		 *
		 *	@return 	array|null								account (the id), kind, expires and target - null for no token
		 */
		private static function _find( array &$appData, string $token ): ?array {

			if( preg_match( '/^[A-Za-z0-9_-]{43}$/D', $token ) !== 1 )
				return null;

			$now = time();
			$entry = self::_liveTokens( \Nino\Filesystem::getFileContent( $appData, self::TOKENS, [] ), $now )[ hash( 'sha256', $token ) ] ?? null;

			return $entry !== null && $entry['expires'] > $now ? $entry : null;
		}

		/**
		 *	Spend a token: delete it, and answer what it was for. An unknown, expired
		 *	or malformed one is nothing, and so is one whose deletion could not be
		 *	written - a link that is not spent is a link that can be used twice
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token
		 *
		 *	@return 	array|null								account (the id), kind and target - null for no token
		 */
		private static function _spend( array &$appData, string $token ): ?array {

			if( preg_match( '/^[A-Za-z0-9_-]{43}$/D', $token ) !== 1 )
				return null;

			return self::_spendHash( $appData, hash( 'sha256', $token ) );
		}

		/**
		 *	Spend a record by its hash: delete it, and answer what it was for. An
		 *	unknown or expired one is nothing, and so is one whose deletion could not
		 *	be written. The entries that have run out go in the same write
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$hash					The sha256 of a token, or of a nonce, a bar and a code
		 *	@param		string		$kind					The kind the record has to be of, '' for any: a record of another kind is nothing, and stays
		 *
		 *	@return 	array|null								account (the id), kind, expires, target and - for a change - email; null for no record
		 */
		private static function _spendHash( array &$appData, string $hash, string $kind = '' ): ?array {

			$now = time();
			$found = null;

			$written = \Nino\Filesystem::mutate( $appData, self::TOKENS, static function( mixed $state ) use ( $hash, $kind, $now, &$found ): ?array {

				$tokens = is_array( $state ) === true ? $state : [];
				$live = self::_liveTokens( $state, $now );
				$entry = $live[$hash] ?? null;

				if( $entry !== null && $entry['expires'] > $now && ( $kind === '' || $entry['kind'] === $kind ) ) {
					$found = $entry;
					unset( $live[$hash] );
				}

				return $live === $tokens ? null : $live;
			}, [] );

			return $written === true && $found !== null ? $found : null;
		}

		/**
		 *	The tokens that are kept: an entry that is well formed - an account, a
		 *	kind of KINDS, a target that is a string, and for a change the new address -
		 *	and has not run out; the record of a link that ran out stays for LINK_KEEP
		 *	seconds, for _expiredTarget() alone: what asks for a token that is good looks at
		 *	'expires' itself (_find(), _spendHash())
		 *
		 *	@param		mixed			$state				As the file holds it
		 *	@param		int				$now
		 *
		 *	@return 	array										hash => account, kind, expires, target - and email, for a change
		 */
		private static function _liveTokens( mixed $state, int $now ): array {

			$live = [];

			foreach( is_array( $state ) === true ? $state : [] as $hash => $entry )
				if( is_array( $entry ) === true && is_string( $entry['account'] ?? null ) === true && in_array( $entry['kind'] ?? null, self::KINDS, true ) === true
					&& is_int( $entry['expires'] ?? null ) === true && $entry['expires'] > $now - ( in_array( $entry['kind'], self::LINKS, true ) === true ? self::LINK_KEEP : 0 ) && is_string( $entry['target'] ?? null ) === true
					&& ( $entry['kind'] !== 'change' || is_string( $entry['email'] ?? null ) === true ) )
					$live[(string) $hash] = [ 'account' => $entry['account'], 'kind' => $entry['kind'], 'expires' => $entry['expires'], 'target' => $entry['target'] ] + ( $entry['kind'] === 'change' ? [ 'email' => $entry['email'] ] : [] );

			return $live;
		}

		/**
		 *	Where a link led that has run out: the path its record carries, and nothing for a token that is not well formed, one the file does not know - a
		 *	record is kept for LINK_KEEP seconds after its end - or one that is still good. Only what the server stored with the token is answered, never
		 *	a value of the request
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token
		 *
		 *	@return 	string										A path _isPath() accepts, '' for none
		 */
		private static function _expiredTarget( array &$appData, string $token ): string {

			if( preg_match( '/^[A-Za-z0-9_-]{43}$/D', $token ) !== 1 )
				return '';

			$now = time();
			$entry = self::_liveTokens( \Nino\Filesystem::getFileContent( $appData, self::TOKENS, [] ), $now )[ hash( 'sha256', $token ) ] ?? null;

			return $entry !== null && in_array( $entry['kind'], self::LINKS, true ) === true && $entry['expires'] <= $now && strlen( $entry['target'] ) <= self::TARGET_MAX && self::_isPath( $entry['target'] ) === true ? $entry['target'] : '';
		}

		/**
		 *	Send a link or the notice of a change in the request, before the answer: the mails the owner asks for in the panel, which says whether they went, and the ones that
		 *	carry a link - a link signs in, so it is never kept in the outbox, a file that waits - are sent here. What is made of is in _compose()
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$to
		 *	@param		string		$id						The account the mail is for: its language, where mailLocale() knows one, is the one the mail is written in
		 *	@param		string		$kind					'login', 'invite', 'send' or 'changed'
		 *	@param		string		$token				The token of a link - nothing for a notice
		 *	@param		bool			$native				Write in the site's native language
		 *	@param		string		$message			The message of the owner, for the mail of a link whose template has the slot [[message]] ('invite' and 'send')
		 *
		 *	@return 	bool										Whether the mail was delivered
		 */
		private static function _mail( array &$appData, string $to, string $id, string $kind, string $token, bool $native, string $message = '' ): bool {

			[ $subject, $body, $replyTo ] = self::_compose( $appData, $id, $kind, $token, $native, $message );

			return \Nino\Mail::send( $appData, $to, $subject, $body, $replyTo );
		}

		/**
		 *	The notice to the old address of an account that its address was changed, by the member's own confirmation, put in the outbox of the kernel: the answer to the member
		 *	does not wait for a transport, and a notice the transport does not take is tried again. It names neither the new address nor a code, so what waits in the outbox is
		 *	no secret. What the outbox refuses is a warning for the log that names no address
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$to						The old address
		 *	@param		string		$id						The account
		 *
		 *	@return 	bool										Whether the outbox took the mail
		 */
		private static function _notice( array &$appData, string $to, string $id ): bool {

			[ $subject, $body, $replyTo ] = self::_compose( $appData, $id, 'changed', '', false, '' );

			if( \Nino\Mail::queue( $appData, [ 'to' => $to, 'subject' => $subject, 'body' => $body, 'replyTo' => $replyTo ], \Nino\Mail::CLIENT ) !== '' )
				return true;

			trigger_error( 'Members: no mail queued ('. \Nino\Mail::queueRefused( $appData ). ')', E_USER_WARNING );

			return false;
		}

		/**
		 *	The mail of a kind, finished: its subject and its body from the templates of the unit, in the language it is written in, and the address the reply goes to. The template is
		 *	rendered first - its words are the keys of the unit - and the link or the code, the validity, the day and the contact address of the
		 *	project are put in afterwards, in the result, so that nothing in them is ever read as a fill or
		 *	a shortcode (the order \Nino\Form::render() keeps). A code is set as two
		 *	groups of three digits. The reply goes to the project's contact address
		 *	where it has one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id						The account the mail is for: its language, where mailLocale() knows one, is the one the mail is written in - '' for none, and then the hook is not asked: the mails of a code ('code', 'change')
		 *															are written in the language of the request for every address alike, since the hook knows accounts and would tell an address that has one from one that has none; the mails that tell nothing of that
		 *															(the links and the notice, which go to the account the owner or the signed-in member named) are written in the language of the account
		 *	@param		string		$kind					'login', 'invite', 'send', 'code', 'change' (a code, to the new address) or 'changed' (the notice to the old address that it was changed, which names neither the new one nor a code)
		 *	@param		string		$token				The token of a link, the six digits of a code - nothing for a notice
		 *	@param		bool			$native				Write in the site's native language
		 *	@param		string		$message			The message of the owner, for the mail of a link whose template has the slot [[message]] ('invite' and 'send'); it is put in the place of the slot when everything else is in, so that nothing of it is read again
		 *
		 *	@return 	array										[ subject, body, replyTo ]
		 */
		private static function _compose( array &$appData, string $id, string $kind, string $token, bool $native, string $message ): array {

			$url = in_array( $kind, self::LINKS, true ) === true ? \Nino\Html::absoluteUrl( $appData, '/.members/login?t='. rawurlencode( $token ) ) : '';
			$code = in_array( $kind, [ 'code', 'change' ], true ) === true ? substr( $token, 0, 3 ). ' '. substr( $token, 3 ) : '';
			$locale = \Nino\Locales::getCurrentLocale( $appData );
			$message = self::_messageText( $message );
			$paragraph = '';
			$slot = bin2hex( random_bytes( 8 ) );
			$text = bin2hex( random_bytes( 8 ) );

			// The language of the account, where one is known, wins over the site's native one and over the request's
			$written = $id === '' ? '' : self::mailLocale( $appData, $id );
			$written = $written !== '' ? $written : ( $native === true ? \Nino\Locales::getNativeLocale( $appData ) : '' );

			if( $written !== '' )
				\Nino\Locales::useLocale( $appData, $written );

			try {
				$date = str_starts_with( \Nino\Locales::getCurrentLocale( $appData ), 'de' ) === true ? date( 'd.m.Y' ) : date( 'Y-m-d' );
				$body = \Nino\Html::renderHtml( $appData, str_replace( '[[message]]', $slot, \Nino\Feature::template( $appData, self::TEMPLATES, 'mail-'. $kind ) ) );
				$subject = \Nino\Html::renderHtml( $appData, '[[/feature/members/mail/'. $kind. '-subject]]' );
				$paragraph = $message === '' ? '' : \Nino\Html::renderHtml( $appData, str_replace( '[[message-text]]', $text, \Nino\Feature::template( $appData, self::TEMPLATES, 'mail-message' ) ) );
				$replyTo = trim( (string) \Nino\Html::resolveTextfill( $appData, '/project/company/contact/email' ) );
			}
			finally {
				if( $written !== '' )
					\Nino\Locales::useLocale( $appData, $locale );
			}

			$replyTo = filter_var( $replyTo, FILTER_VALIDATE_EMAIL ) === false ? '' : $replyTo;
			$body = str_replace(
				[ '[[link]]', '[[code]]', '[[minutes]]', '[[days]]', '[[date]]', '[[contact]]' ],
				[ \Nino\Components::escape( $url ), \Nino\Components::escape( $code ), (string) self::_minutes( $appData ), (string) self::_days( $appData ), \Nino\Components::escape( $date ), \Nino\Components::escape( $replyTo ) ],
				$body
			);

			// The slot and the slot of the paragraph are random marks in the template text before it is rendered, so that a fill that spells them out is no slot. The message comes last and is escaped before its line breaks become breaks: nothing in it is a slot, a fill or a shortcode, and none of the steps above reads it. The slot stands at the start of the line of the paragraph after it,
			// so that the mail without a message is the mail the template has without the slot
			$paragraph = $message === '' ? '' : trim( str_replace( $text, nl2br( \Nino\Components::escape( $message ), false ), $paragraph ) ). "\n\t";
			$body = str_replace( $slot, $paragraph, $body );

			return [ $subject, $body, $replyTo ];
		}

		/**
		 *	The message of the owner as the mail writes it: its line breaks as "\n" (the separator of a line or of a paragraph is one), the other control characters and the marks that turn the text around
		 *	left out, trimmed. A text that is no UTF-8 is none
		 *
		 *	@param		string		$message
		 *
		 *	@return 	string
		 */
		private static function _messageText( string $message ): string {

			$text = str_replace( [ "\r\n", "\r", "\u{2028}", "\u{2029}" ], "\n", $message );

			return trim( (string) preg_replace( '/[^\P{Cc}\n]|[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $text ) );
		}

		/**
		 *	The session epoch the sign-ins are stamped with: the string signOutAll()
		 *	wrote or _openEpoch() opened, '' while there is none - nobody has signed
		 *	in then, and a file that holds anything else counts as none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _epoch( array &$appData ): string {

			return self::_epochOf( \Nino\Filesystem::getFileContent( $appData, self::EPOCH, [] ) );
		}

		/**
		 *	The epoch to sign in under: the current one, or - for the first sign-in
		 *	of the project - a new one, written in the same lock that found there
		 *	was none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										'' where it could not be written
		 */
		private static function _openEpoch( array &$appData ): string {

			$epoch = '';
			$created = false;

			$written = \Nino\Filesystem::mutate( $appData, self::EPOCH, static function( mixed $state ) use ( &$epoch, &$created ): ?array {

				$epoch = self::_epochOf( $state );

				if( $epoch !== '' )
					return null;

				$epoch = bin2hex( random_bytes( 16 ) );
				$created = true;

				return [ 'epoch' => $epoch ];
			}, [] );

			return $created === true && $written === false ? '' : $epoch;
		}

		/**
		 *	@param		mixed			$state				As the file holds it
		 *
		 *	@return 	string										The epoch: 32 hex characters - '' for anything else
		 */
		private static function _epochOf( mixed $state ): string {

			$epoch = is_array( $state ) === true ? ( $state['epoch'] ?? null ) : null;

			return is_string( $epoch ) === true && preg_match( '/^[0-9a-f]{32}$/D', $epoch ) === 1 ? $epoch : '';
		}

		/**
		 *	The accounts a file holds, each read for what it has to be: the id is 12
		 *	hex characters, the address lower case, the strings strings, 'active' a
		 *	bool and the time of the last sign-in a string or null, and the epoch - 32 hex
		 *	characters - there where the account has one, and the list remember (see _devicesOf()) there where the record has the key, an empty list as it
		 *	stands: a write of another account's change leaves the bytes of this one as the file had them. A record that is none is left out - the file is
		 *	plain php and editable
		 *
		 *	@param		mixed			$state				As the file holds it
		 *
		 *	@return 	array										id => email, name, group, active, created, login - and epoch and remember, where there are some
		 */
		private static function _accountsOf( mixed $state ): array {

			$text = static fn( mixed $value ): string => is_string( $value ) === true ? $value : '';
			$accounts = [];

			foreach( is_array( $state ) === true && is_array( $state['accounts'] ?? null ) === true ? $state['accounts'] : [] as $id => $record ) {

				$id = (string) $id;

				if( preg_match( '/^[0-9a-f]{12}$/D', $id ) !== 1 || is_array( $record ) === false )
					continue;

				$accounts[$id] = [
					'email'		=> mb_strtolower( $text( $record['email'] ?? null ), 'UTF-8' ),
					'name'		=> $text( $record['name'] ?? null ),
					'group'		=> $text( $record['group'] ?? null ),
					'active'	=> ( $record['active'] ?? false ) === true,
					'created'	=> $text( $record['created'] ?? null ),
					'login'		=> is_string( $record['login'] ?? null ) === true ? $record['login'] : null,
				] + ( self::_epochOf( $record ) !== '' ? [ 'epoch' => self::_epochOf( $record ) ] : [] ) + ( is_array( $record['remember'] ?? null ) === true ? [ 'remember' => self::_devicesOf( $record['remember'] ) ] : [] );
			}

			return $accounts;
		}

		/**
		 *	One account as the file has it, its epoch among the fields
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$id
		 *
		 *	@return 	array|null								email, name, group, active, created, login and - where there are some - epoch and remember; null for an id nobody has
		 */
		private static function _record( array &$appData, string $id ): ?array {

			return self::_accountsOf( \Nino\Filesystem::getFileContent( $appData, self::ACCOUNTS, [] ) )[$id] ?? null;
		}

		/**
		 *	An account without its epoch and its devices: what the account is to everybody outside this class
		 *
		 *	@param		array 		$record				As _record() has it
		 *
		 *	@return 	array
		 */
		private static function _plain( array $record ): array {

			unset( $record['epoch'], $record['remember'] );

			return $record;
		}

		/**
		 *	Tell the features that build on this one that accounts are gone: '/members/removed' is fired once for each, after the
		 *	file was written, with the id, the address and the group the account had, in the order they were deleted
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$gone					[ [ id, email, group ], ... ]
		 *
		 *	@return 	void
		 */
		private static function _removed( array &$appData, array $gone ): void {

			foreach( $gone as $account ) {

				$told = [ 'id' => (string) $account['id'], 'email' => (string) $account['email'], 'group' => (string) $account['group'] ];

				\Nino\Callbacks::doCallbacks( $appData, '/members/removed', $told );
			}
		}

		/**
		 *	Whether an account is the member who is signed in in this session
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$account			As current() has it
		 *
		 *	@return 	bool
		 */
		private static function _own( array &$appData, array $account ): bool {

			$member = self::current( $appData );

			return $member !== null && is_string( $account['id'] ?? null ) === true && $member['id'] === $account['id'];
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Lower case
		 *
		 *	@return 	array|null								The account with that address, as account() has it
		 */
		private static function _byEmail( array &$appData, string $email ): ?array {

			foreach( self::_accountsOf( \Nino\Filesystem::getFileContent( $appData, self::ACCOUNTS, [] ) ) as $id => $record )
				if( $record['email'] === $email )
					return [ 'id' => (string) $id ] + self::_plain( $record );

			return null;
		}

		/**
		 *	@param		array 		$account
		 *
		 *	@return 	string										What names the member: the name, the email address where there is none
		 */
		private static function _label( array $account ): string {

			return $account['name'] !== '' ? $account['name'] : $account['email'];
		}

		/**
		 *	@param		string		$text					As typed
		 *
		 *	@return 	string										The address in lower case - '' for a text that is no email address
		 */
		private static function _email( string $text ): string {

			$email = mb_strtolower( trim( $text ), 'UTF-8' );

			return $email !== '' && filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false ? $email : '';
		}

		/**
		 *	One posted value, as a string. is_string() rather than a (string) cast:
		 *	nothing says a post carries strings, and 'email[]=x' would raise an
		 *	"Array to string conversion" - a level \Nino\Runtime treats as fatal, ie.
		 *	an unauthenticated 500 from a post anybody can send. Same reading as
		 *	\Nino\Form::posted(), whose docblock says why
		 *
		 *	@param		string		$name
		 *
		 *	@return 	string
		 */
		private static function _posted( string $name ): string {

			return is_string( $_POST[$name] ?? null ) === true ? $_POST[$name] : '';
		}

		/**
		 *	The page the answer of a login request goes back to: the posted 'return'
		 *	without its query, where it is a path of this site and no endpoint of a
		 *	module - the start page for anything else, an unsafe address among it.
		 *	The kernel's \Nino\Http::redirect() holds a path to the same rules and
		 *	leaves the response alone, with a warning, for one that breaks them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$posted
		 *
		 *	@return 	string
		 */
		private static function _returnPath( array &$appData, string $posted ): string {

			$path = substr( $posted, 0, strcspn( $posted, '?#' ) );

			return self::_isPath( $path ) === true ? $path : self::_start( $appData );
		}

		/**
		 *	Whether a text is a path of this site that is safe to redirect to: the kernel's reading of one
		 *	(\Nino\Http::isSitePath(): it starts with a single '/', has no whitespace or control character, no
		 *	':' before the next '/' - a browser reads that as a scheme - and no backslash after the slash, which
		 *	it reads as a second one) and no endpoint of a module ('/.')
		 *
		 *	@param		string		$path
		 *
		 *	@return 	bool
		 */
		private static function _isPath( string $path ): bool {

			return \Nino\Http::isSitePath( $path ) === true && str_starts_with( $path, '/.' ) === false;
		}

		/**
		 *	The start page: the setting, where it is a path of this site, else the
		 *	front page. The manifest's pattern keeps a query, a fragment and a
		 *	foreign address out of the setting (\Nino\Features::setting() hands over the
		 *	default for a value that fails it), but not an empty value, nor an
		 *	endpoint of a module
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _start( array &$appData ): string {

			$start = (string) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'start', '/' );

			return self::_isPath( $start ) === true ? $start : '/';
		}

		/**
		 *	Answer with a redirect to a page of the site, carrying the state the
		 *	form will say there
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$path
		 *	@param		string		$state				One of STATES
		 *
		 *	@return 	void
		 */
		private static function _redirect( array &$appData, array &$request, string $path, string $state ): void {

			\Nino\Http::redirect( $appData, $request, $path. '?members='. $state, 303 );
		}

		/**
		 *	Answer a link that does not lead in (see _refuse(), 400): where the link led, if its record is still kept (_expiredTarget()), is put in the session as the page the
		 *	next sign-in asked for - the form of the page asks for a code and the code leads there, the way the gate does it. The record is the server's: the address of the
		 *	request names the token and nothing else
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$token				What the request named
		 *
		 *	@return 	void
		 */
		private static function _refuseLink( array &$appData, array &$request, string $token ): void {

			$target = self::_expiredTarget( $appData, $token );

			// A device that is signed in by a good cookie has no use for the page that says the link does not work: it goes where the link led, with no form and no mail
			if( $target !== '' && ( $request['/nino/http/request']['method'] ?? '' ) === 'GET' && self::current( $appData ) === null ) {

				self::_resume( $appData, $request, true );

				$member = self::current( $appData );

				if( $member !== null ) {
					\Nino\Http::redirect( $appData, $request, self::_arrival( $appData, $member, $target ), 303 );
					return;
				}
			}

			if( $target !== '' )
				\Nino\Runtime::setSessionValue( $appData, self::SESSION_TARGET, $target );

			self::_refuse( $appData, $request, 400 );
		}

		/**
		 *	Answer a link that does not lead in: the login page in place, with the state
		 *	'invalid' for the form to say, and not stored by anyone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		int				$status				400 for a link that is not valid, 500 for a sign-in that could not be made
		 *
		 *	@return 	void
		 */
		private static function _refuse( array &$appData, array &$request, int $status ): void {

			$appData[self::STATE] = 'invalid';

			self::_answerPage( $appData, $request, 'page-members-login', $status );
		}

		/**
		 *	Answer the page that asks the member to confirm a link: the page of the
		 *	install unit with the button of componentMembersConfirm() in it, handed the
		 *	token, the name of the account and - where there is one - a state. With no
		 *	referrer: the address of the page carries the token, and nothing the page
		 *	loads is to be told it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$token				A token that is good
		 *	@param		array 		$account			As account() has it
		 *	@param		string		$state				'' for none, else one of STATES
		 *	@param		int				$status
		 *
		 *	@return 	void
		 */
		private static function _confirmPage( array &$appData, array &$request, string $token, array $account, string $state, int $status ): void {

			$appData[self::CONFIRM] = [ 'token' => $token, 'label' => self::_label( $account ), 'state' => $state ];

			self::_answerPage( $appData, $request, 'page-members-confirm', $status );
		}

		/**
		 *	Answer with a page of the install unit and nothing of the route: a response of the feature's own, under its own uri (PAGE_URI) -
		 *	the callbacks the route has, a file it names, the headers it sets and the title of its page stay out of it, but for the policy, which the
		 *	callbacks of the response have widened for this request. Not stored by anyone, and with no referrer: the address of such a page may hold
		 *	a token - the confirm page's does, and so does the page of a link that is dead - so nothing the page loads is to be told it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *	@param		string		$template			The name of the page template: page-members-login or page-members-confirm
		 *	@param		int				$status
		 *
		 *	@return 	void
		 */
		private static function _answerPage( array &$appData, array &$request, string $template, int $status ): void {

			$header = [];

			foreach( (array) ( $request['/nino/http/response']['header'] ?? [] ) as $name => $value )
				if( strtolower( (string) $name ) === 'content-security-policy' )
					$header[$name] = $value;

			$header['Cache-Control'] = 'no-store';
			$header['Referrer-Policy'] = 'no-referrer';

			$request['/nino/http/response'] = [
				'uri'					=> self::PAGE_URI,
				'locale'			=> $request['/nino/http/response']['locale'] ?? \Nino\Locales::getCurrentLocale( $appData ),
				'header'			=> $header,
				'body'				=> '[template /templates/'. $template. ']',
				'statusCode'	=> $status,
			];
		}

		/**
		 *	What the form says: the state a callback of this request set, else what
		 *	the query 'members' names - one of STATES, nothing else is a state
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										'' for none
		 */
		private static function _state( array &$appData ): string {

			$set = $appData[self::STATE] ?? null;

			if( is_string( $set ) === true && in_array( $set, self::STATES, true ) === true )
				return $set;

			$request = \Nino\Http::getRequest( $appData );
			$query = $request === false ? null : ( $request['/nino/http/request']['query'] ?? null );
			$asked = is_array( $query ) === true ? ( $query['members'] ?? null ) : null;

			return is_string( $asked ) === true && in_array( $asked, self::STATES, true ) === true ? $asked : '';
		}

		/**
		 *	The paragraph a form says its state in, the template members-state with the
		 *	key of the state's words in it - the state's own name where STATE_KEYS has
		 *	none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$state				'' for none, else one of STATES
		 *
		 *	@return 	string										'' for no state
		 */
		private static function _say( array &$appData, string $state, string $id ): string {

			if( $state === '' )
				return '';

			return self::_draw( $appData, 'members-state', [
				'[[id]]'		=> $id,
				'[[name]]'	=> self::STATE_KEYS[$state] ?? $state,
				'[[text]]'	=> \Nino\Components::escape( (string) \Nino\Html::resolveTextfill( $appData, '/feature/members/state/'. ( self::STATE_KEYS[$state] ?? $state ) ) ),
			] );
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int												How many minutes a login code, and the link of a login, is good for
		 */
		private static function _minutes( array &$appData ): int {

			return (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'linkMinutes', 15 );
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int												How many days an invitation is good for
		 */
		private static function _days( array &$appData ): int {

			return (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'inviteDays', 7 );
		}

		/**
		 *	The box 'stay signed in on this device' of the second step of the form: drawn by the component from its own template, so that a project whose words
		 *	do not have the label yet (the install unit is applied add-only) shows no box with a bare key in place of it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									The markup; '' where the label of the box has no text
		 */
		private static function _box( array &$appData ): string {

			return \Nino\Html::resolveTextfill( $appData, '/feature/members/code/remember' ) === null ? '' : self::_draw( $appData, 'members-remember', [] );
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int											How many days a device that stays signed in is good for, from its last use
		 */
		private static function _rememberDays( array &$appData ): int {

			return (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'rememberDays', self::DEFAULT_REMEMBER );
		}

		/**
		 *	Keep the kernel's full-page cache from both storing and serving what a pattern names: it joins the cache's
		 *	blacklist ('/nino/cache/blacklist', which Modules\Cache reads for both), once, for the rest of this request. Runtime
		 *	only, appended to the live array and never written to config.php - a later change to 'paths' takes effect on the
		 *	next request instead of leaving a stale entry behind. The one place the feature touches the key: the protected
		 *	prefixes at boot, with their subtrees (the cache matches its blacklist with \Nino\Http::matchesPath()), and the
		 *	address of a request of a signed-in member, whose page may carry their name and would otherwise be stored and handed
		 *	to whoever asks next
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$patterns			Uris and subtrees, as matchesPath() reads them
		 *
		 *	@return 	void
		 */
		private static function _keepOutOfCache( array &$appData, array $patterns ): void {

			$blacklist = (array) ( $appData['/nino/cache/blacklist'] ?? [] );
			$added = false;

			foreach( $patterns as $pattern )
				if( is_string( $pattern ) === true && $pattern !== '' && in_array( $pattern, $blacklist, true ) === false ) {
					$blacklist[] = $pattern;
					$added = true;
				}

			if( $added === true )
				$appData['/nino/cache/blacklist'] = $blacklist;
		}

		/**
		 *	How many requests for a code one sender may make in an hour: the setting attempts, which the manifest holds to ten at most - a sixth of the sixty places of the outbox that
		 *	a visitor may hold. The count is the same for an address that has an account and one that has none - it is made before anything is known of the address, and the sender is the
		 *	one the kernel counts a client by (see _visitor()). The mail cap of the kernel per visitor, '/nino/mail/cap', acts first where it is lower
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int
		 */
		private static function _attempts( array &$appData ): int {

			return (int) \Nino\Features::setting( $appData, self::FEATURE_KEY, 'attempts', self::DEFAULT_ATTEMPTS );
		}

		/**
		 *	The key of the counter of a visitor: the address as the kernel counts a client (an IPv6 address as its /64)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		private static function _visitor( array &$appData ): string {

			return 'visitor|'. \Nino\Http::clientBucket( \Nino\Http::getClientIp( $appData ) );
		}

		/**
		 *	An address as the gate compares it: percent escapes decoded - as often
		 *	as there is one, since '%252F' is '%2F' is '/' -, a backslash a slash,
		 *	letters lower case, runs of slashes one, '.' and '..' segments resolved
		 *	and the dots and blanks a segment ends in dropped, so that '/Club',
		 *	'//club//', '/club/.', '/club.' and '/x/../club' are the one address a
		 *	prefix names. Stricter than the server, on purpose: an address it would
		 *	answer with a 404 is shut as well
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
	}
}
