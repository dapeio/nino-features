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
	 *										until GET /.newsletter?confirm=<token> is visited. The
	 *										same per-subscriber token drives the self-service
	 *										unsubscribe (GET /.newsletter?unsubscribe=<token>, url
	 *										via getUnsubscribeLink() - append it to any outgoing
	 *										mail). A subscriber who lost the link asks for it again
	 *										at /.newsletter/unsubscribe: an address in, a mail with
	 *										the link out, the same answer for every address.
	 *										Persisted as one growing, email-deduped
	 *										/data/newsletter.php - no per-month bucketing (unlike
	 *										Form::_record()'s forms.<Y-m>.php) since a subscriber
	 *										list isn't naturally date-bucketed the way individual
	 *										contact inquiries are. Read independently by
	 *										Newsletter\Admin in Admin/Admin.php beside this file.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Newsletter {

		private const int MAX_FIELD_LENGTH = 1000;

		private const string PATH = '/data/newsletter.php';

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
		// _recordRemoval()'s own docblock for why), and a plaintext address
		// would sit in it forever even past its own deletion, working
		// against the exact erasure this exists to protect. A hash is
		// enough - all this ever needs to answer is "was this address
		// removed", never "which addresses were removed". Consulted by
		// \Nino\Backup and Dev\Restore, both via the plain
		// '/data/newsletter-removed.php' literal rather than this constant -
		// see Backup::manifest()'s own docblock for why
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

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/POST://.newsletter', [ self::class, 'callbackResponse' ] );
			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET://.newsletter', [ self::class, 'callbackAction' ] );
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
		 *	Validate a signup request and mail the confirmation link - the
		 *	actual subscription only happens once that link is visited
		 *	(double opt-in, see callbackAction()). The answer follows the
		 *	delivery and nothing else: 200 when the mail went out, 429 when
		 *	the per-ip mail cap refused it, 500 when it could not be sent or
		 *	the entry could not be stored
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackResponse( array &$appData, array &$request ): void {

			// Respect a rejection from the earlier global Csrf callback - same guard
			// Form/Auth::callbackResponse use
			if( ( $request['./nino/csrf/blocked'] ?? false ) === true )
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
		 *	?confirm=<token> / ?unsubscribe=<token>) and prepare the fills
		 *	the page template renders the outcome with. An unknown or
		 *	missing token answers 404 - the page stays a friendly html page
		 *	either way
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackAction( array &$appData, array &$request ): void {

			$query 	= $request['/nino/http/request']['query'] ?? [];
			$result = 'invalid';

			// A query variable is whatever the address carried, arrays included
			// ('?confirm[]=x') - so it is read as a string or not at all, the
			// same reading callbackResponse() gives the post above. A token
			// that is not a string is no token, which is the 'invalid' page
			if( isset( $query['confirm'] ) === true )
				$result = ( is_string( $query['confirm'] ) === true && self::_confirm( $appData, $query['confirm'] ) === true ) ? 'confirmed' : 'invalid';
			else if( isset( $query['unsubscribe'] ) === true )
				$result = ( is_string( $query['unsubscribe'] ) === true && self::_unsubscribe( $appData, $query['unsubscribe'] ) === true ) ? 'unsubscribed' : 'invalid';

			if( $result === 'invalid' )
				$request['/nino/http/response']['statusCode'] = 404;

			// Nested fills - the outer key is what the page template uses,
			// the inner one resolves per-locale from text/*.php
			\Nino\Html::addFills( $appData, [
				'[[/feature/newsletter/page/title]]'	=> '[[/feature/newsletter/result-'. $result. '/title]]',
				'[[/feature/newsletter/page/text]]'		=> '[[/feature/newsletter/result-'. $result. '/text]]',
			], '*' );
		}

		/**
		 *	Build the absolute self-service unsubscribe url for a subscribed
		 *	(or still pending) email - append it to any outgoing newsletter
		 *	mail. Same https://[[/project/website/general/url]] convention the sitemap
		 *	template uses for absolute urls
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Subscriber to build the url for
		 *
		 *	@return 	string | false							Unsubscribe url, or false for an unknown email
		 */
		public static function getUnsubscribeLink( array &$appData, string $email ): string|false {

			$email = mb_strtolower( trim( $email ) );

			foreach( \Nino\Filesystem::getFileContent( $appData, self::PATH, [] ) as $entry )
				if( ( $entry['email'] ?? null ) === $email && empty( $entry['token'] ) === false )
					return self::_getActionUrl( $appData, 'unsubscribe', $entry['token'] );

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
		 *	answer depends on it - no 429, no 500, whatever the mail did
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackUnsubscribeRequest( array &$appData, array &$request ): void {

			if( ( $request['./nino/csrf/blocked'] ?? false ) === true )
				return;

			[ $email, $location ] = self::_posted();

			$form = '[template '. ( $appData['/nino/newsletter/unsubscribe-template'] ?? '/templates/page-newsletter-unsubscribe' ). ']';
			$page = '[template '. ( $appData['/nino/newsletter/page-template'] ?? '/templates/page-newsletter' ). ']';

			// The answer page, the same one whatever was asked: a filled
			// honeypot gets it too, so a bot learns nothing from the status
			// but that it was refused
			$answer = static function( array &$appData, array &$request ) use ( $page ): void {

				\Nino\Html::addFills( $appData, [
					'[[/feature/newsletter/page/title]]'	=> '[[/feature/newsletter/result-unsubscribe-requested/title]]',
					'[[/feature/newsletter/page/text]]'		=> '[[/feature/newsletter/result-unsubscribe-requested/text]]',
				], '*' );

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
		 *	Read the two posted values of a signup or an unsubscribe request
		 *
		 *	Not escaped before validating (see Form::callbackResponse):
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
		 *	answer must not depend on it.
		 *
		 *	Only a mail that is sent charges the per-ip mail cap, so what is left
		 *	of the difference between a listed and an unlisted address is the
		 *	cap (the signup answers 429 at it) and the time the request takes -
		 *	both accepted, see the README. Charging it for an unlisted address as
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

				// Sticky until somebody unsets it (Mail::send() only ever sets it),
				// and this is a new send
				unset( $appData['./nino/mail/ratelimited'] );

				\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/unsubscribe/url]]' => self::_getActionUrl( $appData, 'unsubscribe', $token ) ], '*' );

				$template = $appData['/nino/newsletter/unsubscribe-mail-template'] ?? '/templates/mail-newsletter-unsubscribe';
				$tpl 			= \Nino\Html::renderHtml( $appData, '[template '. $template. ']' );
				$subject 	= \Nino\Html::renderHtml( $appData, '[[/feature/newsletter/subject/unsubscribe]]' );
				$replyTo 	= \Nino\Html::renderHtml( $appData, '[[/project/mail/address/owner]]' );

				\Nino\Mail::send( $appData, $email, $subject, $tpl, $replyTo );

			} catch( \Throwable $e ) {
				trigger_error( 'Newsletter unsubscribe request failed: '. $e->getMessage() );
			}
		}

		/**
		 *	Record a pending signup and mail its confirmation link - never
		 *	thrown, same reasoning as Form::_record: a storage failure is
		 *	answered, not raised. Every address takes the same way: a new one
		 *	is recorded as pending with a fresh token, a still-pending one gets
		 *	its mail again with the same token (the first one may simply never
		 *	have arrived), and one that is already subscribed gets it with the
		 *	token it already has - its status and date stay as they are, so
		 *	confirming that mail changes nothing. Entries written before the
		 *	double opt-in flow (no status/token fields) count as subscribed and
		 *	get a token the first time they are asked for one; confirming that
		 *	token's mail records them with status 'subscribed' and the date of
		 *	the confirmation
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$email				Validated email address
		 *
		 *	@return 	string									'sent' when the mail went out, 'ratelimited' when the mail cap refused it, 'failed' when the entry could not be stored or the mail could not be sent
		 */
		private static function _requestSignup( array &$appData, string $email ): string {

			try {

				$ran 				= false;
				$subscribed	= false;
				$token 			= null;
				// Resolved out here: the closure has no $appData, and the
				// address a visitor counts as depends on the proxy list in it
				// (see \Nino\Http::getClientIp())
				$ip 				= \Nino\Http::getClientIp( $appData );
				$keepSeconds = max( 1, (int) ( $appData[ self::PENDING_DAYS ] ?? 7 ) ) * 86400;

				$written = \Nino\Filesystem::mutate( $appData, self::PATH, function( array $entries ) use ( $email, $ip, $keepSeconds, &$ran, &$subscribed, &$token ): array {

					$ran = true;

					foreach( $entries as $entryKey => $entry ) {

						if( ( $entry['email'] ?? null ) !== $email )
							continue;

						if( ( $entry['status'] ?? 'subscribed' ) === 'subscribed' ) {

							$subscribed = true;
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

				// A signup this loop actually recorded (pending or resent) is
				// a current, freely given consent - clear any earlier removal
				// so a later restore doesn't mistake this resubscription for
				// the resurrection it's specifically meant to prevent. Not for
				// an address that was subscribed already: nothing was recorded
				if( $subscribed === false )
					self::_clearRemoval( $appData, $email );

				if( self::_sendConfirmMail( $appData, $email, $token ) === true )
					return 'sent';

				return ( $appData['./nino/mail/ratelimited'] ?? false ) === true ? 'ratelimited' : 'failed';

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
		 *	@return 	bool										Whether \Nino\Mail::send() delivered the mail - false with './nino/mail/ratelimited' left set when the cap refused it
		 */
		private static function _sendConfirmMail( array &$appData, string $email, string $token ): bool {

			$template = $appData['/nino/newsletter/confirm-template'] ?? '/templates/mail-newsletter-confirm';

			\Nino\Html::addFills( $appData, [ '[[/feature/newsletter/confirm/url]]' => self::_getActionUrl( $appData, 'confirm', $token ) ], '*' );

			$tpl 			= \Nino\Html::renderHtml( $appData, '[template '. $template. ']' );
			$subject 	= \Nino\Html::renderHtml( $appData, '[[/feature/newsletter/subject/confirm]]' );
			$replyTo 	= \Nino\Html::renderHtml( $appData, '[[/project/mail/address/owner]]' );

			// Sticky until somebody unsets it (Mail::send() only ever sets it),
			// so a flag left by an earlier send of this request must not read
			// as this one's refusal - the Mailer panel's test mail does the same
			unset( $appData['./nino/mail/ratelimited'] );

			return \Nino\Mail::send( $appData, $email, $subject, $tpl, $replyTo );
		}

		/**
		 *	Flip a pending entry to subscribed for a visited confirm link.
		 *	Idempotent - confirming an already-subscribed token stays true,
		 *	so a twice-clicked mail link doesn't scare the visitor with an
		 *	error
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token				Token from the visited link
		 *
		 *	@return 	bool										False for an unknown/empty token or a failed write
		 */
		private static function _confirm( array &$appData, string $token ): bool {

			if( $token === '' )
				return false;

			try {

				$found = false;

				\Nino\Filesystem::mutate( $appData, self::PATH, function( array $entries ) use ( $token, &$found ): ?array {

					foreach( $entries as $entryKey => $entry ) {

						if( hash_equals( (string) ( $entry['token'] ?? '' ), $token ) === false )
							continue;

						$found = true;

						if( ( $entry['status'] ?? '' ) === 'subscribed' )
							return null;

						$entries[$entryKey]['status']	= 'subscribed';
						$entries[$entryKey]['date']		= date( 'Y-m-d H:i:s' );

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
		 *	Remove the entry a visited unsubscribe link's token belongs to -
		 *	works for subscribed and still-pending entries alike
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$token				Token from the visited link
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
					self::_recordRemoval( $appData, $email );

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
		 *	@return 	string									Absolute url
		 */
		private static function _getActionUrl( array &$appData, string $action, string $token ): string {

			return 'https://'. \Nino\Html::renderHtml( $appData, '[[/project/website/general/url]]' ). '/.newsletter?'. $action. '='. rawurlencode( $token );
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
		 *	be trusted as complete. A resubscribe clears its own address from
		 *	that list already (see _clearRemoval()'s call site) - this merge
		 *	only ever excludes, never decides who should be excluded. The list
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
					$hash = hash( 'sha256', mb_strtolower( trim( (string) ( $entry['email'] ?? '' ) ) ) );
					return in_array( $hash, $removed, true ) === false;
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

		// Record an email as removed - called on self-service unsubscribe
		// above. Newsletter\Admin::apiDelete() (Admin/Admin.php) does its
		// own equivalent write (same hash) rather than calling this: a
		// static method call autoloads this class just as unconditionally
		// as a constant read does (see Backup::manifest()'s own docblock
		// for the underlying reason), which would turn deleting a
		// subscriber - a routine admin action - into a fatal error for a
		// project that removed this module's file because it never used
		// the public signup routes. This list is never pruned (that's the
		// point - see REMOVED_PATH's own docblock), so only the hash goes
		// in, never the address itself
		private static function _recordRemoval( array &$appData, string $email ): void {

			$hash = hash( 'sha256', mb_strtolower( trim( $email ) ) );

			\Nino\Filesystem::mutate( $appData, self::REMOVED_PATH, function( array $removed ) use ( $hash ): array {

				if( in_array( $hash, $removed, true ) === false )
					$removed[] = $hash;

				return $removed;
			} );
		}

		// Undoes _recordRemoval() for a fresh signup - see its call site in
		// _requestSignup()
		private static function _clearRemoval( array &$appData, string $email ): void {

			$hash = hash( 'sha256', mb_strtolower( trim( $email ) ) );

			\Nino\Filesystem::mutate( $appData, self::REMOVED_PATH, function( array $removed ) use ( $hash ): array {
				return array_values( array_filter( $removed, function( $entry ) use ( $hash ): bool { return $entry !== $hash; } ) );
			} );
		}
	}

}
