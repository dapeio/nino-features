<?php
declare(strict_types=1);

/**
 *	Nino
 *	stats-smoke.php			Contract test for the Stats feature (Modules\Stats):
 *											the manifest and activation through \Nino\Features, what
 *											is and is not counted (method, status, tool/dot uris, the
 *											exclude list, signed-in visitors, a cache hit still
 *											counting), the per-day/per-month storage shape including
 *											the maxUris fold and the retention sweep, the read-only
 *											panel and its permission, the dashboard tile, and
 *											deactivation. Travels with the feature and runs against
 *											the checkout three levels up, or the one NINO_ROOT names
 *											(see tests/harness.php there).
 *
 *	Usage: php features/Stats/tests/stats-smoke.php
 *	       NINO_ROOT=../nino php features/Stats/tests/stats-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'stats' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

/**
 *	Build a request the way \Nino\Http::request() leaves it - method,
 *	rawMethod, uri (query already stripped by Http::cleanUri()), the request
 *	header - and a response seeded at statusCode 200, so a test can call the
 *	counter directly against it without a route ever being resolved. Same
 *	helper and calling convention as tests/kernel-smoke.php's own
 *	fakeRequest(), so the two stay directly comparable.
 *
 *	@param		array 		&$appData
 *	@param		string		$uri					A path, optionally carrying a query string
 *	@param		string		$method
 *	@param		array			$server				Further $_SERVER-shaped keys, eg. HTTP_REFERER, HTTP_HOST
 *
 *	@return 	array
 */
function fakeRequest( array &$appData, string $uri, string $method = 'GET', array $server = [] ): array {
	$request = array_merge( [ 'REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, 'REMOTE_ADDR' => '127.0.0.1' ], $server );
	\Nino\Http::request( $appData, $request );
	return $request;
}

/**
 *	Run the counter against a hand-built request and hand it back, so a
 *	caller can still inspect what it did
 *
 *	@param		array 		&$appData
 *	@param		array 		$request
 *
 *	@return 	array
 */
function countRequest( array &$appData, array $request ): array {
	\Nino\Modules\Stats::callbackCount( $appData, $request );
	return $request;
}

function monthFile( array &$appData, string $month ): array {
	$state = \Nino\Filesystem::getFileContent( $appData, '/data/stats/'. $month. '.php', [] );
	return is_array( $state ) ? $state : [];
}

function dayTotal( array &$appData, string $month, string $day ): int {
	return (int) ( monthFile( $appData, $month )['days'][$day]['total'] ?? 0 );
}

function callAdminPost( array &$appData, string $action, array $data = [] ): array {
	$request = [ '/nino/http/response' => [ 'statusCode' => 200 ] ];
	$_POST['action'] = $action;
	$_POST['data'] = json_encode( $data );
	\Nino\Admin\Admin::handlePost( $appData, $request );
	return [ $request['/nino/http/response']['statusCode'], $request['/nino/http/response']['body'] ?? null ];
}

$today = date( 'Y-m-d' );
$month = date( 'Y-m' );


// --- The feature -----------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates without a warning', is_array( $manifest ) && ninoWarnings() === [] );
check( 'key, class and version are what the directory says', is_array( $manifest ) && $manifest['key'] === 'stats'
	&& $manifest['module'] === '\\Nino\\Modules\\Stats' && $manifest['version'] === '1.0.0' );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names itself in both interface languages', is_array( $manifest ) && \Nino\Features::localized( $manifest['name'], 'de_DE' ) !== \Nino\Features::localized( $manifest['name'], 'en_US' ) );
check( 'it declares only the directory it owns under data/', is_array( $manifest ) && $manifest['data'] === [ '/data/stats' ] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'stats' );
	return $feature !== null && $feature['active'] === false && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'stats' ) === true );
$stored = \Nino\Filesystem::getFileContent( $appData, '/config.php', [] );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Stats', $stored['/nino/modules'], true ) === true
	&& $stored['/nino/features']['stats']['version'] === '1.0.0' );
