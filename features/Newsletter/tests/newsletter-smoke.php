<?php
declare(strict_types=1);

/**
 *	Nino
 *	newsletter-smoke.php	Contract test for the Newsletter feature (Modules\Newsletter):
 *											the manifest and the activation through \Nino\Features,
 *											the double opt-in signup with confirm and unsubscribe
 *											links, the workbench panel and its permission, and the
 *											restore merge that keeps an unsubscribe unsubscribed.
 *											Travels with the feature and runs against the checkout
 *											three levels up, or the one NINO_ROOT names (see
 *											tests/harness.php there).
 *
 *	Usage: php features/Newsletter/tests/newsletter-smoke.php
 *	       NINO_ROOT=../nino php features/Newsletter/tests/newsletter-smoke.php
 */

// The checkout this test runs against: three levels up when the feature sits
// in a project's features/, else the one NINO_ROOT names (a checkout beside
// the catalogue, say). The features root is this feature's own parent either
// way, so the kernel's autoloader serves the class from here
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'newsletter' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$appData['/nino/dir'] = '';

function submitNewsletter( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'email' => '', 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Newsletter::callbackResponse( $appData, $request );
	return $request;
}

function submitUnsubscribe( array &$appData, array $post ): array {
	$_POST = array_merge( [ 'email' => '', 'location' => '' ], $post );
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $request );
	return $request;
}

function visitNewsletterLink( array &$appData, array $query ): array {
	$request = [ '/nino/http/request' => [ 'query' => $query ], '/nino/http/response' => [ 'statusCode' => 200 ] ];
	\Nino\Modules\Newsletter::callbackAction( $appData, $request );
	return $request;
}

function callAdminPost( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST['action'] = $action;
	$_POST['data'] = json_encode( $data );
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates, key "newsletter"', is_array( $manifest ) && $manifest['key'] === 'newsletter' && ninoWarnings() === [] );
check( 'the feature is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );
check( 'it declares the two data files its restore callback merges', is_array( $manifest ) && $manifest['data'] === [ '/data/newsletter.php', '/data/newsletter-removed.php' ] );

// A project's config.php, written the way the wizard leaves it, so the
// activation has something to add its class and routes to
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the Features registry lists it, inactive', ( \Nino\Features::get( $appData, 'newsletter' )['active'] ?? null ) === false );
check( 'activation succeeds', \Nino\Features::activate( $appData, 'newsletter' ) === true );
check( 'the class is listed in /nino/modules and the version recorded', in_array( '\\Nino\\Modules\\Newsletter', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'newsletter' )['installed'] === $manifest['version'] );
check( 'the unit copied the pages and the mail templates, the unsubscribe form and mail among them', array_filter( [ 'page-newsletter.tpl', 'page-newsletter-unsubscribe.tpl', 'mail-newsletter-confirm.tpl', 'mail-newsletter-unsubscribe.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	static fn( string $tpl ): bool => \Nino\Filesystem::fileExists( $appData, '/templates/'. $tpl ) === false ) === [] );
check( 'the unit merged the fills for the available locales', \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/newsletter/label/email]]'] === 'E-Mail-Adresse'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/newsletter/label/submit]]'] === 'Subscribe' );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the POST route under /.newsletter', isset( $appData['/nino/http/routes']['POST://.newsletter'] ) === true );
check( 'init registers the GET route (confirm/unsubscribe page) under /.newsletter', isset( $appData['/nino/http/routes']['GET://.newsletter'] ) === true );
check( 'init registers the unsubscribe form (GET) and its post under /.newsletter/unsubscribe', isset( $appData['/nino/http/routes']['GET://.newsletter/unsubscribe'] ) === true
	&& isset( $appData['/nino/http/routes']['POST://.newsletter/unsubscribe'] ) === true
	&& isset( $appData['./nino/callbacks']['/nino/http/response/POST://.newsletter/unsubscribe'] ) === true );
check( '...the form with its own template as the body, not the page the links land on', ( $appData['/nino/http/routes']['GET://.newsletter/unsubscribe']['body'] ?? '' ) === '[template /templates/page-newsletter-unsubscribe]' );
check( 'init does not register the old /newsletter route anymore', isset( $appData['/nino/http/routes']['POST://newsletter'] ) === false );
check( 'init registers the restore merge on /nino/admin/restore', isset( $appData['./nino/callbacks']['/nino/admin/restore'] ) === true );

/*	A transport takes the mail so nothing is actually sent - the same
	\Nino\Mail::TRANSPORT callback the Mailer feature registers. Without one the
	signup would call the real mail(), and the answer follows the delivery now:
	a host with no sendmail answers 500. $transportResult is what the stub says
	about the mail, so a refusal is as easy to make as a delivery.

	\Nino\Mail::send() caps a client ip at five mails an hour, and the answer
	follows that too - 429 - so every check on a status starts from a fresh
	window	*/
$mails = [];
$transportResult = true;
\Nino\Callbacks::registerCallback( $appData, \Nino\Mail::TRANSPORT, static function( array &$appData, array &$mail ) use ( &$mails, &$transportResult ): void {
	$mails[] = $mail;
	$mail['sent'] = $transportResult;
} );

$freshMailWindow = static function( array &$appData ): void {
	$state = \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] );
	unset( $state['127.0.0.1'] );
	\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', $state );
	unset( $appData['./nino/mail/ratelimited'] );
};

echo "\n";


// --- Signup, confirm, unsubscribe ----------------------------------------------

echo "Modules\\Newsletter - double opt-in signup, confirm, unsubscribe\n";

$missingEmailRequest = submitNewsletter( $appData, [] );
check( 'a missing email is rejected (400)', $missingEmailRequest['/nino/http/response']['statusCode'] === 400 );

$invalidEmailRequest = submitNewsletter( $appData, [ 'email' => 'not-an-email' ] );
check( 'an invalid email is rejected (400)', $invalidEmailRequest['/nino/http/response']['statusCode'] === 400 );

$honeypotRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com', 'location' => 'filled-by-a-bot' ] );
check( 'a filled honeypot is rejected (418)', $honeypotRequest['/nino/http/response']['statusCode'] === 418 );

check( 'none of the rejected signups created the newsletter file', is_file( \Nino\Filesystem::path( $appData, '/data/newsletter.php' ) ) === false );

$_POST['_csrf'] = 'wrong-token';
$blockedNewsletterRequest = [ '/nino/http/request' => [ 'method' => 'POST' ], '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Csrf::callbackResponse( $appData, $blockedNewsletterRequest ); // sets 403 + the blocked flag, same as the real POST pipeline
$_POST = array_merge( $_POST, [ 'email' => 'jo@example.com', 'location' => '' ] );
\Nino\Modules\Newsletter::callbackResponse( $appData, $blockedNewsletterRequest );
check( 'a csrf-blocked signup is rejected too', $blockedNewsletterRequest['/nino/http/response']['statusCode'] === 403 );
check( 'a csrf-blocked signup does not create the newsletter file', is_file( \Nino\Filesystem::path( $appData, '/data/newsletter.php' ) ) === false );
unset( $_POST['_csrf'] );

$freshMailWindow( $appData );
$okNewsletterRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'a valid signup succeeds (200) - the mail went out', $okNewsletterRequest['/nino/http/response']['statusCode'] === 200 && count( $mails ) === 1 );
check( 'a valid new signup reports the generic status', $okNewsletterRequest['/nino/http/response']['body']['status'] === 'ok' );
check( 'a valid signup bootstraps the newsletter file on the private root', is_file( \Nino\Filesystem::path( $appData, '/data/newsletter.php' ) ) === true );

$subscribersPath 	= '/data/newsletter.php';
$subscribersFile 	= \Nino\Filesystem::path( $appData, $subscribersPath );

$subscribers = \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] );
check( 'exactly one entry was recorded', count( $subscribers ) === 1 );
check( 'the entry is pending, not subscribed - the form submit alone must not subscribe', ( $subscribers[0]['status'] ?? '' ) === 'pending' );
check( 'the entry has the submitted email, a confirm token, a date and ip', $subscribers[0]['email'] === 'jo@example.com' && empty( $subscribers[0]['token'] ) === false && isset( $subscribers[0]['date'] ) === true && $subscribers[0]['ip'] === '127.0.0.1' );

check( 'the file is a plain, human-readable php array file - not an encoded stub', str_starts_with( (string) file_get_contents( $subscribersFile ), "<?php return array (" ) === true );

$pendingToken = $subscribers[0]['token'];

$freshMailWindow( $appData );
$dupeRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'a repeated signup while still pending succeeds (200)', $dupeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and reports the same generic status - the confirm mail is simply re-sent', $dupeRequest['/nino/http/response']['body']['status'] === 'ok' );
$subscribers = \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] );
check( 'without creating a duplicate entry', count( $subscribers ) === 1 );
check( 'and without rotating the pending token', $subscribers[0]['token'] === $pendingToken );

// Nothing says a post or a query carries strings. 'email[]=x' and
// '?confirm[]=x' used to reach a (string) cast, and the warning that raises
// is fatal to the request (see \Nino\Runtime::NON_FATAL_LEVELS) - an
// unauthenticated 500 from an address anybody can visit
ninoWarnings();
$arraySignupRequest = submitNewsletter( $appData, [ 'email' => [ 'jo@example.com' ] ] );
check( 'a signup whose email is an array is refused, not raised at', ninoWarnings() === [] && $arraySignupRequest['/nino/http/response']['statusCode'] === 400 );

$arrayHoneypotRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com', 'location' => [ 'x' ] ] );
check( 'and an array in the honeypot is a refusal like any other value in it', ninoWarnings() === [] && $arrayHoneypotRequest['/nino/http/response']['statusCode'] === 418 );

$arrayConfirmRequest = visitNewsletterLink( $appData, [ 'confirm' => [ 'x' ] ] );
check( 'a confirm link whose token is an array answers 404, not a 500', ninoWarnings() === [] && $arrayConfirmRequest['/nino/http/response']['statusCode'] === 404 );

$arrayUnsubscribeRequest = visitNewsletterLink( $appData, [ 'unsubscribe' => [ 'x' ] ] );
check( '...and so does an unsubscribe link', ninoWarnings() === [] && $arrayUnsubscribeRequest['/nino/http/response']['statusCode'] === 404 );

$wrongTokenRequest = visitNewsletterLink( $appData, [ 'confirm' => 'not-the-token' ] );
check( 'a confirm link with an unknown token answers 404', $wrongTokenRequest['/nino/http/response']['statusCode'] === 404 );
check( 'and leaves the entry pending', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'pending' );
check( 'and prepares the "invalid" page fills', ( $appData['./nino/html/fills']['*']['[[/newsletter/page/title]]'] ?? '' ) === '[[/newsletter/page/invalid/title]]' );

$confirmRequest = visitNewsletterLink( $appData, [ 'confirm' => $pendingToken ] );
check( 'a confirm link with the mailed token answers 200', $confirmRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and flips the entry to subscribed', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'subscribed' );
check( 'and prepares the "confirmed" page fills', ( $appData['./nino/html/fills']['*']['[[/newsletter/page/title]]'] ?? '' ) === '[[/newsletter/page/confirmed/title]]' );

$reconfirmRequest = visitNewsletterLink( $appData, [ 'confirm' => $pendingToken ] );
check( 'confirming twice stays a friendly 200, not an error', $reconfirmRequest['/nino/http/response']['statusCode'] === 200 );

$freshMailWindow( $appData );
$mails = [];
$subscribedRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
// The response must not distinguish a known address from a new one - that
// would let anyone test whether a given address is subscribed
check( 'signing up a confirmed subscriber again reports the same generic status', $subscribedRequest['/nino/http/response']['statusCode'] === 200 && $subscribedRequest['/nino/http/response']['body']['status'] === 'ok' );
check( 'and does not create a duplicate entry', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );
// The sandbox has no template shortcode, so the mail's body is the template
// call as it stands - the link it carries is the fill the class sets for it
check( 'it is mailed exactly like a new address is - once, with the token it already has', count( $mails ) === 1
	&& str_ends_with( (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/confirm/url]]'] ?? '' ), '/.newsletter?confirm='. $pendingToken ) === true );
check( '...and stays subscribed, the mail being no signup', \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] )[0]['status'] === 'subscribed' );

