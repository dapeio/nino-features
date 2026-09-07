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
check( 'the unit copied the page and the mail templates', array_filter( [ 'page-newsletter.tpl', 'mail-newsletter-confirm.tpl', 'mail-header.tpl', 'mail-footer.tpl' ],
	static fn( string $tpl ): bool => \Nino\Filesystem::fileExists( $appData, '/templates/'. $tpl ) === false ) === [] );
check( 'the unit merged the fills for the available locales', \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/newsletter/label/email]]'] === 'E-Mail-Adresse'
	&& \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/newsletter/label/submit]]'] === 'Subscribe' );

\Nino\Modules::callModules( $appData, 'init' );
check( 'init registers the POST route under /.newsletter', isset( $appData['/nino/http/routes']['POST://.newsletter'] ) === true );
check( 'init registers the GET route (confirm/unsubscribe page) under /.newsletter', isset( $appData['/nino/http/routes']['GET://.newsletter'] ) === true );
check( 'init does not register the old /newsletter route anymore', isset( $appData['/nino/http/routes']['POST://newsletter'] ) === false );
check( 'init registers the restore merge on /nino/admin/restore', isset( $appData['./nino/callbacks']['/nino/admin/restore'] ) === true );

echo "\n";


// --- Signup, confirm, unsubscribe ----------------------------------------------

echo "Modules\\Newsletter - double opt-in signup, confirm, unsubscribe\n";

$missingEmailRequest = submitNewsletter( $appData, [] );
check( 'a missing email is rejected (400)', $missingEmailRequest['/nino/http/response']['statusCode'] === 400 );

$invalidEmailRequest = submitNewsletter( $appData, [ 'email' => 'not-an-email' ] );
check( 'an invalid email is rejected (400)', $invalidEmailRequest['/nino/http/response']['statusCode'] === 400 );

$honeypotRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com', 'location' => 'filled-by-a-bot' ] );
check( 'a filled honeypot is rejected (418)', $honeypotRequest['/nino/http/response']['statusCode'] === 418 );

check( 'none of the rejected signups created the newsletter file', is_file( \Nino\Filesystem::getPath( $appData ). '/data/newsletter.php' ) === false );

$_POST['_csrf'] = 'wrong-token';
$blockedNewsletterRequest = [ '/nino/http/request' => [ 'method' => 'POST' ], '/nino/http/response' => [ 'statusCode' => 200 ] ];
\Nino\Csrf::callbackResponse( $appData, $blockedNewsletterRequest ); // sets 403 + the blocked flag, same as the real POST pipeline
$_POST = array_merge( $_POST, [ 'email' => 'jo@example.com', 'location' => '' ] );
\Nino\Modules\Newsletter::callbackResponse( $appData, $blockedNewsletterRequest );
check( 'a csrf-blocked signup is rejected too', $blockedNewsletterRequest['/nino/http/response']['statusCode'] === 403 );
check( 'a csrf-blocked signup does not create the newsletter file', is_file( \Nino\Filesystem::getPath( $appData ). '/data/newsletter.php' ) === false );
unset( $_POST['_csrf'] );

$okNewsletterRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'a valid signup succeeds (200)', $okNewsletterRequest['/nino/http/response']['statusCode'] === 200 );
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

$dupeRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'a repeated signup while still pending succeeds (200)', $dupeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and reports the same generic status - the confirm mail is simply re-sent', $dupeRequest['/nino/http/response']['body']['status'] === 'ok' );
$subscribers = \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] );
check( 'without creating a duplicate entry', count( $subscribers ) === 1 );
check( 'and without rotating the pending token', $subscribers[0]['token'] === $pendingToken );

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

$subscribedRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
// The response must not distinguish a known address from a new one - that
// would let anyone test whether a given address is subscribed
check( 'signing up a confirmed subscriber again reports the same generic status', $subscribedRequest['/nino/http/response']['body']['status'] === 'ok' );
check( 'and does not create a duplicate entry', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );

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

$resubscribeRequest = submitNewsletter( $appData, [ 'email' => 'jo@example.com' ] );
check( 'resubscribing after an unsubscribe succeeds', $resubscribeRequest['/nino/http/response']['statusCode'] === 200 );
check( 'and creates a fresh pending entry', count( \Nino\Filesystem::getFileContent( $appData, $subscribersPath, [] ) ) === 1 );
check( 'and clears the earlier removal record - a fresh signup is a fresh consent', in_array( $joRemovalHash, \Nino\Filesystem::getFileContent( $appData, '/data/newsletter-removed.php', [] ), true ) === false );

$bareVisitRequest = visitNewsletterLink( $appData, [] );
check( 'a bare GET /.newsletter without confirm/unsubscribe answers 404', $bareVisitRequest['/nino/http/response']['statusCode'] === 404 );

echo "\n";


// --- The panel -----------------------------------------------------------------

echo "Modules\\Newsletter\\Admin - the subscriber list and its permission\n";

// A set-up project with a full-access account, the way the wizard leaves one
$appData['/nino/install/completed'] = true;
\Nino\Auth::insertUser( $appData, 'admin@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::insertUser( $appData, 'plain@example.com', 'plain password', [] );
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

check( 'the panel is in the registry while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['newsletter'] ) === true );

$annaRequest = submitNewsletter( $appData, [ 'email' => 'anna@example.com' ] );
check( 'a second valid signup succeeds', $annaRequest['/nino/http/response']['statusCode'] === 200 );

[ $status, $body ] = callAdminPost( $appData, 'newsletter/list' );
check( 'newsletter/list succeeds', $status === 200 );
check( 'newsletter/list finds both signups, most recent first', count( $body['entries'] ) === 2 && $body['entries'][0]['email'] === 'anna@example.com' );

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

ninoDone( $appData );