check( 'the settings answer their defaults', \Nino\Features::settings( $appData, 'stats' ) === [
	'countSignedIn' 	=> false,
	'exclude' 				=> [],
	'maxUris' 				=> 500,
	'retentionMonths'	=> 13,
] );

\Nino\Modules::callModules( $appData, 'init' );
check( 'the panel is registered while the feature is active', isset( \Nino\Admin\Admin::panels( $appData )['stats'] ) === true );

echo "\n";


// --- Retention, tied to the very first count of a new day -------------------

echo "Retention - pruned on the first count of a new day, using the default 13 months\n";

$staleMonth 	= date( 'Y-m', strtotime( '-20 months' ) );
$recentMonth	= date( 'Y-m', strtotime( '-2 months' ) );
$staleMonthPath 	= \Nino\Filesystem::path( $appData, '/data/stats' ). '/'. $staleMonth. '.php';
$recentMonthPath	= \Nino\Filesystem::path( $appData, '/data/stats' ). '/'. $recentMonth. '.php';

\Nino\Filesystem::putFileContent( $appData, '/data/stats/'. $staleMonth. '.php', [ 'days' => [] ] );
\Nino\Filesystem::putFileContent( $appData, '/data/stats/'. $recentMonth. '.php', [ 'days' => [] ] );
check( 'a stale month file exists before the first count', is_file( $staleMonthPath ) === true );
check( 'a recent month file exists before the first count', is_file( $recentMonthPath ) === true );

echo "\n";


// --- What is counted --------------------------------------------------------

echo "Modules\\Stats::callbackCount - a qualifying GET is counted\n";

countRequest( $appData, fakeRequest( $appData, '/hello' ) );
$state = monthFile( $appData, $month );

check( 'the month file exists with the { days: { day: { total, uris, referrers } } } shape', is_array( $state['days'][$today] ?? null ) === true
	&& is_array( $state['days'][$today]['uris'] ?? null ) === true && is_array( $state['days'][$today]['referrers'] ?? null ) === true );
check( 'the first view is the day\'s total', $state['days'][$today]['total'] === 1 );
check( 'the uri is counted once', $state['days'][$today]['uris']['/hello'] === 1 );
check( 'no referrer means nothing under referrers', $state['days'][$today]['referrers'] === [] );

check( '...and pruned the stale month file, older than the default 13 months', is_file( $staleMonthPath ) === false );
check( '...while a recent one, only 2 months old, survives', is_file( $recentMonthPath ) === true );

countRequest( $appData, fakeRequest( $appData, '/hello' ) );
check( 'a second view increments both the total and the uri', dayTotal( $appData, $month, $today ) === 2
	&& monthFile( $appData, $month )['days'][$today]['uris']['/hello'] === 2 );

echo "\n";


echo "Referrers - external host counted, own host and empty dropped\n";

countRequest( $appData, fakeRequest( $appData, '/hello', 'GET', [ 'HTTP_REFERER' => 'https://example.org/page', 'HTTP_HOST' => 'my-site.test' ] ) );
$state = monthFile( $appData, $month );
check( 'an external referrer is counted by host, lowercased', $state['days'][$today]['referrers']['example.org'] === 1 );
check( 'the view still counts towards the total and the uri', $state['days'][$today]['total'] === 3 && $state['days'][$today]['uris']['/hello'] === 3 );

countRequest( $appData, fakeRequest( $appData, '/hello', 'GET', [ 'HTTP_REFERER' => 'https://MY-SITE.test/other', 'HTTP_HOST' => 'my-site.test' ] ) );
$state = monthFile( $appData, $month );
check( 'the site\'s own host as a referrer is dropped, not counted', isset( $state['days'][$today]['referrers']['my-site.test'] ) === false && $state['days'][$today]['referrers'] === [ 'example.org' => 1 ] );
check( 'the view underneath it still counts', $state['days'][$today]['total'] === 4 );