$unsubscribeLink = \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'jo@example.com' );
check( 'getUnsubscribeLink builds the /.newsletter unsubscribe url for a subscriber', is_string( $unsubscribeLink ) === true && str_contains( $unsubscribeLink, '/.newsletter?unsubscribe='. $pendingToken ) === true );
check( 'getUnsubscribeLink is case-insensitive about the email', \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'JO@example.com' ) === $unsubscribeLink );
check( 'getUnsubscribeLink returns false for an unknown email', \Nino\Modules\Newsletter::getUnsubscribeLink( $appData, 'nobody@example.com' ) === false );

$badUnsubscribeRequest = visitNewsletterLink( $appData, [ 'unsubscribe' => 'not-the-token' ] );
check( 'an unsubscribe link with an unknown token answers 404 and removes nothing', $badUnsubscribeRequest['/nino/http/response']['statusCode'] === 404 && count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );

$unsubscribeRequest = visitNewsletterLink( $appData, [ 'unsubscribe' => $pendingToken ] );
check( 'an unsubscribe link with the subscriber\'s token answers 200', $unsubscribeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and removes the entry from the list', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 0 );
check( 'and prepares the "unsubscribed" page fills', ( $appData['./nino/html/fills']['*']['[[/newsletter/page/title]]'] ?? '' ) === '[[/newsletter/page/unsubscribed/title]]' );

// callbackRestore() relies on this record surviving every unsubscribe - see
// the restore section below. A sha256 of the address, not the address itself
$joRemovalHash = hash( 'sha256', 'jo@example.com' );
check( 'the unsubscribe is recorded, for a later restore not to undo it', in_array( $joRemovalHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );

$freshMailWindow( $appData );
$resubscribeRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'resubscribing after an unsubscribe succeeds', $resubscribeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and creates a fresh pending entry', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );
check( 'and clears the earlier removal record - a fresh signup is a fresh consent', in_array( $joRemovalHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === false );

$bareVisitRequest = visitNewsletterLink( $appData, [] );
check( 'a bare GET /.newsletter without confirm/unsubscribe answers 404', $bareVisitRequest['/nino/http/response']['statusCode'] === 404 );

echo "\n";


// --- The answer follows the delivery ------------------------------------------

echo "Modules\\Newsletter - the answer follows the delivery and nothing about the list\n";

$writeList = static function( array &$appData, array $entries ): void {
	\Nino\Filesystem::putFileContent( $appData, '/data/newsletter.php', $entries );
};
$readList = static function( array &$appData ): array {
	return \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] );
};
$entryAt = static fn( string $email, string $status, string $date ): array => [
	'email' => $email, 'token' => bin2hex( random_bytes( 8 ) ), 'status' => $status, 'date' => $date, 'ip' => '203.0.113.7',
];


$listBefore 		= $readList( $appData );
$removedBefore	= \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] );

/*	An address on the list and one that is not take the same way through the
	mail cap and the transport, so what the visitor is answered says what
	happened to the mail and nothing about who is subscribed: 429 when the cap
	refused it, 500 when it could not be sent or the entry could not be stored,
	200 when it went out. An answer that only some addresses could reach would
	be a free test of whether an address is on the list	*/
$memberSince = '2026-01-02 03:04:05';
$memberEntry = $entryAt( 'member@example.com', 'subscribed', $memberSince );
$resetList = static function( array &$appData ) use ( $writeList, $memberEntry, $entryAt ): void {
	$writeList( $appData, [
		$memberEntry,
		$entryAt( 'waiting@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) ),
	] );
};
$answer = static function( array &$appData, string $email ) use ( $freshMailWindow, &$mails ): array {
	$freshMailWindow( $appData );
	$mails = [];
	$request = submitNewsletter( $appData, [ 'email' => $email ] );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, count( $mails ) ];
};
$addresses = [ 'new' => 'brand-new@example.com', 'pending' => 'waiting@example.com', 'subscribed' => 'member@example.com' ];

$resetList( $appData );
$transportResult = false;
$refused = array_map( static fn( string $email ): array => $answer( $appData, $email ), $addresses );
$transportResult = true;
check( 'a transport that refuses the mail answers 500, with no body, for a new, a pending and a subscribed address alike',
	array_unique( array_column( $refused, 0 ) ) === [ 500 ] && array_unique( array_column( $refused, 1 ), SORT_REGULAR ) === [ null ] );
check( '...every one of them having been handed to the transport once', array_unique( array_column( $refused, 2 ) ) === [ 1 ] );
check( '...the pending entry is kept and the new one recorded, a re-submit re-sends it',
	array_column( array_filter( $readList( $appData ), static fn( array $e ): bool => ( $e['status'] ?? '' ) === 'pending' ), 'email' ) === [ 'waiting@example.com', 'brand-new@example.com' ] );

$resetList( $appData );
$state = \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] );
$state['127.0.0.1'] = [ 'tries' => 5, 'reset' => time() + 3600 ];
$capped = [];
foreach( $addresses as $kind => $email ) {
	\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', $state );
	unset( $appData['./nino/mail/ratelimited'] );
	$mails = [];
	$request = submitNewsletter( $appData, [ 'email' => $email ] );
	$capped[$kind] = [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null, count( $mails ) ];
}
check( 'an ip at its mail cap is answered 429 - no body, nothing sent - for a new, a pending and a subscribed address alike',
	array_unique( array_column( $capped, 0 ) ) === [ 429 ] && array_unique( array_column( $capped, 1 ), SORT_REGULAR ) === [ null ] && array_unique( array_column( $capped, 2 ) ) === [ 0 ] );

