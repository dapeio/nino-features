<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Newsletter				see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Newsletter				Double opt-in newsletter signup, everything under the
	 *										/.newsletter uri: POST /.newsletter validates the address
	 *										and mails a confirmation link - nothing is subscribed
	 *										until the visitor presses the button of the page that link
	 *										opens (GET /.newsletter?confirm=<token> only reads the
	 *										token, POST /.newsletter/action does what the button
	 *										says: a mail scanner that opens links ahead of the reader
	 *										changes nothing). The same per-subscriber token drives
	 *										the self-service unsubscribe (GET /.newsletter
	 *										?unsubscribe=<token>, url via getUnsubscribeLink() -
	 *										append it to any outgoing mail). A subscriber who lost
	 *										the link asks for it again
	 *										at /.newsletter/unsubscribe: an address in, a mail with
	 *										the link out, the same answer for every address.
	 *										Persisted as one growing, email-deduped
	 *										/data/newsletter.php - no per-month bucketing (unlike
	 *										\Nino\Form::record()'s forms.<Y-m>.php) since a subscriber
	 *										list isn't naturally date-bucketed the way individual
	 *										contact inquiries are. The panel in Admin/Admin.php
	 *										reads the list through PATH and records an address it
	 *										deletes through recordRemoval().
	 *
	 *										The form that posts to it is [newsletter-signup], which
	 *										the manifest declares as a component: the Builder offers
	 *										it, a page writes it, and componentNewsletterSignup()
	 *										fills templates/newsletter-signup.tpl for both. It is the
	 *										form the manual's markup line describes, so one written by
	 *										hand is taken by the endpoint all the same.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Newsletter {

		private const int MAX_FIELD_LENGTH = 1000;

		// Where this feature's own templates are, as \Nino\Filesystem resolves
		// them: /features is the installed features directory, wherever
		// NINO_FEATURES_DIR put it
		public const string TEMPLATES = '/features/Newsletter/templates';

		// How many signup forms this request has drawn, so that the label of
		// each one points at the field of its own when a page carries more
		// than one - a hero and a footer, say
		// Where the count of the signup forms drawn in this request is kept - a request-lifecycle key, never saved
		private const string COUNTER = './newsletter/signups';

		public const string PATH = '/data/newsletter.php';

		/*	The signup endpoint is public and unauthenticated, and what it
			writes is a file that used to only ever grow: an unconfirmed entry
			had neither an expiry nor a ceiling, and \Nino\Filesystem::mutate()
			rewrites the whole file on every signup. Measured on this machine,
			2000 posts with distinct addresses: 404 KB stored, 2000 pending,
			none of them expiring, and the cost of a signup up from 0.97 ms to
			5.87 ms because each one reads and rewrites everything before it.
			That is quadratic, and nothing in front of it counts requests.

			So an unconfirmed signup expires. A confirm link that has sat
			unclicked for a week is not going to be clicked, and \Nino\Mail's
			own rate-limit file drops its elapsed keys on write for exactly
			this reason. A project with a slower audience raises the days. */
		public const string PENDING_DAYS = '/nino/newsletter/pending-days';

		/*	...and a ceiling under the expiry, because a burst arrives faster
			than a week passes. A real list does not reach it; a flood reaches
			it at once, and from there the oldest unconfirmed entry makes room
			for the newest. That can push out a visitor's own pending signup -
			they sign up again, which is one form. Refusing new signups while
			the list is full would be the other way round: anybody could close
			the form for everybody, which is the thing worth preventing. A
			confirmed subscriber is never touched by either rule. */
		public const int PENDING_LIMIT = 500;

		// Flat, append-only list of a sha256 of every email ever removed
		// (self-service unsubscribe or an admin delete) - hashed, not the
		// address itself: this list is never pruned by design (see
		// callbackRestore()'s docblock for why), and a plaintext address
		// would sit in it forever even past its own deletion, working
		// against the exact erasure this exists to protect. A hash is
		// enough - all this ever needs to answer is "was this address
		// removed", never "which addresses were removed". \Nino\Backup
		// archives the file under the plain '/data/newsletter-removed.php'
		// literal rather than this constant - see Backup::manifest()'s own
		// docblock for why - and this feature's manifest names it under
		// 'data'
		private const string REMOVED_PATH = '/data/newsletter-removed.php';

		/**
		 *	The /_admin screen this feature brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears in the
		 *	workbench exactly while this feature is active and vanishes with it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Newsletter\Admin::class ];
		}

		/**
		 *	Register the /.newsletter routes and their handlers - the routes
		 *	are registered here rather than hand-declared in config.php
		 *	(unlike the page GET routes, which are genuinely per-project
		 *	content): this module owns the endpoints it needs, so a project
		 *	enabling Modules\Newsletter (see config.php's /nino/modules)
		 *	gets a working signup + confirm/unsubscribe flow without also
		 *	having to remember to wire up the routes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {
			$appData['/nino/http/routes']['POST://.newsletter']	= [ 'uri' => '/.newsletter' ];
			$appData['/nino/http/routes']['GET://.newsletter']		= [ 'uri' => '/.newsletter', 'body' => '[template '. ( $appData['/nino/newsletter/page-template'] ?? '/templates/page-newsletter' ). ']' ];

			// The way out for a subscriber who has no link: a page with a form,
			// and the post that mails the link. Routes of their own, so the
			// signup's answers above stay what they are
			$appData['/nino/http/routes']['POST://.newsletter/unsubscribe']	= [ 'uri' => '/.newsletter/unsubscribe' ];
			$appData['/nino/http/routes']['GET://.newsletter/unsubscribe']		= [ 'uri' => '/.newsletter/unsubscribe', 'body' => '[template '. ( $appData['/nino/newsletter/unsubscribe-template'] ?? '/templates/page-newsletter-unsubscribe' ). ']' ];

			// The second step of a link: the page a link opens only reads its
			// token and offers a button, and the button posts here. Nothing
			// answers a GET at this uri, and what the post answers with is a page
			// of /.newsletter (see callbackPerform()) - so this uri has no
			// details of a page of its own
			$appData['/nino/http/routes']['POST://.newsletter/action']	= [ 'uri' => '/.newsletter/action' ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.newsletter', [ self::class, 'callbackResponse' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.newsletter', [ self::class, 'callbackLink' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.newsletter/action', [ self::class, 'callbackPerform' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.newsletter/unsubscribe', [ self::class, 'callbackUnsubscribeRequest' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.newsletter/unsubscribe', [ self::class, 'callbackUnsubscribeForm' ] );

			// A restore from /_admin merges this module's files instead of
			// overwriting them - see callbackRestore(). Registered here, so
			// Restore itself knows nothing about newsletters: a project
			// without this module has no callback, and a project that
			// deleted the module's file registers none either (callModules()
			// guards with method_exists) - no class is ever autoloaded on
			// its behalf, which is exactly what a restore, the one tool a
			// broken install needs most, must never risk
			\Nino\Callbacks::registerCallback( $appData, '/nino/admin/restore', [ self::class, 'callbackRestore' ] );
		}

		/**
		 *	[newsletter-signup] - the signup form, as the Components module hands
		 *	it over: the manifest declares the component, so the shortcode and
		 *	the Builder's component are one and the same renderer. The form is
		 *	the one a page could write by hand (see the manual's markup line):
		 *	the endpoint reads email and the empty honeypot location, and
		 *	Nino.ui.js drives the form by its classes. inline puts the field and
		 *	the button on one line, where the label is read by screen readers
		 *	only. The words are the Text panel's /feature/newsletter/signup/ keys
		 *	and the button's /feature/newsletter/label/submit, left in the
		 *	template for the kernel to resolve
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					The resolved arguments (see \Nino\Modules\Components::dispatch())
		 *
		 *	@return 	string								The form
		 */
		public static function componentNewsletterSignup( array &$appData, array $args ): string {

			$inline = $args['inline'] === '1';

			// The classes of the call last: escaped, they cannot carry a token, but they are the one value an editor writes
			return str_replace(
				[ '[[inline]]', '[[hidden]]', '[[id]]', '[[classes]]' ],
				[ $inline === true ? ' nino-form--inline' : '', $inline === true ? ' nino-sr-only' : '', 'newsletter-signup-'. ( $appData[self::COUNTER] = (int) ( $appData[self::COUNTER] ?? 0 ) + 1 ), \Nino\Feature::classes( $args ) ],
				\Nino\Feature::template( $appData, self::TEMPLATES, 'newsletter-signup' )
			);
		}

		/**
		 *	Validate a signup request and mail the confirmation link - the
		 *	actual subscription only happens once the button of the page that
		 *	link opens is pressed (double opt-in, see callbackLink() and
		 *	callbackPerform()). The answer follows the
		 *	delivery and nothing else: 200 when the mail went out, 429 when
		 *	the per-ip mail cap refused it, 500 when it could not be sent (a
		 *	site without an address has no link to send) or the entry could not
		 *	be stored
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackResponse( array &$appData, array &$request ): void {

			// Respect a rejection from the earlier global Csrf callback - the
			// same guard \Nino\Form::handle() and \Nino\Auth's callbacks use
			if( \Nino\Form::blocked( $request ) === true )
				return;

			[ $email, $location ] = self::_posted();

			// Email missing/invalid
			if( $email === '' || filter_var( $email, FILTER_VALIDATE_EMAIL ) === false )
				$request['/nino/http/response']['statusCode'] = 400;

			// Honeypot is filled
			if( $location !== '' )
				$request['/nino/http/response']['statusCode'] = 418;

			if( $request['/nino/http/response']['statusCode'] !== 200 )
				return;

			$outcome = self::_requestSignup( $appData, $email );

			// The answer depends on the delivery and on nothing about the
			// address. Reporting 'new' vs 'existing' told anyone who asked
			// whether a given address is on the list - the signup form is
			// public, so that is a free subscriber-enumeration oracle - and
			// so would a 429 or a 500 that only some addresses can reach: an
			// address that is already subscribed gets the mail again, with its
			// stored token, which is what lets every address take the same
			// way through the mail cap and the transport. A visitor is told
			// what happened to the mail, not who is on the list; the page
			// shows the generic /feature/newsletter/info/error for anything but 200
			if( $outcome === 'ratelimited' ) {
				$request['/nino/http/response']['statusCode'] = 429;
				return;
			}

			if( $outcome === 'failed' ) {
				$request['/nino/http/response']['statusCode'] = 500;
				return;
			}

			$request['/nino/http/response']['statusCode'] = 200;
			$request['/nino/http/response']['body'] 			= [ 'status' => 'ok' ];
		}

		/**
		 *	Handle a visited confirm/unsubscribe link (GET /.newsletter
		 *	?confirm=<token> / ?unsubscribe=<token>): it changes nothing. A link
		 *	is opened by whoever holds the mail and by whatever reads it ahead
		 *	of them - a scanner follows every link - so the visit only checks
		 *	the token, with the reading the post below will give it, and offers
		 *	the one step that does the deed: a button in a form that posts to
		 *	/.newsletter/action. An unknown, expired or missing token answers
		 *	404 - the page stays a friendly html page either way - and the
		 *	token of a signup that is confirmed already answers the page of the
		 *	confirmation, without a button: there is nothing left to press
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackLink( array &$appData, array &$request ): void {

			$query = $request['/nino/http/request']['query'] ?? [];

			[ $action, $token ] = self::_asked( is_array( $query ) === true ? $query : [] );

			$known = $action === null ? null : self::_known( $appData, $action, $token );

			if( $known === null ) {
				$request['/nino/http/response']['statusCode'] = 404;
				self::_fillPage( $appData, 'invalid' );
				return;
			}

			if( $known === 'done' ) {
				self::_fillPage( $appData, 'confirmed' );
				return;
			}

			// The token is the one the list holds, and is escaped anyway: it
			// goes into a fill, and a fill is read for fills and shortcodes
			// once more
			\Nino\Html::addFills( $appData, [
				'[[/feature/newsletter/action/field]]'	=> $action,
				'[[/feature/newsletter/action/token]]'	=> \Nino\Modules\Components::escape( $token ),
				'[[/feature/newsletter/action/button]]'	=> '[[/feature/newsletter/action/'. $action. ']]',
			], '*' );

			self::_fillPage( $appData, 'pending-'. $action, '[template '. ( $appData['/nino/newsletter/action-template'] ?? '/templates/newsletter-action' ). ']' );
		}

		/**
		 *	POST /.newsletter/action: what the button of the page a link opens
		 *	does - confirms the signup or removes the entry the token belongs
		 *	to, and answers with the page of the outcome (200), or with the
		 *	invalid page (404) for a token that is unknown, expired or not
		 *	there, and for a post that carries both fields or neither. Nothing
		 *	happens without the csrf token the form carries: the kernel's guard
		 *	has answered 403 with the body false, which a visitor whose session
		 *	is gone would read as a bare "false" - the status stays, and the body
		 *	is the page that says the page has expired (a body that is a string
		 *	is rendered whatever the status is, Http only encodes the others).
		 *	Every answer is a page of /.newsletter: the response's uri is that of
		 *	the page template's details (its title and description are read from
		 *	/_nino/webpage<uri>/...), and this route has none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackPerform( array &$appData, array &$request ): void {

			$request['/nino/http/response']['uri']		= '/.newsletter';
			$request['/nino/http/response']['body']	= '[template '. ( $appData['/nino/newsletter/page-template'] ?? '/templates/page-newsletter' ). ']';

			if( \Nino\Form::blocked( $request ) === true ) {
				self::_fillPage( $appData, 'expired' );
				return;
			}

			[ $action, $token ] = self::_asked( $_POST );
			$result = 'invalid';

			if( $action === 'confirm' && self::_confirm( $appData, $token ) === true )
				$result = 'confirmed';
			else if( $action === 'unsubscribe' && self::_unsubscribe( $appData, $token ) === true )
				$result = 'unsubscribed';

			if( $result === 'invalid' )
				$request['/nino/http/response']['statusCode'] = 404;

			self::_fillPage( $appData, $result );
		}

		/**
		 *	Build the absolute self-service unsubscribe url for a subscribed
		 *	(or still pending) email - append it to any outgoing newsletter
		 *	mail. Built by \Nino\Html::absoluteUrl(), on the website's address
		 *	(the fill /project/website/general/url): a site that has none has no
		 *	link to give
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Subscriber to build the url for
		 *
		 *	@return 	string | false							Unsubscribe url, or false for an unknown email - and for a site without an address
		 */
		public static function getUnsubscribeLink( array &$appData, string $email ): string|false {

			$email = mb_strtolower( trim( $email ) );

			foreach( \Nino\Filesystem::getFileContent( $appData, self::PATH, [] ) as $entry )
				if( ( $entry['email'] ?? null ) === $email && empty( $entry['token'] ) === false )
					return self::_getActionUrl( $appData, 'unsubscribe', $entry['token'] ) ?: false;

			return false;
		}

		/**
		 *	GET /.newsletter/unsubscribe: the form that asks for the address.
		 *	The page's error fill is empty here, and set by the post below
		 *	when it has something to say
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackUnsubscribeForm( array &$appData, array &$request ): void {

			\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/unsubscribe/error]]' => '' ], '*' );
		}

		/**
		 *	POST /.newsletter/unsubscribe: mail the unsubscribe link to an
		 *	address that has lost its own. Nothing is removed here - the link
		 *	is the proof that the address is the asker's, exactly as for the
		 *	confirm link - and the answer is the same for every address and
		 *	for every outcome of the mail: 200, 'a link is on its way if the
		 *	address is on the list'. A known address is mailed, an unknown one
		 *	is not; the visitor cannot tell which, because nothing in the
		 *	answer depends on it - no 429, no 500, whatever the mail did.
		 *	Nothing happens without the csrf token the form carries: the
		 *	kernel's guard has answered 403 with the body false, which a
		 *	visitor whose session is gone would read as a bare "false" - the
		 *	status stays, and the body is the form's page with the line that
		 *	says the form has expired
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackUnsubscribeRequest( array &$appData, array &$request ): void {

			$form = '[template '. ( $appData['/nino/newsletter/unsubscribe-template'] ?? '/templates/page-newsletter-unsubscribe' ). ']';

			if( \Nino\Form::blocked( $request ) === true ) {
				$request['/nino/http/response']['body'] = $form;
				\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/unsubscribe/error]]' => '[[/feature/newsletter/info/expired]]' ], '*' );
				return;
			}

			[ $email, $location ] = self::_posted();

			$page = '[template '. ( $appData['/nino/newsletter/page-template'] ?? '/templates/page-newsletter' ). ']';

			// The answer page, the same one whatever was asked: a filled
			// honeypot gets it too, so a bot learns nothing from the status
			// but that it was refused
			$answer = static function( array &$appData, array &$request ) use ( $page ): void {

				self::_fillPage( $appData, 'unsubscribe-requested' );

				$request['/nino/http/response']['body'] = $page;
			};

			if( $location !== '' ) {
				$request['/nino/http/response']['statusCode'] = 418;
				$answer( $appData, $request );
				return;
			}

			if( $email === '' || filter_var( $email, FILTER_VALIDATE_EMAIL ) === false ) {
				$request['/nino/http/response']['statusCode'] = 400;
				$request['/nino/http/response']['body'] 			= $form;
				\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/unsubscribe/error]]' => '[[/feature/newsletter/info/email]]' ], '*' );
				return;
			}

			self::_requestUnsubscribe( $appData, $email );

			$request['/nino/http/response']['statusCode'] = 200;
			$answer( $appData, $request );
		}

		/**
		 *	The fills the page template renders an outcome with. The outer key
		 *	is what the template uses, the inner one resolves per locale from
		 *	the text files; the form is the page's one place for a button, and
		 *	empty wherever the page has none to offer
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$result				The outcome: the name behind /feature/newsletter/result-
		 *	@param		string		$form					What the page draws as its form, '' for none
		 *
		 *	@return 	void
		 */
		private static function _fillPage( array &$appData, string $result, string $form = '' ): void {

			\Nino\Html::addFills( $appData, [
				'[[/feature/newsletter/page/title]]'	=> '[[/feature/newsletter/result-'. $result. '/title]]',
				'[[/feature/newsletter/page/text]]'		=> '[[/feature/newsletter/result-'. $result. '/text]]',
				'[[/feature/newsletter/page/form]]'		=> $form,
			], '*' );
		}

		/**
		 *	Which link a visit or a post carries: exactly one of confirm and
		 *	unsubscribe, as a string. Both, none or an array ('?confirm[]=x')
		 *	is no token, which is the invalid page - an array least of all may
		 *	reach a string cast, see _posted()
		 *
		 *	@param		array 		$source				The query or the post
		 *
		 *	@return 	array										[ 'confirm' | 'unsubscribe' | null, the token ]
		 */
		private static function _asked( array $source ): array {

			$actions = array_values( array_filter( [ 'confirm', 'unsubscribe' ], static fn( string $action ): bool => isset( $source[$action] ) === true ) );

			if( count( $actions ) !== 1 || is_string( $source[ $actions[0] ] ) === false )
				return [ null, '' ];

			return [ $actions[0], $source[ $actions[0] ] ];
		}

		/**
		 *	What a token opens, without touching the list - the question
		 *	_confirm() and _unsubscribe() answer by doing it: a confirm token
		 *	belongs to an entry that is still live, an unsubscribe token to any
		 *	entry, pending or not. A confirm token of an entry that is subscribed
		 *	has nothing left to do: 'done'. An entry from before the double
		 *	opt-in flow has no status, and its confirmation still records one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$action				'confirm' or 'unsubscribe'
		 *	@param		string		$token				Token from the visited link
		 *
		 *	@return 	string|null							'open' when the button has something to do, 'done' for a signup that is confirmed already, null for no token
		 */
		private static function _known( array &$appData, string $action, string $token ): ?string {

			if( $token === '' )
				return null;

			$now 					= time();
			$keepSeconds 	= self::_keepSeconds( $appData );

			foreach( \Nino\Filesystem::getFileContent( $appData, self::PATH, [] ) as $entry )
				if( is_array( $entry ) === true && hash_equals( (string) ( $entry['token'] ?? '' ), $token ) === true && ( $action === 'unsubscribe' || self::_live( $entry, $now, $keepSeconds ) === true ) )
					return $action === 'confirm' && ( $entry['status'] ?? '' ) === 'subscribed' ? 'done' : 'open';

			return null;
		}

		/**
		 *	Whether an entry can still be confirmed: one that is not pending
		 *	always, a pending one while it is within the pending days. The
		 *	reading _prunePending() applies - a pending entry past its days, or
		 *	with no date to count them from, is gone as far as a visitor can
		 *	tell, it is only not yet swept
		 *
		 *	@param		array 		$entry				A stored entry
		 *	@param		int				$now					Now, as a timestamp
		 *	@param		int				$keepSeconds	How long an unconfirmed entry lives
		 *
		 *	@return 	bool
		 */
		private static function _live( array $entry, int $now, int $keepSeconds ): bool {

			if( ( $entry['status'] ?? '' ) !== 'pending' )
				return true;

			$at = strtotime( (string) ( $entry['date'] ?? '' ) );

			return $at !== false && $at + $keepSeconds > $now;
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	int											How long an unconfirmed entry lives, in seconds
		 */
		private static function _keepSeconds( array &$appData ): int {
			return max( 1, (int) ( $appData[ self::PENDING_DAYS ] ?? 7 ) ) * 86400;
		}

		/**
		 *	Read the two posted values of a signup or an unsubscribe request
		 *
		 *	Not escaped before validating (\Nino\Form::handle() keeps the same order):
		 *	escaping an address with an apostrophe first would turn it into
		 *	"&#039;", either failing validation or getting stored in a form
		 *	getUnsubscribeLink() can never match again.
		 *
		 *	is_string() rather than a (string) cast: nothing says a post
		 *	carries strings, and 'email[]=x' used to raise an "Array to
		 *	string conversion" - a level \Nino\Runtime treats as fatal, ie.
		 *	an unauthenticated 500 from a post anybody can send. Same reading
		 *	as \Nino\Form::posted(), whose docblock says why. An array in the
		 *	honeypot reads as a filled honeypot, which is what it is
		 *
		 *	@return 	array										[ lowercased trimmed email, trimmed honeypot value ]
		 */
		private static function _posted(): array {

			$postedEmail		= is_string( $_POST['email'] ?? null ) === true ? $_POST['email'] : '';
			$postedLocation	= isset( $_POST['location'] ) === true && is_string( $_POST['location'] ) === false
				? 'not a string'
				: (string) ( $_POST['location'] ?? '' );

			return [
				mb_strtolower( mb_strcut( trim( $postedEmail ), 0, self::MAX_FIELD_LENGTH, 'UTF-8' ) ),
				mb_strcut( trim( $postedLocation ), 0, self::MAX_FIELD_LENGTH, 'UTF-8' ),
			];
		}

		/**
		 *	Mail the unsubscribe link to an address that is on the list,
		 *	subscribed or still pending. An entry written before the double
		 *	opt-in flow has no token and so no link: it gets one here, in the
		 *	same locked write. An address that is not on the list is left
		 *	alone. The outcome of the mail is not reported - the caller's
		 *	answer must not depend on it. A site without an address has no link
		 *	to mail: nothing is sent, and a warning says why.
		 *
		 *	Only a mail that is sent charges the per-ip mail cap, so what is left
		 *	of the difference between a listed and an unlisted address is the
		 *	cap (the signup answers 429 at it) and the time the request takes -
		 *	both accepted. Charging it for an unlisted address as
		 *	well would need a public \Nino\Mail API for it, not a send to ''
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Validated, lowercased address
		 *
		 *	@return 	void
		 */
		private static function _requestUnsubscribe( array &$appData, string $email ): void {

			try {

				$token = null;
				$added = false;
				$ran = false;

				$written = \Nino\Filesystem::mutate( $appData, self::PATH, function( array $entries ) use ( $email, &$token, &$added, &$ran ): ?array {

					$ran = true;

					foreach( $entries as $entryKey => $entry ) {

						if( ( $entry['email'] ?? null ) !== $email )
							continue;

						if( empty( $entry['token'] ) === false ) {
							$token = (string) $entry['token'];
							return null;
						}

						$token = bin2hex( random_bytes( 16 ) );
						$added = true;
						$entries[$entryKey]['token'] = $token;

						return $entries;
					}

					return null;
				} );

				// A token that was made here and not stored would be a link that
				// never works - no mail is better than that one
				if( $token === null || ( $added === true && $written === false ) ) {

					if( $ran === false )
						trigger_error( 'Newsletter unsubscribe request: the list could not be locked' );
					elseif( $added === true )
						trigger_error( 'Newsletter unsubscribe request: the token could not be stored' );

					return;
				}

				$url = self::_getActionUrl( $appData, 'unsubscribe', $token );

				// A site without an address has no link to put in the mail, and a
				// mail without its link is no better than none
				if( $url === '' ) {
					trigger_error( 'Newsletter unsubscribe request: no mail was sent, the site has no address (the fill /project/website/general/url)', E_USER_WARNING );
					return;
				}

				// The link is the one thing this mail has to carry, and it is a
				// credential: a fill for '*' stays in $appData for the rest of the
				// request, so it is taken out again whatever the send does
				\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/unsubscribe/url]]' => $url ], '*' );

				try {
					$template = $appData['/nino/newsletter/unsubscribe-mail-template'] ?? '/templates/mail-newsletter-unsubscribe';
					$tpl 			= \Nino\Html::renderHtml( $appData, '[template '. $template. ']' );
					$subject 	= \Nino\Html::renderHtml( $appData, '[[/feature/newsletter/subject/unsubscribe]]' );
					$replyTo 	= \Nino\Html::renderHtml( $appData, '[[/project/mail/address/owner]]' );

					\Nino\Mail::send( $appData, $email, $subject, $tpl, $replyTo );
				} finally {
					\Nino\Html::removeFill( $appData, '/feature/newsletter/unsubscribe/url' );
				}

			} catch( \Throwable $e ) {
				trigger_error( 'Newsletter unsubscribe request failed: '. $e->getMessage() );
			}
		}

		/**
		 *	Record a pending signup and mail its confirmation link - never
		 *	thrown, same reasoning as \Nino\Form::record(): a storage failure is
		 *	answered, not raised. Every address takes the same way: a new one
		 *	is recorded as pending with a fresh token, a still-pending one gets
		 *	its mail again with the same token (the first one may simply never
		 *	have arrived), and one that is already subscribed gets it with the
		 *	token it already has - its status and date stay as they are, so
		 *	confirming that mail changes nothing. Entries written before the
		 *	double opt-in flow (no status/token fields) count as subscribed and
		 *	get a token the first time they are asked for one; confirming that
		 *	token's mail records them with status 'subscribed' and leaves the
		 *	date they signed up on
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Validated email address
		 *
		 *	@return 	string									'sent' when the mail went out, 'ratelimited' when the mail cap refused it, 'failed' when the entry could not be stored or the mail could not be sent
		 */
		private static function _requestSignup( array &$appData, string $email ): string {

			try {

				$ran 				= false;
				$token 			= null;
				// Resolved out here: the closure has no $appData, and the
				// address a visitor counts as depends on the proxy list in it
				// (see \Nino\Http::getClientIp())
				$ip 				= \Nino\Http::getClientIp( $appData );
				$keepSeconds = self::_keepSeconds( $appData );

				$written = \Nino\Filesystem::mutate( $appData, self::PATH, function( array $entries ) use ( $email, $ip, $keepSeconds, &$ran, &$token ): array {

					$ran = true;

					foreach( $entries as $entryKey => $entry ) {

						if( ( $entry['email'] ?? null ) !== $email )
							continue;

						if( ( $entry['status'] ?? 'subscribed' ) === 'subscribed' ) {

							$token 			= empty( $entry['token'] ) === false ? (string) $entry['token'] : null;

							// Nothing about the entry changes - only a legacy one gets
							// the token it never had. The list is written back either
							// way, unchanged: a signup that writes for a new address and
							// not for a subscribed one would answer 200 for the one and
							// 500 for the other while the write fails
							if( $token === null ) {
								$token = bin2hex( random_bytes( 16 ) );
								$entries[$entryKey]['token'] = $token;
							}

							return $entries;
						}

						$token = $entry['token'];
						$entries[$entryKey]['date'] = date( 'Y-m-d H:i:s' );
					}

					// After the loop, so the entry it just refreshed is the newest
					// one here and cannot be the one that makes room
					$entries = self::_prunePending( $entries, $keepSeconds );

					if( $token === null ) {
						$token 		 = bin2hex( random_bytes( 16 ) );
						$entries[] = [
							'email'		=> $email,
							'token'		=> $token,
							'status'	=> 'pending',
							'date'		=> date( 'Y-m-d H:i:s' ),
							'ip'			=> $ip,
						];
					}

					return $entries;
				} );

				// The callback never ran when the lock itself couldn't be taken -
				// nothing was recorded, so there is nothing to mail a link for
				// either. A write that failed is the same: the token in the mail
				// would be one nothing stores
				if( $ran === false || $token === null || $written === false )
					return 'failed';

				// An earlier removal of the address is left alone: anyone can type
				// an address into the form, so a signup is no consent. Only the
				// confirmation is, and _confirm() clears the record with it
				return self::_sendConfirmMail( $appData, $email, $token );

			} catch( \Throwable $e ) {
				trigger_error( 'Newsletter signup write failed: '. $e->getMessage() );
				return 'failed';
			}
		}

		/**
		 *	Drop the unconfirmed entries that have run out of time, and then
		 *	the oldest of whatever is still over the ceiling.
		 *
		 *	Only ever unconfirmed ones: a subscriber gave consent and stays
		 *	until they withdraw it, and an entry written before the double
		 *	opt-in flow has no status at all - it counts as subscribed, the
		 *	same reading _requestSignup() applies. An entry whose date cannot
		 *	be read is treated as expired rather than kept forever; it can only
		 *	come from a hand-edited file, and a pending entry nobody can date
		 *	is one nobody can confirm either.
		 *
		 *	@param		array			$entries			The stored list
		 *	@param		int				$keepSeconds	How long an unconfirmed entry lives
		 *
		 *	@return 	array										The list, bounded
		 */
		private static function _prunePending( array $entries, int $keepSeconds ): array {

			$now		= time();
			$pending	= [];

			foreach( $entries as $key => $entry ) {

				if( is_array( $entry ) === false || ( $entry['status'] ?? 'subscribed' ) !== 'pending' )
					continue;

				$at = strtotime( (string) ( $entry['date'] ?? '' ) );

				if( $at === false || $at + $keepSeconds <= $now ) {
					unset( $entries[$key] );
					continue;
				}

				$pending[$key] = $at;
			}

			// The oldest first, so what goes is what has waited longest
			asort( $pending );

			foreach( array_keys( $pending ) as $key ) {
				if( count( $pending ) < self::PENDING_LIMIT )
					break;
				unset( $entries[$key], $pending[$key] );
			}

			return array_values( $entries );
		}

		/**
		 *	Send the confirmation mail carrying the signup link, in the
		 *	visitor's own current locale (no owner-locale juggling needed
		 *	here, unlike Form - there's no separate owner notification to
		 *	send). Template path is config-driven (/nino/newsletter/
		 *	confirm-template) so a project can swap it; recipient/subject/
		 *	body copy stay Text-driven same as Form's mail-user/mail-owner.
		 *	Whether the mail went out is the answer the visitor gets (see
		 *	callbackResponse()) - the pending entry is recorded either way,
		 *	so a re-submit simply re-sends it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Recipient
		 *	@param		string		$token				The entry's token
		 *
		 *	@return 	string									'sent' when \Nino\Mail::send() delivered the mail, 'ratelimited' when the cap refused it, 'failed' when it could not be sent - and for a site without an address, which has no link to put in it, with a warning
		 */
		private static function _sendConfirmMail( array &$appData, string $email, string $token ): string {

			$template = $appData['/nino/newsletter/confirm-template'] ?? '/templates/mail-newsletter-confirm';
			$url			= self::_getActionUrl( $appData, 'confirm', $token );

			// A site without an address has no link to put in the mail, and a
			// mail without its link is no better than none. The visitor is told
			// what happened to the mail, as for any other that is not sent
			if( $url === '' ) {
				trigger_error( 'Newsletter signup: no confirmation mail was sent, the site has no address (the fill /project/website/general/url)', E_USER_WARNING );
				return 'failed';
			}

			// A fill for '*' stays in $appData for the rest of the request, and
			// the link is a credential: it is taken out again once the mail is
			// out, whatever the send does, so no later response can carry it
			\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/confirm/url]]' => $url ], '*' );

			try {
				$tpl 			= \Nino\Html::renderHtml( $appData, '[template '. $template. ']' );
				$subject 	= \Nino\Html::renderHtml( $appData, '[[/feature/newsletter/subject/confirm]]' );
				$replyTo 	= \Nino\Html::renderHtml( $appData, '[[/project/mail/address/owner]]' );

				if( \Nino\Mail::send( $appData, $email, $subject, $tpl, $replyTo ) === true )
					return 'sent';

				return \Nino\Mail::rateLimited( $appData ) === true ? 'ratelimited' : 'failed';
			} finally {
				\Nino\Html::removeFill( $appData, '/feature/newsletter/confirm/url' );
			}
		}

		/**
		 *	Flip a pending entry to subscribed for a posted confirm token.
		 *	Idempotent - confirming an already-subscribed token stays true,
		 *	so a twice-clicked mail link doesn't scare the visitor with an
		 *	error. A pending entry gets the date and the ip of the confirming
		 *	request - the record of the consent is the confirmation, not the
		 *	form that anybody could have submitted - and the removal record of
		 *	its address is cleared in the same write: a removal that cannot be
		 *	cleared leaves the entry pending. A legacy one - a token but no
		 *	status, from before the double opt-in flow - keeps the date and the
		 *	ip it signed up with, and only its status is set. A
		 *	pending entry that has waited longer than the pending days is not
		 *	confirmed either: its token is as unknown as one never issued (the
		 *	entry stays until the next signup sweeps it, _prunePending()), and
		 *	so is the token of one whose date cannot be read
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token				Token from the link, as the post carries it
		 *
		 *	@return 	bool										False for an unknown/empty/expired token or a failed write
		 */
		private static function _confirm( array &$appData, string $token ): bool {

			if( $token === '' )
				return false;

			try {

				$found = false;
				$now = time();
				$keepSeconds = self::_keepSeconds( $appData );
				// Resolved out here, as in _requestSignup(): this is the request
				// of whoever pressed the button
				$ip = \Nino\Http::getClientIp( $appData );

				\Nino\Filesystem::mutate( $appData, self::PATH, function( array $entries, array &$appData ) use ( $token, $now, $keepSeconds, $ip, &$found ): ?array {

					foreach( $entries as $entryKey => $entry ) {

						if( hash_equals( (string) ( $entry['token'] ?? '' ), $token ) === false )
							continue;

						// A pending entry past its days is gone as far as a visitor can
						// tell - it is only not yet swept
						if( self::_live( $entry, $now, $keepSeconds ) === false )
							continue;

						$found = true;

						if( ( $entry['status'] ?? '' ) === 'subscribed' )
							return null;

						// An entry from before the double opt-in flow has a token but no
						// status, and its date is the day it signed up: only the status
						// is set. A pending one has no date of its own yet
						$legacy = isset( $entry['status'] ) === false && empty( $entry['date'] ) === false;
						$entries[$entryKey]['status']	= 'subscribed';

						if( $legacy === false ) {

							// A consent given now is the one a restore must not undo:
							// an earlier removal of the address ends here, in the write
							// that confirms. When it cannot be cleared the entry stays
							// pending - an address stays removed rather than turn up
							// subscribed on a list a restore will filter
							if( self::_clearRemoval( $appData, (string) ( $entry['email'] ?? '' ) ) === false ) {
								$found = false;
								return null;
							}

							$entries[$entryKey]['date']	= date( 'Y-m-d H:i:s', $now );
							$entries[$entryKey]['ip']		= $ip;
						}

						return $entries;
					}

					return null;
				} );

				return $found;

			} catch( \Throwable $e ) {
				trigger_error( 'Newsletter confirm failed: '. $e->getMessage() );
			}

			return false;
		}

		/**
		 *	Remove the entry a posted unsubscribe token belongs to -
		 *	works for subscribed and still-pending entries alike
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token				Token from the link, as the post carries it
		 *
		 *	@return 	bool										False for an unknown/empty token or a failed write
		 */
		private static function _unsubscribe( array &$appData, string $token ): bool {

			if( $token === '' )
				return false;

			try {

				$found = false;
				$email = null;

				\Nino\Filesystem::mutate( $appData, self::PATH, function( array $entries ) use ( $token, &$found, &$email ): ?array {

					foreach( $entries as $entryKey => $entry )
						if( hash_equals( (string) ( $entry['token'] ?? '' ), $token ) === true ) {
							$email = $entry['email'] ?? null;
							unset( $entries[$entryKey] );
							$found = true;
							return array_values( $entries );
						}

					return null;
				} );

				if( $found === true && is_string( $email ) === true )
					self::recordRemoval( $appData, $email );

				return $found;

			} catch( \Throwable $e ) {
				trigger_error( 'Newsletter unsubscribe failed: '. $e->getMessage() );
			}

			return false;
		}

		/**
		 *	Build an absolute /.newsletter action url (confirm/unsubscribe)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$action				Query key: 'confirm' or 'unsubscribe'
		 *	@param		string		$token				The entry's token
		 *
		 *	@return 	string									Absolute url, '' for a site without an address
		 */
		private static function _getActionUrl( array &$appData, string $action, string $token ): string {

			return \Nino\Html::absoluteUrl( $appData, '/.newsletter?'. $action. '='. rawurlencode( $token ) );
		}

		/**
		 *	Rewrite the staged newsletter files in place before /_admin's
		 *	Restore copies the staged directories over the live ones, so a
		 *	restore merges instead of overwriting: an address someone removed
		 *	(self-service unsubscribe or an admin delete, see REMOVED_PATH's
		 *	docblock) stays removed no matter how old the backup being
		 *	restored is - Art. 17 (right to erasure), once exercised, must
		 *	survive a later disaster-recovery restore the same way it
		 *	survives everything else.
		 *
		 *	The removal list itself is unioned rather than replaced (a removal
		 *	recorded on either side stays a removal) since the live copy
		 *	could itself be the very thing being recovered from and is not to
		 *	be trusted as complete. A confirmed resubscribe clears its own
		 *	address from that list (see _clearRemoval()'s call site in
		 *	_confirm()) - this merge only ever excludes, never decides who
		 *	should be excluded. The list
		 *	holds a sha256 per address, not the address itself, so an entry is
		 *	matched by hashing it the same way, not by comparing emails.
		 *
		 *	Reached through the '/nino/admin/restore' callback init()
		 *	registers, with the live /data directory and the extracted backup
		 *	as arguments. A no-op if the backup carries neither file. Runs only
		 *	while the module is active - a project that switched it off after
		 *	recording removals and then restores an older backup gets the
		 *	backup's list back, which nothing reads until the module returns.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$args				(reference) { dataDir: live /data (read only), staging: extracted backup }
		 *
		 *	@return 	void
		 */
		public static function callbackRestore( array &$appData, array &$args ): void {

			$dataDir	= (string) ( $args['dataDir'] ?? '' );
			$staging	= (string) ( $args['staging'] ?? '' );

			$stagedEntries = $staging. '/data/newsletter.php';
			$stagedRemoved = $staging. '/data/newsletter-removed.php';

			if( $staging === '' || ( is_file( $stagedEntries ) === false && is_file( $stagedRemoved ) === false ) )
				return;

			$removed = array_values( array_unique( array_merge(
				self::_readDataFile( $dataDir. '/newsletter-removed.php' ),
				self::_readDataFile( $stagedRemoved )
			) ) );

			$entries = array_values( array_filter(
				self::_readDataFile( $stagedEntries ),
				function( array $entry ) use ( $removed ): bool {
					return in_array( self::_removalHash( (string) ( $entry['email'] ?? '' ) ), $removed, true ) === false;
				}
			) );

			file_put_contents( $stagedEntries, '<?php return '. var_export( $entries, true ). ';' );
			file_put_contents( $stagedRemoved, '<?php return '. var_export( $removed, true ). ';' );
		}

		// Reads one of Filesystem's own "<?php return [...];" data files
		// straight off disk, bypassing $appData/Filesystem entirely - used
		// by callbackRestore() for files under the staging dir (a tempdir,
		// not the project root Filesystem resolves against) and, for the
		// same reason, for the live copy alongside it, so both sides of the
		// merge go through the identical read path
		private static function _readDataFile( string $path ): array {

			if( is_file( $path ) === false )
				return [];

			$data = include $path;

			return is_array( $data ) ? $data : [];
		}

		/**
		 *	Record an address as removed - by the self-service unsubscribe
		 *	above, and by the panel's delete (Admin::apiDelete()), which
		 *	calls it inside the list's lock and keeps the address on the list
		 *	when this answers false. The list is never pruned (see
		 *	REMOVED_PATH), so only the hash goes in, never the address itself
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				The removed address
		 *
		 *	@return 	bool										False when the record could not be written
		 */
		public static function recordRemoval( array &$appData, string $email ): bool {

			$hash = self::_removalHash( $email );

			return \Nino\Filesystem::mutate( $appData, self::REMOVED_PATH, function( array $removed ) use ( $hash ): array {

				if( in_array( $hash, $removed, true ) === false )
					$removed[] = $hash;

				return $removed;
			} );
		}

		// Undoes recordRemoval() for a confirmed signup - see its call site in
		// _confirm(). False when the record could not be written
		private static function _clearRemoval( array &$appData, string $email ): bool {

			$hash = self::_removalHash( $email );

			// An address that was never removed has nothing to undo, and its
			// confirmation does not depend on this file being writable
			$stored = \Nino\Filesystem::getFileContent( $appData, self::REMOVED_PATH, [] );

			if( is_array( $stored ) === false || in_array( $hash, $stored, true ) === false )
				return true;

			return \Nino\Filesystem::mutate( $appData, self::REMOVED_PATH, function( array $removed ) use ( $hash ): array {
				return array_values( array_filter( $removed, function( $entry ) use ( $hash ): bool { return $entry !== $hash; } ) );
			} );
		}

		// What the removal record holds for an address: the sha256 of it,
		// trimmed and lower-cased, so the spelling it was typed in does not
		// matter - written by recordRemoval(), cleared by _clearRemoval(),
		// matched by callbackRestore()
		private static function _removalHash( string $email ): string {
			return hash( 'sha256', mb_strtolower( trim( $email ) ) );
		}
	}

}