echo "\n";


echo "Query strings are dropped - only the uri is stored\n";

countRequest( $appData, fakeRequest( $appData, '/hello?utm_source=test&ref=x' ) );
$state = monthFile( $appData, $month );
check( 'a query string never reaches the stored uri', array_keys( $state['days'][$today]['uris'] ) === [ '/hello' ] );
check( 'the view still counts', $state['days'][$today]['uris']['/hello'] === 5 && $state['days'][$today]['total'] === 5 );

echo "\n";


echo "What is never counted\n";

$before = dayTotal( $appData, $month, $today );
countRequest( $appData, fakeRequest( $appData, '/_admin' ) );
check( 'a /_admin uri is not counted', dayTotal( $appData, $month, $today ) === $before );

countRequest( $appData, fakeRequest( $appData, '/_admin/features' ) );
check( 'nor is anything under /_admin', dayTotal( $appData, $month, $today ) === $before );

countRequest( $appData, fakeRequest( $appData, '/.form', 'POST' ) );
check( 'a dot-prefixed module endpoint is not counted', dayTotal( $appData, $month, $today ) === $before );

$dotRequest = fakeRequest( $appData, '/.something' );
check( 'a dot-prefixed uri is excluded even as a plain GET 200', dayTotal( $appData, $month, $today ) === $before );
countRequest( $appData, $dotRequest );
check( '...still not counted after actually running the counter on it', dayTotal( $appData, $month, $today ) === $before );

countRequest( $appData, fakeRequest( $appData, '/hello', 'POST' ) );
check( 'a POST is not counted, even to an otherwise countable uri', dayTotal( $appData, $month, $today ) === $before );

$notFoundRequest = fakeRequest( $appData, '/does-not-exist' );
$notFoundRequest['/nino/http/response']['statusCode'] = 404;
countRequest( $appData, $notFoundRequest );
check( 'a non-200 response is not counted', dayTotal( $appData, $month, $today ) === $before );

$headRequest = fakeRequest( $appData, '/hello', 'HEAD' );
check( 'a HEAD is folded to a GET for routing but keeps its raw method', $headRequest['/nino/http/request']['method'] === 'GET' && $headRequest['/nino/http/request']['rawMethod'] === 'HEAD' );
countRequest( $appData, $headRequest );
check( '...and is not counted - "a GET" means the raw method too', dayTotal( $appData, $month, $today ) === $before );

echo "\n";


echo "The exclude setting - exact uris and a wildcard subtree\n";

check( 'exclude is saved', \Nino\Features::saveSettings( $appData, 'stats', [ 'exclude' => "/internal\n/blog/*" ] ) === [] );
check( '...and comes back as two lines', \Nino\Features::setting( $appData, 'stats', 'exclude' ) === [ '/internal', '/blog/*' ] );

$before = dayTotal( $appData, $month, $today );
countRequest( $appData, fakeRequest( $appData, '/internal' ) );
check( 'an excluded exact uri is not counted', dayTotal( $appData, $month, $today ) === $before );

countRequest( $appData, fakeRequest( $appData, '/blog' ) );
check( 'a wildcard pattern also excludes the subtree root itself', dayTotal( $appData, $month, $today ) === $before );

countRequest( $appData, fakeRequest( $appData, '/blog/one/two' ) );
check( '...and everything nested under it', dayTotal( $appData, $month, $today ) === $before );

countRequest( $appData, fakeRequest( $appData, '/blogging' ) );
check( '...but not a uri that merely shares the prefix', dayTotal( $appData, $month, $today ) === $before + 1 );

check( 'exclude is cleared again', \Nino\Features::saveSettings( $appData, 'stats', [ 'exclude' => '' ] ) === [] );

echo "\n";


echo "Signed-in visitors - excluded unless countSignedIn is on\n";

\Nino\Auth::insertUser( $appData, 'admin@example.com', 'correct horse battery staple', [ '/*' ] );
\Nino\Auth::insertUser( $appData, 'plain@example.com', 'plain password', [] );
\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