$resetList( $appData );
$delivered = array_map( static fn( string $email ): array => $answer( $appData, $email ), $addresses );
check( 'a delivered mail is a 200 {status: ok} for all three, each of them mailed once',
	array_unique( array_column( $delivered, 0 ) ) === [ 200 ] && array_unique( array_column( $delivered, 1 ), SORT_REGULAR ) === [ [ 'status' => 'ok' ] ] && array_unique( array_column( $delivered, 2 ) ) === [ 1 ] );

/*	Its stored token, and nothing else of it touched: confirming that mail
	changes nothing, and the signup is no fresh consent to clear a removal for	*/
$memberToken = $memberEntry['token'];
$resetList( $appData );
$memberHash = hash( 'sha256', 'member@example.com' );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', [ $memberHash ] );
$answer( $appData, 'member@example.com' );
$memberNow = array_values( array_filter( $readList( $appData ), static fn( array $e ): bool => $e['email'] === 'member@example.com' ) );
check( 'a subscribed address that signs up again is mailed the confirm link with the token it already has',
	str_ends_with( (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/confirm/url]]'] ?? '' ), '/.newsletter?confirm='. rawurlencode( $memberToken ) ) === true && count( $mails ) === 1 );
check( '...and stays what it was: one entry, subscribed, the same token and date', count( $memberNow ) === 1 && $memberNow[0] === $memberEntry );
check( '...and visiting that link changes nothing', visitNewsletterLink( $appData, [ 'confirm' => $memberToken ] )['/nino/http/response']['statusCode'] === 200
	&& array_values( array_filter( $readList( $appData ), static fn( array $e ): bool => $e['email'] === 'member@example.com' ) ) === [ $memberEntry ] );
check( '...and a removal recorded for the address is not cleared by it', in_array( $memberHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', [] );

// An entry from before the double opt-in flow has no token to put in a mail
$legacyDate = date( 'Y-m-d H:i:s', time() - 900 * 86400 );
$writeList( $appData, [ [ 'email' => 'legacy@example.com', 'date' => $legacyDate ] ] );
[ $legacyStatus ] = $answer( $appData, 'legacy@example.com' );
$legacyNow = $readList( $appData );
check( 'a legacy entry without a token is mailed one, stored in the same write', $legacyStatus === 200 && count( $mails ) === 1 && count( $legacyNow ) === 1
	&& empty( $legacyNow[0]['token'] ) === false
	&& str_ends_with( (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/confirm/url]]'] ?? '' ), '/.newsletter?confirm='. rawurlencode( $legacyNow[0]['token'] ) ) === true );
check( '...its status and date left as they were - it is still subscribed', array_diff_key( $legacyNow[0], [ 'token' => 1 ] ) === [ 'email' => 'legacy@example.com', 'date' => $legacyDate ] );

/*	A list that cannot be locked or written is no mail either: nothing was
	recorded, so there is no token to put in one - and the visitor is told so
	(500), not given the success text for a mail that never left	*/
$resetList( $appData );
$locks = \Nino\Filesystem::path( $appData, '/data/.locks' );
$locksAside = $locks. '-aside';
rename( $locks, $locksAside );
file_put_contents( $locks, 'not a directory' );
ninoWarnings();
[ $unlockableStatus, $unlockableBody, $unlockableMails ] = $answer( $appData, 'brand-new@example.com' );
ninoWarnings();
unlink( $locks );
rename( $locksAside, $locks );
check( 'a list that cannot be locked answers 500 and sends nothing', $unlockableStatus === 500 && $unlockableBody === null && $unlockableMails === 0
	&& in_array( 'brand-new@example.com', array_column( $readList( $appData ), 'email' ), true ) === false );

// What the sections that follow start from
$writeList( $appData, $listBefore );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', $removedBefore );
$freshMailWindow( $appData );

echo "\n";


// --- The way out without a link --------------------------------------------------

echo "Modules\\Newsletter - /.newsletter/unsubscribe: an address in, a mail with the link out\n";

$listBefore 		= $readList( $appData );
$removedBefore	= \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] );

$memberEntry = $entryAt( 'member@example.com', 'subscribed', '2026-01-02 03:04:05' );
$waitingEntry = $entryAt( 'waiting@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) );
$legacyEntry = [ 'email' => 'legacy@example.com', 'date' => '2025-05-06 07:08:09' ];
$writeList( $appData, [ $memberEntry, $waitingEntry, $legacyEntry ] );
$listBeforeAsks = $readList( $appData );

$pageFills = static fn( array $appData ): array => array_intersect_key( $appData['./nino/html/fills']['*'] ?? [], [ '[[/newsletter/page/title]]' => 1, '[[/newsletter/page/text]]' => 1 ] );
$errorFill = static fn( array $appData ): string => (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/unsubscribe/error]]'] ?? '' );
$asked = static function( array &$appData, array $post ) use ( $freshMailWindow, &$mails, $pageFills ): array {
	$freshMailWindow( $appData );
	$mails = [];
	$request = submitUnsubscribe( $appData, $post );
	return [ 'status' => $request['/nino/http/response']['statusCode'], 'body' => $request['/nino/http/response']['body'] ?? null, 'fills' => $pageFills( $appData ), 'mails' => count( $mails ) ];
};

$formRequest = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Modules\Newsletter::callbackUnsubscribeForm( $appData, $formRequest );
check( 'the form page starts without an error line', ( $appData['./nino/html/fills']['*']['[[/newsletter/unsubscribe/error]]'] ?? 'unset' ) === '' && $formRequest['/nino/http/response']['statusCode'] === 200 );

$known = $asked( $appData, [ 'email' => 'Member@Example.com' ] );
$knownToken = $memberEntry['token'];
check( 'a known address is answered 200 with the page that says a link is on its way, if the address is on the list',
	$known['status'] === 200 && $known['body'] === '[template /templates/page-newsletter]'
	&& $known['fills']['[[/newsletter/page/title]]'] === '[[/newsletter/page/unsubscribe-requested/title]]'
	&& $known['fills']['[[/newsletter/page/text]]'] === '[[/newsletter/page/unsubscribe-requested/text]]' );
check( '...and gets exactly one mail, to that address, carrying the link with its stored token', $known['mails'] === 1 && $mails[0]['to'] === 'member@example.com'
	&& str_ends_with( (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/unsubscribe/url]]'] ?? '' ), '/.newsletter?unsubscribe='. rawurlencode( $knownToken ) ) === true
	&& str_contains( (string) $mails[0]['body'], 'mail-newsletter-unsubscribe' ) === true );
check( '...nothing is removed by asking - the list is as it was until the link is visited', $readList( $appData ) === $listBeforeAsks );

$unknown = $asked( $appData, [ 'email' => 'nobody@example.com' ] );
check( 'an unknown address gets the identical answer - status, body and fills - and no mail', $unknown['mails'] === 0 && $unknown['status'] === $known['status'] && $unknown['body'] === $known['body'] && $unknown['fills'] === $known['fills'] );
check( '...and the list is not touched by it', $readList( $appData ) === $listBeforeAsks );

/*	An unknown address sends nothing and so charges nothing: the per-ip mail
	cap stays as it was. That is a difference to a listed address (which
	charges it with its mail), accepted and documented in the README - the
	check only keeps it from growing into something else, such as a send to an
	empty recipient, which a kernel that records failed deliveries would count
	as one	*/
$triesOf = static fn( array &$appData ): int => (int) ( \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] )['127.0.0.1']['tries'] ?? 0 );
$triesBefore = $triesOf( $appData );
$asked( $appData, [ 'email' => 'nobody@example.com' ] );
check( 'an unknown address leaves the mail cap untouched', $triesOf( $appData ) === $triesBefore );
$writeList( $appData, $listBeforeAsks );
$freshMailWindow( $appData );
$mails = [];

$waiting = $asked( $appData, [ 'email' => 'waiting@example.com' ] );
check( 'a pending address gets the mail as well, with its token', $waiting['mails'] === 1 && $waiting['status'] === 200
	&& str_ends_with( (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/unsubscribe/url]]'] ?? '' ), '/.newsletter?unsubscribe='. rawurlencode( $waitingEntry['token'] ) ) === true );

$legacy = $asked( $appData, [ 'email' => 'legacy@example.com' ] );
$legacyStored = array_values( array_filter( $readList( $appData ), static fn( array $e ): bool => $e['email'] === 'legacy@example.com' ) );
check( 'an entry without a token gets one, in the same write, and the mail goes out with it', $legacy['mails'] === 1 && count( $legacyStored ) === 1 && empty( $legacyStored[0]['token'] ) === false
	&& str_ends_with( (string) ( $appData['./nino/html/fills']['*']['[[/newsletter/unsubscribe/url]]'] ?? '' ), '/.newsletter?unsubscribe='. rawurlencode( $legacyStored[0]['token'] ) ) === true
	&& array_diff_key( $legacyStored[0], [ 'token' => 1 ] ) === $legacyEntry );

// The mail's template is the project's to swap
$appData['/nino/newsletter/unsubscribe-mail-template'] = '/templates/own-unsubscribe-mail';
$swapped = $asked( $appData, [ 'email' => 'member@example.com' ] );
unset( $appData['/nino/newsletter/unsubscribe-mail-template'] );
check( 'the template the config key names is the one the mail is made of', $swapped['mails'] === 1 && str_contains( (string) $mails[0]['body'], 'own-unsubscribe-mail' ) === true );

$invalid = $asked( $appData, [ 'email' => 'not-an-email' ] );
check( 'an invalid address is a 400 with the form and its error line, and no mail', $invalid['status'] === 400 && $invalid['mails'] === 0
	&& $invalid['body'] === '[template /templates/page-newsletter-unsubscribe]' && $errorFill( $appData ) === '[[/newsletter/info/email]]' );
$empty = $asked( $appData, [] );
check( '...and so is none at all', $empty['status'] === 400 && $empty['mails'] === 0 );

$bot = $asked( $appData, [ 'email' => 'member@example.com', 'location' => 'filled-by-a-bot' ] );
check( 'a filled honeypot is a 418 with the answer page, and no mail', $bot['status'] === 418 && $bot['mails'] === 0 && $bot['body'] === '[template /templates/page-newsletter]' );
ninoWarnings();
$botArray = $asked( $appData, [ 'email' => [ 'member@example.com' ], 'location' => [ 'x' ] ] );
check( 'a post of arrays is refused like any other, not raised at', ninoWarnings() === [] && $botArray['status'] === 418 && $botArray['mails'] === 0 );

// Whatever the mail does, the answer does not say: no 429 for the ones
// that are on the list and a 200 for the ones that are not
$transportResult = false;
$refusedAsk = $asked( $appData, [ 'email' => 'member@example.com' ] );
$transportResult = true;
check( 'a transport that refuses the mail is still answered 200 - never a 500 that only a known address could reach', $refusedAsk['mails'] === 1 && $refusedAsk['status'] === $known['status'] && $refusedAsk['body'] === $known['body'] && $refusedAsk['fills'] === $known['fills'] );

$freshMailWindow( $appData );
$state = \Nino\Filesystem::getFileContent( $appData, '/data/ratelimit.php', [] );
$state['127.0.0.1'] = [ 'tries' => 5, 'reset' => time() + 3600 ];
\Nino\Filesystem::putFileContent( $appData, '/data/ratelimit.php', $state );
$mails = [];
$cappedAsk = submitUnsubscribe( $appData, [ 'email' => 'member@example.com' ] );
check( 'an ip at its mail cap is still answered 200, with nothing sent - never a 429 that only a known address could reach', $cappedAsk['/nino/http/response']['statusCode'] === 200
	&& $mails === [] && $pageFills( $appData ) === $known['fills'] );

// A post the csrf check refused writes and sends nothing
$freshMailWindow( $appData );
$mails = [];
$_POST['_csrf'] = 'wrong-token';
$blockedAsk = [ '/nino/http/request' => [ 'method' => 'POST' ], '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Csrf::callbackResponse( $appData, $blockedAsk );
$_POST = array_merge( $_POST, [ 'email' => 'legacy@example.com', 'location' => '' ] );
$before = $readList( $appData );
\Nino\Modules\Newsletter::callbackUnsubscribeRequest( $appData, $blockedAsk );
unset( $_POST['_csrf'] );
check( 'a csrf-blocked post is refused (403), sends nothing and writes nothing', $blockedAsk['/nino/http/response']['statusCode'] === 403 && $mails === [] && $readList( $appData ) === $before );

// Only the link takes anybody off the list
check( 'visiting the link the mail carried removes the entry and records the removal', visitNewsletterLink( $appData, [ 'unsubscribe' => $knownToken ] )['/nino/http/response']['statusCode'] === 200
	&& in_array( 'member@example.com', array_column( $readList( $appData ), 'email' ), true ) === false
	&& in_array( hash( 'sha256', 'member@example.com' ), \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );

$writeList( $appData, $listBefore );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', $removedBefore );
$freshMailWindow( $appData );

echo "\n";


// --- The panel -----------------------------------------------------------------

echo "Modules\\Newsletter\\Admin - the subscriber list and its permission\n";

// A set-up project with a full-access account, the way the wizard leaves one
$appData['/nino/install/completed'] = true;
\Nino\Auth::insertUser( $appData, 'admin@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::insertUser( $appData, 'plain@example.com', 'plain password', [] );
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

/*	The page and the confirmation mail carry [[/newsletter/page/title]],
	[[/newsletter/page/text]] and [[/newsletter/confirm/url]], which the class
	fills at request time and no text file answers. The Text panel's scan for
	missing keys reads the templates as source, so without the unit's
	blacklist entries it reported the three - and the Dashboard counted them -
	on every project with this feature, as gaps nobody could close	*/
[ $status, $body ] = callAdminPost( $appData, 'keys/scan' );
check( 'the Text panel\'s scan reports no key of this feature: what the templates use is in the text files or in the blacklist', $status === 200
	&& array_filter( array_column( (array) ( $body['missing'] ?? [] ), 'key' ), static fn( string $key ): bool => str_starts_with( $key, '/newsletter/' ) === true ) === [] );

check( 'the panel is in the registry while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['newsletter'] ) === true );

$freshMailWindow( $appData );
$annaRequest = submitNewsletter( $appData, [ 'email' => 'anna@example.com' ] );
check( 'a second valid signup succeeds', $annaRequest['/nino/http/response']['statusCode'] === 200 );

[ $status, $body ] = callAdminPost( $appData, 'newsletter/list' );
check( 'newsletter/list succeeds', $status === 200 );
check( 'newsletter/list finds both signups, most recent first', count( $body['entries'] ) === 2 && $body['entries'][0]['email'] === 'anna@example.com' );

/*	A token is not a field, it is a credential: ?unsubscribe=<token> on the
	public route takes that address off the list and ?confirm=<token> confirms
	a signup, both with nothing else to show. The panel never draws it and
	deletes by address - but Nino.admin.exportCsv() writes the union of every
	row's keys, so it went into a file that is opened in a spreadsheet, mailed
	around and handed to a sending provider	*/
check( 'the list the panel gets carries no token, so neither does the csv it exports', count( array_filter( $body['entries'], static fn( array $e ): bool => isset( $e['token'] ) ) ) === 0 );
check( '...while the record of the consent stays, which is what the panel is for', count( array_filter( $body['entries'], static fn( array $e ): bool => isset( $e['email'], $e['date'], $e['ip'] ) ) ) === 2 );
check( '...and the token is still stored, or no link in a sent mail would work again', count( array_filter(
	\Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ),
	static fn( array $e ): bool => ( $e['token'] ?? '' ) !== ''
) ) === 2 );

/*	A signup nobody confirmed has agreed to nothing: the count, the Dashboard's
	tile and the BCC line are the confirmed addresses, and the list says which
	is which	*/
\Nino\Html::addFills( $appData, [ '[[/website/url]]' => 'example.org' ], '*' );
check( 'with both signups unconfirmed the count and the Dashboard tile say 0', \Nino\Modules\Newsletter\Admin::count( $appData ) === 0
	&& \Nino\Modules\Newsletter\Admin::summary( $appData )['value'] === 0 );
check( '...and every row of the list says it is pending', array_column( $body['entries'], 'status' ) === [ 'pending', 'pending' ] && $body['counts'] === [ 'subscribed' => 0, 'pending' => 2 ] );

$annaToken = array_column( \Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ), 'token', 'email' )['anna@example.com'];
visitNewsletterLink( $appData, [ 'confirm' => $annaToken ] );
check( 'a confirmed signup is counted, and the tile with it', \Nino\Modules\Newsletter\Admin::count( $appData ) === 1
	&& \Nino\Modules\Newsletter\Admin::summary( $appData )['value'] === 1 );

// An entry from before the double opt-in flow has no status at all
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter.php', array_merge(
	\Nino\Filesystem::getFileContent( $appData, '/data/newsletter.php', [] ),
	[ [ 'email' => 'legacy@example.com', 'date' => '2025-05-06 07:08:09' ] ]
) );
[ , $body ] = callAdminPost( $appData, 'newsletter/list' );
check( 'the list carries a status for every row - an entry with none reads as subscribed', array_column( $body['entries'], 'status', 'email' ) === [ 'legacy@example.com' => 'subscribed', 'anna@example.com' => 'subscribed', 'jo@example.com' => 'pending' ] );
check( '...the counts of both, a legacy entry among the subscribed', $body['counts'] === [ 'subscribed' => 2, 'pending' => 1 ] && \Nino\Modules\Newsletter\Admin::count( $appData ) === 2 );
check( '...the address of the page where a subscriber asks for a link, built like the links in the mails', ( $body['unsubscribeUrl'] ?? '' ) === 'https://example.org/.newsletter/unsubscribe' );
check( '...and still no token in any row, whatever its status', count( array_filter( $body['entries'], static fn( array $e ): bool => isset( $e['token'] ) ) ) === 0 );
callAdminPost( $appData, 'newsletter/delete', [ 'email' => 'legacy@example.com' ] );
\Nino\Filesystem::putFileContent( $appData, '/data/newsletter-removed.php', [] );

unset( $appData['./nino/auth/current'] );
[ $status ] = callAdminPost( $appData, 'newsletter/list' );
check( 'newsletter/list requires a signed-in account', $status === 401 );
[ $status ] = callAdminPost( $appData, 'newsletter/delete', [ 'email' => 'jo@example.com' ] );
check( 'newsletter/delete requires a signed-in account', $status === 401 );

\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );
[ $status ] = callAdminPost( $appData, 'newsletter/list' );
check( 'an account without the permission is rejected from newsletter/list', $status === 403 );
[ $status ] = callAdminPost( $appData, 'newsletter/delete', [ 'email' => 'jo@example.com' ] );
check( 'an account without the permission is rejected from newsletter/delete', $status === 403 );