$before = dayTotal( $appData, $month, $today );
countRequest( $appData, fakeRequest( $appData, '/hello' ) );
check( 'a signed-in visitor is not counted by default', dayTotal( $appData, $month, $today ) === $before );

check( 'countSignedIn is switched on', \Nino\Features::saveSettings( $appData, 'stats', [ 'countSignedIn' => 'true' ] ) === [] );
countRequest( $appData, fakeRequest( $appData, '/hello' ) );
check( 'a signed-in visitor is counted once countSignedIn is on', dayTotal( $appData, $month, $today ) === $before + 1 );

check( 'countSignedIn is switched back off', \Nino\Features::saveSettings( $appData, 'stats', [ 'countSignedIn' => 'false' ] ) === [] );
unset( $appData['./nino/auth/current'] );

echo "\n";


echo "maxUris - beyond the budget, further distinct uris fold into one bucket\n";

check( 'maxUris is saved down to its allowed minimum', \Nino\Features::saveSettings( $appData, 'stats', [ 'maxUris' => '50' ] ) === [] );

$urisBefore = monthFile( $appData, $month )['days'][$today]['uris'] ?? [];
$countBefore = count( $urisBefore );
$overflowBefore = (int) ( $urisBefore[\Nino\Modules\Stats::OVERFLOW_URI] ?? 0 );

$newUris = 60;
for( $i = 0; $i < $newUris; $i++ )
	countRequest( $appData, fakeRequest( $appData, '/generated/'. $i ) );

$urisAfter = monthFile( $appData, $month )['days'][$today]['uris'] ?? [];
$freeSlots = max( 0, 50 - $countBefore );
$expectedOverflow = $overflowBefore + max( 0, $newUris - $freeSlots );

check( 'distinct real uris never exceed maxUris', count( $urisAfter ) - ( isset( $urisAfter[\Nino\Modules\Stats::OVERFLOW_URI] ) ? 1 : 0 ) <= 50 );
check( 'uris beyond the budget are folded into the overflow bucket, counted exactly', ( $urisAfter[\Nino\Modules\Stats::OVERFLOW_URI] ?? 0 ) === $expectedOverflow );

check( 'maxUris is reset to its default', \Nino\Features::saveSettings( $appData, 'stats', [ 'maxUris' => '500' ] ) === [] );

echo "\n";


// --- A cache hit is still counted -------------------------------------------

echo "Modules\\Stats counts a page the cache answers - priority 8 runs before Cache's 9\n";

$appData['/nino/http/routes']['GET://cache-test'] = [ 'uri' => '/cache-test', 'body' => 'hello from the cache test', 'locale' => 'de_DE' ];
$appData['/nino/cache/status'] = true;
$appData['/nino/cache/ttl'] = 60;
if( in_array( '\\Nino\\Modules\\Cache', $appData['/nino/modules'], true ) === false )
	$appData['/nino/modules'][] = '\\Nino\\Modules\\Cache';

// The second view runs in a subprocess (see below) that reloads config.php
// from scratch - everything the cache needs to answer a hit there has to be
// on disk, not just in this process' memory
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/cache/status', '/nino/cache/ttl', '/nino/http/routes' ] );

\Nino\Modules\Cache::init( $appData );
\Nino\Modules\Cache::_invalidate( $appData );

$before = dayTotal( $appData, $month, $today );

$missRequest = fakeRequest( $appData, '/cache-test' );
\Nino\Http::response( $appData, $missRequest );
check( 'the first view (a cache miss) is counted', dayTotal( $appData, $month, $today ) === $before + 1 );
check( '...and is not answered from the cache - nothing stored yet', ( $missRequest['/nino/http/response']['header']['X-Nino-Cache'] ?? '' ) !== 'hit' );