\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

[ $status ] = callAdminPost( $appData, 'newsletter/delete', [ 'email' => 'does-not-exist@example.com' ] );
check( 'newsletter/delete 404s for an email that was never subscribed', $status === 404 );

[ $status ] = callAdminPost( $appData, 'newsletter/delete', [ 'email' => 'jo@example.com' ] );
check( 'newsletter/delete succeeds for an existing subscriber', $status === 200 );

[ , $body ] = callAdminPost( $appData, 'newsletter/list' );
check( 'the deleted subscriber is gone, the other one remains', count( $body['entries'] ) === 1 && $body['entries'][0]['email'] === 'anna@example.com' );

check( 'the admin delete also records the removal, same as a self-service unsubscribe', in_array( hash( 'sha256', 'jo@example.com' ), \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === true );

// The panel is the feature's: switch the feature off and the screen and its
// actions are gone from the workbench
check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'newsletter' ) === true );
check( 'with the feature off, its panel is not in the registry', isset( \Nino\Admin\Admin::panels( $appData )['newsletter'] ) === false );
[ $status ] = callAdminPost( $appData, 'newsletter/list' );
check( 'with the feature off, newsletter/list is an unknown action', $status === 404 );
check( 'the subscribers survive the deactivation', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );

echo "\n";


// --- Restore -------------------------------------------------------------------

echo "Modules\\Newsletter::callbackRestore - a restore merges instead of overwriting (Art. 17)\n";

// The merge is reached from the Backups panel's Restore through the
// '/nino/admin/restore' callback init() registers - exercised here directly
// rather than through a real two-backup round trip: the daily backup only
// ever creates one per calendar day, so a sandboxed run cannot produce an
// "older backup" and a "current, since-changed state" days apart
$mergeInvoke = static function( string $dataDir, string $staging ) use ( &$appData ): void {
	$args = [ 'dataDir' => $dataDir, 'staging' => $staging ];
	\Nino\Modules\Newsletter::callbackRestore( $appData, $args );
};

$sandbox			= ninoSandboxDir( $appData );
$mergeRoot 		= $sandbox. '/merge-root';
$mergeStaging = $sandbox. '/merge-staging';
mkdir( $mergeRoot. '/data', 0755, true );
mkdir( $mergeStaging. '/data', 0755, true );

// root (current): alice unsubscribed since the backup was taken - gone from
// newsletter.php, recorded as a sha256 in newsletter-removed.php; bob untouched
$aliceHash = hash( 'sha256', 'alice@example.com' );
file_put_contents( $mergeRoot. '/data/newsletter.php', '<?php return [ [ "email" => "bob@example.com", "status" => "subscribed" ] ];' );
file_put_contents( $mergeRoot. '/data/newsletter-removed.php', '<?php return [ '. var_export( $aliceHash, true ). ' ];' );

// staging (the backup being restored): taken before Alice unsubscribed, so it
// still has her subscription and no removal record of its own
file_put_contents( $mergeStaging. '/data/newsletter.php', '<?php return [ [ "email" => "alice@example.com", "status" => "subscribed" ], [ "email" => "bob@example.com", "status" => "subscribed" ] ];' );

$mergeInvoke( $mergeRoot. '/data', $mergeStaging );

$mergedEntries = include $mergeStaging. '/data/newsletter.php';
$mergedRemoved = include $mergeStaging. '/data/newsletter-removed.php';

check( 'a restore does not resurrect an address unsubscribed since the backup was taken', in_array( 'alice@example.com', array_column( $mergedEntries, 'email' ), true ) === false );
check( 'an untouched subscriber survives the restore', in_array( 'bob@example.com', array_column( $mergedEntries, 'email' ), true ) === true );
check( 'the removal record itself is carried into the restored state, not just the filtered entries', in_array( $aliceHash, $mergedRemoved, true ) === true );

// A project without the feature carries neither file. Merging that state
// must be a no-op, not an error
$mergeRoot2 	= $sandbox. '/merge-root2';
$mergeStaging2 = $sandbox. '/merge-staging2';
mkdir( $mergeRoot2, 0755, true );
mkdir( $mergeStaging2, 0755, true );

$mergeInvoke( $mergeRoot2, $mergeStaging2 );
check( 'a backup with no newsletter files at all is a no-op, not an error', is_file( $mergeStaging2. '/data/newsletter.php' ) === false );


// --- What an unconfirmed signup costs ----------------------------------------

echo "Modules\\Newsletter - an unconfirmed signup expires, and there is a ceiling under it\n";

/*	The signup endpoint is public and unauthenticated, and mutate() rewrites
	the whole file on every post. Without an expiry and a ceiling, 2000 posts
	with distinct addresses stored 404 KB, none of it expiring, and took a
	signup from 0.97 ms to 5.87 ms because each one reads and rewrites
	everything before it	*/