\Nino\Modules\Cache::callbackOutput( $appData, $missRequest );
check( 'the page is now stored in the cache', count( glob( \Nino\Filesystem::path( $appData, '/data/cache' ). '/*.php' ) ?: [] ) === 1 );

// Modules\Cache::callbackResponse() answers a hit by calling \Nino\Http::output(),
// which exit()s - can't be driven in this same process without ending the
// whole suite (same reasoning as tests/kernel-smoke.php's Runtime::handleError()
// section), so a subprocess runs the second view and reports back what it saw
// right up to the point output() ends it
$sandbox = ninoSandboxDir( $appData );
$hitDriver = $sandbox. '/stats-cache-hit-driver.php';

file_put_contents( $hitDriver, '<?php
declare(strict_types=1);
define( "NINO_FEATURES_DIR", '. var_export( dirname( __DIR__, 2 ), true ). ' );
require '. var_export( $root. '/_nino/Nino.php', true ). ';
require '. var_export( $root. '/_admin/Admin.php', true ). ';
set_error_handler( function() { return true; } );

$appData = [ "./nino/uid" => '. var_export( $sandbox, true ). ' ];
\Nino\AppData::prepare( $appData );
$appData["./nino/filesystem/path"] 				= '. var_export( $sandbox, true ). ';
$appData["./nino/filesystem/configpath"] 	= '. var_export( $sandbox. '/private', true ). ';
$appData["./nino/filesystem/contentpath"] 	= '. var_export( $sandbox. '/private', true ). ';
$appData["./nino/filesystem/privatepath"] 	= '. var_export( $sandbox. '/private', true ). ';
$appData["./nino/filesystem/publicpath"]		= '. var_export( $sandbox. '/public', true ). ';
\Nino\Filesystem::init( $appData );
\Nino\AppData::init( $appData );
\Nino\Modules::callModules( $appData, "init" );

$request = array_merge( [ "REQUEST_METHOD" => "GET", "REQUEST_URI" => "/cache-test", "REMOTE_ADDR" => "127.0.0.1" ] );
\Nino\Http::request( $appData, $request );

// A hit answers by calling \Nino\Http::output(), which exit()s right there -
// \Nino\Http::response() below never returns in that case. Registered
// before that call, this shutdown closure still sees $request exactly as
// Cache::callbackResponse() left it, by reference, whichever way this ends;
// $returned distinguishes the two, since register_shutdown_function() itself
// fires on both a plain exit() and a normal end of script
$returned = false;
register_shutdown_function( function() use ( &$request, &$returned ) {
	fwrite( STDOUT, "\n===STATS-CACHE-RESULT===\n". json_encode( [
		"returned"	=> $returned,
		"header"		=> $request["/nino/http/response"]["header"] ?? [],
		"body"			=> $request["/nino/http/response"]["body"] ?? "",
	] ) );
} );

\Nino\Http::response( $appData, $request );
$returned = true;
' );

$hitOutput = (string) shell_exec( 'php '. escapeshellarg( $hitDriver ). ' 2>&1' );
$hitMarker = '===STATS-CACHE-RESULT===';
$hitPos = strpos( $hitOutput, $hitMarker );
$hitResult = $hitPos !== false ? json_decode( trim( substr( $hitOutput, $hitPos + strlen( $hitMarker ) ) ), true ) : null;

check( 'the subprocess produced a readable result', is_array( $hitResult ) === true );
check( 'the second view ends inside Http::output() - Http::response() never returns', ( $hitResult['returned'] ?? true ) === false );
check( '...carrying the X-Nino-Cache: hit header', ( $hitResult['header']['X-Nino-Cache'] ?? '' ) === 'hit' );
check( '...serving the stored body, not a fresh render', ( $hitResult['body'] ?? '' ) === 'hello from the cache test' );

// The subprocess wrote this same month file through its own mutate() call -
// dropped here so the next read is a real one rather than this process' own
// mtime+size fingerprinted copy, which a same-second write of an
// equal-length file (68 -> 69, one digit apiece) can leave looking unchanged
unset( $appData['./nino/filesystem/cache']['/data/stats/'. $month. '.php'] );
check( '...and the counter, registered at priority 8, still ran before Cache answered at priority 9', dayTotal( $appData, $month, $today ) === $before + 2 );

$appData['/nino/cache/status'] = false;
\Nino\Modules\Cache::_invalidate( $appData );

echo "\n";


// --- The panel ---------------------------------------------------------------

echo "Modules\\Stats\\Admin - months, one month's numbers, and the permission\n";

\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

[ $status, $body ] = callAdminPost( $appData, 'stats/months' );
check( 'stats/months succeeds', $status === 200 );
check( '...and lists the current month, newest first', ( $body['months'][0] ?? null ) === $month );

$expectedTotal = dayTotal( $appData, $month, $today );

[ $status, $body ] = callAdminPost( $appData, 'stats/month', [ 'month' => $month ] );
check( 'stats/month succeeds', $status === 200 );
check( '...echoes the month and one day', $body['month'] === $month && count( $body['days'] ) === 1 && $body['days'][0]['day'] === $today );
check( '...the day\'s total matches what was counted, and totals.views agrees', $body['days'][0]['total'] === $expectedTotal && $body['totals']['views'] === $expectedTotal && $body['totals']['days'] === 1 );
check( '...at most 50 uris are returned, most-viewed first', count( $body['uris'] ) <= 50
	&& ( count( $body['uris'] ) < 2 || $body['uris'][0]['views'] >= $body['uris'][1]['views'] ) );
check( '...the referrer is among the top referrers with its count', in_array( [ 'host' => 'example.org', 'views' => 1 ], $body['referrers'], true ) === true );

[ $status ] = callAdminPost( $appData, 'stats/month', [ 'month' => 'not-a-month' ] );
check( 'an invalid month is a 400', $status === 400 );

[ $status, $body ] = callAdminPost( $appData, 'stats/month', [ 'month' => '2000-01' ] );
check( 'a well-formed but empty month is a 200 with zero totals, not an error', $status === 200 && $body['totals']['views'] === 0 && $body['days'] === [] );

check( 'summary() gives the dashboard tile - here, the 7-day total equals today\'s, since everything happened today', \Nino\Modules\Stats::summary( $appData ) === [ 'value' => (string) $expectedTotal, 'label' => '/_admin/stats/label/tile' ] );
check( 'the panel\'s own summary() delegates to it', \Nino\Modules\Stats\Admin::summary( $appData ) === \Nino\Modules\Stats::summary( $appData ) );
check( 'log() never writes an activity-log line - the panel is read-only', \Nino\Modules\Stats\Admin::log( 'stats/month', [] ) === '' );

unset( $appData['./nino/auth/current'] );
[ $status ] = callAdminPost( $appData, 'stats/months' );
check( 'stats/months requires a signed-in account', $status === 401 );
[ $status ] = callAdminPost( $appData, 'stats/month', [ 'month' => $month ] );
check( 'stats/month requires a signed-in account', $status === 401 );

\Nino\Auth::loginUser( $appData, 'plain@example.com', 'plain password' );
[ $status ] = callAdminPost( $appData, 'stats/months' );
check( 'an account without the permission is rejected from stats/months', $status === 403 );
[ $status ] = callAdminPost( $appData, 'stats/month', [ 'month' => $month ] );
check( 'an account without the permission is rejected from stats/month', $status === 403 );

\Nino\Auth::loginUser( $appData, 'admin@example.com', 'correct horse battery staple' );

echo "\n";


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'stats' ) === true );
check( 'the panel is gone with it', isset( \Nino\Admin\Admin::panels( $appData )['stats'] ) === false );
[ $status ] = callAdminPost( $appData, 'stats/months' );
check( 'with the feature off, stats/months is an unknown action', $status === 404 );
check( 'the counted views survive deactivation', dayTotal( $appData, $month, $today ) === $expectedTotal );

ninoDone( $appData );