$writeList( $appData, [
	$entryAt( 'stale@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 8 * 86400 ) ),
	$entryAt( 'fresh@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 ) ),
	$entryAt( 'member@example.com', 'subscribed', date( 'Y-m-d H:i:s', time() - 400 * 86400 ) ),
	[ 'email' => 'ancient@example.com', 'date' => date( 'Y-m-d H:i:s', time() - 900 * 86400 ) ],
] );
submitNewsletter( $appData, [ 'email' => 'arrival@example.com' ] );
$afterSweep = array_column( $readList( $appData ), 'email' );

check( 'an unconfirmed signup that ran out of time is dropped when the next one arrives', in_array( 'stale@example.com', $afterSweep, true ) === false );
check( '...while one still inside the window stays', in_array( 'fresh@example.com', $afterSweep, true ) === true );
check( 'a subscriber is never swept, however old the entry', in_array( 'member@example.com', $afterSweep, true ) === true );
check( '...and neither is an entry from before the double opt-in flow, which counts as subscribed', in_array( 'ancient@example.com', $afterSweep, true ) === true );

// The days are a project's to set - a slower audience confirms later
$appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] = 30;
$writeList( $appData, [ $entryAt( 'week-old@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 8 * 86400 ) ) ] );
submitNewsletter( $appData, [ 'email' => 'arrival2@example.com' ] );
check( 'a longer window keeps what the default would have dropped', in_array( 'week-old@example.com', array_column( $readList( $appData ), 'email' ), true ) === true );
unset( $appData[ \Nino\Modules\Newsletter::PENDING_DAYS ] );

/*	And the ceiling, for a burst that arrives faster than a week passes. The
	oldest unconfirmed entry makes room; a subscriber is not counted and not
	touched	*/
$flood = [ $entryAt( 'keeper@example.com', 'subscribed', date( 'Y-m-d H:i:s' ) ) ];
for( $i = 0; $i < \Nino\Modules\Newsletter::PENDING_LIMIT + 50; $i++ )
	$flood[] = $entryAt( 'flood'. $i. '@example.com', 'pending', date( 'Y-m-d H:i:s', time() - 3600 + $i ) );
$writeList( $appData, $flood );
submitNewsletter( $appData, [ 'email' => 'last@example.com' ] );
$afterFlood = $readList( $appData );
$stillPending = array_filter( $afterFlood, static fn( array $e ): bool => ( $e['status'] ?? '' ) === 'pending' );

check( 'the unconfirmed entries are held to the ceiling', count( $stillPending ) <= \Nino\Modules\Newsletter::PENDING_LIMIT );
check( '...and what went is the oldest of them, not the newest', in_array( 'flood0@example.com', array_column( $afterFlood, 'email' ), true ) === false
	&& in_array( 'last@example.com', array_column( $afterFlood, 'email' ), true ) === true );
check( '...while the subscriber is still there - a ceiling on unconfirmed entries is not a ceiling on the list', in_array( 'keeper@example.com', array_column( $afterFlood, 'email' ), true ) === true );
check( 'the stored list is a list again, with no gaps left by what was removed', array_keys( $afterFlood ) === range( 0, count( $afterFlood ) - 1 ) );

echo "\n";


// --- The confirmation mail's Reply-To ----------------------------------------

echo "Modules\\Newsletter::_sendConfirmMail - who a confirmation can be replied to\n";

/*	The reply address is '[[/form/email/owner]]', which the base install unit
	ships as '[[/company/email]]' - the mailbox the project already named. It
	is the base unit's since Nino 1.3.0, where it used to be the Form module's
	and a project without the contact form therefore had none; the chained
	value is resolved here rather than assumed, because a fill that resolves to
	another fill is where "it is installed" stops meaning "it is an address".

	A transport, registered at the top, takes the mail so nothing is
	actually sent	*/

\Nino\Html::addFills( $appData, [ '[[/company/email]]' => 'hallo@example.com', '[[/form/email/owner]]' => '[[/company/email]]' ], '*' );
$mails = [];
$writeList( $appData, [] );
$freshMailWindow( $appData );
submitNewsletter( $appData, [ 'email' => 'reply-to@example.org' ] );

check( 'the confirmation mail carries the mailbox the project named, through the chained fill',
	( $mails[0]['replyTo'] ?? '' ) === 'hallo@example.com'
	&& str_contains( (string) ( $mails[0]['headers'] ?? '' ), 'Reply-To: hallo@example.com' ) === true );

/*	And where the fill is not installed at all, the kernel drops it rather than
	sending a header naming a fill: the recipient and the body were never the
	problem, and a confirmation nobody receives is worse than one nobody can
	reply to. Checked here because it is what this feature relies on - it hands
	Mail::send() whatever the fill rendered to	*/
\Nino\Html::addFills( $appData, [ '[[/company/email]]' => '', '[[/form/email/owner]]' => '' ], '*' );
$mails = [];
$writeList( $appData, [] );
$freshMailWindow( $appData );
ninoWarnings();
$unresolved = submitNewsletter( $appData, [ 'email' => 'no-owner@example.org' ] );

check( 'with no owner mailbox installed, the mail still goes out and carries no Reply-To at all',
	( $unresolved['/nino/http/response']['statusCode'] ?? 0 ) === 200 && count( $mails ) === 1
	&& ( $mails[0]['replyTo'] ?? 'x' ) === ''
	&& str_contains( (string) ( $mails[0]['headers'] ?? '' ), 'Reply-To:' ) === false );

echo "\n";


// --- The panel's script, where node is on the path ------------------------------
//
// newsletter-js-smoke.js beside this file draws the list over a dom stand-in;
// this suite runs it too where node is on the path, the way stats-smoke.php runs
// its twin, so bin/check.sh and CI cover both halves in one go
$jsTest	= __DIR__. '/newsletter-js-smoke.js';
$node		= function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false ) {
	echo "  --  - node is not available here: newsletter-js-smoke.js was NOT run\n";
} else {
	$output = []; $status = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( $jsTest ). ' 2>&1', $output, $status );
	$summary = (string) end( $output );
	check( 'newsletter-js-smoke.js passes - '. ( $summary === '' ? 'no output' : $summary ), $status === 0 );
}

ninoDone( $appData );
